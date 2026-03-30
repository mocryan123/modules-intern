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
    .kbf-btn-withdraw{
        height:34px;
        padding:0 12px;
        gap:6px;
        white-space:nowrap;
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
        background:none;
        border:0;
        cursor:pointer;
        padding:0;
    }
    .kbf-dashboard-user .kbf-dashboard-avatar-wrap{order:1;}
    .kbf-dashboard-user .kbf-dashboard-name{order:0;}
    .kbf-dashboard-user:hover{ color:#1f2a44; }
    .kbf-user-menu{position:relative;display:inline-flex;align-items:center;}
    .kbf-user-dropdown{
        position:absolute;
        top:calc(100% + 8px);
        right:0;
        min-width:160px;
        background:#fff;
        border:1px solid var(--kbf-border);
        border-radius:12px;
        box-shadow:0 16px 36px rgba(15,40,80,0.18);
        padding:6px;
        display:none;
        z-index:999;
    }
    .kbf-user-dropdown a{
        display:flex;
        align-items:center;
        gap:8px;
        padding:9px 10px;
        border-radius:10px;
        font-size:12.5px;
        color:#1f2a44;
        text-decoration:none;
        font-weight:600;
    }
    .kbf-user-dropdown a:hover{background:var(--kbf-slate-lt);}
    .kbf-user-dropdown.kbf-open{display:block;}
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
    .kbf-user-ui .kbf-modal-header h3{font-weight:600;
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
    .kbf-user-ui .kbf-stepper{
        display:flex;
        gap:10px;
        align-items:center;
        margin-bottom:18px;
        flex-wrap:wrap;
    }
    .kbf-user-ui .kbf-step{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:6px 12px;
        border-radius:999px;
        font-size:12px;
        font-weight:600;
        color:#5b6b84;
        background:#f3f6fb;
        border:1px solid #e4ebf6;
    }
    .kbf-user-ui .kbf-step span{
        width:20px;height:20px;border-radius:50%;
        display:inline-flex;align-items:center;justify-content:center;
        background:#e7efff;color:#2a5bd7;font-size:11px;font-weight:700;
    }
    .kbf-user-ui .kbf-step.is-active{
        background:#eef5ff;border-color:#d6e4ff;color:#1d3563;
    }
    .kbf-user-ui .kbf-step.is-active span{
        background:#2a5bd7;color:#fff;
    }
    .kbf-user-ui .kbf-step-content{display:none;}
    .kbf-user-ui .kbf-step-content.is-active{display:block;}
    .kbf-user-ui .kbf-step-actions{
        display:flex;gap:10px;align-items:center;justify-content:flex-end;
    }
    .kbf-user-ui .kbf-step-note{
        font-size:12px;color:#6b7b93;margin-bottom:10px;
    }
    .kbf-user-ui .kbf-photo-previews{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
        margin-top:10px;
    }
    .kbf-user-ui .kbf-photo-add{
        width:120px;height:86px;
        border-radius:12px;
        border:1.5px dashed #d9dde6;
        background:#fff;
        color:#6b7280;
        font-size:26px;
        font-weight:500;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        transition:border-color .18s ease, color .18s ease, box-shadow .18s ease, transform .18s ease;
    }
    .kbf-user-ui .kbf-photo-add:hover{
        cursor:pointer;
        border-color:#9aa3b2;
        color:#111827;
        box-shadow:0 10px 20px rgba(16,24,40,.12);
        transform: translateY(-1px);
    }
    .kbf-user-ui .kbf-photo-thumb{
        width:120px;height:86px;
        border-radius:12px;
        border:1px solid #e2e8f0;
        overflow:hidden;
        position:relative;
    }
    .kbf-user-ui .kbf-photo-thumb img{
        width:100%;height:100%;
        object-fit:cover;display:block;
    }
    .kbf-user-ui .kbf-photo-remove{
        position:absolute;
        top:6px;right:6px;
        width:22px;height:22px;
        border-radius:50%;
        border:none;
        background:rgba(15,23,42,.75);
        color:#fff;
        font-size:14px;
        line-height:1;
        display:flex;
        align-items:center;
        justify-content:center;
        opacity:0;
        transform:scale(.9);
        transition:opacity .15s ease, transform .15s ease, background .15s ease;
        cursor:pointer;
    }
    .kbf-user-ui .kbf-photo-thumb:hover .kbf-photo-remove{
        opacity:1;
        transform:scale(1);
    }
    .kbf-user-ui .kbf-photo-remove:hover{
        background:rgba(220,38,38,.9);
    }
    .kbf-user-ui #kbf-loading-overlay{
        position:fixed;
        inset:0;
        z-index:99999;
        display:none;
        align-items:center;
        justify-content:center;
        background:rgba(248,250,252,0.75);
        backdrop-filter: blur(8px);
    }
    .kbf-user-ui .kbf-loading-mark{
        width:54px;height:54px;
        border-radius:14px;
        background:linear-gradient(135deg,#5ba8f5,#3d8ef0);
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        font-weight:800;
        letter-spacing:.6px;
        box-shadow:0 8px 18px rgba(61,142,240,.2);
        animation:kbfpreloadjump 1.2s cubic-bezier(.34,1.2,.64,1) infinite;
    }
    .kbf-user-ui .kbf-loading-mark img{
        width:26px;height:26px;object-fit:contain;display:block;
        filter:brightness(0) invert(1);
    }
    @keyframes kbfpreloadjump{
        0%,100%{transform:translateY(0);}
        50%{transform:translateY(-6px);}
    }
    html.kbf-modal-lock, body.kbf-modal-lock { overflow: hidden; }
    </style>





.kbf-user-ui .kbf-modal-body p{font-weight:400;}

