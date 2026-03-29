<?php
/* Public browse shortcode */
function bntm_shortcode_kbf_browse() {
    kbf_global_assets();
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $q   = isset($_GET['q'])   ? sanitize_text_field($_GET['q'])   : '';
    $cat = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : '';
    $loc = isset($_GET['loc']) ? sanitize_text_field($_GET['loc']) : '';
    $sort= isset($_GET['sort'])? sanitize_text_field($_GET['sort']): 'newest';

    $where='WHERE f.status=\'active\''; $params=[];
    if($q)  { $where.=" AND (f.title LIKE %s OR f.description LIKE %s)"; $params[]="%".$wpdb->esc_like($q)."%"; $params[]="%".$wpdb->esc_like($q)."%"; }
    if($cat){ $where.=" AND f.category=%s"; $params[]=$cat; }
    if($loc){ $where.=" AND f.location LIKE %s"; $params[]="%".$wpdb->esc_like($loc)."%"; }
    $order = $sort==='most_funded' ? 'f.raised_amount DESC' : ($sort==='ending_soon' ? 'f.deadline ASC' : 'f.created_at DESC');
    $sql="SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID {$where} ORDER BY {$order}";
    $funds = !empty($params)?$wpdb->get_results($wpdb->prepare($sql,...$params)):$wpdb->get_results($sql); // phpcs:ignore
    $cats  = kbf_get_categories();
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $fund_details_url = kbf_get_page_url('fund_details');
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $total_active = count($funds);
    ob_start();
    ?>

    <!-- ================== CSS ================== -->
    <style>
    .kbf-user-ui{
    font-family: "Poppins",system-ui,-apple-system,sans-serif;
    color:#0f1115;
    background:transparent;
    border-radius:0;
    padding:0;
}
    .kbf-user-ui .kbf-card,
    .kbf-user-ui .kbf-fund-card,
    .kbf-user-ui .kbf-filter-bar{
        border-radius:18px;
        border:1px solid #edf0f4;
        box-shadow:0 18px 40px rgba(16,24,40,0.08);
    }
    .kbf-user-ui .kbf-btn-primary{
        background: linear-gradient(135deg, #5ba8f5 0%, #3d8ef0 50%, #2070e0 100%);
        color: #ffffff;
        border-color: transparent;
        box-shadow:
            0 1px 2px rgba(32, 112, 224, 0.20),
            0 4px 14px rgba(42, 120, 220, 0.28),
            0 0 0 0px rgba(111, 182, 255, 0),
            inset 0 1px 0 rgba(255, 255, 255, 0.18);
        font-weight: 600;
        letter-spacing: 0.01em;
        position: relative;
        isolation: isolate;
    }
    .kbf-user-ui .kbf-btn-primary::before{
        content: '';
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        background: linear-gradient(135deg, #7ec4ff 0%, #5aaaf8 40%, #2878e8 100%);
        opacity: 0;
        z-index: -1;
        transition: opacity .3s ease;
    }
    .kbf-user-ui .kbf-btn-primary:visited,
    .kbf-user-ui .kbf-btn-primary:active{ color:#ffffff; }
    .kbf-user-ui .kbf-btn-primary:hover{
        transform: translateY(-2px);
        box-shadow:
            0 1px 3px rgba(32, 112, 224, 0.15),
            0 8px 24px rgba(42, 120, 220, 0.45),
            0 16px 40px rgba(61, 142, 240, 0.20),
            inset 0 1px 0 rgba(255, 255, 255, 0.25);
        filter: brightness(1.06);
    }
    .kbf-user-ui .kbf-btn-primary:hover::before{ opacity: 1; }
    .kbf-user-ui .kbf-btn-primary:active{
        transform: translateY(0px);
        box-shadow:
            0 1px 2px rgba(32, 112, 224, 0.20),
            0 4px 14px rgba(42, 120, 220, 0.28),
            inset 0 1px 0 rgba(255, 255, 255, 0.18);
        transition: transform .1s ease, box-shadow .1s ease, filter .1s ease;
    }
    .kbf-user-ui .kbf-btn-secondary{
        background:#ffffff;
        border:1px solid #dfe7f3;
        color:#344055;
    }
    .kbf-user-ui .kbf-meta,
    .kbf-user-ui .kbf-browse-tab,
    .kbf-user-ui .kbf-browse-search input{
        color:#4f5a6b;
    }
    .kbf-browse-shell{display:grid;grid-template-columns:84px 1fr;gap:22px;}
    .kbf-browse-sidebar{background:var(--kbf-slate-lt);border:1px solid var(--kbf-border);border-radius:18px;padding:16px;display:flex;flex-direction:column;align-items:center;gap:16px;min-height:620px;position:sticky;top:24px;}
    .kbf-browse-sidebar .kbf-side-logo{width:48px;height:48px;border-radius:14px;background:var(--kbf-accent-lt);color:var(--kbf-navy);display:flex;align-items:center;justify-content:center;font-weight:800;}
    .kbf-side-btn{width:44px;height:44px;border-radius:14px;border:1px solid var(--kbf-border);display:flex;align-items:center;justify-content:center;color:var(--kbf-slate);background:#fff;}
    .kbf-side-btn.active{background:var(--kbf-accent);color:#fff;border-color:var(--kbf-accent);}
    .kbf-browse-main{min-width:0;}
    .kbf-browse-topbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px;}
    .kbf-browse-search{flex:1;max-width:520px;display:flex;align-items:center;gap:8px;background:var(--kbf-slate-lt);border:1px solid var(--kbf-border);border-radius:14px;padding:10px 14px;}
    .kbf-browse-search input{border:none;background:transparent;outline:none;width:100%;font-size:13.5px;color:var(--kbf-text);}
    .kbf-browse-actions{display:flex;gap:10px;align-items:center;}
    .kbf-browse-header{margin:6px 0 8px;}
    .kbf-browse-header h2{margin:0;font-size:24px;font-weight:800;color:var(--kbf-navy);}
    .kbf-browse-tabs{display:flex;gap:14px;align-items:center;margin:12px 0 16px;flex-wrap:wrap;}
    .kbf-browse-tab{font-size:13px;font-weight:600;color:var(--kbf-slate);text-decoration:none;position:relative;padding-bottom:6px;}
    .kbf-browse-tab.active{color:var(--kbf-navy);}
    .kbf-browse-tab.active::after{content:'';position:absolute;left:0;bottom:0;width:18px;height:4px;border-radius:999px;background:var(--kbf-accent);}
    .kbf-fund-card{background:#fff;border:1px solid var(--kbf-border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .2s,transform .15s;}
    .kbf-fund-card:hover{box-shadow:0 8px 28px rgba(15,32,68,.13);transform:translateY(-2px);}
    .kbf-fund-photo{width:100%;height:190px;object-fit:cover;background:linear-gradient(135deg,var(--kbf-navy),var(--kbf-navy-light));display:flex;align-items:center;justify-content:center;position:relative;}
    .kbf-fund-photo-placeholder{width:100%;height:190px;background:linear-gradient(135deg,#0f2044 0%,#243b78 100%);display:flex;align-items:center;justify-content:center;position:relative;}
    .kbf-fund-photo img{width:100%;height:190px;object-fit:cover;display:block;}
    .kbf-fund-cat-badge{position:absolute;top:12px;left:12px;background:rgba(15,32,68,.85);backdrop-filter:blur(4px);color:var(--kbf-accent);padding:4px 10px;border-radius:99px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
    .kbf-fund-days-badge{position:absolute;top:12px;right:12px;padding:4px 10px;border-radius:99px;font-size:10.5px;font-weight:800;}
    .kbf-filter-bar{background:#fff;border:1px solid var(--kbf-border);border-radius:var(--kbf-radius);padding:16px 18px;margin-bottom:18px;}
    .kbf-sort-pills{display:flex;gap:6px;flex-wrap:wrap;}
    .kbf-sort-pill{padding:6px 14px;border-radius:99px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid var(--kbf-border);color:var(--kbf-slate);transition:all .15s;}
    .kbf-sort-pill:hover,.kbf-sort-pill.active{background:var(--kbf-navy);color:#fff;border-color:var(--kbf-navy);}
    .kbf-browse-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:22px;}
    @media(max-width:900px){.kbf-browse-shell{grid-template-columns:1fr;}.kbf-browse-sidebar{position:static;min-height:auto;flex-direction:row;justify-content:space-between;padding:12px;}}
    @media(max-width:640px){.kbf-browse-grid{grid-template-columns:1fr;}.kbf-browse-search{max-width:100%;}}
    </style>
    
    
    

    
    
    <style>
    .kbf-user-ui{
    font-family: "Poppins",system-ui,-apple-system,sans-serif;
    color:#0f1115;
    background:transparent;
    border-radius:0;
    padding:0;
}
    .kbf-user-ui .kbf-btn{font-weight:400;}
    .kbf-user-ui .kbf-card,
    .kbf-user-ui .kbf-fund-card,
    .kbf-user-ui .kbf-filter-bar{
        border-radius:18px;
        border:1px solid #edf0f4;
        box-shadow:0 18px 40px rgba(16,24,40,0.08);
        transition:transform .2s ease, box-shadow .2s ease;
    }
    .kbf-user-ui .kbf-card:hover,
    .kbf-user-ui .kbf-fund-card:hover{
        transform:translateY(-3px);
        box-shadow:0 16px 34px rgba(15,23,42,0.08);
    }
    .kbf-user-ui .kbf-btn-primary{
        background: linear-gradient(135deg, #5ba8f5 0%, #3d8ef0 50%, #2070e0 100%);
        color: #ffffff;
        border-color: transparent;
        box-shadow:
            0 1px 2px rgba(32, 112, 224, 0.20),
            0 4px 14px rgba(42, 120, 220, 0.28),
            0 0 0 0px rgba(111, 182, 255, 0),
            inset 0 1px 0 rgba(255, 255, 255, 0.18);
        font-weight: 600;
        letter-spacing: 0.01em;
        position: relative;
        isolation: isolate;
    }
    .kbf-user-ui .kbf-btn-primary::before{
        content: '';
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        background: linear-gradient(135deg, #7ec4ff 0%, #5aaaf8 40%, #2878e8 100%);
        opacity: 0;
        z-index: -1;
        transition: opacity .3s ease;
    }
    .kbf-user-ui .kbf-btn-primary:visited,
    .kbf-user-ui .kbf-btn-primary:active{ color:#ffffff; }
    .kbf-user-ui .kbf-btn-primary:hover{
        transform: translateY(-2px);
        box-shadow:
            0 1px 3px rgba(32, 112, 224, 0.15),
            0 8px 24px rgba(42, 120, 220, 0.45),
            0 16px 40px rgba(61, 142, 240, 0.20),
            inset 0 1px 0 rgba(255, 255, 255, 0.25);
        filter: brightness(1.06);
    }
    .kbf-user-ui .kbf-btn-primary:hover::before{ opacity: 1; }
    .kbf-user-ui .kbf-btn-primary:active{
        transform: translateY(0px);
        box-shadow:
            0 1px 2px rgba(32, 112, 224, 0.20),
            0 4px 14px rgba(42, 120, 220, 0.28),
            inset 0 1px 0 rgba(255, 255, 255, 0.18);
        transition: transform .1s ease, box-shadow .1s ease, filter .1s ease;
    }
    .kbf-user-ui .kbf-btn-secondary{
        background:#ffffff;
        border:1px solid #dfe7f3;
        color:#344055;
    }
    .kbf-user-ui .kbf-meta,
    .kbf-user-ui .kbf-text-sm,
    .kbf-user-ui .kbf-browse-tab,
    .kbf-user-ui .kbf-browse-search input{
        color:#4f5a6b;
    }
    .kbf-dashboard-topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        padding:8px 4px 14px;
        background:transparent;
        border:none;
        border-radius:0;
        box-shadow:none;
        margin-bottom:10px;
    }
    .kbf-dashboard-brand{
        display:flex;
        align-items:center;
        gap:10px;
        font-weight:800;
        color:#0f172a;
        font-size:15px;
    }
    .kbf-dashboard-brand .kbf-logo-dot{
        width:26px;height:26px;border-radius:10px;
        background:linear-gradient(135deg,#79c0ff 0%,#4a98ff 100%);
        display:inline-flex;align-items:center;justify-content:center;
        color:#fff;font-weight:800;font-size:12px;
        box-shadow:0 8px 18px rgba(79,147,255,0.35);
    }
    .kbf-dashboard-nav{
        display:flex;
        gap:18px;
        font-size:12.5px;
        color:var(--kbf-muted);
        align-items:center;
        flex-wrap:wrap;
    }
    .kbf-dashboard-nav a{
        position:relative;
        text-decoration:none;
        color:#64748b;
        font-weight:600;
    }
    .kbf-dashboard-nav a::after{
        content:'';
        position:absolute;
        left:0;
        bottom:-8px;
        width:0;
        height:2px;
        border-radius:999px;
        background:#4a98ff;
        transition:width .2s ease;
    }
    .kbf-dashboard-nav a:hover::after,
    .kbf-dashboard-nav a.active::after{ width:100%; }
    .kbf-dashboard-nav a.active{ color:#1f2a44; }
    .kbf-dashboard-actions{
        display:flex;align-items:center;gap:10px;
    }
    .kbf-dashboard-avatar{
        width:34px;height:34px;border-radius:50%;
        border:2px solid #e5efff;object-fit:cover;
        box-shadow:0 8px 18px rgba(16,24,40,0.12);
    }
    .kbf-hero-wrap{
        padding:10px 6px 0;
    }
    .kbf-hero-banner{
        background:linear-gradient(135deg,#edf4ff 0%,#ffffff 50%,#e5f0ff 100%);
        background-image:
          radial-gradient(140% 160% at 0% 0%, rgba(79,147,255,0.28) 0%, rgba(79,147,255,0) 55%),
          radial-gradient(140% 160% at 100% 0%, rgba(161,210,255,0.30) 0%, rgba(161,210,255,0) 55%),
          linear-gradient(135deg,#edf4ff 0%,#ffffff 50%,#e5f0ff 100%);
        border:1.5px solid #cfe0f7;
        border-radius:22px;
        padding:22px 24px;
        box-shadow:none;
        margin-bottom:18px;
    }
    .kbf-user-ui,
    .kbf-hero-banner{
        background-image:none !important;
    }
    .kbf-user-ui{
    font-family: "Poppins",system-ui,-apple-system,sans-serif;
    color:#0f1115;
    background:transparent;
    border-radius:0;
    padding:0;
}
    .kbf-hero-title{
        font-size:28px;
        font-weight:800;
        color:#0f172a;
        margin:0 0 6px;
    }
    .kbf-hero-sub{
        color:#4b5563;
        font-size:13.5px;
        margin:0 0 18px;
        max-width:520px;
    }
    .kbf-hero-grid{
        display:grid;
        grid-template-columns:1.2fr 1fr;
        gap:18px;
        margin-bottom:20px;
    }
    .kbf-hero-card{
        background:linear-gradient(180deg,#ffffff 0%,#f7faff 100%);
        border:1.5px solid #dfe7f3;
        border-radius:20px;
        padding:18px 20px;
        box-shadow:none;
        display:flex;
        flex-direction:column;
        gap:10px;
        min-height:150px;
        position:relative;
        overflow:hidden;
    }
    .kbf-hero-card.kbf-hero-primary{
        background:linear-gradient(135deg,#2f7bdc 0%,#4a98ff 100%);
        border:1.5px solid #8cc0ff;
        color:#fff;
    }
    .kbf-hero-card h4{
        margin:0;
        font-size:16px;
        font-weight:700;
    }
    .kbf-hero-card p{
        margin:0;
        font-size:12.5px;
        color:inherit;
        opacity:.9;
    }
    .kbf-hero-card .kbf-hero-icon{
        width:34px;height:34px;border-radius:12px;
        display:inline-flex;align-items:center;justify-content:center;
        background:#eef4ff;color:#1f2a44;
    }
    .kbf-hero-card.kbf-hero-primary .kbf-hero-icon{
        background:rgba(255,255,255,.2);
        color:#fff;
    }
    @media (max-width: 900px){
        .kbf-hero-grid{ grid-template-columns:1fr; }
        .kbf-dashboard-topbar{ flex-wrap:wrap; }
    }
    .kbf-user-ui .kbf-modal-overlay{
        position:fixed;
        inset:0;
        display:flex;
        align-items:center;
        justify-content:center;
        background:rgba(11,20,38,0.45);
        backdrop-filter:blur(6px);
        padding:24px;
        z-index:9999;
    }
    .kbf-user-ui .kbf-modal{
        width:100%;
        max-width:720px;
        background:#fff;
        border-radius:22px;
        border:1px solid #dfe7f3;
        box-shadow:0 30px 80px rgba(15,40,80,0.22);
        overflow:hidden;
        max-height:90vh;
        display:flex;
        flex-direction:column;
    }
    .kbf-user-ui .kbf-modal.kbf-modal-sm{
        max-width:520px;
    }
    .kbf-user-ui .kbf-modal-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:18px 20px;
        background:
            radial-gradient(520px 200px at 10% -40%, rgba(111,182,255,0.25), transparent 70%),
            #f8fbff;
        border-bottom:1px solid #edf0f4;
    }
    .kbf-user-ui .kbf-modal-header h3{
        margin:0;
        font-size:16px;
        font-weight:600;
        color:#0d1a2e;
    }
    .kbf-user-ui .kbf-modal-close{
        width:32px;
        height:32px;
        border-radius:50%;
        border:1px solid #dfe7f3;
        background:#fff;
        color:#6b7a90;
        font-size:18px;
        line-height:1;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        transition:transform .15s ease, box-shadow .2s ease;
    }
    .kbf-user-ui .kbf-modal-close:hover{
        transform:translateY(-1px);
        box-shadow:0 8px 18px rgba(15,40,80,0.12);
    }
    .kbf-user-ui .kbf-modal-body{
        padding:20px;
        background:#fff;
        overflow-y:auto;
        max-height:70vh;
    }
    .kbf-user-ui .kbf-modal-footer{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        padding:16px 20px 20px;
        background:#fbfcff;
        border-top:1px solid #edf0f4;
    }
    .kbf-user-ui .kbf-modal input,
    .kbf-user-ui .kbf-modal select,
    .kbf-user-ui .kbf-modal textarea{
        border-radius:12px;
        border:1.5px solid #e2e8f0;
        background:#fff;
    }
    .kbf-user-ui .kbf-field-error{
        margin-top:6px;
        font-size:11.5px;
        color:#e11d48;
    }
    .kbf-user-ui .kbf-desc-counter{
        display:block;
        margin-top:6px;
        font-size:11.5px;
        color:#4f5a6b;
    }
    .kbf-user-ui .kbf-char-count{
        display:block;
        margin-top:6px;
        font-size:11.5px;
        color:#4f5a6b;
    }
    .kbf-user-ui .kbf-title-counter{
        display:block;
        margin-top:6px;
        font-size:11.5px;
        color:#4f5a6b;
    }
    html.kbf-modal-lock, body.kbf-modal-lock { overflow: hidden; }
    </style>

    
    <!-- ================== HTML ================== -->
    <div class="kbf-wrap kbf-user-ui">

    <?php echo kbf_role_nav('sponsor'); ?>

    <!-- MODAL: Sponsor -->
    <div id="kbf-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header"><h3>Sponsor This Fund</h3><button class="kbf-modal-close" onclick="kbfSponsorClose()">&times;</button></div>
        <div class="kbf-modal-body">
          <div id="kbf-fund-preview" style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:18px;"></div>
          <form id="kbf-sponsor-form" onsubmit="return false;">
            <input type="hidden" name="fund_id" id="sponsor-fund-id">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Organization</label><input type="text" name="sponsor_name" id="sponsor-name-field" placeholder="Your name, company, or org"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;">
                <label class="kbf-checkbox-row"><input type="checkbox" id="anon-check" onchange="document.getElementById('sponsor-name-field').disabled=this.checked"> Sponsor Anonymously</label>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Amount (PHP) *</label>
              <input type="number" name="amount" placeholder="Enter amount" min="10" step="1" required>
              <div id="kbf-sponsor-limit" class="kbf-meta" style="margin-top:4px;"></div>
            </div>
            <div class="kbf-form-group"><label>Message (optional)</label><textarea name="message" rows="2" placeholder="Leave an encouraging message..."></textarea></div>
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
            <?php else: ?>
            <div id="kbf-payment-placeholder" class="kbf-payment-placeholder" style="display:none;"><strong>Payment API Integration Point</strong><span id="kbf-payment-label"></span><br><small>Hook: <code>do_action('kbf_process_payment', $method, $amount, $fund_id)</code></small></div>
            <?php endif; ?>
            <div id="kbf-sponsor-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfSponsorClose()">Cancel</button>
          <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSubmitSponsor('<?php echo $nonce_sponsor; ?>')">Confirm Sponsorship</button>
        </div>
      </div>
    </div>

    <!-- MODAL: Report -->
    <div id="kbf-modal-report" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Report This Fund</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-report').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-report-form">
            <input type="hidden" name="fund_id" id="report-fund-id">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email" placeholder="your@email.com"></div>
            <div class="kbf-form-group"><label>Reason *</label><select name="reason" required><option value="">Select Reason</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Information</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select></div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" placeholder="Describe the issue..." required></textarea></div>
            <div id="kbf-report-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-report').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbfSubmitReport('<?php echo $nonce_report; ?>')">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- MODAL: Organizer -->
    <div id="kbf-modal-organizer" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal"><div class="kbf-modal-header"><h3>Organizer Profile</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-organizer').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body" id="kbf-organizer-body"><div style="text-align:center;padding:30px;color:var(--kbf-slate);">Loading...</div></div>
      </div>
    </div>

    <div class="kbf-browse-shell">
      <aside class="kbf-browse-sidebar">
        <div class="kbf-side-logo">KB</div>
        <div class="kbf-side-btn active" title="Browse">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4h8a1 1 0 011 1v6a1 1 0 01-1 1H3a1 1 0 01-1-1V5a1 1 0 011-1zm10 0h8a1 1 0 011 1v14a1 1 0 01-1 1h-8a1 1 0 01-1-1V5a1 1 0 011-1zM3 14h8a1 1 0 011 1v4a1 1 0 01-1 1H3a1 1 0 01-1-1v-4a1 1 0 011-1z"/></svg>
        </div>
        <div class="kbf-side-btn" title="Favorites">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21s-7-4.35-9.33-8.02C.5 9.5 2.5 6 6 6c2 0 3.33 1.02 4 2 0.67-0.98 2-2 4-2 3.5 0 5.5 3.5 3.33 6.98C19 16.65 12 21 12 21z"/></svg>
        </div>
        <div class="kbf-side-btn" title="Settings">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94a7.5 7.5 0 000-1.88l2.03-1.58a.5.5 0 00.12-.64l-1.92-3.32a.5.5 0 00-.6-.22l-2.39.96a7.48 7.48 0 00-1.63-.94l-.36-2.54a.5.5 0 00-.5-.42h-3.84a.5.5 0 00-.5.42l-.36 2.54a7.48 7.48 0 00-1.63.94l-2.39-.96a.5.5 0 00-.6.22L2.7 8.84a.5.5 0 00.12.64l2.03 1.58a7.5 7.5 0 000 1.88L2.82 14.52a.5.5 0 00-.12.64l1.92 3.32c.14.24.43.34.6.22l2.39-.96c.5.39 1.05.72 1.63.94l.36 2.54c.04.24.25.42.5.42h3.84c.25 0 .46-.18.5-.42l.36-2.54c.58-.22 1.13-.55 1.63-.94l2.39.96c.17.12.46.02.6-.22l1.92-3.32a.5.5 0 00-.12-.64l-2.03-1.58zM12 15.5a3.5 3.5 0 110-7 3.5 3.5 0 010 7z"/></svg>
        </div>
      </aside>
      <div class="kbf-browse-main">
        <div class="kbf-browse-topbar">
          <form method="GET" class="kbf-browse-search" id="kbf-browse-search-form">
            <input type="text" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Search funds, organizers, or causes...">
            <button type="submit" style="border:none;background:none;cursor:pointer;color:var(--kbf-slate);">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
          </form>
          <div class="kbf-browse-actions">
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url(add_query_arg('kbf_tab','overview',kbf_get_page_url('dashboard'))); ?>">Start a campaign</a>
          </div>
        </div>

        <div class="kbf-browse-header">
          <h2>All Projects</h2>
          <div style="font-size:13px;color:var(--kbf-slate);margin-top:4px;">
            Discover <?php echo $total_active; ?> active cause<?php echo $total_active!==1?'s':''; ?> near you.
          </div>
        </div>

        <div class="kbf-browse-tabs">
          <a class="kbf-browse-tab <?php echo !$sort || $sort==='newest'?'active':''; ?>" href="<?php echo esc_url(add_query_arg('sort','newest')); ?>">All Projects</a>
          <a class="kbf-browse-tab <?php echo $sort==='most_funded'?'active':''; ?>" href="<?php echo esc_url(add_query_arg('sort','most_funded')); ?>">Popular</a>
          <a class="kbf-browse-tab <?php echo $sort==='ending_soon'?'active':''; ?>" href="<?php echo esc_url(add_query_arg('sort','ending_soon')); ?>">Ending Soon</a>
        </div>

        <div class="kbf-filter-bar">
          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
              <span style="font-size:12px;font-weight:700;color:var(--kbf-slate);text-transform:uppercase;letter-spacing:.5px;">Category:</span>
              <a href="<?php echo esc_url(add_query_arg('cat','')); ?>" class="kbf-sort-pill <?php echo !$cat?'active':''; ?>">All</a>
              <?php foreach($cats as $c): ?><a href="<?php echo esc_url(add_query_arg('cat',$c)); ?>" class="kbf-sort-pill <?php echo $cat===$c?'active':''; ?>"><?php echo $c; ?></a><?php endforeach; ?>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
              <span style="font-size:12px;font-weight:700;color:var(--kbf-slate);text-transform:uppercase;letter-spacing:.5px;">Sort:</span>
              <div class="kbf-sort-pills">
                <a href="<?php echo esc_url(add_query_arg('sort','newest')); ?>" class="kbf-sort-pill <?php echo $sort==='newest'||!$sort?'active':''; ?>">Newest</a>
                <a href="<?php echo esc_url(add_query_arg('sort','most_funded')); ?>" class="kbf-sort-pill <?php echo $sort==='most_funded'?'active':''; ?>">Most Funded</a>
                <a href="<?php echo esc_url(add_query_arg('sort','ending_soon')); ?>" class="kbf-sort-pill <?php echo $sort==='ending_soon'?'active':''; ?>">Ending Soon</a>
              </div>
            </div>
          </div>
          <?php if($loc): ?>
          <div style="margin-top:10px;display:flex;align-items:center;gap:6px;font-size:13px;color:var(--kbf-slate);">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <strong><?php echo esc_html($loc); ?></strong> <a href="<?php echo esc_url(remove_query_arg('loc')); ?>" style="color:var(--kbf-red);margin-left:4px;text-decoration:none;">× Remove</a>
          </div>
          <?php endif; ?>
        </div>

    <!-- Fund grid -->
    <?php if(empty($funds)): ?>
    <div class="kbf-empty" style="padding:80px 20px;">
      <svg width="52" height="52" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 16px;display:block;opacity:.3;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <p style="font-size:16px;font-weight:600;color:var(--kbf-navy);margin-bottom:6px;">No funds found</p>
      <p style="color:var(--kbf-slate);">Try adjusting your search or filters.</p>
      <?php if($q||$cat||$loc): ?><a href="?" class="kbf-btn kbf-btn-primary" style="margin-top:16px;">Clear All Filters</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="kbf-browse-grid">
      <?php foreach($funds as $f):
        $pct   = $f->goal_amount>0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $days  = $f->deadline ? max(0,ceil((strtotime($f->deadline)-time())/86400)) : null;
        $photos = $f->photos ? json_decode($f->photos,true) : [];
        $cover  = !empty($photos[0]) ? $photos[0] : null;
        $fund_token = function_exists('kbf_get_or_create_fund_token') ? kbf_get_or_create_fund_token($f->id) : '';
        $detail_url = esc_url(add_query_arg('fund', $fund_token ?: $f->id, $fund_details_url));
        $days_color = $days!==null&&$days<7 ? '#fca5a5' : 'rgba(255,255,255,.85)';
        $days_bg    = $days!==null&&$days<7 ? 'rgba(220,38,38,.85)' : 'rgba(15,32,68,.7)';
      ?>
      <div class="kbf-fund-card">
        <!-- Photo hero -->
        <a href="<?php echo $detail_url; ?>" style="text-decoration:none;display:block;">
          <?php if($cover): ?>
          <div class="kbf-fund-photo" style="position:relative;">
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($f->title); ?>" style="width:100%;height:190px;object-fit:cover;display:block;">
            <div class="kbf-fund-cat-badge"><?php echo esc_html($f->category); ?></div>
            <?php if($days!==null): ?><div class="kbf-fund-days-badge" style="background:<?php echo $days_bg; ?>;color:<?php echo $days_color; ?>;backdrop-filter:blur(4px);"><?php echo $days; ?>d left</div><?php endif; ?>
          </div>
          <?php else: ?>
          <div class="kbf-fund-photo-placeholder" style="position:relative;">
            <svg width="48" height="48" fill="none" stroke="rgba(255,255,255,.2)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <div class="kbf-fund-cat-badge"><?php echo esc_html($f->category); ?></div>
            <?php if($days!==null): ?><div class="kbf-fund-days-badge" style="background:<?php echo $days_bg; ?>;color:<?php echo $days_color; ?>;"><?php echo $days; ?>d left</div><?php endif; ?>
          </div>
          <?php endif; ?>
        </a>

        <!-- Card body -->
        <div style="padding:18px;flex:1;display:flex;flex-direction:column;">
          <a href="<?php echo $detail_url; ?>" style="text-decoration:none;">
            <h4 style="font-size:15.5px;font-weight:700;color:var(--kbf-navy);margin:0 0 6px;line-height:1.4;"><?php echo esc_html($f->title); ?></h4>
          </a>
          <p style="font-size:13px;color:var(--kbf-slate);margin:0 0 12px;flex:1;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.55;"><?php echo esc_html(wp_trim_words($f->description,18)); ?></p>

          <!-- Location + Organizer -->
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--kbf-slate);margin-bottom:12px;gap:6px;flex-wrap:wrap;">
            <span style="display:flex;align-items:center;gap:4px;">
              <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <?php echo esc_html($f->location); ?>
            </span>
            <button onclick="kbfViewOrganizer(<?php echo $f->business_id; ?>)" style="background:none;border:none;color:var(--kbf-blue);cursor:pointer;font-size:12px;padding:0;font-weight:600;">
              <?php
                $org_token = function_exists('kbf_get_or_create_organizer_token') ? kbf_get_or_create_organizer_token($f->business_id) : '';
                $org_param = $org_token ? ['organizer'=>$org_token] : ['organizer_id'=>$f->business_id];
              ?>
              by <a href="<?php echo esc_url(add_query_arg($org_param, kbf_get_page_url('organizer_profile'))); ?>" style="color:inherit;text-decoration:none;font-weight:700;"><?php echo esc_html($f->organizer_name?:'Organizer'); ?></a>
            </button>
          </div>

          <!-- Progress -->
          <div class="kbf-progress-wrap" style="margin-bottom:8px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:16px;">
            <span><strong style="color:var(--kbf-navy);font-size:14px;">₱<?php echo number_format($f->raised_amount,0); ?></strong> <span style="color:var(--kbf-slate);">raised</span></span>
            <span style="color:var(--kbf-slate);"><?php echo round($pct); ?>% of ₱<?php echo number_format($f->goal_amount,0); ?></span>
          </div>

          <!-- Actions -->
          <div style="display:grid;grid-template-columns:1fr auto auto auto;gap:8px;align-items:center;">
            <button class="kbf-btn kbf-btn-primary" style="font-size:13px;" onclick="kbfOpenSponsor(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>',<?php echo $f->goal_amount; ?>,<?php echo $f->raised_amount; ?>,'<?php echo esc_js(isset($cover) ? $cover : ''); ?>')">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
              Sponsor
            </button>
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-secondary kbf-btn-sm" title="View details">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')" title="Share">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            </button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfOpenReport(<?php echo $f->id; ?>)" title="Report">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
      </div>
    </div>
    </div><!-- .kbf-wrap -->
    
    <!-- ================== JS ================== -->
    <script>
    window.kbfOpenReport=function(id){document.getElementById('report-fund-id').value=id;document.getElementById('kbf-modal-report').style.display='flex';};
    window.kbfSponsorClose=function(){document.getElementById('kbf-modal-sponsor').style.display='none';};
    window.kbfOpenSponsor=function(id,title,goal,raised,img){
        document.getElementById('sponsor-fund-id').value=id;
        const pct=goal>0?Math.min(100,Math.round((raised/goal)*100)):0;
        const remaining = goal>0 ? Math.max(0, goal - raised) : 0;
        const limitEl = document.getElementById('kbf-sponsor-limit');
        const amountEl = document.querySelector('#kbf-sponsor-form input[name="amount"]');
        if (remaining > 0) {
            if (limitEl) limitEl.textContent = 'Max allowed: ₱' + parseFloat(remaining).toLocaleString() + ' (remaining goal)';
            if (amountEl) amountEl.max = remaining;
        } else {
            if (limitEl) limitEl.textContent = '';
            if (amountEl) amountEl.removeAttribute('max');
        }
        document.getElementById('kbf-fund-preview').innerHTML=
            (img?'<img src="'+img+'" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:10px;display:block;">':'')
            +'<strong style="font-size:15px;color:var(--kbf-navy);">'+title+'</strong>'
            +'<div style="margin-top:8px;" class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:'+pct+'%"></div></div>'
            +'<div style="display:flex;justify-content:space-between;font-size:12px;margin-top:6px;color:var(--kbf-slate);"><span>₱'+parseFloat(raised).toLocaleString()+' raised</span><span>'+pct+'% funded</span></div>';
        document.getElementById('kbf-modal-sponsor').style.display='flex';
    };
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
    window.kbfSubmitSponsor=function(nonce){
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
        fd.append('is_anonymous',document.getElementById('anon-check').checked?'1':'0');
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
    window.kbfSubmitReport=function(nonce){
        const form=document.getElementById('kbf-report-form');
        const btn=document.querySelector('#kbf-modal-report .kbf-modal-footer .kbf-btn-danger');
        const msg=document.getElementById('kbf-report-msg');
        kbfSetBtnLoading(btn,true,'Submitting...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            msg.innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if(j.success)setTimeout(()=>{document.getElementById('kbf-modal-report').style.display='none';},2000);else{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    };
    window.kbfViewOrganizer=function(bizId){
        document.getElementById('kbf-modal-organizer').style.display='flex';
        kbfSetSkeleton(document.getElementById('kbf-organizer-body'), true);
        const fd=new FormData();fd.append('action','kbf_get_organizer_profile');fd.append('business_id',bizId);fd.append('nonce','<?php echo wp_create_nonce('kbf_sponsor'); ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            if(j.success){
                const d=j.data;
                const starSvg=(filled)=>{
                    const src = filled ? 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg' : 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg';
                    const filter = filled
                        ? 'invert(70%) sepia(85%) saturate(531%) hue-rotate(3deg) brightness(98%) contrast(92%)'
                        : 'invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%)';
                    return '<img src="'+src+'" width="14" height="14" style="filter:'+filter+';">';
                };
                const stars=Array.from({length:5},(_,i)=>starSvg(i<Math.round(parseFloat(d.rating)))).join('');
                const statusColor={'active':'var(--kbf-green)','completed':'var(--kbf-blue)'};
                const statusBg={'active':'var(--kbf-green-lt)','completed':'#dbeafe'};

                // Fund history HTML
                const fundHistory = d.funds&&d.funds.length
                    ? '<div style="margin-top:20px;"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;"><h4 style="font-size:13px;font-weight:800;color:var(--kbf-navy);margin:0;">Fund History</h4><span style="font-size:11.5px;color:var(--kbf-slate);">'+d.total_funds+' total fund'+(d.total_funds!==1?'s':'')+'</span></div>'
                      + d.funds.map(f=>'<div style="border:1px solid var(--kbf-border);border-radius:8px;padding:12px;margin-bottom:8px;">'
                        +'<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">'
                        +'<div style="font-weight:700;font-size:13.5px;color:var(--kbf-navy);flex:1;margin-right:8px;">'+f.title+'</div>'
                        +'<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:'+(statusBg[f.status]||'var(--kbf-slate-lt)')+';color:'+(statusColor[f.status]||'var(--kbf-slate)')+';white-space:nowrap;">'+f.status.toUpperCase()+'</span>'
                        +'</div>'
                        +'<div style="font-size:11.5px;color:var(--kbf-slate);margin-bottom:6px;">'+f.category+' &bull; '+f.sponsor_count+' sponsor'+(f.sponsor_count!==1?'s':'')+'</div>'
                        +'<div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:'+f.pct+'%"></div></div>'
                        +'<div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--kbf-slate);margin-top:4px;"><span>₱'+f.raised+' raised</span><span>'+f.pct+'% of ₱'+f.goal+'</span></div>'
                        +'</div>').join('')
                      + '</div>'
                    : '<div style="text-align:center;padding:16px;color:var(--kbf-slate);font-size:13px;margin-top:16px;">No fund history yet.</div>';

                // Reviews HTML
                const reviewsHtml = d.reviews&&d.reviews.length
                    ? '<div style="margin-top:20px;"><h4 style="font-size:13px;font-weight:800;color:var(--kbf-navy);margin:0 0 10px;">Recent Reviews</h4>'
                      + d.reviews.map(r=>'<div style="border:1px solid var(--kbf-border);border-radius:8px;padding:10px 12px;margin-bottom:8px;">'
                        +'<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">'
                        +'<div style="display:flex;gap:2px;">'+Array.from({length:5},(_,i)=>starSvg(i<r.rating)).join('')+'</div>'
                        +'<span style="font-size:11px;color:var(--kbf-slate);">'+r.date+'</span>'
                        +'</div>'
                        +(r.review?'<p style="font-size:12.5px;color:var(--kbf-text-sm);margin:0 0 4px;font-style:italic;">"'+r.review+'"</p>':'')
                        +'<div style="font-size:11px;color:var(--kbf-slate);">'+r.email+(r.fund_title?' &bull; on "'+r.fund_title+'"':'')+'</div>'
                        +'</div>').join('')
                      + '</div>'
                    : '';

                document.getElementById('kbf-organizer-body').innerHTML=
                // Header
                '<div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:16px;">'
                +(d.avatar_url?'<img src="'+d.avatar_url+'" style="width:62px;height:62px;border-radius:50%;object-fit:cover;border:2px solid var(--kbf-border);flex-shrink:0;">':'<div style="width:62px;height:62px;border-radius:50%;background:var(--kbf-navy);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><img src=\"https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg\" width=\"28\" height=\"28\" style=\"filter:invert(100%);\"></div>')
                +'<div><div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;"><strong style="font-size:16px;color:var(--kbf-navy);">'+d.display_name+'</strong>'
                +(d.is_verified?'<span class="kbf-badge kbf-badge-verified" style="font-size:10px;">Verified</span>':'')
                +'</div>'
                +'<div style="display:flex;gap:3px;align-items:center;margin-top:5px;">'+stars
                +'<span style="font-size:12px;color:var(--kbf-slate);margin-left:5px;"><strong>'+d.rating+'</strong>/5 &nbsp;&bull;&nbsp; '+d.rating_count+' review'+(d.rating_count!==1?'s':'')+'</span></div>'
                +(d.bio?'<p style="font-size:13px;color:var(--kbf-slate);margin:6px 0 0;line-height:1.55;">'+d.bio+'</p>':'')
                +'</div></div>'
                // Stats
                +'<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:4px;">'
                +'<div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px;text-align:center;"><div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--kbf-slate);margin-bottom:3px;">Raised</div><div style="font-size:15px;font-weight:800;color:var(--kbf-navy);">₱'+parseFloat(d.total_raised).toLocaleString()+'</div></div>'
                +'<div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px;text-align:center;"><div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--kbf-slate);margin-bottom:3px;">Sponsors</div><div style="font-size:15px;font-weight:800;color:var(--kbf-navy);">'+d.total_sponsors+'</div></div>'
                +'<div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px;text-align:center;"><div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--kbf-slate);margin-bottom:3px;">Funds</div><div style="font-size:15px;font-weight:800;color:var(--kbf-navy);">'+d.total_funds+'</div></div>'
                +'</div>'
                + fundHistory
                + reviewsHtml;
            } else {
                document.getElementById('kbf-organizer-body').innerHTML='<div class="kbf-alert kbf-alert-error">Profile not found.</div>';
            }
        }).catch(()=>{ document.getElementById('kbf-organizer-body').innerHTML='<div class="kbf-alert kbf-alert-error">Profile not found.</div>'; });
    };
    </script>
    <?php
    $c=ob_get_clean();
    return bntm_universal_container('Browse Funds -- KonekBayan',$c, ['show_topbar'=>false,'show_header'=>false]);
}


