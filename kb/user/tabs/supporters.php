<?php
/*
 * KBF user dashboard tab: Sponsorships.
 */

function kbf_dashboard_sponsorships_tab($business_id) {
    global $wpdb;
    $ft = $wpdb->prefix . 'kbf_funds';
    $st = $wpdb->prefix . 'kbf_sponsorships';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*,f.title as fund_title FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d ORDER BY s.created_at DESC",
        $business_id
    ));
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $pending_count = 0;
    foreach ((array)$rows as $s) {
        if ($s->payment_status === 'pending') { $pending_count++; }
    }
    $format_date = function($value) {
        return $value ? date('M d, Y', strtotime($value)) : '—';
    };
    ob_start();
    ?>
    
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <style>
        .kbf-supporters-table th,
        .kbf-supporters-table td{color:#0f172a;}
        .kbf-supporters-table .kbf-meta{color:#0f172a;}
        .kbf-supporters-table a{color:#0f172a;}
      </style>
      <h3 class="kbf-section-title">All Sponsorships Received</h3>
      <?php if($demo_mode): ?>
      <div class="kbf-alert kbf-alert-warning kbf-alert-noicon" style="margin-bottom:16px;display:flex;align-items:center;gap:10px;">
          <span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;"><i class="ph-fill ph-warning" aria-hidden="true"></i></span>
        <div><span class="kbf-strong">Demo mode active.</span> Payments are simulated for testing.</div>
      </div>
      <?php elseif($pending_count > 0): ?>
      <div class="kbf-alert kbf-alert-warning" style="margin-bottom:16px;">
        <span class="kbf-strong"><?php echo $pending_count; ?> sponsorship<?php echo $pending_count>1?'s':''; ?> pending payment confirmation.</span>
          Go to Admin &gt; Payments tab to manually confirm payments.
      </div>
      <?php endif; ?>
      <?php if(empty($rows)): ?>
        <div class="kbf-table-empty" data-kbf-table-desc="Shows all sponsorships received for your fundraisers, including amount and payment status.">
          <div class="kbf-table-empty-head" style="grid-template-columns:2fr 1.2fr .8fr .8fr 1.4fr .8fr;">
            <span>Fundraiser</span>
            <span>Supporter</span>
            <span>Amount</span>
            <span>Payment</span>
            <span>Note</span>
            <span>Date</span>
          </div>
          <div class="kbf-table-empty-body">No sponsorships yet.</div>
        </div>
      <?php else: ?>
        <div class="kbf-table-wrap" data-kbf-table-desc="Shows all sponsorships received for your fundraisers, including amount and payment status.">
          <table class="kbf-table kbf-supporters-table">
            <thead><tr><th>Fundraiser</th><th>Supporter</th><th>Amount</th><th>Payment</th><th>Note</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach($rows as $s): ?>
              <tr>
                <td>
                  <span style="display:block;margin-bottom:4px;" class="kbf-strong"><?php echo esc_html($s->fund_title); ?></span>
                  <div style="height:14px;"></div>
                </td>
                <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name); ?><?php if($s->email): ?><div class="kbf-meta"><?php echo esc_html($s->email); ?></div><?php endif; ?></td>
                <td><span class="kbf-strong">&#8369;<?php echo number_format($s->amount,2); ?></span></td>
                <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
                <td class="kbf-meta" style="font-style:italic;max-width:200px;">
                  <span class="kbf-clamp-2"><?php echo esc_html($s->message?:' -- '); ?></span>
                </td>
                <td class="kbf-meta"><?php echo $format_date($s->created_at); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}







