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

    // 7-day signup sparkline data
    $sparkline = [];
    for ($d = 6; $d >= 0; $d--) {
        $date = date('Y-m-d', strtotime("-{$d} days"));
        $cnt = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$pt} WHERE DATE(created_at) = %s AND business_name != ''", $date
        ));
        $sparkline[] = ['date' => date('D', strtotime($date)), 'count' => $cnt];
    }
    $spark_max = max(array_column($sparkline, 'count') ?: [1]);

    // Asset type breakdown
    $asset_types_db = $wpdb->get_results(
        "SELECT asset_type, COUNT(*) as cnt FROM {$at} WHERE is_generated = 1 GROUP BY asset_type ORDER BY cnt DESC",
        ARRAY_A
    ) ?: [];
    $asset_type_max = !empty($asset_types_db) ? max(array_column($asset_types_db, 'cnt')) : 1;

    // Top 5 most active users
    $top_users = $wpdb->get_results(
        "SELECT p.business_name, p.ticket, p.plan, p.industry,
            (SELECT COUNT(*) FROM {$at} a WHERE a.ticket = p.ticket AND a.is_generated = 1) as asset_count
         FROM {$pt} p WHERE p.business_name != ''
         ORDER BY asset_count DESC LIMIT 5",
        ARRAY_A
    ) ?: [];

    // New this week / this month
    $new_week  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND business_name != ''");
    $new_month = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) AND business_name != ''");
    $new_today = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE DATE(created_at) = CURDATE() AND business_name != ''");

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

    /* ── Welcome Banner ── */
    .bae-adm-welcome {
        background: linear-gradient(135deg, rgba(109,40,217,.12), rgba(236,72,153,.06));
        border: 1px solid rgba(139,92,246,.18);
        border-radius: 16px; padding: 24px 28px; margin-bottom: 20px;
        display: flex; justify-content: space-between; align-items: center; gap: 20px;
    }
    .bae-adm-welcome-text h2 { font-family: 'Instrument Serif', serif; font-size: 22px; font-style: italic; color: var(--text); margin: 0 0 4px; }
    .bae-adm-welcome-text p { font-size: 13px; color: var(--text-2); margin: 0; }
    .bae-adm-quick-actions { display: flex; gap: 8px; flex-shrink: 0; }
    .bae-adm-quick-btn {
        padding: 8px 16px; border-radius: 9px; font-size: 12px; font-weight: 600;
        font-family: 'Geist', sans-serif; cursor: pointer; border: 1px solid var(--border-2);
        background: var(--surface); color: var(--text-2); transition: all .15s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .bae-adm-quick-btn:hover { color: var(--text); border-color: var(--brand-s); background: rgba(139,92,246,.06); }
    .bae-adm-quick-btn svg { width: 12px; height: 12px; }

    /* ── Donut Chart ── */
    .bae-adm-donut-wrap { display: flex; align-items: center; gap: 24px; padding: 20px; background: var(--bg-2); border: 1px solid var(--border); border-radius: 14px; margin-bottom: 16px; }
    .bae-adm-donut { width: 100px; height: 100px; border-radius: 50%; position: relative; flex-shrink: 0; }
    .bae-adm-donut-center { position: absolute; inset: 20px; background: var(--bg-2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; color: var(--text); }
    .bae-adm-donut-legend { display: flex; flex-direction: column; gap: 8px; flex: 1; }
    .bae-adm-donut-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-2); }
    .bae-adm-donut-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

    /* ── Sparkline ── */
    .bae-adm-spark { display: flex; gap: 4px; align-items: flex-end; height: 60px; padding: 20px 22px; background: var(--bg-2); border: 1px solid var(--border); border-radius: 14px; margin-bottom: 16px; }
    .bae-adm-spark-bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; height: 100%; justify-content: flex-end; }
    .bae-adm-spark-bar { width: 100%; min-height: 3px; background: linear-gradient(180deg, var(--brand-s), var(--brand-d)); border-radius: 4px 4px 0 0; transition: height .3s; }
    .bae-adm-spark-label { font-size: 9px; color: var(--text-3); font-weight: 600; }

    /* ── Timeline ── */
    .bae-adm-timeline { position: relative; padding-left: 28px; }
    .bae-adm-timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: var(--border); }
    .bae-adm-tl-group { font-size: 10px; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: .1em; margin: 18px 0 10px; position: relative; }
    .bae-adm-tl-item {
        position: relative; padding: 10px 14px; margin-bottom: 6px;
        background: var(--bg-2); border: 1px solid var(--border); border-radius: 10px;
        font-size: 13px; color: var(--text-2); display: flex; align-items: center; gap: 10px;
    }
    .bae-adm-tl-dot { position: absolute; left: -24px; top: 14px; width: 10px; height: 10px; border-radius: 50%; border: 2px solid var(--bg); }
    .bae-adm-tl-dot-signup { background: var(--green); }
    .bae-adm-tl-dot-asset { background: var(--brand-s); }
    .bae-adm-tl-dot-payment { background: var(--yellow); }
    .bae-adm-tl-time { margin-left: auto; font-size: 11px; color: var(--text-3); white-space: nowrap; }
    .bae-adm-tl-tag { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px; white-space: nowrap; }
    .bae-adm-tl-filters { display: flex; gap: 6px; margin-bottom: 16px; }
    .bae-adm-tl-filter-btn {
        padding: 6px 14px; border-radius: 8px; font-size: 11px; font-weight: 600;
        font-family: 'Geist', sans-serif; cursor: pointer; border: 1px solid var(--border);
        background: var(--bg-2); color: var(--text-3); transition: all .15s;
    }
    .bae-adm-tl-filter-btn.active { border-color: var(--brand-s); color: var(--brand-s); background: rgba(139,92,246,.06); }
    .bae-adm-tl-filter-btn:hover { color: var(--text-2); }

    /* ── Asset Type Cards ── */
    .bae-adm-asset-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
    @media (max-width: 900px) { .bae-adm-asset-grid { grid-template-columns: repeat(2, 1fr); } }
    .bae-adm-asset-type-card {
        background: var(--bg-2); border: 1px solid var(--border); border-radius: 14px;
        padding: 20px; display: flex; align-items: center; gap: 16px; transition: all .2s;
        position: relative; overflow: hidden;
    }
    .bae-adm-asset-type-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, var(--brand-d), var(--brand-s)); opacity: 0; transition: opacity .2s;
    }
    .bae-adm-asset-type-card:hover { border-color: rgba(139,92,246,.25); transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.15); }
    .bae-adm-asset-type-card:hover::before { opacity: 1; }
    .bae-adm-asset-icon-wrap {
        width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: rgba(139,92,246,.1); color: var(--brand-s);
    }
    .bae-adm-asset-icon-wrap svg { width: 20px; height: 20px; }
    .bae-adm-asset-type-info { flex: 1; min-width: 0; }
    .bae-adm-asset-type-name { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
    .bae-adm-asset-type-count { font-size: 22px; font-weight: 800; color: var(--brand-s); }

    /* ── User Profile Drawer ── */
    .bae-adm-profile-drawer { padding: 16px 20px; background: var(--bg-3); border-top: 1px solid var(--border); }
    .bae-adm-profile-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
    .bae-adm-profile-section h4 { font-size: 10px; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; }
    .bae-adm-color-swatches { display: flex; gap: 6px; }
    .bae-adm-color-swatch { width: 28px; height: 28px; border-radius: 7px; border: 1px solid rgba(255,255,255,.1); }
    .bae-adm-profile-info { font-size: 12px; color: var(--text-2); line-height: 1.8; }
    .bae-adm-profile-info strong { color: var(--text); }

    /* ── Plan Cards ── */
    .bae-adm-plan-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
    .bae-adm-plan-card {
        background: var(--bg-2); border: 1px solid var(--border); border-radius: 14px;
        padding: 22px; text-align: center; transition: all .15s; position: relative;
    }
    .bae-adm-plan-card:hover { border-color: var(--border-2); }
    .bae-adm-plan-card.popular { border-color: rgba(139,92,246,.3); }
    .bae-adm-plan-card.popular::before {
        content: '★ POPULAR'; position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
        background: var(--brand); color: white; font-size: 9px; font-weight: 800;
        padding: 2px 10px; border-radius: 999px; letter-spacing: .08em;
    }
    .bae-adm-plan-card h3 { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
    .bae-adm-plan-price { font-size: 28px; font-weight: 800; color: var(--brand-s); line-height: 1; margin-bottom: 4px; }
    .bae-adm-plan-price span { font-size: 13px; font-weight: 400; color: var(--text-3); }
    .bae-adm-plan-users { font-size: 12px; color: var(--text-3); margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); }
    .bae-adm-plan-users strong { color: var(--text); font-size: 20px; }

    /* ── System Info ── */
    .bae-adm-sysinfo { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
    .bae-adm-sysinfo-item {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 16px; background: var(--bg-2); border: 1px solid var(--border);
        border-radius: 10px; font-size: 12px; color: var(--text-2);
    }
    .bae-adm-sysinfo-item strong { color: var(--text); margin-right: auto; }
    .bae-adm-sysinfo-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .bae-adm-sysinfo-dot.ok { background: var(--green); }
    .bae-adm-sysinfo-dot.warn { background: var(--yellow); }
    .bae-adm-sysinfo-dot.err { background: var(--red); }

    /* ── Analytics Bar Chart ── */
    .bae-adm-bar-chart { display: flex; flex-direction: column; gap: 8px; }
    .bae-adm-bar-row { display: flex; align-items: center; gap: 10px; }
    .bae-adm-bar-label { font-size: 12px; color: var(--text-2); width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .bae-adm-bar-track { flex: 1; height: 8px; background: var(--bg-3); border-radius: 999px; overflow: hidden; }
    .bae-adm-bar-fill { height: 100%; border-radius: 999px; transition: width .4s; }
    .bae-adm-bar-count { font-size: 12px; font-weight: 700; color: var(--text-3); width: 30px; text-align: right; }

    /* ── Copy btn ── */
    .bae-adm-copy-btn {
        background: none; border: 1px solid var(--border); border-radius: 5px;
        padding: 2px 6px; font-size: 10px; color: var(--text-3); cursor: pointer;
        font-family: 'Geist', sans-serif; transition: all .15s;
    }
    .bae-adm-copy-btn:hover { color: var(--brand-s); border-color: var(--brand-s); }

    /* ── Section Header ── */
    .bae-adm-section-hdr { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .bae-adm-section-title { font-size: 14px; font-weight: 700; color: var(--text); }
    .bae-adm-section-sub { font-size: 12px; color: var(--text-3); }

    /* ── Metric Pill ── */
    .bae-adm-metric-pills { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
    .bae-adm-metric-pill {
        display: flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 600;
        background: var(--bg-2); border: 1px solid var(--border); color: var(--text-2);
    }
    .bae-adm-metric-pill strong { color: var(--text); }

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

                <?php if ($route === 'overview'):
                    $hour = (int)date('H');
                    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
                    $free_pct = $stats['total'] ? round(($stats['free']/$stats['total'])*100) : 0;
                    $starter_pct = $stats['total'] ? round(($stats['starter']/$stats['total'])*100) : 0;
                    $pro_pct = $stats['total'] ? round(($stats['pro']/$stats['total'])*100) : 0;
                    $donut_deg1 = round($free_pct * 3.6);
                    $donut_deg2 = $donut_deg1 + round($starter_pct * 3.6);
                ?>
                    <!-- Welcome Banner -->
                    <div class="bae-adm-welcome">
                        <div class="bae-adm-welcome-text">
                            <h2><?php echo $greeting; ?>, Admin</h2>
                            <p><?php echo $stats['total']; ?> brands registered · <?php echo $new_today; ?> new today · <?php echo $stats['assets']; ?> assets generated</p>
                        </div>
                        <div class="bae-adm-quick-actions">
                            <a href="<?php echo esc_url($base_url . '&adm=users'); ?>" class="bae-adm-quick-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                Users
                            </a>
                            <a href="<?php echo esc_url($base_url . '&adm=analytics'); ?>" class="bae-adm-quick-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                                Analytics
                            </a>
                            <a href="<?php echo esc_url($base_url . '&adm=settings'); ?>" class="bae-adm-quick-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09"/></svg>
                                Settings
                            </a>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="bae-adm-stats">
                        <div class="bae-adm-stat bae-adm-stat-accent-purple">
                            <div class="bae-adm-stat-label">Total Users</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['total']; ?></div>
                            <div class="bae-adm-stat-sub"><?php echo $new_week; ?> this week</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-green">
                            <div class="bae-adm-stat-label">Assets</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['assets']; ?></div>
                            <div class="bae-adm-stat-sub"><?php echo $stats['total'] ? round($stats['assets'] / $stats['total'], 1) : 0; ?> avg/user</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-yellow">
                            <div class="bae-adm-stat-label">Revenue</div>
                            <div class="bae-adm-stat-val">₱<?php echo number_format($revenue / 100); ?></div>
                            <div class="bae-adm-stat-sub"><?php echo $conv; ?>% conversion</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-pink">
                            <div class="bae-adm-stat-label">Paid Users</div>
                            <div class="bae-adm-stat-val"><?php echo $paid; ?></div>
                            <div class="bae-adm-stat-sub"><?php echo $stats['starter']; ?> starter · <?php echo $stats['pro']; ?> pro</div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                        <!-- Donut Chart -->
                        <div class="bae-adm-donut-wrap">
                            <div class="bae-adm-donut" style="background:conic-gradient(#60a5fa 0deg <?php echo $donut_deg1; ?>deg, var(--green) <?php echo $donut_deg1; ?>deg <?php echo $donut_deg2; ?>deg, var(--brand-s) <?php echo $donut_deg2; ?>deg 360deg);">
                                <div class="bae-adm-donut-center"><?php echo $stats['total']; ?></div>
                            </div>
                            <div class="bae-adm-donut-legend">
                                <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:4px;">Plan Distribution</div>
                                <div class="bae-adm-donut-item"><div class="bae-adm-donut-dot" style="background:#60a5fa;"></div> Free — <?php echo $stats['free']; ?> (<?php echo $free_pct; ?>%)</div>
                                <div class="bae-adm-donut-item"><div class="bae-adm-donut-dot" style="background:var(--green);"></div> Starter — <?php echo $stats['starter']; ?> (<?php echo $starter_pct; ?>%)</div>
                                <div class="bae-adm-donut-item"><div class="bae-adm-donut-dot" style="background:var(--brand-s);"></div> Pro — <?php echo $stats['pro']; ?> (<?php echo $pro_pct; ?>%)</div>
                            </div>
                        </div>
                        <!-- 7-Day Sparkline -->
                        <div style="background:var(--bg-2);border:1px solid var(--border);border-radius:14px;padding:16px 20px;">
                            <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:12px;">Signups — Last 7 Days</div>
                            <div class="bae-adm-spark" style="border:none;padding:0;background:none;margin:0;">
                                <?php foreach ($sparkline as $s): $h = $spark_max ? round(($s['count']/$spark_max)*100) : 5; ?>
                                <div class="bae-adm-spark-bar-wrap">
                                    <div style="font-size:10px;font-weight:700;color:var(--text-2);"><?php echo $s['count']; ?></div>
                                    <div class="bae-adm-spark-bar" style="height:<?php echo max($h, 5); ?>%;"></div>
                                    <div class="bae-adm-spark-label"><?php echo $s['date']; ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Signups -->
                    <div class="bae-adm-table-wrap">
                        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                            <div style="font-size:13px;font-weight:600;color:var(--text);">Recent Signups</div>
                            <a href="<?php echo esc_url($base_url . '&adm=users'); ?>" style="font-size:12px;color:var(--text-3);text-decoration:none;">View all →</a>
                        </div>
                        <table class="bae-adm-table">
                            <thead><tr><th>Business</th><th>Ticket</th><th>Plan</th><th>Assets</th><th>Joined</th></tr></thead>
                            <tbody>
                            <?php foreach (array_slice($users, 0, 5) as $u): ?>
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
                    <!-- Plan metric pills -->
                    <div class="bae-adm-metric-pills">
                        <div class="bae-adm-metric-pill">Total: <strong><?php echo count($users); ?></strong></div>
                        <div class="bae-adm-metric-pill" style="border-color:rgba(96,165,250,.3);">Free: <strong><?php echo $stats['free']; ?></strong></div>
                        <div class="bae-adm-metric-pill" style="border-color:rgba(52,211,153,.3);">Starter: <strong><?php echo $stats['starter']; ?></strong></div>
                        <div class="bae-adm-metric-pill" style="border-color:rgba(139,92,246,.3);">Pro: <strong><?php echo $stats['pro']; ?></strong></div>
                    </div>
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
                                <td>
                                    <span class="ticket-code"><?php echo esc_html($u['ticket']); ?></span>
                                    <button class="bae-adm-copy-btn" onclick="navigator.clipboard.writeText('<?php echo $ticket_js; ?>');this.textContent='✓';setTimeout(()=>this.textContent='Copy',1200);">Copy</button>
                                </td>
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
                                    <button class="bae-adm-action-btn" style="margin-left:4px;" onclick="baeAdmToggleProfile('<?php echo $ticket_js; ?>')">Details</button>
                                </td>
                                <td style="color:var(--text-3);white-space:nowrap;font-size:12px;"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td><button class="bae-adm-action-btn bae-adm-action-revoke" onclick="baeAdmRevoke('<?php echo $ticket_js; ?>', '<?php echo esc_js($u['business_name']); ?>')">Revoke</button></td>
                            </tr>
                            <tr id="drawer-<?php echo $ticket; ?>" style="display:none;">
                                <td colspan="7" class="bae-adm-profile-drawer">
                                    <div class="bae-adm-profile-grid">
                                        <div class="bae-adm-profile-section">
                                            <h4>Brand Colors</h4>
                                            <div class="bae-adm-color-swatches">
                                                <div class="bae-adm-color-swatch" style="background:<?php echo esc_attr($u['primary_color'] ?? '#1a1a2e'); ?>;" title="Primary"></div>
                                                <div class="bae-adm-color-swatch" style="background:<?php echo esc_attr($u['secondary_color'] ?? '#16213e'); ?>;" title="Secondary"></div>
                                                <div class="bae-adm-color-swatch" style="background:<?php echo esc_attr($u['accent_color'] ?? '#e94560'); ?>;" title="Accent"></div>
                                            </div>
                                            <div class="bae-adm-profile-info" style="margin-top:8px;">
                                                <strong>Logo:</strong> <?php echo esc_html(ucfirst($u['logo_style'] ?? 'wordmark')); ?><?php echo !empty($u['logo_icon']) ? ' + ' . esc_html($u['logo_icon']) : ''; ?><br>
                                                <strong>Fonts:</strong> <?php echo esc_html(($u['font_heading'] ?? 'Inter') . ' / ' . ($u['font_body'] ?? 'Inter')); ?>
                                            </div>
                                        </div>
                                        <div class="bae-adm-profile-section">
                                            <h4>Contact</h4>
                                            <div class="bae-adm-profile-info">
                                                <?php if (!empty($u['email'])): ?><strong>Email:</strong> <?php echo esc_html($u['email']); ?><br><?php endif; ?>
                                                <?php if (!empty($u['phone'])): ?><strong>Phone:</strong> <?php echo esc_html($u['phone']); ?><br><?php endif; ?>
                                                <?php if (!empty($u['website'])): ?><strong>Web:</strong> <?php echo esc_html($u['website']); ?><br><?php endif; ?>
                                                <?php if (empty($u['email']) && empty($u['phone']) && empty($u['website'])): ?>No contact info<?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="bae-adm-profile-section">
                                            <h4>Profile Details</h4>
                                            <div class="bae-adm-profile-info">
                                                <strong>Assets:</strong> <?php echo (int)$u['asset_count']; ?> generated<br>
                                                <strong>Kit:</strong> <?php echo esc_html($u['kit_visibility'] ?? 'private'); ?><br>
                                                <strong>ID:</strong> <?php echo esc_html($u['rand_id'] ?? '—'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?><tr><td colspan="7" class="bae-adm-empty">No users yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($route === 'tickets'):
                    $active_tickets = count(array_filter($all_tickets, function($t) { return !empty($t['business_name']); }));
                    $unused_tickets = count($all_tickets) - $active_tickets;
                ?>
                    <div class="bae-adm-metric-pills">
                        <div class="bae-adm-metric-pill">Total: <strong><?php echo count($all_tickets); ?></strong></div>
                        <div class="bae-adm-metric-pill" style="border-color:rgba(52,211,153,.3);">
                            <div class="bae-adm-sysinfo-dot ok"></div> Active: <strong><?php echo $active_tickets; ?></strong>
                        </div>
                        <div class="bae-adm-metric-pill" style="border-color:rgba(251,191,36,.3);">
                            <div class="bae-adm-sysinfo-dot warn"></div> Unused: <strong><?php echo $unused_tickets; ?></strong>
                        </div>
                    </div>
                    <div class="bae-adm-toolbar">
                        <input type="text" class="bae-adm-search" id="bae-adm-search" placeholder="Search tickets..." oninput="baeAdmSearch()">
                        <div style="font-size:12px;color:var(--text-3);" id="bae-adm-count"><?php echo count($all_tickets); ?> tickets</div>
                    </div>
                    <div class="bae-adm-table-wrap">
                        <table class="bae-adm-table">
                            <thead><tr><th>Status</th><th>Ticket</th><th>Business</th><th>Plan</th><th>Assets</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($all_tickets as $t): $ticket_js = esc_js($t['ticket']); $has_biz = !empty($t['business_name']); ?>
                            <tr class="bae-user-row" data-name="<?php echo esc_attr(strtolower($t['business_name'] ?? '')); ?>" data-ticket="<?php echo esc_attr(strtolower($t['ticket'])); ?>">
                                <td>
                                    <div class="bae-adm-sysinfo-dot <?php echo $has_biz ? 'ok' : 'warn'; ?>" style="display:inline-block;" title="<?php echo $has_biz ? 'Active' : 'Unused'; ?>"></div>
                                    <span style="font-size:10px;color:var(--text-3);margin-left:4px;"><?php echo $has_biz ? 'Active' : 'Unused'; ?></span>
                                </td>
                                <td>
                                    <span class="ticket-code"><?php echo esc_html($t['ticket']); ?></span>
                                    <button class="bae-adm-copy-btn" onclick="navigator.clipboard.writeText('<?php echo $ticket_js; ?>');this.textContent='✓';setTimeout(()=>this.textContent='Copy',1200);">Copy</button>
                                </td>
                                <td class="biz-name"><?php echo esc_html($t['business_name'] ?: '—'); ?></td>
                                <td><span class="bae-plan bae-plan-<?php echo esc_attr($t['plan'] ?? 'free'); ?>"><?php echo ucfirst($t['plan'] ?? 'free'); ?></span></td>
                                <td style="font-weight:600;"><?php echo (int)$t['asset_count']; ?></td>
                                <td style="color:var(--text-3);font-size:12px;white-space:nowrap;"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                                <td><button class="bae-adm-action-btn bae-adm-action-revoke" onclick="baeAdmRevoke('<?php echo $ticket_js; ?>', '<?php echo esc_js($t['business_name'] ?: $t['ticket']); ?>')">Revoke</button></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($all_tickets)): ?><tr><td colspan="7" class="bae-adm-empty">No tickets yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($route === 'assets'):
                    $type_svgs = [
                        'business_card'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M16 14h2M6 14h2M6 10h12"/></svg>',
                        'letterhead'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
                        'email_signature'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
                        'social_kit'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
                        'brand_guidelines' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>',
                        'custom'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>',
                    ];
                    $type_colors = [
                        'business_card' => 'rgba(139,92,246,.12)', 'letterhead' => 'rgba(52,211,153,.12)',
                        'email_signature' => 'rgba(96,165,250,.12)', 'social_kit' => 'rgba(236,72,153,.12)',
                        'brand_guidelines' => 'rgba(251,191,36,.12)', 'custom' => 'rgba(244,114,182,.12)',
                    ];
                    $type_stroke = [
                        'business_card' => 'var(--brand-s)', 'letterhead' => 'var(--green)',
                        'email_signature' => '#60a5fa', 'social_kit' => '#ec4899',
                        'brand_guidelines' => '#fbbf24', 'custom' => '#f472b6',
                    ];
                    $type_labels = ['business_card' => 'Business Card', 'letterhead' => 'Letterhead', 'email_signature' => 'Email Signature', 'social_kit' => 'Social Kit', 'brand_guidelines' => 'Brand Guidelines', 'custom' => 'Custom'];
                    $fallback_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="8" height="8" x="3" y="3" rx="1"/><rect width="8" height="5" x="13" y="3" rx="1"/><rect width="8" height="8" x="13" y="12" rx="1"/><rect width="8" height="5" x="3" y="15" rx="1"/></svg>';
                ?>
                    <!-- Asset type summary cards -->
                    <div class="bae-adm-section-hdr">
                        <div class="bae-adm-section-title">Asset Breakdown</div>
                        <div class="bae-adm-section-sub"><?php echo count($all_assets); ?> total assets generated</div>
                    </div>
                    <div class="bae-adm-asset-grid">
                        <?php foreach ($asset_types_db as $at_row):
                            $atype = $at_row['asset_type'];
                            $svg   = $type_svgs[$atype] ?? $fallback_svg;
                            $bg    = $type_colors[$atype] ?? 'rgba(139,92,246,.1)';
                            $clr   = $type_stroke[$atype] ?? 'var(--brand-s)';
                        ?>
                        <div class="bae-adm-asset-type-card">
                            <div class="bae-adm-asset-icon-wrap" style="background:<?php echo $bg; ?>;color:<?php echo $clr; ?>;">
                                <?php echo $svg; ?>
                            </div>
                            <div class="bae-adm-asset-type-info">
                                <div class="bae-adm-asset-type-name"><?php echo esc_html($type_labels[$atype] ?? ucfirst(str_replace('_', ' ', $atype))); ?></div>
                                <div style="font-size:11px;color:var(--text-3);"><?php echo (int)$at_row['cnt']; ?> generated</div>
                            </div>
                            <div class="bae-adm-asset-type-count"><?php echo (int)$at_row['cnt']; ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($asset_types_db)): ?>
                        <div class="bae-adm-asset-type-card">
                            <div class="bae-adm-asset-icon-wrap"><?php echo $fallback_svg; ?></div>
                            <div class="bae-adm-asset-type-info"><div class="bae-adm-asset-type-name">No assets yet</div><div style="font-size:11px;color:var(--text-3);">Generate from the user side</div></div>
                            <div class="bae-adm-asset-type-count">0</div>
                        </div>
                        <?php endif; ?>
                    </div>

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
                                <td><span class="bae-adm-asset-pill"><?php echo esc_html($type_labels[$a['asset_type']] ?? $a['asset_type']); ?></span></td>
                                <td class="biz-name"><?php echo esc_html($a['business_name'] ?: '—'); ?></td>
                                <td class="ticket-code"><?php echo esc_html($a['ticket']); ?></td>
                                <td style="color:var(--text-3);font-size:12px;white-space:nowrap;"><?php echo date('M d, Y', strtotime($a['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($all_assets)): ?><tr><td colspan="5" class="bae-adm-empty">No assets yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($route === 'payments'):
                    $revenue_this_month = $wpdb->get_var("SELECT SUM(amount) FROM bntm_pay_events WHERE status = 'paid' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())") ?: 0;
                    $paid_count = $wpdb->get_var("SELECT COUNT(*) FROM bntm_pay_events WHERE status = 'paid'") ?: 0;
                    $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM bntm_pay_events WHERE status = 'pending'") ?: 0;
                ?>
                    <div class="bae-adm-metric-pills">
                        <div class="bae-adm-metric-pill" style="border-color:rgba(52,211,153,.3);">
                            <div class="bae-adm-sysinfo-dot ok"></div> Paid: <strong><?php echo $paid_count; ?></strong>
                        </div>
                        <div class="bae-adm-metric-pill" style="border-color:rgba(251,191,36,.3);">
                            <div class="bae-adm-sysinfo-dot warn"></div> Pending: <strong><?php echo $pending_count; ?></strong>
                        </div>
                    </div>

                    <div class="bae-adm-stats" style="grid-template-columns:repeat(3,1fr);">
                        <div class="bae-adm-stat bae-adm-stat-accent-green">
                            <div class="bae-adm-stat-label">Total Revenue</div>
                            <div class="bae-adm-stat-val">₱<?php echo number_format($revenue / 100); ?></div>
                            <div class="bae-adm-stat-sub">all time</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-blue">
                            <div class="bae-adm-stat-label">This Month</div>
                            <div class="bae-adm-stat-val">₱<?php echo number_format($revenue_this_month / 100); ?></div>
                            <div class="bae-adm-stat-sub">current period</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-yellow">
                            <div class="bae-adm-stat-label">Avg. Value</div>
                            <div class="bae-adm-stat-val">₱<?php echo $paid_count ? number_format(($revenue / 100) / $paid_count) : 0; ?></div>
                            <div class="bae-adm-stat-sub">per transaction</div>
                        </div>
                    </div>

                    <?php if (empty($payments)): ?>
                    <div class="bae-adm-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1.5" style="width:48px;height:48px;margin-bottom:16px;">
                            <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                        </svg>
                        <div style="font-size:14px;color:var(--text);font-weight:600;margin-bottom:8px;">No transactions yet</div>
                        <div>Transactions will appear here once PayMongo is integrated and users start upgrading.</div>
                    </div>
                    <?php else: ?>
                    <div class="bae-adm-table-wrap">
                        <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                            <div style="font-size:13px;font-weight:600;color:var(--text);">Recent Transactions</div>
                        </div>
                        <table class="bae-adm-table">
                            <thead><tr><th>Reference</th><th>Ticket</th><th>Plan</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($payments as $pay):
                                $ptk = $wpdb->get_var($wpdb->prepare("SELECT ticket FROM {$pt} WHERE user_id = %d LIMIT 1", $pay['user_id']));
                                $status_color = $pay['status'] === 'paid' ? 'var(--green)' : ($pay['status'] === 'pending' ? 'var(--yellow)' : 'var(--red)');
                                $status_bg = $pay['status'] === 'paid' ? 'rgba(52,211,153,.1)' : ($pay['status'] === 'pending' ? 'rgba(251,191,36,.1)' : 'rgba(239,68,68,.1)');
                            ?>
                            <tr>
                                <td style="font-family:monospace;font-size:11px;color:var(--text-3);"><?php echo esc_html($pay['reference']); ?></td>
                                <td class="ticket-code"><?php echo esc_html($ptk ?: '—'); ?></td>
                                <td><span class="bae-plan bae-plan-<?php echo esc_attr($pay['plan']); ?>"><?php echo ucfirst($pay['plan']); ?></span></td>
                                <td style="font-weight:700;color:<?php echo $status_color; ?>;">₱<?php echo number_format($pay['amount'] / 100); ?></td>
                                <td><span style="font-size:10px;font-weight:700;color:<?php echo $status_color; ?>;background:<?php echo $status_bg; ?>;border-radius:999px;padding:3px 10px;"><?php echo ucfirst($pay['status']); ?></span></td>
                                <td style="color:var(--text-3);font-size:12px;white-space:nowrap;"><?php echo date('M d, Y H:i', strtotime($pay['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                <?php elseif ($route === 'plans'):
                    $plan_prices = ['free' => 0, 'starter' => 49, 'pro' => 99];
                ?>
                    <!-- Visual plan cards -->
                    <div class="bae-adm-plan-cards">
                        <div class="bae-adm-plan-card">
                            <h3>Free</h3>
                            <div class="bae-adm-plan-price">₱0 <span>/forever</span></div>
                            <div style="font-size:11px;color:var(--text-3);margin-top:4px;">Basic brand identity</div>
                            <div class="bae-adm-plan-users"><strong><?php echo $stats['free']; ?></strong> users</div>
                        </div>
                        <div class="bae-adm-plan-card">
                            <h3>Starter</h3>
                            <div class="bae-adm-plan-price">₱49 <span>/mo</span></div>
                            <div style="font-size:11px;color:var(--text-3);margin-top:4px;">or ₱199 lifetime</div>
                            <div class="bae-adm-plan-users"><strong><?php echo $stats['starter']; ?></strong> users</div>
                        </div>
                        <div class="bae-adm-plan-card popular">
                            <h3>Pro</h3>
                            <div class="bae-adm-plan-price">₱99 <span>/mo</span></div>
                            <div style="font-size:11px;color:var(--text-3);margin-top:4px;">Unlimited everything</div>
                            <div class="bae-adm-plan-users"><strong><?php echo $stats['pro']; ?></strong> users</div>
                        </div>
                    </div>

                    <!-- Conversion funnel -->
                    <div style="background:var(--bg-2);border:1px solid var(--border);border-radius:14px;padding:20px;margin-bottom:20px;">
                        <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:14px;">Conversion Funnel</div>
                        <?php
                        $funnel = [
                            ['label' => 'Free Users', 'count' => $stats['free'], 'color' => '#60a5fa'],
                            ['label' => 'Starter', 'count' => $stats['starter'], 'color' => 'var(--green)'],
                            ['label' => 'Pro', 'count' => $stats['pro'], 'color' => 'var(--brand-s)'],
                        ];
                        $funnel_max = max($stats['free'], $stats['starter'], $stats['pro'], 1);
                        foreach ($funnel as $f):
                            $w = round(($f['count'] / $funnel_max) * 100);
                        ?>
                        <div class="bae-adm-bar-row" style="margin-bottom:8px;">
                            <div class="bae-adm-bar-label"><?php echo $f['label']; ?></div>
                            <div class="bae-adm-bar-track">
                                <div class="bae-adm-bar-fill" style="width:<?php echo max($w, 3); ?>%;background:<?php echo $f['color']; ?>;"></div>
                            </div>
                            <div class="bae-adm-bar-count"><?php echo $f['count']; ?></div>
                        </div>
                        <?php endforeach; ?>
                        <div style="margin-top:12px;font-size:12px;color:var(--text-3);display:flex;gap:16px;">
                            <span>Conversion: <strong style="color:var(--text);"><?php echo $conv; ?>%</strong></span>
                            <span>Est. MRR: <strong style="color:var(--green);">₱<?php echo number_format($stats['starter'] * 49 + $stats['pro'] * 99); ?></strong></span>
                        </div>
                    </div>

                    <!-- Plan distribution table -->
                    <div class="bae-adm-table-wrap">
                        <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                            <div style="font-size:13px;font-weight:600;color:var(--text);">Plan Distribution</div>
                        </div>
                        <table class="bae-adm-table">
                            <thead><tr><th>Plan</th><th>Users</th><th>Share</th><th>Est. Monthly Revenue</th></tr></thead>
                            <tbody>
                            <?php
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
                    <div class="bae-adm-stats" style="grid-template-columns:repeat(4,1fr);">
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
                        <div class="bae-adm-stat bae-adm-stat-accent-pink">
                            <div class="bae-adm-stat-label">Avg Assets</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['total'] ? round($stats['assets'] / $stats['total'], 1) : 0; ?></div>
                            <div class="bae-adm-stat-sub">per user</div>
                        </div>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                        <?php
                        // Top industries
                        $industries = $wpdb->get_results("SELECT industry, COUNT(*) as cnt FROM {$pt} WHERE industry != '' GROUP BY industry ORDER BY cnt DESC LIMIT 8", ARRAY_A) ?: [];
                        $top_ind_max = !empty($industries) ? max(array_column($industries, 'cnt')) : 1;
                        ?>
                        <div class="bae-adm-form-section" style="margin:0;">
                            <div class="bae-adm-section-hdr">
                                <div class="bae-adm-section-title">Top Industries</div>
                            </div>
                            <div class="bae-adm-bar-chart">
                                <?php foreach ($industries as $ind): $pct = round(($ind['cnt'] / max($top_ind_max, 1)) * 100); ?>
                                <div class="bae-adm-bar-row">
                                    <div class="bae-adm-bar-label" title="<?php echo esc_attr(ucfirst($ind['industry'])); ?>"><?php echo esc_html(ucfirst($ind['industry'])); ?></div>
                                    <div class="bae-adm-bar-track">
                                        <div class="bae-adm-bar-fill" style="width:<?php echo max($pct, 2); ?>%;background:linear-gradient(90deg,var(--brand-d),var(--brand-s));"></div>
                                    </div>
                                    <div class="bae-adm-bar-count"><?php echo $ind['cnt']; ?></div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($industries)): ?><div style="color:var(--text-3);font-size:13px;">No data yet.</div><?php endif; ?>
                            </div>
                        </div>

                        <?php
                        // Top assets
                        $top_asset_types = $wpdb->get_results("SELECT asset_type, COUNT(*) as cnt FROM {$at} WHERE is_generated=1 GROUP BY asset_type ORDER BY cnt DESC LIMIT 8", ARRAY_A) ?: [];
                        $top_ast_max = !empty($top_asset_types) ? max(array_column($top_asset_types, 'cnt')) : 1;
                        ?>
                        <div class="bae-adm-form-section" style="margin:0;">
                            <div class="bae-adm-section-hdr">
                                <div class="bae-adm-section-title">Most Generated Assets</div>
                            </div>
                            <div class="bae-adm-bar-chart">
                                <?php foreach ($top_asset_types as $ast): $pct = round(($ast['cnt'] / max($top_ast_max, 1)) * 100); $name = ucfirst(str_replace('_', ' ', $ast['asset_type'])); ?>
                                <div class="bae-adm-bar-row">
                                    <div class="bae-adm-bar-label" title="<?php echo esc_attr($name); ?>"><?php echo esc_html($name); ?></div>
                                    <div class="bae-adm-bar-track">
                                        <div class="bae-adm-bar-fill" style="width:<?php echo max($pct, 2); ?>%;background:linear-gradient(90deg,rgba(52,211,153,.6),var(--green));"></div>
                                    </div>
                                    <div class="bae-adm-bar-count"><?php echo $ast['cnt']; ?></div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($top_asset_types)): ?><div style="color:var(--text-3);font-size:13px;">No data yet.</div><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="bae-adm-table-wrap">
                        <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                            <div style="font-size:13px;font-weight:600;color:var(--text);">Most Active Users</div>
                        </div>
                        <table class="bae-adm-table">
                            <thead><tr><th>Business</th><th>Plan</th><th>Assets Generated</th><th>Joined</th></tr></thead>
                            <tbody>
                            <?php foreach ($top_users as $tu): ?>
                            <tr>
                                <td class="biz-name"><?php echo esc_html($tu['business_name']); ?></td>
                                <td><span class="bae-plan bae-plan-<?php echo esc_attr($tu['plan'] ?? 'free'); ?>"><?php echo ucfirst($tu['plan'] ?? 'free'); ?></span></td>
                                <td style="font-weight:700;color:var(--text);"><?php echo (int)$tu['asset_count']; ?></td>
                                <td style="color:var(--text-3);font-size:12px;white-space:nowrap;"><?php echo date('M d, Y', strtotime($tu['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($top_users)): ?><tr><td colspan="4" class="bae-adm-empty">No users yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($route === 'logs'): ?>
                    <?php
                    $recent = $wpdb->get_results(
                        "SELECT business_name, ticket, plan, created_at, 'signup' as event FROM {$pt} WHERE business_name != ''
                         UNION ALL
                         SELECT p.business_name, a.ticket, p.plan, a.created_at, CONCAT('asset:', a.asset_type) as event FROM {$at} a LEFT JOIN {$pt} p ON p.ticket = a.ticket WHERE a.is_generated = 1
                         UNION ALL 
                         SELECT p.business_name, pe.ticket, pe.plan, pe.created_at, 'payment' as event FROM bntm_pay_events pe LEFT JOIN {$pt} p ON p.ticket = pe.ticket WHERE pe.status = 'paid'
                         ORDER BY created_at DESC LIMIT 60",
                        ARRAY_A
                    ) ?: [];
                    ?>
                    <div class="bae-adm-tl-filters">
                        <button class="bae-adm-tl-filter-btn active" onclick="baeAdmFilterLogs('all',this)">All Activity</button>
                        <button class="bae-adm-tl-filter-btn" onclick="baeAdmFilterLogs('signup',this)">Signups</button>
                        <button class="bae-adm-tl-filter-btn" onclick="baeAdmFilterLogs('asset',this)">Assets</button>
                        <button class="bae-adm-tl-filter-btn" onclick="baeAdmFilterLogs('payment',this)">Payments</button>
                    </div>
                    <div style="background:var(--bg-2);border:1px solid var(--border);border-radius:14px;padding:24px;">
                        <div class="bae-adm-timeline" id="bae-adm-logs-timeline">
                            <?php 
                            $last_date = '';
                            foreach ($recent as $log):
                                $date = date('F j, Y', strtotime($log['created_at']));
                                if ($date !== $last_date) {
                                    echo '<div class="bae-adm-tl-group">' . ($date === date('F j, Y') ? 'Today' : ($date === date('F j, Y', strtotime('-1 day')) ? 'Yesterday' : $date)) . '</div>';
                                    $last_date = $date;
                                }
                                $is_signup = $log['event'] === 'signup';
                                $is_payment = $log['event'] === 'payment';
                                $is_asset = strpos($log['event'], 'asset:') === 0;
                                $type_class = $is_signup ? 'signup' : ($is_payment ? 'payment' : 'asset');
                                $dot_class = 'bae-adm-tl-dot-' . $type_class;
                                
                                $desc = '';
                                if ($is_signup) $desc = '<strong>' . esc_html($log['business_name']) . '</strong> created a brand profile';
                                elseif ($is_payment) $desc = '<strong>' . esc_html($log['business_name'] ?: 'A user') . '</strong> upgraded to ' . ucfirst($log['plan']);
                                elseif ($is_asset) $desc = '<strong>' . esc_html($log['business_name'] ?: 'A user') . '</strong> generated ' . esc_html(ucfirst(str_replace(['asset:', '_'], ['', ' '], $log['event'])));
                            ?>
                            <div class="bae-adm-tl-item" data-type="<?php echo $type_class; ?>">
                                <div class="bae-adm-tl-dot <?php echo $dot_class; ?>"></div>
                                <div style="flex:1;"><?php echo $desc; ?></div>
                                <div class="bae-adm-tl-tag" style="background:<?php echo $is_signup?'rgba(52,211,153,.1)':($is_payment?'rgba(251,191,36,.1)':'rgba(139,92,246,.1)'); ?>;color:<?php echo $is_signup?'var(--green)':($is_payment?'var(--yellow)':'var(--brand-s)'); ?>;">
                                    <?php echo $is_signup ? 'Signup' : ($is_payment ? 'Upgrade' : 'Asset'); ?>
                                </div>
                                <div class="bae-adm-tl-time"><?php echo date('g:i A', strtotime($log['created_at'])); ?></div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($recent)): ?><div style="font-size:13px;color:var(--text-3);padding:10px;">No activity recorded yet.</div><?php endif; ?>
                        </div>
                    </div>
                    <script>
                    function baeAdmFilterLogs(type, btn) {
                        document.querySelectorAll('.bae-adm-tl-filter-btn').forEach(b=>b.classList.remove('active'));
                        btn.classList.add('active');
                        document.querySelectorAll('.bae-adm-tl-item').forEach(el => {
                            if (type === 'all' || el.dataset.type === type) el.style.display = 'flex'; else el.style.display = 'none';
                        });
                        document.querySelectorAll('.bae-adm-tl-group').forEach(grp => {
                            var visibleSiblings = false; var next = grp.nextElementSibling;
                            while(next && !next.classList.contains('bae-adm-tl-group')){
                                if(next.style.display !== 'none'){ visibleSiblings=true; break; }
                                next = next.nextElementSibling;
                            }
                            grp.style.display = visibleSiblings ? 'block' : 'none';
                        });
                    }
                    </script>

                <?php elseif ($route === 'settings'): ?>
                    <div class="bae-adm-sysinfo">
                        <div class="bae-adm-sysinfo-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="2" width="16" height="16"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                            <strong>BAE Engine Version</strong>
                            <span>1.2.0</span>
                            <div class="bae-adm-sysinfo-dot ok"></div>
                        </div>
                        <div class="bae-adm-sysinfo-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="2" width="16" height="16"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            <strong>PHP Version</strong>
                            <span><?php echo phpversion(); ?></span>
                            <div class="bae-adm-sysinfo-dot <?php echo version_compare(phpversion(), '7.4', '>=') ? 'ok' : 'warn'; ?>"></div>
                        </div>
                        <div class="bae-adm-sysinfo-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="2" width="16" height="16"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                            <strong>Database Status</strong>
                            <span>Connected</span>
                            <div class="bae-adm-sysinfo-dot <?php echo $wpdb->get_var("SHOW TABLES LIKE '{$pt}'") === $pt ? 'ok' : 'err'; ?>"></div>
                        </div>
                        <div class="bae-adm-sysinfo-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="2" width="16" height="16"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            <strong>PayMongo Gateway</strong>
                            <span><?php echo !empty($paymongo_secret) ? 'Configured' : 'Not Set'; ?></span>
                            <div class="bae-adm-sysinfo-dot <?php echo !empty($paymongo_secret) ? 'ok' : 'err'; ?>"></div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                        <div class="bae-adm-form-section" style="margin:0;">
                            <h3>PayMongo API Keys</h3>
                            <p>Required for Starter and Pro plan upgrades.</p>
                            <div class="bae-adm-field">
                                <label>Public Key</label>
                                <input type="text" id="bae-pm-public" value="<?php echo esc_attr($paymongo_public); ?>" placeholder="pk_live_...">
                            </div>
                            <div class="bae-adm-field">
                                <label>Secret Key</label>
                                <input type="password" id="bae-pm-secret" value="<?php echo esc_attr($paymongo_secret); ?>" placeholder="sk_live_...">
                            </div>
                            <button class="bae-adm-save-btn" id="bae-pm-save" onclick="baeAdmSavePaymongo()">Save Keys</button>
                        </div>
                        <div class="bae-adm-form-section" style="margin:0;">
                            <h3>Quick Links</h3>
                            <p>Direct access to user-facing applications.</p>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                <a href="<?php echo home_url('/brand-engine'); ?>" target="_blank" class="bae-adm-quick-btn" style="justify-content:center;padding:12px;">Open Brand Engine Frontend ↗</a>
                                <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" target="_blank" class="bae-adm-quick-btn" style="justify-content:center;padding:12px;">Create New Page in WP ↗</a>
                                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
                                    <h4 style="font-size:12px;color:var(--text);margin:0 0 8px;">Admin Passcode</h4>
                                    <p style="font-size:11px;color:var(--text-3);margin:0;">The passcode protects this dashboard from unauthorized access. To change it, edit the <code>BAE_ADMIN_SECRET</code> constant in the plugin code.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($route === 'security'): ?>
                    <?php
                    $total_users_all = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt}");
                    $empty_profiles  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE business_name = ''");
                    $session_time = isset($_COOKIE[BAE_ADMIN_COOKIE]) ? 'Active' : 'Expired';
                    ?>
                    <div class="bae-adm-stats" style="grid-template-columns:repeat(4,1fr);">
                        <div class="bae-adm-stat bae-adm-stat-accent-purple">
                            <div class="bae-adm-stat-label">Total Tickets</div>
                            <div class="bae-adm-stat-val"><?php echo $total_users_all; ?></div>
                            <div class="bae-adm-stat-sub">issued total</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-green">
                            <div class="bae-adm-stat-label">Valid Profiles</div>
                            <div class="bae-adm-stat-val"><?php echo $stats['total']; ?></div>
                            <div class="bae-adm-stat-sub">healthy accounts</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-yellow">
                            <div class="bae-adm-stat-label">Incomplete</div>
                            <div class="bae-adm-stat-val"><?php echo $empty_profiles; ?></div>
                            <div class="bae-adm-stat-sub">no profile setup</div>
                        </div>
                        <div class="bae-adm-stat bae-adm-stat-accent-blue">
                            <div class="bae-adm-stat-label">Admin Session</div>
                            <div class="bae-adm-stat-val" style="font-size:16px;line-height:1.2;margin:4px 0;">Secured</div>
                            <div class="bae-adm-stat-sub">cookie active</div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                        <div class="bae-adm-form-section" style="margin:0;">
                            <h3>Data Integrity Check</h3>
                            <p>Verify that all generated assets belong to valid tickets.</p>
                            <?php
                            $orphaned_assets = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$at} a LEFT JOIN {$pt} p ON a.ticket = p.ticket WHERE p.ticket IS NULL");
                            ?>
                            <div style="padding:16px;background:var(--bg-2);border:1px solid var(--border);border-radius:10px;margin-bottom:16px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                    <strong style="font-size:13px;color:var(--text);">Orphaned Assets</strong>
                                    <span style="font-size:12px;font-weight:700;color:<?php echo $orphaned_assets > 0 ? 'var(--red)' : 'var(--green)'; ?>;"><?php echo $orphaned_assets; ?> found</span>
                                </div>
                                <div style="font-size:11px;color:var(--text-3);">Assets with ticket codes that no longer exist in the user table.</div>
                            </div>
                            <button class="bae-adm-quick-btn" disabled>Clean Orphaned Assets (Coming Soon)</button>
                        </div>

                        <div class="bae-adm-form-section" style="margin:0;border:1px solid rgba(225,29,72,.3);background:rgba(225,29,72,.02);">
                            <h3 style="color:var(--red);">Data Cleanup</h3>
                            <p>Remove tickets that were issued but never used to set up a brand profile. This frees up database space.</p>
                            <div style="margin-top:20px;padding-top:20px;border-top:1px solid rgba(225,29,72,.1);">
                                <button class="bae-adm-save-btn" style="background:linear-gradient(135deg,#be123c,var(--red));" onclick="baeAdmCleanup()">
                                    Purge <?php echo $empty_profiles; ?> Empty Tickets
                                </button>
                            </div>
                        </div>
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

    // ── Asset / Profile drawer
    var _drawerCache = {};
    function baeAdmToggleProfile(ticket) {
        var drawer = document.getElementById('drawer-'+ticket);
        if (!drawer) return;
        if (drawer.style.display !== 'none') { drawer.style.display = 'none'; return; }
        drawer.style.display = 'table-row';
        if (window.gsap) gsap.fromTo(drawer.firstElementChild.firstElementChild, {opacity:0,y:-10}, {opacity:1,y:0,duration:.3,ease:'power2.out'});
    }
    
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
