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

add_action('wp_ajax_bae_save_profile',      'bntm_ajax_bae_save_profile');
add_action('wp_ajax_bae_generate_asset',    'bntm_ajax_bae_generate_asset');
add_action('wp_ajax_bae_delete_asset',      'bntm_ajax_bae_delete_asset');
add_action('wp_ajax_bae_reset_profile',     'bntm_ajax_bae_reset_profile');
add_action('wp_ajax_bae_save_kit_settings', 'bntm_ajax_bae_save_kit_settings');

// ADD THIS RIGHT HERE ↓
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

function bntm_shortcode_bae() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to access the Brand Asset Engine.</div>';
    }

    $user_id    = get_current_user_id();
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
    $profile    = bae_get_profile($user_id);

    ob_start();
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>

    <div class="bae-wrap" id="bae-wrap">

        <!-- Header -->
        <div class="bae-header">
            <div class="bae-logo-mark">B</div>
            <div class="bae-logo-text">Brand<span>Asset</span></div>
            <div class="bae-header-right">
                <button class="bae-theme-btn" id="bae-theme-btn" onclick="baeToggleTheme()">
                    <span id="bae-theme-icon">☀️</span>
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

        <!-- Tab Nav -->
        <div class="bae-tabs">
            <?php
            $tabs = [
                'overview' => 'Brand Profile',
                'identity' => 'Identity Board',
                'assets'   => 'Asset Generator',
                'kit'      => 'Brand Kit',
                'startup'  => '🚀 Startup Toolkit',
                'settings' => 'Settings',
            ];
            $base_url = strtok($_SERVER['REQUEST_URI'], '?');
            foreach ($tabs as $slug => $label):
                $class = $active_tab === $slug ? 'bae-tab active' : 'bae-tab';
            ?>
                <a href="<?php echo $base_url; ?>?tab=<?php echo $slug; ?>" class="<?php echo $class; ?>">
                    <?php echo $label; ?>
                    <?php if ($slug === 'identity' && empty($profile)) echo '<span class="bae-tab-lock">&#9670;</span>'; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Tab Content -->
        <div class="bae-tab-content">
            <?php
            if ($active_tab === 'overview') echo bae_overview_tab($user_id, $profile);
            elseif ($active_tab === 'identity') echo bae_identity_tab($user_id, $profile);
            elseif ($active_tab === 'assets')   echo bae_assets_tab($user_id, $profile);
            elseif ($active_tab === 'kit')      echo bae_kit_tab($user_id, $profile);
            elseif ($active_tab === 'startup')  echo bae_startup_tab($user_id, $profile);
            elseif ($active_tab === 'settings') echo bae_settings_tab($user_id, $profile);
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

    /* ── TABS ── */
    .bae-tabs {
        display: flex; gap: 2px;
        padding: 12px 24px 0;
        border-bottom: 1px solid var(--border);
        background: var(--bg-2);
        overflow-x: auto;
        transition: background 0.5s, border-color 0.5s;
        scrollbar-width: none;
    }
    .bae-tabs::-webkit-scrollbar { display: none; }

    .bae-tab {
        padding: 10px 18px;
        font-size: 13px; font-weight: 500;
        color: var(--text-3);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        white-space: nowrap;
        display: flex; align-items: center; gap: 6px;
        border-radius: 10px 10px 0 0;
        transition: color 0.2s, background 0.2s, border-color 0.2s;
    }
    .bae-tab:hover { color: var(--text-2); background: var(--surface); }
    .bae-tab.active {
        color: var(--text);
        border-bottom-color: var(--brand);
        background: var(--surface);
    }
    .bae-tab-lock { font-size: 9px; color: var(--text-3); }

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

    .bae-font-sample {
        padding: 20px; border: 1px solid var(--border); border-radius: 14px;
        margin-top: 14px; background: var(--bg-2);
        transition: background 0.5s, border-color 0.5s;
    }
    .bae-font-heading-sample {
        font-family: 'Instrument Serif', serif;
        font-size: 26px; font-style: italic; line-height: 1.2;
        color: var(--brand-soft); transition: color 0.5s;
    }
    .bae-font-body-sample { font-size: 14px; color: var(--text-2); margin-top: 8px; line-height: 1.7; transition: color 0.5s; }

    /* ── LOGO MOCKUP ── */
    .bae-logo-mockup {
        display: flex; align-items: center; gap: 16px;
        padding: 24px; border: 1px solid var(--border);
        border-radius: 14px; margin-top: 14px;
        background: var(--surface);
        transition: background 0.5s, border-color 0.5s;
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
    .bae-wrap ::-webkit-scrollbar { width: 5px; }
    .bae-wrap ::-webkit-scrollbar-track { background: transparent; }
    .bae-wrap ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 999px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
        .bae-stats-row { grid-template-columns: 1fr 1fr; }
        .bae-assets-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 680px) {
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
            modalBody.innerHTML = html;
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
    })();
    </script>

    <script>
    /* BAE — THEME TOGGLE + GSAP */
    var baeIsDark = true;

    function baeToggleTheme() {
        baeIsDark = !baeIsDark;
        var wrap  = document.getElementById('bae-wrap');
        var track = document.getElementById('bae-toggle-track');
        var label = document.getElementById('bae-theme-label');
        var icon  = document.getElementById('bae-theme-icon');

        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:999999;pointer-events:none;background:' + (baeIsDark ? '#0a0a0f' : '#fafafa') + ';opacity:0;';
        document.body.appendChild(overlay);

        if (window.gsap) {
            var tl = gsap.timeline();
            tl.to(overlay, { opacity: 0.3, duration: 0.18, ease: 'power2.in' })
              .call(function() {
                  if (baeIsDark) { wrap.classList.remove('bae-light'); track.classList.add('on'); label.textContent = 'Light'; icon.textContent = '☀️'; }
                  else { wrap.classList.add('bae-light'); track.classList.remove('on'); label.textContent = 'Dark'; icon.textContent = '🌙'; }
              })
              .to(overlay, { opacity: 0, duration: 0.35, ease: 'power2.out' })
              .call(function() { overlay.remove(); });
            gsap.to('.bae-toggle-thumb', { x: baeIsDark ? 16 : 0, duration: 0.4, ease: 'back.out(1.8)' });
            gsap.fromTo('.bae-stat-card, .bae-card, .bae-asset-card', { scale: 0.995 }, { scale: 1, duration: 0.35, stagger: 0.01, ease: 'power3.out' });
        } else {
            if (baeIsDark) { wrap.classList.remove('bae-light'); track.classList.add('on'); label.textContent = 'Light'; icon.textContent = '☀️'; }
            else { wrap.classList.add('bae-light'); track.classList.remove('on'); label.textContent = 'Dark'; icon.textContent = '🌙'; }
            overlay.remove();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (!window.gsap) return;
        gsap.fromTo('.bae-header', { opacity: 0, y: -12 }, { opacity: 1, y: 0, duration: 0.5, ease: 'power3.out', delay: 0.1 });
        gsap.fromTo('.bae-tab', { opacity: 0, y: -6 }, { opacity: 1, y: 0, duration: 0.4, stagger: 0.05, ease: 'power3.out', delay: 0.2 });
        gsap.fromTo('.bae-stat-card', { opacity: 0, y: 18 }, { opacity: 1, y: 0, duration: 0.5, stagger: 0.08, ease: 'power3.out', delay: 0.35 });
        gsap.fromTo('.bae-card', { opacity: 0, y: 22 }, { opacity: 1, y: 0, duration: 0.5, stagger: 0.07, ease: 'power3.out', delay: 0.45 });
        gsap.fromTo('.bae-asset-card', { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 0.45, stagger: 0.05, ease: 'power3.out', delay: 0.3 });
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
    $content = ob_get_clean();
    return bntm_universal_container('Brand Asset Engine', $content);
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
        <div class="bae-stat-card">
            <div class="bae-stat-label">Brand Kit</div>
            <div class="bae-stat-value" style="font-size:18px;margin-top:10px;">
                <?php if ($kit_status === 'public'): ?>
                    <span class="bae-badge bae-badge-green">Public</span>
                <?php else: ?>
                    <span class="bae-badge bae-badge-gray">Private</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="bae-stat-card">
            <div class="bae-stat-label">Last Updated</div>
            <div class="bae-stat-value" style="font-size:15px;margin-top:10px;">
                <?php echo !empty($p['updated_at']) ? date('M d, Y', strtotime($p['updated_at'])) : '—'; ?>
            </div>
        </div>
    </div>

    <?php if ($is_new): ?>
    <div class="bae-notice bae-notice-info" style="margin-bottom:24px;">
        Welcome! Start by filling in your brand profile below. This information powers all your generated assets.
    </div>
    <?php endif; ?>

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
                        <input type="text" name="tagline" placeholder="e.g. Fresh baked with love, every day"
                               value="<?php echo esc_attr($p['tagline'] ?? ''); ?>">
                        <small>Short phrase that sums up your brand promise.</small>
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
                <small style="display:block;color:#9ca3af;font-size:12px;margin-bottom:16px;">
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
                <div class="bae-section-label">Brand Colors</div>
                <div class="bae-form-grid three">
                    <div class="bae-form-group">
                        <label>Primary Color</label>
                        <div class="bae-color-row bae-color-pair">
                            <input type="color" name="primary_color_picker" value="<?php echo esc_attr(bae_safe_color($p['primary_color'] ?? '', '#1a1a2e')); ?>">
                            <input type="text" name="primary_color" value="<?php echo esc_attr(bae_safe_color($p['primary_color'] ?? '', '#1a1a2e')); ?>" maxlength="7" placeholder="#1a1a2e">
                        </div>
                        <small>Main brand color — headers, buttons, accents.</small>
                    </div>
                    <div class="bae-form-group">
                        <label>Secondary Color</label>
                        <div class="bae-color-row bae-color-pair">
                            <input type="color" name="secondary_color_picker" value="<?php echo esc_attr(bae_safe_color($p['secondary_color'] ?? '', '#16213e')); ?>">
                            <input type="text" name="secondary_color" value="<?php echo esc_attr(bae_safe_color($p['secondary_color'] ?? '', '#16213e')); ?>" maxlength="7" placeholder="#16213e">
                        </div>
                        <small>Supports primary — backgrounds, cards.</small>
                    </div>
                    <div class="bae-form-group">
                        <label>Accent Color</label>
                        <div class="bae-color-row bae-color-pair">
                            <input type="color" name="accent_color_picker" value="<?php echo esc_attr(bae_safe_color($p['accent_color'] ?? '', '#e94560')); ?>">
                            <input type="text" name="accent_color" value="<?php echo esc_attr(bae_safe_color($p['accent_color'] ?? '', '#e94560')); ?>" maxlength="7" placeholder="#e94560">
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

            <!-- Section: Logo Style -->
            <div style="margin-bottom:28px;">
                <div class="bae-section-label">Logo Style</div>
                <div class="bae-form-grid">
                    <div class="bae-form-group">
                        <label>Logo Type</label>
                        <select name="logo_style">
                            <?php
                            $styles = ['wordmark' => 'Wordmark — Business name as logo', 'lettermark' => 'Lettermark — Initials only', 'combination' => 'Combination — Icon + Name'];
                            $sls = $p['logo_style'] ?? 'wordmark';
                            foreach ($styles as $val => $lbl):
                            ?>
                                <option value="<?php echo $val; ?>" <?php selected($sls, $val); ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bae-form-group">
                        <label>Logo Icon / Symbol</label>
                        <select name="logo_icon">
                            <?php
                            $icons = ['' => 'None', 'diamond' => 'Diamond', 'star' => 'Star', 'shield' => 'Shield', 'leaf' => 'Leaf', 'bolt' => 'Bolt', 'flame' => 'Flame', 'crown' => 'Crown', 'circle' => 'Circle'];
                            $sli = $p['logo_icon'] ?? '';
                            foreach ($icons as $val => $lbl):
                            ?>
                                <option value="<?php echo $val; ?>" <?php selected($sli, $val); ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Abstract shape used in combination/lettermark logos.</small>
                    </div>
                </div>
            </div>

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
    <!-- Startup Toolkit -->
    <div class="bae-card" style="margin-top:0;">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">🚀 Startup Toolkit</div>
                <div class="bae-card-desc">Everything a new business needs to get online — generated from your brand profile.</div>
            </div>
            <span class="bae-badge bae-badge-yellow">Auto-generated</span>
        </div>

        <?php
        $biz_raw   = $p['business_name'] ?? 'yourbusiness';
        $biz_slug  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $biz_raw));
        $biz_words = explode(' ', strtolower(trim($biz_raw)));
        $biz_short = $biz_words[0] ?? $biz_slug;

        // Domain suggestions
        $domains = [
            $biz_slug . '.com',
            $biz_slug . '.ph',
            $biz_slug . 'ph.com',
            'get' . $biz_slug . '.com',
            $biz_short . 'store.com',
            $biz_slug . '.shop',
            $biz_slug . 'online.com',
        ];

        // Social handle suggestions
        $handles = [
            '@' . $biz_slug,
            '@' . $biz_slug . 'ph',
            '@' . $biz_slug . 'official',
            '@the' . $biz_slug,
        ];

        // Tagline variants based on industry
        $industry = $p['industry'] ?? 'Other';
        $tagline_base = !empty($p['tagline']) ? $p['tagline'] : '';
        $tagline_variants = bae_generate_tagline_variants($p['business_name'], $industry, $tagline_base);

        // Email name suggestions
        $email_suggestions = [
            'hello@' . $biz_slug . '.com',
            'info@' . $biz_slug . '.com',
            $biz_slug . '@gmail.com',
            'contact@' . $biz_slug . '.com',
        ];
        ?>

        <!-- Domain Suggestions -->
        <div style="margin-bottom:28px;">
            <div class="bae-section-label" style="margin-bottom:12px;">🌐 Domain Name Ideas</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;">
                <?php foreach ($domains as $i => $domain): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border:1px solid <?php echo $i === 0 ? '#111827' : '#e5e7eb'; ?>;border-radius:8px;background:<?php echo $i === 0 ? '#111827' : '#fff'; ?>;">
                    <span style="font-size:13px;font-weight:<?php echo $i === 0 ? '600' : '400'; ?>;color:<?php echo $i === 0 ? '#fff' : '#374151'; ?>;font-family:'Courier New',monospace;"><?php echo esc_html($domain); ?></span>
                    <?php if ($i === 0): ?><span style="font-size:10px;background:#fff;color:#111827;padding:2px 8px;border-radius:999px;font-weight:700;">TOP</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <p style="font-size:12px;color:#9ca3af;margin-top:10px;">💡 Check availability at <strong>namecheap.com</strong>, <strong>godaddy.com</strong>, or <strong>dot.ph</strong> (for .ph domains)</p>
        </div>

        <div class="bae-divider"></div>

        <!-- Social Handles -->
        <div style="margin-bottom:28px;">
            <div class="bae-section-label" style="margin-bottom:12px;">📱 Social Media Handles to Register</div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($handles as $handle): ?>
                <span style="padding:8px 16px;background:#f3f4f6;border-radius:8px;font-size:13px;font-weight:500;color:#374151;font-family:'Courier New',monospace;"><?php echo esc_html($handle); ?></span>
                <?php endforeach; ?>
            </div>
            <p style="font-size:12px;color:#9ca3af;margin-top:10px;">💡 Register the same handle on Facebook, Instagram, TikTok, and YouTube — consistency builds trust.</p>
        </div>

        <div class="bae-divider"></div>

        <!-- Tagline Variants -->
        <div style="margin-bottom:28px;">
            <div class="bae-section-label" style="margin-bottom:12px;">✍️ Tagline Variants</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <?php foreach ($tagline_variants as $variant): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid #e5e7eb;border-radius:8px;background:#fafafa;">
                    <span style="font-size:14px;color:#374151;font-style:italic;">"<?php echo esc_html($variant); ?>"</span>
                    <button onclick="navigator.clipboard.writeText('<?php echo esc_js($variant); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500);"
                            style="border:none;background:none;color:#9ca3af;font-size:12px;cursor:pointer;padding:4px 8px;">Copy</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bae-divider"></div>

        <!-- Business Email -->
        <div>
            <div class="bae-section-label" style="margin-bottom:12px;">📧 Business Email Ideas</div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($email_suggestions as $email): ?>
                <span style="padding:8px 16px;background:#f3f4f6;border-radius:8px;font-size:13px;color:#374151;font-family:'Courier New',monospace;"><?php echo esc_html($email); ?></span>
                <?php endforeach; ?>
            </div>
            <p style="font-size:12px;color:#9ca3af;margin-top:10px;">💡 Get a professional business email via <strong>Google Workspace</strong> or <strong>Zoho Mail</strong> — avoid using personal Gmail for business.</p>
        </div>

    </div>
    <?php endif; ?>

    <script>
    (function() {
        // CHANGED: No color picker init here — handled once in main shortcode

        var form = document.getElementById('bae-profile-form');
        var msg  = document.getElementById('bae-profile-msg');

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
                    btn.textContent = 'Save Brand Profile';
                    if (json.success) {
                        setTimeout(function() { location.reload(); }, 1200);
                    }
                })
                .catch(function() {
                    msg.innerHTML = '<div class="bae-notice bae-notice-error">An error occurred. Please try again.</div>';
                    btn.disabled = false;
                    btn.textContent = 'Save Brand Profile';
                });
            });
        }
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
        <div class="bae-logo-mockup" style="background:#fff;">
            <?php if ($p['logo_style'] !== 'wordmark'): ?>
            <div class="bae-logo-icon-shape" style="background:<?php echo $pc; ?>;">
                <?php echo $p['logo_style'] === 'lettermark' ? $initials : bae_render_icon($p['logo_icon'], $p['primary_color']); ?>
            </div>
            <?php endif; ?>
            <div class="bae-logo-text-area">
                <div class="bae-logo-name" style="font-family:'<?php echo esc_attr($p['font_heading']); ?>',sans-serif;color:<?php echo $pc; ?>;">
                    <?php echo $p['logo_style'] === 'lettermark' ? esc_html($initials) : esc_html($p['business_name']); ?>
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
            <?php if ($p['logo_style'] !== 'wordmark'): ?>
            <div class="bae-logo-icon-shape" style="background:rgba(255,255,255,0.15);">
                <?php echo $p['logo_style'] === 'lettermark' ? $initials : bae_render_icon($p['logo_icon'], '#fff'); ?>
            </div>
            <?php endif; ?>
            <div class="bae-logo-text-area">
                <div class="bae-logo-name" style="font-family:'<?php echo esc_attr($p['font_heading']); ?>',sans-serif;color:#ffffff;">
                    <?php echo $p['logo_style'] === 'lettermark' ? esc_html($initials) : esc_html($p['business_name']); ?>
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
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:<?php echo esc_attr($hex); ?>;"></div>
                <div class="bae-swatch-label"><?php echo $label; ?></div>
                <div class="bae-swatch-hex"><?php echo strtoupper($hex); ?></div>
            </div>
            <?php endforeach; ?>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:<?php echo esc_attr($pc); ?>;opacity:0.6;"></div>
                <div class="bae-swatch-label">Primary Tint</div>
                <div class="bae-swatch-hex">60%</div>
            </div>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:#ffffff;border:1px solid #e5e7eb;"></div>
                <div class="bae-swatch-label">White</div>
                <div class="bae-swatch-hex">#FFFFFF</div>
            </div>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:#f9fafb;"></div>
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
            <div style="font-size:13px;color:#6b7280;">
                <strong>Heading:</strong> <?php echo esc_html($p['font_heading']); ?> — Bold 700
            </div>
            <div style="font-size:13px;color:#6b7280;">
                <strong>Body:</strong> <?php echo esc_html($p['font_body']); ?> — Regular 400
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
        <div style="margin-top:16px;padding:16px;background:#f9fafb;border-radius:8px;font-size:14px;color:#374151;line-height:1.7;">
            <strong>Target Audience:</strong> <?php echo esc_html($p['personality']); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- CTA -->
    <div style="display:flex;gap:12px;">
        <a href="?tab=assets" class="bae-btn bae-btn-primary">Generate Assets &rarr;</a>
        <a href="?tab=overview" class="bae-btn bae-btn-outline">&larr; Edit Profile</a>
    </div>
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
        'brand_book'       => ['name' => 'Brand Book',        'desc' => 'Full multi-page brand identity book'],
        'sitemap'          => ['name' => 'Site Structure',    'desc' => 'Suggested sitemap for your industry'],
    ];

    ob_start();
    ?>
    <div class="bae-generate-row">
        <div>
            <div class="bae-card-title">Asset Generator</div>
            <div class="bae-card-desc">Generate branded HTML assets ready for download or handoff.</div>
        </div>
        <button class="bae-btn bae-btn-primary" id="bae-generate-all-btn"
                data-nonce="<?php echo $nonce; ?>"
                data-pid="<?php echo $profile_id; ?>">
            Generate All Assets
        </button>
    </div>

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
                    <div class="bae-asset-preview-inner">
                        <?php echo $gen_data['asset_html']; ?>
                    </div>
                <?php else: ?>
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                <?php endif; ?>
            </div>
            <div class="bae-asset-info">
                <div class="bae-asset-name"><?php echo $meta['name']; ?></div>
                <div class="bae-asset-meta"><?php echo $meta['desc']; ?></div>
            </div>
            <div class="bae-asset-actions">
                <?php if ($is_gen): ?>
                    <button class="bae-btn bae-btn-outline bae-btn-sm bae-preview-btn"
                            data-type="<?php echo $type; ?>"
                            data-name="<?php echo esc_attr($meta['name']); ?>"
                            data-html="<?php echo esc_attr($gen_data['asset_html']); ?>">
                        Preview
                    </button>
                    <button class="bae-btn bae-btn-primary bae-btn-sm bae-regen-btn"
                            data-type="<?php echo $type; ?>"
                            data-nonce="<?php echo $nonce; ?>"
                            data-pid="<?php echo $profile_id; ?>">
                        Regenerate
                    </button>
                    <button class="bae-btn bae-btn-outline bae-btn-sm bae-delete-btn"
                            data-type="<?php echo $type; ?>"
                            data-id="<?php echo $gen_data['id']; ?>"
                            data-nonce="<?php echo $nonce; ?>">
                        &times;
                    </button>
                <?php else: ?>
                    <button class="bae-btn bae-btn-primary bae-btn-sm bae-regen-btn"
                            data-type="<?php echo $type; ?>"
                            data-nonce="<?php echo $nonce; ?>"
                            data-pid="<?php echo $profile_id; ?>">
                        Generate
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    (function() {
        // Preview
        document.querySelectorAll('.bae-preview-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                baeOpenModal(this.dataset.name, this.dataset.html);
            });
        });

        // Single Generate / Regenerate
        document.querySelectorAll('.bae-regen-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                baeGenerateAsset(this.dataset.type, this.dataset.nonce, this.dataset.pid, this);
            });
        });

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

        var types = ['business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'brand_book', 'sitemap'];

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
        function baeGenerateAsset(type, nonce, pid, btn) {
            var original = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Generating...';

            var formData = new FormData();
            formData.append('action', 'bae_generate_asset');
            formData.append('asset_type', type);
            formData.append('profile_id', pid);
            formData.append('nonce', nonce);

            fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                if (json.success) {
                    location.reload();
                } else {
                    alert(json.data.message || 'Generation failed.');
                    btn.disabled = false;
                    btn.textContent = original;
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.textContent = original;
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

    $p        = $profile;
    $kit_slug = !empty($p['kit_slug']) ? $p['kit_slug'] : sanitize_title($p['business_name']) . '-' . substr($p['rand_id'], 0, 6);
    $kit_url  = get_permalink(get_page_by_path('brand-kit')) . '?slug=' . $kit_slug;
    $is_pub   = $p['kit_visibility'] === 'public';
    $nonce    = wp_create_nonce('bae_save_kit_settings');

    ob_start();
    ?>
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

        <?php if ($is_pub): ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
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
                    <label>Kit Visibility</label>
                    <select name="kit_visibility">
                        <option value="private" <?php selected($p['kit_visibility'], 'private'); ?>>Private — Only you can see it</option>
                        <option value="public"  <?php selected($p['kit_visibility'], 'public'); ?>>Public — Anyone with the link</option>
                    </select>
                </div>
                <div class="bae-form-group">
                    <label>Kit URL Slug</label>
                    <input type="text" name="kit_slug" value="<?php echo esc_attr($kit_slug); ?>" placeholder="my-brand-kit">
                    <small>Unique identifier in the shareable URL.</small>
                </div>
            </div>
            <input type="hidden" name="action" value="bae_save_kit_settings">
            <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
            <button type="submit" class="bae-btn bae-btn-primary" style="margin-top:16px;">Save Kit Settings</button>
            <div id="bae-kit-msg"></div>
        </form>
    </div>

    <!-- Brand Kit Preview -->
    <div class="bae-card">
        <div class="bae-card-title">Kit Preview</div>
        <div class="bae-card-desc" style="margin-top:4px;margin-bottom:20px;">What partners and designers will see when they open your Brand Kit link.</div>
        <div style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
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
    $nonce = wp_create_nonce('bae_reset_profile');

    ob_start();
    ?>
    <div class="bae-card">
        <div class="bae-card-title">Export</div>
        <div class="bae-card-desc" style="margin-top:4px;margin-bottom:16px;">Download all your brand assets and profile data.</div>
        <button class="bae-btn bae-btn-outline" disabled>Export ZIP (Coming Soon)</button>
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
                <span style="color:#9ca3af;"><?php echo $k; ?>: </span>
                <span style="color:#111827;font-weight:500;"><?php echo $v; ?></span>
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
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:#fff8f8;border:1px solid #fecaca;border-radius:8px;flex-wrap:wrap;gap:10px;">
                <div>
                    <div style="font-size:14px;font-weight:600;color:#111827;">Reset Brand Profile</div>
                    <div style="font-size:13px;color:#6b7280;margin-top:2px;">Clears your brand profile and all generated assets. Cannot be undone.</div>
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
    return ob_get_clean();
}

// =============================================================================
// PUBLIC BRAND KIT SHORTCODE
// CHANGED: Added OG meta tags to head for better social sharing previews
// =============================================================================

function bntm_shortcode_bae_kit() {
    $slug = isset($_GET['slug']) ? sanitize_text_field($_GET['slug']) : '';
    if (empty($slug)) {
        return '<div style="text-align:center;padding:60px;color:#9ca3af;">Brand Kit not found.</div>';
    }

    global $wpdb;
    $profiles_table = $wpdb->prefix . 'bae_profiles';
    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$profiles_table} WHERE kit_slug = %s AND kit_visibility = 'public'",
        $slug
    ), ARRAY_A);

    if (!$profile) {
        return '<div style="text-align:center;padding:60px;color:#9ca3af;">This Brand Kit is private or does not exist.</div>';
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

function bae_generate_asset_html($type, $profile) {
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
  <div style='width:336px;height:192px;background:{$pc};border-radius:10px;padding:24px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;'>
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
        <div style='font-size:11px;font-weight:700;color:#065f46;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;'>✓ We Sound Like</div>
        <div style='font-size:13px;color:#374151;line-height:1.7;'>" . bae_get_voice_examples($tone_tags, true) . "</div>
      </div>
      <div style='padding:16px;border:1px solid #fee2e2;background:#fff5f5;border-radius:10px;'>
        <div style='font-size:11px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;'>✗ We Don't Sound Like</div>
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
    check_ajax_referer('bae_save_profile', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $table   = $wpdb->prefix . 'bae_profiles';
    $user_id = get_current_user_id();

    $data = [
        'business_name'   => sanitize_text_field($_POST['business_name'] ?? ''),
        'industry'        => sanitize_text_field($_POST['industry']       ?? ''),
        'tagline'         => sanitize_text_field($_POST['tagline']         ?? ''),
        'personality'     => sanitize_text_field($_POST['personality']     ?? ''),
        // CHANGED: Save contact fields
        'email'           => sanitize_email($_POST['email']               ?? ''),
        'phone'           => sanitize_text_field($_POST['phone']           ?? ''),
        'website'         => esc_url_raw($_POST['website']                ?? ''),
        'address'         => sanitize_text_field($_POST['address']         ?? ''),
        'primary_color'   => bae_safe_color($_POST['primary_color']   ?? '', '#1a1a2e'),
        'secondary_color' => bae_safe_color($_POST['secondary_color'] ?? '', '#16213e'),
        'accent_color'    => bae_safe_color($_POST['accent_color']    ?? '', '#e94560'),
        'font_heading'    => sanitize_text_field($_POST['font_heading']    ?? 'Inter'),
        'font_body'       => sanitize_text_field($_POST['font_body']       ?? 'Inter'),
        'logo_style'      => sanitize_text_field($_POST['logo_style']      ?? 'wordmark'),
        'logo_icon'       => sanitize_text_field($_POST['logo_icon']       ?? ''),
    ];

    if (empty($data['business_name'])) {
        wp_send_json_error(['message' => 'Business name is required.']);
    }

    $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table} WHERE user_id = %d", $user_id));

    if ($existing) {
        $result = $wpdb->update($table, $data, ['user_id' => $user_id]);
        $msg    = 'Brand profile updated successfully!';
    } else {
        $data['rand_id']  = bntm_rand_id();
        $data['user_id']  = $user_id;
        $data['kit_slug'] = sanitize_title($data['business_name']) . '-' . substr($data['rand_id'], 0, 6);
        $result = $wpdb->insert($table, $data);
        $msg    = 'Brand profile created successfully!';
    }

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to save profile. Please try again.']);
    }

    wp_send_json_success(['message' => $msg]);
}

function bntm_ajax_bae_generate_asset() {
    check_ajax_referer('bae_generate_asset', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id    = get_current_user_id();
    $asset_type = sanitize_text_field($_POST['asset_type'] ?? '');
    $profile_id = intval($_POST['profile_id'] ?? 0);

    $allowed_types = ['business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'brand_book', 'sitemap'];
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

    $asset_html = bae_generate_asset_html($asset_type, $profile);

    $names = [
        'business_card'    => 'Business Card',
        'letterhead'       => 'Letterhead',
        'email_signature'  => 'Email Signature',
        'social_kit'       => 'Social Media Kit',
        'brand_guidelines' => 'Brand Guidelines',
        'brand_book'       => 'Brand Book',
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
// HELPER FUNCTIONS
// =============================================================================

function bae_get_profile($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $user_id), ARRAY_A);
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

function bae_render_icon($icon, $color) {
    $icons = [
        'diamond' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="' . $color . '"><path d="M12 2L2 9l10 13L22 9z"/></svg>',
        'star'    => '<svg width="22" height="22" viewBox="0 0 24 24" fill="' . $color . '"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'shield'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="' . $color . '"><path d="M12 2l7 4v5c0 5.55-3.84 10.74-7 12-3.16-1.26-7-6.45-7-12V6l7-4z"/></svg>',
        'leaf'    => '<svg width="22" height="22" viewBox="0 0 24 24" fill="' . $color . '"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 008 20C19 20 22 3 22 3c-1 2-8 5.5-10 8.5z"/></svg>',
        'bolt'    => '<svg width="22" height="22" viewBox="0 0 24 24" fill="' . $color . '"><path d="M13 2L4.09 12.26 9 12.97 11 22l8.91-10.26L15 10.97z"/></svg>',
        'flame'   => '<svg width="22" height="22" viewBox="0 0 24 24" fill="' . $color . '"><path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67z"/></svg>',
        'crown'   => '<svg width="22" height="22" viewBox="0 0 24 24" fill="' . $color . '"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm0 4h14v-2H5v2z"/></svg>',
        'circle'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="2.5"><circle cx="12" cy="12" r="9"/></svg>',
        ''        => '',
    ];
    return $icons[$icon] ?? '';
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
        ['name' => 'Home',    'slug' => '/',         'icon' => '🏠', 'priority' => 'high',   'desc' => 'Main landing page. Hero section, key offerings, and primary CTA. First impression — make it count.'],
        ['name' => 'About',   'slug' => '/about',    'icon' => '👤', 'priority' => 'high',   'desc' => 'Your story, mission, values, and team. Builds trust and humanizes your brand.'],
        ['name' => 'Contact', 'slug' => '/contact',  'icon' => '📬', 'priority' => 'high',   'desc' => 'Contact form, phone, email, map/address, and business hours.'],
        ['name' => 'Privacy', 'slug' => '/privacy',  'icon' => '🔒', 'priority' => 'low',    'desc' => 'Privacy policy — required for any business collecting customer data or using analytics.'],
    ];

    $industry_pages = [
        'Food & Beverage' => [
            ['name' => 'Menu',      'slug' => '/menu',      'icon' => '🍽️', 'priority' => 'high',   'desc' => 'Full menu with categories, descriptions, photos, and prices. Your most-visited page.'],
            ['name' => 'Order',     'slug' => '/order',     'icon' => '🛒', 'priority' => 'high',   'desc' => 'Online ordering or reservation system. Direct revenue driver.'],
            ['name' => 'Gallery',   'slug' => '/gallery',   'icon' => '📸', 'priority' => 'medium', 'desc' => 'Food photography and restaurant ambiance shots. Social proof through visuals.'],
            ['name' => 'Blog',      'slug' => '/blog',      'icon' => '📝', 'priority' => 'low',    'desc' => 'Recipes, behind-the-scenes stories, and food culture content. Boosts SEO.'],
        ],
        'Retail & Commerce' => [
            ['name' => 'Shop',      'slug' => '/shop',      'icon' => '🛍️', 'priority' => 'high',   'desc' => 'Product catalog with filters, search, and categories. Core of your retail experience.'],
            ['name' => 'Products',  'slug' => '/products',  'icon' => '📦', 'priority' => 'high',   'desc' => 'Individual product pages with photos, specs, and add-to-cart.'],
            ['name' => 'Deals',     'slug' => '/deals',     'icon' => '🏷️', 'priority' => 'medium', 'desc' => 'Promotions, sale items, and limited-time offers. High traffic potential.'],
            ['name' => 'FAQ',       'slug' => '/faq',       'icon' => '❓', 'priority' => 'medium', 'desc' => 'Common questions about shipping, returns, sizing, and policies.'],
        ],
        'Professional Services' => [
            ['name' => 'Services',  'slug' => '/services',  'icon' => '💼', 'priority' => 'high',   'desc' => 'What you offer — detailed service descriptions with pricing or inquiry CTAs.'],
            ['name' => 'Portfolio', 'slug' => '/portfolio', 'icon' => '🗂️', 'priority' => 'high',   'desc' => 'Past projects, case studies, and results. Social proof for high-ticket services.'],
            ['name' => 'Pricing',   'slug' => '/pricing',   'icon' => '💳', 'priority' => 'medium', 'desc' => 'Transparent pricing tiers or estimate range. Builds trust and filters leads.'],
            ['name' => 'Blog',      'slug' => '/blog',      'icon' => '📝', 'priority' => 'medium', 'desc' => 'Thought leadership and industry insights. Positions you as an expert.'],
        ],
        'Health & Wellness' => [
            ['name' => 'Services',  'slug' => '/services',  'icon' => '💊', 'priority' => 'high',   'desc' => 'Treatments, programs, and wellness offerings with full descriptions.'],
            ['name' => 'Book',      'slug' => '/book',      'icon' => '📅', 'priority' => 'high',   'desc' => 'Appointment booking system — reduce friction to conversion.'],
            ['name' => 'Resources', 'slug' => '/resources', 'icon' => '📚', 'priority' => 'medium', 'desc' => 'Health guides, tips, and educational content. Builds authority and SEO value.'],
            ['name' => 'FAQ',       'slug' => '/faq',       'icon' => '❓', 'priority' => 'low',    'desc' => 'Answers to common patient/client questions about services and policies.'],
        ],
        'Technology' => [
            ['name' => 'Products',  'slug' => '/products',  'icon' => '⚙️', 'priority' => 'high',   'desc' => 'Product feature pages with demos, screenshots, and use cases.'],
            ['name' => 'Pricing',   'slug' => '/pricing',   'icon' => '💳', 'priority' => 'high',   'desc' => 'Subscription tiers or license options with feature comparison table.'],
            ['name' => 'Docs',      'slug' => '/docs',      'icon' => '📄', 'priority' => 'medium', 'desc' => 'Technical documentation, API reference, and integration guides.'],
            ['name' => 'Blog',      'slug' => '/blog',      'icon' => '📝', 'priority' => 'medium', 'desc' => 'Product updates, tutorials, and industry insights. Key for developer audiences.'],
        ],
        'Education & Training' => [
            ['name' => 'Courses',   'slug' => '/courses',   'icon' => '🎓', 'priority' => 'high',   'desc' => 'Course catalog with descriptions, duration, and enrollment CTAs.'],
            ['name' => 'Enroll',    'slug' => '/enroll',    'icon' => '✍️', 'priority' => 'high',   'desc' => 'Enrollment or registration form. Direct conversion page.'],
            ['name' => 'Faculty',   'slug' => '/faculty',   'icon' => '👨‍🏫', 'priority' => 'medium', 'desc' => 'Instructor profiles and credentials. Builds trust with prospective students.'],
            ['name' => 'Blog',      'slug' => '/blog',      'icon' => '📝', 'priority' => 'low',    'desc' => 'Learning tips, career guidance, and educational resources.'],
        ],
        'Fashion & Apparel' => [
            ['name' => 'Collection','slug' => '/collection','icon' => '👗', 'priority' => 'high',   'desc' => 'Current and seasonal collections with editorial-style photography.'],
            ['name' => 'Shop',      'slug' => '/shop',      'icon' => '🛍️', 'priority' => 'high',   'desc' => 'E-commerce catalog with filters for size, color, and category.'],
            ['name' => 'Lookbook',  'slug' => '/lookbook',  'icon' => '📸', 'priority' => 'medium', 'desc' => 'Styled outfit inspirations. Drives aspiration and upselling.'],
            ['name' => 'Journal',   'slug' => '/journal',   'icon' => '📓', 'priority' => 'low',    'desc' => 'Brand storytelling, style tips, and behind-the-scenes content.'],
        ],
    ];

    $extra = $industry_pages[$industry] ?? [
        ['name' => 'Services',  'slug' => '/services',  'icon' => '⭐', 'priority' => 'high',   'desc' => 'What you offer — describe your core products or services in detail.'],
        ['name' => 'Portfolio', 'slug' => '/portfolio', 'icon' => '🗂️', 'priority' => 'medium', 'desc' => 'Showcase your work, projects, or past clients. Builds credibility.'],
        ['name' => 'Blog',      'slug' => '/blog',      'icon' => '📝', 'priority' => 'low',    'desc' => 'Content marketing — share insights, news, and tips for SEO and authority.'],
        ['name' => 'FAQ',       'slug' => '/faq',       'icon' => '❓', 'priority' => 'low',    'desc' => 'Answer common questions to reduce support load and build confidence.'],
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

    $handles = [
        ['platform' => 'Facebook',   'icon' => 'fb',  'handle' => '@' . $biz_slug],
        ['platform' => 'Instagram',  'icon' => 'ig',  'handle' => '@' . $biz_slug],
        ['platform' => 'TikTok',     'icon' => 'tt',  'handle' => '@' . $biz_slug],
        ['platform' => 'YouTube',    'icon' => 'yt',  'handle' => '@' . $biz_slug],
        ['platform' => 'X (Twitter)','icon' => 'x',   'handle' => '@' . $biz_slug],
        ['platform' => 'LinkedIn',   'icon' => 'li',  'handle' => $biz_slug],
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
        <div class="bae-card-title" style="font-size:20px;">🚀 Startup Toolkit</div>
        <div class="bae-card-desc" style="margin-top:6px;">Everything you need to launch <?php echo esc_html($biz_raw); ?> — from domain to legal registration. All generated from your brand profile.</div>
    </div>

    <!-- SECTION: Domain Names -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">🌐 Domain Name Ideas</div>
                <div class="bae-card-desc">Suggested domain names based on your business name. Check availability before registering.</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <?php foreach ($domains as $d): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid <?php echo $d['rec'] ? '#111827' : '#e5e7eb'; ?>;border-radius:10px;background:<?php echo $d['rec'] ? '#111827' : '#fff'; ?>;">
                <div>
                    <div style="font-size:14px;font-weight:600;color:<?php echo $d['rec'] ? '#fff' : '#374151'; ?>;font-family:'Courier New',monospace;"><?php echo esc_html($d['domain']); ?></div>
                    <div style="font-size:11px;color:<?php echo $d['rec'] ? 'rgba(255,255,255,0.5)' : '#9ca3af'; ?>;margin-top:2px;"><?php echo esc_html($d['note']); ?></div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <?php if ($d['rec']): ?><span style="font-size:10px;background:<?php echo '#fff'; ?>;color:#111827;padding:2px 8px;border-radius:999px;font-weight:700;">★ TOP</span><?php endif; ?>
                    <button onclick="navigator.clipboard.writeText('<?php echo esc_js($d['domain']); ?>');this.textContent='✓';setTimeout(()=>this.textContent='Copy',1500);"
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
                <div class="bae-card-title">📱 Social Media Handles</div>
                <div class="bae-card-desc">Register the same handle on every platform — consistency is a brand asset.</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
            <?php foreach ($handles as $h): ?>
            <div style="padding:14px 16px;border:1px solid #e5e7eb;border-radius:10px;">
                <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px;"><?php echo esc_html($h['platform']); ?></div>
                <div style="font-size:14px;font-weight:700;color:#111827;font-family:'Courier New',monospace;"><?php echo esc_html($h['handle']); ?></div>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($h['handle']); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500);"
                        style="margin-top:8px;border:1px solid #e5e7eb;background:none;color:#6b7280;font-size:11px;cursor:pointer;padding:4px 10px;border-radius:6px;width:100%;">Copy</button>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="bae-notice bae-notice-info" style="margin-top:16px;">
            💡 Register all handles even if you're not active on every platform yet — prevents brand squatting and ensures consistency when you scale.
        </div>
    </div>

    <!-- SECTION: Tagline Variants -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">✍️ Tagline Variants</div>
                <div class="bae-card-desc">Use these across your website, social bios, packaging, and marketing materials.</div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($tagline_variants as $i => $variant): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border:1px solid <?php echo $i === 0 ? '#111827' : '#e5e7eb'; ?>;border-radius:10px;background:<?php echo $i === 0 ? '#fafafa' : '#fff'; ?>;">
                <div>
                    <span style="font-size:15px;color:#374151;font-style:italic;">"<?php echo esc_html($variant); ?>"</span>
                    <?php if ($i === 0 && !empty($p['tagline'])): ?><span style="margin-left:10px;font-size:10px;background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:999px;font-weight:700;">CURRENT</span><?php endif; ?>
                </div>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($variant); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500);"
                        style="border:1px solid #d1d5db;background:none;color:#6b7280;font-size:11px;cursor:pointer;padding:5px 12px;border-radius:6px;white-space:nowrap;">Copy</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SECTION: Business Email -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">📧 Business Email Suggestions</div>
                <div class="bae-card-desc">A professional email builds trust immediately. Avoid using personal Gmail for business communications.</div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($email_ideas as $email => $note): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid #e5e7eb;border-radius:10px;">
                <div>
                    <div style="font-size:14px;font-weight:600;color:#374151;font-family:'Courier New',monospace;"><?php echo esc_html($email); ?></div>
                    <div style="font-size:12px;color:#9ca3af;margin-top:3px;"><?php echo esc_html($note); ?></div>
                </div>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($email); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500);"
                        style="border:1px solid #d1d5db;background:none;color:#6b7280;font-size:11px;cursor:pointer;padding:5px 12px;border-radius:6px;">Copy</button>
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
                <div class="bae-card-title">🗺️ Recommended Website Pages</div>
                <div class="bae-card-desc">Based on your industry (<?php echo esc_html($industry); ?>), these are the pages your website needs.</div>
            </div>
            <a href="?tab=assets" class="bae-btn bae-btn-outline bae-btn-sm">Generate Full Sitemap →</a>
        </div>
        <div style="display:flex;flex-direction:column;gap:0;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
            <?php foreach ($pages as $i => $page):
                $bg = $i % 2 === 0 ? '#fff' : '#fafafa';
                $priority_bg = $page['priority'] === 'high' ? '#d1fae5' : ($page['priority'] === 'medium' ? '#fef3c7' : '#f3f4f6');
                $priority_txt = $page['priority'] === 'high' ? '#065f46' : ($page['priority'] === 'medium' ? '#92400e' : '#6b7280');
            ?>
            <div style="display:grid;grid-template-columns:36px 130px 60px 1fr;gap:0;background:<?php echo $bg; ?>;border-bottom:1px solid #f3f4f6;align-items:center;">
                <div style="padding:12px;text-align:center;font-size:16px;"><?php echo $page['icon']; ?></div>
                <div style="padding:10px 8px;font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html($page['name']); ?></div>
                <div style="padding:10px 8px;">
                    <span style="font-size:10px;padding:2px 8px;border-radius:999px;background:<?php echo $priority_bg; ?>;color:<?php echo $priority_txt; ?>;font-weight:700;"><?php echo ucfirst($page['priority']); ?></span>
                </div>
                <div style="padding:10px 16px;font-size:12px;color:#6b7280;line-height:1.6;"><?php echo esc_html($page['desc']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SECTION: Launch Checklist -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">✅ Launch Checklist for <?php echo esc_html($biz_raw); ?></div>
                <div class="bae-card-desc">Complete these steps to officially launch your business online and legally in the Philippines.</div>
            </div>
        </div>
        <?php foreach ($checklist as $section => $tasks): ?>
        <div style="margin-bottom:24px;">
            <div class="bae-section-label" style="margin-bottom:10px;"><?php echo $section; ?></div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <?php foreach ($tasks as $task): ?>
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:12px 14px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;">
                    <div style="display:flex;align-items:flex-start;gap:10px;flex:1;">
                        <input type="checkbox" style="margin-top:3px;width:16px;height:16px;cursor:pointer;flex-shrink:0;">
                        <div>
                            <div style="font-size:13px;color:#374151;font-weight:500;"><?php echo esc_html($task['task']); ?></div>
                            <div style="font-size:11px;color:#9ca3af;margin-top:3px;">🔧 <?php echo esc_html($task['tools']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

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
              . "<div style='font-size:16px;margin-bottom:4px;'>{$page['icon']}</div>"
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
              . "<div style='padding:12px;text-align:center;font-size:14px;'>{$page['icon']}</div>"
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
