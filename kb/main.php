<?php
/**
 * Module Name: Fundora Platform
 * Module Slug: kbf
 * Description: Community crowdfunding and sponsorship platform for funders and sponsors.
 * Version: 2.0.0
 * Author: Barth Brayan D. Serceña
 * Icon: assets/iconlogo.png
 */
/*
 * KBF module bootstrap: defines constants, shared helpers, DB/cron setup,
 * global assets, and loads user/admin components.
 */

if (!defined('ABSPATH')) exit;

define('BNTM_KBF_PATH', dirname(__FILE__) . '/');
define('BNTM_KBF_URL', plugin_dir_url(__FILE__));
if (!defined('KB_PATH')) {
    define('KB_PATH', BNTM_KBF_PATH);
}

// Clear PHP opcache to ensure updated code is served after git pulls
if (extension_loaded('Zend OPcache')) {
    opcache_reset();
}

// Minimal logging to help diagnose white screen issues.
if (!function_exists('kbf_log')) {
    function kbf_log($message, $context = []) {
        $prefix = '[KBF] ';
        if (!empty($context)) {
            $message .= ' | ' . wp_json_encode($context);
        }
        error_log($prefix . $message);
    }
}

register_shutdown_function(function () {
    $error = error_get_last();
    if (!$error) {
        return;
    }
    $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (in_array($error['type'], $fatal_types, true)) {
        kbf_log('Fatal error', $error);
    }
});

kbf_log('KBF bootstrap start', ['file' => __FILE__]);

require_once(BNTM_KBF_PATH . 'user.php');
require_once(BNTM_KBF_PATH . 'admin.php');
require_once(BNTM_KBF_PATH . 'includes/pages.php');
require_once(BNTM_KBF_PATH . 'includes/shortcodes.php');
require_once(BNTM_KBF_PATH . 'includes/db.php');
require_once(BNTM_KBF_PATH . 'includes/ajax-hooks.php');
require_once KB_PATH . 'includes/didit.php';
require_once(BNTM_KBF_PATH . 'includes/cron.php');
require_once(BNTM_KBF_PATH . 'includes/loading.php');
require_once(BNTM_KBF_PATH . 'includes/assets.php');

// Remove WP admin-bar top offset on KBF pages to avoid white strip.
if (!function_exists('kbf_is_kbf_page')) {
    function kbf_is_kbf_page() {
        if (!is_singular()) {
            return false;
        }
        $post = get_post();
        if (!$post || empty($post->post_content) || !function_exists('bntm_kbf_get_shortcodes')) {
            return false;
        }
        foreach (array_keys(bntm_kbf_get_shortcodes()) as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }
}

add_action('wp', function () {
    if (!kbf_is_kbf_page()) {
        return;
    }
    remove_action('wp_head', '_admin_bar_bump_cb');
    add_filter('show_admin_bar', '__return_false');
}, 0);

// KBF pages: block legacy plugin frontend CSS from rendering.
if (!function_exists('kbf_block_legacy_frontend_css')) {
    function kbf_block_legacy_frontend_css() {
        if (is_admin() || !function_exists('kbf_is_kbf_page') || !kbf_is_kbf_page()) {
            return;
        }

        // Remove any enqueued style handles that point to legacy frontend CSS.
        global $wp_styles;
        if ($wp_styles && !empty($wp_styles->registered)) {
            foreach ($wp_styles->registered as $handle => $style_obj) {
                $src = isset($style_obj->src) ? (string) $style_obj->src : '';
                if ($src === '') {
                    continue;
                }
                if (strpos($src, '/assets/css/bntm-frontend.css') !== false || preg_match('#/assets/css/frontend\.css(?:\?|$)#i', $src)) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'kbf_block_legacy_frontend_css', 9999);
add_action('wp_print_styles', 'kbf_block_legacy_frontend_css', 9999);

if (!function_exists('kbf_filter_legacy_frontend_style_tag')) {
    function kbf_filter_legacy_frontend_style_tag($html, $handle, $href, $media) {
        if (is_admin() || !function_exists('kbf_is_kbf_page') || !kbf_is_kbf_page()) {
            return $html;
        }
        $href = (string) $href;
        if (strpos($href, '/assets/css/bntm-frontend.css') !== false || preg_match('#/assets/css/frontend\.css(?:\?|$)#i', $href)) {
            return '';
        }
        return $html;
    }
}
add_filter('style_loader_tag', 'kbf_filter_legacy_frontend_style_tag', 9999, 4);

if (!function_exists('kbf_strip_hardcoded_legacy_frontend_css')) {
    function kbf_strip_hardcoded_legacy_frontend_css() {
        if (is_admin() || !function_exists('kbf_is_kbf_page') || !kbf_is_kbf_page()) {
            return;
        }
        ob_start(function ($html) {
            $pattern = '#<link\b[^>]*href=["\'][^"\']*(?:/assets/css/bntm-frontend\.css|/assets/css/frontend\.css)(?:\?[^"\']*)?["\'][^>]*>\s*#i';
            return preg_replace($pattern, '', $html);
        });
    }
}
add_action('template_redirect', 'kbf_strip_hardcoded_legacy_frontend_css', 0);

// Prevent browser/back-forward cache from showing stale signed-in KBF pages after logout.
if (!function_exists('kbf_no_cache_private_pages')) {
    function kbf_no_cache_private_pages() {
        if (is_admin() || !function_exists('kbf_is_kbf_page') || !kbf_is_kbf_page()) {
            return;
        }
        if (!is_user_logged_in()) {
            return;
        }
        if (!headers_sent()) {
            nocache_headers();
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
            header('Pragma: no-cache');
            header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
        }
    }
}
add_action('template_redirect', 'kbf_no_cache_private_pages', 1);

// Disable legacy plugin preloader on KBF pages (use KBF branding preloader instead).
add_filter('bntm_disable_loading_overlay', function($disabled){
    if (function_exists('kbf_is_kbf_page') && kbf_is_kbf_page()) {
        return true;
    }
    return $disabled;
});


// Mark new accounts to land on profile after first login.
function kbf_mark_first_login($user_id) {
    if (!$user_id) return;
    update_user_meta($user_id, 'kbf_first_login', 1);
    update_user_meta($user_id, 'kbf_show_onboarding', 1);
    if (function_exists('kbf_push_user_notification')) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        $profile_tab_url = add_query_arg('kbf_tab', 'profile', $dashboard_url);
        kbf_push_user_notification((int)$user_id, [
            'type' => 'welcome_first_login',
            'title' => 'Welcome to Fundora',
            'message' => 'Your account is ready. Complete your profile to start creating campaigns.',
            'url' => $profile_tab_url,
            'target_id' => (string)((int)$user_id),
            'dedupe_window' => 1209600,
        ]);
    }
}

add_action('wp_mail_failed', function($error) {
    if (!function_exists('kbf_log') || !is_wp_error($error)) {
        return;
    }
    kbf_log('wp_mail_failed', [
        'message' => $error->get_error_message(),
        'code' => $error->get_error_code(),
        'data' => $error->get_error_data(),
    ]);
});
add_action('user_register', 'kbf_mark_first_login', 10, 1);

/**
 * Keep onboarding visibility flag in sync with current profile completeness.
 * This prevents stale kbf_show_onboarding meta from forcing the modal after completion.
 */
if (!function_exists('kbf_sync_onboarding_flag_for_user')) {
    function kbf_sync_onboarding_flag_for_user($user_id = 0) {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }
        if (user_can($user_id, 'manage_options')) {
            return false;
        }

        global $wpdb;
        $pt = $wpdb->prefix . 'kbf_organizer_profiles';
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT profile_type,payout_type,payout_name,payout_number FROM {$pt} WHERE business_id=%d",
            $user_id
        ));
        $user = get_userdata($user_id);
        $social_name = (string) get_user_meta($user_id, 'kbf_social_name', true);
        $address = (string) get_user_meta($user_id, 'kbf_address', true);

        $has_display_name = $user && !empty(trim((string) $user->display_name));
        $has_social_name = !empty(trim($social_name));
        $has_profile_type = $profile && !empty(trim((string) $profile->profile_type));
        $has_payout = $profile && !empty($profile->payout_type) && !empty($profile->payout_name) && !empty($profile->payout_number);
        $has_address = !empty(trim($address));

        $is_complete = ($has_display_name && $has_social_name && $has_profile_type && $has_payout && $has_address);
        if (!$is_complete) {
            return false;
        }

        $flag = get_user_meta($user_id, 'kbf_show_onboarding', true);
        if ($flag === '' || $flag === null) {
            return false;
        }

        delete_user_meta($user_id, 'kbf_show_onboarding');
        clean_user_cache($user_id);
        wp_cache_delete($user_id, 'user_meta');
        wp_cache_delete($user_id, 'users');
        return true;
    }
}

add_action('template_redirect', function() {
    if (is_admin() || !is_user_logged_in()) {
        return;
    }
    kbf_sync_onboarding_flag_for_user(get_current_user_id());
}, 2);

// ============================================================
// AUTH HARDENING
// ============================================================

if (!defined('KBF_AUTH_RATE_LIMIT')) {
    define('KBF_AUTH_RATE_LIMIT', 5);
}
if (!defined('KBF_AUTH_RATE_WINDOW')) {
    define('KBF_AUTH_RATE_WINDOW', 15 * MINUTE_IN_SECONDS);
}
if (!defined('KBF_EMAIL_VERIFY_TTL')) {
    define('KBF_EMAIL_VERIFY_TTL', DAY_IN_SECONDS);
}
if (!defined('KBF_EMAIL_VERIFY_DISABLED')) {
    // Temporarily disable email verification checks and emails.
    define('KBF_EMAIL_VERIFY_DISABLED', false);
}
if (!defined('KBF_AUTH_SIGNUP_LIMIT')) {
    define('KBF_AUTH_SIGNUP_LIMIT', 5);
}
if (!defined('KBF_AUTH_SIGNUP_WINDOW')) {
    define('KBF_AUTH_SIGNUP_WINDOW', HOUR_IN_SECONDS);
}

// TODO: Add rate limiting for AI generation endpoints when implemented.

function kbf_auth_get_ip() {
    $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? preg_replace('/[^0-9a-fA-F:\.]/', '', (string) $_SERVER['REMOTE_ADDR']) : '';

    // Cloudflare always sends the real visitor IP in this header.
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $cf_ip = preg_replace('/[^0-9a-fA-F:\.]/', '', (string) $_SERVER['HTTP_CF_CONNECTING_IP']);
        if (filter_var($cf_ip, FILTER_VALIDATE_IP)) {
            return $cf_ip;
        }
    }

    if ($remote_addr === '') {
        return '0.0.0.0';
    }

    // Only trust forwarded headers if behind a known proxy.
    $trusted_proxies = ['127.0.0.1', '::1'];
    if (!in_array($remote_addr, $trusted_proxies, true)) {
        return $remote_addr;
    }

    foreach (['HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP'] as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $value = sanitize_text_field($_SERVER[$key]);
        $ip = trim(explode(',', $value)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return $remote_addr;
}

function kbf_auth_rate_limit_key($login, $ip) {
    $login = strtolower((string) $login);
    return 'kbf_auth_fail_' . md5($login . '|' . $ip);
}

function kbf_auth_get_rate_state($login, $ip) {
    $key = kbf_auth_rate_limit_key($login, $ip);
    $state = get_transient($key);
    return is_array($state) ? $state : null;
}

function kbf_auth_is_rate_limited($login, $ip, &$retry_after = 0) {
    $state = kbf_auth_get_rate_state($login, $ip);
    if (!$state || empty($state['count']) || empty($state['expires'])) {
        return false;
    }
    if ($state['count'] < KBF_AUTH_RATE_LIMIT) {
        return false;
    }
    $retry_after = max(0, (int) $state['expires'] - time());
    return $retry_after > 0;
}

function kbf_auth_register_failed_login($login, $ip) {
    if (!$login) return;
    $key = kbf_auth_rate_limit_key($login, $ip);
    $state = kbf_auth_get_rate_state($login, $ip);
    if (!$state) {
        $state = [
            'count' => 1,
            'expires' => time() + KBF_AUTH_RATE_WINDOW,
        ];
    } else {
        $state['count'] = (int) $state['count'] + 1;
        if (empty($state['expires']) || $state['expires'] < time()) {
            $state['expires'] = time() + KBF_AUTH_RATE_WINDOW;
        }
    }
    set_transient($key, $state, KBF_AUTH_RATE_WINDOW);
}

function kbf_auth_clear_failed_login($login, $ip) {
    if (!$login) return;
    delete_transient(kbf_auth_rate_limit_key($login, $ip));
}

function kbf_auth_signup_rate_limit_key($ip) {
    return 'kbf_signup_' . md5((string) $ip);
}

function kbf_auth_signup_cooldown($strikes) {
    $cooldowns = [0 => 3 * MINUTE_IN_SECONDS, 1 => 15 * MINUTE_IN_SECONDS, 2 => 30 * MINUTE_IN_SECONDS];
    return $cooldowns[$strikes] ?? 60 * MINUTE_IN_SECONDS;
}

function kbf_auth_is_signup_rate_limited($ip, &$retry_after = 0) {
    $key = kbf_auth_signup_rate_limit_key($ip);
    $state = get_transient($key);
    if (!is_array($state) || empty($state['count']) || empty($state['expires'])) {
        return false;
    }
    if ($state['count'] < KBF_AUTH_SIGNUP_LIMIT) {
        return false;
    }
    $retry_after = max(0, (int) $state['expires'] - time());
    return $retry_after > 0;
}

function kbf_auth_register_signup_attempt($ip) {
    if (!$ip) return;
    $key = kbf_auth_signup_rate_limit_key($ip);
    $state = get_transient($key);
    $now = time();
    if (!$state || empty($state['expires']) || $state['expires'] < $now) {
        // New block or previous window expired → increment strikes.
        $strikes = isset($state['strikes']) ? (int) $state['strikes'] + 1 : 0;
        $state = [
            'count'   => 1,
            'strikes' => $strikes,
            'expires' => $now + kbf_auth_signup_cooldown($strikes),
        ];
    } else {
        $state['count'] = (int) $state['count'] + 1;
    }
    set_transient($key, $state, KBF_AUTH_SIGNUP_WINDOW);
}

add_filter('authenticate', function($user, $username, $password) {
    if (is_wp_error($user)) {
        return $user;
    }
    $ip = kbf_auth_get_ip();
    $retry_after = 0;
    if ($username && kbf_auth_is_rate_limited($username, $ip, $retry_after)) {
        $mins = max(1, (int) ceil($retry_after / 60));
        return new WP_Error('kbf_rate_limited', 'Too many login attempts. Try again in ' . $mins . ' minute(s).');
    }
    if ($user instanceof WP_User && !KBF_EMAIL_VERIFY_DISABLED) {
        if (!user_can($user, 'manage_options')) {
            $verified = get_user_meta($user->ID, 'kbf_email_verified', true);
            if ($verified !== '' && $verified !== '1') {
                return new WP_Error('kbf_email_unverified', 'Please verify your email before signing in.');
            }
        }
    }
    return $user;
}, 30, 3);

add_action('wp_login_failed', function($username) {
    $ip = kbf_auth_get_ip();
    kbf_auth_register_failed_login($username, $ip);
});

add_action('wp_login', function($user_login, $user) {
    $ip = kbf_auth_get_ip();
    kbf_auth_clear_failed_login($user_login, $ip);
    if ($user instanceof WP_User && !headers_sent()) {
        // Keep bntm-hub single-session guard in sync for custom KBF sign-in flows.
        $session_token = wp_generate_password(32, false);
        update_user_meta($user->ID, '_bntm_session_token', $session_token);
        setcookie(
            'bntm_session_token',
            $session_token,
            time() + (30 * DAY_IN_SECONDS),
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }
    if ($user instanceof WP_User && function_exists('kbf_sync_onboarding_flag_for_user')) {
        kbf_sync_onboarding_flag_for_user((int) $user->ID);
    }
}, 10, 2);

if (!function_exists('kbf_dashboard_home_url')) {
    function kbf_dashboard_home_url() {
        $dashboard_url = function_exists('kbf_get_page_url') ? (string) kbf_get_page_url('dashboard') : '';
        $looks_like_filesystem_path = (bool) preg_match('/^[a-zA-Z]:[\\\\\\/]/', $dashboard_url);
        if ($looks_like_filesystem_path || $dashboard_url === '') {
            $dashboard_url = '';
        }
        if ($dashboard_url !== '' && strpos($dashboard_url, '/') === 0) {
            $dashboard_url = home_url($dashboard_url);
        }
        $dashboard_path = $dashboard_url ? (string) wp_parse_url($dashboard_url, PHP_URL_PATH) : '';
        $dashboard_is_login = $dashboard_path && preg_match('#/(?:wp-login\.php|login)/?$#i', $dashboard_path);
        if ($dashboard_url === '' || !wp_http_validate_url($dashboard_url) || $dashboard_is_login) {
            $dashboard_page = get_page_by_path('fundora-user');
            if ($dashboard_page && !empty($dashboard_page->ID)) {
                $dashboard_url = get_permalink($dashboard_page->ID);
            } else {
                $dashboard_url = home_url('/fundora-user/');
            }
        }
        return add_query_arg('kbf_tab', 'overview', $dashboard_url);
    }
}

if (!function_exists('kbf_landing_page_url')) {
    function kbf_landing_page_url() {
        $landing_url = function_exists('kbf_get_page_url') ? (string) kbf_get_page_url('landing') : '';
        $site_home = (string) home_url('/');
        $looks_like_filesystem_path = (bool) preg_match('/^[a-zA-Z]:[\\\\\\/]/', $landing_url);
        if ($looks_like_filesystem_path || $landing_url === '') {
            $landing_url = '';
        }
        // Reject plain site root; for logout we always want Fundora landing page.
        if ($landing_url !== '' && untrailingslashit($landing_url) === untrailingslashit($site_home)) {
            $landing_url = '';
        }
        if ($landing_url !== '' && strpos($landing_url, '/') === 0) {
            $landing_url = home_url($landing_url);
        }
        if ($landing_url === '' || !wp_http_validate_url($landing_url)) {
            $landing_page = get_page_by_path('fundora');
            if ($landing_page && !empty($landing_page->ID)) {
                $landing_url = get_permalink($landing_page->ID);
            } else {
                // Fallback to expected Fundora landing slug (never root "/").
                $landing_url = home_url('/fundora/');
                // If slug changed, try resolving by shortcode before final fallback.
                $pages = get_posts([
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    's' => '[kbf_landing]',
                ]);
                foreach ($pages as $p) {
                    if (has_shortcode($p->post_content, 'kbf_landing')) {
                        $landing_url = get_permalink($p->ID);
                        break;
                    }
                }
            }
        }
        return $landing_url;
    }
}

if (!function_exists('kbf_signin_page_url')) {
    function kbf_signin_page_url() {
        $signin_url = function_exists('kbf_get_page_url') ? (string) kbf_get_page_url('signin') : '';
        $site_home = (string) home_url('/');
        $looks_like_filesystem_path = (bool) preg_match('/^[a-zA-Z]:[\\\\\/]/', $signin_url);
        if ($looks_like_filesystem_path || $signin_url === '') {
            $signin_url = '';
        }
        if ($signin_url !== '' && untrailingslashit($signin_url) === untrailingslashit($site_home)) {
            $signin_url = '';
        }
        if ($signin_url !== '' && function_exists('kbf_landing_page_url')) {
            $landing_url = (string) kbf_landing_page_url();
            if ($landing_url !== '' && untrailingslashit($signin_url) === untrailingslashit($landing_url)) {
                $signin_url = '';
            }
        }
        if ($signin_url !== '' && strpos($signin_url, '/') === 0) {
            $signin_url = home_url($signin_url);
        }
        $signin_path = $signin_url ? (string) wp_parse_url($signin_url, PHP_URL_PATH) : '';
        $signin_is_core_login = $signin_path && preg_match('#/(?:wp-login\.php|login)/?$#i', $signin_path);
        if ($signin_url === '' || !wp_http_validate_url($signin_url) || $signin_is_core_login) {
            $signin_page = get_page_by_path('fundora-sign-in');
            if ($signin_page && !empty($signin_page->ID)) {
                $signin_url = get_permalink($signin_page->ID);
            } else {
                $signin_url = home_url('/fundora-sign-in/');
            }
        }
        return $signin_url;
    }
}

if (!function_exists('kbf_redirect_session_error_to_signin')) {
    function kbf_redirect_session_error_to_signin() {
        if (is_admin()) {
            return;
        }
        if (empty($_GET['session_error']) || (string) $_GET['session_error'] !== '1') {
            return;
        }
        $req_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $req_path = (string) wp_parse_url($req_uri, PHP_URL_PATH);
        if (!$req_path || !preg_match('#/(?:wp-login\.php|login)/?$#i', $req_path)) {
            return;
        }
        $signin_url = function_exists('kbf_signin_page_url') ? (string) kbf_signin_page_url() : '';
        if (!$signin_url) {
            $signin_url = home_url('/fundora-sign-in/');
        }
        $target = add_query_arg('session_error', '1', $signin_url);
        wp_safe_redirect($target);
        exit;
    }
    add_action('init', 'kbf_redirect_session_error_to_signin', 1);
}

if (!function_exists('kbf_set_reauth_lock_cookie')) {
    function kbf_set_reauth_lock_cookie() {
        if (headers_sent()) {
            return;
        }
        setcookie('kbf_reauth_lock', '1', time() + (10 * MINUTE_IN_SECONDS), '/', COOKIE_DOMAIN, is_ssl(), true);
    }
}

if (!function_exists('kbf_clear_reauth_lock_cookie')) {
    function kbf_clear_reauth_lock_cookie() {
        if (headers_sent()) {
            return;
        }
        setcookie('kbf_reauth_lock', '', time() - HOUR_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true);
        setcookie('kbf_reauth_lock', '', time() - HOUR_IN_SECONDS, '/', '', is_ssl(), true);
        unset($_COOKIE['kbf_reauth_lock']);
    }
}

if (!function_exists('kbf_expire_bntm_session_cookie')) {
    function kbf_expire_bntm_session_cookie() {
        if (headers_sent()) {
            return;
        }
        $secure = is_ssl();
        $cookie_paths = array_filter(array_unique([
            '/',
            (string) COOKIEPATH,
            defined('SITECOOKIEPATH') ? (string) SITECOOKIEPATH : '',
        ]));
        $cookie_domains = array_unique([
            (string) COOKIE_DOMAIN,
            '',
        ]);
        foreach ($cookie_paths as $path) {
            foreach ($cookie_domains as $domain) {
                setcookie('bntm_session_token', '', time() - HOUR_IN_SECONDS, $path, $domain, $secure, true);
            }
        }
        unset($_COOKIE['bntm_session_token']);
    }
}

if (!function_exists('kbf_logout_action_url')) {
    function kbf_logout_action_url() {
        $base_url = function_exists('kbf_landing_page_url') ? (string) kbf_landing_page_url() : home_url('/');
        if (!wp_http_validate_url($base_url)) {
            $base_url = home_url('/');
        }
        return add_query_arg([
            'kbf_action'       => 'logout',
            'kbf_logout_nonce' => wp_create_nonce('kbf_logout'),
        ], $base_url);
    }
}

if (!function_exists('kbf_handle_logout_request')) {
    function kbf_handle_logout_request() {
        if (is_admin()) {
            return;
        }
        $action = isset($_GET['kbf_action']) ? sanitize_key((string) wp_unslash($_GET['kbf_action'])) : '';
        if ($action !== 'logout') {
            return;
        }

        $signin_url = function_exists('kbf_signin_page_url') ? kbf_signin_page_url() : home_url('/fundora-sign-in/');
        $reauth_url = add_query_arg([
            'loggedout' => '1',
            'reauth'    => '1',
        ], $signin_url);

        if (!is_user_logged_in()) {
            wp_safe_redirect($reauth_url);
            exit;
        }

        $nonce = isset($_GET['kbf_logout_nonce']) ? sanitize_text_field((string) wp_unslash($_GET['kbf_logout_nonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'kbf_logout')) {
            wp_safe_redirect(add_query_arg('logout_error', 'nonce', $signin_url));
            exit;
        }

        $user_id = (int) get_current_user_id();
        if (function_exists('kbf_set_reauth_lock_cookie')) {
            kbf_set_reauth_lock_cookie();
        }
        wp_logout();

        if ($user_id > 0) {
            delete_user_meta($user_id, '_bntm_session_token');
        }
        if (function_exists('kbf_expire_bntm_session_cookie')) {
            kbf_expire_bntm_session_cookie();
        }

        wp_safe_redirect($reauth_url);
        exit;
    }
}
if (did_action('init')) {
    kbf_handle_logout_request();
}
add_action('init', 'kbf_handle_logout_request', 1);

if (!function_exists('kbf_enforce_reauth_lock')) {
    function kbf_enforce_reauth_lock() {
        if (is_admin()) {
            return;
        }
        $reauth_requested =
            (!empty($_GET['reauth']) && $_GET['reauth'] === '1') ||
            (!empty($_GET['loggedout']) && $_GET['loggedout'] === '1') ||
            (!empty($_COOKIE['kbf_reauth_lock']) && $_COOKIE['kbf_reauth_lock'] === '1');

        if (!$reauth_requested || !is_user_logged_in()) {
            return;
        }

        $user_id = (int) get_current_user_id();
        wp_logout();
        if ($user_id > 0) {
            delete_user_meta($user_id, '_bntm_session_token');
        }
        if (function_exists('kbf_expire_bntm_session_cookie')) {
            kbf_expire_bntm_session_cookie();
        }

        $signin_url = function_exists('kbf_signin_page_url') ? kbf_signin_page_url() : home_url('/fundora-sign-in/');
        $reauth_url = add_query_arg([
            'loggedout' => '1',
            'reauth'    => '1',
        ], $signin_url);
        if (!headers_sent()) {
            wp_safe_redirect($reauth_url);
            exit;
        }
    }
}
if (did_action('init')) {
    kbf_enforce_reauth_lock();
}
add_action('init', 'kbf_enforce_reauth_lock', 20);

if (!function_exists('kbf_auth_post_login_redirect')) {
    function kbf_auth_post_login_redirect($user, $default = '') {
        if (is_wp_error($user) || !($user instanceof WP_User)) {
            return $default;
        }
        if (user_can($user, 'manage_options')) {
            $requested = trim((string) $default);
            if ($requested !== '' && wp_http_validate_url($requested)) {
                return $requested;
            }
            return admin_url();
        }
        return kbf_dashboard_home_url();
    }
}

add_filter('login_redirect', function($redirect_to, $requested_redirect_to, $user) {
    return kbf_auth_post_login_redirect($user, $redirect_to);
}, 20, 3);

if (!function_exists('kbf_is_account_suspended')) {
    function kbf_is_account_suspended($user_id) {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }
        return !empty(get_user_meta($user_id, 'kbf_account_suspended', true));
    }
}

if (!function_exists('kbf_enforce_suspended_account_logout')) {
    function kbf_enforce_suspended_account_logout() {
        if (!is_user_logged_in()) {
            return;
        }
        $user_id = (int) get_current_user_id();
        if ($user_id <= 0 || user_can($user_id, 'manage_options')) {
            return;
        }
        if (!function_exists('kbf_is_account_suspended') || !kbf_is_account_suspended($user_id)) {
            return;
        }

        wp_logout();
        delete_user_meta($user_id, '_bntm_session_token');
        if (function_exists('kbf_expire_bntm_session_cookie')) {
            kbf_expire_bntm_session_cookie();
        }

        $signin_url = function_exists('kbf_signin_page_url') ? kbf_signin_page_url() : home_url('/fundora-sign-in/');
        $target = add_query_arg('suspended', '1', $signin_url);
        if (!headers_sent()) {
            wp_safe_redirect($target);
            exit;
        }
    }
}
add_action('init', 'kbf_enforce_suspended_account_logout', 21);

add_filter('authenticate', function($user, $username, $password) {
    if (is_wp_error($user)) {
        return $user;
    }
    $subject = null;
    if ($user instanceof WP_User) {
        $subject = $user;
    } else {
        $login = is_string($username) ? trim($username) : '';
        if ($login !== '') {
            if (is_email($login)) {
                $subject = get_user_by('email', $login);
            }
            if (!($subject instanceof WP_User)) {
                $subject = get_user_by('login', $login);
            }
            if (!($subject instanceof WP_User)) {
                $social = ltrim($login, '@');
                if ($social !== '') {
                    $social_user = get_users([
                        'meta_key'   => 'kbf_social_name',
                        'meta_value' => $social,
                        'number'     => 1,
                    ]);
                    if (!empty($social_user) && $social_user[0] instanceof WP_User) {
                        $subject = $social_user[0];
                    }
                }
            }
        }
    }
    if ($subject instanceof WP_User && !user_can($subject, 'manage_options') && function_exists('kbf_is_account_suspended') && kbf_is_account_suspended((int)$subject->ID)) {
        return new WP_Error('kbf_account_suspended', __('This account is suspended.'));
    }
    return $user;
}, 50, 3);

add_filter('logout_redirect', function($redirect_to, $requested_redirect_to, $user) {
    if (function_exists('kbf_set_reauth_lock_cookie')) {
        kbf_set_reauth_lock_cookie();
    }
    $signin_url = function_exists('kbf_signin_page_url') ? kbf_signin_page_url() : home_url('/fundora-sign-in/');
    return add_query_arg([
        'loggedout' => '1',
        'reauth'    => '1',
    ], $signin_url);
}, 9999, 3);

add_action('wp_logout', function($user_id = 0) {
    $user_id = (int) $user_id;
    if ($user_id > 0) {
        delete_user_meta($user_id, '_bntm_session_token');
    }
    if (function_exists('kbf_expire_bntm_session_cookie')) {
        kbf_expire_bntm_session_cookie();
    }
    if (function_exists('kbf_set_reauth_lock_cookie')) {
        kbf_set_reauth_lock_cookie();
    }
}, 20, 1);

add_filter('auth_cookie_expiration', function($seconds, $user_id, $remember) {
    if ($remember) {
        return 14 * DAY_IN_SECONDS;
    }
    return 8 * HOUR_IN_SECONDS;
}, 10, 3);

add_filter('password_reset_expiration', function($seconds) {
    return DAY_IN_SECONDS;
});

function kbf_auth_make_verify_hash($token) {
    return hash_hmac('sha256', (string) $token, wp_salt('auth'));
}

function kbf_ensure_organizer_account_row($user_id) {
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return false;
    }
    if (function_exists('kbf_get_or_create_organizer_token')) {
        // This helper creates the organizer profile row when missing.
        kbf_get_or_create_organizer_token($user_id);
        return true;
    }
    global $wpdb;
    $pt = $wpdb->prefix . 'kbf_organizer_profiles';
    $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d", $user_id));
    if ($exists > 0) {
        return true;
    }
    return (bool) $wpdb->insert($pt, ['business_id' => $user_id], ['%d']);
}

function kbf_handle_email_verification() {
    if (is_admin()) return;
    if (KBF_EMAIL_VERIFY_DISABLED) return;
    if (empty($_GET['kbf_verify']) || empty($_GET['uid'])) return;
    $token = sanitize_text_field(wp_unslash($_GET['kbf_verify']));
    $user_id = absint($_GET['uid']);
    if (!$user_id || !$token) return;
    $user = get_user_by('id', $user_id);
    if (!$user) return;
    $hash = get_user_meta($user_id, 'kbf_email_verify_hash', true);
    $expires = (int) get_user_meta($user_id, 'kbf_email_verify_expires', true);
    $valid = $hash && hash_equals($hash, kbf_auth_make_verify_hash($token)) && $expires && time() <= $expires;
    if ($valid) {
        update_user_meta($user_id, 'kbf_email_verified', '1');
        delete_user_meta($user_id, 'kbf_email_verify_hash');
        delete_user_meta($user_id, 'kbf_email_verify_expires');
        kbf_ensure_organizer_account_row($user_id);
        $target = function_exists('kbf_get_page_url') ? (string) kbf_get_page_url('signin') : '';
        if ($target === '' || !wp_http_validate_url($target)) {
            $target = home_url('/fundora-sign-in/');
        }
        $target_path = (string) wp_parse_url($target, PHP_URL_PATH);
        if ($target_path && preg_match('#/(?:wp-login\.php|login)/?$#i', $target_path)) {
            $target = home_url('/fundora-sign-in/');
        }
        wp_safe_redirect(add_query_arg('verified', '1', $target));
        exit;
    }
    // TODO: Add resend verification flow for expired/invalid tokens.
    $fallback = function_exists('kbf_get_page_url') ? kbf_get_page_url('signup') : home_url('/');
    wp_safe_redirect(add_query_arg('verify', 'failed', $fallback));
    exit;
}
add_action('init', 'kbf_handle_email_verification', 9);


// ============================================================
// SHARE META TAGS (Open Graph / Twitter)
// ============================================================

add_action('wp_head', 'kbf_output_share_meta', 5);
function kbf_output_share_meta() {
    if (is_admin()) return;
    // Prefer the dedicated Fund Details meta handler when available to avoid duplicate OG/Twitter tags.
    if (function_exists('kbf_fund_details_output_social_meta')) return;
    if (empty($_GET['kbf_share']) && empty($_GET['fund_id'])) return;
    if (!function_exists('esc_attr')) return;
    global $wpdb;
    $ft = $wpdb->prefix . 'kbf_funds';
    $fund = null;
    if (!empty($_GET['kbf_share'])) {
        $token = sanitize_text_field($_GET['kbf_share']);
        $fund = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$ft} WHERE share_token=%s AND status IN ('active','completed')",
            $token
        ));
    } elseif (!empty($_GET['fund_id'])) {
        $id = intval($_GET['fund_id']);
        $fund = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$ft} WHERE id=%d AND status IN ('active','completed')",
            $id
        ));
    }
    if (!$fund) return;

    $title = $fund->title ? $fund->title : 'KonekBayan Fundraiser';
    $desc  = $fund->description ? wp_strip_all_tags($fund->description) : 'Support this fundraiser on KonekBayan.';
    $desc  = wp_trim_words($desc, 22, '...');
    $base  = kbf_get_page_url('fund_details');
    $share = !empty($fund->share_token)
        ? add_query_arg('kbf_share', $fund->share_token, $base)
        : add_query_arg('fund', function_exists('kbf_get_or_create_fund_token') ? kbf_get_or_create_fund_token($fund->id) : $fund->id, $base);
    $img = '';
    if (!empty($fund->photos)) {
        $photos = json_decode($fund->photos, true);
        if (is_array($photos) && !empty($photos[0])) {
            $img = esc_url_raw($photos[0]);
        }
    }
    ?>
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php echo esc_attr($title); ?>">
    <meta property="og:description" content="<?php echo esc_attr($desc); ?>">
    <meta property="og:url" content="<?php echo esc_url($share); ?>">
    <?php if ($img): ?><meta property="og:image" content="<?php echo esc_url($img); ?>"><?php endif; ?>
    <meta name="twitter:card" content="<?php echo $img ? 'summary_large_image' : 'summary'; ?>">
    <meta name="twitter:title" content="<?php echo esc_attr($title); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr($desc); ?>">
    <?php if ($img): ?><meta name="twitter:image" content="<?php echo esc_url($img); ?>"><?php endif; ?>
<?php
/*
 * KBF module bootstrap: defines constants, shared helpers, DB/cron setup,
 * global assets, and loads user/admin components.
 */
}









// ============================================================


// ============================================================
// FUNDER DASHBOARD SHORTCODE
// ============================================================

function kbf_table_has_column($table, $column) {
    global $wpdb;
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
    return !empty($col);
}

/**
 * Safe wrapper for bntm_get_setting that guarantees a default value.
 * bntm_get_setting() may not support a second default parameter depending
 * on the framework version -- this wrapper handles both cases.
 */
function kbf_sensitive_setting_keys() {
    return [
        'kbf_maya_sandbox_public',
        'kbf_maya_sandbox_secret',
        'kbf_maya_live_public',
        'kbf_maya_live_secret',
        'kbf_maya_webhook_secret',
        'kbf_didit_sandbox_api_key',
        'kbf_didit_sandbox_app_id',
        'kbf_didit_sandbox_workflow_id',
        'kbf_didit_live_api_key',
        'kbf_didit_live_app_id',
        'kbf_didit_live_workflow_id',
        'kbf_didit_webhook_secret',
    ];
}

function kbf_is_sensitive_setting_key($key) {
    return in_array((string) $key, kbf_sensitive_setting_keys(), true);
}

function kbf_encrypt_setting_value($value) {
    $raw = (string) $value;
    if ($raw === '') {
        return '';
    }
    if (strpos($raw, 'kbfenc:v1:') === 0) {
        return $raw;
    }

    $key = hash('sha256', wp_salt('auth') . '|kbf-settings-v1', true);

    if (function_exists('sodium_crypto_secretbox') && defined('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES')) {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($raw, $nonce, $key);
        return 'kbfenc:v1:sodium:' . base64_encode($nonce . $cipher);
    }

    if (function_exists('openssl_encrypt')) {
        $iv_len = openssl_cipher_iv_length('aes-256-cbc');
        if (!is_int($iv_len) || $iv_len < 1) {
            $iv_len = 16;
        }
        $iv = random_bytes($iv_len);
        $cipher = openssl_encrypt($raw, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher !== false) {
            return 'kbfenc:v1:openssl:' . base64_encode($iv . $cipher);
        }
    }

    return $raw;
}

function kbf_decrypt_setting_value($value) {
    $raw = (string) $value;
    if ($raw === '') {
        return '';
    }
    if (strpos($raw, 'kbfenc:v1:') !== 0) {
        return $raw;
    }

    $parts = explode(':', $raw, 4);
    if (count($parts) !== 4) {
        return '';
    }
    $algo = $parts[2];
    $blob = base64_decode($parts[3], true);
    if ($blob === false) {
        return '';
    }

    $key = hash('sha256', wp_salt('auth') . '|kbf-settings-v1', true);

    if ($algo === 'sodium' && function_exists('sodium_crypto_secretbox_open') && defined('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES')) {
        $nonce_len = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        if (strlen($blob) <= $nonce_len) {
            return '';
        }
        $nonce = substr($blob, 0, $nonce_len);
        $cipher = substr($blob, $nonce_len);
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
        return $plain === false ? '' : (string) $plain;
    }

    if ($algo === 'openssl' && function_exists('openssl_decrypt')) {
        $iv_len = openssl_cipher_iv_length('aes-256-cbc');
        if (!is_int($iv_len) || $iv_len < 1 || strlen($blob) <= $iv_len) {
            return '';
        }
        $iv = substr($blob, 0, $iv_len);
        $cipher = substr($blob, $iv_len);
        $plain = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : (string) $plain;
    }

    return '';
}

function kbf_get_setting($key, $default = null) {
    $is_sensitive = kbf_is_sensitive_setting_key($key);
    if (function_exists('bntm_get_setting')) {
        $val = bntm_get_setting($key);
        if (!($val === null || $val === false || $val === '')) {
            $resolved = $is_sensitive ? kbf_decrypt_setting_value((string) $val) : $val;
            if (!($resolved === null || $resolved === false || $resolved === '')) {
                return $resolved;
            }
        }
        // If framework setting is empty, fall back to mirrored wp_options value.
        $stored = get_option('kbf_setting_' . $key, null);
        if ($stored === null || $stored === false || $stored === '') {
            return $default;
        }
        $resolved = $is_sensitive ? kbf_decrypt_setting_value((string) $stored) : $stored;
        return ($resolved === null || $resolved === false || $resolved === '') ? $default : $resolved;
    }
    // Fallback: store in wp_options directly
    $stored = get_option('kbf_setting_' . $key, null);
    if ($stored === null || $stored === false || $stored === '') {
        return $default;
    }
    $resolved = $is_sensitive ? kbf_decrypt_setting_value((string) $stored) : $stored;
    return ($resolved === null || $resolved === false || $resolved === '') ? $default : $resolved;
}

function kbf_set_setting($key, $value) {
    $to_store = kbf_is_sensitive_setting_key($key)
        ? kbf_encrypt_setting_value($value)
        : $value;
    if (function_exists('bntm_set_setting')) {
        bntm_set_setting($key, $to_store);
    }
    // Also mirror to wp_options as fallback
    update_option('kbf_setting_' . $key, $to_store);
}

function kbf_get_platform_fee_rate($fund) {
    $fee_disabled = (bool) kbf_get_setting('kbf_disable_platform_fee', false);
    if ($fee_disabled) {
        return 0.0;
    }
    if (!$fund) {
        return 0.05;
    }
    $deadline_ts = $fund->deadline ? strtotime($fund->deadline . ' 23:59:59') : 0;
    $now = time();
    if ($fund->raised_amount >= $fund->goal_amount && $deadline_ts && $now <= $deadline_ts) {
        return 0.03;
    }
    return 0.05;
}

function kbf_withdrawal_status_label($status) {
    if ($status === 'released' || $status === 'approved') return 'Approved';
    if ($status === 'rejected') return 'Rejected';
    if ($status === 'pending') return 'Pending';
    return ucfirst(sanitize_text_field((string)$status));
}

function kbf_withdrawal_badge_class($status) {
    if ($status === 'released' || $status === 'approved') return 'active';
    if ($status === 'rejected') return 'cancelled';
    if ($status === 'pending') return 'pending';
    return sanitize_html_class((string)$status);
}

function kbf_role_nav($role) {
    $links = [];
    if ($role === 'funder') {
        $base = kbf_get_page_url('dashboard');
        $links = [
            ['Create Fundraiser', add_query_arg('kbf_tab','my_funds',$base)],
            ['Manage Fundraisers', add_query_arg('kbf_tab','my_funds',$base)],
            ['Request Withdrawal', add_query_arg('kbf_tab','withdrawals',$base)],
            ['View Fundraiser Status', add_query_arg('kbf_tab','overview',$base)],
        ];
    } elseif ($role === 'sponsor') {
        $links = [
            ['Browse Fundraisers', kbf_get_page_url('browse')],
            ['View Fundraiser Details', kbf_get_page_url('fund_details')],
            ['Donate to Fundraiser', kbf_get_page_url('fund_details')],
            ['Donation History', kbf_get_page_url('sponsor_history')],
            ['Funder Profiles', kbf_get_page_url('organizer_profile')],
        ];
    } elseif ($role === 'admin') {
        $base = kbf_get_page_url('admin');
        $links = [
            ['Manage Fundraisers', add_query_arg('kbf_tab','pending',$base)],
            ['Withdraw Requests Panel', add_query_arg('kbf_tab','withdrawals',$base)],
            ['Approve or Reject Withdrawals', add_query_arg('kbf_tab','withdrawals',$base)],
            ['User Management', add_query_arg('kbf_tab','organizers',$base)],
            ['Platform Monitoring', add_query_arg('kbf_tab','transactions',$base)],
        ];
    }
    if (empty($links)) return '';
    ob_start(); ?>
    <div class="kbf-card" style="margin-bottom:18px;">
      <div style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-slate);margin-bottom:10px;"><?php echo esc_html(ucfirst($role)); ?> Pages</div>
      <div class="kbf-btn-group">
        <?php foreach($links as $l): ?>
          <a class="kbf-btn kbf-btn-secondary kbf-btn-sm" href="<?php echo esc_url($l[1]); ?>"><?php echo esc_html($l[0]); ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

function kbf_get_categories() {
    return ['Community','Sports','Family','Emergency','Education','Medical','Business','Religion','Arts & Culture','Environment','Animals','Others'];
}

function kbf_get_provinces() {
    return [
        'Abra','Agusan del Norte','Agusan del Sur','Aklan','Albay','Antique','Apayao','Aurora','Basilan','Bataan',
        'Batanes','Batangas','Benguet','Biliran','Bohol','Bukidnon','Bulacan','Cagayan','Camarines Norte','Camarines Sur',
        'Camiguin','Capiz','Catanduanes','Cavite','Cebu','Cotabato','Davao de Oro','Davao del Norte','Davao del Sur','Davao Occidental',
        'Davao Oriental','Dinagat Islands','Eastern Samar','Guimaras','Ifugao','Ilocos Norte','Ilocos Sur','Iloilo','Isabela','Kalinga',
        'La Union','Laguna','Lanao del Norte','Lanao del Sur','Leyte','Maguindanao del Norte','Maguindanao del Sur','Marinduque','Masbate','Misamis Occidental',
        'Misamis Oriental','Mountain Province','Negros Occidental','Negros Oriental','Northern Samar','Nueva Ecija','Nueva Vizcaya','Occidental Mindoro','Oriental Mindoro','Palawan',
        'Pampanga','Pangasinan','Quezon','Quirino','Rizal','Romblon','Samar','Sarangani','Siquijor','Sorsogon',
        'South Cotabato','Southern Leyte','Sultan Kudarat','Sulu','Surigao del Norte','Surigao del Sur','Tarlac','Tawi-Tawi','Zambales','Zamboanga del Norte',
        'Zamboanga del Sur','Zamboanga Sibugay'
    ];
}

/**
 * Resolve the URL of a page by the shortcode it contains.
 * Usage: kbf_get_page_url('fund_details') â†’ URL of the page with [kbf_fund_details]
 */
function kbf_get_page_url($page_key) {
    static $cache = [];
    if(isset($cache[$page_key])) return $cache[$page_key];
    $slug_by_key = [
        'landing'   => 'fundora',
        'dashboard' => 'fundora-user',
        'terms'     => 'fundora-terms',
        'privacy'   => 'fundora-privacy',
        'refund'    => 'fundora-refund',
        'admin'     => 'fundora-admin',
        'signin'    => 'fundora-sign-in',
        'signup'    => 'fundora-sign-up',
        'reset_password' => 'fundora-reset-password',
    ];
    if (isset($slug_by_key[$page_key])) {
        $p = get_page_by_path($slug_by_key[$page_key]);
        if ($p && $p->ID) {
            $url = get_permalink($p->ID);
            $cache[$page_key] = $url;
            return $url;
        }
    }
    $dashboard_tabs = [
        'browse'            => 'find_funds',
        'fund_details'      => 'fund_details',
        'organizer_profile' => 'organizer_profile',
        'sponsor_history'   => 'sponsor_history',
    ];
    if (isset($dashboard_tabs[$page_key])) {
        $dash_url = kbf_get_page_url('dashboard');
        $url = add_query_arg('kbf_tab', $dashboard_tabs[$page_key], $dash_url);
        $cache[$page_key] = $url;
        return $url;
    }
    $shortcode_map = [
        'dashboard'         => 'kbf_dashboard',
        'admin'             => 'kbf_admin',
        'signin'            => 'kbf_signin',
        'signup'            => 'kbf_signup',
        'reset_password'    => 'kbf_reset_password',
        'terms'             => 'kbf_terms',
        'privacy'           => 'kbf_privacy',
        'refund'            => 'kbf_refund',
    ];
    $shortcode = $shortcode_map[$page_key] ?? $page_key;
    // Try bntm framework page setting first
    $stored_url = trim((string) bntm_get_setting('kbf_page_' . $page_key));
    if ($stored_url !== '' && stripos($stored_url, 'konekbayan') === false) {
        // Guard against accidentally saved filesystem paths (e.g. C:\...\signin.php).
        $looks_like_filesystem_path = (bool) preg_match('/^[a-zA-Z]:[\\\\\\/]/', $stored_url);
        if (!$looks_like_filesystem_path) {
            if (strpos($stored_url, '/') === 0) {
                $stored_url = home_url($stored_url);
            }
            if (wp_http_validate_url($stored_url)) {
                // Never use WordPress core login endpoints for Fundora page URLs.
                $stored_path = (string) wp_parse_url($stored_url, PHP_URL_PATH);
                if ($stored_path && preg_match('#/(?:wp-login\.php|login)/?$#i', $stored_path)) {
                    $stored_url = '';
                }
            }
            if ($stored_url !== '' && wp_http_validate_url($stored_url)) {
                $cache[$page_key] = $stored_url;
                return $stored_url;
            }
        }
    }
    // Fall back: search all pages for the shortcode
    $pages = get_posts(['post_type'=>'page','post_status'=>'publish','numberposts'=>-1,'s'=>'['.$shortcode.']']);
    foreach($pages as $p) {
        if(has_shortcode($p->post_content, $shortcode)) {
            $url = get_permalink($p->ID);
            $cache[$page_key] = $url;
            return $url;
        }
    }
    // Last resort: home URL (will at least not 404)
    return home_url('/');
}

if (!function_exists('bntm_rand_id')) {
    function bntm_rand_id($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

function kbf_refund_all_sponsors($fund_id) {
    // Auto-refund disabled.
    return;
}

// Hide legacy sidebar on both frontend and admin
function kbf_hide_bntm_sidebar_styles() {
    echo '<style type="text/css">
        /* Force remove sidebar margin on all KBF pages */
        .bntm-main,
        main.bntm-main,
        .bntm-main.sidebar-collapsed,
        main.bntm-main.sidebar-collapsed,
        #bntmMain,
        .bntm-container {
            margin-left: 0 !important;
            margin-inline-start: 0 !important;
            transition: none !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 100% !important;
            flex: 1 1 100% !important;
            flex-basis: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        
        /* Specifically target the problematic margin-left property */
        .bntm-main {
            margin-left: 0 !important;
            --bntm-sidebar-width: 0 !important;
            --bntm-sidebar-collapsed: 0 !important;
            --bntm-transition: 0s !important;
        }
        
        /* Hide any sidebar elements */
        .bntm-sidebar,
        #bntmSidebar,
        [class*="bntm-sidebar"] {
            display: none !important;
            visibility: hidden !important;
            width: 0 !important;
            min-width: 0 !important;
            max-width: 0 !important;
            overflow: hidden !important;
        }
        
        /* Fix layout container */
        .bntm-layout {
            display: block !important;
            flex-direction: column !important;
        }
        
        /* Hide legacy fixed performance monitor widget on KBF pages */
        div[style*="position:fixed"][style*="font-family:monospace"][style*="z-index:99999"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        .bntm-sidebar-overlay, 
        #bntmSidebarOverlay { 
            display: none !important; 
        }
    </style>';
}

add_action('wp_head', 'kbf_hide_bntm_sidebar_styles', 99999);
add_action('admin_head', 'kbf_hide_bntm_sidebar_styles', 99999);

function kbf_hide_bntm_sidebar_js() {
    echo '<script type="text/javascript">
        (function(){
            function removeSidebarMargin() {
                // Remove CSS variable
                document.documentElement.style.setProperty("--bntm-sidebar-width", "0");
                document.documentElement.style.setProperty("--bntm-sidebar-collapsed", "0");
                document.documentElement.style.setProperty("--bntm-transition", "0s");
                
                // Target all .bntm-main elements
                var mains = document.querySelectorAll(".bntm-main, main.bntm-main, #bntmMain");
                mains.forEach(function(main) {
                    main.classList.remove("sidebar-collapsed");
                    main.style.setProperty("margin-left", "0px", "important");
                    main.style.setProperty("margin-inline-start", "0px", "important");
                    main.style.setProperty("transition", "none", "important");
                    main.style.setProperty("--bntm-sidebar-width", "0px", "important");
                    main.style.setProperty("--bntm-sidebar-collapsed", "0px", "important");
                    main.style.setProperty("--bntm-transition", "0s", "important");
                });
                
                // Hide sidebar elements
                var sidebars = document.querySelectorAll(".bntm-sidebar, #bntmSidebar, [class*=\"bntm-sidebar\"]");
                sidebars.forEach(function(sidebar) {
                    sidebar.style.setProperty("display", "none", "important");
                    sidebar.style.setProperty("width", "0", "important");
                });

                // Remove debug monitor widget (CPU / Memory / Queries)
                var monitors = document.querySelectorAll("div[style*=\"position:fixed\"][style*=\"font-family:monospace\"], div[style*=\"position:fixed\"][style*=\"bottom:10px\"][style*=\"right:10px\"]");
                monitors.forEach(function(el) {
                    var txt = (el.textContent || "").toLowerCase();
                    if (txt.indexOf("cpu:") !== -1 && txt.indexOf("memory:") !== -1 && txt.indexOf("queries:") !== -1) {
                        if (el.parentNode) {
                            el.parentNode.removeChild(el);
                        } else {
                            el.style.setProperty("display", "none", "important");
                        }
                    }
                });
            }
            
            // Run immediately and after DOM ready
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", removeSidebarMargin);
            } else {
                removeSidebarMargin();
            }
            
            // Monitor for dynamic changes
            var observer = new MutationObserver(removeSidebarMargin);
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ["style", "class"],
                subtree: true,
                childList: true
            });
            
            setTimeout(removeSidebarMargin, 100);
            setTimeout(removeSidebarMargin, 500);
        })();
    </script>';
}
add_action('wp_footer', 'kbf_hide_bntm_sidebar_js', 99999);
add_action('admin_footer', 'kbf_hide_bntm_sidebar_js', 99999);

/**
 * Ensure wp_usermeta has an index for (meta_key, meta_value) to speed up @username lookups.
 * Runs once safely.
 */
function kbf_ensure_usermeta_index() {
    if (get_option('kbf_usermeta_index_checked')) return;
    global $wpdb;
    $table = $wpdb->usermeta;
    $index_name = 'meta_key_value';
    $index_exists = $wpdb->get_results($wpdb->prepare("SHOW INDEX FROM {$table} WHERE Key_name = %s", $index_name));
    if (empty($index_exists)) {
        $wpdb->query("ALTER TABLE {$table} ADD INDEX {$index_name} (meta_key(191), meta_value(191))");
    }
    update_option('kbf_usermeta_index_checked', 1);
}
add_action('admin_init', 'kbf_ensure_usermeta_index');











