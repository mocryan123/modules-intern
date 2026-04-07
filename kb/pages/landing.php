<?php
/*
 * KBF landing page template (HTML/CSS/JS).
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('bntm_kbf_landing_seo_meta')) {
    function bntm_kbf_landing_seo_meta() {
        if (empty($GLOBALS['kbf_landing_seo'])) return;
        $seo = $GLOBALS['kbf_landing_seo'];
        $title = esc_attr($seo['title']);
        $desc  = esc_attr($seo['desc']);
        $url   = esc_url($seo['url']);
        $site  = esc_attr($seo['site']);
        $logo  = esc_url($seo['logo']);
        ?>
        <meta name="description" content="<?php echo $desc; ?>">
        <meta property="og:type" content="website">
        <meta property="og:title" content="<?php echo $title; ?>">
        <meta property="og:description" content="<?php echo $desc; ?>">
        <meta property="og:url" content="<?php echo $url; ?>">
        <?php if ($logo): ?><meta property="og:image" content="<?php echo $logo; ?>"><?php endif; ?>
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo $title; ?>">
        <meta name="twitter:description" content="<?php echo $desc; ?>">
        <?php if ($logo): ?><meta name="twitter:image" content="<?php echo $logo; ?>"><?php endif; ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode($seo['schema']); ?>
        </script>
        <?php
    }
}

if (!function_exists('kbf_landing_get_urls')) {
    function kbf_landing_get_urls() {
        $site_url = home_url('/');
        return [
            'cta'   => function_exists('kbf_get_page_url') ? kbf_get_page_url('browse') : $site_url,
            'login' => function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : '#',
            'join'  => function_exists('kbf_get_page_url') ? kbf_get_page_url('signup') : $site_url,
            'page'  => function_exists('kbf_get_page_url') ? kbf_get_page_url('landing') : $site_url,
            'site'  => $site_url,
        ];
    }
}

if (!function_exists('kbf_landing_get_faq_items')) {
    function kbf_landing_get_faq_items() {
        return [
            [
                'q' => 'How can I sponsor a fundraiser?',
                'a' => 'Browse active campaigns, choose a cause, and sponsor using the available payment options.'
            ],
            [
                'q' => 'Is my sponsorship tax‑deductible?',
                'a' => 'Tax benefits depend on organizer accreditation and local regulations. Please check with the organizer first.'
            ],
            [
                'q' => 'Can I sponsor in honor of someone?',
                'a' => 'Yes. Organizers can add dedication notes in campaign updates and acknowledgments.'
            ],
            [
                'q' => 'How will my sponsorship be used?',
                'a' => 'Organizers share budgets and progress updates so sponsors can see how funds are allocated.'
            ],
            [
                'q' => 'Can I set up recurring sponsorships?',
                'a' => 'Recurring sponsorships are planned and will be available in a future update.'
            ],
            [
                'q' => 'How do organizers receive the funds?',
                'a' => 'Funds are released to organizers based on the platform’s payout schedule and verification steps.'
            ],
            [
                'q' => 'What if a fundraiser looks suspicious?',
                'a' => 'You can report the fundraiser and our team will review it promptly.'
            ],
        ];
    }
}

if (!function_exists('kbf_landing_build_schema')) {
    function kbf_landing_build_schema($site_name, $site_url, $logo_url, $faq_items) {
        $faq_entities = array_map(function($item){
            return [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['a']
                ]
            ];
        }, $faq_items);

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    'name' => $site_name,
                    'url' => $site_url,
                    'logo' => $logo_url
                ],
                [
                    '@type' => 'WebSite',
                    'name' => $site_name,
                    'url' => $site_url,
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => $site_url . '?s={search_term_string}',
                        'query-input' => 'required name=search_term_string'
                    ]
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => $faq_entities
                ]
            ]
        ];
    }
}

if (!function_exists('kbf_landing_register_seo')) {
    function kbf_landing_register_seo($seo) {
        $GLOBALS['kbf_landing_seo'] = $seo;
        if (!has_action('wp_head', 'bntm_kbf_landing_seo_meta')) {
            add_action('wp_head', 'bntm_kbf_landing_seo_meta', 1);
        }
    }
}

if (!function_exists('kbf_landing_get_image_sets')) {
    function kbf_landing_get_image_sets() {
        return [
            'urgent' => [
                'https://upload.wikimedia.org/wikipedia/commons/thumb/5/58/Elderly_woman_gazing_at_art_%28Unsplash%29.jpg/1200px-Elderly_woman_gazing_at_art_%28Unsplash%29.jpg',
                'https://upload.wikimedia.org/wikipedia/commons/1/1c/Womens_wheelchair_basketball_%28Unsplash%29.jpg',
                'https://images.unsplash.com/photo-1642059893618-22daf30e92a2?auto=format&fit=crop&fm=jpg&q=80&w=1800',
            ],
            'bw' => [
                BNTM_KBF_URL . 'assets/landing/disabled.jpg',
                BNTM_KBF_URL . 'assets/landing/stray.jpg',
                BNTM_KBF_URL . 'assets/landing/hospitalized.jpg',
                BNTM_KBF_URL . 'assets/landing/family.jpg',
            ],
        ];
    }
}

if (!function_exists('kbf_landing_render_faq')) {
    function kbf_landing_render_faq($faq_items) {
        foreach ($faq_items as $item) {
            ?>
            <details>
              <summary><?php echo esc_html($item['q']); ?></summary>
              <div class="kbf-faq-body"><div>
                <p><?php echo esc_html($item['a']); ?></p>
              </div></div>
            </details>
            <?php
        }
    }
}

function bntm_kbf_render_landing() {
    $urls = kbf_landing_get_urls();
    $cta_url = $urls['cta'];
    $login_url = $urls['login'];
    $join_url = $urls['join'];

    $site_name = 'fundora';
    $site_url  = $urls['site'];
    $page_url  = $urls['page'];
    $logo_url  = esc_url(BNTM_KBF_URL . 'assets/branding/logo.png');
    $seo_title = 'Fundora: The Filipino Crowdfunding Platform Built on Bayanihan | Transparent, Trusted, Free';
    $seo_desc  = 'Fundora is a community-powered crowdfunding platform built for Filipinos with verified campaigns, transparent fund tracking, and zero platform fees during beta. Start or support a fundraiser today.';
    $faq_items = kbf_landing_get_faq_items();
    $schema = kbf_landing_build_schema($site_name, $site_url, $logo_url, $faq_items);
    kbf_landing_register_seo([
        'title' => $seo_title,
        'desc' => $seo_desc,
        'url' => $page_url ?: $site_url,
        'site' => $site_name,
        'logo' => $logo_url,
        'schema' => $schema
    ]);

    $images = kbf_landing_get_image_sets();
    $urgent_1 = $images['urgent'][0];
    $urgent_2 = $images['urgent'][1];
    $urgent_3 = $images['urgent'][2];
    $bw_1 = $images['bw'][0];
    $bw_2 = $images['bw'][1];
    $bw_3 = $images['bw'][2];
    $bw_4 = $images['bw'][3];

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
    .kbf-container { overflow: visible; max-width: 1120px; margin: 0 auto; padding: 62px 22px 0; }
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
        padding: 12px 20px;
        min-height: 60px;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.86);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid transparent;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        margin: 0;
        width: 100%;
        box-sizing: border-box;
    }
    .kbf-topbar.kbf-topbar-scrolled {
        border-bottom-color: var(--kbf-border);
        box-shadow: 0 6px 28px rgba(15, 40, 80, 0.10);
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
        padding: 10px 18px; border-radius: 999px;
        font-weight: 500; font-size: 12.5px;
        border: 1px solid transparent;
        transition: transform .3s ease, box-shadow .3s ease, background .3s ease, filter .3s ease;
    }
    .kbf-btn.kbf-btn-primary {
        background: linear-gradient(135deg, #5ba8f5 0%, #3d8ef0 55%, #1f6fe0 100%);
        color: #ffffff;
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
        background:#ffffff;
        border-radius: 24px;
        padding: 44px;
        border: 1px solid #dfe9f7;
        box-shadow: 0 18px 40px rgba(15, 40, 80, 0.10);
        overflow: hidden;
    }
    .kbf-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url('<?php echo esc_url(BNTM_KBF_URL . 'assets/hero.jpg'); ?>');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0.42;
        pointer-events: none;
    }
    .kbf-hero::after{
        content:'';
        position:absolute;
        inset:0;
        background:
            linear-gradient(120deg, rgba(231,241,255,0.72) 0%, rgba(255,255,255,0.58) 45%, rgba(231,241,255,0.45) 100%),
            radial-gradient(880px 680px at -8% 110%, rgba(111,182,255,0.22) 0%, rgba(111,182,255,0.08) 55%, transparent 100%),
            radial-gradient(860px 640px at 108% -10%, rgba(111,182,255,0.20) 0%, rgba(111,182,255,0.06) 55%, transparent 100%);
        pointer-events:none;
    }
    .kbf-hero-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        gap: 40px;
        padding: 48px 0 32px;
        position: relative;
        z-index: 1;
    }
    /* Premium motion (subtle, non-slop) */
    .kbf-hero .kbf-hero-left,
    .kbf-hero .kbf-hero-right{
        opacity:1;
        transform:translateY(0);
    }
    .kbf-page-loaded .kbf-hero .kbf-hero-left{animation:kbfHeroRise .7s cubic-bezier(.2,.65,.3,1) .05s both;}
    .kbf-page-loaded .kbf-hero .kbf-hero-right{animation:kbfHeroRise .7s cubic-bezier(.2,.65,.3,1) .15s both;}
    @keyframes kbfHeroRise{
        from{opacity:0; transform:translateY(12px);}
        to{opacity:1; transform:translateY(0);}
    }
    /* ─── Scroll reveal system ─── */
    .kbf-reveal {
        opacity: 0;
        transform: translateY(22px);
        transition:
            opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1),
            transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: opacity, transform;
    }
    .kbf-reveal.is-in {
        opacity: 1;
        transform: translateY(0);
    }

    /* Delay variants for staggered sibling reveals */
    .kbf-reveal.delay-1 { transition-delay: 0.06s; }
    .kbf-reveal.delay-2 { transition-delay: 0.13s; }
    .kbf-reveal.delay-3 { transition-delay: 0.20s; }

    /* Child stagger — direct children of a revealed section */
    .kbf-reveal.is-in > * {
        opacity: 0;
        transform: translateY(14px);
        animation: none;
    }
    /* Stagger kicks in after parent resolves */
    .kbf-reveal.is-in > *:nth-child(1) { animation: kbfChildIn 0.45s cubic-bezier(0.16, 1, 0.3, 1) 0.08s both; }
    .kbf-reveal.is-in > *:nth-child(2) { animation: kbfChildIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.18s both; }
    .kbf-reveal.is-in > *:nth-child(3) { animation: kbfChildIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.28s both; }
    .kbf-reveal.is-in > *:nth-child(4) { animation: kbfChildIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.38s both; }
    .kbf-reveal.is-in > *:nth-child(n+5) { animation: kbfChildIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.46s both; }

    @keyframes kbfChildIn {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Heading clip reveal — apply .kbf-heading-reveal to h1/h2 */
    .kbf-heading-reveal {
        clip-path: inset(0 0 100% 0);
        transition: clip-path 0.65s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .kbf-reveal.is-in .kbf-heading-reveal,
    .is-in.kbf-heading-reveal {
        clip-path: inset(0 0 0% 0);
    }

    /* Cards in feature grid — cascade with y offset */
    .kbf-reveal.is-in .kbf-card:nth-child(1) { animation: kbfCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.08s both; }
    .kbf-reveal.is-in .kbf-card:nth-child(2) { animation: kbfCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.18s both; }
    .kbf-reveal.is-in .kbf-card:nth-child(3) { animation: kbfCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.28s both; }
    .kbf-reveal.is-in .kbf-card:nth-child(4) { animation: kbfCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.38s both; }
    .kbf-reveal.is-in .kbf-card:nth-child(5) { animation: kbfCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.48s both; }

    @keyframes kbfCardIn {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Stat numbers — count-up is handled in JS; just fade the block */
    .kbf-about-stat {
        opacity: 0;
        transform: translateY(10px);
        transition:
            opacity 0.55s cubic-bezier(0.16, 1, 0.3, 1),
            transform 0.55s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .kbf-stats-revealed .kbf-about-stat:nth-child(1) { opacity: 1; transform: none; transition-delay: 0.10s; }
    .kbf-stats-revealed .kbf-about-stat:nth-child(2) { opacity: 1; transform: none; transition-delay: 0.20s; }
    .kbf-stats-revealed .kbf-about-stat:nth-child(3) { opacity: 1; transform: none; transition-delay: 0.30s; }
    .kbf-stats-revealed .kbf-about-stat:nth-child(4) { opacity: 1; transform: none; transition-delay: 0.40s; }
    @media (prefers-reduced-motion: reduce) {
        .kbf-reveal,
        .kbf-reveal.is-in > *,
        .kbf-card,
        .kbf-about-stat,
        .kbf-heading-reveal {
            transition: none !important;
            animation: none !important;
            opacity: 1 !important;
            transform: none !important;
            clip-path: none !important;
        }
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
        font-size: clamp(40px, 5.6vw, 64px);
        font-weight: 700; line-height: 1.04;
        color: #0d1a2e; letter-spacing: -1.5px; margin: 0;
    }
    .kbf-hero-highlight {
        display: inline-block;
        background: #6fb6ff; color: #0f2a52;
        border-radius: 8px; padding: 2px 14px; font-style: normal;
    }
    .kbf-hero-desc {
        font-size: 14.5px; font-weight: 400;
        color: var(--kbf-muted); line-height: 1.7; margin: 0;
    }
    .kbf-hero-sub {
        font-size: 12.5px;
        color: #6a7b92;
        letter-spacing: 0.02em;
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
        position: absolute; border-radius: 24px; overflow: hidden;
        background: linear-gradient(180deg, #f7f9fc 0%, #eef2f7 100%);
        border: 1px solid #e1e7f0;
        box-shadow:
            0 30px 70px rgba(15, 23, 42, 0.12),
            0 12px 28px rgba(15, 23, 42, 0.08),
            0 2px 6px rgba(15, 23, 42, 0.06),
            inset 0 1px 0 rgba(255,255,255,0.9);
    }
    .kbf-pcard::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.65) 0%, rgba(255,255,255,0) 46%);
        pointer-events: none;
    }
    .kbf-pcard::after {
        content: '';
        position: absolute;
        inset: 10px 10px auto 10px;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.5), transparent);
        opacity: 0.7;
        pointer-events: none;
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
        width: 100%; height: calc(100% - 50px);
        display: flex; align-items: center; justify-content: center;
        position: relative;
        background: #e9eef6;
    }
    .kbf-pcard-img::after {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(180deg, rgba(255,255,255,0.45), rgba(255,255,255,0.1)),
            radial-gradient(240px 160px at 18% 12%, rgba(255,255,255,0.6), transparent 62%);
        pointer-events: none;
    }
    .kbf-pimg-1 { background: #e9eef6; }
    .kbf-pimg-2 { background: #e9eef6; }
    .kbf-pimg-3 { background: #e9eef6; }
    .kbf-pimg-4 { background: #e9eef6; }
    .kbf-pcard-img img{
        width:100%;
        height:100%;
        object-fit:cover;
        display:block;
        filter: grayscale(100%) contrast(0.95);
    }
    .kbf-pcard-bar {
        padding: 7px 12px; background: linear-gradient(180deg, #f9fafb 0%, #eef2f7 100%);
        display: flex; align-items: center; gap: 8px;
        border-top: 1px solid #e1e7f0; height: 50px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.85);
    }
    .kbf-pcard-av {
        width: 22px; height: 22px; border-radius: 50%;
        background: linear-gradient(180deg, #ffffff 0%, #eef2f7 100%);
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; flex-shrink: 0;
        border: 1px solid #dde3ee;
        box-shadow: 0 4px 10px rgba(15, 40, 80, 0.12);
    }
    .kbf-pcard-av img {
        width: 12px;
        height: 12px;
        display: block;
        filter: grayscale(100%) brightness(0.6);
    }
    .kbf-pcard-name { font-size: 9px; font-weight: 600; color: #0d1a2e; line-height: 1.3; }
    .kbf-pcard-sub  { font-size: 8px; color: #8aa0b8; }
    .kbf-pcard-play {
        margin-left: auto; width: 20px; height: 20px;
        background: linear-gradient(180deg, #ffffff 0%, #eef2f7 100%);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 8px; color: #2a6aad; flex-shrink: 0;
        border: 1px solid #dde3ee;
        box-shadow: 0 4px 10px rgba(15, 40, 80, 0.12);
    }
    .kbf-pcard-play img {
        width: 8px;
        height: 8px;
        display: block;
        filter: grayscale(100%) brightness(0.55);
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
    .kbf-section h2 { font-size: 24px; margin: 0 0 8px; letter-spacing: -0.2px; }
    .kbf-section p { color: var(--kbf-muted); margin: 0; font-size: 13.5px; line-height: 1.7; }
    .kbf-section p + p { margin-top: 10px; }
    .kbf-section .kbf-lead { font-size: 14px; line-height: 1.7; margin-top: 8px; }
    .kbf-article-list {
        margin: 16px 0 0;
        padding: 18px;
        list-style: none;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 22px;
        background: transparent;
        border: none;
        border-radius: 0;
        box-shadow: none;
        padding-left: 0;
        padding-right: 0;
    }
    .kbf-article-list li {
        font-size: 12.8px;
        color: #3a4b63;
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 8px 10px;
        border-radius: 12px;
        background: #f7fbff;
        border: 1px solid #e8f0fb;
    }
    .kbf-article-list li span {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: linear-gradient(135deg, #7ec4ff 0%, #3d8ef0 70%);
        box-shadow: 0 4px 10px rgba(61, 142, 240, 0.25);
        flex-shrink: 0;
        position: relative;
    }
    .kbf-article-list li span::after {
        content: '';
        position: absolute;
        inset: 5px;
        border-radius: 50%;
        background: #ffffff;
        opacity: 0.9;
    }
    .kbf-two-col { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin-top: 18px; }
    .kbf-list { margin: 12px 0 0; padding: 0; color: var(--kbf-muted); font-size: 14px; line-height: 1.65; list-style: none; }
    .kbf-list li {
        position: relative;
        padding-left: 22px;
        margin: 8px 0;
        color: #46566b;
    }
    .kbf-list li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 7px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: linear-gradient(135deg, #7ec4ff 0%, #3d8ef0 80%);
        box-shadow: 0 4px 10px rgba(61, 142, 240, 0.25);
    }
    .kbf-compare-table { margin-top: 18px; border: 1px solid var(--kbf-border); border-radius: 18px; overflow: hidden; background: #fff; box-shadow: var(--kbf-shadow); }
    .kbf-compare-row { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 10px; padding: 12px 16px; border-bottom: 1px solid var(--kbf-border); font-size: 12.5px; }
    .kbf-compare-row strong { color: #0d1a2e; font-weight: 600; }
    .kbf-compare-row:last-child { border-bottom: none; }
    .kbf-compare-head { background: #f3f7ff; font-weight: 600; color: #1a3a66; }
    .kbf-compare-row:nth-child(even):not(.kbf-compare-head) { background: #fbfdff; }

    .kbf-feature-grid { margin-top: 24px; display: grid; grid-template-columns: repeat(6, 1fr); gap: 18px; }
    .kbf-feature-grid .kbf-card { grid-column: span 2; }
    .kbf-feature-grid .kbf-card:nth-child(4),
    .kbf-feature-grid .kbf-card:nth-child(5) { grid-column: span 3; }
    .kbf-feature-grid.kbf-feature-grid--two { grid-template-columns: repeat(2, 1fr); }
    .kbf-feature-grid.kbf-feature-grid--two .kbf-card { grid-column: auto; min-height: 140px; }
    .kbf-feature-grid.kbf-feature-grid--two .kbf-card:nth-child(4),
    .kbf-feature-grid.kbf-feature-grid--two .kbf-card:nth-child(5) { grid-column: auto; }
    .kbf-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #e0e9f7;
        border-radius: 18px;
        padding: 16px;
        box-shadow: 0 10px 24px rgba(15, 40, 80, 0.08);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        position: relative;
        overflow: hidden;
    }
    .kbf-card::before { display: none; }
    .kbf-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px rgba(15, 40, 80, 0.12);
        border-color: #cfe0f6;
    }
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
    .kbf-card::after,
    .kbf-card::before { pointer-events: none; }
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

    /* Trust strip */
    .kbf-trust-strip {
        margin-top: 18px;
        padding: 10px 14px;
        border-radius: 999px;
        border: 1px solid #e3ecf7;
        background: #fbfdff;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 12px;
        color: #1a3a66;
        box-shadow: 0 10px 20px rgba(15, 40, 80, 0.06);
    }
    .kbf-trust-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #f3f7ff;
        border: 1px solid #e1ebf7;
        font-weight: 600;
    }

    /* Card variants for visual variety */
    .kbf-card--soft {
        background: linear-gradient(180deg, #ffffff 0%, #f4f9ff 100%);
        border-color: #e1ebf7;
    }
    .kbf-card--glass {
        background: linear-gradient(180deg, rgba(255,255,255,0.92) 0%, rgba(247,250,255,0.85) 100%);
        border-color: rgba(210,226,247,0.9);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .kbf-card--outline {
        background: #ffffff;
        border: 1px solid #d9e6f7;
        box-shadow: 0 6px 18px rgba(15, 40, 80, 0.06);
    }
    .kbf-card--split {
        background: linear-gradient(180deg, #f7fbff 0%, #ffffff 65%);
        border-color: #dfe9f7;
    }
    .kbf-card--tint {
        background: linear-gradient(180deg, #ffffff 0%, #f1f7ff 100%);
        border-color: #dbe7f7;
    }

    /* Shine hover effect (targeted) */
    .kbf-card--shine {
        position: relative;
        overflow: hidden;
    }
    .kbf-card--shine::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, transparent 0%, rgba(111,182,255,0.18) 40%, rgba(111,182,255,0.4) 52%, rgba(111,182,255,0.18) 64%, transparent 100%);
        transform: translateX(-120%) skewX(-12deg);
        opacity: 0;
        transition: opacity .55s ease, transform .55s ease;
        pointer-events: none;
    }
    .kbf-card--shine:hover::after {
        opacity: 1;
        transform: translateX(150%) skewX(-12deg);
    }

    .kbf-about-values .kbf-about-value {
        background: linear-gradient(180deg, #ffffff 0%, #f7faff 100%);
        border-color: #e3ecf7;
        box-shadow: var(--kbf-shadow);
        position: relative;
        overflow: hidden;
    }
    .kbf-about-values .kbf-about-value::after {
        content: '';
        position: absolute;
        width: 120px;
        height: 120px;
        right: -60px;
        top: -60px;
        background: radial-gradient(circle at center, rgba(111,182,255,0.35), rgba(111,182,255,0) 70%);
        opacity: 0.5;
        pointer-events: none;
    }

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
    .kbf-about-photo img { width: 100%; height: 100%; object-fit: cover; display: block; min-height: 0; filter: grayscale(1); }

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
        animation: kbfFloatPic 6s ease-in-out infinite;
        will-change: transform;
        transition: transform .2s ease, box-shadow .2s ease;
        translate: 0 0;
    }
    .kbf-stat .kbf-photo-grid img:nth-child(1) { animation-delay: 0s; }
    .kbf-stat .kbf-photo-grid img:nth-child(2) { animation-delay: 1s; }
    .kbf-stat .kbf-photo-grid img:nth-child(3) { animation-delay: 2s; }
    .kbf-stat .kbf-photo-grid img:nth-child(4) { animation-delay: 3s; }
    @keyframes kbfFloatPic {
        0%, 100% { translate: 0 0; }
        50% { translate: 0 -8px; }
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
        margin-top: 44px; 
        background: #0c0f14; 
        color: #b9c0cc;
        margin-bottom:40px;
        border-radius: 22px; padding: 20px 22px;
        display: grid; grid-template-columns: 1fr auto;
        align-items: center; gap: 14px;
    }
    .kbf-footer-left{ display:flex; flex-direction:column; gap:6px; }
    .kbf-footer h5 { margin: 0 0 6px; color: #fff; }
    .kbf-footer p { font-size: 12px; line-height: 1.6; }
    .kbf-footer small { color: #8590a6; font-size: 11.5px; }
    .kbf-footer .kbf-footer-links{
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      margin:0;
    }
    .kbf-footer .kbf-footer-links a{
      color:#cbd5f5;
      font-size:12px;
      text-decoration:none;
    }
    .kbf-footer .kbf-footer-links a:hover{
      color:#ffffff;
    }
    .kbf-footer .kbf-social { display: flex; gap: 8px; }
    .kbf-footer .kbf-social a {
        width: 32px; height: 32px; border-radius: 50%;
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
        .kbf-two-col { grid-template-columns: 1fr; }
        .kbf-article-list { grid-template-columns: 1fr; }
        .kbf-about-photo { height: 320px; }
        .kbf-stat .kbf-photo-grid { display: none; }
        .kbf-stat h3 { font-size: 56px; }
        .kbf-divider { margin-top: 56px; margin-bottom: 56px; }
        .kbf-footer { grid-template-columns: 1fr; text-align: left; margin: 18px auto; }
        .kbf-footer .kbf-social { justify-content: flex-start; }
        .kbf-footer .kbf-brand { justify-content: flex-start; }
        .kbf-trust-strip { border-radius: 16px; }
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
        .kbf-footer { grid-template-columns: 1fr; text-align: left; margin: 18px auto; }
        .kbf-footer .kbf-social { justify-content: flex-start; }
        .kbf-footer .kbf-brand { justify-content: flex-start; }
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
        .kbf-compare-row { grid-template-columns: 1fr; gap: 6px; }
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
        <div class="kbf-topbar">
          <div class="kbf-topbar-left">
            <div class="kbf-brand">
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logobanner.png'); ?>" alt="fundora" style="width:auto;height:25px;object-fit:contain;border-radius:6px;">
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
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora" style="width:24px;height:24px;object-fit:contain;border-radius:6px;">
              <span class="kbf-brand-text" style="font-weight:800;">fundora</span>
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
        <div id="kbf-home" class="kbf-hero" style="margin-top: 14px; margin-bottom: 90px;">
          <div class="kbf-hero-inner">

            <!-- Left: Text -->
            <div class="kbf-hero-left">
              <div class="kbf-eyebrow">
                <span class="kbf-eyebrow-dot"></span>
                Filipino Crowdfunding Platform
              </div>

              <h1 class="kbf-hero-heading" style="font-weight: 600;">
                Fundora: <br>
                Start a fund, <br>
                Change a Life.
              </h1>

              <p class="kbf-hero-desc">
                Why Filipinos are moving from Social Media donation posts to a platform designed for trust, transparency, and real accountability.
              </p>
              <div class="kbf-hero-sub">Crowdfunding Philippines • Online Fundraising • Zero Fees (Beta)</div>


              <a class="kbf-btn kbf-btn-primary kbf-hero-cta-btn" href="<?php echo esc_url($cta_url); ?>">
                Start Supporting on Fundora
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/arrow-up-right.svg" alt="">

              </a>
            </div>
            
            <!-- Right: Floating phone cards -->
            <div class="kbf-hero-right" aria-hidden="true">
              <div class="kbf-cards-wrap">
                <div class="kbf-pcard kbf-pcard-tl">
                  <div class="kbf-pcard-img kbf-pimg-1">
                    <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/landing/patient.jpg'); ?>" alt="Patient support">
                  </div>
                  <div class="kbf-pcard-bar">
                    <div class="kbf-pcard-av"><img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt=""></div>
                    <div><div class="kbf-pcard-name">Yourself</div><div class="kbf-pcard-sub">Health</div></div>
                    <div class="kbf-pcard-play"><img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/play-fill.svg" alt=""></div>
                  </div>
                </div>
                <div class="kbf-pcard kbf-pcard-main">
                  <div class="kbf-pcard-img kbf-pimg-2">
                    <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/landing/basketball.jpg'); ?>" alt="Basketball community">
                  </div>
                  <div class="kbf-pcard-bar">
                    <div class="kbf-pcard-av"><img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt=""></div>
                    <div><div class="kbf-pcard-name">Charity or Events</div><div class="kbf-pcard-sub">Community</div></div>
                    <div class="kbf-pcard-play"><img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/play-fill.svg" alt=""></div>
                  </div>
                </div>
                <div class="kbf-pcard kbf-pcard-br">
                  <div class="kbf-pcard-img kbf-pimg-4">
                    <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/landing/graduation.jpg'); ?>" alt="Graduation moment">
                  </div>
                  <div class="kbf-pcard-bar">
                    <div class="kbf-pcard-av"><img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt=""></div>
                    <div><div class="kbf-pcard-name">Someone Else</div><div class="kbf-pcard-sub">Protected</div></div>
                    <div class="kbf-pcard-play"><img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/play-fill.svg" alt=""></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- IN THIS ARTICLE -->
        <div class="kbf-section delay-1" style="margin-top: 60px;">
          <h2 style="font-size: 1.4em; font-weight: 500;">Overview</h2>
          <ul class="kbf-article-list">
            <li><span></span>What Is Fundora?</li>
            <li><span></span>The Problem with Fundraising on Social Media</li>
            <li><span></span>How Fundora Works</li>
            <li><span></span>Key Features That Build Trust</li>
            <li><span></span>Fundora vs. Social Media Fundraising</li>
            <li><span></span>Who Should Use Fundora?</li>
            <li><span></span>Free During Beta — Why Now Is the Right Time</li>
            <li><span></span>The Future of Filipino Crowdfunding</li>
          </ul>
        </div>

        <!-- FEATURES -->
        <div id="kbf-how" class="kbf-section kbf-reveal delay-1" style="margin-top: 80px;">
          <h2 style="font-size: 1.5em; font-weight: 400;">Key features that build trust</h2>
          <p class="kbf-lead">The biggest barrier to online fundraising in the Philippines is not generosity. It is trust. Fundora is built so every campaign is verifiable and every organizer is accountable.</p>
          <div class="kbf-feature-grid">
            <div class="kbf-card kbf-card--soft">
                <div class="kbf-chip" aria-hidden="true">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/book-half.svg" alt="">
                </div>
               <h4 style="font-weight: 500;">Verified account profiles</h4>
               <p>Anyone raising funds completes ID verification before going live. A visible checkmark builds instant donor confidence.</p>
            </div>
            <div class="kbf-card kbf-card--glass">
              <div class="kbf-chip" aria-hidden="true">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/megaphone-fill.svg" alt="">
              </div>
                <h4 style="font-weight: 500;">Transparent fund tracking</h4>
               <p>Every peso is logged. Organizers post receipts, photos, and spending breakdowns that donors can view anytime.</p>
            </div>
            <div class="kbf-card kbf-card--outline">
              <div class="kbf-chip" aria-hidden="true">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/hand-thumbs-up-fill.svg" alt="">
              </div>
                <h4 style="font-weight: 500;">Public update log</h4>
               <p>Updates are timestamped on the campaign page so supporters see progress, receipts, and outcomes in one place.</p>
            </div>
            <div class="kbf-card kbf-card--split">
              <div class="kbf-chip" aria-hidden="true">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/transparency.svg" alt="">
              </div>
                <h4 style="font-weight: 500;">Campaign reporting system</h4>
               <p>Suspicious activity can be flagged directly. Reports are reviewed by the Fundora team to protect donors.</p>
            </div>
            <div class="kbf-card kbf-card--tint">
              <div class="kbf-chip" aria-hidden="true">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/capslock-fill.svg" alt="">
              </div>
                <h4 style="font-weight: 500;">Credibility index</h4>
               <p>Organizers build a visible track record across campaigns that makes future fundraising faster and more trusted.</p>
            </div>
          </div>
        </div>

                  <div class="kbf-section" style="margin-top:18px;">
            <h2 style="font-size: 1.4em; font-weight: 500;">How Fundora works</h2>
            <div class="kbf-two-col">
              <div class="kbf-card kbf-card--soft kbf-card--shine">
                <h4 style="font-weight: 600; margin: 0 0 6px;">For Anyone Starting a Campaign</h4>
                <ul class="kbf-list">
                  <li>Create a campaign for yourself, someone else, or a cause.</li>
                  <li>Share your link to group chats and social media.</li>
                  <li>Post updates with receipts and milestones.</li>
                  <li>Receive funds after verification.</li>
                </ul>
              </div>
              <div class="kbf-card kbf-card--glass kbf-card--shine">
                <h4 style="font-weight: 600; margin: 0 0 6px;">For donors and supporters</h4>
                <ul class="kbf-list">
                  <li>Browse campaigns by cause, location, or urgency.</li>
                  <li>Check verified status, updates, and progress.</li>
                  <li>Donate using GCash or bank transfer.</li>
                  <li>Get confirmation and follow updates.</li>
                </ul>
              </div>
            </div>
          </div>

        <!-- ABOUT US -->
        <div class="kbf-divider"></div>
        <div id="kbf-donation" class="kbf-section kbf-reveal delay-2">
          <div class="kbf-about-grid">
            <div class="kbf-about-photo">
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/landing/bayanihan.jpg'); ?>" alt="Bayanihan">
            </div>
            <div class="kbf-about-card">
              <h2 style="font-size: 1.5em; font-weight: 500;">What is Fundora?</h2>
              <p>Fundora is a community-powered crowdfunding platform built for Filipino families, organizations, and communities. It supports local payment methods, a community-first experience, and the cultural value of bayanihan.</p>
              <p style="margin-top:10px;">Instead of informal posts, organizers get a dedicated campaign page with progress tracking, proof uploads, and an update log. Donors get confidence that their money is reaching the right person, for the right reason.</p>
            </div>
          </div>

          <div class="kbf-about-card" style="margin-top:18px;">
            <h2 style="font-size: 1.4em; font-weight: 500;">The problem with fundraising on Social Media</h2>
            <p>Every Filipino has seen it: a relative posts their GCash number after a hospitalization. A neighbor shares a donation link after a house fire. The intention is real. The response is generous. But the system is broken.</p>
            <p style="margin-top:10px;">There is no way to confirm how much was raised or whether help arrived. Posts get buried. Families keep waiting. This is not a generosity problem. It is an infrastructure problem — and that is exactly what Fundora was built to solve.</p>
          </div>



          <div class="kbf-section" style="margin-top:18px;">
            <h2 style="font-size: 1.4em; font-weight: 500;">Fundora vs. Social Media Fundraising</h2>
            <p>Social media was built for connection. Fundora was built for community fundraising in the Philippines.</p>
            <div class="kbf-compare-table" style="text-align:left;">
              <div class="kbf-compare-row kbf-compare-head">
                <div>Factor</div>
                <div>Social Media donation posts</div>
                <div>Fundora</div>
              </div>
              <div class="kbf-compare-row">
                <strong>Campaign visibility</strong>
                <div>Posts get buried within days</div>
                <div>Permanent, shareable campaign page</div>
              </div>
              <div class="kbf-compare-row">
                <strong>Funds tracking</strong>
                <div>Manual tracking and receipts</div>
                <div>Automatic progress tracking and logs</div>
              </div>
              <div class="kbf-compare-row">
                <strong>Account verification</strong>
                <div>None</div>
                <div>ID-verified with credibility index</div>
              </div>
              <div class="kbf-compare-row">
                <strong>Update history</strong>
                <div>Scattered across posts</div>
                <div>Timestamped update log</div>
              </div>
              <div class="kbf-compare-row">
                <strong>Platform fee</strong>
                <div>Free</div>
                <div>Free during beta</div>
              </div>
            </div>
          </div>

          <!-- Stats row -->
          <div class="kbf-about-stats">
            <div class="kbf-about-stat">
              <div class="kbf-about-stat-num">₱0</div>
              <div class="kbf-about-stat-label">Platform fee during beta — 100% to the person raising funds</div>
            </div>
            <div class="kbf-about-stat">
              <div class="kbf-about-stat-num">3×</div>
              <div class="kbf-about-stat-label">More raised with proof updates</div>
            </div>
            <div class="kbf-about-stat">
              <div class="kbf-about-stat-num">48h</div>
              <div class="kbf-about-stat-label">Average time to first donation</div>
            </div>
            <div class="kbf-about-stat">
              <div class="kbf-about-stat-num">100%</div>
              <div class="kbf-about-stat-label">Donations go directly to the person raising funds</div>
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
                <div class="kbf-about-value-desc">GCash, local banks, community-first — designed for how we actually live.</div>
              </div>
            </div>
          </div>
        </div>

        

        <!-- WHO SHOULD USE FUNDORA -->
        <div class="kbf-divider"></div>
        <div class="kbf-section kbf-stat kbf-reveal delay-2">
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
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($join_url); ?>">Join Fundora — it's free</a>
          </div>
        </div>

        <!-- WHO SHOULD USE FUNDORA -->
        <div class="kbf-divider"></div>
        <div class="kbf-section kbf-reveal delay-2">
          <h2 style="font-size: 1.5em; font-weight: 400;">Who should use Fundora?</h2>
          <p class="kbf-lead">Fundora is built for anyone who needs to raise money from a Filipino community — and for donors who want confidence before giving.</p>
          <div class="kbf-feature-grid kbf-feature-grid--two" style="margin-top:18px;">
            <div class="kbf-card kbf-card--outline">
              <h4 style="font-weight: 600;">Families facing medical or emergency costs</h4>
              <p>Hospital bills, rehabilitation, and funeral expenses deserve a dignified, organized way to get support.</p>
            </div>
            <div class="kbf-card kbf-card--soft">
              <h4 style="font-weight: 600;">School organizations and community groups</h4>
              <p>Raise funds for events, scholarships, and barangay projects with full transparency for members.</p>
            </div>
            <div class="kbf-card kbf-card--glass">
              <h4 style="font-weight: 600;">NGOs and social enterprises</h4>
              <p>Verified campaigns and public updates add the credibility informal posts cannot provide.</p>
            </div>
            <div class="kbf-card kbf-card--tint">
              <h4 style="font-weight: 600;">OFWs supporting families back home</h4>
              <p>Verification gives confidence that support reaches the right people for the right reason.</p>
            </div>
          </div>
        </div>

        <!-- FAQ -->
        <div class="kbf-divider"></div>
        <div id="kbf-faq" class="kbf-section kbf-reveal delay-3">
          <h2 style="text-align: center; margin-bottom: 40px; font-size: 1.5em; font-weight: 400;">Questions people actually ask</h2>
          <div class="kbf-faq">
            <?php kbf_landing_render_faq($faq_items); ?>
          </div>
        </div>

        <!-- FOOTER -->
        <footer class="kbf-footer kbf-reveal delay-3" style="margin-top:80px;">
          <div class="kbf-footer-left">
            <div class="kbf-brand" style="margin-bottom:8px;">
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora" style="width:20px;height:20px;object-fit:contain;border-radius:6px;">
              <span class="kbf-brand-text" style="font-weight: 600;">fundora</span>
            </div>
            <p>Community fundraising rooted in bayanihan.</p>
            <div class="kbf-footer-links">
              <a href="<?php echo esc_url(kbf_get_page_url('privacy')); ?>">Privacy Policy</a>
              <a href="<?php echo esc_url(kbf_get_page_url('terms')); ?>">Terms of Service</a>
              <a href="<?php echo esc_url(kbf_get_page_url('refund')); ?>">Refund Policy</a>
            </div>
            <small>© fundora. All rights reserved.</small>
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
    (function () {
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        window.scrollTo(0, 0);
        // Page load: mark immediately so hero animations fire
        requestAnimationFrame(function () {
            document.documentElement.classList.add('kbf-page-loaded');
        });

        if (!('IntersectionObserver' in window)) {
            // Fallback: reveal everything
            document.querySelectorAll('.kbf-reveal').forEach(function (el) {
                el.classList.add('is-in');
            });
            document.querySelectorAll('.kbf-about-stats').forEach(function (el) {
                el.classList.add('kbf-stats-revealed');
            });
            return;
        }

        // ── Section reveals ──────────────────────────────────────────
        var revealIO = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-in');
                revealIO.unobserve(entry.target);
            });
        }, {
            rootMargin: '0px 0px -8% 0px',
            threshold: 0.07
        });

        document.querySelectorAll(
            '.kbf-section, .kbf-urgent-grid, .kbf-feature-grid, .kbf-faq, .kbf-stat, .kbf-footer'
        ).forEach(function (el) {
            if (!el.classList.contains('kbf-reveal')) {
                el.classList.add('kbf-reveal');
            }
            revealIO.observe(el);
        });

        // ── Stat number count-up ─────────────────────────────────────
        var statsIO = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('kbf-stats-revealed');
                animateStatNumbers(entry.target);
                statsIO.unobserve(entry.target);
            });
        }, { threshold: 0.3 });

        document.querySelectorAll('.kbf-about-stats').forEach(function (el) {
            statsIO.observe(el);
        });

        function animateStatNumbers(container) {
            container.querySelectorAll('.kbf-about-stat-num').forEach(function (el, i) {
                var raw = el.textContent.trim();
                var prefix = raw.match(/^[^\\d]*/)[0];
                var suffix = raw.match(/[^\\d]*$/)[0];
                var num    = parseFloat(raw.replace(/[^\\d.]/g, '')) || 0;

                if (num === 0) return;

                var delay  = 60 * i;
                var dur    = 700;
                var start  = null;

                setTimeout(function () {
                    requestAnimationFrame(function tick(ts) {
                        if (!start) start = ts;
                        var p = Math.min((ts - start) / dur, 1);
                        var eased = p === 1 ? 1 : 1 - Math.pow(2, -10 * p);
                        var val = eased * num;
                        el.textContent = prefix + (Number.isInteger(num) ? Math.round(val) : val.toFixed(1)) + suffix;
                        if (p < 1) requestAnimationFrame(tick);
                    });
                }, delay);
            });
        }
    })();
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



