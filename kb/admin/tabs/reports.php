<?php
/*
 * KBF admin tab: Reports.
 */

function kbf_admin_reports_tab() {
    global $wpdb;$rt=$wpdb->prefix.'kbf_reports';$ft=$wpdb->prefix.'kbf_funds';
    $rows=$wpdb->get_results("SELECT r.*,f.title as fund_title FROM {$rt} r JOIN {$ft} f ON r.fund_id=f.id ORDER BY FIELD(r.status,'open','reviewed','dismissed'),r.created_at DESC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
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