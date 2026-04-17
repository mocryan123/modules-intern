<?php
/* Organizer profile shortcode */
if (!function_exists('kbf_account_profile_get_biz_id')) {
    /**
     * @function  kbf_account_profile_get_biz_id
     * @purpose   Resolves the organizer business ID from request query parameters.
     * @used-by   bntm_shortcode_kbf_organizer_profile
     * @calls     sanitize_text_field, intval, $wpdb->prepare, $wpdb->get_var
     * @params    object $wpdb - WordPress database object used for organizer/user lookup queries
     * @returns   int - Organizer business ID, or 0 when not found
     * @status    ACTIVE
     */
    function kbf_account_profile_get_biz_id($wpdb) {
        $biz_id = 0;
        // Try organizer token first
        $org_token = !empty($_GET['organizer']) ? sanitize_text_field($_GET['organizer']) : '';
        if ($org_token) {
            $pt = $wpdb->prefix.'kbf_organizer_profiles';
            $biz_id = (int)$wpdb->get_var($wpdb->prepare("SELECT business_id FROM {$pt} WHERE organizer_token=%s", $org_token));
        }
        // Try social name (e.g. ?organizer=myname)
        if (!$biz_id && $org_token) {
            $biz_id = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='kbf_social_name' AND meta_value=%s LIMIT 1",
                $org_token
            ));
        }
        if(!$biz_id && isset($_GET['organizer_id'])) {
            $biz_id = intval($_GET['organizer_id']);
        }
        return $biz_id;
    }
}

if (!function_exists('kbf_get_organizer_profile_url')) {
    /**
     * @function  kbf_get_organizer_profile_url
     * @purpose   Builds the organizer profile URL using social name first, then token, then organizer ID fallback.
     * @used-by   modules/kb/user/partials/browse.php, modules/kb/user/partials/fund_details.php
     * @calls     get_user_meta, function_exists, kbf_get_page_url, home_url, add_query_arg, $wpdb->get_var, $wpdb->prepare
     * @params    int $biz_id - Organizer WordPress user ID
     * @returns   string - Organizer profile URL with organizer identifier query argument
     * @status    ACTIVE
     */
    function kbf_get_organizer_profile_url($biz_id) {
        $social_name = get_user_meta($biz_id, 'kbf_social_name', true);
        $base_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('organizer_profile') : home_url('/');
        if (!empty($social_name)) {
            return add_query_arg('organizer', $social_name, $base_url);
        }
        // Fallback to token
        global $wpdb;
        $pt = $wpdb->prefix.'kbf_organizer_profiles';
        $token = $wpdb->get_var($wpdb->prepare("SELECT organizer_token FROM {$pt} WHERE business_id=%d", $biz_id));
        if (!empty($token)) {
            return add_query_arg('organizer', $token, $base_url);
        }
        return add_query_arg('organizer_id', $biz_id, $base_url);
    }
}

if (!function_exists('kbf_account_profile_get_back_link')) {
    /**
     * @function  kbf_account_profile_get_back_link
     * @purpose   Computes back-link URL and label based on incoming fund-related query parameters.
     * @used-by   bntm_shortcode_kbf_organizer_profile
     * @calls     add_query_arg, sanitize_text_field, intval
     * @params    string $fund_details_url - Base URL for fund details page
     * @params    string $browse_url - Base URL for browse page
     * @returns   array - Two-item array containing back URL and back label text
     * @status    ACTIVE
     */
    function kbf_account_profile_get_back_link($fund_details_url, $browse_url) {
        $back_url = $browse_url;
        $back_label = 'Back to Browse';
        if (!empty($_GET['fund'])) {
            $back_url = add_query_arg('fund', sanitize_text_field($_GET['fund']), $fund_details_url);
            $back_label = 'Back to Fund Details';
        } elseif (!empty($_GET['fund_id'])) {
            $back_url = add_query_arg('fund_id', intval($_GET['fund_id']), $fund_details_url);
            $back_label = 'Back to Fund Details';
        } elseif (!empty($_GET['kbf_share'])) {
            $back_url = add_query_arg('kbf_share', sanitize_text_field($_GET['kbf_share']), $fund_details_url);
            $back_label = 'Back to Fund Details';
        }
        return [$back_url, $back_label];
    }
}

if (!function_exists('kbf_account_profile_get_fund_tokens')) {
    /**
     * @function  kbf_account_profile_get_fund_tokens
     * @purpose   Returns mapped fund tokens for provided fund IDs when token helper is available.
     * @used-by   bntm_shortcode_kbf_organizer_profile
     * @calls     function_exists, kbf_get_fund_tokens
     * @params    array $fund_ids - Numeric fund IDs to resolve tokens for
     * @returns   array - Fund token map keyed by fund ID, or empty array
     * @status    ACTIVE
     */
    function kbf_account_profile_get_fund_tokens($fund_ids) {
        if (!empty($fund_ids) && function_exists('kbf_get_fund_tokens')) {
            return kbf_get_fund_tokens($fund_ids);
        }
        return [];
    }
}

/**
 * @function  bntm_shortcode_kbf_organizer_profile
 * @purpose   Renders organizer profile page content, campaigns, reviews, rating modal, and filter interactions.
 * @used-by   modules/kb/includes/shortcodes.php (shortcode map), modules/kb/user/partials/dashboard/sections.php
 * @calls     kbf_global_assets, kbf_account_profile_get_biz_id, kbf_account_profile_get_fund_tokens, kbf_account_profile_get_back_link, kbf_get_page_url, wp_create_nonce, wp_get_current_user, get_user_meta, get_userdata, bntm_universal_container, WordPress DB query methods
 * @params    none
 * @returns   string - Rendered organizer profile HTML container output
 * @status    ACTIVE
 */
function bntm_shortcode_kbf_organizer_profile() {
    kbf_global_assets();
    global $wpdb;
    $biz_id = kbf_account_profile_get_biz_id($wpdb);
    if(!$biz_id) return bntm_universal_container('Organizer Profile','<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Organizer not found.</div></div>', ['show_topbar'=>false,'show_header'=>false]);
    $pt=$wpdb->prefix.'kbf_organizer_profiles';
    $ft=$wpdb->prefix.'kbf_funds';
    $rt=$wpdb->prefix.'kbf_ratings';
    $st=$wpdb->prefix.'kbf_sponsorships';
    $profile=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$biz_id));
    $user=get_userdata($biz_id);
    if(!$user) return bntm_universal_container('Organizer Profile','<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Organizer not found.</div></div>', ['show_topbar'=>false,'show_header'=>false]);
    $funds=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$ft} WHERE business_id=%d AND status IN ('active','completed') AND (status!='active' OR deadline IS NULL OR deadline > NOW()) ORDER BY created_at DESC LIMIT 50",$biz_id));
    $reviews=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$rt} WHERE organizer_id=%d ORDER BY created_at DESC LIMIT 50",$biz_id));
    $sponsor_counts = [];
    $fund_ids = [];
    if (!empty($funds)) {
        $fund_ids = array_values(array_filter(array_map(function($f){ return (int)$f->id; }, $funds)));
        if (!empty($fund_ids)) {
            $placeholders = implode(',', array_fill(0, count($fund_ids), '%d'));
            $sql = "SELECT fund_id, COUNT(*) AS cnt FROM {$st} WHERE fund_id IN ($placeholders) AND payment_status='completed' GROUP BY fund_id";
            $rows = $wpdb->get_results($wpdb->prepare($sql, $fund_ids));
            foreach ($rows as $r) {
                $sponsor_counts[(int)$r->fund_id] = (int)$r->cnt;
            }
        }
    }
    $fund_tokens = kbf_account_profile_get_fund_tokens($fund_ids);
    $fund_details_url = kbf_get_page_url('fund_details');
    $browse_url = kbf_get_page_url('browse');
    list($back_url, $back_label) = kbf_account_profile_get_back_link($fund_details_url, $browse_url);
    $socials=$profile&&$profile->social_links?json_decode($profile->social_links,true):[];
    $nonce_rating = wp_create_nonce('kbf_rating');
    $current_user = wp_get_current_user();
    $current_email = $current_user && !empty($current_user->user_email) ? $current_user->user_email : '';
    $current_user_id = $current_user ? (int)$current_user->ID : 0;
    $has_reviewed = false;
    if ($current_email) {
        $has_reviewed = (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$rt} WHERE organizer_id=%d AND sponsor_email=%s LIMIT 1",
            $biz_id, $current_email
        ));
    }
    $is_self = $current_user_id && $current_user_id === (int)$biz_id;
    $prefill_email = $current_user_id ? $current_email : '';
    $bio_text = '';
    if ($profile && trim((string)$profile->bio) !== '') {
        $bio_text = (string)$profile->bio;
    } else {
        $bio_text = (string)get_user_meta($biz_id, 'description', true);
    }
    $account_address = trim((string)get_user_meta($biz_id, 'kbf_address', true));
    $social_name = sanitize_text_field(get_user_meta($biz_id, 'kbf_social_name', true));
    $profile_type = $profile ? sanitize_text_field($profile->profile_type ?? '') : '';
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-wrap">
      <style>
        .kbf-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--kbf-slate);margin-bottom:20px;}
        .kbf-breadcrumb a{color:var(--kbf-blue);text-decoration:none;font-weight:600;}
        .kbf-breadcrumb a:hover{text-decoration:none;}
        .kbf-card-title{
          font-size:15px;
          font-weight:600;
          overflow:hidden;
          white-space:nowrap;
          text-overflow:ellipsis;
          max-width:100%;
        }
        .kbf-org-avatar{
          position:relative;
          width:70px;
          height:70px;
          flex-shrink:0;
        }
        .kbf-meta{white-space:nowrap;text-overflow:ellipsis;overflow:hidden;}
        .kbf-org-avatar > img,
        .kbf-org-avatar > .kbf-org-avatar-fallback{
          width:70px;
          height:70px;
          border-radius:50%;
          object-fit:cover;
          display:block;
          border:3px solid rgba(255,255,255,.4);
          box-shadow:0 2px 8px rgba(15,23,42,.1);
        }
        .kbf-org-avatar > .kbf-org-avatar-fallback{
          background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);
          display:flex;
          align-items:center;
          justify-content:center;
        }
        .kbf-org-avatar > .kbf-org-avatar-fallback i{font-size:28px;color:#ffffff;}
        .kbf-org-verified{
          position:absolute;right:-2px;bottom:-2px;width:20px;height:20px;border-radius:50%;
          background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 1px #fff;
        }
        .kbf-org-verified{color:#1d4ed8;}
        .kbf-org-verified i{font-size:14px;}
        .kbf-social-icons{
          display:flex;
          align-items:center;
          gap:8px;
          margin-top:8px;
          flex-wrap:wrap;
        }
        .kbf-social-icon{
          width:36px;
          height:36px;
          border-radius:10px;
          border:1.5px solid #e2e8f0;
          background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%);
          display:inline-flex;
          align-items:center;
          justify-content:center;
          transition:all .2s cubic-bezier(.4,0,.2,1);
          text-decoration:none !important;
          box-shadow:0 1px 2px rgba(15,23,42,.04);
        }
        .kbf-social-icon:hover{
          border-color:#3b82f6;
          background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);
          box-shadow:0 4px 12px rgba(59,130,246,.15);
          transform:translateY(-2px);
        }
        .kbf-social-icon i{
          font-size:18px;
          display:block;
          color:#64748b;
          transition:color .2s ease;
        }
        .kbf-social-icon:hover i{
          color:#3b82f6;
        }
        .kbf-profile-sidebar{
          position:sticky;
          top:24px;
          align-self:start;
          height:max-content;
          z-index:0 !important;
        }
        .bntm-container,
        .bntm-content{
          overflow:visible !important;
        }
        .kbf-profile-grid{
          align-items:start;
          overflow:visible;
        }

        .kbf-section-header{
          margin-bottom:14px;
          align-items:center;
          display:flex;
          justify-content:space-between;
          width:100%;
          flex-wrap:wrap;
          gap:12px;
        }

        @media(max-width:900px){
          .kbf-profile-sidebar{position:static;top:auto;}
        }
        @media(max-width:768px){
          .kbf-page-header {
            padding: 20px 24px;
          }
          .kbf-page-header > div {
            gap: 16px;
          }
        }
        .kbf-page-header.kbf-ap-compact {
          padding: 16px 20px;
          text-align: center;
        }
        .kbf-page-header.kbf-ap-compact > div {
          flex-direction: column;
          align-items: center !important;
          text-align: center;
          gap: 16px;
        }
        .kbf-page-header.kbf-ap-compact .kbf-ap-main{
          width:100%;
          display:flex;
          flex-direction:column;
          align-items:center;
        }
        .kbf-page-header.kbf-ap-compact .kbf-ap-name-row,
        .kbf-page-header.kbf-ap-compact .kbf-ap-meta-row{
          justify-content:center;
        }
        .kbf-page-header.kbf-ap-compact .kbf-org-avatar{
          width:80px;
          height:80px;
          margin:0 auto !important;
          align-self:center;
        }
        .kbf-page-header.kbf-ap-compact .kbf-org-avatar > img,
        .kbf-page-header.kbf-ap-compact .kbf-org-avatar > .kbf-org-avatar-fallback{
          width:80px;
          height:80px;
        }
        .kbf-page-header.kbf-ap-compact h2{
          font-size:18px;
        }
        .kbf-page-header.kbf-ap-compact > div > div{
          width:100%;
        }
        .kbf-page-header.kbf-ap-compact .kbf-social-icons{
          justify-content:center;
          width:100%;
        }
        @media(max-width:1024px){
          .kbf-profile-grid{
            grid-template-columns:1fr !important;
          }
          .kbf-profile-sidebar{
            order:2;
          }
        }
        @media(max-width:900px){
          .kbf-inline-filters{
            margin-left:auto;
            width:auto;
            justify-content:flex-end !important;
            gap:8px !important;
            flex-direction:row;
            order:2;
          }
          .kbf-inline-filters > div{
            width:auto;
          }
          #kbf-filter-status,
          #kbf-filter-escrow{
            width:auto;
            min-width:0 !important;
          }
}
        @media(max-width:768px){
          .kbf-section-header{
            margin-bottom:16px;
            justify-content:space-between;
          }
          .kbf-inline-filters{
            margin-left:auto;
            gap:10px !important;
          }
          .kbf-inline-filters > div {
            width:auto;
          }
          #kbf-filter-status,
          #kbf-filter-escrow{
            font-size:14px;
            padding:8px 6px;
          }
          .kbf-form-group span[style*="width:28px"]{
            width:24px !important;
            height:24px !important;
            font-size:12px;
          }
        }
        @media(max-width:640px){
          .kbf-section-header{
            gap:10px;
          }
          .kbf-section-title{
            font-size:15px;
            flex-shrink:0;
          }
          .kbf-inline-filters{
            margin-left:auto;
            width:auto;
            flex-direction:row;
            gap:8px !important;
            justify-content:flex-end;
            order:2;
          }
          .kbf-inline-filters > div{
            width:auto;
          }
          .kbf-ap-filter-btn{
            width:auto !important;
            flex-shrink:0;
          }
        }
        @media(max-width:480px){
          .kbf-section-header{
            gap:8px;
          }
          .kbf-section-title{
            font-size:14px;
            flex-shrink:0;
          }
          .kbf-inline-filters{
            margin-left:auto;
            width:auto;
            gap:6px !important;
            justify-content:flex-end;
          }
          .kbf-ap-filter-btn{
            width:auto !important;
            flex-shrink:0;
          }
          .kbf-form-group span[style*="width:28px"]{
            width:20px !important;
            height:20px !important;
            border-radius:6px !important;
            font-size:11px !important;
          }
          #kbf-filter-status,
          #kbf-filter-escrow{
            font-size:13px;
            padding:6px 4px;
          }
        }
        @media(max-width:520px){
          .kbf-org-avatar{
            width:56px;
            height:56px;
          }
          .kbf-org-avatar > img,
          .kbf-org-avatar > .kbf-org-avatar-fallback{
            width:56px;
            height:56px;
          }
          .kbf-org-avatar > .kbf-org-avatar-fallback i{font-size:24px;}
          .kbf-page-header h2 {
            font-size: 16px;
          }
          .kbf-breadcrumb{font-size:12px;flex-wrap:wrap;}
        }
        .kbf-ap-filter-btn{
          display:none;
          margin-left:auto;
          flex-shrink:0;
        }
        .kbf-ap-sheet-overlay{
          position:fixed;
          inset:0;
          background:rgba(10,16,32,0.45);
          backdrop-filter:blur(2px);
          -webkit-backdrop-filter:blur(2px);
          z-index:2147483000 !important;
          display:none;
          pointer-events:none;
        }
        .kbf-ap-sheet-overlay.open{
          display:block;
          pointer-events:auto;
          inset:0 !important;
          z-index:2147483000 !important;
        }
        .kbf-ap-sheet{
          position:fixed;
          left:0;
          right:0;
          bottom:0;
          background:#fff;
          z-index:2147483001 !important;
          transform:translateY(100%);
          transition:transform .3s cubic-bezier(.4,0,.2,1);
          max-height:min(80vh, calc(100vh - 60px));
          overflow-y:auto;
          overflow-x:hidden;
          padding:0 0 24px;
        }
        @media(max-width:640px){
          .kbf-ap-sheet{
            max-height:min(85vh, calc(100vh - 40px));
          }
        }
        .kbf-ap-sheet.open{transform:translateY(0);z-index:2147483001 !important;}
        .kbf-ap-sheet-handle{
          width:44px;
          height:5px;
          border-radius:999px;
          background:#e2e8f0;
          margin:10px auto 6px;
        }
        .kbf-ap-sheet-header{
          display:flex;
          align-items:center;
          justify-content:space-between;
          padding:6px 20px 10px;
        }
        .kbf-ap-sheet-body{
          padding:0 20px 10px;
          display:grid;
          gap:12px;
        }
        .kbf-ap-sheet-body [data-kbf-ap-group]{
          background:#fff !important;
          color:var(--kbf-text) !important;
          border-color:var(--kbf-border) !important;
          box-shadow:none !important;
          border-radius:12px !important;
          font-weight:600;
          text-align:left;
          justify-content:flex-start;
          transition:all .15s ease !important;
          cursor:pointer;
        }
        .kbf-ap-sheet-body [data-kbf-ap-group]:hover{
          border-color:#93c5fd !important;
          background:#f0f9ff !important;
        }
        .kbf-ap-sheet-body [data-kbf-ap-group]:focus-visible{
          outline:2px solid #3b82f6;
          outline-offset:2px;
        }
        .kbf-ap-sheet-body [data-kbf-ap-group].is-active{
          border-color:#3b82f6 !important;
          background:#eff6ff !important;
          color:#0f172a !important;
          box-shadow:inset 0 0 0 2px #eff6ff !important;
        }
        .kbf-ap-sheet-actions{
          display:flex;
          gap:10px;
          padding:8px 20px 0;
        }
        .kbf-ap-sheet-actions .kbf-btn{
          flex:1;
          height:44px;
          border-radius:12px;
        }
        .kbf-user-ui .kbf-ap-sheet .kbf-btn-primary::before{
          display:none;
          content:none;
        }
        @media (max-width: 1200px){
          #kbf-ap-filter-status-wrap,
          #kbf-ap-filter-escrow-wrap{ display:none !important; }
          .kbf-ap-filter-btn{ display:inline-flex !important; }
        }
        @media(max-width:768px){
          .kbf-ap-sheet-body{
            padding:0 16px 10px;
          }
        }
      </style>
      <!-- Breadcrumb -->
      <div class="kbf-breadcrumb">
        <a href="<?php echo esc_url($back_url); ?>" style="display:inline-flex;align-items:center;gap:6px;">
          <i class="ph ph-arrow-left kbf-icon" style="font-size:14px;color:inherit" aria-hidden="true"></i>
          <?php echo esc_html($back_label); ?>
        </a>
      </div>
    <div class="kbf-page-header">
      <div style="display:flex;align-items:flex-start;gap:20px;">
        <!-- Avatar Section -->
        <div class="kbf-org-avatar">
          <?php if($profile&&$profile->avatar_url): ?>
            <img src="<?php echo esc_url($profile->avatar_url); ?>" alt="">
          <?php else: ?>
            <div class="kbf-org-avatar-fallback" aria-hidden="true">
              <i class="ph ph-user kbf-icon" aria-hidden="true"></i>
            </div>
          <?php endif; ?>
          <?php if($profile && (int)$profile->is_verified === 1): ?>
            <span class="kbf-org-verified" aria-hidden="true"><i class="ph-fill ph-seal-check kbf-icon" aria-hidden="true"></i></span>
          <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="kbf-ap-main" style="flex:1;min-width:0;">
          <!-- Header Row: Name, Social, Badge -->
          <div class="kbf-ap-name-row" style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
            <h2 style="margin:0;font-size:20px;font-weight:600;color:#0f172a;"><?php echo esc_html($user->display_name); ?></h2>
            <?php if($social_name): ?>
              <span style="font-size:13px;color:#64748b;font-weight:500;">@<?php echo esc_html($social_name); ?></span>
            <?php endif; ?>
          </div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap;">
            <span style="font-size:11px;font-weight:600;color:#0f172a;background:#eef4ff;border:1px solid #d4e4ff;padding:4px 10px;border-radius:999px;">
              <?php 
                if ($profile_type === 'nonprofit') {
                  echo 'Non-Profit Organization';
                } elseif ($profile_type === 'business') {
                  echo 'Profit Organization';
                } elseif ($profile_type === 'individual') {
                  echo 'Individual';
                } else {
                  echo 'Account Type Not Set';
                }
              ?>
            </span>
          </div>

          <!-- Bio Section -->
          <?php if(trim($bio_text) !== ''): ?>
            <div style="color:#4f5a6b;font-size:13px;line-height:1.6;margin-bottom:10px;">
              <?php echo nl2br(esc_html(str_replace('\\', '', wp_unslash($bio_text)))); ?>
            </div>
          <?php endif; ?>

          <!-- Address & Rating Row -->
          <div class="kbf-ap-meta-row" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:10px;">
            <?php if($account_address !== ''): ?>
              <div style="display:flex;align-items:center;gap:6px;color:var(--kbf-slate);font-size:12.5px;">
                <i class="ph ph-map-pin kbf-icon" style="font-size:13px;color:#64748b;" aria-hidden="true"></i>
                <span><?php echo esc_html($account_address); ?></span>
              </div>
            <?php endif; ?>
            <?php if($profile&&$profile->rating_count>0): ?>
              <div style="display:flex;align-items:center;gap:6px;color:var(--kbf-slate);font-size:12.5px;">
                <i class="ph-fill ph-thumbs-up kbf-icon" style="font-size:13px;color:#3b82f6;" aria-hidden="true"></i>
                <span><strong><?php echo number_format($profile->rating,1); ?></strong>/5 (<?php echo (int)$profile->rating_count; ?>)</span>
              </div>
            <?php endif; ?>
          </div>

          <!-- Social Icons Row -->
          <?php if(!empty(array_filter($socials))): ?>
            <div class="kbf-social-icons">
              <?php foreach([
                'facebook' => 'ph ph-facebook-logo',
                'instagram' => 'ph ph-instagram-logo',
                'twitter' => 'ph ph-x-logo',
                'website' => 'ph ph-globe'
              ] as $k=>$icon): if(!empty($socials[$k])): ?>
                <a class="kbf-social-icon" href="<?php echo esc_url($socials[$k]); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr(ucfirst($k)); ?>">
                  <i class="<?php echo esc_attr($icon); ?> kbf-icon" aria-hidden="true"></i>
                </a>
              <?php endif; endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="kbf-profile-grid" style="display:grid;grid-template-columns:1fr 360px;gap:24px;">
      <div class="kbf-profile-sidebar">
        <?php if(false): ?><div></div><?php endif; ?>

        <div class="kbf-section-header" style="margin-bottom:14px;align-items:center;display:flex;justify-content:space-between;width:100%;flex-wrap:wrap;gap:12px;">
          <h3 class="kbf-section-title">Campaigns</h3>
          <div class="kbf-inline-filters" style="display:flex;gap:12px;align-items:center;margin-left:auto;flex-wrap:wrap;justify-content:flex-end;">
            <button type="button" class="kbf-btn kbf-btn-secondary kbf-ap-filter-btn" onclick="kbfAccountProfileOpenSheet()">
              <i class="ph ph-sliders kbf-icon" style="font-size:14px" aria-hidden="true"></i>
              Filters
            </button>
            <div class="kbf-form-group" id="kbf-ap-filter-status-wrap" style="display:flex;align-items:center;gap:8px;margin:0;">
              <span style="width:28px;height:28px;border-radius:8px;background:#eef4ff;display:inline-flex;align-items:center;justify-content:center;">
                <i class="ph ph-tag kbf-icon" style="font-size:14px" aria-hidden="true"></i>
              </span>
              <select id="kbf-filter-status">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="suspended">Suspended</option>
                <option value="cancelled">Cancelled</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <div class="kbf-form-group" id="kbf-ap-filter-escrow-wrap" style="display:flex;align-items:center;gap:8px;margin:0;">
              <span style="width:28px;height:28px;border-radius:8px;background:#eef4ff;display:inline-flex;align-items:center;justify-content:center;">
                <i class="ph ph-funnel kbf-icon" style="font-size:14px" aria-hidden="true"></i>
              </span>
              <select id="kbf-filter-escrow">
                <option value="all">All Escrow</option>
                <option value="holding">Holding</option>
                <option value="released">Released</option>
              </select>
            </div>
          </div>
        </div>
        <div class="kbf-ap-sheet-overlay" id="kbf-ap-sheet-overlay" onclick="kbfAccountProfileCloseSheet()"></div>
        <div class="kbf-ap-sheet" id="kbf-ap-sheet">
          <div class="kbf-ap-sheet-handle"></div>
          <div class="kbf-ap-sheet-header">
            <h3 class="kbf-section-title" style="margin:0;">Filter Campaigns</h3>
            <button type="button" class="kbf-btn kbf-btn-secondary" onclick="kbfAccountProfileCloseSheet()">&times;</button>
          </div>
          <div class="kbf-ap-sheet-body">
            <div class="kbf-form-group">
              <label>Status</label>
              <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
                <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-ap-group="status" data-kbf-ap-value="all">
                  <i class="ph ph-app-window kbf-icon" aria-hidden="true"></i>
                  All
                </button>
                <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-ap-group="status" data-kbf-ap-value="active">
                  <i class="ph ph-check-circle kbf-icon" aria-hidden="true"></i>
                  Active
                </button>
                <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-ap-group="status" data-kbf-ap-value="pending">
                  <i class="ph ph-clock kbf-icon" aria-hidden="true"></i>
                  Pending
                </button>
                <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-ap-group="status" data-kbf-ap-value="suspended">
                  <i class="ph ph-pause-circle kbf-icon" aria-hidden="true"></i>
                  Suspended
                </button>
                <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-ap-group="status" data-kbf-ap-value="cancelled">
                  <i class="ph ph-prohibit kbf-icon" aria-hidden="true"></i>
                  Cancelled
                </button>
                <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-ap-group="status" data-kbf-ap-value="completed">
                  <i class="ph ph-flag-checkered kbf-icon" aria-hidden="true"></i>
                  Completed
                </button>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Escrow</label>
              <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
                <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-ap-group="escrow" data-kbf-ap-value="all">
                  <i class="ph ph-cards kbf-icon" aria-hidden="true"></i>
                  All
                </button>
                <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-ap-group="escrow" data-kbf-ap-value="holding">
                  <i class="ph ph-lock-key kbf-icon" aria-hidden="true"></i>
                  Holding
                </button>
                <button type="button" class="kbf-btn kbf-btn-secondary" data-kbf-ap-group="escrow" data-kbf-ap-value="released">
                  <i class="ph ph-lock-open kbf-icon" aria-hidden="true"></i>
                  Released
                </button>
              </div>
            </div>
          </div>
          <div class="kbf-ap-sheet-actions">
            <button type="button" class="kbf-btn kbf-btn-primary" id="kbf-ap-sheet-apply">Apply Filters</button>
            <button type="button" class="kbf-btn kbf-btn-secondary" id="kbf-ap-sheet-clear">Clear all</button>
          </div>
        </div>
        <?php if(empty($funds)): ?>
          <div class="kbf-empty"><p>No active campaigns.</p></div>
        <?php else: ?>
        <div class="kbf-card-list" data-kbf-card-pager="organizer-campaigns">
        <?php foreach($funds as $f):
          $pct=$f->goal_amount>0?min(100,($f->raised_amount/$f->goal_amount)*100):0;
          $sc  = isset($sponsor_counts[(int)$f->id]) ? (int)$sponsor_counts[(int)$f->id] : 0;
          $days_left = $f->deadline ? max(0, ceil((strtotime($f->deadline)-time())/86400)) : null;
        ?>
          <div class="kbf-card" data-status="<?php echo esc_attr($f->status); ?>" data-escrow="<?php echo esc_attr($f->escrow_status ?? ''); ?>">
            <div class="kbf-card-header">
              <div style="flex:1;">
                <div style="display:flex;flex-direction:row;gap:6px;margin-bottom:4px;">
                  <span class="kbf-card-title kbf-strong"><?php echo esc_html($f->title); ?></span>
                  <span class="kbf-badge kbf-badge-<?php echo $f->status; ?>" style="width:max-content;"><?php echo ucfirst($f->status); ?></span>
                </div>
                <div class="kbf-meta">
                  <div class="kbf-meta-row">
                      <span class="kbf-meta-item">
                      <i class="ph ph-tag kbf-icon" aria-hidden="true"></i>
                      <?php echo esc_html($f->category); ?>
                    </span>
                    <span class="kbf-meta-divider"></span>
                    <?php if($days_left!==null): ?>
                      <span class="kbf-meta-item kbf-meta-strong" style="color:<?php echo $days_left<7?'#dc2626':'#64748b';?>;">
                        <i class="ph ph-clock kbf-icon" aria-hidden="true"></i>
                        <?php echo $days_left; ?>d left
                      </span>
                      <span class="kbf-meta-divider"></span>
                    <?php endif; ?>
                    <span class="kbf-meta-item">
                      <i class="ph ph-users kbf-icon" aria-hidden="true"></i>
                      <?php echo $sc; ?> sponsors
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div class="kbf-progress-wrap" style="margin-bottom:12px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
            <div class="kbf-fund-amounts"><span><span class="kbf-strong">&#8369;<?php echo number_format($f->raised_amount,2); ?></span>raised</span><span><span class="kbf-strong">&#8369;<?php echo number_format($f->goal_amount,2); ?></span>goal</span><span><span class="kbf-strong"><?php echo round($pct); ?>%</span>funded</span></div>
            <div class="kbf-card-actions">
              <?php
                $fund_token = '';
                if (!empty($fund_tokens) && isset($fund_tokens[(int)$f->id])) {
                    $fund_token = $fund_tokens[(int)$f->id];
                } elseif (function_exists('kbf_get_or_create_fund_token')) {
                    $fund_token = kbf_get_or_create_fund_token($f->id);
                }
              ?>
              <a class="kbf-btn kbf-btn-primary kbf-btn-sm" href="<?php echo esc_url(add_query_arg('fund', $fund_token ?: $f->id, $fund_details_url)); ?>">
                View Details
              </a>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
        <div class="kbf-table-pager" data-kbf-card-pager-ui="organizer-campaigns"></div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <div style="position:relative;z-index:0;">
        <?php if($profile): ?>
        <div class="kbf-card" style="margin-bottom:16px;">
          <h4 style="font-size:13px;font-weight:700;color:var(--kbf-navy);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;">Stats</h4>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Total Raised</span><span style="color:var(--kbf-blue);" class="kbf-strong">&#8369;<?php echo number_format($profile->total_raised,0); ?></span></div>
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Total Sponsors</span><span class="kbf-strong"><?php echo number_format($profile->total_sponsors); ?></span></div>
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Active Funds</span><span class="kbf-strong"><?php $active_count=0; foreach($funds as $f){ if($f->status==='active') $active_count++; } echo $active_count; ?></span></div>             
          </div>
        </div>
        <?php endif; ?>

        <div class="kbf-card" style="margin-bottom:16px;">
          <div class="kbf-card-header">
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                <h4 style="font-size:13px;font-weight:700;color:var(--kbf-navy);margin:0;text-transform:uppercase;letter-spacing:.5px;">Credibility Score</h4>
                <div style="display:flex;align-items:center;gap:4px;">
                  <i class="ph-fill ph-thumbs-up kbf-icon" style="font-size:14px;color:#3b82f6;" aria-hidden="true"></i>
                  <span style="font-size:15px;font-weight:700;color:var(--kbf-navy);"><?php echo number_format((float)$profile->rating,1); ?></span>
                  <span style="font-size:12px;color:var(--kbf-slate);">/5 (<?php echo (int)$profile->rating_count; ?>)</span>
                </div>
              </div>
            </div>
          </div>
          <?php if($has_reviewed): ?>
            <div class="kbf-meta" style="margin-bottom:10px;">
              <span class="kbf-meta-item" style="color:#3b82f6;font-weight:600;">
                <i class="ph-fill ph-thumbs-up kbf-icon" style="font-size:13px;" aria-hidden="true"></i>
                Already scored
              </span>
            </div>
          <?php elseif($is_self): ?>
            <!-- Hidden for self -->
          <?php elseif(!is_user_logged_in()): ?>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" type="button" disabled style="margin-bottom:10px;padding:6px 12px;opacity:0.6;cursor:not-allowed;">Log in to score</button>
          <?php else: ?>
            <div class="kbf-card-actions">
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" type="button" style="padding:6px 12px;" onclick="document.getElementById('kbf-modal-rating').style.display='flex'">Add Score</button>
            </div>
          <?php endif; ?>
          <?php if(empty($reviews)): ?>
            <div class="kbf-empty" style="padding:18px 10px;"><p>No scores yet.</p></div>
          <?php else: ?>
          <div class="kbf-card-list" data-kbf-card-pager="organizer-reviews">
          <?php foreach($reviews as $r): ?>
            <div class="kbf-card" style="padding:14px 16px;">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
                <div style="display:flex;align-items:center;gap:3px;">
                  <?php for($i=1;$i<=5;$i++): ?>
                  <i class="<?php echo $i <= (int)$r->rating ? 'ph-fill ph-thumbs-up' : 'ph ph-thumbs-up'; ?> kbf-icon" style="font-size:12px;color:<?php echo $i <= (int)$r->rating ? '#3b82f6' : '#94a3b8'; ?>;" aria-hidden="true"></i>
                  <?php endfor; ?>
                </div>
                <span class="kbf-meta"><?php echo date('M d, Y',strtotime($r->created_at)); ?></span>
              </div>
              <?php if($r->review): ?><p style="margin:0;font-size:13.5px;color:var(--kbf-text-sm);font-style:italic;">"<?php echo esc_html($r->review); ?>"</p><?php endif; ?>
              <div class="kbf-meta" style="margin-top:6px;"><?php echo esc_html($r->sponsor_email?:'Anonymous'); ?></div>
            </div>
          <?php endforeach; ?>
          </div>
          <div class="kbf-table-pager" data-kbf-card-pager-ui="organizer-reviews"></div>
          <?php endif; ?>
        </div>

        <?php if(false): ?><div></div><?php endif; ?>
      </div>
    </div>
    </div>
    <!-- Rating Modal -->
    <?php if($is_self): ?>
      <!-- Credibility modal hidden for organizer -->
    <?php else: ?>
    <div id="kbf-modal-rating" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Credibility Score</h3>
          <button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-rating').style.display='none'">&times;</button>
          <p style="font-size:12.5px;color:var(--kbf-slate);margin:2px 0 0;">Rate this organizer's trustworthiness. You can only submit once.</p>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-rating-form" onsubmit="return false;">
            <input type="hidden" name="organizer_id" value="<?php echo (int)$biz_id; ?>">
            <input type="hidden" name="fund_id" value="0">
            <div class="kbf-form-group"><label>Credibility</label>
              <div id="kbf-star-picker" style="display:flex;gap:8px;margin-top:6px;">
                <?php for($i=1;$i<=5;$i++): ?>
                  <i class="kbf-star-btn ph ph-thumbs-up kbf-icon" data-val="<?php echo $i; ?>" data-filled="ph-fill ph-thumbs-up" data-empty="ph ph-thumbs-up" style="cursor:pointer;font-size:28px;color:#94a3b8;" onclick="kbfSetRating(<?php echo $i; ?>)" aria-hidden="true"></i>
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="kbf-rating-val" value="5">
            </div>
            <div class="kbf-form-group"><label>Your Email *</label>
              <input type="email" name="sponsor_email" required placeholder="your@email.com" value="<?php echo esc_attr($prefill_email); ?>"<?php echo $current_user_id ? ' readonly style="background:#f8fafc;color:var(--kbf-slate);"' : ''; ?>>
              <?php if($current_user_id): ?>
                <small class="kbf-meta" style="margin-top:4px;display:block;">Submitting as your account email. This cannot be changed.</small>
              <?php endif; ?>
            </div>
            <div class="kbf-form-group"><label>Comment (optional)</label><textarea name="review" rows="3" placeholder="Share your thoughts..."></textarea></div>
            <div id="kbf-rate-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-rating').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitRating('<?php echo $nonce_rating; ?>')">Submit Score</button>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <script>
    (function(){
      /**
       * @function  kbfSetRating
       * @purpose   Updates selected thumbs-up rating UI state and hidden rating input value.
       * @used-by   Inline onclick handler on rating icon buttons in the modal
       * @calls     document.querySelectorAll, parseInt, document.getElementById
       * @params    number v - Selected rating value from 1 to 5
       * @returns   void
       * @status    ACTIVE
       */
      window.kbfSetRating = function(v){
        var stars = document.querySelectorAll('#kbf-star-picker .kbf-star-btn');
        stars.forEach(function(star){
          var val = parseInt(star.getAttribute('data-val'),10);
          var fillCls = (star.getAttribute('data-filled') || 'ph-fill ph-thumbs-up').split(' ');
          var emptyCls = (star.getAttribute('data-empty') || 'ph ph-thumbs-up').split(' ');
          star.classList.remove.apply(star.classList, fillCls);
          star.classList.remove.apply(star.classList, emptyCls);
          star.classList.add.apply(star.classList, val <= v ? fillCls : emptyCls);
          star.style.color = val <= v ? '#3b82f6' : '#94a3b8';
        });
        var inp = document.getElementById('kbf-rating-val');
        if(inp) inp.value = v;
      };
      /**
       * @function  kbfSubmitRating
       * @purpose   Submits organizer rating form data via AJAX and updates modal feedback state.
       * @used-by   Inline onclick handler on modal submit button
       * @calls     FormData, window.kbfFetchJson, document.getElementById, document.querySelector, document.querySelectorAll, setTimeout, window.location.reload
       * @params    string nonce - Security nonce for kbf_submit_rating AJAX action
       * @returns   void
       * @status    ACTIVE
       */
      window.kbfSubmitRating = function(nonce){
        var form = document.getElementById('kbf-rating-form');
        var btn = document.querySelector('#kbf-modal-rating .kbf-modal-footer .kbf-btn-primary');
        if(!form || !btn) return;
        var fd = new FormData(form);
        fd.append('action','kbf_submit_rating');
        fd.append('nonce',nonce);
        btn.disabled = true;
        var old = btn.textContent;
        btn.textContent = 'Submitting...';
        window.kbfFetchJson(ajaxurl, fd, function(j){
          var msg = document.getElementById('kbf-rate-msg');
          // ===== RULE 3C: HANDLE DUPLICATE ERROR =====
          if (!j.success && j.data && j.data.message && j.data.message.indexOf('already submitted') !== -1) {
            if(msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-warning kbf-alert-compact">' + j.data.message + '</div>';
            setTimeout(function(){ document.getElementById('kbf-modal-rating').style.display='none'; }, 2000);
            document.querySelectorAll('[onclick*="kbf-modal-rating"]').forEach(function(el){
              if(el.tagName === 'BUTTON' && !el.closest('.kbf-modal')) {
                el.disabled = true;
                el.textContent = 'Already Scored';
              }
            });
            btn.disabled = false;
            btn.textContent = old;
            return;
          }
          if(msg) msg.innerHTML = '<div class="kbf-alert ' + (j.success?'kbf-alert-success':'kbf-alert-error') + ' kbf-alert-compact">' + (j.data && j.data.message ? j.data.message : (j.success?'Submitted':'Failed')) + '</div>';
          if(j.success) setTimeout(function(){ window.location.reload(); }, 800);
          btn.disabled = false;
          btn.textContent = old;
        }, function(err){
          var msg = document.getElementById('kbf-rate-msg');
          if(msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error kbf-alert-compact">' + err + '</div>';
          btn.disabled = false;
          btn.textContent = old;
        });
      };
      /**
       * @function  initCardPager
       * @purpose   Initializes client-side card pagination controls for campaign and review card lists.
       * @used-by   DOMContentLoaded handler in this script
       * @calls     document.querySelector, Array.prototype.slice.call, pager/query selector APIs, getFilteredCards, render
       * @params    string scope - Pager scope key matching data-kbf-card-pager attributes
       * @returns   void
       * @status    ACTIVE
       */
      function initCardPager(scope){
        var wrap = document.querySelector('.kbf-card-list[data-kbf-card-pager="'+scope+'"]');
        var pager = document.querySelector('.kbf-table-pager[data-kbf-card-pager-ui="'+scope+'"]');
        if(!wrap || !pager) return;
        var cards = Array.prototype.slice.call(wrap.querySelectorAll('.kbf-card'));
        if(cards.length === 0) return;
        if(pager.dataset.ready === '1') return;
        pager.dataset.ready = '1';
        pager.innerHTML = '' +
          '<div class="kbf-table-pager-left">Show&nbsp;' +
          '<select class="kbf-table-rows">' +
            '<option value="5" selected>5</option>' +
            '<option value="10">10</option>' +
            '<option value="20">20</option>' +
          '</select> rows' +
          '</div>' +
          '<div class="kbf-table-pager-right">' +
            '<button class="kbf-table-pager-btn kbf-table-prev" type="button">Prev</button>' +
            '<span class="kbf-table-pager-page">1 / 1</span>' +
            '<button class="kbf-table-pager-btn kbf-table-next" type="button">Next</button>' +
          '</div>';
        var select = pager.querySelector('.kbf-table-rows');
        var prevBtn = pager.querySelector('.kbf-table-prev');
        var nextBtn = pager.querySelector('.kbf-table-next');
        var pageLabel = pager.querySelector('.kbf-table-pager-page');
        var page = 1;
        var perPage = 5;
        /**
         * @function  getFilteredCards
         * @purpose   Filters campaign cards by selected status and escrow filter values for the campaign scope.
         * @used-by   render
         * @calls     document.getElementById, Array.prototype.filter
         * @params    none
         * @returns   array - Filtered card element list
         * @status    ACTIVE
         */
        function getFilteredCards(){
          if(scope !== 'organizer-campaigns') return cards;
          var statusSel = document.getElementById('kbf-filter-status');
          var escrowSel = document.getElementById('kbf-filter-escrow');
          var statusVal = statusSel ? String(statusSel.value || '').trim() : '';
          var escrowVal = escrowSel ? String(escrowSel.value || '').trim() : '';
          return cards.filter(function(card){
            var s = (card.getAttribute('data-status') || '').toLowerCase();
            var e = (card.getAttribute('data-escrow') || '').toLowerCase();
            var okStatus = !statusVal || statusVal === 'all' || s === statusVal;
            var okEscrow = !escrowVal || escrowVal === 'all' || e === escrowVal;
            return okStatus && okEscrow;
          });
        }
        /**
         * @function  render
         * @purpose   Renders visible card subset for current page and updates pager controls.
         * @used-by   initCardPager, rows/select change handlers, prev/next click handlers, filter change handlers
         * @calls     getFilteredCards, Math.max, Math.ceil, Array.prototype.forEach
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function render(){
          var filtered = getFilteredCards();
          var total = filtered.length;
          var pages = Math.max(1, Math.ceil(total / perPage));
          if(page > pages) page = pages;
          var start = (page - 1) * perPage;
          var end = start + perPage;
          cards.forEach(function(card){
            card.style.display = 'none';
          });
          filtered.forEach(function(card, i){
            card.style.display = (i >= start && i < end) ? '' : 'none';
          });
          pageLabel.textContent = page + ' / ' + pages;
          prevBtn.disabled = page <= 1;
          nextBtn.disabled = page >= pages;
          pager.style.display = total > 0 ? 'flex' : 'none';
        }
        select.addEventListener('change', function(){
          perPage = parseInt(this.value, 10) || 5;
          page = 1;
          render();
        });
        prevBtn.addEventListener('click', function(){ if(page > 1){ page--; render(); } });
        nextBtn.addEventListener('click', function(){ if(page < Math.ceil(cards.length / perPage)){ page++; render(); } });
        if(scope === 'organizer-campaigns') {
          var statusSel = document.getElementById('kbf-filter-status');
          var escrowSel = document.getElementById('kbf-filter-escrow');
          if(statusSel) statusSel.addEventListener('change', function(){ page = 1; render(); });
          if(escrowSel) escrowSel.addEventListener('change', function(){ page = 1; render(); });
        }
        render();
      }
    document.addEventListener('DOMContentLoaded', function(){
      initCardPager('organizer-campaigns');
      initCardPager('organizer-reviews');
      (function(){
        var header = document.querySelector('.kbf-page-header');
        if(!header) return;
        /**
         * @function  syncHeaderCompact
         * @purpose   Toggles compact profile-header layout class based on current header width threshold.
         * @used-by   Immediate call in this IIFE and window resize event listener
         * @calls     header.classList.toggle
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function syncHeaderCompact(){
          header.classList.toggle('kbf-ap-compact', header.offsetWidth < 720);
        }
        syncHeaderCompact();
        window.addEventListener('resize', syncHeaderCompact);
      })();
    });
  })();

  (function(){
    var sheet = document.getElementById('kbf-ap-sheet');
    var overlay = document.getElementById('kbf-ap-sheet-overlay');
    var statusEl = document.getElementById('kbf-filter-status');
    var escrowEl = document.getElementById('kbf-filter-escrow');
    var applyBtn = document.getElementById('kbf-ap-sheet-apply');
    var clearBtn = document.getElementById('kbf-ap-sheet-clear');
    if(!sheet || !overlay || !statusEl || !escrowEl) return;

    // Ensure overlay/sheet are rendered at document root to avoid parent stacking-context conflicts.
    if (overlay.parentNode !== document.body) document.body.appendChild(overlay);
    if (sheet.parentNode !== document.body) document.body.appendChild(sheet);

    /**
     * @function  setGroupValue
     * @purpose   Sets active state for bottom-sheet filter option buttons within a group.
     * @used-by   syncFromSelects, kbfAccountProfileClearSheet, button click handlers
     * @calls     document.querySelectorAll, Element.classList.toggle
     * @params    string group - Filter group key (status or escrow)
     * @params    string value - Target active option value
     * @returns   void
     * @status    ACTIVE
     */
    function setGroupValue(group, value){
      var buttons = document.querySelectorAll('[data-kbf-ap-group="'+group+'"]');
      buttons.forEach(function(b){
        var isActive = (b.getAttribute('data-kbf-ap-value') === value);
        b.classList.toggle('is-active', isActive);
      });
    }
    /**
     * @function  syncFromSelects
     * @purpose   Syncs bottom-sheet button selection state from desktop filter select values.
     * @used-by   kbfAccountProfileOpenSheet
     * @calls     setGroupValue
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function syncFromSelects(){
      setGroupValue('status', statusEl.value || 'all');
      setGroupValue('escrow', escrowEl.value || 'all');
    }
    /**
     * @function  kbfAccountProfileOpenSheet
     * @purpose   Opens filter bottom sheet and overlay while locking page scroll.
     * @used-by   Inline onclick on Filters button
     * @calls     syncFromSelects, classList.add
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfAccountProfileOpenSheet = function(){
      syncFromSelects();
      sheet.classList.add('open');
      overlay.classList.add('open');
      if(!document.body.dataset.kbfOverflowLocked){
        document.body.style.overflow = 'hidden';
        document.body.dataset.kbfOverflowLocked = '1';
      }
    };
    /**
     * @function  kbfAccountProfileCloseSheet
     * @purpose   Closes filter bottom sheet and overlay and restores page scroll state.
     * @used-by   Inline onclick on overlay and close button, kbfAccountProfileApplySheet, window resize handler
     * @calls     classList.remove
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfAccountProfileCloseSheet = function(){
      sheet.classList.remove('open');
      overlay.classList.remove('open');
      if(document.body.dataset.kbfOverflowLocked === '1'){
        document.body.style.overflow = '';
        delete document.body.dataset.kbfOverflowLocked;
      }
    };
    /**
     * @function  getActiveValue
     * @purpose   Reads active value from selected button within a filter group in the sheet.
     * @used-by   kbfAccountProfileApplySheet
     * @calls     sheet.querySelector, Element.getAttribute
     * @params    string group - Filter group key (status or escrow)
     * @returns   string - Active group value or empty string when not selected
     * @status    ACTIVE
     */
    function getActiveValue(group){
      var active = sheet.querySelector('[data-kbf-ap-group="'+group+'"].is-active');
      return active ? active.getAttribute('data-kbf-ap-value') : '';
    }
    /**
     * @function  kbfAccountProfileApplySheet
     * @purpose   Applies selected bottom-sheet filters back to desktop selects and triggers filtering.
     * @used-by   Apply button click handler, kbfAccountProfileClearSheet
     * @calls     getActiveValue, dispatchEvent, kbfAccountProfileCloseSheet
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfAccountProfileApplySheet = function(){
      var statusVal = getActiveValue('status') || 'all';
      var escrowVal = getActiveValue('escrow') || 'all';
      statusEl.value = statusVal;
      escrowEl.value = escrowVal;
      statusEl.dispatchEvent(new Event('change'));
      escrowEl.dispatchEvent(new Event('change'));
      window.kbfAccountProfileCloseSheet();
    };
    /**
     * @function  kbfAccountProfileClearSheet
     * @purpose   Resets bottom-sheet filter selections to all values and applies them.
     * @used-by   Clear button click handler
     * @calls     setGroupValue, kbfAccountProfileApplySheet
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfAccountProfileClearSheet = function(){
      setGroupValue('status', 'all');
      setGroupValue('escrow', 'all');
      window.kbfAccountProfileApplySheet();
    };
    sheet.querySelectorAll('[data-kbf-ap-group]').forEach(function(btn){
      btn.addEventListener('click', function(){
        setGroupValue(btn.getAttribute('data-kbf-ap-group') || '', btn.getAttribute('data-kbf-ap-value') || '');
      });
    });
    if (applyBtn) applyBtn.addEventListener('click', window.kbfAccountProfileApplySheet);
    if (clearBtn) clearBtn.addEventListener('click', window.kbfAccountProfileClearSheet);
    window.addEventListener('resize', function(){
      if (window.innerWidth >= 900) window.kbfAccountProfileCloseSheet();
    });
  })();
    </script>
    <?php
    $c=ob_get_clean();
    if (!empty($_GET['kbf_tab']) && $_GET['kbf_tab'] === 'organizer_profile') {
        return $c;
    }
    return bntm_universal_container('Organizer Profile -- KonekBayan',$c, ['show_topbar'=>false,'show_header'=>false]);
}
