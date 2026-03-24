<?php
/*
 * KBF payment helpers and Maya checkout/webhook handlers.
 */

if (!defined('ABSPATH')) exit;

function kbf_maya_secret_key() {
    $demo = (bool)kbf_get_setting('kbf_demo_mode', true);
    return $demo
        ? kbf_get_setting('kbf_maya_sandbox_secret', '')
        : kbf_get_setting('kbf_maya_live_secret', '');
}

function kbf_maya_public_key() {
    $demo = (bool)kbf_get_setting('kbf_demo_mode', true);
    return $demo
        ? kbf_get_setting('kbf_maya_sandbox_public', '')
        : kbf_get_setting('kbf_maya_live_public', '');
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

    if ($amount < 1) wp_send_json_error(['message' => 'Minimum sponsorship is ₱1.']);
    if ($method !== 'online_payment') {
        wp_send_json_error(['message' => 'Please select Online Payment to proceed to Maya Checkout.']);
    }

    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND status='active'", $fund_id));
    if (!$fund) wp_send_json_error(['message' => 'Fund not found or not accepting sponsorships.']);
    if ($fund->goal_amount > 0) {
        $remaining = max(0, floatval($fund->goal_amount) - floatval($fund->raised_amount));
        if ($remaining <= 0) {
            wp_send_json_error(['message' => 'This fund has already reached its goal.']);
        }
        if ($amount > $remaining) {
            wp_send_json_error(['message' => 'Maximum allowed sponsorship is ₱' . number_format($remaining, 2) . ' for this fund.']);
        }
    }

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
        'payment_status' => 'completed',
        'message'        => $message,
    ], ['%s','%d','%s','%d','%f','%s','%s','%s','%s','%s']);
    $sponsorship_id = $wpdb->insert_id;

    // Auto-confirm immediately after online payment initiation (requested behavior)
    if ($sponsorship_id) {
        $wpdb->query($wpdb->prepare("UPDATE {$ft} SET raised_amount=raised_amount+%f WHERE id=%d", $amount, $fund_id));
        $updated = $wpdb->get_row($wpdb->prepare("SELECT raised_amount,goal_amount FROM {$ft} WHERE id=%d", $fund_id));
        if ($updated && $updated->goal_amount > 0 && $updated->raised_amount >= $updated->goal_amount) {
            $wpdb->update($ft, ['status'=>'completed','escrow_status'=>'holding'], ['id'=>$fund_id], ['%s','%s'], ['%d']);
        }
        $pt = $wpdb->prefix . 'kbf_organizer_profiles';
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(s.amount),0) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",
            $fund->business_id
        ));
        $cnt = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",
            $fund->business_id
        ));
        $wpdb->update($pt, ['total_raised'=>$total,'total_sponsors'=>$cnt], ['business_id'=>$fund->business_id], ['%f','%d'], ['%d']);
    }

    // Redirect URLs -- Maya sends buyer back after payment
    // Use a stable absolute URL (home_url) to avoid invalid redirectUrl errors in AJAX context.
    $base_return = kbf_get_page_url('dashboard');
    $success_url = add_query_arg(['kbf_payment' => 'success', 'kbf_tab' => 'find_funds', 'sid' => $sponsorship_id, 'ref' => $rand_id, 'fund_id' => $fund_id], $base_return);
    $failure_url = add_query_arg(['kbf_payment' => 'failed',  'kbf_tab' => 'find_funds', 'sid' => $sponsorship_id, 'fund_id' => $fund_id], $base_return);
    $cancel_url  = add_query_arg(['kbf_payment' => 'cancelled','kbf_tab' => 'find_funds', 'sid' => $sponsorship_id, 'fund_id' => $fund_id], $base_return);

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
        $wpdb->delete($st, ['id' => $sponsorship_id], ['%d']);
        wp_send_json_error(['message' => 'Payment gateway error: ' . $result['error']]);
    }

    $checkout_url = $result['redirectUrl'] ?? '';
    $checkout_id  = $result['checkoutId'] ?? '';

    if (empty($checkout_url)) {
        $wpdb->delete($st, ['id' => $sponsorship_id], ['%d']);
        wp_send_json_error(['message' => 'Could not create payment session. Please try again.']);
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

    $raw_body = $request->get_body();
    $payload  = json_decode($raw_body, true);

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
            $wpdb->update($ft, ['status' => 'completed', 'escrow_status' => 'holding'],
                ['id' => $fund->id], ['%s','%s'], ['%d']);
            do_action('kbf_fund_goal_reached', $fund->id);
        }
        // Update organizer stats
        $pt = $wpdb->prefix . 'kbf_organizer_profiles';
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(s.amount),0) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",
            $fund->business_id
        ));
        $cnt = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",
            $fund->business_id
        ));
        $wpdb->update($pt, ['total_raised' => $total, 'total_sponsors' => $cnt],
            ['business_id' => $fund->business_id], ['%f','%d'], ['%d']);
    }

    do_action('kbf_payment_confirmed', $sponsorship->id);
    return new WP_REST_Response(['received' => true, 'processed' => $sponsorship->id], 200);
}

