<?php
/*
 * KBF admin tab: Transactions.
 */

function kbf_admin_transactions_tab() {
    global $wpdb;$st=$wpdb->prefix.'kbf_sponsorships';$ft=$wpdb->prefix.'kbf_funds';
    $rows=$wpdb->get_results("SELECT s.*,f.title as fund_title FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id ORDER BY s.created_at DESC LIMIT 300"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">All Transactions</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fundraiser</th><th>Supporter</th><th>Amount</th><th>Payment</th><th>Date</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--kbf-slate);">No transactions found.</td></tr>
          <?php else: foreach($rows as $s): ?>
            <tr>
              <td><strong><?php echo esc_html(wp_trim_words($s->fund_title,5)); ?></strong></td>
              <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name); ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($s->amount,2); ?></strong></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($s->created_at)); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}

