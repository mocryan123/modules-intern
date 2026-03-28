<?php
/* Admin embed in user dashboard */
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
    $pending_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_funds WHERE status='pending'"); // phpcs:ignore
    $reports_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_reports WHERE status='open'"); // phpcs:ignore
    $wd_count      = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_withdrawals WHERE status='pending'"); // phpcs:ignore
    $counts = ['pending'=>$pending_count,'reports'=>$reports_count,'withdrawals'=>$wd_count];
    $adm_tabs = ['pending'=>'Pending Funds','all_funds'=>'All Funds','transactions'=>'Transactions','withdrawals'=>'Withdrawals','reports'=>'Reports','appeals'=>'Appeals','organizers'=>'Organizers','settings'=>'Settings'];
    ?>

    <!-- ================== HTML ================== -->
    <div style="margin:-28px -28px 0;background:var(--kbf-navy);border-radius:0;">
      <div style="padding:20px 28px 0;display:flex;align-items:center;gap:10px;">
        <svg width="16" height="16" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
        <span style="color:#fff;font-weight:700;font-size:15px;">Admin Panel</span>
      </div>
      <div class="kbf-tabs" style="border-radius:0;padding:0 28px;">
        <?php foreach($adm_tabs as $k=>$label): ?>
        <a href="?kbf_tab=admin&adm_tab=<?php echo $k; ?>" class="kbf-tab <?php echo $adm_tab===$k?'active':''; ?>">
          <?php echo $label; ?>
          <?php if(!empty($counts[$k])&&$counts[$k]>0): ?>
            <span style="background:var(--kbf-red);color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;font-weight:800;line-height:1.5;"><?php echo $counts[$k]; ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div style="margin-top:24px;">
      <?php
      if     ($adm_tab==='pending')      echo kbf_admin_pending_tab();
      elseif ($adm_tab==='all_funds')    echo kbf_admin_all_funds_tab();
      elseif ($adm_tab==='transactions') echo kbf_admin_transactions_tab();
      elseif ($adm_tab==='withdrawals')  echo kbf_admin_withdrawals_tab();
      elseif ($adm_tab==='reports')      echo kbf_admin_reports_tab();
      elseif ($adm_tab==='appeals')      echo kbf_admin_appeals_tab();
      elseif ($adm_tab==='organizers')   echo kbf_admin_organizers_tab();
      elseif ($adm_tab==='settings')     echo kbf_admin_settings_tab();
      ?>
    </div>

    <!-- ================== JS ================== -->
    <script>
    var _kbfAdminNonce='<?php echo $nonce; ?>';
    if(typeof window.kbfAdmin==='undefined'){
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
                console.log('kbfAdmin action:', action, 'params:', params);
            });
        };
        window.kbfApprove     = function(id){if(!confirm('Approve this fund?'))return;kbfAdmin('kbf_admin_approve_fund',{fund_id:id});};
        window.kbfReject      = function(id){const r=prompt('Reason for rejection (optional):');if(r===null)return;kbfAdmin('kbf_admin_reject_fund',{fund_id:id,reason:r});};
        window.kbfSuspend     = function(id){if(!confirm('Suspend this fund?'))return;kbfAdmin('kbf_admin_suspend_fund',{fund_id:id});};
        window.kbfVerifyBadge = function(id,cur){kbfAdmin('kbf_admin_verify_badge',{fund_id:id,verified:cur?'0':'1'});};
        window.kbfEscrow      = function(id,act){kbfAdmin('kbf_admin_'+act+'_escrow',{fund_id:id});};
        window.kbfDismissReport  = function(id){kbfAdmin('kbf_admin_dismiss_report',{report_id:id});};
        window.kbfReviewAppeal   = function(id,action){const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_appeal',{appeal_id:id,action_type:action,notes:n});};
        window.kbfProcessWd      = function(id,type){if(type==='reject'){const r=prompt('Reason:');if(!r)return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'reject',notes:r});}else{if(!confirm('Approve & release?'))return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'approve'});}};
        window.kbfConfirmPayment = function(id){if(!confirm('Mark as paid?'))return;kbfAdmin('kbf_admin_confirm_payment',{sponsorship_id:id});};
        window.kbfVerifyOrg      = function(id,cur){kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:cur?'0':'1'});};
    } else {
        // Already defined -- just refresh the nonce value
        _kbfAdminNonce = '<?php echo $nonce; ?>';
    }
    </script>

    <?php
    return ob_get_clean();
}
