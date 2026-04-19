<?php
/*
 * KBF user dashboard tab: Overview.
 */

/**
 * @function  kbf_dashboard_overview_tab
 * @purpose   Renders the dashboard overview tab markup, data summaries, and client-side interactions for a business user.
 * @used-by   [kbf dashboard tab renderer in user dashboard sections, AJAX tab refresh handler]
 * @calls     [kbf_get_page_url, get_user_meta, get_userdata, wp_create_nonce, add_query_arg, wp_json_encode, wp_trim_words, kbf_get_or_create_fund_token, WordPress DB APIs]
 * @params    [int $business_id - Current business/user ID for dashboard data scope]
 * @returns   [string - Buffered HTML content for the overview tab]
 * @status    ACTIVE
 */
  function kbf_dashboard_overview_tab($business_id) {
      global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $st = $wpdb->prefix.'kbf_sponsorships';
    $wt = $wpdb->prefix.'kbf_withdrawals';
    $at = $wpdb->prefix.'kbf_appeals';
      $fund_details_url = kbf_get_page_url('fund_details');
      $pt = $wpdb->prefix.'kbf_organizer_profiles';
      $profile = $business_id ? $wpdb->get_row($wpdb->prepare("SELECT avatar_url,bio,payout_type,payout_name,payout_number FROM {$pt} WHERE business_id=%d", $business_id)) : null;
      $show_onboarding = $business_id ? (bool) get_user_meta($business_id, 'kbf_show_onboarding', true) : false;
      $address = $business_id ? get_user_meta($business_id, 'kbf_address', true) : '';
      $user = $business_id ? get_userdata($business_id) : null;
      $social_name = $business_id ? (string) get_user_meta($business_id, 'kbf_social_name', true) : '';
      $has_avatar = $profile && !empty($profile->avatar_url);
      $has_display_name = $user && !empty(trim((string) $user->display_name));
      $has_social_name = !empty(trim($social_name));
      $has_bio = $profile && !empty(trim((string) $profile->bio));
      $has_payout = $profile && !empty($profile->payout_type) && !empty($profile->payout_name) && !empty($profile->payout_number);
      $has_address = !empty(trim((string) $address));
      $onboard_required = 5;
      $onboard_done = ($has_display_name ? 1 : 0) + ($has_social_name ? 1 : 0) + ($has_bio ? 1 : 0) + ($has_payout ? 1 : 0) + ($has_address ? 1 : 0);
      $onboard_pct = round(($onboard_done / $onboard_required) * 100);
      $onboard_complete = ($onboard_done >= $onboard_required);
      // Always show onboarding modal if profile is incomplete, even if previously dismissed.
      if (!$onboard_complete && !$show_onboarding) {
          $show_onboarding = true;
      }
      $format_currency = function($amount, $decimals = 2) {
          return number_format((float)$amount, $decimals);
      };
      $format_payment_method = function($method) {
          if ($method === 'online_payment') return 'Online Payment';
          if ($method === 'bank_payment') return 'Bank Payment';
          return ucfirst(str_replace('_', ' ', isset($method) ? $method : '--'));
      };
    // Keep render path read-only: do not mutate escrow state while building the dashboard.

    $total_funds    = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d",$business_id));
    $active_funds   = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status='active'",$business_id));
    $pending_funds  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status='pending'",$business_id));
    $total_raised   = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(raised_amount),0) FROM {$ft} WHERE business_id=%d",$business_id));
    $total_sponsors = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$business_id));
    $funds = $wpdb->get_results($wpdb->prepare("SELECT id,title,description,location,deadline,auto_return,photos,benefits,status,escrow_status,raised_amount,goal_amount,category,admin_notes,share_token,created_at FROM {$ft} WHERE business_id=%d ORDER BY created_at DESC",$business_id));
    $fund_ids = array_values(array_filter(array_map('intval', wp_list_pluck((array)$funds, 'id'))));
    $sponsor_counts_by_fund = [];
    $last_withdrawal_by_fund = [];
    $last_appeal_by_fund = [];
    $sponsor_preview_by_fund = [];
    if (!empty($fund_ids)) {
        $in_placeholders = implode(',', array_fill(0, count($fund_ids), '%d'));

        $count_sql = "SELECT fund_id, COUNT(*) AS c FROM {$st} WHERE payment_status='completed' AND fund_id IN ({$in_placeholders}) GROUP BY fund_id";
        $count_rows = $wpdb->get_results($wpdb->prepare($count_sql, $fund_ids));
        foreach ((array)$count_rows as $crow) {
            $sponsor_counts_by_fund[(int)$crow->fund_id] = (int)$crow->c;
        }

        $last_wd_sql = "SELECT w1.fund_id, w1.status, w1.admin_notes
                        FROM {$wt} w1
                        INNER JOIN (
                            SELECT fund_id, MAX(requested_at) AS max_requested_at
                            FROM {$wt}
                            WHERE fund_id IN ({$in_placeholders})
                            GROUP BY fund_id
                        ) w2 ON w1.fund_id = w2.fund_id AND w1.requested_at = w2.max_requested_at
                        INNER JOIN (
                            SELECT fund_id, requested_at, MAX(id) AS max_id
                            FROM {$wt}
                            WHERE fund_id IN ({$in_placeholders})
                            GROUP BY fund_id, requested_at
                        ) w3 ON w1.fund_id = w3.fund_id AND w1.requested_at = w3.requested_at AND w1.id = w3.max_id";
        $last_wd_args = array_merge($fund_ids, $fund_ids);
        $last_wd_rows = $wpdb->get_results($wpdb->prepare($last_wd_sql, $last_wd_args));
        foreach ((array)$last_wd_rows as $wrow) {
            $last_withdrawal_by_fund[(int)$wrow->fund_id] = $wrow;
        }

        $last_appeal_sql = "SELECT a1.fund_id, a1.status, a1.admin_notes, a1.message, a1.created_at
                            FROM {$at} a1
                            INNER JOIN (
                                SELECT fund_id, MAX(created_at) AS max_created_at
                                FROM {$at}
                                WHERE fund_id IN ({$in_placeholders})
                                GROUP BY fund_id
                            ) a2 ON a1.fund_id = a2.fund_id AND a1.created_at = a2.max_created_at
                            INNER JOIN (
                                SELECT fund_id, created_at, MAX(id) AS max_id
                                FROM {$at}
                                WHERE fund_id IN ({$in_placeholders})
                                GROUP BY fund_id, created_at
                            ) a3 ON a1.fund_id = a3.fund_id AND a1.created_at = a3.created_at AND a1.id = a3.max_id";
        $last_appeal_args = array_merge($fund_ids, $fund_ids);
        $last_appeal_rows = $wpdb->get_results($wpdb->prepare($last_appeal_sql, $last_appeal_args));
        foreach ((array)$last_appeal_rows as $arow) {
            $last_appeal_by_fund[(int)$arow->fund_id] = $arow;
        }

        // Prefetch top 5 completed sponsors per fund in batch to avoid N+1 queries in the render loop.
        $top_ids_sql = "SELECT fund_id, SUBSTRING_INDEX(GROUP_CONCAT(id ORDER BY amount DESC, created_at DESC), ',', 5) AS top_ids
                        FROM {$st}
                        WHERE payment_status='completed' AND fund_id IN ({$in_placeholders})
                        GROUP BY fund_id";
        $top_id_rows = $wpdb->get_results($wpdb->prepare($top_ids_sql, $fund_ids));
        $preview_ids = [];
        $top_ids_by_fund = [];
        foreach ((array)$top_id_rows as $top_row) {
            $fid = (int)$top_row->fund_id;
            $ids = array_filter(array_map('intval', explode(',', (string)$top_row->top_ids)));
            if (!empty($ids)) {
                $top_ids_by_fund[$fid] = $ids;
                $preview_ids = array_merge($preview_ids, $ids);
            }
        }
        if (!empty($preview_ids)) {
            $preview_ids = array_values(array_unique($preview_ids));
            $id_placeholders = implode(',', array_fill(0, count($preview_ids), '%d'));
            $preview_sql = "SELECT id,fund_id,sponsor_name,is_anonymous,message,amount,payment_method,created_at FROM {$st} WHERE id IN ({$id_placeholders})";
            $preview_rows = $wpdb->get_results($wpdb->prepare($preview_sql, $preview_ids));
            $preview_by_id = [];
            foreach ((array)$preview_rows as $prow) {
                $preview_by_id[(int)$prow->id] = $prow;
            }
            foreach ($top_ids_by_fund as $fid => $ids) {
                $sponsor_preview_by_fund[$fid] = [];
                foreach ($ids as $sid) {
                    if (isset($preview_by_id[$sid])) {
                        $sponsor_preview_by_fund[$fid][] = $preview_by_id[$sid];
                    }
                }
            }
        }
    }
    $escrow_requests = [];
    $er = $wpdb->prefix.'kbf_escrow_requests';
    $escrow_rows = $wpdb->get_results($wpdb->prepare("SELECT fund_id,status,requested_at FROM {$er} WHERE business_id=%d ORDER BY requested_at DESC",$business_id));
    foreach ((array)$escrow_rows as $erow) {
        if (!isset($escrow_requests[$erow->fund_id])) {
            $escrow_requests[$erow->fund_id] = $erow;
        }
    }
    $find_funds_url = add_query_arg('kbf_tab', 'find_funds', kbf_get_page_url('dashboard'));

    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <style>
        .kbf-onboard-open{
          overflow:hidden;
        }
        .kbf-onboard-backdrop{
          position:fixed;
          inset:0;
          background:rgba(15,23,42,.5);
          display:flex;
          align-items:center;
          justify-content:center;
          z-index:9999;
          padding:22px;
        }
        .kbf-onboard{
          background:#fff;
          border:1px solid rgba(15,23,42,.08);
          border-radius:18px;
          padding:0;
          width:100%;
          max-width:820px;
          display:grid;
          box-shadow:0 28px 70px rgba(15,23,42,.22);
          position:relative;
          overflow:hidden;
        }
        .kbf-onboard h4{margin:0 0 6px;font-size:19px;font-weight:600;color:var(--kbf-navy);letter-spacing:-.3px;}
        .kbf-onboard p{margin:0;font-size:13px;color:var(--kbf-slate);line-height:1.7;}
        .kbf-onboard-shell{
          display:grid;
          grid-template-columns:260px 1fr;
          min-height:260px;
        }
        .kbf-onboard-left{
          padding:20px 18px;
          background:linear-gradient(160deg,#eef4ff 0%, #f7fbff 55%, #ffffff 100%);
          border-right:1px solid rgba(15,23,42,.08);
          display:flex;
          flex-direction:column;
          gap:12px;
        }
        .kbf-onboard-avatar{
          width:54px;
          height:54px;
          border-radius:16px;
          display:flex;
          align-items:center;
          justify-content:center;
          overflow:hidden;
          background:#eaf1ff;
          border-radius: 999px;
          border:1px solid rgba(37,99,235,.2);
        }
        .kbf-onboard-avatar.is-empty{
          border-radius:50%;
          background:linear-gradient(135deg,#3b82f6 0%, #2563eb 60%, #1d4ed8 100%);
          border:none;
          box-shadow:0 10px 18px rgba(37,99,235,.25);
        }
        .kbf-onboard-avatar img{
          width:100%;
          height:100%;
          object-fit:cover;
        }
        .kbf-onboard-avatar svg{
          width:26px;
          height:26px;
          fill:#fff;
        }
        .kbf-onboard-right{
          padding:20px 22px 18px;
          display:grid;
          gap:14px;
        }
        .kbf-onboard-badge{
          display:inline-flex;
          align-items:center;
          gap:6px;
          padding:6px 10px;
          border-radius:999px;
          background:#eef4ff;
          color:#1d4ed8;
          font-size:10.5px;
          font-weight:600;
          text-transform:uppercase;
        }
        .kbf-onboard-progress{
          display:flex;
          align-items:flex-end;
          gap:10px;
        }
        .kbf-onboard-progress .kbf-count{
          font-size:28px;
          font-weight:600;
          color:var(--kbf-navy);
          line-height:1;
        }
        .kbf-onboard-progress .kbf-count span{
          font-size:12px;
          font-weight:500;
          color:var(--kbf-slate);
          margin-left:2px;
        }
        .kbf-onboard-bar{
          flex:1;
          height:7px;
          border-radius:999px;
          background:#e7efff;
          overflow:hidden;
        }
        .kbf-onboard-bar span{
          display:block;
          height:100%;
          background:linear-gradient(90deg,#2563eb 0%, #60a5fa 100%);
        }
        .kbf-onboard-step-list{
          margin:0;
          padding:0;
          list-style:none;
          display:grid;
          gap:10px;
        }
        .kbf-onboard-step{
          list-style:none;
          background:#ffffff;
          border:1px solid rgba(15,23,42,.08);
          border-radius:12px;
          padding:12px 14px;
          font-size:12px;
          font-weight:600;
          color:var(--kbf-navy);
          display:flex;
          align-items:center;
          justify-content:space-between;
          gap:10px;
          box-shadow:0 8px 16px rgba(15,23,42,.05);
        }
        .kbf-onboard-step.is-done{
          border-color:rgba(34,197,94,.25);
          background:#f0fdf4;
        }
        .kbf-onboard-step .kbf-step-left{
          display:flex;
          align-items:center;
          gap:10px;
        }
        .kbf-onboard-dot{
          width:10px;
          height:10px;
          border-radius:50%;
          background:#c7d2fe;
        }
        .kbf-onboard-step.is-done .kbf-onboard-dot{
          background:#22c55e;
          box-shadow:0 0 0 4px rgba(34,197,94,.16);
        }
        .kbf-onboard-meta{
          font-size:10.5px;
          font-weight:500;
          color:var(--kbf-slate);
          text-transform:uppercase;
          letter-spacing:.3px;
        }
        .kbf-onboard-actions{
          display:flex;
          gap:10px;
          align-items:center;
          justify-content:flex-end;
        }
        @media (max-width: 820px){
          .kbf-onboard-shell{grid-template-columns:1fr;}
          .kbf-onboard-left{border-right:0;border-bottom:1px solid rgba(15,23,42,.08);}
        }
      </style>
      <?php if ($show_onboarding): ?>
        <div class="kbf-onboard-backdrop" id="kbf-onboard-backdrop">
          <div class="kbf-onboard" id="kbf-onboard-card" role="dialog" aria-modal="true" aria-labelledby="kbf-onboard-title">
            <div class="kbf-onboard-shell">
              <div class="kbf-onboard-left">
                <div class="kbf-onboard-avatar <?php echo $has_avatar ? '' : 'is-empty'; ?>">
                  <?php if ($has_avatar): ?>
                    <img src="<?php echo esc_url($profile->avatar_url); ?>" alt="Profile">
                  <?php else: ?>
                    <svg viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3Zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                    </svg>
                  <?php endif; ?>
                </div>
                <div class="kbf-onboard-badge">Onboarding</div>
                <h4 id="kbf-onboard-title">Set up your account profile</h4>
                <p>Complete the essentials below to unlock withdrawals and build supporter trust.</p>
                <div class="kbf-onboard-progress">
                  <div class="kbf-count"><?php echo (int) $onboard_done; ?><span>/5</span></div>
                  <div class="kbf-onboard-bar"><span style="width:<?php echo (int) $onboard_pct; ?>%;"></span></div>
                </div>
              </div>
              <div class="kbf-onboard-right">
                <ul class="kbf-onboard-step-list">
                  <li class="kbf-onboard-step <?php echo $has_display_name ? 'is-done' : ''; ?>">
                    <span class="kbf-step-left"><span class="kbf-onboard-dot"></span>Display name</span>
                    <span class="kbf-onboard-meta"><?php echo $has_display_name ? 'Done' : 'Pending'; ?></span>
                  </li>
                  <li class="kbf-onboard-step <?php echo $has_social_name ? 'is-done' : ''; ?>">
                    <span class="kbf-step-left"><span class="kbf-onboard-dot"></span>Social name</span>
                    <span class="kbf-onboard-meta"><?php echo $has_social_name ? 'Done' : 'Pending'; ?></span>
                  </li>
                  <li class="kbf-onboard-step <?php echo $has_bio ? 'is-done' : ''; ?>">
                    <span class="kbf-step-left"><span class="kbf-onboard-dot"></span>About/Bio</span>
                    <span class="kbf-onboard-meta"><?php echo $has_bio ? 'Done' : 'Pending'; ?></span>
                  </li>
                  <li class="kbf-onboard-step <?php echo $has_payout ? 'is-done' : ''; ?>">
                    <span class="kbf-step-left"><span class="kbf-onboard-dot"></span>Payout details</span>
                    <span class="kbf-onboard-meta"><?php echo $has_payout ? 'Done' : 'Pending'; ?></span>
                  </li>
                  <li class="kbf-onboard-step <?php echo $has_address ? 'is-done' : ''; ?>">
                    <span class="kbf-step-left"><span class="kbf-onboard-dot"></span>Address</span>
                    <span class="kbf-onboard-meta"><?php echo $has_address ? 'Done' : 'Pending'; ?></span>
                  </li>
                </ul>
                <div class="kbf-onboard-actions">
                  <a class="kbf-btn kbf-btn-primary" href="?kbf_tab=profile">Complete Profile</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <script>
          document.documentElement.classList.add('kbf-onboard-open');
          // Prevent dismissing by clicking outside the modal.
          (function(){
            var backdrop = document.getElementById('kbf-onboard-backdrop');
            if (backdrop) {
              backdrop.addEventListener('click', function(e){ e.stopPropagation(); });
            }
          })();
        </script>
      <?php endif; ?>
      <style>
        .kbf-home-alert{
          width:100%;
          box-sizing:border-box;
          align-items:flex-start !important;
          gap:10px !important;
        }
        .kbf-home-alert > span{
          flex:0 0 auto;
          margin-top:2px;
          display:inline-flex;
          align-items:flex-start;
        }
        .kbf-home-alert > div{
          flex:1 1 auto;
          min-width:0;
          overflow-wrap:anywhere;
          word-break:break-word;
          line-height:1.45;
        }
        .kbf-home-alert > div .kbf-strong{
          display:block;
          margin-bottom:2px;
          line-height:1.25;
        }
        .kbf-alert.kbf-alert-warning.kbf-alert-noicon.kbf-home-alert{
          align-items:center !important;
          flex-wrap:nowrap !important;
        }
        .kbf-alert.kbf-alert-warning.kbf-alert-noicon.kbf-home-alert > div{
          white-space:normal;
          overflow:visible;
          text-overflow:clip;
          overflow-wrap:anywhere;
          word-break:break-word;
          line-height:1.3;
        }
        .kbf-alert.kbf-alert-warning.kbf-alert-noicon.kbf-home-alert > div .kbf-strong{
          display:inline;
          margin:0 4px 0 0;
          line-height:inherit;
        }
        @media (max-width: 900px){
          .kbf-alert.kbf-alert-warning.kbf-alert-noicon.kbf-home-alert{
            flex-wrap:wrap !important;
            align-items:flex-start !important;
          }
        }
        @media (max-width: 680px){
          .kbf-home-alert{ gap:8px !important; }
          .kbf-home-alert > span{ margin-top:1px; }
        }
        .kbf-card-list[data-kbf-card-pager="home"] + .kbf-table-pager{
          margin-bottom:0;
          padding-bottom:0;
        }
        .kbf-card-list[data-kbf-card-pager="home"] + .kbf-table-pager .kbf-table-pager-btn.is-loading::after{
          top:50%;
          left:50%;
          transform:translate(-50%,-50%);
        }
        .kbf-card-more-menu button:hover,
        .kbf-card-more-menu .kbf-btn:hover,
        .kbf-card-more-menu .kbf-btn-secondary:hover{
          background:linear-gradient(90deg,#e7f1ff 0%, #edf5ff 60%, #f8fbff 100%) !important;
          color:#0f172a !important;
          transform:none;
          box-shadow:
            inset 0 0 0 1px #bfdbfe,
            0 8px 18px rgba(59,130,246,.16);
        }
      </style>
      <style>
        .kbf-sponsor-details{border-top:1px solid var(--kbf-border);margin-top:14px;}
        .kbf-home-sponsor-wrap .kbf-table{
          width:100%;
          min-width:640px;
          table-layout:fixed;
        }
        .kbf-home-sponsor-table col:nth-child(1){width:24%;}
        .kbf-home-sponsor-table col:nth-child(2){width:42%;}
        .kbf-home-sponsor-table col:nth-child(3){width:14%;}
        .kbf-home-sponsor-table col:nth-child(4){width:20%;}
        .kbf-home-sponsor-table tbody td:first-child{max-width:none;}
        .kbf-home-sponsor-table th,
        .kbf-home-sponsor-table td{
          white-space:nowrap;
          overflow:hidden;
          text-overflow:ellipsis;
        }
        .kbf-home-sponsor-table th:nth-child(2),
        .kbf-home-sponsor-table td:nth-child(2){text-align:left;}
        .kbf-home-sponsor-table th:nth-child(3),
        .kbf-home-sponsor-table td:nth-child(3){text-align:right;}
        .kbf-home-sponsor-table th:nth-child(4),
        .kbf-home-sponsor-table td:nth-child(4){text-align:right;}
        .kbf-sponsor-details summary{
          cursor:pointer;
          font-size:13px;
          font-weight:600;
          color:var(--kbf-navy);
          list-style:none;
          padding:12px 0;
          display:flex;
          align-items:center;
          gap:8px;
        }
        .kbf-sponsor-details summary::-webkit-details-marker{display:none;}
        .kbf-sponsor-details-content{
          display:block;
          overflow:hidden;
          max-height:0;
          opacity:0;
          transform:translateY(-6px);
          transition:
            max-height 520ms cubic-bezier(.2,.8,.2,1),
            opacity 320ms ease,
            transform 420ms cubic-bezier(.2,.8,.2,1);
          will-change:max-height, opacity, transform;
        }
        .kbf-sponsor-details.is-open .kbf-sponsor-details-content{
          opacity:1;
          transform:translateY(0);
        }
        @media (prefers-reduced-motion: reduce){
          .kbf-sponsor-details-content{
            transition:none;
            transform:none;
          }
        }
      </style>
      <style>
        .kbf-home-filter-btn{display:none;}
        .kbf-home-sheet-overlay{
          position:fixed;
          inset:0;
          background:rgba(10,16,32,0.45);
          backdrop-filter:blur(2px);
          z-index:9998;
          display:none;
        }
        .kbf-home-sheet-overlay.open{display:block;}
        .kbf-home-sheet{
          position:fixed;
          left:0;
          right:0;
          bottom:0;
          background:#fff;
          z-index:9999;
          transform:translateY(100%);
          transition:transform .3s cubic-bezier(.4,0,.2,1);
          max-height:80vh;
          overflow-y:auto;
          padding:0 0 24px;
        }
        .kbf-home-sheet.open{transform:translateY(0);}
        .kbf-home-sheet-handle{
          width:44px;
          height:5px;
          border-radius:999px;
          background:#e2e8f0;
          margin:10px auto 6px;
        }
        .kbf-home-sheet-header{
          display:flex;
          align-items:center;
          justify-content:space-between;
          padding:6px 20px 10px;
        }
        .kbf-home-sheet-body{
          padding:0 20px 10px;
          display:grid;
          gap:12px;
        }
        .kbf-home-sheet-body [data-kbf-home-group]{
          background:#fff !important;
          color:var(--kbf-text) !important;
          border-color:var(--kbf-border) !important;
          box-shadow:none !important;
          border-radius:12px !important;
          font-weight:600;
          text-align:left;
          justify-content:flex-start;
          transition:none !important;
        }
        .kbf-home-sheet-body [data-kbf-home-group].is-active{
          border-color:#60a5fa !important;
          background:#eff6ff !important;
          color:#0f172a !important;
        }
        .kbf-home-sheet-actions{
          display:flex;
          gap:10px;
          padding:8px 20px 0;
        }
        .kbf-home-sheet-actions .kbf-btn{
          flex:1;
          height:44px;
          border-radius:12px;
        }
        .kbf-user-ui .kbf-home-sheet .kbf-btn-primary::before{
          display:none;
          content:none;
        }
        @media (max-width: 720px){
          #kbf-filter-status-wrap,
          #kbf-filter-escrow-wrap{ display:none !important; }
          .kbf-home-filter-btn{ display:inline-flex; }
        }
      </style>
        <div class="kbf-section-header">
         <h3 class="kbf-section-title">Dashboard Overview</h3>
          <button class="kbf-btn kbf-btn-primary kbf-btn-sm" style="padding:0 14px;" onclick="kbfOpenModal('kbf-modal-create')">
            <i class="ph ph-plus kbf-icon" style="font-size:12px; color:#ffffff;" aria-hidden="true"></i>
            Create Fund
          </button>
        </div>
      <?php if($pending_funds > 0): ?>
      <div class="kbf-alert kbf-alert-warning kbf-alert-noicon kbf-home-alert" style="margin-bottom:20px;display:flex;align-items:center;gap:12px;">
        <div>
          <span style="margin-right:6px;display:inline-flex;vertical-align:middle;">
            <i class="ph-fill ph-warning" aria-hidden="true"></i>
          </span>
          <span class="kbf-strong"><?php echo $pending_funds; ?> fund<?php echo $pending_funds>1?'s':''; ?> under review.</span>
          Not visible to sponsors yet. Usually 3-5 days. You'll be notified after approval.
          <span style="margin-left:6px;font-weight:700;">View all funds below.</span>
        </div>
      </div>
      <?php endif; ?>
      <div class="kbf-stats">
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <i class="ph ph-cards-three kbf-stat-icon-img kbf-icon" style="font-size:20px" aria-hidden="true"></i>
          </div>
          <div><div class="kbf-stat-label">Total Funds</div><div class="kbf-stat-value"><?php echo $total_funds; ?></div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <i class="ph ph-piggy-bank kbf-stat-icon-img kbf-icon" style="font-size:20px" aria-hidden="true"></i>
          </div>
          <div><div class="kbf-stat-label">Total Raised</div><div class="kbf-stat-value">&#8369;<?php echo $format_currency($total_raised, 0); ?></div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <i class="ph ph-users kbf-stat-icon-img kbf-icon" style="font-size:20px" aria-hidden="true"></i>
          </div>
          <div><div class="kbf-stat-label">Total Sponsors</div><div class="kbf-stat-value"><?php echo $total_sponsors; ?></div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <i class="ph ph-eye kbf-stat-icon-img kbf-icon" style="font-size:20px" aria-hidden="true"></i>
          </div>
          <div><div class="kbf-stat-label">Active Now</div><div class="kbf-stat-value"><?php echo $active_funds; ?></div></div>
        </div>
      </div>

      <div class="kbf-section-header" style="margin-bottom:14px;align-items:center;">
        <h3 class="kbf-section-title">All My Funds</h3>
        <div class="kbf-inline-filters" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
            <button type="button" class="kbf-btn kbf-btn-secondary kbf-home-filter-btn" onclick="kbfHomeOpenSheet()">
              <i class="ph ph-sliders kbf-icon" style="font-size:14px" aria-hidden="true"></i>
              Filters
            </button>
            <div class="kbf-form-group" id="kbf-filter-status-wrap" style="display:flex;align-items:center;gap:8px;margin:0;min-width:160px;">
              <span style="width:28px;height:28px;border-radius:8px;background:#eef4ff;display:inline-flex;align-items:center;justify-content:center;">
                <i class="ph ph-tag kbf-icon" style="font-size:14px" aria-hidden="true"></i>
              </span>
              <select id="kbf-filter-status" style="padding:7px 10px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:12.5px;background:#fff;color:var(--kbf-text);min-width:160px;">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="suspended">Suspended</option>
              <option value="cancelled">Cancelled</option>
              <option value="completed">Completed</option>
            </select>
          </div>
            <div class="kbf-form-group" id="kbf-filter-escrow-wrap" style="display:flex;align-items:center;gap:8px;margin:0;min-width:160px;">
              <span style="width:28px;height:28px;border-radius:8px;background:#eef4ff;display:inline-flex;align-items:center;justify-content:center;">
                <i class="ph ph-funnel kbf-icon" style="font-size:14px" aria-hidden="true"></i>
              </span>
              <select id="kbf-filter-escrow" style="padding:7px 10px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:12.5px;background:#fff;color:var(--kbf-text);min-width:160px;">
                <option value="all">All Escrow</option>
                <option value="holding">Holding</option>
              <option value="released">Released</option>
            </select>
          </div>
        </div>
      </div>
      <div class="kbf-home-sheet-overlay" id="kbf-home-sheet-overlay" onclick="kbfHomeCloseSheet()"></div>
      <div class="kbf-home-sheet" id="kbf-home-sheet">
        <div class="kbf-home-sheet-handle"></div>
        <div class="kbf-home-sheet-header">
          <h3 class="kbf-section-title" style="margin:0;">Filter Campaigns</h3>
          <button type="button" class="kbf-btn kbf-btn-secondary" onclick="kbfHomeCloseSheet()">&times;</button>
        </div>
        <div class="kbf-home-sheet-body">
          <div class="kbf-form-group">
            <label>Status</label>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
              <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-home-group="status" data-kbf-home-value="all">
                <i class="ph ph-app-window kbf-icon" aria-hidden="true"></i>
                All
              </button>
              <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-home-group="status" data-kbf-home-value="active">
                <i class="ph ph-check-circle kbf-icon" aria-hidden="true"></i>
                Active
              </button>
              <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-home-group="status" data-kbf-home-value="pending">
                <i class="ph ph-clock kbf-icon" aria-hidden="true"></i>
                Pending
              </button>
              <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-home-group="status" data-kbf-home-value="suspended">
                <i class="ph ph-pause-circle kbf-icon" aria-hidden="true"></i>
                Suspended
              </button>
              <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-home-group="status" data-kbf-home-value="cancelled">
                <i class="ph ph-prohibit kbf-icon" aria-hidden="true"></i>
                Cancelled
              </button>
              <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-home-group="status" data-kbf-home-value="completed">
                <i class="ph ph-flag-checkered kbf-icon" aria-hidden="true"></i>
                Completed
              </button>
            </div>
          </div>
          <div class="kbf-form-group">
            <label>Escrow</label>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
              <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-home-group="escrow" data-kbf-home-value="all">
                <i class="ph ph-cards kbf-icon" aria-hidden="true"></i>
                All
              </button>
              <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-home-group="escrow" data-kbf-home-value="holding">
                <i class="ph ph-lock-key kbf-icon" aria-hidden="true"></i>
                Holding
              </button>
              <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-home-group="escrow" data-kbf-home-value="released">
                <i class="ph ph-lock-open kbf-icon" aria-hidden="true"></i>
                Released
              </button>
            </div>
          </div>
        </div>
        <div class="kbf-home-sheet-actions">
          <button type="button" class="kbf-btn kbf-btn-primary" id="kbf-home-sheet-apply">Apply Filters</button>
          <button type="button" class="kbf-btn kbf-btn-secondary" id="kbf-home-sheet-clear">Clear all</button>
        </div>
      </div>
      <?php if(empty($funds)): ?>
        <div class="kbf-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><p>No funds created yet.</p></div>
      <?php else: ?>
      <div class="kbf-card-list" data-kbf-card-pager="home">
      <?php foreach($funds as $f):
        $pct = $f->goal_amount > 0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $sc  = isset($sponsor_counts_by_fund[(int)$f->id]) ? (int)$sponsor_counts_by_fund[(int)$f->id] : 0;
        $deadline_ts = null;
        if (!empty($f->deadline)) {
          $parsed_deadline = strtotime((string)$f->deadline);
          if ($parsed_deadline !== false) {
            $deadline_ts = (int) $parsed_deadline;
          }
        }
        $days_left = ($deadline_ts !== null) ? max(0, (int) ceil(($deadline_ts - time())/86400)) : null;
        $is_inactive = (strtolower((string)$f->status) === 'active' && $days_left !== null && $days_left <= 0);
        $photo_list = $f->photos ? json_decode($f->photos, true) : [];
        $photo_json = wp_json_encode(array_values(array_filter(is_array($photo_list) ? $photo_list : [])));
        $benefit_list = $f->benefits ? json_decode($f->benefits, true) : [];
        $benefit_json = wp_json_encode(array_values(array_filter(is_array($benefit_list) ? $benefit_list : [])));
        $last_wd = isset($last_withdrawal_by_fund[(int)$f->id]) ? $last_withdrawal_by_fund[(int)$f->id] : null;
        $last_appeal = isset($last_appeal_by_fund[(int)$f->id]) ? $last_appeal_by_fund[(int)$f->id] : null;
        $appeal_pending = $last_appeal && $last_appeal->status === 'open';
        ?>
        <div class="kbf-card" data-status="<?php echo esc_attr($f->status); ?>" data-escrow="<?php echo esc_attr($f->escrow_status); ?>">
          <?php if($last_wd && $last_wd->status === 'pending'): ?>
          <div class="kbf-alert kbf-alert-warning kbf-alert-noicon kbf-home-alert" style="margin-bottom:12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <div>
              <span style="margin-right:6px;display:inline-flex;vertical-align:middle;">
                <i class="ph-fill ph-warning" aria-hidden="true"></i>
              </span>
              <span class="kbf-strong">Withdrawal Pending:</span>
              <span>Your request is being reviewed by admin (2-5 business days).</span>
            </div>
          </div>
          <?php endif; ?>
          <?php if($last_wd && $last_wd->status === 'rejected'): ?>
          <div class="kbf-alert kbf-alert-error kbf-alert-noicon kbf-home-alert" style="margin-bottom:12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
              <span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;">
                <i class="ph-fill ph-x-circle" aria-hidden="true"></i>
              </span>
            <div>
              <span class="kbf-strong">Withdrawal Rejected:</span>
              <?php if(!empty($last_wd->admin_notes)): ?>
                <?php echo esc_html($last_wd->admin_notes); ?>
              <?php else: ?>
                <span>No rejection note was provided.</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          <?php if($f->status === 'suspended'): ?>
          <div class="kbf-alert kbf-alert-error kbf-alert-noicon kbf-home-alert" style="margin-bottom:12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <div>
              <?php if($last_appeal && $last_appeal->status === 'open'): ?>
                <span class="kbf-strong" style="display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-clock kbf-icon" aria-hidden="true"></i>Appeal Submitted:</span>
                Your appeal is under admin review. We'll notify you once a decision is made.
              <?php elseif($last_appeal && $last_appeal->status === 'rejected'): ?>
                <span class="kbf-strong" style="display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-prohibit kbf-icon" aria-hidden="true"></i>Appeal Rejected:</span>
                <?php if(!empty($last_appeal->admin_notes)): ?>
                  <?php echo esc_html($last_appeal->admin_notes); ?>
                <?php elseif($f->admin_notes): ?>
                  <?php echo esc_html($f->admin_notes); ?>
                <?php else: ?>
                  Your fund remains suspended. Contact support for details.
                <?php endif; ?>
              <?php else: ?>
                <span class="kbf-strong" style="display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-prohibit kbf-icon" aria-hidden="true"></i>Fund Suspended</span> -- Not visible to sponsors.
                <?php if($f->admin_notes): ?>
                  Admin note: <?php echo esc_html($f->admin_notes); ?>
                <?php else: ?>
                  Contact support for details.
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
          <?php elseif($f->status === 'cancelled'): ?>
          <div class="kbf-alert kbf-alert-error kbf-alert-noicon kbf-home-alert" style="margin-bottom:12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
              <span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;">
                <i class="ph-fill ph-x-circle" aria-hidden="true"></i>
              </span>
            <div>
              <span class="kbf-strong">Cancelled:</span>
              <?php if(!empty($f->admin_notes)): ?>
                <?php echo esc_html($f->admin_notes); ?>
              <?php else: ?>
                <span>This campaign was cancelled and is no longer visible to sponsors. Contact support if you need details.</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          <div class="kbf-card-header">
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                  <span class="kbf-clamp-2 kbf-strong" style="font-size:15px;max-width:520px;"><?php echo esc_html($f->title); ?></span>
                <?php
                  $status_raw = strtolower((string)$f->status);
                  $allowed_statuses = ['active','pending','completed','suspended','cancelled','rejected','draft'];
                  $status_class = $is_inactive ? 'suspended' : (in_array($status_raw, $allowed_statuses, true) ? $status_raw : 'unknown');
                  $status_label = $is_inactive ? 'Inactive' : ucfirst($status_raw !== '' ? $status_raw : 'unknown');
                ?>
                <span class="kbf-badge kbf-badge-<?php echo esc_attr(sanitize_html_class($status_class)); ?>"><?php echo esc_html($status_label); ?></span>
              </div>
              <div class="kbf-meta">
                <div class="kbf-meta-row">
                  <span class="kbf-meta-item">
                    <i class="ph ph-tag kbf-icon" aria-hidden="true"></i>
                      <?php echo esc_html(ucwords(strtolower((string)$f->category))); ?>
                  </span>
                  <?php if($days_left!==null): ?>
                    <span class="kbf-meta-divider"></span>
                    <span class="kbf-meta-item kbf-meta-strong" style="color:<?php echo $days_left<7?'#dc2626':'#64748b';?>;">
                      <i class="ph ph-clock kbf-icon" aria-hidden="true"></i>
                      <?php echo $days_left; ?>d left
                    </span>
                  <?php endif; ?>
                  <span class="kbf-meta-divider"></span>
                  <span class="kbf-meta-item">
                    <i class="ph ph-users kbf-icon" aria-hidden="true"></i>
                    <?php echo $sc; ?> sponsor<?php echo $sc !== 1 ? 's' : ''; ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="kbf-fund-amounts">
            <span><span class="kbf-strong">&#8369;<?php echo $format_currency($f->raised_amount); ?></span>raised</span>
            <span><span class="kbf-strong">&#8369;<?php echo $format_currency($f->goal_amount); ?></span>goal</span>
            <span><span class="kbf-strong"><?php echo round($pct); ?>%</span>funded</span>
          </div>
          <?php
            $wd_block = $last_wd && in_array($last_wd->status, ['pending','approved','released']);
          ?>
            <div class="kbf-card-actions">
              <?php
                $deadline_passed = ($deadline_ts !== null) && ($deadline_ts <= time());
                $escrow_req = isset($escrow_requests[(int)$f->id]) ? $escrow_requests[(int)$f->id] : null;
                $escrow_pending = $escrow_req && $escrow_req->status === 'pending';
                $escrow_rejected = $escrow_req && $escrow_req->status === 'rejected';
              ?>
              <?php $fund_token = function_exists('kbf_get_or_create_fund_token') ? kbf_get_or_create_fund_token((int) $f->id) : ''; ?>
              <a class="kbf-btn kbf-btn-primary kbf-btn-sm" href="<?php echo esc_url(add_query_arg('fund', $fund_token ?: (int) $f->id, $fund_details_url)); ?>">
                View Details
              </a>
              <?php if($f->status === 'suspended'): ?>
                <?php if($appeal_pending): ?>
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" type="button" disabled aria-disabled="true" title="Appeal already submitted and under review">
                    Appeal Pending Review
                  </button>
                <?php else: ?>
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" type="button" onclick="kbfOpenAppeal(<?php echo (int) $f->id; ?>,'<?php echo esc_js($f->title); ?>')">
                    Appeal Suspension
                  </button>
                <?php endif; ?>
              <?php endif; ?>
              <?php if($f->status==='active' && $f->escrow_status==='holding' && $deadline_passed && $f->raised_amount < $f->goal_amount): ?>
                <?php if($escrow_pending): ?>
                  <span class="kbf-badge kbf-badge-pending">Escrow Request Pending</span>
                <?php else: ?>
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenEscrowRequest(<?php echo (int) $f->id; ?>)">
                    Request Escrow
                  </button>
                <?php endif; ?>
                <?php if($escrow_rejected): ?>
                  <span class="kbf-badge kbf-badge-cancelled">Escrow Request Rejected</span>
                <?php endif; ?>
              <?php endif; ?>
                <?php if(in_array($f->status,['active','completed'], true) && $f->escrow_status==='released'): ?>
                  <?php if($wd_block): ?>
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-btn-withdraw" disabled aria-disabled="true" title="Withdrawal pending">
                    <i class="ph ph-money-wavy kbf-icon" style="font-size:12px; filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                    Request Withdrawal
                  </button>
                  <?php else: ?>
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-btn-withdraw" onclick="kbfOpenWd(<?php echo (int) $f->id; ?>,<?php echo wp_json_encode((float) $f->raised_amount); ?>,'<?php echo esc_js($f->title); ?>')">
                    <i class="ph ph-money-wavy kbf-icon" style="font-size:12px; filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                    Request Withdrawal
                  </button>
                  <?php endif; ?>
                <?php endif; ?>
              <div class="kbf-card-more-wrap">
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfToggleHomeMore(event,'<?php echo esc_js((string) ((int) $f->id)); ?>')" title="More" data-tooltip="More">
                  <i class="ph ph-dots-three-vertical kbf-icon" style="font-size:12px; filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                </button>
                <div class="kbf-card-more-menu" id="kbf-home-more-<?php echo esc_attr((int) $f->id); ?>">
                <?php if(in_array($f->status,['active','pending'], true)): ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenEdit(<?php echo (int) $f->id; ?>,'<?php echo esc_js($f->title); ?>','<?php echo esc_js($f->description); ?>','<?php echo esc_js($f->location); ?>','<?php echo esc_js($f->deadline); ?>',<?php echo (int)$f->auto_return; ?>,'<?php echo esc_js($photo_json); ?>','<?php echo esc_js($benefit_json); ?>')">
                  <i class="ph ph-pencil-simple kbf-icon" style="font-size:12px; filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                  Edit
                </button>
                <?php endif; ?>
                <?php if(in_array($f->status, ['active','completed'], true)): ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-more-milestone" type="button" onclick="kbfOpenMilestoneModal(<?php echo (int) $f->id; ?>,'<?php echo esc_js($f->title); ?>')">
                  <i class="ph ph-plus kbf-icon" style="font-size:12px; color:currentColor;" aria-hidden="true"></i>
                  Add Story
                </button>
                <?php endif; ?>
                <?php if($f->status==='active' && $f->raised_amount>=$f->goal_amount): ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfMarkComplete(<?php echo (int) $f->id; ?>)">
                  <i class="ph-bold ph-check kbf-icon" style="font-size:12px; filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                  Mark Complete
                </button>
                <?php endif; ?>
                <?php if(in_array($f->status,['active','completed'], true) && $f->escrow_status==='released'): ?>
                  <?php if($wd_block): ?>
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-btn-withdraw kbf-more-withdraw" disabled aria-disabled="true" title="Withdrawal pending">
                    <i class="ph ph-money-wavy kbf-icon" style="font-size:12px; filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                    Request Withdrawal
                  </button>
                  <?php else: ?>
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-btn-withdraw kbf-more-withdraw" onclick="kbfOpenWd(<?php echo (int) $f->id; ?>,<?php echo wp_json_encode((float) $f->raised_amount); ?>,'<?php echo esc_js($f->title); ?>')">
                    <i class="ph ph-money-wavy kbf-icon" style="font-size:12px; filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                    Request Withdrawal
                  </button>
                  <?php endif; ?>
                <?php endif; ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')">
                  <i class="ph ph-share kbf-icon" style="font-size:12px; filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                  Share
                </button>
                <?php if($f->status==='pending'): ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenTrashFund(<?php echo (int) $f->id; ?>,'<?php echo esc_js($f->title); ?>','cancel')">
                  <i class="ph ph-prohibit kbf-icon" style="font-size:12px; filter:invert(34%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                  Cancel
                </button>
                <?php endif; ?>
                <?php if(in_array($f->status,['cancelled','suspended'], true)): ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenTrashFund(<?php echo (int) $f->id; ?>,'<?php echo esc_js($f->title); ?>','trash')">
                  <i class="ph ph-trash-simple kbf-icon" style="font-size:12px; filter:invert(34%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                  Trash
                </button>
            <?php endif; ?>
              </div>
            </div>
          </div>
          <?php
          $sponsors = isset($sponsor_preview_by_fund[(int)$f->id]) ? $sponsor_preview_by_fund[(int)$f->id] : [];
          if(!empty($sponsors)): ?>
          <details class="kbf-sponsor-details">
            <summary>View Sponsors (<?php echo $sc; ?>)</summary>
            <div class="kbf-sponsor-details-content">
              <div class="kbf-table-wrap kbf-home-sponsor-wrap" style="margin-top:10px;" data-kbf-table-desc="Lists recent sponsors for this fundraiser and their contributions.">
              <table class="kbf-table kbf-home-sponsor-table">
                <colgroup>
                  <col><col><col><col>
                </colgroup>
                <thead><tr><th>Sponsor</th><th>Message</th><th>Amount</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($sponsors as $sp): ?>
                  <tr>
                    <td><?php echo $sp->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($sp->sponsor_name); ?></td>
                    <td><?php echo esc_html(!empty($sp->message) ? $sp->message : '--'); ?></td>
                    <td><span style="color:var(--kbf-blue);" class="kbf-strong">&#8369;<?php echo $format_currency($sp->amount); ?></span></td>
                    <?php $sp_created_ts = !empty($sp->created_at) ? strtotime((string) $sp->created_at) : false; ?>
                    <td class="kbf-meta"><?php echo esc_html($sp_created_ts !== false ? date('M d, Y', $sp_created_ts) : '--'); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
              </div>
            </div>
          </details>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      </div>
      <div class="kbf-empty kbf-home-empty" style="display:none;padding:60px 20px;">
        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        <p>No funds match your filters.</p>
        <button class="kbf-btn kbf-btn-primary kbf-home-clear" type="button" style="margin-top:12px;">Clear Filters</button>
      </div>
      <?php endif; ?>
      <div class="kbf-cta-card">
        <div>
          <div class="kbf-cta-eyebrow">Next Step</div>
          <div class="kbf-cta-title">Launch your next fundraiser</div>
          <div class="kbf-cta-sub">Create a new fund to mobilize support. Keep updates consistent to build trust and improve conversion.</div>
          <div class="kbf-cta-checklist">
            <div class="kbf-cta-check">
              <i><i class="ph ph-check" aria-hidden="true"></i></i>
              Add a clear goal and deadline
            </div>
            <div class="kbf-cta-check">
              <i><i class="ph ph-check" aria-hidden="true"></i></i>
              Upload 2-3 photos to build trust
            </div>
            <div class="kbf-cta-check">
              <i><i class="ph ph-check" aria-hidden="true"></i></i>
              Share once it's live to get first sponsors
            </div>
            <div class="kbf-cta-check">
              <i><i class="ph ph-check" aria-hidden="true"></i></i>
              Post a quick update every story
            </div>
          </div>
        </div>
        <div class="kbf-cta-right">
          <div class="kbf-cta-actions">
            <button class="kbf-btn kbf-btn-primary" onclick="kbfOpenModal('kbf-modal-create')">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Start a Fundraiser
            </button>
            <a class="kbf-btn kbf-btn-secondary" href="<?php echo esc_url($find_funds_url); ?>">
              Browse Funds
            </a>
          </div>
          <div class="kbf-cta-note">Start a fund in under 3 minutes. We'll guide you step-by-step.</div>
        </div>
      </div>
      
    <!-- ================== JS ================== -->
    <script>
      (function(){
        var statusEl = document.getElementById('kbf-filter-status');
        var escrowEl = document.getElementById('kbf-filter-escrow');
        if(!statusEl || !escrowEl) return;
        /**
         * @function  applyFilters
         * @purpose   Applies selected status and escrow filters to fund cards and triggers rerendering.
         * @used-by   [status change listener, escrow change listener, initial filter run]
         * @calls     [window.kbfHomeRenderCards]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function applyFilters(){
          var statusVal = statusEl.value;
          var escrowVal = escrowEl.value;
          var cards = document.querySelectorAll('.kbf-section .kbf-card[data-status]');
          cards.forEach(function(card){
            var matchStatus = (statusVal === 'all') || (card.getAttribute('data-status') === statusVal);
            var matchEscrow = (escrowVal === 'all') || (card.getAttribute('data-escrow') === escrowVal);
            card.dataset.kbfFilterHidden = (matchStatus && matchEscrow) ? '0' : '1';
          });
          if (window.kbfHomeRenderCards) window.kbfHomeRenderCards();
        }
        window.kbfHomeApplyFilters = applyFilters;
        statusEl.addEventListener('change', applyFilters);
        escrowEl.addEventListener('change', applyFilters);
        applyFilters();
      })();

      (function(){
        var sheet = document.getElementById('kbf-home-sheet');
        var overlay = document.getElementById('kbf-home-sheet-overlay');
        var statusEl = document.getElementById('kbf-filter-status');
        var escrowEl = document.getElementById('kbf-filter-escrow');
        var applyBtn = document.getElementById('kbf-home-sheet-apply');
        var clearBtn = document.getElementById('kbf-home-sheet-clear');
        if(!sheet || !overlay || !statusEl || !escrowEl) return;

        /**
         * @function  setGroupValue
         * @purpose   Updates active state for mobile sheet filter button groups.
         * @used-by   [syncFromSelects, window.kbfHomeClearSheet, mobile group button click handler]
         * @calls     [document.querySelectorAll, classList.toggle]
         * @params    [string group - Filter group key, string value - Active value for the group]
         * @returns   [void]
         * @status    ACTIVE
         */
        function setGroupValue(group, value){
          var buttons = document.querySelectorAll('[data-kbf-home-group="'+group+'"]');
          buttons.forEach(function(b){
            var isActive = (b.getAttribute('data-kbf-home-value') === value);
            b.classList.toggle('is-active', isActive);
          });
        }
        /**
         * @function  syncFromSelects
         * @purpose   Synchronizes mobile sheet selection states from desktop select controls.
         * @used-by   [window.kbfHomeOpenSheet]
         * @calls     [setGroupValue]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function syncFromSelects(){
          setGroupValue('status', statusEl.value || 'all');
          setGroupValue('escrow', escrowEl.value || 'all');
        }
        /**
         * @function  kbfHomeOpenSheet
         * @purpose   Opens the mobile filter sheet and locks body scroll.
         * @used-by   [Filters button onclick]
         * @calls     [syncFromSelects, classList.add]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfHomeOpenSheet = function(){
          syncFromSelects();
          sheet.classList.add('open');
          overlay.classList.add('open');
          document.body.style.overflow = 'hidden';
        };
        /**
         * @function  kbfHomeCloseSheet
         * @purpose   Closes the mobile filter sheet and restores page scrolling.
         * @used-by   [sheet close button onclick, overlay onclick, apply/clear flows, resize handler]
         * @calls     [classList.remove]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfHomeCloseSheet = function(){
          sheet.classList.remove('open');
          overlay.classList.remove('open');
          document.body.style.overflow = '';
        };
        /**
         * @function  getActiveValue
         * @purpose   Reads the currently active value from a filter group inside the mobile sheet.
         * @used-by   [window.kbfHomeApplySheet]
         * @calls     [sheet.querySelector, getAttribute]
         * @params    [string group - Filter group key]
         * @returns   [string - Active value or empty string]
         * @status    ACTIVE
         */
        function getActiveValue(group){
          var active = sheet.querySelector('[data-kbf-home-group="'+group+'"].is-active');
          return active ? active.getAttribute('data-kbf-home-value') : '';
        }
        /**
         * @function  kbfHomeApplySheet
         * @purpose   Applies mobile sheet selections to desktop filters and refreshes card visibility.
         * @used-by   [Apply Filters button listener, window.kbfHomeClearSheet]
         * @calls     [getActiveValue, window.kbfHomeApplyFilters, window.kbfHomeRenderCards, window.kbfHomeCloseSheet]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfHomeApplySheet = function(){
          var statusVal = getActiveValue('status') || 'all';
          var escrowVal = getActiveValue('escrow') || 'all';
          statusEl.value = statusVal;
          escrowEl.value = escrowVal;
          if (window.kbfHomeApplyFilters) window.kbfHomeApplyFilters();
          if (window.kbfHomeRenderCards) window.kbfHomeRenderCards();
          window.kbfHomeCloseSheet();
        };
        /**
         * @function  kbfHomeClearSheet
         * @purpose   Resets sheet filters to default values and reapplies them.
         * @used-by   [Clear all button listener]
         * @calls     [setGroupValue, window.kbfHomeApplySheet]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfHomeClearSheet = function(){
          setGroupValue('status', 'all');
          setGroupValue('escrow', 'all');
          window.kbfHomeApplySheet();
        };
        sheet.querySelectorAll('[data-kbf-home-group]').forEach(function(btn){
          btn.addEventListener('click', function(){
            setGroupValue(btn.getAttribute('data-kbf-home-group') || '', btn.getAttribute('data-kbf-home-value') || '');
          });
        });
        if (applyBtn) applyBtn.addEventListener('click', window.kbfHomeApplySheet);
        if (clearBtn) clearBtn.addEventListener('click', window.kbfHomeClearSheet);
        window.addEventListener('resize', function(){
          if (window.innerWidth >= 720) window.kbfHomeCloseSheet();
        });
      })();

      (function(){
        var wrap = document.querySelector('.kbf-card-list[data-kbf-card-pager="home"]');
        var emptyEl = document.querySelector('.kbf-home-empty');
        var clearBtn = document.querySelector('.kbf-home-clear');
        var statusEl = document.getElementById('kbf-filter-status');
        var escrowEl = document.getElementById('kbf-filter-escrow');
        if(!wrap || wrap.dataset.kbfPager === 'on') return;
        var cards = Array.prototype.slice.call(wrap.querySelectorAll('.kbf-card[data-status]'));
        if(cards.length === 0) return;
        wrap.dataset.kbfPager = 'on';

        var pager = document.createElement('div');
        pager.className = 'kbf-table-pager';
        pager.innerHTML = '' +
          '<div class="kbf-table-pager-left">Show Cards&nbsp;' +
          '<select class="kbf-table-rows">' +
            '<option value="3">3</option>' +
            '<option value="5" selected>5</option>' +
            '<option value="10">10</option>' +
          '</select></div>' +
          '<div class="kbf-table-pager-right">' +
            '<button class="kbf-table-pager-btn kbf-table-prev" type="button">Prev</button>' +
            '<span class="kbf-table-pager-page">1 / 1</span>' +
            '<button class="kbf-table-pager-btn kbf-table-next" type="button">Next</button>' +
          '</div>';
        wrap.insertAdjacentElement('afterend', pager);

        var select = pager.querySelector('.kbf-table-rows');
        var prevBtn = pager.querySelector('.kbf-table-prev');
        var nextBtn = pager.querySelector('.kbf-table-next');
        var pageLabel = pager.querySelector('.kbf-table-pager-page');
        var page = 1;
        var perPage = parseInt(select.value, 10) || 5;
        if (clearBtn) {
          clearBtn.addEventListener('click', function(){
            if (statusEl) statusEl.value = 'all';
            if (escrowEl) escrowEl.value = 'all';
            if (typeof window.kbfHomeApplyFilters === 'function') {
              window.kbfHomeApplyFilters();
            } else if (window.kbfHomeRenderCards) {
              window.kbfHomeRenderCards();
            }
            if (window.kbfHomeCloseSheet) window.kbfHomeCloseSheet();
          });
        }

        /**
         * @function  getFilteredCards
         * @purpose   Returns cards currently passing active filter constraints.
         * @used-by   [render]
         * @calls     [Array.filter]
         * @params    [none]
         * @returns   [Array - Filtered card elements]
         * @status    ACTIVE
         */
        function getFilteredCards(){
          return cards.filter(function(card){ return card.dataset.kbfFilterHidden !== '1'; });
        }

        /**
         * @function  scrollToCards
         * @purpose   Scrolls viewport back to the card section after pager navigation.
         * @used-by   [setLoading]
         * @calls     [Element.closest, scrollIntoView, window.scrollTo]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function scrollToCards(){
          try {
            var target = wrap.closest('.kbf-section') || wrap;
            if (target && target.scrollIntoView) {
              target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }
          } catch(e) {
            window.scrollTo(0, 0);
          }
        }

        /**
         * @function  render
         * @purpose   Renders paginated card visibility state and pager controls.
         * @used-by   [setLoading, rows-per-page change, window.kbfHomeRenderCards, initial load]
         * @calls     [getFilteredCards]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function render(){
          var visible = getFilteredCards();
          var total = visible.length;
          var pages = Math.max(1, Math.ceil(total / perPage));
          if(page > pages) page = pages;
          var start = (page - 1) * perPage;
          var end = start + perPage;
          cards.forEach(function(card){
            card.style.display = 'none';
          });
          visible.forEach(function(card, i){
            if (i >= start && i < end) card.style.display = '';
          });
          pageLabel.textContent = page + ' / ' + pages;
          prevBtn.disabled = page <= 1;
          nextBtn.disabled = page >= pages;
          pager.style.display = total > 0 ? 'flex' : 'none';
          if (emptyEl) emptyEl.style.display = total > 0 ? 'none' : 'flex';
          if (wrap) wrap.style.display = total > 0 ? '' : 'none';
        }
        /**
         * @function  getPagerLoadingDelay
         * @purpose   Computes pager loading delay with longer feedback on slower network conditions.
         * @used-by   [setLoading]
         * @calls     [none]
         * @params    [none]
         * @returns   [number - Loading delay in milliseconds]
         * @status    ACTIVE
         */
        function getPagerLoadingDelay(){
          var delay = 250;
          try {
            var connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            var effectiveType = connection && connection.effectiveType ? String(connection.effectiveType).toLowerCase() : '';
            if (effectiveType === 'slow-2g' || effectiveType === '2g') {
              delay = 900;
            } else if (effectiveType === '3g') {
              delay = 650;
            }
          } catch (e) {}
          return delay;
        }
        /**
         * @function  setLoading
         * @purpose   Shows a temporary loading state on pager controls before rerender and scroll.
         * @used-by   [Prev button click handler, Next button click handler]
         * @calls     [getPagerLoadingDelay, render, scrollToCards, setTimeout]
         * @params    [HTMLElement btn - Pager button element]
         * @returns   [void]
         * @status    ACTIVE
         */
        function setLoading(btn){
          if (!btn || btn.classList.contains('is-loading')) return;
          var delay = getPagerLoadingDelay();
          btn.classList.add('is-loading');
          btn.disabled = true;
          if (select) select.disabled = true;
          if (btn === prevBtn && nextBtn) nextBtn.disabled = true;
          if (btn === nextBtn && prevBtn) prevBtn.disabled = true;
          setTimeout(function(){
            btn.classList.remove('is-loading');
            render();
            scrollToCards();
            if (select) select.disabled = false;
          }, delay);
        }
        select.addEventListener('change', function(){
          perPage = parseInt(this.value, 10) || 5;
          page = 1;
          render();
        });
        prevBtn.addEventListener('click', function(){
          if(page > 1){ page--; setLoading(prevBtn); }
        });
        nextBtn.addEventListener('click', function(){
          if(nextBtn.disabled) return;
          page++;
          setLoading(nextBtn);
        });

        /**
         * @function  kbfHomeRenderCards
         * @purpose   Resets pager to page one and rerenders current filtered cards.
         * @used-by   [window.kbfHomeApplyFilters, window.kbfHomeApplySheet, clear filters fallback path]
         * @calls     [render]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfHomeRenderCards = function(){
          page = 1;
          render();
        };

        render();
      })();

      /**
       * @function  kbfToggleHomeMore
       * @purpose   Toggles the per-card overflow action menu and closes other open menus.
       * @used-by   [More button onclick]
       * @calls     [document.getElementById, querySelectorAll, classList.toggle]
       * @params    [Event e - Click event, string id - Fund identifier for menu lookup]
       * @returns   [void]
       * @status    ACTIVE
       */
      window.kbfToggleHomeMore=function(e,id){
        if(e) e.stopPropagation();
        var menu = document.getElementById('kbf-home-more-' + id);
        if(!menu) return;
        var card = menu.closest('.kbf-card');
        menu.onclick = function(ev){ ev.stopPropagation(); };
        document.querySelectorAll('.kbf-card-more-menu.open').forEach(function(m){
          if(m !== menu) m.classList.remove('open');
        });
        document.querySelectorAll('.kbf-card.is-menu-open').forEach(function(c){
          if(!card || c !== card) c.classList.remove('is-menu-open');
        });
        menu.classList.toggle('open');
        if(card){ card.classList.toggle('is-menu-open', menu.classList.contains('open')); }
      };
      document.addEventListener('click', function(){
        document.querySelectorAll('.kbf-card-more-menu.open').forEach(function(m){
          m.classList.remove('open');
        });
        document.querySelectorAll('.kbf-card.is-menu-open').forEach(function(c){
          c.classList.remove('is-menu-open');
        });
      });

      (function(){
        var items = document.querySelectorAll('.kbf-sponsor-details');
        if (!items || !items.length) return;

        /**
         * @function  openDetails
         * @purpose   Expands a sponsor details accordion section with transition state updates.
         * @used-by   [sponsor details summary click handler]
         * @calls     [classList.add]
         * @params    [HTMLDetailsElement details - Details container, HTMLElement content - Collapsible content element]
         * @returns   [void]
         * @status    ACTIVE
         */
        function openDetails(details, content){
          details.open = true;
          details.classList.add('is-open');
          content.style.maxHeight = '0px';
          content.offsetHeight;
          content.style.maxHeight = content.scrollHeight + 'px';
        }

        /**
         * @function  closeDetails
         * @purpose   Collapses a sponsor details accordion section and finalizes close on transition end.
         * @used-by   [sponsor details summary click handler]
         * @calls     [classList.remove, addEventListener]
         * @params    [HTMLDetailsElement details - Details container, HTMLElement content - Collapsible content element]
         * @returns   [void]
         * @status    ACTIVE
         */
        function closeDetails(details, content){
          details.classList.remove('is-open');
          content.style.maxHeight = content.scrollHeight + 'px';
          content.offsetHeight;
          content.style.maxHeight = '0px';
          var onEnd = function(e){
            if (e.propertyName !== 'max-height') return;
            content.removeEventListener('transitionend', onEnd);
            details.open = false;
          };
          content.addEventListener('transitionend', onEnd);
        }

        items.forEach(function(details){
          var summary = details.querySelector('summary');
          var content = details.querySelector('.kbf-sponsor-details-content');
          if (!summary || !content) return;

          if (details.open) {
            details.classList.add('is-open');
            content.style.maxHeight = content.scrollHeight + 'px';
          } else {
            details.classList.remove('is-open');
            content.style.maxHeight = '0px';
          }

          summary.addEventListener('click', function(e){
            e.preventDefault();
            if (details.classList.contains('is-open')) {
              closeDetails(details, content);
            } else {
              openDetails(details, content);
            }
          });
        });
      })();
      
    </script>
    </div>
    <?php return ob_get_clean();
}














