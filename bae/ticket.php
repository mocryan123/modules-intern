<?php
/**
 * BAE Ticketing System
 * Handles client identity via ticket codes (BAE-XXXX-XXXX)
 * Loaded via require_once from main.php — zero changes to main.php internals
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// =============================================================================
// HOOKS — registered once when file loads
// =============================================================================

// nopriv versions of existing handlers so clients work without WP login
add_action( 'wp_ajax_nopriv_bae_save_profile',      'bntm_ajax_bae_save_profile' );
add_action( 'wp_ajax_nopriv_bae_generate_asset',    'bntm_ajax_bae_generate_asset' );
add_action( 'wp_ajax_nopriv_bae_delete_asset',      'bntm_ajax_bae_delete_asset' );
add_action( 'wp_ajax_nopriv_bae_reset_profile',     'bntm_ajax_bae_reset_profile' );
add_action( 'wp_ajax_nopriv_bae_save_kit_settings', 'bntm_ajax_bae_save_kit_settings' );

// ticket-specific AJAX — unique name, no conflict risk
add_action( 'wp_ajax_nopriv_bae_ticket_check',    'bntm_bae_ajax_ticket_check' );
add_action( 'wp_ajax_bae_ticket_check',           'bntm_bae_ajax_ticket_check' );
add_action( 'wp_ajax_nopriv_bae_ticket_generate', 'bntm_bae_ajax_ticket_generate' );
add_action( 'wp_ajax_bae_ticket_generate',        'bntm_bae_ajax_ticket_generate' );

// DB migration runs only inside wp-admin, never on frontend
add_action( 'admin_init', 'bntm_bae_ticket_migrate' );
add_action( 'wp_ajax_nopriv_bae_ticket_logout', 'bntm_bae_ajax_ticket_logout' );
add_action( 'wp_ajax_bae_ticket_logout',        'bntm_bae_ajax_ticket_logout' );

// =============================================================================
// TICKET HELPERS — all prefixed bntm_bae_ to avoid any conflict
// =============================================================================

/**
 * Read ticket from cookie → GET → POST (in that priority)
 * Cookie is set client-side after validation, lives 1 year
 */
function bntm_bae_read_ticket() {
    if ( ! empty( $_COOKIE['bae_ticket'] ) )
        return strtoupper( sanitize_text_field( $_COOKIE['bae_ticket'] ) );
    if ( ! empty( $_GET['bae_ticket'] ) )
        return strtoupper( sanitize_text_field( $_GET['bae_ticket'] ) );
    if ( ! empty( $_POST['bae_ticket'] ) )
        return strtoupper( sanitize_text_field( $_POST['bae_ticket'] ) );
    return '';
}

/**
 * Validate ticket format: BAE-XXXX-XXXX (uppercase letters + digits, no 0/O/1/I)
 */
function bntm_bae_ticket_valid( $t ) {
    return (bool) preg_match( '/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $t );
}

/**
 * Get profile row by ticket — wrapped in hide_errors in case column not yet migrated
 */
function bntm_bae_profile_by_ticket( $ticket ) {
    global $wpdb;
    $wpdb->hide_errors();
    $row = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s", $ticket ),
        ARRAY_A
    );
    $wpdb->show_errors();
    return $row;
}

/**
 * Count generated assets by ticket
 */
function bntm_bae_count_by_ticket( $ticket ) {
    global $wpdb;
    $wpdb->hide_errors();
    $n = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bae_assets WHERE ticket = %s AND is_generated = 1",
            $ticket
        )
    );
    $wpdb->show_errors();
    return $n;
}

// =============================================================================
// AJAX: ticket check
// Client submits ticket code → server confirms format is valid → returns has_profile
// Cookie is set client-side in JS after success (avoids headers-already-sent issues)
// =============================================================================

function bntm_bae_ajax_ticket_check() {
    $ticket = strtoupper( sanitize_text_field( $_POST['ticket'] ?? '' ) );

    if ( ! bntm_bae_ticket_valid( $ticket ) ) {
        wp_send_json_error( [ 'message' => 'Invalid ticket format. Expected: BAE-XXXX-XXXX' ] );
    }

    // Ticket must exist in DB — no random codes allowed
    global $wpdb;
    $wpdb->hide_errors();
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s", $ticket
    ) );
    $wpdb->show_errors();

    if ( ! $exists ) {
        wp_send_json_error( [ 'message' => 'Ticket not found. Check your code and try again.' ] );
    }

    $profile = bntm_bae_profile_by_ticket( $ticket );

    wp_send_json_success( [
        'ticket'      => $ticket,
        'has_profile' => ! empty( $profile ),
    ] );
}

/**
 * Generate a fresh ticket for new users — self-serve, no admin needed
 * Format: BAE-XXXX-XXXX using unambiguous chars (no 0/O/1/I)
 */
function bntm_bae_ajax_ticket_generate() {
    $chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $len    = strlen( $chars );
    $part1  = '';
    $part2  = '';
    for ( $i = 0; $i < 4; $i++ ) $part1 .= $chars[ wp_rand( 0, $len - 1 ) ];
    for ( $i = 0; $i < 4; $i++ ) $part2 .= $chars[ wp_rand( 0, $len - 1 ) ];
    $ticket = 'BAE-' . $part1 . '-' . $part2;

    global $wpdb;
    $wpdb->hide_errors();

    // Check collision
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s", $ticket
    ) );

    if ( $exists ) {
        $wpdb->show_errors();
        wp_send_json_error( [ 'message' => 'Please try again.' ] );
    }

    // Reserve the ticket in DB immediately as an empty profile row
    // This is what makes the ticket "real" — it exists in DB, can be found later
    $r = $wpdb->insert( $wpdb->prefix . 'bae_profiles', [
        'rand_id'        => bntm_rand_id(),
        'ticket'         => $ticket,
        'user_id'        => is_user_logged_in() ? get_current_user_id() : 0,
        'business_name'  => '',
        'kit_slug'       => 'draft-' . strtolower( $part1 . $part2 ),
        'kit_visibility' => 'private',
    ] );

    $wpdb->show_errors();

    if ( $r === false ) {
        wp_send_json_error( [ 'message' => 'Could not reserve ticket. Please try again.' ] );
    }

    wp_send_json_success( [ 'ticket' => $ticket ] );
}

// =============================================================================
// DB MIGRATION — adds ticket column to existing installs
// Runs only on admin_init, never on frontend, hide_errors protects against
// any failure on fresh installs where table doesn't exist yet
// =============================================================================

/**
 * AJAX: logout — expire the cookie server-side
 * Client JS also clears it locally
 */
function bntm_bae_ajax_ticket_logout() {
    // Expire cookie server-side
    setcookie( 'bae_ticket', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false );
    wp_send_json_success( [ 'message' => 'Logged out.' ] );
}

/**
 * Inject ticket card into settings tab output
 * Hooked via output buffer on the settings tab return value
 */
function bntm_bae_inject_settings_ticket( $html ) {
    $ticket = bntm_bae_read_ticket();
    if ( empty( $ticket ) ) return $html;

    $aj     = esc_js( admin_url( 'admin-ajax.php' ) );
    $tk_esc = esc_html( $ticket );
    $tk_js  = esc_js( $ticket );

    $card = '
    <div class="bae-card" style="margin-bottom:0;">
        <div class="bae-card-title">Your Session Ticket</div>
        <div class="bae-card-desc" style="margin-top:4px;margin-bottom:20px;">
            This is your permanent access code. Save it to open your workspace from any device.
        </div>
        <div style="background:var(--bg-3);border:1.5px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <code style="font-family:\'Geist\',monospace;font-size:20px;font-weight:800;letter-spacing:0.16em;color:var(--brand-soft);">' . $tk_esc . '</code>
            <button id="bae-tk-copy-settings" onclick="baeTkCopySettings()" style="background:var(--surface);border:1px solid var(--border-2);border-radius:8px;padding:8px 16px;font-size:12px;font-weight:700;color:var(--text-2);cursor:pointer;font-family:\'Geist\',sans-serif;display:flex;align-items:center;gap:6px;transition:all .2s;">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                Copy
            </button>
        </div>
        <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text-3);margin-bottom:20px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            Share this code to resume your session on another device.
        </div>
        <button id="bae-tk-logout" onclick="baeTkLogout()" style="background:rgba(244,63,94,0.06);border:1px solid rgba(244,63,94,0.2);border-radius:10px;padding:10px 18px;font-size:13px;font-weight:600;color:#fb7185;cursor:pointer;font-family:\'Geist\',sans-serif;display:flex;align-items:center;gap:8px;transition:all .2s;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign out of this device
        </button>
    </div>
    <script>
    function baeTkCopySettings() {
        var btn = document.getElementById(\'bae-tk-copy-settings\');
        navigator.clipboard.writeText(\'' . $tk_js . '\').then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = \'<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Copied!\';
            btn.style.color = \'#34d399\'; btn.style.borderColor = \'rgba(52,211,153,0.3)\';
            setTimeout(function() { btn.innerHTML = orig; btn.style.color = \'\'; btn.style.borderColor = \'\'; }, 2000);
        });
    }
    function baeTkLogout() {
        var btn = document.getElementById(\'bae-tk-logout\');
        btn.disabled = true; btn.textContent = \'Signing out...\';
        var fd = new FormData(); fd.append(\'action\', \'bae_ticket_logout\');
        fetch(\'' . $aj . '\', {method:\'POST\',body:fd}).finally(function() {
            // Clear cookie client-side regardless of server response
            document.cookie = \'bae_ticket=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax\';
            window.location.href = window.location.pathname;
        });
    }
    </script>
    ';

    // Inject before the first <div class="bae-card"> in the settings output
    return $card . $html;
}

function bntm_bae_ticket_migrate() {
    global $wpdb;
    $wpdb->hide_errors();

    $profiles = $wpdb->prefix . 'bae_profiles';
    $assets   = $wpdb->prefix . 'bae_assets';

    $has_p = $wpdb->get_results( "SHOW COLUMNS FROM `{$profiles}` LIKE 'ticket'" );
    if ( empty( $has_p ) ) {
        $wpdb->query( "ALTER TABLE `{$profiles}` ADD COLUMN ticket VARCHAR(20) NOT NULL DEFAULT '' AFTER user_id" );
        $wpdb->query( "ALTER TABLE `{$profiles}` ADD INDEX bae_ticket_idx (ticket)" );
    }

    $has_a = $wpdb->get_results( "SHOW COLUMNS FROM `{$assets}` LIKE 'ticket'" );
    if ( empty( $has_a ) ) {
        $wpdb->query( "ALTER TABLE `{$assets}` ADD COLUMN ticket VARCHAR(20) NOT NULL DEFAULT '' AFTER user_id" );
        $wpdb->query( "ALTER TABLE `{$assets}` ADD INDEX bae_ticket_a_idx (ticket)" );
    }

    $wpdb->show_errors();
}

// =============================================================================
// TICKET SCREEN UI
// Full-screen dark entry screen shown when no valid ticket in cookie/GET
// =============================================================================

function bntm_bae_ticket_screen() {
    ob_start(); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    .baetk { font-family:'Geist',-apple-system,sans-serif; background:#09090e; color:#ede9ff; min-height:520px; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 24px; position:relative; overflow:hidden; border-radius:20px; isolation:isolate; }
    .baetk::before { content:''; position:absolute; width:480px; height:480px; border-radius:50%; background:radial-gradient(circle,rgba(139,92,246,.13) 0%,transparent 70%); top:-160px; right:-80px; pointer-events:none; z-index:0; }
    .baetk::after  { content:''; position:absolute; width:320px; height:320px; border-radius:50%; background:radial-gradient(circle,rgba(236,72,153,.08) 0%,transparent 70%); bottom:-80px; left:-80px; pointer-events:none; z-index:0; }
    .baetk-in { width:100%; max-width:400px; text-align:center; position:relative; z-index:2; display:flex; flex-direction:column; align-items:center; }
    .baetk-logo { width:56px; height:56px; background:linear-gradient(135deg,#6d28d9,#ec4899); border-radius:18px; display:flex; align-items:center; justify-content:center; margin:0 auto 28px; box-shadow:0 0 36px rgba(139,92,246,.4); position:relative; z-index:2; }
    .baetk-logo svg { width:26px; height:26px; }
    .baetk-title { font-family:'Instrument Serif',serif; font-size:32px; font-style:italic; color:#ede9ff; margin-bottom:8px; line-height:1.2; width:100%; position:relative; z-index:2; }
    .baetk-sub { font-size:14px; color:#4d4a65; margin-bottom:36px; line-height:1.7; width:100%; position:relative; z-index:2; }
    .baetk-inp { width:100%; background:rgba(255,255,255,.05); border:1.5px solid rgba(139,92,246,.2); border-radius:14px; padding:16px 20px; font-size:22px; font-family:'Geist',monospace; font-weight:700; letter-spacing:.15em; color:#ede9ff; outline:none; text-align:center; text-transform:uppercase; transition:border-color .2s,box-shadow .2s; margin-bottom:14px; display:block; box-sizing:border-box; position:relative; z-index:2; pointer-events:auto; }
    .baetk-inp:focus { border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.15); }
    .baetk-inp::placeholder { color:#2a2740; font-size:15px; letter-spacing:.08em; }
    .baetk-btn { width:100%; background:linear-gradient(135deg,#6d28d9,#8b5cf6); color:white; border:none; border-radius:14px; padding:15px 24px; font-size:15px; font-weight:700; font-family:'Geist',sans-serif; cursor:pointer; transition:all .2s; box-shadow:0 8px 28px rgba(109,40,217,.4); display:flex; align-items:center; justify-content:center; gap:10px; box-sizing:border-box; position:relative; z-index:2; pointer-events:auto; }
    .baetk-btn:hover { transform:translateY(-2px); box-shadow:0 12px 36px rgba(109,40,217,.5); }
    .baetk-btn:disabled { opacity:.38; cursor:not-allowed; transform:none; box-shadow:none; }
    .baetk-err { width:100%; font-size:13px; color:#fb7185; margin-top:10px; display:none; background:rgba(244,63,94,.08); border:1px solid rgba(244,63,94,.2); border-radius:10px; padding:10px 14px; box-sizing:border-box; text-align:center; position:relative; z-index:2; }
    .baetk-hint { font-size:12px; color:#4d4a65; margin-top:14px; line-height:1.7; width:100%; position:relative; z-index:2; }
    .baetk-hint strong { color:#8b88a4; }
    .baetk-new-btn { width:100%; background:transparent; color:#6d5fad; border:1.5px solid rgba(139,92,246,0.2); border-radius:14px; padding:13px 24px; font-size:14px; font-weight:600; font-family:'Geist',sans-serif; cursor:pointer; transition:all .2s; display:flex; align-items:center; justify-content:center; gap:8px; box-sizing:border-box; position:relative; z-index:2; pointer-events:auto; }
    .baetk-new-btn:hover { background:rgba(139,92,246,0.08); border-color:rgba(139,92,246,0.4); color:#a78bfa; }
    .baetk-new-btn svg { flex-shrink:0; }
    .baetk-spin { width:18px; height:18px; border:2px solid rgba(255,255,255,.3); border-top-color:white; border-radius:50%; animation:baetk-sp .7s linear infinite; display:none; flex-shrink:0; }
    @keyframes baetk-sp { to { transform:rotate(360deg); } }
    </style>

    <div class="baetk">
        <div class="baetk-in" id="baetk-in">
            <div class="baetk-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            </div>
            <div class="baetk-title">Brand Asset Engine</div>
            <div class="baetk-sub">Enter your ticket code to access<br>your brand workspace.</div>
            <input type="text" id="baetk-f" class="baetk-inp"
                   placeholder="BAE-XXXX-XXXX"
                   maxlength="13" autocomplete="off" spellcheck="false"
                   inputmode="text">
            <button class="baetk-btn" id="baetk-btn" onclick="baeTkGo()">
                <div class="baetk-spin" id="baetk-sp"></div>
                <span id="baetk-lbl">Enter Workspace</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" id="baetk-arrow"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
            <div class="baetk-err" id="baetk-err"></div>
            <div class="baetk-hint">
                Already have a ticket? Enter it above.
            </div>

            <!-- Divider -->
            <div style="display:flex;align-items:center;gap:12px;margin:20px 0;">
                <div style="flex:1;height:1px;background:rgba(255,255,255,0.07);"></div>
                <span style="font-size:11px;color:#2a2740;font-weight:600;letter-spacing:0.08em;">OR</span>
                <div style="flex:1;height:1px;background:rgba(255,255,255,0.07);"></div>
            </div>

            <!-- New user -->
            <button class="baetk-new-btn" id="baetk-new" onclick="baeTkNew()">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                New here? Start for free
            </button>
        </div>
    </div>

    <script>
    /* BAE Ticket Screen — all functions global, no IIFE */
    var _baeTkAj = '<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>';

    function _baeTkEl(id) { return document.getElementById(id); }

    // Auto-format input as user types → BAE-XXXX-XXXX
    document.addEventListener('DOMContentLoaded', function() {
        var f = _baeTkEl('baetk-f');
        if (!f) return;

        f.addEventListener('input', function() {
            var raw = this.value.replace(/[^A-Z0-9]/gi, '').toUpperCase().substring(0, 12);
            var out = raw;
            if (raw.length > 3 && raw.substring(0, 3) === 'BAE') {
                var rest = raw.substring(3);
                out = rest.length <= 4 ? 'BAE-' + rest : 'BAE-' + rest.substring(0, 4) + '-' + rest.substring(4, 8);
            }
            this.value = out;
            var err = _baeTkEl('baetk-err');
            if (err) err.style.display = 'none';
        });

        f.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') baeTkGo();
        });

        setTimeout(function() { f.focus(); }, 380);

        if (window.gsap) {
            gsap.fromTo('#baetk-in', {opacity:0, y:24}, {opacity:1, y:0, duration:.55, ease:'power3.out', delay:.1});
            gsap.to('.baetk-logo', {boxShadow:'0 0 44px rgba(139,92,246,.55)', duration:1.8, repeat:-1, yoyo:true, ease:'sine.inOut', delay:1});
        }
    });

    function baeTkGo() {
        var f   = _baeTkEl('baetk-f');
        var btn = _baeTkEl('baetk-btn');
        var sp  = _baeTkEl('baetk-sp');
        var lbl = _baeTkEl('baetk-lbl');
        var arr = _baeTkEl('baetk-arrow');
        var err = _baeTkEl('baetk-err');
        if (!f || !btn) return;

        var t = (f.value || '').trim().toUpperCase();

        if (!t) {
            if (err) { err.textContent = 'Please enter your ticket code.'; err.style.display = 'block'; }
            f.focus();
            return;
        }

        if (!/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(t)) {
            if (err) { err.textContent = 'Invalid format. Expected: BAE-XXXX-XXXX'; err.style.display = 'block'; }
            if (window.gsap) gsap.fromTo(f, {x:-5}, {x:0, duration:.35, ease:'elastic.out(1,.4)'});
            return;
        }

        btn.disabled = true;
        if (sp)  sp.style.display  = 'block';
        if (arr) arr.style.display = 'none';
        if (lbl) lbl.textContent   = 'Checking...';

        var fd = new FormData();
        fd.append('action', 'bae_ticket_check');
        fd.append('ticket', t);

        fetch(_baeTkAj, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(j) {
            if (j.success) {
                var exp = new Date(Date.now() + 365*24*60*60*1000).toUTCString();
                document.cookie = 'bae_ticket=' + encodeURIComponent(j.data.ticket) + '; expires=' + exp + '; path=/; SameSite=Lax';
                if (lbl) lbl.textContent = 'Opening...';
                if (window.gsap) {
                    gsap.to('#baetk-in', {opacity:0, y:-20, duration:.3, ease:'power2.in', onComplete:function(){ window.location.reload(); }});
                } else {
                    window.location.reload();
                }
            } else {
                btn.disabled = false;
                if (sp)  sp.style.display  = 'none';
                if (arr) arr.style.display = '';
                if (lbl) lbl.textContent   = 'Enter Workspace';
                if (err) { err.textContent = (j.data && j.data.message) ? j.data.message : 'Ticket not recognized.'; err.style.display = 'block'; }
            }
        })
        .catch(function() {
            btn.disabled = false;
            if (sp)  sp.style.display  = 'none';
            if (arr) arr.style.display = '';
            if (lbl) lbl.textContent   = 'Enter Workspace';
            if (err) { err.textContent = 'Connection error. Please try again.'; err.style.display = 'block'; }
        });
    }

    function baeTkNew() {
        var newBtn = _baeTkEl('baetk-new');
        var err    = _baeTkEl('baetk-err');
        if (!newBtn) return;

        newBtn.disabled = true;
        newBtn.innerHTML = '<div class="baetk-spin" style="display:block;border-top-color:#a78bfa;border-color:rgba(139,92,246,.3);margin:0 auto;"></div>';

        var fd = new FormData();
        fd.append('action', 'bae_ticket_generate');

        fetch(_baeTkAj, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(j) {
            if (j.success) {
                var ticket = j.data.ticket;
                var exp = new Date(Date.now() + 365*24*60*60*1000).toUTCString();
                document.cookie = 'bae_ticket=' + encodeURIComponent(ticket) + '; expires=' + exp + '; path=/; SameSite=Lax';

                var overlay = document.createElement('div');
                overlay.id = 'baetk-reveal';
                overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.85);display:flex;align-items:center;justify-content:center;padding:24px;';
                overlay.innerHTML = [
                    '<div style="background:#13111f;border:1px solid rgba(139,92,246,0.3);border-radius:20px;padding:36px 32px;max-width:420px;width:100%;text-align:center;">',
                        '<div style="width:48px;height:48px;background:linear-gradient(135deg,#6d28d9,#ec4899);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">',
                            '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"22\" height=\"22\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"white\" stroke-width=\"2.5\"><path d=\"M20 6 9 17l-5-5\"/></svg>',
                        '</div>',
                        '<div style="font-family:Instrument Serif,serif;font-size:26px;font-style:italic;color:#ede9ff;margin-bottom:8px;">Your ticket is ready</div>',
                        '<div style="font-size:13px;color:#4d4a65;margin-bottom:24px;">Save this code to access your workspace from any device.</div>',
                        '<div style="background:rgba(139,92,246,0.1);border:1.5px solid rgba(139,92,246,0.25);border-radius:12px;padding:16px 20px;margin-bottom:8px;">',
                            '<div style="font-size:11px;font-weight:700;color:#4d4a65;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:8px;">Your Ticket Code</div>',
                            '<div style="font-family:monospace;font-size:26px;font-weight:800;letter-spacing:0.18em;color:#ede9ff;">' + ticket + '</div>',
                        '</div>',
                        '<button onclick="baeTkCopyReveal(\'' + ticket + '\')" style="width:100%;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:10px;font-size:13px;font-weight:600;color:#8b88a4;cursor:pointer;margin-bottom:16px;" id="baetk-copy-reveal">Copy ticket code</button>',
                        '<button onclick="baeTkRevealContinue()" style="width:100%;background:linear-gradient(135deg,#6d28d9,#8b5cf6);color:white;border:none;border-radius:12px;padding:14px 24px;font-size:15px;font-weight:700;cursor:pointer;">Continue to workspace</button>',
                        '<div style="font-size:11px;color:#2a2740;margin-top:16px;">You can find this code in Settings anytime.</div>',
                    '</div>'
                ].join('');
                document.body.appendChild(overlay);

                if (window.gsap) {
                    gsap.fromTo(overlay.firstElementChild, {opacity:0,scale:0.92,y:20}, {opacity:1,scale:1,y:0,duration:.4,ease:'back.out(1.5)'});
                }
            } else {
                newBtn.disabled = false;
                newBtn.innerHTML = '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"15\" height=\"15\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\"><path d=\"M12 5v14M5 12h14\"/></svg> New here? Start for free';
                if (err) { err.textContent = (j.data && j.data.message) ? j.data.message : 'Could not generate ticket.'; err.style.display = 'block'; }
            }
        })
        .catch(function() {
            newBtn.disabled = false;
            newBtn.innerHTML = 'New here? Start for free';
            if (err) { err.textContent = 'Connection error. Please try again.'; err.style.display = 'block'; }
        });
    }

    function baeTkCopyReveal(ticket) {
        var btn = _baeTkEl('baetk-copy-reveal');
        navigator.clipboard.writeText(ticket).then(function() {
            if (btn) { btn.textContent = 'Copied!'; btn.style.color = '#34d399'; }
            setTimeout(function() { if (btn) { btn.textContent = 'Copy ticket code'; btn.style.color = ''; } }, 2000);
        });
    }

    function baeTkRevealContinue() {
        var overlay = _baeTkEl('baetk-reveal');
        if (window.gsap && overlay) {
            gsap.to(overlay.firstElementChild, {opacity:0, scale:0.95, duration:.25, ease:'power2.in', onComplete:function(){ window.location.reload(); }});
        } else {
            window.location.reload();
        }
    }
    </script>
    <?php
    return ob_get_clean();
}

// =============================================================================
// OVERRIDE: bntm_shortcode_bae routing
// Wraps the original shortcode to inject ticket gate before anything else.
// We remove the original shortcode and re-register with our wrapped version.
// =============================================================================

// Wait until all shortcodes are registered, then override
add_action( 'init', 'bntm_bae_ticket_override_shortcode', 20 );

function bntm_bae_ticket_override_shortcode() {
    // Remove original shortcode registered in main.php
    remove_shortcode( 'bntm_bae_dashboard' );
    // Re-register with our ticket-aware wrapper
    add_shortcode( 'bntm_bae_dashboard', 'bntm_bae_ticket_shortcode_wrapper' );
}

function bntm_bae_ticket_shortcode_wrapper( $atts ) {
    // Step 1: read ticket
    $ticket = bntm_bae_read_ticket();
    $valid  = bntm_bae_ticket_valid( $ticket );

    // No valid ticket → show ticket screen
    if ( ! $valid ) {
        return bntm_bae_ticket_screen();
    }

    // Step 2: resolve profile by ticket ONLY
    // Ticket = identity. No fallback to user_id.
    // Each ticket is a separate brand/session.
    $profile = bntm_bae_profile_by_ticket( $ticket );

    // Valid ticket, no business name yet → show wizard
    if ( ( empty($profile) || empty($profile['business_name']) ) && ! isset( $_GET['tab'] ) ) {
        return bae_wizard_shortcode_with_ticket( $ticket );
    }

    // Step 3: logged-in WP user with valid ticket + profile
    // Call the original shortcode DIRECTLY — it has all CSS, JS, tabs
    if ( is_user_logged_in() ) {
        add_filter( 'bae_settings_tab_output', 'bntm_bae_inject_settings_ticket' );
        return bntm_shortcode_bae();
    }

    // Step 4: non-logged-in ticket user — use ticket-based dashboard
    $GLOBALS['bae_ticket_profile'] = $profile;
    $GLOBALS['bae_current_ticket'] = $ticket;
    add_filter( 'bae_settings_tab_output', 'bntm_bae_inject_settings_ticket' );
    return bntm_bae_dashboard_with_ticket( $ticket, $profile );
}

// =============================================================================
// DASHBOARD with ticket context
// Replicates bntm_shortcode_bae but uses ticket-resolved profile
// =============================================================================

function bntm_bae_dashboard_with_ticket( $ticket, $profile ) {
    $user_id    = is_user_logged_in() ? get_current_user_id() : 0;
    $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';

    ob_start(); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>
        var ajaxurl = '<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>';
        // Ticket helper — reads from cookie, appended to every AJAX call
        function bntmTk() {
            var m = document.cookie.match('(?:^|; )bae_ticket=([^;]*)');
            return m ? decodeURIComponent(m[1]) : '';
        }
    </script>

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
                  if (baeIsDark) { wrap.classList.remove('bae-light'); track.classList.add('on'); label.textContent = 'Light'; document.getElementById('bae-icon-sun').style.display=''; document.getElementById('bae-icon-moon').style.display='none'; }
                  else { wrap.classList.add('bae-light'); track.classList.remove('on'); label.textContent = 'Dark'; document.getElementById('bae-icon-sun').style.display='none'; document.getElementById('bae-icon-moon').style.display=''; }
              })
              .to(overlay, { opacity: 0, duration: 0.35, ease: 'power2.out' })
              .call(function() { overlay.remove(); });
            gsap.to('.bae-toggle-thumb', { x: baeIsDark ? 16 : 0, duration: 0.4, ease: 'back.out(1.8)' });
            gsap.fromTo('.bae-stat-card, .bae-card, .bae-asset-card', { scale: 0.995 }, { scale: 1, duration: 0.35, stagger: 0.01, ease: 'power3.out' });
        } else {
            if (baeIsDark) { wrap.classList.remove('bae-light'); track.classList.add('on'); label.textContent = 'Light'; document.getElementById('bae-icon-sun').style.display=''; document.getElementById('bae-icon-moon').style.display='none'; }
            else { wrap.classList.add('bae-light'); track.classList.remove('on'); label.textContent = 'Dark'; document.getElementById('bae-icon-sun').style.display='none'; document.getElementById('bae-icon-moon').style.display=''; }
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

    <div class="bae-wrap" id="bae-wrap">

        <?php
        // Reuse main.php header by reading the same HTML it outputs
        // We call the tab functions directly — they are defined in main.php
        // and already loaded since ticket.php is require_once'd after main.php

        // Header HTML — replicated from main.php (theme toggle + nav)
        $base_url = strtok( $_SERVER['REQUEST_URI'], '?' );
        $tabs = [
            'overview' => 'Brand Profile',
            'identity' => 'Identity Board',
            'assets'   => 'Asset Generator',
            'kit'      => 'Brand Kit',
            'startup'  => 'Launch Toolkit',
            'settings' => 'Settings',
        ];
        ?>

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

        <!-- Modals from main.php -->
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
        <div id="bae-confirm-overlay" class="bae-modal-overlay" style="display:none;">
            <div class="bae-modal bae-modal-sm">
                <div class="bae-modal-header"><span class="bae-modal-title">Confirm Action</span></div>
                <div class="bae-modal-body"><p id="bae-confirm-msg"></p></div>
                <div class="bae-modal-footer">
                    <button class="bae-btn bae-btn-outline" id="bae-confirm-cancel">Cancel</button>
                    <button class="bae-btn bae-btn-danger" id="bae-confirm-ok">Confirm</button>
                </div>
            </div>
        </div>

        <!-- Tab Nav -->
        <div class="bae-tabs">
            <?php foreach ( $tabs as $slug => $label ) :
                $class = $active_tab === $slug ? 'bae-tab active' : 'bae-tab'; ?>
                <a href="<?php echo esc_url( $base_url ); ?>?tab=<?php echo esc_attr( $slug ); ?>" class="<?php echo $class; ?>">
                    <?php echo esc_html( $label ); ?>
                    <?php if ( $slug === 'identity' && empty( $profile ) ) echo '<span class="bae-tab-lock">&#9670;</span>'; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Tab Content -->
        <div class="bae-tab-content">
            <?php
            if     ( $active_tab === 'overview' ) echo bae_overview_tab( $user_id, $profile );
            elseif ( $active_tab === 'identity' ) echo bae_identity_tab( $user_id, $profile );
            elseif ( $active_tab === 'assets'   ) echo bae_assets_tab(   $user_id, $profile );
            elseif ( $active_tab === 'kit'      ) echo bae_kit_tab(      $user_id, $profile );
            elseif ( $active_tab === 'startup'  ) echo bae_startup_tab(  $user_id, $profile );
            elseif ( $active_tab === 'settings' ) echo bae_settings_tab( $user_id, $profile );
            ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
}

// =============================================================================
// WIZARD with ticket context
// Wrapper around main.php's bae_wizard_shortcode that passes ticket to JS
// =============================================================================

function bae_wizard_shortcode_with_ticket( $ticket ) {
    // bae_wizard_shortcode($user_id) is defined in main.php
    // It doesn't use $user_id for anything critical in JS — the wizard
    // just calls bae_save_profile via AJAX. We call it normally.
    // The ticket gets appended client-side via the bntmTk() cookie helper.
    $user_id = is_user_logged_in() ? get_current_user_id() : 0;
    return bae_wizard_shortcode( $user_id );
}

// =============================================================================
// PATCH: make existing AJAX handlers also accept ticket
// These are thin wrappers — if ticket is present and valid, use it as identity.
// The originals in main.php check is_user_logged_in() first which still works
// for WP-logged-in users. For non-logged-in clients the nopriv hooks fire
// and the same function runs — it reads user_id=0 but also reads ticket.
// We hook into save_profile to also save the ticket on the profile row.
// =============================================================================

add_action( 'wp_ajax_nopriv_bae_save_profile', 'bntm_bae_ticket_patch_save_profile', 1 );
add_action( 'wp_ajax_bae_save_profile',        'bntm_bae_ticket_patch_save_profile', 1 );

function bntm_bae_ticket_patch_save_profile() {
    // This runs at priority 1, before the main handler at default priority 10
    // It adds the ticket to the $_POST so the main handler saves it naturally
    $ticket = bntm_bae_read_ticket();
    if ( bntm_bae_ticket_valid( $ticket ) && empty( $_POST['bae_ticket_injected'] ) ) {
        $_POST['bae_ticket_injected'] = '1';
        // We'll handle saving ticket in the after-save hook below
        // Store in global for the after hook
        $GLOBALS['bae_pending_ticket'] = $ticket;
    }
}

add_action( 'wp_ajax_nopriv_bae_save_profile', 'bntm_bae_ticket_after_save', 99 );
add_action( 'wp_ajax_bae_save_profile',        'bntm_bae_ticket_after_save', 99 );

function bntm_bae_ticket_after_save() {
    // Runs after main save handler — but save handler calls wp_send_json_success
    // which calls die() so this never actually runs.
    // Instead we use output buffering trick — not needed.
    // The right approach: just override save_profile entirely for nopriv.
    // See the nopriv-specific handler below.
}

// Remove the generic nopriv save_profile we added above and replace
// with a ticket-aware version for non-logged-in clients only
remove_action( 'wp_ajax_nopriv_bae_save_profile', 'bntm_ajax_bae_save_profile' );
remove_action( 'wp_ajax_nopriv_bae_save_profile', 'bntm_bae_ticket_patch_save_profile', 1 );
remove_action( 'wp_ajax_nopriv_bae_save_profile', 'bntm_bae_ticket_after_save', 99 );
add_action(    'wp_ajax_nopriv_bae_save_profile', 'bntm_bae_nopriv_save_profile' );

remove_action( 'wp_ajax_nopriv_bae_generate_asset',    'bntm_ajax_bae_generate_asset' );
add_action(    'wp_ajax_nopriv_bae_generate_asset',    'bntm_bae_nopriv_generate_asset' );

remove_action( 'wp_ajax_nopriv_bae_delete_asset',      'bntm_ajax_bae_delete_asset' );
add_action(    'wp_ajax_nopriv_bae_delete_asset',      'bntm_bae_nopriv_delete_asset' );

remove_action( 'wp_ajax_nopriv_bae_reset_profile',     'bntm_ajax_bae_reset_profile' );
add_action(    'wp_ajax_nopriv_bae_reset_profile',     'bntm_bae_nopriv_reset_profile' );

remove_action( 'wp_ajax_nopriv_bae_save_kit_settings', 'bntm_ajax_bae_save_kit_settings' );
add_action(    'wp_ajax_nopriv_bae_save_kit_settings', 'bntm_bae_nopriv_save_kit_settings' );

// =============================================================================
// NOPRIV AJAX HANDLERS — ticket-based, for non-logged-in clients
// =============================================================================

function bntm_bae_nopriv_save_profile() {
    check_ajax_referer( 'bae_save_profile', 'nonce' );
    $ticket = bntm_bae_read_ticket();
    if ( ! bntm_bae_ticket_valid( $ticket ) ) wp_send_json_error( [ 'message' => 'Invalid ticket.' ] );

    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    $data  = [
        'business_name'   => sanitize_text_field( $_POST['business_name']  ?? '' ),
        'industry'        => sanitize_text_field( $_POST['industry']        ?? '' ),
        'tagline'         => sanitize_text_field( $_POST['tagline']         ?? '' ),
        'personality'     => sanitize_text_field( $_POST['personality']     ?? '' ),
        'email'           => sanitize_email(      $_POST['email']           ?? '' ),
        'phone'           => sanitize_text_field( $_POST['phone']           ?? '' ),
        'website'         => esc_url_raw(         $_POST['website']         ?? '' ),
        'address'         => sanitize_text_field( $_POST['address']         ?? '' ),
        'primary_color'   => bae_safe_color( $_POST['primary_color']   ?? '', '#1a1a2e' ),
        'secondary_color' => bae_safe_color( $_POST['secondary_color'] ?? '', '#16213e' ),
        'accent_color'    => bae_safe_color( $_POST['accent_color']    ?? '', '#e94560' ),
        'font_heading'    => sanitize_text_field( $_POST['font_heading']    ?? 'Inter' ),
        'font_body'       => sanitize_text_field( $_POST['font_body']       ?? 'Inter' ),
        'logo_style'      => sanitize_text_field( $_POST['logo_style']      ?? 'wordmark' ),
        'logo_icon'       => sanitize_text_field( $_POST['logo_icon']       ?? '' ),
    ];
    if ( empty( $data['business_name'] ) ) wp_send_json_error( [ 'message' => 'Business name is required.' ] );

    $wpdb->hide_errors();
    $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table} WHERE ticket = %s", $ticket ) );
    if ( $existing ) {
        $wpdb->update( $table, $data, [ 'ticket' => $ticket ] );
        $wpdb->show_errors();
        wp_send_json_success( [ 'message' => 'Brand profile updated successfully!' ] );
    } else {
        $data['rand_id']  = bntm_rand_id();
        $data['ticket']   = $ticket;
        $data['user_id']  = 0;
        $data['kit_slug'] = sanitize_title( $data['business_name'] ) . '-' . substr( $data['rand_id'], 0, 6 );
        $r = $wpdb->insert( $table, $data );
        $wpdb->show_errors();
        if ( $r === false ) wp_send_json_error( [ 'message' => 'Failed to save. Please try again.' ] );
        wp_send_json_success( [ 'message' => 'Brand profile created successfully!' ] );
    }
}

function bntm_bae_nopriv_generate_asset() {
    check_ajax_referer( 'bae_generate_asset', 'nonce' );
    $ticket = bntm_bae_read_ticket();
    if ( ! bntm_bae_ticket_valid( $ticket ) ) wp_send_json_error( [ 'message' => 'Invalid ticket.' ] );

    global $wpdb;
    $asset_type = sanitize_text_field( $_POST['asset_type'] ?? '' );
    $profile_id = intval( $_POST['profile_id'] ?? 0 );
    $allowed    = [ 'business_card','letterhead','email_signature','social_kit','brand_guidelines','brand_book','sitemap' ];
    if ( ! in_array( $asset_type, $allowed ) ) wp_send_json_error( [ 'message' => 'Invalid asset type.' ] );

    $pt = $wpdb->prefix . 'bae_profiles';
    $at = $wpdb->prefix . 'bae_assets';

    $wpdb->hide_errors();
    $profile = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$pt} WHERE id = %d AND ticket = %s", $profile_id, $ticket
    ), ARRAY_A );
    $wpdb->show_errors();
    if ( ! $profile ) wp_send_json_error( [ 'message' => 'Profile not found.' ] );

    $html  = bae_generate_asset_html( $asset_type, $profile );
    $names = [ 'business_card'=>'Business Card','letterhead'=>'Letterhead','email_signature'=>'Email Signature','social_kit'=>'Social Media Kit','brand_guidelines'=>'Brand Guidelines','brand_book'=>'Brand Book','sitemap'=>'Site Structure' ];

    $wpdb->hide_errors();
    $ex = $wpdb->get_row( $wpdb->prepare(
        "SELECT id FROM {$at} WHERE profile_id = %d AND ticket = %s AND asset_type = %s",
        $profile_id, $ticket, $asset_type
    ) );
    if ( $ex ) {
        $wpdb->update( $at, [ 'asset_html'=>$html,'is_generated'=>1 ], [ 'id'=>$ex->id ] );
    } else {
        $wpdb->insert( $at, [ 'rand_id'=>bntm_rand_id(),'profile_id'=>$profile_id,'ticket'=>$ticket,'user_id'=>0,'asset_type'=>$asset_type,'asset_name'=>$names[$asset_type],'asset_html'=>$html,'is_generated'=>1 ] );
    }
    $wpdb->show_errors();
    wp_send_json_success( [ 'message' => 'Asset generated!', 'html' => $html ] );
}

function bntm_bae_nopriv_delete_asset() {
    check_ajax_referer( 'bae_generate_asset', 'nonce' );
    $ticket = bntm_bae_read_ticket();
    if ( ! bntm_bae_ticket_valid( $ticket ) ) wp_send_json_error( [ 'message' => 'Invalid ticket.' ] );

    global $wpdb;
    $wpdb->hide_errors();
    $r = $wpdb->delete( $wpdb->prefix . 'bae_assets', [ 'id'=>intval($_POST['asset_id']??0), 'ticket'=>$ticket ], [ '%d','%s' ] );
    $wpdb->show_errors();
    if ( $r ) wp_send_json_success( [ 'message' => 'Asset removed.' ] );
    else       wp_send_json_error(   [ 'message' => 'Failed to delete.' ] );
}

function bntm_bae_nopriv_save_kit_settings() {
    check_ajax_referer( 'bae_save_kit_settings', 'nonce' );
    $ticket = bntm_bae_read_ticket();
    if ( ! bntm_bae_ticket_valid( $ticket ) ) wp_send_json_error( [ 'message' => 'Invalid ticket.' ] );

    global $wpdb;
    $table      = $wpdb->prefix . 'bae_profiles';
    $visibility = sanitize_text_field( $_POST['kit_visibility'] ?? 'private' );
    $slug       = sanitize_title( $_POST['kit_slug'] ?? '' );
    if ( ! in_array( $visibility, [ 'public','private' ] ) ) wp_send_json_error( [ 'message' => 'Invalid visibility.' ] );
    if ( empty( $slug ) ) wp_send_json_error( [ 'message' => 'Kit slug cannot be empty.' ] );

    $wpdb->hide_errors();
    $conflict = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE kit_slug=%s AND ticket!=%s", $slug, $ticket ) );
    if ( $conflict ) { $wpdb->show_errors(); wp_send_json_error( [ 'message' => 'Slug already taken.' ] ); }
    $wpdb->update( $table, [ 'kit_visibility'=>$visibility,'kit_slug'=>$slug ], [ 'ticket'=>$ticket ] );
    $wpdb->show_errors();
    wp_send_json_success( [ 'message' => 'Kit settings saved!' ] );
}

function bntm_bae_nopriv_reset_profile() {
    check_ajax_referer( 'bae_reset_profile', 'nonce' );
    $ticket = bntm_bae_read_ticket();
    if ( ! bntm_bae_ticket_valid( $ticket ) ) wp_send_json_error( [ 'message' => 'Invalid ticket.' ] );

    global $wpdb;
    $wpdb->hide_errors();
    $wpdb->delete( $wpdb->prefix . 'bae_assets',   [ 'ticket'=>$ticket ], [ '%s' ] );
    $wpdb->delete( $wpdb->prefix . 'bae_profiles', [ 'ticket'=>$ticket ], [ '%s' ] );
    $wpdb->show_errors();
    wp_send_json_success( [ 'message' => 'Profile and assets reset.' ] );
}
