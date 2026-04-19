<?php
/*
 * KBF admin tab: Pending funds.
 */

/**
 * @function  kbf_admin_pending_tab
 * @purpose   Renders the admin pending-funds review tab with fund details and approve/reject actions.
 * @used-by   [admin/ui.php tab router, includes/ajax-admin.php tab refresh handler, user/partials/admin_embed.php tab renderer]
 * @calls     [kbf_admin_date_where, $wpdb->prepare, $wpdb->get_results, number_format, date, strtotime, ucwords, str_replace, ob_start, ob_get_clean, count, esc_html, wp_trim_words, wp_unslash]
 * @params    [none]
 * @returns   [string buffered HTML markup for the pending funds admin tab]
 * @status    ACTIVE
 *            ACTIVE = confirmed it is called somewhere
 *            NEEDS REVIEW = could not confirm caller,
 *                           may be unused/dead code
 */
function kbf_admin_pending_tab() {
    global $wpdb;
    $t = $wpdb->prefix.'kbf_funds';
    $params = [];
    $where = "WHERE f.status='pending'";
    $where .= kbf_admin_date_where('f.created_at', $params);
    $sql = "SELECT f.*,u.display_name as organizer FROM {$t} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID {$where} ORDER BY f.created_at ASC";
    $funds = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input when params empty
    /**
     * @function  format_currency
     * @purpose   Formats numeric fund amounts into a fixed decimal currency string.
     * @used-by   [kbf_admin_pending_tab fund goal display output]
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
     * @purpose   Converts a raw date value into a human-readable admin date label.
     * @used-by   [kbf_admin_pending_tab deadline field output]
     * @calls     [date, strtotime]
     * @params    [mixed $value - date/time value from fund record]
     * @returns   [string formatted date or fallback label]
     * @status    ACTIVE
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $format_date = function($value) {
        return $value ? date('M d, Y', strtotime($value)) : 'None';
    };
    /**
     * @function  format_funder_type
     * @purpose   Normalizes funder type slugs into title-cased labels for display.
     * @used-by   [kbf_admin_pending_tab fund metadata output]
     * @calls     [ucwords, str_replace]
     * @params    [mixed $value - funder type slug value]
     * @returns   [string human-readable funder type label]
     * @status    ACTIVE
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $format_funder_type = function($value) {
        return ucwords(str_replace('_', ' ', (string)$value));
    };
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title">Funds Pending Approval <span style="background:var(--kbf-red-lt);color:var(--kbf-red);padding:2px 8px;border-radius:99px;font-size:12px;margin-left:8px;"><?php echo count($funds); ?></span></h3>
      <?php if(empty($funds)): ?><div class="kbf-empty"><p>No funds pending review.</p></div>
      <?php else: foreach($funds as $f): ?>
        <div class="kbf-card">
          <div class="kbf-card-header">
            <div>
              <span style="font-size:15px;" class="kbf-strong"><?php echo esc_html($f->title); ?></span>
              <div class="kbf-meta" style="margin-top:4px;">by <?php echo esc_html($f->organizer); ?> &bull; <?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?> &bull; <?php echo esc_html($format_funder_type($f->funder_type)); ?></div>
              <p style="font-size:13px;color:var(--kbf-text-sm);margin:8px 0 0;"><?php echo esc_html(wp_trim_words(wp_unslash($f->description),40)); ?></p>
              <div style="display:flex;gap:20px;margin-top:10px;font-size:12.5px;color:var(--kbf-slate);flex-wrap:wrap;">
                <span><span class="kbf-strong">Goal:</span> &#8369;<?php echo $format_currency($f->goal_amount,2); ?></span>
                <span><span class="kbf-strong">Deadline:</span> <?php echo $format_date($f->deadline); ?></span>
                <span><span class="kbf-strong">Email:</span> <?php echo esc_html($f->email); ?></span>
                <span><span class="kbf-strong">Phone:</span> <?php echo esc_html($f->phone); ?></span>
              </div>            </div>
            <span class="kbf-badge kbf-badge-pending">Pending</span>
          </div>
          <div class="kbf-btn-group" style="margin-top:14px;">
            <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfApprove(<?php echo (int)$f->id; ?>)">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Approve
            </button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfReject(<?php echo (int)$f->id; ?>)">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Reject
            </button>
          </div>
        </div>
      <?php endforeach; endif; ?>
      <div class="kbf-table-desc">Shows fundraisers awaiting admin approval or rejection.</div>
    </div>
    <?php return ob_get_clean();
}

