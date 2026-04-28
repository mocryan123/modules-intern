<?php if (!defined('ABSPATH')) exit;
function bae_logo_tab($user_id, $profile) {
    if (empty($profile)) {
        return '<div class="bae-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/><path d="M12 6v6l4 2"/></svg><strong>No brand profile yet.</strong><p>Complete your <a href="?tab=overview">Brand Profile</a> first.</p></div>';
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

        <!-- Stats row (identical to overview tab) -->
        <div class="bae-stats-row">
            <div class="bae-stat-card">
                <div class="bae-stat-label">Active Style</div>
                <div class="bae-stat-value" style="font-size:18px;margin-top:10px;">
                    <span class="bae-badge bae-badge-purple"><?php echo esc_html(ucfirst($current_style)); ?></span>
                </div>
            </div>
            <div class="bae-stat-card">
                <div class="bae-stat-label">Logo Uploaded</div>
                <div class="bae-stat-value" style="font-size:18px;margin-top:10px;">
                    <?php if (!empty($p['logo_url'])): ?>
                        <span class="bae-badge bae-badge-green">Yes</span>
                    <?php else: ?>
                        <span class="bae-badge bae-badge-gray">No</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bae-stat-card">
                <div class="bae-stat-label">Previews</div>
                <div class="bae-stat-value">2</div>
                <div class="bae-stat-sub">Light + Dark</div>
            </div>
            <div class="bae-stat-card">
                <div class="bae-stat-label">Last Updated</div>
                <div class="bae-stat-value" style="font-size:15px;margin-top:10px;">
                    <?php echo !empty($p['updated_at']) ? date('M d, Y', strtotime($p['updated_at'])) : '—'; ?>
                </div>
            </div>
        </div>

        <!-- Three‑column layout using Bento grid / flex -->
        <div class="bae-profile-split" style="align-items: stretch; gap: 24px; flex-wrap: wrap;">
            <!-- LEFT: Logo variants (cards) -->
            <div class="bae-card" style="flex: 1.2; min-width: 260px; padding: 20px;">
                <div class="bae-card-header" style="margin-bottom: 16px; padding-bottom: 0;">
                    <div class="bae-card-title" style="font-size: 13px;">Logo Variants</div>
                </div>
                <div class="bae-variants-list" style="display: flex; flex-direction: column; gap: 12px; max-height: 480px; overflow-y: auto;">
                    <?php foreach ($styles as $key => $info):
                        $is_active = ($current_style === $key);
                        $preview_profile = $p;
                        $preview_profile['logo_style'] = $key;
                        $preview_html = bae_render_logo_lockup($preview_profile, ['compact' => true, 'dark' => false]);
                    ?>
                    <div class="bae-variant-item" data-style="<?php echo esc_attr($key); ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--bg-2); border-radius: 14px; border: 1px solid var(--border); transition: all 0.15s;">
                        <div style="width: 56px; height: 48px; background: var(--surface); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <?php echo $preview_html; ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 13px; color: var(--text);"><?php echo esc_html($info['label']); ?></div>
                            <div style="font-size: 10px; color: var(--text-3);"><?php echo esc_html($info['sub']); ?></div>
                        </div>
                        <div>
                            <span class="bae-badge <?php echo $is_active ? 'bae-badge-purple' : 'bae-badge-gray'; ?>" style="font-size: 9px;"><?php echo $is_active ? 'Active' : 'Inactive'; ?></span>
                        </div>
                        <div style="width: 60px;">
                            <div style="height: 3px; background: var(--border-2); border-radius: 3px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo $is_active ? '100' : '0'; ?>%; background: var(--brand-soft); border-radius: 3px;"></div>
                            </div>
                        </div>
                        <button class="variant-apply-btn bae-btn bae-btn-outline bae-btn-sm" data-style="<?php echo esc_attr($key); ?>" style="padding: 4px 12px; font-size: 11px;"><?php echo $is_active ? 'Applied' : 'Apply'; ?></button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="bae-metrics-strip" style="display: flex; justify-content: space-around; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border);">
                    <div style="text-align: center;"><div style="font-size: 20px; font-weight: 700;"><?php echo count($styles); ?></div><div style="font-size: 10px; color: var(--text-3);">Styles</div></div>
                    <div style="width:1px; background: var(--border);"></div>
                    <div style="text-align: center;"><div style="font-size: 20px; font-weight: 700;"><?php echo !empty($p['logo_url']) ? '1' : '0'; ?></div><div style="font-size: 10px; color: var(--text-3);">Uploaded</div></div>
                    <div style="width:1px; background: var(--border);"></div>
                    <div style="text-align: center;"><div style="font-size: 20px; font-weight: 700;">2</div><div style="font-size: 10px; color: var(--text-3);">Previews</div></div>
                </div>
            </div>

            <!-- CENTER: Circular Stage (original) -->
            <div class="bae-card" style="flex: 2; min-width: 360px; display: flex; flex-direction: column; align-items: center; padding: 20px;">
                <!-- Top bar -->
                <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div class="bae-badge bae-badge-purple" style="font-size: 10px;" id="ls-mode-tag"><?php echo !empty($p['logo_url']) ? 'Image Mode' : 'CSS Builder'; ?></div>
                    <div style="font-size: 13px; font-weight: 600; color: var(--text-2);"><?php echo esc_html($p['business_name'] ?? 'Your Brand'); ?></div>
                    <div style="display: flex; gap: 8px;">
                        <button class="bae-btn bae-btn-outline bae-btn-sm" id="ls-download-png">PNG</button>
                        <button class="bae-btn bae-btn-outline bae-btn-sm" id="ls-download-svg">SVG</button>
                        <button class="bae-btn bae-btn-outline bae-btn-sm" id="ls-save-main"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg></button>
                        <span id="ls-save-status" style="font-size: 11px; color: var(--text-3);"></span>
                    </div>
                </div>

                <!-- Orbit stage -->
                <div style="position: relative; width: 380px; height: 380px; margin: 0 auto;">
                    <svg style="position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; animation: spin 80s linear infinite;" viewBox="0 0 560 560">
                        <circle cx="280" cy="280" r="270" fill="none" stroke="currentColor" stroke-width="0.5" stroke-dasharray="4 8" opacity="0.15"/>
                        <circle cx="280" cy="280" r="230" fill="none" stroke="currentColor" stroke-width="0.5" stroke-dasharray="2 12" opacity="0.1"/>
                    </svg>
                    <!-- Satellites -->
                    <div style="position: absolute; top: 0; left: 0; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                        <div style="font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-3);">Light</div>
                        <div class="light-surface" style="width: 90px; height: 64px; border-radius: 16px; border: 1px solid var(--border); background: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden;" id="ls-preview-light"><?php echo bae_render_logo_lockup($p, ['dark'=>false]); ?></div>
                    </div>
                    <div style="position: absolute; top: 0; right: 0; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                        <div style="font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-3);">Dark</div>
                        <div class="dark-surface" style="width: 90px; height: 64px; border-radius: 16px; border: 1px solid var(--border); background: #1a1a24; display: flex; align-items: center; justify-content: center; overflow: hidden;" id="ls-preview-dark"><?php echo bae_render_logo_lockup($p, ['dark'=>true]); ?></div>
                    </div>
                    <div style="position: absolute; bottom: 0; left: 0; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                        <div style="font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-3);">Upload</div>
                        <label style="width: 90px; height: 64px; border-radius: 16px; border: 1px solid var(--border); background: var(--bg-2); display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer;" id="ls-upload-sat">
                            <?php if (!empty($p['logo_url'])): ?>
                                <img src="<?php echo esc_url($p['logo_url']); ?>" style="max-width: 90%; max-height: 90%; object-fit: contain;">
                            <?php else: ?>
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg><span style="font-size: 8px;">Drop or click</span>
                            <?php endif; ?>
                            <input type="file" id="ls-file-input" accept="image/*" style="display: none;">
                        </label>
                        <span id="ls-upload-status" style="font-size: 10px; color: var(--text-3);"></span>
                    </div>
                    <div style="position: absolute; bottom: 0; right: 0; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                        <div style="font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-3);">BG Check</div>
                        <button class="bae-btn bae-btn-outline bae-btn-sm" style="width: 90px; height: 64px; border-radius: 16px;" id="ls-checker-btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg></button>
                    </div>
                    <!-- Main circle -->
                    <div style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 220px; height: 220px;">
                        <div id="ls-tick-ring" style="position: absolute; inset: 0; border-radius: 50%;">
                            <?php for ($i = 0; $i < 60; $i++): ?>
                            <div style="position: absolute; top: 0; left: 50%; width: 1px; height: 50%; transform: rotate(<?php echo $i * 6; ?>deg); transform-origin: bottom center;">
                                <div style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 1px; height: <?php echo ($i % 5 === 0) ? '9px' : '5px'; ?>; background: var(--border-2); border-radius: 2px;"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div style="width: 190px; height: 190px; border-radius: 50%; background: var(--surface); border: 1px solid var(--border); box-shadow: 0 0 0 6px var(--bg), 0 0 0 7px var(--border), 0 10px 30px var(--shadow); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; margin-top: 15px; margin-left: 15px;">
                            <div id="ls-logo-display" style="max-width: 140px; max-height: 110px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <?php if (!empty($p['logo_url'])): ?>
                                    <img src="<?php echo esc_url($p['logo_url']); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <div id="ls-logo-lockup"><?php echo bae_render_logo_lockup($p, ['dark'=>false]); ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-3);" id="ls-circle-status"><?php echo esc_html($current_style); ?></div>
                        </div>
                        <!-- Radial buttons -->
                        <button class="bae-btn bae-btn-outline bae-btn-sm" style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); width: 28px; height: 28px; border-radius: 50%; padding: 0;" id="ls-rb-refresh" title="Refresh"><svg width="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></button>
                        <button class="bae-btn bae-btn-outline bae-btn-sm" style="position: absolute; right: -14px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border-radius: 50%; padding: 0;" id="ls-rb-check" title="Check"><svg width="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg></button>
                        <button class="bae-btn bae-btn-outline bae-btn-sm" style="position: absolute; bottom: -14px; left: 50%; transform: translateX(-50%); width: 28px; height: 28px; border-radius: 50%; padding: 0;" id="ls-rb-remove" title="Remove"><svg width="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                        <button class="bae-btn bae-btn-outline bae-btn-sm" style="position: absolute; left: -14px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border-radius: 50%; padding: 0;" id="ls-rb-export" title="Export"><svg width="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button>
                    </div>
                </div>
                <!-- Mode dots -->
                <div style="display: flex; justify-content: center; gap: 10px; margin-top: 16px;">
                    <button class="bae-mode-dot active" data-mode="light" style="width: 8px; height: 8px; border-radius: 50%; background: var(--brand); cursor: pointer;"></button>
                    <button class="bae-mode-dot" data-mode="dark" style="width: 8px; height: 8px; border-radius: 50%; background: var(--border-2); cursor: pointer;"></button>
                    <button class="bae-mode-dot" data-mode="upload" style="width: 8px; height: 8px; border-radius: 50%; background: var(--border-2); cursor: pointer;"></button>
                    <button class="bae-mode-dot" data-mode="check" style="width: 8px; height: 8px; border-radius: 50%; background: var(--border-2); cursor: pointer;"></button>
                </div>

                <!-- Background Checker panel (hidden) -->
                <div id="ls-checker-panel" style="display: none; width: 100%; margin-top: 20px; background: var(--surface); border-radius: 16px; padding: 16px;">
                    <div style="font-size: 11px; font-weight: 700;">Background compatibility</div>
                    <div id="ls-checker-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px,1fr)); gap: 10px; margin-top: 12px;"></div>
                </div>
            </div>

            <!-- RIGHT: CSS Builder + Save/Export -->
            <div style="flex: 1; min-width: 260px; display: flex; flex-direction: column; gap: 20px;">
                <div class="bae-card">
                    <div class="bae-card-header"><div class="bae-card-title">CSS Builder <span style="font-size: 10px; font-weight: 400;">(fallback)</span></div></div>
                    <div class="bae-card-body" style="padding: 18px;">
                        <div class="bae-form-group">
                            <label>Logo Type</label>
                            <select class="bae-select" id="ls-style-select">
                                <?php foreach ($styles as $val => $info): ?>
                                    <option value="<?php echo $val; ?>" <?php selected($current_style, $val); ?>><?php echo esc_html($info['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="bae-form-group">
                            <label>Icon</label>
                            <div class="bae-icon-grid" style="display: grid; grid-template-columns: repeat(6,1fr); gap: 6px; max-height: 120px; overflow-y: auto; background: var(--bg-2); border-radius: 12px; padding: 8px;">
                                <?php $all_icons = bae_get_all_icons(); $sli = $p['logo_icon'] ?? ''; foreach ($all_icons as $icon_key => $icon_label): $svg = bae_get_icon_svg_preview($icon_key); ?>
                                <div class="bae-icon-tile <?php echo $sli === $icon_key ? 'selected' : ''; ?>" data-value="<?php echo esc_attr($icon_key); ?>" title="<?php echo esc_attr($icon_label); ?>" style="aspect-ratio:1; display:flex; align-items:center; justify-content:center; border-radius:8px; border:1px solid transparent; cursor:pointer;"><?php echo $svg; ?></div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="ls-icon-hidden" value="<?php echo esc_attr($sli); ?>">
                        </div>
                        <div class="bae-form-group">
                            <label>Icon Scale <span id="ls-scale-val"><?php echo (int)($p['logo_icon_scale'] ?? 100); ?>%</span></label>
                            <input type="range" id="ls-icon-scale" min="60" max="160" step="5" value="<?php echo (int)($p['logo_icon_scale'] ?? 100); ?>" style="width:100%;">
                        </div>
                        <div class="bae-form-group">
                            <label>Spacing <span id="ls-spacing-val"><?php echo (int)($p['logo_spacing'] ?? 14); ?>px</span></label>
                            <input type="range" id="ls-spacing" min="6" max="28" step="1" value="<?php echo (int)($p['logo_spacing'] ?? 14); ?>" style="width:100%;">
                        </div>
                        <div class="bae-form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="bae-form-group"><label>Position</label><select id="ls-position"><?php $pos = $p['logo_position'] ?? 'auto'; ?><option value="auto" <?php selected($pos,'auto'); ?>>Auto</option><option value="left" <?php selected($pos,'left'); ?>>Left</option><option value="top" <?php selected($pos,'top'); ?>>Top</option><option value="right" <?php selected($pos,'right'); ?>>Right</option></select></div>
                            <div class="bae-form-group"><label>Text Case</label><select id="ls-case"><?php $tc = $p['logo_text_case'] ?? 'default'; ?><option value="default" <?php selected($tc,'default'); ?>>Default</option><option value="uppercase" <?php selected($tc,'uppercase'); ?>>UPPER</option><option value="title" <?php selected($tc,'title'); ?>>Title</option><option value="lowercase" <?php selected($tc,'lowercase'); ?>>lower</option></select></div>
                        </div>
                    </div>
                </div>
                <div class="bae-card">
                    <div class="bae-card-header"><div class="bae-card-title">Save & Export</div></div>
                    <div class="bae-card-body" style="padding: 18px;">
                        <button class="bae-btn bae-btn-primary" style="width:100%;" id="ls-save-settings">Save Logo Settings</button>
                        <div style="display: flex; gap: 10px; margin-top: 12px;">
                            <button class="bae-btn bae-btn-outline" style="flex:1;" id="ls-dl-png">PNG</button>
                            <button class="bae-btn bae-btn-outline" style="flex:1;" id="ls-dl-svg">SVG</button>
                            <span id="ls-dl-status" style="font-size: 11px; color: var(--text-3);"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .bae-logo-studio .bae-card { background: var(--surface); backdrop-filter: blur(12px); border: 1px solid var(--border); }
    .bae-logo-studio .bae-card-header { border-bottom: 1px solid var(--border); padding: 14px 18px; margin-bottom: 0; }
    .bae-logo-studio .bae-card-body { padding: 18px; }
    .bae-logo-studio .bae-select { width: 100%; background: var(--bg-2); border: 1px solid var(--border); border-radius: 12px; padding: 8px 12px; }
    .bae-logo-studio .bae-form-group { margin-bottom: 16px; }
    .bae-logo-studio .bae-form-group label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-2); display: block; margin-bottom: 6px; }
    .bae-icon-tile:hover { border-color: var(--brand-soft); background: rgba(243,45,134,0.08); }
    .bae-icon-tile.selected { border-color: var(--brand); background: rgba(243,45,134,0.12); }
    .bae-variant-item:hover { background: rgba(243,45,134,0.06); border-color: var(--brand-soft); }
    .light-surface, .dark-surface { transition: all 0.2s; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .bae-mode-dot { transition: all 0.15s; }
    .bae-mode-dot.active { transform: scale(1.2); background: var(--brand); }
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

        function $(id){ return document.getElementById(id); }
        var previewTimer = null;
        function schedulePreview(){ clearTimeout(previewTimer); previewTimer = setTimeout(doPreview, 200); }
        function doPreview(){
            var logoUrl = $('ls-logo-url') ? $('ls-logo-url').value : '';
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
        if(styleSelect) styleSelect.addEventListener('change', function(){ P.logo_style = this.value; if($('ls-circle-status')) $('ls-circle-status').innerText = this.value; schedulePreview(); });
        if(posSelect) posSelect.addEventListener('change', function(){ P.logo_position = this.value; schedulePreview(); });
        if(caseSelect) caseSelect.addEventListener('change', function(){ P.logo_text_case = this.value; schedulePreview(); });

        document.querySelectorAll('.bae-icon-tile').forEach(function(tile){
            tile.addEventListener('click', function(){
                document.querySelectorAll('.bae-icon-tile').forEach(t=>t.classList.remove('selected'));
                this.classList.add('selected');
                P.logo_icon = this.dataset.value;
                if($('ls-icon-hidden')) $('ls-icon-hidden').value = P.logo_icon;
                schedulePreview();
            });
        });

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
            fd.append('logo_url', $('ls-logo-url') ? $('ls-logo-url').value : '');
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
                        setTimeout(()=>{ if(saveStatus) saveStatus.textContent=''; }, 3000);
                    }
                    if(data.success && typeof window.baeToast === 'function') window.baeToast('Logo settings saved.','success');
                });
        }
        if(saveBtn) saveBtn.addEventListener('click', ()=>doSave());

        var fileInput = $('ls-file-input');
        var uploadSat = $('ls-upload-sat');
        var uploadStatus = $('ls-upload-status');
        function uploadFile(file){
            if(!file) return;
            var allowed = ['image/png','image/jpeg','image/jpg','image/svg+xml','image/gif','image/webp'];
            if(!allowed.includes(file.type)){ if(uploadStatus){uploadStatus.textContent='PNG/JPG/SVG only';uploadStatus.style.color='#fb7185';} return; }
            if(file.size>2*1024*1024){ if(uploadStatus){uploadStatus.textContent='Max 2MB';uploadStatus.style.color='#fb7185';} return; }
            if(uploadStatus){ uploadStatus.textContent='Uploading...'; uploadStatus.style.color='var(--text-3)'; }
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
                        if($('ls-logo-url')) $('ls-logo-url').value = url;
                        P.logo_url = url;
                        if(uploadSat) uploadSat.innerHTML = '<img src="'+url+'" style="max-width:90%;max-height:90%;object-fit:contain;"><input type="file" id="ls-file-input" accept="image/*" style="display:none">';
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
        if(uploadSat){
            uploadSat.addEventListener('dragover', e=>{ e.preventDefault(); uploadSat.classList.add('drag-over'); });
            uploadSat.addEventListener('dragleave', ()=>uploadSat.classList.remove('drag-over'));
            uploadSat.addEventListener('drop', e=>{ e.preventDefault(); uploadSat.classList.remove('drag-over'); uploadFile(e.dataTransfer.files[0]); });
        }

        var removeBtn = document.getElementById('ls-rb-remove');
        if(removeBtn){
            removeBtn.addEventListener('click', function(){
                if($('ls-logo-url')) $('ls-logo-url').value = '';
                P.logo_url = '';
                if(uploadSat) uploadSat.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg><span style="font-size: 8px;">Drop or click</span><input type="file" id="ls-file-input" accept="image/*" style="display:none">';
                if($('ls-mode-tag')) $('ls-mode-tag').textContent = 'CSS Builder';
                doPreview();
                doSave({logo_url:''});
            });
        }

        var rbRefresh = $('ls-rb-refresh'); if(rbRefresh) rbRefresh.addEventListener('click', doPreview);
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
            var logoUrl = $('ls-logo-url') ? $('ls-logo-url').value : '';
            var colors = ['#ffffff','#f8f9fc','#1a1a24','#F32D86','#2d1066','#1d3a6b','#c2410c'];
            colors.forEach(function(bg){
                var cell = document.createElement('div');
                cell.style.cssText = 'aspect-ratio:1.4/1; border-radius:10px; display:flex; align-items:center; justify-content:center; border:1px solid var(--border); background:'+bg+';';
                if(logoUrl){
                    cell.innerHTML = '<img src="'+logoUrl+'" style="max-width:85%; max-height:85%; object-fit:contain;">';
                } else {
                    var lockup = $('ls-logo-display');
                    if(lockup) cell.innerHTML = lockup.innerHTML;
                }
                grid.appendChild(cell);
            });
        }

        function doDownload(fmt){
            var status = $('ls-dl-status');
            if(status) status.textContent = 'Preparing...';
            var logoUrl = $('ls-logo-url') ? $('ls-logo-url').value : '';
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

        document.querySelectorAll('.variant-apply-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var style = this.dataset.style;
                if(styleSelect) styleSelect.value = style;
                if(styleSelect) styleSelect.dispatchEvent(new Event('change'));
                document.querySelectorAll('.variant-apply-btn').forEach(b=>{ b.textContent = b.dataset.style === style ? 'Applied' : 'Apply'; });
                document.querySelectorAll('.bae-badge').forEach(badge=>{ badge.classList.remove('bae-badge-purple','bae-badge-gray'); badge.classList.add('bae-badge-gray'); });
                if(btn.parentElement.querySelector('.bae-badge')) btn.parentElement.querySelector('.bae-badge').classList.add('bae-badge-purple');
                if(typeof window.baeToast === 'function') window.baeToast('Applied '+style+' style', 'success');
            });
        });

        document.querySelectorAll('.bae-mode-dot').forEach(function(dot){
            dot.addEventListener('click', function(){
                document.querySelectorAll('.bae-mode-dot').forEach(d=>d.classList.remove('active'));
                this.classList.add('active');
            });
        });

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
