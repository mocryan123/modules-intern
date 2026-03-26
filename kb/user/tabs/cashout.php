<?php
/*
 * KBF user dashboard tab: Withdrawals.
 */

function kbf_dashboard_withdrawals_tab($business_id) {
    global $wpdb;
    $ft=$wpdb->prefix.'kbf_funds';$wt=$wpdb->prefix.'kbf_withdrawals';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT w.*,f.title as fund_title FROM {$wt} w LEFT JOIN {$ft} f ON w.fund_id=f.id WHERE f.business_id=%d ORDER BY w.requested_at DESC",
        $business_id
    ));
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Cashout History</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fundraiser</th><th>Amount</th><th>Payout Account</th><th>Status</th><th>Requested</th><th>Released</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--kbf-slate);">No cashout requests yet.</td></tr>
          <?php else: foreach($rows as $w): ?>
            <tr>
              <td><strong><?php echo esc_html($w->fund_title); ?></strong></td>
              <td><strong>₱<?php echo number_format($w->amount,2); ?></strong></td>
              <td class="kbf-meta"><?php echo esc_html($w->account_name); ?> &bull; <?php echo esc_html($w->account_number); ?></td>
              <td><span class="kbf-badge kbf-badge-<?php echo kbf_withdrawal_badge_class($w->status); ?>"><?php echo kbf_withdrawal_status_label($w->status); ?></span></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($w->requested_at)); ?></td>
              <td class="kbf-meta"><?php echo $w->processed_at?date('M d, Y',strtotime($w->processed_at)):'—'; ?></td>
            </tr>
            <?php if(!empty($w->admin_notes)): ?>
            <tr>
              <td style="background:var(--kbf-slate-lt);font-size:12px;padding:6px 14px;border-top:1px solid var(--kbf-border);">
                <strong>Admin Note for:</strong> <?php echo esc_html($w->fund_title); ?>
              </td>
              <td colspan="5" style="background:var(--kbf-slate-lt);font-size:12px;padding:6px 14px;border-top:1px solid var(--kbf-border);">
                <span style="display:inline-block;padding-left:4px;"><?php echo esc_html($w->admin_notes); ?></span>
              </td>
            </tr>
            <?php endif; ?>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}



