<?php if (!defined('ABSPATH')) exit;
function bae_kit_tab($user_id, $profile) {
    if (empty($profile)) {
        ob_start(); ?>
        <div class="bae-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
            <strong>Complete your Brand Profile first.</strong>
            <p><a href="?tab=overview" class="bae-btn bae-btn-primary" style="margin-top:12px;display:inline-flex;">Go to Brand Profile &rarr;</a></p>
        </div>
        <?php
        return bae_wrap_tab_panel(ob_get_clean());
    }

    if (!bae_has_viewed_onboarding_asset($profile)) {
        ob_start(); ?>
        <div class="bae-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            <strong>Preview 1 generated asset first.</strong>
            <p style="color:var(--text-3);margin-bottom:16px;">Your Brand Kit unlocks after you preview one generated asset in Asset Generator.</p>
            <a href="?tab=assets" class="bae-btn bae-btn-primary" style="display:inline-flex;align-items:center;gap:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Go to Asset Generator
            </a>
        </div>
        <?php
        return bae_wrap_tab_panel(ob_get_clean());
    }

    $p         = $profile;
    $kit_slug  = !empty($p['kit_slug']) ? $p['kit_slug'] : sanitize_title($p['business_name']) . '-' . substr($p['rand_id'], 0, 6);
    $kit_url   = bae_get_kit_public_url($p);
    $kit_qr_url = bae_get_kit_qr_url($kit_url, 360);
    $is_pub    = $p['kit_visibility'] === 'public';
    $nonce     = wp_create_nonce('bae_save_kit_settings');
    $user_plan = bae_get_user_plan($user_id, $profile);
    $is_free   = $user_plan === 'free';
    $kit_views = number_format_i18n((int)($p['kit_views'] ?? 0));
    $kit_unique_views = number_format_i18n((int)($p['kit_unique_views'] ?? 0));

    ob_start();
    ?>
    <?php if ($is_free): ?>
    <!-- Free plan kit lock banner -->
    <div style="position:relative;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;padding:16px 20px;background:linear-gradient(135deg,rgba(195,25,106,.1),rgba(243,45,134,.06));border:1px solid rgba(243,45,134,.25);border-radius:14px;flex-wrap:wrap;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f76fb0" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
            <div style="flex:1;">
                <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px;">Public Brand Kit is a Starter+ feature</div>
                <div style="font-size:12px;color:var(--text-3);">Upgrade to share your brand kit with designers, vendors, and partners via a public link.</div>
            </div>
            <button class="bae-btn bae-btn-primary bae-btn-sm" onclick="baePricingOpen('Share your Brand Kit publicly', 'Give designers, vendors, and clients a single link to your brand — colors, fonts, logo, and usage rules.')" style="white-space:nowrap;">Upgrade Now ✦</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Shareable Brand Kit</div>
                <div class="bae-card-desc">A public page showing your logo, colors, fonts, and brand rules for designers, vendors, and partners.</div>
            </div>
            <span class="bae-badge <?php echo $is_pub ? 'bae-badge-green' : 'bae-badge-gray'; ?>">
                <?php echo $is_pub ? 'Public' : 'Private'; ?>
            </span>
        </div>

        <?php if ($is_pub && !$is_free): ?>
        <div style="background:#f0fdf4;border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div style="font-size:13px;color:#065f46;font-family:'Courier New',monospace;word-break:break-all;">
                <?php echo esc_url($kit_url); ?>
            </div>
            <button class="bae-btn bae-btn-sm bae-btn-outline" onclick="navigator.clipboard.writeText('<?php echo esc_js($kit_url); ?>');this.textContent='Copied!';setTimeout(function(){this.textContent='Copy Link';}.bind(this),2000);">
                Copy Link
            </button>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
            <div style="flex:1;min-width:180px;padding:14px 16px;border-radius:12px;background:var(--bg-3);border:1px solid var(--border);">
                <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.08em;">Total Views</div>
                <div style="font-size:26px;font-weight:800;color:var(--text);margin-top:6px;"><?php echo esc_html($kit_views); ?></div>
            </div>
            <div style="flex:1;min-width:180px;padding:14px 16px;border-radius:12px;background:var(--bg-3);border:1px solid var(--border);">
                <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.08em;">Unique Viewers</div>
                <div style="font-size:26px;font-weight:800;color:var(--text);margin-top:6px;"><?php echo esc_html($kit_unique_views); ?></div>
                <div style="font-size:11px;color:var(--text-3);margin-top:4px;">Approx. one unique browser per year, GitHub-style.</div>
            </div>
        </div>

        <?php if ($is_pub && !$is_free): ?>
        <div style="display:grid;grid-template-columns:minmax(220px,280px) 1fr;gap:18px;align-items:center;margin-bottom:20px;padding:18px;border-radius:14px;background:var(--bg-3);border:1px solid var(--border);">
            <div style="display:flex;justify-content:center;">
                <div style="background:#fff;padding:14px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.18);">
                    <img src="<?php echo esc_url($kit_qr_url); ?>" alt="QR code for Brand Kit" style="display:block;width:220px;height:220px;border-radius:10px;">
                </div>
            </div>
            <div>
                <div style="font-size:12px;font-weight:700;color:var(--brand-soft);text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px;">QR Code</div>
                <div style="font-size:22px;font-weight:800;color:var(--text);margin-bottom:8px;">Share your Brand Kit with one scan</div>
                <div style="font-size:13px;color:var(--text-3);line-height:1.7;margin-bottom:14px;">Use this QR on printed cards, proposals, booths, or packaging so people can open your public Brand Kit instantly.</div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a href="<?php echo esc_url($kit_qr_url); ?>" download="<?php echo esc_attr(sanitize_title($p['business_name'] ?: 'brand-kit')); ?>-kit-qr.svg" class="bae-btn bae-btn-primary bae-btn-sm">Download QR</a>
                    <button type="button" class="bae-btn bae-btn-outline bae-btn-sm" onclick="navigator.clipboard.writeText('<?php echo esc_js($kit_url); ?>');this.textContent='Link Copied!';setTimeout(function(){this.textContent='Copy Link';}.bind(this),2000);">Copy Link</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form id="bae-kit-form">
            <div class="bae-form-grid">
                <div class="bae-form-group">
                    <label>Kit Visibility <?php if ($is_free) echo '<span style="font-size:10px;font-weight:700;background:linear-gradient(135deg,#c4196a,#F32D86);color:white;padding:2px 7px;border-radius:5px;margin-left:6px;vertical-align:middle;">STARTER+</span>'; ?></label>
                    <?php if ($is_free): ?>
                    <select name="kit_visibility" disabled onclick="baePricingOpen()" style="opacity:.5;cursor:not-allowed;">
                        <option>Private — Upgrade to make public</option>
                    </select>
                    <small style="color:var(--brand-soft);cursor:pointer;" onclick="baePricingOpen('Make your Brand Kit public', 'Share your brand with the world on Starter plan.')">Upgrade to unlock public sharing →</small>
                    <?php else: ?>
                    <select name="kit_visibility">
                        <option value="private" <?php selected($p['kit_visibility'], 'private'); ?>>Private — Only you can see it</option>
                        <option value="public"  <?php selected($p['kit_visibility'], 'public'); ?>>Public — Anyone with the link</option>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="bae-form-group">
                    <label>Kit URL Slug</label>
                    <input type="text" name="kit_slug" value="<?php echo esc_attr($kit_slug); ?>" placeholder="my-brand-kit" <?php echo $is_free ? 'disabled style="opacity:.5;"' : ''; ?>>
                    <small>Unique identifier in the shareable URL.</small>
                </div>
            </div>
            <input type="hidden" name="action" value="bae_save_kit_settings">
            <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
            <?php if (!$is_free): ?>
            <button type="submit" class="bae-btn bae-btn-primary" style="margin-top:16px;">Save Kit Settings</button>
            <?php else: ?>
            <button type="button" class="bae-btn bae-btn-outline" style="margin-top:16px;" onclick="baePricingOpen()">Upgrade to Save Settings ✦</button>
            <?php endif; ?>
            <div id="bae-kit-msg"></div>
        </form>
    </div>

    <!-- Brand Kit Preview -->
    <div class="bae-card">
        <div class="bae-card-title">Kit Preview</div>
        <div class="bae-card-desc" style="margin-top:4px;margin-bottom:20px;">What partners and designers will see when they open your Brand Kit link.</div>
        <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;">
            <?php echo bae_render_kit_html($p); ?>
        </div>
    </div>

    <script>
    (function() {
        var form = document.getElementById('bae-kit-form');
        var msg  = document.getElementById('bae-kit-msg');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = 'Saving...';
                msg.innerHTML = '';
                var formData = new FormData(form);
                fetch(ajaxurl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    msg.innerHTML = '<div class="bae-notice bae-notice-' +
                        (json.success ? 'success' : 'error') + '">' +
                        json.data.message + '</div>';
                    btn.disabled = false;
                    btn.textContent = 'Save Kit Settings';
                    if (json.success) setTimeout(function() { location.reload(); }, 1200);
                });
            });
        }

        // Brand Tools are wired in the Overview tab script.
    })();
    </script>
    <?php
    return bae_wrap_tab_panel(ob_get_clean());
}

// =============================================================================
// TAB: SETTINGS
// =============================================================================

