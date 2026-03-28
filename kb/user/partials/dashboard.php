<?php
/* User dashboard shortcode */
function bntm_shortcode_kbf_dashboard() {
    if (!is_user_logged_in()) {
        return '<div class="kbf-wrap"><div class="kbf-alert kbf-alert-warning">Please log in to access your dashboard.</div></div>';
    }
    kbf_global_assets();
    $user        = wp_get_current_user();
    global $wpdb;
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $nav_profile = $wpdb->get_row($wpdb->prepare("SELECT avatar_url,is_verified FROM {$pt} WHERE business_id=%d", $user->ID));
    $business_id = $user->ID;
    $tab         = isset($_GET['kbf_tab']) ? sanitize_text_field($_GET['kbf_tab']) : 'find_funds';
    $nonce_create = wp_create_nonce('kbf_create_fund');
    $nonce_edit   = wp_create_nonce('kbf_update_fund');
    $nonce_cancel = wp_create_nonce('kbf_cancel_fund');
    $nonce_wd     = wp_create_nonce('kbf_withdrawal');
    $nonce_extend = wp_create_nonce('kbf_extend');
    $nonce_appeal = wp_create_nonce('kbf_appeal');
    $payment_state = isset($_GET['kbf_payment']) ? sanitize_text_field($_GET['kbf_payment']) : '';

    if ($payment_state === 'success') {
        $find_url = add_query_arg('kbf_tab', 'find_funds', kbf_get_page_url('dashboard'));
        ob_start();
    ?>
    
    <div class="kbf-user-ui">
          <div class="kbf-card" style="max-width:640px;margin:50px auto;padding:34px 30px;text-align:center;">
            <div style="font-size:26px;font-weight:800;color:var(--kbf-navy);margin-bottom:8px;">Thank You</div>
            <div style="font-size:14px;color:var(--kbf-slate);margin-bottom:22px;">Thank you for your donation or support.</div>
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($find_url); ?>">Find Funds</a>
          </div>
        </div>
        <?php
        return ob_get_clean();
    }

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
    width:100%;
    max-width:none;
    margin:0;
    --kbf-shadow: rgba(0, 0, 0, 0.05) 0px 2px 4px -1px, rgba(0, 0, 0, 0.04) 0px 1px 2px -1px;
    --kbf-shadow-lg: rgba(0, 0, 0, 0.06) 0px 6px 12px -3px, rgba(0, 0, 0, 0.05) 0px 3px 6px -2px;
}
    .kbf-user-ui h1{font-size:28px;font-weight:700;letter-spacing:-0.4px;color:#0d1a2e;margin:0 0 6px;line-height:1.2;}
    .kbf-user-ui h2{font-size:22px;font-weight:600;color:#0f172a;margin:0 0 6px;line-height:1.3;}
    .kbf-user-ui h3{font-size:16px;font-weight:600;color:#0f172a;margin:0 0 6px;line-height:1.35;}
    .kbf-user-ui h4{font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;line-height:1.4;}
    .kbf-user-ui p{font-size:13.5px;font-weight:400;color:#4f5a6b;line-height:1.65;margin:0;}
    .kbf-user-ui small,
    .kbf-user-ui .kbf-meta,
    .kbf-user-ui .kbf-text-sm{font-size:12.5px;font-weight:400;color:#4f5a6b;line-height:1.5;}
    .kbf-user-ui label{font-size:12.5px;font-weight:600;color:#4f5a6b;}
    .kbf-user-ui .kbf-table thead th{
        font-size:10.5px;
        font-weight:600;
        text-transform:uppercase;
        letter-spacing:.6px;
        color:#94a3b8;
    }
    .kbf-user-ui .kbf-table tbody td{font-size:12.5px;color:#0f172a;}
    .kbf-user-ui .kbf-btn{font-weight:600;}
    .kbf-user-ui .kbf-card,
    .kbf-user-ui .kbf-fund-card,
    .kbf-user-ui .kbf-filter-bar{
        border-radius:18px;
        border:1px solid #edf0f4;
        box-shadow:var(--kbf-shadow);
        transition:transform .2s ease, box-shadow .2s ease;
    }
    .kbf-user-ui .kbf-card{overflow:visible;position:relative;z-index:1;}
    .kbf-user-ui .kbf-card.is-menu-open{z-index:60;}
    .kbf-user-ui .kbf-card:hover,
    .kbf-user-ui .kbf-fund-card:hover{
        transform:translateY(-3px);
        box-shadow:var(--kbf-shadow-lg);
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
        background:#f8fafc;
        border:1px solid #e5e7eb;
        color:#334155;
    }
    .kbf-card-actions{
        display:flex;
        align-items:center;
        gap:8px;
        justify-content:flex-start;
        margin-top:12px;
    }
    .kbf-card-actions .kbf-btn-sm{
        height:32px;
        min-width:32px;
        padding:0 10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
    }
    .kbf-card-more-wrap{position:relative;}
    .kbf-card-more-menu{
        position:absolute;
        right:0;
        top:calc(100% + 8px);
        background:#fff;
        border:1px solid var(--kbf-border);
        border-radius:12px;
        box-shadow:var(--kbf-shadow);
        padding:6px;
        min-width:180px;
        display:none;
        z-index:50;
    }
    .kbf-card-more-menu.open{display:block;}
    .kbf-card-more-menu button{
        width:100%;
        justify-content:flex-start;
        gap:8px;
        margin:4px 0;
    }
    .kbf-user-ui .kbf-meta,
    .kbf-user-ui .kbf-text-sm,
    .kbf-user-ui .kbf-browse-tab,
    .kbf-user-ui .kbf-browse-search input{
        color:#4f5a6b;
    }
    .kbf-dashboard-shell{
        max-width:1120px;
        margin:0 auto;
        padding:76px 22px 0;
        box-sizing:border-box;
    }
    .kbf-tab-content{
        margin-bottom:24px;
    }
    .kbf-topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:18px;
        padding:10px 16px;
        min-height:56px;
        position:fixed;
        top:0;
        left:0;
        right:0;
        z-index:1000;
        background: rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom:1px solid transparent;
        transition:border-color .2s ease, box-shadow .2s ease;
        margin:0;
        width:100%;
        box-sizing:border-box;
    }
    .kbf-topbar.kbf-topbar-scrolled{
        border-bottom-color:#e2e8f0;
        box-shadow:0 4px 24px rgba(15,40,80,0.06);
    }
    .kbf-topbar-left{display:flex;align-items:center;gap:28px;flex-wrap:wrap;}
    .kbf-brand{display:flex;align-items:center;gap:10px;font-weight:800;color:#0f172a;font-size:15px;}
    .kbf-brand-text{color:#3d8ef0;font-family:'Shippori Antique B1','Poppins',system-ui,-apple-system,sans-serif;}
    .kbf-nav{
        display:flex;
        gap:20px;
        font-size:12.5px;
        color:#64748b;
        align-items:center;
        flex-wrap:wrap;
    }
    .kbf-nav a{position:relative;text-decoration:none;color:#64748b;font-weight:500;display:inline-flex;align-items:center;}
    .kbf-nav a::after{
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
    .kbf-nav a:hover::after,
    .kbf-nav a.active::after{width:100%;}
    .kbf-nav a.active{color:#1f2a44;}
    .kbf-actions{display:flex;align-items:center;gap:10px;}
    .kbf-actions .kbf-hamburger{order:1;}
    .kbf-actions .kbf-dashboard-user{order:0;}
    .kbf-hamburger{
        display:none;
        background:#fff;
        border:1px solid var(--kbf-border);
        border-radius:10px;
        padding:6px 8px;
        cursor:pointer;
        color:#64748b;
        align-items:center;
        justify-content:center;
    }
    .kbf-hamburger img{
        width:18px;height:18px;display:block;
        filter: invert(30%) sepia(10%) saturate(800%) hue-rotate(185deg) brightness(0.9);
    }
    .kbf-mobile-overlay{
        display:none;
        position:fixed;
        inset:0;
        background:rgba(0,0,0,0.45);
        z-index:998;
        animation:kbfOverlayIn .2s ease forwards;
    }
    .kbf-mobile-overlay.kbf-overlay-open{display:block;}
    @keyframes kbfOverlayIn{from{opacity:0}to{opacity:1}}
    .kbf-mobile-menu{
        position:fixed;
        top:0;left:0;right:0;
        z-index:999;
        border-radius:0;
        margin-top:0;
        transform:translateY(-110%);
        transition:transform .3s cubic-bezier(.4,0,.2,1);
        display:flex;
        flex-direction:column;
        overflow:hidden;
        background:#fff;
        border:1px solid var(--kbf-border);
        box-shadow:0 10px 26px rgba(15,40,80,0.18);
    }
    .kbf-mobile-menu.kbf-menu-open{transform:translateY(0);display:flex;}
    .kbf-mobile-menu-header{
        display:flex;align-items:center;justify-content:space-between;
        padding:14px 16px;border-bottom:1px solid var(--kbf-border);
        background:#fff;
    }
    .kbf-mobile-menu a{
        padding:13px 18px;
        font-size:13.5px;
        color:#0f172a;
        border-bottom:1px solid var(--kbf-border);
        transition:background .15s ease;
        text-align:center;
    }
    .kbf-mobile-menu a:last-of-type{border-bottom:none;}
    .kbf-mobile-menu-actions{
        display:flex;flex-direction:row;gap:8px;
        padding:12px 14px;border-top:1px solid var(--kbf-border);
        background:var(--kbf-slate-lt);
    }
    .kbf-mobile-menu-actions .kbf-btn{flex:1;justify-content:center;}

    .kbf-dashboard-avatar-wrap{position:relative;width:34px;height:34px;flex-shrink:0;}
    .kbf-dashboard-avatar-wrap .kbf-dashboard-avatar{width:34px;height:34px;border-radius:50%;border:1px solid var(--kbf-border);object-fit:cover;box-shadow:0 8px 18px rgba(16,24,40,0.12);display:block;}
    .kbf-dashboard-verified{position:absolute;right:-2px;bottom:-2px;width:14px;height:14px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 1px #fff;}
    .kbf-dashboard-verified::before{content:'';width:10px;height:10px;background:#1d4ed8;display:block;
        -webkit-mask:url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/patch-check-fill.svg') no-repeat center/contain;
        mask:url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/patch-check-fill.svg') no-repeat center/contain;}
    .kbf-dashboard-user{
        display:inline-flex;
        align-items:center;
        gap:8px;
        text-decoration:none;
        color:#4f5a6b;
        font-size:12.5px;
        font-weight:600;
    }
    .kbf-dashboard-user .kbf-dashboard-avatar-wrap{order:1;}
    .kbf-dashboard-user .kbf-dashboard-name{order:0;}
    .kbf-dashboard-user:hover{ color:#1f2a44; }
    .kbf-hero-wrap{
        padding:0;
        margin-bottom:14px;
    }
    .kbf-table-wrap{
        width:100%;
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
    }
    .kbf-table{
        min-width:980px;
        table-layout:auto;
    }
    .kbf-table th,
    .kbf-table td{
        white-space:nowrap;
    }
    .kbf-fund-amounts strong{
        font-weight:600;
    }
    .kbf-note-row td,
    .kbf-note-cell{
        background:var(--kbf-slate-lt);
        font-size:12px;
        padding:6px 14px;
        border-top:1px solid var(--kbf-border);
        white-space:normal !important;
    }
    .kbf-note-text{
        display:inline-block;
        padding-left:8px;
        color:var(--kbf-text-sm);
    }
    .kbf-cashout-table{
        min-width:760px;
        table-layout:fixed;
    }
    .kbf-cashout-table th,
    .kbf-cashout-table td{
        white-space:normal;
    }
    .kbf-cashout-table th:nth-child(1),
    .kbf-cashout-table td:nth-child(1){width:30%;}
    .kbf-cashout-table th:nth-child(2),
    .kbf-cashout-table td:nth-child(2){width:12%;}
    .kbf-cashout-table th:nth-child(3),
    .kbf-cashout-table td:nth-child(3){width:20%;}
    .kbf-cashout-table th:nth-child(4),
    .kbf-cashout-table td:nth-child(4){width:12%;}
    .kbf-cashout-table th:nth-child(5),
    .kbf-cashout-table td:nth-child(5){width:13%;}
    .kbf-cashout-table th:nth-child(6),
    .kbf-cashout-table td:nth-child(6){width:13%;}
    .kbf-cashout-table .kbf-note-cell{white-space:normal;}
    .kbf-cashout-table .kbf-note-row td,
    .kbf-cashout-table .kbf-note-cell{
        display:table-cell !important;
        width:auto !important;
    }
    .kbf-cashout-title{
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        overflow:hidden;
        white-space:normal;
        word-break:normal;
        overflow-wrap:break-word;
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
        margin-bottom:0;
        width:100%;
        box-sizing:border-box;
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
        .kbf-topbar{ flex-wrap:wrap; }
        .kbf-nav{ display:none; }
        .kbf-hamburger{ display:inline-flex; }
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
    .kbf-loading-overlay{
        position:fixed;
        inset:0;
        display:flex;
        align-items:center;
        justify-content:center;
        background:rgba(11,20,38,0.55);
        backdrop-filter:blur(6px);
        z-index:10000;
    }
    .kbf-loading-card{
        background:#ffffff;
        border:1px solid #dfe7f3;
        border-radius:22px;
        padding:24px 28px;
        box-shadow:0 30px 80px rgba(15,40,80,0.22);
        text-align:center;
        min-width:220px;
    }
    .kbf-loading-spinner{
        width:34px;
        height:34px;
        border-radius:50%;
        border:4px solid rgba(111,182,255,0.25);
        border-top-color:#6fb6ff;
        margin:0 auto 12px;
        animation:kbfSpin .8s linear infinite;
    }
    @keyframes kbfSpin { to { transform: rotate(360deg); } }
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

    
    <div class="kbf-user-ui">
    <div id="kbf-loading-overlay" class="kbf-loading-overlay" style="display:none;">
      <div class="kbf-loading-card">
        <div class="kbf-loading-spinner"></div>
        <div style="font-size:13px;color:#4f5a6b;">Submitting your fund...</div>
      </div>
    </div>
    
    <!-- ===== MODAL: Create Fund ===== -->
    <div id="kbf-modal-create" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Create New Fund</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-create')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-create-fund-form" enctype="multipart/form-data">
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Funding For *</label>
                <select name="funder_type" required>
                  <option value="yourself">Yourself</option>
                  <option value="someone_else">Someone Else</option>
                  <option value="animal_care">Animal Care</option>
                  <option value="charity_event">Charity or Event</option>
                </select>
              </div>
              <div class="kbf-form-group">
                <label>Category *</label>
                <select name="category" required>
                  <?php foreach (kbf_get_categories() as $c): ?>
                    <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Fund Title *</label>
              <input type="text" name="title" id="kbf-create-title" placeholder="Clear, compelling title" maxlength="50" required>
              <small class="kbf-title-counter">0 / 40</small>
            </div>
            <div class="kbf-form-group">
              <label>Description *</label>
              <textarea name="description" rows="10" maxlength="800" placeholder="Tell your story and why this fund matters..." required></textarea>
              <small class="kbf-desc-counter">0 / 800</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Goal Amount (PHP) *</label>
                <input type="number" name="goal_amount" placeholder="0.00" min="100" step="0.01" required>
              </div>
            <div class="kbf-form-group">
              <label>Deadline</label>
              <input type="date" name="deadline" min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
              <small>Optional — set an end date (minimum 7 days from today).</small>
            </div>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Contact Email *</label>
                <input type="email" name="email" required>
              </div>
              <div class="kbf-form-group">
                <label>Contact Phone *</label>
                <input type="text" name="phone" placeholder="+63 9XX XXX XXXX" required>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Province *</label>
              <select name="location" id="kbf-province" required>
                <option value="">Select Province</option>
                <?php foreach (kbf_get_provinces() as $p): ?>
                  <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                <?php endforeach; ?>
              </select>
              <small>Select your province first.</small>
            </div>
            <div class="kbf-form-group">
              <label>Municipality *</label>
              <select name="municipality" id="kbf-municipality" required disabled>
                <option value="">Select Municipality</option>
              </select>
              <small>Municipality list will load based on province.</small>
            </div>
            <div class="kbf-form-group">
              <label>Barangay *</label>
              <select name="barangay" id="kbf-barangay" required disabled>
                <option value="">Select Barangay</option>
              </select>
              <small>Barangay list will load based on municipality.</small>
            </div><div class="kbf-form-group">
              <label>Add Photos (up to 5)</label>
              <input type="file" name="photos[]" accept="image/*" multiple required>
              <small></small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label class="kbf-checkbox-row">
                <input type="checkbox" name="auto_return" value="1">
                Auto-return funds to sponsors if goal not met by deadline
              </label>
            </div>
            <div class="kbf-form-group">
              <label class="kbf-checkbox-row">
                <input type="checkbox" name="agree_terms" id="kbf-agree-terms" required>
                I agree to the <a href="<?php echo esc_url(kbf_get_page_url('terms')); ?>" target="_blank" rel="noopener noreferrer">Terms &amp; Agreement</a>.
              </label>
              <div class="kbf-field-error"></div>
            </div>
            <div id="kbf-create-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-create')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitCreate()">Submit for Review</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Edit Fund ===== -->
    <div id="kbf-modal-edit" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Edit Fund</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-edit')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-edit-fund-form" enctype="multipart/form-data">
            <input type="hidden" name="fund_id" id="edit-fund-id">
            <div class="kbf-form-group">
              <label>Title</label>
              <input type="text" name="title" id="edit-fund-title" maxlength="80" required>
              <small class="kbf-title-counter">0 / 80</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Description</label>
              <textarea name="description" id="edit-fund-desc" rows="10" maxlength="800" required></textarea>
              <small class="kbf-desc-counter">0 / 800</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Province</label>
              <select id="kbf-edit-province" required>
                <option value="">Select Province</option>
                <?php foreach (kbf_get_provinces() as $p): ?>
                  <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                <?php endforeach; ?>
              </select>
              <small>Select your province first.</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Municipality</label>
              <select id="kbf-edit-municipality" required disabled>
                <option value="">Select Municipality</option>
              </select>
              <small>Municipality list will load based on province.</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Barangay</label>
              <select id="kbf-edit-barangay" required disabled>
                <option value="">Select Barangay</option>
              </select>
              <small>Barangay list will load based on municipality.</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Deadline</label>
              <input type="date" name="deadline" id="edit-fund-deadline">
              <small>Optional — update the end date.</small>
            </div>
            <div class="kbf-form-group">
              <label class="kbf-checkbox-row">
                <input type="checkbox" name="auto_return" id="edit-fund-auto-return" value="1">
                Auto-return funds to sponsors if goal not met by deadline
              </label>
            </div>
            <input type="hidden" name="location" id="edit-fund-location-hidden">
            <div id="kbf-edit-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-edit')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitEdit()">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Withdrawal ===== -->
    <div id="kbf-modal-wd" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Request Withdrawal</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-wd')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-wd-form">
            <input type="hidden" name="fund_id" id="wd-fund-id">
            <input type="hidden" name="funder_name" value="<?php echo esc_attr(isset($user->display_name) ? $user->display_name : ''); ?>">
            <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:12px 14px;margin-bottom:16px;">
              <div style="font-size:12px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">Fund</div>
              <div id="wd-fund-title" style="font-size:14px;font-weight:700;color:var(--kbf-navy);margin-bottom:6px;"></div>
              <div style="font-size:13px;color:var(--kbf-green);font-weight:700;"><span id="wd-available-label"></span> available</div>
            </div>
            <div class="kbf-form-group">
              <label>Amount to Withdraw (PHP) *</label>
              <input type="number" name="amount" id="wd-amount" placeholder="0.00" min="1" step="0.01" required>
              <small style="color:var(--kbf-slate);font-size:11.5px;">Admin will review and process your request within 1-3 business days.</small>
            </div>
            <div class="kbf-form-group">
              <label>Withdrawal Method *</label>
              <select name="method" required>
                <option value="">Select Method</option>
                <option value="online_payment">Online Payment (GCash / Maya / E-Wallet)</option>
                <option value="bank_payment">Bank Payment (Bank Transfer / Over-the-Counter)</option>
              </select>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Account Name *</label>
                <input type="text" name="account_name" placeholder="Full name on account" required>
              </div>
              <div class="kbf-form-group">
                <label>Account Number *</label>
                <input type="text" name="account_number" placeholder="e.g. 09XX XXX XXXX" required>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Additional Details</label>
              <textarea name="account_details" rows="2" placeholder="Bank name, branch, or any other details..."></textarea>
            </div>
            <div id="kbf-wd-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-wd')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitWd()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Submit Request
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Appeal Suspension ===== -->
    <div id="kbf-modal-appeal" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Appeal Suspension</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-appeal')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-appeal-form">
            <input type="hidden" name="fund_id" id="kbf-appeal-fund-id">
            <div class="kbf-form-group">
              <label>Appeal Message *</label>
              <textarea name="message" rows="4" placeholder="Explain why this fund should be reinstated..." required></textarea>
            </div>
            <div id="kbf-appeal-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-appeal')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitAppeal('<?php echo $nonce_appeal; ?>')">Submit Appeal</button>
        </div>
      </div>
    </div>

    <!-- Topbar (Landing-style) -->
    <div class="kbf-mobile-overlay" id="kbf-mobile-overlay"></div>
    <div class="kbf-mobile-menu" id="kbf-mobile-menu">
      <div class="kbf-mobile-menu-header">
        <div class="kbf-brand">
          <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="ambag" style="width:22px;height:22px;object-fit:contain;border-radius:6px;">
          <span class="kbf-brand-text">ambag</span>
        </div>
        <button class="kbf-hamburger" type="button" onclick="kbfCloseMobileMenu()">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/x-lg.svg" alt="">
        </button>
      </div>
      <a href="?kbf_tab=overview" onclick="kbfCloseMobileMenu()">Home</a>
      <a href="?kbf_tab=sponsorships" onclick="kbfCloseMobileMenu()">Supporters</a>
      <a href="?kbf_tab=withdrawals" onclick="kbfCloseMobileMenu()">Cashout</a>
      <a href="?kbf_tab=find_funds" onclick="kbfCloseMobileMenu()">Explore</a>
      <div class="kbf-mobile-menu-actions">
        <a class="kbf-btn kbf-btn-secondary" href="?kbf_tab=profile">Profile</a>
        <a class="kbf-btn kbf-btn-primary" href="?kbf_tab=find_funds">Find Funds</a>
      </div>
    </div>

    <div class="kbf-topbar">
      <div class="kbf-topbar-left">
        <div class="kbf-brand">
          <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="ambag" style="width:22px;height:22px;object-fit:contain;border-radius:6px;">
          <span class="kbf-brand-text">ambag</span>
        </div>
        <nav class="kbf-nav">
          <a href="?kbf_tab=overview" class="<?php echo $tab==='overview'?'active':''; ?>">Home</a>
          <a href="?kbf_tab=sponsorships" class="<?php echo $tab==='sponsorships'?'active':''; ?>">Supporters</a>
          <a href="?kbf_tab=withdrawals" class="<?php echo $tab==='withdrawals'?'active':''; ?>">Cashout</a>
          <a href="?kbf_tab=find_funds" class="<?php echo $tab==='find_funds'?'active':''; ?>">Explore</a>
        </nav>
      </div>
      <div class="kbf-actions">
        <button class="kbf-hamburger" type="button" onclick="kbfToggleMobileMenu()">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/list.svg" alt="">
        </button>
        <?php
          $avatar_url = ($nav_profile && $nav_profile->avatar_url) ? $nav_profile->avatar_url : get_avatar_url($user->ID, ['size'=>64]);
        ?>
        <a href="?kbf_tab=profile" class="kbf-dashboard-user" title="Profile">
          <span class="kbf-dashboard-avatar-wrap">
            <img class="kbf-dashboard-avatar" src="<?php echo esc_url($avatar_url); ?>" alt="User avatar" id="kbf-navbar-avatar">
            <?php if($nav_profile && !empty($nav_profile->is_verified)): ?>
              <span class="kbf-dashboard-verified" aria-hidden="true"></span>
            <?php endif; ?>
          </span>
          <span class="kbf-dashboard-name"><?php echo esc_html($user->display_name); ?></span>
        </a>
      </div>
    </div>

    <div class="kbf-dashboard-shell">

    <div class="kbf-hero-wrap">
      <div class="kbf-hero-banner">
        <h2 class="kbf-hero-title">Welcome, <?php echo esc_html($user->display_name); ?></h2>
        <p class="kbf-hero-sub">Empower change today. Oversee and manage your community-driven impact initiatives.</p>
      </div>
    </div>
    <div class="kbf-tab-content">
      <?php
      if ($tab === 'overview')         echo kbf_dashboard_overview_tab($business_id);
      elseif ($tab === 'sponsorships') echo kbf_dashboard_sponsorships_tab($business_id);
      elseif ($tab === 'withdrawals')  echo kbf_dashboard_withdrawals_tab($business_id);
      elseif ($tab === 'find_funds')   echo kbf_dashboard_find_funds_tab();
      elseif ($tab === 'profile')      echo kbf_dashboard_profile_tab($business_id);
      elseif ($tab === 'fund_details') echo bntm_shortcode_kbf_fund_details();
      elseif ($tab === 'organizer_profile') echo bntm_shortcode_kbf_organizer_profile();
      elseif ($tab === 'sponsor_history') echo bntm_shortcode_kbf_sponsor_history();
      ?>
      </div>
      </div><!-- .kbf-dashboard-shell -->
    </div><!-- .kbf-user-ui -->

        
        <!-- ================== JS ================== -->
    <script>if(typeof ajaxurl==='undefined') var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';</script>
    <script>
      (function(){
        var topbar = document.querySelector('.kbf-topbar');
        if (!topbar) return;
        function onScroll(){
          if (window.scrollY > 10) topbar.classList.add('kbf-topbar-scrolled');
          else topbar.classList.remove('kbf-topbar-scrolled');
        }
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
      })();

      function kbfToggleMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (!menu || !overlay) return;
        menu.classList.toggle('kbf-menu-open');
        overlay.classList.toggle('kbf-overlay-open');
      }
      function kbfCloseMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (menu) menu.classList.remove('kbf-menu-open');
        if (overlay) overlay.classList.remove('kbf-overlay-open');
      }
      window.addEventListener('resize', function(){
        if (window.innerWidth > 900) kbfCloseMobileMenu();
      });
      document.addEventListener('click', function(e){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (!menu || !overlay) return;
        if (overlay.classList.contains('kbf-overlay-open') && e.target === overlay) {
          kbfCloseMobileMenu();
        }
      });

      function kbfCloseModal(id) {
        document.getElementById(id).style.display = 'none';
        if (id === 'kbf-modal-create') {
            document.documentElement.classList.remove('kbf-modal-lock');
            document.body.classList.remove('kbf-modal-lock');
        }
    }
    function kbfOpenModal(id)  {
        document.getElementById(id).style.display = 'flex';
        if (id === 'kbf-modal-create') {
            document.documentElement.classList.add('kbf-modal-lock');
            document.body.classList.add('kbf-modal-lock');
        }
    }
    var kbfPsgcData = null;
    var kbfPsgcLoading = false;
    function kbfTitleCase(str){
        return String(str).toLowerCase().replace(/\b\w/g,function(c){return c.toUpperCase();});
    }
    function kbfSetMuniOptions(muniEl, list){
        if (!muniEl) return;
        muniEl.innerHTML = '<option value="">Select Municipality</option>';
        for (var i=0;i<list.length;i++){
            var opt = document.createElement('option');
            opt.value = list[i].label;
            opt.textContent = list[i].label;
            muniEl.appendChild(opt);
        }
    }
    function kbfSetBrgyOptions(brgyEl, list){
        if (!brgyEl) return;
        brgyEl.innerHTML = '<option value="">Select Barangay</option>';
        for (var i=0;i<list.length;i++){
            var opt = document.createElement('option');
            opt.value = list[i];
            opt.textContent = list[i];
            brgyEl.appendChild(opt);
        }
    }
    function kbfBuildMunicipalities(provinceUpper){
        var out = [];
        if (!kbfPsgcData) return out;
        for (var regionKey in kbfPsgcData){
            if (!kbfPsgcData.hasOwnProperty(regionKey)) continue;
            var provList = kbfPsgcData[regionKey].province_list || {};
            if (provList[provinceUpper]) {
                var munList = provList[provinceUpper].municipality_list || [];
                for (var i=0;i<munList.length;i++){
                    var obj = munList[i];
                    for (var muniName in obj){
                        if (obj.hasOwnProperty(muniName)){
                            var barangays = obj[muniName].barangay_list || [];
                            var brgyList = [];
                            for (var b=0;b<barangays.length;b++){
                                brgyList.push(kbfTitleCase(barangays[b]));
                            }
                            out.push({ key: muniName, label: kbfTitleCase(muniName), barangays: brgyList });
                        }
                    }
                }
                break;
            }
        }
        return out;
    }
    function kbfEnsurePsgc(cb){
        if (kbfPsgcData){ cb(); return; }
        if (kbfPsgcLoading) return;
        kbfPsgcLoading = true;
        fetch('<?php echo esc_url(BNTM_KBF_URL . 'data/psgc_2016.json'); ?>')
          .then(function(r){ return r.json(); })
          .then(function(j){ kbfPsgcData = j; cb(); })
          .catch(function(){ kbfPsgcData = null; })
          .finally(function(){ kbfPsgcLoading = false; });
    }
    function kbfInitLocationPicker(provinceEl, muniEl, brgyEl){
        if (!provinceEl || !muniEl || !brgyEl) return;
        var muniData = [];
        function handleProvinceChange(){
            var val = provinceEl.value || '';
            if (!val){
                muniEl.disabled = true;
                brgyEl.disabled = true;
                kbfSetMuniOptions(muniEl, []);
                kbfSetBrgyOptions(brgyEl, []);
                return;
            }
            muniEl.disabled = true;
            brgyEl.disabled = true;
            kbfSetMuniOptions(muniEl, []);
            kbfSetBrgyOptions(brgyEl, []);
            kbfEnsurePsgc(function(){
                muniData = kbfBuildMunicipalities(String(val).toUpperCase());
                kbfSetMuniOptions(muniEl, muniData);
                muniEl.disabled = muniData.length === 0;
            });
        }
        function handleMunicipalityChange(){
            var val = muniEl.value || '';
            if (!val){
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
                return;
            }
            var upperVal = String(val).toUpperCase();
            var found = null;
            for (var i=0;i<muniData.length;i++){
                if (muniData[i].key === upperVal){
                    found = muniData[i];
                    break;
                }
            }
            if (!found){
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
                return;
            }
            kbfSetBrgyOptions(brgyEl, found.barangays);
            brgyEl.disabled = found.barangays.length === 0;
        }
        provinceEl.addEventListener('change', handleProvinceChange);
        muniEl.addEventListener('change', handleMunicipalityChange);
        handleProvinceChange();
        return { handleProvinceChange: handleProvinceChange, handleMunicipalityChange: handleMunicipalityChange };
    }
    function kbfApplyLocationSelection(provinceEl, muniEl, brgyEl, loc){
        if (!provinceEl || !muniEl || !brgyEl) return;
        var parts = String(loc || '').split(',').map(function(p){ return p.trim(); }).filter(Boolean);
        var barangay = parts.length > 0 ? parts[0] : '';
        var municipality = parts.length > 1 ? parts[1] : '';
        var province = parts.length > 2 ? parts[2] : (parts.length === 1 ? parts[0] : (parts.length === 2 ? parts[1] : ''));
        provinceEl.value = province;
        if (!province) {
            muniEl.disabled = true; brgyEl.disabled = true;
            kbfSetMuniOptions(muniEl, []); kbfSetBrgyOptions(brgyEl, []);
            return;
        }
        kbfEnsurePsgc(function(){
            var muniData = kbfBuildMunicipalities(String(province).toUpperCase());
            kbfSetMuniOptions(muniEl, muniData);
            muniEl.disabled = muniData.length === 0;
            if (municipality) muniEl.value = municipality;
            var upperVal = String(muniEl.value || '').toUpperCase();
            var found = null;
            for (var i=0;i<muniData.length;i++){
                if (muniData[i].key === upperVal){
                    found = muniData[i];
                    break;
                }
            }
            if (found){
                kbfSetBrgyOptions(brgyEl, found.barangays);
                brgyEl.disabled = found.barangays.length === 0;
                if (barangay) brgyEl.value = barangay;
            } else {
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
            }
        });
    }
    (function(){
        kbfInitLocationPicker(
            document.getElementById('kbf-province'),
            document.getElementById('kbf-municipality'),
            document.getElementById('kbf-barangay')
        );
        kbfInitLocationPicker(
            document.getElementById('kbf-edit-province'),
            document.getElementById('kbf-edit-municipality'),
            document.getElementById('kbf-edit-barangay')
        );
        kbfInitLocationPicker(
            document.getElementById('kbf-profile-province'),
            document.getElementById('kbf-profile-municipality'),
            document.getElementById('kbf-profile-barangay')
        );
        var profProv = document.getElementById('kbf-profile-province');
        var profMuni = document.getElementById('kbf-profile-municipality');
        var profBrgy = document.getElementById('kbf-profile-barangay');
        var profHidden = document.getElementById('kbf-profile-address');
        if (profProv && profMuni && profBrgy) {
            if (profHidden) {
                kbfApplyLocationSelection(profProv, profMuni, profBrgy, profHidden.value || '');
            }
            var updateProfileAddress = function(){
                if (!profHidden) return;
                var parts = [];
                if (profBrgy.value) parts.push(profBrgy.value);
                if (profMuni.value) parts.push(profMuni.value);
                if (profProv.value) parts.push(profProv.value);
                profHidden.value = parts.join(', ');
            };
            profProv.addEventListener('change', updateProfileAddress);
            profMuni.addEventListener('change', updateProfileAddress);
            profBrgy.addEventListener('change', updateProfileAddress);
        }
    })();
    function kbfSetBtnLoading(btn, on, label) {
        if (!btn) return;
        if (on) {
            btn.dataset.kbfLabel = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = label || 'Loading...';
        } else {
            btn.disabled = false;
            if (btn.dataset.kbfLabel) btn.innerHTML = btn.dataset.kbfLabel;
        }
    }
    function kbfSetSkeleton(el, on) {
        if (!el) return;
        if (on) {
            el.dataset.kbfPrev = el.innerHTML;
            el.innerHTML =
                '<div class="kbf-skeleton kbf-skel-line lg"></div>' +
                '<div class="kbf-skeleton kbf-skel-line md"></div>' +
                '<div class="kbf-skeleton kbf-skel-line sm"></div>' +
                '<div class="kbf-skeleton kbf-skel-box"></div>';
        } else if (el.dataset.kbfPrev !== undefined) {
            el.innerHTML = el.dataset.kbfPrev;
            delete el.dataset.kbfPrev;
        }
    }

    function kbfSetFieldError(field, message) {
        var group = field.closest('.kbf-form-group');
        if (!group) return;
        var err = group.querySelector('.kbf-field-error');
        if (!err) {
            err = document.createElement('div');
            err.className = 'kbf-field-error';
            group.appendChild(err);
        }
        err.textContent = message;
    }

    function kbfClearFieldError(field) {
        var group = field.closest('.kbf-form-group');
        if (!group) return;
        var err = group.querySelector('.kbf-field-error');
        if (err) err.textContent = '';
    }

    function kbfValidateEditFund(form) {
        var fields = [
            document.getElementById('edit-fund-title'),
            document.getElementById('edit-fund-desc')
        ];
        var firstInvalid = null;
        for (var i = 0; i < fields.length; i++) {
            var f = fields[i];
            if (!f) continue;
            var valid = String(f.value || '').trim() !== '';
            if (!valid) {
                if (!firstInvalid) firstInvalid = f;
                kbfSetFieldError(f, 'This field is required.');
            } else {
                kbfClearFieldError(f);
            }
        }
        return firstInvalid;
    }

    function kbfValidateCreateForm(form) {
        var fields = form.querySelectorAll('input, select, textarea');
        var firstInvalid = null;
        for (var i=0; i<fields.length; i++) {
            var f = fields[i];
            if (f.disabled) continue;
            if (f.hasAttribute('required')) {
                var valid = true;
                if (f.type === 'file') {
                    valid = f.files && f.files.length > 0;
                } else if (f.type === 'checkbox') {
                    valid = f.checked;
                } else {
                    valid = String(f.value || '').trim() !== '';
                }
                if (!valid) {
                    if (!firstInvalid) firstInvalid = f;
                    kbfSetFieldError(f, 'This field is required.');
                } else {
                    kbfClearFieldError(f);
                }
            } else {
                kbfClearFieldError(f);
            }
        }
        return firstInvalid;
    }

    function kbfSetLoadingPage(on) {
        var el = document.getElementById('kbf-loading-overlay');
        if (!el) return;
        el.style.display = on ? 'flex' : 'none';
    }

    function kbfInitDescCounter(textarea){
        if (!textarea) return;
        var counter = textarea.parentNode.querySelector('.kbf-desc-counter');
        function update(){
            if (!counter) return;
            var len = (textarea.value || '').length;
            counter.textContent = len + ' / 800';
        }
        textarea.addEventListener('input', update);
        update();
    }
    function kbfInitTitleCounter(input){
        if (!input) return;
        var counter = input.parentNode.querySelector('.kbf-title-counter');
        function update(){
            if (!counter) return;
            var len = (input.value || '').length;
            counter.textContent = len + ' / 80';
        }
        input.addEventListener('input', update);
        update();
    }
    (function(){
        kbfInitDescCounter(document.querySelector('#kbf-create-fund-form textarea[name="description"]'));
        kbfInitDescCounter(document.getElementById('edit-fund-desc'));
        kbfInitTitleCounter(document.querySelector('#kbf-create-fund-form input[name="title"]'));
        kbfInitTitleCounter(document.getElementById('edit-fund-title'));
        var funderSelect = document.querySelector('#kbf-create-fund-form select[name="funder_type"]');
        var titleInput = document.getElementById('kbf-create-title');
        if (funderSelect && titleInput) {
            var placeholders = {
                yourself: 'e.g., Help with my medical bills',
                someone_else: 'e.g., Support Maria’s recovery',
                charity_event: 'e.g., Barangay relief drive 2026'
            };
            var updatePlaceholder = function(){
                var key = funderSelect.value || 'yourself';
                titleInput.placeholder = placeholders[key] || 'Clear, compelling title';
            };
            funderSelect.addEventListener('change', updatePlaceholder);
            updatePlaceholder();
        }
    })();

    function kbfSubmitCreate() {
        const form = document.getElementById('kbf-create-fund-form');
        const btn  = document.querySelector('#kbf-modal-create .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-create-msg');
        const invalid = kbfValidateCreateForm(form);
        if (invalid) {
            invalid.focus();
            return;
        }
        kbfSetLoadingPage(true);
        kbfSetBtnLoading(btn, true, 'Submitting...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        var provEl = document.getElementById('kbf-province');
        var muniEl = document.getElementById('kbf-municipality');
        var brgyEl = document.getElementById('kbf-barangay');
        var prov = provEl ? provEl.value : '';
        var muni = muniEl ? muniEl.value : '';
        var brgy = brgyEl ? brgyEl.value : '';
        var parts = [];
        if (brgy) parts.push(brgy);
        if (muni) parts.push(muni);
        if (prov) parts.push(prov);
        if (parts.length) fd.append('location_full', parts.join(', '));
        fd.append('action', 'kbf_create_fund');
        fd.append('nonce', '<?php echo $nonce_create; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-create-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json && (json.success === true || json.success === 1 || json.success === '1')) {
                kbfCloseModal('kbf-modal-create');
                var modal = document.getElementById('kbf-modal-create');
                if (modal) modal.style.display = 'none';
                document.documentElement.classList.remove('kbf-modal-lock');
                document.body.classList.remove('kbf-modal-lock');
            }
            else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetLoadingPage(false); kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

    function kbfSubmitEdit() {
        const form = document.getElementById('kbf-edit-fund-form');
        const btn  = document.querySelector('#kbf-modal-edit .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-edit-msg');
        const invalid = kbfValidateEditFund(form);
        if (invalid) {
            invalid.focus();
            return;
        }
        kbfSetBtnLoading(btn, true, 'Saving...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        var eProv = document.getElementById('kbf-edit-province');
        var eMuni = document.getElementById('kbf-edit-municipality');
        var eBrgy = document.getElementById('kbf-edit-barangay');
        var eProvVal = eProv ? eProv.value : '';
        var eMuniVal = eMuni ? eMuni.value : '';
        var eBrgyVal = eBrgy ? eBrgy.value : '';
        var eParts = [];
        if (eBrgyVal) eParts.push(eBrgyVal);
        if (eMuniVal) eParts.push(eMuniVal);
        if (eProvVal) eParts.push(eProvVal);
        if (eParts.length) fd.append('location_full', eParts.join(', '));
        fd.append('action', 'kbf_update_fund');
        fd.append('nonce', '<?php echo $nonce_edit; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-edit-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json.success) {
                kbfCloseModal('kbf-modal-edit');
            } else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

    function kbfSubmitWd() {
        const form = document.getElementById('kbf-wd-form');
        const btn  = document.querySelector('#kbf-modal-wd .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-wd-msg');
        kbfSetBtnLoading(btn, true, 'Submitting...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        fd.append('action', 'kbf_request_withdrawal');
        fd.append('nonce', '<?php echo $nonce_wd; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-wd-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json.success) {
                kbfCloseModal('kbf-modal-wd');
            } else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

    function kbfSubmitAppeal(nonce) {
        const form = document.getElementById('kbf-appeal-form');
        const btn  = document.querySelector('#kbf-modal-appeal .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-appeal-msg');
        kbfSetBtnLoading(btn, true, 'Submitting...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        fd.append('action', 'kbf_submit_appeal');
        fd.append('nonce', nonce);
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            msg.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if (json.success) setTimeout(()=>{ kbfCloseModal('kbf-modal-appeal'); }, 1600);
            else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

    window.kbfOpenEdit = function(id, title, desc, loc, deadline, autoReturn) {
        document.getElementById('edit-fund-id').value = id;
        document.getElementById('edit-fund-title').value = title;
        document.getElementById('edit-fund-desc').value = desc;
        var hiddenLoc = document.getElementById('edit-fund-location-hidden');
        if (hiddenLoc) hiddenLoc.value = loc || '';
        var titleCounter = document.getElementById('edit-fund-title').parentNode.querySelector('.kbf-title-counter');
        if (titleCounter) titleCounter.textContent = (title || '').length + ' / 80';
        var deadlineEl = document.getElementById('edit-fund-deadline');
        if (deadlineEl) deadlineEl.value = deadline || '';
        var autoEl = document.getElementById('edit-fund-auto-return');
        if (autoEl) autoEl.checked = String(autoReturn) === '1';
        kbfApplyLocationSelection(
            document.getElementById('kbf-edit-province'),
            document.getElementById('kbf-edit-municipality'),
            document.getElementById('kbf-edit-barangay'),
            loc
        );
        var counter = document.getElementById('edit-fund-desc').parentNode.querySelector('.kbf-desc-counter');
        if (counter) counter.textContent = (desc || '').length + ' / 800';
        kbfOpenModal('kbf-modal-edit');
    };

    window.kbfOpenWd = function(fundId, available, title) {
        document.getElementById('wd-fund-id').value = fundId;
        document.getElementById('wd-fund-title').textContent = title || 'Fund #'+fundId;
        document.getElementById('wd-available-label').textContent = '₱' + parseFloat(available).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        document.getElementById('wd-amount').max = available;
        document.getElementById('wd-amount').placeholder = 'Max ₱'+parseFloat(available).toLocaleString('en-PH',{minimumFractionDigits:2});
        document.getElementById('kbf-wd-msg').innerHTML = '';
        document.getElementById('kbf-wd-form').reset();
        document.getElementById('wd-fund-id').value = fundId; // re-set after reset
        kbfOpenModal('kbf-modal-wd');
    };

    window.kbfOpenAppeal = function(fundId, title) {
        document.getElementById('kbf-appeal-fund-id').value = fundId;
        document.getElementById('kbf-appeal-msg').innerHTML = '';
        kbfOpenModal('kbf-modal-appeal');
    };

    window.kbfCancelFund = function(fundId) {
        if (!confirm('Cancel this fund? This cannot be undone.')) return;
        const fd = new FormData();
        fd.append('action','kbf_cancel_fund'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo $nonce_cancel; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };

    window.kbfExtendDeadline = function(fundId) {
        const d = prompt('New deadline (YYYY-MM-DD):'); if(!d) return;
        const fd = new FormData();
        fd.append('action','kbf_extend_deadline'); fd.append('fund_id',fundId);
        fd.append('deadline',d); fd.append('nonce','<?php echo $nonce_extend; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };


    window.kbfMarkComplete = function(fundId) {
        if(!confirm('Mark this fund as complete?')) return;
        const fd = new FormData();
        fd.append('action','kbf_mark_fund_complete'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo wp_create_nonce('kbf_cancel_fund'); ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };
    </script>
    <?php
    $content = ob_get_clean();
    return bntm_universal_container('KonekBayan -- Finding Platform', $content, ['show_topbar'=>false,'show_header'=>false,'wrap'=>false]);
}
