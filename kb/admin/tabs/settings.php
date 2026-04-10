<?php
/*
 * KBF admin tab: Settings.
 */

function kbf_admin_settings_tab() {
    $get_setting = function($key, $default = '') {
        return kbf_get_setting($key, $default);
    };
    $demo_mode = (bool)$get_setting('kbf_demo_mode', true);
    $fee_disabled = (bool)$get_setting('kbf_disable_platform_fee', false);
    $sb_pub    = $get_setting('kbf_maya_sandbox_public', '');
    $sb_sec    = $get_setting('kbf_maya_sandbox_secret', '');
    $lv_pub    = $get_setting('kbf_maya_live_public', '');
    $lv_sec    = $get_setting('kbf_maya_live_secret', '');
    $wh_secret = $get_setting('kbf_maya_webhook_secret', '');
    $didit_sb_key = $get_setting('kbf_didit_sandbox_api_key', '');
    $didit_sb_app = $get_setting('kbf_didit_sandbox_app_id', $get_setting('kbf_didit_sandbox_api_secret', ''));
    $didit_lv_key = $get_setting('kbf_didit_live_api_key', $get_setting('kbf_didit_api_key', ''));
    $didit_lv_app = $get_setting('kbf_didit_live_app_id', $get_setting('kbf_didit_live_api_secret', $get_setting('kbf_didit_api_secret', '')));
    $didit_sb_wf = $get_setting('kbf_didit_sandbox_workflow_id', '');
    $didit_lv_wf = $get_setting('kbf_didit_live_workflow_id', '');
    $didit_wh_secret = $get_setting('kbf_didit_webhook_secret', '');
    $nonce     = wp_create_nonce('kbf_admin_action');
    $webhook_url = rest_url('kbf/v1/maya-webhook');
    $didit_webhook_url = rest_url('kbf/v1/didit-webhook');
    $admin_ajax_url = admin_url('admin-ajax.php');
    $logo_url = defined('BNTM_KBF_URL') ? esc_url(BNTM_KBF_URL . 'assets/branding/logo.png') : '';
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
        <div>
          <h3 class="kbf-section-title" style="margin-bottom:6px;">Platform Settings</h3>
          <p style="color:var(--kbf-slate);font-size:13.5px;margin:0;">Configure Fundora payments and live mode.</p>
        </div>
        <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfAdminReloadSettings()">Save Changes</button>
      </div>
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
        @keyframes kbfpreloadjump{
          0%{transform:translateY(0) rotate(0deg) scale(1); box-shadow:0 8px 18px rgba(61,142,240,.2);}
          25%{transform:translateY(-14px) rotate(-8deg) scale(1.01); box-shadow:0 16px 28px rgba(61,142,240,.3);}
          50%{transform:translateY(2px) rotate(6deg) scale(.99); box-shadow:0 6px 14px rgba(61,142,240,.18);}
          75%{transform:translateY(-8px) rotate(-6deg) scale(1.005); box-shadow:0 12px 24px rgba(61,142,240,.26);}
          100%{transform:translateY(0) rotate(0deg) scale(1); box-shadow:0 8px 18px rgba(61,142,240,.2);}
        }
        .kbf-section .kbf-card{
          box-shadow:none;
          border:1px solid var(--kbf-border);
          transform:none;
        }
        .kbf-section .kbf-card:hover{
          box-shadow:none;
          transform:none;
        }
      </style>
      <?php if($demo_mode): ?>
        <div style="margin-bottom:18px;border-radius:14px;border:1px solid #f59e0b;background:linear-gradient(90deg,#fff7ed 0%,#fff 70%);padding:14px 16px;display:flex;gap:12px;align-items:flex-start;overflow:hidden;">
          <div style="width:36px;height:36px;border-radius:10px;color:#92400e;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
              <path d="M8.982 1.566a1.13 1.13 0 0 0-1.964 0L.165 13.233c-.457.778.091 1.767.982 1.767h13.706c.89 0 1.438-.99.982-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
            </svg>
          </div>
          <div>
            <div style="font-weight:700;color:#92400e;font-size:14.5px;margin-bottom:2px;">Demo Mode is ON — payments are auto-confirmed</div>
            <div style="color:#92400e;font-size:13px;line-height:1.6;">
              No real payment is required while Demo Mode is active. Switch to Live before launch.
            </div>
          </div>
        </div>
      <?php endif; ?>
      <!-- Demo / Live Mode Toggle -->
        <div class="kbf-card" style="margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
              <span style="font-size:15px;color:var(--kbf-navy);" class="kbf-strong">Payment Mode</span>
              <span class="kbf-badge <?php echo $demo_mode?'kbf-badge-holding':'kbf-badge-active'; ?>">
                <?php echo $demo_mode?'DEMO -- Auto-confirm':'LIVE -- Maya Checkout'; ?>
              </span>
            </div>
            <p style="margin:0 0 10px;font-size:13.5px;color:var(--kbf-text-sm);line-height:1.6;">
              <span class="kbf-strong">Demo ON:</span> Sponsorships auto-confirmed instantly, no real payment.<br>
              <span class="kbf-strong">Demo OFF:</span> Sponsors are redirected to Maya's secure checkout page (Maya Wallet, cards, QRPh).
            </p>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;min-width:160px;">
            <?php if($demo_mode): ?>
              <button class="kbf-btn kbf-btn-success" onclick="kbfSaveSetting('kbf_demo_mode','0','<?php echo $nonce; ?>', false)">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Switch to Live
              </button>
            <?php else: ?>
              <button class="kbf-btn kbf-btn-warning" onclick="kbfSaveSetting('kbf_demo_mode','1','<?php echo $nonce; ?>', false)">Switch to Demo</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Platform Fee Toggle -->
      <div class="kbf-card" style="margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
              <span style="font-size:15px;color:var(--kbf-navy);" class="kbf-strong">Platform Fee</span>
              <span class="kbf-badge <?php echo $fee_disabled?'kbf-badge-cancelled':'kbf-badge-active'; ?>">
                <?php echo $fee_disabled?'DISABLED (0%)':'ENABLED (5%)'; ?>
              </span>
            </div>
            <p style="margin:0 0 10px;font-size:13.5px;color:var(--kbf-text-sm);line-height:1.6;">
              <span class="kbf-strong">Enabled:</span> Platform fee is shown in goal preview (5%).<br>
              <span class="kbf-strong">Disabled:</span> Platform fee is 0% and hidden from goal preview.
            </p>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;min-width:160px;">
            <?php if($fee_disabled): ?>
              <button class="kbf-btn kbf-btn-accent" onclick="kbfSaveSetting('kbf_disable_platform_fee','0','<?php echo $nonce; ?>', false)">Enable Fee</button>
            <?php else: ?>
              <button class="kbf-btn kbf-btn-danger" onclick="kbfSaveSetting('kbf_disable_platform_fee','1','<?php echo $nonce; ?>', false)">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Disable Fee
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Maya API Keys -->
      <div class="kbf-card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <span style="font-size:15px;color:var(--kbf-navy);" class="kbf-strong">Maya API Keys</span>
          <a href="https://developers.maya.ph/reference/sandbox-credentials-and-cards" target="_blank" style="font-size:12px;color:var(--kbf-blue);margin-left:auto;">Open Maya Business Manager</a>
        </div>

        <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:16px;font-size:13px;color:var(--kbf-text-sm);line-height:1.7;">
          <span style="color:var(--kbf-navy);" class="kbf-strong">Where to find your keys:</span><br>
          Maya Business Manager -> Developers -> API Keys. Copy Public Key &amp; Secret Key for both Sandbox and Live environments.
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
          <!-- Sandbox Keys -->
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-accent);margin-bottom:10px;">Sandbox (Testing)</div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Sandbox Public Key</label>
              <input type="text" id="sb-pub" value="<?php echo esc_attr($sb_pub); ?>" placeholder="pk-sandbox-..." style="font-family:monospace;font-size:12px;">
              <small style="color:var(--kbf-slate);">Leave blank to keep existing key.</small>
            </div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Sandbox Secret Key</label>
              <input type="password" id="sb-sec" value="" placeholder="sk-sandbox-..." style="font-family:monospace;font-size:12px;">
              <small style="color:var(--kbf-slate);">Stored: <?php echo $sb_sec ? 'Yes' : 'No'; ?></small>
            </div>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveMayaKeys('sandbox')">Save Sandbox Keys</button>
          </div>
          <!-- Live Keys -->
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-blue);margin-bottom:10px;">Live (Production)</div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Live Public Key</label>
              <input type="text" id="lv-pub" value="<?php echo esc_attr($lv_pub); ?>" placeholder="pk-live-..." style="font-family:monospace;font-size:12px;">
              <small style="color:var(--kbf-slate);">Leave blank to keep existing key.</small>
            </div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Live Secret Key</label>
              <input type="password" id="lv-sec" value="" placeholder="sk-live-..." style="font-family:monospace;font-size:12px;">
              <small style="color:var(--kbf-slate);">Stored: <?php echo $lv_sec ? 'Yes' : 'No'; ?></small>
            </div>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveMayaKeys('live')">Save Live Keys</button>
          </div>
        </div>

        <!-- Webhook URL -->
        <div style="border-top:1px solid var(--kbf-border);margin-top:20px;padding-top:16px;">
          <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-navy);margin-bottom:10px;">Webhook URL (for automatic payment confirmation)</div>
          <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;font-size:13px;line-height:1.8;">
            <span class="kbf-strong">Your Webhook URL -- copy this into Maya Business Manager:</span><br>
            <code style="font-size:12px;word-break:break-all;color:var(--kbf-navy);background:#e2e8f0;padding:4px 8px;border-radius:4px;display:inline-block;margin:6px 0;"><?php echo esc_html($webhook_url); ?></code><br>
            <small style="color:var(--kbf-slate);">
              Maya Business Manager -> Developers -> Webhooks -> Add Webhook URL.<br>
              Subscribe to events: <span class="kbf-strong">CHECKOUT_SUCCESS</span> and <span class="kbf-strong">PAYMENT_SUCCESS</span>.<br>
              Optional: add a webhook secret below for signature verification.<br>
              <a href="https://sandbox-manager.paymaya.com" target="_blank" rel="noopener noreferrer">Open Maya Webhooks</a>
              &nbsp;|&nbsp;
              <a href="<?php echo esc_url($webhook_url); ?>" target="_blank" rel="noopener noreferrer">Open Fundora Webhook Endpoint</a>
            </small>
          </div>

         <div style="margin-top:14px;">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-navy);margin-bottom:8px;">Webhook Secret (optional)</div>
            
            <label style="font-size:12.5px;display:block;margin-bottom:4px;">Maya Webhook Secret</label>
            <div class="kbf-form-group" style="display:flex;flex-direction:row;align-items:center;gap:8px;">
              <input type="password" id="wh-secret" value="" placeholder="webhook-secret-...">
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveWebhookSecret()" style="flex-shrink:0;white-space:nowrap;">Save Secret</button>
            </div>
            <small style="color:var(--kbf-slate);display:block;margin-top:4px;">Stored: <?php echo $wh_secret ? 'Yes' : 'No'; ?></small>
            
            <small style="color:var(--kbf-slate);display:block;margin-top:6px;">
              When set, Fundora verifies incoming webhook signatures before updating payments.
            </small>
          </div>
        </div>
      </div>
      <!-- Didit API Keys -->
      <div class="kbf-card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <span style="font-size:15px;color:var(--kbf-navy);" class="kbf-strong">Didit ID Verification</span>
          <a href="https://docs.didit.me/core-technology/id-verification/overview" target="_blank" rel="noopener noreferrer" style="font-size:12px;color:var(--kbf-blue);margin-left:auto;">Open Didit Docs</a>
        </div>
        <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:16px;font-size:13px;color:var(--kbf-text-sm);line-height:1.7;">
          <span style="color:var(--kbf-navy);" class="kbf-strong">API setup:</span><br>
          Add your Didit App ID, API key, and workflow IDs for sandbox and live environments.
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px;">
            <div>
              <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-accent);margin-bottom:10px;">Sandbox</div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Sandbox API Key</label>
              <input type="text" id="didit-sb-key" value="<?php echo esc_attr($didit_sb_key); ?>" placeholder="didit-sandbox-key-...">
              <small style="color:var(--kbf-slate);">Leave blank to keep existing key.</small>
            </div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Sandbox App ID</label>
              <input type="text" id="didit-sb-app-id" value="<?php echo esc_attr($didit_sb_app); ?>" placeholder="didit-sandbox-app-id-...">
            </div>
              <div class="kbf-form-group" style="margin-bottom:10px;">
                <label style="font-size:12.5px;">Sandbox Workflow ID</label>
                <input type="text" id="didit-sb-workflow" value="<?php echo esc_attr($didit_sb_wf); ?>" placeholder="workflow-id-...">
              </div>
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveDiditKeys('sandbox')">Save Sandbox Keys</button>
            </div>
            <div>
              <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-blue);margin-bottom:10px;">Live</div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Live API Key</label>
              <input type="text" id="didit-lv-key" value="<?php echo esc_attr($didit_lv_key); ?>" placeholder="didit-live-key-...">
              <small style="color:var(--kbf-slate);">Leave blank to keep existing key.</small>
            </div>
            <div class="kbf-form-group" style="margin-bottom:10px;">
              <label style="font-size:12.5px;">Live App ID</label>
              <input type="text" id="didit-lv-app-id" value="<?php echo esc_attr($didit_lv_app); ?>" placeholder="didit-live-app-id-...">
            </div>
              <div class="kbf-form-group" style="margin-bottom:10px;">
                <label style="font-size:12.5px;">Live Workflow ID</label>
                <input type="text" id="didit-lv-workflow" value="<?php echo esc_attr($didit_lv_wf); ?>" placeholder="workflow-id-...">
              </div>
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveDiditKeys('live')">Save Live Keys</button>
            </div>
          </div>

        <div style="border-top:1px solid var(--kbf-border);padding-top:16px;">
          <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-navy);margin-bottom:10px;">Webhook URL (for automatic verification)</div>
          <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;font-size:13px;line-height:1.8;">
            <span class="kbf-strong">Your Didit Webhook URL:</span><br>
            <code style="font-size:12px;word-break:break-all;color:var(--kbf-navy);background:#e2e8f0;padding:4px 8px;border-radius:4px;display:inline-block;margin:6px 0;"><?php echo esc_html($didit_webhook_url); ?></code><br>
            <small style="color:var(--kbf-slate);">
              Didit Dashboard -> API &amp; Webhooks -> Add Webhook URL.<br>
              Optional: add a webhook secret below for signature verification.<br>
              <a href="https://docs.didit.me/core-technology/id-verification/overview" target="_blank" rel="noopener noreferrer">Open Didit Docs</a>
              &nbsp;|&nbsp;
              <a href="<?php echo esc_url($didit_webhook_url); ?>" target="_blank" rel="noopener noreferrer">Open Fundora Webhook Endpoint</a>
            </small>
          </div>

          <div style="margin-top:14px;">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-navy);margin-bottom:8px;">Webhook Secret (optional)</div>
            <label style="font-size:12.5px;display:block;margin-bottom:4px;">Didit Webhook Secret</label>
            <div class="kbf-form-group" style="display:flex;flex-direction:row;align-items:center;gap:8px;">
              <input type="password" id="didit-wh-secret" value="" placeholder="webhook-secret-...">
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveDiditWebhookSecret()" style="flex-shrink:0;white-space:nowrap;">Save Secret</button>
            </div>
            <small style="color:var(--kbf-slate);display:block;margin-top:4px;">Stored: <?php echo $didit_wh_secret ? 'Yes' : 'No'; ?></small>
            <small style="color:var(--kbf-slate);display:block;margin-top:6px;">
              When set, Fundora verifies incoming webhook signatures before updating verification status.
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
        fetch((window.ajaxurl||'<?php echo $admin_ajax_url; ?>'),{method:'POST',body:fd})
        .then(r=>r.json()).then(j=>{
            alert(j.data&&j.data.message?j.data.message:(j.success?'Setting saved!':'Failed to save.'));
        }).catch(()=>{
        });
    };
    window.kbfSaveMayaKeys = function(type) {
        const nonce = '<?php echo $nonce; ?>';
        const msg   = document.getElementById('kbf-settings-msg');
        const pairs = type === 'sandbox'
            ? [['kbf_maya_sandbox_public', document.getElementById('sb-pub').value],
               ['kbf_maya_sandbox_secret', document.getElementById('sb-sec').value]]
            : [['kbf_maya_live_public', document.getElementById('lv-pub').value],
               ['kbf_maya_live_secret', document.getElementById('lv-sec').value]];
        const toSave = pairs.filter(([key, val]) => !!val);
        if (toSave.length === 0) {
            if (msg) {
                msg.innerHTML = '<div class="kbf-alert kbf-alert-error kbf-alert-compact">Enter a key to save.</div>';
                setTimeout(() => msg.innerHTML = '', 4000);
            }
            return;
        }
        let done = 0;
        pairs.forEach(([key, val]) => {
            if (!val) return;
            const fd = new FormData();
            fd.append('action', 'kbf_save_setting');
            fd.append('_ajax_nonce', nonce);
            fd.append('setting_key', key);
            fd.append('setting_val', val);
            fetch((window.ajaxurl || '<?php echo $admin_ajax_url; ?>'), {method:'POST', body:fd})
            .then(r => r.json()).then(j => {
                if (!j || !j.success) {
                    if (msg) {
                        msg.innerHTML = '<div class="kbf-alert kbf-alert-error kbf-alert-compact">' + (j && j.data && j.data.message ? j.data.message : 'Failed to save key.') + '</div>';
                        setTimeout(() => msg.innerHTML = '', 4000);
                    }
                    return;
                }
                if (++done === toSave.length && msg) {
                    msg.innerHTML = '<div class="kbf-alert kbf-alert-success kbf-alert-compact">' + (type==='sandbox'?'Sandbox':'Live') + ' keys saved successfully.</div>';
                    setTimeout(() => msg.innerHTML = '', 4000);
                }
            });
        });
    };
    window.kbfSaveWebhookSecret = function() {
        const val = document.getElementById('wh-secret').value;
        if (!val) {
            alert('Enter a new secret to update. Leave blank to keep existing.');
            return;
        }
        kbfSaveSetting('kbf_maya_webhook_secret', val, '<?php echo $nonce; ?>');
    };
    window.kbfSaveDiditKeys = function(type) {
        const msg = document.getElementById('kbf-settings-msg');
        const nonce = '<?php echo $nonce; ?>';
        const pairs = type === 'sandbox'
            ? [
                ['kbf_didit_sandbox_api_key', document.getElementById('didit-sb-key').value],
                ['kbf_didit_sandbox_app_id', document.getElementById('didit-sb-app-id').value],
                ['kbf_didit_sandbox_workflow_id', document.getElementById('didit-sb-workflow').value]
            ]
            : [
                ['kbf_didit_live_api_key', document.getElementById('didit-lv-key').value],
                ['kbf_didit_live_app_id', document.getElementById('didit-lv-app-id').value],
                ['kbf_didit_live_workflow_id', document.getElementById('didit-lv-workflow').value]
            ];
        const toSave = pairs.filter(([_, val]) => !!val);
        if (toSave.length === 0) {
            if (msg) {
                msg.innerHTML = '<div class="kbf-alert kbf-alert-error kbf-alert-compact">Enter a key, app ID, or workflow ID to save.</div>';
                setTimeout(() => msg.innerHTML = '', 4000);
            }
            return;
        }
        let done = 0;
        toSave.forEach(([key, val]) => {
            const fd = new FormData();
            fd.append('action', 'kbf_save_setting');
            fd.append('_ajax_nonce', nonce);
            fd.append('setting_key', key);
            fd.append('setting_val', val);
            fetch((window.ajaxurl || '<?php echo $admin_ajax_url; ?>'), {method:'POST', body:fd})
            .then(r => r.json()).then(j => {
                if (!j || !j.success) {
                    if (msg) {
                        msg.innerHTML = '<div class="kbf-alert kbf-alert-error kbf-alert-compact">' + (j && j.data && j.data.message ? j.data.message : 'Failed to save Didit settings.') + '</div>';
                        setTimeout(() => msg.innerHTML = '', 4000);
                    }
                    return;
                }
                if (++done === toSave.length && msg) {
                    msg.innerHTML = '<div class="kbf-alert kbf-alert-success kbf-alert-compact">' + (type === 'sandbox' ? 'Sandbox' : 'Live') + ' Didit keys saved successfully.</div>';
                    setTimeout(() => msg.innerHTML = '', 4000);
                }
            });
        });
    };
    window.kbfSaveDiditWebhookSecret = function() {
        const val = document.getElementById('didit-wh-secret').value;
        if (!val) {
            alert('Enter a new secret to update. Leave blank to keep existing.');
            return;
        }
        kbfSaveSetting('kbf_didit_webhook_secret', val, '<?php echo $nonce; ?>');
    };
    window.kbfAdminReloadSettings = function(){
        if (document.getElementById('kbf-admin-preload')) {
            location.reload();
            return;
        }
        var pre = document.createElement('div');
        pre.id = 'kbf-admin-preload';
        pre.className = 'kbf-admin-preload';
        var logo = '<?php echo $logo_url; ?>';
        pre.innerHTML = '<div class="kbf-admin-preload-mark">' + (logo ? '<img src="'+logo+'" alt="">' : 'BS') + '</div>';
        document.body.appendChild(pre);
        setTimeout(function(){ location.reload(); }, 150);
    };
    </script>
    <?php return ob_get_clean();
}


