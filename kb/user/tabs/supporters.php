<?php
/*
 * KBF user dashboard tab: Sponsorships.
 */

/**
 * @function  kbf_dashboard_sponsorships_tab
 * @purpose   Renders the organizer-facing sponsorships table with pending-payment notice and pagination.
 * @used-by   [dashboard tab router in user/partials/dashboard/sections.php, AJAX tab refresh in includes/ajax-user.php]
 * @calls     [$wpdb->get_var, $wpdb->get_results, $wpdb->prepare, kbf_get_setting, wp_unslash, absint, strtotime, date_i18n, ob_start, ob_get_clean, esc_html, esc_attr, sanitize_html_class, add_query_arg, esc_url, number_format, in_array, strtolower, ucwords, str_replace]
 * @params    [$business_id (int) organizer/business user ID used to filter sponsorship records]
 * @returns   [string HTML markup for the sponsorships tab content]
 * @status    ACTIVE
 *            ACTIVE = confirmed it is called somewhere
 *            NEEDS REVIEW = could not confirm caller,
 *                           may be unused/dead code
 */
function kbf_dashboard_sponsorships_tab($business_id) {
    global $wpdb;
    $ft = $wpdb->prefix . 'kbf_funds';
    $st = $wpdb->prefix . 'kbf_sponsorships';

    $total_count = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",
        $business_id
    ));
    $per_page = 50;
    $total_pages = max(1, (int)ceil($total_count / $per_page));
    $current_page = isset($_GET['kbf_sp_page']) ? max(1, absint(wp_unslash($_GET['kbf_sp_page']))) : 1;
    if ($current_page > $total_pages) {
        $current_page = $total_pages;
    }
    $offset = ($current_page - 1) * $per_page;

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*,f.title as fund_title FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed' ORDER BY s.created_at DESC LIMIT %d OFFSET %d",
        $business_id,
        $per_page,
        $offset
    ));

    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $format_date = function($value) {
        if (!$value) {
            return '--';
        }
        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return '--';
        }
        return date_i18n('M d, Y', $timestamp);
    };

    ob_start();
    ?>
    
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <style>
        .kbf-supporters-table{
          min-width:760px;
          table-layout:fixed;
        }
        .kbf-supporters-table th,
        .kbf-supporters-table td{
          vertical-align:top;
        }
        .kbf-supporters-table th,
        .kbf-supporters-table td{color:#0f172a;}
        .kbf-supporters-table th:nth-child(4),
        .kbf-supporters-table td:nth-child(4),
        .kbf-supporters-table th:nth-child(6),
        .kbf-supporters-table td:nth-child(6),
        .kbf-supporters-table th:nth-child(7),
        .kbf-supporters-table td:nth-child(7){
          white-space:nowrap;
        }
        .kbf-supporters-table td:nth-child(2),
        .kbf-supporters-table td:nth-child(5),
        .kbf-supporters-table td:nth-child(6){
          white-space:normal;
          overflow-wrap:anywhere;
          word-break:break-word;
        }
        .kbf-supporters-table td .kbf-clamp-2{
          display:-webkit-box;
          -webkit-line-clamp:2;
          -webkit-box-orient:vertical;
          overflow:hidden;
          text-overflow:ellipsis;
          white-space:normal;
          overflow-wrap:anywhere;
          word-break:break-word;
        }
        /* Override shared table rule that sets first td to display:block and breaks column alignment. */
        .kbf-supporters-table tbody td:first-child{
          display:table-cell !important;
          max-width:none;
          overflow:visible;
          text-overflow:clip;
          white-space:normal;
        }
        .kbf-supporters-table tbody td:first-child .kbf-strong{
          display:block !important;
          overflow:visible;
          text-overflow:clip;
          white-space:normal;
        }
        .kbf-supporter-name{
          display:block;
          font-weight:600;
          color:#0f172a;
          line-height:1.35;
          margin-bottom:2px;
        }
        .kbf-supporter-email{
          display:block;
          color:var(--kbf-slate);
          font-size:12px;
          line-height:1.4;
        }
        .kbf-supporters-table .kbf-meta{color:#0f172a;}
        .kbf-supporters-table a{color:#0f172a;}
      </style>
      <h3 class="kbf-section-title">All Sponsorships Received</h3>
      <?php if($demo_mode): ?>
      <div class="kbf-alert kbf-alert-warning kbf-alert-noicon" style="margin-bottom:16px;display:flex;align-items:center;gap:10px;">
          <span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;"><i class="ph-fill ph-warning" aria-hidden="true"></i></span>
        <div><span class="kbf-strong">Demo mode active.</span> Payments are simulated for testing.</div>
      </div>
      <?php endif; ?>
      <?php if(empty($rows)): ?>
        <div class="kbf-table-empty" data-kbf-table-desc="Shows completed sponsorships received for your campaigns, including amount and transaction reference.">
          <div class="kbf-table-empty-head" style="grid-template-columns:2fr 1.2fr .8fr .9fr 1.4fr 1.1fr .8fr;">
            <span>Campaign</span>
            <span>Supporter</span>
            <span>Amount</span>
            <span>Status</span>
            <span>Note</span>
            <span>TRN / Reference</span>
            <span>Date</span>
          </div>
          <div class="kbf-table-empty-body">No sponsorships yet.</div>
        </div>
      <?php else: ?>
        <div class="kbf-table-wrap" data-kbf-table-desc="Shows completed sponsorships received for your campaigns, including amount and transaction reference.">
          <table class="kbf-table kbf-supporters-table">
            <colgroup>
              <col style="width:22%;">
              <col style="width:22%;">
              <col style="width:10%;">
              <col style="width:12%;">
              <col style="width:14%;">
              <col style="width:12%;">
              <col style="width:10%;">
            </colgroup>
            <thead><tr><th scope="col">Campaign</th><th scope="col">Supporter</th><th scope="col">Amount</th><th scope="col">Status</th><th scope="col">Note</th><th scope="col">TRN / Reference</th><th scope="col">Date</th></tr></thead>
            <tbody>
            <?php foreach($rows as $s): ?>
              <tr>
                <td>
                  <span class="kbf-strong"><?php echo esc_html($s->fund_title); ?></span>
                </td>
                <td>
                  <?php if($s->is_anonymous): ?>
                    <em class="kbf-supporter-name" style="color:var(--kbf-slate);font-style:italic;">Anonymous</em>
                  <?php else: ?>
                    <span class="kbf-supporter-name"><?php echo esc_html($s->sponsor_name); ?></span>
                  <?php endif; ?>
                  </td>
                <td><span class="kbf-strong">&#8369;<?php echo number_format($s->amount,2); ?></span></td>
                <td>
                  <span class="kbf-badge kbf-badge-completed">Completed</span>
                </td>
                <td class="kbf-meta" style="font-style:italic;max-width:200px;">
                  <span class="kbf-clamp-2"><?php echo esc_html($s->message?:' -- '); ?></span>
                </td>
                <td class="kbf-meta"><?php echo esc_html($s->payment_reference ? $s->payment_reference : '--'); ?></td>
                <td class="kbf-meta"><?php echo $format_date($s->created_at); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if($total_pages > 1): ?>
          <div style="margin-top:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div class="kbf-meta">Page <?php echo (int)$current_page; ?> of <?php echo (int)$total_pages; ?> - <?php echo (int)$total_count; ?> total sponsorships</div>
            <div style="display:flex;gap:8px;align-items:center;">
              <?php if($current_page > 1): ?>
                <a class="kbf-btn kbf-btn-secondary kbf-btn-sm" href="<?php echo esc_url(add_query_arg('kbf_sp_page', $current_page - 1)); ?>">Previous</a>
              <?php endif; ?>
              <?php if($current_page < $total_pages): ?>
                <a class="kbf-btn kbf-btn-secondary kbf-btn-sm" href="<?php echo esc_url(add_query_arg('kbf_sp_page', $current_page + 1)); ?>">Next</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}
