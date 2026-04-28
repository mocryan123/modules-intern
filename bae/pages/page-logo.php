<?php if (!defined('ABSPATH')) exit;
function bae_logo_tab($user_id, $profile) {
    if (empty($profile)) {
        return '<div class="bae-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/><path d="M12 6v6l4 2"/></svg><strong>No brand profile yet.</strong><p>Complete your <a href="?tab=overview">Brand Profile</a> first.</p></div>';
    }
    $p = $profile;
    $styles = [
        'wordmark'    => ['label' => 'Wordmark',    'sub' => 'Name as logo'],
        'lettermark'  => ['label' => 'Lettermark',  'sub' => 'Initials only'],
        'combination' => ['label' => 'Combination', 'sub' => 'Icon + Name'],
        'emblem'      => ['label' => 'Emblem',      'sub' => 'Icon in badge'],
        'monogram'    => ['label' => 'Monogram',    'sub' => 'Stylised initials'],
        'abstract'    => ['label' => 'Abstract',    'sub' => 'Icon only'],
        'badge'       => ['label' => 'Badge',       'sub' => 'Circular seal'],
        'stacked'     => ['label' => 'Stacked',     'sub' => 'Icon above name'],
        'outlined'    => ['label' => 'Outlined',    'sub' => 'Name with border'],
        'minimal'     => ['label' => 'Minimal',     'sub' => 'Initials + dot'],
    ];
    $current_style = $p['logo_style'] ?? 'wordmark';
    $logo_url      = $p['logo_url'] ?? '';
    $biz_name      = $p['business_name'] ?? 'Your Brand';
    $first_letter  = strtoupper(substr($biz_name, 0, 1));
    $updated       = !empty($p['updated_at']) ? date('M d, Y', strtotime($p['updated_at'])) : '—';

    ob_start();
?>
<div class="ls">

    <!-- ══════════ LEFT — Variants ══════════ -->
    <aside class="ls-left">
        <div class="ls-panel-head">
            <span class="ls-eyebrow">Studio</span>
            <div class="ls-panel-title">Logo Variants</div>
            <div class="ls-panel-meta"><?php echo count($styles); ?> styles available</div>
        </div>

        <div class="ls-variants">
            <?php foreach ($styles as $key => $info):
                $active = ($current_style === $key);
                $pp = $p; $pp['logo_style'] = $key;
            ?>
            <div class="ls-variant<?php echo $active ? ' is-active' : ''; ?>" data-style="<?php echo esc_attr($key); ?>">
                <div class="ls-vthumb">
                    <?php if (!empty($logo_url)): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="">
                    <?php else: ?>
                        <span class="ls-vthumb-letter"><?php echo $first_letter; ?></span>
                    <?php endif; ?>
                </div>
                <div class="ls-vinfo">
                    <div class="ls-vname"><?php echo esc_html($info['label']); ?></div>
                    <div class="ls-vsub"><?php echo esc_html($info['sub']); ?></div>
                </div>
                <?php if ($active): ?>
                    <span class="ls-pill ls-pill-active">Active</span>
                <?php else: ?>
                    <button class="ls-apply" data-style="<?php echo esc_attr($key); ?>">Apply</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="ls-left-footer">
            <div class="ls-foot-stat">
                <div class="ls-foot-num"><?php echo count($styles); ?></div>
                <div class="ls-foot-lbl">Styles</div>
            </div>
            <div class="ls-foot-div"></div>
            <div class="ls-foot-stat">
                <div class="ls-foot-num"><?php echo !empty($logo_url) ? '1' : '0'; ?></div>
                <div class="ls-foot-lbl">Uploaded</div>
            </div>
            <div class="ls-foot-div"></div>
            <div class="ls-foot-stat">
                <div class="ls-foot-num"><?php echo !empty($p['logo_url']) ? 'Yes' : 'No'; ?></div>
                <div class="ls-foot-lbl">Image</div>
            </div>
        </div>
    </aside>

    <!-- ══════════ CENTER — Stage ══════════ -->
    <main class="ls-center">

        <!-- Topbar -->
        <div class="ls-topbar">
            <span class="ls-tag" id="ls-mode-tag"><?php echo !empty($logo_url) ? 'Image Mode' : 'CSS Builder'; ?></span>
            <span class="ls-topbar-name"><?php echo esc_html($biz_name); ?></span>
            <div class="ls-topbar-actions">
                <button class="ls-ibtn" id="ls-save-btn" title="Save">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                </button>
                <button class="ls-ibtn" id="ls-top-png" title="Export PNG">PNG</button>
                <button class="ls-ibtn" id="ls-top-svg" title="Export SVG">SVG</button>
                <span class="ls-save-status" id="ls-save-status"></span>
            </div>
        </div>

        <!-- Orbital Stage -->
        <div class="ls-stage">

            <!-- Decorative orbit rings -->
            <svg class="ls-orbits" viewBox="0 0 500 500">
                <circle cx="250" cy="250" r="240" fill="none" stroke="currentColor" stroke-width="0.6" stroke-dasharray="3 9" opacity="0.12"/>
                <circle cx="250" cy="250" r="200" fill="none" stroke="currentColor" stroke-width="0.4" stroke-dasharray="1 11" opacity="0.08"/>
            </svg>

            <!-- Satellite: Light preview -->
            <div class="ls-sat ls-sat-tl">
                <div class="ls-sat-lbl">Light</div>
                <div class="ls-sat-card ls-sat-light" id="ls-preview-light"><?php echo bae_render_logo_lockup($p, ['dark' => false]); ?></div>
            </div>

            <!-- Satellite: Dark preview -->
            <div class="ls-sat ls-sat-tr">
                <div class="ls-sat-lbl">Dark</div>
                <div class="ls-sat-card ls-sat-dark" id="ls-preview-dark"><?php echo bae_render_logo_lockup($p, ['dark' => true]); ?></div>
            </div>

            <!-- Satellite: Upload -->
            <div class="ls-sat ls-sat-bl">
                <div class="ls-sat-lbl">Upload</div>
                <label class="ls-sat-card ls-sat-upload" id="ls-upload-sat" title="Click or drag a logo here">
                    <?php if (!empty($logo_url)): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="logo">
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.45"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>Drop / click</span>
                    <?php endif; ?>
                    <input type="file" id="ls-file-input" accept="image/*" style="display:none">
                </label>
                <span class="ls-sat-status" id="ls-upload-status"></span>
            </div>

            <!-- Satellite: BG Check -->
            <div class="ls-sat ls-sat-br">
                <div class="ls-sat-lbl">BG Check</div>
                <button class="ls-sat-card ls-sat-check" id="ls-checker-btn" title="Check logo on backgrounds">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                </button>
            </div>

            <!-- Main circle -->
            <div class="ls-circle" id="ls-circle">
                <!-- Tick ring -->
                <div class="ls-ticks" id="ls-ticks">
                    <?php for ($i = 0; $i < 60; $i++): ?>
                    <div class="ls-tick" style="transform:rotate(<?php echo $i * 6; ?>deg)">
                        <div class="ls-tick-line <?php echo ($i % 5 === 0) ? 'major' : ''; ?>"></div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Inner disc -->
                <div class="ls-disc">
                    <div class="ls-logo-area" id="ls-logo-area">
                        <?php if (!empty($logo_url)): ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="logo">
                        <?php else: ?>
                            <?php echo bae_render_logo_lockup($p, ['dark' => false]); ?>
                        <?php endif; ?>
                    </div>
                    <div class="ls-disc-status" id="ls-circle-status"><?php echo esc_html($current_style); ?></div>
                </div>

                <!-- Cardinal action buttons -->
                <button class="ls-rb ls-rb-n" id="ls-rb-refresh" title="Refresh preview">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                </button>
                <button class="ls-rb ls-rb-e" id="ls-rb-check" title="BG check">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
                <button class="ls-rb ls-rb-s" id="ls-rb-remove" title="Remove logo">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                </button>
                <button class="ls-rb ls-rb-w" id="ls-rb-export" title="Export PNG">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </button>
            </div>

        </div><!-- /ls-stage -->

        <!-- Mode indicator dots -->
        <div class="ls-dots">
            <button class="ls-dot active" data-mode="light"  title="Light"></button>
            <button class="ls-dot"        data-mode="dark"   title="Dark"></button>
            <button class="ls-dot"        data-mode="upload" title="Upload"></button>
            <button class="ls-dot"        data-mode="check"  title="BG check"></button>
        </div>

        <!-- Background checker panel (hidden by default) -->
        <div class="ls-checker-panel" id="ls-checker-panel" style="display:none">
            <div class="ls-checker-head">Background compatibility</div>
            <div class="ls-checker-grid" id="ls-checker-grid"></div>
        </div>

    </main><!-- /ls-center -->

    <!-- ══════════ RIGHT — Controls ══════════ -->
    <aside class="ls-right">

        <div class="ls-panel-head">
            <span class="ls-eyebrow">Controls</span>
            <div class="ls-panel-title">CSS Builder</div>
            <div class="ls-panel-meta">Fallback when no logo uploaded</div>
        </div>

        <!-- Logo Type -->
        <div class="ls-field">
            <label class="ls-label">Logo Type</label>
            <select class="ls-select" id="ls-style-select">
                <?php foreach ($styles as $val => $info): ?>
                    <option value="<?php echo $val; ?>" <?php selected($current_style, $val); ?>><?php echo esc_html($info['label']); ?> — <?php echo esc_html($info['sub']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Icon picker -->
        <div class="ls-field">
            <label class="ls-label">Icon</label>
            <div class="ls-icon-grid" id="ls-icon-grid">
                <?php
                $all_icons = bae_get_all_icons();
                $sel_icon  = $p['logo_icon'] ?? '';
                foreach ($all_icons as $icon_key => $icon_label):
                    $svg = bae_get_icon_svg_preview($icon_key);
                ?>
                <div class="ls-icon<?php echo $sel_icon === $icon_key ? ' selected' : ''; ?>"
                     data-value="<?php echo esc_attr($icon_key); ?>"
                     title="<?php echo esc_attr($icon_label); ?>"><?php echo $svg; ?></div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" id="ls-icon-hidden" value="<?php echo esc_attr($sel_icon); ?>">
        </div>

        <!-- Icon Scale -->
        <div class="ls-field">
            <label class="ls-label ls-label-row">
                Icon Scale
                <span class="ls-val" id="ls-scale-val"><?php echo (int)($p['logo_icon_scale'] ?? 100); ?>%</span>
            </label>
            <input type="range" class="ls-range" id="ls-icon-scale"
                   min="60" max="160" step="5" value="<?php echo (int)($p['logo_icon_scale'] ?? 100); ?>">
        </div>

        <!-- Spacing -->
        <div class="ls-field">
            <label class="ls-label ls-label-row">
                Spacing
                <span class="ls-val" id="ls-spacing-val"><?php echo (int)($p['logo_spacing'] ?? 14); ?>px</span>
            </label>
            <input type="range" class="ls-range" id="ls-spacing"
                   min="6" max="28" step="1" value="<?php echo (int)($p['logo_spacing'] ?? 14); ?>">
        </div>

        <!-- Position & Case row -->
        <div class="ls-field-row">
            <div class="ls-field ls-field-half">
                <label class="ls-label">Position</label>
                <select class="ls-select" id="ls-position">
                    <?php $pos = $p['logo_position'] ?? 'auto'; ?>
                    <option value="auto"  <?php selected($pos,'auto'); ?>>Auto</option>
                    <option value="left"  <?php selected($pos,'left'); ?>>Left</option>
                    <option value="top"   <?php selected($pos,'top'); ?>>Top</option>
                    <option value="right" <?php selected($pos,'right'); ?>>Right</option>
                </select>
            </div>
            <div class="ls-field ls-field-half">
                <label class="ls-label">Text Case</label>
                <select class="ls-select" id="ls-case">
                    <?php $tc = $p['logo_text_case'] ?? 'default'; ?>
                    <option value="default"   <?php selected($tc,'default'); ?>>Default</option>
                    <option value="uppercase" <?php selected($tc,'uppercase'); ?>>UPPER</option>
                    <option value="title"     <?php selected($tc,'title'); ?>>Title</option>
                    <option value="lowercase" <?php selected($tc,'lowercase'); ?>>lower</option>
                </select>
            </div>
        </div>

        <!-- Metrics -->
        <div class="ls-metrics">
            <div class="ls-metric">
                <div class="ls-metric-ico">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M20.188 10.934A8.001 8.001 0 1 1 3.811 13.066"/></svg>
                </div>
                <div>
                    <div class="ls-metric-val" id="ls-metric-style"><?php echo esc_html(ucfirst($current_style)); ?></div>
                    <div class="ls-metric-lbl">Active Style</div>
                </div>
            </div>
            <div class="ls-metric">
                <div class="ls-metric-ico">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                </div>
                <div>
                    <div class="ls-metric-val"><?php echo !empty($logo_url) ? 'Yes' : 'No'; ?></div>
                    <div class="ls-metric-lbl">Logo Uploaded</div>
                </div>
            </div>
            <div class="ls-metric">
                <div class="ls-metric-ico">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <div class="ls-metric-val"><?php echo $updated; ?></div>
                    <div class="ls-metric-lbl">Last Updated</div>
                </div>
            </div>
        </div>

        <!-- Save -->
        <button class="ls-save-btn" id="ls-save-main">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Logo Settings
        </button>

        <!-- Export -->
        <div class="ls-export-row">
            <button class="ls-exp-btn" id="ls-dl-png">PNG</button>
            <button class="ls-exp-btn" id="ls-dl-svg">SVG</button>
            <span class="ls-exp-status" id="ls-dl-status"></span>
        </div>

    </aside>

    <!-- Hidden logo URL field -->
    <input type="hidden" id="ls-logo-url" value="<?php echo esc_attr($logo_url); ?>">

</div><!-- /ls -->

<!-- ════════════ STYLES ════════════ -->
<style>
/* ── Reset & tokens ── */
.ls,
.ls *,
.ls *::before,
.ls *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
.ls {
    --c-bg:      #f2f0ec;
    --c-surface: #f9f8f5;
    --c-card:    #ffffff;
    --c-bd:      rgba(0,0,0,.08);
    --c-bd2:     rgba(0,0,0,.13);
    --c-tx:      #1b1916;
    --c-tx2:     #6a6860;
    --c-tx3:     #aaa89f;
    --c-acc:     #F32D86;
    --c-acc-a:   rgba(243,45,134,.11);
    --c-shd:     0 2px 12px rgba(0,0,0,.06);
    --c-shd-lg:  0 8px 32px rgba(0,0,0,.10);
    --r:         18px;
    --r-sm:      10px;
    --font:      'DM Sans', system-ui, sans-serif;
    --mono:      'DM Mono', 'Fira Mono', monospace;

    font-family: var(--font);
    display: grid;
    grid-template-columns: 280px 1fr 264px;
    min-height: 100vh;
    background: var(--c-bg);
}

/* ────────────────────────────────────
   SHARED
───────────────────────────────────── */
.ls-eyebrow {
    display: block;
    font-family: var(--mono);
    font-size: 9.5px;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: var(--c-acc);
    margin-bottom: 5px;
}
.ls-panel-head {
    padding: 28px 22px 20px;
    border-bottom: 1px solid var(--c-bd);
}
.ls-panel-title {
    font-size: 17px;
    font-weight: 600;
    letter-spacing: -.02em;
    color: var(--c-tx);
    margin-bottom: 2px;
}
.ls-panel-meta {
    font-size: 11px;
    color: var(--c-tx3);
}
.ls-pill-active {
    font-size: 9px;
    font-weight: 600;
    font-family: var(--mono);
    letter-spacing: .05em;
    background: var(--c-acc-a);
    color: var(--c-acc);
    padding: 3px 9px;
    border-radius: 40px;
    white-space: nowrap;
}

/* ────────────────────────────────────
   LEFT PANEL
───────────────────────────────────── */
.ls-left {
    background: var(--c-card);
    border-right: 1px solid var(--c-bd);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.ls-variants {
    flex: 1;
    overflow-y: auto;
    padding: 10px 0;
    scrollbar-width: thin;
    scrollbar-color: var(--c-bd2) transparent;
}
.ls-variant {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 22px;
    cursor: pointer;
    transition: background .12s;
    border-right: 2px solid transparent;
}
.ls-variant:hover { background: var(--c-surface); }
.ls-variant.is-active {
    background: var(--c-acc-a);
    border-right-color: var(--c-acc);
}
.ls-vthumb {
    width: 42px;
    height: 34px;
    border-radius: 9px;
    background: var(--c-surface);
    border: 1px solid var(--c-bd);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.ls-vthumb img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.ls-vthumb-letter {
    font-size: 13px;
    font-weight: 700;
    color: var(--c-acc);
}
.ls-vinfo { flex: 1; min-width: 0; }
.ls-vname { font-size: 12.5px; font-weight: 600; color: var(--c-tx); }
.ls-vsub  { font-size: 10px;   color: var(--c-tx3); margin-top: 1px; }
.ls-apply {
    font-size: 10.5px;
    font-family: var(--font);
    background: transparent;
    border: 1px solid var(--c-bd2);
    border-radius: 40px;
    padding: 3px 10px;
    color: var(--c-tx2);
    cursor: pointer;
    transition: all .13s;
    white-space: nowrap;
    flex-shrink: 0;
}
.ls-apply:hover {
    background: var(--c-acc-a);
    border-color: var(--c-acc);
    color: var(--c-acc);
}
.ls-left-footer {
    border-top: 1px solid var(--c-bd);
    padding: 18px 22px;
    display: flex;
    align-items: center;
    justify-content: space-around;
    gap: 0;
}
.ls-foot-stat { text-align: center; flex: 1; }
.ls-foot-num  { font-size: 19px; font-weight: 700; letter-spacing: -.03em; color: var(--c-tx); }
.ls-foot-lbl  { font-size: 9px; font-family: var(--mono); color: var(--c-tx3); text-transform: uppercase; letter-spacing: .08em; margin-top: 1px; }
.ls-foot-div  { width: 1px; height: 28px; background: var(--c-bd); }

/* ────────────────────────────────────
   CENTER PANEL
───────────────────────────────────── */
.ls-center {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 24px 20px 28px;
    gap: 16px;
    position: relative;
}
.ls-center::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 55% 45% at 50% 38%, rgba(243,45,134,.04) 0%, transparent 70%);
    pointer-events: none;
}

/* Topbar */
.ls-topbar {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 1;
}
.ls-topbar-name {
    flex: 1;
    text-align: center;
    font-family: var(--mono);
    font-size: 12px;
    font-weight: 500;
    color: var(--c-tx2);
    letter-spacing: .03em;
}
.ls-tag {
    font-family: var(--mono);
    font-size: 9.5px;
    letter-spacing: .1em;
    text-transform: uppercase;
    background: var(--c-acc-a);
    color: var(--c-acc);
    padding: 5px 11px;
    border-radius: 40px;
    white-space: nowrap;
}
.ls-topbar-actions {
    display: flex;
    align-items: center;
    gap: 7px;
}
.ls-ibtn {
    width: 32px;
    height: 32px;
    background: var(--c-card);
    border: 1px solid var(--c-bd2);
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--c-tx2);
    font-size: 10px;
    font-weight: 700;
    font-family: var(--mono);
    transition: all .13s;
}
.ls-ibtn:hover { background: var(--c-acc-a); border-color: var(--c-acc); color: var(--c-acc); }
.ls-save-status {
    font-size: 10.5px;
    font-family: var(--mono);
    color: var(--c-tx3);
    min-width: 44px;
}

/* Stage */
.ls-stage {
    position: relative;
    width: 440px;
    height: 440px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ls-orbits {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    color: var(--c-tx);
    animation: ls-spin 90s linear infinite;
}
@keyframes ls-spin { to { transform: rotate(360deg); } }

/* Satellites */
.ls-sat {
    position: absolute;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}
.ls-sat-tl { top: 8px;    left: 12px;  animation: ls-float 4s ease-in-out infinite 0s; }
.ls-sat-tr { top: 8px;    right: 12px; animation: ls-float 4s ease-in-out infinite .7s; }
.ls-sat-bl { bottom: 20px; left: 12px; animation: ls-float 4s ease-in-out infinite 1.4s; }
.ls-sat-br { bottom: 20px; right: 12px;animation: ls-float 4s ease-in-out infinite 2.1s; }
@keyframes ls-float {
    0%,100% { transform: translateY(0); }
    50%     { transform: translateY(-5px); }
}
.ls-sat-lbl {
    font-size: 8.5px;
    font-family: var(--mono);
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--c-tx3);
}
.ls-sat-card {
    width: 92px;
    height: 66px;
    border-radius: 16px;
    border: 1px solid var(--c-bd2);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: all .18s;
    flex-shrink: 0;
}
.ls-sat-card:hover { transform: scale(1.05); box-shadow: var(--c-shd-lg); border-color: rgba(243,45,134,.3); }
.ls-sat-light { background: #ffffff; cursor: default; }
.ls-sat-dark  { background: #0e0c18; cursor: default; }
.ls-sat-upload {
    background: var(--c-surface);
    flex-direction: column;
    gap: 3px;
    cursor: pointer;
    color: var(--c-tx3);
    font-size: 8px;
    font-family: var(--mono);
}
.ls-sat-upload img { max-width: 90%; max-height: 90%; object-fit: contain; }
.ls-sat-upload.drag-over { border-color: var(--c-acc); background: var(--c-acc-a); }
.ls-sat-check {
    background: var(--c-surface);
    color: var(--c-tx3);
    cursor: pointer;
    transition: all .15s;
}
.ls-sat-check:hover { background: var(--c-acc-a); color: var(--c-acc); border-color: var(--c-acc); }
.ls-sat-status {
    font-size: 9px;
    font-family: var(--mono);
    color: var(--c-tx3);
    text-align: center;
    min-height: 13px;
}

/* Main circle */
.ls-circle {
    position: absolute;
    left: 50%; top: 50%;
    transform: translate(-50%,-50%);
    width: 260px; height: 260px;
    border-radius: 50%;
}
.ls-ticks {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    transition: transform .05s linear;
}
.ls-tick {
    position: absolute;
    top: 0; left: 50%;
    width: 1px; height: 50%;
    transform-origin: bottom center;
}
.ls-tick-line {
    position: absolute;
    top: 0; left: 50%;
    transform: translateX(-50%);
    width: 1px;
    height: 4px;
    background: var(--c-bd2);
    border-radius: 2px;
}
.ls-tick-line.major {
    height: 8px;
    width: 1.5px;
    background: var(--c-tx3);
}
.ls-disc {
    position: absolute;
    left: 50%; top: 50%;
    transform: translate(-50%,-50%);
    width: 220px; height: 220px;
    border-radius: 50%;
    background: var(--c-card);
    border: 1px solid var(--c-bd2);
    box-shadow: 0 0 0 7px var(--c-bg), 0 0 0 8px var(--c-bd), var(--c-shd-lg);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    overflow: hidden;
    transition: box-shadow .3s;
}
.ls-disc:hover {
    box-shadow: 0 0 0 7px var(--c-bg), 0 0 0 8px rgba(243,45,134,.28), 0 16px 44px rgba(243,45,134,.12);
}
.ls-logo-area {
    max-width: 160px; max-height: 128px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.ls-logo-area img {
    max-width: 160px; max-height: 120px;
    object-fit: contain;
}
.ls-disc-status {
    font-family: var(--mono);
    font-size: 9px;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--c-tx3);
    position: absolute;
    bottom: 24px;
}

/* Cardinal buttons */
.ls-rb {
    position: absolute;
    width: 28px; height: 28px;
    border-radius: 50%;
    background: var(--c-card);
    border: 1px solid var(--c-bd2);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    color: var(--c-tx2);
    box-shadow: var(--c-shd);
    transition: all .15s;
    z-index: 2;
}
.ls-rb:hover { background: var(--c-acc-a); border-color: var(--c-acc); color: var(--c-acc); }
.ls-rb-n { top: -14px;    left: 50%; transform: translateX(-50%); }
.ls-rb-e { right: -14px;  top: 50%;  transform: translateY(-50%); }
.ls-rb-s { bottom: -14px; left: 50%; transform: translateX(-50%); }
.ls-rb-w { left: -14px;   top: 50%;  transform: translateY(-50%); }
.ls-rb-n:hover { transform: translateX(-50%) scale(1.12); }
.ls-rb-e:hover { transform: translateY(-50%) scale(1.12); }
.ls-rb-s:hover { transform: translateX(-50%) scale(1.12); }
.ls-rb-w:hover { transform: translateY(-50%) scale(1.12); }

/* Mode dots */
.ls-dots {
    display: flex;
    gap: 9px;
    align-items: center;
}
.ls-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    border: none;
    background: var(--c-bd2);
    cursor: pointer;
    transition: all .15s;
}
.ls-dot.active { background: var(--c-acc); transform: scale(1.35); }
.ls-dot:hover  { background: var(--c-tx3); }

/* Checker panel */
.ls-checker-panel {
    width: 100%;
    background: var(--c-card);
    border-radius: var(--r);
    border: 1px solid var(--c-bd);
    padding: 16px 20px;
    animation: ls-fadein .2s ease;
}
@keyframes ls-fadein { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform: none; } }
.ls-checker-head {
    font-size: 10px;
    font-family: var(--mono);
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--c-tx3);
    margin-bottom: 12px;
}
.ls-checker-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 8px;
}
.ls-checker-cell {
    aspect-ratio: 1.4;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(0,0,0,.07);
    overflow: hidden;
}
.ls-checker-cell img { max-width: 88%; max-height: 88%; object-fit: contain; }

/* ────────────────────────────────────
   RIGHT PANEL
───────────────────────────────────── */
.ls-right {
    background: var(--c-card);
    border-left: 1px solid var(--c-bd);
    padding: 0 0 24px;
    display: flex;
    flex-direction: column;
    gap: 0;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--c-bd2) transparent;
}
.ls-field {
    padding: 0 22px;
    margin-bottom: 18px;
}
.ls-field:first-of-type { margin-top: 20px; }
.ls-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    font-family: var(--mono);
    letter-spacing: .09em;
    text-transform: uppercase;
    color: var(--c-tx2);
    margin-bottom: 7px;
}
.ls-label-row { display: flex; justify-content: space-between; }
.ls-val { font-weight: 400; color: var(--c-acc); }
.ls-select {
    width: 100%;
    background: var(--c-surface);
    border: 1px solid var(--c-bd2);
    border-radius: 9px;
    padding: 8px 30px 8px 11px;
    font-family: var(--font);
    font-size: 12.5px;
    color: var(--c-tx);
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='12' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='m6 9 6 6 6-6' fill='none' stroke='%23aaa89f' stroke-width='2'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 11px center;
    transition: border-color .12s;
}
.ls-select:focus { outline: none; border-color: var(--c-acc); }
.ls-icon-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    max-height: 128px;
    overflow-y: auto;
    background: var(--c-surface);
    border: 1px solid var(--c-bd2);
    border-radius: 10px;
    padding: 7px;
    scrollbar-width: none;
}
.ls-icon-grid::-webkit-scrollbar { display: none; }
.ls-icon {
    aspect-ratio: 1;
    display: flex; align-items: center; justify-content: center;
    border-radius: 6px;
    border: 1px solid transparent;
    background: var(--c-card);
    cursor: pointer;
    color: var(--c-tx2);
    transition: all .11s;
}
.ls-icon:hover  { border-color: rgba(243,45,134,.4); background: var(--c-acc-a); color: var(--c-acc); }
.ls-icon.selected { border-color: var(--c-acc); background: var(--c-acc-a); color: var(--c-acc); }
.ls-range {
    width: 100%;
    height: 3px;
    appearance: none;
    background: var(--c-bd2);
    border-radius: 3px;
    outline: none;
    cursor: pointer;
}
.ls-range::-webkit-slider-thumb {
    appearance: none;
    width: 15px; height: 15px;
    border-radius: 50%;
    background: var(--c-acc);
    border: 2px solid #fff;
    box-shadow: 0 1px 4px rgba(243,45,134,.3);
    cursor: pointer;
}
.ls-range::-moz-range-thumb {
    width: 15px; height: 15px;
    border-radius: 50%;
    background: var(--c-acc);
    border: 2px solid #fff;
    cursor: pointer;
}
.ls-field-row {
    display: flex;
    gap: 10px;
    padding: 0 22px;
    margin-bottom: 18px;
}
.ls-field-half { flex: 1; }
.ls-field-half .ls-field { padding: 0; margin-bottom: 0; }

/* Metrics */
.ls-metrics {
    margin: 6px 22px 18px;
    display: flex;
    flex-direction: column;
    gap: 9px;
}
.ls-metric {
    background: var(--c-surface);
    border: 1px solid var(--c-bd);
    border-radius: 10px;
    padding: 10px 13px;
    display: flex;
    align-items: center;
    gap: 11px;
}
.ls-metric-ico {
    width: 30px; height: 30px;
    background: var(--c-acc-a);
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    color: var(--c-acc);
    flex-shrink: 0;
}
.ls-metric-val { font-size: 13px; font-weight: 600; color: var(--c-tx); }
.ls-metric-lbl { font-size: 9.5px; font-family: var(--mono); color: var(--c-tx3); text-transform: uppercase; letter-spacing: .06em; margin-top: 1px; }

/* Save button */
.ls-save-btn {
    margin: 0 22px 11px;
    width: calc(100% - 44px);
    background: var(--c-tx);
    color: #fff;
    border: none;
    border-radius: 11px;
    padding: 12px 18px;
    font-family: var(--font);
    font-size: 12.5px;
    font-weight: 600;
    letter-spacing: .01em;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: all .18s;
}
.ls-save-btn:hover { background: var(--c-acc); box-shadow: 0 5px 18px rgba(243,45,134,.24); transform: translateY(-1px); }
.ls-save-btn:active { transform: none; box-shadow: none; }
.ls-save-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }

/* Export row */
.ls-export-row {
    margin: 0 22px;
    display: flex;
    gap: 9px;
    align-items: center;
}
.ls-exp-btn {
    flex: 1;
    background: var(--c-surface);
    border: 1px solid var(--c-bd2);
    border-radius: 9px;
    padding: 7px 0;
    font-family: var(--mono);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .09em;
    color: var(--c-tx2);
    cursor: pointer;
    transition: all .13s;
}
.ls-exp-btn:hover { background: var(--c-acc-a); border-color: var(--c-acc); color: var(--c-acc); }
.ls-exp-status {
    font-size: 10px;
    font-family: var(--mono);
    color: var(--c-tx3);
    min-width: 42px;
}

/* ─ Responsive ─ */
@media (max-width: 1080px) {
    .ls { grid-template-columns: 240px 1fr 240px; }
    .ls-stage { width: 380px; height: 380px; }
    .ls-circle { width: 220px; height: 220px; }
    .ls-disc   { width: 185px; height: 185px; }
}
@media (max-width: 860px) {
    .ls {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto auto;
    }
    .ls-left, .ls-right { border: none; border-bottom: 1px solid var(--c-bd); }
    .ls-stage { width: 320px; height: 320px; }
    .ls-circle { width: 190px; height: 190px; }
    .ls-disc   { width: 160px; height: 160px; }
    .ls-sat-card { width: 72px; height: 52px; }
    .ls-checker-grid { grid-template-columns: repeat(4,1fr); }
}
</style>

<!-- ════════════ SCRIPT ════════════ -->
<script>
(function () {
    'use strict';
    var ajaxurl = window.ajaxurl || '';

    var P = <?php echo wp_json_encode([
        'id'             => (int)($p['id']             ?? 0),
        'business_name'  => $p['business_name']         ?? '',
        'tagline'        => $p['tagline']                ?? '',
        'primary_color'  => $p['primary_color']          ?? '#1a1a2e',
        'secondary_color'=> $p['secondary_color']        ?? '#16213e',
        'accent_color'   => $p['accent_color']           ?? '#e94560',
        'font_heading'   => $p['font_heading']            ?? 'DM Sans',
        'logo_style'     => $current_style,
        'logo_icon'      => $p['logo_icon']              ?? '',
        'logo_icon_scale'=> (int)($p['logo_icon_scale'] ?? 100),
        'logo_spacing'   => (int)($p['logo_spacing']    ?? 14),
        'logo_position'  => $p['logo_position']          ?? 'auto',
        'logo_text_case' => $p['logo_text_case']         ?? 'default',
        'logo_url'       => $p['logo_url']               ?? '',
        'nonce'          => wp_create_nonce('bae_save_profile'),
    ]); ?>;

    /* ─ helpers ─ */
    function el(id)  { return document.getElementById(id); }
    function qsa(s)  { return document.querySelectorAll(s); }

    /* ─ Preview ─ */
    var _pt = null;
    function sched() { clearTimeout(_pt); _pt = setTimeout(render, 200); }
    function render() {
        var url = el('ls-logo-url').value;
        if (url) {
            var li = '<img src="'+url+'" style="max-height:44px;max-width:130px;object-fit:contain;">';
            var dk = '<img src="'+url+'" style="max-height:44px;max-width:130px;object-fit:contain;filter:brightness(0) invert(1);opacity:.9;">';
            var ma = '<img src="'+url+'" style="max-height:120px;max-width:160px;object-fit:contain;">';
            if (el('ls-preview-light')) el('ls-preview-light').innerHTML = li;
            if (el('ls-preview-dark'))  el('ls-preview-dark').innerHTML  = dk;
            if (el('ls-logo-area'))     el('ls-logo-area').innerHTML     = ma;
            return;
        }
        var fd = new FormData();
        fd.append('action',         'bae_logo_preview');
        fd.append('nonce',          P.nonce);
        fd.append('profile_id',     P.id);
        fd.append('logo_style',     P.logo_style);
        fd.append('logo_icon',      P.logo_icon);
        fd.append('logo_icon_scale',P.logo_icon_scale);
        fd.append('logo_spacing',   P.logo_spacing);
        fd.append('logo_position',  P.logo_position);
        fd.append('logo_text_case', P.logo_text_case);
        fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (!d.success) return;
                if (el('ls-preview-light') && d.data.light) el('ls-preview-light').innerHTML = d.data.light;
                if (el('ls-preview-dark')  && d.data.dark)  el('ls-preview-dark').innerHTML  = d.data.dark;
                if (el('ls-logo-area')     && d.data.light) el('ls-logo-area').innerHTML     = d.data.light;
            })
            .catch(function() {});
    }

    /* ─ Controls ─ */
    function on(id, ev, fn) { var e = el(id); if (e) e.addEventListener(ev, fn); }

    on('ls-style-select', 'change', function() {
        P.logo_style = this.value;
        if (el('ls-circle-status'))  el('ls-circle-status').textContent  = this.value;
        if (el('ls-metric-style'))   el('ls-metric-style').textContent   = this.value.charAt(0).toUpperCase() + this.value.slice(1);
        syncVariants(this.value);
        sched();
    });
    on('ls-position', 'change', function() { P.logo_position   = this.value; sched(); });
    on('ls-case',     'change', function() { P.logo_text_case  = this.value; sched(); });

    on('ls-icon-scale', 'input', function() {
        P.logo_icon_scale = parseInt(this.value, 10);
        if (el('ls-scale-val')) el('ls-scale-val').textContent = this.value + '%';
        sched();
    });
    on('ls-spacing', 'input', function() {
        P.logo_spacing = parseInt(this.value, 10);
        if (el('ls-spacing-val')) el('ls-spacing-val').textContent = this.value + 'px';
        sched();
    });

    qsa('.ls-icon').forEach(function(t) {
        t.addEventListener('click', function() {
            qsa('.ls-icon').forEach(function(i) { i.classList.remove('selected'); });
            t.classList.add('selected');
            P.logo_icon = t.dataset.value || '';
            if (el('ls-icon-hidden')) el('ls-icon-hidden').value = P.logo_icon;
            sched();
        });
    });

    /* ─ Variant list sync ─ */
    function syncVariants(style) {
        qsa('.ls-variant').forEach(function(row) {
            var s = row.dataset.style;
            var isNow = (s === style);
            row.classList.toggle('is-active', isNow);
            var meta = row.querySelector('.ls-pill-active, .ls-apply');
            if (!meta) return;
            if (isNow) {
                meta.outerHTML = '<span class="ls-pill-active">Active</span>';
            } else if (meta.classList.contains('ls-pill-active')) {
                var btn = document.createElement('button');
                btn.className   = 'ls-apply';
                btn.dataset.style = s;
                btn.textContent = 'Apply';
                btn.addEventListener('click', applyVariant);
                meta.replaceWith(btn);
            }
        });
    }
    function applyVariant(e) {
        var style = e.currentTarget.dataset.style;
        var sel = el('ls-style-select');
        if (sel) { sel.value = style; sel.dispatchEvent(new Event('change')); }
    }
    qsa('.ls-apply').forEach(function(b) { b.addEventListener('click', applyVariant); });

    /* ─ Save ─ */
    function save(extra) {
        var sb = el('ls-save-main'); var st = el('ls-save-status');
        if (sb) { sb.disabled = true; sb.textContent = 'Saving…'; }
        var fd = new FormData();
        fd.append('action',         'bae_save_profile');
        fd.append('nonce',          P.nonce);
        fd.append('profile_id',     P.id);
        fd.append('logo_style',     P.logo_style);
        fd.append('logo_icon',      P.logo_icon);
        fd.append('logo_icon_scale',P.logo_icon_scale);
        fd.append('logo_spacing',   P.logo_spacing);
        fd.append('logo_position',  P.logo_position);
        fd.append('logo_text_case', P.logo_text_case);
        fd.append('logo_url',       el('ls-logo-url').value);
        fd.append('business_name',  P.business_name);
        fd.append('tagline',        P.tagline);
        fd.append('primary_color',  P.primary_color);
        fd.append('secondary_color',P.secondary_color);
        fd.append('accent_color',   P.accent_color);
        fd.append('font_heading',   P.font_heading);
        if (extra) Object.keys(extra).forEach(function(k) { fd.append(k, extra[k]); });
        fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (sb) {
                    sb.disabled = false;
                    sb.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Logo Settings';
                }
                if (st) {
                    st.textContent = d.success ? '✓ Saved' : 'Failed';
                    st.style.color = d.success ? '#34d399' : '#fb7185';
                    setTimeout(function() { if (st) st.textContent = ''; }, 3000);
                }
                if (d.success && typeof window.baeToast === 'function') window.baeToast('Logo saved.', 'success');
            })
            .catch(function() {
                if (sb) { sb.disabled = false; sb.textContent = 'Save Logo Settings'; }
                if (st) { st.textContent = 'Error'; st.style.color = '#fb7185'; }
            });
    }
    on('ls-save-main', 'click', function() { save(); });
    on('ls-save-btn',  'click', function() { save(); });

    /* ─ Upload ─ */
    function uploadFile(file) {
        if (!file) return;
        var allowed = ['image/png','image/jpeg','image/jpg','image/svg+xml','image/gif','image/webp'];
        if (!allowed.includes(file.type)) { setUpSt('PNG/JPG/SVG only', '#fb7185'); return; }
        if (file.size > 2 * 1024 * 1024) { setUpSt('Max 2 MB', '#fb7185'); return; }
        setUpSt('Uploading…', 'var(--c-tx3)');
        var fd = new FormData();
        fd.append('action',    'bae_upload_logo');
        fd.append('nonce',     P.nonce);
        fd.append('logo_file', file);
        if (P.id) fd.append('profile_id', P.id);
        fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success && d.data && d.data.url) {
                    var url = d.data.url;
                    el('ls-logo-url').value = url;
                    P.logo_url = url;
                    refreshUploadSat(url);
                    if (el('ls-mode-tag')) el('ls-mode-tag').textContent = 'Image Mode';
                    setUpSt('✓ Uploaded', '#34d399');
                    render();
                    save({ logo_url: url });
                    if (typeof window.baeToast === 'function') window.baeToast('Logo uploaded.', 'success');
                } else {
                    setUpSt((d.data && d.data.message) || 'Upload failed', '#fb7185');
                }
            })
            .catch(function() { setUpSt('Network error', '#fb7185'); });
    }
    function setUpSt(msg, color) {
        var e = el('ls-upload-status'); if (!e) return;
        e.textContent = msg; e.style.color = color;
        if (msg.charAt(0) === '✓') setTimeout(function() { e.textContent = ''; }, 3000);
    }
    function refreshUploadSat(url) {
        var sat = el('ls-upload-sat'); if (!sat) return;
        sat.innerHTML = '<img src="'+url+'" style="max-width:90%;max-height:90%;object-fit:contain;"><input type="file" id="ls-file-input" accept="image/*" style="display:none">';
        bindFileInput();
    }
    function bindFileInput() {
        var fi  = el('ls-file-input'); if (fi)  fi.addEventListener('change', function() { uploadFile(this.files[0]); });
        var sat = el('ls-upload-sat'); if (!sat) return;
        sat.addEventListener('dragover',  function(e) { e.preventDefault(); sat.classList.add('drag-over'); });
        sat.addEventListener('dragleave', function()  { sat.classList.remove('drag-over'); });
        sat.addEventListener('drop',      function(e) { e.preventDefault(); sat.classList.remove('drag-over'); uploadFile(e.dataTransfer.files[0]); });
    }
    bindFileInput();

    /* ─ Radial buttons ─ */
    on('ls-rb-refresh', 'click', render);
    on('ls-rb-export',  'click', function() { download('png'); });
    on('ls-rb-remove',  'click', function() {
        el('ls-logo-url').value = ''; P.logo_url = '';
        var sat = el('ls-upload-sat');
        if (sat) {
            sat.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.45"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg><span>Drop / click</span><input type="file" id="ls-file-input" accept="image/*" style="display:none">';
            bindFileInput();
        }
        if (el('ls-mode-tag')) el('ls-mode-tag').textContent = 'CSS Builder';
        render();
        save({ logo_url: '' });
    });
    on('ls-rb-check',    'click', toggleChecker);
    on('ls-checker-btn', 'click', toggleChecker);

    /* ─ BG Checker ─ */
    var checkerOpen = false;
    function toggleChecker() {
        var panel = el('ls-checker-panel'); if (!panel) return;
        checkerOpen = !checkerOpen;
        panel.style.display = checkerOpen ? 'block' : 'none';
        if (checkerOpen) buildChecker();
    }
    function buildChecker() {
        var grid = el('ls-checker-grid'); if (!grid || grid.children.length) return;
        var url  = el('ls-logo-url').value;
        var area = el('ls-logo-area');
        ['#ffffff','#f2f0ec','#1b1916','#0e0c18','#F32D86','#2d1066','#1d3a6b','#c2410c'].forEach(function(bg) {
            var cell = document.createElement('div');
            cell.className = 'ls-checker-cell';
            cell.style.background = bg;
            cell.innerHTML = url
                ? '<img src="'+url+'" style="max-width:88%;max-height:88%;object-fit:contain;">'
                : (area ? area.innerHTML : '');
            grid.appendChild(cell);
        });
    }

    /* ─ Download ─ */
    function download(fmt) {
        var st  = el('ls-dl-status'); if (st) st.textContent = 'Preparing…';
        var url = el('ls-logo-url').value;
        var nm  = P.business_name || 'logo';
        if (url && fmt === 'png') {
            var a = document.createElement('a'); a.href = url; a.download = nm + '.png'; a.click();
            done(st); return;
        }
        var area = el('ls-logo-area'); if (!area) { if (st) st.textContent = 'Nothing to export.'; return; }
        if (fmt === 'svg') {
            var blob = new Blob(['<svg xmlns="http://www.w3.org/2000/svg" width="400" height="100"><foreignObject width="400" height="100"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:12px;display:flex;align-items:center;">'+area.innerHTML+'</body></foreignObject></svg>'], { type: 'image/svg+xml' });
            var u = URL.createObjectURL(blob);
            var a = document.createElement('a'); a.href = u; a.download = nm + '.svg'; a.click();
            setTimeout(function() { URL.revokeObjectURL(u); }, 2000);
            done(st);
        } else {
            var canvas = document.createElement('canvas'); canvas.width = 800; canvas.height = 200;
            var ctx = canvas.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0,0,800,200);
            var svgBlob = new Blob(['<svg xmlns="http://www.w3.org/2000/svg" width="800" height="200"><foreignObject width="800" height="200"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:24px;display:flex;align-items:center;zoom:2;">'+area.innerHTML+'</body></foreignObject></svg>'], { type: 'image/svg+xml' });
            var bu = URL.createObjectURL(svgBlob);
            var img = new Image(); img.crossOrigin = 'anonymous';
            img.onload = function() {
                ctx.drawImage(img,0,0); URL.revokeObjectURL(bu);
                var a = document.createElement('a'); a.download = nm+'.png'; a.href = canvas.toDataURL('image/png'); a.click();
                done(st);
            };
            img.onerror = function() { if (st) { st.textContent = 'Try SVG instead'; st.style.color = '#fb7185'; } };
            img.src = bu;
        }
    }
    function done(st) {
        if (!st) return;
        st.textContent = '✓ Done'; st.style.color = '#34d399';
        setTimeout(function() { st.textContent = ''; }, 2500);
    }
    on('ls-dl-png',  'click', function() { download('png'); });
    on('ls-dl-svg',  'click', function() { download('svg'); });
    on('ls-top-png', 'click', function() { download('png'); });
    on('ls-top-svg', 'click', function() { download('svg'); });

    /* ─ Mode dots (cosmetic) ─ */
    qsa('.ls-dot').forEach(function(d) {
        d.addEventListener('click', function() {
            qsa('.ls-dot').forEach(function(x) { x.classList.remove('active'); });
            d.classList.add('active');
        });
    });

    /* ─ Tick ring animation ─ */
    var ticks = el('ls-ticks'), angle = 0;
    if (ticks) setInterval(function() { angle += 0.18; ticks.style.transform = 'rotate('+angle+'deg)'; }, 50);

})();
</script>
<?php
    return bae_wrap_tab_panel(ob_get_clean());
}
