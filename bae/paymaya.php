<?php
/**
 * BAE - PayMaya Payment Integration
 * File: paymaya.php
 *
 * Handles:
 *  - Maya Checkout transaction creation
 *  - Success/cancel redirect handlers
 *  - Basic webhook listener
 *  - Payment logging
 */

if (!defined('ABSPATH')) exit;

if (file_exists(dirname(__FILE__) . '/.env') && !getenv('BAE_PM_SECRET_KEY')) {
    $env_file = dirname(__FILE__) . '/.env';
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key !== '' && !getenv($key)) {
            putenv($key . '=' . $value);
        }
    }
}

define('BAE_PM_SECRET_KEY', getenv('BAE_PM_SECRET_KEY') ?: 'MISSING_SECRET_KEY');
define('BAE_PM_PUBLIC_KEY', getenv('BAE_PM_PUBLIC_KEY') ?: 'MISSING_PUBLIC_KEY');
define('BAE_PM_WEBHOOK_SECRET', getenv('BAE_PM_WEBHOOK_SECRET') ?: 'MISSING_WEBHOOK_SECRET');
define('BAE_PM_BASE_URL', rtrim(getenv('BAE_PM_BASE_URL') ?: 'https://pg-sandbox.paymaya.com', '/'));

define('BAE_PRICE_STARTER_MONTHLY', 4900);
define('BAE_PRICE_STARTER_LIFETIME', 19900);
define('BAE_PRICE_PRO_MONTHLY', 9900);

add_action('wp_ajax_bae_pm_checkout', 'bntm_ajax_bae_pm_checkout');
add_action('wp_ajax_nopriv_bae_pm_checkout', 'bntm_ajax_bae_pm_checkout');

function bntm_ajax_bae_pm_checkout() {
    check_ajax_referer('bae_pm_checkout', 'nonce');

    $user_id = get_current_user_id();
    $plan = sanitize_text_field($_POST['plan'] ?? '');
    $billing = sanitize_text_field($_POST['billing'] ?? 'monthly');

    if (!$user_id) {
        wp_send_json_error(['message' => 'Not logged in.']);
    }

    if (!in_array($plan, ['starter', 'pro'], true)) {
        wp_send_json_error(['message' => 'Invalid plan.']);
    }

    if (BAE_PM_PUBLIC_KEY === 'MISSING_PUBLIC_KEY') {
        wp_send_json_error(['message' => 'PayMaya public key is missing.']);
    }

    if ($plan === 'starter' && $billing === 'lifetime') {
        $amount_centavos = BAE_PRICE_STARTER_LIFETIME;
        $amount_php = 199;
        $description = 'BAE Starter - Lifetime';
        $plan_key = 'starter_lifetime';
    } elseif ($plan === 'starter') {
        $amount_centavos = BAE_PRICE_STARTER_MONTHLY;
        $amount_php = 49;
        $description = 'BAE Starter - Monthly';
        $plan_key = 'starter_monthly';
    } else {
        $amount_centavos = BAE_PRICE_PRO_MONTHLY;
        $amount_php = 99;
        $description = 'BAE Pro - Monthly';
        $plan_key = 'pro_monthly';
    }

    $ref = 'bae_' . $user_id . '_' . $plan_key . '_' . time();
    update_user_meta($user_id, 'bae_pm_pending_ref', $ref);
    update_user_meta($user_id, 'bae_pm_pending_plan', $plan);

    $site_url = get_site_url();
    $success_url = add_query_arg(['bae_pm' => 'success', 'ref' => $ref], $site_url . '/brand-asset-engine');
    $failure_url = add_query_arg(['bae_pm' => 'failure', 'ref' => $ref], $site_url . '/brand-asset-engine');
    $cancel_url = add_query_arg(['bae_pm' => 'cancel', 'ref' => $ref], $site_url . '/brand-asset-engine');

    // Maya Checkout uses the public key and a flat payload shape.
    $payload = [
        'totalAmount' => [
            'value' => $amount_php,
            'currency' => 'PHP',
        ],
        'requestReferenceNumber' => $ref,
        'redirectUrl' => [
            'success' => $success_url,
            'failure' => $failure_url,
            'cancel' => $cancel_url,
        ],
        'items' => [[
            'name' => $description,
            'quantity' => 1,
            'code' => strtoupper($plan_key),
            'description' => $description,
            'amount' => [
                'value' => $amount_php,
            ],
            'totalAmount' => [
                'value' => $amount_php,
            ],
        ]],
        'metadata' => [
            'user_id' => (string) $user_id,
            'plan' => $plan,
            'billing' => $billing,
            'reference_number' => $ref,
        ],
    ];

    $response = bae_pm_request('POST', '/checkout/v1/checkouts', $payload, 'public');

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $code = wp_remote_retrieve_response_code($response);
    $checkout_url = $body['redirectUrl'] ?? '';

    if ($code < 200 || $code >= 300 || $checkout_url === '') {
        $err = $body['message']
            ?? $body['error']
            ?? ($body['errors'][0]['detail'] ?? '')
            ?? 'PayMaya error. Please try again.';

        if ($err === '') {
            $err = 'PayMaya error. Please try again.';
        }

        if (!empty($body['code'])) {
            $err .= ' (' . $body['code'] . ')';
        }

        if (!empty($body['reference'])) {
            $err .= ' Ref: ' . $body['reference'];
        }

        error_log('BAE PayMaya checkout error: HTTP ' . $code . ' ' . wp_json_encode($body));
        wp_send_json_error(['message' => $err]);
    }

    update_user_meta($user_id, 'bae_pm_pending_amount', $amount_centavos);

    wp_send_json_success([
        'checkout_url' => $checkout_url,
    ]);
}

add_action('template_redirect', 'bae_pm_handle_redirect');

function bae_pm_handle_redirect() {
    if (!isset($_GET['bae_pm'])) return;

    $user_id = get_current_user_id();
    if (!$user_id) return;

    if ($_GET['bae_pm'] === 'success') {
        $ref = sanitize_text_field($_GET['ref'] ?? '');
        $pending_ref = get_user_meta($user_id, 'bae_pm_pending_ref', true);
        $pending_plan = get_user_meta($user_id, 'bae_pm_pending_plan', true);

        if ($ref && $ref === $pending_ref && $pending_plan) {
            bae_pm_upgrade_user($user_id, $pending_plan);
            delete_user_meta($user_id, 'bae_pm_pending_ref');
            delete_user_meta($user_id, 'bae_pm_pending_plan');
            set_transient('bae_pm_success_' . $user_id, $pending_plan, 60);
        }
    }
}

add_action('init', 'bae_pm_webhook_listener');

function bae_pm_webhook_listener() {
    if (!isset($_GET['bae_webhook']) || $_GET['bae_webhook'] !== 'paymaya') return;

    $payload = file_get_contents('php://input');
    $sig = $_SERVER['HTTP_PAYMAYA_SIGNATURE'] ?? '';

    if (!bae_pm_verify_signature($payload, $sig)) {
        http_response_code(401);
        exit('Unauthorized');
    }

    $event = json_decode($payload, true);
    $ref = $event['requestReferenceNumber']
        ?? $event['metadata']['reference_number']
        ?? '';

    if ($ref && strpos($ref, 'bae_') === 0) {
        $parts = explode('_', $ref);
        $user_id = intval($parts[1] ?? 0);
        $plan = $parts[2] ?? '';

        if ($user_id && in_array($plan, ['starter', 'pro'], true)) {
            bae_pm_upgrade_user($user_id, $plan);
            bae_pm_log_payment($user_id, $plan, $ref, $event);
        }
    }

    http_response_code(200);
    echo 'OK';
    exit;
}

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

function bae_pm_create_log_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_payments';
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
    if (strpos($ref, 'starter_lifetime') !== false) {
        $amount = BAE_PRICE_STARTER_LIFETIME;
    } elseif (strpos($ref, 'starter') !== false) {
        $amount = BAE_PRICE_STARTER_MONTHLY;
    } elseif (strpos($ref, 'pro') !== false) {
        $amount = BAE_PRICE_PRO_MONTHLY;
    }

    $wpdb->insert($table, [
        'user_id' => $user_id,
        'plan' => $plan,
        'reference' => $ref,
        'amount' => $amount,
        'status' => 'paid',
        'raw_event' => wp_json_encode($event),
    ], ['%d', '%s', '%s', '%d', '%s', '%s']);
}

function bae_pm_request($method, $endpoint, $body = null, $key_type = 'secret') {
    $api_key = $key_type === 'public' ? BAE_PM_PUBLIC_KEY : BAE_PM_SECRET_KEY;

    $args = [
        'method' => $method,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'timeout' => 30,
    ];

    if ($body !== null) {
        $args['body'] = wp_json_encode($body);
    }

    return wp_remote_request(BAE_PM_BASE_URL . $endpoint, $args);
}

function bae_pm_verify_signature($payload, $sig_header) {
    if (empty($sig_header) || !defined('BAE_PM_WEBHOOK_SECRET') || !BAE_PM_WEBHOOK_SECRET || BAE_PM_WEBHOOK_SECRET === 'MISSING_WEBHOOK_SECRET') {
        return true;
    }

    $parts = [];
    foreach (explode(',', $sig_header) as $part) {
        [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
        $parts[$k] = $v;
    }

    $timestamp = $parts['t'] ?? '';
    $te_sig = $parts['te'] ?? '';

    if ($timestamp === '' || $te_sig === '') {
        return false;
    }

    $signed_payload = $timestamp . '.' . $payload;
    $expected = hash_hmac('sha256', $signed_payload, BAE_PM_WEBHOOK_SECRET);

    return hash_equals($expected, $te_sig);
}

add_action('wp_enqueue_scripts', 'bae_pm_enqueue');

function bae_pm_enqueue() {
    wp_add_inline_script('jquery', '
        window.BAE_PM = {
            ajax_url: ' . json_encode(admin_url('admin-ajax.php')) . ',
            nonce: ' . json_encode(wp_create_nonce('bae_pm_checkout')) . '
        };
    ');
}
