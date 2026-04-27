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

        /* Live preview */
        #bae-logo-live-preview { margin-top:20px; padding:20px; background:var(--bg-3); border:1px solid var(--border-2); border-radius:16px; }
        #bae-logo-live-preview-label { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:12px; }
        .bae-logo-preview-swatch { display:flex; align-items:center; justify-content:center; border-radius:12px; padding:22px 28px; min-height:80px; }
        .bae-logo-preview-swatch.light-bg { background:#ffffff; border:1px solid #e5e7eb; }
        .bae-logo-preview-swatch.dark-bg  { background:#0f0e17; border:1px solid rgba(255,255,255,.08); }

        /* Save button */
        #bae-logo-save-btn { margin-top:18px; display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#c4196a,#F32D86); color:#fff; border:none; border-radius:12px; padding:12px 28px; font-size:14px; font-weight:700; font-family:'Geist',sans-serif; cursor:pointer; transition:all .2s; box-shadow:0 6px 20px rgba(195,25,106,.35); }
        #bae-logo-save-btn:hover { transform:translateY(-1px); box-shadow:0 10px 28px rgba(195,25,106,.45); }
        #bae-logo-save-btn:disabled { opacity:.5; cursor:not-allowed; transform:none; }

        /* Drag hover */
        #bae-logo-upload-area.drag-over { border-color:#F32D86 !important; background:rgba(243,45,134,.06); }
        </style>

        <!-- Live Preview -->
        <div id="bae-logo-live-preview">
            <div id="bae-logo-live-preview-label">Live Preview</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="bae-logo-preview-swatch light-bg">
                    <div id="bae-logo-lockup-light"><?php echo bae_render_logo_lockup($p, ['dark'=>false]); ?></div>
                </div>
                <div class="bae-logo-preview-swatch dark-bg">
                    <div id="bae-logo-lockup-dark"><?php echo bae_render_logo_lockup($p, ['dark'=>true]); ?></div>
                </div>
            </div>
        </div>

        <!-- Save -->
        <div style="margin-top:4px;">
            <button type="button" id="bae-logo-save-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Logo Settings
            </button>
            <span id="bae-logo-save-status" style="font-size:12px;color:var(--text-3);margin-left:12px;"></span>
        </div>

    </div>

    <script>
    (function(){
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

        /* ── ICON PICKER ── */
        window.baeSelectIcon = function(el) {
            document.querySelectorAll('.bae-icon-tile').forEach(function(t){ t.classList.remove('selected'); });
            el.classList.add('selected');
            var val = el.dataset.value || '';
            document.getElementById('bae-logo-icon-hidden').value = val;
            baeLogoProfile.logo_icon = val;
            baeRequestPreviewRefresh();
        };

        /* ── CONTROL BINDING ── */
        function bindControl(selector, profileKey, isRange, unit) {
            document.querySelectorAll(selector).forEach(function(el) {
                el.addEventListener('change', function() {
                    baeLogoProfile[profileKey] = isRange ? parseInt(this.value,10) : this.value;
                    baeRequestPreviewRefresh();
                });
                if (isRange) {
                    el.addEventListener('input', function() {
                        baeLogoProfile[profileKey] = parseInt(this.value,10);
                        var v = document.getElementById(el.id+'-value');
                        if (v) v.textContent = this.value + (unit||'');
                        baeRequestPreviewRefresh();
                    });
                }
            });
        }
        bindControl('[name="logo_style"]',    'logo_style',     false);
        bindControl('[name="logo_position"]', 'logo_position',  false);
        bindControl('[name="logo_text_case"]','logo_text_case', false);
        bindControl('#bae-logo-icon-scale',   'logo_icon_scale',true, '%');
        bindControl('#bae-logo-spacing',      'logo_spacing',   true, 'px');

        /* ── LIVE PREVIEW (debounced) ── */
        var previewTimer = null;
        function baeRequestPreviewRefresh() {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(baeRefreshPreview, 220);
        }
        function baeRefreshPreview() {
            var logoUrl = document.getElementById('bae-logo-url-hidden').value;
            if (logoUrl) {
                var light = '<img src="'+logoUrl+'" style="max-height:52px;max-width:160px;object-fit:contain;" alt="logo">';
                var dark  = '<img src="'+logoUrl+'" style="max-height:52px;max-width:160px;object-fit:contain;filter:brightness(0) invert(1);opacity:.92;" alt="logo">';
                var ll = document.getElementById('bae-logo-lockup-light');
                var ld = document.getElementById('bae-logo-lockup-dark');
                if (ll) ll.innerHTML = light;
                if (ld) ld.innerHTML = dark;
                return;
            }
            var fd = new FormData();
            fd.append('action','bae_logo_preview');
            fd.append('nonce', baeLogoProfile.nonce);
            fd.append('profile_id',      baeLogoProfile.id);
            fd.append('logo_style',      baeLogoProfile.logo_style);
            fd.append('logo_icon',       baeLogoProfile.logo_icon);
            fd.append('logo_icon_scale', baeLogoProfile.logo_icon_scale);
            fd.append('logo_spacing',    baeLogoProfile.logo_spacing);
            fd.append('logo_position',   baeLogoProfile.logo_position);
            fd.append('logo_text_case',  baeLogoProfile.logo_text_case);
            fetch(ajaxurl,{method:'POST',body:fd})
                .then(function(r){return r.json();})
                .then(function(data){
                    if (!data.success) return;
                    var ll = document.getElementById('bae-logo-lockup-light');
                    var ld = document.getElementById('bae-logo-lockup-dark');
                    if (ll && data.data.light) ll.innerHTML = data.data.light;
                    if (ld && data.data.dark)  ld.innerHTML = data.data.dark;
                }).catch(function(){});
        }

        /* ── SAVE ── */
        var saveBtn    = document.getElementById('bae-logo-save-btn');
        var saveStatus = document.getElementById('bae-logo-save-status');
        var saveSVG    = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> ';

        function baeLogoSave(extra) {
            if (saveBtn) { saveBtn.disabled=true; saveBtn.textContent='Saving…'; }
            var fd = new FormData();
            fd.append('action','bae_save_profile');
            fd.append('nonce', baeLogoProfile.nonce);
            fd.append('profile_id',      baeLogoProfile.id);
            fd.append('logo_style',      baeLogoProfile.logo_style);
            fd.append('logo_icon',       baeLogoProfile.logo_icon);
            fd.append('logo_icon_scale', baeLogoProfile.logo_icon_scale);
            fd.append('logo_spacing',    baeLogoProfile.logo_spacing);
            fd.append('logo_position',   baeLogoProfile.logo_position);
            fd.append('logo_text_case',  baeLogoProfile.logo_text_case);
            fd.append('logo_url',        document.getElementById('bae-logo-url-hidden').value);
            fd.append('business_name',   baeLogoProfile.business_name);
            fd.append('tagline',         baeLogoProfile.tagline);
            fd.append('primary_color',   baeLogoProfile.primary_color);
            fd.append('secondary_color', baeLogoProfile.secondary_color);
            fd.append('accent_color',    baeLogoProfile.accent_color);
            fd.append('font_heading',    baeLogoProfile.font_heading);
            if (extra) Object.keys(extra).forEach(function(k){ fd.append(k,extra[k]); });
            fetch(ajaxurl,{method:'POST',body:fd})
                .then(function(r){return r.json();})
                .then(function(data){
                    if (saveBtn){ saveBtn.disabled=false; saveBtn.innerHTML=saveSVG+'Save Logo Settings'; }
                    if (saveStatus){
                        saveStatus.textContent = data.success ? '✓ Saved' : (data.data&&data.data.message?data.data.message:'Save failed.');
                        saveStatus.style.color = data.success ? '#34d399' : '#fb7185';
                        setTimeout(function(){ if(saveStatus) saveStatus.textContent=''; },3000);
                    }
                    if (data.success && typeof window.baeToast==='function') window.baeToast('Logo settings saved.','success');
                })
                .catch(function(){
                    if (saveBtn){ saveBtn.disabled=false; saveBtn.innerHTML=saveSVG+'Save Logo Settings'; }
                    if (saveStatus){ saveStatus.textContent='Network error.'; saveStatus.style.color='#fb7185'; }
                });
        }
        if (saveBtn) saveBtn.addEventListener('click', function(){ baeLogoSave(); });

        /* ── FILE UPLOAD ── */
        var fileInput    = document.getElementById('bae-logo-file-input');
        var uploadArea   = document.getElementById('bae-logo-upload-area');
        var uploadStatus = document.getElementById('bae-logo-upload-status');

        function baeUploadFile(file) {
            if (!file) return;
            var allowed=['image/png','image/jpeg','image/jpg','image/svg+xml','image/gif','image/webp'];
            if (!allowed.includes(file.type)){ if(uploadStatus){uploadStatus.textContent='PNG, JPG, SVG only.';uploadStatus.style.color='#fb7185';} return; }
            if (file.size>2*1024*1024){ if(uploadStatus){uploadStatus.textContent='Max 2MB.';uploadStatus.style.color='#fb7185';} return; }
            if (uploadStatus){ uploadStatus.textContent='Uploading…'; uploadStatus.style.color='var(--text-3)'; }
            var fd=new FormData();
            fd.append('action','bae_upload_logo');
            fd.append('nonce', baeLogoProfile.nonce);
            fd.append('logo_file',file);
            if (baeLogoProfile.id) fd.append('profile_id',baeLogoProfile.id);
            fetch(ajaxurl,{method:'POST',body:fd})
                .then(function(r){return r.json();})
                .then(function(data){
                    if (data.success&&data.data&&data.data.url){
                        var url=data.data.url;
                        document.getElementById('bae-logo-url-hidden').value=url;
                        baeLogoProfile.logo_url=url;
                        var wrap=document.getElementById('bae-logo-preview-wrap');
                        if(wrap) wrap.innerHTML='<img id="bae-logo-preview-img" src="'+url+'" style="max-width:100%;max-height:100%;object-fit:contain;">';
                        baeShowUploadedActions();
                        var builder=document.getElementById('bae-logo-css-builder-panel');
                        if(builder) builder.style.display='none';
                        if(uploadStatus){uploadStatus.textContent='✓ Uploaded';uploadStatus.style.color='#34d399';}
                        baeRefreshPreview();
                        baeLogoSave({logo_url:url});
                    } else {
                        if(uploadStatus){uploadStatus.textContent=(data.data&&data.data.message)?data.data.message:'Upload failed.';uploadStatus.style.color='#fb7185';}
                    }
                }).catch(function(){ if(uploadStatus){uploadStatus.textContent='Network error.';uploadStatus.style.color='#fb7185';} });
        }

        function baeShowUploadedActions() {
            if (document.getElementById('bae-logo-remove-btn')) return;
            var ref = document.querySelector('#bae-logo-upload-area [style*="gap:8px"][style*="flex-wrap"]');
            if (!ref) return;
            var div=document.createElement('div');
            div.innerHTML='<button type="button" id="bae-logo-remove-btn" style="background:none;border:1px solid rgba(244,63,94,.3);border-radius:9px;padding:8px 14px;font-size:12px;font-weight:600;color:#fb7185;cursor:pointer;font-family:\'Geist\',sans-serif;">Remove</button>'
                         +'<button type="button" id="bae-logo-check-btn" class="bae-btn bae-btn-outline bae-btn-sm" style="display:flex;align-items:center;gap:5px;"><svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg> Check BG</button>';
            while(div.firstChild) ref.appendChild(div.firstChild);
            baeBindUploadedActions();
        }

        function baeBindUploadedActions() {
            var rb=document.getElementById('bae-logo-remove-btn');
            if(rb&&!rb._b){
                rb._b=true;
                rb.addEventListener('click',function(){
                    document.getElementById('bae-logo-url-hidden').value='';
                    baeLogoProfile.logo_url='';
                    var wrap=document.getElementById('bae-logo-preview-wrap');
                    if(wrap) wrap.innerHTML='<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';
                    var builder=document.getElementById('bae-logo-css-builder-panel');
                    if(builder) builder.style.display='';
                    var checker=document.getElementById('bae-logo-bg-checker');
                    if(checker) checker.style.display='none';
                    this.remove(); var cb=document.getElementById('bae-logo-check-btn'); if(cb) cb.remove();
                    baeRequestPreviewRefresh();
                    baeLogoSave({logo_url:''});
                });
            }
            var cb=document.getElementById('bae-logo-check-btn');
            if(cb&&!cb._b){
                cb._b=true;
                cb.addEventListener('click',function(){
                    var logoUrl=document.getElementById('bae-logo-url-hidden').value;
                    if(!logoUrl) return;
                    var checker=document.getElementById('bae-logo-bg-checker');
                    var grid=document.getElementById('bae-logo-bg-grid');
                    if(!checker||!grid) return;
                    checker.style.display=checker.style.display==='none'?'':'none';
                    if(checker.style.display===''&&grid.children.length===0){
                        ['#ffffff','#000000','#f5f5f5','#1a1a2e','#F32D86','#2d1066','#ea580c','#16213e'].forEach(function(bg){
                            var cell=document.createElement('div');
                            cell.style.cssText='background:'+bg+';border-radius:8px;padding:10px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(128,128,128,.2);';
                            cell.innerHTML='<img src="'+logoUrl+'" style="max-width:70px;max-height:40px;object-fit:contain;">';
                            grid.appendChild(cell);
                        });
                    }
                });
            }
        }

        if(fileInput) fileInput.addEventListener('change',function(){ baeUploadFile(this.files[0]); });

        if(uploadArea){
            uploadArea.addEventListener('dragover',function(e){e.preventDefault();this.classList.add('drag-over');});
            uploadArea.addEventListener('dragleave',function(){this.classList.remove('drag-over');});
            uploadArea.addEventListener('drop',function(e){e.preventDefault();this.classList.remove('drag-over');baeUploadFile(e.dataTransfer.files&&e.dataTransfer.files[0]);});
        }

        // Bind any server-rendered remove/check buttons (for users who already have a logo)
        baeBindUploadedActions();

        /* ── DOWNLOAD ── */
        function baeLogoDownload(fmt) {
            var st=document.getElementById('bae-logo-download-status');
            var logoUrl=document.getElementById('bae-logo-url-hidden').value;
            if(logoUrl&&fmt==='png'){
                var a=document.createElement('a'); a.href=logoUrl; a.download=(baeLogoProfile.business_name||'logo')+'.png'; a.click(); return;
            }
            if(st){st.textContent='Preparing…';st.style.color='var(--text-3)';}
            var lockupEl=document.getElementById('bae-logo-lockup-light');
            if(!lockupEl){if(st) st.textContent='Nothing to export.'; return;}
            var name=baeLogoProfile.business_name||'logo';
            if(fmt==='svg'){
                var blob=new Blob(['<svg xmlns="http://www.w3.org/2000/svg" width="320" height="80"><foreignObject width="320" height="80"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:14px;display:flex;align-items:center;">'+lockupEl.innerHTML+'</body></foreignObject></svg>'],{type:'image/svg+xml'});
                var url=URL.createObjectURL(blob);
                var a=document.createElement('a'); a.href=url; a.download=name+'.svg'; a.click();
                setTimeout(function(){URL.revokeObjectURL(url);},2000);
                if(st){st.textContent='✓ Downloaded';setTimeout(function(){if(st)st.textContent='';},2500);}
            } else {
                var canvas=document.createElement('canvas'); canvas.width=640; canvas.height=160;
                var ctx=canvas.getContext('2d'); ctx.fillStyle='#ffffff'; ctx.fillRect(0,0,640,160);
                var svgBlob=new Blob(['<svg xmlns="http://www.w3.org/2000/svg" width="640" height="160"><foreignObject width="640" height="160"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:24px;display:flex;align-items:center;zoom:2;">'+lockupEl.innerHTML+'</body></foreignObject></svg>'],{type:'image/svg+xml'});
                var burl=URL.createObjectURL(svgBlob);
                var img=document.createElement('img'); img.crossOrigin='anonymous';
                img.onload=function(){
                    ctx.drawImage(img,0,0); URL.revokeObjectURL(burl);
                    var a=document.createElement('a'); a.download=name+'.png'; a.href=canvas.toDataURL('image/png'); a.click();
                    if(st){st.textContent='✓ Downloaded';setTimeout(function(){if(st)st.textContent='';},2500);}
                };
                img.onerror=function(){ if(st){st.textContent='PNG failed — try SVG.';st.style.color='#fb7185';} };
                img.src=burl;
            }
        }
        var pngBtn=document.getElementById('bae-logo-download-png');
        var svgBtn=document.getElementById('bae-logo-download-svg');
        if(pngBtn) pngBtn.addEventListener('click',function(){baeLogoDownload('png');});
        if(svgBtn) svgBtn.addEventListener('click',function(){baeLogoDownload('svg');});

    })();
    </script>
    <?php
    return ob_get_clean();
}

