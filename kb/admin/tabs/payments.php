<?php
/*
 * KBF admin tab: Transactions.
 */

/**
 * @function  kbf_admin_transactions_tab
 * @purpose   Renders the admin transactions tab with sponsorship payment records and status badges.
 * @used-by   [admin/ui.php tab router, includes/ajax-admin.php tab refresh handler, user/partials/admin_embed.php tab renderer]
 * @calls     [kbf_admin_date_where, $wpdb->prepare, $wpdb->get_results, number_format, date, strtotime, ob_start, ob_get_clean, esc_html, wp_trim_words, sanitize_html_class, esc_attr, ucfirst]
 * @params    [none]
 * @returns   [string buffered HTML markup for the transactions admin tab]
 * @status    ACTIVE
 *            ACTIVE = confirmed it is called somewhere
 *            NEEDS REVIEW = could not confirm caller,
 *                           may be unused/dead code
 */
function kbf_admin_transactions_tab() {
    global $wpdb;
    $st = $wpdb->prefix.'kbf_sponsorships';
    $ft = $wpdb->prefix.'kbf_funds';
    $params = [];
    $where = "WHERE 1=1";
    $where .= kbf_admin_date_where('s.created_at', $params);
    $sql = "SELECT s.*,f.title as fund_title FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id {$where} ORDER BY s.created_at DESC LIMIT 300";
    $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    /**
     * @function  format_currency
     * @purpose   Formats sponsorship amounts into a fixed decimal currency string for table display.
     * @used-by   [kbf_admin_transactions_tab amount column rendering]
     * @calls     [number_format]
     * @params    [mixed $amount - numeric amount to format, int $decimals - decimal precision]
     * @returns   [string formatted numeric amount]
     * @status    ACTIVE
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $format_currency = function($amount, $decimals = 2) {
        return number_format((float)$amount, $decimals);
    };
    /**
     * @function  format_date
     * @purpose   Converts transaction timestamps into a human-readable admin date label.
     * @used-by   [kbf_admin_transactions_tab date column rendering]
     * @calls     [date, strtotime]
     * @params    [mixed $value - date/time value to format]
     * @returns   [string formatted date or fallback placeholder]
     * @status    ACTIVE
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $format_date = function($value) {
        return $value ? date('M d, Y', strtotime($value)) : '-';
    };
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title">All Transactions</h3>
      <?php if(empty($rows)): ?>
        <div class="kbf-table-empty" data-kbf-table-desc="Displays sponsorship transactions, payment status, sponsor contact, and transaction reference.">
          <div class="kbf-table-empty-head" style="grid-template-columns:1.4fr 1fr 1.2fr .8fr .8fr 1.2fr .8fr .8fr;">
            <span>Fundraiser</span>
            <span>Supporter</span>
            <span>Contact</span>
            <span>Amount</span>
            <span>Payment</span>
            <span>TRN / Reference</span>
            <span>Date</span>
            <span>Action</span>
          </div>
          <div class="kbf-table-empty-body">No transactions found.</div>
        </div>
      <?php else: ?>
      <div class="kbf-table-wrap" data-kbf-table-desc="Displays sponsorship transactions, payment status, sponsor contact, and transaction reference.">
        <table class="kbf-table kbf-table-payments">
          <colgroup>
            <col style="width:18%;">
            <col style="width:12%;">
            <col style="width:18%;">
            <col style="width:9%;">
            <col style="width:9%;">
            <col style="width:18%;">
            <col style="width:9%;">
            <col style="width:7%;">
          </colgroup>
          <thead><tr><th>Fundraiser</th><th>Supporter</th><th>Contact</th><th>Amount</th><th>Payment</th><th>TRN / Reference</th><th>Date</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach($rows as $s): ?>
            <?php $payment_status_class = sanitize_html_class((string)$s->payment_status); ?>
            <tr>
              <td><span class="kbf-strong"><?php echo esc_html(wp_trim_words($s->fund_title,5)); ?></span></td>
              <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name); ?></td>
              <td class="kbf-meta">
                <div class="kbf-cell-stack" style="gap:2px;">
                  <span><?php echo esc_html($s->email ? $s->email : '--'); ?></span>
                  <span><?php echo esc_html($s->phone ? $s->phone : '--'); ?></span>
                </div>
              </td>
              <td><span style="color:var(--kbf-blue);" class="kbf-strong">&#8369;<?php echo $format_currency($s->amount, 2); ?></span></td>
              <td><span class="kbf-badge kbf-badge-<?php echo esc_attr($payment_status_class); ?>"><?php echo esc_html(ucfirst((string)$s->payment_status)); ?></span></td>
              <td class="kbf-meta"><?php echo esc_html($s->payment_reference ? $s->payment_reference : '--'); ?></td>
              <td class="kbf-meta"><?php echo esc_html($format_date($s->created_at)); ?></td>
              <td style="white-space:nowrap;">
                <?php if ((string)$s->payment_status !== 'completed'): ?>
                  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <button type="button" class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfRecheckPayment(<?php echo (int)$s->id; ?>)">Recheck Maya</button>
                    <button type="button" class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfConfirmPayment(<?php echo (int)$s->id; ?>)">Mark Complete</button>
                  </div>
                <?php else: ?>
                  <span class="kbf-meta">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}


