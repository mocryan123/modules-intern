<?php
/**
 * Module Name: Brand Asset Engine
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

// Load ticketing system
require_once BNTM_BAE_PATH . 'ticket.php';

// Load PayMongo payment integration
require_once BNTM_BAE_PATH . 'paymongo.php';

// Load Admin Panel
require_once BNTM_BAE_PATH . 'admin.php';


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
        'Brand Asset Engine' => '[bntm_bae_dashboard]',
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
            logo_url VARCHAR(500) NOT NULL DEFAULT '',
            plan VARCHAR(20) NOT NULL DEFAULT 'free',
            tone_statement TEXT NOT NULL DEFAULT '',
            kit_visibility VARCHAR(10) NOT NULL DEFAULT 'private',
            kit_slug VARCHAR(100) UNIQUE NOT NULL DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id)
        ) {$charset};",

        'bae_assets' => "CREATE TABLE {$prefix}bae_assets (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            profile_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            asset_type VARCHAR(50) NOT NULL DEFAULT '',
            asset_name VARCHAR(255) NOT NULL DEFAULT '',
            asset_html LONGTEXT NOT NULL DEFAULT '',
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
add_action('wp_ajax_bae_custom_generate',        'bntm_ajax_bae_custom_generate');
add_action('wp_ajax_nopriv_bae_custom_generate', 'bntm_ajax_bae_custom_generate');
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
add_action('wp_ajax_bae_upload_logo',              'bntm_ajax_bae_upload_logo');
add_action('wp_ajax_nopriv_bae_upload_logo',       'bntm_ajax_bae_upload_logo');
add_action('wp_ajax_bae_export_zip',               'bntm_ajax_bae_export_zip');

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
    ob_start();
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    .bae-wiz-wrap * { box-sizing: border-box; margin: 0; padding: 0; }
    .bae-wiz-wrap {
        font-family: 'Geist', -apple-system, sans-serif;
        background: #09090e;
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
    .bae-wiz-wrap::before {
        content: '';
        position: absolute;
        width: 600px; height: 600px; border-radius: 50%;
        background: radial-gradient(circle, rgba(139,92,246,0.1) 0%, transparent 70%);
        top: -200px; right: -100px; pointer-events: none;
    }
    .bae-wiz-wrap::after {
        content: '';
        position: absolute;
        width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(236,72,153,0.07) 0%, transparent 70%);
        bottom: -100px; left: -100px; pointer-events: none;
    }
    .bae-wiz-progress-bar {
        position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: rgba(255,255,255,0.06);
    }
    .bae-wiz-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #6d28d9, #ec4899);
        border-radius: 0 3px 3px 0;
        transition: width 0.5s cubic-bezier(0.16,1,0.3,1);
        width: 0%;
    }
    .bae-wiz-step-label {
        position: absolute; top: 20px; left: 50%; transform: translateX(-50%);
        font-size: 11px; font-weight: 700; color: #4d4a65;
        text-transform: uppercase; letter-spacing: 0.12em; white-space: nowrap;
    }
    .bae-wiz-skip {
        position: absolute; top: 16px; right: 20px;
        font-size: 12px; color: #4d4a65; text-decoration: none;
        transition: color 0.2s; font-family: 'Geist', sans-serif;
    }
    .bae-wiz-skip:hover { color: #8b88a4; }
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
        border: 1px solid rgba(139,92,246,0.25);
        border-radius: 14px; padding: 16px 20px;
        font-size: 17px; font-family: 'Geist', sans-serif;
        color: #ede9ff; outline: none; text-align: center;
        transition: border-color 0.2s, box-shadow 0.2s;
        margin-bottom: 18px; display: block;
    }
    .bae-wiz-input:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,0.15); }
    .bae-wiz-input::placeholder { color: #4d4a65; }
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
    .bae-wiz-tile:hover { border-color: rgba(139,92,246,0.4); background: rgba(139,92,246,0.08); }
    .bae-wiz-tile.selected { border-color: #8b5cf6; background: rgba(139,92,246,0.15); box-shadow: 0 0 0 3px rgba(139,92,246,0.12); }
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
    .bae-wiz-tagline-opt:hover { border-color: rgba(139,92,246,0.4); color: #ede9ff; }
    .bae-wiz-tagline-opt.selected { border-color: #8b5cf6; color: #ede9ff; background: rgba(139,92,246,0.1); }
    .bae-wiz-tagline-custom {
        width: 100%; background: rgba(255,255,255,0.04);
        border: 1.5px dashed rgba(139,92,246,0.3);
        border-radius: 12px; padding: 12px 16px;
        font-size: 13px; font-family: 'Geist', sans-serif;
        color: #ede9ff; outline: none; transition: border-color 0.2s;
    }
    .bae-wiz-tagline-custom:focus { border-color: #8b5cf6; border-style: solid; }
    .bae-wiz-tagline-custom::placeholder { color: #4d4a65; }
    .bae-wiz-next {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        background: linear-gradient(135deg, #6d28d9, #8b5cf6);
        color: white; border: none; border-radius: 13px;
        padding: 15px 32px; font-size: 15px; font-weight: 700;
        font-family: 'Geist', sans-serif; cursor: pointer;
        transition: all 0.2s; box-shadow: 0 8px 28px rgba(109,40,217,0.4);
        width: 100%; margin-top: 4px;
    }
    .bae-wiz-next:hover { transform: translateY(-2px); box-shadow: 0 12px 36px rgba(109,40,217,0.5); }
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
        border: 3px solid rgba(139,92,246,0.2);
        border-top-color: #8b5cf6; border-radius: 50%;
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
        <div class="bae-wiz-progress-bar"><div class="bae-wiz-progress-fill" id="bae-wiz-progress"></div></div>
        <div class="bae-wiz-step-label" id="bae-wiz-step-label">Step 1 of 5</div>
        <a href="?tab=overview" class="bae-wiz-skip">Skip to full form &rarr;</a>

        <!-- Step 1: Name -->
        <div class="bae-wiz-screen" id="bae-step-1">
            <div class="bae-wiz-question">What's your business name?</div>
            <div class="bae-wiz-hint">This will appear on all your brand assets.</div>
            <input type="text" class="bae-wiz-input" id="bae-wiz-name" placeholder="e.g. Dela Cruz Bakery" autocomplete="off">
            <div class="bae-wiz-error" id="bae-wiz-name-err">Please enter your business name.</div>
            <button class="bae-wiz-next" onclick="baeWizGo(2)">Continue &rarr;</button>
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
                    <div class="bae-wiz-palette-dot" style="width:10px;height:10px;border-radius:50%;background:#6d28d9;animation:bae-wiz-bounce 1.2s ease-in-out infinite;"></div>
                    <div class="bae-wiz-palette-dot" style="width:10px;height:10px;border-radius:50%;background:#8b5cf6;animation:bae-wiz-bounce 1.2s ease-in-out 0.2s infinite;"></div>
                    <div class="bae-wiz-palette-dot" style="width:10px;height:10px;border-radius:50%;background:#ec4899;animation:bae-wiz-bounce 1.2s ease-in-out 0.4s infinite;"></div>
                </div>
                <div style="font-size:13px;color:#4d4a65;" id="bae-wiz-palette-loading-txt">Generating palettes for your brand...</div>
            </div>

            <!-- Palette tiles — filled by JS -->
            <div id="bae-wiz-palette-tiles" style="display:none;width:100%;"></div>

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
        .bae-wiz-palette-card:hover { border-color: rgba(139,92,246,0.4); background: rgba(139,92,246,0.06); }
        .bae-wiz-palette-card.selected { border-color: #8b5cf6; background: rgba(139,92,246,0.12); box-shadow: 0 0 0 3px rgba(139,92,246,0.12); }
        .bae-wiz-palette-swatches { display: flex; gap: 6px; flex-shrink: 0; }
        .bae-wiz-palette-swatch { width: 28px; height: 44px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); }
        .bae-wiz-palette-info { flex: 1; min-width: 0; }
        .bae-wiz-palette-name { font-size: 14px; font-weight: 700; color: #ede9ff; margin-bottom: 3px; }
        .bae-wiz-palette-reason { font-size: 12px; color: #4d4a65; line-height: 1.5; }
        .bae-wiz-palette-check { width: 20px; height: 20px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.15); flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .bae-wiz-palette-card.selected .bae-wiz-palette-check { background: #8b5cf6; border-color: #8b5cf6; }
        .bae-wiz-palette-ai-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 10px; font-weight: 700; color: #a78bfa;
            background: rgba(139,92,246,0.12); border: 1px solid rgba(139,92,246,0.2);
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
                    <div style="width:10px;height:10px;border-radius:50%;background:#6d28d9;animation:bae-wiz-bounce 1.2s ease-in-out infinite;"></div>
                    <div style="width:10px;height:10px;border-radius:50%;background:#8b5cf6;animation:bae-wiz-bounce 1.2s ease-in-out 0.2s infinite;"></div>
                    <div style="width:10px;height:10px;border-radius:50%;background:#ec4899;animation:bae-wiz-bounce 1.2s ease-in-out 0.4s infinite;"></div>
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
            <button class="bae-wiz-next" onclick="baeWizSubmit()">Build My Brand &#9654;</button>
            <button class="bae-wiz-back" onclick="baeWizGo(4)">&#8592; Back</button>
        </div>

        <!-- Generating -->
        <div class="bae-wiz-screen bae-wiz-generating" id="bae-step-gen" style="display:none;">
            <div class="bae-wiz-spinner"></div>
            <div class="bae-wiz-gen-title">Building your brand...</div>
            <div class="bae-wiz-gen-sub" id="bae-wiz-gen-status">Saving your profile</div>
        </div>

        <!-- Celebration screen -->
        <div class="bae-wiz-screen" id="bae-step-done" style="display:none;text-align:center;">
            <div id="bae-cel-icon" style="width:64px;height:64px;background:linear-gradient(135deg,#6d28d9,#ec4899);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <div class="bae-wiz-question" style="margin-bottom:8px;" id="bae-cel-title">Your brand is ready.</div>
            <div class="bae-wiz-hint" id="bae-cel-sub">All assets powered by your profile.<br>Let's see what we built.</div>
            <div id="bae-cel-swatches" style="display:flex;justify-content:center;gap:10px;margin:24px 0;"></div>
            <button class="bae-wiz-next" style="max-width:280px;margin:0 auto;" onclick="window.location.href=window.location.pathname+'?tab=identity'">
                Open My Brand
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <script>
    (function() {
        var ajaxurl = '<?php echo esc_js(admin_url("admin-ajax.php")); ?>';
        var nonce   = '<?php echo esc_js($nonce); ?>';
        var state   = { step:1, name:'', industry:'', primary:'', secondary:'', accent:'', personality:'', tagline:'', email:'', phone:'', website:'' };

        // AI palette fetch state
        var palettePromise   = null;
        var palettesReady    = false;
        var palettesData     = null;

        // AI tagline fetch state
        var taglinePromise   = null;
        var taglinesReady    = false;
        var taglinesData     = null;

        // Static fallback palettes — used if AI fails or times out
        var staticPalettes = [
            { name:'Bold',    primary:'#6d28d9', secondary:'#2d1066', accent:'#ec4899', personality:'Bold, premium, innovative',         reason:'Strong contrast and vibrant energy — great for brands that want to stand out.' },
            { name:'Pro',     primary:'#1d4ed8', secondary:'#1e3a5f', accent:'#38bdf8', personality:'Professional, trustworthy, reliable', reason:'Deep blue tones communicate credibility and calm confidence.' },
            { name:'Fresh',   primary:'#16a34a', secondary:'#14532d', accent:'#86efac', personality:'Natural, fresh, community-focused',   reason:'Green signals growth, health, and authenticity.' },
            { name:'Warm',    primary:'#ea580c', secondary:'#7c2d12', accent:'#fb923c', personality:'Warm, energetic, approachable',       reason:'Energetic oranges feel welcoming and enthusiastic.' },
            { name:'Luxury',  primary:'#b45309', secondary:'#451a03', accent:'#fbbf24', personality:'Luxury, refined, classic',           reason:'Gold and amber tones evoke prestige and timeless quality.' },
            { name:'Playful', primary:'#db2777', secondary:'#831843', accent:'#f9a8d4', personality:'Playful, feminine, creative',        reason:'Pinks and roses feel joyful, creative, and approachable.' },
            { name:'Minimal', primary:'#404040', secondary:'#0a0a0a', accent:'#a3a3a3', personality:'Minimal, modern, clean',             reason:'Monochrome palette — lets your content breathe.' },
            { name:'Tech',    primary:'#0f172a', secondary:'#020617', accent:'#6366f1', personality:'Tech-forward, analytical, precise',  reason:'Dark navy with indigo accents — modern and precise.' },
        ];

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

            var fd = new FormData();
            fd.append('action',   'bae_wizard_palettes');
            fd.append('nonce',    nonce);
            fd.append('name',     name);
            fd.append('industry', industry || '');

            palettePromise = fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (j.success && j.data && j.data.palettes && j.data.palettes.length) {
                        palettesData  = j.data.palettes;
                        palettesReady = true;
                        return j.data.palettes;
                    }
                    // AI failed — log reason and fall back to static
                    if (j.data && j.data.message) console.warn('[BAE Palettes]', j.data.message);
                    palettesData  = staticPalettes;
                    palettesReady = true;
                    return staticPalettes;
                })
                .catch(function() {
                    palettesData  = staticPalettes;
                    palettesReady = true;
                    return staticPalettes;
                });
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

            document.getElementById('bae-wiz-gen-status').textContent = 'Saving your brand profile...';

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
                    document.getElementById('bae-wiz-gen-status').textContent = 'Brand created!';
                    var swatchContainer = document.getElementById('bae-cel-swatches');
                    if (swatchContainer && state.primary) {
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
                        gsap.to('#bae-cel-icon', {boxShadow:'0 0 36px rgba(139,92,246,0.6)',duration:1.4,repeat:-1,yoyo:true,ease:'sine.inOut',delay:0.8});
                    }
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
                            genScreen.innerHTML = '<div class="bae-wiz-spinner"></div><div class="bae-wiz-gen-title">Building your brand...</div><div class="bae-wiz-gen-sub" id="bae-wiz-gen-status">Saving your profile</div>';
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
                        genScreen.innerHTML = '<div class="bae-wiz-spinner"></div><div class="bae-wiz-gen-title">Building your brand...</div><div class="bae-wiz-gen-sub" id="bae-wiz-gen-status">Saving your profile</div>';
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
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to access the Brand Asset Engine.</div>';
    }

    $user_id    = get_current_user_id();
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

    // Use ticket as identity — read from cookie
    $ticket  = '';
    $profile = null;
    if ( !empty($_COOKIE['bae_ticket']) ) {
        $raw = strtoupper( sanitize_text_field( $_COOKIE['bae_ticket'] ) );
        if ( preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw) ) {
            $ticket  = $raw;
            global $wpdb;
            $wpdb->hide_errors();
            $profile = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s", $ticket ),
                ARRAY_A
            );
            $wpdb->show_errors();

            // Special: Auto-create demo profile for BAE-2525-2525
            if ( !$profile && $ticket === 'BAE-2525-2525' ) {
                $demo_profile = [
                    'rand_id' => bntm_rand_id(),
                    'user_id' => $user_id,
                    'ticket' => $ticket,
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
                    'plan' => 'pro',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ];
                $wpdb->insert( $wpdb->prefix . 'bae_profiles', $demo_profile );
                $profile = $demo_profile;
                $profile['id'] = $wpdb->insert_id;
            }
        }
    }

    // No ticket cookie → show ticket screen (never fall through to wizard without ticket)
    if ( empty( $ticket ) ) {
        return bntm_bae_ticket_screen();
    }

    // Has ticket but no business name yet → show wizard
    if ( ( empty($profile) || empty($profile['business_name']) ) && !isset($_GET['tab']) ) {
        return bae_wizard_shortcode($user_id);
    }

    ob_start();
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';</script>

    <script>
    window.BAE_PM = {
        ajax_url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
        nonce:    '<?php echo esc_js(wp_create_nonce('bae_pm_checkout')); ?>'
    };
    </script>
    <div class="bae-wrap" id="bae-wrap" style="opacity:0;transition:opacity 0.25s ease;">

        <!-- Page loader -->
        <div id="bae-page-loader" style="position:absolute;inset:0;z-index:9999;background:var(--bg);display:flex;align-items:center;justify-content:center;border-radius:20px;">
            <div style="display:flex;flex-direction:column;align-items:center;gap:16px;">
                <div style="width:36px;height:36px;border:3px solid rgba(139,92,246,0.2);border-top-color:#8b5cf6;border-radius:50%;animation:bae-spin 0.8s linear infinite;"></div>
                <div style="font-size:12px;color:var(--text-3);font-family:'Geist',sans-serif;letter-spacing:0.05em;">Loading...</div>
            </div>
        </div>
        <style>
        @keyframes bae-spin { to { transform: rotate(360deg); } }
        </style>

        <!-- Header -->
        <div class="bae-header">
            <div class="bae-logo-mark">B</div>
            <div class="bae-logo-text">Brand<span>Asset</span></div>
            <div class="bae-header-right">
                <button class="bae-theme-btn" id="bae-theme-btn" onclick="baeToggleTheme()">
                    <span id="bae-theme-icon">
                        <svg id="bae-icon-sun" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                        <svg id="bae-icon-moon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    </span>
                    <span id="bae-theme-label">Light</span>
                    <div class="bae-toggle-track on" id="bae-toggle-track">
                        <div class="bae-toggle-thumb"></div>
                    </div>
                </button>
            </div>
        </div>

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
                        Unlock the full Brand Asset Engine
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
                                Multiple brand profiles
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
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Everything in Starter
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Unlimited Custom AI Generator
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Multiple brand profiles
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Priority AI generation
                            </li>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
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

        <!-- Tab Nav -->
        <div class="bae-stepper">
            <?php
            $tabs = [
                'overview'   => ['label' => 'Brand Profile',    'short' => 'Profile'],
                'identity'   => ['label' => 'Identity Board',   'short' => 'Identity'],
                'assets'     => ['label' => 'Asset Generator',  'short' => 'Assets'],
                'kit'        => ['label' => 'Brand Kit',        'short' => 'Kit'],
                'startup'    => ['label' => 'Launch Toolkit',   'short' => 'Launch'],
                'brand_book' => ['label' => 'Brand Book',       'short' => 'Book'],
                'settings'   => ['label' => 'Settings',         'short' => 'Settings'],
            ];

            $user_plan   = bae_get_user_plan($user_id, $profile);
            $is_free     = $user_plan === 'free';
            $has_profile = !empty($profile) && !empty($profile['business_name']);

            // Determine completion per step
            $completed = [
                'overview'   => $has_profile,
                'identity'   => $has_profile && !empty($profile['primary_color']) && $profile['primary_color'] !== '#1a1a2e',
                'assets'     => $has_profile && ($stats['asset_count'] ?? 0) > 0,
                'kit'        => false,
                'startup'    => false,
                'brand_book' => false,
                'settings'   => false,
            ];

            // Get asset count for this user
            global $wpdb;
            $ticket_for_step = $GLOBALS['bae_current_ticket'] ?? '';
            if ( $ticket_for_step ) {
                $asset_count_step = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}bae_assets WHERE ticket = %s AND is_generated = 1",
                    $ticket_for_step
                ));
                $completed['assets'] = $has_profile && $asset_count_step > 0;
            }

            $needs_profile = ['identity','assets','kit','startup','brand_book'];
            $pro_tabs      = [];
            $base_url      = strtok($_SERVER['REQUEST_URI'], '?');

            $tab_keys = array_keys($tabs);
            $total    = count($tab_keys);

            foreach ($tab_keys as $i => $slug):
                $info          = $tabs[$slug];
                $is_active     = $active_tab === $slug;
                $is_done       = $completed[$slug] ?? false;
                $locked_profile= in_array($slug, $needs_profile) && !$has_profile;
                $locked_pro    = in_array($slug, $pro_tabs) && $is_free;
                $is_locked     = $locked_profile || $locked_pro;
                $is_last       = $i === $total - 1;
                $step_num      = $i + 1;
            ?>
                <div class="bae-step-wrap <?php echo $is_last ? 'bae-step-last' : ''; ?>">
                    <?php if ($is_locked): ?>
                    <div class="bae-step bae-step-locked" title="<?php echo $locked_pro ? 'Upgrade to unlock' : 'Complete Brand Profile first'; ?>">
                        <div class="bae-step-node">
                            <?php if ($locked_pro): ?>
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
                            <?php else: ?>
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="bae-step-label"><?php echo $info['short']; ?></div>
                        <?php if ($locked_pro): ?><span class="bae-tab-pro-badge">PRO</span><?php endif; ?>
                    </div>
                    <?php elseif ($is_done && !$is_active): ?>
                    <a href="<?php echo $base_url; ?>?tab=<?php echo $slug; ?>" class="bae-step bae-step-done">
                        <div class="bae-step-node">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                        </div>
                        <div class="bae-step-label"><?php echo $info['short']; ?></div>
                    </a>
                    <?php elseif ($is_active): ?>
                    <div class="bae-step bae-step-active">
                        <div class="bae-step-node">
                            <span><?php echo $step_num; ?></span>
                        </div>
                        <div class="bae-step-label"><?php echo $info['label']; ?></div>
                    </div>
                    <?php else: ?>
                    <a href="<?php echo $base_url; ?>?tab=<?php echo $slug; ?>" class="bae-step">
                        <div class="bae-step-node">
                            <span><?php echo $step_num; ?></span>
                        </div>
                        <div class="bae-step-label"><?php echo $info['short']; ?></div>
                    </a>
                    <?php endif; ?>

                    <?php if (!$is_last): ?>
                    <div class="bae-step-line <?php echo $is_done ? 'bae-step-line-done' : ''; ?>"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tab Content -->
        <div class="bae-tab-content">
            <?php
            $profile_tabs = ['identity', 'assets', 'kit', 'startup', 'brand_book'];
            if (in_array($active_tab, $profile_tabs) && !$has_profile) $active_tab = 'overview';

            if ($active_tab === 'overview')        echo bae_overview_tab($user_id, $profile);
            elseif ($active_tab === 'identity')    echo bae_identity_tab($user_id, $profile);
            elseif ($active_tab === 'assets')      echo bae_assets_tab($user_id, $profile);
            elseif ($active_tab === 'kit')         echo bae_kit_tab($user_id, $profile);
            elseif ($active_tab === 'startup')     echo bae_startup_tab($user_id, $profile);
            elseif ($active_tab === 'brand_book')  echo bae_brand_book_tab($user_id, $profile);
            elseif ($active_tab === 'settings')    echo bae_settings_tab($user_id, $profile);
            ?>
        </div>

    </div>

    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* ═══════════════════════════════════════════════════
       BAE — DESIGN SYSTEM v2
       Purple/Pink · Dark/Light · Minimal Premium
    ═══════════════════════════════════════════════════ */

    /* Tokens */
    .bae-wrap {
        --brand:       #8b5cf6;
        --brand-deep:  #6d28d9;
        --brand-soft:  #a78bfa;
        --brand-glow:  #c4b5fd;
        --pink:        #ec4899;
        --pink-soft:   #f472b6;
        --ease-out:    cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Dark theme (default) */
    .bae-wrap {
        --bg:         #0a0a0f;
        --bg-2:       #111118;
        --bg-3:       #17171f;
        --surface:    #1c1c26;
        --surface-2:  #22222e;
        --border:     rgba(255,255,255,0.07);
        --border-2:   rgba(255,255,255,0.12);
        --text:       #f0eeff;
        --text-2:     #9390a8;
        --text-3:     #5c5972;
        --input-bg:   #17171f;
        --input-bd:   rgba(139,92,246,0.25);
        --shadow:     0 1px 3px rgba(0,0,0,0.5), 0 8px 24px rgba(0,0,0,0.4);
    }

    /* Light theme */
    .bae-wrap.bae-light {
        --bg:         #fafafa;
        --bg-2:       #f4f3ff;
        --bg-3:       #ede9fe;
        --surface:    #ffffff;
        --surface-2:  #f8f7ff;
        --border:     rgba(0,0,0,0.07);
        --border-2:   rgba(0,0,0,0.12);
        --text:       #1a1730;
        --text-2:     #6b6880;
        --text-3:     #a09db8;
        --input-bg:   #ffffff;
        --input-bd:   rgba(139,92,246,0.3);
        --shadow:     0 1px 3px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.08);
    }

    /* Base reset */
    .bae-wrap *, .bae-wrap *::before, .bae-wrap *::after { box-sizing: border-box; margin: 0; padding: 0; }

    .bae-wrap {
        font-family: 'Geist', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--bg);
        color: var(--text);
        border-radius: 20px;
        overflow: hidden;
        transition: background 0.5s, color 0.5s;
        position: relative;
    }

    /* Glow orb bg */
    .bae-wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 60% 40% at 80% 10%, rgba(139,92,246,0.1) 0%, transparent 60%),
            radial-gradient(ellipse 40% 30% at 10% 90%, rgba(236,72,153,0.06) 0%, transparent 50%);
        pointer-events: none;
        z-index: 0;
        transition: background 0.5s;
    }

    .bae-wrap > * { position: relative; z-index: 1; }

    /* ── HEADER BAR ── */
    .bae-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 18px 28px;
        border-bottom: 1px solid var(--border);
        background: var(--bg-2);
        transition: background 0.5s, border-color 0.5s;
        position: sticky; top: 0; z-index: 100;
    }
    .bae-logo-mark {
        width: 34px; height: 34px;
        background: linear-gradient(135deg, var(--brand-deep), var(--pink));
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Instrument Serif', serif;
        font-size: 14px; font-style: italic; color: white; font-weight: 400;
        flex-shrink: 0;
    }
    .bae-logo-text {
        font-family: 'Instrument Serif', serif;
        font-size: 18px; font-style: italic;
        color: var(--text); transition: color 0.5s;
    }
    .bae-logo-text span { color: var(--brand-soft); }
    .bae-header-right { margin-left: auto; display: flex; align-items: center; gap: 12px; }

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

    .bae-toggle-track {
        width: 34px; height: 18px;
        background: var(--surface-2);
        border: 1px solid var(--border-2);
        border-radius: 999px; position: relative;
        transition: background 0.3s, border-color 0.5s;
    }
    .bae-wrap .bae-toggle-track.on { background: var(--brand); border-color: var(--brand); }
    .bae-toggle-thumb {
        position: absolute; top: 2px; left: 2px;
        width: 12px; height: 12px; background: white;
        border-radius: 50%; transition: transform 0.35s var(--ease-out);
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }
    .bae-wrap .bae-toggle-track.on .bae-toggle-thumb { transform: translateX(16px); }

    /* ── STEPPER ── */
    .bae-stepper {
        display: flex;
        align-items: center;
        padding: 0 24px;
        background: var(--bg-2);
        border-bottom: 1px solid var(--border);
        overflow-x: auto;
        scrollbar-width: none;
        transition: background 0.5s, border-color 0.5s;
        min-height: 56px;
    }
    .bae-stepper::-webkit-scrollbar { display: none; }

    .bae-step-wrap {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }
    .bae-step-last { flex-shrink: 0; }

    .bae-step {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 4px;
        text-decoration: none;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
        border: none; background: none;
        font-family: 'Geist', sans-serif;
    }

    .bae-step-node {
        width: 24px; height: 24px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700;
        flex-shrink: 0;
        transition: all .2s;
        border: 1.5px solid var(--border-2);
        color: var(--text-3);
        background: var(--bg-3);
    }

    .bae-step-label {
        font-size: 12px; font-weight: 500;
        color: var(--text-3);
        transition: color .15s;
    }

    /* Default (unlocked, not done, not active) */
    .bae-step:hover .bae-step-node { border-color: var(--brand-s); color: var(--brand-s); }
    .bae-step:hover .bae-step-label { color: var(--text-2); }

    /* Active step */
    .bae-step-active .bae-step-node {
        background: var(--brand);
        border-color: var(--brand);
        color: white;
        box-shadow: 0 0 0 3px rgba(139,92,246,.2);
    }
    .bae-step-active .bae-step-label {
        color: var(--text);
        font-weight: 700;
    }

    /* Done step */
    .bae-step-done .bae-step-node {
        background: rgba(52,211,153,.12);
        border-color: rgba(52,211,153,.4);
        color: #34d399;
    }
    .bae-step-done .bae-step-label { color: var(--text-2); }
    .bae-step-done:hover .bae-step-node { background: rgba(52,211,153,.2); border-color: #34d399; }

    /* Locked step */
    .bae-step-locked {
        opacity: .50;
        cursor: not-allowed;
        pointer-events: none;
    }
    .bae-step-locked .bae-step-node {
        border-style: dashed;
    }

    /* Connector line */
    .bae-step-line {
        width: 28px; height: 1.5px;
        background: var(--border-2);
        flex-shrink: 0;
        margin: 0 4px;
        transition: background .3s;
    }
    .bae-step-line-done { background: rgba(52,211,153,.4); }

    /* ── PRO BADGE on steps ── */
    .bae-tab-pro-badge {
        font-size: 9px; font-weight: 800;
        letter-spacing: 0.06em;
        padding: 2px 6px; border-radius: 5px;
        background: linear-gradient(135deg, #6d28d9, #ec4899);
        color: white;
        animation: bae-pro-pulse 2.5s ease-in-out infinite;
        box-shadow: 0 0 8px rgba(139,92,246,0.5);
    }
    @keyframes bae-pro-pulse {
        0%, 100% { box-shadow: 0 0 6px rgba(139,92,246,0.4); }
        50%       { box-shadow: 0 0 14px rgba(236,72,153,0.7); }
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
        box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(139,92,246,0.1);
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
        border-color: #8b5cf6;
        background: linear-gradient(145deg, rgba(109,40,217,0.08), rgba(139,92,246,0.04));
    }
    .bae-pricing-recommended-badge {
        position: absolute; top: -11px; left: 50%; transform: translateX(-50%);
        background: linear-gradient(135deg, #6d28d9, #8b5cf6);
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
        background: linear-gradient(135deg, #6d28d9, #8b5cf6);
        color: white; box-shadow: 0 6px 20px rgba(109,40,217,0.35);
    }
    .bae-pricing-cta.primary:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(109,40,217,0.45); }
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
        background: linear-gradient(135deg, #6d28d9, #8b5cf6);
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
    .bae-tab-content { padding: 28px; background: var(--bg); transition: background 0.5s; }

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
    .bae-badge-purple { background: rgba(139,92,246,0.12); color: var(--brand-soft); border: 1px solid rgba(139,92,246,0.2); }

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
        box-shadow: 0 4px 14px rgba(109,40,217,0.3);
    }
    .bae-btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(109,40,217,0.4);
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
        box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
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
    .bae-notice-info    { background: rgba(139,92,246,0.1); color: var(--brand-soft); border: 1px solid rgba(139,92,246,0.2); }

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
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 18px; overflow: hidden;
        transition: background 0.5s, border-color 0.5s, transform 0.2s, box-shadow 0.2s;
    }
    .bae-asset-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); border-color: var(--border-2); }

    .bae-asset-preview {
        height: 160px; position: relative; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(135deg, rgba(139,92,246,0.06), rgba(236,72,153,0.04));
        transition: border-color 0.5s;
    }
    .bae-asset-preview-inner {
        transform: scale(0.4); transform-origin: center center;
        width: 250%; pointer-events: none;
        overflow: hidden; isolation: isolate;
    }
    .bae-asset-preview-empty {
        height: 160px; display: flex; align-items: center; justify-content: center;
        font-size: 36px; background: var(--bg-3);
        border-bottom: 1px solid var(--border);
        transition: background 0.5s;
    }

    .bae-asset-info { padding: 14px 16px; }
    .bae-asset-name { font-size: 13px; font-weight: 600; color: var(--text); transition: color 0.5s; }
    .bae-asset-meta { font-size: 12px; color: var(--text-3); margin-top: 3px; transition: color 0.5s; }
    .bae-asset-actions {
        display: flex; gap: 8px;
        padding: 12px 16px;
        border-top: 1px solid var(--border);
        transition: border-color 0.5s;
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
        background: rgba(139,92,246,0.1); color: var(--brand-soft);
        border: 1px solid rgba(139,92,246,0.2);
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

    .bae-wrap ::-webkit-scrollbar { width: 5px; }
    .bae-wrap ::-webkit-scrollbar-track { background: transparent; }
    .bae-wrap ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 999px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
        .bae-stats-row { grid-template-columns: 1fr 1fr; }
        .bae-assets-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 680px) {
        .bae-stepper { padding: 0 16px; }
        .bae-tab-content { padding: 18px; }
        .bae-form-grid { grid-template-columns: 1fr; }
        .bae-form-grid.three { grid-template-columns: 1fr; }
        .bae-stats-row { grid-template-columns: 1fr 1fr; }
        .bae-assets-grid { grid-template-columns: 1fr; }
        .bae-header { padding: 14px 18px; }
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
                        alert(res.data && res.data.message ? res.data.message : 'Something went wrong. Please try again.');
                        btns.forEach(function(b){ b.disabled = false; });
                        if (clickedBtn) clickedBtn.textContent = origText;
                    }
                })
                .catch(function() {
                    alert('Network error. Please try again.');
                    btns.forEach(function(b){ b.disabled = false; });
                    if (clickedBtn) clickedBtn.textContent = origText;
                });
        };

        // After PayMongo redirect back — refresh plan badge without full reload
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
    var baeIsDark = (localStorage.getItem('bae_theme') !== 'light');

    function baeApplyTheme(dark, animate) {
        var wrap  = document.getElementById('bae-wrap');
        var track = document.getElementById('bae-toggle-track');
        var label = document.getElementById('bae-theme-label');
        if (!wrap) return;

        if (dark) {
            wrap.classList.remove('bae-light');
            if (track) track.classList.add('on');
            if (label) label.textContent = 'Light';
            var sun = document.getElementById('bae-icon-sun');
            var moon = document.getElementById('bae-icon-moon');
            if (sun)  sun.style.display  = '';
            if (moon) moon.style.display = 'none';
        } else {
            wrap.classList.add('bae-light');
            if (track) track.classList.remove('on');
            if (label) label.textContent = 'Dark';
            var sun2 = document.getElementById('bae-icon-sun');
            var moon2 = document.getElementById('bae-icon-moon');
            if (sun2)  sun2.style.display  = 'none';
            if (moon2) moon2.style.display = '';
        }

        if (animate && window.gsap) {
            gsap.to('.bae-toggle-thumb', { x: dark ? 16 : 0, duration: 0.4, ease: 'back.out(1.8)' });
        } else if (window.gsap) {
            // Instant set without animation on page load
            gsap.set('.bae-toggle-thumb', { x: dark ? 16 : 0 });
        }
    }

    function baeToggleTheme() {
        baeIsDark = !baeIsDark;
        localStorage.setItem('bae_theme', baeIsDark ? 'dark' : 'light');

        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:999999;pointer-events:none;background:' + (baeIsDark ? '#0a0a0f' : '#fafafa') + ';opacity:0;';
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

        // Reveal the wrap — hide loader, fade in content
        var wrap   = document.getElementById('bae-wrap');
        var loader = document.getElementById('bae-page-loader');

        function revealWrap() {
            if (loader) {
                loader.style.opacity = '0';
                loader.style.transition = 'opacity 0.2s ease';
                setTimeout(function() {
                    if (loader) loader.style.display = 'none';
                }, 200);
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

        if (!window.gsap) return;
        // Hover micro-interactions only — entry animations removed,
        // the wrap opacity fade-in handles the reveal cleanly
        document.querySelectorAll('.bae-stat-card, .bae-asset-card, .bae-card').forEach(function(el) {
            el.addEventListener('mouseenter', function() { gsap.to(el, { y: -3, duration: 0.22, ease: 'power2.out' }); });
            el.addEventListener('mouseleave', function() { gsap.to(el, { y: 0, duration: 0.22, ease: 'power2.out' }); });
        });
        document.querySelectorAll('.bae-btn-primary, .bae-btn-danger').forEach(function(btn) {
            btn.addEventListener('mouseenter', function() { gsap.to(btn, { y: -2, duration: 0.18, ease: 'power2.out' }); });
            btn.addEventListener('mouseleave', function() { gsap.to(btn, { y: 0, duration: 0.18, ease: 'power2.out' }); });
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

function bae_overview_tab($user_id, $profile) {
    $nonce  = wp_create_nonce('bae_save_profile');
    $p      = $profile ?: [];
    $is_new = empty($profile);

    // CHANGED: Use cached asset count only — no repeated DB hits on re-render
    $assets_count = bae_count_assets($user_id);
    $kit_status   = !empty($p['kit_visibility']) ? $p['kit_visibility'] : 'private';

    ob_start();

    // PayMongo payment success flash banner
    $pm_success = get_transient('bae_pm_success_' . $user_id);
    if ($pm_success) {
        delete_transient('bae_pm_success_' . $user_id);
        $plan_label = $pm_success === 'pro' ? 'Pro ✦' : 'Starter';
        echo '<div style="background:linear-gradient(135deg,#065f46,#047857);color:#fff;padding:16px 20px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">'
           . '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6ee7b7" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>'
           . '<div><strong>Payment successful!</strong> You\'re now on the <strong>' . esc_html($plan_label) . '</strong> plan. All features are unlocked. 🎉</div>'
           . '</div>';
    }

    $user_plan = bae_get_user_plan($user_id, $profile);
    $is_free   = $user_plan === 'free';
    ?>
    <!-- Stats Row -->
    <div class="bae-stats-row">
        <div class="bae-stat-card">
            <div class="bae-stat-label">Profile Status</div>
            <div class="bae-stat-value" style="font-size:18px;margin-top:10px;">
                <?php if (!$is_new): ?>
                    <span class="bae-badge bae-badge-green">Complete</span>
                <?php else: ?>
                    <span class="bae-badge bae-badge-yellow">Incomplete</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="bae-stat-card">
            <div class="bae-stat-label">Assets Generated</div>
            <div class="bae-stat-value"><?php echo $assets_count; ?></div>
            <div class="bae-stat-sub">of 7 available</div>
        </div>
        <div class="bae-stat-card" style="cursor:<?php echo $is_free ? 'pointer' : 'default'; ?>;" <?php if ($is_free) echo 'onclick="baePricingOpen()"'; ?>>
            <div class="bae-stat-label">Current Plan</div>
            <div class="bae-stat-value" style="font-size:18px;margin-top:10px;">
                <?php if ($user_plan === 'pro'): ?>
                    <span class="bae-badge bae-badge-purple">Pro ✦</span>
                <?php elseif ($user_plan === 'starter'): ?>
                    <span class="bae-badge bae-badge-green">Starter</span>
                <?php else: ?>
                    <span class="bae-badge bae-badge-gray">Free</span>
                <?php endif; ?>
            </div>
            <?php if ($is_free): ?>
            <div class="bae-stat-sub" style="color:var(--brand-soft);font-size:11px;margin-top:4px;">Upgrade ✦</div>
            <?php endif; ?>
        </div>
        <div class="bae-stat-card">
            <div class="bae-stat-label">Last Updated</div>
            <div class="bae-stat-value" style="font-size:15px;margin-top:10px;">
                <?php echo !empty($p['updated_at']) ? date('M d, Y', strtotime($p['updated_at'])) : '—'; ?>
            </div>
        </div>
    </div>

    <?php
    // Completeness indicator — check which fields are filled
    $fields_done = [];
    $fields_todo = [];

    $check = [
        'business_name' => 'Business name',
        'industry'      => 'Industry',
        'tagline'       => 'Tagline',
        'email'         => 'Email',
        'phone'         => 'Phone',
        'primary_color' => 'Brand colors',
        'logo_style'    => 'Logo style',
        'font_heading'  => 'Typography',
    ];
    foreach ($check as $field => $label) {
        if (!empty($p[$field])) {
            $fields_done[] = $label;
        } else {
            $fields_todo[] = $label;
        }
    }
    $total = count($check);
    $done  = count($fields_done);
    $pct   = $total > 0 ? round(($done / $total) * 100) : 0;
    ?>
    <div class="bae-completeness-bar">
        <div class="bae-completeness-top">
            <span class="bae-completeness-label">Profile Completeness</span>
            <span class="bae-completeness-pct"><?php echo $pct; ?>%</span>
        </div>
        <div class="bae-completeness-track">
            <div class="bae-completeness-fill" style="width:<?php echo $pct; ?>%;"></div>
        </div>
        <div class="bae-completeness-items">
            <?php foreach ($fields_done as $f): ?>
            <span class="bae-completeness-item done">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                <?php echo esc_html($f); ?>
            </span>
            <?php endforeach; ?>
            <?php foreach ($fields_todo as $f): ?>
            <span class="bae-completeness-item todo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <?php echo esc_html($f); ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Profile Form -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Brand Profile</div>
                <div class="bae-card-desc">Define your business identity — this drives every asset generated.</div>
            </div>
        </div>

        <form id="bae-profile-form">

            <!-- Section: Business Info -->
            <div style="margin-bottom:28px;">
                <div class="bae-section-label">Business Information</div>
                <div class="bae-form-grid">
                    <div class="bae-form-group">
                        <label>Business Name *</label>
                        <input type="text" name="business_name" placeholder="e.g. Dela Cruz Bakery"
                               value="<?php echo esc_attr($p['business_name'] ?? ''); ?>" required>
                    </div>
                    <div class="bae-form-group">
                        <label>Industry / Sector *</label>
                        <select name="industry">
                            <?php
                            $industries = ['Food & Beverage','Retail & Commerce','Fashion & Apparel','Health & Wellness',
                                          'Beauty & Cosmetics','Technology','Professional Services','Education & Training',
                                          'Home & Lifestyle','Agriculture','Construction & Trades','Creative & Media','Other'];
                            $sel = $p['industry'] ?? '';
                            foreach ($industries as $ind):
                            ?>
                                <option value="<?php echo $ind; ?>" <?php selected($sel, $ind); ?>><?php echo $ind; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bae-form-group">
                        <label>Tagline</label>
                        <input type="text" name="tagline" id="bae-tagline-input" placeholder="e.g. Fresh baked with love, every day"
                               value="<?php echo esc_attr($p['tagline'] ?? ''); ?>">
                        <div style="display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap;">
                            <small style="margin:0;">Short phrase that sums up your brand promise.</small>
                            <button type="button" id="bae-ai-tagline-btn" style="display:inline-flex;align-items:center;gap:5px;background:none;border:1px solid var(--border-2);border-radius:7px;padding:3px 10px;font-size:11px;font-weight:600;color:var(--brand-soft);cursor:pointer;font-family:'Geist',sans-serif;transition:all .2s;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                                Suggest with AI
                            </button>
                        </div>
                        <!-- AI tagline suggestions dropdown -->
                        <div id="bae-ai-tagline-panel" style="display:none;margin-top:10px;padding:12px;background:var(--bg-3);border-radius:12px;border:1px solid var(--border-2);">
                            <div id="bae-ai-tagline-results" style="display:flex;flex-direction:column;gap:6px;">
                                <div style="font-size:13px;color:var(--text-3);text-align:center;padding:8px;">Writing taglines...</div>
                            </div>
                        </div>
                    </div>
                    <div class="bae-form-group">
                        <label>Target Audience</label>
                        <input type="text" name="personality" placeholder="e.g. Families in Quezon City who value quality"
                               value="<?php echo esc_attr($p['personality'] ?? ''); ?>">
                        <small>Describe your ideal customer in one sentence.</small>
                    </div>
                </div>
            </div>

            <div class="bae-divider"></div>

            <!-- CHANGED: NEW Contact Information section — used in all generated assets -->
            <div style="margin-bottom:28px;">
                <div class="bae-section-label">Contact Information</div>
                <small style="display:block;color:var(--text-3);font-size:12px;margin-bottom:16px;">
                    Used in business cards, letterheads, email signatures, and other assets — no more placeholder text.
                </small>
                <div class="bae-form-grid">
                    <div class="bae-form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="hello@yourbusiness.com"
                               value="<?php echo esc_attr($p['email'] ?? ''); ?>">
                    </div>
                    <div class="bae-form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" placeholder="+63 900 000 0000"
                               value="<?php echo esc_attr($p['phone'] ?? ''); ?>">
                    </div>
                    <div class="bae-form-group">
                        <label>Website</label>
                        <input type="url" name="website" placeholder="https://yourbusiness.com"
                               value="<?php echo esc_attr($p['website'] ?? ''); ?>">
                    </div>
                    <div class="bae-form-group">
                        <label>Business Address</label>
                        <input type="text" name="address" placeholder="123 Main St, Quezon City"
                               value="<?php echo esc_attr($p['address'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="bae-divider"></div>

            <!-- Section: Colors -->
            <div style="margin-bottom:28px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div class="bae-section-label" style="margin-bottom:0;">Brand Colors</div>
                    <button type="button" id="bae-ai-color-btn" class="bae-btn bae-btn-outline bae-btn-sm" style="display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                        Suggest with AI
                    </button>
                </div>
                <!-- AI color suggestions panel -->
                <div id="bae-ai-color-panel" style="display:none;margin-bottom:16px;padding:16px;background:var(--bg-3);border-radius:14px;border:1px solid var(--border-2);">
                    <div style="font-size:11px;font-weight:700;color:var(--brand-soft);text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                        AI Palette Suggestions
                    </div>
                    <div id="bae-ai-color-results" style="display:flex;flex-direction:column;gap:8px;">
                        <div style="font-size:13px;color:var(--text-3);text-align:center;padding:16px;">Generating palettes...</div>
                    </div>
                </div>
                <div class="bae-form-grid three">
                    <div class="bae-form-group">
                        <label>Primary Color</label>
                        <div class="bae-color-row bae-color-pair">
                            <input type="color" name="primary_color_picker" value="<?php echo esc_attr(bae_safe_color($p['primary_color'] ?? '', '#1a1a2e')); ?>">
                            <input type="text" name="primary_color" id="bae-primary-color" value="<?php echo esc_attr(bae_safe_color($p['primary_color'] ?? '', '#1a1a2e')); ?>" maxlength="7" placeholder="#1a1a2e">
                        </div>
                        <small>Main brand color — headers, buttons, accents.</small>
                    </div>
                    <div class="bae-form-group">
                        <label>Secondary Color</label>
                        <div class="bae-color-row bae-color-pair">
                            <input type="color" name="secondary_color_picker" value="<?php echo esc_attr(bae_safe_color($p['secondary_color'] ?? '', '#16213e')); ?>">
                            <input type="text" name="secondary_color" id="bae-secondary-color" value="<?php echo esc_attr(bae_safe_color($p['secondary_color'] ?? '', '#16213e')); ?>" maxlength="7" placeholder="#16213e">
                        </div>
                        <small>Supports primary — backgrounds, cards.</small>
                    </div>
                    <div class="bae-form-group">
                        <label>Accent Color</label>
                        <div class="bae-color-row bae-color-pair">
                            <input type="color" name="accent_color_picker" value="<?php echo esc_attr(bae_safe_color($p['accent_color'] ?? '', '#e94560')); ?>">
                            <input type="text" name="accent_color" id="bae-accent-color" value="<?php echo esc_attr(bae_safe_color($p['accent_color'] ?? '', '#e94560')); ?>" maxlength="7" placeholder="#e94560">
                        </div>
                        <small>Highlight color — badges, links, CTAs.</small>
                    </div>
                </div>
            </div>

            <div class="bae-divider"></div>

            <!-- Section: Typography -->
            <div style="margin-bottom:28px;">
                <div class="bae-section-label">Typography</div>
                <div class="bae-form-grid">
                    <div class="bae-form-group">
                        <label>Heading Font</label>
                        <select name="font_heading">
                            <?php
                            $fonts = ['Inter','Playfair Display','Montserrat','Raleway','Oswald','Lora','Poppins','Nunito','Roboto Slab','Merriweather'];
                            $sfh = $p['font_heading'] ?? 'Inter';
                            foreach ($fonts as $f):
                            ?>
                                <option value="<?php echo $f; ?>" <?php selected($sfh, $f); ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Used for titles and headlines in your assets.</small>
                    </div>
                    <div class="bae-form-group">
                        <label>Body Font</label>
                        <select name="font_body">
                            <?php
                            $sfb = $p['font_body'] ?? 'Inter';
                            foreach ($fonts as $f):
                            ?>
                                <option value="<?php echo $f; ?>" <?php selected($sfb, $f); ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Used for body text, descriptions, addresses.</small>
                    </div>
                </div>
            </div>

            <div class="bae-divider"></div>

            <!-- Section: Logo -->
            <div style="margin-bottom:28px;">
                <div class="bae-section-label">Logo</div>

                <!-- Method A: Upload -->
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <div style="width:22px;height:22px;border-radius:6px;background:linear-gradient(135deg,var(--brand-deep),var(--brand));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:11px;font-weight:800;color:white;">A</span>
                    </div>
                    <div style="font-size:12px;font-weight:700;color:var(--text-2);">Upload Your Own Logo <span style="font-size:11px;font-weight:400;color:var(--text-3);">— Use your actual logo file (PNG, SVG, JPG)</span></div>
                </div>

                <!-- Logo Upload -->
                <div style="margin-bottom:20px;padding:20px;background:var(--bg-3);border:2px dashed var(--border-2);border-radius:14px;transition:border-color .2s;" id="bae-logo-upload-area">
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                        <div id="bae-logo-preview-wrap" style="width:80px;height:80px;border-radius:12px;background:var(--surface);border:1px solid var(--border-2);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
                            <?php if (!empty($p['logo_url'])): ?>
                                <img src="<?php echo esc_url($p['logo_url']); ?>" style="max-width:100%;max-height:100%;object-fit:contain;" id="bae-logo-preview-img">
                            <?php else: ?>
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:200px;">
                            <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:4px;">Upload Your Logo</div>
                            <div style="font-size:12px;color:var(--text-3);margin-bottom:12px;">PNG, SVG, or JPG. Transparent PNG recommended for best results.</div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <label style="display:inline-flex;align-items:center;gap:6px;background:var(--surface);border:1px solid var(--border-2);border-radius:9px;padding:8px 14px;font-size:12px;font-weight:600;color:var(--text-2);cursor:pointer;transition:all .2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    Choose File
                                    <input type="file" id="bae-logo-file-input" accept="image/*" style="display:none;">
                                </label>
                                <?php if (!empty($p['logo_url'])): ?>
                                <button type="button" id="bae-logo-remove-btn" style="background:none;border:1px solid rgba(244,63,94,.3);border-radius:9px;padding:8px 14px;font-size:12px;font-weight:600;color:#fb7185;cursor:pointer;font-family:'Geist',sans-serif;">Remove</button>
                                <button type="button" id="bae-logo-check-btn" class="bae-btn bae-btn-outline bae-btn-sm" style="display:flex;align-items:center;gap:5px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                                    Check Background
                                </button>
                                <?php endif; ?>
                                <span id="bae-logo-upload-status" style="font-size:12px;color:var(--text-3);"></span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="logo_url" id="bae-logo-url-hidden" value="<?php echo esc_attr($p['logo_url'] ?? ''); ?>">
                </div>

                <!-- Logo Background Checker -->
                <div id="bae-logo-bg-checker" style="display:none;margin-bottom:20px;">
                    <div style="font-size:11px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;">Background Checker — click to apply</div>
                    <div id="bae-logo-bg-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;"></div>
                    <div style="font-size:11px;color:var(--text-3);margin-top:10px;">These show your logo on different backgrounds. Pick one to set as your primary color, or use it as a reference when designing.</div>
                </div>

                <div class="bae-form-grid">
                    <div class="bae-form-group">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                            <div style="width:22px;height:22px;border-radius:6px;background:var(--bg-3);border:1px solid var(--border-2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span style="font-size:11px;font-weight:800;color:var(--text-2);">B</span>
                            </div>
                            <div style="font-size:12px;font-weight:700;color:var(--text-2);">CSS Logo Builder <span style="font-size:11px;font-weight:400;color:var(--text-3);">— Used in assets when no logo uploaded</span></div>
                        </div>
                        <label>Logo Type</label>
                        <select name="logo_style">
                            <?php
                            $styles = [
                                'wordmark'    => 'Wordmark — Business name as logo',
                                'lettermark'  => 'Lettermark — Initials only',
                                'combination' => 'Combination — Icon + Name',
                                'emblem'      => 'Emblem — Icon inside a badge shape',
                                'monogram'    => 'Monogram — Stylized initials',
                                'abstract'    => 'Abstract Mark — Icon only, no text',
                                'badge'       => 'Badge — Circular seal style',
                                'stacked'     => 'Stacked — Icon above name',
                                'outlined'    => 'Outlined — Name with border frame',
                                'minimal'     => 'Minimal — Initials with dot/line',
                            ];
                            $sls = $p['logo_style'] ?? 'wordmark';
                            foreach ($styles as $val => $lbl):
                            ?>
                                <option value="<?php echo $val; ?>" <?php selected($sls, $val); ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bae-form-group">
                        <label>Logo Icon / Symbol</label>
                        <div id="bae-icon-picker" style="display:grid;grid-template-columns:repeat(8,1fr);gap:6px;padding:12px;background:var(--bg-3);border-radius:12px;border:1px solid var(--border-2);max-height:200px;overflow-y:auto;">
                            <?php
                            $all_icons = bae_get_all_icons();
                            $sli = $p['logo_icon'] ?? '';
                            foreach ($all_icons as $icon_key => $icon_label):
                                $svg = bae_get_icon_svg_preview($icon_key);
                            ?>
                            <div class="bae-icon-tile <?php echo $sli === $icon_key ? 'selected' : ''; ?>"
                                 data-value="<?php echo esc_attr($icon_key); ?>"
                                 title="<?php echo esc_attr($icon_label); ?>"
                                 onclick="baeSelectIcon(this)"
                                 style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;border:1.5px solid <?php echo $sli === $icon_key ? '#8b5cf6' : 'transparent'; ?>;background:<?php echo $sli === $icon_key ? 'rgba(139,92,246,.15)' : 'var(--surface)'; ?>;transition:all .15s;">
                                <?php echo $svg; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="logo_icon" id="bae-logo-icon-hidden" value="<?php echo esc_attr($sli); ?>">
                        <small>Icon used in combination/lettermark logos.</small>
                    </div>
                </div>
            </div>

            <style>
            .bae-icon-tile:hover { border-color: rgba(139,92,246,.4) !important; background: rgba(139,92,246,.08) !important; }
            .bae-icon-tile.selected { border-color: #8b5cf6 !important; background: rgba(139,92,246,.15) !important; }
            #bae-icon-picker::-webkit-scrollbar { width: 4px; }
            #bae-icon-picker::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 999px; }
            </style>

            <input type="hidden" name="action" value="bae_save_profile">
            <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">

            <div style="display:flex;align-items:center;gap:12px;">
                <button type="submit" class="bae-btn bae-btn-primary">Save Brand Profile</button>
                <?php if (!$is_new): ?>
                    <a href="?tab=identity" class="bae-btn bae-btn-outline">View Identity Board &rarr;</a>
                <?php endif; ?>
            </div>
            <div id="bae-profile-msg"></div>
        </form>
    </div>

    <?php if (!$is_new): ?>
    <!-- Launch Toolkit shortcut card -->
    <div class="bae-card" style="margin-top:0;">
        <div class="bae-card-header" style="margin-bottom:0;">
            <div>
                <div class="bae-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:6px;color:var(--brand-soft)"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/></svg>
                    Launch Toolkit
                </div>
                <div class="bae-card-desc">Domain ideas, social handles, tagline variants, email suggestions, and a full legal launch checklist — all generated from your profile.</div>
            </div>
            <a href="?tab=startup" class="bae-btn bae-btn-primary bae-btn-sm" style="white-space:nowrap;">Open Toolkit &rarr;</a>
        </div>
    </div>
    <?php endif; ?>

    <script>
    (function() {
        var overviewNonce = '<?php echo esc_js(wp_create_nonce('bae_save_profile')); ?>';
        var bizName       = <?php echo json_encode($p['business_name'] ?? ''); ?>;
        var bizIndustry   = <?php echo json_encode($p['industry'] ?? ''); ?>;

        var form = document.getElementById('bae-profile-form');
        var msg  = document.getElementById('bae-profile-msg');

        // ── Profile save ─────────────────────────────────────────────────
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = 'Saving...';
                msg.innerHTML = '';
                var formData = new FormData(form);
                fetch(ajaxurl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    msg.innerHTML = '<div class="bae-notice bae-notice-' + (json.success ? 'success' : 'error') + '">' + json.data.message + '</div>';
                    btn.disabled = false;
                    btn.textContent = 'Save Brand Profile';
                    if (json.success) setTimeout(function() { location.reload(); }, 1200);
                })
                .catch(function() {
                    msg.innerHTML = '<div class="bae-notice bae-notice-error">An error occurred. Please try again.</div>';
                    btn.disabled = false;
                    btn.textContent = 'Save Brand Profile';
                });
            });
        }

        // ── AI Color Suggest ──────────────────────────────────────────────
        var colorBtn   = document.getElementById('bae-ai-color-btn');
        var colorPanel = document.getElementById('bae-ai-color-panel');
        var colorResults = document.getElementById('bae-ai-color-results');

        if (colorBtn) {
            colorBtn.addEventListener('click', function() {
                var isOpen = colorPanel.style.display !== 'none';
                if (isOpen) { colorPanel.style.display = 'none'; return; }
                colorPanel.style.display = 'block';
                colorResults.innerHTML = '<div style="font-size:13px;color:var(--text-3);text-align:center;padding:16px;">Generating palettes...</div>';

                var name = (document.querySelector('[name="business_name"]') || {}).value || bizName;
                var industry = (document.querySelector('[name="industry"]') || {}).value || bizIndustry;

                var fd = new FormData();
                fd.append('action', 'bae_suggest_colors');
                fd.append('nonce', overviewNonce);
                fd.append('name', name);
                fd.append('industry', industry);

                fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (!j.success || !j.data.palettes) {
                        colorResults.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:8px;">Could not generate suggestions. Try again.</div>';
                        return;
                    }
                    colorResults.innerHTML = '';
                    j.data.palettes.forEach(function(p) {
                        var card = document.createElement('div');
                        card.style.cssText = 'display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--surface);border-radius:10px;border:1.5px solid var(--border);cursor:pointer;transition:all .2s;';
                        card.innerHTML =
                            '<div style="display:flex;gap:4px;flex-shrink:0;">' +
                                '<div style="width:22px;height:38px;border-radius:5px;background:'+p.primary+';"></div>' +
                                '<div style="width:22px;height:38px;border-radius:5px;background:'+p.secondary+';"></div>' +
                                '<div style="width:22px;height:38px;border-radius:5px;background:'+p.accent+';"></div>' +
                            '</div>' +
                            '<div style="flex:1;min-width:0;">' +
                                '<div style="font-size:13px;font-weight:600;color:var(--text);">'+p.name+'</div>' +
                                '<div style="font-size:11px;color:var(--text-3);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+p.reason+'</div>' +
                            '</div>' +
                            '<button style="background:var(--brand);color:white;border:none;border-radius:7px;padding:5px 12px;font-size:11px;font-weight:700;cursor:pointer;font-family:\'Geist\',sans-serif;white-space:nowrap;">Apply</button>';

                        card.querySelector('button').addEventListener('click', function(e) {
                            e.stopPropagation();
                            // Apply colors to pickers
                            function applyColor(nameAttr, val) {
                                var txt = document.querySelector('[name="'+nameAttr+'"]');
                                var picker = document.querySelector('[name="'+nameAttr+'_picker"]');
                                if (txt) txt.value = val;
                                if (picker) picker.value = val;
                            }
                            applyColor('primary_color', p.primary);
                            applyColor('secondary_color', p.secondary);
                            applyColor('accent_color', p.accent);
                            card.style.borderColor = '#8b5cf6';
                            card.style.background = 'rgba(139,92,246,.08)';
                            colorPanel.style.display = 'none';
                            if (window.gsap) gsap.fromTo(card, {scale:.97}, {scale:1, duration:.25, ease:'back.out(2)'});
                        });
                        colorResults.appendChild(card);
                    });
                })
                .catch(function() {
                    colorResults.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:8px;">Connection error. Try again.</div>';
                });
            });
        }

        // ── AI Tagline Suggest ────────────────────────────────────────────
        var taglineBtn    = document.getElementById('bae-ai-tagline-btn');
        var taglinePanel  = document.getElementById('bae-ai-tagline-panel');
        var taglineResult = document.getElementById('bae-ai-tagline-results');
        var taglineInput  = document.getElementById('bae-tagline-input');

        if (taglineBtn) {
            taglineBtn.addEventListener('click', function() {
                var isOpen = taglinePanel.style.display !== 'none';
                if (isOpen) { taglinePanel.style.display = 'none'; return; }
                taglinePanel.style.display = 'block';
                taglineResult.innerHTML = '<div style="font-size:13px;color:var(--text-3);text-align:center;padding:8px;">Writing taglines...</div>';

                var name     = (document.querySelector('[name="business_name"]') || {}).value || bizName;
                var industry = (document.querySelector('[name="industry"]') || {}).value || bizIndustry;

                var fd = new FormData();
                fd.append('action', 'bae_suggest_tagline');
                fd.append('nonce', overviewNonce);
                fd.append('name', name);
                fd.append('industry', industry);

                fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (!j.success || !j.data.taglines) {
                        taglineResult.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:4px;">Could not generate. Try again.</div>';
                        return;
                    }
                    taglineResult.innerHTML = '';
                    j.data.taglines.forEach(function(t) {
                        var row = document.createElement('div');
                        row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 12px;background:var(--surface);border-radius:8px;border:1px solid var(--border);cursor:pointer;transition:all .2s;';
                        row.innerHTML = '<span style="font-size:13px;color:var(--text);font-style:italic;">"'+t+'"</span>' +
                            '<button style="background:none;border:1px solid var(--border-2);border-radius:6px;padding:3px 10px;font-size:11px;font-weight:600;color:var(--text-2);cursor:pointer;font-family:\'Geist\',sans-serif;white-space:nowrap;">Use</button>';
                        row.querySelector('button').addEventListener('click', function(e) {
                            e.stopPropagation();
                            if (taglineInput) taglineInput.value = t;
                            taglinePanel.style.display = 'none';
                        });
                        taglineResult.appendChild(row);
                    });
                })
                .catch(function() {
                    taglineResult.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:4px;">Connection error.</div>';
                });
            });
        }

        // ── Logo Upload ───────────────────────────────────────────────────
        var fileInput    = document.getElementById('bae-logo-file-input');
        var uploadStatus = document.getElementById('bae-logo-upload-status');
        var logoUrlHidden = document.getElementById('bae-logo-url-hidden');
        var logoPreviewWrap = document.getElementById('bae-logo-preview-wrap');

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                var file = this.files[0];
                if (!file) return;
                uploadStatus.textContent = 'Uploading...';
                uploadStatus.style.color = 'var(--text-3)';

                var fd = new FormData();
                fd.append('action', 'bae_upload_logo');
                fd.append('nonce', overviewNonce);
                fd.append('logo_file', file);

                fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (j.success && j.data.url) {
                        logoUrlHidden.value = j.data.url;
                        uploadStatus.textContent = 'Uploaded!';
                        uploadStatus.style.color = '#34d399';
                        // Show preview
                        logoPreviewWrap.innerHTML = '<img src="'+j.data.url+'" style="max-width:100%;max-height:100%;object-fit:contain;" id="bae-logo-preview-img">';
                        // Show check bg button
                        var checkBtn = document.getElementById('bae-logo-check-btn');
                        if (!checkBtn) {
                            var removeBtn = document.getElementById('bae-logo-remove-btn');
                            var newCheckBtn = document.createElement('button');
                            newCheckBtn.type = 'button';
                            newCheckBtn.id = 'bae-logo-check-btn';
                            newCheckBtn.className = 'bae-btn bae-btn-outline bae-btn-sm';
                            newCheckBtn.style.cssText = 'display:inline-flex;align-items:center;gap:5px;';
                            newCheckBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg> Check Background';
                            newCheckBtn.addEventListener('click', showBgChecker);
                            uploadStatus.parentNode.insertBefore(newCheckBtn, uploadStatus);
                        }
                        setTimeout(function() { uploadStatus.textContent = ''; }, 2500);
                    } else {
                        uploadStatus.textContent = (j.data && j.data.message) ? j.data.message : 'Upload failed.';
                        uploadStatus.style.color = '#fb7185';
                    }
                })
                .catch(function() {
                    uploadStatus.textContent = 'Upload error.';
                    uploadStatus.style.color = '#fb7185';
                });
            });
        }

        // Remove logo
        var removeBtn = document.getElementById('bae-logo-remove-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                logoUrlHidden.value = '';
                logoPreviewWrap.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';
                var bgChecker = document.getElementById('bae-logo-bg-checker');
                if (bgChecker) bgChecker.style.display = 'none';
                removeBtn.style.display = 'none';
            });
        }

        // ── Logo Background Checker ───────────────────────────────────────
        var checkBtn = document.getElementById('bae-logo-check-btn');
        if (checkBtn) checkBtn.addEventListener('click', showBgChecker);

        function showBgChecker() {
            var logoUrl = (logoUrlHidden && logoUrlHidden.value) ? logoUrlHidden.value : '';
            if (!logoUrl) return;

            var checker = document.getElementById('bae-logo-bg-checker');
            var grid    = document.getElementById('bae-logo-bg-grid');
            checker.style.display = 'block';
            grid.innerHTML = '';

            // Get current brand colors
            var pc = (document.getElementById('bae-primary-color')   || {}).value || '#1a1a2e';
            var sc = (document.getElementById('bae-secondary-color') || {}).value || '#16213e';
            var ac = (document.getElementById('bae-accent-color')    || {}).value || '#e94560';

            var bgs = [
                { color:'#ffffff', label:'White' },
                { color:'#f9fafb', label:'Off-White' },
                { color:'#111827', label:'Near Black' },
                { color:'#000000', label:'Black' },
                { color:pc,        label:'Primary' },
                { color:sc,        label:'Secondary' },
                { color:ac,        label:'Accent' },
                { color:'#f3f4f6', label:'Light Gray' },
                { color:'#1f2937', label:'Dark Gray' },
                { color:'#fef3c7', label:'Cream' },
                { color:'#dbeafe', label:'Sky Blue' },
                { color:'#d1fae5', label:'Mint' },
            ];

            bgs.forEach(function(bg) {
                var tile = document.createElement('div');
                tile.style.cssText = 'border-radius:10px;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:all .2s;';
                tile.title = 'Use ' + bg.label + ' as primary color';

                var preview = document.createElement('div');
                preview.style.cssText = 'height:70px;background:'+bg.color+';display:flex;align-items:center;justify-content:center;padding:8px;';
                var img = document.createElement('img');
                img.src = logoUrl;
                img.style.cssText = 'max-width:100%;max-height:100%;object-fit:contain;';
                preview.appendChild(img);

                var label = document.createElement('div');
                label.style.cssText = 'background:var(--surface);padding:4px 6px;font-size:10px;font-weight:600;color:var(--text-2);text-align:center;';
                label.textContent = bg.label;

                tile.appendChild(preview);
                tile.appendChild(label);

                tile.addEventListener('mouseenter', function() { tile.style.borderColor = '#8b5cf6'; });
                tile.addEventListener('mouseleave', function() { tile.style.borderColor = 'transparent'; });
                tile.addEventListener('click', function() {
                    // Apply this bg color as primary
                    var pcInput  = document.querySelector('[name="primary_color"]');
                    var pcPicker = document.querySelector('[name="primary_color_picker"]');
                    if (pcInput)  pcInput.value  = bg.color;
                    if (pcPicker) pcPicker.value = bg.color;
                    tile.style.borderColor = '#8b5cf6';
                    setTimeout(function() { tile.style.borderColor = 'transparent'; }, 1500);
                });

                grid.appendChild(tile);
            });

            if (window.gsap) gsap.fromTo(checker, {opacity:0, y:8}, {opacity:1, y:0, duration:.3, ease:'power3.out'});
        }

        // Icon picker
        window.baeSelectIcon = function(el) {
            document.querySelectorAll('.bae-icon-tile').forEach(function(t) {
                t.classList.remove('selected');
                t.style.borderColor = 'transparent';
                t.style.background  = 'var(--surface)';
            });
            el.classList.add('selected');
            el.style.borderColor = '#8b5cf6';
            el.style.background  = 'rgba(139,92,246,.15)';
            var hidden = document.getElementById('bae-logo-icon-hidden');
            if (hidden) hidden.value = el.dataset.value;
            if (window.gsap) gsap.fromTo(el, {scale:.9}, {scale:1, duration:.2, ease:'back.out(2)'});
        };

    })();
    </script>
    <?php
    return ob_get_clean();
}

// =============================================================================
// TAB: IDENTITY BOARD
// CHANGED: Colors run through bae_safe_color() before rendering
// =============================================================================

function bae_identity_tab($user_id, $profile) {
    if (empty($profile)) {
        ob_start();
        ?>
        <div class="bae-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
            </svg>
            <strong>No brand profile yet.</strong>
            <p>Complete your <a href="?tab=overview">Brand Profile</a> first to see your Identity Board.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    $p        = $profile;
    $initials = bae_get_initials($p['business_name']);
    $tone_tags = bae_derive_tone_tags($p['industry'], $p['personality'] ?? '');

    // CHANGED: All colors validated at render time
    $pc = bae_safe_color($p['primary_color'], '#1a1a2e');
    $sc = bae_safe_color($p['secondary_color'], '#16213e');
    $ac = bae_safe_color($p['accent_color'], '#e94560');

    ob_start();
    ?>
    <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($p['font_heading']); ?>:wght@400;700&family=<?php echo urlencode($p['font_body']); ?>&display=swap" rel="stylesheet">

    <!-- Logo Mockup -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Logo Concept</div>
                <div class="bae-card-desc">CSS-based logo mockup based on your style selection. Use this as a starting reference for your designer.</div>
            </div>
            <span class="bae-badge bae-badge-gray"><?php echo ucfirst($p['logo_style']); ?></span>
        </div>

        <!-- Light background -->
        <div class="bae-logo-mockup" style="background:#ffffff;">
            <?php if (!empty($p['logo_url'])): ?>
                <img src="<?php echo esc_url($p['logo_url']); ?>" style="max-height:52px;max-width:160px;object-fit:contain;" alt="<?php echo esc_attr($p['business_name']); ?>">
            <?php else: ?>
                <?php if ($p['logo_style'] !== 'wordmark'): ?>
                <div class="bae-logo-icon-shape" style="background:<?php echo $pc; ?>;">
                    <?php echo $p['logo_style'] === 'lettermark' ? $initials : bae_render_icon($p['logo_icon'], $p['primary_color']); ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="bae-logo-text-area">
                <div class="bae-logo-name" style="font-family:'<?php echo esc_attr($p['font_heading']); ?>',sans-serif;color:<?php echo $pc; ?>;">
                    <?php echo empty($p['logo_url']) ? (($p['logo_style'] === 'lettermark') ? esc_html($initials) : esc_html($p['business_name'])) : esc_html($p['business_name']); ?>
                </div>
                <?php if (!empty($p['tagline'])): ?>
                <div class="bae-logo-tagline" style="color:<?php echo $sc; ?>;">
                    <?php echo esc_html($p['tagline']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dark background version -->
        <div class="bae-logo-mockup" style="background:<?php echo $pc; ?>;margin-top:12px;">
            <?php if (!empty($p['logo_url'])): ?>
                <img src="<?php echo esc_url($p['logo_url']); ?>" style="max-height:52px;max-width:160px;object-fit:contain;filter:brightness(0) invert(1);opacity:.9;" alt="<?php echo esc_attr($p['business_name']); ?>">
            <?php else: ?>
                <?php if ($p['logo_style'] !== 'wordmark'): ?>
                <div class="bae-logo-icon-shape" style="background:rgba(255,255,255,0.15);">
                    <?php echo $p['logo_style'] === 'lettermark' ? $initials : bae_render_icon($p['logo_icon'], '#fff'); ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="bae-logo-text-area">
                <div class="bae-logo-name" style="font-family:'<?php echo esc_attr($p['font_heading']); ?>',sans-serif;color:#ffffff;">
                    <?php echo empty($p['logo_url']) ? (($p['logo_style'] === 'lettermark') ? esc_html($initials) : esc_html($p['business_name'])) : esc_html($p['business_name']); ?>
                </div>
                <?php if (!empty($p['tagline'])): ?>
                <div class="bae-logo-tagline" style="color:rgba(255,255,255,0.65);">
                    <?php echo esc_html($p['tagline']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Color Palette -->
    <div class="bae-card">
        <div class="bae-card-title">Color Palette</div>
        <div class="bae-card-desc" style="margin-top:4px;">Your defined brand colors with usage guidance.</div>
        <div class="bae-color-swatches">
            <?php
            $colors = [
                'Primary'   => $pc,
                'Secondary' => $sc,
                'Accent'    => $ac,
            ];
            foreach ($colors as $label => $hex):
            ?>
            <div class="bae-swatch" onclick="baeCopyHex('<?php echo esc_js(strtoupper($hex)); ?>', this)">
                <div class="bae-swatch-block" style="background:<?php echo esc_attr($hex); ?>;"></div>
                <div class="bae-swatch-label"><?php echo $label; ?></div>
                <div class="bae-swatch-hex"><?php echo strtoupper($hex); ?></div>
                <button class="bae-swatch-copy-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    Copy
                </button>
            </div>
            <?php endforeach; ?>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:<?php echo esc_attr($pc); ?>;opacity:0.6;"></div>
                <div class="bae-swatch-label">Primary Tint</div>
                <div class="bae-swatch-hex">60%</div>
            </div>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:var(--surface);border:1px solid var(--border);"></div>
                <div class="bae-swatch-label">White</div>
                <div class="bae-swatch-hex">#FFFFFF</div>
            </div>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:var(--bg-3);"></div>
                <div class="bae-swatch-label">Off-White</div>
                <div class="bae-swatch-hex">#F9FAFB</div>
            </div>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:#111827;"></div>
                <div class="bae-swatch-label">Near Black</div>
                <div class="bae-swatch-hex">#111827</div>
            </div>
        </div>
    </div>

    <!-- Typography -->
    <div class="bae-card">
        <div class="bae-card-title">Typography Pairing</div>
        <div class="bae-card-desc" style="margin-top:4px;"><?php echo esc_html($p['font_heading']); ?> + <?php echo esc_html($p['font_body']); ?></div>
        <div class="bae-font-sample">
            <div class="bae-font-heading-sample" style="font-family:'<?php echo esc_attr($p['font_heading']); ?>',sans-serif;color:<?php echo $pc; ?>;">
                <?php echo esc_html($p['business_name']); ?>
            </div>
            <div class="bae-font-body-sample" style="font-family:'<?php echo esc_attr($p['font_body']); ?>',sans-serif;">
                <?php echo !empty($p['tagline']) ? esc_html($p['tagline']) : 'Quality products and services crafted with care for every customer we serve.'; ?>
                Founded with a vision to bring something meaningful to the community.
            </div>
        </div>
        <div style="margin-top:16px;display:flex;gap:24px;flex-wrap:wrap;">
            <div style="font-size:13px;color:var(--text-2);">
                <strong>Heading:</strong> <?php echo esc_html($p['font_heading']); ?> — Bold 700
                <button class="bae-font-copy-btn" onclick="baeCopyText('<?php echo esc_js($p['font_heading']); ?>', this)" style="margin-left:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    Copy name
                </button>
            </div>
            <div style="font-size:13px;color:var(--text-2);">
                <strong>Body:</strong> <?php echo esc_html($p['font_body']); ?> — Regular 400
                <button class="bae-font-copy-btn" onclick="baeCopyText('<?php echo esc_js($p['font_body']); ?>', this)" style="margin-left:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    Copy name
                </button>
            </div>
        </div>
    </div>

    <!-- Tone of Voice -->
    <div class="bae-card">
        <div class="bae-card-title">Tone of Voice</div>
        <div class="bae-card-desc" style="margin-top:4px;">Derived from your industry and target audience.</div>
        <div class="bae-tone-tags">
            <?php foreach ($tone_tags as $tag): ?>
                <span class="bae-tone-tag"><?php echo esc_html($tag); ?></span>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($p['personality'])): ?>
        <div style="margin-top:16px;padding:16px;background:var(--bg-3);border-radius:8px;font-size:14px;color:var(--text-2);line-height:1.7;">
            <strong>Target Audience:</strong> <?php echo esc_html($p['personality']); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- CTA -->
    <div style="display:flex;gap:12px;">
        <a href="?tab=assets" class="bae-btn bae-btn-primary">Generate Assets &rarr;</a>
        <a href="?tab=overview" class="bae-btn bae-btn-outline">&larr; Edit Profile</a>
    </div>
    <script>
    function baeCopyHex(hex, swatchEl) {
        navigator.clipboard.writeText(hex).then(function() {
            var btn = swatchEl.querySelector('.bae-swatch-copy-btn');
            if (!btn) return;
            var orig = btn.innerHTML;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Copied';
            btn.classList.add('copied');
            if (window.gsap) gsap.fromTo(btn, {scale:0.9}, {scale:1, duration:0.3, ease:'back.out(2)'});
            setTimeout(function() { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1800);
        });
    }
    function baeCopyText(text, btnEl) {
        navigator.clipboard.writeText(text).then(function() {
            var orig = btnEl.innerHTML;
            btnEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Copied';
            btnEl.classList.add('copied');
            if (window.gsap) gsap.fromTo(btnEl, {scale:0.9}, {scale:1, duration:0.3, ease:'back.out(2)'});
            setTimeout(function() { btnEl.innerHTML = orig; btnEl.classList.remove('copied'); }, 1800);
        });
    }
    </script>
    <?php
    return ob_get_clean();
}

// =============================================================================
// TAB: ASSET GENERATOR
// CHANGED: Sequential generate-all with progress bar and per-step status
// CHANGED: Removed bae_generate_all AJAX action (handled client-side sequentially)
// =============================================================================

function bae_assets_tab($user_id, $profile) {
    if (empty($profile)) {
        ob_start();
        ?>
        <div class="bae-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <strong>No brand profile yet.</strong>
            <p>Complete your <a href="?tab=overview">Brand Profile</a> first to generate assets.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    global $wpdb;
    $p            = $profile;
    $nonce        = wp_create_nonce('bae_generate_asset');
    $assets_table = $wpdb->prefix . 'bae_assets';
    $profile_id   = $p['id'];
    $user_plan    = bae_get_user_plan($user_id, $profile);
    $is_free      = $user_plan === 'free';

    $generated = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$assets_table} WHERE profile_id = %d AND user_id = %d",
        $profile_id, $user_id
    ), ARRAY_A);

    $gen_map = [];
    foreach ($generated as $ga) {
        $gen_map[$ga['asset_type']] = $ga;
    }

    $asset_types = [
        'business_card'    => ['name' => 'Business Card',    'desc' => 'Print-ready front & back layout'],
        'letterhead'       => ['name' => 'Letterhead',        'desc' => 'A4 branded document header/footer'],
        'email_signature'  => ['name' => 'Email Signature',   'desc' => 'HTML email signature snippet'],
        'social_kit'       => ['name' => 'Social Media Kit',  'desc' => 'Profile frame + post template'],
        'brand_guidelines' => ['name' => 'Brand Guidelines',  'desc' => 'One-page brand rules document'],
        'sitemap'          => ['name' => 'Site Structure',    'desc' => 'Suggested sitemap for your industry'],
    ];

    // Append saved custom assets from DB
    foreach ( $gen_map as $type => $asset ) {
        if ( strpos($type, 'custom_') === 0 && ! isset($asset_types[$type]) ) {
            $asset_types[$type] = [
                'name' => esc_html($asset['asset_name']),
                'desc' => 'Custom AI-generated asset',
                'custom' => true,
            ];
        }
    }

    ob_start();
    ?>
    <div class="bae-generate-row">
        <div>
            <div class="bae-card-title">Asset Generator</div>
            <div class="bae-card-desc">Generate branded HTML assets ready for download or handoff.</div>
        </div>
        <?php if ($is_free): ?>
        <button class="bae-btn bae-btn-outline" onclick="baePricingOpen('Generate all 7 assets at once', 'Free plan lets you generate each asset individually. Upgrade to generate all 7 in one click, and regenerate anytime.')">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
            Generate All — Starter+
        </button>
        <?php else: ?>
        <button class="bae-btn bae-btn-primary" id="bae-generate-all-btn"
                data-nonce="<?php echo $nonce; ?>"
                data-pid="<?php echo $profile_id; ?>">
            Generate All Assets
        </button>
        <?php endif; ?>
    </div>

    <?php if ($is_free): ?>
    <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;background:linear-gradient(135deg,rgba(109,40,217,.1),rgba(236,72,153,.06));border:1px solid rgba(139,92,246,.2);border-radius:12px;margin-bottom:20px;flex-wrap:wrap;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
        <span style="font-size:13px;color:var(--text-2);flex:1;"><strong>Free plan</strong> — click <strong>Generate</strong> on any card below to generate it free, once per asset. You also get <strong>1 free Custom AI generation</strong>. Assets are yours to keep. Upgrade for Generate All, unlimited regeneration, and unlimited Custom AI.</span>
        <button class="bae-btn bae-btn-primary bae-btn-sm" onclick="baePricingOpen()" style="white-space:nowrap;">Upgrade Now ✦</button>
    </div>
    <?php endif; ?>

    <!-- CHANGED: Progress UI for sequential generate-all -->
    <div class="bae-progress-wrap" id="bae-progress-wrap">
        <div class="bae-progress-bar-track">
            <div class="bae-progress-bar-fill" id="bae-progress-fill"></div>
        </div>
        <div class="bae-progress-label" id="bae-progress-label">Starting...</div>
        <div class="bae-progress-steps" id="bae-progress-steps">
            <?php foreach ($asset_types as $type => $meta): ?>
                <span class="bae-progress-step" id="bae-step-<?php echo $type; ?>"><?php echo $meta['name']; ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="bae-generate-all-msg"></div>

    <div class="bae-assets-grid" style="margin-top:20px;">
        <?php foreach ($asset_types as $type => $meta):
            $is_gen   = isset($gen_map[$type]);
            $gen_data = $is_gen ? $gen_map[$type] : null;
        ?>
        <div class="bae-asset-card" id="bae-card-<?php echo $type; ?>">
            <div class="bae-asset-preview">
                <?php if ($is_gen): ?>
                    <div class="bae-asset-preview-inner bae-ai-asset">
                        <?php echo $gen_data['asset_html']; ?>
                    </div>
                <?php else: ?>
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                <?php endif; ?>
            </div>
            <div class="bae-asset-info">
                <div class="bae-asset-name">
                    <?php echo $meta['name']; ?>
                    <?php if ( ! empty($meta['custom']) ): ?>
                    <span style="font-size:10px;font-weight:700;background:linear-gradient(135deg,rgba(109,40,217,.15),rgba(139,92,246,.15));color:var(--brand-soft);border:1px solid rgba(139,92,246,.25);border-radius:999px;padding:2px 7px;margin-left:6px;vertical-align:middle;">AI</span>
                    <?php endif; ?>
                </div>
                <div class="bae-asset-meta"><?php echo $meta['desc']; ?></div>
            </div>
            <div class="bae-asset-actions">
                <?php if ($is_gen): ?>
                    <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-preview-btn"
                            data-type="<?php echo $type; ?>"
                            data-name="<?php echo esc_attr($meta['name']); ?>"
                            data-html="<?php echo esc_attr($gen_data['asset_html']); ?>">
                        Preview
                    </button>
                    <?php if ( empty($meta['custom']) ): ?>
                    <?php if ($is_free): ?>
                    <button type="button" class="bae-btn bae-btn-outline bae-btn-sm" onclick="baePricingOpen('Regenerate anytime', 'Free plan generates each asset once. Upgrade to regenerate whenever you update your brand.')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
                        Regen
                    </button>
                    <?php else: ?>
                    <button type="button" class="bae-btn bae-btn-primary bae-btn-sm bae-regen-btn"
                            data-type="<?php echo $type; ?>"
                            data-nonce="<?php echo $nonce; ?>"
                            data-pid="<?php echo $profile_id; ?>">
                        Regenerate
                    </button>
                    <?php endif; ?>
                    <?php endif; ?>
                    <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-delete-btn"
                            data-type="<?php echo $type; ?>"
                            data-id="<?php echo $gen_data['id']; ?>"
                            data-nonce="<?php echo $nonce; ?>">
                        &times;
                    </button>
                <?php else: ?>
                    <?php if ( empty($meta['custom']) ): ?>
                    <button type="button" class="bae-btn bae-btn-primary bae-btn-sm bae-gen-btn"
                            data-type="<?php echo $type; ?>"
                            data-nonce="<?php echo $nonce; ?>"
                            data-pid="<?php echo $profile_id; ?>">
                        Generate
                    </button>
                    <?php if ($is_free): ?>
                    <span style="font-size:10px;font-weight:600;color:var(--brand-soft);white-space:nowrap;display:flex;align-items:center;gap:3px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                        Free taste
                    </span>
                    <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Custom AI Generator -->
    <?php
    // Count custom AI assets already generated by this user
    $custom_ai_count = 0;
    foreach ( $gen_map as $type => $asset ) {
        if ( strpos($type, 'custom_') === 0 ) $custom_ai_count++;
    }
    $free_ai_used = $is_free && $custom_ai_count >= 1;
    ?>
    <div class="bae-card" style="margin-top:24px;position:relative;overflow:hidden;">
        <?php if ($free_ai_used): ?>
        <div style="position:absolute;inset:0;background:rgba(10,10,15,.75);backdrop-filter:blur(4px);border-radius:18px;z-index:10;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;cursor:pointer;" onclick="baePricingOpen('Unlock more AI generations', 'You\'ve used your one free AI generation. Upgrade to keep generating custom brand assets with AI.')">
            <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#6d28d9,#8b5cf6);display:flex;align-items:center;justify-content:center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
            </div>
            <div style="text-align:center;">
                <div style="font-size:14px;font-weight:700;color:white;margin-bottom:4px;">You've used your free AI generation</div>
                <div style="font-size:12px;color:rgba(255,255,255,.5);">Upgrade for unlimited custom AI assets</div>
            </div>
            <button style="background:linear-gradient(135deg,#6d28d9,#8b5cf6);color:white;border:none;border-radius:10px;padding:10px 24px;font-size:13px;font-weight:700;font-family:'Geist',sans-serif;cursor:pointer;box-shadow:0 4px 16px rgba(109,40,217,.4);">Upgrade to Unlock ✦</button>
        </div>
        <?php endif; ?>
        <div class="bae-card-title" style="display:flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--brand-soft)"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
            Custom AI Generator
        </div>
        <div class="bae-card-desc" style="margin-top:4px;margin-bottom:16px;">Describe anything and AI will generate it using your brand. Not limited to the 7 assets above.</div>
        <textarea id="bae-ai-prompt" rows="3"
            placeholder="e.g. Create a grand opening flyer with 20% discount&#10;e.g. Design a WhatsApp business banner&#10;e.g. Make a cafe menu price list"
            style="width:100%;background:var(--input-bg);border:1.5px solid var(--input-bd);border-radius:10px;padding:12px 14px;font-size:14px;font-family:'Geist',sans-serif;color:var(--text);outline:none;resize:vertical;transition:border-color .2s;box-sizing:border-box;"></textarea>
        <div style="display:flex;align-items:center;gap:10px;margin-top:10px;flex-wrap:wrap;">
            <button type="button" id="bae-ai-gen-btn" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>"
                style="background:linear-gradient(135deg,#6d28d9,#8b5cf6);color:white;border:none;border-radius:10px;padding:10px 20px;font-size:14px;font-weight:700;font-family:'Geist',sans-serif;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(109,40,217,.3);transition:all .2s;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                Generate
            </button>
            <span id="bae-ai-status" style="font-size:13px;color:var(--text-3);"></span>
        </div>
        <div id="bae-ai-result" style="display:none;margin-top:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
                <div style="font-size:12px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;">Result</div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="button" id="bae-ai-copy" style="background:var(--surface);border:1px solid var(--border-2);border-radius:7px;padding:6px 12px;font-size:12px;font-weight:600;color:var(--text-2);cursor:pointer;font-family:'Geist',sans-serif;">Copy HTML</button>
                    <button type="button" id="bae-ai-preview" style="background:var(--surface);border:1px solid var(--border-2);border-radius:7px;padding:6px 12px;font-size:12px;font-weight:600;color:var(--text-2);cursor:pointer;font-family:'Geist',sans-serif;">Preview</button>
                    <button type="button" id="bae-ai-save-btn" style="background:linear-gradient(135deg,#6d28d9,#8b5cf6);color:white;border:none;border-radius:7px;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Geist',sans-serif;display:flex;align-items:center;gap:5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                        Save as Asset
                    </button>
                </div>
            </div>
            <!-- Save name input — hidden until Save clicked -->
            <div id="bae-ai-save-row" style="display:none;margin-bottom:10px;display:none;align-items:center;gap:8px;flex-wrap:wrap;">
                <input type="text" id="bae-ai-save-name"
                    placeholder="Asset name e.g. Grand Opening Flyer"
                    style="flex:1;min-width:200px;background:var(--input-bg);border:1.5px solid var(--input-bd);border-radius:8px;padding:8px 12px;font-size:13px;font-family:'Geist',sans-serif;color:var(--text);outline:none;">
                <button type="button" id="bae-ai-save-confirm" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>"
                    style="background:linear-gradient(135deg,#059669,#10b981);color:white;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:'Geist',sans-serif;white-space:nowrap;">
                    Confirm Save
                </button>
                <button type="button" id="bae-ai-save-cancel"
                    style="background:var(--surface);border:1px solid var(--border-2);border-radius:8px;padding:8px 12px;font-size:13px;font-weight:600;color:var(--text-3);cursor:pointer;font-family:'Geist',sans-serif;">
                    Cancel
                </button>
                <span id="bae-ai-save-status" style="font-size:12px;color:var(--text-3);"></span>
            </div>
            <div id="bae-ai-frame" style="background:white;border:1px solid var(--border);border-radius:10px;padding:20px;overflow:auto;max-height:480px;"></div>
        </div>
    </div>

    <!-- Regen Prompt Modal -->
    <div id="bae-regen-modal-overlay" class="bae-modal-overlay" style="display:none;">
        <div class="bae-modal">
            <div class="bae-modal-header">
                <span class="bae-modal-title">Regenerate with AI Improvements</span>
                <button type="button" class="bae-modal-close" id="bae-regen-modal-close">&times;</button>
            </div>
            <div class="bae-modal-body">
                <p style="margin-bottom:16px;font-size:14px;color:var(--text-3);">Describe how you'd like to improve this asset. Keep it relevant to the asset type.</p>
                <textarea id="bae-regen-prompt" rows="3" placeholder="e.g. Make the colors brighter, add more contact info, change the layout..." style="width:100%;background:var(--input-bg);border:1.5px solid var(--input-bd);border-radius:10px;padding:12px 14px;font-size:14px;font-family:'Geist',sans-serif;color:var(--text);outline:none;resize:vertical;"></textarea>
                <div id="bae-regen-error" style="font-size:12px;color:#fb7185;margin-top:8px;display:none;"></div>
            </div>
            <div class="bae-modal-footer">
                <button type="button" class="bae-btn bae-btn-outline" id="bae-regen-skip">Skip Prompt</button>
                <button type="button" class="bae-btn bae-btn-primary" id="bae-regen-generate">Regenerate with AI</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        // Preview
        document.querySelectorAll('.bae-preview-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                baeOpenModal(this.dataset.name, this.dataset.html);
            });
        });

        // Single Generate (new assets - static)
        document.querySelectorAll('.bae-gen-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                baeGenerateAsset(this.dataset.type, this.dataset.nonce, this.dataset.pid, this, ''); // empty prompt for static
            });
        });

        // Regenerate (existing assets - with AI prompt)
        document.querySelectorAll('.bae-regen-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                // Open regen modal
                var modal = document.getElementById('bae-regen-modal-overlay');
                var promptEl = document.getElementById('bae-regen-prompt');
                var errorEl = document.getElementById('bae-regen-error');
                if (modal && promptEl) {
                    promptEl.value = '';
                    errorEl.style.display = 'none';
                    modal.style.display = 'flex';
                    // Store data on modal
                    modal.dataset.type = this.dataset.type;
                    modal.dataset.nonce = this.dataset.nonce;
                    modal.dataset.pid = this.dataset.pid;
                    modal.dataset.triggerType = this.dataset.type; // used to locate the card button
                }
            });
        });

        // Regen Modal Handlers
        var regenModal = document.getElementById('bae-regen-modal-overlay');
        var regenClose = document.getElementById('bae-regen-modal-close');
        var regenSkip = document.getElementById('bae-regen-skip');
        var regenGenerate = document.getElementById('bae-regen-generate');
        var regenPrompt = document.getElementById('bae-regen-prompt');
        var regenError = document.getElementById('bae-regen-error');

        if (regenClose) {
            regenClose.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                regenModal.style.display = 'none';
            });
        }

        if (regenSkip) {
            regenSkip.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                // Regenerate without prompt
                var type = regenModal.dataset.type;
                var nonce = regenModal.dataset.nonce;
                var pid = regenModal.dataset.pid;
                regenModal.style.display = 'none';
                var cardBtn = document.querySelector('.bae-regen-btn[data-type="' + type + '"]');
                baeGenerateAsset(type, nonce, pid, cardBtn, ''); // empty prompt
            });
        }

        if (regenGenerate) {
            regenGenerate.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                var prompt = regenPrompt.value.trim();
                if (!prompt) {
                    regenError.textContent = 'Please enter an improvement prompt.';
                    regenError.style.display = 'block';
                    return;
                }
                // Basic client-side validation: check if prompt is relevant
                var type = regenModal.dataset.type;
                var assetNames = {
                    'business_card': 'business card',
                    'letterhead': 'letterhead',
                    'email_signature': 'email signature',
                    'social_kit': 'social media kit',
                    'brand_guidelines': 'brand guidelines',
                    'sitemap': 'sitemap'
                };
                var assetName = assetNames[type] || 'asset';
                var lowerPrompt = prompt.toLowerCase();
                // Deny if prompt suggests creating something completely different
                var denyWords = ['create a website', 'make a flyer', 'design a poster', 'build an app', 'develop software'];
                for (var deny of denyWords) {
                    if (lowerPrompt.includes(deny)) {
                        regenError.textContent = 'Prompt must improve the existing ' + assetName + ', not create something new.';
                        regenError.style.display = 'block';
                        return;
                    }
                }
                regenError.style.display = 'none';
                var nonce = regenModal.dataset.nonce;
                var pid = regenModal.dataset.pid;
                regenModal.style.display = 'none';
                var cardBtn = document.querySelector('.bae-regen-btn[data-type="' + type + '"]');
                baeGenerateAsset(type, nonce, pid, cardBtn, prompt);
            });
        }

        // Delete
        document.querySelectorAll('.bae-delete-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id    = this.dataset.id;
                var nonce = this.dataset.nonce;
                baeConfirm('Remove this asset? You can regenerate it anytime.', function() {
                    var formData = new FormData();
                    formData.append('action', 'bae_delete_asset');
                    formData.append('asset_id', id);
                    formData.append('nonce', nonce);
                    fetch(ajaxurl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(json) {
                        if (json.success) location.reload();
                        else alert(json.data.message);
                    });
                });
            });
        });

        // ── CHANGED: Sequential Generate All with progress bar ──────────
        var genAllBtn  = document.getElementById('bae-generate-all-btn');
        var genAllMsg  = document.getElementById('bae-generate-all-msg');
        var progressWrap = document.getElementById('bae-progress-wrap');
        var progressFill = document.getElementById('bae-progress-fill');
        var progressLabel = document.getElementById('bae-progress-label');

        var types = ['business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'sitemap'];

        function setStepState(type, state) {
            var el = document.getElementById('bae-step-' + type);
            if (!el) return;
            el.className = 'bae-progress-step ' + state;
        }

        function setProgress(done, total, label) {
            var pct = Math.round((done / total) * 100);
            progressFill.style.width = pct + '%';
            progressLabel.textContent = label;
        }

        if (genAllBtn) {
            genAllBtn.addEventListener('click', function() {
                var nonce = this.dataset.nonce;
                var pid   = this.dataset.pid;

                genAllBtn.disabled = true;
                genAllBtn.textContent = 'Generating...';
                genAllMsg.innerHTML = '';
                progressWrap.classList.add('visible');
                setProgress(0, types.length, 'Starting generation...');

                // CHANGED: Sequential — each fetch waits for previous to complete
                var errors = [];

                function runNext(index) {
                    if (index >= types.length) {
                        // All done
                        var pct = errors.length === 0 ? 100 : Math.round(((types.length - errors.length) / types.length) * 100);
                        progressFill.style.width = '100%';
                        if (errors.length === 0) {
                            progressLabel.textContent = 'All assets generated!';
                            genAllMsg.innerHTML = '<div class="bae-notice bae-notice-success" style="margin-top:12px;">All 7 assets generated successfully!</div>';
                        } else {
                            progressLabel.textContent = (types.length - errors.length) + ' of ' + types.length + ' succeeded.';
                            genAllMsg.innerHTML = '<div class="bae-notice bae-notice-error" style="margin-top:12px;">Some assets failed: ' + errors.join(', ') + '. Please try regenerating them individually.</div>';
                        }
                        setTimeout(function() { location.reload(); }, 2000);
                        return;
                    }

                    var type = types[index];
                    setStepState(type, 'active');
                    setProgress(index, types.length, 'Generating ' + type.replace(/_/g,' ') + '...');

                    var formData = new FormData();
                    formData.append('action', 'bae_generate_asset');
                    formData.append('asset_type', type);
                    formData.append('profile_id', pid);
                    formData.append('nonce', nonce);

                    fetch(ajaxurl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(json) {
                        if (json.success) {
                            setStepState(type, 'done');
                        } else {
                            setStepState(type, 'error');
                            errors.push(type.replace(/_/g,' '));
                        }
                        runNext(index + 1);
                    })
                    .catch(function() {
                        setStepState(type, 'error');
                        errors.push(type.replace(/_/g,' '));
                        runNext(index + 1);
                    });
                }

                runNext(0);
            });
        }

        // ── Single asset generate helper ──────────────────────────────────
        function baeGenerateAsset(type, nonce, pid, btn, prompt) {
            var original = btn ? btn.textContent : 'Generating...';
            var isRegen = prompt && prompt.trim();
            if (btn) {
                btn.disabled = true;
                btn.textContent = isRegen ? 'Regenerating...' : 'Generating...';
            }

            var formData = new FormData();
            formData.append('action', 'bae_generate_asset');
            formData.append('asset_type', type);
            formData.append('profile_id', pid);
            formData.append('nonce', nonce);
            if (prompt) {
                formData.append('regen_prompt', prompt);
            }

            fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                console.log('[BAE Regen] Response received:', type, 'isRegen=', isRegen, json);
                if (json.success) {
                    var card = document.getElementById('bae-card-' + type);
                    var html = (typeof json.html === 'string' && json.html.trim()) ? json.html : '<div style="padding:18px 14px;color:#6b7280;">Asset generated. Refresh to view.</div>';
                    var isFallback = !(typeof json.html === 'string' && json.html.trim());

                    // Update preview in place (for both generate and regenerate)
                    if (card) {
                        var previewInner = card.querySelector('.bae-asset-preview-inner');
                        if (previewInner) {
                            previewInner.innerHTML = html;
                        }
                    }
                    // If it was empty, remove the empty SVG and add the inner div
                    var preview = card ? card.querySelector('.bae-asset-preview') : null;
                    var hasSvg = preview ? !!preview.querySelector('svg') : false;
                    if (preview && hasSvg) {
                        preview.innerHTML = '<div class="bae-asset-preview-inner bae-ai-asset">' + html + '</div>';
                        // Force repaint to ensure the new HTML renders immediately
                        preview.style.display = 'none';
                        preview.offsetHeight; // Trigger reflow
                        preview.style.display = '';
                    }

                    // If it's the fallback message, reload to show the actual asset
                    if (isFallback) {
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    }

                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = original;
                    }
                } else {
                    console.log('[BAE Regen] Error:', json.data.message);
                    alert(json.data.message || 'Generation failed.');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = original;
                    }
                }
            })
            .catch(function() {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = original;
                }
            });
        }
        // ── Custom AI Generator ─────────────────────────────────────────
        var aiBtn     = document.getElementById('bae-ai-gen-btn');
        var aiPrompt  = document.getElementById('bae-ai-prompt');
        var aiStatus  = document.getElementById('bae-ai-status');
        var aiResult  = document.getElementById('bae-ai-result');
        var aiFrame   = document.getElementById('bae-ai-frame');
        var aiCopy    = document.getElementById('bae-ai-copy');
        var aiPreview = document.getElementById('bae-ai-preview');

        if (aiBtn) {
            aiBtn.addEventListener('click', function() {
                var prompt = (aiPrompt.value || '').trim();
                if (!prompt) {
                    aiStatus.textContent = 'Please describe what you want to generate.';
                    aiStatus.style.color = '#fb7185';
                    return;
                }
                var nonce = this.dataset.nonce;
                var pid   = this.dataset.pid;
                aiBtn.disabled = true;
                aiStatus.style.color = 'var(--text-3)';
                aiStatus.textContent = 'AI is generating...';
                aiResult.style.display = 'none';

                var fd = new FormData();
                fd.append('action',        'bae_custom_generate');
                fd.append('nonce',         nonce);
                fd.append('profile_id',    pid);
                fd.append('custom_prompt', prompt);

                fetch(ajaxurl, {method:'POST', body:fd})
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    aiBtn.disabled = false;
                    if (j.success) {
                        aiStatus.textContent = 'Done.';
                        aiStatus.style.color = '#34d399';
                        aiFrame.innerHTML = '<div class="bae-ai-asset" style="isolation:isolate;">' + j.data.html + '</div>';
                        aiResult.style.display = 'block';
                        if (window.gsap) gsap.fromTo(aiResult,{opacity:0,y:8},{opacity:1,y:0,duration:.35,ease:'power3.out'});
                        setTimeout(function() { aiStatus.textContent = ''; }, 2000);
                    } else {
                        aiStatus.textContent = (j.data && j.data.message) ? j.data.message : 'Generation failed.';
                        aiStatus.style.color = '#fb7185';
                    }
                })
                .catch(function() {
                    aiBtn.disabled = false;
                    aiStatus.textContent = 'Connection error. Please try again.';
                    aiStatus.style.color = '#fb7185';
                });
            });
        }

        if (aiCopy) {
            aiCopy.addEventListener('click', function() {
                var innerEl = aiFrame ? aiFrame.querySelector('.bae-ai-asset') : null;
                var html = innerEl ? innerEl.innerHTML : (aiFrame ? aiFrame.innerHTML : '');
                navigator.clipboard.writeText(html).then(function() {
                    aiCopy.textContent = 'Copied!';
                    aiCopy.style.color = '#34d399';
                    setTimeout(function() { aiCopy.textContent = 'Copy HTML'; aiCopy.style.color = ''; }, 2000);
                });
            });
        }

        if (aiPreview) {
            aiPreview.addEventListener('click', function() {
                var _inner = aiFrame ? aiFrame.querySelector('.bae-ai-asset') : null;
                baeOpenModal('Custom Asset Preview', _inner ? _inner.innerHTML : (aiFrame ? aiFrame.innerHTML : ''));
            });
        }

        // ── Save as Asset ────────────────────────────────────────────────
        var aiSaveBtn     = document.getElementById('bae-ai-save-btn');
        var aiSaveRow     = document.getElementById('bae-ai-save-row');
        var aiSaveName    = document.getElementById('bae-ai-save-name');
        var aiSaveConfirm = document.getElementById('bae-ai-save-confirm');
        var aiSaveCancel  = document.getElementById('bae-ai-save-cancel');
        var aiSaveStatus  = document.getElementById('bae-ai-save-status');

        if (aiSaveBtn) {
            aiSaveBtn.addEventListener('click', function() {
                // Pre-fill name from the prompt
                var promptText = aiPrompt ? aiPrompt.value.trim() : '';
                if (aiSaveName && promptText) {
                    // Capitalise first letter
                    aiSaveName.value = promptText.charAt(0).toUpperCase() + promptText.slice(1);
                }
                aiSaveRow.style.display = 'flex';
                if (aiSaveName) aiSaveName.focus();
            });
        }

        if (aiSaveCancel) {
            aiSaveCancel.addEventListener('click', function() {
                aiSaveRow.style.display = 'none';
                aiSaveStatus.textContent = '';
            });
        }

        if (aiSaveConfirm) {
            aiSaveConfirm.addEventListener('click', function() {
                var name = aiSaveName ? aiSaveName.value.trim() : '';
                var _saveEl = aiFrame ? aiFrame.querySelector('.bae-ai-asset') : null;
                var html = _saveEl ? _saveEl.innerHTML : (aiFrame ? aiFrame.innerHTML : '');
                if (!name) {
                    aiSaveStatus.textContent = 'Please enter an asset name.';
                    aiSaveStatus.style.color = '#fb7185';
                    return;
                }
                if (!html) {
                    aiSaveStatus.textContent = 'Nothing to save yet.';
                    aiSaveStatus.style.color = '#fb7185';
                    return;
                }

                var nonce = this.dataset.nonce;
                var pid   = this.dataset.pid;
                aiSaveConfirm.disabled = true;
                aiSaveStatus.textContent = 'Saving...';
                aiSaveStatus.style.color = 'var(--text-3)';

                var fd = new FormData();
                fd.append('action',     'bae_save_custom_asset');
                fd.append('nonce',      nonce);
                fd.append('profile_id', pid);
                fd.append('asset_name', name);
                fd.append('asset_html', html);

                fetch(ajaxurl, {method:'POST', body:fd})
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    aiSaveConfirm.disabled = false;
                    if (j.success) {
                        aiSaveStatus.textContent = 'Saved!';
                        aiSaveStatus.style.color = '#34d399';
                        aiSaveRow.style.display = 'none';
                        // Reload to show new asset in grid
                        setTimeout(function() { location.reload(); }, 800);
                    } else {
                        aiSaveStatus.textContent = (j.data && j.data.message) ? j.data.message : 'Save failed.';
                        aiSaveStatus.style.color = '#fb7185';
                    }
                })
                .catch(function() {
                    aiSaveConfirm.disabled = false;
                    aiSaveStatus.textContent = 'Connection error.';
                    aiSaveStatus.style.color = '#fb7185';
                });
            });
        }

        if (aiSaveName) {
            aiSaveName.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') aiSaveConfirm && aiSaveConfirm.click();
            });
        }

    })();
    </script>
    <?php
    return ob_get_clean();
}

// =============================================================================
// TAB: BRAND KIT
// =============================================================================

function bae_kit_tab($user_id, $profile) {
    if (empty($profile)) {
        ob_start();
        ?>
        <div class="bae-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
            </svg>
            <strong>No brand profile yet.</strong>
            <p>Complete your <a href="?tab=overview">Brand Profile</a> to generate your Brand Kit page.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    $p         = $profile;
    $kit_slug  = !empty($p['kit_slug']) ? $p['kit_slug'] : sanitize_title($p['business_name']) . '-' . substr($p['rand_id'], 0, 6);
    $kit_page  = get_page_by_path('brand-kit');
    $kit_base  = $kit_page ? get_permalink($kit_page) : home_url('/brand-kit/');
    $kit_url   = add_query_arg('slug', $kit_slug, $kit_base);
    $is_pub    = $p['kit_visibility'] === 'public';
    $nonce     = wp_create_nonce('bae_save_kit_settings');
    $user_plan = bae_get_user_plan($user_id, $profile);
    $is_free   = $user_plan === 'free';

    ob_start();
    ?>
    <?php if ($is_free): ?>
    <!-- Free plan kit lock banner -->
    <div style="position:relative;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;padding:16px 20px;background:linear-gradient(135deg,rgba(109,40,217,.1),rgba(236,72,153,.06));border:1px solid rgba(139,92,246,.25);border-radius:14px;flex-wrap:wrap;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
            <div style="flex:1;">
                <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px;">Public Brand Kit is a Starter+ feature</div>
                <div style="font-size:12px;color:var(--text-3);">Upgrade to share your brand kit with designers, vendors, and partners via a public link.</div>
            </div>
            <button class="bae-btn bae-btn-primary bae-btn-sm" onclick="baePricingOpen('Share your Brand Kit publicly', 'Give designers, vendors, and clients a single link to your brand — colors, fonts, logo, and usage rules.')" style="white-space:nowrap;">Upgrade Now ✦</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Shareable Brand Kit</div>
                <div class="bae-card-desc">A public page showing your logo, colors, fonts, and brand rules for designers, vendors, and partners.</div>
            </div>
            <span class="bae-badge <?php echo $is_pub ? 'bae-badge-green' : 'bae-badge-gray'; ?>">
                <?php echo $is_pub ? 'Public' : 'Private'; ?>
            </span>
        </div>

        <?php if ($is_pub && !$is_free): ?>
        <div style="background:#f0fdf4;border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div style="font-size:13px;color:#065f46;font-family:'Courier New',monospace;word-break:break-all;">
                <?php echo esc_url($kit_url); ?>
            </div>
            <button class="bae-btn bae-btn-sm bae-btn-outline" onclick="navigator.clipboard.writeText('<?php echo esc_js($kit_url); ?>');this.textContent='Copied!';setTimeout(function(){this.textContent='Copy Link';}.bind(this),2000);">
                Copy Link
            </button>
        </div>
        <?php endif; ?>

        <form id="bae-kit-form">
            <div class="bae-form-grid">
                <div class="bae-form-group">
                    <label>Kit Visibility <?php if ($is_free) echo '<span style="font-size:10px;font-weight:700;background:linear-gradient(135deg,#6d28d9,#ec4899);color:white;padding:2px 7px;border-radius:5px;margin-left:6px;vertical-align:middle;">STARTER+</span>'; ?></label>
                    <?php if ($is_free): ?>
                    <select name="kit_visibility" disabled onclick="baePricingOpen()" style="opacity:.5;cursor:not-allowed;">
                        <option>Private — Upgrade to make public</option>
                    </select>
                    <small style="color:var(--brand-soft);cursor:pointer;" onclick="baePricingOpen('Make your Brand Kit public', 'Share your brand with the world on Starter plan.')">Upgrade to unlock public sharing →</small>
                    <?php else: ?>
                    <select name="kit_visibility">
                        <option value="private" <?php selected($p['kit_visibility'], 'private'); ?>>Private — Only you can see it</option>
                        <option value="public"  <?php selected($p['kit_visibility'], 'public'); ?>>Public — Anyone with the link</option>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="bae-form-group">
                    <label>Kit URL Slug</label>
                    <input type="text" name="kit_slug" value="<?php echo esc_attr($kit_slug); ?>" placeholder="my-brand-kit" <?php echo $is_free ? 'disabled style="opacity:.5;"' : ''; ?>>
                    <small>Unique identifier in the shareable URL.</small>
                </div>
            </div>
            <input type="hidden" name="action" value="bae_save_kit_settings">
            <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
            <?php if (!$is_free): ?>
            <button type="submit" class="bae-btn bae-btn-primary" style="margin-top:16px;">Save Kit Settings</button>
            <?php else: ?>
            <button type="button" class="bae-btn bae-btn-outline" style="margin-top:16px;" onclick="baePricingOpen()">Upgrade to Save Settings ✦</button>
            <?php endif; ?>
            <div id="bae-kit-msg"></div>
        </form>
    </div>

    <!-- Brand Kit Preview -->
    <div class="bae-card">
        <div class="bae-card-title">Kit Preview</div>
        <div class="bae-card-desc" style="margin-top:4px;margin-bottom:20px;">What partners and designers will see when they open your Brand Kit link.</div>
        <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;">
            <?php echo bae_render_kit_html($p); ?>
        </div>
    </div>

    <script>
    (function() {
        var form = document.getElementById('bae-kit-form');
        var msg  = document.getElementById('bae-kit-msg');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = 'Saving...';
                msg.innerHTML = '';
                var formData = new FormData(form);
                fetch(ajaxurl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    msg.innerHTML = '<div class="bae-notice bae-notice-' +
                        (json.success ? 'success' : 'error') + '">' +
                        json.data.message + '</div>';
                    btn.disabled = false;
                    btn.textContent = 'Save Kit Settings';
                    if (json.success) setTimeout(function() { location.reload(); }, 1200);
                });
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

// =============================================================================
// TAB: SETTINGS
// =============================================================================

function bae_settings_tab($user_id, $profile) {
    $nonce     = wp_create_nonce('bae_reset_profile');
    $user_plan = bae_get_user_plan($user_id, $profile);
    $is_free   = $user_plan === 'free';

    ob_start();
    ?>
    <!-- Current Plan Card -->
    <div class="bae-card" style="background:linear-gradient(135deg,rgba(109,40,217,.08),rgba(236,72,153,.04));border-color:rgba(139,92,246,.2);">
        <div class="bae-card-header" style="margin-bottom:0;">
            <div>
                <div class="bae-card-title">Current Plan</div>
                <div class="bae-card-desc" style="margin-top:4px;">
                    <?php if ($user_plan === 'pro'): ?>
                        You're on <strong>Pro</strong> — everything is unlocked. Thank you!
                    <?php elseif ($user_plan === 'starter'): ?>
                        You're on <strong>Starter</strong> — assets, regeneration, and brand kit sharing enabled.
                    <?php else: ?>
                        You're on the <strong>Free plan</strong> — generate each asset once, identity board and wizard always free.
                    <?php endif; ?>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                <?php if ($user_plan === 'pro'): ?>
                    <span class="bae-badge bae-badge-purple" style="font-size:13px;padding:6px 14px;">Pro ✦</span>
                <?php elseif ($user_plan === 'starter'): ?>
                    <span class="bae-badge bae-badge-green" style="font-size:13px;padding:6px 14px;">Starter</span>
                    <button class="bae-btn bae-btn-primary bae-btn-sm" onclick="baePricingOpen()">Upgrade to Pro ✦</button>
                <?php else: ?>
                    <span class="bae-badge bae-badge-gray" style="font-size:13px;padding:6px 14px;">Free</span>
                    <button class="bae-btn bae-btn-primary bae-btn-sm" onclick="baePricingOpen()" style="white-space:nowrap;">Upgrade Now ✦</button>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($is_free): ?>
        <div style="margin-top:20px;display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <?php
            $features = [
                ['label' => 'Wizard & Identity Board', 'free' => true],
                ['label' => 'Generate 7 assets (once)', 'free' => true],
                ['label' => 'Regenerate assets anytime', 'free' => false],
                ['label' => 'Public brand kit link', 'free' => false],
                ['label' => 'Custom AI Generator', 'free' => false],
                ['label' => 'Multiple brand profiles', 'free' => false],
            ];
            foreach ($features as $f):
            ?>
            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:<?php echo $f['free'] ? 'var(--text-2)' : 'var(--text-3)'; ?>;">
                <?php if ($f['free']): ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                <?php else: ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#5c5972" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
                <?php endif; ?>
                <?php echo $f['label']; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="bae-card">
        <div class="bae-card-title">Export</div>
        <div class="bae-card-desc" style="margin-top:4px;margin-bottom:16px;">Download all your generated brand assets as a ZIP file — each asset as a standalone HTML file.</div>
        <?php if (!empty($profile)): ?>
        <button class="bae-btn bae-btn-outline" id="bae-export-zip-btn"
                data-nonce="<?php echo wp_create_nonce('bae_reset_profile'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export ZIP
        </button>
        <span id="bae-export-zip-msg" style="font-size:12px;color:var(--text-3);margin-left:12px;"></span>
        <script>
        (function(){
            var btn = document.getElementById('bae-export-zip-btn');
            if (!btn) return;
            btn.addEventListener('click', function() {
                var origText = btn.innerHTML;
                btn.disabled = true;
                btn.textContent = 'Preparing ZIP...';
                var msg = document.getElementById('bae-export-zip-msg');
                msg.textContent = '';

                var fd = new FormData();
                fd.append('action', 'bae_export_zip');
                fd.append('nonce', btn.dataset.nonce);

                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(json) {
                    if (json.success) {
                        // Decode base64 and trigger download
                        var byteChars = atob(json.data.data);
                        var byteArr = new Uint8Array(byteChars.length);
                        for (var i = 0; i < byteChars.length; i++) byteArr[i] = byteChars.charCodeAt(i);
                        var blob = new Blob([byteArr], { type: 'application/zip' });
                        var url  = URL.createObjectURL(blob);
                        var a    = document.createElement('a');
                        a.href     = url;
                        a.download = json.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        msg.textContent = json.data.count + ' assets downloaded.';
                        msg.style.color = '#059669';
                    } else {
                        msg.textContent = json.data.message || 'Export failed.';
                        msg.style.color = '#dc2626';
                    }
                    btn.disabled = false;
                    btn.innerHTML = origText;
                })
                .catch(function() {
                    msg.textContent = 'Network error. Try again.';
                    msg.style.color = '#dc2626';
                    btn.disabled = false;
                    btn.innerHTML = origText;
                });
            });
        })();
        </script>
        <?php else: ?>
        <button class="bae-btn bae-btn-outline" disabled>Export ZIP — Complete your profile first</button>
        <?php endif; ?>
    </div>

    <div class="bae-card">
        <div class="bae-card-title">Module Information</div>
        <div style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <?php
            $info = [
                'Module'      => 'Brand Asset Engine',
                'Version'     => '1.1.0',
                'Module Slug' => 'bae',
                'Status'      => !empty($profile) ? 'Profile Active' : 'No Profile',
            ];
            foreach ($info as $k => $v):
            ?>
            <div style="font-size:13px;">
                <span style="color:var(--text-3);"><?php echo $k; ?>: </span>
                <span style="color:var(--text);font-weight:500;"><?php echo $v; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($profile)): ?>
    <div class="bae-danger-zone">
        <div class="bae-card-header" style="margin-bottom:16px;">
            <div>
                <div class="bae-card-title">Danger Zone</div>
                <div class="bae-card-desc">Irreversible actions — proceed with caution.</div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--surface)8f8;border:1px solid rgba(244,63,94,0.3);border-radius:8px;flex-wrap:wrap;gap:10px;">
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--text);">Reset Brand Profile</div>
                    <div style="font-size:13px;color:var(--text-3);margin-top:2px;">Clears your brand profile and all generated assets. Cannot be undone.</div>
                </div>
                <button class="bae-btn bae-btn-danger bae-btn-sm" id="bae-reset-btn" data-nonce="<?php echo $nonce; ?>">
                    Reset Everything
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    (function() {
        var resetBtn = document.getElementById('bae-reset-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                var nonce = this.dataset.nonce;
                baeConfirm('This will permanently delete your brand profile and all generated assets. Are you sure?', function() {
                    var formData = new FormData();
                    formData.append('action', 'bae_reset_profile');
                    formData.append('nonce', nonce);
                    fetch(ajaxurl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(json) {
                        if (json.success) location.href = '?tab=overview';
                        else alert(json.data.message);
                    });
                });
            });
        }
    })();
    </script>
    <?php
    $output = ob_get_clean();
    return apply_filters( 'bae_settings_tab_output', $output );
}

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
        "SELECT * FROM {$profiles_table} WHERE kit_slug = %s AND kit_visibility = 'public'",
        $slug
    ), ARRAY_A);

    if (!$profile) {
        return '<div style="text-align:center;padding:60px;color:var(--text-3);">This Brand Kit is private or does not exist.</div>';
    }

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

// =============================================================================
// ASSET HTML GENERATORS
// CHANGED: Contact fields (email, phone, website, address) used throughout
// CHANGED: Colors run through bae_safe_color() before injection into HTML
// =============================================================================

// =============================================================================
// GEMINI AI ASSET GENERATION
// Calls Gemini to generate unique, brand-specific HTML for each asset type.
// Falls back to static template if API fails or quota is hit.
// =============================================================================

// Load environment variables from .env file if not already loaded
if ( file_exists( BNTM_BAE_PATH . '.env' ) && ! getenv('BAE_GEMINI_API_KEY') ) {
    $env_file = BNTM_BAE_PATH . '.env';
    $lines = file( $env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
    foreach ( $lines as $line ) {
        if ( strpos( trim( $line ), '#' ) === 0 ) continue; // Skip comments
        if ( strpos( $line, '=' ) === false ) continue; // Skip invalid lines
        list( $key, $value ) = explode( '=', $line, 2 );
        $key = trim( $key );
        $value = trim( $value );
        if ( ! empty( $key ) && ! getenv( $key ) ) {
            putenv( $key . '=' . $value );
        }
    }
}

if ( ! defined( 'BAE_GEMINI_API_KEY' ) ) {
    $api_key = getenv( 'BAE_GEMINI_API_KEY' );
    if ( ! $api_key ) {
        $api_key = 'MISSING_GEMINI_KEY'; // Fallback if not set
    }
    define( 'BAE_GEMINI_API_KEY', $api_key );
    define( 'BAE_GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . BAE_GEMINI_API_KEY );
}

// =============================================================================
// JSON-only response handler (no HTML sanitization)
// Used for palette generation, tone analysis, and other JSON APIs
// =============================================================================
function bae_gemini_json_request( $prompt ) {
    $body = json_encode([
        'contents' => [[
            'parts' => [[ 'text' => $prompt ]]
        ]],
        'generationConfig' => [
            'temperature'     => 0.9,
            'maxOutputTokens' => 2048,
        ]
    ]);

    $response = wp_remote_post( BAE_GEMINI_ENDPOINT, [
        'timeout' => 30,
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => $body,
    ]);

    if ( is_wp_error($response) ) {
        return [ 'error' => 'Request failed: ' . $response->get_error_message() ];
    }

    $code = wp_remote_retrieve_response_code($response);
    if ( $code !== 200 ) {
        $decoded = json_decode( wp_remote_retrieve_body($response), true );
        $msg     = $decoded['error']['message'] ?? "HTTP {$code}";
        return [ 'error' => "Gemini error: {$msg}" ];
    }

    $data = json_decode( wp_remote_retrieve_body($response), true );
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if ( ! $text ) {
        return [ 'error' => 'Gemini returned empty response.' ];
    }

    // For JSON responses, only strip markdown code fences (no HTML sanitization)
    $text = preg_replace( '/^```json\s*/i', '', trim($text) );
    $text = preg_replace( '/^```\s*/i',     '', trim($text) );
    $text = preg_replace( '/```\s*$/',      '', trim($text) );
    $text = trim($text);

    return $text;
}

function bae_gemini_request( $prompt ) {
    $body = json_encode([
        'contents' => [[
            'parts' => [[ 'text' => $prompt ]]
        ]],
        'generationConfig' => [
            'temperature'     => 0.9,
            'maxOutputTokens' => 2048,
        ]
    ]);

    $response = wp_remote_post( BAE_GEMINI_ENDPOINT, [
        'timeout' => 30,
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => $body,
    ]);

    if ( is_wp_error($response) ) {
        return [ 'error' => 'Request failed: ' . $response->get_error_message() ];
    }

    $code = wp_remote_retrieve_response_code($response);
    if ( $code !== 200 ) {
        $decoded = json_decode( wp_remote_retrieve_body($response), true );
        $msg     = $decoded['error']['message'] ?? "HTTP {$code}";
        return [ 'error' => "Gemini error: {$msg}" ];
    }

    $data = json_decode( wp_remote_retrieve_body($response), true );
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if ( ! $text ) {
        return [ 'error' => 'Gemini returned empty response.' ];
    }

    // Strip markdown code fences
    $text = preg_replace( '/^```html\s*/i', '', trim($text) );
    $text = preg_replace( '/^```\s*/i',     '', trim($text) );
    $text = preg_replace( '/```\s*$/',      '', trim($text) );
    $text = trim($text);

    // SANITIZE: remove tags that would break the page when injected inline
    // Strip <html>, <head>, <body> wrappers — keep only inner content
    $text = preg_replace( '/<html[^>]*>/i',  '', $text );
    $text = preg_replace( '/<\/html>/i',     '', $text );
    $text = preg_replace( '/<head[^>]*>.*?<\/head>/is', '', $text );
    $text = preg_replace( '/<body[^>]*>/i',  '', $text );
    $text = preg_replace( '/<\/body>/i',     '', $text );

    // Neutralise any <script> tags — convert to data attributes so they can't execute or break JS
    $text = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '<!-- script removed for safety -->', $text );

    // Scope any bare <style> blocks so they don't leak into the parent page
    // Wrap them in a shadow-safe container that won't affect BAE UI
    $text = preg_replace_callback(
        '/<style[^>]*>(.*?)<\/style>/is',
        function($matches) {
            // Prefix every selector with .bae-ai-asset so styles are scoped
            $css = $matches[1];
            $css = preg_replace( '/([^{}]+)\{/m', '.bae-ai-asset $1 {', $css );
            return '<style>' . $css . '</style>';
        },
        $text
    );

    return trim($text);
}


// =============================================================================
// AJAX: WIZARD — AI Color Palette Suggestions
// Fires in the background after Step 1. Returns 5-6 named palettes with
// reasoning specific to the business name + industry.
// Falls back gracefully on the client side if this fails.
// =============================================================================

function bntm_ajax_bae_wizard_palettes() {
    $name     = sanitize_text_field( $_POST['name']     ?? '' );
    $industry = sanitize_text_field( $_POST['industry'] ?? '' );

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
        wp_send_json_error( [ 'message' => $result['error'] ] );
    }

    if ( ! is_string( $result ) ) {
        wp_send_json_error( [ 'message' => 'Invalid response from AI.' ] );
    }

    // Strip any stray markdown fences
    $json_str = preg_replace( '/^```json\s*/i', '', trim( $result ) );
    $json_str = preg_replace( '/^```\s*/i',     '', trim( $json_str ) );
    $json_str = preg_replace( '/```\s*$/',      '', trim( $json_str ) );
    $json_str = trim( $json_str );

    $palettes = json_decode( $json_str, true );

    if ( ! is_array( $palettes ) || empty( $palettes ) ) {
        wp_send_json_error( [ 'message' => 'Could not parse palette data.' ] );
    }

    // Sanitize each palette
    $clean = [];
    foreach ( $palettes as $p ) {
        if ( empty( $p['primary'] ) || empty( $p['name'] ) ) continue;
        $clean[] = [
            'name'        => sanitize_text_field( $p['name']        ?? 'Custom' ),
            'primary'     => bae_safe_color( $p['primary']   ?? '', '#1a1a2e' ),
            'secondary'   => bae_safe_color( $p['secondary'] ?? '', '#16213e' ),
            'accent'      => bae_safe_color( $p['accent']    ?? '', '#e94560' ),
            'personality' => sanitize_text_field( $p['personality'] ?? 'Professional, distinctive' ),
            'reason'      => sanitize_text_field( $p['reason']      ?? '' ),
        ];
    }

    if ( empty( $clean ) ) {
        wp_send_json_error( [ 'message' => 'No valid palettes generated.' ] );
    }

    wp_send_json_success( [ 'palettes' => $clean ] );
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

// =============================================================================
// AJAX: Logo Upload — saves to WordPress media library
// =============================================================================
function bntm_ajax_bae_upload_logo() {
    check_ajax_referer( 'bae_save_profile', 'nonce', false );
    if ( !is_user_logged_in() ) wp_send_json_error(['message' => 'Unauthorized.']);
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
    }

    wp_send_json_success(['url' => $upload['url'], 'id' => $attach_id ?? 0]);
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
    <div style='font-size:10px;color:#9ca3af;'>Generated by Brand Asset Engine</div>
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
      <span>Generated by Brand Asset Engine</span>
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
    <span>Generated by Brand Asset Engine — Site Structure Guide</span>
    <span>{$name} &copy; " . date('Y') . "</span>
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
            <div style="font-size:11px;color:#9ca3af;">Generated by Brand Asset Engine</div>
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
    // Use soft nonce check — wizard users may not be logged in (ticket-based identity)
    check_ajax_referer('bae_save_profile', 'nonce', false);

    // Ticket = identity. Must have a valid ticket to save.
    $ticket = '';
    if ( !empty($_COOKIE['bae_ticket']) ) {
        $raw = strtoupper( sanitize_text_field( $_COOKIE['bae_ticket'] ) );
        if ( preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw) ) {
            $ticket = $raw;
        }
    }
    if ( empty($ticket) ) {
        wp_send_json_error(['message' => 'No valid ticket found. Please refresh.']);
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
        'logo_url'        => esc_url_raw($_POST['logo_url']               ?? ''),
        'ticket'          => $ticket,
    ];

    if (empty($data['business_name'])) {
        wp_send_json_error(['message' => 'Business name is required.']);
    }

    // Look up ONLY by ticket — each ticket is its own brand
    $wpdb->hide_errors();
    $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table} WHERE ticket = %s", $ticket));
    $wpdb->show_errors();

    if ($existing) {
        $wpdb->update($table, $data, ['id' => $existing->id]);
        wp_send_json_success(['message' => 'Brand profile updated successfully!']);
    } else {
        $data['rand_id']  = bntm_rand_id();
        $data['user_id']  = is_user_logged_in() ? get_current_user_id() : 0;
        $data['kit_slug'] = sanitize_title($data['business_name']) . '-' . substr($data['rand_id'], 0, 6);
        $r = $wpdb->insert($table, $data);
        if ($r === false) wp_send_json_error(['message' => 'Failed to save profile. Please try again.']);
        wp_send_json_success(['message' => 'Brand profile created successfully!']);
    }
}


function bntm_ajax_bae_generate_asset() {
    check_ajax_referer('bae_generate_asset', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id    = get_current_user_id();
    $asset_type = sanitize_text_field($_POST['asset_type'] ?? '');
    $profile_id = intval($_POST['profile_id'] ?? 0);
    $regen_prompt = sanitize_text_field($_POST['regen_prompt'] ?? '');

    $allowed_types = ['business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'sitemap'];
    if (!in_array($asset_type, $allowed_types)) {
        wp_send_json_error(['message' => 'Invalid asset type.']);
    }

    $profiles_table = $wpdb->prefix . 'bae_profiles';
    $assets_table   = $wpdb->prefix . 'bae_assets';

    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$profiles_table} WHERE id = %d AND user_id = %d",
        $profile_id, $user_id
    ), ARRAY_A);

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
        if ( defined('BAE_GEMINI_API_KEY') && BAE_GEMINI_API_KEY === 'MISSING_GEMINI_KEY' ) {
            wp_send_json_error(['message' => 'AI regeneration is not configured (missing Gemini API key).']);
        }
    }

    // Regeneration path: use AI and error loudly if it fails (no silent static fallback).
    if ( ! empty($regen_prompt) ) {
        $current_html = bae_generate_asset_html_static($asset_type, $profile, '');
        $asset_names = [
            'business_card'    => 'business card',
            'letterhead'       => 'letterhead',
            'email_signature'  => 'email signature',
            'social_kit'       => 'social media kit',
            'brand_guidelines' => 'brand guidelines',
            'sitemap'          => 'site structure',
        ];
        $asset_name = $asset_names[$asset_type] ?? 'asset';
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
    ];

    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$assets_table} WHERE profile_id = %d AND user_id = %d AND asset_type = %s",
        $profile_id, $user_id, $asset_type
    ));

    if ($existing) {
        $result = $wpdb->update($assets_table, [
            'asset_html'   => $asset_html,
            'is_generated' => 1,
        ], ['id' => $existing->id]);
    } else {
        $result = $wpdb->insert($assets_table, [
            'rand_id'      => bntm_rand_id(),
            'profile_id'   => $profile_id,
            'user_id'      => $user_id,
            'asset_type'   => $asset_type,
            'asset_name'   => $names[$asset_type],
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
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $table    = $wpdb->prefix . 'bae_assets';
    $user_id  = get_current_user_id();
    $asset_id = intval($_POST['asset_id'] ?? 0);

    $result = $wpdb->delete($table, ['id' => $asset_id, 'user_id' => $user_id], ['%d', '%d']);

    if ($result) {
        wp_send_json_success(['message' => 'Asset removed.']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete asset.']);
    }
}

function bntm_ajax_bae_save_kit_settings() {
    check_ajax_referer('bae_save_kit_settings', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $table      = $wpdb->prefix . 'bae_profiles';
    $user_id    = get_current_user_id();
    $visibility = sanitize_text_field($_POST['kit_visibility'] ?? 'private');
    $slug       = sanitize_title($_POST['kit_slug'] ?? '');

    if (!in_array($visibility, ['public', 'private'])) {
        wp_send_json_error(['message' => 'Invalid visibility option.']);
    }
    if (empty($slug)) {
        wp_send_json_error(['message' => 'Kit slug cannot be empty.']);
    }

    $conflict = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE kit_slug = %s AND user_id != %d",
        $slug, $user_id
    ));

    if ($conflict) {
        wp_send_json_error(['message' => 'That slug is already taken. Please choose another.']);
    }

    $result = $wpdb->update($table, [
        'kit_visibility' => $visibility,
        'kit_slug'       => $slug,
    ], ['user_id' => $user_id]);

    wp_send_json_success(['message' => 'Kit settings saved successfully!']);
}

function bntm_ajax_bae_reset_profile() {
    check_ajax_referer('bae_reset_profile', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id        = get_current_user_id();
    $profiles_table = $wpdb->prefix . 'bae_profiles';
    $assets_table   = $wpdb->prefix . 'bae_assets';

    $profile = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$profiles_table} WHERE user_id = %d", $user_id));

    if ($profile) {
        $wpdb->delete($assets_table,   ['user_id' => $user_id], ['%d']);
        $wpdb->delete($profiles_table, ['user_id' => $user_id], ['%d']);
    }

    wp_send_json_success(['message' => 'Brand profile and all assets have been reset.']);
}

// =============================================================================
// EXPORT ZIP — Download all generated assets as individual HTML files in a ZIP
// =============================================================================
function bntm_ajax_bae_export_zip() {
    check_ajax_referer('bae_reset_profile', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id      = get_current_user_id();
    $assets_table = $wpdb->prefix . 'bae_assets';
    $profile      = bae_get_profile($user_id);

    if (!$profile) {
        wp_send_json_error(['message' => 'No brand profile found.']);
    }

    $assets = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$assets_table} WHERE user_id = %d AND is_generated = 1",
        $user_id
    ), ARRAY_A);

    if (empty($assets)) {
        wp_send_json_error(['message' => 'No assets generated yet.']);
    }

    // Build ZIP in memory using PHP's ZipArchive
    if (!class_exists('ZipArchive')) {
        wp_send_json_error(['message' => 'ZIP export not supported on this server.']);
    }

    $biz_slug = sanitize_title($profile['business_name'] ?? 'brand');
    $zip_file = sys_get_temp_dir() . '/bae_' . $user_id . '_' . time() . '.zip';
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
    $readme .= "Generated by Brand Asset Engine\n";
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
    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $user_id), ARRAY_A);
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

function bae_count_assets($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_assets';
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_generated = 1",
        $user_id
    ));
}

function bae_get_initials($name) {
    $words    = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials ?: 'BR';
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


// =============================================================================
// TAB: STARTUP TOOLKIT
// Full dedicated tab — domain, handles, taglines, email, launch checklist
// =============================================================================

function bae_startup_tab($user_id, $profile) {
    if (empty($profile)) {
        ob_start();
        ?>
        <div class="bae-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7a6 6 0 11-5.398-5.398"/>
            </svg>
            <strong>No brand profile yet.</strong>
            <p>Complete your <a href="?tab=overview">Brand Profile</a> first to access the Startup Toolkit.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    $p         = $profile;
    $biz_raw   = $p['business_name'] ?? 'yourbusiness';
    $biz_slug  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $biz_raw));
    $biz_words = explode(' ', strtolower(trim($biz_raw)));
    $biz_short = $biz_words[0] ?? $biz_slug;
    $industry  = $p['industry'] ?? 'Other';

    $domains = [
        ['domain' => $biz_slug . '.com',          'type' => '.com',   'rec' => true,  'note' => 'Best for global reach'],
        ['domain' => $biz_slug . '.ph',           'type' => '.ph',    'rec' => true,  'note' => 'Best for PH local market'],
        ['domain' => $biz_slug . 'ph.com',        'type' => '.com',   'rec' => false, 'note' => 'If .com is taken'],
        ['domain' => 'get' . $biz_slug . '.com',  'type' => '.com',   'rec' => false, 'note' => 'Action-oriented'],
        ['domain' => $biz_short . 'store.com',    'type' => '.com',   'rec' => false, 'note' => 'Good for e-commerce'],
        ['domain' => $biz_slug . '.shop',         'type' => '.shop',  'rec' => false, 'note' => 'New TLD, retail-focused'],
        ['domain' => $biz_slug . 'online.com',    'type' => '.com',   'rec' => false, 'note' => 'Emphasizes online presence'],
        ['domain' => 'the' . $biz_slug . '.com',  'type' => '.com',   'rec' => false, 'note' => 'Classic brand framing'],
    ];

    $social_svgs = [
        'fb' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
        'ig' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
        'tt' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.75a4.85 4.85 0 0 1-1.01-.06z"/></svg>',
        'yt' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>',
        'x'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.742l7.741-8.851L1.254 2.25H8.08l4.265 5.638L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'li' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>',
    ];

    $handles = [
        ['platform' => 'Facebook',   'icon' => 'fb',  'handle' => '@' . $biz_slug, 'color' => '#1877f2'],
        ['platform' => 'Instagram',  'icon' => 'ig',  'handle' => '@' . $biz_slug, 'color' => '#e1306c'],
        ['platform' => 'TikTok',     'icon' => 'tt',  'handle' => '@' . $biz_slug, 'color' => '#010101'],
        ['platform' => 'YouTube',    'icon' => 'yt',  'handle' => '@' . $biz_slug, 'color' => '#ff0000'],
        ['platform' => 'X (Twitter)','icon' => 'x',   'handle' => '@' . $biz_slug, 'color' => '#000000'],
        ['platform' => 'LinkedIn',   'icon' => 'li',  'handle' => $biz_slug,        'color' => '#0077b5'],
    ];

    $tagline_variants = bae_generate_tagline_variants($p['business_name'], $industry, $p['tagline'] ?? '');

    $email_ideas = [
        'hello@' . $biz_slug . '.com'   => 'Friendly first impression — great for customer-facing',
        'info@' . $biz_slug . '.com'    => 'Standard and professional — safe choice',
        'contact@' . $biz_slug . '.com' => 'Clearly a contact email — good for forms',
        $biz_slug . '@gmail.com'        => 'Free option — use if you don\'t have a domain yet',
    ];

    $pages = bae_get_industry_sitemap($industry, $biz_raw);

    // Launch checklist
    $checklist = [
        'Domain'    => [
            ['task' => 'Register your primary domain name (.com or .ph)', 'tools' => 'Namecheap, GoDaddy, dot.ph'],
            ['task' => 'Set up domain email (e.g. hello@yourbrand.com)',  'tools' => 'Google Workspace, Zoho Mail'],
            ['task' => 'Point domain to your website or hosting',         'tools' => 'Cloudflare, cPanel'],
        ],
        'Social'    => [
            ['task' => 'Register your brand handle on all major platforms', 'tools' => 'Facebook, Instagram, TikTok, YouTube'],
            ['task' => 'Set consistent profile photo and cover image',      'tools' => 'Use your Brand Kit assets'],
            ['task' => 'Write consistent bio/description across platforms', 'tools' => 'Use your tagline + website URL'],
        ],
        'Website'   => [
            ['task' => 'Set up your website with essential pages',         'tools' => 'WordPress, Wix, or Framer'],
            ['task' => 'Submit your sitemap to Google Search Console',     'tools' => 'Google Search Console (free)'],
            ['task' => 'Install Google Analytics for visitor tracking',    'tools' => 'Google Analytics 4 (free)'],
            ['task' => 'Add SSL certificate (https://)',                   'tools' => 'Included with most hosting'],
        ],
        'Brand'     => [
            ['task' => 'Download and save your Brand Book',               'tools' => 'Generated in Asset Generator tab'],
            ['task' => 'Share your Brand Kit link with designers/vendors','tools' => 'Brand Kit tab → make public'],
            ['task' => 'Create a Google Business Profile',                'tools' => 'business.google.com (free)'],
        ],
        'Legal'     => [
            ['task' => 'Register your business name with DTI or SEC',     'tools' => 'DTI Business Name Registration'],
            ['task' => 'Get your Barangay Business Permit',               'tools' => 'Your local Barangay Hall'],
            ['task' => 'Apply for Mayor\'s Permit (Business License)',    'tools' => 'Your City/Municipal Hall'],
            ['task' => 'Register with BIR for tax compliance',            'tools' => 'BIR (bir.gov.ph)'],
        ],
    ];

    ob_start();
    ?>
    <div style="margin-bottom:24px;">
        <div class="bae-card-title" style="font-size:20px;">Launch Toolkit</div>
        <div class="bae-card-desc" style="margin-top:6px;">Everything you need to launch <?php echo esc_html($biz_raw); ?> — from domain to legal registration. All generated from your brand profile.</div>
    </div>

    <!-- SECTION: Domain Names -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Domain Name Ideas</div>
                <div class="bae-card-desc">Suggested domain names based on your business name. Check availability before registering.</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;">
            <?php foreach ($domains as $d): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid <?php echo $d['rec'] ? '#111827' : '#e5e7eb'; ?>;border-radius:10px;background:<?php echo $d['rec'] ? '#111827' : '#fff'; ?>;">
                <div>
                    <div style="font-size:14px;font-weight:600;color:<?php echo $d['rec'] ? '#fff' : '#374151'; ?>;font-family:'Courier New',monospace;"><?php echo esc_html($d['domain']); ?></div>
                    <div style="font-size:11px;color:<?php echo $d['rec'] ? 'rgba(255,255,255,0.5)' : '#9ca3af'; ?>;margin-top:2px;"><?php echo esc_html($d['note']); ?></div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <?php if ($d['rec']): ?><span style="font-size:9px;background:var(--surface);color:var(--text);padding:2px 8px;border-radius:999px;font-weight:800;">TOP</span><?php endif; ?>
                    <button onclick="navigator.clipboard.writeText('<?php echo esc_js($d['domain']); ?>');this.textContent='Done';setTimeout(()=>this.textContent='Copy',1500);"
                            style="border:1px solid <?php echo $d['rec'] ? 'rgba(255,255,255,0.3)' : '#d1d5db'; ?>;background:none;color:<?php echo $d['rec'] ? '#fff' : '#6b7280'; ?>;font-size:11px;cursor:pointer;padding:4px 10px;border-radius:6px;">Copy</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="bae-notice bae-notice-info" style="margin-top:16px;">
            Check availability at <strong><a href="https://www.namecheap.com" target="_blank">Namecheap.com</a></strong> or <strong><a href="https://www.dot.ph" target="_blank">dot.ph</a></strong> for .ph domains. Average cost: ₱600–₱1,200/year for .com, ₱1,500–₱2,000/year for .ph.
        </div>
    </div>

    <!-- SECTION: Social Handles -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Social Media Handles</div>
                <div class="bae-card-desc">Register the same handle on every platform — consistency is a brand asset.</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;">
            <?php foreach ($handles as $h): ?>
            <div style="padding:14px 16px;border:1px solid var(--border);border-radius:10px;background:var(--surface);transition:border-color .2s;" onmouseenter="this.style.borderColor='var(--border-2)'" onmouseleave="this.style.borderColor='var(--border)'">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div style="width:28px;height:28px;border-radius:7px;background:<?php echo esc_attr($h['color']); ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:white;">
                        <?php echo $social_svgs[$h['icon']] ?? ''; ?>
                    </div>
                    <div style="font-size:12px;font-weight:600;color:var(--text-2);"><?php echo esc_html($h['platform']); ?></div>
                </div>
                <div style="font-size:13px;font-weight:700;color:var(--text);font-family:'Courier New',monospace;"><?php echo esc_html($h['handle']); ?></div>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($h['handle']); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500);"
                        style="margin-top:8px;border:1px solid var(--border-2);background:none;color:var(--text-3);font-size:11px;cursor:pointer;padding:4px 10px;border-radius:6px;width:100%;font-family:'Geist',sans-serif;transition:all .2s;">Copy</button>
            </div>
            <?php endforeach; ?>
        </div>
        </div>
        <div class="bae-notice bae-notice-info" style="margin-top:16px;">
            Register all handles even if you're not active on every platform yet — prevents brand squatting and ensures consistency when you scale.
        </div>
    </div>

    <!-- SECTION: Tagline Variants -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Tagline Variants</div>
                <div class="bae-card-desc">Use these across your website, social bios, packaging, and marketing materials.</div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($tagline_variants as $i => $variant): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border:1px solid <?php echo $i === 0 ? '#111827' : '#e5e7eb'; ?>;border-radius:10px;background:<?php echo $i === 0 ? '#fafafa' : '#fff'; ?>;">
                <div>
                    <span style="font-size:15px;color:var(--text-2);font-style:italic;">"<?php echo esc_html($variant); ?>"</span>
                    <?php if ($i === 0 && !empty($p['tagline'])): ?><span style="margin-left:10px;font-size:10px;background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:999px;font-weight:700;">CURRENT</span><?php endif; ?>
                </div>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($variant); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500);"
                        style="border:1px solid var(--border-2);background:none;color:var(--text-3);font-size:11px;cursor:pointer;padding:5px 12px;border-radius:6px;white-space:nowrap;">Copy</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SECTION: Business Email -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Business Email Suggestions</div>
                <div class="bae-card-desc">A professional email builds trust immediately. Avoid using personal Gmail for business communications.</div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($email_ideas as $email => $note): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid var(--border);border-radius:10px;">
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--text-2);font-family:'Courier New',monospace;"><?php echo esc_html($email); ?></div>
                    <div style="font-size:12px;color:var(--text-3);margin-top:3px;"><?php echo esc_html($note); ?></div>
                </div>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($email); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500);"
                        style="border:1px solid var(--border-2);background:none;color:var(--text-3);font-size:11px;cursor:pointer;padding:5px 12px;border-radius:6px;">Copy</button>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="bae-notice bae-notice-info" style="margin-top:16px;">
            Get a professional email at <strong>Google Workspace</strong> (~₱280/mo) or free with <strong>Zoho Mail</strong>. Requires a registered domain.
        </div>
    </div>

    <!-- SECTION: Recommended Site Pages -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Recommended Website Pages</div>
                <div class="bae-card-desc">Based on your industry (<?php echo esc_html($industry); ?>), these are the pages your website needs.</div>
            </div>
            <a href="?tab=assets" class="bae-btn bae-btn-outline bae-btn-sm">Generate Full Sitemap →</a>
        </div>
        <div style="display:flex;flex-direction:column;gap:0;border:1px solid var(--border);border-radius:10px;overflow:hidden;">
            <?php foreach ($pages as $i => $page):
                $bg = $i % 2 === 0 ? '#fff' : '#fafafa';
                $priority_bg = $page['priority'] === 'high' ? '#d1fae5' : ($page['priority'] === 'medium' ? '#fef3c7' : '#f3f4f6');
                $priority_txt = $page['priority'] === 'high' ? '#065f46' : ($page['priority'] === 'medium' ? '#92400e' : '#6b7280');
            ?>
            <div style="display:grid;grid-template-columns:36px 130px 60px 1fr;gap:0;background:<?php echo $bg; ?>;border-bottom:1px solid #f3f4f6;align-items:center;">
                <div style="padding:12px;display:flex;align-items:center;justify-content:center;color:var(--text-2);"><?php echo $page['icon']; ?></div>
                <div style="padding:10px 8px;font-size:13px;font-weight:600;color:var(--text);"><?php echo esc_html($page['name']); ?></div>
                <div style="padding:10px 8px;">
                    <span style="font-size:10px;padding:2px 8px;border-radius:999px;background:<?php echo $priority_bg; ?>;color:<?php echo $priority_txt; ?>;font-weight:700;"><?php echo ucfirst($page['priority']); ?></span>
                </div>
                <div style="padding:10px 16px;font-size:12px;color:var(--text-3);line-height:1.6;"><?php echo esc_html($page['desc']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SECTION: Launch Checklist -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Launch Checklist for <?php echo esc_html($biz_raw); ?></div>
                <div class="bae-card-desc">Complete these steps to officially launch your business online and legally in the Philippines.</div>
            </div>
        </div>
        <?php foreach ($checklist as $section => $tasks): ?>
        <div style="margin-bottom:24px;">
            <div class="bae-section-label" style="margin-bottom:10px;"><?php echo $section; ?></div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <?php foreach ($tasks as $task): ?>
                <div class="bae-checklist-item" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:12px 14px;border:1px solid var(--border);border-radius:8px;background:var(--surface);transition:background .2s;">
                    <div style="display:flex;align-items:flex-start;gap:10px;flex:1;">
                        <input type="checkbox"
                               class="bae-checklist-cb"
                               data-key="bae_chk_<?php echo esc_attr(sanitize_title($task['task'])); ?>"
                               style="margin-top:3px;width:16px;height:16px;cursor:pointer;flex-shrink:0;accent-color:var(--brand-soft);">
                        <div>
                            <div class="bae-checklist-label" style="font-size:13px;color:var(--text-2);font-weight:500;"><?php echo esc_html($task['task']); ?></div>
                            <div style="font-size:11px;color:var(--text-3);margin-top:3px;"><?php echo esc_html($task['tools']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    (function() {
        // Restore checklist state from localStorage
        var cbs = document.querySelectorAll('.bae-checklist-cb');
        cbs.forEach(function(cb) {
            var key = cb.dataset.key;
            if (!key) return;
            // Restore
            if (localStorage.getItem(key) === '1') {
                cb.checked = true;
                var item = cb.closest('.bae-checklist-item');
                var label = item ? item.querySelector('.bae-checklist-label') : null;
                if (item)  item.style.background  = 'var(--bg-3)';
                if (label) label.style.textDecoration = 'line-through';
                if (label) label.style.opacity = '0.5';
            }
            // Save on change
            cb.addEventListener('change', function() {
                localStorage.setItem(key, cb.checked ? '1' : '0');
                var item  = cb.closest('.bae-checklist-item');
                var label = item ? item.querySelector('.bae-checklist-label') : null;
                if (item)  item.style.background       = cb.checked ? 'var(--bg-3)' : 'var(--surface)';
                if (label) label.style.textDecoration  = cb.checked ? 'line-through' : 'none';
                if (label) label.style.opacity         = cb.checked ? '0.5' : '1';
            });
        });
    })();
    </script>

    <?php
    return ob_get_clean();
}


// =============================================================================
// RENDER HELPERS for brand_book and sitemap (PHP 7.0+ compatible)
// =============================================================================

function bae_render_tone_pills($tone_tags, $pc) {
    $out = '';
    foreach ($tone_tags as $t) {
        $out .= "<span style='padding:8px 18px;background:{$pc};color:#fff;border-radius:999px;font-size:13px;font-weight:600;'>" . esc_html($t) . "</span>";
    }
    return $out;
}

function bae_render_logo_rules($pc, $fh) {
    $rules = [
        'Always use official brand colors — no substitutions or approximations.',
        'Use the heading font (' . $fh . ') for all titles and brand copy.',
        'Maintain clear space around the logo equal to the cap-height of the logotype.',
        'Do not stretch, rotate, skew, or add effects to the logo.',
        'Ensure minimum contrast ratio of 4.5:1 when placing logo on any background.',
        'Do not place the logo on busy photographic backgrounds without a clear backdrop.',
    ];
    $out = '';
    foreach ($rules as $i => $rule) {
        $num = $i + 1;
        $out .= "<div style='display:flex;gap:14px;padding:14px;border:1px solid #e5e7eb;border-radius:10px;'>"
              . "<span style='width:28px;height:28px;background:{$pc};color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;'>{$num}</span>"
              . "<div style='font-size:13px;color:#374151;line-height:1.6;padding-top:4px;'>" . esc_html($rule) . "</div>"
              . "</div>";
    }
    return $out;
}

function bae_render_sitemap_connectors($pages) {
    $out = '';
    foreach ($pages as $page) {
        $out .= "<div style='width:2px;height:20px;background:#d1d5db;margin:0 auto;'></div>";
    }
    return $out;
}

function bae_render_sitemap_cards($pages, $pc, $sc, $ac, $domain_sm) {
    $out = '';
    foreach ($pages as $page) {
        $color     = $page['priority'] === 'high' ? $pc : ($page['priority'] === 'medium' ? $sc : '#6b7280');
        $badge_bg  = $page['priority'] === 'high' ? '#d1fae5' : ($page['priority'] === 'medium' ? '#fef3c7' : '#f3f4f6');
        $badge_txt = $page['priority'] === 'high' ? '#065f46' : ($page['priority'] === 'medium' ? '#92400e' : '#6b7280');
        $prio_label = ucfirst($page['priority']);
        $out .= "<div style='display:flex;flex-direction:column;align-items:center;min-width:100px;max-width:130px;'>"
              . "<div style='border:2px solid {$color};border-radius:8px;padding:10px 12px;text-align:center;width:100%;background:#fff;'>"
              . "<div style='display:flex;align-items:center;justify-content:center;margin-bottom:6px;color:#6b7280;'>{$page['icon']}</div>"
              . "<div style='font-size:12px;font-weight:600;color:#111827;'>" . esc_html($page['name']) . "</div>"
              . "<div style='font-size:10px;color:#9ca3af;margin-top:2px;font-family:monospace;'>{$domain_sm}" . esc_html($page['slug']) . "</div>"
              . "</div>"
              . "<span style='margin-top:6px;font-size:10px;padding:2px 8px;border-radius:999px;background:{$badge_bg};color:{$badge_txt};font-weight:600;'>{$prio_label}</span>"
              . "</div>";
    }
    return $out;
}

function bae_render_sitemap_table($pages) {
    $out = '';
    $i = 0;
    foreach ($pages as $page) {
        $bg = $i % 2 === 0 ? '#fff' : '#fafafa';
        $out .= "<div style='display:grid;grid-template-columns:32px 120px 1fr;gap:0;background:{$bg};border-bottom:1px solid #f3f4f6;'>"
              . "<div style='padding:12px;display:flex;align-items:center;justify-content:center;color:#6b7280;'>{$page['icon']}</div>"
              . "<div style='padding:12px 8px;font-size:13px;font-weight:600;color:#111827;border-right:1px solid #f3f4f6;'>" . esc_html($page['name']) . "</div>"
              . "<div style='padding:12px 16px;font-size:12px;color:#6b7280;line-height:1.6;'>" . esc_html($page['desc']) . "</div>"
              . "</div>";
        $i++;
    }
    return $out;
}

function bae_render_xml_sitemap($pages, $domain_sm) {
    $entries = [];
    foreach ($pages as $page) {
        $priority = $page['priority'] === 'high' ? '1.0' : ($page['priority'] === 'medium' ? '0.8' : '0.6');
        $entries[] = "&nbsp;&nbsp;<span style='color:#cba6f7;'>&lt;url&gt;</span><br>"
                   . "&nbsp;&nbsp;&nbsp;&nbsp;<span style='color:#f9e2af;'>&lt;loc&gt;</span>{$domain_sm}" . esc_html($page['slug']) . "<span style='color:#f9e2af;'>&lt;/loc&gt;</span><br>"
                   . "&nbsp;&nbsp;&nbsp;&nbsp;<span style='color:#f9e2af;'>&lt;priority&gt;</span>{$priority}<span style='color:#f9e2af;'>&lt;/priority&gt;</span><br>"
                   . "&nbsp;&nbsp;<span style='color:#cba6f7;'>&lt;/url&gt;</span>";
    }
    return implode('<br>', $entries);
}

// =============================================================================
// BRAND BOOK TAB — 50 templates, 12-page preview, save/print
// =============================================================================

function bae_get_book_templates() {
    return array(
        // FREE (5)
        'modern_elegant'      => array('name'=>'Modern Elegant',      'cat'=>'Free',    'cb'=>'#1e1b4b','ct'=>'#e0e7ff','acc'=>'#818cf8','pb'=>'#ffffff','pt'=>'#1e1b4b','pm'=>'#6b7280'),
        'fresh_basic'         => array('name'=>'Fresh Basic',         'cat'=>'Free',    'cb'=>'#f0fdf4','ct'=>'#166534','acc'=>'#22c55e','pb'=>'#ffffff','pt'=>'#1f2937','pm'=>'#6b7280'),
        'pop_of_pink'         => array('name'=>'Pop of Pink',         'cat'=>'Free',    'cb'=>'#ec4899','ct'=>'#ffffff','acc'=>'#f9a8d4','pb'=>'#ffffff','pt'=>'#1f2937','pm'=>'#6b7280'),
        'effervescent'        => array('name'=>'Effervescent',        'cat'=>'Free',    'cb'=>'#0f172a','ct'=>'#f8fafc','acc'=>'#38bdf8','pb'=>'#f8fafc','pt'=>'#0f172a','pm'=>'#64748b'),
        'detailed_minimalist' => array('name'=>'Detailed Minimalist', 'cat'=>'Free',    'cb'=>'#fafafa','ct'=>'#111827','acc'=>'#111827','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#9ca3af'),
        // MODERN (20)
        'simply_chic'         => array('name'=>'Simply Chic',         'cat'=>'Modern',  'cb'=>'#0f766e','ct'=>'#ffffff','acc'=>'#5eead4','pb'=>'#ffffff','pt'=>'#134e4a','pm'=>'#6b7280'),
        'white_blue'          => array('name'=>'White & Blue',        'cat'=>'Modern',  'cb'=>'#1d4ed8','ct'=>'#ffffff','acc'=>'#93c5fd','pb'=>'#ffffff','pt'=>'#1e3a8a','pm'=>'#6b7280'),
        'white_apricot'       => array('name'=>'White & Apricot',     'cat'=>'Modern',  'cb'=>'#fb923c','ct'=>'#ffffff','acc'=>'#fed7aa','pb'=>'#fffbf5','pt'=>'#9a3412','pm'=>'#9ca3af'),
        'structured'          => array('name'=>'The Structured',      'cat'=>'Modern',  'cb'=>'#4f46e5','ct'=>'#ffffff','acc'=>'#6ee7b7','pb'=>'#ffffff','pt'=>'#312e81','pm'=>'#6b7280'),
        'green_elegance'      => array('name'=>'Green Elegance',      'cat'=>'Modern',  'cb'=>'#14532d','ct'=>'#f0fdf4','acc'=>'#86efac','pb'=>'#ffffff','pt'=>'#14532d','pm'=>'#6b7280'),
        'pastel_themed'       => array('name'=>'Pastel Themed',       'cat'=>'Modern',  'cb'=>'#fdf2f8','ct'=>'#831843','acc'=>'#f9a8d4','pb'=>'#fdf2f8','pt'=>'#831843','pm'=>'#be185d'),
        'black_green'         => array('name'=>'Black & Green',       'cat'=>'Modern',  'cb'=>'#111827','ct'=>'#f9fafb','acc'=>'#4ade80','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'bold_beautiful'      => array('name'=>'Bold & Beautiful',    'cat'=>'Modern',  'cb'=>'#000000','ct'=>'#ffffff','acc'=>'#22c55e','pb'=>'#ffffff','pt'=>'#000000','pm'=>'#6b7280'),
        'simply_red'          => array('name'=>'Simply Red',          'cat'=>'Modern',  'cb'=>'#dc2626','ct'=>'#ffffff','acc'=>'#fca5a5','pb'=>'#ffffff','pt'=>'#7f1d1d','pm'=>'#6b7280'),
        'red_black_white'     => array('name'=>'Red Black White',     'cat'=>'Modern',  'cb'=>'#000000','ct'=>'#ffffff','acc'=>'#ef4444','pb'=>'#ffffff','pt'=>'#000000','pm'=>'#6b7280'),
        'touch_yellow'        => array('name'=>'Touch of Yellow',     'cat'=>'Modern',  'cb'=>'#854d0e','ct'=>'#fefce8','acc'=>'#fbbf24','pb'=>'#fffbeb','pt'=>'#78350f','pm'=>'#92400e'),
        'green_minimalist'    => array('name'=>'Green Minimalist',    'cat'=>'Modern',  'cb'=>'#ecfdf5','ct'=>'#065f46','acc'=>'#059669','pb'=>'#ffffff','pt'=>'#064e3b','pm'=>'#6b7280'),
        'beige_boldness'      => array('name'=>'Beige Boldness',      'cat'=>'Modern',  'cb'=>'#d4a574','ct'=>'#ffffff','acc'=>'#92400e','pb'=>'#fdf8f0','pt'=>'#78350f','pm'=>'#a16207'),
        'gray_coral'          => array('name'=>'Gray & Coral',        'cat'=>'Modern',  'cb'=>'#374151','ct'=>'#f9fafb','acc'=>'#f87171','pb'=>'#f9fafb','pt'=>'#111827','pm'=>'#6b7280'),
        'earth_toned'         => array('name'=>'Earth Toned',         'cat'=>'Modern',  'cb'=>'#57301e','ct'=>'#fef9f5','acc'=>'#d4a574','pb'=>'#fef9f5','pt'=>'#3b1a0a','pm'=>'#92400e'),
        'elite_portfolio'     => array('name'=>'Elite Portfolio',     'cat'=>'Modern',  'cb'=>'#022c22','ct'=>'#ecfdf5','acc'=>'#6ee7b7','pb'=>'#ffffff','pt'=>'#022c22','pm'=>'#6b7280'),
        'black_yellow'        => array('name'=>'Black & Yellow',      'cat'=>'Modern',  'cb'=>'#111827','ct'=>'#fefce8','acc'=>'#facc15','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'bold_red'            => array('name'=>'Bold Red',            'cat'=>'Modern',  'cb'=>'#991b1b','ct'=>'#fff1f2','acc'=>'#fca5a5','pb'=>'#fff1f2','pt'=>'#7f1d1d','pm'=>'#6b7280'),
        'pale_peach'          => array('name'=>'Pale Peach',          'cat'=>'Modern',  'cb'=>'#fcd9bd','ct'=>'#7c3d2c','acc'=>'#ea580c','pb'=>'#fff7f0','pt'=>'#7c3d2c','pm'=>'#c2410c'),
        'blue_dominance'      => array('name'=>'Blue Dominance',      'cat'=>'Modern',  'cb'=>'#1e40af','ct'=>'#eff6ff','acc'=>'#60a5fa','pb'=>'#f0f7ff','pt'=>'#1e3a8a','pm'=>'#3b82f6'),
        // ONE PAGE (9)
        'light_dark'          => array('name'=>'Light & Dark',        'cat'=>'One Page','cb'=>'#18181b','ct'=>'#f4f4f5','acc'=>'#a78bfa','pb'=>'#18181b','pt'=>'#f4f4f5','pm'=>'#a1a1aa'),
        'minimal_muted'       => array('name'=>'Minimal Muted',       'cat'=>'One Page','cb'=>'#f1f5f9','ct'=>'#334155','acc'=>'#94a3b8','pb'=>'#f8fafc','pt'=>'#334155','pm'=>'#94a3b8'),
        'blue_red_brand'      => array('name'=>'Blue & Red Brand',    'cat'=>'One Page','cb'=>'#1e40af','ct'=>'#ffffff','acc'=>'#ef4444','pb'=>'#ffffff','pt'=>'#1e3a8a','pm'=>'#6b7280'),
        'tan_accent'          => array('name'=>'Tan Accent',          'cat'=>'One Page','cb'=>'#fafaf9','ct'=>'#292524','acc'=>'#d6b896','pb'=>'#fafaf9','pt'=>'#292524','pm'=>'#78716c'),
        'orange_accents'      => array('name'=>'Orange Accents',      'cat'=>'One Page','cb'=>'#fff7ed','ct'=>'#9a3412','acc'=>'#ea580c','pb'=>'#ffffff','pt'=>'#431407','pm'=>'#7c3d12'),
        'fair_square'         => array('name'=>'Fair & Square',       'cat'=>'One Page','cb'=>'#7c3aed','ct'=>'#f5f3ff','acc'=>'#c4b5fd','pb'=>'#f5f3ff','pt'=>'#4c1d95','pm'=>'#7c3aed'),
        'basic_corporate'     => array('name'=>'Basic Corporate',     'cat'=>'One Page','cb'=>'#1f2937','ct'=>'#f9fafb','acc'=>'#fbbf24','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'red_font'            => array('name'=>'Red Font',            'cat'=>'One Page','cb'=>'#ffffff','ct'=>'#111827','acc'=>'#dc2626','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'straightforward'     => array('name'=>'Straightforward',     'cat'=>'One Page','cb'=>'#ffffff','ct'=>'#111827','acc'=>'#f97316','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        // CREATIVE (9)
        'pink_bw'             => array('name'=>'Pink on BW',          'cat'=>'Creative','cb'=>'#000000','ct'=>'#ffffff','acc'=>'#f9a8d4','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'red_bw'              => array('name'=>'Red on BW',           'cat'=>'Creative','cb'=>'#111827','ct'=>'#f9fafb','acc'=>'#ef4444','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'simply_creative'     => array('name'=>'Simply Creative',     'cat'=>'Creative','cb'=>'#065f46','ct'=>'#ecfdf5','acc'=>'#f87171','pb'=>'#ffffff','pt'=>'#064e3b','pm'=>'#6b7280'),
        'classic_pro'         => array('name'=>'Classic Professional','cat'=>'Creative','cb'=>'#1a1a2e','ct'=>'#e0e7ff','acc'=>'#e94560','pb'=>'#ffffff','pt'=>'#1a1a2e','pm'=>'#6b7280'),
        'stylish_orange'      => array('name'=>'Stylish Orange',      'cat'=>'Creative','cb'=>'#c2410c','ct'=>'#fff7ed','acc'=>'#fb923c','pb'=>'#fff7ed','pt'=>'#7c2d12','pm'=>'#9a3412'),
        'black_pink'          => array('name'=>'Black & Pink',        'cat'=>'Creative','cb'=>'#18181b','ct'=>'#fdf4ff','acc'=>'#e879f9','pb'=>'#ffffff','pt'=>'#18181b','pm'=>'#71717a'),
        'vibrant_sunset'      => array('name'=>'Vibrant Sunset',      'cat'=>'Creative','cb'=>'#dc2626','ct'=>'#fff7ed','acc'=>'#fbbf24','pb'=>'#fff7ed','pt'=>'#78350f','pm'=>'#9a3412'),
        'neon_dark'           => array('name'=>'Neon Dark',           'cat'=>'Creative','cb'=>'#09090b','ct'=>'#f4f4f5','acc'=>'#a3e635','pb'=>'#09090b','pt'=>'#f4f4f5','pm'=>'#71717a'),
        'ocean_breeze'        => array('name'=>'Ocean Breeze',        'cat'=>'Creative','cb'=>'#0c4a6e','ct'=>'#f0f9ff','acc'=>'#38bdf8','pb'=>'#f0f9ff','pt'=>'#0c4a6e','pm'=>'#0284c7'),
        // MINIMAL (7)
        'swiss_grid'          => array('name'=>'Swiss Grid',          'cat'=>'Minimal', 'cb'=>'#ffffff','ct'=>'#000000','acc'=>'#ff0000','pb'=>'#ffffff','pt'=>'#000000','pm'=>'#737373'),
        'paper_white'         => array('name'=>'Paper White',         'cat'=>'Minimal', 'cb'=>'#fafaf9','ct'=>'#1c1917','acc'=>'#78716c','pb'=>'#fafaf9','pt'=>'#1c1917','pm'=>'#a8a29e'),
        'monochrome'          => array('name'=>'Monochrome',          'cat'=>'Minimal', 'cb'=>'#000000','ct'=>'#ffffff','acc'=>'#ffffff','pb'=>'#ffffff','pt'=>'#000000','pm'=>'#737373'),
        'sage_minimal'        => array('name'=>'Sage Minimal',        'cat'=>'Minimal', 'cb'=>'#86a390','ct'=>'#ffffff','acc'=>'#4a7c6b','pb'=>'#f9faf8','pt'=>'#2d3b35','pm'=>'#7a9187'),
        'slate_clean'         => array('name'=>'Slate Clean',         'cat'=>'Minimal', 'cb'=>'#475569','ct'=>'#f8fafc','acc'=>'#94a3b8','pb'=>'#f8fafc','pt'=>'#1e293b','pm'=>'#64748b'),
        'ink_minimal'         => array('name'=>'Ink Minimal',         'cat'=>'Minimal', 'cb'=>'#0f172a','ct'=>'#f8fafc','acc'=>'#e2e8f0','pb'=>'#ffffff','pt'=>'#0f172a','pm'=>'#64748b'),
        'lavender_soft'       => array('name'=>'Lavender Soft',       'cat'=>'Minimal', 'cb'=>'#7c3aed','ct'=>'#faf5ff','acc'=>'#c4b5fd','pb'=>'#faf5ff','pt'=>'#4c1d95','pm'=>'#7c3aed'),
    );
}

function bae_render_book_pages($p, $tpl) {
    $name    = esc_html($p['business_name']  ?? 'Your Brand');
    $tagline = esc_html($p['tagline']        ?? '');
    $industry= esc_html($p['industry']       ?? '');
    $fh      = esc_attr($p['font_heading']   ?? 'Inter');
    $fb      = esc_attr($p['font_body']      ?? 'Inter');
    $pc      = bae_safe_color($p['primary_color']   ?? '', '#1a1a2e');
    $sc      = bae_safe_color($p['secondary_color'] ?? '', '#16213e');
    $ac      = bae_safe_color($p['accent_color']    ?? '', '#e94560');
    $email   = esc_html($p['email']   ?? '');
    $website = esc_html($p['website'] ?? '');
    $phone   = esc_html($p['phone']   ?? '');
    $ini     = bae_get_initials($name);

    $cb = $tpl['cb']; $ct = $tpl['ct']; $ta = $tpl['acc'];
    $pb = $tpl['pb']; $pt = $tpl['pt']; $pm = $tpl['pm'];

    $cs = 'background:'.$cb.';color:'.$ct.';font-family:\''.$fb.'\',sans-serif;';
    $ps = 'background:'.$pb.';color:'.$pt.';font-family:\''.$fb.'\',sans-serif;';
    $o  = '';

    // P1 Cover
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$cs.'display:flex;flex-direction:column;justify-content:space-between;">';
    $o .= '<div style="font-size:9px;letter-spacing:.2em;text-transform:uppercase;opacity:.4;">Brand Guidelines</div>';
    $o .= '<div><div style="font-family:\''.$fh.'\',sans-serif;font-size:2.2em;font-weight:700;line-height:1.1;margin-bottom:8px;">'.$name.'</div>';
    $o .= '<div style="font-size:.8em;opacity:.65;">'.$tagline.'</div></div>';
    $o .= '<div style="font-size:.6em;opacity:.35;">'.$website.' &copy; '.date('Y').'</div>';
    $o .= '</div></div>';

    // P2 Brand Story
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">01 — Brand Story</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;border-bottom:1px solid rgba(0,0,0,.08);padding-bottom:10px;margin-bottom:12px;">'.$name.'</div>';
    $o .= '<div style="font-size:.72em;color:'.$pm.';margin-bottom:4px;"><strong>Industry:</strong> '.$industry.'</div>';
    if ($tagline) $o .= '<div style="margin-top:12px;padding:10px 12px;border-left:3px solid '.$ta.';background:rgba(0,0,0,.03);font-size:.8em;font-style:italic;">&ldquo;'.$tagline.'&rdquo;</div>';
    $o .= '</div></div>';

    // P3 Colors
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">02 — Colors</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:14px;">Brand Colors</div>';
    foreach (array(array('Primary',$pc),array('Secondary',$sc),array('Accent',$ac)) as $col) {
        $o .= '<div style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">';
        $o .= '<div style="width:44px;height:44px;border-radius:8px;background:'.$col[1].';flex-shrink:0;"></div>';
        $o .= '<div><div style="font-size:.75em;font-weight:600;">'.$col[0].'</div>';
        $o .= '<div style="font-size:.65em;color:'.$pm.';font-family:monospace;">'.strtoupper($col[1]).'</div></div></div>';
    }
    $o .= '</div></div>';

    // P4 Typography
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">03 — Typography</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:14px;">Type System</div>';
    $o .= '<div style="padding:10px;border:1px solid rgba(0,0,0,.08);border-radius:6px;margin-bottom:8px;">';
    $o .= '<div style="font-size:.6em;color:'.$pm.';text-transform:uppercase;margin-bottom:4px;">Heading</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.5em;font-weight:700;color:'.$pc.'">'.$fh.'</div></div>';
    $o .= '<div style="padding:10px;border:1px solid rgba(0,0,0,.08);border-radius:6px;">';
    $o .= '<div style="font-size:.6em;color:'.$pm.';text-transform:uppercase;margin-bottom:4px;">Body</div>';
    $o .= '<div style="font-family:\''.$fb.'\',sans-serif;font-size:1.5em;color:'.$pc.'">'.$fb.'</div></div>';
    $o .= '</div></div>';

    // P5 Tone
    $tone_tags = bae_derive_tone_tags($p['industry'] ?? '', $p['personality'] ?? '');
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">04 — Tone of Voice</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">How We Speak</div>';
    $o .= '<div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px;">';
    foreach ($tone_tags as $tag) $o .= '<span style="padding:3px 9px;background:'.$ta.';color:'.$ct.';border-radius:999px;font-size:.66em;font-weight:600;">'.esc_html($tag).'</span>';
    $o .= '</div>';
    $o .= '<div style="font-size:.68em;color:'.$pm.';line-height:1.6;">'.bae_get_voice_examples($tone_tags, true).'</div>';
    $o .= '</div></div>';

    // P6 Logo Rules
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">05 — Logo Usage</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Usage Rules</div>';
    foreach (array('Always use official brand colors.','Never stretch or rotate the logo.','Maintain clear space equal to logo height.','Ensure 4.5:1 contrast ratio minimum.','Never place on busy backgrounds.') as $i => $rule) {
        $o .= '<div style="display:flex;gap:8px;margin-bottom:8px;">';
        $o .= '<span style="width:18px;height:18px;background:'.$ta.';color:'.$ct.';border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6em;font-weight:700;flex-shrink:0;">'.($i+1).'</span>';
        $o .= '<div style="font-size:.7em;color:'.$pt.';line-height:1.5;">'.$rule.'</div></div>';
    }
    $o .= '</div></div>';

    // P7 Stationery
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">06 — Stationery</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Applications</div>';
    $o .= '<div style="font-size:.6em;color:'.$pm.';text-transform:uppercase;font-weight:700;margin-bottom:6px;">Business Card</div>';
    $o .= '<div style="display:flex;gap:8px;margin-bottom:12px;">';
    $o .= '<div style="width:110px;height:62px;background:'.$pc.';border-radius:5px;padding:8px;display:flex;flex-direction:column;justify-content:space-between;">';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:.65em;font-weight:700;color:#fff;">'.$name.'</div>';
    $o .= '<div style="width:14px;height:2px;background:'.$ac.';"></div></div>';
    $o .= '<div style="width:110px;height:62px;background:#fff;border:1px solid #e5e7eb;border-radius:5px;padding:8px;">';
    $o .= '<div style="font-size:.6em;font-weight:700;color:'.$pc.';margin-bottom:3px;">'.$name.'</div>';
    $o .= '<div style="font-size:.55em;color:#6b7280;">'.$email.'</div>';
    $o .= '<div style="font-size:.55em;color:#6b7280;">'.$phone.'</div></div></div>';
    $o .= '<div style="font-size:.6em;color:'.$pm.';text-transform:uppercase;font-weight:700;margin-bottom:6px;">Email Signature</div>';
    $o .= '<div style="padding:7px;background:#fff;border:1px solid #e5e7eb;border-radius:5px;display:flex;align-items:center;gap:7px;">';
    $o .= '<div style="width:26px;height:26px;border-radius:6px;background:'.$pc.';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    $o .= '<span style="font-size:.65em;font-weight:700;color:#fff;">'.$ini.'</span></div>';
    $o .= '<div><div style="font-size:.65em;font-weight:700;color:'.$pc.'">[Name] &middot; '.$name.'</div>';
    $o .= '<div style="font-size:.58em;color:#6b7280;">'.$email.'</div></div></div>';
    $o .= '</div></div>';

    // P8 Social
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">07 — Social Media</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Social Presence</div>';
    $o .= '<div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;">';
    $o .= '<div style="width:48px;height:48px;border-radius:50%;background:'.$pc.';border:3px solid '.$ac.';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    $o .= '<span style="font-family:\''.$fh.'\',sans-serif;font-size:.9em;font-weight:700;color:#fff;">'.$ini.'</span></div>';
    $o .= '<div><div style="font-size:.75em;font-weight:700;">Profile Photo</div>';
    $o .= '<div style="font-size:.62em;color:'.$pm.';">200x200px &middot; Initials on primary color</div></div></div>';
    $o .= '<div style="width:100%;height:55px;background:'.$pc.';border-radius:5px;padding:8px;display:flex;flex-direction:column;justify-content:flex-end;margin-bottom:6px;">';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:.75em;font-weight:700;color:#fff;">'.$name.'</div>';
    $o .= '<div style="font-size:.58em;color:rgba(255,255,255,.6);">'.$tagline.'</div></div>';
    $o .= '<div style="font-size:.62em;color:'.$pm.';">Cover 820x312px &middot; Primary color background</div>';
    $o .= '</div></div>';

    // P9 Visual Style
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">08 — Visual Style</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Design Principles</div>';
    foreach (array(array('Consistency','Every touchpoint must feel unmistakably '.$name.'.'),array('Clarity','Information hierarchy guides the eye naturally.'),array('Authenticity','Design decisions reflect your brand values.'),array('Proportion','Use whitespace generously for maximum impact.')) as $pr) {
        $o .= '<div style="display:flex;gap:8px;margin-bottom:10px;">';
        $o .= '<div style="width:7px;height:7px;border-radius:50%;background:'.$ta.';flex-shrink:0;margin-top:4px;"></div>';
        $o .= '<div><div style="font-size:.74em;font-weight:700;margin-bottom:2px;">'.$pr[0].'</div>';
        $o .= '<div style="font-size:.66em;color:'.$pm.';line-height:1.5;">'.$pr[1].'</div></div></div>';
    }
    $o .= '</div></div>';

    // P10 Brand Rules
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">09 — Brand Rules</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Do\'s & Don\'ts</div>';
    $o .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">';
    $o .= '<div><div style="font-size:.6em;font-weight:700;color:#065f46;text-transform:uppercase;margin-bottom:5px;">Do</div>';
    foreach (array('Use official brand colors.','Use heading font for titles.','Maintain logo clear space.','Ensure text contrast.') as $d)
        $o .= '<div style="font-size:.66em;color:'.$pt.';padding:3px 0;border-bottom:1px solid rgba(0,0,0,.04);">'.$d.'</div>';
    $o .= '</div><div><div style="font-size:.6em;font-weight:700;color:#991b1b;text-transform:uppercase;margin-bottom:5px;">Don\'t</div>';
    foreach (array('Stretch or rotate the logo.','Use non-approved fonts.','Place on clashing backgrounds.','Use low-res logo files.') as $d)
        $o .= '<div style="font-size:.66em;color:'.$pt.';padding:3px 0;border-bottom:1px solid rgba(0,0,0,.04);">'.$d.'</div>';
    $o .= '</div></div>';
    $o .= '</div></div>';

    // P11 Contact
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">10 — Contact</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Get in Touch</div>';
    if ($email)   $o .= '<div style="font-size:.75em;margin-bottom:5px;">'.$email.'</div>';
    if ($phone)   $o .= '<div style="font-size:.75em;margin-bottom:5px;">'.$phone.'</div>';
    if ($website) $o .= '<div style="font-size:.75em;font-weight:700;color:'.$ta.';margin-bottom:5px;">'.$website.'</div>';
    $o .= '</div></div>';

    // P12 Back Cover
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$cs.'display:flex;flex-direction:column;justify-content:space-between;">';
    $o .= '<div style="font-size:.62em;opacity:.4;text-transform:uppercase;letter-spacing:.15em;">Thank You</div>';
    $o .= '<div style="text-align:center;">';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.5em;font-weight:700;margin-bottom:6px;">'.$name.'</div>';
    $o .= '<div style="font-size:.75em;opacity:.65;margin-bottom:14px;">'.$tagline.'</div>';
    if ($website) $o .= '<div style="font-size:.72em;font-weight:700;opacity:.85;">'.$website.'</div>';
    $o .= '</div>';
    $o .= '<div style="font-size:.58em;opacity:.3;">&copy; '.date('Y').' '.$name.' &middot; Brand Asset Engine</div>';
    $o .= '</div></div>';

    return $o;
}

function bae_brand_book_tab($user_id, $profile) {
    if (empty($profile)) {
        return '<div class="bae-empty"><strong>Complete your <a href="?tab=overview">Brand Profile</a> first.</strong></div>';
    }

    $templates = bae_get_book_templates();
    $saved     = get_transient('bae_bb_' . md5($profile['ticket'] ?? $user_id)) ?: 'modern_elegant';
    if (!isset($templates[$saved])) $saved = 'modern_elegant';
    $nonce    = wp_create_nonce('bae_save_profile');
    $is_free  = bae_get_user_plan($user_id, $profile) === 'free';

    // Group by category
    $cats = array();
    foreach ($templates as $id => $t) {
        $cats[$t['cat']][$id] = $t;
    }

    $out = '';
    $out .= '<style>
.bae-bb-wrap{display:grid;grid-template-columns:220px 1fr;gap:18px;align-items:start}
.bae-bb-sb{position:sticky;top:20px;max-height:82vh;overflow-y:auto}
.bae-bb-sb::-webkit-scrollbar{width:3px}.bae-bb-sb::-webkit-scrollbar-thumb{background:var(--border-2);border-radius:3px}
.bae-bb-cl{font-size:9px;font-weight:700;color:var(--brand-soft);text-transform:uppercase;letter-spacing:.12em;margin:12px 0 5px}
.bae-bb-tg{display:grid;grid-template-columns:1fr 1fr;gap:5px}
.bae-bb-tc{border:1.5px solid var(--border);border-radius:8px;overflow:hidden;cursor:pointer;transition:all .15s}
.bae-bb-tc:hover{border-color:var(--border-2);transform:translateY(-1px)}
.bae-bb-tc.active{border-color:#8b5cf6;box-shadow:0 0 0 2px rgba(139,92,246,.15)}
.bae-bb-th{height:40px;display:flex;align-items:center;justify-content:center;padding:4px}
.bae-bb-th span{font-size:7px;font-weight:700;text-align:center;line-height:1.3}
.bae-bb-tn{font-size:8px;font-weight:600;color:var(--text);padding:3px 5px;background:var(--surface);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bae-bb-pages{padding:14px;display:flex;flex-direction:column;gap:3px;max-height:70vh;overflow-y:auto}
.bae-bb-pages::-webkit-scrollbar{width:3px}.bae-bb-pages::-webkit-scrollbar-thumb{background:var(--border-2);border-radius:3px}
.bae-bp{width:100%;aspect-ratio:8.5/11;border-radius:3px;box-shadow:0 2px 10px rgba(0,0,0,.2);overflow:hidden;flex-shrink:0}
.bae-bpi{width:100%;height:100%;padding:22px;box-sizing:border-box;font-size:13px}
#bae-bb-pt{display:none}
@media print{body>*:not(#bae-bb-pt){display:none!important}#bae-bb-pt{display:block!important}.bae-bp{page-break-after:always;width:210mm;min-height:297mm;padding:18mm;box-sizing:border-box}}
@media(max-width:840px){.bae-bb-wrap{grid-template-columns:1fr}.bae-bb-sb{position:static;max-height:none}}
</style>';

    $out .= '<div class="bae-bb-wrap">';

    // Sidebar
    $out .= '<div class="bae-bb-sb"><div class="bae-card" style="padding:10px;">';
    $out .= '<div style="font-size:11px;font-weight:700;color:var(--text);margin-bottom:2px;">Choose Style</div>';
    $out .= '<div style="font-size:10px;color:var(--text-3);margin-bottom:10px;">'.count($templates).' templates</div>';
    foreach ($cats as $cat => $tpls) {
        $out .= '<div class="bae-bb-cl">'.esc_html($cat).'</div><div class="bae-bb-tg">';
        foreach ($tpls as $id => $t) {
            $active = $id === $saved ? ' active' : '';
            $out .= '<div class="bae-bb-tc'.$active.'" onclick="baeBookTpl(\''.esc_js($id).'\',this)" title="'.esc_attr($t['name']).'">';
            $out .= '<div class="bae-bb-th" style="background:'.esc_attr($t['cb']).';">';
            $out .= '<span style="color:'.esc_attr($t['ct']).';">'.esc_html($t['name']).'</span></div>';
            $out .= '<div class="bae-bb-tn">'.esc_html($t['name']).'</div></div>';
        }
        $out .= '</div>';
    }
    $out .= '</div></div>';

    // Preview
    $out .= '<div><div class="bae-card" style="padding:0;overflow:hidden;">';
    $out .= '<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--surface);border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px;">';
    $out .= '<div><div style="font-size:13px;font-weight:600;color:var(--text);">Preview</div>';
    $out .= '<div style="font-size:11px;color:var(--text-3);" id="bae-bb-lbl">'.esc_html($templates[$saved]['name']).' &mdash; 12 pages</div></div>';
    $out .= '<div style="display:flex;gap:7px;">';
    if ($is_free) {
        $out .= '<button class="bae-btn bae-btn-outline bae-btn-sm" onclick="baePricingOpen(\'Export Brand Book\',\'Download as PDF on Starter plan.\')">Export PDF &mdash; Starter+</button>';
    } else {
        $out .= '<button class="bae-btn bae-btn-outline bae-btn-sm" onclick="baeBookPrint()">Print / Save PDF</button>';
    }
    $out .= '<button class="bae-btn bae-btn-primary bae-btn-sm" id="bae-bb-sbtn" onclick="baeBookSave()">Save Style</button>';
    $out .= '</div></div>';
    $out .= '<div class="bae-bb-pages" id="bae-bb-pages">';
    $out .= bae_render_book_pages($profile, $templates[$saved]);
    $out .= '</div></div></div></div>';

    $out .= '<div id="bae-bb-pt"></div>';

    $out .= '<script>(function(){';
    $out .= 'var cur=\''.esc_js($saved).'\',nonce=\''.esc_js($nonce).'\';';
    $out .= 'window.baeBookTpl=function(id,el){';
    $out .= 'document.querySelectorAll(\'.bae-bb-tc\').forEach(function(c){c.classList.remove(\'active\');});';
    $out .= 'el.classList.add(\'active\');cur=id;';
    $out .= 'var pg=document.getElementById(\'bae-bb-pages\');';
    $out .= 'pg.innerHTML=\'<div style="padding:40px;text-align:center;color:var(--text-3);">Loading...</div>\';';
    $out .= 'var fd=new FormData();fd.append(\'action\',\'bae_render_book\');fd.append(\'nonce\',nonce);fd.append(\'template\',id);';
    $out .= 'fetch(ajaxurl,{method:\'POST\',body:fd}).then(function(r){return r.json();}).then(function(j){';
    $out .= 'if(j.success){pg.innerHTML=j.data.html;var lb=document.getElementById(\'bae-bb-lbl\');if(lb)lb.textContent=j.data.name+\' \u2014 12 pages\';}';
    $out .= '});};';
    $out .= 'window.baeBookSave=function(){var b=document.getElementById(\'bae-bb-sbtn\');if(!b)return;';
    $out .= 'b.disabled=true;b.textContent=\'Saving...\';';
    $out .= 'var fd=new FormData();fd.append(\'action\',\'bae_save_book_template\');fd.append(\'nonce\',nonce);fd.append(\'template\',cur);';
    $out .= 'fetch(ajaxurl,{method:\'POST\',body:fd}).then(function(r){return r.json();}).then(function(j){';
    $out .= 'b.disabled=false;b.textContent=j.success?\'Saved!\':\'Save Style\';';
    $out .= 'setTimeout(function(){b.textContent=\'Save Style\';},2000);});};';
    $out .= 'window.baeBookPrint=function(){var pg=document.getElementById(\'bae-bb-pages\'),tgt=document.getElementById(\'bae-bb-pt\');';
    $out .= 'if(!pg||!tgt)return;tgt.innerHTML=pg.innerHTML;setTimeout(function(){window.print();setTimeout(function(){tgt.innerHTML=\'\';},500);},200);};';
    $out .= '})();</script>';

    return $out;
}

add_action('wp_ajax_bae_render_book',        'bntm_ajax_bae_render_book');
add_action('wp_ajax_nopriv_bae_render_book', 'bntm_ajax_bae_render_book');
function bntm_ajax_bae_render_book() {
    check_ajax_referer('bae_save_profile', 'nonce', false);
    $tpl_id    = sanitize_text_field($_POST['template'] ?? '');
    $templates = bae_get_book_templates();
    if (!isset($templates[$tpl_id])) wp_send_json_error(array());
    $ticket = '';
    if (!empty($_COOKIE['bae_ticket'])) {
        $raw = strtoupper(sanitize_text_field($_COOKIE['bae_ticket']));
        if (preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw)) $ticket = $raw;
    }
    $profile = array();
    if ($ticket) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s", $ticket), ARRAY_A);
        if ($row) $profile = $row;
    }
    if (empty($profile) && is_user_logged_in()) {
        $p = bae_get_profile(get_current_user_id());
        if ($p) $profile = (array)$p;
    }
    wp_send_json_success(array(
        'html' => bae_render_book_pages($profile, $templates[$tpl_id]),
        'name' => $templates[$tpl_id]['name'],
    ));
}

add_action('wp_ajax_bae_save_book_template',        'bntm_ajax_bae_save_book_template');
add_action('wp_ajax_nopriv_bae_save_book_template', 'bntm_ajax_bae_save_book_template');
function bntm_ajax_bae_save_book_template() {
    check_ajax_referer('bae_save_profile', 'nonce', false);
    $tpl = sanitize_text_field($_POST['template'] ?? '');
    if (!isset(bae_get_book_templates()[$tpl])) wp_send_json_error(array());
    $key = '';
    if (!empty($_COOKIE['bae_ticket'])) {
        $raw = strtoupper(sanitize_text_field($_COOKIE['bae_ticket']));
        if (preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw)) $key = 'bae_bb_' . md5($raw);
    }
    if (!$key && is_user_logged_in()) $key = 'bae_bb_' . md5(get_current_user_id());
    if ($key) set_transient($key, $tpl, YEAR_IN_SECONDS);
    wp_send_json_success(array('saved' => true));
}