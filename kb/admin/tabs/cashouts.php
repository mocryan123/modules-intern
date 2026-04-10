<?php
/*
 * KBF admin tab: Withdrawals.
 */

function kbf_admin_withdrawals_tab() {
    global $wpdb;
    $wt = $wpdb->prefix.'kbf_withdrawals';
    $ft = $wpdb->prefix.'kbf_funds';
    $et = $wpdb->prefix.'kbf_escrow_requests';
    $params = [];
    $where = "WHERE 1=1";
    $where .= kbf_admin_date_where('w.requested_at', $params);
    $sql = "SELECT w.*,f.title as fund_title,u.display_name as funder_display FROM {$wt} w LEFT JOIN {$ft} f ON w.fund_id=f.id LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID {$where} ORDER BY w.requested_at DESC";
    $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input

    $params = [];
    $where = "WHERE 1=1";
    $where .= kbf_admin_date_where('e.requested_at', $params);
    $sql = "SELECT e.*,f.title as fund_title,u.display_name as funder_display FROM {$et} e LEFT JOIN {$ft} f ON e.fund_id=f.id LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID {$where} ORDER BY e.requested_at DESC";
    $escrows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    $format_date = function($value) {
        return $value ? date('M d, Y', strtotime($value)) : '—';
    };
    $format_account_type = function($value) {
        return $value ? ucwords(str_replace('_', ' ', $value)) : '—';
    };
    $format_amount = function($value, $decimals = 2) {
        return number_format((float)$value, $decimals);
    };
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title">Escrow Release Requests</h3>
      <?php if(empty($escrows)): ?>
        <div class="kbf-table-empty" style="margin-bottom:18px;" data-kbf-table-desc="Lists escrow release requests awaiting action.">
          <div class="kbf-table-empty-head" style="grid-template-columns:1.4fr 1fr .9fr .9fr .9fr;">
            <span>Fund</span>
            <span>Funder</span>
            <span>Status</span>
            <span>Requested</span>
            <span>Actions</span>
          </div>
          <div class="kbf-table-empty-body">No escrow release requests.</div>
        </div>
      <?php else: ?>
      <div class="kbf-table-wrap" style="margin-bottom:18px;" data-kbf-table-desc="Lists escrow release requests awaiting action.">
        <table class="kbf-table">
          <thead><tr><th>Fund</th><th>Funder</th><th>Status</th><th>Requested</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($escrows as $e): ?>
            <tr>
              <td><span class="kbf-strong"><?php echo esc_html(wp_trim_words($e->fund_title,5)); ?></span></td>
              <td class="kbf-meta"><?php echo esc_html($e->funder_display ?: '-'); ?></td>
              <td><span class="kbf-badge kbf-badge-<?php echo esc_attr($e->status); ?>"><?php echo ucfirst($e->status); ?></span></td>
              <td class="kbf-meta"><?php echo $format_date($e->requested_at); ?></td>
              <td>
                <?php if($e->status==='pending'): ?>
                <div class="kbf-btn-group" style="justify-content:center;">
                  <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfProcessEscrowRequest(<?php echo (int)$e->id; ?>,'approve')">Approve</button>
                  <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfProcessEscrowRequest(<?php echo (int)$e->id; ?>,'reject')">Reject</button>
                </div>
                <?php else: ?>
                  <span class="kbf-meta">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <h3 class="kbf-section-title">Withdrawal Requests</h3>
      <?php if(empty($rows)): ?>
        <div class="kbf-table-empty" data-kbf-table-desc="Lists withdrawal requests and their processing status.">
          <div class="kbf-table-empty-head" style="grid-template-columns:1.3fr 1fr .8fr .9fr 1.2fr .8fr .8fr .8fr .8fr;">
            <span>Fund</span>
            <span>Funder</span>
            <span>Amount</span>
            <span>Account Type</span>
            <span>Account</span>
            <span>Status</span>
            <span>Requested</span>
            <span>Released</span>
            <span>Actions</span>
          </div>
          <div class="kbf-table-empty-body">No withdrawal requests.</div>
        </div>
      <?php else: ?>
      <div class="kbf-table-wrap" data-kbf-table-desc="Lists withdrawal requests and their processing status.">
        <table class="kbf-table">
          <thead><tr><th>Fund</th><th>Funder</th><th>Amount</th><th>Account Type</th><th>Account</th><th>Status</th><th>Requested</th><th>Released</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($rows as $w): ?>
            <tr>
              <td>
                <div class="kbf-cell-center">
                  <div class="kbf-cell-spacer"></div>
                  <span class="kbf-strong"><?php echo esc_html(wp_trim_words($w->fund_title,5)); ?></span>
                  <div class="kbf-cell-spacer"></div>
                </div>
              </td>
              <td class="kbf-meta">
                <div class="kbf-cell-center">
                  <div class="kbf-cell-spacer"></div>
                  <?php echo esc_html($w->funder_name ?: ($w->funder_display ?: '-')); ?>
                  <div class="kbf-cell-spacer"></div>
                </div>
              </td>
              <td><span class="kbf-strong">PHP <?php echo $format_amount($w->amount, 2); ?></span></td>
              <td class="kbf-meta"><?php echo esc_html($format_account_type($w->account_type)); ?></td>
              <td class="kbf-meta"><?php echo esc_html($w->account_name); ?><br><?php echo esc_html($w->account_number); ?></td>
              <td><span class="kbf-badge kbf-badge-<?php echo kbf_withdrawal_badge_class($w->status); ?>"><?php echo kbf_withdrawal_status_label($w->status); ?></span></td>
              <td class="kbf-meta"><?php echo $format_date($w->requested_at); ?></td>
              <td class="kbf-meta"><?php echo $format_date($w->processed_at); ?></td>
              <td>
                <?php if($w->status==='pending'): ?>
                <div class="kbf-btn-group" style="justify-content:center;">
                  <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfProcessWd(<?php echo $w->id; ?>,'approve')">Release</button>
                  <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfProcessWd(<?php echo $w->id; ?>,'reject')">Reject</button>
                </div>
                <?php else: ?>
                  <span class="kbf-meta">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
      <script>
        if (window.kbfInitTablePager) {
          window.kbfInitTablePager();
        }
      </script>
    </div>
    <?php return ob_get_clean();
}

