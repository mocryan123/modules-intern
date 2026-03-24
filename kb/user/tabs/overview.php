<?php
/*
 * KBF user dashboard tab: Overview.
 */

function kbf_dashboard_overview_tab($business_id) {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $st = $wpdb->prefix.'kbf_sponsorships';

    $total_funds    = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d",$business_id));
    $active_funds   = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status='active'",$business_id));
    $pending_funds  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status='pending'",$business_id));
    $total_raised   = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(raised_amount),0) FROM {$ft} WHERE business_id=%d",$business_id));
    $total_sponsors = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$business_id));
    $recent = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$ft} WHERE business_id=%d ORDER BY created_at DESC LIMIT 5",$business_id));

    ob_start(); ?>
    <div class="kbf-section">
      <div class="kbf-section-header">
        <h3 class="kbf-section-title">Dashboard Overview</h3>
        <button class="kbf-btn kbf-btn-primary" onclick="kbfOpenModal('kbf-modal-create')">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Create Fund
        </button>
      </div>
      <?php if($pending_funds > 0): ?>
      <div class="kbf-alert kbf-alert-warning" style="margin-bottom:20px;display:flex;align-items:center;gap:12px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
          <strong>You have <?php echo $pending_funds; ?> fund<?php echo $pending_funds>1?'s':''; ?> awaiting admin review.</strong>
          Pending funds are not visible to sponsors until approved. You will be notified once approved.
          <a href="?kbf_tab=my_funds" style="color:inherit;font-weight:700;text-decoration:underline;margin-left:6px;">View My Funds &rarr;</a>
        </div>
      </div>
      <?php endif; ?>
      <div class="kbf-stats">
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#0f2044,#243b78);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          </div>
          <div><div class="kbf-stat-label">Total Funds</div><div class="kbf-stat-value"><?php echo $total_funds; ?></div><div class="kbf-stat-sub"><?php echo $active_funds; ?> active</div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#16a34a,#15803d);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Total Raised</div><div class="kbf-stat-value">₱<?php echo number_format($total_raised,0); ?></div><div class="kbf-stat-sub">across all funds</div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#e8a020,#d4911a);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Total Sponsors</div><div class="kbf-stat-value"><?php echo $total_sponsors; ?></div><div class="kbf-stat-sub">completed payments</div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Active Now</div><div class="kbf-stat-value"><?php echo $active_funds; ?></div><div class="kbf-stat-sub">running campaigns</div></div>
        </div>
        <?php if($pending_funds > 0): ?>
        <div class="kbf-stat" style="border-color:#fcd34d;background:#fffbeb;">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Pending Review</div><div class="kbf-stat-value" style="color:#92400e;"><?php echo $pending_funds; ?></div><div class="kbf-stat-sub">awaiting approval</div></div>
        </div>
        <?php endif; ?>
      </div>

      <h3 class="kbf-section-title" style="margin-bottom:14px;">Recent Funds</h3>
      <?php if(empty($recent)): ?>
        <div class="kbf-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><p>No funds yet. Create your first fund!</p></div>
      <?php else: foreach($recent as $f):
          $pct = $f->goal_amount > 0 ? min(100, ($f->raised_amount/$f->goal_amount)*100) : 0; ?>
        <div class="kbf-card">
          <?php if($f->status === 'pending'): ?>
          <div style="background:#fef3c7;border-radius:6px;padding:8px 12px;margin-bottom:10px;font-size:12.5px;color:#92400e;display:flex;align-items:center;gap:8px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <strong>Awaiting admin approval</strong> -- This fund is not yet visible to sponsors.
          </div>
          <?php elseif($f->status === 'suspended'): ?>
          <div style="background:#fce7f3;border-radius:6px;padding:8px 12px;margin-bottom:10px;font-size:12.5px;color:#831843;display:flex;align-items:center;gap:8px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <strong>Fund suspended by admin.</strong> Contact support for details.
          </div>
          <?php endif; ?>
          <div class="kbf-card-header">
            <div>
              <strong style="font-size:15px;"><?php echo esc_html($f->title); ?></strong>
              <div class="kbf-meta" style="margin-top:4px;"><?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?></div>
            </div>
            <span class="kbf-badge kbf-badge-<?php echo $f->status; ?>"><?php echo ucfirst($f->status); ?></span>
          </div>
          <div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="kbf-fund-amounts">
            <span><strong>₱<?php echo number_format($f->raised_amount,2); ?></strong>raised</span>
            <span><strong>₱<?php echo number_format($f->goal_amount,2); ?></strong>goal</span>
            <span><strong><?php echo round($pct); ?>%</strong>funded</span>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// DASHBOARD TAB: My Funds
// ============================================================

