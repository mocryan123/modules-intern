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
// EMAIL via Gmail SMTP — set in .env:
//   BAE_SMTP_FROM=yourname@gmail.com
//   BAE_SMTP_PASS=xxxx xxxx xxxx xxxx  (Gmail App Password)
// =============================================================================
$bae_resend_last_error = '';

function bae_resend_email( $to, $subject, $text ) {
    global $bae_resend_last_error;
    $bae_resend_last_error = '';

    // First try database options, fallback to environment variables
    $from = get_option('bae_smtp_from') ?: getenv('BAE_SMTP_FROM');
    $pass = get_option('bae_smtp_pass') ?: getenv('BAE_SMTP_PASS');

    if ( empty( $from ) || empty( $pass ) ) {
        $bae_resend_last_error = 'BAE SMTP credentials are not set in settings.';
        error_log( '[BAE Mail] ' . $bae_resend_last_error );
        return false;
    }

    // Use PHPMailer — bundled with WordPress core, always available available
    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer( true );

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $from;
        $mail->Password   = $pass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom( $from, 'Mothie' );
        $mail->addAddress( $to );
        $mail->Subject = $subject;
        $mail->Body    = $text;

        $mail->send();
        error_log( '[BAE Mail] Sent OK to ' . $to );
        return true;

    } catch ( PHPMailer\PHPMailer\Exception $e ) {
        $bae_resend_last_error = $mail->ErrorInfo;
        error_log( '[BAE Mail] Failed: ' . $bae_resend_last_error );
        return false;
    }
}

// FIX Issue 5: Login code (email 2FA) actions
add_action( 'wp_ajax_nopriv_bae_toggle_login_code', 'bntm_bae_ajax_toggle_login_code' );
add_action( 'wp_ajax_bae_toggle_login_code',        'bntm_bae_ajax_toggle_login_code' );
add_action( 'wp_ajax_nopriv_bae_send_login_otp',    'bntm_bae_ajax_send_login_otp' );
add_action( 'wp_ajax_bae_send_login_otp',           'bntm_bae_ajax_send_login_otp' );
add_action( 'wp_ajax_nopriv_bae_verify_login_otp',  'bntm_bae_ajax_verify_login_otp' );
add_action( 'wp_ajax_bae_verify_login_otp',         'bntm_bae_ajax_verify_login_otp' );

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
    return (bool) preg_match( '/^(BAE|ADM)-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $t );
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

/**
 * Return the best-effort client IP for rate limiting.
 * Prefers proxy headers when present, falls back to REMOTE_ADDR.
 */
function bntm_bae_client_ip() {
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['HTTP_X_REAL_IP'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    foreach ( $candidates as $candidate ) {
        if ( empty( $candidate ) ) continue;
        $parts = array_map( 'trim', explode( ',', $candidate ) );
        foreach ( $parts as $part ) {
            if ( filter_var( $part, FILTER_VALIDATE_IP ) ) {
                return $part;
            }
        }
    }

    return '';
}

/**
 * Track ticket-generation attempts per IP.
 * Allows 1 ticket every 20 minutes per IP.
 */
function bntm_bae_ticket_rate_limit_check() {
    $ip = bntm_bae_client_ip();
    if ( empty( $ip ) ) {
        return true;
    }

    $key = 'bae_ticket_rate_' . md5( $ip );
    $now = time();
    $attempts = get_transient( $key );
    if ( ! is_array( $attempts ) ) {
        $attempts = [];
    }

    $recent_window = array_filter( $attempts, function( $ts ) use ( $now ) {
        return is_numeric( $ts ) && ( $now - (int) $ts ) < 20 * MINUTE_IN_SECONDS;
    } );

    if ( count( $recent_window ) >= 1 ) {
        return new WP_Error( 'bae_ticket_rate_limited', 'Please wait 20 minutes before creating another ticket.' );
    }

    $attempts[] = $now;
    $attempts = array_values( array_filter( $attempts, function( $ts ) use ( $now ) {
        return is_numeric( $ts ) && ( $now - (int) $ts ) < 20 * MINUTE_IN_SECONDS;
    } ) );
    set_transient( $key, $attempts, 20 * MINUTE_IN_SECONDS );

    return true;
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

    // ADM tickets are admin codes — validate against BAE_ADMIN_SECRET and set admin session
    if ( strpos( $ticket, 'ADM-' ) === 0 ) {
        if ( ! defined('BAE_ADMIN_SECRET') || ! hash_equals( BAE_ADMIN_SECRET, $ticket ) ) {
            wp_send_json_error( [ 'message' => 'Invalid admin code.' ] );
        }
        // Set admin session cookie
        $token   = bae_admin_make_token();
        $expires = time() + DAY_IN_SECONDS;
        setcookie( BAE_ADMIN_COOKIE, $token, $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        wp_send_json_success( [ 'ticket' => $ticket, 'is_admin' => true ] );
    }

    // Ticket must exist in DB — no random codes allowed
    // Special: Allow BAE-2525-2525 for demo
    if ( $ticket !== 'BAE-2525-2525' ) {
        global $wpdb;
        $wpdb->hide_errors();
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s", $ticket
        ) );
        $wpdb->show_errors();

        if ( ! $exists ) {
            wp_send_json_error( [ 'message' => 'Ticket not found. Check your code and try again.' ] );
        }
    }

    $profile = bntm_bae_profile_by_ticket( $ticket );

    // FIX Issue 5: If login code verification is enabled for this ticket,
    // don't grant access yet — tell the client to collect the OTP first.
    // The server will send the OTP email; client shows the code input step.
    if (
        ! empty( $profile['login_code_enabled'] ) &&
        ! empty( $profile['login_code_email'] ) &&
        $ticket !== 'BAE-2525-2525'
    ) {
        // Generate and store OTP
        $otp     = str_pad( wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
        $expires = date( 'Y-m-d H:i:s', time() + 600 );
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'bae_profiles',
            [ 'login_otp' => $otp, 'login_otp_expires' => $expires ],
            [ 'ticket' => $ticket ],
            [ '%s', '%s' ],
            [ '%s' ]
        );

        $email   = $profile['login_code_email'];
        $name    = $profile['business_name'] ?: 'your workspace';
        $subject = 'Your Mothie login code';
        $body    = "Hi,\n\nYour login verification code for {$name} is:\n\n    {$otp}\n\nThis code expires in 10 minutes.\n\n— Mothie";
        $sent = bae_resend_email( $email, $subject, $body );

        if ( ! $sent ) {
            global $bae_resend_last_error;
            wp_send_json_error( [ 'message' => 'Email failed: ' . ( $bae_resend_last_error ?: 'Unknown — check BAE_SMTP_FROM and BAE_SMTP_PASS in .env' ) ] );
        }

        $at     = strpos( $email, '@' );
        $masked = substr( $email, 0, 2 ) . str_repeat( '*', max( 1, $at - 2 ) ) . substr( $email, $at );

        wp_send_json_success( [
            'ticket'        => $ticket,
            'has_profile'   => true,
            'requires_code' => true,
            'masked_email'  => $masked,
        ] );
    }

    wp_send_json_success( [
        'ticket'      => $ticket,
        'has_profile' => ! empty( $profile ) || $ticket === 'BAE-2525-2525',
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

    $rate_check = bntm_bae_ticket_rate_limit_check();
    if ( is_wp_error( $rate_check ) ) {
        wp_send_json_error( [ 'message' => $rate_check->get_error_message() ] );
    }

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
    // Expire identity cookies server-side
    setcookie( 'bae_ticket', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false );
    setcookie( 'bae_session', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
    wp_send_json_success( [ 'message' => 'Logged out.' ] );
}

/**
 * Inject ticket card into settings tab output
 * Hooked via output buffer on the settings tab return value
 */
function bntm_bae_inject_settings_ticket( $html ) {
    $ticket = bntm_bae_read_ticket();
    if ( empty( $ticket ) ) return $html;

    global $wpdb;
    $profile = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bae_profiles WHERE ticket = %s", $ticket ),
        ARRAY_A
    );

    $aj     = esc_js( admin_url( 'admin-ajax.php' ) );
    $tk_esc = esc_html( $ticket );
    $tk_js  = esc_js( $ticket );

    // 2FA state from profile
    $code_enabled = ! empty( $profile['login_code_enabled'] );
    $code_email   = esc_attr( $profile['login_code_email'] ?? '' );
    $profile_email = esc_attr( $profile['email'] ?? '' );
    $has_email    = ! empty( $profile['email'] ) || ! empty( $profile['login_code_email'] );
    $initial_email = $code_email ?: $profile_email;

    $card = '
    <div class="bae-card" style="margin-bottom:20px;">
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

    <div class="bae-card" style="margin-bottom:20px;" id="bae-login-code-card">
        <div class="bae-card-header" style="margin-bottom:16px;">
            <div>
                <div class="bae-card-title">Login Verification</div>
                <div class="bae-card-desc" style="margin-top:4px;">
                    When enabled, entering your ticket code will also require a one-time email code. Adds a layer of security to your workspace.
                </div>
            </div>
            <label class="bae-toggle-wrap" style="flex-shrink:0;cursor:pointer;display:flex;align-items:center;gap:10px;" title="' . ( $has_email ? 'Toggle login verification' : 'Add an email to your profile to enable this' ) . '">
                <div class="bae-toggle ' . ( $code_enabled ? 'active' : '' ) . '" id="bae-lc-toggle" style="width:40px;height:22px;border-radius:999px;background:' . ( $code_enabled ? 'var(--brand-s)' : 'rgba(255,255,255,0.1)' ) . ';position:relative;transition:background .2s;' . ( $has_email ? '' : 'opacity:0.4;pointer-events:none;' ) . '">
                    <div id="bae-lc-knob" style="position:absolute;top:3px;left:' . ( $code_enabled ? '21px' : '3px' ) . ';width:16px;height:16px;border-radius:50%;background:#fff;transition:left .2s;box-shadow:0 1px 4px rgba(0,0,0,0.3);"></div>
                </div>
                <span id="bae-lc-label" style="font-size:13px;font-weight:600;color:' . ( $code_enabled ? 'var(--text)' : 'var(--text-3)' ) . ';">' . ( $code_enabled ? 'Enabled' : 'Disabled' ) . '</span>
            </label>
        </div>

        <div id="bae-lc-email-row" style="' . ( $code_enabled ? '' : 'display:none;' ) . 'margin-top:4px;">
            <div style="font-size:12px;color:var(--text-3);margin-bottom:8px;">Verification codes will be sent to:</div>
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="email" id="bae-lc-email-inp" value="' . $initial_email . '" placeholder="your@email.com"
                    style="flex:1;background:var(--bg-3);border:1.5px solid var(--border);border-radius:10px;padding:10px 14px;font-size:13px;color:var(--text);outline:none;font-family:\'Geist\',sans-serif;transition:border-color .2s;"
                    onfocus="this.style.borderColor=\'var(--brand-s)\'" onblur="this.style.borderColor=\'var(--border)\'">
                <button onclick="baeLcSave()" id="bae-lc-save-btn"
                    style="background:var(--brand-s);color:#fff;border:none;border-radius:10px;padding:10px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:\'Geist\',sans-serif;white-space:nowrap;transition:opacity .2s;">
                    Save
                </button>
            </div>
            <div id="bae-lc-msg" style="font-size:12px;margin-top:8px;display:none;"></div>
        </div>

        ' . ( ! $has_email ? '<div style="font-size:12px;color:var(--text-3);margin-top:4px;display:flex;align-items:center;gap:6px;"><svg xmlns=\'http://www.w3.org/2000/svg\' width=\'11\' height=\'11\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><circle cx=\'12\' cy=\'12\' r=\'10\'/><path d=\'M12 8v4M12 16h.01\'/></svg> Add an email address to your brand profile to enable this feature.</div>' : '' ) . '
    </div>

    <script>
    var _baeTkAjLc = \'' . $aj . '\';
    var _baeLcEnabled = ' . ( $code_enabled ? 'true' : 'false' ) . ';

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
            document.cookie = \'bae_ticket=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax\';
            document.cookie = \'bae_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax\';
            window.location.href = window.location.pathname;
        });
    }

    (function() {
        var toggle   = document.getElementById(\'bae-lc-toggle\');
        var knob     = document.getElementById(\'bae-lc-knob\');
        var label    = document.getElementById(\'bae-lc-label\');
        var emailRow = document.getElementById(\'bae-lc-email-row\');
        if (!toggle) return;

        toggle.addEventListener(\'click\', function() {
            _baeLcEnabled = !_baeLcEnabled;
            toggle.style.background = _baeLcEnabled ? \'var(--brand-s)\' : \'rgba(255,255,255,0.1)\';
            knob.style.left         = _baeLcEnabled ? \'21px\' : \'3px\';
            label.textContent       = _baeLcEnabled ? \'Enabled\' : \'Disabled\';
            label.style.color       = _baeLcEnabled ? \'var(--text)\' : \'var(--text-3)\';
            emailRow.style.display  = _baeLcEnabled ? \'\' : \'none\';
            if (!_baeLcEnabled) baeLcToggleServer(false, \'\');
        });
    })();

    function baeLcSave() {
        var email = document.getElementById(\'bae-lc-email-inp\').value.trim();
        if (!email || !/^[^@]+@[^@]+\.[^@]+$/.test(email)) {
            baeLcShowMsg(\'Please enter a valid email address.\', \'#fb7185\');
            return;
        }
        baeLcToggleServer(true, email);
    }

    function baeLcToggleServer(enable, email) {
        var btn = document.getElementById(\'bae-lc-save-btn\');
        if (btn) { btn.disabled = true; btn.textContent = \'Saving...\'; }
        var msg = document.getElementById(\'bae-lc-msg\');
        var fd = new FormData();
        fd.append(\'action\', \'bae_toggle_login_code\');
        fd.append(\'enable\', enable ? \'1\' : \'0\');
        fd.append(\'email\', email);
        fetch(_baeTkAjLc, {method:\'POST\', body:fd})
            .then(function(r){ return r.json(); })
            .then(function(j) {
                if (j.success) {
                    baeLcShowMsg(j.data.message, \'#34d399\');
                } else {
                    baeLcShowMsg(j.data.message || \'Failed to save.\', \'#fb7185\');
                    // Revert toggle on error
                    _baeLcEnabled = !enable;
                    var toggle = document.getElementById(\'bae-lc-toggle\');
                    var knob   = document.getElementById(\'bae-lc-knob\');
                    var label  = document.getElementById(\'bae-lc-label\');
                    var emailRow = document.getElementById(\'bae-lc-email-row\');
                    if (toggle) { toggle.style.background = _baeLcEnabled ? \'var(--brand-s)\' : \'rgba(255,255,255,0.1)\'; }
                    if (knob)   { knob.style.left = _baeLcEnabled ? \'21px\' : \'3px\'; }
                    if (label)  { label.textContent = _baeLcEnabled ? \'Enabled\' : \'Disabled\'; label.style.color = _baeLcEnabled ? \'var(--text)\' : \'var(--text-3)\'; }
                    if (emailRow) { emailRow.style.display = _baeLcEnabled ? \'\' : \'none\'; }
                }
            })
            .catch(function() { baeLcShowMsg(\'Network error. Try again.\', \'#fb7185\'); })
            .finally(function() { if (btn) { btn.disabled = false; btn.textContent = \'Save\'; } });
    }

    function baeLcShowMsg(text, color) {
        var msg = document.getElementById(\'bae-lc-msg\');
        if (!msg) return;
        msg.textContent = text;
        msg.style.color = color;
        msg.style.display = \'block\';
        setTimeout(function() { msg.style.display = \'none\'; }, 4000);
    }
    </script>
    ';

    return $card . $html;
}

// =============================================================================
// FIX Issue 5: EMAIL LOGIN CODE (2FA) — Toggle, Send OTP, Verify OTP
// User can enable "require email code on login" from the Settings tab.
// Requires their profile to have an email address set.
// =============================================================================

function bntm_bae_ajax_toggle_login_code() {
    $ticket = bntm_bae_read_ticket();
    if ( ! bntm_bae_ticket_valid( $ticket ) ) {
        wp_send_json_error( [ 'message' => 'Invalid ticket.' ] );
    }

    global $wpdb;
    $table   = $wpdb->prefix . 'bae_profiles';
    $profile = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE ticket = %s", $ticket ), ARRAY_A );

    if ( ! $profile ) {
        wp_send_json_error( [ 'message' => 'Profile not found.' ] );
    }

    $enable = ! empty( $_POST['enable'] ) && $_POST['enable'] === '1';
    $email  = sanitize_email( $_POST['email'] ?? $profile['login_code_email'] ?? '' );

    if ( $enable && empty( $email ) ) {
        wp_send_json_error( [ 'message' => 'An email address is required to enable login verification.' ] );
    }

    $wpdb->update(
        $table,
        [
            'login_code_enabled' => $enable ? 1 : 0,
            'login_code_email'   => $enable ? $email : '',
        ],
        [ 'ticket' => $ticket ],
        [ '%d', '%s' ],
        [ '%s' ]
    );

    wp_send_json_success( [
        'enabled' => $enable,
        'message' => $enable ? 'Login verification enabled.' : 'Login verification disabled.',
    ] );
}

function bntm_bae_ajax_send_login_otp() {
    $ticket = strtoupper( sanitize_text_field( $_POST['ticket'] ?? '' ) );
    if ( ! bntm_bae_ticket_valid( $ticket ) ) {
        wp_send_json_error( [ 'message' => 'Invalid ticket.' ] );
    }

    global $wpdb;
    $table   = $wpdb->prefix . 'bae_profiles';
    $profile = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE ticket = %s", $ticket ), ARRAY_A );

    if ( ! $profile || empty( $profile['login_code_enabled'] ) || empty( $profile['login_code_email'] ) ) {
        wp_send_json_error( [ 'message' => 'Login verification is not configured for this ticket.' ] );
    }

    // Generate 6-digit OTP
    $otp     = str_pad( wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
    $expires = date( 'Y-m-d H:i:s', time() + 600 ); // 10 minutes

    $wpdb->update(
        $table,
        [ 'login_otp' => $otp, 'login_otp_expires' => $expires ],
        [ 'ticket' => $ticket ],
        [ '%s', '%s' ],
        [ '%s' ]
    );

    $email   = $profile['login_code_email'];
    $name    = $profile['business_name'] ?: 'your workspace';
    $subject = 'Your Mothie login code';
    $body    = "Hi,\n\nYour login verification code for {$name} is:\n\n    {$otp}\n\nThis code expires in 10 minutes. If you didn't request this, you can ignore this email.\n\n— Mothie";

    $sent = bae_resend_email( $email, $subject, $body );

    if ( ! $sent ) {
        global $bae_resend_last_error;
        wp_send_json_error( [ 'message' => 'Resend failed: ' . ( $bae_resend_last_error ?: 'Unknown error — check BAE_RESEND_API_KEY is set.' ) ] );
    }

    // Mask email for display: he***@example.com
    $at       = strpos( $email, '@' );
    $masked   = substr( $email, 0, 2 ) . str_repeat( '*', max( 1, $at - 2 ) ) . substr( $email, $at );

    wp_send_json_success( [ 'message' => "Code sent to {$masked}.", 'masked_email' => $masked ] );
}

function bntm_bae_ajax_verify_login_otp() {
    $ticket = strtoupper( sanitize_text_field( $_POST['ticket'] ?? '' ) );
    $otp    = sanitize_text_field( $_POST['otp'] ?? '' );

    if ( ! bntm_bae_ticket_valid( $ticket ) || empty( $otp ) ) {
        wp_send_json_error( [ 'message' => 'Invalid request.' ] );
    }

    global $wpdb;
    $table   = $wpdb->prefix . 'bae_profiles';
    $profile = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE ticket = %s", $ticket ), ARRAY_A );

    if ( ! $profile ) {
        wp_send_json_error( [ 'message' => 'Ticket not found.' ] );
    }

    if ( empty( $profile['login_otp'] ) || $profile['login_otp'] !== $otp ) {
        wp_send_json_error( [ 'message' => 'Incorrect code. Please try again.' ] );
    }

    if ( strtotime( $profile['login_otp_expires'] ) < time() ) {
        wp_send_json_error( [ 'message' => 'This code has expired. Please request a new one.' ] );
    }

    // Clear OTP after successful use
    $wpdb->update(
        $table,
        [ 'login_otp' => '', 'login_otp_expires' => null ],
        [ 'ticket' => $ticket ],
        [ '%s', '%s' ],
        [ '%s' ]
    );

    wp_send_json_success( [ 'ticket' => $ticket, 'verified' => true ] );
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

    // FIX Issue 5: Add login code verification columns for optional email 2FA
    $has_code = $wpdb->get_results( "SHOW COLUMNS FROM `{$profiles}` LIKE 'login_code_enabled'" );
    if ( empty( $has_code ) ) {
        $wpdb->query( "ALTER TABLE `{$profiles}` ADD COLUMN login_code_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER kit_visibility" );
        $wpdb->query( "ALTER TABLE `{$profiles}` ADD COLUMN login_code_email VARCHAR(255) NOT NULL DEFAULT '' AFTER login_code_enabled" );
        $wpdb->query( "ALTER TABLE `{$profiles}` ADD COLUMN login_otp VARCHAR(10) NOT NULL DEFAULT '' AFTER login_code_email" );
        $wpdb->query( "ALTER TABLE `{$profiles}` ADD COLUMN login_otp_expires DATETIME NULL DEFAULT NULL AFTER login_otp" );
    }

    // FIX: Add payment pending columns for ticket-based checkout flow
    $has_pending = $wpdb->get_results( "SHOW COLUMNS FROM `{$profiles}` LIKE 'bae_pm_pending_ref'" );
    if ( empty( $has_pending ) ) {
        $wpdb->query( "ALTER TABLE `{$profiles}` ADD COLUMN bae_pm_pending_ref VARCHAR(120) NOT NULL DEFAULT '' AFTER login_otp_expires" );
        $wpdb->query( "ALTER TABLE `{$profiles}` ADD COLUMN bae_pm_pending_plan VARCHAR(20) NOT NULL DEFAULT '' AFTER bae_pm_pending_ref" );
    }

    $wpdb->show_errors();
}

// =============================================================================
// TICKET SCREEN UI
// Full-screen dark entry screen shown when no valid ticket in cookie/GET
// =============================================================================

function bntm_bae_ticket_screen() {
    $logo_url = esc_js(bae_module_logo_url());
    ob_start(); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
div[style*="position:fixed"][style*="bottom:10px"][style*="right:10px"][style*="z-index:99999"] {
    display: none !important;
}
		
    .baetk { font-family:'Geist',-apple-system,sans-serif; background:#09090e; color:#ede9ff; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 24px; position:relative; overflow:hidden; border-radius:0; isolation:isolate; }
    .baetk::before { content:''; position:absolute; width:480px; height:480px; border-radius:50%; background:radial-gradient(circle,rgba(139,92,246,.13) 0%,transparent 70%); top:-160px; right:-80px; pointer-events:none; z-index:0; }
    .baetk::after  { content:''; position:absolute; width:320px; height:320px; border-radius:50%; background:radial-gradient(circle,rgba(236,72,153,.08) 0%,transparent 70%); bottom:-80px; left:-80px; pointer-events:none; z-index:0; }
    .baetk-in { width:100%; max-width:400px; text-align:center; position:relative; z-index:2; display:flex; flex-direction:column; align-items:center; }
    .baetk-logo { width:56px; display:flex; align-items:center; justify-content:center; margin:0 auto 28px; position:relative; z-index:2; }
    .baetk-logo img { width:100%; height:auto; display:block; }
    .baetk-title { font-family:'Cambo',serif; font-size:32px; font-style:bold; color:#ede9ff; margin-bottom:8px; line-height:1.2; width:100%; position:relative; z-index:2; }
    .baetk-sub { font-size:14px; color:#4d4a65; margin-bottom:36px; line-height:1.7; width:100%; position:relative; z-index:2; }
    .baetk-inp { width:100%; background:rgba(28,20,12,0.04); border:1.5px solid rgba(139,92,246,.2); border-radius:14px; padding:16px 20px; font-size:22px; font-family:'Geist',monospace; font-weight:700; letter-spacing:.15em; color:#1d1a16; outline:none; text-align:center; text-transform:uppercase; transition:border-color .2s,box-shadow .2s; margin-bottom:14px; display:block; box-sizing:border-box; position:relative; z-index:2; pointer-events:auto; }
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

    /* Landing overrides */
    .baetk {
        background:
            radial-gradient(circle at top right, rgba(124,58,237,.12), transparent 34%),
            radial-gradient(circle at 12% 18%, rgba(236,72,153,.08), transparent 24%),
            linear-gradient(180deg, #fcfcf9 0%, #f7f4ed 100%);
        color:#1d1a16;
        display:block;
        padding:0;
        min-height:100vh;
    }
    .baetk[data-theme="dark"] {
        background:
            radial-gradient(circle at top right, rgba(124,58,237,.16), transparent 32%),
            radial-gradient(circle at 18% 20%, rgba(236,72,153,.08), transparent 24%),
            linear-gradient(180deg, #0a0a0f 0%, #10101a 100%);
        color:#f4f1ff;
    }
    .baetk-shell { width:min(1200px, calc(100% - 32px)); margin:0 auto; padding:14px 0 30px; position:relative; z-index:2; }
    .baetk-nav { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:2px 0 12px; }
    .baetk-brand { display:flex; align-items:center; gap:10px; min-width:0; }
    .baetk-kicker { display:block; font-size:9px; letter-spacing:.3em; text-transform:uppercase; color:#8c857a; margin-bottom:2px; }
    .baetk[data-theme="dark"] .baetk-kicker { color:#8c88a8; }
    .baetk-brandname { display:block; font-family:'Instrument Serif',serif; font-size:18px; line-height:1; color:inherit; }
    .baetk-brandname .bae-brand-wordmark {
        display:inline-flex;
        align-items:flex-end;
        gap:.03em;
        color:inherit;
        line-height:1;
    }
    .baetk-brandname .bae-brand-wordmark-leading,
    .baetk-brandname .bae-brand-wordmark-trailing,
    .baetk-brandname .bae-brand-wordmark-letter {
        display:inline-block;
    }
    .baetk-brandname .bae-brand-wordmark-center {
        display:inline-flex;
        flex-direction:column;
        align-items:center;
        justify-content:flex-end;
        margin:0 .08em;
        line-height:.88;
    }
    .baetk-brandname .bae-brand-wordmark-logo {
        width:1.9em;
        margin-bottom:-0.04em;
    }
    .baetk-brandname .bae-brand-wordmark-letter { color:#7c3aed; }
    .baetk-brandname .bae-brand-wordmark-merge .bae-brand-wordmark-center {
        position:relative;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        flex-direction:row;
        margin:0 .02em 0 .04em;
        line-height:1;
    }
    .baetk-brandname .bae-brand-wordmark-merge .bae-brand-wordmark-letter-o { color:transparent; }
    .baetk-brandname .bae-brand-wordmark-logo-merge {
        width:1.12em;
        margin:0;
        position:absolute;
        left:50%;
        top:52%;
        transform:translate(-50%, -50%);
    }
    .baetk[data-theme="dark"] .baetk-brandname .bae-brand-wordmark-letter { color:#a78bfa; }
    .baetk[data-theme="light"] .baetk-brandname .bae-brand-logo-img { filter:invert(1); }
    .baetk-theme-btn {
        display:flex; align-items:center; gap:8px;
        background:rgba(255,255,255,.74); border:1px solid rgba(28,20,12,.08);
        border-radius:10px; padding:7px 14px;
        font-size:12px; font-weight:600; color:#6d665c;
        cursor:pointer; font-family:'Geist',sans-serif;
        transition:all .2s ease, box-shadow .25s ease, transform .2s ease;
        backdrop-filter:blur(20px);
    }
    .baetk[data-theme="dark"] .baetk-theme-btn { color:#a7a2bb; background:rgba(28,28,38,.86); border-color:rgba(255,255,255,.08); box-shadow:0 20px 50px rgba(0,0,0,.35); }
    .baetk-theme-btn:hover { color:#1d1a16; border-color:#d6c9ff; transform:translateY(-1px); }
    .baetk[data-theme="dark"] .baetk-theme-btn:hover { color:#f4f1ff; border-color:rgba(167,139,250,.3); }
    .baetk-theme-icon { display:inline-flex; align-items:center; justify-content:center; width:16px; height:16px; flex-shrink:0; }
    .baetk-theme-label { font-size:12px; font-weight:600; letter-spacing:.01em; }
    .baetk-toggle-track {
        width:34px; height:18px;
        background:rgba(255,255,255,.7);
        border:1px solid rgba(28,20,12,.08);
        border-radius:999px; position:relative;
        transition:background .3s, border-color .3s;
        flex-shrink:0;
    }
    .baetk[data-theme="dark"] .baetk-toggle-track { background:rgba(34,34,46,.92); border-color:rgba(255,255,255,.08); }
    .baetk-toggle-track.on { background:var(--accent); border-color:var(--accent); }
    .baetk-toggle-thumb {
        position:absolute; top:2px; left:2px;
        width:12px; height:12px; background:white;
        border-radius:50%; transition:transform .35s cubic-bezier(.2,.8,.2,1);
        box-shadow:0 1px 3px rgba(0,0,0,.3);
    }
    .baetk-toggle-track.on .baetk-toggle-thumb { transform:translateX(16px); }
    .baetk-logo { width:42px; margin:0; transition:transform .28s ease, filter .28s ease; }
    .baetk-logo:hover { transform:scale(1.05) rotate(-2deg); }
    .baetk-in { width:100%; max-width:none; text-align:left; position:relative; z-index:2; display:block; }
    .baetk-err { color:#dc2626; background:rgba(220,38,38,.08); border-color:rgba(220,38,38,.18); }
    .baetk-hint { color:#8c857a; }
    .baetk[data-theme="dark"] .baetk-hint { color:#8c88a8; }
    .baetk-new-btn { color:#1d1a16; border-color:rgba(28,20,12,.08); background:rgba(255,255,255,.52); }
    .baetk[data-theme="dark"] .baetk-new-btn { color:#f4f1ff; border-color:rgba(255,255,255,.08); background:rgba(28,28,38,.72); }
    .baetk-new-btn:hover { background:rgba(124,58,237,.08); border-color:rgba(124,58,237,.3); color:inherit; transform:translateY(-1px) scale(1.01); box-shadow:0 16px 30px rgba(124,58,237,.12); }
    .baetk[data-theme="light"] .baetk-logo img { filter:invert(1); }
    .baetk-spin { border:2px solid rgba(124,58,237,.24); border-top-color:var(--accent); }
    .baetk-inp::placeholder { color:#a49c92; }
    .baetk[data-theme="dark"] .baetk-inp::placeholder { color:#6a667e; }
    .baetk[data-theme="dark"] .baetk-inp { color:#ede9ff; background:rgba(255,255,255,.05); }
    .baetk-hero { display:grid; grid-template-columns:minmax(0,1.15fr) minmax(340px,.85fr); gap:28px; align-items:center; min-height:calc(100vh - 118px); padding:32px 0 48px; }
    .baetk-copy { position:relative; z-index:1; }
    .baetk-eyebrow { display:inline-flex; align-items:center; gap:10px; padding:10px 14px; border-radius:999px; border:1px solid rgba(28,20,12,.08); background:rgba(255,255,255,.86); color:#6d665c; font-size:12px; letter-spacing:.14em; text-transform:uppercase; margin-bottom:24px; box-shadow:0 18px 50px rgba(28,20,12,.08); backdrop-filter:blur(20px); }
    .baetk[data-theme="dark"] .baetk-eyebrow { border-color:rgba(255,255,255,.08); background:rgba(28,28,38,.86); color:#a7a2bb; }
    .baetk-eyebrow i { width:8px; height:8px; border-radius:50%; background:linear-gradient(135deg,#ec4899,#8b5cf6); box-shadow:0 0 0 6px rgba(139,92,246,.12); display:inline-block; }
    .baetk-title { font-family:'Cambo',serif; font-size:clamp(42px, 5.1vw, 72px); line-height:.92; letter-spacing:-.03em; color:inherit; margin:0 0 16px; max-width:30ch; }
    .baetk-sub { font-size:clamp(14px, 1.1vw, 18px); line-height:1.85; color:#6d665c; margin-bottom:22px; max-width:58ch; }
    .baetk[data-theme="dark"] .baetk-sub { color:#a7a2bb; }
    .baetk-chips { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:30px; }
    .baetk-chip { padding:9px 12px; border-radius:999px; border:1px solid rgba(124,58,237,.16); background:rgba(124,58,237,.08); color:#5b21b6; font-size:11px; font-weight:600; letter-spacing:.03em; transition:transform .25s ease, box-shadow .25s ease, background .25s ease; }
    .baetk[data-theme="dark"] .baetk-chip { background:rgba(167,139,250,.12); border-color:rgba(167,139,250,.2); color:#ddd6fe; }
    .baetk-proof { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; max-width:640px; }
    .baetk-stat { padding:18px 16px; border-radius:20px; background:rgba(255,255,255,.86); border:1px solid rgba(28,20,12,.08); box-shadow:0 18px 50px rgba(28,20,12,.08); backdrop-filter:blur(20px); transition:transform .25s ease, box-shadow .25s ease; }
    .baetk[data-theme="dark"] .baetk-stat { background:rgba(28,28,38,.86); border-color:rgba(255,255,255,.08); box-shadow:0 20px 50px rgba(0,0,0,.35); }
    .baetk-stat strong { display:block; font-size:15px; color:inherit; margin-bottom:4px; }
    .baetk-stat span { display:block; font-size:11px; line-height:1.55; color:#8c857a; }
    .baetk[data-theme="dark"] .baetk-stat span { color:#6a667e; }
    .baetk-card { border-radius:30px; border:1px solid rgba(28,20,12,.08); background:linear-gradient(180deg, rgba(255,255,255,.62), rgba(248,246,240,.78)); box-shadow:0 22px 60px rgba(28,20,12,.1); backdrop-filter:blur(26px); padding:28px; position:relative; overflow:hidden; margin-top:10px; transition:transform .28s ease, box-shadow .28s ease; }
    .baetk[data-theme="dark"] .baetk-card { border-color:rgba(255,255,255,.08); background:linear-gradient(180deg, rgba(28,28,38,.88), rgba(34,34,46,.92)); box-shadow:0 20px 50px rgba(0,0,0,.35); }
    .baetk-card::before { content:''; position:absolute; inset:auto -30px -30px auto; width:170px; height:170px; border-radius:50%; background:radial-gradient(circle, rgba(124,58,237,.14) 0%, transparent 70%); pointer-events:none; animation:baetk-drift 14s ease-in-out infinite; }
    .baetk-card:hover { transform:translateY(-4px) scale(1.01); box-shadow:0 28px 72px rgba(28,20,12,.14); }
    .baetk-card-top { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px; }
    .baetk-card-top h2 { margin:0; font-size:12px; letter-spacing:.22em; text-transform:uppercase; color:#8c857a; }
    .baetk[data-theme="dark"] .baetk-card-top h2 { color:#6a667e; }
    .baetk-pill { padding:8px 12px; border-radius:999px; background:rgba(124,58,237,.08); color:#5b21b6; font-size:11px; font-weight:600; letter-spacing:.04em; }
    .baetk[data-theme="dark"] .baetk-pill { background:rgba(167,139,250,.12); color:#ddd6fe; }
    .baetk-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; padding:8px 0 20px; }
    .baetk-feature { padding:20px 18px; border-radius:24px; background:rgba(255,255,255,.86); border:1px solid rgba(28,20,12,.08); box-shadow:0 18px 50px rgba(28,20,12,.08); backdrop-filter:blur(16px); transition:transform .25s ease, box-shadow .25s ease; }
    .baetk[data-theme="dark"] .baetk-feature { background:rgba(28,28,38,.86); border-color:rgba(255,255,255,.08); box-shadow:0 20px 50px rgba(0,0,0,.35); }
    .baetk-feature h3 { margin:0 0 10px; font-size:14px; color:inherit; }
    .baetk-feature p { margin:0; font-size:13px; line-height:1.75; color:#6d665c; }
    .baetk[data-theme="dark"] .baetk-feature p { color:#a7a2bb; }
    .baetk-feature:hover { transform:translateY(-3px); box-shadow:0 24px 60px rgba(28,20,12,.12); }
    .baetk-surface,
    .baetk-storycard,
    .baetk-railcard,
    .baetk-metric,
    .baetk-slide {
        transition:transform .28s ease, box-shadow .28s ease, filter .28s ease;
    }
    .baetk-surface:hover,
    .baetk-storycard:hover,
    .baetk-railcard:hover,
    .baetk-metric:hover,
    .baetk-slide:hover {
        transform:translateY(-4px) scale(1.01);
        box-shadow:0 28px 78px rgba(28,20,12,.16);
        filter:saturate(1.04);
    }
    .baetk-story {
        display:grid;
        grid-template-columns:1.05fr .95fr;
        gap:18px;
        align-items:stretch;
        padding:18px 0 36px;
    }
    .baetk-quote {
        padding:28px;
        border-radius:28px;
        background:rgba(255,255,255,.86);
        border:1px solid rgba(28,20,12,.08);
        box-shadow:0 18px 50px rgba(28,20,12,.08);
        backdrop-filter:blur(20px);
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        min-height:360px;
    }
    .baetk[data-theme="dark"] .baetk-quote { background:rgba(28,28,38,.86); border-color:rgba(255,255,255,.08); box-shadow:0 20px 50px rgba(0,0,0,.35); }
    .baetk-quote h2 { margin:0 0 16px; font-family:'Instrument Serif',serif; font-size:clamp(28px, 3.4vw, 44px); line-height:.95; }
    .baetk-quote p { margin:0; color:#6d665c; line-height:1.85; font-size:14px; max-width:55ch; }
    .baetk[data-theme="dark"] .baetk-quote p { color:#a7a2bb; }
    .baetk-quote-foot { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:26px; flex-wrap:wrap; }
    .baetk-mini {
        display:flex;
        gap:12px;
        align-items:center;
        padding:14px;
        border-radius:20px;
        background:rgba(124,58,237,.08);
        border:1px solid rgba(124,58,237,.16);
        color:#5b21b6;
    }
    .baetk[data-theme="dark"] .baetk-mini { background:rgba(167,139,250,.12); border-color:rgba(167,139,250,.2); color:#ddd6fe; }
    .baetk-mini strong { display:block; font-size:13px; color:inherit; margin-bottom:2px; }
    .baetk-mini span { display:block; font-size:11px; color:inherit; opacity:.8; }
    .baetk-gallery {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:14px;
    }
    .baetk-shot {
        position:relative;
        overflow:hidden;
        border-radius:24px;
        min-height:172px;
        border:1px solid rgba(28,20,12,.08);
        box-shadow:0 18px 50px rgba(28,20,12,.08);
        background:#f3f0ea;
    }
    .baetk[data-theme="dark"] .baetk-shot { border-color:rgba(255,255,255,.08); box-shadow:0 20px 50px rgba(0,0,0,.35); background:#111118; }
    .baetk-shot img { width:100%; height:100%; object-fit:cover; display:block; filter:saturate(.92) contrast(1.02); transform:scale(1.02); }
    .baetk-shot .cap {
        position:absolute;
        left:14px;
        bottom:14px;
        padding:8px 10px;
        border-radius:999px;
        background:rgba(255,255,255,.72);
        color:#1d1a16;
        font-size:11px;
        font-weight:700;
        letter-spacing:.08em;
        text-transform:uppercase;
        backdrop-filter:blur(10px);
    }
    .baetk[data-theme="dark"] .baetk-shot .cap { background:rgba(17,24,39,.68); color:#f4f1ff; }
    .baetk-footer {
        min-height:40vh;
        display:flex;
        align-items:flex-end;
        padding:24px 0 18px;
        color:#8c857a;
        font-size:12px;
    }
    .baetk-footer-inner {
        width:min(1200px, calc(100% - 40px));
        margin:0 auto;
        display:grid;
        grid-template-columns:minmax(0, 1fr) auto;
        gap:18px;
        align-items:end;
        padding-top:18px;
        border-top:1px solid rgba(28,20,12,.08);
    }
    .baetk[data-theme="dark"] .baetk-footer-inner { border-top-color:rgba(255,255,255,.08); color:#6a667e; }
    .baetk-footer-brand {
        display:flex;
        align-items:center;
        gap:18px;
        min-width:0;
    }
    .baetk-footer-logo {
        width:72px;
        flex-shrink:0;
    }
    .baetk-footer-logo .bae-brand-logo-img {
        width:100%;
        height:auto;
        display:block;
    }
    .baetk[data-theme="light"] .baetk-footer-logo .bae-brand-logo-img { filter:invert(1); }
    .baetk-footer-copy strong {
        display:block;
        font-size:13px;
        color:inherit;
        margin-bottom:4px;
    }
    .baetk-footer-copy span {
        display:block;
        max-width:44ch;
    }
    .baetk-footer-links { display:flex; gap:14px; flex-wrap:wrap; justify-content:flex-end; }
    .baetk-footer-links a { color:inherit; text-decoration:none; }
    .baetk-footer-links a:hover { color:inherit; opacity:.78; }
    @media (max-width: 720px) {
        .baetk-footer-inner {
            grid-template-columns:1fr;
            align-items:flex-start;
        }
        .baetk-footer-links {
            justify-content:flex-start;
        }
        .baetk-footer-logo {
            width:60px;
        }
    }
        .baetk-shell { width:min(100% - 24px, 1200px); }
        .baetk-nav { flex-direction:column; align-items:flex-start; }
        .baetk-card { padding:22px; border-radius:24px; }
        .baetk-title { font-size:clamp(38px, 14vw, 56px); }
        .baetk-inp { font-size:18px; letter-spacing:.1em; }
        .baetk-theme { width:100%; justify-content:space-between; }
    }
    </style>

    <div class="baetk" id="baetk-page" data-theme="light">
        <div class="baetk-shell">
            <div class="baetk-nav">
                <div class="baetk-brand">
                    <div class="baetk-logo">
                        <img src="<?php echo esc_url(bae_module_logo_url()); ?>" alt="Mothie logo">
                    </div>
                    <div>
                        <span class="baetk-brandname"><?php echo bae_render_brand_wordmark_merge(); ?></span>
                    </div>
                </div>
                <button type="button" class="baetk-theme-btn" id="baetk-theme-btn" onclick="baeTkToggleTheme()">
                    <span class="baetk-theme-icon" id="baetk-theme-icon">
                        <svg id="baetk-icon-sun" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                        <svg id="baetk-icon-moon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    </span>
                    <span class="baetk-theme-label" id="baetk-theme-label">Light</span>
                    <div class="baetk-toggle-track on" id="baetk-toggle-track">
                        <div class="baetk-toggle-thumb"></div>
                    </div>
                </button>
            </div>

            <div class="baetk-hero">
                <div class="baetk-copy" id="baetk-copy">
                    <div class="baetk-eyebrow"><i></i> Mothie</div>
					<div class="baetk-title">YOUR BRAND YOUR ASSET.</div>
					<div class="baetk-sub">Mothie is your Brand Asset Engine for modular brand systems — logo, colors, typography, business card, letterhead, email signature, and social kit — all from one brand profile. No design skills required.</div>
					<div class="baetk-chips">
						<div class="baetk-chip">Logo system</div>
						<div class="baetk-chip">Color palette</div>
						<div class="baetk-chip">Business card</div>
						<div class="baetk-chip">Social kit</div>
						<div class="baetk-chip">Brand guidelines</div>
					</div>
                </div>

                <div class="baetk-card" id="baetk-card">
                    <div class="baetk-card-top">
                        <h2>Enter Access</h2>
                        <div class="baetk-pill">Secure ticket gate</div>
                    </div>
                    <div class="baetk-in" id="baetk-in">
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
                        <div class="baetk-hint">Already have a ticket? Enter it above.</div>
                        <button class="baetk-new-btn" id="baetk-new" onclick="baeTkNew()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                            New here? Start for free
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- /.baetk-shell -->
    </div><!-- /.baetk-page -->

    <script>
    /* BAE Ticket Screen — all functions global, no IIFE */
    var _baeTkAj = '<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>';
    var _baeTkLogo = '<?php echo $logo_url; ?>';

    function _baeTkEl(id) { return document.getElementById(id); }

    var baeTkIsDark = (localStorage.getItem('bae_theme') !== 'light');

    function baeTkApplyTheme(dark, animate) {
        var page = _baeTkEl('baetk-page');
        var track = _baeTkEl('baetk-toggle-track');
        var label = _baeTkEl('baetk-theme-label');
        var icon  = _baeTkEl('baetk-theme-icon');
        var sun   = _baeTkEl('baetk-icon-sun');
        var moon  = _baeTkEl('baetk-icon-moon');
        if (!page) return;
        page.setAttribute('data-theme', dark ? 'dark' : 'light');
        if (track) track.className = dark ? 'baetk-toggle-track on' : 'baetk-toggle-track';
        if (label) label.textContent = dark ? 'Dark' : 'Light';
        if (sun) sun.style.display = dark ? 'none' : '';
        if (moon) moon.style.display = dark ? '' : 'none';
        try { localStorage.setItem('bae_theme', page.getAttribute('data-theme')); } catch (e) {}
        if (window.gsap) {
            gsap.to('.baetk-toggle-thumb', { x: dark ? 16 : 0, duration: animate ? 0.4 : 0, ease: 'back.out(1.8)' });
            if (icon && animate) gsap.fromTo(icon, { scale: 0.92 }, { scale: 1, duration: 0.25, ease: 'power2.out' });
        } else if (track) {
            var thumb = track.querySelector('.baetk-toggle-thumb');
            if (thumb) thumb.style.transform = dark ? 'translateX(16px)' : 'translateX(0)';
        }
    }

    function baeTkToggleTheme() {
        baeTkIsDark = !baeTkIsDark;
        baeTkApplyTheme(baeTkIsDark, true);
    }

    // Auto-format input as user types → BAE-XXXX-XXXX
    document.addEventListener('DOMContentLoaded', function() {
        try {
            var savedTheme = localStorage.getItem('bae_theme');
            if (savedTheme === 'dark' || savedTheme === 'light') baeTkIsDark = (savedTheme === 'dark');
        } catch (e) {}
        baeTkApplyTheme(baeTkIsDark, false);

        var f = _baeTkEl('baetk-f');
        if (!f) return;

        // Auto-format input as user types → BAE-XXXX-XXXX or ADM-XXXX-XXXX
        var f = _baeTkEl('baetk-f');
        if (!f) return;

        f.addEventListener('input', function() {
            var raw = this.value.replace(/[^A-Z0-9]/gi, '').toUpperCase().substring(0, 12);
            var out = raw;
            var prefix = '';
            if (raw.length >= 3) {
                var p3 = raw.substring(0, 3);
                if (p3 === 'BAE' || p3 === 'ADM') {
                    prefix = p3;
                    var rest = raw.substring(3);
                    out = rest.length <= 4 ? prefix + '-' + rest : prefix + '-' + rest.substring(0, 4) + '-' + rest.substring(4, 8);
                }
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
            gsap.fromTo('#baetk-copy', {opacity:0, y:24}, {opacity:1, y:0, duration:.7, ease:'power3.out', delay:.05});
            gsap.fromTo('#baetk-card', {opacity:0, y:24, scale:.98}, {opacity:1, y:0, scale:1, duration:.65, ease:'power3.out', delay:.12});
            gsap.to('.baetk-logo', {boxShadow:'0 18px 40px rgba(124,58,237,.24), 0 0 44px rgba(139,92,246,.22)', duration:1.8, repeat:-1, yoyo:true, ease:'sine.inOut', delay:1});
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

        if (!/^(BAE|ADM)-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(t)) {
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
                if (j.data.is_admin) {
                    if (lbl) lbl.textContent = 'Opening Admin...';
                    var adminUrl = window.location.pathname + '?bae=admin';
                    if (window.gsap) {
                        gsap.to('#baetk-in', {opacity:0, y:-20, duration:.3, ease:'power2.in', onComplete:function(){ window.location.href = adminUrl; }});
                    } else {
                        window.location.href = adminUrl;
                    }
                    return;
                }

                // FIX Issue 5: If login code verification is required, show OTP step
                if (j.data.requires_code) {
                    btn.disabled = false;
                    if (sp)  sp.style.display  = 'none';
                    if (arr) arr.style.display = '';
                    if (lbl) lbl.textContent   = 'Enter Workspace';
                    baeTkShowOtpStep(j.data.ticket, j.data.masked_email);
                    return;
                }

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

    // FIX Issue 5: OTP verification step — shown after ticket check returns requires_code:true
    function baeTkShowOtpStep(ticket, maskedEmail) {
        var wrap = _baeTkEl('baetk-in');
        if (!wrap) return;

        if (window.gsap) gsap.to(wrap, {opacity:0, y:-14, duration:.22, ease:'power2.in', onComplete: renderOtp});
        else renderOtp();

        function renderOtp() {
            wrap.innerHTML =
                '<div class="baetk-logo">' +
                    '<img src="' + _baeTkLogo + '" alt="Mothie logo">' +
                '</div>' +
                '<div class="baetk-title">Check your email</div>' +
                '<div class="baetk-sub">A 6-digit code was sent to<br><strong style="color:#8b88a4;">' + (maskedEmail || 'your registered email') + '</strong></div>' +
                '<input type="text" id="baetk-otp" class="baetk-inp" placeholder="000000" maxlength="6" autocomplete="one-time-code" inputmode="numeric" style="letter-spacing:.35em;font-size:26px;">' +
                '<button class="baetk-btn" id="baetk-otp-btn" onclick="baeTkVerifyOtp(\'' + ticket + '\')">' +
                    '<div class="baetk-spin" id="baetk-otp-sp"></div>' +
                    '<span id="baetk-otp-lbl">Verify Code</span>' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" id="baetk-otp-arr"><path d="M5 12h14M12 5l7 7-7 7"/></svg>' +
                '</button>' +
                '<div class="baetk-err" id="baetk-otp-err"></div>' +
                '<div class="baetk-hint" style="margin-top:14px;">' +
                    'Didn\'t get a code? <button onclick="baeTkResendOtp(\'' + ticket + '\')" style="background:none;border:none;color:#6d5fad;font-size:12px;font-family:\'Geist\',sans-serif;cursor:pointer;padding:0;" id="baetk-resend-btn">Resend</button>' +
                    ' &nbsp;·&nbsp; <button onclick="window.location.reload()" style="background:none;border:none;color:#4d4a65;font-size:12px;font-family:\'Geist\',sans-serif;cursor:pointer;padding:0;">Back</button>' +
                '</div>';

            if (window.gsap) gsap.fromTo(wrap, {opacity:0, y:20}, {opacity:1, y:0, duration:.4, ease:'power3.out'});
            else wrap.style.opacity = 1;

            var otpInp = _baeTkEl('baetk-otp');
            if (otpInp) {
                otpInp.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '').substring(0, 6);
                });
                otpInp.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') baeTkVerifyOtp(ticket);
                });
                setTimeout(function() { otpInp.focus(); }, 200);
            }
        }
    }

    function baeTkVerifyOtp(ticket) {
        var otp    = (_baeTkEl('baetk-otp') || {}).value || '';
        var btn    = _baeTkEl('baetk-otp-btn');
        var sp     = _baeTkEl('baetk-otp-sp');
        var lbl    = _baeTkEl('baetk-otp-lbl');
        var arr    = _baeTkEl('baetk-otp-arr');
        var err    = _baeTkEl('baetk-otp-err');

        if (otp.length < 6) {
            if (err) { err.textContent = 'Please enter the 6-digit code.'; err.style.display = 'block'; }
            return;
        }

        if (btn) btn.disabled = true;
        if (sp)  sp.style.display  = 'block';
        if (arr) arr.style.display = 'none';
        if (lbl) lbl.textContent   = 'Verifying...';

        var fd = new FormData();
        fd.append('action', 'bae_verify_login_otp');
        fd.append('ticket', ticket);
        fd.append('otp', otp);

        fetch(_baeTkAj, { method:'POST', body:fd })
        .then(function(r) { return r.json(); })
        .then(function(j) {
            if (j.success && j.data.verified) {
                if (lbl) lbl.textContent = 'Opening...';
                var exp = new Date(Date.now() + 365*24*60*60*1000).toUTCString();
                document.cookie = 'bae_ticket=' + encodeURIComponent(ticket) + '; expires=' + exp + '; path=/; SameSite=Lax';
                if (window.gsap) {
                    gsap.to('#baetk-in', {opacity:0, y:-20, duration:.3, ease:'power2.in', onComplete:function(){ window.location.reload(); }});
                } else {
                    window.location.reload();
                }
            } else {
                if (btn) btn.disabled = false;
                if (sp)  sp.style.display  = 'none';
                if (arr) arr.style.display = '';
                if (lbl) lbl.textContent   = 'Verify Code';
                if (err) { err.textContent = (j.data && j.data.message) ? j.data.message : 'Incorrect code.'; err.style.display = 'block'; }
                if (window.gsap) gsap.fromTo(_baeTkEl('baetk-otp'), {x:-5},{x:0,duration:.35,ease:'elastic.out(1,.4)'});
            }
        })
        .catch(function() {
            if (btn) btn.disabled = false;
            if (sp)  sp.style.display  = 'none';
            if (arr) arr.style.display = '';
            if (lbl) lbl.textContent   = 'Verify Code';
            if (err) { err.textContent = 'Connection error. Try again.'; err.style.display = 'block'; }
        });
    }

    function baeTkResendOtp(ticket) {
        var resendBtn = _baeTkEl('baetk-resend-btn');
        var err       = _baeTkEl('baetk-otp-err');
        if (resendBtn) { resendBtn.disabled = true; resendBtn.textContent = 'Sending...'; }

        var fd = new FormData();
        fd.append('action', 'bae_send_login_otp');
        fd.append('ticket', ticket);

        fetch(_baeTkAj, { method:'POST', body:fd })
        .then(function(r) { return r.json(); })
        .then(function(j) {
            if (j.success) {
                if (err) { err.textContent = j.data.message; err.style.color = '#34d399'; err.style.display = 'block'; setTimeout(function(){ err.style.display='none'; err.style.color=''; },3000); }
            } else {
                if (err) { err.textContent = (j.data && j.data.message) || 'Failed to resend.'; err.style.display = 'block'; }
            }
        })
        .finally(function() {
            if (resendBtn) { resendBtn.disabled = false; resendBtn.textContent = 'Resend'; }
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

                var theme = document.querySelector('#baetk-page').getAttribute('data-theme') || 'light';
                var isDark = theme === 'dark';

                var overlayBg = isDark ? 'rgba(0,0,0,0.85)' : 'rgba(255,255,255,0.85)';
                var modalBg = isDark ? '#13111f' : '#ffffff';
                var modalBorder = isDark ? 'rgba(139,92,246,0.3)' : 'rgba(28,20,12,0.08)';
                var titleColor = isDark ? '#ede9ff' : '#1d1a16';
                var textColor = isDark ? '#4d4a65' : '#6d665c';
                var codeBg = isDark ? 'rgba(139,92,246,0.1)' : 'rgba(124,58,237,0.06)';
                var codeBorder = isDark ? 'rgba(139,92,246,0.25)' : 'rgba(124,58,237,0.16)';
                var codeLabelColor = isDark ? '#4d4a65' : '#5b21b6';
                var codeTextColor = isDark ? '#ede9ff' : '#1d1a16';
                var copyBtnBg = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(255,255,255,0.8)';
                var copyBtnBorder = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(28,20,12,0.08)';
                var copyBtnColor = isDark ? '#8b88a4' : '#6d665c';
                var footerColor = isDark ? '#2a2740' : '#8c857a';

                var overlay = document.createElement('div');
                overlay.id = 'baetk-reveal';
                overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:' + overlayBg + ';display:flex;align-items:center;justify-content:center;padding:24px;';
                overlay.innerHTML = [
                    '<div style="background:' + modalBg + ';border:1px solid ' + modalBorder + ';border-radius:20px;padding:36px 32px;max-width:420px;width:100%;text-align:center;">',
                        '<div style="width:58px;margin:0 auto 20px;">',
                            '<img src="' + _baeTkLogo + '" alt="Mothie logo" style="width:100%;height:auto;display:block;' + (isDark ? '' : 'filter:invert(1);') + '">',
                        '</div>',
                        '<div style="font-family:Instrument Serif,serif;font-size:26px;font-style:italic;color:' + titleColor + ';margin-bottom:8px;">Your ticket is ready</div>',
                        '<div style="font-size:13px;color:' + textColor + ';margin-bottom:24px;">Save this code to access your workspace from any device.</div>',
                        '<div style="background:' + codeBg + ';border:1.5px solid ' + codeBorder + ';border-radius:12px;padding:16px 20px;margin-bottom:8px;">',
                            '<div style="font-size:11px;font-weight:700;color:' + codeLabelColor + ';letter-spacing:0.1em;text-transform:uppercase;margin-bottom:8px;">Your Ticket Code</div>',
                            '<div style="font-family:monospace;font-size:26px;font-weight:800;letter-spacing:0.18em;color:' + codeTextColor + ';">' + ticket + '</div>',
                        '</div>',
                        '<button onclick="baeTkCopyReveal(\'' + ticket + '\')" style="width:100%;background:' + copyBtnBg + ';border:1px solid ' + copyBtnBorder + ';border-radius:10px;padding:10px;font-size:13px;font-weight:600;color:' + copyBtnColor + ';cursor:pointer;margin-bottom:16px;" id="baetk-copy-reveal">Copy ticket code</button>',
                        '<button onclick="baeTkRevealContinue()" style="width:100%;background:linear-gradient(135deg,#6d28d9,#8b5cf6);color:white;border:none;border-radius:12px;padding:14px 24px;font-size:15px;font-weight:700;cursor:pointer;">Continue to workspace</button>',
                        '<div style="font-size:11px;color:' + footerColor + ';margin-top:16px;">You can find this code in Settings anytime.</div>',
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
    // Admin route — ?bae=admin with valid admin session
    if ( isset( $_GET['bae'] ) && $_GET['bae'] === 'admin' ) {
        if ( bae_admin_check_session() ) {
            return bae_admin_dashboard();
        }
    }
    // All identity resolution (session cookie → ticket cookie) is now
    // handled inside bntm_shortcode_bae() / main.php. Just call it.
    add_filter( 'bae_settings_tab_output', 'bntm_bae_inject_settings_ticket' );
    return bntm_shortcode_bae();
}

// =============================================================================
// DASHBOARD with ticket context
// Replicates bntm_shortcode_bae but uses ticket-resolved profile
// =============================================================================

function bntm_bae_dashboard_with_ticket( $ticket, $profile ) {
    $user_id    = is_user_logged_in() ? get_current_user_id() : 0;
    $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';

    if ( ! isset( $_GET['tab'] ) && ! empty( $profile['id'] ) ) {
        global $wpdb;
        $has_generated_assets_tk = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bae_assets WHERE profile_id = %d AND is_generated = 1",
            $profile['id']
        ) ) >= 1;
        if ( $has_generated_assets_tk && ! bae_has_viewed_onboarding_asset( $profile ) ) {
            $active_tab = 'assets';
        }
    }

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
        background: linear-gradient(180deg, var(--bg) 0%, var(--bg-2) 100%);
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
    .bae-header-logo {
        display:flex; align-items:center; gap:10px;
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
    .bae-brand-wordmark-letter { color: var(--brand-soft); }
    .bae-wrap.bae-light .bae-brand-logo-img { filter: invert(1); }
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
    /* ── STEPPER ── */
    .bae-stepper {
        display: flex; align-items: center;
        padding: 0 24px;
        background: var(--bg-2);
        border-bottom: 1px solid var(--border);
        overflow-x: auto; scrollbar-width: none;
        transition: background 0.5s, border-color 0.5s;
        min-height: 56px;
    }
    .bae-stepper::-webkit-scrollbar { display: none; }
    .bae-step-wrap { display: flex; align-items: center; flex-shrink: 0; }
    .bae-step {
        display: flex; align-items: center; gap: 8px;
        padding: 8px 4px; text-decoration: none; cursor: pointer;
        transition: all .15s; white-space: nowrap;
        border: none; background: none; font-family: 'Geist', sans-serif;
    }
    .bae-step-node {
        width: 24px; height: 24px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700; flex-shrink: 0;
        transition: all .2s; border: 1.5px solid var(--border-2);
        color: var(--text-3); background: var(--bg-3);
    }
    .bae-step-label { font-size: 12px; font-weight: 500; color: var(--text-3); transition: color .15s; }
    .bae-step:hover .bae-step-node { border-color: var(--brand-s); color: var(--brand-s); }
    .bae-step:hover .bae-step-label { color: var(--text-2); }
    .bae-step-active .bae-step-node { background: var(--brand); border-color: var(--brand); color: white; box-shadow: 0 0 0 3px rgba(139,92,246,.2); }
    .bae-step-active .bae-step-label { color: var(--text); font-weight: 700; }
    .bae-step-done .bae-step-node { background: rgba(52,211,153,.12); border-color: rgba(52,211,153,.4); color: #34d399; }
    .bae-step-done .bae-step-label { color: var(--text-2); }
    .bae-step-done:hover .bae-step-node { background: rgba(52,211,153,.2); border-color: #34d399; }
    .bae-step-locked { opacity: .35; cursor: not-allowed; pointer-events: none; }
    .bae-step-locked .bae-step-node { border-style: dashed; }
    .bae-step-line { width: 28px; height: 1.5px; background: var(--border-2); flex-shrink: 0; margin: 0 4px; transition: background .3s; }
    .bae-step-line-done { background: rgba(52,211,153,.4); }

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
        gsap.fromTo('.bae-step', { opacity: 0, y: -6 }, { opacity: 1, y: 0, duration: 0.4, stagger: 0.05, ease: 'power3.out', delay: 0.2 });
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
            'assets'   => 'Asset Generator',
            'kit'      => 'Brand Kit',
            'startup'  => 'Launch Toolkit',
        ];
        ?>

        <!-- Header -->
        <div class="bae-header">
            <div class="bae-header-logo"><?php echo bae_render_brand_wordmark_merge(); ?></div>
            <div class="bae-header-right">
                <?php if ($onboarding_done_tk && $active_tab !== 'dashboard'): ?>
                <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'],'?')); ?>"
                   style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--text-2);text-decoration:none;padding:6px 12px;border:1px solid var(--border-2);border-radius:8px;transition:all .15s;font-family:'Geist',sans-serif;"
                   onmouseover="this.style.color='var(--text)';this.style.borderColor='var(--brand-s)'"
                   onmouseout="this.style.color='var(--text-2)';this.style.borderColor='var(--border-2)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
                    Dashboard
                </a>
                <?php endif; ?>
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

        <!-- Stepper Nav -->
        <div class="bae-stepper">
            <?php
            $tab_keys    = array_keys( $tabs );
            $total       = count( $tab_keys );
            $has_profile = !empty( $profile ) && !empty( $profile['business_name'] );
            $needs_profile = ['assets','kit','startup'];
            $user_plan   = $profile['plan'] ?? 'free';
            $is_free     = $user_plan === 'free';
            $pro_tabs    = [];
            global $wpdb;
            $ac_tk = $has_profile ? (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bae_assets WHERE ticket = %s AND is_generated = 1", $ticket
            )) : 0;
            $has_viewed_asset_tk = $has_profile && bae_has_viewed_onboarding_asset( $profile );
            $assets_done_tk = $has_profile && $ac_tk >= 1;
            $completed_steps = [
                'overview' => $has_profile,
                'assets'   => $assets_done_tk,
                'kit'      => $has_viewed_asset_tk,
                'startup'  => $has_viewed_asset_tk,
            ];
            $locks_tk = [
                'assets'  => !$has_profile,
                'kit'     => !$has_viewed_asset_tk,
                'startup' => !$has_viewed_asset_tk,
            ];
            foreach ( $tab_keys as $i => $slug ):
                $label          = $tabs[$slug];
                $short          = explode(' ', $label)[0];
                $is_active = $active_tab === $slug;
                $is_done   = ($completed_steps[$slug] ?? false) && !$is_active;
                $is_locked = $locks_tk[$slug] ?? false;
                $lock_msg_tk = match($slug) {
                    'assets'  => 'Complete Brand Profile first',
                    'kit'     => 'Preview 1 generated asset first',
                    'startup' => 'Preview 1 generated asset first',
                    default   => 'Complete previous step first',
                };
                $is_last        = $i === $total - 1;
                $step_num       = $i + 1;
            ?>
            <div class="bae-step-wrap">
                <?php if ($is_locked): ?>
                <div class="bae-step bae-step-locked" title="<?php echo esc_attr($lock_msg_tk); ?>">
                    <div class="bae-step-node"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg></div>
                    <div class="bae-step-label"><?php echo esc_html($short); ?></div>
                </div>
                <?php elseif ($is_done): ?>
                <a href="<?php echo esc_url($base_url); ?>?tab=<?php echo esc_attr($slug); ?>" class="bae-step bae-step-done">
                    <div class="bae-step-node"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg></div>
                    <div class="bae-step-label"><?php echo esc_html($short); ?></div>
                </a>
                <?php elseif ($is_active): ?>
                <div class="bae-step bae-step-active">
                    <div class="bae-step-node"><span><?php echo $step_num; ?></span></div>
                    <div class="bae-step-label"><?php echo esc_html($label); ?></div>
                </div>
                <?php else: ?>
                <a href="<?php echo esc_url($base_url); ?>?tab=<?php echo esc_attr($slug); ?>" class="bae-step">
                    <div class="bae-step-node"><span><?php echo $step_num; ?></span></div>
                    <div class="bae-step-label"><?php echo esc_html($short); ?></div>
                </a>
                <?php endif; ?>
                <?php if (!$is_last): ?>
                <div class="bae-step-line <?php echo ($completed_steps[$slug] ?? false) ? 'bae-step-line-done' : ''; ?>"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if ($onboarding_done_tk): ?>
            <div style="margin-left:auto;padding:0 8px 0 16px;flex-shrink:0;">
                <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'],'?')); ?>"
                   style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--brand-soft);text-decoration:none;padding:7px 14px;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.25);border-radius:8px;white-space:nowrap;"
                   onmouseover="this.style.background='rgba(139,92,246,.18)'"
                   onmouseout="this.style.background='rgba(139,92,246,.1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="15" rx="1"/></svg>
                    Dashboard
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tab Content -->
        <div class="bae-tab-content">
            <?php
            $profile_tabs = ['assets','kit','startup','brand_book'];
            if (in_array($active_tab, $profile_tabs) && !$has_profile) $active_tab = 'overview';

            // Onboarding completion check
            global $wpdb;
            $asset_count_tk = $has_profile && !empty($profile['id'])
                ? (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}bae_assets WHERE profile_id = %d AND is_generated = 1",
                    $profile['id']
                )) : 0;
            $onboarding_done_tk = $has_profile && bae_has_viewed_onboarding_asset( $profile );
            if ($onboarding_done_tk && !isset($_GET['tab'])) $active_tab = 'dashboard';

            if     ( $active_tab === 'overview'   ) echo bae_overview_tab( $user_id, $profile );
            elseif ( $active_tab === 'identity'   ) echo bae_overview_tab( $user_id, $profile ); // absorbed
            elseif ( $active_tab === 'assets'     ) echo bae_assets_tab(   $user_id, $profile );
            elseif ( $active_tab === 'kit'        ) echo bae_kit_tab(      $user_id, $profile );
            elseif ( $active_tab === 'startup'    ) echo bae_startup_tab(  $user_id, $profile );
            elseif ( $active_tab === 'settings'   ) echo bae_settings_tab( $user_id, $profile );
            elseif ( $active_tab === 'brand_book' ) echo bae_brand_book_tab( $user_id, $profile );
            elseif ( $active_tab === 'dashboard'  ) echo bae_home_dashboard( $user_id, $profile );
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

// NOTE: No patch hooks needed. main.php's bntm_ajax_bae_save_profile() already
// reads $_COOKIE['bae_ticket'] natively and saves it to the profile row.

// FIX: Do NOT override the nopriv handlers for save_profile, generate_asset,
// delete_asset, reset_profile, or save_kit_settings. The handlers in main.php
// already support both ticket-based AND session-based (no-ticket) identity.
// Overriding them with ticket-only versions broke all non-logged-in wizard users
// who land directly on the wizard without a ticket (the new flow).
// The main.php handlers are the source of truth — leave them in place.

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
        $saved_profile = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $existing->id ), ARRAY_A );
        $saved_plan = bae_get_user_plan( 0, $saved_profile );
        wp_send_json_success( [
            'message' => 'Brand profile updated successfully!',
            'profile_id' => (int) $existing->id,
            'plan' => $saved_plan,
            'starter_assets' => bae_get_onboarding_auto_asset_types( $saved_plan ),
        ] );
    } else {
        $data['rand_id']  = bntm_rand_id();
        $data['ticket']   = $ticket;
        $data['user_id']  = 0;
        $data['kit_slug'] = sanitize_title( $data['business_name'] ) . '-' . substr( $data['rand_id'], 0, 6 );
        $r = $wpdb->insert( $table, $data );
        $wpdb->show_errors();
        if ( $r === false ) wp_send_json_error( [ 'message' => 'Failed to save. Please try again.' ] );
        $profile_id = (int) $wpdb->insert_id;
        $saved_profile = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $profile_id ), ARRAY_A );
        $saved_plan = bae_get_user_plan( 0, $saved_profile );
        wp_send_json_success( [
            'message' => 'Brand profile created successfully!',
            'profile_id' => $profile_id,
            'plan' => $saved_plan,
            'starter_assets' => bae_get_onboarding_auto_asset_types( $saved_plan ),
        ] );
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