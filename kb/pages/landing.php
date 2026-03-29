<?php
/*
 * KBF landing page template (HTML/CSS/JS).
 */

if (!defined('ABSPATH')) exit;

function bntm_kbf_render_landing() {
    $cta_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('browse') : home_url('/');
    $login_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : '#';
    $join_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signup') : $cta_url;

    $urgent_1 = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/58/Elderly_woman_gazing_at_art_%28Unsplash%29.jpg/1200px-Elderly_woman_gazing_at_art_%28Unsplash%29.jpg';
    $urgent_2 = 'https://upload.wikimedia.org/wikipedia/commons/1/1c/Womens_wheelchair_basketball_%28Unsplash%29.jpg';
    $urgent_3 = 'https://images.unsplash.com/photo-1642059893618-22daf30e92a2?auto=format&fit=crop&fm=jpg&q=80&w=1800';

    $bw_1 = 'https://upload.wikimedia.org/wikipedia/commons/6/68/Filipino_family.jpg';
    $bw_2 = 'https://upload.wikimedia.org/wikipedia/commons/b/b2/Filipino_family.JPG';
    $bw_3 = 'https://upload.wikimedia.org/wikipedia/commons/1/1d/Filipino_family_Argao_cebu_1800%27s.jpg';
    $bw_4 = 'https://upload.wikimedia.org/wikipedia/commons/a/a1/Battle_of_Leyte_Filipino_volunteers.jpg';

    ob_start();
    ?>
    <!-- ================== CSS ================== -->
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Shippori+Antique+B1&display=swap');

    :root {
        --kbf-ink: #0f1115;
        --kbf-muted: #4f5a6b;
        --kbf-border: #edf0f4;
        --kbf-surface: #ffffff;
        --kbf-soft: #f7f8fb;
        --kbf-lime: #6fb6ff;
        --kbf-lime-dark: #0f2a52;
        --kbf-shadow: rgba(0, 0, 0, 0.05) 0px 2px 4px -1px, rgba(0, 0, 0, 0.04) 0px 1px 2px -1px;
        --kbf-shadow-lg: rgba(0, 0, 0, 0.06) 0px 6px 12px -3px, rgba(0, 0, 0, 0.05) 0px 3px 6px -2px;
        --kbf-radius: 26px;
    }

    .kbf-landing {
        font-family: 'Poppins', system-ui, -apple-system, sans-serif;
        color: var(--kbf-ink);
        background:
            radial-gradient(1200px 320px at 8% -12%, #eaf2ff 0%, transparent 62%),
            radial-gradient(1000px 320px at 92% -2%, #e6f0ff 0%, transparent 58%),
            var(--kbf-surface);
        width: 100%;
        max-width: 1240px;
        margin: 0 auto 18px;
        padding: 26px 22px 64px;
        border-radius: 18px;
        overflow: visible;
    }

    .kbf-landing, .kbf-landing *, .kbf-landing *::before, .kbf-landing *::after { box-sizing: border-box; }
    .kbf-container { overflow: visible; max-width: 1120px; margin: 0 auto; padding: 76px 22px 0; }
    .kbf-landing a { color: inherit; text-decoration: none; }
    .kbf-brand-text{ color:#3d8ef0; font-family:'Shippori Antique B1','Poppins',system-ui,-apple-system,sans-serif; }
        .kbf-divider {
        height: 1px;
        width: 100%;
        background: linear-gradient(90deg, transparent, #e5e9f2, transparent);
        margin: 30px 0;
        margin-top: 80px;
        margin-bottom: 80px;
    }
    html { scroll-behavior: smooth; }
    /* html.kbf-no-scroll, body.kbf-no-scroll { overflow-y: hidden; } */
    /* .kbf-landing-no-scroll { overflow: hidden; } */

    /* TOPBAR */
    .kbf-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 10px 16px;
        min-height: 56px;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid transparent;
        transition: border-color .2s ease, box-shadow .2s ease;
        margin: 0;
        width: 100%;
        box-sizing: border-box;
    }
    .kbf-topbar.kbf-topbar-scrolled {
        border-bottom-color: var(--kbf-border);
        box-shadow: 0 4px 24px rgba(15, 40, 80, 0.06);
    }
    .kbf-topbar-left { display: flex; align-items: center; gap: 28px; flex-wrap: wrap; }
    .kbf-brand { display: flex; align-items: center; gap: 10px; font-weight: 800; }
    .kbf-brand-badge {
        width: 26px; height: 26px;
        border-radius: 50%;
        background: #b9dcff;
        display: inline-flex; align-items: center; justify-content: center;
        color: #103054; font-weight: 800; font-size: 14px;
    }
    .kbf-nav { display: flex; flex-direction: row; gap: 20px; font-size: 12.5px; color: var(--kbf-muted); }
    .kbf-nav a { position: relative; display:inline-flex; align-items:center; }
    .kbf-nav a::after {
        content: ''; position: absolute; left: 0; bottom: -8px;
        width: 0; height: 2px; border-radius: 999px;
        background: var(--kbf-lime); transition: width .2s ease;
    }
    .kbf-nav a:hover::after { width: 100%; }
    .kbf-actions { display: flex; gap: 10px; align-items: center; }
    .kbf-btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 9px 18px; border-radius: 999px;
        font-weight: 400; font-size: 12.5px;
        border: 1px solid transparent;
        transition: transform .3s ease, box-shadow .3s ease, background .3s ease, filter .3s ease;
    }
    .kbf-btn.kbf-btn-primary {
        background: linear-gradient(135deg, #5ba8f5 0%, #3d8ef0 50%, #2070e0 100%);
        color: #ffffff;
        box-shadow:
            0 1px 2px rgba(32, 112, 224, 0.20),
            0 4px 14px rgba(42, 120, 220, 0.28),
            0 0 0 0px rgba(111, 182, 255, 0),
            inset 0 1px 0 rgba(255, 255, 255, 0.18);
        font-weight: 500;
        letter-spacing: 0.01em;
        position: relative;
        isolation: isolate;
    }
    .kbf-btn.kbf-btn-primary::before {
        content: '';
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        background: linear-gradient(135deg, #7ec4ff 0%, #5aaaf8 40%, #2878e8 100%);
        opacity: 0;
        z-index: -1;
        transition: opacity .3s ease;
    }
    .kbf-btn.kbf-btn-primary:visited,
    .kbf-btn.kbf-btn-primary:active { color: #ffffff; }
    .kbf-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow:
            0 1px 3px rgba(32, 112, 224, 0.15),
            0 8px 24px rgba(42, 120, 220, 0.45),
            0 16px 40px rgba(61, 142, 240, 0.20),
            inset 0 1px 0 rgba(255, 255, 255, 0.25);
        filter: brightness(1.06);
    }
    .kbf-btn-primary:hover::before {
        opacity: 1;
    }
    .kbf-btn-primary:active {
        transform: translateY(0px);
        box-shadow:
            0 1px 2px rgba(32, 112, 224, 0.30),
            0 2px 8px rgba(61, 142, 240, 0.28),
            0 0 0 2px rgba(111, 182, 255, 0.15),
            inset 0 1px 0 rgba(255, 255, 255, 0.12);
        filter: brightness(0.96);
        transition: transform .1s ease, box-shadow .1s ease, filter .1s ease;
    }
    .kbf-btn-ghost { background: transparent; border-color: var(--kbf-border); color: var(--kbf-muted); }
    .kbf-btn-ghost[aria-disabled="true"] { opacity: .7; cursor: not-allowed; }

    /* ============================================================
       NEW HERO — light theme + floating phone cards
       ============================================================ */
    .kbf-hero {
        width: 100%;
        margin-bottom: 34px;
        position: relative;
        background:
            radial-gradient(880px 680px at -8% 110%, rgba(111,182,255,0.24) 0%, rgba(111,182,255,0.08) 55%, transparent 100%),
            radial-gradient(860px 640px at 108% -10%, rgba(111,182,255,0.22) 0%, rgba(111,182,255,0.07) 55%, transparent 100%),
            #ffffff;
        border-radius: 22px;
        padding: 44px;
        border: 1px solid #dce8f8;
        box-shadow: var(--kbf-shadow-lg);
        overflow: hidden;
    }
    .kbf-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
    }
    .kbf-hero-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        gap: 40px;
        padding: 48px 0 32px;
        position: relative;
    }

    /* Left */
    .kbf-hero-left {
        flex: 0 0 auto;
        max-width: 480px;
        display: flex;
        flex-direction: column;
        gap: 18px;
        position: relative;
        z-index: 2;
    }
    .kbf-eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 999px;
        font-size: 11px; font-weight: 600;
        letter-spacing: .14em; text-transform: uppercase;
        color: #1a3a66; background: #e7f1ff;
        border: 1px solid #d7e7ff; width: fit-content;
    }
    .kbf-eyebrow-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: #6fb6ff; display: inline-block; flex-shrink: 0;
    }
    .kbf-hero-heading {
        font-size: clamp(40px, 5.5vw, 64px);
        font-weight: 800; line-height: 1.05;
        color: #0d1a2e; letter-spacing: -1.5px; margin: 0;
    }
    .kbf-hero-highlight {
        display: inline-block;
        background: #6fb6ff; color: #0f2a52;
        border-radius: 8px; padding: 2px 14px; font-style: normal;
    }
    .kbf-hero-desc {
        font-size: 14px; font-weight: 300;
        color: var(--kbf-muted); line-height: 1.7; margin: 0;
    }
    
    .kbf-hero-cta-btn { width: fit-content; padding: 12px 22px; gap: 10px; }
    .kbf-hero-cta-btn img {
        width: 17px;
        height: 17px;
        margin-left: 8px;
        filter: invert(100%);
        display: inline-block;
        transform: scale(1);
        transition: transform .2s ease;
    }
    .kbf-hero-cta-btn:hover img {
        transform: scale(1) rotate(45deg);
    }
    .kbf-hero-arrow {
        width: 26px; height: 26px;
        background: #ffffff; color: #ff6f6f;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0;
    }

    /* Right: floating phone cards */
    .kbf-hero-right {
        flex: 0 0 420px; height: 500px;
        position: relative; z-index: 2;
    }
     /* Cards wrap — fixed internal coordinate system */
    .kbf-cards-wrap {
        position: absolute;
        inset: 0;
        --cw: 420px;
        --ch: 500px;
        width: var(--cw);
        height: var(--ch);
        transform: scale(1.08);
        transform-origin: top left;
    }
    .kbf-pcard {
        position: absolute; border-radius: 22px; overflow: hidden;
        background: #fff; border: 1px solid #ddeaf8;
        box-shadow: var(--kbf-shadow);
    }
    /* Main card — tall, center-left, highest z */
    .kbf-pcard-main {
        width: 200px; height: 340px;
        left: 80px; top: 50%;
        transform: translateY(-50%);
        z-index: 4;
        animation: kbfFloatMain 5s ease-in-out infinite;
    }
    /* Bottom-right card — shortest, sits bottom-right */
 .kbf-pcard-br {
        width: 124px; height: 210px;
        right: 15%; bottom: 30%;
        z-index: 3; transform: translateY(-50%) rotate(1.5deg);
        animation: kbfFloatBR 4.5s ease-in-out infinite;
    }
    /* Left card — partially hidden behind main, slight counter-tilt */
    .kbf-pcard-tl {
        width: 124px; height: 210px;
        left: 0; top: 50%;
        transform: translateY(-50%) rotate(-1.5deg);
        z-index: 2;
        animation: kbfFloatTL 5.5s ease-in-out infinite;
    }
    @keyframes kbfFloatMain { 0%,100%{transform:translateY(-50%)} 50%{transform:translateY(calc(-50% - 9px))} }
    @keyframes kbfFloatTR   { 0%,100%{transform:rotate(1.5deg) translateY(0)} 50%{transform:rotate(1.5deg) translateY(-7px)} }
    @keyframes kbfFloatBR   { 0%,100%{transform:rotate(1.5deg) translateY(0)} 50%{transform:rotate(1.5deg) translateY(-5px)} }
    @keyframes kbfFloatTL   { 0%,100%{transform:translateY(-50%) rotate(-1.5deg) translateY(0px)} 50%{transform:translateY(calc(-50% - 6px)) rotate(-1.5deg)} }

    .kbf-pcard-img {
        width: 100%; height: calc(100% - 48px);
        display: flex; align-items: center; justify-content: center;
    }
    .kbf-pimg-1 { background: linear-gradient(160deg, #daeeff 0%, #b8d8f8 100%); }
    .kbf-pimg-2 { background: linear-gradient(160deg, #c8e4ff 0%, #93c8f8 100%); }
    .kbf-pimg-3 { background: linear-gradient(160deg, #d4edff 0%, #a8d8fa 100%); }
    .kbf-pimg-4 { background: linear-gradient(160deg, #e0f0ff 0%, #b4d4f5 100%); }
    .kbf-pcard-icon {
        width: 38px;
        height: 38px;
        opacity: 0.85;
        display: block;
        filter: invert(22%) sepia(25%) saturate(1200%) hue-rotate(185deg) brightness(0.9);
    }
    .kbf-pcard-bar {
        padding: 7px 9px; background: #fff;
        display: flex; align-items: center; gap: 6px;
        border-top: 1px solid #edf2f8; height: 48px;
    }
    .kbf-pcard-av {
        width: 22px; height: 22px; border-radius: 50%;
        background: #e7f1ff;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; flex-shrink: 0;
    }
    .kbf-pcard-av img {
        width: 12px;
        height: 12px;
        display: block;
        filter: invert(22%) sepia(25%) saturate(1200%) hue-rotate(185deg) brightness(0.9);
    }
    .kbf-pcard-name { font-size: 9px; font-weight: 600; color: #0d1a2e; line-height: 1.3; }
    .kbf-pcard-sub  { font-size: 8px; color: #8aa0b8; }
    .kbf-pcard-play {
        margin-left: auto; width: 18px; height: 18px;
        background: #e7f1ff; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 8px; color: #2a6aad; flex-shrink: 0;
    }
    .kbf-pcard-play img {
        width: 8px;
        height: 8px;
        display: block;
        filter: invert(25%) sepia(30%) saturate(1200%) hue-rotate(185deg) brightness(0.9);
    }
    .kbf-fchip {
        position: absolute; background: #fff;
        border: 1px solid #d7e7ff; border-radius: 20px;
        padding: 5px 11px; font-size: 10.5px; font-weight: 600;
        color: #1a3a66; display: flex; align-items: center; gap: 6px;
        z-index: 5; white-space: nowrap;
        box-shadow: var(--kbf-shadow);
        backdrop-filter: blur(8px);
    }

    /* SECTIONS */
    .kbf-section { margin-top: 54px; scroll-margin-top: calc(var(--kbf-topbar-h, 64px) + 16px); }
    .kbf-section:first-of-type { margin-top: 32px; }
    .kbf-section h2 { font-size: 22px; margin: 0 0 6px; }
    .kbf-section p { color: var(--kbf-muted); margin: 0; font-size: 13.5px; }

    .kbf-feature-grid { margin-top: 24px; display: grid; grid-template-columns: repeat(6, 1fr); gap: 18px; }
    .kbf-feature-grid .kbf-card { grid-column: span 2; }
    .kbf-feature-grid .kbf-card:nth-child(4),
    .kbf-feature-grid .kbf-card:nth-child(5) { grid-column: span 3; }
    .kbf-card {
        background: var(--kbf-surface); border: 1px solid var(--kbf-border);
        border-radius: 18px; padding: 16px;
        box-shadow: var(--kbf-shadow);
        transition: transform .2s ease, box-shadow .2s ease;
        position: relative; overflow: hidden;
    }
    .kbf-card:hover { transform: translateY(-3px); box-shadow: var(--kbf-shadow-lg); }
    .kbf-card::after {
        content: '';
        position: absolute;
        width: 380px;
        height: 300px;
        right: -130px;
        top: -150px;
        background: radial-gradient(circle at center, rgba(111,182,255,.35), rgba(111,182,255,0) 70%);
        opacity: 0;
        transition: opacity .25s ease, transform .25s ease;
        pointer-events: none;
    }
    .kbf-card:hover::after { opacity: 1; transform: translate(10px, -10px); }
    .kbf-card .kbf-chip {
        transition: 0.3s ease;
        width: 30px; height: 30px; border-radius: 12px;
        background: #e7f1ff;
        border: 1px solid #d7e7ff;
        display: inline-flex; align-items: center;
        justify-content: center; color: #2a5a9e; font-weight: 700; font-size: 13px;
    }
    .kbf-card .kbf-chip img {
        width: 16px;
        height: 16px;
        display: block;
        filter: invert(46%) sepia(85%) saturate(1381%) hue-rotate(198deg) brightness(98%) contrast(93%);
    }
    .kbf-card h4 { margin: 12px 0 6px; font-size: 14px; }
    .kbf-card p { font-size: 12.5px; }

    .kbf-urgent-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-top: 24px; }
    .kbf-urgent-card {
        background: var(--kbf-surface); border: 1px solid var(--kbf-border);
        border-radius: 18px; overflow: hidden; box-shadow: var(--kbf-shadow);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .kbf-urgent-card:hover { transform: translateY(-3px); box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12); }
    .kbf-urgent-thumb { height: 180px; background-size: cover; background-position: center; }
    .kbf-urgent-body { padding: 14px 16px 16px; }
    .kbf-urgent-meta { font-size: 11.5px; color: var(--kbf-muted); }
    .kbf-urgent-title { font-size: 13.5px; font-weight: 700; margin: 6px 0 10px; }
    .kbf-progress { height: 6px; background: #edf1f7; border-radius: 999px; overflow: hidden; }
    .kbf-progress span { display: block; height: 100%; background: var(--kbf-lime); width: 68%; }
    .kbf-amounts { display: flex; justify-content: space-between; font-size: 12px; color: var(--kbf-muted); margin-top: 10px; }
    .kbf-about-grid { display: grid; grid-template-columns: 1.1fr .9fr; gap: 18px; margin-top: 24px; align-items: stretch; }
    .kbf-about-card {
        background: var(--kbf-surface);
        border: 1px solid var(--kbf-border);
        border-radius: 18px;
        padding: 18px;
        box-shadow: var(--kbf-shadow);
    }
    .kbf-about-card h4 { margin: 0 0 8px; font-size: 16px; }
    .kbf-about-card p { margin: 0; color: var(--kbf-muted); font-size: 13.5px; line-height: 1.6; }
    .kbf-about-photo {
        border-radius: 18px;
        overflow: hidden;
        background: #e9eef6;
        border: 1px solid var(--kbf-border);
        box-shadow: var(--kbf-shadow);
        height: 440px;
    }
    .kbf-about-photo img { width: 100%; height: 100%; object-fit: cover; display: block; min-height: 0; }

    .kbf-stat {
        margin-top: 44px; display: grid; grid-template-columns: 1fr;
        align-items: center; position: relative;
        text-align: center; padding: 28px 12px 34px;
    }
    .kbf-stat h3 { font-size: 72px; font-weight: 800; margin: 10px 0 6px; }
    .kbf-stat p { margin: 0 0 16px; color: var(--kbf-muted); }
    .kbf-stat .kbf-photo-grid { position: absolute; inset: 0; pointer-events: none; z-index: 1; }
    .kbf-stat .kbf-photo-grid img {
        width: 90px; height: 110px; border-radius: 18px;
        object-fit: cover; filter: grayscale(100%);
        box-shadow: var(--kbf-shadow); position: absolute;
    }
    .kbf-stat .kbf-photo-grid img:nth-child(1) { left: 4%; top: 6%; }
    .kbf-stat .kbf-photo-grid img:nth-child(2) { right: 4%; top: 6%; }
    .kbf-stat .kbf-photo-grid img:nth-child(3) { left: 8%; bottom: 6%; }
    .kbf-stat .kbf-photo-grid img:nth-child(4) { right: 8%; bottom: 6%; }
    .kbf-stat .kbf-stat-content { position: relative; z-index: 2; }
    .kbf-stat .kbf-btn-primary { margin-top: 6px; }

    .kbf-faq { margin-top: 22px; border-top: 1px solid var(--kbf-border); }
    .kbf-faq details { border-bottom: 1px solid var(--kbf-border); padding: 18px 0; }
    .kbf-faq summary {
        list-style: none; cursor: pointer; font-size: 13.5px; font-weight: 600;
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
    }
    .kbf-faq summary::-webkit-details-marker { display: none; }
    .kbf-faq summary::after { content: '+'; color: var(--kbf-muted); font-weight: 700; }
    .kbf-faq details[open] summary::after { content: '–'; }
    .kbf-faq details p { margin: 10px 0 0; color: var(--kbf-muted); font-size: 12.5px; line-height: 1.5; }
    .kbf-faq-body {
        display: grid;
        grid-template-rows: 0fr;
        transition: grid-template-rows .3s ease;
    }
    .kbf-faq-body > div { overflow: hidden; }
    .kbf-faq details[open] .kbf-faq-body { grid-template-rows: 1fr; }

    .kbf-footer {
        margin-top: 48px; 
        background: #0c0f14; 
        color: #b9c0cc;
        margin-bottom:48px;
        border-radius: 26px; padding: 26px 28px;
        display: flex; flex-direction: row;
        justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 18px;
    }
    .kbf-reveal { opacity: 0; transform: translateY(12px); animation: kbfFadeUp .6s ease forwards; }
    .kbf-reveal.delay-1 { animation-delay: .1s; }
    .kbf-reveal.delay-2 { animation-delay: .2s; }
    .kbf-reveal.delay-3 { animation-delay: .3s; }
    @keyframes kbfFadeUp { to { opacity: 1; transform: translateY(0); } }
    .kbf-footer h5 { margin: 0 0 8px; color: #fff; }
    .kbf-footer small { color: #8590a6; }
    .kbf-footer .kbf-social { display: flex; gap: 8px; }
    .kbf-footer .kbf-social a {
        width: 34px; height: 34px; border-radius: 50%;
        border: 1px solid rgba(255,255,255,.18);
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: 12px;
    }
    .kbf-footer .kbf-social img {
        width: 16px;
        height: 16px;
        display: block;
        filter: invert(100%);
    }

    /* About section extras */
    .kbf-about-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1px;
        background: var(--kbf-border);
        border: 1px solid var(--kbf-border);
        border-radius: 18px;
        overflow: hidden;
        margin-top: 18px;
    }
    .kbf-about-stat {
        background: var(--kbf-surface);
        padding: 24px 20px;
        text-align: center;
    }
    .kbf-about-stat-num {
        font-size: 32px;
        font-weight: 400;
        background: linear-gradient(to top, #1f6fe0 0%, #4da0ff 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        -webkit-text-fill-color: transparent;
        letter-spacing: -1px;
        line-height: 1;
    }
    .kbf-about-stat-label {
        font-size: 11.5px;
        color: var(--kbf-muted);
        margin-top: 6px;
        line-height: 1.4;
    }
    .kbf-about-values {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        margin-top: 14px;
    }
    .kbf-about-value {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        background: var(--kbf-soft);
        border: 1px solid var(--kbf-border);
        border-radius: 16px;
        padding: 18px;
    }
    .kbf-about-value-icon {
        font-size: 22px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .kbf-about-value-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--kbf-ink);
        margin-bottom: 4px;
    }
    .kbf-about-value-desc {
        font-size: 12px;
        color: var(--kbf-muted);
        line-height: 1.6;
    }

    @media (max-width: 900px) {
        .kbf-about-stats { grid-template-columns: repeat(2, 1fr); }
        .kbf-about-values { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
        .kbf-about-stats { grid-template-columns: repeat(2, 1fr); }
        .kbf-about-stat-num { font-size: 26px; }
    }
    
    /* Hamburger menu */
    .kbf-hamburger {
        display: none;
        background: none;
        border: 1px solid var(--kbf-border);
        border-radius: 8px;
        padding: 6px 8px;
        cursor: pointer;
        color: var(--kbf-muted);
        align-items: center;
        justify-content: center;
    }
    .kbf-mobile-menu-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-bottom: 1px solid var(--kbf-border);
        background: #fff;
    }
    .kbf-hamburger img {
        width: 18px;
        height: 18px;
        display: block;
        filter: invert(30%) sepia(10%) saturate(800%) hue-rotate(185deg) brightness(0.9);
    }
    .kbf-mobile-menu {
        display: none;
        flex-direction: column;
        gap: 0;
        width: 100%;
        background: #fff;
        border: 1px solid var(--kbf-border);
        border-radius: 0;
        margin-top: 8px;
        overflow: hidden;
        box-shadow: var(--kbf-shadow);
    }
    .kbf-mobile-menu.kbf-menu-open { display: flex; }
    .kbf-mobile-menu a {
        padding: 13px 18px;
        font-size: 13.5px;
        color: var(--kbf-ink);
        border-bottom: 1px solid var(--kbf-border);
        transition: background .15s ease;
    }
    .kbf-mobile-menu a:last-of-type { border-bottom: none; }
    .kbf-mobile-menu-actions {
        display: flex;
        flex-direction: row;
        gap: 8px;
        padding: 12px 14px;
        border-top: 1px solid var(--kbf-border);
        background: var(--kbf-soft);
    }
    .kbf-mobile-menu-actions .kbf-btn { flex: 1; justify-content: center; }
    
    /* Mobile menu overlay behaviour */
    .kbf-mobile-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 98;
        animation: kbfOverlayIn .2s ease forwards;
    }
    .kbf-mobile-overlay.kbf-overlay-open { display: block; }
    @keyframes kbfOverlayIn { from { opacity: 0; } to { opacity: 1; } }

    .kbf-mobile-menu {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 99;
        border-radius: 0;
        margin-top: 0;
        transform: translateY(-110%);
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--kbf-border);
        box-shadow: var(--kbf-shadow-lg);
    }
    .kbf-mobile-menu.kbf-menu-open {
        transform: translateY(0);
        display: flex;
    }
    
    /* Responsive */
   /* Large tablets / small desktops (≤1024px) */
    @media (max-width: 1024px) {
        .kbf-hero-left { max-width: 420px; }
        .kbf-hero-right { flex: 0 0 360px; height: 440px; }
        .kbf-cards-wrap { transform: scale(calc(360 / 420)); }
    }

    @media (max-width: 900px) {
        .kbf-nav { display: none; }
        .kbf-hamburger { display: inline-flex; }
        .kbf-actions { display: none; }
        .kbf-hamburger { display: inline-flex; margin-left: auto; }        
        .kbf-mobile-menu a { text-align: center; }
        .kbf-hero-heading { font-size: 38px; letter-spacing: -1px; }
        .kbf-hero-right { flex: 0 0 300px; height: 380px; }
        .kbf-cards-wrap { transform: scale(calc(300 / 420)); }
        .kbf-feature-grid { grid-template-columns: 1fr; }
        .kbf-feature-grid .kbf-card { grid-column: span 1; }
        .kbf-feature-grid .kbf-card:nth-child(4),
        .kbf-feature-grid .kbf-card:nth-child(5) { grid-column: span 1; }
        .kbf-urgent-grid { grid-template-columns: 1fr; }
        .kbf-about-grid { grid-template-columns: 1fr; }
        .kbf-about-photo { height: 320px; }
        .kbf-stat .kbf-photo-grid { display: none; }
        .kbf-stat h3 { font-size: 56px; }
        .kbf-divider { margin-top: 56px; margin-bottom: 56px; }
        .kbf-footer { flex-direction: column; align-items: center; text-align: center; margin: 20px auto; }
        .kbf-footer .kbf-social { justify-content: center; }
        .kbf-footer .kbf-brand { justify-content: center; }
        .kbf-footer > div:first-child { display: contents; }
        .kbf-footer .kbf-brand { order: 1; }
        .kbf-footer p { order: 2; }
        .kbf-footer .kbf-social { order: 3; }
        .kbf-footer small { order: 4; }
    }
    @media (max-width: 830px) {
        .kbf-hero { padding: 28px 24px; }
        .kbf-hero-inner {
            flex-direction: column;
            align-items: center;
            padding: 24px 0 16px;
            gap: 32px;
            text-align: center;
        }
        .kbf-hero-left { align-items: center; }
        .kbf-hero-left .kbf-chip { margin-left: auto; margin-right: auto; }
        .kbf-hero-left .kbf-actions { justify-content: center; }
        .kbf-hero-left { max-width: 100%; gap: 14px; }
        .kbf-hero-heading { font-size: 34px; letter-spacing: -0.5px; }
        .kbf-hero-right {
            width: 100%;
            flex: none;
            height: 320px;
            position: relative;
            align-self: center;
        }
        .kbf-cards-wrap {
            left: 50%;
            right: auto;
            margin-left: auto;
            margin-right: auto;
            transform: translateX(-50%) scale(calc(320 / 500));
            transform-origin: top center;
        }
    }

    @media (max-width: 720px) {
        .kbf-landing { padding: 20px 14px 48px; text-align: center; }
        .kbf-topbar { flex-wrap: wrap; gap: 12px; justify-content: center; }
        .kbf-actions { width: 100%; justify-content: center; gap: 8px; }
        .kbf-feature-grid { grid-template-columns: 1fr; gap: 12px; }
        .kbf-feature-grid .kbf-card { grid-column: span 1; }
        .kbf-feature-grid .kbf-card:nth-child(4),
        .kbf-feature-grid .kbf-card:nth-child(5) { grid-column: span 1; }
        .kbf-section h2 { font-size: 20px; }
        .kbf-stat h3 { font-size: 48px; }
        .kbf-divider { margin-top: 44px; margin-bottom: 44px; }
        .kbf-footer { flex-direction: column; align-items: center; text-align: center; margin: 20px auto; }
        .kbf-footer .kbf-social { justify-content: center; }
        .kbf-footer .kbf-brand { justify-content: center; }
    }

    @media (max-width: 480px) {
        .kbf-landing { padding: 14px 10px 36px; }
        .kbf-topbar { padding: 6px 0 14px; }
        .kbf-brand-badge { width: 22px; height: 22px; font-size: 12px; }
        .kbf-hero { padding: 20px 16px 28px; border-radius: 16px; }
        .kbf-hero-heading { font-size: 28px; letter-spacing: -0.5px; }
        .kbf-hero-desc { font-size: 13px; }
        .kbf-hero-right { height: 260px; }
        .kbf-cards-wrap {
            transform: translateX(-50%) scale(calc(260 / 500));
        }
        .kbf-eyebrow { font-size: 9.5px; padding: 5px 10px; }
        .kbf-btn { font-size: 12px; padding: 8px 14px; }
        .kbf-actions .kbf-btn { width: auto; }
        .kbf-feature-grid { grid-template-columns: 1fr; gap: 10px; }
        .kbf-feature-grid .kbf-card,
        .kbf-feature-grid .kbf-card:nth-child(4),
        .kbf-feature-grid .kbf-card:nth-child(5) { grid-column: span 1; }
        .kbf-urgent-grid { grid-template-columns: 1fr; }
        .kbf-urgent-thumb { height: 180px; }
        .kbf-about-photo { height: 240px; }
        .kbf-section h2 { font-size: 18px; }
        .kbf-section p { font-size: 13px; }
        .kbf-stat h3 { font-size: 40px; }
        .kbf-stat p { font-size: 13px; }
        .kbf-faq summary { font-size: 13px; }
        .kbf-faq details p { font-size: 12px; }
        .kbf-footer { padding: 20px 18px; border-radius: 18px; gap: 14px; }
        .kbf-footer p { font-size: 12px; }
        .kbf-divider { margin-top: 36px; margin-bottom: 36px; }
    }

    @media (max-width: 360px) {
        .kbf-hero-heading { font-size: 24px; }
        .kbf-hero-right { height: 220px; }
        .kbf-cards-wrap {
            transform: translateX(-50%) scale(calc(220 / 500));
        }
        .kbf-stat h3 { font-size: 34px; }
        .kbf-btn.kbf-btn-primary { font-size: 11.5px; }
    }
    </style>

    <!-- ================== HTML ================== -->


    <section class="kbf-landing">
     
        <div class="kbf-mobile-overlay" id="kbf-mobile-overlay"></div>
         <div class="kbf-container">
  

        <!-- NAVBAR -->
        <div class="kbf-topbar kbf-reveal">
          <div class="kbf-topbar-left">
            <div class="kbf-brand">
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="ambag" style="width:22px;height:22px;object-fit:contain;border-radius:6px;">
              <span class="kbf-brand-text" style="font-weight: 00;">ambag</span>
            </div>
            <nav class="kbf-nav">
              <a href="#kbf-home" onclick="return kbfScrollTo('kbf-home')">Home</a>
              <a href="#kbf-how" onclick="return kbfScrollTo('kbf-how')">Features</a>
              <a href="#kbf-donation" onclick="return kbfScrollTo('kbf-donation')">About</a>
              <a href="#kbf-faq" onclick="return kbfScrollTo('kbf-faq')">FAQ</a>
            </nav>
          </div>
          <div class="kbf-actions">
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($login_url); ?>">Sign In to Start</a>
          </div>
          <button class="kbf-hamburger" id="kbf-hamburger-btn" aria-label="Open menu">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/list.svg" alt="Menu" id="kbf-hamburger-icon">
          </button>
        </div>
              <div class="kbf-mobile-menu" id="kbf-mobile-menu">
          <div class="kbf-mobile-menu-header">
            <div class="kbf-brand">
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="ambag" style="width:24px;height:24px;object-fit:contain;border-radius:6px;">
              <span class="kbf-brand-text" style="font-weight:800;">ambag</span>
            </div>
            <button class="kbf-hamburger" onclick="document.getElementById('kbf-hamburger-btn').click()" aria-label="Close menu" style="display:inline-flex;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/x-lg.svg" alt="Close">
            </button>
          </div>
            <a href="#kbf-home" onclick="kbfMobileNav('kbf-home')">Home</a>
            <a href="#kbf-how" onclick="kbfMobileNav('kbf-how')">Features</a>
            <a href="#kbf-donation" onclick="kbfMobileNav('kbf-donation')">About</a>
            <a href="#kbf-faq" onclick="kbfMobileNav('kbf-faq')">FAQ</a>  
          <div class="kbf-mobile-menu-actions">
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($login_url); ?>">Sign In to Start</a>
          </div>
        </div>

        <!-- HERO -->
        <div id="kbf-home" class="kbf-hero kbf-reveal delay-1" style="margin-top: 45px; margin-bottom: 45px;">
          <div class="kbf-hero-inner">

            <!-- Left: Text -->
            <div class="kbf-hero-left">
              <div class="kbf-eyebrow">
                <span class="kbf-eyebrow-dot"></span>
                Community‑Powered Fundraising
              </div>

              <h1 class="kbf-hero-heading" style="font-weight: 400;">
                Crowdfunding<br>
                Built For
                Bayanihan.
              </h1>

              <p class="kbf-hero-desc">
                Ambag gives Filipino families a proper place to raise support — with real transparency, real updates, and real accountability.
              </p>


              <a class="kbf-btn kbf-btn-primary kbf-hero-cta-btn" href="<?php echo esc_url($cta_url); ?>">
                Start Supporting
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/arrow-up-right.svg" alt="">

              </a>
            </div>
            
            <!-- Right: Floating phone cards -->
            <div class="kbf-hero-right" aria-hidden="true">
              <div class="kbf-cards-wrap">
                <div class="kbf-pcard kbf-pcard-tl">
                  <div class="kbf-pcard-img kbf-pimg-1"><span class="kbf-pcard-icon">🏘️</span></div>
                  <div class="kbf-pcard-bar">
                    <div class="kbf-pcard-av">🤝</div>
                    <div><div class="kbf-pcard-name">Yourself</div><div class="kbf-pcard-sub">Health</div></div>
                    <div class="kbf-pcard-play">▶</div>
                  </div>
                </div>
                <div class="kbf-pcard kbf-pcard-main">
                  <div class="kbf-pcard-img kbf-pimg-2"><span class="kbf-pcard-icon">💙</span></div>
                  <div class="kbf-pcard-bar">
                    <div class="kbf-pcard-av">🌱</div>
                    <div><div class="kbf-pcard-name">Charity or Events</div><div class="kbf-pcard-sub">Community</div></div>
                    <div class="kbf-pcard-play">▶</div>
                  </div>
                </div>
                <div class="kbf-pcard kbf-pcard-br">
                  <div class="kbf-pcard-img kbf-pimg-4"><span class="kbf-pcard-icon">🏅</span></div>
                  <div class="kbf-pcard-bar">
                    <div class="kbf-pcard-av">🔒</div>
                    <div><div class="kbf-pcard-name">Someone Else</div><div class="kbf-pcard-sub">Protected</div></div>
                    <div class="kbf-pcard-play">▶</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- FEATURES -->
        <div id="kbf-how" class="kbf-section kbf-reveal delay-1" style="margin-top: 80px;">
          <h2 style="font-size: 1.5em; font-weight: 400;">Hindi lang pera — tiwala.</h2>
          <p>Raising funds online is easy. Getting people to actually trust your campaign — that's the hard part. Here's how we make it easier.</p>
          <div class="kbf-feature-grid">
            <div class="kbf-card">
                <div class="kbf-chip" aria-hidden="true">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/book-half.svg" alt="">
                </div>
               <h4 style="font-weight: 500;">Tell Your Real Story</h4>
               <p>Write it the way you'd explain it to a neighbor. Honest campaigns — even messy ones — raise more than polished ones. We help you write it right.</p>
            </div>
            <div class="kbf-card">
              <div class="kbf-chip" aria-hidden="true">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/megaphone-fill.svg" alt="">
              </div>
                <h4 style="font-weight: 500;">Spread Through Your Network</h4>
               <p>Share your link to group chats, barangay pages, barkadas. Your first wave of donors is already in your contacts — we just make the ask easier.</p>
            </div>
            <div class="kbf-card">
              <div class="kbf-chip" aria-hidden="true">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/hand-thumbs-up-fill.svg" alt="">
              </div>
                <h4 style="font-weight: 500;">Donors Can Trust You</h4>
               <p>Verified organizers, ID-checked accounts, and a public update log. When donors see the checkmark, they know their money won't disappear.</p>
            </div>
            <div class="kbf-card">
              <div class="kbf-chip" aria-hidden="true">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/transparency.svg" alt="">
              </div>
                <h4 style="font-weight: 500;">Show Where the Money Goes</h4>
               <p>Post receipts, photos, milestones. Not because we force you to — but because campaigns that show proof raise 3x more. Transparency works.</p>
            </div>
            <div class="kbf-card">
              <div class="kbf-chip" aria-hidden="true">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/capslock-fill.svg" alt="">
              </div>
                <h4 style="font-weight: 500;">Build a Track Record</h4>
               <p>Every campaign you complete, every update you post — it all builds your profile. Future campaigns get funded faster because your reputation speaks first.</p>
            </div>
          </div>
        </div>

        <!-- ABOUT US -->
        <div class="kbf-divider"></div>
        <div id="kbf-donation" class="kbf-section kbf-reveal delay-2">
          <div class="kbf-about-grid">
            <div class="kbf-about-photo">
              <img src="https://upload.wikimedia.org/wikipedia/commons/a/a1/Battle_of_Leyte_Filipino_volunteers.jpg" alt="Filipino volunteers">
            </div>
            <div class="kbf-about-card">
              <h2 style="font-size: 1.5em; font-weight: 400;">Why we built this</h2>
              <p>We've all seen it — a family posting their GCash number on Facebook after a flood, a hospitalization, a fire. It works sometimes. But it's chaotic, untracked, and exhausting for the family asking.</p>
              <p style="margin-top:10px;">Ambag started because Filipinos don't need convincing to help each other. Bayanihan is already in our culture. We just needed to build something worthy of it.</p>
            </div>
          </div>

          <!-- Stats row -->
          <div class="kbf-about-stats">
            <div class="kbf-about-stat">
              <div class="kbf-about-stat-num">₱0</div>
              <div class="kbf-about-stat-label">Platform fee during beta</div>
            </div>
            <div class="kbf-about-stat">
              <div class="kbf-about-stat-num">48h</div>
              <div class="kbf-about-stat-label">Average time to first donation</div>
            </div>
            <div class="kbf-about-stat">
              <div class="kbf-about-stat-num">100%</div>
              <div class="kbf-about-stat-label">Funds go directly to organizer</div>
            </div>
            <div class="kbf-about-stat">
              <div class="kbf-about-stat-num">3x</div>
              <div class="kbf-about-stat-label">More raised with proof updates</div>
            </div>
          </div>

          <!-- Values row -->
          <div class="kbf-about-values">
            <div class="kbf-about-value">
              <div>
                <div class="kbf-about-value-title">Rooted in bayanihan</div>
                <div class="kbf-about-value-desc">We didn't invent community giving. We just built infrastructure worthy of it.</div>
              </div>
            </div>
            <div class="kbf-about-value">
              <div>
                <div class="kbf-about-value-title">Radical transparency</div>
                <div class="kbf-about-value-desc">Every peso tracked. Every organizer verified. No black holes.</div>
              </div>
            </div>
            <div class="kbf-about-value">
              <div>
                <div class="kbf-about-value-title">Built for Filipinos</div>
                <div class="kbf-about-value-desc">GCash, local banks, Taglish — designed for how we actually live.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATS / COMMUNITY -->
        <div class="kbf-divider"></div>
        <div id="kbf-about" class="kbf-section kbf-stat kbf-reveal delay-2">
          <div class="kbf-photo-grid" aria-hidden="true">
            <img src="<?php echo esc_url($bw_1); ?>" alt="">
            <img src="<?php echo esc_url($bw_2); ?>" alt="">
            <img src="<?php echo esc_url($bw_3); ?>" alt="">
            <img src="<?php echo esc_url($bw_4); ?>" alt="">
          </div>
          <div class="kbf-stat-content">
            <p>Designed for trust</p>
            <h3 style="font-weight: 300;">Safe & Trusted</h3>
            <p>Reviewed. Verified. Transparent.</p>
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($join_url); ?>">Join Ambag — it's free</a>
          </div>
        </div>

        <!-- FAQ -->
        <div class="kbf-divider"></div>
        <div id="kbf-faq" class="kbf-section kbf-reveal delay-3">
          <h2 style="text-align: center; margin-bottom: 40px; font-size: 1.5em; font-weight: 400;">Questions people actually ask</h2>
          <div class="kbf-faq">
            <details>
              <summary>How can I sponsor a fundraiser?</summary>
              <div class="kbf-faq-body"><div>
                <p>Browse active campaigns, choose a cause, and sponsor using the available payment options.</p>
              </div></div>
            </details>
            <details>
              <summary>Is my sponsorship tax‑deductible?</summary>
              <div class="kbf-faq-body"><div>
                <p>Tax benefits depend on organizer accreditation and local regulations. Please check with the organizer first.</p>
              </div></div>
            </details>
            <details>
              <summary>Can I sponsor in honor of someone?</summary>
              <div class="kbf-faq-body"><div>
                <p>Yes. Organizers can add dedication notes in campaign updates and acknowledgments.</p>
              </div></div>
            </details>
            <details>
              <summary>How will my sponsorship be used?</summary>
              <div class="kbf-faq-body"><div>
                <p>Organizers share budgets and progress updates so sponsors can see how funds are allocated.</p>
              </div></div>
            </details>
            <details>
              <summary>Can I set up recurring sponsorships?</summary>
              <div class="kbf-faq-body"><div>
                <p>Recurring sponsorships are planned and will be available in a future update.</p>
              </div></div>
            </details>
            <details>
              <summary>How do organizers receive the funds?</summary>
              <div class="kbf-faq-body"><div>
                <p>Funds are released to organizers based on the platform’s payout schedule and verification steps.</p>
              </div></div>
            </details>
            <details>
              <summary>What if a fundraiser looks suspicious?</summary>
              <div class="kbf-faq-body"><div>
                <p>You can report the fundraiser and our team will review it promptly.</p>
              </div></div>
            </details>
          </div>
        </div>

        <!-- FOOTER -->
        <footer class="kbf-footer kbf-reveal delay-3" style="margin-top:80px;">
          <div>
            <div class="kbf-brand" style="margin-bottom:8px;">
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="ambag" style="width:20px;height:20px;object-fit:contain;border-radius:6px;">
              <span class="kbf-brand-text" style="font-weight: 600;">ambag</span>
            </div>
            <p>Community fundraising rooted in bayanihan.</p>
            <small>© Ambag. All rights reserved.</small>
          </div>
          <div class="kbf-social">
            <a href="https://www.instagram.com/bntmtechnologiesinc/" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/instagram.svg" alt="">
            </a>
            <a href="https://www.facebook.com/bentamosabentamo" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/facebook.svg" alt="">
            </a>
            <a href="https://www.linkedin.com/company/bentamo/" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/linkedin.svg" alt="">
            </a>
          </div>
        </footer>

      </div>
    </section>
    <!-- ================== JS ================== -->
    <script>
    window.kbfScrollTo = function(id) {
        if (id === 'kbf-home') {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return false;
        }
        var target = document.getElementById(id);
        if (!target) return false;
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return false;
    };

    // FAQ smooth animation — prevent instant jump on open
    document.querySelectorAll('.kbf-faq details').forEach(function(detail) {
        var body = detail.querySelector('.kbf-faq-body');
        var summary = detail.querySelector('summary');
        if (!body || !summary) return;

        // Ensure correct initial state
        body.style.gridTemplateRows = detail.open ? '1fr' : '0fr';

        summary.addEventListener('click', function(e) {
            e.preventDefault();
            if (detail.dataset.animating === '1') return;
            detail.dataset.animating = '1';

            if (detail.open) {
                body.style.gridTemplateRows = '0fr';
                var onEnd = function() {
                    detail.open = false;
                    detail.dataset.animating = '0';
                    body.removeEventListener('transitionend', onEnd);
                };
                body.addEventListener('transitionend', onEnd);
            } else {
                detail.open = true;
                requestAnimationFrame(function(){
                    body.style.gridTemplateRows = '1fr';
                    detail.dataset.animating = '0';
                });
            }
        });
    });
    // Hamburger menu toggle
      (function() {
          var btn = document.getElementById('kbf-hamburger-btn');
          var menu = document.getElementById('kbf-mobile-menu');
          var overlay = document.getElementById('kbf-mobile-overlay');
          var icon = document.getElementById('kbf-hamburger-icon');
          if (!btn || !menu) return;
          var open = false;

        function openMenu() {
            open = true;
            menu.classList.add('kbf-menu-open');
            if (overlay) overlay.classList.add('kbf-overlay-open');
            icon.src = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/x-lg.svg';
        }

        function closeMenu() {
            open = false;
            menu.classList.remove('kbf-menu-open');
            if (overlay) overlay.classList.remove('kbf-overlay-open');
            icon.src = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/list.svg';
        }

          btn.addEventListener('click', function() {
              open ? closeMenu() : openMenu();
          });

          if (overlay) overlay.addEventListener('click', closeMenu);
          window.addEventListener('resize', function(){
              if (window.innerWidth > 900 && open) {
                  closeMenu();
              }
          });
      })();

        // Sticky navbar scroll effect
    (function() {
        var topbar = document.querySelector('.kbf-topbar');
        if (!topbar) return;
        window.addEventListener('scroll', function() {
            if (window.scrollY > 10) {
                topbar.classList.add('kbf-topbar-scrolled');
            } else {
                topbar.classList.remove('kbf-topbar-scrolled');
            }
        }, { passive: true });
    })();

    window.kbfMobileNav = function(id) {
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        var icon = document.getElementById('kbf-hamburger-icon');
        if (menu) menu.classList.remove('kbf-menu-open');
        if (overlay) overlay.classList.remove('kbf-overlay-open');
        if (icon) icon.src = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/list.svg';
        var target = document.getElementById(id);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return false;
    };
    function openMenu() {
            open = true;
            menu.classList.add('kbf-menu-open');
            if (overlay) overlay.classList.add('kbf-overlay-open');
        }

        function closeMenu() {
            open = false;
            menu.classList.remove('kbf-menu-open');
            if (overlay) overlay.classList.remove('kbf-overlay-open');
        }
    </script>
    <?php
    return ob_get_clean();
}

