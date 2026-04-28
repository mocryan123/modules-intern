<?php if (!defined('ABSPATH')) exit;
function bae_logo_tab($user_id, $profile) {
    if (empty($profile)) {
        return '<div style="padding:40px;text-align:center;color:var(--text-3);">Complete your Brand Identity first to access Logo Studio.</div>';
    }
    $p = $profile;
    ob_start();
    ?>
    <div class="bae-logo-studio">
        <!-- Header -->
        <div class="bae-studio-header">
            <div class="bae-studio-title">
                <span class="bae-studio-eyebrow">Logo Studio</span>
                <h2>Your Logo</h2>
                <p>Upload your logo or build one with the CSS builder. This logo is used across all your brand assets.</p>
            </div>
        </div>

        <!-- Two-column layout like "Tool Storage" image -->
        <div class="bae-studio-layout">
            <!-- Left: Main preview + controls -->
            <div class="bae-studio-main">
                <!-- Live Preview Card -->
                <div class="bae-card bae-preview-card">
                    <div class="bae-card-header">
                        <span class="bae-card-title">Live Preview</span>
                        <div class="bae-preview-toggles">
                            <span class="bae-preview-badge light">Light</span>
                            <span class="bae-preview-badge dark">Dark</span>
                        </div>
                    </div>
                    <div class="bae-preview-grid">
                        <div class="bae-preview-swatch light-bg">
                            <div id="bae-logo-lockup-light"><?php echo bae_render_logo_lockup($p, ['dark'=>false]); ?></div>
                        </div>
                        <div class="bae-preview-swatch dark-bg">
                            <div id="bae-logo-lockup-dark"><?php echo bae_render_logo_lockup($p, ['dark'=>true]); ?></div>
                        </div>
                    </div>

                    <!-- Upload Area (drag & drop) -->
                    <div id="bae-logo-upload-area" class="bae-upload-area">
                        <div class="bae-upload-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        </div>
                        <div class="bae-upload-info">
                            <div class="bae-upload-title">Upload your logo</div>
                            <div class="bae-upload-desc">PNG, SVG, JPG (max 2MB). Transparent PNG recommended.</div>
                            <div class="bae-upload-actions">
                                <label class="bae-btn bae-btn-outline bae-btn-sm">
                                    Choose File
                                    <input type="file" id="bae-logo-file-input" accept="image/*" style="display:none;">
                                </label>
                                <span id="bae-logo-upload-status" class="bae-upload-status"></span>
                            </div>
                        </div>
                        <div id="bae-logo-preview-wrap" class="bae-upload-preview">
                            <?php if (!empty($p['logo_url'])): ?>
                                <img src="<?php echo esc_url($p['logo_url']); ?>" id="bae-logo-preview-img">
                            <?php else: ?>
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="hidden" id="bae-logo-url-hidden" value="<?php echo esc_attr($p['logo_url'] ?? ''); ?>">

                    <!-- CSS Builder (collapsible if logo uploaded) -->
                    <div id="bae-logo-css-builder" class="<?php echo !empty($p['logo_url']) ? 'bae-collapsed' : ''; ?>">
                        <div class="bae-builder-header" onclick="baeToggleBuilder()">
                            <span>CSS Logo Builder <span class="bae-builder-sub">— fallback when no logo</span></span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                        <div class="bae-builder-content">
                            <div class="bae-form-grid">
                                <div class="bae-form-group">
                                    <label>Logo Type</label>
                                    <select name="logo_style" id="bae-logo-style">
                                        <?php
                                        $styles = [
                                            'wordmark'    => 'Wordmark — Name as logo',
                                            'lettermark'  => 'Lettermark — Initials only',
                                            'combination' => 'Combination — Icon + Name',
                                            'emblem'      => 'Emblem — Icon inside badge',
                                            'monogram'    => 'Monogram — Stylized initials',
                                            'abstract'    => 'Abstract Mark — Icon only',
                                            'badge'       => 'Badge — Circular seal',
                                            'stacked'     => 'Stacked — Icon above name',
                                            'outlined'    => 'Outlined — Name with border',
                                            'minimal'     => 'Minimal — Initials with dot',
                                        ];
                                        $sls = $p['logo_style'] ?? 'wordmark';
                                        foreach ($styles as $val => $lbl): ?>
                                            <option value="<?php echo $val; ?>" <?php selected($sls, $val); ?>><?php echo $lbl; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="bae-form-group">
                                    <label>Icon <span class="bae-helper-icon">(pick one)</span></label>
                                    <div class="bae-icon-grid" id="bae-icon-picker">
                                        <?php
                                        $all_icons = bae_get_all_icons();
                                        $sli = $p['logo_icon'] ?? '';
                                        foreach ($all_icons as $icon_key => $icon_label):
                                            $svg = bae_get_icon_svg_preview($icon_key);
                                        ?>
                                        <div class="bae-icon-tile <?php echo $sli === $icon_key ? 'selected' : ''; ?>"
                                             data-value="<?php echo esc_attr($icon_key); ?>"
                                             title="<?php echo esc_attr($icon_label); ?>">
                                            <?php echo $svg; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" id="bae-logo-icon-hidden" name="logo_icon" value="<?php echo esc_attr($sli); ?>">
                                </div>
                            </div>
                            <div class="bae-form-grid">
                                <div class="bae-form-group">
                                    <label>Icon Scale — <span id="bae-logo-icon-scale-value"><?php echo esc_html((int)($p['logo_icon_scale'] ?? 100)); ?>%</span></label>
                                    <input type="range" id="bae-logo-icon-scale" name="logo_icon_scale" min="60" max="160" step="5" value="<?php echo esc_attr((int)($p['logo_icon_scale'] ?? 100)); ?>">
                                </div>
                                <div class="bae-form-group">
                                    <label>Spacing — <span id="bae-logo-spacing-value"><?php echo esc_html((int)($p['logo_spacing'] ?? 14)); ?>px</span></label>
                                    <input type="range" id="bae-logo-spacing" name="logo_spacing" min="6" max="28" step="1" value="<?php echo esc_attr((int)($p['logo_spacing'] ?? 14)); ?>">
                                </div>
                                <div class="bae-form-group">
                                    <label>Icon Position</label>
                                    <?php $logo_pos = $p['logo_position'] ?? 'auto'; ?>
                                    <select name="logo_position" id="bae-logo-position">
                                        <option value="auto" <?php selected($logo_pos, 'auto'); ?>>Auto</option>
                                        <option value="left" <?php selected($logo_pos, 'left'); ?>>Left</option>
                                        <option value="top" <?php selected($logo_pos, 'top'); ?>>Top</option>
                                        <option value="right" <?php selected($logo_pos, 'right'); ?>>Right</option>
                                    </select>
                                </div>
                                <div class="bae-form-group">
                                    <label>Text Case</label>
                                    <?php $logo_case = $p['logo_text_case'] ?? 'default'; ?>
                                    <select name="logo_text_case" id="bae-logo-case">
                                        <option value="default" <?php selected($logo_case, 'default'); ?>>Default</option>
                                        <option value="uppercase" <?php selected($logo_case, 'uppercase'); ?>>UPPERCASE</option>
                                        <option value="title" <?php selected($logo_case, 'title'); ?>>Title Case</option>
                                        <option value="lowercase" <?php selected($logo_case, 'lowercase'); ?>>lowercase</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save & Download -->
                    <div class="bae-studio-actions">
                        <button type="button" id="bae-logo-save-btn" class="bae-btn bae-btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save Logo Settings
                        </button>
                        <span id="bae-logo-save-status" class="bae-save-status"></span>
                        <div class="bae-download-group">
                            <button type="button" id="bae-logo-download-png" class="bae-btn bae-btn-outline bae-btn-sm">PNG</button>
                            <button type="button" id="bae-logo-download-svg" class="bae-btn bae-btn-outline bae-btn-sm">SVG</button>
                            <span id="bae-logo-download-status" class="bae-download-status"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Logo Variants "Pods" (like Tool Storage) -->
            <div class="bae-studio-sidebar">
                <div class="bae-pods-header">
                    <span class="bae-pods-title">Logo Variants</span>
                    <span class="bae-pods-count"><?php echo count($styles); ?> styles</span>
                </div>
                <div class="bae-pods-grid" id="bae-pods-grid">
                    <?php foreach ($styles as $style_key => $style_label):
                        $current_style = $p['logo_style'] ?? 'wordmark';
                        $is_active = ($current_style === $style_key);
                    ?>
                    <div class="bae-pod" data-style="<?php echo esc_attr($style_key); ?>">
                        <div class="bae-pod-preview">
                            <?php
                            // Generate a quick preview for this style using current brand data
                            $preview_profile = $p;
                            $preview_profile['logo_style'] = $style_key;
                            // Keep existing logo_url? If logo_url exists, we show that, else show CSS preview.
                            if (!empty($p['logo_url'])) {
                                echo '<img src="'.esc_url($p['logo_url']).'" style="max-height:40px;max-width:100%;object-fit:contain;">';
                            } else {
                                echo bae_render_logo_lockup($preview_profile, ['dark'=>false, 'compact'=>true]);
                            }
                            ?>
                        </div>
                        <div class="bae-pod-info">
                            <div class="bae-pod-name"><?php echo esc_html(explode(' — ', $style_label)[0]); ?></div>
                            <div class="bae-pod-status <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                            </div>
                        </div>
                        <button class="bae-pod-apply" data-style="<?php echo esc_attr($style_key); ?>">Apply</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Background Checker (hidden until needed) -->
        <div id="bae-logo-bg-checker" style="display:none;">
            <div class="bae-checker-header">Background Checker</div>
            <div id="bae-logo-bg-grid" class="bae-checker-grid"></div>
        </div>
    </div>

    <style>
    /* Logo Studio – Tool Storage style */
    .bae-logo-studio {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    .bae-studio-header {
        margin-bottom: 28px;
    }
    .bae-studio-eyebrow {
        font-size: 11px;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--text-3);
    }
    .bae-studio-header h2 {
        font-size: 28px;
        font-weight: 700;
        margin: 4px 0 6px;
        color: var(--text);
    }
    .bae-studio-header p {
        font-size: 14px;
        color: var(--text-3);
        max-width: 600px;
    }

    /* Two‑column layout */
    .bae-studio-layout {
        display: flex;
        gap: 28px;
        align-items: stretch;
    }
    .bae-studio-main {
        flex: 2;
        min-width: 0;
    }
    .bae-studio-sidebar {
        flex: 1.2;
        min-width: 280px;
    }
    @media (max-width: 900px) {
        .bae-studio-layout { flex-direction: column; }
        .bae-studio-sidebar { width: 100%; }
    }

    /* Preview Card */
    .bae-preview-card {
        background: var(--surface);
        border-radius: 28px;
        border: 1px solid var(--border);
        overflow: hidden;
        margin-bottom: 0;
    }
    .bae-preview-card .bae-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 24px 0;
    }
    .bae-preview-toggles {
        display: flex;
        gap: 8px;
    }
    .bae-preview-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 40px;
        background: var(--bg-3);
        color: var(--text-2);
    }
    .bae-preview-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        padding: 20px 24px;
    }
    .bae-preview-swatch {
        border-radius: 18px;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 110px;
        border: 1px solid var(--border);
    }
    .light-bg { background: #ffffff; }
    .dark-bg { background: #0f0e17; }

    /* Upload Area */
    .bae-upload-area {
        background: var(--bg-3);
        border: 2px dashed var(--border-2);
        border-radius: 20px;
        margin: 0 24px 20px;
        padding: 18px;
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
        transition: border-color .2s, background .2s;
    }
    .bae-upload-area.drag-over {
        border-color: var(--brand);
        background: rgba(243,45,134,.05);
    }
    .bae-upload-icon {
        color: var(--text-3);
    }
    .bae-upload-info {
        flex: 1;
        min-width: 180px;
    }
    .bae-upload-title {
        font-weight: 700;
        margin-bottom: 4px;
        color: var(--text);
    }
    .bae-upload-desc {
        font-size: 12px;
        color: var(--text-3);
        margin-bottom: 10px;
    }
    .bae-upload-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .bae-upload-preview {
        width: 70px;
        height: 70px;
        background: var(--surface);
        border-radius: 12px;
        border: 1px solid var(--border-2);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }
    .bae-upload-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    /* CSS Builder (collapsible) */
    .bae-collapsed .bae-builder-content {
        display: none;
    }
    .bae-builder-header {
        padding: 14px 24px;
        background: var(--bg-3);
        border-top: 1px solid var(--border);
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        font-size: 13px;
        color: var(--text-2);
    }
    .bae-builder-header svg {
        transition: transform .2s;
    }
    .bae-collapsed .bae-builder-header svg {
        transform: rotate(180deg);
    }
    .bae-builder-content {
        padding: 20px 24px 24px;
        transition: all .2s;
    }
    .bae-builder-sub {
        font-weight: 400;
        color: var(--text-3);
        font-size: 12px;
    }

    /* Form grids */
    .bae-form-grid {
        display: grid;
        grid-template-columns: repeat(2,1fr);
        gap: 20px;
        margin-bottom: 20px;
    }
    @media (max-width: 600px) {
        .bae-form-grid { grid-template-columns: 1fr; }
    }
    .bae-form-group label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-2);
        margin-bottom: 8px;
        display: block;
    }
    .bae-icon-grid {
        display: grid;
        grid-template-columns: repeat(8,1fr);
        gap: 6px;
        background: var(--bg-3);
        border-radius: 14px;
        padding: 10px;
        border: 1px solid var(--border-2);
        max-height: 180px;
        overflow-y: auto;
    }
    .bae-icon-tile {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1.5px solid transparent;
        background: var(--surface);
        cursor: pointer;
        transition: all .15s;
    }
    .bae-icon-tile:hover { border-color: rgba(243,45,134,.4); background: rgba(243,45,134,.08); }
    .bae-icon-tile.selected { border-color: var(--brand); background: rgba(243,45,134,.15); }

    /* Action buttons */
    .bae-studio-actions {
        padding: 16px 24px 24px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        border-top: 1px solid var(--border);
    }
    .bae-download-group {
        margin-left: auto;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .bae-save-status, .bae-download-status {
        font-size: 12px;
        color: var(--text-3);
    }

    /* Right sidebar – Variants pods */
    .bae-pods-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 18px;
        padding-left: 4px;
    }
    .bae-pods-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text);
    }
    .bae-pods-count {
        font-size: 12px;
        color: var(--text-3);
    }
    .bae-pods-grid {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .bae-pod {
        background: var(--surface);
        border-radius: 20px;
        border: 1px solid var(--border);
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: all .2s;
    }
    .bae-pod:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        border-color: rgba(243,45,134,.3);
    }
    .bae-pod-active {
        border-left: 3px solid var(--brand);
    }
    .bae-pod-preview {
        width: 80px;
        height: 60px;
        background: var(--bg-3);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
        font-size: 12px;
    }
    .bae-pod-info {
        flex: 1;
    }
    .bae-pod-name {
        font-weight: 700;
        font-size: 14px;
        color: var(--text);
        margin-bottom: 4px;
    }
    .bae-pod-status {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .bae-pod-status.active {
        color: var(--brand-soft);
    }
    .bae-pod-status.inactive {
        color: var(--text-3);
    }
    .bae-pod-apply {
        background: transparent;
        border: 1px solid var(--border-2);
        border-radius: 40px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-2);
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }
    .bae-pod-apply:hover {
        background: rgba(243,45,134,.1);
        border-color: var(--brand);
        color: var(--brand);
    }

    /* Checker */
    .bae-checker-header {
        font-size: 12px;
        font-weight: 700;
        margin: 20px 0 12px;
        color: var(--text-2);
    }
    .bae-checker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill,minmax(90px,1fr));
        gap: 10px;
    }
    </style>

    <script>
    (function() {
        var ajaxurl = window.ajaxurl || '';
        var baeLogoProfile = <?php echo wp_json_encode([
            'id'             => (int)($p['id'] ?? 0),
            'business_name'  => $p['business_name'] ?? '',
            'tagline'        => $p['tagline'] ?? '',
            'primary_color'  => $p['primary_color'] ?? '#1a1a2e',
            'secondary_color'=> $p['secondary_color'] ?? '#16213e',
            'accent_color'   => $p['accent_color'] ?? '#e94560',
            'font_heading'   => $p['font_heading'] ?? 'Inter',
            'logo_style'     => $p['logo_style'] ?? 'wordmark',
            'logo_icon'      => $p['logo_icon'] ?? '',
            'logo_icon_scale'=> (int)($p['logo_icon_scale'] ?? 100),
            'logo_spacing'   => (int)($p['logo_spacing'] ?? 14),
            'logo_position'  => $p['logo_position'] ?? 'auto',
            'logo_text_case' => $p['logo_text_case'] ?? 'default',
            'logo_url'       => $p['logo_url'] ?? '',
            'nonce'          => wp_create_nonce('bae_save_profile'),
        ]); ?>;

        // Icon picker
        function baeSelectIcon(el) {
            document.querySelectorAll('.bae-icon-tile').forEach(function(t){ t.classList.remove('selected'); });
            el.classList.add('selected');
            var val = el.dataset.value || '';
            document.getElementById('bae-logo-icon-hidden').value = val;
            baeLogoProfile.logo_icon = val;
            baeRequestPreviewRefresh();
        }
        window.baeSelectIcon = baeSelectIcon;
        document.querySelectorAll('.bae-icon-tile').forEach(function(tile) {
            tile.addEventListener('click', function() { baeSelectIcon(this); });
        });

        // Bind controls
        function bindSelect(selector, key) {
            document.querySelector(selector).addEventListener('change', function() {
                baeLogoProfile[key] = this.value;
                baeRequestPreviewRefresh();
            });
        }
        bindSelect('#bae-logo-style', 'logo_style');
        bindSelect('#bae-logo-position', 'logo_position');
        bindSelect('#bae-logo-case', 'logo_text_case');

        var scaleRange = document.getElementById('bae-logo-icon-scale');
        var spacingRange = document.getElementById('bae-logo-spacing');
        function updateRange(which) {
            var val = which.value;
            var span = document.getElementById(which.id+'-value');
            if (span) span.textContent = val + (which.id === 'bae-logo-icon-scale' ? '%' : 'px');
            baeLogoProfile[which.id === 'bae-logo-icon-scale' ? 'logo_icon_scale' : 'logo_spacing'] = parseInt(val,10);
            baeRequestPreviewRefresh();
        }
        if (scaleRange) scaleRange.addEventListener('input', function() { updateRange(this); });
        if (spacingRange) spacingRange.addEventListener('input', function() { updateRange(this); });

        // Live preview + debounce
        var previewTimer = null;
        function baeRequestPreviewRefresh() {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(baeRefreshPreview, 200);
        }
        function baeRefreshPreview() {
            var logoUrl = document.getElementById('bae-logo-url-hidden').value;
            if (logoUrl) {
                var lightHtml = '<img src="'+logoUrl+'" style="max-height:52px;max-width:160px;object-fit:contain;" alt="logo">';
                var darkHtml = '<img src="'+logoUrl+'" style="max-height:52px;max-width:160px;object-fit:contain;filter:brightness(0) invert(1);opacity:.92;" alt="logo">';
                var lightCont = document.getElementById('bae-logo-lockup-light');
                var darkCont = document.getElementById('bae-logo-lockup-dark');
                if (lightCont) lightCont.innerHTML = lightHtml;
                if (darkCont) darkCont.innerHTML = darkHtml;
                return;
            }
            var fd = new FormData();
            fd.append('action','bae_logo_preview');
            fd.append('nonce', baeLogoProfile.nonce);
            fd.append('profile_id', baeLogoProfile.id);
            fd.append('logo_style', baeLogoProfile.logo_style);
            fd.append('logo_icon', baeLogoProfile.logo_icon);
            fd.append('logo_icon_scale', baeLogoProfile.logo_icon_scale);
            fd.append('logo_spacing', baeLogoProfile.logo_spacing);
            fd.append('logo_position', baeLogoProfile.logo_position);
            fd.append('logo_text_case', baeLogoProfile.logo_text_case);
            fetch(ajaxurl,{method:'POST',body:fd})
                .then(function(r){return r.json();})
                .then(function(data){
                    if (data.success) {
                        var light = document.getElementById('bae-logo-lockup-light');
                        var dark = document.getElementById('bae-logo-lockup-dark');
                        if (light && data.data.light) light.innerHTML = data.data.light;
                        if (dark && data.data.dark) dark.innerHTML = data.data.dark;
                    }
                }).catch(function(){});
        }

        // Save settings
        var saveBtn = document.getElementById('bae-logo-save-btn');
        var saveStatus = document.getElementById('bae-logo-save-status');
        function baeLogoSave(extra) {
            if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }
            var fd = new FormData();
            fd.append('action','bae_save_profile');
            fd.append('nonce', baeLogoProfile.nonce);
            fd.append('profile_id', baeLogoProfile.id);
            fd.append('logo_style', baeLogoProfile.logo_style);
            fd.append('logo_icon', baeLogoProfile.logo_icon);
            fd.append('logo_icon_scale', baeLogoProfile.logo_icon_scale);
            fd.append('logo_spacing', baeLogoProfile.logo_spacing);
            fd.append('logo_position', baeLogoProfile.logo_position);
            fd.append('logo_text_case', baeLogoProfile.logo_text_case);
            fd.append('logo_url', document.getElementById('bae-logo-url-hidden').value);
            fd.append('business_name', baeLogoProfile.business_name);
            fd.append('tagline', baeLogoProfile.tagline);
            fd.append('primary_color', baeLogoProfile.primary_color);
            fd.append('secondary_color', baeLogoProfile.secondary_color);
            fd.append('accent_color', baeLogoProfile.accent_color);
            fd.append('font_heading', baeLogoProfile.font_heading);
            if (extra) Object.keys(extra).forEach(function(k){ fd.append(k, extra[k]); });
            fetch(ajaxurl,{method:'POST',body:fd})
                .then(function(r){return r.json();})
                .then(function(data){
                    if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Logo Settings'; }
                    if (saveStatus) {
                        saveStatus.textContent = data.success ? '✓ Saved' : (data.data && data.data.message ? data.data.message : 'Save failed.');
                        saveStatus.style.color = data.success ? '#34d399' : '#fb7185';
                        setTimeout(function(){ if(saveStatus) saveStatus.textContent = ''; }, 3000);
                    }
                    if (data.success && typeof window.baeToast === 'function') window.baeToast('Logo settings saved.','success');
                })
                .catch(function(){
                    if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Logo Settings'; }
                    if (saveStatus) { saveStatus.textContent = 'Network error.'; saveStatus.style.color = '#fb7185'; }
                });
        }
        if (saveBtn) saveBtn.addEventListener('click', function(){ baeLogoSave(); });

        // File upload
        var fileInput = document.getElementById('bae-logo-file-input');
        var uploadArea = document.getElementById('bae-logo-upload-area');
        var uploadStatus = document.getElementById('bae-logo-upload-status');
        function baeUploadFile(file) {
            if (!file) return;
            var allowed = ['image/png','image/jpeg','image/jpg','image/svg+xml','image/gif','image/webp'];
            if (!allowed.includes(file.type)) {
                if (uploadStatus) { uploadStatus.textContent = 'PNG, JPG, SVG only.'; uploadStatus.style.color = '#fb7185'; }
                return;
            }
            if (file.size > 2*1024*1024) {
                if (uploadStatus) { uploadStatus.textContent = 'Max 2MB.'; uploadStatus.style.color = '#fb7185'; }
                return;
            }
            if (uploadStatus) { uploadStatus.textContent = 'Uploading…'; uploadStatus.style.color = 'var(--text-3)'; }
            var fd = new FormData();
            fd.append('action','bae_upload_logo');
            fd.append('nonce', baeLogoProfile.nonce);
            fd.append('logo_file', file);
            if (baeLogoProfile.id) fd.append('profile_id', baeLogoProfile.id);
            fetch(ajaxurl,{method:'POST',body:fd})
                .then(function(r){return r.json();})
                .then(function(data){
                    if (data.success && data.data && data.data.url) {
                        var url = data.data.url;
                        document.getElementById('bae-logo-url-hidden').value = url;
                        baeLogoProfile.logo_url = url;
                        var previewWrap = document.getElementById('bae-logo-preview-wrap');
                        if (previewWrap) previewWrap.innerHTML = '<img src="'+url+'" style="max-width:100%;max-height:100%;object-fit:contain;">';
                        var builder = document.getElementById('bae-logo-css-builder');
                        if (builder) builder.classList.add('bae-collapsed');
                        if (uploadStatus) { uploadStatus.textContent = '✓ Uploaded'; uploadStatus.style.color = '#34d399'; }
                        baeRefreshPreview();
                        baeLogoSave({logo_url:url});
                        // Add remove/check buttons
                        baeEnsurePostUploadButtons();
                    } else {
                        if (uploadStatus) { uploadStatus.textContent = (data.data && data.data.message) ? data.data.message : 'Upload failed.'; uploadStatus.style.color = '#fb7185'; }
                    }
                }).catch(function(){ if (uploadStatus) { uploadStatus.textContent = 'Network error.'; uploadStatus.style.color = '#fb7185'; } });
        }
        if (fileInput) fileInput.addEventListener('change', function(){ baeUploadFile(this.files[0]); });
        if (uploadArea) {
            uploadArea.addEventListener('dragover', function(e){ e.preventDefault(); this.classList.add('drag-over'); });
            uploadArea.addEventListener('dragleave', function(){ this.classList.remove('drag-over'); });
            uploadArea.addEventListener('drop', function(e){ e.preventDefault(); this.classList.remove('drag-over'); baeUploadFile(e.dataTransfer.files && e.dataTransfer.files[0]); });
        }

        function baeEnsurePostUploadButtons() {
            var existingRemove = document.getElementById('bae-logo-remove-btn');
            if (existingRemove) return;
            var actionsDiv = document.querySelector('.bae-upload-actions');
            if (!actionsDiv) return;
            var removeBtn = document.createElement('button');
            removeBtn.id = 'bae-logo-remove-btn';
            removeBtn.className = 'bae-btn bae-btn-outline bae-btn-sm';
            removeBtn.textContent = 'Remove';
            var checkBtn = document.createElement('button');
            checkBtn.id = 'bae-logo-check-btn';
            checkBtn.className = 'bae-btn bae-btn-outline bae-btn-sm';
            checkBtn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg> Check BG';
            actionsDiv.appendChild(removeBtn);
            actionsDiv.appendChild(checkBtn);
            removeBtn.addEventListener('click', function() {
                document.getElementById('bae-logo-url-hidden').value = '';
                baeLogoProfile.logo_url = '';
                var previewWrap = document.getElementById('bae-logo-preview-wrap');
                if (previewWrap) previewWrap.innerHTML = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';
                var builder = document.getElementById('bae-logo-css-builder');
                if (builder) builder.classList.remove('bae-collapsed');
                removeBtn.remove();
                checkBtn.remove();
                baeRefreshPreview();
                baeLogoSave({logo_url:''});
            });
            checkBtn.addEventListener('click', function() {
                var logoUrl = document.getElementById('bae-logo-url-hidden').value;
                if (!logoUrl) return;
                var checker = document.getElementById('bae-logo-bg-checker');
                var grid = document.getElementById('bae-logo-bg-grid');
                if (!checker || !grid) return;
                if (checker.style.display === 'none') {
                    checker.style.display = 'block';
                    if (grid.children.length === 0) {
                        var colors = ['#ffffff','#000000','#f5f5f5','#1a1a2e','#F32D86','#2d1066','#ea580c','#16213e'];
                        colors.forEach(function(bg) {
                            var cell = document.createElement('div');
                            cell.style.cssText = 'background:'+bg+';border-radius:12px;padding:10px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(128,128,128,.2);';
                            cell.innerHTML = '<img src="'+logoUrl+'" style="max-width:70px;max-height:40px;object-fit:contain;">';
                            grid.appendChild(cell);
                        });
                    }
                } else {
                    checker.style.display = 'none';
                }
            });
        }
        baeEnsurePostUploadButtons(); // in case logo already exists on load

        // Pods (variant) click handling
        document.querySelectorAll('.bae-pod-apply').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var style = this.dataset.style;
                var styleSelect = document.getElementById('bae-logo-style');
                if (styleSelect) {
                    styleSelect.value = style;
                    // trigger change
                    var evt = new Event('change');
                    styleSelect.dispatchEvent(evt);
                }
                // Update active status in pods UI
                document.querySelectorAll('.bae-pod').forEach(function(pod) {
                    var podStyle = pod.dataset.style;
                    var statusSpan = pod.querySelector('.bae-pod-status');
                    if (podStyle === style) {
                        statusSpan.classList.remove('inactive');
                        statusSpan.classList.add('active');
                        statusSpan.textContent = 'Active';
                    } else {
                        statusSpan.classList.remove('active');
                        statusSpan.classList.add('inactive');
                        statusSpan.textContent = 'Inactive';
                    }
                });
                // Also scroll to top and show toast
                if (typeof window.baeToast === 'function') window.baeToast('Applied '+style+' style', 'success');
            });
        });

        // Toggle CSS Builder
        window.baeToggleBuilder = function() {
            var builder = document.getElementById('bae-logo-css-builder');
            if (builder) builder.classList.toggle('bae-collapsed');
        };

        // Download
        var pngBtn = document.getElementById('bae-logo-download-png');
        var svgBtn = document.getElementById('bae-logo-download-svg');
        function baeLogoDownload(fmt) {
            var st = document.getElementById('bae-logo-download-status');
            var logoUrl = document.getElementById('bae-logo-url-hidden').value;
            if (logoUrl && fmt === 'png') {
                var a = document.createElement('a'); a.href = logoUrl; a.download = (baeLogoProfile.business_name || 'logo') + '.png'; a.click();
                return;
            }
            if (st) { st.textContent = 'Preparing…'; st.style.color = 'var(--text-3)'; }
            var lockupEl = document.getElementById('bae-logo-lockup-light');
            if (!lockupEl) { if(st) st.textContent = 'Nothing to export.'; return; }
            var name = baeLogoProfile.business_name || 'logo';
            if (fmt === 'svg') {
                var svgString = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="100"><foreignObject width="400" height="100"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:12px;display:flex;align-items:center;">'+lockupEl.innerHTML+'</body></foreignObject></svg>';
                var blob = new Blob([svgString], {type:'image/svg+xml'});
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a'); a.href = url; a.download = name+'.svg'; a.click();
                setTimeout(function(){ URL.revokeObjectURL(url); }, 2000);
                if(st){ st.textContent = '✓ Downloaded'; setTimeout(function(){ if(st) st.textContent=''; },2500); }
            } else {
                var canvas = document.createElement('canvas'); canvas.width = 800; canvas.height = 200;
                var ctx = canvas.getContext('2d'); ctx.fillStyle = '#ffffff'; ctx.fillRect(0,0,800,200);
                var svgBlob = new Blob(['<svg xmlns="http://www.w3.org/2000/svg" width="800" height="200"><foreignObject width="800" height="200"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:24px;display:flex;align-items:center;zoom:2;">'+lockupEl.innerHTML+'</body></foreignObject></svg>'], {type:'image/svg+xml'});
                var burl = URL.createObjectURL(svgBlob);
                var img = new Image(); img.crossOrigin = 'anonymous';
                img.onload = function() {
                    ctx.drawImage(img,0,0); URL.revokeObjectURL(burl);
                    var a = document.createElement('a'); a.download = name+'.png'; a.href = canvas.toDataURL('image/png'); a.click();
                    if(st){ st.textContent = '✓ Downloaded'; setTimeout(function(){ if(st) st.textContent=''; },2500); }
                };
                img.onerror = function(){ if(st){ st.textContent = 'PNG failed — try SVG.'; st.style.color = '#fb7185'; } };
                img.src = burl;
            }
        }
        if(pngBtn) pngBtn.addEventListener('click', function(){ baeLogoDownload('png'); });
        if(svgBtn) svgBtn.addEventListener('click', function(){ baeLogoDownload('svg'); });

        // Initial sync: mark active pod
        var currentStyle = baeLogoProfile.logo_style;
        document.querySelectorAll('.bae-pod').forEach(function(pod) {
            var podStyle = pod.dataset.style;
            var statusSpan = pod.querySelector('.bae-pod-status');
            if (podStyle === currentStyle) {
                statusSpan.classList.add('active');
                statusSpan.classList.remove('inactive');
                statusSpan.textContent = 'Active';
            } else {
                statusSpan.classList.add('inactive');
                statusSpan.classList.remove('active');
                statusSpan.textContent = 'Inactive';
            }
        });
    })();
    </script>
    <?php
    return bae_wrap_tab_panel(ob_get_clean());
}
