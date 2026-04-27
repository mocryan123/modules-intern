<?php
/*
 * KBF user dashboard tab: Find Funds.
 */

/**
 * @function  kbf_dashboard_find_funds_tab
 * @purpose   Renders the Find Funds dashboard tab with filters, cards, modals, and client scripts.
 * @used-by   [dashboard sections renderer in user/partials/dashboard/sections.php]
 * @calls     [get_current_user_id, sanitize_text_field, kbf_get_page_url, add_query_arg, wp_create_nonce, kbf_get_categories, kbf_get_setting, wp_json_encode, WordPress DB APIs]
 * @params    [none]
 * @returns   [string - Buffered HTML markup for the tab content]
 * @status    ACTIVE
 */
function kbf_dashboard_find_funds_tab() {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $current_user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $current_user_email = ($current_user && !empty($current_user->user_email)) ? sanitize_email($current_user->user_email) : '';
    /**
     * @function  get_param
     * @purpose   Reads and sanitizes a GET parameter with a fallback default value.
     * @used-by   [kbf_dashboard_find_funds_tab parameter parsing flow]
     * @calls     [sanitize_text_field]
     * @params    [string $key - Query parameter key, mixed $default - Fallback value]
     * @returns   [string|mixed - Sanitized query value or default]
     * @status    ACTIVE
     */
    $get_param = function($key, $default = '') {
        return isset($_GET[$key]) ? sanitize_text_field($_GET[$key]) : $default;
    };
    /**
     * @function  build_like
     * @purpose   Builds a safe SQL LIKE token with wildcard wrappers for search filters.
     * @used-by   [kbf_dashboard_find_funds_tab search query builder]
     * @calls     [$wpdb->esc_like]
     * @params    [string $value - Raw search term]
     * @returns   [string - Escaped wildcard LIKE pattern]
     * @status    ACTIVE
     */
    $build_like = function($value) use ($wpdb) {
        return '%' . $wpdb->esc_like($value) . '%';
    };
    /**
     * @function  format_currency
     * @purpose   Formats numeric values for peso display with configurable decimal precision.
     * @used-by   [fund card amount output in kbf_dashboard_find_funds_tab]
     * @calls     [number_format]
     * @params    [float|int $amount - Numeric amount, int $decimals - Decimal places]
     * @returns   [string - Human-readable formatted number]
     * @status    ACTIVE
     */
    $format_currency = function($amount, $decimals = 0) {
        return number_format((float)$amount, $decimals);
    };

    // Filters from GET
    $q = $get_param('ff_q', '');
    $cat = $get_param('ff_cat', '');
    $sort = $get_param('ff_sort', 'newest');
    $saved_only = $get_param('ff_saved', '');

    // Payment result handling
    $payment_result = $get_param('kbf_payment', '');
    $payment_sid    = intval($get_param('sid', '0'));
    $payment_fund_id = intval($get_param('fund_id', '0'));
    $payment_fund_token = $get_param('fund', '');
    $show_payment_banner = in_array($payment_result, ['success', 'failed', 'cancelled'], true) && $current_user_id;
    $payment_banner_data = null;

    if ($show_payment_banner) {
        $pay_ft = $wpdb->prefix . 'kbf_funds';
        $pay_st = $wpdb->prefix . 'kbf_sponsorships';

        $pay_fund = null;
        if ($payment_fund_id > 0) {
            $pay_fund = $wpdb->get_row($wpdb->prepare("SELECT id,fund_token,title FROM {$pay_ft} WHERE id=%d", $payment_fund_id));
        }
        if (!$pay_fund && $payment_fund_token !== '') {
            $pay_fund = $wpdb->get_row($wpdb->prepare("SELECT id,fund_token,title FROM {$pay_ft} WHERE fund_token=%s", $payment_fund_token));
        }

        $pay_sponsorship = null;
        if ($payment_sid > 0 && $current_user_email !== '') {
            $pay_sponsorship = $wpdb->get_row($wpdb->prepare(
                "SELECT id,amount FROM {$pay_st} WHERE id=%d AND email=%s",
                $payment_sid,
                $current_user_email
            ));
        }

        $pay_fund_title   = $pay_fund ? $pay_fund->title : 'this fundraiser';
        $pay_fund_url     = kbf_get_page_url('fund_details');
        $pay_fund_url     = add_query_arg('fund', $payment_fund_token ?: $payment_fund_id, $pay_fund_url);
        $pay_sponsor_amount = $pay_sponsorship ? number_format((float)$pay_sponsorship->amount, 2) : '';

        if ($payment_result === 'success') {
            $payment_banner_data = [
                'type'     => 'success',
                'icon'     => 'ph-fill ph-check-circle',
                'title'    => 'Thank You!',
                'message'  => $pay_sponsor_amount !== ''
                    ? 'Your &#8369;' . $pay_sponsor_amount . ' sponsorship for <strong>' . esc_html($pay_fund_title) . '</strong> was received successfully.'
                    : 'Your sponsorship for <strong>' . esc_html($pay_fund_title) . '</strong> was received successfully.',
                'countdown' => 6,
                'redirect'  => $pay_fund_url,
                'btn_text'  => 'View Fundraiser',
            ];
        } elseif ($payment_result === 'failed') {
            $payment_banner_data = [
                'type'     => 'error',
                'icon'     => 'ph-fill ph-x-circle',
                'title'    => 'Payment Failed',
                'message'  => 'We couldn\'t process your payment for <strong>' . esc_html($pay_fund_title) . '</strong>. Please try again with a different payment method.',
                'countdown' => 8,
                'redirect'  => add_query_arg('kbf_retry', '1', $pay_fund_url),
                'btn_text'  => 'Try Again',
            ];
        } elseif ($payment_result === 'cancelled') {
            $payment_banner_data = [
                'type'     => 'warning',
                'icon'     => 'ph-fill ph-warning',
                'title'    => 'Payment Cancelled',
                'message'  => 'Your payment for <strong>' . esc_html($pay_fund_title) . '</strong> was cancelled. You can complete your sponsorship anytime.',
                'countdown' => 5,
                'redirect'  => $pay_fund_url,
                'btn_text'  => 'Return to Fundraiser',
            ];
        }
    }

    $where = "WHERE f.status='active' AND (f.deadline IS NULL OR f.deadline > NOW())"; $params = [];
    if($saved_only) {
        $where = "WHERE f.status IN ('active','completed') AND (f.status!='active' OR f.deadline IS NULL OR f.deadline > NOW())";
    }
    if($q)  {
        $like = $build_like($q);
        $where .= " AND (f.title LIKE %s OR f.location LIKE %s OR u.display_name LIKE %s OR u.user_login LIKE %s OR umsn.meta_value LIKE %s)";
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if($cat){ $where .= " AND f.category=%s"; $params[] = $cat; }
    $order = $sort === 'most_funded' ? 'f.raised_amount DESC' : ($sort === 'ending_soon' ? 'f.deadline ASC' : 'f.created_at DESC');
    $join = '';
    if($saved_only) {
        if(!$current_user_id) {
            $funds = [];
        } else {
            $sf = $wpdb->prefix.'kbf_saved_funds';
            $join = "JOIN {$sf} sf ON sf.fund_id=f.id AND sf.user_id=%d";
            array_unshift($params, $current_user_id);
        }
    }
    if(!isset($funds)) {
        $max_funds = 200;
        $sql = "SELECT f.*,u.display_name as organizer_name FROM {$ft} f {$join} LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID LEFT JOIN {$wpdb->usermeta} umsn ON umsn.user_id=u.ID AND umsn.meta_key='kbf_social_name' {$where} ORDER BY {$order} LIMIT %d";
        $params[] = $max_funds;
        $funds = $wpdb->get_results($wpdb->prepare($sql,...$params)); // phpcs:ignore
    }
    $cats  = kbf_get_categories();
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $nonce_save    = wp_create_nonce('kbf_save_fund');
    $fund_details_url = kbf_get_page_url('fund_details');
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $base_url = strtok($_SERVER['REQUEST_URI'],'?').'?kbf_tab=find_funds';
    $saved_ids = [];
    if($current_user_id) {
        $sf = $wpdb->prefix.'kbf_saved_funds';
        $saved_ids = $wpdb->get_col($wpdb->prepare("SELECT fund_id FROM {$sf} WHERE user_id=%d", $current_user_id));
        $saved_ids = array_map('intval', $saved_ids);
    }
    $active_filters = 0;
    if ($cat) $active_filters++;
    if ($sort && $sort !== 'newest') $active_filters++;
    if ($saved_only) $active_filters++;

    ob_start();
    ?>

    <!-- ================== CSS ================== -->
    <style>
      /* ===== PAYMENT RESULT BANNER ===== */
      .kbf-payment-banner{
        position:relative;
        background:#fff;
        border:none;
        border-radius:20px;
        box-shadow:0 1px 3px rgba(0,0,0,.06), 0 8px 24px rgba(0,0,0,.08);
        margin-bottom:18px;
        overflow:hidden;
        animation:kbfPaymentBannerIn .5s cubic-bezier(.16,1,.3,1) both;
        transition:opacity .35s ease, transform .35s cubic-bezier(.16,1,.3,1);
      }
      @keyframes kbfPaymentBannerIn{
        from{opacity:0;transform:translateY(-16px) scale(.97);}
        to{opacity:1;transform:translateY(0) scale(1);}
      }
      .kbf-payment-banner-accent{
        height:0;
        width:100%;
        display:none;
      }
      .kbf-payment-banner-success .kbf-payment-banner-accent{
        background:linear-gradient(90deg,#22c55e 0%,#16a34a 60%,#15803d 100%);
      }
      .kbf-payment-banner-error .kbf-payment-banner-accent{
        background:linear-gradient(90deg,#ef4444 0%,#dc2626 60%,#b91c1c 100%);
      }
      .kbf-payment-banner-warning .kbf-payment-banner-accent{
        background:linear-gradient(90deg,#f59e0b 0%,#d97706 60%,#b45309 100%);
      }
      .kbf-payment-banner-inner{
        display:flex;
        align-items:center;
        gap:16px;
        padding:20px 24px;
      }
      .kbf-payment-banner-content-wrap{
        flex:1;
        min-width:0;
        display:flex;
        align-items:center;
        gap:16px;
      }
      .kbf-payment-banner-icon{
        width:48px;
        height:48px;
        border-radius:14px;
        display:flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
        font-size:26px;
      }
      .kbf-payment-banner-success .kbf-payment-banner-icon{
        background:linear-gradient(145deg,#dcfce7 0%,#d1fae5 100%);
        color:#15803d;
        box-shadow:0 2px 8px rgba(21,128,61,.10);
      }
      .kbf-payment-banner-error .kbf-payment-banner-icon{
        background:linear-gradient(145deg,#fee2e2 0%,#fecaca 100%);
        color:#b91c1c;
        box-shadow:0 2px 8px rgba(185,28,28,.10);
      }
      .kbf-payment-banner-warning .kbf-payment-banner-icon{
        background:linear-gradient(145deg,#fef3c7 0%,#fde68a 100%);
        color:#b45309;
        box-shadow:0 2px 8px rgba(180,83,9,.10);
      }
      .kbf-payment-banner-content{
        flex:1;
        min-width:0;
      }
      .kbf-payment-banner-title{
        font-size:17px;
        font-weight:700;
        color:var(--kbf-navy);
        margin:0 0 4px;
        line-height:1.3;
        letter-spacing:-.2px;
      }
      .kbf-payment-banner-message{
        font-size:14px;
        color:var(--kbf-slate);
        margin:0 0 10px;
        line-height:1.6;
      }
      .kbf-payment-banner-message strong{
        color:var(--kbf-navy);
        font-weight:600;
      }
      .kbf-payment-banner-countdown{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:6px 12px;
        border-radius:999px;
        background:#f1f5f9;
        font-size:12px;
        color:var(--kbf-slate);
        font-weight:600;
        letter-spacing:.02em;
      }
      .kbf-payment-banner-countdown strong{
        color:var(--kbf-blue);
        font-weight:700;
        font-variant-numeric:tabular-nums;
      }
      .kbf-payment-banner-actions{
        display:flex;
        flex-direction:row;
        align-items:center;
        gap:8px;
        flex-shrink:0;
      }
      .kbf-payment-banner-actions .kbf-btn{
        height:36px;
        padding:0 16px;
        font-size:13px;
        font-weight:600;
        justify-content:center;
        border-radius:8px;
        white-space:nowrap;
      }
      .kbf-payment-banner-dismiss{
        width:28px;
        height:28px;
        border-radius:6px;
        border:none;
        background:transparent;
        color:#94a3b8;
        font-size:16px;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:center;
        transition:background .15s, color .15s;
        flex-shrink:0;
      }
      .kbf-payment-banner-dismiss:hover{
        background:#f1f5f9;
        color:#475569;
      }
      .kbf-payment-banner-progress{
        height:3px;
        background:#f1f5f9;
        overflow:hidden;
      }
      .kbf-payment-banner-progress-bar{
        height:100%;
        width:0;
        border-radius:0 2px 2px 0;
        transition:width .1s linear;
      }
      .kbf-payment-banner-success .kbf-payment-banner-progress-bar{
        background:linear-gradient(90deg,#22c55e 0%,#16a34a 100%);
      }
      .kbf-payment-banner-error .kbf-payment-banner-progress-bar{
        background:linear-gradient(90deg,#ef4444 0%,#dc2626 100%);
      }
      .kbf-payment-banner-warning .kbf-payment-banner-progress-bar{
        background:linear-gradient(90deg,#f59e0b 0%,#d97706 100%);
      }
      @media (max-width: 900px){
        .kbf-payment-banner-inner{
          flex-direction:column;
          align-items:stretch;
          gap:14px;
          padding:18px 20px;
        }
        .kbf-payment-banner-content-wrap{
          flex-direction:column;
          align-items:flex-start;
          gap:12px;
        }
        .kbf-payment-banner-content{
          width:100%;
        }
        .kbf-payment-banner-actions{
          width:100%;
          flex-direction:row;
          justify-content:space-between;
        }
        .kbf-payment-banner-actions .kbf-btn{
          flex:1;
        }
      }
      @media (max-width: 600px){
        .kbf-payment-banner{
          border-radius:16px;
        }
        .kbf-payment-banner-inner{
          padding:16px;
        }
        .kbf-payment-banner-icon{
          width:40px;
          height:40px;
          font-size:20px;
        }
        .kbf-payment-banner-title{
          font-size:15px;
        }
        .kbf-payment-banner-message{
          font-size:13px;
          margin:0 0 6px;
        }
        .kbf-payment-banner-actions{
          flex-direction:column;
        }
        .kbf-payment-banner-actions .kbf-btn{
          width:100%;
        }
      }
      @media (prefers-reduced-motion: reduce){
        .kbf-payment-banner{
          animation:none;
        }
        .kbf-payment-banner-progress-bar{
          transition:none;
        }
      }
      .kbff-toolbar-card{
        --kbff-control-h:36px;
        background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%);
        border:1px solid #e7edf7;
        border-radius:18px;
        margin-bottom:18px;
        box-shadow:0 10px 24px rgba(15,23,42,.04);
        padding:12px;
      }
      .kbff-toolbar-row{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
        align-items:center;
      }
      .kbff-toolbar-row .kbff-filter-control,
      .kbff-toolbar-row #kbff-filter-btn.kbff-filter-btn,
      .kbff-toolbar-row #kbff-reset-filters-btn.kbff-reset-filters-btn,
      .kbff-toolbar-row #kbff-search-input.kbff-search-input,
      .kbff-toolbar-row #kbff-near-me-btn.kbff-near-btn,
      .kbff-toolbar-row .kbff-search-submit{
        height:var(--kbff-control-h);
        min-height:var(--kbff-control-h);
        box-sizing:border-box;
      }
      #kbff-search-form.kbff-toolbar-form{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        flex:1;
        align-items:center;
        min-width:0;
      }
      .kbff-toolbar-filters{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        align-items:center;
      }
      .kbff-filter-control{
        height:var(--kbff-control-h);
        display:flex;
        align-items:center;
        gap:8px;
        margin:0;
        background:#fff;
        border:1.5px solid var(--kbf-border);
        border-radius:10px;
        padding:3px 8px 3px 3px;
        cursor:pointer;
      }
      .kbff-filter-icon{
        width:calc(var(--kbff-control-h) - 8px);
        height:calc(var(--kbff-control-h) - 8px);
        border-radius:8px;
        background:#eef4ff;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
      }
      #kbff-filter-btn.kbff-filter-btn{
        height:var(--kbff-control-h);
        min-height:var(--kbff-control-h);
        border-radius:10px;
        padding:0 12px;
      }
      #kbff-reset-filters-btn.kbff-reset-filters-btn{
        border-radius:10px;
        width:var(--kbff-control-h);
        padding:0;
        white-space:nowrap;
        display:inline-flex;
        align-items:center;
        justify-content:center;
      }
      #kbff-reset-filters-btn.kbff-reset-filters-btn.is-hidden{
        display:none;
      }
      .kbff-filter-control select{
        border:none !important;
        background:transparent !important;
        min-width:120px;
        padding:0 20px 0 0 !important;
        height:calc(var(--kbff-control-h) - 8px) !important;
        line-height:calc(var(--kbff-control-h) - 8px) !important;
        box-shadow:none !important;
        cursor:pointer;
      }
      .kbff-filter-control select option{
        cursor:pointer;
      }
      .kbff-search-group{
        display:flex;
        align-items:center;
        gap:8px;
        flex:1 1 260px;
        min-width:240px;
      }
      #kbff-search-input.kbff-search-input{
        flex:1 1 auto;
        min-width:0;
        height:var(--kbff-control-h);
        padding:3px 10px;
        border-radius:10px;
        border:1.5px solid var(--kbf-border);
        font-size:13px;
        background:#fff;
        color:var(--kbf-text);
      }
      #kbff-near-me-btn.kbff-near-btn{
        white-space:nowrap;
        width:var(--kbff-control-h);
        height:var(--kbff-control-h);
        padding:0;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
      }
      #kbff-near-me-btn.kbff-near-btn.is-loading{
        position:relative;
        pointer-events:none;
        opacity:.75;
      }
      #kbff-near-me-btn.kbff-near-btn.is-loading::after{
        content:'';
        position:absolute;
        inset:0;
        margin:auto;
        display:block;
        width:16px;
        height:16px;
        box-sizing:border-box;
        border-radius:50%;
        border:2px solid rgba(59,130,246,0.25);
        border-top-color:#3b82f6;
        animation:kbff-near-spin .7s linear infinite;
      }
      @keyframes kbff-near-spin{
        from{transform:rotate(0deg);}
        to{transform:rotate(360deg);}
      }
      .kbff-search-submit{
        height:var(--kbff-control-h);
        min-height:var(--kbff-control-h);
        border-radius:10px;
        padding:0 14px;
      }
      .kbff-toolbar-tip{
        font-size:12.5px;
        color:var(--kbf-slate);
        display:block;
        width:100%;
        max-width:100%;
        box-sizing:border-box;
        align-self:stretch;
        margin-top:8px;
      }
      @media (max-width: 900px){
        .kbff-toolbar-card{padding:10px;}
        .kbff-toolbar-filters{width:100%;}
        .kbff-search-group{flex:1 1 100%;min-width:0;}
        #kbff-search-form{
          flex-wrap:wrap !important;
          row-gap:10px;
        }
        #kbff-search-input{
          order:1;
          flex:1 1 100% !important;
          min-width:0 !important;
        }
        #kbff-search-form .kbf-form-group{
          order:2;
          flex:0 0 auto;
        }
        #kbff-near-me-btn,
        #kbff-search-form .kbf-btn.kbf-btn-primary{
          order:3;
          flex:0 0 auto;
        }
      }
      @media (max-width: 720px){
        .kbff-filter-control{flex:1 1 calc(50% - 6px);min-width:0;}
        .kbff-filter-control select{min-width:0;width:100%;}
        #kbff-reset-filters-btn{display:none !important;}
        .kbff-filter-btn{
          order:3;
          margin-right:auto;
        }
        #kbff-near-me-btn,
        #kbff-search-form .kbf-btn.kbf-btn-primary{
          order:4;
        }
      }
      #kbf-explore-tip{
        display:block;
        width:100%;
        max-width:100%;
        box-sizing:border-box;
        align-self:stretch;
      }
      .kbf-explore-grid{
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
        gap:18px;
        overflow:visible;
        width:100%;
      }
      .kbf-user-ui .kbf-dashboard-shell,
      .kbf-user-ui .kbf-tab-content,
      .kbf-user-ui .kbf-card-list[data-kbf-card-pager="explore"],
      .kbf-user-ui .kbf-card-list[data-kbf-card-pager="explore"] > .kbf-explore-grid{
        max-height:none !important;
        height:auto !important;
        overflow:visible !important;
        overflow-y:visible !important;
      }
      .kbf-explore-grid > *{min-width:0;}
      @media (max-width: 900px){
        .kbf-explore-grid{
          grid-template-columns:repeat(2, minmax(0, 1fr));
        }
        .kbf-explore-card{
          border-radius:16px;
        }
        .kbf-explore-media{
          padding:10px 10px 0;
        }
        .kbf-explore-media img,
        .kbf-explore-fallback{
          height:150px;
        }
        .kbf-explore-body{
          padding:12px 12px 14px;
        }
        .kbf-explore-chip{
          top:18px;
          left:18px;
        }
      }
      @media (max-width: 600px){
        .kbf-explore-grid{
          grid-template-columns:1fr;
        }
        .kbf-explore-media img,
        .kbf-explore-fallback{
          height:auto;
          aspect-ratio:16 / 10;
        }
        .kbf-explore-title{
          font-size:14px;
        }
        .kbf-explore-actions .kbf-btn-sm{
          width:36px;
          height:36px;
          min-width:36px;
        }
      }
      @media (max-width: 480px){
        .kbf-explore-title-row{
          align-items:flex-start;
          flex-direction:column;
          gap:6px;
        }
        .kbf-explore-meta{
          flex-wrap:wrap;
          row-gap:6px;
        }
      }
      .kbf-explore-card{
        background:#fff;
        border:1px solid var(--kbf-border);
        border-radius:18px;
        overflow:visible;
        box-shadow:var(--kbf-shadow);
        display:flex;
        flex-direction:column;
        transition:box-shadow .2s ease, transform .15s ease;
        position:relative;
        z-index:1;
        width:100%;
        max-width:100%;
        min-width:0;
        box-sizing:border-box;
      }
      .kbf-explore-card:hover{
        box-shadow:var(--kbf-shadow-lg);
        transform:translateY(-2px);
      }
      .kbf-explore-media{
        position:relative;
        display:block;
        text-decoration:none;
        padding:12px 12px 0;
      }
      .kbf-explore-media-frame{
        border-radius:8px;
        overflow:hidden;
      }
      .kbf-explore-media img{
        width:100%;
        height:170px;
        object-fit:cover;
        display:block;
        border-radius:12px;
        background:#f1f5f9;
      }
      .kbf-explore-fallback{
        width:100%;
        height:170px;
        border-radius:12px;
        background:linear-gradient(135deg,#dce9ff 0%,#cfe2ff 55%,#eaf2ff 100%);
        display:flex;
        align-items:center;
        justify-content:center;
      }
      .kbf-explore-chip{
        position:absolute;
        top:22px;
        left:22px;
        background:linear-gradient(135deg,#5ba8f5 0%,#3d8ef0 50%,#2070e0 100%);
        color:#ffffff;
        padding:4px 10px;
        border-radius:999px;
        font-size:10px;
        font-weight:700;
        text-transform:none;
        letter-spacing:0;
        border:1px solid rgba(32,112,224,.45);
        backdrop-filter:blur(2px);
        text-shadow:0 1px 1px rgba(30,64,175,.35);
      }
      .kbf-explore-body{
        padding:14px 16px 16px;
        display:flex;
        flex-direction:column;
        gap:10px;
        flex:1;
        position:relative;
        z-index:2;
      }
      .kbf-explore-title{
        font-size:14.5px;
        font-weight:700;
        color:var(--kbf-navy);
        margin:0;
        line-height:1.45;
        text-align:left;
      }
      .kbf-explore-title-row{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:10px;
        text-align:left;
      }
      .kbf-explore-title-text{
        flex:1;
        min-width:0;
        display:-webkit-box;
        -webkit-box-orient:vertical;
        -webkit-line-clamp:2;
        line-clamp:2;
        line-height:1.45;
        min-height:calc(1.45em * 2);
        max-height:calc(1.45em * 2);
        overflow:hidden;
        white-space:normal;
        text-overflow:ellipsis;
        text-align:left;
      }
      .kbf-explore-meta{
        display:flex;
        align-items:center;
        justify-content:space-between;
        font-size:11.5px;
        color:var(--kbf-slate);
        gap:8px;
        min-height:18px;
      }
      .kbf-explore-meta-item{
        display:inline-flex;
        align-items:center;
        gap:6px;
        min-width:0;
      }
      .kbf-explore-meta-item img{
        width:12px;
        height:12px;
      }
      .kbf-explore-loc{
        flex:1;
        min-width:0;
        overflow:hidden;
        white-space:nowrap;
        text-overflow:ellipsis;
      }
      .kbf-explore-progress{
        height:6px;
        background:#eef2f7;
        border-radius:999px;
        overflow:hidden;
        margin-top:auto;
      }
      .kbf-explore-progress span{
        display:block;
        height:100%;
        background:linear-gradient(90deg,#63a4ff 0%,#3b82f6 100%);
        border-radius:999px;
      }
      .kbf-explore-footer{
        display:flex;
        align-items:center;
        justify-content:space-between;
        font-size:12px;
        color:var(--kbf-slate);
        margin-top:2px;
      }
      .kbf-explore-amount{
        font-weight:800;
        color:#1f2a44;
        font-size:13.5px;
      }
      .kbf-explore-actions{
        display:grid;
        gap:8px;
        align-items:center;
      }
      .kbf-explore-actions.is-own{ grid-template-columns:minmax(0,1fr) auto; }
      .kbf-explore-actions.is-public{ grid-template-columns:minmax(0,1fr) auto; }
      .kbf-explore-actions > .kbf-btn-primary{
        min-width:0;
        width:100%;
        height:38px;
        min-height:38px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        box-sizing:border-box;
      }
      .kbf-explore-card .kbf-btn-primary{
        box-shadow:
          0 1px 2px rgba(32, 112, 224, 0.18),
          0 3px 10px rgba(42, 120, 220, 0.18);
      }
      .kbf-explore-card .kbf-btn-primary::before{
        opacity:0;
      }
      .kbf-explore-card .kbf-btn-primary:hover{
        box-shadow:
          0 2px 6px rgba(32, 112, 224, 0.16),
          0 6px 16px rgba(42, 120, 220, 0.22);
      }
      .kbf-explore-actions .kbf-btn-sm{
        width:38px;
        height:38px;
        min-width:38px;
        padding:0;
        display:inline-flex;
        align-items:center;
        justify-content:center;
      }
      .kbf-explore-actions .kbf-btn-sm i{margin:0;}
      .kbf-save-btn{
        transition:none;
      }
      .kbf-save-btn.is-loading{
        position:relative;
        pointer-events:none;
        opacity:.75;
      }
      .kbf-save-btn.is-loading i{
        display:none;
      }
      .kbf-save-loader{
        display:none;
        width:14px;
        height:14px;
        box-sizing:border-box;
        border-radius:50%;
        border:2px solid rgba(59,130,246,0.25);
        border-top-color:#3b82f6;
      }
      .kbf-save-btn.is-loading .kbf-save-loader{
        display:inline-block;
        animation:kbfspin .7s linear infinite;
      }
      @keyframes kbfspin{
        from{transform:rotate(0deg);}
        to{transform:rotate(360deg);}
      }
      .kbf-save-btn i{
        transition:none;
      }
      .kbf-save-btn.is-saved{
        background:#e7f1ff;
        border-color:#bfd7ff;
        color:#1d4ed8;
      }
      .kbf-save-btn.is-saved i{
        color:#3b82f6;
      }
      .kbf-explore-pager{
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        padding:10px 2px 0;
        border:none;
        background:transparent;
        font-size:12px;
        color:var(--kbf-slate);
        flex-wrap:wrap;
      }
      .kbf-explore-pager .kbf-pager-pages{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:wrap;
      }
      .kbf-explore-pager .kbf-table-pager-btn{
        border:1px solid #dbe3ef;
        background:#fff;
        color:var(--kbf-navy);
        padding:6px 10px;
        border-radius:8px;
        font-size:12px;
        font-weight:600;
        cursor:pointer;
        transition:all .15s ease;
      }
      .kbf-explore-pager .kbf-table-pager-btn:hover{border-color:#bcd2f3;background:#f8fafc;}
      .kbf-explore-pager .kbf-table-pager-btn:disabled{opacity:.45;cursor:not-allowed;}
      .kbf-explore-pager .kbf-page-btn{
        min-width:30px;
        height:30px;
        padding:0 8px;
        border-radius:10px;
        border:1px solid transparent;
        background:#fff;
        color:var(--kbf-slate);
        font-weight:600;
        cursor:pointer;
        transition:all .15s ease;
      }
      .kbf-explore-pager .kbf-page-btn:hover{border-color:#cbd5f1;color:#1f2a44;}
      .kbf-explore-pager .kbf-page-btn.is-active{
        background:#eef4ff;
        border-color:#dbe7ff;
        color:#1f2a44;
      }
      .kbf-explore-pager .kbf-page-gap{padding:0 2px;color:#94a3b8;font-weight:600;}
      /* ===== MOBILE FILTER ===== */
      .kbff-filter-btn{
        display:none;
        height:38px;
        border:1.5px solid var(--kbf-border);
        gap:8px;
        align-items:center;
      }
      .kbff-filter-btn.has-filters{
        border-color:var(--kbf-accent);
        background:var(--kbf-accent-lt);
      }
      .kbff-filter-badge{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:18px;
        height:18px;
        padding:0 6px;
        border-radius:999px;
        background:var(--kbf-accent);
        color:#fff;
        font-size:11px;
        font-weight:700;
        margin-left:2px;
      }
      .kbff-filter-badge.is-hidden{display:none;}
      #kbff-sheet-overlay{
        position:fixed;
        inset:0;
        background:rgba(10,16,32,0.45);
        backdrop-filter:blur(2px);
        z-index:9998;
        display:none;
      }
      #kbff-sheet-overlay.open{display:block;}
      html.kbff-scroll-lock, body.kbff-scroll-lock{
        overflow:hidden !important;
        overscroll-behavior:none;
        touch-action:none;
      }
      #kbff-sheet{
        position:fixed;
        left:0;
        right:0;
        bottom:0;
        background:#fff;
        z-index:9999;
        transform:translateY(100%);
        transition:transform 0.3s cubic-bezier(.4,0,.2,1);
        max-height:none;
        overflow-x:hidden;
        overflow-y:visible;
        padding:0 0 32px;
        box-shadow:var(--kbf-shadow-lg);
      }
      #kbff-sheet.open{transform:translateY(0);}
      .kbff-sheet-handle{
        width:44px;
        height:5px;
        border-radius:999px;
        background:#e2e8f0;
        margin:10px auto 6px;
      }
      .kbff-sheet-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:8px 20px 6px;
      }
      .kbff-sheet-body{
        padding:0 20px 12px;
        display:grid;
        gap:12px;
        overflow-x:hidden;
      }
        .kbff-sheet-apply{
          height:44px;
          border-radius:12px;
          background:linear-gradient(135deg,#5ba8f5,#3d8ef0,#2070e0);
          box-shadow:0 4px 14px rgba(42,120,220,0.28);
        }
        .kbff-sheet-clear{ }
        .kbff-sheet-actions{
          display:flex;
          gap:10px;
          padding:8px 20px 0;
        }
        .kbff-sheet-actions .kbff-sheet-apply,
        .kbff-sheet-actions .kbff-sheet-clear{
          flex:1;
          width:auto;
          margin:0;
        }
        .kbf-user-ui #kbff-sheet .kbf-btn-primary::before{
          display:none;
          content:none;
        }
        .kbf-user-ui .kbff-sheet-body [data-kbff-group]{
          background:#fff !important;
          color:var(--kbf-text) !important;
          border-color:var(--kbf-border) !important;
          box-shadow:none !important;
          border-radius:12px !important;
          font-weight:600;
          text-align:left;
          justify-content:flex-start;
          white-space:normal;
          word-break:break-word;
          width:100%;
          min-width:0;
          transition:none !important;
        }
        .kbf-user-ui .kbff-sheet-body [data-kbff-group].is-active{
          border-color:#60a5fa !important;
          background:#eff6ff !important;
          color:#0f172a !important;
        }
      @media (max-width: 720px){
        #kbff-cat-select-wrap,
        #kbff-sort-select-wrap,
        #kbff-saved-select-wrap{ display:none !important; }
        .kbff-filter-btn{ display:inline-flex; }
      }
      @media (max-width: 560px){
        .kbff-sheet-grid-cat,
        .kbff-sheet-grid-sort{
          grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        }
      }
    </style>

    <!-- ================== HTML ================== -->
    <?php if ($payment_banner_data): ?>
    <div id="kbf-payment-banner" class="kbf-payment-banner kbf-payment-banner-<?php echo esc_attr($payment_banner_data['type']); ?>">
      <div class="kbf-payment-banner-accent"></div>
      <div class="kbf-payment-banner-inner">
        <div class="kbf-payment-banner-content-wrap">
          <div class="kbf-payment-banner-icon">
            <i class="<?php echo esc_attr($payment_banner_data['icon']); ?>" aria-hidden="true"></i>
          </div>
          <div class="kbf-payment-banner-content">
            <h4 class="kbf-payment-banner-title"><?php echo esc_html($payment_banner_data['title']); ?></h4>
            <p class="kbf-payment-banner-message"><?php echo $payment_banner_data['message']; ?></p>
            <div class="kbf-payment-banner-countdown">
              <i class="ph ph-clock" aria-hidden="true"></i>
              <span>Redirecting in <strong id="kbf-payment-countdown"><?php echo (int)$payment_banner_data['countdown']; ?></strong>s</span>
            </div>
          </div>
        </div>
        <div class="kbf-payment-banner-actions">
          <a href="<?php echo esc_url($payment_banner_data['redirect']); ?>" class="kbf-btn kbf-btn-primary kbf-payment-btn-go" id="kbf-payment-btn-go">
            <?php echo esc_html($payment_banner_data['btn_text']); ?>
          </a>
          <button type="button" class="kbf-btn kbf-btn-secondary kbf-payment-btn-dismiss" onclick="kbfDismissPaymentBanner()">
            Stay here
          </button>
          <button type="button" class="kbf-payment-banner-dismiss" onclick="kbfDismissPaymentBanner()" aria-label="Dismiss">
            <i class="ph ph-x" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <div class="kbf-payment-banner-progress">
        <div class="kbf-payment-banner-progress-bar" id="kbf-payment-progress"></div>
      </div>
    </div>
    <script>
      (function(){
        var totalSeconds = <?php echo (int)$payment_banner_data['countdown']; ?>;
        var redirectUrl = <?php echo wp_json_encode($payment_banner_data['redirect']); ?>;
        var countdownEl = document.getElementById('kbf-payment-countdown');
        var progressEl = document.getElementById('kbf-payment-progress');
        var startTime = Date.now();
        var interval = null;

        /**
         * @function  updateProgress
         * @purpose   Updates payment banner countdown text and progress bar width.
         * @used-by   [payment banner interval timer]
         * @calls     [Math.max, Math.round]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function updateProgress(){
          var elapsed = (Date.now() - startTime) / 1000;
          var remaining = Math.max(0, totalSeconds - elapsed);
          var pct = Math.min(100, (elapsed / totalSeconds) * 100);

          if (countdownEl) {
            countdownEl.textContent = Math.ceil(remaining);
          }
          if (progressEl) {
            progressEl.style.width = pct + '%';
          }

          if (remaining <= 0) {
            clearInterval(interval);
            window.location.href = redirectUrl;
          }
        }

        interval = setInterval(updateProgress, 100);

        /**
         * @function  kbfDismissPaymentBanner
         * @purpose   Dismisses the payment banner and clears its countdown timer.
         * @used-by   [payment banner dismiss button onclick, countdown completion path]
         * @calls     [clearInterval, setTimeout, classList.add]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfDismissPaymentBanner = function(){
          clearInterval(interval);
          var banner = document.getElementById('kbf-payment-banner');
          if (banner) {
            banner.style.opacity = '0';
            banner.style.transform = 'translateY(-12px) scale(0.98)';
            setTimeout(function(){ banner.remove(); }, 300);
          }
          // Clean URL
          try {
            var url = new URL(window.location.href);
            url.searchParams.delete('kbf_payment');
            url.searchParams.delete('sid');
            url.searchParams.delete('fund_id');
            url.searchParams.delete('fund');
            url.searchParams.delete('ref');
            window.history.replaceState({}, '', url.toString());
          } catch(e){}
        };

        var goBtn = document.getElementById('kbf-payment-btn-go');
        if (goBtn) {
          goBtn.addEventListener('click', function(e){
            clearInterval(interval);
            window.kbfDismissPaymentBanner();
          });
        }
      })();
    </script>
    <?php endif; ?>

    <!-- MODAL: Sponsor -->
    <div id="kbff-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3 class="kbf-section-title">Sponsor This Fund</h3>
          <button class="kbf-modal-close" onclick="kbffCloseSponsorModal()">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <div id="kbff-fund-preview" style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:18px;"></div>
          <form id="kbff-sponsor-form" onsubmit="return false;">
            <input type="hidden" name="fund_id" id="kbff-fund-id">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Account</label><input type="text" name="sponsor_name" id="kbff-name" placeholder="Your name, company, or account"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;">
                <label class="kbf-checkbox-row"><input type="checkbox" id="kbff-anon" onchange="document.getElementById('kbff-name').disabled=this.checked"> Sponsor Anonymously</label>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Amount (PHP) *</label>
              <input type="number" name="amount" placeholder="Min. &#8369;50" min="50" step="1" required>
              <div id="kbff-sponsor-limit" class="kbf-meta" style="margin-top:4px;"></div>
            </div>
            <div class="kbf-form-group">
              <label>Encouraging Message (optional)</label>
              <textarea name="message" id="kbff-message" rows="2" maxlength="300" placeholder="Leave a message for the organizer..."></textarea>
              <div class="kbf-char-count" id="kbff-message-count">0/300</div>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Email (for receipt) *</label><input type="email" name="email" placeholder="your@email.com" required></div>
              <div class="kbf-form-group"><label>Phone *</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX" required></div>
            </div>
            <input type="hidden" name="payment_method" value="online_payment">
            <?php if($demo_mode): ?>
            <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;margin-top:6px;">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
              <div><span class="kbf-strong">Demo Mode:</span> Redirects to Maya sandbox checkout. No real payment is processed.</div>
            </div>
            <?php endif; ?>
            <div id="kbff-sponsor-msg" style="margin-top:10px;"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbffCloseSponsorModal()">Cancel</button>
          <button type="button" class="kbf-btn kbf-btn-primary" id="kbff-sponsor-submit" onclick="kbffSubmitSponsor('<?php echo $nonce_sponsor; ?>')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            Confirm Sponsorship
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: Report -->
    <div id="kbff-modal-report" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3 class="kbf-section-title">Report This Fund</h3><button class="kbf-modal-close" onclick="kbffCloseReportModal()">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbff-report-form">
            <input type="hidden" name="fund_id" id="kbff-report-fund-id">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email" placeholder="your@email.com"></div>
            <div class="kbf-form-group"><label>Upload Photo (optional)</label><input type="file" name="report_image" accept="image/*"></div>
            <div class="kbf-form-group"><label>Reason *</label>
              <select name="reason" required><option value="">Select Reason</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Information</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select>
            </div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" placeholder="Describe the issue..." required></textarea></div>
            <div id="kbff-report-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbffCloseReportModal()">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbffSubmitReport('<?php echo $nonce_report; ?>')">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- Header -->
    <div class="kbff-toolbar-card">
      <div class="kbff-toolbar-row">
        <form method="GET" class="kbff-toolbar-form" id="kbff-search-form">
          <input type="hidden" name="kbf_tab" value="find_funds">
          <?php if($cat): ?><input type="hidden" name="ff_cat" value="<?php echo esc_attr($cat); ?>"><?php endif; ?>
          <?php if($sort && $sort!=='newest'): ?><input type="hidden" name="ff_sort" value="<?php echo esc_attr($sort); ?>"><?php endif; ?>
          <?php if($saved_only): ?><input type="hidden" name="ff_saved" value="<?php echo esc_attr($saved_only); ?>"><?php endif; ?>

          <div class="kbff-toolbar-filters">
            <button type="button" id="kbff-filter-btn" class="kbf-btn kbf-btn-secondary kbff-filter-btn <?php echo $active_filters ? 'has-filters' : ''; ?>" onclick="kbffOpenSheet()">
              <i class="ph ph-sliders kbf-icon" style="font-size:14px" aria-hidden="true"></i>
              Filters
              <span class="kbff-filter-badge <?php echo $active_filters ? '' : 'is-hidden'; ?>" id="kbff-filter-badge"><?php echo (int)$active_filters; ?></span>
            </button>

            <div id="kbff-cat-select-wrap" class="kbff-filter-control">
              <span class="kbff-filter-icon">
                <i class="ph ph-tag kbf-icon" style="font-size:14px" aria-hidden="true"></i>
              </span>
              <select id="kbff-cat-select">
                <option value="">All Categories</option>
                <?php foreach($cats as $c): ?>
                  <option value="<?php echo esc_attr($c); ?>" <?php echo $cat===$c?'selected':''; ?>><?php echo esc_html($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="kbff-sort-select-wrap" class="kbff-filter-control">
              <span class="kbff-filter-icon">
                <i class="ph ph-funnel kbf-icon" style="font-size:14px" aria-hidden="true"></i>
              </span>
              <select id="kbff-sort-select">
                <option value="newest" <?php echo ($sort==='newest'||!$sort)?'selected':''; ?>>Newest</option>
                <option value="most_funded" <?php echo $sort==='most_funded'?'selected':''; ?>>Most Funded</option>
                <option value="ending_soon" <?php echo $sort==='ending_soon'?'selected':''; ?>>Ending Soon</option>
              </select>
            </div>

            <div id="kbff-saved-select-wrap" class="kbff-filter-control">
              <span class="kbff-filter-icon">
                <i class="ph ph-bookmark-simple kbf-icon" style="font-size:14px" aria-hidden="true"></i>
              </span>
              <select id="kbff-saved-select" name="ff_saved">
                <option value="">All Funds</option>
                <option value="1" <?php echo $saved_only ? 'selected' : ''; ?>>Saved</option>
              </select>
            </div>
            <button type="button" id="kbff-reset-filters-btn" class="kbf-btn kbf-btn-secondary kbff-reset-filters-btn <?php echo $active_filters ? '' : 'is-hidden'; ?>" aria-label="Reset filters">
              <i class="ph ph-arrow-counter-clockwise kbf-icon" style="font-size:14px" aria-hidden="true"></i>
            </button>
          </div>

          <div class="kbff-search-group">
            <input type="text" name="ff_q" id="kbff-search-input" class="kbff-search-input" value="<?php echo esc_attr($q); ?>" placeholder="Search title, account/profile name, or address...">
            <button type="button" id="kbff-near-me-btn" onclick="kbfNearMe('kbff-search-input','kbff-search-form', this)" class="kbf-btn kbf-btn-secondary kbff-near-btn" aria-label="Near Me">
              <i class="ph ph-map-pin kbf-icon" style="font-size:14px; filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
            </button>
            <button type="submit" class="kbf-btn kbf-btn-primary kbff-search-submit">
              <i class="ph ph-magnifying-glass kbf-icon" style="font-size:14px;color:#ffffff;filter:none;margin-right:6px" aria-hidden="true"></i>
              Search
            </button>
          </div>
        </form>
      </div>
      <div class="kbf-cta-note kbff-toolbar-tip" id="kbf-explore-tip">
        <span class="kbf-strong">Tip:</span> Funds with regular updates raise up to 3x more.
      </div>
    </div>
    <!-- ===== MOBILE FILTER SHEET ===== -->
    <div id="kbff-sheet-overlay" onclick="kbffCloseSheet()"></div>
    <div id="kbff-sheet" data-kbff-cat="<?php echo esc_attr($cat); ?>" data-kbff-sort="<?php echo esc_attr($sort ? $sort : 'newest'); ?>" data-kbff-saved="<?php echo esc_attr($saved_only); ?>">
      <div class="kbff-sheet-handle"></div>
      <div class="kbff-sheet-header">
        <h3 class="kbf-section-title" style="margin:0;">Filter Campaigns</h3>
        <button type="button" class="kbf-btn kbf-btn-secondary" onclick="kbffCloseSheet()">&times;</button>
      </div>
      <div class="kbff-sheet-body">
          <div class="kbf-form-group">
            <label>Category</label>
            <div class="kbff-sheet-grid-cat" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
              <button type="button" class="kbf-btn kbf-choice-card kbf-btn-secondary <?php echo $cat ? '' : 'is-active'; ?>" data-kbff-group="cat" data-kbff-value="" style="justify-content:flex-start;text-align:left;">
                <i class="ph ph-app-window kbf-icon" aria-hidden="true"></i>
                All
              </button>
              <?php foreach($cats as $c):
                $active = $cat===$c;
                $cat_icon_map = [
                  'Community' => 'ph ph-users',
                  'Sports' => 'ph ph-tennis-ball',
                  'Family' => 'ph ph-house',
                  'Medical' => 'ph ph-heartbeat',
                  'Education' => 'ph ph-graduation-cap',
                  'Emergency' => 'ph ph-siren',
                  'Business' => 'ph ph-briefcase',
                  'Religion' => 'ph ph-cross',
                  'Arts & Culture' => 'ph ph-palette',
                  'Environment' => 'ph ph-leaf',
                  'Animals' => 'ph ph-paw-print',
                  'Others' => 'ph ph-dots-three'
                ];
                $cat_icon = isset($cat_icon_map[$c]) ? $cat_icon_map[$c] : 'ph ph-tag';
              ?>
                <button type="button" class="kbf-btn kbf-choice-card kbf-btn-secondary <?php echo $active ? 'is-active' : ''; ?>" data-kbff-group="cat" data-kbff-value="<?php echo esc_attr($c); ?>" style="justify-content:flex-start;text-align:left;">
                  <i class="<?php echo esc_attr($cat_icon); ?> kbf-icon" aria-hidden="true"></i>
                  <?php echo esc_html($c); ?>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="kbf-form-group">
            <label>Sort By</label>
            <div class="kbff-sheet-grid-sort" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
              <button type="button" class="kbf-btn kbf-choice-card kbf-btn-secondary <?php echo ($sort==='newest'||!$sort)?'is-active':''; ?>" data-kbff-group="sort" data-kbff-value="newest">
                <i class="ph ph-clock kbf-icon" aria-hidden="true"></i>
                Newest
              </button>
              <button type="button" class="kbf-btn kbf-choice-card kbf-btn-secondary <?php echo $sort==='most_funded'?'is-active':''; ?>" data-kbff-group="sort" data-kbff-value="most_funded">
                <i class="ph ph-chart-line-up kbf-icon" aria-hidden="true"></i>
                Most Funded
              </button>
              <button type="button" class="kbf-btn kbf-choice-card kbf-btn-secondary <?php echo $sort==='ending_soon'?'is-active':''; ?>" data-kbff-group="sort" data-kbff-value="ending_soon">
                <i class="ph ph-timer kbf-icon" aria-hidden="true"></i>
                Ending Soon
              </button>
          </div>
          </div>
          <div class="kbf-form-group">
            <label>Show</label>
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
              <button type="button" class="kbf-btn kbf-choice-card kbf-btn-secondary <?php echo $saved_only ? '' : 'is-active'; ?>" data-kbff-group="saved" data-kbff-value="">
                <i class="ph ph-cards kbf-icon" aria-hidden="true"></i>
                All Funds
              </button>
              <button type="button" class="kbf-btn kbf-choice-card kbf-btn-secondary <?php echo $saved_only ? 'is-active' : ''; ?>" data-kbff-group="saved" data-kbff-value="1">
                <i class="ph ph-bookmark-simple kbf-icon" aria-hidden="true"></i>
                Saved
              </button>
          </div>
        </div>
      </div>
      <div class="kbff-sheet-actions">
        <button type="button" class="kbf-btn kbf-btn-primary kbff-sheet-apply" id="kbff-sheet-apply">Apply Filters</button>
        <button type="button" class="kbf-btn kbf-btn-secondary kbff-sheet-clear" id="kbff-sheet-clear">Clear all filters</button>
      </div>
    </div>

    <!-- Fund grid -->
    <?php if(empty($funds)): ?>
    <div class="kbf-empty" style="padding:60px 20px;">
      <i class="ph ph-magnifying-glass kbf-icon" style="font-size:44px; margin:0 auto 14px;display:block;opacity:.35;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
      <p style="font-size:15px;font-weight:600;color:var(--kbf-navy);margin-bottom:4px;">No funds found</p>
      <p style="color:var(--kbf-slate);font-size:13px;">Try adjusting your search or category filter.</p>
      <?php if($q||$cat): ?><a href="?kbf_tab=find_funds" class="kbf-btn kbf-btn-primary" style="margin-top:14px;">Clear Filters</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="kbf-card-list" data-kbf-card-pager="explore">
    <div class="kbf-explore-grid">
      <?php foreach($funds as $f):
        $pct   = $f->goal_amount > 0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $days  = $f->deadline ? max(0,ceil((strtotime($f->deadline)-time())/86400)) : null;
        $photos = $f->photos ? json_decode($f->photos,true) : [];
        $cover  = !empty($photos[0]) ? $photos[0] : null;
        $fund_token = function_exists('kbf_get_or_create_fund_token') ? kbf_get_or_create_fund_token($f->id) : '';
        $detail_url = esc_url(add_query_arg('fund', $fund_token ?: $f->id, $fund_details_url));
        $is_own = ($f->business_id == $current_user_id);
        $supporters = isset($f->sponsors_count) ? (int)$f->sponsors_count : (isset($f->sponsor_count) ? (int)$f->sponsor_count : 0);
        $supporters_label = $supporters > 0 ? $supporters.' people' : '';
        $supporters_icon = 'ph ph-users';
        $is_saved = in_array((int)$f->id, $saved_ids, true);
        $save_icon = $is_saved ? 'ph-fill ph-bookmark-simple' : 'ph ph-bookmark-simple';
        $save_title = $is_saved ? 'Saved' : 'Save';
      ?>
      <div class="kbf-explore-card">

        <!-- Photo / cover -->
        <a href="<?php echo $detail_url; ?>" class="kbf-explore-media">
          <div class="kbf-explore-media-frame">
            <?php if($cover): ?>
              <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($f->title); ?>">
            <?php else: ?>
              <div class="kbf-explore-fallback">
                <i class="ph-fill ph-heart kbf-icon" style="font-size:40px; opacity:.35;filter:invert(36%) sepia(16%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
              </div>
            <?php endif; ?>
          </div>
          <!-- Overlays -->
            <div class="kbf-explore-chip"><?php echo esc_html(ucwords(strtolower((string)$f->category))); ?></div>
        </a>

        <!-- Card body -->
        <div class="kbf-explore-body">
          <a href="<?php echo $detail_url; ?>" style="text-decoration:none;">
          <div class="kbf-explore-title-row">
            <h4 class="kbf-explore-title kbf-explore-title-text" title="<?php echo esc_attr($f->title); ?>"><?php echo esc_html($f->title); ?></h4>
            <?php if(!empty($f->verified_badge)): ?><span class="kbf-badge kbf-badge-verified">Verified</span><?php endif; ?>
          </div>
        </a>

        <!-- Location + Organizer -->
        <div class="kbf-explore-meta">
          <span class="kbf-explore-meta-item kbf-explore-loc">
            <i class="ph ph-map-pin kbf-icon" aria-hidden="true"></i>
            <?php echo esc_html($f->location); ?>
          </span>
          <?php if($supporters > 0): ?>
          <span class="kbf-explore-meta-item" style="flex-shrink:0;">
            <i class="<?php echo esc_attr($supporters_icon); ?> kbf-icon" aria-hidden="true"></i>
            <?php echo esc_html($supporters_label); ?>
          </span>
          <?php endif; ?>
          <?php if($days!==null): ?>
          <span class="kbf-explore-meta-item" style="flex-shrink:0;">
            <i class="ph ph-clock kbf-icon" aria-hidden="true"></i>
            <?php echo $days; ?>d
          </span>
          <?php endif; ?>
        </div>

          <!-- Progress -->
          <div class="kbf-explore-progress"><span style="width:<?php echo $pct; ?>%"></span></div>
          <div class="kbf-explore-footer">
            <span><span class="kbf-explore-amount">&#8369;<?php echo $format_currency($f->raised_amount, 0); ?></span> &middot; <?php echo round($pct); ?>%</span>
          </div>

          <!-- Action buttons -->
          <?php if($is_own): ?>
          <div class="kbf-explore-actions is-own">
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-primary" style="font-size:12.5px;text-align:center;">View Details</a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-save-btn <?php echo $is_saved ? 'is-saved' : ''; ?>" data-fund-id="<?php echo esc_attr($f->id); ?>" data-saved="<?php echo $is_saved ? '1' : '0'; ?>" onclick="kbfSaveFund('<?php echo esc_js($f->id); ?>', this)" aria-label="<?php echo esc_attr($save_title); ?>">
                <i class="<?php echo esc_attr($save_icon); ?> kbf-icon" style="font-size:13px;color:var(--kbf-text-sm);" aria-hidden="true"></i>
                <span class="kbf-save-loader" aria-hidden="true"></span>
              </button>
          </div>
          <?php else: ?>
          <div class="kbf-explore-actions is-public">
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-primary" style="font-size:12.5px;text-align:center;">View Campaign</a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-save-btn <?php echo $is_saved ? 'is-saved' : ''; ?>" data-fund-id="<?php echo esc_attr($f->id); ?>" data-saved="<?php echo $is_saved ? '1' : '0'; ?>" onclick="kbfSaveFund('<?php echo esc_js($f->id); ?>', this)" aria-label="<?php echo esc_attr($save_title); ?>">
                <i class="<?php echo esc_attr($save_icon); ?> kbf-icon" style="font-size:13px;color:var(--kbf-text-sm);" aria-hidden="true"></i>
                <span class="kbf-save-loader" aria-hidden="true"></span>
              </button>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    </div>
    <?php endif; ?>
    
    <!-- ================== JS ================== -->
    <script>
      if(typeof ajaxurl==='undefined') 
        var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';
    (function(){
        /**
         * @function  kbffSetPageScrollLocked
         * @purpose   Toggles root scroll-lock classes used while mobile overlays are open.
         * @used-by   [kbffOpenSheet, kbffCloseSheet, kbffSetModalLock]
         * @calls     [document.documentElement.classList.add/remove, document.body.classList.add/remove]
         * @params    [boolean locked - Whether page scrolling should be disabled]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbffSetPageScrollLocked = function(locked){
            if (locked) {
                document.documentElement.classList.add('kbff-scroll-lock');
                document.body.classList.add('kbff-scroll-lock');
            } else {
                document.documentElement.classList.remove('kbff-scroll-lock');
                document.body.classList.remove('kbff-scroll-lock');
            }
        };
        var catSel = document.getElementById('kbff-cat-select');
        var sortSel = document.getElementById('kbff-sort-select');
        var savedSel = document.getElementById('kbff-saved-select');
        var msg = document.getElementById('kbff-message');
        var msgCount = document.getElementById('kbff-message-count');
        if (!catSel || !sortSel || !savedSel) return;
        /**
         * @function  updateMsgCount
         * @purpose   Updates the report message character counter display.
         * @used-by   [report textarea input listener, initial filter script setup]
         * @calls     [none]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function updateMsgCount(){
            if(!msg || !msgCount) return;
            msgCount.textContent = (msg.value ? msg.value.length : 0) + '/300';
        }
        if (msg && msgCount){
            updateMsgCount();
            msg.addEventListener('input', updateMsgCount);
        }
        /**
         * @function  buildUrl
         * @purpose   Builds the explore tab URL from current filter/search control values.
         * @used-by   [filter select change listeners, sheet apply flow]
         * @calls     [encodeURIComponent]
         * @params    [none]
         * @returns   [string - URL with query arguments]
         * @status    ACTIVE
         */
        function buildUrl(){
            var url = '<?php echo esc_url($base_url); ?>';
            var params = [];
            var qEl = document.querySelector('#kbff-search-form input[name="ff_q"]');
            if(qEl && qEl.value) params.push('ff_q=' + encodeURIComponent(qEl.value));
            if (catSel.value) params.push('ff_cat=' + encodeURIComponent(catSel.value));
            if (sortSel.value) params.push('ff_sort=' + encodeURIComponent(sortSel.value));
            if (savedSel.value) params.push('ff_saved=' + encodeURIComponent(savedSel.value));
            if (params.length) url += '&' + params.join('&');
            return url;
        }
        window.kbffBuildUrl = buildUrl;
        catSel.addEventListener('change', function(){ window.location.href = buildUrl(); });
        sortSel.addEventListener('change', function(){ window.location.href = buildUrl(); });
        savedSel.addEventListener('change', function(){ window.location.href = buildUrl(); });

        // ===== BOTTOM SHEET =====
        var sheet = document.getElementById('kbff-sheet');
        var overlay = document.getElementById('kbff-sheet-overlay');
        var btn = document.getElementById('kbff-filter-btn');
        var resetBtn = document.getElementById('kbff-reset-filters-btn');
        var badge = document.getElementById('kbff-filter-badge');
        var applyBtn = document.getElementById('kbff-sheet-apply');
        var clearBtn = document.getElementById('kbff-sheet-clear');
        var sheetCatVal = sheet ? (sheet.getAttribute('data-kbff-cat') || '') : '';
        var sheetSortVal = sheet ? (sheet.getAttribute('data-kbff-sort') || 'newest') : 'newest';
        var sheetSavedVal = sheet ? (sheet.getAttribute('data-kbff-saved') || '') : '';

        /**
         * @function  updateFilterBtnState
         * @purpose   Updates the mobile filter button badge and active visual state.
         * @used-by   [initialization, sheet open flow, sheet apply flow]
         * @calls     [classList.toggle]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function updateFilterBtnState(){
            if (!btn || !badge || !catSel || !sortSel || !savedSel) return;
            var count = 0;
            if (catSel.value) count++;
            if (sortSel.value && sortSel.value !== 'newest') count++;
            if (savedSel.value) count++;
            btn.classList.toggle('has-filters', count > 0);
            badge.textContent = String(count);
            badge.classList.toggle('is-hidden', count === 0);
            if (resetBtn) {
                resetBtn.classList.toggle('is-hidden', count === 0);
                resetBtn.disabled = (count === 0);
            }
        }
        updateFilterBtnState();
        window.kbffResetFilters = function(){
            if (catSel) catSel.value = '';
            if (sortSel) sortSel.value = 'newest';
            if (savedSel) savedSel.value = '';
            sheetCatVal = '';
            sheetSortVal = 'newest';
            sheetSavedVal = '';
            updateFilterBtnState();
            window.location.href = buildUrl();
        };
        if (resetBtn) resetBtn.addEventListener('click', window.kbffResetFilters);

        /**
         * @function  kbffOpenSheet
         * @purpose   Opens the mobile filter sheet and syncs its values from desktop controls.
         * @used-by   [Filters button onclick]
         * @calls     [updateFilterBtnState, classList.add]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbffOpenSheet = function(){
            if (sheet) sheet.classList.add('open');
            if (overlay) overlay.classList.add('open');
            window.kbffSetPageScrollLocked(true);
        };
        /**
         * @function  kbffCloseSheet
         * @purpose   Closes the mobile filter sheet and restores page scrolling.
         * @used-by   [sheet overlay onclick, close button onclick, resize handler, apply flow]
         * @calls     [classList.remove]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbffCloseSheet = function(){
            if (sheet) sheet.classList.remove('open');
            if (overlay) overlay.classList.remove('open');
            window.kbffSetPageScrollLocked(false);
        };
        /**
         * @function  kbffApplySheet
         * @purpose   Applies mobile filter sheet selections and navigates to the filtered URL.
         * @used-by   [Apply Filters button listener, kbffClearSheet]
         * @calls     [updateFilterBtnState, window.kbffCloseSheet, buildUrl]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbffApplySheet = function(){
            if (catSel) catSel.value = sheetCatVal || '';
            if (sortSel) sortSel.value = sheetSortVal || 'newest';
            if (savedSel) savedSel.value = sheetSavedVal || '';
            updateFilterBtnState();
            window.kbffCloseSheet();
            window.location.href = buildUrl();
        };
        /**
         * @function  kbffClearSheet
         * @purpose   Resets mobile filter sheet values to defaults and reapplies filters.
         * @used-by   [Clear all filters button listener]
         * @calls     [window.kbffApplySheet]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbffClearSheet = function(){
            sheetCatVal = '';
            sheetSortVal = 'newest';
            sheetSavedVal = '';
            window.kbffApplySheet();
        };
        if (applyBtn) applyBtn.addEventListener('click', window.kbffApplySheet);
        if (clearBtn) clearBtn.addEventListener('click', window.kbffClearSheet);
        window.addEventListener('resize', function(){
          if (window.innerWidth >= 720) window.kbffCloseSheet();
        });

        /**
         * @function  setGroupValue
         * @purpose   Updates active state for a mobile filter choice group and stores selected values.
         * @used-by   [sheet group button click handler]
         * @calls     [document.querySelectorAll, classList.toggle]
         * @params    [string group - Filter group name, string value - Selected option value]
         * @returns   [void]
         * @status    ACTIVE
         */
        function setGroupValue(group, value){
            var buttons = document.querySelectorAll('[data-kbff-group="'+group+'"]');
            buttons.forEach(function(b){
                var isActive = (b.getAttribute('data-kbff-value') === value);
                b.classList.toggle('is-active', isActive);
                b.disabled = false;
            });
            if (group === 'cat') sheetCatVal = value;
            if (group === 'sort') sheetSortVal = value || 'newest';
            if (group === 'saved') sheetSavedVal = value;
        }
        document.querySelectorAll('[data-kbff-group]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var group = btn.getAttribute('data-kbff-group') || '';
                var value = btn.getAttribute('data-kbff-value') || '';
                setGroupValue(group, value);
            });
        });
    })();

    (function(){
        var wrap = document.querySelector('.kbf-card-list[data-kbf-card-pager="explore"]');
        if(!wrap || wrap.dataset.kbfPager === 'on') return;
        var cards = Array.prototype.slice.call(wrap.querySelectorAll('.kbf-explore-card'));
        if(cards.length === 0) return;
        wrap.dataset.kbfPager = 'on';

        var pager = document.createElement('div');
        pager.className = 'kbf-explore-pager';
        var prevPagerBtn = document.createElement('button');
        prevPagerBtn.className = 'kbf-table-pager-btn kbf-table-prev';
        prevPagerBtn.type = 'button';
        prevPagerBtn.textContent = 'Previous';
        var pagerPages = document.createElement('div');
        pagerPages.className = 'kbf-pager-pages';
        var nextPagerBtn = document.createElement('button');
        nextPagerBtn.className = 'kbf-table-pager-btn kbf-table-next';
        nextPagerBtn.type = 'button';
        nextPagerBtn.textContent = 'Next';
        pager.appendChild(prevPagerBtn);
        pager.appendChild(pagerPages);
        pager.appendChild(nextPagerBtn);
        wrap.insertAdjacentElement('afterend', pager);

        var prevBtn = pager.querySelector('.kbf-table-prev');
        var nextBtn = pager.querySelector('.kbf-table-next');
        var pagesWrap = pager.querySelector('.kbf-pager-pages');
        var page = 1;
        var perPage = 9;

        /**
         * @function  buildPageModel
         * @purpose   Builds compact pagination tokens with edge pages and gap markers.
         * @used-by   [renderPages]
         * @calls     [Math.max, Math.min]
         * @params    [number pages - Total page count, number current - Active page number]
         * @returns   [Array - Ordered page numbers and gap tokens]
         * @status    ACTIVE
         */
        function buildPageModel(pages, current){
          var items = [];
          if(pages <= 7){
            for(var i=1;i<=pages;i++) items.push(i);
            return items;
          }
          items.push(1);
          if(current > 3) items.push('gap');
          var start = Math.max(2, current - 1);
          var end = Math.min(pages - 1, current + 1);
          for(var i=start;i<=end;i++) items.push(i);
          if(current < pages - 2) items.push('gap');
          items.push(pages);
          return items;
        }
        /**
         * @function  renderPages
         * @purpose   Renders clickable pagination controls based on the current page model.
         * @used-by   [render]
         * @calls     [buildPageModel, document.createElement, pagesWrap.replaceChildren]
         * @params    [number pages - Total page count, number current - Active page number]
         * @returns   [void]
         * @status    ACTIVE
         */
        function renderPages(pages, current){
          pagesWrap.replaceChildren();
          buildPageModel(pages, current).forEach(function(p){
            if(p === 'gap'){
              var span = document.createElement('span');
              span.className = 'kbf-page-gap';
              span.textContent = '...';
              pagesWrap.appendChild(span);
              return;
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'kbf-page-btn' + (p === current ? ' is-active' : '');
            btn.textContent = String(p);
            btn.addEventListener('click', function(){
              page = p;
              render();
            });
            pagesWrap.appendChild(btn);
          });
        }

        /**
         * @function  render
         * @purpose   Applies client-side pagination visibility and updates pager button states.
         * @used-by   [setLoading, page button click handlers, initial pager load]
         * @calls     [renderPages, Math.ceil]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function render(){
          var total = cards.length;
          var pages = Math.max(1, Math.ceil(total / perPage));
          if(page > pages) page = pages;
          var start = (page - 1) * perPage;
          var end = start + perPage;
          cards.forEach(function(card, i){
            card.style.display = (i >= start && i < end) ? '' : 'none';
          });
          renderPages(pages, page);
          prevBtn.disabled = page <= 1;
          nextBtn.disabled = page >= pages;
          pager.style.display = total > 0 ? 'flex' : 'none';
        }
        /**
         * @function  getPagerLoadingDelay
         * @purpose   Calculates pager loading delay based on network effective type.
         * @used-by   [setLoading]
         * @calls     [none]
         * @params    [none]
         * @returns   [number - Delay in milliseconds]
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
         * @purpose   Applies temporary loading state to pager controls before re-render.
         * @used-by   [prev/next click handlers]
         * @calls     [getPagerLoadingDelay, setTimeout, render]
         * @params    [HTMLElement btn - Triggered pager button]
         * @returns   [void]
         * @status    ACTIVE
         */
        function setLoading(btn){
          if (!btn || btn.classList.contains('is-loading')) return;
          var delay = getPagerLoadingDelay();
          btn.classList.add('is-loading');
          btn.disabled = true;
          if (btn === prevBtn && nextBtn) nextBtn.disabled = true;
          if (btn === nextBtn && prevBtn) prevBtn.disabled = true;
          if (pagesWrap) pagesWrap.style.pointerEvents = 'none';
          setTimeout(function(){
            btn.classList.remove('is-loading');
            render();
            if (pagesWrap) pagesWrap.style.pointerEvents = '';
          }, delay);
        }
        prevBtn.addEventListener('click', function(){
          if(page > 1){ page--; setLoading(prevBtn); }
        });
        nextBtn.addEventListener('click', function(){
          if(nextBtn.disabled) return;
          page++;
          setLoading(nextBtn);
        });
        render();
    })();

    var kbfSaveNonce = '<?php echo esc_js($nonce_save); ?>';
    var kbfSavedOnly = '<?php echo $saved_only ? '1' : ''; ?>';
    if (typeof window.kbfSaveFund === 'undefined') {
        /**
         * @function  kbfSaveFund
         * @purpose   Toggles save/unsave state for a fund and updates button visuals.
         * @used-by   [Save button onclick on explore cards]
         * @calls     [FormData, kbfFetchJson, classList.toggle, window.location.reload]
         * @params    [number|string id - Fund identifier, HTMLElement btn - Trigger button]
         * @returns   [void]
         * @status    ACTIVE
         */
        window.kbfSaveFund = function(id, btn){
            if (window.kbfIsLoggedIn === false) {
                if (window.kbfOpenAuthModal) window.kbfOpenAuthModal('Sign in to save fundraisers.');
                return;
            }
            if(!id) return;
            var el = btn || document.querySelector('.kbf-save-btn[data-fund-id="' + id + '"]');
            if (el && el.classList.contains('is-loading')) return;
            if (el) {
                el.classList.add('is-loading');
                el.disabled = true;
            }
            var fd = new FormData();
            fd.append('action','kbf_toggle_save_fund');
            fd.append('nonce', kbfSaveNonce);
            fd.append('fund_id', id);
            kbfFetchJson(ajaxurl, fd, function(j){
                if(j && j.success){
                    var saved = !!(j.data && j.data.saved);
                    if(el){
                        el.classList.toggle('is-saved', saved);
                        el.setAttribute('data-saved', saved ? '1' : '0');
                        el.setAttribute('aria-label', saved ? 'Saved' : 'Save');
                        var icon = el.querySelector('i');
                        if(icon){
                          icon.classList.remove('ph','ph-bookmark-simple','ph-fill');
                          if(saved){ icon.classList.add('ph-fill','ph-bookmark-simple'); icon.style.color = '#3b82f6'; }
                          else { icon.classList.add('ph','ph-bookmark-simple'); icon.style.color = 'var(--kbf-text-sm)'; }
                        }
                    }
                    if(kbfSavedOnly && !saved){ window.location.reload(); }
                } else {
                    alert((j && j.data && j.data.message) ? j.data.message : 'Unable to save.');
                }
                if (el) {
                    el.classList.remove('is-loading');
                    el.disabled = false;
                }
            }, function(err){
                alert(err || 'Request failed.');
                if (el) {
                    el.classList.remove('is-loading');
                    el.disabled = false;
                }
            });
        };
    }
      /**
       * @function  kbffSetModalLock
       * @purpose   Bridges modal open/close states to the shared page scroll lock controller.
       * @used-by   [kbffCloseSponsorModal, kbffCloseReportModal, kbffOpenReport, kbffOpenSponsor]
       * @calls     [window.kbffSetPageScrollLocked]
       * @params    [boolean locked - Whether modal lock should be enabled]
       * @returns   [void]
       * @status    ACTIVE
       */
      function kbffSetModalLock(locked){
        if (typeof window.kbffSetPageScrollLocked === 'function') window.kbffSetPageScrollLocked(!!locked);
      }
      /**
       * @function  kbffCloseSponsorModal
       * @purpose   Closes the sponsor modal and restores page scrolling state.
       * @used-by   [Sponsor modal close button onclick, kbffSubmitSponsor success flow]
       * @calls     [document.getElementById, classList.remove, kbffSetModalLock]
       * @params    [none]
       * @returns   [void]
       * @status    ACTIVE
       */
      window.kbffCloseSponsorModal = function(){
        var m = document.getElementById('kbff-modal-sponsor');
        if (m) {
          m.classList.remove('is-open');
          m.style.display = 'none';
        }
        kbffSetModalLock(false);
      };
      /**
       * @function  kbffCloseReportModal
       * @purpose   Closes the report modal and restores page scrolling state.
       * @used-by   [Report modal close button onclick, kbffSubmitReport success flow]
       * @calls     [document.getElementById, classList.remove, kbffSetModalLock]
       * @params    [none]
       * @returns   [void]
       * @status    ACTIVE
       */
      window.kbffCloseReportModal = function(){
        var m = document.getElementById('kbff-modal-report');
        if (m) {
          m.classList.remove('is-open');
          m.style.display = 'none';
        }
        kbffSetModalLock(false);
      };

      /**
       * @function  kbffOpenReport
       * @purpose   Opens the report-abuse modal for a selected fund.
       * @used-by   [report abuse action flows in explore cards]
       * @calls     [document.getElementById, classList.add]
       * @params    [number|string id - Fund identifier]
       * @returns   [void]
       * @status    ACTIVE
       */
      window.kbffOpenReport=function(id){
        if (window.kbfIsLoggedIn === false) {
          if (window.kbfOpenAuthModal) window.kbfOpenAuthModal('Sign in to report abuse.');
          else window.location.href = '<?php echo esc_js(kbf_get_page_url('signin')); ?>';
          return;
        }
        var m=document.getElementById('kbff-modal-report');
        if(!m) return;
        document.getElementById('kbff-report-fund-id').value=id;
        m.style.display='flex';
        m.classList.add('is-open');
        kbffSetModalLock(true);
      };
    /**
     * @function  kbffOpenSponsor
     * @purpose   Opens the sponsor modal and renders the selected fund preview details.
     * @used-by   [sponsor action flows tied to explore fund interactions]
     * @calls     [document.getElementById, document.createElement, replaceChildren]
     * @params    [number|string id - Fund ID, string title - Fund title, number goal - Goal amount, number raised - Raised amount, string img - Cover image URL]
     * @returns   [void]
     * @status    ACTIVE
     */
    window.kbffOpenSponsor=function(id,title,goal,raised,img){
        document.getElementById('kbff-fund-id').value=id;
        document.getElementById('kbff-sponsor-form').reset();
        const pct=goal>0?Math.min(100,Math.round((raised/goal)*100)):0;
        const limitEl = document.getElementById('kbff-sponsor-limit');
        const amountEl = document.querySelector('#kbff-sponsor-form input[name="amount"]');
        if (goal > 0) {
            if (limitEl) limitEl.textContent = 'Goal: \u20B1' + parseFloat(goal).toLocaleString();
            if (amountEl) amountEl.removeAttribute('max');
        } else {
            if (limitEl) limitEl.textContent = '';
            if (amountEl) amountEl.removeAttribute('max');
        }
        var preview = document.getElementById('kbff-fund-preview');
        if (preview) {
            preview.replaceChildren();
            if (img) {
                var imgEl = document.createElement('img');
                imgEl.src = img;
                imgEl.style.width = '100%';
                imgEl.style.height = '110px';
                imgEl.style.objectFit = 'cover';
                imgEl.style.borderRadius = '6px';
                imgEl.style.marginBottom = '10px';
                imgEl.style.display = 'block';
                preview.appendChild(imgEl);
            }
            var titleEl = document.createElement('span');
            titleEl.style.fontSize = '14px';
            titleEl.style.color = 'var(--kbf-navy)';
            titleEl.className = 'kbf-strong';
            titleEl.textContent = title || '';
            preview.appendChild(titleEl);

            var progressWrap = document.createElement('div');
            progressWrap.style.marginTop = '8px';
            progressWrap.className = 'kbf-progress-wrap';
            var progressBar = document.createElement('div');
            progressBar.className = 'kbf-progress-bar';
            progressBar.style.width = pct + '%';
            progressWrap.appendChild(progressBar);
            preview.appendChild(progressWrap);

            var stats = document.createElement('div');
            stats.style.display = 'flex';
            stats.style.justifyContent = 'space-between';
            stats.style.fontSize = '12px';
            stats.style.marginTop = '5px';
            stats.style.color = 'var(--kbf-slate)';
            var raisedEl = document.createElement('span');
            raisedEl.textContent = '\u20B1' + parseFloat(raised).toLocaleString() + ' raised';
            var pctEl = document.createElement('span');
            pctEl.textContent = pct + '% of \u20B1' + parseFloat(goal).toLocaleString();
            stats.appendChild(raisedEl);
            stats.appendChild(pctEl);
            preview.appendChild(stats);
        }
        var sponsorModal = document.getElementById('kbff-modal-sponsor');
        if (sponsorModal) {
            sponsorModal.style.display='flex';
            sponsorModal.classList.add('is-open');
        }
        kbffSetModalLock(true);
    };
    /**
     * @function  kbffSetAlertMessage
     * @purpose   Renders a standardized alert banner message inside a target container.
     * @used-by   [kbffSubmitSponsor, kbffSubmitReport]
     * @calls     [document.createElement, replaceChildren]
     * @params    [HTMLElement targetEl - Container element, string type - success|error, string text - Message text]
     * @returns   [void]
     * @status    ACTIVE
     */
    function kbffSetAlertMessage(targetEl, type, text){
        if(!targetEl) return;
        var alert = document.createElement('div');
        alert.className = 'kbf-alert kbf-alert-' + (type === 'success' ? 'success' : 'error');
        alert.textContent = text || 'Request failed.';
        targetEl.replaceChildren(alert);
    }
    /**
     * @function  kbffSubmitSponsor
     * @purpose   Validates and submits sponsor form data to create a checkout session.
     * @used-by   [Sponsor modal submit button onclick]
     * @calls     [kbfSetBtnLoading, kbfSetSkeleton, kbfFetchJson, kbffSetAlertMessage, window.open]
     * @params    [string nonce - Sponsor action nonce]
     * @returns   [void]
     * @status    ACTIVE
     */
    window.kbffSubmitSponsor=function(nonce){
        const form=document.getElementById('kbff-sponsor-form');
        const btn=document.getElementById('kbff-sponsor-submit');
        const msg=document.getElementById('kbff-sponsor-msg');
        form.querySelectorAll('.kbf-field-error').forEach(function(el){ el.remove(); });
        var first=null;
        form.querySelectorAll('[required]').forEach(function(el){
            if(!el.value || !el.value.trim()){
                var group = el.closest('.kbf-form-group');
                if(group){
                    var err = document.createElement('div');
                    err.className = 'kbf-field-error';
                    err.textContent = 'This field is required.';
                    group.appendChild(err);
                }
                if(!first) first = el;
            }
        });
        if(first){ first.focus(); return; }
        const amountEl = form.querySelector('input[name="amount"]');
        const amt = amountEl ? parseFloat(amountEl.value || '0') : 0;
        kbfSetBtnLoading(btn,true,'Processing...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);
        fd.append('action', 'kbf_create_checkout');
        fd.append('nonce',nonce);
        fd.append('is_anonymous',document.getElementById('kbff-anon').checked?'1':'0');
          kbfFetchJson(ajaxurl, fd, (j)=>{
              if(j.success){
                if(j.data && j.data.checkout_url){
                    btn.textContent='Redirecting to payment...';
                    var w = window.open(j.data.checkout_url, '_blank', 'noopener');
                    if (w) { try { w.opener = null; } catch(e) {} }
                    if (window.kbfAwaitPaymentSuccess) window.kbfAwaitPaymentSuccess();
                } else {
                    kbffSetAlertMessage(msg, 'error', 'Maya checkout URL was not returned. Please check your Maya API keys and try again.');
                    kbfSetBtnLoading(btn,false);
                    kbfSetSkeleton(msg,false);
                }
              } else {
                kbffSetAlertMessage(msg, 'error', (j && j.data && j.data.message) ? j.data.message : 'Request failed.');
                kbfSetBtnLoading(btn,false);
                kbfSetSkeleton(msg,false);
              }
        }, (err)=>{
            console.error('KBF checkout error (explore):', err);
            kbffSetAlertMessage(msg, 'error', err || 'Request failed. Please try again.');
            kbfSetBtnLoading(btn,false);
            kbfSetSkeleton(msg,false);
        });
    };
    /**
     * @function  kbffSubmitReport
     * @purpose   Submits a report-abuse request for a fund and handles modal feedback.
     * @used-by   [Report modal submit button onclick]
     * @calls     [fetch, kbfSetBtnLoading, kbfSetSkeleton, kbffSetAlertMessage]
     * @params    [string nonce - Report action nonce]
     * @returns   [void]
     * @status    ACTIVE
     */
    window.kbffSubmitReport=function(nonce){
        const form=document.getElementById('kbff-report-form');
        const btn=document.querySelector('#kbff-modal-report .kbf-modal-footer .kbf-btn-danger');
        const msg=document.getElementById('kbff-report-msg');
        if(!form || !btn || !msg) return;
        var reasonEl = form.querySelector('[name="reason"]');
        var detailsEl = form.querySelector('[name="details"]');
        if(!reasonEl || !detailsEl || !reasonEl.value || !detailsEl.value.trim()){
            kbffSetAlertMessage(msg, 'error', 'Please fill all required fields.');
            return;
        }
        kbfSetBtnLoading(btn,true,'Submitting...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>{
            return r.json().catch(function(){
                return r.text().then(function(t){
                    return {success:false,data:{message:t && t.trim() ? t.trim() : 'Request failed.'}};
                });
            });
        }).then(j=>{
            kbffSetAlertMessage(msg, (j && j.success ? 'success' : 'error'), (j && j.data && j.data.message) ? j.data.message : 'Request failed.');
            if(j.success)setTimeout(function(){ if (window.kbffCloseReportModal) window.kbffCloseReportModal(); },1800);
            else{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{
            kbffSetAlertMessage(msg, 'error', 'Request failed. Please try again.');
            kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false);
        });
    };
    (function(){
        var tipEl = document.getElementById('kbf-explore-tip');
        if(!tipEl) return;
        var tips = [
            'Tip: Funds with regular updates raise up to 3x more.',
            'Tip: Add 2-3 photos to build trust quickly.',
            'Tip: Clear titles get more clicks in search.',
            'Tip: Short, specific titles perform better in search.',
            'Tip: Goals with clear purposes get more sponsors.',
            'Tip: Share to your closest circles first for momentum.',
            'Tip: Use real photos to improve credibility.',
            'Tip: Thank early sponsors to build social proof.',
            'Tip: Post updates after big stories.',
            'Tip: Found a malicious campaign? Report it to us.'
        ];
        /**
         * @function  pickRandom
         * @purpose   Returns a random tip string from the predefined tip list.
         * @used-by   [setTip]
         * @calls     [Math.random, Math.floor]
         * @params    [none]
         * @returns   [string - Randomly selected tip]
         * @status    ACTIVE
         */
        function pickRandom(){
            return tips[Math.floor(Math.random()*tips.length)];
        }
        /**
         * @function  setTip
         * @purpose   Updates the explore tip banner content with formatted tip text.
         * @used-by   [initial tip render, periodic tip rotation interval]
         * @calls     [pickRandom, document.createElement, replaceChildren]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function setTip(){
            var next = pickRandom();
            if(next.indexOf('Tip:') === 0){
                var strong = document.createElement('span');
                strong.className = 'kbf-strong';
                strong.textContent = 'Tip:';
                tipEl.replaceChildren(strong, document.createTextNode(' ' + next.replace(/^Tip:\s*/,'')));
            } else {
                tipEl.textContent = next;
            }
        }
        setTip();
        setInterval(setTip, 300000);
    })();
    // Refresh explore when a fund is edited elsewhere (e.g., dashboard edit modal).
    (function(){
        /**
         * @function  consumeFlag
         * @purpose   Consumes cross-tab update flag and reloads explore when a fund update is detected.
         * @used-by   [initial check, visibility/pageshow handlers, periodic poll]
         * @calls     [localStorage.getItem, localStorage.removeItem, location.reload]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function consumeFlag(){
            try {
                var flag = localStorage.getItem('kbf_fund_updated');
                if (!flag) return;
                localStorage.removeItem('kbf_fund_updated');
                location.reload();
            } catch(e){}
        }
        window.addEventListener('storage', function(ev){
            try {
                if (!ev || ev.key !== 'kbf_fund_updated' || !ev.newValue) return;
                localStorage.removeItem('kbf_fund_updated');
                location.reload();
            } catch(e){}
        });
        consumeFlag();
        document.addEventListener('visibilitychange', function(){
            if (!document.hidden) consumeFlag();
        });
        window.addEventListener('pageshow', function(e){
            if (e && e.persisted) consumeFlag();
        });
        setInterval(consumeFlag, 30000);
    })();
    </script>
    <?php
    return ob_get_clean();
}
