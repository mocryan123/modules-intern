<?php if (!defined('ABSPATH')) exit;
function bae_startup_tab($user_id, $profile) {
    if (empty($profile)) {
        ob_start(); ?>
        <div class="bae-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7a6 6 0 11-5.398-5.398"/></svg>
            <strong>Complete your Brand Profile first.</strong>
            <p><a href="?tab=overview" class="bae-btn bae-btn-primary" style="margin-top:12px;display:inline-flex;">Go to Brand Profile &rarr;</a></p>
        </div>
        <?php
        return ob_get_clean();
    }

    if (!bae_has_viewed_onboarding_asset($profile)) {
        ob_start(); ?>
        <div class="bae-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7a6 6 0 11-5.398-5.398"/></svg>
            <strong>Preview 1 generated asset first.</strong>
            <p style="color:var(--text-3);margin-bottom:16px;">Launch Toolkit unlocks after you preview one generated asset in Asset Generator.</p>
            <a href="?tab=assets" class="bae-btn bae-btn-primary" style="display:inline-flex;align-items:center;gap:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Go to Asset Generator
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    // Ticket claim/generation is now centralized in the header "Enter Ticket" modal.
    $claim_html = '';
    $biz_raw   = $p['business_name'] ?? 'yourbusiness';
    $biz_slug  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $biz_raw));
    $biz_words = explode(' ', strtolower(trim($biz_raw)));
    $biz_short = $biz_words[0] ?? $biz_slug;
    $industry  = $p['industry'] ?? 'Other';

    $domains = [
        ['domain' => $biz_slug . '.com',          'type' => '.com',   'rec' => true,  'note' => 'Best for global reach'],
        ['domain' => $biz_slug . '.ph',           'type' => '.ph',    'rec' => true,  'note' => 'Best for PH local market'],
        ['domain' => $biz_slug . 'ph.com',        'type' => '.com',   'rec' => false, 'note' => 'If .com is taken'],
        ['domain' => 'get' . $biz_slug . '.com',  'type' => '.com',   'rec' => false, 'note' => 'Action-oriented'],
        ['domain' => $biz_short . 'store.com',    'type' => '.com',   'rec' => false, 'note' => 'Good for e-commerce'],
        ['domain' => $biz_slug . '.shop',         'type' => '.shop',  'rec' => false, 'note' => 'New TLD, retail-focused'],
        ['domain' => $biz_slug . 'online.com',    'type' => '.com',   'rec' => false, 'note' => 'Emphasizes online presence'],
        ['domain' => 'the' . $biz_slug . '.com',  'type' => '.com',   'rec' => false, 'note' => 'Classic brand framing'],
    ];

    $social_svgs = [
        'fb' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
        'ig' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
        'tt' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.75a4.85 4.85 0 0 1-1.01-.06z"/></svg>',
        'yt' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>',
        'x'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.742l7.741-8.851L1.254 2.25H8.08l4.265 5.638L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'li' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>',
    ];

    $handles = [
        ['platform' => 'Facebook',   'icon' => 'fb',  'handle' => '@' . $biz_slug, 'color' => '#1877f2'],
        ['platform' => 'Instagram',  'icon' => 'ig',  'handle' => '@' . $biz_slug, 'color' => '#e1306c'],
        ['platform' => 'TikTok',     'icon' => 'tt',  'handle' => '@' . $biz_slug, 'color' => '#010101'],
        ['platform' => 'YouTube',    'icon' => 'yt',  'handle' => '@' . $biz_slug, 'color' => '#ff0000'],
        ['platform' => 'X (Twitter)','icon' => 'x',   'handle' => '@' . $biz_slug, 'color' => '#000000'],
        ['platform' => 'LinkedIn',   'icon' => 'li',  'handle' => $biz_slug,        'color' => '#0077b5'],
    ];

    $tagline_variants = bae_generate_tagline_variants($p['business_name'], $industry, $p['tagline'] ?? '');

    $email_ideas = [
        'hello@' . $biz_slug . '.com'   => 'Friendly first impression — great for customer-facing',
        'info@' . $biz_slug . '.com'    => 'Standard and professional — safe choice',
        'contact@' . $biz_slug . '.com' => 'Clearly a contact email — good for forms',
        $biz_slug . '@gmail.com'        => 'Free option — use if you don\'t have a domain yet',
    ];

    $pages = bae_get_industry_sitemap($industry, $biz_raw);

    // Launch checklist
    $checklist = [
        'Domain'    => [
            ['task' => 'Register your primary domain name (.com or .ph)', 'tools' => 'Namecheap, GoDaddy, dot.ph'],
            ['task' => 'Set up domain email (e.g. hello@yourbrand.com)',  'tools' => 'Google Workspace, Zoho Mail'],
            ['task' => 'Point domain to your website or hosting',         'tools' => 'Cloudflare, cPanel'],
        ],
        'Social'    => [
            ['task' => 'Register your brand handle on all major platforms', 'tools' => 'Facebook, Instagram, TikTok, YouTube'],
            ['task' => 'Set consistent profile photo and cover image',      'tools' => 'Use your Brand Kit assets'],
            ['task' => 'Write consistent bio/description across platforms', 'tools' => 'Use your tagline + website URL'],
        ],
        'Website'   => [
            ['task' => 'Set up your website with essential pages',         'tools' => 'WordPress, Wix, or Framer'],
            ['task' => 'Submit your sitemap to Google Search Console',     'tools' => 'Google Search Console (free)'],
            ['task' => 'Install Google Analytics for visitor tracking',    'tools' => 'Google Analytics 4 (free)'],
            ['task' => 'Add SSL certificate (https://)',                   'tools' => 'Included with most hosting'],
        ],
        'Brand'     => [
            ['task' => 'Download and save your Brand Book',               'tools' => 'Generated in Asset Generator tab'],
            ['task' => 'Share your Brand Kit link with designers/vendors','tools' => 'Brand Kit tab → make public'],
            ['task' => 'Create a Google Business Profile',                'tools' => 'business.google.com (free)'],
        ],
        'Legal'     => [
            ['task' => 'Register your business name with DTI or SEC',     'tools' => 'DTI Business Name Registration'],
            ['task' => 'Get your Barangay Business Permit',               'tools' => 'Your local Barangay Hall'],
            ['task' => 'Apply for Mayor\'s Permit (Business License)',    'tools' => 'Your City/Municipal Hall'],
            ['task' => 'Register with BIR for tax compliance',            'tools' => 'BIR (bir.gov.ph)'],
        ],
    ];

    // Checklist diary state (per user + profile)
    $toolkit_nonce = wp_create_nonce('bae_save_profile');
    $meta_key = 'bae_toolkit_checklist_' . intval($p['id'] ?? 0);
    $saved_raw = get_user_meta( $user_id, $meta_key, true );
    $saved_state = [];
    if ( is_string($saved_raw) && $saved_raw ) {
        $decoded = json_decode( $saved_raw, true );
        if ( is_array($decoded) ) $saved_state = $decoded;
    }

    ob_start();
    ?>
    <div style="margin-bottom:24px;">
        <div class="bae-card-title" style="font-size:20px;">Launch Toolkit</div>
        <div class="bae-card-desc" style="margin-top:6px;">Everything you need to launch <?php echo esc_html($biz_raw); ?> — from domain to legal registration. All generated from your brand profile.</div>
    </div>

    <!-- SECTION: Domain Names -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Domain Name Ideas</div>
                <div class="bae-card-desc">Suggested domain names based on your business name. Check availability before registering.</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;">
            <?php foreach ($domains as $d): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid <?php echo $d['rec'] ? '#111827' : '#e5e7eb'; ?>;border-radius:10px;background:<?php echo $d['rec'] ? '#111827' : '#fff'; ?>;">
                <div>
                    <div style="font-size:14px;font-weight:600;color:<?php echo $d['rec'] ? '#fff' : '#374151'; ?>;font-family:'Courier New',monospace;"><?php echo esc_html($d['domain']); ?></div>
                    <div style="font-size:11px;color:<?php echo $d['rec'] ? 'rgba(255,255,255,0.5)' : '#9ca3af'; ?>;margin-top:2px;"><?php echo esc_html($d['note']); ?></div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <?php if ($d['rec']): ?><span style="font-size:9px;background:var(--surface);color:var(--text);padding:2px 8px;border-radius:999px;font-weight:800;">TOP</span><?php endif; ?>
                    <button onclick="navigator.clipboard.writeText('<?php echo esc_js($d['domain']); ?>');this.textContent='Done';setTimeout(()=>this.textContent='Copy',1500);"
                            style="border:1px solid <?php echo $d['rec'] ? 'rgba(255,255,255,0.3)' : '#d1d5db'; ?>;background:none;color:<?php echo $d['rec'] ? '#fff' : '#6b7280'; ?>;font-size:11px;cursor:pointer;padding:4px 10px;border-radius:6px;">Copy</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="bae-notice bae-notice-info" style="margin-top:16px;">
            Check availability at <strong><a href="https://www.namecheap.com" target="_blank">Namecheap.com</a></strong> or <strong><a href="https://www.dot.ph" target="_blank">dot.ph</a></strong> for .ph domains. Average cost: ₱600–₱1,200/year for .com, ₱1,500–₱2,000/year for .ph.
        </div>
    </div>

    <!-- SECTION: Social Handles -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Social Media Handles</div>
                <div class="bae-card-desc">Register the same handle on every platform — consistency is a brand asset.</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;">
            <?php foreach ($handles as $h): ?>
            <div style="padding:14px 16px;border:1px solid var(--border);border-radius:10px;background:var(--surface);transition:border-color .2s;" onmouseenter="this.style.borderColor='var(--border-2)'" onmouseleave="this.style.borderColor='var(--border)'">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div style="width:28px;height:28px;border-radius:7px;background:<?php echo esc_attr($h['color']); ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:white;">
                        <?php echo $social_svgs[$h['icon']] ?? ''; ?>
                    </div>
                    <div style="font-size:12px;font-weight:600;color:var(--text-2);"><?php echo esc_html($h['platform']); ?></div>
                </div>
                <div style="font-size:13px;font-weight:700;color:var(--text);font-family:'Courier New',monospace;"><?php echo esc_html($h['handle']); ?></div>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($h['handle']); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500);"
                        style="margin-top:8px;border:1px solid var(--border-2);background:none;color:var(--text-3);font-size:11px;cursor:pointer;padding:4px 10px;border-radius:6px;width:100%;font-family:'Geist',sans-serif;transition:all .2s;">Copy</button>
            </div>
            <?php endforeach; ?>
        </div>
        </div>
        <div class="bae-notice bae-notice-info" style="margin-top:16px;">
            Register all handles even if you're not active on every platform yet — prevents brand squatting and ensures consistency when you scale.
        </div>
    </div>

    <!-- SECTION: Tagline Variants -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Tagline Variants</div>
                <div class="bae-card-desc">Use these across your website, social bios, packaging, and marketing materials.</div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($tagline_variants as $i => $variant): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border:1px solid <?php echo $i === 0 ? '#111827' : '#e5e7eb'; ?>;border-radius:10px;background:<?php echo $i === 0 ? '#fafafa' : '#fff'; ?>;">
                <div>
                    <span style="font-size:15px;color:var(--text-2);font-style:italic;">"<?php echo esc_html($variant); ?>"</span>
                    <?php if ($i === 0 && !empty($p['tagline'])): ?><span style="margin-left:10px;font-size:10px;background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:999px;font-weight:700;">CURRENT</span><?php endif; ?>
                </div>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($variant); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500);"
                        style="border:1px solid var(--border-2);background:none;color:var(--text-3);font-size:11px;cursor:pointer;padding:5px 12px;border-radius:6px;white-space:nowrap;">Copy</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SECTION: Business Email -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Business Email Suggestions</div>
                <div class="bae-card-desc">A professional email builds trust immediately. Avoid using personal Gmail for business communications.</div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($email_ideas as $email => $note): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid var(--border);border-radius:10px;">
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--text-2);font-family:'Courier New',monospace;"><?php echo esc_html($email); ?></div>
                    <div style="font-size:12px;color:var(--text-3);margin-top:3px;"><?php echo esc_html($note); ?></div>
                </div>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($email); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500);"
                        style="border:1px solid var(--border-2);background:none;color:var(--text-3);font-size:11px;cursor:pointer;padding:5px 12px;border-radius:6px;">Copy</button>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="bae-notice bae-notice-info" style="margin-top:16px;">
            Get a professional email at <strong>Google Workspace</strong> (~₱280/mo) or free with <strong>Zoho Mail</strong>. Requires a registered domain.
        </div>
    </div>

    <!-- SECTION: Recommended Site Pages -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Recommended Website Pages</div>
                <div class="bae-card-desc">Based on your industry (<?php echo esc_html($industry); ?>), these are the pages your website needs.</div>
            </div>
            <a href="?tab=assets" class="bae-btn bae-btn-outline bae-btn-sm">Generate Full Sitemap →</a>
        </div>
        <div style="display:flex;flex-direction:column;gap:0;border:1px solid var(--border);border-radius:10px;overflow:hidden;">
            <?php foreach ($pages as $i => $page):
                $bg = $i % 2 === 0 ? '#fff' : '#fafafa';
                $priority_bg = $page['priority'] === 'high' ? '#d1fae5' : ($page['priority'] === 'medium' ? '#fef3c7' : '#f3f4f6');
                $priority_txt = $page['priority'] === 'high' ? '#065f46' : ($page['priority'] === 'medium' ? '#92400e' : '#6b7280');
            ?>
            <div style="display:grid;grid-template-columns:36px 130px 60px 1fr;gap:0;background:<?php echo $bg; ?>;border-bottom:1px solid #f3f4f6;align-items:center;">
                <div style="padding:12px;display:flex;align-items:center;justify-content:center;color:var(--text-2);"><?php echo $page['icon']; ?></div>
                <div style="padding:10px 8px;font-size:13px;font-weight:600;color:var(--text);"><?php echo esc_html($page['name']); ?></div>
                <div style="padding:10px 8px;">
                    <span style="font-size:10px;padding:2px 8px;border-radius:999px;background:<?php echo $priority_bg; ?>;color:<?php echo $priority_txt; ?>;font-weight:700;"><?php echo ucfirst($page['priority']); ?></span>
                </div>
                <div style="padding:10px 16px;font-size:12px;color:var(--text-3);line-height:1.6;"><?php echo esc_html($page['desc']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SECTION: Launch Checklist -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Launch Checklist for <?php echo esc_html($biz_raw); ?></div>
                <div class="bae-card-desc">Complete these steps to officially launch your business online and legally in the Philippines.</div>
            </div>
            <button type="button" id="bae-toolkit-reset" class="bae-btn bae-btn-outline bae-btn-sm" style="white-space:nowrap;">Reset checklist</button>
        </div>
        <?php foreach ($checklist as $section => $tasks): ?>
        <div style="margin-bottom:24px;">
            <div class="bae-section-label" style="margin-bottom:10px;"><?php echo $section; ?></div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <?php foreach ($tasks as $task): ?>
                <div class="bae-checklist-item" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:12px 14px;border:1px solid var(--border);border-radius:8px;background:var(--surface);transition:background .2s;">
                    <div style="display:flex;align-items:flex-start;gap:10px;flex:1;">
                        <?php
                        $key = 'bae_chk_' . sanitize_title($task['task']);
                        $is_checked = !empty($saved_state[$key]) && (int)$saved_state[$key] === 1;
                        ?>
                        <input type="checkbox"
                               class="bae-checklist-cb"
                               data-key="<?php echo esc_attr($key); ?>"
                               <?php echo $is_checked ? 'checked' : ''; ?>
                               style="margin-top:3px;width:16px;height:16px;cursor:pointer;flex-shrink:0;accent-color:var(--brand-soft);">
                        <div>
                            <div class="bae-checklist-label" style="font-size:13px;color:var(--text-2);font-weight:500;"><?php echo esc_html($task['task']); ?></div>
                            <div style="font-size:11px;color:var(--text-3);margin-top:3px;"><?php echo esc_html($task['tools']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    (function() {
        var nonce = <?php echo json_encode($toolkit_nonce); ?>;
        var profileId = <?php echo json_encode((int)($p['id'] ?? 0)); ?>;
        var resetBtn = document.getElementById('bae-toolkit-reset');

        function applyChecklistUI(cb) {
            var item = cb.closest('.bae-checklist-item');
            var label = item ? item.querySelector('.bae-checklist-label') : null;
            if (item)  item.style.background       = cb.checked ? 'var(--bg-3)' : 'var(--surface)';
            if (label) label.style.textDecoration  = cb.checked ? 'line-through' : 'none';
            if (label) label.style.opacity         = cb.checked ? '0.5' : '1';
        }

        function saveChecklistKey(key, checked) {
            var fd = new FormData();
            fd.append('action', 'bae_toolkit_checklist_save');
            fd.append('nonce', nonce);
            fd.append('profile_id', profileId);
            fd.append('key', key);
            fd.append('checked', checked ? '1' : '0');
            return fetch(ajaxurl, { method:'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(j){ return j && j.success; })
                .catch(function(){ return false; });
        }

        function resetChecklist() {
            var fd = new FormData();
            fd.append('action', 'bae_toolkit_checklist_save');
            fd.append('nonce', nonce);
            fd.append('profile_id', profileId);
            fd.append('reset', '1');
            return fetch(ajaxurl, { method:'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(j){ return j && j.success; })
                .catch(function(){ return false; });
        }

        var cbs = document.querySelectorAll('.bae-checklist-cb');
        cbs.forEach(function(cb) {
            var key = cb.dataset.key;
            if (!key) return;

            // Initial UI (checkbox may already be checked from server)
            applyChecklistUI(cb);

            // Save on change (diary)
            cb.addEventListener('change', function() {
                applyChecklistUI(cb);
                saveChecklistKey(key, cb.checked);
            });
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function(e){
                e.preventDefault();
                resetBtn.disabled = true;
                var original = resetBtn.textContent;
                resetBtn.textContent = 'Resetting...';
                resetChecklist().then(function(ok){
                    // Clear UI regardless (server is source of truth; refresh later if needed)
                    cbs.forEach(function(cb){ cb.checked = false; applyChecklistUI(cb); });
                    resetBtn.disabled = false;
                    resetBtn.textContent = original;
                    if (!ok) alert('Reset failed. Please try again.');
                });
            });
        }
    })();
    </script>

    <?php
    return $claim_html . ob_get_clean();
}

function bae_render_tone_pills($tone_tags, $pc) {
    $out = '';
    foreach ($tone_tags as $t) {
        $out .= "<span style='padding:8px 18px;background:{$pc};color:#fff;border-radius:999px;font-size:13px;font-weight:600;'>" . esc_html($t) . "</span>";
    }
    return $out;
}

function bae_render_logo_rules($pc, $fh) {
    $rules = [
        'Always use official brand colors — no substitutions or approximations.',
        'Use the heading font (' . $fh . ') for all titles and brand copy.',
        'Maintain clear space around the logo equal to the cap-height of the logotype.',
        'Do not stretch, rotate, skew, or add effects to the logo.',
        'Ensure minimum contrast ratio of 4.5:1 when placing logo on any background.',
        'Do not place the logo on busy photographic backgrounds without a clear backdrop.',
    ];
    $out = '';
    foreach ($rules as $i => $rule) {
        $num = $i + 1;
        $out .= "<div style='display:flex;gap:14px;padding:14px;border:1px solid #e5e7eb;border-radius:10px;'>"
              . "<span style='width:28px;height:28px;background:{$pc};color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;'>{$num}</span>"
              . "<div style='font-size:13px;color:#374151;line-height:1.6;padding-top:4px;'>" . esc_html($rule) . "</div>"
              . "</div>";
    }
    return $out;
}

function bae_render_sitemap_connectors($pages) {
    $out = '';
    foreach ($pages as $page) {
        $out .= "<div style='width:2px;height:20px;background:#d1d5db;margin:0 auto;'></div>";
    }
    return $out;
}

function bae_render_sitemap_cards($pages, $pc, $sc, $ac, $domain_sm) {
    $out = '';
    foreach ($pages as $page) {
        $color     = $page['priority'] === 'high' ? $pc : ($page['priority'] === 'medium' ? $sc : '#6b7280');
        $badge_bg  = $page['priority'] === 'high' ? '#d1fae5' : ($page['priority'] === 'medium' ? '#fef3c7' : '#f3f4f6');
        $badge_txt = $page['priority'] === 'high' ? '#065f46' : ($page['priority'] === 'medium' ? '#92400e' : '#6b7280');
        $prio_label = ucfirst($page['priority']);
        $out .= "<div style='display:flex;flex-direction:column;align-items:center;min-width:100px;max-width:130px;'>"
              . "<div style='border:2px solid {$color};border-radius:8px;padding:10px 12px;text-align:center;width:100%;background:#fff;'>"
              . "<div style='display:flex;align-items:center;justify-content:center;margin-bottom:6px;color:#6b7280;'>{$page['icon']}</div>"
              . "<div style='font-size:12px;font-weight:600;color:#111827;'>" . esc_html($page['name']) . "</div>"
              . "<div style='font-size:10px;color:#9ca3af;margin-top:2px;font-family:monospace;'>{$domain_sm}" . esc_html($page['slug']) . "</div>"
              . "</div>"
              . "<span style='margin-top:6px;font-size:10px;padding:2px 8px;border-radius:999px;background:{$badge_bg};color:{$badge_txt};font-weight:600;'>{$prio_label}</span>"
              . "</div>";
    }
    return $out;
}

function bae_render_sitemap_table($pages) {
    $out = '';
    $i = 0;
    foreach ($pages as $page) {
        $bg = $i % 2 === 0 ? '#fff' : '#fafafa';
        $out .= "<div style='display:grid;grid-template-columns:32px 120px 1fr;gap:0;background:{$bg};border-bottom:1px solid #f3f4f6;'>"
              . "<div style='padding:12px;display:flex;align-items:center;justify-content:center;color:#6b7280;'>{$page['icon']}</div>"
              . "<div style='padding:12px 8px;font-size:13px;font-weight:600;color:#111827;border-right:1px solid #f3f4f6;'>" . esc_html($page['name']) . "</div>"
              . "<div style='padding:12px 16px;font-size:12px;color:#6b7280;line-height:1.6;'>" . esc_html($page['desc']) . "</div>"
              . "</div>";
        $i++;
    }
    return $out;
}

function bae_render_xml_sitemap($pages, $domain_sm) {
    $entries = [];
    foreach ($pages as $page) {
        $priority = $page['priority'] === 'high' ? '1.0' : ($page['priority'] === 'medium' ? '0.8' : '0.6');
        $entries[] = "&nbsp;&nbsp;<span style='color:#cba6f7;'>&lt;url&gt;</span><br>"
                   . "&nbsp;&nbsp;&nbsp;&nbsp;<span style='color:#f9e2af;'>&lt;loc&gt;</span>{$domain_sm}" . esc_html($page['slug']) . "<span style='color:#f9e2af;'>&lt;/loc&gt;</span><br>"
                   . "&nbsp;&nbsp;&nbsp;&nbsp;<span style='color:#f9e2af;'>&lt;priority&gt;</span>{$priority}<span style='color:#f9e2af;'>&lt;/priority&gt;</span><br>"
                   . "&nbsp;&nbsp;<span style='color:#cba6f7;'>&lt;/url&gt;</span>";
    }
    return implode('<br>', $entries);
}

// =============================================================================
// BRAND BOOK TAB — 50 templates, 12-page preview, save/print
// =============================================================================

if (!function_exists('bae_get_book_templates')) {
function bae_get_book_templates() {
    return array(
        // FREE (5)
        'modern_elegant'      => array('name'=>'Modern Elegant',      'cat'=>'Free',    'layout'=>'classic',      'cb'=>'#1e1b4b','ct'=>'#e0e7ff','acc'=>'#818cf8','pb'=>'#ffffff','pt'=>'#1e1b4b','pm'=>'#6b7280'),
        'fresh_basic'         => array('name'=>'Fresh Basic',         'cat'=>'Free',    'layout'=>'issey',        'cb'=>'#f9f6f6','ct'=>'#000000','acc'=>'#4a4a4a','pb'=>'#ffffff','pt'=>'#000000','pm'=>'#999999'),
        'pop_of_pink'         => array('name'=>'Pop of Pink',         'cat'=>'Free',    'layout'=>'editorial',    'cb'=>'#F32D86','ct'=>'#ffffff','acc'=>'#f9a8d4','pb'=>'#ffffff','pt'=>'#1f2937','pm'=>'#6b7280'),
        'effervescent'        => array('name'=>'Effervescent',        'cat'=>'Free',    'layout'=>'editorial',    'cb'=>'#0f172a','ct'=>'#f8fafc','acc'=>'#38bdf8','pb'=>'#f8fafc','pt'=>'#0f172a','pm'=>'#64748b'),
        'detailed_minimalist' => array('name'=>'Detailed Minimalist', 'cat'=>'Free',    'layout'=>'japanese_min', 'cb'=>'#fafafa','ct'=>'#111827','acc'=>'#111827','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#9ca3af'),
        // MODERN (20)
        'simply_chic'         => array('name'=>'Simply Chic',         'cat'=>'Modern',  'layout'=>'editorial',    'cb'=>'#0f766e','ct'=>'#ffffff','acc'=>'#5eead4','pb'=>'#ffffff','pt'=>'#134e4a','pm'=>'#6b7280'),
        'white_blue'          => array('name'=>'White & Blue',        'cat'=>'Modern',  'cb'=>'#1d4ed8','ct'=>'#ffffff','acc'=>'#93c5fd','pb'=>'#ffffff','pt'=>'#1e3a8a','pm'=>'#6b7280'),
        'white_apricot'       => array('name'=>'White & Apricot',     'cat'=>'Modern',  'cb'=>'#fb923c','ct'=>'#ffffff','acc'=>'#fed7aa','pb'=>'#fffbf5','pt'=>'#9a3412','pm'=>'#9ca3af'),
        'structured'          => array('name'=>'The Structured',      'cat'=>'Modern',  'cb'=>'#4f46e5','ct'=>'#ffffff','acc'=>'#6ee7b7','pb'=>'#ffffff','pt'=>'#312e81','pm'=>'#6b7280'),
        'green_elegance'      => array('name'=>'Green Elegance',      'cat'=>'Modern',  'cb'=>'#14532d','ct'=>'#f0fdf4','acc'=>'#86efac','pb'=>'#ffffff','pt'=>'#14532d','pm'=>'#6b7280'),
        'pastel_themed'       => array('name'=>'Pastel Themed',       'cat'=>'Modern',  'cb'=>'#fdf2f8','ct'=>'#831843','acc'=>'#f9a8d4','pb'=>'#fdf2f8','pt'=>'#831843','pm'=>'#be185d'),
        'black_green'         => array('name'=>'Black & Green',       'cat'=>'Modern',  'cb'=>'#111827','ct'=>'#f9fafb','acc'=>'#4ade80','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'bold_beautiful'      => array('name'=>'Bold & Beautiful',    'cat'=>'Modern',  'cb'=>'#000000','ct'=>'#ffffff','acc'=>'#22c55e','pb'=>'#ffffff','pt'=>'#000000','pm'=>'#6b7280'),
        'simply_red'          => array('name'=>'Simply Red',          'cat'=>'Modern',  'cb'=>'#dc2626','ct'=>'#ffffff','acc'=>'#fca5a5','pb'=>'#ffffff','pt'=>'#7f1d1d','pm'=>'#6b7280'),
        'red_black_white'     => array('name'=>'Red Black White',     'cat'=>'Modern',  'cb'=>'#000000','ct'=>'#ffffff','acc'=>'#ef4444','pb'=>'#ffffff','pt'=>'#000000','pm'=>'#6b7280'),
        'touch_yellow'        => array('name'=>'Touch of Yellow',     'cat'=>'Modern',  'cb'=>'#854d0e','ct'=>'#fefce8','acc'=>'#fbbf24','pb'=>'#fffbeb','pt'=>'#78350f','pm'=>'#92400e'),
        'green_minimalist'    => array('name'=>'Green Minimalist',    'cat'=>'Modern',  'layout'=>'japanese_min', 'cb'=>'#ecfdf5','ct'=>'#065f46','acc'=>'#059669','pb'=>'#ffffff','pt'=>'#064e3b','pm'=>'#6b7280'),
        'beige_boldness'      => array('name'=>'Beige Boldness',      'cat'=>'Modern',  'cb'=>'#d4a574','ct'=>'#ffffff','acc'=>'#92400e','pb'=>'#fdf8f0','pt'=>'#78350f','pm'=>'#a16207'),
        'gray_coral'          => array('name'=>'Gray & Coral',        'cat'=>'Modern',  'cb'=>'#374151','ct'=>'#f9fafb','acc'=>'#f87171','pb'=>'#f9fafb','pt'=>'#111827','pm'=>'#6b7280'),
        'earth_toned'         => array('name'=>'Earth Toned',         'cat'=>'Modern',  'cb'=>'#57301e','ct'=>'#fef9f5','acc'=>'#d4a574','pb'=>'#fef9f5','pt'=>'#3b1a0a','pm'=>'#92400e'),
        'elite_portfolio'     => array('name'=>'Elite Portfolio',     'cat'=>'Modern',  'layout'=>'editorial',    'cb'=>'#022c22','ct'=>'#ecfdf5','acc'=>'#6ee7b7','pb'=>'#ffffff','pt'=>'#022c22','pm'=>'#6b7280'),
        'black_yellow'        => array('name'=>'Black & Yellow',      'cat'=>'Modern',  'cb'=>'#111827','ct'=>'#fefce8','acc'=>'#facc15','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'bold_red'            => array('name'=>'Bold Red',            'cat'=>'Modern',  'cb'=>'#991b1b','ct'=>'#fff1f2','acc'=>'#fca5a5','pb'=>'#fff1f2','pt'=>'#7f1d1d','pm'=>'#6b7280'),
        'pale_peach'          => array('name'=>'Pale Peach',          'cat'=>'Modern',  'cb'=>'#fcd9bd','ct'=>'#7c3d2c','acc'=>'#ea580c','pb'=>'#fff7f0','pt'=>'#7c3d2c','pm'=>'#c2410c'),
        'blue_dominance'      => array('name'=>'Blue Dominance',      'cat'=>'Modern',  'cb'=>'#1e40af','ct'=>'#eff6ff','acc'=>'#60a5fa','pb'=>'#f0f7ff','pt'=>'#1e3a8a','pm'=>'#3b82f6'),
        // ONE PAGE (9)
        'light_dark'          => array('name'=>'Light & Dark',        'cat'=>'One Page','cb'=>'#18181b','ct'=>'#f4f4f5','acc'=>'#f76fb0','pb'=>'#18181b','pt'=>'#f4f4f5','pm'=>'#a1a1aa'),
        'minimal_muted'       => array('name'=>'Minimal Muted',       'cat'=>'One Page','layout'=>'japanese_min', 'cb'=>'#f1f5f9','ct'=>'#334155','acc'=>'#94a3b8','pb'=>'#f8fafc','pt'=>'#334155','pm'=>'#94a3b8'),
        'blue_red_brand'      => array('name'=>'Blue & Red Brand',    'cat'=>'One Page','cb'=>'#1e40af','ct'=>'#ffffff','acc'=>'#ef4444','pb'=>'#ffffff','pt'=>'#1e3a8a','pm'=>'#6b7280'),
        'tan_accent'          => array('name'=>'Tan Accent',          'cat'=>'One Page','layout'=>'japanese_min', 'cb'=>'#fafaf9','ct'=>'#292524','acc'=>'#d6b896','pb'=>'#fafaf9','pt'=>'#292524','pm'=>'#78716c'),
        'orange_accents'      => array('name'=>'Orange Accents',      'cat'=>'One Page','cb'=>'#fff7ed','ct'=>'#9a3412','acc'=>'#ea580c','pb'=>'#ffffff','pt'=>'#431407','pm'=>'#7c3d12'),
        'fair_square'         => array('name'=>'Fair & Square',       'cat'=>'One Page','cb'=>'#c4196a','ct'=>'#f5f3ff','acc'=>'#fbadd3','pb'=>'#f5f3ff','pt'=>'#8c0e4a','pm'=>'#c4196a'),
        'basic_corporate'     => array('name'=>'Basic Corporate',     'cat'=>'One Page','cb'=>'#1f2937','ct'=>'#f9fafb','acc'=>'#fbbf24','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'red_font'            => array('name'=>'Red Font',            'cat'=>'One Page','cb'=>'#ffffff','ct'=>'#111827','acc'=>'#dc2626','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'straightforward'     => array('name'=>'Straightforward',     'cat'=>'One Page','cb'=>'#ffffff','ct'=>'#111827','acc'=>'#f97316','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        // CREATIVE (9)
        'pink_bw'             => array('name'=>'Pink on BW',          'cat'=>'Creative','cb'=>'#000000','ct'=>'#ffffff','acc'=>'#f9a8d4','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'red_bw'              => array('name'=>'Red on BW',           'cat'=>'Creative','cb'=>'#111827','ct'=>'#f9fafb','acc'=>'#ef4444','pb'=>'#ffffff','pt'=>'#111827','pm'=>'#6b7280'),
        'simply_creative'     => array('name'=>'Simply Creative',     'cat'=>'Creative','cb'=>'#065f46','ct'=>'#ecfdf5','acc'=>'#f87171','pb'=>'#ffffff','pt'=>'#064e3b','pm'=>'#6b7280'),
        'classic_pro'         => array('name'=>'Classic Professional','cat'=>'Creative','layout'=>'editorial',    'cb'=>'#1a1a2e','ct'=>'#e0e7ff','acc'=>'#e94560','pb'=>'#ffffff','pt'=>'#1a1a2e','pm'=>'#6b7280'),
        'stylish_orange'      => array('name'=>'Stylish Orange',      'cat'=>'Creative','cb'=>'#c2410c','ct'=>'#fff7ed','acc'=>'#fb923c','pb'=>'#fff7ed','pt'=>'#7c2d12','pm'=>'#9a3412'),
        'black_pink'          => array('name'=>'Black & Pink',        'cat'=>'Creative','cb'=>'#18181b','ct'=>'#fdf4ff','acc'=>'#e879f9','pb'=>'#ffffff','pt'=>'#18181b','pm'=>'#71717a'),
        'vibrant_sunset'      => array('name'=>'Vibrant Sunset',      'cat'=>'Creative','cb'=>'#dc2626','ct'=>'#fff7ed','acc'=>'#fbbf24','pb'=>'#fff7ed','pt'=>'#78350f','pm'=>'#9a3412'),
        'neon_dark'           => array('name'=>'Neon Dark',           'cat'=>'Creative','cb'=>'#09090b','ct'=>'#f4f4f5','acc'=>'#a3e635','pb'=>'#09090b','pt'=>'#f4f4f5','pm'=>'#71717a'),
        'ocean_breeze'        => array('name'=>'Ocean Breeze',        'cat'=>'Creative','cb'=>'#0c4a6e','ct'=>'#f0f9ff','acc'=>'#38bdf8','pb'=>'#f0f9ff','pt'=>'#0c4a6e','pm'=>'#0284c7'),
        // MINIMAL (7)
        'swiss_grid'          => array('name'=>'Swiss Grid',          'cat'=>'Minimal', 'cb'=>'#ffffff','ct'=>'#000000','acc'=>'#ff0000','pb'=>'#ffffff','pt'=>'#000000','pm'=>'#737373'),
        'paper_white'         => array('name'=>'Paper White',         'cat'=>'Minimal', 'layout'=>'japanese_min', 'cb'=>'#fafaf9','ct'=>'#1c1917','acc'=>'#78716c','pb'=>'#fafaf9','pt'=>'#1c1917','pm'=>'#a8a29e'),
        'monochrome'          => array('name'=>'Monochrome',          'cat'=>'Minimal', 'cb'=>'#000000','ct'=>'#ffffff','acc'=>'#ffffff','pb'=>'#ffffff','pt'=>'#000000','pm'=>'#737373'),
        'sage_minimal'        => array('name'=>'Sage Minimal',        'cat'=>'Minimal', 'cb'=>'#86a390','ct'=>'#ffffff','acc'=>'#4a7c6b','pb'=>'#f9faf8','pt'=>'#2d3b35','pm'=>'#7a9187'),
        'slate_clean'         => array('name'=>'Slate Clean',         'cat'=>'Minimal', 'cb'=>'#475569','ct'=>'#f8fafc','acc'=>'#94a3b8','pb'=>'#f8fafc','pt'=>'#1e293b','pm'=>'#64748b'),
        'ink_minimal'         => array('name'=>'Ink Minimal',         'cat'=>'Minimal', 'cb'=>'#0f172a','ct'=>'#f8fafc','acc'=>'#e2e8f0','pb'=>'#ffffff','pt'=>'#0f172a','pm'=>'#64748b'),
        'lavender_soft'       => array('name'=>'Lavender Soft',       'cat'=>'Minimal', 'cb'=>'#c4196a','ct'=>'#faf5ff','acc'=>'#fbadd3','pb'=>'#faf5ff','pt'=>'#8c0e4a','pm'=>'#c4196a'),
    );
}
}

if (!function_exists('bae_render_book_pages')) {
function bae_render_book_pages($p, $tpl) {
    $name    = esc_html($p['business_name']  ?? 'Your Brand');
    $tagline = esc_html($p['tagline']        ?? '');
    $industry= esc_html($p['industry']       ?? '');
    $fh      = esc_attr($p['font_heading']   ?? 'Inter');
    $fb      = esc_attr($p['font_body']      ?? 'Inter');
    $pc      = bae_safe_color($p['primary_color']   ?? '', '#1a1a2e');
    $sc      = bae_safe_color($p['secondary_color'] ?? '', '#16213e');
    $ac      = bae_safe_color($p['accent_color']    ?? '', '#e94560');
    $email   = esc_html($p['email']   ?? '');
    $website = esc_html($p['website'] ?? '');
    $phone   = esc_html($p['phone']   ?? '');
    $ini     = bae_get_initials($name);

    $cb = $tpl['cb']; $ct = $tpl['ct']; $ta = $tpl['acc'];
    $pb = $tpl['pb']; $pt = $tpl['pt']; $pm = $tpl['pm'];

    $cs = 'background:'.$cb.';color:'.$ct.';font-family:\''.$fb.'\',sans-serif;';
    $ps = 'background:'.$pb.';color:'.$pt.';font-family:\''.$fb.'\',sans-serif;';
    $o  = '';

    $layout = $tpl['layout'] ?? 'classic';
    if ($layout === 'editorial') {
        return bae_bb_render_editorial(array(
            'name'=>$name,'tagline'=>$tagline,'industry'=>$industry,
            'personality'=>($p['personality'] ?? ''),
            'fh'=>$fh,'fb'=>$fb,'pc'=>$pc,'sc'=>$sc,'ac'=>$ac,
            'email'=>$email,'website'=>$website,'phone'=>$phone,'ini'=>$ini,
            'cb'=>$cb,'ct'=>$ct,'ta'=>$ta,'pb'=>$pb,'pt'=>$pt,'pm'=>$pm,
        ));
    }
    if ($layout === 'japanese_min') {
        return bae_bb_render_japanese_min(array(
            'name'=>$name,'tagline'=>$tagline,'industry'=>$industry,
            'personality'=>($p['personality'] ?? ''),
            'fh'=>$fh,'fb'=>$fb,'pc'=>$pc,'sc'=>$sc,'ac'=>$ac,
            'email'=>$email,'website'=>$website,'phone'=>$phone,'ini'=>$ini,
            'cb'=>$cb,'ct'=>$ct,'ta'=>$ta,'pb'=>$pb,'pt'=>$pt,'pm'=>$pm,
        ));
    }

    // P1 Cover
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$cs.'display:flex;flex-direction:column;justify-content:space-between;">';
    $o .= '<div style="font-size:9px;letter-spacing:.2em;text-transform:uppercase;opacity:.4;">Brand Guidelines</div>';
    $o .= '<div><div style="font-family:\''.$fh.'\',sans-serif;font-size:2.2em;font-weight:700;line-height:1.1;margin-bottom:8px;">'.$name.'</div>';
    $o .= '<div style="font-size:.8em;opacity:.65;">'.$tagline.'</div></div>';
    $o .= '<div style="font-size:.6em;opacity:.35;">'.$website.' &copy; '.date('Y').'</div>';
    $o .= '</div></div>';

    // P2 Brand Story
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">01 — Brand Story</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;border-bottom:1px solid rgba(0,0,0,.08);padding-bottom:10px;margin-bottom:12px;">'.$name.'</div>';
    $o .= '<div style="font-size:.72em;color:'.$pm.';margin-bottom:4px;"><strong>Industry:</strong> '.$industry.'</div>';
    if ($tagline) $o .= '<div style="margin-top:12px;padding:10px 12px;border-left:3px solid '.$ta.';background:rgba(0,0,0,.03);font-size:.8em;font-style:italic;">&ldquo;'.$tagline.'&rdquo;</div>';
    $o .= '</div></div>';

    // P3 Colors
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">02 — Colors</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:14px;">Brand Colors</div>';
    foreach (array(array('Primary',$pc),array('Secondary',$sc),array('Accent',$ac)) as $col) {
        $o .= '<div style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">';
        $o .= '<div style="width:44px;height:44px;border-radius:8px;background:'.$col[1].';flex-shrink:0;"></div>';
        $o .= '<div><div style="font-size:.75em;font-weight:600;">'.$col[0].'</div>';
        $o .= '<div style="font-size:.65em;color:'.$pm.';font-family:monospace;">'.strtoupper($col[1]).'</div></div></div>';
    }
    $o .= '</div></div>';

    // P4 Typography
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">03 — Typography</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:14px;">Type System</div>';
    $o .= '<div style="padding:10px;border:1px solid rgba(0,0,0,.08);border-radius:6px;margin-bottom:8px;">';
    $o .= '<div style="font-size:.6em;color:'.$pm.';text-transform:uppercase;margin-bottom:4px;">Heading</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.5em;font-weight:700;color:'.$pc.'">'.$fh.'</div></div>';
    $o .= '<div style="padding:10px;border:1px solid rgba(0,0,0,.08);border-radius:6px;">';
    $o .= '<div style="font-size:.6em;color:'.$pm.';text-transform:uppercase;margin-bottom:4px;">Body</div>';
    $o .= '<div style="font-family:\''.$fb.'\',sans-serif;font-size:1.5em;color:'.$pc.'">'.$fb.'</div></div>';
    $o .= '</div></div>';

    // P5 Tone
    $tone_tags = bae_derive_tone_tags($p['industry'] ?? '', $p['personality'] ?? '');
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">04 — Tone of Voice</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">How We Speak</div>';
    $o .= '<div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px;">';
    foreach ($tone_tags as $tag) $o .= '<span style="padding:3px 9px;background:'.$ta.';color:'.$ct.';border-radius:999px;font-size:.66em;font-weight:600;">'.esc_html($tag).'</span>';
    $o .= '</div>';
    $o .= '<div style="font-size:.68em;color:'.$pm.';line-height:1.6;">'.bae_get_voice_examples($tone_tags, true).'</div>';
    $o .= '</div></div>';

    // P6 Logo Rules
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">05 — Logo Usage</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Usage Rules</div>';
    foreach (array('Always use official brand colors.','Never stretch or rotate the logo.','Maintain clear space equal to logo height.','Ensure 4.5:1 contrast ratio minimum.','Never place on busy backgrounds.') as $i => $rule) {
        $o .= '<div style="display:flex;gap:8px;margin-bottom:8px;">';
        $o .= '<span style="width:18px;height:18px;background:'.$ta.';color:'.$ct.';border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6em;font-weight:700;flex-shrink:0;">'.($i+1).'</span>';
        $o .= '<div style="font-size:.7em;color:'.$pt.';line-height:1.5;">'.$rule.'</div></div>';
    }
    $o .= '</div></div>';

    // P7 Stationery
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">06 — Stationery</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Applications</div>';
    $o .= '<div style="font-size:.6em;color:'.$pm.';text-transform:uppercase;font-weight:700;margin-bottom:6px;">Business Card</div>';
    $o .= '<div style="display:flex;gap:8px;margin-bottom:12px;">';
    $o .= '<div style="width:110px;height:62px;background:'.$pc.';border-radius:5px;padding:8px;display:flex;flex-direction:column;justify-content:space-between;">';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:.65em;font-weight:700;color:#fff;">'.$name.'</div>';
    $o .= '<div style="width:14px;height:2px;background:'.$ac.';"></div></div>';
    $o .= '<div style="width:110px;height:62px;background:#fff;border:1px solid #e5e7eb;border-radius:5px;padding:8px;">';
    $o .= '<div style="font-size:.6em;font-weight:700;color:'.$pc.';margin-bottom:3px;">'.$name.'</div>';
    $o .= '<div style="font-size:.55em;color:#6b7280;">'.$email.'</div>';
    $o .= '<div style="font-size:.55em;color:#6b7280;">'.$phone.'</div></div></div>';
    $o .= '<div style="font-size:.6em;color:'.$pm.';text-transform:uppercase;font-weight:700;margin-bottom:6px;">Email Signature</div>';
    $o .= '<div style="padding:7px;background:#fff;border:1px solid #e5e7eb;border-radius:5px;display:flex;align-items:center;gap:7px;">';
    $o .= '<div style="width:26px;height:26px;border-radius:6px;background:'.$pc.';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    $o .= '<span style="font-size:.65em;font-weight:700;color:#fff;">'.$ini.'</span></div>';
    $o .= '<div><div style="font-size:.65em;font-weight:700;color:'.$pc.'">[Name] &middot; '.$name.'</div>';
    $o .= '<div style="font-size:.58em;color:#6b7280;">'.$email.'</div></div></div>';
    $o .= '</div></div>';

    // P8 Social
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">07 — Social Media</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Social Presence</div>';
    $o .= '<div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;">';
    $o .= '<div style="width:48px;height:48px;border-radius:50%;background:'.$pc.';border:3px solid '.$ac.';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    $o .= '<span style="font-family:\''.$fh.'\',sans-serif;font-size:.9em;font-weight:700;color:#fff;">'.$ini.'</span></div>';
    $o .= '<div><div style="font-size:.75em;font-weight:700;">Profile Photo</div>';
    $o .= '<div style="font-size:.62em;color:'.$pm.';">200x200px &middot; Initials on primary color</div></div></div>';
    $o .= '<div style="width:100%;height:55px;background:'.$pc.';border-radius:5px;padding:8px;display:flex;flex-direction:column;justify-content:flex-end;margin-bottom:6px;">';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:.75em;font-weight:700;color:#fff;">'.$name.'</div>';
    $o .= '<div style="font-size:.58em;color:rgba(255,255,255,.6);">'.$tagline.'</div></div>';
    $o .= '<div style="font-size:.62em;color:'.$pm.';">Cover 820x312px &middot; Primary color background</div>';
    $o .= '</div></div>';

    // P9 Visual Style
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">08 — Visual Style</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Design Principles</div>';
    foreach (array(array('Consistency','Every touchpoint must feel unmistakably '.$name.'.'),array('Clarity','Information hierarchy guides the eye naturally.'),array('Authenticity','Design decisions reflect your brand values.'),array('Proportion','Use whitespace generously for maximum impact.')) as $pr) {
        $o .= '<div style="display:flex;gap:8px;margin-bottom:10px;">';
        $o .= '<div style="width:7px;height:7px;border-radius:50%;background:'.$ta.';flex-shrink:0;margin-top:4px;"></div>';
        $o .= '<div><div style="font-size:.74em;font-weight:700;margin-bottom:2px;">'.$pr[0].'</div>';
        $o .= '<div style="font-size:.66em;color:'.$pm.';line-height:1.5;">'.$pr[1].'</div></div></div>';
    }
    $o .= '</div></div>';

    // P10 Brand Rules
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">09 — Brand Rules</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Do\'s & Don\'ts</div>';
    $o .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">';
    $o .= '<div><div style="font-size:.6em;font-weight:700;color:#065f46;text-transform:uppercase;margin-bottom:5px;">Do</div>';
    foreach (array('Use official brand colors.','Use heading font for titles.','Maintain logo clear space.','Ensure text contrast.') as $d)
        $o .= '<div style="font-size:.66em;color:'.$pt.';padding:3px 0;border-bottom:1px solid rgba(0,0,0,.04);">'.$d.'</div>';
    $o .= '</div><div><div style="font-size:.6em;font-weight:700;color:#991b1b;text-transform:uppercase;margin-bottom:5px;">Don\'t</div>';
    foreach (array('Stretch or rotate the logo.','Use non-approved fonts.','Place on clashing backgrounds.','Use low-res logo files.') as $d)
        $o .= '<div style="font-size:.66em;color:'.$pt.';padding:3px 0;border-bottom:1px solid rgba(0,0,0,.04);">'.$d.'</div>';
    $o .= '</div></div>';
    $o .= '</div></div>';

    // P11 Contact
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$ps.'">';
    $o .= '<div style="font-size:.62em;color:'.$ta.';letter-spacing:.14em;text-transform:uppercase;margin-bottom:6px;font-weight:700;">10 — Contact</div>';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.4em;font-weight:700;margin-bottom:12px;">Get in Touch</div>';
    if ($email)   $o .= '<div style="font-size:.75em;margin-bottom:5px;">'.$email.'</div>';
    if ($phone)   $o .= '<div style="font-size:.75em;margin-bottom:5px;">'.$phone.'</div>';
    if ($website) $o .= '<div style="font-size:.75em;font-weight:700;color:'.$ta.';margin-bottom:5px;">'.$website.'</div>';
    $o .= '</div></div>';

    // P12 Back Cover
    $o .= '<div class="bae-bp"><div class="bae-bpi" style="'.$cs.'display:flex;flex-direction:column;justify-content:space-between;">';
    $o .= '<div style="font-size:.62em;opacity:.4;text-transform:uppercase;letter-spacing:.15em;">Thank You</div>';
    $o .= '<div style="text-align:center;">';
    $o .= '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.5em;font-weight:700;margin-bottom:6px;">'.$name.'</div>';
    $o .= '<div style="font-size:.75em;opacity:.65;margin-bottom:14px;">'.$tagline.'</div>';
    if ($website) $o .= '<div style="font-size:.72em;font-weight:700;opacity:.85;">'.$website.'</div>';
    $o .= '</div>';
    $o .= '<div style="font-size:.58em;opacity:.3;">&copy; '.date('Y').' '.$name.' &middot; Mothie</div>';
    $o .= '</div></div>';

    return $o;
}
}

if (!function_exists('bae_bb_page')) {
function bae_bb_page($inner, $style) {
    return '<div class="bae-bp"><div class="bae-bpi" style="'.$style.'">'.$inner.'</div></div>';
}
}

if (!function_exists('bae_bb_badge_chip')) {
function bae_bb_badge_chip($text, $bg, $fg) {
    return '<span style="display:inline-flex;align-items:center;justify-content:center;padding:4px 10px;border-radius:999px;background:'.$bg.';color:'.$fg.';font-size:.62em;font-weight:800;letter-spacing:.14em;text-transform:uppercase;">'.esc_html($text).'</span>';
}
}

if (!function_exists('bae_bb_render_editorial')) {
function bae_bb_render_editorial($c) {
    $name=$c['name']; $tagline=$c['tagline']; $industry=$c['industry'];
    $personality=$c['personality'] ?? '';
    $fh=$c['fh']; $fb=$c['fb'];
    $pc=$c['pc']; $sc=$c['sc']; $ac=$c['ac'];
    $email=$c['email']; $website=$c['website']; $phone=$c['phone']; $ini=$c['ini'];
    $cb=$c['cb']; $ct=$c['ct']; $ta=$c['ta'];
    $pb=$c['pb']; $pt=$c['pt']; $pm=$c['pm'];

    $cs = 'background:'.$cb.';color:'.$ct.';font-family:\''.$fb.'\',sans-serif;';
    $ps = 'background:'.$pb.';color:'.$pt.';font-family:\''.$fb.'\',sans-serif;';
    $o = '';

    // P1 Cover
    $inner = '<div style="height:100%;display:grid;grid-template-rows:auto 1fr auto;gap:14px;">'
        . '<div style="display:flex;justify-content:space-between;align-items:center;">'
        . bae_bb_badge_chip('Brand Book', 'rgba(255,255,255,.14)', $ct)
        . '<div style="font-size:.62em;opacity:.55;letter-spacing:.18em;font-weight:900;text-transform:uppercase;">Edition '.date('Y').'</div>'
        . '</div>'
        . '<div style="display:grid;grid-template-columns:1.15fr .85fr;gap:16px;align-items:end;">'
        . '<div>'
        . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:2.65em;font-weight:900;line-height:1.0;margin-bottom:10px;">'.$name.'</div>'
        . ($tagline ? '<div style="font-size:.86em;opacity:.78;max-width:95%;line-height:1.55;">'.$tagline.'</div>' : '')
        . '</div>'
        . '<div style="display:flex;flex-direction:column;gap:8px;align-items:flex-start;">'
        . '<div style="width:100%;height:130px;border-radius:16px;background:linear-gradient(135deg, rgba(255,255,255,.18), rgba(255,255,255,.04));border:1px solid rgba(255,255,255,.12);"></div>'
        . '<div style="width:100%;height:34px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.10);display:flex;align-items:center;padding:0 12px;box-sizing:border-box;">'
        . '<div style="font-size:.62em;opacity:.78;letter-spacing:.14em;font-weight:900;text-transform:uppercase;">'.$industry.'</div>'
        . '</div>'
        . '</div>'
        . '</div>'
        . '<div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;">'
        . '<div style="font-size:.62em;opacity:.5;">'.$website.'</div>'
        . '<div style="display:flex;gap:6px;">'
        . '<span style="width:10px;height:10px;border-radius:3px;background:'.$ta.'"></span>'
        . '<span style="width:10px;height:10px;border-radius:3px;background:rgba(255,255,255,.18)"></span>'
        . '<span style="width:10px;height:10px;border-radius:3px;background:rgba(255,255,255,.10)"></span>'
        . '</div>'
        . '</div>'
        . '</div>';
    $o .= bae_bb_page($inner, $cs);

    // Section title helper
    $sec = function($num, $title) use ($ta, $pm, $fh) {
        return '<div style="display:flex;gap:10px;align-items:flex-end;margin-bottom:10px;">'
            . '<div style="font-size:2.1em;font-weight:900;line-height:1;color:'.$ta.';">'.esc_html($num).'</div>'
            . '<div>'
            . '<div style="font-size:.62em;letter-spacing:.18em;text-transform:uppercase;font-weight:900;color:'.$pm.';">Section</div>'
            . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.25em;font-weight:900;line-height:1.1;">'.esc_html($title).'</div>'
            . '</div></div>';
    };

    // P2 Brand Story
    $inner = $sec('01','Brand Story')
        . '<div style="display:grid;grid-template-columns:1.1fr .9fr;gap:16px;">'
        . '<div style="font-size:.72em;color:'.$pm.';line-height:1.75">'
        . '<div style="font-weight:900;color:'.$pt.';margin-bottom:8px;">'.$name.'</div>'
        . ($tagline ? '<div style="padding:10px 12px;border-radius:14px;background:rgba(0,0,0,.03);border:1px solid rgba(0,0,0,.06);margin-bottom:10px;"><span style="font-style:italic;">&ldquo;'.$tagline.'&rdquo;</span></div>' : '')
        . '<div>Write your origin, mission, and what you believe. Keep it short, crisp, and repeatable.</div>'
        . '</div>'
        . '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.7);">'
        . '<div style="font-size:.62em;letter-spacing:.16em;text-transform:uppercase;color:'.$pm.';font-weight:900;margin-bottom:8px;">Quick facts</div>'
        . '<div style="display:grid;grid-template-columns:1fr;gap:8px;">'
        . '<div style="display:flex;justify-content:space-between;gap:10px;"><span style="font-weight:900;font-size:.7em;">Industry</span><span style="font-size:.7em;color:'.$pm.';text-align:right;">'.$industry.'</span></div>'
        . '<div style="display:flex;justify-content:space-between;gap:10px;"><span style="font-weight:900;font-size:.7em;">Primary</span><span style="font-family:monospace;font-size:.7em;color:'.$pm.'">'.esc_html($pc).'</span></div>'
        . '<div style="display:flex;justify-content:space-between;gap:10px;"><span style="font-weight:900;font-size:.7em;">Accent</span><span style="font-family:monospace;font-size:.7em;color:'.$pm.'">'.esc_html($ac).'</span></div>'
        . '</div></div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P3 Colors
    $row = function($label, $hex) use ($pm) {
        return '<div style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">'
            . '<div style="width:50px;height:50px;border-radius:12px;background:'.$hex.';flex-shrink:0;box-shadow:0 6px 18px rgba(0,0,0,.12);"></div>'
            . '<div style="min-width:0">'
            . '<div style="font-size:.74em;font-weight:900;letter-spacing:.02em;">'.esc_html($label).'</div>'
            . '<div style="font-family:monospace;font-size:.66em;color:'.$pm.'">'.esc_html(strtoupper($hex)).'</div>'
            . '</div></div>';
    };
    $inner = $sec('02','Colors')
        . $row('Primary', $pc)
        . $row('Secondary', $sc)
        . $row('Accent', $ac);
    $o .= bae_bb_page($inner, $ps);

    // P4 Typography
    $inner = $sec('03','Typography')
        . '<div style="display:grid;grid-template-columns:1fr;gap:10px;">'
        . '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);">'
        . '<div style="font-size:.62em;letter-spacing:.16em;text-transform:uppercase;color:'.$pm.';font-weight:900;margin-bottom:8px;">Heading</div>'
        . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:2.1em;font-weight:900;color:'.$pt.';line-height:1.05">The quick brown fox</div>'
        . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:.9em;font-weight:800;color:'.$ta.';margin-top:6px">'.$fh.'</div>'
        . '</div>'
        . '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);">'
        . '<div style="font-size:.62em;letter-spacing:.16em;text-transform:uppercase;color:'.$pm.';font-weight:900;margin-bottom:8px;">Body</div>'
        . '<div style="font-family:\''.$fb.'\',sans-serif;font-size:.82em;font-weight:600;color:'.$pm.';line-height:1.75">Use body type for clarity. Prioritize spacing and legibility across sizes.</div>'
        . '<div style="font-family:\''.$fb.'\',sans-serif;font-size:.9em;font-weight:800;color:'.$ta.';margin-top:6px">'.$fb.'</div>'
        . '</div></div>';
    $o .= bae_bb_page($inner, $ps);

    // P5 Tone
    $tone_tags = bae_derive_tone_tags($industry, $personality);
    if (!is_array($tone_tags) || empty($tone_tags)) $tone_tags = array('Clear','Confident','Warm');
    $chips = '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">';
    foreach ($tone_tags as $tag) $chips .= bae_bb_badge_chip($tag, 'rgba(0,0,0,.05)', $pt);
    $chips .= '</div>';
    $inner = $sec('04','Tone of Voice')
        . $chips
        . '<div style="font-size:.72em;color:'.$pm.';line-height:1.8">'.bae_get_voice_examples($tone_tags, true).'</div>';
    $o .= bae_bb_page($inner, $ps);

    // P6 Logo Usage
    $rules = array('Use official colors or approved monochrome.','Keep clear space around the mark.','Do not distort, rotate, or add effects.','Avoid busy backgrounds or low contrast.');
    $grid = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">';
    foreach ($rules as $i => $rule) {
        $grid .= '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);">'
            . '<div style="font-size:.62em;letter-spacing:.16em;text-transform:uppercase;color:'.$pm.';font-weight:900;margin-bottom:6px;">Rule '.($i+1).'</div>'
            . '<div style="font-size:.74em;font-weight:900;line-height:1.45;">'.$rule.'</div>'
            . '</div>';
    }
    $grid .= '</div>';
    $inner = $sec('05','Logo Usage') . $grid;
    $o .= bae_bb_page($inner, $ps);

    // P7 Stationery
    $inner = $sec('06','Applications')
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">'
        . '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);">'
        . '<div style="font-size:.62em;letter-spacing:.16em;text-transform:uppercase;color:'.$pm.';font-weight:900;margin-bottom:8px;">Business card</div>'
        . '<div style="height:70px;border-radius:14px;background:'.$pc.';padding:10px;display:flex;flex-direction:column;justify-content:space-between;box-sizing:border-box;box-shadow:0 10px 28px rgba(0,0,0,.18);">'
        . '<div style="font-family:\''.$fh.'\',sans-serif;font-weight:900;color:#fff;font-size:.72em;">'.$name.'</div>'
        . '<div style="width:24px;height:4px;border-radius:99px;background:'.$ac.'"></div>'
        . '</div>'
        . '</div>'
        . '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);">'
        . '<div style="font-size:.62em;letter-spacing:.16em;text-transform:uppercase;color:'.$pm.';font-weight:900;margin-bottom:8px;">Email signature</div>'
        . '<div style="display:flex;gap:10px;align-items:center;">'
        . '<div style="width:38px;height:38px;border-radius:14px;background:'.$pc.';display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;">'.esc_html($ini).'</div>'
        . '<div style="min-width:0">'
        . '<div style="font-size:.7em;font-weight:900;color:'.$pt.'">[Name] &middot; '.$name.'</div>'
        . '<div style="font-size:.62em;color:'.$pm.'">'.esc_html($email).'</div>'
        . '</div></div>'
        . '</div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P8 Social
    $inner = $sec('07','Social')
        . '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);display:flex;gap:10px;align-items:center;">'
        . '<div style="width:56px;height:56px;border-radius:50%;background:'.$pc.';border:3px solid '.$ac.';display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-family:\''.$fh.'\',sans-serif;">'.esc_html($ini).'</div>'
        . '<div><div style="font-size:.72em;font-weight:900;">Avatar</div><div style="font-size:.62em;color:'.$pm.'">Initials, high contrast, consistent stroke</div></div>'
        . '</div>'
        . '<div style="margin-top:10px;padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);">'
        . '<div style="font-size:.62em;letter-spacing:.16em;text-transform:uppercase;color:'.$pm.';font-weight:900;margin-bottom:8px;">Cover</div>'
        . '<div style="height:70px;border-radius:16px;background:'.$pc.';padding:10px;display:flex;flex-direction:column;justify-content:flex-end;box-sizing:border-box;">'
        . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:.86em;font-weight:900;color:#fff;">'.$name.'</div>'
        . '<div style="font-size:.62em;color:rgba(255,255,255,.72)">'.$tagline.'</div>'
        . '</div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P9 Visual Style
    $inner = $sec('08','Visual Style')
        . '<div style="font-size:.72em;color:'.$pm.';line-height:1.8;margin-bottom:12px">Choose a limited set of design moves and repeat them: spacing scale, radius, and an editorial hierarchy.</div>'
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">'
        . '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);"><div style="font-weight:900;margin-bottom:6px;">Hierarchy</div><div style="font-size:.66em;color:'.$pm.';line-height:1.6">One hero element per page.</div></div>'
        . '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);"><div style="font-weight:900;margin-bottom:6px;">Whitespace</div><div style="font-size:.66em;color:'.$pm.';line-height:1.6">Let content breathe.</div></div>'
        . '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);"><div style="font-weight:900;margin-bottom:6px;">Contrast</div><div style="font-size:.66em;color:'.$pm.';line-height:1.6">Legibility wins.</div></div>'
        . '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);"><div style="font-weight:900;margin-bottom:6px;">Consistency</div><div style="font-size:.66em;color:'.$pm.';line-height:1.6">Repeat your moves.</div></div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P10 Rules
    $inner = $sec('09','Do & Don\'t')
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">'
        . '<div style="padding:12px;border-radius:18px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.16)"><div style="font-size:.62em;letter-spacing:.16em;text-transform:uppercase;font-weight:900;color:#065f46;margin-bottom:8px;">Do</div>'
        . '<div style="font-size:.7em;font-weight:800;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.05)">Use approved colors</div>'
        . '<div style="font-size:.7em;font-weight:800;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.05)">Align to grid</div>'
        . '<div style="font-size:.7em;font-weight:800;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.05)">Keep contrast strong</div>'
        . '<div style="font-size:.7em;font-weight:800;padding:5px 0;">Repeat patterns</div></div>'
        . '<div style="padding:12px;border-radius:18px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.16)"><div style="font-size:.62em;letter-spacing:.16em;text-transform:uppercase;font-weight:900;color:#991b1b;margin-bottom:8px;">Don\'t</div>'
        . '<div style="font-size:.7em;font-weight:800;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.05)">Stretch the logo</div>'
        . '<div style="font-size:.7em;font-weight:800;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.05)">Mix random fonts</div>'
        . '<div style="font-size:.7em;font-weight:800;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.05)">Overdecorate</div>'
        . '<div style="font-size:.7em;font-weight:800;padding:5px 0;">Use low-res assets</div></div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P11 Contact
    $inner = $sec('10','Contact')
        . '<div style="display:grid;grid-template-columns:1fr;gap:10px;">'
        . ($email ? '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);font-size:.8em;font-weight:900;">'.esc_html($email).'</div>' : '')
        . ($phone ? '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);font-size:.8em;font-weight:900;">'.esc_html($phone).'</div>' : '')
        . ($website ? '<div style="padding:12px;border-radius:18px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.75);font-size:.8em;font-weight:900;color:'.$ta.'">'.esc_html($website).'</div>' : '')
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P12 Back cover
    $inner = '<div style="height:100%;display:flex;flex-direction:column;justify-content:space-between;">'
        . '<div style="display:flex;justify-content:space-between;">'
        . bae_bb_badge_chip('End', 'rgba(255,255,255,.14)', $ct)
        . '<div style="font-size:.62em;opacity:.55;letter-spacing:.18em;font-weight:900;text-transform:uppercase;">'.$industry.'</div>'
        . '</div>'
        . '<div style="text-align:center;">'
        . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.9em;font-weight:900;margin-bottom:8px;">'.$name.'</div>'
        . ($tagline ? '<div style="opacity:.75;font-size:.78em;max-width:90%;margin:0 auto;line-height:1.6">'.$tagline.'</div>' : '')
        . '</div>'
        . '<div style="display:flex;justify-content:space-between;align-items:flex-end;">'
        . '<div style="font-size:.58em;opacity:.35;">&copy; '.date('Y').'</div>'
        . '<div style="display:flex;gap:6px;">'
        . '<span style="width:10px;height:10px;border-radius:3px;background:'.$ta.'"></span>'
        . '<span style="width:10px;height:10px;border-radius:3px;background:rgba(255,255,255,.18)"></span>'
        . '<span style="width:10px;height:10px;border-radius:3px;background:rgba(255,255,255,.10)"></span>'
        . '</div>'
        . '</div>'
        . '</div>';
    $o .= bae_bb_page($inner, $cs);

    return $o;
}
}

if (!function_exists('bae_bb_render_japanese_min')) {
function bae_bb_render_japanese_min($c) {
    $name=$c['name']; $tagline=$c['tagline']; $industry=$c['industry'];
    $personality=$c['personality'] ?? '';
    $fh=$c['fh']; $fb=$c['fb'];
    $pc=$c['pc']; $sc=$c['sc']; $ac=$c['ac'];
    $email=$c['email']; $website=$c['website']; $phone=$c['phone']; $ini=$c['ini'];
    $cb=$c['cb']; $ct=$c['ct']; $ta=$c['ta'];
    $pb=$c['pb']; $pt=$c['pt']; $pm=$c['pm'];

    $cs = 'background:'.$cb.';color:'.$ct.';font-family:\''.$fb.'\',sans-serif;';
    $ps = 'background:'.$pb.';color:'.$pt.';font-family:\''.$fb.'\',sans-serif;';
    $o = '';

    $sec = function($num, $title) use ($ta, $pm, $fh) {
        return '<div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:12px;">'
            . '<div style="font-size:.62em;letter-spacing:.22em;text-transform:uppercase;font-weight:900;color:'.$pm.';">'.esc_html($num).'</div>'
            . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.35em;font-weight:900;letter-spacing:.01em;">'.esc_html($title).'</div>'
            . '<div style="width:34px;height:1px;background:'.$ta.';opacity:.6"></div>'
            . '</div>';
    };

    // P1 Cover (quiet)
    $inner = '<div style="height:100%;display:grid;grid-template-rows:auto 1fr auto;">'
        . '<div style="display:flex;justify-content:space-between;align-items:center;">'
        . '<div style="font-size:.62em;letter-spacing:.22em;text-transform:uppercase;opacity:.55;font-weight:900;">Brand Book</div>'
        . '<div style="width:28px;height:2px;background:'.$ta.';opacity:.9;border-radius:99px"></div>'
        . '</div>'
        . '<div style="display:flex;flex-direction:column;justify-content:center;gap:14px;">'
        . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:2.35em;font-weight:900;line-height:1.05;">'.$name.'</div>'
        . ($tagline ? '<div style="font-size:.82em;opacity:.72;max-width:80%;line-height:1.7;">'.$tagline.'</div>' : '')
        . '<div style="display:flex;gap:8px;align-items:center;opacity:.9;">'
        . '<span style="width:8px;height:8px;border-radius:2px;background:'.$ta.'"></span>'
        . '<span style="width:8px;height:8px;border-radius:2px;background:rgba(255,255,255,.18)"></span>'
        . '<span style="width:8px;height:8px;border-radius:2px;background:rgba(255,255,255,.10)"></span>'
        . '</div>'
        . '</div>'
        . '<div style="display:flex;justify-content:space-between;align-items:flex-end;">'
        . '<div style="font-size:.58em;opacity:.45;letter-spacing:.12em;text-transform:uppercase;font-weight:900;">'.$industry.'</div>'
        . '<div style="font-size:.58em;opacity:.35;">'.$website.'</div>'
        . '</div>'
        . '</div>';
    $o .= bae_bb_page($inner, $cs);

    // P2 Brand Story
    $inner = $sec('01','Brand Story')
        . '<div style="font-size:.74em;color:'.$pm.';line-height:1.95;">'
        . '<div style="font-weight:900;color:'.$pt.';margin-bottom:10px;">'.$name.'</div>'
        . ($tagline ? '<div style="padding:10px 12px;border-radius:14px;border:1px solid rgba(0,0,0,.08);background:rgba(255,255,255,.7);margin-bottom:12px;"><span style="font-style:italic;">&ldquo;'.$tagline.'&rdquo;</span></div>' : '')
        . '<div style="display:flex;gap:10px;align-items:flex-start;margin-top:14px;">'
        . '<div style="width:8px;height:8px;border-radius:2px;background:'.$ta.';margin-top:6px;flex-shrink:0"></div>'
        . '<div>Keep the story calm, direct, and human. Minimal layout emphasizes meaning over decoration.</div>'
        . '</div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P3 Colors (quiet list)
    $inner = $sec('02','Colors');
    foreach (array(array('Primary',$pc),array('Secondary',$sc),array('Accent',$ac)) as $col) {
        $inner .= '<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(0,0,0,.06);">'
            . '<div style="display:flex;gap:10px;align-items:center;min-width:0;">'
            . '<div style="width:36px;height:36px;border-radius:10px;background:'.$col[1].';box-shadow:0 10px 24px rgba(0,0,0,.12);"></div>'
            . '<div style="font-weight:900;font-size:.74em;letter-spacing:.02em;">'.esc_html($col[0]).'</div>'
            . '</div>'
            . '<div style="font-family:monospace;font-size:.7em;color:'.$pm.';">'.esc_html(strtoupper($col[1])).'</div>'
            . '</div>';
    }
    $o .= bae_bb_page($inner, $ps);

    // P4 Typography (simple)
    $inner = $sec('03','Typography')
        . '<div style="padding:12px 0;border-bottom:1px solid rgba(0,0,0,.06);">'
        . '<div style="font-size:.62em;letter-spacing:.22em;text-transform:uppercase;font-weight:900;color:'.$pm.';margin-bottom:8px;">Heading</div>'
        . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:2.15em;font-weight:900;line-height:1.05;color:'.$pt.'">A calm hierarchy</div>'
        . '</div>'
        . '<div style="padding:12px 0;">'
        . '<div style="font-size:.62em;letter-spacing:.22em;text-transform:uppercase;font-weight:900;color:'.$pm.';margin-bottom:8px;">Body</div>'
        . '<div style="font-family:\''.$fb.'\',sans-serif;font-size:.82em;line-height:1.9;color:'.$pm.'">Use body type for clarity. Prioritize spacing, rhythm, and legibility.</div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P5 Tone
    $tone_tags = bae_derive_tone_tags($industry, $personality);
    if (!is_array($tone_tags) || empty($tone_tags)) $tone_tags = array('Calm','Clear','Honest');
    $chips = '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">';
    foreach ($tone_tags as $tag) $chips .= '<span style="padding:4px 10px;border-radius:999px;border:1px solid rgba(0,0,0,.10);background:rgba(255,255,255,.7);font-size:.66em;font-weight:900;letter-spacing:.02em;">'.esc_html($tag).'</span>';
    $chips .= '</div>';
    $inner = $sec('04','Tone') . $chips . '<div style="font-size:.74em;color:'.$pm.';line-height:1.95">'.bae_get_voice_examples($tone_tags, true).'</div>';
    $o .= bae_bb_page($inner, $ps);

    // P6 Logo Usage (quiet list)
    $inner = $sec('05','Logo Usage');
    foreach (array('Use official colors or monochrome.','Keep clear space around the logo.','Never distort or rotate.','Avoid low contrast backgrounds.') as $i => $rule) {
        $inner .= '<div style="display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid rgba(0,0,0,.06);">'
            . '<div style="font-family:monospace;font-size:.74em;color:'.$ta.';font-weight:900;min-width:18px;">'.($i+1).'</div>'
            . '<div style="font-size:.74em;color:'.$pm.';line-height:1.85;">'.$rule.'</div>'
            . '</div>';
    }
    $o .= bae_bb_page($inner, $ps);

    // P7 Applications
    $inner = $sec('06','Applications')
        . '<div style="display:grid;grid-template-columns:1fr;gap:12px;">'
        . '<div style="padding:12px;border:1px solid rgba(0,0,0,.08);border-radius:16px;background:rgba(255,255,255,.75);">'
        . '<div style="font-size:.62em;letter-spacing:.22em;text-transform:uppercase;font-weight:900;color:'.$pm.';margin-bottom:8px;">Business card</div>'
        . '<div style="height:64px;border-radius:14px;background:'.$pc.';padding:10px;display:flex;flex-direction:column;justify-content:space-between;box-shadow:0 10px 24px rgba(0,0,0,.14);">'
        . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:.72em;font-weight:900;color:#fff;">'.$name.'</div>'
        . '<div style="width:28px;height:2px;background:'.$ac.';border-radius:99px"></div>'
        . '</div></div>'
        . '<div style="padding:12px;border:1px solid rgba(0,0,0,.08);border-radius:16px;background:rgba(255,255,255,.75);">'
        . '<div style="font-size:.62em;letter-spacing:.22em;text-transform:uppercase;font-weight:900;color:'.$pm.';margin-bottom:8px;">Email</div>'
        . '<div style="font-size:.74em;font-weight:900;color:'.$pt.'">[Name] &middot; '.$name.'</div>'
        . '<div style="font-size:.7em;color:'.$pm.';margin-top:3px">'.esc_html($email).'</div>'
        . '</div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P8 Social
    $inner = $sec('07','Social')
        . '<div style="display:flex;gap:12px;align-items:center;padding:12px;border:1px solid rgba(0,0,0,.08);border-radius:16px;background:rgba(255,255,255,.75);">'
        . '<div style="width:56px;height:56px;border-radius:50%;background:'.$pc.';border:3px solid '.$ac.';display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-family:\''.$fh.'\',sans-serif;">'.esc_html($ini).'</div>'
        . '<div><div style="font-size:.72em;font-weight:900;">Avatar</div><div style="font-size:.68em;color:'.$pm.';line-height:1.7">Keep it simple. One mark, one background.</div></div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P9 Visual Style
    $inner = $sec('08','Visual Style')
        . '<div style="font-size:.74em;color:'.$pm.';line-height:2.0;">'
        . '<div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:12px;"><div style="width:8px;height:8px;border-radius:2px;background:'.$ta.';margin-top:7px;"></div><div>Use whitespace as a design element.</div></div>'
        . '<div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:12px;"><div style="width:8px;height:8px;border-radius:2px;background:'.$ta.';margin-top:7px;"></div><div>Prefer clean borders and soft shadows.</div></div>'
        . '<div style="display:flex;gap:10px;align-items:flex-start;"><div style="width:8px;height:8px;border-radius:2px;background:'.$ta.';margin-top:7px;"></div><div>Keep a tight, consistent spacing scale.</div></div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P10 Rules
    $inner = $sec('09','Rules')
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">'
        . '<div><div style="font-size:.62em;letter-spacing:.22em;text-transform:uppercase;font-weight:900;color:#065f46;margin-bottom:8px;">Do</div>'
        . '<div style="font-size:.72em;color:'.$pm.';padding:6px 0;border-bottom:1px solid rgba(0,0,0,.06)">Use approved colors</div>'
        . '<div style="font-size:.72em;color:'.$pm.';padding:6px 0;border-bottom:1px solid rgba(0,0,0,.06)">Align to grid</div>'
        . '<div style="font-size:.72em;color:'.$pm.';padding:6px 0;border-bottom:1px solid rgba(0,0,0,.06)">Be consistent</div>'
        . '<div style="font-size:.72em;color:'.$pm.';padding:6px 0;border-bottom:1px solid rgba(0,0,0,.06)">Maintain contrast</div></div>'
        . '<div><div style="font-size:.62em;letter-spacing:.22em;text-transform:uppercase;font-weight:900;color:#991b1b;margin-bottom:8px;">Don\'t</div>'
        . '<div style="font-size:.72em;color:'.$pm.';padding:6px 0;border-bottom:1px solid rgba(0,0,0,.06)">Stretch logo</div>'
        . '<div style="font-size:.72em;color:'.$pm.';padding:6px 0;border-bottom:1px solid rgba(0,0,0,.06)">Mix random fonts</div>'
        . '<div style="font-size:.72em;color:'.$pm.';padding:6px 0;border-bottom:1px solid rgba(0,0,0,.06)">Overdecorate</div>'
        . '<div style="font-size:.72em;color:'.$pm.';padding:6px 0;border-bottom:1px solid rgba(0,0,0,.06)">Use noisy patterns</div></div>'
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P11 Contact
    $inner = $sec('10','Contact')
        . '<div style="padding:12px;border:1px solid rgba(0,0,0,.08);border-radius:16px;background:rgba(255,255,255,.75);">'
        . ($email ? '<div style="font-size:.74em;font-weight:900;margin-bottom:6px;">'.esc_html($email).'</div>' : '')
        . ($phone ? '<div style="font-size:.74em;font-weight:900;margin-bottom:6px;">'.esc_html($phone).'</div>' : '')
        . ($website ? '<div style="font-size:.74em;font-weight:900;color:'.$ta.'">'.esc_html($website).'</div>' : '')
        . '</div>';
    $o .= bae_bb_page($inner, $ps);

    // P12 Back cover
    $inner = '<div style="height:100%;display:flex;flex-direction:column;justify-content:space-between;">'
        . '<div style="font-size:.62em;letter-spacing:.22em;text-transform:uppercase;font-weight:900;opacity:.55;">End</div>'
        . '<div style="text-align:center;">'
        . '<div style="font-family:\''.$fh.'\',sans-serif;font-size:1.7em;font-weight:900;margin-bottom:8px;">'.$name.'</div>'
        . ($tagline ? '<div style="font-size:.76em;opacity:.72;line-height:1.7">'.$tagline.'</div>' : '')
        . '</div>'
        . '<div style="display:flex;justify-content:space-between;align-items:flex-end;">'
        . '<div style="font-size:.58em;opacity:.35;">&copy; '.date('Y').'</div>'
        . '<div style="width:38px;height:1px;background:'.$ta.';opacity:.6"></div>'
        . '</div>'
        . '</div>';
    $o .= bae_bb_page($inner, $cs);

    return $o;
}
}
