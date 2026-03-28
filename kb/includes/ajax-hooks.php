<?php
if (!defined('ABSPATH')) exit;

// ============================================================
// AJAX ACTION HOOKS
// ============================================================

add_action('wp_ajax_kbf_create_fund',                'bntm_ajax_kbf_create_fund');
add_action('wp_ajax_kbf_update_fund',                'bntm_ajax_kbf_update_fund');
add_action('wp_ajax_kbf_cancel_fund',                'bntm_ajax_kbf_cancel_fund');
add_action('wp_ajax_kbf_request_withdrawal',         'bntm_ajax_kbf_request_withdrawal');
add_action('wp_ajax_kbf_extend_deadline',            'bntm_ajax_kbf_extend_deadline');
add_action('wp_ajax_kbf_toggle_auto_return',         'bntm_ajax_kbf_toggle_auto_return');
add_action('wp_ajax_kbf_save_organizer_profile',     'bntm_ajax_kbf_save_organizer_profile');
add_action('wp_ajax_kbf_request_verification',      'bntm_ajax_kbf_request_verification');
add_action('wp_ajax_kbf_mark_fund_complete',         'bntm_ajax_kbf_mark_fund_complete');

add_action('wp_ajax_kbf_sponsor_fund',               'bntm_ajax_kbf_sponsor_fund');
add_action('wp_ajax_nopriv_kbf_sponsor_fund',        'bntm_ajax_kbf_sponsor_fund');
add_action('wp_ajax_kbf_report_fund',                'bntm_ajax_kbf_report_fund');
add_action('wp_ajax_nopriv_kbf_report_fund',         'bntm_ajax_kbf_report_fund');
add_action('wp_ajax_kbf_submit_appeal',             'bntm_ajax_kbf_submit_appeal');
add_action('wp_ajax_kbf_get_fund_details',           'bntm_ajax_kbf_get_fund_details');
add_action('wp_ajax_nopriv_kbf_get_fund_details',    'bntm_ajax_kbf_get_fund_details');
add_action('wp_ajax_kbf_get_organizer_profile',      'bntm_ajax_kbf_get_organizer_profile');
add_action('wp_ajax_nopriv_kbf_get_organizer_profile','bntm_ajax_kbf_get_organizer_profile');
add_action('wp_ajax_kbf_submit_rating',              'bntm_ajax_kbf_submit_rating');
add_action('wp_ajax_nopriv_kbf_submit_rating',       'bntm_ajax_kbf_submit_rating');
add_action('wp_ajax_kbf_toggle_save_fund',           'bntm_ajax_kbf_toggle_save_fund');

add_action('wp_ajax_kbf_admin_approve_fund',         'bntm_ajax_kbf_admin_approve_fund');
add_action('wp_ajax_kbf_admin_reject_fund',          'bntm_ajax_kbf_admin_reject_fund');
add_action('wp_ajax_kbf_admin_suspend_fund',         'bntm_ajax_kbf_admin_suspend_fund');
add_action('wp_ajax_kbf_admin_verify_badge',         'bntm_ajax_kbf_admin_verify_badge');
add_action('wp_ajax_kbf_admin_release_escrow',       'bntm_ajax_kbf_admin_release_escrow');
add_action('wp_ajax_kbf_admin_hold_escrow',          'bntm_ajax_kbf_admin_hold_escrow');
add_action('wp_ajax_kbf_admin_process_withdrawal',   'bntm_ajax_kbf_admin_process_withdrawal');
add_action('wp_ajax_kbf_admin_dismiss_report',       'bntm_ajax_kbf_admin_dismiss_report');
add_action('wp_ajax_kbf_admin_review_appeal',        'bntm_ajax_kbf_admin_review_appeal');
add_action('wp_ajax_kbf_admin_confirm_payment',      'bntm_ajax_kbf_admin_confirm_payment');
add_action('wp_ajax_kbf_admin_verify_organizer',     'bntm_ajax_kbf_admin_verify_organizer');
add_action('wp_ajax_kbf_save_setting',               'bntm_ajax_kbf_save_setting');
add_action('wp_ajax_kbf_create_checkout',            'bntm_ajax_kbf_create_checkout');
add_action('wp_ajax_nopriv_kbf_create_checkout',     'bntm_ajax_kbf_create_checkout');
add_action('rest_api_init', function() {
    register_rest_route('kbf/v1', '/maya-webhook', [
        'methods'             => 'POST',
        'callback'            => 'kbf_maya_webhook_handler',
        'permission_callback' => '__return_true',
    ]);
});

