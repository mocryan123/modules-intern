<?php
/*
 * KBF admin UI rendering and shortcode output.
 */

if (!defined('ABSPATH')) exit;

function bntm_shortcode_kbf_admin() {
    if(!current_user_can('manage_options')) return '<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Access denied.</div></div>';
    kbf_global_assets();
    global $wpdb;
    $tab = isset($_GET['adm_tab'])?sanitize_text_field($_GET['adm_tab']):'pending';
    $nonce = wp_create_nonce('kbf_admin_action');
    ob_start(); ?>
    <script>
    var ajaxurl = window.ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>';
    var _kbfAdminNonce='<?php echo $nonce; ?>';
    window.kbfAdmin=function(action,params){
        const fd=new FormData();fd.append('action',action);fd.append('_ajax_nonce',_kbfAdminNonce);
        Object.keys(params).forEach(k=>fd.append(k,params[k]));
        return fetch(ajaxurl,{method:'POST',body:fd}).then(async r=>{
            const text = await r.text();
            let j = null;
            try { j = JSON.parse(text); } catch(e) {}
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
    window.kbfApprove=function(id){if(!confirm('Approve this fund?'))return;kbfAdmin('kbf_admin_approve_fund',{fund_id:id});};
    window.kbfReject=function(id){const r=prompt('Reason for rejection (optional):');if(r===null)return;kbfAdmin('kbf_admin_reject_fund',{fund_id:id,reason:r});};
    window.kbfSuspend=function(id){if(!confirm('Suspend this fund?'))return;kbfAdmin('kbf_admin_suspend_fund',{fund_id:id});};
    window.kbfVerifyBadge=function(id,cur){kbfAdmin('kbf_admin_verify_badge',{fund_id:id,verified:cur?'0':'1'});};
    window.kbfEscrow=function(id,act){kbfAdmin('kbf_admin_'+act+'_escrow',{fund_id:id});};
        window.kbfDismissReport=function(id){kbfAdmin('kbf_admin_dismiss_report',{report_id:id});};
        window.kbfReviewReport=function(id){const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_report',{report_id:id,notes:n});};
        window.kbfReviewAppeal=function(id,action){const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_appeal',{appeal_id:id,action_type:action,notes:n});};
    window.kbfProcessWd=function(id,type){
        if(type==='reject'){const r=prompt('Reason for rejection:');if(!r)return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'reject',notes:r});}
        else{if(!confirm('Approve & release this withdrawal?'))return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'approve'});}
    };
    window.kbfConfirmPayment=function(id){if(!confirm('Mark this sponsorship as paid?'))return;kbfAdmin('kbf_admin_confirm_payment',{sponsorship_id:id});};
    window.kbfVerifyOrg=function(id,cur){kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:cur?'0':'1'});};
    </script>
    <div class="kbf-wrap">
    <div class="kbf-page-header"><h2>KonekBayan Admin Panel</h2><p>Moderate funds, manage escrow, review reports, and process withdrawals.</p></div>

    <div class="kbf-tabs">
      <?php
      $pending_count_admin = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_funds WHERE status='pending'"); // phpcs:ignore
      $open_reports_count  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_reports WHERE status='open'"); // phpcs:ignore
      $pending_wd_count    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_withdrawals WHERE status='pending'"); // phpcs:ignore
      $open_appeals_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_appeals WHERE status='open'"); // phpcs:ignore
      $tabs=['pending'=>'Pending','all_funds'=>'All Funds','transactions'=>'Transactions','withdrawals'=>'Withdrawals','reports'=>'Reports','appeals'=>'Appeals','organizers'=>'Organizers','settings'=>'Settings'];
      $counts=['pending'=>$pending_count_admin,'reports'=>$open_reports_count,'withdrawals'=>$pending_wd_count,'appeals'=>$open_appeals_count];
      foreach($tabs as $k=>$label): ?>
      <a href="?adm_tab=<?php echo $k; ?>" class="kbf-tab <?php echo $tab===$k?'active':''; ?>">
        <?php echo $label; ?>
        <?php if(!empty($counts[$k]) && $counts[$k]>0): ?>
          <span style="background:var(--kbf-red);color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;font-weight:800;line-height:1.5;"><?php echo $counts[$k]; ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>

    <?php echo kbf_role_nav('admin'); ?>
    <div class="kbf-tab-content">
      <?php
      if($tab==='pending')      echo kbf_admin_pending_tab();
      elseif($tab==='all_funds')     echo kbf_admin_all_funds_tab();
      elseif($tab==='transactions')  echo kbf_admin_transactions_tab();
      elseif($tab==='withdrawals')   echo kbf_admin_withdrawals_tab();
      elseif($tab==='reports')       echo kbf_admin_reports_tab();
      elseif($tab==='appeals')       echo kbf_admin_appeals_tab();
      elseif($tab==='organizers')    echo kbf_admin_organizers_tab();
      elseif($tab==='settings')      echo kbf_admin_settings_tab();
      ?>
    </div>
    </div>
    <?php
    $c=ob_get_clean();
    return bntm_universal_container('KonekBayan Admin Panel',$c, ['show_topbar'=>false,'show_header'=>false]);
}



// ============================================================
// AJAX HANDLERS -- ADMIN
// ============================================================

