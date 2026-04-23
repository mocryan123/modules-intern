<?php if (!defined('ABSPATH')) exit;
function bae_identity_tab($user_id, $profile) {
    if (empty($profile)) {
        ob_start();
        ?>
        <div class="bae-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
            </svg>
            <strong>No brand profile yet.</strong>
            <p>Complete your <a href="?tab=overview">Brand Profile</a> first to see your Identity Board.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    $p        = $profile;
    $initials = bae_get_initials($p['business_name']);
    $tone_tags = bae_derive_tone_tags($p['industry'], $p['personality'] ?? '');

    // CHANGED: All colors validated at render time
    $pc = bae_safe_color($p['primary_color'], '#1a1a2e');
    $sc = bae_safe_color($p['secondary_color'], '#16213e');
    $ac = bae_safe_color($p['accent_color'], '#e94560');

    ob_start();
    ?>
    <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($p['font_heading']); ?>:wght@400;700&family=<?php echo urlencode($p['font_body']); ?>&display=swap" rel="stylesheet">

    <!-- Logo Mockup -->
    <div class="bae-card">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Logo Concept</div>
                <div class="bae-card-desc">CSS-based logo mockup based on your style selection. Use this as a starting reference for your designer.</div>
            </div>
            <span class="bae-badge bae-badge-gray"><?php echo ucfirst($p['logo_style']); ?></span>
        </div>

        <!-- Light background -->
        <div class="bae-logo-mockup" style="background:#ffffff;">
            <?php echo bae_render_logo_lockup($p); ?>
        </div>

        <!-- Dark background version -->
        <div class="bae-logo-mockup" style="background:<?php echo $pc; ?>;margin-top:12px;">
            <?php echo bae_render_logo_lockup($p, ['dark' => true]); ?>
        </div>
    </div>

    <!-- Color Palette -->
    <div class="bae-card">
        <div class="bae-card-title">Color Palette</div>
        <div class="bae-card-desc" style="margin-top:4px;">Your defined brand colors with usage guidance.</div>
        <div class="bae-color-swatches">
            <?php
            $colors = [
                'Primary'   => $pc,
                'Secondary' => $sc,
                'Accent'    => $ac,
            ];
            foreach ($colors as $label => $hex):
            ?>
            <div class="bae-swatch" data-color="<?php echo esc_attr(strtoupper($hex)); ?>">
                <div class="bae-swatch-block" style="background:<?php echo esc_attr($hex); ?>;"></div>
                <div class="bae-swatch-label"><?php echo $label; ?></div>
                <div class="bae-swatch-hex"><?php echo strtoupper($hex); ?></div>
                <div class="bae-swatch-copy-wrap">
                    <button type="button" class="bae-swatch-copy-btn" onclick="baeToggleColorMenu(event, this)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        Copy
                    </button>
                    <div class="bae-swatch-copy-menu">
                        <button type="button" class="bae-swatch-copy-option" onclick="baeCopyColorFormat(event, this, 'hex')">HEX</button>
                        <button type="button" class="bae-swatch-copy-option" onclick="baeCopyColorFormat(event, this, 'rgb')">RGB</button>
                        <button type="button" class="bae-swatch-copy-option" onclick="baeCopyColorFormat(event, this, 'hsl')">HSL</button>
                        <button type="button" class="bae-swatch-copy-option" onclick="baeCopyColorFormat(event, this, 'cmyk')">CMYK</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:<?php echo esc_attr($pc); ?>;opacity:0.6;"></div>
                <div class="bae-swatch-label">Primary Tint</div>
                <div class="bae-swatch-hex">60%</div>
            </div>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:var(--surface);border:1px solid var(--border);"></div>
                <div class="bae-swatch-label">White</div>
                <div class="bae-swatch-hex">#FFFFFF</div>
            </div>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:var(--bg-3);"></div>
                <div class="bae-swatch-label">Off-White</div>
                <div class="bae-swatch-hex">#F9FAFB</div>
            </div>
            <div class="bae-swatch">
                <div class="bae-swatch-block" style="background:#111827;"></div>
                <div class="bae-swatch-label">Near Black</div>
                <div class="bae-swatch-hex">#111827</div>
            </div>
        </div>
    </div>

    <!-- Typography -->
    <div class="bae-card">
        <div class="bae-card-title">Typography Pairing</div>
        <div class="bae-card-desc" style="margin-top:4px;"><?php echo esc_html($p['font_heading']); ?> + <?php echo esc_html($p['font_body']); ?></div>
        <div class="bae-font-sample">
            <div class="bae-font-heading-sample" style="font-family:'<?php echo esc_attr($p['font_heading']); ?>',sans-serif;color:<?php echo $pc; ?>;">
                <?php echo esc_html($p['business_name']); ?>
            </div>
            <div class="bae-font-body-sample" style="font-family:'<?php echo esc_attr($p['font_body']); ?>',sans-serif;">
                <?php echo !empty($p['tagline']) ? esc_html($p['tagline']) : 'Quality products and services crafted with care for every customer we serve.'; ?>
                Founded with a vision to bring something meaningful to the community.
            </div>
        </div>
        <div style="margin-top:16px;display:flex;gap:24px;flex-wrap:wrap;">
            <div style="font-size:13px;color:var(--text-2);">
                <strong>Heading:</strong> <?php echo esc_html($p['font_heading']); ?> — Bold 700
                <button class="bae-font-copy-btn" onclick="baeCopyText('<?php echo esc_js($p['font_heading']); ?>', this)" style="margin-left:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    Copy name
                </button>
            </div>
            <div style="font-size:13px;color:var(--text-2);">
                <strong>Body:</strong> <?php echo esc_html($p['font_body']); ?> — Regular 400
                <button class="bae-font-copy-btn" onclick="baeCopyText('<?php echo esc_js($p['font_body']); ?>', this)" style="margin-left:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    Copy name
                </button>
            </div>
        </div>
    </div>

    <!-- Tone of Voice -->
    <div class="bae-card">
        <div class="bae-card-title">Tone of Voice</div>
        <div class="bae-card-desc" style="margin-top:4px;">Derived from your industry and target audience.</div>
        <div class="bae-tone-tags">
            <?php foreach ($tone_tags as $tag): ?>
                <span class="bae-tone-tag"><?php echo esc_html($tag); ?></span>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($p['personality'])): ?>
        <div style="margin-top:16px;padding:16px;background:var(--bg-3);border-radius:8px;font-size:14px;color:var(--text-2);line-height:1.7;">
            <strong>Target Audience:</strong> <?php echo esc_html($p['personality']); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- CTA -->
    <div style="display:flex;gap:12px;">
        <a href="?tab=assets" class="bae-btn bae-btn-primary">Generate Assets &rarr;</a>
        <a href="?tab=overview" class="bae-btn bae-btn-outline">&larr; Edit Profile</a>
    </div>
    <script>
    function baeHexToRgbObject(hex) {
        hex = (hex || '').replace('#', '');
        if (hex.length !== 6) return null;
        return {
            r: parseInt(hex.slice(0, 2), 16),
            g: parseInt(hex.slice(2, 4), 16),
            b: parseInt(hex.slice(4, 6), 16)
        };
    }
    function baeHexToHslString(hex) {
        var rgb = baeHexToRgbObject(hex);
        if (!rgb) return hex;
        var r = rgb.r / 255, g = rgb.g / 255, b = rgb.b / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b);
        var h, s, l = (max + min) / 2;
        if (max === min) {
            h = 0; s = 0;
        } else {
            var d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                case g: h = (b - r) / d + 2; break;
                default: h = (r - g) / d + 4; break;
            }
            h /= 6;
        }
        return 'hsl(' + Math.round(h * 360) + ', ' + Math.round(s * 100) + '%, ' + Math.round(l * 100) + '%)';
    }
    function baeHexToCmykString(hex) {
        var rgb = baeHexToRgbObject(hex);
        if (!rgb) return hex;
        var r = rgb.r / 255, g = rgb.g / 255, b = rgb.b / 255;
        var k = 1 - Math.max(r, g, b);
        if (k === 1) return 'cmyk(0%, 0%, 0%, 100%)';
        var c = (1 - r - k) / (1 - k);
        var m = (1 - g - k) / (1 - k);
        var y = (1 - b - k) / (1 - k);
        return 'cmyk(' + Math.round(c * 100) + '%, ' + Math.round(m * 100) + '%, ' + Math.round(y * 100) + '%, ' + Math.round(k * 100) + '%)';
    }
    function baeFormatColorValue(hex, format) {
        var rgb = baeHexToRgbObject(hex);
        if (!rgb) return hex;
        if (format === 'rgb') return 'rgb(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ')';
        if (format === 'hsl') return baeHexToHslString(hex);
        if (format === 'cmyk') return baeHexToCmykString(hex);
        return (hex || '').toUpperCase();
    }
    function baeFlashCopyButton(btn, label) {
        if (!btn) return;
        var orig = btn.innerHTML;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> ' + label;
        btn.classList.add('copied');
        if (window.gsap) gsap.fromTo(btn, {scale:0.9}, {scale:1, duration:0.3, ease:'back.out(2)'});
        setTimeout(function() { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1800);
    }
    function baeToggleColorMenu(event, btn) {
        event.preventDefault();
        event.stopPropagation();
        document.querySelectorAll('.bae-swatch-copy-menu.open').forEach(function(menu) {
            if (!btn.parentNode.contains(menu)) menu.classList.remove('open');
        });
        var menu = btn.parentNode.querySelector('.bae-swatch-copy-menu');
        if (menu) menu.classList.toggle('open');
    }
    function baeCopyColorFormat(event, btn, format) {
        event.preventDefault();
        event.stopPropagation();
        var swatch = btn.closest('.bae-swatch');
        if (!swatch) return;
        var hex = swatch.dataset.color || '';
        var value = baeFormatColorValue(hex, format);
        navigator.clipboard.writeText(value).then(function() {
            var menu = swatch.querySelector('.bae-swatch-copy-menu');
            if (menu) menu.classList.remove('open');
            baeFlashCopyButton(swatch.querySelector('.bae-swatch-copy-btn'), format.toUpperCase() + ' Copied');
        });
    }
    function baeCopyText(text, btnEl) {
        navigator.clipboard.writeText(text).then(function() {
            var orig = btnEl.innerHTML;
            btnEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Copied';
            btnEl.classList.add('copied');
            if (window.gsap) gsap.fromTo(btnEl, {scale:0.9}, {scale:1, duration:0.3, ease:'back.out(2)'});
            setTimeout(function() { btnEl.innerHTML = orig; btnEl.classList.remove('copied'); }, 1800);
        });
    }
    document.addEventListener('click', function() {
        document.querySelectorAll('.bae-swatch-copy-menu.open').forEach(function(menu) {
            menu.classList.remove('open');
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// =============================================================================
// TAB: ASSET GENERATOR
// CHANGED: Sequential generate-all with progress bar and per-step status
// CHANGED: Removed bae_generate_all AJAX action (handled client-side sequentially)
// =============================================================================

