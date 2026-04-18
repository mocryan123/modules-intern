<?php
/*
 * KBF user dashboard tab: Withdrawals.
 */

/**
 * @function  kbf_dashboard_withdrawals_tab
 * @purpose   Renders the user cashout history tab for the given business account.
 * @used-by   [kbf user dashboard section renderer in user/partials/dashboard/sections.php, AJAX tab loader in includes/ajax-user.php]
 * @calls     [$wpdb->prepare, $wpdb->get_results, ob_start, ob_get_clean, strtotime, wp_date, esc_html, esc_attr, number_format, kbf_withdrawal_badge_class, kbf_withdrawal_status_label]
 * @params    [int $business_id - Business/user ID used to scope withdrawal records]
 * @returns   [string - Buffered HTML markup for the withdrawals tab]
 * @status    ACTIVE
 */
function kbf_dashboard_withdrawals_tab($business_id) {
    global $wpdb;
    $business_id = (int) $business_id;
    $ft = $wpdb->prefix . 'kbf_funds';
    $wt = $wpdb->prefix . 'kbf_withdrawals';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT w.*,f.title as fund_title,f.goal_amount as fund_goal_amount,f.raised_amount as fund_raised_amount,f.deadline as fund_deadline FROM {$wt} w INNER JOIN {$ft} f ON w.fund_id=f.id WHERE f.business_id=%d ORDER BY w.requested_at DESC",
        $business_id
    ));
    $format_date = function($value) {
        if (empty($value)) {
            return '-';
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '-';
        }
        return wp_date('M d, Y', $timestamp);
    };
    $format_account_type = function($type) {
        return $type ? ucwords(str_replace('_', ' ', $type)) : '-';
    };
    $mask_account_number = function($number) {
        $raw = trim((string) $number);
        if ($raw === '') {
            return '-';
        }
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            return $raw;
        }
        if (strlen($digits) <= 4) {
            return $digits;
        }
        return str_repeat('*', strlen($digits) - 4) . substr($digits, -4);
    };
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <style>
      .kbf-cashout-wrap .kbf-cashout-table tbody td:first-child{
        display:table-cell !important;
      }
      .kbf-cashout-wrap .kbf-cashout-title{
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:normal;
        overflow-wrap:anywhere;
        word-break:break-word;
      }
      .kbf-cashout-wrap .kbf-cashout-table td:nth-child(5){
        overflow-wrap:anywhere;
        word-break:break-word;
      }
    </style>
    <div class="kbf-section">
      <h3 class="kbf-section-title">Cashout History</h3>
      <?php if(empty($rows)): ?>
        <div class="kbf-table-empty" data-kbf-table-desc="Tracks your cashout requests, payout account details, and release status.">
          <div class="kbf-table-empty-head" style="grid-template-columns:1.6fr .9fr .9fr 1fr 1.6fr .9fr .9fr .9fr;">
            <span>Fundraiser</span>
            <span>Amount</span>
            <span>Platform Fee</span>
            <span>Account Type</span>
            <span>Payout Account</span>
            <span>Status</span>
            <span>Requested</span>
            <span>Released</span>
          </div>
          <div class="kbf-table-empty-body">No cashout requests yet.</div>
        </div>
      <?php else: ?>
        <div class="kbf-table-wrap kbf-cashout-wrap" data-kbf-table-desc="Tracks your cashout requests, payout account details, and release status.">
          <table class="kbf-table kbf-cashout-table">
            <colgroup>
              <col><col><col><col><col><col><col><col>
            </colgroup>
            <thead><tr><th>Fundraiser</th><th>Amount</th><th>Platform Fee</th><th>Account Type</th><th>Payout Account</th><th>Status</th><th>Requested</th><th>Released</th></tr></thead>
            <tbody>
            <?php foreach($rows as $w): ?>
              <?php
                $fund_meta = (object) [
                  'goal_amount' => (float) $w->fund_goal_amount,
                  'raised_amount' => (float) $w->fund_raised_amount,
                  'deadline' => (string) $w->fund_deadline,
                ];
                $fee_rate = function_exists('kbf_get_platform_fee_rate') ? kbf_get_platform_fee_rate($fund_meta) : 0.05;
                $fee_amount = max(0, round(((float) $w->amount) * (float) $fee_rate, 2));
              ?>
              <tr>
                <td><span class="kbf-cashout-title kbf-strong"><?php echo esc_html($w->fund_title); ?></span></td>
                <td><span class="kbf-strong">&#8369;<?php echo esc_html(number_format((float) $w->amount,2)); ?></span></td>
                <td class="kbf-meta"><span class="kbf-strong"><?php echo esc_html((string) round($fee_rate * 100)); ?>%</span> &bull; &#8369;<?php echo esc_html(number_format($fee_amount, 2)); ?></td>
                <td class="kbf-meta"><?php echo esc_html($format_account_type($w->account_type)); ?></td>
                <td class="kbf-meta"><?php echo esc_html($w->account_name); ?> &bull; <?php echo esc_html($mask_account_number($w->account_number)); ?></td>
                <td><span class="kbf-badge kbf-badge-<?php echo esc_attr(kbf_withdrawal_badge_class($w->status)); ?>"><?php echo esc_html(kbf_withdrawal_status_label($w->status)); ?></span></td>
                <td class="kbf-meta"><?php echo esc_html($format_date($w->requested_at)); ?></td>
                <td class="kbf-meta"><?php echo esc_html($format_date($w->processed_at)); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}




