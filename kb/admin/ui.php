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
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <?php if(false): ?><div></div><?php endif; ?>
    <div class="kbf-wrap kbf-admin-wrap">
    <?php
    $pending_count_admin = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_funds WHERE status='pending'"); // phpcs:ignore
    $open_reports_count  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_reports WHERE status='open'"); // phpcs:ignore
    $pending_wd_count    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_withdrawals WHERE status='pending'"); // phpcs:ignore
    $open_appeals_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_appeals WHERE status='open'"); // phpcs:ignore
    $tabs=['pending'=>'For Review','all_funds'=>'Fundraisers','transactions'=>'Payments','withdrawals'=>'Cashouts','reports'=>'Reports','appeals'=>'Appeals','organizers'=>'Accounts','settings'=>'Settings'];
    $counts=['pending'=>$pending_count_admin,'reports'=>$open_reports_count,'withdrawals'=>$pending_wd_count,'appeals'=>$open_appeals_count];
    ?>
    <div class="kbf-dashboard-topbar">
      <div class="kbf-dashboard-brand">
        <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="ambag" style="width:22px;height:22px;object-fit:contain;border-radius:6px;">
        <span class="kbf-brand-text">ambag</span>
      </div>
      <button class="kbf-hamburger" type="button" onclick="kbfToggleMobileMenu()" aria-label="Toggle menu">
        <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/list.svg" alt="">
      </button>
      <div class="kbf-dashboard-nav" id="kbf-admin-nav">
        <?php foreach($tabs as $k=>$label): ?>
        <?php $raw_count = !empty($counts[$k]) ? (int)$counts[$k] : 0; ?>
        <?php $display_count = $raw_count >= 100 ? '99+' : (string)$raw_count; ?>
        <a href="?adm_tab=<?php echo $k; ?>" class="<?php echo $tab===$k?'active':''; ?>">
          <?php echo $label; ?>
          <?php if($raw_count > 0): ?>
            <span class="kbf-nav-count"><?php echo esc_html($display_count); ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="kbf-admin-shell">
    <div class="kbf-page-header"><h2>Ambag Admin Panel</h2><p>Moderate funds, manage escrow, review reports, and process withdrawals.</p></div>
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
    </div>
    <div class="kbf-mobile-overlay" id="kbf-mobile-overlay"></div>
    <div class="kbf-mobile-menu" id="kbf-mobile-menu">
      <div class="kbf-mobile-menu-header">
        <div class="kbf-dashboard-brand">
          <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="ambag" style="width:22px;height:22px;object-fit:contain;border-radius:6px;">
          <span class="kbf-brand-text">ambag</span>
        </div>
        <button class="kbf-hamburger" type="button" onclick="kbfCloseMobileMenu()">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/x-lg.svg" alt="">
        </button>
      </div>
      <?php foreach($tabs as $k=>$label): ?>
        <?php $raw_count = !empty($counts[$k]) ? (int)$counts[$k] : 0; ?>
        <?php $display_count = $raw_count >= 100 ? '99+' : (string)$raw_count; ?>
        <a href="?adm_tab=<?php echo $k; ?>" class="<?php echo $tab===$k?'active':''; ?>" onclick="kbfCloseMobileMenu()">
          <?php echo $label; ?>
          <?php if($raw_count > 0): ?>
            <span class="kbf-nav-count"><?php echo esc_html($display_count); ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

       <!-- ================== JS ================== -->
    <script>
    var ajaxurl = window.ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>';
    var _kbfAdminNonce='<?php echo $nonce; ?>';
    window.kbfSetLoadingPage = function(on){
        var el = document.getElementById('kbf-loading-overlay');
        if(!el) return;
        el.style.display = on ? 'flex' : 'none';
    };
    window.kbfAdmin=function(action,params){
        const fd=new FormData();fd.append('action',action);fd.append('_ajax_nonce',_kbfAdminNonce);
        Object.keys(params).forEach(k=>fd.append(k,params[k]));
        return fetch(ajaxurl,{method:'POST',body:fd}).then(async r=>{
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
    window.kbfApprove=function(id){if(!confirm('Approve this fund?'))return;kbfAdmin('kbf_admin_approve_fund',{fund_id:id});};
    window.kbfReject=function(id){const r=prompt('Reason for rejection (optional):');if(r===null)return;kbfAdmin('kbf_admin_reject_fund',{fund_id:id,reason:r});};
    window.kbfSuspend=function(id){if(!confirm('Suspend this fund?'))return;kbfAdmin('kbf_admin_suspend_fund',{fund_id:id});};
    window.kbfVerifyBadge=function(id,cur){kbfAdmin('kbf_admin_verify_badge',{fund_id:id,verified:cur?'0':'1'});};
    window.kbfEscrow=function(id,act){kbfAdmin('kbf_admin_'+act+'_escrow',{fund_id:id});};
        window.kbfDismissReport=function(id){kbfAdmin('kbf_admin_dismiss_report',{report_id:id});};
        window.kbfReviewAppeal=function(id,action){const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_appeal',{appeal_id:id,action_type:action,notes:n});};
    window.kbfProcessWd=function(id,type){
        if(type==='reject'){const r=prompt('Reason for rejection:');if(!r)return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'reject',notes:r});}
        else{if(!confirm('Approve & release this withdrawal?'))return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'approve'});}
    };
    window.kbfConfirmPayment=function(id){if(!confirm('Mark this sponsorship as paid?'))return;kbfAdmin('kbf_admin_confirm_payment',{sponsorship_id:id});};
    function kbfToggleMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (!menu || !overlay) return;
        menu.classList.toggle('kbf-menu-open');
        overlay.classList.toggle('kbf-overlay-open');
    }
    function kbfCloseMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (menu) menu.classList.remove('kbf-menu-open');
        if (overlay) overlay.classList.remove('kbf-overlay-open');
    }
    window.kbfToggleMobileMenu = kbfToggleMobileMenu;
    window.kbfCloseMobileMenu = kbfCloseMobileMenu;
    window.addEventListener('resize', function(){
        if (window.innerWidth > 900) kbfCloseMobileMenu();
    });
    document.addEventListener('click', function(e){
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (overlay && e.target === overlay) {
            kbfCloseMobileMenu();
        }
    });
window.kbfVerifyOrg=function(id,verified){
    var v = parseInt(verified,10) ? 1 : 0;
    var notes = '';
    if(v === 0){
        notes = prompt('Reason for rejection (required):','') || '';
        notes = notes.trim();
        if(!notes){ return; }
    }
    kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:v,notes:notes});
};
window.kbfTriggerOnboarding=function(id){
    if(!confirm('Restart onboarding for this account?')) return;
    kbfAdmin('kbf_admin_trigger_onboarding',{business_id:id});
};
    </script>
    <?php
    $c=ob_get_clean();
    return bntm_universal_container('KonekBayan Admin Panel',$c, ['show_topbar'=>false,'show_header'=>false,'wrap'=>false]);
}
