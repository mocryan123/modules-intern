<?php
/*
 * Fundora Didit verification callback shortcode.
 */

if (!defined('ABSPATH')) {
    exit;
}

function fundora_verification_complete_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="kbf-alert kbf-alert-error">Please log in to complete verification.</div>';
    }

    $session_id = isset($_GET['verificationSessionId']) ? sanitize_text_field($_GET['verificationSessionId']) : '';
    $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $user_id = get_current_user_id();
    $stored = get_user_meta($user_id, 'fundora_didit_session_id', true);

    if (!$session_id || !$stored) {
        return '<div class="kbf-alert kbf-alert-error">Session mismatch.</div>';
    }
    if ($session_id !== $stored) {
        return '<div class="kbf-alert kbf-alert-error">Session mismatch.</div>';
    }

    $result = fundora_didit_get_session($session_id);
    if (is_wp_error($result)) {
        return '<div class="kbf-alert kbf-alert-error">' . esc_html($result->get_error_message()) . '</div>';
    }

    $remote_status = isset($result['status']) ? sanitize_text_field($result['status']) : $status;
    $mapped = 'In Review';
    if ($remote_status === 'Approved') {
        $mapped = 'Approved';
    } elseif ($remote_status === 'Declined') {
        $mapped = 'Declined';
    }

    update_user_meta($user_id, 'fundora_didit_verification_status', $mapped);
    if ($mapped === 'Approved') {
        update_user_meta($user_id, 'fundora_didit_verified_at', current_time('mysql'));
    }

    $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
    if ($mapped === 'Approved') {
        return '<div class="kbf-alert kbf-alert-success">Verification approved. <a href="' . esc_url($dashboard_url) . '">Go back to your dashboard.</a></div>';
    }
    if ($mapped === 'Declined') {
        return '<div class="kbf-alert kbf-alert-error">Verification declined. <a href="' . esc_url($dashboard_url) . '">Try again from your dashboard.</a></div>';
    }

    return '<div class="kbf-alert kbf-alert-warning">Verification is under review. Please allow 1-2 business days. <a href="' . esc_url($dashboard_url) . '">Return to dashboard.</a></div>';
}

add_shortcode('fundora_verification_complete', 'fundora_verification_complete_shortcode');
