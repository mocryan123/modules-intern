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
    $tabs=['pending'=>'For Review','all_funds'=>'Fundraisers','transactions'=>'Payments','withdrawals'=>'Cashouts','reports'=>'Reports','appeals'=>'Appeals','organizers'=>'Accounts','security'=>'Security Logs','settings'=>'Settings'];
    $counts=['pending'=>$pending_count_admin,'reports'=>$open_reports_count,'withdrawals'=>$pending_wd_count,'appeals'=>$open_appeals_count];
    ?>
    <div class="kbf-dashboard-topbar kbf-admin-topbar">
      <div class="kbf-dashboard-brand">
        <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora" style="width:22px;height:22px;object-fit:contain;border-radius:6px;">
        <span class="kbf-brand-text">fundora</span>
      </div>
      <button class="kbf-hamburger" type="button" onclick="kbfToggleMobileMenu()" aria-label="Toggle menu">
        <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/list.svg" alt="">
      </button>
    </div>

    <div class="kbf-admin-layout">
      <aside class="kbf-admin-sidebar">
        <div class="kbf-admin-sidebar-brand">
          <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora">
          <span class="kbf-brand-text">fundora</span>
        </div>
        <div class="kbf-admin-sidebar-label">Admin Navigation</div>
        <nav class="kbf-dashboard-nav kbf-admin-nav" id="kbf-admin-nav">
          <?php foreach($tabs as $k=>$label): ?>
          <?php $raw_count = !empty($counts[$k]) ? (int)$counts[$k] : 0; ?>
          <?php $display_count = $raw_count >= 100 ? '99+' : (string)$raw_count; ?>
          <a href="?adm_tab=<?php echo $k; ?>" class="<?php echo $tab===$k?'active':''; ?>" data-kbf-adm-tab="<?php echo esc_attr($k); ?>">
            <?php echo $label; ?>
            <?php if($raw_count > 0): ?>
              <span class="kbf-nav-count"><?php echo esc_html($display_count); ?></span>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        </nav>
        <div class="kbf-admin-sidebar-note">Fundora Admin</div>
      </aside>

      <main class="kbf-admin-main">
        <div class="kbf-admin-shell">
          <div class="kbf-page-header"><h2>fundora Admin Panel</h2><p>Moderate funds, manage escrow, review reports, and process withdrawals.</p></div>
          <div class="kbf-tab-content">
            <?php
            if($tab==='pending')      echo kbf_admin_pending_tab();
            elseif($tab==='all_funds')     echo kbf_admin_all_funds_tab();
            elseif($tab==='transactions')  echo kbf_admin_transactions_tab();
            elseif($tab==='withdrawals')   echo kbf_admin_withdrawals_tab();
            elseif($tab==='reports')       echo kbf_admin_reports_tab();
            elseif($tab==='appeals')       echo kbf_admin_appeals_tab();
            elseif($tab==='organizers')    echo kbf_admin_organizers_tab();
            elseif($tab==='security')      echo kbf_admin_security_logs_tab();
            elseif($tab==='settings')      echo kbf_admin_settings_tab();
            ?>
          </div>
        </div>
      </main>
    </div>
    </div>
    <div class="kbf-mobile-overlay" id="kbf-mobile-overlay"></div>
    <div class="kbf-mobile-menu" id="kbf-mobile-menu">
      <div class="kbf-mobile-menu-header" style="justify-content:flex-end;">
        <button class="kbf-hamburger" type="button" onclick="kbfCloseMobileMenu()" aria-label="Close menu">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/x-lg.svg" alt="">
        </button>
      </div>
      <?php foreach($tabs as $k=>$label): ?>
        <?php $raw_count = !empty($counts[$k]) ? (int)$counts[$k] : 0; ?>
        <?php $display_count = $raw_count >= 100 ? '99+' : (string)$raw_count; ?>
        <a href="?adm_tab=<?php echo $k; ?>" class="<?php echo $tab===$k?'active':''; ?>" data-kbf-adm-tab="<?php echo esc_attr($k); ?>" onclick="kbfCloseMobileMenu()">
          <?php echo $label; ?>
          <?php if($raw_count > 0): ?>
            <span class="kbf-nav-count"><?php echo esc_html($display_count); ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="kbf-modal-overlay kbf-admin-reject-modal" id="kbf-admin-reject-modal" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3 id="kbf-admin-reject-title">Provide Reason</h3>
          <button class="kbf-modal-close" type="button" onclick="kbfCloseAdminRejectModal()" aria-label="Close">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <div class="kbf-form-group">
            <label for="kbf-admin-reject-template">Pre‑Generated Message</label>
            <select id="kbf-admin-reject-template">
              <option value="">Select a reason...</option>
            </select>
          </div>
          <div class="kbf-form-group">
            <label for="kbf-admin-reject-notes">Reason (required)</label>
            <textarea id="kbf-admin-reject-notes" placeholder="Write the reason for rejection..."></textarea>
            <small style="color:var(--kbf-slate);display:block;margin-top:6px;">This message will be shown to the user.</small>
          </div>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" type="button" onclick="kbfCloseAdminRejectModal()">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" type="button" onclick="kbfSubmitAdminReject()">Submit Rejection</button>
        </div>
      </div>
    </div>

       <!-- ================== JS ================== -->
    <script>
    var ajaxurl = window.ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>';
    var _kbfAdminNonce='<?php echo $nonce; ?>';
    var kbfAdminTab = '<?php echo esc_js($tab); ?>';
    var kbfAdminAutoRefresh = <?php echo $tab === 'settings' ? 'false' : 'true'; ?>;
    var kbfAdminRefreshInterval = 25000;
    var kbfAdminRefreshTimer = null;
    function kbfAdminStartRefresh(){
        if (!kbfAdminAutoRefresh) return;
        if (kbfAdminRefreshTimer) clearInterval(kbfAdminRefreshTimer);
        kbfAdminRefreshTimer = setInterval(kbfAdminRefreshTab, kbfAdminRefreshInterval);
    }
    function kbfAdminStopRefresh(){
        if (!kbfAdminRefreshTimer) return;
        clearInterval(kbfAdminRefreshTimer);
        kbfAdminRefreshTimer = null;
    }
    var kbfRejectState = { id: null, btn: null };
    var kbfAdminRejectState = { context: '', params: null };
    var kbfAdminRejectTemplates = {
        fund: [
            'Insufficient details provided. Please add more context and resubmit.',
            'Fund description does not meet our guidelines. Please update and resubmit.',
            'Uploaded images are unclear or incomplete. Please provide clearer photos.',
            'Fund category or information appears incorrect. Please revise and resubmit.',
            'Other'
        ],
        withdrawal: [
            'Withdrawal details are incomplete. Please update your payout info.',
            'Account details do not match our records. Please verify and resubmit.',
            'Withdrawal request exceeds available balance.',
            'Pending compliance review. Please contact support.',
            'Other'
        ],
        escrow: [
            'Escrow request is not eligible at this time.',
            'Fund deadline has not passed. Please request after the deadline.',
            'Required documentation is missing. Please update and resubmit.',
            'Other'
        ],
        appeal: [
            'Appeal does not meet reinstatement criteria at this time.',
            'Provided explanation is insufficient. Please add more details.',
            'Evidence submitted does not support reinstatement.',
            'Other'
        ]
    };
    window.kbfSetLoadingPage = function(on){
        var el = document.getElementById('kbf-loading-overlay');
        if(!el) return;
        el.style.display = on ? 'flex' : 'none';
    };
    window.kbfAdmin=function(action,params,opts){
        opts = opts || {};
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
            if (opts.onSuccess) { opts.onSuccess(j); }
            alert((j.data&&j.data.message)?j.data.message:(j.data||'Done.'));
            if(j.success && !opts.noReload)location.reload();
        }).catch(err=>{
            alert('Request failed. Please try again.');
            console.error('kbfAdmin error:', err);
            console.log('kbfAdmin action:', action, 'params:', params);
        });
    };
    window.kbfOpenAdminRejectModal = function(context, params){
        var modal = document.getElementById('kbf-admin-reject-modal');
        var title = document.getElementById('kbf-admin-reject-title');
        var select = document.getElementById('kbf-admin-reject-template');
        var notes = document.getElementById('kbf-admin-reject-notes');
        if (!modal || !select || !notes) return;
        kbfAdminRejectState.context = context || '';
        kbfAdminRejectState.params = params || null;
        if (title) {
            var label = context === 'fund' ? 'Reject Fund' :
                (context === 'withdrawal' ? 'Reject Withdrawal' :
                (context === 'escrow' ? 'Reject Escrow Request' :
                (context === 'appeal' ? 'Reject Appeal' : 'Provide Reason')));
            title.textContent = label;
        }
        select.innerHTML = '<option value="">Select a reason...</option>';
        var list = kbfAdminRejectTemplates[context] || ['Other'];
        list.forEach(function(item){
            var opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            select.appendChild(opt);
        });
        select.value = '';
        notes.value = '';
        modal.style.display = 'flex';
        document.documentElement.classList.add('kbf-modal-lock');
        document.body.classList.add('kbf-modal-lock');
    };
    window.kbfCloseAdminRejectModal = function(){
        var modal = document.getElementById('kbf-admin-reject-modal');
        if (modal) modal.style.display = 'none';
        document.documentElement.classList.remove('kbf-modal-lock');
        document.body.classList.remove('kbf-modal-lock');
    };
    window.kbfSubmitAdminReject = function(){
        var notes = document.getElementById('kbf-admin-reject-notes');
        var text = notes ? String(notes.value || '').trim() : '';
        if (!text) {
            alert('Please provide a reason for rejection.');
            return;
        }
        var ctx = kbfAdminRejectState.context;
        var params = kbfAdminRejectState.params || {};
        if (!ctx || !params) return;
        if (ctx === 'fund') {
            kbfAdmin('kbf_admin_reject_fund', {fund_id: params.fund_id, reason: text});
        } else if (ctx === 'withdrawal') {
            kbfAdmin('kbf_admin_process_withdrawal', {withdrawal_id: params.withdrawal_id, action_type: 'reject', notes: text});
        } else if (ctx === 'escrow') {
            kbfAdmin('kbf_admin_process_escrow_request', {request_id: params.request_id, action_type: 'reject', notes: text});
        } else if (ctx === 'appeal') {
            kbfAdmin('kbf_admin_review_appeal', {appeal_id: params.appeal_id, action_type: 'reject', notes: text});
        }
        kbfAdminRejectState.context = '';
        kbfAdminRejectState.params = null;
        window.kbfCloseAdminRejectModal();
    };
    document.addEventListener('change', function(e){
        if (!e.target || e.target.id !== 'kbf-admin-reject-template') return;
        var notes = document.getElementById('kbf-admin-reject-notes');
        if (!notes) return;
        var val = String(e.target.value || '').trim();
        if (!val || val === 'Other') return;
        notes.value = val;
    });
    function kbfAdminApplyCounts(counts){
        if (!counts) return;
        Object.keys(counts).forEach(function(key){
            var value = parseInt(counts[key], 10) || 0;
            var label = value >= 100 ? '99+' : String(value);
            var links = document.querySelectorAll('[data-kbf-adm-tab="'+key+'"]');
            links.forEach(function(link){
                var badge = link.querySelector('.kbf-nav-count');
                if (value > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'kbf-nav-count';
                        link.appendChild(badge);
                    }
                    badge.textContent = label;
                } else if (badge && badge.parentNode) {
                    badge.parentNode.removeChild(badge);
                }
            });
        });
    }
    function kbfAdminRunInlineScripts(container){
        if (!container) return;
        var scripts = container.querySelectorAll('script');
        scripts.forEach(function(script){
            var code = script.textContent || '';
            if (!code.trim()) return;
            try { (new Function(code))(); } catch(e) { console.error('kbfAdmin inline script error:', e); }
        });
    }
    function kbfAdminRefreshTab(){
        if (!kbfAdminAutoRefresh || document.hidden) return;
        if (window.kbfAdminRefreshing) return;
        window.kbfAdminRefreshing = true;
        const fd = new FormData();
        fd.append('action','kbf_admin_refresh_tab');
        fd.append('_ajax_nonce', _kbfAdminNonce);
        fd.append('tab', kbfAdminTab);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            if (!j || !j.success || !j.data || !j.data.html) return;
            var container = document.querySelector('.kbf-tab-content');
            if (!container) return;
            container.innerHTML = j.data.html;
            kbfAdminRunInlineScripts(container);
            if (j.data.counts) kbfAdminApplyCounts(j.data.counts);
            if (window.kbfInitTableDescriptions) window.kbfInitTableDescriptions();
        }).catch(function(err){
            console.error('kbfAdminRefreshTab error:', err);
        }).finally(function(){
            window.kbfAdminRefreshing = false;
        });
    }
    window.kbfSetTableLoading = function(target, on){
        var el = null;
        if (typeof target === 'string') {
            el = document.querySelector(target);
        } else {
            el = target;
        }
        if (!el) return;
        var wrap = el.classList.contains('kbf-table-wrap') ? el : el.closest('.kbf-table-wrap');
        if (!wrap) return;
        if (on) wrap.classList.add('is-loading');
        else wrap.classList.remove('is-loading');
    };
    window.kbfApprove=function(id){if(!confirm('Approve this fund?'))return;kbfAdmin('kbf_admin_approve_fund',{fund_id:id});};
    window.kbfReject=function(id){
        window.kbfOpenAdminRejectModal('fund',{fund_id:id});
    };
    window.kbfSuspend=function(id){if(!confirm('Suspend this fund?'))return;kbfAdmin('kbf_admin_suspend_fund',{fund_id:id});};
    window.kbfVerifyBadge=function(id,cur){kbfAdmin('kbf_admin_verify_badge',{fund_id:id,verified:cur?'0':'1'});};
    window.kbfEscrow=function(id,act){kbfAdmin('kbf_admin_'+act+'_escrow',{fund_id:id});};
        window.kbfDismissReport=function(id){kbfAdmin('kbf_admin_dismiss_report',{report_id:id});};
        window.kbfReviewAppeal=function(id,action){
            if(action==='reject'){
                window.kbfOpenAdminRejectModal('appeal',{appeal_id:id});
                return;
            }
            const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_appeal',{appeal_id:id,action_type:action,notes:n});
        };
window.kbfProcessWd=function(id,type){
    if(type==='reject'){
        window.kbfOpenAdminRejectModal('withdrawal',{withdrawal_id:id});
    } else {
        if(!confirm('Approve & release this withdrawal?'))return;
        kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'approve'});
    }
};
window.kbfProcessEscrowRequest=function(id,type){
    if(type==='reject'){
        window.kbfOpenAdminRejectModal('escrow',{request_id:id});
    } else {
        if(!confirm('Approve escrow release for this fund?'))return;
        kbfAdmin('kbf_admin_process_escrow_request',{request_id:id,action_type:'approve'});
    }
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
window.kbfVerifyOrg=function(btn,id,verified){
    var v = parseInt(verified,10) ? 1 : 0;
    if(v === 0){
        kbfRejectState.id = id;
        kbfRejectState.btn = btn || null;
        if (typeof window.kbfOpenRejectModal === 'function') window.kbfOpenRejectModal();
        return;
    }
    var stack = btn && btn.closest ? btn.closest('.kbf-verify-stack') : null;
    kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:v,notes:''},{
        noReload:true,
        onSuccess:function(j){
            if(!j || !j.success) return;
            if (stack) {
                stack.classList.add('is-locked');
                var buttons = stack.querySelectorAll('.kbf-btn');
                buttons.forEach(function(b){ b.classList.remove('is-selected'); b.setAttribute('disabled','disabled'); });
                if (btn) btn.classList.add('is-selected');
            }
        }
    });
};

window.kbfOpenRejectModal = function(){
    var modal = document.getElementById('kbf-reject-modal');
    if (!modal) return;
    var select = document.getElementById('kbf-reject-template');
    var notes = document.getElementById('kbf-reject-notes');
    if (select) select.value = '';
    if (notes) notes.value = '';
    modal.style.display = 'flex';
    document.documentElement.classList.add('kbf-modal-lock');
    document.body.classList.add('kbf-modal-lock');
};
window.kbfCloseRejectModal = function(){
    var modal = document.getElementById('kbf-reject-modal');
    if (modal) modal.style.display = 'none';
    document.documentElement.classList.remove('kbf-modal-lock');
    document.body.classList.remove('kbf-modal-lock');
};
window.kbfSubmitReject = function(){
    var notes = document.getElementById('kbf-reject-notes');
    var text = notes ? String(notes.value || '').trim() : '';
    if (!text) {
        alert('Please provide a reason for rejection.');
        return;
    }
    var id = kbfRejectState.id;
    if (!id) return;
    var btn = kbfRejectState.btn;
    var stack = btn && btn.closest ? btn.closest('.kbf-verify-stack') : null;
    kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:0,notes:text},{
        noReload:true,
        onSuccess:function(j){
            if(!j || !j.success) return;
            if (stack) {
                stack.classList.add('is-locked');
                var buttons = stack.querySelectorAll('.kbf-btn');
                buttons.forEach(function(b){ b.classList.remove('is-selected'); b.setAttribute('disabled','disabled'); });
                if (btn) btn.classList.add('is-selected');
            }
            kbfRejectState.id = null;
            kbfRejectState.btn = null;
            window.kbfCloseRejectModal();
        }
    });
};
document.addEventListener('change', function(e){
    if (!e.target || e.target.id !== 'kbf-reject-template') return;
    var notes = document.getElementById('kbf-reject-notes');
    if (!notes) return;
    var val = String(e.target.value || '').trim();
    if (!val || val === 'Other') return;
    notes.value = val;
});
    window.kbfTriggerOnboarding=function(id){
        if(!confirm('Restart onboarding for this account?')) return;
        kbfAdmin('kbf_admin_trigger_onboarding',{business_id:id});
    };
    if (kbfAdminAutoRefresh) {
        kbfAdminStartRefresh();
        document.addEventListener('visibilitychange', function(){
            if (document.hidden) {
                kbfAdminStopRefresh();
                return;
            }
            kbfAdminStartRefresh();
            kbfAdminRefreshTab();
        });
    }
    </script>
    <?php
    $c=ob_get_clean();
    return bntm_universal_container('KonekBayan Admin Panel',$c, ['show_topbar'=>false,'show_header'=>false,'wrap'=>false]);
}
