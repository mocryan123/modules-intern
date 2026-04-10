<?php
/*
 * KBF Sign Up page.
 */

if (!defined('ABSPATH')) exit;

function bntm_kbf_render_signup() {
    kbf_global_assets();
    $signin_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : '#';
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
            $full_name = isset($_POST['full_name']) ? sanitize_text_field(wp_unslash($_POST['full_name'])) : '';
            $email = isset($_POST['user_email']) ? sanitize_email(wp_unslash($_POST['user_email'])) : '';
            $password = isset($_POST['user_password']) ? (string) wp_unslash($_POST['user_password']) : '';
            if (!$full_name || !$email || !$password) {
                $signup_error = 'Please complete all required fields.';
            } elseif (!is_email($email)) {
                $signup_error = 'Please enter a valid email address.';
            } elseif (strlen($password) < 8) {
                $signup_error = 'Password must be at least 8 characters.';
                // TODO: Consider enforcing stronger password policy (complexity/strength meter).
            } elseif (email_exists($email)) {
                $signup_error = 'An account with that email already exists.';
            } else {
                $base_login = sanitize_user(current(explode('@', $email)), true);
                if (!$base_login) {
                    $base_login = 'user';
                }
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
                    'display_name' => $full_name,
                    'first_name' => $full_name,
                    'role' => 'subscriber',
                ]);
                if (is_wp_error($user_id)) {
                    $signup_error = 'Unable to create account. Please try again.';
                } else {
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
                        $message = "Hi {$full_name},\n\nPlease verify your email by clicking the link below:\n{$verify_url}\n\nThis link expires in 24 hours.\n\nIf you did not create this account, you can ignore this email.";
                        wp_mail($email, $subject, $message);
                        $signup_success = 'Account created. Please check your email to verify before signing in.';
                    }
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
        max-width:1100px;
        margin:0 auto;
        background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.7); box-shadow: var(--kbf-glass-shadow);
        border:1px solid rgba(37,99,235,.12);
        border-radius:32px; overflow:hidden; transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
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
        background: linear-gradient(135deg, rgba(74, 152, 255, 0.9) 0%, rgba(47, 123, 220, 0.95) 100%); backdrop-filter: blur(12px);
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
      .kbf-auth-brand{display:flex;align-items:center;gap:10px;font-weight:600;color:var(--kbf-auth-ink);font-size:15px;margin-bottom:14px;letter-spacing:.2px;}
      .kbf-auth-brand img{width:140px;height:auto;max-height:36px;object-fit:contain;}
      .kbf-auth-title{font-size:26px;font-weight:500;color:var(--kbf-auth-ink);margin:0 0 8px;}
      .kbf-auth-sub{font-size:14px;color:var(--kbf-slate);margin:0 0 22px;line-height:1.7;max-width:440px;}
      .kbf-auth-form .kbf-form-group{margin-bottom:14px;}
      .kbf-auth-input{display:flex;align-items:center;gap:10px;background:#ffffff;border:1px solid rgba(37,99,235,0.2); background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(8px);border-radius:14px;padding:12px 14px;box-shadow:0 6px 16px rgba(30,64,175,.06);}
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
      .kbf-auth-right h3{font-size:20px;margin:0;font-weight:500;color:#ffffff;}
      .kbf-auth-right p{font-size:13.5px;margin:0;color:rgba(255,255,255,.85);line-height:1.7;}
      .kbf-auth-points{display:grid;gap:10px;margin-top:6px;}
      .kbf-auth-point{display:flex;align-items:center;gap:8px;font-size:12.5px;color:#ffffff;}
      .kbf-auth-point span{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:rgba(255,255,255,.2);color:#ffffff;font-size:12px;font-weight:600;}
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
        .kbf-auth-sub{font-size:13.5px;line-height:1.6;margin-bottom:18px;}
        .kbf-auth-input{padding:10px 12px;border-radius:12px;}
        .kbf-auth-point{font-size:12.5px;}
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
            <h2 class="kbf-auth-title">Sign Up</h2>
            <p class="kbf-auth-sub">Create your account to support fundraisers or launch your own in minutes.</p>
            <form class="kbf-auth-form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
              <?php if ($signup_error): ?>
                <div class="kbf-alert kbf-alert-error kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($signup_error); ?></div>
              <?php elseif ($signup_success): ?>
                <div class="kbf-alert kbf-alert-success kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($signup_success); ?></div>
              <?php elseif (!empty($_GET['verify']) && $_GET['verify'] === 'failed'): ?>
                <div class="kbf-alert kbf-alert-error kbf-alert-compact" style="margin-bottom:12px;">Verification link is invalid or expired. Please sign up again or request a new link. TODO: add resend verification.</div>
              <?php endif; ?>
              <input type="hidden" name="kbf_auth_action" value="signup">
              <input type="hidden" name="kbf_auth_nonce" value="<?php echo esc_attr(wp_create_nonce('kbf_auth_signup')); ?>">
              <div class="kbf-form-group">
                <label>Name</label>
                <div class="kbf-auth-input">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg" alt="">
                  <input type="text" name="full_name" placeholder="Full name" required>
                </div>
              </div>
              <div class="kbf-form-group">
                <label>Email</label>
                <div class="kbf-auth-input">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/envelope-fill.svg" alt="">
                  <input type="email" name="user_email" placeholder="you@example.com" required>
                </div>
              </div>
                <div class="kbf-form-group">
                  <label>Password</label>
                  <div class="kbf-auth-input">
                    <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/lock-fill.svg" alt="">
                    <input type="password" id="kbf-signup-password" name="user_password" placeholder="Create a password" required>
                    <button type="button" class="kbf-auth-toggle" data-target="kbf-signup-password" aria-label="Show password">
                      <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/eye-slash.svg" alt="">
                    </button>
                  </div>
                </div>
              <label style="display:flex;gap:6px;align-items:center;font-size:18px;color:var(--kbf-slate);margin-top:2px;">
                <input type="checkbox" style="width:14px;height:14px;" required> I agree to the Terms & Conditions
              </label>
              <div class="kbf-auth-cta">
                <button class="kbf-btn kbf-btn-primary" type="submit">Create Account</button>
              </div>
              <div class="kbf-auth-footer">Already have an account? <a href="<?php echo esc_url($signin_url); ?>">Sign In</a></div>
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
                  ? 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/eye.svg'
                  : 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/eye-slash.svg';
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



