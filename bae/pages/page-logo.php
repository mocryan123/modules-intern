<?php if (!defined('ABSPATH')) exit;
function bae_logo_tab($user_id, $profile) {
    if (empty($profile)) {
        return '<div style="padding:60px;text-align:center;color:var(--text-3);">Complete your Brand Identity first to access Logo Studio.</div>';
    }
    $p = $profile;

    $styles = [
        'wordmark'    => ['label'=>'Wordmark',    'sub'=>'Name as logo'],
        'lettermark'  => ['label'=>'Lettermark',  'sub'=>'Initials only'],
        'combination' => ['label'=>'Combination', 'sub'=>'Icon + Name'],
        'emblem'      => ['label'=>'Emblem',      'sub'=>'Icon in badge'],
        'monogram'    => ['label'=>'Monogram',    'sub'=>'Stylised initials'],
        'abstract'    => ['label'=>'Abstract',    'sub'=>'Icon only'],
        'badge'       => ['label'=>'Badge',       'sub'=>'Circular seal'],
        'stacked'     => ['label'=>'Stacked',     'sub'=>'Icon above name'],
        'outlined'    => ['label'=>'Outlined',    'sub'=>'Name with border'],
        'minimal'     => ['label'=>'Minimal',     'sub'=>'Initials + dot'],
    ];
    $current_style = $p['logo_style'] ?? 'wordmark';

    ob_start();
    ?>
    <div class="bae-logo-studio">
        <!-- Header -->
        <div class="ls-header">
            <div>
                <div class="ls-eyebrow">Studio</div>
                <h1 class="ls-title">Logo Studio</h1>
                <p class="ls-desc">Upload your logo or build one with the CSS builder. This logo is used across all your brand assets.</p>
            </div>
        </div>

        <!-- Three‑column layout: left = pods, center = circular stage, right = controls -->
        <div class="ls-layout-3col">
            <!-- LEFT: Logo variants pods -->
            <div class="ls-left-pods">
                <div class="ls-section-title">Logo Variants</div>
                <div class="ls-pods-grid" id="ls-pods-grid">
                    <?php foreach ($styles as $key => $info):
                        $is_active = ($current_style === $key);
                        $preview_profile = $p;
                        $preview_profile['logo_style'] = $key;
                        $preview_html = bae_render_logo_lockup($preview_profile, ['compact' => true, 'dark' => false]);
                    ?>
                    <div class="ls-pod" data-style="<?php echo esc_attr($key); ?>">
                        <div class="ls-pod-thumb">
                            <?php echo $preview_html; ?>
                        </div>
                        <div class="ls-pod-info">
                            <div class="ls-pod-name"><?php echo esc_html($info['label']); ?></div>
                            <div class="ls-pod-sub"><?php echo esc_html($info['sub']); ?></div>
                        </div>
                        <div class="ls-pod-status">
                            <span class="ls-badge <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        <div class="ls-pod-progress">
                            <div class="ls-progress-track">
                                <div class="ls-progress-fill" style="width: <?php echo $is_active ? '100' : '0'; ?>%;"></div>
                            </div>
                        </div>
                        <button class="ls-pod-apply" data-style="<?php echo esc_attr($key); ?>">
                            <?php echo $is_active ? 'Applied' : 'Apply'; ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="ls-metrics-strip">
                    <div class="ls-metric-item">
                        <div class="ls-metric-val"><?php echo count($styles); ?></div>
                        <div class="ls-metric-lbl">Styles</div>
                    </div>
                    <div class="ls-metric-divider"></div>
                    <div class="ls-metric-item">
                        <div class="ls-metric-val"><?php echo !empty($p['logo_url']) ? '1' : '0'; ?></div>
                        <div class="ls-metric-lbl">Uploaded</div>
                    </div>
                    <div class="ls-metric-divider"></div>
                    <div class="ls-metric-item">
                        <div class="ls-metric-val">2</div>
                        <div class="ls-metric-lbl">Previews</div>
                    </div>
                </div>
            </div>

            <!-- CENTER: Circular Stage (original, untouched) -->
            <div class="ls-center-stage">
                <!-- Top bar (mode tag + brand name + action icons) -->
                <div class="ls-topbar">
                    <div class="ls-mode-tag" id="ls-mode-tag">
                        <?php echo !empty($p['logo_url']) ? 'Image Mode' : 'CSS Builder'; ?>
                    </div>
                    <div class="ls-brand-name"><?php echo esc_html($p['business_name'] ?? 'Your Brand'); ?></div>
                    <div class="ls-top-actions">
                        <button class="ls-icon-btn" id="ls-download-png" title="Export PNG">PNG</button>
                        <button class="ls-icon-btn" id="ls-download-svg" title="Export SVG">SVG</button>
                        <button class="ls-icon-btn" id="ls-save-main" title="Save">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        </button>
                        <span class="ls-save-status" id="ls-save-status"></span>
                    </div>
                </div>

                <!-- Orbit stage (the big circular preview) -->
                <div class="ls-stage-wrap">
                    <!-- Orbit rings -->
                    <svg class="ls-orbit-ring" viewBox="0 0 560 560">
                        <circle cx="280" cy="280" r="270" fill="none" stroke="currentColor" stroke-width="0.5" stroke-dasharray="4 8" opacity="0.15"/>
                        <circle cx="280" cy="280" r="230" fill="none" stroke="currentColor" stroke-width="0.5" stroke-dasharray="2 12" opacity="0.1"/>
                    </svg>

                    <!-- Satellites -->
                    <div class="ls-satellite ls-sat-light">
                        <div class="ls-sat-label">Light</div>
                        <div class="ls-sat-preview light-surface" id="ls-preview-light">
                            <?php echo bae_render_logo_lockup($p, ['dark'=>false]); ?>
                        </div>
                    </div>
                    <div class="ls-satellite ls-sat-dark">
                        <div class="ls-sat-label">Dark</div>
                        <div class="ls-sat-preview dark-surface" id="ls-preview-dark">
                            <?php echo bae_render_logo_lockup($p, ['dark'=>true]); ?>
                        </div>
                    </div>
                    <div class="ls-satellite ls-sat-upload">
                        <div class="ls-sat-label">Upload</div>
                        <label class="ls-sat-preview ls-upload-sat" id="ls-upload-sat">
                            <?php if (!empty($p['logo_url'])): ?>
                                <img src="<?php echo esc_url($p['logo_url']); ?>" id="ls-upload-img" alt="logo">
                            <?php else: ?>
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <span id="ls-upload-hint">Drop or click</span>
                            <?php endif; ?>
                            <input type="file" id="ls-file-input" accept="image/*" style="display:none">
                        </label>
                        <span class="ls-sat-status" id="ls-upload-status"></span>
                    </div>
                    <div class="ls-satellite ls-sat-checker">
                        <div class="ls-sat-label">BG Check</div>
                        <button class="ls-sat-preview ls-checker-sat" id="ls-checker-btn" title="Background Checker">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        </button>
                    </div>

                    <!-- Main circle -->
                    <div class="ls-circle-stage">
                        <div class="ls-tick-ring" id="ls-tick-ring">
                            <?php for ($i = 0; $i < 60; $i++): ?>
                            <div class="ls-tick" style="transform:rotate(<?php echo $i * 6; ?>deg)">
                                <div class="ls-tick-inner <?php echo ($i % 5 === 0) ? 'major' : ''; ?>"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div class="ls-circle-disc">
                            <div class="ls-logo-display" id="ls-logo-display">
                                <?php if (!empty($p['logo_url'])): ?>
                                    <img src="<?php echo esc_url($p['logo_url']); ?>" id="ls-main-logo-img">
                                <?php else: ?>
                                    <div id="ls-logo-lockup"><?php echo bae_render_logo_lockup($p, ['dark'=>false]); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="ls-circle-label">
                                <span id="ls-circle-status"><?php echo esc_html($current_style); ?></span>
                            </div>
                        </div>
                        <button class="ls-radial-btn ls-rb-top" id="ls-rb-refresh" title="Refresh">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        </button>
                        <button class="ls-radial-btn ls-rb-right" id="ls-rb-check" title="Check backgrounds">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                        <button class="ls-radial-btn ls-rb-bottom" id="ls-rb-remove" title="Remove logo">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                        </button>
                        <button class="ls-radial-btn ls-rb-left" id="ls-rb-export" title="Export">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>

                    <!-- Mode dots -->
                    <div class="ls-mode-dots">
                        <button class="ls-mode-dot active" data-mode="light"></button>
                        <button class="ls-mode-dot" data-mode="dark"></button>
                        <button class="ls-mode-dot" data-mode="upload"></button>
                        <button class="ls-mode-dot" data-mode="check"></button>
                    </div>
                </div>

                <!-- Background Checker panel (hidden initially) -->
                <div class="ls-checker-panel" id="ls-checker-panel" style="display:none">
                    <div class="ls-checker-label">Background compatibility</div>
                    <div class="ls-checker-grid" id="ls-checker-grid"></div>
                </div>
            </div>

            <!-- RIGHT: CSS Builder + Controls -->
            <div class="ls-right-controls">
                <!-- CSS Builder card -->
                <div class="ls-card">
                    <div class="ls-card-header">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        <span>CSS Builder</span>
                        <span class="ls-card-sub">(fallback when no logo)</span>
                    </div>
                    <div class="ls-card-body">
                        <div class="ls-ctrl-group">
                            <label>Logo Type</label>
                            <select class="ls-select" id="ls-style-select">
                                <?php foreach ($styles as $val => $info): ?>
                                    <option value="<?php echo $val; ?>" <?php selected($current_style, $val); ?>><?php echo esc_html($info['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ls-ctrl-group">
                            <label>Icon</label>
                            <div class="ls-icon-grid" id="ls-icon-grid">
                                <?php
                                $all_icons = bae_get_all_icons();
                                $sli = $p['logo_icon'] ?? '';
                                foreach ($all_icons as $icon_key => $icon_label):
                                    $svg = bae_get_icon_svg_preview($icon_key);
                                ?>
                                <div class="ls-icon-tile <?php echo $sli === $icon_key ? 'selected' : ''; ?>"
                                     data-value="<?php echo esc_attr($icon_key); ?>"
                                     title="<?php echo esc_attr($icon_label); ?>">
                                    <?php echo $svg; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="ls-icon-hidden" value="<?php echo esc_attr($sli); ?>">
                        </div>

                        <div class="ls-ctrl-group">
                            <label>Icon Scale <span id="ls-scale-val"><?php echo (int)($p['logo_icon_scale'] ?? 100); ?>%</span></label>
                            <div class="ls-slider">
                                <input type="range" id="ls-icon-scale" min="60" max="160" step="5" value="<?php echo (int)($p['logo_icon_scale'] ?? 100); ?>">
                            </div>
                        </div>

                        <div class="ls-ctrl-group">
                            <label>Spacing <span id="ls-spacing-val"><?php echo (int)($p['logo_spacing'] ?? 14); ?>px</span></label>
                            <div class="ls-slider">
                                <input type="range" id="ls-spacing" min="6" max="28" step="1" value="<?php echo (int)($p['logo_spacing'] ?? 14); ?>">
                            </div>
                        </div>

                        <div class="ls-ctrl-row">
                            <div class="ls-ctrl-group">
                                <label>Position</label>
                                <select class="ls-select" id="ls-position">
                                    <?php $pos = $p['logo_position'] ?? 'auto'; ?>
                                    <option value="auto" <?php selected($pos, 'auto'); ?>>Auto</option>
                                    <option value="left" <?php selected($pos, 'left'); ?>>Left</option>
                                    <option value="top" <?php selected($pos, 'top'); ?>>Top</option>
                                    <option value="right" <?php selected($pos, 'right'); ?>>Right</option>
                                </select>
                            </div>
                            <div class="ls-ctrl-group">
                                <label>Text Case</label>
                                <select class="ls-select" id="ls-case">
                                    <?php $tc = $p['logo_text_case'] ?? 'default'; ?>
                                    <option value="default" <?php selected($tc, 'default'); ?>>Default</option>
                                    <option value="uppercase" <?php selected($tc, 'uppercase'); ?>>UPPER</option>
                                    <option value="title" <?php selected($tc, 'title'); ?>>Title</option>
                                    <option value="lowercase" <?php selected($tc, 'lowercase'); ?>>lower</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save & Download card -->
                <div class="ls-card">
                    <div class="ls-card-header">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span>Save & Export</span>
                    </div>
                    <div class="ls-card-body">
                        <button class="ls-btn ls-btn-primary ls-full-width" id="ls-save-settings">Save Logo Settings</button>
                        <div class="ls-download-row">
                            <button class="ls-btn ls-btn-outline" id="ls-dl-png">PNG</button>
                            <button class="ls-btn ls-btn-outline" id="ls-dl-svg">SVG</button>
                            <span id="ls-dl-status" class="ls-dl-status"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* ===== Global ===== */
    .bae-logo-studio {
        --ls-bg: #f8f9fc;
        --ls-card: #ffffff;
        --ls-border: rgba(0,0,0,0.08);
        --ls-border2: rgba(0,0,0,0.12);
        --ls-text: #1a1a1a;
        --ls-text2: #4a4a4a;
        --ls-text3: #8a8a8a;
        --ls-accent: #F32D86;
        --ls-accent-s: rgba(243,45,134,0.12);
        font-family: 'DM Sans', system-ui, sans-serif;
        background: var(--ls-bg);
        padding: 24px;
    }
    .bae-wrap:not(.bae-light) .bae-logo-studio {
        --ls-bg: #0f0f15;
        --ls-card: rgba(30,30,40,0.85);
        --ls-border: rgba(255,255,255,0.08);
        --ls-border2: rgba(255,255,255,0.12);
        --ls-text: #ededed;
        --ls-text2: #b0b0b0;
        --ls-text3: #707070;
    }
    .ls-header {
        margin-bottom: 32px;
    }
    .ls-eyebrow {
        font-size: 11px;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--ls-accent);
        font-weight: 600;
    }
    .ls-title {
        font-size: 28px;
        font-weight: 700;
        margin: 4px 0 8px;
        color: var(--ls-text);
    }
    .ls-desc {
        font-size: 14px;
        color: var(--ls-text3);
        max-width: 500px;
    }

    /* 3‑column layout */
    .ls-layout-3col {
        display: flex;
        gap: 28px;
        align-items: flex-start;
        flex-wrap: wrap;
    }
    .ls-left-pods {
        flex: 1.2;
        min-width: 260px;
        background: var(--ls-card);
        border-radius: 20px;
        border: 1px solid var(--ls-border);
        padding: 20px 16px;
    }
    .ls-center-stage {
        flex: 2;
        min-width: 400px;
        background: var(--ls-card);
        border-radius: 32px;
        border: 1px solid var(--ls-border);
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .ls-right-controls {
        flex: 1;
        min-width: 260px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Left pods grid */
    .ls-section-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--ls-text2);
        margin-bottom: 16px;
        padding-left: 4px;
    }
    .ls-pods-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 20px;
        max-height: 480px;
        overflow-y: auto;
    }
    .ls-pod {
        background: var(--ls-bg);
        border-radius: 16px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid var(--ls-border);
        transition: all .15s;
    }
    .ls-pod:hover {
        border-color: var(--ls-accent-s);
        background: var(--ls-accent-s);
    }
    .ls-pod-thumb {
        width: 56px;
        height: 48px;
        background: var(--ls-card);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }
    .ls-pod-info {
        flex: 1;
    }
    .ls-pod-name {
        font-weight: 700;
        font-size: 13px;
        color: var(--ls-text);
    }
    .ls-pod-sub {
        font-size: 10px;
        color: var(--ls-text3);
    }
    .ls-badge {
        font-size: 9px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 40px;
        background: var(--ls-border2);
        color: var(--ls-text3);
    }
    .ls-badge.active {
        background: var(--ls-accent-s);
        color: var(--ls-accent);
    }
    .ls-pod-progress {
        width: 60px;
    }
    .ls-progress-track {
        height: 3px;
        background: var(--ls-border2);
        border-radius: 3px;
        overflow: hidden;
    }
    .ls-progress-fill {
        height: 100%;
        background: var(--ls-accent);
        width: 0%;
        border-radius: 3px;
    }
    .ls-pod-apply {
        background: transparent;
        border: 1px solid var(--ls-border2);
        border-radius: 40px;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 600;
        color: var(--ls-text2);
        cursor: pointer;
        transition: .15s;
    }
    .ls-pod-apply:hover {
        background: var(--ls-accent-s);
        border-color: var(--ls-accent);
        color: var(--ls-accent);
    }

    .ls-metrics-strip {
        display: flex;
        align-items: center;
        justify-content: space-around;
        padding-top: 16px;
        border-top: 1px solid var(--ls-border);
        margin-top: 8px;
    }
    .ls-metric-item {
        text-align: center;
    }
    .ls-metric-val {
        font-size: 20px;
        font-weight: 700;
        color: var(--ls-text);
    }
    .ls-metric-lbl {
        font-size: 10px;
        color: var(--ls-text3);
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .ls-metric-divider {
        width: 1px;
        height: 28px;
        background: var(--ls-border);
    }

    /* Center stage (circular preview) – preserved exactly */
    .ls-topbar {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .ls-mode-tag {
        font-size: 10px;
        font-weight: 600;
        padding: 4px 12px;
        background: var(--ls-accent-s);
        color: var(--ls-accent);
        border-radius: 40px;
    }
    .ls-brand-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--ls-text2);
    }
    .ls-top-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .ls-icon-btn {
        background: none;
        border: 1px solid var(--ls-border2);
        border-radius: 30px;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        color: var(--ls-text2);
    }
    .ls-save-status { font-size: 11px; color: var(--ls-text3); }

    .ls-stage-wrap {
        position: relative;
        width: 380px;
        height: 380px;
        margin: 0 auto;
    }
    .ls-orbit-ring {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        animation: spin 80s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .ls-satellite {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }
    .ls-sat-light { top: 0; left: 0; }
    .ls-sat-dark { top: 0; right: 0; }
    .ls-sat-upload { bottom: 0; left: 0; }
    .ls-sat-checker { bottom: 0; right: 0; }
    .ls-sat-label {
        font-size: 9px;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--ls-text3);
    }
    .ls-sat-preview {
        width: 90px;
        height: 64px;
        border-radius: 16px;
        border: 1px solid var(--ls-border2);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: var(--ls-card);
        cursor: pointer;
    }
    .light-surface { background: #ffffff; }
    .dark-surface { background: #1a1a24; }
    .dark-surface * { color: white !important; }

    .ls-circle-stage {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%,-50%);
        width: 220px;
        height: 220px;
    }
    .ls-tick-ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
    }
    .ls-tick {
        position: absolute;
        top: 0;
        left: 50%;
        width: 1px;
        height: 50%;
        transform-origin: bottom center;
    }
    .ls-tick-inner {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 1px;
        height: 5px;
        background: var(--ls-border2);
    }
    .ls-tick-inner.major { height: 9px; background: var(--ls-text3); }
    .ls-circle-disc {
        width: 190px;
        height: 190px;
        border-radius: 50%;
        background: var(--ls-card);
        border: 1px solid var(--ls-border2);
        box-shadow: 0 0 0 6px var(--ls-bg), 0 0 0 7px var(--ls-border), 0 10px 30px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 15px;
        margin-left: 15px;
    }
    .ls-logo-display {
        max-width: 140px;
        max-height: 110px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .ls-logo-display img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .ls-circle-label {
        font-size: 9px;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--ls-text3);
    }
    .ls-radial-btn {
        position: absolute;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--ls-card);
        border: 1px solid var(--ls-border2);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--ls-text2);
    }
    .ls-rb-top { top: -14px; left: 50%; transform: translateX(-50%); }
    .ls-rb-right { right: -14px; top: 50%; transform: translateY(-50%); }
    .ls-rb-bottom { bottom: -14px; left: 50%; transform: translateX(-50%); }
    .ls-rb-left { left: -14px; top: 50%; transform: translateY(-50%); }
    .ls-mode-dots {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 16px;
    }
    .ls-mode-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--ls-border2);
        cursor: pointer;
    }
    .ls-mode-dot.active { background: var(--ls-accent); transform: scale(1.2); }

    /* Right panel cards */
    .ls-card {
        background: var(--ls-card);
        border-radius: 20px;
        border: 1px solid var(--ls-border);
        overflow: hidden;
    }
    .ls-card-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--ls-border);
        font-weight: 600;
        font-size: 13px;
        color: var(--ls-text);
    }
    .ls-card-sub {
        font-size: 10px;
        font-weight: 400;
        color: var(--ls-text3);
        margin-left: auto;
    }
    .ls-card-body {
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .ls-ctrl-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .ls-ctrl-group label {
        font-size: 11px;
        font-weight: 600;
        color: var(--ls-text2);
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .ls-select {
        background: var(--ls-bg);
        border: 1px solid var(--ls-border2);
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 13px;
        color: var(--ls-text);
    }
    .ls-icon-grid {
        display: grid;
        grid-template-columns: repeat(6,1fr);
        gap: 6px;
        max-height: 120px;
        overflow-y: auto;
        background: var(--ls-bg);
        border-radius: 12px;
        padding: 8px;
    }
    .ls-icon-tile {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid transparent;
        cursor: pointer;
        color: var(--ls-text2);
    }
    .ls-icon-tile.selected { border-color: var(--ls-accent); background: var(--ls-accent-s); color: var(--ls-accent); }
    .ls-slider input {
        width: 100%;
    }
    .ls-ctrl-row {
        display: flex;
        gap: 12px;
    }
    .ls-ctrl-row .ls-ctrl-group { flex: 1; }
    .ls-btn {
        border: none;
        border-radius: 40px;
        padding: 10px 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: .15s;
    }
    .ls-btn-primary {
        background: linear-gradient(135deg, #c4196a, #F32D86);
        color: white;
    }
    .ls-btn-outline {
        background: transparent;
        border: 1px solid var(--ls-border2);
        color: var(--ls-text2);
    }
    .ls-full-width { width: 100%; }
    .ls-download-row {
        display: flex;
        gap: 10px;
        margin-top: 8px;
    }
    .ls-dl-status { font-size: 11px; color: var(--ls-text3); }

    /* Checker panel */
    .ls-checker-panel {
        width: 100%;
        margin-top: 20px;
        background: var(--ls-card);
        border-radius: 16px;
        padding: 16px;
    }
    .ls-checker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px,1fr));
        gap: 10px;
        margin-top: 12px;
    }
    .ls-checker-cell {
        aspect-ratio: 1.4/1;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--ls-border);
    }
    .ls-checker-cell img { max-width: 85%; max-height: 85%; object-fit: contain; }

    @media (max-width: 1000px) {
        .ls-layout-3col { flex-direction: column; }
        .ls-center-stage { order: 1; }
        .ls-left-pods { order: 2; }
        .ls-right-controls { order: 3; }
    }
    </style>

    <script>
    (function(){
        var ajaxurl = window.ajaxurl || '';
        var P = <?php echo wp_json_encode([
            'id'             => (int)($p['id'] ?? 0),
            'business_name'  => $p['business_name'] ?? '',
            'tagline'        => $p['tagline'] ?? '',
            'primary_color'  => $p['primary_color'] ?? '#1a1a2e',
            'secondary_color'=> $p['secondary_color'] ?? '#16213e',
            'accent_color'   => $p['accent_color'] ?? '#e94560',
            'font_heading'   => $p['font_heading'] ?? 'Inter',
            'logo_style'     => $current_style,
            'logo_icon'      => $p['logo_icon'] ?? '',
            'logo_icon_scale'=> (int)($p['logo_icon_scale'] ?? 100),
            'logo_spacing'   => (int)($p['logo_spacing'] ?? 14),
            'logo_position'  => $p['logo_position'] ?? 'auto',
            'logo_text_case' => $p['logo_text_case'] ?? 'default',
            'logo_url'       => $p['logo_url'] ?? '',
            'nonce'          => wp_create_nonce('bae_save_profile'),
        ]); ?>;

        // Helper
        function $(id){ return document.getElementById(id); }

        // Live preview (debounced)
        var previewTimer = null;
        function schedulePreview(){
            clearTimeout(previewTimer);
            previewTimer = setTimeout(doPreview, 200);
        }
        function doPreview(){
            var logoUrl = $('ls-logo-url').value;
            if(logoUrl){
                var lightHtml = '<img src="'+logoUrl+'" style="max-height:44px;max-width:140px;object-fit:contain;">';
                var darkHtml = '<img src="'+logoUrl+'" style="max-height:44px;max-width:140px;object-fit:contain;filter:brightness(0) invert(1);opacity:.9;">';
                if($('ls-preview-light')) $('ls-preview-light').innerHTML = lightHtml;
                if($('ls-preview-dark')) $('ls-preview-dark').innerHTML = darkHtml;
                if($('ls-logo-display')) $('ls-logo-display').innerHTML = '<img src="'+logoUrl+'" style="max-height:110px;max-width:140px;object-fit:contain;">';
                return;
            }
            var fd = new FormData();
            fd.append('action','bae_logo_preview');
            fd.append('nonce', P.nonce);
            fd.append('profile_id', P.id);
            fd.append('logo_style', P.logo_style);
            fd.append('logo_icon', P.logo_icon);
            fd.append('logo_icon_scale', P.logo_icon_scale);
            fd.append('logo_spacing', P.logo_spacing);
            fd.append('logo_position', P.logo_position);
            fd.append('logo_text_case', P.logo_text_case);
            fetch(ajaxurl,{method:'POST',body:fd})
                .then(r=>r.json())
                .then(data=>{
                    if(data.success){
                        if($('ls-preview-light') && data.data.light) $('ls-preview-light').innerHTML = data.data.light;
                        if($('ls-preview-dark') && data.data.dark) $('ls-preview-dark').innerHTML = data.data.dark;
                        if($('ls-logo-display') && data.data.light) $('ls-logo-display').innerHTML = data.data.light;
                    }
                });
        }

        // Bind CSS controls
        var styleSelect = $('ls-style-select');
        var posSelect = $('ls-position');
        var caseSelect = $('ls-case');
        var scaleRange = $('ls-icon-scale');
        var spacingRange = $('ls-spacing');
        function updateRange(which, valSpanId, unit){
            var val = which.value;
            $(valSpanId).innerText = val + unit;
            P[which.id === 'ls-icon-scale' ? 'logo_icon_scale' : 'logo_spacing'] = parseInt(val,10);
            schedulePreview();
        }
        if(scaleRange) scaleRange.addEventListener('input', function(){ updateRange(this,'ls-scale-val','%'); });
        if(spacingRange) spacingRange.addEventListener('input', function(){ updateRange(this,'ls-spacing-val','px'); });
        if(styleSelect) styleSelect.addEventListener('change', function(){ P.logo_style = this.value; $('ls-circle-status').innerText = this.value; schedulePreview(); });
        if(posSelect) posSelect.addEventListener('change', function(){ P.logo_position = this.value; schedulePreview(); });
        if(caseSelect) caseSelect.addEventListener('change', function(){ P.logo_text_case = this.value; schedulePreview(); });

        // Icon picker
        document.querySelectorAll('.ls-icon-tile').forEach(function(tile){
            tile.addEventListener('click', function(){
                document.querySelectorAll('.ls-icon-tile').forEach(t=>t.classList.remove('selected'));
                this.classList.add('selected');
                P.logo_icon = this.dataset.value;
                $('ls-icon-hidden').value = P.logo_icon;
                schedulePreview();
            });
        });

        // Save settings
        var saveBtn = $('ls-save-settings');
        var saveStatus = $('ls-save-status');
        function doSave(extra){
            if(saveBtn) saveBtn.disabled = true;
            var fd = new FormData();
            fd.append('action','bae_save_profile');
            fd.append('nonce', P.nonce);
            fd.append('profile_id', P.id);
            fd.append('logo_style', P.logo_style);
            fd.append('logo_icon', P.logo_icon);
            fd.append('logo_icon_scale', P.logo_icon_scale);
            fd.append('logo_spacing', P.logo_spacing);
            fd.append('logo_position', P.logo_position);
            fd.append('logo_text_case', P.logo_text_case);
            fd.append('logo_url', $('ls-logo-url').value);
            fd.append('business_name', P.business_name);
            fd.append('tagline', P.tagline);
            fd.append('primary_color', P.primary_color);
            fd.append('secondary_color', P.secondary_color);
            fd.append('accent_color', P.accent_color);
            fd.append('font_heading', P.font_heading);
            if(extra) Object.keys(extra).forEach(k=>fd.append(k,extra[k]));
            fetch(ajaxurl,{method:'POST',body:fd})
                .then(r=>r.json())
                .then(data=>{
                    if(saveBtn) saveBtn.disabled = false;
                    if(saveStatus){
                        saveStatus.textContent = data.success ? '✓ Saved' : 'Failed';
                        saveStatus.style.color = data.success ? '#34d399' : '#fb7185';
                        setTimeout(()=>{ saveStatus.textContent=''; }, 3000);
                    }
                    if(data.success && typeof window.baeToast === 'function') window.baeToast('Logo settings saved.','success');
                });
        }
        if(saveBtn) saveBtn.addEventListener('click', ()=>doSave());

        // File upload
        var uploadArea = $('ls-upload-sat');
        var fileInput = $('ls-file-input');
        var uploadStatus = $('ls-upload-status');
        function uploadFile(file){
            if(!file) return;
            var allowed = ['image/png','image/jpeg','image/jpg','image/svg+xml','image/gif','image/webp'];
            if(!allowed.includes(file.type)){ if(uploadStatus){uploadStatus.textContent='PNG/JPG/SVG only';uploadStatus.style.color='#fb7185';} return; }
            if(file.size>2*1024*1024){ if(uploadStatus){uploadStatus.textContent='Max 2MB';uploadStatus.style.color='#fb7185';} return; }
            if(uploadStatus){ uploadStatus.textContent='Uploading...'; uploadStatus.style.color='var(--ls-text3)'; }
            var fd = new FormData();
            fd.append('action','bae_upload_logo');
            fd.append('nonce', P.nonce);
            fd.append('logo_file', file);
            if(P.id) fd.append('profile_id', P.id);
            fetch(ajaxurl,{method:'POST',body:fd})
                .then(r=>r.json())
                .then(data=>{
                    if(data.success && data.data && data.data.url){
                        var url = data.data.url;
                        $('ls-logo-url').value = url;
                        P.logo_url = url;
                        if($('ls-upload-sat')) $('ls-upload-sat').innerHTML = '<img src="'+url+'" style="max-width:90%;max-height:90%;object-fit:contain;"><input type="file" id="ls-file-input" accept="image/*" style="display:none">';
                        if($('ls-mode-tag')) $('ls-mode-tag').textContent = 'Image Mode';
                        doPreview();
                        doSave({logo_url:url});
                        if(typeof window.baeToast === 'function') window.baeToast('Logo uploaded.','success');
                    } else {
                        if(uploadStatus) uploadStatus.textContent = (data.data && data.data.message) || 'Upload failed';
                    }
                }).catch(()=>{ if(uploadStatus) uploadStatus.textContent='Network error'; });
        }
        if(fileInput) fileInput.addEventListener('change', function(){ uploadFile(this.files[0]); });
        if(uploadArea){
            uploadArea.addEventListener('dragover', e=>{ e.preventDefault(); uploadArea.classList.add('drag-over'); });
            uploadArea.addEventListener('dragleave', ()=>uploadArea.classList.remove('drag-over'));
            uploadArea.addEventListener('drop', e=>{ e.preventDefault(); uploadArea.classList.remove('drag-over'); uploadFile(e.dataTransfer.files[0]); });
        }

        // Remove logo
        var removeBtn = $('ls-remove-logo');
        if(removeBtn){
            removeBtn.addEventListener('click', function(){
                $('ls-logo-url').value = '';
                P.logo_url = '';
                if($('ls-upload-sat')) $('ls-upload-sat').innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg><span id="ls-upload-hint">Drop or click</span><input type="file" id="ls-file-input" accept="image/*" style="display:none">';
                if($('ls-mode-tag')) $('ls-mode-tag').textContent = 'CSS Builder';
                doPreview();
                doSave({logo_url:''});
            });
        }

        // Radial button actions
        var rbRefresh = $('ls-rb-refresh'); if(rbRefresh) rbRefresh.addEventListener('click', doPreview);
        var rbRemove = $('ls-rb-remove'); if(rbRemove) rbRemove.addEventListener('click', ()=>removeBtn.click());
        var rbExport = $('ls-rb-export'); if(rbExport) rbExport.addEventListener('click', ()=>doDownload('png'));
        var rbCheck = $('ls-rb-check'); if(rbCheck) rbCheck.addEventListener('click', toggleChecker);
        var checkerBtn = $('ls-checker-btn'); if(checkerBtn) checkerBtn.addEventListener('click', toggleChecker);

        function toggleChecker(){
            var panel = $('ls-checker-panel');
            if(!panel) return;
            if(panel.style.display === 'none'){
                panel.style.display = 'block';
                buildChecker();
            } else {
                panel.style.display = 'none';
            }
        }
        function buildChecker(){
            var grid = $('ls-checker-grid');
            if(!grid || grid.children.length > 0) return;
            var logoUrl = $('ls-logo-url').value;
            var colors = ['#ffffff','#f8f9fc','#1a1a24','#F32D86','#2d1066','#1d3a6b','#c2410c'];
            colors.forEach(function(bg){
                var cell = document.createElement('div');
                cell.className = 'ls-checker-cell';
                cell.style.background = bg;
                if(logoUrl){
                    cell.innerHTML = '<img src="'+logoUrl+'">';
                } else {
                    var lockup = $('ls-logo-display');
                    if(lockup) cell.innerHTML = lockup.innerHTML;
                }
                grid.appendChild(cell);
            });
        }

        // Download
        function doDownload(fmt){
            var status = $('ls-dl-status');
            if(status) status.textContent = 'Preparing...';
            var logoUrl = $('ls-logo-url').value;
            var name = P.business_name || 'logo';
            if(logoUrl && fmt === 'png'){
                var a = document.createElement('a'); a.href = logoUrl; a.download = name+'.png'; a.click();
                if(status){ status.textContent = '✓ Done'; setTimeout(()=> status.textContent='',2500); }
                return;
            }
            var lockup = $('ls-logo-display');
            if(!lockup){ if(status) status.textContent = 'Nothing to export.'; return; }
            if(fmt === 'svg'){
                var svgStr = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="100"><foreignObject width="400" height="100"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:12px;display:flex;align-items:center;">'+lockup.innerHTML+'</body></foreignObject></svg>';
                var blob = new Blob([svgStr],{type:'image/svg+xml'});
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a'); a.href=url; a.download=name+'.svg'; a.click();
                setTimeout(()=>URL.revokeObjectURL(url),2000);
            } else {
                var canvas = document.createElement('canvas'); canvas.width=800; canvas.height=200;
                var ctx = canvas.getContext('2d'); ctx.fillStyle='#ffffff'; ctx.fillRect(0,0,800,200);
                var svgBlob = new Blob(['<svg xmlns="http://www.w3.org/2000/svg" width="800" height="200"><foreignObject width="800" height="200"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:24px;display:flex;align-items:center;zoom:2;">'+lockup.innerHTML+'</body></foreignObject></svg>'],{type:'image/svg+xml'});
                var burl = URL.createObjectURL(svgBlob);
                var img = new Image(); img.crossOrigin='anonymous';
                img.onload = function(){
                    ctx.drawImage(img,0,0); URL.revokeObjectURL(burl);
                    var a = document.createElement('a'); a.download=name+'.png'; a.href=canvas.toDataURL('image/png'); a.click();
                    if(status){ status.textContent='✓ Done'; setTimeout(()=> status.textContent='',2500); }
                };
                img.onerror = function(){ if(status){ status.textContent='Try SVG instead'; } };
                img.src = burl;
            }
            if(status){ setTimeout(()=> status.textContent='',2600); }
        }
        var dlPng = $('ls-dl-png'); if(dlPng) dlPng.addEventListener('click', ()=>doDownload('png'));
        var dlSvg = $('ls-dl-svg'); if(dlSvg) dlSvg.addEventListener('click', ()=>doDownload('svg'));
        var dlPngTop = $('ls-download-png'); if(dlPngTop) dlPngTop.addEventListener('click', ()=>doDownload('png'));
        var dlSvgTop = $('ls-download-svg'); if(dlSvgTop) dlSvgTop.addEventListener('click', ()=>doDownload('svg'));

        // Apply pod style
        document.querySelectorAll('.ls-pod-apply').forEach(function(btn){
            btn.addEventListener('click', function(){
                var style = this.dataset.style;
                if(styleSelect) styleSelect.value = style;
                if(styleSelect) styleSelect.dispatchEvent(new Event('change'));
                // Update active badge in pods
                document.querySelectorAll('.ls-pod').forEach(function(pod){
                    var isActive = pod.dataset.style === style;
                    var badge = pod.querySelector('.ls-badge');
                    if(badge){
                        badge.className = 'ls-badge ' + (isActive ? 'active' : 'inactive');
                        badge.textContent = isActive ? 'Active' : 'Inactive';
                    }
                    var fill = pod.querySelector('.ls-progress-fill');
                    if(fill) fill.style.width = isActive ? '100%' : '0%';
                    var applyBtn = pod.querySelector('.ls-pod-apply');
                    if(applyBtn) applyBtn.textContent = isActive ? 'Applied' : 'Apply';
                });
                if(typeof window.baeToast === 'function') window.baeToast('Applied '+style+' style', 'success');
            });
        });

        // Mode dots (cosmetic)
        document.querySelectorAll('.ls-mode-dot').forEach(function(dot){
            dot.addEventListener('click', function(){
                document.querySelectorAll('.ls-mode-dot').forEach(d=>d.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Tick ring rotation
        var tickRing = $('ls-tick-ring');
        if(tickRing){
            var angle = 0;
            setInterval(function(){
                angle += 0.2;
                tickRing.style.transform = 'rotate('+angle+'deg)';
            }, 50);
        }
    })();
    </script>
    <?php
    return bae_wrap_tab_panel(ob_get_clean());
}
