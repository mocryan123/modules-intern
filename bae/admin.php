<?php
/**
 * BAE — Admin Panel
 * File: admin.php
 * Include via: require_once(plugin_dir_path(__FILE__) . 'admin.php');
 *
 * Access: yoursite.com/bae-admin  (or whatever page slug you use with shortcode [bntm_bae_admin])
 * Auth:   Admin Secret Ticket — set BAE_ADMIN_SECRET below
 *
 * Features:
 *  - Secure admin ticket login (separate from user tickets)
 *  - Dashboard: total users, assets, plans, revenue estimates
 *  - Users table: search, filter by plan, view profile, upgrade/downgrade plan
 *  - Asset log: see what each user has generated
 *  - Payment log: all PayMongo transactions
 *  - Ticket actions: revoke, regenerate
 */

if (!defined('ABSPATH')) exit;

// ─────────────────────────────────────────────────────────────────
// CONFIG — Change this to a strong secret. This is your admin password.
// Format must be: ADM-XXXX-XXXX  (8 chars after ADM-)
// ─────────────────────────────────────────────────────────────────
define('BAE_ADMIN_SECRET', 'ADM-2525-2525'); // ← CHANGE THIS

// Admin session cookie name — different from user ticket
define('BAE_ADMIN_COOKIE', 'bae_admin_session');
define('BAE_ADMIN_NONCE',  'bae_admin_nonce');

// ─────────────────────────────────────────────────────────────────
// SESSION HELPERS
// ─────────────────────────────────────────────────────────────────
function bae_admin_check_session() {
    if (!isset($_COOKIE[BAE_ADMIN_COOKIE])) return false;
    $token = sanitize_text_field($_COOKIE[BAE_ADMIN_COOKIE]);
    // Validate token — stored as hash of secret + date + site_url
    $expected = bae_admin_make_token();
    return hash_equals($expected, $token);
}

function bae_admin_make_token() {
    return hash('sha256', BAE_ADMIN_SECRET . date('Y-m-d') . get_site_url());
}

// ─────────────────────────────────────────────────────────────────
// AJAX HOOKS
// ─────────────────────────────────────────────────────────────────
add_action('wp_ajax_bae_admin_login',        'bae_ajax_admin_login');
add_action('wp_ajax_nopriv_bae_admin_login', 'bae_ajax_admin_login');
add_action('wp_ajax_bae_admin_logout',        'bae_ajax_admin_logout');
add_action('wp_ajax_nopriv_bae_admin_logout', 'bae_ajax_admin_logout');
add_action('wp_ajax_bae_admin_set_plan',        'bae_ajax_admin_set_plan');
add_action('wp_ajax_nopriv_bae_admin_set_plan', 'bae_ajax_admin_set_plan');
add_action('wp_ajax_bae_admin_revoke_ticket',        'bae_ajax_admin_revoke_ticket');
add_action('wp_ajax_nopriv_bae_admin_revoke_ticket', 'bae_ajax_admin_revoke_ticket');
add_action('wp_ajax_bae_admin_get_assets',        'bae_ajax_admin_get_assets');
add_action('wp_ajax_nopriv_bae_admin_get_assets', 'bae_ajax_admin_get_assets');
add_action('wp_ajax_bae_admin_stats',        'bae_ajax_admin_stats');
add_action('wp_ajax_nopriv_bae_admin_stats', 'bae_ajax_admin_stats');

function bae_admin_auth_check() {
    if (!bae_admin_check_session()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }
}

function bae_ajax_admin_login() {
    $secret = sanitize_text_field($_POST['secret'] ?? '');
    if (!hash_equals(BAE_ADMIN_SECRET, $secret)) {
        wp_send_json_error(['message' => 'Invalid admin code. Try again.']);
    }
    $token   = bae_admin_make_token();
    $expires = time() + (DAY_IN_SECONDS * 1); // 1 day session
    setcookie(BAE_ADMIN_COOKIE, $token, $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    wp_send_json_success(['message' => 'Authenticated.']);
}

function bae_ajax_admin_logout() {
    setcookie(BAE_ADMIN_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    wp_send_json_success(['message' => 'Logged out.']);
}

function bae_ajax_admin_set_plan() {
    bae_admin_auth_check();
    check_ajax_referer(BAE_ADMIN_NONCE, 'nonce');
    global $wpdb;
    $ticket = sanitize_text_field($_POST['ticket'] ?? '');
    $plan   = sanitize_text_field($_POST['plan']   ?? '');
    if (!in_array($plan, ['free', 'starter', 'pro'])) wp_send_json_error(['message' => 'Invalid plan.']);
    if (!preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $ticket)) wp_send_json_error(['message' => 'Invalid ticket.']);
    $r = $wpdb->update($wpdb->prefix . 'bae_profiles', ['plan' => $plan], ['ticket' => $ticket], ['%s'], ['%s']);
    if ($r === false) wp_send_json_error(['message' => 'DB error.']);
    wp_send_json_success(['message' => 'Plan updated to ' . $plan . '.']);
}

function bae_ajax_admin_revoke_ticket() {
    bae_admin_auth_check();
    check_ajax_referer(BAE_ADMIN_NONCE, 'nonce');
    global $wpdb;
    $ticket = sanitize_text_field($_POST['ticket'] ?? '');
    if (!preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $ticket)) wp_send_json_error(['message' => 'Invalid ticket.']);
    // Delete assets + profile
    $wpdb->delete($wpdb->prefix . 'bae_assets',   ['ticket' => $ticket], ['%s']);
    $wpdb->delete($wpdb->prefix . 'bae_profiles', ['ticket' => $ticket], ['%s']);
    wp_send_json_success(['message' => 'Ticket revoked and data deleted.']);
}

function bae_ajax_admin_get_assets() {
    bae_admin_auth_check();
    global $wpdb;
    $ticket = sanitize_text_field($_GET['ticket'] ?? '');
    if (!preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $ticket)) wp_send_json_error(['message' => 'Invalid ticket.']);
    $assets = $wpdb->get_results($wpdb->prepare(
        "SELECT asset_type, asset_name, is_generated, created_at FROM {$wpdb->prefix}bae_assets WHERE ticket = %s ORDER BY created_at DESC",
        $ticket
    ), ARRAY_A);
    wp_send_json_success(['assets' => $assets]);
}

function bae_ajax_admin_stats() {
    bae_admin_auth_check();
    global $wpdb;
    $pt = $wpdb->prefix . 'bae_profiles';
    $at = $wpdb->prefix . 'bae_assets';
    $pay = $wpdb->prefix . 'bae_payments';

    $stats = [
        'total_users'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE business_name != ''"),
        'total_assets'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$at} WHERE is_generated = 1"),
        'plan_free'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE plan = 'free' AND business_name != ''"),
        'plan_starter'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE plan = 'starter'"),
        'plan_pro'       => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE plan = 'pro'"),
        'new_today'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE DATE(created_at) = CURDATE() AND business_name != ''"),
        'new_this_week'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND business_name != ''"),
    ];

    // Revenue from payments table if it exists
    $pay_exists = $wpdb->get_var("SHOW TABLES LIKE '{$pay}'");
    if ($pay_exists) {
        $stats['total_revenue'] = (int) $wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM {$pay} WHERE status = 'paid'");
        $stats['paid_count']    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pay} WHERE status = 'paid'");
    } else {
        $stats['total_revenue'] = 0;
        $stats['paid_count']    = 0;
    }

    wp_send_json_success($stats);
}

// ─────────────────────────────────────────────────────────────────
// ADMIN LOGIN SCREEN
// ─────────────────────────────────────────────────────────────────
function bae_admin_login_screen() {
    $aj = esc_js(admin_url('admin-ajax.php'));
    ob_start(); ?>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <style>
    .bae-adm-login {
        font-family: 'Geist', -apple-system, sans-serif;
        background: #07070d;
        min-height: 540px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 48px 24px;
        border-radius: 20px;
        position: relative;
        overflow: hidden;
    }
    .bae-adm-login::before {
        content: '';
        position: absolute;
        width: 600px; height: 600px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(109,40,217,.1) 0%, transparent 65%);
        top: -200px; right: -150px;
        pointer-events: none;
    }
    .bae-adm-login::after {
        content: '';
        position: absolute;
        width: 400px; height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(236,72,153,.07) 0%, transparent 65%);
        bottom: -100px; left: -100px;
        pointer-events: none;
    }
    .bae-adm-login-inner {
        width: 100%;
        max-width: 380px;
        text-align: center;
        position: relative;
        z-index: 2;
    }
    .bae-adm-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(109,40,217,.15);
        border: 1px solid rgba(139,92,246,.25);
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 700;
        color: #a78bfa;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: 24px;
    }
    .bae-adm-badge svg { flex-shrink: 0; }
    .bae-adm-title {
        font-family: 'Instrument Serif', serif;
        font-size: 36px;
        font-style: italic;
        color: #ede9ff;
        line-height: 1.15;
        margin-bottom: 8px;
    }
    .bae-adm-sub {
        font-size: 13px;
        color: #3d3a55;
        margin-bottom: 36px;
        line-height: 1.6;
    }
    .bae-adm-inp {
        width: 100%;
        background: rgba(255,255,255,.04);
        border: 1.5px solid rgba(139,92,246,.18);
        border-radius: 14px;
        padding: 15px 20px;
        font-size: 18px;
        font-family: 'Geist', monospace;
        font-weight: 700;
        letter-spacing: .12em;
        color: #ede9ff;
        outline: none;
        text-align: center;
        text-transform: uppercase;
        transition: border-color .2s, box-shadow .2s;
        margin-bottom: 12px;
        display: block;
        box-sizing: border-box;
    }
    .bae-adm-inp:focus {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139,92,246,.12);
    }
    .bae-adm-inp::placeholder { color: #25223a; font-size: 13px; letter-spacing: .06em; }
    .bae-adm-btn {
        width: 100%;
        background: linear-gradient(135deg, #6d28d9, #8b5cf6);
        color: white;
        border: none;
        border-radius: 14px;
        padding: 15px 24px;
        font-size: 14px;
        font-weight: 700;
        font-family: 'Geist', sans-serif;
        cursor: pointer;
        transition: all .2s;
        box-shadow: 0 8px 28px rgba(109,40,217,.35);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-sizing: border-box;
    }
    .bae-adm-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 36px rgba(109,40,217,.45); }
    .bae-adm-btn:disabled { opacity: .4; cursor: not-allowed; transform: none; box-shadow: none; }
    .bae-adm-err {
        font-size: 12px;
        color: #fb7185;
        margin-top: 10px;
        background: rgba(244,63,94,.08);
        border: 1px solid rgba(244,63,94,.2);
        border-radius: 10px;
        padding: 10px 14px;
        display: none;
        text-align: center;
    }
    .bae-adm-spin {
        width: 16px; height: 16px;
        border: 2px solid rgba(255,255,255,.3);
        border-top-color: white;
        border-radius: 50%;
        animation: bae-spin .7s linear infinite;
        display: none;
    }
    @keyframes bae-spin { to { transform: rotate(360deg); } }
    </style>

    <div class="bae-adm-login">
        <div class="bae-adm-login-inner" id="bae-adm-login-wrap">
            <div class="bae-adm-badge">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
                Admin Access
            </div>
            <div class="bae-adm-title">Command Center</div>
            <div class="bae-adm-sub">Enter your admin code to access<br>the BAE admin panel.</div>
            <input type="password" id="bae-adm-inp" class="bae-adm-inp"
                   placeholder="ADM-XXXX-XXXX"
                   autocomplete="off" spellcheck="false">
            <button class="bae-adm-btn" id="bae-adm-btn" onclick="baeAdmLogin()">
                <div class="bae-adm-spin" id="bae-adm-spin"></div>
                <span id="bae-adm-lbl">Access Panel</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" id="bae-adm-arr"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
            <div class="bae-adm-err" id="bae-adm-err"></div>
        </div>
    </div>

    <script>
    var _baeAdmAj = '<?php echo $aj; ?>';
    document.addEventListener('DOMContentLoaded', function() {
        var inp = document.getElementById('bae-adm-inp');
        if (inp) {
            inp.focus();
            inp.addEventListener('keydown', function(e) { if (e.key === 'Enter') baeAdmLogin(); });
        }
        if (window.gsap) {
            gsap.fromTo('#bae-adm-login-wrap', {opacity:0,y:20}, {opacity:1,y:0,duration:.5,ease:'power3.out',delay:.1});
        }
    });

    function baeAdmLogin() {
        var inp = document.getElementById('bae-adm-inp');
        var btn = document.getElementById('bae-adm-btn');
        var sp  = document.getElementById('bae-adm-spin');
        var lbl = document.getElementById('bae-adm-lbl');
        var arr = document.getElementById('bae-adm-arr');
        var err = document.getElementById('bae-adm-err');
        var val = (inp.value || '').trim().toUpperCase();

        if (!val) { err.textContent = 'Enter your admin code.'; err.style.display = 'block'; inp.focus(); return; }

        btn.disabled = true;
        sp.style.display = 'block';
        arr.style.display = 'none';
        lbl.textContent = 'Verifying...';
        err.style.display = 'none';

        var fd = new FormData();
        fd.append('action', 'bae_admin_login');
        fd.append('secret', val);

        fetch(_baeAdmAj, {method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(j) {
            if (j.success) {
                lbl.textContent = 'Entering...';
                setTimeout(function() { window.location.reload(); }, 400);
            } else {
                btn.disabled = false;
                sp.style.display = 'none';
                arr.style.display = '';
                lbl.textContent = 'Access Panel';
                err.textContent = (j.data && j.data.message) ? j.data.message : 'Incorrect code.';
                err.style.display = 'block';
                if (window.gsap) gsap.fromTo(inp, {x:-6},{x:0,duration:.4,ease:'elastic.out(1,.4)'});
            }
        })
        .catch(function() {
            btn.disabled = false;
            sp.style.display = 'none';
            arr.style.display = '';
            lbl.textContent = 'Access Panel';
            err.textContent = 'Connection error. Try again.';
            err.style.display = 'block';
        });
    }
    </script>
    <?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────────
// ADMIN DASHBOARD
// ─────────────────────────────────────────────────────────────────
function bae_admin_dashboard() {
    global $wpdb;
    $pt    = $wpdb->prefix . 'bae_profiles';
    $at    = $wpdb->prefix . 'bae_assets';
    $payt  = $wpdb->prefix . 'bae_payments';
    $nonce = wp_create_nonce(BAE_ADMIN_NONCE);
    $aj    = admin_url('admin-ajax.php');
    $route = isset($_GET['adm']) ? sanitize_text_field($_GET['adm']) : 'overview';

    $users = $wpdb->get_results(
        "SELECT p.*,
            (SELECT COUNT(*) FROM {$at} a WHERE a.ticket = p.ticket AND a.is_generated = 1) as asset_count
         FROM {$pt} p
         WHERE p.business_name != ''
         ORDER BY p.created_at DESC
         LIMIT 200",
        ARRAY_A
    ) ?: [];

    $stats = [
        'total'   => count($users),
        'free'    => count(array_filter($users, fn($u) => ($u['plan'] ?? 'free') === 'free')),
        'starter' => count(array_filter($users, fn($u) => ($u['plan'] ?? '') === 'starter')),
        'pro'     => count(array_filter($users, fn($u) => ($u['plan'] ?? '') === 'pro')),
        'assets'  => array_sum(array_column($users, 'asset_count')),
    ];

    $pay_exists = $wpdb->get_var("SHOW TABLES LIKE '{$payt}'");
    $payments   = [];
    $revenue    = 0;
    if ($pay_exists) {
        $payments = $wpdb->get_results("SELECT * FROM {$payt} ORDER BY created_at DESC LIMIT 50", ARRAY_A) ?: [];
        $revenue  = (int)$wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM {$payt} WHERE status='paid'");
    }

    $paid   = $stats['starter'] + $stats['pro'];
    $total  = max($stats['total'], 1);
    $conv   = round(($paid / $total) * 100, 1);
    $conv_w = min($conv * 3, 100);

    // All assets for assets route
    $all_assets = [];
    if ($route === 'assets') {
        $all_assets = $wpdb->get_results(
            "SELECT a.*, p.business_name FROM {$at} a
             LEFT JOIN {$pt} p ON p.ticket = a.ticket
             WHERE a.is_generated = 1
             ORDER BY a.created_at DESC LIMIT 200",
            ARRAY_A
        ) ?: [];
    }

    // Tickets for tickets route
    $all_tickets = [];
    if ($route === 'tickets') {
        $all_tickets = $wpdb->get_results(
            "SELECT ticket, business_name, plan, created_at,
                (SELECT COUNT(*) FROM {$at} a WHERE a.ticket = p.ticket AND a.is_generated = 1) as asset_count
             FROM {$pt} p ORDER BY created_at DESC LIMIT 200",
            ARRAY_A
        ) ?: [];
    }

    $base_url = strtok($_SERVER['REQUEST_URI'], '?') . '?bae=admin';

    $nav_items = [
        'overview'  => ['label' => 'Overview',  'group' => 'Dashboard', 'icon' => '<path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/>'],
        'users'     => ['label' => 'Users',     'group' => 'Manage',    'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
        'tickets'   => ['label' => 'Tickets',   'group' => 'Manage',    'icon' => '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M16 14h2M6 14h2M6 10h12"/>'],
        'assets'    => ['label' => 'Assets',    'group' => 'Manage',    'icon' => '<rect width="8" height="8" x="3" y="3" rx="1"/><rect width="8" height="5" x="13" y="3" rx="1"/><rect width="8" height="8" x="13" y="12" rx="1"/><rect width="8" height="5" x="3" y="15" rx="1"/>'],
        'payments'  => ['label' => 'Payments',  'group' => 'Manage',    'icon' => '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>'],
        'plans'     => ['label' => 'Plans',     'group' => 'Manage',    'icon' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>'],
        'analytics' => ['label' => 'Analytics', 'group' => 'Insights',  'icon' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
        'logs'      => ['label' => 'Logs',      'group' => 'Insights',  'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>'],
        'settings'  => ['label' => 'Settings',  'group' => 'Config',    'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
        'security'  => ['label' => 'Security',  'group' => 'Config',    'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
    ];

    $route_titles = [
        'overview'  => 'Overview',
        'users'     => 'Users',
        'tickets'   => 'Tickets',
        'assets'    => 'Assets',
        'payments'  => 'Payments',
        'plans'     => 'Plans',
        'analytics' => 'Analytics',
        'logs'      => 'Logs',
        'settings'  => 'Settings',
        'security'  => 'Security',
    ];

    $paymongo_secret = get_option('bae_paymongo_secret', '');
    $paymongo_public = get_option('bae_paymongo_public', '');

    ob_start();
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <style>
    .bae-adm {
        font-family: 'Geist', -apple-system, sans-serif;
        --bg:       #07070d;
        --bg-2:     #0e0e17;
        --bg-3:     #13131e;
        --surface:  #1a1a28;
        --border:   rgba(255,255,255,.07);
        --border-2: rgba(255,255,255,.12);
        --text:     #ede9ff;
        --text-2:   #9490b5;
        --text-3:   #3d3a55;
        --brand:    #8b5cf6;
        --brand-d:  #6d28d9;
        --brand-s:  #a78bfa;
        --pink:     #ec4899;
        --green:    #34d399;
        --yellow:   #fbbf24;
        --red:      #fb7185;
        background: var(--bg);
        color: var(--text);
        border-radius: 20px;
        overflow: hidden;
        height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* ── Layout ── */
    .bae-adm-layout { display: flex; flex: 1; overflow: hidden; }

    /* ── Sidebar ── */
    .bae-adm-sidebar {
        width: 210px;
        min-width: 210px;
        flex-shrink: 0;
        background: var(--bg-2);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }
    .bae-adm-sidebar-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 18px 16px;
        border-bottom: 1px solid var(--border);
        overflow: hidden;
        flex-shrink: 0;
    }
    .bae-adm-sidebar-icon {
        width: 32px; height: 32px; min-width: 32px;
        background: linear-gradient(135deg, #6d28d9, #ec4899);
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 0 14px rgba(139,92,246,.3);
    }
    .bae-adm-sidebar-icon svg { width: 15px; height: 15px; flex-shrink: 0; }
    .bae-adm-sidebar-name {
        font-family: 'Instrument Serif', serif;
        font-size: 16px; font-style: italic;
        color: var(--text); line-height: 1;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .bae-adm-sidebar-sub { font-size: 10px; color: var(--text-3); margin-top: 2px; white-space: nowrap; }

    /* Search */
    .bae-adm-sidebar-search { padding: 12px 12px 8px; flex-shrink: 0; }
    .bae-adm-sidebar-search input {
        width: 100%; box-sizing: border-box;
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 7px 10px 7px 30px;
        font-size: 12px; color: var(--text-2);
        font-family: 'Geist', sans-serif;
        outline: none; transition: border-color .15s;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%233d3a55' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: 10px center;
    }
    .bae-adm-sidebar-search input:focus { border-color: var(--brand); }
    .bae-adm-sidebar-search input::placeholder { color: var(--text-3); }

    /* Nav */
    .bae-adm-nav { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 4px 8px 8px; }
    .bae-adm-nav::-webkit-scrollbar { width: 3px; }
    .bae-adm-nav::-webkit-scrollbar-track { background: transparent; }
    .bae-adm-nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
    .bae-adm-nav-label {
        font-size: 9px; font-weight: 700; color: var(--text-3);
        text-transform: uppercase; letter-spacing: .1em;
        padding: 0 8px; margin: 14px 0 4px;
        white-space: nowrap; overflow: hidden;
    }
    .bae-adm-nav-item {
        display: flex; align-items: center; gap: 8px;
        padding: 8px 10px; border-radius: 8px;
        font-size: 13px; font-weight: 500; color: var(--text-3);
        cursor: pointer; transition: all .15s;
        text-decoration: none; border: none; background: none;
        width: 100%; font-family: 'Geist', sans-serif;
        white-space: nowrap; overflow: hidden;
        box-sizing: border-box;
    }
    .bae-adm-nav-item:hover { color: var(--text-2); background: rgba(255,255,255,.03); }
    .bae-adm-nav-item.active { color: var(--text); background: rgba(139,92,246,.12); }
    .bae-adm-nav-item svg { width: 14px; height: 14px; min-width: 14px; opacity: .5; }
    .bae-adm-nav-item.active svg { opacity: 1; color: var(--brand-s); }
    .bae-adm-nav-item span.nav-label { overflow: hidden; text-overflow: ellipsis; flex: 1; }
    .bae-adm-nav-badge {
        margin-left: auto; flex-shrink: 0;
        background: var(--surface); border-radius: 999px;
        padding: 1px 6px; font-size: 10px; color: var(--text-3);
    }

    .bae-adm-sidebar-footer {
        padding: 10px 8px; border-top: 1px solid var(--border); flex-shrink: 0;
    }
    .bae-adm-signout {
        display: flex; align-items: center; gap: 8px;
        padding: 8px 10px; border-radius: 8px;
        font-size: 13px; font-weight: 500; color: var(--red);
        cursor: pointer; transition: all .15s;
        border: none; background: none; width: 100%;
        font-family: 'Geist', sans-serif;
        white-space: nowrap; overflow: hidden; box-sizing: border-box;
    }
    .bae-adm-signout:hover { background: rgba(251,113,133,.06); }
    .bae-adm-signout svg { width: 14px; height: 14px; min-width: 14px; }

    /* ── Main ── */
    .bae-adm-main { flex: 1; overflow-y: auto; overflow-x: hidden; min-width: 0; }
    .bae-adm-topbar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 24px; border-bottom: 1px solid var(--border);
        background: var(--bg); position: sticky; top: 0; z-index: 10;
    }
    .bae-adm-topbar-title { font-size: 15px; font-weight: 700; color: var(--text); }
    .bae-adm-topbar-sub   { font-size: 11px; color: var(--text-3); margin-top: 1px; }
    .bae-adm-topbar-date  { font-size: 11px; color: var(--text-3); }
    .bae-adm-body { padding: 24px; }

    /* ── Stats ── */
    .bae-adm-stats {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(150px,1fr));
        gap: 12px; margin-bottom: 24px;
    }
    .bae-adm-stat {
        background: var(--bg-2); border: 1px solid var(--border);
        border-radius: 14px; padding: 18px 20px; transition: border-color .2s;
    }
    .bae-adm-stat:hover { border-color: var(--border-2); }
    .bae-adm-stat-label { font-size: 11px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 10px; }
    .bae-adm-stat-val   { font-size: 28px; font-weight: 800; color: var(--text); line-height: 1; }
    .bae-adm-stat-sub   { font-size: 11px; color: var(--text-3); margin-top: 4px; }
    .bae-adm-stat-accent-purple { border-left: 3px solid var(--brand); }
    .bae-adm-stat-accent-green  { border-left: 3px solid var(--green); }
    .bae-adm-stat-accent-yellow { border-left: 3px solid var(--yellow); }
    .bae-adm-stat-accent-pink   { border-left: 3px solid var(--pink); }
    .bae-adm-stat-accent-blue   { border-left: 3px solid #60a5fa; }

    /* ── Toolbar ── */
    .bae-adm-toolbar { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; align-items: center; }
    .bae-adm-search {
        flex: 1; min-width: 200px;
        background: var(--bg-2); border: 1.5px solid var(--border); border-radius: 10px;
        padding: 9px 14px 9px 36px; font-size: 13px; color: var(--text);
        font-family: 'Geist', sans-serif; outline: none; transition: border-color .2s;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%233d3a55' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: 12px center;
    }
    .bae-adm-search:focus { border-color: var(--brand); }
    .bae-adm-search::placeholder { color: var(--text-3); }
    .bae-adm-filter {
        background: var(--bg-2); border: 1.5px solid var(--border); border-radius: 10px;
        padding: 9px 14px; font-size: 13px; color: var(--text-2);
        font-family: 'Geist', sans-serif; outline: none; cursor: pointer;
    }
    .bae-adm-filter:focus { border-color: var(--brand); }

    /* ── Table ── */
    .bae-adm-table-wrap { background: var(--bg-2); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
    .bae-adm-table { width: 100%; border-collapse: collapse; }
    .bae-adm-table th {
        font-size: 10px; font-weight: 700; color: var(--text-3);
        text-transform: uppercase; letter-spacing: .08em;
        padding: 12px 16px; text-align: left;
        border-bottom: 1px solid var(--border); background: var(--bg-3); white-space: nowrap;
    }
    .bae-adm-table td { padding: 12px 16px; font-size: 13px; color: var(--text-2); border-bottom: 1px solid var(--border); vertical-align: middle; }
    .bae-adm-table tr:last-child td { border-bottom: none; }
    .bae-adm-table tr:hover td { background: var(--bg-3); }
    .bae-adm-table .biz-name { font-weight: 600; color: var(--text); max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .bae-adm-table .ticket-code { font-family: monospace; font-size: 12px; color: var(--brand-s); letter-spacing: .06em; }

    /* ── Plan badges ── */
    .bae-plan { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; font-size: 10px; font-weight: 700; letter-spacing: .05em; }
    .bae-plan-free    { background: rgba(255,255,255,.06); color: var(--text-3); }
    .bae-plan-starter { background: rgba(52,211,153,.1);  color: var(--green); border: 1px solid rgba(52,211,153,.2); }
    .bae-plan-pro     { background: rgba(139,92,246,.12); color: var(--brand-s); border: 1px solid rgba(139,92,246,.25); }

    .bae-plan-select {
        background: var(--surface); border: 1px solid var(--border-2); border-radius: 7px;
        padding: 5px 8px; font-size: 12px; font-weight: 600;
        font-family: 'Geist', sans-serif; color: var(--text-2); cursor: pointer; outline: none;
    }
    .bae-plan-select:focus { border-color: var(--brand); }

    .bae-adm-row-actions { display: flex; align-items: center; gap: 6px; }
    .bae-adm-action-btn {
        padding: 5px 10px; border-radius: 7px; font-size: 11px; font-weight: 600;
        font-family: 'Geist', sans-serif; cursor: pointer;
        border: 1px solid var(--border-2); background: var(--surface); color: var(--text-3);
        transition: all .15s; white-space: nowrap;
    }
    .bae-adm-action-btn:hover { color: var(--text); border-color: var(--brand-s); }
    .bae-adm-action-revoke { color: var(--red); border-color: rgba(251,113,133,.2); }
    .bae-adm-action-revoke:hover { background: rgba(251,113,133,.06); border-color: rgba(251,113,133,.4); }

    .bae-adm-asset-pill {
        display: inline-flex; align-items: center; gap: 5px;
        background: rgba(139,92,246,.1); border: 1px solid rgba(139,92,246,.2);
        border-radius: 999px; padding: 3px 10px;
        font-size: 11px; font-weight: 600; color: var(--brand-s); margin: 3px;
    }
    .bae-adm-pay-amount { font-weight: 700; color: var(--green); }
    .bae-adm-empty { text-align: center; padding: 48px 24px; color: var(--text-3); font-size: 14px; }

    /* ── Settings form ── */
    .bae-adm-form-section {
        background: var(--bg-2); border: 1px solid var(--border);
        border-radius: 14px; padding: 22px; margin-bottom: 16px;
    }
    .bae-adm-form-section h3 { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
    .bae-adm-form-section p  { font-size: 12px; color: var(--text-3); margin-bottom: 18px; }
    .bae-adm-field { margin-bottom: 14px; }
    .bae-adm-field label { display: block; font-size: 11px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px; }
    .bae-adm-field input[type=text],
    .bae-adm-field input[type=password] {
        width: 100%; box-sizing: border-box;
        background: var(--bg-3); border: 1.5px solid var(--border);
        border-radius: 10px; padding: 10px 14px;
        font-size: 13px; font-family: 'Geist', sans-serif; color: var(--text);
        outline: none; transition: border-color .2s;
    }
    .bae-adm-field input:focus { border-color: var(--brand); }
    .bae-adm-save-btn {
        display: inline-flex; align-items: center; gap: 8px;
        background: linear-gradient(135deg, var(--brand-d), var(--brand));
        color: white; border: none; border-radius: 10px;
        padding: 10px 20px; font-size: 13px; font-weight: 700;
        font-family: 'Geist', sans-serif; cursor: pointer;
        transition: all .2s; box-shadow: 0 4px 16px rgba(109,40,217,.3);
    }
    .bae-adm-save-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(109,40,217,.4); }
    .bae-adm-save-btn:disabled { opacity: .4; cursor: not-allowed; transform: none; }

    /* ── Conversion bar ── */
    .bae-adm-conv-bar { background: var(--bg-2); border: 1px solid var(--border); border-radius: 14px; padding: 20px 22px; margin-bottom: 16px; }

    /* ── Confirm modal ── */
    .bae-adm-confirm-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,.7); backdrop-filter: blur(8px);
        z-index: 99999; display: none; align-items: center; justify-content: center; padding: 24px;
    }
    .bae-adm-confirm-overlay.open { display: flex; }
    .bae-adm-confirm-modal {
        background: var(--surface); border: 1px solid var(--border-2);
        border-radius: 18px; width: 100%; max-width: 400px;
        box-shadow: 0 24px 80px rgba(0,0,0,.6); overflow: hidden;
    }
    .bae-adm-confirm-header { padding: 18px 22px 0; }
    .bae-adm-confirm-title {
        font-family: 'Instrument Serif', serif; font-size: 18px; font-style: italic;
        color: var(--text); margin-bottom: 8px;
    }
    .bae-adm-confirm-msg { font-size: 13px; color: var(--text-2); line-height: 1.6; padding: 0 22px 18px; }
    .bae-adm-confirm-footer {
        display: flex; gap: 10px; justify-content: flex-end;
        padding: 14px 22px; border-top: 1px solid var(--border);
    }
    .bae-adm-confirm-cancel {
        padding: 9px 18px; border-radius: 9px; font-size: 13px; font-weight: 600;
        font-family: 'Geist', sans-serif; cursor: pointer;
        border: 1px solid var(--border-2); background: var(--bg-3); color: var(--text-2);
        transition: all .15s;
    }
    .bae-adm-confirm-cancel:hover { color: var(--text); border-color: var(--border-2); }
    .bae-adm-confirm-ok {
        padding: 9px 18px; border-radius: 9px; font-size: 13px; font-weight: 700;
        font-family: 'Geist', sans-serif; cursor: pointer;
        border: none; background: var(--red); color: white; transition: all .15s;
    }
    .bae-adm-confirm-ok:hover { background: #e11d48; }

    /* ── Toast ── */
    #bae-adm-toast {
        position: fixed; bottom: 24px; right: 24px;
        background: var(--surface); border: 1px solid var(--border-2);
        border-radius: 12px; padding: 12px 18px;
        font-size: 13px; font-weight: 600; color: var(--text);
        z-index: 99999; display: none;
        box-shadow: 0 8px 32px rgba(0,0,0,.4); max-width: 300px;
    }
    #bae-adm-toast.success { border-color: rgba(52,211,153,.3); color: var(--green); }
    #bae-adm-toast.error   { border-color: rgba(251,113,133,.3); color: var(--red); }

    @media (max-width: 700px) {
        .bae-adm-sidebar { display: none; }
        .bae-adm-body { padding: 16px; }
    }
    </style>

    <div class="bae-adm" id="bae-adm-wrap">

        <!-- Confirm Modal -->
        <div class="bae-adm-confirm-overlay" id="bae-adm-confirm">
            <div class="bae-adm-confirm-modal">
                <div class="bae-adm-confirm-header">
                    <div class="bae-adm-confirm-title" id="bae-adm-confirm-title">Confirm Action</div>
                </div>
                <div class="bae-adm-confirm-msg" id="bae-adm-confirm-msg"></div>
                <div class="bae-adm-confirm-footer">
                    <button class="bae-adm-confirm-cancel" id="bae-adm-confirm-cancel">Cancel</button>
                    <button class="bae-adm-confirm-ok" id="bae-adm-confirm-ok">Confirm</button>
                </div>
            </div>
        </div>

        <div class="bae-adm-layout">

            <!-- ── Sidebar ── -->
            <aside class="bae-adm-sidebar">
                <div class="bae-adm-sidebar-logo">
                    <div class="bae-adm-sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    </div>
                    <div>
                        <div class="bae-adm-sidebar-name">BAE Admin</div>
                        <div class="bae-adm-sidebar-sub">Command Center</div>
                    </div>
                </div>

                <div class="bae-adm-sidebar-search">
                    <input type="text" id="bae-adm-nav-search" placeholder="Search menu..." oninput="baeAdmNavSearch(this.value)">
                </div>

                <nav class="bae-adm-nav" id="bae-adm-nav">
                    <?php
                    $groups = [];
                    foreach ($nav_items as $key => $item) {
                        $groups[$item['group']][$key] = $item;
                    }
                    foreach ($groups as $group_name => $items):
                    ?>
                    <div class="bae-adm-nav-label" data-nav-group="<?php echo esc_attr($group_name); ?>"><?php echo esc_html($group_name); ?></div>
                    <?php foreach ($items as $key => $item): ?>
                    <a href="<?php echo esc_url($base_url . '&adm=' . $key); ?>"
                       class="bae-adm-nav-item <?php echo $route === $key ? 'active' : ''; ?>"
                       data-nav-label="<?php echo esc_attr(strtolower($item['label'])); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $item['icon']; ?></svg>
                        <span class="nav-label"><?php echo esc_html($item['label']); ?></span>
                        <?php if ($key === 'users'): ?>
                        <span class="bae-adm-nav-badge"><?php echo count($users); ?></span>
                        <?php elseif ($key === 'assets'): ?>
                        <span class="bae-adm-nav-badge"><?php echo $stats['assets']; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </nav>

                <div class="bae-adm-sidebar-footer">
                    <button class="bae-adm-signout" onclick="baeAdmLogout()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>Sign Out</span>
                    </button>
                </div>
            </aside>

            <!-- ── Main ── -->
            <div class="bae-adm-main">
                <div class="bae-adm-topbar">
                    <div>
                        <div class="bae-adm-topbar-title"><?php echo esc_html($route_titles[$route] ?? 'Overview'); ?></div>
                        <div class="bae-adm-topbar-sub">Brand Asset Engine</div>
                    </div>
                    <div class="bae-adm-topbar-date"><?php echo date('M d, Y'); ?></div>
                </div>

                <div class="bae-adm-body">

                <?php if ($route === 'overview'): ?>
                    <div class="bae-adm-stats">
                        <div class="bae-adm-stat bae-adm-stat-accent-purple">
                            <div class="bae-adm-stat-label">Total Users</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['total']; ?></div>
                            <div class="bae-adm-stat-sub">with a brand profile</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-green">
                            <div class="bae-adm-stat-label">Assets</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['assets']; ?></div>
                            <div class="bae-adm-stat-sub">generated</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-yellow">
                            <div class="bae-adm-stat-label">Revenue</div>
                            <div class="bae-adm-stat-val">₱<?php echo number_format($revenue / 100); ?></div>
                            <div class="bae-adm-stat-sub"><?php echo $pay_exists ? count($payments) : 0; ?> transactions</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-pink">
                            <div class="bae-adm-stat-label">Pro</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['pro']; ?></div>
                            <div class="bae-adm-stat-sub">subscribers</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-green">
                            <div class="bae-adm-stat-label">Starter</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['starter']; ?></div>
                            <div class="bae-adm-stat-sub">monthly + lifetime</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-blue">
                            <div class="bae-adm-stat-label">Free</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['free']; ?></div>
                            <div class="bae-adm-stat-sub">conversion opps</div>
                        </div>
                    </div>
                    <div class="bae-adm-conv-bar">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                            <div style="font-size:13px;font-weight:600;color:var(--text);">Conversion Rate</div>
                            <div style="font-size:20px;font-weight:800;color:var(--brand-s);"><?php echo $conv; ?>%</div>
                        </div>
                        <div style="background:var(--bg-3);border-radius:999px;height:6px;overflow:hidden;">
                            <div style="background:linear-gradient(90deg,var(--brand-d),var(--brand-s));height:100%;width:<?php echo $conv_w; ?>%;border-radius:999px;"></div>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:var(--text-3);">
                            <span><?php echo $paid; ?> paying</span>
                            <span><?php echo $stats['free']; ?> free</span>
                        </div>
                    </div>
                    <div class="bae-adm-table-wrap">
                        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                            <div style="font-size:13px;font-weight:600;color:var(--text);">Recent Signups</div>
                            <a href="<?php echo esc_url($base_url . '&adm=users'); ?>" style="font-size:12px;color:var(--text-3);text-decoration:none;">View all →</a>
                        </div>
                        <table class="bae-adm-table">
                            <thead><tr><th>Business</th><th>Ticket</th><th>Plan</th><th>Assets</th><th>Joined</th></tr></thead>
                            <tbody>
                            <?php foreach (array_slice($users, 0, 8) as $u): ?>
                            <tr>
                                <td class="biz-name"><?php echo esc_html($u['business_name']); ?></td>
                                <td class="ticket-code"><?php echo esc_html($u['ticket']); ?></td>
                                <td><span class="bae-plan bae-plan-<?php echo esc_attr($u['plan'] ?? 'free'); ?>"><?php echo ucfirst($u['plan'] ?? 'free'); ?></span></td>
                                <td><?php echo (int)$u['asset_count']; ?></td>
                                <td style="color:var(--text-3);white-space:nowrap;"><?php echo date('M d', strtotime($u['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?><tr><td colspan="5" class="bae-adm-empty">No users yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($route === 'users'): ?>
                    <div class="bae-adm-toolbar">
                        <input type="text" class="bae-adm-search" id="bae-adm-search" placeholder="Search by business name or ticket..." oninput="baeAdmSearch()">
                        <select class="bae-adm-filter" id="bae-adm-plan-filter" onchange="baeAdmSearch()">
                            <option value="">All Plans</option>
                            <option value="free">Free</option>
                            <option value="starter">Starter</option>
                            <option value="pro">Pro</option>
                        </select>
                        <div style="font-size:12px;color:var(--text-3);" id="bae-adm-count"><?php echo count($users); ?> users</div>
                    </div>
                    <div class="bae-adm-table-wrap">
                        <table class="bae-adm-table">
                            <thead><tr><th>Business</th><th>Ticket</th><th>Industry</th><th>Plan</th><th>Assets</th><th>Joined</th><th>Actions</th></tr></thead>
                            <tbody id="bae-users-tbody">
                            <?php foreach ($users as $u):
                                $plan = $u['plan'] ?? 'free'; $ticket = esc_attr($u['ticket']); $ticket_js = esc_js($u['ticket']);
                            ?>
                            <tr class="bae-user-row" data-name="<?php echo esc_attr(strtolower($u['business_name'])); ?>" data-ticket="<?php echo esc_attr(strtolower($u['ticket'])); ?>" data-plan="<?php echo esc_attr($plan); ?>">
                                <td>
                                    <div class="biz-name"><?php echo esc_html($u['business_name']); ?></div>
                                    <div style="font-size:11px;color:var(--text-3);margin-top:2px;"><?php echo esc_html($u['tagline'] ?? ''); ?></div>
                                </td>
                                <td class="ticket-code"><?php echo esc_html($u['ticket']); ?></td>
                                <td style="color:var(--text-3);font-size:12px;"><?php echo esc_html($u['industry'] ?? '—'); ?></td>
                                <td>
                                    <select class="bae-plan-select" onchange="baeAdmSetPlan('<?php echo $ticket_js; ?>', this)" data-current="<?php echo esc_attr($plan); ?>">
                                        <option value="free"    <?php selected($plan,'free'); ?>>Free</option>
                                        <option value="starter" <?php selected($plan,'starter'); ?>>Starter</option>
                                        <option value="pro"     <?php selected($plan,'pro'); ?>>Pro</option>
                                    </select>
                                </td>
                                <td>
                                    <span style="font-weight:700;color:var(--text);"><?php echo (int)$u['asset_count']; ?></span>
                                    <button class="bae-adm-action-btn" style="margin-left:4px;" onclick="baeAdmToggleAssets('<?php echo $ticket_js; ?>', this)">View</button>
                                </td>
                                <td style="color:var(--text-3);white-space:nowrap;font-size:12px;"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td><button class="bae-adm-action-btn bae-adm-action-revoke" onclick="baeAdmRevoke('<?php echo $ticket_js; ?>', '<?php echo esc_js($u['business_name']); ?>')">Revoke</button></td>
                            </tr>
                            <tr id="drawer-<?php echo $ticket; ?>" style="display:none;">
                                <td colspan="7" style="padding:12px 16px;background:var(--bg-3);border-top:1px solid var(--border);">
                                    <div id="drawer-content-<?php echo $ticket; ?>" style="font-size:12px;color:var(--text-3);">Loading assets...</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?><tr><td colspan="7" class="bae-adm-empty">No users yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($route === 'tickets'): ?>
                    <div class="bae-adm-toolbar">
                        <input type="text" class="bae-adm-search" id="bae-adm-search" placeholder="Search tickets..." oninput="baeAdmSearch()">
                        <div style="font-size:12px;color:var(--text-3);" id="bae-adm-count"><?php echo count($all_tickets); ?> tickets</div>
                    </div>
                    <div class="bae-adm-table-wrap">
                        <table class="bae-adm-table">
                            <thead><tr><th>Ticket</th><th>Business</th><th>Plan</th><th>Assets</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($all_tickets as $t): $ticket_js = esc_js($t['ticket']); ?>
                            <tr class="bae-user-row" data-name="<?php echo esc_attr(strtolower($t['business_name'] ?? '')); ?>" data-ticket="<?php echo esc_attr(strtolower($t['ticket'])); ?>">
                                <td class="ticket-code"><?php echo esc_html($t['ticket']); ?></td>
                                <td class="biz-name"><?php echo esc_html($t['business_name'] ?: '—'); ?></td>
                                <td><span class="bae-plan bae-plan-<?php echo esc_attr($t['plan'] ?? 'free'); ?>"><?php echo ucfirst($t['plan'] ?? 'free'); ?></span></td>
                                <td><?php echo (int)$t['asset_count']; ?></td>
                                <td style="color:var(--text-3);font-size:12px;white-space:nowrap;"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                                <td><button class="bae-adm-action-btn bae-adm-action-revoke" onclick="baeAdmRevoke('<?php echo $ticket_js; ?>', '<?php echo esc_js($t['business_name'] ?: $t['ticket']); ?>')">Revoke</button></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($all_tickets)): ?><tr><td colspan="6" class="bae-adm-empty">No tickets yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($route === 'assets'): ?>
                    <div class="bae-adm-toolbar">
                        <input type="text" class="bae-adm-search" id="bae-adm-search" placeholder="Search assets..." oninput="baeAdmSearch()">
                        <div style="font-size:12px;color:var(--text-3);" id="bae-adm-count"><?php echo count($all_assets); ?> assets</div>
                    </div>
                    <div class="bae-adm-table-wrap">
                        <table class="bae-adm-table">
                            <thead><tr><th>Asset</th><th>Type</th><th>Business</th><th>Ticket</th><th>Generated</th></tr></thead>
                            <tbody>
                            <?php foreach ($all_assets as $a): ?>
                            <tr class="bae-user-row" data-name="<?php echo esc_attr(strtolower($a['asset_name'] . ' ' . $a['business_name'])); ?>" data-ticket="<?php echo esc_attr(strtolower($a['ticket'])); ?>">
                                <td style="font-weight:600;color:var(--text);"><?php echo esc_html($a['asset_name']); ?></td>
                                <td><span class="bae-adm-asset-pill"><?php echo esc_html($a['asset_type']); ?></span></td>
                                <td class="biz-name"><?php echo esc_html($a['business_name'] ?: '—'); ?></td>
                                <td class="ticket-code"><?php echo esc_html($a['ticket']); ?></td>
                                <td style="color:var(--text-3);font-size:12px;white-space:nowrap;"><?php echo date('M d, Y', strtotime($a['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($all_assets)): ?><tr><td colspan="5" class="bae-adm-empty">No assets yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($route === 'payments'): ?>
                    <div class="bae-adm-stats" style="grid-template-columns:repeat(3,1fr);">
                        <div class="bae-adm-stat bae-adm-stat-accent-green">
                            <div class="bae-adm-stat-label">Total Revenue</div>
                            <div class="bae-adm-stat-val">₱<?php echo number_format($revenue / 100); ?></div>
                            <div class="bae-adm-stat-sub">all time</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-purple">
                            <div class="bae-adm-stat-label">Transactions</div>
                            <div class="bae-adm-stat-val"><?php echo count($payments); ?></div>
                            <div class="bae-adm-stat-sub">successful</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-yellow">
                            <div class="bae-adm-stat-label">Avg. Value</div>
                            <div class="bae-adm-stat-val">₱<?php echo count($payments) ? number_format(($revenue / 100) / count($payments)) : 0; ?></div>
                            <div class="bae-adm-stat-sub">per transaction</div>
                        </div>
                    </div>
                    <?php if (empty($payments)): ?>
                    <div class="bae-adm-empty">No payments yet. Transactions will appear here once PayMongo is integrated.</div>
                    <?php else: ?>
                    <div class="bae-adm-table-wrap">
                        <table class="bae-adm-table">
                            <thead><tr><th>Reference</th><th>Ticket</th><th>Plan</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($payments as $pay):
                                $ptk = $wpdb->get_var($wpdb->prepare("SELECT ticket FROM {$pt} WHERE user_id = %d LIMIT 1", $pay['user_id']));
                            ?>
                            <tr>
                                <td style="font-family:monospace;font-size:11px;color:var(--text-3);"><?php echo esc_html($pay['reference']); ?></td>
                                <td class="ticket-code"><?php echo esc_html($ptk ?: '—'); ?></td>
                                <td><span class="bae-plan bae-plan-<?php echo esc_attr($pay['plan']); ?>"><?php echo ucfirst($pay['plan']); ?></span></td>
                                <td style="font-weight:700;color:var(--green);">₱<?php echo number_format($pay['amount'] / 100); ?></td>
                                <td><span style="font-size:10px;font-weight:700;color:var(--green);background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.2);border-radius:999px;padding:2px 8px;"><?php echo ucfirst($pay['status']); ?></span></td>
                                <td style="color:var(--text-3);font-size:12px;white-space:nowrap;"><?php echo date('M d, Y H:i', strtotime($pay['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                <?php elseif ($route === 'plans'): ?>
                    <div class="bae-adm-stats" style="grid-template-columns:repeat(3,1fr);">
                        <div class="bae-adm-stat bae-adm-stat-accent-blue">
                            <div class="bae-adm-stat-label">Free</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['free']; ?></div>
                            <div class="bae-adm-stat-sub"><?php echo $stats['total'] ? round(($stats['free']/$stats['total'])*100) : 0; ?>% of users</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-green">
                            <div class="bae-adm-stat-label">Starter</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['starter']; ?></div>
                            <div class="bae-adm-stat-sub"><?php echo $stats['total'] ? round(($stats['starter']/$stats['total'])*100) : 0; ?>% of users</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-purple">
                            <div class="bae-adm-stat-label">Pro</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['pro']; ?></div>
                            <div class="bae-adm-stat-sub"><?php echo $stats['total'] ? round(($stats['pro']/$stats['total'])*100) : 0; ?>% of users</div>
                        </div>
                    </div>
                    <div class="bae-adm-table-wrap">
                        <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                            <div style="font-size:13px;font-weight:600;color:var(--text);">Plan Distribution</div>
                        </div>
                        <table class="bae-adm-table">
                            <thead><tr><th>Plan</th><th>Users</th><th>Share</th><th>Est. Monthly Revenue</th></tr></thead>
                            <tbody>
                            <?php
                            $plan_prices = ['free' => 0, 'starter' => 299, 'pro' => 599];
                            foreach ($plan_prices as $plan_name => $price):
                                $count = $stats[$plan_name] ?? 0;
                                $share = $stats['total'] ? round(($count/$stats['total'])*100, 1) : 0;
                                $mrr   = $count * $price;
                            ?>
                            <tr>
                                <td><span class="bae-plan bae-plan-<?php echo $plan_name; ?>"><?php echo ucfirst($plan_name); ?></span></td>
                                <td style="font-weight:700;color:var(--text);"><?php echo $count; ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="flex:1;height:4px;background:var(--bg-3);border-radius:999px;overflow:hidden;min-width:60px;">
                                            <div style="height:100%;width:<?php echo $share; ?>%;background:var(--brand);border-radius:999px;"></div>
                                        </div>
                                        <span style="font-size:12px;color:var(--text-3);"><?php echo $share; ?>%</span>
                                    </div>
                                </td>
                                <td style="color:var(--green);font-weight:600;">₱<?php echo number_format($mrr); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($route === 'analytics'): ?>
                    <div class="bae-adm-stats">
                        <div class="bae-adm-stat bae-adm-stat-accent-purple">
                            <div class="bae-adm-stat-label">New Today</div>
                            <div class="bae-adm-stat-val"><?php echo (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE DATE(created_at) = CURDATE() AND business_name != ''"); ?></div>
                            <div class="bae-adm-stat-sub">signups</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-blue">
                            <div class="bae-adm-stat-label">This Week</div>
                            <div class="bae-adm-stat-val"><?php echo (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND business_name != ''"); ?></div>
                            <div class="bae-adm-stat-sub">new users</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-green">
                            <div class="bae-adm-stat-label">This Month</div>
                            <div class="bae-adm-stat-val"><?php echo (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) AND business_name != ''"); ?></div>
                            <div class="bae-adm-stat-sub">new users</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-yellow">
                            <div class="bae-adm-stat-label">Conversion</div>
                            <div class="bae-adm-stat-val"><?php echo $conv; ?>%</div>
                            <div class="bae-adm-stat-sub">free → paid</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-pink">
                            <div class="bae-adm-stat-label">Avg Assets</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['total'] ? round($stats['assets'] / $stats['total'], 1) : 0; ?></div>
                            <div class="bae-adm-stat-sub">per user</div>
                        </div>
                    </div>
                    <?php
                    // Top industries
                    $industries = $wpdb->get_results("SELECT industry, COUNT(*) as cnt FROM {$pt} WHERE industry != '' GROUP BY industry ORDER BY cnt DESC LIMIT 8", ARRAY_A) ?: [];
                    $top_industry_max = !empty($industries) ? max(array_column($industries, 'cnt')) : 1;
                    ?>
                    <div class="bae-adm-form-section">
                        <h3>Top Industries</h3>
                        <p>Distribution of user industries</p>
                        <?php foreach ($industries as $ind): $pct = round(($ind['cnt'] / max($top_industry_max, 1)) * 100); ?>
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                            <div style="font-size:12px;color:var(--text-2);width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html(ucfirst($ind['industry'])); ?></div>
                            <div style="flex:1;height:6px;background:var(--bg-3);border-radius:999px;overflow:hidden;">
                                <div style="height:100%;width:<?php echo $pct; ?>%;background:linear-gradient(90deg,var(--brand-d),var(--brand-s));border-radius:999px;"></div>
                            </div>
                            <div style="font-size:12px;color:var(--text-3);width:20px;text-align:right;"><?php echo $ind['cnt']; ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($industries)): ?><div style="color:var(--text-3);font-size:13px;">No data yet.</div><?php endif; ?>
                    </div>

                <?php elseif ($route === 'logs'): ?>
                    <?php
                    $recent = $wpdb->get_results(
                        "SELECT business_name, ticket, plan, created_at, 'signup' as event FROM {$pt} WHERE business_name != ''
                         UNION ALL
                         SELECT p.business_name, a.ticket, p.plan, a.created_at, CONCAT('asset:', a.asset_type) as event
                         FROM {$at} a LEFT JOIN {$pt} p ON p.ticket = a.ticket
                         WHERE a.is_generated = 1
                         ORDER BY created_at DESC LIMIT 50",
                        ARRAY_A
                    ) ?: [];
                    ?>
                    <div class="bae-adm-table-wrap">
                        <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                            <div style="font-size:13px;font-weight:600;color:var(--text);">Activity Log</div>
                        </div>
                        <table class="bae-adm-table">
                            <thead><tr><th>Event</th><th>Business</th><th>Ticket</th><th>Plan</th><th>Time</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent as $log):
                                $is_signup = $log['event'] === 'signup';
                                $color = $is_signup ? 'var(--green)' : 'var(--brand-s)';
                                $label = $is_signup ? 'Signup' : str_replace('asset:', '', $log['event']);
                            ?>
                            <tr>
                                <td><span style="font-size:11px;font-weight:700;color:<?php echo $color ?>;background:rgba(139,92,246,.08);border-radius:6px;padding:3px 8px;"><?php echo esc_html(ucfirst($label)); ?></span></td>
                                <td class="biz-name"><?php echo esc_html($log['business_name'] ?: '—'); ?></td>
                                <td class="ticket-code"><?php echo esc_html($log['ticket']); ?></td>
                                <td><span class="bae-plan bae-plan-<?php echo esc_attr($log['plan'] ?? 'free'); ?>"><?php echo ucfirst($log['plan'] ?? 'free'); ?></span></td>
                                <td style="color:var(--text-3);font-size:12px;white-space:nowrap;"><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent)): ?><tr><td colspan="5" class="bae-adm-empty">No activity yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($route === 'settings'): ?>
                    <div class="bae-adm-form-section">
                        <h3>PayMongo Integration</h3>
                        <p>Update your PayMongo API keys. Changes take effect immediately.</p>
                        <div class="bae-adm-field">
                            <label>Public Key</label>
                            <input type="text" id="bae-pm-public" value="<?php echo esc_attr($paymongo_public); ?>" placeholder="pk_live_...">
                        </div>
                        <div class="bae-adm-field">
                            <label>Secret Key</label>
                            <input type="password" id="bae-pm-secret" value="<?php echo esc_attr($paymongo_secret); ?>" placeholder="sk_live_...">
                        </div>
                        <button class="bae-adm-save-btn" id="bae-pm-save" onclick="baeAdmSavePaymongo()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save Keys
                        </button>
                    </div>
                    <div class="bae-adm-form-section">
                        <h3>Admin Code</h3>
                        <p>Current admin ticket is set in <code style="background:var(--bg-3);padding:2px 6px;border-radius:5px;font-size:11px;">admin.php</code> as <code style="background:var(--bg-3);padding:2px 6px;border-radius:5px;font-size:11px;">BAE_ADMIN_SECRET</code>. Change it there to rotate your admin access code.</p>
                    </div>

                <?php elseif ($route === 'security'): ?>
                    <?php
                    $total_users_all = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt}");
                    $empty_profiles  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE business_name = ''");
                    ?>
                    <div class="bae-adm-stats" style="grid-template-columns:repeat(3,1fr);">
                        <div class="bae-adm-stat bae-adm-stat-accent-purple">
                            <div class="bae-adm-stat-label">Total Tickets</div>
                            <div class="bae-adm-stat-val"><?php echo $total_users_all; ?></div>
                            <div class="bae-adm-stat-sub">issued</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-yellow">
                            <div class="bae-adm-stat-label">Incomplete</div>
                            <div class="bae-adm-stat-val"><?php echo $empty_profiles; ?></div>
                            <div class="bae-adm-stat-sub">no profile setup</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-green">
                            <div class="bae-adm-stat-label">Active</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['total']; ?></div>
                            <div class="bae-adm-stat-sub">with profiles</div>
                        </div>
                    </div>
                    <div class="bae-adm-form-section">
                        <h3>Cleanup</h3>
                        <p>Remove tickets that were issued but never used to set up a brand profile.</p>
                        <button class="bae-adm-save-btn" style="background:linear-gradient(135deg,#be123c,var(--red));" onclick="baeAdmCleanup()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                            Purge <?php echo $empty_profiles; ?> Empty Tickets
                        </button>
                    </div>

                <?php endif; ?>

                </div><!-- /body -->
            </div><!-- /main -->
        </div><!-- /layout -->
    </div><!-- /bae-adm -->

    <div id="bae-adm-toast"></div>

    <script>
    var _baeAdmAj    = '<?php echo esc_js($aj); ?>';
    var _baeAdmNonce = '<?php echo esc_js($nonce); ?>';
    var _baeAdmConfirmCb = null;

    // ── Confirm modal
    function baeAdmConfirm(title, msg, cb) {
        document.getElementById('bae-adm-confirm-title').textContent = title;
        document.getElementById('bae-adm-confirm-msg').textContent   = msg;
        _baeAdmConfirmCb = cb;
        var el = document.getElementById('bae-adm-confirm');
        el.classList.add('open');
        if (window.gsap) gsap.fromTo('.bae-adm-confirm-modal', {opacity:0,y:16}, {opacity:1,y:0,duration:.25,ease:'power3.out'});
    }
    document.getElementById('bae-adm-confirm-ok').addEventListener('click', function() {
        document.getElementById('bae-adm-confirm').classList.remove('open');
        if (typeof _baeAdmConfirmCb === 'function') _baeAdmConfirmCb();
    });
    document.getElementById('bae-adm-confirm-cancel').addEventListener('click', function() {
        document.getElementById('bae-adm-confirm').classList.remove('open');
        _baeAdmConfirmCb = null;
    });

    // ── Toast
    function baeAdmToast(msg, type) {
        var t = document.getElementById('bae-adm-toast');
        t.textContent = msg; t.className = type || ''; t.style.display = 'block';
        if (window.gsap) gsap.fromTo(t, {opacity:0,y:10}, {opacity:1,y:0,duration:.3});
        clearTimeout(t._timer);
        t._timer = setTimeout(function() {
            if (window.gsap) gsap.to(t, {opacity:0,y:10,duration:.25,onComplete:function(){t.style.display='none';}});
            else t.style.display = 'none';
        }, 2800);
    }

    // ── Logout
    function baeAdmLogout() {
        baeAdmConfirm('Sign Out', 'Are you sure you want to sign out of the admin panel?', function() {
            var fd = new FormData(); fd.append('action','bae_admin_logout');
            fetch(_baeAdmAj, {method:'POST',body:fd}).finally(function() {
                document.cookie = '<?php echo BAE_ADMIN_COOKIE; ?>=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                window.location.href = window.location.pathname;
            });
        });
    }

    // ── Set plan
    function baeAdmSetPlan(ticket, sel) {
        var plan = sel.value, prev = sel.dataset.current;
        var fd = new FormData();
        fd.append('action','bae_admin_set_plan'); fd.append('nonce',_baeAdmNonce);
        fd.append('ticket',ticket); fd.append('plan',plan);
        sel.disabled = true;
        fetch(_baeAdmAj,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j) {
            sel.disabled = false;
            if (j.success) { sel.dataset.current = plan; var row = sel.closest('tr'); if (row) row.dataset.plan = plan; baeAdmToast('Plan updated to ' + plan, 'success'); }
            else { sel.value = prev; baeAdmToast((j.data && j.data.message) || 'Failed.', 'error'); }
        });
    }

    // ── Revoke
    function baeAdmRevoke(ticket, name) {
        baeAdmConfirm('Revoke Ticket', 'Permanently delete "' + name + '" and all their assets? This cannot be undone.', function() {
            var fd = new FormData();
            fd.append('action','bae_admin_revoke_ticket'); fd.append('nonce',_baeAdmNonce); fd.append('ticket',ticket);
            fetch(_baeAdmAj,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j) {
                if (j.success) {
                    document.querySelectorAll('[data-ticket="'+ticket.toLowerCase()+'"]').forEach(function(r){r.remove();});
                    var d = document.getElementById('drawer-'+ticket); if (d) d.remove();
                    baeAdmToast('Ticket revoked.', 'success');
                } else { baeAdmToast((j.data && j.data.message) || 'Failed.', 'error'); }
            });
        });
    }

    // ── Cleanup empty tickets
    function baeAdmCleanup() {
        baeAdmConfirm('Purge Empty Tickets', 'This will permanently delete all tickets that were never used to create a brand profile. Are you sure?', function() {
            var fd = new FormData();
            fd.append('action','bae_admin_purge_empty'); fd.append('nonce',_baeAdmNonce);
            fetch(_baeAdmAj,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j) {
                baeAdmToast(j.success ? 'Empty tickets purged.' : 'Failed.', j.success ? 'success' : 'error');
                if (j.success) setTimeout(function(){ window.location.reload(); }, 1200);
            });
        });
    }

    // ── Save PayMongo
    function baeAdmSavePaymongo() {
        var pub = document.getElementById('bae-pm-public').value.trim();
        var sec = document.getElementById('bae-pm-secret').value.trim();
        var btn = document.getElementById('bae-pm-save');
        btn.disabled = true; btn.textContent = 'Saving...';
        var fd = new FormData();
        fd.append('action','bae_admin_save_paymongo'); fd.append('nonce',_baeAdmNonce);
        fd.append('public_key', pub); fd.append('secret_key', sec);
        fetch(_baeAdmAj,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j) {
            btn.disabled = false; btn.textContent = 'Save Keys';
            baeAdmToast(j.success ? 'PayMongo keys saved.' : 'Failed to save.', j.success ? 'success' : 'error');
        });
    }

    // ── Asset drawer
    var _drawerCache = {};
    function baeAdmToggleAssets(ticket, btn) {
        var drawer = document.getElementById('drawer-'+ticket);
        var content = document.getElementById('drawer-content-'+ticket);
        if (!drawer) return;
        if (drawer.style.display !== 'none') { drawer.style.display = 'none'; btn.textContent = 'View'; return; }
        drawer.style.display = ''; btn.textContent = 'Hide';
        if (_drawerCache[ticket]) { content.innerHTML = _drawerCache[ticket]; return; }
        fetch(_baeAdmAj+'?action=bae_admin_get_assets&ticket='+encodeURIComponent(ticket))
        .then(function(r){return r.json();}).then(function(j) {
            if (j.success && j.data.assets.length) {
                var html = '<div style="display:flex;flex-wrap:wrap;gap:4px;">';
                j.data.assets.forEach(function(a) { html += '<span class="bae-adm-asset-pill">'+( a.asset_name||a.asset_type)+'</span>'; });
                html += '</div>'; _drawerCache[ticket] = html; content.innerHTML = html;
            } else { content.innerHTML = '<span style="color:var(--text-3);">No assets generated yet.</span>'; }
        });
    }

    // ── Table search/filter
    function baeAdmSearch() {
        var searchEl = document.getElementById('bae-adm-search');
        var planEl   = document.getElementById('bae-adm-plan-filter');
        var q    = searchEl ? searchEl.value.toLowerCase().trim() : '';
        var plan = planEl   ? planEl.value.toLowerCase() : '';
        var rows = document.querySelectorAll('.bae-user-row');
        var count = 0;
        rows.forEach(function(row) {
            var show = (!q || (row.dataset.name||'').includes(q) || (row.dataset.ticket||'').includes(q)) &&
                       (!plan || row.dataset.plan === plan);
            row.style.display = show ? '' : 'none';
            var drawer = document.getElementById('drawer-'+(row.dataset.ticket||''));
            if (drawer && !show) drawer.style.display = 'none';
            if (show) count++;
        });
        var el = document.getElementById('bae-adm-count');
        if (el) el.textContent = count + ' result' + (count !== 1 ? 's' : '');
    }

    // ── Sidebar nav search
    function baeAdmNavSearch(val) {
        val = val.toLowerCase().trim();
        document.querySelectorAll('.bae-adm-nav-item').forEach(function(item) {
            var label = item.dataset.navLabel || '';
            item.style.display = (!val || label.includes(val)) ? '' : 'none';
        });
        document.querySelectorAll('.bae-adm-nav-label').forEach(function(label) {
            var group = label.dataset.navGroup || '';
            var next = label.nextElementSibling;
            var hasVisible = false;
            while (next && next.classList.contains('bae-adm-nav-item')) {
                if (next.style.display !== 'none') hasVisible = true;
                next = next.nextElementSibling;
            }
            label.style.display = (!val || hasVisible) ? '' : 'none';
        });
    }

    // ── Init
    document.addEventListener('DOMContentLoaded', function() {
        if (window.gsap) gsap.fromTo('.bae-adm-stat', {opacity:0,y:12}, {opacity:1,y:0,duration:.4,stagger:.05,ease:'power2.out',delay:.1});
    });
    </script>
    <?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────────
// AJAX: Save PayMongo keys
// ─────────────────────────────────────────────────────────────────
add_action('wp_ajax_bae_admin_save_paymongo',        'bae_ajax_admin_save_paymongo');
add_action('wp_ajax_nopriv_bae_admin_save_paymongo', 'bae_ajax_admin_save_paymongo');
function bae_ajax_admin_save_paymongo() {
    bae_admin_auth_check();
    check_ajax_referer(BAE_ADMIN_NONCE, 'nonce');
    $public = sanitize_text_field($_POST['public_key'] ?? '');
    $secret = sanitize_text_field($_POST['secret_key'] ?? '');
    update_option('bae_paymongo_public', $public);
    update_option('bae_paymongo_secret', $secret);
    wp_send_json_success(['message' => 'PayMongo keys saved.']);
}

// ─────────────────────────────────────────────────────────────────
// AJAX: Purge empty tickets (security cleanup)
// ─────────────────────────────────────────────────────────────────
add_action('wp_ajax_bae_admin_purge_empty',        'bae_ajax_admin_purge_empty');
add_action('wp_ajax_nopriv_bae_admin_purge_empty', 'bae_ajax_admin_purge_empty');
function bae_ajax_admin_purge_empty() {
    bae_admin_auth_check();
    check_ajax_referer(BAE_ADMIN_NONCE, 'nonce');
    global $wpdb;
    $deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}bae_profiles WHERE business_name = ''");
    wp_send_json_success(['message' => "Purged {$deleted} empty tickets.", 'count' => $deleted]);
}
