<?php
/*
 * Fundora Didit verification helpers: session create + status polling.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolve Didit configuration based on demo/live mode.
 *
 * @return array|null
 */
function kbf_didit_resolve_config() {
    $demo_mode = (bool) kbf_get_setting('kbf_demo_mode', true);

    if ($demo_mode) {
        $api_key = kbf_get_setting('kbf_didit_sandbox_api_key', '');
        $workflow_id = kbf_get_setting('kbf_didit_sandbox_workflow_id', '');
    } else {
        $api_key = kbf_get_setting('kbf_didit_live_api_key', '');
        $workflow_id = kbf_get_setting('kbf_didit_live_workflow_id', '');
    }

    if (empty($api_key) || empty($workflow_id)) {
        return null;
    }

    $callback = add_query_arg(
        ['kbf_tab' => 'profile', 'didit' => 'done'],
        kbf_get_page_url('dashboard')
    );

    return [
        'api_key' => $api_key,
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
        return new WP_Error('didit_session_error', 'Didit is not configured. Please add your API key and Workflow ID in Platform Settings.');
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

    if ((int) $code !== 201 || !is_array($data)) {
        $message = is_array($data) && !empty($data['message']) ? $data['message'] : ('HTTP ' . $code);
        return new WP_Error('didit_session_error', $message);
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
