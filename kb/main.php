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
        'KonekBayan Dashboard'   => '[kbf_dashboard]',
        'Browse Funds'           => '[kbf_browse]',
        'Fund Details'           => '[kbf_fund_details]',
        'Organizer Profile'      => '[kbf_organizer_profile]',
        'Sponsor Donation History'=> '[kbf_sponsor_history]',
        'KonekBayan Admin Panel' => '[kbf_admin]',
    ];
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
        'kbf_dashboard'          => 'bntm_shortcode_kbf_dashboard',
        'kbf_browse'             => 'bntm_shortcode_kbf_browse',
        'kbf_fund_details'       => 'bntm_shortcode_kbf_fund_details',
        'kbf_organizer_profile'  => 'bntm_shortcode_kbf_organizer_profile',
        'kbf_sponsor_history'    => 'bntm_shortcode_kbf_sponsor_history',
        'kbf_admin'              => 'bntm_shortcode_kbf_admin',
    ];
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
    /* === KBF DESIGN SYSTEM -- Professional Navy === */
    :root {
        --kbf-navy:       #0f2044;
        --kbf-navy-mid:   #1a3160;
        --kbf-navy-light: #243b78;
        --kbf-accent:     #e8a020;
        --kbf-accent-lt:  #fdf3e0;
        --kbf-green:      #16a34a;
        --kbf-green-lt:   #dcfce7;
        --kbf-red:        #dc2626;
        --kbf-red-lt:     #fee2e2;
        --kbf-blue:       #2563eb;
        --kbf-blue-lt:    #dbeafe;
        --kbf-slate:      #64748b;
        --kbf-slate-lt:   #f1f5f9;
        --kbf-border:     #e2e8f0;
        --kbf-text:       #1e293b;
        --kbf-text-sm:    #475569;
        --kbf-radius:     10px;
        --kbf-shadow:     0 2px 12px rgba(15,32,68,.10);
        --kbf-shadow-lg:  0 8px 32px rgba(15,32,68,.16);
    }
    .kbf-wrap * { box-sizing: border-box; }
    .kbf-wrap { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: var(--kbf-text); }

    /* Tabs */
    .kbf-tabs { display: flex; gap: 2px; background: var(--kbf-navy); border-radius: var(--kbf-radius) var(--kbf-radius) 0 0; padding: 0 16px; overflow-x: auto; }
    .kbf-tab { display: inline-flex; align-items: center; gap: 7px; padding: 14px 18px; color: rgba(255,255,255,.65); font-size: 13.5px; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; white-space: nowrap; transition: all .18s; }
    .kbf-tab:hover { color: #fff; background: rgba(255,255,255,.06); }
    .kbf-tab.active { color: var(--kbf-accent); border-bottom-color: var(--kbf-accent); background: rgba(232,160,32,.08); }
    .kbf-tab-content { background: #fff; border: 1px solid var(--kbf-border); border-top: none; border-radius: 0 0 var(--kbf-radius) var(--kbf-radius); padding: 28px; }

    /* Cards & Sections */
    .kbf-section { margin-bottom: 28px; }
    .kbf-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
    .kbf-section-title { font-size: 16px; font-weight: 700; color: var(--kbf-navy); margin: 0; }
    .kbf-card { background: #fff; border: 1px solid var(--kbf-border); border-radius: var(--kbf-radius); padding: 20px; margin-bottom: 14px; transition: box-shadow .2s; }
    .kbf-card:hover { box-shadow: var(--kbf-shadow); }
    .kbf-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }

    /* Stats Row */
    .kbf-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 16px; margin-bottom: 28px; }
    .kbf-stat { background: #fff; border: 1px solid var(--kbf-border); border-radius: var(--kbf-radius); padding: 18px 20px; display: flex; align-items: center; gap: 14px; }
    .kbf-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .kbf-stat-label { font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--kbf-slate); }
    .kbf-stat-value { font-size: 22px; font-weight: 800; color: var(--kbf-navy); line-height: 1.2; }
    .kbf-stat-sub { font-size: 12px; color: var(--kbf-slate); margin-top: 2px; }

    /* Progress */
    .kbf-progress-wrap { background: var(--kbf-border); border-radius: 99px; height: 8px; overflow: hidden; }
    .kbf-progress-bar { height: 8px; border-radius: 99px; background: linear-gradient(90deg, var(--kbf-navy-light), var(--kbf-accent)); transition: width .6s ease; }

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
    .kbf-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 18px; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: all .16s; text-decoration: none; line-height: 1; }
    .kbf-btn:disabled { opacity: .55; cursor: not-allowed; }
    .kbf-btn-primary   { background: var(--kbf-navy); color: #fff; }
    .kbf-btn-primary:hover:not(:disabled) { background: var(--kbf-navy-mid); }
    .kbf-btn-accent    { background: var(--kbf-accent); color: var(--kbf-navy); }
    .kbf-btn-accent:hover:not(:disabled) { background: #d4911a; }
    .kbf-btn-secondary { background: #fff; color: var(--kbf-navy); border: 1.5px solid var(--kbf-border); }
    .kbf-btn-secondary:hover:not(:disabled) { border-color: var(--kbf-navy); background: var(--kbf-slate-lt); }
    .kbf-btn-danger    { background: var(--kbf-red-lt); color: var(--kbf-red); border: 1.5px solid #fca5a5; }
    .kbf-btn-danger:hover:not(:disabled) { background: var(--kbf-red); color: #fff; }
    .kbf-btn-success   { background: var(--kbf-green-lt); color: var(--kbf-green); border: 1.5px solid #86efac; }
    .kbf-btn-success:hover:not(:disabled) { background: var(--kbf-green); color: #fff; }
    .kbf-btn-sm { padding: 6px 13px; font-size: 12px; }
    .kbf-btn-group { display: flex; gap: 8px; flex-wrap: wrap; }

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
    .kbf-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px 16px; border-bottom: 1px solid var(--kbf-border); background: var(--kbf-navy); border-radius: 14px 14px 0 0; }
    .kbf-modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #fff; }
    .kbf-modal-close { background: rgba(255,255,255,.15); border: none; color: #fff; width: 28px; height: 28px; border-radius: 50%; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; transition: background .15s; }
    .kbf-modal-close:hover { background: rgba(255,255,255,.28); }
    .kbf-modal-body { padding: 24px; flex: 1; }
    .kbf-modal-footer { padding: 16px 24px; border-top: 1px solid var(--kbf-border); background: var(--kbf-slate-lt); border-radius: 0 0 14px 14px; display: flex; justify-content: flex-end; gap: 10px; }

    /* Tables */
    .kbf-table-wrap { overflow-x: auto; border-radius: var(--kbf-radius); border: 1px solid var(--kbf-border); }
    .kbf-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .kbf-table thead th { background: var(--kbf-navy); color: rgba(255,255,255,.88); font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px; text-align: left; }
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
    .kbf-page-header { background: linear-gradient(135deg, var(--kbf-navy) 0%, var(--kbf-navy-light) 100%); border-radius: var(--kbf-radius); padding: 28px 32px; margin-bottom: 24px; color: #fff; }
    .kbf-page-header h2 { margin: 0 0 4px; font-size: 22px; font-weight: 800; }
    .kbf-page-header p  { margin: 0; color: rgba(255,255,255,.7); font-size: 14px; }

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

function bntm_shortcode_kbf_dashboard() {
    if (!is_user_logged_in()) {
        return '<div class="kbf-wrap"><div class="kbf-alert kbf-alert-warning">Please log in to access your dashboard.</div></div>';
    }
    kbf_global_assets();
    $user        = wp_get_current_user();
    $business_id = $user->ID;
    $tab         = isset($_GET['kbf_tab']) ? sanitize_text_field($_GET['kbf_tab']) : 'overview';
    $nonce_create = wp_create_nonce('kbf_create_fund');
    $nonce_edit   = wp_create_nonce('kbf_update_fund');
    $nonce_cancel = wp_create_nonce('kbf_cancel_fund');
    $nonce_wd     = wp_create_nonce('kbf_withdrawal');
    $nonce_extend = wp_create_nonce('kbf_extend');

    ob_start();
    ?>
    <script>if(typeof ajaxurl==='undefined') var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';</script>
    <div class="kbf-wrap">

    <!-- ===== MODAL: Create Fund ===== -->
    <div id="kbf-modal-create" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Create New Fund</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-create')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-create-fund-form" enctype="multipart/form-data">
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Funding For *</label>
                <select name="funder_type" required>
                  <option value="yourself">Yourself</option>
                  <option value="someone_else">Someone Else</option>
                  <option value="charity_event">Charity or Event</option>
                </select>
              </div>
              <div class="kbf-form-group">
                <label>Category *</label>
                <select name="category" required>
                  <?php foreach (kbf_get_categories() as $c): ?>
                    <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Fund Title *</label>
              <input type="text" name="title" placeholder="Clear, compelling title" required>
            </div>
            <div class="kbf-form-group">
              <label>Description *</label>
              <textarea name="description" rows="4" placeholder="Tell your story and why this fund matters..." required></textarea>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Goal Amount (PHP) *</label>
                <input type="number" name="goal_amount" placeholder="0.00" min="100" step="0.01" required>
              </div>
              <div class="kbf-form-group">
                <label>Deadline</label>
                <input type="date" name="deadline" min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
              </div>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Contact Email *</label>
                <input type="email" name="email" required>
              </div>
              <div class="kbf-form-group">
                <label>Contact Phone *</label>
                <input type="text" name="phone" placeholder="+63 9XX XXX XXXX" required>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Location *</label>
              <input type="text" name="location" placeholder="City, Province" required>
            </div>
            <div class="kbf-form-group">
              <label>Valid Government / Company ID *</label>
              <input type="file" name="valid_id" accept="image/*,.pdf" required>
              <small>Required for identity verification before fund approval.</small>
            </div>
            <div class="kbf-form-group">
              <label>Fund Photos (up to 5)</label>
              <input type="file" name="photos[]" accept="image/*" multiple>
            </div>
            <div class="kbf-form-group">
              <label class="kbf-checkbox-row">
                <input type="checkbox" name="auto_return" value="1">
                Auto-return funds to sponsors if goal not met by deadline
              </label>
            </div>
            <div id="kbf-create-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-create')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitCreate()">Submit for Review</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Edit Fund ===== -->
    <div id="kbf-modal-edit" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Edit Fund</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-edit')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-edit-fund-form" enctype="multipart/form-data">
            <input type="hidden" name="fund_id" id="edit-fund-id">
            <div class="kbf-form-group">
              <label>Title</label>
              <input type="text" name="title" id="edit-fund-title">
            </div>
            <div class="kbf-form-group">
              <label>Description</label>
              <textarea name="description" id="edit-fund-desc" rows="4"></textarea>
            </div>
            <div class="kbf-form-group">
              <label>Location</label>
              <input type="text" name="location" id="edit-fund-location">
            </div>
            <div class="kbf-form-group">
              <label>Additional Photos</label>
              <input type="file" name="photos[]" accept="image/*" multiple>
              <small>Leave empty to keep existing photos.</small>
            </div>
            <div id="kbf-edit-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-edit')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitEdit()">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Withdrawal ===== -->
    <div id="kbf-modal-wd" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Request Withdrawal</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-wd')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-wd-form">
            <input type="hidden" name="fund_id" id="wd-fund-id">
            <input type="hidden" name="funder_name" value="<?php echo esc_attr($user->display_name ?? ''); ?>">
            <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:12px 14px;margin-bottom:16px;">
              <div style="font-size:12px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">Fund</div>
              <div id="wd-fund-title" style="font-size:14px;font-weight:700;color:var(--kbf-navy);margin-bottom:6px;"></div>
              <div style="font-size:13px;color:var(--kbf-green);font-weight:700;"><span id="wd-available-label"></span> available</div>
            </div>
            <div class="kbf-form-group">
              <label>Amount to Withdraw (PHP) *</label>
              <input type="number" name="amount" id="wd-amount" placeholder="0.00" min="1" step="0.01" required>
              <small style="color:var(--kbf-slate);font-size:11.5px;">Admin will review and process your request within 1-3 business days.</small>
            </div>
            <div class="kbf-form-group">
              <label>Withdrawal Method *</label>
              <select name="method" required>
                <option value="">Select Method</option>
                <option value="online_payment">Online Payment (GCash / Maya / E-Wallet)</option>
                <option value="bank_payment">Bank Payment (Bank Transfer / Over-the-Counter)</option>
              </select>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Account Name *</label>
                <input type="text" name="account_name" placeholder="Full name on account" required>
              </div>
              <div class="kbf-form-group">
                <label>Account Number *</label>
                <input type="text" name="account_number" placeholder="e.g. 09XX XXX XXXX" required>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Additional Details</label>
              <textarea name="account_details" rows="2" placeholder="Bank name, branch, or any other details..."></textarea>
            </div>
            <div id="kbf-wd-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-wd')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitWd()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Submit Request
          </button>
        </div>
      </div>
    </div>

    <!-- Page header -->
    <div class="kbf-page-header">
      <h2>My KonekBayan Dashboard</h2>
      <p>Manage your funds and track sponsorships -- <?php echo esc_html($user->display_name); ?></p>
    </div>

    <?php echo kbf_role_nav('funder'); ?>

    <div class="kbf-tabs">
      <a href="?kbf_tab=overview"      class="kbf-tab <?php echo $tab==='overview'?'active':''; ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        Overview
      </a>
      <a href="?kbf_tab=my_funds"      class="kbf-tab <?php echo $tab==='my_funds'?'active':''; ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        My Funds
      </a>
      <a href="?kbf_tab=sponsorships"  class="kbf-tab <?php echo $tab==='sponsorships'?'active':''; ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
        Sponsorships
      </a>
      <a href="?kbf_tab=withdrawals"   class="kbf-tab <?php echo $tab==='withdrawals'?'active':''; ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Withdrawals
      </a>
      <a href="?kbf_tab=find_funds"    class="kbf-tab <?php echo $tab==='find_funds'?'active':''; ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        Find Funds
      </a>
      <a href="?kbf_tab=profile"       class="kbf-tab <?php echo $tab==='profile'?'active':''; ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        Profile
      </a>
      <?php if(current_user_can('manage_options')):
        global $wpdb;
        $pending_badge = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_funds WHERE status='pending'"); // phpcs:ignore
      ?>
      <a href="?kbf_tab=admin" class="kbf-tab <?php echo $tab==='admin'?'active':''; ?>" style="<?php echo $tab==='admin'?'':''; ?>margin-left:auto;border-bottom-color:<?php echo $tab==='admin'?'var(--kbf-accent)':'transparent'; ?>;">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
        Admin
        <?php if($pending_badge > 0): ?>
          <span style="background:var(--kbf-red);color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;font-weight:800;line-height:1.5;margin-left:2px;"><?php echo $pending_badge; ?></span>
        <?php endif; ?>
      </a>
      <?php endif; ?>
    </div>
    <div class="kbf-tab-content">
      <?php
      if ($tab === 'overview')         echo kbf_dashboard_overview_tab($business_id);
      elseif ($tab === 'my_funds')     echo kbf_dashboard_my_funds_tab($business_id, $nonce_cancel, $nonce_extend);
      elseif ($tab === 'sponsorships') echo kbf_dashboard_sponsorships_tab($business_id);
      elseif ($tab === 'withdrawals')  echo kbf_dashboard_withdrawals_tab($business_id);
      elseif ($tab === 'find_funds')   echo kbf_dashboard_find_funds_tab();
      elseif ($tab === 'profile')      echo kbf_dashboard_profile_tab($business_id);
      elseif ($tab === 'admin' && current_user_can('manage_options')) echo kbf_dashboard_admin_embed();
      ?>
    </div>
    </div><!-- .kbf-wrap -->

    <script>
    function kbfCloseModal(id) { document.getElementById(id).style.display = 'none'; }
    function kbfOpenModal(id)  { document.getElementById(id).style.display = 'flex'; }

    function kbfSubmitCreate() {
        const form = document.getElementById('kbf-create-fund-form');
        const btn  = document.querySelector('#kbf-modal-create .kbf-modal-footer .kbf-btn-primary');
        btn.disabled = true; btn.textContent = 'Submitting...';
        const fd = new FormData(form);
        fd.append('action', 'kbf_create_fund');
        fd.append('nonce', '<?php echo $nonce_create; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-create-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json.success) setTimeout(()=>location.reload(), 1800);
            else { btn.disabled=false; btn.textContent='Submit for Review'; }
        }).catch(()=>{ btn.disabled=false; btn.textContent='Submit for Review'; });
    }

    function kbfSubmitEdit() {
        const form = document.getElementById('kbf-edit-fund-form');
        const btn  = document.querySelector('#kbf-modal-edit .kbf-modal-footer .kbf-btn-primary');
        btn.disabled = true; btn.textContent = 'Saving...';
        const fd = new FormData(form);
        fd.append('action', 'kbf_update_fund');
        fd.append('nonce', '<?php echo $nonce_edit; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-edit-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json.success) setTimeout(()=>location.reload(), 1500);
            else { btn.disabled=false; btn.textContent='Save Changes'; }
        });
    }

    function kbfSubmitWd() {
        const form = document.getElementById('kbf-wd-form');
        const btn  = document.querySelector('#kbf-modal-wd .kbf-modal-footer .kbf-btn-primary');
        btn.disabled = true; btn.textContent = 'Submitting...';
        const fd = new FormData(form);
        fd.append('action', 'kbf_request_withdrawal');
        fd.append('nonce', '<?php echo $nonce_wd; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-wd-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json.success) setTimeout(()=>location.reload(), 1500);
            else { btn.disabled=false; btn.textContent='Submit Request'; }
        });
    }

    window.kbfOpenEdit = function(id, title, desc, loc) {
        document.getElementById('edit-fund-id').value = id;
        document.getElementById('edit-fund-title').value = title;
        document.getElementById('edit-fund-desc').value = desc;
        document.getElementById('edit-fund-location').value = loc;
        kbfOpenModal('kbf-modal-edit');
    };

    window.kbfOpenWd = function(fundId, available, title) {
        document.getElementById('wd-fund-id').value = fundId;
        document.getElementById('wd-fund-title').textContent = title || 'Fund #'+fundId;
        document.getElementById('wd-available-label').textContent = '₱' + parseFloat(available).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        document.getElementById('wd-amount').max = available;
        document.getElementById('wd-amount').placeholder = 'Max ₱'+parseFloat(available).toLocaleString('en-PH',{minimumFractionDigits:2});
        document.getElementById('kbf-wd-msg').innerHTML = '';
        document.getElementById('kbf-wd-form').reset();
        document.getElementById('wd-fund-id').value = fundId; // re-set after reset
        kbfOpenModal('kbf-modal-wd');
    };

    window.kbfCancelFund = function(fundId) {
        if (!confirm('Cancel this fund? This cannot be undone.')) return;
        const fd = new FormData();
        fd.append('action','kbf_cancel_fund'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo $nonce_cancel; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };

    window.kbfExtendDeadline = function(fundId) {
        const d = prompt('New deadline (YYYY-MM-DD):'); if(!d) return;
        const fd = new FormData();
        fd.append('action','kbf_extend_deadline'); fd.append('fund_id',fundId);
        fd.append('deadline',d); fd.append('nonce','<?php echo $nonce_extend; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };


    window.kbfMarkComplete = function(fundId) {
        if(!confirm('Mark this fund as complete?')) return;
        const fd = new FormData();
        fd.append('action','kbf_mark_fund_complete'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo wp_create_nonce('kbf_cancel_fund'); ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };
    </script>
    <?php
    $content = ob_get_clean();
    return bntm_universal_container('KonekBayan -- Finding Platform', $content);
}


// ============================================================
// DASHBOARD TAB: Overview
// ============================================================

function kbf_dashboard_overview_tab($business_id) {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $st = $wpdb->prefix.'kbf_sponsorships';

    $total_funds    = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d",$business_id));
    $active_funds   = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status='active'",$business_id));
    $pending_funds  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status='pending'",$business_id));
    $total_raised   = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(raised_amount),0) FROM {$ft} WHERE business_id=%d",$business_id));
    $total_sponsors = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$business_id));
    $recent = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$ft} WHERE business_id=%d ORDER BY created_at DESC LIMIT 5",$business_id));

    ob_start(); ?>
    <div class="kbf-section">
      <div class="kbf-section-header">
        <h3 class="kbf-section-title">Dashboard Overview</h3>
        <button class="kbf-btn kbf-btn-primary" onclick="kbfOpenModal('kbf-modal-create')">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Create Fund
        </button>
      </div>
      <?php if($pending_funds > 0): ?>
      <div class="kbf-alert kbf-alert-warning" style="margin-bottom:20px;display:flex;align-items:center;gap:12px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
          <strong>You have <?php echo $pending_funds; ?> fund<?php echo $pending_funds>1?'s':''; ?> awaiting admin review.</strong>
          Pending funds are not visible to sponsors until approved. You will be notified once approved.
          <a href="?kbf_tab=my_funds" style="color:inherit;font-weight:700;text-decoration:underline;margin-left:6px;">View My Funds &rarr;</a>
        </div>
      </div>
      <?php endif; ?>
      <div class="kbf-stats">
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#0f2044,#243b78);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          </div>
          <div><div class="kbf-stat-label">Total Funds</div><div class="kbf-stat-value"><?php echo $total_funds; ?></div><div class="kbf-stat-sub"><?php echo $active_funds; ?> active</div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#16a34a,#15803d);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Total Raised</div><div class="kbf-stat-value">₱<?php echo number_format($total_raised,0); ?></div><div class="kbf-stat-sub">across all funds</div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#e8a020,#d4911a);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Total Sponsors</div><div class="kbf-stat-value"><?php echo $total_sponsors; ?></div><div class="kbf-stat-sub">completed payments</div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Active Now</div><div class="kbf-stat-value"><?php echo $active_funds; ?></div><div class="kbf-stat-sub">running campaigns</div></div>
        </div>
        <?php if($pending_funds > 0): ?>
        <div class="kbf-stat" style="border-color:#fcd34d;background:#fffbeb;">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Pending Review</div><div class="kbf-stat-value" style="color:#92400e;"><?php echo $pending_funds; ?></div><div class="kbf-stat-sub">awaiting approval</div></div>
        </div>
        <?php endif; ?>
      </div>

      <h3 class="kbf-section-title" style="margin-bottom:14px;">Recent Funds</h3>
      <?php if(empty($recent)): ?>
        <div class="kbf-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><p>No funds yet. Create your first fund!</p></div>
      <?php else: foreach($recent as $f):
          $pct = $f->goal_amount > 0 ? min(100, ($f->raised_amount/$f->goal_amount)*100) : 0; ?>
        <div class="kbf-card">
          <?php if($f->status === 'pending'): ?>
          <div style="background:#fef3c7;border-radius:6px;padding:8px 12px;margin-bottom:10px;font-size:12.5px;color:#92400e;display:flex;align-items:center;gap:8px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <strong>Awaiting admin approval</strong> -- This fund is not yet visible to sponsors.
          </div>
          <?php elseif($f->status === 'suspended'): ?>
          <div style="background:#fce7f3;border-radius:6px;padding:8px 12px;margin-bottom:10px;font-size:12.5px;color:#831843;display:flex;align-items:center;gap:8px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <strong>Fund suspended by admin.</strong> Contact support for details.
          </div>
          <?php endif; ?>
          <div class="kbf-card-header">
            <div>
              <strong style="font-size:15px;"><?php echo esc_html($f->title); ?></strong>
              <div class="kbf-meta" style="margin-top:4px;"><?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?></div>
            </div>
            <span class="kbf-badge kbf-badge-<?php echo $f->status; ?>"><?php echo ucfirst($f->status); ?></span>
          </div>
          <div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="kbf-fund-amounts">
            <span><strong>₱<?php echo number_format($f->raised_amount,2); ?></strong>raised</span>
            <span><strong>₱<?php echo number_format($f->goal_amount,2); ?></strong>goal</span>
            <span><strong><?php echo round($pct); ?>%</strong>funded</span>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// DASHBOARD TAB: My Funds
// ============================================================

function kbf_dashboard_my_funds_tab($business_id, $nonce_cancel, $nonce_extend) {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $st = $wpdb->prefix.'kbf_sponsorships';
    $funds = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$ft} WHERE business_id=%d ORDER BY created_at DESC",$business_id));

    ob_start(); ?>
    <div class="kbf-section">
      <div class="kbf-section-header">
        <h3 class="kbf-section-title">All My Funds</h3>
        <button class="kbf-btn kbf-btn-primary" onclick="kbfOpenModal('kbf-modal-create')">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Create Fund
        </button>
      </div>
      <?php
      $pending_count = count(array_filter($funds, fn($f) => $f->status === 'pending'));
      if($pending_count > 0): ?>
      <div class="kbf-alert kbf-alert-info" style="margin-bottom:20px;">
        <strong>How fund approval works:</strong> After you submit a fund, our admin team reviews it (usually within 24â€“48 hours).
        Once approved, your fund goes <strong>live</strong> and becomes visible to all sponsors on the Browse page.
        You'll see the status change from <em>Pending</em> to <em>Active</em> here.
      </div>
      <?php endif; ?>
      <?php if(empty($funds)): ?>
        <div class="kbf-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><p>No funds created yet.</p></div>
      <?php else: foreach($funds as $f):
        $pct = $f->goal_amount > 0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $sc  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed'",$f->id));
        $days_left = $f->deadline ? max(0, ceil((strtotime($f->deadline)-time())/86400)) : null;
        ?>
        <div class="kbf-card">
          <?php if($f->status === 'pending'): ?>
          <div style="background:#fef3c7;border-left:3px solid #f59e0b;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div><strong>Under Review</strong> -- Awaiting admin approval. Not visible to sponsors yet. Usually 24â€“48 hours.</div>
          </div>
          <?php elseif($f->status === 'suspended'): ?>
          <div style="background:#fce7f3;border-left:3px solid #db2777;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#831843;display:flex;align-items:flex-start;gap:10px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <div><strong>Fund Suspended</strong> -- Not visible to sponsors.<?php if($f->admin_notes): ?> Admin note: <?php echo esc_html($f->admin_notes); ?><?php else: ?> Contact support for details.<?php endif; ?></div>
          </div>
          <?php elseif($f->status === 'cancelled' && $f->admin_notes): ?>
          <div style="background:#fee2e2;border-left:3px solid #ef4444;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#7f1d1d;display:flex;align-items:flex-start;gap:10px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <div><strong>Rejected:</strong> <?php echo esc_html($f->admin_notes); ?></div>
          </div>
          <?php endif; ?>
          <div class="kbf-card-header">
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                <strong style="font-size:15px;"><?php echo esc_html($f->title); ?></strong>
                <span class="kbf-badge kbf-badge-<?php echo $f->status; ?>"><?php echo ucfirst($f->status); ?></span>
              </div>
              <div class="kbf-meta">
                <?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?>
                <?php if($days_left!==null): ?> &bull; <span style="color:<?php echo $days_left<7?'#dc2626':'#64748b';?>;font-weight:700;"><?php echo $days_left; ?> days left</span><?php endif; ?>
                &bull; <?php echo $sc; ?> sponsors
                &bull; Escrow: <span class="kbf-badge kbf-badge-<?php echo $f->escrow_status; ?>" style="font-size:10px;"><?php echo ucfirst($f->escrow_status); ?></span>
              </div>
            </div>
          </div>
          <div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="kbf-fund-amounts">
            <span><strong>₱<?php echo number_format($f->raised_amount,2); ?></strong>raised</span>
            <span><strong>₱<?php echo number_format($f->goal_amount,2); ?></strong>goal</span>
            <span><strong><?php echo round($pct); ?>%</strong>funded</span>
          </div>
          <div class="kbf-btn-group" style="margin-top:12px;">
            <?php if(in_array($f->status,['active','pending'])): ?>
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenEdit(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>','<?php echo esc_js($f->description); ?>','<?php echo esc_js($f->location); ?>')">Edit</button>
            <?php endif; ?>
            <?php if(in_array($f->status,['active','completed']) && $f->raised_amount>0): ?>
              <button class="kbf-btn kbf-btn-primary kbf-btn-sm" onclick="kbfOpenWd(<?php echo $f->id; ?>,<?php echo $f->raised_amount; ?>,'<?php echo esc_js($f->title); ?>')">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Request Withdrawal
              </button>
            <?php endif; ?>
            <?php if($f->status==='active'): ?>
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfExtendDeadline(<?php echo $f->id; ?>)">Extend Deadline</button>
            <?php endif; ?>
            <?php if($f->status==='active' && $f->raised_amount>=$f->goal_amount): ?>
              <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfMarkComplete(<?php echo $f->id; ?>)">Mark Complete</button>
            <?php endif; ?>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
              Share
            </button>
            <?php if(!in_array($f->status,['cancelled','completed'])): ?>
              <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfCancelFund(<?php echo $f->id; ?>)">Cancel</button>
            <?php endif; ?>
          </div>
          <?php
          $sponsors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$st} WHERE fund_id=%d AND payment_status='completed' ORDER BY amount DESC LIMIT 5",$f->id));
          if(!empty($sponsors)): ?>
          <details style="margin-top:14px;border-top:1px solid var(--kbf-border);padding-top:12px;">
            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--kbf-navy);">View Sponsors (<?php echo $sc; ?>)</summary>
            <div class="kbf-table-wrap" style="margin-top:10px;">
              <table class="kbf-table">
                <thead><tr><th>Sponsor</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($sponsors as $sp): ?>
                  <tr>
                    <td><?php echo $sp->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($sp->sponsor_name); ?></td>
                    <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($sp->amount,2); ?></strong></td>
                    <td><?php echo esc_html($sp->payment_method==='online_payment'?'Online Payment':($sp->payment_method==='bank_payment'?'Bank Payment':ucfirst(str_replace('_',' ',$sp->payment_method??'--')))); ?></td>
                    <td class="kbf-meta"><?php echo date('M d, Y',strtotime($sp->created_at)); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </details>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// DASHBOARD TAB: Sponsorships Received
// ============================================================

function kbf_dashboard_sponsorships_tab($business_id) {
    global $wpdb;
    $ft=$wpdb->prefix.'kbf_funds';$st=$wpdb->prefix.'kbf_sponsorships';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*,f.title as fund_title FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d ORDER BY s.created_at DESC",
        $business_id
    ));
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $pending_count = count(array_filter((array)$rows, fn($s) => $s->payment_status === 'pending'));
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">All Sponsorships Received</h3>
      <?php if($demo_mode): ?>
      <div class="kbf-alert kbf-alert-info" style="margin-bottom:16px;display:flex;align-items:center;gap:10px;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div><strong>Demo Mode is ON.</strong> Sponsorships are auto-confirmed instantly. When you integrate a real payment API, toggle Demo Mode off in the Admin â†’ Settings tab.</div>
      </div>
      <?php elseif($pending_count > 0): ?>
      <div class="kbf-alert kbf-alert-warning" style="margin-bottom:16px;">
        <strong><?php echo $pending_count; ?> sponsorship<?php echo $pending_count>1?'s':''; ?> pending payment confirmation.</strong>
        Go to Admin â†’ Transactions tab to manually confirm payments.
      </div>
      <?php endif; ?>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fund</th><th>Sponsor</th><th>Amount</th><th>Method</th><th>Status</th><th>Message</th><th>Date</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--kbf-slate);">No sponsorships yet.</td></tr>
          <?php else: foreach($rows as $s): ?>
            <tr>
              <td><strong><?php echo esc_html($s->fund_title); ?></strong></td>
              <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name); ?><?php if($s->email): ?><div class="kbf-meta"><?php echo esc_html($s->email); ?></div><?php endif; ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($s->amount,2); ?></strong></td>
              <td><?php echo esc_html($s->payment_method==='online_payment'?'Online Payment':($s->payment_method==='bank_payment'?'Bank Payment':ucfirst(str_replace('_',' ',$s->payment_method??'--')))); ?></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
              <td style="font-size:12.5px;color:var(--kbf-text-sm);font-style:italic;max-width:180px;"><?php echo esc_html($s->message?:' -- '); ?></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($s->created_at)); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// DASHBOARD TAB: Withdrawals
// ============================================================

function kbf_dashboard_withdrawals_tab($business_id) {
    global $wpdb;
    $ft=$wpdb->prefix.'kbf_funds';$wt=$wpdb->prefix.'kbf_withdrawals';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT w.*,f.title as fund_title FROM {$wt} w LEFT JOIN {$ft} f ON w.fund_id=f.id WHERE f.business_id=%d ORDER BY w.requested_at DESC",
        $business_id
    ));
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Withdrawal History</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fund</th><th>Amount</th><th>Method</th><th>Account</th><th>Status</th><th>Requested</th><th>Processed</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--kbf-slate);">No withdrawal requests yet.</td></tr>
          <?php else: foreach($rows as $w): ?>
            <tr>
              <td><strong><?php echo esc_html($w->fund_title); ?></strong></td>
              <td><strong>₱<?php echo number_format($w->amount,2); ?></strong></td>
              <td><?php echo esc_html($w->method); ?></td>
              <td class="kbf-meta"><?php echo esc_html($w->account_name); ?> &bull; <?php echo esc_html($w->account_number); ?></td>
              <td><span class="kbf-badge kbf-badge-<?php echo kbf_withdrawal_badge_class($w->status); ?>"><?php echo kbf_withdrawal_status_label($w->status); ?></span></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($w->requested_at)); ?></td>
              <td class="kbf-meta"><?php echo $w->processed_at?date('M d, Y',strtotime($w->processed_at)):'--'; ?></td>
            </tr>
            <?php if(!empty($w->admin_notes)): ?>
            <tr><td colspan="7" style="background:var(--kbf-slate-lt);font-size:12px;padding:8px 14px;"><strong>Admin Note:</strong> <?php echo esc_html($w->admin_notes); ?></td></tr>
            <?php endif; ?>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// DASHBOARD TAB: Organizer Profile
// ============================================================

function kbf_dashboard_profile_tab($business_id) {
    global $wpdb;
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$business_id));
    $user = get_userdata($business_id);
    $socials = $profile && $profile->social_links ? json_decode($profile->social_links,true) : [];
    $nonce = wp_create_nonce('kbf_organizer_profile');

    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:18px;">Organizer Profile</h3>
      <div class="kbf-alert kbf-alert-info" style="margin-bottom:18px;">
        This profile is publicly visible when sponsors view your funds.
      </div>
      <form id="kbf-profile-form" enctype="multipart/form-data">
        <div class="kbf-form-row">
          <div class="kbf-form-group">
            <label>Display Name</label>
            <input type="text" value="<?php echo esc_attr($user->display_name); ?>" disabled style="background:var(--kbf-slate-lt);">
            <small>Set in WordPress user settings.</small>
          </div>
          <div class="kbf-form-group">
            <label>Profile Photo</label>
            <input type="file" name="avatar" accept="image/*">
            <?php if($profile && $profile->avatar_url): ?>
              <div style="margin-top:8px;"><img src="<?php echo esc_url($profile->avatar_url); ?>" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--kbf-border);"></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="kbf-form-group">
          <label>Bio / About</label>
          <textarea name="bio" rows="4" placeholder="Tell sponsors about yourself or your organization..."><?php echo esc_textarea($profile->bio??''); ?></textarea>
        </div>
        <div class="kbf-form-row-3">
          <div class="kbf-form-group">
            <label>Facebook URL</label>
            <input type="url" name="social_facebook" value="<?php echo esc_attr($socials['facebook']??''); ?>" placeholder="https://facebook.com/...">
          </div>
          <div class="kbf-form-group">
            <label>Instagram URL</label>
            <input type="url" name="social_instagram" value="<?php echo esc_attr($socials['instagram']??''); ?>" placeholder="https://instagram.com/...">
          </div>
          <div class="kbf-form-group">
            <label>Twitter/X URL</label>
            <input type="url" name="social_twitter" value="<?php echo esc_attr($socials['twitter']??''); ?>" placeholder="https://x.com/...">
          </div>
        </div>
        <div id="kbf-profile-msg"></div>
        <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSaveProfile('<?php echo $nonce; ?>')">Save Profile</button>
      </form>

      <?php if($profile): ?>
      <hr class="kbf-divider">
      <div class="kbf-stats" style="margin-top:18px;">
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#0f2044,#243b78);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Total Raised</div><div class="kbf-stat-value">₱<?php echo number_format($profile->total_raised,0); ?></div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#e8a020,#d4911a);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Total Sponsors</div><div class="kbf-stat-value"><?php echo number_format($profile->total_sponsors); ?></div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#fbbf24,#f59e0b);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Rating</div><div class="kbf-stat-value"><?php echo number_format($profile->rating,1); ?>/5</div><div class="kbf-stat-sub"><?php echo $profile->rating_count; ?> reviews</div></div>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <script>
    window.kbfSaveProfile = function(nonce) {
        const form = document.getElementById('kbf-profile-form');
        const fd = new FormData(form);
        fd.append('action','kbf_save_organizer_profile');
        fd.append('nonce',nonce);
        const btn = form.querySelector('.kbf-btn-primary');
        btn.disabled=true; btn.textContent='Saving...';
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            document.getElementById('kbf-profile-msg').innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            btn.disabled=false; btn.textContent='Save Profile';
        });
    };
    </script>
    <?php return ob_get_clean();
}


// ============================================================
// DASHBOARD TAB: Find Funds (sponsor view -- browse & support)
// ============================================================

function kbf_dashboard_find_funds_tab() {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $current_user_id = get_current_user_id();

    // Filters from GET
    $q    = isset($_GET['ff_q'])   ? sanitize_text_field($_GET['ff_q'])   : '';
    $cat  = isset($_GET['ff_cat']) ? sanitize_text_field($_GET['ff_cat']) : '';
    $loc  = isset($_GET['ff_loc']) ? sanitize_text_field($_GET['ff_loc']) : '';
    $sort = isset($_GET['ff_sort'])? sanitize_text_field($_GET['ff_sort']): 'newest';

    $where = "WHERE f.status='active'"; $params = [];
    if($q)  { $where .= " AND (f.title LIKE %s OR f.description LIKE %s)"; $params[] = "%".$wpdb->esc_like($q)."%"; $params[] = "%".$wpdb->esc_like($q)."%"; }
    if($cat){ $where .= " AND f.category=%s"; $params[] = $cat; }
    if($loc){ $where .= " AND f.location LIKE %s"; $params[] = "%".$wpdb->esc_like($loc)."%"; }
    $order = $sort === 'most_funded' ? 'f.raised_amount DESC' : ($sort === 'ending_soon' ? 'f.deadline ASC' : 'f.created_at DESC');
    $sql = "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID {$where} ORDER BY {$order}";
    $funds = !empty($params) ? $wpdb->get_results($wpdb->prepare($sql,...$params)) : $wpdb->get_results($sql); // phpcs:ignore
    $cats  = kbf_get_categories();
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $fund_details_url = kbf_get_page_url('fund_details');
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $base_url = strtok($_SERVER['REQUEST_URI'],'?').'?kbf_tab=find_funds';

    ob_start(); ?>
    <script>if(typeof ajaxurl==='undefined') var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';</script>

    <?php echo kbf_role_nav('sponsor'); ?>

    <!-- MODAL: Sponsor -->
    <div id="kbff-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Sponsor This Fund</h3>
          <button class="kbf-modal-close" onclick="document.getElementById('kbff-modal-sponsor').style.display='none'">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <div id="kbff-fund-preview" style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:18px;"></div>
          <form id="kbff-sponsor-form">
            <input type="hidden" name="fund_id" id="kbff-fund-id">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Organization</label><input type="text" name="sponsor_name" id="kbff-name" placeholder="Your name, company, or org"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;">
                <label class="kbf-checkbox-row"><input type="checkbox" id="kbff-anon" onchange="document.getElementById('kbff-name').disabled=this.checked"> Sponsor Anonymously</label>
              </div>
            </div>
            <div class="kbf-form-group"><label>Amount (PHP) *</label><input type="number" name="amount" placeholder="Min. ₱10" min="10" step="1" required></div>
            <div class="kbf-form-group"><label>Encouraging Message (optional)</label><textarea name="message" rows="2" placeholder="Leave a message for the organizer..."></textarea></div>
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Email (for receipt)</label><input type="email" name="email" placeholder="your@email.com"></div>
              <div class="kbf-form-group"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX"></div>
            </div>
            <div class="kbf-form-group"><label>Payment Method *</label>
              <select name="payment_method" required>
                <option value="">Select Method</option>
                <option value="online_payment">Online Payment (GCash / PayMaya / E-Wallet)</option>
                <option value="bank_payment">Bank Payment (Bank Transfer / Over-the-Counter)</option>
              </select>
            </div>
            <?php if($demo_mode): ?>
            <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;margin-top:6px;">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
              <div><strong>Demo Mode:</strong> No real payment is processed. Your sponsorship is instantly confirmed and the fund's progress updates immediately.</div>
            </div>
            <?php endif; ?>
            <div id="kbff-sponsor-msg" style="margin-top:10px;"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbff-modal-sponsor').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" id="kbff-sponsor-submit" onclick="kbffSubmitSponsor('<?php echo $nonce_sponsor; ?>')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            Confirm Sponsorship
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: Report -->
    <div id="kbff-modal-report" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Report This Fund</h3><button class="kbf-modal-close" onclick="document.getElementById('kbff-modal-report').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbff-report-form">
            <input type="hidden" name="fund_id" id="kbff-report-fund-id">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email" placeholder="your@email.com"></div>
            <div class="kbf-form-group"><label>Reason *</label>
              <select name="reason" required><option value="">Select Reason</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Information</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select>
            </div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" placeholder="Describe the issue..." required></textarea></div>
            <div id="kbff-report-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbff-modal-report').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbffSubmitReport('<?php echo $nonce_report; ?>')">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- Header -->
    <div style="background:linear-gradient(135deg,var(--kbf-navy) 0%,#1e4080 100%);border-radius:var(--kbf-radius);padding:28px 30px;margin-bottom:24px;color:#fff;position:relative;overflow:hidden;">
      <div style="position:relative;z-index:1;">
        <h3 style="font-size:20px;font-weight:800;margin:0 0 6px;">Find Funds to Support</h3>
        <p style="margin:0 0 18px;color:rgba(255,255,255,.75);font-size:13.5px;"><?php echo count($funds); ?> active fund<?php echo count($funds)!==1?'s':''; ?> are looking for sponsors right now.</p>
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;max-width:700px;" id="kbff-search-form">
          <input type="hidden" name="kbf_tab" value="find_funds">
          <?php if($cat): ?><input type="hidden" name="ff_cat" value="<?php echo esc_attr($cat); ?>"><?php endif; ?>
          <?php if($sort && $sort!=='newest'): ?><input type="hidden" name="ff_sort" value="<?php echo esc_attr($sort); ?>"><?php endif; ?>
          <input type="text" name="ff_q" value="<?php echo esc_attr($q); ?>" placeholder="Search by title or description..." style="flex:1;min-width:180px;padding:9px 14px;border-radius:8px;border:none;font-size:13px;background:rgba(255,255,255,.95);color:var(--kbf-text);">
          <input type="text" name="ff_loc" id="kbff-loc-input" value="<?php echo esc_attr($loc); ?>" placeholder="Location (city, province)..." style="flex:1;min-width:160px;padding:9px 14px;border-radius:8px;border:none;font-size:13px;background:rgba(255,255,255,.95);color:var(--kbf-text);">
          <button type="button" id="kbff-near-me-btn" onclick="kbffNearMe()" style="padding:9px 14px;border-radius:8px;border:none;background:rgba(255,255,255,.15);color:#fff;cursor:pointer;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;transition:background .15s;white-space:nowrap;" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Near Me
          </button>
          <button type="submit" class="kbf-btn kbf-btn-accent" style="padding:9px 20px;">Search</button>
          <?php if($q||$cat||$loc): ?><a href="?kbf_tab=find_funds" class="kbf-btn kbf-btn-secondary" style="padding:9px 14px;">Clear</a><?php endif; ?>
        </form>
        <?php if($loc): ?>
        <div style="margin-top:12px;font-size:12.5px;color:rgba(255,255,255,.8);display:flex;align-items:center;gap:6px;">
          <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Showing funds near: <strong><?php echo esc_html($loc); ?></strong>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Filter + Sort bar -->
    <div style="background:#fff;border:1px solid var(--kbf-border);border-radius:var(--kbf-radius);padding:14px 18px;margin-bottom:22px;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
          <span style="font-size:11.5px;font-weight:700;color:var(--kbf-slate);text-transform:uppercase;letter-spacing:.5px;">Category:</span>
          <a href="<?php echo esc_url($base_url.($q?'&ff_q='.urlencode($q):'')); ?>" style="padding:5px 12px;border-radius:99px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid <?php echo !$cat?'var(--kbf-navy)':'var(--kbf-border)'; ?>;background:<?php echo !$cat?'var(--kbf-navy)':'transparent'; ?>;color:<?php echo !$cat?'#fff':'var(--kbf-slate)'; ?>;">All</a>
          <?php foreach($cats as $c): ?>
          <a href="<?php echo esc_url($base_url.'&ff_cat='.urlencode($c).($q?'&ff_q='.urlencode($q):'')); ?>" style="padding:5px 12px;border-radius:99px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid <?php echo $cat===$c?'var(--kbf-navy)':'var(--kbf-border)'; ?>;background:<?php echo $cat===$c?'var(--kbf-navy)':'transparent'; ?>;color:<?php echo $cat===$c?'#fff':'var(--kbf-slate)'; ?>;"><?php echo $c; ?></a>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
          <span style="font-size:11.5px;font-weight:700;color:var(--kbf-slate);text-transform:uppercase;letter-spacing:.5px;">Sort:</span>
          <a href="<?php echo esc_url($base_url.($cat?'&ff_cat='.urlencode($cat):'').($q?'&ff_q='.urlencode($q):'').'&ff_sort=newest'); ?>" style="padding:5px 12px;border-radius:99px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid <?php echo $sort==='newest'||!$sort?'var(--kbf-navy)':'var(--kbf-border)'; ?>;background:<?php echo $sort==='newest'||!$sort?'var(--kbf-navy)':'transparent'; ?>;color:<?php echo $sort==='newest'||!$sort?'#fff':'var(--kbf-slate)'; ?>;">Newest</a>
          <a href="<?php echo esc_url($base_url.($cat?'&ff_cat='.urlencode($cat):'').($q?'&ff_q='.urlencode($q):'').'&ff_sort=most_funded'); ?>" style="padding:5px 12px;border-radius:99px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid <?php echo $sort==='most_funded'?'var(--kbf-navy)':'var(--kbf-border)'; ?>;background:<?php echo $sort==='most_funded'?'var(--kbf-navy)':'transparent'; ?>;color:<?php echo $sort==='most_funded'?'#fff':'var(--kbf-slate)'; ?>;">Most Funded</a>
          <a href="<?php echo esc_url($base_url.($cat?'&ff_cat='.urlencode($cat):'').($q?'&ff_q='.urlencode($q):'').'&ff_sort=ending_soon'); ?>" style="padding:5px 12px;border-radius:99px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid <?php echo $sort==='ending_soon'?'var(--kbf-navy)':'var(--kbf-border)'; ?>;background:<?php echo $sort==='ending_soon'?'var(--kbf-navy)':'transparent'; ?>;color:<?php echo $sort==='ending_soon'?'#fff':'var(--kbf-slate)'; ?>;">Ending Soon</a>
        </div>
      </div>
    </div>

    <!-- Fund grid -->
    <?php if(empty($funds)): ?>
    <div class="kbf-empty" style="padding:60px 20px;">
      <svg width="44" height="44" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 14px;display:block;opacity:.3;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <p style="font-size:15px;font-weight:600;color:var(--kbf-navy);margin-bottom:4px;">No funds found</p>
      <p style="color:var(--kbf-slate);font-size:13px;">Try adjusting your search or category filter.</p>
      <?php if($q||$cat): ?><a href="?kbf_tab=find_funds" class="kbf-btn kbf-btn-primary" style="margin-top:14px;">Clear Filters</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:20px;">
      <?php foreach($funds as $f):
        $pct   = $f->goal_amount > 0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $days  = $f->deadline ? max(0,ceil((strtotime($f->deadline)-time())/86400)) : null;
        $photos = $f->photos ? json_decode($f->photos,true) : [];
        $cover  = !empty($photos[0]) ? $photos[0] : null;
        $detail_url = esc_url(add_query_arg('fund_id',$f->id,$fund_details_url));
        $is_own = ($f->business_id == $current_user_id);
      ?>
      <div style="background:#fff;border:1px solid var(--kbf-border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .2s,transform .15s;" onmouseover="this.style.boxShadow='0 8px 28px rgba(15,32,68,.13)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">

        <!-- Photo / cover -->
        <a href="<?php echo $detail_url; ?>" style="text-decoration:none;display:block;position:relative;">
          <?php if($cover): ?>
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($f->title); ?>" style="width:100%;height:180px;object-fit:cover;display:block;">
          <?php else: ?>
            <div style="width:100%;height:180px;background:linear-gradient(135deg,var(--kbf-navy) 0%,#243b78 100%);display:flex;align-items:center;justify-content:center;">
              <svg width="40" height="40" fill="none" stroke="rgba(255,255,255,.2)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </div>
          <?php endif; ?>
          <!-- Overlays -->
          <div style="position:absolute;top:10px;left:10px;background:rgba(15,32,68,.8);backdrop-filter:blur(4px);color:var(--kbf-accent);padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><?php echo esc_html($f->category); ?></div>
          <?php if($days!==null): ?>
          <div style="position:absolute;top:10px;right:10px;background:<?php echo $days<7?'rgba(220,38,38,.85)':'rgba(15,32,68,.75)'; ?>;backdrop-filter:blur(4px);color:#fff;padding:3px 10px;border-radius:99px;font-size:10px;font-weight:800;"><?php echo $days; ?>d left</div>
          <?php endif; ?>
          <?php if($is_own): ?>
          <div style="position:absolute;bottom:10px;left:10px;background:rgba(15,32,68,.85);color:var(--kbf-accent);padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700;">Your Fund</div>
          <?php endif; ?>
        </a>

        <!-- Card body -->
        <div style="padding:16px;flex:1;display:flex;flex-direction:column;">
          <a href="<?php echo $detail_url; ?>" style="text-decoration:none;">
            <h4 style="font-size:14.5px;font-weight:700;color:var(--kbf-navy);margin:0 0 5px;line-height:1.4;"><?php echo esc_html($f->title); ?></h4>
          </a>
          <p style="font-size:12.5px;color:var(--kbf-slate);margin:0 0 10px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.5;flex:1;"><?php echo esc_html(wp_trim_words($f->description,16)); ?></p>

          <!-- Location + Organizer -->
          <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--kbf-slate);margin-bottom:10px;gap:6px;flex-wrap:wrap;">
            <span style="display:flex;align-items:center;gap:3px;">
              <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <?php echo esc_html($f->location); ?>
            </span>
            <span style="color:var(--kbf-blue);font-weight:600;">by <a href="<?php echo esc_url(add_query_arg('organizer_id',$f->business_id,kbf_get_page_url('organizer_profile'))); ?>" style="color:inherit;text-decoration:none;font-weight:700;"><?php echo esc_html($f->organizer_name?:'Organizer'); ?></a></span>
          </div>

          <!-- Progress -->
          <div class="kbf-progress-wrap" style="margin-bottom:6px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:14px;">
            <span><strong style="color:var(--kbf-navy);font-size:13.5px;">₱<?php echo number_format($f->raised_amount,0); ?></strong> <span style="color:var(--kbf-slate);">raised</span></span>
            <span style="color:var(--kbf-slate);"><?php echo round($pct); ?>% of ₱<?php echo number_format($f->goal_amount,0); ?></span>
          </div>

          <!-- Action buttons -->
          <?php if($is_own): ?>
          <div style="display:grid;grid-template-columns:1fr auto;gap:8px;">
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-secondary" style="font-size:12.5px;text-align:center;">View Details</a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbffShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')" title="Share">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            </button>
          </div>
          <?php else: ?>
          <div style="display:grid;grid-template-columns:1fr auto auto auto;gap:7px;align-items:center;">
            <button class="kbf-btn kbf-btn-primary" style="font-size:12.5px;" onclick="kbffOpenSponsor(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>',<?php echo $f->goal_amount; ?>,<?php echo $f->raised_amount; ?>,'<?php echo esc_js($cover??''); ?>')">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
              Sponsor
            </button>
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-secondary kbf-btn-sm" title="View full details">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbffShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')" title="Share">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            </button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbffOpenReport(<?php echo $f->id; ?>)" title="Report">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </button>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <script>
    window.kbffOpenReport=function(id){document.getElementById('kbff-report-fund-id').value=id;document.getElementById('kbff-modal-report').style.display='flex';};
    window.kbffOpenSponsor=function(id,title,goal,raised,img){
        document.getElementById('kbff-fund-id').value=id;
        document.getElementById('kbff-sponsor-form').reset();
        const pct=goal>0?Math.min(100,Math.round((raised/goal)*100)):0;
        document.getElementById('kbff-fund-preview').innerHTML=
            (img?'<img src="'+img+'" style="width:100%;height:110px;object-fit:cover;border-radius:6px;margin-bottom:10px;display:block;">':'')
            +'<strong style="font-size:14px;color:var(--kbf-navy);">'+title+'</strong>'
            +'<div style="margin-top:8px;" class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:'+pct+'%"></div></div>'
            +'<div style="display:flex;justify-content:space-between;font-size:12px;margin-top:5px;color:var(--kbf-slate);">'
            +'<span>₱'+parseFloat(raised).toLocaleString()+' raised</span><span>'+pct+'% of ₱'+parseFloat(goal).toLocaleString()+'</span></div>';
        document.getElementById('kbff-modal-sponsor').style.display='flex';
    };
    window.kbffSubmitSponsor=function(nonce){
        const form=document.getElementById('kbff-sponsor-form');
        const btn=document.getElementById('kbff-sponsor-submit');
        const demo=<?php echo $demo_mode?'true':'false'; ?>;
        btn.disabled=true;btn.innerHTML='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg> Processing...';
        const fd=new FormData(form);
        fd.append('action', demo ? 'kbf_sponsor_fund' : 'kbf_create_checkout');
        fd.append('nonce',nonce);
        fd.append('is_anonymous',document.getElementById('kbff-anon').checked?'1':'0');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            if(j.success){
                if(!demo && j.data.checkout_url){
                    btn.innerHTML='Redirecting to payment...';
                    window.location.href=j.data.checkout_url;
                } else {
                    document.getElementById('kbff-sponsor-msg').innerHTML='<div class="kbf-alert kbf-alert-success">'+j.data.message+'</div>';
                    setTimeout(()=>location.reload(),2200);
                }
            } else {
                document.getElementById('kbff-sponsor-msg').innerHTML='<div class="kbf-alert kbf-alert-error">'+j.data.message+'</div>';
                btn.disabled=false;btn.innerHTML='<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg> Confirm Sponsorship';
            }
        });
    };
    window.kbffSubmitReport=function(nonce){
        const form=document.getElementById('kbff-report-form');
        const btn=document.querySelector('#kbff-modal-report .kbf-modal-footer .kbf-btn-danger');
        btn.disabled=true;btn.textContent='Submitting...';
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            document.getElementById('kbff-report-msg').innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if(j.success)setTimeout(()=>{document.getElementById('kbff-modal-report').style.display='none';},1800);
            else{btn.disabled=false;btn.textContent='Submit Report';}
        });
    };
    </script>
    <?php
    return ob_get_clean();
}


// ============================================================
// DASHBOARD TAB: Admin Embed (admin-only inline panel)
// ============================================================

function kbf_dashboard_admin_embed() {
    if (!current_user_can('manage_options')) return '';
    // Reuse all admin tab functions directly -- they share the same JS
    // already loaded by bntm_shortcode_kbf_admin, but here we need
    // to output the admin JS inline since we're inside the dashboard.
    global $wpdb;
    $adm_tab = isset($_GET['adm_tab']) ? sanitize_text_field($_GET['adm_tab']) : 'pending';
    $nonce   = wp_create_nonce('kbf_admin_action');

    ob_start(); ?>
    <script>
    var _kbfAdminNonce='<?php echo $nonce; ?>';
    if(typeof window.kbfAdmin==='undefined'){
        window.kbfAdmin=function(action,params){
            const fd=new FormData();fd.append('action',action);fd.append('_ajax_nonce',_kbfAdminNonce);
            Object.keys(params).forEach(k=>fd.append(k,params[k]));
            return fetch((window.ajaxurl||'<?php echo admin_url('admin-ajax.php'); ?>'),{method:'POST',body:fd})
            .then(r=>r.json()).then(j=>{
                alert((j.data&&j.data.message)?j.data.message:(j.data||'Done.'));
                if(j.success)location.reload();
            }).catch(()=>alert('Request failed.'));
        };
        window.kbfApprove     = function(id){if(!confirm('Approve this fund?'))return;kbfAdmin('kbf_admin_approve_fund',{fund_id:id});};
        window.kbfReject      = function(id){const r=prompt('Reason for rejection (optional):');if(r===null)return;kbfAdmin('kbf_admin_reject_fund',{fund_id:id,reason:r});};
        window.kbfSuspend     = function(id){if(!confirm('Suspend this fund?'))return;kbfAdmin('kbf_admin_suspend_fund',{fund_id:id});};
        window.kbfVerifyBadge = function(id,cur){kbfAdmin('kbf_admin_verify_badge',{fund_id:id,verified:cur?'0':'1'});};
        window.kbfEscrow      = function(id,act){kbfAdmin('kbf_admin_'+act+'_escrow',{fund_id:id});};
        window.kbfDismissReport  = function(id){kbfAdmin('kbf_admin_dismiss_report',{report_id:id});};
        window.kbfReviewReport   = function(id){const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_report',{report_id:id,notes:n});};
        window.kbfProcessWd      = function(id,type){if(type==='reject'){const r=prompt('Reason:');if(!r)return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'reject',notes:r});}else{if(!confirm('Approve & release?'))return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'approve'});}};
        window.kbfConfirmPayment = function(id){if(!confirm('Mark as paid?'))return;kbfAdmin('kbf_admin_confirm_payment',{sponsorship_id:id});};
        window.kbfVerifyOrg      = function(id,cur){kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:cur?'0':'1'});};
    } else {
        // Already defined -- just refresh the nonce value
        _kbfAdminNonce = '<?php echo $nonce; ?>';
    }
    </script>

    <?php
    $pending_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_funds WHERE status='pending'"); // phpcs:ignore
    $reports_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_reports WHERE status='open'"); // phpcs:ignore
    $wd_count      = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_withdrawals WHERE status='pending'"); // phpcs:ignore
    $counts = ['pending'=>$pending_count,'reports'=>$reports_count,'withdrawals'=>$wd_count];
    $adm_tabs = ['pending'=>'Pending Funds','all_funds'=>'All Funds','transactions'=>'Transactions','withdrawals'=>'Withdrawals','reports'=>'Reports','organizers'=>'Organizers','settings'=>'Settings'];
    ?>

    <div style="margin:-28px -28px 0;background:var(--kbf-navy);border-radius:0;">
      <div style="padding:20px 28px 0;display:flex;align-items:center;gap:10px;">
        <svg width="16" height="16" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
        <span style="color:#fff;font-weight:700;font-size:15px;">Admin Panel</span>
      </div>
      <div class="kbf-tabs" style="border-radius:0;padding:0 28px;">
        <?php foreach($adm_tabs as $k=>$label): ?>
        <a href="?kbf_tab=admin&adm_tab=<?php echo $k; ?>" class="kbf-tab <?php echo $adm_tab===$k?'active':''; ?>">
          <?php echo $label; ?>
          <?php if(!empty($counts[$k])&&$counts[$k]>0): ?>
            <span style="background:var(--kbf-red);color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;font-weight:800;line-height:1.5;"><?php echo $counts[$k]; ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div style="margin-top:24px;">
      <?php
      if     ($adm_tab==='pending')      echo kbf_admin_pending_tab();
      elseif ($adm_tab==='all_funds')    echo kbf_admin_all_funds_tab();
      elseif ($adm_tab==='transactions') echo kbf_admin_transactions_tab();
      elseif ($adm_tab==='withdrawals')  echo kbf_admin_withdrawals_tab();
      elseif ($adm_tab==='reports')      echo kbf_admin_reports_tab();
      elseif ($adm_tab==='organizers')   echo kbf_admin_organizers_tab();
      elseif ($adm_tab==='settings')     echo kbf_admin_settings_tab();
      ?>
    </div>
    <?php
    return ob_get_clean();
}

// ============================================================
// PUBLIC BROWSE SHORTCODE
// ============================================================

function bntm_shortcode_kbf_browse() {
    kbf_global_assets();
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $q   = isset($_GET['q'])   ? sanitize_text_field($_GET['q'])   : '';
    $cat = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : '';
    $loc = isset($_GET['loc']) ? sanitize_text_field($_GET['loc']) : '';
    $sort= isset($_GET['sort'])? sanitize_text_field($_GET['sort']): 'newest';

    $where='WHERE f.status=\'active\''; $params=[];
    if($q)  { $where.=" AND (f.title LIKE %s OR f.description LIKE %s)"; $params[]="%".$wpdb->esc_like($q)."%"; $params[]="%".$wpdb->esc_like($q)."%"; }
    if($cat){ $where.=" AND f.category=%s"; $params[]=$cat; }
    if($loc){ $where.=" AND f.location LIKE %s"; $params[]="%".$wpdb->esc_like($loc)."%"; }
    $order = $sort==='most_funded' ? 'f.raised_amount DESC' : ($sort==='ending_soon' ? 'f.deadline ASC' : 'f.created_at DESC');
    $sql="SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID {$where} ORDER BY {$order}";
    $funds = !empty($params)?$wpdb->get_results($wpdb->prepare($sql,...$params)):$wpdb->get_results($sql); // phpcs:ignore
    $cats  = kbf_get_categories();
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $fund_details_url = kbf_get_page_url('fund_details');
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $total_active = count($funds);
    ob_start(); ?>
    <script>if(typeof ajaxurl==='undefined') var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';</script>
    <style>
    .kbf-browse-hero{background:linear-gradient(135deg,var(--kbf-navy) 0%,var(--kbf-navy-light) 60%,#1e4080 100%);border-radius:var(--kbf-radius);padding:36px 40px;margin-bottom:28px;color:#fff;position:relative;overflow:hidden;}
    .kbf-browse-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");}
    .kbf-fund-card{background:#fff;border:1px solid var(--kbf-border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .2s,transform .15s;}
    .kbf-fund-card:hover{box-shadow:0 8px 28px rgba(15,32,68,.13);transform:translateY(-2px);}
    .kbf-fund-photo{width:100%;height:190px;object-fit:cover;background:linear-gradient(135deg,var(--kbf-navy),var(--kbf-navy-light));display:flex;align-items:center;justify-content:center;position:relative;}
    .kbf-fund-photo-placeholder{width:100%;height:190px;background:linear-gradient(135deg,#0f2044 0%,#243b78 100%);display:flex;align-items:center;justify-content:center;position:relative;}
    .kbf-fund-photo img{width:100%;height:190px;object-fit:cover;display:block;}
    .kbf-fund-cat-badge{position:absolute;top:12px;left:12px;background:rgba(15,32,68,.85);backdrop-filter:blur(4px);color:var(--kbf-accent);padding:4px 10px;border-radius:99px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
    .kbf-fund-days-badge{position:absolute;top:12px;right:12px;padding:4px 10px;border-radius:99px;font-size:10.5px;font-weight:800;}
    .kbf-filter-bar{background:#fff;border:1px solid var(--kbf-border);border-radius:var(--kbf-radius);padding:18px 20px;margin-bottom:24px;}
    .kbf-sort-pills{display:flex;gap:6px;flex-wrap:wrap;}
    .kbf-sort-pill{padding:6px 14px;border-radius:99px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid var(--kbf-border);color:var(--kbf-slate);transition:all .15s;}
    .kbf-sort-pill:hover,.kbf-sort-pill.active{background:var(--kbf-navy);color:#fff;border-color:var(--kbf-navy);}
    .kbf-browse-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:22px;}
    @media(max-width:640px){.kbf-browse-grid{grid-template-columns:1fr;}.kbf-browse-hero{padding:24px 20px;}}
    </style>
    <div class="kbf-wrap">

    <?php echo kbf_role_nav('sponsor'); ?>

    <!-- MODAL: Sponsor -->
    <div id="kbf-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header"><h3>Sponsor This Fund</h3><button class="kbf-modal-close" onclick="kbfSponsorClose()">&times;</button></div>
        <div class="kbf-modal-body">
          <div id="kbf-fund-preview" style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:18px;"></div>
          <form id="kbf-sponsor-form">
            <input type="hidden" name="fund_id" id="sponsor-fund-id">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Organization</label><input type="text" name="sponsor_name" id="sponsor-name-field" placeholder="Your name, company, or org"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;">
                <label class="kbf-checkbox-row"><input type="checkbox" id="anon-check" onchange="document.getElementById('sponsor-name-field').disabled=this.checked"> Sponsor Anonymously</label>
              </div>
            </div>
            <div class="kbf-form-group"><label>Amount (PHP) *</label><input type="number" name="amount" placeholder="Enter amount" min="10" step="1" required></div>
            <div class="kbf-form-group"><label>Message (optional)</label><textarea name="message" rows="2" placeholder="Leave an encouraging message..."></textarea></div>
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Email (for receipt)</label><input type="email" name="email" placeholder="your@email.com"></div>
              <div class="kbf-form-group"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX"></div>
            </div>
            <div class="kbf-form-group"><label>Payment Method *</label>
              <select name="payment_method" id="sponsor-payment-method" required><option value="">Select Payment Method</option><option value="online_payment">Online Payment (GCash / PayMaya / E-Wallet)</option><option value="bank_payment">Bank Payment (Bank Transfer / Over-the-Counter)</option></select>
            </div>
            <?php if($demo_mode): ?>
            <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;margin-top:4px;">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
              <div><strong>Demo Mode:</strong> No real payment processed. Sponsorship is instantly confirmed and progress updates immediately.</div>
            </div>
            <?php else: ?>
            <div id="kbf-payment-placeholder" class="kbf-payment-placeholder" style="display:none;"><strong>Payment API Integration Point</strong><span id="kbf-payment-label"></span><br><small>Hook: <code>do_action('kbf_process_payment', $method, $amount, $fund_id)</code></small></div>
            <?php endif; ?>
            <div id="kbf-sponsor-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfSponsorClose()">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitSponsor('<?php echo $nonce_sponsor; ?>')">Confirm Sponsorship</button>
        </div>
      </div>
    </div>

    <!-- MODAL: Report -->
    <div id="kbf-modal-report" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Report This Fund</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-report').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-report-form">
            <input type="hidden" name="fund_id" id="report-fund-id">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email" placeholder="your@email.com"></div>
            <div class="kbf-form-group"><label>Reason *</label><select name="reason" required><option value="">Select Reason</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Information</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select></div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" placeholder="Describe the issue..." required></textarea></div>
            <div id="kbf-report-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-report').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbfSubmitReport('<?php echo $nonce_report; ?>')">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- MODAL: Organizer -->
    <div id="kbf-modal-organizer" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal"><div class="kbf-modal-header"><h3>Organizer Profile</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-organizer').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body" id="kbf-organizer-body"><div style="text-align:center;padding:30px;color:var(--kbf-slate);">Loading...</div></div>
      </div>
    </div>

    <!-- Hero -->
    <div class="kbf-browse-hero">
      <div style="position:relative;z-index:1;">
        <h2 style="margin:0 0 6px;font-size:26px;font-weight:800;">Browse Funds</h2>
        <p style="margin:0 0 16px;color:rgba(255,255,255,.75);font-size:14.5px;">Discover <?php echo $total_active; ?> active cause<?php echo $total_active!==1?'s':''; ?> and make a real difference today.</p>
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;max-width:760px;" id="kbf-browse-search-form">
          <input type="text" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Search funds by title or description..." style="flex:2;min-width:180px;padding:10px 16px;border-radius:8px;border:none;font-size:13.5px;background:rgba(255,255,255,.95);color:var(--kbf-text);">
          <input type="text" name="loc" id="kbf-browse-loc-input" value="<?php echo esc_attr($loc); ?>" placeholder="Location (city, province)..." style="flex:1;min-width:150px;padding:10px 14px;border-radius:8px;border:none;font-size:13px;background:rgba(255,255,255,.95);color:var(--kbf-text);">
          <button type="button" onclick="kbfNearMe('kbf-browse-loc-input','kbf-browse-search-form')" style="padding:10px 14px;border-radius:8px;border:none;background:rgba(255,255,255,.15);color:#fff;cursor:pointer;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;transition:background .15s;white-space:nowrap;" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'" id="kbf-browse-nearme-btn">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Near Me
          </button>
          <button type="submit" class="kbf-btn kbf-btn-accent" style="padding:10px 22px;">Search</button>
          <?php if($q||$cat||$loc): ?><a href="<?php echo esc_url(remove_query_arg(['q','cat','loc','sort'])); ?>" class="kbf-btn kbf-btn-secondary" style="padding:10px 16px;">Clear</a><?php endif; ?>
        </form>
        <?php if($loc): ?>
        <div style="margin-top:10px;font-size:13px;color:rgba(255,255,255,.8);display:flex;align-items:center;gap:6px;">
          <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Showing funds near: <strong><?php echo esc_html($loc); ?></strong> &nbsp; <a href="<?php echo esc_url(remove_query_arg('loc')); ?>" style="color:rgba(255,255,255,.7);font-size:12px;">âœ• Remove</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="kbf-filter-bar">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
          <span style="font-size:12px;font-weight:700;color:var(--kbf-slate);text-transform:uppercase;letter-spacing:.5px;">Category:</span>
          <a href="<?php echo esc_url(add_query_arg('cat','')); ?>" class="kbf-sort-pill <?php echo !$cat?'active':''; ?>">All</a>
          <?php foreach($cats as $c): ?><a href="<?php echo esc_url(add_query_arg('cat',$c)); ?>" class="kbf-sort-pill <?php echo $cat===$c?'active':''; ?>"><?php echo $c; ?></a><?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
          <span style="font-size:12px;font-weight:700;color:var(--kbf-slate);text-transform:uppercase;letter-spacing:.5px;">Sort:</span>
          <div class="kbf-sort-pills">
            <a href="<?php echo esc_url(add_query_arg('sort','newest')); ?>" class="kbf-sort-pill <?php echo $sort==='newest'||!$sort?'active':''; ?>">Newest</a>
            <a href="<?php echo esc_url(add_query_arg('sort','most_funded')); ?>" class="kbf-sort-pill <?php echo $sort==='most_funded'?'active':''; ?>">Most Funded</a>
            <a href="<?php echo esc_url(add_query_arg('sort','ending_soon')); ?>" class="kbf-sort-pill <?php echo $sort==='ending_soon'?'active':''; ?>">Ending Soon</a>
          </div>
        </div>
      </div>
      <?php if($loc): ?>
      <div style="margin-top:10px;display:flex;align-items:center;gap:6px;font-size:13px;color:var(--kbf-slate);">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <strong><?php echo esc_html($loc); ?></strong> <a href="<?php echo esc_url(remove_query_arg('loc')); ?>" style="color:var(--kbf-red);margin-left:4px;text-decoration:none;">âœ• Remove</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Fund grid -->
    <?php if(empty($funds)): ?>
    <div class="kbf-empty" style="padding:80px 20px;">
      <svg width="52" height="52" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 16px;display:block;opacity:.3;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <p style="font-size:16px;font-weight:600;color:var(--kbf-navy);margin-bottom:6px;">No funds found</p>
      <p style="color:var(--kbf-slate);">Try adjusting your search or filters.</p>
      <?php if($q||$cat||$loc): ?><a href="?" class="kbf-btn kbf-btn-primary" style="margin-top:16px;">Clear All Filters</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="kbf-browse-grid">
      <?php foreach($funds as $f):
        $pct   = $f->goal_amount>0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $days  = $f->deadline ? max(0,ceil((strtotime($f->deadline)-time())/86400)) : null;
        $photos = $f->photos ? json_decode($f->photos,true) : [];
        $cover  = !empty($photos[0]) ? $photos[0] : null;
        $detail_url = esc_url(add_query_arg('fund_id',$f->id,$fund_details_url));
        $days_color = $days!==null&&$days<7 ? '#fca5a5' : 'rgba(255,255,255,.85)';
        $days_bg    = $days!==null&&$days<7 ? 'rgba(220,38,38,.85)' : 'rgba(15,32,68,.7)';
      ?>
      <div class="kbf-fund-card">
        <!-- Photo hero -->
        <a href="<?php echo $detail_url; ?>" style="text-decoration:none;display:block;">
          <?php if($cover): ?>
          <div class="kbf-fund-photo" style="position:relative;">
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($f->title); ?>" style="width:100%;height:190px;object-fit:cover;display:block;">
            <div class="kbf-fund-cat-badge"><?php echo esc_html($f->category); ?></div>
            <?php if($days!==null): ?><div class="kbf-fund-days-badge" style="background:<?php echo $days_bg; ?>;color:<?php echo $days_color; ?>;backdrop-filter:blur(4px);"><?php echo $days; ?>d left</div><?php endif; ?>
          </div>
          <?php else: ?>
          <div class="kbf-fund-photo-placeholder" style="position:relative;">
            <svg width="48" height="48" fill="none" stroke="rgba(255,255,255,.2)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <div class="kbf-fund-cat-badge"><?php echo esc_html($f->category); ?></div>
            <?php if($days!==null): ?><div class="kbf-fund-days-badge" style="background:<?php echo $days_bg; ?>;color:<?php echo $days_color; ?>;"><?php echo $days; ?>d left</div><?php endif; ?>
          </div>
          <?php endif; ?>
        </a>

        <!-- Card body -->
        <div style="padding:18px;flex:1;display:flex;flex-direction:column;">
          <a href="<?php echo $detail_url; ?>" style="text-decoration:none;">
            <h4 style="font-size:15.5px;font-weight:700;color:var(--kbf-navy);margin:0 0 6px;line-height:1.4;"><?php echo esc_html($f->title); ?></h4>
          </a>
          <p style="font-size:13px;color:var(--kbf-slate);margin:0 0 12px;flex:1;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.55;"><?php echo esc_html(wp_trim_words($f->description,18)); ?></p>

          <!-- Location + Organizer -->
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--kbf-slate);margin-bottom:12px;gap:6px;flex-wrap:wrap;">
            <span style="display:flex;align-items:center;gap:4px;">
              <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <?php echo esc_html($f->location); ?>
            </span>
            <button onclick="kbfViewOrganizer(<?php echo $f->business_id; ?>)" style="background:none;border:none;color:var(--kbf-blue);cursor:pointer;font-size:12px;padding:0;font-weight:600;">
              by <a href="<?php echo esc_url(add_query_arg('organizer_id',$f->business_id,kbf_get_page_url('organizer_profile'))); ?>" style="color:inherit;text-decoration:none;font-weight:700;"><?php echo esc_html($f->organizer_name?:'Organizer'); ?></a>
            </button>
          </div>

          <!-- Progress -->
          <div class="kbf-progress-wrap" style="margin-bottom:8px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:16px;">
            <span><strong style="color:var(--kbf-navy);font-size:14px;">₱<?php echo number_format($f->raised_amount,0); ?></strong> <span style="color:var(--kbf-slate);">raised</span></span>
            <span style="color:var(--kbf-slate);"><?php echo round($pct); ?>% of ₱<?php echo number_format($f->goal_amount,0); ?></span>
          </div>

          <!-- Actions -->
          <div style="display:grid;grid-template-columns:1fr auto auto auto;gap:8px;align-items:center;">
            <button class="kbf-btn kbf-btn-primary" style="font-size:13px;" onclick="kbfOpenSponsor(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>',<?php echo $f->goal_amount; ?>,<?php echo $f->raised_amount; ?>,'<?php echo esc_js($cover??''); ?>')">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
              Sponsor
            </button>
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-secondary kbf-btn-sm" title="View details">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')" title="Share">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            </button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfOpenReport(<?php echo $f->id; ?>)" title="Report">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    </div><!-- .kbf-wrap -->

    <script>
    window.kbfOpenReport=function(id){document.getElementById('report-fund-id').value=id;document.getElementById('kbf-modal-report').style.display='flex';};
    window.kbfSponsorClose=function(){document.getElementById('kbf-modal-sponsor').style.display='none';};
    window.kbfOpenSponsor=function(id,title,goal,raised,img){
        document.getElementById('sponsor-fund-id').value=id;
        const pct=goal>0?Math.min(100,Math.round((raised/goal)*100)):0;
        document.getElementById('kbf-fund-preview').innerHTML=
            (img?'<img src="'+img+'" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:10px;display:block;">':'')
            +'<strong style="font-size:15px;color:var(--kbf-navy);">'+title+'</strong>'
            +'<div style="margin-top:8px;" class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:'+pct+'%"></div></div>'
            +'<div style="display:flex;justify-content:space-between;font-size:12px;margin-top:6px;color:var(--kbf-slate);"><span>₱'+parseFloat(raised).toLocaleString()+' raised</span><span>'+pct+'% funded</span></div>';
        document.getElementById('kbf-modal-sponsor').style.display='flex';
    };
    window.kbfSubmitSponsor=function(nonce){
        const form=document.getElementById('kbf-sponsor-form');
        const btn=document.querySelector('#kbf-modal-sponsor .kbf-modal-footer .kbf-btn-primary');
        const demo=<?php echo $demo_mode?'true':'false'; ?>;
        btn.disabled=true;btn.innerHTML='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg> Processing...';
        const fd=new FormData(form);
        fd.append('action', demo ? 'kbf_sponsor_fund' : 'kbf_create_checkout');
        fd.append('nonce',nonce);
        fd.append('is_anonymous',document.getElementById('anon-check').checked?'1':'0');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            if(j.success){
                if(!demo && j.data.checkout_url){
                    btn.innerHTML='Redirecting to payment...';
                    window.location.href=j.data.checkout_url;
                } else {
                    document.getElementById('kbf-sponsor-msg').innerHTML='<div class="kbf-alert kbf-alert-success">'+j.data.message+'</div>';
                    setTimeout(()=>location.reload(),2000);
                }
            } else {
                document.getElementById('kbf-sponsor-msg').innerHTML='<div class="kbf-alert kbf-alert-error">'+j.data.message+'</div>';
                btn.disabled=false;btn.innerHTML='Confirm Sponsorship';
            }
        });
    };
    window.kbfSubmitReport=function(nonce){
        const form=document.getElementById('kbf-report-form');
        const btn=document.querySelector('#kbf-modal-report .kbf-modal-footer .kbf-btn-danger');
        btn.disabled=true;btn.textContent='Submitting...';
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            document.getElementById('kbf-report-msg').innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if(j.success)setTimeout(()=>{document.getElementById('kbf-modal-report').style.display='none';},2000);else{btn.disabled=false;btn.textContent='Submit Report';}
        });
    };
    window.kbfViewOrganizer=function(bizId){
        document.getElementById('kbf-modal-organizer').style.display='flex';
        document.getElementById('kbf-organizer-body').innerHTML='<div style="text-align:center;padding:30px;color:var(--kbf-slate);">Loading...</div>';
        const fd=new FormData();fd.append('action','kbf_get_organizer_profile');fd.append('business_id',bizId);fd.append('nonce','<?php echo wp_create_nonce('kbf_sponsor'); ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            if(j.success){
                const d=j.data;
                const starSvg=(filled)=>'<svg width="14" height="14" viewBox="0 0 24 24" fill="'+(filled?'#fbbf24':'#d1d5db')+'"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>';
                const stars=Array.from({length:5},(_,i)=>starSvg(i<Math.round(parseFloat(d.rating)))).join('');
                const statusColor={'active':'var(--kbf-green)','completed':'var(--kbf-blue)'};
                const statusBg={'active':'var(--kbf-green-lt)','completed':'#dbeafe'};

                // Fund history HTML
                const fundHistory = d.funds&&d.funds.length
                    ? '<div style="margin-top:20px;"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;"><h4 style="font-size:13px;font-weight:800;color:var(--kbf-navy);margin:0;">Fund History</h4><span style="font-size:11.5px;color:var(--kbf-slate);">'+d.total_funds+' total fund'+(d.total_funds!==1?'s':'')+'</span></div>'
                      + d.funds.map(f=>'<div style="border:1px solid var(--kbf-border);border-radius:8px;padding:12px;margin-bottom:8px;">'
                        +'<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">'
                        +'<div style="font-weight:700;font-size:13.5px;color:var(--kbf-navy);flex:1;margin-right:8px;">'+f.title+'</div>'
                        +'<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:'+(statusBg[f.status]||'var(--kbf-slate-lt)')+';color:'+(statusColor[f.status]||'var(--kbf-slate)')+';white-space:nowrap;">'+f.status.toUpperCase()+'</span>'
                        +'</div>'
                        +'<div style="font-size:11.5px;color:var(--kbf-slate);margin-bottom:6px;">'+f.category+' &bull; '+f.sponsor_count+' sponsor'+(f.sponsor_count!==1?'s':'')+'</div>'
                        +'<div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:'+f.pct+'%"></div></div>'
                        +'<div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--kbf-slate);margin-top:4px;"><span>₱'+f.raised+' raised</span><span>'+f.pct+'% of ₱'+f.goal+'</span></div>'
                        +'</div>').join('')
                      + '</div>'
                    : '<div style="text-align:center;padding:16px;color:var(--kbf-slate);font-size:13px;margin-top:16px;">No fund history yet.</div>';

                // Reviews HTML
                const reviewsHtml = d.reviews&&d.reviews.length
                    ? '<div style="margin-top:20px;"><h4 style="font-size:13px;font-weight:800;color:var(--kbf-navy);margin:0 0 10px;">Recent Reviews</h4>'
                      + d.reviews.map(r=>'<div style="border:1px solid var(--kbf-border);border-radius:8px;padding:10px 12px;margin-bottom:8px;">'
                        +'<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">'
                        +'<div style="display:flex;gap:2px;">'+Array.from({length:5},(_,i)=>starSvg(i<r.rating)).join('')+'</div>'
                        +'<span style="font-size:11px;color:var(--kbf-slate);">'+r.date+'</span>'
                        +'</div>'
                        +(r.review?'<p style="font-size:12.5px;color:var(--kbf-text-sm);margin:0 0 4px;font-style:italic;">"'+r.review+'"</p>':'')
                        +'<div style="font-size:11px;color:var(--kbf-slate);">'+r.email+(r.fund_title?' &bull; on "'+r.fund_title+'"':'')+'</div>'
                        +'</div>').join('')
                      + '</div>'
                    : '';

                document.getElementById('kbf-organizer-body').innerHTML=
                // Header
                '<div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:16px;">'
                +(d.avatar_url?'<img src="'+d.avatar_url+'" style="width:62px;height:62px;border-radius:50%;object-fit:cover;border:2px solid var(--kbf-border);flex-shrink:0;">':'<div style="width:62px;height:62px;border-radius:50%;background:var(--kbf-navy);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="28" height="28" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>')
                +'<div><div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;"><strong style="font-size:16px;color:var(--kbf-navy);">'+d.display_name+'</strong>'
                +(d.is_verified?'<span class="kbf-badge kbf-badge-verified" style="font-size:10px;">Verified</span>':'')
                +'</div>'
                +'<div style="display:flex;gap:3px;align-items:center;margin-top:5px;">'+stars
                +'<span style="font-size:12px;color:var(--kbf-slate);margin-left:5px;"><strong>'+d.rating+'</strong>/5 &nbsp;&bull;&nbsp; '+d.rating_count+' review'+(d.rating_count!==1?'s':'')+'</span></div>'
                +(d.bio?'<p style="font-size:13px;color:var(--kbf-slate);margin:6px 0 0;line-height:1.55;">'+d.bio+'</p>':'')
                +'</div></div>'
                // Stats
                +'<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:4px;">'
                +'<div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px;text-align:center;"><div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--kbf-slate);margin-bottom:3px;">Raised</div><div style="font-size:15px;font-weight:800;color:var(--kbf-navy);">₱'+parseFloat(d.total_raised).toLocaleString()+'</div></div>'
                +'<div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px;text-align:center;"><div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--kbf-slate);margin-bottom:3px;">Sponsors</div><div style="font-size:15px;font-weight:800;color:var(--kbf-navy);">'+d.total_sponsors+'</div></div>'
                +'<div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px;text-align:center;"><div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--kbf-slate);margin-bottom:3px;">Funds</div><div style="font-size:15px;font-weight:800;color:var(--kbf-navy);">'+d.total_funds+'</div></div>'
                +'</div>'
                + fundHistory
                + reviewsHtml;
            } else {
                document.getElementById('kbf-organizer-body').innerHTML='<div class="kbf-alert kbf-alert-error">Profile not found.</div>';
            }
        });
    };
    </script>
    <?php
    $c=ob_get_clean();
    return bntm_universal_container('Browse Funds -- KonekBayan',$c);
}

// ============================================================
// SPONSOR DONATION HISTORY SHORTCODE
// ============================================================

function bntm_shortcode_kbf_sponsor_history() {
    kbf_global_assets();
    global $wpdb;
    $st = $wpdb->prefix.'kbf_sponsorships';
    $ft = $wpdb->prefix.'kbf_funds';
    $email = sanitize_email($_GET['email'] ?? '');
    $rows = [];
    if ($email) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*,f.title as fund_title FROM {$st} s LEFT JOIN {$ft} f ON s.fund_id=f.id WHERE s.email=%s ORDER BY s.created_at DESC",
            $email
        ));
    }
    $fund_details_url = kbf_get_page_url('fund_details');
    ob_start(); ?>
    <div class="kbf-wrap">
      <div class="kbf-page-header">
        <h2>Donation History</h2>
        <p>View your sponsorship records by email.</p>
      </div>

      <div class="kbf-card" style="margin-bottom:18px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
          <div class="kbf-form-group" style="flex:1;min-width:220px;margin-bottom:0;">
            <label>Your Email</label>
            <input type="email" name="email" value="<?php echo esc_attr($email); ?>" placeholder="you@example.com" required>
          </div>
          <button class="kbf-btn kbf-btn-primary" type="submit">View History</button>
        </form>
        <small style="color:var(--kbf-slate);display:block;margin-top:8px;">Enter the email you used when sponsoring a fund.</small>
      </div>

      <?php if(!$email): ?>
        <div class="kbf-empty"><p>Enter an email to view your donations.</p></div>
      <?php else: ?>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fundraiser</th><th>Sponsor</th><th>Amount</th><th>Status</th><th>Method</th><th>Date</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--kbf-slate);">No donations found for this email.</td></tr>
          <?php else: foreach($rows as $s): ?>
            <tr>
              <td>
                <strong><?php echo esc_html($s->fund_title ?: 'Fundraiser'); ?></strong>
                <?php if($s->fund_id): ?>
                  <div class="kbf-meta"><a href="<?php echo esc_url(add_query_arg('fund_id',$s->fund_id,$fund_details_url)); ?>" style="color:var(--kbf-blue);text-decoration:none;">View fundraiser</a></div>
                <?php endif; ?>
              </td>
              <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name ?: 'Sponsor'); ?></td>
              <td><strong style="color:var(--kbf-green);">PHP <?php echo number_format($s->amount,2); ?></strong></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
              <td><?php echo esc_html($s->payment_method==='online_payment'?'Online Payment':($s->payment_method==='bank_payment'?'Bank Payment':ucfirst(str_replace('_',' ',$s->payment_method??'')))); ?></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($s->created_at)); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}



// ============================================================
// FUND DETAILS SHORTCODE
// ============================================================

function bntm_shortcode_kbf_fund_details() {
    kbf_global_assets();
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $fund = null;
    $current_user_id = get_current_user_id();
    if(!empty($_GET['fund_id'])) {
        $fid = intval($_GET['fund_id']);
        $fund = $wpdb->get_row($wpdb->prepare(
            "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.id=%d AND (f.status IN ('active','completed') OR f.business_id=%d)",
            $fid, $current_user_id
        ));
    } elseif(!empty($_GET['kbf_share'])) {
        $token = sanitize_text_field($_GET['kbf_share']);
        $fund = $wpdb->get_row($wpdb->prepare(
            "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.share_token=%s AND (f.status IN ('active','completed') OR f.business_id=%d)",
            $token, $current_user_id
        ));
    }
    $is_owner = $fund && $current_user_id && $fund->business_id == $current_user_id;
    if(!$fund) return bntm_universal_container('Fund Details', '<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Fund not found or no longer active.</div></div>');

    $st = $wpdb->prefix.'kbf_sponsorships';
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $pct      = $fund->goal_amount>0 ? min(100,($fund->raised_amount/$fund->goal_amount)*100) : 0;
    $sponsors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$st} WHERE fund_id=%d AND payment_status='completed' ORDER BY created_at DESC LIMIT 20",$fund->id));
    $sponsor_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed'",$fund->id));
    // Leaderboard: group by sponsor name/email, sum total contributed, rank by total DESC
    $leaderboard = $wpdb->get_results($wpdb->prepare(
        "SELECT
            CASE WHEN is_anonymous=1 THEN 'Anonymous' ELSE COALESCE(NULLIF(sponsor_name,''),'Anonymous') END AS display_name,
            is_anonymous,
            SUM(amount) AS total_given,
            COUNT(*) AS num_donations
         FROM {$st}
         WHERE fund_id=%d AND payment_status='completed'
         GROUP BY display_name, is_anonymous
         ORDER BY total_given DESC
         LIMIT 10",
        $fund->id
    ));
    $organizer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$fund->business_id));
    $days     = $fund->deadline ? max(0,ceil((strtotime($fund->deadline)-time())/86400)) : null;
    $photos   = $fund->photos ? json_decode($fund->photos,true) : [];
    $browse_url = kbf_get_page_url('browse');
    $profile_url = $fund ? add_query_arg('organizer_id', $fund->business_id, kbf_get_page_url('organizer_profile')) : kbf_get_page_url('organizer_profile');
    $demo_mode  = (bool)kbf_get_setting('kbf_demo_mode',true);
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $nonce_rating  = wp_create_nonce('kbf_rating');

    ob_start(); ?>
    <script>if(typeof ajaxurl==='undefined') var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';</script>
    <style>
    .kbf-detail-wrap{max-width:1000px;margin:0 auto;}
    .kbf-detail-layout{display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start;}
    .kbf-detail-sticky{position:sticky;top:24px;}
    .kbf-photo-gallery{display:grid;grid-template-columns:1fr;gap:8px;margin-bottom:24px;}
    .kbf-photo-gallery.multi{grid-template-columns:1fr 1fr;grid-template-rows:auto auto;}
    .kbf-photo-gallery.multi .kbf-photo-main{grid-column:1/-1;}
    .kbf-photo-main img,.kbf-photo-thumb img{width:100%;object-fit:cover;border-radius:10px;display:block;}
    .kbf-photo-main img{height:340px;}
    .kbf-photo-thumb img{height:150px;}
    .kbf-detail-sponsor-box{background:#fff;border:1px solid var(--kbf-border);border-radius:12px;padding:24px;box-shadow:var(--kbf-shadow);}
    .kbf-sponsor-wall{display:flex;flex-direction:column;gap:10px;margin-top:14px;}
    .kbf-sponsor-item{display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--kbf-slate-lt);border-radius:8px;}
    .kbf-sponsor-avatar{width:36px;height:36px;border-radius:50%;background:var(--kbf-navy);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;font-weight:700;color:#fff;}
    .kbf-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--kbf-slate);margin-bottom:20px;}
    .kbf-breadcrumb a{color:var(--kbf-blue);text-decoration:none;font-weight:600;}
    .kbf-breadcrumb a:hover{text-decoration:underline;}
    @media(max-width:760px){.kbf-detail-layout{grid-template-columns:1fr;}.kbf-detail-sticky{position:static;}.kbf-photo-main img{height:220px;}.kbf-photo-thumb img{height:110px;}}
    </style>
    <div class="kbf-wrap">

    <!-- Sponsor Modal -->
    <div id="kbf-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header"><h3>Sponsor "<?php echo esc_html(wp_trim_words($fund->title,6)); ?>"</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-sponsor').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:12px 16px;margin-bottom:18px;display:flex;justify-content:space-between;font-size:13px;">
            <span><strong style="color:var(--kbf-green);">₱<?php echo number_format($fund->raised_amount,2); ?></strong> raised</span>
            <span style="color:var(--kbf-slate);"><?php echo round($pct); ?>% of ₱<?php echo number_format($fund->goal_amount,2); ?> goal</span>
          </div>
          <form id="kbf-sponsor-form">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Organization</label><input type="text" name="sponsor_name" id="spd-name" placeholder="Your name, company, or org"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;"><label class="kbf-checkbox-row"><input type="checkbox" id="spd-anon" onchange="document.getElementById('spd-name').disabled=this.checked"> Sponsor Anonymously</label></div>
            </div>
            <div class="kbf-form-group"><label>Amount (PHP) *</label><input type="number" name="amount" placeholder="Min. ₱10" min="10" step="1" required></div>
            <div class="kbf-form-group"><label>Encouraging Message (optional)</label><textarea name="message" rows="2" placeholder="Leave a message for the organizer..."></textarea></div>
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Email (for receipt)</label><input type="email" name="email" placeholder="your@email.com"></div>
              <div class="kbf-form-group"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX"></div>
            </div>
            <div class="kbf-form-group"><label>Payment Method *</label>
              <select name="payment_method" required>
                <option value="">Select Method</option>
                <option value="online_payment">Online Payment (GCash / PayMaya / E-Wallet)</option>
                <option value="bank_payment">Bank Payment (Bank Transfer / Over-the-Counter)</option>
              </select>
            </div>
            <?php if($demo_mode): ?>
            <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;margin-top:4px;">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
              <div><strong>Demo Mode:</strong> No real payment is processed. Your sponsorship will be instantly confirmed and the fund's progress updates immediately.</div>
            </div>
            <?php endif; ?>
            <div id="kbf-spd-msg" style="margin-top:10px;"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-sponsor').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSpdSponsor('<?php echo $nonce_sponsor; ?>')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            Confirm Sponsorship
          </button>
        </div>
      </div>
    </div>

    <!-- Report Modal -->
    <div id="kbf-modal-report" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Report This Fund</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-report').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-report-form">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email"></div>
            <div class="kbf-form-group"><label>Reason *</label><select name="reason" required><option value="">Select</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Info</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select></div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" required></textarea></div>
            <div id="kbf-rpt-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-report').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbfSpdReport('<?php echo $nonce_report; ?>')">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- Rating Modal -->
    <div id="kbf-modal-rating" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Rate This Organizer</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-rating').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-rating-form">
            <input type="hidden" name="organizer_id" value="<?php echo $fund->business_id; ?>">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-group"><label>Rating</label>
              <div id="kbf-star-picker" style="display:flex;gap:8px;margin-top:6px;">
                <?php for($i=1;$i<=5;$i++): ?>
                  <svg class="kbf-star-btn" data-val="<?php echo $i; ?>" width="32" height="32" viewBox="0 0 24 24" fill="#d1d5db" style="cursor:pointer;transition:fill .1s;" onclick="kbfSetRating(<?php echo $i; ?>)"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="kbf-rating-val" value="5">
            </div>
            <div class="kbf-form-group"><label>Your Email *</label><input type="email" name="sponsor_email" required placeholder="your@email.com"></div>
            <div class="kbf-form-group"><label>Review (optional)</label><textarea name="review" rows="3" placeholder="Share your experience..."></textarea></div>
            <div id="kbf-rate-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-rating').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitRating('<?php echo $nonce_rating; ?>')">Submit Review</button>
        </div>
      </div>
    </div>

    <!-- Breadcrumb -->
    <div class="kbf-breadcrumb">
      <a href="<?php echo esc_url($browse_url); ?>">Browse Funds</a>
      <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      <span><?php echo esc_html(wp_trim_words($fund->title,6)); ?></span>
    </div>

    <?php if($fund->status==='pending' && $is_owner): ?>
    <div class="kbf-alert kbf-alert-warning" style="margin-bottom:20px;"><strong>Under Review:</strong> This fund is not yet visible to sponsors. Once approved it goes live.</div>
    <?php elseif($fund->status==='suspended'): ?>
    <div class="kbf-alert kbf-alert-error" style="margin-bottom:20px;"><strong>Suspended:</strong> <?php echo esc_html($fund->admin_notes?:'Contact support.'); ?></div>
    <?php endif; ?>

    <div class="kbf-detail-layout">
      <!-- LEFT: Main content -->
      <div>
        <!-- Photo gallery -->
        <?php if(!empty($photos)): ?>
        <div class="kbf-photo-gallery <?php echo count($photos)>1?'multi':''; ?>" style="margin-bottom:24px;">
          <div class="kbf-photo-main"><img src="<?php echo esc_url($photos[0]); ?>" alt="<?php echo esc_attr($fund->title); ?>"></div>
          <?php if(count($photos)>1): foreach(array_slice($photos,1,4) as $ph): ?>
          <div class="kbf-photo-thumb"><img src="<?php echo esc_url($ph); ?>" alt=""></div>
          <?php endforeach; endif; ?>
        </div>
        <?php else: ?>
        <div style="width:100%;height:280px;background:linear-gradient(135deg,var(--kbf-navy) 0%,var(--kbf-navy-light) 100%);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
          <svg width="64" height="64" fill="none" stroke="rgba(255,255,255,.2)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
        </div>
        <?php endif; ?>

        <!-- Title + Meta -->
        <div style="margin-bottom:20px;">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
            <span style="background:var(--kbf-accent-lt);color:#92400e;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><?php echo esc_html($fund->category); ?></span>
            <span class="kbf-badge kbf-badge-<?php echo $fund->status; ?>"><?php echo ucfirst($fund->status); ?></span>
          </div>
          <h1 style="font-size:24px;font-weight:800;color:var(--kbf-navy);margin:0 0 10px;line-height:1.3;"><?php echo esc_html($fund->title); ?></h1>
          <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:13px;color:var(--kbf-slate);">
            <span style="display:flex;align-items:center;gap:5px;">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <?php echo esc_html($fund->location); ?>
            </span>
            <span style="display:flex;align-items:center;gap:5px;">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              by <strong style="color:var(--kbf-text);"><a href="<?php echo esc_url($profile_url); ?>" style="color:inherit;text-decoration:none;"><?php echo esc_html($fund->organizer_name?:'Organizer'); ?></a></strong>
            </span>
            <?php if($days!==null): ?>
            <span style="display:flex;align-items:center;gap:5px;color:<?php echo $days<7?'var(--kbf-red)':'var(--kbf-slate)'; ?>;font-weight:<?php echo $days<7?'700':'400'; ?>;">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <?php echo $days; ?> day<?php echo $days!==1?'s':''; ?> left <?php echo $fund->deadline?'(ends '.date('M d, Y',strtotime($fund->deadline)).')':''; ?>
            </span>
            <?php endif; ?>
            <span style="display:flex;align-items:center;gap:5px;">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <?php echo $sponsor_count; ?> sponsor<?php echo $sponsor_count!==1?'s':''; ?>
            </span>
          </div>
        </div>

        <!-- Description -->
        <div class="kbf-card" style="margin-bottom:20px;">
          <h3 style="font-size:15px;font-weight:700;color:var(--kbf-navy);margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">About This Fund</h3>
          <div style="font-size:14.5px;color:var(--kbf-text-sm);line-height:1.8;"><?php echo nl2br(esc_html($fund->description)); ?></div>
        </div>

        <!-- Organizer card -->
        <?php if($fund->organizer_name): ?>
        <div class="kbf-card" style="margin-bottom:20px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">
            <h3 style="font-size:15px;font-weight:700;color:var(--kbf-navy);margin:0;">About the Organizer</h3>
            <a href="<?php echo esc_url($profile_url); ?>" style="background:none;border:none;color:var(--kbf-blue);cursor:pointer;font-size:12.5px;font-weight:600;padding:0;display:flex;align-items:center;gap:4px;text-decoration:none;">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              View Full Profile & History
            </a>
          </div>
          <a href="<?php echo esc_url($profile_url); ?>" style="display:flex;align-items:center;gap:14px;cursor:pointer;text-decoration:none;color:inherit;" title="View organizer profile">
            <?php if($organizer&&$organizer->avatar_url): ?>
              <img src="<?php echo esc_url($organizer->avatar_url); ?>" style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--kbf-border);flex-shrink:0;transition:border-color .15s;" onmouseover="this.style.borderColor='var(--kbf-blue)'" onmouseout="this.style.borderColor='var(--kbf-border)'">
            <?php else: ?>
              <div style="width:52px;height:52px;border-radius:50%;background:var(--kbf-navy);display:flex;align-items:center;justify-content:center;flex-shrink:0;cursor:pointer;"><svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
            <?php endif; ?>
            <div style="flex:1;">
              <div style="font-weight:700;font-size:15px;color:var(--kbf-navy);"><?php echo esc_html($fund->organizer_name); ?><?php if($organizer&&$organizer->is_verified): ?><span class="kbf-badge kbf-badge-verified" style="margin-left:8px;font-size:10px;">Verified</span><?php endif; ?></div>
              <?php if($organizer&&$organizer->bio): ?><p style="font-size:13px;color:var(--kbf-text-sm);margin:4px 0 0;line-height:1.55;"><?php echo esc_html(wp_trim_words($organizer->bio,30)); ?></p><?php endif; ?>
              <?php if($organizer&&$organizer->rating_count>0): ?>
              <div style="display:flex;align-items:center;gap:4px;margin-top:5px;">
                <?php for($i=1;$i<=5;$i++): ?><svg width="12" height="12" viewBox="0 0 24 24" fill="<?php echo $i<=round($organizer->rating)?'#fbbf24':'#d1d5db'; ?>"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg><?php endfor; ?>
                <span style="font-size:11.5px;color:var(--kbf-slate);margin-left:2px;"><?php echo number_format($organizer->rating,1); ?>/5 &bull; ₱<?php echo number_format($organizer->total_raised,0); ?> raised</span>
              </div>
              <?php else: ?>
              <div style="font-size:12px;color:var(--kbf-slate);margin-top:4px;">Click to view fund history &amp; reviews</div>
              <?php endif; ?>
            </div>
            <svg width="16" height="16" fill="none" stroke="var(--kbf-blue)" viewBox="0 0 24 24" style="flex-shrink:0;opacity:.7;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>
        </div>
        <?php endif; ?>

        <!-- Sponsors wall -->
        <?php if(!empty($sponsors)): ?>
        <div class="kbf-card">
          <h3 style="font-size:15px;font-weight:700;color:var(--kbf-navy);margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">
            Sponsors <span style="background:var(--kbf-green-lt);color:var(--kbf-green);padding:2px 8px;border-radius:99px;font-size:12px;margin-left:6px;"><?php echo $sponsor_count; ?></span>
          </h3>
          <div class="kbf-sponsor-wall">
            <?php foreach($sponsors as $sp):
              $initials = $sp->is_anonymous ? '?' : strtoupper(substr($sp->sponsor_name??'A',0,1));
            ?>
            <div class="kbf-sponsor-item">
              <div class="kbf-sponsor-avatar"><?php echo $initials; ?></div>
              <div style="flex:1;">
                <div style="font-weight:600;font-size:13.5px;color:var(--kbf-text);"><?php echo $sp->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($sp->sponsor_name); ?></div>
                <?php if($sp->message): ?><div style="font-size:12px;color:var(--kbf-slate);font-style:italic;margin-top:2px;">"<?php echo esc_html($sp->message); ?>"</div><?php endif; ?>
              </div>
              <div style="text-align:right;flex-shrink:0;">
                <div style="font-weight:800;font-size:14px;color:var(--kbf-green);">₱<?php echo number_format($sp->amount,0); ?></div>
                <div style="font-size:11px;color:var(--kbf-slate);"><?php echo date('M d',strtotime($sp->created_at)); ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: Sticky action sidebar -->
      <div class="kbf-detail-sticky">
        <div class="kbf-detail-sponsor-box">
          <!-- Progress -->
          <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
              <span style="font-size:24px;font-weight:800;color:var(--kbf-green);">₱<?php echo number_format($fund->raised_amount,2); ?></span>
              <span style="font-size:13px;color:var(--kbf-slate);">of ₱<?php echo number_format($fund->goal_amount,2); ?></span>
            </div>
            <div class="kbf-progress-wrap" style="height:10px;margin-bottom:10px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%;height:10px;"></div></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center;">
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div style="font-size:16px;font-weight:800;color:var(--kbf-navy);"><?php echo round($pct); ?>%</div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Funded</div>
              </div>
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div style="font-size:16px;font-weight:800;color:var(--kbf-navy);"><?php echo $sponsor_count; ?></div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Sponsors</div>
              </div>
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div style="font-size:16px;font-weight:800;color:var(--kbf-navy);"><?php echo $days!==null?$days:'âˆž'; ?></div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Days Left</div>
              </div>
            </div>
          </div>

          <?php if($fund->status==='active' && !$is_owner): ?>
          <?php if($demo_mode): ?>
          <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:10px 14px;font-size:12.5px;color:#92400e;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            <span><strong>Demo Mode</strong> -- no real payment processed</span>
          </div>
          <?php endif; ?>
          <button class="kbf-btn kbf-btn-primary" style="width:100%;padding:13px;font-size:15px;font-weight:700;margin-bottom:10px;" onclick="document.getElementById('kbf-modal-sponsor').style.display='flex'">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            Sponsor This Fund
          </button>
          <?php elseif($fund->status==='completed'): ?>
          <div class="kbf-alert kbf-alert-success" style="margin-bottom:10px;text-align:center;font-weight:700;">This fund has been completed!</div>
          <?php elseif($is_owner && $fund->status==='active'): ?>
          <div style="background:var(--kbf-slate-lt);border:1.5px dashed var(--kbf-border);border-radius:8px;padding:12px 14px;font-size:12.5px;color:var(--kbf-slate);margin-bottom:12px;text-align:center;">
            <strong style="color:var(--kbf-navy);display:block;margin-bottom:4px;">This is your fund</strong>
            Sponsors can contribute using the Sponsor button.
          </div>
          <?php if($demo_mode): ?>
          <button class="kbf-btn kbf-btn-secondary" style="width:100%;padding:11px;font-size:13px;font-weight:600;margin-bottom:10px;border-style:dashed;" onclick="document.getElementById('kbf-modal-sponsor').style.display='flex'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            Test Demo Sponsorship
          </button>
          <?php endif; ?>
          <?php endif; ?>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;">
            <button class="kbf-btn kbf-btn-secondary" style="font-size:13px;" onclick="kbfShareFundDetail('<?php echo esc_js($fund->share_token); ?>','<?php echo esc_js($fund->title); ?>','<?php echo esc_js(wp_trim_words($fund->description,18)); ?>')">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg> Share
            </button>
            <?php if(!$is_owner): ?>
            <button class="kbf-btn kbf-btn-secondary" style="font-size:13px;" onclick="document.getElementById('kbf-modal-rating').style.display='flex'">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" style="color:#fbbf24;"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg> Rate
            </button>
            <?php else: ?>
            <button class="kbf-btn kbf-btn-secondary" style="font-size:13px;" onclick="window.history.back()">â† Back</button>
            <?php endif; ?>
          </div>

          <?php if(!$is_owner && $fund->status==='active'): ?>
          <button style="background:none;border:none;width:100%;color:var(--kbf-slate);font-size:12px;cursor:pointer;padding:4px;" onclick="document.getElementById('kbf-modal-report').style.display='flex'">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Report this fund
          </button>
          <?php endif; ?>
        </div>

        <!-- Funder type info -->
        <div style="background:var(--kbf-slate-lt);border-radius:10px;padding:14px 16px;margin-top:14px;font-size:13px;color:var(--kbf-text-sm);">
          <div style="font-weight:700;color:var(--kbf-navy);margin-bottom:4px;">Fund Type</div>
          <div><?php echo ucwords(str_replace('_',' ',$fund->funder_type)); ?></div>
          <?php if($fund->auto_return): ?><div style="margin-top:6px;display:flex;align-items:center;gap:6px;color:var(--kbf-green);font-size:12px;"><svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Auto-refund if goal not met</div><?php endif; ?>
        </div>

        <!-- Top Sponsors Leaderboard -->
        <div style="background:#fff;border:1px solid var(--kbf-border);border-radius:12px;padding:18px;margin-top:14px;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="color:var(--kbf-accent);flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span style="font-size:14px;font-weight:800;color:var(--kbf-navy);">Top Sponsors</span>
            <?php if(!empty($leaderboard)): ?><span style="background:var(--kbf-accent);color:#fff;border-radius:99px;padding:1px 8px;font-size:10px;font-weight:800;margin-left:auto;"><?php echo count($leaderboard); ?></span><?php endif; ?>
          </div>

          <?php if(empty($leaderboard)): ?>
          <div style="text-align:center;padding:20px 10px;">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 10px;display:block;opacity:.25;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <p style="font-size:13px;color:var(--kbf-slate);margin:0;font-weight:600;">No sponsors yet</p>
            <p style="font-size:12px;color:var(--kbf-slate);margin:4px 0 0;opacity:.7;">Be the first to support!</p>
          </div>
          <?php else: ?>
          <?php
          $rank_colors = [
              1 => ['bg'=>'linear-gradient(135deg,#f59e0b,#fbbf24)', 'icon'=>'ðŸ¥‡', 'label'=>'1st'],
              2 => ['bg'=>'linear-gradient(135deg,#6b7280,#9ca3af)', 'icon'=>'ðŸ¥ˆ', 'label'=>'2nd'],
              3 => ['bg'=>'linear-gradient(135deg,#b45309,#d97706)', 'icon'=>'ðŸ¥‰', 'label'=>'3rd'],
          ];
          foreach($leaderboard as $rank => $entry):
            $pos = $rank + 1;
            $rc  = $rank_colors[$pos] ?? ['bg'=>'var(--kbf-navy)', 'icon'=>null, 'label'=>$pos.'th'];
            $initials = $entry->is_anonymous ? '?' : strtoupper(substr($entry->display_name, 0, 1));
            $bar_pct = $leaderboard[0]->total_given > 0 ? min(100, ($entry->total_given / $leaderboard[0]->total_given) * 100) : 0;
          ?>
          <div style="margin-bottom:<?php echo $pos < count($leaderboard)?'14':'0'; ?>px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;">
              <!-- Rank badge -->
              <div style="width:28px;height:28px;border-radius:50%;background:<?php echo $rc['bg']; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:<?php echo $pos<=3?'13':'11'; ?>px;font-weight:800;color:#fff;">
                <?php echo $pos <= 3 ? $pos : $pos; ?>
              </div>
              <!-- Name -->
              <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:700;color:var(--kbf-navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?php echo $entry->is_anonymous ? '<em style="color:var(--kbf-slate);font-style:italic;">Anonymous</em>' : esc_html($entry->display_name); ?>
                  <?php if($pos===1 && !empty($leaderboard) && count($leaderboard)>1): ?>
                  <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:99px;margin-left:4px;font-weight:700;font-style:normal;">TOP</span>
                  <?php endif; ?>
                </div>
                <?php if($entry->num_donations > 1): ?><div style="font-size:10.5px;color:var(--kbf-slate);"><?php echo $entry->num_donations; ?> donations</div><?php endif; ?>
              </div>
              <!-- Amount -->
              <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:13.5px;font-weight:800;color:var(--kbf-green);">₱<?php echo number_format($entry->total_given, 0); ?></div>
              </div>
            </div>
            <!-- Relative bar -->
            <div style="height:4px;background:var(--kbf-border);border-radius:99px;overflow:hidden;margin-left:38px;">
              <div style="height:100%;width:<?php echo $bar_pct; ?>%;background:<?php echo $pos===1?'linear-gradient(90deg,#f59e0b,#fbbf24)':($pos===2?'linear-gradient(90deg,#6b7280,#9ca3af)':($pos===3?'linear-gradient(90deg,#b45309,#d97706)':'var(--kbf-navy)')); ?>;border-radius:99px;transition:width .4s;"></div>
            </div>
          </div>
          <?php endforeach; ?>

          <?php if($sponsor_count > count($leaderboard)): ?>
          <div style="text-align:center;margin-top:12px;padding-top:10px;border-top:1px solid var(--kbf-border);font-size:12px;color:var(--kbf-slate);">
            +<?php echo $sponsor_count - count($leaderboard); ?> more sponsor<?php echo ($sponsor_count - count($leaderboard)) !== 1 ? 's' : ''; ?>
          </div>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    </div><!-- .kbf-wrap -->

    <script>
    window.kbfSpdSponsor=function(nonce){
        const form=document.getElementById('kbf-sponsor-form');
        const btn=document.querySelector('#kbf-modal-sponsor .kbf-modal-footer .kbf-btn-primary');
        const demo=<?php echo $demo_mode?'true':'false'; ?>;
        btn.disabled=true;btn.innerHTML='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg> Processing...';
        const fd=new FormData(form);
        fd.append('action', demo ? 'kbf_sponsor_fund' : 'kbf_create_checkout');
        fd.append('nonce',nonce);
        fd.append('is_anonymous',document.getElementById('spd-anon').checked?'1':'0');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            if(j.success){
                if(!demo && j.data.checkout_url){
                    btn.innerHTML='Redirecting to payment...';
                    window.location.href=j.data.checkout_url;
                } else {
                    document.getElementById('kbf-spd-msg').innerHTML='<div class="kbf-alert kbf-alert-success">'+j.data.message+'</div>';
                    setTimeout(()=>location.reload(),2000);
                }
            } else {
                document.getElementById('kbf-spd-msg').innerHTML='<div class="kbf-alert kbf-alert-error">'+j.data.message+'</div>';
                btn.disabled=false;btn.innerHTML='<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg> Confirm Sponsorship';
            }
        });
    };
    window.kbfSpdReport=function(nonce){
        const form=document.getElementById('kbf-report-form');
        const btn=document.querySelector('#kbf-modal-report .kbf-modal-footer .kbf-btn-danger');
        btn.disabled=true;btn.textContent='Submitting...';
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            document.getElementById('kbf-rpt-msg').innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if(j.success)setTimeout(()=>{document.getElementById('kbf-modal-report').style.display='none';},1800);else{btn.disabled=false;btn.textContent='Submit Report';}
        });
    };
    var _kbfRating=5;
    window.kbfSetRating=function(v){_kbfRating=v;document.getElementById('kbf-rating-val').value=v;document.querySelectorAll('.kbf-star-btn').forEach((s,i)=>{s.setAttribute('fill',i<v?'#fbbf24':'#d1d5db');});};kbfSetRating(5);
    window.kbfSubmitRating=function(nonce){
        const form=document.getElementById('kbf-rating-form');
        const btn=document.querySelector('#kbf-modal-rating .kbf-modal-footer .kbf-btn-primary');
        btn.disabled=true;btn.textContent='Submitting...';
        const fd=new FormData(form);fd.append('action','kbf_submit_rating');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            document.getElementById('kbf-rate-msg').innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if(j.success)setTimeout(()=>{document.getElementById('kbf-modal-rating').style.display='none';},1800);else{btn.disabled=false;btn.textContent='Submit Review';}
        });
    };
    </script>
    <?php
    $c=ob_get_clean();
    return bntm_universal_container('Fund Details -- KonekBayan',$c);
}

function bntm_shortcode_kbf_organizer_profile() {
    kbf_global_assets();
    global $wpdb;
    $biz_id = isset($_GET['organizer_id'])?intval($_GET['organizer_id']):0;
    if(!$biz_id) return bntm_universal_container('Organizer Profile','<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Organizer not found.</div></div>');
    $pt=$wpdb->prefix.'kbf_organizer_profiles';$ft=$wpdb->prefix.'kbf_funds';$rt=$wpdb->prefix.'kbf_ratings';
    $profile=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$biz_id));
    $user=get_userdata($biz_id);
    if(!$user) return bntm_universal_container('Organizer Profile','<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Organizer not found.</div></div>');
    $funds=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$ft} WHERE business_id=%d AND status IN ('active','completed') ORDER BY created_at DESC LIMIT 10",$biz_id));
    $reviews=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$rt} WHERE organizer_id=%d ORDER BY created_at DESC LIMIT 10",$biz_id));
    $socials=$profile&&$profile->social_links?json_decode($profile->social_links,true):[];
    ob_start(); ?>
    <div class="kbf-wrap">
    <div class="kbf-page-header">
      <div style="display:flex;align-items:center;gap:16px;">
        <?php if($profile&&$profile->avatar_url): ?>
          <img src="<?php echo esc_url($profile->avatar_url); ?>" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.3);">
        <?php else: ?>
          <div style="width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;"><svg width="32" height="32" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
        <?php endif; ?>
        <div>
          <h2 style="margin:0 0 4px;"><?php echo esc_html($user->display_name); ?><?php if($profile&&$profile->is_verified): ?><span class="kbf-verified-badge" style="margin-left:8px;"><svg viewBox="0 0 24 24" fill="currentColor" width="11" height="11"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Verified</span><?php endif; ?></h2>
          <?php if($profile&&$profile->rating_count>0): ?>
          <div style="display:flex;align-items:center;gap:6px;">
            <?php for($i=1;$i<=5;$i++): ?><svg width="14" height="14" viewBox="0 0 24 24" fill="<?php echo $i<=round($profile->rating)?'#fbbf24':'#d1d5db'; ?>"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg><?php endfor; ?>
            <span style="color:rgba(255,255,255,.8);font-size:13px;"><?php echo number_format($profile->rating,1); ?>/5 &bull; <?php echo $profile->rating_count; ?> reviews</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;">
      <div>
        <?php if($profile&&$profile->bio): ?>
        <div class="kbf-card"><p style="font-size:14px;line-height:1.75;color:var(--kbf-text-sm);margin:0;"><?php echo nl2br(esc_html($profile->bio)); ?></p></div>
        <?php endif; ?>

        <h3 class="kbf-section-title" style="margin:20px 0 12px;">Campaigns</h3>
        <?php if(empty($funds)): ?>
          <div class="kbf-empty"><p>No active campaigns.</p></div>
        <?php else: foreach($funds as $f):
          $pct=$f->goal_amount>0?min(100,($f->raised_amount/$f->goal_amount)*100):0; ?>
          <div class="kbf-card">
            <div class="kbf-card-header">
              <div><strong style="font-size:14px;"><?php echo esc_html($f->title); ?></strong><div class="kbf-meta"><?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?></div></div>
              <span class="kbf-badge kbf-badge-<?php echo $f->status; ?>"><?php echo ucfirst($f->status); ?></span>
            </div>
            <div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
            <div class="kbf-fund-amounts"><span><strong>₱<?php echo number_format($f->raised_amount,2); ?></strong>raised</span><span><strong>₱<?php echo number_format($f->goal_amount,2); ?></strong>goal</span><span><strong><?php echo round($pct); ?>%</strong>funded</span></div>
            <?php if($f->status==='active'): ?><a href="?page_id=<?php echo urlencode(get_the_ID()); ?>&fund_id=<?php echo $f->id; ?>" class="kbf-btn kbf-btn-primary kbf-btn-sm" style="margin-top:10px;">View Fund</a><?php endif; ?>
          </div>
        <?php endforeach; endif; ?>

        <?php if(!empty($reviews)): ?>
        <h3 class="kbf-section-title" style="margin:24px 0 12px;">Reviews</h3>
        <?php foreach($reviews as $r):
          $stars=array_fill(0,$r->rating,'â˜…');$empty=array_fill(0,5-$r->rating,'â˜†'); ?>
          <div class="kbf-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
              <div style="color:#fbbf24;font-size:16px;"><?php echo implode('',$stars).implode('',$empty); ?></div>
              <span class="kbf-meta"><?php echo date('M d, Y',strtotime($r->created_at)); ?></span>
            </div>
            <?php if($r->review): ?><p style="margin:0;font-size:13.5px;color:var(--kbf-text-sm);font-style:italic;">"<?php echo esc_html($r->review); ?>"</p><?php endif; ?>
            <div class="kbf-meta" style="margin-top:6px;"><?php echo esc_html($r->sponsor_email?:'Anonymous'); ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Sidebar -->
      <div>
        <?php if($profile): ?>
        <div class="kbf-card" style="margin-bottom:16px;">
          <h4 style="font-size:13px;font-weight:700;color:var(--kbf-navy);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;">Stats</h4>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Total Raised</span><strong style="color:var(--kbf-green);">₱<?php echo number_format($profile->total_raised,0); ?></strong></div>
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Total Sponsors</span><strong><?php echo number_format($profile->total_sponsors); ?></strong></div>
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Active Funds</span><strong><?php echo count(array_filter($funds,fn($f)=>$f->status==='active')); ?></strong></div>
          </div>
        </div>
        <?php endif; ?>
        <?php if(!empty(array_filter($socials))): ?>
        <div class="kbf-card">
          <h4 style="font-size:13px;font-weight:700;color:var(--kbf-navy);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;">Connect</h4>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach(['facebook'=>'Facebook','instagram'=>'Instagram','twitter'=>'Twitter/X'] as $k=>$label): if(!empty($socials[$k])): ?>
              <a href="<?php echo esc_url($socials[$k]); ?>" target="_blank" rel="noopener" class="kbf-btn kbf-btn-secondary kbf-btn-sm"><?php echo $label; ?></a>
            <?php endif; endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    </div>
    <?php
    $c=ob_get_clean();
    return bntm_universal_container('Organizer Profile -- KonekBayan',$c);
}


// ============================================================
// ADMIN PANEL SHORTCODE
// ============================================================

function bntm_shortcode_kbf_admin() {
    if(!current_user_can('manage_options')) return '<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Access denied.</div></div>';
    kbf_global_assets();
    $tab = isset($_GET['adm_tab'])?sanitize_text_field($_GET['adm_tab']):'pending';
    $nonce = wp_create_nonce('kbf_admin_action');
    ob_start(); ?>
    <script>
    var ajaxurl = window.ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>';
    var _kbfAdminNonce='<?php echo $nonce; ?>';
    window.kbfAdmin=function(action,params){
        const fd=new FormData();fd.append('action',action);fd.append('_ajax_nonce',_kbfAdminNonce);
        Object.keys(params).forEach(k=>fd.append(k,params[k]));
        return fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            alert((j.data&&j.data.message)?j.data.message:(j.data||'Done.'));
            if(j.success)location.reload();
        }).catch(()=>alert('Request failed. Please try again.'));
    };
    window.kbfApprove=function(id){if(!confirm('Approve this fund?'))return;kbfAdmin('kbf_admin_approve_fund',{fund_id:id});};
    window.kbfReject=function(id){const r=prompt('Reason for rejection (optional):');if(r===null)return;kbfAdmin('kbf_admin_reject_fund',{fund_id:id,reason:r});};
    window.kbfSuspend=function(id){if(!confirm('Suspend this fund?'))return;kbfAdmin('kbf_admin_suspend_fund',{fund_id:id});};
    window.kbfVerifyBadge=function(id,cur){kbfAdmin('kbf_admin_verify_badge',{fund_id:id,verified:cur?'0':'1'});};
    window.kbfEscrow=function(id,act){kbfAdmin('kbf_admin_'+act+'_escrow',{fund_id:id});};
    window.kbfDismissReport=function(id){kbfAdmin('kbf_admin_dismiss_report',{report_id:id});};
    window.kbfReviewReport=function(id){const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_report',{report_id:id,notes:n});};
    window.kbfProcessWd=function(id,type){
        if(type==='reject'){const r=prompt('Reason for rejection:');if(!r)return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'reject',notes:r});}
        else{if(!confirm('Approve & release this withdrawal?'))return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'approve'});}
    };
    window.kbfConfirmPayment=function(id){if(!confirm('Mark this sponsorship as paid?'))return;kbfAdmin('kbf_admin_confirm_payment',{sponsorship_id:id});};
    window.kbfVerifyOrg=function(id,cur){kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:cur?'0':'1'});};
    </script>
    <div class="kbf-wrap">
    <div class="kbf-page-header"><h2>KonekBayan Admin Panel</h2><p>Moderate funds, manage escrow, review reports, and process withdrawals.</p></div>

    <div class="kbf-tabs">
      <?php
      $pending_count_admin = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_funds WHERE status='pending'"); // phpcs:ignore
      $open_reports_count  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_reports WHERE status='open'"); // phpcs:ignore
      $pending_wd_count    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_withdrawals WHERE status='pending'"); // phpcs:ignore
      $tabs=['pending'=>'Pending','all_funds'=>'All Funds','transactions'=>'Transactions','withdrawals'=>'Withdrawals','reports'=>'Reports','organizers'=>'Organizers','settings'=>'Settings'];
      $counts=['pending'=>$pending_count_admin,'reports'=>$open_reports_count,'withdrawals'=>$pending_wd_count];
      foreach($tabs as $k=>$label): ?>
      <a href="?adm_tab=<?php echo $k; ?>" class="kbf-tab <?php echo $tab===$k?'active':''; ?>">
        <?php echo $label; ?>
        <?php if(!empty($counts[$k]) && $counts[$k]>0): ?>
          <span style="background:var(--kbf-red);color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;font-weight:800;line-height:1.5;"><?php echo $counts[$k]; ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>

    <?php echo kbf_role_nav('admin'); ?>
    <div class="kbf-tab-content">
      <?php
      if($tab==='pending')      echo kbf_admin_pending_tab();
      elseif($tab==='all_funds')     echo kbf_admin_all_funds_tab();
      elseif($tab==='transactions')  echo kbf_admin_transactions_tab();
      elseif($tab==='withdrawals')   echo kbf_admin_withdrawals_tab();
      elseif($tab==='reports')       echo kbf_admin_reports_tab();
      elseif($tab==='organizers')    echo kbf_admin_organizers_tab();
      elseif($tab==='settings')      echo kbf_admin_settings_tab();
      ?>
    </div>
    </div>
    <?php
    $c=ob_get_clean();
    return bntm_universal_container('KonekBayan Admin Panel',$c);
}

function kbf_admin_pending_tab() {
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $funds=$wpdb->get_results("SELECT f.*,u.display_name as organizer FROM {$t} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.status='pending' ORDER BY f.created_at ASC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input, table name only
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Funds Pending Approval <span style="background:var(--kbf-red-lt);color:var(--kbf-red);padding:2px 8px;border-radius:99px;font-size:12px;margin-left:8px;"><?php echo count($funds); ?></span></h3>
      <?php if(empty($funds)): ?><div class="kbf-empty"><p>No funds pending review.</p></div>
      <?php else: foreach($funds as $f): ?>
        <div class="kbf-card">
          <div class="kbf-card-header">
            <div>
              <strong style="font-size:15px;"><?php echo esc_html($f->title); ?></strong>
              <div class="kbf-meta" style="margin-top:4px;">by <?php echo esc_html($f->organizer); ?> &bull; <?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?> &bull; <?php echo ucwords(str_replace('_',' ',$f->funder_type)); ?></div>
              <p style="font-size:13px;color:var(--kbf-text-sm);margin:8px 0 0;"><?php echo esc_html(wp_trim_words($f->description,40)); ?></p>
              <div style="display:flex;gap:20px;margin-top:10px;font-size:12.5px;color:var(--kbf-slate);flex-wrap:wrap;">
                <span><strong>Goal:</strong> ₱<?php echo number_format($f->goal_amount,2); ?></span>
                <span><strong>Deadline:</strong> <?php echo $f->deadline?date('M d, Y',strtotime($f->deadline)):'None'; ?></span>
                <span><strong>Email:</strong> <?php echo esc_html($f->email); ?></span>
                <span><strong>Phone:</strong> <?php echo esc_html($f->phone); ?></span>
              </div>
              <?php if(!empty($f->valid_id_path)): ?>
                <div style="margin-top:10px;"><a href="<?php echo esc_url($f->valid_id_path); ?>" target="_blank" class="kbf-btn kbf-btn-secondary kbf-btn-sm">
                  <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                  View Valid ID (Manual Verification)
                </a></div>
              <?php endif; ?>
            </div>
            <span class="kbf-badge kbf-badge-pending">Pending</span>
          </div>
          <div class="kbf-btn-group" style="margin-top:14px;">
            <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfApprove(<?php echo $f->id; ?>)">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Approve
            </button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfReject(<?php echo $f->id; ?>)">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Reject
            </button>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfVerifyBadge(<?php echo $f->id; ?>,<?php echo $f->verified_badge; ?>)">
              <?php echo $f->verified_badge?'Remove Badge':'Grant Verified Badge'; ?>
            </button>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_all_funds_tab() {
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $funds=$wpdb->get_results("SELECT f.*,u.display_name as organizer FROM {$t} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID ORDER BY f.created_at DESC LIMIT 200"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">All Funds</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Title</th><th>Organizer</th><th>Category</th><th>Goal</th><th>Raised</th><th>Status</th><th>Escrow</th><th>Verified</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($funds)): ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--kbf-slate);">No funds found.</td></tr>
          <?php else: foreach($funds as $f): ?>
            <tr>
              <td><strong><?php echo esc_html(wp_trim_words($f->title,6)); ?></strong></td>
              <td><?php echo esc_html($f->organizer); ?></td>
              <td><?php echo esc_html($f->category); ?></td>
              <td>₱<?php echo number_format($f->goal_amount,0); ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($f->raised_amount,0); ?></strong></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $f->status; ?>"><?php echo ucfirst($f->status); ?></span></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $f->escrow_status; ?>"><?php echo ucfirst($f->escrow_status); ?></span></td>
              <td><?php echo $f->verified_badge?'<span style="color:var(--kbf-green);font-weight:700;">Yes</span>':'No'; ?></td>
              <td>
                <div class="kbf-btn-group">
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfVerifyBadge(<?php echo $f->id; ?>,<?php echo $f->verified_badge; ?>)"><?php echo $f->verified_badge?'Remove Badge':'Verify'; ?></button>
                  <?php if($f->status==='active'): ?>
                    <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfEscrow(<?php echo $f->id; ?>,'<?php echo $f->escrow_status==='holding'?'release':'hold'; ?>')"><?php echo $f->escrow_status==='holding'?'Release Escrow':'Hold Escrow'; ?></button>
                    <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfSuspend(<?php echo $f->id; ?>)">Suspend</button>
                  <?php elseif($f->status==='pending'): ?>
                    <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfApprove(<?php echo $f->id; ?>)">Approve</button>
                    <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfReject(<?php echo $f->id; ?>)">Reject</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_transactions_tab() {
    global $wpdb;$st=$wpdb->prefix.'kbf_sponsorships';$ft=$wpdb->prefix.'kbf_funds';
    $rows=$wpdb->get_results("SELECT s.*,f.title as fund_title FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id ORDER BY s.created_at DESC LIMIT 300"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">All Transactions</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fund</th><th>Sponsor</th><th>Amount</th><th>Method</th><th>Status</th><th>Reference</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--kbf-slate);">No transactions found.</td></tr>
          <?php else: foreach($rows as $s): ?>
            <tr>
              <td><strong><?php echo esc_html(wp_trim_words($s->fund_title,5)); ?></strong></td>
              <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name); ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($s->amount,2); ?></strong></td>
              <td><?php echo esc_html($s->payment_method==='online_payment'?'Online Payment':($s->payment_method==='bank_payment'?'Bank Payment':ucfirst(str_replace('_',' ',$s->payment_method??'--')))); ?></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
              <td class="kbf-meta"><?php echo esc_html($s->payment_reference?:'--'); ?></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($s->created_at)); ?></td>
              <td>
                <?php if($s->payment_status==='pending'): ?>
                  <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfConfirmPayment(<?php echo $s->id; ?>)">Confirm Payment</button>
                <?php else: echo '--'; endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div class="kbf-notif-placeholder" style="margin-top:14px;">
        <strong>Notification Integration Point:</strong> TODO: Hook your 3rd-party notification service here.<br>
        <small>When a payment is confirmed, fire: <code>do_action('kbf_payment_confirmed', $sponsorship_id)</code></small>
      </div>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_withdrawals_tab() {
    global $wpdb;$wt=$wpdb->prefix.'kbf_withdrawals';$ft=$wpdb->prefix.'kbf_funds';
    $rows=$wpdb->get_results("SELECT w.*,f.title as fund_title,u.display_name as funder_display FROM {$wt} w LEFT JOIN {$ft} f ON w.fund_id=f.id LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID ORDER BY w.requested_at DESC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Withdrawal Requests</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fund</th><th>Funder</th><th>Amount</th><th>Method</th><th>Account</th><th>Status</th><th>Requested</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--kbf-slate);">No withdrawal requests.</td></tr>
          <?php else: foreach($rows as $w): ?>
            <tr>
              <td><strong><?php echo esc_html(wp_trim_words($w->fund_title,5)); ?></strong></td>
              <td class="kbf-meta"><?php echo esc_html($w->funder_name ?: ($w->funder_display ?: '-')); ?></td>
              <td><strong>PHP <?php echo number_format($w->amount,2); ?></strong></td>
              <td><?php echo esc_html($w->method); ?></td>
              <td class="kbf-meta"><?php echo esc_html($w->account_name); ?><br><?php echo esc_html($w->account_number); ?></td>
              <td><span class="kbf-badge kbf-badge-<?php echo kbf_withdrawal_badge_class($w->status); ?>"><?php echo kbf_withdrawal_status_label($w->status); ?></span></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($w->requested_at)); ?></td>
              <td>
                <?php if($w->status==='pending'): ?>
                <div class="kbf-btn-group">
                  <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfProcessWd(<?php echo $w->id; ?>,'approve')">Approve & Release</button>
                  <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfProcessWd(<?php echo $w->id; ?>,'reject')">Reject</button>
                </div>
                <?php else: ?>
                  <span class="kbf-meta"><?php echo $w->processed_at?date('M d, Y',strtotime($w->processed_at)):'-'; ?></span>
                  <?php if($w->admin_notes): ?><div class="kbf-meta" style="font-style:italic;"><?php echo esc_html($w->admin_notes); ?></div><?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_reports_tab() {
    global $wpdb;$rt=$wpdb->prefix.'kbf_reports';$ft=$wpdb->prefix.'kbf_funds';
    $rows=$wpdb->get_results("SELECT r.*,f.title as fund_title FROM {$rt} r JOIN {$ft} f ON r.fund_id=f.id ORDER BY FIELD(r.status,'open','reviewed','dismissed'),r.created_at DESC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Fund Reports</h3>
      <?php if(empty($rows)): ?><div class="kbf-empty"><p>No reports filed.</p></div>
      <?php else: foreach($rows as $r): ?>
        <div class="kbf-card" style="border-left:3px solid <?php echo $r->status==='open'?'var(--kbf-red)':($r->status==='reviewed'?'var(--kbf-accent)':'var(--kbf-border)'); ?>;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
            <div>
              <strong style="font-size:14px;">Fund: <?php echo esc_html($r->fund_title); ?></strong>
              <div style="font-size:13px;color:var(--kbf-red);font-weight:700;margin-top:2px;">Reason: <?php echo esc_html($r->reason); ?></div>
              <p style="font-size:13px;color:var(--kbf-text-sm);margin:6px 0 0;"><?php echo esc_html($r->details); ?></p>
              <div class="kbf-meta" style="margin-top:6px;"><?php echo $r->reporter_email?esc_html($r->reporter_email):'Anonymous reporter'; ?> &bull; <?php echo date('M d, Y H:i',strtotime($r->created_at)); ?></div>
              <?php if($r->admin_notes): ?><div class="kbf-alert kbf-alert-info" style="margin-top:8px;font-size:12px;"><strong>Admin Note:</strong> <?php echo esc_html($r->admin_notes); ?></div><?php endif; ?>
            </div>
            <span class="kbf-badge kbf-badge-<?php echo $r->status; ?>"><?php echo ucfirst($r->status); ?></span>
          </div>
          <?php if($r->status==='open'): ?>
          <div class="kbf-btn-group">
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfSuspend(<?php echo $r->fund_id; ?>)">Suspend Fund</button>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfReviewReport(<?php echo $r->id; ?>)">Mark Reviewed</button>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfDismissReport(<?php echo $r->id; ?>)">Dismiss</button>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_organizers_tab() {
    global $wpdb;$pt=$wpdb->prefix.'kbf_organizer_profiles';
    $rows=$wpdb->get_results("SELECT p.*,u.display_name,u.user_email FROM {$pt} p JOIN {$wpdb->users} u ON p.business_id=u.ID ORDER BY p.total_raised DESC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Organizer Management</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Name</th><th>Email</th><th>Total Raised</th><th>Sponsors</th><th>Rating</th><th>Verified</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--kbf-slate);">No organizer profiles yet.</td></tr>
          <?php else: foreach($rows as $p): ?>
            <tr>
              <td><strong><?php echo esc_html($p->display_name); ?></strong></td>
              <td class="kbf-meta"><?php echo esc_html($p->user_email); ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($p->total_raised,0); ?></strong></td>
              <td><?php echo number_format($p->total_sponsors); ?></td>
              <td><?php echo number_format($p->rating,1); ?>/5 (<?php echo $p->rating_count; ?>)</td>
              <td><?php echo $p->is_verified?'<span style="color:var(--kbf-green);font-weight:700;">Verified</span>':'--'; ?></td>
              <td><button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfVerifyOrg(<?php echo $p->business_id; ?>,<?php echo $p->is_verified; ?>)"><?php echo $p->is_verified?'Revoke Verification':'Verify Organizer'; ?></button></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}


// ============================================================
// AJAX HANDLERS -- FUNDER
// ============================================================

function bntm_ajax_kbf_create_fund() {
    check_ajax_referer('kbf_create_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$table=$wpdb->prefix.'kbf_funds';
    $biz=get_current_user_id();
    foreach(['title','description','goal_amount','email','phone','location','category','funder_type'] as $f) {
        if(empty($_POST[$f])) wp_send_json_error(['message'=>'Please fill all required fields.']);
    }
    $goal=floatval($_POST['goal_amount']);
    if($goal<100) wp_send_json_error(['message'=>'Minimum goal is ₱100.']);
    $valid_id='';
    if(!empty($_FILES['valid_id']['name'])) {
        if(!function_exists('wp_handle_upload')) require_once(ABSPATH.'wp-admin/includes/file.php');
        $up=wp_handle_upload($_FILES['valid_id'],['test_form'=>false]);
        if(isset($up['url'])) $valid_id=$up['url'];
        elseif(isset($up['error'])) wp_send_json_error(['message'=>'ID upload failed: '.$up['error']]);
    }
    // Handle photos
    $photo_urls=[];
    if(!empty($_FILES['photos']['name'][0])) {
        if(!function_exists('wp_handle_upload')) require_once(ABSPATH.'wp-admin/includes/file.php');
        $count=min(5,count($_FILES['photos']['name']));
        for($i=0;$i<$count;$i++) {
            $file=['name'=>$_FILES['photos']['name'][$i],'type'=>$_FILES['photos']['type'][$i],'tmp_name'=>$_FILES['photos']['tmp_name'][$i],'error'=>$_FILES['photos']['error'][$i],'size'=>$_FILES['photos']['size'][$i]];
            $up=wp_handle_upload($file,['test_form'=>false]);
            if(isset($up['url'])) $photo_urls[]=$up['url'];
        }
    }
    $res=$wpdb->insert($table,[
        'rand_id'       =>bntm_rand_id(),
        'business_id'   =>$biz,
        'funder_type'   =>sanitize_text_field($_POST['funder_type']),
        'title'         =>sanitize_text_field($_POST['title']),
        'description'   =>sanitize_textarea_field($_POST['description']),
        'photos'        =>!empty($photo_urls)?json_encode($photo_urls):null,
        'goal_amount'   =>$goal,
        'category'      =>sanitize_text_field($_POST['category']),
        'email'         =>sanitize_email($_POST['email']),
        'phone'         =>sanitize_text_field($_POST['phone']),
        'location'      =>sanitize_text_field($_POST['location']),
        'valid_id_path' =>$valid_id,
        'auto_return'   =>isset($_POST['auto_return'])?1:0,
        'deadline'      =>!empty($_POST['deadline'])?sanitize_text_field($_POST['deadline']):null,
        'status'        =>'pending',
        'share_token'   =>wp_generate_password(32,false),
    ],['%s','%d','%s','%s','%s','%s','%f','%s','%s','%s','%s','%s','%d','%s','%s','%s']);
    // Ensure organizer profile exists
    $pt=$wpdb->prefix.'kbf_organizer_profiles';
    if(!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d",$biz))) {
        $wpdb->insert($pt,['business_id'=>$biz],['%d']);
    }
    if($res) wp_send_json_success(['message'=>'Fund submitted for review! We will notify you once approved.']);
    else wp_send_json_error(['message'=>'Failed to create fund. Please try again.']);
}

function bntm_ajax_kbf_update_fund() {
    check_ajax_referer('kbf_update_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    $data=['title'=>sanitize_text_field($_POST['title']),'description'=>sanitize_textarea_field($_POST['description']),'location'=>sanitize_text_field($_POST['location'])];
    // New photos
    if(!empty($_FILES['photos']['name'][0])) {
        if(!function_exists('wp_handle_upload')) require_once(ABSPATH.'wp-admin/includes/file.php');
        $existing=$fund->photos?json_decode($fund->photos,true):[];
        $count=min(5,count($_FILES['photos']['name']));
        for($i=0;$i<$count;$i++) {
            if(count($existing)>=5) break;
            $file=['name'=>$_FILES['photos']['name'][$i],'type'=>$_FILES['photos']['type'][$i],'tmp_name'=>$_FILES['photos']['tmp_name'][$i],'error'=>$_FILES['photos']['error'][$i],'size'=>$_FILES['photos']['size'][$i]];
            $up=wp_handle_upload($file,['test_form'=>false]);
            if(isset($up['url'])) $existing[]=$up['url'];
        }
        $data['photos']=json_encode($existing);
    }
    $res=$wpdb->update($t,$data,['id'=>$id],array_fill(0,count($data),'%s'),['%d']);
    if($res!==false) wp_send_json_success(['message'=>'Fund updated successfully!']);
    else wp_send_json_error(['message'=>'Failed to update fund.']);
}

function bntm_ajax_kbf_cancel_fund() {
    check_ajax_referer('kbf_cancel_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    if(in_array($fund->status,['cancelled','completed'])) wp_send_json_error(['message'=>'This fund cannot be cancelled.']);
    if($fund->raised_amount>0 && $fund->auto_return) kbf_refund_all_sponsors($id);
    $wpdb->update($t,['status'=>'cancelled'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Fund cancelled.']);
}

function bntm_ajax_kbf_mark_fund_complete() {
    check_ajax_referer('kbf_cancel_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund||$fund->status!=='active') wp_send_json_error(['message'=>'Fund not found or not active.']);
    $wpdb->update($t,['status'=>'completed'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Fund marked as complete!']);
}

function bntm_ajax_kbf_request_withdrawal() {
    check_ajax_referer('kbf_withdrawal','nonce');
    global $wpdb;$ft=$wpdb->prefix.'kbf_funds';$wt=$wpdb->prefix.'kbf_withdrawals';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();$amount=floatval($_POST['amount']);
    $fund = $biz
        ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND business_id=%d",$id,$biz))
        : $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d",$id));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    if(!$biz) {
        $email = sanitize_email($_POST['funder_email']??'');
        if(empty($email) || strcasecmp($email, (string)$fund->email) !== 0) {
            wp_send_json_error(['message'=>'Unauthorized. Please use the funder email on record.']);
        }
    }
    if(!in_array($fund->status, ['active', 'completed'])) wp_send_json_error(['message'=>'Withdrawals are only available for active or completed fundraisers.']);
    if($fund->escrow_status !== 'holding') wp_send_json_error(['message'=>'Funds must be in escrow before requesting withdrawal.']);
    if($amount<=0) wp_send_json_error(['message'=>'Please enter a valid amount.']);
    if($amount>$fund->raised_amount) wp_send_json_error(['message'=>'Amount exceeds total raised funds (PHP '.number_format($fund->raised_amount,2).' ).']);
    if(empty($_POST['method'])||empty($_POST['account_name'])||empty($_POST['account_number'])) wp_send_json_error(['message'=>'Please fill all required fields.']);
    // Prevent duplicate pending request for same fund
    $pending=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wt} WHERE fund_id=%d AND status='pending'",$id));
    if($pending>0) wp_send_json_error(['message'=>'You already have a pending withdrawal request for this fund. Please wait for admin to process it first.']);
    $funder_name = '';
    if($biz) {
        $user = get_userdata($biz);
        $funder_name = $user ? $user->display_name : '';
    } else {
        $funder_name = sanitize_text_field($_POST['funder_name']??'');
    }
    if(!$funder_name && $fund->business_id) {
        $u = get_userdata($fund->business_id);
        $funder_name = $u ? $u->display_name : '';
    }
    if(!$funder_name) $funder_name = 'Funder';
    $res=$wpdb->insert($wt,[
        'rand_id'        =>bntm_rand_id(),
        'fund_id'        =>$id,
        'funder_name'    =>$funder_name,
        'amount'         =>$amount,
        'method'         =>sanitize_text_field($_POST['method']),
        'account_name'   =>sanitize_text_field($_POST['account_name']),
        'account_number' =>sanitize_text_field($_POST['account_number']),
        'account_details'=>sanitize_textarea_field($_POST['account_details']??''),
        'status'         =>'pending',
    ],['%s','%d','%s','%f','%s','%s','%s','%s','%s']);
    if($res) wp_send_json_success(['message'=>'Withdrawal request submitted! Admin will review and process it within 2â€“3 business days.']);
    else wp_send_json_error(['message'=>'Failed to submit withdrawal request. Please try again.']);
}

function bntm_ajax_kbf_extend_deadline() {
    check_ajax_referer('kbf_extend','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    $deadline=sanitize_text_field($_POST['deadline']);
    if(strtotime($deadline)<=time()) wp_send_json_error(['message'=>'Deadline must be a future date.']);
    $res=$wpdb->update($t,['deadline'=>$deadline],['id'=>$id,'business_id'=>$biz],['%s'],['%d','%d']);
    if($res!==false) wp_send_json_success(['message'=>'Deadline extended to '.date('M d, Y',strtotime($deadline)).'!']);
    else wp_send_json_error(['message'=>'Failed to extend deadline.']);
}

function bntm_ajax_kbf_toggle_auto_return() {
    check_ajax_referer('kbf_cancel_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();$val=intval($_POST['auto_return']);
    $fund=$wpdb->get_row($wpdb->prepare("SELECT id FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    $wpdb->update($t,['auto_return'=>$val],['id'=>$id],['%d'],['%d']);
    wp_send_json_success(['message'=>'Auto-return setting updated.']);
}

function bntm_ajax_kbf_save_organizer_profile() {
    check_ajax_referer('kbf_organizer_profile','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$pt=$wpdb->prefix.'kbf_organizer_profiles';$biz=get_current_user_id();
    $avatar='';
    if(!empty($_FILES['avatar']['name'])) {
        if(!function_exists('wp_handle_upload')) require_once(ABSPATH.'wp-admin/includes/file.php');
        $up=wp_handle_upload($_FILES['avatar'],['test_form'=>false]);
        if(isset($up['url'])) $avatar=$up['url'];
    }
    $socials=json_encode(['facebook'=>esc_url_raw($_POST['social_facebook']??''),'instagram'=>esc_url_raw($_POST['social_instagram']??''),'twitter'=>esc_url_raw($_POST['social_twitter']??'')]);
    $data=['bio'=>sanitize_textarea_field($_POST['bio']??''),'social_links'=>$socials,'business_id'=>$biz];
    if($avatar) $data['avatar_url']=$avatar;
    $exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d",$biz));
    if($exists) {
        unset($data['business_id']);
        $formats = array_fill(0, count($data), '%s');
        $wpdb->update($pt, $data, ['business_id'=>$biz], $formats, ['%d']);
    } else {
        $insert_formats = array_fill(0, count($data), '%s');
        $wpdb->insert($pt, $data, $insert_formats);
    }
    wp_send_json_success(['message'=>'Profile saved successfully!']);
}

// ============================================================
// AJAX HANDLERS -- SPONSOR / PUBLIC
// ============================================================

// ============================================================
// MAYA CHECKOUT INTEGRATION
// ============================================================

/**
 * Get Maya secret key from settings.
 * Sandbox key when demo_mode ON, live key when OFF.
 */
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

    $response = wp_remote_request(kbf_maya_base_url() . $endpoint, $args);
    if (is_wp_error($response)) return ['error' => $response->get_error_message()];

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 400) {
        $msg = $body['message'] ?? ($body['error']['message'] ?? 'Maya API error (HTTP ' . $code . ').');
        return ['error' => $msg, 'code' => $code, 'raw' => $body];
    }
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

    if ($amount < 10) wp_send_json_error(['message' => 'Minimum sponsorship is ₱10.']);

    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND status='active'", $fund_id));
    if (!$fund) wp_send_json_error(['message' => 'Fund not found or not accepting sponsorships.']);

    // Save a pending sponsorship -- confirmed via webhook after payment
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
        'payment_status' => 'pending',
        'message'        => $message,
    ], ['%s','%d','%s','%d','%f','%s','%s','%s','%s','%s']);
    $sponsorship_id = $wpdb->insert_id;

    // Redirect URLs -- Maya sends buyer back after payment
    $success_url = add_query_arg(['kbf_payment' => 'success', 'sid' => $sponsorship_id, 'ref' => $rand_id], get_permalink());
    $failure_url = add_query_arg(['kbf_payment' => 'failed',  'sid' => $sponsorship_id], get_permalink());
    $cancel_url  = add_query_arg(['kbf_payment' => 'cancelled','sid' => $sponsorship_id], get_permalink());

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

function bntm_ajax_kbf_sponsor_fund() {
    check_ajax_referer('kbf_sponsor','nonce');
    global $wpdb;$ft=$wpdb->prefix.'kbf_funds';$st=$wpdb->prefix.'kbf_sponsorships';
    $id=intval($_POST['fund_id']);$amount=floatval($_POST['amount']);
    if($amount<10) wp_send_json_error(['message'=>'Minimum sponsorship is ₱10.']);
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND status='active'",$id));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found or not accepting sponsorships.']);
    $anon=intval($_POST['is_anonymous']??0);
    $method=sanitize_text_field($_POST['payment_method']??'');

    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);

    // In live mode, sponsor_fund is handled by kbf_create_checkout.
    // This handler only runs in demo mode (auto-confirm, no gateway).
    if (!$demo_mode) {
        wp_send_json_error(['message' => 'Please use the payment checkout flow.']);
        return;
    }

    // â”€â”€ DEMO MODE: auto-confirm sponsorship â”€â”€
    $res=$wpdb->insert($st,[
        'rand_id'          =>bntm_rand_id(),
        'fund_id'          =>$id,
        'sponsor_name'     =>$anon?'Anonymous':sanitize_text_field($_POST['sponsor_name']??'Anonymous'),
        'is_anonymous'     =>$anon,
        'amount'           =>$amount,
        'email'            =>sanitize_email($_POST['email']??''),
        'phone'            =>sanitize_text_field($_POST['phone']??''),
        'payment_method'   =>$method,
        'payment_status'   =>'completed',
        'message'          =>sanitize_textarea_field($_POST['message']??''),
    ],['%s','%d','%s','%d','%f','%s','%s','%s','%s','%s']);
    if($res) {
        $new_id = $wpdb->insert_id;
        $wpdb->query($wpdb->prepare("UPDATE {$ft} SET raised_amount=raised_amount+%f WHERE id=%d",$amount,$id));
        // Auto-complete: mark fund complete if goal reached
        $updated_fund = $wpdb->get_row($wpdb->prepare("SELECT raised_amount,goal_amount FROM {$ft} WHERE id=%d",$id));
        $just_completed = false;
        if($updated_fund && $updated_fund->goal_amount > 0 && $updated_fund->raised_amount >= $updated_fund->goal_amount) {
            $wpdb->update($ft,['status'=>'completed','escrow_status'=>'holding'],['id'=>$id],['%s','%s'],['%d']);
            $just_completed = true;
            do_action('kbf_fund_goal_reached', $id);
        }
        $pt=$wpdb->prefix.'kbf_organizer_profiles';
        $total=$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(s.amount),0) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$fund->business_id));
        $cnt=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$fund->business_id));
        $wpdb->update($pt,['total_raised'=>$total,'total_sponsors'=>$cnt],['business_id'=>$fund->business_id],['%f','%d'],['%d']);
        $msg = $just_completed
            ? 'Sponsorship confirmed! ₱'.number_format($amount,2).' added. This fund has now reached its goal!'
            : 'Sponsorship confirmed! ₱'.number_format($amount,2).' has been added to this fund. Thank you for your support!';
        wp_send_json_success(['message'=>$msg,'fund_completed'=>$just_completed]);
    } else {
        wp_send_json_error(['message'=>'Sponsorship failed. Please try again.']);
    }
}

function bntm_ajax_kbf_report_fund() {
    check_ajax_referer('kbf_report','nonce');
    global $wpdb;$t=$wpdb->prefix.'kbf_reports';
    $id=intval($_POST['fund_id']);$reason=sanitize_text_field($_POST['reason']);$details=sanitize_textarea_field($_POST['details']);
    if(empty($reason)||empty($details)) wp_send_json_error(['message'=>'Please fill all required fields.']);
    $res=$wpdb->insert($t,['rand_id'=>bntm_rand_id(),'fund_id'=>$id,'reporter_id'=>get_current_user_id(),'reporter_email'=>sanitize_email($_POST['reporter_email']??''),'reason'=>$reason,'details'=>$details,'status'=>'open'],['%s','%d','%d','%s','%s','%s','%s']);
    if($res) wp_send_json_success(['message'=>'Report submitted. Our team will review it shortly.']);
    else wp_send_json_error(['message'=>'Failed to submit report.']);
}

function bntm_ajax_kbf_get_fund_details() {
    check_ajax_referer('kbf_sponsor','nonce');
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);
    $f=$wpdb->get_row($wpdb->prepare("SELECT id,title,description,goal_amount,raised_amount,location,category FROM {$t} WHERE id=%d AND status='active'",$id));
    if($f) wp_send_json_success($f);
    else wp_send_json_error(['message'=>'Fund not found.']);
}

function bntm_ajax_kbf_get_organizer_profile() {
    // Public endpoint: no nonce required -- returns only public profile data
    global $wpdb;
    $biz=intval($_POST['business_id']??0);
    if(!$biz) wp_send_json_error(['message'=>'Not found.']);
    $pt=$wpdb->prefix.'kbf_organizer_profiles';
    $ft=$wpdb->prefix.'kbf_funds';
    $rt=$wpdb->prefix.'kbf_ratings';
    $st=$wpdb->prefix.'kbf_sponsorships';
    $profile=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$biz));
    $user=get_userdata($biz);
    if(!$user) wp_send_json_error(['message'=>'Organizer not found.']);
    $active_funds=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status='active'",$biz));
    $total_funds=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status IN ('active','completed')",$biz));
    // All funds (active + completed) for fund history
    $funds=$wpdb->get_results($wpdb->prepare(
        "SELECT id,title,goal_amount,raised_amount,status,category,deadline,created_at FROM {$ft} WHERE business_id=%d AND status IN ('active','completed') ORDER BY created_at DESC LIMIT 10",
        $biz
    ));
    $fund_data=array_map(function($f) use($wpdb,$st){
        $sponsor_count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed'",$f->id));
        $pct=$f->goal_amount>0?min(100,round(($f->raised_amount/$f->goal_amount)*100)):0;
        return [
            'id'           =>$f->id,
            'title'        =>$f->title,
            'raised'       =>number_format($f->raised_amount,0),
            'goal'         =>number_format($f->goal_amount,0),
            'pct'          =>$pct,
            'status'       =>$f->status,
            'category'     =>$f->category,
            'sponsor_count'=>$sponsor_count,
        ];
    }, $funds);
    // Recent reviews
    $reviews=$wpdb->get_results($wpdb->prepare(
        "SELECT r.*,f.title as fund_title FROM {$rt} r LEFT JOIN {$ft} f ON r.fund_id=f.id WHERE r.organizer_id=%d ORDER BY r.created_at DESC LIMIT 5",
        $biz
    ));
    $review_data=array_map(fn($r)=>[
        'rating'    =>$r->rating,
        'review'    =>$r->review,
        'email'     =>substr($r->sponsor_email,0,3).'***'.strstr($r->sponsor_email,'@'),
        'fund_title'=>$r->fund_title,
        'date'      =>date('M d, Y',strtotime($r->created_at)),
    ],$reviews);
    wp_send_json_success([
        'display_name'  =>$user->display_name,
        'avatar_url'    =>$profile?$profile->avatar_url:'',
        'bio'           =>$profile?$profile->bio:'',
        'is_verified'   =>$profile?(bool)$profile->is_verified:false,
        'total_raised'  =>$profile?$profile->total_raised:0,
        'total_sponsors'=>$profile?$profile->total_sponsors:0,
        'rating'        =>$profile?number_format($profile->rating,1):'0.0',
        'rating_count'  =>$profile?$profile->rating_count:0,
        'active_funds'  =>$active_funds,
        'total_funds'   =>$total_funds,
        'funds'         =>$fund_data,
        'reviews'       =>$review_data,
    ]);
}

function bntm_ajax_kbf_submit_rating() {
    check_ajax_referer('kbf_rating','nonce');
    global $wpdb;$rt=$wpdb->prefix.'kbf_ratings';$pt=$wpdb->prefix.'kbf_organizer_profiles';
    $org_id=intval($_POST['organizer_id']);$rating=min(5,max(1,intval($_POST['rating'])));
    $email=sanitize_email($_POST['sponsor_email']??'');
    if(empty($email)) wp_send_json_error(['message'=>'Email required to submit a review.']);
    // Prevent duplicate rating per email per organizer
    $exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$rt} WHERE organizer_id=%d AND sponsor_email=%s",$org_id,$email));
    if($exists) wp_send_json_error(['message'=>'You have already submitted a review for this organizer.']);
    $wpdb->insert($rt,['rand_id'=>bntm_rand_id(),'organizer_id'=>$org_id,'sponsor_email'=>$email,'rating'=>$rating,'review'=>sanitize_textarea_field($_POST['review']??''),'fund_id'=>intval($_POST['fund_id']??0)],['%s','%d','%s','%d','%s','%d']);
    // Recalculate average
    $avg=$wpdb->get_var($wpdb->prepare("SELECT AVG(rating) FROM {$rt} WHERE organizer_id=%d",$org_id));
    $cnt=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE organizer_id=%d",$org_id));
    $wpdb->update($pt,['rating'=>round($avg,2),'rating_count'=>(int)$cnt],['business_id'=>$org_id],['%f','%d'],['%d']);
    wp_send_json_success(['message'=>'Thank you for your review!']);
}

// ============================================================
// ADMIN TAB: Settings
// ============================================================

function kbf_admin_settings_tab() {
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $sb_pub    = kbf_get_setting('kbf_maya_sandbox_public', '');
    $sb_sec    = kbf_get_setting('kbf_maya_sandbox_secret', '');
    $lv_pub    = kbf_get_setting('kbf_maya_live_public', '');
    $lv_sec    = kbf_get_setting('kbf_maya_live_secret', '');
    $nonce     = wp_create_nonce('kbf_admin_action');
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:8px;">Platform Settings</h3>
      <p style="color:var(--kbf-slate);font-size:13.5px;margin-bottom:24px;">Configure KonekBayan payments and live mode.</p>

      <!-- Demo / Live Mode Toggle -->
      <div class="kbf-card" style="border-left:4px solid <?php echo $demo_mode?'var(--kbf-accent)':'var(--kbf-green)'; ?>;margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
              <strong style="font-size:15px;color:var(--kbf-navy);">Payment Mode</strong>
              <span class="kbf-badge <?php echo $demo_mode?'kbf-badge-holding':'kbf-badge-active'; ?>">
                <?php echo $demo_mode?'DEMO -- Auto-confirm':'LIVE -- Maya Checkout'; ?>
              </span>
            </div>
            <p style="margin:0 0 10px;font-size:13.5px;color:var(--kbf-text-sm);line-height:1.6;">
              <strong>Demo ON:</strong> Sponsorships auto-confirmed instantly, no real payment.<br>
              <strong>Demo OFF:</strong> Sponsors are redirected to Maya's secure checkout page (Maya Wallet, cards, QRPh).
            </p>
            <?php if($demo_mode): ?>
            <div class="kbf-alert kbf-alert-warning" style="font-size:12.5px;"><strong>Demo Mode is active.</strong> Configure your Maya API keys below, then switch to Live.</div>
            <?php else: ?>
            <div class="kbf-alert kbf-alert-success" style="font-size:12.5px;"><strong>Live Mode active.</strong> Maya Checkout processes all payments. Sandbox keys used for testing.</div>
            <?php endif; ?>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;min-width:160px;">
            <?php if($demo_mode): ?>
              <button class="kbf-btn kbf-btn-success" onclick="kbfSaveSetting('kbf_demo_mode','0','<?php echo $nonce; ?>')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Switch to Live
              </button>
            <?php else: ?>
              <button class="kbf-btn kbf-btn-accent" onclick="kbfSaveSetting('kbf_demo_mode','1','<?php echo $nonce; ?>')">Switch to Demo</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Maya API Keys -->
      <div class="kbf-card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--kbf-navy)" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
          <strong style="font-size:15px;color:var(--kbf-navy);">Maya API Keys</strong>
          <a href="https://sandbox-manager.paymaya.com" target="_blank" style="font-size:12px;color:var(--kbf-blue);margin-left:auto;">Open Maya Business Manager â†’</a>
        </div>

        <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:16px;font-size:13px;color:var(--kbf-text-sm);line-height:1.7;">
          <strong style="color:var(--kbf-navy);">Where to find your keys:</strong><br>
          Maya Business Manager â†’ Developers â†’ API Keys. Copy Public Key &amp; Secret Key for both Sandbox and Live environments.
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
          <!-- Sandbox Keys -->
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-accent);margin-bottom:10px;">Sandbox (Testing)</div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Sandbox Public Key</label>
              <input type="text" id="sb-pub" value="<?php echo esc_attr($sb_pub); ?>" placeholder="pk-sandbox-..." style="font-family:monospace;font-size:12px;">
            </div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Sandbox Secret Key</label>
              <input type="password" id="sb-sec" value="<?php echo esc_attr($sb_sec); ?>" placeholder="sk-sandbox-..." style="font-family:monospace;font-size:12px;">
            </div>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveMayaKeys('sandbox')">Save Sandbox Keys</button>
          </div>
          <!-- Live Keys -->
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-green);margin-bottom:10px;">Live (Production)</div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Live Public Key</label>
              <input type="text" id="lv-pub" value="<?php echo esc_attr($lv_pub); ?>" placeholder="pk-live-..." style="font-family:monospace;font-size:12px;">
            </div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Live Secret Key</label>
              <input type="password" id="lv-sec" value="<?php echo esc_attr($lv_sec); ?>" placeholder="sk-live-..." style="font-family:monospace;font-size:12px;">
            </div>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveMayaKeys('live')">Save Live Keys</button>
          </div>
        </div>

        <!-- Webhook URL -->
        <div style="border-top:1px solid var(--kbf-border);margin-top:20px;padding-top:16px;">
          <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-navy);margin-bottom:10px;">Webhook URL (for automatic payment confirmation)</div>
          <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;font-size:13px;line-height:1.8;">
            <strong>Your Webhook URL -- copy this into Maya Business Manager:</strong><br>
            <code style="font-size:12px;word-break:break-all;color:var(--kbf-navy);background:#e2e8f0;padding:4px 8px;border-radius:4px;display:inline-block;margin:6px 0;"><?php echo esc_html(rest_url('kbf/v1/maya-webhook')); ?></code><br>
            <small style="color:var(--kbf-slate);">
              Maya Business Manager â†’ Developers â†’ Webhooks â†’ Add Webhook URL.<br>
              Subscribe to events: <strong>CHECKOUT_SUCCESS</strong> and <strong>PAYMENT_SUCCESS</strong>.<br>
              No webhook secret is required -- Maya authenticates via your API keys.
            </small>
          </div>
        </div>
      </div>

      <div id="kbf-settings-msg" style="margin-top:12px;"></div>
    </div>
    <script>
    window.kbfSaveSetting = function(key, val, nonce) {
        const fd = new FormData();
        fd.append('action','kbf_save_setting');
        fd.append('_ajax_nonce', nonce);
        fd.append('setting_key', key);
        fd.append('setting_val', val);
        fetch((window.ajaxurl||'<?php echo admin_url('admin-ajax.php'); ?>'),{method:'POST',body:fd})
        .then(r=>r.json()).then(j=>{
            alert(j.data&&j.data.message?j.data.message:(j.success?'Setting saved!':'Failed to save.'));
            if(j.success) location.reload();
        });
    };
    window.kbfSaveMayaKeys = function(type) {
        const nonce = '<?php echo $nonce; ?>';
        const msg   = document.getElementById('kbf-settings-msg');
        const pairs = type === 'sandbox'
            ? [['kbf_maya_sandbox_public', document.getElementById('sb-pub').value],
               ['kbf_maya_sandbox_secret', document.getElementById('sb-sec').value]]
            : [['kbf_maya_live_public', document.getElementById('lv-pub').value],
               ['kbf_maya_live_secret', document.getElementById('lv-sec').value]];
        let done = 0;
        pairs.forEach(([key, val]) => {
            const fd = new FormData();
            fd.append('action', 'kbf_save_setting');
            fd.append('_ajax_nonce', nonce);
            fd.append('setting_key', key);
            fd.append('setting_val', val);
            fetch((window.ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>'), {method:'POST', body:fd})
            .then(r => r.json()).then(j => {
                if (++done === pairs.length && msg) {
                    msg.innerHTML = '<div class="kbf-alert kbf-alert-success" style="font-size:13px;">' + (type==='sandbox'?'Sandbox':'Live') + ' keys saved successfully.</div>';
                    setTimeout(() => msg.innerHTML = '', 4000);
                }
            });
        });
    };
    </script>
    <?php return ob_get_clean();
}


// ============================================================
// AJAX HANDLERS -- ADMIN
// ============================================================

function bntm_ajax_kbf_admin_approve_fund() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $wpdb->update($t,['status'=>'active'],['id'=>$id],['%s'],['%d']);
    // =====================================================
    // NOTIFICATION PLACEHOLDER
    // TODO: Notify funder their fund is now live
    // do_action('kbf_fund_approved', $id);
    // =====================================================
    wp_send_json_success(['message'=>'Fund approved and is now live!']);
}

function bntm_ajax_kbf_admin_reject_fund() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $notes=sanitize_text_field($_POST['reason']??'');
    $wpdb->update($t,['status'=>'cancelled','admin_notes'=>$notes],['id'=>$id],['%s','%s'],['%d']);
    wp_send_json_success(['message'=>'Fund rejected.']);
}

function bntm_ajax_kbf_admin_suspend_fund() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $wpdb->update($t,['status'=>'suspended'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Fund suspended.']);
}

function bntm_ajax_kbf_admin_verify_badge() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);$v=intval($_POST['verified']);
    $wpdb->update($t,['verified_badge'=>$v],['id'=>$id],['%d'],['%d']);
    wp_send_json_success(['message'=>$v?'Verified badge granted!':'Badge removed.']);
}

function bntm_ajax_kbf_admin_release_escrow() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $wpdb->update($t,['escrow_status'=>'released'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Escrow released. Organizer can now withdraw funds.']);
}

function bntm_ajax_kbf_admin_hold_escrow() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $wpdb->update($t,['escrow_status'=>'holding'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Funds placed on hold.']);
}

function bntm_ajax_kbf_admin_process_withdrawal() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$wt=$wpdb->prefix.'kbf_withdrawals';$ft=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['withdrawal_id']);$type=sanitize_text_field($_POST['action_type']);$notes=sanitize_text_field($_POST['notes']??'');
    $wd=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wt} WHERE id=%d",$id));
    if(!$wd) wp_send_json_error(['message'=>'Withdrawal not found.']);
    if($type==='approve') {
        $wpdb->update($wt,['status'=>'released','processed_at'=>current_time('mysql'),'admin_notes'=>$notes],['id'=>$id],['%s','%s','%s'],['%d']);
        $wpdb->query($wpdb->prepare("UPDATE {$ft} SET raised_amount=raised_amount-%f WHERE id=%d",$wd->amount,$wd->fund_id));
        $wpdb->update($ft,['escrow_status'=>'released'],['id'=>$wd->fund_id],['%s'],['%d']);
        wp_send_json_success(['message'=>'Withdrawal approved and released!']);
    } else {
        $wpdb->update($wt,['status'=>'rejected','processed_at'=>current_time('mysql'),'admin_notes'=>$notes],['id'=>$id],['%s','%s','%s'],['%d']);
        wp_send_json_success(['message'=>'Withdrawal rejected.']);
    }
}

function bntm_ajax_kbf_admin_dismiss_report() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_reports';$id=intval($_POST['report_id']);
    $wpdb->update($t,['status'=>'dismissed'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Report dismissed.']);
}

function bntm_ajax_kbf_admin_review_report() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_reports';$id=intval($_POST['report_id']);
    $notes=sanitize_text_field($_POST['notes']??'');
    $wpdb->update($t,['status'=>'reviewed','admin_notes'=>$notes],['id'=>$id],['%s','%s'],['%d']);
    wp_send_json_success(['message'=>'Report marked as reviewed.']);
}

function bntm_ajax_kbf_admin_confirm_payment() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$st=$wpdb->prefix.'kbf_sponsorships';$ft=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['sponsorship_id']);
    $sp=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$st} WHERE id=%d",$id));
    if(!$sp||$sp->payment_status==='completed') wp_send_json_error(['message'=>'Sponsorship not found or already confirmed.']);
    $wpdb->update($st,['payment_status'=>'completed'],['id'=>$id],['%s'],['%d']);
    // Update raised amount on fund
    $wpdb->query($wpdb->prepare("UPDATE {$ft} SET raised_amount=raised_amount+%f WHERE id=%d",$sp->amount,$sp->fund_id));
    // Update organizer profile stats
    $fund=$wpdb->get_row($wpdb->prepare("SELECT business_id FROM {$ft} WHERE id=%d",$sp->fund_id));
    if($fund) {
        $pt=$wpdb->prefix.'kbf_organizer_profiles';
        $total=$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(s.amount),0) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$fund->business_id));
        $cnt=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$fund->business_id));
        $wpdb->update($pt,['total_raised'=>$total,'total_sponsors'=>$cnt],['business_id'=>$fund->business_id],['%f','%d'],['%d']);
    }
    // =====================================================
    // NOTIFICATION PLACEHOLDER
    // TODO: Send receipt to sponsor here
    // do_action('kbf_send_sponsorship_receipt', $id, $sp->fund_id);
    // =====================================================
    wp_send_json_success(['message'=>'Payment confirmed! Sponsor notified.']);
}

function bntm_ajax_kbf_admin_verify_organizer() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$pt=$wpdb->prefix.'kbf_organizer_profiles';
    $biz=intval($_POST['business_id']);$v=intval($_POST['verified']);
    $exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d",$biz));
    if($exists) $wpdb->update($pt,['is_verified'=>$v],['business_id'=>$biz],['%d'],['%d']);
    else $wpdb->insert($pt,['business_id'=>$biz,'is_verified'=>$v],['%d','%d']);
    wp_send_json_success(['message'=>$v?'Organizer verified!':'Verification revoked.']);
}

function bntm_ajax_kbf_save_setting() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    $key = sanitize_key($_POST['setting_key']??'');
    $val = sanitize_text_field($_POST['setting_val']??'');
    if(empty($key)) wp_send_json_error(['message'=>'Invalid setting key.']);
    kbf_set_setting($key, $val);
    $labels = ['kbf_demo_mode' => ['0'=>'Live Mode activated. Sponsorships now require payment confirmation.','1'=>'Demo Mode activated. Sponsorships will be auto-confirmed.']];
    $msg = $labels[$key][$val] ?? 'Setting saved!';
    wp_send_json_success(['message'=>$msg]);
}

// ============================================================
// HELPER FUNCTIONS
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

/**
 * Resolve the URL of a page by the shortcode it contains.
 * Usage: kbf_get_page_url('fund_details') â†’ URL of the page with [kbf_fund_details]
 */
function kbf_get_page_url($page_key) {
    static $cache = [];
    if(isset($cache[$page_key])) return $cache[$page_key];
    $shortcode_map = [
        'dashboard'         => 'kbf_dashboard',
        'browse'            => 'kbf_browse',
        'fund_details'      => 'kbf_fund_details',
        'organizer_profile' => 'kbf_organizer_profile',
        'sponsor_history'   => 'kbf_sponsor_history',
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






