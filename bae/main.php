<?php
/**
 * Module Name: Mothie
 * Module Slug: bae
 * Description: A complete brand identity builder for MSMEs. Allows business owners to define
 *              their brand identity (name, colors, fonts, tone, tagline) and generate ready-to-use
 *              brand assets including logo concepts, business card layouts, letterheads,
 *              social media templates, brand guidelines, and a shareable brand kit page.
 * Version: 1.1.0
 * Author: BNTM
 * Icon: assets/logo.png
 */

if (!defined('ABSPATH')) exit;

define('BNTM_BAE_PATH', dirname(__FILE__) . '/');
define('BNTM_BAE_URL', plugin_dir_url(__FILE__));

function bae_module_logo_url() {
    $path = BNTM_BAE_PATH . 'logo.png';
    $ver  = file_exists($path) ? filemtime($path) : time();
    return add_query_arg('ver', $ver, BNTM_BAE_URL . 'logo.png');
}

function bae_module_favicon_url() {
    $path = BNTM_BAE_PATH . 'favicon.ico';
    $ver  = file_exists($path) ? filemtime($path) : time();
    return add_query_arg('ver', $ver, BNTM_BAE_URL . 'favicon.ico');
}

function bae_policy_url() {
    if (function_exists('get_privacy_policy_url')) {
        $url = get_privacy_policy_url();
        if (!empty($url)) return $url;
    }
    return home_url('/privacy-policy');
}

function bae_render_brand_mark($class = '') {
    $classes = trim('bae-brand-mark ' . $class);
    return '<span class="' . esc_attr($classes) . '"><img class="bae-brand-logo-img" src="' . esc_url(bae_module_logo_url()) . '" alt="Mothie logo"></span>';
}

function bae_render_brand_wordmark($class = '') {
    $classes = trim('bae-brand-wordmark ' . $class);
    return '<span class="' . esc_attr($classes) . '">'
        . '<span class="bae-brand-wordmark-leading">M</span>'
        . '<span class="bae-brand-wordmark-center">'
        . '<img class="bae-brand-logo-img bae-brand-wordmark-logo" src="' . esc_url(bae_module_logo_url()) . '" alt="Mothie logo">'
        . '<span class="bae-brand-wordmark-letter">o</span>'
        . '</span>'
        . '<span class="bae-brand-wordmark-trailing">thie</span>'
        . '</span>';
}

function bae_render_brand_wordmark_merge($class = '') {
    $classes = trim('bae-brand-wordmark bae-brand-wordmark-merge ' . $class);
    return '<span class="' . esc_attr($classes) . '">'
        . '<span class="bae-brand-wordmark-leading">M</span>'
        . '<span class="bae-brand-wordmark-center">'
        . '<span class="bae-brand-wordmark-letter bae-brand-wordmark-letter-o">o</span>'
        . '<img class="bae-brand-logo-img bae-brand-wordmark-logo bae-brand-wordmark-logo-merge" src="' . esc_url(bae_module_logo_url()) . '" alt="Mothie logo">'
        . '</span>'
        . '<span class="bae-brand-wordmark-trailing">thie</span>'
        . '</span>';
}

add_action('wp_head', function() {
    static $bae_favicon_rendered = false;

    if ($bae_favicon_rendered) {
        return;
    }

    $bae_favicon_rendered = true;
    $favicon_url = esc_url(bae_module_favicon_url());
    $logo_url    = esc_url(bae_module_logo_url());

    echo '<link rel="icon" href="' . $favicon_url . '" sizes="any">' . "\n";
    echo '<link rel="shortcut icon" href="' . $favicon_url . '">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . $logo_url . '">' . "\n";
}, 5);


add_action('wp_head', function() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
}, 1);

// Load ticketing system
require_once BNTM_BAE_PATH . 'ticket.php';

// Load PayMaya payment integration
require_once BNTM_BAE_PATH . 'paymaya.php';

// Load Admin Panel
require_once BNTM_BAE_PATH . 'admin.php';

// Load Brand Book templates/renderers
require_once BNTM_BAE_PATH . 'brand-book.php';


// =============================================================================
// STEP-BY-STEP PROCESS THIS MODULE PERFORMS:
//
// STEP 1 — Brand Profile Setup (Overview Tab)
//   User fills in: Business Name, Industry, Tagline, Brand Personality,
//   Contact Info (Email, Phone, Website, Address),
//   Primary Color, Secondary Color, Accent Color, Font Preference, Logo Style.
//   This is saved as the brand profile for the business.
//
// STEP 2 — Brand Identity Preview (Identity Tab)
//   System renders a live Brand Board: color swatches, font pairing preview,
//   logo concept mockup (CSS-based), and a tone-of-voice statement generated
//   from the personality inputs.
//
// STEP 3 — Asset Generator (Assets Tab)
//   User selects which assets to generate:
//     - Business Card (front + back layout, print-ready HTML)
//     - Letterhead (A4 template with brand header/footer)
//     - Email Signature (HTML snippet)
//     - Social Media Kit (profile photo frame, cover photo, post template)
//     - Brand Guidelines PDF (one-page brand rules document)
//   Each asset is generated as a styled HTML preview with a download/copy option.
//
// STEP 4 — Brand Kit Page (Brand Kit Tab)
//   A shareable, public-facing single-page brand kit is generated.
//   Business owner gets a unique URL they can share with designers, printers,
//   vendors, or team members. Shows: logo, colors, fonts, usage rules.
//
// STEP 5 — Settings (Settings Tab)
//   Manage brand profile reset, export all assets as ZIP (future),
//   set brand kit page visibility (public/private), and delete brand data.
// =============================================================================

// =============================================================================
// MODULE CONFIGURATION
// =============================================================================

function bntm_bae_get_pages() {
    return [
        'Mothie' => '[bntm_bae_dashboard]',
        'Brand Kit'          => '[bntm_bae_kit]',
    ];
}

function bntm_bae_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    return [
        // CHANGED: Added contact fields (email, phone, website, address) to profiles table
        'bae_profiles' => "CREATE TABLE {$prefix}bae_profiles (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            business_name VARCHAR(255) NOT NULL DEFAULT '',
            industry VARCHAR(100) NOT NULL DEFAULT '',
            tagline VARCHAR(255) NOT NULL DEFAULT '',
            personality TEXT NOT NULL DEFAULT '',
            email VARCHAR(255) NOT NULL DEFAULT '',
            phone VARCHAR(50) NOT NULL DEFAULT '',
            website VARCHAR(255) NOT NULL DEFAULT '',
            address VARCHAR(255) NOT NULL DEFAULT '',
            primary_color VARCHAR(10) NOT NULL DEFAULT '#1a1a2e',
            secondary_color VARCHAR(10) NOT NULL DEFAULT '#16213e',
            accent_color VARCHAR(10) NOT NULL DEFAULT '#e94560',
            font_heading VARCHAR(100) NOT NULL DEFAULT 'Inter',
            font_body VARCHAR(100) NOT NULL DEFAULT 'Inter',
            logo_style VARCHAR(50) NOT NULL DEFAULT 'wordmark',
            logo_icon VARCHAR(50) NOT NULL DEFAULT '',
            logo_icon_scale INT NOT NULL DEFAULT 100,
            logo_spacing INT NOT NULL DEFAULT 14,
            logo_position VARCHAR(20) NOT NULL DEFAULT 'auto',
            logo_text_case VARCHAR(20) NOT NULL DEFAULT 'default',
            ticket VARCHAR(20) NOT NULL DEFAULT '',
            session_id VARCHAR(64) NOT NULL DEFAULT '',
            logo_url VARCHAR(500) NOT NULL DEFAULT '',
            plan VARCHAR(20) NOT NULL DEFAULT 'free',
            beta_free_claimed TINYINT(1) NOT NULL DEFAULT 0,
            beta_claimed_at DATETIME NULL,
            tone_statement TEXT NOT NULL DEFAULT '',
            kit_visibility VARCHAR(10) NOT NULL DEFAULT 'private',
            kit_slug VARCHAR(100) UNIQUE NOT NULL DEFAULT '',
            kit_views INT UNSIGNED NOT NULL DEFAULT 0,
            kit_unique_views INT UNSIGNED NOT NULL DEFAULT 0,
            onboarding_asset_viewed TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_ticket (ticket),
            INDEX idx_session (session_id)
        ) {$charset};",

        'bae_assets' => "CREATE TABLE {$prefix}bae_assets (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            profile_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            asset_type VARCHAR(50) NOT NULL DEFAULT '',
            asset_name VARCHAR(255) NOT NULL DEFAULT '',
            asset_html LONGTEXT NOT NULL DEFAULT '',
            asset_html_prev LONGTEXT NOT NULL DEFAULT '',
            is_generated TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_profile (profile_id),
            INDEX idx_user (user_id),
            INDEX idx_type (asset_type)
        ) {$charset};",
    ];
}

function bntm_bae_get_shortcodes() {
    return [
        'bntm_bae_dashboard' => 'bntm_shortcode_bae',
        'bntm_bae_kit'       => 'bntm_shortcode_bae_kit',
    ];
}

function bntm_bae_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_bae_get_tables();
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
    return count($tables);
}



// =============================================================================
// AJAX ACTION HOOKS
// =============================================================================

add_action('wp_ajax_bae_save_profile',           'bntm_ajax_bae_save_profile');
add_action('wp_ajax_nopriv_bae_save_profile',    'bntm_ajax_bae_save_profile');
add_action('wp_ajax_bae_generate_asset',         'bntm_ajax_bae_generate_asset');
add_action('wp_ajax_bae_delete_asset',           'bntm_ajax_bae_delete_asset');
add_action('wp_ajax_bae_reset_profile',          'bntm_ajax_bae_reset_profile');
add_action('wp_ajax_bae_save_kit_settings',      'bntm_ajax_bae_save_kit_settings');
add_action('wp_ajax_nopriv_bae_save_kit_settings','bntm_ajax_bae_save_kit_settings');
add_action('wp_ajax_bae_custom_generate',        'bntm_ajax_bae_custom_generate');
add_action('wp_ajax_nopriv_bae_custom_generate', 'bntm_ajax_bae_custom_generate');
add_action('wp_ajax_bae_mark_asset_viewed',        'bntm_ajax_bae_mark_asset_viewed');
add_action('wp_ajax_nopriv_bae_mark_asset_viewed', 'bntm_ajax_bae_mark_asset_viewed');
add_action('wp_ajax_bae_save_custom_asset',        'bntm_ajax_bae_save_custom_asset');
add_action('wp_ajax_nopriv_bae_save_custom_asset', 'bntm_ajax_bae_save_custom_asset');
add_action('wp_ajax_bae_wizard_palettes',          'bntm_ajax_bae_wizard_palettes');
add_action('wp_ajax_nopriv_bae_wizard_palettes',   'bntm_ajax_bae_wizard_palettes');
add_action('wp_ajax_bae_wizard_taglines',          'bntm_ajax_bae_wizard_taglines');
add_action('wp_ajax_nopriv_bae_wizard_taglines',   'bntm_ajax_bae_wizard_taglines');
add_action('wp_ajax_bae_suggest_colors',           'bntm_ajax_bae_suggest_colors');
add_action('wp_ajax_nopriv_bae_suggest_colors',    'bntm_ajax_bae_suggest_colors');
add_action('wp_ajax_bae_suggest_tagline',          'bntm_ajax_bae_suggest_tagline');
add_action('wp_ajax_nopriv_bae_suggest_tagline',   'bntm_ajax_bae_suggest_tagline');
add_action('wp_ajax_bae_social_captions',          'bntm_ajax_bae_social_captions');
add_action('wp_ajax_nopriv_bae_social_captions',   'bntm_ajax_bae_social_captions');
add_action('wp_ajax_bae_suggest_fonts',            'bntm_ajax_bae_suggest_fonts');
add_action('wp_ajax_bae_consistency_scan',         'bntm_ajax_bae_consistency_scan');
add_action('wp_ajax_bae_toolkit_checklist_save',   'bntm_ajax_bae_toolkit_checklist_save');
add_action('wp_ajax_bae_upload_logo',              'bntm_ajax_bae_upload_logo');
add_action('wp_ajax_bae_claim_ticket',             'bntm_ajax_bae_claim_ticket');
add_action('wp_ajax_nopriv_bae_claim_ticket',      'bntm_ajax_bae_claim_ticket');
add_action('wp_ajax_bae_beta_campaign_status',        'bntm_ajax_bae_beta_campaign_status');
add_action('wp_ajax_nopriv_bae_beta_campaign_status', 'bntm_ajax_bae_beta_campaign_status');
add_action('wp_ajax_nopriv_bae_upload_logo',       'bntm_ajax_bae_upload_logo');
add_action('wp_ajax_bae_export_zip',               'bntm_ajax_bae_export_zip');
add_action('wp_ajax_bae_undo_asset',               'bntm_ajax_bae_undo_asset');
add_action('wp_ajax_nopriv_bae_undo_asset',        'bntm_ajax_bae_undo_asset');
add_action('wp_ajax_bae_preview_asset',            'bntm_ajax_bae_preview_asset');
add_action('wp_ajax_nopriv_bae_preview_asset',     'bntm_ajax_bae_preview_asset');
add_action('wp_ajax_bae_autofix_consistency',      'bntm_ajax_bae_autofix_consistency');
add_action('wp_ajax_nopriv_bae_autofix_consistency','bntm_ajax_bae_autofix_consistency');

add_action('init', 'bae_register_blocks');

function bae_register_blocks() {
    $types = [
        'business-card',
        'letterhead',
        'email-signature',
        'social-kit',
        'brand-guideline', 
    ];

    foreach ($types as $type) {
        $path = BNTM_BAE_PATH . 'blocks/' . $type;
        if (file_exists($path . '/block.json')) {
            register_block_type($path, [
                'render_callback' => 'bae_render_block_' . str_replace('-', '_', $type),
            ]);
        }
    }
}


function bae_render_block_business_card($attributes, $content) {
    $user_id = get_current_user_id();
    $profile = bae_get_profile($user_id);
    if (!$profile) return '<p>No brand profile found.</p>';
    return bae_generate_asset_html('business_card', $profile);
}

function bae_render_block_letterhead($attributes, $content) {
    $user_id = get_current_user_id();
    $profile = bae_get_profile($user_id);
    if (!$profile) return '<p>No brand profile found.</p>';
    return bae_generate_asset_html('letterhead', $profile);
}

function bae_render_block_email_signature($attributes, $content) {
    $user_id = get_current_user_id();
    $profile = bae_get_profile($user_id);
    if (!$profile) return '<p>No brand profile found.</p>';
    return bae_generate_asset_html('email_signature', $profile);
}

function bae_render_block_social_kit($attributes, $content) {
    $user_id = get_current_user_id();
    $profile = bae_get_profile($user_id);
    if (!$profile) return '<p>No brand profile found.</p>';
    return bae_generate_asset_html('social_kit', $profile);
}

function bae_render_block_brand_guideline($attributes, $content) {
    $user_id = get_current_user_id();
    $profile = bae_get_profile($user_id);
    if (!$profile) return '<p>No brand profile found.</p>';
    return bae_generate_asset_html('brand_guidelines', $profile);
}


// =============================================================================
// HELPER: Sanitize and validate a hex color, with fallback
// CHANGED: Centralized color sanitization used at both save AND render time
// =============================================================================

function bae_safe_color($value, $fallback = '#000000') {
    $clean = sanitize_hex_color(trim($value));
    return $clean ?: $fallback;
}

function bae_get_ticket_cookie() {
    if (empty($_COOKIE['bae_ticket'])) return '';
    $raw = strtoupper(sanitize_text_field($_COOKIE['bae_ticket']));
    return preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw) ? $raw : '';
}

// Returns a stable anonymous session ID stored in a cookie.
// Used as identity for steps 1–3 before a ticket is claimed.
function bae_get_session_id() {
    if (!empty($_COOKIE['bae_session'])) {
        $raw = sanitize_text_field($_COOKIE['bae_session']);
        if (preg_match('/^[a-f0-9]{32}$/', $raw)) return $raw;
    }
    $sid = md5(uniqid('bae_', true) . wp_rand());
    // 30-day cookie, same path as ticket
    if (!headers_sent()) {
        setcookie('bae_session', $sid, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }
    $_COOKIE['bae_session'] = $sid;
    return $sid;
}

function bae_get_profile_by_ticket($ticket) {
    if (!$ticket) return null;
    global $wpdb;
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s ORDER BY updated_at DESC, created_at DESC, id DESC LIMIT 1",
            $ticket
        ),
        ARRAY_A
    );
    return $row ?: null;
}

function bae_beta_is_enabled() {
    return (int) get_option('bae_beta_enabled', 1) === 1;
}

function bae_beta_slots_remaining() {
    return max(0, (int) get_option('bae_beta_slots_remaining', 100));
}

function bae_beta_status_payload() {
    $enabled = bae_beta_is_enabled();
    $remaining = bae_beta_slots_remaining();
    $total = max(1, (int) get_option('bae_beta_slots_total', 100));
    return [
        'enabled' => $enabled,
        'total' => $total,
        'remaining' => $remaining,
        'available' => $enabled && $remaining > 0,
    ];
}

function bae_beta_try_consume_slot() {
    global $wpdb;
    $opt = $wpdb->options;
    $name = 'bae_beta_slots_remaining';
    $rows = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$opt}
             SET option_value = CAST(option_value AS UNSIGNED) - 1
             WHERE option_name = %s AND CAST(option_value AS UNSIGNED) > 0",
            $name
        )
    );
    if ($rows === 1) {
        wp_cache_delete($name, 'options');
        return true;
    }
    return false;
}

function bae_try_apply_beta_claim_to_profile($profile_id) {
    $pid = (int) $profile_id;
    if ($pid <= 0) return false;
    if (!bae_beta_is_enabled() || bae_beta_slots_remaining() <= 0) return false;
    if (!bae_beta_try_consume_slot()) return false;

    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    $updated = $wpdb->update(
        $table,
        [
            'plan' => 'pro',
            'beta_free_claimed' => 1,
            'beta_claimed_at' => current_time('mysql'),
        ],
        [
            'id' => $pid,
            'beta_free_claimed' => 0,
        ],
        ['%s', '%d', '%s'],
        ['%d', '%d']
    );

    if ($updated === 1) return true;

    // Refund consumed slot when update didn't apply (already claimed/race).
    update_option('bae_beta_slots_remaining', bae_beta_slots_remaining() + 1, false);
    return false;
}

function bntm_ajax_bae_beta_campaign_status() {
    check_ajax_referer('bae_claim_ticket', 'nonce', false);
    wp_send_json_success(bae_beta_status_payload());
}

function bae_maybe_migrate_beta_claim_columns() {
    if (get_option('bae_beta_schema_v1', '0') === '1') return;
    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    $wpdb->hide_errors();
    $col1 = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'beta_free_claimed'");
    if (empty($col1)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN beta_free_claimed TINYINT(1) NOT NULL DEFAULT 0 AFTER plan");
    }
    $col2 = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'beta_claimed_at'");
    if (empty($col2)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN beta_claimed_at DATETIME NULL AFTER beta_free_claimed");
    }
    $wpdb->show_errors();
    if (get_option('bae_beta_enabled', null) === null) update_option('bae_beta_enabled', 1, false);
    if (get_option('bae_beta_slots_remaining', null) === null) update_option('bae_beta_slots_remaining', 100, false);
    if (get_option('bae_beta_slots_total', null) === null) update_option('bae_beta_slots_total', 100, false);
    update_option('bae_beta_schema_v1', '1', false);
}
add_action('init', 'bae_maybe_migrate_beta_claim_columns');

function bae_make_unique_kit_slug($name, $exclude_id = 0) {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    $base = sanitize_title($name ?: 'brand') ?: 'brand';
    $slug = $base;
    $i = 2;
    while (true) {
        if ($exclude_id) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE kit_slug = %s AND id != %d LIMIT 1", $slug, $exclude_id));
        } else {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE kit_slug = %s LIMIT 1", $slug));
        }
        if (!$exists) return $slug;
        $slug = $base . '-' . $i;
        $i++;
    }
}

function bae_hex_to_rgb($hex) {
    $hex = ltrim(bae_safe_color($hex), '#');
    if (strlen($hex) !== 6) return ['r' => 0, 'g' => 0, 'b' => 0];
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2)),
    ];
}

function bae_rgb_to_hex($r, $g, $b) {
    $r = max(0, min(255, (int) round($r)));
    $g = max(0, min(255, (int) round($g)));
    $b = max(0, min(255, (int) round($b)));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function bae_color_luminance_score($hex) {
    $rgb = bae_hex_to_rgb($hex);
    return (int) round(($rgb['r'] * 0.299) + ($rgb['g'] * 0.587) + ($rgb['b'] * 0.114));
}

function bae_color_hue_deg($hex) {
    $rgb = bae_hex_to_rgb($hex);
    $r = $rgb['r'] / 255;
    $g = $rgb['g'] / 255;
    $b = $rgb['b'] / 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $delta = $max - $min;

    if ($delta == 0.0) return 0.0;

    if ($max === $r) {
        $h = fmod((($g - $b) / $delta), 6);
    } elseif ($max === $g) {
        $h = (($b - $r) / $delta) + 2;
    } else {
        $h = (($r - $g) / $delta) + 4;
    }

    $deg = $h * 60;
    return $deg < 0 ? $deg + 360 : $deg;
}

function bae_hue_difference($hex_a, $hex_b) {
    $diff = abs(bae_color_hue_deg($hex_a) - bae_color_hue_deg($hex_b));
    return min($diff, 360 - $diff);
}

function bae_adjust_secondary_contrast($primary, $secondary, $min_diff = 30) {
    $primary = bae_safe_color($primary, '#1a1a2e');
    $secondary = bae_safe_color($secondary, '#16213e');
    $primary_lum = bae_color_luminance_score($primary);
    $secondary_lum = bae_color_luminance_score($secondary);

    if (abs($primary_lum - $secondary_lum) >= $min_diff) {
        return $secondary;
    }

    $rgb = bae_hex_to_rgb($secondary);
    $direction = $primary_lum >= 128 ? -1 : 1;
    $delta = max($min_diff, 36);

    return bae_rgb_to_hex(
        $rgb['r'] + ($direction * $delta),
        $rgb['g'] + ($direction * $delta),
        $rgb['b'] + ($direction * $delta)
    );
}

function bae_rotate_accent_hue($hex, $degrees = 45) {
    $rgb = bae_hex_to_rgb($hex);
    $r = $rgb['r'] / 255;
    $g = $rgb['g'] / 255;
    $b = $rgb['b'] / 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $delta = $max - $min;
    $l = ($max + $min) / 2;

    if ($delta == 0.0) {
        return bae_rgb_to_hex($rgb['r'] + 36, $rgb['g'] - 18, $rgb['b'] + 18);
    }

    $s = $delta / (1 - abs((2 * $l) - 1));

    if ($max === $r) {
        $h = fmod((($g - $b) / $delta), 6);
    } elseif ($max === $g) {
        $h = (($b - $r) / $delta) + 2;
    } else {
        $h = (($r - $g) / $delta) + 4;
    }

    $h = fmod((($h * 60) + $degrees + 360), 360);
    $c = (1 - abs((2 * $l) - 1)) * $s;
    $x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
    $m = $l - ($c / 2);

    if ($h < 60) {
        [$rp, $gp, $bp] = [$c, $x, 0];
    } elseif ($h < 120) {
        [$rp, $gp, $bp] = [$x, $c, 0];
    } elseif ($h < 180) {
        [$rp, $gp, $bp] = [0, $c, $x];
    } elseif ($h < 240) {
        [$rp, $gp, $bp] = [0, $x, $c];
    } elseif ($h < 300) {
        [$rp, $gp, $bp] = [$x, 0, $c];
    } else {
        [$rp, $gp, $bp] = [$c, 0, $x];
    }

    return bae_rgb_to_hex(
        ($rp + $m) * 255,
        ($gp + $m) * 255,
        ($bp + $m) * 255
    );
}

function bae_normalize_palette_colors($primary, $secondary, $accent) {
    $primary = bae_safe_color($primary, '#1a1a2e');
    $secondary = bae_adjust_secondary_contrast($primary, $secondary, 30);
    $accent = bae_safe_color($accent, '#e94560');

    if (bae_hue_difference($primary, $accent) < 30) {
        $accent = bae_rotate_accent_hue($accent, 45);
    }

    return [
        'primary' => $primary,
        'secondary' => $secondary,
        'accent' => $accent,
    ];
}

function bae_wizard_static_palettes() {
    return [
        [ 'name' => 'Bold',    'primary' => '#c4196a', 'secondary' => '#2d1066', 'accent' => '#F32D86', 'personality' => 'Bold, premium, innovative',        'reason' => 'Strong contrast and vibrant energy make this a confident fit for brands that want to stand out.' ],
        [ 'name' => 'Pro',     'primary' => '#1d4ed8', 'secondary' => '#1e3a5f', 'accent' => '#38bdf8', 'personality' => 'Professional, trustworthy, reliable', 'reason' => 'Deep blue tones communicate credibility and calm confidence.' ],
        [ 'name' => 'Fresh',   'primary' => '#16a34a', 'secondary' => '#14532d', 'accent' => '#86efac', 'personality' => 'Natural, fresh, community-focused',   'reason' => 'Green signals growth, health, and authenticity.' ],
        [ 'name' => 'Warm',    'primary' => '#ea580c', 'secondary' => '#7c2d12', 'accent' => '#fb923c', 'personality' => 'Warm, energetic, approachable',       'reason' => 'Energetic oranges feel welcoming and enthusiastic.' ],
        [ 'name' => 'Luxury',  'primary' => '#b45309', 'secondary' => '#451a03', 'accent' => '#fbbf24', 'personality' => 'Luxury, refined, classic',            'reason' => 'Gold and amber tones evoke prestige and timeless quality.' ],
        [ 'name' => 'Playful', 'primary' => '#db2777', 'secondary' => '#831843', 'accent' => '#f9a8d4', 'personality' => 'Playful, feminine, creative',        'reason' => 'Pinks and roses feel joyful, creative, and approachable.' ],
        [ 'name' => 'Minimal', 'primary' => '#404040', 'secondary' => '#0a0a0a', 'accent' => '#a3a3a3', 'personality' => 'Minimal, modern, clean',             'reason' => 'Monochrome palette lets your content breathe.' ],
        [ 'name' => 'Tech',    'primary' => '#0f172a', 'secondary' => '#020617', 'accent' => '#6366f1', 'personality' => 'Tech-forward, analytical, precise',  'reason' => 'Dark navy with indigo accents feels modern and precise.' ],
    ];
}

function bae_extract_json_array($text) {
    if ( ! is_string( $text ) || $text === '' ) {
        return '';
    }

    $text = trim( $text );
    if ( strpos( $text, '[' ) === false || strpos( $text, ']' ) === false ) {
        return $text;
    }

    if ( preg_match( '/\[[\s\S]*\]/', $text, $m ) ) {
        return trim( $m[0] );
    }

    return $text;
}

// =============================================================================
// MAIN DASHBOARD SHORTCODE
// =============================================================================

// =============================================================================
// WIZARD: Guided onboarding — shown to new users before any profile exists
// One question per screen, Pomelli-style. Zero design knowledge needed.
// Saves via existing bae_save_profile AJAX — no new backend needed.
// =============================================================================

function bae_wizard_shortcode($user_id) {
    $nonce = wp_create_nonce('bae_save_profile');
    $generate_nonce = wp_create_nonce('bae_generate_asset');
    ob_start();
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

    <style>
    .bae-wiz-wrap * { box-sizing: border-box; margin: 0; padding: 0; }
    .bae-wiz-wrap {
        font-family: 'Geist', -apple-system, sans-serif;
        background: linear-gradient(180deg, #09090e 0%, #141423 100%);
        color: #ede9ff;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 24px;
        position: relative;
        overflow: hidden;
        border-radius: 0;
    }
    .bae-wiz-wrap.bae-light {
        background: linear-gradient(180deg, #fcfcf9 0%, #f4f3ff 45%, #eae7f2 100%);
        color: #1d1a16;
    }
    .bae-wiz-wrap::before {
        content: '';
        position: absolute;
        width: 600px; height: 600px; border-radius: 50%;
        background: radial-gradient(circle, rgba(243,45,134,0.1) 0%, transparent 70%);
        top: -200px; right: -100px; pointer-events: none;
    }
    .bae-wiz-wrap::after {
        content: '';
        position: absolute;
        width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(243,45,134,0.07) 0%, transparent 70%);
        bottom: -100px; left: -100px; pointer-events: none;
    }
    .bae-wiz-progress-bar {
        position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: rgba(255,255,255,0.06);
    }
    .bae-wiz-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #c4196a, #F32D86);
        border-radius: 0 3px 3px 0;
        transition: width 0.5s cubic-bezier(0.16,1,0.3,1);
        width: 0%;
    }
    .bae-wiz-step-label {
        position: absolute; top: 20px; left: 50%; transform: translateX(-50%);
        font-size: 11px; font-weight: 700; color: #4d4a65;
        text-transform: uppercase; letter-spacing: 0.12em; white-space: nowrap;
    }
    /* ── WIZ STEP 1 ANIMATED GRADIENT CANVAS ── */
    #bae-wiz-gradient-canvas {
        position: absolute; inset: 0; z-index: 0;
        pointer-events: none; overflow: hidden;
        transition: opacity 0.4s;
    }
    .bae-wiz-wrap.bae-light #bae-wiz-gradient-canvas { opacity: 0.25; }
    .bae-wiz-orb {
        position: absolute; border-radius: 50%;
        filter: blur(72px); opacity: 0; pointer-events: none;
        will-change: transform, opacity;
    }
    .bae-wiz-orb-1 { width: 520px; height: 520px; background: radial-gradient(circle, rgba(195,25,106,0.55) 0%, transparent 70%); top: -120px; left: -100px; }
    .bae-wiz-orb-2 { width: 420px; height: 420px; background: radial-gradient(circle, rgba(243,45,134,0.45) 0%, transparent 70%); bottom: -80px; right: -60px; }
    .bae-wiz-orb-3 { width: 300px; height: 300px; background: radial-gradient(circle, rgba(243,45,134,0.35) 0%, transparent 70%); top: 40%; left: 60%; }
    .bae-wiz-orb-4 { width: 250px; height: 250px; background: radial-gradient(circle, rgba(243,45,134,0.3) 0%, transparent 70%); top: 20%; right: 25%; }
    /* ── WIZ TOP BUTTONS ── */
    .bae-wiz-top-actions {
        position: absolute; top: 14px; right: 16px;
        display: flex; align-items: center; gap: 8px; z-index: 10;
    }
    .bae-wiz-skip {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; font-weight: 600; color: rgba(237,233,255,0.55);
        text-decoration: none; font-family: 'Geist', sans-serif;
        padding: 7px 14px; border-radius: 9px;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.04);
        transition: all 0.2s; backdrop-filter: blur(8px);
        white-space: nowrap;
    }
    .bae-wiz-skip:hover { color: #ede9ff; border-color: rgba(243,45,134,0.4); background: rgba(243,45,134,0.1); }
    .bae-wiz-wrap.bae-light .bae-wiz-skip {
        color: rgba(30,20,40,0.6);
        border-color: rgba(0,0,0,0.15);
        background: rgba(0,0,0,0.04);
    }
    .bae-wiz-wrap.bae-light .bae-wiz-skip:hover {
        color: #111;
        border-color: rgba(243,45,134,0.4);
        background: rgba(243,45,134,0.08);
    }
    .bae-wiz-token-btn {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 700; color: #ede9ff;
        font-family: 'Geist', sans-serif; cursor: pointer;
        padding: 7px 14px; border-radius: 9px;
        border: 1px solid rgba(243,45,134,0.5);
        background: linear-gradient(135deg, rgba(195,25,106,0.35), rgba(243,45,134,0.25));
        transition: all 0.2s; backdrop-filter: blur(8px);
        white-space: nowrap;
    }
    .bae-wiz-token-btn:hover { border-color: rgba(243,45,134,0.8); background: linear-gradient(135deg, rgba(195,25,106,0.55), rgba(243,45,134,0.45)); box-shadow: 0 4px 18px rgba(195,25,106,0.35); }
    .bae-wiz-wrap.bae-light .bae-wiz-token-btn {
        color: #1a0a12;
        border-color: rgba(243,45,134,0.4);
        background: rgba(243,45,134,0.08);
    }
    .bae-wiz-wrap.bae-light .bae-wiz-token-btn:hover {
        background: rgba(243,45,134,0.16);
        border-color: rgba(243,45,134,0.7);
    }
    /* OR separator */
    .bae-wiz-or-sep {
        display: flex; align-items: center; gap: 12px;
        margin: 16px 0 12px;
    }
    .bae-wiz-or-line {
        flex: 1; height: 1px;
        background: rgba(255,255,255,0.12);
    }
    .bae-wiz-wrap.bae-light .bae-wiz-or-line {
        background: rgba(0,0,0,0.12);
    }
    .bae-wiz-or-text {
        font-size: 11px; font-weight: 600;
        color: rgba(255,255,255,0.3);
        letter-spacing: 0.08em; text-transform: uppercase;
        font-family: 'Geist', sans-serif;
    }
    .bae-wiz-wrap.bae-light .bae-wiz-or-text {
        color: rgba(0,0,0,0.3);
    }
    /* Token btn when below input - full width style */
    .bae-wiz-screen .bae-wiz-token-btn {
        width: 100%; justify-content: center;
        font-weight: 600; font-size: 13px;
    }
    /* ── TOKEN MODAL ── */
    .bae-wiz-token-overlay {
        position: fixed; inset: 0; z-index: 999999;
        background: rgba(0,0,0,0.75); backdrop-filter: blur(10px);
        display: flex; align-items: center; justify-content: center; padding: 24px;
        opacity: 0; visibility: hidden; transition: opacity 0.25s, visibility 0.25s;
    }
    .bae-wiz-token-overlay.open { opacity: 1; visibility: visible; }
    .bae-wiz-token-modal {
        background: #1c1c26; border: 1px solid rgba(243,45,134,0.25);
        border-radius: 20px; width: 100%; max-width: 420px;
        box-shadow: 0 24px 80px rgba(0,0,0,0.7), 0 0 0 1px rgba(243,45,134,0.1);
        overflow: hidden; transform: translateY(16px); transition: transform 0.3s cubic-bezier(0.16,1,0.3,1);
    }
    .bae-wiz-token-overlay.open .bae-wiz-token-modal { transform: translateY(0); }
    .bae-wiz-token-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 22px; border-bottom: 1px solid rgba(255,255,255,0.07);
        background: linear-gradient(135deg, rgba(195,25,106,0.12), rgba(243,45,134,0.06));
    }
    .bae-wiz-token-modal-title {
        font-family: 'Instrument Serif', serif; font-size: 17px; font-style: italic; color: #ede9ff;
    }
    .bae-wiz-token-modal-close {
        background: rgba(255,255,255,0.07); border: none; width: 30px; height: 30px;
        border-radius: 8px; cursor: pointer; color: #9390a8; font-size: 18px;
        display: flex; align-items: center; justify-content: center; transition: all 0.2s;
    }
    .bae-wiz-token-modal-close:hover { background: rgba(255,255,255,0.12); color: #ede9ff; }
    .bae-wiz-token-modal-body { padding: 24px 22px; }
    .bae-wiz-token-modal-icon {
        width: 48px; height: 48px; border-radius: 14px; margin: 0 auto 16px;
        background: linear-gradient(135deg, #c4196a, #F32D86);
        display: flex; align-items: center; justify-content: center;
    }
    .bae-wiz-token-modal-desc { font-size: 13px; color: #9390a8; margin-bottom: 20px; line-height: 1.6; text-align: center; }
    .bae-wiz-token-input-wrap { position: relative; margin-bottom: 14px; }
    .bae-wiz-token-input {
        width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(243,45,134,0.3);
        border-radius: 12px; padding: 14px 18px; font-size: 16px; font-family: 'Geist', sans-serif;
        color: #ede9ff; outline: none; text-align: center; letter-spacing: 0.08em; font-weight: 600;
        text-transform: uppercase; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .bae-wiz-token-input:focus { border-color: #F32D86; box-shadow: 0 0 0 3px rgba(243,45,134,0.15); }
    .bae-wiz-token-input::placeholder { color: #4d4a65; font-weight: 400; letter-spacing: normal; text-transform: none; }
    .bae-wiz-token-submit {
        width: 100%; background: linear-gradient(135deg, #c4196a, #F32D86); color: white;
        border: none; border-radius: 12px; padding: 14px; font-size: 15px; font-weight: 700;
        font-family: 'Geist', sans-serif; cursor: pointer; transition: all 0.2s;
        box-shadow: 0 8px 24px rgba(195,25,106,0.4);
    }
    .bae-wiz-token-submit:hover { transform: translateY(-1px); box-shadow: 0 12px 32px rgba(195,25,106,0.5); }
    .bae-wiz-token-submit:disabled { opacity: 0.4; cursor: not-allowed; transform: none; box-shadow: none; }
    .bae-wiz-token-err { font-size: 12px; color: #fb7185; text-align: center; margin-top: 10px; display: none; }
    .bae-wiz-token-success { font-size: 13px; color: #34d399; text-align: center; margin-top: 10px; display: none; }
    .bae-wiz-screen {
        width: 100%; max-width: 540px;
        text-align: center;
        position: relative; z-index: 1;
    }
    .bae-wiz-question {
        font-family: 'Instrument Serif', serif;
        font-size: clamp(22px, 5vw, 34px);
        font-style: italic; font-weight: 400;
        color: #ede9ff; line-height: 1.25;
        margin-bottom: 8px;
    }
    .bae-wiz-hint { font-size: 14px; color: #4d4a65; margin-bottom: 32px; line-height: 1.6; }
    .bae-wiz-input {
        width: 100%;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(243,45,134,0.25);
        border-radius: 14px; padding: 16px 20px;
        font-size: 17px; font-family: 'Geist', sans-serif;
        color: #ede9ff; outline: none; text-align: center;
        transition: border-color 0.2s, box-shadow 0.2s;
        margin-bottom: 18px; display: block;
    }
    .bae-wiz-input:focus { border-color: #F32D86; box-shadow: 0 0 0 3px rgba(243,45,134,0.15); }
    .bae-wiz-input::placeholder { color: #4d4a65; }
    .bae-wiz-wrap.bae-light .bae-wiz-input { background: rgba(255,255,255,0.82); border-color: rgba(243,45,134,0.3); color: #1d1a16; }
    .bae-wiz-wrap.bae-light .bae-wiz-input::placeholder { color: #6b6880; }
    .bae-wiz-wrap.bae-light .bae-wiz-tile { background: rgba(255,255,255,0.72); border-color: rgba(28,20,40,0.14); }
    .bae-wiz-wrap.bae-light .bae-wiz-tile:hover { border-color: rgba(243,45,134,0.4); background: rgba(243,45,134,0.12); }
    .bae-wiz-wrap.bae-light .bae-wiz-tile-icon { color: #1d1a16; opacity: 0.85; }
    .bae-wiz-wrap.bae-light .bae-wiz-tile-label { color: #1d1a16; }
    .bae-wiz-wrap.bae-light .bae-wiz-tile-desc { color: #5c586d; }
    .bae-wiz-wrap.bae-light .bae-wiz-hint { color: #6b6880; }
    .bae-wiz-wrap.bae-light .bae-wiz-question, .bae-wiz-wrap.bae-light .bae-wiz-tagline-opt { color: #1d1a16; }
    .bae-wiz-brand {
        display: inline-flex;
        align-items: flex-end;
        justify-content: center;
        margin-bottom: 20px;
        transform: scale(.88);
        transform-origin: center bottom;
    }
    .bae-wiz-brand .bae-brand-wordmark { color: inherit; }
    .bae-wiz-brand .bae-brand-wordmark-logo { width: 1.7em; }
    .bae-wiz-wrap .bae-brand-logo-img { filter: invert(1); }
    .bae-wiz-wrap.bae-light .bae-brand-logo-img { filter: none; }
    .bae-wiz-tiles {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 10px; margin-bottom: 22px;
    }
    .bae-wiz-tile {
        background: rgba(255,255,255,0.04);
        border: 1.5px solid rgba(255,255,255,0.08);
        border-radius: 14px; padding: 18px 14px;
        cursor: pointer; transition: all 0.2s; text-align: left;
    }
    .bae-wiz-tile:hover { border-color: rgba(243,45,134,0.4); background: rgba(243,45,134,0.08); }
    .bae-wiz-tile.selected { border-color: #F32D86; background: rgba(243,45,134,0.15); box-shadow: 0 0 0 3px rgba(243,45,134,0.12); }
    .bae-wiz-tile-icon { width: 28px; height: 28px; margin-bottom: 7px; display: flex; align-items: center; justify-content: center; color: #ede9ff; opacity: 0.85; }
    .bae-wiz-tile-label { font-size: 13px; font-weight: 700; color: #ede9ff; margin-bottom: 2px; }
    .bae-wiz-tile-desc { font-size: 11px; color: #4d4a65; }
    .bae-wiz-color-tiles {
        display: grid; grid-template-columns: repeat(4,1fr);
        gap: 8px; margin-bottom: 22px;
    }
    .bae-wiz-color-tile {
        border-radius: 12px; padding: 14px 6px 10px;
        cursor: pointer; border: 2px solid transparent;
        transition: all 0.2s; text-align: center;
    }
    .bae-wiz-color-tile:hover { transform: translateY(-3px); }
    .bae-wiz-color-tile.selected { border-color: white; transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.4); }
    .bae-wiz-color-swatch { width: 44px; height: 44px; border-radius: 10px; margin: 0 auto 6px; border: 1px solid rgba(255,255,255,0.15); }
    .bae-wiz-color-name { font-size: 11px; font-weight: 700; color: #ede9ff; }
    .bae-wiz-taglines { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
    .bae-wiz-tagline-opt {
        background: rgba(255,255,255,0.04);
        border: 1.5px solid rgba(255,255,255,0.08);
        border-radius: 12px; padding: 14px 18px;
        cursor: pointer; font-size: 14px; font-style: italic;
        color: #8b88a4; transition: all 0.2s; text-align: left;
    }
    .bae-wiz-tagline-opt:hover { border-color: rgba(243,45,134,0.4); color: #ede9ff; }
    .bae-wiz-tagline-opt.selected { border-color: #F32D86; color: #ede9ff; background: rgba(243,45,134,0.1); }
    .bae-wiz-tagline-custom {
        width: 100%; background: rgba(255,255,255,0.04);
        border: 1.5px dashed rgba(243,45,134,0.3);
        border-radius: 12px; padding: 12px 16px;
        font-size: 13px; font-family: 'Geist', sans-serif;
        color: #ede9ff; outline: none; transition: border-color 0.2s;
    }
    .bae-wiz-tagline-custom:focus { border-color: #F32D86; border-style: solid; }
    .bae-wiz-tagline-custom::placeholder { color: #4d4a65; }
    .bae-wiz-next {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        background: linear-gradient(135deg, #c4196a, #F32D86);
        color: white; border: none; border-radius: 13px;
        padding: 15px 32px; font-size: 15px; font-weight: 700;
        font-family: 'Geist', sans-serif; cursor: pointer;
        transition: all 0.2s; box-shadow: 0 8px 28px rgba(195,25,106,0.4);
        width: 100%; margin-top: 4px;
    }
    .bae-wiz-next:hover { transform: translateY(-2px); box-shadow: 0 12px 36px rgba(195,25,106,0.5); }
    .bae-wiz-next:disabled { opacity: 0.35; cursor: not-allowed; transform: none; box-shadow: none; }

    .bae-wiz-back {
        display: inline-flex; align-items: center; gap: 6px;
        background: none; border: none; color: #4d4a65;
        font-size: 13px; font-weight: 500; cursor: pointer;
        margin-top: 14px; font-family: 'Geist', sans-serif; transition: color 0.2s;
    }
    .bae-wiz-back:hover { color: #8b88a4; }
    .bae-wiz-error { font-size: 12px; color: #fb7185; margin-bottom: 12px; display: none; margin-top: -8px; }
    .bae-wiz-generating { text-align: center; }
    .bae-wiz-spinner {
        width: 52px; height: 52px;
        border: 3px solid rgba(243,45,134,0.2);
        border-top-color: #F32D86; border-radius: 50%;
        margin: 0 auto 24px;
        animation: bae-wiz-spin 0.9s linear infinite;
    }
    @keyframes bae-wiz-spin { to { transform: rotate(360deg); } }
    .bae-wiz-gen-title { font-family: 'Instrument Serif', serif; font-size: 26px; font-style: italic; color: #ede9ff; margin-bottom: 8px; }
    .bae-wiz-gen-sub { font-size: 13px; color: #4d4a65; }
    @media (max-width: 480px) {
        .bae-wiz-color-tiles { grid-template-columns: repeat(2,1fr); }
        .bae-wiz-question { font-size: 20px; }
    }
    </style>

    <div class="bae-wiz-wrap" id="bae-wiz-wrap">
        <!-- Animated GSAP gradient orbs -->
        <div id="bae-wiz-gradient-canvas">
            <div class="bae-wiz-orb bae-wiz-orb-1" id="bae-orb-1"></div>
            <div class="bae-wiz-orb bae-wiz-orb-2" id="bae-orb-2"></div>
            <div class="bae-wiz-orb bae-wiz-orb-3" id="bae-orb-3"></div>
            <div class="bae-wiz-orb bae-wiz-orb-4" id="bae-orb-4"></div>
        </div>
        <div class="bae-wiz-progress-bar"><div class="bae-wiz-progress-fill" id="bae-wiz-progress"></div></div>
        <div class="bae-wiz-step-label" id="bae-wiz-step-label">Step 1 of 5</div>
        <!-- Top action buttons - skip only -->
        <div class="bae-wiz-top-actions">
            <a href="?tab=overview" class="bae-wiz-skip">Skip &rarr;</a>
        </div>

        <!-- Step 1: Name -->
        <div class="bae-wiz-screen" id="bae-step-1">
            <div class="bae-wiz-question">What's your business name?</div>
            <div class="bae-wiz-hint">This will appear on all your brand assets.</div>
            <input type="text" class="bae-wiz-input" id="bae-wiz-name" placeholder="e.g. Dela Cruz Bakery" autocomplete="off">
            <div class="bae-wiz-error" id="bae-wiz-name-err">Please enter your business name.</div>
            <button class="bae-wiz-next" onclick="baeWizGo(2)">Continue &rarr;</button>
            <!-- OR separator + token button -->
            <div class="bae-wiz-or-sep">
                <span class="bae-wiz-or-line"></span>
                <span class="bae-wiz-or-text">or</span>
                <span class="bae-wiz-or-line"></span>
            </div>
            <button class="bae-wiz-token-btn" onclick="baeWizTokenOpen()">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Already have a token? Enter it here
            </button>
        </div>

        <!-- Step 2: Industry -->
        <div class="bae-wiz-screen" id="bae-step-2" style="display:none;">
            <div class="bae-wiz-question">What does your business do?</div>
            <div class="bae-wiz-hint">Pick the one that fits best.</div>
            <div class="bae-wiz-tiles" id="bae-wiz-industry-tiles">
                <div class="bae-wiz-tile" data-value="Food &amp; Beverage" onclick="baeWizSelectTile(this)"><span class="bae-wiz-tile-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg></span><div class="bae-wiz-tile-label">Food &amp; Drinks</div><div class="bae-wiz-tile-desc">Restaurant, bakery, cafe</div></div>
                <div class="bae-wiz-tile" data-value="Retail &amp; Commerce" onclick="baeWizSelectTile(this)"><span class="bae-wiz-tile-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span><div class="bae-wiz-tile-label">Retail &amp; Selling</div><div class="bae-wiz-tile-desc">Store, shop, e-commerce</div></div>
                <div class="bae-wiz-tile" data-value="Professional Services" onclick="baeWizSelectTile(this)"><span class="bae-wiz-tile-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></span><div class="bae-wiz-tile-label">Services</div><div class="bae-wiz-tile-desc">Freelance, consulting, agency</div></div>
                <div class="bae-wiz-tile" data-value="Health &amp; Wellness" onclick="baeWizSelectTile(this)"><span class="bae-wiz-tile-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span><div class="bae-wiz-tile-label">Health &amp; Beauty</div><div class="bae-wiz-tile-desc">Clinic, salon, spa</div></div>
                <div class="bae-wiz-tile" data-value="Technology" onclick="baeWizSelectTile(this)"><span class="bae-wiz-tile-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></span><div class="bae-wiz-tile-label">Tech &amp; Digital</div><div class="bae-wiz-tile-desc">App, software, IT</div></div>
                <div class="bae-wiz-tile" data-value="Education &amp; Training" onclick="baeWizSelectTile(this)"><span class="bae-wiz-tile-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg></span><div class="bae-wiz-tile-label">Education</div><div class="bae-wiz-tile-desc">School, tutoring, coaching</div></div>
                <div class="bae-wiz-tile" data-value="Creative &amp; Media" onclick="baeWizSelectTile(this)"><span class="bae-wiz-tile-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r="0.5" fill="currentColor"/><circle cx="17.5" cy="10.5" r="0.5" fill="currentColor"/><circle cx="8.5" cy="7.5" r="0.5" fill="currentColor"/><circle cx="6.5" cy="12.5" r="0.5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg></span><div class="bae-wiz-tile-label">Creative &amp; Media</div><div class="bae-wiz-tile-desc">Design, photography</div></div>
                <div class="bae-wiz-tile" data-value="Other" onclick="baeWizSelectTile(this)"><span class="bae-wiz-tile-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg></span><div class="bae-wiz-tile-label">Something Else</div><div class="bae-wiz-tile-desc">My business is unique</div></div>
            </div>
            <div class="bae-wiz-error" id="bae-wiz-industry-err">Please pick what your business does.</div>
            <button class="bae-wiz-next" id="bae-wiz-next-2" onclick="baeWizGo(3)" disabled>Continue &rarr;</button>
            <button class="bae-wiz-back" onclick="baeWizGo(1)">&#8592; Back</button>
        </div>

        <!-- Step 3: Color Palette (AI-powered) -->
        <div class="bae-wiz-screen" id="bae-step-3" style="display:none;">
            <div class="bae-wiz-question">Choose your brand colors</div>
            <div class="bae-wiz-hint" id="bae-wiz-palette-hint">Personalized palettes for your brand — loading...</div>

            <!-- AI loading state -->
            <div id="bae-wiz-palette-loading" style="display:flex;flex-direction:column;align-items:center;gap:14px;padding:32px 0;">
                <div style="display:flex;gap:8px;">
                    <div class="bae-wiz-palette-dot" style="width:10px;height:10px;border-radius:50%;background:#c4196a;animation:bae-wiz-bounce 1.2s ease-in-out infinite;"></div>
                    <div class="bae-wiz-palette-dot" style="width:10px;height:10px;border-radius:50%;background:#F32D86;animation:bae-wiz-bounce 1.2s ease-in-out 0.2s infinite;"></div>
                    <div class="bae-wiz-palette-dot" style="width:10px;height:10px;border-radius:50%;background:#F32D86;animation:bae-wiz-bounce 1.2s ease-in-out 0.4s infinite;"></div>
                </div>
                <div style="font-size:13px;color:#4d4a65;" id="bae-wiz-palette-loading-txt">Generating palettes for your brand...</div>
            </div>

            <!-- Palette tiles — filled by JS -->
            <div id="bae-wiz-palette-tiles" style="display:none;width:100%;"></div>
            <div class="bae-wiz-error" id="bae-wiz-palette-warning" style="display:none;"></div>

            <div class="bae-wiz-error" id="bae-wiz-vibe-err">Please pick a palette.</div>
            <button class="bae-wiz-next" id="bae-wiz-next-3" onclick="baeWizGo(4)" disabled>Continue &rarr;</button>
            <button class="bae-wiz-back" onclick="baeWizGo(2)">&#8592; Back</button>
        </div>

        <style>
        @keyframes bae-wiz-bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40% { transform: scale(1); opacity: 1; }
        }
        .bae-wiz-palette-card {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1.5px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 16px 18px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .bae-wiz-palette-card:hover { border-color: rgba(243,45,134,0.4); background: rgba(243,45,134,0.06); }
        .bae-wiz-palette-card.selected { border-color: #F32D86; background: rgba(243,45,134,0.12); box-shadow: 0 0 0 3px rgba(243,45,134,0.12); }
        .bae-wiz-palette-swatches { display: flex; gap: 6px; flex-shrink: 0; }
        .bae-wiz-palette-swatch { width: 28px; height: 44px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); }
        .bae-wiz-palette-info { flex: 1; min-width: 0; }
        .bae-wiz-palette-name { font-size: 14px; font-weight: 700; color: #ede9ff; margin-bottom: 3px; }
        .bae-wiz-palette-reason { font-size: 12px; color: #4d4a65; line-height: 1.5; }
        .bae-wiz-palette-check { width: 20px; height: 20px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.15); flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .bae-wiz-palette-card.selected .bae-wiz-palette-check { background: #F32D86; border-color: #F32D86; }
        .bae-wiz-palette-ai-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 10px; font-weight: 700; color: #f76fb0;
            background: rgba(243,45,134,0.12); border: 1px solid rgba(243,45,134,0.2);
            border-radius: 999px; padding: 2px 8px; margin-bottom: 12px;
        }
        </style>

        <!-- Step 4: Tagline -->
        <div class="bae-wiz-screen" id="bae-step-4" style="display:none;">
            <div class="bae-wiz-question">Pick your tagline</div>
            <div class="bae-wiz-hint" id="bae-wiz-tagline-hint">AI-written taglines for your brand — loading...</div>

            <!-- AI loading dots -->
            <div id="bae-wiz-tagline-loading" style="display:flex;flex-direction:column;align-items:center;gap:14px;padding:24px 0;">
                <div style="display:flex;gap:8px;">
                    <div style="width:10px;height:10px;border-radius:50%;background:#c4196a;animation:bae-wiz-bounce 1.2s ease-in-out infinite;"></div>
                    <div style="width:10px;height:10px;border-radius:50%;background:#F32D86;animation:bae-wiz-bounce 1.2s ease-in-out 0.2s infinite;"></div>
                    <div style="width:10px;height:10px;border-radius:50%;background:#F32D86;animation:bae-wiz-bounce 1.2s ease-in-out 0.4s infinite;"></div>
                </div>
                <div style="font-size:13px;color:#4d4a65;">Writing taglines for your brand...</div>
            </div>

            <div class="bae-wiz-taglines" id="bae-wiz-tagline-opts" style="display:none;"></div>
            <input type="text" class="bae-wiz-tagline-custom" id="bae-wiz-tagline-custom" placeholder="Or write your own tagline here..." style="display:none;">
            <div class="bae-wiz-error" id="bae-wiz-tagline-err" style="margin-top:8px;">Please pick or write a tagline.</div>
            <button class="bae-wiz-next" style="margin-top:16px;" onclick="baeWizGo(5)">Continue &rarr;</button>
            <button class="bae-wiz-back" onclick="baeWizGo(3)">&#8592; Back</button>
        </div>

        <!-- Step 5: Contact -->
        <div class="bae-wiz-screen" id="bae-step-5" style="display:none;">
            <div class="bae-wiz-question">Almost done!</div>
            <div class="bae-wiz-hint">Add your contact info so it appears on your assets. All optional.</div>
            <input type="email" class="bae-wiz-input" id="bae-wiz-email" placeholder="Business email (optional)">
            <input type="text" class="bae-wiz-input" id="bae-wiz-phone" placeholder="Phone number (optional)">
            <input type="text" class="bae-wiz-input" id="bae-wiz-website" placeholder="Website (optional)">
            <button class="bae-wiz-next" onclick="baeWizSubmit()">Mothify My Brand &#9654;</button>
            <button class="bae-wiz-back" onclick="baeWizGo(4)">&#8592; Back</button>
        </div>

        <!-- Generating -->
        <div class="bae-wiz-screen bae-wiz-generating" id="bae-step-gen" style="display:none;">
            <div class="bae-wiz-spinner"></div>
            <div class="bae-wiz-gen-title">Mothifying your brand...</div>
            <div class="bae-wiz-gen-sub" id="bae-wiz-gen-status">Saving your profile</div>
        </div>

        <!-- Celebration screen -->
        <div class="bae-wiz-screen" id="bae-step-done" style="display:none;text-align:center;">
            <div id="bae-cel-icon" style="width:64px;height:64px;background:linear-gradient(135deg,#c4196a,#F32D86);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <div class="bae-wiz-question" style="margin-bottom:8px;" id="bae-cel-title">Your brand is mothified.</div>
            <div class="bae-wiz-hint" id="bae-cel-sub">All assets powered by your profile.<br>Let's see what we mothified.</div>
            <div id="bae-cel-swatches" style="display:flex;justify-content:center;gap:10px;margin:24px 0;"></div>
            <button class="bae-wiz-next" style="max-width:280px;margin:0 auto;" onclick="window.location.href=window.location.pathname+'?tab=assets'">
                Open Asset Generator
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </div>

        <!-- Token modal -->
        <div class="bae-wiz-token-overlay" id="bae-wiz-token-overlay" onclick="if(event.target===this)baeWizTokenClose()">
            <div class="bae-wiz-token-modal">
                <div class="bae-wiz-token-modal-header">
                    <div class="bae-wiz-token-modal-title">Enter your ticket</div>
                    <button type="button" class="bae-wiz-token-modal-close" onclick="baeWizTokenClose()" aria-label="Close">&times;</button>
                </div>
                <div class="bae-wiz-token-modal-body">
                    <div class="bae-wiz-token-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <div class="bae-wiz-token-modal-desc">Use your existing ticket to load your saved workspace.</div>
                    <div class="bae-wiz-token-input-wrap">
                        <input type="text" id="bae-wiz-token-field" class="bae-wiz-token-input" placeholder="BAE-XXXX-XXXX" maxlength="13" autocomplete="off" spellcheck="false">
                    </div>
                    <button type="button" id="bae-wiz-token-submit" class="bae-wiz-token-submit" onclick="baeWizTokenSubmit()">Continue</button>
                    <div id="bae-wiz-token-err" class="bae-wiz-token-err">Invalid token. Please try again.</div>
                    <div id="bae-wiz-token-success" class="bae-wiz-token-success">Token verified. Opening your workspace...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        document.querySelectorAll('div[style*="position:fixed"][style*="bottom:10px"][style*="right:10px"]').forEach(function(el) {
            var text = (el.textContent || '').replace(/\s+/g, ' ').trim();
            if (text.indexOf('CPU:') !== -1 && text.indexOf('Memory:') !== -1 && text.indexOf('Queries:') !== -1) {
                el.style.display = 'none';
            }
        });

        var ajaxurl = '<?php echo esc_js(admin_url("admin-ajax.php")); ?>';
        var nonce   = '<?php echo esc_js($nonce); ?>';
        var generateNonce = '<?php echo esc_js($generate_nonce); ?>';
        var state   = { step:1, name:'', industry:'', primary:'', secondary:'', accent:'', personality:'', tagline:'', email:'', phone:'', website:'' };

        // AI palette fetch state
        var palettePromise   = null;
        var palettesReady    = false;
        var palettesData     = null;

        // AI tagline fetch state
        var taglinePromise   = null;
        var taglinesReady    = false;
        var taglinesData     = null;

        // Theme state
        var baeWizIsDark = (localStorage.getItem('bae_theme') === 'dark');
        function baeWizApplyTheme(dark) {
            var wrap = document.getElementById('bae-wiz-wrap');
            var btn  = document.getElementById('bae-wiz-theme-btn');
            if (!wrap) return;
            if (dark) {
                wrap.classList.remove('bae-light');
                if (btn) btn.textContent = 'Light';
            } else {
                wrap.classList.add('bae-light');
                if (btn) btn.textContent = 'Dark';
            }
            try { localStorage.setItem('bae_theme', dark ? 'dark' : 'light'); } catch (e) {}
        }
        function baeWizToggleTheme() {
            baeWizIsDark = !baeWizIsDark;
            baeWizApplyTheme(baeWizIsDark);
        }

        // Remove the theme toggle button if still present (intentional no-theme UI)
        var themeToggle = document.getElementById('bae-wiz-theme-btn');
        if (themeToggle) themeToggle.remove();

        // Initialize wizard theme on open
        baeWizApplyTheme(baeWizIsDark);

        // ── GSAP STEP 1 ORB ANIMATIONS ──
        (function() {
            if (typeof gsap === 'undefined') return;
            var orbs = ['#bae-orb-1','#bae-orb-2','#bae-orb-3','#bae-orb-4'];
            var tl = gsap.timeline({ repeat: -1 });

            // Fade in orbs on step 1
            gsap.to(orbs, { opacity: 1, duration: 1.2, stagger: 0.2, ease: 'power2.out' });

            // Orb 1 — large purple, slow orbit top-left
            gsap.to('#bae-orb-1', {
                x: 80, y: 60, duration: 8, ease: 'sine.inOut', repeat: -1, yoyo: true
            });
            // Orb 2 — pink, slow orbit bottom-right
            gsap.to('#bae-orb-2', {
                x: -70, y: -50, duration: 10, ease: 'sine.inOut', repeat: -1, yoyo: true
            });
            // Orb 3 — small purple, middle-right drift
            gsap.to('#bae-orb-3', {
                x: -90, y: 40, duration: 7, ease: 'sine.inOut', repeat: -1, yoyo: true, delay: 1
            });
            // Orb 4 — accent, top-right shimmer
            gsap.to('#bae-orb-4', {
                x: 50, y: 60, duration: 9, ease: 'sine.inOut', repeat: -1, yoyo: true, delay: 2
            });

            // Pulse breathing on all orbs
            gsap.to(orbs, {
                scale: 1.12, duration: 4, ease: 'sine.inOut', repeat: -1, yoyo: true, stagger: 0.8
            });

            // Hide orbs when leaving step 1
            document.addEventListener('bae-wiz-step-change', function(e) {
                if (e.detail && e.detail.step !== 1) {
                    gsap.to(orbs, { opacity: 0, duration: 0.5 });
                } else {
                    gsap.to(orbs, { opacity: 1, duration: 0.8 });
                }
            });
        })();

        // ── TOKEN MODAL ──
        function baeWizTokenOpen() {
            var overlay = document.getElementById('bae-wiz-token-overlay');
            if (!overlay) return;
            // Move to body so it escapes wizard's overflow:hidden
            if (overlay.parentNode !== document.body) {
                document.body.appendChild(overlay);
            }
            overlay.classList.add('open');
            var field = document.getElementById('bae-wiz-token-field');
            if (field) { setTimeout(function(){ field.focus(); }, 200); }
        }
        function baeWizTokenClose() {
            var overlay = document.getElementById('bae-wiz-token-overlay');
            if (overlay) { overlay.classList.remove('open'); }
        }
        function baeWizTokenSubmit() {
            var field   = document.getElementById('bae-wiz-token-field');
            var btn     = document.getElementById('bae-wiz-token-submit');
            var errEl   = document.getElementById('bae-wiz-token-err');
            var succEl  = document.getElementById('bae-wiz-token-success');
            var ticket  = (field ? field.value.trim().toUpperCase() : '');
            if (!ticket) { if(errEl){errEl.style.display='block';errEl.textContent='Please enter your ticket code.';} return; }
            // Validate format before hitting server
            if (!/^(BAE|ADM)-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(ticket)) {
                if (errEl) { errEl.style.display='block'; errEl.textContent='Invalid format. Expected: BAE-XXXX-XXXX'; }
                return;
            }
            if (errEl)  errEl.style.display = 'none';
            if (succEl) succEl.style.display = 'none';
            if (btn) btn.disabled = true;

            // Use bae_ticket_check (validates existence) not bae_claim_ticket (creates/stamps)
            var fd = new FormData();
            fd.append('action', 'bae_ticket_check');
            fd.append('ticket', ticket);
            fd.append('nonce',  '<?php echo esc_js(wp_create_nonce("bae_claim_ticket")); ?>');
            fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    if (data && data.success) {
                        var d = data.data || {};
                        // Admin ticket — redirect to admin panel
                        if (d.is_admin) {
                            window.location.href = window.location.pathname + '?bae=admin';
                            return;
                        }
                        // Set ticket cookie then reload
                        var exp = new Date(); exp.setFullYear(exp.getFullYear() + 1);
                        document.cookie = 'bae_ticket=' + encodeURIComponent(ticket) + '; expires=' + exp.toUTCString() + '; path=/; SameSite=Lax';
                        if (succEl) { succEl.style.display='block'; }
                        setTimeout(function(){ window.location.reload(); }, 900);
                    } else {
                        if (errEl) { errEl.style.display='block'; errEl.textContent = (data && data.data && data.data.message) ? data.data.message : 'Ticket not found. Check your code and try again.'; }
                        if (btn) btn.disabled = false;
                    }
                })
                .catch(function(){ if(errEl){errEl.style.display='block';errEl.textContent='Connection error. Please try again.';} if(btn)btn.disabled=false; });
        }
        // Enter key and auto-format for token input
        (function(){
            var f = document.getElementById('bae-wiz-token-field');
            if (!f) return;
            f.addEventListener('input', function() {
                // Strip non-alphanumeric, uppercase, limit to 12 chars (BAE + 4 + 4)
                var raw = this.value.replace(/[^A-Z0-9]/gi, '').toUpperCase().substring(0, 12);
                var out = raw;
                if (raw.length >= 3) {
                    var p3 = raw.substring(0, 3);
                    if (p3 === 'BAE' || p3 === 'ADM') {
                        var rest = raw.substring(3);
                        out = rest.length <= 4 ? p3 + '-' + rest : p3 + '-' + rest.substring(0, 4) + '-' + rest.substring(4, 8);
                    }
                }
                this.value = out;
                var errEl = document.getElementById('bae-wiz-token-err');
                if (errEl) errEl.style.display = 'none';
            });
            f.addEventListener('keydown', function(e){ if(e.key==='Enter') baeWizTokenSubmit(); });
        })();
        window.baeWizTokenOpen = baeWizTokenOpen;
        window.baeWizTokenClose = baeWizTokenClose;
        window.baeWizTokenSubmit = baeWizTokenSubmit;

        // Static fallback palettes — used if AI fails or times out
        var staticPalettes = [
            { name:'Bold',    primary:'#c4196a', secondary:'#2d1066', accent:'#F32D86', personality:'Bold, premium, innovative',         reason:'Strong contrast and vibrant energy — great for brands that want to stand out.' },
            { name:'Pro',     primary:'#1d4ed8', secondary:'#1e3a5f', accent:'#38bdf8', personality:'Professional, trustworthy, reliable', reason:'Deep blue tones communicate credibility and calm confidence.' },
            { name:'Fresh',   primary:'#16a34a', secondary:'#14532d', accent:'#86efac', personality:'Natural, fresh, community-focused',   reason:'Green signals growth, health, and authenticity.' },
            { name:'Warm',    primary:'#ea580c', secondary:'#7c2d12', accent:'#fb923c', personality:'Warm, energetic, approachable',       reason:'Energetic oranges feel welcoming and enthusiastic.' },
            { name:'Luxury',  primary:'#b45309', secondary:'#451a03', accent:'#fbbf24', personality:'Luxury, refined, classic',           reason:'Gold and amber tones evoke prestige and timeless quality.' },
            { name:'Playful', primary:'#db2777', secondary:'#831843', accent:'#f9a8d4', personality:'Playful, feminine, creative',        reason:'Pinks and roses feel joyful, creative, and approachable.' },
            { name:'Minimal', primary:'#404040', secondary:'#0a0a0a', accent:'#a3a3a3', personality:'Minimal, modern, clean',             reason:'Monochrome palette — lets your content breathe.' },
            { name:'Tech',    primary:'#0f172a', secondary:'#020617', accent:'#6366f1', personality:'Tech-forward, analytical, precise',  reason:'Dark navy with indigo accents — modern and precise.' },
        ];

        function baeHexToRgb(hex) {
            hex = (hex || '').replace('#', '');
            if (hex.length !== 6) return null;
            return {
                r: parseInt(hex.slice(0, 2), 16),
                g: parseInt(hex.slice(2, 4), 16),
                b: parseInt(hex.slice(4, 6), 16)
            };
        }

        function baeColorLuminance(hex) {
            var rgb = baeHexToRgb(hex);
            if (!rgb) return 0;
            return Math.round((rgb.r * 0.299) + (rgb.g * 0.587) + (rgb.b * 0.114));
        }

        function baeColorHue(hex) {
            var rgb = baeHexToRgb(hex);
            if (!rgb) return 0;
            var r = rgb.r / 255, g = rgb.g / 255, b = rgb.b / 255;
            var max = Math.max(r, g, b), min = Math.min(r, g, b), delta = max - min;
            if (!delta) return 0;
            var h;
            if (max === r) h = ((g - b) / delta) % 6;
            else if (max === g) h = ((b - r) / delta) + 2;
            else h = ((r - g) / delta) + 4;
            h *= 60;
            return h < 0 ? h + 360 : h;
        }

        function baeHueDifference(a, b) {
            var diff = Math.abs(baeColorHue(a) - baeColorHue(b));
            return Math.min(diff, 360 - diff);
        }

        function baePaletteWarnings(primary, secondary, accent) {
            var warnings = [];
            if (Math.abs(baeColorLuminance(primary) - baeColorLuminance(secondary)) < 30) {
                warnings.push('Primary and secondary are very close in brightness.');
            }
            if (baeHueDifference(primary, accent) < 30) {
                warnings.push('Primary and accent are too close in hue.');
            }
            return warnings;
        }

        function baeRenderPaletteWarning(targetId, primary, secondary, accent) {
            var el = document.getElementById(targetId);
            if (!el) return;
            if (!primary || !secondary || !accent) {
                el.style.display = 'none';
                el.textContent = '';
                return;
            }
            var warnings = baePaletteWarnings(primary, secondary, accent);
            if (!warnings.length) {
                el.style.display = 'none';
                el.textContent = '';
                return;
            }
            el.style.display = 'block';
            el.textContent = warnings.join(' ');
        }

        function setProgress(s) {
            var pct = ((s-1)/5)*100;
            document.getElementById('bae-wiz-progress').style.width = pct + '%';
            document.getElementById('bae-wiz-step-label').textContent = 'Step ' + s + ' of 5';
        }
        function showScreen(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.style.display = 'block';
            if (window.gsap) gsap.fromTo(el, {opacity:0,y:20}, {opacity:1,y:0,duration:0.4,ease:'power3.out'});
            var inp = el.querySelector('input:not([type=hidden])');
            if (inp) setTimeout(function(){ inp.focus(); }, 420);
        }
        function hideScreen(id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (window.gsap) {
                gsap.to(el, {opacity:0,y:-14,duration:0.22,ease:'power2.in',onComplete:function(){ el.style.display='none'; el.style.opacity=''; el.style.transform=''; }});
            } else { el.style.display='none'; }
        }

        // ── Fire AI palette generation in background ───────────────────────
        function fireAIPalettes(name, industry) {
            // If we already have a successful AI result, don't re-fire
            if (palettesReady && palettesData && palettesData !== staticPalettes) return;

            palettesReady = false;
            palettesData  = null;

            function attemptFetch(attemptsLeft) {
                var fd = new FormData();
                fd.append('action',   'bae_wizard_palettes');
                fd.append('nonce',    nonce);
                fd.append('name',     name);
                fd.append('industry', industry || '');

                return fetch(ajaxurl, { method:'POST', body:fd })
                    .then(function(r) { return r.json(); })
                    .then(function(j) {
                        if (j.success && j.data && j.data.palettes && j.data.palettes.length) {
                            palettesData  = j.data.fallback ? staticPalettes : j.data.palettes;
                            palettesReady = true;
                            return palettesData;
                        }
                        // AI failed — retry if attempts remain
                        if (attemptsLeft > 1) {
                            return new Promise(function(res) { setTimeout(res, 800); })
                                .then(function() { return attemptFetch(attemptsLeft - 1); });
                        }
                        if (j.data && j.data.message) console.warn('[BAE Palettes]', j.data.message);
                        palettesData  = staticPalettes;
                        palettesReady = true;
                        return staticPalettes;
                    })
                    .catch(function() {
                        if (attemptsLeft > 1) {
                            return new Promise(function(res) { setTimeout(res, 800); })
                                .then(function() { return attemptFetch(attemptsLeft - 1); });
                        }
                        palettesData  = staticPalettes;
                        palettesReady = true;
                        return staticPalettes;
                    });
            }

            palettePromise = attemptFetch(3); // up to 3 attempts
        }

        // ── Render palette cards into Step 3 ──────────────────────────────
        function renderPalettes(palettes, isAI) {
            var container = document.getElementById('bae-wiz-palette-tiles');
            var hint      = document.getElementById('bae-wiz-palette-hint');
            var loading   = document.getElementById('bae-wiz-palette-loading');

            container.innerHTML = '';

            if (isAI) {
                hint.textContent = 'AI-picked palettes for ' + state.name + ' — pick the one that feels right.';
                var badge = document.createElement('div');
                badge.className = 'bae-wiz-palette-ai-badge';
                badge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg> Personalized by AI for ' + state.name;
                container.appendChild(badge);
            } else {
                hint.textContent = 'Pick a palette that fits your brand. You can fine-tune colors later.';
            }

            palettes.forEach(function(p, i) {
                var card = document.createElement('div');
                card.className = 'bae-wiz-palette-card';
                card.dataset.primary     = p.primary;
                card.dataset.secondary   = p.secondary;
                card.dataset.accent      = p.accent;
                card.dataset.personality = p.personality;

                card.innerHTML =
                    '<div class="bae-wiz-palette-swatches">' +
                        '<div class="bae-wiz-palette-swatch" style="background:' + p.primary   + ';"></div>' +
                        '<div class="bae-wiz-palette-swatch" style="background:' + p.secondary + ';"></div>' +
                        '<div class="bae-wiz-palette-swatch" style="background:' + p.accent    + ';"></div>' +
                    '</div>' +
                    '<div class="bae-wiz-palette-info">' +
                        '<div class="bae-wiz-palette-name">' + p.name + '</div>' +
                        '<div class="bae-wiz-palette-reason">' + p.reason + '</div>' +
                    '</div>' +
                    '<div class="bae-wiz-palette-check">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>' +
                    '</div>';

                card.addEventListener('click', function() {
                    container.querySelectorAll('.bae-wiz-palette-card').forEach(function(c){ c.classList.remove('selected'); });
                    card.classList.add('selected');
                    state.primary     = card.dataset.primary;
                    state.secondary   = card.dataset.secondary;
                    state.accent      = card.dataset.accent;
                    state.personality = card.dataset.personality;
                    baeRenderPaletteWarning('bae-wiz-palette-warning', state.primary, state.secondary, state.accent);
                    document.getElementById('bae-wiz-next-3').disabled = false;
                    document.getElementById('bae-wiz-vibe-err').style.display = 'none';
                    if (window.gsap) gsap.fromTo(card, {scale:0.98}, {scale:1, duration:0.25, ease:'back.out(2)'});
                });

                container.appendChild(card);

                if (window.gsap) {
                    gsap.fromTo(card, {opacity:0, y:10}, {opacity:1, y:0, duration:0.3, delay: i * 0.06, ease:'power3.out'});
                }
            });

            loading.style.display   = 'none';
            container.style.display = 'block';
            baeRenderPaletteWarning('bae-wiz-palette-warning', '', '', '');
        }

        // ── Show Step 3 — wait for palette promise if needed ──────────────
        function showStep3() {
            var loading = document.getElementById('bae-wiz-palette-loading');
            var loadTxt = document.getElementById('bae-wiz-palette-loading-txt');
            loading.style.display = 'flex';
            document.getElementById('bae-wiz-palette-tiles').style.display = 'none';
            document.getElementById('bae-wiz-next-3').disabled = true;

            if (palettesReady && palettesData) {
                renderPalettes(palettesData, palettesData !== staticPalettes);
                return;
            }

            loadTxt.textContent = 'Generating palettes for ' + state.name + '...';

            if (!palettePromise) {
                fireAIPalettes(state.name, state.industry);
            }

            var rendered = false;
            var timeout = setTimeout(function() {
                if (!rendered) { rendered = true; renderPalettes(staticPalettes, false); }
            }, 12000);

            palettePromise.then(function(palettes) {
                clearTimeout(timeout);
                if (!rendered) { rendered = true; renderPalettes(palettes, palettes !== staticPalettes); }
            });
        }

        // ── Fire AI tagline generation in background ──────────────────────
        function fireAITaglines(name, industry, personality) {
            taglinesReady = false;
            taglinesData  = null;

            var fd = new FormData();
            fd.append('action',      'bae_wizard_taglines');
            fd.append('nonce',       nonce);
            fd.append('name',        name);
            fd.append('industry',    industry    || '');
            fd.append('personality', personality || '');

            taglinePromise = fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (j.success && j.data && j.data.taglines && j.data.taglines.length) {
                        taglinesData  = j.data.taglines;
                        taglinesReady = true;
                        return j.data.taglines;
                    }
                    if (j.data && j.data.message) console.warn('[BAE Taglines]', j.data.message);
                    taglinesData  = null;
                    taglinesReady = true;
                    return null;
                })
                .catch(function() {
                    taglinesData  = null;
                    taglinesReady = true;
                    return null;
                });
        }

        // ── Render taglines into Step 4 ───────────────────────────────────
        function renderTaglines(taglines, isAI) {
            var optsEl   = document.getElementById('bae-wiz-tagline-opts');
            var customEl = document.getElementById('bae-wiz-tagline-custom');
            var loading  = document.getElementById('bae-wiz-tagline-loading');
            var hint     = document.getElementById('bae-wiz-tagline-hint');

            optsEl.innerHTML = '';

            if (isAI && taglines && taglines.length) {
                hint.textContent = 'AI-written taglines for ' + state.name + ' — pick one or write your own.';
                var badge = document.createElement('div');
                badge.className = 'bae-wiz-palette-ai-badge';
                badge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg> Written by AI for ' + state.name;
                optsEl.appendChild(badge);
            } else {
                hint.textContent = 'One line that sums up what you do. You can always change it.';
                var map = {
                    'Bold, premium, innovative':          ['Stand out. Be bold.','Built different.','The future of ' + state.name + '.'],
                    'Professional, trustworthy, reliable':['Your trusted partner.','Excellence, every time.','Built on trust.'],
                    'Natural, fresh, community-focused':  ['Fresh from the heart.','Good for you. Good for all.','Grown with care.'],
                    'Warm, energetic, approachable':      ['Made with love.','Where everyone belongs.','Energy in every detail.'],
                    'Luxury, refined, classic':           ['Crafted for those who know.','Timeless by design.','Excellence is the standard.'],
                    'Playful, feminine, creative':        ['Life is better with ' + state.name + '.','Because you deserve it.','Made to delight.'],
                    'Minimal, modern, clean':             ['Less, but better.','Simple. Powerful.','Clean by design.'],
                    'Tech-forward, analytical, precise':  ['Precision at scale.','Engineered for results.','Smarter, faster, better.']
                };
                taglines = map[state.personality] || ['Quality you can trust.','Built with purpose.','Your brand, your story.'];
            }

            taglines.forEach(function(opt, i) {
                var d = document.createElement('div');
                d.className = 'bae-wiz-tagline-opt';
                d.dataset.value = opt;
                d.textContent = '"' + opt + '"';
                d.onclick = function() {
                    optsEl.querySelectorAll('.bae-wiz-tagline-opt').forEach(function(o){o.classList.remove('selected');});
                    d.classList.add('selected');
                    customEl.value = '';
                    if (window.gsap) gsap.fromTo(d,{scale:0.97},{scale:1,duration:0.22,ease:'back.out(2)'});
                };
                optsEl.appendChild(d);
                if (window.gsap) gsap.fromTo(d,{opacity:0,y:8},{opacity:1,y:0,duration:0.28,delay:i*0.05,ease:'power3.out'});
            });

            loading.style.display  = 'none';
            optsEl.style.display   = 'block';
            customEl.style.display = 'block';
        }

        // ── Show Step 4 — wait for tagline promise ────────────────────────
        function showStep4() {
            var loading = document.getElementById('bae-wiz-tagline-loading');
            loading.style.display = 'flex';
            document.getElementById('bae-wiz-tagline-opts').style.display   = 'none';
            document.getElementById('bae-wiz-tagline-custom').style.display = 'none';

            if (taglinesReady) {
                renderTaglines(taglinesData, taglinesData !== null);
                return;
            }
            if (!taglinePromise) {
                fireAITaglines(state.name, state.industry, state.personality);
            }
            var rendered = false;
            var timeout = setTimeout(function() {
                if (!rendered) { rendered = true; renderTaglines(null, false); }
            }, 12000);
            taglinePromise.then(function(taglines) {
                clearTimeout(timeout);
                if (!rendered) { rendered = true; renderTaglines(taglines, taglines !== null); }
            });
        }

        window.baeWizGo = function(step) {
            if (step > state.step) {
                if (state.step === 1) {
                    state.name = (document.getElementById('bae-wiz-name').value||'').trim();
                    if (!state.name) { document.getElementById('bae-wiz-name-err').style.display='block'; if(window.gsap) gsap.fromTo('#bae-wiz-name',{x:-5},{x:0,duration:0.35,ease:'elastic.out(1,0.4)'}); return; }
                    document.getElementById('bae-wiz-name-err').style.display='none';
                    fireAIPalettes(state.name, '');
                }
                if (state.step === 2) {
                    if (!state.industry) { document.getElementById('bae-wiz-industry-err').style.display='block'; return; }
                    document.getElementById('bae-wiz-industry-err').style.display='none';
                    // Only re-fire if we don't have AI palettes yet — adds industry context
                    if (!palettesReady || palettesData === staticPalettes) {
                        fireAIPalettes(state.name, state.industry);
                    }
                }
                if (state.step === 3) {
                    if (!state.primary) { document.getElementById('bae-wiz-vibe-err').style.display='block'; return; }
                    document.getElementById('bae-wiz-vibe-err').style.display='none';
                    fireAITaglines(state.name, state.industry, state.personality);
                }
                if (state.step === 4) {
                    var sel = document.querySelector('.bae-wiz-tagline-opt.selected');
                    var cus = (document.getElementById('bae-wiz-tagline-custom').value||'').trim();
                    state.tagline = cus || (sel ? sel.dataset.value : '');
                    if (!state.tagline) { document.getElementById('bae-wiz-tagline-err').style.display='block'; return; }
                    document.getElementById('bae-wiz-tagline-err').style.display='none';
                }
            }
            hideScreen('bae-step-' + state.step);
            state.step = step;
            setProgress(step);
            // Fire step change event for orb animation
            try { document.dispatchEvent(new CustomEvent('bae-wiz-step-change', { detail: { step: step } })); } catch(e){}
            if (step === 3) {
                showScreen('bae-step-3');
                showStep3();
            } else if (step === 4) {
                showScreen('bae-step-4');
                showStep4();
            } else {
                showScreen('bae-step-' + step);
            }
        };

        window.baeWizSelectTile = function(el) {
            document.querySelectorAll('#bae-wiz-industry-tiles .bae-wiz-tile').forEach(function(t){t.classList.remove('selected');});
            el.classList.add('selected');
            state.industry = el.dataset.value;
            document.getElementById('bae-wiz-next-2').disabled = false;
            document.getElementById('bae-wiz-industry-err').style.display = 'none';
            if (window.gsap) gsap.fromTo(el,{scale:0.96},{scale:1,duration:0.28,ease:'back.out(2)'});
        };

        document.getElementById('bae-wiz-tagline-custom').addEventListener('input', function() {
            if (this.value.trim()) document.querySelectorAll('.bae-wiz-tagline-opt').forEach(function(o){o.classList.remove('selected');});
        });
        document.getElementById('bae-wiz-name').addEventListener('keydown', function(e){ if(e.key==='Enter') baeWizGo(2); });

        window.baeWizSubmit = function() {
            // Keep wizard theme in sync on submission
            baeWizApplyTheme(baeWizIsDark);
            state.email   = (document.getElementById('bae-wiz-email').value||'').trim();
            state.phone   = (document.getElementById('bae-wiz-phone').value||'').trim();
            state.website = (document.getElementById('bae-wiz-website').value||'').trim();

            // Guard: ensure colors are set (fallback if user somehow got here without picking)
            if (!state.primary)     state.primary     = '#1a1a2e';
            if (!state.secondary)   state.secondary   = '#16213e';
            if (!state.accent)      state.accent      = '#e94560';
            if (!state.personality) state.personality = 'Professional, trustworthy, reliable';

            hideScreen('bae-step-5');
            showScreen('bae-step-gen');

            var fd = new FormData();
            fd.append('action',          'bae_save_profile');
            fd.append('nonce',           nonce);
            fd.append('business_name',   state.name);
            fd.append('industry',        state.industry);
            fd.append('tagline',         state.tagline);
            fd.append('personality',     state.personality);
            fd.append('email',           state.email);
            fd.append('phone',           state.phone);
            fd.append('website',         state.website);
            fd.append('address',         '');
            fd.append('primary_color',   state.primary);
            fd.append('secondary_color', state.secondary);
            fd.append('accent_color',    state.accent);
            fd.append('font_heading',    'Inter');
            fd.append('font_body',       'Inter');
            fd.append('logo_style',      'wordmark');
            fd.append('logo_icon',       '');
            // FIX: Send ticket via POST so nopriv handler can read it even
            // when the cookie hasn't propagated to $_COOKIE yet (new users)
            var _tk = (function() {
                var m = document.cookie.match('(?:^|; )bae_ticket=([^;]*)');
                return m ? decodeURIComponent(m[1]) : '';
            })();
            if (_tk) fd.append('bae_ticket', _tk);

            document.getElementById('bae-wiz-gen-status').textContent = 'Saving your brand profile...';

            function generateStarterAssets(profileId, assetTypes) {
                assetTypes = Array.isArray(assetTypes) ? assetTypes : [];
                if (!profileId || !assetTypes.length) return Promise.resolve();

                var labels = {
                    business_card: 'Business Card',
                    letterhead: 'Letterhead',
                    email_signature: 'Email Signature',
                    social_kit: 'Social Media Kit',
                    brand_guidelines: 'Brand Guidelines',
                    sitemap: 'Site Structure'
                };

                function runAt(index) {
                    if (index >= assetTypes.length) return Promise.resolve();
                    var type = assetTypes[index];
                    document.getElementById('bae-wiz-gen-status').textContent = 'Creating ' + (labels[type] || type) + ' (' + (index + 1) + '/' + assetTypes.length + ')...';

                    var afd = new FormData();
                    afd.append('action', 'bae_generate_asset');
                    afd.append('asset_type', type);
                    afd.append('profile_id', profileId);
                    afd.append('nonce', generateNonce);

                    return fetch(ajaxurl, { method:'POST', body: afd })
                        .then(function(r){ return r.json(); })
                        .then(function(j){
                            if (!j || !j.success) {
                                var msg = j && j.data && j.data.message ? j.data.message : 'Failed to generate starter assets.';
                                throw new Error(msg);
                            }
                            return runAt(index + 1);
                        });
                }

                return runAt(0);
            }

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(function(r){
                // Log raw response for debugging
                return r.text().then(function(txt) {
                    console.log('[BAE Submit] Raw response:', txt);
                    try { return JSON.parse(txt); }
                    catch(e) { throw new Error('Non-JSON response: ' + txt.substring(0, 200)); }
                });
            })
            .then(function(j){
                if (j.success) {
                    var payload = j.data || {};
                    generateStarterAssets(payload.profile_id || 0, payload.starter_assets || [])
                        .then(function() {
                            document.getElementById('bae-wiz-gen-status').textContent = 'Brand created!';
                            var swatchContainer = document.getElementById('bae-cel-swatches');
                            if (swatchContainer && state.primary && !swatchContainer.children.length) {
                                [state.primary, state.secondary, state.accent].forEach(function(col) {
                                    var sw = document.createElement('div');
                                    sw.style.cssText = 'width:48px;height:48px;border-radius:14px;background:'+col+';border:1px solid rgba(255,255,255,0.15);';
                                    swatchContainer.appendChild(sw);
                                });
                            }
                            var celTitle = document.getElementById('bae-cel-title');
                            if (celTitle && state.name) celTitle.textContent = state.name + ' is ready.';
                            hideScreen('bae-step-gen');
                            showScreen('bae-step-done');
                            if (window.gsap) {
                                gsap.fromTo('#bae-cel-icon', {scale:0,rotate:-15}, {scale:1,rotate:0,duration:0.5,ease:'back.out(2)',delay:0.1});
                                gsap.fromTo('#bae-cel-swatches > div', {scale:0,y:10}, {scale:1,y:0,duration:0.4,stagger:0.08,ease:'back.out(2)',delay:0.3});
                                gsap.to('#bae-cel-icon', {boxShadow:'0 0 36px rgba(243,45,134,0.6)',duration:1.4,repeat:-1,yoyo:true,ease:'sine.inOut',delay:0.8});
                            }
                        })
                        .catch(function(err) {
                            document.getElementById('bae-wiz-gen-status').textContent = err && err.message ? err.message : 'Starter asset generation failed.';
                        });
                } else {
                    var errMsg = (j.data && j.data.message) ? j.data.message : 'Something went wrong.';
                    document.getElementById('bae-wiz-gen-status').textContent = errMsg;
                    // Show a retry button instead of trying to navigate back
                    var genScreen = document.getElementById('bae-step-gen');
                    if (genScreen) {
                        var retryBtn = document.createElement('button');
                        retryBtn.className = 'bae-wiz-next';
                        retryBtn.style.cssText = 'max-width:220px;margin:20px auto 0;';
                        retryBtn.textContent = 'Try Again';
                        retryBtn.onclick = function() {
                            genScreen.innerHTML = '';
                            hideScreen('bae-step-gen');
                            // Reset gen screen for next attempt
                            genScreen.innerHTML = '<div class="bae-wiz-spinner"></div><div class="bae-wiz-gen-title">Mothifying your brand...</div><div class="bae-wiz-gen-sub" id="bae-wiz-gen-status">Saving your profile</div>';
                            showScreen('bae-step-5');
                        };
                        genScreen.appendChild(retryBtn);
                    }
                }
            })
            .catch(function(err){
                console.error('[BAE Submit] Error:', err);
                document.getElementById('bae-wiz-gen-status').textContent = 'Error: ' + (err.message || 'Unknown. See console.');
                var genScreen = document.getElementById('bae-step-gen');
                if (genScreen) {
                    var retryBtn = document.createElement('button');
                    retryBtn.className = 'bae-wiz-next';
                    retryBtn.style.cssText = 'max-width:220px;margin:20px auto 0;';
                    retryBtn.textContent = 'Try Again';
                    retryBtn.onclick = function() {
                        genScreen.innerHTML = '<div class="bae-wiz-spinner"></div><div class="bae-wiz-gen-title">Mothifying your brand...</div><div class="bae-wiz-gen-sub" id="bae-wiz-gen-status">Saving your profile</div>';
                        hideScreen('bae-step-gen');
                        showScreen('bae-step-5');
                    };
                    genScreen.appendChild(retryBtn);
                }
            });
        };

        setProgress(1);
        showScreen('bae-step-1');
    })();
    </script>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_bae() {
// FORCE NO CACHE – bypass BNTM Hub and all other caching layers
if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
if (!defined('DONOTCACHEDB')) define('DONOTCACHEDB', true);
if (!defined('DONOTCACHEOBJECT')) define('DONOTCACHEOBJECT', true);
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');

    
    // Identity resolution: ticket (returning user) → session (new/unclaimed user)
    // Steps 1–3 save freely under session_id. Step 4 (startup) lets user claim with a ticket.
    $user_id    = is_user_logged_in() ? get_current_user_id() : 0;
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

    $ticket  = bae_get_ticket_cookie();
    $session = bae_get_session_id();

    // Resolve profile: ticket first, then session fallback
    $profile = null;
    if ($ticket) {
        $profile = bae_get_profile_by_ticket($ticket);
    }
    if (!$profile && $session) {
        global $wpdb;
        $profile = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bae_profiles WHERE session_id = %s AND (ticket = '' OR ticket IS NULL) ORDER BY updated_at DESC, id DESC LIMIT 1",
                $session
            ), ARRAY_A
        );
    }

    // Demo profile shortcut
    if (!$profile && $ticket === 'BAE-2525-2525') {
        global $wpdb;
        $demo_profile = [
            'rand_id' => bntm_rand_id(),
            'user_id' => $user_id,
            'ticket' => $ticket,
            'session_id' => $session,
            'business_name' => 'Demo Brand Co.',
            'industry' => 'Technology',
            'tagline' => 'Innovating the Future',
            'personality' => 'Tech-savvy professionals in Silicon Valley',
            'email' => 'hello@demobrand.com',
            'phone' => '+1 555 123 4567',
            'website' => 'https://demobrand.com',
            'address' => '123 Tech Street, Silicon Valley, CA',
            'primary_color' => '#6366f1',
            'secondary_color' => '#374151',
            'accent_color' => '#f59e0b',
            'font_heading' => 'Inter',
            'font_body' => 'Inter',
            'logo_style' => 'wordmark',
            'logo_icon' => 'code',
            'logo_icon_scale' => 100,
            'logo_spacing' => 14,
            'logo_position' => 'auto',
            'logo_text_case' => 'default',
            'plan' => 'pro',
            'kit_slug' => bae_make_unique_kit_slug('demo-brand-co'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        $wpdb->insert($wpdb->prefix . 'bae_profiles', $demo_profile);
        $profile = bae_get_profile_by_ticket($ticket);
    }

    // No profile and no business name yet → show wizard (first visit)
    if ((empty($profile) || empty($profile['business_name'])) && !isset($_GET['tab'])) {
        return bae_wizard_shortcode($user_id);
    }

    if (!isset($_GET['tab']) && !empty($profile) && !empty($profile['id'])) {
        $has_generated_assets = bae_count_assets($user_id, (int) $profile['id']) >= 1;
        if ($has_generated_assets && !bae_has_viewed_onboarding_asset($profile)) {
            $active_tab = 'assets';
        }
    }

    $onboarding_done = false;

    ob_start();
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script>var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';</script>

    <script>
    window.BAE_PM = {
        ajax_url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
        nonce:    '<?php echo esc_js(wp_create_nonce('bae_pm_checkout')); ?>'
    };
    window.BAE_SESSION = {
        has_ticket:  <?php echo $ticket ? 'true' : 'false'; ?>,
        ticket:      '<?php echo esc_js($ticket); ?>',
        claim_nonce: '<?php echo esc_js(wp_create_nonce('bae_claim_ticket')); ?>'
    };
    window.BAE_BETA = <?php echo wp_json_encode(bae_beta_status_payload()); ?>;
    </script>
    <div class="bae-wrap" id="bae-wrap" style="opacity:0;transition:opacity 0.25s ease;">

        <!-- Page loader -->
        <div id="bae-page-loader" class="bae-page-loader bae-page-loader-enter" aria-live="polite">
            <div class="bae-loader-panel">
                <div class="bae-loader-logo"><?php echo bae_render_brand_mark(); ?></div>
                <div class="bae-loader-kicker">Mothie</div>
                <div class="bae-loader-title">
                    <span class="jp">読み込み中</span>
                    <span class="en" id="bae-loader-title-en">Loading your workspace</span>
                </div>
                <div class="bae-loader-bars">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="bae-loader-meta">
                    <span id="bae-loader-meta-step">Preparing assets</span>
                </div>
            </div>
        </div>
        <style>
        @keyframes bae-loader-bar {
            0%, 100% { transform: scaleY(0.35); opacity: 0.35; }
            50% { transform: scaleY(1); opacity: 1; }
        }
        .bae-page-loader {
            --brand:       #F32D86;
            --brand-deep:  #c4196a;
            --brand-soft:  #f76fb0;
            --pink:        #F32D86;
            --bg:         #000000;
            --surface:    #161616;
            --border:     rgba(255,255,255,0.06);
            --border-2:   rgba(255,255,255,0.10);
            --text:       #f5f5f5;
            --text-2:     #a0a0a0;
            --text-3:     #555555;
            position: fixed;
            inset: 0;
            z-index: 100000;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 18% 18%, rgba(243,45,134,.18), transparent 34%),
                radial-gradient(circle at 82% 14%, rgba(243,45,134,.12), transparent 28%),
                linear-gradient(180deg, rgba(10,10,15,.94) 0%, rgba(17,17,24,.97) 100%);
            transition: opacity .45s ease, visibility .45s ease, transform .5s ease;
            transform-origin: center top;
            padding: 24px;
            pointer-events: auto;
        }
        .bae-page-loader.bae-light {
            --bg:         #EFF3F6;
            --surface:    #ffffff;
            --border:     rgba(0,0,0,0.07);
            --border-2:   rgba(0,0,0,0.12);
            --text:       #111418;
            --text-2:     #5a6472;
            --text-3:     #93a0af;
        }
        .bae-page-loader.is-hidden {
            opacity: 0;
            visibility: hidden;
            transform: scale(1.015);
            pointer-events: none;
        }
        .bae-page-loader.is-transitioning {
            position: fixed;
            inset: 0;
        }
        .bae-loader-panel {
            width: min(360px, 100%);
            padding: 28px 26px 24px;
            text-align: center;
        }
        .bae-loader-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 14px;
        }
        .bae-loader-logo .bae-brand-mark {
            width: 72px;
        }
        .bae-loader-logo .bae-brand-logo-img {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }
        .bae-loader-kicker {
            font-size: 11px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--text-3);
            margin-bottom: 12px;
        }
        .bae-loader-title {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(28px, 4vw, 34px);
            line-height: 1.05;
            color: var(--text);
            margin-bottom: 18px;
        }
        .bae-loader-title .jp {
            display: none;
        }
        .bae-loader-title .en {
            font: inherit;
            color: inherit;
        }
        .bae-loader-bars {
            display: flex;
            gap: 8px;
            align-items: flex-end;
            justify-content: center;
            height: 38px;
            margin-bottom: 14px;
        }
        .bae-loader-bars span {
            width: 7px;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(180deg, var(--brand-soft) 0%, var(--brand) 55%, var(--pink) 100%);
            transform-origin: bottom center;
            animation: bae-loader-bar 1s ease-in-out infinite;
        }
        .bae-loader-bars span:nth-child(2) { animation-delay: .14s; }
        .bae-loader-bars span:nth-child(3) { animation-delay: .28s; }
        .bae-loader-meta {
            display: flex;
            justify-content: center;
            font-size: 12px;
            color: var(--text-2);
            letter-spacing: .05em;
        }
        .bae-page-loader.bae-light {
            background:
                radial-gradient(circle at 18% 18%, rgba(243,45,134,.14), transparent 34%),
                radial-gradient(circle at 82% 14%, rgba(243,45,134,.09), transparent 28%),
                linear-gradient(180deg, rgba(250,250,250,.95) 0%, rgba(244,243,255,.98) 100%);
        }
        .bae-page-loader .bae-brand-logo-img { filter: invert(1); }
        .bae-page-loader.bae-light .bae-brand-logo-img { filter: none; }
        @media (max-width: 640px) {
            .bae-page-loader { padding: 16px; }
            .bae-loader-panel { padding: 24px 20px 20px; }
        }
        </style>

        <!-- Header -->
        <div class="bae-header">
            <div class="bae-header-logo">
                <div class="bae-header-brand">
                    <img class="bae-header-logo-img" src="<?php echo esc_url(bae_module_logo_url()); ?>" alt="Mothie">
                    <span class="bae-header-wordmark">MOTHIE</span>
                </div>
            </div>
            <?php
                /* ── 7-TAB NAV ── */
                $user_plan   = bae_get_user_plan($user_id, $profile);
                $is_free     = $user_plan === 'free';
                $has_profile = !empty($profile) && !empty($profile['business_name']);
                global $wpdb;
                $asset_count_step = 0;
                if ($has_profile && !empty($profile['id'])) {
                    $asset_count_step = (int)$wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}bae_assets WHERE profile_id = %d AND is_generated = 1",
                        $profile['id']
                    ));
                }
                $has_viewed_asset = $has_profile && bae_has_viewed_onboarding_asset($profile);
                $onboarding_done = $has_profile && $has_viewed_asset;
                if ($onboarding_done && !isset($_GET['tab'])) {
                    $active_tab = 'dashboard';
                }
                $base_url = strtok($_SERVER['REQUEST_URI'], '?');

                // 7 tabs with icons and lock states
                $nav_tabs = [
                    'dashboard'  => [
                        'label' => 'Dashboard',
                        'icon'  => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
                        'locked' => false,
                        'lock_msg' => '',
                    ],
                    'overview'   => [
                        'label' => 'Identity',
                        'icon'  => '<circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/>',
                        'locked' => false,
                        'lock_msg' => '',
                    ],
                    'logo'       => [
                        'label' => 'Logo',
                        'icon'  => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M2 12h3M19 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/>',
                        'locked' => !$has_profile,
                        'lock_msg' => 'Complete your Brand Identity first to unlock the Logo builder.',
                    ],
                    'assets'     => [
                        'label' => 'Assets',
                        'icon'  => '<rect width="8" height="8" x="3" y="3" rx="1"/><rect width="8" height="5" x="13" y="3" rx="1"/><rect width="8" height="8" x="13" y="12" rx="1"/><rect width="8" height="5" x="3" y="15" rx="1"/>',
                        'locked' => !$has_profile,
                        'lock_msg' => 'Complete your Brand Identity first to unlock Asset generation.',
                    ],
                    'kit'        => [
                        'label' => 'Kit',
                        'icon'  => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>',
                        'locked' => !$has_viewed_asset,
                        'lock_msg' => 'Preview any generated asset once to unlock Brand Kit.',
                    ],
                    'startup'    => [
                        'label' => 'Launch',
                        'icon'  => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>',
                        'locked' => !$has_viewed_asset,
                        'lock_msg' => 'Preview any generated asset once to unlock Launch Toolkit.',
                    ],
                    'brand_book' => [
                        'label' => 'Mockup',
                        'icon'  => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
                        'locked' => !$has_viewed_asset,
                        'lock_msg' => 'Preview any generated asset once to unlock Mockup templates.',
                    ],
                    'settings'   => [
                        'label' => 'Settings',
                        'icon'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
                        'locked' => false,
                        'lock_msg' => '',
                    ],
                ];
            ?>

            <!-- Lock Modal -->
            <div class="bae-lock-modal-overlay" id="bae-lock-modal-overlay" onclick="if(event.target===this)baeNavLockClose()">
                <div class="bae-lock-modal">
                    <div class="bae-lock-modal-header">
                        <span class="bae-lock-modal-title" id="bae-lock-modal-title">Page Locked</span>
                        <button class="bae-modal-close" onclick="baeNavLockClose()">&times;</button>
                    </div>
                    <div class="bae-lock-modal-body">
                        <div class="bae-lock-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <p class="bae-lock-modal-msg" id="bae-lock-modal-msg"></p>
                    </div>
                    <div class="bae-lock-modal-footer">
                        <button class="bae-btn bae-btn-outline" onclick="baeNavLockClose()">Got it</button>
                    </div>
                </div>
            </div>

            <div class="bae-header-right">
                <button class="bae-theme-switch" id="bae-theme-btn" onclick="baeToggleTheme()" aria-label="Toggle theme" title="Toggle light/dark">
                    <div class="bae-ts-track" id="bae-toggle-track">
                        <div class="bae-ts-thumb" id="bae-ts-thumb">
                            <svg id="bae-ts-sun" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                            <svg id="bae-ts-moon" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                        </div>
                    </div>
                </button>

                <?php if ($ticket): ?>
                <!-- Logged in: show ticket chip + logout button -->
                <div class="bae-auth-ticket-chip" id="bae-auth-ticket-chip" title="Your access ticket">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
                    <span class="bae-auth-ticket-val"><?php echo esc_html($ticket); ?></span>
                </div>
                <button class="bae-header-logout-btn" id="bae-header-logout-btn" onclick="baeHeaderLogout()" title="Sign out / Clear ticket">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Logout</span>
                </button>
                <?php else: ?>
                <!-- Not logged in: show login button -->
                <button class="bae-header-login-btn" id="bae-header-login-btn" onclick="baeTicketModalOpen('header')" title="Access your workspace with a ticket, or generate one">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
                    <span>Enter Ticket</span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══ TICKET LOGIN MODAL ══ -->
        <div class="bae-ticket-modal-overlay" id="bae-ticket-modal-overlay" onclick="if(event.target===this)baeTicketModalClose()">
            <div class="bae-ticket-modal" id="bae-ticket-modal">
                <div class="bae-ticket-modal-header">
                    <div class="bae-ticket-modal-header-left">
                        <div class="bae-ticket-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
                        </div>
                        <span class="bae-ticket-modal-title" id="bae-tkm-title">Access Your Ticket</span>
                    </div>
                    <button class="bae-modal-close" onclick="baeTicketModalClose()" aria-label="Close">&times;</button>
                </div>
                <div class="bae-ticket-modal-body">
                    <p class="bae-ticket-modal-desc" id="bae-tkm-desc">Enter your access ticket to open your workspace, or generate one to bind this workspace for later return. No account needed.</p>
                    <div id="bae-beta-banner" style="display:none;margin:0 0 12px;padding:10px 12px;border-radius:10px;border:1px solid rgba(243,45,134,.28);background:rgba(243,45,134,.08);">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                            <div style="font-size:12px;font-weight:700;color:var(--text);">Beta Free Pro · First <span id="bae-beta-total">100</span></div>
                            <div id="bae-beta-remaining" style="font-size:11px;font-weight:700;color:var(--brand-soft);">Remaining: --</div>
                        </div>
                        <div style="font-size:11px;color:var(--text-3);margin-top:4px;">Claim now to get Pro plan instantly when slots are available.</div>
                        <button type="button" class="bae-btn bae-btn-primary bae-ticket-modal-submit" id="bae-beta-claim-btn" style="margin-top:10px;height:34px;" onclick="baeTicketModalGenerate('beta')">Claim Beta Pro Ticket</button>
                    </div>

                    <!-- Step 1: Ticket input -->
                    <div id="bae-tkm-step-ticket">
                        <div class="bae-ticket-modal-input-wrap">
                            <input type="text" id="bae-tkm-input" class="bae-ticket-modal-input"
                                   placeholder="BAE-XXXX-XXXX"
                                   maxlength="13" autocomplete="off" spellcheck="false" inputmode="text">
                        </div>
                        <div class="bae-ticket-modal-err" id="bae-tkm-err" style="display:none"></div>
                        <button class="bae-btn bae-btn-primary bae-ticket-modal-submit" id="bae-tkm-btn" onclick="baeTicketModalSubmit()">
                            <div class="bae-tkm-spin" id="bae-tkm-spin"></div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" id="bae-tkm-arrow"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            <span id="bae-tkm-lbl">Open Workspace</span>
                        </button>
                    </div>

                    <!-- Step 2: OTP verification (shown when login_code_enabled) -->
                    <div id="bae-tkm-step-otp" style="display:none">
                        <p class="bae-ticket-modal-otp-hint" id="bae-tkm-otp-hint">A 6-digit code was sent to your email.</p>
                        <div class="bae-ticket-modal-input-wrap">
                            <input type="text" id="bae-tkm-otp" class="bae-ticket-modal-input bae-ticket-modal-input-otp"
                                   placeholder="000000" maxlength="6" inputmode="numeric" autocomplete="one-time-code">
                        </div>
                        <div class="bae-ticket-modal-err" id="bae-tkm-otp-err" style="display:none"></div>
                        <button class="bae-btn bae-btn-primary bae-ticket-modal-submit" id="bae-tkm-otp-btn" onclick="baeTicketModalOtpSubmit()">
                            <div class="bae-tkm-spin" id="bae-tkm-otp-spin"></div>
                            <span>Verify Code</span>
                        </button>
                        <button class="bae-ticket-modal-back" onclick="baeTicketModalBackToTicket()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                            Back
                        </button>
                    </div>
                </div>
                <div class="bae-ticket-modal-footer">
                    <span class="bae-ticket-modal-footer-hint">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        <span id="bae-tkm-footer-hint-text">No ticket yet?</span>
                    </span>
                    <button class="bae-ticket-modal-new-btn" onclick="baeTicketModalGenerate()" id="bae-tkm-new-btn">Generate and bind ticket</button>
                </div>
            </div>
        </div>

        <!-- Mobile floating bottom nav -->
        <button type="button" class="bae-mobile-nav-toggle" id="bae-mobile-nav-toggle" onclick="baeMobileNavToggle()" aria-expanded="true" aria-controls="bae-mobile-nav" title="Toggle navigation">
            <svg id="bae-mob-nav-icon-open" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            <svg id="bae-mob-nav-icon-closed" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="m18 15-6-6-6 6"/></svg>
            <span id="bae-mobile-nav-toggle-label">Hide Menu</span>
        </button>
        <nav class="bae-mobile-nav" id="bae-mobile-nav">
            <?php foreach ($nav_tabs as $slug => $tab): ?>
            <?php
                $is_active = $active_tab === $slug;
                $is_locked = $tab['locked'];
                $lock_msg  = $tab['lock_msg'];
                $mob_cls = 'bae-mob-item';
                if ($is_active) $mob_cls .= ' bae-mob-active';
                if ($is_locked) $mob_cls .= ' bae-hn-locked';
            ?>
            <?php if ($is_locked): ?>
            <button type="button" class="<?php echo $mob_cls; ?>"
                onclick="baeNavLockModal(<?php echo esc_attr(json_encode($tab['label'])); ?>, <?php echo esc_attr(json_encode($lock_msg)); ?>)"
                title="<?php echo esc_attr($tab['label']); ?>">
                <span class="bae-mob-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $tab['icon']; ?></svg>
                    <svg class="bae-hn-lock bae-mob-lock lucide lucide-lock" xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <span class="bae-mob-label"><?php echo esc_html($tab['label']); ?></span>
            </button>
            <?php else: ?>
            <a href="<?php echo esc_url(add_query_arg('tab', $slug, $base_url)); ?>" class="<?php echo $mob_cls; ?>" title="<?php echo esc_attr($tab['label']); ?>">
                <span class="bae-mob-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $tab['icon']; ?></svg>
                </span>
                <span class="bae-mob-label"><?php echo esc_html($tab['label']); ?></span>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- Shared Modal -->
        <div id="bae-modal-overlay" class="bae-modal-overlay" style="display:none;">
            <div class="bae-modal">
                <div class="bae-modal-header">
                    <span id="bae-modal-title" class="bae-modal-title">Asset Preview</span>
                    <button class="bae-modal-close" id="bae-modal-close">&times;</button>
                </div>
                <div class="bae-modal-body" id="bae-modal-body"></div>
                <div class="bae-modal-footer">
    <button class="bae-btn bae-btn-outline" id="bae-modal-cancel">Close</button>
    <button class="bae-btn bae-btn-outline" id="bae-modal-download-png">Download PNG</button>
    <button class="bae-btn bae-btn-primary" id="bae-modal-copy">Copy HTML</button>
</div>
            </div>
        </div>

        
        <!-- Confirm Modal -->
        <div id="bae-confirm-overlay" class="bae-modal-overlay" style="display:none;">
            <div class="bae-modal bae-modal-sm">
                <div class="bae-modal-header">
                    <span class="bae-modal-title">Confirm Action</span>
                </div>
                <div class="bae-modal-body">
                    <p id="bae-confirm-msg"></p>
                </div>
                <div class="bae-modal-footer">
                    <button class="bae-btn bae-btn-outline" id="bae-confirm-cancel">Cancel</button>
                    <button class="bae-btn bae-btn-danger" id="bae-confirm-ok">Confirm</button>
                </div>
            </div>
        </div>


        <!-- Pricing Modal -->
        <div id="bae-pricing-overlay" class="bae-pricing-overlay" style="display:none;">
            <div class="bae-pricing-modal">
                <button class="bae-pricing-close" onclick="baePricingClose()">&times;</button>

                <div class="bae-pricing-header">
                    <div class="bae-pricing-eyebrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                        Unlock the full Mothie
                    </div>
                    <div class="bae-pricing-title" id="bae-pricing-title">Take your brand further</div>
                    <div class="bae-pricing-subtitle" id="bae-pricing-subtitle">Regenerate assets anytime, use the custom AI generator, and share your brand kit publicly.</div>
                </div>

                <div class="bae-pricing-cards">
                    <!-- Starter -->
                    <div class="bae-pricing-card">
                        <div class="bae-pricing-plan-name">Starter</div>
                        <div class="bae-pricing-price">₱49 <span>/mo</span></div>
                        <div class="bae-pricing-period">or ₱199 one-time lifetime</div>
                        <ul class="bae-pricing-features">
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                All 7 brand assets — generate anytime
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Regenerate assets as often as you want
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Public brand kit page with shareable link
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Custom AI Generator — 10 uses/month
                            </li>
                            <li class="locked">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5c5972" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
                                Single brand workspace
                            </li>
                        </ul>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <button class="bae-pricing-cta outline" onclick="baePricingSelect('starter','monthly')">Get Starter — ₱49/mo</button>
                            <button class="bae-pricing-cta outline" style="font-size:12px;padding:8px 16px;opacity:0.8;" onclick="baePricingSelect('starter','lifetime')">One-time Lifetime — ₱199</button>
                        </div>
                    </div>

                    <!-- Pro -->
                    <div class="bae-pricing-card recommended">
                        <div class="bae-pricing-recommended-badge">✦ Most Popular</div>
                        <div class="bae-pricing-plan-name">Pro</div>
                        <div class="bae-pricing-price">₱99 <span>/mo</span></div>
                        <div class="bae-pricing-period">Best value for growing brands</div>
                        <ul class="bae-pricing-features">
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f76fb0" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Everything in Starter
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f76fb0" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Unlimited Custom AI Generator
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f76fb0" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Single brand workspace
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f76fb0" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Priority AI generation
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f76fb0" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Early access to new features
                            </li>
                        </ul>
                        <button class="bae-pricing-cta primary" onclick="baePricingSelect('pro')">Get Pro — ₱99/mo</button>
                    </div>
                </div>

                <div class="bae-pricing-footer">
                    Your free assets are always yours — upgrading unlocks more power, not access to what you already have.<br>
                    <a href="#" onclick="baePricingClose()" style="color:var(--text-3);text-decoration:underline;margin-top:4px;display:inline-block;">Continue with Free</a>
                </div>
            </div>
        </div>



        <!-- Body Row: Sidebar + Content -->
        <div class="bae-body-row">

        <!-- Sidebar nav (desktop) -->
        <nav class="bae-sidebar" id="bae-sidebar">
            <div class="bae-sidebar-toggle">
                <button class="bae-sidebar-toggle-btn" id="bae-sidebar-toggle-btn" onclick="baeSidebarToggle()" title="Collapse sidebar">
                    <svg id="bae-sb-icon-collapse" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <svg id="bae-sb-icon-expand" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
            <?php foreach ($nav_tabs as $slug => $tab): ?>
            <?php
                $is_active = $active_tab === $slug;
                $is_locked = $tab['locked'];
                $lock_msg  = $tab['lock_msg'];
                $nav_cls   = 'bae-hn-item';
                if ($is_active)  $nav_cls .= ' bae-hn-active';
                if ($is_locked)  $nav_cls .= ' bae-hn-locked';
            ?>
            <?php if ($is_locked): ?>
            <button type="button" class="<?php echo $nav_cls; ?>"
                onclick="baeNavLockModal(<?php echo esc_attr(json_encode($tab['label'])); ?>, <?php echo esc_attr(json_encode($lock_msg)); ?>)"
                title="<?php echo esc_attr($tab['label']); ?>">
                <span class="bae-hn-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $tab['icon']; ?></svg>
                    <svg class="bae-hn-lock lucide lucide-lock" xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <span class="bae-hn-label"><?php echo esc_html($tab['label']); ?></span>
            </button>
            <?php else: ?>
            <a href="<?php echo esc_url(add_query_arg('tab', $slug, $base_url)); ?>" class="<?php echo $nav_cls; ?>" title="<?php echo esc_attr($tab['label']); ?>">
                <span class="bae-hn-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $tab['icon']; ?></svg>
                </span>
                <span class="bae-hn-label"><?php echo esc_html($tab['label']); ?></span>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- Tab Content -->
        <div class="bae-tab-content">
            <?php
            $profile_tabs = ['logo', 'assets', 'kit', 'startup', 'brand_book'];
            if (in_array($active_tab, $profile_tabs) && !$has_profile) $active_tab = 'overview';
            // Identity board removed as standalone tab — absorbed into brand profile

            if ($active_tab === 'overview')        echo bae_overview_tab($user_id, $profile);
            elseif ($active_tab === 'identity')    { wp_redirect(strtok($_SERVER['REQUEST_URI'],'?').'?tab=overview'); exit; }
            elseif ($active_tab === 'logo')        echo bae_logo_tab($user_id, $profile);
            elseif ($active_tab === 'assets')      echo bae_assets_tab($user_id, $profile);
            elseif ($active_tab === 'kit')         echo bae_kit_tab($user_id, $profile);
            elseif ($active_tab === 'startup')     echo bae_startup_tab($user_id, $profile);
            elseif ($active_tab === 'brand_book')  echo bae_brand_book_tab($user_id, $profile);
            elseif ($active_tab === 'settings')    echo bae_settings_tab($user_id, $profile);
            // Post-onboarding dashboard
            elseif ($active_tab === 'dashboard')   echo bae_home_dashboard($user_id, $profile);
            ?>
        </div>

        </div><!-- /.bae-body-row -->

    </div>

    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&family=Noto+Serif+JP:wght@200;300;400&display=swap" rel="stylesheet">
    <style>
    /* ═══════════════════════════════════════════════════
       BAE — DESIGN SYSTEM v2
       Purple/Pink · Dark/Light · Minimal Premium
    ═══════════════════════════════════════════════════ */

    /* Tokens */
    .bae-wrap {
        --brand:       #F32D86;
        --brand-deep:  #c4196a;
        --brand-soft:  #f76fb0;
        --brand-glow:  #fbadd3;
        --pink:        #F32D86;
        --pink-soft:   #f472b6;
        --ease-out:    cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Dark theme (default) */
    .bae-wrap {
        --bg:         #000000;
        --bg-2:       #0a0a0a;
        --bg-3:       #111111;
        --surface:    #161616;
        --surface-2:  #1e1e1e;
        --border:     rgba(255,255,255,0.06);
        --border-2:   rgba(255,255,255,0.10);
        --text:       #f5f5f5;
        --text-2:     #a0a0a0;
        --text-3:     #555555;
        --input-bg:   #111111;
        --input-bd:   rgba(243,45,134,0.25);
        --shadow:     0 1px 3px rgba(0,0,0,0.7), 0 8px 24px rgba(0,0,0,0.5);
    }

    .bae-wrap.bae-light {
        background:
            linear-gradient(160deg, #ffffff 0%, #EFF3F6 30%, #e8edf1 100%);
        --bg:         #EFF3F6;
        --bg-2:       #e8edf1;
        --bg-3:       #dde4ea;
        --surface:    #ffffff;
        --surface-2:  #f5f7f9;
        --border:     rgba(0,0,0,0.07);
        --border-2:   rgba(0,0,0,0.12);
        --text:       #111418;
        --text-2:     #5a6472;
        --text-3:     #93a0af;
        --input-bg:   #ffffff;
        --input-bd:   rgba(243,45,134,0.25);
        --shadow:     0 1px 3px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.08);
    }

    /* Base reset */
    .bae-wrap *, .bae-wrap *::before, .bae-wrap *::after { box-sizing: border-box; margin: 0; padding: 0; }

    .bae-wrap {
        font-family: 'Geist', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--bg);
        color: var(--text);
        border-radius: 0;
        overflow: hidden;
        transition: background 0.5s, color 0.5s;
        position: relative;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    /* body row = sidebar + content */
    .bae-body-row {
        display: flex;
        flex: 1;
        min-height: 0;
    }
    /* Sidebar nav */
    .bae-sidebar {
        width: 220px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        padding: 12px 10px;
        gap: 2px;
        background: var(--bg);
        border-right: 1px solid var(--border);
        transition: width 0.28s cubic-bezier(0.4,0,0.2,1), background 0.5s, border-color 0.5s;
        position: sticky;
        top: 58px;
        height: calc(100vh - 58px);
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: none;
        z-index: 50;
    }
    .bae-sidebar::-webkit-scrollbar { display: none; }
    .bae-sidebar.collapsed { width: 52px; }
    .bae-sidebar-toggle {
        display: flex; align-items: center; justify-content: flex-end;
        padding: 4px 2px 10px;
        flex-shrink: 0;
    }
    .bae-sidebar-toggle-btn {
        width: 28px; height: 28px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text-3);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
        flex-shrink: 0;
    }
    .bae-sidebar-toggle-btn:hover { color: var(--text); background: var(--surface-2); border-color: var(--border-2); }
    .bae-sidebar .bae-hn-item {
        width: 100%;
        display: flex; align-items: center; gap: 10px;
        padding: 9px 10px;
        border-radius: 10px;
        font-size: 13px; font-weight: 500; font-family: 'Geist', sans-serif;
        color: var(--text-3); text-decoration: none; white-space: nowrap;
        background: none; border: none; cursor: pointer;
        transition: color .15s, background .15s;
        position: relative;
        overflow: hidden;
    }
    .bae-sidebar .bae-hn-item:hover { color: var(--text-2); background: var(--surface); }
    .bae-sidebar .bae-hn-active {
        color: var(--text) !important;
        background: rgba(243,45,134,0.10) !important;
        box-shadow: inset 0 0 0 1px rgba(243,45,134,0.22);
    }
    .bae-sidebar.collapsed .bae-hn-label { display: none; }
    .bae-sidebar.collapsed .bae-hn-item { padding: 9px; justify-content: center; }
    .bae-sidebar.collapsed .bae-sidebar-toggle { justify-content: center; }
    .bae-tab-content {
        flex: 1;
        min-width: 0;
    }

    div[style*="position:fixed"][style*="bottom:10px"][style*="right:10px"][style*="z-index:99999"] {
        display: none !important;
    }

    /* Glow orb bg */
    .bae-wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 60% 40% at 80% 10%, rgba(243,45,134,0.1) 0%, transparent 60%),
            radial-gradient(ellipse 40% 30% at 10% 90%, rgba(243,45,134,0.06) 0%, transparent 50%);
        pointer-events: none;
        z-index: 0;
        transition: background 0.5s;
    }

    .bae-wrap > * { position: relative; z-index: 1; }

    /* ── HEADER BAR ── */
    .bae-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0 20px;
        height: 58px;
        border-bottom: none;
        background: var(--bg);
        transition: background 0.5s;
        position: sticky; top: 0; z-index: 100;
        overflow-x: auto; scrollbar-width: none;
    }
    .bae-header::-webkit-scrollbar { display: none; }
    .bae-header-brand {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .bae-header-logo-img {
        width: 32px;
        height: 32px;
        object-fit: contain;
        flex-shrink: 0;
        display: block;
    }
    .bae-wrap .bae-header-logo-img { filter: invert(1); }
    .bae-wrap.bae-light .bae-header-logo-img { filter: none; }
    .bae-header-wordmark {
        font-family: 'Noto Serif JP', 'Noto Serif', 'Yu Mincho', 'Hiragino Mincho Pro', serif;
        font-size: 10px;
        font-weight: 300;
        letter-spacing: 0.32em;
        color: var(--text-2);
        text-transform: uppercase;
        writing-mode: horizontal-tb;
        transition: color 0.4s;
        line-height: 1;
        margin-top: 1px;
    }
    .bae-wrap.bae-light .bae-header-wordmark { color: var(--text-3); }
    .bae-header-logo {
        display: flex; align-items: center; gap: 10px; flex-shrink: 0;
    }
    .bae-brand-mark {
        width: 52px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .bae-brand-logo-img {
        width: 100%;
        height: auto;
        display: block;
    }
    .bae-brand-wordmark {
        display: inline-flex;
        align-items: flex-end;
        gap: 0.03em;
        font-family: 'Instrument Serif', serif;
        font-size: 18px; font-style: italic;
        color: var(--text); transition: color 0.5s;
        line-height: 1;
    }
    .bae-brand-wordmark-leading,
    .bae-brand-wordmark-trailing,
    .bae-brand-wordmark-letter {
        display: inline-block;
    }
    .bae-brand-wordmark-center {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        margin: 0 0.08em;
        line-height: 0.88;
    }
    .bae-brand-wordmark-logo {
        width: 1.9em;
        margin-bottom: -0.04em;
    }
    .bae-brand-wordmark-letter {
        color: var(--brand-soft);
    }
    .bae-brand-wordmark-merge .bae-brand-wordmark-center {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-direction: row;
        margin: 0 0.02em 0 0.04em;
        line-height: 1;
    }
    .bae-brand-wordmark-merge .bae-brand-wordmark-letter-o {
        color: transparent;
    }
    .bae-brand-wordmark-logo-merge {
        width: 1.12em;
        margin: 0;
        position: absolute;
        left: 50%;
        top: 52%;
        transform: translate(-50%, -50%);
    }
    .bae-wrap .bae-brand-logo-img {
        filter: invert(1);
    }
    .bae-wrap.bae-light .bae-brand-logo-img {
        filter: none;
    }

    .bae-free-plan-tip {
        display:flex;
        align-items:center;
        gap:12px;
        padding:12px 16px;
        background:linear-gradient(135deg,rgba(195,25,106,.1),rgba(243,45,134,.06));
        border:1px solid rgba(243,45,134,.2);
        border-radius:12px;
        margin-bottom:20px;
        flex-wrap:wrap;
    }
    .bae-free-plan-tip-copy {
        font-size:13px;
        color:var(--text-2);
        flex:1;
        min-width:220px;
        line-height:1.55;
    }
    .bae-free-plan-tip .bae-btn {
        white-space:nowrap;
    }
    .bae-dash-shell {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .bae-dash-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 24px 28px;
        border-bottom: 1px solid var(--border);
        background:
            radial-gradient(circle at 0% 0%, rgba(243,45,134,.12), transparent 28%),
            var(--bg-2);
        transition: background 0.5s, border-color 0.5s;
    }
    .bae-wrap.bae-light .bae-dash-hero {
        background:
            radial-gradient(circle at 0% 0%, rgba(243,45,134,.06), transparent 28%),
            var(--surface);
        border-bottom: 1px solid var(--border);
    }
    .bae-dash-user {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
    }
    .bae-dash-avatar {
        width: 66px;
        height: 66px;
        border-radius: 22px;
        overflow: hidden;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(243,45,134,.18), rgba(243,45,134,.16));
        border: 1px solid rgba(255,255,255,.10);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.08);
    }
    .bae-wrap.bae-light .bae-dash-avatar {
        background: linear-gradient(135deg, rgba(243,45,134,.12), rgba(243,45,134,.10));
        border: 1px solid var(--border);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.35);
    }
    .bae-dash-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .bae-dash-avatar-fallback {
        width: 100%;
        height: 100%;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bae-dash-avatar-fallback > * {
        max-width: 100%;
    }
    .bae-dash-avatar-fallback-light {
        display: none;
    }
    .bae-wrap.bae-light .bae-dash-avatar-fallback-dark {
        display: none;
    }
    .bae-wrap.bae-light .bae-dash-avatar-fallback-light {
        display: flex;
    }
    .bae-dash-copy {
        min-width: 0;
    }
    .bae-dash-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 999px;
        background: rgba(243,45,134,.1);
        border: 1px solid rgba(243,45,134,.2);
        color: var(--brand-soft);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .bae-dash-title {
        font-family: 'Instrument Serif', serif;
        font-size: clamp(22px, 3vw, 28px);
        line-height: 1.05;
        color: var(--text);
        margin-bottom: 4px;
    }
    .bae-dash-sub {
        font-size: 13px;
        line-height: 1.6;
        color: var(--text-2);
    }
    .bae-dash-metrics {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .bae-dash-metric {
        min-width: 120px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 10px;
        background: rgba(243,45,134,0.06);
        border: 1px solid rgba(243,45,134,0.14);
    }
    .bae-wrap.bae-light .bae-dash-metric {
        background: rgba(243,45,134,0.05);
        border: 1px solid rgba(243,45,134,0.12);
    }
    .bae-dash-metric-icon {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .bae-dash-metric-icon svg {
        width: 15px;
        height: 15px;
    }
    .bae-dash-metric-value {
        font-size: 18px;
        font-weight: 800;
        color: var(--text);
        line-height: 1;
        margin-bottom: 2px;
    }
    .bae-dash-metric-label {
        font-size: 11px;
        color: var(--text-3);
        line-height: 1.3;
    }
    /* ── DASHBOARD TOOLS GRID (matches header nav aesthetic) ── */
    .bae-dash-tools {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 0;
        border-top: 1px solid var(--border);
    }
    .bae-dash-tools::-webkit-scrollbar { display: none; }
    .bae-dash-card {
        padding: 20px 22px;
        color: var(--text);
        text-decoration: none;
        position: relative;
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 14px;
        border-right: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        background: var(--bg-2);
        transition: background .15s, color .15s;
    }
    .bae-dash-card:hover {
        background: rgba(243,45,134,0.06);
    }
    .bae-dash-card:last-child { border-right: none; }
    .bae-dash-card::before { display: none; }
    .bae-dash-card::after  { display: none; }
    .bae-dash-card-top {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 0;
    }
    .bae-dash-card-bottom { display: none; }
    .bae-dash-card-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(243,45,134,0.1);
        border: 1px solid rgba(243,45,134,0.2);
        flex-shrink: 0;
    }
    .bae-dash-card:hover .bae-dash-card-icon {
        background: rgba(243,45,134,0.18);
    }
    .bae-dash-card-icon svg {
        width: 17px;
        height: 17px;
    }
    .bae-dash-card-title {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.2;
        color: var(--text);
        margin-bottom: 2px;
        letter-spacing: 0;
    }
    .bae-dash-card-desc {
        font-size: 11px;
        line-height: 1.4;
        color: var(--text-3);
        max-width: none;
    }
    .bae-dash-card-kicker { display: none; }
    /* Arrow indicator */
    .bae-dash-card-arrow {
        flex-shrink: 0;
        color: var(--text-3);
        opacity: 0;
        transition: opacity .15s, transform .15s;
    }
    .bae-dash-card:hover .bae-dash-card-arrow {
        opacity: 1;
        transform: translateX(2px);
    }
    .bae-dash-identity {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 28px;
        background: var(--bg-2);
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
    }
    .bae-dash-swatches {
        display: flex;
        gap: 6px;
        align-items: center;
    }
    .bae-dash-swatch {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 1px solid rgba(0,0,0,.08);
        flex-shrink: 0;
    }
    .bae-dash-fonts {
        font-size: 13px;
        color: var(--text-2);
    }
    .bae-dash-edit {
        font-size: 12px;
        font-weight: 600;
        color: var(--brand-soft);
        text-decoration: none;
        white-space: nowrap;
    }
    .bae-dash-edit:hover { color: var(--brand); }
    @media (max-width: 720px) {
        .bae-free-plan-tip {
            align-items:flex-start;
            gap:10px;
            padding:10px 12px;
        }
        .bae-free-plan-tip svg {
            width:14px;
            height:14px;
            flex-shrink:0;
            margin-top:2px;
        }
        .bae-free-plan-tip-copy {
            font-size:12px;
            min-width:0;
        }
        .bae-free-plan-tip .bae-btn {
            width:100%;
            justify-content:center;
        }
        .bae-dash-hero { padding: 18px 18px; }
        .bae-dash-avatar { width: 52px; height: 52px; border-radius: 16px; }
        .bae-dash-title  { font-size: 22px; }
        .bae-dash-tools  { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 980px) {
        .bae-dash-hero {
            flex-direction: column;
            align-items: flex-start;
        }
        .bae-dash-metrics {
            justify-content: flex-start;
            width: 100%;
        }
        .bae-dash-metric {
            min-width: calc(50% - 6px);
            flex: 1 1 180px;
        }
        .bae-dash-identity {
            flex-direction: column;
            align-items: flex-start;
            padding: 14px 18px;
        }
    }
    @media (max-width: 480px) {
        .bae-dash-tools { grid-template-columns: 1fr; }
        .bae-dash-card { border-right: none; }
    }
    .bae-header-right { margin-left: auto; display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

    /* ── Header Login Button ── */
    .bae-header-login-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 14px; border-radius: 10px;
        font-size: 12px; font-weight: 700; font-family: 'Geist', sans-serif;
        color: #fff; background: linear-gradient(135deg, #c4196a, #F32D86);
        border: none; cursor: pointer; white-space: nowrap;
        box-shadow: 0 4px 16px rgba(243,45,134,0.35);
        transition: transform .18s, box-shadow .18s, opacity .18s;
        letter-spacing: .01em;
    }
    .bae-header-login-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 22px rgba(243,45,134,0.48); }
    .bae-header-login-btn:active { transform: translateY(0); opacity: .88; }
    .bae-header-login-btn svg { flex-shrink: 0; }

    /* ── Header Ticket Chip (when logged in) ── */
    .bae-auth-ticket-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px; border-radius: 999px;
        background: rgba(243,45,134,0.08); border: 1px solid rgba(243,45,134,0.22);
        font-size: 11px; font-weight: 700; font-family: 'Geist', monospace;
        color: #F32D86; letter-spacing: .06em; white-space: nowrap;
        cursor: default; user-select: all;
        transition: background .2s, border-color .2s;
    }
    .bae-auth-ticket-chip:hover { background: rgba(243,45,134,0.12); border-color: rgba(243,45,134,0.34); }
    .bae-auth-ticket-chip svg { flex-shrink: 0; opacity: .8; }

    /* ── Header Logout Button ── */
    .bae-header-logout-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 14px; border-radius: 10px;
        font-size: 12px; font-weight: 600; font-family: 'Geist', sans-serif;
        color: var(--text-2); background: var(--surface-2);
        border: 1px solid var(--border); cursor: pointer; white-space: nowrap;
        transition: color .15s, background .15s, border-color .15s, transform .15s;
        letter-spacing: .01em;
    }
    .bae-header-logout-btn:hover {
        color: #fb7185; background: rgba(244,63,94,0.07);
        border-color: rgba(244,63,94,0.22); transform: translateY(-1px);
    }
    .bae-header-logout-btn:active { transform: translateY(0); }
    .bae-header-logout-btn svg { flex-shrink: 0; }

    /* ── Ticket Login Modal ── */
    .bae-ticket-modal-overlay {
        position: fixed; inset: 0; z-index: 99999;
        background: rgba(0,0,0,0.72); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center; padding: 24px;
        opacity: 0; visibility: hidden;
        transition: opacity .25s, visibility .25s;
    }
    .bae-ticket-modal-overlay.open { opacity: 1; visibility: visible; }
    .bae-ticket-modal {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 22px; width: 100%; max-width: 420px;
        box-shadow: 0 28px 80px rgba(0,0,0,0.55);
        transform: translateY(18px) scale(.97);
        transition: transform .3s cubic-bezier(0.16,1,0.3,1);
        overflow: hidden;
    }
    .bae-ticket-modal-overlay.open .bae-ticket-modal { transform: translateY(0) scale(1); }
    .bae-ticket-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 22px; border-bottom: 1px solid var(--border);
        background: linear-gradient(135deg, rgba(196,25,106,0.1), rgba(243,45,134,0.05));
    }
    .bae-ticket-modal-header-left { display: flex; align-items: center; gap: 12px; }
    .bae-ticket-modal-icon {
        width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
        background: linear-gradient(135deg, #c4196a, #F32D86);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 14px rgba(243,45,134,0.35);
    }
    .bae-ticket-modal-title {
        font-family: 'Instrument Serif', serif; font-size: 17px; font-style: italic;
        color: var(--text); line-height: 1;
    }
    .bae-ticket-modal-body { padding: 24px 24px 20px; }
    .bae-ticket-modal-desc {
        font-size: 13px; color: var(--text-2); line-height: 1.65;
        margin: 0 0 20px; transition: color .5s;
    }
    .bae-ticket-modal-input-wrap { margin-bottom: 12px; }
    .bae-ticket-modal-input {
        width: 100%; box-sizing: border-box;
        background: var(--bg-3, var(--surface-2));
        border: 1.5px solid var(--border);
        border-radius: 12px; padding: 14px 18px;
        font-size: 20px; font-family: 'Geist', monospace;
        font-weight: 700; letter-spacing: .15em;
        color: var(--text); outline: none; text-align: center;
        text-transform: uppercase;
        transition: border-color .2s, box-shadow .2s;
    }
    .bae-ticket-modal-input:focus { border-color: #F32D86; box-shadow: 0 0 0 3px rgba(243,45,134,0.15); }
    .bae-ticket-modal-input::placeholder { color: var(--text-3); font-size: 14px; letter-spacing: .06em; }
    .bae-ticket-modal-input-otp { font-size: 26px; letter-spacing: .22em; }
    .bae-ticket-modal-err {
        font-size: 12px; color: #fb7185;
        background: rgba(244,63,94,.08); border: 1px solid rgba(244,63,94,.2);
        border-radius: 9px; padding: 9px 14px; margin-bottom: 12px; text-align: center;
    }
    .bae-ticket-modal-submit {
        width: 100%; justify-content: center; gap: 8px;
        padding: 13px 20px; font-size: 14px; font-weight: 700;
        background: linear-gradient(135deg, #c4196a, #F32D86) !important;
        box-shadow: 0 6px 20px rgba(243,45,134,0.38) !important;
        transition: transform .18s, box-shadow .18s !important;
    }
    .bae-ticket-modal-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 28px rgba(243,45,134,0.5) !important; }
    .bae-ticket-modal-submit:disabled { opacity: .42; cursor: not-allowed; transform: none !important; }
    .bae-tkm-spin {
        width: 16px; height: 16px; flex-shrink: 0;
        border: 2px solid rgba(255,255,255,.3); border-top-color: #fff;
        border-radius: 50%; animation: bae-tkm-spin .7s linear infinite; display: none;
    }
    @keyframes bae-tkm-spin { to { transform: rotate(360deg); } }
    .bae-ticket-modal-otp-hint { font-size: 13px; color: var(--text-3); margin: 0 0 14px; text-align: center; }
    .bae-ticket-modal-back {
        display: flex; align-items: center; gap: 6px; justify-content: center;
        width: 100%; margin-top: 10px; padding: 8px;
        background: none; border: none; cursor: pointer;
        font-size: 12px; color: var(--text-3); font-family: 'Geist', sans-serif;
        font-weight: 600; transition: color .15s;
    }
    .bae-ticket-modal-back:hover { color: var(--text-2); }
    .bae-ticket-modal-footer {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; padding: 14px 22px; border-top: 1px solid var(--border);
    }
    .bae-ticket-modal-footer-hint {
        display: flex; align-items: center; gap: 5px;
        font-size: 12px; color: var(--text-3);
    }
    .bae-ticket-modal-new-btn {
        background: none; border: none; cursor: pointer;
        font-size: 12px; font-weight: 700; color: #F32D86;
        font-family: 'Geist', sans-serif; padding: 0;
        text-decoration: underline; text-underline-offset: 3px;
        transition: opacity .15s;
    }
    .bae-ticket-modal-new-btn:hover { opacity: .75; }
    @media (max-width: 480px) {
        .bae-auth-ticket-chip { display: none; }
        .bae-header-login-btn span, .bae-header-logout-btn span { display: none; }
        .bae-header-login-btn, .bae-header-logout-btn { padding: 7px 10px; }
    }


    /* ── 7-TAB HEADER NAV ── */
    .bae-headnav {
        display: none; /* hidden on desktop — sidebar handles nav */
    }
    .bae-headnav::-webkit-scrollbar { display: none; }
    .bae-hn-item {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 12px; border-radius: 9px;
        font-size: 12px; font-weight: 600; font-family: 'Geist', sans-serif;
        color: var(--text-3); text-decoration: none; white-space: nowrap;
        background: none; border: none; cursor: pointer;
        transition: color .15s, background .15s;
        position: relative;
    }
    .bae-hn-item:hover { color: var(--text-2); background: rgba(255,255,255,0.05); }
    .bae-hn-active {
        color: var(--text) !important;
        background: rgba(243,45,134,0.10) !important;
        box-shadow: inset 0 0 0 1px rgba(243,45,134,0.22);
    }
    .bae-hn-locked { opacity: 0.42; cursor: pointer !important; }
    .bae-hn-locked:hover { opacity: 0.65; background: rgba(255,255,255,0.04) !important; color: var(--text-3) !important; }
    .bae-hn-icon { position: relative; display: flex; align-items: center; flex-shrink: 0; }
    .bae-hn-lock {
        position: absolute; bottom: -4px; right: -5px;
        color: var(--text-3); opacity: 0.9;
    }
    .bae-mob-icon { position: relative; display: flex; align-items: center; justify-content: center; }
    .bae-mob-lock { right: -6px; bottom: -5px; }
    /* Lock modal */
    .bae-lock-modal-overlay {
        position: fixed; inset: 0; z-index: 99998;
        background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center; padding: 24px;
        opacity: 0; visibility: hidden; transition: opacity .25s, visibility .25s;
    }
    .bae-lock-modal-overlay.open { opacity: 1; visibility: visible; }
    .bae-lock-modal {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 20px; width: 100%; max-width: 380px;
        box-shadow: 0 24px 80px rgba(0,0,0,0.6);
        transform: translateY(14px); transition: transform .3s cubic-bezier(0.16,1,0.3,1);
        overflow: hidden;
    }
    .bae-lock-modal-overlay.open .bae-lock-modal { transform: translateY(0); }
    .bae-lock-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 22px; border-bottom: 1px solid var(--border);
        background: linear-gradient(135deg, rgba(195,25,106,0.1), rgba(243,45,134,0.05));
    }
    .bae-lock-modal-title {
        font-family: 'Instrument Serif', serif; font-size: 16px; font-style: italic; color: var(--text);
    }
    .bae-lock-modal-body { padding: 24px 22px; text-align: center; }
    .bae-lock-modal-icon {
        width: 48px; height: 48px; border-radius: 14px; margin: 0 auto 16px;
        background: linear-gradient(135deg, #c4196a, #F32D86);
        display: flex; align-items: center; justify-content: center;
    }
    .bae-lock-modal-msg { font-size: 14px; color: var(--text-2); line-height: 1.6; margin: 0; }
    .bae-lock-modal-footer {
        display: flex; align-items: center; justify-content: flex-end;
        padding: 14px 22px; border-top: 1px solid var(--border);
    }

    /* Mobile floating bottom nav */
    .bae-mobile-nav {
        display: none;
        position: fixed;
        bottom: 16px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 200;
        background: var(--surface);
        border: 1px solid var(--border-2);
        border-radius: 24px;
        padding: 8px 10px;
        gap: 2px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.22), 0 2px 8px rgba(0,0,0,0.14);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        align-items: center;
        justify-content: center;
        max-width: calc(100vw - 32px);
        overflow-x: auto;
        scrollbar-width: none;
        transition: transform .22s ease, opacity .22s ease;
    }
    .bae-mobile-nav::-webkit-scrollbar { display: none; }
    .bae-mobile-nav.collapsed {
        transform: translateX(-50%) translateY(140%);
        opacity: 0;
        pointer-events: none;
    }
    .bae-mobile-nav-toggle {
        display: none;
        position: fixed;
        bottom: 84px;
        right: 14px;
        z-index: 210;
        border: 1px solid var(--border-2);
        background: var(--surface);
        color: var(--text-2);
        border-radius: 999px;
        padding: 8px 10px;
        gap: 6px;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 11px;
        font-weight: 700;
        font-family: 'Geist', sans-serif;
        box-shadow: 0 8px 24px rgba(0,0,0,0.18);
        transition: all .18s ease;
    }
    .bae-mobile-nav-toggle:hover {
        color: var(--text);
        border-color: var(--border);
        background: var(--surface-2);
    }
    .bae-mobile-nav-toggle.collapsed {
        bottom: 14px;
    }
    .bae-mob-item {
        display: flex; flex-direction: column; align-items: center;
        gap: 3px; padding: 8px 10px; border-radius: 16px;
        color: var(--text-3); text-decoration: none;
        background: none; border: none; cursor: pointer;
        transition: color .15s, background .15s;
        min-width: 44px; flex-shrink: 0;
    }
    .bae-mob-item:hover { color: var(--text-2); background: var(--surface-2); }
    .bae-mob-active {
        color: #F32D86 !important;
        background: rgba(243,45,134,0.10) !important;
    }
    .bae-mob-label {
        font-size: 9px; font-weight: 600; font-family: 'Geist', sans-serif;
        letter-spacing: 0.02em; line-height: 1; white-space: nowrap;
    }
    .bae-hn-locked.bae-mob-item { opacity: 0.4; }

    @media (max-width: 768px) {
        .bae-sidebar { display: none !important; }
        .bae-mobile-nav { display: flex; }
        .bae-mobile-nav-toggle { display: inline-flex; }
        .bae-body-row { flex-direction: column; }
        .bae-tab-content { padding-bottom: 88px; }
    }

    /* Theme toggle */
    .bae-theme-btn {
        display: flex; align-items: center; gap: 8px;
        background: var(--surface); border: 1px solid var(--border-2);
        border-radius: 10px; padding: 7px 14px;
        font-size: 12px; font-weight: 600; color: var(--text-2);
        cursor: pointer; font-family: 'Geist', sans-serif;
        transition: all 0.2s;
    }
    .bae-theme-btn:hover { color: var(--text); border-color: var(--brand-soft); }

    /* ── THEME SWITCH (Apple-style) ── */
    .bae-theme-switch {
        background: none; border: none; padding: 0; cursor: pointer;
        display: flex; align-items: center;
    }
    .bae-ts-track {
        width: 46px; height: 26px;
        background: #c8d0d8;
        border-radius: 999px;
        position: relative;
        transition: background 0.3s cubic-bezier(0.4,0,0.2,1);
        flex-shrink: 0;
    }
    .bae-ts-track.on { background: #34c759; }
    .bae-ts-thumb {
        position: absolute;
        top: 3px; left: 3px;
        width: 20px; height: 20px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 1px 4px rgba(0,0,0,0.25), 0 0 0 0.5px rgba(0,0,0,0.08);
        transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
        display: flex; align-items: center; justify-content: center;
        color: #888;
    }
    .bae-ts-track.on .bae-ts-thumb {
        transform: translateX(20px);
        color: #555;
    }
    .bae-ts-thumb svg { pointer-events: none; }

    .bae-toggle-track { display: none; } /* hide old toggle if referenced anywhere */
    .bae-toggle-thumb { display: none; }
    .bae-theme-btn { display: none; } /* hide old btn class */
    .bae-stepper { display: flex; align-items: center; }
    .bae-step-wrap { display: flex; align-items: center; flex-shrink: 0; }
    .bae-step { display: flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer; border: none; background: none; }
    .bae-step-node { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
    .bae-step-label { font-size: 12px; font-weight: 500; color: var(--text-3); }
    .bae-step-active .bae-step-label { color: var(--text); font-weight: 700; }
    .bae-step-locked { opacity: .5; cursor: not-allowed; pointer-events: none; }
    .bae-step-line { width: 28px; height: 1.5px; background: var(--border-2); flex-shrink: 0; margin: 0 4px; }
    .bae-step-line-done { background: rgba(243,45,134,.35); }

    /* ── PRO BADGE on steps ── */
    .bae-tab-pro-badge {
        font-size: 9px; font-weight: 800;
        letter-spacing: 0.06em;
        padding: 2px 6px; border-radius: 5px;
        background: linear-gradient(135deg, #c4196a, #F32D86);
        color: white;
        animation: bae-pro-pulse 2.5s ease-in-out infinite;
        box-shadow: 0 0 8px rgba(243,45,134,0.5);
    }
    @keyframes bae-pro-pulse {
        0%, 100% { box-shadow: 0 0 6px rgba(243,45,134,0.4); }
        50%       { box-shadow: 0 0 14px rgba(243,45,134,0.7); }
    }

    /* ── PRICING MODAL ── */
    .bae-pricing-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.75); backdrop-filter: blur(10px);
        z-index: 999999; display: flex; align-items: center; justify-content: center;
        padding: 24px; animation: bae-fade-in 0.2s ease;
    }
    @keyframes bae-fade-in { from { opacity:0; } to { opacity:1; } }
    .bae-pricing-modal {
        background: var(--bg-2); border: 1px solid var(--border);
        border-radius: 24px; width: 100%; max-width: 680px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(243,45,134,0.1);
        animation: bae-slide-up 0.3s cubic-bezier(0.16,1,0.3,1);
    }
    @keyframes bae-slide-up { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }
    .bae-pricing-header {
        padding: 32px 32px 0;
        text-align: center;
    }
    .bae-pricing-eyebrow {
        font-size: 11px; font-weight: 700; letter-spacing: 0.15em;
        text-transform: uppercase; color: var(--brand-soft);
        display: flex; align-items: center; justify-content: center; gap: 6px;
        margin-bottom: 12px;
    }
    .bae-pricing-title {
        font-family: 'Instrument Serif', serif;
        font-size: 28px; font-style: italic;
        color: var(--text); margin-bottom: 8px;
    }
    .bae-pricing-subtitle { font-size: 14px; color: var(--text-3); margin-bottom: 28px; }
    .bae-pricing-cards {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 14px; padding: 0 32px 32px;
    }
    .bae-pricing-card {
        background: var(--surface); border: 1.5px solid var(--border);
        border-radius: 18px; padding: 24px;
        position: relative; transition: border-color 0.2s;
    }
    .bae-pricing-card.recommended {
        border-color: #F32D86;
        background: linear-gradient(145deg, rgba(195,25,106,0.08), rgba(243,45,134,0.04));
    }
    .bae-pricing-recommended-badge {
        position: absolute; top: -11px; left: 50%; transform: translateX(-50%);
        background: linear-gradient(135deg, #c4196a, #F32D86);
        color: white; font-size: 10px; font-weight: 800;
        padding: 3px 14px; border-radius: 999px; letter-spacing: 0.08em;
        white-space: nowrap;
    }
    .bae-pricing-plan-name {
        font-size: 13px; font-weight: 700; color: var(--text-2);
        text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px;
    }
    .bae-pricing-price {
        font-family: 'Instrument Serif', serif;
        font-size: 36px; color: var(--text); line-height: 1;
        margin-bottom: 4px;
    }
    .bae-pricing-price span { font-size: 16px; font-family: 'Geist', sans-serif; color: var(--text-3); }
    .bae-pricing-period { font-size: 12px; color: var(--text-3); margin-bottom: 20px; }
    .bae-pricing-features { list-style: none; margin-bottom: 20px; display: flex; flex-direction: column; gap: 9px; }
    .bae-pricing-features li {
        font-size: 13px; color: var(--text-2);
        display: flex; align-items: flex-start; gap: 8px; line-height: 1.4;
    }
    .bae-pricing-features li svg { flex-shrink: 0; margin-top: 1px; }
    .bae-pricing-features li.locked { color: var(--text-3); }
    .bae-pricing-cta {
        width: 100%; padding: 12px; border-radius: 12px;
        font-size: 14px; font-weight: 700; font-family: 'Geist', sans-serif;
        cursor: pointer; border: none; transition: all 0.2s;
    }
    .bae-pricing-cta.primary {
        background: linear-gradient(135deg, #c4196a, #F32D86);
        color: white; box-shadow: 0 6px 20px rgba(195,25,106,0.35);
    }
    .bae-pricing-cta.primary:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(195,25,106,0.45); }
    .bae-pricing-cta.outline {
        background: var(--surface-2); color: var(--text-2);
        border: 1px solid var(--border-2);
    }
    .bae-pricing-cta.outline:hover { border-color: var(--brand-soft); color: var(--text); }
    .bae-pricing-close {
        position: absolute; top: 16px; right: 16px;
        background: var(--bg-3); border: none; width: 30px; height: 30px;
        border-radius: 8px; cursor: pointer; color: var(--text-2);
        font-size: 18px; display: flex; align-items: center; justify-content: center;
        transition: all 0.2s;
    }
    .bae-pricing-close:hover { background: var(--border-2); color: var(--text); }
    .bae-pricing-footer {
        text-align: center; padding: 0 32px 24px;
        font-size: 12px; color: var(--text-3);
    }
    /* ── LOCKED ASSET CARD OVERLAY ── */
    .bae-asset-locked-overlay {
        position: absolute; inset: 0;
        background: rgba(10,10,15,0.7); backdrop-filter: blur(3px);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 8px; border-radius: 18px; z-index: 2;
        cursor: pointer; transition: background 0.2s;
    }
    .bae-asset-locked-overlay:hover { background: rgba(10,10,15,0.6); }
    .bae-asset-locked-icon {
        width: 36px; height: 36px; border-radius: 10px;
        background: linear-gradient(135deg, #c4196a, #F32D86);
        display: flex; align-items: center; justify-content: center;
    }
    .bae-asset-locked-label { font-size: 12px; font-weight: 700; color: white; }
    .bae-asset-locked-sub { font-size: 11px; color: rgba(255,255,255,0.5); }
    @media (max-width: 540px) {
        .bae-pricing-cards { grid-template-columns: 1fr; }
        .bae-pricing-header { padding: 24px 20px 0; }
        .bae-pricing-cards { padding: 0 20px 24px; }
    }

    /* ── TAB CONTENT ── */
    .bae-tab-content { padding: 28px; background: var(--bg); transition: background 0.5s; min-height: calc(100vh - 58px); }

    @media (min-width: 1025px) {
        .bae-tab-content {
            padding: 42px 96px;
        }
    }

    /* ── CARDS ── */
    .bae-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 26px;
        margin-bottom: 20px;
        transition: background 0.5s, border-color 0.5s;
    }
    .bae-card:hover { border-color: var(--border-2); }
    .bae-card-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 22px;
    }
    .bae-card-title {
        font-family: 'Instrument Serif', serif;
        font-size: 17px; font-style: italic;
        color: var(--text); transition: color 0.5s;
        display: flex; align-items: center; gap: 10px;
    }
    .bae-card-title::before {
        content: '';
        display: block; width: 3px; height: 16px;
        background: linear-gradient(180deg, var(--brand), var(--pink));
        border-radius: 999px; flex-shrink: 0;
    }
    .bae-card-desc { font-size: 13px; color: var(--text-3); margin-top: 4px; transition: color 0.5s; }

    /* ── STATS ── */
    .bae-stats-row {
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 14px; margin-bottom: 24px;
    }
    .bae-stat-card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 16px; padding: 20px 22px;
        transition: background 0.5s, border-color 0.5s, transform 0.2s;
    }
    .bae-stat-card:hover { transform: translateY(-2px); border-color: var(--border-2); }
    .bae-stat-label {
        font-size: 10px; font-weight: 700; color: var(--text-3);
        text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px;
        transition: color 0.5s;
    }
    .bae-stat-value {
        font-family: 'Instrument Serif', serif;
        font-size: 28px; color: var(--text); line-height: 1;
        transition: color 0.5s;
    }
    .bae-stat-sub { font-size: 12px; color: var(--text-3); margin-top: 4px; transition: color 0.5s; }

    /* ── BADGES ── */
    .bae-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 11px; border-radius: 999px;
        font-size: 11px; font-weight: 600; letter-spacing: 0.02em;
    }
    .bae-badge-green  { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
    .bae-badge-gray   { background: var(--bg-3); color: var(--text-3); border: 1px solid var(--border); }
    .bae-badge-yellow { background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2); }
    .bae-badge-purple { background: rgba(243,45,134,0.12); color: var(--brand-soft); border: 1px solid rgba(243,45,134,0.2); }

    /* ── BUTTONS ── */
    .bae-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        padding: 10px 20px; border-radius: 12px;
        font-size: 13px; font-weight: 600;
        font-family: 'Geist', sans-serif;
        border: none; cursor: pointer;
        transition: all 0.2s; text-decoration: none;
        letter-spacing: 0.01em;
    }
    .bae-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .bae-btn-primary {
        background: linear-gradient(135deg, var(--brand-deep), var(--brand));
        color: white;
        box-shadow: 0 4px 14px rgba(195,25,106,0.3);
    }
    .bae-btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(195,25,106,0.4);
    }
    .bae-btn-outline {
        background: var(--surface-2); color: var(--text-2);
        border: 1px solid var(--border-2);
    }
    .bae-btn-outline:hover:not(:disabled) { color: var(--text); border-color: var(--brand-soft); }
    .bae-btn-danger { background: #f43f5e; color: white; }
    .bae-btn-danger:hover:not(:disabled) { background: #e11d48; box-shadow: 0 6px 20px rgba(244,63,94,0.35); }
    .bae-btn-success { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.25); }
    .bae-btn-success:hover:not(:disabled) { background: rgba(16,185,129,0.25); }
    .bae-btn-sm { padding: 7px 14px; font-size: 12px; border-radius: 9px; }

    /* ── FORM ── */
    .bae-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .bae-form-grid.three { grid-template-columns: 1fr 1fr 1fr; }
    .bae-form-grid.full { grid-template-columns: 1fr; }
    .bae-form-group { display: flex; flex-direction: column; gap: 7px; }
    .bae-form-group label {
        font-size: 12px; font-weight: 600;
        color: var(--text-2); letter-spacing: 0.01em;
        transition: color 0.5s;
    }
    .bae-form-group input[type="text"],
    .bae-form-group input[type="email"],
    .bae-form-group input[type="tel"],
    .bae-form-group input[type="url"],
    .bae-form-group select,
    .bae-form-group textarea {
        background: var(--input-bg);
        border: 1px solid var(--input-bd);
        border-radius: 11px; padding: 11px 14px;
        font-size: 14px; font-family: 'Geist', sans-serif;
        color: var(--text);
        outline: none; width: 100%;
        transition: border-color 0.2s, box-shadow 0.2s, background 0.5s, color 0.5s;
        appearance: none;
    }
    .bae-form-group input:focus,
    .bae-form-group select:focus,
    .bae-form-group textarea:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(243,45,134,0.15);
    }
    .bae-form-group input::placeholder,
    .bae-form-group textarea::placeholder { color: var(--text-3); }
    .bae-form-group textarea { resize: vertical; min-height: 90px; }
    .bae-form-group small { font-size: 12px; color: var(--text-3); transition: color 0.5s; }

    /* ── COLOR PICKER ── */
    .bae-color-row { display: flex; align-items: center; gap: 10px; }
    .bae-color-row input[type="color"] {
        width: 44px; height: 44px;
        border: 1px solid var(--border-2); border-radius: 11px;
        padding: 2px; cursor: pointer; background: none;
        flex-shrink: 0;
    }
    .bae-color-row input[type="text"] {
        flex: 1; font-family: monospace; font-size: 13px; font-weight: 600;
    }

    /* ── NOTICES ── */
    .bae-notice { padding: 12px 16px; border-radius: 11px; font-size: 13px; margin-top: 14px; }
    .bae-notice-success { background: rgba(16,185,129,0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
    .bae-notice-error   { background: rgba(244,63,94,0.1); color: #fb7185; border: 1px solid rgba(244,63,94,0.2); }
    .bae-notice-info    { background: rgba(243,45,134,0.1); color: var(--brand-soft); border: 1px solid rgba(243,45,134,0.2); }

    /* ── EMPTY STATE ── */
    .bae-empty { text-align: center; padding: 60px 20px; color: var(--text-3); }
    .bae-empty svg { width: 52px; height: 52px; margin-bottom: 16px; opacity: 0.3; }
    .bae-empty p { font-size: 14px; margin-top: 8px; }

    /* ── SECTION LABEL ── */
    .bae-section-label {
        font-size: 10px; font-weight: 700;
        color: var(--brand-soft); text-transform: uppercase;
        letter-spacing: 0.12em; margin-bottom: 16px;
        transition: color 0.5s;
    }

    /* ── DIVIDER ── */
    .bae-divider { height: 1px; background: var(--border); margin: 24px 0; transition: background 0.5s; }

    /* ── ASSET GRID ── */
    .bae-assets-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }

.bae-asset-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    overflow: hidden;
    transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
    position: relative;
    cursor: pointer;
}
.bae-asset-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 24px 48px rgba(0,0,0,.35);
    border-color: rgba(243,45,134,.35);
}

/* Thumbnail fills the whole card */
.bae-asset-preview {
    height: 240px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(243,45,134,.08), rgba(243,45,134,.05));
    border-bottom: none;
}
.bae-asset-preview-inner {
    transform: scale(0.4);
    transform-origin: center center;
    width: 250%;
    pointer-events: none;
    overflow: hidden;
    isolation: isolate;
}
.bae-asset-preview-empty {
    height: 240px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    background: var(--bg-3);
    border-bottom: none;
}

/* Hover overlay — slides up from bottom */
.bae-asset-info,
.bae-asset-actions {
    position: absolute;
    left: 0; right: 0;
    z-index: 2;
    transition: transform 0.3s cubic-bezier(0.16,1,0.3,1), opacity 0.3s ease;
}
.bae-asset-info {
    bottom: 48px; /* sits just above actions */
    padding: 12px 16px 0;
    transform: translateY(20px);
    opacity: 0;
}
.bae-asset-actions {
    bottom: 0;
    padding: 10px 16px 14px;
    transform: translateY(20px);
    opacity: 0;
    border-top: none;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

/* Frosted glass backdrop — only visible on hover */
.bae-asset-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to top,
        var(--bg) 0%,
        rgba(from var(--bg) r g b / 0.75) 50%,
        transparent 100%
    );
    opacity: 0;
    transition: opacity 0.3s ease, backdrop-filter 0.3s ease;
    z-index: 1;
    border-radius: 18px;
    pointer-events: none;
}

/* Trigger on hover */
.bae-asset-card:hover::after {
    opacity: 1;
}
.bae-asset-card:hover .bae-asset-info,
.bae-asset-card:hover .bae-asset-actions {
    transform: translateY(0);
    opacity: 1;
}

/* Text colors inside overlay */
.bae-asset-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    transition: color 0.5s;
}
.bae-asset-meta {
    font-size: 12px;
    color: var(--text-3);
    margin-top: 3px;
    transition: color 0.5s;
}

/* On hover, text flips to white so it's readable over the dark overlay */
.bae-asset-card:hover .bae-asset-name { color: #fff; }
.bae-asset-card:hover .bae-asset-meta { color: rgba(255,255,255,.65); }

/* Light mode hover keeps dark text since overlay is lighter */
.bae-wrap.bae-light .bae-asset-card:hover .bae-asset-name { color: var(--text); }
.bae-wrap.bae-light .bae-asset-card:hover .bae-asset-meta { color: var(--text-2); }




        #bae-card-social_kit .bae-asset-info,
#bae-card-social_kit .bae-asset-actions,
#bae-card-social_kit::after {
    position: static;
    transform: none;
    opacity: 1;
    backdrop-filter: none;
    background: none;
}

    /* ── GENERATE ROW ── */
    .bae-generate-row {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
    }

    /* ── PROGRESS BAR ── */
    .bae-progress-wrap { margin-top: 12px; display: none; }
    .bae-progress-wrap.visible { display: block; }
    .bae-progress-bar-track {
        background: var(--bg-3); border-radius: 999px; height: 6px;
        overflow: hidden; margin-bottom: 8px; transition: background 0.5s;
    }
    .bae-progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--brand-deep), var(--pink));
        border-radius: 999px; width: 0%; transition: width 0.5s var(--ease-out);
    }
    .bae-progress-label { font-size: 12px; color: var(--text-3); transition: color 0.5s; }
    .bae-progress-steps { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }
    .bae-progress-step {
        font-size: 11px; font-weight: 600; padding: 4px 11px; border-radius: 999px;
        background: var(--bg-3); color: var(--text-3);
        transition: all 0.3s;
    }
    .bae-progress-step.active { background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2); }
    .bae-progress-step.done   { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
    .bae-progress-step.error  { background: rgba(244,63,94,0.12); color: #fb7185; border: 1px solid rgba(244,63,94,0.2); }

    /* ── IDENTITY BOARD ── */
    .bae-color-swatches { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 14px; }
    .bae-swatch { display: flex; flex-direction: column; align-items: center; gap: 7px; }
    .bae-swatch-block { width: 72px; height: 72px; border-radius: 16px; border: 1px solid var(--border); }
    .bae-swatch-label { font-size: 11px; color: var(--text-3); text-align: center; transition: color 0.5s; }
    .bae-swatch-hex { font-size: 11px; font-weight: 700; color: var(--text-2); font-family: monospace; transition: color 0.5s; }
    .bae-swatch { cursor: pointer; }
    .bae-swatch-copy-btn {
        font-size: 10px; font-weight: 700;
        padding: 3px 9px; border-radius: 999px;
        background: var(--bg-3); color: var(--text-3);
        border: 1px solid var(--border-2);
        cursor: pointer; font-family: 'Geist', sans-serif;
        transition: all 0.2s; display: flex; align-items: center; gap: 4px;
        opacity: 0; transform: translateY(3px);
    }
    .bae-swatch:hover .bae-swatch-copy-btn { opacity: 1; transform: translateY(0); }
    .bae-swatch-copy-btn svg { width: 10px; height: 10px; }
    .bae-swatch-copy-btn.copied { background: rgba(16,185,129,0.12); color: #34d399; border-color: rgba(16,185,129,0.2); }
    .bae-swatch-copy-wrap { position: relative; }
    .bae-swatch-copy-menu {
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        min-width: 88px;
        background: var(--surface);
        border: 1px solid var(--border-2);
        border-radius: 12px;
        box-shadow: 0 18px 36px rgba(0,0,0,.24);
        padding: 6px;
        display: none;
        z-index: 20;
    }
    .bae-swatch-copy-menu.open { display: block; }
    .bae-swatch-copy-option {
        width: 100%;
        border: 0;
        background: transparent;
        color: var(--text-2);
        font: inherit;
        font-size: 11px;
        font-weight: 700;
        text-align: left;
        border-radius: 8px;
        padding: 7px 9px;
        cursor: pointer;
    }
    .bae-swatch-copy-option:hover { background: rgba(243,45,134,.12); color: var(--text); }
    .bae-font-copy-row { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
    .bae-font-copy-btn {
        font-size: 10px; font-weight: 700;
        padding: 3px 9px; border-radius: 999px;
        background: var(--bg-3); color: var(--text-3);
        border: 1px solid var(--border-2);
        cursor: pointer; font-family: 'Geist', sans-serif;
        transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px;
    }
    .bae-font-copy-btn:hover { color: var(--text); border-color: var(--brand-soft); }
    .bae-font-copy-btn svg { width: 10px; height: 10px; }
    .bae-font-copy-btn.copied { background: rgba(16,185,129,0.12); color: #34d399; border-color: rgba(16,185,129,0.2); }

    .bae-font-sample {
        padding: 20px; border: 1px solid #e5e7eb; border-radius: 14px;
        margin-top: 14px; background: #ffffff;
        transition: border-color 0.5s;
    }
    .bae-font-heading-sample {
        font-family: 'Instrument Serif', serif;
        font-size: 26px; font-style: italic; line-height: 1.2;
        color: var(--brand-soft); transition: color 0.5s;
    }
    .bae-font-body-sample { font-size: 14px; color: #374151; margin-top: 8px; line-height: 1.7; }

    /* ── LOGO MOCKUP ── */
    .bae-logo-mockup {
        display: flex; align-items: center; gap: 16px;
        padding: 24px; border: 1px solid #e5e7eb;
        border-radius: 14px; margin-top: 14px;
        background: #ffffff;
    }
    .bae-logo-mockup.dark-bg { background: var(--bg); }
    .bae-logo-icon-shape {
        width: 52px; height: 52px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-family: 'Instrument Serif', serif;
        font-size: 18px; font-style: italic; font-weight: 400; color: white;
    }
    .bae-logo-text-area .bae-logo-name {
        font-family: 'Instrument Serif', serif;
        font-size: 20px; font-style: italic; color: var(--text); line-height: 1;
        transition: color 0.5s;
    }
    .bae-logo-text-area .bae-logo-tagline {
        font-size: 11px; color: var(--text-3); margin-top: 5px;
        letter-spacing: 0.1em; text-transform: uppercase;
        transition: color 0.5s;
    }

    /* ── TONE TAGS ── */
    .bae-tone-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
    .bae-tone-tag {
        padding: 6px 15px; border-radius: 999px; font-size: 13px; font-weight: 500;
        background: rgba(243,45,134,0.1); color: var(--brand-soft);
        border: 1px solid rgba(243,45,134,0.2);
        transition: all 0.5s;
    }

    /* ── KIT PAGE ── */
    .bae-kit-wrap { max-width: 860px; margin: 0 auto; padding: 48px 24px; }

    /* ── DANGER ZONE ── */
    .bae-danger-zone {
        border: 1px solid rgba(244,63,94,0.25);
        border-radius: 16px; padding: 24px;
        background: rgba(244,63,94,0.05);
    }
    .bae-danger-zone .bae-card-title { color: #fb7185; }

    /* ── MODAL ── */
    .bae-modal-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
        z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 24px;
    }
    .bae-modal {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 20px; width: 100%; max-width: 820px;
        max-height: 88vh; display: flex; flex-direction: column; overflow: hidden;
        box-shadow: 0 24px 80px rgba(0,0,0,0.6);
    }
    .bae-modal-sm { max-width: 420px; }
    .bae-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 24px; border-bottom: 1px solid var(--border);
        transition: border-color 0.5s;
    }
    .bae-modal-title {
        font-family: 'Instrument Serif', serif;
        font-size: 17px; font-style: italic; color: var(--text); transition: color 0.5s;
    }
    .bae-modal-close {
        background: var(--bg-3); border: none; width: 30px; height: 30px;
        border-radius: 8px; cursor: pointer; color: var(--text-2);
        font-size: 18px; display: flex; align-items: center; justify-content: center;
        line-height: 1; transition: all 0.2s;
    }
    .bae-modal-close:hover { background: var(--border-2); color: var(--text); }
    .bae-modal-body { padding: 24px; overflow-y: auto; flex: 1; }
    .bae-modal-body p { font-size: 14px; color: var(--text-2); line-height: 1.6; transition: color 0.5s; }
    .bae-modal-footer {
        display: flex; align-items: center; justify-content: flex-end; gap: 10px;
        padding: 16px 24px; border-top: 1px solid var(--border); transition: border-color 0.5s;
    }

    /* ── SCROLLBAR ── */
    /* ── PROFILE SPLIT LAYOUT ── */
    .bae-profile-split {
        display: flex;
        gap: 20px;
        align-items: flex-start;
    }
    .bae-profile-form-col { flex: 1; min-width: 0; }
    .bae-preview-panel {
        width: 340px;
        min-width: 300px;
        flex-shrink: 0;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        position: sticky;
        top: 20px;
    }
    .bae-preview-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border);
        background: var(--bg-2);
    }
    .bae-preview-panel-tabs {
        display: flex;
        gap: 2px;
        padding: 8px 10px;
        border-bottom: 1px solid var(--border);
        background: var(--bg-2);
        overflow-x: auto;
        scrollbar-width: none;
    }
    .bae-preview-panel-tabs::-webkit-scrollbar { display: none; }
    .bae-preview-tab-btn {
        padding: 5px 10px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 600;
        font-family: 'Geist', sans-serif;
        border: none;
        background: none;
        color: var(--text-3);
        cursor: pointer;
        white-space: nowrap;
        transition: all .15s;
    }
    .bae-preview-tab-btn:hover { color: var(--text-2); background: var(--bg-3); }
    .bae-preview-tab-btn.active { color: var(--text); background: rgba(243,45,134,.12); }
    .bae-preview-panel-content {
        height: 340px;
        overflow: hidden;
        position: relative;
        padding: 12px;
        background: var(--bg-3);
    }

    /* Brand Intelligence */
    .bae-brand-intel .bae-card-header { margin-bottom: 0; }
    #bae-intel-body { padding-top: 16px; }

    @media (max-width: 900px) {
        .bae-profile-split { flex-direction: column; }
        .bae-preview-panel { width: 100%; min-width: 0; position: static; }
    }
    /* Mobile: preview as bottom sheet */
    @media (max-width: 680px) {
        .bae-preview-panel {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            width: 100%;
            border-radius: 20px 20px 0 0;
            z-index: 9990;
            max-height: 70vh;
            overflow-y: auto;
            transform: translateY(100%);
            transition: transform .3s cubic-bezier(.16,1,.3,1);
            box-shadow: 0 -8px 40px rgba(0,0,0,.4);
        }
        .bae-preview-panel.open { transform: translateY(0); }
    }

    /* ── BENTO GRID SYSTEM ── */
    .bae-bento-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        grid-auto-flow: dense;
        gap: 16px;
    }
    .bae-bento-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 24px;
        padding: 24px;
        transition: background 0.3s, border-color 0.3s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
    }
    .bae-bento-card:hover {
        border-color: var(--border-2);
        box-shadow: 0 4px 20px rgba(243,45,134,0.08);
    }
    .bae-bento-card.span-8 { grid-column: span 8; }
    .bae-bento-card.span-6 { grid-column: span 6; }
    .bae-bento-card.span-4 { grid-column: span 4; }
    .bae-bento-card.span-12 { grid-column: span 12; }
    @media (max-width: 900px) {
        .bae-bento-grid { grid-template-columns: 1fr 1fr; }
        .bae-bento-card.span-8,
        .bae-bento-card.span-6,
        .bae-bento-card.span-4 { grid-column: span 1; }
        .bae-bento-card.span-12 { grid-column: span 2; }
    }
    @media (max-width: 600px) {
        .bae-bento-grid { grid-template-columns: 1fr; }
        .bae-bento-card.span-12 { grid-column: span 1; }
    }
    .bae-bento-card-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 14px;
        background: linear-gradient(135deg, rgba(243,45,134,0.15), rgba(167,139,250,0.08));
        color: var(--brand-soft);
        flex-shrink: 0;
    }
    .bae-bento-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .1em; color: var(--text-3);
        margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
    }
    .bae-bento-label::after {
        content: ''; flex: 1; height: 1px; background: var(--border);
    }
    /* Soft glassmorphism inputs for bento cards */
    .bae-bento-card .bae-form-group input,
    .bae-bento-card .bae-form-group select,
    .bae-bento-card .bae-form-group textarea {
        background: var(--bg-2);
        border: 1.5px solid transparent;
        border-radius: 14px;
        padding: 11px 14px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        transition: border-color .2s, box-shadow .2s;
    }
    .bae-bento-card .bae-form-group input:focus,
    .bae-bento-card .bae-form-group select:focus,
    .bae-bento-card .bae-form-group textarea:focus {
        border-color: rgba(243,45,134,0.5);
        box-shadow: 0 0 0 3px rgba(243,45,134,0.1), inset 0 1px 3px rgba(0,0,0,0.05);
        outline: none;
    }
    .bae-wrap.bae-light .bae-bento-card .bae-form-group input,
    .bae-wrap.bae-light .bae-bento-card .bae-form-group select,
    .bae-wrap.bae-light .bae-bento-card .bae-form-group textarea {
        background: #f5f4ff;
        border-color: transparent;
    }
    /* AI pill buttons */
    .bae-ai-pill {
        display: inline-flex; align-items: center; gap: 5px;
        background: linear-gradient(135deg, rgba(243,45,134,0.12), rgba(243,45,134,0.08));
        border: 1px solid rgba(243,45,134,0.25);
        border-radius: 999px; padding: 4px 12px;
        font-size: 11px; font-weight: 600; color: var(--brand-soft);
        cursor: pointer; font-family: 'Geist', sans-serif;
        transition: all .2s; white-space: nowrap;
    }
    .bae-ai-pill:hover {
        background: linear-gradient(135deg, rgba(243,45,134,0.2), rgba(243,45,134,0.12));
        border-color: rgba(243,45,134,0.4);
        transform: translateY(-1px);
    }

    /* ── ASSET SEARCH + DRAG ── */
    .bae-asset-toolbar {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 18px; flex-wrap: wrap;
    }
    .bae-asset-search {
        flex: 1; min-width: 200px; max-width: 340px;
        display: flex; align-items: center; gap: 8px;
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 12px; padding: 8px 14px;
        transition: border-color .2s;
    }
    .bae-asset-search:focus-within { border-color: rgba(243,45,134,0.5); }
    .bae-asset-search svg { flex-shrink: 0; color: var(--text-3); }
    .bae-asset-search input {
        background: none; border: none; outline: none;
        font-size: 13px; font-family: 'Geist', sans-serif;
        color: var(--text); width: 100%;
    }
    .bae-asset-search input::placeholder { color: var(--text-3); }
    .bae-asset-card.bae-drag-over {
        border: 2px dashed var(--brand) !important;
        background: rgba(243,45,134,0.06) !important;
    }
    .bae-asset-card.bae-dragging {
        opacity: 0.4;
        transform: scale(0.97);
        transition: all .15s;
    }
    .bae-asset-card[draggable="true"] { cursor: grab; }
    .bae-asset-card[draggable="true"]:active { cursor: grabbing; }
    .bae-assets-hidden { display: none !important; }
    .bae-asset-no-results {
        grid-column: 1 / -1;
        text-align: center; padding: 40px;
        font-size: 14px; color: var(--text-3);
    }

    /* ── COMPLETENESS INDICATOR ── */
    .bae-completeness-bar {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 14px; padding: 16px 20px; margin-bottom: 20px;
        transition: background 0.5s, border-color 0.5s;
    }
    .bae-completeness-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .bae-completeness-label { font-size: 12px; font-weight: 600; color: var(--text-2); transition: color 0.5s; }
    .bae-completeness-pct { font-family: 'Instrument Serif', serif; font-size: 16px; font-style: italic; color: var(--brand-soft); transition: color 0.5s; }
    .bae-completeness-track { height: 4px; background: var(--bg-3); border-radius: 999px; overflow: hidden; margin-bottom: 12px; transition: background 0.5s; }
    .bae-completeness-fill { height: 100%; background: linear-gradient(90deg, var(--brand-deep), var(--brand-soft)); border-radius: 999px; transition: width 0.6s cubic-bezier(0.16,1,0.3,1); }
    .bae-completeness-items { display: flex; flex-wrap: wrap; gap: 6px; }
    .bae-completeness-item { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px; }
    .bae-completeness-item svg { width: 10px; height: 10px; }
    .bae-completeness-item.done { background: rgba(16,185,129,0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
    .bae-completeness-item.todo { background: var(--bg-3); color: var(--text-3); border: 1px solid var(--border); }

    /* Frontend toasts */
    .bae-toast-stack {
        position: fixed;
        right: 16px;
        bottom: 18px;
        z-index: 999999;
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-width: min(90vw, 360px);
    }
    .bae-toast {
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text-2);
        font-size: 12px;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(0,0,0,0.22);
    }
    .bae-toast.success { border-color: rgba(16,185,129,0.35); color: #34d399; background: rgba(16,185,129,0.08); }
    .bae-toast.error { border-color: rgba(244,63,94,0.35); color: #fb7185; background: rgba(244,63,94,0.08); }
    .bae-toast.info { border-color: rgba(243,45,134,0.35); color: var(--brand-soft); background: rgba(243,45,134,0.1); }

    .bae-wrap ::-webkit-scrollbar { width: 5px; }
    .bae-wrap ::-webkit-scrollbar-track { background: transparent; }
    .bae-wrap ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 999px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
        .bae-stats-row { grid-template-columns: 1fr 1fr; }
        .bae-assets-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 680px) {
        .bae-tab-content { padding: 16px; }
        .bae-form-grid { grid-template-columns: 1fr; }
        .bae-form-grid.three { grid-template-columns: 1fr; }
        .bae-stats-row { grid-template-columns: 1fr 1fr; }
        .bae-assets-grid { grid-template-columns: 1fr; }
        .bae-header { padding: 0 14px; height: 52px; }
    }
    </style>

    <?php
    // CHANGED: Color picker sync JS is defined ONCE here at the top level — removed duplicate in bae_overview_tab
    ?>
    <script>
    (function() {
        // ── Color picker sync ──────────────────────────────────────────────
        function initColorPairs(root) {
            (root || document).querySelectorAll('.bae-color-pair').forEach(function(pair) {
                var picker = pair.querySelector('input[type="color"]');
                var text   = pair.querySelector('input[type="text"]');
                if (!picker || !text) return;
                picker.addEventListener('input', function() { text.value = this.value; });
                text.addEventListener('input', function() {
                    if (/^#[0-9a-fA-F]{6}$/.test(this.value)) picker.value = this.value;
                });
            });
        }
        initColorPairs();
        window.baeInitColorPairs = initColorPairs;
        window.baeToast = function(message, type, ttl) {
            if (!message) return;
            var kind = type || 'info';
            var duration = (typeof ttl === 'number' ? ttl : 3200);
            var stack = document.getElementById('bae-toast-stack');
            if (!stack) {
                stack = document.createElement('div');
                stack.id = 'bae-toast-stack';
                stack.className = 'bae-toast-stack';
                document.body.appendChild(stack);
            }
            var el = document.createElement('div');
            el.className = 'bae-toast ' + kind;
            el.textContent = message;
            stack.appendChild(el);
            if (window.gsap) {
                gsap.fromTo(el, {opacity:0, y:10}, {opacity:1, y:0, duration:0.22, ease:'power3.out'});
            }
            setTimeout(function() {
                if (window.gsap) {
                    gsap.to(el, {opacity:0, y:8, duration:0.18, onComplete:function(){ if (el && el.parentNode) el.parentNode.removeChild(el); }});
                } else if (el && el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, Math.max(1200, duration));
        };

        // ── Modal ──────────────────────────────────────────────────────────
        var overlay   = document.getElementById('bae-modal-overlay');
        var modalBody = document.getElementById('bae-modal-body');
        var modalTitle = document.getElementById('bae-modal-title');
        var copyBtn   = document.getElementById('bae-modal-copy');
        var closeBtn  = document.getElementById('bae-modal-close');
        var cancelBtn = document.getElementById('bae-modal-cancel');

        window.baeOpenModal = function(title, html) {
            if (!overlay) return;
            modalTitle.textContent = title;
            modalBody.innerHTML = '<div class="bae-ai-asset" style="isolation:isolate;">' + html + '</div>';
            overlay.style.display = 'flex';
        };

        function closeBaeModal() { if (overlay) overlay.style.display = 'none'; }
        if (closeBtn)  closeBtn.addEventListener('click',  closeBaeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeBaeModal);
        if (overlay)   overlay.addEventListener('click', function(e) { if (e.target === this) closeBaeModal(); });

        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                var html = modalBody.innerHTML;
                navigator.clipboard.writeText(html).then(function() {
                    copyBtn.textContent = 'Copied!';
                    setTimeout(function() { copyBtn.textContent = 'Copy HTML'; }, 2000);
                });
            });
        }


           // Load html2canvas lazily then download
var dlPngBtn = document.getElementById('bae-modal-download-png');
if (dlPngBtn) {
    dlPngBtn.addEventListener('click', function() {
        var target = modalBody.querySelector('.bae-ai-asset') || modalBody;
        dlPngBtn.textContent = 'Rendering...';
        dlPngBtn.disabled = true;

        // Load html2canvas if not already loaded
        function doCapture() {
            html2canvas(target, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false
            }).then(function(canvas) {
                var link = document.createElement('a');
                var title = (modalTitle.textContent || 'asset').toLowerCase().replace(/\s+/g, '-');
                link.download = title + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                dlPngBtn.textContent = 'Download PNG';
                dlPngBtn.disabled = false;
            }).catch(function() {
                dlPngBtn.textContent = 'Failed — retry';
                dlPngBtn.disabled = false;
            });
        }

        if (window.html2canvas) {
            doCapture();
        } else {
            var s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
            s.onload = doCapture;
            s.onerror = function() {
                dlPngBtn.textContent = 'Failed — retry';
                dlPngBtn.disabled = false;
            };
            document.head.appendChild(s);
        }
    });
}                                                                                                                                                                                                                                                                                           
                    

        // ── Confirm Modal ──────────────────────────────────────────────────
        var confirmOverlay = document.getElementById('bae-confirm-overlay');
        var confirmMsg     = document.getElementById('bae-confirm-msg');
        var confirmOk      = document.getElementById('bae-confirm-ok');
        var confirmCancel  = document.getElementById('bae-confirm-cancel');
        var confirmCallback = null;

        window.baeConfirm = function(msg, cb) {
            confirmMsg.textContent = msg;
            confirmCallback = cb;
            confirmOverlay.style.display = 'flex';
        };

        if (confirmOk) {
            confirmOk.addEventListener('click', function() {
                confirmOverlay.style.display = 'none';
                if (typeof confirmCallback === 'function') confirmCallback();
            });
        }
        if (confirmCancel) {
            confirmCancel.addEventListener('click', function() {
                confirmOverlay.style.display = 'none';
                confirmCallback = null;
            });
        }

        // ── Pricing Modal ──────────────────────────────────────────────────
        window.baePricingOpen = function(title, subtitle) {
            var overlay = document.getElementById('bae-pricing-overlay');
            if (!overlay) return;
            if (title)    document.getElementById('bae-pricing-title').textContent    = title;
            if (subtitle) document.getElementById('bae-pricing-subtitle').textContent = subtitle;
            overlay.style.display = 'flex';
            if (window.gsap) gsap.fromTo('.bae-pricing-modal', {opacity:0,y:20}, {opacity:1,y:0,duration:0.35,ease:'power3.out'});
        };

        window.baePricingClose = function() {
            var overlay = document.getElementById('bae-pricing-overlay');
            if (!overlay) return;
            if (window.gsap) {
                gsap.to('.bae-pricing-modal', {opacity:0, y:16, duration:0.2, ease:'power2.in', onComplete:function(){ overlay.style.display='none'; }});
            } else { overlay.style.display = 'none'; }
        };

        window.baePricingSelect = function(plan, billing) {
            billing = billing || 'monthly';
            var btns = document.querySelectorAll('.bae-pricing-cta');
            var clickedBtn = event && event.target ? event.target : null;
            var origText   = clickedBtn ? clickedBtn.textContent : '';

            var hasTicket = /(?:^|;\s*)bae_ticket=/.test(document.cookie || '');
            if (!hasTicket) {
                if (clickedBtn) clickedBtn.textContent = origText;
                btns.forEach(function(b){ b.disabled = false; });
                baePricingClose();
                baeTicketModalOpen('pricing');
                baeTicketModalShowErr('Bind this workspace with a ticket first before upgrading.');
                return;
            }

            btns.forEach(function(b){ b.disabled = true; });
            if (clickedBtn) clickedBtn.textContent = 'Redirecting...';

            var fd = new FormData();
            fd.append('action',  'bae_pm_checkout');
            fd.append('nonce',   (window.BAE_PM && window.BAE_PM.nonce) ? window.BAE_PM.nonce : '');
            fd.append('plan',    plan);
            fd.append('billing', billing);

            fetch((window.BAE_PM && window.BAE_PM.ajax_url) ? window.BAE_PM.ajax_url : ajaxurl, { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(res) {
                    if (res.success && res.data.checkout_url) {
                        window.location.href = res.data.checkout_url;
                    } else {
                        var msg = (res.data && res.data.message) ? res.data.message : 'Something went wrong. Please try again.';
                        if (/not logged in|unauthorized|no identity|ticket/i.test(msg)) {
                            baePricingClose();
                            baeTicketModalOpen('pricing');
                            baeTicketModalShowErr('Bind this workspace with a ticket first before upgrading.');
                        } else {
                            if (typeof window.baeToast === 'function') window.baeToast(msg, 'error');
                        }
                        btns.forEach(function(b){ b.disabled = false; });
                        if (clickedBtn) clickedBtn.textContent = origText;
                    }
                })
                .catch(function() {
                    if (typeof window.baeToast === 'function') window.baeToast('Network error. Please try again.', 'error');
                    btns.forEach(function(b){ b.disabled = false; });
                    if (clickedBtn) clickedBtn.textContent = origText;
                });
        };

        // After PayMaya redirect back — refresh plan badge without full reload
        (function() {
            var params = new URLSearchParams(window.location.search);
            if (params.get('bae_pm') === 'success') {
                // Remove bae_pm param from URL cleanly
                params.delete('bae_pm');
                params.delete('ref');
                var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.history.replaceState({}, '', newUrl);
                // Refresh plan badge by reloading just the stats row
                setTimeout(function() { location.reload(); }, 800);
            }
        })();

        // Close on overlay click
        var pricingOverlay = document.getElementById('bae-pricing-overlay');
        if (pricingOverlay) {
            pricingOverlay.addEventListener('click', function(e) {
                if (e.target === this) baePricingClose();
            });
        }
    })();
    </script>

    <script>
    /* BAE — THEME TOGGLE + GSAP */
    var baeIsDark = (localStorage.getItem('bae_theme') === 'dark');

    function baeApplyTheme(dark, animate) {
        var wrap   = document.getElementById('bae-wrap');
        var loader = document.getElementById('bae-page-loader');
        var track  = document.getElementById('bae-toggle-track');
        var sun    = document.getElementById('bae-ts-sun');
        var moon   = document.getElementById('bae-ts-moon');
        if (!wrap) return;

        if (dark) {
            wrap.classList.remove('bae-light');
            if (loader) loader.classList.remove('bae-light');
            if (track)  track.classList.add('on');
            if (sun)    sun.style.display  = 'none';
            if (moon)   moon.style.display = '';
        } else {
            wrap.classList.add('bae-light');
            if (loader) loader.classList.add('bae-light');
            if (track)  track.classList.remove('on');
            if (sun)    sun.style.display  = '';
            if (moon)   moon.style.display = 'none';
        }
    }

    // ── LOCK MODAL ──
    function baeNavLockModal(tabName, msg) {
        var overlay = document.getElementById('bae-lock-modal-overlay');
        var title   = document.getElementById('bae-lock-modal-title');
        var msgEl   = document.getElementById('bae-lock-modal-msg');
        if (!overlay) return;
        if (title)  title.textContent  = (tabName || 'Page') + ' is locked';
        if (msgEl)  msgEl.textContent  = msg || 'Complete the previous steps to unlock this page.';
        overlay.classList.add('open');
    }
    function baeNavLockClose() {
        var overlay = document.getElementById('bae-lock-modal-overlay');
        if (overlay) overlay.classList.remove('open');
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            baeNavLockClose();
            baeTicketModalClose();
        }
    });

    /* ══ TICKET LOGIN MODAL ══ */
    var baeTicketSubmitIdleLabel = 'Continue';
    var baeTicketModalContext = 'header';
    var baeBetaRefreshTimer = null;

    function baeBetaRenderStatus(payload) {
        var banner = document.getElementById('bae-beta-banner');
        var rem = document.getElementById('bae-beta-remaining');
        var total = document.getElementById('bae-beta-total');
        var btn = document.getElementById('bae-beta-claim-btn');
        if (!banner || !rem || !btn) return;

        var p = payload || (window.BAE_BETA || {});
        var enabled = !!p.enabled;
        var remaining = Math.max(0, parseInt(p.remaining || 0, 10));
        if (total) total.textContent = String(p.total || 100);
        rem.textContent = 'Remaining: ' + remaining;

        banner.style.display = enabled ? '' : 'none';
        btn.style.display = (enabled && remaining > 0) ? '' : 'none';
        if (enabled && remaining <= 0) rem.textContent = 'Remaining: 0 (claimed out)';
        if (window.gsap && enabled && !banner.dataset.popped) {
            banner.dataset.popped = '1';
            gsap.fromTo(banner, {opacity:0, y:8, scale:0.98}, {opacity:1, y:0, scale:1, duration:0.28, ease:'power3.out'});
        }
    }

    function baeBetaFetchStatus() {
        var fd = new FormData();
        fd.append('action', 'bae_beta_campaign_status');
        fd.append('nonce', (window.BAE_SESSION && window.BAE_SESSION.claim_nonce) ? window.BAE_SESSION.claim_nonce : '');
        return fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(j){
                if (j && j.success && j.data) {
                    window.BAE_BETA = j.data;
                    baeBetaRenderStatus(j.data);
                }
            })
            .catch(function(){});
    }
    function baeTicketModalApplyContext(context) {
        var mode = context || 'header';
        baeTicketModalContext = mode;
        var titleEl = document.getElementById('bae-tkm-title');
        var descEl = document.getElementById('bae-tkm-desc');
        var hintEl = document.getElementById('bae-tkm-footer-hint-text');
        var newBtn = document.getElementById('bae-tkm-new-btn');
        var submitLbl = document.getElementById('bae-tkm-lbl');
        if (mode === 'pricing') {
            if (titleEl) titleEl.textContent = 'Bind Ticket To Continue';
            if (descEl) descEl.textContent = 'Enter your existing ticket, or generate one now to bind this workspace before upgrading your plan.';
            if (hintEl) hintEl.textContent = 'Need a ticket first?';
            if (newBtn) newBtn.textContent = 'Generate and bind ticket';
            baeTicketSubmitIdleLabel = 'Continue to Upgrade';
        } else {
            if (titleEl) titleEl.textContent = 'Access Your Ticket';
            if (descEl) descEl.textContent = 'Enter your access ticket to open your workspace, or generate one to bind this workspace for later return. No account needed.';
            if (hintEl) hintEl.textContent = 'No ticket yet?';
            if (newBtn) newBtn.textContent = 'Generate and bind ticket';
            baeTicketSubmitIdleLabel = 'Open Workspace';
        }
        if (submitLbl) submitLbl.textContent = baeTicketSubmitIdleLabel;
        baeBetaRenderStatus(window.BAE_BETA || {});
    }

    function baeTicketModalOpen(context) {
        var overlay = document.getElementById('bae-ticket-modal-overlay');
        if (!overlay) return;
        baeTicketModalApplyContext(context);
        overlay.classList.add('open');
        // Reset to step 1
        baeTicketModalBackToTicket();
        // Clear errors
        var err = document.getElementById('bae-tkm-err');
        if (err) { err.style.display = 'none'; err.textContent = ''; }
        // Focus input after transition
        setTimeout(function() {
            var inp = document.getElementById('bae-tkm-input');
            if (inp) inp.focus();
        }, 320);
        baeBetaFetchStatus();
        if (baeBetaRefreshTimer) clearInterval(baeBetaRefreshTimer);
        baeBetaRefreshTimer = setInterval(baeBetaFetchStatus, 15000);
    }

    function baeTicketModalClose() {
        var overlay = document.getElementById('bae-ticket-modal-overlay');
        if (overlay) overlay.classList.remove('open');
        if (baeBetaRefreshTimer) {
            clearInterval(baeBetaRefreshTimer);
            baeBetaRefreshTimer = null;
        }
    }

    function baeTicketModalBackToTicket() {
        document.getElementById('bae-tkm-step-ticket').style.display = '';
        document.getElementById('bae-tkm-step-otp').style.display = 'none';
    }

    function baeTicketModalSetLoading(loading) {
        var btn  = document.getElementById('bae-tkm-btn');
        var spin = document.getElementById('bae-tkm-spin');
        var arrow = document.getElementById('bae-tkm-arrow');
        var lbl  = document.getElementById('bae-tkm-lbl');
        if (!btn) return;
        btn.disabled = loading;
        if (spin)  spin.style.display = loading ? 'block' : 'none';
        if (arrow) arrow.style.display = loading ? 'none' : '';
        if (lbl)   lbl.textContent = loading ? 'Verifying...' : baeTicketSubmitIdleLabel;
    }

    function baeTicketModalShowErr(msg) {
        var err = document.getElementById('bae-tkm-err');
        if (err) { err.textContent = msg; err.style.display = 'block'; }
    }

    function baeTicketModalSubmit() {
        var inp = document.getElementById('bae-tkm-input');
        var ticket = inp ? inp.value.trim().toUpperCase() : '';
        if (!ticket) { baeTicketModalShowErr('Please enter your ticket code.'); return; }
        if (!/^(BAE|ADM)-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(ticket)) {
            baeTicketModalShowErr('Invalid format. Expected: BAE-XXXX-XXXX');
            return;
        }
        var err = document.getElementById('bae-tkm-err');
        if (err) err.style.display = 'none';
        baeTicketModalSetLoading(true);
        var fd = new FormData();
        fd.append('action', 'bae_ticket_check');
        fd.append('ticket', ticket);
        fd.append('nonce', (window.BAE_SESSION && window.BAE_SESSION.claim_nonce) ? window.BAE_SESSION.claim_nonce : '');
        fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                baeTicketModalSetLoading(false);
                if (!data.success) {
                    baeTicketModalShowErr(data.data && data.data.message ? data.data.message : 'Ticket not found. Check your code and try again.');
                    return;
                }
                var d = data.data || {};
                // Set cookie
                var exp = new Date(); exp.setFullYear(exp.getFullYear() + 1);
                document.cookie = 'bae_ticket=' + encodeURIComponent(ticket) + '; expires=' + exp.toUTCString() + '; path=/; SameSite=Lax';

                if (d.requires_code) {
                    // Show OTP step
                    document.getElementById('bae-tkm-step-ticket').style.display = 'none';
                    document.getElementById('bae-tkm-step-otp').style.display = '';
                    var hint = document.getElementById('bae-tkm-otp-hint');
                    if (hint) hint.textContent = 'A 6-digit code was sent to ' + (d.masked_email || 'your email') + '.';
                    setTimeout(function() {
                        var otpInp = document.getElementById('bae-tkm-otp');
                        if (otpInp) otpInp.focus();
                    }, 200);
                    return;
                }
                // Success — reload page
                try {
                    sessionStorage.setItem('bae_toast_flash', JSON.stringify({
                        msg: 'Ticket verified. Welcome back!',
                        type: 'success'
                    }));
                } catch (e) {}
                baeTicketModalClose();
                window.location.reload();
            })
            .catch(function() {
                baeTicketModalSetLoading(false);
                baeTicketModalShowErr('Network error. Please try again.');
            });
    }

    function baeTicketModalGenerate(mode) {
        var btn = document.getElementById('bae-tkm-new-btn');
        var betaBtn = document.getElementById('bae-beta-claim-btn');
        var err = document.getElementById('bae-tkm-err');
        var originalText = btn ? btn.textContent : '';
        var betaOriginalText = betaBtn ? betaBtn.textContent : '';
        var isBeta = mode === 'beta';

        if (err) err.style.display = 'none';
        if (btn) {
            btn.disabled = true;
            btn.textContent = isBeta ? 'Claiming...' : 'Generating...';
        }
        if (betaBtn) {
            betaBtn.disabled = true;
            betaBtn.textContent = isBeta ? 'Claiming...' : betaOriginalText;
        }

        var fd = new FormData();
        fd.append('action', 'bae_claim_ticket');
        fd.append('nonce', (window.BAE_SESSION && window.BAE_SESSION.claim_nonce) ? window.BAE_SESSION.claim_nonce : '');
        if (isBeta) fd.append('beta_claim', '1');

        fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    baeTicketModalShowErr(data.data && data.data.message ? data.data.message : 'Could not generate ticket. Please try again.');
                    return;
                }

                var d = data.data || {};
                if (d.ticket) {
                    var exp = new Date();
                    exp.setFullYear(exp.getFullYear() + 1);
                    document.cookie = 'bae_ticket=' + encodeURIComponent(d.ticket) + '; expires=' + exp.toUTCString() + '; path=/; SameSite=Lax';
                }
                if (d.beta_status) window.BAE_BETA = d.beta_status;
                try {
                    var flash = null;
                    if (isBeta) {
                        if (d.beta_claimed) flash = { msg: 'Beta Pro claimed successfully!', type: 'success' };
                        else flash = { msg: 'Ticket bound. Beta slots are currently unavailable.', type: 'info' };
                    } else {
                        flash = { msg: 'Ticket generated and bound successfully.', type: 'success' };
                    }
                    if (flash) sessionStorage.setItem('bae_toast_flash', JSON.stringify(flash));
                } catch (e) {}

                baeTicketModalClose();
                window.location.reload();
            })
            .catch(function() {
                baeTicketModalShowErr('Network error. Please try again.');
            })
            .finally(function() {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = originalText || 'Generate and bind ticket';
                }
                if (betaBtn) {
                    betaBtn.disabled = false;
                    betaBtn.textContent = betaOriginalText || 'Claim Beta Pro Ticket';
                }
            });
    }

    function baeTicketModalOtpSubmit() {
        var otp = (document.getElementById('bae-tkm-otp') || {}).value || '';
        otp = otp.trim();
        if (!otp || otp.length !== 6) {
            var oe = document.getElementById('bae-tkm-otp-err');
            if (oe) { oe.textContent = 'Please enter the 6-digit code.'; oe.style.display = 'block'; }
            return;
        }
        var inp = document.getElementById('bae-tkm-input');
        var ticket = inp ? inp.value.trim().toUpperCase() : '';
        var btn = document.getElementById('bae-tkm-otp-btn');
        var spin = document.getElementById('bae-tkm-otp-spin');
        if (btn) btn.disabled = true;
        if (spin) spin.style.display = 'block';
        var fd = new FormData();
        fd.append('action', 'bae_verify_login_otp');
        fd.append('ticket', ticket);
        fd.append('otp', otp);
        fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (btn) btn.disabled = false;
                if (spin) spin.style.display = 'none';
                if (!data.success) {
                    var oe = document.getElementById('bae-tkm-otp-err');
                    if (oe) { oe.textContent = (data.data && data.data.message) ? data.data.message : 'Invalid code. Please try again.'; oe.style.display = 'block'; }
                    return;
                }
                try {
                    sessionStorage.setItem('bae_toast_flash', JSON.stringify({
                        msg: 'Login verified successfully.',
                        type: 'success'
                    }));
                } catch (e) {}
                baeTicketModalClose();
                window.location.reload();
            })
            .catch(function() {
                if (btn) btn.disabled = false;
                if (spin) spin.style.display = 'none';
                var oe = document.getElementById('bae-tkm-otp-err');
                if (oe) { oe.textContent = 'Network error. Please try again.'; oe.style.display = 'block'; }
            });
    }

    // Auto-format ticket input in modal
    document.addEventListener('DOMContentLoaded', function() {
        var inp = document.getElementById('bae-tkm-input');
        if (!inp) return;
        inp.addEventListener('input', function() {
            var raw = this.value.replace(/[^A-Z0-9]/gi, '').toUpperCase().substring(0, 12);
            var out = raw;
            if (raw.length >= 3) {
                var p3 = raw.substring(0, 3);
                if (p3 === 'BAE' || p3 === 'ADM') {
                    var rest = raw.substring(3);
                    out = rest.length <= 4 ? p3 + '-' + rest : p3 + '-' + rest.substring(0, 4) + '-' + rest.substring(4, 8);
                }
            }
            this.value = out;
            var err = document.getElementById('bae-tkm-err');
            if (err) err.style.display = 'none';
        });
        inp.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') baeTicketModalSubmit();
        });
        var otpInp = document.getElementById('bae-tkm-otp');
        if (otpInp) {
            otpInp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') baeTicketModalOtpSubmit();
            });
        }
    });

    /* ══ HEADER LOGOUT (ticket-only) ══ */
    function baeHeaderLogout() {
        var fd = new FormData();
        fd.append('action', 'bae_ticket_logout');
        fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function() {
                // Clear client-side identity cookies and force wizard entry.
                document.cookie = 'bae_ticket=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
                document.cookie = 'bae_session=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
                window.location.href = window.location.pathname;
            })
            .catch(function() {
                document.cookie = 'bae_ticket=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
                document.cookie = 'bae_session=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
                window.location.href = window.location.pathname;
            });
    }

    /* ── SIDEBAR TOGGLE ── */
    (function() {
        var sbCollapsed = (localStorage.getItem('bae_sb_collapsed') === '1');
        function applySb(collapsed, animate) {
            var sb  = document.getElementById('bae-sidebar');
            var ic  = document.getElementById('bae-sb-icon-collapse');
            var ie  = document.getElementById('bae-sb-icon-expand');
            if (!sb) return;
            if (collapsed) {
                sb.classList.add('collapsed');
                if (ic) ic.style.display = 'none';
                if (ie) ie.style.display = '';
            } else {
                sb.classList.remove('collapsed');
                if (ic) ic.style.display = '';
                if (ie) ie.style.display = 'none';
            }
        }
        window.baeSidebarToggle = function() {
            sbCollapsed = !sbCollapsed;
            localStorage.setItem('bae_sb_collapsed', sbCollapsed ? '1' : '0');
            applySb(sbCollapsed, true);
        };
        applySb(sbCollapsed, false);
    })();

    /* Mobile bottom nav collapse */
    (function() {
        var nav = document.getElementById('bae-mobile-nav');
        var toggle = document.getElementById('bae-mobile-nav-toggle');
        var iconOpen = document.getElementById('bae-mob-nav-icon-open');
        var iconClosed = document.getElementById('bae-mob-nav-icon-closed');
        var label = document.getElementById('bae-mobile-nav-toggle-label');
        if (!nav || !toggle) return;

        var collapsed = (localStorage.getItem('bae_mobile_nav_collapsed') === '1');

        function apply() {
            nav.classList.toggle('collapsed', collapsed);
            toggle.classList.toggle('collapsed', collapsed);
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            if (label) label.textContent = collapsed ? 'Show Menu' : 'Hide Menu';
            if (iconOpen) iconOpen.style.display = collapsed ? 'none' : '';
            if (iconClosed) iconClosed.style.display = collapsed ? '' : 'none';
        }

        window.baeMobileNavToggle = function() {
            collapsed = !collapsed;
            localStorage.setItem('bae_mobile_nav_collapsed', collapsed ? '1' : '0');
            apply();
        };

        apply();
    })();

    function baeToggleTheme() {
        baeIsDark = !baeIsDark;
        localStorage.setItem('bae_theme', baeIsDark ? 'dark' : 'light');

        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:999999;pointer-events:none;background:' + (baeIsDark ? '#000000' : '#EFF3F6') + ';opacity:0;';
        document.body.appendChild(overlay);

        if (window.gsap) {
            var tl = gsap.timeline();
            tl.to(overlay, { opacity: 0.3, duration: 0.18, ease: 'power2.in' })
              .call(function() { baeApplyTheme(baeIsDark, true); })
              .to(overlay, { opacity: 0, duration: 0.35, ease: 'power2.out' })
              .call(function() { overlay.remove(); });
            gsap.fromTo('.bae-stat-card, .bae-card, .bae-asset-card', { scale: 0.995 }, { scale: 1, duration: 0.35, stagger: 0.01, ease: 'power3.out' });
        } else {
            baeApplyTheme(baeIsDark, false);
            overlay.remove();
        }
    }

    // Apply saved theme immediately on load (before DOMContentLoaded to avoid flash)
    (function() {
        var wrap = document.getElementById('bae-wrap');
        if (wrap && !baeIsDark) wrap.classList.add('bae-light');
    })();

    document.addEventListener('DOMContentLoaded', function() {
        // Sync toggle thumb position to saved theme
        baeApplyTheme(baeIsDark, false);
        try {
            var _flash = sessionStorage.getItem('bae_toast_flash');
            if (_flash) {
                sessionStorage.removeItem('bae_toast_flash');
                var parsed = JSON.parse(_flash);
                if (parsed && parsed.msg && typeof window.baeToast === 'function') {
                    window.baeToast(parsed.msg, parsed.type || 'info');
                }
            }
        } catch (e) {}
        if (typeof window.baeInitColorPairs === 'function') window.baeInitColorPairs();
        setTimeout(function() {
            window.dispatchEvent(new Event('resize'));
            if (window.ScrollTrigger && typeof window.ScrollTrigger.refresh === 'function') {
                window.ScrollTrigger.refresh();
            }
        }, 80);

        // Reveal the wrap — hide loader, fade in content
        var wrap   = document.getElementById('bae-wrap');
        var loader = document.getElementById('bae-page-loader');
        var loaderTitle = document.getElementById('bae-loader-title-en');
        var loaderStep = document.getElementById('bae-loader-meta-step');
        if (loader && loader.parentNode !== document.body) {
            document.body.appendChild(loader);
        }

        function baeSetLoaderCopy(title, step) {
            if (loaderTitle && title) loaderTitle.textContent = title;
            if (loaderStep && step) loaderStep.textContent = step;
        }

        window.baeShowPageTransition = function(title, step, href) {
            baeSetLoaderCopy(title || 'Loading your workspace', step || 'Preparing assets');
            if (loader) {
                loader.classList.remove('is-hidden');
                loader.classList.add('is-transitioning');
                loader.style.display = 'flex';
            }
            if (wrap) wrap.style.opacity = '1';
            setTimeout(function() {
                if (href) window.location.href = href;
            }, 360);
        };

        function revealWrap() {
            if (loader) {
                loader.classList.add('is-hidden');
                setTimeout(function() {
                    if (loader) loader.style.display = 'none';
                }, 460);
            }
            if (wrap) {
                wrap.style.opacity = '1';
            }
        }

        // Use document.fonts if available for proper font-load timing
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function() {
                requestAnimationFrame(revealWrap);
            });
        } else {
            // Fallback: short delay
            setTimeout(revealWrap, 120);
        }

        document.querySelectorAll('.bae-wrap a[href]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                var href = link.getAttribute('href') || '';
                if (!href || href.charAt(0) === '#') return;
                if (link.target && link.target !== '_self') return;
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
                if (/^(mailto:|tel:|javascript:)/i.test(href)) return;
                var sameOriginRelative = href.indexOf('?tab=') !== -1 || href.indexOf(window.location.pathname) === 0 || href.indexOf('?') === 0;
                if (!sameOriginRelative) return;
                e.preventDefault();
                var label = (link.textContent || 'next page').replace(/\s+/g, ' ').trim();
                window.baeShowPageTransition('Loading ' + label, 'Switching tabs', href);
            });
        });

        if (!window.gsap) return;

        // ── Register ScrollTrigger ────────────────────────────────────────
        if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);

        // ── Utility: scroll-reveal a set of elements ──────────────────────
        function baeScrollReveal(selector, vars) {
            if (!window.ScrollTrigger) return;
            document.querySelectorAll(selector).forEach(function(el, i) {
                gsap.fromTo(el,
                    { opacity: 0, y: vars.y !== undefined ? vars.y : 22, scale: vars.scale || 1 },
                    {
                        opacity: 1, y: 0, scale: 1,
                        duration: vars.duration || 0.52,
                        delay: (vars.stagger || 0.07) * (i % (vars.maxStagger || 6)),
                        ease: vars.ease || 'power3.out',
                        scrollTrigger: {
                            trigger: el,
                            start: 'top 92%',
                            toggleActions: 'play none none none',
                            once: true
                        }
                    }
                );
            });
        }

        // ── Entrance: Dashboard hero (immediate, no scroll trigger) ───────
        var dashHero = document.querySelector('.bae-dash-hero');
        if (dashHero) {
            gsap.fromTo('.bae-dash-avatar',  { opacity:0, scale:0.82 }, { opacity:1, scale:1, duration:0.55, ease:'back.out(1.6)', delay:0.05 });
            gsap.fromTo('.bae-dash-eyebrow', { opacity:0, y:-10 },      { opacity:1, y:0, duration:0.38, ease:'power3.out', delay:0.15 });
            gsap.fromTo('.bae-dash-title',   { opacity:0, y:14 },       { opacity:1, y:0, duration:0.46, ease:'power3.out', delay:0.22 });
            gsap.fromTo('.bae-dash-sub',     { opacity:0, y:10 },       { opacity:1, y:0, duration:0.38, ease:'power3.out', delay:0.30 });
            gsap.fromTo('.bae-dash-metric',  { opacity:0, y:16, scale:0.95 }, { opacity:1, y:0, scale:1, duration:0.42, stagger:0.07, ease:'power3.out', delay:0.36 });
        }

        // ── Entrance: stat cards ──────────────────────────────────────────
        var statCards = document.querySelectorAll('.bae-stat-card');
        if (statCards.length) {
            gsap.fromTo(statCards, { opacity:0, y:18, scale:0.96 }, { opacity:1, y:0, scale:1, duration:0.46, stagger:0.08, ease:'power3.out', delay:0.18 });
        }

        // ── Entrance: dashboard tool cards ───────────────────────────────
        var dashCards = document.querySelectorAll('.bae-dash-card');
        if (dashCards.length) {
            gsap.fromTo(dashCards, { opacity:0, y:24 }, { opacity:1, y:0, duration:0.48, stagger:0.07, ease:'power3.out', delay:0.28 });
        }

        // ── ScrollTrigger: bento cards ────────────────────────────────────
        baeScrollReveal('.bae-bento-card',  { y:28, duration:0.50, stagger:0.08, maxStagger:8 });

        // ── ScrollTrigger: generic bae-card ──────────────────────────────
        baeScrollReveal('.bae-card',        { y:22, duration:0.48, stagger:0.07, maxStagger:6 });

        // ── ScrollTrigger: asset cards ───────────────────────────────────
        baeScrollReveal('.bae-asset-card',  { y:20, scale:0.97, duration:0.44, stagger:0.06, maxStagger:8 });

        // ── ScrollTrigger: kit cards / sections ───────────────────────────
        baeScrollReveal('.bae-kit-card, .bae-kit-section', { y:18, duration:0.42, stagger:0.06, maxStagger:6 });

        // ── ScrollTrigger: info / upgrade boxes ───────────────────────────
        baeScrollReveal('.bae-feature-box, .bae-info-box, .bae-upgrade-banner', { y:16, duration:0.40, stagger:0.05 });

        // ── ScrollTrigger: form groups ────────────────────────────────────
        baeScrollReveal('.bae-form-group',  { y:14, duration:0.34, stagger:0.04, maxStagger:12 });

        // ── ScrollTrigger: color swatches ────────────────────────────────
        baeScrollReveal('.bae-swatch',      { y:10, scale:0.88, duration:0.36, stagger:0.05, maxStagger:6 });

        // ── ScrollTrigger: card titles / bento labels ─────────────────────
        baeScrollReveal('.bae-card-title, .bae-bento-label', { y:12, duration:0.36, stagger:0.05 });

        // ── ScrollTrigger: free plan tip ──────────────────────────────────
        baeScrollReveal('.bae-free-plan-tip', { y:12, duration:0.38 });

        // ── Hover micro-interactions ──────────────────────────────────────
        document.querySelectorAll('.bae-stat-card, .bae-asset-card, .bae-card, .bae-bento-card, .bae-dash-card, .bae-kit-card').forEach(function(el) {
            el.addEventListener('mouseenter', function() { gsap.to(el, { y:-4, scale:1.012, duration:0.22, ease:'power2.out' }); });
            el.addEventListener('mouseleave', function() { gsap.to(el, { y: 0, scale:1,     duration:0.22, ease:'power2.out' }); });
        });
        document.querySelectorAll('.bae-btn-primary, .bae-btn-danger').forEach(function(btn) {
            btn.addEventListener('mouseenter', function() { gsap.to(btn, { y:-2, duration:0.18, ease:'power2.out' }); });
            btn.addEventListener('mouseleave', function() { gsap.to(btn, { y: 0, duration:0.18, ease:'power2.out' }); });
        });
        document.querySelectorAll('.bae-swatch').forEach(function(sw) {
            sw.addEventListener('mouseenter', function() { gsap.to(sw, { scale:1.10, duration:0.18, ease:'back.out(2)' }); });
            sw.addEventListener('mouseleave', function() { gsap.to(sw, { scale:1,    duration:0.18, ease:'power2.out' }); });
        });
    });
    </script>

    <?php
    return ob_get_clean();
}

// =============================================================================
// TAB: OVERVIEW — Brand Profile Form
// CHANGED: Added Contact Info section (email, phone, website, address)
// CHANGED: Removed duplicate color picker JS init (now in main shortcode)
// =============================================================================


// ── PAGE FILES (each tab in its own file) ──────────────────────────────────
require_once BNTM_BAE_PATH . 'pages/page-overview.php';
require_once BNTM_BAE_PATH . 'pages/page-logo.php';
require_once BNTM_BAE_PATH . 'pages/page-identity.php';
require_once BNTM_BAE_PATH . 'pages/page-assets.php';
require_once BNTM_BAE_PATH . 'pages/page-kit.php';
require_once BNTM_BAE_PATH . 'pages/page-settings.php';
require_once BNTM_BAE_PATH . 'pages/page-startup.php';
require_once BNTM_BAE_PATH . 'pages/page-brand-book.php';
require_once BNTM_BAE_PATH . 'pages/page-dashboard.php';


// =============================================================================
// PUBLIC BRAND KIT SHORTCODE
// CHANGED: Added OG meta tags to head for better social sharing previews
// =============================================================================

function bntm_shortcode_bae_kit() {
    $slug = isset($_GET['slug']) ? sanitize_text_field($_GET['slug']) : '';
    if (empty($slug)) {
        return '<div style="text-align:center;padding:60px;color:var(--text-3);">Brand Kit not found.</div>';
    }

    global $wpdb;
    $profiles_table = $wpdb->prefix . 'bae_profiles';
    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$profiles_table} WHERE kit_slug = %s",
        $slug
    ), ARRAY_A);

    if (!$profile) {
        return '<div style="text-align:center;padding:60px;color:var(--text-3);">This Brand Kit is private or does not exist.</div>';
    }

    // Visibility rules:
    // - public: anyone with the link
    // - private: only the owner (ticket cookie) or WP user owner (if applicable)
    $is_public = isset($profile['kit_visibility']) && $profile['kit_visibility'] === 'public';
    if ( ! $is_public ) {
        $ticket = '';
        if ( !empty($_COOKIE['bae_ticket']) ) {
            $raw = strtoupper( sanitize_text_field( $_COOKIE['bae_ticket'] ) );
            if ( preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw) ) $ticket = $raw;
        }
        $is_owner_by_ticket = $ticket && isset($profile['ticket']) && $profile['ticket'] === $ticket;
        $is_owner_by_user   = is_user_logged_in() && !empty($profile['user_id']) && (int)$profile['user_id'] === (int)get_current_user_id();
        if ( ! $is_owner_by_ticket && ! $is_owner_by_user ) {
            return '<div style="text-align:center;padding:60px;color:var(--text-3);">This Brand Kit is private or does not exist.</div>';
        }
    }

    $profile = bae_track_kit_view($profile);

    // CHANGED: Inject OG tags into <head> for social sharing previews
    $og_title       = esc_attr($profile['business_name'] . ' — Brand Kit');
    $og_description = esc_attr($profile['tagline'] ?: 'Official brand guidelines and assets.');
    $og_url         = esc_url(get_permalink()) . '?slug=' . esc_attr($slug);

    add_action('wp_head', function() use ($og_title, $og_description, $og_url) {
        echo "<meta property=\"og:title\" content=\"{$og_title}\">\n";
        echo "<meta property=\"og:description\" content=\"{$og_description}\">\n";
        echo "<meta property=\"og:url\" content=\"{$og_url}\">\n";
        echo "<meta property=\"og:type\" content=\"website\">\n";
        echo "<meta name=\"twitter:card\" content=\"summary\">\n";
        echo "<meta name=\"twitter:title\" content=\"{$og_title}\">\n";
        echo "<meta name=\"twitter:description\" content=\"{$og_description}\">\n";
    });

    return '<div class="bae-kit-wrap">' . bae_render_kit_html($profile) . '</div>';
}

function bae_get_kit_public_url($profile) {
    $kit_slug = !empty($profile['kit_slug'])
        ? sanitize_title($profile['kit_slug'])
        : sanitize_title(($profile['business_name'] ?? 'brand-kit')) . '-' . substr(($profile['rand_id'] ?? wp_generate_password(6, false)), 0, 6);

    $kit_page = get_page_by_path('brand-kit');
    $kit_base = $kit_page ? get_permalink($kit_page) : home_url('/brand-kit/');

    return add_query_arg('slug', $kit_slug, $kit_base);
}

function bae_get_kit_qr_url($url, $size = 320) {
    $size = max(160, min(1000, (int) $size));
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&format=svg&data=' . rawurlencode($url);
}

function bae_track_kit_view($profile) {
    if (empty($profile['id'])) return $profile;

    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    $kit_slug = sanitize_title($profile['kit_slug'] ?? '');
    $cookie_name = 'bae_kit_view_' . substr(md5($kit_slug ?: (string) $profile['id']), 0, 16);
    $is_unique = empty($_COOKIE[$cookie_name]);

    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table}
             SET kit_views = kit_views + 1,
                 kit_unique_views = kit_unique_views + %d
             WHERE id = %d",
            $is_unique ? 1 : 0,
            (int) $profile['id']
        )
    );

    if ($is_unique && !headers_sent()) {
        setcookie($cookie_name, '1', time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true);
        $_COOKIE[$cookie_name] = '1';
    }

    $profile['kit_views'] = (int) ($profile['kit_views'] ?? 0) + 1;
    $profile['kit_unique_views'] = (int) ($profile['kit_unique_views'] ?? 0) + ($is_unique ? 1 : 0);

    return $profile;
}

// =============================================================================
// ASSET HTML GENERATORS
// CHANGED: Contact fields (email, phone, website, address) used throughout
// CHANGED: Colors run through bae_safe_color() before injection into HTML
// =============================================================================
// AI PROVIDER — Gemini with Groq fallback
// bae_gemini_json_request() and bae_gemini_request() are defined in ai-provider.php
// =============================================================================
require_once __DIR__ . '/ai-provider.php';



// =============================================================================
// AJAX: WIZARD — AI Color Palette Suggestions
// Fires in the background after Step 1. Returns 5-6 named palettes with
// reasoning specific to the business name + industry.
// Falls back gracefully on the client side if this fails.
// =============================================================================

function bntm_ajax_bae_wizard_palettes() {
    $name     = sanitize_text_field( $_POST['name']     ?? '' );
    $industry = sanitize_text_field( $_POST['industry'] ?? '' );
    $fallback = bae_wizard_static_palettes();

    if ( empty( $name ) ) {
        wp_send_json_error( [ 'message' => 'No business name provided.' ] );
    }

    $industry_hint = $industry ? " in the {$industry} industry" : '';

    $prompt = "You are a professional brand color consultant.

A business called \"{$name}\"{$industry_hint} needs color palette suggestions.

Generate exactly 5 distinct, meaningful color palettes tailored specifically for \"{$name}\".
Consider what the business name implies, the industry, cultural context, and what emotions the brand should evoke.

Each palette must have:
- A creative, specific name (NOT generic like 'Bold' or 'Modern' — name it after something meaningful to this brand e.g. 'Coconut White', 'Midnight Steel', 'Harvest Amber')
- primary: the dominant brand color (hex)
- secondary: a supporting/background color (hex)
- accent: a highlight/CTA color (hex)
- personality: 4-5 word brand personality description
- reason: 1 sentence explaining WHY this palette fits \"{$name}\" specifically

Return ONLY a valid JSON array. No markdown, no code fences, no explanation. Example format:
[
  {\"name\":\"Deep Ocean\",\"primary\":\"#0c4a6e\",\"secondary\":\"#082f49\",\"accent\":\"#38bdf8\",\"personality\":\"Calm, trustworthy, deep\",\"reason\":\"Ocean blues mirror the depth and reliability customers expect from a dental practice.\"}
]";

    $result = bae_gemini_json_request( $prompt );

    if ( is_array( $result ) && isset( $result['error'] ) ) {
        wp_send_json_success( [ 'palettes' => array_slice( $fallback, 0, 5 ), 'fallback' => true, 'message' => $result['error'] ] );
    }

    if ( ! is_string( $result ) ) {
        wp_send_json_success( [ 'palettes' => array_slice( $fallback, 0, 5 ), 'fallback' => true, 'message' => 'Invalid response from AI.' ] );
    }

    // Strip any stray markdown fences
    $json_str = bae_extract_json_array( $result );
    $json_str = preg_replace( '/^```json\s*/i', '', trim( $json_str ) );
    $json_str = preg_replace( '/^```\s*/i',     '', trim( $json_str ) );
    $json_str = preg_replace( '/```\s*$/',      '', trim( $json_str ) );
    $json_str = trim( $json_str );

    $palettes = json_decode( $json_str, true );

    if ( ! is_array( $palettes ) || empty( $palettes ) ) {
        wp_send_json_success( [ 'palettes' => array_slice( $fallback, 0, 5 ), 'fallback' => true, 'message' => 'Could not parse palette data.' ] );
    }

    // Sanitize each palette
    $clean = [];
    foreach ( $palettes as $p ) {
        if ( ! is_array( $p ) ) {
            continue;
        }

        $fallback_palette = $fallback[ count( $clean ) % count( $fallback ) ];
        $primary = bae_safe_color( $p['primary'] ?? '', $fallback_palette['primary'] );
        $secondary = bae_safe_color( $p['secondary'] ?? '', $fallback_palette['secondary'] );
        $accent = bae_safe_color( $p['accent'] ?? '', $fallback_palette['accent'] );
        $normalized = bae_normalize_palette_colors(
            $primary,
            $secondary,
            $accent
        );
        $clean[] = [
            'name'        => sanitize_text_field( $p['name']        ?? $fallback_palette['name'] ),
            'primary'     => $normalized['primary'],
            'secondary'   => $normalized['secondary'],
            'accent'      => $normalized['accent'],
            'personality' => sanitize_text_field( $p['personality'] ?? $fallback_palette['personality'] ),
            'reason'      => sanitize_text_field( $p['reason']      ?? $fallback_palette['reason'] ),
        ];
    }

    if ( empty( $clean ) ) {
        wp_send_json_success( [ 'palettes' => array_slice( $fallback, 0, 5 ), 'fallback' => true, 'message' => 'No valid palettes generated.' ] );
    }

    while ( count( $clean ) < 5 ) {
        $fallback_palette = $fallback[ count( $clean ) % count( $fallback ) ];
        $clean[] = $fallback_palette;
    }

    wp_send_json_success( [ 'palettes' => array_slice( $clean, 0, 5 ) ] );
}

// =============================================================================
// AJAX: WIZARD — AI Tagline Suggestions
// =============================================================================
function bntm_ajax_bae_wizard_taglines() {
    $name        = sanitize_text_field( $_POST['name']        ?? '' );
    $industry    = sanitize_text_field( $_POST['industry']    ?? '' );
    $personality = sanitize_text_field( $_POST['personality'] ?? '' );
    if ( empty($name) ) wp_send_json_error(['message' => 'No name provided.']);

    $prompt = "You are a professional brand copywriter.

Business name: \"{$name}\"
Industry: \"{$industry}\"
Brand personality: \"{$personality}\"

Write exactly 5 short, punchy taglines specifically for \"{$name}\".
Each tagline must:
- Be under 8 words
- Feel written for THIS specific business, not generic
- Match the personality/tone provided
- Be memorable and distinct from each other

Return ONLY a valid JSON array of 5 strings. No markdown, no code fences, no explanation.
Example: [\"Fresh baked with love, every day.\",\"Your neighborhood bakery since 1998.\"]";

    $result = bae_gemini_request($prompt);
    if ( is_array($result) ) wp_send_json_error(['message' => $result['error'] ?? 'AI failed.']);

    $json = trim(preg_replace(['/^```json\s*/i','/^```\s*/i','/```\s*$/'], '', $result));
    $taglines = json_decode($json, true);
    if ( !is_array($taglines) || empty($taglines) ) wp_send_json_error(['message' => 'Could not parse taglines.']);

    $clean = array_map('sanitize_text_field', array_slice($taglines, 0, 5));
    wp_send_json_success(['taglines' => $clean]);
}

// =============================================================================
// AJAX: OVERVIEW — AI Color Suggestions (same logic as wizard palettes)
// =============================================================================
function bntm_ajax_bae_suggest_colors() {
    check_ajax_referer( 'bae_save_profile', 'nonce', false );
    $name     = sanitize_text_field( $_POST['name']     ?? '' );
    $industry = sanitize_text_field( $_POST['industry'] ?? '' );
    if ( empty($name) ) wp_send_json_error(['message' => 'No name provided.']);

    // Reuse the wizard palette prompt
    $_POST['name']     = $name;
    $_POST['industry'] = $industry;
    bntm_ajax_bae_wizard_palettes(); // exits via wp_send_json
}

// =============================================================================
// AJAX: OVERVIEW — AI Tagline Suggestions
// =============================================================================
function bntm_ajax_bae_suggest_tagline() {
    check_ajax_referer( 'bae_save_profile', 'nonce', false );
    $name     = sanitize_text_field( $_POST['name']     ?? '' );
    $industry = sanitize_text_field( $_POST['industry'] ?? '' );
    if ( empty($name) ) wp_send_json_error(['message' => 'No name provided.']);

    $_POST['name']        = $name;
    $_POST['industry']    = $industry;
    $_POST['personality'] = '';
    bntm_ajax_bae_wizard_taglines(); // exits via wp_send_json
}

function bntm_ajax_bae_social_captions() {
    check_ajax_referer('bae_generate_asset', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $profile_id = intval($_POST['profile_id'] ?? 0);
    $topic = sanitize_text_field($_POST['topic'] ?? '');

    if (!$profile_id || $topic === '') {
        wp_send_json_error(['message' => 'Please enter a topic or event first.']);
    }

    $ticket_ck = bae_get_ticket_cookie();
    $profile = null;
    if ($ticket_ck) {
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT business_name, industry, personality, tagline FROM {$wpdb->prefix}bae_profiles WHERE id = %d AND ticket = %s",
            $profile_id, $ticket_ck
        ), ARRAY_A);
    }
    if (!$profile) {
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT business_name, industry, personality, tagline FROM {$wpdb->prefix}bae_profiles WHERE id = %d",
            $profile_id
        ), ARRAY_A);
    }

    if (!$profile) {
        wp_send_json_error(['message' => 'Profile not found.']);
    }

    $business_name = sanitize_text_field($profile['business_name'] ?? 'This business');
    $industry = sanitize_text_field($profile['industry'] ?? '');
    $personality = sanitize_text_field($profile['personality'] ?? '');
    $tagline = sanitize_text_field($profile['tagline'] ?? '');

    $prompt = "You are a social media copywriter for MSMEs.

Business: {$business_name}
Industry: {$industry}
Brand personality: {$personality}
Tagline: {$tagline}
Topic or event: {$topic}

Write exactly 5 short social media captions for this business about the topic above.
Each caption must use a distinct tone:
1. Professional
2. Casual
3. Promotional
4. Storytelling
5. CTA-focused

Keep each caption platform-ready, natural, and specific to the business.
Return ONLY a valid JSON array like:
[
  {\"tone\":\"Professional\",\"caption\":\"...\"},
  {\"tone\":\"Casual\",\"caption\":\"...\"}
]";

    $result = bae_gemini_request($prompt);
    if (is_array($result)) {
        wp_send_json_error(['message' => $result['error'] ?? 'AI failed.']);
    }

    $json = trim(preg_replace(['/^```json\s*/i','/^```\s*/i','/```\s*$/'], '', $result));
    $captions = json_decode($json, true);

    if (!is_array($captions) || empty($captions)) {
        wp_send_json_error(['message' => 'Could not generate captions right now.']);
    }

    $clean = [];
    foreach ($captions as $item) {
        $tone = sanitize_text_field($item['tone'] ?? '');
        $caption = sanitize_textarea_field($item['caption'] ?? '');
        if ($tone === '' || $caption === '') continue;
        $clean[] = [
            'tone' => $tone,
            'caption' => $caption,
        ];
    }

    if (empty($clean)) {
        wp_send_json_error(['message' => 'No valid captions generated.']);
    }

    wp_send_json_success(['captions' => $clean]);
}

// =============================================================================
// AJAX: Brand Tools — Font Pairing Suggestions
// =============================================================================
function bntm_ajax_bae_suggest_fonts() {
    check_ajax_referer( 'bae_save_profile', 'nonce', false );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );

    global $wpdb;
    $user_id    = get_current_user_id();
    $profile_id = intval( $_POST['profile_id'] ?? 0 );
    if ( ! $profile_id ) wp_send_json_error( [ 'message' => 'Missing profile.' ] );

    $profiles_table = $wpdb->prefix . 'bae_profiles';
    $profile = $wpdb->get_row( $wpdb->prepare(
        "SELECT business_name, industry, personality, font_heading, font_body FROM {$profiles_table} WHERE id = %d AND user_id = %d",
        $profile_id, $user_id
    ), ARRAY_A );

    if ( ! $profile ) wp_send_json_error( [ 'message' => 'Profile not found.' ] );

    $name     = sanitize_text_field( $profile['business_name'] ?? '' );
    $industry = sanitize_text_field( $profile['industry'] ?? '' );
    $persona  = sanitize_text_field( $profile['personality'] ?? '' );
    $currentH = sanitize_text_field( $profile['font_heading'] ?? 'Inter' );
    $currentB = sanitize_text_field( $profile['font_body']    ?? 'Inter' );

    $fallback = [
        [ 'heading' => 'Poppins',    'body' => 'Inter',        'reason' => 'Clean, friendly, and highly readable across brand assets.' ],
        [ 'heading' => 'Montserrat', 'body' => 'Inter',        'reason' => 'Modern geometric headings with a neutral body for clarity.' ],
        [ 'heading' => 'Playfair Display', 'body' => 'Inter',  'reason' => 'Premium editorial headings with a simple, readable body.' ],
        [ 'heading' => 'DM Serif Display', 'body' => 'Inter',  'reason' => 'Distinct, confident headings balanced by a clean body.' ],
        [ 'heading' => 'Space Grotesk', 'body' => 'Inter',     'reason' => 'Tech-forward feel with excellent legibility.' ],
    ];

    // If no AI keys are configured, return fallback immediately.
    if ( empty( bae_gemini_key_pool() ) && empty( bae_groq_key_pool() ) ) {
        // Put current pair first if it isn't already.
        array_unshift( $fallback, [ 'heading' => $currentH, 'body' => $currentB, 'reason' => 'Your current selection.' ] );
        wp_send_json_success( [ 'pairs' => array_slice( $fallback, 0, 6 ) ] );
    }

    $industry_hint = $industry ? "Industry: {$industry}\n" : '';
    $persona_hint  = $persona  ? "Target audience: {$persona}\n" : '';
    $prompt = "You are a typography expert for brand identity.\n\nBusiness: \"{$name}\"\n{$industry_hint}{$persona_hint}\nCurrent fonts: Heading=\"{$currentH}\", Body=\"{$currentB}\"\n\nReturn exactly 6 font pairings suitable for Google Fonts.\nRules:\n- Provide pairs as objects: {\"heading\":\"Font Name\",\"body\":\"Font Name\",\"reason\":\"...\"}\n- Heading should have personality; body must be very readable\n- Avoid overly decorative fonts\n- Prefer widely available fonts (Google Fonts)\n- Keep reasons to 1 sentence\n\nReturn ONLY valid JSON array. No markdown, no code fences.";

    $result = bae_gemini_json_request( $prompt );
    if ( is_array( $result ) && isset( $result['error'] ) ) {
        array_unshift( $fallback, [ 'heading' => $currentH, 'body' => $currentB, 'reason' => 'Your current selection.' ] );
        wp_send_json_success( [ 'pairs' => array_slice( $fallback, 0, 6 ) ] );
    }

    $json_str = trim( (string) $result );
    $json_str = preg_replace( '/^```json\s*/i', '', $json_str );
    $json_str = preg_replace( '/^```\s*/i',     '', $json_str );
    $json_str = preg_replace( '/```\s*$/',      '', $json_str );
    $json_str = trim( $json_str );

    $pairs = json_decode( $json_str, true );
    if ( ! is_array( $pairs ) ) {
        array_unshift( $fallback, [ 'heading' => $currentH, 'body' => $currentB, 'reason' => 'Your current selection.' ] );
        wp_send_json_success( [ 'pairs' => array_slice( $fallback, 0, 6 ) ] );
    }

    $clean = [];
    foreach ( $pairs as $p ) {
        $h = sanitize_text_field( $p['heading'] ?? '' );
        $b = sanitize_text_field( $p['body'] ?? '' );
        $r = sanitize_text_field( $p['reason'] ?? '' );
        if ( ! $h || ! $b ) continue;
        $clean[] = [ 'heading' => $h, 'body' => $b, 'reason' => $r ];
        if ( count( $clean ) >= 6 ) break;
    }
    if ( empty( $clean ) ) {
        array_unshift( $fallback, [ 'heading' => $currentH, 'body' => $currentB, 'reason' => 'Your current selection.' ] );
        wp_send_json_success( [ 'pairs' => array_slice( $fallback, 0, 6 ) ] );
    }

    // Ensure current fonts show up first.
    array_unshift( $clean, [ 'heading' => $currentH, 'body' => $currentB, 'reason' => 'Your current selection.' ] );
    wp_send_json_success( [ 'pairs' => array_slice( $clean, 0, 6 ) ] );
}

// =============================================================================
// AJAX: Brand Tools — Consistency Scan (colors + fonts)
// =============================================================================
function bntm_ajax_bae_consistency_scan() {
    check_ajax_referer( 'bae_generate_asset', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );

    global $wpdb;
    $user_id    = get_current_user_id();
    $profile_id = intval( $_POST['profile_id'] ?? 0 );
    if ( ! $profile_id ) wp_send_json_error( [ 'message' => 'Missing profile.' ] );

    $profiles_table = $wpdb->prefix . 'bae_profiles';
    $assets_table   = $wpdb->prefix . 'bae_assets';

    $profile = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, primary_color, secondary_color, accent_color, font_heading, font_body FROM {$profiles_table} WHERE id = %d AND user_id = %d",
        $profile_id, $user_id
    ), ARRAY_A );

    if ( ! $profile ) wp_send_json_error( [ 'message' => 'Profile not found.' ] );

    $allowed = [ 'business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'sitemap', 'invoice_template', 'price_list', 'flyer_template', 'thank_you_card', 'media_kit', 'poster_a3' ];

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT asset_type, asset_html FROM {$assets_table} WHERE profile_id = %d AND is_generated = 1",
        $profile_id
    ), ARRAY_A );

    $byType = [];
    foreach ( $rows as $r ) {
        $t = $r['asset_type'] ?? '';
        if ( $t ) $byType[ $t ] = (string) ( $r['asset_html'] ?? '' );
    }

    $missing = [];
    foreach ( $allowed as $t ) {
        if ( empty( $byType[ $t ] ) ) $missing[] = str_replace('_',' ', $t);
    }

    // Extract colors + fonts from HTML.
    $allHtml = implode( "\n", array_values( $byType ) );
    $allHtmlLower = strtolower( $allHtml );

    // Colors: hex + rgb/rgba
    preg_match_all( '/#[0-9a-fA-F]{3,6}\b/', $allHtml, $hexes );
    preg_match_all( '/\brgba?\(\s*[\d.]+\s*,\s*[\d.]+\s*,\s*[\d.]+(?:\s*,\s*[\d.]+)?\s*\)/i', $allHtml, $rgbs );
    $colors = array_merge( $hexes[0] ?? [], $rgbs[0] ?? [] );

    $norm = [];
    foreach ( $colors as $c ) {
        $c = trim( $c );
        if ( strpos( $c, '#' ) === 0 ) {
            $h = strtolower( $c );
            if ( strlen( $h ) === 4 ) {
                $h = '#' . $h[1] . $h[1] . $h[2] . $h[2] . $h[3] . $h[3];
            }
            $norm[] = $h;
        } else {
            $norm[] = strtolower( preg_replace( '/\s+/', '', $c ) );
        }
    }
    $norm = array_values( array_unique( $norm ) );
    sort( $norm );

    // Fonts: attempt to pull names used in font-family declarations.
    $fonts = [];
    preg_match_all( '/font-family\s*:\s*([^;}{]+)[;}{]/i', $allHtml, $fm );
    foreach ( $fm[1] ?? [] as $decl ) {
        $decl = str_replace( ['"',"'"], '', $decl );
        $parts = array_map( 'trim', explode( ',', $decl ) );
        foreach ( $parts as $p ) {
            if ( ! $p ) continue;
            if ( in_array( strtolower( $p ), [ 'sans-serif', 'serif', 'monospace', 'system-ui' ], true ) ) continue;
            $fonts[] = $p;
        }
    }
    $fonts = array_values( array_unique( $fonts ) );
    sort( $fonts );

    $pc = strtolower( bae_safe_color( $profile['primary_color'] ?? '', '#1a1a2e' ) );
    $sc = strtolower( bae_safe_color( $profile['secondary_color'] ?? '', '#16213e' ) );
    $ac = strtolower( bae_safe_color( $profile['accent_color'] ?? '', '#e94560' ) );
    $fh = sanitize_text_field( $profile['font_heading'] ?? 'Inter' );
    $fb = sanitize_text_field( $profile['font_body']    ?? 'Inter' );

    $issues = [];

    // Color presence checks
    if ( $pc && strpos( $allHtmlLower, $pc ) === false ) $issues[] = "Primary color ({$pc}) is rarely/never used in saved assets.";
    if ( $sc && strpos( $allHtmlLower, $sc ) === false ) $issues[] = "Secondary color ({$sc}) is rarely/never used in saved assets.";
    if ( $ac && strpos( $allHtmlLower, $ac ) === false ) $issues[] = "Accent color ({$ac}) is rarely/never used in saved assets.";

    if ( count( $norm ) > 10 ) $issues[] = 'Many distinct colors detected — consider simplifying to your core palette.';

    // Font checks
    $fontLower = array_map( 'strtolower', $fonts );
    if ( $fh && ! in_array( strtolower( $fh ), $fontLower, true ) ) $issues[] = "Heading font ({$fh}) not detected in saved asset HTML.";
    if ( $fb && ! in_array( strtolower( $fb ), $fontLower, true ) ) $issues[] = "Body font ({$fb}) not detected in saved asset HTML.";

    $nonBrandFonts = [];
    foreach ( $fonts as $f ) {
        $lf = strtolower( $f );
        if ( $lf === strtolower( $fh ) || $lf === strtolower( $fb ) ) continue;
        $nonBrandFonts[] = $f;
    }
    if ( ! empty( $nonBrandFonts ) ) {
        $issues[] = 'Non-brand fonts detected: ' . implode( ', ', array_slice( $nonBrandFonts, 0, 6 ) ) . ( count($nonBrandFonts) > 6 ? '…' : '' );
    }

    // Score (simple heuristic)
    $score = 100;
    $score -= min( 45, count( $issues ) * 12 );
    if ( ! empty( $missing ) ) $score -= min( 25, count( $missing ) * 4 );
    $score = max( 0, min( 100, $score ) );

    wp_send_json_success( [
        'report' => [
            'score'          => $score,
            'issues'         => $issues,
            'used_fonts'     => array_slice( $fonts, 0, 12 ),
            'used_colors'    => array_slice( $norm, 0, 14 ),
            'missing_assets' => $missing,
        ]
    ] );
}

// =============================================================================
// AJAX: Launch Toolkit — Checklist persistence (diary)
// =============================================================================
function bntm_ajax_bae_toolkit_checklist_save() {
    check_ajax_referer( 'bae_save_profile', 'nonce', false );

    // Allow ticket or session identity (not WP login required)
    $ticket  = bae_get_ticket_cookie();
    $session = !empty($_COOKIE['bae_session']) ? sanitize_text_field($_COOKIE['bae_session']) : '';
    if ( !$ticket && !$session && !is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => 'No identity found. Please refresh.' ] );
    }

    $profile_id = intval( $_POST['profile_id'] ?? 0 );
    $key        = sanitize_key( $_POST['key'] ?? '' );
    $checked    = isset($_POST['checked']) ? (int) $_POST['checked'] : 0;
    $reset      = isset($_POST['reset']) ? (int) $_POST['reset'] : 0;

    if ( ! $profile_id ) wp_send_json_error( [ 'message' => 'Missing profile.' ] );

    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';

    // Use WP user meta for logged-in users, profile column for ticket/session users
    if ( is_user_logged_in() ) {
        $user_id  = get_current_user_id();
        $meta_key = 'bae_toolkit_checklist_' . $profile_id;
        if ( $reset ) {
            update_user_meta( $user_id, $meta_key, wp_json_encode( [] ) );
            wp_send_json_success( [ 'message' => 'Checklist reset.' ] );
        }
        if ( ! $key ) wp_send_json_error( [ 'message' => 'Missing key.' ] );
        $raw   = get_user_meta( $user_id, $meta_key, true );
        $state = ( is_string($raw) && $raw ) ? (json_decode($raw, true) ?: []) : [];
        $state[$key] = $checked ? 1 : 0;
        update_user_meta( $user_id, $meta_key, wp_json_encode($state) );
    } else {
        // Store checklist in profile row as JSON in toolkit_checklist column (if exists), else silently succeed
        $wpdb->hide_errors();
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM `{$table}` LIKE 'toolkit_checklist'");
        if ( $col_exists ) {
            $where = $ticket ? ['ticket' => $ticket] : ['session_id' => $session];
            $raw   = $wpdb->get_var($wpdb->prepare("SELECT toolkit_checklist FROM {$table} WHERE id = %d", $profile_id));
            $state = ( is_string($raw) && $raw ) ? (json_decode($raw, true) ?: []) : [];
            if ($reset) $state = [];
            else $state[$key] = $checked ? 1 : 0;
            $wpdb->update($table, ['toolkit_checklist' => wp_json_encode($state)], ['id' => $profile_id]);
        }
        $wpdb->show_errors();
    }

    wp_send_json_success( [ 'saved' => true ] );
}

// =============================================================================
// AJAX: Logo Upload — saves to WordPress media library
// =============================================================================
function bntm_ajax_bae_upload_logo() {
    check_ajax_referer( 'bae_save_profile', 'nonce', false );
    $ticket  = bae_get_ticket_cookie();
    $session = !empty($_COOKIE['bae_session']) ? sanitize_text_field($_COOKIE['bae_session']) : '';
    if ( !$ticket && !$session && !is_user_logged_in() ) {
        wp_send_json_error(['message' => 'No identity found. Please refresh.']);
    }
    if ( empty($_FILES['logo_file']) ) wp_send_json_error(['message' => 'No file uploaded.']);

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $allowed = ['image/png','image/jpeg','image/jpg','image/svg+xml','image/gif','image/webp'];
    if ( !in_array($_FILES['logo_file']['type'], $allowed) ) {
        wp_send_json_error(['message' => 'Only PNG, JPG, SVG, GIF, or WEBP allowed.']);
    }

    // Max 2MB
    if ( $_FILES['logo_file']['size'] > 2 * 1024 * 1024 ) {
        wp_send_json_error(['message' => 'File must be under 2MB.']);
    }

    // Temporarily rename $_FILES key to 'file' for wp_handle_upload
    $_FILES['file'] = $_FILES['logo_file'];
    $overrides = ['test_form' => false, 'unique_filename_callback' => null];
    $upload = wp_handle_upload($_FILES['file'], $overrides);
    unset($_FILES['file']);

    if ( isset($upload['error']) ) {
        wp_send_json_error(['message' => $upload['error']]);
    }

    // Insert into media library
    $attachment = [
        'guid'           => $upload['url'],
        'post_mime_type' => $upload['type'],
        'post_title'     => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
        'post_status'    => 'inherit',
    ];
    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    if ( !is_wp_error($attach_id) ) {
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
    }

    // Save logo_url to profile
    $ticket = '';
    if ( !empty($_COOKIE['bae_ticket']) ) {
        $raw = strtoupper(sanitize_text_field($_COOKIE['bae_ticket']));
        if ( preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw) ) $ticket = $raw;
    }
    if ( $ticket ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'bae_profiles',
            ['logo_url' => esc_url_raw($upload['url'])],
            ['ticket' => $ticket]
        );
    } elseif (!empty($_COOKIE['bae_session'])) {
        $session = sanitize_text_field($_COOKIE['bae_session']);
        if (preg_match('/^[a-f0-9]{32}$/', $session)) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'bae_profiles',
                ['logo_url' => esc_url_raw($upload['url'])],
                ['session_id' => $session]
            );
        }
    }

    wp_send_json_success(['url' => $upload['url'], 'id' => $attach_id ?? 0]);
}

// =============================================================================
// CLAIM TICKET — stamps session rows with a ticket, persisting the work
// Called from Step 4 (Launch Toolkit) when user clicks "Save My Brand"
// =============================================================================
function bntm_ajax_bae_claim_ticket() {
    check_ajax_referer('bae_claim_ticket', 'nonce', false);
    $wants_beta = !empty($_POST['beta_claim']) && (string) $_POST['beta_claim'] === '1';

    // Get or generate ticket
    $ticket = '';
    if (!empty($_POST['ticket'])) {
        $raw = strtoupper(sanitize_text_field($_POST['ticket']));
        if (preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw)) $ticket = $raw;
    }
    // Auto-generate if not provided
    if (empty($ticket)) {
        do {
            $ticket = 'BAE-' . strtoupper(substr(md5(wp_rand()), 0, 4)) . '-' . strtoupper(substr(md5(wp_rand()), 0, 4));
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s LIMIT 1", $ticket
            ));
        } while ($exists);
    }

    // Validate ticket not already claimed by someone else
    global $wpdb;
    $already = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s LIMIT 1", $ticket
    ));
    if ($already) {
        // Ticket exists — this is a returning user, just set the cookie
        setcookie('bae_ticket', $ticket, time() + 365 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s LIMIT 1", $ticket
        ), ARRAY_A);
        $beta_claimed = false;
        if ($wants_beta && !empty($profile['id']) && empty($profile['beta_free_claimed'])) {
            $beta_claimed = bae_try_apply_beta_claim_to_profile((int) $profile['id']);
            $profile = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bae_profiles WHERE id = %d LIMIT 1",
                (int) $profile['id']
            ), ARRAY_A);
        }
        wp_send_json_success([
            'ticket' => $ticket,
            'returning' => true,
            'profile' => $profile,
            'beta_claimed' => $beta_claimed,
            'beta_status' => bae_beta_status_payload(),
        ]);
    }

    // Stamp all unclaimed session rows with this ticket
    $session = '';
    if (!empty($_COOKIE['bae_session'])) {
        $raw = sanitize_text_field($_COOKIE['bae_session']);
        if (preg_match('/^[a-f0-9]{32}$/', $raw)) $session = $raw;
    }
    if ($session) {
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}bae_profiles SET ticket = %s WHERE session_id = %s AND (ticket = '' OR ticket IS NULL)",
            $ticket, $session
        ));
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}bae_assets a
             INNER JOIN {$wpdb->prefix}bae_profiles p ON p.id = a.profile_id
             SET a.user_id = a.user_id
             WHERE p.session_id = %s AND p.ticket = %s",
            $session, $ticket
        ));
    }

    // Set ticket cookie (1 year)
    setcookie('bae_ticket', $ticket, time() + 365 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s LIMIT 1", $ticket
    ), ARRAY_A);

    $beta_claimed = false;
    if ($wants_beta && !empty($profile['id']) && empty($profile['beta_free_claimed'])) {
        $beta_claimed = bae_try_apply_beta_claim_to_profile((int) $profile['id']);
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bae_profiles WHERE id = %d LIMIT 1",
            (int) $profile['id']
        ), ARRAY_A);
    }

    wp_send_json_success([
        'ticket' => $ticket,
        'returning' => false,
        'profile' => $profile,
        'beta_claimed' => $beta_claimed,
        'beta_status' => bae_beta_status_payload(),
    ]);
}

function bntm_ajax_bae_custom_generate() {
    check_ajax_referer( 'bae_generate_asset', 'nonce' );

    $custom = sanitize_textarea_field( $_POST['custom_prompt'] ?? '' );
    $pid    = intval( $_POST['profile_id'] ?? 0 );

    if ( empty($custom) ) wp_send_json_error([ 'message' => 'Please describe what you want.' ]);

    global $wpdb;
    $wpdb->hide_errors();
    $profile = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bae_profiles WHERE id = %d", $pid
    ), ARRAY_A );
    $wpdb->show_errors();

    if ( ! $profile ) wp_send_json_error([ 'message' => 'Profile not found.' ]);

    $name    = $profile['business_name'] ?? 'My Business';
    $pc      = bae_safe_color( $profile['primary_color'],   '#1a1a2e' );
    $sc      = bae_safe_color( $profile['secondary_color'], '#16213e' );
    $ac      = bae_safe_color( $profile['accent_color'],    '#e94560' );
    $fh      = $profile['font_heading'] ?? 'Inter';
    $fb      = $profile['font_body']    ?? 'Inter';
    $seed    = substr( md5( $custom . microtime() ), 0, 8 );

    $prompt = "You are a professional brand designer and copywriter.

Brand: {$name} | Industry: {$profile['industry']} | Personality: {$profile['personality']}
Tagline: {$profile['tagline']} | Colors: Primary {$pc} | Secondary {$sc} | Accent {$ac}
Fonts: Heading {$fh} | Body {$fb}
Email: {$profile['email']} | Phone: {$profile['phone']} | Website: {$profile['website']}
Seed: {$seed}

User request: {$custom}

Create exactly what was requested using this brand's identity. Write all copy specifically for {$name}.

RULES: Output ONLY raw HTML with inline CSS. No markdown. No code fences. No text before or after the HTML. Make it visually polished and brand-consistent.";

    $result = bae_gemini_request( $prompt );

    // Surface the real error so user knows what's wrong
    if ( is_array($result) && isset($result['error']) ) {
        wp_send_json_error([ 'message' => $result['error'] ]);
    }

    if ( ! is_string($result) || strlen($result) < 50 ) {
        wp_send_json_error([ 'message' => 'AI generation failed. Please try again.' ]);
    }

    wp_send_json_success([ 'html' => $result ]);
}

function bntm_ajax_bae_save_custom_asset() {
    check_ajax_referer( 'bae_generate_asset', 'nonce' );

    $pid        = intval( $_POST['profile_id'] ?? 0 );
    $asset_name = sanitize_text_field( $_POST['asset_name'] ?? '' );
    $asset_html = wp_kses_post( $_POST['asset_html'] ?? '' );

    if ( ! $pid || empty($asset_name) || empty($asset_html) ) {
        wp_send_json_error([ 'message' => 'Missing required fields.' ]);
    }

    global $wpdb;

    // Read ticket from cookie for identity
    $ticket = '';
    if ( ! empty($_COOKIE['bae_ticket']) ) {
        $raw = strtoupper( sanitize_text_field( $_COOKIE['bae_ticket'] ) );
        if ( preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw) ) $ticket = $raw;
    }

    // Generate a unique asset_type slug from the name
    $slug   = 'custom_' . substr( md5( $asset_name . time() ), 0, 8 );
    $table  = $wpdb->prefix . 'bae_assets';

    $wpdb->hide_errors();
    $r = $wpdb->insert( $table, [
        'rand_id'      => bntm_rand_id(),
        'profile_id'   => $pid,
        'ticket'       => $ticket,
        'user_id'      => is_user_logged_in() ? get_current_user_id() : 0,
        'asset_type'   => $slug,
        'asset_name'   => $asset_name,
        'asset_html'   => $asset_html,
        'is_generated' => 1,
    ]);
    $wpdb->show_errors();

    if ( $r === false ) {
        wp_send_json_error([ 'message' => 'Failed to save asset.' ]);
    }

    wp_send_json_success([ 'message' => 'Asset saved!', 'asset_type' => $slug ]);
}

function bae_build_prompt( $type, $profile ) {
    $p        = $profile;
    $name     = $p['business_name']  ?? 'My Business';
    $industry = $p['industry']       ?? 'General';
    $tagline  = $p['tagline']        ?? '';
    $tone     = $p['personality']    ?? 'Professional';
    $email    = !empty($p['email'])  ? $p['email']   : 'hello@' . sanitize_title($name) . '.com';
    $phone    = !empty($p['phone'])  ? $p['phone']   : '+63 900 000 0000';
    $website  = !empty($p['website'])? $p['website'] : 'www.' . sanitize_title($name) . '.com';
    $address  = $p['address']        ?? '';
    $pc       = bae_safe_color($p['primary_color'],   '#1a1a2e');
    $sc       = bae_safe_color($p['secondary_color'], '#16213e');
    $ac       = bae_safe_color($p['accent_color'],    '#e94560');
    $fh       = $p['font_heading']   ?? 'Inter';
    $fb       = $p['font_body']      ?? 'Inter';
    $initials = bae_get_initials($name);
    $seed     = substr( md5( $name . $type . microtime() ), 0, 8 );

    $brand = "Business: {$name} | Industry: {$industry} | Tagline: {$tagline}
Personality: {$tone} | Unique seed: {$seed}
Colors — Primary: {$pc} | Secondary: {$sc} | Accent: {$ac}
Fonts — Heading: {$fh} | Body: {$fb}
Contact — Email: {$email} | Phone: {$phone} | Website: {$website}" . ($address ? " | Address: {$address}" : '') . "
Initials: {$initials}";

    $rules = "STRICT OUTPUT RULES:
1. Raw HTML with inline CSS ONLY. Zero markdown, zero code fences, zero explanatory text before or after the HTML.
2. You MUST use a unique layout — do NOT use the standard header-body-footer pattern, do NOT center the logo at the top. Be a real designer.
3. Every word of copy must be written specifically for {$name} in the {$industry} industry. Nothing generic.
4. Use the exact hex colors provided. Use the heading font for titles, body font for text.
5. Add a Google Fonts @import in a <style> tag at the top for the fonts if needed.";

    switch ($type) {

        case 'business_card':
            return "You are a senior brand designer at a top agency. Design a completely unique, print-ready HTML business card for {$name}.

{$brand}

Requirements:
- Two cards stacked: front (336x192px) and back (336x192px) with 20px gap
- Front: The primary color {$pc} is your canvas. Design something that embodies '{$tone}'. The name must be prominent but the layout should be unexpected — diagonal text, bold geometric shapes, asymmetric composition, anything but a standard card.
- Back: Contact details arranged thoughtfully. Not just a column of icons and text — make it designed.
- The {$ac} accent color should be used as a deliberate design element, not decoration.
- If the personality is 'Warm' or 'Playful', the design should feel warm or playful. If 'Minimal', strip everything unnecessary. If 'Tech', make it sharp and digital.

{$rules}";

        case 'letterhead':
            return "You are a senior brand designer. Create a distinctive, full-page A4 letterhead HTML for {$name}.

{$brand}

Requirements:
- Full A4 (794x1123px), margin:0 auto
- The header must be MEMORABLE — not a colored stripe. Consider a full bleed top section, a bold typographic treatment, or a geometric element that carries the brand.
- Include a realistic sample letter body with Lorem Ipsum dated today, addressed to 'Dear [Name],'
- Footer: contact info, subtle
- The letter should look like it came from a real, premium {$industry} brand with '{$tone}' personality

{$rules}";

        case 'email_signature':
            return "You are a senior brand designer. Create a polished, email-client-compatible HTML signature for {$name}.

{$brand}

Requirements:
- Max 500px wide, compact but well-designed
- Table-based layout for email compatibility (Gmail, Outlook)
- Include: [Your Name] | [Your Title] | {$name} | {$email} | {$phone} | {$website}
- Brand mark: a styled box using initials {$initials} — make the styling reflect '{$tone}', not just a colored square
- A thin branded separator or color accent that ties to {$pc}
- Should feel like it came from a premium {$industry} company

{$rules}";

        case 'social_kit':
            return "You are a senior social media designer. Create a complete social media kit HTML preview for {$name}.

{$brand}

Requirements:
- Three sections labeled and displayed vertically with gaps:
  1. Profile Picture (circular, 200x200px) — initials {$initials} on brand background, styled for '{$tone}'
  2. Cover Photo (820x312px display) — business name, tagline '{$tagline}', website. Bold, designed, not just text on a background.
  3. Post Template (500x500px) — a real-looking branded post for a {$industry} business. Include a sample caption and hashtag area.
- Each section has its name and dimensions labeled above it
- The three pieces should feel like a cohesive set

{$rules}";

        case 'brand_guidelines':
            return "You are a senior brand strategist. Create a one-page brand guidelines HTML document for {$name}.

{$brand}

Content — ALL written specifically for {$name}:
- Brand Mission: 2 sentences that capture what {$name} does and why it matters for {$industry}
- Color Palette: each color as a styled swatch with hex code, color name, and 1-line usage note
- Typography: heading font {$fh} with a sample sentence about {$name}, body font {$fb} with a paragraph
- Tone of Voice: 5 descriptors for a '{$tone}' {$industry} brand, plus one example of on-brand copy and one off-brand example
- Logo Usage: 3 dos and 3 don'ts with visual HTML/CSS examples
- Contact block

{$rules}";

        case 'brand_book':
            return "You are a senior brand strategist at a top agency. Create a comprehensive brand book HTML for {$name}.

{$brand}

Write a minimum of 700 words of real brand-specific content across these chapters:

Chapter 01 — About {$name}: Write the brand story. Who they are, what they do, why they exist, who they serve. Specific to {$industry} and '{$tone}' personality.
Chapter 02 — Brand Colors: Why {$pc}, {$sc}, and {$ac} were chosen for a {$industry} brand. Psychology, usage, pairings.
Chapter 03 — Typography: Why {$fh} for headings and {$fb} for body. Show the type scale with sample text at h1, h2, h3, body, caption.
Chapter 04 — Tone of Voice: Write 6 tone descriptors. Show 3 real examples of on-brand copy for {$name} (social post, tagline variant, customer greeting). Show 3 off-brand examples.
Chapter 05 — Logo & Visual Identity: Usage rules with visual CSS examples. Clear space, minimum size, background rules.

{$rules}";

        case 'sitemap':
            return "You are a senior web strategist. Create a visual site structure HTML document for {$name}.

{$brand}

Requirements:
- Recommend the RIGHT pages for a {$industry} business — not generic pages that could fit any business
- Show the hierarchy visually (not just a list) — parent pages with child pages branching from them
- For each page: name, URL slug, 1-sentence purpose, priority (High/Medium/Low)
- 8-12 pages total
- Include an XML sitemap preview at the bottom
- The page names and purposes should make a {$industry} business owner say 'yes, that's exactly what I need'

{$rules}";
    }

    return '';
}

function bae_generate_asset_html($type, $profile, $regen_prompt = '') {
    if (!empty($regen_prompt)) {
        // Use AI to regenerate based on prompt
        $current_html = bae_generate_asset_html_static($type, $profile, '');
        $asset_names = [
            'business_card' => 'business card',
            'letterhead' => 'letterhead',
            'email_signature' => 'email signature',
            'social_kit' => 'social media kit',
            'brand_guidelines' => 'brand guidelines',
            'sitemap' => 'site structure',
        ];
        $asset_name = $asset_names[$type] ?? 'asset';
        $ai_prompt = "Improve this {$asset_name} HTML based on the user's request: '{$regen_prompt}'. Return only the improved HTML, no explanations or markdown.\n\nCurrent HTML:\n{$current_html}";
        $improved_html = bae_gemini_request($ai_prompt);
        if (is_string($improved_html) && !empty(trim($improved_html))) {
            return $improved_html;
        }
    }
    return bae_generate_asset_html_static($type, $profile, $regen_prompt);
}

function bae_generate_asset_html_static($type, $profile, $regen_prompt = '') {
    $p = $profile;

    $name     = esc_html($p['business_name']);
    $tagline  = esc_html($p['tagline'] ?? '');
    $initials = bae_get_initials($name);

    // CHANGED: All colors sanitized at generation time — not just on save
    $pc = bae_safe_color($p['primary_color'],   '#1a1a2e');
    $sc = bae_safe_color($p['secondary_color'], '#16213e');
    $ac = bae_safe_color($p['accent_color'],    '#e94560');

    $fh = sanitize_text_field($p['font_heading'] ?? 'Inter');
    $fb = sanitize_text_field($p['font_body']    ?? 'Inter');

    // Apply regen prompt modifications
    $front_bg = $pc;
    if (!empty($regen_prompt) && stripos($regen_prompt, 'gradient') !== false) {
        $front_bg = "linear-gradient(135deg, {$pc}, {$sc})";
    }

    // CHANGED: Real contact info replaces placeholders
    $email   = esc_html(!empty($p['email'])   ? $p['email']   : 'hello@' . sanitize_title($p['business_name']) . '.com');
    $phone   = esc_html(!empty($p['phone'])   ? $p['phone']   : '+63 900 000 0000');
    $website = esc_html(!empty($p['website']) ? $p['website'] : 'www.' . sanitize_title($p['business_name']) . '.com');
    $address = esc_html(!empty($p['address']) ? $p['address'] : '');

    switch ($type) {

        case 'business_card':
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700&family=" . urlencode($fb) . "&display=swap');
</style>
<div style='display:flex;flex-direction:column;gap:16px;font-family:\"{$fb}\",sans-serif;'>
  <!-- Front -->
  <div style='width:336px;height:192px;background:{$front_bg};border-radius:10px;padding:24px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;'>
    <div style='position:absolute;top:-30px;right:-30px;width:120px;height:120px;background:{$ac};opacity:0.15;border-radius:50%;'></div>
    <div style='position:absolute;bottom:-40px;left:-20px;width:160px;height:160px;background:{$sc};opacity:0.2;border-radius:50%;'></div>
    <div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:0.02em;'>{$name}</div>
      <div style='font-size:10px;color:rgba(255,255,255,0.6);margin-top:4px;letter-spacing:0.12em;text-transform:uppercase;'>{$tagline}</div>
    </div>
    <div style='width:36px;height:4px;background:{$ac};border-radius:2px;'></div>
  </div>
  <!-- Back — CHANGED: Real contact info -->
  <div style='width:336px;height:192px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:24px;display:flex;flex-direction:column;justify-content:center;gap:8px;'>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:15px;font-weight:700;color:{$pc};'>{$name}</div>
    <div style='width:40px;height:2px;background:{$ac};margin:4px 0;'></div>
    <div style='font-size:11px;color:#6b7280;'>{$email}</div>
    <div style='font-size:11px;color:#6b7280;'>{$phone}</div>
    <div style='font-size:11px;color:#6b7280;'>{$website}</div>
    " . ($address ? "<div style='font-size:11px;color:#6b7280;'>{$address}</div>" : '') . "
  </div>
</div>";

        case 'letterhead':
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700&family=" . urlencode($fb) . "&display=swap');
</style>
<div style='width:595px;min-height:842px;background:#fff;font-family:\"{$fb}\",sans-serif;position:relative;border:1px solid #e5e7eb;'>
  <!-- Header -->
  <div style='background:{$pc};padding:28px 40px;display:flex;align-items:center;justify-content:space-between;'>
    <div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:22px;font-weight:700;color:#fff;'>{$name}</div>
      <div style='font-size:10px;color:rgba(255,255,255,0.65);letter-spacing:0.1em;text-transform:uppercase;margin-top:4px;'>{$tagline}</div>
    </div>
    <div style='width:6px;height:40px;background:{$ac};border-radius:3px;'></div>
  </div>
  <!-- Content area -->
  <div style='padding:48px 40px;flex:1;'>
    <div style='font-size:12px;color:#9ca3af;margin-bottom:32px;'>Date: _______________</div>
    <div style='font-size:13px;color:#374151;line-height:1.9;'>
      <p>Dear [Recipient Name],</p><br>
      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.</p><br>
      <p>Sincerely,</p><br><br>
      <p><strong>{$name}</strong></p>
    </div>
  </div>
  <!-- Footer — CHANGED: Real contact info -->
  <div style='border-top:3px solid {$pc};padding:16px 40px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;'>
    <div style='font-size:10px;color:#9ca3af;'>{$email} &nbsp;|&nbsp; {$phone}" . ($address ? " &nbsp;|&nbsp; {$address}" : '') . "</div>
    <div style='font-size:10px;color:{$ac};font-weight:600;'>{$website}</div>
  </div>
</div>";

        case 'email_signature':
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700&family=" . urlencode($fb) . "&display=swap');
</style>
<table cellpadding='0' cellspacing='0' border='0' style='font-family:\"{$fb}\",Arial,sans-serif;'>
  <tr>
    <td style='padding-right:16px;border-right:3px solid {$ac};'>
      <div style='width:52px;height:52px;background:{$pc};border-radius:10px;display:flex;align-items:center;justify-content:center;'>
        <span style='font-family:\"{$fh}\",sans-serif;font-size:18px;font-weight:700;color:#fff;'>{$initials}</span>
      </div>
    </td>
    <td style='padding-left:16px;'>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:15px;font-weight:700;color:{$pc};'>[Your Name]</div>
      <div style='font-size:12px;color:{$ac};font-weight:600;margin-top:2px;'>[Your Title] &middot; {$name}</div>
      <div style='margin-top:8px;'>
        <span style='font-size:11px;color:#6b7280;'>{$email}</span>
        <span style='font-size:11px;color:#d1d5db;'> | </span>
        <span style='font-size:11px;color:#6b7280;'>{$phone}</span>
      </div>
      <div style='font-size:11px;color:#9ca3af;margin-top:2px;'>{$website}</div>
    </td>
  </tr>
</table>";

        case 'social_kit':
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700&family=" . urlencode($fb) . "&display=swap');
</style>
<div style='display:flex;flex-direction:column;gap:16px;font-family:\"{$fb}\",sans-serif;'>
  <!-- Profile Photo Frame -->
  <div style='display:flex;align-items:center;gap:12px;'>
    <div style='width:80px;height:80px;border-radius:50%;background:{$pc};border:4px solid {$ac};display:flex;align-items:center;justify-content:center;'>
      <span style='font-family:\"{$fh}\",sans-serif;font-size:24px;font-weight:700;color:#fff;'>{$initials}</span>
    </div>
    <div style='font-size:12px;color:#6b7280;'>Profile Frame (80×80)</div>
  </div>
  <!-- Cover Photo -->
  <div style='width:480px;height:168px;background:linear-gradient(135deg,{$pc} 0%,{$sc} 100%);border-radius:10px;padding:24px;display:flex;flex-direction:column;justify-content:flex-end;position:relative;overflow:hidden;'>
    <div style='position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:{$ac};opacity:0.15;border-radius:50%;'></div>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:20px;font-weight:700;color:#fff;'>{$name}</div>
    <div style='font-size:11px;color:rgba(255,255,255,0.65);margin-top:4px;letter-spacing:0.08em;text-transform:uppercase;'>{$tagline}</div>
  </div>
  <!-- Post Template -->
  <div style='width:300px;height:300px;background:{$sc};border-radius:10px;padding:24px;display:flex;flex-direction:column;justify-content:space-between;'>
    <div style='font-size:10px;color:{$ac};font-weight:600;letter-spacing:0.1em;text-transform:uppercase;'>{$name}</div>
    <div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:18px;font-weight:700;color:#fff;line-height:1.3;'>Your post headline goes here</div>
      <div style='font-size:11px;color:rgba(255,255,255,0.65);margin-top:8px;'>Short supporting text or caption goes here.</div>
    </div>
    <div style='display:flex;align-items:center;gap:8px;'>
      <div style='width:24px;height:24px;background:{$ac};border-radius:4px;display:flex;align-items:center;justify-content:center;'>
        <span style='font-size:10px;font-weight:700;color:#fff;'>{$initials}</span>
      </div>
      <span style='font-size:11px;color:rgba(255,255,255,0.65);'>{$website}</span>
    </div>
  </div>
</div>";

        case 'brand_guidelines':
            $tone_tags = bae_derive_tone_tags($p['industry'], $p['personality'] ?? '');
            $tone_str  = implode(', ', $tone_tags);
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700&family=" . urlencode($fb) . "&display=swap');
</style>
<div style='width:595px;background:#fff;font-family:\"{$fb}\",sans-serif;border:1px solid #e5e7eb;'>
  <div style='background:{$pc};padding:32px 40px;'>
    <div style='font-size:10px;color:rgba(255,255,255,0.5);letter-spacing:0.15em;text-transform:uppercase;margin-bottom:12px;'>Brand Guidelines</div>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:28px;font-weight:700;color:#fff;'>{$name}</div>
    <div style='font-size:12px;color:rgba(255,255,255,0.65);margin-top:6px;'>{$tagline}</div>
  </div>
  <div style='padding:32px 40px;'>
    <div style='margin-bottom:24px;'>
      <div style='font-size:10px;font-weight:700;color:{$ac};letter-spacing:0.12em;text-transform:uppercase;margin-bottom:10px;'>Brand Colors</div>
      <div style='display:flex;gap:12px;'>
        <div><div style='width:48px;height:48px;border-radius:8px;background:{$pc};'></div><div style='font-size:10px;color:#9ca3af;margin-top:4px;'>Primary</div><div style='font-size:10px;font-weight:600;color:#374151;font-family:monospace;'>" . strtoupper($pc) . "</div></div>
        <div><div style='width:48px;height:48px;border-radius:8px;background:{$sc};'></div><div style='font-size:10px;color:#9ca3af;margin-top:4px;'>Secondary</div><div style='font-size:10px;font-weight:600;color:#374151;font-family:monospace;'>" . strtoupper($sc) . "</div></div>
        <div><div style='width:48px;height:48px;border-radius:8px;background:{$ac};'></div><div style='font-size:10px;color:#9ca3af;margin-top:4px;'>Accent</div><div style='font-size:10px;font-weight:600;color:#374151;font-family:monospace;'>" . strtoupper($ac) . "</div></div>
      </div>
    </div>
    <div style='margin-bottom:24px;'>
      <div style='font-size:10px;font-weight:700;color:{$ac};letter-spacing:0.12em;text-transform:uppercase;margin-bottom:10px;'>Typography</div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:18px;font-weight:700;color:{$pc};'>Heading: {$fh}</div>
      <div style='font-size:12px;color:#6b7280;margin-top:4px;'>Body: {$fb} — Regular 400, Line height 1.7</div>
    </div>
    <div style='margin-bottom:24px;'>
      <div style='font-size:10px;font-weight:700;color:{$ac};letter-spacing:0.12em;text-transform:uppercase;margin-bottom:10px;'>Tone of Voice</div>
      <div style='font-size:12px;color:#374151;'>{$tone_str}</div>
      " . (!empty($p['personality']) ? "<div style='margin-top:8px;font-size:11px;color:#9ca3af;'>Target: " . esc_html($p['personality']) . "</div>" : '') . "
    </div>
    <!-- CHANGED: Contact info in guidelines -->
    <div>
      <div style='font-size:10px;font-weight:700;color:{$ac};letter-spacing:0.12em;text-transform:uppercase;margin-bottom:10px;'>Contact</div>
      <div style='font-size:12px;color:#374151;'>{$email} &nbsp;&middot;&nbsp; {$phone}</div>
      <div style='font-size:12px;color:#374151;margin-top:4px;'>{$website}" . ($address ? " &nbsp;&middot;&nbsp; {$address}" : '') . "</div>
    </div>
  </div>
  <div style='background:#f9fafb;border-top:1px solid #e5e7eb;padding:14px 40px;display:flex;justify-content:space-between;'>
    <div style='font-size:10px;color:#9ca3af;'>Generated by Mothie</div>
    <div style='font-size:10px;color:{$pc};font-weight:600;'>{$name} &copy; " . date('Y') . "</div>
  </div>
</div>";

        case 'brand_book':
            $tone_tags   = bae_derive_tone_tags($p['industry'], $p['personality'] ?? '');
            $color_psych = bae_get_color_psychology($pc, $sc, $ac);
            $font_rationale = bae_get_font_rationale($fh, $fb);
            $industry_desc = bae_get_industry_desc($p['industry'] ?? 'Other');
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@300;400;600;700;900&family=" . urlencode($fb) . ":wght@300;400;500&display=swap');
* { box-sizing: border-box; }
</style>
<div style='width:720px;font-family:\"{$fb}\",sans-serif;background:#fff;'>

  <!-- COVER PAGE -->
  <div style='min-height:500px;background:{$pc};padding:60px 56px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;'>
    <div style='position:absolute;bottom:-60px;right:-60px;width:300px;height:300px;background:{$ac};opacity:0.12;border-radius:50%;'></div>
    <div style='position:absolute;top:-40px;left:-40px;width:200px;height:200px;background:{$sc};opacity:0.2;border-radius:50%;'></div>
    <div>
      <div style='font-size:10px;color:rgba(255,255,255,0.5);letter-spacing:0.2em;text-transform:uppercase;margin-bottom:24px;'>Brand Identity Book</div>
      <div style='width:56px;height:4px;background:{$ac};border-radius:2px;margin-bottom:32px;'></div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:52px;font-weight:900;color:#fff;line-height:1;'>{$name}</div>
      <div style='font-size:14px;color:rgba(255,255,255,0.65);margin-top:16px;line-height:1.6;max-width:400px;'>{$tagline}</div>
    </div>
    <div style='font-size:11px;color:rgba(255,255,255,0.35);'>{$website} &nbsp;&middot;&nbsp; " . date('Y') . "</div>
  </div>

  <!-- SECTION: About This Brand -->
  <div style='padding:48px 56px;border-bottom:1px solid #f3f4f6;'>
    <div style='font-size:10px;font-weight:700;color:{$ac};letter-spacing:0.2em;text-transform:uppercase;margin-bottom:8px;'>01</div>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:28px;font-weight:700;color:{$pc};margin-bottom:24px;'>About {$name}</div>
    <div style='display:grid;grid-template-columns:1fr 1fr;gap:24px;'>
      <div>
        <div style='font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px;'>Industry</div>
        <div style='font-size:15px;color:#111827;'>" . esc_html($p['industry'] ?? 'General') . "</div>
        <div style='font-size:13px;color:#6b7280;margin-top:6px;line-height:1.6;'>{$industry_desc}</div>
      </div>
      <div>
        <div style='font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px;'>Target Audience</div>
        <div style='font-size:15px;color:#111827;line-height:1.6;'>" . esc_html($p['personality'] ?? 'General consumers') . "</div>
      </div>
    </div>
    " . (!empty($tagline) ? "<div style='margin-top:24px;padding:20px 24px;border-left:4px solid {$ac};background:#fafafa;'><div style='font-size:11px;color:#9ca3af;margin-bottom:6px;'>BRAND PROMISE</div><div style='font-size:18px;font-style:italic;color:{$pc};font-family:\"{$fh}\",sans-serif;'>\"{$tagline}\"</div></div>" : '') . "
  </div>

  <!-- SECTION: Color Psychology -->
  <div style='padding:48px 56px;border-bottom:1px solid #f3f4f6;'>
    <div style='font-size:10px;font-weight:700;color:{$ac};letter-spacing:0.2em;text-transform:uppercase;margin-bottom:8px;'>02</div>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:28px;font-weight:700;color:{$pc};margin-bottom:8px;'>Brand Colors</div>
    <div style='font-size:14px;color:#6b7280;margin-bottom:32px;'>Colors are chosen intentionally. Each one communicates something about your brand.</div>

    <div style='display:flex;flex-direction:column;gap:20px;'>
      <div style='display:flex;align-items:flex-start;gap:20px;padding:20px;border:1px solid #e5e7eb;border-radius:12px;'>
        <div style='width:80px;height:80px;border-radius:10px;background:{$pc};flex-shrink:0;'></div>
        <div>
          <div style='display:flex;align-items:center;gap:12px;margin-bottom:8px;'>
            <div style='font-size:16px;font-weight:700;color:#111827;'>Primary — <span style='font-family:monospace;'>" . strtoupper($pc) . "</span></div>
          </div>
          <div style='font-size:13px;color:#374151;line-height:1.7;'>{$color_psych['primary']}</div>
        </div>
      </div>
      <div style='display:flex;align-items:flex-start;gap:20px;padding:20px;border:1px solid #e5e7eb;border-radius:12px;'>
        <div style='width:80px;height:80px;border-radius:10px;background:{$sc};flex-shrink:0;'></div>
        <div>
          <div style='display:flex;align-items:center;gap:12px;margin-bottom:8px;'>
            <div style='font-size:16px;font-weight:700;color:#111827;'>Secondary — <span style='font-family:monospace;'>" . strtoupper($sc) . "</span></div>
          </div>
          <div style='font-size:13px;color:#374151;line-height:1.7;'>{$color_psych['secondary']}</div>
        </div>
      </div>
      <div style='display:flex;align-items:flex-start;gap:20px;padding:20px;border:1px solid #e5e7eb;border-radius:12px;'>
        <div style='width:80px;height:80px;border-radius:10px;background:{$ac};flex-shrink:0;'></div>
        <div>
          <div style='display:flex;align-items:center;gap:12px;margin-bottom:8px;'>
            <div style='font-size:16px;font-weight:700;color:#111827;'>Accent — <span style='font-family:monospace;'>" . strtoupper($ac) . "</span></div>
          </div>
          <div style='font-size:13px;color:#374151;line-height:1.7;'>{$color_psych['accent']}</div>
        </div>
      </div>
    </div>

    <!-- Color usage grid -->
    <div style='margin-top:28px;'>
      <div style='font-size:12px;font-weight:600;color:#374151;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.08em;'>Color Palette at a Glance</div>
      <div style='display:flex;gap:0;border-radius:12px;overflow:hidden;height:60px;'>
        <div style='flex:3;background:{$pc};'></div>
        <div style='flex:2;background:{$sc};'></div>
        <div style='flex:1;background:{$ac};'></div>
        <div style='flex:1;background:#f9fafb;border:1px solid #e5e7eb;'></div>
        <div style='flex:1;background:#111827;'></div>
      </div>
      <div style='display:flex;gap:0;font-size:10px;color:#9ca3af;margin-top:6px;'>
        <div style='flex:3;'>Primary</div>
        <div style='flex:2;'>Secondary</div>
        <div style='flex:1;'>Accent</div>
        <div style='flex:1;'>Light</div>
        <div style='flex:1;'>Dark</div>
      </div>
    </div>
  </div>

  <!-- SECTION: Typography -->
  <div style='padding:48px 56px;border-bottom:1px solid #f3f4f6;'>
    <div style='font-size:10px;font-weight:700;color:{$ac};letter-spacing:0.2em;text-transform:uppercase;margin-bottom:8px;'>03</div>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:28px;font-weight:700;color:{$pc};margin-bottom:8px;'>Typography</div>
    <div style='font-size:14px;color:#6b7280;margin-bottom:32px;'>Your fonts shape how people feel when they read your brand.</div>

    <div style='display:grid;grid-template-columns:1fr 1fr;gap:24px;'>
      <div style='padding:24px;border:1px solid #e5e7eb;border-radius:12px;'>
        <div style='font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px;'>Heading Font</div>
        <div style='font-family:\"{$fh}\",sans-serif;font-size:32px;font-weight:700;color:{$pc};line-height:1.1;'>Aa</div>
        <div style='font-family:\"{$fh}\",sans-serif;font-size:16px;font-weight:700;color:#111827;margin-top:10px;'>{$fh}</div>
        <div style='font-size:12px;color:#6b7280;margin-top:6px;line-height:1.6;'>{$font_rationale['heading']}</div>
        <div style='margin-top:12px;font-size:11px;color:#9ca3af;'>Use for: Titles, Headlines, Section Labels</div>
        <div style='margin-top:4px;font-size:11px;color:#9ca3af;'>Weight: Bold 700</div>
      </div>
      <div style='padding:24px;border:1px solid #e5e7eb;border-radius:12px;'>
        <div style='font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px;'>Body Font</div>
        <div style='font-family:\"{$fb}\",sans-serif;font-size:32px;font-weight:400;color:{$pc};line-height:1.1;'>Aa</div>
        <div style='font-family:\"{$fb}\",sans-serif;font-size:16px;font-weight:400;color:#111827;margin-top:10px;'>{$fb}</div>
        <div style='font-size:12px;color:#6b7280;margin-top:6px;line-height:1.6;'>{$font_rationale['body']}</div>
        <div style='margin-top:12px;font-size:11px;color:#9ca3af;'>Use for: Paragraphs, Captions, Labels</div>
        <div style='margin-top:4px;font-size:11px;color:#9ca3af;'>Weight: Regular 400</div>
      </div>
    </div>

    <!-- Type scale -->
    <div style='margin-top:28px;padding:24px;background:#f9fafb;border-radius:12px;'>
      <div style='font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:16px;'>Type Scale</div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:36px;font-weight:700;color:{$pc};'>{$name}</div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:24px;font-weight:700;color:#374151;margin-top:4px;'>Section Heading</div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:18px;font-weight:600;color:#374151;margin-top:4px;'>Subsection Title</div>
      <div style='font-family:\"{$fb}\",sans-serif;font-size:15px;color:#374151;margin-top:8px;line-height:1.7;'>" . esc_html(!empty($tagline) ? $tagline : 'This is your body text. Readable, clear, and warm — perfect for describing your products and services to customers.') . "</div>
      <div style='font-family:\"{$fb}\",sans-serif;font-size:12px;color:#9ca3af;margin-top:6px;'>Caption / footnote text &nbsp;&middot;&nbsp; {$email}</div>
    </div>
  </div>

  <!-- SECTION: Tone of Voice -->
  <div style='padding:48px 56px;border-bottom:1px solid #f3f4f6;'>
    <div style='font-size:10px;font-weight:700;color:{$ac};letter-spacing:0.2em;text-transform:uppercase;margin-bottom:8px;'>04</div>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:28px;font-weight:700;color:{$pc};margin-bottom:8px;'>Tone of Voice</div>
    <div style='font-size:14px;color:#6b7280;margin-bottom:24px;'>How {$name} speaks — in marketing, signage, social media, and customer conversations.</div>
    <div style='display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;'>
      " . bae_render_tone_pills($tone_tags, $pc) . "
    </div>
    <div style='display:grid;grid-template-columns:1fr 1fr;gap:16px;'>
      <div style='padding:16px;border:1px solid #d1fae5;background:#f0fdf4;border-radius:10px;'>
        <div style='font-size:11px;font-weight:700;color:#065f46;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;'>We Sound Like</div>
        <div style='font-size:13px;color:#374151;line-height:1.7;'>" . bae_get_voice_examples($tone_tags, true) . "</div>
      </div>
      <div style='padding:16px;border:1px solid #fee2e2;background:#fff5f5;border-radius:10px;'>
        <div style='font-size:11px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;'>We Don't Sound Like</div>
        <div style='font-size:13px;color:#374151;line-height:1.7;'>" . bae_get_voice_examples($tone_tags, false) . "</div>
      </div>
    </div>
  </div>

  <!-- SECTION: Logo Usage -->
  <div style='padding:48px 56px;border-bottom:1px solid #f3f4f6;'>
    <div style='font-size:10px;font-weight:700;color:{$ac};letter-spacing:0.2em;text-transform:uppercase;margin-bottom:8px;'>05</div>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:28px;font-weight:700;color:{$pc};margin-bottom:8px;'>Logo Usage Rules</div>
    <div style='font-size:14px;color:#6b7280;margin-bottom:24px;'>Protect your brand identity by following these rules consistently.</div>
    <div style='display:grid;grid-template-columns:1fr;gap:12px;'>
      " . bae_render_logo_rules($pc, $fh) . "
    </div>
  </div>

  <!-- SECTION: Contact & Footer -->
  <div style='padding:48px 56px;background:{$pc};'>
    <div style='font-size:10px;font-weight:700;color:rgba(255,255,255,0.5);letter-spacing:0.2em;text-transform:uppercase;margin-bottom:16px;'>Contact</div>
    <div style='display:grid;grid-template-columns:1fr 1fr;gap:16px;'>
      " . ($email ? "<div style='font-size:14px;color:rgba(255,255,255,0.85);'>{$email}</div>" : '') . "
      " . ($phone ? "<div style='font-size:14px;color:rgba(255,255,255,0.85);'>{$phone}</div>" : '') . "
      " . ($website ? "<div style='font-size:14px;color:{$ac};font-weight:600;'>{$website}</div>" : '') . "
      " . ($address ? "<div style='font-size:13px;color:rgba(255,255,255,0.6);'>{$address}</div>" : '') . "
    </div>
    <div style='margin-top:32px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.15);display:flex;justify-content:space-between;font-size:11px;color:rgba(255,255,255,0.35);'>
      <span>Generated by Mothie</span>
      <span>{$name} Brand Book &copy; " . date('Y') . "</span>
    </div>
  </div>

</div>";

        case 'sitemap':
            $pages = bae_get_industry_sitemap($p['industry'] ?? 'Other', $p['business_name'] ?? 'Business');
            $biz_slug_sm = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['business_name'] ?? 'yourbusiness'));
            $domain_sm = !empty($p['website']) ? rtrim($p['website'], '/') : 'https://' . $biz_slug_sm . '.com';
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700&family=" . urlencode($fb) . "&display=swap');
* { box-sizing: border-box; }
</style>
<div style='width:720px;font-family:\"{$fb}\",sans-serif;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;'>

  <!-- Header -->
  <div style='background:{$pc};padding:28px 36px;display:flex;justify-content:space-between;align-items:center;'>
    <div>
      <div style='font-size:10px;color:rgba(255,255,255,0.5);letter-spacing:0.15em;text-transform:uppercase;margin-bottom:6px;'>Site Structure</div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:22px;font-weight:700;color:#fff;'>{$name}</div>
    </div>
    <div style='font-size:11px;color:rgba(255,255,255,0.5);text-align:right;'>
      <div>{$domain_sm}</div>
      <div style='margin-top:4px;'>Recommended Pages: " . count($pages) . "</div>
    </div>
  </div>

  <!-- Visual Sitemap -->
  <div style='padding:32px 36px;'>
    <div style='font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:20px;'>Recommended Site Structure for " . esc_html($p['industry'] ?? 'Your Industry') . "</div>

    <!-- Homepage (root) -->
    <div style='display:flex;flex-direction:column;align-items:center;'>
      <div style='background:{$pc};color:#fff;padding:12px 28px;border-radius:8px;font-size:14px;font-weight:700;font-family:\"{$fh}\",sans-serif;'>{$domain_sm}/</div>
      <div style='width:2px;height:24px;background:#d1d5db;'></div>

      <!-- Branch line -->
      <div style='position:relative;width:100%;'>
        <div style='height:2px;background:#d1d5db;margin:0 auto;width:80%;'></div>
        <div style='display:flex;justify-content:space-around;width:80%;margin:0 auto;'>
          " . bae_render_sitemap_connectors($pages) . "
        </div>
      </div>

      <!-- Pages -->
      <div style='display:flex;justify-content:space-around;width:100%;flex-wrap:wrap;gap:8px;margin-top:0;'>
        " . bae_render_sitemap_cards($pages, $pc, $sc, $ac, $domain_sm) . "
      </div>
    </div>

    <!-- Page Descriptions Table -->
    <div style='margin-top:36px;'>
      <div style='font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px;'>Page Guide</div>
      <div style='display:flex;flex-direction:column;gap:0;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;'>
        " . bae_render_sitemap_table($pages) . "
      </div>
    </div>

    <!-- XML Sitemap Preview -->
    <div style='margin-top:28px;'>
      <div style='font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px;'>XML Sitemap Preview</div>
      <div style='background:#1e1e2e;border-radius:10px;padding:20px;font-family:monospace;font-size:12px;color:#a6e3a1;line-height:1.8;overflow-x:auto;'>
        <span style='color:#89dceb;'>&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;</span><br>
        <span style='color:#89dceb;'>&lt;urlset xmlns=&quot;http://www.sitemaps.org/schemas/sitemap/0.9&quot;&gt;</span><br>
        " . bae_render_xml_sitemap($pages, $domain_sm) . "<br>
        <span style='color:#89dceb;'>&lt;/urlset&gt;</span>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div style='background:#f9fafb;border-top:1px solid #e5e7eb;padding:14px 36px;display:flex;justify-content:space-between;font-size:11px;color:#9ca3af;'>
    <span>Generated by Mothie — Site Structure Guide</span>
    <span>{$name} &copy; " . date('Y') . "</span>
  </div>

</div>";

        case 'invoice_template':
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700&family=" . urlencode($fb) . "&display=swap');
* { box-sizing: border-box; }
</style>
<div style='width:595px;min-height:842px;background:#fff;font-family:\"{$fb}\",sans-serif;border:1px solid #e5e7eb;'>
  <!-- Header -->
  <div style='background:{$pc};padding:28px 40px;display:flex;justify-content:space-between;align-items:flex-start;'>
    <div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:24px;font-weight:700;color:#fff;margin-bottom:4px;'>{$name}</div>
      <div style='font-size:11px;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:0.1em;'>{$tagline}</div>
    </div>
    <div style='text-align:right;'>
      <div style='font-size:22px;font-weight:700;color:#fff;letter-spacing:0.05em;'>INVOICE</div>
      <div style='font-size:12px;color:rgba(255,255,255,0.7);margin-top:4px;'>#INV-2025-001</div>
    </div>
  </div>
  <!-- Bill To / Invoice Info -->
  <div style='display:grid;grid-template-columns:1fr 1fr;gap:0;padding:28px 40px;border-bottom:1px solid #f3f4f6;'>
    <div>
      <div style='font-size:10px;font-weight:700;color:{$ac};text-transform:uppercase;letter-spacing:0.12em;margin-bottom:8px;'>Bill To</div>
      <div style='font-size:14px;font-weight:700;color:#111827;margin-bottom:4px;'>Client Name</div>
      <div style='font-size:12px;color:#6b7280;line-height:1.7;'>Company Name<br>Address Line 1<br>City, Province ZIP</div>
    </div>
    <div style='text-align:right;'>
      <div style='font-size:10px;font-weight:700;color:{$ac};text-transform:uppercase;letter-spacing:0.12em;margin-bottom:10px;'>Invoice Details</div>
      <div style='display:flex;flex-direction:column;gap:4px;font-size:12px;color:#374151;'>
        <div style='display:flex;justify-content:space-between;gap:24px;'><span style='color:#9ca3af;'>Issue Date</span><span>" . date('M d, Y') . "</span></div>
        <div style='display:flex;justify-content:space-between;gap:24px;'><span style='color:#9ca3af;'>Due Date</span><span>" . date('M d, Y', strtotime('+30 days')) . "</span></div>
        <div style='display:flex;justify-content:space-between;gap:24px;'><span style='color:#9ca3af;'>Invoice #</span><span>INV-2025-001</span></div>
      </div>
    </div>
  </div>
  <!-- Items Table -->
  <div style='padding:0 40px;'>
    <table style='width:100%;border-collapse:collapse;margin:24px 0;'>
      <thead>
        <tr style='background:#f9fafb;'>
          <th style='padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;border-bottom:1px solid #e5e7eb;'>Description</th>
          <th style='padding:10px 12px;text-align:center;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;border-bottom:1px solid #e5e7eb;'>Qty</th>
          <th style='padding:10px 12px;text-align:right;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;border-bottom:1px solid #e5e7eb;'>Price</th>
          <th style='padding:10px 12px;text-align:right;font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;border-bottom:1px solid #e5e7eb;'>Total</th>
        </tr>
      </thead>
      <tbody>
        <tr><td style='padding:12px;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6;'>Service / Product Name</td><td style='padding:12px;text-align:center;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6;'>1</td><td style='padding:12px;text-align:right;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6;'>₱5,000.00</td><td style='padding:12px;text-align:right;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6;'>₱5,000.00</td></tr>
        <tr><td style='padding:12px;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6;'>Additional Item</td><td style='padding:12px;text-align:center;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6;'>2</td><td style='padding:12px;text-align:right;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6;'>₱1,500.00</td><td style='padding:12px;text-align:right;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6;'>₱3,000.00</td></tr>
        <tr><td style='padding:12px;font-size:13px;color:#9ca3af;font-style:italic;' colspan='4'>Add more items as needed...</td></tr>
      </tbody>
    </table>
    <!-- Totals -->
    <div style='display:flex;justify-content:flex-end;'>
      <div style='min-width:240px;'>
        <div style='display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:13px;'><span style='color:#6b7280;'>Subtotal</span><span>₱8,000.00</span></div>
        <div style='display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:13px;'><span style='color:#6b7280;'>VAT (12%)</span><span>₱960.00</span></div>
        <div style='display:flex;justify-content:space-between;padding:12px 0;font-size:16px;font-weight:700;color:{$pc};'><span>Total Due</span><span>₱8,960.00</span></div>
      </div>
    </div>
  </div>
  <!-- Footer -->
  <div style='margin:28px 40px 0;padding:18px;background:{$pc}08;border:1px solid {$pc}22;border-radius:10px;'>
    <div style='font-size:10px;font-weight:700;color:{$ac};text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px;'>Payment Details</div>
    <div style='font-size:12px;color:#374151;line-height:1.7;'>Bank: BDO / BPI &nbsp;|&nbsp; Account: {$name} &nbsp;|&nbsp; GCash: {$phone}<br>{$email} &nbsp;·&nbsp; {$website}</div>
  </div>
  <div style='padding:16px 40px;margin-top:20px;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;font-size:11px;color:#9ca3af;'>
    <span>Thank you for your business!</span>
    <span>{$name} &copy; " . date('Y') . "</span>
  </div>
</div>";

        case 'price_list':
            $items = [
                ['cat' => 'Basic', 'items' => [
                    ['name' => 'Starter Package', 'desc' => 'Perfect for small needs', 'price' => '₱499'],
                    ['name' => 'Standard Service', 'desc' => 'Most popular choice', 'price' => '₱999'],
                ]],
                ['cat' => 'Premium', 'items' => [
                    ['name' => 'Professional Package', 'desc' => 'Full-featured solution', 'price' => '₱1,999'],
                    ['name' => 'Enterprise Plan', 'desc' => 'Custom for large teams', 'price' => 'Custom'],
                ]],
            ];
            $rows_html = '';
            foreach ($items as $cat) {
                $rows_html .= "<div style='margin-bottom:20px;'>";
                $rows_html .= "<div style='font-size:10px;font-weight:700;color:{$ac};text-transform:uppercase;letter-spacing:0.12em;margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid {$ac};'>" . esc_html($cat['cat']) . "</div>";
                foreach ($cat['items'] as $item) {
                    $rows_html .= "<div style='display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f3f4f6;gap:16px;'>"
                        . "<div><div style='font-size:14px;font-weight:600;color:#111827;'>" . esc_html($item['name']) . "</div><div style='font-size:12px;color:#6b7280;margin-top:2px;'>" . esc_html($item['desc']) . "</div></div>"
                        . "<div style='font-size:18px;font-weight:700;color:{$pc};white-space:nowrap;'>" . esc_html($item['price']) . "</div>"
                        . "</div>";
                }
                $rows_html .= "</div>";
            }
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700&family=" . urlencode($fb) . "&display=swap');
</style>
<div style='width:595px;background:#fff;font-family:\"{$fb}\",sans-serif;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;'>
  <div style='background:linear-gradient(135deg,{$pc},{$sc});padding:32px 40px;position:relative;overflow:hidden;'>
    <div style='position:absolute;top:-30px;right:-30px;width:140px;height:140px;background:{$ac};opacity:0.15;border-radius:50%;'></div>
    <div style='font-size:10px;font-weight:700;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:0.14em;margin-bottom:8px;'>Price List</div>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:26px;font-weight:700;color:#fff;margin-bottom:4px;'>{$name}</div>
    <div style='font-size:12px;color:rgba(255,255,255,0.7);'>{$tagline}</div>
  </div>
  <div style='padding:28px 40px;'>{$rows_html}</div>
  <div style='background:#f9fafb;border-top:1px solid #e5e7eb;padding:14px 40px;display:flex;justify-content:space-between;font-size:11px;color:#9ca3af;'>
    <span>All prices are subject to change without notice.</span>
    <span>{$email}</span>
  </div>
</div>";

        case 'flyer_template':
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700;900&family=" . urlencode($fb) . "&display=swap');
</style>
<div style='width:420px;min-height:595px;background:linear-gradient(160deg,{$pc} 0%,{$sc} 55%,{$pc} 100%);font-family:\"{$fb}\",sans-serif;position:relative;overflow:hidden;display:flex;flex-direction:column;'>
  <!-- Decorative circles -->
  <div style='position:absolute;top:-60px;right:-60px;width:220px;height:220px;background:{$ac};opacity:0.18;border-radius:50%;'></div>
  <div style='position:absolute;bottom:-40px;left:-40px;width:180px;height:180px;background:{$ac};opacity:0.12;border-radius:50%;'></div>
  <!-- Content -->
  <div style='position:relative;z-index:1;padding:36px 32px;flex:1;display:flex;flex-direction:column;justify-content:space-between;'>
    <div>
      <div style='display:inline-flex;padding:6px 16px;background:{$ac};border-radius:999px;font-size:11px;font-weight:700;color:#fff;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:20px;'>Special Offer</div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:38px;font-weight:900;color:#fff;line-height:1.1;margin-bottom:12px;'>{$name}</div>
      <div style='font-size:16px;color:rgba(255,255,255,0.8);margin-bottom:24px;line-height:1.6;'>{$tagline}</div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:48px;font-weight:900;color:{$ac};letter-spacing:-1px;'>20% OFF</div>
      <div style='font-size:13px;color:rgba(255,255,255,0.65);margin-top:6px;'>Limited time offer · Valid this month only</div>
    </div>
    <div style='margin-top:32px;'>
      <div style='border-top:1px solid rgba(255,255,255,0.2);padding-top:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;'>
        <div>
          <div style='font-family:\"{$fh}\",sans-serif;font-size:15px;font-weight:700;color:#fff;'>{$name}</div>
          <div style='font-size:11px;color:rgba(255,255,255,0.6);margin-top:2px;'>{$email}</div>
          " . ($phone ? "<div style='font-size:11px;color:rgba(255,255,255,0.6);'>{$phone}</div>" : '') . "
        </div>
        " . ($website ? "<div style='background:{$ac};color:#fff;padding:10px 20px;border-radius:8px;font-size:12px;font-weight:700;'>{$website}</div>" : '') . "
      </div>
    </div>
  </div>
</div>";

        case 'thank_you_card':
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700&family=" . urlencode($fb) . "&display=swap');
</style>
<div style='display:flex;flex-direction:column;gap:16px;font-family:\"{$fb}\",sans-serif;'>
  <!-- Front -->
  <div style='width:400px;height:240px;background:linear-gradient(135deg,{$pc},{$sc});border-radius:14px;padding:32px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;'>
    <div style='position:absolute;bottom:-40px;right:-40px;width:160px;height:160px;background:{$ac};opacity:0.15;border-radius:50%;'></div>
    <div style='position:absolute;top:-20px;left:-20px;width:100px;height:100px;background:rgba(255,255,255,0.06);border-radius:50%;'></div>
    <div style='position:relative;z-index:1;'>
      <div style='font-size:12px;color:rgba(255,255,255,0.6);letter-spacing:0.14em;text-transform:uppercase;margin-bottom:12px;'>A note from</div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:22px;font-weight:700;color:#fff;'>{$name}</div>
    </div>
    <div style='width:36px;height:3px;background:{$ac};border-radius:2px;'></div>
  </div>
  <!-- Inside -->
  <div style='width:400px;min-height:240px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:32px;'>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:28px;font-style:italic;font-weight:700;color:{$pc};margin-bottom:16px;'>Thank You!</div>
    <div style='font-size:14px;color:#374151;line-height:1.9;margin-bottom:20px;'>Dear valued customer,<br><br>We truly appreciate your support and trust in <strong>{$name}</strong>. Your satisfaction is our greatest reward, and we look forward to serving you again soon.</div>
    <div style='border-top:1px solid #f3f4f6;padding-top:16px;display:flex;align-items:center;gap:12px;'>
      <div style='width:38px;height:38px;background:{$pc};border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;'>
        <span style='font-family:\"{$fh}\",sans-serif;font-size:14px;font-weight:700;color:#fff;'>{$initials}</span>
      </div>
      <div>
        <div style='font-size:13px;font-weight:700;color:{$pc};'>{$name}</div>
        <div style='font-size:11px;color:#9ca3af;'>{$email}</div>
      </div>
    </div>
  </div>
</div>";

        case 'media_kit':
            $tone_tags_mk = bae_derive_tone_tags($p['industry'] ?? '', $p['personality'] ?? '');
            $tone_str_mk  = implode(', ', array_slice($tone_tags_mk, 0, 4));
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700&family=" . urlencode($fb) . "&display=swap');
* { box-sizing: border-box; }
</style>
<div style='width:720px;font-family:\"{$fb}\",sans-serif;background:#fff;'>
  <!-- Cover -->
  <div style='background:linear-gradient(135deg,{$pc} 0%,{$sc} 100%);padding:48px 52px;position:relative;overflow:hidden;'>
    <div style='position:absolute;top:-50px;right:-50px;width:250px;height:250px;background:{$ac};opacity:0.12;border-radius:50%;'></div>
    <div style='position:absolute;bottom:-60px;left:-30px;width:200px;height:200px;background:rgba(255,255,255,0.06);border-radius:50%;'></div>
    <div style='position:relative;z-index:1;'>
      <div style='font-size:10px;color:rgba(255,255,255,0.55);letter-spacing:0.2em;text-transform:uppercase;margin-bottom:16px;'>Media Kit · " . date('Y') . "</div>
      <div style='font-family:\"{$fh}\",sans-serif;font-size:36px;font-weight:700;color:#fff;margin-bottom:8px;'>{$name}</div>
      <div style='font-size:14px;color:rgba(255,255,255,0.75);max-width:480px;line-height:1.7;'>{$tagline}</div>
    </div>
  </div>
  <!-- About -->
  <div style='padding:36px 52px;border-bottom:1px solid #f3f4f6;'>
    <div style='font-size:10px;font-weight:700;color:{$ac};text-transform:uppercase;letter-spacing:0.14em;margin-bottom:10px;'>About {$name}</div>
    <div style='display:grid;grid-template-columns:1fr 1fr;gap:20px;'>
      <div style='font-size:13px;color:#374151;line-height:1.8;'><strong style='color:{$pc};'>{$name}</strong> is a {$p['industry']} brand committed to quality, trust, and meaningful impact. We serve our community through products and services that reflect our values.<br><br><em>Tone: {$tone_str_mk}</em></div>
      <div style='display:flex;flex-direction:column;gap:10px;'>
        <div style='padding:12px;background:#f9fafb;border-radius:10px;'><div style='font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.08em;'>Industry</div><div style='font-size:14px;font-weight:600;color:#111827;margin-top:3px;'>" . esc_html($p['industry'] ?? 'General') . "</div></div>
        <div style='padding:12px;background:#f9fafb;border-radius:10px;'><div style='font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.08em;'>Founded</div><div style='font-size:14px;font-weight:600;color:#111827;margin-top:3px;'>" . date('Y') . "</div></div>
      </div>
    </div>
  </div>
  <!-- Brand Colors -->
  <div style='padding:28px 52px;border-bottom:1px solid #f3f4f6;'>
    <div style='font-size:10px;font-weight:700;color:{$ac};text-transform:uppercase;letter-spacing:0.14em;margin-bottom:12px;'>Brand Colors</div>
    <div style='display:flex;gap:14px;'>
      " . implode('', array_map(function($lbl, $c) {
            return "<div><div style='width:60px;height:60px;border-radius:12px;background:{$c};margin-bottom:6px;'></div><div style='font-size:11px;color:#9ca3af;'>{$lbl}</div><div style='font-family:monospace;font-size:11px;font-weight:600;color:#374151;'>" . strtoupper($c) . "</div></div>";
        }, ['Primary','Secondary','Accent'], [$pc,$sc,$ac])) . "
    </div>
  </div>
  <!-- Contact -->
  <div style='padding:28px 52px;background:#f9fafb;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;'>
    <div>
      <div style='font-size:10px;font-weight:700;color:{$ac};text-transform:uppercase;letter-spacing:0.12em;margin-bottom:6px;'>Press Contact</div>
      " . ($email ? "<div style='font-size:13px;color:#374151;margin-bottom:3px;'>{$email}</div>" : '') . "
      " . ($phone ? "<div style='font-size:13px;color:#374151;'>{$phone}</div>" : '') . "
    </div>
    " . ($website ? "<div style='font-size:14px;font-weight:700;color:{$pc};'>{$website}</div>" : '') . "
  </div>
</div>";

        case 'poster_a3':
            return "
<style>
@import url('https://fonts.googleapis.com/css2?family=" . urlencode($fh) . ":wght@400;700;900&family=" . urlencode($fb) . "&display=swap');
</style>
<div style='width:420px;height:594px;background:{$pc};font-family:\"{$fb}\",sans-serif;position:relative;overflow:hidden;display:flex;flex-direction:column;'>
  <!-- Background decoration -->
  <div style='position:absolute;top:-80px;right:-80px;width:300px;height:300px;background:{$ac};opacity:0.14;border-radius:50%;'></div>
  <div style='position:absolute;bottom:-60px;left:-60px;width:240px;height:240px;background:{$sc};opacity:0.4;border-radius:50%;'></div>
  <div style='position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:500px;height:500px;background:rgba(255,255,255,0.03);border-radius:50%;'></div>
  <!-- Top band -->
  <div style='position:relative;z-index:1;padding:28px 32px 0;'>
    <div style='display:flex;align-items:center;justify-content:space-between;'>
      <div style='font-size:10px;letter-spacing:0.2em;color:rgba(255,255,255,0.45);text-transform:uppercase;'>" . date('Y') . "</div>
      <div style='width:28px;height:3px;background:{$ac};border-radius:2px;'></div>
    </div>
  </div>
  <!-- Hero Text -->
  <div style='position:relative;z-index:1;flex:1;display:flex;flex-direction:column;justify-content:center;padding:32px;'>
    <div style='font-size:11px;font-weight:700;color:{$ac};text-transform:uppercase;letter-spacing:0.18em;margin-bottom:14px;'>Presenting</div>
    <div style='font-family:\"{$fh}\",sans-serif;font-size:48px;font-weight:900;color:#fff;line-height:1.0;margin-bottom:18px;'>" . wordwrap($name, 12, "\n", true) . "</div>
    <div style='width:48px;height:4px;background:{$ac};border-radius:2px;margin-bottom:18px;'></div>
    " . ($tagline ? "<div style='font-size:15px;color:rgba(255,255,255,0.75);line-height:1.7;max-width:300px;'>{$tagline}</div>" : '') . "
  </div>
  <!-- Bottom info bar -->
  <div style='position:relative;z-index:1;padding:20px 32px;background:rgba(0,0,0,0.25);backdrop-filter:blur(10px);'>
    <div style='display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;'>
      <div>
        <div style='font-family:\"{$fh}\",sans-serif;font-size:14px;font-weight:700;color:#fff;'>{$name}</div>
        " . ($email ? "<div style='font-size:11px;color:rgba(255,255,255,0.55);margin-top:2px;'>{$email}</div>" : '') . "
      </div>
      " . ($website ? "<div style='font-size:11px;font-weight:700;color:{$ac};'>{$website}</div>" : '') . "
    </div>
  </div>
</div>";

        default:
            return '<div>Unknown asset type.</div>';
    }
}

// =============================================================================
// BRAND KIT RENDER
// CHANGED: Colors validated via bae_safe_color() before rendering
// =============================================================================

function bae_render_kit_html($p) {
    $name     = esc_html($p['business_name']);
    $tagline  = esc_html($p['tagline'] ?? '');
    $initials = bae_get_initials($name);
    $tone_tags = bae_derive_tone_tags($p['industry'], $p['personality'] ?? '');

    // CHANGED: Validate colors at render time
    $pc = bae_safe_color($p['primary_color'],   '#1a1a2e');
    $sc = bae_safe_color($p['secondary_color'], '#16213e');
    $ac = bae_safe_color($p['accent_color'],    '#e94560');
    $fh = sanitize_text_field($p['font_heading'] ?? 'Inter');
    $fb = sanitize_text_field($p['font_body']    ?? 'Inter');

    // Contact info
    $email   = esc_html($p['email']   ?? '');
    $phone   = esc_html($p['phone']   ?? '');
    $website = esc_html($p['website'] ?? '');
    $address = esc_html($p['address'] ?? '');
    $kit_url = bae_get_kit_public_url($p);
    $kit_qr_url = bae_get_kit_qr_url($kit_url, 300);

    ob_start();
    ?>
    <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($fh); ?>:wght@400;700&family=<?php echo urlencode($fb); ?>&display=swap" rel="stylesheet">
    <div style="font-family:'<?php echo esc_attr($fb); ?>',sans-serif;background:#fff;">

        <!-- Kit Header -->
        <div style="background:<?php echo $pc; ?>;padding:48px 40px;text-align:center;">
            <div style="display:inline-flex;align-items:center;gap:14px;">
                <div style="width:60px;height:60px;background:rgba(255,255,255,0.15);border-radius:14px;display:flex;align-items:center;justify-content:center;">
                    <span style="font-family:'<?php echo esc_attr($fh); ?>',sans-serif;font-size:22px;font-weight:700;color:#fff;"><?php echo $initials; ?></span>
                </div>
                <div style="text-align:left;">
                    <div style="font-family:'<?php echo esc_attr($fh); ?>',sans-serif;font-size:28px;font-weight:700;color:#fff;"><?php echo $name; ?></div>
                    <?php if ($tagline): ?>
                    <div style="font-size:12px;color:rgba(255,255,255,0.65);letter-spacing:0.1em;text-transform:uppercase;margin-top:4px;"><?php echo $tagline; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top:16px;font-size:12px;color:rgba(255,255,255,0.4);letter-spacing:0.1em;text-transform:uppercase;">Official Brand Kit</div>
        </div>

        <div style="padding:40px;">
            <!-- Colors -->
            <div style="margin-bottom:36px;">
                <div style="font-size:11px;font-weight:700;color:<?php echo $ac; ?>;letter-spacing:0.15em;text-transform:uppercase;margin-bottom:16px;">Brand Colors</div>
                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                    <?php
                    $colors = ['Primary' => $pc, 'Secondary' => $sc, 'Accent' => $ac];
                    foreach ($colors as $lbl => $hex):
                    ?>
                    <div style="display:flex;flex-direction:column;gap:8px;align-items:center;">
                        <div style="width:80px;height:80px;border-radius:12px;background:<?php echo $hex; ?>;border:1px solid rgba(0,0,0,0.06);"></div>
                        <div style="font-size:12px;color:#9ca3af;"><?php echo $lbl; ?></div>
                        <div style="font-size:12px;font-weight:600;color:#374151;font-family:'Courier New',monospace;"><?php echo strtoupper($hex); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Typography -->
            <div style="margin-bottom:36px;">
                <div style="font-size:11px;font-weight:700;color:<?php echo $ac; ?>;letter-spacing:0.15em;text-transform:uppercase;margin-bottom:16px;">Typography</div>
                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:24px;">
                    <div style="font-family:'<?php echo esc_attr($fh); ?>',sans-serif;font-size:28px;font-weight:700;color:<?php echo $pc; ?>;"><?php echo $name; ?></div>
                    <div style="font-size:13px;color:#6b7280;margin-top:8px;line-height:1.7;">
                        <?php echo $tagline ?: 'Sample body text. The quick brown fox jumps over the lazy dog.'; ?>
                    </div>
                    <div style="margin-top:16px;display:flex;gap:20px;font-size:12px;color:#9ca3af;border-top:1px solid #f3f4f6;padding-top:12px;">
                        <span>Heading: <strong style="color:#374151;"><?php echo esc_html($fh); ?></strong></span>
                        <span>Body: <strong style="color:#374151;"><?php echo esc_html($fb); ?></strong></span>
                    </div>
                </div>
            </div>

            <!-- Tone -->
            <div style="margin-bottom:36px;">
                <div style="font-size:11px;font-weight:700;color:<?php echo $ac; ?>;letter-spacing:0.15em;text-transform:uppercase;margin-bottom:16px;">Tone of Voice</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach ($tone_tags as $tag): ?>
                        <span style="padding:6px 14px;background:#f3f4f6;border-radius:999px;font-size:13px;color:#374151;font-weight:500;"><?php echo esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Contact — CHANGED: Real contact info shown in public kit -->
            <?php if ($email || $phone || $website || $address): ?>
            <div style="margin-bottom:36px;">
                <div style="font-size:11px;font-weight:700;color:<?php echo $ac; ?>;letter-spacing:0.15em;text-transform:uppercase;margin-bottom:16px;">Contact</div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <?php if ($email):  ?><div style="font-size:13px;color:#374151;"><?php echo $email; ?></div><?php endif; ?>
                    <?php if ($phone):  ?><div style="font-size:13px;color:#374151;"><?php echo $phone; ?></div><?php endif; ?>
                    <?php if ($website):?><div style="font-size:13px;color:#374151;"><?php echo $website; ?></div><?php endif; ?>
                    <?php if ($address):?><div style="font-size:13px;color:#374151;"><?php echo $address; ?></div><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="margin-bottom:36px;">
                <div style="font-size:11px;font-weight:700;color:<?php echo $ac; ?>;letter-spacing:0.15em;text-transform:uppercase;margin-bottom:16px;">Quick Access</div>
                <div style="display:grid;grid-template-columns:minmax(170px,210px) 1fr;gap:18px;align-items:center;border:1px solid #e5e7eb;border-radius:14px;padding:18px;">
                    <div style="display:flex;justify-content:center;">
                        <div style="background:#fff;padding:12px;border-radius:14px;box-shadow:0 8px 22px rgba(15,23,42,.08);">
                            <img src="<?php echo esc_url($kit_qr_url); ?>" alt="QR code for this Brand Kit" style="display:block;width:170px;height:170px;border-radius:8px;">
                        </div>
                    </div>
                    <div>
                        <div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:8px;">Scan to open this Brand Kit</div>
                        <div style="font-size:13px;color:#6b7280;line-height:1.7;margin-bottom:12px;">Share this QR in proposals, print materials, booths, or packaging so collaborators can open the latest version instantly.</div>
                        <div style="font-size:12px;color:#374151;word-break:break-all;font-family:'Courier New',monospace;background:#f9fafb;border:1px solid #f3f4f6;border-radius:10px;padding:10px 12px;"><?php echo esc_html($kit_url); ?></div>
                        <div style="margin-top:12px;">
                            <a href="<?php echo esc_url($kit_qr_url); ?>" download="<?php echo esc_attr(sanitize_title($p['business_name'] ?: 'brand-kit')); ?>-kit-qr.svg" style="display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;background:<?php echo $pc; ?>;color:#fff;text-decoration:none;font-size:12px;font-weight:700;">Download QR</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Rules -->
            <div>
                <div style="font-size:11px;font-weight:700;color:<?php echo $ac; ?>;letter-spacing:0.15em;text-transform:uppercase;margin-bottom:16px;">Usage Rules</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php
                    $rules = [
                        'Always use the official brand colors — no substitutions.',
                        'Use the heading font for all titles and the body font for all other text.',
                        'Maintain clear space around the logo equal to the height of the logo icon.',
                        'Do not stretch, rotate, or modify the logo in any way.',
                        'Ensure sufficient contrast when placing the logo on backgrounds.',
                    ];
                    foreach ($rules as $i => $rule):
                    ?>
                    <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:#374151;">
                        <span style="background:<?php echo $pc; ?>;color:#fff;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;margin-top:1px;"><?php echo $i+1; ?></span>
                        <?php echo $rule; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 40px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div style="font-size:11px;color:#9ca3af;">Generated by Mothie</div>
            <div style="font-size:11px;color:<?php echo $pc; ?>;font-weight:600;"><?php echo $name; ?> &copy; <?php echo date('Y'); ?></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// =============================================================================
// AJAX HANDLERS
// CHANGED: bae_save_profile now saves contact fields (email, phone, website, address)
// =============================================================================

function bntm_ajax_bae_save_profile() {
    check_ajax_referer('bae_save_profile', 'nonce', false);

    // Identity: ticket (claimed user) or session_id (unclaimed/new user)
    $ticket = '';
    if (!empty($_COOKIE['bae_ticket'])) {
        $raw = strtoupper(sanitize_text_field($_COOKIE['bae_ticket']));
        if (preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw)) $ticket = $raw;
    }
    $session = '';
    if (!empty($_COOKIE['bae_session'])) {
        $raw = sanitize_text_field($_COOKIE['bae_session']);
        if (preg_match('/^[a-f0-9]{32}$/', $raw)) $session = $raw;
    }
    if (empty($ticket) && empty($session)) {
        wp_send_json_error(['message' => 'No identity found. Please refresh.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';

    $data = [
        'business_name'   => sanitize_text_field($_POST['business_name'] ?? ''),
        'industry'        => sanitize_text_field($_POST['industry']       ?? ''),
        'tagline'         => sanitize_text_field($_POST['tagline']        ?? ''),
        'personality'     => sanitize_text_field($_POST['personality']    ?? ''),
        'email'           => sanitize_email($_POST['email']               ?? ''),
        'phone'           => sanitize_text_field($_POST['phone']          ?? ''),
        'website'         => esc_url_raw($_POST['website']               ?? ''),
        'address'         => sanitize_text_field($_POST['address']        ?? ''),
        'primary_color'   => bae_safe_color($_POST['primary_color']   ?? '', '#1a1a2e'),
        'secondary_color' => bae_safe_color($_POST['secondary_color'] ?? '', '#16213e'),
        'accent_color'    => bae_safe_color($_POST['accent_color']    ?? '', '#e94560'),
        'font_heading'    => sanitize_text_field($_POST['font_heading']   ?? 'Inter'),
        'font_body'       => sanitize_text_field($_POST['font_body']      ?? 'Inter'),
        'logo_style'      => sanitize_text_field($_POST['logo_style']     ?? 'wordmark'),
        'logo_icon'       => sanitize_text_field($_POST['logo_icon']      ?? ''),
        'logo_icon_scale' => max(70, min(160, intval($_POST['logo_icon_scale'] ?? 100))),
        'logo_spacing'    => max(6, min(28, intval($_POST['logo_spacing'] ?? 14))),
        'logo_position'   => sanitize_text_field($_POST['logo_position']  ?? 'auto'),
        'logo_text_case'  => sanitize_text_field($_POST['logo_text_case'] ?? 'default'),
        'logo_url'        => esc_url_raw($_POST['logo_url']               ?? ''),
        'ticket'          => $ticket,
        'session_id'      => $session,
    ];

    if (empty($data['business_name'])) {
        wp_send_json_error(['message' => 'Business name is required.']);
    }

    $wpdb->hide_errors();
    // Look up existing row by ticket first, then session
    $existing = null;
    if ($ticket) {
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table} WHERE ticket = %s ORDER BY updated_at DESC, id DESC LIMIT 1", $ticket));
    }
    if (!$existing && $session) {
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table} WHERE session_id = %s AND (ticket = '' OR ticket IS NULL) ORDER BY updated_at DESC, id DESC LIMIT 1", $session));
    }
    $wpdb->show_errors();

    if ($existing) {
        $data['kit_slug'] = bae_make_unique_kit_slug($data['business_name'], (int) $existing->id);
        $wpdb->update($table, $data, ['id' => $existing->id]);
        $saved_profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $existing->id), ARRAY_A);
        $saved_plan = bae_get_user_plan(is_user_logged_in() ? get_current_user_id() : 0, $saved_profile);
        wp_send_json_success([
            'message' => 'Brand profile updated successfully!',
            'profile_id' => (int) $existing->id,
            'plan' => $saved_plan,
            'starter_assets' => bae_get_onboarding_auto_asset_types($saved_plan),
        ]);
    } else {
        $data['rand_id']  = bntm_rand_id();
        $data['user_id']  = is_user_logged_in() ? get_current_user_id() : 0;
        $data['kit_slug'] = bae_make_unique_kit_slug($data['business_name']);
        $r = $wpdb->insert($table, $data);
        if ($r === false) wp_send_json_error(['message' => 'Failed to save profile. Please try again.']);
        $profile_id = (int) $wpdb->insert_id;
        $saved_profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $profile_id), ARRAY_A);
        $saved_plan = bae_get_user_plan(is_user_logged_in() ? get_current_user_id() : 0, $saved_profile);
        wp_send_json_success([
            'message' => 'Brand profile created successfully!',
            'profile_id' => $profile_id,
            'plan' => $saved_plan,
            'starter_assets' => bae_get_onboarding_auto_asset_types($saved_plan),
        ]);
    }
}


function bntm_ajax_bae_generate_asset() {
    check_ajax_referer('bae_generate_asset', 'nonce');

    // Allow ticket-based or session-based users (user_id = 0) — auth is by cookie identity
    $ticket_cookie  = bae_get_ticket_cookie();
    $session_cookie = !empty($_COOKIE['bae_session']) ? sanitize_text_field($_COOKIE['bae_session']) : '';
    if (!$ticket_cookie && !$session_cookie && !is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    global $wpdb;
    $user_id    = is_user_logged_in() ? get_current_user_id() : 0;
    $asset_type = sanitize_text_field($_POST['asset_type'] ?? '');
    $profile_id = intval($_POST['profile_id'] ?? 0);
    $regen_prompt = sanitize_text_field($_POST['regen_prompt'] ?? '');

    $allowed_types = ['business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'sitemap', 'invoice_template', 'price_list', 'flyer_template', 'thank_you_card', 'media_kit', 'poster_a3'];
    $is_custom_asset = strpos($asset_type, 'custom_') === 0;
    if (!$is_custom_asset && !in_array($asset_type, $allowed_types)) {
        wp_send_json_error(['message' => 'Invalid asset type.']);
    }

    $profiles_table = $wpdb->prefix . 'bae_profiles';
    $assets_table   = $wpdb->prefix . 'bae_assets';

    // Ticket-first identity — users are identified by ticket, not user_id
    $profile = null;
    if ($ticket_cookie) {
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$profiles_table} WHERE id = %d AND ticket = %s",
            $profile_id, $ticket_cookie
        ), ARRAY_A);
    }
    if (!$profile && $user_id > 0) {
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$profiles_table} WHERE id = %d AND user_id = %d",
            $profile_id, $user_id
        ), ARRAY_A);
    }
    if (!$profile) {
        // Final fallback for legacy data
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$profiles_table} WHERE id = %d",
            $profile_id
        ), ARRAY_A);
    }
    if (!$profile) {
        wp_send_json_error(['message' => 'Profile not found.']);
    }

    // Validate regen prompt if provided
    if (!empty($regen_prompt)) {
        $asset_names = [
            'business_card' => 'business card',
            'letterhead' => 'letterhead',
            'email_signature' => 'email signature',
            'social_kit' => 'social media kit',
            'brand_guidelines' => 'brand guidelines',
            'sitemap' => 'sitemap'
        ];
        $asset_name = $asset_names[$asset_type] ?? 'asset';
        $lower_prompt = strtolower($regen_prompt);
        $deny_phrases = ['create a website', 'make a flyer', 'design a poster', 'build an app', 'develop software', 'generate a new asset', 'create something new'];
        foreach ($deny_phrases as $phrase) {
            if (strpos($lower_prompt, $phrase) !== false) {
                wp_send_json_error(['message' => 'Prompt must improve the existing ' . $asset_name . ', not create something new.']);
            }
        }
        // AI regen requires a configured key; do not silently fall back.
        if ( empty( bae_gemini_key_pool() ) && empty( bae_groq_key_pool() ) ) {
            wp_send_json_error(['message' => 'AI regeneration is not configured (no API keys found).']);
        }
    }

    // Regeneration path: use AI and error loudly if it fails (no silent static fallback).
    if ( ! empty($regen_prompt) ) {
        // Custom assets: fetch saved HTML from DB. Standard assets: use static generator.
        if ( $is_custom_asset ) {
            $existing_row = $wpdb->get_row($wpdb->prepare(
                "SELECT asset_html, asset_name FROM {$assets_table} WHERE profile_id = %d AND asset_type = %s",
                $profile_id, $asset_type
            ));
            $current_html = $existing_row->asset_html ?? '';
        } else {
            $current_html = bae_generate_asset_html_static($asset_type, $profile, '');
        }
        $asset_names = [
            'business_card'    => 'business card',
            'letterhead'       => 'letterhead',
            'email_signature'  => 'email signature',
            'social_kit'       => 'social media kit',
            'brand_guidelines' => 'brand guidelines',
            'sitemap'          => 'site structure',
        ];
        $asset_name = $is_custom_asset
            ? ( $existing_row->asset_name ?? 'custom asset' )
            : ( $asset_names[$asset_type] ?? 'asset' );
        $ai_prompt = "Improve this {$asset_name} HTML based on the user's request: '{$regen_prompt}'. Return only the improved HTML, no explanations or markdown. IMPORTANT: Do not include <style> tags, <link> tags, or external CSS — use inline styles only.\n\nCurrent HTML:\n{$current_html}";
        $improved_html = bae_gemini_request($ai_prompt);

        if ( is_array($improved_html) && ! empty($improved_html['error']) ) {
            wp_send_json_error(['message' => $improved_html['error']]);
        }
        if ( ! is_string($improved_html) || ! trim($improved_html) ) {
            wp_send_json_error(['message' => 'AI regeneration failed: empty response.']);
        }

        // Hard safety: prevent AI output from leaking CSS that breaks the BAE UI grid.
        // Regenerated assets should be self-contained HTML with inline styles only.
        $improved_html = preg_replace( '/<style\b[^>]*>.*?<\/style>/is', '', $improved_html );
        $improved_html = preg_replace( '/<link\b[^>]*rel=[\'"]?stylesheet[\'"]?[^>]*>/is', '', $improved_html );

        $asset_html = $improved_html;
    } else {
        // Normal generation path (static)
        $asset_html = bae_generate_asset_html_static($asset_type, $profile, '');
    }

    $names = [
        'business_card'    => 'Business Card',
        'letterhead'       => 'Letterhead',
        'email_signature'  => 'Email Signature',
        'social_kit'       => 'Social Media Kit',
        'brand_guidelines' => 'Brand Guidelines',
        'sitemap'          => 'Site Structure',
        'invoice_template' => 'Invoice Template',
        'price_list'       => 'Price List',
        'flyer_template'   => 'Promo Flyer',
        'thank_you_card'   => 'Thank You Card',
        'media_kit'        => 'Media Kit',
        'poster_a3'        => 'A3 Poster',
    ];

    // Ticket-first lookup — users are ticket-based, not user_id
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id, asset_html, asset_name FROM {$assets_table} WHERE profile_id = %d AND asset_type = %s",
        $profile_id, $asset_type
    ));

    // Sanitize before saving so echoed HTML can never break the page
    $asset_html = bae_sanitize_asset_html( $asset_html );

    if ($existing) {
        $result = $wpdb->update($assets_table, [
            'asset_html_prev' => $existing->asset_html ?? '',
            'asset_html'      => $asset_html,
            'is_generated'    => 1,
        ], ['id' => $existing->id]);
    } else {
        $result = $wpdb->insert($assets_table, [
            'rand_id'      => bntm_rand_id(),
            'profile_id'   => $profile_id,
            'user_id'      => $user_id,
            'asset_type'   => $asset_type,
            'asset_name'   => $is_custom_asset ? ( $existing->asset_name ?? 'Custom Asset' ) : ( $names[$asset_type] ?? 'Asset' ),
            'asset_html'   => $asset_html,
            'is_generated' => 1,
        ]);
    }

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to generate asset.']);
    }

    wp_send_json_success(['message' => 'Asset generated successfully!', 'html' => $asset_html]);
}

function bntm_ajax_bae_delete_asset() {
    check_ajax_referer('bae_generate_asset', 'nonce');

    global $wpdb;
    $table    = $wpdb->prefix . 'bae_assets';
    $asset_id = intval($_POST['asset_id'] ?? 0);

    // Ticket-first identity
    $ticket = bae_get_ticket_cookie();
    if ($ticket) {
        // Verify the asset belongs to this ticket's profile
        $asset = $wpdb->get_row($wpdb->prepare(
            "SELECT a.id FROM {$table} a
             JOIN {$wpdb->prefix}bae_profiles p ON p.id = a.profile_id
             WHERE a.id = %d AND p.ticket = %s",
            $asset_id, $ticket
        ));
        if ($asset) {
            $result = $wpdb->delete($table, ['id' => $asset_id], ['%d']);
        } else {
            $result = false;
        }
    } elseif (is_user_logged_in()) {
        $result = $wpdb->delete($table, ['id' => $asset_id, 'user_id' => get_current_user_id()], ['%d', '%d']);
    } else {
        wp_send_json_error(['message' => 'Unauthorized']);
        return;
    }

    if ($result) {
        wp_send_json_success(['message' => 'Asset removed.']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete asset.']);
    }
}

function bntm_ajax_bae_save_kit_settings() {
    check_ajax_referer('bae_save_kit_settings', 'nonce');
    // Ticket-based identity (works for logged-in WP users too)
    $ticket = '';
    if ( !empty($_COOKIE['bae_ticket']) ) {
        $raw = strtoupper( sanitize_text_field( $_COOKIE['bae_ticket'] ) );
        if ( preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw) ) $ticket = $raw;
    }
    if ( empty($ticket) && !is_user_logged_in() ) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    global $wpdb;
    $table      = $wpdb->prefix . 'bae_profiles';
    $visibility = sanitize_text_field($_POST['kit_visibility'] ?? 'private');
    $slug       = sanitize_title($_POST['kit_slug'] ?? '');

    if (!in_array($visibility, ['public', 'private'])) {
        wp_send_json_error(['message' => 'Invalid visibility option.']);
    }
    if (empty($slug)) {
        wp_send_json_error(['message' => 'Kit slug cannot be empty.']);
    }

    // Determine owner identity for conflict checking + update
    $where = [];
    if ( ! empty($ticket) ) {
        $where = [ 'ticket' => $ticket ];
    } else {
        $where = [ 'user_id' => get_current_user_id() ];
        // Also load ticket for conflict check if profile exists (optional)
        $row = $wpdb->get_row($wpdb->prepare("SELECT ticket FROM {$table} WHERE user_id = %d", get_current_user_id()));
        if ( $row && ! empty($row->ticket) ) $ticket = (string) $row->ticket;
    }

    // Ensure unique slug across all profiles (prefer ticket uniqueness when available)
    if ( ! empty($ticket) ) {
        $conflict = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE kit_slug = %s AND ticket != %s",
            $slug, $ticket
        ));
    } else {
        $conflict = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE kit_slug = %s AND user_id != %d",
            $slug, get_current_user_id()
        ));
    }

    if ($conflict) {
        wp_send_json_error(['message' => 'That slug is already taken. Please choose another.']);
    }

    // Update the owner's profile
    $result = $wpdb->update($table, [
        'kit_visibility' => $visibility,
        'kit_slug'       => $slug,
    ], $where);

    if ( $result === false ) {
        wp_send_json_error(['message' => 'Failed to save kit settings.']);
    }

    wp_send_json_success(['message' => 'Kit settings saved successfully!']);
}

function bntm_ajax_bae_reset_profile() {
    check_ajax_referer('bae_reset_profile', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $ticket         = bae_get_ticket_cookie();
    $profiles_table = $wpdb->prefix . 'bae_profiles';
    $assets_table   = $wpdb->prefix . 'bae_assets';

    if ($ticket) {
        $profile = bae_get_profile_by_ticket($ticket);
        if ($profile) {
            $wpdb->delete($assets_table, ['profile_id' => (int) $profile['id']], ['%d']);
            $wpdb->delete($profiles_table, ['id' => (int) $profile['id']], ['%d']);
        }
    }

    wp_send_json_success(['message' => 'Current brand profile and its assets have been reset.']);
}

// =============================================================================
// EXPORT ZIP — Download all generated assets as individual HTML files in a ZIP
// =============================================================================
function bntm_ajax_bae_export_zip() {
    check_ajax_referer('bae_reset_profile', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $assets_table = $wpdb->prefix . 'bae_assets';
    $profile      = bae_get_profile(get_current_user_id());

    if (!$profile) {
        wp_send_json_error(['message' => 'No brand profile found.']);
    }

    $assets = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$assets_table} WHERE profile_id = %d AND is_generated = 1",
        (int) $profile['id']
    ), ARRAY_A);

    if (empty($assets)) {
        wp_send_json_error(['message' => 'No assets generated yet.']);
    }

    // Build ZIP in memory using PHP's ZipArchive
    if (!class_exists('ZipArchive')) {
        wp_send_json_error(['message' => 'ZIP export not supported on this server.']);
    }

    $biz_slug = sanitize_title($profile['business_name'] ?? 'brand');
    $zip_file = sys_get_temp_dir() . '/bae_' . (int) $profile['id'] . '_' . time() . '.zip';
    $zip      = new ZipArchive();

    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        wp_send_json_error(['message' => 'Could not create ZIP file.']);
    }

    $asset_name_map = [
        'business_card'    => 'Business Card',
        'letterhead'       => 'Letterhead',
        'email_signature'  => 'Email Signature',
        'social_kit'       => 'Social Media Kit',
        'brand_guidelines' => 'Brand Guidelines',
        'sitemap'          => 'Site Structure',
        'brand_book'       => 'Brand Book',
    ];

    foreach ($assets as $asset) {
        $type     = $asset['asset_type'];
        $name     = $asset['asset_name'] ?: ($asset_name_map[$type] ?? $type);
        $filename = sanitize_title($name) . '.html';
        $html     = $asset['asset_html'];

        // Wrap in a full HTML document for proper browser rendering
        $full_html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . esc_html($name) . ' — ' . esc_html($profile['business_name']) . '</title>'
            . '<style>body{margin:0;padding:24px;background:#f3f4f6;display:flex;justify-content:center;} '
            . '@media print{body{background:white;padding:0;}}</style>'
            . '</head><body>' . $html . '</body></html>';

        $zip->addFromString($biz_slug . '/' . $filename, $full_html);
    }

    // Add a README
    $readme  = "Brand Assets — " . ($profile['business_name'] ?? 'Your Brand') . "\n";
    $readme .= "Generated by Mothie\n";
    $readme .= "Date: " . date('Y-m-d') . "\n\n";
    $readme .= "FILES INCLUDED:\n";
    foreach ($assets as $asset) {
        $name     = $asset['asset_name'] ?: ($asset_name_map[$asset['asset_type']] ?? $asset['asset_type']);
        $filename = sanitize_title($name) . '.html';
        $readme  .= "  - " . $filename . "\n";
    }
    $readme .= "\nAll files are standalone HTML. Open in any browser or print to PDF.\n";
    $zip->addFromString($biz_slug . '/README.txt', $readme);

    $zip->close();

    if (!file_exists($zip_file)) {
        wp_send_json_error(['message' => 'ZIP file creation failed.']);
    }

    // Stream file to browser
    $zip_data = base64_encode(file_get_contents($zip_file));
    unlink($zip_file);

    wp_send_json_success([
        'filename' => $biz_slug . '-brand-assets.zip',
        'data'     => $zip_data,
        'count'    => count($assets),
    ]);
}

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

function bae_get_profile($user_id) {
    $ticket = bae_get_ticket_cookie();
    if ($ticket) {
        $profile = bae_get_profile_by_ticket($ticket);
        if ($profile) {
            return $profile;
        }
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC, id DESC LIMIT 1", $user_id),
        ARRAY_A
    );
}

// =============================================================================
// PLAN DETECTION
// Returns 'free', 'starter', or 'pro'
// For now reads from bae_profiles.plan column (add this via dbDelta or ALTER)
// Falls back to 'free' if column doesn't exist yet
// =============================================================================
function bae_get_user_plan($user_id, $profile = null) {
    if ( empty($profile) ) return 'free';
    // Special: If ticket is BAE-2525-2525, force PRO for testing
    if ( isset($profile['ticket']) && $profile['ticket'] === 'BAE-2525-2525' ) return 'pro';
    // Read plan from profile — defaults to 'free' if column missing
    return isset($profile['plan']) ? ($profile['plan'] ?: 'free') : 'free';
}

function bae_count_assets($user_id, $profile_id = 0) {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_assets';
    // Always prefer profile_id — this scopes to the specific brand, not all of a user's assets
    if ($profile_id) {
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE profile_id = %d AND is_generated = 1",
            $profile_id
        ));
    }
    // Only fall back to user_id if profile_id is genuinely missing AND user is logged in
    if ($user_id > 0) {
        // Get the user's active profile id first, then count by profile
        $pid = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bae_profiles WHERE user_id = %d AND business_name != '' ORDER BY updated_at DESC LIMIT 1",
            $user_id
        ));
        if ($pid) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE profile_id = %d AND is_generated = 1",
                $pid
            ));
        }
    }
    return 0;
}

function bae_get_onboarding_auto_asset_types($plan = 'free') {
    $all_assets = ['business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'sitemap', 'invoice_template', 'price_list', 'flyer_template', 'thank_you_card', 'media_kit', 'poster_a3'];
    if ($plan === 'starter' || $plan === 'pro') {
        return $all_assets;
    }
    return ['business_card', 'email_signature', 'social_kit'];
}

function bae_has_viewed_onboarding_asset($profile = null) {
    if (empty($profile) || !is_array($profile)) return false;
    return !empty($profile['onboarding_asset_viewed']);
}

function bae_get_initials($name) {
    $words    = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials ?: 'BR';
}

function bae_render_logo_lockup($profile, $args = []) {
    $p = is_array($profile) ? $profile : [];
    $dark = !empty($args['dark']);
    $compact = !empty($args['compact']);

    $name = trim((string) ($p['business_name'] ?? 'Brand Name'));
    if ($name === '') $name = 'Brand Name';
    $tagline = trim((string) ($p['tagline'] ?? ''));
    $style = sanitize_key($p['logo_style'] ?? 'wordmark');
    $initials = bae_get_initials($name);
    $pc = bae_safe_color($p['primary_color'] ?? '', '#1a1a2e');
    $sc = bae_safe_color($p['secondary_color'] ?? '', '#16213e');
    $ac = bae_safe_color($p['accent_color'] ?? '', '#e94560');
    $font = esc_attr($p['font_heading'] ?? 'Inter');
    $icon_scale = max(70, min(160, intval($p['logo_icon_scale'] ?? 100)));
    $spacing = max(6, min(28, intval($p['logo_spacing'] ?? 14)));
    $position = sanitize_key($p['logo_position'] ?? 'auto');
    if (!in_array($position, ['auto', 'left', 'right', 'top'], true)) $position = 'auto';
    $text_case = sanitize_key($p['logo_text_case'] ?? 'default');
    if (!in_array($text_case, ['default', 'uppercase', 'lowercase', 'title'], true)) $text_case = 'default';
    $name_color = $dark ? '#ffffff' : $pc;
    $muted_color = $dark ? 'rgba(255,255,255,0.68)' : $sc;
    $soft_surface = $dark ? 'rgba(255,255,255,0.12)' : 'rgba(255,255,255,0.94)';
    $frame_border = $dark ? 'rgba(255,255,255,0.18)' : 'rgba(26,26,46,0.12)';
    $styled_name = $name;
    if ($text_case === 'uppercase') $styled_name = strtoupper($name);
    elseif ($text_case === 'lowercase') $styled_name = strtolower($name);
    elseif ($text_case === 'title') $styled_name = ucwords(strtolower($name));
    $styled_tagline = $tagline;
    if ($text_case === 'uppercase') $styled_tagline = strtoupper($tagline);
    elseif ($text_case === 'lowercase') $styled_tagline = strtolower($tagline);
    elseif ($text_case === 'title') $styled_tagline = ucwords(strtolower($tagline));
    $base_icon_size = $compact ? 42 : 50;
    $scaled_icon_size = max(28, intval(round($base_icon_size * ($icon_scale / 100))));
    $scaled_emblem_size = max(54, intval(round(($compact ? 76 : 92) * ($icon_scale / 100))));
    $scaled_abstract_size = max(40, intval(round(($compact ? 54 : 68) * ($icon_scale / 100))));
    $root_align = in_array($style, ['stacked', 'emblem', 'badge', 'abstract', 'monogram'], true) ? 'center' : 'flex-start';
    $root_dir = in_array($style, ['stacked', 'emblem', 'badge', 'abstract', 'monogram'], true) ? 'column' : 'row';
    if ($position === 'top') {
        $root_dir = 'column';
        $root_align = 'center';
    } elseif ($position === 'right') {
        $root_dir = 'row-reverse';
        $root_align = 'center';
    } elseif ($position === 'left') {
        $root_dir = 'row';
        $root_align = 'center';
    }
    if (in_array($style, ['emblem', 'badge', 'abstract', 'monogram'], true)) {
        $root_align = 'center';
    }
    $root_gap = $spacing . 'px';

    if (!empty($p['logo_url'])) {
        $img_style = $compact
            ? 'max-height:42px;max-width:120px;object-fit:contain;'
            : 'max-height:52px;max-width:160px;object-fit:contain;';
        if ($dark) $img_style .= 'filter:brightness(0) invert(1);opacity:.92;';
        return '<div style="display:flex;align-items:center;gap:12px;">'
            . '<img src="' . esc_url($p['logo_url']) . '" style="' . esc_attr($img_style) . '" alt="' . esc_attr($name) . '">'
            . '</div>';
    }

    $icon_markup = '';
    if ($style !== 'wordmark' && $style !== 'lettermark' && !empty($p['logo_icon'])) {
        $icon_markup = bae_render_icon($p['logo_icon'], '#ffffff');
    }
    $symbol_markup = $style === 'lettermark'
        ? '<span style="font-family:\'' . $font . '\',sans-serif;font-size:' . max(16, intval(round(($compact ? 18 : 22) * ($icon_scale / 100)))) . 'px;font-weight:800;line-height:1;color:#ffffff;">' . esc_html($initials) . '</span>'
        : $icon_markup;

    $text_block = '<div style="display:flex;flex-direction:column;gap:' . ($tagline !== '' && !$compact ? '4px' : '0') . ';align-items:' . $root_align . ';">'
        . '<div style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '17px' : '21px') . ';font-weight:700;line-height:1.1;color:' . esc_attr($name_color) . ';">' . esc_html($styled_name) . '</div>';
    if ($tagline !== '' && !$compact) {
        $text_block .= '<div style="font-size:' . ($compact ? '11px' : '12px') . ';letter-spacing:.02em;color:' . esc_attr($muted_color) . ';">' . esc_html($styled_tagline) . '</div>';
    }
    $text_block .= '</div>';

    switch ($style) {
        case 'lettermark':
            return '<div style="display:flex;align-items:center;gap:' . $root_gap . ';">'
                . '<div style="width:' . $scaled_icon_size . 'px;height:' . $scaled_icon_size . 'px;border-radius:' . ($compact ? '12px' : '16px') . ';background:linear-gradient(135deg,' . esc_attr($pc) . ',' . esc_attr($ac) . ');display:flex;align-items:center;justify-content:center;box-shadow:0 10px 24px rgba(0,0,0,.12);">'
                . $symbol_markup
                . '</div>'
                . '<div style="display:flex;flex-direction:column;gap:2px;">'
                . '<div style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '18px' : '22px') . ';font-weight:800;color:' . esc_attr($name_color) . ';letter-spacing:.08em;">' . esc_html($initials) . '</div>'
                . '<div style="font-size:' . ($compact ? '10px' : '11px') . ';text-transform:uppercase;letter-spacing:.18em;color:' . esc_attr($muted_color) . ';">' . esc_html($styled_name) . '</div>'
                . '</div>'
                . '</div>';

        case 'combination':
            return '<div style="display:flex;align-items:center;gap:' . $root_gap . ';">'
                . '<div style="width:' . $scaled_icon_size . 'px;height:' . $scaled_icon_size . 'px;border-radius:' . ($compact ? '12px' : '15px') . ';background:' . esc_attr($pc) . ';display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(0,0,0,.10);">'
                . $symbol_markup
                . '</div>'
                . $text_block
                . '</div>';

        case 'emblem':
            return '<div style="display:flex;flex-direction:column;align-items:center;gap:' . ($compact ? '8px' : '10px') . ';">'
                . '<div style="width:' . $scaled_emblem_size . 'px;height:' . $scaled_emblem_size . 'px;border-radius:999px;background:linear-gradient(135deg,' . esc_attr($pc) . ', ' . esc_attr($sc) . ');border:4px solid ' . esc_attr($soft_surface) . ';display:flex;align-items:center;justify-content:center;box-shadow:0 10px 24px rgba(0,0,0,.12);">'
                . ($symbol_markup ?: '<span style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '20px' : '26px') . ';font-weight:800;color:#ffffff;">' . esc_html($initials) . '</span>')
                . '</div>'
                . '<div style="text-align:center;">'
                . '<div style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '15px' : '18px') . ';font-weight:700;color:' . esc_attr($name_color) . ';">' . esc_html($styled_name) . '</div>'
                . '</div>'
                . '</div>';

        case 'monogram':
            return '<div style="display:flex;flex-direction:column;align-items:center;gap:' . ($compact ? '4px' : '6px') . ';">'
                . '<div style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '28px' : '38px') . ';font-weight:800;line-height:1;color:' . esc_attr($name_color) . ';letter-spacing:.08em;">' . esc_html($initials) . '</div>'
                . '<div style="width:' . ($compact ? '34px' : '48px') . ';height:2px;background:' . esc_attr($ac) . ';border-radius:999px;"></div>'
                . '<div style="font-size:' . ($compact ? '10px' : '11px') . ';letter-spacing:.16em;text-transform:uppercase;color:' . esc_attr($muted_color) . ';">' . esc_html($styled_name) . '</div>'
                . '</div>';

        case 'abstract':
            return '<div style="display:flex;flex-direction:column;align-items:center;gap:' . ($compact ? '8px' : '10px') . ';">'
                . '<div style="width:' . $scaled_abstract_size . 'px;height:' . $scaled_abstract_size . 'px;border-radius:' . ($compact ? '18px' : '22px') . ';background:linear-gradient(135deg,' . esc_attr($pc) . ', ' . esc_attr($ac) . ');display:flex;align-items:center;justify-content:center;box-shadow:0 10px 24px rgba(0,0,0,.12);transform:rotate(-8deg);">'
                . ($symbol_markup ?: '<span style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '18px' : '22px') . ';font-weight:800;color:#ffffff;">' . esc_html($initials) . '</span>')
                . '</div>'
                . '<div style="font-size:' . ($compact ? '10px' : '11px') . ';letter-spacing:.14em;text-transform:uppercase;color:' . esc_attr($muted_color) . ';">Abstract Mark</div>'
                . '</div>';

        case 'badge':
            return '<div style="display:inline-flex;align-items:center;gap:' . ($compact ? '8px' : '10px') . ';padding:' . ($compact ? '9px 12px' : '12px 16px') . ';border-radius:999px;border:1px solid ' . esc_attr($frame_border) . ';background:' . esc_attr($soft_surface) . ';">'
                . '<div style="width:' . ($compact ? '28px' : '34px') . ';height:' . ($compact ? '28px' : '34px') . ';border-radius:999px;background:' . esc_attr($pc) . ';display:flex;align-items:center;justify-content:center;">'
                . ($symbol_markup ?: '<span style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '12px' : '14px') . ';font-weight:800;color:#ffffff;">' . esc_html($initials) . '</span>')
                . '</div>'
                . '<div style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '15px' : '17px') . ';font-weight:700;color:' . esc_attr($name_color) . ';">' . esc_html($styled_name) . '</div>'
                . '</div>';

        case 'stacked':
            return '<div style="display:flex;flex-direction:column;align-items:center;gap:' . ($compact ? '8px' : '10px') . ';text-align:center;">'
                . '<div style="width:' . $scaled_icon_size . 'px;height:' . $scaled_icon_size . 'px;border-radius:' . ($compact ? '14px' : '18px') . ';background:' . esc_attr($pc) . ';display:flex;align-items:center;justify-content:center;">'
                . ($symbol_markup ?: '<span style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '16px' : '20px') . ';font-weight:800;color:#ffffff;">' . esc_html($initials) . '</span>')
                . '</div>'
                . $text_block
                . '</div>';

        case 'outlined':
            return '<div style="display:inline-flex;flex-direction:column;gap:' . ($tagline !== '' ? '5px' : '0') . ';padding:' . ($compact ? '12px 14px' : '14px 18px') . ';border:1.5px solid ' . esc_attr($frame_border) . ';border-radius:' . ($compact ? '14px' : '18px') . ';background:' . esc_attr($soft_surface) . ';">'
                . '<div style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '18px' : '22px') . ';font-weight:700;color:' . esc_attr($name_color) . ';">' . esc_html($styled_name) . '</div>'
                . ($tagline !== '' ? '<div style="font-size:' . ($compact ? '10px' : '11px') . ';letter-spacing:.14em;text-transform:uppercase;color:' . esc_attr($muted_color) . ';">' . esc_html($styled_tagline) . '</div>' : '')
                . '</div>';

        case 'minimal':
            return '<div style="display:flex;align-items:center;gap:' . ($compact ? '8px' : '10px') . ';">'
                . '<div style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '18px' : '22px') . ';font-weight:800;color:' . esc_attr($name_color) . ';letter-spacing:.08em;">' . esc_html($initials) . '</div>'
                . '<div style="width:' . ($compact ? '16px' : '22px') . ';height:2px;background:' . esc_attr($ac) . ';border-radius:999px;"></div>'
                . '<div style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '16px' : '19px') . ';font-weight:600;color:' . esc_attr($name_color) . ';">' . esc_html($styled_name) . '</div>'
                . '</div>';

        case 'wordmark':
        default:
            return '<div style="display:flex;flex-direction:column;gap:' . ($tagline !== '' ? '4px' : '0') . ';">'
                . '<div style="font-family:\'' . $font . '\',sans-serif;font-size:' . ($compact ? '20px' : '24px') . ';font-weight:700;color:' . esc_attr($name_color) . ';letter-spacing:.01em;">' . esc_html($styled_name) . '</div>'
                . ($tagline !== '' ? '<div style="font-size:' . ($compact ? '11px' : '12px') . ';letter-spacing:.14em;text-transform:uppercase;color:' . esc_attr($muted_color) . ';">' . esc_html($styled_tagline) . '</div>' : '')
                . '</div>';
    }
}

function bae_derive_tone_tags($industry, $personality) {
    $base_tags = [
        'Food & Beverage'       => ['Warm', 'Approachable', 'Fresh', 'Community-first'],
        'Retail & Commerce'     => ['Trustworthy', 'Value-driven', 'Helpful', 'Straightforward'],
        'Fashion & Apparel'     => ['Bold', 'Creative', 'Aspirational', 'Modern'],
        'Health & Wellness'     => ['Caring', 'Calm', 'Informative', 'Empowering'],
        'Beauty & Cosmetics'    => ['Confident', 'Inclusive', 'Vibrant', 'Empowering'],
        'Technology'            => ['Precise', 'Innovative', 'Clear', 'Professional'],
        'Professional Services' => ['Authoritative', 'Reliable', 'Expert', 'Direct'],
        'Education & Training'  => ['Encouraging', 'Clear', 'Supportive', 'Knowledgeable'],
        'Home & Lifestyle'      => ['Warm', 'Inviting', 'Authentic', 'Inspiring'],
        'Agriculture'           => ['Grounded', 'Honest', 'Community-focused', 'Sustainable'],
        'Construction & Trades' => ['Dependable', 'Strong', 'Straightforward', 'Skilled'],
        'Creative & Media'      => ['Expressive', 'Bold', 'Innovative', 'Story-driven'],
        'Other'                 => ['Authentic', 'Reliable', 'Clear', 'Approachable'],
    ];

    $tags = $base_tags[$industry] ?? ['Authentic', 'Reliable', 'Clear', 'Approachable'];

    $personality_lower = strtolower($personality);
    if (strpos($personality_lower, 'family')  !== false) $tags[] = 'Family-friendly';
    if (strpos($personality_lower, 'premium') !== false) $tags[] = 'Premium';
    if (strpos($personality_lower, 'young')   !== false) $tags[] = 'Youthful';
    if (strpos($personality_lower, 'local')   !== false) $tags[] = 'Local Pride';
    if (strpos($personality_lower, 'quality') !== false) $tags[] = 'Quality-focused';

    return array_unique($tags);
}

// =============================================================================
// ICON LIBRARY — 50+ icons organized by category
// bae_get_all_icons() returns [key => label] for the picker
// bae_get_icon_svg_preview() returns small SVG for the picker tile
// bae_render_icon() returns full-size SVG for use in assets
// =============================================================================

function bae_get_all_icons() {
    return [
        // Shapes
        ''          => 'None',
        'circle'    => 'Circle',
        'diamond'   => 'Diamond',
        'hexagon'   => 'Hexagon',
        'triangle'  => 'Triangle',
        'square'    => 'Square',
        'octagon'   => 'Octagon',
        'cross'     => 'Cross',
        'plus'      => 'Plus',
        'infinity'  => 'Infinity',
        // Nature
        'leaf'      => 'Leaf',
        'tree'      => 'Tree',
        'flower'    => 'Flower',
        'sun'       => 'Sun',
        'moon'      => 'Moon',
        'star'      => 'Star',
        'snowflake' => 'Snowflake',
        'wave'      => 'Wave',
        'mountain'  => 'Mountain',
        'drop'      => 'Water Drop',
        // Business
        'briefcase' => 'Briefcase',
        'chart'     => 'Chart',
        'building'  => 'Building',
        'handshake' => 'Handshake',
        'target'    => 'Target',
        'shield'    => 'Shield',
        'crown'     => 'Crown',
        'trophy'    => 'Trophy',
        'badge'     => 'Badge',
        'key'       => 'Key',
        // Tech
        'bolt'      => 'Bolt',
        'code'      => 'Code',
        'cpu'       => 'CPU',
        'globe'     => 'Globe',
        'link'      => 'Link',
        'signal'    => 'Signal',
        'wifi'      => 'WiFi',
        'zap'       => 'Zap',
        'layers'    => 'Layers',
        'grid'      => 'Grid',
        // Creative
        'brush'     => 'Brush',
        'pen'       => 'Pen',
        'camera'    => 'Camera',
        'music'     => 'Music',
        'film'      => 'Film',
        'palette'   => 'Palette',
        'scissors'  => 'Scissors',
        'feather'   => 'Feather',
        // Food & Life
        'flame'     => 'Flame',
        'coffee'    => 'Coffee',
        'heart'     => 'Heart',
        'home'      => 'Home',
        'anchor'    => 'Anchor',
        'compass'   => 'Compass',
        'rocket'    => 'Rocket',
        'gem'       => 'Gem',
        'award'     => 'Award',
        'eye'       => 'Eye',
    ];
}

function bae_get_icon_svg_preview($key) {
    // Returns 18x18 SVG for the picker tile
    $c = 'currentColor';
    $w = '18'; $h = '18';
    $map = [
        ''          => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24"><line x1="4" y1="4" x2="20" y2="20" stroke="'.$c.'" stroke-width="2"/><line x1="20" y1="4" x2="4" y2="20" stroke="'.$c.'" stroke-width="2"/></svg>',
        'circle'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>',
        'diamond'   => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="'.$c.'"><path d="M12 2L2 9l10 13L22 9z"/></svg>',
        'hexagon'   => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
        'triangle'  => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="'.$c.'"><path d="M12 2L2 21h20z"/></svg>',
        'square'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
        'octagon'   => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/></svg>',
        'cross'     => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>',
        'plus'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>',
        'infinity'  => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M12 12c-2-2.5-4-4-6-4a4 4 0 0 0 0 8c2 0 4-1.5 6-4zm0 0c2 2.5 4 4 6 4a4 4 0 0 0 0-8c-2 0-4 1.5-6 4z"/></svg>',
        'leaf'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="'.$c.'"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 008 20C19 20 22 3 22 3c-1 2-8 5.5-10 8.5z"/></svg>',
        'tree'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M12 22V12M9 7H4l8-5 8 5h-5"/><path d="M9 12H4l8-5 8 5h-5"/><path d="M9 17H4l8-5 8 5h-5"/></svg>',
        'flower'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2a2 2 0 0 0-2 2c0 1.1.9 2 2 2a2 2 0 0 0 2-2 2 2 0 0 0-2-2zM12 18a2 2 0 0 0-2 2 2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0-2-2zM2 12a2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0-2-2 2 2 0 0 0-2 2zM18 12a2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0-2-2 2 2 0 0 0-2 2z"/></svg>',
        'sun'       => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>',
        'moon'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>',
        'star'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="'.$c.'"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'snowflake' => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><line x1="12" y1="2" x2="12" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/><line x1="19.07" y1="4.93" x2="4.93" y2="19.07"/></svg>',
        'wave'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M2 12c1.5-3 3-4.5 4.5-4.5S9 9 10.5 9 13.5 6 15 6s3 1.5 4.5 1.5S22 9 22 12"/><path d="M2 17c1.5-3 3-4.5 4.5-4.5S9 14 10.5 14 13.5 11 15 11s3 1.5 4.5 1.5S22 14 22 17"/></svg>',
        'mountain'  => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="m3 20 6-9 4 5 3-4 5 8z"/></svg>',
        'drop'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="'.$c.'"><path d="M12 2C6 10 4 14 4 16a8 8 0 0 0 16 0c0-2-2-6-8-14z"/></svg>',
        'briefcase' => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>',
        'chart'     => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'building'  => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18z"/><path d="M6 12H4a2 2 0 0 0-2 2v8h4"/><path d="M18 9h2a2 2 0 0 1 2 2v11h-4"/><line x1="10" y1="6" x2="10" y2="6"/><line x1="14" y1="6" x2="14" y2="6"/><line x1="10" y1="10" x2="10" y2="10"/><line x1="14" y1="10" x2="14" y2="10"/><line x1="10" y1="14" x2="10" y2="14"/><line x1="14" y1="14" x2="14" y2="14"/></svg>',
        'handshake' => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81"/><path d="m2 9 2.06-2.06A2 2 0 0 1 5.48 6.4l1.2-.4a3 3 0 0 1 2.28.17l3.04 1.52"/><path d="m22 15-3.06 3.06A2 2 0 0 1 17.52 18.4l-1.2.4a3 3 0 0 1-2.28-.17l-1.04-.52"/></svg>',
        'target'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
        'shield'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="'.$c.'"><path d="M12 2l7 4v5c0 5.55-3.84 10.74-7 12-3.16-1.26-7-6.45-7-12V6l7-4z"/></svg>',
        'crown'     => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="'.$c.'"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm0 4h14v-2H5v2z"/></svg>',
        'trophy'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg>',
        'badge'     => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76z"/><path d="m9 12 2 2 4-4"/></svg>',
        'key'       => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>',
        'bolt'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="'.$c.'"><path d="M13 2L4.09 12.26 9 12.97 11 22l8.91-10.26L15 10.97z"/></svg>',
        'code'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
        'cpu'       => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>',
        'globe'     => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        'link'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
        'signal'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><line x1="2" y1="20" x2="2" y2="20"/><line x1="7" y1="20" x2="7" y2="14"/><line x1="12" y1="20" x2="12" y2="9"/><line x1="17" y1="20" x2="17" y2="4"/><line x1="22" y1="20" x2="22" y2="2"/></svg>',
        'wifi'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>',
        'zap'       => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'layers'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
        'grid'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'brush'     => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="m9.06 11.9 8.07-8.06a2.85 2.85 0 1 1 4.03 4.03l-8.06 8.08"/><path d="M7.07 14.94c-1.66 0-3 1.35-3 3.02 0 1.33-2.5 1.52-2 2.02 1 1 2.48 1.02 3.5 1.02 2.2 0 3.99-1.8 3.99-4.04a3.01 3.01 0 0 0-2.49-3.02z"/></svg>',
        'pen'       => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>',
        'camera'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
        'music'     => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
        'film'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>',
        'palette'   => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5" fill="'.$c.'"/><circle cx="17.5" cy="10.5" r=".5" fill="'.$c.'"/><circle cx="8.5" cy="7.5" r=".5" fill="'.$c.'"/><circle cx="6.5" cy="12.5" r=".5" fill="'.$c.'"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>',
        'scissors'  => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>',
        'feather'   => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" y1="8" x2="2" y2="22"/><line x1="17.5" y1="15" x2="9" y2="15"/></svg>',
        'flame'     => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="'.$c.'"><path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67z"/></svg>',
        'coffee'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>',
        'heart'     => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="'.$c.'"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'home'      => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'anchor'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="12" cy="5" r="3"/><line x1="12" y1="22" x2="12" y2="8"/><path d="M5 12H2a10 10 0 0 0 20 0h-3"/></svg>',
        'compass'   => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>',
        'rocket'    => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/></svg>',
        'gem'       => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><polygon points="6 3 18 3 22 9 12 22 2 9"/><polyline points="2 9 12 14 22 9"/><line x1="12" y1="22" x2="12" y2="14"/><line x1="6" y1="3" x2="2" y2="9"/><line x1="18" y1="3" x2="22" y2="9"/></svg>',
        'award'     => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
        'eye'       => '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    ];
    return $map[$key] ?? $map[''];
}

function bae_render_icon($icon, $color) {
    // Returns full 22x22 SVG with the given color applied
    $svg = bae_get_icon_svg_preview($icon);
    // Replace currentColor with the actual color
    $svg = str_replace('currentColor', esc_attr($color), $svg);
    // Scale to 22x22
    $svg = preg_replace('/width="18"/', 'width="22"', $svg);
    $svg = preg_replace('/height="18"/', 'height="22"', $svg);
    return $svg;
}

add_action('admin_notices', function() {
    $types = [
        'business-card',
        'letterhead', 
        'email-signature',
        'social-kit',
        'brand-guideline',
    ];
    
    echo '<div class="notice notice-info"><p><strong>BAE Block Debug:</strong><br>';
    
    foreach ($types as $type) {
        $path = BNTM_BAE_PATH . 'blocks/' . $type;
        $json_exists = file_exists($path . '/block.json') ? '✅' : '❌ MISSING';
        $js_exists   = file_exists($path . '/index.js')   ? '✅' : '❌ MISSING';
        $registered  = WP_Block_Type_Registry::get_instance()->is_registered('bae/' . $type) ? '✅' : '❌ NOT REGISTERED';
        
        echo "<br><strong>bae/{$type}</strong> — block.json: {$json_exists} | index.js: {$js_exists} | registered: {$registered}";
    }
    
    echo '</p></div>';
});

// =============================================================================
// NEW HELPER: Color Psychology Descriptions
// =============================================================================

function bae_get_color_psychology($pc, $sc, $ac) {
    // Analyze hue of each color
    $primary_hue   = bae_hex_to_hue($pc);
    $secondary_hue = bae_hex_to_hue($sc);
    $accent_hue    = bae_hex_to_hue($ac);

    $hue_meanings = [
        'red'    => 'Red communicates energy, passion, and urgency. It commands attention and drives action — used widely in food, retail, and brands that want to excite or motivate.',
        'orange' => 'Orange blends red\'s energy with yellow\'s warmth. It feels friendly, enthusiastic, and approachable — great for brands that want to feel fun without being aggressive.',
        'yellow' => 'Yellow radiates optimism, clarity, and warmth. It\'s eye-catching and cheerful — associated with creativity, sunshine, and forward-thinking brands.',
        'green'  => 'Green signals growth, health, and balance. It\'s calming and trustworthy — widely used in wellness, agriculture, finance, and eco-friendly brands.',
        'blue'   => 'Blue builds trust, credibility, and calm. It\'s the most universally liked color — used by banks, tech companies, and healthcare to communicate reliability and professionalism.',
        'purple' => 'Purple suggests creativity, luxury, and wisdom. It\'s associated with premium brands, spirituality, and imaginative industries like beauty, education, and entertainment.',
        'pink'   => 'Pink communicates warmth, femininity, and playfulness. Modern brands use it to feel youthful, friendly, and emotionally connected with their audience.',
        'brown'  => 'Brown grounds a brand in tradition, reliability, and earthiness. It feels natural and honest — used in food, craftsmanship, outdoor, and heritage brands.',
        'gray'   => 'Gray conveys sophistication, neutrality, and professionalism. It works as a foundation for modern, minimalist brands that want to feel sleek and balanced.',
        'black'  => 'Black is the ultimate authority — luxury, power, and elegance. It communicates exclusivity and timelessness, used by premium and fashion-forward brands.',
        'white'  => 'White communicates purity, simplicity, and space. It creates breathing room and is the go-to for clean, modern, and health-focused brands.',
        'mixed'  => 'This color sits between hues, carrying a mix of emotional signals — versatile and distinctive when used intentionally.',
    ];

    return [
        'primary'   => ($hue_meanings[$primary_hue]   ?? $hue_meanings['mixed']) . ' As your primary color, this defines the dominant impression of your brand identity.',
        'secondary' => ($hue_meanings[$secondary_hue] ?? $hue_meanings['mixed']) . ' As your secondary color, this supports and complements the primary — used for backgrounds, cards, and supporting elements.',
        'accent'    => ($hue_meanings[$accent_hue]    ?? $hue_meanings['mixed']) . ' As your accent color, this creates emphasis and draws attention to calls-to-action, badges, and key highlights.',
    ];
}

function bae_hex_to_hue($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return 'mixed';
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    $max = max($r, $g, $b); $min = min($r, $g, $b);
    if ($max == $min) { // achromatic
        if ($max > 0.85) return 'white';
        if ($max < 0.2)  return 'black';
        return 'gray';
    }
    $d = $max - $min;
    if ($max == $r)      $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
    elseif ($max == $g)  $h = ($b - $r) / $d + 2;
    else                 $h = ($r - $g) / $d + 4;
    $h *= 60;
    if ($h < 0) $h += 360;

    if ($h < 15 || $h >= 345) return 'red';
    if ($h < 45)  return 'orange';
    if ($h < 70)  return 'yellow';
    if ($h < 160) return 'green';
    if ($h < 250) return 'blue';
    if ($h < 290) return 'purple';
    if ($h < 345) return 'pink';
    return 'red';
}

function bae_get_font_rationale($fh, $fb) {
    $font_desc = [
        'Inter'           => 'clean, modern, and highly readable at any size — popular for tech and professional brands',
        'Playfair Display'=> 'elegant with high contrast serifs — evokes tradition, luxury, and editorial refinement',
        'Montserrat'      => 'geometric and contemporary — communicates precision and forward-thinking confidence',
        'Raleway'         => 'thin and elegant with distinctive letterforms — suited for creative and fashion-forward brands',
        'Oswald'          => 'condensed and bold — impactful for headlines, great for brands that want to make a statement',
        'Lora'            => 'brushed-stroke serifs with a literary feel — warm, approachable, and trustworthy',
        'Poppins'         => 'rounded and geometric — friendly, modern, and approachable for a wide audience',
        'Nunito'          => 'rounded and soft — feels gentle and welcoming, ideal for community-focused or lifestyle brands',
        'Roboto Slab'     => 'mechanical precision with slab serifs — sturdy and authoritative for technical or industrial brands',
        'Merriweather'    => 'designed for screen readability — warm serif that works well in long-form content and editorial layouts',
    ];

    return [
        'heading' => ($font_desc[$fh] ?? 'a distinctive typeface') . '. This font was selected to lead your brand\'s visual voice in headlines and prominent text.',
        'body'    => ($font_desc[$fb] ?? 'a readable typeface') . '. This font ensures your body text remains clear and comfortable to read at any length.',
    ];
}

function bae_get_industry_desc($industry) {
    $descs = [
        'Food & Beverage'       => 'Businesses in this space thrive on warmth, community, and sensory appeal. Customers choose based on trust, quality, and emotional connection.',
        'Retail & Commerce'     => 'Retail brands compete on value, convenience, and trust. A strong brand reduces price sensitivity and builds loyal repeat buyers.',
        'Fashion & Apparel'     => 'Fashion is identity. Your brand must communicate taste, aspiration, and personality — before a single product is examined.',
        'Health & Wellness'     => 'Trust is everything in wellness. Customers need to believe you understand their needs and genuinely care about their wellbeing.',
        'Beauty & Cosmetics'    => 'Beauty brands sell confidence and transformation. Your visual identity should make customers feel something before they buy.',
        'Technology'            => 'Tech brands must balance innovation with accessibility. The best tech brands feel powerful but approachable.',
        'Professional Services' => 'Credibility is currency. Clients hire professionals they trust — your brand must communicate expertise and reliability.',
        'Education & Training'  => 'Education brands inspire possibility. Students and parents want to believe in the journey your brand promises.',
        'Home & Lifestyle'      => 'Home brands tap into aspiration and comfort. Customers want to see themselves living the life your brand represents.',
        'Agriculture'           => 'Agricultural brands win through authenticity, quality, and connection to land. Honest and grounded branding resonates deeply.',
        'Construction & Trades' => 'Trade brands compete on reliability and expertise. Customers want confidence that the job gets done right.',
        'Creative & Media'      => 'Creative brands sell vision and capability. Your brand itself is proof of your work — it must demonstrate your aesthetic skill.',
        'Other'                 => 'A distinctive brand identity helps you stand out in your market, build recognition, and charge what you\'re worth.',
    ];
    return $descs[$industry] ?? $descs['Other'];
}

function bae_get_voice_examples($tone_tags, $positive) {
    $positive_map = [
        'Warm'             => '"We\'re always here for you."',
        'Approachable'     => '"Let\'s figure this out together."',
        'Fresh'            => '"Something new every day."',
        'Community-first'  => '"Made for our neighborhood."',
        'Trustworthy'      => '"We\'ll be straight with you."',
        'Professional'     => '"Here\'s what the data shows."',
        'Bold'             => '"We do things differently."',
        'Creative'         => '"Imagine what\'s possible."',
        'Empowering'       => '"You\'ve got this — we\'ve got you."',
        'Precise'          => '"Every detail matters."',
        'Innovative'       => '"We built something you haven\'t seen yet."',
        'Local Pride'      => '"Proudly made right here."',
        'Family-friendly'  => '"Safe for the whole family."',
        'Premium'          => '"Only the finest."',
        'Youthful'         => '"Keep it real. Keep it fun."',
    ];
    $negative_map = [
        'Warm'             => '"We are not responsible for..."',
        'Approachable'     => 'Corporate jargon and technical speak',
        'Fresh'            => '"As per our long-standing tradition..."',
        'Community-first'  => 'Cold, transactional language',
        'Trustworthy'      => 'Vague promises without substance',
        'Professional'     => 'Slang or overly casual tone in formal contexts',
        'Bold'             => 'Safe, hedged, non-committal statements',
        'Creative'         => '"It is what it is."',
        'Empowering'       => 'Condescending or preachy messaging',
        'Precise'          => '"Roughly speaking, more or less..."',
        'Innovative'       => '"We\'ve always done it this way."',
        'Local Pride'      => 'Generic, could-be-anywhere messaging',
        'Family-friendly'  => 'Edgy or exclusionary language',
        'Premium'          => 'Discount language, urgency tricks',
        'Youthful'         => 'Stiff, formal, or dated phrasing',
    ];
    $map = $positive ? $positive_map : $negative_map;
    $examples = [];
    foreach ($tone_tags as $tag) {
        if (isset($map[$tag])) $examples[] = '&bull; ' . $map[$tag];
        if (count($examples) >= 3) break;
    }
    if (empty($examples)) {
        return $positive ? '&bull; Clear and direct &bull; Honest and consistent &bull; Respectful of your audience' : '&bull; Jargon &bull; Vague promises &bull; Inconsistent voice';
    }
    return implode('<br>', $examples);
}

// =============================================================================
// NEW HELPER: Tagline Variants Generator
// =============================================================================

function bae_generate_tagline_variants($biz_name, $industry, $current_tagline) {
    $templates = [
        'Food & Beverage'       => [
            "Fresh, made with love — {$biz_name}",
            "Every bite, a memory",
            "Taste the difference at {$biz_name}",
            "Good food. Good people. {$biz_name}.",
        ],
        'Retail & Commerce'     => [
            "Quality you can count on",
            "Find what you need at {$biz_name}",
            "More than a store — a destination",
            "Your everyday, elevated",
        ],
        'Fashion & Apparel'     => [
            "Wear your story",
            "Style without compromise",
            "Dress the life you want",
            "{$biz_name} — wear it well",
        ],
        'Health & Wellness'     => [
            "Feel better. Live better.",
            "Your wellness, our mission",
            "Because you deserve to thrive",
            "Health is wealth — {$biz_name}",
        ],
        'Beauty & Cosmetics'    => [
            "Beauty starts from within",
            "Confidence, bottled.",
            "Look good. Feel unstoppable.",
            "{$biz_name} — glow on",
        ],
        'Technology'            => [
            "Built for what's next",
            "Smarter, together",
            "Technology that works for you",
            "{$biz_name} — innovate daily",
        ],
        'Professional Services' => [
            "Expertise you can trust",
            "Results that speak for themselves",
            "Your success is our standard",
            "Reliable. Professional. {$biz_name}.",
        ],
        'Education & Training'  => [
            "Learn. Grow. Achieve.",
            "Every lesson, a step forward",
            "Where futures are built",
            "{$biz_name} — unlock your potential",
        ],
        'Home & Lifestyle'      => [
            "Make your space yours",
            "Home is where {$biz_name} is",
            "Living beautifully, every day",
            "Comfort, curated.",
        ],
        'Agriculture'           => [
            "From the earth, for the people",
            "Fresh from the source",
            "Grown with care. Delivered with pride.",
            "{$biz_name} — rooted in quality",
        ],
        'Construction & Trades' => [
            "Built right, built to last",
            "We build your vision",
            "Quality craftsmanship, every time",
            "{$biz_name} — strength in every detail",
        ],
        'Creative & Media'      => [
            "Ideas that move people",
            "Where creativity meets purpose",
            "We make things that matter",
            "{$biz_name} — your story, told well",
        ],
        'Other' => [
            "Quality you can trust",
            "Built for you",
            "{$biz_name} — making a difference",
            "Committed to excellence",
        ],
    ];

    $variants = $templates[$industry] ?? $templates['Other'];
    if (!empty($current_tagline)) {
        array_unshift($variants, $current_tagline . ' (current)');
    }
    return array_slice($variants, 0, 5);
}

// =============================================================================
// NEW HELPER: Industry Sitemap Generator
// =============================================================================

function bae_get_industry_sitemap($industry, $biz_name) {
    $base = [
        ['name' => 'Home',    'slug' => '/',         'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>', 'priority' => 'high',   'desc' => 'Main landing page. Hero section, key offerings, and primary CTA. First impression — make it count.'],
        ['name' => 'About',   'slug' => '/about',    'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>', 'priority' => 'high',   'desc' => 'Your story, mission, values, and team. Builds trust and humanizes your brand.'],
        ['name' => 'Contact', 'slug' => '/contact',  'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>', 'priority' => 'high',   'desc' => 'Contact form, phone, email, map/address, and business hours.'],
        ['name' => 'Privacy', 'slug' => '/privacy',  'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>', 'priority' => 'low',    'desc' => 'Privacy policy — required for any business collecting customer data or using analytics.'],
    ];

    $industry_pages = [
        'Food & Beverage' => [
            ['name' => 'Menu',      'slug' => '/menu',      'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>', 'priority' => 'high',   'desc' => 'Full menu with categories, descriptions, photos, and prices. Your most-visited page.'],
            ['name' => 'Order',     'slug' => '/order',     'icon' => '🛒', 'priority' => 'high',   'desc' => 'Online ordering or reservation system. Direct revenue driver.'],
            ['name' => 'Gallery',   'slug' => '/gallery',   'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>', 'priority' => 'medium', 'desc' => 'Food photography and restaurant ambiance shots. Social proof through visuals.'],
            ['name' => 'Blog',      'slug' => '/blog',      'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>', 'priority' => 'low',    'desc' => 'Recipes, behind-the-scenes stories, and food culture content. Boosts SEO.'],
        ],
        'Retail & Commerce' => [
            ['name' => 'Shop',      'slug' => '/shop',      'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>', 'priority' => 'high',   'desc' => 'Product catalog with filters, search, and categories. Core of your retail experience.'],
            ['name' => 'Products',  'slug' => '/products',  'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12.89 1.45 8 4A2 2 0 0 1 22 7.24v9.53a2 2 0 0 1-1.11 1.79l-8 4a2 2 0 0 1-1.79 0l-8-4a2 2 0 0 1-1.1-1.8V7.24a2 2 0 0 1 1.11-1.79l8-4a2 2 0 0 1 1.78 0z"/><polyline points="2.32 6.16 12 11 21.68 6.16"/><line x1="12" y1="22.76" x2="12" y2="11"/></svg>', 'priority' => 'high',   'desc' => 'Individual product pages with photos, specs, and add-to-cart.'],
            ['name' => 'Deals',     'slug' => '/deals',     'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>', 'priority' => 'medium', 'desc' => 'Promotions, sale items, and limited-time offers. High traffic potential.'],
            ['name' => 'FAQ',       'slug' => '/faq',       'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>', 'priority' => 'medium', 'desc' => 'Common questions about shipping, returns, sizing, and policies.'],
        ],
        'Professional Services' => [
            ['name' => 'Services',  'slug' => '/services',  'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>', 'priority' => 'high',   'desc' => 'What you offer — detailed service descriptions with pricing or inquiry CTAs.'],
            ['name' => 'Portfolio', 'slug' => '/portfolio', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>', 'priority' => 'high',   'desc' => 'Past projects, case studies, and results. Social proof for high-ticket services.'],
            ['name' => 'Pricing',   'slug' => '/pricing',   'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>', 'priority' => 'medium', 'desc' => 'Transparent pricing tiers or estimate range. Builds trust and filters leads.'],
            ['name' => 'Blog',      'slug' => '/blog',      'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>', 'priority' => 'medium', 'desc' => 'Thought leadership and industry insights. Positions you as an expert.'],
        ],
        'Health & Wellness' => [
            ['name' => 'Services',  'slug' => '/services',  'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7z"/><line x1="8.5" y1="8.5" x2="15.5" y2="15.5"/></svg>', 'priority' => 'high',   'desc' => 'Treatments, programs, and wellness offerings with full descriptions.'],
            ['name' => 'Book',      'slug' => '/book',      'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>', 'priority' => 'high',   'desc' => 'Appointment booking system — reduce friction to conversion.'],
            ['name' => 'Resources', 'slug' => '/resources', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>', 'priority' => 'medium', 'desc' => 'Health guides, tips, and educational content. Builds authority and SEO value.'],
            ['name' => 'FAQ',       'slug' => '/faq',       'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>', 'priority' => 'low',    'desc' => 'Answers to common patient/client questions about services and policies.'],
        ],
        'Technology' => [
            ['name' => 'Products',  'slug' => '/products',  'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>', 'priority' => 'high',   'desc' => 'Product feature pages with demos, screenshots, and use cases.'],
            ['name' => 'Pricing',   'slug' => '/pricing',   'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>', 'priority' => 'high',   'desc' => 'Subscription tiers or license options with feature comparison table.'],
            ['name' => 'Docs',      'slug' => '/docs',      'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>', 'priority' => 'medium', 'desc' => 'Technical documentation, API reference, and integration guides.'],
            ['name' => 'Blog',      'slug' => '/blog',      'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>', 'priority' => 'medium', 'desc' => 'Product updates, tutorials, and industry insights. Key for developer audiences.'],
        ],
        'Education & Training' => [
            ['name' => 'Courses',   'slug' => '/courses',   'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>', 'priority' => 'high',   'desc' => 'Course catalog with descriptions, duration, and enrollment CTAs.'],
            ['name' => 'Enroll',    'slug' => '/enroll',    'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>', 'priority' => 'high',   'desc' => 'Enrollment or registration form. Direct conversion page.'],
            ['name' => 'Faculty',   'slug' => '/faculty',   'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>', 'priority' => 'medium', 'desc' => 'Instructor profiles and credentials. Builds trust with prospective students.'],
            ['name' => 'Blog',      'slug' => '/blog',      'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>', 'priority' => 'low',    'desc' => 'Learning tips, career guidance, and educational resources.'],
        ],
        'Fashion & Apparel' => [
            ['name' => 'Collection','slug' => '/collection','icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>', 'priority' => 'high',   'desc' => 'Current and seasonal collections with editorial-style photography.'],
            ['name' => 'Shop',      'slug' => '/shop',      'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>', 'priority' => 'high',   'desc' => 'E-commerce catalog with filters for size, color, and category.'],
            ['name' => 'Lookbook',  'slug' => '/lookbook',  'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>', 'priority' => 'medium', 'desc' => 'Styled outfit inspirations. Drives aspiration and upselling.'],
            ['name' => 'Journal',   'slug' => '/journal',   'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>', 'priority' => 'low',    'desc' => 'Brand storytelling, style tips, and behind-the-scenes content.'],
        ],
    ];

    $extra = $industry_pages[$industry] ?? [
        ['name' => 'Services',  'slug' => '/services',  'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>', 'priority' => 'high',   'desc' => 'What you offer — describe your core products or services in detail.'],
        ['name' => 'Portfolio', 'slug' => '/portfolio', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>', 'priority' => 'medium', 'desc' => 'Showcase your work, projects, or past clients. Builds credibility.'],
        ['name' => 'Blog',      'slug' => '/blog',      'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>', 'priority' => 'low',    'desc' => 'Content marketing — share insights, news, and tips for SEO and authority.'],
        ['name' => 'FAQ',       'slug' => '/faq',       'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>', 'priority' => 'low',    'desc' => 'Answer common questions to reduce support load and build confidence.'],
    ];

    // Merge base + industry pages (home first, then industry-specific, then contact/privacy last)
    $home    = [$base[0]];
    $about   = [$base[1]];
    $contact = [$base[2], $base[3]];

    return array_merge($home, $about, $extra, $contact);
}



// ── END OF MAIN.PHP ─────────────────────────────────────────────────────────
