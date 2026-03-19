<?php
/**
 * BAE — Stripe Payment Integration
 * File: stripe.php
 * Include via: require_once BNTM_BAE_PATH . 'stripe.php';
 *
 * Handles:
 *  - Stripe Checkout Session creation
 *  - Webhook verification and plan upgrades
 *  - Payment success redirect handling
 */

if (!defined('ABSPATH')) exit;

// ─────────────────────────────────────────────────────────────────
// ENV — Load .env file if present
// ─────────────────────────────────────────────────────────────────
if (file_exists(dirname(__FILE__) . '/.env') && !getenv('BAE_STRIPE_SECRET_KEY')) {
    $env_file = dirname(__FILE__) . '/.env';
    $lines    = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$key, $val] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key); $val = trim($val);
        if (!empty($key) && !getenv($key)) putenv("{$key}={$val}");
    }
}

// ─────────────────────────────────────────────────────────────────
// CONFIG — Stripe API Keys (from .env)
// Get keys from: dashboard.stripe.com → Developers → API Keys
// ─────────────────────────────────────────────────────────────────
if (!defined('BAE_STRIPE_SECRET_KEY')) {
    define('BAE_STRIPE_SECRET_KEY',  getenv('BAE_STRIPE_SECRET_KEY')  ?: 'MISSING_SECRET_KEY');
    define('BAE_STRIPE_PUBLIC_KEY',  getenv('BAE_STRIPE_PUBLIC_KEY')  ?: 'MISSING_PUBLIC_KEY');
    define('BAE_STRIPE_WEBHOOK_SECRET', getenv('BAE_STRIPE_WEBHOOK_SECRET') ?: 'MISSING_WEBHOOK_SECRET');
}

define('BAE_STRIPE_API', 'https://api.stripe.com/v1');

// ─────────────────────────────────────────────────────────────────
// PLAN → PRICE ID MAP
// Create products/prices in Stripe dashboard, paste IDs here
// or set via .env as BAE_STRIPE_PRICE_STARTER and BAE_STRIPE_PRICE_PRO
// ─────────────────────────────────────────────────────────────────
function bae_stripe_price_ids() {
    return [
        'starter_monthly'  => getenv('BAE_STRIPE_PRICE_STARTER_MONTHLY')  ?: '',
        'starter_lifetime' => getenv('BAE_STRIPE_PRICE_STARTER_LIFETIME') ?: '',
        'pro_monthly'      => getenv('BAE_STRIPE_PRICE_PRO_MONTHLY')      ?: '',
    ];
}

// ─────────────────────────────────────────────────────────────────
// AJAX: Create Stripe Checkout Session
// ─────────────────────────────────────────────────────────────────
add_action('wp_ajax_bae_pm_checkout',        'bae_ajax_stripe_checkout');
add_action('wp_ajax_nopriv_bae_pm_checkout', 'bae_ajax_stripe_checkout');
function bae_ajax_stripe_checkout() {
    check_ajax_referer('bae_pm_checkout', 'nonce');

    if (BAE_STRIPE_SECRET_KEY === 'MISSING_SECRET_KEY') {
        wp_send_json_error(['message' => 'Stripe is not configured.']);
    }

    $plan     = sanitize_text_field($_POST['plan']    ?? 'starter');
    $billing  = sanitize_text_field($_POST['billing'] ?? 'monthly');
    $ticket   = '';
    if (!empty($_COOKIE['bae_ticket'])) {
        $raw = strtoupper(sanitize_text_field($_COOKIE['bae_ticket']));
        if (preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw)) $ticket = $raw;
    }

    $prices     = bae_stripe_price_ids();
    $price_key  = $plan . '_' . $billing;
    $price_id   = $prices[$price_key] ?? '';

    if (empty($price_id)) {
        wp_send_json_error(['message' => 'Price not configured for this plan. Contact support.']);
    }

    $success_url = add_query_arg([
        'bae_stripe_success' => '1',
        'plan'               => $plan,
        'ticket'             => urlencode($ticket),
    ], get_permalink());

    $cancel_url = get_permalink();

    $mode = $billing === 'lifetime' ? 'payment' : 'subscription';

    $body = [
        'mode'                => $mode,
        'line_items[0][price]'    => $price_id,
        'line_items[0][quantity]' => '1',
        'success_url'         => $success_url,
        'cancel_url'          => $cancel_url,
        'metadata[ticket]'    => $ticket,
        'metadata[plan]'      => $plan,
    ];

    $response = bae_stripe_request('POST', '/checkout/sessions', $body);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Stripe connection error.']);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['error'])) {
        wp_send_json_error(['message' => $body['error']['message'] ?? 'Stripe error.']);
    }

    if (!empty($body['url'])) {
        wp_send_json_success(['checkout_url' => $body['url']]);
    }

    wp_send_json_error(['message' => 'Could not create checkout session.']);
}

// ─────────────────────────────────────────────────────────────────
// HANDLE: Stripe success redirect
// Runs when user returns from Stripe with ?bae_stripe_success=1
// ─────────────────────────────────────────────────────────────────
add_action('template_redirect', 'bae_handle_stripe_success', 5);
function bae_handle_stripe_success() {
    if (empty($_GET['bae_stripe_success']) || $_GET['bae_stripe_success'] !== '1') return;

    $plan   = sanitize_text_field($_GET['plan']   ?? 'starter');
    $ticket = strtoupper(sanitize_text_field($_GET['ticket'] ?? ''));

    if (!preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $ticket)) return;
    if (!in_array($plan, ['starter', 'pro'])) return;

    global $wpdb;
    $wpdb->update(
        $wpdb->prefix . 'bae_profiles',
        ['plan' => $plan],
        ['ticket' => $ticket],
        ['%s'], ['%s']
    );

    $user_id = is_user_logged_in() ? get_current_user_id() : 0;
    if ($user_id) set_transient('bae_pm_success_' . $user_id, $plan, 60);

    // Clean URL redirect
    $clean = remove_query_arg(['bae_stripe_success', 'plan', 'ticket']);
    wp_redirect($clean);
    exit;
}

// ─────────────────────────────────────────────────────────────────
// WEBHOOK: Stripe sends events here
// Register in: dashboard.stripe.com → Developers → Webhooks
// URL: https://yoursite.com/?bae_webhook=stripe
// Events to listen for: checkout.session.completed, customer.subscription.deleted
// ─────────────────────────────────────────────────────────────────
add_action('init', 'bae_stripe_webhook_listener');
function bae_stripe_webhook_listener() {
    if (!isset($_GET['bae_webhook']) || $_GET['bae_webhook'] !== 'stripe') return;

    $payload = @file_get_contents('php://input');
    $sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (empty($payload)) { http_response_code(400); exit; }

    // Verify webhook signature
    $event = bae_stripe_verify_webhook($payload, $sig);
    if (!$event) { http_response_code(400); exit; }

    $type   = $event['type'] ?? '';
    $object = $event['data']['object'] ?? [];

    switch ($type) {
        case 'checkout.session.completed':
            $ticket = $object['metadata']['ticket'] ?? '';
            $plan   = $object['metadata']['plan']   ?? 'starter';
            if (preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $ticket)) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'bae_profiles',
                    ['plan' => $plan],
                    ['ticket' => $ticket],
                    ['%s'], ['%s']
                );
                // Log payment
                $amount = intval($object['amount_total'] ?? 0);
                $ref    = sanitize_text_field($object['id'] ?? '');
                bae_stripe_log_payment($ticket, $plan, $amount, $ref, 'paid');
            }
            break;

        case 'customer.subscription.deleted':
            // Subscription cancelled — revert to free
            $customer_id = $object['customer'] ?? '';
            if ($customer_id) {
                global $wpdb;
                // Find ticket by stripe customer id stored in meta
                $ticket = $wpdb->get_var($wpdb->prepare(
                    "SELECT ticket FROM {$wpdb->prefix}bae_profiles WHERE stripe_customer_id = %s LIMIT 1",
                    $customer_id
                ));
                if ($ticket) {
                    $wpdb->update(
                        $wpdb->prefix . 'bae_profiles',
                        ['plan' => 'free'],
                        ['ticket' => $ticket],
                        ['%s'], ['%s']
                    );
                }
            }
            break;
    }

    http_response_code(200);
    exit;
}

// ─────────────────────────────────────────────────────────────────
// HELPER: Verify Stripe webhook signature
// ─────────────────────────────────────────────────────────────────
function bae_stripe_verify_webhook($payload, $sig_header) {
    if (BAE_STRIPE_WEBHOOK_SECRET === 'MISSING_WEBHOOK_SECRET' || empty($sig_header)) {
        // Skip verification in dev — just decode
        return json_decode($payload, true);
    }

    $parts     = [];
    $timestamp = null;
    $signatures = [];

    foreach (explode(',', $sig_header) as $part) {
        [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
        if ($k === 't') $timestamp = $v;
        if ($k === 'v1') $signatures[] = $v;
    }

    if (!$timestamp || empty($signatures)) return false;

    // Reject old webhooks (5 minute tolerance)
    if (abs(time() - intval($timestamp)) > 300) return false;

    $signed_payload = $timestamp . '.' . $payload;
    $expected       = hash_hmac('sha256', $signed_payload, BAE_STRIPE_WEBHOOK_SECRET);

    foreach ($signatures as $sig) {
        if (hash_equals($expected, $sig)) {
            return json_decode($payload, true);
        }
    }

    return false;
}

// ─────────────────────────────────────────────────────────────────
// HELPER: Log payment to bae_payments table
// ─────────────────────────────────────────────────────────────────
function bae_stripe_log_payment($ticket, $plan, $amount, $reference, $status = 'paid') {
    global $wpdb;
    $pay_table = $wpdb->prefix . 'bae_payments';
    if (!$wpdb->get_var("SHOW TABLES LIKE '{$pay_table}'")) return;

    $user_id = 0;
    $row     = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s LIMIT 1", $ticket
    ));
    if ($row) $user_id = (int) $row->user_id;

    $wpdb->insert($pay_table, [
        'rand_id'   => bntm_rand_id(),
        'user_id'   => $user_id,
        'ticket'    => $ticket,
        'plan'      => $plan,
        'amount'    => $amount,
        'reference' => $reference,
        'status'    => $status,
        'created_at'=> current_time('mysql'),
    ]);
}

// ─────────────────────────────────────────────────────────────────
// HELPER: Make Stripe API request
// ─────────────────────────────────────────────────────────────────
function bae_stripe_request($method, $endpoint, $body = []) {
    return wp_remote_request(BAE_STRIPE_API . $endpoint, [
        'method'  => $method,
        'headers' => [
            'Authorization' => 'Bearer ' . BAE_STRIPE_SECRET_KEY,
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Stripe-Version'=> '2024-06-20',
        ],
        'body'    => $method === 'POST' ? http_build_query($body) : null,
        'timeout' => 30,
    ]);
}
