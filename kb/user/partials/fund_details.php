<?php
/* Fund details shortcode */
if (!function_exists('kbf_fund_details_load_fund')) {
    /**
     * @function  kbf_fund_details_load_fund
     * @purpose   Loads a fund record by token, ID, or share token based on incoming request parameters.
     * @used-by   bntm_shortcode_kbf_fund_details
     * @calls     sanitize_text_field, intval, $wpdb->prepare, $wpdb->get_row
     * @params    object $wpdb - WordPress database object
     * @params    string $ft - Funds table name
     * @params    int $current_user_id - Current logged-in user ID for owner visibility checks
     * @returns   object|null - Fund row object when found, otherwise null
     * @status    ACTIVE
     */
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
    /**
     * @function  kbf_fund_details_share_url
     * @purpose   Builds the shareable fund URL using the fund share token.
     * @used-by   bntm_shortcode_kbf_fund_details
     * @calls     add_query_arg
     * @params    string $fund_details_url - Base fund details page URL
     * @params    object $fund - Fund object containing share_token
     * @returns   string - Share URL with kbf_share query parameter
     * @status    ACTIVE
     */
    function kbf_fund_details_share_url($fund_details_url, $fund) {
        return add_query_arg('kbf_share', $fund->share_token, $fund_details_url);
    }
}

if (!function_exists('kbf_fund_details_output_social_meta')) {
    /**
     * @function  kbf_fund_details_output_social_meta
     * @purpose   Outputs Open Graph/Twitter metadata early in <head> for share links so social previews include fund photo.
     * @used-by   wp_head action hook
     * @calls     get_current_user_id, sanitize_text_field, intval, $wpdb->prepare, $wpdb->get_row, json_decode, wp_strip_all_tags, wp_trim_words, esc_attr, esc_url
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function kbf_fund_details_output_social_meta() {
        if (is_admin()) {
            return;
        }
        if (empty($_GET['kbf_share']) && empty($_GET['fund']) && empty($_GET['fund_id'])) {
            return;
        }
        if (!function_exists('kbf_get_page_url')) {
            return;
        }

        global $wpdb;
        $ft = $wpdb->prefix . 'kbf_funds';
        $current_user_id = get_current_user_id();
        $fund = null;

        if (!empty($_GET['kbf_share'])) {
            $token = sanitize_text_field(wp_unslash($_GET['kbf_share']));
            $fund = $wpdb->get_row($wpdb->prepare(
                "SELECT f.*,u.display_name as organizer_name,u.user_login as organizer_login FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.share_token=%s AND (f.status IN ('active','completed') OR f.business_id=%d) LIMIT 1",
                $token,
                (int)$current_user_id
            ));
        } elseif (!empty($_GET['fund'])) {
            $token = sanitize_text_field(wp_unslash($_GET['fund']));
            $fund = $wpdb->get_row($wpdb->prepare(
                "SELECT f.*,u.display_name as organizer_name,u.user_login as organizer_login FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.fund_token=%s AND (f.status IN ('active','completed') OR f.business_id=%d) LIMIT 1",
                $token,
                (int)$current_user_id
            ));
        } elseif (!empty($_GET['fund_id'])) {
            $fid = intval($_GET['fund_id']);
            $fund = $wpdb->get_row($wpdb->prepare(
                "SELECT f.*,u.display_name as organizer_name,u.user_login as organizer_login FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.id=%d AND (f.status IN ('active','completed') OR f.business_id=%d) LIMIT 1",
                $fid,
                (int)$current_user_id
            ));
        }

        if (empty($fund) || empty($fund->share_token)) {
            return;
        }

        $photos = [];
        if (!empty($fund->photos)) {
            $decoded = json_decode((string)$fund->photos, true);
            if (is_array($decoded)) {
                $photos = $decoded;
            }
        }
        $og_img = '';
        if (!empty($photos)) {
            $og_img = (string)$photos[0];
        }
        if (!$og_img && defined('BNTM_KBF_URL')) {
            $og_img = BNTM_KBF_URL . 'assets/branding/logo.png';
        }

        $fund_details_url = kbf_get_page_url('fund_details');
        $share_url = add_query_arg('kbf_share', $fund->share_token, $fund_details_url);
        $og_title = wp_strip_all_tags((string)$fund->title);
        $og_desc = wp_trim_words(wp_strip_all_tags((string)$fund->description), 28, '...');

        echo "\n<meta property=\"og:type\" content=\"article\" />";
        echo "\n<meta property=\"og:title\" content=\"" . esc_attr($og_title) . "\" />";
        echo "\n<meta property=\"og:description\" content=\"" . esc_attr($og_desc) . "\" />";
        echo "\n<meta property=\"og:url\" content=\"" . esc_url($share_url) . "\" />";
        if (!empty($og_img)) {
            echo "\n<meta property=\"og:image\" content=\"" . esc_url($og_img) . "\" />";
            echo "\n<meta property=\"og:image:secure_url\" content=\"" . esc_url($og_img) . "\" />";
        }
        echo "\n<meta name=\"twitter:card\" content=\"summary_large_image\" />";
        echo "\n<meta name=\"twitter:title\" content=\"" . esc_attr($og_title) . "\" />";
        echo "\n<meta name=\"twitter:description\" content=\"" . esc_attr($og_desc) . "\" />";
        if (!empty($og_img)) {
            echo "\n<meta name=\"twitter:image\" content=\"" . esc_url($og_img) . "\" />\n";
        }
    }
    add_action('wp_head', 'kbf_fund_details_output_social_meta', 1);
}

/**
 * @function  bntm_shortcode_kbf_fund_details
 * @purpose   Renders the full fund details page UI, sponsor/report/rating modals, and interactive scripts.
 * @used-by   modules/kb/includes/shortcodes.php shortcode map, modules/kb/user/partials/dashboard/sections.php
 * @calls     kbf_global_assets, kbf_fund_details_load_fund, kbf_fund_details_share_url, kbf_get_page_url, get_current_user_id, wp_create_nonce, WordPress DB query methods
 * @params    none
 * @returns   string - Rendered fund details HTML content
 * @status    ACTIVE
 */
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
    $at = $wpdb->prefix.'kbf_appeals';
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $pct = $fund->goal_amount > 0 ? min(100, round(($fund->raised_amount / $fund->goal_amount) * 100)) 
    : 0;    $sponsors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$st} WHERE fund_id=%d AND payment_status='completed' AND message IS NOT NULL AND message != '' ORDER BY created_at DESC LIMIT 20",$fund->id));
    $sponsor_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed' AND is_anonymous=0",$fund->id));
    // Leaderboard: non-anonymous grouped by name, each anonymous donation as separate row
    $leaderboard = $wpdb->get_results($wpdb->prepare(
        "SELECT
            CASE WHEN is_anonymous=1 THEN 'Anonymous' ELSE COALESCE(NULLIF(sponsor_name,''),'Anonymous') END AS display_name,
            is_anonymous,
            SUM(amount) AS total_given,
            COUNT(*) AS num_donations,
            MAX(created_at) AS last_donated
         FROM {$st}
         WHERE fund_id=%d AND payment_status='completed'
         GROUP BY
             is_anonymous,
             CASE WHEN is_anonymous=0 THEN COALESCE(NULLIF(sponsor_name,''),'Anonymous') ELSE id END
         ORDER BY total_given DESC
         LIMIT 10",
        $fund->id
    ));
    $organizer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$fund->business_id));
    $latest_appeal = $wpdb->get_row($wpdb->prepare("SELECT status,admin_notes,message FROM {$at} WHERE fund_id=%d ORDER BY created_at DESC, id DESC LIMIT 1", $fund->id));
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
        ? kbf_get_organizer_profile_url($fund->business_id)
        : kbf_get_page_url('organizer_profile');
    $demo_mode  = (bool)kbf_get_setting('kbf_demo_mode',true);
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $nonce_rating  = wp_create_nonce('kbf_rating');
    $nonce_save   = wp_create_nonce('kbf_save_fund');
    $is_saved = false;
    $funder_type_raw = isset($fund->funder_type) ? (string)$fund->funder_type : '';
    if ($funder_type_raw === '') {
        $funder_type_raw = 'yourself';
    }
    $funder_type_label = ucwords(str_replace('_', ' ', $funder_type_raw));
    if($current_user_id){
        $sf = $wpdb->prefix.'kbf_saved_funds';
        $is_saved = (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$sf} WHERE user_id=%d AND fund_id=%d", $current_user_id, $fund->id));
    }
    // ===== RULE 3A: CHECK IF USER ALREADY RATED =====
    $rt = $wpdb->prefix.'kbf_ratings';
    $current_user_email = $current_user_id ? wp_get_current_user()->user_email : '';
    $already_rated = false;
    if ($current_user_id && $current_user_email && $fund->business_id) {
        $already_rated = (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$rt} WHERE organizer_id=%d AND sponsor_email=%s",
            (int)$fund->business_id,
            $current_user_email
        ));
    }
    $is_self = $current_user_id && $current_user_id === (int)$fund->business_id;
    $prefill_email = $current_user_id ? wp_get_current_user()->user_email : '';
    $prefill_phone = $current_user_id ? sanitize_text_field((string)get_user_meta($current_user_id, 'kbf_phone', true)) : '';
    $payment_result = isset($_GET['kbf_payment']) ? sanitize_text_field($_GET['kbf_payment']) : '';
    $payment_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
    $payment_ref = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    if ($demo_mode && $payment_result === 'success' && $payment_sid > 0 && $prefill_email !== '' && function_exists('kbf_maya_sync_sponsorship_from_checkout')) {
        kbf_maya_sync_sponsorship_from_checkout($payment_sid, $prefill_email);
    }
    if ($demo_mode && $payment_result === 'success' && $prefill_email !== '' && function_exists('kbf_mark_sponsorship_completed')) {
        $demo_return_sponsorship = null;
        if ($payment_sid > 0) {
            $demo_return_sponsorship = $wpdb->get_row($wpdb->prepare(
                "SELECT id,payment_status FROM {$st} WHERE id=%d AND email=%s",
                $payment_sid,
                $prefill_email
            ));
        } elseif ($payment_ref !== '') {
            $demo_return_sponsorship = $wpdb->get_row($wpdb->prepare(
                "SELECT id,payment_status FROM {$st} WHERE rand_id=%s AND email=%s",
                $payment_ref,
                $prefill_email
            ));
        }
        if ($demo_return_sponsorship && $demo_return_sponsorship->payment_status !== 'completed') {
            kbf_mark_sponsorship_completed((int)$demo_return_sponsorship->id, $payment_ref);
        }
    }
    $show_payment_banner = in_array($payment_result, ['success', 'failed', 'cancelled'], true) && $current_user_id;
    $payment_banner_data = null;
    if ($show_payment_banner) {
        $pay_sponsorship = null;
        if ($demo_mode && $payment_sid > 0 && $prefill_email !== '') {
            $pay_sponsorship = $wpdb->get_row($wpdb->prepare(
                "SELECT id,amount,payment_status FROM {$st} WHERE id=%d AND email=%s",
                $payment_sid,
                $prefill_email
            ));
        } elseif ($demo_mode && $payment_ref !== '' && $prefill_email !== '') {
            $pay_sponsorship = $wpdb->get_row($wpdb->prepare(
                "SELECT id,amount,payment_status FROM {$st} WHERE rand_id=%s AND email=%s",
                $payment_ref,
                $prefill_email
            ));
        } elseif ($payment_sid > 0 && $prefill_email !== '') {
            $pay_sponsorship = $wpdb->get_row($wpdb->prepare(
                "SELECT id,amount,payment_status FROM {$st} WHERE id=%d AND email=%s",
                $payment_sid,
                $prefill_email
            ));
        } elseif ($payment_ref !== '' && $prefill_email !== '') {
            $pay_sponsorship = $wpdb->get_row($wpdb->prepare(
                "SELECT id,amount,payment_status FROM {$st} WHERE rand_id=%s AND email=%s",
                $payment_ref,
                $prefill_email
            ));
        }
        $pay_amount = $pay_sponsorship ? number_format((float)$pay_sponsorship->amount, 2) : '';
        $explore_url = add_query_arg('kbf_tab', 'find_funds', kbf_get_page_url('dashboard'));
        if ($payment_result === 'success' && $pay_sponsorship && $pay_sponsorship->payment_status === 'completed') {
            $payment_banner_data = [
                'type' => 'success',
                'icon' => 'ph-fill ph-check-circle',
                'title' => 'Thank You!',
                'message' => $pay_amount !== ''
                    ? 'Your &#8369;' . $pay_amount . ' sponsorship for <strong>' . esc_html($fund->title) . '</strong> was received successfully.'
                    : 'Your sponsorship for <strong>' . esc_html($fund->title) . '</strong> was received successfully.',
                'countdown' => 15,
                'redirect' => $explore_url,
                'btn_text' => 'Go to Explore',
            ];
        } elseif ($payment_result === 'success') {
            $payment_banner_data = [
                'type' => 'warning',
                'icon' => 'ph-fill ph-clock-countdown',
                'title' => 'Payment Processing',
                'message' => 'Your payment return was received, but confirmation is still being finalized. Please refresh in a moment if this status does not update.',
                'countdown' => 15,
                'redirect' => $explore_url,
                'btn_text' => 'Go to Explore',
            ];
        } elseif ($payment_result === 'failed') {
            $payment_banner_data = [
                'type' => 'error',
                'icon' => 'ph-fill ph-x-circle',
                'title' => 'Payment Failed',
                'message' => 'We could not process your payment. You can try sponsoring again.',
                'countdown' => 15,
                'redirect' => $explore_url,
                'btn_text' => 'Go to Explore',
            ];
        } elseif ($payment_result === 'cancelled') {
            $payment_banner_data = [
                'type' => 'warning',
                'icon' => 'ph-fill ph-warning',
                'title' => 'Payment Cancelled',
                'message' => 'Your payment was cancelled. You can sponsor again anytime.',
                'countdown' => 15,
                'redirect' => $explore_url,
                'btn_text' => 'Go to Explore',
            ];
        }
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
            /**
             * @function  kbf_fund_details_wp_head_meta_callback (anonymous closure)
             * @purpose   Outputs Open Graph and Twitter meta tags for the current fund share page.
             * @used-by   [wp_head action hook]
             * @calls     [esc_attr, esc_url]
             * @params    [none]
             * @returns   [void]
             * @status    ACTIVE
             */
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
    .kbf-detail-wrap{max-width:1200px;margin:0 auto;padding:30px 16px 0;}
    .kbf-wrap{
      padding:0 !important;
      margin-top:0 !important;
    }
    .kbf-photo-main img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }
    .kbf-detail-title{
      font-size:28px;
      font-weight:700;
      color:var(--kbf-navy);
      margin:0 0 8px;
      line-height:1.25;
      letter-spacing:-0.2px;
    }
    .kbf-fund-header{margin-bottom:20px;}
    .kbf-fund-header-top{display:flex;align-items:center;gap:12px;flex-wrap:wrap; padding-top: 10px;}
    .kbf-fund-organizer-link{text-decoration:none !important;}
    .kbf-fund-organizer-avatar{
      position:relative;width:44px;height:44px;flex-shrink:0;cursor:pointer;
      transition:transform .2s ease;border-radius:50%;
    }
    .kbf-fund-organizer-avatar:hover{transform:scale(1.06);}
    .kbf-fund-organizer-avatar img{
      width:44px;height:44px;border-radius:50%;object-fit:cover;display:block;border:2px solid var(--kbf-border);
    }
    .kbf-fund-organizer-avatar-placeholder{
      width:44px;height:44px;border-radius:50%;background:var(--kbf-navy);
      display:flex;align-items:center;justify-content:center;border:2px solid var(--kbf-border);
    }
    .kbf-fund-organizer-avatar-placeholder i{font-size:20px;color:#fff;}
    .kbf-fund-verified-badge{
      position:absolute;
        right:-2px;
        bottom:-2px;
        width:16px;
        height:16px;
        border-radius:50%;
        background:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        box-shadow:0 0 0 1px #fff;
        color:#1d4ed8;
        z-index:2;
    }
    .kbf-fund-verified-badge i{font-size:11px;line-height:1;color:inherit;}
    .kbf-fund-header-info{flex:1;min-width:0;}
    .kbf-fund-organizer-name-row{
      display:flex;align-items:baseline;gap:6px;margin-bottom:4px;flex-wrap:wrap;
    }
    .kbf-fund-organizer-label{font-size:11.5px;color:var(--kbf-slate);font-weight:500;}
    .kbf-fund-header-tags{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
    .kbf-fund-tag{
      display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;
      font-size:11px;font-weight:500;color:var(--kbf-slate);background:#f1f5f9;
    }
    .kbf-fund-tag i{font-size:12px;opacity:.7;}
    .kbf-fund-tag-type{background:#eef4ff;color:#1e40af;font-weight:600;}
    .kbf-fund-header-rating{display:flex;align-items:center;gap:6px;margin-top:4px;}
    .kbf-fund-rating-pill{
      display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;
      font-size:12px;font-weight:700;color:#3b82f6;background:#e0eaff;
    }
    .kbf-fund-rating-pill i{font-size:12px;color:#3b82f6;}
    .kbf-fund-rating-empty{background:#f1f5f9;color:var(--kbf-slate);}
    .kbf-fund-rating-label{font-size:11.5px;color:var(--kbf-slate);font-weight:500;}
    .kbf-category-pill img{
      width:10px;
      height:10px;
      flex-shrink:0;
    }
    .kbf-btn.kbf-btn-primary .kbf-icon,
    .kbf-btn.kbf-btn-primary i,
    .kbf-btn.kbf-btn-primary svg{
      color:#ffffff !important;
      fill:#ffffff !important;
      stroke:#ffffff !important;
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
    .kbf-detail-right .kbf-card:hover{
      transform:none !important;
      box-shadow:none !important;
      border-color:var(--kbf-border) !important;
    }
    .kbf-detail-layout{display:flex;gap:28px;align-items:stretch;flex-wrap:wrap;min-width:0;}
  .kbf-detail-panels{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,1fr);gap:28px;width:100%;min-width:0;}
.kbf-detail-left{display:flex;flex-direction:column;justify-content:flex-start;min-height:0;width:100%;max-width:none;}
.kbf-detail-right{display:flex;flex-direction:column;align-self:stretch;width:100%;max-width:none;}
    @media (max-width: 1100px){
        .kbf-detail-panels{grid-template-columns:minmax(0,1.2fr) minmax(300px,1fr);gap:22px;}
    }
  .kbf-detail-tabs{
      display:flex;
      flex-direction:column;
      gap:14px;
      width:100%;
      align-self:stretch;
  }
  .kbf-detail-tab-list{
      display:flex;
      gap:22px;
      align-items:center;
      border-bottom:1px solid var(--kbf-border);
      padding-bottom:8px;
      flex-wrap:wrap;
      width:100%;
  }
  .kbf-detail-tab{
      background:none;
      border:none;
      padding:8px 12px;
      font-size:14px;
      font-weight:600;
      color:var(--kbf-slate);
      cursor:pointer;
      position:relative;
      border-radius:8px 8px 0 0;
      transition:background .15s ease, color .15s ease;
  }
  .kbf-detail-tab:hover{
      background:#f8fafc;
      color:var(--kbf-navy);
  }
  .kbf-detail-tab::after{
      content:'';
      position:absolute;
      left:0;
      bottom:-9px;
      width:0;
      height:3px;
      background:var(--kbf-blue);
      border-radius:99px;
      transition:width .2s ease;
  }
  .kbf-detail-tab.is-active{
      background:#eef4ff;
      color:var(--kbf-blue);
  }
  .kbf-detail-tab.is-active::after{
      width:100%;
  }
  .kbf-detail-tab-panel{display:none;}
  .kbf-detail-tab-panel.is-active{display:block;}
  .kbf-detail-tab-panels{width:100%;}
  .kbf-detail-tab-panel{width:100%;}
  .kbf-detail-tab-panel .kbf-card{width:100%; box-sizing:border-box;}
  .kbf-detail-secondary{width:100%;}
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
      .kbf-account-profile-text{display:none;}
    }
    @media (max-width: 900px){
      .kbf-account-header-row .kbf-section-title{order:1;}
      .kbf-account-header-actions{order:2;}
    }
    .kbf-save-btn{
        transition:none;
    }
    .kbf-save-btn i{
      transition:none;
    }
    .kbf-save-btn.is-loading{
      position:relative;
      pointer-events:none;
      opacity:.8;
    }
    .kbf-save-btn.is-loading .kbf-icon,
    .kbf-save-btn.is-loading .kbf-save-label{
      opacity:0;
    }
    .kbf-save-btn.is-loading::after{
      content:'';
      position:absolute;
      top:50%;
      left:50%;
      width:14px;
      height:14px;
      transform:translate(-50%,-50%);
      border-radius:50%;
      border:2px solid rgba(59,130,246,0.25);
      border-top-color:#3b82f6;
      animation:kbfSaveBtnSpin .7s linear infinite;
    }
    @keyframes kbfSaveBtnSpin{
      from{transform:translate(-50%,-50%) rotate(0deg);}
      to{transform:translate(-50%,-50%) rotate(360deg);}
    }
        .kbf-save-btn.is-saved{
      background:#e7f1ff;
      border-color:#bfd7ff;
      color:#1d4ed8;
    }
    .kbf-save-btn.is-saved i{
      color:#3b82f6;
    }
    /* ===== LEADERBOARD + PROGRESS CARDS ===== */
    .kbf-detail-sticky{
      display:flex;
      flex-direction:column;
      gap:14px;
      min-height:0;
      flex:1;
    }
    .kbf-section-leaderboard,
    .kbf-leaderboard-card{
      display:flex;
      flex-direction:column;
      min-height:0;
      margin:0;
      flex:1 1 auto;
    }
    .kbf-leaderboard-head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      padding:0 0 12px;
      margin-bottom:0;
      border-bottom:1px solid var(--kbf-border);
      flex-shrink:0;
    }
    .kbf-leaderboard-body{
      flex:1 1 auto;
      display:flex;
      flex-direction:column;
      min-height:0;
      overflow-y:auto;
      overflow-x:hidden;
      padding-right:6px;
      margin-top:12px;
    }
    /* Empty state fills card and centers */
    .kbf-leaderboard-empty{
      flex:1 1 auto;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      padding:24px 10px;
      text-align:center;
      min-height:260px;
    }
    .kbf-leaderboard-empty i{
      font-size:28px;
      margin-bottom:8px;
      opacity:.3;
    }
    .kbf-leaderboard-empty p{
      margin:2px 0;
    }
    .kbf-leaderboard-title{display:flex;align-items:center;gap:10px;min-width:0;}
    .kbf-leaderboard-icon{width:28px;height:28px;border-radius:8px;background:#eef4ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .kbf-leaderboard-icon i{font-size:14px;color:#3b82f6;}
    .kbf-leaderboard-text{font-size:14px;font-weight:500;color:var(--kbf-navy);line-height:1;}
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
    .kbf-photo-gallery{display:flex;flex-direction:column;gap:12px;margin-bottom:22px;width:100%;}
    .kbf-photo-main{width:100%;height:auto;border-radius:16px;overflow:hidden;border:1px solid var(--kbf-border);background:#f1f5f9;position:relative;cursor:pointer;aspect-ratio:16 / 10;}
    .kbf-photo-slides{position:relative;width:100%;height:100%;overflow:hidden;}
    .kbf-photo-slide{position:absolute;inset:0;opacity:0;transition:opacity .45s ease, transform .45s ease;transform:scale(1.03);z-index:1;}
    .kbf-photo-slide.is-active{opacity:1;transform:scale(1);z-index:2;}
    .kbf-photo-slide img{width:100%;height:100%;object-fit:cover;display:block;}
    .kbf-photo-nav{
        position:absolute;top:50%;transform:translateY(-50%);width:40px;height:40px;border-radius:50%;border:0;
        background:rgba(255,255,255,.9);box-shadow:0 4px 16px rgba(15,23,42,.15);
        display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;opacity:.85;transition:opacity .2s, transform .2s;
    }
    .kbf-photo-nav:hover{opacity:1;transform:translateY(-50%) scale(1.08);}
    .kbf-photo-nav i{font-size:18px;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);}
    .kbf-photo-prev{left:12px;}
    .kbf-photo-next{right:12px;}
    .kbf-photo-dots{
        position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:6px;z-index:10;padding:6px 10px;
        background:rgba(15,23,42,.35);border-radius:99px;backdrop-filter:blur(4px);
    }
    .kbf-photo-dot{
        width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.45);cursor:pointer;transition:all .25s ease;flex-shrink:0;
    }
    .kbf-photo-dot.is-active{background:#fff;width:20px;border-radius:4px;}
    .kbf-photo-dot:hover{background:rgba(255,255,255,.8);}
    .kbf-photo-progress-bar{
        position:absolute;bottom:0;left:0;right:0;height:3px;background:rgba(15,23,42,.15);z-index:10;border-radius:0 0 16px 16px;overflow:hidden;
    }
    .kbf-photo-progress-fill{display:block;height:100%;width:0;background:linear-gradient(90deg,#60a5fa,#3b82f6);border-radius:0 0 16px 16px;transition:none;}
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
        border-radius:12px;
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
    .kbf-org-verified{
        position:absolute;right:-2px;bottom:-2px;width:18px;height:18px;border-radius:50%;
        background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 1px #fff;
        color:#1d4ed8;
    }
    .kbf-org-verified i{font-size:13px;}
.kbf-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--kbf-slate);margin-bottom:20px;padding-top:30px;}
    .kbf-breadcrumb a{color:var(--kbf-blue);text-decoration:none;font-weight:600;}
    .kbf-breadcrumb a:hover{text-decoration:none;}
    .kbf-more-wrap{position:relative;z-index:50;}
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
        z-index:50;
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
        overflow:visible !important;
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
        cursor:pointer;
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
        transform:none;
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
        .kbf-detail-left,.kbf-photo-gallery{max-width:100%;}
        .kbf-detail-right{order:0;}
        .kbf-section-photo{order:1;}
        .kbf-fund-header{order:2;}
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

    /* ===== IMPROVEMENTS APPLIED =====
       Fix 1: Organizer card moved to sidebar
       Fix 2: Tabs moved above messages
       Fix 3: Progress bar styling enhanced
       Fix 4: Sidebar made sticky
       Fix 5: Empty leaderboard height collapsed
       Fix 6: Sponsor button label updated
       Fix 7: Pills refined
    ===== END ===== */

    /* ===== FIX 3: PROGRESS BAR ENHANCED ===== */
    .kbf-detail-right .kbf-progress-wrap{
      height:14px !important;
      background:#e8f0fe !important;
      border-radius:999px;
    }
    .kbf-detail-right .kbf-progress-bar{
      height:14px !important;
      border-radius:999px;
      background:linear-gradient(90deg, #5ba8f5 0%, #3d8ef0 50%, #2070e0 100%) !important;
    }

    /* ===== FIX 4: SIDEBAR ===== */
    .kbf-detail-right{
      display:flex;
      flex-direction:column;
      overflow:visible !important;
      z-index:10;
    }
    .kbf-detail-secondary{
      position:relative;
      z-index:1;
    }
    .kbf-detail-sticky{overflow:visible !important;}
    .kbf-detail-right .kbf-card{
      box-shadow:none !important;
      overflow:visible !important;
      position:relative !important;
      z-index:1 !important;
    }
    .kbf-detail-right .kbf-fund-cta-card{
      position:relative !important;
      z-index:300 !important;
      pointer-events:auto !important;
      isolation:isolate;
    }
    .kbf-detail-right .kbf-fund-cta-card .kbf-card-actions{
      position:relative;
      z-index:301;
      pointer-events:auto !important;
    }
    .kbf-detail-right .kbf-fund-cta-card .kbf-btn{
      position:relative;
      z-index:302;
      pointer-events:auto !important;
    }
    .kbf-detail-right .kbf-card-actions{overflow:visible !important; position:relative; z-index:10;}
    .kbf-detail-right .kbf-more-wrap{
      position:relative;
      z-index:200;
      flex-shrink:0;
      pointer-events:auto !important;
    }
    .kbf-detail-right .kbf-more-wrap.open{
      z-index:1000;
    }
    .kbf-detail-right .kbf-more-wrap .kbf-btn{
      position:relative;
      z-index:201;
      pointer-events:auto !important;
      cursor:pointer !important;
      user-select:none;
      touch-action:manipulation;
      transform:translateZ(0);
      background-clip:padding-box;
    }
    .kbf-detail-right .kbf-more-wrap .kbf-btn i{
      pointer-events:none;
    }

    /* ===== FIX 9: COMPACT LEADERBOARD ITEM ===== */
    .kbf-leaderboard-item{
      display:flex;
      align-items:center;
      gap:8px;
      padding:6px 0;
      border-bottom:1px solid var(--kbf-border);
    }
    .kbf-leaderboard-item:last-child{border-bottom:none;}
    .kbf-lb-rank{
      font-size:11px;
      font-weight:700;
      color:#92400e;
      background:#fef3c7;
      border-radius:4px;
      padding:2px 6px;
      min-width:24px;
      text-align:center;
      flex-shrink:0;
      line-height:1.2;
    }
    .kbf-lb-name{
      flex:1;
      min-width:0;
      font-size:12.5px;
      font-weight:500;
      color:var(--kbf-navy);
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .kbf-lb-amount{
      font-size:12px;
      font-weight:700;
      color:var(--kbf-blue);
      flex-shrink:0;
    }
    /* Payment result banner (Fund Details) */
    .kbf-payment-banner{
      position:relative;background:#fff;border:none;border-radius:18px;
      box-shadow:0 1px 3px rgba(0,0,0,.06),0 8px 24px rgba(0,0,0,.08);
      margin:72px 0 16px;overflow:hidden;
      max-height:260px;
      opacity:1;
      transform:translateY(0);
      transition:opacity .28s ease, transform .28s ease, max-height .32s ease, margin .32s ease;
    }
    .kbf-payment-banner.is-closing{
      opacity:0;
      transform:translateY(-8px);
      max-height:0;
      margin-top:0;
      margin-bottom:0;
      pointer-events:none;
    }
    .kbf-payment-banner-inner{display:flex;align-items:center;gap:14px;padding:18px 20px;}
    .kbf-payment-banner-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
    .kbf-payment-banner-success .kbf-payment-banner-icon{background:#dcfce7;color:#166534;}
    .kbf-payment-banner-error .kbf-payment-banner-icon{background:#fee2e2;color:#991b1b;}
    .kbf-payment-banner-warning .kbf-payment-banner-icon{background:#fef3c7;color:#92400e;}
    .kbf-payment-banner-content{flex:1;min-width:0;}
    .kbf-payment-banner-title{margin:0 0 3px;font-size:28px;font-weight:800;color:#0f172a;line-height:1.1}
    .kbf-payment-banner-message{margin:0;font-size:14px;color:#475569;line-height:1.45}
    .kbf-payment-banner-countdown{display:inline-flex;align-items:center;gap:7px;margin-top:8px;padding:5px 10px;border-radius:999px;background:#f8fafc;color:#64748b;font-size:12px;font-weight:600}
    .kbf-payment-banner-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}
    .kbf-payment-banner-progress{height:3px;background:#e2e8f0}
    .kbf-payment-banner-progress-bar{height:100%;width:0;transition:width .1s linear}
    .kbf-payment-banner-success .kbf-payment-banner-progress-bar{background:#22c55e}
    .kbf-payment-banner-error .kbf-payment-banner-progress-bar{background:#ef4444}
    .kbf-payment-banner-warning .kbf-payment-banner-progress-bar{background:#f59e0b}
    @media (max-width: 900px){
      .kbf-payment-banner{margin-top:52px}
    }
    @media (max-width: 768px){
      .kbf-payment-banner-inner{flex-direction:column;align-items:flex-start}
      .kbf-payment-banner-actions{width:100%}
      .kbf-payment-banner-actions .kbf-btn{flex:1;justify-content:center}
      .kbf-payment-banner-title{font-size:24px}
      .kbf-payment-banner{margin-top:26px}
    }

    /* ===== FIX 7: PILL STYLING ===== */
    .kbf-category-pill{
      display:inline-flex;
      align-items:center;
      gap:5px;
      line-height:1.1;
      border-radius:999px;
      box-sizing:border-box;
      background:#eef4ff;
      color:#1e40af;
      border:1px solid #c7d8f7;
      font-weight:700;
      font-size:10.5px;
      padding:4px 10px;
    }
    .kbf-fundtype-pill{
      display:inline-flex;
      align-items:center;
      line-height:1.1;
      border-radius:999px;
      box-sizing:border-box;
      background:#f1f5f9;
      color:#475569;
      border:1px solid #e2e8f0;
      font-weight:600;
      font-size:10.5px;
      padding:4px 10px;
    }
 </style>
    <!-- ================== HTML ================== -->
    <div class="kbf-wrap">
    <?php if ($payment_banner_data): ?>
      <div id="kbf-payment-banner" class="kbf-payment-banner kbf-payment-banner-<?php echo esc_attr($payment_banner_data['type']); ?>">
        <div class="kbf-payment-banner-inner">
          <div class="kbf-payment-banner-icon"><i class="<?php echo esc_attr($payment_banner_data['icon']); ?>" aria-hidden="true"></i></div>
          <div class="kbf-payment-banner-content">
            <h4 class="kbf-payment-banner-title"><?php echo esc_html($payment_banner_data['title']); ?></h4>
            <p class="kbf-payment-banner-message"><?php echo $payment_banner_data['message']; ?></p>
            <div class="kbf-payment-banner-countdown">
              <i class="ph ph-clock" aria-hidden="true"></i>
              <span>Staying on this page in <strong id="kbf-payment-countdown"><?php echo (int)$payment_banner_data['countdown']; ?></strong>s</span>
            </div>
          </div>
          <div class="kbf-payment-banner-actions">
            <a href="<?php echo esc_url($payment_banner_data['redirect']); ?>" class="kbf-btn kbf-btn-primary" id="kbf-payment-btn-go"><?php echo esc_html($payment_banner_data['btn_text']); ?></a>
            <button type="button" class="kbf-btn kbf-btn-secondary" onclick="kbfDismissPaymentBanner()">Stay on fundraiser</button>
          </div>
        </div>
        <div class="kbf-payment-banner-progress"><div class="kbf-payment-banner-progress-bar" id="kbf-payment-progress"></div></div>
      </div>
      <script>
        (function(){
          var totalSeconds = <?php echo (int)$payment_banner_data['countdown']; ?>;
          var redirectUrl = <?php echo wp_json_encode($payment_banner_data['redirect']); ?>;
          var countdownEl = document.getElementById('kbf-payment-countdown');
          var progressEl = document.getElementById('kbf-payment-progress');
          var startTime = Date.now();
          var timer = null;
          function tick(){
            var elapsed = (Date.now() - startTime) / 1000;
            var remaining = Math.max(0, totalSeconds - elapsed);
            var pct = Math.min(100, (elapsed / totalSeconds) * 100);
            if(countdownEl) countdownEl.textContent = Math.ceil(remaining);
            if(progressEl) progressEl.style.width = pct + '%';
            if(remaining <= 0){
              clearInterval(timer);
              window.kbfDismissPaymentBanner();
            }
          }
          window.kbfDismissPaymentBanner = function(){
            clearInterval(timer);
            var banner = document.getElementById('kbf-payment-banner');
            if (banner) {
              banner.classList.add('is-closing');
              setTimeout(function(){ banner.remove(); }, 320);
            }
            try {
              var url = new URL(window.location.href);
              url.searchParams.delete('kbf_payment');
              url.searchParams.delete('sid');
              url.searchParams.delete('ref');
              window.history.replaceState({}, '', url.toString());
            } catch(e){}
          };
          timer = setInterval(tick, 100);
          var goBtn = document.getElementById('kbf-payment-btn-go');
          if(goBtn){
            goBtn.addEventListener('click', function(){ clearInterval(timer); });
          }
        })();
      </script>
    <?php endif; ?>

    <!-- Sponsor Modal -->
    <div id="kbf-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header"><h3>Sponsor "<?php echo esc_html(wp_trim_words($fund->title,6)); ?>"</h3><button class="kbf-modal-close" onclick="kbfHideModal('kbf-modal-sponsor')">&times;</button></div>
        <div class="kbf-modal-body">
          <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:12px 16px;margin-bottom:18px;display:flex;justify-content:space-between;font-size:13px;">
            <span><span style="color:var(--kbf-blue);" class="kbf-strong">&#8369;<?php echo number_format($fund->raised_amount,2); ?></span> raised</span>
            <span style="color:var(--kbf-slate);"><?php echo round($pct); ?>% of &#8369;<?php echo number_format($fund->goal_amount,2); ?> goal</span>
          </div>
          <form id="kbf-sponsor-form" onsubmit="return false;">
            <input type="hidden" name="fund_id" value="<?php echo esc_attr((int) $fund->id); ?>">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Account</label><input type="text" name="sponsor_name" id="spd-name" placeholder="Your name, company, or account"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;"><label class="kbf-checkbox-row"><input type="checkbox" id="spd-anon" onchange="document.getElementById('spd-name').disabled=this.checked"> Sponsor Anonymously</label></div>
            </div>
            <div class="kbf-form-group">
              <label>Amount (PHP) *</label>
              <input type="text" name="amount_display" id="kbf-sponsor-amount-display" placeholder="Min. &#8369;50" inputmode="numeric" autocomplete="off" required>
              <input type="hidden" name="amount" id="kbf-sponsor-amount" min="50" step="1">
              <div class="kbf-meta" style="margin-top:4px;">Minimum sponsorship: &#8369;50.00</div>
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
              <div class="kbf-form-group"><label>Email (for receipt) *</label><input type="email" name="email" placeholder="your@email.com" value="<?php echo esc_attr($prefill_email); ?>" required<?php echo $current_user_id ? ' readonly style="background:#f8fafc;color:var(--kbf-slate);"' : ''; ?>></div>
              <div class="kbf-form-group"><label>Phone *</label><input type="text" name="phone" id="kbf-sponsor-phone" placeholder="09XX XXX XXXX" value="<?php echo esc_attr($prefill_phone); ?>" required></div>
            </div>
            <input type="hidden" name="payment_method" value="online_payment">
            <div id="kbf-spd-msg" style="margin-top:10px;"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfHideModal('kbf-modal-sponsor')">Cancel</button>
          <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSpdSponsor('<?php echo esc_js($nonce_sponsor); ?>')">
            <i class="ph-fill ph-heart kbf-icon" style="font-size:14px;color:#ffffff;" aria-hidden="true"></i>
            Confirm Sponsorship
          </button>
        </div>
      </div>
    </div>

    <!-- Report Modal -->
    <div id="kbf-modal-report" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Report This Fund</h3><button class="kbf-modal-close" onclick="var m=document.getElementById('kbf-modal-report');if(m){m.classList.remove('is-open');m.style.display='none';}">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-report-form">
            <input type="hidden" name="fund_id" value="<?php echo esc_attr((int) $fund->id); ?>">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email"></div>
            <div class="kbf-form-group"><label>Upload Photo (optional)</label><input type="file" name="report_image" accept="image/*"></div>
            <div class="kbf-form-group"><label>Reason *</label><select name="reason" required><option value="">Select</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Info</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select></div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" required></textarea></div>
            <div id="kbf-rpt-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="var m=document.getElementById('kbf-modal-report');if(m){m.classList.remove('is-open');m.style.display='none';}">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbfSpdReport('<?php echo esc_js($nonce_report); ?>')">Submit Report</button>
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
          <button class="kbf-modal-close" onclick="kbfHideModal('kbf-modal-rating')">&times;</button>
          <p style="font-size:12.5px;color:var(--kbf-slate);margin:2px 0 0;">Rate this organizer's trustworthiness. You can only submit once.</p>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-rating-form">
            <input type="hidden" name="organizer_id" value="<?php echo esc_attr((int) $fund->business_id); ?>">
            <input type="hidden" name="fund_id" value="<?php echo esc_attr((int) $fund->id); ?>">
            <div class="kbf-form-group"><label>Credibility</label>
              <div id="kbf-star-picker" style="display:flex;gap:8px;margin-top:6px;">
                <?php for($i=1;$i<=5;$i++): ?>
                  <i class="kbf-star-btn ph ph-thumbs-up kbf-icon" data-val="<?php echo $i; ?>" data-filled="ph-fill ph-thumbs-up" data-empty="ph ph-thumbs-up" style="cursor:pointer;font-size:32px;color:#94a3b8;" onclick="kbfSetRating(<?php echo $i; ?>)" aria-hidden="true"></i>
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
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfHideModal('kbf-modal-rating')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitRating('<?php echo esc_js($nonce_rating); ?>')">Submit Score</button>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Poster Modal -->
    <div id="kbf-modal-poster" class="kbf-modal-overlay kbf-poster-modal" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header"><h3>Create Campaign Poster</h3><button class="kbf-modal-close" onclick="kbfHideModal('kbf-modal-poster')">&times;</button></div>
        <div class="kbf-modal-body">
          <div class="kbf-poster-grid">
            <div class="kbf-poster-left">
              <div class="kbf-poster-preview" id="kbf-poster-print" style="border:1px solid var(--kbf-border);border-radius:12px;padding:18px;background:#fff;">
                <div class="kbf-poster-brand">
                  <div class="kbf-poster-brand-logo">
                    <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="Fundora">
                  </div>
                  <span class="kbf-poster-brand-name">fundora</span>
                </div>
                <?php if(!empty($photos)): ?>
                  <div class="kbf-poster-cover">
                    <img src="<?php echo esc_url($photos[0]); ?>" alt="<?php echo esc_attr($fund->title); ?>">
                  </div>
                <?php endif; ?>
                <div class="kbf-poster-title" id="kbf-poster-title"><?php echo esc_html($fund->title); ?></div>
                <div class="kbf-poster-desc" id="kbf-poster-desc"><?php echo esc_html($poster_desc); ?></div>
                <div class="kbf-poster-qr">
                  <div class="kbf-poster-note">Scan to support this campaign</div>
                  <div class="kbf-poster-qr-canvas" id="kbf-poster-qr"></div>
                </div>
              </div>
            </div>
            <div class="kbf-poster-right">
              <div class="kbf-form-group">
                <label>Campaign Title</label>
                <input type="text" id="kbf-poster-title-input" value="<?php echo esc_attr($fund->title); ?>" maxlength="40">
                <div class="kbf-poster-count" id="kbf-poster-title-count"><?php echo strlen($fund->title); ?>/40</div>
              </div>
              <div class="kbf-form-group">
                <label>Description</label>
                <textarea id="kbf-poster-desc-input" rows="4" maxlength="150"><?php echo esc_html(wp_strip_all_tags($fund->description)); ?></textarea>
                <div class="kbf-poster-count" id="kbf-poster-desc-count">0/150</div>
              </div>
              <div style="font-size:12px;color:var(--kbf-slate);margin-top:8px;line-height:1.5;">
                Customize your poster text, then export as PDF to share on social media or print.
              </div>
            </div>
          </div>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfHideModal('kbf-modal-poster')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfExportPoster()">
            <i class="ph ph-download-simple kbf-icon" style="font-size:14px;color:#fff;" aria-hidden="true"></i>
            Export PDF
          </button>
        </div>
      </div>
    </div>

    <!-- Breadcrumb -->
    <div class="kbf-breadcrumb">
      <a href="<?php echo esc_url($browse_url); ?>" style="display:inline-flex;align-items:center;gap:6px;">
        <i class="ph ph-arrow-left kbf-icon" style="font-size:14px;color:inherit" aria-hidden="true"></i>
        Back to Browse
      </a>
    </div>

    <?php if($fund->status==='pending' && $is_owner): ?>
    <div class="kbf-alert kbf-alert-warning" style="margin-bottom:20px;"><span class="kbf-strong">Under Review:</span> This fund is not yet visible to sponsors. Once approved it goes live.</div>
    <?php elseif($fund->status==='suspended'): ?>
    <div class="kbf-alert kbf-alert-error" style="margin-bottom:20px;">
      <?php if($latest_appeal && $latest_appeal->status === 'open'): ?>
        <span class="kbf-strong">Appeal Submitted:</span> Your appeal is under admin review. We'll notify you once a decision is made.
      <?php elseif($latest_appeal && $latest_appeal->status === 'rejected'): ?>
        <span class="kbf-strong">Appeal Rejected:</span>
        <?php
          $appeal_note = !empty($latest_appeal->admin_notes) ? $latest_appeal->admin_notes : '';
          $fallback_note = !empty($fund->admin_notes) ? $fund->admin_notes : '';
          echo esc_html($appeal_note !== '' ? $appeal_note : ($fallback_note !== '' ? $fallback_note : 'Your fund remains suspended. Contact support.'));
        ?>
      <?php else: ?>
        <span class="kbf-strong">Suspended:</span> <?php echo esc_html($fund->admin_notes?:'Contact support.'); ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="kbf-detail-layout">
      <div class="kbf-detail-panels">
      <!-- LEFT: Main content -->
      <div class="kbf-detail-left">
        <!-- Photo gallery -->
        <?php if(!empty($photos)): ?>
        <div class="kbf-photo-gallery kbf-section-photo">
          <div class="kbf-photo-main" id="kbf-photo-main" data-photos='<?php echo esc_attr(wp_json_encode(array_values($photos))); ?>' data-current="0">
            <div class="kbf-photo-slides">
              <?php foreach($photos as $idx => $ph): ?>
                <div class="kbf-photo-slide<?php echo $idx===0?' is-active':''; ?>" data-index="<?php echo $idx; ?>">
                  <img src="<?php echo esc_url($ph); ?>" alt="<?php echo esc_attr($fund->title); ?> photo <?php echo $idx+1; ?>" loading="<?php echo $idx===0?'eager':'lazy'; ?>">
                </div>
              <?php endforeach; ?>
            </div>
            <?php if(count($photos)>1): ?>
              <button type="button" class="kbf-photo-nav kbf-photo-prev" aria-label="Previous photo">
                <i class="ph ph-caret-left kbf-icon" aria-hidden="true"></i>
              </button>
              <button type="button" class="kbf-photo-nav kbf-photo-next" aria-label="Next photo">
                <i class="ph ph-caret-right kbf-icon" aria-hidden="true"></i>
              </button>
              <div class="kbf-photo-dots">
                <?php foreach($photos as $idx => $ph): ?>
                  <span class="kbf-photo-dot<?php echo $idx===0?' is-active':''; ?>" data-index="<?php echo $idx; ?>" role="button" aria-label="Photo <?php echo $idx+1; ?>"></span>
                <?php endforeach; ?>
              </div>
              <div class="kbf-photo-progress-bar"><span class="kbf-photo-progress-fill"></span></div>
            <?php endif; ?>
          </div>
        </div>
        <?php else: ?>
        <div class="kbf-photo-gallery kbf-section-photo">
          <div class="kbf-photo-main" id="kbf-photo-main" style="height:300px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--kbf-navy) 0%,var(--kbf-navy-light) 100%);">
            <i class="ph-fill ph-heart kbf-icon" style="font-size:64px; opacity:.25;filter:invert(100%)" aria-hidden="true"></i>
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
        <div class="kbf-fund-header">
          <div class="kbf-fund-header-tags" style="margin-top:10px;">
            <span class="kbf-fund-tag">
              <i class="ph ph-tag kbf-icon"></i>
              <?php echo esc_html(ucfirst(strtolower((string)$fund->category))); ?>
            </span>
            <span class="kbf-fund-tag kbf-fund-tag-type"><?php echo esc_html($funder_type_label); ?></span>
          </div>
          <h1 class="kbf-detail-title" style="margin:4px 0 0;"><?php echo esc_html($fund->title); ?></h1>
          <div class="kbf-fund-header-top">
            <a href="<?php echo esc_url($profile_url); ?>" class="kbf-fund-organizer-link" style="text-decoration:none;" <?php if(!$current_user_id): ?>onclick="event.preventDefault();if(window.kbfOpenAuthModal){window.kbfOpenAuthModal('Sign in to view account details.');}else{window.location.href='<?php echo esc_js(kbf_get_page_url('signin')); ?>';}"<?php endif; ?>>
              <div class="kbf-fund-organizer-avatar">
                <?php if($organizer && !empty($organizer->avatar_url)): ?>
                  <img src="<?php echo esc_url($organizer->avatar_url); ?>" alt="<?php echo esc_attr($fund->organizer_name); ?>">
                <?php else: ?>
                  <div class="kbf-fund-organizer-avatar-placeholder">
                    <i class="ph ph-user kbf-icon"></i>
                  </div>
                <?php endif; ?>
                <?php if($organizer && $organizer->is_verified): ?>
                  <span class="kbf-fund-verified-badge" aria-label="Verified"><i class="ph-fill ph-seal-check kbf-icon"></i></span>
                <?php endif; ?>
              </div>
            </a>
            <div class="kbf-fund-header-info">
              <div class="kbf-fund-organizer-name-row">
                <a href="<?php echo esc_url($profile_url); ?>" style="color:var(--kbf-blue);text-decoration:none;font-weight:600;font-size:14px;" <?php if(!$current_user_id): ?>onclick="event.preventDefault();if(window.kbfOpenAuthModal){window.kbfOpenAuthModal('Sign in to view account details.');}else{window.location.href='<?php echo esc_js(kbf_get_page_url('signin')); ?>';}"<?php endif; ?>>
                  <?php echo esc_html($fund->organizer_name); ?>
                </a>
              </div>
              <div class="kbf-fund-header-rating">
                <?php $cred_score = $organizer && isset($organizer->rating) ? (float)$organizer->rating : 0; ?>
                <?php if($cred_score > 0): ?>
                  <span class="kbf-fund-rating-pill">
                    <i class="ph-fill ph-thumbs-up kbf-icon"></i>
                    <?php echo number_format($cred_score, 1); ?>
                  </span>
                <?php else: ?>
                  <span class="kbf-fund-rating-pill kbf-fund-rating-empty">
                    <i class="ph-fill ph-thumbs-up kbf-icon"></i>
                    Not rated yet
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

</div> <!-- .kbf-detail-left -->
      <div class="kbf-detail-right">
        <div class="kbf-detail-sticky">
          <div class="kbf-card kbf-section-leaderboard kbf-leaderboard-card" style="padding:18px;">
            <div class="kbf-leaderboard-head">
              <div class="kbf-leaderboard-title">
                <div>
                  <div class="kbf-leaderboard-text">Top Sponsors</div>
                  <div class="kbf-leaderboard-sub">Recent supporters</div>
                </div>
              </div>
            </div>
            <?php if(!empty($leaderboard)): ?>
              <div class="kbf-leaderboard-body" data-kbf-leaderboard-list>
                <?php $pos=0; foreach($leaderboard as $row): $pos++; ?>
                  <div class="kbf-leaderboard-item">
                    <span class="kbf-lb-rank">#<?php echo $pos; ?></span>
                    <span class="kbf-lb-name">
                      <?php if ($row->is_anonymous): ?>
                      <?php endif; ?>
                      <?php echo esc_html($row->display_name); ?>
                    </span>
                    <span class="kbf-lb-amount">&#8369;<?php echo number_format((float)$row->total_given, 0); ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="kbf-table-pager kbf-table-pager-inline" data-kbf-leaderboard-pager></div>
            <?php else: ?>
              <div class="kbf-leaderboard-empty">
                <i class="ph-fill ph-heart kbf-icon" style="font-size:32px; margin:0 auto 10px;display:block;opacity:.25;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                <p style="font-size:13px;color:var(--kbf-slate);margin:0;font-weight:600;">No sponsors yet</p>
                <p style="font-size:12px;color:var(--kbf-slate);margin:4px 0 0;opacity:.7;">Be the first to support!</p>
              </div>
            <?php endif; ?>
          </div>

          <div class="kbf-card kbf-fund-cta-card" style="padding:18px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px;">
              <div style="font-size:20px;font-weight:700;color:var(--kbf-blue);">&#8369;<?php echo number_format((float)$fund->raised_amount, 2); ?></div>
              <div style="font-size:12px;color:var(--kbf-slate);">of &#8369;<?php echo number_format((float)$fund->goal_amount, 2); ?></div>
            </div>
            <div class="kbf-progress-wrap" style="height:10px;margin-bottom:10px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%;height:10px;"></div></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center;">
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div style="font-weight:700;font-size:12px;color:var(--kbf-navy);"><?php echo $pct; ?>%</div>
                <div style="font-size:10px;color:var(--kbf-slate);">FUNDED</div>
              </div>
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div style="font-weight:700;font-size:12px;color:var(--kbf-navy);"><?php echo $sponsor_count; ?></div>
                <div style="font-size:10px;color:var(--kbf-slate);">SPONSORS</div>
              </div>
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div style="font-weight:700;font-size:12px;color:var(--kbf-navy);"><?php echo $days !== null ? $days : '--'; ?></div>
                <div style="font-size:10px;color:var(--kbf-slate);">DAYS LEFT</div>
              </div>
            </div>
            <div style="margin-top:14px;">
              <button class="kbf-btn kbf-btn-primary" style="width:100%;font-weight:600;" onclick="kbfShowModal('kbf-modal-sponsor')">
                <i class="ph-fill ph-heart kbf-icon" style="font-size:16px;color:#ffffff;" aria-hidden="true"></i>
                Sponsor This Campaign
              </button>
              <div class="kbf-card-actions" style="display:flex;gap:10px;margin-top:10px;">
                <button class="kbf-btn kbf-btn-secondary kbf-save-btn" type="button" data-fund-id="<?php echo (int)$fund->id; ?>" data-saved="<?php echo $is_saved ? '1' : '0'; ?>" data-save-label="Save Fund" onclick="kbfSaveFund('<?php echo (int)$fund->id; ?>', this)" style="pointer-events:auto !important; cursor:pointer !important; position:relative; z-index:302; touch-action:manipulation;">
                  <i class="<?php echo $is_saved ? 'ph-fill ph-bookmark-simple' : 'ph ph-bookmark-simple'; ?> kbf-icon" style="font-size:13px;color:var(--kbf-text-sm);" aria-hidden="true"></i>
                  <span class="kbf-save-label"><?php echo $is_saved ? 'Saved' : 'Save Fund'; ?></span>
                </button>
                <div class="kbf-more-wrap">
                  <button class="kbf-btn kbf-btn-secondary" type="button" onclick="kbfToggleMoreMenu(event)" style="pointer-events:auto !important; cursor:pointer !important; position:relative; z-index:201; touch-action:manipulation;">
                    <i class="ph ph-dots-three-vertical kbf-icon" style="font-size:12px; filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                    More
                  </button>
                  <div class="kbf-more-menu" id="kbf-more-menu">
                    <button type="button" onclick="kbfShareFundDetail('<?php echo esc_js($fund->share_token); ?>','<?php echo esc_js($fund->title); ?>','<?php echo esc_js(wp_trim_words($fund->description,18)); ?>')">Share</button>
                    <?php if($is_self): ?>
                      <button type="button" onclick="kbfCreatePoster('<?php echo esc_js($org_token ?: $fund->business_id); ?>','<?php echo esc_js($fund->title); ?>')">Create Poster</button>
                    <?php else: ?>
                      <?php if(!$current_user_id): ?>
                        <button type="button" onclick="if(window.kbfOpenAuthModal){window.kbfOpenAuthModal('Sign in to report abuse.');}else{window.location.href='<?php echo esc_js(kbf_get_page_url('signin')); ?>';}" data-tooltip="Sign in to report abuse">
                          Report Abuse
                        </button>
                      <?php else: ?>
                        <button type="button" onclick="var m=document.getElementById('kbf-modal-report');if(m){m.style.display='flex';m.classList.add('is-open');}">Report Abuse</button>
                      <?php endif; ?>
                      <?php if($already_rated): ?>
                        <button type="button" disabled style="opacity:0.7;cursor:not-allowed;" data-tooltip="You have already rated this organizer">
                          <i class="ph-fill ph-thumbs-up kbf-icon" style="font-size:13px;color:#3b82f6;" aria-hidden="true"></i>
                          Score Submitted
                        </button>
                      <?php elseif(!$current_user_id): ?>
                        <button type="button" onclick="if(window.kbfOpenAuthModal){window.kbfOpenAuthModal('Sign in to rate this organizer.');}else{window.location.href='<?php echo esc_js(kbf_get_page_url('signin')); ?>';}" data-tooltip="Sign in to rate this organizer">
                          <i class="ph ph-thumbs-up kbf-icon" style="font-size:13px;" aria-hidden="true"></i>
                          Credibility Score
                        </button>
                      <?php else: ?>
                        <button type="button" onclick="kbfShowModal('kbf-modal-rating')">Credibility Score</button>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div style="font-size:11.5px;color:var(--kbf-slate);margin-top:10px;display:flex;align-items:center;gap:5px;justify-content:center;">
                <i class="ph ph-check-circle kbf-icon" style="font-size:13px;color:#22c55e;" aria-hidden="true"></i>
                Sponsors get a receipt instantly after checkout.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <div class="kbf-detail-secondary">
    <!-- Tabs (full width) -->
    <div class="kbf-detail-tabs" style="margin-top:18px;">
      <div class="kbf-detail-tab-list" role="tablist" aria-label="Fund details tabs">
        <button class="kbf-detail-tab is-active" type="button" data-kbf-tab="desc" role="tab" aria-selected="true">Description</button>
        <button class="kbf-detail-tab" type="button" data-kbf-tab="milestones" role="tab" aria-selected="false">Stories</button>
        <button class="kbf-detail-tab" type="button" data-kbf-tab="benefits" role="tab" aria-selected="false">Rewards</button>
      </div>
      <div class="kbf-detail-tab-panels">
        <div class="kbf-detail-tab-panel is-active" data-kbf-panel="desc" role="tabpanel">
          <div class="kbf-card kbf-section-description" style="padding:18px;">
            <h3 class="kbf-section-title" style="margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">About This Fund</h3>
            <div style="font-size:14.5px;color:var(--kbf-text-sm);line-height:1.8;"><?php echo nl2br(esc_html(wp_unslash($fund->description))); ?></div>
          </div>
        </div>
        <div class="kbf-detail-tab-panel" data-kbf-panel="milestones" role="tabpanel">
          <div class="kbf-card kbf-section-milestones" style="padding:18px;">
            <h3 class="kbf-section-title" style="margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">Stories &amp; Updates</h3>
            <?php if(!empty($milestones)): ?>
              <div style="display:grid;gap:12px;">
                <?php foreach($milestones as $ms):
                  $ms_title = isset($ms['title']) ? $ms['title'] : '';
                  $ms_body = isset($ms['body']) ? $ms['body'] : '';
                  $ms_date = isset($ms['created_at']) ? $ms['created_at'] : '';
                  $ms_photos = isset($ms['photos']) ? $ms['photos'] : [];
                  if (is_string($ms_photos)) {
                    $decoded = json_decode($ms_photos, true);
                    $ms_photos = is_array($decoded) ? $decoded : [];
                  }
                  if (!is_array($ms_photos)) $ms_photos = [];
                ?>
                  <div style="border:1px solid var(--kbf-border);border-radius:12px;padding:12px;background:#fff;">
                    <?php if($ms_title !== ''): ?><div style="font-weight:600;color:var(--kbf-navy);margin-bottom:4px;"><?php echo esc_html($ms_title); ?></div><?php endif; ?>
                    <?php if($ms_date): ?><div style="font-size:11.5px;color:var(--kbf-slate);margin-bottom:6px;"><?php echo esc_html(date('M d, Y', strtotime($ms_date))); ?></div><?php endif; ?>
                    <?php if($ms_body !== ''): ?><div style="font-size:13px;color:var(--kbf-text-sm);line-height:1.6;"><?php echo nl2br(esc_html(wp_unslash($ms_body))); ?></div><?php endif; ?>
                    <?php if(!empty($ms_photos)): ?>
                      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                        <?php foreach($ms_photos as $p):
                          $url = '';
                          if (is_array($p) && isset($p['url'])) {
                            $url = $p['url'];
                          } elseif (is_string($p)) {
                            $url = $p;
                          }
                          $url = $url ? wp_unslash($url) : '';
                          if (!$url) continue;
                        ?>
                          <img src="<?php echo esc_url($url); ?>" alt="Story photo" style="width:110px;height:82px;object-fit:cover;border-radius:8px;border:1px solid var(--kbf-border);">
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div style="text-align:center;padding:24px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <i class="ph ph-article kbf-icon" style="font-size:32px; margin:0 auto 10px;display:block;opacity:.25;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                <p style="font-size:13px;color:var(--kbf-slate);margin:0;font-weight:600;">No updates yet</p>
                <p style="font-size:12px;color:var(--kbf-slate);margin:4px 0 0;opacity:.7;">The organizer hasn't posted any stories yet.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="kbf-detail-tab-panel" data-kbf-panel="benefits" role="tabpanel">
          <div class="kbf-card kbf-section-rewards" style="padding:18px;">
            <h3 class="kbf-section-title" style="margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">Rewards</h3>
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
              <div style="text-align:center;padding:24px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <i class="ph ph-star kbf-icon" style="font-size:32px; margin:0 auto 10px;display:block;opacity:.25;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
                <p style="font-size:13px;color:var(--kbf-slate);margin:0;font-weight:600;">No rewards yet</p>
                <p style="font-size:12px;color:var(--kbf-slate);margin:4px 0 0;opacity:.7;">The organizer hasn't added any sponsor rewards.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <!-- Message (full width) -->
    <div class="kbf-card kbf-section-message" style="padding:18px;margin-top:18px;">
      <h3 class="kbf-section-title" style="margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">
        Message <span style="background:var(--kbf-green-lt);color:var(--kbf-blue);padding:2px 8px;border-radius:99px;font-size:12px;margin-left:6px;">
          <?php echo $sponsor_count; ?>
        </span>
      </h3>
      <?php if(!empty($sponsors)): ?>
      <div class="kbf-sponsor-wall">
        <?php foreach($sponsors as $sp):
          $initials = $sp->is_anonymous ? '?' : strtoupper(substr(isset($sp->sponsor_name) ? $sp->sponsor_name : 'A',0,1));
        ?>
        <div class="kbf-sponsor-item">
          <div class="kbf-sponsor-avatar">
            <?php if($sp->is_anonymous || empty($sp->sponsor_name)): ?>
              <i class="ph ph-user kbf-icon" aria-hidden="true"></i>
            <?php else: ?>
              <?php echo esc_html($initials); ?>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0;padding-left:4px;">
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="font-weight:600;font-size:13.5px;color:var(--kbf-navy);">
                <?php echo $sp->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($sp->sponsor_name); ?>
              </span>
              <?php $sp_created_ts = !empty($sp->created_at) ? strtotime((string) $sp->created_at) : false; ?>
              <span style="font-size:12px;color:var(--kbf-slate);"><?php echo esc_html($sp_created_ts !== false ? wp_date('M d g:ia', $sp_created_ts) : '--'); ?></span>
            </div>
            <?php if($sp->message): ?><div class="kbf-sponsor-msg" style="margin-top:6px;">"<?php echo esc_html($sp->message); ?>"</div><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div style="text-align:center;padding:24px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;">
        <i class="ph ph-chat kbf-icon" style="font-size:32px; margin:0 auto 10px;display:block;opacity:.25;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)" aria-hidden="true"></i>
        <p style="font-size:13px;color:var(--kbf-slate);margin:0;font-weight:600;">No messages yet</p>
        <p style="font-size:12px;color:var(--kbf-slate);margin:4px 0 0;opacity:.7;">Be the first to leave a message.</p>
      </div>
      <?php endif; ?>
    </div>
</div><!-- .kbf-detail-secondary -->
</div><!-- .kbf-wrap -->
    
    <!-- ================== JS ================== -->
    <script>
   /**
    * @function  kbfSyncDetailPanels
    * @purpose   Normalizes the sticky detail container height after tab or viewport updates.
    * @used-by   DOMContentLoaded flow and window resize listener
    * @calls     document.querySelector
    * @params    none
    * @returns   void
    * @status    ACTIVE
    */
   function kbfSyncDetailPanels(){
    var sticky = document.querySelector('.kbf-detail-sticky');
    if(sticky) sticky.style.height = 'auto';
}
    document.addEventListener('DOMContentLoaded', function(){
        var tabRoot = document.querySelector('.kbf-detail-tabs');
        if (tabRoot) {
            var tabList = tabRoot.querySelector('.kbf-detail-tab-list');
            var tabs = Array.prototype.slice.call(tabRoot.querySelectorAll('.kbf-detail-tab'));
            var panels = Array.prototype.slice.call(tabRoot.querySelectorAll('.kbf-detail-tab-panel'));
            var activateTab = function(key){
                tabs.forEach(function(tab){
                    var isActive = tab.getAttribute('data-kbf-tab') === key;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
                panels.forEach(function(panel){
                    var isActive = panel.getAttribute('data-kbf-panel') === key;
                    panel.classList.toggle('is-active', isActive);
                });
            };
            if (tabList) {
                tabList.addEventListener('click', function(e){
                    var btn = e.target.closest('.kbf-detail-tab');
                    if (!btn) return;
                    activateTab(btn.getAttribute('data-kbf-tab'));
                });
            }
        }
        /* ===== PHOTO SLIDER ===== */
        var mainWrap = document.getElementById('kbf-photo-main');
        var lightbox = document.getElementById('kbf-photo-lightbox');
        var lightImg = document.getElementById('kbf-photo-lightbox-img');
        if (!mainWrap || !lightbox || !lightImg) return;

        var slides = Array.prototype.slice.call(mainWrap.querySelectorAll('.kbf-photo-slide'));
        var dots = Array.prototype.slice.call(mainWrap.querySelectorAll('.kbf-photo-dot'));
        var prevBtn = mainWrap.querySelector('.kbf-photo-prev');
        var nextBtn = mainWrap.querySelector('.kbf-photo-next');
        var progressFill = mainWrap.querySelector('.kbf-photo-progress-fill');
        var total = slides.length;
        var currentIndex = 0;
        var autoTimer = null;
        var autoPaused = false;
        var isTransitioning = false;
        var touchStartX = 0;
        var touchEndX = 0;

        /**
         * @function  goTo
         * @purpose   Switches the active photo slide and dot indicator to a target index.
         * @used-by   goNext, goPrev, dot click handlers
         * @calls     resetAuto, setTimeout
         * @params    number index - Target slide index
         * @params    boolean animate - Whether to use transition lock timeout
         * @returns   void
         * @status    ACTIVE
         */
        function goTo(index, animate){
            if(isTransitioning || index === currentIndex || !slides.length) return;
            isTransitioning = true;
            slides[currentIndex].classList.remove('is-active');
            if(dots[currentIndex]) dots[currentIndex].classList.remove('is-active');
            currentIndex = (index + total) % total;
            slides[currentIndex].classList.add('is-active');
            if(dots[currentIndex]) dots[currentIndex].classList.add('is-active');
            if(lightImg) lightImg.src = slides[currentIndex].querySelector('img').src;
            mainWrap.setAttribute('data-current', currentIndex);
            resetAuto();
            setTimeout(function(){ isTransitioning = false; }, animate !== false ? 500 : 0);
        }

        /**
         * @function  goNext
         * @purpose   Moves the photo slider to the next slide.
         * @used-by   autoplay timer, next button click, keyboard/touch handlers
         * @calls     goTo
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function goNext(){ goTo(currentIndex + 1); }
        /**
         * @function  goPrev
         * @purpose   Moves the photo slider to the previous slide.
         * @used-by   prev button click, keyboard/touch handlers
         * @calls     goTo
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function goPrev(){ goTo(currentIndex - 1); }

        /**
         * @function  scheduleAuto
         * @purpose   Starts the photo slider autoplay cycle and progress bar animation.
         * @used-by   resetAuto, resumeAuto, initial slider init
         * @calls     clearAutoTimer, requestAnimationFrame, setTimeout, goNext
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function scheduleAuto(){
            if(total < 2 || autoPaused || !progressFill) return;
            clearAutoTimer();
            progressFill.style.transition = 'none';
            progressFill.style.width = '0%';
            var delay = 5000;
            requestAnimationFrame(function(){
                requestAnimationFrame(function(){
                    progressFill.style.transition = 'width ' + delay + 'ms linear';
                    progressFill.style.width = '100%';
                });
            });
            autoTimer = setTimeout(function(){
                if(autoPaused) return;
                goNext();
            }, delay);
        }
        /**
         * @function  clearAutoTimer
         * @purpose   Clears the active photo slider autoplay timer.
         * @used-by   scheduleAuto, resetAuto, pauseAuto
         * @calls     clearTimeout
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function clearAutoTimer(){
            if(!autoTimer) return;
            clearTimeout(autoTimer);
            autoTimer = null;
        }
        /**
         * @function  resetAuto
         * @purpose   Restarts autoplay timing after manual slide changes.
         * @used-by   goTo
         * @calls     clearAutoTimer, scheduleAuto
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function resetAuto(){ clearAutoTimer(); scheduleAuto(); }
        /**
         * @function  pauseAuto
         * @purpose   Pauses slider autoplay and freezes current progress indicator state.
         * @used-by   mouseenter on slider, opening lightbox
         * @calls     clearAutoTimer, window.getComputedStyle
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function pauseAuto(){
            autoPaused = true;
            clearAutoTimer();
            if(progressFill){
                var computed = window.getComputedStyle(progressFill);
                var currentWidth = computed.width;
                progressFill.style.transition = 'none';
                progressFill.style.width = currentWidth;
            }
            mainWrap.classList.add('is-paused');
        }
        /**
         * @function  resumeAuto
         * @purpose   Resumes slider autoplay when lightbox is closed and interaction ends.
         * @used-by   mouseleave on slider, closeLightbox
         * @calls     scheduleAuto
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function resumeAuto(){
            if(lightbox.classList.contains('open')) return;
            autoPaused = false;
            mainWrap.classList.remove('is-paused');
            scheduleAuto();
        }

        // Events
        if(prevBtn) prevBtn.addEventListener('click', function(e){ e.stopPropagation(); goPrev(); });
        if(nextBtn) nextBtn.addEventListener('click', function(e){ e.stopPropagation(); goNext(); });
        dots.forEach(function(dot){
            dot.addEventListener('click', function(e){
                e.stopPropagation();
                goTo(parseInt(dot.getAttribute('data-index'), 10), false);
            });
        });
        // Click on main image opens lightbox
        mainWrap.addEventListener('click', function(e){
            if(e.target.closest('.kbf-photo-nav') || e.target.closest('.kbf-photo-dot')) return;
            lightImg.src = slides[currentIndex].querySelector('img').src;
            lightbox.classList.add('open');
            lightbox.setAttribute('aria-hidden','false');
            pauseAuto();
        });
        mainWrap.addEventListener('mouseenter', pauseAuto);
        mainWrap.addEventListener('mouseleave', resumeAuto);

        // Touch/swipe
        mainWrap.addEventListener('touchstart', function(e){ touchStartX = e.changedTouches[0].screenX; }, {passive:true});
        mainWrap.addEventListener('touchend', function(e){
            touchEndX = e.changedTouches[0].screenX;
            var diff = touchStartX - touchEndX;
            if(Math.abs(diff) > 50){ diff > 0 ? goNext() : goPrev(); }
        }, {passive:true});

        // Lightbox controls
        var lbClose = document.getElementById('kbf-photo-lightbox-close');
        var lbPrev = document.getElementById('kbf-photo-lightbox-prev');
        var lbNext = document.getElementById('kbf-photo-lightbox-next');
        /**
         * @function  closeLightbox
         * @purpose   Closes the photo lightbox overlay and restores autoplay state.
         * @used-by   close button click, backdrop click, Escape key handler
         * @calls     resumeAuto
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function closeLightbox(){
            lightbox.classList.remove('open');
            lightbox.setAttribute('aria-hidden','true');
            resumeAuto();
        }
        if(lbClose) lbClose.addEventListener('click', closeLightbox);
        if(lbPrev) lbPrev.addEventListener('click', function(e){ e.stopPropagation(); goPrev(); lightImg.src = slides[currentIndex].querySelector('img').src; });
        if(lbNext) lbNext.addEventListener('click', function(e){ e.stopPropagation(); goNext(); lightImg.src = slides[currentIndex].querySelector('img').src; });
        lightbox.addEventListener('click', function(e){ if(e.target === lightbox) closeLightbox(); });
        document.addEventListener('keydown', function(e){
            if(!lightbox.classList.contains('open')) return;
            if(e.key === 'Escape') closeLightbox();
            if(e.key === 'ArrowRight') { goNext(); if(lightImg) lightImg.src = slides[currentIndex].querySelector('img').src; }
            if(e.key === 'ArrowLeft') { goPrev(); if(lightImg) lightImg.src = slides[currentIndex].querySelector('img').src; }
        });

        scheduleAuto();
        kbfSyncDetailPanels();
        window.addEventListener('resize', kbfSyncDetailPanels);
    });
    /**
     * @function  kbfGetActiveSponsorModal
     * @purpose   Returns the currently visible sponsor modal element.
     * @used-by   kbfGetActiveSponsorForm, kbfGetActiveSponsorMsg, kbfSpdSponsor
     * @calls     document.querySelectorAll, window.getComputedStyle
     * @params    none
     * @returns   HTMLElement|null - Active modal element or fallback modal/null
     * @status    ACTIVE
     */
    function kbfGetActiveSponsorModal(){
        var modals = document.querySelectorAll('#kbf-modal-sponsor');
        for (var i = 0; i < modals.length; i++) {
            var d = window.getComputedStyle(modals[i]).display;
            if (d && d !== 'none') return modals[i];
        }
        return modals[0] || null;
    }
    /**
     * @function  kbfGetActiveSponsorForm
     * @purpose   Resolves the sponsor form associated with the active modal context.
     * @used-by   kbfSpdSponsor
     * @calls     kbfGetActiveSponsorModal, document.getElementById
     * @params    none
     * @returns   HTMLElement|null - Sponsor form element
     * @status    ACTIVE
     */
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
    /**
     * @function  kbfGetActiveSponsorMsg
     * @purpose   Resolves the sponsor message container in the active sponsor modal.
     * @used-by   kbfSpdSponsor
     * @calls     kbfGetActiveSponsorModal
     * @params    none
     * @returns   HTMLElement|null - Message container element
     * @status    ACTIVE
     */
    function kbfGetActiveSponsorMsg(){
        var modal = kbfGetActiveSponsorModal();
        if (!modal) return null;
        return modal.querySelector('#kbf-spd-msg') || modal.querySelector('#kbf-sponsor-msg');
    }
    /**
     * @function  kbfValidateRequired
     * @purpose   Validates required visible sponsor form fields and injects inline error messages.
     * @used-by   kbfSpdSponsor
     * @calls     form.querySelectorAll, document.createElement
     * @params    HTMLFormElement form - Sponsor form to validate
     * @returns   boolean - True when form passes required field validation
     * @status    ACTIVE
     */
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
    /**
     * @function  updateMsgCount
     * @purpose   Updates sponsor message character counter display.
     * @used-by   input event handler on sponsor message field and initial invocation
     * @calls     none
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function updateMsgCount(){
            msgCount.textContent = (msg.value ? msg.value.length : 0) + '/300';
        }
        updateMsgCount();
        msg.addEventListener('input', updateMsgCount);
    })();
    (function(){
        var amountDisplay = document.getElementById('kbf-sponsor-amount-display');
        var amountHidden = document.getElementById('kbf-sponsor-amount');
        if(!amountDisplay || !amountHidden) return;

        function formatAmountInput(){
            var digits = (amountDisplay.value || '').replace(/[^\d]/g, '');
            amountHidden.value = digits;
            if(!digits){
                amountDisplay.value = '';
                return;
            }
            amountDisplay.value = Number(digits).toLocaleString('en-US');
        }

        amountDisplay.addEventListener('input', formatAmountInput);
        amountDisplay.addEventListener('blur', formatAmountInput);
    })();
    (function(){
        var phoneInput = document.getElementById('kbf-sponsor-phone');
        if(!phoneInput) return;
        function formatSponsorPhone(){
            var digits = (phoneInput.value || '').replace(/\D/g, '').substring(0, 11);
            if(digits.length <= 4) phoneInput.value = digits;
            else if(digits.length <= 7) phoneInput.value = digits.substring(0,4) + ' ' + digits.substring(4);
            else phoneInput.value = digits.substring(0,4) + ' ' + digits.substring(4,7) + ' ' + digits.substring(7);
        }
        formatSponsorPhone();
        phoneInput.addEventListener('input', formatSponsorPhone);
        phoneInput.addEventListener('blur', formatSponsorPhone);
    })();
    /**
     * @function  kbfEscHtmlMsg
     * @purpose   Escapes untrusted text before rendering inside HTML alert containers.
     * @used-by   [kbfSpdSponsor, kbfSubmitRating]
     * @calls     [String.replace]
     * @params    [mixed v - text value to escape]
     * @returns   [string escaped HTML-safe text]
     * @status    ACTIVE
     */
    function kbfEscHtmlMsg(v){
        return String(v == null ? '' : v).replace(/[&<>"']/g, function(ch){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch];
        });
    }
    /**
     * @function  kbfSpdSponsor
     * @purpose   Submits sponsor checkout payload and opens returned Maya checkout URL.
     * @used-by   Sponsor modal primary button onclick
     * @calls     kbfGetActiveSponsorForm, kbfGetActiveSponsorModal, kbfGetActiveSponsorMsg, kbfValidateRequired, kbfSetBtnLoading, kbfSetSkeleton, kbfFetchJson
     * @params    string nonce - WordPress nonce for checkout action
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfSpdSponsor=function(nonce){
        const form=kbfGetActiveSponsorForm();
        const modal=kbfGetActiveSponsorModal();
        const btn=modal ? modal.querySelector('.kbf-modal-footer .kbf-btn-primary') : document.querySelector('#kbf-modal-sponsor .kbf-modal-footer .kbf-btn-primary');
        const msg=kbfGetActiveSponsorMsg();
        if(!kbfValidateRequired(form)) return;
        const amountEl = form.querySelector('input[name="amount"]');
        const amt = amountEl ? parseFloat((amountEl.value || '0').replace(/,/g, '')) : 0;
        if(amt < 50){
            msg.innerHTML='<div class="kbf-alert kbf-alert-error">Minimum sponsorship is &#8369;50.00.</div>';
            return;
        }
        kbfSetBtnLoading(btn,true,'Processing...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);
        fd.append('action', 'kbf_create_checkout');
        fd.append('nonce',nonce);
        fd.append('is_anonymous',document.getElementById('spd-anon').checked?'1':'0');
        kbfFetchJson(ajaxurl, fd, (j)=>{
            if(j.success){
                if(j.data && j.data.checkout_url){
                    btn.innerHTML='Redirecting to payment...';
                    // Use same-tab navigation because popup windows are often blocked on mobile Safari.
                    const checkoutUrl = String(j.data.checkout_url || '');
                    if (window.kbfAwaitPaymentSuccess) window.kbfAwaitPaymentSuccess();
                    try {
                        window.location.assign(checkoutUrl);
                    } catch (e) {
                        window.location.href = checkoutUrl;
                    }
                } else {
                    msg.innerHTML='<div class="kbf-alert kbf-alert-error">Maya checkout URL was not returned. Please check your Maya API keys and try again.</div>';
                    kbfSetBtnLoading(btn,false);
                    kbfSetSkeleton(msg,false);
                }
            } else {
                msg.innerHTML='<div class="kbf-alert kbf-alert-error">'+kbfEscHtmlMsg(j && j.data ? j.data.message : '')+'</div>';
                kbfSetBtnLoading(btn,false);
                kbfSetSkeleton(msg,false);
            }
        }, (err)=>{
            console.error('KBF checkout error:', err);
            msg.innerHTML='<div class="kbf-alert kbf-alert-error">'+kbfEscHtmlMsg(err)+'</div>';
            kbfSetBtnLoading(btn,false);
            kbfSetSkeleton(msg,false);
        });
    };
    /**
     * @function  kbfSpdReport
     * @purpose   Submits abuse report form via AJAX and updates report modal feedback state.
     * @used-by   Report modal submit button onclick
     * @calls     kbfSetBtnLoading, kbfSetSkeleton, kbfSetLoadingPage, fetch, kbfHideModal
     * @params    string nonce - WordPress nonce for report action
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfSpdReport=function(nonce){
        const form=document.getElementById('kbf-report-form');
        const btn=document.querySelector('#kbf-modal-report .kbf-modal-footer .kbf-btn-danger');
        const msg=document.getElementById('kbf-rpt-msg');
        if(!form || !btn || !msg) return;
        var reasonEl = form.querySelector('[name="reason"]');
        var detailsEl = form.querySelector('[name="details"]');
        if(!reasonEl || !detailsEl || !reasonEl.value || !detailsEl.value.trim()){
            msg.innerHTML='<div class="kbf-alert kbf-alert-error">Please fill all required fields.</div>';
            return;
        }
        kbfSetBtnLoading(btn,true,'Submitting...');
        kbfSetSkeleton(msg,true);
        kbfSetLoadingPage(true);
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>{
            return r.json().catch(function(){
                return r.text().then(function(t){
                    return {success:false,data:{message:t && t.trim() ? t.trim() : 'Request failed.'}};
                });
            });
        }).then(j=>{
            msg.innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+kbfEscHtmlMsg(j && j.data ? j.data.message : 'Request failed.')+'</div>';
        if(j.success){
            var m=document.getElementById('kbf-modal-report');
            if(m){m.classList.remove('is-open');m.style.display='none';}
            if(form) form.reset();
        } else {
                kbfSetBtnLoading(btn,false); 
                kbfSetSkeleton(msg,false);
            }
            kbfSetLoadingPage(false);
        }).catch(()=>{ 
            msg.innerHTML='<div class="kbf-alert kbf-alert-error">Request failed. Please try again.</div>';
            kbfSetBtnLoading(btn,false); 
            kbfSetSkeleton(msg,false);
            kbfSetLoadingPage(false);
        });
    };
    /**
     * @function  kbfCreatePoster
     * @purpose   Opens the poster modal and initializes poster preview/QR state.
     * @used-by   More-menu button onclick
     * @calls     kbfShowModal, kbfPosterSync, kbfPosterRenderQr
     * @params    string token - Fund token reference for poster flow
     * @params    string title - Fund title reference for poster flow
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfCreatePoster = function(token, title){
        var modal = document.getElementById('kbf-modal-poster');
        if (!modal) return;
        kbfShowModal(modal);
        kbfPosterSync();
        kbfPosterRenderQr();
    };
    /**
     * @function  kbfExportPoster
     * @purpose   Exports poster preview as PDF using html2canvas and jsPDF libraries.
     * @used-by   Poster modal export button onclick
     * @calls     kbfPosterRenderQr, html2canvas, window.jspdf.jsPDF, window.open, setTimeout
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
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
    /**
     * @function  kbfPosterSync
     * @purpose   Syncs poster preview title/description text and character counts from editor inputs.
     * @used-by   kbfCreatePoster, poster input event listener
     * @calls     document.getElementById
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
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
    /**
     * @function  kbfPosterRenderQr
     * @purpose   Renders poster QR code and converts canvas output to image for export reliability.
     * @used-by   kbfCreatePoster, kbfExportPoster, QR script-load callback
     * @calls     document.getElementById, QRCode constructor, canvas.toDataURL
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
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
        /**
         * @function  loadScript
         * @purpose   Dynamically loads an external script once and runs optional callback after load.
         * @used-by   Poster assets loader IIFE
         * @calls     document.querySelector, document.createElement, document.head.appendChild
         * @params    string src - Script URL to load
         * @params    function cb - Optional callback after script load
         * @returns   void
         * @status    ACTIVE
         */
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
    /**
     * @function  kbfSetRating
     * @purpose   Sets selected organizer rating value and updates thumbs-up icon states.
     * @used-by   Rating icon onclick handlers and initial default rating setup
     * @calls     document.getElementById, document.querySelectorAll
     * @params    number v - Selected rating value from 1 to 5
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfSetRating=function(v){
        _kbfRating=v;
        var ratingInput = document.getElementById('kbf-rating-val');
        if(ratingInput) ratingInput.value=v;
        document.querySelectorAll('.kbf-star-btn').forEach((s,i)=>{
            const filled = i < v;
            const fillCls = (s.getAttribute('data-filled') || 'ph-fill ph-thumbs-up').split(' ');
            const emptyCls = (s.getAttribute('data-empty') || 'ph ph-thumbs-up').split(' ');
            s.classList.remove.apply(s.classList, fillCls);
            s.classList.remove.apply(s.classList, emptyCls);
            s.classList.add.apply(s.classList, filled ? fillCls : emptyCls);
            s.style.color = filled ? '#3b82f6' : '#94a3b8';
        });
    };
    kbfSetRating(5);
    /**
     * @function  kbfSubmitRating
     * @purpose   Submits organizer rating form via AJAX and handles success/duplicate-rating feedback.
     * @used-by   Rating modal submit button onclick
     * @calls     fetch, FormData, kbfHideModal, document.querySelectorAll
     * @params    string nonce - WordPress nonce for rating action
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfSubmitRating=function(nonce){
        const form=document.getElementById('kbf-rating-form');
        const btn=document.querySelector('#kbf-modal-rating .kbf-modal-footer .kbf-btn-primary');
        btn.disabled=true;btn.textContent='Submitting...';
        const fd=new FormData(form);fd.append('action','kbf_submit_rating');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            // ===== RULE 3C: HANDLE DUPLICATE ERROR =====
            if (!j.success && j.data && j.data.message && j.data.message.indexOf('already submitted') !== -1) {
                document.getElementById('kbf-rate-msg').innerHTML = '<div class="kbf-alert kbf-alert-warning">' + kbfEscHtmlMsg(j.data.message) + '</div>';
                setTimeout(function(){ kbfHideModal('kbf-modal-rating'); }, 2000);
                document.querySelectorAll('[onclick*="kbf-modal-rating"],[onclick*="kbf-modal-rating\'"]').forEach(function(el){
                    if(el.tagName === 'BUTTON' && !el.closest('.kbf-modal')) {
                        el.disabled = true;
                        el.innerHTML = '<i class="ph-fill ph-thumbs-up kbf-icon" style="font-size:13px;color:#3b82f6;" aria-hidden="true"></i> Score Submitted';
                    }
                });
                return;
            }
            document.getElementById('kbf-rate-msg').innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+kbfEscHtmlMsg(j && j.data ? j.data.message : '')+'</div>';
            if(j.success)setTimeout(()=>{kbfHideModal('kbf-modal-rating');},1800);else{btn.disabled=false;btn.textContent='Submit Score';}
        });
    };
    /**
     * @function  kbfToggleMoreMenu
     * @purpose   Toggles the contextual more-menu visibility and positions it near its trigger.
     * @used-by   More button onclick
     * @calls     document.getElementById, Element.getBoundingClientRect, classList.toggle
     * @params    Event e - Click event object
     * @returns   void
     * @status    ACTIVE
     */
    /**
     * @function  kbfCloseMoreMenu
     * @purpose   Closes the contextual more-menu and clears its wrapper open state.
     * @used-by   [document click handler, kbfToggleMoreMenu flow]
     * @calls     [document.getElementById, Element.closest, classList.remove]
     * @params    [none]
     * @returns   [void]
     * @status    ACTIVE
     */
    function kbfCloseMoreMenu(){
        var menu = document.getElementById('kbf-more-menu');
        if(!menu) return;
        var wrap = menu.closest('.kbf-more-wrap');
        menu.classList.remove('open');
        if(wrap) wrap.classList.remove('open');
    }
    /**
     * @function  kbfToggleMoreMenu
     * @purpose   Toggles the contextual more-menu open/closed state for fund actions.
     * @used-by   [More button onclick]
     * @calls     [kbfCloseMoreMenu, document.getElementById, Element.closest, classList.contains, classList.toggle]
     * @params    [Event e - Click event object]
     * @returns   [void]
     * @status    ACTIVE
     */
    window.kbfToggleMoreMenu=function(e){
        e = e || window.event;
        if(e) { e.stopPropagation(); e.preventDefault(); }
        var menu = document.getElementById('kbf-more-menu');
        if(!menu) return;
        var wrap = menu.closest('.kbf-more-wrap');
        var isOpen = menu.classList.contains('open');
        menu.classList.toggle('open');
        if(wrap) wrap.classList.toggle('open', !isOpen);
    };
    document.addEventListener('click', function(e){
        var menu = document.getElementById('kbf-more-menu');
        if(!menu || !menu.classList.contains('open')) return;
        var wrap = menu.closest('.kbf-more-wrap');
        if(wrap && wrap.contains(e.target)) return;
        kbfCloseMoreMenu();
    });
    var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
    var kbfSaveNonce = '<?php echo esc_js($nonce_save); ?>';
    var kbfIsLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
    var kbfSignInUrl = '<?php echo esc_js(kbf_get_page_url('signin')); ?>';
    /**
     * @function  kbfSaveFund
     * @purpose   Toggles saved-state for a fund and updates save button UI state.
     * @used-by   Save button onclick
     * @calls     FormData, kbfFetchJson, classList.toggle, alert
     * @params    number|string id - Fund ID to toggle save state for
     * @params    HTMLElement btn - Optional trigger button element
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfSaveFund=function(id, btn){
        if (kbfIsLoggedIn === false) {
            if (window.kbfOpenAuthModal) {
                window.kbfOpenAuthModal('Sign in to save fundraisers.');
            } else if (kbfSignInUrl) {
                window.location.href = kbfSignInUrl;
            } else {
                alert('Please sign in to save funds.');
            }
            return;
        }
        if(!id) return;
        var el = btn || document.querySelector('.kbf-save-btn[data-fund-id="' + id + '"]');
        if(el && el.classList.contains('is-loading')) return;
        var finishLoading = function(){
            if(!el) return;
            el.classList.remove('is-loading');
            el.removeAttribute('aria-busy');
            el.disabled = false;
        };
        if(el){
            el.classList.add('is-loading');
            el.setAttribute('aria-busy', 'true');
            el.disabled = true;
        }
        var fd = new FormData();
        fd.append('action','kbf_toggle_save_fund');
        fd.append('nonce', kbfSaveNonce);
        fd.append('fund_id', id);
        if(typeof kbfFetchJson === 'undefined'){ finishLoading(); alert('Save failed.'); return; }
        kbfFetchJson(ajaxurl, fd, function(j){
            if(j && j.success){
                var saved = !!(j.data && j.data.saved);
                if(el){
                    el.classList.toggle('is-saved', saved);
                    el.setAttribute('data-saved', saved ? '1' : '0');
                    var icon = el.querySelector('i');
                    if(icon){
                        icon.classList.remove('ph','ph-bookmark-simple','ph-fill');
                        if(saved){ icon.classList.add('ph-fill','ph-bookmark-simple'); }
                        else { icon.classList.add('ph','ph-bookmark-simple'); }
                    }
                    var label = el.querySelector('.kbf-save-label');
                    if(label){ label.textContent = saved ? 'Saved' : (el.getAttribute('data-save-label') || 'Save Fund'); }
                }
            } else {
                alert((j && j.data && j.data.message) ? j.data.message : 'Unable to save.');
            }
            finishLoading();
        }, function(err){
            finishLoading();
            alert(err || 'Request failed.');
        });
    };
    (function(){
        var kbfCtaHitFix = function(e){
            var saveBtn = document.querySelector('.kbf-fund-cta-card .kbf-save-btn');
            var moreBtn = document.querySelector('.kbf-fund-cta-card .kbf-more-wrap > .kbf-btn');
            var t = e.target;
            if((saveBtn && saveBtn.contains(t)) || (moreBtn && moreBtn.contains(t))) return;
            var x = e.clientX, y = e.clientY;
            var hit = function(el){
                if(!el) return false;
                var r = el.getBoundingClientRect();
                return x >= r.left && x <= r.right && y >= r.top && y <= r.bottom;
            };
            if(hit(saveBtn)){
                e.preventDefault();
                e.stopPropagation();
                if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
                if(typeof kbfSaveFund === 'function'){
                    kbfSaveFund(saveBtn.getAttribute('data-fund-id'), saveBtn);
                } else {
                    saveBtn.click();
                }
                return;
            }
            if(hit(moreBtn)){
                e.preventDefault();
                e.stopPropagation();
                if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
                if(typeof kbfToggleMoreMenu === 'function'){
                    kbfToggleMoreMenu(e);
                } else {
                    moreBtn.click();
                }
            }
        };
        document.addEventListener('pointerdown', kbfCtaHitFix, true);
        document.addEventListener('click', kbfCtaHitFix, true);
    })();
    /**
     * @function  initLeaderboardPager
     * @purpose   Builds and runs client-side pagination UI for leaderboard entries.
     * @used-by   DOMContentLoaded handler
     * @calls     document.querySelector, querySelectorAll, render
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function initLeaderboardPager(){
        var list = document.querySelector('[data-kbf-leaderboard-list]');
        var pager = document.querySelector('[data-kbf-leaderboard-pager]');
        if(!list || !pager) return;
        var items = Array.prototype.slice.call(list.querySelectorAll('.kbf-leaderboard-item'));
        if(items.length === 0) { pager.style.display = 'none'; return; }
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
         * @function  render
         * @purpose   Renders current leaderboard page slice and updates pager controls.
         * @used-by   initLeaderboardPager, page-size change, prev/next button handlers
         * @calls     Math.max, Math.ceil, Array.prototype.forEach
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
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
            pager.style.display = total > perPage ? 'flex' : 'none';
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
        var tabRoot = document.querySelector('.kbf-detail-tabs');
        if (tabRoot) {
            var tabList = tabRoot.querySelector('.kbf-detail-tab-list');
            var tabs = Array.prototype.slice.call(tabRoot.querySelectorAll('.kbf-detail-tab'));
            var panels = Array.prototype.slice.call(tabRoot.querySelectorAll('.kbf-detail-tab-panel'));
            var activateTab = function(key){
                tabs.forEach(function(tab){
                    var isActive = tab.getAttribute('data-kbf-tab') === key;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
                panels.forEach(function(panel){
                    var isActive = panel.getAttribute('data-kbf-panel') === key;
                    panel.classList.toggle('is-active', isActive);
                });
            };
            if (tabList) {
                tabList.addEventListener('click', function(e){
                    var btn = e.target.closest('.kbf-detail-tab');
                    if (!btn) return;
                    activateTab(btn.getAttribute('data-kbf-tab'));
                });
            }
        }
        initLeaderboardPager();
    });
    </script>
    <?php
    $c=ob_get_clean();
    if (!empty($_GET['kbf_tab']) && $_GET['kbf_tab'] === 'fund_details') {
        return $c;
    }
    return bntm_universal_container('Fund Details -- KonekBayan',$c, ['show_topbar'=>false,'show_header'=>false]);
}






