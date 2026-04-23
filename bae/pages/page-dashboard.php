<?php if (!defined('ABSPATH')) exit;
function bae_home_dashboard($user_id, $profile) {
    if (empty($profile)) { return bae_overview_tab($user_id, $profile); }
    $p         = $profile;
    $base      = strtok($_SERVER['REQUEST_URI'], '?');
    $plan      = bae_get_user_plan($user_id, $profile);
    $is_free   = $plan === 'free';
    global $wpdb;
    $asset_count = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}bae_assets WHERE profile_id = %d AND is_generated = 1",
        $p['id']
    ));
    $brand_name = trim((string)($p['business_name'] ?? 'Your brand'));
    $tagline    = trim((string)($p['tagline'] ?? ''));
    $logo_url   = trim((string)($p['logo_url'] ?? ''));
    $greeting_hour = (int) current_time('G');
    if ($greeting_hour < 12) {
        $greeting = 'Good Morning';
    } elseif ($greeting_hour < 18) {
        $greeting = 'Good Afternoon';
    } else {
        $greeting = 'Good Evening';
    }
    $greeting_name = $brand_name ?: 'Builder';

    $tools = [
        ['slug'=>'overview',   'icon'=>'<path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 2H8l-2 5h12z"/>',     'label'=>'Brand Profile',   'desc'=>'Edit your brand details and colors', 'color'=>'var(--brand)'],
        ['slug'=>'assets',     'icon'=>'<rect width="8" height="8" x="3" y="3" rx="1"/><rect width="8" height="5" x="13" y="3" rx="1"/><rect width="8" height="8" x="13" y="12" rx="1"/><rect width="8" height="5" x="3" y="15" rx="1"/>', 'label'=>'Asset Generator', 'desc'=>$asset_count.' assets generated',    'color'=>'#F32D86'],
        ['slug'=>'kit',        'icon'=>'<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>',                                                'label'=>'Brand Kit',       'desc'=>'Your shareable brand page',          'color'=>'#34d399'],
        ['slug'=>'startup',    'icon'=>'<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>',  'label'=>'Launch Toolkit',  'desc'=>'Domains, handles, checklist',        'color'=>'#fbbf24'],
        ['slug'=>'brand_book', 'icon'=>'<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><path d="M8 7h6M8 11h8"/>',                     'label'=>'Brand Book',      'desc'=>'50 professional templates',          'color'=>'#f76fb0'],
        ['slug'=>'settings',   'icon'=>'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>', 'label'=>'Settings',        'desc'=>'Plan, export, and account',          'color'=>'var(--text-3)'],
    ];
    $quick_stats = [
        [
            'value' => $asset_count,
            'label' => 'Assets generated',
            'color' => '#38bdf8',
            'bg'    => 'rgba(56,189,248,.12)',
            'icon'  => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        ],
        [
            'value' => count($tools),
            'label' => 'Workspace tools',
            'color' => '#F32D86',
            'bg'    => 'rgba(243,45,134,.12)',
            'icon'  => '<rect width="16" height="16" x="4" y="4" rx="3"/><path d="M9 9h6M9 15h6M9 12h6"/><path d="M7 9h.01M7 12h.01M7 15h.01"/>',
        ],
        [
            'value' => strtoupper(substr($plan, 0, 3)),
            'label' => ucfirst($plan) . ' plan',
            'color' => '#f59e0b',
            'bg'    => 'rgba(245,158,11,.12)',
            'icon'  => '<path d="M12 3l2.45 4.96 5.47.8-3.96 3.86.94 5.45L12 15.77 7.1 18.07l.94-5.45-3.96-3.86 5.47-.8L12 3z"/>',
        ],
    ];
    $card_themes = [
        'linear-gradient(135deg, #5b14d6 0%, #c4196a 42%, #c026d3 100%)',
        'linear-gradient(135deg, #f59e0b 0%, #fb923c 42%, #f97316 100%)',
        'linear-gradient(135deg, #db2777 0%, #F32D86 45%, #c4196a 100%)',
        'linear-gradient(135deg, #22c55e 0%, #84cc16 45%, #16a34a 100%)',
        'linear-gradient(135deg, #06b6d4 0%, #3b82f6 45%, #4f46e5 100%)',
        'linear-gradient(135deg, #111827 0%, #374151 40%, #c4196a 100%)',
    ];

    ob_start(); ?>
    <?php
    $pc = bae_safe_color($p['primary_color'], '#1a1a2e');
    $sc = bae_safe_color($p['secondary_color'], '#16213e');
    $ac = bae_safe_color($p['accent_color'], '#e94560');
    ?>
    <div class="bae-dash-shell">
        <div class="bae-dash-hero">
            <div class="bae-dash-user">
                <div class="bae-dash-copy">
                    <div class="bae-dash-eyebrow"><?php echo esc_html(ucfirst($plan)); ?> plan</div>
                    <div class="bae-dash-title"><?php echo esc_html($greeting . ', ' . $greeting_name); ?></div>
                    <div class="bae-dash-sub"><?php echo esc_html($tagline ?: 'Your brand tools, all in one place. Build, refine, and launch — at your own pace.'); ?></div>
                </div>
            </div>
            <div class="bae-dash-metrics">
                <?php foreach ($quick_stats as $stat): ?>
                <div class="bae-dash-metric">
                    <span class="bae-dash-metric-icon" style="background:<?php echo esc_attr($stat['bg']); ?>;color:<?php echo esc_attr($stat['color']); ?>;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $stat['icon']; ?></svg>
                    </span>
                    <span>
                        <div class="bae-dash-metric-value"><?php echo esc_html((string)$stat['value']); ?></div>
                        <div class="bae-dash-metric-label"><?php echo esc_html($stat['label']); ?></div>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bae-dash-identity">
            <div class="bae-dash-swatches">
                <?php foreach([$pc,$sc,$ac] as $c): ?>
                <div class="bae-dash-swatch" style="background:<?php echo esc_attr($c); ?>;" title="<?php echo esc_attr(strtoupper($c)); ?>"></div>
                <?php endforeach; ?>
            </div>
            <div class="bae-dash-fonts">
                <span style="font-family:'<?php echo esc_attr($p['font_heading']); ?>',sans-serif;font-weight:700;"><?php echo esc_html($p['font_heading']); ?></span>
                <span style="color:var(--text-3);margin:0 6px;">+</span>
                <span style="font-family:'<?php echo esc_attr($p['font_body']); ?>',sans-serif;"><?php echo esc_html($p['font_body']); ?></span>
            </div>
            <a href="<?php echo esc_url($base . '?tab=overview'); ?>" class="bae-dash-edit">Edit profile →</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// =============================================================================
// CRON: Sweep unclaimed session rows older than 48 hours
// Keeps DB clean — rows that were never claimed with a ticket get pruned.
// =============================================================================
add_action('bae_cleanup_unclaimed_sessions', 'bntm_bae_cleanup_unclaimed_sessions');
function bntm_bae_cleanup_unclaimed_sessions() {
    global $wpdb;
    // Delete profile rows with no ticket, older than 48h
    $wpdb->query(
        "DELETE FROM {$wpdb->prefix}bae_profiles
         WHERE (ticket = '' OR ticket IS NULL)
         AND session_id != ''
         AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)"
    );
    // Orphaned assets (profile_id no longer exists)
    $wpdb->query(
        "DELETE a FROM {$wpdb->prefix}bae_assets a
         LEFT JOIN {$wpdb->prefix}bae_profiles p ON p.id = a.profile_id
         WHERE p.id IS NULL"
    );
}
if (!wp_next_scheduled('bae_cleanup_unclaimed_sessions')) {
    wp_schedule_event(time(), 'twicedaily', 'bae_cleanup_unclaimed_sessions');
}
