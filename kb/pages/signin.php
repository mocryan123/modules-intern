<?php
/*
 * KBF Sign In page.
 */

if (!defined('ABSPATH')) exit;

function bntm_kbf_render_signin() {
    $signup_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signup') : '#';
    if (is_user_logged_in()) {
        $home = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        if ($home) {
            $home = add_query_arg('kbf_tab', 'overview', $home);
        }
        if (!headers_sent()) {
            wp_safe_redirect($home);
            exit;
        }
        return '<script>window.location.href=' . wp_json_encode($home) . ';</script><noscript><meta http-equiv="refresh" content="0;url=' . esc_url($home) . '"></noscript>';
    }
    kbf_global_assets();
    $login_error = '';
    $login_notice = '';
    if (!empty($_GET['verified']) && $_GET['verified'] === '1') {
        $login_notice = 'Email verified. You can now sign in.';
    }
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
            $retry_after = 0;
            if ($login && function_exists('kbf_auth_is_rate_limited') && kbf_auth_is_rate_limited($login, kbf_auth_get_ip(), $retry_after)) {
                $mins = max(1, (int) ceil($retry_after / 60));
                $login_error = 'Too many login attempts. Try again in ' . $mins . ' minute(s).';
            }
            $creds = [
                'user_login'    => $login,
                'user_password' => isset($_POST['user_password']) ? (string) wp_unslash($_POST['user_password']) : '',
                'remember'      => !empty($_POST['rememberme']),
            ];
            if (!$login_error) {
                $user = wp_signon($creds, is_ssl());
                if (is_wp_error($user)) {
                    if (function_exists('kbf_auth_register_failed_login')) {
                        kbf_auth_register_failed_login($login, kbf_auth_get_ip());
                    }
                    $login_error = 'Invalid login details. Please try again.';
                    if ($user->get_error_code() === 'kbf_email_unverified') {
                        $login_error = 'Please verify your email before signing in.';
                    } elseif ($user->get_error_code() === 'kbf_rate_limited') {
                        $login_error = $user->get_error_message();
                    }
                } else {
                    $home = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
                    if ($home) {
                        $home = add_query_arg('kbf_tab', 'overview', $home);
                    }
                    if (!headers_sent()) {
                        wp_safe_redirect($home);
                        exit;
                    }
                    echo '<script>window.location.href=' . wp_json_encode($home) . ';</script><noscript><meta http-equiv="refresh" content="0;url=' . esc_url($home) . '"></noscript>';
                    exit;
                }
            }
        }
    }
    ob_start();
    ?>
    <style>
      html,body{margin:0 !important;padding:0;width:100%;height:100%;overflow:hidden;overscroll-behavior:none;}
      html,body{margin-top:0 !important;}
      body.admin-bar{margin-top:0 !important;}
      #wpadminbar{display:none !important;}
      :root{
        --kbf-auth-ink:#0b1a33;
        --kbf-auth-blue:#2563eb;
      }
      .bntm-bg{position:fixed;inset:0;width:100vw;height:100vh;margin:0;padding:0;overflow:hidden;background:#ffffff;}
      .kbf-auth-wrap{
        width:100vw;
        height:100vh;
        box-sizing:border-box;
        margin:0;
        padding:0 18px;
        display:flex;
        align-items:center;
        justify-content:center;
        position:fixed;
        inset:0;
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
      .kbf-auth-wrap::after{ display:none; }
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
        margin:0 auto;
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
      .kbf-auth-card:hover{transform:none;box-shadow:0 34px 90px rgba(15,23,42,.16), 0 8px 24px rgba(37,99,235,.08);}
      .kbf-auth-left{padding:40px 42px 44px;}
      .kbf-auth-right{
        background: linear-gradient(135deg, #4a98ff 0%, #2f7bdc 100%);
        color:#ffffff;
        padding:36px 34px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:flex-start;
        text-align:left;
        gap:14px;
        border-left:1px solid rgba(255,255,255,.2);
      }
      .kbf-auth-brand{display:flex;align-items:center;gap:10px;font-weight:800;color:var(--kbf-auth-ink);font-size:15px;margin-bottom:14px;letter-spacing:.2px;}
      .kbf-auth-brand img{width:140px;height:auto;max-height:36px;object-fit:contain;}
      .kbf-auth-title{font-size:30px;font-weight:600;color:var(--kbf-auth-ink);margin:0 0 8px;}
      .kbf-auth-sub{font-size:13.5px;color:var(--kbf-slate);margin:0 0 26px;line-height:1.8;max-width:440px;}
      .kbf-auth-form .kbf-form-group{margin-bottom:14px;}
      .kbf-auth-input{display:flex;align-items:center;gap:10px;background:#ffffff;border:1.5px solid #dbe8ff;border-radius:14px;padding:12px 14px;box-shadow:0 6px 16px rgba(30,64,175,.06);}
      .kbf-auth-input input{background:#ffffff;}
      .kbf-auth-input img{width:16px;height:16px;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);}
      .kbf-auth-input input{border:0;background:transparent;outline:none;font-size:13.5px;width:100%;}
      .kbf-auth-input input:focus{outline:none;box-shadow:none;}
      .kbf-auth-input input:-webkit-autofill,
      .kbf-auth-input input:-webkit-autofill:hover,
      .kbf-auth-input input:-webkit-autofill:focus,
      .kbf-auth-input input:-webkit-autofill:active{
        -webkit-text-fill-color:#0b1a33;
        transition: background-color 9999s ease-in-out 0s;
        box-shadow:0 0 0 1000px #ffffff inset;
      }
      .kbf-auth-toggle{border:0;background:transparent;padding:0;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;}
      .kbf-auth-toggle img{width:16px;height:16px;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);}
      .kbf-auth-cta{margin-top:14px;}
      .kbf-auth-right h3{font-size:20px;margin:0;font-weight:600;color:#ffffff;}
      .kbf-auth-right p{font-size:13px;margin:0;color:rgba(255,255,255,.85);line-height:1.7;}
      .kbf-auth-points{display:grid;gap:10px;margin-top:6px;}
      .kbf-auth-point{display:flex;align-items:center;gap:8px;font-size:12.5px;color:#ffffff;}
      .kbf-auth-point span{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:rgba(255,255,255,.2);color:#ffffff;font-size:12px;font-weight:700;}
      .kbf-auth-footer{margin-top:14px;font-size:12.5px;color:var(--kbf-slate);}
      .kbf-auth-footer a{color:var(--kbf-blue);font-weight:600;text-decoration:none;}
      @media (max-width: 900px){
        .kbf-auth-card{grid-template-columns:1fr;}
        .kbf-auth-right{order:-1;padding:24px 22px;}
      }
      @media (max-width: 900px){
        .kbf-auth-wrap{padding:0 14px;}
        .kbf-auth-card{border-radius:20px;}
        .kbf-auth-left{padding:26px 20px 28px;}
        .kbf-auth-right{padding:20px;}
        .kbf-auth-brand img{width:120px;}
        .kbf-auth-title{font-size:24px;}
        .kbf-auth-sub{font-size:12.5px;line-height:1.6;margin-bottom:18px;}
        .kbf-auth-input{padding:10px 12px;border-radius:12px;}
        .kbf-auth-point{font-size:12px;}
      }
      @media (max-width: 520px){
        .kbf-auth-card{box-shadow:0 18px 50px rgba(15,23,42,.12), 0 6px 18px rgba(37,99,235,.08);}
        .kbf-auth-right{display:none;}
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
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logobanner.png'); ?>" alt="fundora">
            </div>
            <h2 class="kbf-auth-title">Sign In</h2>
            <p class="kbf-auth-sub">Welcome back. Access your dashboard, monitor fundraising progress, and support campaigns.</p>
            <form class="kbf-auth-form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
              <?php if ($login_error): ?>
                <div class="kbf-alert kbf-alert-error kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($login_error); ?></div>
              <?php elseif ($login_notice): ?>
                <div class="kbf-alert kbf-alert-success kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($login_notice); ?></div>
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
                    <input type="password" id="kbf-signin-password" name="user_password" placeholder="Enter your password" required>
                    <button type="button" class="kbf-auth-toggle" data-target="kbf-signin-password" aria-label="Show password">
                      <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/eye.svg" alt="">
                    </button>
                  </div>
                </div>
              <label style="display:flex;gap:6px;align-items:center;font-size:12.5px;color:var(--kbf-slate);margin-top:2px;">
                <input type="checkbox" name="rememberme" style="width:14px;height:14px;"> Keep me signed in
              </label>
              <div class="kbf-auth-cta">
                <button class="kbf-btn kbf-btn-primary" type="submit">Sign In</button>
              </div>
              <div class="kbf-auth-footer">Don't have an account? <a href="<?php echo esc_url($signup_url); ?>">Sign Up</a></div>
            </form>
          </div>
          <div class="kbf-auth-right">
            <h3>Build impact faster</h3>
            <p>Launch fundraisers, share updates, and grow a trusted supporter base.</p>
            <div class="kbf-auth-points">
              <div class="kbf-auth-point"><span>&#10003;</span> Verified profiles build trust</div>
              <div class="kbf-auth-point"><span>&#10003;</span> Seamless donation tracking</div>
              <div class="kbf-auth-point"><span>&#10003;</span> Transparent progress updates</div>
            </div>
          </div>
        </div>
      </div>
      <script>
        (function(){
          document.querySelectorAll('.kbf-auth-toggle').forEach(function(btn){
            btn.addEventListener('click', function(){
              var id = btn.getAttribute('data-target');
              var input = id ? document.getElementById(id) : null;
              if (!input) return;
              var isHidden = input.getAttribute('type') === 'password';
              input.setAttribute('type', isHidden ? 'text' : 'password');
              var img = btn.querySelector('img');
              if (img) {
                img.src = isHidden
                  ? 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/eye-slash.svg'
                  : 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/eye.svg';
              }
              btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
          });
        })();
      </script>
    </div>
    <?php
    return ob_get_clean();
}


