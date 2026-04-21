<?php
/*
 * Fundora Didit verification helpers: session create + status polling.
 */

if (!defined('ABSPATH')) {
    exit;
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

if (!function_exists('kbf_didit_extract_error_message')) {
    function kbf_didit_extract_error_message($data, $code) {
        if (is_array($data)) {
            if (!empty($data['message']) && is_string($data['message'])) {
                return sanitize_text_field($data['message']);
            }
            if (!empty($data['error']) && is_string($data['error'])) {
                return sanitize_text_field($data['error']);
            }
            if (!empty($data['detail']) && is_string($data['detail'])) {
                return sanitize_text_field($data['detail']);
            }
            if (!empty($data['errors']) && is_array($data['errors'])) {
                $first = reset($data['errors']);
                if (is_string($first) && $first !== '') {
                    return sanitize_text_field($first);
                }
                if (is_array($first) && !empty($first['message']) && is_string($first['message'])) {
                    return sanitize_text_field($first['message']);
                }
            }
        }
        return 'HTTP ' . (int) $code;
    }
}

if (!function_exists('kbf_didit_normalize_session_url')) {
    function kbf_didit_normalize_session_url($data) {
        if (!is_array($data)) {
            return '';
        }
        $candidates = [
            $data['url'] ?? '',
            $data['session_url'] ?? '',
            $data['verification_url'] ?? '',
            $data['redirect_url'] ?? '',
            isset($data['data']) && is_array($data['data']) ? ($data['data']['url'] ?? '') : '',
            isset($data['data']) && is_array($data['data']) ? ($data['data']['session_url'] ?? '') : '',
            isset($data['data']) && is_array($data['data']) ? ($data['data']['verification_url'] ?? '') : '',
            isset($data['data']) && is_array($data['data']) ? ($data['data']['redirect_url'] ?? '') : '',
        ];
        foreach ($candidates as $candidate) {
            $candidate = esc_url_raw((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }
        return '';
    }
}

/**
 * Resolve Didit configuration based on demo/live mode.
 *
 * @return array|null
 */
function kbf_didit_resolve_config() {
    $demo_mode = (bool) kbf_get_setting('kbf_demo_mode', true);

    if ($demo_mode) {
        $api_key = (string) kbf_get_setting('kbf_didit_sandbox_api_key', '');
        $app_id = (string) kbf_get_setting('kbf_didit_sandbox_app_id', '');
        $workflow_id = (string) kbf_get_setting('kbf_didit_sandbox_workflow_id', '');
        if ($api_key === '') $api_key = kbf_get_env_secret('KBF_DIDIT_SANDBOX_API_KEY');
        if ($app_id === '') $app_id = kbf_get_env_secret('KBF_DIDIT_SANDBOX_APP_ID');
        if ($workflow_id === '') $workflow_id = kbf_get_env_secret('KBF_DIDIT_SANDBOX_WORKFLOW_ID');
    } else {
        $api_key = (string) kbf_get_setting('kbf_didit_live_api_key', '');
        $app_id = (string) kbf_get_setting('kbf_didit_live_app_id', '');
        $workflow_id = (string) kbf_get_setting('kbf_didit_live_workflow_id', '');
        if ($api_key === '') $api_key = kbf_get_env_secret('KBF_DIDIT_LIVE_API_KEY');
        if ($app_id === '') $app_id = kbf_get_env_secret('KBF_DIDIT_LIVE_APP_ID');
        if ($workflow_id === '') $workflow_id = kbf_get_env_secret('KBF_DIDIT_LIVE_WORKFLOW_ID');
    }

    $api_key = trim($api_key);
    $app_id = trim($app_id);
    $workflow_id = trim($workflow_id);

    if ($api_key === '' || $app_id === '' || $workflow_id === '') {
        return null;
    }

    $callback = add_query_arg(
        ['kbf_tab' => 'profile', 'didit' => 'done'],
        kbf_get_page_url('dashboard')
    );

    return [
        'api_key' => $api_key,
        'app_id' => $app_id,
        'workflow_id' => $workflow_id,
        'base_url' => 'https://verification.didit.me',
        'callback' => $callback,
    ];
}

/**
 * Create a Didit verification session for the given user.
 *
 * @param int $user_id
 * @return array|WP_Error
 */
function fundora_didit_create_session($user_id) {
    $config = kbf_didit_resolve_config();
    if (!$config) {
        return new WP_Error('didit_session_error', 'Didit is not fully configured for the current mode. Set API key, App ID, and Workflow ID.');
    }

    $base = rtrim($config['base_url'], '/');
    $endpoint = $base . '/v3/session/';
    $payload = [
        'workflow_id' => $config['workflow_id'],
        'vendor_data' => (string) $user_id,
        'callback' => $config['callback'],
    ];

    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key' => $config['api_key'],
            'x-app-id' => $config['app_id'],
        ],
        'body' => wp_json_encode($payload),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('didit_session_error', $response->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    $callback = isset($payload['callback']) ? (string) $payload['callback'] : '';
    $callback_is_https = (stripos($callback, 'https://') === 0);
    if ((int) $code === 400 && $callback !== '' && !$callback_is_https) {
        $retry_payload = $payload;
        unset($retry_payload['callback']);
        $retry_response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $config['api_key'],
                'x-app-id' => $config['app_id'],
            ],
            'body' => wp_json_encode($retry_payload),
            'timeout' => 20,
        ]);
        if (!is_wp_error($retry_response)) {
            $code = wp_remote_retrieve_response_code($retry_response);
            $body = wp_remote_retrieve_body($retry_response);
            $data = json_decode($body, true);
        }
    }

    if ((int) $code < 200 || (int) $code >= 300 || !is_array($data)) {
        $message = kbf_didit_extract_error_message($data, $code);
        return new WP_Error('didit_session_error', $message);
    }

    $normalized_url = kbf_didit_normalize_session_url($data);
    if ($normalized_url !== '') {
        $data['url'] = $normalized_url;
    }

    if (!empty($data['session_id'])) {
        update_user_meta($user_id, 'fundora_didit_session_id', sanitize_text_field($data['session_id']));
    }

    return $data;
}

/**
 * Poll a Didit verification session status.
 *
 * @param string $session_id
 * @return array|WP_Error
 */
function fundora_didit_get_session($session_id) {
    $config = kbf_didit_resolve_config();
    if (!$config) {
        return new WP_Error('didit_poll_error', 'Didit is not configured.');
    }

    $base = rtrim($config['base_url'], '/');
    $endpoint = $base . '/v3/session/' . rawurlencode($session_id) . '/';

    $response = wp_remote_get($endpoint, [
        'headers' => [
            'x-api-key' => $config['api_key'],
            'x-app-id' => $config['app_id'],
        ],
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('didit_poll_error', $response->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ((int) $code < 200 || (int) $code >= 300 || !is_array($data)) {
        $message = is_array($data) && !empty($data['message']) ? $data['message'] : ('HTTP ' . $code);
        return new WP_Error('didit_poll_error', $message);
    }

    return $data;
}
