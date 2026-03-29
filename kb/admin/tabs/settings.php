<?php
/*
 * KBF admin tab: Settings.
 */

function kbf_admin_settings_tab() {
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $sb_pub    = kbf_get_setting('kbf_maya_sandbox_public', '');
    $sb_sec    = kbf_get_setting('kbf_maya_sandbox_secret', '');
    $lv_pub    = kbf_get_setting('kbf_maya_live_public', '');
    $lv_sec    = kbf_get_setting('kbf_maya_live_secret', '');
    $nonce     = wp_create_nonce('kbf_admin_action');
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title">Platform Settings</h3>
      <p style="color:var(--kbf-slate);font-size:13.5px;margin-bottom:24px;">Configure KonekBayan payments and live mode.</p>
      <style>
        .kbf-admin-preload{
          position:fixed;
          inset:0;
          z-index:99999;
          display:flex;
          align-items:center;
          justify-content:center;
          background:rgba(248,250,252,0.78);
          backdrop-filter: blur(8px);
          transition:opacity .35s ease, visibility .35s ease;
        }
        .kbf-admin-preload.kbf-preload-hide{
          opacity:0;
          visibility:hidden;
          pointer-events:none;
        }
        html.kbf-preload-lock,
        body.kbf-preload-lock{
          overflow:hidden !important;
          height:100%;
        }
        .kbf-admin-preload-mark{
          width:54px;
          height:54px;
          border-radius:14px;
          background:linear-gradient(135deg, #5ba8f5, #3d8ef0);
          display:inline-flex;
          align-items:center;
          justify-content:center;
          color:#ffffff;
          font-weight:800;
          letter-spacing:.6px;
          box-shadow:0 8px 18px rgba(61,142,240,.2);
          overflow:hidden;
          animation:kbfpreloadjump 1.2s cubic-bezier(.34,1.2,.64,1) infinite;
        }
        .kbf-admin-preload-mark img{
          width:26px;height:26px;object-fit:contain;display:block;
          filter:brightness(0) invert(1);
        }
      </style>

      <!-- Demo / Live Mode Toggle -->
        <div class="kbf-card" style="margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
              <strong style="font-size:15px;color:var(--kbf-navy);">Payment Mode</strong>
              <span class="kbf-badge <?php echo $demo_mode?'kbf-badge-holding':'kbf-badge-active'; ?>">
                <?php echo $demo_mode?'DEMO -- Auto-confirm':'LIVE -- Maya Checkout'; ?>
              </span>
            </div>
            <p style="margin:0 0 10px;font-size:13.5px;color:var(--kbf-text-sm);line-height:1.6;">
              <strong>Demo ON:</strong> Sponsorships auto-confirmed instantly, no real payment.<br>
              <strong>Demo OFF:</strong> Sponsors are redirected to Maya's secure checkout page (Maya Wallet, cards, QRPh).
            </p>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;min-width:160px;">
            <?php if($demo_mode): ?>
              <button class="kbf-btn kbf-btn-success" onclick="kbfSaveSetting('kbf_demo_mode','0','<?php echo $nonce; ?>', true)">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Switch to Live
              </button>
            <?php else: ?>
              <button class="kbf-btn kbf-btn-accent" onclick="kbfSaveSetting('kbf_demo_mode','1','<?php echo $nonce; ?>', true)">Switch to Demo</button>
            <?php endif; ?>
          </div>
        </div>
        <?php if($demo_mode): ?>
        <div class="kbf-alert kbf-alert-warning kbf-alert-noicon kbf-alert-compact kbf-alert-block" style="margin-top:12px;">
          <span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
              <path d="M8.982 1.566a1.13 1.13 0 0 0-1.964 0L.165 13.233c-.457.778.091 1.767.982 1.767h13.706c.89 0 1.438-.99.982-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1-2.002 0 1 1 0 0 1 2.002 0z"/>
            </svg>
          </span>
          <div><strong>Demo Mode is active.</strong> Configure your Maya API keys below, then switch to Live.</div>
        </div>
        <?php else: ?>
        <div class="kbf-alert kbf-alert-success kbf-alert-noicon kbf-alert-compact kbf-alert-block" style="margin-top:12px;"><span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;"><svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM12.03 5.97a.75.75 0 0 0-1.06-1.06L7.5 8.44 5.53 6.47a.75.75 0 1 0-1.06 1.06L6.97 10a.75.75 0 0 0 1.06 0l4-4z"/></svg></span><div><strong>Live Mode active.</strong> Maya Checkout processes all payments. Sandbox keys used for testing.</div></div>
        <?php endif; ?>
      </div>

      <!-- Maya API Keys -->
      <div class="kbf-card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <strong style="font-size:15px;color:var(--kbf-navy);">Maya API Keys</strong>
          <a href="https://sandbox-manager.paymaya.com" target="_blank" style="font-size:12px;color:var(--kbf-blue);margin-left:auto;">Open Maya Business Manager</a>
        </div>

        <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:16px;font-size:13px;color:var(--kbf-text-sm);line-height:1.7;">
          <strong style="color:var(--kbf-navy);">Where to find your keys:</strong><br>
          Maya Business Manager â†’ Developers â†’ API Keys. Copy Public Key &amp; Secret Key for both Sandbox and Live environments.
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
          <!-- Sandbox Keys -->
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-accent);margin-bottom:10px;">Sandbox (Testing)</div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Sandbox Public Key</label>
              <input type="text" id="sb-pub" value="<?php echo esc_attr($sb_pub); ?>" placeholder="pk-sandbox-..." style="font-family:monospace;font-size:12px;">
            </div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Sandbox Secret Key</label>
              <input type="password" id="sb-sec" value="<?php echo esc_attr($sb_sec); ?>" placeholder="sk-sandbox-..." style="font-family:monospace;font-size:12px;">
            </div>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveMayaKeys('sandbox')">Save Sandbox Keys</button>
          </div>
          <!-- Live Keys -->
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-green);margin-bottom:10px;">Live (Production)</div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Live Public Key</label>
              <input type="text" id="lv-pub" value="<?php echo esc_attr($lv_pub); ?>" placeholder="pk-live-..." style="font-family:monospace;font-size:12px;">
            </div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Live Secret Key</label>
              <input type="password" id="lv-sec" value="<?php echo esc_attr($lv_sec); ?>" placeholder="sk-live-..." style="font-family:monospace;font-size:12px;">
            </div>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveMayaKeys('live')">Save Live Keys</button>
          </div>
        </div>

        <!-- Webhook URL -->
        <div style="border-top:1px solid var(--kbf-border);margin-top:20px;padding-top:16px;">
          <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-navy);margin-bottom:10px;">Webhook URL (for automatic payment confirmation)</div>
          <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;font-size:13px;line-height:1.8;">
            <strong>Your Webhook URL -- copy this into Maya Business Manager:</strong><br>
            <code style="font-size:12px;word-break:break-all;color:var(--kbf-navy);background:#e2e8f0;padding:4px 8px;border-radius:4px;display:inline-block;margin:6px 0;"><?php echo esc_html(rest_url('kbf/v1/maya-webhook')); ?></code><br>
            <small style="color:var(--kbf-slate);">
              Maya Business Manager â†’ Developers â†’ Webhooks â†’ Add Webhook URL.<br>
              Subscribe to events: <strong>CHECKOUT_SUCCESS</strong> and <strong>PAYMENT_SUCCESS</strong>.<br>
              No webhook secret is required -- Maya authenticates via your API keys.
            </small>
          </div>
        </div>
      </div>

      <div id="kbf-settings-msg" style="margin-top:12px;"></div>
    </div>
    <!-- ================== JS ================== -->
    <script>
    window.kbfSaveSetting = function(key, val, nonce, reloadOnSuccess) {
        const fd = new FormData();
        fd.append('action','kbf_save_setting');
        fd.append('_ajax_nonce', nonce);
        fd.append('setting_key', key);
        fd.append('setting_val', val);
        if (reloadOnSuccess && window.kbfTriggerAdminPreload) window.kbfTriggerAdminPreload();
        if (window.kbfSetLoadingPage) window.kbfSetLoadingPage(true);
        fetch((window.ajaxurl||'<?php echo admin_url('admin-ajax.php'); ?>'),{method:'POST',body:fd})
        .then(r=>r.json()).then(j=>{
            alert(j.data&&j.data.message?j.data.message:(j.success?'Setting saved!':'Failed to save.'));
            if(j.success && reloadOnSuccess) location.reload();
            if(!reloadOnSuccess && window.kbfSetLoadingPage) window.kbfSetLoadingPage(false);
            if(!reloadOnSuccess && window.kbfHideAdminPreload) window.kbfHideAdminPreload();
            if(reloadOnSuccess && !j.success && window.kbfHideAdminPreload) window.kbfHideAdminPreload();
        }).catch(()=>{
            if(window.kbfSetLoadingPage) window.kbfSetLoadingPage(false);
            if(window.kbfHideAdminPreload) window.kbfHideAdminPreload();
        });
    };
    window.kbfTriggerAdminPreload = function(){
        if (document.getElementById('kbf-admin-preload')) return;
        var root = document.documentElement;
        if (root) root.classList.add('kbf-preload-lock');
        if (document.body) document.body.classList.add('kbf-preload-lock');
        var pre = document.createElement('div');
        pre.id = 'kbf-admin-preload';
        pre.className = 'kbf-admin-preload';
        var logo = '<?php echo defined('BNTM_KBF_URL') ? esc_url(BNTM_KBF_URL . 'assets/branding/logo.png') : ''; ?>';
        pre.innerHTML = '<div class="kbf-admin-preload-mark">' + (logo ? '<img src="'+logo+'" alt="">' : 'BS') + '</div>';
        document.body.appendChild(pre);
    };
    window.kbfHideAdminPreload = function(){
        var pre = document.getElementById('kbf-admin-preload');
        var root = document.documentElement;
        if (pre) {
            pre.classList.add('kbf-preload-hide');
            setTimeout(function(){
                if (pre && pre.parentNode) pre.parentNode.removeChild(pre);
            }, 300);
        }
        if (root) root.classList.remove('kbf-preload-lock');
        if (document.body) document.body.classList.remove('kbf-preload-lock');
    };
    window.kbfSaveMayaKeys = function(type) {
        const nonce = '<?php echo $nonce; ?>';
        const msg   = document.getElementById('kbf-settings-msg');
        const pairs = type === 'sandbox'
            ? [['kbf_maya_sandbox_public', document.getElementById('sb-pub').value],
               ['kbf_maya_sandbox_secret', document.getElementById('sb-sec').value]]
            : [['kbf_maya_live_public', document.getElementById('lv-pub').value],
               ['kbf_maya_live_secret', document.getElementById('lv-sec').value]];
        let done = 0;
        pairs.forEach(([key, val]) => {
            const fd = new FormData();
            fd.append('action', 'kbf_save_setting');
            fd.append('_ajax_nonce', nonce);
            fd.append('setting_key', key);
            fd.append('setting_val', val);
            fetch((window.ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>'), {method:'POST', body:fd})
            .then(r => r.json()).then(j => {
                if (++done === pairs.length && msg) {
                    msg.innerHTML = '<div class="kbf-alert kbf-alert-success kbf-alert-compact">' + (type==='sandbox'?'Sandbox':'Live') + ' keys saved successfully.</div>';
                    setTimeout(() => msg.innerHTML = '', 4000);
                }
            });
        });
    };
    </script>
    <?php return ob_get_clean();
}
