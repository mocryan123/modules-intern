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
            } elseif ($raw_login) {
                $social = ltrim($raw_login, '@');
                $social_user = get_users(['meta_key' => 'kbf_social_name', 'meta_value' => $social, 'number' => 1]);
                if (!empty($social_user)) {
                    $login = $social_user[0]->user_login;
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
      /* Typography scale (match landing) */
      :root{
        --kbf-type-h1: 64px;
        --kbf-type-h2: 32px;
        --kbf-type-h3: 24px;
        --kbf-type-h4: 18px;
        --kbf-type-body: 16px;
        --kbf-type-lead: 18px;
        --kbf-type-meta: 12.5px;
      }
      .kbf-auth-wrap, .kbf-auth-wrap *{
        font-family: 'Outfit', system-ui, -apple-system, sans-serif;
      }
      h1{font-size:var(--kbf-type-h1);line-height:1.05;font-weight:600;letter-spacing:-1.5px;color:#0d1a2e;}
      h2{font-size:var(--kbf-type-h2);line-height:1.2;font-weight:500;letter-spacing:-0.5px;color:#0f172a;}
      h3{font-size:var(--kbf-type-h3);line-height:1.3;font-weight:500;letter-spacing:-0.2px;color:#0f172a;}
      h4{font-size:var(--kbf-type-h4);line-height:1.35;font-weight:500;color:#0f172a;}
      p, li{font-size:var(--kbf-type-body);line-height:1.65;font-weight:400;color:#334155;}
      .kbf-lead{font-size:var(--kbf-type-lead);line-height:1.7;}
      small, .kbf-meta{font-size:var(--kbf-type-meta);line-height:1.5;font-weight:500;color:#64748b;}
      @media (max-width:720px){
        :root{
          --kbf-type-h1: 40px;
          --kbf-type-h2: 26px;
          --kbf-type-h3: 20px;
          --kbf-type-h4: 16px;
          --kbf-type-body: 15px;
          --kbf-type-lead: 16px;
          --kbf-type-meta: 12px;
        }
      }@import url('https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css');
      @import url('https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css');
      .ph{font-family:'Phosphor' !important;font-style:normal;font-weight:400;line-height:1;}
      .ph-bold{font-weight:700;}
      .ph-fill{font-weight:400;}
      html,body{margin:0 !important;padding:0;width:100%;height:100%;overflow:hidden;overscroll-behavior:none;}
      html,body{margin-top:0 !important;}
      body.admin-bar{margin-top:0 !important;}
      #wpadminbar{display:none !important;}
      :root{
        --kbf-glass-bg: rgba(255, 255, 255, 0.7);
        --kbf-glass-border: rgba(255, 255, 255, 0.6);
        --kbf-glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        --kbf-blob-1: rgba(91, 168, 245, 0.08);
        --kbf-blob-2: rgba(111, 182, 255, 0.05);
        --kbf-auth-ink:#0b1a33;
        --kbf-auth-blue:#2563eb;
      }
      .bntm-bg{position:fixed;inset:0;width:100vw;height:100vh;margin:0;padding:0;overflow:hidden;background:#ffffff;}
      .kbf-auth-wrap{
        width:100vw;
        height:100dvh;
        min-height:100dvh;
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
        padding-top: env(safe-area-inset-top);
        padding-bottom: env(safe-area-inset-bottom);
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
        background: radial-gradient(circle at 30% 30%, var(--kbf-blob-1), transparent 64%); }
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
        max-width:500px;
        margin:0 auto;
        background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.7); box-shadow: var(--kbf-glass-shadow);
        border:1px solid rgba(37,99,235,.12);
        border-radius:32px; overflow:hidden; transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
        box-shadow:0 34px 90px rgba(15,23,42,.16), 0 8px 24px rgba(37,99,235,.08);
        display:grid;
        grid-template-columns:1fr;
        overflow:hidden;
        transition:max-width .3s ease,width .3s ease,transform .3s ease,box-shadow .3s ease;
        backdrop-filter:blur(4px);
      }
      .kbf-auth-card:hover{transform:none;box-shadow:0 34px 90px rgba(15,23,42,.16), 0 8px 24px rgba(37,99,235,.08);}
      .kbf-auth-left{padding:40px 42px 44px;}
      .kbf-auth-brand{display:flex;align-items:center;justify-content:center;gap:10px;font-weight:600;color:var(--kbf-auth-ink);font-size:15px;margin-bottom:14px;letter-spacing:.2px;}
      .kbf-auth-brand img{width:140px;height:auto;max-height:36px;object-fit:contain;}
      .kbf-auth-title{font-size:26px;font-weight:500;color:var(--kbf-auth-ink);margin:0 0 8px;}
      .kbf-auth-sub{font-size:14px;color:var(--kbf-slate);margin:0 0 22px;line-height:1.7;max-width:440px;}
      .kbf-auth-form .kbf-form-group{margin-bottom:14px;}
      .kbf-auth-input{display:flex;align-items:center;gap:10px;background:#ffffff;border:0; background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(8px);border-radius:14px;padding:0 0 12px;box-shadow:none;position:relative;}
      .kbf-auth-input .kbf-icon{position:absolute;left:12px;top:38%;transform:translateY(-50%);}
      .kbf-auth-input input{background:#ffffff;}
      .kbf-auth-input img{width:16px;height:16px;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);}
      .kbf-auth-input input{border:1px solid rgba(37,99,235,0.2);background:transparent;outline:none;font-size:13.5px;width:100%;padding-left:36px;padding-right:34px;}
      .kbf-auth-input input:focus{outline:none;box-shadow:none;}
      .kbf-auth-input input:-webkit-autofill,
      .kbf-auth-input input:-webkit-autofill:hover,
      .kbf-auth-input input:-webkit-autofill:focus,
      .kbf-auth-input input:-webkit-autofill:active{
        -webkit-text-fill-color:#0b1a33;
        transition: background-color 9999s ease-in-out 0s;
        box-shadow:0 0 0 1000px #ffffff inset;
      }
      .kbf-auth-toggle{border:0;background:transparent;padding:0;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;position:absolute;right:40px;top:38%;transform:translateY(-50%);}
      .kbf-auth-toggle img{width:16px;height:16px;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);}
      .kbf-auth-cta{margin-top:14px;}
      .kbf-field-error{margin-top:6px;font-size:11.5px;color:#e11d48;display:none;}
      .kbf-input-error{border-color:#dc2626 !important;box-shadow:0 0 0 3px rgba(220,38,38,.12);}
      .kbf-auth-legal{display:flex;gap:6px;align-items:center;justify-content:flex-start;font-size:13px;color:var(--kbf-slate);margin-top:2px;cursor:pointer;}
      .kbf-auth-legal input{width:14px;height:14px;accent-color:var(--kbf-blue);cursor:pointer;}
      .kbf-auth-cta .kbf-btn.kbf-btn-primary{width:100%;display:block;}
      .kbf-auth-footer{margin-top:14px;font-size:12.5px;color:var(--kbf-slate);}
      .kbf-auth-footer a{color:var(--kbf-blue);font-weight:600;text-decoration:none;}
      @media (max-width: 900px){
        .kbf-auth-card{grid-template-columns:1fr;}
      }
      @media (max-width: 900px){
        .kbf-auth-wrap{padding:0 14px;}
        .kbf-auth-card{border-radius:20px;}
        .kbf-auth-left{padding:26px 20px 28px;}
        .kbf-auth-brand img{width:120px;}
        .kbf-auth-title{font-size:24px;}
        .kbf-auth-sub{font-size:13.5px;line-height:1.6;margin-bottom:18px;}
        .kbf-auth-input{padding:0 0 12px;border-radius:12px;}
        .kbf-auth-point{font-size:12.5px;}
      }
      @media (max-width: 520px){
        .kbf-auth-card{box-shadow:0 18px 50px rgba(15,23,42,.12), 0 6px 18px rgba(37,99,235,.08);}
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
                <label>Account name, social name, or email</label>
                <div class="kbf-auth-input">
                  <i class="ph ph-envelope-simple kbf-icon" aria-hidden="true"></i>
                  <input type="text" name="user_login" placeholder="Account name, social name, or email" required>
                </div>
                <div class="kbf-field-error" aria-live="polite">This field is required.</div>
              </div>
                <div class="kbf-form-group">
                  <label>Password</label>
                  <div class="kbf-auth-input">
                    <i class="ph ph-lock kbf-icon" aria-hidden="true"></i>
                    <input type="password" id="kbf-signin-password" name="user_password" placeholder="Enter your password" required>
                    <button type="button" class="kbf-auth-toggle" data-target="kbf-signin-password" aria-label="Show password">
                      <i class="ph ph-eye-slash kbf-icon" aria-hidden="true"></i>
                    </button>
                  </div>
                  <div class="kbf-field-error" aria-live="polite">This field is required.</div>
                </div>
              <label class="kbf-auth-legal">
                <input type="checkbox" name="rememberme"> Remember me
              </label>
              <div class="kbf-auth-cta">
                <button class="kbf-btn kbf-btn-primary" type="submit">Sign In</button>
              </div>
              <div class="kbf-auth-footer">Don't have an account? <a href="<?php echo esc_url($signup_url); ?>">Sign Up</a></div>
            </form>
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
              var icon = btn.querySelector('i');
              if (icon) {
                icon.classList.remove('ph-eye','ph-eye-slash','ph-fill','ph');
                if (isHidden) {
                  icon.classList.add('ph','ph-eye');
                } else {
                  icon.classList.add('ph','ph-eye-slash');
                }
              }
              btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
          });

          function setupValidation(form){
            if (!form) return;
            var required = form.querySelectorAll('input[required], select[required], textarea[required]');
            function setError(input, hasError){
              var group = input.closest('.kbf-form-group');
              var errorEl = group ? group.querySelector('.kbf-field-error') : null;
              if (hasError) {
                input.classList.add('kbf-input-error');
                if (errorEl) errorEl.style.display = 'block';
              } else {
                input.classList.remove('kbf-input-error');
                if (errorEl) errorEl.style.display = 'none';
              }
            }
            form.addEventListener('submit', function(e){
              var firstInvalid = null;
              required.forEach(function(input){
                var valid = input.type === 'checkbox' ? input.checked : input.value.trim() !== '';
                if (!valid && !firstInvalid) firstInvalid = input;
                setError(input, !valid);
              });
              if (firstInvalid) {
                e.preventDefault();
                firstInvalid.focus();
              }
            });
          }

          setupValidation(document.querySelector('.kbf-auth-form'));
        })();
      </script>
    </div>
    <?php
    return ob_get_clean();
}


