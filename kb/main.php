<?php
/**
 * Module Name: KonekBayan Finding Platform
 * Module Slug: kbf
 * Description: Community crowdfunding and sponsorship platform for funders and sponsors.
 * Version: 2.0.0
 * Author: KonekBayan
 * Icon: ./assets/logo.png
 */
/*
 * KBF module bootstrap: defines constants, shared helpers, DB/cron setup,
 * global assets, and loads user/admin components.
 */

if (!defined('ABSPATH')) exit;

define('BNTM_KBF_PATH', dirname(__FILE__) . '/');
define('BNTM_KBF_URL', plugin_dir_url(__FILE__));

require_once(BNTM_KBF_PATH . 'user.php');
require_once(BNTM_KBF_PATH . 'admin.php');
require_once(BNTM_KBF_PATH . 'includes/pages.php');
require_once(BNTM_KBF_PATH . 'includes/shortcodes.php');
require_once(BNTM_KBF_PATH . 'includes/db.php');
require_once(BNTM_KBF_PATH . 'includes/ajax-hooks.php');
require_once(BNTM_KBF_PATH . 'includes/cron.php');
require_once(BNTM_KBF_PATH . 'includes/assets.php');


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





