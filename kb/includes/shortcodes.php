<?php
if (!defined('ABSPATH')) exit;

function bntm_kbf_get_shortcodes() {
    return [
        'kbf_landing'            => 'bntm_shortcode_kbf_landing',
        'kbf_dashboard'          => 'bntm_shortcode_kbf_dashboard',
        'kbf_browse'             => 'bntm_shortcode_kbf_browse',
        'kbf_fund_details'       => 'bntm_shortcode_kbf_fund_details',
        'kbf_organizer_profile'  => 'bntm_shortcode_kbf_organizer_profile',
        'kbf_sponsor_history'    => 'bntm_shortcode_kbf_sponsor_history',
        'kbf_terms'              => 'bntm_shortcode_kbf_terms',
        'kbf_admin'              => 'bntm_shortcode_kbf_admin',
        'kbf_signin'             => 'bntm_shortcode_kbf_signin',
        'kbf_signup'             => 'bntm_shortcode_kbf_signup',
    ];
}

if (file_exists(BNTM_KBF_PATH . 'landing.php')) {
    require_once(BNTM_KBF_PATH . 'landing.php');
}
function bntm_shortcode_kbf_landing() {
    return function_exists('bntm_kbf_render_landing') ? bntm_kbf_render_landing() : '';
}

function bntm_shortcode_kbf_terms() {
    return '<div class="kbf-wrap"></div>';
}

function bntm_shortcode_kbf_signin() {
    if (file_exists(BNTM_KBF_PATH . 'pages/signin.php')) {
        require_once BNTM_KBF_PATH . 'pages/signin.php';
    }
    return function_exists('bntm_kbf_render_signin') ? bntm_kbf_render_signin() : '';
}

function bntm_shortcode_kbf_signup() {
    if (file_exists(BNTM_KBF_PATH . 'pages/signup.php')) {
        require_once BNTM_KBF_PATH . 'pages/signup.php';
    }
    return function_exists('bntm_kbf_render_signup') ? bntm_kbf_render_signup() : '';
}

