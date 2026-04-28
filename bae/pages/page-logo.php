<?php if (!defined('ABSPATH')) exit;
function bae_logo_tab($user_id, $profile) {
    if (empty($profile)) {
        return '<div class="bae-empty">Complete your Brand Profile first.</div>';
    }
    $p             = $profile;
    $current_style = $p['logo_style']    ?? 'wordmark';
    $logo_url      = $p['logo_url']      ?? '';
    $biz_name      = $p['business_name'] ?? 'Your Brand';
    $first_letter  = strtoupper(substr($biz_name, 0, 1));
    $updated       = !empty($p['updated_at']) ? date('M d, Y', strtotime($p['updated_at'])) : 'â€”';
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
    ob_start();
?>
<div class="ls">

    <!-- â•â• LEFT PANEL â•â• -->
    <aside class="ls-left">
        <div class="ls-lh">
            <span class="ls-eye">Studio</span>
            <div class="ls-lh-title">Logo Variants</div>
            <div class="ls-lh-sub"><?php echo count($styles); ?> styles available</div>
        </div>
        <div class="ls-list">
            <?php foreach ($styles as $key => $info):
                $act = ($current_style === $key); ?>
            <div class="ls-row<?php echo $act?' ls-act':''; ?>" data-style="<?php echo esc_attr($key); ?>">
                <div class="ls-row-info">
                    <div class="ls-row-name"><?php echo esc_html($info['label']); ?></div>
                    <div class="ls-row-sub"><?php echo esc_html($info['sub']); ?></div>
                </div>
                <?php if ($act): ?>
                    <span class="ls-badge-act">Active</span>
                <?php else: ?>
                    <button class="ls-apply" data-style="<?php echo esc_attr($key); ?>">Apply</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="ls-lfoot">
            <div class="ls-fs"><div class="ls-fn"><?php echo count($styles); ?></div><div class="ls-fl">Styles</div></div>
            <div class="ls-fdiv"></div>
            <div class="ls-fs"><div class="ls-fn"><?php echo !empty($logo_url)?'1':'0'; ?></div><div class="ls-fl">Uploaded</div></div>
            <div class="ls-fdiv"></div>
            <div class="ls-fs"><div class="ls-fn">2</div><div class="ls-fl">Previews</div></div>
        </div>
    </aside>

    <!-- â•â• CENTER STAGE â•â• -->
    <main class="ls-center">

        <!-- topbar -->
        <div class="ls-top">
            <span class="ls-tag" id="ls-mode-tag"><?php echo !empty($logo_url)?'Image Mode':'CSS Builder'; ?></span>
            <span class="ls-top-name"><?php echo esc_html($biz_name); ?></span>
            <div class="ls-top-acts">
                <button class="ls-ib" id="ls-save-btn" title="Save">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                </button>
                <button class="ls-ib ls-ib-txt" id="ls-top-png">PNG</button>
                <button class="ls-ib ls-ib-txt" id="ls-top-svg">SVG</button>
                <span class="ls-sv-st" id="ls-save-status"></span>
            </div>
        </div>

        <!-- stage wrap -->
        <div class="ls-stage">
            <!-- orbit rings -->
            <svg class="ls-orb" viewBox="0 0 500 500">
                <circle cx="250" cy="250" r="238" fill="none" stroke="rgba(243,45,134,0.12)" stroke-width="1" stroke-dasharray="4 10"/>
                <circle cx="250" cy="250" r="196" fill="none" stroke="rgba(243,45,134,0.07)" stroke-width="1" stroke-dasharray="2 14"/>
            </svg>

            <!-- SAT: light -->
            <div class="ls-sat ls-sat-tl">
                <div class="ls-sat-lbl">Light</div>
                <div class="ls-sat-c ls-sc-light" id="ls-prev-light"><?php echo bae_render_logo_lockup($p,['dark'=>false]); ?></div>
            </div>

            <!-- SAT: dark -->
            <div class="ls-sat ls-sat-tr">
                <div class="ls-sat-lbl">Dark</div>
                <div class="ls-sat-c ls-sc-dark" id="ls-prev-dark"><?php echo bae_render_logo_lockup($p,['dark'=>true]); ?></div>
            </div>

            <!-- SAT: upload -->
            <div class="ls-sat ls-sat-bl">
                <div class="ls-sat-lbl">Upload</div>
                <label class="ls-sat-c ls-sc-up" id="ls-up-sat" title="Click or drag">
                    <?php if(!empty($logo_url)): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="">
                    <?php else: ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>Drop / click</span>
                    <?php endif; ?>
                    <input type="file" id="ls-file-inp" accept="image/*" style="display:none">
                </label>
                <span class="ls-up-st" id="ls-up-status"></span>
            </div>

            <!-- SAT: BG check -->
            <div class="ls-sat ls-sat-br">
                <div class="ls-sat-lbl">BG Check</div>
                <button class="ls-sat-c ls-sc-chk" id="ls-chk-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                </button>
            </div>

            <!-- MAIN CIRCLE -->
            <div class="ls-circ">
                <div class="ls-ticks" id="ls-ticks">
                    <?php for($i=0;$i<60;$i++): ?>
                    <div class="ls-tk" style="transform:rotate(<?php echo $i*6;?>deg)">
                        <div class="ls-tkl<?php echo($i%5===0)?' ls-tkm':'';?>"></div>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="ls-disc">
                    <div class="ls-logo" id="ls-logo">
                        <?php if(!empty($logo_url)): ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="">
                        <?php else: ?>
                            <?php echo bae_render_logo_lockup($p,['dark'=>false]); ?>
                        <?php endif; ?>
                    </div>
                    <div class="ls-disc-lbl" id="ls-disc-lbl"><?php echo esc_html($current_style); ?></div>
                </div>
                <!-- cardinal btns -->
                <button class="ls-rb ls-rb-n" id="ls-rb-refresh" title="Refresh"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></button>
                <button class="ls-rb ls-rb-e" id="ls-rb-chk"     title="BG Check"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg></button>
                <button class="ls-rb ls-rb-s" id="ls-rb-del"     title="Remove logo"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                <button class="ls-rb ls-rb-w" id="ls-rb-exp"     title="Export PNG"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button>
            </div>
        </div>

        <!-- dots -->
        <div class="ls-dots">
            <button class="ls-dot ls-dot-on"></button>
            <button class="ls-dot"></button>
            <button class="ls-dot"></button>
            <button class="ls-dot"></button>
        </div>

        <!-- BG checker -->
        <div class="ls-checker" id="ls-checker" style="display:none">
            <div class="ls-chk-head">Background compatibility</div>
            <div class="ls-chk-grid" id="ls-chk-grid"></div>
        </div>

    </main>

    <!-- â•â• RIGHT PANEL â•â• -->
    <aside class="ls-right">
        <div class="ls-rh">
            <span class="ls-eye">Controls</span>
            <div class="ls-rh-title">CSS Builder</div>
            <div class="ls-rh-sub">Fallback when no logo uploaded</div>
        </div>

        <div class="ls-rscroll">

            <div class="ls-field">
                <label class="ls-lbl">Logo Type</label>
                <select class="ls-sel" id="ls-style-sel">
                    <?php foreach($styles as $val=>$info): ?>
                    <option value="<?php echo $val;?>" <?php selected($current_style,$val);?>><?php echo esc_html($info['label']); ?> â€” <?php echo esc_html($info['sub']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ls-field">
                <label class="ls-lbl">Icon</label>
                <div class="ls-icons" id="ls-icons">
                    <?php
                    $all_icons = bae_get_all_icons();
                    $sel_icon  = $p['logo_icon'] ?? '';
                    foreach($all_icons as $ik=>$il):
                        $svg = bae_get_icon_svg_preview($ik);
                    ?>
                    <div class="ls-ic<?php echo $sel_icon===$ik?' ls-ic-on':'';?>" data-v="<?php echo esc_attr($ik);?>" title="<?php echo esc_attr($il);?>"><?php echo $svg;?></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="ls-icon-hid" value="<?php echo esc_attr($sel_icon);?>">
            </div>

            <div class="ls-field">
                <label class="ls-lbl ls-lbl-row">Icon Scale <span class="ls-lv" id="ls-sv"><?php echo (int)($p['logo_icon_scale']??100);?>%</span></label>
                <input type="range" class="ls-range" id="ls-scale" min="60" max="160" step="5" value="<?php echo (int)($p['logo_icon_scale']??100);?>">
            </div>

            <div class="ls-field">
                <label class="ls-lbl ls-lbl-row">Spacing <span class="ls-lv" id="ls-spv"><?php echo (int)($p['logo_spacing']??14);?>px</span></label>
                <input type="range" class="ls-range" id="ls-spacing" min="6" max="28" step="1" value="<?php echo (int)($p['logo_spacing']??14);?>">
            </div>

            <div class="ls-row2">
                <div class="ls-field ls-fh">
                    <label class="ls-lbl">Position</label>
                    <select class="ls-sel" id="ls-pos">
                        <?php $pos=$p['logo_position']??'auto';?>
                        <option value="auto"  <?php selected($pos,'auto');?>>Auto</option>
                        <option value="left"  <?php selected($pos,'left');?>>Left</option>
                        <option value="top"   <?php selected($pos,'top');?>>Top</option>
                        <option value="right" <?php selected($pos,'right');?>>Right</option>
                    </select>
                </div>
                <div class="ls-field ls-fh">
                    <label class="ls-lbl">Text Case</label>
                    <select class="ls-sel" id="ls-case">
                        <?php $tc=$p['logo_text_case']??'default';?>
                        <option value="default"   <?php selected($tc,'default');?>>Default</option>
                        <option value="uppercase" <?php selected($tc,'uppercase');?>>UPPER</option>
                        <option value="title"     <?php selected($tc,'title');?>>Title</option>
                        <option value="lowercase" <?php selected($tc,'lowercase');?>>lower</option>
                    </select>
                </div>
            </div>

            <!-- metrics -->
            <div class="ls-mets">
                <div class="ls-met">
                    <div class="ls-met-ico"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M20.188 10.934A8.001 8.001 0 1 1 3.811 13.066"/></svg></div>
                    <div><div class="ls-mv" id="ls-mv-style"><?php echo esc_html(ucfirst($current_style));?></div><div class="ls-ml">Active Style</div></div>
                </div>
                <div class="ls-met">
                    <div class="ls-met-ico"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg></div>
                    <div><div class="ls-mv"><?php echo !empty($logo_url)?'Yes':'No';?></div><div class="ls-ml">Logo Uploaded</div></div>
                </div>
                <div class="ls-met">
                    <div class="ls-met-ico"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                    <div><div class="ls-mv"><?php echo $updated;?></div><div class="ls-ml">Last Updated</div></div>
                </div>
            </div>

            <button class="ls-savebtn" id="ls-save-main">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Logo Settings
            </button>

            <div class="ls-exrow">
                <button class="ls-exbtn" id="ls-dl-png">PNG</button>
                <button class="ls-exbtn" id="ls-dl-svg">SVG</button>
                <span class="ls-exst" id="ls-dl-st"></span>
            </div>

        </div><!-- /rscroll -->
    </aside>

    <input type="hidden" id="ls-logo-url" value="<?php echo esc_attr($logo_url);?>">
</div>

<style>
/* â”€â”€ tokens â”€â”€ */
.ls{
    --acc:   #F32D86;
    --acc-a: rgba(243,45,134,.13);
    --acc-b: rgba(243,45,134,.06);
    --white: rgba(255,255,255,0.82);
    --glass: rgba(255,255,255,0.55);
    --glb:   rgba(255,255,255,0.22);
    --bg:    #f0eee9;
    --tx:    #1a1714;
    --tx2:   #6b6761;
    --tx3:   #b0ada6;
    --bd:    rgba(255,255,255,0.6);
    --bds:   rgba(0,0,0,0.07);
    --r:     20px;
    --rs:    12px;
    --font:  'DM Sans',system-ui,sans-serif;
    --mono:  'DM Mono','Fira Mono',monospace;
    font-family:var(--font);
    display:grid;
    grid-template-columns:272px 1fr 260px;
    min-height:100vh;
    background:
        radial-gradient(ellipse 70% 60% at 20% 10%, rgba(243,45,134,.07) 0%,transparent 60%),
        radial-gradient(ellipse 60% 50% at 80% 80%, rgba(100,80,255,.05) 0%,transparent 60%),
        #f0eee9;
}
.ls *,.ls *::before,.ls *::after{box-sizing:border-box;margin:0;padding:0;}

/* â”€â”€ glassmorphism card mixin â”€â”€ */
.ls-glass{
    background:var(--glass);
    backdrop-filter:blur(20px) saturate(1.6);
    -webkit-backdrop-filter:blur(20px) saturate(1.6);
    border:1px solid var(--bd);
    box-shadow:0 4px 24px rgba(0,0,0,0.06),inset 0 1px 0 rgba(255,255,255,0.8);
}

/* â”€â”€ eyebrow â”€â”€ */
.ls-eye{
    display:block;
    font-family:var(--mono);
    font-size:9px;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:var(--acc);
    margin-bottom:4px;
}

/* â•â•â•â•â•â• LEFT â•â•â•â•â•â• */
.ls-left{
    background:var(--glass);
    backdrop-filter:blur(24px) saturate(1.8);
    -webkit-backdrop-filter:blur(24px) saturate(1.8);
    border-right:1px solid var(--bd);
    display:flex;
    flex-direction:column;
    overflow:hidden;
}
.ls-lh{padding:26px 20px 18px;border-bottom:1px solid rgba(0,0,0,.06);}
.ls-lh-title{font-size:18px;font-weight:700;letter-spacing:-.025em;color:var(--tx);}
.ls-lh-sub{font-size:11px;color:var(--tx3);margin-top:2px;}

.ls-list{flex:1;overflow-y:auto;padding:8px 0;scrollbar-width:thin;scrollbar-color:rgba(0,0,0,.1) transparent;}
.ls-row{
    display:flex;align-items:center;gap:12px;
    padding:10px 20px;cursor:pointer;
    border-right:2.5px solid transparent;
    transition:background .12s,border-color .12s;
}
.ls-row:hover{background:rgba(243,45,134,.05);}
.ls-row.ls-act{
    background:rgba(243,45,134,.08);
    border-right-color:var(--acc);
}
.ls-row-info{flex:1;min-width:0;}
.ls-row-name{font-size:13px;font-weight:600;color:var(--tx);}
.ls-row-sub{font-size:10px;color:var(--tx3);margin-top:1px;}
.ls-badge-act{
    font-size:9px;font-weight:700;font-family:var(--mono);
    letter-spacing:.06em;background:var(--acc-a);color:var(--acc);
    padding:3px 9px;border-radius:40px;white-space:nowrap;flex-shrink:0;
}
.ls-apply{
    font-size:10px;font-family:var(--font);font-weight:600;
    background:rgba(255,255,255,.6);border:1px solid rgba(0,0,0,.1);
    border-radius:40px;padding:4px 12px;color:var(--tx2);cursor:pointer;
    transition:all .13s;white-space:nowrap;flex-shrink:0;
}
.ls-apply:hover{background:var(--acc-a);border-color:var(--acc);color:var(--acc);}

.ls-lfoot{border-top:1px solid rgba(0,0,0,.06);padding:16px 20px;display:flex;align-items:center;justify-content:space-around;}
.ls-fs{text-align:center;flex:1;}
.ls-fn{font-size:20px;font-weight:700;letter-spacing:-.03em;color:var(--tx);}
.ls-fl{font-size:9px;font-family:var(--mono);color:var(--tx3);text-transform:uppercase;letter-spacing:.08em;margin-top:1px;}
.ls-fdiv{width:1px;height:26px;background:rgba(0,0,0,.08);}

/* â•â•â•â•â•â• CENTER â•â•â•â•â•â• */
.ls-center{
    display:flex;flex-direction:column;align-items:center;
    padding:22px 16px 28px;gap:14px;position:relative;
}

/* topbar */
.ls-top{
    width:100%;display:flex;align-items:center;gap:10px;
    background:var(--glass);
    backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
    border:1px solid var(--bd);
    border-radius:var(--rs);
    padding:9px 14px;
    box-shadow:0 2px 12px rgba(0,0,0,.05);
}
.ls-top-name{flex:1;text-align:center;font-family:var(--mono);font-size:11.5px;color:var(--tx2);letter-spacing:.04em;}
.ls-tag{
    font-family:var(--mono);font-size:9px;letter-spacing:.12em;text-transform:uppercase;
    background:var(--acc-a);color:var(--acc);padding:5px 11px;border-radius:40px;
    white-space:nowrap;flex-shrink:0;
}
.ls-top-acts{display:flex;align-items:center;gap:6px;}
.ls-ib{
    width:30px;height:30px;
    background:rgba(255,255,255,.7);border:1px solid rgba(0,0,0,.1);
    border-radius:8px;display:flex;align-items:center;justify-content:center;
    cursor:pointer;color:var(--tx2);transition:all .13s;
}
.ls-ib:hover{background:var(--acc-a);border-color:var(--acc);color:var(--acc);}
.ls-ib-txt{font-size:10px;font-weight:700;font-family:var(--mono);}
.ls-sv-st{font-size:10px;font-family:var(--mono);color:var(--tx3);min-width:40px;}

/* stage */
.ls-stage{
    position:relative;width:420px;height:420px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
}
.ls-orb{
    position:absolute;inset:0;width:100%;height:100%;
    pointer-events:none;
    animation:ls-spin 100s linear infinite;
}
@keyframes ls-spin{to{transform:rotate(360deg);}}

/* satellites */
.ls-sat{position:absolute;display:flex;flex-direction:column;align-items:center;gap:5px;}
.ls-sat-tl{top:6px;  left:4px;  animation:ls-flt 4.2s ease-in-out infinite 0s;}
.ls-sat-tr{top:6px;  right:4px; animation:ls-flt 4.2s ease-in-out infinite .9s;}
.ls-sat-bl{bottom:16px;left:4px; animation:ls-flt 4.2s ease-in-out infinite 1.8s;}
.ls-sat-br{bottom:16px;right:4px;animation:ls-flt 4.2s ease-in-out infinite 2.7s;}
@keyframes ls-flt{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}

.ls-sat-lbl{
    font-size:8px;font-family:var(--mono);letter-spacing:.14em;
    text-transform:uppercase;color:var(--tx3);
}
.ls-sat-c{
    width:96px;height:70px;border-radius:18px;
    border:1px solid rgba(255,255,255,.7);
    display:flex;align-items:center;justify-content:center;
    overflow:hidden;transition:all .2s;flex-shrink:0;
    backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);
    box-shadow:0 4px 20px rgba(0,0,0,.08),inset 0 1px 0 rgba(255,255,255,.9);
}
.ls-sat-c:hover{transform:scale(1.06);box-shadow:0 8px 28px rgba(243,45,134,.18);}
.ls-sc-light{background:rgba(255,255,255,.92);}
.ls-sc-dark{background:rgba(14,12,24,.88);}
.ls-sc-up{
    background:rgba(255,255,255,.55);
    flex-direction:column;gap:3px;cursor:pointer;
    color:var(--tx3);font-size:8px;font-family:var(--mono);
}
.ls-sc-up img{max-width:90%;max-height:90%;object-fit:contain;}
.ls-sc-up.drag-over{border-color:var(--acc);background:var(--acc-b);}
.ls-sc-chk{background:rgba(255,255,255,.55);color:var(--tx3);cursor:pointer;}
.ls-sc-chk:hover{background:var(--acc-a);color:var(--acc);}
.ls-up-st{font-size:9px;font-family:var(--mono);color:var(--tx3);text-align:center;min-height:12px;}

/* main circle */
.ls-circ{
    position:absolute;left:50%;top:50%;
    transform:translate(-50%,-50%);
    width:258px;height:258px;border-radius:50%;
}
.ls-ticks{position:absolute;inset:0;border-radius:50%;}
.ls-tk{
    position:absolute;top:0;left:50%;
    width:1px;height:50%;transform-origin:bottom center;
}
.ls-tkl{
    position:absolute;top:0;left:50%;
    transform:translateX(-50%);
    width:1px;height:4px;
    background:rgba(0,0,0,.12);border-radius:2px;
}
.ls-tkm{height:8px;width:1.5px;background:rgba(0,0,0,.22);}
.ls-disc{
    position:absolute;left:50%;top:50%;
    transform:translate(-50%,-50%);
    width:218px;height:218px;border-radius:50%;
    background:rgba(255,255,255,.78);
    backdrop-filter:blur(24px) saturate(1.8);
    -webkit-backdrop-filter:blur(24px) saturate(1.8);
    border:1px solid rgba(255,255,255,.9);
    box-shadow:
        0 0 0 8px rgba(240,238,233,.7),
        0 0 0 9px rgba(243,45,134,.15),
        0 20px 60px rgba(0,0,0,.12),
        inset 0 1px 0 rgba(255,255,255,1);
    display:flex;flex-direction:column;
    align-items:center;justify-content:center;gap:6px;
    transition:box-shadow .35s;
}
.ls-disc:hover{
    box-shadow:
        0 0 0 8px rgba(240,238,233,.7),
        0 0 0 9px rgba(243,45,134,.35),
        0 24px 70px rgba(243,45,134,.14),
        inset 0 1px 0 rgba(255,255,255,1);
}
.ls-logo{
    max-width:158px;max-height:126px;
    display:flex;align-items:center;justify-content:center;overflow:hidden;
}
.ls-logo img{max-width:158px;max-height:116px;object-fit:contain;}
.ls-disc-lbl{
    font-family:var(--mono);font-size:8.5px;
    letter-spacing:.12em;text-transform:uppercase;color:var(--tx3);
    position:absolute;bottom:22px;
}

/* cardinal btns */
.ls-rb{
    position:absolute;width:26px;height:26px;border-radius:50%;
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.9);
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;color:var(--tx2);
    box-shadow:0 2px 10px rgba(0,0,0,.08);
    transition:all .15s;z-index:2;
}
.ls-rb:hover{background:var(--acc-a);border-color:var(--acc);color:var(--acc);transform:scale(1.14);}
.ls-rb-n{top:-13px; left:50%;transform:translateX(-50%);}
.ls-rb-e{right:-13px;top:50%; transform:translateY(-50%);}
.ls-rb-s{bottom:-13px;left:50%;transform:translateX(-50%);}
.ls-rb-w{left:-13px; top:50%; transform:translateY(-50%);}
.ls-rb-n:hover{transform:translateX(-50%) scale(1.14);}
.ls-rb-e:hover{transform:translateY(-50%) scale(1.14);}
.ls-rb-s:hover{transform:translateX(-50%) scale(1.14);}
.ls-rb-w:hover{transform:translateY(-50%) scale(1.14);}

/* dots */
.ls-dots{display:flex;gap:8px;}
.ls-dot{width:7px;height:7px;border-radius:50%;border:none;background:rgba(0,0,0,.15);cursor:pointer;transition:all .15s;}
.ls-dot-on{background:var(--acc);transform:scale(1.35);}

/* checker */
.ls-checker{
    width:100%;
    background:rgba(255,255,255,.6);
    backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
    border:1px solid var(--bd);
    border-radius:var(--r);padding:16px 18px;
    animation:ls-in .2s ease;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
}
@keyframes ls-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.ls-chk-head{font-size:9px;font-family:var(--mono);letter-spacing:.12em;text-transform:uppercase;color:var(--tx3);margin-bottom:11px;}
.ls-chk-grid{display:grid;grid-template-columns:repeat(8,1fr);gap:7px;}
.ls-chk-cell{aspect-ratio:1.4;border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(0,0,0,.06);overflow:hidden;}
.ls-chk-cell img{max-width:88%;max-height:88%;object-fit:contain;}

/* â•â•â•â•â•â• RIGHT â•â•â•â•â•â• */
.ls-right{
    background:var(--glass);
    backdrop-filter:blur(24px) saturate(1.8);
    -webkit-backdrop-filter:blur(24px) saturate(1.8);
    border-left:1px solid var(--bd);
    display:flex;flex-direction:column;overflow:hidden;
}
.ls-rh{padding:26px 20px 18px;border-bottom:1px solid rgba(0,0,0,.06);}
.ls-rh-title{font-size:18px;font-weight:700;letter-spacing:-.025em;color:var(--tx);}
.ls-rh-sub{font-size:11px;color:var(--tx3);margin-top:2px;}

.ls-rscroll{flex:1;overflow-y:auto;padding:18px 20px 24px;scrollbar-width:thin;scrollbar-color:rgba(0,0,0,.1) transparent;display:flex;flex-direction:column;gap:16px;}

.ls-field{display:flex;flex-direction:column;gap:7px;}
.ls-lbl{font-size:9.5px;font-weight:700;font-family:var(--mono);letter-spacing:.1em;text-transform:uppercase;color:var(--tx2);}
.ls-lbl-row{display:flex;justify-content:space-between;}
.ls-lv{font-weight:400;color:var(--acc);}

.ls-sel{
    background:rgba(255,255,255,.65);
    backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.8);
    border-radius:10px;padding:8px 28px 8px 11px;
    font-family:var(--font);font-size:12.5px;color:var(--tx);
    cursor:pointer;appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg width='11' height='11' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='m6 9 6 6 6-6' fill='none' stroke='%23b0ada6' stroke-width='2'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 10px center;
    transition:border-color .13s;
    box-shadow:0 2px 8px rgba(0,0,0,.04),inset 0 1px 0 rgba(255,255,255,.9);
}
.ls-sel:focus{outline:none;border-color:var(--acc);}

.ls-icons{
    display:grid;grid-template-columns:repeat(7,1fr);gap:4px;
    max-height:122px;overflow-y:auto;
    background:rgba(255,255,255,.5);
    backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.75);
    border-radius:11px;padding:7px;
    scrollbar-width:none;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.9);
}
.ls-icons::-webkit-scrollbar{display:none;}
.ls-ic{
    aspect-ratio:1;display:flex;align-items:center;justify-content:center;
    border-radius:7px;border:1px solid transparent;
    background:rgba(255,255,255,.6);cursor:pointer;color:var(--tx2);
    transition:all .11s;
}
.ls-ic:hover{border-color:rgba(243,45,134,.4);background:var(--acc-a);color:var(--acc);}
.ls-ic.ls-ic-on{border-color:var(--acc);background:var(--acc-a);color:var(--acc);}

.ls-range{
    width:100%;height:3px;appearance:none;
    background:rgba(0,0,0,.12);border-radius:3px;outline:none;cursor:pointer;
}
.ls-range::-webkit-slider-thumb{
    appearance:none;width:16px;height:16px;border-radius:50%;
    background:var(--acc);border:2.5px solid #fff;
    box-shadow:0 1px 6px rgba(243,45,134,.35);cursor:pointer;
}
.ls-range::-moz-range-thumb{
    width:16px;height:16px;border-radius:50%;
    background:var(--acc);border:2.5px solid #fff;cursor:pointer;
}

.ls-row2{display:flex;gap:10px;}
.ls-fh{flex:1;}

/* metrics */
.ls-mets{display:flex;flex-direction:column;gap:8px;}
.ls-met{
    background:rgba(255,255,255,.5);
    backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.75);
    border-radius:11px;padding:10px 13px;
    display:flex;align-items:center;gap:11px;
    box-shadow:0 2px 8px rgba(0,0,0,.04),inset 0 1px 0 rgba(255,255,255,.9);
}
.ls-met-ico{
    width:28px;height:28px;background:var(--acc-a);border-radius:7px;
    display:flex;align-items:center;justify-content:center;color:var(--acc);flex-shrink:0;
}
.ls-mv{font-size:12.5px;font-weight:600;color:var(--tx);}
.ls-ml{font-size:9px;font-family:var(--mono);color:var(--tx3);text-transform:uppercase;letter-spacing:.06em;margin-top:1px;}

/* save btn */
.ls-savebtn{
    background:var(--tx);color:#fff;border:none;border-radius:12px;
    padding:12px 18px;font-family:var(--font);font-size:12.5px;font-weight:600;
    cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;
    transition:all .18s;letter-spacing:.01em;
    box-shadow:0 4px 16px rgba(0,0,0,.14);
}
.ls-savebtn:hover{background:var(--acc);box-shadow:0 6px 22px rgba(243,45,134,.3);transform:translateY(-1px);}
.ls-savebtn:active{transform:none;box-shadow:0 2px 8px rgba(0,0,0,.12);}
.ls-savebtn:disabled{opacity:.5;cursor:not-allowed;transform:none;}

/* export row */
.ls-exrow{display:flex;gap:8px;align-items:center;}
.ls-exbtn{
    flex:1;
    background:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.8);
    border-radius:9px;padding:8px 0;
    font-family:var(--mono);font-size:10px;font-weight:700;letter-spacing:.09em;
    color:var(--tx2);cursor:pointer;transition:all .13s;
    box-shadow:0 2px 8px rgba(0,0,0,.04);
}
.ls-exbtn:hover{background:var(--acc-a);border-color:var(--acc);color:var(--acc);}
.ls-exst{font-size:10px;font-family:var(--mono);color:var(--tx3);}

/* â”€â”€ responsive â”€â”€ */
@media(max-width:1080px){
    .ls{grid-template-columns:240px 1fr 236px;}
    .ls-stage{width:380px;height:380px;}
    .ls-circ{width:230px;height:230px;}
    .ls-disc{width:194px;height:194px;}
}
@media(max-width:820px){
    .ls{grid-template-columns:1fr;}
    .ls-left,.ls-right{border:none;border-bottom:1px solid rgba(0,0,0,.07);}
    .ls-stage{width:320px;height:320px;}
    .ls-circ{width:196px;height:196px;}
    .ls-disc{width:162px;height:162px;}
    .ls-sat-c{width:76px;height:56px;border-radius:14px;}
    .ls-chk-grid{grid-template-columns:repeat(4,1fr);}
    .ls-sat-tl{top:2px;left:0;}
    .ls-sat-tr{top:2px;right:0;}
    .ls-sat-bl{bottom:8px;left:0;}
    .ls-sat-br{bottom:8px;right:0;}
}
</style>

<script>
(function(){
'use strict';
var AX=window.ajaxurl||'';
var P=<?php echo wp_json_encode([
    'id'             =>(int)($p['id']??0),
    'business_name'  =>$p['business_name']??'',
    'tagline'        =>$p['tagline']??'',
    'primary_color'  =>$p['primary_color']??'#1a1a2e',
    'secondary_color'=>$p['secondary_color']??'#16213e',
    'accent_color'   =>$p['accent_color']??'#e94560',
    'font_heading'   =>$p['font_heading']??'DM Sans',
    'logo_style'     =>$current_style,
    'logo_icon'      =>$p['logo_icon']??'',
    'logo_icon_scale'=>(int)($p['logo_icon_scale']??100),
    'logo_spacing'   =>(int)($p['logo_spacing']??14),
    'logo_position'  =>$p['logo_position']??'auto',
    'logo_text_case' =>$p['logo_text_case']??'default',
    'logo_url'       =>$p['logo_url']??'',
    'nonce'          =>wp_create_nonce('bae_save_profile'),
]); ?>;

/* helpers */
function el(id){return document.getElementById(id);}
function qsa(s){return document.querySelectorAll(s);}
function on(id,ev,fn){var e=el(id);if(e)e.addEventListener(ev,fn);}

/* â”€â”€ REAL-TIME PREVIEW (fires on every control change, no save needed) â”€â”€ */
var _rt=null;
function sched(){clearTimeout(_rt);_rt=setTimeout(renderPreview,180);}

function renderPreview(){
    var url=el('ls-logo-url').value;
    /* image mode â€” swap instantly from cached img tag */
    if(url){
        var li='<img src="'+url+'" style="max-height:46px;max-width:130px;object-fit:contain;">';
        var dk='<img src="'+url+'" style="max-height:46px;max-width:130px;object-fit:contain;filter:brightness(0) invert(1);opacity:.88;">';
        var ma='<img src="'+url+'" style="max-height:116px;max-width:158px;object-fit:contain;">';
        set('ls-prev-light',li); set('ls-prev-dark',dk); set('ls-logo',ma);
        return;
    }
    /* CSS builder mode â€” AJAX */
    var fd=new FormData();
    fd.append('action','bae_logo_preview');
    fd.append('nonce',P.nonce);
    fd.append('profile_id',P.id);
    fd.append('logo_style',P.logo_style);
    fd.append('logo_icon',P.logo_icon);
    fd.append('logo_icon_scale',P.logo_icon_scale);
    fd.append('logo_spacing',P.logo_spacing);
    fd.append('logo_position',P.logo_position);
    fd.append('logo_text_case',P.logo_text_case);
    fetch(AX,{method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d.success)return;
            if(d.data.light){set('ls-prev-light',d.data.light);set('ls-logo',d.data.light);}
            if(d.data.dark) set('ls-prev-dark',d.data.dark);
        }).catch(function(){});
}
function set(id,html){var e=el(id);if(e)e.innerHTML=html;}

/* â”€â”€ CONTROLS â€” every change triggers real-time preview â”€â”€ */
on('ls-style-sel','change',function(){
    P.logo_style=this.value;
    var cap=this.value.charAt(0).toUpperCase()+this.value.slice(1);
    set('ls-disc-lbl',this.value);
    set('ls-mv-style',cap);
    syncList(this.value);
    sched();
});
on('ls-pos', 'change',function(){P.logo_position=this.value;sched();});
on('ls-case','change',function(){P.logo_text_case=this.value;sched();});

on('ls-scale','input',function(){
    P.logo_icon_scale=parseInt(this.value,10);
    set('ls-sv',this.value+'%'); sched();
});
on('ls-spacing','input',function(){
    P.logo_spacing=parseInt(this.value,10);
    set('ls-spv',this.value+'px'); sched();
});

/* icon picker */
qsa('.ls-ic').forEach(function(t){
    t.addEventListener('click',function(){
        qsa('.ls-ic').forEach(function(i){i.classList.remove('ls-ic-on');});
        t.classList.add('ls-ic-on');
        P.logo_icon=t.dataset.v||'';
        if(el('ls-icon-hid'))el('ls-icon-hid').value=P.logo_icon;
        sched();
    });
});

/* â”€â”€ VARIANT LIST SYNC â”€â”€ */
function syncList(style){
    qsa('.ls-row').forEach(function(row){
        var s=row.dataset.style, now=(s===style);
        row.classList.toggle('ls-act',now);
        var m=row.querySelector('.ls-badge-act,.ls-apply');
        if(!m)return;
        if(now && m.classList.contains('ls-apply')){
            var sp=document.createElement('span');
            sp.className='ls-badge-act';sp.textContent='Active';
            m.replaceWith(sp);
        } else if(!now && m.classList.contains('ls-badge-act')){
            var btn=document.createElement('button');
            btn.className='ls-apply';btn.dataset.style=s;btn.textContent='Apply';
            btn.addEventListener('click',applyVariant);
            m.replaceWith(btn);
        }
    });
}
function applyVariant(e){
    var s=e.currentTarget.dataset.style;
    var sel=el('ls-style-sel');
    if(sel){sel.value=s;sel.dispatchEvent(new Event('change'));}
}
qsa('.ls-apply').forEach(function(b){b.addEventListener('click',applyVariant);});

/* â”€â”€ SAVE â”€â”€ */
function doSave(extra){
    var sb=el('ls-save-main'),st=el('ls-save-status');
    if(sb){sb.disabled=true;sb.textContent='Savingâ€¦';}
    var fd=new FormData();
    var fields={
        action:'bae_save_profile',nonce:P.nonce,profile_id:P.id,
        logo_style:P.logo_style,logo_icon:P.logo_icon,
        logo_icon_scale:P.logo_icon_scale,logo_spacing:P.logo_spacing,
        logo_position:P.logo_position,logo_text_case:P.logo_text_case,
        logo_url:el('ls-logo-url').value,
        business_name:P.business_name,tagline:P.tagline,
        primary_color:P.primary_color,secondary_color:P.secondary_color,
        accent_color:P.accent_color,font_heading:P.font_heading
    };
    if(extra)Object.assign(fields,extra);
    Object.keys(fields).forEach(function(k){fd.append(k,fields[k]);});
    fetch(AX,{method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if(sb){
                sb.disabled=false;
                sb.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Logo Settings';
            }
            if(st){st.textContent=d.success?'âœ“ Saved':'Failed';st.style.color=d.success?'#34d399':'#fb7185';setTimeout(function(){st.textContent='';},3000);}
            if(d.success&&typeof window.baeToast==='function')window.baeToast('Logo saved.','success');
        })
        .catch(function(){if(sb){sb.disabled=false;sb.textContent='Save Logo Settings';}});
}
on('ls-save-main','click',function(){doSave();});
on('ls-save-btn', 'click',function(){doSave();});

/* â”€â”€ UPLOAD â”€â”€ */
function uploadFile(file){
    if(!file)return;
    var ok=['image/png','image/jpeg','image/jpg','image/svg+xml','image/gif','image/webp'];
    if(!ok.includes(file.type)){upSt('PNG/JPG/SVG only','#fb7185');return;}
    if(file.size>2*1024*1024){upSt('Max 2 MB','#fb7185');return;}
    upSt('Uploadingâ€¦','var(--tx3)');
    var fd=new FormData();
    fd.append('action','bae_upload_logo');fd.append('nonce',P.nonce);
    fd.append('logo_file',file);if(P.id)fd.append('profile_id',P.id);
    fetch(AX,{method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if(d.success&&d.data&&d.data.url){
                var url=d.data.url;
                el('ls-logo-url').value=url; P.logo_url=url;
                rebuildUpSat(url);
                if(el('ls-mode-tag'))el('ls-mode-tag').textContent='Image Mode';
                upSt('âœ“ Uploaded','#34d399');
                renderPreview();
                doSave({logo_url:url});
                if(typeof window.baeToast==='function')window.baeToast('Logo uploaded.','success');
            } else {
                upSt((d.data&&d.data.message)||'Upload failed','#fb7185');
            }
        }).catch(function(){upSt('Network error','#fb7185');});
}
function upSt(m,c){var e=el('ls-up-status');if(!e)return;e.textContent=m;e.style.color=c;if(m[0]==='âœ“')setTimeout(function(){e.textContent='';},3000);}
function rebuildUpSat(url){
    var sat=el('ls-up-sat');if(!sat)return;
    sat.innerHTML='<img src="'+url+'" style="max-width:90%;max-height:90%;object-fit:contain;"><input type="file" id="ls-file-inp" accept="image/*" style="display:none">';
    bindUp();
}
function bindUp(){
    var fi=el('ls-file-inp');if(fi)fi.addEventListener('change',function(){uploadFile(this.files[0]);});
    var sat=el('ls-up-sat');if(!sat)return;
    sat.addEventListener('dragover',function(e){e.preventDefault();sat.classList.add('drag-over');});
    sat.addEventListener('dragleave',function(){sat.classList.remove('drag-over');});
    sat.addEventListener('drop',function(e){e.preventDefault();sat.classList.remove('drag-over');uploadFile(e.dataTransfer.files[0]);});
}
bindUp();

/* â”€â”€ RADIAL BUTTONS â”€â”€ */
on('ls-rb-refresh','click',renderPreview);
on('ls-rb-exp',    'click',function(){doDownload('png');});
on('ls-rb-chk',    'click',toggleChecker);
on('ls-chk-btn',   'click',toggleChecker);
on('ls-rb-del',    'click',function(){
    el('ls-logo-url').value='';P.logo_url='';
    var sat=el('ls-up-sat');
    if(sat){
        sat.innerHTML='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg><span>Drop / click</span><input type="file" id="ls-file-inp" accept="image/*" style="display:none">';
        bindUp();
    }
    if(el('ls-mode-tag'))el('ls-mode-tag').textContent='CSS Builder';
    renderPreview();
    doSave({logo_url:''});
});

/* â”€â”€ BG CHECKER â”€â”€ */
var _co=false;
function toggleChecker(){
    var p=el('ls-checker');if(!p)return;
    _co=!_co;p.style.display=_co?'block':'none';
    if(_co)buildChecker();
}
function buildChecker(){
    var g=el('ls-chk-grid');if(!g||g.children.length)return;
    var url=el('ls-logo-url').value;
    var area=el('ls-logo');
    ['#ffffff','#f5f3ef','#1a1714','#0e0c18','#F32D86','#2d1066','#1d3a6b','#c2410c'].forEach(function(bg){
        var c=document.createElement('div');c.className='ls-chk-cell';c.style.background=bg;
        c.innerHTML=url?'<img src="'+url+'" style="max-width:88%;max-height:88%;object-fit:contain;">':(area?area.innerHTML:'');
        g.appendChild(c);
    });
}

/* â”€â”€ DOWNLOAD â”€â”€ */
function doDownload(fmt){
    var st=el('ls-dl-st');if(st)st.textContent='Preparingâ€¦';
    var url=el('ls-logo-url').value,nm=P.business_name||'logo';
    if(url&&fmt==='png'){
        var a=document.createElement('a');a.href=url;a.download=nm+'.png';a.click();
        done(st);return;
    }
    var area=el('ls-logo');if(!area){if(st)st.textContent='Nothing to export.';return;}
    if(fmt==='svg'){
        var b=new Blob(['<svg xmlns="http://www.w3.org/2000/svg" width="400" height="100"><foreignObject width="400" height="100"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:12px;display:flex;align-items:center;">'+area.innerHTML+'</body></foreignObject></svg>'],{type:'image/svg+xml'});
        var u=URL.createObjectURL(b),a=document.createElement('a');a.href=u;a.download=nm+'.svg';a.click();
        setTimeout(function(){URL.revokeObjectURL(u);},2000);done(st);
    } else {
        var cv=document.createElement('canvas');cv.width=800;cv.height=200;
        var ctx=cv.getContext('2d');ctx.fillStyle='#fff';ctx.fillRect(0,0,800,200);
        var sb=new Blob(['<svg xmlns="http://www.w3.org/2000/svg" width="800" height="200"><foreignObject width="800" height="200"><body xmlns="http://www.w3.org/1999/xhtml" style="margin:0;padding:24px;display:flex;align-items:center;zoom:2;">'+area.innerHTML+'</body></foreignObject></svg>'],{type:'image/svg+xml'});
        var bu=URL.createObjectURL(sb),img=new Image();img.crossOrigin='anonymous';
        img.onload=function(){
            ctx.drawImage(img,0,0);URL.revokeObjectURL(bu);
            var a=document.createElement('a');a.download=nm+'.png';a.href=cv.toDataURL('image/png');a.click();done(st);
        };
        img.onerror=function(){if(st){st.textContent='Try SVG instead';st.style.color='#fb7185';}};
        img.src=bu;
    }
}
function done(st){if(!st)return;st.textContent='âœ“ Done';st.style.color='#34d399';setTimeout(function(){st.textContent='';},2500);}
on('ls-dl-png', 'click',function(){doDownload('png');});
on('ls-dl-svg', 'click',function(){doDownload('svg');});
on('ls-top-png','click',function(){doDownload('png');});
on('ls-top-svg','click',function(){doDownload('svg');});

/* â”€â”€ MODE DOTS â”€â”€ */
qsa('.ls-dot').forEach(function(d){
    d.addEventListener('click',function(){
        qsa('.ls-dot').forEach(function(x){x.classList.remove('ls-dot-on');});
        d.classList.add('ls-dot-on');
    });
});

/* â”€â”€ TICK ANIMATION â”€â”€ */
var tk=el('ls-ticks'),ang=0;
if(tk)setInterval(function(){ang+=0.16;tk.style.transform='rotate('+ang+'deg)';},50);

})();
</script>
<?php
    return bae_wrap_tab_panel(ob_get_clean());
}
