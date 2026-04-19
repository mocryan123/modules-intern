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
    $forgot_error = '';
    $forgot_notice = '';
    if (!empty($_GET['verified']) && $_GET['verified'] === '1') {
        $login_notice = 'Email verified. You can now sign in.';
    }
    if (!empty($_GET['reset']) && $_GET['reset'] === '1') {
        $login_notice = 'Password updated. You can now sign in.';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['kbf_auth_action'])) {
        $auth_action = sanitize_key((string) wp_unslash($_POST['kbf_auth_action']));
        if ($auth_action === 'signin') {
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
        } elseif ($auth_action === 'forgot_password') {
            $nonce_ok = isset($_POST['kbf_forgot_nonce']) && wp_verify_nonce($_POST['kbf_forgot_nonce'], 'kbf_auth_forgot_password');
            if (!$nonce_ok) {
                $forgot_error = 'Security check failed. Please try again.';
            } else {
                $forgot_processed = false;
                $forgot_login = isset($_POST['forgot_user_login']) ? trim(sanitize_text_field(wp_unslash($_POST['forgot_user_login']))) : '';
                if ($forgot_login === '') {
                    $forgot_error = 'Enter your email or username.';
                } else {
                    $forgot_processed = true;
                    $user = null;
                    if (is_email($forgot_login)) {
                        $user = get_user_by('email', $forgot_login);
                    } else {
                        $user = get_user_by('login', $forgot_login);
                        if (!$user) {
                            $social = ltrim($forgot_login, '@');
                            $social_user = get_users(['meta_key' => 'kbf_social_name', 'meta_value' => $social, 'number' => 1]);
                            if (!empty($social_user)) {
                                $user = $social_user[0];
                            }
                        }
                    }
                    if ($user instanceof WP_User) {
                        $reset_key = get_password_reset_key($user);
                        if (is_wp_error($reset_key)) {
                            $forgot_error = 'Unable to send reset link right now. Please try again later.';
                            if (function_exists('kbf_log')) {
                                kbf_log('Forgot password key generation failed', ['user_id' => (int) $user->ID, 'error' => $reset_key->get_error_message()]);
                            }
                        } else {
                            $reset_url = '';
                            if (function_exists('kbf_get_page_url')) {
                                $reset_url = (string) kbf_get_page_url('reset_password');
                            }
                            // If URL resolver points to home, try direct lookup by slug/shortcode.
                            if (!$reset_url || untrailingslashit($reset_url) === untrailingslashit(home_url('/'))) {
                                $reset_page = get_page_by_path('fundora-reset-password');
                                if ($reset_page && !empty($reset_page->ID)) {
                                    $reset_url = get_permalink($reset_page->ID);
                                } else {
                                    $existing_reset_pages = get_posts([
                                        'post_type'   => 'page',
                                        'post_status' => ['publish', 'draft', 'private'],
                                        'numberposts' => 1,
                                        's'           => '[kbf_reset_password]',
                                    ]);
                                    foreach ($existing_reset_pages as $p) {
                                        if (has_shortcode($p->post_content, 'kbf_reset_password')) {
                                            $reset_url = get_permalink($p->ID);
                                            break;
                                        }
                                    }
                                }
                            }
                            if (!$reset_url || untrailingslashit($reset_url) === untrailingslashit(home_url('/'))) {
                                // Final fallback: use expected slug URL and continue sending.
                                $reset_url = home_url('/fundora-reset-password/');
                                if (function_exists('kbf_log')) {
                                    kbf_log('Forgot password reset page URL fallback used', ['resolved_url' => (string) $reset_url]);
                                }
                            }
                            // Avoid login endpoints: reset links must always target Fundora reset page.
                            $reset_path = (string) wp_parse_url($reset_url, PHP_URL_PATH);
                            if ($reset_path && preg_match('#/(login|wp-login\.php)/?$#i', $reset_path)) {
                                $reset_url = home_url('/fundora-reset-password/');
                                if (function_exists('kbf_log')) {
                                    kbf_log('Forgot password reset URL pointed to login; replaced with reset page', ['resolved_url' => (string) $reset_url]);
                                }
                            }
                            $reset_url = add_query_arg(
                                [
                                    'key'    => $reset_key,
                                    'login'  => $user->user_login,
                                ],
                                $reset_url
                            );
                            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
                            $subject = sprintf('[%s] Password Reset', $blogname);
                            $message = "Hi,\n\n";
                            $message .= "We received a request to reset your Fundora password.\n";
                            $message .= "Open this link to set a new password:\n\n";
                            $message .= esc_url_raw($reset_url) . "\n\n";
                            $message .= "If you did not request this, you can ignore this email.\n";
                            $mail_sent = wp_mail($user->user_email, $subject, $message, ['Content-Type: text/plain; charset=UTF-8']);
                            if (!$mail_sent) {
                                $forgot_error = 'Unable to send reset link right now. Please try again later.';
                                if (function_exists('kbf_log')) {
                                    kbf_log('Forgot password mail send failed', ['user_id' => (int) $user->ID, 'email' => $user->user_email]);
                                }
                            }
                        }
                    }
                    if ($forgot_processed && !$forgot_error) {
                        $forgot_notice = 'If an account exists for that email/username, a password reset link has been sent.';
                    }
                }
            }
        }
    }
    $forgot_open = (bool) ($forgot_error || $forgot_notice);
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
        line-height:1.35;
        color:#0b1a33;
        padding:14px 46px 14px 36px;
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
      .kbf-auth-toggle{border:0;background:transparent;padding:0;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;position:absolute;right:12px;top:50%;transform:translateY(-50%);width:28px;height:28px;color:#2563eb;}
      .kbf-auth-toggle i{font-size:20px;line-height:1;}
      .kbf-auth-toggle img{width:20px;height:20px;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);}
      .kbf-auth-cta{margin-top:14px;}
      .kbf-auth-link{display:inline-block;margin-top:10px;font-size:12.5px;color:var(--kbf-auth-blue);text-decoration:none;font-weight:600;background:transparent;border:0;padding:0;cursor:pointer;}
      .kbf-auth-link:hover{text-decoration:underline;}
      .kbf-auth-divider{margin:14px 0 12px;display:flex;align-items:center;color:#94a3b8;font-size:11.5px;text-transform:uppercase;letter-spacing:.08em;}
      .kbf-auth-divider::before,.kbf-auth-divider::after{content:"";flex:1;height:1px;background:rgba(148,163,184,.35);}
      .kbf-auth-divider span{padding:0 10px;}
      .kbf-forgot-wrap{
        max-height:0;
        opacity:0;
        overflow:hidden;
        transform:translateY(-6px);
        margin-top:0;
        transition:max-height .35s ease,opacity .25s ease,transform .25s ease,margin-top .25s ease;
      }
      .kbf-forgot-wrap.is-open{
        max-height:420px;
        opacity:1;
        transform:translateY(0);
        margin-top:6px;
      }
      .kbf-field-error{margin-top:6px;font-size:11.5px;color:#e11d48;display:none;}
      .kbf-input-error{border-color:#dc2626 !important;box-shadow:0 0 0 3px rgba(220,38,38,.12);}
      .kbf-auth-legal{display:flex;gap:6px;align-items:center;justify-content:flex-start;font-size:13px;color:var(--kbf-slate);margin-top:2px;cursor:pointer;}
      .kbf-auth-legal input{width:14px;height:14px;accent-color:var(--kbf-blue);cursor:pointer;}
      .kbf-auth-cta .kbf-btn.kbf-btn-primary{width:100%;display:block;}
      .kbf-auth-footer{margin-top:14px;font-size:12.5px;color:var(--kbf-slate);}
      .kbf-auth-footer a{color:var(--kbf-auth-blue);font-weight:600;text-decoration:none;}
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
        .kbf-auth-input{padding:0;border-radius:12px;}
        .kbf-auth-input input{padding-top:15px;padding-bottom:15px;}
        .kbf-auth-toggle i{font-size:21px;}
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
                <div class="kbf-auth-input">
                  <i class="ph ph-envelope-simple kbf-icon" aria-hidden="true"></i>
                  <label class="kbf-float-label" for="kbf-signin-email">Email or username</label>
                  <input type="text" id="kbf-signin-email" name="user_login" required>
                </div>
                <div class="kbf-field-error" aria-live="polite">This field is required.</div>
              </div>
                <div class="kbf-form-group">
                  <div class="kbf-auth-input">
                    <i class="ph ph-lock kbf-icon" aria-hidden="true"></i>
                    <label class="kbf-float-label" for="kbf-signin-password">Password</label>
                    <input type="password" id="kbf-signin-password" name="user_password" required>
                    <button type="button" class="kbf-auth-toggle" data-target="kbf-signin-password" aria-label="Show password">
                      <i class="ph ph-eye-slash" aria-hidden="true"></i>
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
              <button class="kbf-auth-link" type="button" id="kbf-forgot-toggle" aria-controls="kbf-forgot-wrap" aria-expanded="<?php echo $forgot_open ? 'true' : 'false'; ?>">Forgot password?</button>
            </form>
            <div id="kbf-forgot-wrap" class="kbf-forgot-wrap<?php echo $forgot_open ? ' is-open' : ''; ?>">
              <div class="kbf-auth-divider"><span>Need help signing in?</span></div>
              <form id="kbf-forgot-password" class="kbf-auth-form kbf-forgot-form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                <?php if ($forgot_error): ?>
                  <div class="kbf-alert kbf-alert-error kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($forgot_error); ?></div>
                <?php elseif ($forgot_notice): ?>
                  <div class="kbf-alert kbf-alert-success kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($forgot_notice); ?></div>
                <?php endif; ?>
                <input type="hidden" name="kbf_auth_action" value="forgot_password">
                <input type="hidden" name="kbf_forgot_nonce" value="<?php echo esc_attr(wp_create_nonce('kbf_auth_forgot_password')); ?>">
                <div class="kbf-form-group">
                  <div class="kbf-auth-input">
                    <i class="ph ph-envelope-simple kbf-icon" aria-hidden="true"></i>
                    <label class="kbf-float-label" for="kbf-forgot-email">Email or username</label>
                    <input type="text" id="kbf-forgot-email" name="forgot_user_login" required>
                  </div>
                  <div class="kbf-field-error" aria-live="polite">This field is required.</div>
                </div>
                <div class="kbf-auth-cta">
                  <button class="kbf-btn kbf-btn-primary" type="submit">Send Password Reset Link</button>
                </div>
              </form>
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

          document.querySelectorAll('.kbf-auth-form').forEach(setupValidation);

          var forgotToggle = document.getElementById('kbf-forgot-toggle');
          var forgotWrap = document.getElementById('kbf-forgot-wrap');
          if (forgotToggle && forgotWrap) {
            var navEntries = (window.performance && typeof window.performance.getEntriesByType === 'function')
              ? window.performance.getEntriesByType('navigation')
              : [];
            var isReload = !!(navEntries.length && navEntries[0] && navEntries[0].type === 'reload');
            if (isReload) {
              forgotWrap.classList.remove('is-open');
              forgotToggle.setAttribute('aria-expanded', 'false');
            }
            forgotToggle.addEventListener('click', function(){
              var isOpen = forgotWrap.classList.toggle('is-open');
              forgotToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
              if (isOpen) {
                var forgotInput = document.getElementById('kbf-forgot-email');
                if (forgotInput) forgotInput.focus();
              }
            });
          }

          var forgotForm = document.getElementById('kbf-forgot-password');
          if (forgotForm) {
            forgotForm.addEventListener('submit', function(){
              console.log('[Fundora] Password reset button clicked');
            });
          }
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



