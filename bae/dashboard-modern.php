if (!defined('ABSPATH')) exit;
<?php

function bae_render_modern_dashboard($user_id, $profile) {
    if (empty($profile)) return bae_overview_tab($user_id, $profile);
    global $wpdb;

    $base = strtok($_SERVER['REQUEST_URI'], '?');
    $plan = bae_get_user_plan($user_id, $profile);
    $theme_now = (!empty($_COOKIE['bae_theme']) && $_COOKIE['bae_theme'] === 'light') ? 'light' : 'dark';
    $asset_count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}bae_assets WHERE profile_id = %d AND is_generated = 1",
        $profile['id']
    ));
    $recent_assets = $wpdb->get_results($wpdb->prepare(
        "SELECT asset_name, asset_type, updated_at FROM {$wpdb->prefix}bae_assets WHERE profile_id = %d AND is_generated = 1 ORDER BY updated_at DESC, id DESC LIMIT 4",
        $profile['id']
    ), ARRAY_A);

    $name = trim($profile['business_name'] ?? '');
    $tagline = trim($profile['tagline'] ?? '');
    $greeting_hour = (int) current_time('G');
    $greeting = $greeting_hour < 12 ? 'Good Morning' : ($greeting_hour < 18 ? 'Good Afternoon' : 'Good Evening');
    $workspace_name = $name !== '' ? $name : 'Brand workspace';
    $workspace_tagline = $tagline !== '' ? $tagline : 'Let's keep mothifying your brand with a cleaner, calmer workspace.';
    $plan_label = ucfirst($plan ?: 'free');
    $recent_total = is_array($recent_assets) ? count($recent_assets) : 0;

    $initials = 'BA';
    if ($name !== '') {
        $parts = preg_split('/\s+/', $name);
        $picked = '';
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') continue;
            $picked .= strtoupper(substr($part, 0, 1));
            if (strlen($picked) >= 2) break;
        }
        $initials = $picked !== '' ? substr($picked, 0, 2) : (strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 2)) ?: 'BA');
    }

    $actions = [
        ['slug' => 'assets', 'title' => 'Create Assets', 'desc' => 'Generate your first brand outputs and keep everything consistent.', 'grad' => 'linear-gradient(135deg,#6d28d9 0%,#8b5cf6 55%,#4f46e5 100%)', 'icon' => '<path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 2H8l-2 5h12z"/>'],
        ['slug' => 'kit', 'title' => 'Open Brand Kit', 'desc' => 'Preview the shareable brand page your team or clients will see.', 'grad' => 'linear-gradient(135deg,#f97316 0%,#fb923c 52%,#f59e0b 100%)', 'icon' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>'],
        ['slug' => 'startup', 'title' => 'Launch Toolkit', 'desc' => 'Stay on top of names, handles, and the rollout checklist.', 'grad' => 'linear-gradient(135deg,#db2777 0%,#ec4899 50%,#f43f5e 100%)', 'icon' => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>'],
        ['slug' => 'brand_book', 'title' => 'Brand Book', 'desc' => 'View your polished visual system and ready-to-use pages.', 'grad' => 'linear-gradient(135deg,#22c55e 0%,#84cc16 48%,#10b981 100%)', 'icon' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 0 0 1 0-5H20"/><path d="M8 7h6M8 11h8"/>'],
    ];

    $nav = [
        ['slug' => 'dashboard', 'label' => 'Dashboard', 'sub' => 'Overview', 'icon' => '<path d="M4 13.5V20h6.5v-6.5H4Zm9.5 0V20H20v-6.5h-6.5ZM4 4v6.5h6.5V4H4Zm9.5 0v6.5H20V4h-6.5Z"/>'],
        ['slug' => 'overview', 'label' => 'Brand Profile', 'sub' => 'Identity', 'icon' => '<path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/><path d="M5 21a7 7 0 0 1 14 0"/>'],
        ['slug' => 'assets', 'label' => 'Assets', 'sub' => 'Generate', 'icon' => '<path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z"/><path d="M16 2H8l-2 5h12Z"/>'],
        ['slug' => 'kit', 'label' => 'Brand Kit', 'sub' => 'Share', 'icon' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 0 0 1 0-5H20"/>'],
        ['slug' => 'startup', 'label' => 'Launch', 'sub' => 'Checklist', 'icon' => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09Z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2Z"/>'],
        ['slug' => 'brand_book', 'label' => 'Brand Book', 'sub' => 'System', 'icon' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 0 0 1 0-5H20"/><path d="M8 7h6M8 11h8"/>'],
        ['slug' => 'settings', 'label' => 'Settings', 'sub' => 'Plan', 'icon' => '<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
    ];

    $pc = bae_safe_color($profile['primary_color'] ?? '#1a1a2e', '#1a1a2e');
    $sc = bae_safe_color($profile['secondary_color'] ?? '#16213e', '#16213e');
    $ac = bae_safe_color($profile['accent_color'] ?? '#e94560', '#e94560');
    $logo_markup = function_exists('bae_render_logo_lockup') ? bae_render_logo_lockup($profile, ['compact' => true, 'dark' => $theme_now === 'dark']) : '';

    ob_start(); ?>
    <style>
        .bae-dashboard-shell{position:relative;border:1px solid var(--border);border-radius:34px;overflow:hidden;box-shadow:0 34px 90px rgba(15,23,42,.10);background:radial-gradient(circle at 12% 10%,rgba(139,92,246,.16),transparent 30%),radial-gradient(circle at 88% 16%,rgba(236,72,153,.10),transparent 24%),linear-gradient(180deg,rgba(255,255,255,.88),rgba(247,245,255,.96))}
        .bae-wrap:not(.bae-light) .bae-dashboard-shell{background:radial-gradient(circle at 12% 10%,rgba(139,92,246,.16),transparent 32%),radial-gradient(circle at 88% 16%,rgba(236,72,153,.10),transparent 24%),linear-gradient(180deg,rgba(20,18,33,.96),rgba(10,10,16,.98));box-shadow:0 34px 96px rgba(0,0,0,.35)}
        .bae-dashboard-shell:before{content:'';position:absolute;inset:0;pointer-events:none;background:linear-gradient(120deg,rgba(255,255,255,.18),transparent 26%),linear-gradient(0deg,rgba(255,255,255,.08),transparent 32%);opacity:.7}
        .bae-dashboard-app{position:relative;display:grid;grid-template-columns:280px minmax(0,1fr);min-height:820px;backdrop-filter:blur(18px)}
        .bae-dashboard-rail{position:relative;padding:22px 18px 20px;border-right:1px solid var(--border);background:rgba(255,255,255,.48)}
        .bae-wrap:not(.bae-light) .bae-dashboard-rail{background:rgba(255,255,255,.035)}
        .bae-dashboard-rail:after{content:'';position:absolute;inset:14px 12px;border-radius:26px;border:1px solid rgba(255,255,255,.28);pointer-events:none;opacity:.42}
        .bae-wrap:not(.bae-light) .bae-dashboard-rail:after{border-color:rgba(255,255,255,.05)}
        .bae-dashboard-rail-inner,.bae-dashboard-column,.bae-dashboard-brandcard,.bae-dashboard-stack,.bae-dashboard-assetlist,.bae-dashboard-list{display:grid;gap:18px}
        .bae-dashboard-rail-inner{position:relative;z-index:1;height:100%}
        .bae-dashboard-brand,.bae-dashboard-rail-foot,.bae-dashboard-panel,.bae-dashboard-side-stat,.bae-dashboard-quicknote{border:1px solid var(--border);background:rgba(255,255,255,.66);box-shadow:0 16px 30px rgba(15,23,42,.06)}
        .bae-wrap:not(.bae-light) .bae-dashboard-brand,.bae-wrap:not(.bae-light) .bae-dashboard-rail-foot,.bae-wrap:not(.bae-light) .bae-dashboard-panel,.bae-wrap:not(.bae-light) .bae-dashboard-side-stat{background:rgba(255,255,255,.04);box-shadow:0 16px 30px rgba(0,0,0,.18)}
        .bae-dashboard-brand{display:flex;align-items:center;gap:14px;padding:12px;border-radius:22px}
        .bae-dashboard-mark,.bae-dashboard-avatar-badge{width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--brand),var(--pink));color:#fff;font-family:'Instrument Serif',serif;font-size:18px;box-shadow:0 12px 24px rgba(139,92,246,.22);overflow:hidden}
        .bae-dashboard-brand-copy strong,.bae-dashboard-avatar strong,.bae-dashboard-section-head h3,.bae-dashboard-row strong,.bae-dashboard-asset strong,.bae-dashboard-mini b,.bae-dashboard-quicknote strong{display:block;color:var(--text)}
        .bae-dashboard-brand-copy span,.bae-dashboard-nav-copy span,.bae-dashboard-rail-foot span,.bae-dashboard-crumbs,.bae-dashboard-search,.bae-dashboard-section-head p,.bae-dashboard-row span,.bae-dashboard-asset span,.bae-dashboard-asset .when,.bae-dashboard-mini span,.bae-dashboard-side-stat small{color:var(--text-3)}
        .bae-dashboard-nav{display:grid;gap:8px}
        .bae-dashboard-nav-item,.bae-dashboard-nav-current{display:grid;grid-template-columns:42px minmax(0,1fr);gap:12px;align-items:center;min-height:54px;padding:8px 12px;border-radius:18px;text-decoration:none;color:var(--text-2);border:1px solid transparent;transition:all .22s ease}
        .bae-dashboard-nav-item:hover{color:var(--text);background:rgba(139,92,246,.08);border-color:rgba(139,92,246,.14)}
        .bae-dashboard-nav-current{color:var(--text);background:linear-gradient(135deg,rgba(139,92,246,.16),rgba(236,72,153,.07));border-color:rgba(139,92,246,.22);box-shadow:0 14px 28px rgba(139,92,246,.12)}
        .bae-dashboard-nav-icon{width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.58)}
        .bae-wrap:not(.bae-light) .bae-dashboard-nav-icon{background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.05)}
        .bae-dashboard-nav-icon svg{width:18px;height:18px;stroke-width:2}
        .bae-dashboard-rail-foot{margin-top:auto;padding:16px;border-radius:24px}
        .bae-dashboard-main{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(300px,.72fr);gap:18px;padding:18px}
        .bae-dashboard-panel,.bae-dashboard-side-stat,.bae-dashboard-quicknote{position:relative;padding:22px;border-radius:28px;overflow:hidden}
        .bae-dashboard-panel:before{content:'';position:absolute;inset:0 auto auto 0;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(139,92,246,.09),transparent 70%);pointer-events:none}
        .bae-dashboard-topline,.bae-dashboard-section-head,.bae-dashboard-hero,.bae-dashboard-row,.bae-dashboard-asset{display:grid;gap:12px}
        .bae-dashboard-topline{grid-template-columns:minmax(0,1fr) auto;align-items:center;margin-bottom:18px}
        .bae-dashboard-crumbs{display:flex;align-items:center;gap:8px;font-size:11px;letter-spacing:.08em;text-transform:uppercase}
        .bae-dashboard-crumbs .dot{width:4px;height:4px;border-radius:50%;background:currentColor;opacity:.55}
        .bae-dashboard-search{width:min(290px,100%);display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:999px;border:1px solid var(--border);background:rgba(255,255,255,.62);font-size:12px}
        .bae-wrap:not(.bae-light) .bae-dashboard-search{background:rgba(255,255,255,.03)}
        .bae-dashboard-hero{grid-template-columns:minmax(0,1fr) 220px;align-items:start}
        .bae-dashboard-kicker{display:inline-flex;align-items:center;gap:8px;padding:7px 12px;border-radius:999px;background:rgba(139,92,246,.12);color:var(--brand-deep);font-size:11px;font-weight:700;width:max-content}
        .bae-wrap:not(.bae-light) .bae-dashboard-kicker{color:var(--brand-soft)}
        .bae-dashboard-hero-copy{display:grid;gap:14px}
        .bae-dashboard-hero-copy h1{font-size:clamp(28px,3vw,42px);line-height:1.02;color:var(--text)}
        .bae-dashboard-hero-copy p,.bae-dashboard-side-stat span,.bae-dashboard-quicknote p{font-size:13px;line-height:1.7;color:var(--text-2)}
        .bae-dashboard-hero-actions{display:flex;flex-wrap:wrap;gap:10px}
        .bae-dashboard-btn,.bae-dashboard-link,.bae-dashboard-chip{display:inline-flex;align-items:center;text-decoration:none;font-size:12px;font-weight:700}
        .bae-dashboard-btn{gap:8px;min-height:42px;padding:0 16px;border-radius:14px;border:1px solid transparent;transition:all .2s ease}
        .bae-dashboard-btn.primary{color:#fff;background:linear-gradient(135deg,var(--brand-deep),var(--brand));box-shadow:0 14px 26px rgba(109,40,217,.24)}
        .bae-dashboard-btn.secondary{color:var(--text);border-color:var(--border);background:rgba(255,255,255,.58)}
        .bae-wrap:not(.bae-light) .bae-dashboard-btn.secondary{background:rgba(255,255,255,.04)}
        .bae-dashboard-avatar{display:flex;align-items:center;gap:12px;padding:14px;border-radius:22px;border:1px solid var(--border);background:rgba(255,255,255,.62)}
        .bae-wrap:not(.bae-light) .bae-dashboard-avatar{background:rgba(255,255,255,.03)}
        .bae-dashboard-avatar span{display:block;font-size:11px;color:var(--text-3);margin-top:4px}
        .bae-dashboard-stat-grid,.bae-dashboard-action-grid,.bae-dashboard-grid-two,.bae-dashboard-brandmeta{display:grid;gap:12px}
        .bae-dashboard-stat-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
        .bae-dashboard-stat,.bae-dashboard-mini{padding:16px;border-radius:22px;border:1px solid var(--border);background:rgba(255,255,255,.56)}
        .bae-wrap:not(.bae-light) .bae-dashboard-stat,.bae-wrap:not(.bae-light) .bae-dashboard-mini,.bae-wrap:not(.bae-light) .bae-dashboard-brandmark,.bae-wrap:not(.bae-light) .bae-dashboard-asset,.bae-wrap:not(.bae-light) .bae-dashboard-row{background:rgba(255,255,255,.03)}
        .bae-dashboard-stat span{display:block;font-size:11px;margin-bottom:8px}
        .bae-dashboard-stat strong{display:block;font-size:24px;line-height:1;color:var(--text);margin-bottom:4px}
        .bae-dashboard-stat small{font-size:11px;color:var(--text-2)}
        .bae-dashboard-section-head{grid-template-columns:minmax(0,1fr) auto;align-items:start;margin-bottom:16px}
        .bae-dashboard-link{gap:6px;color:var(--brand-soft);white-space:nowrap}
        .bae-dashboard-action-grid,.bae-dashboard-grid-two,.bae-dashboard-brandmeta{grid-template-columns:repeat(2,minmax(0,1fr))}
        .bae-dashboard-action{min-height:188px;padding:20px;border-radius:24px;color:#fff;text-decoration:none;position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:flex-end;box-shadow:0 20px 34px rgba(0,0,0,.14);transition:transform .25s ease,box-shadow .25s ease}
        .bae-dashboard-action:hover{transform:translateY(-4px);box-shadow:0 26px 42px rgba(0,0,0,.18)}
        .bae-dashboard-action:before,.bae-dashboard-action:after{content:'';position:absolute;border-radius:50%;background:rgba(255,255,255,.12);filter:blur(10px)}
        .bae-dashboard-action:before{top:-18px;right:-10px;width:150px;height:150px}
        .bae-dashboard-action:after{bottom:-38px;left:-20px;width:190px;height:190px}
        .bae-dashboard-action-icon{width:46px;height:46px;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.16);margin-bottom:16px;position:relative;z-index:1}
        .bae-dashboard-action h4,.bae-dashboard-action p{position:relative;z-index:1}
        .bae-dashboard-action h4{font-size:18px;line-height:1.1;margin-bottom:10px}
        .bae-dashboard-action p{font-size:12px;line-height:1.65;color:rgba(255,255,255,.82)}
        .bae-dashboard-row,.bae-dashboard-asset{grid-template-columns:minmax(0,1fr) auto;align-items:center;padding:16px;border-radius:20px;border:1px solid var(--border);background:rgba(255,255,255,.56);box-shadow:0 12px 24px rgba(15,23,42,.04)}
        .bae-wrap:not(.bae-light) .bae-dashboard-row{box-shadow:0 12px 24px rgba(0,0,0,.14)}
        .bae-dashboard-chip{justify-content:center;padding:8px 10px;border-radius:999px;color:var(--brand-deep);background:rgba(139,92,246,.10);border:1px solid rgba(139,92,246,.14)}
        .bae-wrap:not(.bae-light) .bae-dashboard-chip{color:var(--brand-soft)}
        .bae-dashboard-side-stat small{display:block;font-size:11px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px}
        .bae-dashboard-side-stat strong{display:block;font-size:32px;color:var(--text);line-height:1;margin-bottom:8px}
        .bae-dashboard-brandmark{padding:16px;border-radius:20px;border:1px solid var(--border);background:rgba(255,255,255,.55);min-height:112px;display:flex;align-items:center}
        .bae-dashboard-mini{padding:14px;border-radius:18px}
        .bae-dashboard-swatches{display:flex;gap:8px;align-items:center;margin-bottom:8px}
        .bae-dashboard-swatch{width:24px;height:24px;border-radius:8px;border:1px solid rgba(0,0,0,.08);box-shadow:inset 0 1px 1px rgba(255,255,255,.18)}
        .bae-dashboard-quicknote{border-style:dashed;border-color:rgba(139,92,246,.28);background:linear-gradient(135deg,rgba(139,92,246,.08),rgba(236,72,153,.05))}
        .bae-dashboard-quicknote p{margin:8px 0 12px}
        @media (max-width:1220px){.bae-dashboard-app{grid-template-columns:1fr}.bae-dashboard-rail{border-right:0;border-bottom:1px solid var(--border)}.bae-dashboard-main{grid-template-columns:1fr}}
        @media (max-width:920px){.bae-dashboard-topline,.bae-dashboard-hero,.bae-dashboard-grid-two,.bae-dashboard-section-head,.bae-dashboard-action-grid,.bae-dashboard-brandmeta,.bae-dashboard-stat-grid{grid-template-columns:1fr}.bae-dashboard-search{width:100%}}
        @media (max-width:720px){.bae-dashboard-shell{border-radius:24px}.bae-dashboard-main{padding:14px}.bae-dashboard-panel,.bae-dashboard-side-stat,.bae-dashboard-quicknote{padding:18px}.bae-dashboard-row,.bae-dashboard-asset{grid-template-columns:1fr}}
    </style>

    <div class="bae-dashboard-shell">
        <div class="bae-dashboard-app">
            <aside class="bae-dashboard-rail">
                <div class="bae-dashboard-rail-inner">
                    <div class="bae-dashboard-brand">
                        <div class="bae-dashboard-mark"><?php echo $logo_markup !== '' ? $logo_markup : esc_html($initials); ?></div>
                        <div class="bae-dashboard-brand-copy">
                            <strong><?php echo esc_html($workspace_name); ?></strong>
                            <span><?php echo esc_html($plan_label); ?> plan</span>
                        </div>
                    </div>
                    <nav class="bae-dashboard-nav" aria-label="Dashboard navigation">
                        <?php foreach ($nav as $item): ?>
                            <?php $is_current = $item['slug'] === 'dashboard'; ?>
                            <?php if ($is_current): ?>
                                <span class="bae-dashboard-nav-current">
                                    <span class="bae-dashboard-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><?php echo $item['icon']; ?></svg></span>
                                    <span class="bae-dashboard-nav-copy">
                                        <strong><?php echo esc_html($item['label']); ?></strong>
                                        <span><?php echo esc_html($item['sub']); ?></span>
                                    </span>
                                </span>
                            <?php else: ?>
                                <a class="bae-dashboard-nav-item" href="<?php echo esc_url($base . '?tab=' . $item['slug']); ?>">
                                    <span class="bae-dashboard-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><?php echo $item['icon']; ?></svg></span>
                                    <span class="bae-dashboard-nav-copy">
                                        <strong><?php echo esc_html($item['label']); ?></strong>
                                        <span><?php echo esc_html($item['sub']); ?></span>
                                    </span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                    <div class="bae-dashboard-rail-foot">
                        <strong>Workspace status</strong>
                        <span><?php echo esc_html($asset_count); ?> generated assets ready. Your profile, kit, and launch tools stay one click away.</span>
                    </div>
                </div>
            </aside>

            <div class="bae-dashboard-main">
                <div class="bae-dashboard-column">
                    <section class="bae-dashboard-panel">
                        <div class="bae-dashboard-topline">
                            <div class="bae-dashboard-crumbs"><span>BAE</span><span class="dot"></span><span>Dashboard</span><span class="dot"></span><span><?php echo esc_html($plan_label); ?> workspace</span></div>
                            <div class="bae-dashboard-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg><span><?php echo esc_html($workspace_name); ?> workspace overview</span></div>
                        </div>
                        <div class="bae-dashboard-hero">
                            <div class="bae-dashboard-hero-copy">
                                <span class="bae-dashboard-kicker"><?php echo esc_html($greeting); ?></span>
                                <h1><?php echo esc_html($workspace_name); ?></h1>
                                <p><?php echo esc_html($workspace_tagline); ?></p>
                                <div class="bae-dashboard-hero-actions">
                                    <a class="bae-dashboard-btn primary" href="<?php echo esc_url($base . '?tab=assets'); ?>">Create assets <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></a>
                                    <a class="bae-dashboard-btn secondary" href="<?php echo esc_url($base . '?tab=overview'); ?>">Open brand profile</a>
                                </div>
                            </div>
                            <div class="bae-dashboard-avatar">
                                <div class="bae-dashboard-avatar-badge"><?php echo esc_html($initials); ?></div>
                                <div><strong><?php echo esc_html($workspace_name); ?></strong><span><?php echo esc_html($plan_label); ?> plan | BAE dashboard</span></div>
                            </div>
                        </div>
                    </section>

                    <section class="bae-dashboard-panel">
                        <div class="bae-dashboard-stat-grid">
                            <div class="bae-dashboard-stat"><span>Plan</span><strong><?php echo esc_html($plan_label); ?></strong><small>Current workspace access</small></div>
                            <div class="bae-dashboard-stat"><span>Assets</span><strong><?php echo esc_html($asset_count); ?></strong><small>Generated outputs available</small></div>
                            <div class="bae-dashboard-stat"><span>Status</span><strong><?php echo esc_html(!empty($name) ? 'Ready' : 'Setup'); ?></strong><small>Brand workspace condition</small></div>
                        </div>
                    </section>

                    <section class="bae-dashboard-panel">
                        <div class="bae-dashboard-section-head">
                            <div><h3>Core Workspace</h3><p>The same BAE tools, reorganized into a cleaner dashboard flow.</p></div>
                            <a class="bae-dashboard-link" href="<?php echo esc_url($base . '?tab=brand_book'); ?>">Open brand book</a>
                        </div>
                        <div class="bae-dashboard-action-grid">
                            <?php foreach ($actions as $action): ?>
                                <a class="bae-dashboard-action" href="<?php echo esc_url($base . '?tab=' . $action['slug']); ?>" style="background:<?php echo esc_attr($action['grad']); ?>;">
                                    <span class="bae-dashboard-action-icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $action['icon']; ?></svg></span>
                                    <h4><?php echo esc_html($action['title']); ?></h4>
                                    <p><?php echo esc_html($action['desc']); ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <div class="bae-dashboard-grid-two">
                        <section class="bae-dashboard-panel">
                            <div class="bae-dashboard-section-head">
                                <div><h3>Latest Assets</h3><p>Your most recent generated outputs.</p></div>
                                <a class="bae-dashboard-link" href="<?php echo esc_url($base . '?tab=assets'); ?>">Open assets</a>
                            </div>
                            <div class="bae-dashboard-assetlist">
                                <?php if (!empty($recent_assets)): foreach ($recent_assets as $asset): ?>
                                    <a class="bae-dashboard-asset" href="<?php echo esc_url($base . '?tab=assets'); ?>">
                                        <div>
                                            <strong><?php echo esc_html($asset['asset_name'] ?: ucwords(str_replace('_', ' ', $asset['asset_type']))); ?></strong>
                                            <span><?php echo esc_html(ucwords(str_replace('_', ' ', $asset['asset_type']))); ?></span>
                                        </div>
                                        <div class="when"><?php echo esc_html(mysql2date('M j, Y', $asset['updated_at'])); ?></div>
                                    </a>
                                <?php endforeach; else: ?>
                                    <div class="bae-dashboard-asset"><div><strong>No assets yet</strong><span>Generate your first set from the Asset Generator.</span></div></div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="bae-dashboard-panel">
                            <div class="bae-dashboard-section-head">
                                <div><h3>Next Steps</h3><p>Move through the workspace without changing the content structure.</p></div>
                                <a class="bae-dashboard-link" href="<?php echo esc_url($base . '?tab=settings'); ?>">Settings</a>
                            </div>
                            <div class="bae-dashboard-list">
                                <a class="bae-dashboard-row" href="<?php echo esc_url($base . '?tab=overview'); ?>"><div><strong>Refine your brand profile</strong><span>Keep your identity, fonts, and details current before generating more assets.</span></div><span class="bae-dashboard-chip">Profile</span></a>
                                <a class="bae-dashboard-row" href="<?php echo esc_url($base . '?tab=kit'); ?>"><div><strong>Review your Brand Kit</strong><span>Check the shareable kit experience and keep your handoff polished.</span></div><span class="bae-dashboard-chip">Kit</span></a>
                                <a class="bae-dashboard-row" href="<?php echo esc_url($base . '?tab=startup'); ?>"><div><strong>Continue launch planning</strong><span>Track names, links, and launch tasks from the same dashboard flow.</span></div><span class="bae-dashboard-chip">Launch</span></a>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="bae-dashboard-column">
                    <section class="bae-dashboard-side-stat">
                        <small>Workspace score</small>
                        <strong><?php echo esc_html($asset_count > 0 ? 'Ready' : 'Start'); ?></strong>
                        <span><?php echo esc_html($asset_count > 0 ? 'Your brand workspace already has generated outputs and is ready for refinement.' : 'Your dashboard is set up. Create your first asset to activate the full flow.'); ?></span>
                    </section>

                    <section class="bae-dashboard-panel">
                        <div class="bae-dashboard-section-head">
                            <div><h3>Brand Snapshot</h3><p>Your identity details in one place.</p></div>
                            <a class="bae-dashboard-link" href="<?php echo esc_url($base . '?tab=overview'); ?>">Edit profile</a>
                        </div>
                        <div class="bae-dashboard-brandcard">
                            <div class="bae-dashboard-brandmark"><?php echo $logo_markup !== '' ? $logo_markup : '<div style="font-size:12px;color:var(--text-3);">No logo uploaded yet.</div>'; ?></div>
                            <div class="bae-dashboard-brandmeta">
                                <div class="bae-dashboard-mini"><b>Colors</b><div class="bae-dashboard-swatches"><span class="bae-dashboard-swatch" style="background:<?php echo esc_attr($pc); ?>"></span><span class="bae-dashboard-swatch" style="background:<?php echo esc_attr($sc); ?>"></span><span class="bae-dashboard-swatch" style="background:<?php echo esc_attr($ac); ?>"></span></div><span><?php echo esc_html(strtoupper($pc) . ' / ' . strtoupper($sc) . ' / ' . strtoupper($ac)); ?></span></div>
                                <div class="bae-dashboard-mini"><b>Typography</b><span><?php echo esc_html(($profile['font_heading'] ?? 'Inter') . ' + ' . ($profile['font_body'] ?? 'Inter')); ?></span></div>
                                <div class="bae-dashboard-mini"><b>Contact</b><span><?php echo esc_html(trim((string)($profile['email'] ?? '')) !== '' ? $profile['email'] : 'No email saved'); ?></span></div>
                                <div class="bae-dashboard-mini"><b>Kit</b><span><?php echo esc_html(!empty($profile['kit_slug']) ? $profile['kit_slug'] : 'Private workspace'); ?></span></div>
                            </div>
                        </div>
                    </section>

                    <section class="bae-dashboard-panel">
                        <div class="bae-dashboard-section-head"><div><h3>Quick Summary</h3><p>A compact view of the same dashboard data.</p></div></div>
                        <div class="bae-dashboard-stack">
                            <div class="bae-dashboard-row"><div><strong><?php echo esc_html($plan_label); ?> plan active</strong><span>Your current access level controls brand kit sharing and generation limits.</span></div><span class="bae-dashboard-chip">Plan</span></div>
                            <div class="bae-dashboard-row"><div><strong><?php echo esc_html($recent_total); ?> recent asset<?php echo $recent_total === 1 ? '' : 's'; ?></strong><span>Latest generated files are surfaced here for quick access.</span></div><span class="bae-dashboard-chip">Recent</span></div>
                        </div>
                    </section>

                    <section class="bae-dashboard-quicknote">
                        <strong>BAE layout refresh</strong>
                        <p>This dashboard keeps your original tools and content intact, but presents them in a softer admin layout inspired by the shared references.</p>
                        <a class="bae-dashboard-link" href="<?php echo esc_url($base . '?tab=assets'); ?>">Continue to asset generator</a>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
