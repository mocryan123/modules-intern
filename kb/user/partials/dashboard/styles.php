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
    .kbf-user-ui h1{font-size:28px;font-weight:600;letter-spacing:-0.5px;color:#0d1a2e;margin:0 0 6px;line-height:1.2;}
    .kbf-user-ui h2{font-size:22px;font-weight:500;color:#0f172a;margin:0 0 6px;line-height:1.3;}
    .kbf-user-ui h3{font-size:16px;font-weight:500;color:#0f172a;margin:0 0 6px;line-height:1.35;}
    .kbf-user-ui h4{font-size:14px;font-weight:500;color:#0f172a;margin:0 0 6px;line-height:1.4;}
    .kbf-user-ui p{font-size:13.5px;font-weight:400;color:#4f5a6b;line-height:1.65;margin:0;}
    .kbf-user-ui small,
    .kbf-user-ui .kbf-meta,
    .kbf-user-ui .kbf-text-sm{font-size:12.5px;font-weight:400;color:#4f5a6b;line-height:1.5;}
    .kbf-user-ui label{font-size:12.5px;font-weight:500;color:#4f5a6b;}
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
        height:43px;
        line-height:43px;
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
        height:43px;
        line-height:43px;
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
    .kbf-card-actions .kbf-btn,
    .kbf-card-actions .kbf-btn-sm{
        height:32px;
        min-width:32px;
        padding:0 10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
    }
    .kbf-card-actions .kbf-btn-withdraw{
        height:32px;
        padding:0 10px;
        gap:6px;
        white-space:nowrap;
    }
    .kbf-more-withdraw{
        display:none;
    }
    .kbf-more-milestone{
        display:none;
    }
    @media (max-width: 580px){
        .kbf-card-actions .kbf-btn-withdraw{
            display:none;
        }
        .kbf-card-more-menu .kbf-more-withdraw{
            display:inline-flex;
        }
        .kbf-card-actions .kbf-btn-milestone{
            display:none;
        }
        .kbf-card-more-menu .kbf-more-milestone{
            display:inline-flex;
        }
    }
    .kbf-card-more-wrap{position:relative;}
    .kbf-card-more-menu{
        position:absolute;
        right:0;
        top:calc(100% + 8px);
        background:#ffffff;
        border:1px solid #e2e8f0;
        border-radius:14px;
        box-shadow:
          0 14px 30px rgba(15,23,42,.12),
          0 4px 10px rgba(15,23,42,.08);
        padding:8px;
        min-width:110px;
        z-index:50;
        opacity:0;
        visibility:hidden;
        pointer-events:none;
        transform:translateY(-6px) scale(0.98);
        transition:opacity .18s ease, transform .18s ease, visibility .18s ease;
        backdrop-filter:blur(10px);
    }
    .kbf-card-more-menu.open{
        opacity:1;
        visibility:visible;
        pointer-events:auto;
        transform:translateY(0) scale(1);
    }
    .kbf-card-more-menu button{
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
    .kbf-card-more-menu .kbf-btn,
    .kbf-card-more-menu .kbf-btn-secondary{
        background:transparent !important;
        border:0 !important;
        box-shadow:none !important;
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
    .kbf-card-more-menu button:active{
        background:#e7f1ff;
    }
    .kbf-user-ui .kbf-meta,
    .kbf-user-ui .kbf-text-sm,
    .kbf-user-ui .kbf-browse-tab,
    .kbf-user-ui .kbf-browse-search input{
        color:#4f5a6b;
    }
    .kbf-dashboard-shell{
        max-width:80%;
        margin:0 auto;
        padding-top:30px;
        box-sizing:border-box;
    }
    .kbf-topbar + .kbf-dashboard-shell{
        margin-top:0 !important;
    }
    @media (max-width: 1200px){
        .kbf-dashboard-shell{
            padding:74px 0 0;
        }
    }
    .kbf-tab-content{
        margin-bottom:24px;
        overflow:visible !important;
    }
    .kbf-topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:18px;
        padding:12px 20px;
        min-height:68px;
        position:fixed;
        top:0;
        left:0;
        right:0;
        z-index:1000;
        background: #ffffff;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid transparent;
        transition:border-color .2s ease, box-shadow .2s ease, background .2s ease;
        margin:0;
        width:100%;
        box-sizing:border-box;
    }
    .bntm-topbar{
        display:none !important;
    }
    .kbf-topbar.kbf-topbar-scrolled{
        border-bottom-color:var(--kbf-border);
        box-shadow:0 6px 28px rgba(15, 40, 80, 0.10);
    }
    .kbf-topbar-left{display:flex;align-items:center;gap:28px;flex-wrap:wrap;}
    .kbf-brand{display:flex;align-items:center;gap:10px;font-weight:800;color:#0f172a;font-size:15px;}
    .kbf-brand-text{color:#3d8ef0;font-family:'Poppins', system-ui, -apple-system, sans-serif;}
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
    .kbf-notif-btn{
        width:36px;
        height:36px;
        border-radius:10px;
        border:1px solid var(--kbf-border);
        background:#fff;
        color:#475569;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        text-decoration:none;
        transition:background .15s ease, border-color .15s ease, color .15s ease;
        position:relative;
        cursor:pointer;
    }
    .kbf-notif-btn:hover{
        background:#f8fafc;
        border-color:#cbd5e1;
        color:#1f2a44;
    }
    .kbf-notif-btn .kbf-icon{font-size:17px;cursor:pointer;}
    .kbf-notif-btn.has-unread{border-color:#93c5fd;background:#eff6ff;color:#1d4ed8;}
    .kbf-notif-badge{
        position:absolute;
        top:-5px;
        right:-5px;
        min-width:16px;
        height:16px;
        padding:0 4px;
        border-radius:999px;
        background:#ef4444;
        color:#fff;
        font-size:10px;
        line-height:16px;
        text-align:center;
        font-weight:700;
        box-shadow:0 0 0 2px #fff;
    }
    .kbf-notif-menu{position:relative;display:inline-flex;align-items:center;}
    .kbf-notif-dropdown{
        position:absolute;
        top:calc(100% + 8px);
        right:0;
        width:320px;
        max-width:min(92vw, 320px);
        background:#ffffff;
        border:1px solid #e2e8f0;
        border-radius:14px;
        box-shadow:0 14px 30px rgba(15,23,42,.12),0 4px 10px rgba(15,23,42,.08);
        padding:8px;
        z-index:999;
        opacity:0;
        visibility:hidden;
        pointer-events:none;
        transform:translateY(-6px) scale(.98);
        transition:opacity .18s ease, transform .18s ease, visibility .18s ease;
    }
    .kbf-notif-dropdown.kbf-open{opacity:1;visibility:visible;pointer-events:auto;transform:translateY(0) scale(1);}
    .kbf-notif-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:6px 8px 8px;
        border-bottom:1px solid #eef2f7;
        margin-bottom:4px;
    }
    .kbf-notif-head strong{font-size:12.5px;color:#0f172a;}
    .kbf-notif-head-count{
        font-size:11px;
        font-weight:600;
        color:#1d4ed8;
        background:#eff6ff;
        border:1px solid #bfdbfe;
        border-radius:999px;
        padding:2px 8px;
    }
    .kbf-notif-list{max-height:340px;overflow:auto;padding-right:2px;}
    .kbf-notif-item{
        display:block;
        text-decoration:none;
        color:#0f172a;
        border-radius:10px;
        padding:8px;
        border:1px solid transparent;
    }
    .kbf-notif-item + .kbf-notif-item{margin-top:4px;}
    .kbf-notif-item.is-unread{background:#f8fbff;border-color:#dbeafe;}
    .kbf-notif-item:hover{background:#f8fafc;border-color:#e2e8f0;}
    .kbf-notif-item-title{display:block;font-size:12.5px;font-weight:600;line-height:1.35;}
    .kbf-notif-item-msg{display:block;font-size:12px;color:#475569;line-height:1.35;margin-top:2px;}
    .kbf-notif-item-time{display:block;font-size:11px;color:#94a3b8;line-height:1.2;margin-top:4px;}
    .kbf-notif-empty{padding:14px 10px;color:#64748b;font-size:12px;text-align:center;}
    .kbf-notif-view-all{
        display:flex;
        align-items:center;
        justify-content:center;
        text-decoration:none;
        margin-top:6px;
        padding:8px 10px;
        border-radius:10px;
        font-size:12px;
        font-weight:600;
        color:#1d4ed8;
        background:#f8fbff;
        border:1px solid #dbeafe;
    }
    .kbf-notif-view-all:hover{background:#eff6ff;}
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
    .kbf-hamburger i{
        font-size:18px;
        display:block;
        color:#64748b;
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
    @keyframes kbfFadeIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
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
    .kbf-mobile-menu.kbf-menu-open{transform:translateY(65px);display:flex;}
    .kbf-mobile-notif-menu{
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
    .kbf-mobile-notif-menu.kbf-menu-open{transform:translateY(65px);display:flex;}
    .kbf-mobile-notif-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        padding:13px 16px;
        border-bottom:1px solid var(--kbf-border);
    }
    .kbf-mobile-notif-head strong{font-size:13px;color:#0f172a;}
    .kbf-mobile-notif-head-count{
        font-size:11px;
        font-weight:600;
        color:#1d4ed8;
        background:#eff6ff;
        border:1px solid #bfdbfe;
        border-radius:999px;
        padding:2px 8px;
    }
    .kbf-mobile-notif-list{
        max-height:min(60vh, 460px);
        overflow:auto;
        padding:8px;
    }
    .kbf-mobile-notif-item{
        display:block;
        text-decoration:none;
        color:#0f172a;
        border-radius:10px;
        padding:10px;
        border:1px solid transparent;
    }
    .kbf-mobile-notif-item + .kbf-mobile-notif-item{margin-top:4px;}
    .kbf-mobile-notif-item.is-unread{background:#f8fbff;border-color:#dbeafe;}
    .kbf-mobile-notif-item-title{display:block;font-size:12.5px;font-weight:600;line-height:1.35;}
    .kbf-mobile-notif-item-msg{display:block;font-size:12px;color:#475569;line-height:1.35;margin-top:2px;}
    .kbf-mobile-notif-item-time{display:block;font-size:11px;color:#94a3b8;line-height:1.2;margin-top:4px;}
    .kbf-mobile-notif-empty{padding:14px 10px;color:#64748b;font-size:12px;text-align:center;}
    .kbf-mobile-notif-clear{
        display:flex;
        align-items:center;
        justify-content:center;
        text-decoration:none;
        margin:0 8px 8px;
        padding:8px 10px;
        border-radius:10px;
        font-size:12px;
        font-weight:600;
        color:#1d4ed8;
        background:#f8fbff;
        border:1px solid #dbeafe;
    }
    .kbf-mobile-notif-clear:hover{background:#eff6ff;}
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
        align-items:center;
        height:auto !important;
        min-height:43px !important;
        padding:12px 14px !important;border-top:1px solid var(--kbf-border);
        background:var(--kbf-slate-lt);
        box-sizing:border-box;
    }
    .kbf-mobile-menu-actions .kbf-btn{
        flex:1;
        justify-content:center;
        align-items:center;
        height:43px !important;
        min-height:43px !important;
        padding:0 12px !important;
        line-height:1 !important;
        box-sizing:border-box;
    }

    .kbf-dashboard-avatar-wrap{position:relative;width:34px;height:34px;flex-shrink:0;}
    .kbf-dashboard-avatar-wrap .kbf-dashboard-avatar{width:34px;height:34px;border-radius:50%;border:1px solid var(--kbf-border);object-fit:cover;box-shadow:0 8px 18px #1018281f;display:block;}
    .kbf-dashboard-avatar-fallback{
        width:34px;height:34px;border-radius:50%;
        border:1px solid var(--kbf-border);
        background:var(--kbf-navy);
        display:inline-flex;align-items:center;justify-content:center;
        box-shadow:0 8px 18px #1018281f;
    }
    .kbf-dashboard-avatar-fallback i{font-size:16px;color:#ffffff;}
    .kbf-dashboard-verified{
        position:absolute;
        right:-2px;
        bottom:-2px;
        width:14px;
        height:14px;
        border-radius:50%;
        background:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        box-shadow:0 0 0 1px #fff;
        color:#1d4ed8;
    }
    .kbf-dashboard-verified i{font-size:10px;}
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
    .kbf-dashboard-name{font-size:12.5px;}
    .kbf-dashboard-user:hover{ color:#1f2a44; }
    .kbf-user-menu{position:relative;display:inline-flex;align-items:center;}
    .kbf-user-dropdown{
        position:absolute;
        top:calc(100% + 8px);
        right:0;
        min-width:160px;
        background:#ffffff;
        border:1px solid #e2e8f0;
        border-radius:14px;
        box-shadow:
          0 14px 30px rgba(15,23,42,.12),
          0 4px 10px rgba(15,23,42,.08);
        padding:8px;
        display:block;
        z-index:999;
        backdrop-filter:blur(10px);
        opacity:0;
        visibility:hidden;
        pointer-events:none;
        transform:translateY(-6px) scale(0.98);
        transition:opacity .18s ease, transform .18s ease, visibility .18s ease;
    }
    .kbf-user-dropdown a{
        display:flex;
        align-items:center;
        gap:8px;
        padding:8px 10px;
        border-radius:10px;
        font-size:12.5px;
        color:#1f2a44;
        text-decoration:none;
        font-weight:600;
    }
    .kbf-user-dropdown a:hover{
        background:linear-gradient(90deg,#e7f1ff 0%, #edf5ff 60%, #f8fbff 100%);
        box-shadow:
          inset 0 0 0 1px #bfdbfe,
          0 8px 18px rgba(59,130,246,.16);
    }
    .kbf-user-dropdown.kbf-open{
        display:block;
        opacity:1;
        visibility:visible;
        pointer-events:auto;
        transform:translateY(0) scale(1);
    }
    .kbf-hero-wrap{
        padding:0;
        margin-top:50px;
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
    .kbf-cashout-table td:nth-child(1){width:22%;}
    .kbf-cashout-table th:nth-child(2),
    .kbf-cashout-table td:nth-child(2){width:11%;}
    .kbf-cashout-table th:nth-child(3),
    .kbf-cashout-table td:nth-child(3){width:13%;}
    .kbf-cashout-table th:nth-child(4),
    .kbf-cashout-table td:nth-child(4){width:16%;}
    .kbf-cashout-table th:nth-child(5),
    .kbf-cashout-table td:nth-child(5){width:12%;}
    .kbf-cashout-table th:nth-child(6),
    .kbf-cashout-table td:nth-child(6){width:9%;}
    .kbf-cashout-table th:nth-child(7),
    .kbf-cashout-table td:nth-child(7){width:9%;}
    .kbf-cashout-table th:nth-child(8),
    .kbf-cashout-table td:nth-child(8){width:8%;}
    .kbf-cashout-table .kbf-note-cell{white-space:normal;}
    .kbf-cashout-table .kbf-note-row td,
    .kbf-cashout-table .kbf-note-cell{
        display:table-cell !important;
        width:auto !important;
    }
    .kbf-cashout-wrap + .kbf-table-pager .kbf-table-pager-btn.is-loading::after{
        top:50%;
        left:50%;
        transform:translate(-50%,-50%);
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
        background:
          radial-gradient(85% 90% at 0% 0%, #4f93ff24 0%, #4f93ff00 60%),
          radial-gradient(85% 90% at 100% 0%, #a1d2ff28 0%, #a1d2ff00 60%),
          linear-gradient(135deg,#f1f6ff 0%,#fbfdff 55%,#eef5ff 100%);
        border:1px solid #d7e6ff;
        border-top:0;
        border-radius:22px;
        padding:22px 24px;
        box-shadow:none;
        margin-bottom:0;
        width:100%;
        box-sizing:border-box;
    }
    @media (max-width: 1200px){
        .kbf-hero-banner{
            padding-left:10px;
            padding-right:10px;
        }
        .kbf-hero-wrap{}
    }
    .kbf-user-ui{
        background-image:none !important;
    }
    .kbf-user-ui .kbf-wrap{
        padding:0 !important;
        overflow:visible !important;
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
    .kbf-cta-card{
        background:#fff;
        border:1px solid var(--kbf-border);
        border-radius:20px;
        padding:18px 20px;
        display:flex;
        gap:18px;
        align-items:flex-start;
        justify-content:space-between;
        box-shadow:0 10px 24px rgba(15,23,42,.06);
    }
    .kbf-cta-eyebrow{
        font-size:11px;
        letter-spacing:.22em;
        text-transform:uppercase;
        color:#94a3b8;
        font-weight:700;
        margin-bottom:4px;
    }
    .kbf-cta-title{
        font-size:18px;
        font-weight:800;
        color:#0f172a;
        margin:0 0 6px;
    }
    .kbf-cta-sub{
        font-size:13px;
        color:#6b7280;
        margin-bottom:10px;
        max-width:520px;
    }
    .kbf-cta-checklist{
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
        gap:8px 14px;
        max-width:640px;
    }
    .kbf-cta-check{
        display:flex;
        align-items:center;
        gap:8px;
        font-size:12.5px;
        color:#475569;
        line-height:1.35;
    }
    .kbf-cta-check > i{
        width:18px;
        height:18px;
        border-radius:50%;
        background:#e7f1ff;
        color:#2563eb;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        flex:0 0 18px;
    }
    .kbf-cta-check > i > i{
        font-size:10px;
        line-height:1;
    }
    .kbf-cta-right{
        display:flex;
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
        min-width:220px;
    }
    .kbf-cta-actions{
        display:flex;
        flex-direction:column;
        gap:8px;
        width:100%;
    }
    .kbf-cta-actions{
        align-items:stretch;
    }
    .kbf-cta-actions .kbf-btn{
        width:230px !important;
        justify-content:center;
    }
    .kbf-cta-note{
        font-size:12px;
        color:#64748b;
        max-width:220px;
    }
    @media (max-width: 900px){
        .kbf-cta-card{
            flex-direction:column;
            align-items:stretch;
        }
        .kbf-cta-right{
            align-items:flex-start;
        }
        .kbf-cta-actions .kbf-btn{
            width:100%;
        }
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
        .kbf-dashboard-shell{ max-width:90%; padding-top:74px; }
        .kbf-topbar + .kbf-dashboard-shell{ margin-top:0 !important; }
        .kbf-hero-wrap{ margin-top:12px; }
        .kbf-hero-grid{ grid-template-columns:1fr; }
        .kbf-topbar{ flex-wrap:nowrap; }
        .kbf-nav{ display:none; }
        .kbf-hamburger{ display:inline-flex; }
        .kbf-actions > a.kbf-btn{ display:none; }
        .kbf-dashboard-name{ display:none; }
        .kbf-notif-dropdown{ display:none !important; }
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
        border-radius:16px;
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
        border-bottom:0;
    }
    .kbf-user-ui .kbf-modal-header h3{
        margin:0;
        font-size:17px;
        font-weight:400;
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
        overflow-y:auto;
        max-height:calc(90vh - 140px);
    }
    .kbf-user-ui .kbf-modal-footer{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        padding:16px 24px 22px;
        background:#ffffff;
        border-top:0;
    }
    .kbf-user-ui #kbf-modal-create .kbf-modal-footer,
    .kbf-user-ui #kbf-modal-edit .kbf-modal-footer{
        justify-content:space-between;
        position:sticky;
        bottom:0;
        background:#ffffff;
    }
    .kbf-user-ui #kbf-modal-create .kbf-modal-footer .kbf-btn-secondary.kbf-modal-left,
    .kbf-user-ui #kbf-modal-edit .kbf-modal-footer .kbf-btn-secondary.kbf-modal-left{
        margin-right:auto;
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
        width:min(920px, calc(100vw - 32px));
        max-height:calc(100vh - 32px);
        padding:0;
        background:linear-gradient(180deg,#ffffff 0%, #f8fbff 100%);
        border:none;
        border-radius:26px;
        box-shadow:0 34px 90px rgba(15,23,42,.16), 0 8px 24px rgba(37,99,235,.08);
        backdrop-filter:blur(4px);
        overflow:hidden;
        transform:translateY(0) scale(1);
        animation:kbfAuthModalIn .22s ease-out;
    }
    @keyframes kbfAuthModalIn{
        from{opacity:0;transform:translateY(8px) scale(.985);}
        to{opacity:1;transform:translateY(0) scale(1);}
    }
    .kbf-user-ui .kbf-auth-modal .kbf-modal-close{
        position:absolute;
        top:18px;
        right:18px;
        z-index:3;
        width:36px;
        height:36px;
        border-radius:999px;
        border:1px solid #dbe4f0;
        background:#ffffffd9;
        color:#475569;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        box-shadow:0 4px 12px rgba(15,23,42,.08);
    }
    .kbf-user-ui .kbf-auth-modal .kbf-modal-close:hover{
        background:#ffffff;
        color:#0f172a;
        border-color:#cbd5e1;
    }
    .kbf-user-ui .kbf-auth-modal .kbf-modal-close:focus-visible{
        outline:2px solid #3b82f6;
        outline-offset:2px;
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
        max-width:150px;
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
        font-size:24px;
        font-weight:700;
        margin:0;
        color:#0f172a;
        letter-spacing:-.01em;
    }
    .kbf-user-ui .kbf-auth-main p{
        margin:0;
        color:#4f5a6b;
        font-size:13.5px;
        line-height:1.6;
    }
    .kbf-user-ui #kbf-auth-reason{
        max-width:44ch;
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
        min-height:46px;
        border-radius:12px;
        align-self:stretch;
        box-sizing:border-box;
        font-weight:600;
    }
    .kbf-user-ui .kbf-auth-actions .kbf-btn:focus-visible{
        outline:2px solid #3b82f6;
        outline-offset:2px;
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
            flex-direction:column;
            min-height:0;
        }
        .kbf-user-ui .kbf-auth-side{
            min-height:170px;
            padding:22px;
            flex:0 0 auto;
        }
        .kbf-user-ui .kbf-auth-main{
            border-left:0;
            border-top:1px solid #e2e8f0;
            padding:26px 22px;
            flex:1 1 auto;
        }
        .kbf-user-ui .kbf-auth-quote{
            font-size:18px;
            line-height:1.35;
        }
        .kbf-user-ui .kbf-auth-sub{
            font-size:12px;
            line-height:1.5;
        }
    }
    @media (max-width: 560px){
        .kbf-user-ui #kbf-auth-modal{
            padding:10px;
        }
        .kbf-user-ui .kbf-auth-modal{
            width:calc(100vw - 20px);
            max-height:calc(100vh - 20px);
            border-radius:18px;
        }
        .kbf-user-ui .kbf-auth-modal .kbf-modal-close{
            top:10px;
            right:10px;
        }
        .kbf-user-ui .kbf-auth-side{
            min-height:130px;
            padding:16px;
        }
        .kbf-user-ui .kbf-auth-main{
            padding:18px 16px;
        }
        .kbf-user-ui .kbf-auth-main h3{
            font-size:18px;
        }
    }
    @media (max-width: 720px){
        .kbf-user-ui .kbf-auth-side-inner{
            align-items:center;
            text-align:center;
            margin:0 auto;
        }
        .kbf-user-ui .kbf-auth-chip{
            margin-left:auto;
            margin-right:auto;
        }
        .kbf-user-ui .kbf-auth-sub{
            margin-left:auto;
            margin-right:auto;
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
        resize:none;
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
    .kbf-user-ui .kbf-auth-legal{
        display:flex;
        gap:6px;
        align-items:center;
        justify-content:flex-start;
        font-size:13px;
        color:var(--kbf-slate, #64748b);
        margin-top:2px;
        cursor:pointer;
    }
    .kbf-user-ui .kbf-auth-legal input{
        width:14px;
        height:14px;
        accent-color:var(--kbf-blue, #2563eb);
        cursor:pointer;
    }
    .kbf-user-ui .kbf-auth-legal a{
        color:var(--kbf-blue, #2563eb);
        text-decoration:none;
        font-weight:600;
    }
    .kbf-user-ui .kbf-auth-legal a:hover{
        text-decoration:underline;
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
    @media (max-width: 1200px){
        .kbf-user-ui .kbf-stepper .kbf-step{display:none;}
        .kbf-user-ui .kbf-stepper .kbf-step.is-active{display:inline-flex;}
        .kbf-user-ui .kbf-step::after{display:none;}
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
    .kbf-user-ui #kbf-modal-create .kbf-modal-body{
        overflow-x:hidden;
    }
    .kbf-user-ui #kbf-modal-create .kbf-step-content[data-step="3"]{
        width:100%;
        max-width:100%;
        overflow-x:hidden;
    }
    .kbf-user-ui #kbf-modal-create .kbf-form-row{
        flex-wrap:wrap;
    }
    .kbf-user-ui #kbf-modal-create .kbf-form-row > .kbf-form-group{
        width:100%;
        min-width:0;
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
    .kbf-user-ui .kbf-milestone-photo-input{
        display:none;
    }
    .kbf-user-ui .kbf-milestone-photo-grid{
        grid-template-columns:repeat(auto-fill, minmax(120px, 1fr));
    }
    .kbf-user-ui .kbf-milestone-photo-grid .kbf-photo-thumb,
    .kbf-user-ui .kbf-milestone-photo-grid .kbf-photo-add{
        aspect-ratio:4/3;
        height:auto;
    }
    .kbf-user-ui .kbf-benefits-list{
        display:grid;
        gap:12px;
        margin-top:10px;
    }
    .kbf-user-ui .kbf-benefit-card{
        border:1px solid #e2e8f0;
        border-radius:16px;
        background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%);
        padding:10px;
        display:grid;
        gap:6px;
        box-shadow:0 1px 2px rgba(15,23,42,.03);
        transition:border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .kbf-user-ui .kbf-benefit-card:focus-within{
        border-color:#bfdbfe;
        box-shadow:0 0 0 3px #dbeafe;
    }
    .kbf-user-ui .kbf-benefit-card.is-invalid{
        border-color:#fecdd3;
    }
    .kbf-user-ui .kbf-benefit-row{
        display:grid;
        grid-template-columns:minmax(0,1fr) 124px 34px;
        gap:8px;
        align-items:center;
    }
    .kbf-user-ui .kbf-benefit-row .kbf-benefit-title{
        min-width:0;
    }
    .kbf-user-ui .kbf-benefit-row input{
        height:40px;
    }
    .kbf-user-ui .kbf-benefit-amount{
        text-align:left;
    }
    .kbf-user-ui .kbf-benefit-remove{
        width:34px;
        height:34px;
        border-radius:10px;
        border:1px solid #e2e8f0;
        background:#ffffff;
        color:#64748b;
        font-size:16px;
        line-height:1;
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        transition:border-color .2s ease, color .2s ease, background .2s ease;
    }
    .kbf-user-ui .kbf-benefit-remove:hover{
        border-color:#fecaca;
        background:#fff5f5;
        color:#dc2626;
    }
    .kbf-user-ui .kbf-benefit-desc{
        min-height:64px;
        resize:none;
    }
    .kbf-user-ui .kbf-benefit-meta{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:8px;
        min-height:0;
    }
    .kbf-user-ui .kbf-benefit-meta-amount{
        display:none;
    }
    .kbf-user-ui .kbf-benefit-meta-amount.has-error{
        display:flex;
    }
    .kbf-user-ui .kbf-benefit-counter{
        font-size:11.5px;
        color:#64748b;
        line-height:1.25;
        margin-left:auto;
        text-align:right;
    }
    .kbf-user-ui .kbf-benefit-field-error{
        font-size:11.5px;
        color:#e11d48;
        line-height:1.25;
    }
    .kbf-user-ui .kbf-benefit-field-error:empty{
        display:none;
    }
    .kbf-user-ui .kbf-benefit-add{
        margin-top:10px;
        padding:8px 14px;
        font-size:13px;
    }
    @media (max-width: 600px){
        .kbf-user-ui .kbf-benefit-card{
            position:relative;
            padding-top:42px;
        }
        .kbf-user-ui .kbf-benefit-row{
            grid-template-columns:1fr;
            grid-template-areas:
                "title"
                "amount";
            align-items:start;
        }
        .kbf-user-ui .kbf-benefit-row .kbf-benefit-title{grid-area:title;}
        .kbf-user-ui .kbf-benefit-row .kbf-benefit-amount{grid-area:amount;}
        .kbf-user-ui .kbf-benefit-row .kbf-benefit-remove{
            position:absolute;
            top:10px;
            right:10px;
            width:30px;
            height:30px;
            justify-self:auto;
        }
        .kbf-user-ui .kbf-benefit-amount{
            text-align:left;
        }
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
        display:flex;
        align-items:center;
        justify-content:center;
        background:#f8fafcbf;
        
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
html.kbf-modal-lock, body.kbf-modal-lock {
    overflow: hidden !important;
    overscroll-behavior: none;
}
    .kbf-user-ui #kbf-modal-create .kbf-modal-body,
    .kbf-user-ui #kbf-modal-edit .kbf-modal-body{
        overflow-y:hidden !important;
    }
    .kbf-user-ui .kbf-modal-body p{font-weight:400;}
    /* ===== CREATE MODAL REDESIGN ===== */
    #kbf-modal-create .kbf-modal{max-width:900px;}
    #kbf-modal-create .kbf-modal{height:min(90vh, 720px);}
    /* Keep parent scrollbar styling consistent with create modal body. */
    #kbf-modal-create .kbf-modal{
        scrollbar-width:thin;
        scrollbar-color:#2070e0 #f8fafc;
    }
    #kbf-modal-create .kbf-modal::-webkit-scrollbar{
        width:10px;
    }
    #kbf-modal-create .kbf-modal::-webkit-scrollbar-button{
        width:0;
        height:0;
        display:none;
    }
    #kbf-modal-create .kbf-modal::-webkit-scrollbar-track{
        background:#f8fafc;
        border-radius:999px;
    }
    #kbf-modal-create .kbf-modal::-webkit-scrollbar-thumb{
        background:#2070e0;
        border-radius:999px;
        border:2px solid #f8fafc;
    }
    #kbf-modal-create .kbf-modal::-webkit-scrollbar-thumb:hover{
        background:#2070e0;
    }
    #kbf-modal-create .kbf-modal-body{
        max-height:none;
        flex:1 1 auto;
        overflow-y:hidden;
    }
    #kbf-modal-create .kbf-modal-body{
        scrollbar-width:thin;
        scrollbar-color:#2070e0 #f8fafc;
    }
    #kbf-modal-create .kbf-modal-body::-webkit-scrollbar{
        width:10px;
    }
    #kbf-modal-create .kbf-modal-body::-webkit-scrollbar-button{
        width:0;
        height:0;
        display:none;
    }
    #kbf-modal-create .kbf-modal-body::-webkit-scrollbar-track{
        background:#f8fafc;
        border-radius:999px;
    }
    #kbf-modal-create .kbf-modal-body::-webkit-scrollbar-thumb{
        background:#2070e0;
        border-radius:999px;
        border:2px solid #f8fafc;
    }
    #kbf-modal-create .kbf-modal-body::-webkit-scrollbar-thumb:hover{
        background:#2070e0;
    }
    #kbf-modal-edit .kbf-modal{max-width:900px;height:min(90vh, 720px);}
    #kbf-modal-edit .kbf-modal-body{
        max-height:none;
        flex:1 1 auto;
        overflow-y:hidden;
        scrollbar-width:thin;
        scrollbar-color:#2070e0 #f8fafc;
    }
    @media (max-width: 900px){
        .kbf-user-ui #kbf-modal-create .kbf-modal-body,
        .kbf-user-ui #kbf-modal-edit .kbf-modal-body{
            overflow-y:auto !important;
            max-height:calc(100vh - 180px);
        }
        .kbf-user-ui #kbf-modal-create .kbf-step-content,
        .kbf-user-ui #kbf-modal-edit .kbf-step-content{
            overflow:visible !important;
            height:auto !important;
        }
    }
    #kbf-modal-edit .kbf-modal-body::-webkit-scrollbar{
        width:10px;
    }
    #kbf-modal-edit .kbf-modal-body::-webkit-scrollbar-button{
        width:0;
        height:0;
        display:none;
    }
    #kbf-modal-edit .kbf-modal-body::-webkit-scrollbar-track{
        background:#f8fafc;
        border-radius:999px;
    }
    #kbf-modal-edit .kbf-modal-body::-webkit-scrollbar-thumb{
        background:#2070e0;
        border-radius:999px;
        border:2px solid #f8fafc;
    }
    #kbf-modal-edit .kbf-modal-body::-webkit-scrollbar-thumb:hover{
        background:#2070e0;
    }
    #kbf-modal-create .kbf-create-stepper{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:wrap;
        width:fit-content;
        margin-bottom:18px;
    }
    #kbf-modal-create .kbf-create-step{
        display:flex;
        align-items:center;
        gap:8px;
        border:1px solid transparent;
        background:transparent;
        color:#64748b;
        border-radius:999px;
        padding:6px 14px;
        font-size:12.5px;
        font-weight:600;
        transition:all .2s ease;
        cursor:pointer;
    }
    #kbf-modal-create .kbf-create-step.is-active{
        background:#ffffff;
        color:#1e3a8a;
        border-color:#bfdbfe;
        font-weight:700;
        box-shadow:0 6px 14px rgba(59,130,246,.12);
    }
    #kbf-modal-create .kbf-create-step.is-complete{
        background:#ffffff;
        color:#15803d;
        border-color:#86efac;
        font-weight:700;
        box-shadow:0 6px 14px rgba(34,197,94,.18);
    }
    #kbf-modal-create .kbf-create-step:disabled{
        opacity:.55;
        cursor:not-allowed;
    }
    #kbf-modal-create .kbf-step-index{
        width:22px;height:22px;
        border-radius:999px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        background:#e7efff;
        font-size:12px;
        color:#1d4ed8;
        font-weight:700;
    }
    #kbf-modal-create .kbf-create-step.is-active .kbf-step-index{
        background:linear-gradient(135deg,#5ba8f5,#2f6fe9);
        border-color:#2f6fe9;
        color:#ffffff;
    }
    #kbf-modal-create .kbf-create-step.is-complete .kbf-step-index{
        background:#dcfce7;
        border-color:#86efac;
        color:#15803d;
    }
    #kbf-modal-create .kbf-create-step-line{
        display:inline-flex;
        width:10px;
        height:2px;
        border-radius:999px;
        background:#cbd5f5;
    }
    #kbf-modal-create .kbf-create-step.is-active + .kbf-create-step-line,
    #kbf-modal-create .kbf-create-step.is-complete + .kbf-create-step-line{
        background:#5ba8f5;
    }

    /* ===== EDIT MODAL STEPPER (MATCH CREATE) ===== */
    #kbf-modal-edit .kbf-stepper{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:wrap;
        width:fit-content;
        margin-bottom:18px;
    }
    #kbf-modal-edit .kbf-step{
        display:flex;
        align-items:center;
        gap:8px;
        border:1px solid transparent;
        background:transparent;
        color:#64748b;
        border-radius:999px;
        padding:6px 14px;
        font-size:12.5px;
        font-weight:600;
        transition:all .2s ease;
        cursor:pointer;
    }
    #kbf-modal-edit .kbf-step.is-active{
        background:#ffffff;
        color:#1e3a8a;
        border-color:#bfdbfe;
        font-weight:700;
        box-shadow:0 6px 14px rgba(59,130,246,.12);
    }
    #kbf-modal-edit .kbf-step.is-complete{
        background:#ffffff;
        color:#15803d;
        border-color:#86efac;
        font-weight:700;
        box-shadow:0 6px 14px rgba(34,197,94,.18);
    }
    #kbf-modal-edit .kbf-step:disabled{
        opacity:.55;
        cursor:not-allowed;
    }
    #kbf-modal-edit .kbf-step > span:first-child{
        width:22px;height:22px;
        border-radius:999px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        background:#e7efff;
        font-size:12px;
        color:#1d4ed8;
        font-weight:700;
    }
    #kbf-modal-edit .kbf-step.is-active > span:first-child{
        background:linear-gradient(135deg,#5ba8f5,#2f6fe9);
        border-color:#2f6fe9;
        color:#ffffff;
    }
    #kbf-modal-edit .kbf-step.is-complete > span:first-child{
        background:#dcfce7;
        border-color:#86efac;
        color:#15803d;
    }
    .kbf-user-ui #kbf-modal-edit .kbf-step-content{
        height:auto !important;
        overflow:visible !important;
    }
    #kbf-modal-create .kbf-create-panel{display:none;}
    #kbf-modal-create .kbf-create-panel.is-active{display:block;}
    #kbf-modal-create .kbf-choice-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(140px,1fr));
        gap:12px;
        margin-top:8px;
    }
    #kbf-modal-create .kbf-choice-card{
        border:1px solid #e2e8f0;
        border-radius:14px;
        padding:14px;
        text-align:left;
        background:#ffffff;
        font-weight:600;
        transition:all .2s ease;
        cursor:pointer;
    }
    #kbf-modal-create .kbf-choice-card.is-selected{
        border-color:#60a5fa;
        box-shadow:0 8px 18px rgba(59,130,246,.18);
        background:#eff6ff;
    }
    #kbf-modal-create .kbf-category-grid{
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:10px;
        margin-top:8px;
    }
    @media (max-width: 780px){
        #kbf-modal-create .kbf-category-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
    }
    #kbf-modal-create .kbf-category-card{
        border:1px solid #e2e8f0;
        border-radius:12px;
        padding:10px;
        display:flex;
        align-items:center;
        gap:8px;
        background:#ffffff;
        font-size:12.5px;
        font-weight:600;
        color:#334155;
        transition:all .2s ease;
        cursor:pointer;
    }
    #kbf-modal-create .kbf-category-card i{font-size:16px;}
    #kbf-modal-create .kbf-category-card.is-selected{
        border-color:#60a5fa;
        background:#eff6ff;
        color:#1d4ed8;
    }
    #kbf-modal-create .kbf-category-card.is-hidden{display:none;}
    #kbf-modal-create .kbf-photo-grid{
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
        gap:10px;
        margin-top:10px;
    }
    @media (max-width: 820px){
        #kbf-modal-create .kbf-photo-grid{grid-template-columns:repeat(3,minmax(0,1fr));}
    }
    #kbf-modal-create .kbf-photo-slot{
        position:relative;
        border:1px solid transparent;
        outline:1px dashed #cbd5f5;
        outline-offset:-1px;
        border-radius:14px;
        background:#f8fafc;
        aspect-ratio:4 / 3;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:12px;
        color:#64748b;
        overflow:hidden;
        cursor:pointer;
        box-sizing:border-box;
        padding:0;
    }
    #kbf-modal-create .kbf-photo-slot img{
        width:100%;
        height:100%;
        object-fit:cover;
        object-position:center;
        border-radius:inherit;
    }
    #kbf-modal-create .kbf-photo-slot .kbf-photo-remove{
        position:absolute;
        top:6px;
        right:6px;
        width:22px;
        height:22px;
        border-radius:50%;
        border:none;
        background:#0f172abf;
        color:#fff;
        font-size:14px;
        line-height:1;
        display:flex;
        align-items:center;
        justify-content:center;
        opacity:1;
        transform:scale(1);
        transition:opacity .15s ease, transform .15s ease, background .15s ease;
        cursor:pointer;
        pointer-events:auto;
    }
    #kbf-modal-create .kbf-photo-slot .kbf-photo-edit{
        position:absolute;
        top:6px;
        right:32px;
        width:22px;
        height:22px;
        border-radius:999px;
        border:0;
        background:#0f172abf;
        color:#fff;
        font-size:14px;
        line-height:1;
        display:flex;
        align-items:center;
        justify-content:center;
        opacity:1;
        transform:scale(1);
        transition:opacity .15s ease, transform .15s ease, background .15s ease;
        cursor:pointer;
        padding:0;
        pointer-events:auto;
    }
    #kbf-modal-create .kbf-photo-slot .kbf-photo-edit svg{
        width:12px;
        height:12px;
    }
    #kbf-modal-create .kbf-photo-slot .kbf-photo-edit{
        opacity:1;
        transform:scale(1);
    }
    #kbf-modal-create .kbf-photo-slot .kbf-photo-cover{
        position:absolute;
        bottom:6px;
        left:6px;
        background:#1d4ed8;
        color:#ffffff;
        font-size:10px;
        padding:2px 6px;
        border-radius:999px;
    }
    /* ===== EDIT MODAL PHOTO GRID (MATCH CREATE) ===== */
    #kbf-modal-edit .kbf-photo-previews,
    #kbf-modal-edit .kbf-photo-grid{
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
        gap:10px;
        margin-top:10px;
    }
    @media (max-width: 820px){
        #kbf-modal-edit .kbf-photo-previews,
        #kbf-modal-edit .kbf-photo-grid{grid-template-columns:repeat(3,minmax(0,1fr));}
    }
    @media (max-width: 640px){
        #kbf-modal-edit .kbf-photo-previews,
        #kbf-modal-edit .kbf-photo-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
    }
    @media (max-width: 420px){
        #kbf-modal-edit .kbf-photo-previews,
        #kbf-modal-edit .kbf-photo-grid{grid-template-columns:repeat(1,minmax(0,1fr));}
    }
    #kbf-modal-edit .kbf-photo-grid .kbf-photo-slot,
    #kbf-modal-edit .kbf-photo-thumb,
    #kbf-modal-edit .kbf-photo-slot,
    #kbf-modal-edit .kbf-photo-add{
        position:relative;
        border:1px solid transparent;
        outline:1px dashed #cbd5f5;
        outline-offset:-1px;
        border-radius:14px;
        background:#f8fafc;
        aspect-ratio:4 / 3 !important;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:12px;
        color:#64748b;
        overflow:hidden;
        cursor:pointer;
        box-sizing:border-box;
        padding:0;
        height:auto !important;
    }
    #kbf-modal-edit .kbf-photo-add{
        height:auto !important;
    }
    #kbf-modal-edit .kbf-photo-thumb img{
        width:100%;
        height:100%;
        object-fit:cover;
        object-position:center;
        border-radius:inherit;
    }
    #kbf-modal-edit .kbf-photo-remove{
        position:absolute;
        top:6px;
        right:6px;
        width:22px;
        height:22px;
        border-radius:50%;
        border:none;
        background:#0f172abf;
        color:#fff;
        font-size:14px;
        line-height:1;
        display:flex;
        align-items:center;
        justify-content:center;
        opacity:1;
        transform:scale(1);
        transition:opacity .15s ease, transform .15s ease, background .15s ease;
        cursor:pointer;
        pointer-events:auto;
    }
    #kbf-modal-edit .kbf-photo-edit{
        position:absolute;
        top:6px;
        right:32px;
        width:22px;
        height:22px;
        border-radius:50%;
        border:none;
        background:#0f172abf;
        color:#fff;
        font-size:14px;
        line-height:1;
        display:flex;
        align-items:center;
        justify-content:center;
        opacity:1;
        transform:scale(1);
        transition:opacity .15s ease, transform .15s ease, background .15s ease;
        cursor:pointer;
        padding:0;
        pointer-events:auto;
    }
    #kbf-modal-edit .kbf-photo-edit svg{
        width:12px;
        height:12px;
    }
    #kbf-modal-create .kbf-tier-card{
        border:1px solid #e2e8f0;
        border-radius:16px;
        padding:14px;
        margin-bottom:12px;
        background:#ffffff;
    }
    #kbf-modal-create .kbf-tier-list{
        display:flex;
        flex-direction:column;
        gap:12px;
    }
    #kbf-modal-create .kbf-tier-header{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        margin-bottom:12px;
    }
    #kbf-modal-create .kbf-tier-header-meta{
        display:flex;
        flex-direction:column;
        gap:4px;
        min-width:0;
    }
    #kbf-modal-create .kbf-tier-badge{
        display:inline-flex;
        align-items:center;
        width:max-content;
        max-width:100%;
        padding:0;
        border-radius:999px;
        background:transparent;
        border:none;
        color:#0f172a;
        font-size:14px;
        font-weight:700;
        letter-spacing:0;
        text-transform:none;
    }
    #kbf-modal-create .kbf-tier-hint{
        color:#64748b;
        font-size:12px;
        line-height:1.45;
    }
    #kbf-modal-create .kbf-tier-remove{
        border:1px solid #fee2e2;
        background:#fff7f7;
        color:#dc2626;
        border-radius:999px;
        padding:5px 10px;
        font-size:11.5px;
        font-weight:600;
        cursor:pointer;
        transition:background .2s ease, border-color .2s ease;
        flex-shrink:0;
    }
    #kbf-modal-create .kbf-tier-remove:hover{
        background:#fee2e2;
        border-color:#fecaca;
    }
    #kbf-modal-create .kbf-tier-body{
        display:grid;
        grid-template-columns:minmax(0,1.6fr) minmax(180px,.9fr);
        gap:12px 14px;
        align-items:start;
    }
    #kbf-modal-create .kbf-tier-field{
        min-width:0;
    }
    #kbf-modal-create .kbf-tier-field-perks{
        grid-column:1 / -1;
    }
    #kbf-modal-create .kbf-tier-label{
        display:block;
        margin-bottom:6px;
        color:#334155;
        font-size:12px;
        font-weight:600;
    }
    #kbf-modal-create .kbf-tier-input{
        width:100%;
    }
    #kbf-modal-create .kbf-tier-amount-wrap{
        display:flex;
        align-items:center;
        border:1.5px solid #94a3b859;
        border-radius:14px;
        background:#fff;
        overflow:hidden;
        transition:border-color .2s ease, box-shadow .2s ease;
    }
    #kbf-modal-create .kbf-tier-amount-wrap:focus-within{
        border-color:#3b82f6b2;
        box-shadow:0 0 0 3px #3b82f61f;
    }
    #kbf-modal-create .kbf-tier-amount-prefix{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:48px;
        padding:0 12px;
        align-self:stretch;
        background:#f8fafc;
        color:#475569;
        font-weight:700;
        border-right:1px solid #e2e8f0;
    }
    #kbf-modal-create .kbf-tier-amount-wrap .kbf-tier-input{
        border:0 !important;
        box-shadow:none !important;
        border-radius:0 !important;
    }
    #kbf-modal-create .kbf-tier-textarea{
        min-height:96px;
        resize:vertical;
    }
    #kbf-modal-create .kbf-counter{
        display:block;
        margin-top:4px;
        font-size:11.5px;
        color:#64748b;
        text-align:left;
    }
    @media (max-width: 720px){
        #kbf-modal-create .kbf-tier-card{
            padding:14px;
            border-radius:18px;
        }
        #kbf-modal-create .kbf-tier-header{
            flex-direction:column;
            align-items:stretch;
        }
        #kbf-modal-create .kbf-tier-remove{
            align-self:flex-start;
        }
        #kbf-modal-create .kbf-tier-body{
            grid-template-columns:1fr;
            gap:12px;
        }
        #kbf-modal-create .kbf-tier-field-perks{
            grid-column:auto;
        }
    }
    .kbf-user-ui .kbf-field-error{
        margin-top:6px;
        font-size:11.5px;
        color:#e11d48;
    }
    #kbf-modal-create .kbf-modal-footer{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        width:100%;
        max-width:100%;
        box-sizing:border-box;
    }
    #kbf-modal-create .kbf-create-footer-actions{
        display:flex;
        align-items:center;
        gap:10px;
    }
    #kbf-modal-create .kbf-create-success{
        display:none;
        text-align:center;
        min-height:100%;
        height:100%;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:10px;
    }
    /* ===== CREATE MODAL RESPONSIVE ===== */
    @media (max-width: 980px){
        #kbf-modal-create .kbf-modal{max-width:92vw;}
    }
    @media (max-width: 820px){
        #kbf-modal-create .kbf-modal{height:min(92vh, 720px);}
        #kbf-modal-create .kbf-create-stepper{
            width:100%;
            justify-content:flex-start;
            gap:8px;
        }
        #kbf-modal-create .kbf-create-step{
            flex:1 1 calc(50% - 8px);
            justify-content:flex-start;
        }
        #kbf-modal-create .kbf-form-row{
            flex-direction:column;
            gap:12px;
        }
        #kbf-modal-create .kbf-form-row > .kbf-form-group{
            width:100%;
        }
        #kbf-modal-create .kbf-photo-grid{
            grid-template-columns:repeat(3,minmax(0,1fr));
        }
        #kbf-modal-create .kbf-category-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
        #kbf-modal-create .kbf-modal-footer{
            display:grid;
            grid-template-columns:1fr;
            grid-template-areas:
                "next"
                "back"
                "save";
            grid-auto-rows:auto;
            gap:10px;
            align-items:stretch;
            justify-items:stretch;
            width:100%;
            max-width:100%;
            box-sizing:border-box;
            margin:0;
        }
        #kbf-modal-create .kbf-modal-footer > *{
            min-width:0;
            max-width:100%;
        }
        #kbf-modal-create .kbf-create-footer-actions{
            display:contents;
        }
        #kbf-modal-create #kbf-create-next{
            grid-area:next;
            width:100%;
        }
        #kbf-modal-create #kbf-create-prev{
            grid-area:back;
            width:100%;
        }
        #kbf-modal-create #kbf-create-save-close{
            grid-area:save;
            width:100%;
        }
        #kbf-modal-create .kbf-modal-footer .kbf-btn{
            width:100%;
            min-width:0;
            display:flex;
            align-self:stretch;
            box-sizing:border-box;
        }
    }
    @media (max-width: 640px){
        #kbf-modal-create .kbf-modal{
            height:92vh;
            border-radius:14px;
        }
        #kbf-modal-create .kbf-modal-body{
            padding:18px 18px 16px;
        }
        #kbf-modal-create .kbf-modal-header{
            padding:16px 18px 14px;
        }
        #kbf-modal-create .kbf-modal-footer{
            padding:14px 18px 18px;
        }
        #kbf-modal-create .kbf-create-step{
            flex:1 1 100%;
        }
        #kbf-modal-create .kbf-photo-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
    }
    @media (max-width: 420px){
        #kbf-modal-create .kbf-photo-grid{
            grid-template-columns:repeat(1,minmax(0,1fr));
        }
    }
    @media (max-width: 900px){
        #kbf-modal-create .kbf-create-stepper{
            width:100%;
            flex-wrap:nowrap;
            justify-content:flex-start;
        }
        #kbf-modal-create .kbf-create-stepper .kbf-create-step,
        #kbf-modal-create .kbf-create-stepper .kbf-create-step-line{
            display:none;
        }
        #kbf-modal-create .kbf-create-stepper .kbf-create-step.is-active{
            display:flex;
            flex:1 1 100%;
            width:100%;
            justify-content:flex-start;
        }
        #kbf-modal-edit .kbf-stepper{
            width:100%;
            flex-wrap:nowrap;
            justify-content:flex-start;
        }
        #kbf-modal-edit .kbf-stepper .kbf-step{
            display:none;
        }
        #kbf-modal-edit .kbf-stepper .kbf-step.is-active{
            display:flex;
            flex:1 1 100%;
            width:100%;
            justify-content:flex-start;
        }
    }
    #kbf-modal-create .kbf-success-icon{
        width:84px;
        height:84px;
        margin:0 auto 16px;
    }
    #kbf-modal-create .kbf-success-ring{
        stroke:#22c55e;
        stroke-width:2.5;
        stroke-dasharray:157;
        stroke-dashoffset:157;
        animation:kbfSuccessRing 1.2s ease forwards;
    }
    #kbf-modal-create .kbf-success-check{
        stroke:#16a34a;
        stroke-width:3;
        stroke-linecap:round;
        stroke-linejoin:round;
        stroke-dasharray:48;
        stroke-dashoffset:48;
        animation:kbfSuccessCheck .6s ease .6s forwards;
    }
    #kbf-modal-create.is-success .kbf-modal-body form,
    #kbf-modal-create.is-success .kbf-modal-footer{display:none;}
    #kbf-modal-create.is-success .kbf-create-success{display:flex;}
    @keyframes kbfSuccessRing{to{stroke-dashoffset:0;}}
    @keyframes kbfSuccessCheck{to{stroke-dashoffset:0;}}
        /* ===== AUDIT SAFE OVERRIDES ===== */
    /* Keep table usable on smaller viewports without changing desktop layout math. */
    @media (max-width: 900px){
        .kbf-table{ min-width:760px; }
    }
    @media (max-width: 640px){
        .kbf-table{ min-width:640px; }
    }

    /* Single, explicit modal scroll policy to avoid selector collision across sections. */
    .kbf-user-ui #kbf-modal-create .kbf-modal-body,
    .kbf-user-ui #kbf-modal-edit .kbf-modal-body{
        overflow-x:hidden;
    }
    /* Canonical modal scroll behavior: keep final computed values stable. */
    .kbf-user-ui #kbf-modal-create .kbf-modal-body,
    .kbf-user-ui #kbf-modal-edit .kbf-modal-body{
        overflow-y:hidden;
    }
    /* Create modal uses .kbf-create-panel (not .kbf-step-content), so body must remain scrollable. */
    .kbf-user-ui #kbf-modal-create .kbf-modal-body{
        overflow-y:auto !important;
        -webkit-overflow-scrolling:touch;
    }
    /* Edit modal step panels now flow to body height, so the body must scroll as well. */
    .kbf-user-ui #kbf-modal-edit .kbf-modal-body{
        overflow-y:auto !important;
        -webkit-overflow-scrolling:touch;
    }
    /* Enforce scrollbar skin on all user-side modals (final cascade). */
    .kbf-user-ui .kbf-modal,
    .kbf-user-ui .kbf-modal-body,
    .kbf-user-ui .kbf-table-wrap,
    .kbf-user-ui .kbf-notif-list{
        scrollbar-width:thin !important;
        scrollbar-color:#2070e0 #f8fafc !important;
    }
    .kbf-user-ui .kbf-modal::-webkit-scrollbar,
    .kbf-user-ui .kbf-modal-body::-webkit-scrollbar,
    .kbf-user-ui .kbf-table-wrap::-webkit-scrollbar,
    .kbf-user-ui .kbf-notif-list::-webkit-scrollbar{
        width:10px !important;
        height:10px !important;
    }
    .kbf-user-ui .kbf-modal::-webkit-scrollbar-button,
    .kbf-user-ui .kbf-modal-body::-webkit-scrollbar-button,
    .kbf-user-ui .kbf-table-wrap::-webkit-scrollbar-button,
    .kbf-user-ui .kbf-notif-list::-webkit-scrollbar-button{
        width:0;
        height:0;
        display:none;
    }
    .kbf-user-ui .kbf-modal::-webkit-scrollbar-track,
    .kbf-user-ui .kbf-modal-body::-webkit-scrollbar-track,
    .kbf-user-ui .kbf-table-wrap::-webkit-scrollbar-track,
    .kbf-user-ui .kbf-notif-list::-webkit-scrollbar-track{
        background:#f8fafc !important;
        border-radius:999px !important;
    }
    .kbf-user-ui .kbf-modal::-webkit-scrollbar-thumb,
    .kbf-user-ui .kbf-modal-body::-webkit-scrollbar-thumb,
    .kbf-user-ui .kbf-table-wrap::-webkit-scrollbar-thumb,
    .kbf-user-ui .kbf-notif-list::-webkit-scrollbar-thumb{
        background:#2070e0 !important;
        border-radius:999px !important;
        border:2px solid #f8fafc !important;
    }
    .kbf-user-ui .kbf-modal::-webkit-scrollbar-thumb:hover,
    .kbf-user-ui .kbf-modal-body::-webkit-scrollbar-thumb:hover,
    .kbf-user-ui .kbf-table-wrap::-webkit-scrollbar-thumb:hover,
    .kbf-user-ui .kbf-notif-list::-webkit-scrollbar-thumb:hover{
        background:#2070e0 !important;
    }
    /* Strong parent override: create modal container scrollbar must beat generic styles. */
    .kbf-user-ui #kbf-modal-create.kbf-modal-overlay .kbf-modal::-webkit-scrollbar{
        width:10px !important;
        height:10px !important;
    }
    .kbf-user-ui #kbf-modal-create.kbf-modal-overlay .kbf-modal::-webkit-scrollbar-track{
        background:#f8fafc !important;
        border-radius:999px !important;
    }
    .kbf-user-ui #kbf-modal-create.kbf-modal-overlay .kbf-modal::-webkit-scrollbar-thumb{
        background:#2070e0 !important;
        border-radius:999px !important;
        border:2px solid #f8fafc !important;
    }
    .kbf-user-ui #kbf-modal-create.kbf-modal-overlay .kbf-modal{
        scrollbar-width:thin !important;
        scrollbar-color:#2070e0 #f8fafc !important;
    }
    @media (max-width: 900px){
        .kbf-user-ui #kbf-modal-create .kbf-modal-body,
        .kbf-user-ui #kbf-modal-edit .kbf-modal-body{
            overflow-y:auto !important;
            max-height:calc(100vh - 180px);
        }
    }
    /* ===== MOBILE MODAL UX POLISH ===== */
    @media (max-width: 900px){
        .kbf-user-ui .kbf-modal{
            max-height:calc(100dvh - 16px);
        }
        .kbf-user-ui .kbf-modal-header{
            padding:14px 16px;
        }
        .kbf-user-ui .kbf-modal-body{
            padding:16px 16px 14px;
            max-height:calc(100dvh - 170px);
        }
        .kbf-user-ui .kbf-modal-footer{
            position:sticky;
            bottom:0;
            z-index:3;
            padding:12px 16px calc(12px + env(safe-area-inset-bottom, 0px));
            border-top:1px solid var(--kbf-border);
            background:linear-gradient(180deg, #ffffffed 0%, #ffffff 34%);
        }
        .kbf-user-ui .kbf-modal-close,
        .kbf-user-ui .kbf-photo-editor-icon-btn{
            width:44px;
            height:44px;
            min-width:44px;
            min-height:44px;
        }
        .kbf-user-ui .kbf-form-group{
            margin-bottom:14px;
        }
        .kbf-user-ui .kbf-form-group label{
            display:block;
            margin-bottom:6px;
        }
        .kbf-user-ui .kbf-form-group small{
            display:block;
            margin-top:6px;
            line-height:1.35;
        }
        .kbf-user-ui .kbf-field-error{
            margin-top:3px;
            font-size:12px;
            font-weight:600;
            line-height:1.35;
        }
    }
    @media (max-width: 640px){
        .kbf-user-ui .kbf-modal{
            width:calc(100vw - 12px);
            border-radius:14px;
        }
        .kbf-user-ui .kbf-modal-body{
            padding:14px 14px 12px;
        }
        .kbf-user-ui .kbf-modal-footer{
            padding:10px 14px calc(10px + env(safe-area-inset-bottom, 0px));
        }
        .kbf-user-ui .kbf-modal-footer .kbf-btn{
            min-height:44px;
        }
    }
    </style>









