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
          <thead><tr><th>Fund</th><th>Sponsor</th><th>Amount</th><th>Method</th><th>Status</th><th>Reference</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--kbf-slate);">No transactions found.</td></tr>
          <?php else: foreach($rows as $s): ?>
            <tr>
              <td><strong><?php echo esc_html(wp_trim_words($s->fund_title,5)); ?></strong></td>
              <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name); ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($s->amount,2); ?></strong></td>
              <td><?php echo esc_html($s->payment_method==='online_payment'?'Online Payment':($s->payment_method==='bank_payment'?'Bank Payment':ucfirst(str_replace('_',' ',isset($s->payment_method) ? $s->payment_method : '--')))); ?></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
              <td class="kbf-meta"><?php echo esc_html($s->payment_reference?:'--'); ?></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($s->created_at)); ?></td>
              <td>
                <?php if($s->payment_status==='pending'): ?>
                  <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfConfirmPayment(<?php echo $s->id; ?>)">Confirm Payment</button>
                <?php else: echo '--'; endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div class="kbf-notif-placeholder" style="margin-top:14px;">
        <strong>Notification Integration Point:</strong> TODO: Hook your 3rd-party notification service here.<br>
        <small>When a payment is confirmed, fire: <code>do_action('kbf_payment_confirmed', $sponsorship_id)</code></small>
      </div>
    </div>
    <?php return ob_get_clean();
}

