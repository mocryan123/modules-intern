<?php
/*
 * KBF Didit verification helpers and webhook handler.
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('kbf_didit_env')) {
    function kbf_didit_env() {
        return (bool) kbf_get_setting('kbf_demo_mode', true) ? 'sandbox' : 'live';
    }
}

if (!function_exists('kbf_didit_config')) {
    function kbf_didit_config() {
        $env = kbf_didit_env();
        $legacy_key = kbf_get_setting('kbf_didit_api_key', '');
        $legacy_secret = kbf_get_setting('kbf_didit_api_secret', '');
        $key = $env === 'sandbox'
            ? kbf_get_setting('kbf_didit_sandbox_api_key', '')
            : kbf_get_setting('kbf_didit_live_api_key', '');
        $secret = $env === 'sandbox'
            ? kbf_get_setting('kbf_didit_sandbox_api_secret', '')
            : kbf_get_setting('kbf_didit_live_api_secret', '');
        $workflow = $env === 'sandbox'
            ? kbf_get_setting('kbf_didit_sandbox_workflow_id', '')
            : kbf_get_setting('kbf_didit_live_workflow_id', '');
        if ($env === 'live' && !$key && $legacy_key) {
            $key = $legacy_key;
        }
        if ($env === 'live' && !$secret && $legacy_secret) {
            $secret = $legacy_secret;
        }
        $webhook_secret = kbf_get_setting('kbf_didit_webhook_secret', '');
        return [
            'env' => $env,
            'api_key' => (string) $key,
            'api_secret' => (string) $secret,
            'workflow_id' => (string) $workflow,
            'webhook_secret' => (string) $webhook_secret,
        ];
    }
}

if (!function_exists('kbf_didit_is_enabled')) {
    function kbf_didit_is_enabled() {
        $cfg = kbf_didit_config();
        return !empty($cfg['api_key']) && !empty($cfg['workflow_id']);
    }
}

if (!function_exists('kbf_didit_sort_keys_recursive')) {
    function kbf_didit_sort_keys_recursive($data) {
        if (!is_array($data)) return $data;
        foreach ($data as $key => $value) {
            $data[$key] = kbf_didit_sort_keys_recursive($value);
        }
        ksort($data);
        return $data;
    }
}

if (!function_exists('kbf_didit_verify_signature_v2')) {
    function kbf_didit_verify_signature_v2($json_body, $signature, $timestamp, $secret) {
        if (!$signature || !$timestamp || !$secret) return false;
        $now = time();
        if (abs($now - (int)$timestamp) > 300) return false;
        $sorted = kbf_didit_sort_keys_recursive($json_body);
        $encoded = json_encode($sorted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expected = hash_hmac('sha256', $encoded, $secret);
        return hash_equals($expected, $signature);
    }
}

if (!function_exists('kbf_didit_verify_signature_simple')) {
    function kbf_didit_verify_signature_simple($json_body, $signature, $timestamp, $secret) {
        if (!$signature || !$timestamp || !$secret) return false;
        $now = time();
        if (abs($now - (int)$timestamp) > 300) return false;
        $canonical = implode(':', [
            (string)($json_body['timestamp'] ?? ''),
            (string)($json_body['session_id'] ?? ''),
            (string)($json_body['status'] ?? ''),
            (string)($json_body['webhook_type'] ?? ''),
        ]);
        $expected = hash_hmac('sha256', $canonical, $secret);
        return hash_equals($expected, $signature);
    }
}

if (!function_exists('kbf_didit_verify_signature_raw')) {
    function kbf_didit_verify_signature_raw($raw_body, $signature, $timestamp, $secret) {
        if (!$signature || !$timestamp || !$secret) return false;
        $now = time();
        if (abs($now - (int)$timestamp) > 300) return false;
        $expected = hash_hmac('sha256', $raw_body, $secret);
        return hash_equals($expected, $signature);
    }
}

if (!function_exists('kbf_didit_webhook_handler')) {
    function kbf_didit_webhook_handler(WP_REST_Request $request) {
        $cfg = kbf_didit_config();
        $body = (string) $request->get_body();
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            return new WP_REST_Response(['ok' => false], 400);
        }
        $sig_v2 = $request->get_header('x-signature-v2');
        $sig_simple = $request->get_header('x-signature-simple');
        $sig_raw = $request->get_header('x-signature');
        $ts = $request->get_header('x-timestamp');
        $verified = false;
        if ($sig_v2 && kbf_didit_verify_signature_v2($payload, $sig_v2, $ts, $cfg['webhook_secret'])) {
            $verified = true;
        } elseif ($sig_simple && kbf_didit_verify_signature_simple($payload, $sig_simple, $ts, $cfg['webhook_secret'])) {
            $verified = true;
        } elseif ($sig_raw && kbf_didit_verify_signature_raw($body, $sig_raw, $ts, $cfg['webhook_secret'])) {
            $verified = true;
        }
        if (!$verified) {
            if (function_exists('kbf_log_security_event')) {
                kbf_log_security_event('webhook_sig_fail', ['state' => 'didit'], 'didit_webhook');
            }
            return new WP_REST_Response(['ok' => false], 401);
        }

        $session_id = $payload['session_id'] ?? '';
        $status = $payload['status'] ?? '';
        $vendor_data = $payload['vendor_data'] ?? '';

        $user_id = 0;
        if ($vendor_data && is_numeric($vendor_data)) {
            $user_id = (int) $vendor_data;
        }
        if (!$user_id && $session_id) {
            $users = get_users([
                'meta_key' => 'kbf_didit_session_id',
                'meta_value' => $session_id,
                'number' => 1,
                'fields' => 'ID',
            ]);
            if (!empty($users)) {
                $user_id = (int) $users[0];
            }
        }
        if (!$user_id) {
            return new WP_REST_Response(['ok' => true], 200);
        }

        $mapped = [
            'Approved' => 'approved',
            'Declined' => 'rejected',
            'Rejected' => 'rejected',
            'In Review' => 'pending',
            'Not Started' => 'pending',
            'In Progress' => 'pending',
            'Abandoned' => 'rejected',
            'Expired' => 'rejected',
        ];
        $normalized = isset($mapped[$status]) ? $mapped[$status] : 'pending';

        update_user_meta($user_id, 'kbf_didit_status', $normalized);
        update_user_meta($user_id, 'kbf_didit_raw', wp_json_encode($payload));
        if ($normalized === 'approved') {
            update_user_meta($user_id, 'kbf_didit_verified_at', current_time('mysql'));
        }

        global $wpdb;
        $pt = $wpdb->prefix . 'kbf_organizer_profiles';
        $data = [
            'verify_status' => $normalized,
            'is_verified' => $normalized === 'approved' ? 1 : 0,
        ];
        if ($normalized === 'rejected') {
            $data['verify_notes'] = isset($payload['decision']['status']) ? sanitize_text_field($payload['decision']['status']) : 'Rejected';
        }
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d", $user_id));
        if ($exists) {
            $wpdb->update($pt, $data, ['business_id' => $user_id], ['%s','%d'], ['%d']);
        }

        return new WP_REST_Response(['ok' => true], 200);
    }
}
