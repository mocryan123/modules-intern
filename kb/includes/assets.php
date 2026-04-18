<?php
if (!defined('ABSPATH')) exit;

// GLOBAL CSS + JS (shared across all shortcodes)
// ============================================================

function kbf_global_assets() {
    static $printed = false;
    if ($printed) return;
    $printed = true;
    ?>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
    @import url('https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css');
    @import url('https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css');
    .ph{font-family:'Phosphor' !important;font-style:normal;font-weight:400;line-height:1;}
    .ph-bold{font-weight:700;}
    .ph-fill{font-weight:400;}
    /* === KBF DESIGN SYSTEM -- Landing-aligned (Blue/White) === */
    :root {
        --kbf-navy:       #0f1115;
        --kbf-navy-mid:   #1a2333;
        --kbf-navy-light: #22324a;
        --kbf-accent:     #6fb6ff;
        --kbf-accent-lt:  #e7f1ff;
        --kbf-green:      #6fb6ff;
        --kbf-green-lt:   #e7f1ff;
        --kbf-red:        #ef4444;
        --kbf-red-lt:     #fee2e2;
        --kbf-blue:       #3b82f6;
        --kbf-blue-lt:    #dbeafe;
        --kbf-slate:      #6f7785;
        --kbf-slate-lt:   #f3f5f8;
        --kbf-border:     #edf0f4;
        --kbf-text:       #0f1115;
        --kbf-text-sm:    #6f7785;
        --kbf-bg:         #ffffff;
        --kbf-surface:    #ffffff;
        --kbf-radius:     12px;
        --kbf-radius-sm:  8px;
        --kbf-radius-md:  12px;
        --kbf-radius-lg:  14px;
        --kbf-shadow:     rgba(0, 0, 0, 0.05) 0px 2px 4px -1px, rgba(0, 0, 0, 0.04) 0px 1px 2px -1px;
        --kbf-shadow-lg:  rgba(0, 0, 0, 0.06) 0px 6px 12px -3px, rgba(0, 0, 0, 0.05) 0px 3px 6px -2px;
        --kbf-brand-logo: url('<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logobanner.png'); ?>');
    }
        /* Typography scale (admin) */
    :root{
        --kbf-type-h1: 64px;
        --kbf-type-h2: 32px;
        --kbf-type-h3: 24px;
        --kbf-type-h4: 18px;
        --kbf-type-body: 16px;
        --kbf-type-lead: 18px;
        --kbf-type-meta: 12.5px;
    }
    .kbf-admin-wrap h1{font-size:var(--kbf-type-h1);line-height:1.05;font-weight:600;letter-spacing:-1.5px;color:#0d1a2e;}
    .kbf-admin-wrap h2{font-size:var(--kbf-type-h2);line-height:1.2;font-weight:500;letter-spacing:-0.5px;color:#0f172a;}
    .kbf-admin-wrap h3{font-size:var(--kbf-type-h3);line-height:1.3;font-weight:500;letter-spacing:-0.2px;color:#0f172a;}
    .kbf-admin-wrap h4{font-size:var(--kbf-type-h4);line-height:1.35;font-weight:500;color:#0f172a;}
    .kbf-admin-wrap p,
    .kbf-admin-wrap li{font-size:var(--kbf-type-body);line-height:1.65;font-weight:400;color:#334155;}
    .kbf-admin-wrap .kbf-lead{font-size:var(--kbf-type-lead);line-height:1.7;}
    .kbf-admin-wrap small,
    .kbf-admin-wrap .kbf-meta{font-size:var(--kbf-type-meta);line-height:1.5;font-weight:500;color:#64748b;}
    @media (max-width:720px){
        :root{
            --kbf-type-h1: 40px;
            --kbf-type-h2: 26px;
            --kbf-type-h3: 20px;
            --kbf-type-h4: 16px;
            --kbf-type-body: 15px;
            --kbf-type-lead: 16px;
            --kbf-type-meta: 12px;
        }
    }.kbf-wrap { font-family: 'Poppins', system-ui, -apple-system, sans-serif; color: var(--kbf-text); background: var(--kbf-bg); padding: 18px; }    
    /* Fundora preloader (module-wide) */
    #kbf-loading-overlay{
        position:fixed;
        inset:0;
        z-index:99999;
        display:flex;
        align-items:center;
        justify-content:center;
        background:#f8fafcbf;
        width:100vw;
        height:100vh;
        
    }
    #kbf-loading-overlay .kbf-loading-mark{
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
    #kbf-loading-overlay .kbf-loading-mark img{
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
    /* Hide legacy BNTM chrome on KBF pages */
    .bntm-topbar,
    .bntm-header{
        display:none !important;
    }
    /* Override: Hide BNTM sidebar on KBF pages */
    .bntm-sidebar,
    aside#bntmSidebar,
    .bntm-layout > .bntm-sidebar,
    body .bntm-sidebar,
    .kbf-wrap ~ .bntm-sidebar,
    .kbf-wrap .bntm-sidebar,
    .kbf-admin-wrap ~ .bntm-sidebar,
    .kbf-admin-wrap .bntm-sidebar{
        display:none !important;
        width:0 !important;
        min-width:0 !important;
        max-width:0 !important;
        visibility:hidden !important;
        opacity:0 !important;
        position:absolute !important;
        left:-9999px !important;
        transform:none !important;
        overflow:hidden !important;
        pointer-events:none !important;
    }
    @media (min-width: 769px){
        .bntm-sidebar,
        aside#bntmSidebar,
        .bntm-layout > .bntm-sidebar{
            display:none !important;
            transform:none !important;
            opacity:0 !important;
            visibility:hidden !important;
        }
    }
    /* KBF-only: force content full width and no padding */
    .bntm-content{
        width:100% !important;
        max-width:none !important;
        margin:0 !important;
        padding:0 !important;
        box-shadow:none !important;
        background: transparent !important;
        border-radius:0 !important;
        border:0 !important;
        position:relative !important;
        top:0 !important;
        transform:none !important;
    }
    .bntm-container{
        width:100% !important;
        max-width:none !important;
        margin:0 !important;
        padding:0 !important;
        min-height:100vh !important;
        position:relative !important;
        top:0 !important;
        transform:none !important;
        display:block !important;
    }
    /* Hide legacy plugin preloader on KBF pages */
    .kbf-wrap #bntmLoadingOverlay,
    .kbf-user-ui #bntmLoadingOverlay,
    .kbf-admin-ui #bntmLoadingOverlay,
    .kbf-wrap .bntm-loading-overlay,
    .kbf-user-ui .bntm-loading-overlay,
    .kbf-admin-ui .bntm-loading-overlay{
        display:none !important;
        opacity:0 !important;
        visibility:hidden !important;
        pointer-events:none !important;
    }
    /* Override: Kill default plugin preloader globally (before KBF loads) */
    #bntmLoadingOverlay,
    .bntm-loading-overlay,
    body > #bntmLoadingOverlay,
    #bntmLoadingOverlay *,
    .bntm-loading-overlay *{
        display:none !important;
        opacity:0 !important;
        visibility:hidden !important;
        width:0 !important;
        height:0 !important;
        position:absolute !important;
        left:-9999px !important;
        pointer-events:none !important;
    }
    /* Kill any top offset/white strip */
    .bntm-topbar{
        height:0 !important;
        min-height:0 !important;
        max-height:0 !important;
        margin:0 !important;
        padding:0 !important;
        border:0 !important;
        line-height:0 !important;
        overflow:hidden !important;
        visibility:hidden !important;
    }
    html, body{
        margin:0 !important;
        padding:0 !important;
        top:0 !important;
        background: transparent;
    }
    body.admin-bar{
        margin-top:0 !important;
        padding-top:0 !important;
    }
    #wpadminbar{
        display:none !important;
    }
    body.kbf-fullwidth-page{
        background: var(--kbf-bg) !important;
    }
    body.kbf-fullwidth-page #page,
    body.kbf-fullwidth-page .site,
    body.kbf-fullwidth-page .site-content,
    body.kbf-fullwidth-page .content-area,
    body.kbf-fullwidth-page .entry-content,
    body.kbf-fullwidth-page .ast-container,
    body.kbf-fullwidth-page .ast-container-fluid{
        margin-top:0 !important;
        padding-top:0 !important;
    }
    /* Internal KBF pages: kill theme top spacing and header */
    body.kbf-module-page #page,
    body.kbf-module-page .site,
    body.kbf-module-page .site-content,
    body.kbf-module-page .content-area,
    body.kbf-module-page .entry-content,
    body.kbf-module-page .ast-container,
    body.kbf-module-page .ast-container-fluid,
    body.kbf-module-page .wp-site-blocks{
        margin-top:0 !important;
        padding-top:0 !important;
        border-top:0 !important;
    }
    body.kbf-module-page header#masthead,
    body.kbf-module-page .site-header,
    body.kbf-module-page .ast-hfb-header,
    body.kbf-module-page .ast-header-break-point .main-header-bar,
    body.kbf-module-page #ast-desktop-header,
    body.kbf-module-page #ast-mobile-header{
        display:none !important;
        height:0 !important;
        min-height:0 !important;
        margin:0 !important;
        padding:0 !important;
        border:0 !important;
        overflow:hidden !important;
        visibility:hidden !important;
    }
    body.kbf-fullwidth-page header#masthead,
    body.kbf-fullwidth-page .site-header,
    body.kbf-fullwidth-page .ast-hfb-header,
    body.kbf-fullwidth-page .ast-header-break-point .main-header-bar,
    body.kbf-fullwidth-page #ast-desktop-header,
    body.kbf-fullwidth-page #ast-mobile-header{
        display:none !important;
        height:0 !important;
        min-height:0 !important;
        margin:0 !important;
        padding:0 !important;
        border:0 !important;
        overflow:hidden !important;
        visibility:hidden !important;
    }
    body.kbf-fullwidth-page,
    body.kbf-fullwidth-page #page,
    body.kbf-fullwidth-page .site,
    body.kbf-fullwidth-page .site-content,
    body.kbf-fullwidth-page .content-area,
    body.kbf-fullwidth-page .entry-content,
    body.kbf-fullwidth-page .wp-site-blocks,
    body.kbf-fullwidth-page .wp-site-blocks > *{
        margin-top:0 !important;
        padding-top:0 !important;
        border-top:0 !important;
    }
    .bntm-content,
    .bntm-container,
    .bntm-bg,
    .kbf-wrap,
    .kbf-user-ui,
    .kbf-admin-ui{
        margin-top:0 !important;
        padding-top:0 !important;
        top:0 !important;
    }
    .bntm-bg{
        position:relative;
        width:100%;
        min-height:100vh;
        height:auto;
        overflow:visible;
        margin:0 !important;
        padding:0 !important;
        top:0 !important;
        transform:none !important;
        display:block !important;
    }
    html.kbf-modal-lock, body.kbf-modal-lock{
        overflow:hidden !important;
    }
    .kbf-wrap h1{font-size:28px;font-weight:700;letter-spacing:-0.4px;color:#0d1a2e;margin:0 0 6px;line-height:1.2;}
    .kbf-wrap h2{font-size:22px;font-weight:600;color:#0f172a;margin:0 0 6px;line-height:1.3;}
    .kbf-wrap h3{font-size:16px;font-weight: 500;color:#0f172a;margin:0 0 6px;line-height:1.35;}
    .kbf-wrap h4{font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;line-height:1.4;}
    .kbf-wrap p{font-size:13.5px;font-weight:400;color:#4f5a6b;line-height:1.65;margin:0;}
    .kbf-wrap small,
    .kbf-wrap .kbf-meta,
    .kbf-wrap .kbf-text-sm{font-size:12.5px;font-weight:400;color:#4f5a6b;line-height:1.5;}
    .kbf-meta-row{display:flex;flex-wrap:wrap;gap:8px;align-items:center;}
    .kbf-meta-row + .kbf-meta-row{margin-top:4px;}
    .kbf-meta-divider{width:4px;height:4px;border-radius:999px;background:#cbd5e1;display:inline-block;}
    .kbf-meta-strong{color:#0f172a;font-weight:600;}
    .kbf-strong{font-weight:600;}
    .kbf-meta-item{display:inline-flex;align-items:center;gap:6px;}
    .kbf-meta-item img,
    .kbf-muted-icon{
        width:12px;
        height:12px;
        filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);
        opacity:.9;
    }
    .kbf-icon{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        line-height:1;
        font-size:inherit;
        vertical-align:middle;
    }
    .kbf-wrap label{font-size:12.5px;font-weight:600;color:#4f5a6b;}
    .kbf-wrap .kbf-table thead th{
        font-size:10.5px;
        font-weight:600;
        text-transform:uppercase;
        letter-spacing:.6px;
        color:#94a3b8;
    }
    .kbf-wrap .kbf-table tbody td{font-size:12.5px;color:#0f172a;}
    .kbf-gradient-num{
        background: linear-gradient(180deg,#63b3ff 0%,#1d4ed8 100%);
        -webkit-background-clip:text;
        background-clip:text;
        -webkit-text-fill-color:transparent;
        color:transparent;
        font-weight:800 !important;
    }
    .kbf-empty{
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:10px;
        padding:40px 24px;
        border:1px solid var(--kbf-border);
        border-radius:16px;
        background:#fff;
        color:var(--kbf-slate);
        text-align:center;
    }
    .kbf-empty svg{
        width:42px;
        height:42px;
        padding:10px;
        border-radius:14px;
        background:#f1f5f9;
        color:#94a3b8;
        stroke:none;
        fill:currentColor;
    }
    .kbf-table-empty{
        border:1px solid var(--kbf-border);
        border-radius:16px;
        background:#fff;
        overflow:hidden;
        box-shadow:var(--kbf-shadow);
    }
    .kbf-table-empty-head{
        display:grid;
        gap:8px;
        padding:14px 18px;
        border-bottom:1px solid var(--kbf-border);
        background:#fbfdff;
        text-transform:uppercase;
        letter-spacing:.6px;
        font-size:10.5px;
        font-weight:600;
        color:#94a3b8;
    }
    .kbf-table-empty-body{
        padding:34px 18px;
        text-align:center;
        color:var(--kbf-slate);
        font-size:13px;
    }
    .kbf-table-tools{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:nowrap;
        margin:8px 0 12px;
    }
    .kbf-table-tools-left{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:nowrap;
        min-width:0;
        flex:1;
    }
    .kbf-table-tools .kbf-form-group{
        margin:0;
        min-width:auto;
    }
    .kbf-table-date-range{
        min-width:auto;
    }
    .kbf-table-tools .kbf-form-group input,
    .kbf-table-tools .kbf-form-group select{
        padding:8px 12px;
        font-size:12.5px;
    }
    .kbf-table-search{width:190px;}
    .kbf-table-status{width:150px;}
    .kbf-table-range{width:150px;}
    .kbf-table-refresh{
        padding:6px 12px;
        min-width:34px;
        justify-content:center;
    }
    .kbf-table-tools .kbf-btn.kbf-table-apply,
    .kbf-table-tools .kbf-btn.kbf-table-clear{
        padding:6px 16px !important;
        height:auto !important;
    }
    .kbf-table-refresh .kbf-refresh-icon{
        width:14px;
        height:14px;
        display:block;
        filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);
    }
    .kbf-table-tools-right{
        margin-left:auto;
        flex-wrap:nowrap;
    }
    .kbf-table-refresh.is-loading .kbf-refresh-icon{
        animation:kbfRotate .8s linear infinite;
    }
    @keyframes kbfRotate{
        from{ transform:rotate(0deg); }
        to{ transform:rotate(360deg); }
    }
    @media (max-width: 980px){
        .kbf-table-tools{
            flex-wrap:wrap;
            align-items:flex-start;
        }
        .kbf-table-tools-left{
            flex-wrap:wrap;
        }
    }
    .kbf-table-tools-right{
        display:flex;
        align-items:center;
        gap:8px;
    }
    .kbf-admin-wrap,
    .kbf-admin-wrap.kbf-wrap{ padding:0 !important; margin:0 !important; background:#f5f7fb; }
    .kbf-admin-wrap .kbf-admin-layout{ margin:0; }
    .kbf-admin-wrap .kbf-dashboard-topbar{
        margin:0;
        border-radius:0;
        background:#f7f9fc;
        border-bottom:1px solid #e6edf5;
        box-shadow:none;
        padding:14px 18px;
        min-height:64px;
    }
    .kbf-admin-layout{
        display:flex;
        min-height:100vh;
        gap:0;
        background:transparent;
    }
    .kbf-admin-sidebar{
        width:248px;
        background: #f7f9fc;
        border-right: 1px solid #e6edf5;
        padding:20px 16px;
        position:sticky;
        top:0;
        height:100vh;
        height:stretch;
        box-shadow:none;
        display:flex;
        flex-direction:column;
        gap:6px;
    }
    .kbf-admin-sidebar-brand{
        display:flex;
        align-items:center;
        gap:10px;
        font-weight:800;
        color:#111827;
        font-size:14.5px;
        margin-bottom:14px;
    }
    .kbf-admin-sidebar-brand img{
        width:22px;height:22px;object-fit:contain;border-radius:6px;
    }
    .kbf-admin-sidebar-label{
        font-size:10px;
        color:#9aa7b7;
        font-weight:500;
        margin:12px 0 8px;
    }
    .kbf-admin-sidebar-note{
        margin-top:auto;
        font-size:11px;
        color:#9aa7b7;
        padding-top:16px;
        border-top:1px dashed #e2e8f0;
    }
    .kbf-admin-main{
        flex:1;
        padding:24px 28px 40px;
        box-sizing:border-box;
    }
    .kbf-admin-shell{
        max-width:100%;
        margin:0 auto;
        padding:0;
        box-sizing:border-box;
    }
    .kbf-eyebrow { font-size: 11.5px; text-transform: uppercase; letter-spacing: .16em; color: var(--kbf-slate); font-weight: 700; }
    /* Tabs */
    .kbf-tabs { display: flex; gap: 18px; background: transparent; border: none; border-radius: 0; padding: 0; overflow-x: auto; margin-bottom: 10px; }
    .kbf-tab { position: relative; display: inline-flex; align-items: center; gap: 7px; padding: 6px 0; color: #64748b; font-size: 12.5px; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all .18s; }
    .kbf-tab svg { opacity: .75; }
    .kbf-tab::after{ content:''; position:absolute; left:0; bottom:-8px; width:0; height:2px; border-radius:999px; background:#4a98ff; transition:width .2s ease; }
    .kbf-tab:hover::after, .kbf-tab.active::after{ width:100%; }
    .kbf-tab:hover { color: #1f2a44; background: transparent; }
    .kbf-tab.active { color: #1f2a44; background: transparent; border: none; }
    .kbf-tab-content { background: transparent; border: none; border-radius: 0; padding: 0; margin-top: 14px; box-shadow: none; }

    /* Cards & Sections */
    .kbf-section { margin-bottom: 30px; }
    .kbf-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
    .kbf-section-title { font-size: 16px; font-weight: 600; color: var(--kbf-text); margin: 0; }
    .kbf-cta-card{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:18px;
        padding:22px 24px;
        border-radius:16px;
        border:1px solid var(--kbf-border);
        background:linear-gradient(135deg,#f7fbff 0%,#ffffff 55%,#f3f7ff 100%);
        box-shadow:var(--kbf-shadow);
        margin:12px 0 18px;
    }
    .kbf-cta-eyebrow{font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#8b97aa;font-weight:700;}
    .kbf-cta-title{font-size:20px;font-weight:700;color:#0f172a;margin:4px 0 6px;line-height:1.25;}
    .kbf-cta-sub{font-size:13.5px;color:#556070;max-width:520px;}
    .kbf-cta-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-start;}
    .kbf-cta-right{
        display:flex;
        flex-direction:column;
        align-items:flex-end;
        gap:8px;
    }
    .kbf-cta-note{
        font-size:12px;
        color:#7a8798;
        margin-top:0;
    }
    .kbf-action-note{
        font-size:11.5px;
        color:#7a8798;
        margin-top:10px;
        margin-bottom:10px;
        text-align:center;
    }
    .kbf-cta-checklist{
        margin-top:12px;
        display:flex;
        flex-direction:column;
        gap:6px;
    }
    .kbf-cta-check{
        display:flex;
        align-items:center;
        gap:8px;
        font-size:12.5px;
        color:#64748b;
    }
    .kbf-cta-check i{
        width:18px;
        height:18px;
        border-radius:50%;
        background:#e7f1ff;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
    }
    .kbf-cta-check i img{
        width:10px;
        height:10px;
        filter:invert(36%) sepia(88%) saturate(2029%) hue-rotate(198deg) brightness(95%) contrast(95%);
    }
    @media(min-width:721px){
        .kbf-cta-checklist{
            flex-direction:row;
            flex-wrap:wrap;
            gap:12px 18px;
        }
        .kbf-cta-check{
            min-width:220px;
        }
    }
    @media(max-width:900px){
        .kbf-cta-card{flex-direction:column;align-items:flex-start;}
        .kbf-cta-right{width:100%;align-items:flex-start;}
        .kbf-cta-actions{width:100%;}
    }
    .kbf-card { background: var(--kbf-surface); border: 1px solid var(--kbf-border); border-radius: 16px; padding: 22px; margin-bottom: 16px; transition: transform .18s, box-shadow .2s; box-shadow: var(--kbf-shadow); overflow: visible; position: relative; z-index: 0; }
    .kbf-card { font-weight: 500; }
    .kbf-card:hover { box-shadow: var(--kbf-shadow-lg); transform: translateY(-3px); }
    .kbf-admin-card{
        border-radius:18px;
        border-color:#edf0f4;
        box-shadow:var(--kbf-shadow);
    }
    .kbf-admin-card:hover{
        box-shadow:var(--kbf-shadow-lg);
        transform:translateY(-3px);
    }
    .kbf-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }

    /* Stats Row */
    .kbf-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .kbf-stat { background: var(--kbf-surface); border: 1px solid var(--kbf-border); border-radius: 12px; padding: 12px 14px; display: flex; align-items: center; gap: 10px; }
    .kbf-stat-icon--plain{background:transparent;}
.kbf-stat-icon-img{
    color:#3b82f6;
}
    .kbf-stat-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .kbf-stat-label { font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--kbf-slate); }
    .kbf-stat-value { font-size: 22px; font-weight: 600; color: var(--kbf-navy); line-height: 1.2; }
    .kbf-stat-sub { font-size: 12px; color: var(--kbf-slate); margin-top: 2px; }

    /* Progress */
    .kbf-progress-wrap { background: #eef2f7; border-radius: 999px; height: 8px; overflow: hidden; }
    .kbf-progress-bar { height: 8px; border-radius: 999px; background: linear-gradient(90deg, #60a5fa, #3b82f6); transitio  n: width .6s ease; }

    /* Badges / Status */
    .kbf-badge{
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:3px 8px;
        border-radius:999px;
        font-size:10px;
        font-weight:700;
        letter-spacing:.25px;
        line-height:1.1;
    }
    .kbf-badge svg, .kbf-badge i, .kbf-badge img { display: none !important; }
    .kbf-badge::before{
        content:'';
        width:12px;
        height:12px;
        background-color:currentColor;
        -webkit-mask-repeat:no-repeat;
        mask-repeat:no-repeat;
        -webkit-mask-position:center;
        mask-position:center;
        -webkit-mask-size:12px 12px;
        mask-size:12px 12px;
        border-radius:50%;
        opacity:.9;
        display:none !important;
    }
    
    .kbf-badge::before{
        display:none; content:'';
        width:12px;
        height:12px;
        display:inline-block;
        background-color:currentColor;
        -webkit-mask-repeat:no-repeat;
        mask-repeat:no-repeat;
        -webkit-mask-position:center;
        mask-position:center;
        -webkit-mask-size:12px 12px;
        mask-size:12px 12px;
        border-radius:50%;
        opacity:.9;
    }
    .kbf-badge-pending   { background:#fef3c7; color:#b45309; }
    .kbf-badge-active    { background:#dcfce7; color:#15803d; }
    .kbf-badge-completed { background:#dbeafe; color:#1d4ed8; }
    .kbf-badge-cancelled { background:#fee2e2; color:#b91c1c; }
    .kbf-badge-suspended { background:#ffe4e6; color:#be123c; }
    .kbf-badge-draft     { background:#f1f5f9; color:#475569; }
    .kbf-badge-holding   { background:#fef3c7; color:#b45309; }
    .kbf-badge-released  { background:#dcfce7; color:#15803d; }
    .kbf-badge-refunded  { background:#dbeafe; color:#1d4ed8; }
    .kbf-badge-open      { background:#fee2e2; color:#b91c1c; }
    .kbf-badge-reviewed  { background:#fef3c7; color:#b45309; }
    .kbf-badge-dismissed { background:#f1f5f9; color:#475569; }
    .kbf-badge-verified  { background:#e7f1ff; color:#1f3b8a; }

    /* Buttons */
    .kbf-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 38px; padding: 0 15px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all .25s ease; text-decoration: none; line-height: 1; background: #fff; }
    .kbf-btn:disabled { opacity: .55; cursor: not-allowed; }
    .kbf-btn:focus-visible{
        outline:none;
        box-shadow:0 0 0 3px rgba(59,130,246,0.18);
    }
    .kbf-btn-primary{
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
    .kbf-btn-primary::before{
        content: '';
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        background: linear-gradient(135deg, #7ec4ff 0%, #5aaaf8 40%, #2878e8 100%);
        opacity: 0;
        z-index: -1;
        transition: opacity .3s ease;
    }
    .kbf-btn-primary:hover:not(:disabled){
        transform: translateY(-2px);
        box-shadow:
            0 1px 3px rgba(32, 112, 224, 0.15),
            0 8px 24px rgba(42, 120, 220, 0.45),
            0 16px 40px rgba(61, 142, 240, 0.20),
            inset 0 1px 0 rgba(255, 255, 255, 0.25);
        filter: brightness(1.06);
    }
    .kbf-btn-primary:hover:not(:disabled)::before{ opacity: 1; }
    .kbf-btn-primary:active{
        transform: translateY(0px);
        box-shadow:
            0 1px 2px rgba(32, 112, 224, 0.20),
            0 4px 14px rgba(42, 120, 220, 0.28),
            inset 0 1px 0 rgba(255, 255, 255, 0.18);
        transition: transform .1s ease, box-shadow .1s ease, filter .1s ease;
    }
    .kbf-btn-accent    { background: #ecfdf3; color: #166534; border-color: #bbf7d0; }
    .kbf-btn-accent:hover:not(:disabled) { background: #dcfce7; border-color: #86efac; }
    .kbf-btn-warning   { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
    .kbf-btn-warning:hover:not(:disabled) { background: #fde68a; border-color: #fbbf24; }
    .kbf-btn-secondary { background: #f8fafc; color: #334155; border-color: #e5e7eb; }
    .kbf-btn-secondary:hover:not(:disabled) { background: #f1f5f9; border-color: #cbd5e1; }
    .kbf-btn-secondary .kbf-icon,
    .kbf-btn-secondary i,
    .kbf-btn-secondary svg{
        color: currentColor !important;
        fill: currentColor;
        stroke: currentColor;
        filter: none !important;
    }
    .kbf-btn-danger    { background: #fff1f2; color: #9f1239; border-color: #fecdd3; }
    .kbf-btn-danger:hover:not(:disabled) { background: #ffe4e6; border-color: #fda4af; }
    .kbf-btn-success   { background: #ecfdf3; color: #166534; border-color: #bbf7d0; }
    .kbf-btn-success:hover:not(:disabled) { background: #dcfce7; border-color: #86efac; }
    .kbf-btn-sm { height: 32px; padding: 0 10px; font-size: 12px; }
    .kbf-btn-group { display: flex; gap: 8px; flex-wrap: wrap; }
    .kbf-verify-stack{
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:6px;
    }
    .kbf-verify-stack .kbf-btn-sm{
        min-width:76px !important;
        justify-content:center;
    }
    .kbf-table-accounts{
        table-layout:auto;
    }
    .kbf-table-accounts th,
    .kbf-table-accounts td{
        padding:12px 12px;
    }
    .kbf-table-accounts th:nth-child(6),
    .kbf-table-accounts td:nth-child(6),
    .kbf-table-accounts th:nth-child(7),
    .kbf-table-accounts td:nth-child(7),
    .kbf-table-accounts th:nth-child(8),
    .kbf-table-accounts td:nth-child(8),
    .kbf-table-accounts th:nth-child(9),
    .kbf-table-accounts td:nth-child(9){
        width:140px;
        text-align:center;
        white-space:nowrap;
    }
    [data-tooltip]{ position:relative; }
    [data-tooltip]::after{
        content: attr(data-tooltip);
        position:absolute;
        left:50%;
        bottom: calc(100% + 8px);
        transform: translateX(-50%) translateY(4px);
        background:#0f172a;
        color:#ffffff;
        font-size:11px;
        font-weight:600;
        padding:5px 8px;
        border-radius:6px;
        white-space:nowrap;
        opacity:0;
        pointer-events:none;
        transition:opacity .18s ease, transform .18s ease;
        box-shadow:0 6px 14px rgba(15,23,42,.18);
        z-index:20;
    }
    [data-tooltip]::before{
        content:'';
        position:absolute;
        left:50%;
        bottom: calc(100% + 2px);
        transform: translateX(-50%) translateY(4px);
        width:8px;
        height:8px;
        background:#0f172a;
        clip-path: polygon(50% 100%, 0 0, 100% 0);
        opacity:0;
        transition:opacity .18s ease, transform .18s ease;
        z-index:19;
    }
    [data-tooltip]:hover::after,
    [data-tooltip]:hover::before{
        opacity:1;
        transform: translateX(-50%) translateY(0);
    }
    .kbf-table .kbf-btn-group .kbf-btn{
        min-width:90px;
        justify-content:center;
        text-align:center;
    }
    .kbf-action-bar { display: flex; gap: 10px; flex-wrap: wrap; margin: 10px 0 16px; }
    .kbf-action-card { background: var(--kbf-surface); border: 1px solid var(--kbf-border); border-radius: 14px; padding: 14px 16px; display: flex; align-items: center; gap: 10px; box-shadow: none; }
    .kbf-action-card .kbf-strong { font-size: 14px; }
    .kbf-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: var(--kbf-slate-lt); color: var(--kbf-text-sm); }\n    .kbf-chip-row{display:flex;flex-wrap:wrap;gap:8px;align-items:center;}\n    .kbf-trust-row{display:flex;flex-wrap:wrap;gap:10px;align-items:center;font-size:10.5px;color:var(--kbf-slate);margin-top:6px;line-height:1.2;}\n    .kbf-trust-item{display:inline-flex;align-items:center;gap:5px;background:transparent;border:none;border-radius:0;padding:0;font-weight:600;}\n    .kbf-trust-item img{width:10px;height:10px;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);}
    .kbf-section-sub { font-size: 13px; color: var(--kbf-text-sm); margin-top: 6px; }
    /* Topbar nav (user/admin) */
    .kbf-dashboard-topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:18px;
        padding:10px 16px;
        min-height:56px;
        position:relative;
        z-index:1000;
        background: #ffffff;
        
        -webkit-
        border-bottom:1px solid var(--kbf-border);
        border-radius:0;
        box-shadow: none;
        margin-bottom:10px;
        flex-wrap:wrap;
    }
    .kbf-admin-wrap .kbf-dashboard-topbar{ display:flex; }
    .kbf-admin-wrap .kbf-dashboard-topbar.kbf-admin-topbar{ position:sticky; top:0; }
    .kbf-dashboard-topbar.kbf-topbar-scrolled{
        border-bottom-color:#e2e8f0;
        box-shadow:0 4px 24px rgba(15,40,80,0.06);
    }
    .kbf-dashboard-brand{
        display:flex;
        align-items:center;
        gap:10px;
        font-weight:800;
        color:#0f172a;
        font-size:15px;
    }
    .kbf-brand-text{color:#3d8ef0;font-family:'Poppins', system-ui, -apple-system, sans-serif;}
    .kbf-dashboard-brand .kbf-logo-dot{
        width:26px;height:26px;border-radius:10px;
        background:linear-gradient(135deg,#79c0ff 0%,#4a98ff 100%);
        display:inline-flex;align-items:center;justify-content:center;
        color:#fff;font-weight:800;font-size:12px;
        box-shadow: none;
    }
    .kbf-dashboard-nav{
        display:flex;
        gap:20px;
        font-size:12.5px;
        color:var(--kbf-muted);
        align-items:center;
        flex-wrap:wrap;
    }
    .kbf-admin-wrap .kbf-admin-nav{
        flex-direction:column;
        align-items:stretch;
        gap:6px;
        width:100%;
    }
    .kbf-admin-wrap .kbf-admin-nav a{
        padding:10px 12px;
        border-radius:12px;
        background:transparent;
        border:1px solid transparent;
        color:#5b6b7d;
        font-weight:600;
        display:flex;
        align-items:center;
        gap:10px;
        position:relative;
    }
    .kbf-admin-wrap .kbf-admin-nav a::after{ display:none; }
    .kbf-admin-wrap .kbf-admin-nav a:hover{
        background:#edf4ff;
        border-color:#dce7fb;
        color:#1f2a44;
    }
    .kbf-admin-wrap .kbf-admin-nav a.active{
        background:#e6f0ff;
        border-color:#cfe1ff;
        color:#1f2a44;
        box-shadow:none;
    }
    .kbf-admin-wrap .kbf-admin-nav a.active::before{
        content:'';
        width:6px;
        height:6px;
        border-radius:999px;
        background:#3b82f6;
        display:inline-block;
    }
    .kbf-admin-wrap .kbf-admin-nav a .kbf-nav-count{
        margin-left:auto;
    }
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
        background:rgba(0,0,0,0.45);
        z-index:998;
        animation:kbfOverlayIn .2s ease forwards;
    }
    .kbf-mobile-overlay.kbf-overlay-open{display:block;}
    @keyframes kbfOverlayIn{from{opacity:0}to{opacity:1}}
    .kbf-mobile-menu{
        position:fixed;
        top:56px;
        left:0;
        right:0;
        z-index:999;
        border-radius:0;
        margin-top:0;
        transform:translateY(-115%);
        transition:transform .3s cubic-bezier(.4,0,.2,1);
        display:flex;
        flex-direction:column;
        background:#fff;
        border-bottom:1px solid var(--kbf-border);
        box-shadow:0 12px 30px rgba(15,23,42,0.12);
    }
    .kbf-mobile-menu.kbf-menu-open{transform:translateY(0);display:flex;}
    .kbf-mobile-menu-header{
        display:flex;align-items:center;justify-content:space-between;
        padding:10px 16px;border-bottom:1px solid var(--kbf-border);
        background:#fff;
    }
    .kbf-mobile-menu a{
        padding:13px 18px;
        font-size:13.5px;
        color:#0f172a;
        border-bottom:1px solid var(--kbf-border);
        text-decoration:none;
    }
    .kbf-mobile-menu a.active{color:#1f2a44;font-weight:600;}
    .kbf-admin-wrap .kbf-mobile-menu{
        top:56px;
        height:calc(100vh - 56px);
        max-height:calc(100vh - 56px);
        overflow:auto;
        margin-top:0;
    }
    .kbf-admin-wrap .kbf-mobile-overlay{
        top:56px;
    }
    .kbf-admin-wrap .kbf-mobile-menu-header{
        display:flex;
    }
    .kbf-nav-toggle{
        display:none;
        width:36px;
        height:36px;
        border-radius:10px;
        border:1px solid #e2e8f0;
        background:#fff;
        align-items:center;
        justify-content:center;
        cursor:pointer;
    }
    .kbf-nav-toggle img{width:16px;height:16px;display:block;}
    .kbf-dashboard-nav a{
        position:relative;
        text-decoration:none;
        color:#64748b;
        font-weight:400;
        display:inline-flex;
        align-items:center;
        gap:6px;
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
    .kbf-nav-count{
        background: linear-gradient(135deg, #ff6b6b 0%, #ef4444 50%, #dc2626 100%);
        color:#fff;
        border-radius:999px;
        padding:0 7px;
        min-width:20px;
        height:20px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size:10.5px;
        font-weight:700;
        line-height:1;
        letter-spacing:.2px;
        border:1px solid rgba(255,255,255,0.6);
        box-shadow:
            0 6px 16px rgba(239,68,68,0.28),
            inset 0 1px 0 rgba(255,255,255,0.35);
    }
    @media (max-width: 900px){
        .kbf-admin-layout{flex-direction:column;}
        .kbf-admin-sidebar{display:none;}
        .kbf-admin-main{padding:18px 16px 32px;}
        .kbf-admin-wrap .kbf-dashboard-topbar{
            display:flex;
            position:fixed !important;
            top:0;
            left:0;
            right:0;
            z-index:1000;
            margin:0;
        }
        .kbf-admin-layout{
            padding-top:56px;
        }
        .kbf-admin-wrap .kbf-dashboard-nav{display:none;}
        .kbf-admin-wrap .kbf-hamburger{display:inline-flex;}
        /* Match user mobile menu behavior */
        .kbf-admin-wrap .kbf-mobile-menu{
            top:0;
            height:100vh;
            max-height:100vh;
            border:1px solid var(--kbf-border);
            box-shadow:0 10px 26px #0f28502e;
        }
        .kbf-admin-wrap .kbf-mobile-overlay{
            top:0;
        }
        .kbf-admin-wrap .kbf-mobile-menu-header{
            justify-content:space-between;
            padding:14px 16px;
        }
        .kbf-admin-wrap .kbf-mobile-menu a{
            text-align:center;
        }
    }
    @media (min-width: 901px){
        .kbf-admin-wrap .kbf-dashboard-topbar{display:none;}
        .kbf-admin-wrap .kbf-mobile-menu,
        .kbf-admin-wrap .kbf-mobile-overlay{display:none !important;}
    }
    @media (max-width: 900px){
        .kbf-dashboard-nav{
            display:none;
            position:absolute;
            top:56px;
            right:12px;
            background:#fff;
            border:1px solid #e2e8f0;
            border-radius:12px;
            padding:10px 12px;
            box-shadow:0 16px 32px rgba(15,23,42,.12);
            flex-direction:column;
            gap:10px;
            z-index:9999;
        }
        .kbf-dashboard-nav.is-open{display:flex;}
        .kbf-nav-toggle{display:inline-flex;}
    }
    /* Forms */
    .kbf-form-row { display: grid; gap: 16px; }
    .kbf-form-row-2 { grid-template-columns: 1fr 1fr; }
    .kbf-form-row-3 { grid-template-columns: 1fr 1fr 1fr; }
    .kbf-form-group { margin-bottom: 16px; }
    .kbf-form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--kbf-text-sm); margin-bottom: 6px; }
    .kbf-form-group input,
    .kbf-form-group select,
    .kbf-form-group textarea { width: 100%; padding: 9px 12px; border: 1.5px solid var(--kbf-border); border-radius: 7px; font-size: 13.5px; color: var(--kbf-text); background: #fff; transition: border-color .15s, box-shadow .15s; font-family: inherit; }
    .kbf-form-group select{
        appearance:none;
        -webkit-appearance:none;
        -moz-appearance:none;
        padding-right:36px;
        margin-top:2px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364758b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat:no-repeat;
        background-position:right 12px center;
        background-size:14px;
    }
    .kbf-form-group input::placeholder,
    .kbf-form-group textarea::placeholder { color: #8aa0b8; font-weight: 400; font-family: inherit; }
    .kbf-form-group input[type="date"] { font-family: inherit; color: var(--kbf-text); }
    .kbf-form-group input:focus,
    .kbf-form-group select:focus,
    .kbf-form-group textarea:focus { outline: none; border-color: var(--kbf-navy-light); box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
    .kbf-form-group small { display: block; color: var(--kbf-slate); font-size: 11.5px; margin-top: 4px; }
    .kbf-checkbox-row{
        position:relative;
        display:block;
        cursor:pointer;
        font-size:13px;
        font-weight:600;
        color:var(--kbf-text-sm);
        line-height:1.45;
        padding-left:28px;
    }
    .kbf-checkbox-row input[type="checkbox"]{
        position:absolute;
        left:0;
        top:2px;
        width:18px;
        height:18px;
        margin:0;
        opacity:0;
        cursor:pointer;
    }
    .kbf-checkbox-row::before{
        content:'';
        position:absolute;
        left:0;
        top:2px;
        width:18px;
        height:18px;
        border:1.5px solid #cbd5e1;
        border-radius:5px;
        background:#fff;
        box-sizing:border-box;
        transition:border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .08s ease;
    }
    .kbf-checkbox-row:has(input[type="checkbox"]:hover)::before{
        border-color:#94a3b8;
    }
    .kbf-checkbox-row:has(input[type="checkbox"]:focus-visible)::before{
        box-shadow:0 0 0 3px rgba(59,130,246,0.18);
    }
    .kbf-checkbox-row:has(input[type="checkbox"]:checked)::before{
        background:var(--kbf-blue);
        border-color:var(--kbf-blue);
    }
    .kbf-checkbox-row:has(input[type="checkbox"]:checked)::after{
        content:'';
        position:absolute;
        left:3px;
        top:5px;
        width:12px;
        height:12px;
        background:#fff;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='white' d='M6.173 12.727 2.4 8.955l1.273-1.273 2.5 2.5 5.954-5.954 1.273 1.273z'/%3E%3C/svg%3E") no-repeat center / contain;
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='white' d='M6.173 12.727 2.4 8.955l1.273-1.273 2.5 2.5 5.954-5.954 1.273 1.273z'/%3E%3C/svg%3E") no-repeat center / contain;
    }

    /* Modals */
    .kbf-modal-overlay { position: fixed; inset: 0; background: rgba(10,16,32,.58); display: flex; align-items: center; justify-content: center; z-index: 99999;  opacity:0; visibility:hidden; pointer-events:none; transition:opacity .22s ease, visibility .22s ease; }
    .kbf-modal-overlay.is-open{ opacity:1; visibility:visible; pointer-events:auto; }
    .kbf-modal {
        position: relative;
        background: #fff;
        border-radius: 18px;
        width: 94%;
        max-width: 660px;
        max-height: 92vh;
        overflow-y: auto;
        border: 1px solid #e6edf7;
        box-shadow: 0 24px 60px rgba(15,23,42,.18), 0 8px 20px rgba(15,23,42,.12);
        display: flex;
        flex-direction: column;
        transform: translateY(8px) scale(0.98);
        transition: transform .22s ease;
    }
    .kbf-modal-overlay.is-open .kbf-modal{ transform: translateY(0) scale(1); }
    .kbf-modal-sm { max-width: 460px; }
    .kbf-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px 16px; border-bottom: 1px solid #e7eef8; background: #ffffff; border-radius: 18px 18px 0 0; }
    .kbf-modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; font-family: inherit; }
    .kbf-modal-close { background: #f1f5ff; border: 1px solid #dbe7ff; color: #2a5bd7; width: 30px; height: 30px; border-radius: 50%; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; transition: background .15s, transform .15s, box-shadow .15s; }
    .kbf-modal-close:hover { background: #e7efff; box-shadow: 0 4px 10px rgba(42,91,215,.18); transform: translateY(-1px); }
    .kbf-modal-body { padding: 24px; flex: 1; }
    .kbf-modal-footer { padding: 16px 24px; border-top: 1px solid #e7eef8; background: #ffffff; border-radius: 0 0 18px 18px; display: flex; justify-content: flex-end; gap: 10px; }

    /* Tables */
    .kbf-table-wrap { overflow-x: auto; border-radius: 14px; border: 1px solid #e9eef6; background: #fff; position: relative; }
    .kbf-admin-wrap .kbf-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .kbf-admin-wrap .kbf-table { min-width: 860px; }
    .kbf-table-wrap.is-loading::before{
        content:'';
        position:absolute;
        inset:0;
        background:rgba(255,255,255,0.72);
        z-index:2;
    }
    .kbf-table-wrap.is-loading::after{
        content:'';
        position:absolute;
        width:30px;
        height:30px;
        border-radius:50%;
        border:3px solid #dbeafe;
        border-top-color:#3b82f6;
        left:50%;
        top:50%;
        transform:translate(-50%,-50%);
        animation:kbfSpin .8s linear infinite;
        z-index:3;
    }
    @keyframes kbfSpin{
        from{ transform:translate(-50%,-50%) rotate(0deg); }
        to{ transform:translate(-50%,-50%) rotate(360deg); }
    }
    .kbf-table { width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed; }
    .kbf-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #eef2f7;
    }
    .kbf-table tbody td { padding: 14px 16px; border-bottom: 1px solid #eef2f7; vertical-align: middle; }
    .kbf-table tbody td:first-child { max-width: 240px; }
    .kbf-table tbody td:first-child {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .kbf-table tbody td:first-child .kbf-strong {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
    }
    .kbf-table tbody tr:last-child td { border-bottom: none; }
    .kbf-table tbody tr:hover td { background: #f9fafb; }
    .kbf-table td { vertical-align: middle; }
    .kbf-clamp-2{
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        overflow:hidden;
    }
    .kbf-table .kbf-btn{
        padding:6px 10px;
        font-size:11.5px;
        border-radius:10px;
    }
    .kbf-table .kbf-btn.kbf-btn-sm{
        padding:5px 9px;
        font-size:11px;
        border-radius:9px;
    }
    .kbf-table-progress {
        width: 120px;
        height: 6px;
        background: #eef2f7;
        border-radius: 999px;
        overflow: hidden;
    }
    .kbf-table-progress span {
        display: block;
        height: 100%;
        background: #22c55e;
        border-radius: 999px;
    }
    .kbf-table-pager{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:10px 2px 0;
        border:none;
        border-radius:0;
        background:transparent;
        box-shadow:none;
        font-size:12px;
        color:var(--kbf-slate);
    }
    .kbf-table-pager-left,
    .kbf-table-pager-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
    .kbf-table-pager-left{
        flex:0 0 auto;
        width:fit-content;
        max-width:100%;
        white-space:nowrap;
        flex-wrap:nowrap;
        display:flex;
        align-items:center;
        gap:8px;
    }
    .kbf-table-pager-right .kbf-pager-pages{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:wrap;
    }
    .kbf-table-pager-right .kbf-page-btn{
        border:1px solid #dbe3ef;
        background:#fff;
        color:var(--kbf-navy);
        padding:6px 9px;
        border-radius:8px;
        font-size:12px;
        font-weight:600;
        cursor:pointer;
        transition:all .15s ease;
        min-width:32px;
        text-align:center;
    }
    .kbf-table-pager-right .kbf-page-btn:hover{border-color:#bcd2f3;background:#f8fafc;}
    .kbf-table-pager-right .kbf-page-btn.is-active{
        background:#e7f1ff;
        border-color:#cfe1ff;
        color:#1f2a44;
    }
    .kbf-table-pager-right .kbf-page-gap{
        padding:0 2px;
        color:#94a3b8;
        font-weight:600;
    }
    .kbf-table-pager select{
        border:1px solid #e2e8f0;
        border-radius:8px;
        padding:6px 8px;
        font-size:12px;
        background:#f8fafc;
        color:var(--kbf-navy);
        font-weight:600;
    }
    .kbf-table-pager-btn{
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
    .kbf-table-pager-btn:hover{border-color:#bcd2f3;background:#f8fafc;}
    .kbf-table-pager-btn:disabled{opacity:.45;cursor:not-allowed;}
    .kbf-table-pager-btn.is-loading{
        position:relative;
        color:transparent;
        pointer-events:none;
        opacity:.75;
    }
    .kbf-table-pager-btn.is-loading::after{
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
        animation:kbfSpin .7s linear infinite;
    }
    .kbf-table-pager-page{font-weight:600;color:var(--kbf-navy);}
    .kbf-table-desc{
        margin-top:10px;
        font-size:12.5px;
        color:var(--kbf-slate);
        line-height:1.6;
    }
    .kbf-cell-stack{display:flex;flex-direction:column;gap:4px;}
    .kbf-cell-center{display:flex;flex-direction:column;justify-content:center;gap:4px;height:100%;}
    .kbf-cell-spacer{height:14px;}

    /* Alerts */
    .kbf-alert {
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 14px;
        font-size: 13.5px;
        font-weight: 500;
        line-height: 1.6;
        margin: 8px 0;
        border: 1px solid transparent;
        overflow: hidden;
        background: #fff;
    }
    .kbf-alert-compact{
        padding:8px 12px;
        font-size:12.5px;
        border-radius:10px;
        gap:8px;
    }
    .kbf-alert-center{
        justify-content:center;
        text-align:center;
    }
    .kbf-alert-block{
        width:100%;
        box-sizing:border-box;
    }
    .kbf-alert .kbf-strong{font-weight:600;}
    .kbf-alert p{margin:0;}
    .kbf-alert a{color:inherit;font-weight:600;text-decoration:underline;}
    .kbf-alert::before{
        content: "";
        width: 20px;
        height: 20px;
        border-radius: 50%;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 2px;
        color: inherit;
        font-size: 16px;
        font-weight: normal;
        line-height: 1;
        font-family: "Phosphor-Fill" !important;
    }
    .kbf-alert-noicon::before{
        display:none;
    }
    .kbf-alert-noicon{
        padding-left:14px;
    }
    .kbf-alert-success { background: linear-gradient(90deg,#ecfdf3 0%,#fff 70%); color: #166534; border-color: #bbf7d0; }
    .kbf-alert-success::before{
        content: "\e184"; /* ph-fill ph-check-circle */
    }
    .kbf-alert-error   { background: linear-gradient(90deg,#fff1f2 0%,#fff 70%); color: #9f1239; border-color: #fecdd3; }
    .kbf-alert-error::before{
        content: "\e4f8"; /* ph-fill ph-x-circle */
    }
    .kbf-alert-info    { background: linear-gradient(90deg,#eff6ff 0%,#fff 70%); color: #1e3a8a; border-color: #bfdbfe; }
    .kbf-alert-info::before{
        content: "\e4e2"; /* ph-fill ph-warning-circle */
    }
    .kbf-alert-warning { background: linear-gradient(90deg,#fffbeb 0%,#fff 70%); color: #92400e; border-color: #fde68a; }
    .kbf-alert-warning::before{
        content: "\e4e0"; /* ph-fill ph-warning */
    }

    /* Validation (match ring + text style) */
    .kbf-input-error{
        border-color:#dc2626 !important;
        box-shadow:0 0 0 3px rgba(220,38,38,.12);
    }
    .kbf-field-error{
        display:block;
        margin-top:6px;
        color:#dc2626;
        font-size:11.5px;
        font-weight:600;
    }

    /* Notices & helpers */
    .kbf-empty { text-align: center; padding: 48px 20px; color: var(--kbf-slate); }
    .kbf-empty svg { display: block; margin: 0 auto 12px; opacity: .4; }
    .kbf-divider { border: none; border-top: 1px solid var(--kbf-border); margin: 20px 0; }
    .kbf-meta { font-size: 12px; color: var(--kbf-slate); }
    .kbf-fund-amounts { display: flex; gap: 20px; margin: 10px 0; }
    .kbf-fund-amounts span .kbf-strong { display: block; font-size: 17px; font-weight: 600; color: var(--kbf-navy); }
    .kbf-fund-amounts span { font-size: 12px; color: var(--kbf-slate); }
    .kbf-verified-badge { display: inline-flex; align-items: center; gap: 4px; background: var(--kbf-navy); color: #fff; padding: 2px 9px; border-radius: 99px; font-size: 10.5px; font-weight: 700; vertical-align: middle; margin-left: 6px; }
    .kbf-verified-badge svg { width: 11px; height: 11px; }
    .kbf-payment-placeholder { background: #fffbeb; border: 1.5px dashed #fbbf24; border-radius: 8px; padding: 14px 16px; font-size: 12.5px; color: #92400e; margin: 12px 0; }
    .kbf-payment-placeholder .kbf-strong { display: block; margin-bottom: 4px; font-size: 13px; }
    .kbf-notif-placeholder { background: #eff6ff; border: 1.5px dashed #60a5fa; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #1e3a8a; margin: 8px 0; }
    .kbf-star { color: #f43f5e; }
    .kbf-star-empty { color: #fecdd3; }

    /* Page header â€” redesigned (clean, minimal card) */
    .kbf-page-header {
        position: relative;
        background: #fff;
        border: 1px solid var(--kbf-border);
        border-radius: 16px;
        padding: 24px 26px;
        margin-bottom: 20px;
        color: var(--kbf-text);
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        overflow: visible;
    }
    .kbf-page-header h2 {
        margin: 0 0 4px;
        font-size: 20px;
        font-weight: 600;
        color: var(--kbf-text);
        line-height: 1.3;
    }
    .kbf-page-header p {
        margin: 0;
        color: var(--kbf-text-sm);
        font-size: 13.5px;
        line-height: 1.6;
    }

    @media(max-width:800px) {
        .kbf-stats { grid-template-columns: 1fr 1fr; }
    }
    @media(max-width:640px) {
        .kbf-form-row, .kbf-form-row-3 { grid-template-columns: 1fr; }
        .kbf-tab-content { padding: 0; }
        .kbf-modal-body { padding: 16px; }
    }
    /* Share modal */
    .kbf-share-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;}
    .kbf-share-box{background:#fff;border-radius:14px;padding:28px;max-width:420px;width:100%;box-shadow: none;}
    .kbf-share-url-row{display:flex;gap:8px;margin:16px 0;}
    .kbf-share-url-input{flex:1;padding:10px 14px;border:1.5px solid var(--kbf-border);border-radius:8px;font-size:13px;color:var(--kbf-text);background:var(--kbf-slate-lt);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .kbf-share-platforms{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:4px;}
    #kbf-share-in,#kbf-share-wa{display:none !important;}
    .kbf-share-platform{display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border:1.5px solid var(--kbf-border);border-radius:10px;cursor:pointer;text-decoration:none;font-size:11.5px;font-weight:600;color:var(--kbf-navy);transition:all .15s;background:#fff;}
    .kbf-share-platform:hover{border-color:var(--kbf-navy);background:var(--kbf-slate-lt);}
    @media(max-width:520px){.kbf-share-platforms{grid-template-columns:repeat(2,1fr);}}
    /* Premium radius scale (global) */
    .kbf-card,
    .kbf-table-wrap,
    .kbf-table-empty,
    .kbf-tab-content,
    .kbf-modal,
    .kbf-empty,
    .kbf-page-header,
    .kbf-cta-card,
    .kbf-stat,
    .kbf-badge,
    .kbf-alert,
    .kbf-table-pager select,
    .kbf-table-pager-btn,
    .kbf-share-box,
    .kbf-share-platform,
    .kbf-input-with-toggle input,
    .kbf-form-group input,
    .kbf-form-group select,
    .kbf-form-group textarea,
    .kbf-btn{
        border-radius: var(--kbf-radius-lg);
    }
    .kbf-btn,
    .kbf-badge,
    .kbf-table-pager-btn,
    .kbf-table-pager select{
        border-radius: var(--kbf-radius-sm);
    }
    </style>
    <script>
    (function(){
        function kbfZeroTop(){
            var html = document.documentElement;
            var body = document.body;
            if (html) {
                html.style.setProperty('margin-top','0','important');
                html.style.setProperty('padding-top','0','important');
                html.style.setProperty('top','0','important');
            }
            if (body) {
                body.style.setProperty('margin-top','0','important');
                body.style.setProperty('padding-top','0','important');
                body.style.setProperty('top','0','important');
            }
            var topbar = document.querySelector('.bntm-topbar');
            if (topbar) {
                topbar.style.setProperty('height','0','important');
                topbar.style.setProperty('min-height','0','important');
                topbar.style.setProperty('margin','0','important');
                topbar.style.setProperty('padding','0','important');
                topbar.style.setProperty('display','none','important');
            }
            var adminbar = document.getElementById('wpadminbar');
            if (adminbar) {
                adminbar.style.setProperty('display','none','important');
                adminbar.style.setProperty('height','0','important');
            }
            var nodes = document.querySelectorAll('.bntm-content,.bntm-container,.bntm-bg,.kbf-wrap,.kbf-user-ui,.kbf-admin-ui');
            for (var i = 0; i < nodes.length; i++) {
                nodes[i].style.setProperty('margin-top','0','important');
                nodes[i].style.setProperty('padding-top','0','important');
                nodes[i].style.setProperty('top','0','important');
                nodes[i].style.setProperty('transform','none','important');
            }
            var bgEl = document.querySelector('.bntm-bg');
            if (bgEl) {
                var cs = window.getComputedStyle(bgEl);
                var bg = cs.backgroundColor;
                if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') {
                    html.style.setProperty('background', bg, 'important');
                    body.style.setProperty('background', bg, 'important');
                }
                var rect = bgEl.getBoundingClientRect();
                if (rect && rect.top > 0) {
                    bgEl.style.setProperty('margin-top', (-rect.top) + 'px', 'important');
                } else if (rect && rect.top < 0) {
                    bgEl.style.setProperty('margin-top', '0', 'important');
                }
            }
            if (body && (document.querySelector('.kbf-user-ui') || document.querySelector('.kbf-admin-ui') || document.querySelector('.kbf-wrap'))) {
                body.classList.add('kbf-module-page');
                var roots = document.querySelectorAll('#page,.site,.site-content,.content-area,.entry-content,.ast-container,.ast-container-fluid,.wp-site-blocks');
                for (var r = 0; r < roots.length; r++) {
                    roots[r].style.setProperty('margin-top','0','important');
                    roots[r].style.setProperty('padding-top','0','important');
                    roots[r].style.setProperty('top','0','important');
                }
                if (html) {
                    html.style.setProperty('--wp-admin--admin-bar--height','0px','important');
                }
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', kbfZeroTop);
        } else {
            kbfZeroTop();
        }
        window.addEventListener('load', kbfZeroTop);
    })();
    </script>
    <script>
    if (typeof window.kbfSetBtnLoading === 'undefined') {
        window.kbfSetBtnLoading = function(btn, on, label) {
            if (!btn) return;
            if (on) {
                btn.dataset.kbfLabel = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = label || 'Loading...';
            } else {
                btn.disabled = false;
                if (btn.dataset.kbfLabel) btn.innerHTML = btn.dataset.kbfLabel;
            }
        };
    }
    if (typeof window.kbfSetSkeleton === 'undefined') {
        // Skeletons removed: keep a no-op to avoid breaking callers.
        window.kbfSetSkeleton = function(el, on) { return; };
    }
    if (typeof window.kbfShowModal === 'undefined') {
        window.kbfShowModal = function(id){
            var el = typeof id === 'string' ? document.getElementById(id) : id;
            if (!el) return;
            el.style.display = 'flex';
            requestAnimationFrame(function(){ el.classList.add('is-open'); });
        };
        window.kbfHideModal = function(id){
            var el = typeof id === 'string' ? document.getElementById(id) : id;
            if (!el) return;
            el.classList.remove('is-open');
            setTimeout(function(){ el.style.display = 'none'; }, 220);
        };
    }
    if (typeof window.kbfFetchJson === 'undefined') {
        window.kbfFetchJson = function(url, fd, onOk, onErr) {
            fetch(url, {method:'POST', body:fd})
            .then(r=>r.text())
            .then(t=>{
                try {
                    const cleaned = String(t || '').trim();
                    const start = cleaned.indexOf('{');
                    const end = cleaned.lastIndexOf('}');
                    const payload = (start !== -1 && end !== -1 && end > start) ? cleaned.slice(start, end + 1) : cleaned;
                    onOk(JSON.parse(payload));
                }
                catch(e){ onErr('Invalid server response. ' + (t ? t.slice(0,200) : '')); }
            })
            .catch(err=>onErr(err && err.message ? err.message : 'Request failed.'));
        };
    }
    if (typeof window.kbfInitTablePager === 'undefined') {
        window.kbfInitTablePager = function(){
            document.querySelectorAll('.kbf-table').forEach(function(table){
                if(table.dataset.kbfPager === 'on') return;
                var tbody = table.querySelector('tbody');
                if(!tbody) return;
                var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
                if(rows.length === 0) return;
                var wrap = table.closest('.kbf-table-wrap') || table.parentElement;
                if(!wrap) return;
                if(wrap.nextElementSibling && wrap.nextElementSibling.classList.contains('kbf-table-pager')) return;

                table.dataset.kbfPager = 'on';

                var pager = document.createElement('div');
                pager.className = 'kbf-table-pager';
                pager.innerHTML = '' +
                  '<div class="kbf-table-pager-left">Show&nbsp;' +
                  '<select class="kbf-table-rows">' +
                    '<option value="5">5</option>' +
                    '<option value="10" selected>10</option>' +
                    '<option value="20">20</option>' +
                    '<option value="50">50</option>' +
                  '</select> rows' +
                  '</div>' +
                  '<div class="kbf-table-pager-right">' +
                    '<button class="kbf-table-pager-btn kbf-table-prev" type="button">Prev</button>' +
                    '<div class="kbf-pager-pages"></div>' +
                    '<button class="kbf-table-pager-btn kbf-table-next" type="button">Next</button>' +
                  '</div>';
                wrap.insertAdjacentElement('afterend', pager);

                var select = pager.querySelector('.kbf-table-rows');
                var prevBtn = pager.querySelector('.kbf-table-prev');
                var nextBtn = pager.querySelector('.kbf-table-next');
                var pagesWrap = pager.querySelector('.kbf-pager-pages');
                var page = 1;
                var perPage = 10;

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
                function renderPages(pages, current){
                    if (!pagesWrap) return;
                    pagesWrap.innerHTML = '';
                    buildPageModel(pages, current).forEach(function(p){
                        if(p === 'gap'){
                            var span = document.createElement('span');
                            span.className = 'kbf-page-gap';
                            span.textContent = 'â€¦';
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
                function render(){
                    var total = rows.length;
                    var pages = Math.max(1, Math.ceil(total / perPage));
                    if(page > pages) page = pages;
                    var start = (page - 1) * perPage;
                    var end = start + perPage;
                    rows.forEach(function(row, i){
                        row.style.display = (i >= start && i < end) ? '' : 'none';
                    });
                    renderPages(pages, page);
                    prevBtn.disabled = page <= 1;
                    nextBtn.disabled = page >= pages;
                    pager.style.display = total > 0 ? 'flex' : 'none';
                }

                function getPagerDelay(){
                    var base = 220;
                    var nav = (typeof navigator !== 'undefined' && navigator && navigator.connection) ? navigator.connection : null;
                    var type = nav && nav.effectiveType ? String(nav.effectiveType).toLowerCase() : '';
                    if(type === 'slow-2g' || type === '2g') return 420;
                    if(type === '3g') return 300;
                    return base;
                }
                function setLoading(btn, cb){
                    if(!btn || btn.classList.contains('is-loading')) return;
                    btn.classList.add('is-loading');
                    btn.disabled = true;
                    setTimeout(function(){
                        if(typeof cb === 'function') cb();
                        btn.classList.remove('is-loading');
                        render();
                    }, getPagerDelay());
                }

                select.addEventListener('change', function(){
                    perPage = parseInt(this.value, 10) || 10;
                    page = 1;
                    render();
                });
                prevBtn.addEventListener('click', function(){
                    if(page <= 1 || this.disabled || this.classList.contains('is-loading')) return;
                    setLoading(prevBtn, function(){
                        page = Math.max(1, page - 1);
                    });
                });
                nextBtn.addEventListener('click', function(){
                    var pages = Math.max(1, Math.ceil(rows.length / perPage));
                    if(page >= pages || this.disabled || this.classList.contains('is-loading')) return;
                    setLoading(nextBtn, function(){
                        page = Math.min(pages, page + 1);
                    });
                });
                render();
            });
        };
        document.addEventListener('DOMContentLoaded', window.kbfInitTablePager);
    }
    if (typeof window.kbfInitTableDescriptions === 'undefined') {
        window.kbfInitTableDescriptions = function(){
            var defaultText = 'This table lists the records for this section.';
            document.querySelectorAll('.kbf-table-wrap, .kbf-table-empty').forEach(function(wrap){
                if (!wrap || wrap.dataset.kbfDescApplied === '1') return;
                if (wrap.nextElementSibling && wrap.nextElementSibling.classList.contains('kbf-table-desc')) {
                    wrap.dataset.kbfDescApplied = '1';
                    return;
                }
                var desc = wrap.getAttribute('data-kbf-table-desc') || defaultText;
                if (!desc) return;
                var p = document.createElement('div');
                p.className = 'kbf-table-desc';
                p.textContent = desc;
                wrap.insertAdjacentElement('afterend', p);
                wrap.dataset.kbfDescApplied = '1';
            });
        };
        document.addEventListener('DOMContentLoaded', window.kbfInitTableDescriptions);
    }
    // Listen for payment success from Maya redirect window.
    window.addEventListener('message', function(ev){
        try {
            if (!ev || !ev.data || ev.data.type !== 'kbf_payment_success') return;
            var modal = document.getElementById('kbf-modal-sponsor');
            if (modal) modal.style.display = 'none';
            if (window.kbfSetLoadingPage) window.kbfSetLoadingPage(true);
            setTimeout(function(){ location.reload(); }, 600);
        } catch(e){}
    });
    // Fallback: listen for localStorage flag from other tabs/windows.
    window.addEventListener('storage', function(ev){
        try {
            if (!ev || ev.key !== 'kbf_payment_success' || !ev.newValue) return;
            var modal = document.getElementById('kbf-modal-sponsor');
            if (modal) modal.style.display = 'none';
            if (window.kbfSetLoadingPage) window.kbfSetLoadingPage(true);
            setTimeout(function(){ location.reload(); }, 600);
            // Clear flag after consumption
            try { localStorage.removeItem('kbf_payment_success'); } catch(e){}
        } catch(e){}
    });
    // One-time check in case storage event was missed.
    (function(){
        try {
            var payload = localStorage.getItem('kbf_payment_success');
            if (!payload) return;
            var modal = document.getElementById('kbf-modal-sponsor');
            if (modal) modal.style.display = 'none';
            if (window.kbfSetLoadingPage) window.kbfSetLoadingPage(true);
            setTimeout(function(){ location.reload(); }, 600);
            localStorage.removeItem('kbf_payment_success');
        } catch(e){}
    })();
    // Polling helper (used when storage events are blocked or missed).
    window.kbfAwaitPaymentSuccess = function(timeoutMs){
        var endAt = Date.now() + (timeoutMs || 180000);
        if (window.__kbfPaymentPoll) return;
        window.__kbfPaymentPoll = setInterval(function(){
            try {
                var payload = localStorage.getItem('kbf_payment_success');
                if (payload) {
                    var modal = document.getElementById('kbf-modal-sponsor');
                    if (modal) modal.style.display = 'none';
                    if (window.kbfSetLoadingPage) window.kbfSetLoadingPage(true);
                    localStorage.removeItem('kbf_payment_success');
                    clearInterval(window.__kbfPaymentPoll);
                    window.__kbfPaymentPoll = null;
                    setTimeout(function(){ location.reload(); }, 600);
                    return;
                }
            } catch(e){}
            if (Date.now() > endAt) {
                clearInterval(window.__kbfPaymentPoll);
                window.__kbfPaymentPoll = null;
            }
        }, 1000);
    };
    </script>

    <!-- Global Share Modal -->
    <div id="kbf-share-modal" class="kbf-share-modal-overlay" style="display:none;" onclick="if(event.target===this)kbfCloseShare()">
      <div class="kbf-share-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
          <h3 style="font-size:17px;font-weight:800;color:var(--kbf-navy);margin:0;">Share This Fund</h3>
          <button onclick="kbfCloseShare()" style="background:none;border:none;cursor:pointer;color:var(--kbf-slate);font-size:22px;line-height:1;padding:0;">&times;</button>
        </div>

        <p id="kbf-share-fund-title" style="font-size:13px;color:var(--kbf-slate);margin:0 0 4px;"></p>
        <div class="kbf-share-url-row">
          <input type="text" id="kbf-share-url-input" class="kbf-share-url-input" readonly>
          <button id="kbf-copy-btn" onclick="kbfCopyShareUrl()" class="kbf-btn kbf-btn-primary" style="padding:10px 16px;white-space:nowrap;flex-shrink:0;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
            Copy
          </button>
        </div>
        <div class="kbf-share-platforms">
          <a id="kbf-share-fb" href="#" target="_blank" class="kbf-share-platform" onclick="kbfSharePlatform('facebook');return false;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#1877f2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Facebook
          </a>
          <a id="kbf-share-x" href="#" target="_blank" class="kbf-share-platform" onclick="kbfSharePlatform('twitter');return false;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#111"><path d="M18.244 2H21.5l-7.147 8.168L22.5 22h-6.55l-5.14-6.71L4.9 22H1.644l7.64-8.735L1.5 2h6.72l4.65 6.02L18.244 2zm-1.15 18h1.803L7.08 4H5.147l11.947 16z"/></svg>
            X / Twitter
          </a>
          <a id="kbf-share-ig" href="#" target="_blank" class="kbf-share-platform" onclick="kbfSharePlatform('instagram');return false;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#e1306c"><path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5a4.25 4.25 0 0 0 4.25-4.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5zm8.85 2.7a.95.95 0 1 1 0 1.9.95.95 0 0 1 0-1.9zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 1.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z"/></svg>
            Instagram
          </a>
          <a id="kbf-share-msg" href="#" class="kbf-share-platform" onclick="kbfSharePlatform('native');return false;">
            <svg width="22" height="22" fill="none" stroke="var(--kbf-navy)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            More
          </a>
        </div>
      </div>
    </div>

    <script>
    (function(){
        function kbfZeroTop(){
            var html = document.documentElement;
            var body = document.body;
            if (html) {
                html.style.setProperty('margin-top','0','important');
                html.style.setProperty('padding-top','0','important');
                html.style.setProperty('top','0','important');
            }
            if (body) {
                body.style.setProperty('margin-top','0','important');
                body.style.setProperty('padding-top','0','important');
                body.style.setProperty('top','0','important');
            }
            var topbar = document.querySelector('.bntm-topbar');
            if (topbar) {
                topbar.style.setProperty('height','0','important');
                topbar.style.setProperty('min-height','0','important');
                topbar.style.setProperty('margin','0','important');
                topbar.style.setProperty('padding','0','important');
                topbar.style.setProperty('display','none','important');
            }
            var adminbar = document.getElementById('wpadminbar');
            if (adminbar) {
                adminbar.style.setProperty('display','none','important');
                adminbar.style.setProperty('height','0','important');
            }
            var nodes = document.querySelectorAll('.bntm-content,.bntm-container,.bntm-bg,.kbf-wrap,.kbf-user-ui,.kbf-admin-ui');
            for (var i = 0; i < nodes.length; i++) {
                nodes[i].style.setProperty('margin-top','0','important');
                nodes[i].style.setProperty('padding-top','0','important');
                nodes[i].style.setProperty('top','0','important');
                nodes[i].style.setProperty('transform','none','important');
            }
            var bgEl = document.querySelector('.bntm-bg');
            if (bgEl) {
                var cs = window.getComputedStyle(bgEl);
                var bg = cs.backgroundColor;
                if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') {
                    html.style.setProperty('background', bg, 'important');
                    body.style.setProperty('background', bg, 'important');
                }
                var rect = bgEl.getBoundingClientRect();
                if (rect && rect.top > 0) {
                    bgEl.style.setProperty('margin-top', (-rect.top) + 'px', 'important');
                } else if (rect && rect.top < 0) {
                    bgEl.style.setProperty('margin-top', '0', 'important');
                }
            }
            if (body && (document.querySelector('.kbf-user-ui') || document.querySelector('.kbf-admin-ui') || document.querySelector('.kbf-wrap'))) {
                body.classList.add('kbf-module-page');
                var roots = document.querySelectorAll('#page,.site,.site-content,.content-area,.entry-content,.ast-container,.ast-container-fluid,.wp-site-blocks');
                for (var r = 0; r < roots.length; r++) {
                    roots[r].style.setProperty('margin-top','0','important');
                    roots[r].style.setProperty('padding-top','0','important');
                    roots[r].style.setProperty('top','0','important');
                }
                if (html) {
                    html.style.setProperty('--wp-admin--admin-bar--height','0px','important');
                }
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', kbfZeroTop);
        } else {
            kbfZeroTop();
        }
        window.addEventListener('load', kbfZeroTop);
    })();
    </script>
    <script>
    var _kbfShareUrl = '';
    var _kbfShareTitle = '';
    var _kbfShareDesc = '';
    var _kbfFundDetailsBase = '<?php echo esc_js(kbf_get_page_url("fund_details")); ?>';

    window.kbfOpenShare = function(token, title, desc) {
        _kbfShareTitle = title || 'Check out this fund';
        _kbfShareDesc  = desc || '';
        _kbfShareUrl   = _kbfFundDetailsBase + (_kbfFundDetailsBase.indexOf('?') >= 0 ? '&' : '?') + 'kbf_share=' + encodeURIComponent(token);
        const shareBody = _kbfShareDesc ? _kbfShareDesc : 'Every contribution helps this cause.';
        const shareText = 'Support this fundraiser: "' + _kbfShareTitle + '"\n\n' + shareBody + '\n\nLearn more here:\n' + _kbfShareUrl;
        document.getElementById('kbf-share-url-input').value = _kbfShareUrl;
        document.getElementById('kbf-share-fund-title').textContent = '"' + _kbfShareTitle + '"';
        document.getElementById('kbf-copy-btn').innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg> Copy';
        // Facebook: sharer pre-fills the URL in the post composer -- add quote param for caption text
        const fbQuote = 'Support this fundraiser: "' + _kbfShareTitle + '"' + (_kbfShareDesc ? '\n\n' + _kbfShareDesc : '');
        const fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(_kbfShareUrl) + '&quote=' + encodeURIComponent(fbQuote);
        const xUrl  = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent('Support this fundraiser: "' + _kbfShareTitle + '"\n\n' + shareBody) + '&url=' + encodeURIComponent(_kbfShareUrl);
        // Instagram doesn't support prefilled web share; use the share URL as fallback
        const igUrl = _kbfShareUrl;
        document.getElementById('kbf-share-fb').href  = fbUrl;
        document.getElementById('kbf-share-x').href   = xUrl;
        document.getElementById('kbf-share-ig').href  = igUrl;
        // Ensure deprecated buttons are removed if cached markup still exists
        ['kbf-share-in','kbf-share-wa'].forEach(function(id){
          var el = document.getElementById(id);
          if(el && el.parentNode){ el.parentNode.removeChild(el); }
        });
        // Store for platform handler
        window._kbfFbUrl = fbUrl;
        window._kbfXUrl  = xUrl;
        window._kbfIgUrl = igUrl;
        window._kbfShareText = shareText;
        document.getElementById('kbf-share-modal').style.display = 'flex';
      };
    window.kbfCloseShare = function() {
        document.getElementById('kbf-share-modal').style.display = 'none';
    };
    window.kbfCopyShareUrl = function() {
        const input = document.getElementById('kbf-share-url-input');
        const btn   = document.getElementById('kbf-copy-btn');
        if(navigator.clipboard) {
            navigator.clipboard.writeText(_kbfShareUrl).then(function() {
                btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied!';
                btn.style.background = 'var(--kbf-green)';
                setTimeout(function(){ btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg> Copy'; btn.style.background = ''; }, 2500);
            });
        } else {
            input.select();
            document.execCommand('copy');
            btn.textContent = 'Copied!';
        }
    };
    window.kbfSharePlatform = function(platform) {
        if(platform === 'facebook') {
            window.open(window._kbfFbUrl || ('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(_kbfShareUrl)), '_blank', 'width=620,height=500,left=200,top=100');
        } else if(platform === 'twitter') {
            window.open(window._kbfXUrl || ('https://twitter.com/intent/tweet?text=' + encodeURIComponent(_kbfShareTitle) + '&url=' + encodeURIComponent(_kbfShareUrl)), '_blank');
        } else if(platform === 'instagram') {
            window.open(window._kbfIgUrl || _kbfShareUrl, '_blank');
        } else if(platform === 'native') {
            if(navigator.share) {
                navigator.share({ title: _kbfShareTitle, text: (window._kbfShareText || _kbfShareTitle), url: _kbfShareUrl })
                .catch(()=>{});
            } else {
                kbfCopyShareUrl();
            }
        }
    };
    // Backward-compat aliases used in various places
    window.kbfShareFund      = function(token, title, desc) { kbfOpenShare(token, title || 'Support this fund on KonekBayan', desc); };
    window.kbffShareFund     = function(token, title, desc) { kbfOpenShare(token, title || 'Support this fund on KonekBayan', desc); };
    window.kbfShareFundDetail= function(token, title, desc) { kbfOpenShare(token, title || 'Support this fund on KonekBayan', desc); };

    // Ã¢â€â‚¬Ã¢â€â‚¬ NEAR ME: browser geolocation Ã¢â€ â€™ Nominatim reverse geocode Ã¢â€ â€™ fill location input Ã¢â€â‚¬Ã¢â€â‚¬
    window.kbfNearMe = function(inputId, formId, btnRef) {
        const input = document.getElementById(inputId);
        let btn = null;
        if (btnRef && typeof btnRef === 'object' && btnRef.nodeType === 1) {
            btn = btnRef;
        } else if (typeof btnRef === 'string' && btnRef) {
            btn = document.getElementById(btnRef);
        }
        if (!btn) {
            const evt = (typeof window !== 'undefined' && window.event) ? window.event : null;
            btn = evt && evt.currentTarget ? evt.currentTarget : null;
        }
        if (!btn) {
            btn = document.getElementById('kbf-browse-nearme-btn') || document.getElementById('kbff-near-me-btn');
        }
        if (!navigator.geolocation) {
            alert('Your browser does not support geolocation. Please type your location manually.');
            return;
        }
        const origText = btn ? btn.innerHTML : '';
        if (btn) { btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8" stroke-width="2"></circle></svg>'; btn.disabled = true; }
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                // Nominatim reverse geocode -- free, no API key required
                fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&zoom=10&accept-language=en', {
                    headers: { 'Accept-Language': 'en', 'User-Agent': 'KonekBayan/1.0' }
                })
                .then(r => r.json())
                .then(data => {
                    const a = data.address || {};
                    // Only municipality/city level (skip province/region)
                    const city = a.city || a.municipality || a.town || a.village || a.county || '';
                    const place = city || data.display_name.split(',').slice(0,2).join(',').trim();
                    if (input) { input.value = place; input.focus(); }
                    if (btn)   { btn.innerHTML = origText; btn.disabled = false; }
                    // Auto-submit the form
                    if (formId) { const f = document.getElementById(formId); if (f) f.submit(); }
                })
                .catch(() => {
                    if (btn) { btn.innerHTML = origText; btn.disabled = false; }
                    alert('Could not determine your location. Please type it manually.');
                });
            },
            function(err) {
                if (btn) { btn.innerHTML = origText; btn.disabled = false; }
                if (err.code === 1) alert('Location permission denied. Please allow location access or type your location manually.');
                else alert('Could not get your location. Please type it manually.');
            },
            { timeout: 10000 }
        );
    };
    // Find Funds tab alias
    window.kbffNearMe = function() { kbfNearMe('kbff-search-input','kbff-search-form'); };
    </script>
    <?php
    if (function_exists('kbf_render_loading_overlay')) {
        echo kbf_render_loading_overlay();
    }
}

























