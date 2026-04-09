<?php
/* Fund details shortcode */
if (!function_exists('kbf_fund_details_load_fund')) {
    function kbf_fund_details_load_fund($wpdb, $ft, $current_user_id) {
        if(!empty($_GET['fund'])) {
            $f_token = sanitize_text_field($_GET['fund']);
            return $wpdb->get_row($wpdb->prepare(
                "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.fund_token=%s AND (f.status IN ('active','completed') OR f.business_id=%d)",
                $f_token, $current_user_id
            ));
        }
        if(!empty($_GET['fund_id'])) {
            $fid = intval($_GET['fund_id']);
            return $wpdb->get_row($wpdb->prepare(
                "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.id=%d AND (f.status IN ('active','completed') OR f.business_id=%d)",
                $fid, $current_user_id
            ));
        }
        if(!empty($_GET['kbf_share'])) {
            $token = sanitize_text_field($_GET['kbf_share']);
            return $wpdb->get_row($wpdb->prepare(
                "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.share_token=%s AND (f.status IN ('active','completed') OR f.business_id=%d)",
                $token, $current_user_id
            ));
        }
        return null;
    }
}

if (!function_exists('kbf_fund_details_share_url')) {
    function kbf_fund_details_share_url($fund_details_url, $fund) {
        return add_query_arg('kbf_share', $fund->share_token, $fund_details_url);
    }
}

function bntm_shortcode_kbf_fund_details() {
    kbf_global_assets();
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $fund = null;
    $current_user_id = get_current_user_id();
    $fund = kbf_fund_details_load_fund($wpdb, $ft, $current_user_id);
    $is_owner = $fund && $current_user_id && $fund->business_id == $current_user_id;
    if(!$fund) return bntm_universal_container('Fund Details', '<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Fund not found or no longer active.</div></div>', ['show_topbar'=>false,'show_header'=>false]);

    $st = $wpdb->prefix.'kbf_sponsorships';
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $pct      = $fund->goal_amount>0 ? min(100,($fund->raised_amount/$fund->goal_amount)*100) : 0;
    $sponsors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$st} WHERE fund_id=%d AND payment_status='completed' ORDER BY created_at DESC LIMIT 20",$fund->id));
    $sponsor_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed'",$fund->id));
    // Leaderboard: group by sponsor name/email, sum total contributed, rank by total DESC
    $leaderboard = $wpdb->get_results($wpdb->prepare(
        "SELECT
            CASE WHEN is_anonymous=1 THEN 'Anonymous' ELSE COALESCE(NULLIF(sponsor_name,''),'Anonymous') END AS display_name,
            is_anonymous,
            SUM(amount) AS total_given,
            COUNT(*) AS num_donations,
            MAX(created_at) AS last_donated
         FROM {$st}
         WHERE fund_id=%d AND payment_status='completed'
         GROUP BY display_name, is_anonymous
         ORDER BY total_given DESC
         LIMIT 10",
        $fund->id
    ));
    $organizer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$fund->business_id));
    $days     = $fund->deadline ? max(0,ceil((strtotime($fund->deadline)-time())/86400)) : null;
    $photos   = $fund->photos ? json_decode($fund->photos,true) : [];
    $benefits = $fund->benefits ? json_decode($fund->benefits,true) : [];
    if (!is_array($benefits)) $benefits = [];
    $milestones = [];
    if (!empty($fund->milestones)) {
        $raw_milestones = $fund->milestones;
        if (is_string($raw_milestones)) {
            $milestones = json_decode($raw_milestones, true);
            if (!is_array($milestones)) {
                $milestones = json_decode(stripslashes($raw_milestones), true);
            }
            if (!is_array($milestones) && function_exists('is_serialized') && is_serialized($raw_milestones)) {
                $milestones = maybe_unserialize($raw_milestones);
            }
        }
        if (!is_array($milestones)) $milestones = [];
    }
    $browse_url = kbf_get_page_url('browse');
    $org_token = ($fund && function_exists('kbf_get_or_create_organizer_token'))
        ? kbf_get_or_create_organizer_token($fund->business_id)
        : '';
    $fund_token = ($fund && function_exists('kbf_get_or_create_fund_token'))
        ? kbf_get_or_create_fund_token($fund->id)
        : '';
    $fund_details_url = kbf_get_page_url('fund_details');
    $share_url = kbf_fund_details_share_url($fund_details_url, $fund);
    $profile_url = $fund
        ? add_query_arg(
            [
                'organizer' => $org_token ?: $fund->business_id,
                'fund'      => $fund_token ?: $fund->id,
            ],
            kbf_get_page_url('organizer_profile')
        )
        : kbf_get_page_url('organizer_profile');
    $demo_mode  = (bool)kbf_get_setting('kbf_demo_mode',true);
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $nonce_rating  = wp_create_nonce('kbf_rating');
    $nonce_save   = wp_create_nonce('kbf_save_fund');
    $is_saved = false;
    if($current_user_id){
        $sf = $wpdb->prefix.'kbf_saved_funds';
        $is_saved = (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$sf} WHERE user_id=%d AND fund_id=%d", $current_user_id, $fund->id));
    }

    // Open Graph meta for social share previews
    if (!empty($fund)) {
        static $kbf_og_added = false;
        if (!$kbf_og_added) {
            $kbf_og_added = true;
            $og_title = wp_strip_all_tags($fund->title);
            $og_desc  = wp_trim_words(wp_strip_all_tags($fund->description), 28, '...');
            $og_img   = '';
            if (!empty($photos) && is_array($photos)) {
                $og_img = isset($photos[0]) ? $photos[0] : '';
            }
            if (!$og_img && $organizer && !empty($organizer->avatar_url)) {
                $og_img = $organizer->avatar_url;
            }
            if (!$og_img && defined('BNTM_KBF_URL')) {
                $og_img = BNTM_KBF_URL . 'assets/branding/logo.png';
            }
            add_action('wp_head', function() use ($og_title, $og_desc, $og_img, $share_url) {
                echo "\n<meta property=\"og:type\" content=\"article\" />";
                echo "\n<meta property=\"og:title\" content=\"" . esc_attr($og_title) . "\" />";
                echo "\n<meta property=\"og:description\" content=\"" . esc_attr($og_desc) . "\" />";
                echo "\n<meta property=\"og:url\" content=\"" . esc_url($share_url) . "\" />";
                if (!empty($og_img)) {
                    echo "\n<meta property=\"og:image\" content=\"" . esc_url($og_img) . "\" />";
                }
                echo "\n<meta name=\"twitter:card\" content=\"summary_large_image\" />";
                echo "\n<meta name=\"twitter:title\" content=\"" . esc_attr($og_title) . "\" />";
                echo "\n<meta name=\"twitter:description\" content=\"" . esc_attr($og_desc) . "\" />";
                if (!empty($og_img)) {
                    echo "\n<meta name=\"twitter:image\" content=\"" . esc_url($og_img) . "\" />\n";
                }
            }, 1);
        }
    }

    ob_start();
    ?>

    <!-- ================== CSS ================== -->
    <style>
    .kbf-detail-wrap{max-width:1000px;margin:0 auto;}
    .kbf-photo-main{
      width:100%;
      aspect-ratio:4 / 3;
      height:auto;
    }
    .kbf-photo-main img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }
    .kbf-category-pill,
    .kbf-badge{
      display:inline-flex;
      align-items:center;
      gap:5px;
      padding:3px 8px;
      font-size:10px;
      font-weight:700;
      letter-spacing:.25px;
      line-height:1.1;
      border-radius:999px;
      box-sizing:border-box;
    }
    .kbf-category-pill{
      background:#eef2f7;
      color:#475569;
      border:1px solid #e2e8f0;
      font-weight:600;
      letter-spacing:0;
    }
    .kbf-fundtype-pill{
      display:inline-flex;
      align-items:center;
      padding:3px 8px;
      font-size:10px;
      font-weight:700;
      letter-spacing:.25px;
      line-height:1.1;
      border-radius:999px;
      background:#eef2ff;
      color:#334155;
      border:1px solid #e2e8f0;
      box-sizing:border-box;
    }
    .kbf-category-pill img{
      width:10px;
      height:10px;
      flex-shrink:0;
    }
    .kbf-section-description,
    .kbf-section-organizer,
    .kbf-section-message{
      transition:none !important;
      box-shadow:none !important;
      border:1px solid var(--kbf-border) !important;
    }
    .kbf-benefits-view{
      display:grid;
      gap:10px;
      margin:0;
      padding:0;
      list-style:none;
    }
    .kbf-benefits-view li{
      border:1px solid var(--kbf-border);
      border-radius:12px;
      padding:12px;
      background:#fff;
      display:grid;
      gap:6px;
    }
    .kbf-benefits-amount{
      font-weight:700;
      color:var(--kbf-blue);
      font-size:13px;
    }
    .kbf-benefits-title{
      font-weight:600;
      color:var(--kbf-navy);
      font-size:14px;
    }
    .kbf-benefits-desc{
      font-size:12.5px;
      color:var(--kbf-slate);
    }
    .kbf-section-description:hover,
    .kbf-section-organizer:hover,
    .kbf-section-message:hover{
      transform:none !important;
      box-shadow:none !important;
      border-color:var(--kbf-border) !important;
    }
    .kbf-detail-layout{display:flex;gap:28px;align-items:stretch;}
   .kbf-detail-panels{display:grid;grid-template-columns:1fr 340px;gap:28px;width:100%;}
 .kbf-detail-left{display:flex;flex-direction:column;padding-bottom:24px;justify-content:flex-start;min-height:0;}
.kbf-detail-right{display:flex;flex-direction:column;}
.kbf-detail-sticky{display:flex;flex-direction:column;gap:14px;flex:1;padding-bottom:40px;box-sizing:border-box;}
    .kbf-detail-sticky > *{margin-top:0 !important;margin-bottom:0 !important;}
    .kbf-poster-modal .kbf-modal{max-width:980px;width:980px;}
    .kbf-poster-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:20px;align-items:stretch;}
    .kbf-poster-left{display:flex;flex-direction:column;gap:14px;}
    .kbf-poster-right{background:transparent;border:1px solid var(--kbf-border);border-radius:16px;padding:18px;position:relative;display:flex;flex-direction:column;gap:12px;box-shadow:0 10px 24px rgba(15,23,42,.08);}
    .kbf-poster-preview{display:flex;flex-direction:column;gap:12px;}
    .kbf-poster-brand{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:2px;}
    .kbf-poster-brand-logo{width:28px;height:28px;border-radius:50%;background:#ffffff;display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid #e2e8f0;}
    .kbf-poster-brand-logo img{width:20px;height:20px;display:block;}
    .kbf-poster-brand-name{font-size:14px;font-weight:700;color:var(--kbf-blue);}
    .kbf-poster-cover{width:100%;height:220px;border-radius:12px;overflow:hidden;background:#e2e8f0;border:1px solid var(--kbf-border);}
    .kbf-poster-cover img{width:100%;height:100%;object-fit:cover;display:block;}
    .kbf-poster-title{font-size:16px;font-weight:700;color:var(--kbf-navy);margin-top:2px;word-break:break-word;}
    .kbf-poster-desc{font-size:12.5px;color:#64748b;line-height:1.5;word-break:break-word;white-space:pre-wrap;}
    .kbf-poster-qr{margin-top:auto;display:flex;align-items:flex-end;justify-content:space-between;gap:12px;padding-top:6px;}
    .kbf-poster-qr-canvas{width:90px;height:90px;border-radius:8px;border:1px solid var(--kbf-border);background:#fff;padding:6px;display:flex;align-items:center;justify-content:center;}
    .kbf-poster-qr-canvas canvas,
    .kbf-poster-qr-canvas img{width:78px;height:78px;display:block;}
    .kbf-poster-note{font-size:11.5px;color:var(--kbf-slate);line-height:1.5;}
    .kbf-poster-count{font-size:11px;color:var(--kbf-slate);margin-top:4px;}
    .kbf-poster-close{position:absolute;top:10px;right:10px;}
    @media (max-width: 1000px){
      .kbf-poster-modal .kbf-modal{width:min(980px,94vw);}
    }
    @media (max-width: 860px){
      .kbf-poster-grid{grid-template-columns:1fr;}
      .kbf-poster-right{order:-1;}
    }
    @media (max-width: 620px){
      .kbf-account-header-row{
        display:flex !important;
        flex-direction:row !important;
        flex-wrap:nowrap !important;
        align-items:center;
        justify-content:flex-start;
        gap:8px;
      }
      .kbf-section-organizer .kbf-organizer-row > img{
        display:none;
      }
      .kbf-section-organizer .kbf-org-text{
        text-align:center;
      }
      .kbf-account-header-row .kbf-section-title{
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
      }
      .kbf-account-header-actions{
        width:auto;
        justify-content:flex-end;
      }
      .kbf-section-organizer .kbf-organizer-row{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
      }
      .kbf-section-organizer .kbf-organizer-row > img{
        align-self:flex-start;
      }
      .kbf-account-profile-text{display:none;}
    }
    @media (max-width: 900px){
      .kbf-account-header-row .kbf-section-title{order:1;}
      .kbf-account-header-actions{order:2;}
    }
    .kbf-save-btn{
        transition:none;
    }
        .kbf-save-btn img{
          transition:none;
        }
        .kbf-save-btn.is-saved{
      background:#e7f1ff;
      border-color:#bfd7ff;
      color:#1d4ed8;
    }
    .kbf-save-btn.is-saved img{
      filter:invert(32%) sepia(58%) saturate(1621%) hue-rotate(202deg) brightness(94%) contrast(92%);
    }
    .kbf-leaderboard-card{flex:1;display:flex;flex-direction:column;justify-content:flex-end;max-height:none;}
      .kbf-leaderboard-body{flex:1;display:flex;flex-direction:column;min-height:0;overflow:auto;padding-right:6px;}
    .kbf-leaderboard-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);}
    .kbf-leaderboard-title{display:flex;align-items:center;gap:10px;min-width:0;}
    .kbf-leaderboard-icon{width:28px;height:28px;border-radius:8px;background:#eef4ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .kbf-leaderboard-icon img{width:14px;height:14px;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);}
    .kbf-leaderboard-text{font-size:14px;font-weight:800;color:var(--kbf-navy);line-height:1;}
    .kbf-leaderboard-sub{font-size:11.5px;color:var(--kbf-slate);margin-top:3px;}
    .kbf-leaderboard-pill{
      background:#eef2ff;
      color:#1d4ed8;
      border:1px solid #c7d2fe;
      border-radius:999px;
      padding:3px 8px;
      font-size:10px;
      font-weight:700;
      letter-spacing:.3px;
      text-transform:uppercase;
      flex-shrink:0;
    }
    .kbf-photo-gallery{display:flex;flex-direction:column;gap:12px;margin-bottom:22px;}
    .kbf-photo-main{border-radius:16px;overflow:hidden;border:1px solid var(--kbf-border);background:#f1f5f9;position:relative;}
    .kbf-photo-main img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s ease;transform:translateX(0);}
    .kbf-photo-nav{
        position:absolute;
        top:50%;
        transform:translateY(-50%);
        width:36px;
        height:36px;
        border-radius:50%;
        border:0;
        background:#fff;
        box-shadow:0 8px 18px rgba(15,23,42,0.18);
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        z-index:4;
        opacity:.96;
    }
    .kbf-photo-nav img{
        width:14px;
        height:14px;
        min-width:14px;
        min-height:14px;
        display:block;
        object-fit:contain;
        filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);
    }
    .kbf-photo-prev{left:12px;}
    .kbf-photo-next{right:12px;}
    .kbf-photo-count{
        position:absolute;
        right:10px;
        bottom:10px;
        background:rgba(15,23,42,0.7);
        color:#fff;
        font-size:11.5px;
        font-weight:600;
        padding:6px 10px;
        border-radius:999px;
        z-index:2;
    }
    .kbf-photo-main.is-lightbox-open .kbf-photo-count{display:none;}
    .kbf-photo-main img.is-sliding{transform:translateX(18px);}
    .kbf-photo-thumbs-wrap{
        display:flex;
        align-items:center;
        justify-content:center;
        gap:10px;
    }
    .kbf-photo-thumbs{
        display:flex;
        gap:10px;
        overflow-x:auto;
        padding-bottom:4px;
        justify-content:center;
        max-width:100%;
    }
    .kbf-photo-thumbs::-webkit-scrollbar{height:6px;}
    .kbf-photo-thumbs::-webkit-scrollbar-thumb{background:#dbeafe;border-radius:999px;}
    .kbf-photo-thumbs::-webkit-scrollbar-track{background:transparent;}
    .kbf-thumb-nav{
        width:28px;
        height:28px;
        border-radius:50%;
        border:1px solid var(--kbf-border);
        background:#fff;
        color:#1f2a44;
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        flex-shrink:0;
        padding:0;
        line-height:1;
    }
    .kbf-thumb-nav:hover{border-color:#cfe0f7;box-shadow:0 6px 14px rgba(15,23,42,0.12);}
    .kbf-thumb-nav img{width:12px;height:12px;display:block;filter:invert(18%) sepia(19%) saturate(1128%) hue-rotate(182deg) brightness(93%) contrast(92%);}
    .kbf-photo-thumb{border-radius:12px;overflow:hidden;border:1px solid var(--kbf-border);background:#f8fafc;transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;}
    .kbf-photo-thumb img{width:100%;height:90px;object-fit:cover;display:block;}
    .kbf-photo-thumb:hover{
        transform:translateY(-2px);
        border-color:#cfe0f7;
        box-shadow:0 8px 18px rgba(15,23,42,0.12);
    }
    .kbf-photo-thumb.is-active{
        border-color:#60a5fa;
        box-shadow:0 0 0 2px rgba(96,165,250,0.35),0 8px 18px rgba(15,23,42,0.12);
    }
    .kbf-photo-main{cursor:pointer;position:relative;}
    .kbf-photo-main::after{
        content:'Click to expand';
        position:absolute;
        bottom:10px;
        left:12px;
        font-size:11px;
        font-weight:600;
        color:#f8fafc;
        background:rgba(15,23,42,0.45);
        padding:6px 10px;
        border-radius:999px;
        opacity:0;
        transition:opacity .2s ease;
    }
    .kbf-photo-main:hover::after{opacity:1;}
    .kbf-photo-lightbox{
        position:fixed;
        inset:0;
        background:rgba(15,23,42,0.75);
        display:flex;
        align-items:center;
        justify-content:center;
        z-index:9999;
        padding:24px;
        opacity:0;
        visibility:hidden;
        pointer-events:none;
        transition:opacity .3s ease, visibility .3s ease;
    }
    .kbf-photo-lightbox.open{
        opacity:1;
        visibility:visible;
        pointer-events:auto;
    }
    .kbf-photo-lightbox img{
        width:min(96vw, 1400px);
        max-width:96vw;
        aspect-ratio:4 / 3;
        height:auto;
        max-height:90vh;
        object-fit:cover;
        border-radius:16px;
        box-shadow:0 24px 60px rgba(0,0,0,0.45);
        background:#111827;
        transform:scale(.98);
        transition:transform .3s ease;
    }
    .kbf-photo-lightbox.open img{transform:scale(1);}
    .kbf-photo-lightbox-nav{
        position:absolute;
        top:50%;
        transform:translateY(-50%);
        width:38px;
        height:38px;
        border-radius:50%;
        border:none;
        background:rgba(255,255,255,0.9);
        color:#0f172a;
        font-size:20px;
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        z-index:3;
    }
    .kbf-photo-lightbox-prev{left:18px;}
    .kbf-photo-lightbox-next{right:18px;}
    .kbf-photo-lightbox-close{
        position:absolute;
        top:18px;
        right:18px;
        width:36px;
        height:36px;
        border-radius:50%;
        border:none;
        background:rgba(255,255,255,0.85);
        color:#0f172a;
        font-size:20px;
        cursor:pointer;
    }
    .kbf-detail-sponsor-box{background:#fff;border:1px solid var(--kbf-border);border-radius:12px;padding:24px;box-shadow:var(--kbf-shadow);}
    .kbf-gradient-num{
        font-weight:800 !important;
        display:inline-block;
        background:linear-gradient(to top,#1f6fe0 0%, #4da0ff 100%);
        -webkit-background-clip:text;
        background-clip:text;
        color:transparent !important;
        -webkit-text-fill-color:transparent !important;
    }
    .kbf-sponsor-wall{display:flex;flex-direction:column;gap:12px;margin-top:14px;}
    .kbf-sponsor-item{
        display:flex;
        align-items:flex-start;
        gap:12px;
        padding:12px 14px;
        background:#fff;
        border:1px solid var(--kbf-border);
        border-radius:12px;
        box-shadow:none;
    }
    .kbf-sponsor-avatar{
        width:38px;height:38px;border-radius:50%;
        background:linear-gradient(180deg,#3b82f6 0%, #1d4ed8 100%);
        display:flex;align-items:center;justify-content:center;flex-shrink:0;
        font-size:12px;font-weight:400;color:#fff;
    }
    .kbf-sponsor-avatar img{
        width:18px;height:18px;display:block;filter:invert(100%);
    }
    .kbf-sponsor-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:4px;}
    .kbf-sponsor-msg{
        margin-top:6px;
        font-size:12.5px;
        color:var(--kbf-text-sm);
        font-style:italic;
        background:var(--kbf-slate-lt);
        padding:6px 10px;
        border-radius:8px;
        display:inline-block;
    }
    .kbf-sponsor-amount{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:4px 10px;
        border-radius:999px;
        background:#e8f1ff;
        color:#1d4ed8;
        font-size:12px;
        font-weight:600;
        white-space:nowrap;
    }

    .kbf-org-avatar{position:relative;width:52px;height:52px;flex-shrink:0;}
    .kbf-org-avatar > img{width:52px;height:52px;border-radius:50%;object-fit:cover;display:block;}
    .kbf-org-verified{position:absolute;right:-2px;bottom:-2px;width:18px;height:18px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 1px #fff;}
    .kbf-org-verified::before{content:'';width:13px;height:13px;background:#1d4ed8;display:block;
        -webkit-mask:url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/patch-check-fill.svg') no-repeat center/contain;
        mask:url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/patch-check-fill.svg') no-repeat center/contain;}
    .kbf-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--kbf-slate);margin-bottom:20px;}
    .kbf-breadcrumb a{color:var(--kbf-blue);text-decoration:none;font-weight:600;}
    .kbf-breadcrumb a:hover{text-decoration:underline;}
    .kbf-more-wrap{position:relative;}
    .kbf-more-menu{
        position:absolute;
        right:0;
        top:calc(100% + 8px);
        background:rgba(255,255,255,0.98);
        border:1px solid #e2e8f0;
        border-radius:14px;
        box-shadow:
          0 14px 30px rgba(15,23,42,.12),
          0 4px 10px rgba(15,23,42,.08);
        padding:8px;
        min-width:170px;
        z-index:20;
        opacity:0;
        visibility:hidden;
        pointer-events:none;
        transform:translateY(-6px) scale(0.98);
        transition:opacity .18s ease, transform .18s ease, visibility .18s ease;
        backdrop-filter:blur(10px);
    }
    .kbf-more-menu.open{
        opacity:1;
        visibility:visible;
        pointer-events:auto;
        transform:translateY(0) scale(1);
    }
    .kbf-more-menu button{
        width:100%;
        justify-content:flex-start !important;
        gap:8px;
        margin:4px 0;
        border:0;
        background:transparent;
        padding:8px 10px;
        border-radius:10px;
        font-size:12.5px;
        font-weight:600;
        color:#0f172a;
        text-align:left;
        transition:background .15s ease, color .15s ease, transform .15s ease;
    }
    .kbf-more-menu .kbf-btn,
    .kbf-more-menu .kbf-btn-secondary{
        background:transparent !important;
        border:0 !important;
        box-shadow:none !important;
    }
    .kbf-more-menu button:hover,
    .kbf-more-menu .kbf-btn:hover,
    .kbf-more-menu .kbf-btn-secondary:hover{
        background:linear-gradient(90deg,#e7f1ff 0%, #edf5ff 60%, #f8fbff 100%) !important;
        color:#0f172a !important;
        transform:translateX(1px);
        box-shadow:
          inset 0 0 0 1px #bfdbfe,
          0 8px 18px rgba(59,130,246,.16);
    }
    .kbf-more-menu button:active{
        background:#e7f1ff;
    }
    @media(max-width:900px){
        .kbf-detail-panels{display:flex;flex-direction:column;gap:20px;}
        .kbf-detail-left,.kbf-detail-right{width:100%;}
        .kbf-detail-right{order:0;}
        .kbf-section-photo{order:1;}
        .kbf-section-title{order:2;}
        .kbf-section-organizer{order:3;}
        .kbf-section-fund-type{order:4;}
        .kbf-section-leaderboard{order:5;}
        .kbf-section-progress{order:6;}
        .kbf-detail-sticky{height:auto !important;}
        .kbf-photo-main img{height:100%;}
        .kbf-photo-thumb img{height:80px;}
        .kbf-photo-nav{
            width:34px;
            height:34px;
        }
        .kbf-photo-prev{left:10px;}
        .kbf-photo-next{right:10px;}
    }
    @media (max-width: 680px){
        .kbf-photo-lightbox{padding:12px;}
        .kbf-photo-lightbox img{
            width:100%;
            max-width:100%;
            aspect-ratio:4 / 3;
            height:auto;
            max-height:86vh;
            object-fit:cover;
            border-radius:14px;
        }
        .kbf-photo-lightbox-nav{
            width:32px;
            height:32px;
        }
        .kbf-photo-lightbox-prev{left:8px;}
        .kbf-photo-lightbox-next{right:8px;}
        .kbf-photo-lightbox-close{
            top:12px;
            right:12px;
            width:32px;
            height:32px;
        }
        .kbf-photo-nav{
            width:32px;
            height:32px;
        }
        .kbf-photo-prev{left:8px;}
        .kbf-photo-next{right:8px;}
        .kbf-photo-nav img{
            width:12px;
            height:12px;
            min-width:12px;
            min-height:12px;
        }
    }
    @media (max-width: 560px){
        .kbf-account-header-row{
            flex-wrap:nowrap;
            justify-content:flex-start;
            align-items:center;
        }
        .kbf-account-header-actions{
            width:auto;
            justify-content:flex-start;
        }
    }
 </style>
    <!-- ================== HTML ================== -->
    <div class="kbf-wrap">

    <!-- Sponsor Modal -->
    <div id="kbf-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header"><h3>Sponsor "<?php echo esc_html(wp_trim_words($fund->title,6)); ?>"</h3><button class="kbf-modal-close" onclick="kbfHideModal('kbf-modal-sponsor')">&times;</button></div>
        <div class="kbf-modal-body">
          <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:12px 16px;margin-bottom:18px;display:flex;justify-content:space-between;font-size:13px;">
            <span><strong style="color:var(--kbf-blue);">&#8369;<?php echo number_format($fund->raised_amount,2); ?></strong> raised</span>
            <span style="color:var(--kbf-slate);"><?php echo round($pct); ?>% of &#8369;<?php echo number_format($fund->goal_amount,2); ?> goal</span>
          </div>
          <form id="kbf-sponsor-form" onsubmit="return false;">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Account</label><input type="text" name="sponsor_name" id="spd-name" placeholder="Your name, company, or account"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;"><label class="kbf-checkbox-row"><input type="checkbox" id="spd-anon" onchange="document.getElementById('spd-name').disabled=this.checked"> Sponsor Anonymously</label></div>
            </div>
            <div class="kbf-form-group">
              <label>Amount (PHP) *</label>
              <input type="number" name="amount" placeholder="Min. ?50" min="50" step="1" max="<?php echo $fund->goal_amount>0?max(0,$fund->goal_amount-$fund->raised_amount):''; ?>" required>
              <?php if($fund->goal_amount>0): ?>
                <div class="kbf-meta" style="margin-top:4px;">Max allowed: &#8369;<?php echo number_format(max(0,$fund->goal_amount-$fund->raised_amount),2); ?> (remaining goal)</div>
              <?php endif; ?>
            </div>
            <div class="kbf-form-group">
              <label>Encouraging Message (optional)</label>
              <textarea name="message" id="kbf-sponsor-message" rows="2" maxlength="300" placeholder="Leave a message for the organizer..."></textarea>
              <div class="kbf-char-count" id="kbf-sponsor-message-count">0/300</div>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Email (for receipt) *</label><input type="email" name="email" placeholder="your@email.com" required></div>
              <div class="kbf-form-group"><label>Phone *</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX" required></div>
            </div>
            <input type="hidden" name="payment_method" value="online_payment">
            <div id="kbf-spd-msg" style="margin-top:10px;"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfHideModal('kbf-modal-sponsor')">Cancel</button>
          <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSpdSponsor('<?php echo $nonce_sponsor; ?>')">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="14" height="14" style="filter:invert(100%);">
            Confirm Sponsorship
          </button>
        </div>
      </div>
    </div>

    <!-- Report Modal -->
    <div id="kbf-modal-report" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Report This Fund</h3><button class="kbf-modal-close" onclick="kbfHideModal('kbf-modal-report')">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-report-form">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email"></div>
            <div class="kbf-form-group"><label>Upload Photo (optional)</label><input type="file" name="report_image" accept="image/*"></div>
            <div class="kbf-form-group"><label>Reason *</label><select name="reason" required><option value="">Select</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Info</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select></div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" required></textarea></div>
            <div id="kbf-rpt-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfHideModal('kbf-modal-report')">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbfSpdReport('<?php echo $nonce_report; ?>')">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- Rating Modal -->
    <div id="kbf-modal-rating" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Credibility Score</h3><button class="kbf-modal-close" onclick="kbfHideModal('kbf-modal-rating')">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-rating-form">
            <input type="hidden" name="organizer_id" value="<?php echo $fund->business_id; ?>">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-group"><label>Credibility</label>
              <div id="kbf-star-picker" style="display:flex;gap:8px;margin-top:6px;">
                <?php for($i=1;$i<=5;$i++): ?>
                  <img class="kbf-star-btn" data-val="<?php echo $i; ?>" data-filled="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/hand-thumbs-up-fill.svg" data-empty="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/hand-thumbs-up.svg" src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/hand-thumbs-up.svg" alt="" width="32" height="32" style="cursor:pointer;filter:invert(79%) sepia(10%) saturate(383%) hue-rotate(183deg) brightness(96%) contrast(90%);" onclick="kbfSetRating(<?php echo $i; ?>)">
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="kbf-rating-val" value="5">
            </div>
            <div class="kbf-form-group"><label>Your Email *</label><input type="email" name="sponsor_email" required placeholder="your@email.com"></div>
            <div class="kbf-form-group"><label>Comment (optional)</label><textarea name="review" rows="3" placeholder="Share your thoughts..."></textarea></div>
            <div id="kbf-rate-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfHideModal('kbf-modal-rating')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitRating('<?php echo $nonce_rating; ?>')">Submit Score</button>
        </div>
      </div>
    </div>

    <!-- Breadcrumb -->
    <div class="kbf-breadcrumb">
      <a href="<?php echo esc_url($browse_url); ?>" style="display:inline-flex;align-items:center;gap:6px;">
        <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/arrow-left.svg" alt="" width="14" height="14" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
        Back to Browse
      </a>
    </div>

    <?php if($fund->status==='pending' && $is_owner): ?>
    <div class="kbf-alert kbf-alert-warning" style="margin-bottom:20px;"><strong>Under Review:</strong> This fund is not yet visible to sponsors. Once approved it goes live.</div>
    <?php elseif($fund->status==='suspended'): ?>
    <div class="kbf-alert kbf-alert-error" style="margin-bottom:20px;"><strong>Suspended:</strong> <?php echo esc_html($fund->admin_notes?:'Contact support.'); ?></div>
    <?php endif; ?>

    <div class="kbf-detail-layout">
      <div class="kbf-detail-panels">
      <!-- LEFT: Main content -->
      <div class="kbf-detail-left">
        <!-- Photo gallery -->
        <?php if(!empty($photos)): ?>
        <div class="kbf-photo-gallery kbf-section-photo">
          <div class="kbf-photo-main" id="kbf-photo-main" data-photos='<?php echo esc_attr(wp_json_encode(array_values($photos))); ?>'>
            <img src="<?php echo esc_url($photos[0]); ?>" alt="<?php echo esc_attr($fund->title); ?>">
            <?php if(count($photos)>1): ?>
              <button type="button" class="kbf-photo-nav kbf-photo-prev" id="kbf-photo-prev" aria-label="Previous photo">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/chevron-left.svg" alt="">
              </button>
              <button type="button" class="kbf-photo-nav kbf-photo-next" id="kbf-photo-next" aria-label="Next photo">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/chevron-right.svg" alt="">
              </button>
              <span class="kbf-photo-count" id="kbf-photo-count">1/<?php echo count($photos); ?></span>
            <?php endif; ?>
          </div>
          <?php if(false): ?>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="kbf-photo-gallery kbf-section-photo">
          <div class="kbf-photo-main" id="kbf-photo-main" style="height:360px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--kbf-navy) 0%,var(--kbf-navy-light) 100%);">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="64" height="64" style="opacity:.25;filter:invert(100%);">
          </div>
        </div>
        <?php endif; ?>

        <div class="kbf-photo-lightbox" id="kbf-photo-lightbox" aria-hidden="true">
          <button type="button" class="kbf-photo-lightbox-close" id="kbf-photo-lightbox-close">&times;</button>
          <button type="button" class="kbf-photo-lightbox-nav kbf-photo-lightbox-prev" id="kbf-photo-lightbox-prev">&#8249;</button>
          <img id="kbf-photo-lightbox-img" alt="Expanded photo">
          <button type="button" class="kbf-photo-lightbox-nav kbf-photo-lightbox-next" id="kbf-photo-lightbox-next">&#8250;</button>
        </div>

        <!-- Title + Meta -->
        <div class="kbf-section-title" style="margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
              <span class="kbf-category-pill">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/tag-fill.svg" alt="" width="11" height="11" style="filter:invert(34%) sepia(8%) saturate(1386%) hue-rotate(182deg) brightness(93%) contrast(85%);">
                <?php echo esc_html(ucfirst(strtolower((string)$fund->category))); ?>
              </span>
              <span class="kbf-fundtype-pill"><?php echo ucwords(str_replace('_',' ',$fund->funder_type)); ?></span>
            </div>
          <h1 style="font-size:24px;font-weight:600;color:var(--kbf-navy);margin:0 0 10px;line-height:1.3;"><?php echo esc_html($fund->title); ?></h1>
          <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:13px;color:var(--kbf-slate);">
            <span style="display:flex;align-items:center;gap:5px;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/geo-alt-fill.svg" alt="" width="14" height="14" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
              <?php echo esc_html($fund->location); ?>
            </span>
          </div>
        </div>


           <!-- Organizer card -->
        <?php if($fund->organizer_name): ?>
        <div class="kbf-card kbf-section-organizer">
          <div class="kbf-account-header-row" style="display:flex;justify-content:space-between;align-items:center;margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);gap:10px;flex-wrap:nowrap;">
            <h3 class="kbf-section-title" style="margin:0;">About the Account</h3>
            <div class="kbf-account-header-actions" style="display:flex;align-items:center;gap:10px;flex-wrap:nowrap;white-space:nowrap;">
              <a href="<?php echo esc_url($profile_url); ?>" class="kbf-account-profile-link" style="background:none;border:none;color:var(--kbf-blue);cursor:pointer;font-size:12.5px;font-weight:600;padding:0;display:flex;align-items:center;gap:4px;text-decoration:none;">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg" alt="" width="13" height="13" style="filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);">
                <span class="kbf-account-profile-text">View Full Profile</span>
              </a>
            </div>
          </div>
          <a href="<?php echo esc_url($profile_url); ?>" class="kbf-organizer-row" style="display:flex;align-items:center;gap:14px;cursor:pointer;text-decoration:none;color:inherit;" title="View account profile">
            <div class="kbf-org-avatar">
              <?php if($organizer&&$organizer->avatar_url): ?>
                <img src="<?php echo esc_url($organizer->avatar_url); ?>" style="border-radius:50%;object-fit:cover;border:2px solid var(--kbf-border);transition:border-color .15s;" onmouseover="this.style.borderColor='var(--kbf-blue)'" onmouseout="this.style.borderColor='var(--kbf-border)'">
              <?php else: ?>
                <div style="width:52px;height:52px;border-radius:50%;background:var(--kbf-navy);display:flex;align-items:center;justify-content:center;cursor:pointer;"><img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg" alt="" width="24" height="24" style="filter:invert(100%);"></div>
              <?php endif; ?>
              <?php if($organizer&&$organizer->is_verified): ?>
                <span class="kbf-org-verified" aria-hidden="true"></span>
              <?php endif; ?>
            </div>

            <div class="kbf-org-text" style="flex:1;">
              <div style="font-weight:700;font-size:15px;color:var(--kbf-navy);"><?php echo esc_html($fund->organizer_name); ?></div>
              <?php if($organizer&&$organizer->bio): ?><p style="font-size:13px;color:var(--kbf-text-sm);margin:4px 0 0;line-height:1.55;"><?php echo esc_html(wp_trim_words(str_replace('\\', '', wp_unslash($organizer->bio)),30)); ?></p><?php endif; ?>
              <?php
  $rating_val = ($organizer && $organizer->rating_count > 0) ? (float)$organizer->rating : 0;
  $rating_round = round($rating_val);
  $rating_count = (int)($organizer ? $organizer->rating_count : 0);
?>
            </div>
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/chevron-right.svg" alt="" width="16" height="16" style="flex-shrink:0;opacity:.7;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);">
          </a>
        </div>
        <?php endif; ?>

      </div>

      <!-- RIGHT: Sticky action sidebar -->
      <div class="kbf-detail-right">
      <div class="kbf-detail-sticky">

        <!-- Top Sponsors Leaderboard -->
        <div class="kbf-leaderboard-card kbf-section-leaderboard" style="background:#fff;border:1px solid var(--kbf-border);border-radius:12px;padding:18px;margin-top:14px;">
          <div class="kbf-leaderboard-head">
            <div class="kbf-leaderboard-title">
              <span class="kbf-leaderboard-icon">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/bar-chart-fill.svg" alt="">
              </span>
              <div>
            <div class="kbf-leaderboard-text" style="font-weight:600;">Top Sponsors</div>
              </div>
            </div>
            <?php if(!empty($leaderboard)): ?><span class="kbf-leaderboard-pill"><?php echo count($leaderboard); ?> listed</span><?php endif; ?>
          </div>

          <div class="kbf-leaderboard-body" data-kbf-leaderboard-list>
          <?php if(empty($leaderboard)): ?>
          <div style="text-align:center;padding:20px 10px;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;height:full;">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="32" height="32" style="margin:0 auto 10px;display:block;opacity:.25;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
            <p style="font-size:13px;color:var(--kbf-slate);margin:0;font-weight:600;">No sponsors yet</p>
            <p style="font-size:12px;color:var(--kbf-slate);margin:4px 0 0;opacity:.7;">Be the first to support!</p>
          </div>
          <?php else: ?>
          <?php
          $rank_colors = [
              1 => ['bg'=>'linear-gradient(135deg,#f59e0b,#fbbf24)', 'icon'=>'ðŸ¥‡', 'label'=>'1st'],
              2 => ['bg'=>'linear-gradient(135deg,#6b7280,#9ca3af)', 'icon'=>'ðŸ¥ˆ', 'label'=>'2nd'],
              3 => ['bg'=>'linear-gradient(135deg,#b45309,#d97706)', 'icon'=>'ðŸ¥‰', 'label'=>'3rd'],
          ];
          foreach($leaderboard as $rank => $entry):
            $pos = $rank + 1;
            $rc  = isset($rank_colors[$pos]) ? $rank_colors[$pos] : ['bg'=>'var(--kbf-navy)', 'icon'=>null, 'label'=>$pos.'th'];
            $initials = $entry->is_anonymous ? '?' : strtoupper(substr($entry->display_name, 0, 1));
            $last_donated = !empty($entry->last_donated) ? date('M d, Y g:ia', strtotime($entry->last_donated)) : '';
          ?>
          <div class="kbf-leaderboard-item" style="margin-bottom:<?php echo $pos < count($leaderboard)?'14':'0'; ?>px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;">
              <!-- Rank badge -->
              <div style="width:28px;height:28px;border-radius:50%;background:<?php echo $rc['bg']; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:<?php echo $pos<=3?'13':'11'; ?>px;font-weight:800;color:#fff;">
                <?php echo $pos <= 3 ? $pos : $pos; ?>
              </div>
              <!-- Name -->
              <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:700;color:var(--kbf-navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?php echo $entry->is_anonymous ? '<em style="color:var(--kbf-slate);font-style:italic;">Anonymous</em>' : esc_html($entry->display_name); ?>
                  <?php if($pos===1 && !empty($leaderboard) && count($leaderboard)>1): ?>
                  <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:99px;margin-left:4px;font-weight:700;font-style:normal;">TOP</span>
                  <?php endif; ?>
                </div>
                <?php if($entry->num_donations > 1): ?><div style="font-size:10.5px;color:var(--kbf-slate);"><?php echo $entry->num_donations; ?> donations</div><?php endif; ?>
              </div>
              <!-- Amount -->
              <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:13.5px;font-weight:600;color:var(--kbf-blue);">&#8369;<?php echo number_format($entry->total_given, 0); ?></div>
              </div>
            </div>
            <?php if($last_donated): ?>
            <div style="margin-left:38px;font-size:11.5px;color:var(--kbf-slate);">
              Last support: <?php echo esc_html($last_donated); ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>

          <?php endif; ?>
          </div>
          <?php if(!empty($leaderboard)): ?>
          <div class="kbf-table-pager" data-kbf-leaderboard-pager></div>
          <?php endif; ?>
        </div>

        <div class="kbf-detail-sponsor-box kbf-section-progress">
          <!-- Progress -->
          <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
              <span class="kbf-gradient-num" style="font-size:24px;font-weight:600 !important;">&#8369;<?php echo number_format($fund->raised_amount,2); ?></span>
              <span style="font-size:13px;color:var(--kbf-slate);">of &#8369;<?php echo number_format($fund->goal_amount,2); ?></span>
            </div>
            <div class="kbf-progress-wrap" style="height:10px;margin-bottom:10px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%;height:10px;"></div></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center;">
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div class="kbf-gradient-num" style="font-size:16px;font-weight:600 !important;"><?php echo round($pct); ?>%</div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Funded</div>
              </div>
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div class="kbf-gradient-num" style="font-size:16px;font-weight:600 !important;"><?php echo $sponsor_count; ?></div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Sponsors</div>
              </div>
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div class="kbf-gradient-num" style="font-size:16px;font-weight:600 !important;"><?php echo $days!==null?$days:'&infin;'; ?></div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Days Left</div>
              </div>
            </div>
          </div>

          <?php if($fund->status==='active' && (!$is_owner || $demo_mode)): ?>
          <button class="kbf-btn kbf-btn-primary" style="width:100%;padding:13px;font-size:15px;font-weight:700;margin-bottom:10px;" onclick="kbfShowModal('kbf-modal-sponsor')">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="16" height="16" style="filter:invert(100%);">
            <?php echo $demo_mode ? 'Demo Sponsor' : 'Sponsor This Fund'; ?>
          </button>
          <!-- Auto-refund UI removed -->
          <div class="kbf-action-note">Sponsors get a receipt instantly after checkout.</div>
          <?php elseif($fund->status==='completed'): ?>
          <div class="kbf-alert kbf-alert-success" style="margin-bottom:10px;text-align:center;font-weight:700;">This fund has been completed!</div>
          <?php endif; ?>

          <div style="display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:10px;">
            <button class="kbf-btn kbf-btn-secondary kbf-save-btn <?php echo $is_saved ? 'is-saved' : ''; ?>" style="font-size:13px;" data-fund-id="<?php echo esc_attr($fund->id); ?>" data-saved="<?php echo $is_saved ? '1' : '0'; ?>" data-save-label="Save Fund" onclick="kbfSaveFund('<?php echo esc_js($fund->id); ?>', this)">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/<?php echo $is_saved ? 'bookmark-check-fill' : 'bookmark'; ?>.svg" alt="" width="13" height="13" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);"> <span class="kbf-save-label"><?php echo $is_saved ? 'Saved' : 'Save Fund'; ?></span>
            </button>
            <div class="kbf-more-wrap">
              <button class="kbf-btn kbf-btn-secondary" style="font-size:13px;" onclick="kbfToggleMoreMenu(event)">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/three-dots-vertical.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
                More
              </button>
              <div class="kbf-more-menu" id="kbf-more-menu">
                <?php if(!$is_owner): ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShareFundDetail('<?php echo esc_js($fund->share_token); ?>','<?php echo esc_js($fund->title); ?>','<?php echo esc_js(wp_trim_words($fund->description,18)); ?>')">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/share-fill.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
                  Share
                </button>
                <?php else: ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShareFundDetail('<?php echo esc_js($fund->share_token); ?>','<?php echo esc_js($fund->title); ?>','<?php echo esc_js(wp_trim_words($fund->description,18)); ?>')">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/share-fill.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
                  Share
                </button>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfCreatePoster && kbfCreatePoster('<?php echo esc_js($fund->share_token); ?>','<?php echo esc_js($fund->title); ?>')">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/file-earmark-richtext-fill.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
                  Create Poster
                </button>
                <?php endif; ?>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShowModal('kbf-modal-report')">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/flag-fill.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
                  Report Abuse
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
      </div>
      </div>
    </div>

    <!-- Poster Modal -->
    <?php
      $poster_img = '';
      if (!empty($photos) && is_array($photos) && !empty($photos[0])) {
          $poster_img = $photos[0];
      } elseif (!empty($organizer) && !empty($organizer->avatar_url)) {
          $poster_img = $organizer->avatar_url;
      } elseif (defined('BNTM_KBF_URL')) {
          $poster_img = BNTM_KBF_URL . 'assets/branding/logo.png';
      }
      $poster_desc = wp_trim_words(wp_strip_all_tags($fund->description), 30, '...');
    ?>
    <div id="kbf-modal-poster" class="kbf-modal-overlay kbf-poster-modal" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Create a fundraiser poster</h3>
          <button class="kbf-modal-close" onclick="kbfHideModal('kbf-modal-poster')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <div class="kbf-poster-grid">
            <div class="kbf-poster-left">
              <p style="margin:0;color:var(--kbf-slate);font-size:13px;line-height:1.6;">
                The poster will include basic information about the fundraiser, as well as the target amount and a QR code. The poster is perfect for increasing the reach of the fundraiser.
              </p>
              <div class="kbf-form-group">
                <label>Change the title on the poster (optional)</label>
                <input type="text" id="kbf-poster-title-input" maxlength="40" placeholder="Optional">
                <div class="kbf-poster-count" id="kbf-poster-title-count">0/40</div>
              </div>
              <div class="kbf-form-group">
                <label>Change the description on the poster (optional)</label>
                <textarea id="kbf-poster-desc-input" rows="6" maxlength="150" placeholder="Optional"></textarea>
                <div class="kbf-poster-count" id="kbf-poster-desc-count">0/150</div>
              </div>
              <button class="kbf-btn kbf-btn-primary" type="button" onclick="kbfExportPoster && kbfExportPoster()">
                Export PDF
              </button>
            </div>
            <div class="kbf-poster-right" id="kbf-poster-print">
              <div class="kbf-poster-preview">
                <div class="kbf-poster-brand">
                  <div class="kbf-poster-brand-logo">
                    <?php if(defined('BNTM_KBF_URL')): ?>
                      <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="">
                    <?php endif; ?>
                  </div>
                  <div class="kbf-poster-brand-name">fundora</div>
                </div>
                <div class="kbf-poster-cover">
                  <?php if($poster_img): ?>
                    <img src="<?php echo esc_url($poster_img); ?>" alt="">
                  <?php endif; ?>
                </div>
                <div class="kbf-poster-title" id="kbf-poster-title"><?php echo esc_html($fund->title); ?></div>
                <div class="kbf-poster-desc" id="kbf-poster-desc"><?php echo esc_html($poster_desc); ?></div>
              </div>
              <div class="kbf-poster-qr">
                <div class="kbf-poster-note">
                  Scan the QR code with your phone camera or go to the following address
                  <div style="color:#e11d48;word-break:break-all;"><?php echo esc_html($share_url); ?></div>
                </div>
                <div class="kbf-poster-qr-canvas" id="kbf-poster-qr"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>

      <!-- Description + Messages -->
      <div class="kbf-detail-panels">
        <div class="kbf-detail-left">
          <div class="kbf-card kbf-section-description" style="padding:18px;">
            <h3 class="kbf-section-title" style="margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">About This Fund</h3>
            <div style="font-size:14.5px;color:var(--kbf-text-sm);line-height:1.8;"><?php echo nl2br(esc_html(wp_unslash($fund->description))); ?></div>
          </div>
          <div class="kbf-card kbf-section-milestones" style="padding:18px;margin-top:18px;">
            <h3 class="kbf-section-title" style="margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">Milestones &amp; Updates</h3>
            <?php if(!empty($milestones)): ?>
              <div style="display:grid;gap:12px;">
                <?php foreach($milestones as $ms):
                  $ms_title = isset($ms['title']) ? $ms['title'] : '';
                  $ms_body = isset($ms['body']) ? $ms['body'] : '';
                  $ms_date = isset($ms['created_at']) ? $ms['created_at'] : '';
                  $ms_photos = isset($ms['photos']) && is_array($ms['photos']) ? $ms['photos'] : [];
                ?>
                  <div style="border:1px solid var(--kbf-border);border-radius:12px;padding:12px;background:#fff;">
                    <?php if($ms_title !== ''): ?><div style="font-weight:600;color:var(--kbf-navy);margin-bottom:4px;"><?php echo esc_html($ms_title); ?></div><?php endif; ?>
                    <?php if($ms_date): ?><div style="font-size:11.5px;color:var(--kbf-slate);margin-bottom:6px;"><?php echo esc_html(date('M d, Y', strtotime($ms_date))); ?></div><?php endif; ?>
                    <?php if($ms_body !== ''): ?><div style="font-size:13px;color:var(--kbf-text-sm);line-height:1.6;"><?php echo nl2br(esc_html(wp_unslash($ms_body))); ?></div><?php endif; ?>
                    <?php if(!empty($ms_photos)): ?>
                      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                        <?php foreach($ms_photos as $p): ?>
                          <img src="<?php echo esc_url($p); ?>" alt="Milestone photo" style="width:110px;height:82px;object-fit:cover;border-radius:8px;border:1px solid var(--kbf-border);">
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div style="font-size:13px;color:var(--kbf-slate);">No updates yet.</div>
            <?php endif; ?>
          </div>
        </div>
        <div class="kbf-detail-right">
          <div class="kbf-card kbf-section-message" style="padding:18px;">
            <h3 class="kbf-section-title" style="margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">
              Message <span style="background:var(--kbf-green-lt);color:var(--kbf-blue);padding:2px 8px;border-radius:99px;font-size:12px;margin-left:6px;"><?php echo $sponsor_count; ?></span>
            </h3>
            <?php if(!empty($sponsors)): ?>
            <div class="kbf-sponsor-wall">
              <?php foreach($sponsors as $sp):
                $initials = $sp->is_anonymous ? '?' : strtoupper(substr(isset($sp->sponsor_name) ? $sp->sponsor_name : 'A',0,1));
              ?>
              <div class="kbf-sponsor-item">
                <div class="kbf-sponsor-avatar">
                  <?php if($sp->is_anonymous || empty($sp->sponsor_name)): ?>
                    <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person.svg" alt="">
                  <?php else: ?>
                    <?php echo $initials; ?>
                  <?php endif; ?>
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="font-weight:500;font-size:13.5px;color:var(--kbf-text);">
                    <?php echo $sp->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($sp->sponsor_name); ?>
                    <span style="color:var(--kbf-slate);font-weight:400;"> • <?php echo date('M d g:ia',strtotime($sp->created_at)); ?></span>
                  </div>
                  <?php if($sp->message): ?><div class="kbf-sponsor-msg">"<?php echo esc_html($sp->message); ?>"</div><?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:24px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/chat-left-text-fill.svg" alt="" width="32" height="32" style="margin:0 auto 10px;display:block;opacity:.25;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
              <p style="font-size:13px;color:var(--kbf-slate);margin:0;font-weight:600;">No messages yet</p>
              <p style="font-size:12px;color:var(--kbf-slate);margin:4px 0 0;opacity:.7;">Be the first to leave a message.</p>
            </div>
            <?php endif; ?>
          </div>
          <div class="kbf-card kbf-section-description" style="padding:18px;margin-top:18px;">
            <h3 class="kbf-section-title" style="margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">Benefits</h3>
            <?php if(!empty($benefits)): ?>
              <ul class="kbf-benefits-view">
                <?php foreach($benefits as $b):
                  $b_title = isset($b['title']) ? $b['title'] : '';
                  $b_desc  = isset($b['description']) ? $b['description'] : '';
                  $b_amt   = isset($b['amount']) ? $b['amount'] : '';
                ?>
                  <li>
                    <?php if($b_title !== ''): ?><div class="kbf-benefits-title"><?php echo esc_html($b_title); ?></div><?php endif; ?>
                    <?php if($b_amt !== '' && (float)$b_amt > 0): ?><div class="kbf-benefits-amount">&#8369;<?php echo number_format((float)$b_amt, 0); ?></div><?php endif; ?>
                    <?php if($b_desc !== ''): ?><div class="kbf-benefits-desc"><?php echo esc_html($b_desc); ?></div><?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div style="font-size:13px;color:var(--kbf-slate);">No benefits added yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      </div><!-- .kbf-wrap -->
    
    <!-- ================== JS ================== -->
    <script>
   function kbfSyncDetailPanels(){
    if(window.innerWidth <= 760){
        var sticky = document.querySelector('.kbf-detail-sticky');
        if(sticky) sticky.style.height = 'auto';
        return;
    }
    var left = document.querySelector('.kbf-detail-left');
    var sticky = document.querySelector('.kbf-detail-sticky');
    if(!left || !sticky) return;
    sticky.style.height = 'auto';
    var leftH = left.getBoundingClientRect().height;
    sticky.style.height = leftH + 'px';
}
    document.addEventListener('DOMContentLoaded', function(){
        var mainWrap = document.getElementById('kbf-photo-main');
        var mainImg = mainWrap ? mainWrap.querySelector('img') : null;
        var lightbox = document.getElementById('kbf-photo-lightbox');
        var lightImg = document.getElementById('kbf-photo-lightbox-img');
        var closeBtn = document.getElementById('kbf-photo-lightbox-close');
        var prevBtn = document.getElementById('kbf-photo-lightbox-prev');
        var nextBtn = document.getElementById('kbf-photo-lightbox-next');
        var mainPrev = document.getElementById('kbf-photo-prev');
        var mainNext = document.getElementById('kbf-photo-next');
        var countEl = document.getElementById('kbf-photo-count');
        var thumbsWrap = document.getElementById('kbf-photo-thumbs');
        var thumbPrev = document.getElementById('kbf-thumb-prev');
        var thumbNext = document.getElementById('kbf-thumb-next');
        if (!mainWrap || !mainImg || !lightbox || !lightImg) return;

        var thumbs = Array.prototype.slice.call(document.querySelectorAll('.kbf-photo-thumb img'));
        var sources = [];
        var dataPhotos = [];
        if (mainWrap && mainWrap.dataset && mainWrap.dataset.photos) {
            try {
                var parsed = JSON.parse(mainWrap.dataset.photos);
                if (Array.isArray(parsed)) dataPhotos = parsed;
            } catch (e) {}
        }
        var currentIndex = 0;

        function pushSrc(src){
            if (!src) return;
            if (sources.indexOf(src) === -1) sources.push(src);
        }

        function rebuildSources(){
            sources = [];
            thumbs.forEach(function(img){
                pushSrc(img.getAttribute('data-full') || img.getAttribute('src'));
            });
            if (!sources.length && dataPhotos.length) {
                dataPhotos.forEach(function(src){ pushSrc(src); });
            }
            if (!sources.length) pushSrc(mainImg.getAttribute('src'));
        }

        function updateActiveThumb(){
            var activeSrc = mainImg.getAttribute('src');
            var activeBox = null;
            thumbs.forEach(function(img){
                var box = img.closest('.kbf-photo-thumb');
                if (!box) return;
                var src = img.getAttribute('data-full') || img.getAttribute('src');
                if (src === activeSrc) {
                    box.classList.add('is-active');
                    activeBox = box;
                } else {
                    box.classList.remove('is-active');
                }
            });
            if (activeBox && thumbsWrap && activeBox.scrollIntoView) {
                activeBox.scrollIntoView({behavior:'smooth', inline:'center', block:'nearest'});
            }
            if(countEl){
                countEl.textContent = (currentIndex + 1) + '/' + sources.length;
            }
        }

        function setIndexBySrc(src){
            var i = sources.indexOf(src);
            currentIndex = i > -1 ? i : 0;
            updateActiveThumb();
        }

        function swapMainImage(src){
            if (!src) return;
            mainImg.classList.add('is-sliding');
            var onLoad = function(){
                requestAnimationFrame(function(){
                    mainImg.classList.remove('is-sliding');
                });
                mainImg.removeEventListener('load', onLoad);
            };
            mainImg.addEventListener('load', onLoad);
            mainImg.src = src;
        }

        function syncMainByIndex(idx){
            if (!sources.length) return;
            currentIndex = (idx + sources.length) % sources.length;
            swapMainImage(sources[currentIndex]);
            updateActiveThumb();
        }

        rebuildSources();
        setIndexBySrc(mainImg.getAttribute('src'));
        updateActiveThumb();

        thumbs.forEach(function(img){
            img.addEventListener('click', function(){
                var src = img.getAttribute('data-full') || img.getAttribute('src');
                if (src) {
                    swapMainImage(src);
                    setIndexBySrc(src);
                }
            });
        });
        if (thumbPrev) thumbPrev.addEventListener('click', goPrev);
        if (thumbNext) thumbNext.addEventListener('click', goNext);
        if (mainPrev) mainPrev.addEventListener('click', function(e){ e.stopPropagation(); goPrev(); });
        if (mainNext) mainNext.addEventListener('click', function(e){ e.stopPropagation(); goNext(); });

        var autoTimer = null;
        var autoPaused = false;
        function clearAutoTimer(){
            if (!autoTimer) return;
            clearTimeout(autoTimer);
            autoTimer = null;
        }
        function scheduleAuto(){
            if (sources.length < 2 || autoPaused) return;
            clearAutoTimer();
            autoTimer = setTimeout(function(){
                if (autoPaused) return;
                goNext();
                scheduleAuto();
            }, 5000);
        }
        function pauseAuto(){
            autoPaused = true;
            clearAutoTimer();
        }
        function resumeAuto(){
            if (lightbox && lightbox.classList.contains('open')) return;
            autoPaused = false;
            scheduleAuto();
        }
        function resetAuto(){
            if (autoPaused) return;
            scheduleAuto();
        }

        function openLightbox(){
            rebuildSources();
            var src = mainImg.getAttribute('src');
            if (!src) return;
            setIndexBySrc(src);
            lightImg.src = src;
            lightbox.classList.add('open');
            lightbox.setAttribute('aria-hidden','false');
            if (mainWrap) mainWrap.classList.add('is-lightbox-open');
            pauseAuto();
        }
        function closeLightbox(){
            lightbox.classList.remove('open');
            lightbox.setAttribute('aria-hidden','true');
            if (mainWrap) mainWrap.classList.remove('is-lightbox-open');
            resumeAuto();
        }
        function goNext(){
            syncMainByIndex(currentIndex + 1);
            lightImg.src = mainImg.getAttribute('src');
        }
        function goPrev(){
            syncMainByIndex(currentIndex - 1);
            lightImg.src = mainImg.getAttribute('src');
        }
        mainWrap.addEventListener('click', openLightbox);
        mainWrap.addEventListener('mouseenter', function(){ pauseAuto(); });
        mainWrap.addEventListener('mouseleave', function(){ resumeAuto(); });
        if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
        if (prevBtn) prevBtn.addEventListener('click', function(){ goPrev(); resetAuto(); });
        if (nextBtn) nextBtn.addEventListener('click', function(){ goNext(); resetAuto(); });
        lightbox.addEventListener('click', function(e){
            if (e.target === lightbox) closeLightbox();
        });
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') closeLightbox();
            if (!lightbox.classList.contains('open')) return;
            if (e.key === 'ArrowRight') { goNext(); }
            if (e.key === 'ArrowLeft') { goPrev(); }
        });

        scheduleAuto();
        kbfSyncDetailPanels();
        window.addEventListener('resize', kbfSyncDetailPanels);
    });
    function kbfGetActiveSponsorModal(){
        var modals = document.querySelectorAll('#kbf-modal-sponsor');
        for (var i = 0; i < modals.length; i++) {
            var d = window.getComputedStyle(modals[i]).display;
            if (d && d !== 'none') return modals[i];
        }
        return modals[0] || null;
    }
    function kbfGetActiveSponsorForm(){
        if (document.activeElement) {
            var activeModal = document.activeElement.closest('.kbf-modal');
            if (activeModal) {
                var activeForm = activeModal.querySelector('form');
                if (activeForm) return activeForm;
            }
        }
        var modal = kbfGetActiveSponsorModal();
        if (modal) {
            var f = modal.querySelector('form');
            if (f) return f;
        }
        return document.getElementById('kbf-sponsor-form');
    }
    function kbfGetActiveSponsorMsg(){
        var modal = kbfGetActiveSponsorModal();
        if (!modal) return null;
        return modal.querySelector('#kbf-spd-msg') || modal.querySelector('#kbf-sponsor-msg');
    }
    function kbfValidateRequired(form){
        var first = null;
        form.querySelectorAll('.kbf-field-error').forEach(function(el){ el.remove(); });
        form.querySelectorAll('[required]').forEach(function(el){
            if (el.disabled) return;
            if (el.type === 'hidden') return;
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
        if(first){ first.focus(); return false; }
        return true;
    }
    (function(){
        var msg = document.getElementById('kbf-sponsor-message');
        var msgCount = document.getElementById('kbf-sponsor-message-count');
        if(!msg || !msgCount) return;
        function updateMsgCount(){
            msgCount.textContent = (msg.value ? msg.value.length : 0) + '/300';
        }
        updateMsgCount();
        msg.addEventListener('input', updateMsgCount);
    })();
    window.kbfSpdSponsor=function(nonce){
        const form=kbfGetActiveSponsorForm();
        const modal=kbfGetActiveSponsorModal();
        const btn=modal ? modal.querySelector('.kbf-modal-footer .kbf-btn-primary') : document.querySelector('#kbf-modal-sponsor .kbf-modal-footer .kbf-btn-primary');
        const msg=kbfGetActiveSponsorMsg();
        if(!kbfValidateRequired(form)) return;
        const amountEl = form.querySelector('input[name="amount"]');
        const maxVal = amountEl && amountEl.max ? parseFloat(amountEl.max) : null;
        const amt = amountEl ? parseFloat(amountEl.value || '0') : 0;
        if (maxVal && amt > maxVal) {
            msg.innerHTML = '<div class="kbf-alert kbf-alert-error">You cannot give more than ?' + maxVal.toLocaleString() + ' for this fund.</div>';
            return;
        }
        kbfSetBtnLoading(btn,true,'Processing...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);
        fd.append('action', 'kbf_create_checkout');
        fd.append('nonce',nonce);
        fd.append('is_anonymous',document.getElementById('spd-anon').checked?'1':'0');
        console.log('KBF sponsor submit (fund details): sending', {
            fund_id: fd.get('fund_id'),
            amount: fd.get('amount'),
            email: fd.get('email'),
            payment_method: fd.get('payment_method'),
            is_anonymous: fd.get('is_anonymous')
        });
        kbfFetchJson(ajaxurl, fd, (j)=>{
            console.log('KBF checkout response (fund details):', j);
            if(j.success){
            if(j.data && j.data.checkout_url){
                btn.innerHTML='Redirecting to payment...';
                var w = window.open(j.data.checkout_url, '_blank', 'noopener');
                if (w) { try { w.opener = null; } catch(e) {} }
                if (window.kbfAwaitPaymentSuccess) window.kbfAwaitPaymentSuccess();
            } else {
                msg.innerHTML='<div class="kbf-alert kbf-alert-error">Maya checkout URL was not returned. Please check your Maya API keys and try again.</div>';
                kbfSetBtnLoading(btn,false);
                kbfSetSkeleton(msg,false);
            }
            } else {
                msg.innerHTML='<div class="kbf-alert kbf-alert-error">'+j.data.message+'</div>';
                kbfSetBtnLoading(btn,false);
                kbfSetSkeleton(msg,false);
            }
        }, (err)=>{
            console.error('KBF checkout error:', err);
            msg.innerHTML='<div class="kbf-alert kbf-alert-error">'+err+'</div>';
            kbfSetBtnLoading(btn,false);
            kbfSetSkeleton(msg,false);
        });
    };
    window.kbfSpdReport=function(nonce){
        const form=document.getElementById('kbf-report-form');
        const btn=document.querySelector('#kbf-modal-report .kbf-modal-footer .kbf-btn-danger');
        const msg=document.getElementById('kbf-rpt-msg');
        kbfSetBtnLoading(btn,true,'Submitting...');
        kbfSetSkeleton(msg,true);
        kbfSetLoadingPage(true);
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            msg.innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
        if(j.success){
            kbfHideModal('kbf-modal-report');
            if(form) form.reset();
        } else {
                kbfSetBtnLoading(btn,false); 
                kbfSetSkeleton(msg,false);
            }
            kbfSetLoadingPage(false);
        }).catch(()=>{ 
            kbfSetBtnLoading(btn,false); 
            kbfSetSkeleton(msg,false);
            kbfSetLoadingPage(false);
        });
    };
    window.kbfCreatePoster = function(token, title){
        var modal = document.getElementById('kbf-modal-poster');
        if (!modal) return;
        kbfShowModal(modal);
        kbfPosterSync();
        kbfPosterRenderQr();
    };
    window.kbfExportPoster = function(){
        var target = document.getElementById('kbf-poster-print');
        if (!target) return;
        if (!window.html2canvas || !window.jspdf) {
            alert('Export libraries not loaded yet. Please try again.');
            return;
        }
        kbfPosterRenderQr();
        var btn = document.querySelector('#kbf-modal-poster .kbf-btn-primary');
        var old = btn ? btn.textContent : '';
        if (btn) { btn.disabled = true; btn.textContent = 'Exporting...'; }
        setTimeout(function(){
            html2canvas(target, {scale:2, backgroundColor:'#ffffff', useCORS:true, allowTaint:true}).then(function(canvas){
                var imgData = canvas.toDataURL('image/png');
                var pdf = new window.jspdf.jsPDF('p','pt','a4');
                var pageW = pdf.internal.pageSize.getWidth();
                var pageH = pdf.internal.pageSize.getHeight();
                var imgW = pageW - 60;
                var imgH = canvas.height * (imgW / canvas.width);
                var y = (pageH - imgH) / 2;
                pdf.addImage(imgData, 'PNG', 30, Math.max(30,y), imgW, imgH);
                var blobUrl = pdf.output('bloburl');
                window.open(blobUrl, '_blank');
            }).catch(function(){
                alert('Failed to export. Please try again.');
            }).finally(function(){
                if (btn) { btn.disabled = false; btn.textContent = old; }
            });
        }, 150);
    };
    function kbfPosterSync(){
        var titleInput = document.getElementById('kbf-poster-title-input');
        var descInput = document.getElementById('kbf-poster-desc-input');
        var titleOut = document.getElementById('kbf-poster-title');
        var descOut = document.getElementById('kbf-poster-desc');
        var tCount = document.getElementById('kbf-poster-title-count');
        var dCount = document.getElementById('kbf-poster-desc-count');
        if (!titleInput || !descInput || !titleOut || !descOut) return;
        var tVal = (titleInput.value || '').trim().slice(0,40);
        var dVal = (descInput.value || '').trim().slice(0,150);
        titleOut.textContent = tVal || <?php echo json_encode($fund->title); ?>;
        descOut.textContent = dVal || <?php echo json_encode($poster_desc); ?>;
        if (tCount) tCount.textContent = (titleInput.value || '').slice(0,40).length + '/40';
        if (dCount) dCount.textContent = (descInput.value || '').slice(0,150).length + '/150';
    }
    document.addEventListener('input', function(e){
        if (e.target && (e.target.id === 'kbf-poster-title-input' || e.target.id === 'kbf-poster-desc-input')) {
            kbfPosterSync();
        }
    });
    function kbfPosterRenderQr(){
        var holder = document.getElementById('kbf-poster-qr');
        if (!holder || !window.QRCode) return;
        holder.innerHTML = '';
        new QRCode(holder, {
            text: <?php echo json_encode($share_url); ?>,
            width: 78,
            height: 78,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
        // Convert canvas to img for reliable export
        var canvas = holder.querySelector('canvas');
        if (canvas) {
            try {
                var img = document.createElement('img');
                img.alt = 'QR Code';
                img.src = canvas.toDataURL('image/png');
                holder.innerHTML = '';
                holder.appendChild(img);
            } catch(e) {}
        }
    }
    (function(){
        function loadScript(src, cb){
            if (document.querySelector('script[src="'+src+'"]')) { cb && cb(); return; }
            var s = document.createElement('script');
            s.src = src; s.async = true; s.onload = cb;
            document.head.appendChild(s);
        }
        loadScript('https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js');
        loadScript('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js', function(){
            if (window.jspdf && window.jspdf.jsPDF) return;
        });
        loadScript('https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js', function(){
            kbfPosterRenderQr();
        });
    })();
    var _kbfRating=5;
    window.kbfSetRating=function(v){
        _kbfRating=v;
        document.getElementById('kbf-rating-val').value=v;
        document.querySelectorAll('.kbf-star-btn').forEach((s,i)=>{
            const filled = i < v;
            const src = filled ? s.getAttribute('data-filled') : s.getAttribute('data-empty');
            s.src = src;
            s.style.filter = filled
                ? 'invert(32%) sepia(58%) saturate(1621%) hue-rotate(202deg) brightness(94%) contrast(92%)'
                : 'invert(79%) sepia(10%) saturate(383%) hue-rotate(183deg) brightness(96%) contrast(90%)';
        });
    };
    kbfSetRating(5);
    window.kbfSubmitRating=function(nonce){
        const form=document.getElementById('kbf-rating-form');
        const btn=document.querySelector('#kbf-modal-rating .kbf-modal-footer .kbf-btn-primary');
        btn.disabled=true;btn.textContent='Submitting...';
        const fd=new FormData(form);fd.append('action','kbf_submit_rating');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
        document.getElementById('kbf-rate-msg').innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
        if(j.success)setTimeout(()=>{kbfHideModal('kbf-modal-rating');},1800);else{btn.disabled=false;btn.textContent='Submit Score';}
        });
    };
    window.kbfToggleMoreMenu=function(e){
        if(e) e.stopPropagation();
        var menu = document.getElementById('kbf-more-menu');
        if(!menu) return;
        menu.classList.toggle('open');
    };
    document.addEventListener('click', function(){
        var menu = document.getElementById('kbf-more-menu');
        if(menu) menu.classList.remove('open');
    });
    var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
    var kbfSaveNonce = '<?php echo esc_js($nonce_save); ?>';
    window.kbfSaveFund=function(id, btn){
        if(!id) return;
        var el = btn || document.querySelector('.kbf-save-btn[data-fund-id="' + id + '"]');
        var fd = new FormData();
        fd.append('action','kbf_toggle_save_fund');
        fd.append('nonce', kbfSaveNonce);
        fd.append('fund_id', id);
        if(typeof kbfFetchJson === 'undefined'){ alert('Save failed.'); return; }
        kbfFetchJson(ajaxurl, fd, function(j){
            if(j && j.success){
                var saved = !!(j.data && j.data.saved);
                if(el){
                    el.classList.toggle('is-saved', saved);
                    el.setAttribute('data-saved', saved ? '1' : '0');
                    var img = el.querySelector('img');
                    if(img){ img.src = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/' + (saved ? 'bookmark-check-fill' : 'bookmark') + '.svg'; }
                    var label = el.querySelector('.kbf-save-label');
                    if(label){ label.textContent = saved ? 'Saved' : (el.getAttribute('data-save-label') || 'Save Fund'); }
                }
            } else {
                alert((j && j.data && j.data.message) ? j.data.message : 'Unable to save.');
            }
        }, function(err){ alert(err || 'Request failed.'); });
    };
    function initLeaderboardPager(){
        var list = document.querySelector('[data-kbf-leaderboard-list]');
        var pager = document.querySelector('[data-kbf-leaderboard-pager]');
        if(!list || !pager) return;
        var items = Array.prototype.slice.call(list.querySelectorAll('.kbf-leaderboard-item'));
        if(items.length === 0) { pager.style.display = 'none'; return; }
        pager.innerHTML = '' +
          '<div class="kbf-table-pager-left">Show ' +
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
        function render(){
            var total = items.length;
            var pages = Math.max(1, Math.ceil(total / perPage));
            if(page > pages) page = pages;
            var start = (page - 1) * perPage;
            var end = start + perPage;
            items.forEach(function(item, i){
                item.style.display = (i >= start && i < end) ? '' : 'none';
            });
            pageLabel.textContent = page + ' / ' + pages;
            prevBtn.disabled = page <= 1;
            nextBtn.disabled = page >= pages;
            pager.style.display = total > perPage ? 'flex' : 'flex';
        }
        select.addEventListener('change', function(){
            perPage = parseInt(this.value, 10) || 5;
            page = 1;
            render();
        });
        prevBtn.addEventListener('click', function(){ if(page > 1){ page--; render(); } });
        nextBtn.addEventListener('click', function(){ if(page < Math.ceil(items.length / perPage)){ page++; render(); } });
        render();
    }
    document.addEventListener('DOMContentLoaded', function(){
        initLeaderboardPager();
    });
    console.log('kbf fund id', <?php echo wp_json_encode($fund->id ?? null); ?>);
    console.log('kbf fund token', <?php echo wp_json_encode($fund->fund_token ?? null); ?>);
    console.log('kbf milestones raw', <?php echo wp_json_encode($fund->milestones ?? null); ?>);
    console.log('kbf milestones parsed', <?php echo wp_json_encode($milestones); ?>);
    </script>
    <?php
    $c=ob_get_clean();
    if (!empty($_GET['kbf_tab']) && $_GET['kbf_tab'] === 'fund_details') {
        return $c;
    }
    return bntm_universal_container('Fund Details -- KonekBayan',$c, ['show_topbar'=>false,'show_header'=>false]);
}










