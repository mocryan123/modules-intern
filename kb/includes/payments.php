<?php
/*
 * KBF payment helpers and Maya checkout/webhook handlers.
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('kbf_maya_webhook_secret')) {
    function kbf_maya_webhook_secret() {
        $env = kbf_get_env_secret('KBF_MAYA_WEBHOOK_SECRET');
        if ($env !== '') return $env;
        // TODO: Migrate stored secrets to environment variables and remove DB storage.
        return (string) kbf_get_setting('kbf_maya_webhook_secret', '');
    }
}

if (!function_exists('kbf_get_env_secret')) {
    function kbf_get_env_secret($key) {
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return trim((string) $env);
        }
        if (defined($key)) {
            $val = constant($key);
            if ($val !== '' && $val !== null) {
                return trim((string) $val);
            }
        }
        return '';
    }
}

if (!function_exists('kbf_get_request_header')) {
    function kbf_get_request_header($name) {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (!empty($_SERVER[$key])) return sanitize_text_field($_SERVER[$key]);
        foreach ($_SERVER as $k => $v) {
            if (strcasecmp($k, $key) === 0) return sanitize_text_field($v);
        }
        return '';
    }
}

if (!function_exists('kbf_verify_maya_signature')) {
    function kbf_verify_maya_signature($raw_body) {
        $secret = kbf_maya_webhook_secret();
        if ($secret === '') {
            return [true, 'no_secret'];
        }
        $sig = kbf_get_request_header('X-Maya-Signature');
        if ($sig === '') $sig = kbf_get_request_header('X-Signature');
        if ($sig === '') {
            return [false, 'missing_signature'];
        }
        $expected = hash_hmac('sha256', $raw_body, $secret);
        $sig_clean = preg_replace('/^sha256=/i', '', trim($sig));
        $ok = hash_equals($expected, $sig_clean);
        return [$ok, $ok ? 'ok' : 'mismatch'];
    }
}

function kbf_maya_secret_key() {
    $demo = (bool)kbf_get_setting('kbf_demo_mode', true);
    if ($demo) {
        $env = kbf_get_env_secret('KBF_MAYA_SANDBOX_SECRET');
        if ($env !== '') return $env;
        // TODO: Migrate stored secrets to environment variables and remove DB storage.
        return kbf_get_setting('kbf_maya_sandbox_secret', '');
    }
    $env = kbf_get_env_secret('KBF_MAYA_LIVE_SECRET');
    if ($env !== '') return $env;
    // TODO: Migrate stored secrets to environment variables and remove DB storage.
    return kbf_get_setting('kbf_maya_live_secret', '');
}

function kbf_maya_public_key() {
    $demo = (bool)kbf_get_setting('kbf_demo_mode', true);
    if ($demo) {
        $env = kbf_get_env_secret('KBF_MAYA_SANDBOX_PUBLIC');
        if ($env !== '') return $env;
        // TODO: Migrate stored keys to environment variables and remove DB storage.
        return kbf_get_setting('kbf_maya_sandbox_public', '');
    }
    $env = kbf_get_env_secret('KBF_MAYA_LIVE_PUBLIC');
    if ($env !== '') return $env;
    // TODO: Migrate stored keys to environment variables and remove DB storage.
    return kbf_get_setting('kbf_maya_live_public', '');
}

/**
 * Mark a sponsorship as completed and update fund + organizer stats.
 */
function kbf_mark_sponsorship_completed($sponsorship_id, $payment_reference = '') {
    global $wpdb;
    $st = $wpdb->prefix . 'kbf_sponsorships';
    $ft = $wpdb->prefix . 'kbf_funds';

    $sponsorship = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$st} WHERE id=%d", $sponsorship_id));
    if (!$sponsorship) return false;
    if ($sponsorship->payment_status === 'completed') return true;

    $wpdb->update($st, [
        'payment_status'    => 'completed',
        'payment_reference' => $payment_reference ?: $sponsorship->payment_reference,
        'notified'          => 1,
    ], ['id' => $sponsorship->id], ['%s','%s','%d'], ['%d']);

    // Update fund raised_amount
    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d", $sponsorship->fund_id));
    if ($fund) {
        $wpdb->query($wpdb->prepare(
            "UPDATE {$ft} SET raised_amount=raised_amount+%f WHERE id=%d",
            $sponsorship->amount, $fund->id
        ));
        // Auto-complete if goal reached
        $updated = $wpdb->get_row($wpdb->prepare("SELECT raised_amount,goal_amount FROM {$ft} WHERE id=%d", $fund->id));
        if ($updated && $updated->goal_amount > 0 && $updated->raised_amount >= $updated->goal_amount) {
            $wpdb->update($ft, ['status' => 'completed', 'escrow_status' => 'released'],
                ['id' => $fund->id], ['%s','%s'], ['%d']);
            do_action('kbf_fund_goal_reached', $fund->id);
        }
        // Update organizer stats (exclude anonymous donations)
        $pt = $wpdb->prefix . 'kbf_organizer_profiles';
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(s.amount),0) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed' AND s.is_anonymous=0",
            $fund->business_id
        ));
        $cnt = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed' AND s.is_anonymous=0",
            $fund->business_id
        ));
        $wpdb->update($pt, ['total_raised' => $total, 'total_sponsors' => $cnt],
            ['business_id' => $fund->business_id], ['%f','%d'], ['%d']);
        if (function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            $fund_url = add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$fund->id], $dashboard_url);
            kbf_push_user_notification((int)$fund->business_id, [
                'type' => 'donation_received',
                'title' => 'New sponsorship received',
                'message' => 'You received a new sponsorship worth PHP ' . number_format((float)$sponsorship->amount, 2) . '.',
                'url' => $fund_url,
                'target_id' => (string)((int)$sponsorship->id),
            ]);
            $updated_goal = $wpdb->get_row($wpdb->prepare("SELECT raised_amount,goal_amount,status FROM {$ft} WHERE id=%d", $fund->id));
            if ($updated_goal && $updated_goal->goal_amount > 0 && $updated_goal->raised_amount >= $updated_goal->goal_amount && $updated_goal->status === 'completed') {
                kbf_push_user_notification((int)$fund->business_id, [
                    'type' => 'goal_reached_fund_completed',
                    'title' => 'Goal reached',
                    'message' => 'Your campaign reached its goal and is now completed.',
                    'url' => $fund_url,
                    'target_id' => (string)((int)$fund->id),
                ]);
            }
            $sponsor_user = get_user_by('email', (string)$sponsorship->email);
            if ($sponsor_user && !empty($sponsor_user->ID)) {
                kbf_push_user_notification((int)$sponsor_user->ID, [
                    'type' => 'payment_confirmed',
                    'title' => 'Payment confirmed',
                    'message' => 'Your sponsorship payment was confirmed successfully.',
                    'url' => $fund_url,
                    'target_id' => (string)((int)$sponsorship->id),
                ]);
            }
        }
    }

    do_action('kbf_payment_confirmed', $sponsorship->id);
    return true;
}

/**
 * Maya API base URL -- sandbox vs production.
 */
function kbf_maya_base_url() {
    $demo = (bool)kbf_get_setting('kbf_demo_mode', true);
    return $demo
        ? 'https://pg-sandbox.paymaya.com'
        : 'https://pg.paymaya.com';
}

/**
 * Make an authenticated request to the Maya API.
 * Maya uses Basic Auth with the public key for checkout creation.
 */
function kbf_maya_request($endpoint, $payload = null, $method = 'POST', $use_secret = false) {
    $key = $use_secret ? kbf_maya_secret_key() : kbf_maya_public_key();
    if (empty($key)) return ['error' => 'Maya API key not configured.'];

    $args = [
        'method'  => $method,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($key . ':'),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ],
        'timeout' => 20,
    ];
    if ($payload !== null) $args['body'] = json_encode($payload);

    $url = kbf_maya_base_url() . $endpoint;
    $response = wp_remote_request($url, $args);
    if (is_wp_error($response)) return ['error' => $response->get_error_message()];

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 400) {
        $msg = $body['message'] ?? ($body['error']['message'] ?? 'Maya API error (HTTP ' . $code . ').');
        error_log('[KBF][Maya] Error ' . $code . ' @ ' . $url . ' :: ' . $msg . ' :: ' . wp_remote_retrieve_body($response));
        return ['error' => $msg, 'code' => $code, 'raw' => $body];
    }
    error_log('[KBF][Maya] OK ' . $code . ' @ ' . $url . ' :: ' . wp_remote_retrieve_body($response));
    return $body;
}

/**
 * AJAX: Create a Maya Checkout session and return the checkout URL.
 * Called when a sponsor submits the form in live mode.
 *
 * Maya Checkout API reference:
 * POST /checkout/v1/checkouts
 * Auth: Basic <base64(publicKey:)>
 */

function bntm_ajax_kbf_create_checkout() {
    check_ajax_referer('kbf_sponsor', 'nonce');
    if (function_exists('kbf_rate_limit_ok') && !kbf_rate_limit_ok('checkout_create', 30, 60)) {
        wp_send_json_error(['message' => 'Too many requests. Please try again shortly.']);
    }
    global $wpdb;
    $ft = $wpdb->prefix . 'kbf_funds';
    $st = $wpdb->prefix . 'kbf_sponsorships';

    $fund_id      = intval($_POST['fund_id']);
    $amount       = floatval($_POST['amount']);
    $sponsor_name = sanitize_text_field($_POST['sponsor_name'] ?? 'Anonymous');
    $email        = sanitize_email($_POST['email'] ?? '');
    $phone        = sanitize_text_field($_POST['phone'] ?? '');
    $message      = sanitize_textarea_field($_POST['message'] ?? '');
    $is_anon      = intval($_POST['is_anonymous'] ?? 0);
    $method       = sanitize_text_field($_POST['payment_method'] ?? 'online_payment');

    if ($amount < 50) wp_send_json_error(['message' => 'Minimum sponsorship is &#8369;50.']);
    if (empty($email) || empty($phone)) {
        wp_send_json_error(['message' => 'Email and phone are required to proceed.']);
    }
    if ($method !== 'online_payment') {
        wp_send_json_error(['message' => 'Please select Online Payment to proceed to Maya Checkout.']);
    }

    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND status='active'", $fund_id));
    if (!$fund) wp_send_json_error(['message' => 'Fund not found or not accepting sponsorships.']);

    // Save sponsorship
    $rand_id = bntm_rand_id();
    $wpdb->insert($st, [
        'rand_id'        => $rand_id,
        'fund_id'        => $fund_id,
        'sponsor_name'   => $is_anon ? 'Anonymous' : $sponsor_name,
        'is_anonymous'   => $is_anon,
        'amount'         => $amount,
        'email'          => $email,
        'phone'          => $phone,
        'payment_method' => $method,
        'payment_status' => 'pending',
        'message'        => $message,
    ], ['%s','%d','%s','%d','%f','%s','%s','%s','%s','%s']);
    $sponsorship_id = $wpdb->insert_id;

    // Payment is confirmed via webhook; do not update totals here.

    // Redirect URLs -- Maya sends buyer back after payment
    // Use a stable absolute URL (home_url) to avoid invalid redirectUrl errors in AJAX context.
    $base_return = kbf_get_page_url('dashboard');
    $fund_token  = function_exists('kbf_get_or_create_fund_token') ? kbf_get_or_create_fund_token($fund_id) : '';
    $success_url = add_query_arg(['kbf_payment' => 'success', 'kbf_tab' => 'find_funds', 'sid' => $sponsorship_id, 'ref' => $rand_id, 'fund_id' => $fund_id, 'fund' => $fund_token], $base_return);
    $failure_url = add_query_arg(['kbf_payment' => 'failed',  'kbf_tab' => 'find_funds', 'sid' => $sponsorship_id, 'fund_id' => $fund_id, 'fund' => $fund_token], $base_return);
    $cancel_url  = add_query_arg(['kbf_payment' => 'cancelled','kbf_tab' => 'find_funds', 'sid' => $sponsorship_id, 'fund_id' => $fund_id, 'fund' => $fund_token], $base_return);

    // Maya amounts are in PHP (not centavos), as decimal strings
    $amount_str = number_format($amount, 2, '.', '');

    // Build Maya Checkout payload
    $payload = [
        'totalAmount' => [
            'value'    => $amount_str,
            'currency' => 'PHP',
            'details'  => [
                'subtotal' => $amount_str,
            ],
        ],
        'buyer' => [
            'firstName' => $is_anon ? 'Anonymous' : (explode(' ', $sponsor_name)[0] ?? 'Sponsor'),
            'lastName'  => $is_anon ? 'Sponsor'   : (explode(' ', $sponsor_name, 2)[1] ?? 'Donor'),
            'contact'   => array_filter([
                'email' => $email ?: null,
                'phone' => $phone ?: null,
            ]),
        ],
        'items' => [[
            'name'        => 'KonekBayan Fund Support',
            'description' => 'Sponsorship for: ' . $fund->title,
            'quantity'    => '1',
            'code'        => 'KBF-' . $rand_id,
            'amount'      => ['value' => $amount_str, 'currency' => 'PHP'],
            'totalAmount' => ['value' => $amount_str, 'currency' => 'PHP'],
        ]],
        'redirectUrl' => [
            'success' => $success_url,
            'failure' => $failure_url,
            'cancel'  => $cancel_url,
        ],
        'requestReferenceNumber' => 'KBF-' . $rand_id,
        'metadata'               => [
            'sponsorshipId' => (string)$sponsorship_id,
            'fundId'        => (string)$fund_id,
            'randId'        => $rand_id,
        ],
    ];

    $result = kbf_maya_request('/checkout/v1/checkouts', $payload);

    if (isset($result['error'])) {
        if (is_user_logged_in() && function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            kbf_push_user_notification((int)get_current_user_id(), [
                'type' => 'payment_failed',
                'title' => 'Payment failed to start',
                'message' => 'We could not start your Maya checkout session. Please try again.',
                'url' => add_query_arg(['kbf_tab' => 'find_funds'], $dashboard_url),
                'target_id' => (string)$fund_id,
            ]);
        }
        $wpdb->delete($st, ['id' => $sponsorship_id], ['%d']);
        error_log('[KBF][Maya] Checkout create failed: ' . $result['error']);
        wp_send_json_error(['message' => 'Payment gateway error. Please try again later.']);
    }

    $checkout_url = $result['redirectUrl'] ?? '';
    $checkout_id  = $result['checkoutId'] ?? '';

    if (empty($checkout_url)) {
        if (is_user_logged_in() && function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            kbf_push_user_notification((int)get_current_user_id(), [
                'type' => 'payment_failed',
                'title' => 'Payment failed to start',
                'message' => 'Unable to start payment session. Please try again.',
                'url' => add_query_arg(['kbf_tab' => 'find_funds'], $dashboard_url),
                'target_id' => (string)$fund_id,
            ]);
        }
        $wpdb->delete($st, ['id' => $sponsorship_id], ['%d']);
        wp_send_json_error(['message' => 'Unable to start payment session. Please try again.']);
    }

    // Store Maya checkout ID for webhook matching / status polling
    $wpdb->update($st,
        ['gateway_payload' => json_encode(['checkoutId' => $checkout_id, 'checkout_url' => $checkout_url])],
        ['id' => $sponsorship_id], ['%s'], ['%d']
    );

    wp_send_json_success([
        'checkout_url'   => $checkout_url,
        'sponsorship_id' => $sponsorship_id,
        'message'        => 'Redirecting to Maya secure payment...',
    ]);
}

/**
 * Maya Webhook Handler -- receives CHECKOUT_SUCCESS / PAYMENT_SUCCESS events.
 * WordPress REST endpoint: /wp-json/kbf/v1/maya-webhook
 *
 * Maya sends a POST to this URL when payment is confirmed.
 * Marks sponsorship completed, updates fund raised_amount,
 * auto-completes fund if goal reached, updates organizer stats.
 */
function kbf_maya_webhook_handler(WP_REST_Request $request) {
    global $wpdb;

    if ((bool)kbf_get_setting('kbf_demo_mode', true)) {
        return new WP_REST_Response([
            'received' => true,
            'note'     => 'Demo mode enabled. Webhook processing is disabled.'
        ], 200);
    }

    if (strtoupper($request->get_method()) === 'GET') {
        return new WP_REST_Response([
            'ok'      => true,
            'message' => 'Fundora Maya webhook endpoint. Send POST requests from Maya here.'
        ], 200);
    }

    $raw_body = $request->get_body();
    $payload  = json_decode($raw_body, true);

    list($sig_ok, $sig_state) = kbf_verify_maya_signature($raw_body);
    if (!$sig_ok) {
        if (function_exists('kbf_log_security_event')) {
            kbf_log_security_event('webhook_sig_fail', ['state' => $sig_state], 'maya_webhook');
        }
        error_log('[KBF][Maya] Webhook rejected: ' . $sig_state);
        return new WP_REST_Response(['error' => 'Unauthorized'], 401);
    }

    if (!$payload) {
        return new WP_REST_Response(['error' => 'Invalid payload'], 400);
    }

    // Maya webhook events we care about
    $event = $payload['eventType'] ?? ($payload['name'] ?? '');
    $success_events = ['CHECKOUT_SUCCESS', 'PAYMENT_SUCCESS', 'AUTHORIZED'];

    if (!in_array($event, $success_events)) {
        return new WP_REST_Response(['received' => true, 'note' => 'Event ignored: ' . $event], 200);
    }

    // Extract our reference number and metadata
    $ref_number = $payload['requestReferenceNumber']
        ?? ($payload['resource']['requestReferenceNumber'] ?? '');
    $metadata   = $payload['metadata']
        ?? ($payload['resource']['metadata'] ?? []);

    $sponsorship_id = intval($metadata['sponsorshipId'] ?? 0);
    $rand_id        = sanitize_text_field($metadata['randId'] ?? '');

    // Fallback: parse rand_id from reference number (format: KBF-{rand_id})
    if (!$sponsorship_id && !$rand_id && str_starts_with((string)$ref_number, 'KBF-')) {
        $rand_id = substr($ref_number, 4);
    }

    if (!$sponsorship_id && !$rand_id) {
        return new WP_REST_Response(['received' => true, 'note' => 'No sponsorship reference found'], 200);
    }

    $st = $wpdb->prefix . 'kbf_sponsorships';
    $ft = $wpdb->prefix . 'kbf_funds';

    $sponsorship = $sponsorship_id
        ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$st} WHERE id=%d", $sponsorship_id))
        : $wpdb->get_row($wpdb->prepare("SELECT * FROM {$st} WHERE rand_id=%s", $rand_id));

    if (!$sponsorship || $sponsorship->payment_status === 'completed') {
        return new WP_REST_Response(['received' => true, 'note' => 'Already processed or not found'], 200);
    }

    // Verify amount + checkoutId match our stored sponsorship record.
    $payload_amount = 0.0;
    if (isset($payload['resource']['totalAmount']['value'])) {
        $payload_amount = floatval($payload['resource']['totalAmount']['value']);
    } elseif (isset($payload['totalAmount']['value'])) {
        $payload_amount = floatval($payload['totalAmount']['value']);
    } elseif (isset($payload['resource']['amount'])) {
        $payload_amount = floatval($payload['resource']['amount']);
    }

    $gw = [];
    if (!empty($sponsorship->gateway_payload)) {
        $gw = json_decode($sponsorship->gateway_payload, true);
        if (!is_array($gw)) $gw = [];
    }
    $stored_checkout_id = $gw['checkoutId'] ?? '';
    $payload_checkout_id = $payload['resource']['checkoutId']
        ?? ($payload['checkoutId'] ?? ($payload['resource']['id'] ?? ''));

    $amount_ok = ($payload_amount > 0)
        ? (abs(floatval($sponsorship->amount) - $payload_amount) <= 0.01)
        : false;
    $checkout_ok = ($stored_checkout_id !== '' && $payload_checkout_id !== '')
        ? hash_equals((string)$stored_checkout_id, (string)$payload_checkout_id)
        : false;

    if (!$amount_ok || !$checkout_ok) {
        if (function_exists('kbf_log_security_event')) {
            kbf_log_security_event('webhook_mismatch', [
                'amount_ok' => $amount_ok,
                'checkout_ok' => $checkout_ok,
                'expected_amount' => (float)$sponsorship->amount,
                'payload_amount' => $payload_amount,
                'expected_checkout' => (string)$stored_checkout_id,
                'payload_checkout' => (string)$payload_checkout_id
            ], 'maya_webhook');
        }
        return new WP_REST_Response(['error' => 'Verification failed'], 400);
    }

    // Extract Maya payment/transaction reference
    $maya_ref = $payload['resource']['receiptNumber']
        ?? ($payload['receiptNumber'] ?? ($payload['resource']['id'] ?? $ref_number));

    // Mark sponsorship completed
    $wpdb->update($st, [
        'payment_status'    => 'completed',
        'payment_reference' => $maya_ref,
        'notified'          => 1,
    ], ['id' => $sponsorship->id], ['%s','%s','%d'], ['%d']);

    // Update fund raised_amount
    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d", $sponsorship->fund_id));
    if ($fund) {
        $wpdb->query($wpdb->prepare(
            "UPDATE {$ft} SET raised_amount=raised_amount+%f WHERE id=%d",
            $sponsorship->amount, $fund->id
        ));
        // Auto-complete if goal reached
        $updated = $wpdb->get_row($wpdb->prepare("SELECT raised_amount,goal_amount FROM {$ft} WHERE id=%d", $fund->id));
        if ($updated && $updated->goal_amount > 0 && $updated->raised_amount >= $updated->goal_amount) {
            $wpdb->update($ft, ['status' => 'completed', 'escrow_status' => 'released'],
                ['id' => $fund->id], ['%s','%s'], ['%d']);
            do_action('kbf_fund_goal_reached', $fund->id);
        }
        // Update organizer stats (exclude anonymous donations)
        $pt = $wpdb->prefix . 'kbf_organizer_profiles';
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(s.amount),0) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed' AND s.is_anonymous=0",
            $fund->business_id
        ));
        $cnt = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed' AND s.is_anonymous=0",
            $fund->business_id
        ));
        $wpdb->update($pt, ['total_raised' => $total, 'total_sponsors' => $cnt],
            ['business_id' => $fund->business_id], ['%f','%d'], ['%d']);
        if (function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            $fund_url = add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$fund->id], $dashboard_url);
            kbf_push_user_notification((int)$fund->business_id, [
                'type' => 'donation_received',
                'title' => 'New sponsorship received',
                'message' => 'You received a new sponsorship worth PHP ' . number_format((float)$sponsorship->amount, 2) . '.',
                'url' => $fund_url,
                'target_id' => (string)((int)$sponsorship->id),
            ]);
            $updated_goal = $wpdb->get_row($wpdb->prepare("SELECT raised_amount,goal_amount,status FROM {$ft} WHERE id=%d", $fund->id));
            if ($updated_goal && $updated_goal->goal_amount > 0 && $updated_goal->raised_amount >= $updated_goal->goal_amount && $updated_goal->status === 'completed') {
                kbf_push_user_notification((int)$fund->business_id, [
                    'type' => 'goal_reached_fund_completed',
                    'title' => 'Goal reached',
                    'message' => 'Your campaign reached its goal and is now completed.',
                    'url' => $fund_url,
                    'target_id' => (string)((int)$fund->id),
                ]);
            }
            $sponsor_user = get_user_by('email', (string)$sponsorship->email);
            if ($sponsor_user && !empty($sponsor_user->ID)) {
                kbf_push_user_notification((int)$sponsor_user->ID, [
                    'type' => 'payment_confirmed',
                    'title' => 'Payment confirmed',
                    'message' => 'Your sponsorship payment was confirmed successfully.',
                    'url' => $fund_url,
                    'target_id' => (string)((int)$sponsorship->id),
                ]);
            }
        }
    }

    do_action('kbf_payment_confirmed', $sponsorship->id);
    return new WP_REST_Response(['received' => true, 'processed' => $sponsorship->id], 200);
}

