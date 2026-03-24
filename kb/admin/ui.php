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
        return fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            alert((j.data&&j.data.message)?j.data.message:(j.data||'Done.'));
            if(j.success)location.reload();
        }).catch(()=>alert('Request failed. Please try again.'));
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

function kbf_admin_pending_tab() {
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $funds=$wpdb->get_results("SELECT f.*,u.display_name as organizer FROM {$t} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.status='pending' ORDER BY f.created_at ASC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input, table name only
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Funds Pending Approval <span style="background:var(--kbf-red-lt);color:var(--kbf-red);padding:2px 8px;border-radius:99px;font-size:12px;margin-left:8px;"><?php echo count($funds); ?></span></h3>
      <?php if(empty($funds)): ?><div class="kbf-empty"><p>No funds pending review.</p></div>
      <?php else: foreach($funds as $f): ?>
        <div class="kbf-card">
          <div class="kbf-card-header">
            <div>
              <strong style="font-size:15px;"><?php echo esc_html($f->title); ?></strong>
              <div class="kbf-meta" style="margin-top:4px;">by <?php echo esc_html($f->organizer); ?> &bull; <?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?> &bull; <?php echo ucwords(str_replace('_',' ',$f->funder_type)); ?></div>
              <p style="font-size:13px;color:var(--kbf-text-sm);margin:8px 0 0;"><?php echo esc_html(wp_trim_words($f->description,40)); ?></p>
              <div style="display:flex;gap:20px;margin-top:10px;font-size:12.5px;color:var(--kbf-slate);flex-wrap:wrap;">
                <span><strong>Goal:</strong> ₱<?php echo number_format($f->goal_amount,2); ?></span>
                <span><strong>Deadline:</strong> <?php echo $f->deadline?date('M d, Y',strtotime($f->deadline)):'None'; ?></span>
                <span><strong>Email:</strong> <?php echo esc_html($f->email); ?></span>
                <span><strong>Phone:</strong> <?php echo esc_html($f->phone); ?></span>
              </div>
              <?php if(!empty($f->valid_id_path)): ?>
                <div style="margin-top:10px;"><a href="<?php echo esc_url($f->valid_id_path); ?>" target="_blank" class="kbf-btn kbf-btn-secondary kbf-btn-sm">
                  <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                  View Valid ID (Manual Verification)
                </a></div>
              <?php endif; ?>
            </div>
            <span class="kbf-badge kbf-badge-pending">Pending</span>
          </div>
          <div class="kbf-btn-group" style="margin-top:14px;">
            <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfApprove(<?php echo $f->id; ?>)">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Approve
            </button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfReject(<?php echo $f->id; ?>)">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Reject
            </button>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfVerifyBadge(<?php echo $f->id; ?>,<?php echo $f->verified_badge; ?>)">
              <?php echo $f->verified_badge?'Remove Badge':'Grant Verified Badge'; ?>
            </button>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_all_funds_tab() {
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $funds=$wpdb->get_results("SELECT f.*,u.display_name as organizer FROM {$t} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID ORDER BY f.created_at DESC LIMIT 200"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">All Funds</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Title</th><th>Organizer</th><th>Category</th><th>Goal</th><th>Raised</th><th>Status</th><th>Escrow</th><th>Verified</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($funds)): ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--kbf-slate);">No funds found.</td></tr>
          <?php else: foreach($funds as $f): ?>
            <tr>
              <td><strong><?php echo esc_html(wp_trim_words($f->title,6)); ?></strong></td>
              <td><?php echo esc_html($f->organizer); ?></td>
              <td><?php echo esc_html($f->category); ?></td>
              <td>₱<?php echo number_format($f->goal_amount,0); ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($f->raised_amount,0); ?></strong></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $f->status; ?>"><?php echo ucfirst($f->status); ?></span></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $f->escrow_status; ?>"><?php echo ucfirst($f->escrow_status); ?></span></td>
              <td><?php echo $f->verified_badge?'<span style="color:var(--kbf-green);font-weight:700;">Yes</span>':'No'; ?></td>
              <td>
                <div class="kbf-btn-group">
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfVerifyBadge(<?php echo $f->id; ?>,<?php echo $f->verified_badge; ?>)"><?php echo $f->verified_badge?'Remove Badge':'Verify'; ?></button>
                  <?php if($f->status==='active'): ?>
                    <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfEscrow(<?php echo $f->id; ?>,'<?php echo $f->escrow_status==='holding'?'release':'hold'; ?>')"><?php echo $f->escrow_status==='holding'?'Release Escrow':'Hold Escrow'; ?></button>
                    <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfSuspend(<?php echo $f->id; ?>)">Suspend</button>
                  <?php elseif($f->status==='pending'): ?>
                    <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfApprove(<?php echo $f->id; ?>)">Approve</button>
                    <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfReject(<?php echo $f->id; ?>)">Reject</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_transactions_tab() {
    global $wpdb;$st=$wpdb->prefix.'kbf_sponsorships';$ft=$wpdb->prefix.'kbf_funds';
    $rows=$wpdb->get_results("SELECT s.*,f.title as fund_title FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id ORDER BY s.created_at DESC LIMIT 300"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">All Transactions</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fund</th><th>Sponsor</th><th>Amount</th><th>Method</th><th>Status</th><th>Reference</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--kbf-slate);">No transactions found.</td></tr>
          <?php else: foreach($rows as $s): ?>
            <tr>
              <td><strong><?php echo esc_html(wp_trim_words($s->fund_title,5)); ?></strong></td>
              <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name); ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($s->amount,2); ?></strong></td>
              <td><?php echo esc_html($s->payment_method==='online_payment'?'Online Payment':($s->payment_method==='bank_payment'?'Bank Payment':ucfirst(str_replace('_',' ',$s->payment_method??'--')))); ?></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
              <td class="kbf-meta"><?php echo esc_html($s->payment_reference?:'--'); ?></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($s->created_at)); ?></td>
              <td>
                <?php if($s->payment_status==='pending'): ?>
                  <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfConfirmPayment(<?php echo $s->id; ?>)">Confirm Payment</button>
                <?php else: echo '--'; endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div class="kbf-notif-placeholder" style="margin-top:14px;">
        <strong>Notification Integration Point:</strong> TODO: Hook your 3rd-party notification service here.<br>
        <small>When a payment is confirmed, fire: <code>do_action('kbf_payment_confirmed', $sponsorship_id)</code></small>
      </div>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_withdrawals_tab() {
    global $wpdb;$wt=$wpdb->prefix.'kbf_withdrawals';$ft=$wpdb->prefix.'kbf_funds';
    $rows=$wpdb->get_results("SELECT w.*,f.title as fund_title,u.display_name as funder_display FROM {$wt} w LEFT JOIN {$ft} f ON w.fund_id=f.id LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID ORDER BY w.requested_at DESC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Withdrawal Requests</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fund</th><th>Funder</th><th>Amount</th><th>Method</th><th>Account</th><th>Status</th><th>Requested</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--kbf-slate);">No withdrawal requests.</td></tr>
          <?php else: foreach($rows as $w): ?>
            <tr>
              <td><strong><?php echo esc_html(wp_trim_words($w->fund_title,5)); ?></strong></td>
              <td class="kbf-meta"><?php echo esc_html($w->funder_name ?: ($w->funder_display ?: '-')); ?></td>
              <td><strong>PHP <?php echo number_format($w->amount,2); ?></strong></td>
              <td><?php echo esc_html($w->method); ?></td>
              <td class="kbf-meta"><?php echo esc_html($w->account_name); ?><br><?php echo esc_html($w->account_number); ?></td>
              <td><span class="kbf-badge kbf-badge-<?php echo kbf_withdrawal_badge_class($w->status); ?>"><?php echo kbf_withdrawal_status_label($w->status); ?></span></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($w->requested_at)); ?></td>
              <td>
                <?php if($w->status==='pending'): ?>
                <div class="kbf-btn-group">
                  <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfProcessWd(<?php echo $w->id; ?>,'approve')">Approve & Release</button>
                  <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfProcessWd(<?php echo $w->id; ?>,'reject')">Reject</button>
                </div>
                <?php else: ?>
                  <span class="kbf-meta"><?php echo $w->processed_at?date('M d, Y',strtotime($w->processed_at)):'-'; ?></span>
                  <?php if($w->admin_notes): ?><div class="kbf-meta" style="font-style:italic;"><?php echo esc_html($w->admin_notes); ?></div><?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_reports_tab() {
    global $wpdb;$rt=$wpdb->prefix.'kbf_reports';$ft=$wpdb->prefix.'kbf_funds';
    $rows=$wpdb->get_results("SELECT r.*,f.title as fund_title FROM {$rt} r JOIN {$ft} f ON r.fund_id=f.id ORDER BY FIELD(r.status,'open','reviewed','dismissed'),r.created_at DESC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Fund Reports</h3>
      <?php if(empty($rows)): ?><div class="kbf-empty"><p>No reports filed.</p></div>
      <?php else: foreach($rows as $r): ?>
        <div class="kbf-card" style="border-left:3px solid <?php echo $r->status==='open'?'var(--kbf-red)':($r->status==='reviewed'?'var(--kbf-accent)':'var(--kbf-border)'); ?>;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
            <div>
              <strong style="font-size:14px;">Fund: <?php echo esc_html($r->fund_title); ?></strong>
              <div style="font-size:13px;color:var(--kbf-red);font-weight:700;margin-top:2px;">Reason: <?php echo esc_html($r->reason); ?></div>
              <p style="font-size:13px;color:var(--kbf-text-sm);margin:6px 0 0;"><?php echo esc_html($r->details); ?></p>
              <div class="kbf-meta" style="margin-top:6px;"><?php echo $r->reporter_email?esc_html($r->reporter_email):'Anonymous reporter'; ?> &bull; <?php echo date('M d, Y H:i',strtotime($r->created_at)); ?></div>
              <?php if($r->admin_notes): ?><div class="kbf-alert kbf-alert-info" style="margin-top:8px;font-size:12px;"><strong>Admin Note:</strong> <?php echo esc_html($r->admin_notes); ?></div><?php endif; ?>
            </div>
            <span class="kbf-badge kbf-badge-<?php echo $r->status; ?>"><?php echo ucfirst($r->status); ?></span>
          </div>
          <?php if($r->status==='open'): ?>
          <div class="kbf-btn-group">
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfSuspend(<?php echo $r->fund_id; ?>)">Suspend Fund</button>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfReviewReport(<?php echo $r->id; ?>)">Mark Reviewed</button>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfDismissReport(<?php echo $r->id; ?>)">Dismiss</button>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_appeals_tab() {
    global $wpdb;
    $at = $wpdb->prefix.'kbf_appeals';
    $ft = $wpdb->prefix.'kbf_funds';
    $rows = $wpdb->get_results("SELECT a.*,f.title as fund_title FROM {$at} a JOIN {$ft} f ON a.fund_id=f.id ORDER BY FIELD(a.status,'open','reviewed','approved','rejected'),a.created_at DESC"); // phpcs:ignore
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Suspension Appeals</h3>
      <?php if(empty($rows)): ?><div class="kbf-empty"><p>No appeals filed.</p></div>
      <?php else: foreach($rows as $a): ?>
        <div class="kbf-card" style="border-left:3px solid <?php echo $a->status==='open'?'var(--kbf-blue)':($a->status==='approved'?'var(--kbf-green)':'var(--kbf-border)'); ?>;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
            <div>
              <strong style="font-size:14px;">Fund: <?php echo esc_html($a->fund_title); ?></strong>
              <p style="font-size:13px;color:var(--kbf-text-sm);margin:6px 0 0;"><?php echo esc_html($a->message); ?></p>
              <div class="kbf-meta" style="margin-top:6px;">Appeal ID: <?php echo esc_html($a->rand_id); ?> &bull; <?php echo date('M d, Y H:i',strtotime($a->created_at)); ?></div>
              <?php if($a->admin_notes): ?><div class="kbf-alert kbf-alert-info" style="margin-top:8px;font-size:12px;"><strong>Admin Note:</strong> <?php echo esc_html($a->admin_notes); ?></div><?php endif; ?>
            </div>
            <span class="kbf-badge kbf-badge-<?php echo $a->status; ?>"><?php echo ucfirst($a->status); ?></span>
          </div>
          <?php if($a->status==='open'): ?>
          <div class="kbf-btn-group">
            <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfReviewAppeal(<?php echo $a->id; ?>,'approve')">Approve & Reinstate</button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfReviewAppeal(<?php echo $a->id; ?>,'reject')">Reject</button>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}

function kbf_admin_organizers_tab() {
    global $wpdb;$pt=$wpdb->prefix.'kbf_organizer_profiles';
    $rows=$wpdb->get_results("SELECT p.*,u.display_name,u.user_email FROM {$pt} p JOIN {$wpdb->users} u ON p.business_id=u.ID ORDER BY p.total_raised DESC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Organizer Management</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Name</th><th>Email</th><th>Total Raised</th><th>Sponsors</th><th>Rating</th><th>Verified</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--kbf-slate);">No organizer profiles yet.</td></tr>
          <?php else: foreach($rows as $p): ?>
            <tr>
              <td><strong><?php echo esc_html($p->display_name); ?></strong></td>
              <td class="kbf-meta"><?php echo esc_html($p->user_email); ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($p->total_raised,0); ?></strong></td>
              <td><?php echo number_format($p->total_sponsors); ?></td>
              <td><?php echo number_format($p->rating,1); ?>/5 (<?php echo $p->rating_count; ?>)</td>
              <td><?php echo $p->is_verified?'<span style="color:var(--kbf-green);font-weight:700;">Verified</span>':'--'; ?></td>
              <td><button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfVerifyOrg(<?php echo $p->business_id; ?>,<?php echo $p->is_verified; ?>)"><?php echo $p->is_verified?'Revoke Verification':'Verify Organizer'; ?></button></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}


// ============================================================
// AJAX HANDLERS -- FUNDER
// ============================================================


function kbf_admin_settings_tab() {
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $sb_pub    = kbf_get_setting('kbf_maya_sandbox_public', '');
    $sb_sec    = kbf_get_setting('kbf_maya_sandbox_secret', '');
    $lv_pub    = kbf_get_setting('kbf_maya_live_public', '');
    $lv_sec    = kbf_get_setting('kbf_maya_live_secret', '');
    $nonce     = wp_create_nonce('kbf_admin_action');
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:8px;">Platform Settings</h3>
      <p style="color:var(--kbf-slate);font-size:13.5px;margin-bottom:24px;">Configure KonekBayan payments and live mode.</p>

      <!-- Demo / Live Mode Toggle -->
      <div class="kbf-card" style="border-left:4px solid <?php echo $demo_mode?'var(--kbf-accent)':'var(--kbf-green)'; ?>;margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
              <strong style="font-size:15px;color:var(--kbf-navy);">Payment Mode</strong>
              <span class="kbf-badge <?php echo $demo_mode?'kbf-badge-holding':'kbf-badge-active'; ?>">
                <?php echo $demo_mode?'DEMO -- Auto-confirm':'LIVE -- Maya Checkout'; ?>
              </span>
            </div>
            <p style="margin:0 0 10px;font-size:13.5px;color:var(--kbf-text-sm);line-height:1.6;">
              <strong>Demo ON:</strong> Sponsorships auto-confirmed instantly, no real payment.<br>
              <strong>Demo OFF:</strong> Sponsors are redirected to Maya's secure checkout page (Maya Wallet, cards, QRPh).
            </p>
            <?php if($demo_mode): ?>
            <div class="kbf-alert kbf-alert-warning" style="font-size:12.5px;"><strong>Demo Mode is active.</strong> Configure your Maya API keys below, then switch to Live.</div>
            <?php else: ?>
            <div class="kbf-alert kbf-alert-success" style="font-size:12.5px;"><strong>Live Mode active.</strong> Maya Checkout processes all payments. Sandbox keys used for testing.</div>
            <?php endif; ?>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;min-width:160px;">
            <?php if($demo_mode): ?>
              <button class="kbf-btn kbf-btn-success" onclick="kbfSaveSetting('kbf_demo_mode','0','<?php echo $nonce; ?>')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Switch to Live
              </button>
            <?php else: ?>
              <button class="kbf-btn kbf-btn-accent" onclick="kbfSaveSetting('kbf_demo_mode','1','<?php echo $nonce; ?>')">Switch to Demo</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Maya API Keys -->
      <div class="kbf-card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--kbf-navy)" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
          <strong style="font-size:15px;color:var(--kbf-navy);">Maya API Keys</strong>
          <a href="https://sandbox-manager.paymaya.com" target="_blank" style="font-size:12px;color:var(--kbf-blue);margin-left:auto;">Open Maya Business Manager â†’</a>
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
    <script>
    window.kbfSaveSetting = function(key, val, nonce) {
        const fd = new FormData();
        fd.append('action','kbf_save_setting');
        fd.append('_ajax_nonce', nonce);
        fd.append('setting_key', key);
        fd.append('setting_val', val);
        fetch((window.ajaxurl||'<?php echo admin_url('admin-ajax.php'); ?>'),{method:'POST',body:fd})
        .then(r=>r.json()).then(j=>{
            alert(j.data&&j.data.message?j.data.message:(j.success?'Setting saved!':'Failed to save.'));
            if(j.success) location.reload();
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
                    msg.innerHTML = '<div class="kbf-alert kbf-alert-success" style="font-size:13px;">' + (type==='sandbox'?'Sandbox':'Live') + ' keys saved successfully.</div>';
                    setTimeout(() => msg.innerHTML = '', 4000);
                }
            });
        });
    };
    </script>
    <?php return ob_get_clean();
}


// ============================================================
// AJAX HANDLERS -- ADMIN
// ============================================================

