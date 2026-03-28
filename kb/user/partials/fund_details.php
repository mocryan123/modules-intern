<?php
/* Fund details shortcode */
function bntm_shortcode_kbf_fund_details() {
    kbf_global_assets();
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $fund = null;
    $current_user_id = get_current_user_id();
    if(!empty($_GET['fund_id'])) {
        $fid = intval($_GET['fund_id']);
        $fund = $wpdb->get_row($wpdb->prepare(
            "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.id=%d AND (f.status IN ('active','completed') OR f.business_id=%d)",
            $fid, $current_user_id
        ));
    } elseif(!empty($_GET['kbf_share'])) {
        $token = sanitize_text_field($_GET['kbf_share']);
        $fund = $wpdb->get_row($wpdb->prepare(
            "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.share_token=%s AND (f.status IN ('active','completed') OR f.business_id=%d)",
            $token, $current_user_id
        ));
    }
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
            COUNT(*) AS num_donations
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
    $browse_url = kbf_get_page_url('browse');
    $profile_url = $fund ? add_query_arg('organizer_id', $fund->business_id, kbf_get_page_url('organizer_profile')) : kbf_get_page_url('organizer_profile');
    $demo_mode  = (bool)kbf_get_setting('kbf_demo_mode',true);
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $nonce_rating  = wp_create_nonce('kbf_rating');

    ob_start();
    ?>

    <!-- ================== CSS ================== -->
    <style>
    .kbf-detail-wrap{max-width:1000px;margin:0 auto;}
    .kbf-detail-layout{display:flex;gap:28px;align-items:stretch;}
   .kbf-detail-panels{display:grid;grid-template-columns:1fr 340px;gap:28px;width:100%;}
.kbf-detail-left{display:flex;flex-direction:column;padding-bottom:24px;justify-content:flex-end;min-height:0;}
.kbf-detail-right{display:flex;flex-direction:column;}
.kbf-detail-sticky{display:flex;flex-direction:column;gap:14px;flex:1;padding-bottom:40px;box-sizing:border-box;}
    .kbf-detail-sticky > *{margin-top:0 !important;margin-bottom:0 !important;}
.kbf-leaderboard-card{flex:1;display:flex;flex-direction:column;justify-content:flex-end;}
    .kbf-leaderboard-body{flex:1;display:flex;flex-direction:column;}
    .kbf-leaderboard-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);}
    .kbf-leaderboard-title{display:flex;align-items:center;gap:10px;min-width:0;}
    .kbf-leaderboard-icon{width:28px;height:28px;border-radius:8px;background:#eef4ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .kbf-leaderboard-icon img{width:14px;height:14px;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);}
    .kbf-leaderboard-text{font-size:14px;font-weight:800;color:var(--kbf-navy);line-height:1;}
    .kbf-leaderboard-sub{font-size:11.5px;color:var(--kbf-slate);margin-top:3px;}
    .kbf-leaderboard-pill{background:var(--kbf-accent);color:#fff;border-radius:99px;padding:3px 10px;font-size:10.5px;font-weight:800;flex-shrink:0;}
    .kbf-photo-gallery{display:flex;flex-direction:column;gap:12px;margin-bottom:22px;}
    .kbf-photo-main{border-radius:16px;overflow:hidden;border:1px solid var(--kbf-border);background:#f1f5f9;}
    .kbf-photo-main img{width:100%;height:360px;object-fit:cover;display:block;transition:transform .3s ease;transform:translateX(0);}
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
        right:12px;
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
        max-width:min(860px,90vw);
        max-height:80vh;
        width:auto;
        height:auto;
        border-radius:16px;
        box-shadow:0 24px 60px rgba(0,0,0,0.45);
        background:#fff;
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
    .kbf-sponsor-wall{display:flex;flex-direction:column;gap:10px;margin-top:14px;}
    .kbf-sponsor-item{display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--kbf-slate-lt);border-radius:8px;}
    .kbf-sponsor-avatar{width:36px;height:36px;border-radius:50%;background:var(--kbf-navy);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;font-weight:700;color:#fff;}

    .kbf-org-avatar{position:relative;width:52px;height:52px;flex-shrink:0;}
    .kbf-org-avatar > img{width:52px;height:52px;border-radius:50%;object-fit:cover;display:block;}
    .kbf-org-verified{position:absolute;right:-2px;bottom:-2px;width:18px;height:18px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 1px #fff;}
    .kbf-org-verified::before{content:'';width:13px;height:13px;background:#1d4ed8;display:block;
        -webkit-mask:url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/patch-check-fill.svg') no-repeat center/contain;
        mask:url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/patch-check-fill.svg') no-repeat center/contain;}
    .kbf-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--kbf-slate);margin-bottom:20px;}
    .kbf-breadcrumb a{color:var(--kbf-blue);text-decoration:none;font-weight:600;}
    .kbf-breadcrumb a:hover{text-decoration:underline;}
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
        .kbf-photo-main img{height:440px;}
        .kbf-photo-thumb img{height:80px;}
    }
 </style>
    <!-- ================== HTML ================== -->
    <div class="kbf-wrap">

    <!-- Sponsor Modal -->
    <div id="kbf-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header"><h3>Sponsor "<?php echo esc_html(wp_trim_words($fund->title,6)); ?>"</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-sponsor').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:12px 16px;margin-bottom:18px;display:flex;justify-content:space-between;font-size:13px;">
            <span><strong style="color:var(--kbf-green);">₱<?php echo number_format($fund->raised_amount,2); ?></strong> raised</span>
            <span style="color:var(--kbf-slate);"><?php echo round($pct); ?>% of ₱<?php echo number_format($fund->goal_amount,2); ?> goal</span>
          </div>
          <form id="kbf-sponsor-form" onsubmit="return false;">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Organization</label><input type="text" name="sponsor_name" id="spd-name" placeholder="Your name, company, or org"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;"><label class="kbf-checkbox-row"><input type="checkbox" id="spd-anon" onchange="document.getElementById('spd-name').disabled=this.checked"> Sponsor Anonymously</label></div>
            </div>
            <div class="kbf-form-group">
              <label>Amount (PHP) *</label>
              <input type="number" name="amount" placeholder="Min. ₱1" min="1" step="1" max="<?php echo $fund->goal_amount>0?max(0,$fund->goal_amount-$fund->raised_amount):''; ?>" required>
              <?php if($fund->goal_amount>0): ?>
                <div class="kbf-meta" style="margin-top:4px;">Max allowed: ₱<?php echo number_format(max(0,$fund->goal_amount-$fund->raised_amount),2); ?> (remaining goal)</div>
              <?php endif; ?>
            </div>
            <div class="kbf-form-group">
              <label>Encouraging Message (optional)</label>
              <textarea name="message" id="kbf-sponsor-message" rows="2" maxlength="300" placeholder="Leave a message for the organizer..."></textarea>
              <div class="kbf-char-count" id="kbf-sponsor-message-count">0/300</div>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Email (for receipt)</label><input type="email" name="email" placeholder="your@email.com"></div>
              <div class="kbf-form-group"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX"></div>
            </div>
            <input type="hidden" name="payment_method" value="online_payment">
            <?php if($demo_mode): ?>
            <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;margin-top:4px;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/exclamation-triangle-fill.svg" alt="" width="16" height="16" style="flex-shrink:0;margin-top:1px;filter:invert(31%) sepia(86%) saturate(1160%) hue-rotate(16deg) brightness(95%) contrast(95%);">
              <div><strong>Demo Mode:</strong> Redirects to Maya sandbox checkout. No real payment is processed.</div>
            </div>
            <?php endif; ?>
            <div id="kbf-spd-msg" style="margin-top:10px;"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-sponsor').style.display='none'">Cancel</button>
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
        <div class="kbf-modal-header"><h3>Report This Fund</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-report').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-report-form">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email"></div>
            <div class="kbf-form-group"><label>Reason *</label><select name="reason" required><option value="">Select</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Info</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select></div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" required></textarea></div>
            <div id="kbf-rpt-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-report').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbfSpdReport('<?php echo $nonce_report; ?>')">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- Rating Modal -->
    <div id="kbf-modal-rating" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Rate This Organizer</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-rating').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-rating-form">
            <input type="hidden" name="organizer_id" value="<?php echo $fund->business_id; ?>">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-group"><label>Rating</label>
              <div id="kbf-star-picker" style="display:flex;gap:8px;margin-top:6px;">
                <?php for($i=1;$i<=5;$i++): ?>
                  <img class="kbf-star-btn" data-val="<?php echo $i; ?>" data-filled="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg" data-empty="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg" src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg" alt="" width="32" height="32" style="cursor:pointer;filter:invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%);" onclick="kbfSetRating(<?php echo $i; ?>)">
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="kbf-rating-val" value="5">
            </div>
            <div class="kbf-form-group"><label>Your Email *</label><input type="email" name="sponsor_email" required placeholder="your@email.com"></div>
            <div class="kbf-form-group"><label>Review (optional)</label><textarea name="review" rows="3" placeholder="Share your experience..."></textarea></div>
            <div id="kbf-rate-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-rating').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitRating('<?php echo $nonce_rating; ?>')">Submit Review</button>
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
          <div class="kbf-photo-main" id="kbf-photo-main">
            <img src="<?php echo esc_url($photos[0]); ?>" alt="<?php echo esc_attr($fund->title); ?>">
          </div>
          <?php if(count($photos)>1): ?>
          <div class="kbf-photo-thumbs-wrap">
            <button type="button" class="kbf-thumb-nav" id="kbf-thumb-prev">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/chevron-left.svg" alt="">
            </button>
            <div class="kbf-photo-thumbs" id="kbf-photo-thumbs">
              <?php foreach(array_slice($photos,0,5) as $ph): ?>
                <div class="kbf-photo-thumb">
                  <img src="<?php echo esc_url($ph); ?>" data-full="<?php echo esc_url($ph); ?>" alt="">
                </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="kbf-thumb-nav" id="kbf-thumb-next">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/chevron-right.svg" alt="">
            </button>
          </div>
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
            <span style="background:var(--kbf-accent-lt);color:#92400e;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><?php echo esc_html($fund->category); ?></span>
            <span class="kbf-badge kbf-badge-<?php echo $fund->status; ?>"><?php echo ucfirst($fund->status); ?></span>
          </div>
          <h1 style="font-size:24px;font-weight:800;color:var(--kbf-navy);margin:0 0 10px;line-height:1.3;"><?php echo esc_html($fund->title); ?></h1>
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
          <div style="display:flex;justify-content:space-between;align-items:center;margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">
            <h3 style="font-size:15px;font-weight:700;color:var(--kbf-navy);margin:0;">About the Account</h3>
            <a href="<?php echo esc_url($profile_url); ?>" style="background:none;border:none;color:var(--kbf-blue);cursor:pointer;font-size:12.5px;font-weight:600;padding:0;display:flex;align-items:center;gap:4px;text-decoration:none;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg" alt="" width="13" height="13" style="filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);">
              View Full Profile & History
            </a>
          </div>
          <a href="<?php echo esc_url($profile_url); ?>" style="display:flex;align-items:center;gap:14px;cursor:pointer;text-decoration:none;color:inherit;" title="View organizer profile">
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

            <div style="flex:1;">
              <div style="font-weight:700;font-size:15px;color:var(--kbf-navy);"><?php echo esc_html($fund->organizer_name); ?></div>
              <?php if($organizer&&$organizer->bio): ?><p style="font-size:13px;color:var(--kbf-text-sm);margin:4px 0 0;line-height:1.55;"><?php echo esc_html(wp_trim_words($organizer->bio,30)); ?></p><?php endif; ?>
              <?php if($organizer&&$organizer->rating_count>0): ?>
              <div style="display:flex;align-items:center;gap:4px;margin-top:5px;">
                <?php for($i=1;$i<=5;$i++): ?>
                  <img src="<?php echo $i<=round($organizer->rating)?'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg':'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg'; ?>" width="12" height="12" alt="" style="filter:<?php echo $i<=round($organizer->rating)?'invert(70%) sepia(85%) saturate(531%) hue-rotate(3deg) brightness(98%) contrast(92%)':'invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%)'; ?>;">
                <?php endfor; ?>
                <span style="font-size:11.5px;color:var(--kbf-slate);margin-left:2px;"><?php echo number_format($organizer->rating,1); ?>/5 &bull; ₱<?php echo number_format($organizer->total_raised,0); ?> raised</span>
              </div>
              <?php else: ?>
              <div style="font-size:12px;color:var(--kbf-slate);margin-top:4px;">Click to view fund history &amp; reviews</div>
              <?php endif; ?>
            </div>
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/chevron-right.svg" alt="" width="16" height="16" style="flex-shrink:0;opacity:.7;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);">
          </a>
        </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: Sticky action sidebar -->
      <div class="kbf-detail-right">
      <div class="kbf-detail-sticky">

        <!-- Funder type info -->
        <div class="kbf-section-fund-type" style="background:var(--kbf-slate-lt);border-radius:10px;padding:14px 16px;font-size:13px;color:var(--kbf-text-sm);">
          <div style="font-weight:700;color:var(--kbf-navy);margin-bottom:4px;">Fund Type</div>
          <div><?php echo ucwords(str_replace('_',' ',$fund->funder_type)); ?></div>
          <?php if($fund->auto_return): ?><div style="margin-top:6px;display:flex;align-items:center;gap:6px;color:var(--kbf-green);font-size:12px;"><img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/check-circle-fill.svg" alt="" width="12" height="12" style="filter:invert(41%) sepia(98%) saturate(342%) hue-rotate(83deg) brightness(93%) contrast(89%);">Auto-refund if goal not met</div><?php endif; ?>
        </div>
  
        <!-- Top Sponsors Leaderboard -->
        <div class="kbf-leaderboard-card kbf-section-leaderboard" style="background:#fff;border:1px solid var(--kbf-border);border-radius:12px;padding:18px;margin-top:14px;">
          <div class="kbf-leaderboard-head">
            <div class="kbf-leaderboard-title">
              <span class="kbf-leaderboard-icon">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/bar-chart-fill.svg" alt="">
              </span>
              <div>
                <div class="kbf-leaderboard-text">Top Sponsors</div>
              </div>
            </div>
            <?php if(!empty($leaderboard)): ?><span class="kbf-leaderboard-pill"><?php echo count($leaderboard); ?> listed</span><?php endif; ?>
          </div>

          <div class="kbf-leaderboard-body">
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
            $bar_pct = $leaderboard[0]->total_given > 0 ? min(100, ($entry->total_given / $leaderboard[0]->total_given) * 100) : 0;
          ?>
          <div style="margin-bottom:<?php echo $pos < count($leaderboard)?'14':'0'; ?>px;">
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
                <div style="font-size:13.5px;font-weight:800;color:var(--kbf-green);">₱<?php echo number_format($entry->total_given, 0); ?></div>
              </div>
            </div>
            <!-- Relative bar -->
            <div style="height:4px;background:var(--kbf-border);border-radius:99px;overflow:hidden;margin-left:38px;">
              <div style="height:100%;width:<?php echo $bar_pct; ?>%;background:<?php echo $pos===1?'linear-gradient(90deg,#f59e0b,#fbbf24)':($pos===2?'linear-gradient(90deg,#6b7280,#9ca3af)':($pos===3?'linear-gradient(90deg,#b45309,#d97706)':'var(--kbf-navy)')); ?>;border-radius:99px;transition:width .4s;"></div>
            </div>
          </div>
          <?php endforeach; ?>

          <?php if($sponsor_count > count($leaderboard)): ?>
          <div style="text-align:center;margin-top:12px;padding-top:10px;border-top:1px solid var(--kbf-border);font-size:12px;color:var(--kbf-slate);">
            +<?php echo $sponsor_count - count($leaderboard); ?> more sponsor<?php echo ($sponsor_count - count($leaderboard)) !== 1 ? 's' : ''; ?>
          </div>
          <?php endif; ?>
          <?php endif; ?>
          </div>
        </div>

        <div class="kbf-detail-sponsor-box kbf-section-progress">
          <!-- Progress -->
          <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
              <span class="kbf-gradient-num" style="font-size:24px;font-weight:800 !important;">₱<?php echo number_format($fund->raised_amount,2); ?></span>
              <span style="font-size:13px;color:var(--kbf-slate);">of ₱<?php echo number_format($fund->goal_amount,2); ?></span>
            </div>
            <div class="kbf-progress-wrap" style="height:10px;margin-bottom:10px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%;height:10px;"></div></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center;">
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div class="kbf-gradient-num" style="font-size:16px;"><?php echo round($pct); ?>%</div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Funded</div>
              </div>
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div class="kbf-gradient-num" style="font-size:16px;"><?php echo $sponsor_count; ?></div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Sponsors</div>
              </div>
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div class="kbf-gradient-num" style="font-size:16px;"><?php echo $days!==null?$days:'&infin;'; ?></div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Days Left</div>
              </div>
            </div>
          </div>

          <?php if($fund->status==='active' && !$is_owner): ?>
          <?php if($demo_mode): ?>
          <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:10px 14px;font-size:12.5px;color:#92400e;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/exclamation-triangle-fill.svg" alt="" width="14" height="14" style="filter:invert(31%) sepia(86%) saturate(1160%) hue-rotate(16deg) brightness(95%) contrast(95%);">
            <span><strong>Demo Mode</strong> -- no real payment processed</span>
          </div>
          <?php endif; ?>
          <button class="kbf-btn kbf-btn-primary" style="width:100%;padding:13px;font-size:15px;font-weight:700;margin-bottom:10px;" onclick="document.getElementById('kbf-modal-sponsor').style.display='flex'">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="16" height="16" style="filter:invert(100%);">
            Sponsor This Fund
          </button>
          <?php elseif($fund->status==='completed'): ?>
          <div class="kbf-alert kbf-alert-success" style="margin-bottom:10px;text-align:center;font-weight:700;">This fund has been completed!</div>
          <button class="kbf-btn kbf-btn-secondary" style="width:100%;padding:11px;font-size:13px;font-weight:600;margin-bottom:10px;border-style:dashed;" onclick="document.getElementById('kbf-modal-sponsor').style.display='flex'">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/exclamation-triangle-fill.svg" alt="" width="14" height="14" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
            Test Demo Sponsorship
          </button>
          <?php endif; ?>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
            <button class="kbf-btn kbf-btn-secondary" style="font-size:13px;" onclick="kbfShareFundDetail('<?php echo esc_js($fund->share_token); ?>','<?php echo esc_js($fund->title); ?>','<?php echo esc_js(wp_trim_words($fund->description,18)); ?>')">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/share-fill.svg" alt="" width="13" height="13" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);"> Share
            </button>
            <?php if(!$is_owner): ?>
            <button class="kbf-btn kbf-btn-secondary" style="font-size:13px;" onclick="document.getElementById('kbf-modal-rating').style.display='flex'">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg" alt="" width="13" height="13" style="filter:invert(70%) sepia(85%) saturate(531%) hue-rotate(3deg) brightness(98%) contrast(92%);"> Rate
            </button>
            <?php else: ?>
            <button class="kbf-btn kbf-btn-secondary" style="font-size:13px;" onclick="window.history.back()">Go Back</button>
            <?php endif; ?>
          </div>
          <button class="kbf-btn kbf-btn-danger" style="width:100%;font-size:13px;" onclick="document.getElementById('kbf-modal-report').style.display='flex'">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/flag-fill.svg" alt="" width="13" height="13" style="filter:invert(34%) sepia(82%) saturate(5110%) hue-rotate(344deg) brightness(100%) contrast(97%);">
            Report this fund
          </button>
        </div>
      </div>
      </div>
      </div>
    </div>

    <!-- Description (full width) -->
    <div class="kbf-card kbf-section-description" style="margin-bottom:20px;">
      <h3 style="font-size:15px;font-weight:700;color:var(--kbf-navy);margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">About This Fund</h3>
      <div style="font-size:14.5px;color:var(--kbf-text-sm);line-height:1.8;"><?php echo nl2br(esc_html(wp_unslash($fund->description))); ?></div>
    </div>

    <!-- Sponsors wall (full width) -->
    <?php if(!empty($sponsors)): ?>
    <div class="kbf-card">
      <h3 style="font-size:15px;font-weight:700;color:var(--kbf-navy);margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">
        Sponsors <span style="background:var(--kbf-green-lt);color:var(--kbf-green);padding:2px 8px;border-radius:99px;font-size:12px;margin-left:6px;"><?php echo $sponsor_count; ?></span>
      </h3>
      <div class="kbf-sponsor-wall">
        <?php foreach($sponsors as $sp):
          $initials = $sp->is_anonymous ? '?' : strtoupper(substr(isset($sp->sponsor_name) ? $sp->sponsor_name : 'A',0,1));
        ?>
        <div class="kbf-sponsor-item">
          <div class="kbf-sponsor-avatar"><?php echo $initials; ?></div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:13.5px;color:var(--kbf-text);"><?php echo $sp->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($sp->sponsor_name); ?></div>
            <?php if($sp->message): ?><div style="font-size:12px;color:var(--kbf-slate);font-style:italic;margin-top:2px;">"<?php echo esc_html($sp->message); ?>"</div><?php endif; ?>
          </div>
          <div style="text-align:right;flex-shrink:0;">
            <div style="font-weight:800;font-size:14px;color:var(--kbf-green);">₱<?php echo number_format($sp->amount,0); ?></div>
            <div style="font-size:11px;color:var(--kbf-slate);"><?php echo date('M d',strtotime($sp->created_at)); ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
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
        var thumbsWrap = document.getElementById('kbf-photo-thumbs');
        var thumbPrev = document.getElementById('kbf-thumb-prev');
        var thumbNext = document.getElementById('kbf-thumb-next');
        if (!mainWrap || !mainImg || !lightbox || !lightImg) return;

        var thumbs = Array.prototype.slice.call(document.querySelectorAll('.kbf-photo-thumb img'));
        var sources = [];
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

        var autoTimer = null;
        function startAutoRotate(){
            if (autoTimer || sources.length < 2) return;
            autoTimer = setInterval(function(){ goNext(); }, 5000);
        }
        function stopAutoRotate(){
            if (!autoTimer) return;
            clearInterval(autoTimer);
            autoTimer = null;
        }

        function openLightbox(){
            rebuildSources();
            var src = mainImg.getAttribute('src');
            if (!src) return;
            setIndexBySrc(src);
            lightImg.src = src;
            lightbox.classList.add('open');
            lightbox.setAttribute('aria-hidden','false');
            stopAutoRotate();
        }
        function closeLightbox(){
            lightbox.classList.remove('open');
            lightbox.setAttribute('aria-hidden','true');
            startAutoRotate();
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
        mainWrap.addEventListener('mouseenter', stopAutoRotate);
        mainWrap.addEventListener('mouseleave', startAutoRotate);
        if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
        if (prevBtn) prevBtn.addEventListener('click', goPrev);
        if (nextBtn) nextBtn.addEventListener('click', goNext);
        lightbox.addEventListener('click', function(e){
            if (e.target === lightbox) closeLightbox();
        });
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') closeLightbox();
            if (!lightbox.classList.contains('open')) return;
            if (e.key === 'ArrowRight') goNext();
            if (e.key === 'ArrowLeft') goPrev();
        });

        startAutoRotate();
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
            msg.innerHTML = '<div class="kbf-alert kbf-alert-error">You cannot give more than ₱' + maxVal.toLocaleString() + ' for this fund.</div>';
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
                console.log('KBF checkout response:', j);
                if(j.data && j.data.checkout_url){
                    btn.innerHTML='Redirecting to payment...';
                    window.open(j.data.checkout_url, '_blank');
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
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            msg.innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if(j.success)setTimeout(()=>{document.getElementById('kbf-modal-report').style.display='none';},1800);else{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    };
    var _kbfRating=5;
    window.kbfSetRating=function(v){
        _kbfRating=v;
        document.getElementById('kbf-rating-val').value=v;
        document.querySelectorAll('.kbf-star-btn').forEach((s,i)=>{
            const filled = i < v;
            const src = filled ? s.getAttribute('data-filled') : s.getAttribute('data-empty');
            s.src = src;
            s.style.filter = filled
                ? 'invert(70%) sepia(85%) saturate(531%) hue-rotate(3deg) brightness(98%) contrast(92%)'
                : 'invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%)';
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
            if(j.success)setTimeout(()=>{document.getElementById('kbf-modal-rating').style.display='none';},1800);else{btn.disabled=false;btn.textContent='Submit Review';}
        });
    };
    </script>
    <?php
    $c=ob_get_clean();
    if (!empty($_GET['kbf_tab']) && $_GET['kbf_tab'] === 'fund_details') {
        return $c;
    }
    return bntm_universal_container('Fund Details -- KonekBayan',$c, ['show_topbar'=>false,'show_header'=>false]);
}
