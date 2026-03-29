<?php
/* Sponsor history shortcode */
function bntm_shortcode_kbf_sponsor_history() {
    kbf_global_assets();
    global $wpdb;
    $st = $wpdb->prefix.'kbf_sponsorships';
    $ft = $wpdb->prefix.'kbf_funds';
    $email = sanitize_email(isset($_GET['email']) ? $_GET['email'] : '');
    $rows = [];
    if ($email) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*,f.title as fund_title FROM {$st} s LEFT JOIN {$ft} f ON s.fund_id=f.id WHERE s.email=%s ORDER BY s.created_at DESC",
            $email
        ));
    }
    $fund_details_url = kbf_get_page_url('fund_details');
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-wrap">
      <div class="kbf-page-header">
        <h2>Donation History</h2>
        <p>View your sponsorship records by email.</p>
      </div>

      <div class="kbf-card" style="margin-bottom:18px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
          <div class="kbf-form-group" style="flex:1;min-width:220px;margin-bottom:0;">
            <label>Your Email</label>
            <input type="email" name="email" value="<?php echo esc_attr($email); ?>" placeholder="you@example.com" required>
          </div>
          <button class="kbf-btn kbf-btn-primary" type="submit">View History</button>
        </form>
        <small style="color:var(--kbf-slate);display:block;margin-top:8px;">Enter the email you used when sponsoring a fund.</small>

      </div>

      <?php if(!$email): ?>
        <div class="kbf-empty"><p>Enter an email to view your donations.</p></div>
      <?php else: ?>
      <?php if(empty($rows)): ?>
        <div class="kbf-table-empty">
          <div class="kbf-table-empty-head" style="grid-template-columns:1.6fr 1fr .9fr .9fr 1fr .9fr;">
            <span>Fundraiser</span>
            <span>Sponsor</span>
            <span>Amount</span>
            <span>Status</span>
            <span>Method</span>
            <span>Date</span>
          </div>
          <div class="kbf-table-empty-body">No donations found for this email.</div>
        </div>
      <?php else: ?>
        <div class="kbf-table-wrap">
          <table class="kbf-table">
            <thead><tr><th>Fundraiser</th><th>Sponsor</th><th>Amount</th><th>Status</th><th>Method</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach($rows as $s): ?>
              <tr>
                <td>
                  <strong><?php echo esc_html($s->fund_title ?: 'Fundraiser'); ?></strong>
                  <?php if($s->fund_id): ?>
                    <?php $fund_token = function_exists('kbf_get_or_create_fund_token') ? kbf_get_or_create_fund_token($s->fund_id) : ''; ?>
                    <div class="kbf-meta"><a href="<?php echo esc_url(add_query_arg('fund', $fund_token ?: $s->fund_id, $fund_details_url)); ?>" style="color:var(--kbf-blue);text-decoration:none;">View fundraiser</a></div>
                  <?php endif; ?>
                </td>
                <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name ?: 'Sponsor'); ?></td>
                <td><strong style="color:var(--kbf-green);">PHP <?php echo number_format($s->amount,2); ?></strong></td>
                <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
                <td><?php echo esc_html($s->payment_method==='online_payment'?'Online Payment':($s->payment_method==='bank_payment'?'Bank Payment':ucfirst(str_replace('_',' ',isset($s->payment_method) ? $s->payment_method : '')))); ?></td>
                <td class="kbf-meta"><?php echo date('M d, Y',strtotime($s->created_at)); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
