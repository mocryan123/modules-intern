<?php
/*
 * KBF admin tab: Withdrawals.
 */

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

