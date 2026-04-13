<?php
/*
 * KBF user dashboard tab: Withdrawals.
 */

function kbf_dashboard_withdrawals_tab($business_id) {
    global $wpdb;
    $ft = $wpdb->prefix . 'kbf_funds';
    $wt = $wpdb->prefix . 'kbf_withdrawals';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT w.*,f.title as fund_title FROM {$wt} w LEFT JOIN {$ft} f ON w.fund_id=f.id WHERE f.business_id=%d ORDER BY w.requested_at DESC",
        $business_id
    ));
    $format_date = function($value) {
        return $value ? date('M d, Y', strtotime($value)) : '—';
    };
    $format_account_type = function($type) {
        return $type ? ucwords(str_replace('_', ' ', $type)) : '—';
    };
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title">Cashout History</h3>
      <?php if(empty($rows)): ?>
        <div class="kbf-table-empty" data-kbf-table-desc="Tracks your cashout requests, payout account details, and release status.">
          <div class="kbf-table-empty-head" style="grid-template-columns:1.6fr .9fr 1fr 1.6fr .9fr .9fr .9fr;">
            <span>Fundraiser</span>
            <span>Amount</span>
            <span>Account Type</span>
            <span>Payout Account</span>
            <span>Status</span>
            <span>Requested</span>
            <span>Released</span>
          </div>
          <div class="kbf-table-empty-body">No cashout requests yet.</div>
        </div>
      <?php else: ?>
        <div class="kbf-table-wrap" data-kbf-table-desc="Tracks your cashout requests, payout account details, and release status.">
          <table class="kbf-table kbf-cashout-table">
            <colgroup>
              <col><col><col><col><col><col><col>
            </colgroup>
            <thead><tr><th>Fundraiser</th><th>Amount</th><th>Account Type</th><th>Payout Account</th><th>Status</th><th>Requested</th><th>Released</th></tr></thead>
            <tbody>
            <?php foreach($rows as $w): ?>
              <tr>
                <td><span class="kbf-cashout-title kbf-strong"><?php echo esc_html($w->fund_title); ?></span></td>
                <td><span class="kbf-strong">&#8369;<?php echo number_format($w->amount,2); ?></span></td>
                <td class="kbf-meta"><?php echo esc_html($format_account_type($w->account_type)); ?></td>
                <td class="kbf-meta"><?php echo esc_html($w->account_name); ?> &bull; <?php echo esc_html($w->account_number); ?></td>
                <td><span class="kbf-badge kbf-badge-<?php echo kbf_withdrawal_badge_class($w->status); ?>"><?php echo kbf_withdrawal_status_label($w->status); ?></span></td>
                <td class="kbf-meta"><?php echo $format_date($w->requested_at); ?></td>
                <td class="kbf-meta"><?php echo $format_date($w->processed_at); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}




