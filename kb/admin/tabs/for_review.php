<?php
/*
 * KBF admin tab: Pending funds.
 */

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
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}

