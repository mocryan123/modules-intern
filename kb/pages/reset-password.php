<?php
/*
 * KBF Reset Password page.
 */

if (!defined('ABSPATH')) exit;

function bntm_kbf_render_reset_password() {
    $signin_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : wp_login_url();
    kbf_global_assets();

    $token_error = '';
    $form_error = '';
    $raw_login = isset($_REQUEST['login']) ? wp_unslash($_REQUEST['login']) : '';
    $raw_key = isset($_REQUEST['key']) ? wp_unslash($_REQUEST['key']) : '';
    $login_value = is_string($raw_login) ? sanitize_user(rawurldecode($raw_login), false) : '';
    $key_value = is_string($raw_key) ? preg_replace('/[^a-zA-Z0-9]/', '', rawurldecode($raw_key)) : '';

    if ($login_value === '' || $key_value === '') {
        $token_error = 'Invalid password reset link. Please request a new reset email.';
    }

    $user = null;
    if (!$token_error) {
        $user = check_password_reset_key($key_value, $login_value);
        if (is_wp_error($user)) {
            if (function_exists('kbf_log')) {
                kbf_log('Reset password token validation failed', [
                    'code' => $user->get_error_code(),
                    'message' => $user->get_error_message(),
                    'login' => (string) $login_value,
                ]);
            }
            $token_error = 'This password reset link is invalid or has expired. Please request a new one.';
            $user = null;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['kbf_auth_action']) && $_POST['kbf_auth_action'] === 'reset_password') {
        $nonce_ok = isset($_POST['kbf_reset_nonce']) && wp_verify_nonce($_POST['kbf_reset_nonce'], 'kbf_auth_reset_password');
        $raw_post_login = isset($_POST['rp_login']) ? wp_unslash($_POST['rp_login']) : '';
        $raw_post_key = isset($_POST['rp_key']) ? wp_unslash($_POST['rp_key']) : '';
        $post_login = is_string($raw_post_login) ? sanitize_user(rawurldecode($raw_post_login), false) : '';
        $post_key = is_string($raw_post_key) ? preg_replace('/[^a-zA-Z0-9]/', '', rawurldecode($raw_post_key)) : '';

        if (!$nonce_ok) {
            $form_error = 'Security check failed. Please try again.';
        } else {
            $user = check_password_reset_key($post_key, $post_login);
            if (is_wp_error($user)) {
                if (function_exists('kbf_log')) {
                    kbf_log('Reset password POST validation failed', [
                        'code' => $user->get_error_code(),
                        'message' => $user->get_error_message(),
                        'login' => (string) $post_login,
                    ]);
                }
                $token_error = 'This password reset link is invalid or has expired. Please request a new one.';
            } else {
                $pass1 = isset($_POST['pass1']) ? (string) wp_unslash($_POST['pass1']) : '';
                $pass2 = isset($_POST['pass2']) ? (string) wp_unslash($_POST['pass2']) : '';
                if ($pass1 === '' || $pass2 === '') {
                    $form_error = 'This field is required.';
                } elseif ($pass1 !== $pass2) {
                    $form_error = 'Mismatch';
                } elseif (strlen($pass1) < 8) {
                    $form_error = 'Password must be at least 8 characters.';
                } elseif (!preg_match('/[A-Z]/', $pass1)) {
                    $form_error = 'Password must contain at least 1 uppercase letter.';
                } elseif (!preg_match('/[a-z]/', $pass1)) {
                    $form_error = 'Password must contain at least 1 lowercase letter.';
                } elseif (!preg_match('/[0-9]/', $pass1)) {
                    $form_error = 'Password must contain at least 1 number.';
                } else {
                    reset_password($user, $pass1);
                    $target = add_query_arg('reset', '1', $signin_url);
                    if (!headers_sent()) {
                        wp_safe_redirect($target);
                        exit;
                    }
                    return '<script>window.location.href=' . wp_json_encode($target) . ';</script><noscript><meta http-equiv="refresh" content="0;url=' . esc_url($target) . '"></noscript>';
                }
            }
        }
    }

    ob_start();
    ?>
    <style>
      .kbf-reset-wrap, .kbf-reset-wrap *{font-family:'Poppins',system-ui,-apple-system,sans-serif;}
      html,body{margin:0 !important;padding:0;width:100%;height:100%;overflow:hidden;overscroll-behavior:none;}
      body.admin-bar{margin-top:0 !important;}
      #wpadminbar{display:none !important;}
      .kbf-reset-wrap{
        width:100vw;height:100dvh;min-height:100dvh;display:flex;align-items:center;justify-content:center;
        padding:0 18px;box-sizing:border-box;position:fixed;inset:0;background:#fff;overflow:hidden;
      }
      .kbf-reset-card{
        width:100%;max-width:500px;background:rgba(255,255,255,.98);border:1px solid rgba(37,99,235,.12);
        border-radius:32px;box-shadow:0 34px 90px rgba(15,23,42,.16), 0 8px 24px rgba(37,99,235,.08);overflow:hidden;
      }
      .kbf-reset-left{padding:40px 42px 44px;}
      .kbf-reset-brand{display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
      .kbf-reset-brand img{width:140px;height:auto;max-height:36px;object-fit:contain;}
      .kbf-reset-title{font-size:26px;font-weight:500;color:#0b1a33;margin:0 0 8px;}
      .kbf-reset-sub{font-size:14px;color:#475569;margin:0 0 22px;line-height:1.7;}
      .kbf-form-group{margin-bottom:20px;position:relative;}
      .kbf-auth-input{position:relative;border:1.5px solid rgba(37,99,235,0.25);border-radius:14px;background:#fff;transition:border-color .2s;}
      .kbf-auth-input:focus-within{border-color:#2563eb;}
      .kbf-auth-input label.kbf-float-label{position:absolute;left:16px;top:50%;transform:translateY(-50%);font-size:13.5px;color:#64748b;background:#fff;padding:0 4px;pointer-events:none;transition:top .18s ease,font-size .18s ease,color .18s ease,transform .18s ease;line-height:1;}
      .kbf-auth-input:focus-within label.kbf-float-label,.kbf-auth-input.kbf-has-value label.kbf-float-label{top:0;transform:translateY(-50%);font-size:11.5px;color:#2563eb;}
      .kbf-auth-input input{display:block;width:100%;border:0;outline:none;background:transparent;font-size:13.5px;color:#0b1a33;padding:14px 14px;box-sizing:border-box;font-family:inherit;}
      .kbf-field-error{margin-top:6px;font-size:11.5px;color:#e11d48;display:none;}
      .kbf-input-error{border-color:#dc2626 !important;box-shadow:0 0 0 3px rgba(220,38,38,.12);}
      .kbf-pass-req{margin-top:8px;background:rgba(15,23,42,0.04);border-radius:10px;padding:10px 12px;font-size:14px;color:#475569;}
      .kbf-pass-req ul{list-style:none;margin:0;padding:0;display:grid;gap:4px;}
      .kbf-pass-req li{font-size:13px;display:flex;align-items:center;gap:6px;}
      .kbf-pass-req .kbf-pass-icon{font-size:12px;color:#e11d48;}
      .kbf-pass-req .is-ok{color:#166534;}
      .kbf-pass-req .is-ok .kbf-pass-icon{color:#16a34a;}
      .kbf-pass-error{margin-top:6px;font-size:11.5px;color:#e11d48;display:none;}
      .kbf-auth-cta{margin-top:14px;}
      .kbf-auth-cta .kbf-btn.kbf-btn-primary{width:100%;display:block;}
      .kbf-auth-footer{margin-top:14px;font-size:12.5px;color:#64748b;}
      .kbf-auth-footer a{color:#2563eb;font-weight:600;text-decoration:none;}
      @media (max-width:900px){
        .kbf-reset-wrap{padding:0 14px;}
        .kbf-reset-card{border-radius:20px;}
        .kbf-reset-left{padding:26px 20px 28px;}
        .kbf-reset-brand img{width:120px;}
      }
    </style>

    <div class="kbf-reset-wrap">
      <div class="kbf-reset-card">
        <div class="kbf-reset-left">
          <div class="kbf-reset-brand">
            <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logobanner.png'); ?>" alt="fundora">
          </div>
          <h2 class="kbf-reset-title">Reset Password</h2>
          <p class="kbf-reset-sub">Set a new password for your Fundora account.</p>

          <?php if ($token_error): ?>
            <div class="kbf-alert kbf-alert-error kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($token_error); ?></div>
            <div class="kbf-auth-footer"><a href="<?php echo esc_url($signin_url); ?>">Back to Sign In</a></div>
          <?php else: ?>
            <form class="kbf-auth-form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
              <?php if ($form_error): ?>
                <div class="kbf-alert kbf-alert-error kbf-alert-compact" style="margin-bottom:12px;"><?php echo esc_html($form_error); ?></div>
              <?php endif; ?>
              <input type="hidden" name="kbf_auth_action" value="reset_password">
              <input type="hidden" name="kbf_reset_nonce" value="<?php echo esc_attr(wp_create_nonce('kbf_auth_reset_password')); ?>">
              <input type="hidden" name="rp_login" value="<?php echo esc_attr($login_value); ?>">
              <input type="hidden" name="rp_key" value="<?php echo esc_attr($key_value); ?>">

              <div class="kbf-form-group">
                <div class="kbf-auth-input">
                  <label class="kbf-float-label" for="kbf-rp-pass1">New password</label>
                  <input type="password" id="kbf-rp-pass1" name="pass1" required>
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
                <div class="kbf-auth-input">
                  <label class="kbf-float-label" for="kbf-rp-pass2">Confirm new password</label>
                  <input type="password" id="kbf-rp-pass2" name="pass2" required>
                </div>
                <div class="kbf-field-error" aria-live="polite">This field is required.</div>
              </div>
              <div class="kbf-auth-cta">
                <button class="kbf-btn kbf-btn-primary" type="submit">Update Password</button>
              </div>
              <div class="kbf-auth-footer"><a href="<?php echo esc_url($signin_url); ?>">Back to Sign In</a></div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <script>
      (function(){
        function setupValidation(form){
          if (!form) return;
          var required = form.querySelectorAll('input[required], select[required], textarea[required]');
          var password = form.querySelector('#kbf-rp-pass1');
          var confirmPassword = form.querySelector('#kbf-rp-pass2');
          var passReq = form.querySelector('.kbf-pass-req');
          var passError = form.querySelector('.kbf-pass-error');
          function setError(input, hasError, message){
            var group = input.closest('.kbf-form-group');
            var errorEl = group ? group.querySelector('.kbf-field-error') : null;
            if (hasError) {
              input.classList.add('kbf-input-error');
              if (errorEl) {
                errorEl.textContent = message || 'This field is required.';
                errorEl.style.display = 'block';
              }
            } else {
              input.classList.remove('kbf-input-error');
              if (errorEl) {
                errorEl.textContent = 'This field is required.';
                errorEl.style.display = 'none';
              }
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
              var valid = input.value.trim() !== '';
              if (!valid && !firstInvalid) firstInvalid = input;
              setError(input, !valid, 'This field is required.');
            });
            if (password) {
              var passOk = updatePassList(password.value || '');
              if (!passOk && !firstInvalid) firstInvalid = password;
              if (passError) passError.style.display = passOk ? 'none' : 'block';
              if (!passOk) password.classList.add('kbf-input-error');
            }
            if (password && confirmPassword && password.value !== confirmPassword.value) {
              if (!firstInvalid) firstInvalid = confirmPassword;
              setError(confirmPassword, true, 'Mismatch');
            }
            if (firstInvalid) {
              e.preventDefault();
              firstInvalid.focus();
            }
          });
        }
        setupValidation(document.querySelector('.kbf-auth-form'));

        document.querySelectorAll('.kbf-auth-input input').forEach(function(input){
          function sync(){
            var wrap = input.closest('.kbf-auth-input');
            if (wrap) wrap.classList.toggle('kbf-has-value', input.value.trim() !== '');
          }
          input.addEventListener('input', sync);
          input.addEventListener('change', sync);
          sync();
        });
      })();
    </script>
    <?php
    return ob_get_clean();
}
