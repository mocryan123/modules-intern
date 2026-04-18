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
add_action('user_register', 'kbf_mark_first_login', 10, 1);

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

add_action('wp_login', function($user_login) {
    $ip = kbf_auth_get_ip();
    kbf_auth_clear_failed_login($user_login, $ip);
}, 10, 1);

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
        $target = function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : wp_login_url();
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
function kbf_get_setting($key, $default = null) {
    if (function_exists('bntm_get_setting')) {
        $val = bntm_get_setting($key);
        return ($val === null || $val === false || $val === '') ? $default : $val;
    }
    // Fallback: store in wp_options directly
    $stored = get_option('kbf_setting_' . $key, null);
    return ($stored === null) ? $default : $stored;
}

function kbf_set_setting($key, $value) {
    if (function_exists('bntm_set_setting')) {
        bntm_set_setting($key, $value);
    }
    // Also mirror to wp_options as fallback
    update_option('kbf_setting_' . $key, $value);
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
        'terms'             => 'kbf_terms',
        'privacy'           => 'kbf_privacy',
        'refund'            => 'kbf_refund',
    ];
    $shortcode = $shortcode_map[$page_key] ?? $page_key;
    // Try bntm framework page setting first
    $stored_url = bntm_get_setting('kbf_page_' . $page_key);
    if($stored_url && stripos($stored_url, 'konekbayan') === false) {
        $cache[$page_key] = $stored_url;
        return $stored_url;
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
        .bntm-layout { display: block !important; flex-direction: column !important; }
        .bntm-sidebar, #bntmSidebar, 
        aside.bntm-sidebar, div.bntm-sidebar { 
            display: none !important; 
            width: 0 !important; 
            min-width: 0 !important; 
            visibility: hidden !important; 
            opacity: 0 !important; 
            position: absolute !important; 
            left: -9999px !important; 
        }
        .bntm-main, .bntm-container, main.bntm-main, #bntmMain {
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            float: none !important;
        }
        .bntm-sidebar-overlay, #bntmSidebarOverlay { display: none !important; }
    </style>';
}
add_action('wp_head', 'kbf_hide_bntm_sidebar_styles', 99999);
add_action('admin_head', 'kbf_hide_bntm_sidebar_styles', 99999);

function kbf_hide_bntm_sidebar_js() {
    echo '<script type="text/javascript">(function(){
        console.log("KBF Sidebar Hider JS: Executed");
        
        // 1. Remove Sidebar from DOM immediately
        var sb = document.getElementById("bntmSidebar") || document.querySelector(".bntm-sidebar");
        if (sb && sb.parentNode) {
            sb.parentNode.removeChild(sb);
            console.log("KBF Sidebar Hider: Sidebar removed from DOM");
        }

        // 2. Fix Layout Container to single column
        var layout = document.querySelector(".bntm-layout");
        if (layout) {
            layout.style.display = "block";
            layout.style.flexDirection = "column";
            layout.style.flexWrap = "nowrap";
        }

        // 3. Force Main Content to full width
        var main = document.getElementById("bntmMain") || document.querySelector(".bntm-main");
        if (main) {
            main.style.marginLeft = "0";
            main.style.marginRight = "0";
            main.style.width = "100%";
            main.style.maxWidth = "100%";
            main.style.flex = "none";
            main.style.float = "none";
        }

        // 4. Hide Overlay
        var overlay = document.getElementById("bntmSidebarOverlay") || document.querySelector(".bntm-sidebar-overlay");
        if (overlay) overlay.style.display = "none";
    })();</script>';
}
add_action('wp_footer', 'kbf_hide_bntm_sidebar_js', 99999);

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











