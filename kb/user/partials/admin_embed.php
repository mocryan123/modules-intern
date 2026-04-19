<?php
/* Admin embed in user dashboard */
if (!function_exists('kbf_admin_embed_tabs')) {
    /**
     * @function  kbf_admin_embed_tabs
     * @purpose   Returns the admin embed tab slug-to-label map.
     * @used-by   [kbf_dashboard_admin_embed]
     * @calls     [none]
     * @params    [none]
     * @returns   [array associative list of admin tabs]
     * @status    ACTIVE
     */
    function kbf_admin_embed_tabs() {
        return [
            'pending'      => 'Pending Funds',
            'all_funds'    => 'All Funds',
            'transactions' => 'Transactions',
            'withdrawals'  => 'Withdrawals',
            'reports'      => 'Reports',
            'appeals'      => 'Appeals',
            'organizers'   => 'Organizers',
            'settings'     => 'Settings',
        ];
    }
}

if (!function_exists('kbf_admin_embed_counts')) {
    /**
     * @function  kbf_admin_embed_counts
     * @purpose   Collects pending count badges for key admin sections.
     * @used-by   [kbf_dashboard_admin_embed]
     * @calls     [$wpdb->get_var]
     * @params    [$wpdb (wpdb) WordPress database object]
     * @returns   [array pending/report/withdrawal badge counts]
     * @status    ACTIVE
     */
    function kbf_admin_embed_counts($wpdb) {
        $prefix = $wpdb->prefix;
        $pending = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}kbf_funds WHERE status='pending'"); // phpcs:ignore
        $reports = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}kbf_reports WHERE status='open'"); // phpcs:ignore
        $wd = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}kbf_withdrawals WHERE status='pending'"); // phpcs:ignore
        return ['pending'=>$pending,'reports'=>$reports,'withdrawals'=>$wd];
    }
}

if (!function_exists('kbf_admin_embed_render_tab')) {
    /**
     * @function  kbf_admin_embed_render_tab
     * @purpose   Routes the selected admin tab slug to its renderer output.
     * @used-by   [kbf_dashboard_admin_embed]
     * @calls     [kbf_admin_pending_tab, kbf_admin_all_funds_tab, kbf_admin_transactions_tab, kbf_admin_withdrawals_tab, kbf_admin_reports_tab, kbf_admin_appeals_tab, kbf_admin_organizers_tab, kbf_admin_settings_tab]
     * @params    [$adm_tab (string) selected admin tab slug]
     * @returns   [string rendered tab markup or empty string]
     * @status    ACTIVE
     */
    function kbf_admin_embed_render_tab($adm_tab) {
        if ($adm_tab === 'pending' && function_exists('kbf_admin_pending_tab'))      return kbf_admin_pending_tab();
        if ($adm_tab === 'all_funds' && function_exists('kbf_admin_all_funds_tab'))    return kbf_admin_all_funds_tab();
        if ($adm_tab === 'transactions' && function_exists('kbf_admin_transactions_tab')) return kbf_admin_transactions_tab();
        if ($adm_tab === 'withdrawals' && function_exists('kbf_admin_withdrawals_tab'))  return kbf_admin_withdrawals_tab();
        if ($adm_tab === 'reports' && function_exists('kbf_admin_reports_tab'))      return kbf_admin_reports_tab();
        if ($adm_tab === 'appeals' && function_exists('kbf_admin_appeals_tab'))      return kbf_admin_appeals_tab();
        if ($adm_tab === 'organizers' && function_exists('kbf_admin_organizers_tab'))   return kbf_admin_organizers_tab();
        if ($adm_tab === 'settings' && function_exists('kbf_admin_settings_tab'))     return kbf_admin_settings_tab();
        return '';
    }
}

/**
 * @function  kbf_dashboard_admin_embed
 * @purpose   Renders the admin-panel embed inside the user dashboard for administrators.
 * @used-by   [NEEDS REVIEW: no direct caller found in current file scan]
 * @calls     [current_user_can, sanitize_text_field, wp_create_nonce, kbf_admin_embed_counts, kbf_admin_embed_tabs, kbf_admin_embed_render_tab, admin_url, ob_start, ob_get_clean]
 * @params    [none]
 * @returns   [string rendered admin embed HTML or empty string]
 * @status    NEEDS REVIEW
 */
function kbf_dashboard_admin_embed() {
    if (!current_user_can('manage_options')) return '';
    // Reuse all admin tab functions directly -- they share the same JS
    // already loaded by bntm_shortcode_kbf_admin, but here we need
    // to output the admin JS inline since we're inside the dashboard.
    global $wpdb;
    $adm_tab = isset($_GET['adm_tab']) ? sanitize_text_field($_GET['adm_tab']) : 'pending';
    $nonce   = wp_create_nonce('kbf_admin_action');

    ob_start();
    ?>

    <?php
    $counts = kbf_admin_embed_counts($wpdb);
    $adm_tabs = kbf_admin_embed_tabs();
    ?>

    <!-- ================== HTML ================== -->
    <div style="margin:-28px -28px 0;background:var(--kbf-navy);border-radius:0;">
      <div style="padding:20px 28px 0;display:flex;align-items:center;gap:10px;">
        <svg width="16" height="16" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
        <span style="color:#fff;font-weight:700;font-size:15px;">Admin Panel</span>
      </div>
      <div class="kbf-tabs" style="border-radius:0;padding:0 28px;">
        <?php foreach($adm_tabs as $k=>$label): ?>
        <a href="?kbf_tab=admin&adm_tab=<?php echo esc_attr($k); ?>" class="kbf-tab <?php echo $adm_tab===$k?'active':''; ?>">
          <?php echo esc_html($label); ?>
          <?php if(!empty($counts[$k])&&$counts[$k]>0): ?>
            <span style="background:var(--kbf-red);color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;font-weight:800;line-height:1.5;"><?php echo (int)$counts[$k]; ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div style="margin-top:24px;">
      <?php
      echo kbf_admin_embed_render_tab($adm_tab);
      ?>
    </div>

    <!-- ================== JS ================== -->
    <script>
    var _kbfAdminNonce='<?php echo esc_js($nonce); ?>';
    if(typeof window.kbfAdmin==='undefined'){
        /**
         * @function  kbfAdmin
         * @purpose   Sends admin AJAX actions with nonce and handles standardized response UI.
         * @used-by   [kbfApprove, kbfReject, kbfSuspend, kbfVerifyBadge, kbfEscrow, kbfDismissReport, kbfReviewAppeal, kbfProcessWd, kbfConfirmPayment, kbfVerifyOrg]
         * @calls     [FormData, fetch, JSON.parse, Object.keys, alert, location.reload, console.error, console.log]
         * @params    [action (string) admin AJAX action name, params (object) action payload params]
         * @returns   [Promise<void> async request lifecycle promise]
         * @status    ACTIVE
         */
        window.kbfAdmin=function(action,params){
            const fd=new FormData();fd.append('action',action);fd.append('_ajax_nonce',_kbfAdminNonce);
            Object.keys(params).forEach(k=>fd.append(k,params[k]));
            return fetch((window.ajaxurl||'<?php echo admin_url('admin-ajax.php'); ?>'),{method:'POST',body:fd})
            .then(async r=>{
                const text = await r.text();
                let j = null;
                try {
                    const cleaned = String(text || '').trim();
                    const start = cleaned.indexOf('{');
                    const end = cleaned.lastIndexOf('}');
                    const payload = (start !== -1 && end !== -1 && end > start) ? cleaned.slice(start, end + 1) : cleaned;
                    j = JSON.parse(payload);
                } catch(e) {}
                if(!j){
                    console.error('kbfAdmin raw response:', text);
                    throw new Error('Server returned non-JSON response.');
                }
                return j;
            }).then(j=>{
                alert((j.data&&j.data.message)?j.data.message:(j.data||'Done.'));
                if(j.success)location.reload();
            }).catch(err=>{
                alert('Request failed. Please try again.');
                console.error('kbfAdmin error:', err);
            });
        };
        /**
         * @function  kbfApprove
         * @purpose   Confirms and submits fund approval from admin actions UI.
         * @used-by   [onclick handlers in admin fund tabs]
         * @calls     [confirm, kbfAdmin]
         * @params    [id (number|string) fund ID]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfApprove     = function(id){if(!confirm('Approve this fund?'))return;kbfAdmin('kbf_admin_approve_fund',{fund_id:id});};
        /**
         * @function  kbfReject
         * @purpose   Prompts for reject reason and submits fund rejection.
         * @used-by   [onclick handlers in admin fund tabs]
         * @calls     [prompt, kbfAdmin]
         * @params    [id (number|string) fund ID]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfReject      = function(id){const r=prompt('Reason for rejection (optional):');if(r===null)return;kbfAdmin('kbf_admin_reject_fund',{fund_id:id,reason:r});};
        /**
         * @function  kbfSuspend
         * @purpose   Confirms and submits fund suspension from admin actions UI.
         * @used-by   [onclick handlers in reports/admin tables]
         * @calls     [confirm, kbfAdmin]
         * @params    [id (number|string) fund ID]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfSuspend     = function(id){if(!confirm('Suspend this fund?'))return;kbfAdmin('kbf_admin_suspend_fund',{fund_id:id});};
        /**
         * @function  kbfVerifyBadge
         * @purpose   Toggles a fund verification badge state.
         * @used-by   [onclick handlers in admin fund rows]
         * @calls     [kbfAdmin]
         * @params    [id (number|string) fund ID, cur (boolean|number|string) current state flag]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfVerifyBadge = function(id,cur){kbfAdmin('kbf_admin_verify_badge',{fund_id:id,verified:cur?'0':'1'});};
        /**
         * @function  kbfEscrow
         * @purpose   Submits escrow hold/release action for a fund.
         * @used-by   [onclick handlers in admin fundraisers table]
         * @calls     [kbfAdmin]
         * @params    [id (number|string) fund ID, act (string) escrow action suffix]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfEscrow      = function(id,act){kbfAdmin('kbf_admin_'+act+'_escrow',{fund_id:id});};
        /**
         * @function  kbfDismissReport
         * @purpose   Submits dismissal of an abuse report item.
         * @used-by   [onclick handlers in admin reports tab]
         * @calls     [kbfAdmin]
         * @params    [id (number|string) report ID]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfDismissReport  = function(id){kbfAdmin('kbf_admin_dismiss_report',{report_id:id});};
        /**
         * @function  kbfReviewAppeal
         * @purpose   Prompts for notes and submits an appeal review decision.
         * @used-by   [onclick handlers in admin appeals tab]
         * @calls     [prompt, kbfAdmin]
         * @params    [id (number|string) appeal ID, action (string) approve/reject action type]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfReviewAppeal   = function(id,action){const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_appeal',{appeal_id:id,action_type:action,notes:n});};
        /**
         * @function  kbfProcessWd
         * @purpose   Handles withdrawal approval or rejection submission flow.
         * @used-by   [onclick handlers in admin cashouts tab]
         * @calls     [prompt, confirm, kbfAdmin]
         * @params    [id (number|string) withdrawal ID, type (string) approve/reject mode]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfProcessWd      = function(id,type){if(type==='reject'){const r=prompt('Reason:');if(!r)return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'reject',notes:r});}else{if(!confirm('Approve & release?'))return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'approve'});}};
        /**
         * @function  kbfConfirmPayment
         * @purpose   Confirms and submits sponsorship payment confirmation action.
         * @used-by   [onclick handlers in admin transactions tab]
         * @calls     [confirm, kbfAdmin]
         * @params    [id (number|string) sponsorship ID]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfConfirmPayment = function(id){if(!confirm('Mark as paid?'))return;kbfAdmin('kbf_admin_confirm_payment',{sponsorship_id:id});};
        /**
         * @function  kbfVerifyOrg
         * @purpose   Toggles organizer verification state from admin actions UI.
         * @used-by   [onclick handlers in admin organizers/accounts tab]
         * @calls     [kbfAdmin]
         * @params    [id (number|string) organizer business ID, cur (boolean|number|string) current verify state]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfVerifyOrg      = function(id,cur){kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:cur?'0':'1'});};
    } else {
        // Already defined -- just refresh the nonce value
        _kbfAdminNonce = '<?php echo esc_js($nonce); ?>';
    }
    </script>

    <?php
    return ob_get_clean();
}
