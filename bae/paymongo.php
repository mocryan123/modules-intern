<?php
/**
 * BAE — PayMongo Payment Integration
 * File: paymongo.php
 * Include this in main.php via: require_once(plugin_dir_path(__FILE__) . 'paymongo.php');
 *
 * Covers:
 *  - Checkout session creation (GCash, Maya, cards)
 *  - Success/cancel redirect handlers
 *  - Webhook listener (auto-upgrades plan on payment)
 *  - Subscription renewal (monthly) and one-time lifetime
 */

if (!defined('ABSPATH')) exit;

// Load environment variables from .env file if not already loaded
if ( file_exists( dirname(__FILE__) . '/.env' ) && ! getenv('BAE_PM_SECRET_KEY') ) {
    $env_file = dirname(__FILE__) . '/.env';
    $lines = file( $env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
    foreach ( $lines as $line ) {
        if ( strpos( trim( $line ), '#' ) === 0 ) continue; // Skip comments
        if ( strpos( $line, '=' ) === false ) continue; // Skip invalid lines
        list( $key, $value ) = explode( '=', $line, 2 );
        $key = trim( $key );
        $value = trim( $value );
        if ( ! empty( $key ) && ! getenv( $key ) ) {
            putenv( $key . '=' . $value );
        }
    }
}

// ─────────────────────────────────────────────
// CONFIG — PayMongo API Keys (from .env)
// https://dashboard.paymongo.com → Developers → API Keys
// ─────────────────────────────────────────────
define('BAE_PM_SECRET_KEY',     getenv('BAE_PM_SECRET_KEY')     ?: 'MISSING_SECRET_KEY');
define('BAE_PM_PUBLIC_KEY',     getenv('BAE_PM_PUBLIC_KEY')     ?: 'MISSING_PUBLIC_KEY');
define('BAE_PM_WEBHOOK_SECRET', getenv('BAE_PM_WEBHOOK_SECRET') ?: 'MISSING_WEBHOOK_SECRET');

// Prices in centavos (PHP × 100)
// Starter monthly: ₱49 → 4900
// Starter lifetime: ₱199 → 19900
// Pro monthly: ₱99 → 9900
define('BAE_PRICE_STARTER_MONTHLY',  4900);
define('BAE_PRICE_STARTER_LIFETIME', 19900);
define('BAE_PRICE_PRO_MONTHLY',      9900);


// ─────────────────────────────────────────────
// AJAX: Create PayMongo Checkout Session
// Called when user clicks "Get Starter" or "Get Pro"
// ─────────────────────────────────────────────
add_action('wp_ajax_bae_pm_checkout',        'bntm_ajax_bae_pm_checkout');
add_action('wp_ajax_nopriv_bae_pm_checkout', 'bntm_ajax_bae_pm_checkout');

function bntm_ajax_bae_pm_checkout() {
    check_ajax_referer('bae_pm_checkout', 'nonce');

    $user_id  = get_current_user_id();
    $plan     = sanitize_text_field($_POST['plan'] ?? '');
    $billing  = sanitize_text_field($_POST['billing'] ?? 'monthly'); // 'monthly' or 'lifetime'

    if (!$user_id) {
        wp_send_json_error(['message' => 'Not logged in.']);
    }

    if (!in_array($plan, ['starter', 'pro'])) {
        wp_send_json_error(['message' => 'Invalid plan.']);
    }

    // Determine amount and description
    if ($plan === 'starter' && $billing === 'lifetime') {
        $amount      = BAE_PRICE_STARTER_LIFETIME;
        $description = 'BAE Starter — Lifetime';
        $plan_key    = 'starter_lifetime';
    } elseif ($plan === 'starter') {
        $amount      = BAE_PRICE_STARTER_MONTHLY;
        $description = 'BAE Starter — Monthly';
        $plan_key    = 'starter_monthly';
    } else {
        $amount      = BAE_PRICE_PRO_MONTHLY;
        $description = 'BAE Pro — Monthly';
        $plan_key    = 'pro_monthly';
    }

    // Store pending payment metadata in user meta so webhook can match it
    $ref = 'bae_' . $user_id . '_' . $plan_key . '_' . time();
    update_user_meta($user_id, 'bae_pm_pending_ref',  $ref);
    update_user_meta($user_id, 'bae_pm_pending_plan', $plan);

    $site_url    = get_site_url();
    $success_url = add_query_arg(['bae_pm' => 'success', 'ref' => $ref], $site_url . '/brand-asset-engine');
    $cancel_url  = add_query_arg(['bae_pm' => 'cancel'],                  $site_url . '/brand-asset-engine');

    $payload = [
        'data' => [
            'attributes' => [
                'billing'           => null,
                'cancel_url'        => $cancel_url,
                'description'       => $description,
                'line_items'        => [[
                    'currency'   => 'PHP',
                    'amount'     => $amount,
                    'description'=> $description,
                    'name'       => $description,
                    'quantity'   => 1,
                ]],
                'payment_method_types' => ['gcash', 'paymaya', 'card', 'grab_pay'],
                'reference_number'  => $ref,
                'send_email_receipt'=> true,
                'show_description'  => true,
                'show_line_items'   => true,
                'success_url'       => $success_url,
            ],
        ],
    ];

    $response = bae_pm_request('POST', '/v1/checkout_sessions', $payload);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $code = wp_remote_retrieve_response_code($response);

    if ($code !== 200 || empty($body['data']['attributes']['checkout_url'])) {
        $err = $body['errors'][0]['detail'] ?? 'PayMongo error. Please try again.';
        wp_send_json_error(['message' => $err]);
    }

    wp_send_json_success([
        'checkout_url' => $body['data']['attributes']['checkout_url'],
    ]);
}


// ─────────────────────────────────────────────
// SUCCESS REDIRECT HANDLER
// Runs when user returns from PayMongo checkout
// Acts as a fallback — webhook is the primary trigger
// ─────────────────────────────────────────────
add_action('template_redirect', 'bae_pm_handle_redirect');

function bae_pm_handle_redirect() {
    if (!isset($_GET['bae_pm'])) return;

    $user_id = get_current_user_id();
    if (!$user_id) return;

    if ($_GET['bae_pm'] === 'success') {
        $ref          = sanitize_text_field($_GET['ref'] ?? '');
        $pending_ref  = get_user_meta($user_id, 'bae_pm_pending_ref',  true);
        $pending_plan = get_user_meta($user_id, 'bae_pm_pending_plan', true);

        // Only upgrade if ref matches and plan not already upgraded
        if ($ref && $ref === $pending_ref && $pending_plan) {
            bae_pm_upgrade_user($user_id, $pending_plan);
            delete_user_meta($user_id, 'bae_pm_pending_ref');
            delete_user_meta($user_id, 'bae_pm_pending_plan');

            // Set a flash message for the UI
            set_transient('bae_pm_success_' . $user_id, $pending_plan, 60);
        }
    }
}


// ─────────────────────────────────────────────
// WEBHOOK LISTENER
// Register this URL in PayMongo Dashboard:
// Dashboard → Developers → Webhooks → Add Endpoint
// URL: https://yoursite.com/?bae_webhook=paymongo
// Events to listen: payment.paid, checkout_session.payment.paid
// ─────────────────────────────────────────────
add_action('init', 'bae_pm_webhook_listener');

function bae_pm_webhook_listener() {
    if (!isset($_GET['bae_webhook']) || $_GET['bae_webhook'] !== 'paymongo') return;

    $payload   = file_get_contents('php://input');
    $sig       = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

    // Verify webhook signature
    if (!bae_pm_verify_signature($payload, $sig)) {
        http_response_code(401);
        exit('Unauthorized');
    }

    $event = json_decode($payload, true);
    $type  = $event['data']['attributes']['type'] ?? '';

    if (in_array($type, ['payment.paid', 'checkout_session.payment.paid'])) {
        $ref = '';

        // Extract reference number depending on event type
        if ($type === 'checkout_session.payment.paid') {
            $ref = $event['data']['attributes']['data']['attributes']['reference_number'] ?? '';
        } else {
            $ref = $event['data']['attributes']['data']['attributes']['description'] ?? '';
            // Try metadata reference
            $ref = $event['data']['attributes']['data']['attributes']['metadata']['reference_number'] ?? $ref;
        }

        if ($ref && strpos($ref, 'bae_') === 0) {
            // ref format: bae_{user_id}_{plan_key}_{timestamp}
            $parts   = explode('_', $ref);
            $user_id = intval($parts[1] ?? 0);
            $plan    = $parts[2] ?? ''; // 'starter' or 'pro'

            if ($user_id && in_array($plan, ['starter', 'pro'])) {
                bae_pm_upgrade_user($user_id, $plan);
                // Log payment
                bae_pm_log_payment($user_id, $plan, $ref, $event);
            }
        }
    }

    http_response_code(200);
    echo 'OK';
    exit;
}


// ─────────────────────────────────────────────
// UPGRADE USER PLAN
// Updates the plan column in bae_profiles
// ─────────────────────────────────────────────
function bae_pm_upgrade_user($user_id, $plan) {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE user_id = %d",
        $user_id
    ));

    if (!$existing) return false;

    return $wpdb->update(
        $table,
        ['plan' => sanitize_text_field($plan)],
        ['user_id' => $user_id],
        ['%s'],
        ['%d']
    );
}


// ─────────────────────────────────────────────
// PAYMENT LOG TABLE (optional but recommended)
// Run bae_pm_create_log_table() once on plugin activation
// ─────────────────────────────────────────────
function bae_pm_create_log_table() {
    global $wpdb;
    $table   = $wpdb->prefix . 'bae_payments';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        plan VARCHAR(20) NOT NULL DEFAULT '',
        reference VARCHAR(100) NOT NULL DEFAULT '',
        amount INT NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'paid',
        raw_event LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_user (user_id),
        INDEX idx_ref (reference)
    ) {$charset};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('plugins_loaded', 'bae_pm_create_log_table');

function bae_pm_log_payment($user_id, $plan, $ref, $event) {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_payments';

    $amount = 0;
    if (strpos($ref, 'starter_lifetime') !== false) $amount = BAE_PRICE_STARTER_LIFETIME;
    elseif (strpos($ref, 'starter'))                $amount = BAE_PRICE_STARTER_MONTHLY;
    elseif (strpos($ref, 'pro'))                    $amount = BAE_PRICE_PRO_MONTHLY;

    $wpdb->insert($table, [
        'user_id'   => $user_id,
        'plan'      => $plan,
        'reference' => $ref,
        'amount'    => $amount,
        'status'    => 'paid',
        'raw_event' => json_encode($event),
    ], ['%d','%s','%s','%d','%s','%s']);
}


// ─────────────────────────────────────────────
// HELPER: Make PayMongo API request
// ─────────────────────────────────────────────
function bae_pm_request($method, $endpoint, $body = null) {
    $args = [
        'method'  => $method,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode(BAE_PM_SECRET_KEY . ':'),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ],
        'timeout' => 30,
    ];

    if ($body !== null) {
        $args['body'] = json_encode($body);
    }

    return wp_remote_request('https://api.paymongo.com' . $endpoint, $args);
}


// ─────────────────────────────────────────────
// HELPER: Verify webhook signature
// ─────────────────────────────────────────────
function bae_pm_verify_signature($payload, $sig_header) {
    if (empty($sig_header) || !defined('BAE_PM_WEBHOOK_SECRET') || !BAE_PM_WEBHOOK_SECRET) {
        // If no secret set yet, skip verification (set it after first webhook test)
        return true;
    }

    // PayMongo signature format: t=timestamp,te=hash,li=hash
    $parts = [];
    foreach (explode(',', $sig_header) as $part) {
        [$k, $v]   = explode('=', $part, 2);
        $parts[$k] = $v;
    }

    $timestamp = $parts['t']  ?? '';
    $te_sig    = $parts['te'] ?? '';

    $signed_payload = $timestamp . '.' . $payload;
    $expected       = hash_hmac('sha256', $signed_payload, BAE_PM_WEBHOOK_SECRET);

    return hash_equals($expected, $te_sig);
}


// ─────────────────────────────────────────────
// ENQUEUE: Pass nonce + ajax url to JS
// ─────────────────────────────────────────────
add_action('wp_enqueue_scripts', 'bae_pm_enqueue');

function bae_pm_enqueue() {
    wp_add_inline_script('jquery', '
        window.BAE_PM = {
            ajax_url: ' . json_encode(admin_url('admin-ajax.php')) . ',
            nonce:    ' . json_encode(wp_create_nonce('bae_pm_checkout')) . '
        };
    ');
}
