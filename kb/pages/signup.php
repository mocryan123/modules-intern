<?php
/*
 * KBF Sign Up page.
 */

if (!defined('ABSPATH')) exit;

function bntm_kbf_render_signup() {
    kbf_global_assets();
    $signin_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : '#';
    $privacy_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('privacy') : '#';
    $terms_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('terms') : '#';
    $signup_error = '';
    $signup_success = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['kbf_auth_action']) && $_POST['kbf_auth_action'] === 'signup') {
        $nonce_ok = isset($_POST['kbf_auth_nonce']) && wp_verify_nonce($_POST['kbf_auth_nonce'], 'kbf_auth_signup');
        if (!$nonce_ok) {
            $signup_error = 'Security check failed. Please try again.';
        } else {
            $ip = function_exists('kbf_auth_get_ip') ? kbf_auth_get_ip() : '';
            $retry_after = 0;
            if ($ip && function_exists('kbf_auth_is_signup_rate_limited') && kbf_auth_is_signup_rate_limited($ip, $retry_after)) {
                $mins = max(1, (int) ceil($retry_after / 60));
                $signup_error = 'Too many sign up attempts. Try again in ' . $mins . ' minute(s).';
            }
            if (!$signup_error && function_exists('kbf_auth_register_signup_attempt')) {
                kbf_auth_register_signup_attempt($ip);
            }
            $email = isset($_POST['user_email']) ? sanitize_email(wp_unslash($_POST['user_email'])) : '';
            $password = isset($_POST['user_password']) ? (string) wp_unslash($_POST['user_password']) : '';
            if (!$email || !$password) {
                $signup_error = 'Please complete all required fields.';
            } elseif (!is_email($email)) {
                $signup_error = 'Please enter a valid email address.';
            } elseif (strlen($password) < 8) {
                $signup_error = 'Password must be at least 8 characters.';
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $signup_error = 'Password must contain at least 1 uppercase letter.';
            } elseif (!preg_match('/[a-z]/', $password)) {
                $signup_error = 'Password must contain at least 1 lowercase letter.';
            } elseif (!preg_match('/[0-9]/', $password)) {
                $signup_error = 'Password must contain at least 1 number.';
            } elseif (email_exists($email)) {
                $signup_error = 'An account with that email already exists.';
            } else {
                $base_login = sanitize_user(current(explode('@', $email)), true);
                if (!$base_login) {
                    $base_login = 'user';
                }
                $display_name = $base_login;
                $login = $base_login;
                $suffix = 1;
                while (username_exists($login)) {
                    $login = $base_login . $suffix;
                    $suffix++;
                }
                $user_id = wp_insert_user([
                    'user_login' => $login,
                    'user_email' => $email,
                    'user_pass' => $password,
                    'display_name' => $display_name,
                    'role' => 'subscriber',
                ]);
                if (is_wp_error($user_id)) {
                    $signup_error = 'Unable to create account. Please try again.';
                } else {
                    // CRITICAL FIX: Save social name so users can login with @username
                    update_user_meta($user_id, 'kbf_social_name', $base_login);

                    if (defined('KBF_EMAIL_VERIFY_DISABLED') && KBF_EMAIL_VERIFY_DISABLED) {
                        update_user_meta($user_id, 'kbf_email_verified', '1');
                        delete_user_meta($user_id, 'kbf_email_verify_hash');
                        delete_user_meta($user_id, 'kbf_email_verify_expires');
                        $signup_success = 'Account created. You can sign in right away.';
                    } else {
                        $token = wp_generate_password(32, false, false);
                        update_user_meta($user_id, 'kbf_email_verified', '0');
                        update_user_meta($user_id, 'kbf_email_verify_hash', kbf_auth_make_verify_hash($token));
                        update_user_meta($user_id, 'kbf_email_verify_expires', time() + KBF_EMAIL_VERIFY_TTL);
                        $verify_url = add_query_arg([
                            'kbf_verify' => $token,
                            'uid' => $user_id,
                        ], function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : wp_login_url());
                        $subject = 'Verify your Fundora account';
                        $message = '
<div style="font-family:Poppins,Arial,sans-serif;max-width:520px;
            margin:0 auto;background:#f3f6fb;">
  <div style="background:#fff;border-radius:16px;margin:24px auto;
              overflow:hidden;border:1px solid #edf0f4;">

    <div style="background:linear-gradient(135deg,#5ba8f5,#3d8ef0);
                padding:28px 32px;text-align:center;">
      <span style="font-size:22px;font-weight:800;color:#fff;
                   letter-spacing:-0.5px;">Fundora</span>
      <div style="font-size:12px;color:rgba(255,255,255,0.8);
                  margin-top:4px;">Filipino Crowdfunding Platform</div>
    </div>

    <div style="padding:32px;">
      <h2 style="font-size:20px;font-weight:700;color:#0f1115;
                 margin:0 0 10px;">Verify your email</h2>
      <p style="font-size:14px;color:#6f7785;line-height:1.65;
                margin:0 0 24px;">
        Thanks for signing up! Click the button below to verify 
        your email address and get started on Fundora.
      </p>
      <a href="' . esc_url($verify_url) . '"
         style="display:inline-block;padding:13px 28px;
                background:linear-gradient(135deg,#5ba8f5,#3d8ef0);
                color:#fff;border-radius:10px;text-decoration:none;
                font-weight:600;font-size:14px;letter-spacing:0.01em;">
        Verify my email
      </a>
      <p style="margin:20px 0 0;font-size:12px;color:#6f7785;
                line-height:1.6;">
        This link expires in <strong>24 hours</strong>. 
        If you did not create a Fundora account, 
        you can safely ignore this email.
      </p>
    </div>

    <div style="padding:16px 32px;border-top:1px solid #edf0f4;
                text-align:center;">
      <p style="font-size:11px;color:#94a3b8;margin:0;">
        &copy; ' . date('Y') . ' Fundora &middot; 
        Maramag, Bukidnon, Philippines
      </p>
    </div>

  </div>
</div>';
                        $headers = ['Content-Type: text/html; charset=UTF-8'];
                        wp_mail($email, $subject, $message, $headers);
                        $signup_success = 'Account created. Please check your email to verify before signing in.';
                    }
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
        font-family: 'Poppins', system-ui, -apple-system, sans-serif;
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
      .kbf-auth-form .kbf-form-group{margin-bottom:20px;position:relative;}
      .kbf-auth-input{
        position:relative;
        border:1.5px solid rgba(37,99,235,0.25);
        border-radius:14px;
        background:#ffffff;
        transition:border-color .2s;
      }
      .kbf-auth-input:focus-within{
        border-color:#2563eb;
      }
      .kbf-auth-input label.kbf-float-label{
        position:absolute;
        left:36px;
        top:50%;
        transform:translateY(-50%);
        font-size:13.5px;
        color:#64748b;
        background:#ffffff;
        padding:0 4px;
        pointer-events:none;
        transition:top .18s ease, font-size .18s ease, color .18s ease, transform .18s ease;
        line-height:1;
      }
      .kbf-auth-input:focus-within label.kbf-float-label,
      .kbf-auth-input.kbf-has-value label.kbf-float-label{
        top:0;
        transform:translateY(-50%);
        font-size:11.5px;
        color:#2563eb;
      }
      .kbf-auth-input .kbf-icon{
        position:absolute;
        left:12px;
        top:50%;
        transform:translateY(-50%);
        pointer-events:none;
        color:#2563eb;
        font-size:15px;
      }
      .kbf-auth-input input{
        display:block;
        width:100%;
        border:0;
        outline:none;
        background:transparent;
        font-size:13.5px;
        color:#0b1a33;
        padding:14px 36px 14px 36px;
        box-sizing:border-box;
        font-family:inherit;
      }
      .kbf-auth-input input:focus{outline:none;box-shadow:none;}
      .kbf-auth-input input:-webkit-autofill,
      .kbf-auth-input input:-webkit-autofill:hover,
      .kbf-auth-input input:-webkit-autofill:focus,
      .kbf-auth-input input:-webkit-autofill:active{
        -webkit-text-fill-color:#0b1a33;
        transition: background-color 9999s ease-in-out 0s;
        box-shadow:0 0 0 1000px #ffffff inset;
      }
      .kbf-auth-toggle{border:0;background:transparent;padding:0;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;position:absolute;right:15px;top:50%;transform:translateY(-50%);}
      .kbf-auth-toggle img{width:16px;height:16px;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);}
      .kbf-auth-cta{margin-top:14px;}
      .kbf-auth-cta .kbf-btn.kbf-btn-primary{width:100%;display:block;}
      .kbf-auth-legal{display:flex;gap:6px;align-items:center;justify-content:center;font-size:13px;color:var(--kbf-slate);margin-top:2px;cursor:pointer;}
      .kbf-auth-legal input{width:14px;height:14px;accent-color:var(--kbf-blue);cursor:pointer;}
      .kbf-auth-legal a{color:var(--kbf-blue);text-decoration:none;font-weight:600;}
      .kbf-auth-legal a:hover{text-decoration:underline;}
      .kbf-field-error{margin-top:6px;font-size:11.5px;color:#e11d48;display:none;}
      .kbf-input-error{border-color:#dc2626 !important;box-shadow:0 0 0 3px rgba(220,38,38,.12);}
      .kbf-pass-req{margin-top:8px;background:rgba(15,23,42,0.04);border-radius:10px;padding:10px 12px;font-size:14px;color:#475569;}
      .kbf-pass-req ul{list-style:none;margin:0;padding:0;display:grid;gap:4px;}
      .kbf-pass-req li{font-size:13px;display:flex;align-items:center;gap:6px;}
      .kbf-pass-req .kbf-pass-icon{font-size:12px;color:#e11d48;}
      .kbf-pass-req .is-ok{color:#166534;}
      .kbf-pass-req .is-ok .kbf-pass-icon{color:#16a34a;}
      .kbf-pass-error{margin-top:6px;font-size:11.5px;color:#e11d48;display:none;}
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
            <h2 class="kbf-auth-title">Sign Up</h2>
            <p class="kbf-auth-sub">Create your account to support fundraisers or launch your own in minutes.</p>
            <form class="kbf-auth-form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
              <?php if ($signup_error): ?>
                <div class="kbf-alert kbf-alert-error kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($signup_error); ?></div>
              <?php elseif ($signup_success): ?>
                <div class="kbf-alert kbf-alert-success kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($signup_success); ?></div>
              <?php elseif (!empty($_GET['verify']) && $_GET['verify'] === 'failed'): ?>
                <div class="kbf-alert kbf-alert-error kbf-alert-compact" style="margin-bottom:12px;">Verification link is invalid or expired. Please sign up again.</div>
              <?php endif; ?>
              <input type="hidden" name="kbf_auth_action" value="signup">
              <input type="hidden" name="kbf_auth_nonce" value="<?php echo esc_attr(wp_create_nonce('kbf_auth_signup')); ?>">
              <div class="kbf-form-group">
                <div class="kbf-auth-input">
                  <i class="ph ph-envelope-simple kbf-icon" aria-hidden="true"></i>
                  <label class="kbf-float-label" for="kbf-signup-email">Email</label>
                  <input type="email" id="kbf-signup-email" name="user_email" required autocomplete="email">
                </div>
                <div class="kbf-field-error" aria-live="polite">This field is required.</div>
              </div>
                <div class="kbf-form-group">
                  <div class="kbf-auth-input">
                    <i class="ph ph-lock kbf-icon" aria-hidden="true"></i>
                    <label class="kbf-float-label" for="kbf-signup-password">Password</label>
                    <input type="password" id="kbf-signup-password" name="user_password" required autocomplete="new-password">
                    <button type="button" class="kbf-auth-toggle" data-target="kbf-signup-password" aria-label="Show password">
                      <i class="ph ph-eye-slash" aria-hidden="true"></i>
                    </button>
                  </div>
                  <div class="kbf-field-error" aria-live="polite">This field is required.</div>
                  <div class="kbf-pass-req" aria-live="polite">
                    <ul>
                      <li data-rule="length"><i class="ph ph-x kbf-pass-icon" aria-hidden="true"></i> Minimum 8 characters</li>
                      <li data-rule="upper"><i class="ph ph-x kbf-pass-icon" aria-hidden="true"></i> 1 uppercase letter</li>
                      <li data-rule="lower"><i class="ph ph-x kbf-pass-icon" aria-hidden="true"></i> 1 lowercase letter</li>
                      <li data-rule="number"><i class="ph ph-x kbf-pass-icon" aria-hidden="true"></i> 1 number</li>
                    </ul>
                  </div>
                  <div class="kbf-pass-error" aria-live="polite">Password does not meet requirements.</div>
                </div>
              <div class="kbf-form-group">
                <label class="kbf-auth-legal">
                  <input type="checkbox" required> I agree to the <a href="<?php echo esc_url($terms_url); ?>">Terms &amp; Conditions</a> and <a href="<?php echo esc_url($privacy_url); ?>">Privacy Policy</a>
                </label>
                <div class="kbf-field-error" aria-live="polite">This field is required.</div>
              </div>
              <div class="kbf-auth-cta">
                <button class="kbf-btn kbf-btn-primary" type="submit">Create Account</button>
              </div>
              <div class="kbf-auth-footer">Already have an account? <a href="<?php echo esc_url($signin_url); ?>">Sign In</a></div>
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
            var password = form.querySelector('#kbf-signup-password');
            var passReq = form.querySelector('.kbf-pass-req');
            var passError = form.querySelector('.kbf-pass-error');
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
            function evalPassword(value){
              return {
                length: value.length >= 8,
                upper: /[A-Z]/.test(value),
                lower: /[a-z]/.test(value),
                number: /[0-9]/.test(value)
              };
            }
            function updatePassList(value){
              if (!passReq) return true;
              var rules = evalPassword(value);
              Object.keys(rules).forEach(function(key){
                var item = passReq.querySelector('[data-rule="'+ key +'"]');
                if (!item) return;
                item.classList.toggle('is-ok', !!rules[key]);
                var icon = item.querySelector('.kbf-pass-icon');
                if (icon) {
                  icon.classList.remove('ph-check','ph-x');
                  icon.classList.add(rules[key] ? 'ph-check' : 'ph-x');
                }
              });
              return rules.length && rules.upper && rules.lower && rules.number;
            }
            if (password) {
              updatePassList(password.value || '');
              password.addEventListener('input', function(){
                updatePassList(password.value || '');
                if (passError) passError.style.display = 'none';
              });
            }
            form.addEventListener('submit', function(e){
              var firstInvalid = null;
              required.forEach(function(input){
                var valid = input.type === 'checkbox' ? input.checked : input.value.trim() !== '';
                if (!valid && !firstInvalid) firstInvalid = input;
                setError(input, !valid);
              });
              if (password) {
                var passOk = updatePassList(password.value || '');
                if (!passOk && !firstInvalid) firstInvalid = password;
                if (passError) passError.style.display = passOk ? 'none' : 'block';
                if (!passOk) password.classList.add('kbf-input-error');
              }
              if (firstInvalid) {
                e.preventDefault();
                firstInvalid.focus();
              }
            });
          }

          setupValidation(document.querySelector('.kbf-auth-form'));
        })();

        document.querySelectorAll('.kbf-auth-input input').forEach(function(input){
        function sync(){ input.closest('.kbf-auth-input').classList.toggle('kbf-has-value', input.value.trim() !== ''); }
        input.addEventListener('input', sync);
        input.addEventListener('change', sync);
        sync();
      });
      </script>
    </div>
    <?php
    return ob_get_clean();
}

