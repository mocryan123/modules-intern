<?php
/**
 * Module Name: KonekBayan Finding Platform
 * Module Slug: kbf
 * Description: Community crowdfunding and sponsorship platform for funders and sponsors.
 * Version: 2.0.0
 * Author: KonekBayan
 * Icon: finding-platform
 */

if (!defined('ABSPATH')) exit;

define('BNTM_KBF_PATH', dirname(__FILE__) . '/');
define('BNTM_KBF_URL', plugin_dir_url(__FILE__));

require_once(BNTM_KBF_PATH . 'user.php');
require_once(BNTM_KBF_PATH . 'admin.php');


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
        : add_query_arg('fund_id', $fund->id, $base);
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
}

// ============================================================
// MODULE CONFIGURATION FUNCTIONS
// ============================================================

function bntm_kbf_get_pages() {
    return [
        'KonekBayan: Landing' => '[kbf_landing]',
        'KonekBayan: User'  => '[kbf_dashboard]',
        'KonekBayan: Admin' => '[kbf_admin]',
    ];
}

// Ensure required pages exist and have the correct shortcodes.
add_action('admin_init', 'bntm_kbf_ensure_pages');
function bntm_kbf_ensure_pages() {
    if (!current_user_can('manage_options')) return;
    $pages = bntm_kbf_get_pages();
    foreach ($pages as $title => $shortcode) {
        $shortcode_tag = trim($shortcode, '[]');

        // 1) Prefer an existing page that already contains the shortcode.
        $existing = get_posts([
            'post_type'   => 'page',
            'post_status' => ['publish','draft','private'],
            'numberposts' => -1,
            's'           => '[' . $shortcode_tag . ']',
        ]);
        $page = null;
        foreach ($existing as $p) {
            if (has_shortcode($p->post_content, $shortcode_tag)) { $page = $p; break; }
        }
        if ($page) {
            if ($page->post_title !== $title) {
                wp_update_post(['ID' => $page->ID, 'post_title' => $title]);
            }
            if ($page->post_status !== 'publish') {
                wp_update_post(['ID' => $page->ID, 'post_status' => 'publish']);
            }
            continue;
        }

        // 2) If a page with the desired title exists but is empty, inject the shortcode.
        $title_page = get_page_by_title($title, OBJECT, 'page');
        if ($title_page) {
            $content = $title_page->post_content;
            if (!has_shortcode($content, $shortcode_tag) && trim(wp_strip_all_tags($content)) === '') {
                wp_update_post(['ID' => $title_page->ID, 'post_content' => $shortcode]);
            }
            if ($title_page->post_status !== 'publish') {
                wp_update_post(['ID' => $title_page->ID, 'post_status' => 'publish']);
            }
            continue;
        }

        // 3) Otherwise, create the page.
        wp_insert_post([
            'post_title'   => $title,
            'post_content' => $shortcode,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}

function bntm_kbf_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    return [
        'kbf_funds' => "CREATE TABLE {$prefix}kbf_funds (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            funder_type ENUM('yourself','someone_else','charity_event') NOT NULL DEFAULT 'yourself',
            title VARCHAR(255) NOT NULL,
            description LONGTEXT,
            photos TEXT COMMENT 'JSON array of photo URLs',
            goal_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            raised_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            category VARCHAR(100) DEFAULT 'Others',
            email VARCHAR(255),
            phone VARCHAR(50),
            valid_id_path VARCHAR(500),
            location VARCHAR(255),
            deadline DATE,
            auto_return TINYINT(1) DEFAULT 0,
            status ENUM('draft','pending','active','completed','cancelled','suspended') DEFAULT 'pending',
            verified_badge TINYINT(1) DEFAULT 0,
            share_token VARCHAR(64) UNIQUE,
            escrow_status ENUM('holding','released','refunded') DEFAULT 'holding',
            admin_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_status (status),
            INDEX idx_category (category),
            INDEX idx_share_token (share_token)
        ) {$charset};",

        'kbf_sponsorships' => "CREATE TABLE {$prefix}kbf_sponsorships (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            sponsor_name VARCHAR(255),
            is_anonymous TINYINT(1) DEFAULT 0,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            email VARCHAR(255),
            phone VARCHAR(50),
            payment_method ENUM('gcash','paymaya','bank_transfer') DEFAULT 'gcash',
            payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
            payment_reference VARCHAR(255),
            receipt_path VARCHAR(500),
            gateway_payload LONGTEXT COMMENT 'Placeholder: store raw gateway JSON response here',
            receipt_sent TINYINT(1) DEFAULT 0,
            notified TINYINT(1) DEFAULT 0,
            message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_fund (fund_id),
            INDEX idx_status (payment_status)
        ) {$charset};",

        'kbf_withdrawals' => "CREATE TABLE {$prefix}kbf_withdrawals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            funder_name VARCHAR(255),
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            method VARCHAR(100),
            account_name VARCHAR(255),
            account_number VARCHAR(255),
            account_details TEXT,
            status ENUM('pending','approved','released','rejected') DEFAULT 'pending',
            admin_notes TEXT,
            requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME,
            INDEX idx_fund (fund_id),
            INDEX idx_status (status)
        ) {$charset};",

        'kbf_reports' => "CREATE TABLE {$prefix}kbf_reports (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            reporter_id BIGINT UNSIGNED DEFAULT 0,
            reporter_email VARCHAR(255),
            reason VARCHAR(255),
            details TEXT,
            status ENUM('open','reviewed','dismissed') DEFAULT 'open',
            admin_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_fund (fund_id),
            INDEX idx_status (status)
        ) {$charset};",

        'kbf_appeals' => "CREATE TABLE {$prefix}kbf_appeals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            status ENUM('open','reviewed','approved','rejected') DEFAULT 'open',
            admin_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_fund (fund_id),
            INDEX idx_business (business_id),
            INDEX idx_status (status)
        ) {$charset};",

        'kbf_organizer_profiles' => "CREATE TABLE {$prefix}kbf_organizer_profiles (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            business_id BIGINT UNSIGNED NOT NULL UNIQUE,
            bio TEXT,
            avatar_url VARCHAR(500),
            social_links TEXT COMMENT 'JSON: facebook, instagram, twitter',
            is_verified TINYINT(1) DEFAULT 0,
            total_raised DECIMAL(15,2) DEFAULT 0.00,
            total_sponsors INT DEFAULT 0,
            rating DECIMAL(3,2) DEFAULT 0.00,
            rating_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id)
        ) {$charset};",

        'kbf_ratings' => "CREATE TABLE {$prefix}kbf_ratings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            organizer_id BIGINT UNSIGNED NOT NULL,
            sponsor_email VARCHAR(255),
            rating TINYINT NOT NULL DEFAULT 5,
            review TEXT,
            fund_id BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_organizer (organizer_id)
        ) {$charset};",
    ];
}

function bntm_kbf_get_shortcodes() {
    return [
        'kbf_landing'            => 'bntm_shortcode_kbf_landing',
        'kbf_dashboard'          => 'bntm_shortcode_kbf_dashboard',
        'kbf_browse'             => 'bntm_shortcode_kbf_browse',
        'kbf_fund_details'       => 'bntm_shortcode_kbf_fund_details',
        'kbf_organizer_profile'  => 'bntm_shortcode_kbf_organizer_profile',
        'kbf_sponsor_history'    => 'bntm_shortcode_kbf_sponsor_history',
        'kbf_admin'              => 'bntm_shortcode_kbf_admin',
    ];
}

if (file_exists(BNTM_KBF_PATH . 'landing.php')) {
    require_once(BNTM_KBF_PATH . 'landing.php');
}
function bntm_shortcode_kbf_landing() {
    return function_exists('bntm_kbf_render_landing') ? bntm_kbf_render_landing() : '';
}

function bntm_kbf_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_kbf_get_tables();
    foreach ($tables as $sql) { dbDelta($sql); }
    return count($tables);
}
add_action('init', 'bntm_kbf_maybe_update_db', 1);
function bntm_kbf_maybe_update_db() {
    $target = '2.0.1';
    $installed = get_option('kbf_db_version');
    if ($installed !== $target) {
        bntm_kbf_create_tables();
        update_option('kbf_db_version', $target);
    }
}

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

add_action('wp_ajax_kbf_admin_approve_fund',         'bntm_ajax_kbf_admin_approve_fund');
add_action('wp_ajax_kbf_admin_reject_fund',          'bntm_ajax_kbf_admin_reject_fund');
add_action('wp_ajax_kbf_admin_suspend_fund',         'bntm_ajax_kbf_admin_suspend_fund');
add_action('wp_ajax_kbf_admin_verify_badge',         'bntm_ajax_kbf_admin_verify_badge');
add_action('wp_ajax_kbf_admin_release_escrow',       'bntm_ajax_kbf_admin_release_escrow');
add_action('wp_ajax_kbf_admin_hold_escrow',          'bntm_ajax_kbf_admin_hold_escrow');
add_action('wp_ajax_kbf_admin_process_withdrawal',   'bntm_ajax_kbf_admin_process_withdrawal');
add_action('wp_ajax_kbf_admin_dismiss_report',       'bntm_ajax_kbf_admin_dismiss_report');
add_action('wp_ajax_kbf_admin_review_report',        'bntm_ajax_kbf_admin_review_report');
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

// ============================================================
// CRON
// ============================================================

add_action('kbf_check_deadlines', 'kbf_cron_check_deadlines');
if (!wp_next_scheduled('kbf_check_deadlines')) {
    wp_schedule_event(time(), 'hourly', 'kbf_check_deadlines');
}

function kbf_cron_check_deadlines() {
    global $wpdb;
    $table = $wpdb->prefix . 'kbf_funds';
    $expired = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE status='active' AND deadline IS NOT NULL AND deadline < %s",
        current_time('Y-m-d')
    ));
    foreach ($expired as $fund) {
        if ($fund->raised_amount >= $fund->goal_amount) {
            $wpdb->update($table, ['status' => 'completed'], ['id' => $fund->id], ['%s'], ['%d']);
        } elseif ($fund->auto_return) {
            kbf_refund_all_sponsors($fund->id);
            $wpdb->update($table, ['status' => 'cancelled', 'escrow_status' => 'refunded'], ['id' => $fund->id], ['%s','%s'], ['%d']);
        } else {
            $wpdb->update($table, ['status' => 'completed'], ['id' => $fund->id], ['%s'], ['%d']);
        }
        // =====================================================
        // NOTIFICATION PLACEHOLDER
        // TODO: Hook your 3rd-party notification service here
        // Example: do_action('kbf_fund_deadline_reached', $fund);
        // =====================================================
    }
}


// ============================================================
// GLOBAL CSS + JS (shared across all shortcodes)
// ============================================================

function kbf_global_assets() {
    static $printed = false;
    if ($printed) return;
    $printed = true;
    ?>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
    /* === KBF DESIGN SYSTEM -- Landing-aligned (Blue/White) === */
    :root {
        --kbf-navy:       #0f1115;
        --kbf-navy-mid:   #1a2333;
        --kbf-navy-light: #22324a;
        --kbf-accent:     #6fb6ff;
        --kbf-accent-lt:  #e7f1ff;
        --kbf-green:      #6fb6ff;
        --kbf-green-lt:   #e7f1ff;
        --kbf-red:        #ef4444;
        --kbf-red-lt:     #fee2e2;
        --kbf-blue:       #3b82f6;
        --kbf-blue-lt:    #dbeafe;
        --kbf-slate:      #6f7785;
        --kbf-slate-lt:   #f3f5f8;
        --kbf-border:     #edf0f4;
        --kbf-text:       #0f1115;
        --kbf-text-sm:    #6f7785;
        --kbf-bg:         #ffffff;
        --kbf-surface:    #ffffff;
        --kbf-radius:     14px;
        --kbf-shadow:     0 12px 30px rgba(15,23,42,.06);
        --kbf-shadow-lg:  0 18px 40px rgba(15,23,42,.10);
    }
    .kbf-wrap * { box-sizing: border-box; }
    .kbf-wrap { font-family: 'Poppins', system-ui, -apple-system, sans-serif; color: var(--kbf-text); background: var(--kbf-bg); border-radius: 18px; padding: 18px; }
    .kbf-eyebrow { font-size: 11.5px; text-transform: uppercase; letter-spacing: .16em; color: var(--kbf-slate); font-weight: 700; }

    /* Tabs */
    .kbf-tabs { display: flex; gap: 8px; background: var(--kbf-surface); border: 1px solid var(--kbf-border); border-radius: 999px; padding: 6px; overflow-x: auto; box-shadow: var(--kbf-shadow); }
    .kbf-tab { display: inline-flex; align-items: center; gap: 7px; padding: 10px 14px; color: var(--kbf-slate); font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 999px; white-space: nowrap; transition: all .18s; }
    .kbf-tab svg { opacity: .75; }
    .kbf-tab:hover { color: var(--kbf-text); background: var(--kbf-slate-lt); }
    .kbf-tab.active { color: var(--kbf-text); background: var(--kbf-accent-lt); border: 1px solid rgba(111,182,255,.25); }
    .kbf-tab-content { background: var(--kbf-surface); border: 1px solid var(--kbf-border); border-radius: 16px; padding: 24px; margin-top: 14px; box-shadow: var(--kbf-shadow); }

    /* Cards & Sections */
    .kbf-section { margin-bottom: 28px; }
    .kbf-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
    .kbf-section-title { font-size: 16px; font-weight: 700; color: var(--kbf-text); margin: 0; }
    .kbf-card { background: var(--kbf-surface); border: 1px solid var(--kbf-border); border-radius: 16px; padding: 20px; margin-bottom: 14px; transition: transform .18s, box-shadow .2s; }
    .kbf-card:hover { box-shadow: var(--kbf-shadow); transform: translateY(-1px); }
    .kbf-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }

    /* Stats Row */
    .kbf-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 16px; margin-bottom: 28px; }
    .kbf-stat { background: var(--kbf-surface); border: 1px solid var(--kbf-border); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; }
    .kbf-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .kbf-stat-label { font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--kbf-slate); }
    .kbf-stat-value { font-size: 22px; font-weight: 800; color: var(--kbf-navy); line-height: 1.2; }
    .kbf-stat-sub { font-size: 12px; color: var(--kbf-slate); margin-top: 2px; }

    /* Progress */
    .kbf-progress-wrap { background: #eef2f7; border-radius: 999px; height: 8px; overflow: hidden; }
    .kbf-progress-bar { height: 8px; border-radius: 999px; background: linear-gradient(90deg, var(--kbf-accent), #34d399); transition: width .6s ease; }

    /* Badges / Status */
    .kbf-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
    .kbf-badge-pending   { background: #fef3c7; color: #92400e; }
    .kbf-badge-active    { background: var(--kbf-green-lt); color: #14532d; }
    .kbf-badge-completed { background: var(--kbf-blue-lt); color: #1e3a8a; }
    .kbf-badge-cancelled { background: var(--kbf-red-lt); color: #7f1d1d; }
    .kbf-badge-suspended { background: #fce7f3; color: #831843; }
    .kbf-badge-draft     { background: var(--kbf-slate-lt); color: #334155; }
    .kbf-badge-holding   { background: #fef3c7; color: #92400e; }
    .kbf-badge-released  { background: var(--kbf-green-lt); color: #14532d; }
    .kbf-badge-refunded  { background: var(--kbf-blue-lt); color: #1e3a8a; }
    .kbf-badge-open      { background: var(--kbf-red-lt); color: #7f1d1d; }
    .kbf-badge-reviewed  { background: #fef3c7; color: #92400e; }
    .kbf-badge-dismissed { background: var(--kbf-slate-lt); color: #334155; }
    .kbf-badge-verified  { background: var(--kbf-navy); color: #fff; }

    /* Buttons */
    .kbf-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: all .16s; text-decoration: none; line-height: 1; }
    .kbf-btn:disabled { opacity: .55; cursor: not-allowed; }
    .kbf-btn-primary   { background: var(--kbf-text); color: #fff; }
    .kbf-btn-primary:hover:not(:disabled) { background: #0b1220; }
    .kbf-btn-accent    { background: var(--kbf-accent); color: #043222; }
    .kbf-btn-accent:hover:not(:disabled) { background: #0aa06e; }
    .kbf-btn-secondary { background: var(--kbf-surface); color: var(--kbf-text); border: 1.5px solid var(--kbf-border); }
    .kbf-btn-secondary:hover:not(:disabled) { border-color: #cbd5e1; background: #fafafa; }
    .kbf-btn-danger    { background: var(--kbf-red-lt); color: var(--kbf-red); border: 1.5px solid #fecaca; }
    .kbf-btn-danger:hover:not(:disabled) { background: var(--kbf-red); color: #fff; }
    .kbf-btn-success   { background: var(--kbf-green-lt); color: var(--kbf-green); border: 1.5px solid #86efac; }
    .kbf-btn-success:hover:not(:disabled) { background: var(--kbf-green); color: #fff; }
    .kbf-btn-sm { padding: 6px 13px; font-size: 12px; }
    .kbf-btn-group { display: flex; gap: 8px; flex-wrap: wrap; }
    .kbf-action-bar { display: flex; gap: 10px; flex-wrap: wrap; margin: 10px 0 16px; }
    .kbf-action-card { background: var(--kbf-surface); border: 1px solid var(--kbf-border); border-radius: 14px; padding: 14px 16px; display: flex; align-items: center; gap: 10px; box-shadow: var(--kbf-shadow); }
    .kbf-action-card strong { font-size: 14px; }
    .kbf-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: var(--kbf-slate-lt); color: var(--kbf-text-sm); }
    .kbf-section-sub { font-size: 13px; color: var(--kbf-text-sm); margin-top: 6px; }
    /* Forms */
    .kbf-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .kbf-form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .kbf-form-group { margin-bottom: 16px; }
    .kbf-form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--kbf-text-sm); margin-bottom: 6px; }
    .kbf-form-group input,
    .kbf-form-group select,
    .kbf-form-group textarea { width: 100%; padding: 9px 12px; border: 1.5px solid var(--kbf-border); border-radius: 7px; font-size: 13.5px; color: var(--kbf-text); background: #fff; transition: border-color .15s; }
    .kbf-form-group input:focus,
    .kbf-form-group select:focus,
    .kbf-form-group textarea:focus { outline: none; border-color: var(--kbf-navy-light); box-shadow: 0 0 0 3px rgba(36,59,120,.12); }
    .kbf-form-group small { display: block; color: var(--kbf-slate); font-size: 11.5px; margin-top: 4px; }
    .kbf-checkbox-row { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--kbf-text-sm); }
    .kbf-checkbox-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--kbf-navy); }

    /* Modals */
    .kbf-modal-overlay { position: fixed; inset: 0; background: rgba(15,32,68,.55); display: flex; align-items: center; justify-content: center; z-index: 99999; backdrop-filter: blur(3px); }
    .kbf-modal { background: #fff; border-radius: 14px; width: 94%; max-width: 660px; max-height: 92vh; overflow-y: auto; box-shadow: var(--kbf-shadow-lg); display: flex; flex-direction: column; }
    .kbf-modal-sm { max-width: 460px; }
    .kbf-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px 16px; border-bottom: 1px solid var(--kbf-border); background: #0b1220; border-radius: 14px 14px 0 0; }
    .kbf-modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #fff; font-family: 'Fraunces', serif; }
    .kbf-modal-close { background: rgba(255,255,255,.15); border: none; color: #fff; width: 28px; height: 28px; border-radius: 50%; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; transition: background .15s; }
    .kbf-modal-close:hover { background: rgba(255,255,255,.28); }
    .kbf-modal-body { padding: 24px; flex: 1; }
    .kbf-modal-footer { padding: 16px 24px; border-top: 1px solid var(--kbf-border); background: var(--kbf-slate-lt); border-radius: 0 0 14px 14px; display: flex; justify-content: flex-end; gap: 10px; }

    /* Tables */
    .kbf-table-wrap { overflow-x: auto; border-radius: var(--kbf-radius); border: 1px solid var(--kbf-border); }
    .kbf-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .kbf-table thead th { background: #0b1220; color: rgba(255,255,255,.9); font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px; text-align: left; }
    .kbf-table tbody td { padding: 11px 14px; border-bottom: 1px solid var(--kbf-border); vertical-align: middle; }
    .kbf-table tbody tr:last-child td { border-bottom: none; }
    .kbf-table tbody tr:hover td { background: var(--kbf-slate-lt); }

    /* Alerts */
    .kbf-alert { padding: 11px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; margin: 8px 0; }
    .kbf-alert-success { background: var(--kbf-green-lt); color: #14532d; border: 1px solid #86efac; }
    .kbf-alert-error   { background: var(--kbf-red-lt); color: #7f1d1d; border: 1px solid #fca5a5; }
    .kbf-alert-info    { background: var(--kbf-blue-lt); color: #1e3a8a; border: 1px solid #93c5fd; }
    .kbf-alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }

    /* Notices & helpers */
    .kbf-empty { text-align: center; padding: 48px 20px; color: var(--kbf-slate); }
    .kbf-empty svg { display: block; margin: 0 auto 12px; opacity: .4; }
    .kbf-divider { border: none; border-top: 1px solid var(--kbf-border); margin: 20px 0; }
    .kbf-meta { font-size: 12px; color: var(--kbf-slate); }
    .kbf-fund-amounts { display: flex; gap: 20px; margin: 10px 0; }
    .kbf-fund-amounts span strong { display: block; font-size: 17px; font-weight: 800; color: var(--kbf-navy); }
    .kbf-fund-amounts span { font-size: 12px; color: var(--kbf-slate); }
    .kbf-verified-badge { display: inline-flex; align-items: center; gap: 4px; background: var(--kbf-navy); color: #fff; padding: 2px 9px; border-radius: 99px; font-size: 10.5px; font-weight: 700; vertical-align: middle; margin-left: 6px; }
    .kbf-verified-badge svg { width: 11px; height: 11px; }
    .kbf-payment-placeholder { background: #fffbeb; border: 1.5px dashed #fbbf24; border-radius: 8px; padding: 14px 16px; font-size: 12.5px; color: #92400e; margin: 12px 0; }
    .kbf-payment-placeholder strong { display: block; margin-bottom: 4px; font-size: 13px; }
    .kbf-notif-placeholder { background: #eff6ff; border: 1.5px dashed #60a5fa; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #1e3a8a; margin: 8px 0; }
    .kbf-star { color: #fbbf24; }
    .kbf-star-empty { color: #d1d5db; }

    /* Page header */
    .kbf-page-header { background: radial-gradient(1200px 200px at 0% 0%, #eef5ff 0%, #ffffff 55%, #ffffff 100%); border: 1px solid var(--kbf-border); border-radius: 20px; padding: 26px 28px; margin-bottom: 18px; color: var(--kbf-text); box-shadow: var(--kbf-shadow); }
    .kbf-page-header h2 { margin: 0 0 4px; font-size: 22px; font-weight: 800; font-family: 'Poppins', system-ui, -apple-system, sans-serif; }
    .kbf-page-header p  { margin: 0; color: var(--kbf-text-sm); font-size: 14px; }

    @media(max-width:640px) {
        .kbf-form-row, .kbf-form-row-3 { grid-template-columns: 1fr; }
        .kbf-stats { grid-template-columns: 1fr 1fr; }
        .kbf-tab-content { padding: 16px; }
        .kbf-modal-body { padding: 16px; }
    }
    /* Share modal */
    .kbf-share-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;}
    .kbf-share-box{background:#fff;border-radius:14px;padding:28px;max-width:420px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);}
    .kbf-share-url-row{display:flex;gap:8px;margin:16px 0;}
    .kbf-share-url-input{flex:1;padding:10px 14px;border:1.5px solid var(--kbf-border);border-radius:8px;font-size:13px;color:var(--kbf-text);background:var(--kbf-slate-lt);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .kbf-share-platforms{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:4px;}
    .kbf-share-platform{display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border:1.5px solid var(--kbf-border);border-radius:10px;cursor:pointer;text-decoration:none;font-size:11.5px;font-weight:600;color:var(--kbf-navy);transition:all .15s;background:#fff;}
    .kbf-share-platform:hover{border-color:var(--kbf-navy);background:var(--kbf-slate-lt);}
    @media(max-width:520px){.kbf-share-platforms{grid-template-columns:repeat(2,1fr);}}
    </style>
    <script>
    if (typeof window.kbfSetBtnLoading === 'undefined') {
        window.kbfSetBtnLoading = function(btn, on, label) {
            if (!btn) return;
            if (on) {
                btn.dataset.kbfLabel = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = label || 'Loading...';
            } else {
                btn.disabled = false;
                if (btn.dataset.kbfLabel) btn.innerHTML = btn.dataset.kbfLabel;
            }
        };
    }
    if (typeof window.kbfSetSkeleton === 'undefined') {
        // Skeletons removed: keep a no-op to avoid breaking callers.
        window.kbfSetSkeleton = function(el, on) { return; };
    }
    if (typeof window.kbfFetchJson === 'undefined') {
        window.kbfFetchJson = function(url, fd, onOk, onErr) {
            fetch(url, {method:'POST', body:fd})
            .then(r=>r.text())
            .then(t=>{
                try { onOk(JSON.parse(t)); }
                catch(e){ onErr('Invalid server response. ' + (t ? t.slice(0,200) : '')); }
            })
            .catch(err=>onErr(err && err.message ? err.message : 'Request failed.'));
        };
    }
    </script>

    <!-- Global Share Modal -->
    <div id="kbf-share-modal" class="kbf-share-modal-overlay" style="display:none;" onclick="if(event.target===this)kbfCloseShare()">
      <div class="kbf-share-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
          <h3 style="font-size:17px;font-weight:800;color:var(--kbf-navy);margin:0;">Share This Fund</h3>
          <button onclick="kbfCloseShare()" style="background:none;border:none;cursor:pointer;color:var(--kbf-slate);font-size:22px;line-height:1;padding:0;">&times;</button>
        </div>

        <p id="kbf-share-fund-title" style="font-size:13px;color:var(--kbf-slate);margin:0 0 4px;"></p>
        <div class="kbf-share-url-row">
          <input type="text" id="kbf-share-url-input" class="kbf-share-url-input" readonly>
          <button id="kbf-copy-btn" onclick="kbfCopyShareUrl()" class="kbf-btn kbf-btn-primary" style="padding:10px 16px;white-space:nowrap;flex-shrink:0;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
            Copy
          </button>
        </div>
        <div class="kbf-share-platforms">
          <a id="kbf-share-fb" href="#" target="_blank" class="kbf-share-platform" onclick="kbfSharePlatform('facebook');return false;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#1877f2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Facebook
          </a>
          <a id="kbf-share-x" href="#" target="_blank" class="kbf-share-platform" onclick="kbfSharePlatform('twitter');return false;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#111"><path d="M18.244 2H21.5l-7.147 8.168L22.5 22h-6.55l-5.14-6.71L4.9 22H1.644l7.64-8.735L1.5 2h6.72l4.65 6.02L18.244 2zm-1.15 18h1.803L7.08 4H5.147l11.947 16z"/></svg>
            X / Twitter
          </a>
          <a id="kbf-share-in" href="#" target="_blank" class="kbf-share-platform" onclick="kbfSharePlatform('linkedin');return false;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#0a66c2"><path d="M20.447 20.452H16.89v-5.569c0-1.327-.027-3.037-1.85-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.35V9h3.41v1.561h.048c.476-.9 1.637-1.85 3.368-1.85 3.602 0 4.266 2.368 4.266 5.451v6.29zM5.337 7.433c-1.144 0-2.07-.928-2.07-2.07 0-1.143.926-2.07 2.07-2.07 1.143 0 2.07.927 2.07 2.07 0 1.142-.927 2.07-2.07 2.07zM6.813 20.452H3.861V9h2.952v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.727v20.545C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.273V1.727C24 .774 23.2 0 22.222 0z"/></svg>
            LinkedIn
          </a>
          <a id="kbf-share-wa" href="#" target="_blank" class="kbf-share-platform" onclick="kbfSharePlatform('whatsapp');return false;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            WhatsApp
          </a>
          <a id="kbf-share-msg" href="#" class="kbf-share-platform" onclick="kbfSharePlatform('native');return false;">
            <svg width="22" height="22" fill="none" stroke="var(--kbf-navy)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            More
          </a>
        </div>
      </div>
    </div>

    <script>
    var _kbfShareUrl = '';
    var _kbfShareTitle = '';
    var _kbfShareDesc = '';
    var _kbfFundDetailsBase = '<?php echo esc_js(kbf_get_page_url("fund_details")); ?>';

    window.kbfOpenShare = function(token, title, desc) {
        _kbfShareTitle = title || 'Check out this fund';
        _kbfShareDesc  = desc || '';
        _kbfShareUrl   = _kbfFundDetailsBase + (_kbfFundDetailsBase.indexOf('?') >= 0 ? '&' : '?') + 'kbf_share=' + encodeURIComponent(token);
        const shareBody = _kbfShareDesc ? _kbfShareDesc : 'Every contribution helps this cause.';
        const shareText = 'Support this fundraiser: "' + _kbfShareTitle + '"\n\n' + shareBody + '\n\nLearn more here:\n' + _kbfShareUrl;
        document.getElementById('kbf-share-url-input').value = _kbfShareUrl;
        document.getElementById('kbf-share-fund-title').textContent = '"' + _kbfShareTitle + '"';
        document.getElementById('kbf-copy-btn').innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg> Copy';
        // Facebook: sharer pre-fills the URL in the post composer -- add quote param for caption text
        const fbQuote = 'Support this fundraiser: "' + _kbfShareTitle + '"' + (_kbfShareDesc ? '\n\n' + _kbfShareDesc : '');
        const fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(_kbfShareUrl) + '&quote=' + encodeURIComponent(fbQuote);
        const xUrl  = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent('Support this fundraiser: "' + _kbfShareTitle + '"\n\n' + shareBody) + '&url=' + encodeURIComponent(_kbfShareUrl);
        const inUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(_kbfShareUrl);
        // WhatsApp: full message text with link
        const waUrl = 'https://wa.me/?text=' + encodeURIComponent(shareText);
        document.getElementById('kbf-share-fb').href  = fbUrl;
        document.getElementById('kbf-share-x').href   = xUrl;
        document.getElementById('kbf-share-in').href  = inUrl;
        document.getElementById('kbf-share-wa').href  = waUrl;
        // Store for platform handler
        window._kbfFbUrl = fbUrl;
        window._kbfXUrl  = xUrl;
        window._kbfInUrl = inUrl;
        window._kbfWaUrl = waUrl;
        window._kbfShareText = shareText;
        document.getElementById('kbf-share-modal').style.display = 'flex';
    };
    window.kbfCloseShare = function() {
        document.getElementById('kbf-share-modal').style.display = 'none';
    };
    window.kbfCopyShareUrl = function() {
        const input = document.getElementById('kbf-share-url-input');
        const btn   = document.getElementById('kbf-copy-btn');
        if(navigator.clipboard) {
            navigator.clipboard.writeText(_kbfShareUrl).then(function() {
                btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied!';
                btn.style.background = 'var(--kbf-green)';
                setTimeout(function(){ btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg> Copy'; btn.style.background = ''; }, 2500);
            });
        } else {
            input.select();
            document.execCommand('copy');
            btn.textContent = 'Copied!';
        }
    };
    window.kbfSharePlatform = function(platform) {
        if(platform === 'facebook') {
            window.open(window._kbfFbUrl || ('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(_kbfShareUrl)), '_blank', 'width=620,height=500,left=200,top=100');
        } else if(platform === 'twitter') {
            window.open(window._kbfXUrl || ('https://twitter.com/intent/tweet?text=' + encodeURIComponent(_kbfShareTitle) + '&url=' + encodeURIComponent(_kbfShareUrl)), '_blank');
        } else if(platform === 'linkedin') {
            window.open(window._kbfInUrl || ('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(_kbfShareUrl)), '_blank');
        } else if(platform === 'whatsapp') {
            window.open(window._kbfWaUrl || ('https://wa.me/?text=' + encodeURIComponent(window._kbfShareText || (_kbfShareTitle + '\n' + _kbfShareUrl))), '_blank');
        } else if(platform === 'native') {
            if(navigator.share) {
                navigator.share({ title: _kbfShareTitle, text: (window._kbfShareText || _kbfShareTitle), url: _kbfShareUrl })
                .catch(()=>{});
            } else {
                kbfCopyShareUrl();
            }
        }
    };
    // Backward-compat aliases used in various places
    window.kbfShareFund      = function(token, title, desc) { kbfOpenShare(token, title || 'Support this fund on KonekBayan', desc); };
    window.kbffShareFund     = function(token, title, desc) { kbfOpenShare(token, title || 'Support this fund on KonekBayan', desc); };
    window.kbfShareFundDetail= function(token, title, desc) { kbfOpenShare(token, title || 'Support this fund on KonekBayan', desc); };

    // â”€â”€ NEAR ME: browser geolocation â†’ Nominatim reverse geocode â†’ fill location input â”€â”€
    window.kbfNearMe = function(inputId, formId) {
        const input = document.getElementById(inputId);
        const btn   = event && event.currentTarget ? event.currentTarget : document.getElementById('kbf-browse-nearme-btn');
        if (!navigator.geolocation) {
            alert('Your browser does not support geolocation. Please type your location manually.');
            return;
        }
        const origText = btn ? btn.innerHTML : '';
        if (btn) { btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg> Locating...'; btn.disabled = true; }
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                // Nominatim reverse geocode -- free, no API key required
                fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&zoom=10&accept-language=en', {
                    headers: { 'Accept-Language': 'en', 'User-Agent': 'KonekBayan/1.0' }
                })
                .then(r => r.json())
                .then(data => {
                    const a = data.address || {};
                    // Build a readable city/province string from Nominatim result
                    const city     = a.city || a.municipality || a.town || a.village || a.county || '';
                    const province = a.state || a.province || a.region || '';
                    const place    = city && province ? city + ', ' + province : (city || province || data.display_name.split(',').slice(0,2).join(',').trim());
                    if (input) { input.value = place; input.focus(); }
                    if (btn)   { btn.innerHTML = origText; btn.disabled = false; }
                    // Auto-submit the form
                    if (formId) { const f = document.getElementById(formId); if (f) f.submit(); }
                })
                .catch(() => {
                    if (btn) { btn.innerHTML = origText; btn.disabled = false; }
                    alert('Could not determine your location. Please type it manually.');
                });
            },
            function(err) {
                if (btn) { btn.innerHTML = origText; btn.disabled = false; }
                if (err.code === 1) alert('Location permission denied. Please allow location access or type your location manually.');
                else alert('Could not get your location. Please type it manually.');
            },
            { timeout: 10000 }
        );
    };
    // Find Funds tab alias
    window.kbffNearMe = function() { kbfNearMe('kbff-loc-input','kbff-search-form'); };
    </script>
    <?php
}


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

function kbf_withdrawal_status_label($status) {
    if ($status === 'released' || $status === 'approved') return 'Approved';
    if ($status === 'rejected') return 'Rejected';
    if ($status === 'pending') return 'Pending';
    return ucfirst((string)$status);
}

function kbf_withdrawal_badge_class($status) {
    if ($status === 'released' || $status === 'approved') return 'active';
    if ($status === 'rejected') return 'cancelled';
    if ($status === 'pending') return 'pending';
    return (string)$status;
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
    ];
    $shortcode = $shortcode_map[$page_key] ?? $page_key;
    // Try bntm framework page setting first
    $stored_url = bntm_get_setting('kbf_page_' . $page_key);
    if($stored_url) { $cache[$page_key] = $stored_url; return $stored_url; }
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
    global $wpdb;$t=$wpdb->prefix.'kbf_sponsorships';
    $wpdb->update($t,['payment_status'=>'refunded'],['fund_id'=>$fund_id,'payment_status'=>'completed'],['%s'],['%d','%s']);
    // =====================================================
    // NOTIFICATION PLACEHOLDER
    // TODO: Notify all sponsors about the refund here
    // do_action('kbf_refunds_triggered', $fund_id);
    // =====================================================
    error_log("[KonekBayan] Auto-refund triggered for fund #{$fund_id}");
}


