<?php
/*
 * KBF user dashboard tab: Sponsorships.
 */

function kbf_dashboard_sponsorships_tab($business_id) {
    global $wpdb;
    $ft=$wpdb->prefix.'kbf_funds';$st=$wpdb->prefix.'kbf_sponsorships';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*,f.title as fund_title FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d ORDER BY s.created_at DESC",
        $business_id
    ));
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $pending_count = 0;
    foreach ((array)$rows as $s) { if ($s->payment_status === 'pending') { $pending_count++; } }
    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">All Sponsorships Received</h3>
      <?php if($demo_mode): ?>
      <div class="kbf-alert kbf-alert-info" style="margin-bottom:16px;display:flex;align-items:center;gap:10px;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div><strong>Demo Mode is ON.</strong> Sponsorships are auto-confirmed instantly. When you integrate a real payment API, toggle Demo Mode off in the Admin â†’ Settings tab.</div>
      </div>
      <?php elseif($pending_count > 0): ?>
      <div class="kbf-alert kbf-alert-warning" style="margin-bottom:16px;">
        <strong><?php echo $pending_count; ?> sponsorship<?php echo $pending_count>1?'s':''; ?> pending payment confirmation.</strong>
        Go to Admin â†’ Transactions tab to manually confirm payments.
      </div>
      <?php endif; ?>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fundraiser</th><th>Supporter</th><th>Amount</th><th>Payment</th><th>Note</th><th>Date</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--kbf-slate);">No sponsorships yet.</td></tr>
          <?php else: foreach($rows as $s): ?>
            <tr>
              <td>
                <strong style="display:block;margin-bottom:4px;"><?php echo esc_html($s->fund_title); ?></strong>
                <div style="height:14px;"></div>
              </td>
              <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name); ?><?php if($s->email): ?><div class="kbf-meta"><?php echo esc_html($s->email); ?></div><?php endif; ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($s->amount,2); ?></strong></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
              <td style="font-size:12.5px;color:var(--kbf-text-sm);font-style:italic;max-width:200px;">
                <span class="kbf-clamp-2"><?php echo esc_html($s->message?:' -- '); ?></span>
              </td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($s->created_at)); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// DASHBOARD TAB: Withdrawals
// ============================================================

