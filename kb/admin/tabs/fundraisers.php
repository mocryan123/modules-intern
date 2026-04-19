<?php
/*
 * KBF admin tab: All funds.
 */

/**
 * @function  kbf_admin_all_funds_tab
 * @purpose   Renders the admin fundraisers tab with campaign list, escrow controls, and moderation actions.
 * @used-by   [admin/ui.php tab router, includes/ajax-admin.php tab refresh handler, user/partials/admin_embed.php tab renderer]
 * @calls     [kbf_admin_date_where, $wpdb->prepare, $wpdb->get_results, number_format, ob_start, ob_get_clean, sanitize_html_class, esc_html, esc_attr, wp_trim_words, function_exists, kbf_get_platform_fee_rate, round, max, ucfirst]
 * @params    [none]
 * @returns   [string buffered HTML markup for the all funds admin tab]
 * @status    ACTIVE
 *            ACTIVE = confirmed it is called somewhere
 *            NEEDS REVIEW = could not confirm caller,
 *                           may be unused/dead code
 */
function kbf_admin_all_funds_tab() {
    global $wpdb;
    $t = $wpdb->prefix.'kbf_funds';
    $params = [];
    $where = "WHERE 1=1";
    $where .= kbf_admin_date_where('f.created_at', $params);
    $sql = "SELECT f.*,u.display_name as organizer FROM {$t} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID {$where} ORDER BY f.created_at DESC LIMIT 200";
    $funds = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    /**
     * @function  format_currency
     * @purpose   Formats numeric amounts for goal, raised, and fee displays in the table.
     * @used-by   [kbf_admin_all_funds_tab goal, raised, and platform fee deduction output]
     * @calls     [number_format]
     * @params    [mixed $amount - numeric value to format, int $decimals - decimal precision to apply]
     * @returns   [string formatted numeric amount]
     * @status    ACTIVE
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $format_currency = function($amount, $decimals = 0) {
        return number_format((float)$amount, $decimals);
    };
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title">All Funds</h3>
      <?php if(empty($funds)): ?>
        <div class="kbf-table-empty" data-kbf-table-desc="Lists all fundraisers and their current status for monitoring.">
          <div class="kbf-table-empty-head" style="grid-template-columns:1.6fr 1fr .9fr .8fr .8fr .9fr .7fr .7fr 1fr;">
            <span>Fundraiser</span>
            <span>Organizer</span>
            <span>Category</span>
            <span>Goal</span>
            <span>Raised</span>
            <span>Platform Fee</span>
            <span>Status</span>
            <span>Escrow</span>
            <span>Actions</span>
          </div>
          <div class="kbf-table-empty-body">No funds found.</div>
        </div>
      <?php else: ?>
      <div class="kbf-table-wrap" data-kbf-table-desc="Lists all fundraisers and their current status for monitoring.">
        <table class="kbf-table">
          <thead><tr><th>Fundraiser</th><th>Organizer</th><th>Category</th><th>Goal</th><th>Raised</th><th>Platform Fee</th><th>Status</th><th>Escrow</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($funds as $f): ?>
            <?php $status_class = sanitize_html_class((string)$f->status); ?>
            <?php $escrow_class = sanitize_html_class((string)$f->escrow_status); ?>
            <?php $fee_rate = function_exists('kbf_get_platform_fee_rate') ? kbf_get_platform_fee_rate($f) : 0.05; ?>
            <?php $fee_amount = max(0, round(((float)$f->raised_amount) * (float)$fee_rate, 2)); ?>
            <tr>
              <td>
                <div class="kbf-cell-center">
                   <div class="kbf-cell-spacer"></div>
                  <span class="kbf-strong"><?php echo esc_html(wp_trim_words($f->title,6)); ?></span>
                  <div class="kbf-cell-spacer"></div>
                </div>
              </td>
              <td>
                <div class="kbf-cell-center">
                  <div class="kbf-cell-spacer"></div>
                  <?php echo esc_html($f->organizer); ?>
                  <div class="kbf-cell-spacer"></div>
                </div>
              </td>
              <td><?php echo esc_html($f->category); ?></td>
              <td>&#8369;<?php echo $format_currency($f->goal_amount, 0); ?></td>
              <td><span style="color:var(--kbf-blue);" class="kbf-strong">&#8369;<?php echo $format_currency($f->raised_amount, 0); ?></span></td>
              <td>
                <span class="kbf-strong"><?php echo esc_html((string)round($fee_rate * 100)); ?>%</span>
                <div class="kbf-meta">Deduct: &#8369;<?php echo esc_html($format_currency($fee_amount, 2)); ?></div>
              </td>
              <td><span class="kbf-badge kbf-badge-<?php echo esc_attr($status_class); ?>"><?php echo esc_html(ucfirst((string)$f->status)); ?></span></td>
              <td><span class="kbf-badge kbf-badge-<?php echo esc_attr($escrow_class); ?>"><?php echo esc_html(ucfirst((string)$f->escrow_status)); ?></span></td>
              <td>
                <div class="kbf-btn-group">
                  <?php if($f->status==='active'): ?>
                    <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfEscrow(<?php echo (int)$f->id; ?>,'<?php echo esc_js($f->escrow_status==='holding'?'release':'hold'); ?>')"><?php echo $f->escrow_status==='holding'?'Release Escrow':'Hold Escrow'; ?></button>
                  <?php elseif($f->status==='pending'): ?>
                    <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfApprove(<?php echo (int)$f->id; ?>)">Approve</button>
                    <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfReject(<?php echo (int)$f->id; ?>)">Reject</button>
                  <?php endif; ?>
                </div>
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


