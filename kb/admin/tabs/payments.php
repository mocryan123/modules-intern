<?php
/*
 * KBF admin tab: Transactions.
 */

function kbf_admin_transactions_tab() {
    global $wpdb;$st=$wpdb->prefix.'kbf_sponsorships';$ft=$wpdb->prefix.'kbf_funds';
    $rows=$wpdb->get_results("SELECT s.*,f.title as fund_title FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id ORDER BY s.created_at DESC LIMIT 300"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title">All Transactions</h3>
      <?php if(empty($rows)): ?>
        <div class="kbf-table-empty">
          <div class="kbf-table-empty-head" style="grid-template-columns:1.6fr 1.2fr .9fr .9fr .9fr;">
            <span>Fundraiser</span>
            <span>Supporter</span>
            <span>Amount</span>
            <span>Payment</span>
            <span>Date</span>
          </div>
          <div class="kbf-table-empty-body">No transactions found.</div>
        </div>
      <?php else: ?>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fundraiser</th><th>Supporter</th><th>Amount</th><th>Payment</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach($rows as $s): ?>
            <tr>
              <td><strong><?php echo esc_html(wp_trim_words($s->fund_title,5)); ?></strong></td>
              <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name); ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($s->amount,2); ?></strong></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($s->created_at)); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}
