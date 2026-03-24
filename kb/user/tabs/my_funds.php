<?php
/*
 * KBF user dashboard tab: My Funds.
 */

function kbf_dashboard_my_funds_tab($business_id, $nonce_cancel, $nonce_extend) {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $st = $wpdb->prefix.'kbf_sponsorships';
    $funds = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$ft} WHERE business_id=%d ORDER BY created_at DESC",$business_id));

    ob_start(); ?>
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
      foreach ($funds as $f) { if ($f->status === 'pending') { $pending_count++; } }
      if($pending_count > 0): ?>
      <div class="kbf-alert kbf-alert-info" style="margin-bottom:20px;">
        <strong>How fund approval works:</strong> After you submit a fund, our admin team reviews it (usually within 24â€“48 hours).
        Once approved, your fund goes <strong>live</strong> and becomes visible to all sponsors on the Browse page.
        You'll see the status change from <em>Pending</em> to <em>Active</em> here.
      </div>
      <?php endif; ?>
      <?php if(empty($funds)): ?>
        <div class="kbf-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><p>No funds created yet.</p></div>
      <?php else: foreach($funds as $f):
        $pct = $f->goal_amount > 0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $sc  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed'",$f->id));
        $days_left = $f->deadline ? max(0, ceil((strtotime($f->deadline)-time())/86400)) : null;
        ?>
        <div class="kbf-card">
          <?php if($f->status === 'pending'): ?>
          <div style="background:#fef3c7;border-left:3px solid #f59e0b;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div><strong>Under Review</strong> -- Awaiting admin approval. Not visible to sponsors yet. Usually 24â€“48 hours.</div>
          </div>
          <?php elseif($f->status === 'suspended'): ?>
          <div style="background:#fce7f3;border-left:3px solid #db2777;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#831843;display:flex;align-items:flex-start;gap:10px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <div><strong>Fund Suspended</strong> -- Not visible to sponsors.<?php if($f->admin_notes): ?> Admin note: <?php echo esc_html($f->admin_notes); ?><?php else: ?> Contact support for details.<?php endif; ?></div>
          </div>
          <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenAppeal(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>')" style="margin:-6px 0 12px;">
            Appeal Suspension
          </button>
          <?php elseif($f->status === 'cancelled' && $f->admin_notes): ?>
          <div style="background:#fee2e2;border-left:3px solid #ef4444;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#7f1d1d;display:flex;align-items:flex-start;gap:10px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <div><strong>Rejected:</strong> <?php echo esc_html($f->admin_notes); ?></div>
          </div>
          <?php endif; ?>
          <div class="kbf-card-header">
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                <strong style="font-size:15px;"><?php echo esc_html($f->title); ?></strong>
                <span class="kbf-badge kbf-badge-<?php echo $f->status; ?>"><?php echo ucfirst($f->status); ?></span>
              </div>
              <div class="kbf-meta">
                <?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?>
                <?php if($days_left!==null): ?> &bull; <span style="color:<?php echo $days_left<7?'#dc2626':'#64748b';?>;font-weight:700;"><?php echo $days_left; ?> days left</span><?php endif; ?>
                &bull; <?php echo $sc; ?> sponsors
                &bull; Escrow: <span class="kbf-badge kbf-badge-<?php echo $f->escrow_status; ?>" style="font-size:10px;"><?php echo ucfirst($f->escrow_status); ?></span>
              </div>
            </div>
          </div>
          <div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="kbf-fund-amounts">
            <span><strong>₱<?php echo number_format($f->raised_amount,2); ?></strong>raised</span>
            <span><strong>₱<?php echo number_format($f->goal_amount,2); ?></strong>goal</span>
            <span><strong><?php echo round($pct); ?>%</strong>funded</span>
          </div>
          <div class="kbf-btn-group" style="margin-top:12px;">
            <?php if(in_array($f->status,['active','pending'])): ?>
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenEdit(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>','<?php echo esc_js($f->description); ?>','<?php echo esc_js($f->location); ?>','<?php echo esc_js($f->deadline); ?>',<?php echo (int)$f->auto_return; ?>)">Edit</button>
            <?php endif; ?>
            <?php if(in_array($f->status,['active','completed']) && $f->raised_amount>0): ?>
              <button class="kbf-btn kbf-btn-primary kbf-btn-sm" onclick="kbfOpenWd(<?php echo $f->id; ?>,<?php echo $f->raised_amount; ?>,'<?php echo esc_js($f->title); ?>')">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Request Withdrawal
              </button>
            <?php endif; ?>
            <?php if($f->status==='active' && $f->raised_amount>=$f->goal_amount): ?>
              <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfMarkComplete(<?php echo $f->id; ?>)">Mark Complete</button>
            <?php endif; ?>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
              Share
            </button>
            <?php if(!in_array($f->status,['cancelled','completed'])): ?>
              <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfCancelFund(<?php echo $f->id; ?>)">Cancel</button>
            <?php endif; ?>
          </div>
          <?php
          $sponsors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$st} WHERE fund_id=%d AND payment_status='completed' ORDER BY amount DESC LIMIT 5",$f->id));
          if(!empty($sponsors)): ?>
          <details style="margin-top:14px;border-top:1px solid var(--kbf-border);padding-top:12px;">
            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--kbf-navy);">View Sponsors (<?php echo $sc; ?>)</summary>
            <div class="kbf-table-wrap" style="margin-top:10px;">
              <table class="kbf-table">
                <thead><tr><th>Sponsor</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($sponsors as $sp): ?>
                  <tr>
                    <td><?php echo $sp->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($sp->sponsor_name); ?></td>
                    <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($sp->amount,2); ?></strong></td>
                    <td><?php echo esc_html($sp->payment_method==='online_payment'?'Online Payment':($sp->payment_method==='bank_payment'?'Bank Payment':ucfirst(str_replace('_',' ',isset($sp->payment_method) ? $sp->payment_method : '--')))); ?></td>
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

// ============================================================
// DASHBOARD TAB: Sponsorships Received
// ============================================================

