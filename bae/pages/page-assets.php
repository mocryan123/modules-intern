<?php if (!defined('ABSPATH')) exit;
function bae_assets_tab($user_id, $profile) {
    if (empty($profile)) {
        ob_start();
        ?>
        <div class="bae-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <strong>No brand profile yet.</strong>
            <p>Complete your <a href="?tab=overview">Brand Profile</a> first to generate assets.</p>
        </div>
        <?php
        return bae_wrap_tab_panel(ob_get_clean());
    }

    global $wpdb;
    $p            = $profile;
    $nonce        = wp_create_nonce('bae_generate_asset');
    $assets_table = $wpdb->prefix . 'bae_assets';
    $profile_id   = $p['id'];
    $user_plan    = bae_get_user_plan($user_id, $profile);
    $is_free      = $user_plan === 'free';
    $needs_first_asset_view = !bae_has_viewed_onboarding_asset($profile);

    $generated = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$assets_table} WHERE profile_id = %d",
        $profile_id
    ), ARRAY_A);

    $gen_map = [];
    foreach ($generated as $ga) {
        $gen_map[$ga['asset_type']] = $ga;
    }

    // Asset groups (categories)
    $asset_groups = [
        '01 – Print Assets' => [
            'business_card'   => ['name' => 'Business Card',   'desc' => 'Print-ready front & back layout',   'icon' => '🪪'],
            'letterhead'      => ['name' => 'Letterhead',      'desc' => 'A4 branded document header/footer', 'icon' => '📄'],
            'invoice_template'=> ['name' => 'Invoice Template','desc' => 'Branded invoice layout for clients','icon' => '🧾'],
            'price_list'      => ['name' => 'Price List',      'desc' => 'Stylized product or service pricing sheet','icon' => '💰'],
            'thank_you_card'  => ['name' => 'Thank You Card',  'desc' => 'Branded thank‑you card for customers','icon' => '💌'],
        ],
        '02 – Digital & Social' => [
            'email_signature' => ['name' => 'Email Signature', 'desc' => 'HTML email signature snippet',      'icon' => '✉️'],
            'social_kit'      => ['name' => 'Social Media Kit','desc' => 'Profile frame + post template',     'icon' => '📱'],
            'media_kit'       => ['name' => 'Media Kit',       'desc' => 'Press/partnership one‑pager','icon' => '📰'],
            'poster_a3'       => ['name' => 'A3 Poster',       'desc' => 'Large‑format print‑ready branded poster','icon' => '🖼️'],
        ],
        '03 – Brand Tools' => [
            'brand_guidelines'=> ['name' => 'Brand Guidelines','desc' => 'One‑page brand rules document',    'icon' => '📋'],
            'sitemap'         => ['name' => 'Site Structure',  'desc' => 'Suggested sitemap for your industry','icon' => '🗺️'],
            'flyer_template'  => ['name' => 'Promo Flyer',     'desc' => 'Promotional flyer for events or offers','icon' => '📣'],
        ],
    ];

    // Append custom AI assets as a separate group
    $custom_assets = [];
    foreach ($gen_map as $type => $asset) {
        if (strpos($type, 'custom_') === 0) {
            $custom_assets[$type] = [
                'name' => esc_html($asset['asset_name']),
                'desc' => 'Custom AI‑generated asset',
                'custom' => true,
            ];
        }
    }
    if (!empty($custom_assets)) {
        $asset_groups['04 – Custom AI'] = $custom_assets;
    }

    ob_start();
    ?>
    <div class="bae-assets-page">

        <!-- Header row (title + generate all) -->
        <div class="bae-generate-row">
            <div>
                <div class="bae-card-title">Asset Generator</div>
                <div class="bae-card-desc">Generate branded HTML assets ready for download or handoff.</div>
            </div>
            <?php if ($is_free): ?>
            <button class="bae-btn bae-btn-outline" onclick="baePricingOpen('Generate all 12 assets at once', 'Free plan lets you generate each asset individually. Upgrade to generate all 12 in one click, and regenerate anytime.')">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
                Generate All — Starter+
            </button>
            <?php else: ?>
            <button class="bae-btn bae-btn-primary" id="bae-generate-all-btn"
                    data-nonce="<?php echo $nonce; ?>"
                    data-pid="<?php echo $profile_id; ?>">
                Generate All Assets
            </button>
            <?php endif; ?>
        </div>

        <?php if ($is_free): ?>
        <div class="bae-free-plan-tip">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f76fb0" stroke-width="2"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
            <span class="bae-free-plan-tip-copy"><strong>Free plan</strong> — click <strong>Generate</strong> on any card below to generate it free, once per asset. You also get <strong>1 free Custom AI generation</strong>. Assets are yours to keep. Upgrade for Generate All, unlimited regeneration, and unlimited Custom AI.</span>
            <button class="bae-btn bae-btn-primary bae-btn-sm" onclick="baePricingOpen()">Upgrade Now ✦</button>
        </div>
        <?php endif; ?>

        <!-- Progress bar for Generate All -->
        <div class="bae-progress-wrap" id="bae-progress-wrap">
            <div class="bae-progress-bar-track">
                <div class="bae-progress-bar-fill" id="bae-progress-fill"></div>
            </div>
            <div class="bae-progress-label" id="bae-progress-label">Starting...</div>
            <div class="bae-progress-steps" id="bae-progress-steps">
                <?php
                $all_types = [];
                foreach ($asset_groups as $group) {
                    foreach ($group as $type => $meta) {
                        $all_types[] = $type;
                    }
                }
                foreach ($all_types as $type): ?>
                    <span class="bae-progress-step" id="bae-step-<?php echo $type; ?>"><?php echo isset($asset_groups[$type]) ? $asset_groups[$type]['name'] : $type; ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="bae-generate-all-msg"></div>

        <!-- Search bar (modern glass) + right sidebar wrapper (desktop) -->
        <div class="bae-assets-top-bar">
            <div class="bae-asset-search-modern">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" id="bae-asset-search-input" placeholder="Search assets…" autocomplete="off">
            </div>

            <!-- Right sidebar area (desktop) – contains AI Promoter and Brand Tools -->
            <div class="bae-assets-sidebar">
                <!-- AI Promoter Card (styled like the image) -->
                <div class="bae-ai-promoter-card">
                    <div class="bae-ai-promoter-header">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 0 1 10 10c0 5.5-4.5 10-10 10S2 17.5 2 12 6.5 2 12 2z"/><path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="4"/></svg>
                        <span>Ask Super AI anything</span>
                    </div>
                    <div class="bae-ai-promoter-body">
                        <div class="bae-ai-promoter-suggestions">
                            <span>Future of E‑Commerce in 2030</span>
                            <span>Healthy Breakfast in 1 Minute</span>
                        </div>
                        <textarea id="bae-ai-prompt" rows="2" placeholder="Message"></textarea>
                        <button type="button" id="bae-ai-gen-btn" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                            Generate
                        </button>
                    </div>
                    <div id="bae-ai-result" style="display:none; margin-top:16px;"></div>
                    <div id="bae-ai-status" style="font-size:12px; color:var(--text-3); margin-top:8px;"></div>
                </div>

                <!-- Brand Tools (Consistency Scan) -->
                <div class="bae-brand-tools-card">
                    <div class="bae-brand-tools-header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                        <span>Brand Tools</span>
                    </div>
                    <div class="bae-brand-tools-body">
                        <button type="button" id="bae-consistency-btn" class="bae-btn bae-btn-outline" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            Scan consistency
                        </button>
                        <span id="bae-consistency-status" style="font-size:12px;color:var(--text-3); margin-left:8px;"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grouped asset sections (card grid) -->
        <?php foreach ($asset_groups as $section_title => $assets): ?>
            <div class="bae-asset-section">
                <div class="bae-section-header">
                    <span class="bae-section-number"><?php echo explode(' – ', $section_title)[0]; ?></span>
                    <span class="bae-section-name"><?php echo explode(' – ', $section_title)[1]; ?></span>
                </div>
                <div class="bae-assets-grid" data-section="<?php echo esc_attr($section_title); ?>">
                    <?php foreach ($assets as $type => $meta):
                        $is_gen   = isset($gen_map[$type]);
                        $gen_data = $is_gen ? $gen_map[$type] : null;
                    ?>
                    <div class="bae-asset-card" id="bae-card-<?php echo $type; ?>"
                         draggable="true"
                         data-asset-type="<?php echo esc_attr($type); ?>"
                         data-asset-name="<?php echo esc_attr(strtolower($meta['name'])); ?>"
                         data-asset-desc="<?php echo esc_attr(strtolower($meta['desc'])); ?>">

                        <!-- Thumbnail area (clickable) with hover overlay -->
                        <div class="bae-asset-thumb" data-type="<?php echo $type; ?>"
                             data-name="<?php echo esc_attr($meta['name']); ?>"
                             data-pid="<?php echo $profile_id; ?>"
                             data-nonce="<?php echo $nonce; ?>">

                            <?php if ($is_gen): ?>
                                <div class="bae-asset-preview-inner bae-ai-asset">
                                    <?php echo $gen_data['asset_html']; ?>
                                </div>
                            <?php else: ?>
                                <div class="bae-asset-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <!-- Hover overlay -->
                            <div class="bae-asset-overlay">
                                <div class="bae-asset-overlay-content">
                                    <?php if ($is_gen): ?>
                                        <div class="bae-asset-name-overlay"><?php echo $meta['name']; ?></div>
                                        <div class="bae-asset-desc-overlay"><?php echo $meta['desc']; ?></div>
                                        <div class="bae-asset-overlay-actions">
                                            <?php if ($is_free): ?>
                                                <button class="bae-btn bae-btn-sm bae-regen-overlay" onclick="baePricingOpen('Regenerate anytime', 'Free plan generates each asset once. Upgrade to regenerate whenever you update your brand.')">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                    Regen
                                                </button>
                                            <?php else: ?>
                                                <button class="bae-btn bae-btn-primary bae-btn-sm bae-regen-btn"
                                                        data-type="<?php echo $type; ?>"
                                                        data-nonce="<?php echo $nonce; ?>"
                                                        data-pid="<?php echo $profile_id; ?>">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                    Regenerate
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($gen_data['asset_html_prev'])): ?>
                                                <button class="bae-btn bae-btn-outline bae-btn-sm bae-undo-btn"
                                                        data-type="<?php echo $type; ?>"
                                                        data-nonce="<?php echo $nonce; ?>"
                                                        data-pid="<?php echo $profile_id; ?>"
                                                        data-mode="undo">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                                                    Undo
                                                </button>
                                            <?php endif; ?>
                                            <button class="bae-btn bae-btn-outline bae-btn-sm bae-delete-btn"
                                                    data-type="<?php echo $type; ?>"
                                                    data-id="<?php echo $gen_data['id']; ?>"
                                                    data-nonce="<?php echo $nonce; ?>">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-13"/><path d="M9 4h6"/></svg>
                                                Delete
                                            </button>
                                        </div>
                                        <?php if ($type === 'social_kit'): ?>
                                            <div class="bae-asset-overlay-tabs">
                                                <button class="bae-overlay-tab is-active" data-target="template">Template</button>
                                                <button class="bae-overlay-tab" data-target="captions">Captions</button>
                                            </div>
                                            <div class="bae-social-caption-overlay" style="display:none;">
                                                <div class="bae-social-caption-input">
                                                    <input type="text" class="bae-social-caption-topic" placeholder="Post topic…">
                                                    <button class="bae-btn bae-btn-primary bae-btn-sm bae-social-caption-generate" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>">Generate</button>
                                                </div>
                                                <div class="bae-social-caption-results"></div>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="bae-asset-name-overlay"><?php echo $meta['name']; ?></div>
                                        <div class="bae-asset-desc-overlay"><?php echo $meta['desc']; ?></div>
                                        <button class="bae-btn bae-btn-primary bae-btn-lg bae-gen-btn"
                                                data-type="<?php echo $type; ?>"
                                                data-nonce="<?php echo $nonce; ?>"
                                                data-pid="<?php echo $profile_id; ?>">
                                            Generate
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Hidden custom AI result container (used by AJAX) -->
        <div id="bae-custom-ai-result-container" style="display:none;">
            <div id="bae-ai-result-full" style="display:none; margin-top:16px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span>Result</span>
                    <div>
                        <button id="bae-ai-copy" class="bae-btn bae-btn-sm">Copy HTML</button>
                        <button id="bae-ai-preview" class="bae-btn bae-btn-sm">Preview</button>
                        <button id="bae-ai-save-btn" class="bae-btn bae-btn-sm">Save as Asset</button>
                    </div>
                </div>
                <div id="bae-ai-frame" style="background:white; border-radius:12px; padding:16px; max-height:400px; overflow:auto;"></div>
            </div>
        </div>

        <!-- Modals (unchanged) -->
        <div id="bae-tools-modal-overlay" class="bae-modal-overlay" style="display:none;">
            <div class="bae-modal" style="max-width:860px;">
                <div class="bae-modal-header">
                    <span class="bae-modal-title" id="bae-tools-modal-title">Brand Tools</span>
                    <button type="button" class="bae-modal-close" id="bae-tools-modal-close">&times;</button>
                </div>
                <div class="bae-modal-body">
                    <div id="bae-tools-modal-body" style="font-size:14px;color:var(--text-2);"></div>
                </div>
                <div class="bae-modal-footer">
                    <button type="button" class="bae-btn bae-btn-outline" id="bae-tools-modal-ok">Close</button>
                </div>
            </div>
        </div>

        <div id="bae-regen-modal-overlay" class="bae-modal-overlay" style="display:none;">
            <div class="bae-modal">
                <div class="bae-modal-header">
                    <span class="bae-modal-title">Regenerate with AI Improvements</span>
                    <button type="button" class="bae-modal-close" id="bae-regen-modal-close">&times;</button>
                </div>
                <div class="bae-modal-body">
                    <p>Describe how you'd like to improve this asset.</p>
                    <textarea id="bae-regen-prompt" rows="3" placeholder="e.g. Make the colors brighter, add more contact info..." style="width:100%;background:var(--input-bg);border:1.5px solid var(--input-bd);border-radius:10px;padding:12px;"></textarea>
                    <div id="bae-regen-error" style="color:#fb7185; margin-top:8px; display:none;"></div>
                </div>
                <div class="bae-modal-footer">
                    <button class="bae-btn bae-btn-outline" id="bae-regen-skip">Skip Prompt</button>
                    <button class="bae-btn bae-btn-primary" id="bae-regen-generate">Regenerate with AI</button>
                </div>
            </div>
        </div>

    </div>

    <style>
    /* Asset page – modern glass search, correct card background, right sidebar */
    .bae-assets-page {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Top bar: search and sidebar (desktop) */
    .bae-assets-top-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        margin: 20px 0 32px;
        align-items: flex-start;
    }
    .bae-asset-search-modern {
        flex: 2;
        min-width: 200px;
        background: rgba(255,255,255,0.12);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 60px;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s;
    }
    .bae-wrap.bae-light .bae-asset-search-modern {
        background: rgba(255,255,255,0.8);
        border-color: rgba(0,0,0,0.1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .bae-asset-search-modern:focus-within {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(243,45,134,0.2);
    }
    .bae-asset-search-modern svg {
        flex-shrink: 0;
        color: var(--text-3);
    }
    .bae-asset-search-modern input {
        background: transparent;
        border: none;
        outline: none;
        font-size: 16px;
        width: 100%;
        color: var(--text);
    }
    .bae-asset-search-modern input::placeholder {
        color: var(--text-3);
    }

    /* Right sidebar (desktop) */
    .bae-assets-sidebar {
        flex: 1;
        min-width: 280px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* AI Promoter Card (matching the image) */
    .bae-ai-promoter-card {
        background: var(--surface);
        border-radius: 28px;
        padding: 20px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        border: 1px solid var(--border);
    }
    .bae-wrap.bae-light .bae-ai-promoter-card {
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(8px);
    }
    .bae-ai-promoter-header {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 16px;
    }
    .bae-ai-promoter-header svg {
        color: var(--brand-soft);
    }
    .bae-ai-promoter-suggestions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }
    .bae-ai-promoter-suggestions span {
        background: rgba(243,45,134,0.1);
        border-radius: 40px;
        padding: 4px 12px;
        font-size: 12px;
        color: var(--brand-soft);
        cursor: pointer;
        transition: 0.1s;
    }
    .bae-ai-promoter-suggestions span:hover {
        background: rgba(243,45,134,0.2);
    }
    .bae-ai-promoter-body textarea {
        width: 100%;
        background: var(--input-bg);
        border: 1.5px solid var(--input-bd);
        border-radius: 20px;
        padding: 12px 16px;
        font-size: 14px;
        font-family: inherit;
        resize: vertical;
        margin-bottom: 12px;
        color: var(--text);
    }
    .bae-ai-promoter-body textarea:focus {
        border-color: var(--brand);
        outline: none;
    }
    .bae-ai-promoter-body button {
        background: linear-gradient(135deg, #c4196a, #F32D86);
        border: none;
        border-radius: 40px;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 700;
        color: white;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .bae-ai-promoter-body button:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(195,25,106,0.3);
    }

    /* Brand Tools Card */
    .bae-brand-tools-card {
        background: var(--surface);
        border-radius: 24px;
        padding: 18px;
        border: 1px solid var(--border);
    }
    .bae-wrap.bae-light .bae-brand-tools-card {
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(8px);
    }
    .bae-brand-tools-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 12px;
    }
    .bae-brand-tools-body {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    /* Asset card – white/glass background, no gray */
    .bae-asset-card {
        background: var(--surface);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        aspect-ratio: 4 / 3;
        position: relative;
        border: 1px solid var(--border);
    }
    .bae-wrap.bae-light .bae-asset-card {
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(4px);
    }
    .bae-asset-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 32px rgba(0,0,0,0.12);
    }

    /* Thumbnail fills card */
    .bae-asset-thumb {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: var(--bg-3);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bae-asset-preview-inner {
        transform: scale(0.35);
        width: 280%;
        transform-origin: center center;
        pointer-events: none;
    }
    .bae-asset-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: var(--text-3);
    }

    /* Hover overlay */
    .bae-asset-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.75);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
        pointer-events: none;
    }
    .bae-asset-card:hover .bae-asset-overlay {
        opacity: 1;
        pointer-events: auto;
    }
    .bae-asset-overlay-content {
        text-align: center;
        padding: 16px;
        max-width: 90%;
    }
    .bae-asset-name-overlay {
        font-size: 16px;
        font-weight: 700;
        color: white;
        margin-bottom: 4px;
    }
    .bae-asset-desc-overlay {
        font-size: 12px;
        color: rgba(255,255,255,0.7);
        margin-bottom: 16px;
    }
    .bae-asset-overlay-actions {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .bae-overlay-tab {
        background: rgba(255,255,255,0.15);
        border: none;
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 600;
        color: white;
        cursor: pointer;
    }
    .bae-overlay-tab.is-active {
        background: var(--brand);
    }
    .bae-social-caption-overlay {
        margin-top: 12px;
    }
    .bae-social-caption-input {
        display: flex;
        gap: 6px;
        margin-bottom: 8px;
    }
    .bae-social-caption-input input {
        flex: 1;
        background: rgba(255,255,255,0.2);
        border: none;
        border-radius: 40px;
        padding: 6px 12px;
        font-size: 12px;
        color: white;
    }
    .bae-social-caption-results {
        font-size: 12px;
        color: white;
        text-align: left;
        max-height: 180px;
        overflow-y: auto;
    }

    /* Buttons inside overlay */
    .bae-asset-overlay .bae-btn {
        border-radius: 999px !important;
        padding: 6px 12px !important;
        font-size: 12px !important;
        font-weight: 600;
    }
    .bae-asset-overlay .bae-btn-primary {
        background: linear-gradient(135deg, #c4196a, #F32D86);
        color: white;
        border: none;
    }
    .bae-asset-overlay .bae-btn-outline {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.4);
        color: white;
    }
    .bae-gen-btn.bae-btn-lg {
        padding: 10px 24px !important;
        font-size: 14px !important;
    }

    /* Responsive: sidebar goes below search on mobile */
    @media (max-width: 768px) {
        .bae-assets-top-bar {
            flex-direction: column;
        }
        .bae-assets-sidebar {
            width: 100%;
        }
        .bae-assets-grid {
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        }
    }

    /* Section headers (unchanged) */
    .bae-asset-section {
        margin-bottom: 32px;
    }
    .bae-section-header {
        display: flex;
        align-items: baseline;
        gap: 12px;
        margin-bottom: 20px;
        padding-left: 4px;
    }
    .bae-section-number {
        font-family: 'Instrument Serif', serif;
        font-size: 28px;
        font-weight: 400;
        font-style: italic;
        color: var(--brand-soft);
        line-height: 1;
    }
    .bae-section-name {
        font-size: 20px;
        font-weight: 700;
        color: var(--text);
        letter-spacing: -0.02em;
    }
    .bae-assets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }
    </style>

    <script>
    (function() {
        var baeNeedsFirstAssetView = <?php echo $needs_first_asset_view ? 'true' : 'false'; ?>;
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

        function baeNotify(msg, type) {
            if (!msg) return;
            if (typeof window.baeToast === 'function') window.baeToast(msg, type || 'info');
        }

        function baeMarkFirstAssetViewed(btn) {
            if (!baeNeedsFirstAssetView || !btn) return;
            var fd = new FormData();
            fd.append('action', 'bae_mark_asset_viewed');
            fd.append('profile_id', btn.dataset.pid || '');
            fd.append('nonce', btn.dataset.nonce || '');
            var stayOnAssetsUrl = window.location.pathname + '?tab=assets';
            fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (j && j.success) {
                        baeNeedsFirstAssetView = false;
                        setTimeout(function() { window.location.href = stayOnAssetsUrl; }, 1100);
                    }
                })
                .catch(function() {});
        }

        // Preview on thumbnail click
        document.querySelectorAll('.bae-asset-thumb').forEach(function(thumb) {
            thumb.addEventListener('click', function(e) {
                e.preventDefault();
                var type = this.dataset.type;
                var name = this.dataset.name;
                var pid = this.dataset.pid;
                var nonce = this.dataset.nonce;
                if (baeNeedsFirstAssetView && pid && nonce) {
                    var dummyBtn = { dataset: { pid: pid, nonce: nonce } };
                    baeMarkFirstAssetViewed(dummyBtn);
                }
                var card = this.closest('.bae-asset-card');
                var inner = card ? card.querySelector('.bae-asset-preview-inner') : null;
                var html = inner ? inner.innerHTML : '';
                if (html && html.trim()) {
                    baeOpenModal(name, html);
                } else {
                    baeNotify('No preview available yet – generate the asset first.', 'info');
                }
            });
        });

        // Generate button (ungenerated cards)
        document.querySelectorAll('.bae-gen-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                baeGenerateAsset(this.dataset.type, this.dataset.nonce, this.dataset.pid, this, '');
            });
        });

        // Regenerate button
        document.querySelectorAll('.bae-regen-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var presetPrompt = this.dataset.prompt || '';
                if (presetPrompt) {
                    baeGenerateAsset(this.dataset.type, this.dataset.nonce, this.dataset.pid, this, presetPrompt);
                    return;
                }
                var modal = document.getElementById('bae-regen-modal-overlay');
                var promptEl = document.getElementById('bae-regen-prompt');
                var errorEl = document.getElementById('bae-regen-error');
                if (modal && promptEl) {
                    promptEl.value = '';
                    errorEl.style.display = 'none';
                    modal.style.display = 'flex';
                    modal.dataset.type = this.dataset.type;
                    modal.dataset.nonce = this.dataset.nonce;
                    modal.dataset.pid = this.dataset.pid;
                }
            });
        });

        // Regen modal handlers
        var regenModal = document.getElementById('bae-regen-modal-overlay');
        var regenClose = document.getElementById('bae-regen-modal-close');
        var regenSkip = document.getElementById('bae-regen-skip');
        var regenGenerate = document.getElementById('bae-regen-generate');
        var regenPrompt = document.getElementById('bae-regen-prompt');
        var regenError = document.getElementById('bae-regen-error');
        if (regenClose) regenClose.addEventListener('click', function(e) { e.preventDefault(); regenModal.style.display = 'none'; });
        if (regenSkip) regenSkip.addEventListener('click', function(e) {
            e.preventDefault();
            var type = regenModal.dataset.type;
            var nonce = regenModal.dataset.nonce;
            var pid = regenModal.dataset.pid;
            regenModal.style.display = 'none';
            var cardBtn = document.querySelector('.bae-regen-btn[data-type="' + type + '"]');
            baeGenerateAsset(type, nonce, pid, cardBtn, '');
        });
        if (regenGenerate) regenGenerate.addEventListener('click', function(e) {
            e.preventDefault();
            var prompt = regenPrompt.value.trim();
            if (!prompt) {
                regenError.textContent = 'Please enter an improvement prompt.';
                regenError.style.display = 'block';
                return;
            }
            var type = regenModal.dataset.type;
            var denyWords = ['create a website', 'make a flyer', 'design a poster', 'build an app', 'develop software'];
            for (var deny of denyWords) {
                if (prompt.toLowerCase().includes(deny)) {
                    regenError.textContent = 'Prompt must improve the existing asset, not create something new.';
                    regenError.style.display = 'block';
                    return;
                }
            }
            regenError.style.display = 'none';
            var nonce = regenModal.dataset.nonce;
            var pid = regenModal.dataset.pid;
            regenModal.style.display = 'none';
            var cardBtn = document.querySelector('.bae-regen-btn[data-type="' + type + '"]');
            baeGenerateAsset(type, nonce, pid, cardBtn, prompt);
        });

        // Undo button
        function baeBindUndoButton(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var type = this.dataset.type;
                var nonce = this.dataset.nonce;
                var pid = this.dataset.pid;
                var self = this;
                self.disabled = true;
                var fd = new FormData();
                fd.append('action', 'bae_undo_asset');
                fd.append('asset_type', type);
                fd.append('profile_id', pid);
                fd.append('nonce', nonce);
                fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(json) {
                    if (json.success) {
                        var card = document.getElementById('bae-card-' + type);
                        if (card) {
                            var inner = card.querySelector('.bae-asset-preview-inner');
                            if (inner) inner.innerHTML = json.data.html;
                        }
                        self.disabled = false;
                    } else {
                        baeNotify((json.data && json.data.message) ? json.data.message : 'Undo failed.', 'error');
                        self.disabled = false;
                    }
                })
                .catch(function() { baeNotify('Undo failed.', 'error'); self.disabled = false; });
            });
        }
        document.querySelectorAll('.bae-undo-btn').forEach(function(btn) { baeBindUndoButton(btn); });

        // Delete button
        document.querySelectorAll('.bae-delete-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var id = this.dataset.id;
                var nonce = this.dataset.nonce;
                baeConfirm('Remove this asset? You can regenerate it anytime.', function() {
                    var formData = new FormData();
                    formData.append('action', 'bae_delete_asset');
                    formData.append('asset_id', id);
                    formData.append('nonce', nonce);
                    fetch(ajaxurl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(json) {
                        if (json.success) location.reload();
                        else baeNotify((json.data && json.data.message) ? json.data.message : 'Delete failed.', 'error');
                    });
                });
            });
        });

        // Social kit overlay tabs
        document.querySelectorAll('#bae-card-social_kit .bae-overlay-tab').forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.stopPropagation();
                var container = this.closest('.bae-asset-overlay-content');
                var target = this.dataset.target;
                container.querySelectorAll('.bae-overlay-tab').forEach(function(btn) { btn.classList.remove('is-active'); });
                this.classList.add('is-active');
                var captionPanel = container.querySelector('.bae-social-caption-overlay');
                var actionsDiv = container.querySelector('.bae-asset-overlay-actions');
                if (target === 'captions') {
                    if (captionPanel) captionPanel.style.display = 'block';
                    if (actionsDiv) actionsDiv.style.display = 'none';
                } else {
                    if (captionPanel) captionPanel.style.display = 'none';
                    if (actionsDiv) actionsDiv.style.display = 'flex';
                }
            });
        });

        function baeRenderSocialCaptions(container, captions) {
            var resultsDiv = container.querySelector('.bae-social-caption-results');
            if (!resultsDiv) return;
            resultsDiv.innerHTML = '';
            captions.forEach(function(item) {
                var row = document.createElement('div');
                row.style.cssText = 'padding:8px 12px;border-bottom:1px solid rgba(255,255,255,0.2);font-size:12px;';
                row.innerHTML = '<strong style="color:#f76fb0;">' + item.tone + '</strong><br>' + item.caption;
                var copyBtn = document.createElement('button');
                copyBtn.textContent = 'Copy';
                copyBtn.style.cssText = 'background:none;border:1px solid rgba(255,255,255,0.3);border-radius:40px;color:white;font-size:10px;padding:2px 8px;margin-left:8px;cursor:pointer;';
                copyBtn.onclick = function() { baeCopyText(item.caption, copyBtn); };
                row.appendChild(copyBtn);
                resultsDiv.appendChild(row);
            });
        }

        function baeGenerateSocialCaptions(btn) {
            var container = btn.closest('.bae-asset-overlay-content');
            if (!container) return;
            var input = container.querySelector('.bae-social-caption-topic');
            var topic = input ? input.value.trim() : '';
            if (!topic) return;
            var statusSpan = container.querySelector('.bae-social-caption-status') || (function() { var s = document.createElement('div'); container.appendChild(s); return s; })();
            statusSpan.textContent = 'Generating...';
            btn.disabled = true;
            var fd = new FormData();
            fd.append('action', 'bae_social_captions');
            fd.append('nonce', btn.dataset.nonce);
            fd.append('profile_id', btn.dataset.pid);
            fd.append('topic', topic);
            fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    btn.disabled = false;
                    if (json.success && json.data && json.data.captions) {
                        statusSpan.textContent = '';
                        baeRenderSocialCaptions(container, json.data.captions);
                    } else {
                        statusSpan.textContent = (json.data && json.data.message) || 'Failed.';
                    }
                })
                .catch(function() { btn.disabled = false; statusSpan.textContent = 'Error.'; });
        }
        document.querySelectorAll('.bae-social-caption-generate').forEach(function(btn) {
            btn.addEventListener('click', function(e) { e.stopPropagation(); baeGenerateSocialCaptions(this); });
        });

        // Generate All (progress)
        var allAssetTypes = [];
        <?php
        $all_asset_types = [];
        foreach ($asset_groups as $group) {
            foreach ($group as $type => $meta) {
                $all_asset_types[] = $type;
            }
        }
        echo 'var types = ' . json_encode($all_asset_types) . ';';
        ?>
        var genAllBtn = document.getElementById('bae-generate-all-btn');
        var genAllMsg = document.getElementById('bae-generate-all-msg');
        var progressWrap = document.getElementById('bae-progress-wrap');
        var progressFill = document.getElementById('bae-progress-fill');
        var progressLabel = document.getElementById('bae-progress-label');
        function setStepState(type, state) {
            var el = document.getElementById('bae-step-' + type);
            if (el) el.className = 'bae-progress-step ' + state;
        }
        function setProgress(done, total, label) {
            var pct = Math.round((done / total) * 100);
            progressFill.style.width = pct + '%';
            progressLabel.textContent = label;
        }
        if (genAllBtn) {
            genAllBtn.addEventListener('click', function() {
                var nonce = this.dataset.nonce;
                var pid = this.dataset.pid;
                genAllBtn.disabled = true;
                genAllBtn.textContent = 'Generating...';
                genAllMsg.innerHTML = '';
                progressWrap.classList.add('visible');
                setProgress(0, types.length, 'Starting generation...');
                var errors = [];
                function runNext(index) {
                    if (index >= types.length) {
                        progressFill.style.width = '100%';
                        if (errors.length === 0) {
                            progressLabel.textContent = 'All assets generated!';
                            genAllMsg.innerHTML = '<div class="bae-notice bae-notice-success">All assets generated successfully!</div>';
                        } else {
                            progressLabel.textContent = (types.length - errors.length) + ' of ' + types.length + ' succeeded.';
                            genAllMsg.innerHTML = '<div class="bae-notice bae-notice-error">Some assets failed: ' + errors.join(', ') + '. Please try regenerating them individually.</div>';
                        }
                        setTimeout(function() { location.reload(); }, 2000);
                        return;
                    }
                    var type = types[index];
                    setStepState(type, 'active');
                    setProgress(index, types.length, 'Generating ' + type.replace(/_/g,' ') + '...');
                    var formData = new FormData();
                    formData.append('action', 'bae_generate_asset');
                    formData.append('asset_type', type);
                    formData.append('profile_id', pid);
                    formData.append('nonce', nonce);
                    fetch(ajaxurl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(json) {
                        if (json.success) setStepState(type, 'done');
                        else { setStepState(type, 'error'); errors.push(type.replace(/_/g,' ')); }
                        runNext(index + 1);
                    })
                    .catch(function() { setStepState(type, 'error'); errors.push(type.replace(/_/g,' ')); runNext(index + 1); });
                }
                runNext(0);
            });
        }

        // Single asset generate/regenerate
        function baeGenerateAsset(type, nonce, pid, btn, prompt) {
            var original = btn ? btn.textContent : 'Generating...';
            var isRegen = prompt && prompt.trim();
            if (btn) { btn.disabled = true; btn.textContent = isRegen ? 'Regenerating...' : 'Generating...'; }
            var formData = new FormData();
            formData.append('action', 'bae_generate_asset');
            formData.append('asset_type', type);
            formData.append('profile_id', pid);
            formData.append('nonce', nonce);
            if (prompt) formData.append('regen_prompt', prompt);
            fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                if (json.success) {
                    var card = document.getElementById('bae-card-' + type);
                    var html = (typeof json.html === 'string' && json.html.trim()) ? json.html : '<div style="padding:18px 14px;">Asset generated. Refresh to view.</div>';
                    if (card) {
                        var previewInner = card.querySelector('.bae-asset-preview-inner');
                        if (previewInner) previewInner.innerHTML = html;
                        var oldPlaceholder = card.querySelector('.bae-asset-placeholder');
                        if (oldPlaceholder) {
                            var newInner = document.createElement('div');
                            newInner.className = 'bae-asset-preview-inner bae-ai-asset';
                            newInner.innerHTML = html;
                            oldPlaceholder.replaceWith(newInner);
                        }
                        // Refresh overlay to show regen buttons
                        location.reload();
                        return;
                    }
                    if (btn) { btn.disabled = false; btn.textContent = original; }
                } else {
                    baeNotify(json.data.message || 'Generation failed.', 'error');
                    if (btn) { btn.disabled = false; btn.textContent = original; }
                }
            })
            .catch(function() { if (btn) { btn.disabled = false; btn.textContent = original; } });
        }

        // Custom AI Generator (new position in sidebar)
        var aiBtn = document.getElementById('bae-ai-gen-btn');
        var aiPrompt = document.getElementById('bae-ai-prompt');
        var aiStatus = document.getElementById('bae-ai-status');
        var aiResultFull = document.getElementById('bae-ai-result-full');
        var aiFrame = document.getElementById('bae-ai-frame');
        var aiCopy = document.getElementById('bae-ai-copy');
        var aiPreviewBtn = document.getElementById('bae-ai-preview');
        var aiSaveBtn = document.getElementById('bae-ai-save-btn');

        if (aiBtn) {
            aiBtn.addEventListener('click', function() {
                var prompt = aiPrompt.value.trim();
                if (!prompt) {
                    aiStatus.textContent = 'Please describe what you want to generate.';
                    aiStatus.style.color = '#fb7185';
                    return;
                }
                var nonce = this.dataset.nonce;
                var pid = this.dataset.pid;
                aiBtn.disabled = true;
                aiStatus.style.color = 'var(--text-3)';
                aiStatus.textContent = 'AI is generating...';
                if (aiResultFull) aiResultFull.style.display = 'none';
                var fd = new FormData();
                fd.append('action', 'bae_custom_generate');
                fd.append('nonce', nonce);
                fd.append('profile_id', pid);
                fd.append('custom_prompt', prompt);
                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    aiBtn.disabled = false;
                    if (j.success) {
                        aiStatus.textContent = 'Done.';
                        aiStatus.style.color = '#34d399';
                        if (aiFrame) aiFrame.innerHTML = '<div class="bae-ai-asset">' + j.data.html + '</div>';
                        if (aiResultFull) aiResultFull.style.display = 'block';
                        if (window.gsap) gsap.fromTo(aiResultFull, {opacity:0,y:8}, {opacity:1,y:0, duration:0.35});
                        setTimeout(function() { aiStatus.textContent = ''; }, 2000);
                    } else {
                        aiStatus.textContent = (j.data && j.data.message) || 'Generation failed.';
                        aiStatus.style.color = '#fb7185';
                    }
                })
                .catch(function() { aiBtn.disabled = false; aiStatus.textContent = 'Connection error.'; });
            });
        }
        if (aiCopy) {
            aiCopy.addEventListener('click', function() {
                var innerEl = aiFrame ? aiFrame.querySelector('.bae-ai-asset') : null;
                var html = innerEl ? innerEl.innerHTML : (aiFrame ? aiFrame.innerHTML : '');
                navigator.clipboard.writeText(html).then(function() {
                    aiCopy.textContent = 'Copied!';
                    setTimeout(function() { aiCopy.textContent = 'Copy HTML'; }, 2000);
                });
            });
        }
        if (aiPreviewBtn) {
            aiPreviewBtn.addEventListener('click', function() {
                var innerEl = aiFrame ? aiFrame.querySelector('.bae-ai-asset') : null;
                baeOpenModal('Custom Asset Preview', innerEl ? innerEl.innerHTML : (aiFrame ? aiFrame.innerHTML : ''));
            });
        }
        // Save as Asset
        if (aiSaveBtn) {
            aiSaveBtn.addEventListener('click', function() {
                var name = prompt('Asset name:', aiPrompt.value.trim().substring(0, 50));
                if (!name) return;
                var innerEl = aiFrame ? aiFrame.querySelector('.bae-ai-asset') : null;
                var html = innerEl ? innerEl.innerHTML : (aiFrame ? aiFrame.innerHTML : '');
                if (!html) { alert('Nothing to save.'); return; }
                var fd = new FormData();
                fd.append('action', 'bae_save_custom_asset');
                fd.append('nonce', aiBtn.dataset.nonce);
                fd.append('profile_id', aiBtn.dataset.pid);
                fd.append('asset_name', name);
                fd.append('asset_html', html);
                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (j.success) {
                        alert('Asset saved! Page will reload.');
                        location.reload();
                    } else {
                        alert(j.data?.message || 'Save failed.');
                    }
                });
            });
        }

        // Consistency scan (Brand Tools)
        var consBtn = document.getElementById('bae-consistency-btn');
        var consStatus = document.getElementById('bae-consistency-status');
        if (consBtn) {
            consBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var nonce = this.dataset.nonce;
                var pid = this.dataset.pid;
                var originalText = consBtn.textContent;
                consBtn.disabled = true;
                consBtn.textContent = 'Scanning...';
                if (consStatus) consStatus.textContent = 'Scanning saved assets...';
                var fd = new FormData();
                fd.append('action', 'bae_consistency_scan');
                fd.append('nonce', nonce);
                fd.append('profile_id', pid);
                fetch(ajaxurl, { method:'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(j){
                    consBtn.disabled = false;
                    consBtn.textContent = originalText;
                    if (consStatus) consStatus.textContent = '';
                    if (!j.success) { baeNotify(j.data?.message || 'Consistency scan failed.', 'error'); return; }
                    var report = j.data.report;
                    if (!report) return;
                    var html = '<strong>Consistency score: ' + report.score + '/100</strong><br>';
                    if (report.issues && report.issues.length) html += '<ul><li>' + report.issues.join('</li><li>') + '</li></ul>';
                    if (report.used_fonts && report.used_fonts.length) html += '<div>Fonts: ' + report.used_fonts.join(', ') + '</div>';
                    if (report.used_colors && report.used_colors.length) html += '<div>Colors: ' + report.used_colors.join(', ') + '</div>';
                    baeToolsOpen('Brand consistency scan', html);
                })
                .catch(function(){ consBtn.disabled = false; consBtn.textContent = originalText; if (consStatus) consStatus.textContent = ''; baeNotify('Consistency scan failed.', 'error'); });
            });
        }

        var toolsModal = document.getElementById('bae-tools-modal-overlay');
        var toolsClose = document.getElementById('bae-tools-modal-close');
        var toolsOk = document.getElementById('bae-tools-modal-ok');
        var toolsTitle = document.getElementById('bae-tools-modal-title');
        var toolsBody = document.getElementById('bae-tools-modal-body');
        function baeToolsOpen(title, html) { if (toolsModal && toolsTitle && toolsBody) { toolsTitle.textContent = title; toolsBody.innerHTML = html; toolsModal.style.display = 'flex'; } }
        function baeToolsClose() { if (toolsModal) toolsModal.style.display = 'none'; }
        if (toolsClose) toolsClose.addEventListener('click', function(e){ e.preventDefault(); baeToolsClose(); });
        if (toolsOk) toolsOk.addEventListener('click', function(e){ e.preventDefault(); baeToolsClose(); });

        // Search filter (modern search)
        var searchInput = document.getElementById('bae-asset-search-input');
        var noResults = document.createElement('div');
        noResults.id = 'bae-asset-no-results';
        noResults.className = 'bae-asset-no-results';
        noResults.style.display = 'none';
        noResults.textContent = 'No assets match your search.';
        document.querySelector('.bae-assets-grid')?.parentNode?.appendChild(noResults);
        var countLabel = document.getElementById('bae-asset-count-label');
        function baeFilterAssets() {
            var q = (searchInput ? searchInput.value : '').toLowerCase().trim();
            var cards = document.querySelectorAll('.bae-asset-card');
            var visible = 0;
            cards.forEach(function(card) {
                var name = (card.dataset.assetName || '').toLowerCase();
                var desc = (card.dataset.assetDesc || '').toLowerCase();
                var match = !q || name.indexOf(q) !== -1 || desc.indexOf(q) !== -1;
                card.classList.toggle('bae-assets-hidden', !match);
                if (match) visible++;
            });
            if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
            if (countLabel) countLabel.textContent = q ? visible + ' result' + (visible !== 1 ? 's' : '') : '';
        }
        window.baeFilterAssets = baeFilterAssets;
        if (searchInput) searchInput.addEventListener('input', baeFilterAssets);

        // Drag and drop (same as before)
        var gridContainers = document.querySelectorAll('.bae-assets-grid');
        gridContainers.forEach(function(grid) {
            var dragging = null;
            grid.addEventListener('dragstart', function(e) {
                var card = e.target.closest('.bae-asset-card');
                if (!card) return;
                dragging = card;
                card.classList.add('bae-dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            grid.addEventListener('dragend', function(e) {
                if (dragging) dragging.classList.remove('bae-dragging');
                grid.querySelectorAll('.bae-drag-over').forEach(function(c) { c.classList.remove('bae-drag-over'); });
                dragging = null;
            });
            grid.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                var target = e.target.closest('.bae-asset-card');
                if (!target || target === dragging) return;
                grid.querySelectorAll('.bae-drag-over').forEach(function(c) { c.classList.remove('bae-drag-over'); });
                target.classList.add('bae-drag-over');
            });
            grid.addEventListener('drop', function(e) {
                e.preventDefault();
                var target = e.target.closest('.bae-asset-card');
                if (!target || !dragging || target === dragging) return;
                target.classList.remove('bae-drag-over');
                var rect = target.getBoundingClientRect();
                var midX = rect.left + rect.width / 2;
                if (e.clientX < midX) grid.insertBefore(dragging, target);
                else grid.insertBefore(dragging, target.nextSibling);
            });
        });

        // Helper: copy text to clipboard
        window.baeCopyText = function(text, btn) {
            navigator.clipboard.writeText(text).then(function() {
                var orig = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(function() { btn.textContent = orig; }, 1500);
            });
        };
    })();
    </script>
    <?php
    return bae_wrap_tab_panel(ob_get_clean());
}
