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
        return ob_get_clean();
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

    $asset_types = [
        'business_card'    => ['name' => 'Business Card',       'desc' => 'Print-ready front & back layout',             'icon' => '🪪'],
        'letterhead'       => ['name' => 'Letterhead',           'desc' => 'A4 branded document header/footer',           'icon' => '📄'],
        'email_signature'  => ['name' => 'Email Signature',      'desc' => 'HTML email signature snippet',                'icon' => '✉️'],
        'social_kit'       => ['name' => 'Social Media Kit',     'desc' => 'Profile frame + post template',               'icon' => '📱'],
        'brand_guidelines' => ['name' => 'Brand Guidelines',     'desc' => 'One-page brand rules document',               'icon' => '📋'],
        'sitemap'          => ['name' => 'Site Structure',       'desc' => 'Suggested sitemap for your industry',          'icon' => '🗺️'],
        'invoice_template' => ['name' => 'Invoice Template',     'desc' => 'Branded invoice layout for clients',           'icon' => '🧾'],
        'price_list'       => ['name' => 'Price List',           'desc' => 'Stylized product or service pricing sheet',    'icon' => '💰'],
        'flyer_template'   => ['name' => 'Promo Flyer',          'desc' => 'Promotional flyer for events or offers',       'icon' => '📣'],
        'thank_you_card'   => ['name' => 'Thank You Card',       'desc' => 'Branded thank-you card for customers',         'icon' => '💌'],
        'media_kit'        => ['name' => 'Media Kit',            'desc' => 'Press/partnership one-pager with brand stats', 'icon' => '📰'],
        'poster_a3'        => ['name' => 'A3 Poster',            'desc' => 'Large-format print-ready branded poster',      'icon' => '🖼️'],
    ];

    // Append saved custom assets from DB
    foreach ( $gen_map as $type => $asset ) {
        if ( strpos($type, 'custom_') === 0 && ! isset($asset_types[$type]) ) {
            $asset_types[$type] = [
                'name' => esc_html($asset['asset_name']),
                'desc' => 'Custom AI-generated asset',
                'custom' => true,
            ];
        }
    }

    ob_start();
    ?>
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

    <?php /* tip removed — unlock info is now shown in the nav lock modal */ ?>

    <?php if ($is_free): ?>
    <div class="bae-free-plan-tip">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f76fb0" stroke-width="2"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
        <span class="bae-free-plan-tip-copy"><strong>Free plan</strong> — click <strong>Generate</strong> on any card below to generate it free, once per asset. You also get <strong>1 free Custom AI generation</strong>. Assets are yours to keep. Upgrade for Generate All, unlimited regeneration, and unlimited Custom AI.</span>
        <button class="bae-btn bae-btn-primary bae-btn-sm" onclick="baePricingOpen()">Upgrade Now ✦</button>
    </div>
    <?php endif; ?>

    <!-- CHANGED: Progress UI for sequential generate-all -->
    <div class="bae-progress-wrap" id="bae-progress-wrap">
        <div class="bae-progress-bar-track">
            <div class="bae-progress-bar-fill" id="bae-progress-fill"></div>
        </div>
        <div class="bae-progress-label" id="bae-progress-label">Starting...</div>
        <div class="bae-progress-steps" id="bae-progress-steps">
            <?php foreach ($asset_types as $type => $meta): ?>
                <span class="bae-progress-step" id="bae-step-<?php echo $type; ?>"><?php echo $meta['name']; ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="bae-generate-all-msg"></div>

    <div class="bae-asset-toolbar" style="margin-top:20px;">
        <!-- Search bar -->
        <div class="bae-asset-search">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" id="bae-asset-search-input" placeholder="Search assets…" autocomplete="off">
        </div>
        <span style="font-size:12px;color:var(--text-3);" id="bae-asset-count-label"></span>
        <span style="font-size:11px;color:var(--text-3);margin-left:auto;display:flex;align-items:center;gap:5px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 9l4-4 4 4"/><path d="M9 5v14"/><path d="M19 15l-4 4-4-4"/><path d="M15 19V5"/></svg>
            Drag cards to reorder
        </span>
    </div>

    <div class="bae-assets-grid" id="bae-assets-grid-container" style="margin-top:0;">
        <div class="bae-asset-no-results" id="bae-asset-no-results" style="display:none;">
            No assets match your search. <button onclick="document.getElementById('bae-asset-search-input').value='';baeFilterAssets();" style="background:none;border:none;color:var(--brand-soft);cursor:pointer;font-family:'Geist',sans-serif;font-size:14px;">Clear search</button>
        </div>
        <?php foreach ($asset_types as $type => $meta):
            $is_gen   = isset($gen_map[$type]);
            $gen_data = $is_gen ? $gen_map[$type] : null;
        ?>
        <div class="bae-asset-card" id="bae-card-<?php echo $type; ?>"
             draggable="true"
             data-asset-type="<?php echo esc_attr($type); ?>"
             data-asset-name="<?php echo esc_attr(strtolower($meta['name'])); ?>"
             data-asset-desc="<?php echo esc_attr(strtolower($meta['desc'])); ?>">
            <div class="bae-asset-preview">
                <?php if ($is_gen): ?>
                    <div class="bae-asset-preview-inner bae-ai-asset">
                        <?php echo $gen_data['asset_html']; ?>
                    </div>
                <?php else: ?>
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                <?php endif; ?>
            </div>
            <?php if ($type === 'social_kit'): ?>
            <div style="display:flex;gap:6px;padding:12px 14px 0 14px;flex-wrap:wrap;">
                <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-social-tab is-active" data-target="template">Template</button>
                <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-social-tab" data-target="captions">Captions</button>
            </div>
            <div class="bae-social-caption-panel" style="display:none;padding:14px;">
                <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:8px;">Generate social captions</div>
                <div style="font-size:12px;color:var(--text-3);margin-bottom:12px;">Enter a post topic, offer, event, or announcement. We'll generate 5 caption styles for your brand.</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                    <input type="text" class="bae-social-caption-topic" placeholder="Example: Summer promo, grand opening, new product launch" style="flex:1;min-width:220px;padding:10px 12px;border-radius:10px;border:1px solid var(--border);background:var(--bg-3);color:var(--text);">
                    <button type="button" class="bae-btn bae-btn-primary bae-btn-sm bae-social-caption-generate" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>">Generate</button>
                    <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-social-caption-more" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>">Generate More</button>
                </div>
                <div class="bae-social-caption-status" style="font-size:12px;color:var(--text-3);margin-bottom:10px;"></div>
                <div class="bae-social-caption-results" style="display:flex;flex-direction:column;gap:10px;"></div>
            </div>
            <?php endif; ?>
            <div class="bae-asset-info">
                <div class="bae-asset-name">
                    <?php echo $meta['name']; ?>
                    <?php if ( ! empty($meta['custom']) ): ?>
                    <span style="font-size:10px;font-weight:700;background:linear-gradient(135deg,rgba(195,25,106,.15),rgba(243,45,134,.15));color:var(--brand-soft);border:1px solid rgba(243,45,134,.25);border-radius:999px;padding:2px 7px;margin-left:6px;vertical-align:middle;">AI</span>
                    <?php endif; ?>
                </div>
                <div class="bae-asset-meta"><?php echo $meta['desc']; ?></div>
            </div>
            <div class="bae-asset-actions">
                <?php if ($is_gen): ?>
                    <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-preview-btn"
                            data-type="<?php echo $type; ?>"
                            data-pid="<?php echo esc_attr($profile_id); ?>"
                            data-nonce="<?php echo esc_attr($nonce); ?>"
                            data-name="<?php echo esc_attr($meta['name']); ?>"
                            data-html="<?php echo esc_attr($gen_data['asset_html']); ?>">
                        Preview
                    </button>
                    <?php if ($is_free): ?>
                    <button type="button" class="bae-btn bae-btn-outline bae-btn-sm" onclick="baePricingOpen('Regenerate anytime', 'Free plan generates each asset once. Upgrade to regenerate whenever you update your brand.')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
                        Regen
                    </button>
                    <?php else: ?>
                    <button type="button" class="bae-btn bae-btn-primary bae-btn-sm bae-regen-btn"
                            data-type="<?php echo $type; ?>"
                            data-nonce="<?php echo $nonce; ?>"
                            data-pid="<?php echo $profile_id; ?>">
                        Regenerate
                    </button>
                    <?php endif; ?>
                    <?php if (!empty($gen_data['asset_html_prev'])): ?>
                    <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-undo-btn"
                            data-type="<?php echo $type; ?>"
                            data-nonce="<?php echo $nonce; ?>"
                            data-pid="<?php echo $profile_id; ?>"
                            data-mode="undo"
                            title="Undo last generation">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                    </button>
                    <?php endif; ?>
                    <?php if ($type === 'social_kit'): ?>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                        <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-regen-btn"
                                data-type="social_kit" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>"
                                data-prompt="Make this a square 1:1 format (1080x1080px) for Instagram feed">
                            1:1
                        </button>
                        <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-regen-btn"
                                data-type="social_kit" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>"
                                data-prompt="Make this a vertical 9:16 story format (1080x1920px) for Instagram/TikTok stories">
                            9:16
                        </button>
                        <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-regen-btn"
                                data-type="social_kit" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>"
                                data-prompt="Make this a horizontal 16:9 banner format (1920x1080px) for YouTube/Facebook cover">
                            16:9
                        </button>
                    </div>
                    <?php endif; ?>
                    <button type="button" class="bae-btn bae-btn-outline bae-btn-sm bae-delete-btn"
                            data-type="<?php echo $type; ?>"
                            data-id="<?php echo $gen_data['id']; ?>"
                            data-nonce="<?php echo $nonce; ?>">
                        &times;
                    </button>
                <?php else: ?>
                    <?php if ( empty($meta['custom']) ): ?>
                    <button type="button" class="bae-btn bae-btn-primary bae-btn-sm bae-gen-btn"
                            data-type="<?php echo $type; ?>"
                            data-nonce="<?php echo $nonce; ?>"
                            data-pid="<?php echo $profile_id; ?>">
                        Generate
                    </button>
                    <?php if ($is_free): ?>
                    <span style="font-size:10px;font-weight:600;color:var(--brand-soft);white-space:nowrap;display:flex;align-items:center;gap:3px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                        Free taste
                    </span>
                    <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div><!-- /bae-assets-grid-container -->

    <script>
    (function() {
        // ── Asset Search ──────────────────────────────────────────────────────
        var searchInput = document.getElementById('bae-asset-search-input');
        var noResults   = document.getElementById('bae-asset-no-results');
        var countLabel  = document.getElementById('bae-asset-count-label');

        function baeFilterAssets() {
            var q = (searchInput ? searchInput.value : '').toLowerCase().trim();
            var cards = document.querySelectorAll('#bae-assets-grid-container .bae-asset-card');
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
        if (searchInput) {
            searchInput.addEventListener('input', baeFilterAssets);
        }

        // ── Drag-and-Drop Reorder ─────────────────────────────────────────────
        var grid = document.getElementById('bae-assets-grid-container');
        var dragging = null;
        var ORDER_KEY = 'bae_asset_order_<?php echo esc_js($profile_id); ?>';

        function saveOrder() {
            try {
                var cards = grid.querySelectorAll('.bae-asset-card[data-asset-type]');
                var order = Array.from(cards).map(function(c) { return c.dataset.assetType; });
                localStorage.setItem(ORDER_KEY, JSON.stringify(order));
            } catch(e) {}
        }

        function loadOrder() {
            try {
                var saved = localStorage.getItem(ORDER_KEY);
                if (!saved) return;
                var order = JSON.parse(saved);
                var noResultsEl = document.getElementById('bae-asset-no-results');
                order.forEach(function(type) {
                    var card = document.getElementById('bae-card-' + type);
                    if (card) grid.appendChild(card);
                });
                if (noResultsEl) grid.appendChild(noResultsEl);
            } catch(e) {}
        }
        loadOrder();

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
            saveOrder();
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
            // Insert dragging before or after target
            var rect = target.getBoundingClientRect();
            var midX = rect.left + rect.width / 2;
            if (e.clientX < midX) {
                grid.insertBefore(dragging, target);
            } else {
                grid.insertBefore(dragging, target.nextSibling);
            }
            saveOrder();
        });
    })();
    </script>

    <!-- Custom AI Generator -->
    <?php
    // Count custom AI assets already generated by this user
    $custom_ai_count = 0;
    foreach ( $gen_map as $type => $asset ) {
        if ( strpos($type, 'custom_') === 0 ) $custom_ai_count++;
    }
    $free_ai_used = $is_free && $custom_ai_count >= 1;
    ?>
    <div class="bae-card" style="margin-top:24px;position:relative;overflow:hidden;">
        <?php if ($free_ai_used): ?>
        <div style="position:absolute;inset:0;background:rgba(10,10,15,.75);backdrop-filter:blur(4px);border-radius:18px;z-index:10;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;cursor:pointer;" onclick="baePricingOpen('Unlock more AI generations', 'You\'ve used your one free AI generation. Upgrade to keep generating custom brand assets with AI.')">
            <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#c4196a,#F32D86);display:flex;align-items:center;justify-content:center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
            </div>
            <div style="text-align:center;">
                <div style="font-size:14px;font-weight:700;color:white;margin-bottom:4px;">You've used your free AI generation</div>
                <div style="font-size:12px;color:rgba(255,255,255,.5);">Upgrade for unlimited custom AI assets</div>
            </div>
            <button style="background:linear-gradient(135deg,#c4196a,#F32D86);color:white;border:none;border-radius:10px;padding:10px 24px;font-size:13px;font-weight:700;font-family:'Geist',sans-serif;cursor:pointer;box-shadow:0 4px 16px rgba(195,25,106,.4);">Upgrade to Unlock ✦</button>
        </div>
        <?php endif; ?>
        <div class="bae-card-title" style="display:flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--brand-soft)"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
            Custom AI Generator
        </div>
        <div class="bae-card-desc" style="margin-top:4px;margin-bottom:16px;">Describe anything and AI will generate it using your brand. Not limited to the 7 assets above.</div>
        <textarea id="bae-ai-prompt" rows="3"
            placeholder="e.g. Create a grand opening flyer with 20% discount&#10;e.g. Design a WhatsApp business banner&#10;e.g. Make a cafe menu price list"
            style="width:100%;background:var(--input-bg);border:1.5px solid var(--input-bd);border-radius:10px;padding:12px 14px;font-size:14px;font-family:'Geist',sans-serif;color:var(--text);outline:none;resize:vertical;transition:border-color .2s;box-sizing:border-box;"></textarea>
        <div style="display:flex;align-items:center;gap:10px;margin-top:10px;flex-wrap:wrap;">
            <button type="button" id="bae-ai-gen-btn" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>"
                style="background:linear-gradient(135deg,#c4196a,#F32D86);color:white;border:none;border-radius:10px;padding:10px 20px;font-size:14px;font-weight:700;font-family:'Geist',sans-serif;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(195,25,106,.3);transition:all .2s;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                Generate
            </button>
            <span id="bae-ai-status" style="font-size:13px;color:var(--text-3);"></span>
        </div>
        <div id="bae-ai-result" style="display:none;margin-top:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
                <div style="font-size:12px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;">Result</div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="button" id="bae-ai-copy" style="background:var(--surface);border:1px solid var(--border-2);border-radius:7px;padding:6px 12px;font-size:12px;font-weight:600;color:var(--text-2);cursor:pointer;font-family:'Geist',sans-serif;">Copy HTML</button>
                    <button type="button" id="bae-ai-preview" style="background:var(--surface);border:1px solid var(--border-2);border-radius:7px;padding:6px 12px;font-size:12px;font-weight:600;color:var(--text-2);cursor:pointer;font-family:'Geist',sans-serif;">Preview</button>
                    <button type="button" id="bae-ai-save-btn" style="background:linear-gradient(135deg,#c4196a,#F32D86);color:white;border:none;border-radius:7px;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Geist',sans-serif;display:flex;align-items:center;gap:5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                        Save as Asset
                    </button>
                </div>
            </div>
            <!-- Save name input — hidden until Save clicked -->
            <div id="bae-ai-save-row" style="display:none;margin-bottom:10px;display:none;align-items:center;gap:8px;flex-wrap:wrap;">
                <input type="text" id="bae-ai-save-name"
                    placeholder="Asset name e.g. Grand Opening Flyer"
                    style="flex:1;min-width:200px;background:var(--input-bg);border:1.5px solid var(--input-bd);border-radius:8px;padding:8px 12px;font-size:13px;font-family:'Geist',sans-serif;color:var(--text);outline:none;">
                <button type="button" id="bae-ai-save-confirm" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>"
                    style="background:linear-gradient(135deg,#059669,#10b981);color:white;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:'Geist',sans-serif;white-space:nowrap;">
                    Confirm Save
                </button>
                <button type="button" id="bae-ai-save-cancel"
                    style="background:var(--surface);border:1px solid var(--border-2);border-radius:8px;padding:8px 12px;font-size:13px;font-weight:600;color:var(--text-3);cursor:pointer;font-family:'Geist',sans-serif;">
                    Cancel
                </button>
                <span id="bae-ai-save-status" style="font-size:12px;color:var(--text-3);"></span>
            </div>
            <div id="bae-ai-frame" style="background:white;border:1px solid var(--border);border-radius:10px;padding:20px;overflow:auto;max-height:480px;"></div>
        </div>
    </div>

    <!-- Brand Tools (Consistency) -->
    <div class="bae-card" style="margin-top:24px;">
        <div class="bae-card-title" style="display:flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--brand-soft)"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
            Brand Tools
        </div>
        <div class="bae-card-desc" style="margin-top:4px;margin-bottom:14px;">Quick helpers to improve typography and keep assets consistent.</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <button type="button" id="bae-consistency-btn" class="bae-btn bae-btn-outline" data-nonce="<?php echo $nonce; ?>" data-pid="<?php echo $profile_id; ?>" style="display:inline-flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                Scan consistency
            </button>
            <span id="bae-consistency-status" style="font-size:13px;color:var(--text-3);"></span>
        </div>
    </div>

    <!-- Brand Tools Modal -->
    <div id="bae-tools-modal-overlay" class="bae-modal-overlay" style="display:none;">
        <div class="bae-modal" style="max-width:860px;">
            <div class="bae-modal-header">
                <span class="bae-modal-title" id="bae-tools-modal-title">Brand Tools</span>
                <button type="button" class="bae-modal-close" id="bae-tools-modal-close">&times;</button>
            </div>
            <div class="bae-modal-body">
                <div id="bae-tools-modal-body" style="font-size:14px;color:var(--text-2);"></div>
            </div>
            <div class="bae-modal-footer" style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="bae-btn bae-btn-outline" id="bae-tools-modal-ok">Close</button>
            </div>
        </div>
    </div>

    <!-- Regen Prompt Modal -->
    <div id="bae-regen-modal-overlay" class="bae-modal-overlay" style="display:none;">
        <div class="bae-modal">
            <div class="bae-modal-header">
                <span class="bae-modal-title">Regenerate with AI Improvements</span>
                <button type="button" class="bae-modal-close" id="bae-regen-modal-close">&times;</button>
            </div>
            <div class="bae-modal-body">
                <p style="margin-bottom:16px;font-size:14px;color:var(--text-3);">Describe how you'd like to improve this asset. Keep it relevant to the asset type.</p>
                <textarea id="bae-regen-prompt" rows="3" placeholder="e.g. Make the colors brighter, add more contact info, change the layout..." style="width:100%;background:var(--input-bg);border:1.5px solid var(--input-bd);border-radius:10px;padding:12px 14px;font-size:14px;font-family:'Geist',sans-serif;color:var(--text);outline:none;resize:vertical;"></textarea>
                <div id="bae-regen-error" style="font-size:12px;color:#fb7185;margin-top:8px;display:none;"></div>
            </div>
            <div class="bae-modal-footer">
                <button type="button" class="bae-btn bae-btn-outline" id="bae-regen-skip">Skip Prompt</button>
                <button type="button" class="bae-btn bae-btn-primary" id="bae-regen-generate">Regenerate with AI</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var baeNeedsFirstAssetView = <?php echo $needs_first_asset_view ? 'true' : 'false'; ?>;
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

            var stayOnAssetsUrl = null;
            try {
                var u = new URL(window.location.href);
                u.searchParams.set('tab', 'assets');
                stayOnAssetsUrl = u.toString();
            } catch (e) {
                stayOnAssetsUrl = window.location.pathname + '?tab=assets';
            }

            fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (j && j.success) {
                        baeNeedsFirstAssetView = false;
                        var guide = document.getElementById('bae-assets-first-view-guide');
                        if (guide) {
                            guide.innerHTML = 'Nice. <strong>Brand Kit</strong> and <strong>Launch Toolkit</strong> are now unlocked. Refreshing this page to update navigation...';
                        }
                        setTimeout(function() {
                            window.location.href = stayOnAssetsUrl;
                        }, 1100);
                    }
                })
                .catch(function() {});
        }

        // Preview
        document.querySelectorAll('.bae-preview-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                baeMarkFirstAssetViewed(this);
                baeOpenModal(this.dataset.name, this.dataset.html);
            });
        });

        // Single Generate (new assets - static)
        document.querySelectorAll('.bae-gen-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                baeGenerateAsset(this.dataset.type, this.dataset.nonce, this.dataset.pid, this, ''); // empty prompt for static
            });
        });

        // Regenerate (existing assets - with AI prompt)
        document.querySelectorAll('.bae-regen-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                var presetPrompt = this.dataset.prompt || '';
                // If button has a preset prompt (size variants), skip modal and regenerate directly
                if (presetPrompt) {
                    var type  = this.dataset.type;
                    var nonce = this.dataset.nonce;
                    var pid   = this.dataset.pid;
                    baeGenerateAsset(type, nonce, pid, this, presetPrompt);
                    return;
                }
                // Open regen modal
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
                    modal.dataset.triggerType = this.dataset.type;
                }
            });
        });

        // Regen Modal Handlers
        var regenModal = document.getElementById('bae-regen-modal-overlay');
        var regenClose = document.getElementById('bae-regen-modal-close');
        var regenSkip = document.getElementById('bae-regen-skip');
        var regenGenerate = document.getElementById('bae-regen-generate');
        var regenPrompt = document.getElementById('bae-regen-prompt');
        var regenError = document.getElementById('bae-regen-error');

        if (regenClose) {
            regenClose.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                regenModal.style.display = 'none';
            });
        }

        if (regenSkip) {
            regenSkip.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                // Regenerate without prompt
                var type = regenModal.dataset.type;
                var nonce = regenModal.dataset.nonce;
                var pid = regenModal.dataset.pid;
                regenModal.style.display = 'none';
                var cardBtn = document.querySelector('.bae-regen-btn[data-type="' + type + '"]');
                baeGenerateAsset(type, nonce, pid, cardBtn, ''); // empty prompt
            });
        }

        if (regenGenerate) {
            regenGenerate.addEventListener('click', function(e) {
                if (e && e.preventDefault) e.preventDefault();
                var prompt = regenPrompt.value.trim();
                if (!prompt) {
                    regenError.textContent = 'Please enter an improvement prompt.';
                    regenError.style.display = 'block';
                    return;
                }
                // Basic client-side validation: check if prompt is relevant
                var type = regenModal.dataset.type;
                var assetNames = {
                    'business_card': 'business card',
                    'letterhead': 'letterhead',
                    'email_signature': 'email signature',
                    'social_kit': 'social media kit',
                    'brand_guidelines': 'brand guidelines',
                    'sitemap': 'sitemap'
                };
                var assetName = assetNames[type] || 'asset';
                var lowerPrompt = prompt.toLowerCase();
                // Deny if prompt suggests creating something completely different
                var denyWords = ['create a website', 'make a flyer', 'design a poster', 'build an app', 'develop software'];
                for (var deny of denyWords) {
                    if (lowerPrompt.includes(deny)) {
                        regenError.textContent = 'Prompt must improve the existing ' + assetName + ', not create something new.';
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
        }

        function baeUndoButtonMarkup(mode) {
            if (mode === 'redo') {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg><span>Redo</span>';
            }
            return '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg><span>Undo</span>';
        }

        function baeSetUndoButtonState(btn, mode) {
            if (!btn) return;
            btn.dataset.mode = mode;
            btn.title = mode === 'redo' ? 'Redo last undo' : 'Undo last generation';
            btn.style.display = '';
            btn.disabled = false;
            btn.innerHTML = baeUndoButtonMarkup(mode);
        }

        function baeFindUndoInsertTarget(actions) {
            return actions.querySelector('.bae-delete-btn')
                || actions.querySelector('.bae-download-btn')
                || null;
        }

        function baeEnsureUndoButton(card, type, nonce, pid) {
            if (!card) return null;
            var btn = card.querySelector('.bae-undo-btn');
            if (!btn) {
                var actions = card.querySelector('.bae-asset-actions');
                if (!actions) return null;
                btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'bae-btn bae-btn-outline bae-btn-sm bae-undo-btn';
                btn.dataset.type = type;
                btn.dataset.nonce = nonce;
                btn.dataset.pid = pid;
                var insertBefore = baeFindUndoInsertTarget(actions);
                if (insertBefore) actions.insertBefore(btn, insertBefore);
                else actions.appendChild(btn);
            }
            btn.dataset.type = type;
            btn.dataset.nonce = nonce;
            btn.dataset.pid = pid;
            baeSetUndoButtonState(btn, 'undo');
            baeBindUndoButton(btn);
            return btn;
        }

        function baeBindUndoButton(btn) {
            if (!btn || btn.dataset.undoBound === '1') return;
            btn.dataset.undoBound = '1';
            if (!btn.dataset.mode) {
                btn.dataset.mode = 'undo';
            }
            baeSetUndoButtonState(btn, btn.dataset.mode);
            btn.addEventListener('click', function() {
                var type  = this.dataset.type;
                var nonce = this.dataset.nonce;
                var pid   = this.dataset.pid;
                var self  = this;
                var currentMode = self.dataset.mode || 'undo';
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
                        baeSetUndoButtonState(self, currentMode === 'undo' ? 'redo' : 'undo');
                    } else {
                        baeNotify((json.data && json.data.message) ? json.data.message : 'Undo failed.', 'error');
                        self.disabled = false;
                    }
                })
                .catch(function() {
                    baeNotify('Undo failed.', 'error');
                    self.disabled = false;
                });
            });
        }

        document.querySelectorAll('.bae-undo-btn').forEach(function(btn) {
            baeBindUndoButton(btn);
        });

        document.querySelectorAll('#bae-card-social_kit .bae-social-tab').forEach(function(tabBtn) {
            tabBtn.addEventListener('click', function() {
                var card = this.closest('#bae-card-social_kit');
                if (!card) return;
                var preview = card.querySelector('.bae-asset-preview');
                var panel = card.querySelector('.bae-social-caption-panel');
                card.querySelectorAll('.bae-social-tab').forEach(function(btn) {
                    btn.classList.remove('is-active');
                });
                this.classList.add('is-active');
                if (this.dataset.target === 'captions') {
                    if (preview) preview.style.display = 'none';
                    if (panel) panel.style.display = 'block';
                } else {
                    if (preview) preview.style.display = '';
                    if (panel) panel.style.display = 'none';
                }
            });
        });

        function baeRenderSocialCaptions(card, captions) {
            var results = card.querySelector('.bae-social-caption-results');
            if (!results) return;
            results.innerHTML = '';
            captions.forEach(function(item) {
                var row = document.createElement('div');
                row.style.cssText = 'padding:12px;border-radius:12px;background:var(--bg-3);border:1px solid var(--border);';
                var wrap = document.createElement('div');
                wrap.style.cssText = 'display:flex;align-items:flex-start;justify-content:space-between;gap:10px;';
                var textCol = document.createElement('div');
                textCol.style.cssText = 'flex:1;min-width:0;';
                var tone = document.createElement('div');
                tone.style.cssText = 'font-size:11px;font-weight:700;color:var(--brand-soft);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;';
                tone.textContent = item.tone;
                var caption = document.createElement('div');
                caption.style.cssText = 'font-size:13px;line-height:1.7;color:var(--text);white-space:pre-wrap;';
                caption.textContent = item.caption;
                var copyBtn = document.createElement('button');
                copyBtn.type = 'button';
                copyBtn.className = 'bae-btn bae-btn-outline bae-btn-sm';
                copyBtn.textContent = 'Copy';
                textCol.appendChild(tone);
                textCol.appendChild(caption);
                wrap.appendChild(textCol);
                wrap.appendChild(copyBtn);
                row.appendChild(wrap);
                copyBtn.addEventListener('click', function() {
                    baeCopyText(item.caption, this);
                });
                results.appendChild(row);
            });
        }

        function baeGenerateSocialCaptions(btn) {
            var card = btn.closest('#bae-card-social_kit');
            if (!card) return;
            var topicInput = card.querySelector('.bae-social-caption-topic');
            var status = card.querySelector('.bae-social-caption-status');
            var results = card.querySelector('.bae-social-caption-results');
            var topic = (topicInput && topicInput.value ? topicInput.value : '').trim();
            if (!topic) {
                if (status) {
                    status.textContent = 'Please enter a topic first.';
                    status.style.color = '#fb7185';
                }
                return;
            }

            var fd = new FormData();
            fd.append('action', 'bae_social_captions');
            fd.append('nonce', btn.dataset.nonce);
            fd.append('profile_id', btn.dataset.pid);
            fd.append('topic', topic);

            if (status) {
                status.textContent = 'Generating captions...';
                status.style.color = 'var(--text-3)';
            }
            if (results) results.innerHTML = '';
            btn.disabled = true;

            fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    btn.disabled = false;
                    if (json.success && json.data && json.data.captions) {
                        if (status) {
                            status.textContent = '5 caption styles ready.';
                            status.style.color = '#34d399';
                        }
                        baeRenderSocialCaptions(card, json.data.captions);
                    } else if (status) {
                        status.textContent = (json.data && json.data.message) ? json.data.message : 'Could not generate captions.';
                        status.style.color = '#fb7185';
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    if (status) {
                        status.textContent = 'Connection error. Please try again.';
                        status.style.color = '#fb7185';
                    }
                });
        }

        document.querySelectorAll('#bae-card-social_kit .bae-social-caption-generate, #bae-card-social_kit .bae-social-caption-more').forEach(function(btn) {
            btn.addEventListener('click', function() {
                baeGenerateSocialCaptions(this);
            });
        });

        // Delete
        document.querySelectorAll('.bae-delete-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id    = this.dataset.id;
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

        // ── CHANGED: Sequential Generate All with progress bar ──────────
        var genAllBtn  = document.getElementById('bae-generate-all-btn');
        var genAllMsg  = document.getElementById('bae-generate-all-msg');
        var progressWrap = document.getElementById('bae-progress-wrap');
        var progressFill = document.getElementById('bae-progress-fill');
        var progressLabel = document.getElementById('bae-progress-label');

        var types = ['business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'sitemap', 'invoice_template', 'price_list', 'flyer_template', 'thank_you_card', 'media_kit', 'poster_a3'];

        function setStepState(type, state) {
            var el = document.getElementById('bae-step-' + type);
            if (!el) return;
            el.className = 'bae-progress-step ' + state;
        }

        function setProgress(done, total, label) {
            var pct = Math.round((done / total) * 100);
            progressFill.style.width = pct + '%';
            progressLabel.textContent = label;
        }

        if (genAllBtn) {
            genAllBtn.addEventListener('click', function() {
                var nonce = this.dataset.nonce;
                var pid   = this.dataset.pid;

                genAllBtn.disabled = true;
                genAllBtn.textContent = 'Generating...';
                genAllMsg.innerHTML = '';
                progressWrap.classList.add('visible');
                setProgress(0, types.length, 'Starting generation...');

                // CHANGED: Sequential — each fetch waits for previous to complete
                var errors = [];

                function runNext(index) {
                    if (index >= types.length) {
                        // All done
                        var pct = errors.length === 0 ? 100 : Math.round(((types.length - errors.length) / types.length) * 100);
                        progressFill.style.width = '100%';
                        if (errors.length === 0) {
                            progressLabel.textContent = 'All assets generated!';
                            genAllMsg.innerHTML = '<div class="bae-notice bae-notice-success" style="margin-top:12px;">All 7 assets generated successfully!</div>';
                        } else {
                            progressLabel.textContent = (types.length - errors.length) + ' of ' + types.length + ' succeeded.';
                            genAllMsg.innerHTML = '<div class="bae-notice bae-notice-error" style="margin-top:12px;">Some assets failed: ' + errors.join(', ') + '. Please try regenerating them individually.</div>';
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
                        if (json.success) {
                            setStepState(type, 'done');
                        } else {
                            setStepState(type, 'error');
                            errors.push(type.replace(/_/g,' '));
                        }
                        runNext(index + 1);
                    })
                    .catch(function() {
                        setStepState(type, 'error');
                        errors.push(type.replace(/_/g,' '));
                        runNext(index + 1);
                    });
                }

                runNext(0);
            });
        }

        // ── Single asset generate helper ──────────────────────────────────
        function baeGenerateAsset(type, nonce, pid, btn, prompt) {
            var original = btn ? btn.textContent : 'Generating...';
            var isRegen = prompt && prompt.trim();
            if (btn) {
                btn.disabled = true;
                btn.textContent = isRegen ? 'Regenerating...' : 'Generating...';
            }

            var formData = new FormData();
            formData.append('action', 'bae_generate_asset');
            formData.append('asset_type', type);
            formData.append('profile_id', pid);
            formData.append('nonce', nonce);
            if (prompt) {
                formData.append('regen_prompt', prompt);
            }

            fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                console.log('[BAE Regen] Response received:', type, 'isRegen=', isRegen, json);
                if (json.success) {
                    var card = document.getElementById('bae-card-' + type);
                    var html = (typeof json.html === 'string' && json.html.trim()) ? json.html : '<div style="padding:18px 14px;color:#6b7280;">Asset generated. Refresh to view.</div>';
                    var isFallback = !(typeof json.html === 'string' && json.html.trim());

                    // Update preview in place (for both generate and regenerate)
                    if (card) {
                        var previewInner = card.querySelector('.bae-asset-preview-inner');
                        if (previewInner) {
                            previewInner.innerHTML = html;
                        }
                    }
                    // If it was empty (svg placeholder), replace with div
                    var preview = card ? card.querySelector('.bae-asset-preview') : null;
                    var hasSvg = preview ? !!preview.querySelector('svg') : false;
                    if (preview && hasSvg) {
                        preview.innerHTML = '<div class="bae-asset-preview-inner bae-ai-asset">' + html + '</div>';
                        preview.style.display = 'none';
                        preview.offsetHeight;
                        preview.style.display = '';
                    }

                    if (card) {
                        var existingUndoBtn = card.querySelector('.bae-undo-btn');
                        if (existingUndoBtn || isRegen) {
                            baeEnsureUndoButton(card, type, nonce, pid);
                        }
                    }

                    // If it's the fallback message, reload to show the actual asset
                    if (isFallback) {
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    }

                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = original;
                    }
                } else {
                    console.log('[BAE Regen] Error:', json.data.message);
                    baeNotify(json.data.message || 'Generation failed.', 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = original;
                    }
                }
            })
            .catch(function() {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = original;
                }
            });
        }
        // ── Custom AI Generator ─────────────────────────────────────────
        var aiBtn     = document.getElementById('bae-ai-gen-btn');
        var aiPrompt  = document.getElementById('bae-ai-prompt');
        var aiStatus  = document.getElementById('bae-ai-status');
        var aiResult  = document.getElementById('bae-ai-result');
        var aiFrame   = document.getElementById('bae-ai-frame');
        var aiCopy    = document.getElementById('bae-ai-copy');
        var aiPreview = document.getElementById('bae-ai-preview');

        if (aiBtn) {
            aiBtn.addEventListener('click', function() {
                var prompt = (aiPrompt.value || '').trim();
                if (!prompt) {
                    aiStatus.textContent = 'Please describe what you want to generate.';
                    aiStatus.style.color = '#fb7185';
                    return;
                }
                var nonce = this.dataset.nonce;
                var pid   = this.dataset.pid;
                aiBtn.disabled = true;
                aiStatus.style.color = 'var(--text-3)';
                aiStatus.textContent = 'AI is generating...';
                aiResult.style.display = 'none';

                var fd = new FormData();
                fd.append('action',        'bae_custom_generate');
                fd.append('nonce',         nonce);
                fd.append('profile_id',    pid);
                fd.append('custom_prompt', prompt);

                fetch(ajaxurl, {method:'POST', body:fd})
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    aiBtn.disabled = false;
                    if (j.success) {
                        aiStatus.textContent = 'Done.';
                        aiStatus.style.color = '#34d399';
                        aiFrame.innerHTML = '<div class="bae-ai-asset" style="isolation:isolate;">' + j.data.html + '</div>';
                        aiResult.style.display = 'block';
                        if (window.gsap) gsap.fromTo(aiResult,{opacity:0,y:8},{opacity:1,y:0,duration:.35,ease:'power3.out'});
                        setTimeout(function() { aiStatus.textContent = ''; }, 2000);
                    } else {
                        aiStatus.textContent = (j.data && j.data.message) ? j.data.message : 'Generation failed.';
                        aiStatus.style.color = '#fb7185';
                    }
                })
                .catch(function() {
                    aiBtn.disabled = false;
                    aiStatus.textContent = 'Connection error. Please try again.';
                    aiStatus.style.color = '#fb7185';
                });
            });
        }

        if (aiCopy) {
            aiCopy.addEventListener('click', function() {
                var innerEl = aiFrame ? aiFrame.querySelector('.bae-ai-asset') : null;
                var html = innerEl ? innerEl.innerHTML : (aiFrame ? aiFrame.innerHTML : '');
                navigator.clipboard.writeText(html).then(function() {
                    aiCopy.textContent = 'Copied!';
                    aiCopy.style.color = '#34d399';
                    setTimeout(function() { aiCopy.textContent = 'Copy HTML'; aiCopy.style.color = ''; }, 2000);
                });
            });
        }

        if (aiPreview) {
            aiPreview.addEventListener('click', function() {
                var _inner = aiFrame ? aiFrame.querySelector('.bae-ai-asset') : null;
                baeOpenModal('Custom Asset Preview', _inner ? _inner.innerHTML : (aiFrame ? aiFrame.innerHTML : ''));
            });
        }

        // ── Save as Asset ────────────────────────────────────────────────
        var aiSaveBtn     = document.getElementById('bae-ai-save-btn');
        var aiSaveRow     = document.getElementById('bae-ai-save-row');
        var aiSaveName    = document.getElementById('bae-ai-save-name');
        var aiSaveConfirm = document.getElementById('bae-ai-save-confirm');
        var aiSaveCancel  = document.getElementById('bae-ai-save-cancel');
        var aiSaveStatus  = document.getElementById('bae-ai-save-status');

        if (aiSaveBtn) {
            aiSaveBtn.addEventListener('click', function() {
                // Pre-fill name from the prompt
                var promptText = aiPrompt ? aiPrompt.value.trim() : '';
                if (aiSaveName && promptText) {
                    // Capitalise first letter
                    aiSaveName.value = promptText.charAt(0).toUpperCase() + promptText.slice(1);
                }
                aiSaveRow.style.display = 'flex';
                if (aiSaveName) aiSaveName.focus();
            });
        }

        if (aiSaveCancel) {
            aiSaveCancel.addEventListener('click', function() {
                aiSaveRow.style.display = 'none';
                aiSaveStatus.textContent = '';
            });
        }

        if (aiSaveConfirm) {
            aiSaveConfirm.addEventListener('click', function() {
                var name = aiSaveName ? aiSaveName.value.trim() : '';
                var _saveEl = aiFrame ? aiFrame.querySelector('.bae-ai-asset') : null;
                var html = _saveEl ? _saveEl.innerHTML : (aiFrame ? aiFrame.innerHTML : '');
                if (!name) {
                    aiSaveStatus.textContent = 'Please enter an asset name.';
                    aiSaveStatus.style.color = '#fb7185';
                    return;
                }
                if (!html) {
                    aiSaveStatus.textContent = 'Nothing to save yet.';
                    aiSaveStatus.style.color = '#fb7185';
                    return;
                }

                var nonce = this.dataset.nonce;
                var pid   = this.dataset.pid;
                aiSaveConfirm.disabled = true;
                aiSaveStatus.textContent = 'Saving...';
                aiSaveStatus.style.color = 'var(--text-3)';

                var fd = new FormData();
                fd.append('action',     'bae_save_custom_asset');
                fd.append('nonce',      nonce);
                fd.append('profile_id', pid);
                fd.append('asset_name', name);
                fd.append('asset_html', html);

                fetch(ajaxurl, {method:'POST', body:fd})
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    aiSaveConfirm.disabled = false;
                    if (j.success) {
                        aiSaveStatus.textContent = 'Saved!';
                        aiSaveStatus.style.color = '#34d399';
                        aiSaveRow.style.display = 'none';
                        // Reload to show new asset in grid
                        setTimeout(function() { location.reload(); }, 800);
                    } else {
                        aiSaveStatus.textContent = (j.data && j.data.message) ? j.data.message : 'Save failed.';
                        aiSaveStatus.style.color = '#fb7185';
                    }
                })
                .catch(function() {
                    aiSaveConfirm.disabled = false;
                    aiSaveStatus.textContent = 'Connection error.';
                    aiSaveStatus.style.color = '#fb7185';
                });
            });
        }

        if (aiSaveName) {
            aiSaveName.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') aiSaveConfirm && aiSaveConfirm.click();
            });
        }

        // ── Brand Tools: modal + actions (Overview) ─────────────────────
        var toolsModal = document.getElementById('bae-tools-modal-overlay');
        var toolsClose = document.getElementById('bae-tools-modal-close');
        var toolsOk    = document.getElementById('bae-tools-modal-ok');
        var toolsTitle = document.getElementById('bae-tools-modal-title');
        var toolsBody  = document.getElementById('bae-tools-modal-body');

        function baeToolsOpen(title, html) {
            if (!toolsModal || !toolsTitle || !toolsBody) return;
            toolsTitle.textContent = title;
            toolsBody.innerHTML = html;
            toolsModal.style.display = 'flex';
        }
        function baeToolsClose() {
            if (!toolsModal) return;
            toolsModal.style.display = 'none';
        }
        if (toolsClose) toolsClose.addEventListener('click', function(e){ if(e&&e.preventDefault) e.preventDefault(); baeToolsClose(); });
        if (toolsOk)    toolsOk.addEventListener('click', function(e){ if(e&&e.preventDefault) e.preventDefault(); baeToolsClose(); });

        // Consistency scan (Brand Tools card)
        var consBtn = document.getElementById('bae-consistency-btn');
        var consStatus = document.getElementById('bae-consistency-status');
        if (consBtn) {
            consBtn.addEventListener('click', function(e){
                if (e && e.preventDefault) e.preventDefault();
                var nonce = this.dataset.nonce;
                var pid   = this.dataset.pid;

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

                    if (!j.success) { baeNotify((j.data && j.data.message) ? j.data.message : 'Consistency scan failed.', 'error'); return; }
                    var report = j.data && j.data.report ? j.data.report : null;
                    if (!report) { baeNotify('No report returned.', 'error'); return; }

                    var score = report.score || 0;
                    var issues = report.issues || [];
                    var usedFonts = report.used_fonts || [];
                    var usedColors = report.used_colors || [];
                    var missing = report.missing_assets || [];

                    var html = '';
                    html += '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;">' +
                        '<div style="font-weight:800;color:var(--text);font-size:15px;">Consistency score: ' + score + '/100</div>' +
                        '<div style="font-size:12px;color:var(--text-3);">Based on saved asset HTML (colors + fonts).</div>' +
                    '</div>';

                    if (issues.length) {
                        html += '<div style="margin-bottom:12px;">' +
                            '<div style="font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--text-2);margin-bottom:8px;">Issues</div>' +
                            '<ul style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:6px;color:var(--text-2);">';
                        issues.forEach(function(it){ html += '<li>' + it + '</li>'; });
                        html += '</ul></div>';
                    } else {
                        html += '<div class="bae-notice bae-notice-success" style="margin-bottom:12px;">Looks consistent — nice work.</div>';
                    }

                    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start;">';
                    html += '<div style="padding:12px;border:1px solid var(--border-2);border-radius:12px;background:var(--bg-2);">' +
                        '<div style="font-size:12px;font-weight:800;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Fonts seen</div>' +
                        (usedFonts.length ? usedFonts.map(function(f){ return '<div style="font-size:13px;color:var(--text-2);">' + f + '</div>'; }).join('') : '<div style="font-size:13px;color:var(--text-3);">None detected</div>') +
                    '</div>';
                    html += '<div style="padding:12px;border:1px solid var(--border-2);border-radius:12px;background:var(--bg-2);">' +
                        '<div style="font-size:12px;font-weight:800;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Colors seen</div>' +
                        (usedColors.length ? usedColors.map(function(c){ return '<div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-2);"><span style="width:12px;height:12px;border-radius:4px;background:' + c + ';border:1px solid rgba(0,0,0,.12)"></span>' + c + '</div>'; }).join('') : '<div style="font-size:13px;color:var(--text-3);">None detected</div>') +
                    '</div>';
                    html += '</div>';

                    if (missing.length) {
                        html += '<div style="margin-top:12px;font-size:12px;color:var(--text-3);">Missing assets: ' + missing.join(', ') + '</div>';
                    }

                    if (issues.length) {
                        html += '<div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">' +
                            '<button id="bae-autofix-btn" style="display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,#c4196a,#F32D86);color:white;border:none;border-radius:10px;padding:10px 18px;font-size:13px;font-weight:700;font-family:\'Geist\',sans-serif;cursor:pointer;">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>' +
                            'Auto-fix All Issues</button>' +
                            '<span id="bae-autofix-status" style="font-size:12px;color:var(--text-3);"></span>' +
                            '</div>';
                    }

                    baeToolsOpen('Brand consistency scan', html);
                    // Wire auto-fix button after modal renders
                    setTimeout(function() {
                        var fixBtn = document.getElementById('bae-autofix-btn');
                        if (!fixBtn) return;
                        fixBtn.addEventListener('click', function() {
                            var statusEl = document.getElementById('bae-autofix-status');
                            fixBtn.disabled = true;
                            fixBtn.textContent = 'Fixing...';
                            if (statusEl) statusEl.textContent = 'Sending assets to Gemini...';
                            var fd = new FormData();
                            fd.append('action', 'bae_autofix_consistency');
                            fd.append('nonce', nonce);
                            fd.append('profile_id', pid);
                            fd.append('issues', issues.join('||'));
                            fetch(ajaxurl, { method:'POST', body:fd })
                            .then(function(r){ return r.json(); })
                            .then(function(j) {
                                if (j.success) {
                                    if (statusEl) statusEl.textContent = j.data.message;
                                    fixBtn.style.background = '#059669';
                                    fixBtn.textContent = 'Fixed!';
                                    // Update each card's preview inner directly — no reload
                                    var fixed = j.data.fixed_assets || {};
                                    Object.keys(fixed).forEach(function(type) {
                                        var card = document.getElementById('bae-card-' + type);
                                        if (!card) return;
                                        var inner = card.querySelector('.bae-asset-preview-inner');
                                        if (inner) inner.innerHTML = fixed[type];
                                    });
                                    // Close modal after a moment
                                    setTimeout(function() {
                                        var overlay = document.getElementById('bae-tools-modal-overlay');
                                        if (overlay) overlay.style.display = 'none';
                                    }, 1200);
                                } else {
                                    fixBtn.disabled = false;
                                    fixBtn.textContent = 'Auto-fix All Issues';
                                    if (statusEl) statusEl.textContent = (j.data && j.data.message) ? j.data.message : 'Fix failed.';
                                }
                            });
                        });
                    }, 100);
                })
                .catch(function(){
                    consBtn.disabled = false;
                    consBtn.textContent = originalText;
                    if (consStatus) consStatus.textContent = '';
                    baeNotify('Consistency scan failed.', 'error');
                });
            });
        }

    })();
    </script>
    <?php
    return ob_get_clean();
}

// =============================================================================
// TAB: BRAND KIT
// =============================================================================

