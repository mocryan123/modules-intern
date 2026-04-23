<?php if (!defined('ABSPATH')) exit;
function bae_logo_tab($user_id, $profile) {
    if (empty($profile)) {
        return '<div style="padding:40px;text-align:center;color:var(--text-3);">Complete your Brand Identity first to access Logo Studio.</div>';
    }
    $p = $profile;
    ob_start();
    ?>
    <div style="padding:28px;max-width:960px;margin:0 auto;">
        <div style="margin-bottom:24px;">
            <div style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--text-3);margin-bottom:6px;">Logo Studio</div>
            <div style="font-size:22px;font-weight:700;color:var(--text);line-height:1.2;">Your Logo</div>
            <div style="font-size:14px;color:var(--text-3);margin-top:6px;">Upload your logo or build one with the CSS builder. This logo is used across all your brand assets.</div>
        </div>

        <div class="bae-bento-grid">

        <!-- ══ Logo Studio (full width) ══ -->
        <div class="bae-bento-card span-12">
            <div class="bae-bento-label">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                Logo Studio
            </div>
            <div class="bae-logo-studio-grid">

                <!-- Sub-panel A: Upload -->
                <div style="padding:20px;background:var(--bg-3);border:2px dashed var(--border-2);border-radius:18px;transition:border-color .2s;" id="bae-logo-upload-area">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                        <div style="width:22px;height:22px;border-radius:6px;background:linear-gradient(135deg,var(--brand-deep),var(--brand));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span style="font-size:11px;font-weight:800;color:white;">A</span>
                        </div>
                        <div style="font-size:12px;font-weight:700;color:var(--text-2);">Upload Your Logo <span style="font-weight:400;color:var(--text-3);">— PNG, SVG, JPG</span></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                        <div id="bae-logo-preview-wrap" style="width:80px;height:80px;border-radius:12px;background:var(--surface);border:1px solid var(--border-2);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
                            <?php if (!empty($p['logo_url'])): ?>
                                <img src="<?php echo esc_url($p['logo_url']); ?>" style="max-width:100%;max-height:100%;object-fit:contain;" id="bae-logo-preview-img">
                            <?php else: ?>
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:160px;">
                            <div style="font-size:12px;color:var(--text-3);margin-bottom:10px;">Transparent PNG recommended.</div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <label style="display:inline-flex;align-items:center;gap:6px;background:var(--surface);border:1px solid var(--border-2);border-radius:9px;padding:8px 14px;font-size:12px;font-weight:600;color:var(--text-2);cursor:pointer;transition:all .2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    Choose File
                                    <input type="file" id="bae-logo-file-input" accept="image/*" style="display:none;">
                                </label>
                                <?php if (!empty($p['logo_url'])): ?>
                                <button type="button" id="bae-logo-remove-btn" style="background:none;border:1px solid rgba(244,63,94,.3);border-radius:9px;padding:8px 14px;font-size:12px;font-weight:600;color:#fb7185;cursor:pointer;font-family:'Geist',sans-serif;">Remove</button>
                                <button type="button" id="bae-logo-check-btn" class="bae-btn bae-btn-outline bae-btn-sm" style="display:flex;align-items:center;gap:5px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                                    Check BG
                                </button>
                                <?php endif; ?>
                                <span id="bae-logo-upload-status" style="font-size:12px;color:var(--text-3);"></span>
                            </div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:10px;">
                                <button type="button" id="bae-logo-download-png" class="bae-btn bae-btn-outline bae-btn-sm">PNG</button>
                                <button type="button" id="bae-logo-download-svg" class="bae-btn bae-btn-outline bae-btn-sm">SVG</button>
                                <span id="bae-logo-download-status" style="font-size:12px;color:var(--text-3);"></span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="logo_url" id="bae-logo-url-hidden" value="<?php echo esc_attr($p['logo_url'] ?? ''); ?>">
                    <!-- Logo Background Checker -->
                    <div id="bae-logo-bg-checker" style="display:none;margin-top:14px;">
                        <div style="font-size:11px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.1em;margin-bottom:10px;">Background Checker</div>
                        <div id="bae-logo-bg-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:8px;"></div>
                    </div>
                </div>

                <!-- Sub-panel B: CSS Builder -->
                <div id="bae-logo-css-builder-panel" style="<?php echo !empty($p['logo_url']) ? 'display:none;' : ''; ?>transition:opacity .3s;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                        <div style="width:22px;height:22px;border-radius:6px;background:var(--bg-3);border:1px solid var(--border-2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span style="font-size:11px;font-weight:800;color:var(--text-2);">B</span>
                        </div>
                        <div style="font-size:12px;font-weight:700;color:var(--text-2);">CSS Logo Builder <span style="font-weight:400;color:var(--text-3);">— Fallback when no logo</span></div>
                    </div>
                    <div class="bae-form-grid">
                        <div class="bae-form-group">
                            <label>Logo Type</label>
                            <select name="logo_style">
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
                                foreach ($styles as $val => $lbl):
                                ?>
                                    <option value="<?php echo $val; ?>" <?php selected($sls, $val); ?>><?php echo $lbl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="bae-form-group">
                            <label>Logo Icon</label>
                            <div id="bae-icon-picker" style="display:grid;grid-template-columns:repeat(8,1fr);gap:6px;padding:10px;background:var(--bg-3);border-radius:12px;border:1px solid var(--border-2);max-height:180px;overflow-y:auto;">
                                <?php
                                $all_icons = bae_get_all_icons();
                                $sli = $p['logo_icon'] ?? '';
                                foreach ($all_icons as $icon_key => $icon_label):
                                    $svg = bae_get_icon_svg_preview($icon_key);
                                ?>
                                <div class="bae-icon-tile <?php echo $sli === $icon_key ? 'selected' : ''; ?>"
                                     onclick="baeSelectIcon(this)"
                                     data-value="<?php echo esc_attr($icon_key); ?>"
                                     title="<?php echo esc_attr($icon_label); ?>"
                                     style="width:100%;aspect-ratio:1;display:flex;align-items:center;justify-content:center;border-radius:6px;cursor:pointer;border:1.5px solid transparent;background:var(--surface);transition:all .15s;padding:4px;">
                                    <?php echo $svg; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="bae-logo-icon-hidden" name="logo_icon" value="<?php echo esc_attr($sli); ?>">
                        </div>
                    </div>
                    <div class="bae-form-grid" style="margin-top:12px;">
                        <div class="bae-form-group">
                            <label for="bae-logo-icon-scale">Icon Scale — <span id="bae-logo-icon-scale-value"><?php echo esc_html((int)($p['logo_icon_scale'] ?? 100)); ?>%</span></label>
                            <input type="range" id="bae-logo-icon-scale" name="logo_icon_scale" min="60" max="160" step="5" value="<?php echo esc_attr((int)($p['logo_icon_scale'] ?? 100)); ?>">
                        </div>
                        <div class="bae-form-group">
                            <label for="bae-logo-spacing">Spacing — <span id="bae-logo-spacing-value"><?php echo esc_html((int)($p['logo_spacing'] ?? 14)); ?>px</span></label>
                            <input type="range" id="bae-logo-spacing" name="logo_spacing" min="6" max="28" step="1" value="<?php echo esc_attr((int)($p['logo_spacing'] ?? 14)); ?>">
                        </div>
                        <div class="bae-form-group">
                            <label>Icon Position</label>
                            <?php $logo_position = $p['logo_position'] ?? 'auto'; ?>
                            <select name="logo_position">
                                <option value="auto" <?php selected($logo_position, 'auto'); ?>>Auto</option>
                                <option value="left" <?php selected($logo_position, 'left'); ?>>Left</option>
                                <option value="top" <?php selected($logo_position, 'top'); ?>>Top</option>
                                <option value="right" <?php selected($logo_position, 'right'); ?>>Right</option>
                            </select>
                        </div>
                        <div class="bae-form-group">
                            <label>Text Case</label>
                            <?php $logo_text_case = $p['logo_text_case'] ?? 'default'; ?>
                            <select name="logo_text_case">
                                <option value="default" <?php selected($logo_text_case, 'default'); ?>>Default</option>
                                <option value="uppercase" <?php selected($logo_text_case, 'uppercase'); ?>>UPPERCASE</option>
                                <option value="title" <?php selected($logo_text_case, 'title'); ?>>Title Case</option>
                                <option value="lowercase" <?php selected($logo_text_case, 'lowercase'); ?>>lowercase</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div><!-- /logo studio grid -->
        </div><!-- /bento card logo -->

        </div><!-- /bae-bento-grid -->

        <style>
            .bae-logo-studio-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 768px) {
    .bae-logo-studio-grid {
        grid-template-columns: 1fr;
    }
}
        .bae-icon-tile:hover { border-color: rgba(243,45,134,.4) !important; background: rgba(243,45,134,.08) !important; }
        .bae-icon-tile.selected { border-color: #F32D86 !important; background: rgba(243,45,134,.15) !important; }
        #bae-icon-picker::-webkit-scrollbar { width: 4px; }
        #bae-icon-picker::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 999px; }
        </style>
    </div>
    <?php
    return ob_get_clean();
}

