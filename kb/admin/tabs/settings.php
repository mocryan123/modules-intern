<?php
/*
 * KBF admin tab: Settings.
 */

/**
 * @function  kbf_admin_settings_tab
 * @purpose   Renders the admin settings tab UI for payment mode, platform fee, and Maya/Didit credentials.
 * @used-by   [kbf_admin_ui() tab switch in admin/ui.php, kbf_admin_refresh_tab AJAX response, user/partials/admin_embed.php]
 * @calls     [kbf_get_setting, wp_create_nonce, rest_url, admin_url, ob_start, ob_get_clean, esc_attr, esc_html, esc_url]
 * @params    [none]
 * @returns   [string HTML output buffer for the settings tab]
 * @status    ACTIVE
 *            ACTIVE = confirmed it is called somewhere
 *            NEEDS REVIEW = could not confirm caller,
 *                           may be unused/dead code
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
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div id="kbf-settings-root" class="kbf-section">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
        <div>
          <h3 class="kbf-section-title" style="margin-bottom:6px;">Platform Settings</h3>
          <p style="color:var(--kbf-slate);font-size:13.5px;margin:0;">Configure Fundora payments and live mode.</p>
        </div>
        <button type="button" class="kbf-btn kbf-btn-primary" onclick="return kbfSaveAllSettings(event)">Save Changes</button>
      </div>
      <div id="kbf-settings-msg" style="margin-bottom:12px;"></div>
      <style>
        #kbf-settings-root .kbf-card{
          box-shadow:none;
          border:1px solid var(--kbf-border);
          transform:none;
        }
        #kbf-settings-root .kbf-card:hover{
          box-shadow:none;
          transform:none;
        }
        #kbf-settings-root .kbf-settings-grid{
          display:grid;
          grid-template-columns:1fr 1fr;
          gap:20px;
        }
        #kbf-settings-root .kbf-settings-field{
          margin-bottom:10px;
        }
        #kbf-settings-root .kbf-settings-label{
          font-size:12.5px;
        }
        #kbf-settings-root .kbf-settings-helper{
          color:var(--kbf-slate);
        }
        #kbf-settings-root .kbf-settings-key{
          font-family:monospace;
          font-size:12px;
        }
        #kbf-settings-root .kbf-form-group input,
        #kbf-settings-root .kbf-form-group select,
        #kbf-settings-root .kbf-form-group textarea{
          font-family:monospace;
          width:100%;
          max-width:100%;
          box-sizing:border-box;
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
            <div style="font-weight:700;color:#92400e;font-size:14.5px;margin-bottom:2px;">Demo Mode is ON - payments are auto-confirmed</div>
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
              <span id="kbf-payment-mode-badge" class="kbf-badge <?php echo $demo_mode?'kbf-badge-holding':'kbf-badge-active'; ?>">
                <?php echo $demo_mode?'DEMO -- Auto-confirm':'LIVE -- Maya Checkout'; ?>
              </span>
            </div>
            <p id="kbf-payment-mode-desc" style="margin:0 0 10px;font-size:13.5px;color:var(--kbf-text-sm);line-height:1.6;">
              <span class="kbf-strong">Demo ON:</span> Sponsorships auto-confirmed instantly, no real payment.<br>
              <span class="kbf-strong">Demo OFF:</span> Sponsors are redirected to Maya's secure checkout page (Maya Wallet, cards, QRPh).
            </p>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;min-width:160px;">
            <button type="button" id="kbf-payment-mode-btn" class="kbf-btn <?php echo $demo_mode ? 'kbf-btn-success' : 'kbf-btn-warning'; ?>" onclick="return kbfToggleDemoMode(event)">
              <?php echo $demo_mode ? 'Switch to Live' : 'Switch to Demo'; ?>
            </button>
          </div>
        </div>
      </div>

      <!-- Platform Fee Toggle -->
      <div class="kbf-card" style="margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
              <span style="font-size:15px;color:var(--kbf-navy);" class="kbf-strong">Platform Fee</span>
              <span id="kbf-fee-badge" class="kbf-badge <?php echo $fee_disabled?'kbf-badge-cancelled':'kbf-badge-active'; ?>">
                <?php echo $fee_disabled?'DISABLED (0%)':'ENABLED (5%)'; ?>
              </span>
            </div>
            <p id="kbf-fee-desc" style="margin:0 0 10px;font-size:13.5px;color:var(--kbf-text-sm);line-height:1.6;">
              <span class="kbf-strong">Enabled:</span> Platform fee is shown in goal preview (5%).<br>
              <span class="kbf-strong">Disabled:</span> Platform fee is 0% and hidden from goal preview.
            </p>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;min-width:160px;">
            <button type="button" id="kbf-fee-btn" class="kbf-btn <?php echo $fee_disabled ? 'kbf-btn-accent' : 'kbf-btn-danger'; ?>" onclick="return kbfTogglePlatformFee(event)">
              <?php echo $fee_disabled ? 'Enable Fee' : 'Disable Fee'; ?>
            </button>
          </div>
        </div>
      </div>

      <!-- Maya API Keys -->
      <div class="kbf-card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <span style="font-size:15px;color:var(--kbf-navy);" class="kbf-strong">Maya API Keys</span>
          <a href="https://developers.maya.ph/reference/sandbox-credentials-and-cards" target="_blank" rel="noopener noreferrer" style="font-size:12px;color:var(--kbf-blue);margin-left:auto;">Open Maya Business Manager</a>
        </div>

        <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:16px;font-size:13px;color:var(--kbf-text-sm);line-height:1.7;">
          <span style="color:var(--kbf-navy);" class="kbf-strong">Where to find your keys:</span><br>
          Maya Business Manager -> Developers -> API Keys. Copy Public Key &amp; Secret Key for both Sandbox and Live environments.<br>
          To clear a stored value, type <code style="font-size:12px;">__CLEAR__</code> then click Save Changes.<br>
          To save the literal text <code style="font-size:12px;">__CLEAR__</code>, type <code style="font-size:12px;">\__CLEAR__</code>.
        </div>

        <div class="kbf-settings-grid">
          <!-- Sandbox Keys -->
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-accent);margin-bottom:10px;">Sandbox (Testing)</div>
            <div class="kbf-form-group kbf-settings-field">
              <label for="sb-pub" class="kbf-settings-label">Sandbox Public Key</label>
              <input type="text" id="sb-pub" class="kbf-settings-key" value="<?php echo esc_attr($sb_pub); ?>" placeholder="pk-sandbox-..." autocomplete="off" autocapitalize="off" spellcheck="false">
              <small class="kbf-settings-helper">Required for checkout redirect when Demo Mode is ON. Leave blank to keep existing key.</small>
            </div>
            <div class="kbf-form-group kbf-settings-field">
              <label for="sb-sec" class="kbf-settings-label">Sandbox Secret Key</label>
              <input type="password" id="sb-sec" class="kbf-settings-key" value="" placeholder="sk-sandbox-..." autocomplete="off" autocapitalize="off" spellcheck="false">
              <small class="kbf-settings-helper">Stored: <?php echo $sb_sec ? 'Yes' : 'No'; ?> (encrypted)</small>
            </div>
          </div>
          <!-- Live Keys -->
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-blue);margin-bottom:10px;">Live (Production)</div>
            <div class="kbf-form-group kbf-settings-field">
              <label for="lv-pub" class="kbf-settings-label">Live Public Key</label>
              <input type="text" id="lv-pub" class="kbf-settings-key" value="<?php echo esc_attr($lv_pub); ?>" placeholder="pk-live-..." autocomplete="off" autocapitalize="off" spellcheck="false">
              <small class="kbf-settings-helper">Required for checkout redirect when Demo Mode is OFF (Live). Leave blank to keep existing key.</small>
            </div>
            <div class="kbf-form-group kbf-settings-field">
              <label for="lv-sec" class="kbf-settings-label">Live Secret Key</label>
              <input type="password" id="lv-sec" class="kbf-settings-key" value="" placeholder="sk-live-..." autocomplete="off" autocapitalize="off" spellcheck="false">
              <small class="kbf-settings-helper">Stored: <?php echo $lv_sec ? 'Yes' : 'No'; ?> (encrypted)</small>
            </div>
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
            
            <label for="wh-secret" style="font-size:12.5px;display:block;margin-bottom:4px;">Maya Webhook Secret</label>
            <div class="kbf-form-group" style="display:flex;flex-direction:row;align-items:center;gap:8px;">
              <input type="password" id="wh-secret" class="kbf-settings-key" value="" placeholder="webhook-secret-..." autocomplete="off" autocapitalize="off" spellcheck="false">
            </div>
            <small style="color:var(--kbf-slate);display:block;margin-top:4px;">Stored: <?php echo $wh_secret ? 'Yes' : 'No'; ?> (encrypted)</small>
            
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
          Add your Didit App ID, API key, and workflow IDs for sandbox and live environments.<br>
          To clear a stored value, type <code style="font-size:12px;">__CLEAR__</code> then click Save Changes.<br>
          To save the literal text <code style="font-size:12px;">__CLEAR__</code>, type <code style="font-size:12px;">\__CLEAR__</code>.
        </div>
        <div class="kbf-settings-grid" style="margin-bottom:16px;">
            <div>
              <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-accent);margin-bottom:10px;">Sandbox</div>
            <div class="kbf-form-group kbf-settings-field">
              <label for="didit-sb-key" class="kbf-settings-label">Sandbox API Key</label>
              <input type="text" id="didit-sb-key" class="kbf-settings-key" value="<?php echo esc_attr($didit_sb_key); ?>" placeholder="didit-sandbox-key-..." autocomplete="off" autocapitalize="off" spellcheck="false">
              <small class="kbf-settings-helper">Leave blank to keep existing key.</small>
              <small class="kbf-settings-helper">Stored: <?php echo $didit_sb_key ? 'Yes' : 'No'; ?> (encrypted)</small>
            </div>
            <div class="kbf-form-group kbf-settings-field">
              <label for="didit-sb-app-id" class="kbf-settings-label">Sandbox App ID</label>
              <input type="text" id="didit-sb-app-id" class="kbf-settings-key" value="<?php echo esc_attr($didit_sb_app); ?>" placeholder="didit-sandbox-app-id-..." autocomplete="off" autocapitalize="off" spellcheck="false">
              <small class="kbf-settings-helper">Stored: <?php echo $didit_sb_app ? 'Yes' : 'No'; ?></small>
            </div>
              <div class="kbf-form-group kbf-settings-field">
                <label for="didit-sb-workflow" class="kbf-settings-label">Sandbox Workflow ID</label>
                <input type="text" id="didit-sb-workflow" class="kbf-settings-key" value="<?php echo esc_attr($didit_sb_wf); ?>" placeholder="workflow-id-..." autocomplete="off" autocapitalize="off" spellcheck="false">
                <small class="kbf-settings-helper">Stored: <?php echo $didit_sb_wf ? 'Yes' : 'No'; ?></small>
              </div>
            </div>
            <div>
              <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--kbf-blue);margin-bottom:10px;">Live</div>
            <div class="kbf-form-group kbf-settings-field">
              <label for="didit-lv-key" class="kbf-settings-label">Live API Key</label>
              <input type="text" id="didit-lv-key" class="kbf-settings-key" value="<?php echo esc_attr($didit_lv_key); ?>" placeholder="didit-live-key-..." autocomplete="off" autocapitalize="off" spellcheck="false">
              <small class="kbf-settings-helper">Leave blank to keep existing key.</small>
              <small class="kbf-settings-helper">Stored: <?php echo $didit_lv_key ? 'Yes' : 'No'; ?> (encrypted)</small>
            </div>
            <div class="kbf-form-group kbf-settings-field">
              <label for="didit-lv-app-id" class="kbf-settings-label">Live App ID</label>
              <input type="text" id="didit-lv-app-id" class="kbf-settings-key" value="<?php echo esc_attr($didit_lv_app); ?>" placeholder="didit-live-app-id-..." autocomplete="off" autocapitalize="off" spellcheck="false">
              <small class="kbf-settings-helper">Stored: <?php echo $didit_lv_app ? 'Yes' : 'No'; ?></small>
            </div>
              <div class="kbf-form-group kbf-settings-field">
                <label for="didit-lv-workflow" class="kbf-settings-label">Live Workflow ID</label>
                <input type="text" id="didit-lv-workflow" class="kbf-settings-key" value="<?php echo esc_attr($didit_lv_wf); ?>" placeholder="workflow-id-..." autocomplete="off" autocapitalize="off" spellcheck="false">
                <small class="kbf-settings-helper">Stored: <?php echo $didit_lv_wf ? 'Yes' : 'No'; ?></small>
              </div>
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
            <label for="didit-wh-secret" style="font-size:12.5px;display:block;margin-bottom:4px;">Didit Webhook Secret</label>
            <div class="kbf-form-group" style="display:flex;flex-direction:row;align-items:center;gap:8px;">
              <input type="password" id="didit-wh-secret" class="kbf-settings-key" value="" placeholder="webhook-secret-..." autocomplete="off" autocapitalize="off" spellcheck="false">
            </div>
            <small style="color:var(--kbf-slate);display:block;margin-top:4px;">Stored: <?php echo $didit_wh_secret ? 'Yes' : 'No'; ?> (encrypted)</small>
            <small style="color:var(--kbf-slate);display:block;margin-top:6px;">
              When set, Fundora verifies incoming webhook signatures before updating verification status.
            </small>
          </div>
        </div>

      </div>
    </div>
    <!-- ================== JS ================== -->
    <script>
    window.kbfSettingsState = {
        demoMode: <?php echo $demo_mode ? '1' : '0'; ?>,
        feeDisabled: <?php echo $fee_disabled ? '1' : '0'; ?>
    };
    /**
     * @function  kbfRenderSettingsState
     * @purpose   Syncs the payment mode and platform fee badges/buttons with the current in-memory settings state.
     * @used-by   [called after toggles and once on initial script run]
     * @calls     [document.getElementById]
     * @params    [none]
     * @returns   [void]
     * @status    ACTIVE
     */
    window.kbfRenderSettingsState = function() {
        const st = window.kbfSettingsState || {demoMode: 1, feeDisabled: 0};
        const modeBadge = document.getElementById('kbf-payment-mode-badge');
        const modeBtn = document.getElementById('kbf-payment-mode-btn');
        if (modeBadge) {
            modeBadge.className = 'kbf-badge ' + (st.demoMode ? 'kbf-badge-holding' : 'kbf-badge-active');
            modeBadge.textContent = st.demoMode ? 'DEMO -- Auto-confirm' : 'LIVE -- Maya Checkout';
        }
        if (modeBtn) {
            modeBtn.className = 'kbf-btn ' + (st.demoMode ? 'kbf-btn-success' : 'kbf-btn-warning');
            modeBtn.textContent = st.demoMode ? 'Switch to Live' : 'Switch to Demo';
        }
        const feeBadge = document.getElementById('kbf-fee-badge');
        const feeBtn = document.getElementById('kbf-fee-btn');
        if (feeBadge) {
            feeBadge.className = 'kbf-badge ' + (st.feeDisabled ? 'kbf-badge-cancelled' : 'kbf-badge-active');
            feeBadge.textContent = st.feeDisabled ? 'DISABLED (0%)' : 'ENABLED (5%)';
        }
        if (feeBtn) {
            feeBtn.className = 'kbf-btn ' + (st.feeDisabled ? 'kbf-btn-accent' : 'kbf-btn-danger');
            feeBtn.textContent = st.feeDisabled ? 'Enable Fee' : 'Disable Fee';
        }
    };
    /**
     * @function  kbfToggleDemoMode
     * @purpose   Toggles demo mode in local UI state and re-renders the payment mode controls.
     * @used-by   [onclick on #kbf-payment-mode-btn]
     * @calls     [ev.preventDefault, ev.stopPropagation, window.kbfRenderSettingsState]
     * @params    [ev (Event) click event object]
     * @returns   [boolean false to prevent default button behavior]
     * @status    ACTIVE
     */
    window.kbfToggleDemoMode = function(ev) {
        if (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        }
        window.kbfSettingsState.demoMode = window.kbfSettingsState.demoMode ? 0 : 1;
        window.kbfRenderSettingsState();
        return false;
    };
    /**
     * @function  kbfTogglePlatformFee
     * @purpose   Toggles platform fee state in memory and re-renders the fee controls.
     * @used-by   [onclick on #kbf-fee-btn]
     * @calls     [ev.preventDefault, ev.stopPropagation, window.kbfRenderSettingsState]
     * @params    [ev (Event) click event object]
     * @returns   [boolean false to prevent default button behavior]
     * @status    ACTIVE
     */
    window.kbfTogglePlatformFee = function(ev) {
        if (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        }
        window.kbfSettingsState.feeDisabled = window.kbfSettingsState.feeDisabled ? 0 : 1;
        window.kbfRenderSettingsState();
        return false;
    };
    /**
     * @function  kbfSaveAllSettings
     * @purpose   Builds a batch settings payload from form/toggle state and saves it through admin AJAX.
     * @used-by   [onclick on top Save Changes button, alias call via window.kbfSaveTopMayaKeys]
     * @calls     [document.getElementById, FormData, JSON.stringify, fetch, setTimeout]
     * @params    [ev (Event) click event object]
     * @returns   [boolean false to prevent default button behavior]
     * @status    ACTIVE
     */
    window.kbfSaveAllSettings = function(ev){
        if (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        }
        const nonce = '<?php echo esc_js($nonce); ?>';
        const msg   = document.getElementById('kbf-settings-msg');
        const saveBtn = (ev && ev.currentTarget) ? ev.currentTarget : null;
        const pairs = [
            ['kbf_demo_mode', String(window.kbfSettingsState.demoMode ? 1 : 0)],
            ['kbf_disable_platform_fee', String(window.kbfSettingsState.feeDisabled ? 1 : 0)],
            ['kbf_maya_sandbox_public', document.getElementById('sb-pub').value],
            ['kbf_maya_sandbox_secret', document.getElementById('sb-sec').value],
            ['kbf_maya_live_public', document.getElementById('lv-pub').value],
            ['kbf_maya_live_secret', document.getElementById('lv-sec').value],
            ['kbf_maya_webhook_secret', document.getElementById('wh-secret').value],
            ['kbf_didit_sandbox_api_key', document.getElementById('didit-sb-key').value],
            ['kbf_didit_sandbox_app_id', document.getElementById('didit-sb-app-id').value],
            ['kbf_didit_sandbox_workflow_id', document.getElementById('didit-sb-workflow').value],
            ['kbf_didit_live_api_key', document.getElementById('didit-lv-key').value],
            ['kbf_didit_live_app_id', document.getElementById('didit-lv-app-id').value],
            ['kbf_didit_live_workflow_id', document.getElementById('didit-lv-workflow').value],
            ['kbf_didit_webhook_secret', document.getElementById('didit-wh-secret').value]
        ];
        const alwaysKeys = {'kbf_demo_mode': true, 'kbf_disable_platform_fee': true};
        /**
         * @function  shouldSave
         * @purpose   Determines whether a key/value pair should be included in the batch save payload.
         * @used-by   [called by pairs.filter inside kbfSaveAllSettings]
         * @calls     [String.trim]
         * @params    [key (string) setting key, val (any) candidate setting value]
         * @returns   [boolean true when value should be persisted]
         * @status    ACTIVE
         */
        const shouldSave = function(key, val) {
            if (alwaysKeys[key]) {
                return true;
            }
            if (typeof val !== 'string') {
                return !!val;
            }
            const trimmed = val.trim();
            if (trimmed === '__CLEAR__' || trimmed === '\\__CLEAR__') {
                return true;
            }
            return trimmed !== '';
        };
        const toSave = pairs.filter(([key, val]) => shouldSave(key, val));
        if (toSave.length === 0) {
            if (msg) {
                const nothingEl = document.createElement('div');
                nothingEl.className = 'kbf-alert kbf-alert-error kbf-alert-compact';
                nothingEl.textContent = 'Nothing to save.';
                msg.innerHTML = '';
                msg.appendChild(nothingEl);
                setTimeout(() => msg.innerHTML = '', 4000);
            }
            return false;
        }

        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
        }

        const ajaxUrl = (window.ajaxurl || '<?php echo esc_js($admin_ajax_url); ?>');
        const payload = {};
        toSave.forEach(([key, val]) => {
            if (typeof val === 'string' && val.trim() === '__CLEAR__') {
                payload[key] = '';
                return;
            }
            if (typeof val === 'string' && val.trim() === '\\__CLEAR__') {
                payload[key] = '__CLEAR__';
                return;
            }
            payload[key] = val;
        });

        const fd = new FormData();
        fd.append('action', 'kbf_save_settings_batch');
        fd.append('_ajax_nonce', nonce);
        fd.append('settings_json', JSON.stringify(payload));

        fetch(ajaxUrl, {method: 'POST', body: fd})
            .then(r => r.json())
            .then(j => {
                if (!j || !j.success) {
                    throw new Error((j && j.data && j.data.message) ? j.data.message : 'Failed to save settings.');
                }
                if (msg) {
                    const successEl = document.createElement('div');
                    successEl.className = 'kbf-alert kbf-alert-success kbf-alert-compact';
                    successEl.textContent = (j.data && j.data.message ? j.data.message : 'Settings saved successfully.');
                    msg.innerHTML = '';
                    msg.appendChild(successEl);
                    setTimeout(() => msg.innerHTML = '', 4000);
                }
            })
            .catch(() => {
                if (msg) {
                    const errEl = document.createElement('div');
                    errEl.className = 'kbf-alert kbf-alert-error kbf-alert-compact';
                    errEl.textContent = 'Failed to save settings. Please try again.';
                    msg.innerHTML = '';
                    msg.appendChild(errEl);
                    setTimeout(() => msg.innerHTML = '', 5000);
                }
            })
            .finally(() => {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Changes';
                }
            });

        return false;
    };
    // Backward-compat alias for any stale inline/on-page references.
    window.kbfSaveTopMayaKeys = window.kbfSaveAllSettings;
    window.kbfRenderSettingsState();
    </script>
    <?php return ob_get_clean();
}



