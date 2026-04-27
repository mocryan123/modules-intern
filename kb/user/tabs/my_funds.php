<?php
/*
 * KBF user dashboard tab: My Funds.
 */

function kbf_dashboard_my_funds_tab($business_id, $nonce_cancel, $nonce_extend) {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $st = $wpdb->prefix.'kbf_sponsorships';
    $wt = $wpdb->prefix.'kbf_withdrawals';
    $at = $wpdb->prefix.'kbf_appeals';
    $funds = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$ft} WHERE business_id=%d ORDER BY created_at DESC",$business_id));
    $format_currency = function($amount, $decimals = 2) {
        return number_format((float)$amount, $decimals);
    };
    $calc_days_left = function($deadline) {
        return $deadline ? max(0, ceil((strtotime($deadline) - time()) / 86400)) : null;
    };
    $format_payment_method = function($method) {
        if ($method === 'online_payment') return 'Online Payment';
        if ($method === 'bank_payment') return 'Bank Payment';
        return ucfirst(str_replace('_', ' ', isset($method) ? $method : '--'));
    };

    ob_start();
    ?>
    
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <div class="kbf-section-header">
        <h3 class="kbf-section-title">All My Funds</h3>
        <button class="kbf-btn kbf-btn-primary" onclick="kbfOpenModal('kbf-modal-create')">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Create Fund
        </button>
      </div>
      <?php
      $pending_count = 0;
      foreach ($funds as $f) {
          if ($f->status === 'pending') { $pending_count++; }
      }
      if($pending_count > 0): ?>
      <div class="kbf-alert kbf-alert-info" style="margin-bottom:20px;">
        <span class="kbf-strong">How fund approval works:</span> After you submit a fund, our admin team reviews it (usually within 24-48 hours).
        Once approved, your fund goes <span class="kbf-strong">live</span> and becomes visible to all sponsors on the Browse page.
        You'll see the status change from <em>Pending</em> to <em>Active</em> here.
      </div>
      <?php endif; ?>
      <?php if(empty($funds)): ?>
        <div class="kbf-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><p>No funds created yet.</p></div>
      <?php else: foreach($funds as $f):
        $pct = $f->goal_amount > 0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $sc  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed'",$f->id));
        $days_left = $calc_days_left($f->deadline);
        $photo_list = $f->photos ? json_decode($f->photos, true) : [];
        $photo_json = wp_json_encode(array_values(array_filter(is_array($photo_list) ? $photo_list : [])));
        $benefit_list = $f->benefits ? json_decode($f->benefits, true) : [];
        $benefit_json = wp_json_encode(array_values(array_filter(is_array($benefit_list) ? $benefit_list : [])));
        $last_wd = $wpdb->get_row($wpdb->prepare("SELECT status FROM {$wt} WHERE fund_id=%d ORDER BY requested_at DESC, id DESC LIMIT 1", $f->id));
        $last_appeal = $wpdb->get_row($wpdb->prepare("SELECT status,admin_notes,message FROM {$at} WHERE fund_id=%d ORDER BY created_at DESC, id DESC LIMIT 1", $f->id));
        $appeal_pending = $last_appeal && $last_appeal->status === 'open';
        $wd_block = $last_wd && $last_wd->status === 'pending';
        ?>
        <div class="kbf-card">
          <?php if($f->status === 'pending'): ?>
          <div style="background:#fef3c7;border-left:3px solid #f59e0b;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div><span class="kbf-strong">Under Review</span> -- Awaiting admin approval. Not visible to sponsors yet. Usually 24-48 hours.</div>
          </div>
          <?php elseif($f->status === 'suspended'): ?>
          <div class="kbf-alert kbf-alert-error kbf-alert-noicon" style="margin-bottom:12px;display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;">
              <span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;">
                <i class="ph ph-prohibit kbf-icon" aria-hidden="true"></i>
              </span>
            <div>
              <?php if($last_appeal && $last_appeal->status === 'open'): ?>
                <span class="kbf-strong">Appeal Submitted:</span>
                Your appeal is under admin review. We'll notify you once a decision is made.
              <?php elseif($last_appeal && $last_appeal->status === 'rejected'): ?>
                <span class="kbf-strong">Appeal Rejected:</span>
                <?php if(!empty($last_appeal->admin_notes)): ?>
                  <?php echo esc_html($last_appeal->admin_notes); ?>
                <?php elseif($f->admin_notes): ?>
                  <?php echo esc_html($f->admin_notes); ?>
                <?php else: ?>
                  Your fund remains suspended. Contact support for details.
                <?php endif; ?>
              <?php else: ?>
                <span class="kbf-strong">Fund Suspended</span> -- Not visible to sponsors.
                <?php if($f->admin_notes): ?>
                  Admin note: <?php echo esc_html($f->admin_notes); ?>
                <?php else: ?>
                  Contact support for details.
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
          <?php elseif($f->status === 'cancelled' && $f->admin_notes): ?>
          <div style="background:#fee2e2;border-left:3px solid #ef4444;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#7f1d1d;display:flex;align-items:flex-start;gap:10px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <div><span class="kbf-strong">Rejected:</span> <?php echo esc_html($f->admin_notes); ?></div>
          </div>
          <?php endif; ?>
          <div class="kbf-card-header">
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                <span style="font-size:15px;" class="kbf-strong"><?php echo esc_html($f->title); ?></span>
                <span class="kbf-badge kbf-badge-<?php echo esc_attr(sanitize_html_class((string)$f->status)); ?>"><?php echo esc_html(ucfirst((string)$f->status)); ?></span>
              </div>
              <div class="kbf-meta">
                <?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?>
                <?php if($days_left!==null): ?> &bull; <span style="color:<?php echo $days_left<7?'#dc2626':'#64748b';?>;font-weight:700;"><?php echo $days_left; ?> days left</span><?php endif; ?>
                &bull; <?php echo $sc; ?> sponsors
                &bull; Escrow: <span class="kbf-badge kbf-badge-<?php echo esc_attr(sanitize_html_class((string)$f->escrow_status)); ?>" style="font-size:10px;"><?php echo esc_html(ucfirst((string)$f->escrow_status)); ?></span>
              </div>
            </div>
          </div>
          <div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="kbf-fund-amounts">
            <span><span class="kbf-strong">&#8369;<?php echo $format_currency($f->raised_amount); ?></span>raised</span>
            <span><span class="kbf-strong">&#8369;<?php echo $format_currency($f->goal_amount); ?></span>goal</span>
            <span><span class="kbf-strong"><?php echo round($pct); ?>%</span>funded</span>
          </div>
          <div class="kbf-btn-group" style="margin-top:12px;">
            <?php if(in_array($f->status,['active','pending'])): ?>
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenEdit(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>','<?php echo esc_js($f->description); ?>','<?php echo esc_js($f->location); ?>','<?php echo esc_js($f->deadline); ?>',<?php echo (int)$f->auto_return; ?>,'<?php echo esc_js($photo_json); ?>','<?php echo esc_js($benefit_json); ?>')">Edit</button>
            <?php endif; ?>
            <?php if(in_array($f->status,['active','completed']) && $f->raised_amount>0): ?>
              <?php if($wd_block): ?>
              <button class="kbf-btn kbf-btn-primary kbf-btn-sm" disabled aria-disabled="true" title="Withdrawal pending">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Request Withdrawal
              </button>
              <?php else: ?>
              <button class="kbf-btn kbf-btn-primary kbf-btn-sm" onclick="kbfOpenWd(<?php echo $f->id; ?>,<?php echo $f->raised_amount; ?>,'<?php echo esc_js($f->title); ?>')">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Request Withdrawal
              </button>
              <?php endif; ?>
            <?php endif; ?>
            <?php if($f->status==='active' && $f->raised_amount>=$f->goal_amount): ?>
              <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfMarkComplete(<?php echo $f->id; ?>)">Mark Complete</button>
            <?php endif; ?>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
              Share
            </button>
            <?php if($f->status === 'pending'): ?>
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfCancelFund(<?php echo $f->id; ?>)">
                <i class="ph-bold ph-x kbf-icon" style="font-size:12px; filter:invert(34%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                Cancel
              </button>
            <?php endif; ?>
            <?php if($f->status === 'suspended'): ?>
              <?php if($appeal_pending): ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" type="button" disabled aria-disabled="true" title="Appeal already submitted and under review">
                  Appeal Pending Review
                </button>
              <?php else: ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" type="button" onclick="kbfOpenAppeal(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>')">
                  Appeal Suspension
                </button>
              <?php endif; ?>
            <?php endif; ?>
            <?php if(in_array($f->status,['cancelled','suspended'])): ?>
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenTrashFund(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>','trash')">
                <i class="ph ph-trash-simple kbf-icon" style="font-size:12px; filter:invert(34%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                Trash
              </button>
            <?php endif; ?>
          </div>
          <?php
          $sponsors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$st} WHERE fund_id=%d AND payment_status='completed' ORDER BY amount DESC LIMIT 5",$f->id));
          if(!empty($sponsors)): ?>
          <details style="margin-top:14px;border-top:1px solid var(--kbf-border);padding-top:12px;">
            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--kbf-navy);">View Sponsors (<?php echo $sc; ?>)</summary>
            <div class="kbf-table-wrap" style="margin-top:10px;" data-kbf-table-desc="Lists top sponsors for this fundraiser and their contribution amounts.">
              <table class="kbf-table">
                <thead><tr><th>Sponsor</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($sponsors as $sp): ?>
                  <tr>
                    <td><?php echo $sp->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($sp->sponsor_name); ?></td>
                    <td><span style="color:var(--kbf-blue);" class="kbf-strong">&#8369;<?php echo $format_currency($sp->amount); ?></span></td>
                    <td><?php echo esc_html($format_payment_method($sp->payment_method)); ?></td>
                    <td class="kbf-meta"><?php echo date('M d, Y',strtotime($sp->created_at)); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </details>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}








