<?php
/*
 * KBF admin tab: All funds.
 */

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

