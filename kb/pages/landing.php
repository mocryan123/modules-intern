<?php
/*
 * KBF landing page template (HTML/CSS/JS).
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('bntm_kbf_landing_seo_meta')) {
    /**
     * @function  bntm_kbf_landing_seo_meta
     * @purpose   Injects SEO meta tags into the <head> from global state.
     * @used-by   add_action('wp_head', 'bntm_kbf_landing_seo_meta', 1)
     * @calls     None
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
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
    /**
     * @function  kbf_landing_get_urls
     * @purpose   Retrieves URLs for landing page navigation and actions.
     * @used-by   bntm_kbf_render_landing
     * @calls     kbf_get_page_url, home_url
     * @params    none
     * @returns   array<string, string> Map of URL keys to absolute URLs
     * @status    ACTIVE
     */
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
    /**
     * @function  kbf_landing_get_faq_items
     * @purpose   Returns the default FAQ items for the landing page.
     * @used-by   kbf_landing_build_schema, kbf_landing_render_faq
     * @calls     None
     * @params    none
     * @returns   array<int, array<string, string>> List of question/answer pairs
     * @status    ACTIVE
     */
    function kbf_landing_get_faq_items() {
        return [
            [
                'q' => 'How can I sponsor a fundraiser?',
                'a' => 'Browse active campaigns, choose a cause, and sponsor using the available payment options.'
            ],
            [
                'q' => 'Is my sponsorship tax-deductible?',
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
                'a' => 'Funds are released to organizers based on the platform\'s payout schedule and verification steps.'
            ],
            [
                'q' => 'What if a fundraiser looks suspicious?',
                'a' => 'You can report the fundraiser and our team will review it promptly.'
            ],
        ];
    }
}

if (!function_exists('kbf_landing_build_schema')) {
    /**
     * @function  kbf_landing_build_schema
     * @purpose   Builds the JSON-LD schema array for landing page SEO.
     * @used-by   bntm_kbf_render_landing
     * @calls     None
     * @params    string $site_name, string $site_url, string $logo_url, array $faq_items
     * @returns   array<string, mixed> Schema.org compatible array
     * @status    ACTIVE
     */
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
    /**
     * @function  kbf_landing_register_seo
     * @purpose   Registers SEO data globally and hooks meta output.
     * @used-by   bntm_kbf_render_landing
     * @calls     add_action, has_action
     * @params    array<string, mixed> $seo
     * @returns   void
     * @status    ACTIVE
     */
    function kbf_landing_register_seo($seo) {
        $GLOBALS['kbf_landing_seo'] = $seo;
        if (!has_action('wp_head', 'bntm_kbf_landing_seo_meta')) {
            add_action('wp_head', 'bntm_kbf_landing_seo_meta', 1);
        }
    }
}

if (!function_exists('kbf_landing_get_image_sets')) {
    /**
     * @function  kbf_landing_get_image_sets
     * @purpose   Returns image sets used in hero and gallery sections.
     * @used-by   bntm_kbf_render_landing
     * @calls     None
     * @params    none
     * @returns   array<string, array<int, string>> Nested array of image URLs
     * @status    ACTIVE
     */
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
    /**
     * @function  kbf_landing_render_faq
     * @purpose   Renders the FAQ HTML section.
     * @used-by   bntm_kbf_render_landing (inline PHP call)
     * @calls     None
     * @params    array<int, array<string, string>> $faq_items
     * @returns   void
     * @status    ACTIVE
     */
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

/**
 * @function  bntm_kbf_render_landing
 * @purpose   Main entry point — renders entire landing page HTML.
 * @used-by   WordPress shortcode [kbf_landing] (registered in includes/shortcodes.php)
 * @calls     kbf_landing_get_urls, kbf_landing_get_faq_items, kbf_landing_build_schema, 
 *            kbf_landing_register_seo, kbf_landing_get_image_sets, kbf_landing_render_faq, ob_start
 * @params    none
 * @returns   string Buffered HTML content
 * @status    ACTIVE
 */
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://upload.wikimedia.org">
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="preload" as="image" href="<?php echo esc_url(BNTM_KBF_URL . 'assets/hero.jpg'); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Shippori+Antique+B1&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
    .ph{font-family:'Phosphor' !important;font-style:normal;font-weight:400;line-height:1;}
    .ph-bold{font-weight:700;}
    .ph-fill{font-weight:400;}

    :root {
        --kbf-glass-bg: rgba(255, 255, 255, 0.7);
        --kbf-glass-border: rgba(255, 255, 255, 0.6);
        --kbf-glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        --kbf-blob-1: rgba(91, 168, 245, 0.08);
        --kbf-blob-2: rgba(111, 182, 255, 0.05);
        --kbf-ink: #0f1115;
        --kbf-muted: #4f5a6b;
        --kbf-border: #edf0f4;
        --kbf-surface: #ffffff;
        --kbf-soft: #f7f8fb;
        --kbf-lime: #6fb6ff;
        --kbf-lime-dark: #0f2a52;
        --kbf-shadow: rgba(0, 0, 0, 0.05) 0px 2px 4px -1px, rgba(0, 0, 0, 0.04) 0px 1px 2px -1px;
        --kbf-shadow-lg: rgba(0, 0, 0, 0.06) 0px 6px 12px -3px, rgba(0, 0, 0, 0.05) 0px 3px 6px -2px;
        --kbf-radius: 12px;
        --kbf-radius-sm: 8px;
        --kbf-radius-md: 12px;
        --kbf-radius-lg: 14px;
    }

    .kbf-landing {
        font-family: 'Outfit', system-ui, -apple-system, sans-serif;
        color: var(--kbf-ink);
        background:
            radial-gradient(1200px 320px at 8% -12%, #eaf2ff 0%, transparent 62%),
            radial-gradient(1000px 320px at 92% -2%, #e6f0ff 0%, transparent 58%),
            var(--kbf-surface);
        width: 100%;
        padding: 26px 22px 64px;
        border-radius: var(--kbf-radius-lg);
        overflow: visible;
    }

    .kbf-landing, .kbf-landing *, .kbf-landing *::before, .kbf-landing *::after { box-sizing: border-box; }
    .kbf-container { overflow: visible; max-width: 80% !important; margin: 0 auto; padding: 62px 0px 0; }
    .kbf-landing a { color: inherit; text-decoration: none; }
    .kbf-brand-text { color: #3d8ef0; font-family: 'Shippori Antique B1', 'Poppins', system-ui, sans-serif; }
        .kbf-divider {
        height: 1px;
        width: 100%;
        background: linear-gradient(90deg, transparent, #e5e9f2, transparent);
        margin: 80px 0;
    }
    html { scroll-behavior: smooth; }

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
        background: #ffffff;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid transparent;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        margin: 0;
        width: 100%;
        box-sizing: border-box;
    }
    .kbf-topbar.kbf-topbar-scrolled {
        border-bottom-color: #edf0f4;
        box-shadow: 0 6px 28px rgba(15, 40, 80, 0.10);
    }
    .kbf-topbar-left { display: flex; align-items: center; gap: 28px; flex-wrap: wrap; }
    .kbf-brand { display: flex; align-items: center; gap: 10px; font-weight: 800; }
    .kbf-nav { display: flex !important; flex-direction: row; gap: 20px; font-size: 12.5px; color: #4f5a6b; }
    .kbf-nav a { position: relative; display:inline-flex; align-items:center; color:#4f5a6b; text-decoration:none; font-size:12.5px; }
    .kbf-nav a::after {
        content: ''; position: absolute; left: 0; bottom: -8px;
        width: 0; height: 2px; border-radius: 999px;
        background: #6fb6ff; transition: width .2s ease;
    }
    .kbf-nav a:hover::after { width: 100%; }
    .kbf-actions { display: flex; gap: 10px; align-items: center; }
    .kbf-btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 10px 18px; border-radius: 999px;
        font-weight: 500; font-size: 12.5px;
        border: 1px solid transparent;
        transition: transform .3s ease, box-shadow .3s ease, background .3s ease, filter .3s ease;
        background:#fff;
        color:#1f2937;
        text-decoration:none;
    }
    .kbf-btn.kbf-btn-block{
        width:100%;
        box-sizing:border-box;
    }
    .kbf-btn.kbf-btn-primary {
        background: linear-gradient(135deg, #5ba8f5 0%, #3d8ef0 55%, #1f6fe0 100%);
        color: #ffffff;
        border-radius: 8px !important;
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
    .kbf-btn-primary:hover::before { opacity: 1; }
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
    .kbf-hamburger{
        display:none !important;
        background:#fff;
        border:1px solid #e2e8f0;
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
    }
    .kbf-mobile-overlay.kbf-overlay-open{ display:block; }
    .kbf-mobile-menu{
        position:fixed;
        top:60px;left:0;right:0;
        z-index:999;
        border-radius:0;
        margin-top:0;
        transform:translateY(-110%);
        transition:transform .3s cubic-bezier(.4,0,.2,1);
        display:flex;
        flex-direction:column;
        overflow:hidden;
        background:#fff;
        border:1px solid #e2e8f0;
        box-shadow:0 10px 26px rgba(15,40,80,.18);
    }
    .kbf-mobile-menu.kbf-menu-open{ transform:translateY(0); display:flex; }
    .kbf-mobile-menu a{
        padding:13px 18px;
        font-size:13.5px;
        color:#0f172a;
        border-bottom:1px solid #e2e8f0;
        text-align:center;
        text-decoration:none;
    }
    .kbf-mobile-menu a:last-of-type{ border-bottom:none; }
    .kbf-mobile-menu-actions{
        display:flex;flex-direction:row;gap:8px;
        align-items:center;
        height:68px;
        padding:12px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;
        box-sizing:border-box;
    }
    .kbf-mobile-menu-actions .kbf-btn{ flex:1; justify-content:center; }

    /* ============================================================
       PREMIUM HERO  -  light luxury + trust-focused (REDESIGNED)
       ============================================================ */
    .kbf-hero {
        width: 100%;
        height: 80vh;
        margin-bottom: 34px;
        position: relative;
        background: 
            linear-gradient(135deg, rgba(59, 130, 246, 0.06) 0%, rgba(59, 130, 246, 0.02) 50%, transparent 100%),
            linear-gradient(180deg, #ffffff 0%, #f8fbff 50%, #f3f7ff 100%);
        border-radius: 28px;
        padding: 0;
        overflow: hidden;
        border: 1px solid rgba(219, 234, 254, 0.8);
        box-shadow:
            0 20px 60px rgba(15, 40, 80, 0.08),
            0 10px 30px rgba(15, 40, 80, 0.05),
            inset 0 1px 0 rgba(255, 255, 255, 0.6);
    }
    /* Premium grid + ornamental pattern overlay */
    .kbf-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(219, 234, 254, 0.4) 1px, transparent 1px),
            linear-gradient(90deg, rgba(219, 234, 254, 0.4) 1px, transparent 1px),
            radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(96, 165, 250, 0.06) 0%, transparent 50%);
        background-size: 80px 80px, 80px 80px, 100% 100%, 100% 100%;
        mask-image: radial-gradient(ellipse 75% 85% at 50% 50%, black 15%, transparent 100%);
        -webkit-mask-image: radial-gradient(ellipse 75% 85% at 50% 50%, black 15%, transparent 100%);
        pointer-events: none;
        z-index: 1;
    }
    /* Subtle radial glow with light theme */
    .kbf-hero::after {
        content: '';
        position: absolute;
        top: -30%; right: -5%;
        width: 800px; height: 800px;
        background: 
            radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, rgba(59, 130, 246, 0.02) 35%, transparent 70%),
            radial-gradient(circle at 80px 80px, rgba(96, 165, 250, 0.04) 0%, transparent 50%);
        pointer-events: none;
        z-index: 1;
        filter: blur(1px);
    }
    .kbf-hero-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        height: 100%;
        gap: 60px;
        padding: 80px 64px;
        position: relative;
        z-index: 2;
    }
    /* Premium motion (sophisticated, refined) */
    .kbf-hero .kbf-hero-left,
    .kbf-hero .kbf-hero-right{
        opacity:1;
        transform:translateY(0);
    }
    .kbf-page-loaded .kbf-hero .kbf-hero-left{animation:kbfHeroRise .8s cubic-bezier(.2,.65,.3,1) .08s both;}
    .kbf-page-loaded .kbf-hero .kbf-hero-right{animation:kbfHeroRise .8s cubic-bezier(.2,.65,.3,1) .18s both;}
    @keyframes kbfHeroRise{
        from{opacity:0; transform:translateY(16px);}
        to{opacity:1; transform:translateY(0);}
    }
    /* --- Scroll reveal system --- */
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

    /* FAQ reveal animation - optimized for accordion */
    .kbf-faq .kbf-reveal.is-in {
        animation: kbfFaqContentReveal 0.45s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes kbfFaqContentReveal {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Delay variants for staggered sibling reveals */
    .kbf-reveal.delay-1 { transition-delay: 0.06s; }
    .kbf-reveal.delay-2 { transition-delay: 0.13s; }
    .kbf-reveal.delay-3 { transition-delay: 0.20s; }

    /* Child stagger  -  direct children of a revealed section */
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

    /* Heading clip reveal  -  apply .kbf-heading-reveal to h1/h2 */
    .kbf-heading-reveal {
        clip-path: inset(0 0 100% 0);
        transition: clip-path 0.65s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .kbf-reveal.is-in .kbf-heading-reveal,
    .is-in.kbf-heading-reveal {
        clip-path: inset(0 0 0% 0);
    }

    /* Cards in feature grid  -  cascade with y offset */
    .kbf-reveal.is-in .kbf-card:nth-child(1) { animation: kbfCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.08s both; }
    .kbf-reveal.is-in .kbf-card:nth-child(2) { animation: kbfCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.18s both; }
    .kbf-reveal.is-in .kbf-card:nth-child(3) { animation: kbfCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.28s both; }
    .kbf-reveal.is-in .kbf-card:nth-child(4) { animation: kbfCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.38s both; }
    .kbf-reveal.is-in .kbf-card:nth-child(5) { animation: kbfCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.48s both; }

    @keyframes kbfCardIn {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Stat numbers  -  count-up is handled in JS; just fade the block */
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
    .kbf-about-stats.kbf-stats-revealed { margin-top: 18px; }
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
        max-width: 520px;
        display: flex;
        flex-direction: column;
        gap: 24px;
        position: relative;
        z-index: 2;
    }
    .kbf-eyebrow {
        display: inline-flex !important; align-items: center !important; gap: 3px !important;
        padding: 3px 8px !important; border-radius: 999px !important;
        font-size: 6px !important; font-weight: 600 !important;
        letter-spacing: .12em !important; text-transform: uppercase !important;
        color: #2563eb !important; 
        background: rgba(59, 130, 246, 0.12) !important;
        border: 1px solid rgba(59, 130, 246, 0.3) !important; 
        width: fit-content !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
        line-height: normal !important;
    }
    .kbf-eyebrow-dot {
        width: 3px; height: 3px; border-radius: 50%;
        background: #3b82f6; display: inline-block; flex-shrink: 0;
        box-shadow: 0 0 12px rgba(59, 130, 246, 0.7);
        animation: kbfPulse 2s ease-in-out infinite;
    }
    @keyframes kbfPulse {
        0%, 100% { box-shadow: 0 0 12px rgba(59, 130, 246, 0.7); opacity: 1; }
        50% { box-shadow: 0 0 20px rgba(59, 130, 246, 1); opacity: 0.9; }
    }
    .kbf-hero-heading {
        font-size: clamp(36px, 5.2vw, 56px);
        font-weight: 600; 
        line-height: 1.15;
        color: #0f1115; 
        letter-spacing: -2px;
        margin: 0;
        background: linear-gradient(180deg, #0f1115 0%, #1f2a44 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .kbf-hero-highlight {
        display: inline-block;
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
        color: #ffffff;
        border-radius: 8px; padding: 2px 14px; font-style: normal;
    }
    .kbf-hero-desc {
        font-size: 15px; font-weight: 400;
        color: #4f5a6b; line-height: 1.8; margin: 0;
        max-width: 460px;
    }
    
    /* Premium hero CTA button matching standard button style */
    .kbf-hero-cta-btn {
        width: fit-content;
        padding: 16px 36px;
        gap: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        background: linear-gradient(135deg, #5ba8f5 0%, #3d8ef0 55%, #1f6fe0 100%);
        color: #ffffff !important;
        border: 1px transparent;
        border-radius: 12px;
        box-shadow:
            0 4px 15px rgba(61, 142, 240, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        text-decoration: none;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        letter-spacing: 0.03em;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
    }
    .kbf-hero-cta-btn::before {
        content: '';
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        background: linear-gradient(135deg, #7ec4ff 0%, #5aaaf8 40%, #2878e8 100%);
        opacity: 0;
        z-index: -1;
        transition: opacity .3s ease;
    }
    .kbf-hero-cta-btn:hover {
        transform: translateY(-2px);
        box-shadow:
            0 1px 3px rgba(32, 112, 224, 0.15),
            0 8px 24px rgba(42, 120, 220, 0.45),
            0 16px 40px rgba(61, 142, 240, 0.20),
            inset 0 1px 0 rgba(255, 255, 255, 0.25);
        filter: brightness(1.06);
    }
    .kbf-hero-cta-btn:hover::before { opacity: 1; }
    .kbf-hero-cta-btn:active {
        transform: translateY(0px);
        box-shadow:
            0 1px 2px rgba(32, 112, 224, 0.30),
            0 2px 8px rgba(61, 142, 240, 0.28),
            0 0 0 2px rgba(111, 182, 255, 0.15),
            inset 0 1px 0 rgba(255, 255, 255, 0.12);
        filter: brightness(0.96);
        transition: transform .1s ease, box-shadow .1s ease, filter .1s ease;
    }
    .kbf-hero-cta-btn img,
    .kbf-hero-cta-btn .kbf-icon,
    .kbf-hero-cta-btn i {
        width: 16px;
        height: 16px;
        margin-left: 8px;
        display: inline-block;
        transition: transform .35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .kbf-hero-cta-btn img { filter: invert(100%); }
    .kbf-hero-cta-btn .kbf-icon,
    .kbf-hero-cta-btn i { color: #ffffff; filter: none; position: relative; top: 2px; }
    .kbf-hero-cta-btn:hover img,
    .kbf-hero-cta-btn:hover .kbf-icon,
    .kbf-hero-cta-btn:hover i {
        transform: rotate(45deg);
    }
    
    /* Trust indicators below CTA */
    .kbf-hero-trust {
        display: flex;
        gap: 16px;
        margin-top: 24px;
        flex-wrap: wrap;
        opacity: 0;
        transform: translateY(8px);
        animation: kbfTrustIn 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.5s forwards;
    }
    @keyframes kbfTrustIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .kbf-trust-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #4f5a6b;
        font-weight: 500;
    }
    .kbf-trust-badge i {
        font-size: 14px;
        color: #3b82f6;
    }

    /* Right: floating phone cards with premium styling */
    .kbf-hero-right {
        flex: 0 0 clamp(280px, 38vw, 400px);
        height: 480px;
        position: relative; z-index: 2;
        display:flex;
        align-items:center;
        justify-content:center;
        filter: drop-shadow(0 20px 40px rgba(15, 40, 80, 0.15)) drop-shadow(0 8px 16px rgba(15, 40, 80, 0.1));
    }
     /* Cards wrap  -  fixed internal coordinate system */
    .kbf-cards-wrap {
        position: relative;
        --cw: 400px;
        --ch: 480px;
        --kbf-card-scale: 1;
        width: var(--cw);
        height: var(--ch);
        transform: scale(var(--kbf-card-scale));
        transform-origin: center;
        transition: transform 0.3s ease;
    }
    .kbf-pcard {
        position: absolute; border-radius: 20px; overflow: hidden;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.95);
        box-shadow:
            0 25px 60px rgba(15, 40, 80, 0.12),
            0 10px 25px rgba(15, 40, 80, 0.08),
            0 2px 8px rgba(15, 40, 80, 0.05),
            inset 0 1px 0 rgba(255,255,255,0.95);
    }
    .kbf-pcard::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0) 46%);
        pointer-events: none;
    }
    /* Main card  -  centered anchor with enhanced animation */
    .kbf-pcard-main {
        width: 180px; height: 310px;
        left: 50%; top: 50%;
        --pc-x: -50%;
        --pc-y: -50%;
        --pc-rot: 0deg;
        transform: translate(var(--pc-x), var(--pc-y)) rotate(var(--pc-rot));
        z-index: 4;
        animation: kbfFloatMain 6s ease-in-out infinite;
    }
    /* Bottom-right card  -  sits to the right of the main card */
    .kbf-pcard-br {
        width: 110px; height: 190px;
        left: 50%; top: 50%;
        --pc-x: calc(-50% + 140px);
        --pc-y: -50%;
        --pc-rot: 2deg;
        transform: translate(var(--pc-x), var(--pc-y)) rotate(var(--pc-rot));
        z-index: 3;
        animation: kbfFloatBR 5.5s ease-in-out infinite;
    }
    /* Left card  -  partially hidden behind main, slight counter-tilt */
    .kbf-pcard-tl {
        width: 120px; height: 190px;
        left: 50%; top: 50%;
        --pc-x: calc(-50% - 140px);
        --pc-y: -50%;
        --pc-rot: -2deg;
        transform: translate(var(--pc-x), var(--pc-y)) rotate(var(--pc-rot));
        z-index: 2;
        animation: kbfFloatTL 6.5s ease-in-out infinite;
    }
    @keyframes kbfFloatMain {
        0%,100%{transform:translate(var(--pc-x), var(--pc-y)) rotate(var(--pc-rot))}
        50%{transform:translate(var(--pc-x), calc(var(--pc-y) - 12px)) rotate(var(--pc-rot))}
    }
    @keyframes kbfFloatBR   {
        0%,100%{transform:translate(var(--pc-x), var(--pc-y)) rotate(var(--pc-rot))}
        50%{transform:translate(var(--pc-x), calc(var(--pc-y) - 8px)) rotate(var(--pc-rot))}
    }
    @keyframes kbfFloatTL   {
        0%,100%{transform:translate(var(--pc-x), var(--pc-y)) rotate(var(--pc-rot))}
        50%{transform:translate(var(--pc-x), calc(var(--pc-y) - 9px)) rotate(var(--pc-rot))}
    }

    .kbf-pcard-img {
        width: 100%; height: calc(95% - 44px);
        display: flex; align-items: center; justify-content: center;
        position: relative;
        background: linear-gradient(135deg, #f0f4f9 0%, #e8f0f8 100%);
    }
    .kbf-pcard-img::after {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(180deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0.1) 100%);
        pointer-events: none;
    }
    .kbf-pimg-1, .kbf-pimg-2, .kbf-pimg-3, .kbf-pimg-4 { background: linear-gradient(135deg, #e5ecf2 0%, #dce6f0 100%); }
    .kbf-pcard-img img{
        width:100%;
        height:100%;
        object-fit:cover;
        display:block;
        filter: grayscale(100%) contrast(1.05) brightness(0.95);
        transition: filter 0.4s ease;
    }
    .kbf-pcard:hover .kbf-pcard-img img {
        filter: grayscale(60%) contrast(1.1) brightness(1);
    }
    .kbf-pcard-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px 12px;
        justify-content: center;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(250, 252, 255, 0.95) 100%);
        border-top: 1px solid rgba(219, 234, 254, 0.6);
    }
    .kbf-pcard-name { 
        font-size: 11px; 
        font-weight: 600; 
        color: #1e3a8a; 
        line-height: 1.4; 
        text-align: center;
        letter-spacing: 0.3px;
    }
    .kbf-fchip {
        position: absolute; background: #fff;
        border: 1px solid #d7e7ff; border-radius: 20px;
        padding: 5px 11px; font-size: 28px; font-weight: 600;
        color: #1a3a66; display: flex; align-items: center; gap: 6px;
        z-index: 5; white-space: nowrap;
        box-shadow: var(--kbf-shadow);
        backdrop-filter: blur(8px);
    }

    /* SECTIONS */
    .kbf-section { margin-top: 54px; scroll-margin-top: calc(var(--kbf-topbar-h, 64px) + 16px); }
    .kbf-section:first-of-type { margin-top: 32px; }
    .kbf-section h2 { font-size: 28px; margin: 0 0 8px; letter-spacing: -0.2px; font-weight: 400; }
    .kbf-section p { color: #334155; margin: 0; font-size: 28px; line-height: 1.7; }
    .kbf-section p + p { margin-top: 10px; }
    .kbf-section .kbf-lead { font-size: 17px; line-height: 1.7; margin-top: 8px; }
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
    .kbf-list { margin: 12px 0 0; padding: 0; color: #334155; font-size: 17px; line-height: 1.65; list-style: none; }
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
    .kbf-compare-table { margin-top: 18px; border: 1px solid var(--kbf-border); border-radius: var(--kbf-radius-lg); overflow: hidden; background: #fff; box-shadow: var(--kbf-shadow); }
    .kbf-compare-row { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 10px; padding: 12px 16px; border-bottom: 1px solid var(--kbf-border); font-size: 18px; }
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
        background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); border: 1px solid var(--kbf-glass-border);
        border: 1px solid #e0e9f7;
        border-radius: var(--kbf-radius-lg);
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
        justify-content: center; color: #2a5a9e; font-weight: 600; font-size: 28px;
    }
    .kbf-card .kbf-chip img {
        width: 16px;
        height: 16px;
        display: block;
        filter: invert(46%) sepia(85%) saturate(1381%) hue-rotate(198deg) brightness(98%) contrast(93%);
    }
    .kbf-card h4 { margin: 12px 0 6px; font-size: 17px; }
    .kbf-card p { font-size: 18px; }

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
        font-size: 18px;
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
        border-radius: var(--kbf-radius-lg); overflow: hidden; box-shadow: var(--kbf-shadow);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .kbf-urgent-card:hover { transform: translateY(-3px); box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12); }
    .kbf-urgent-thumb { height: 180px; background-size: cover; background-position: center; }
    .kbf-urgent-body { padding: 14px 16px 16px; }
    .kbf-urgent-meta { font-size: 17px; color: #334155; }
    .kbf-urgent-title { font-size: 28px; font-weight: 600; margin: 6px 0 10px; }
    .kbf-progress { height: 6px; background: #edf1f7; border-radius: 999px; overflow: hidden; }
    .kbf-progress span { display: block; height: 100%; background: var(--kbf-lime); width: 68%; }
    .kbf-amounts { display: flex; justify-content: space-between; font-size: 18px; color: #334155; margin-top: 10px; }
    .kbf-about-grid { display: grid; grid-template-columns: 1.1fr .9fr; gap: 18px; margin-top: 24px; align-items: stretch; }
    .kbf-about-card {
        background: var(--kbf-surface);
        border: 1px solid var(--kbf-border);
        border-radius: var(--kbf-radius-lg);
        padding: 18px;
        box-shadow: var(--kbf-shadow);
    }
    .kbf-about-card h4 { margin: 0 0 8px; font-size: 28px; }
    .kbf-about-card p { margin: 0; color: #334155; font-size: 28px; line-height: 1.6; }
    .kbf-about-photo {
        border-radius: var(--kbf-radius-lg);
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
    .kbf-stat .kbf-photo-grid { position: absolute; inset: 6% 0; pointer-events: none; z-index: 1; }
    .kbf-stat .kbf-photo-grid img {
        width: 90px; height: 110px; border-radius: var(--kbf-radius-lg);
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
    .kbf-stat .kbf-photo-grid img:nth-child(1) { left: 4%; top: -8%; }
    .kbf-stat .kbf-photo-grid img:nth-child(2) { right: 4%; top: -8%; }
    .kbf-stat .kbf-photo-grid img:nth-child(3) { left: 8%; bottom: -8%; }
    .kbf-stat .kbf-photo-grid img:nth-child(4) { right: 8%; bottom: -8%; }
    .kbf-stat .kbf-stat-content { position: relative; z-index: 2; }
    .kbf-stat .kbf-btn-primary { margin-top: 6px; }

    .kbf-faq { margin-top: 22px; border-top: 1px solid var(--kbf-border); }
    .kbf-faq details { border-bottom: 1px solid var(--kbf-border); padding: 18px 0; }
    .kbf-faq summary {
        list-style: none; cursor: pointer; font-size: 28px; font-weight: 600;
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
    }
    .kbf-faq summary::-webkit-details-marker { display: none; }
    .kbf-faq summary::after { content: '+'; color: #334155; font-weight: 600; }
    .kbf-faq details[open] summary::after { content: '-'; }
    .kbf-faq details p { margin: 10px 0 0; color: #334155; font-size: 18px; line-height: 1.6; }
    .kbf-faq-body {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, opacity 0.2s ease;
        will-change: max-height, opacity;
    }
    .kbf-faq-body > div { overflow: hidden; }
    .kbf-faq details[open] .kbf-faq-body { 
        max-height: 520px; 
        opacity: 1; 
        animation: kbfFaqReveal 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes kbfFaqReveal {
        from {
            opacity: 0;
            max-height: 0;
            transform: translateY(-8px);
        }
        to {
            opacity: 1;
            max-height: 520px;
            transform: translateY(0);
        }
    }

        margin-top: 44px; 
        background: linear-gradient(135deg, rgba(74, 152, 255, 0.9) 0%, rgba(47, 123, 220, 0.95) 100%) !important;
        color: #b9c0cc;
        margin-bottom:40px;
        border-radius: 22px; padding: 20px 22px;
        display: grid; grid-template-columns: 1fr auto;
        align-items: center; gap: 14px;
    }
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      margin:0;
    }
      color:#cbd5f5;
      font-size:18px;
      text-decoration:none;
    }
      color:#ffffff;
    }
        width: 32px; height: 32px; border-radius: 50%;
        border: 1px solid rgba(255,255,255,.18);
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: 18px;
    }
        width: 16px;
        height: 16px;
        display: block;
        filter: invert(100%);
    }
        background: linear-gradient(135deg, rgba(74, 152, 255, 0.9) 0%, rgba(47, 123, 220, 0.95) 100%) !important;
        color: rgba(255, 255, 255, 0.5) !important;
        font-size: 12.5px !important;
        border-top: 1px solid rgba(255, 255, 255, 0.07) !important;
    }
        color:#ffffff !important;
    }

    /* About section extras */
    .kbf-about-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1px;
        background: var(--kbf-border);
        border: 1px solid var(--kbf-border);
        border-radius: var(--kbf-radius-lg);
        overflow: hidden;
        margin-top: 18px;
    }
    .kbf-about-stat {
        background: transparent;
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
        font-size: 17px;
        color: #334155;
        margin-top: 6px;
        line-height: 1.5;
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
        border-radius: var(--kbf-radius-md);
        padding: 18px;
    }
    .kbf-about-value-icon {
        font-size: 22px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .kbf-about-value-title {
        font-size: 28px;
        font-weight: 600;
        color: var(--kbf-ink);
        margin-bottom: 4px;
    }
    .kbf-about-value-desc {
        font-size: 18px;
        color: #334155;
        line-height: 1.6;
    }

    @media (max-width: 900px) {
        .kbf-about-values { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
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
        color: #334155;
        align-items: center;
        justify-content: center;
    }
    .kbf-hamburger i {
        font-size: 18px;
        display: block;
        color: #64748b;
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
        font-size: 28px;
        color: var(--kbf-ink);
        border-bottom: 1px solid var(--kbf-border);
        transition: background .15s ease;
    }
    .kbf-mobile-menu a:last-of-type { border-bottom: none; }
    .kbf-mobile-menu-actions {
        display: flex;
        flex-direction: row;
        gap: 8px;
        align-items: center;
        height: 68px;
        padding: 12px 14px;
        border-top: 1px solid var(--kbf-border);
        background: var(--kbf-soft);
        box-sizing: border-box;
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
   /* Large tablets / small desktops (<=1024px) */
    @media (max-width: 1000px) {
        .kbf-hero-left { max-width: 420px; }
        .kbf-hero-right { flex: 0 0 clamp(260px, 34vw, 380px); height: clamp(300px, 45vw, 460px); }
        .kbf-cards-wrap { --kbf-card-scale: 0.9; }
        .kbf-hero-inner { padding: 56px 48px 52px; gap: 40px; }
    }
    
    /* Mobile navigation */
    @media (max-width: 900px) {
        .kbf-hamburger { display: flex !important; }
        .kbf-nav { display: none !important; }
        .kbf-topbar-left { gap: 16px; }
    }

    @media (max-width: 900px) {
        .kbf-nav { display: none; }
        .kbf-hamburger { display: inline-flex; }
        .kbf-actions { display: none; }
        .kbf-hamburger { display: inline-flex; margin-left: auto; }
        .kbf-mobile-menu a { text-align: center; }
        .kbf-chip { margin-left: auto; margin-right: auto; }
        .kbf-hero-heading { font-size: 34px; letter-spacing: -1px; }
        .kbf-hero-right { flex: 0 0 clamp(240px, 38vw, 340px); height: clamp(280px, 48vw, 420px); }
        .kbf-cards-wrap { --kbf-card-scale: 0.85; }
        .kbf-hero-inner { padding: 48px 36px 44px; gap: 32px; }
        .kbf-hero-trust { gap: 12px; }
        .kbf-trust-badge { font-size: 11px; }
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
    .kbf-trust-strip { border-radius: var(--kbf-radius-md); }
    }
    @media (max-width: 1200px) {
        .kbf-hero-inner {
            flex-direction: column;
            align-items: center;
            padding: 52px 40px 44px;
            gap: 40px;
            text-align: center;
        }
        .kbf-cards-wrap { --kbf-card-scale: 0.95; }
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
    }

    @media (max-width: 720px) {
        .kbf-landing { padding: 20px 14px 48px; text-align: center; }
        .kbf-hero { border-radius: 20px; margin-top: 10px !important; margin-bottom: 60px !important; }
        .kbf-hero-inner { padding: 40px 28px 36px !important; gap: 32px !important; }
        .kbf-topbar { flex-wrap: wrap; gap: 12px; justify-content: center; }
        .kbf-actions { width: 100%; justify-content: center; gap: 8px; }
        .kbf-cards-wrap { --kbf-card-scale: 0.72; }
        .kbf-feature-grid { grid-template-columns: 1fr; gap: 12px; }
        .kbf-feature-grid.kbf-feature-grid--two { grid-template-columns: 1fr; }
        .kbf-feature-grid .kbf-card { grid-column: span 1; }
        .kbf-feature-grid .kbf-card:nth-child(4),
        .kbf-feature-grid .kbf-card:nth-child(5) { grid-column: span 1; }
        .kbf-section h2 { font-size: 28px; }
        .kbf-stat h3 { font-size: 48px; }
        .kbf-divider { margin-top: 44px; margin-bottom: 44px; }
        #kbf-faq h2 { max-width: 250px; margin-left: auto; margin-right: auto; }
        .kbf-card--glass .kbf-list,
        .kbf-card--glass .kbf-list li,
        .kbf-card--soft .kbf-list,
        .kbf-card--soft .kbf-list li{
            text-align:left;
        }
        .kbf-compare-table{
            overflow-x:auto;
            -webkit-overflow-scrolling:touch;
        }
        .kbf-compare-row{
            min-width:680px;
        }
    }

    @media (max-width: 480px) {
        .kbf-landing { padding: 14px 10px 36px; }
        .kbf-topbar { padding: 12px 24px; }
        .kbf-brand-badge { width: 22px; height: 22px; font-size: 18px; }
        .kbf-hero { border-radius: 16px !important; }
        .kbf-hero-inner { padding: 36px 20px 32px !important; gap: 28px !important; }
        .kbf-hero-heading { font-size: 28px !important; letter-spacing: -0.5px; }
        .kbf-hero-desc { font-size: 14px !important; }
        .kbf-hero-right { height: 240px; }
        .kbf-cards-wrap { --kbf-card-scale: 0.62; }
        .kbf-eyebrow { font-size: 6px !important; padding: 3px 8px !important; }
        .kbf-hero-cta-btn { padding: 13px 24px; font-size: 13px; }
        .kbf-btn { font-size: 18px; padding: 8px 14px; }
        .kbf-actions .kbf-btn { width: auto; }
        .kbf-feature-grid { grid-template-columns: 1fr; gap: 10px; }
        .kbf-feature-grid .kbf-card,
        .kbf-feature-grid .kbf-card:nth-child(4),
        .kbf-feature-grid .kbf-card:nth-child(5) { grid-column: span 1; }
        .kbf-urgent-grid { grid-template-columns: 1fr; }
        .kbf-urgent-thumb { height: 180px; }
        .kbf-about-photo { height: 240px; }
        .kbf-section h2 { font-size: 18px; }
        .kbf-section p { font-size: 28px; }
        .kbf-compare-row { gap: 6px; }
        .kbf-stat h3 { font-size: 40px; }
        .kbf-stat p { font-size: 28px; }
        .kbf-faq summary { font-size: 28px; }
        .kbf-faq details p { font-size: 18px; }
        .kbf-divider { margin-top: 36px; margin-bottom: 36px; }
    }

    @media (max-width: 360px) {
        .kbf-hero-heading { font-size: 26px !important; }
        .kbf-hero-right { height: 200px; }
        .kbf-cards-wrap { --kbf-card-scale: 0.55; }
        .kbf-hero-inner { padding: 28px 16px 28px !important; }
        .kbf-stat h3 { font-size: 34px; }
        .kbf-hero-cta-btn { font-size: 13px; padding: 12px 20px; }
    }

    /* --- PREMIUM OVERRIDES (ADDED BY DESIGNER) --- */
    .kbf-hero {
        background: 
            linear-gradient(135deg, rgba(59, 130, 246, 0.06) 0%, rgba(59, 130, 246, 0.02) 50%, transparent 100%),
            linear-gradient(180deg, #ffffff 0%, #f8fbff 50%, #f3f7ff 100%);
    }
    .kbf-hero-heading, .kbf-display, .kbf-hero h1 {
        font-size: clamp(36px, 5.2vw, 56px);
        font-weight: 600;
        letter-spacing: -2px;
        line-height: 1.15;
        color: #0f1115;
        background: linear-gradient(180deg, #0f1115 0%, #1f2a44 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .kbf-hero-sub, .kbf-eyebrow {
        font-size: 6px;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #2563eb;
        font-weight: 600;
        background: rgba(59, 130, 246, 0.12);
        border: 1px solid rgba(59, 130, 246, 0.3);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .kbf-hero-desc {
        font-size: 15px !important;
        line-height: 1.75 !important;
        color: #4f5a6b !important;
    }
    .kbf-hero-cta-btn {
        background: linear-gradient(135deg, #5ba8f5 0%, #3d8ef0 55%, #1f6fe0 100%);
        color: #ffffff !important;
        box-shadow: 0 4px 15px rgba(61, 142, 240, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        letter-spacing: 0.03em;
        border: 1px transparent;
        border-radius: 12px;
        padding: 16px 36px;
        position: relative;
        overflow: hidden;
    }
    .kbf-hero-cta-btn:hover {
        transform: translateY(-2px);
        box-shadow:
            0 1px 3px rgba(32, 112, 224, 0.15),
            0 8px 24px rgba(42, 120, 220, 0.45),
            0 16px 40px rgba(61, 142, 240, 0.20),
            inset 0 1px 0 rgba(255, 255, 255, 0.25);
        filter: brightness(1.06);
    }
    .kbf-hero-cta-btn::after {
        content: '';
        position: absolute;
        top: 0; left: -100%; width: 50%; height: 100%;
        background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
        transform: skewX(-20deg);
        transition: none;
    }
    .kbf-hero-cta-btn:hover::after {
        animation: kbfShimmer 1.5s infinite;
    }
    @keyframes kbfShimmer {
        100% { left: 200%; }
    }
    .kbf-topbar {
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border-bottom: 1px solid rgba(237,240,244,0.8);
    }
    .kbf-topbar.kbf-topbar-scrolled {
        box-shadow: 0 2px 20px rgba(15,40,80,0.07);
    }
    .kbf-feature-grid .kbf-card {
        background: #ffffff;
        border: 1px solid #edf0f4;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(15,23,42,0.05);
    }
    .kbf-feature-grid .kbf-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(15,23,42,0.09);
        border-color: #d0e3fa;
    }
    .kbf-chip {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #e7f1ff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .kbf-chip img, .kbf-chip i, .kbf-chip svg {
        filter: invert(46%) sepia(85%) saturate(1381%) hue-rotate(198deg) brightness(98%) contrast(93%);
    }
    .kbf-feature-grid h4, .kbf-feature-grid .kbf-card h4 {
        font-size: 15px;
        font-weight: 600;
        color: #0f1115;
        margin-bottom: 6px;
    }
    .kbf-feature-grid p, .kbf-feature-grid .kbf-card p {
        font-size: 13.5px;
        line-height: 1.7;
        color: #4f5a6b;
    }
    .kbf-about-stats {
        background: linear-gradient(135deg, #f5f9ff 0%, #ffffff 60%, #f0f5ff 100%);
        border-radius: 20px;
        padding: 40px 32px;
    }
    .kbf-about-stat-num {
        font-size: 40px;
        font-weight: 600;
        letter-spacing: -1.5px;
        background: linear-gradient(180deg, #4a9af5 0%, #1d4ed8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .kbf-about-stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #6f7785;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-top: 4px;
    }
    .kbf-compare-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #edf0f4;
    }
    .kbf-compare-table .kbf-compare-head, .kbf-compare-row:first-child {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        background: linear-gradient(180deg, #fafcff 0%, #f5f8fd 100%);
        color: #64748b;
    }
    .kbf-compare-row:hover td, .kbf-compare-row:hover > div {
        background: #f7faff;
    }
    .kbf-compare-row > p:nth-child(3), .kbf-compare-table td:nth-child(3) {
        border-left: none;
    }
    .kbf-stat .kbf-photo-grid img {
        border-radius: 16px;
        box-shadow: 0 12px 32px rgba(0,0,0,0.3);
    }
    .kbf-feature-grid--two .kbf-card, .kbf-audience-card, .kbf-section.kbf-stat + .kbf-section .kbf-card {
        border: 1px solid #edf0f4;
        border-radius: 16px;
        padding: 24px;
        background: #ffffff;
    }
    .kbf-feature-grid--two .kbf-card:hover, .kbf-audience-card:hover, .kbf-section.kbf-stat + .kbf-section .kbf-card:hover {
        background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
        border-color: #c8dcf5;
    }
    .kbf-feature-grid--two h4, .kbf-audience-card h4, .kbf-section.kbf-stat + .kbf-section .kbf-card h4 {
        font-size: 15px;
        font-weight: 600;
        color: #0f1115;
    }
    .kbf-feature-grid--two p, .kbf-audience-card p, .kbf-section.kbf-stat + .kbf-section .kbf-card p {
        font-size: 13.5px;
        line-height: 1.7;
        color: #4f5a6b;
    }
    #kbf-faq h2 {
        font-size: 26px;
        font-weight: 600;
        color: #0f1115;
        text-align: center;
        margin-bottom: 32px;
    }
    .kbf-faq details {
        border-bottom: 1px solid #edf0f4;
        padding: 18px 0;
        background: transparent;
        box-shadow: none;
        border-radius: 0;
        border-top: none;
        border-left: none;
        border-right: none;
    }
    .kbf-faq summary {
        font-size: 14px;
        font-weight: 600;
        color: #0f1115;
    }
    .kbf-faq-body p, .kbf-faq details p {
        font-size: 13.5px;
        line-height: 1.75;
        color: #4f5a6b;
    }
        background: linear-gradient(135deg, rgba(74, 152, 255, 0.9) 0%, rgba(47, 123, 220, 0.95) 100%) !important;
        color: rgba(255,255,255,0.5) !important;
        font-size: 12.5px !important;
        border-top: 1px solid rgba(255,255,255,0.07) !important;
    }
        color: #3d8ef0 !important;
        font-family: 'Shippori Antique B1', sans-serif !important;
    }
        color: rgba(255,255,255,0.45) !important;
        font-size: 12px !important;
    }
        color: #ffffff !important;
    }

    /* --- TYPOGRAPHY SYSTEM --- */
    .kbf-landing h1 { font-weight: 600; }
    .kbf-landing h2 { font-weight: 600; letter-spacing: -0.5px; font-size: 22px; line-height: 1.35; }
    .kbf-landing h3 { font-weight: 600; font-size: 16px; line-height: 1.35; }
    .kbf-landing h4 { font-weight: 600; font-size: 16px; }
    .kbf-landing h5, .kbf-landing h6 { font-weight: 500; }
    .kbf-landing p, .kbf-landing li { font-weight: 400; font-size: 15px; line-height: 1.75; color: #4f5a6b; }
    .kbf-landing .kbf-meta, .kbf-landing small { font-weight: 400; }
    .kbf-landing strong, .kbf-landing b { font-weight: 600; }
    .kbf-btn { font-weight: 500; }
    .kbf-compare-table tbody td, .kbf-compare-row > p {
        font-size: 13px !important;
        color: #0f1115 !important;
    }
    
    .kbf-eyebrow, .kbf-hero-sub {
        font-weight: 500 !important;
        font-size: 6px !important;
        letter-spacing: 0.18em !important;
        text-transform: uppercase !important;
        color: #8b97aa !important;
    }

    /* Mobile Typography Scaling */
    @media (max-width: 900px) {
        .kbf-landing p, .kbf-landing li {
            font-size: 14px !important;
            line-height: 1.7 !important;
        }
        .kbf-card h4, .kbf-feature-grid h4, .kbf-audience-card h4, .kbf-section h3, .kbf-section h4 {
            font-size: 14px !important;
            line-height: 1.3 !important;
        }
        .kbf-section h2, .kbf-section-title {
            font-size: 18px !important;
        }
    }
    
    .kbf-about-stat-num {
        font-weight: 600 !important;
        font-size: 36px !important;
        letter-spacing: -1px !important;
    }
    
    .kbf-about-stat-label {
        font-weight: 400 !important;
        font-size: 12px !important;
        color: #6f7785 !important;
    }
    
    .kbf-compare-table thead th, .kbf-compare-head, .kbf-compare-row:first-child > div {
        font-weight: 600 !important;
        font-size: 11px !important;
        letter-spacing: 0.6px !important;
        text-transform: uppercase !important;
        color: #64748b !important;
    }
    
    
    .kbf-btn {
        font-weight: 500 !important;
        font-size: 13px !important;
    }
    
    .kbf-stat h3 {
        font-weight: 600 !important;
        font-size: 48px !important;
        letter-spacing: -1px !important;
        color: #0f1115  !important;
    }
    
    .kbf-faq summary {
        font-weight: 600 !important;
        font-size: 14px !important;
        color: #0f1115 !important;
    }
    
    .kbf-faq-body p, .kbf-faq details p {
        font-weight: 400 !important;
        font-size: 13.5px !important;
        color: #4f5a6b !important;
        line-height: 1.75 !important;
    }
    
        font-weight: 400 !important;
        font-size: 12.5px !important;
        color: #ffffff !important;
    }

    /* PCARD Text Fixes */
    h4.kbf-pcard-name {
        font-size: 10px !important;
        font-weight: 600 !important;
        color: #0d1a2e !important;
        margin: 0 !important;
    }
    p.kbf-pcard-sub {
        font-size: 12.5px !important;
        font-weight: 500 !important;
        color: #8b97aa !important;
        margin: 0 !important;
    }

    /* --- GLOBAL CARD HOVER TRANSITIONS --- */
    .kbf-card,
    .kbf-audience-card,
    .kbf-pcard,
    .kbf-compare-row {
        transition: transform 1s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 1s cubic-bezier(0.2, 0.8, 0.2, 1), border-color 1s cubic-bezier(0.2, 0.8, 0.2, 1) !important;
    }

    .kbf-btn {
        transition: transform 1s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 1s cubic-bezier(0.2, 0.8, 0.2, 1), background 1s cubic-bezier(0.2, 0.8, 0.2, 1), filter 1s cubic-bezier(0.2, 0.8, 0.2, 1) !important;
    }

    /* --- COMPARE TABLE CONSISTENCY FIX --- */
    .kbf-compare-head > p, .kbf-compare-row:first-child > p {
        font-weight: 600 !important;
        font-size: 11px !important;
        letter-spacing: 0.6px !important;
        text-transform: uppercase !important;
        color: #64748b !important;
    }
    
    .kbf-compare-row strong {
        font-weight: 600 !important;
        font-size: 14px !important;
        color: #0d1a2e !important;
    }
    .kbf-compare-row .kbf-strong {
        font-size: 13px !important;
    }
    
    .kbf-compare-row > p:not(.kbf-compare-head > p) {
        font-size: 13.5px !important;
        color: #4f5a6b !important;
        line-height:1.5 !important;
    }
    
    /* Enforce the specific table body cell rules requested by user precisely over the global P rules */
    .kbf-compare-row:not(.kbf-compare-head) > p {
        font-weight: 400 !important;
        font-size: 13px !important;
        color: #0f1115 !important;
    }

    /* --- STATS REDESIGN & TYPOGRAPHY FIX --- */
    .kbf-about-stats {
        background: linear-gradient(135deg, #ffffff 0%, #f7fbff 100%) !important;
        border: 1px solid #edf0f4 !important;
        border-radius: 24px !important;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.03) !important;
        padding: 48px 32px !important;
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        grid-auto-flow: column;
    }

    /* #kbf-how responsive: stack to 1 column and center content */
    @media (max-width: 900px) {
        #kbf-how .kbf-feature-grid { grid-template-columns: 1fr !important; }
        #kbf-how .kbf-card { grid-column: span 1 !important; text-align: center; }
        #kbf-how .kbf-card:nth-child(4),
        #kbf-how .kbf-card:nth-child(5) { grid-column: span 1 !important; }
        #kbf-how .kbf-chip { margin-left: auto; margin-right: auto; }
        #kbf-how .kbf-card h4,
        #kbf-how .kbf-card p { text-align: center; }
    }
    @media (max-width: 900px) {
        .kbf-about-stats {
            grid-template-columns: 1fr !important;
            grid-auto-flow: row;
        }
    }

    .kbf-landing p.kbf-about-stat-num {
        font-size: 46px !important;
        font-weight: 600 !important;
        letter-spacing: -1.5px !important;
        background: linear-gradient(135deg, #5ba8f5 0%, #3d8ef0 55%, #1f6fe0 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        line-height: 1 !important;
        margin-bottom: 2px !important;
        display: inline-block !important; /* Required for webkit clear text clipping */
    }
    
    .kbf-landing p.kbf-about-stat-label {
        font-weight: 500 !important;
        font-size: 11.5px !important;
        color: #64748b !important;
        text-transform: none !important;
        letter-spacing: normal !important;
        line-height: 1.6 !important;
        margin-top: 10px !important;
    }

    /* --- FINAL USER TYPOGRAPHY WEIGHT OVERRIDES --- */
    /* H1 (Hero Headlines) */
    .kbf-landing h1, .kbf-hero h1, .kbf-hero-heading, .kbf-display { font-weight: 500 !important; }
    
    /* H2 (Section Titles) */
    .kbf-landing h2, .kbf-section h2, .kbf-section-title, #kbf-faq h2 { font-weight: 500 !important; }
    
    /* H3 (Display Titles) */
    .kbf-landing h3, .kbf-stat h3, .kbf-section h3 { font-weight: 500 !important; }
    
    /* H4 (Card & Feature Headings) */
    .kbf-landing h4, .kbf-card h4, .kbf-feature-grid h4, .kbf-audience-card h4, .kbf-section h4, .kbf-about-value-title, h4.kbf-pcard-name { font-weight: 500 !important; }
    
    /* H5 / H6 (Minor Headings) */
    .kbf-landing h5, .kbf-landing h6 { font-weight: 400 !important; }
    
    /* P (Body Text/Paragraphs) */
    
    /* Strong / B (Bold Text) */
    .kbf-landing strong, .kbf-landing b, .kbf-compare-row strong { font-weight: 500 !important; }

    /* Explicit Fix for Feature Grid H4 */
    .kbf-feature-grid h4, 
    .kbf-feature-grid .kbf-card h4, 
    .kbf-feature-grid--two h4, 
    .kbf-audience-card h4, 
    .kbf-section.kbf-stat + .kbf-section .kbf-card h4 {
        font-weight: 500 !important;
    }

    /* Explicit Fix for FAQ Summary Questions */
    .kbf-faq summary {
        font-weight: 500 !important;
    }
    /* ===== LEGAL FOOTER (CONSISTENT) ===== */
    .kbf-footer {
        margin: 44px 0 40px;
        max-width: none;
        background: linear-gradient(135deg, rgba(74, 152, 255, 0.9) 0%, rgba(47, 123, 220, 0.95) 100%) !important;
        color: rgba(255,255,255,0.5) !important;
        font-size: 12.5px !important;
        border-top: 1px solid rgba(255,255,255,0.07) !important;
        border-radius: 22px;
        padding: 20px 22px;
        gap: 18px;
        display: flex;
        flex-direction: column;
    }
    .kbf-footer-top{
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        row-gap: 4px;
    }
    .kbf-footer-left{ display:flex; flex-direction:column; gap:0; text-align:left; }
    .kbf-footer-left p{ margin-top:0; }
    .kbf-footer-left.kbf-footer-brand{ grid-column: 1; justify-self: start; }
    .kbf-footer-bottom{
        display:flex;
        align-items:center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .kbf-footer,
    .kbf-footer p,
    .kbf-footer small,
    .kbf-footer a,
    .kbf-footer .kbf-footer-links a,
    .kbf-footer .kbf-brand,
    .kbf-footer .kbf-brand-text,
    .kbf-footer h5{
        color:#ffffff !important;
        font-weight:400 !important;
        font-size:12.5px !important;
    }
    .kbf-footer .kbf-brand img{
        filter: invert(100%) brightness(1.1);
    }
    .kbf-footer .kbf-social { display: flex; gap: 8px; justify-self: end; }
    .kbf-footer .kbf-social a {
        width: 40px;
        height: 40px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 28px;
        text-decoration: none;
    }
    .kbf-footer .kbf-social i { font-size: 25px; }
    .kbf-footer .kbf-footer-links{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
        margin:0;
    }
    .kbf-footer .kbf-footer-links a{
        color:#ffffff !important;
        font-size:12.5px !important;
        text-decoration:none;
        position: relative;
        padding-bottom: 2px;
    }
    .kbf-footer .kbf-footer-links a::after{
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 100%;
        height: 2px;
        border-radius: 999px;
        background: rgba(255,255,255,0.9);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s ease;
    }
    .kbf-footer .kbf-footer-links a:hover{
        color:#ffffff !important;
    }
    .kbf-footer .kbf-footer-links a:hover::after{
        transform: scaleX(1);
    }
    @media (max-width: 720px){
        .kbf-footer{
            margin: 18px 0;
            padding: 16px 14px;
            border-radius: 14px;
        }
        .kbf-footer-top{
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 12px;
        }
        .kbf-footer-left{
            align-items: center;
        }
        .kbf-footer-left.kbf-footer-brand{ justify-self: center; }
        .kbf-footer-left p{
            margin: 0 auto;
            max-width: 340px;
        }
        .kbf-footer-bottom{
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.15);
        }
        .kbf-footer .kbf-footer-links{
            justify-content: center;
            gap: 8px 16px;
        }
        .kbf-footer .kbf-social{
            justify-content: center;
            gap: 4px;
        }
        .kbf-brand{ justify-content: center; }
        .kbf-footer .kbf-social a {
            width: 36px;
            height: 36px;
        }
        .kbf-footer .kbf-social i { font-size: 22px; }
    }
    @media (max-width: 480px){
        .kbf-footer{
            padding: 14px 10px;
            margin: 14px 0;
            border-radius: 12px;
        }
        .kbf-footer-top{ gap: 10px; }
        .kbf-footer-bottom{
            gap: 10px;
            padding-top: 10px;
        }
        .kbf-footer .kbf-footer-links{
            flex-direction: column;
            gap: 6px;
        }
        .kbf-footer .kbf-social{ gap: 2px; }
    }
    /* Cookie Consent Banner */
    .kbf-cookie-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 17, 21, 0.45);
        z-index: 9997;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .kbf-cookie-overlay.kbf-cookie-overlay-visible {
        opacity: 1;
    }
    .kbf-cookie-banner {
        position: fixed;
        bottom: 32px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        z-index: 9998;
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid var(--kbf-border);
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.18), 0 8px 20px rgba(15, 23, 42, 0.10);
        padding: 24px 28px 24px 32px;
        max-width: 720px;
        width: calc(100% - 32px);
        display: none;
        opacity: 0;
        transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1), transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .kbf-cookie-banner.kbf-cookie-visible {
        display: block;
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    .kbf-cookie-banner.kbf-cookie-hidden {
        opacity: 0;
        transform: translateX(-50%) translateY(20px);
        pointer-events: none;
    }
    .kbf-cookie-inner {
        display: flex;
        align-items: center;
        gap: 24px;
    }
    .kbf-cookie-content {
        flex: 1 1 auto;
        min-width: 0;
        padding-right: 4px;
    }
    .kbf-cookie-actions {
        flex: 0 0 auto;
        display: flex;
        gap: 8px;
        align-items: center;
        flex-shrink: 0;
    }
    .kbf-cookie-close {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: var(--kbf-soft);
        border-radius: 50%;
        color: #64748b;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.2s ease, color 0.2s ease;
    }
    .kbf-cookie-close:hover {
        background: #e8ecf1;
        color: #334155;
    }
    .kbf-cookie-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--kbf-ink);
        margin: 0 0 6px 0;
        line-height: 1.3;
    }
    .kbf-cookie-message {
        font-size: 13.5px;
        line-height: 1.6;
        color: var(--kbf-muted);
        margin: 0;
    }
    .kbf-cookie-message a {
        color: #3d8ef0;
        text-decoration: underline;
        text-decoration-color: rgba(61, 142, 240, 0.3);
        text-underline-offset: 2px;
        transition: text-decoration-color 0.2s ease;
    }
    .kbf-cookie-message a:hover {
        text-decoration-color: #3d8ef0;
    }
    .kbf-cookie-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 20px;
        border-radius: 10px;
        font-weight: 500;
        font-size: 13.5px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, filter 0.2s ease;
        white-space: nowrap;
    }
    .kbf-cookie-btn.kbf-cookie-accept {
        background: linear-gradient(135deg, #5ba8f5 0%, #3d8ef0 55%, #1f6fe0 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(61, 142, 240, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }
    .kbf-cookie-btn.kbf-cookie-accept:hover {
        transform: translateY(-2px);
        box-shadow: 0 1px 3px rgba(32, 112, 224, 0.15), 0 8px 24px rgba(42, 120, 220, 0.45), 0 16px 40px rgba(61, 142, 240, 0.20), inset 0 1px 0 rgba(255, 255, 255, 0.25);
        filter: brightness(1.06);
    }
    .kbf-cookie-btn.kbf-cookie-accept:active {
        transform: translateY(0);
        box-shadow: 0 1px 2px rgba(32, 112, 224, 0.30), 0 2px 8px rgba(61, 142, 240, 0.28), 0 0 0 2px rgba(111, 182, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.12);
        filter: brightness(0.96);
    }
    .kbf-cookie-btn.kbf-cookie-manage {
        background: transparent;
        border: 1px solid var(--kbf-border);
        color: #64748b;
    }
    .kbf-cookie-btn.kbf-cookie-manage:hover {
        background: var(--kbf-soft);
        color: #334155;
        border-color: #d5dbe4;
    }
    @media (max-width: 720px) {
        .kbf-cookie-banner {
            bottom: 16px;
            padding: 20px 18px;
            max-width: calc(100% - 24px);
            border-radius: 14px;
        }
        .kbf-cookie-inner {
            flex-direction: column;
            align-items: stretch;
            gap: 16px;
        }
        .kbf-cookie-content {
            padding-right: 28px;
        }
        .kbf-cookie-actions {
            flex-direction: row;
            justify-content: flex-end;
        }
        .kbf-cookie-btn {
            flex: 1;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .kbf-cookie-banner,
        .kbf-cookie-overlay,
        .kbf-cookie-btn {
            transition: none !important;
        }
    }

    /* ===== LEGAL MOBILE MENU (MATCH LEGAL PAGES) ===== */
    .kbf-hamburger{
        display:none !important;
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:10px;
        padding:6px 8px;
        cursor:pointer;
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
        background:rgba(0,0,0,.4);
        z-index:998;
    }
    .kbf-mobile-overlay.kbf-overlay-open{ display:block; }
    @media (max-width: 860px){
        .kbf-nav{ display:none !important; }
        .kbf-actions{ display:none !important; }
        .kbf-hamburger{ display:inline-flex !important; }
        .kbf-topbar{ flex-wrap:nowrap; gap:12px; justify-content:space-between; padding:12px 20px; }
    }
    .kbf-landing .kbf-hero-desc{ font-size: 16px !important; }
    </style>

    <!-- ================== HTML ================== -->


    <section class="kbf-landing">
     
        <div class="kbf-container">
  

        <!-- NAVBAR -->
        <div class="kbf-mobile-overlay" id="kbf-mobile-overlay"></div>
        <div class="kbf-topbar">
          <div class="kbf-topbar-left">
            <div class="kbf-brand">
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logobanner.png'); ?>" alt="fundora" style="width:auto;height:24px;object-fit:contain;border-radius:6px;">
            </div>
            <nav class="kbf-nav">
              <a href="<?php echo esc_url($landing_url); ?>#kbf-home">Home</a>
              <a href="<?php echo esc_url($landing_url); ?>#kbf-how">Features</a>
              <a href="<?php echo esc_url($landing_url); ?>#kbf-donation">About</a>
              <a href="<?php echo esc_url($landing_url); ?>#kbf-faq">FAQ</a>
            </nav>
          </div>
          <div class="kbf-actions">
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($login_url); ?>">Sign In to Start</a>
          </div>
          <button class="kbf-hamburger" id="kbf-hamburger-btn" aria-label="Open menu">
            <i id="kbf-hamburger-icon" class="ph ph-list kbf-icon" role="img" aria-label="Menu"></i>
          </button>
        </div>
        <div class="kbf-mobile-menu" id="kbf-mobile-menu">
          <a href="<?php echo esc_url($landing_url); ?>#kbf-home">Home</a>
          <a href="<?php echo esc_url($landing_url); ?>#kbf-how">Features</a>
          <a href="<?php echo esc_url($landing_url); ?>#kbf-donation">About</a>
          <a href="<?php echo esc_url($landing_url); ?>#kbf-faq">FAQ</a>
          <div class="kbf-mobile-menu-actions">
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($login_url); ?>">Sign In to Start</a>
          </div>
        </div>

        <!-- HERO -->
        <div id="kbf-home" class="kbf-hero" style="margin-top: 14px; margin-bottom: 90px;">
          <div class="kbf-hero-inner">

            <!-- Left: Text -->
            <div class="kbf-hero-left">
              <p class="kbf-eyebrow" style="margin:0; display:flex; align-items:center; gap:8px;">
                <span class="kbf-eyebrow-dot"></span> Filipino Crowdfunding Platform
              </p>

              <h1 class="kbf-hero-heading">
                Fundora:<br>
                Start a fund,<br>
                Change a Life.
              </h1>

              <p class="kbf-hero-desc">
                Why Filipinos are moving from Social Media donation posts to a platform designed for trust, transparency, and real accountability.
              </p>

              <div>
                <a class="kbf-btn kbf-hero-cta-btn" href="<?php echo esc_url($cta_url); ?>">
                  Start Supporting on Fundora
                  <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
                </a>
                <div class="kbf-hero-trust">
                  <span class="kbf-trust-badge">
                    <i class="ph-fill ph-check-circle" aria-hidden="true"></i>
                    ID Verified Organizers
                  </span>
                  <span class="kbf-trust-badge">
                    <i class="ph-fill ph-shield-check" aria-hidden="true"></i>
                    Transparent Tracking
                  </span>
                  <span class="kbf-trust-badge">
                    <i class="ph-fill ph-lock" aria-hidden="true"></i>
                    Secure Payments
                  </span>
                </div>
              </div>
            </div>

            <!-- Right: Floating phone cards -->
            <div class="kbf-hero-right" aria-hidden="true">
              <div class="kbf-cards-wrap">
                <div class="kbf-pcard kbf-pcard-tl">
                  <div class="kbf-pcard-img kbf-pimg-1">
                    <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/landing/patient.jpg'); ?>" alt="Patient support" width="120" height="150" loading="eager">
                  </div>
                  <div class="kbf-pcard-bar">
                    <div class="kbf-pcard-name">Yourself</div>
                  </div>
                </div>
                <div class="kbf-pcard kbf-pcard-main">
                  <div class="kbf-pcard-img kbf-pimg-2">
                    <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/landing/basketball.jpg'); ?>" alt="Basketball community" width="180" height="220" loading="eager">
                  </div>
                  <div class="kbf-pcard-bar">
                    <div class="kbf-pcard-name">Charity or Events</div>
                  </div>
                </div>
                <div class="kbf-pcard kbf-pcard-br">
                  <div class="kbf-pcard-img kbf-pimg-4">
                    <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/landing/graduation.jpg'); ?>" alt="Graduation moment" width="110" height="150" loading="eager">
                  </div>
                  <div class="kbf-pcard-bar">
                    <div class="kbf-pcard-name">Someone Else</div>
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
            <li><span></span>Why Social Media Fundraising Fails</li>
            <li><span></span>How Fundora Works</li>
            <li><span></span>Key Features That Build Trust</li>
            <li><span></span>Fundora vs. Social Media Fundraising</li>
            <li><span></span>Who Should Use Fundora?</li>
            <li><span></span>No Platform Fee During Beta</li>
            <li><span></span>The Future of Filipino Crowdfunding</li>
          </ul>
        </div>

        <!-- FEATURES -->
        <div id="kbf-how" class="kbf-section kbf-reveal delay-1" style="margin-top: 80px;">
          <h2 style="font-size: 1.5em; font-weight: 500;">Key features that build trust</h2>
          <p class="kbf-lead">The biggest barrier to online fundraising in the Philippines is not generosity. It is trust. Fundora is built so every campaign is verifiable and every organizer is accountable.</p>
          <div class="kbf-feature-grid">
            <div class="kbf-card kbf-card--soft">
                <div class="kbf-chip" aria-hidden="true">
                  <i class="ph ph-check-fat kbf-icon" aria-hidden="true"></i>
                </div>
               <h4 style="font-weight: 500;">Verified account profiles</h4>
               <p>Anyone raising funds completes ID verification before going live. A visible checkmark builds instant donor confidence.</p>
            </div>
            <div class="kbf-card kbf-card--glass">
              <div class="kbf-chip" aria-hidden="true">
                <i class="ph ph-circle-half-tilt kbf-icon" aria-hidden="true"></i>
              </div>
                <h4 style="font-weight: 500;">Transparent fund tracking</h4>
               <p>Every peso is logged. Organizers post receipts, photos, and spending breakdowns that donors can view anytime.</p>
            </div>
            <div class="kbf-card kbf-card--outline">
              <div class="kbf-chip" aria-hidden="true">
                  <i class="ph ph-book-open kbf-icon" aria-hidden="true"></i>
              </div>
                <h4 style="font-weight: 500;">Public update log</h4>
               <p>Updates are timestamped on the campaign page so supporters see progress, receipts, and outcomes in one place.</p>
            </div>
            <div class="kbf-card kbf-card--split">
              <div class="kbf-chip" aria-hidden="true">
                <i class="ph ph-megaphone kbf-icon" aria-hidden="true"></i>
              </div>
                <h4 style="font-weight: 500;">Campaign reporting system</h4>
               <p>Suspicious activity can be flagged directly. Reports are reviewed by the Fundora team to protect donors.</p>
            </div>
            <div class="kbf-card kbf-card--tint">
              <div class="kbf-chip" aria-hidden="true">
                <i class="ph ph-arrow-fat-line-up kbf-icon" aria-hidden="true"></i>
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
                <h4 style="font-weight: 500; margin: 0 0 6px;">For Anyone Starting a Campaign</h4>
                <ul class="kbf-list">
                  <li>Create a campaign for yourself, someone else, or a cause.</li>
                  <li>Share your link to group chats and social media.</li>
                  <li>Post updates with receipts and stories.</li>
                  <li>Receive funds after verification.</li>
                </ul>
              </div>
              <div class="kbf-card kbf-card--glass kbf-card--shine">
                <h4 style="font-weight: 500; margin: 0 0 6px;">For donors and supporters</h4>
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
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/landing/bayanihan.jpg'); ?>" alt="Bayanihan" loading="lazy" width="600" height="400">
            </div>
            <div class="kbf-about-card">
              <h2 style="font-size: 1.5em; font-weight: 500;">What is Fundora?</h2>
              <p>Fundora is a community-powered crowdfunding platform built for Filipino families, organizations, and communities. It supports local payment methods, a community-first experience, and the cultural value of bayanihan.</p>
              <p style="margin-top:10px;">Instead of informal posts, organizers get a dedicated campaign page with progress tracking, proof uploads, and an update log. Donors get confidence that their money is reaching the right person, for the right reason.</p>
              <div class="kbf-trust-badges" style="margin-top:20px; display:flex; gap:16px; flex-wrap:wrap;">
                <div class="kbf-trust-badge" style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; color:#0f172a;">
                  <i class="ph-fill ph-check-circle" style="font-size:18px; color:#3d8ef0;"></i>
                  <span>Maya Payments</span>
                </div>
                <div class="kbf-trust-badge" style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; color:#0f172a;">
                  <i class="ph-fill ph-check-circle" style="font-size:18px; color:#3d8ef0;"></i>
                  <span>Verified Organizers</span>
                </div>
                <div class="kbf-trust-badge" style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; color:#0f172a;">
                  <i class="ph-fill ph-check-circle" style="font-size:18px; color:#3d8ef0;"></i>
                  <span>Transparent Tracking</span>
                </div>
              </div>
            </div>
          </div>

          <div class="kbf-about-card" style="margin-top:18px;">
            <h2 style="font-size: 1.4em; font-weight: 500;">Why Social Media Fundraising Fails</h2>
            <p>Every Filipino has seen it: a relative posts their GCash number after a hospitalization. A neighbor shares a donation link after a house fire. The intention is real. The response is generous. But the system is broken.</p>
            <p style="margin-top:10px;">There is no way to confirm how much was raised or whether help arrived. Posts get buried. Families keep waiting. This is not a generosity problem. It is an infrastructure problem  -  and that is exactly what Fundora was built to solve.</p>
          </div>



          <div class="kbf-section" style="margin-top:18px;">
            <h2 style="font-size: 1.4em; font-weight: 500;">Fundora vs. Social Media Fundraising</h2>
            <p>Social media was built for connection. Fundora was built for community fundraising in the Philippines.</p>
            <div class="kbf-compare-table" style="text-align:left;">
              <div class="kbf-compare-row kbf-compare-head">
                <p style="margin:0;">Factor</p>
                <p style="margin:0;">Social Media donation posts</p>
                <p style="margin:0;">Fundora</p>
              </div>
              <div class="kbf-compare-row">
                <span class="kbf-strong">Campaign visibility</span>
                <p style="margin:0;">Posts get buried within days</p>
                <p style="margin:0;">Permanent, shareable campaign page</p>
              </div>
              <div class="kbf-compare-row">
                <span class="kbf-strong">Funds tracking</span>
                <p style="margin:0;">Manual tracking and receipts</p>
                <p style="margin:0;">Automatic progress tracking and logs</p>
              </div>
              <div class="kbf-compare-row">
                <span class="kbf-strong">Account verification</span>
                <p style="margin:0;">None</p>
                <p style="margin:0;">ID-verified with credibility index</p>
              </div>
              <div class="kbf-compare-row">
                <span class="kbf-strong">Update history</span>
                <p style="margin:0;">Scattered across posts</p>
                <p style="margin:0;">Timestamped update log</p>
              </div>
              <div class="kbf-compare-row">
                <span class="kbf-strong">Platform fee</span>
                <p style="margin:0;">Free</p>
                <p style="margin:0;">Free during beta</p>
              </div>
            </div>
          </div>

          <!-- Stats row -->
          <div class="kbf-about-stats">
            <div class="kbf-about-stat">
              <p class="kbf-about-stat-num" style="margin:0;">&#8369;0</p>
              <p class="kbf-about-stat-label" style="margin:0;">Platform fee during beta  -  100% to the person raising funds</p>
            </div>
            <div class="kbf-about-stat">
              <p class="kbf-about-stat-num" style="margin:0;">3x</p>
              <p class="kbf-about-stat-label" style="margin:0;">More raised with proof updates</p>
            </div>
            <div class="kbf-about-stat">
              <p class="kbf-about-stat-num" style="margin:0;">48h</p>
              <p class="kbf-about-stat-label" style="margin:0;">Average time to first donation</p>
            </div>
            <div class="kbf-about-stat">
              <p class="kbf-about-stat-num" style="margin:0;">100%</p>
              <p class="kbf-about-stat-label" style="margin:0;">Donations go directly to the person raising funds</p>
            </div>
          </div>

          <!-- Values row -->
          <div class="kbf-about-values">
            <div class="kbf-about-value">
              <div>
                <h4 class="kbf-about-value-title" style="margin:0 0 6px;">Rooted in bayanihan</h4>
                <p class="kbf-about-value-desc" style="margin:0;">We didn't invent community giving. We just built infrastructure worthy of it.</p>
              </div>
            </div>
            <div class="kbf-about-value">
              <div>
                <h4 class="kbf-about-value-title" style="margin:0 0 6px;">Radical transparency</h4>
                <p class="kbf-about-value-desc" style="margin:0;">Every peso tracked. Every organizer verified. No black holes.</p>
              </div>
            </div>
            <div class="kbf-about-value">
              <div>
                <h4 class="kbf-about-value-title" style="margin:0 0 6px;">Built for Filipinos</h4>
                <p class="kbf-about-value-desc" style="margin:0;">GCash, local banks, community-first  -  designed for how we actually live.</p>
              </div>
            </div>
          </div>
        </div>

        

        <!-- WHO SHOULD USE FUNDORA -->
        <div class="kbf-divider"></div>
        <div class="kbf-section kbf-stat kbf-reveal delay-2">
          <div class="kbf-photo-grid" aria-hidden="true">
            <img src="<?php echo esc_url($bw_1); ?>" alt="" loading="lazy" width="400" height="300">
            <img src="<?php echo esc_url($bw_2); ?>" alt="" loading="lazy" width="400" height="300">
            <img src="<?php echo esc_url($bw_3); ?>" alt="" loading="lazy" width="400" height="300">
            <img src="<?php echo esc_url($bw_4); ?>" alt="" loading="lazy" width="400" height="300">
          </div>
          <div class="kbf-stat-content">
            <p>Designed for trust</p>
            <h3 style="font-weight: 300;">Safe & Trusted</h3>
            <p>Reviewed. Verified. Transparent.</p>
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($join_url); ?>">Join Fundora - it's free</a>
          </div>
        </div>

        <!-- WHO SHOULD USE FUNDORA -->
        <div class="kbf-divider"></div>
        <div class="kbf-section kbf-reveal delay-2">
          <h2 style="font-size: 1.5em; font-weight: 400;">Who should use Fundora?</h2>
          <p class="kbf-lead">Fundora is built for anyone who needs to raise money from a Filipino community  -  and for donors who want confidence before giving.</p>
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
          <div class="kbf-footer-top">
            <div class="kbf-footer-left kbf-footer-brand">
              <div class="kbf-brand" style="margin-bottom:8px;">
                <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logobanner.png'); ?>" alt="fundora" style="width:auto;height:30px;object-fit:contain;border-radius:6px;filter:brightness(0) invert(1);">
              </div>
              <p>Community fundraising rooted in bayanihan.</p>
            </div>
            <div class="kbf-social">
              <a href="https://www.instagram.com/bntmtechnologiesinc/" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                <i class="ph ph-instagram-logo" aria-hidden="true"></i>
              </a>
              <a href="https://www.facebook.com/bentamosabentamo" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                <i class="ph ph-facebook-logo" aria-hidden="true"></i>
              </a>
              <a href="https://www.linkedin.com/company/bentamo/" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
                <i class="ph ph-linkedin-logo" aria-hidden="true"></i>
              </a>
            </div>
          </div>
          <div class="kbf-footer-bottom">
            <div class="kbf-footer-links">
              <a href="<?php echo esc_url(kbf_get_page_url('privacy')); ?>">Privacy Policy</a>
              <a href="<?php echo esc_url(kbf_get_page_url('terms')); ?>">Terms of Service</a>
              <a href="<?php echo esc_url(kbf_get_page_url('refund')); ?>">Refund Policy</a>
            </div>
            <small>&copy; fundora. All rights reserved.</small>
          </div>
        </footer>

        <!-- Cookie Consent Banner -->
        <div id="kbf-cookie-overlay" class="kbf-cookie-overlay"></div>
        <div id="kbf-cookie-banner" class="kbf-cookie-banner" role="dialog" aria-label="Cookie Consent">
            <button id="kbf-cookie-close" class="kbf-cookie-close" type="button" aria-label="Close">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
            <div class="kbf-cookie-inner">
                <div class="kbf-cookie-content">
                    <h3 class="kbf-cookie-title">Cookie Consent</h3>
                    <p class="kbf-cookie-message">By clicking "Accept All Cookies", you agree to the storing of cookies on your device to enhance site navigation, analyze site usage, and assist in our marketing efforts. <a href="<?php echo esc_url(kbf_get_page_url('privacy')); ?>">Privacy policy</a></p>
                </div>
                <div class="kbf-cookie-actions">
                    <button id="kbf-cookie-manage" class="kbf-cookie-btn kbf-cookie-manage" type="button">Manage cookies</button>
                    <button id="kbf-cookie-accept" class="kbf-cookie-btn kbf-cookie-accept" type="button">Accept all</button>
                </div>
            </div>
        </div>

      </div>
    </section>
    <!-- ================== JS ================== -->
    <script>
    (function () {
        // Disable automatic scroll restoration and force scroll to top on page load/refresh
        if ('scrollRestoration' in window.history) {
            window.history.scrollRestoration = 'manual';
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

        // -- Section reveals ------------------------------------------
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

        // -- Stat number count-up -------------------------------------
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

        /**
         * @function  animateStatNumbers
         * @purpose   Animates counter numbers with easing effect.
         * @used-by   statsIO IntersectionObserver callback
         * @calls     requestAnimationFrame
         * @params    DOMElement container — Element containing stat numbers
         * @returns   void
         * @status    ACTIVE
         */
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

        // -- Smooth scroll nav links (replaces onclick handlers) --
        document.querySelectorAll('[data-kbf-scroll]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-kbf-scroll');
                if (id === 'kbf-home') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    var target = document.getElementById(id);
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // -- Mobile nav links (replaces onclick handlers) --
        document.querySelectorAll('[data-kbf-mobile-nav]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-kbf-mobile-nav');
                // Close mobile menu
                var menu = document.getElementById('kbf-mobile-menu');
                var overlay = document.getElementById('kbf-mobile-overlay');
                var icon = document.getElementById('kbf-hamburger-icon');
                if (menu) menu.classList.remove('kbf-menu-open');
                if (overlay) overlay.classList.remove('kbf-overlay-open');
                if (icon) {
                    icon.classList.remove('ph-bold','ph-x');
                    icon.classList.add('ph','ph-list');
                }
                // Scroll to target
                if (id === 'kbf-home') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    var target = document.getElementById(id);
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // -- Hamburger menu toggle --
        var hamburgerBtn = document.getElementById('kbf-hamburger-btn');
        var menu = document.getElementById('kbf-mobile-menu');
        var menuOverlay = document.getElementById('kbf-mobile-overlay');
        var hamburgerIcon = document.getElementById('kbf-hamburger-icon');
        if (hamburgerBtn && menu) {
            var menuOpen = false;
            /**
             * @function  setIcon
             * @purpose   Updates hamburger menu icon classes based on open state.
             * @used-by   openMobileMenu, closeMobileMenu
             * @calls     None
             * @params    boolean stateOpen — True for open (X icon), false for closed (hamburger icon)
             * @returns   void
             * @status    ACTIVE
             */
            function setIcon(stateOpen) {
                if (!hamburgerIcon) return;
                hamburgerIcon.className = '';
                var classes = stateOpen ? ['ph','ph-x'] : ['ph','ph-list'];
                classes.forEach(function(c) { hamburgerIcon.classList.add(c); });
            }
            /**
             * @function  openMobileMenu
             * @purpose   Opens mobile navigation menu and updates icon.
             * @used-by   hamburgerBtn click handler
             * @calls     setIcon
             * @params    none
             * @returns   void
             * @status    ACTIVE
             */
            function openMobileMenu() {
                menuOpen = true;
                menu.classList.add('kbf-menu-open');
                if (menuOverlay) menuOverlay.classList.add('kbf-overlay-open');
                setIcon(true);
            }
            /**
             * @function  closeMobileMenu
             * @purpose   Closes mobile navigation menu and resets icon.
             * @used-by   hamburgerBtn click handler, overlay click handler, resize event
             * @calls     setIcon
             * @params    none
             * @returns   void
             * @status    ACTIVE
             */
            function closeMobileMenu() {
                menuOpen = false;
                menu.classList.remove('kbf-menu-open');
                if (menuOverlay) menuOverlay.classList.remove('kbf-overlay-open');
                setIcon(false);
            }
            hamburgerBtn.addEventListener('click', function() {
                menuOpen ? closeMobileMenu() : openMobileMenu();
            });
            if (menuOverlay) menuOverlay.addEventListener('click', closeMobileMenu);
            window.addEventListener('resize', function() {
                if (window.innerWidth > 900 && menuOpen) closeMobileMenu();
            });
        }

        // -- Sticky navbar scroll effect --
        var topbar = document.querySelector('.kbf-topbar');
        if (topbar) {
            var lastScrolled = false;
            window.addEventListener('scroll', function() {
                var isScrolled = window.scrollY > 10;
                if (isScrolled !== lastScrolled) {
                    topbar.classList.toggle('kbf-topbar-scrolled', isScrolled);
                    lastScrolled = isScrolled;
                }
            }, { passive: true });
        }
    })();

    // Scroll-jack compare table: horizontal scroll first, then resume vertical
    (function() {
        var table = document.querySelector('.kbf-compare-table');
        if (!table) return;
        /**
         * @function  inView
         * @purpose   Checks if element is currently visible in viewport.
         * @used-by   Compare table scroll-jack IIFE
         * @calls     getBoundingClientRect
         * @params    DOMElement el — Element to check
         * @returns   boolean True if element overlaps with viewport
         * @status    ACTIVE
         */
        function inView(el) {
            var r = el.getBoundingClientRect();
            return r.top < window.innerHeight && r.bottom > 0;
        }
        table.addEventListener('wheel', function(e) {
            if (window.innerWidth >= 720) return;
            if (!inView(table)) return;
            var maxScroll = table.scrollWidth - table.clientWidth;
            if (maxScroll <= 0) return;
            var delta = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY;
            var next = table.scrollLeft + delta;
            var atStart = table.scrollLeft <= 0 && delta < 0;
            var atEnd = table.scrollLeft >= maxScroll && delta > 0;
            if (!atStart && !atEnd) {
                e.preventDefault();
                table.scrollLeft = Math.max(0, Math.min(maxScroll, next));
            }
        }, { passive: false });
    })();

    // Hamburger menu functionality
    (function(){
        var btn = document.getElementById('kbf-hamburger-btn');
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        var icon = document.getElementById('kbf-hamburger-icon');
        if (!btn || !menu || !overlay) return;
        var open = false;
        function setIcon(stateOpen){
          if (!icon) return;
          var openCls = ['ph','ph-x'];
          var closeCls = ['ph','ph-list'];
          icon.classList.remove.apply(icon.classList, openCls);
          icon.classList.remove.apply(icon.classList, closeCls);
          icon.classList.add.apply(icon.classList, stateOpen ? openCls : closeCls);
        }
        function openMenu(){
          open = true;
          menu.classList.add('kbf-menu-open');
          overlay.classList.add('kbf-overlay-open');
          setIcon(true);
        }
        function closeMenu(){
          open = false;
          menu.classList.remove('kbf-menu-open');
          overlay.classList.remove('kbf-overlay-open');
          setIcon(false);
        }
        btn.addEventListener('click', function(){
          if (open) closeMenu();
          else openMenu();
        });
        if (overlay) overlay.addEventListener('click', closeMenu);
        window.addEventListener('resize', function(){
          if (window.innerWidth > 900 && open) closeMenu();
        });
    })();

    // Topbar scroll effect
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
    </script>

    <!-- Cookie Consent Logic -->
    <script>
    (function() {
        var STORAGE_KEY = 'kbf_cookie_consent';
        var overlay = document.getElementById('kbf-cookie-overlay');
        var banner = document.getElementById('kbf-cookie-banner');
        var acceptBtn = document.getElementById('kbf-cookie-accept');
        var manageBtn = document.getElementById('kbf-cookie-manage');
        var closeBtn = document.getElementById('kbf-cookie-close');
        if (!overlay || !banner || !acceptBtn) return;

        /**
         * @function  setCookieState
         * @purpose   Saves cookie consent choice to localStorage.
         * @used-by   acceptBtn click handler, manageBtn click handler, closeBtn click handler
         * @calls     localStorage.setItem
         * @params    string state — 'accepted' or 'declined'
         * @returns   void
         * @status    ACTIVE
         */
        function setCookieState(state) {
            localStorage.setItem(STORAGE_KEY, state);
        }

        /**
         * @function  showBanner
         * @purpose   Displays cookie consent banner with fade-in animation.
         * @used-by   Cookie consent IIFE (on page load if no consent exists)
         * @calls     requestAnimationFrame
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function showBanner() {
            overlay.style.display = 'block';
            banner.style.display = 'block';
            requestAnimationFrame(function() {
                overlay.classList.add('kbf-cookie-overlay-visible');
                banner.classList.add('kbf-cookie-visible');
            });
        }

        /**
         * @function  hideBanner
         * @purpose   Hides cookie consent banner with slide-out animation.
         * @used-by   acceptBtn click handler, manageBtn click handler, closeBtn click handler
         * @calls     setTimeout
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function hideBanner() {
            overlay.classList.remove('kbf-cookie-overlay-visible');
            banner.classList.remove('kbf-cookie-visible');
            banner.classList.add('kbf-cookie-hidden');
            setTimeout(function() {
                overlay.style.display = 'none';
                banner.style.display = 'none';
                banner.classList.remove('kbf-cookie-hidden');
            }, 350);
        }

        // Show banner if no previous choice
        var existing = localStorage.getItem(STORAGE_KEY);
        if (!existing) {
            showBanner();
        }

        // Accept all
        acceptBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            setCookieState('accepted');
            hideBanner();
        });

        // Manage cookies — for now same as accept; can be extended
        if (manageBtn) {
            manageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                setCookieState('accepted');
                hideBanner();
            });
        }

        // Close button (same as accept)
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                setCookieState('accepted');
                hideBanner();
            });
        }

        // Close button (same as accept)
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                setCookieState('accepted');
                hideBanner();
            });
        }

        // Click overlay to dismiss
        overlay.addEventListener('click', function() {
            setCookieState('accepted');
            hideBanner();
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}