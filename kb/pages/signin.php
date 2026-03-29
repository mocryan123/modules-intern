<?php
/*
 * KBF Sign In page.
 */

if (!defined('ABSPATH')) exit;

function bntm_kbf_render_signin() {
    kbf_global_assets();
    $signup_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signup') : '#';
    if (is_user_logged_in()) {
        $dash = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        $user_id = get_current_user_id();
        $first_login = $user_id ? get_user_meta($user_id, 'kbf_first_login', true) : '';
        if ($first_login) {
            delete_user_meta($user_id, 'kbf_first_login');
            wp_safe_redirect(add_query_arg('kbf_tab', 'profile', $dash));
        } else {
            wp_safe_redirect(add_query_arg('kbf_tab', 'overview', $dash));
        }
        exit;
    }
    $login_error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['kbf_auth_action']) && $_POST['kbf_auth_action'] === 'signin') {
        $nonce_ok = isset($_POST['kbf_auth_nonce']) && wp_verify_nonce($_POST['kbf_auth_nonce'], 'kbf_auth_signin');
        if (!$nonce_ok) {
            $login_error = 'Security check failed. Please try again.';
        } else {
            $raw_login = isset($_POST['user_login']) ? sanitize_text_field(wp_unslash($_POST['user_login'])) : '';
            $login = $raw_login;
            if ($raw_login && is_email($raw_login)) {
                $user_by_email = get_user_by('email', $raw_login);
                if ($user_by_email && !is_wp_error($user_by_email)) {
                    $login = $user_by_email->user_login;
                }
            }
            $creds = [
                'user_login'    => $login,
                'user_password' => isset($_POST['user_password']) ? (string) wp_unslash($_POST['user_password']) : '',
                'remember'      => !empty($_POST['rememberme']),
            ];
            $user = wp_signon($creds, is_ssl());
            if (is_wp_error($user)) {
                $login_error = $user->get_error_message();
                if (!$login_error) {
                    $login_error = 'Invalid login details. Please try again.';
                }
            } else {
                $dash = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
                $user_id = isset($user->ID) ? (int) $user->ID : 0;
                $first_login = $user_id ? get_user_meta($user_id, 'kbf_first_login', true) : '';
                if ($first_login) {
                    delete_user_meta($user_id, 'kbf_first_login');
                    wp_safe_redirect(add_query_arg('kbf_tab', 'profile', $dash));
                } else {
                    wp_safe_redirect(add_query_arg('kbf_tab', 'overview', $dash));
                }
                exit;
            }
        }
    }
    ob_start();
    ?>
    <style>
      html,body{margin:0 !important;padding:0;width:100%;height:100%;overflow:hidden;}
      html,body{margin-top:0 !important;}
      body.admin-bar{margin-top:0 !important;}
      #wpadminbar{display:none !important;}
      :root{
        --kbf-auth-ink:#0b1a33;
        --kbf-auth-blue:#2563eb;
      }
      .bntm-bg{position:fixed;inset:0;width:100vw;height:100vh;margin:0;padding:0;overflow:hidden;background:#ffffff;}
      .kbf-auth-wrap{
        width:100%;
        height:100vh;
        box-sizing:border-box;
        margin:0 auto;
        padding:0 18px;
        display:flex;
        align-items:center;
        justify-content:center;
        position:relative;
        overflow:hidden;
        background:#ffffff;
      }
      .kbf-auth-wrap::before,
      .kbf-auth-wrap::after{
        content:"";
        position:absolute;
        width:560px;height:560px;border-radius:50%;
        background:radial-gradient(circle at 30% 30%, rgba(37,99,235,.22), rgba(255,255,255,0) 62%);
        filter:blur(28px);
        opacity:1;
        pointer-events:none;
        animation:kbfOrbFloat 16s ease-in-out infinite, kbfOrbFade 12s ease-in-out infinite;
      }
      .kbf-auth-wrap::before{top:-260px;left:-220px;}
      .kbf-auth-wrap::after{bottom:-280px;right:-240px;animation-delay:4s,1s;}
      .kbf-auth-orb{
        position:absolute;
        width:320px;height:320px;border-radius:50%;
        background:radial-gradient(circle at 30% 30%, rgba(96,165,250,.28), rgba(255,255,255,0) 64%);
        filter:blur(24px);
        opacity:.95;
        pointer-events:none;
        animation:kbfOrbFloat 18s ease-in-out infinite, kbfOrbFade 14s ease-in-out infinite;
      }
      .kbf-auth-orb.orb-1{top:6%;right:10%;animation-delay:1s,0s;}
      .kbf-auth-orb.orb-2{bottom:8%;left:6%;animation-delay:6s,2s;}
      .kbf-auth-orb.orb-3{top:58%;right:28%;width:240px;height:240px;opacity:.75;animation-delay:9s,3s;}
      @keyframes kbfOrbFloat{
        0%{transform:translate3d(0,0,0) scale(1);}
        50%{transform:translate3d(22px,-16px,0) scale(1.08);}
        100%{transform:translate3d(0,0,0) scale(1);}
      }
      @keyframes kbfOrbFade{
        0%,100%{opacity:.45;}
        50%{opacity:1;}
      }
      @keyframes kbfBgShift{}
      @keyframes kbfGlowFloat{}
      .kbf-auth-card{
        width:100%;
        max-width:1100px;
        background:linear-gradient(180deg,#ffffff 0%, #f8fbff 100%);
        border:1px solid rgba(37,99,235,.12);
        border-radius:26px;
        box-shadow:0 34px 90px rgba(15,23,42,.16), 0 8px 24px rgba(37,99,235,.08);
        display:grid;
        grid-template-columns:1.15fr .85fr;
        overflow:hidden;
        transition:max-width .3s ease,width .3s ease,transform .3s ease,box-shadow .3s ease;
        backdrop-filter:blur(4px);
      }
      .kbf-auth-card:hover{transform:translateY(-2px);box-shadow:0 40px 100px rgba(15,23,42,.18), 0 10px 26px rgba(37,99,235,.12);}
      .kbf-auth-left{padding:40px 42px 44px;}
      .kbf-auth-right{background:linear-gradient(160deg,#eef6ff 0%, #dfeeff 55%, #c7defc 100%);color:#0f172a;padding:36px 34px;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;text-align:left;gap:14px;border-left:1px solid rgba(37,99,235,.12);}
      .kbf-auth-brand{display:flex;align-items:center;gap:10px;font-weight:800;color:var(--kbf-auth-ink);font-size:15px;margin-bottom:14px;letter-spacing:.2px;}
      .kbf-auth-brand img{width:26px;height:26px;border-radius:8px;object-fit:contain;}
      .kbf-auth-title{font-size:30px;font-weight:800;color:var(--kbf-auth-ink);margin:0 0 8px;}
      .kbf-auth-sub{font-size:13.5px;color:var(--kbf-slate);margin:0 0 26px;line-height:1.8;max-width:440px;}
      .kbf-auth-form .kbf-form-group{margin-bottom:14px;}
      .kbf-auth-input{display:flex;align-items:center;gap:10px;background:#ffffff;border:1.5px solid #dbe8ff;border-radius:14px;padding:12px 14px;box-shadow:0 6px 16px rgba(30,64,175,.06);}
      .kbf-auth-input input{background:#ffffff;}
      .kbf-auth-input img{width:16px;height:16px;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);}
      .kbf-auth-input input{border:0;background:transparent;outline:none;font-size:13.5px;width:100%;}
      .kbf-auth-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:6px;}
      .kbf-auth-link{font-size:12.5px;color:var(--kbf-blue);text-decoration:none;font-weight:600;}
      .kbf-auth-cta{margin-top:18px;}
      .kbf-auth-right h3{font-size:20px;margin:0;font-weight:700;color:#0f172a;}
      .kbf-auth-right p{font-size:13px;margin:0;color:#475569;line-height:1.7;}
      .kbf-auth-points{display:grid;gap:10px;margin-top:6px;}
      .kbf-auth-point{display:flex;align-items:center;gap:8px;font-size:12.5px;color:#1f2a44;}
      .kbf-auth-point span{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#e0edff;color:#1d4ed8;font-size:12px;font-weight:700;}
      .kbf-auth-footer{margin-top:16px;font-size:12.5px;color:var(--kbf-slate);}
      .kbf-auth-footer a{color:var(--kbf-blue);font-weight:600;text-decoration:none;}
      @media (max-width: 900px){
        .kbf-auth-card{grid-template-columns:1fr;}
        .kbf-auth-right{order:-1;padding:24px 22px;}
      }
      @media (max-height: 760px){
        .kbf-auth-wrap{padding:0 16px;}
      }
    </style>

    <div>
      <div class="kbf-auth-wrap">
        <span class="kbf-auth-orb orb-1"></span>
        <span class="kbf-auth-orb orb-2"></span>
        <span class="kbf-auth-orb orb-3"></span>
        <div class="kbf-auth-card">
          <div class="kbf-auth-left">
            <div class="kbf-auth-brand">
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="ambag">
              ambag
            </div>
            <h2 class="kbf-auth-title">Sign In</h2>
            <p class="kbf-auth-sub">Welcome back. Access your dashboard, monitor fundraising progress, and support campaigns.</p>
            <form class="kbf-auth-form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
              <?php if ($login_error): ?>
                <div class="kbf-alert kbf-alert-error kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($login_error); ?></div>
              <?php endif; ?>
              <input type="hidden" name="kbf_auth_action" value="signin">
              <input type="hidden" name="kbf_auth_nonce" value="<?php echo esc_attr(wp_create_nonce('kbf_auth_signin')); ?>">
              <div class="kbf-form-group">
                <label>Email or Username</label>
                <div class="kbf-auth-input">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/envelope-fill.svg" alt="">
                  <input type="text" name="user_login" placeholder="you@example.com" required>
                </div>
              </div>
              <div class="kbf-form-group">
                <label>Password</label>
                <div class="kbf-auth-input">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/lock-fill.svg" alt="">
                  <input type="password" name="user_password" placeholder="••••••••" required>
                </div>
              </div>
              <div class="kbf-auth-actions">
                <label style="display:flex;gap:6px;align-items:center;font-size:12.5px;color:var(--kbf-slate);">
                  <input type="checkbox" name="rememberme" value="1" style="width:14px;height:14px;"> Remember me
                </label>
                <a class="kbf-auth-link" href="#">Forgot password?</a>
              </div>
              <div class="kbf-auth-cta">
                <button class="kbf-btn kbf-btn-primary" type="submit">Sign In</button>
              </div>
              <div class="kbf-auth-footer">Don’t have an account? <a href="<?php echo esc_url($signup_url); ?>">Sign Up</a></div>
            </form>
          </div>
          <div class="kbf-auth-right">
            <h3>Glad to see you!</h3>
            <p>One account gives you access to fundraising tools, donor insights, and campaign updates.</p>
            <div class="kbf-auth-points">
              <div class="kbf-auth-point"><span>✓</span> Track donations in real time</div>
              <div class="kbf-auth-point"><span>✓</span> Share your campaign in one click</div>
              <div class="kbf-auth-point"><span>✓</span> Message your supporters</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
