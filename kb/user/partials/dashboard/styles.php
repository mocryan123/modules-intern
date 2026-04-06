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
    --kbf-shadow: #0000000d 0px 2px 4px -1px, #0000000a 0px 1px 2px -1px;
    --kbf-shadow-lg: #0000000f 0px 6px 12px -3px, #0000000d 0px 3px 6px -2px;
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
            0 1px 2px #2070e033,
            0 4px 14px #2a78dc47,
            0 0 0 0px #6fb6ff00,
            inset 0 1px 0 #ffffff2e;
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
            0 1px 3px #2070e026,
            0 8px 24px #2a78dc73,
            0 16px 40px #3d8ef033,
            inset 0 1px 0 #ffffff40;
        filter: brightness(1.06);
    }
    .kbf-user-ui .kbf-btn-primary:hover::before{ opacity: 1; }
    .kbf-user-ui .kbf-btn-primary:active{
        transform: translateY(0px);
        box-shadow:
            0 1px 2px #2070e033,
            0 4px 14px #2a78dc47,
            inset 0 1px 0 #ffffff2e;
        transition: transform .1s ease, box-shadow .1s ease, filter .1s ease;
    }
    .kbf-user-ui .kbf-btn-secondary{
        background:#f8fafc;
        border:1px solid #e5e7eb;
        color:#334155;
    }
    .kbf-user-ui .kbf-btn-ghost{
        background:transparent;
        border:1px dashed #d7e3f7;
        color:#475569;
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
        z-index:50;
        opacity:0;
        visibility:hidden;
        pointer-events:none;
        transform:translateY(-6px) scale(0.98);
        transition:opacity .18s ease, transform .18s ease, visibility .18s ease;
    }
    .kbf-card-more-menu.open{
        opacity:1;
        visibility:visible;
        pointer-events:auto;
        transform:translateY(0) scale(1);
    }
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
        background: #ffffffbf;
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
        box-shadow:0 4px 24px #0f28500f;
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
        background:#00000073;
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
        box-shadow:0 10px 26px #0f28502e;
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
    .kbf-dashboard-avatar-wrap .kbf-dashboard-avatar{width:34px;height:34px;border-radius:50%;border:1px solid var(--kbf-border);object-fit:cover;box-shadow:0 8px 18px #1018281f;display:block;}
    .kbf-dashboard-avatar-fallback{
        width:34px;height:34px;border-radius:50%;
        border:1px solid var(--kbf-border);
        background:var(--kbf-navy);
        display:inline-flex;align-items:center;justify-content:center;
        box-shadow:0 8px 18px #1018281f;
    }
    .kbf-dashboard-avatar-fallback img{width:16px;height:16px;filter:invert(100%);}
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
        box-shadow:0 16px 36px #0f28502e;
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
          radial-gradient(140% 160% at 0% 0%, #4f93ff47 0%, #4f93ff00 55%),
          radial-gradient(140% 160% at 100% 0%, #a1d2ff4c 0%, #a1d2ff00 55%),
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
        background:#ffffff33;
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
        background:#0a122380;
        backdrop-filter:blur(10px) saturate(120%);
        padding:26px;
        z-index:9999;
    }
    .kbf-user-ui .kbf-modal{
        width:100%;
        max-width:740px;
        background:#fff;
        border-radius:24px;
        border:1px solid #94a3b840;
        box-shadow:
            0 32px 90px #0f172a47,
            0 2px 0 #ffffffe6 inset;
        overflow:hidden;
        max-height:90vh;
        display:flex;
        flex-direction:column;
        position:relative;
    }
    .kbf-user-ui .kbf-modal::before{
        content:none;
    }
    .kbf-user-ui .kbf-modal::after{
        content:"";
        position:absolute;
        inset:0;
        border-radius:24px;
        box-shadow:0 0 0 1px #94a3b82e inset;
        pointer-events:none;
    }
    .kbf-user-ui .kbf-modal.kbf-modal-sm{
        max-width:520px;
    }
    .kbf-user-ui .kbf-modal-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:20px 24px 18px;
        background:#ffffff;
        border-bottom:1px solid #94a3b838;
    }
    .kbf-user-ui .kbf-modal-header h3{
        margin:0;
        font-size:17px;
        font-weight:600;
        letter-spacing:-.2px;
        color:var(--kbf-navy, #0f172a);
        display:flex;
        align-items:center;
        gap:10px;
    }
    .kbf-user-ui .kbf-modal-header h3::before{
        content:none;
    }
    .kbf-user-ui .kbf-modal-close{
        width:34px;
        height:34px;
        border-radius:12px;
        border:1px solid #94a3b859;
        background:#fff;
        color:#64748b;
        font-size:18px;
        line-height:1;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        transition:transform .15s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .kbf-user-ui .kbf-modal-close:hover{
        transform:translateY(-1px);
        border-color:#3b82f680;
        box-shadow:0 10px 22px #0f172a1f;
    }
    .kbf-user-ui .kbf-modal-body{
        padding:22px 24px 20px;
        background:#fff;
        overflow-y:auto;
        max-height:70vh;
    }
    .kbf-user-ui #kbf-modal-create .kbf-modal-body,
    .kbf-user-ui #kbf-modal-edit .kbf-modal-body{
        overflow:visible;
        max-height:none;
    }
    .kbf-user-ui .kbf-modal-footer{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        padding:16px 24px 22px;
        background:#ffffff;
        border-top:1px solid #94a3b838;
    }
    .kbf-user-ui .kbf-modal-footer .kbf-btn{
        min-height:40px;
        border-radius:12px;
        padding:10px 18px;
        font-weight:600;
        letter-spacing:.1px;
    }
    .kbf-user-ui .kbf-modal-footer .kbf-btn-primary{
        box-shadow:0 10px 22px #2563eb47;
    }
    .kbf-user-ui .kbf-modal-footer .kbf-btn-primary:hover{
        box-shadow:0 12px 26px #2563eb52;
        transform:translateY(-1px);
    }
    .kbf-user-ui .kbf-modal-footer .kbf-btn-secondary{
        background:#fff;
    }
    .kbf-user-ui .kbf-auth-modal{
        max-width:920px;
        padding:0;
        background:linear-gradient(180deg,#ffffff 0%, #f8fbff 100%);
        border:none;
        border-radius:26px;
        box-shadow:0 34px 90px rgba(15,23,42,.16), 0 8px 24px rgba(37,99,235,.08);
        backdrop-filter:blur(4px);
    }
    .kbf-user-ui .kbf-auth-modal .kbf-modal-close{
        position:absolute;
        top:18px;
        right:18px;
        z-index:3;
    }
    .kbf-user-ui .kbf-auth-grid{
        display:flex;
        min-height:360px;
        align-items:stretch;
    }
    .kbf-user-ui .kbf-auth-side{
        flex:0 0 45%;
    }
    .kbf-user-ui .kbf-auth-main{
        flex:1 1 55%;
    }
    .kbf-user-ui .kbf-auth-side{
        position:relative;
        background:
            linear-gradient(135deg, #2f7bdc 0%, #4a98ff 100%),
            url('<?php echo esc_url(BNTM_KBF_URL . 'assets/hero.jpg'); ?>') center/cover no-repeat;
        color:#fff;
        padding:48px;
        display:flex;
        align-items:flex-end;
        overflow:hidden;
    }
    .kbf-user-ui .kbf-auth-side::before{
        content:'';
        position:absolute;
        inset:0;
        background:
            radial-gradient(120% 120% at 20% 0%, rgba(255,255,255,0.35) 0%, rgba(255,255,255,0) 55%),
            linear-gradient(120deg, rgba(255,255,255,0.22) 0%, rgba(255,255,255,0) 40%);
        mix-blend-mode:screen;
        pointer-events:none;
    }
    .kbf-user-ui .kbf-auth-side::after{
        content:'';
        position:absolute;
        inset:0;
        background:linear-gradient(180deg, rgba(15, 23, 42, 0.15) 0%, rgba(15, 23, 42, 0.55) 100%);
        pointer-events:none;
    }
    .kbf-user-ui .kbf-auth-side-inner{
        position:relative;
        z-index:2;
        display:flex;
        flex-direction:column;
        gap:10px;
        max-width:360px;
    }
    .kbf-user-ui .kbf-auth-chip{
        display:inline-flex;
        align-items:center;
        gap:6px;
        background:rgba(255,255,255,0.22);
        border:1px solid rgba(255,255,255,0.42);
        border-radius:999px;
        padding:4px 8px;
        font-size:11px;
        text-transform:uppercase;
        letter-spacing:.12em;
        font-weight:600;
        box-shadow:0 8px 22px rgba(15,23,42,0.22);
        backdrop-filter:blur(8px);
        margin:0;
        max-width:220px;
        justify-content:center;
    }
    .kbf-user-ui .kbf-auth-quote{
        font-size:20px;
        font-weight:700;
        line-height:1.35;
        text-shadow:0 14px 34px rgba(15,23,42,0.4);
        margin-top:2px;
    }
    .kbf-user-ui .kbf-auth-sub{
        font-size:12.5px;
        color:rgba(255,255,255,0.82);
        max-width:320px;
        line-height:1.6;
    }
    .kbf-user-ui .kbf-auth-main{
        padding:48px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:stretch;
        gap:10px;
        background:#ffffff;
        border-left:1px solid #e2e8f0;
        text-align:left;
        box-sizing:border-box;
        overflow:hidden;
    }
    .kbf-user-ui .kbf-auth-main-inner{
        width:100%;
        max-width:none;
        margin:0;
        display:flex;
        flex-direction:column;
        gap:10px;
        align-items:stretch;
        box-sizing:border-box;
    }
    .kbf-user-ui .kbf-auth-main h3{
        font-size:21px;
        font-weight:700;
        margin:0;
        color:#0f172a;
    }
    .kbf-user-ui .kbf-auth-main p{
        margin:0;
        color:#4f5a6b;
        font-size:13.5px;
        line-height:1.6;
    }
    .kbf-user-ui .kbf-auth-actions{
        display:flex;
        flex-direction:column;
        gap:12px;
        margin-top:16px;
        width:100%;
    }
    .kbf-user-ui .kbf-auth-actions .kbf-btn{
        width:100%;
        justify-content:center;
        min-height:44px;
        border-radius:12px;
        align-self:stretch;
        box-sizing:border-box;
    }
    .kbf-user-ui .kbf-auth-side{
        padding:32px;
    }
    .kbf-user-ui .kbf-auth-quote{
        margin-top:2px;
        margin-bottom:2px;
    }
    .kbf-user-ui .kbf-auth-sub{
        margin-top:2px;
    }
    @media (max-width: 900px){
        .kbf-user-ui .kbf-auth-grid{
            grid-template-columns:1fr;
        }
        .kbf-user-ui .kbf-auth-side{
            min-height:180px;
        }
    }
    .kbf-user-ui .kbf-modal input,
    .kbf-user-ui .kbf-modal select,
    .kbf-user-ui .kbf-modal textarea{
        border-radius:14px;
        border:1.5px solid #94a3b859;
        background:#fff;
        box-shadow:0 1px 0 #ffffffcc inset;
        transition:border-color .2s ease, box-shadow .2s ease;
    }
    .kbf-user-ui .kbf-modal input:focus,
    .kbf-user-ui .kbf-modal select:focus,
    .kbf-user-ui .kbf-modal textarea:focus{
        border-color:#3b82f6b2;
        box-shadow:0 0 0 3px #3b82f61f;
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
        gap:12px;
        align-items:center;
        margin-bottom:18px;
        flex-wrap:wrap;
    }
    .kbf-user-ui .kbf-step{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:7px 14px;
        border-radius:999px;
        font-size:12px;
        font-weight:600;
        color:var(--kbf-slate, #64748b);
        background:#f4f7fb;
        border:1px solid #94a3b840;
        position:relative;
    }
    .kbf-user-ui .kbf-step:not(:last-child)::after{
        content:"";
        position:absolute;
        right:-14px;
        top:50%;
        width:10px;
        height:2px;
        background:#94a3b859;
        transform:translateY(-50%);
    }
    .kbf-user-ui .kbf-step span{
        width:20px;height:20px;border-radius:50%;
        display:inline-flex;align-items:center;justify-content:center;
        background:#eef2ff;color:#2563eb;font-size:11px;font-weight:700;
        box-shadow:0 2px 6px #2563eb1f;
        border:1px solid #2563eb33;
    }
    .kbf-user-ui .kbf-step.is-active{
        background:linear-gradient(180deg,#eef5ff 0%, #ffffff 100%);
        border-color:#2563eb4c;
        color:var(--kbf-navy, #0f172a);
        box-shadow:0 8px 18px #2563eb1f;
    }
    .kbf-user-ui .kbf-step.is-active span{
        background:#2563eb;color:#fff;
        box-shadow:0 6px 14px #2563eb59;
        border-color:#2563eba6;
    }
    .kbf-user-ui .kbf-step.is-active span{
        animation:kbfStepPulse .32s ease;
    }
    @keyframes kbfStepPulse{
        0%{transform:scale(.92);opacity:.7;}
        100%{transform:scale(1);opacity:1;}
    }
    .kbf-user-ui .kbf-step-content{display:none;}
    .kbf-user-ui .kbf-step-content.is-active{
        display:block;
        animation:kbfStepIn .28s ease both;
    }
    @keyframes kbfStepIn{
        0%{opacity:0;transform:translateY(8px) scale(.98);}
        100%{opacity:1;transform:translateY(0) scale(1);}
    }
    .kbf-user-ui #kbf-modal-create .kbf-step-content-flex.is-active{
        display:flex;
        flex-direction:column;
        gap:12px;
    }
    .kbf-user-ui #kbf-modal-create .kbf-step-content-flex{
        height:100%;
    }
    .kbf-user-ui #kbf-modal-create .kbf-step-content{
        height:500px;
        overflow-y:auto;
        overflow-x:hidden;
    }
    .kbf-user-ui #kbf-modal-create .kbf-step-content[data-step="3"]{
        height:auto;
        overflow:visible;
    }
    .kbf-user-ui #kbf-modal-edit .kbf-step-content{
        height:500px;
        overflow-y:auto;
        overflow-x:hidden;
    }
    @media (max-width: 900px){
        .kbf-user-ui #kbf-modal-edit .kbf-step-content{
            height:auto;
            overflow:visible;
        }
    }
    @media (max-width: 900px){
        .kbf-user-ui #kbf-modal-create .kbf-step-content{
            height:auto;
            overflow:visible;
        }
        .kbf-user-ui #kbf-modal-create .kbf-step-content-flex{
            height:auto;
        }
        .kbf-user-ui #kbf-modal-create .kbf-modal-body,
        .kbf-user-ui #kbf-modal-edit .kbf-modal-body{
            overflow-y:auto;
            max-height:calc(100vh - 180px);
        }
    }
    .kbf-user-ui .kbf-photo-tips{
        margin-top:12px;
        padding:10px 12px;
        border:1px dashed #dbe7ff;
        background:#f8fbff;
        border-radius:12px;
        color:#475569;
        font-size:12.5px;
        line-height:1.55;
    }
    .kbf-user-ui .kbf-photo-tips-bottom{
        margin-top:18px;
    }
    .kbf-user-ui #kbf-modal-create .kbf-step-content-flex .kbf-photo-tips-bottom{
        margin-top:auto;
    }
    .kbf-user-ui .kbf-photo-tips-title{
        display:flex;
        align-items:center;
        gap:8px;
        font-size:12.5px;
        font-weight:700;
        color:#1f2a44;
        margin-bottom:6px;
    }
    .kbf-user-ui .kbf-photo-tips-icon{
        width:18px;
        height:18px;
        border-radius:50%;
        background:#e7f1ff;
        color:#1d4ed8;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size:11px;
        font-weight:800;
        flex-shrink:0;
    }
    .kbf-user-ui .kbf-photo-tips-list{
        display:grid;
        gap:4px;
    }
    .kbf-user-ui .kbf-photo-tip-item{
        display:flex;
        align-items:flex-start;
        gap:8px;
    }
    .kbf-user-ui .kbf-photo-tip-check{
        width:18px;
        height:18px;
        border-radius:50%;
        background:#e7f1ff;
        color:#1d4ed8;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size:11px;
        font-weight:800;
        flex-shrink:0;
        margin-top:1px;
    }
    .kbf-user-ui .kbf-step-actions{
        display:flex;gap:10px;align-items:center;justify-content:flex-end;
    }
    .kbf-user-ui .kbf-step-note{
        font-size:12px;color:var(--kbf-slate, #64748b);margin-bottom:10px;
    }
    .kbf-user-ui .kbf-photo-previews{
        display:grid;
        grid-template-columns:repeat(auto-fill, minmax(104px, 1fr));
        gap:12px;
        margin-top:10px;
        width:100%;
        align-content:start;
    }
    .kbf-user-ui .kbf-photo-add{
        width:100%;
        height:86px;
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
        order: 999;
    }
    .kbf-user-ui .kbf-photo-add:hover{
        cursor:pointer;
        border-color:#d9dde6;
        color:#6b7280;
        box-shadow:none;
        transform:none;
    }
    .kbf-user-ui .kbf-photo-thumb{
        width:100%;
        height:86px;
        border-radius:12px;
        border:1px solid #e2e8f0;
        overflow:hidden;
        position:relative;
        cursor:default;
        user-select:none;
        order: 1;
    }
    .kbf-user-ui .kbf-photo-order{
        position:absolute;
        top:6px;left:6px;
        min-width:18px;height:18px;
        border-radius:999px;
        background:#0f172acc;
        color:#fff;
        font-size:10px;
        font-weight:700;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:0 5px;
    }
    @media (max-width: 820px){
        .kbf-user-ui .kbf-photo-previews{
            grid-template-columns:repeat(2, minmax(0, 1fr));
        }
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
        background:#0f172abf;
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
    .kbf-user-ui .kbf-photo-edit{
        position:absolute;
        top:6px;right:34px;
        width:22px;height:22px;
        border-radius:50%;
        border:none;
        background:#0f172abf;
        color:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        opacity:0;
        transform:scale(.9);
        transition:opacity .15s ease, transform .15s ease, background .15s ease;
        cursor:pointer;
        padding:0;
    }
    .kbf-user-ui .kbf-photo-edit svg{
        width:12px;height:12px;display:block;
    }
    .kbf-user-ui .kbf-photo-thumb:hover .kbf-photo-remove{
        opacity:1;
        transform:scale(1);
    }
    .kbf-user-ui .kbf-photo-thumb:hover .kbf-photo-edit{
        opacity:1;
        transform:scale(1);
    }
    .kbf-user-ui .kbf-photo-remove:hover{
        background:#dc2626e6;
    }
    .kbf-user-ui .kbf-photo-edit:hover{
        background:#2563eb;
    }
    .kbf-user-ui .kbf-photo-editor-modal{
        max-width:560px;
    }
    .kbf-user-ui .kbf-photo-editor-stage{
        position:relative;
        height:280px;
        border-radius:14px;
        border:1px solid #e2e8f0;
        background:#f8fafc;
        overflow:hidden;
        display:flex;
        align-items:center;
        justify-content:center;
        touch-action:none;
    }
    .kbf-user-ui .kbf-photo-editor-crop{
        position:absolute;
        border:2px solid #ffffff;
        border-radius:12px;
        box-shadow:0 0 0 2000px rgba(12, 18, 32, 0.45);
        pointer-events:none;
    }
    .kbf-user-ui .kbf-photo-editor-stage img{
        position:absolute;
        max-width:none;
        max-height:none;
        left:50%;
        top:50%;
        transform:translate(-50%, -50%);
        transform-origin:center;
    }
    .kbf-user-ui .kbf-photo-editor-controls{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-top:14px;
        flex-direction:column;
        align-items:stretch;
    }
    .kbf-user-ui .kbf-photo-editor-zoom{
        display:flex;
        flex-direction:column;
        gap:6px;
        width:100%;
    }
    .kbf-user-ui .kbf-photo-editor-zoom label{
        font-size:11.5px;
        font-weight:600;
        color:#64748b;
    }
    .kbf-user-ui .kbf-photo-editor-zoom input[type="range"]{
        width:100%;
    }
    #kbf-photo-zoom{
        accent-color:#3b82f6;
    }
    #kbf-photo-zoom::-webkit-slider-thumb{
        background:#3b82f6;
        border:2px solid #dbeafe;
    }
    #kbf-photo-zoom::-moz-range-thumb{
        background:#3b82f6;
        border:2px solid #dbeafe;
    }
    .kbf-user-ui .kbf-photo-editor-actions{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        justify-content:center;
        width:100%;
    }
    .kbf-user-ui .kbf-photo-editor-icon-btn{
        width:38px;
        height:38px;
        border-radius:12px;
        border:1px solid #e2e8f0;
        background:#fff;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        box-shadow:0 4px 10px rgba(15,23,42,.06);
        transition:transform .15s ease, box-shadow .2s ease, border-color .2s ease;
        padding:0;
    }
    .kbf-user-ui .kbf-photo-editor-icon-btn img{
        width:16px;
        height:16px;
        display:block;
        filter: invert(32%) sepia(8%) saturate(1427%) hue-rotate(182deg) brightness(96%) contrast(93%);
    }
    .kbf-user-ui .kbf-photo-editor-icon-btn:hover{
        transform:translateY(-1px);
        border-color:#cbd5f5;
        box-shadow:0 6px 14px rgba(59,130,246,.15);
    }
    .kbf-user-ui .kbf-photo-editor-reset{
        min-height:38px;
        padding:0 14px;
        font-size:12.5px;
        font-weight:600;
        border-radius:12px;
    }
    .kbf-user-ui .kbf-photo-editor-controls .kbf-btn{
        min-height:34px;
        padding:6px 12px;
        font-size:12px;
    }
    .kbf-user-ui #kbf-loading-overlay{
        position:fixed;
        inset:0;
        z-index:99999;
        display:none;
        align-items:center;
        justify-content:center;
        background:#f8fafcbf;
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
        overflow:hidden;
        animation:kbfpreloadjump 1.2s cubic-bezier(.34,1.2,.64,1) infinite;
    }
    .kbf-user-ui .kbf-loading-mark img{
        width:26px;height:26px;object-fit:contain;display:block;
        filter:brightness(0) invert(1);
    }
    @keyframes kbfpreloadjump{
        0%{transform:translateY(0) rotate(0deg) scale(1); box-shadow:0 8px 18px rgba(61,142,240,.2);}
        25%{transform:translateY(-14px) rotate(-8deg) scale(1.01); box-shadow:0 16px 28px rgba(61,142,240,.3);}
        50%{transform:translateY(2px) rotate(6deg) scale(.99); box-shadow:0 6px 14px rgba(61,142,240,.18);}
        75%{transform:translateY(-8px) rotate(-6deg) scale(1.005); box-shadow:0 12px 24px rgba(61,142,240,.26);}
        100%{transform:translateY(0) rotate(0deg) scale(1); box-shadow:0 8px 18px rgba(61,142,240,.2);}
    }
html.kbf-modal-lock, body.kbf-modal-lock { overflow: auto !important; }
    .kbf-user-ui .kbf-modal-body p{font-weight:400;}
    </style>

