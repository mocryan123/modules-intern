<?php if (!defined('ABSPATH')) exit;
function bae_logo_tab($user_id, $profile) {
    if (empty($profile)) {
        return '<div style="padding:60px;text-align:center;color:var(--text-3);font-family:\'DM Sans\',sans-serif;">Complete your Brand Identity first to access Logo Studio.</div>';
    }
    $p = $profile;
    ob_start();
    ?>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">

    <div class="ls-root" id="ls-root">

        <!-- ═══════════════════════════════════════════
             LEFT PANEL — Variants List
        ═══════════════════════════════════════════ -->
        <aside class="ls-left">
            <div class="ls-left-header">
                <span class="ls-eyebrow">Studio</span>
                <h2 class="ls-title">Logo Variants</h2>
            </div>

            <div class="ls-variant-list" id="ls-variant-list">
                <?php
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
                $idx = 0;
                foreach ($styles as $key => $info):
                    $is_active = ($current_style === $key);
                    $idx++;
                ?>
                <div class="ls-variant-row <?php echo $is_active ? 'is-active' : ''; ?>"
                     data-style="<?php echo esc_attr($key); ?>"
                     style="animation-delay:<?php echo $idx * 0.04; ?>s">
                    <div class="ls-variant-thumb">
                        <?php if (!empty($p['logo_url'])): ?>
                            <img src="<?php echo esc_url($p['logo_url']); ?>" alt="">
                        <?php else: ?>
                            <span class="ls-thumb-letter"><?php echo strtoupper(substr($p['business_name']??'B',0,1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ls-variant-info">
                        <div class="ls-variant-name"><?php echo esc_html($info['label']); ?></div>
                        <div class="ls-variant-sub"><?php echo esc_html($info['sub']); ?></div>
                    </div>
                    <div class="ls-variant-meta">
                        <?php if ($is_active): ?>
                            <span class="ls-active-pill">Active</span>
                        <?php else: ?>
                            <button class="ls-apply-btn" data-style="<?php echo esc_attr($key); ?>">Apply</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Stats strip -->
            <div class="ls-stats-strip">
                <div class="ls-stat">
                    <div class="ls-stat-num"><?php echo count($styles); ?></div>
                    <div class="ls-stat-lbl">Styles</div>
                </div>
                <div class="ls-stat-divider"></div>
                <div class="ls-stat">
                    <div class="ls-stat-num"><?php echo !empty($p['logo_url']) ? '1' : '0'; ?></div>
                    <div class="ls-stat-lbl">Uploaded</div>
                </div>
                <div class="ls-stat-divider"></div>
                <div class="ls-stat">
                    <div class="ls-stat-num">2</div>
                    <div class="ls-stat-lbl">Previews</div>
                </div>
            </div>
        </aside>

        <!-- ═══════════════════════════════════════════
             CENTER — Circular Stage
        ═══════════════════════════════════════════ -->
        <main class="ls-center">

            <!-- Top action bar -->
            <div class="ls-topbar">
                <div class="ls-topbar-left">
                    <span class="ls-mode-tag" id="ls-mode-tag">
                        <?php echo !empty($p['logo_url']) ? 'Image Mode' : 'CSS Builder'; ?>
                    </span>
                </div>
                <div class="ls-topbar-center">
                    <span class="ls-brand-name"><?php echo esc_html($p['business_name'] ?? 'Your Brand'); ?></span>
                </div>
                <div class="ls-topbar-right">
                    <button class="ls-icon-btn" id="ls-download-png" title="Export PNG">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </button>
                    <button class="ls-icon-btn" id="ls-download-svg" title="Export SVG">SVG</button>
                    <button class="ls-icon-btn" id="ls-save-btn" title="Save">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    </button>
                    <span class="ls-save-status" id="ls-save-status"></span>
                </div>
            </div>

            <!-- Orbital Stage -->
            <div class="ls-stage-wrap">

                <!-- Outer orbit ring (decorative dashes) -->
                <svg class="ls-orbit-ring" viewBox="0 0 560 560" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="280" cy="280" r="270" fill="none" stroke="currentColor" stroke-width="0.5" stroke-dasharray="4 8" opacity="0.15"/>
                    <circle cx="280" cy="280" r="230" fill="none" stroke="currentColor" stroke-width="0.5" stroke-dasharray="2 12" opacity="0.1"/>
                </svg>

                <!-- Satellite: Light preview (top-left orbit) -->
                <div class="ls-satellite ls-sat-light" id="ls-sat-light">
                    <div class="ls-sat-label">Light</div>
                    <div class="ls-sat-preview light-surface" id="ls-preview-light">
                        <?php echo bae_render_logo_lockup($p, ['dark'=>false]); ?>
                    </div>
                </div>

                <!-- Satellite: Dark preview (top-right orbit) -->
                <div class="ls-satellite ls-sat-dark" id="ls-sat-dark">
                    <div class="ls-sat-label">Dark</div>
                    <div class="ls-sat-preview dark-surface" id="ls-preview-dark">
                        <?php echo bae_render_logo_lockup($p, ['dark'=>true]); ?>
                    </div>
                </div>

                <!-- Satellite: Upload (bottom-left) -->
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
                    <span class="ls-sat-label" id="ls-upload-status" style="margin-top:6px;"></span>
                </div>

                <!-- Satellite: Checker (bottom-right) -->
                <div class="ls-satellite ls-sat-checker">
                    <div class="ls-sat-label">BG Check</div>
                    <button class="ls-sat-preview ls-checker-sat" id="ls-checker-btn" title="Background Checker">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    </button>
                </div>

                <!-- THE BIG CIRCLE (Main Logo Stage) -->
                <div class="ls-circle-stage" id="ls-circle-stage">
                    <!-- Tick ring -->
                    <div class="ls-tick-ring" id="ls-tick-ring">
                        <?php for ($i = 0; $i < 60; $i++): ?>
                        <div class="ls-tick" style="transform:rotate(<?php echo $i * 6; ?>deg)">
                            <div class="ls-tick-inner <?php echo ($i % 5 === 0) ? 'major' : ''; ?>"></div>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Inner disc -->
                    <div class="ls-circle-disc">
                        <!-- Logo display area -->
                        <div class="ls-logo-display" id="ls-logo-display">
                            <?php if (!empty($p['logo_url'])): ?>
                                <img src="<?php echo esc_url($p['logo_url']); ?>" alt="logo" id="ls-main-logo-img">
                            <?php else: ?>
                                <div id="ls-logo-lockup"><?php echo bae_render_logo_lockup($p, ['dark'=>false]); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Status label inside circle -->
                        <div class="ls-circle-label">
                            <span id="ls-circle-status"><?php echo esc_html($current_style); ?></span>
                        </div>
                    </div>

                    <!-- Radial controls (action buttons on circle edge) -->
                    <button class="ls-radial-btn ls-rb-top"    id="ls-rb-refresh" title="Refresh preview">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    </button>
                    <button class="ls-radial-btn ls-rb-right"  id="ls-rb-check"   title="Check backgrounds">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </button>
                    <button class="ls-radial-btn ls-rb-bottom" id="ls-rb-remove"  title="Remove logo">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                    </button>
                    <button class="ls-radial-btn ls-rb-left"   id="ls-rb-export"  title="Export">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </button>
                </div>

                <!-- Bottom mode selector dots -->
                <div class="ls-mode-dots">
                    <button class="ls-mode-dot active" data-mode="light"  title="Light preview"></button>
                    <button class="ls-mode-dot"        data-mode="dark"   title="Dark preview"></button>
                    <button class="ls-mode-dot"        data-mode="upload" title="Upload"></button>
                    <button class="ls-mode-dot"        data-mode="check"  title="BG check"></button>
                    <button class="ls-mode-dot"        data-mode="export" title="Export"></button>
                </div>
            </div>

            <!-- Background Checker panel (shown below stage) -->
            <div class="ls-checker-panel" id="ls-checker-panel" style="display:none">
                <div class="ls-checker-label">Background compatibility</div>
                <div class="ls-checker-grid" id="ls-checker-grid"></div>
            </div>
        </main>

        <!-- ═══════════════════════════════════════════
             RIGHT PANEL — CSS Builder Controls
        ═══════════════════════════════════════════ -->
        <aside class="ls-right">
            <div class="ls-right-header">
                <span class="ls-eyebrow">Controls</span>
                <h2 class="ls-title">CSS Builder</h2>
                <p class="ls-right-sub">Fallback when no logo uploaded</p>
            </div>

            <!-- Style selector -->
            <div class="ls-ctrl-group">
                <label class="ls-ctrl-label">Logo Type</label>
                <select class="ls-select" id="ls-style-select" name="logo_style">
                    <?php
                    foreach ($styles as $val => $info):
                        $sls = $p['logo_style'] ?? 'wordmark';
                    ?>
                        <option value="<?php echo $val; ?>" <?php selected($sls, $val); ?>><?php echo esc_html($info['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Icon picker -->
            <div class="ls-ctrl-group">
                <label class="ls-ctrl-label">Icon</label>
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
                <input type="hidden" id="ls-icon-hidden" name="logo_icon" value="<?php echo esc_attr($sli); ?>">
            </div>

            <!-- Sliders -->
            <div class="ls-ctrl-group">
                <label class="ls-ctrl-label">
                    Icon Scale
                    <span class="ls-ctrl-val" id="ls-scale-val"><?php echo (int)($p['logo_icon_scale'] ?? 100); ?>%</span>
                </label>
                <div class="ls-slider-track">
                    <input type="range" class="ls-range" id="ls-icon-scale" name="logo_icon_scale"
                           min="60" max="160" step="5"
                           value="<?php echo (int)($p['logo_icon_scale'] ?? 100); ?>">
                    <div class="ls-range-fill" id="ls-scale-fill"></div>
                </div>
            </div>

            <div class="ls-ctrl-group">
                <label class="ls-ctrl-label">
                    Spacing
                    <span class="ls-ctrl-val" id="ls-spacing-val"><?php echo (int)($p['logo_spacing'] ?? 14); ?>px</span>
                </label>
                <div class="ls-slider-track">
                    <input type="range" class="ls-range" id="ls-spacing" name="logo_spacing"
                           min="6" max="28" step="1"
                           value="<?php echo (int)($p['logo_spacing'] ?? 14); ?>">
                    <div class="ls-range-fill" id="ls-spacing-fill"></div>
                </div>
            </div>

            <!-- Selects row -->
            <div class="ls-ctrl-row">
                <div class="ls-ctrl-group ls-ctrl-half">
                    <label class="ls-ctrl-label">Position</label>
                    <select class="ls-select" id="ls-position" name="logo_position">
                        <?php $pos = $p['logo_position'] ?? 'auto'; ?>
                        <option value="auto"  <?php selected($pos,'auto'); ?>>Auto</option>
                        <option value="left"  <?php selected($pos,'left'); ?>>Left</option>
                        <option value="top"   <?php selected($pos,'top'); ?>>Top</option>
                        <option value="right" <?php selected($pos,'right'); ?>>Right</option>
                    </select>
                </div>
                <div class="ls-ctrl-group ls-ctrl-half">
                    <label class="ls-ctrl-label">Text Case</label>
                    <select class="ls-select" id="ls-case" name="logo_text_case">
                        <?php $tc = $p['logo_text_case'] ?? 'default'; ?>
                        <option value="default"   <?php selected($tc,'default'); ?>>Default</option>
                        <option value="uppercase" <?php selected($tc,'uppercase'); ?>>UPPER</option>
                        <option value="title"     <?php selected($tc,'title'); ?>>Title</option>
                        <option value="lowercase" <?php selected($tc,'lowercase'); ?>>lower</option>
                    </select>
                </div>
            </div>

            <!-- Right panel stats / metrics -->
            <div class="ls-metrics">
                <div class="ls-metric-card">
                    <div class="ls-metric-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M20.188 10.934A8.001 8.001 0 1 1 3.811 13.066"/></svg>
                    </div>
                    <div>
                        <div class="ls-metric-num" id="ls-metric-style"><?php echo esc_html(ucfirst($current_style)); ?></div>
                        <div class="ls-metric-lbl">Active Style</div>
                    </div>
                </div>
                <div class="ls-metric-card">
                    <div class="ls-metric-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    </div>
                    <div>
                        <div class="ls-metric-num"><?php echo !empty($p['logo_url']) ? 'Yes' : 'No'; ?></div>
                        <div class="ls-metric-lbl">Logo Uploaded</div>
                    </div>
                </div>
            </div>

            <!-- Save button -->
            <button class="ls-save-btn" id="ls-save-main">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Logo Settings
            </button>

            <!-- Download group -->
            <div class="ls-dl-row">
                <button class="ls-dl-btn" id="ls-dl-png">PNG</button>
                <button class="ls-dl-btn" id="ls-dl-svg">SVG</button>
                <span class="ls-dl-status" id="ls-dl-status"></span>
            </div>

        </aside>

        <!-- Hidden state fields -->
        <input type="hidden" id="ls-logo-url" value="<?php echo esc_attr($p['logo_url'] ?? ''); ?>">
    </div>

    <!-- ═══════════════ STYLES ═══════════════ -->
    <style>
    /* ─── Root / tokens ─── */
    .ls-root *,
    .ls-root *::before,
    .ls-root *::after { box-sizing: border-box; margin: 0; padding: 0; }

    .ls-root {
        --ls-bg:       #f0eeea;
        --ls-surface:  #f7f6f3;
        --ls-card:     #ffffff;
        --ls-border:   rgba(0,0,0,0.08);
        --ls-border2:  rgba(0,0,0,0.14);
        --ls-text:     #1a1916;
        --ls-text2:    #6b6960;
        --ls-text3:    #a09e99;
        --ls-accent:   #F32D86;
        --ls-accent-s: rgba(243,45,134,0.12);
        --ls-shadow:   0 2px 12px rgba(0,0,0,0.06);
        --ls-shadow-lg:0 8px 32px rgba(0,0,0,0.10);
        --ls-r:        20px;
        --ls-r-sm:     12px;
        --ls-font:     'DM Sans', system-ui, sans-serif;
        --ls-mono:     'DM Mono', monospace;

        font-family: var(--ls-font);
        background: var(--ls-bg);
        display: grid;
        grid-template-columns: 300px 1fr 280px;
        min-height: 100vh;
        gap: 0;
    }

    /* ─── Left Panel ─── */
    .ls-left {
        background: var(--ls-card);
        border-right: 1px solid var(--ls-border);
        display: flex;
        flex-direction: column;
        padding: 32px 0 24px;
    }
    .ls-left-header {
        padding: 0 24px 24px;
        border-bottom: 1px solid var(--ls-border);
    }
    .ls-eyebrow {
        font-family: var(--ls-mono);
        font-size: 10px;
        letter-spacing: .16em;
        text-transform: uppercase;
        color: var(--ls-accent);
        display: block;
        margin-bottom: 6px;
    }
    .ls-title {
        font-size: 20px;
        font-weight: 600;
        color: var(--ls-text);
        letter-spacing: -.02em;
    }

    .ls-variant-list {
        flex: 1;
        overflow-y: auto;
        padding: 16px 0;
        scrollbar-width: none;
    }
    .ls-variant-list::-webkit-scrollbar { display: none; }

    .ls-variant-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 24px;
        cursor: pointer;
        transition: background .15s;
        opacity: 0;
        animation: ls-fadein .3s ease forwards;
    }
    @keyframes ls-fadein { from{opacity:0;transform:translateX(-8px)} to{opacity:1;transform:none} }

    .ls-variant-row:hover { background: var(--ls-surface); }
    .ls-variant-row.is-active { background: rgba(243,45,134,0.06); border-right: 2px solid var(--ls-accent); }

    .ls-variant-thumb {
        width: 44px;
        height: 36px;
        background: var(--ls-surface);
        border-radius: var(--ls-r-sm);
        border: 1px solid var(--ls-border);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }
    .ls-variant-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .ls-thumb-letter {
        font-size: 14px;
        font-weight: 600;
        color: var(--ls-accent);
        font-family: var(--ls-font);
    }
    .ls-variant-info { flex: 1; min-width: 0; }
    .ls-variant-name { font-size: 13px; font-weight: 600; color: var(--ls-text); }
    .ls-variant-sub  { font-size: 11px; color: var(--ls-text3); margin-top: 2px; }
    .ls-variant-meta { flex-shrink: 0; }

    .ls-active-pill {
        font-size: 10px;
        font-weight: 600;
        font-family: var(--ls-mono);
        letter-spacing: .05em;
        background: var(--ls-accent-s);
        color: var(--ls-accent);
        padding: 3px 9px;
        border-radius: 40px;
    }
    .ls-apply-btn {
        font-size: 11px;
        font-weight: 500;
        font-family: var(--ls-font);
        background: transparent;
        border: 1px solid var(--ls-border2);
        border-radius: 40px;
        padding: 4px 12px;
        color: var(--ls-text2);
        cursor: pointer;
        transition: all .15s;
    }
    .ls-apply-btn:hover { background: var(--ls-accent-s); border-color: var(--ls-accent); color: var(--ls-accent); }

    .ls-stats-strip {
        padding: 20px 24px;
        border-top: 1px solid var(--ls-border);
        display: flex;
        align-items: center;
        gap: 0;
    }
    .ls-stat { flex: 1; text-align: center; }
    .ls-stat-num { font-size: 22px; font-weight: 600; color: var(--ls-text); letter-spacing: -.03em; }
    .ls-stat-lbl { font-size: 10px; font-family: var(--ls-mono); color: var(--ls-text3); text-transform: uppercase; letter-spacing: .08em; margin-top: 2px; }
    .ls-stat-divider { width: 1px; height: 32px; background: var(--ls-border); }

    /* ─── Center ─── */
    .ls-center {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 28px 24px 32px;
        gap: 20px;
        position: relative;
        overflow: hidden;
    }
    .ls-center::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 60% 50% at 50% 40%, rgba(243,45,134,0.04) 0%, transparent 70%);
        pointer-events: none;
    }

    /* Topbar */
    .ls-topbar {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }
    .ls-topbar-center {
        font-size: 13px;
        font-weight: 600;
        color: var(--ls-text2);
        letter-spacing: .02em;
        font-family: var(--ls-mono);
    }
    .ls-topbar-left, .ls-topbar-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .ls-mode-tag {
        font-family: var(--ls-mono);
        font-size: 10px;
        letter-spacing: .1em;
        text-transform: uppercase;
        background: var(--ls-accent-s);
        color: var(--ls-accent);
        padding: 5px 12px;
        border-radius: 40px;
    }
    .ls-icon-btn {
        width: 34px;
        height: 34px;
        background: var(--ls-card);
        border: 1px solid var(--ls-border2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--ls-text2);
        font-size: 11px;
        font-weight: 600;
        font-family: var(--ls-mono);
        transition: all .15s;
    }
    .ls-icon-btn:hover { background: var(--ls-accent-s); border-color: var(--ls-accent); color: var(--ls-accent); }
    .ls-save-status {
        font-size: 11px;
        font-family: var(--ls-mono);
        color: var(--ls-text3);
        min-width: 48px;
    }

    /* Stage wrap */
    .ls-stage-wrap {
        position: relative;
        width: 480px;
        height: 480px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Orbit SVG */
    .ls-orbit-ring {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        color: var(--ls-text);
        pointer-events: none;
        animation: ls-orbit-spin 80s linear infinite;
    }
    @keyframes ls-orbit-spin { to { transform: rotate(360deg); } }

    /* Satellites */
    .ls-satellite {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        animation: ls-float 4s ease-in-out infinite;
    }
    .ls-sat-light  { top: 12px;  left: 20px;  animation-delay: 0s; }
    .ls-sat-dark   { top: 12px;  right: 20px; animation-delay: .8s; }
    .ls-sat-upload { bottom: 30px; left: 20px; animation-delay: 1.6s; }
    .ls-sat-checker{ bottom: 30px; right:20px; animation-delay: 2.4s; }

    @keyframes ls-float {
        0%,100% { transform: translateY(0px); }
        50%      { transform: translateY(-5px); }
    }

    .ls-sat-label {
        font-size: 10px;
        font-family: var(--ls-mono);
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--ls-text3);
    }
    .ls-sat-preview {
        width: 100px;
        height: 70px;
        border-radius: var(--ls-r);
        border: 1px solid var(--ls-border2);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        cursor: pointer;
        transition: all .2s;
        flex-shrink: 0;
    }
    .ls-sat-preview:hover { transform: scale(1.04); box-shadow: var(--ls-shadow-lg); border-color: rgba(243,45,134,.3); }
    .light-surface { background: #ffffff; }
    .dark-surface  { background: #0e0d14; }
    .dark-surface * { color: #ffffff !important; }

    .ls-upload-sat {
        background: var(--ls-surface);
        flex-direction: column;
        gap: 4px;
        color: var(--ls-text3);
    }
    .ls-upload-sat img { max-width: 90%; max-height: 90%; object-fit: contain; }
    #ls-upload-hint { font-size: 9px; font-family: var(--ls-mono); color: var(--ls-text3); letter-spacing:.06em; }

    .ls-checker-sat {
        background: var(--ls-surface);
        color: var(--ls-text3);
        transition: all .2s;
    }
    .ls-checker-sat:hover { background: var(--ls-accent-s); color: var(--ls-accent); border-color: var(--ls-accent); }

    /* ─── Main Circle ─── */
    .ls-circle-stage {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%,-50%);
        width: 280px;
        height: 280px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Tick ring */
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
        border-radius: 2px;
    }
    .ls-tick-inner.major {
        height: 9px;
        width: 1.5px;
        background: var(--ls-text3);
    }

    /* Inner disc */
    .ls-circle-disc {
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: var(--ls-card);
        border: 1px solid var(--ls-border2);
        box-shadow: 0 0 0 8px var(--ls-bg), 0 0 0 9px var(--ls-border), var(--ls-shadow-lg);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        overflow: hidden;
        position: relative;
        transition: box-shadow .3s;
    }
    .ls-circle-disc:hover { box-shadow: 0 0 0 8px var(--ls-bg), 0 0 0 9px rgba(243,45,134,.3), 0 16px 48px rgba(243,45,134,.12); }

    .ls-logo-display {
        display: flex;
        align-items: center;
        justify-content: center;
        max-width: 180px;
        max-height: 140px;
        overflow: hidden;
    }
    .ls-logo-display img { max-width: 180px; max-height: 130px; object-fit: contain; }

    .ls-circle-label {
        position: absolute;
        bottom: 28px;
    }
    #ls-circle-status {
        font-family: var(--ls-mono);
        font-size: 10px;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--ls-text3);
    }

    /* Radial buttons */
    .ls-radial-btn {
        position: absolute;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: var(--ls-card);
        border: 1px solid var(--ls-border2);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--ls-text2);
        transition: all .2s;
        box-shadow: var(--ls-shadow);
    }
    .ls-radial-btn:hover { background: var(--ls-accent-s); border-color: var(--ls-accent); color: var(--ls-accent); transform: scale(1.1); }
    .ls-rb-top    { top: -15px;    left: 50%; transform: translateX(-50%); }
    .ls-rb-right  { right: -15px;  top: 50%;  transform: translateY(-50%); }
    .ls-rb-bottom { bottom: -15px; left: 50%; transform: translateX(-50%); }
    .ls-rb-left   { left: -15px;   top: 50%;  transform: translateY(-50%); }
    .ls-rb-top:hover    { transform: translateX(-50%) scale(1.1); }
    .ls-rb-right:hover  { transform: translateY(-50%) scale(1.1); }
    .ls-rb-bottom:hover { transform: translateX(-50%) scale(1.1); }
    .ls-rb-left:hover   { transform: translateY(-50%) scale(1.1); }

    /* Mode dots */
    .ls-mode-dots {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: center;
        margin-top: 8px;
    }
    .ls-mode-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: var(--ls-border2);
        border: none;
        cursor: pointer;
        transition: all .2s;
    }
    .ls-mode-dot.active { background: var(--ls-accent); transform: scale(1.3); }
    .ls-mode-dot:hover  { background: var(--ls-text3); }

    /* BG Checker panel */
    .ls-checker-panel {
        width: 100%;
        background: var(--ls-card);
        border-radius: var(--ls-r);
        border: 1px solid var(--ls-border);
        padding: 20px 24px;
        animation: ls-fadein .2s ease;
    }
    .ls-checker-label {
        font-size: 11px;
        font-family: var(--ls-mono);
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--ls-text3);
        margin-bottom: 14px;
    }
    .ls-checker-grid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 8px;
    }
    .ls-checker-cell {
        aspect-ratio: 1.4/1;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .ls-checker-cell img { max-width: 85%; max-height: 85%; object-fit: contain; }

    /* ─── Right Panel ─── */
    .ls-right {
        background: var(--ls-card);
        border-left: 1px solid var(--ls-border);
        padding: 32px 24px;
        display: flex;
        flex-direction: column;
        gap: 0;
        overflow-y: auto;
        scrollbar-width: none;
    }
    .ls-right::-webkit-scrollbar { display:none; }
    .ls-right-header { margin-bottom: 24px; }
    .ls-right-sub { font-size: 12px; color: var(--ls-text3); margin-top: 4px; }

    .ls-ctrl-group { margin-bottom: 20px; }
    .ls-ctrl-label {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        font-weight: 600;
        font-family: var(--ls-mono);
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--ls-text2);
        margin-bottom: 8px;
    }
    .ls-ctrl-val { font-weight: 400; color: var(--ls-accent); }

    .ls-select {
        width: 100%;
        background: var(--ls-surface);
        border: 1px solid var(--ls-border2);
        border-radius: 10px;
        padding: 9px 12px;
        font-family: var(--ls-font);
        font-size: 13px;
        color: var(--ls-text);
        cursor: pointer;
        transition: border-color .15s;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg width='12' height='12' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='m6 9 6 6 6-6' fill='none' stroke='%23a09e99' stroke-width='2'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 32px;
    }
    .ls-select:focus { outline: none; border-color: var(--ls-accent); }

    /* Icon grid */
    .ls-icon-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
        max-height: 140px;
        overflow-y: auto;
        background: var(--ls-surface);
        border-radius: 12px;
        padding: 8px;
        border: 1px solid var(--ls-border2);
        scrollbar-width: none;
    }
    .ls-icon-grid::-webkit-scrollbar { display:none; }
    .ls-icon-tile {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        border: 1px solid transparent;
        background: var(--ls-card);
        cursor: pointer;
        transition: all .12s;
        color: var(--ls-text2);
    }
    .ls-icon-tile:hover  { border-color: rgba(243,45,134,.4); background: var(--ls-accent-s); color: var(--ls-accent); }
    .ls-icon-tile.selected { border-color: var(--ls-accent); background: var(--ls-accent-s); color: var(--ls-accent); }

    /* Range sliders */
    .ls-slider-track { position: relative; }
    .ls-range {
        width: 100%;
        height: 4px;
        appearance: none;
        background: var(--ls-border2);
        border-radius: 4px;
        outline: none;
        cursor: pointer;
    }
    .ls-range::-webkit-slider-thumb {
        appearance: none;
        width: 16px; height: 16px;
        border-radius: 50%;
        background: var(--ls-accent);
        border: 2px solid white;
        box-shadow: 0 1px 4px rgba(243,45,134,.3);
        cursor: pointer;
    }
    .ls-range::-moz-range-thumb {
        width: 16px; height: 16px;
        border-radius: 50%;
        background: var(--ls-accent);
        border: 2px solid white;
        box-shadow: 0 1px 4px rgba(243,45,134,.3);
        cursor: pointer;
    }

    /* Controls row */
    .ls-ctrl-row { display: flex; gap: 12px; }
    .ls-ctrl-half { flex: 1; }

    /* Metrics */
    .ls-metrics {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 20px;
    }
    .ls-metric-card {
        background: var(--ls-surface);
        border-radius: 12px;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid var(--ls-border);
    }
    .ls-metric-icon {
        width: 32px; height: 32px;
        background: var(--ls-accent-s);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--ls-accent);
        flex-shrink: 0;
    }
    .ls-metric-num { font-size: 14px; font-weight: 600; color: var(--ls-text); }
    .ls-metric-lbl { font-size: 10px; font-family: var(--ls-mono); color: var(--ls-text3); margin-top: 1px; text-transform: uppercase; letter-spacing: .06em; }

    /* Save button */
    .ls-save-btn {
        width: 100%;
        background: var(--ls-text);
        color: #fff;
        border: none;
        border-radius: 12px;
        padding: 13px 20px;
        font-family: var(--ls-font);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all .2s;
        margin-bottom: 12px;
        letter-spacing: .01em;
    }
    .ls-save-btn:hover { background: var(--ls-accent); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(243,45,134,.25); }
    .ls-save-btn:active { transform: none; }
    .ls-save-btn:disabled { opacity:.5; cursor:not-allowed; transform:none; box-shadow:none; }

    /* Download row */
    .ls-dl-row {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .ls-dl-btn {
        flex: 1;
        background: var(--ls-surface);
        border: 1px solid var(--ls-border2);
        border-radius: 10px;
        padding: 8px;
        font-family: var(--ls-mono);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .08em;
        color: var(--ls-text2);
        cursor: pointer;
        transition: all .15s;
    }
    .ls-dl-btn:hover { background: var(--ls-accent-s); border-color: var(--ls-accent); color: var(--ls-accent); }
    .ls-dl-status { font-size: 11px; font-family: var(--ls-mono); color: var(--ls-text3); }

    /* Drag-over state */
    .ls-upload-sat.drag-over { border-color: var(--ls-accent); background: var(--ls-accent-s); }

    /* Responsive */
    @media (max-width: 1100px) {
        .ls-root { grid-template-columns: 260px 1fr 240px; }
        .ls-stage-wrap { width: 400px; height: 400px; }
        .ls-circle-stage { width: 240px; height: 240px; }
        .ls-circle-disc  { width: 200px; height: 200px; }
    }
    @media (max-width: 860px) {
        .ls-root {
            grid-template-columns: 1fr;
            grid-template-rows: auto auto auto;
        }
        .ls-left, .ls-right { border: none; border-bottom: 1px solid var(--ls-border); }
        .ls-stage-wrap { width: 340px; height: 340px; }
        .ls-circle-stage { width: 200px; height: 200px; }
        .ls-circle-disc  { width: 170px; height: 170px; }
        .ls-sat-preview  { width: 76px; height: 54px; }
    }
    </style>

    <!-- ═══════════════ SCRIPT ═══════════════ -->
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
        'font_heading'   => $p['font_heading'] ?? 'DM Sans',
        'logo_style'     => $p['logo_style'] ?? 'wordmark',
        'logo_icon'      => $p['logo_icon'] ?? '',
        'logo_icon_scale'=> (int)($p['logo_icon_scale'] ?? 100),
        'logo_spacing'   => (int)($p['logo_spacing'] ?? 14),
        'logo_position'  => $p['logo_position'] ?? 'auto',
        'logo_text_case' => $p['logo_text_case'] ?? 'default',
        'logo_url'       => $p['logo_url'] ?? '',
        'nonce'          => wp_create_nonce('bae_save_profile'),
    ]); ?>;

    /* ── helpers ── */
    function $(id){ return document.getElementById(id); }
    function qsa(sel){ return document.querySelectorAll(sel); }

    /* ── Icon picker ── */
    qsa('.ls-icon-tile').forEach(function(tile){
        tile.addEventListener('click', function(){
            qsa('.ls-icon-tile').forEach(function(t){ t.classList.remove('selected'); });
            tile.classList.add('selected');
            P.logo_icon = tile.dataset.value || '';
            $('ls-icon-hidden').value = P.logo_icon;
            schedulePreview();
        });
    });

    /* ── Control bindings ── */
    function bindSelect(id, key){
        var el = $(id); if(!el) return;
        el.addEventListener('change', function(){
            P[key] = this.value;
            if(key === 'logo_style'){
                $('ls-circle-status').textContent = this.value;
                $('ls-metric-style').textContent = this.value.charAt(0).toUpperCase() + this.value.slice(1);
                syncVariantList(this.value);
            }
            schedulePreview();
        });
    }
    bindSelect('ls-style-select','logo_style');
    bindSelect('ls-position','logo_position');
    bindSelect('ls-case','logo_text_case');

    function bindRange(id, key, unit){
        var el = $(id); if(!el) return;
        el.addEventListener('input', function(){
            P[key] = parseInt(this.value, 10);
            var valEl = $(id === 'ls-icon-scale' ? 'ls-scale-val' : 'ls-spacing-val');
            if(valEl) valEl.textContent = this.value + unit;
            schedulePreview();
        });
    }
    bindRange('ls-icon-scale','logo_icon_scale','%');
    bindRange('ls-spacing','logo_spacing','px');

    /* ── Preview debounce ── */
    var previewTimer = null;
    function schedulePreview(){ clearTimeout(previewTimer); previewTimer = setTimeout(doPreview, 220); }

    function doPreview(){
        var logoUrl = $('ls-logo-url').value;
        if(logoUrl){
            var light = '<img src="'+logoUrl+'" style="max-height:44px;max-width:140px;object-fit:contain;" alt="logo">';
            var dark  = '<img src="'+logoUrl+'" style="max-height:44px;max-width:140px;object-fit:contain;filter:brightness(0) invert(1);opacity:.9;" alt="logo">';
            var lLight = $('ls-preview-light'); var lDark = $('ls-preview-dark'); var lMain = $('ls-logo-display');
            if(lLight) lLight.innerHTML = light;
            if(lDark) lDark.innerHTML = dark;
            if(lMain) lMain.innerHTML = '<img src="'+logoUrl+'" style="max-height:130px;max-width:180px;object-fit:contain;" alt="logo">';
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
            .then(function(r){return r.json();})
            .then(function(data){
                if(data.success){
                    if($('ls-preview-light') && data.data.light) $('ls-preview-light').innerHTML = data.data.light;
                    if($('ls-preview-dark') && data.data.dark)   $('ls-preview-dark').innerHTML  = data.data.dark;
                    if($('ls-logo-display') && data.data.light)  $('ls-logo-display').innerHTML  = data.data.light;
                }
            }).catch(function(){});
    }

    /* ── Variant list sync ── */
    function syncVariantList(style){
        qsa('.ls-variant-row').forEach(function(row){
            var rowStyle = row.dataset.style;
            var meta = row.querySelector('.ls-variant-meta');
            row.classList.toggle('is-active', rowStyle === style);
            if(rowStyle === style){
                meta.innerHTML = '<span class="ls-active-pill">Active</span>';
            } else {
                meta.innerHTML = '<button class="ls-apply-btn" data-style="'+rowStyle+'">Apply</button>';
                meta.querySelector('.ls-apply-btn').addEventListener('click', applyVariant);
            }
        });
    }

    function applyVariant(e){
        var style = e.currentTarget.dataset.style;
        var sel = $('ls-style-select');
        if(sel){ sel.value = style; sel.dispatchEvent(new Event('change')); }
    }

    qsa('.ls-apply-btn').forEach(function(btn){ btn.addEventListener('click', applyVariant); });

    /* ── Save ── */
    function doSave(extra){
        var saveBtn = $('ls-save-main'); var topSave = $('ls-save-btn');
        var status  = $('ls-save-status');
        if(saveBtn){ saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }
        if(topSave){ topSave.disabled = true; }
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
        if(extra) Object.keys(extra).forEach(function(k){ fd.append(k,extra[k]); });
        fetch(ajaxurl,{method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(data){
                if(saveBtn){ saveBtn.disabled=false; saveBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Logo Settings'; }
                if(topSave){ topSave.disabled=false; }
                if(status){
                    status.textContent = data.success ? '✓ Saved' : 'Failed';
                    status.style.color = data.success ? '#34d399' : '#fb7185';
                    setTimeout(function(){ if(status) status.textContent=''; }, 3000);
                }
                if(data.success && typeof window.baeToast === 'function') window.baeToast('Logo settings saved.','success');
            })
            .catch(function(){
                if(saveBtn){ saveBtn.disabled=false; saveBtn.textContent='Save Logo Settings'; }
                if(status){ status.textContent='Network error'; status.style.color='#fb7185'; }
            });
    }

    var saveMain = $('ls-save-main'); if(saveMain) saveMain.addEventListener('click', function(){ doSave(); });
    var saveTop  = $('ls-save-btn');  if(saveTop)  saveTop.addEventListener('click',  function(){ doSave(); });

    /* ── File upload ── */
    function uploadFile(file){
        if(!file) return;
        var allowed = ['image/png','image/jpeg','image/jpg','image/svg+xml','image/gif','image/webp'];
        if(!allowed.includes(file.type)){ setUploadStatus('PNG/JPG/SVG only','#fb7185'); return; }
        if(file.size > 2*1024*1024){ setUploadStatus('Max 2MB','#fb7185'); return; }
        setUploadStatus('Uploading…','var(--ls-text3)');
        var fd = new FormData();
        fd.append('action','bae_upload_logo');
        fd.append('nonce', P.nonce);
        fd.append('logo_file', file);
        if(P.id) fd.append('profile_id', P.id);
        fetch(ajaxurl,{method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(data){
                if(data.success && data.data && data.data.url){
                    var url = data.data.url;
                    $('ls-logo-url').value = url;
                    P.logo_url = url;
                    // Update sat upload
                    var sat = $('ls-upload-sat');
                    if(sat){ sat.innerHTML = '<img id="ls-upload-img" src="'+url+'" style="max-width:90%;max-height:90%;object-fit:contain;"><input type="file" id="ls-file-input" accept="image/*" style="display:none">'; rebindFile(); }
                    // Update mode tag
                    var mt = $('ls-mode-tag'); if(mt) mt.textContent = 'Image Mode';
                    setUploadStatus('✓ Uploaded','#34d399');
                    doPreview();
                    doSave({logo_url:url});
                    if(typeof window.baeToast === 'function') window.baeToast('Logo uploaded.','success');
                } else {
                    setUploadStatus((data.data&&data.data.message)||'Upload failed','#fb7185');
                }
            }).catch(function(){ setUploadStatus('Network error','#fb7185'); });
    }

    function setUploadStatus(msg, color){
        var el = $('ls-upload-status'); if(!el) return;
        el.textContent = msg; el.style.color = color;
        if(msg && msg.startsWith('✓')) setTimeout(function(){ el.textContent=''; }, 3000);
    }

    function rebindFile(){
        var fi = $('ls-file-input'); if(fi) fi.addEventListener('change', function(){ uploadFile(this.files[0]); });
        var sat = $('ls-upload-sat');
        if(sat){
            sat.addEventListener('dragover', function(e){ e.preventDefault(); this.classList.add('drag-over'); });
            sat.addEventListener('dragleave', function(){ this.classList.remove('drag-over'); });
            sat.addEventListener('drop', function(e){ e.preventDefault(); this.classList.remove('drag-over'); uploadFile(e.dataTransfer.files&&e.dataTransfer.files[0]); });
        }
    }
    rebindFile();

    /* ── Radial buttons ── */
    var rbRefresh = $('ls-rb-refresh'); if(rbRefresh) rbRefresh.addEventListener('click', doPreview);
    var rbRemove  = $('ls-rb-remove');
    if(rbRemove) rbRemove.addEventListener('click', function(){
        $('ls-logo-url').value = ''; P.logo_url = '';
        var sat = $('ls-upload-sat');
        if(sat){ sat.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg><span id="ls-upload-hint" style="font-size:9px;font-family:var(--ls-mono);color:var(--ls-text3);">Drop or click</span><input type="file" id="ls-file-input" accept="image/*" style="display:none">'; rebindFile(); }
        var mt = $('ls-mode-tag'); if(mt) mt.textContent = 'CSS Builder';
        doPreview();
        doSave({logo_url:''});
    });

    var rbCheck = $('ls-rb-check');
    if(rbCheck) rbCheck.addEventListener('click', function(){ toggleChecker(); });

    var rbExport = $('ls-rb-export');
    if(rbExport) rbExport.addEventListener('click', function(){ doDownload('png'); });

    /* ── Checker ── */
    var checkerOpen = false;
    function toggleChecker(){
        var panel = $('ls-checker-panel'); if(!panel) return;
        checkerOpen = !checkerOpen;
        panel.style.display = checkerOpen ? 'block' : 'none';
        if(checkerOpen){ buildChecker(); }
    }
    var checkerBtn = $('ls-checker-btn');
    if(checkerBtn) checkerBtn.addEventListener('click', toggleChecker);

    function buildChecker(){
        var grid = $('ls-checker-grid'); if(!grid || grid.children.length > 0) return;
        var logoUrl = $('ls-logo-url').value;
        var lockup  = $('ls-logo-display');
        var colors  = ['#ffffff','#f0eeea','#1a1916','#0f0e17','#F32D86','#2d1066','#1d3a6b','#c2410c'];
        colors.forEach(function(bg){
            var cell = document.createElement('div');
            cell.className = 'ls-checker-cell';
            cell.style.background = bg;
            if(logoUrl){
                cell.innerHTML = '<img src="'+logoUrl+'" style="max-width:85%;max-height:85%;object-fit:contain;">';
            } else if(lockup){
                cell.innerHTML = lockup.innerHTML;
            }
            grid.appendChild(cell);
        });
    }

    /* ── Downloads ── */
    function doDownload(fmt){
        var status = $('ls-dl-status'); if(status) status.textContent = 'Preparing…';
        var logoUrl = $('ls-logo-url').value;
        var name = P.business_name || 'logo';
        if(logoUrl && fmt === 'png'){
            var a = document.createElement('a'); a.href = logoUrl; a.download = name+'.png'; a.click();
            if(status){ status.textContent = '✓ Done'; setTimeout(function(){ status.textContent=''; }, 2500); }
            return;
        }
        var lockup = $('ls-logo-display'); if(!lockup){ if(status) status.textContent='Nothing to export.'; return; }
        if(fmt === 'svg'){
            var svgStr = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="100"><foreignObject width="400" height="100"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:12px;display:flex;align-items:center;">'+lockup.innerHTML+'</body></foreignObject></svg>';
            var blob = new Blob([svgStr],{type:'image/svg+xml'});
            var url  = URL.createObjectURL(blob);
            var a = document.createElement('a'); a.href=url; a.download=name+'.svg'; a.click();
            setTimeout(function(){ URL.revokeObjectURL(url); },2000);
        } else {
            var canvas = document.createElement('canvas'); canvas.width=800; canvas.height=200;
            var ctx = canvas.getContext('2d'); ctx.fillStyle='#ffffff'; ctx.fillRect(0,0,800,200);
            var svgBlob = new Blob(['<svg xmlns="http://www.w3.org/2000/svg" width="800" height="200"><foreignObject width="800" height="200"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:24px;display:flex;align-items:center;zoom:2;">'+lockup.innerHTML+'</body></foreignObject></svg>'],{type:'image/svg+xml'});
            var burl = URL.createObjectURL(svgBlob);
            var img  = new Image(); img.crossOrigin='anonymous';
            img.onload = function(){
                ctx.drawImage(img,0,0); URL.revokeObjectURL(burl);
                var a = document.createElement('a'); a.download=name+'.png'; a.href=canvas.toDataURL('image/png'); a.click();
                if(status){ status.textContent='✓ Done'; setTimeout(function(){ status.textContent=''; },2500); }
            };
            img.onerror = function(){ if(status){ status.textContent='Try SVG instead'; status.style.color='#fb7185'; } };
            img.src = burl;
        }
        if(status){ setTimeout(function(){ if(status) status.textContent=''; }, 2600); }
    }

    var dlPng = $('ls-dl-png');    if(dlPng) dlPng.addEventListener('click', function(){ doDownload('png'); });
    var dlSvg = $('ls-dl-svg');    if(dlSvg) dlSvg.addEventListener('click', function(){ doDownload('svg'); });
    var dlPngTop = $('ls-download-png'); if(dlPngTop) dlPngTop.addEventListener('click', function(){ doDownload('png'); });
    var dlSvgTop = $('ls-download-svg'); if(dlSvgTop) dlSvgTop.addEventListener('click', function(){ doDownload('svg'); });

    /* ── Mode dots (cosmetic) ── */
    qsa('.ls-mode-dot').forEach(function(dot){
        dot.addEventListener('click', function(){
            qsa('.ls-mode-dot').forEach(function(d){ d.classList.remove('active'); });
            dot.classList.add('active');
        });
    });

    /* ── Initial tick ring animation ── */
    var tickRing = $('ls-tick-ring');
    if(tickRing){
        var tickAngle = 0;
        setInterval(function(){
            tickAngle += 0.2;
            tickRing.style.transform = 'rotate('+tickAngle+'deg)';
        }, 50);
    }

    })();
    </script>
    <?php
    return bae_wrap_tab_panel(ob_get_clean());
}
