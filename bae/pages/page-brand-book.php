<?php if (!defined('ABSPATH')) exit;
function bae_brand_book_tab($user_id, $profile) {
    if (empty($profile)) {
        return '<div class="bae-empty"><strong>Complete your <a href="?tab=overview">Brand Profile</a> first.</strong></div>';
    }

    $templates = bae_get_book_templates();
    $saved     = get_transient('bae_bb_' . md5($profile['ticket'] ?? $user_id)) ?: 'modern_elegant';
    if (!isset($templates[$saved])) $saved = 'modern_elegant';
    $nonce    = wp_create_nonce('bae_save_profile');
    $is_free  = bae_get_user_plan($user_id, $profile) === 'free';

    // Group by category
    $cats = array();
    foreach ($templates as $id => $t) {
        $cats[$t['cat']][$id] = $t;
    }

    $out = '';
    $out .= '<style>
.bae-bb-wrap{display:grid;grid-template-columns:260px 1fr;gap:14px;align-items:stretch}
.bae-bb-sb{position:sticky;top:20px;max-height:82vh;display:flex;flex-direction:column;gap:10px}
.bae-bb-sb::-webkit-scrollbar{width:3px}.bae-bb-sb::-webkit-scrollbar-thumb{background:var(--border-2);border-radius:3px}
.bae-bb-sb-head{display:flex;align-items:center;justify-content:space-between;gap:10px}
.bae-bb-sb-body{overflow-y:auto;padding-right:4px;flex:1;min-height:0;max-height:70vh}
.bae-bb-sb-body::-webkit-scrollbar{width:3px}.bae-bb-sb-body::-webkit-scrollbar-thumb{background:var(--border-2);border-radius:3px}
.bae-bb-cl{font-size:9px;font-weight:800;color:var(--brand-soft);text-transform:uppercase;letter-spacing:.12em;margin:12px 0 6px}
.bae-bb-tg{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.bae-bb-tc{border:1.5px solid var(--border);border-radius:10px;overflow:hidden;cursor:pointer;transition:all .15s;background:var(--surface)}
.bae-bb-tc:hover{border-color:var(--border-2);transform:translateY(-1px)}
.bae-bb-tc.active{border-color:#F32D86;box-shadow:0 0 0 2px rgba(243,45,134,.15)}
.bae-bb-th{height:44px;display:flex;align-items:center;justify-content:center;padding:4px}
.bae-bb-th span{font-size:7px;font-weight:900;text-align:center;line-height:1.3}
.bae-bb-tn{font-size:9px;font-weight:700;color:var(--text);padding:5px 7px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.bae-bb-main{display:flex;flex-direction:column;gap:10px;min-width:0}
.bae-bb-stage{display:flex;align-items:center;justify-content:center;padding:14px;background:linear-gradient(180deg, rgba(0,0,0,.02), rgba(0,0,0,.00));}
.bae-bb-viewport{position:relative; width:100%; max-width:1100px; display:flex;align-items:center;justify-content:center;}
.bae-bb-canvas{position:relative;width:100%;display:flex;align-items:center;justify-content:center;}
.bae-bb-page{width:100%;max-width:1100px;aspect-ratio:11/8.5!important;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.18);overflow:hidden;background:transparent;transform-origin:top center;transform:scale(0.65);margin-bottom:-25%}
.bae-bb-page .bae-bp{width:100%!important;height:100%!important;aspect-ratio:auto!important;box-shadow:none!important;border-radius:0!important}
/* Fill the canvas better (less tiny content + less dead space). */
.bae-bb-page .bae-bpi{padding:26px!important;font-size:14px!important;height:100%!important;box-sizing:border-box!important}

.bae-bb-nav{position:absolute;top:50%;transform:translateY(-50%);display:flex;justify-content:space-between;left:-10px;right:-10px;pointer-events:none}
.bae-bb-nav button{pointer-events:auto}
.bae-bb-nav-btn{width:34px;height:34px;border-radius:999px;border:1px solid var(--border);background:rgba(255,255,255,.92);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 22px rgba(0,0,0,.14);cursor:pointer}
.bae-bb-nav-btn:disabled{opacity:.45;cursor:not-allowed}

.bae-bb-film{display:flex;gap:8px;overflow-x:auto;padding:10px 10px 12px;border-top:1px solid var(--border);background:var(--surface);scrollbar-width:thin}
.bae-bb-film::-webkit-scrollbar{height:6px}
.bae-bb-film::-webkit-scrollbar-thumb{background:var(--border-2);border-radius:6px}
.bae-bb-thumb{flex:0 0 auto;width:120px}
.bae-bb-thumb-btn{width:120px;border:1px solid var(--border);border-radius:10px;background:#fff;cursor:pointer;padding:6px;display:flex;flex-direction:column;gap:6px;transition:all .15s}
.bae-bb-thumb-btn:hover{transform:translateY(-1px);border-color:var(--border-2)}
.bae-bb-thumb-btn.active{border-color:#F32D86;box-shadow:0 0 0 2px rgba(243,45,134,.15)}
.bae-bb-thumb-mini{position:relative;width:100%;aspect-ratio:11/8.5;border-radius:6px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.12);background:transparent}
.bae-bb-thumb-mini .bae-bp{width:1100px!important;height:850px!important;box-shadow:none!important;border-radius:0!important;transform-origin:top left!important;transform:scale(0.098)!important}
.bae-bb-thumb-mini .bae-bpi{padding:26px!important;font-size:14px!important;height:100%!important;box-sizing:border-box!important}
.bae-bb-thumb-lbl{font-size:10px;color:var(--text-3);text-align:center}

.bae-bb-pages{display:none}
#bae-bb-pt{display:none}
@media print{body>*:not(#bae-bb-pt){display:none!important}#bae-bb-pt{display:block!important}.bae-bp{page-break-after:always;width:210mm;min-height:297mm;padding:18mm;box-sizing:border-box}}
@media(max-width:980px){.bae-bb-wrap{grid-template-columns:1fr}.bae-bb-sb{position:static;max-height:none}}
</style>';

    $out .= '<div class="bae-bb-wrap">';

    // Sidebar (templates)
    $out .= '<div class="bae-bb-sb" id="bae-bb-sb"><div class="bae-card" style="padding:10px;display:flex;flex-direction:column;gap:10px;">';
    $out .= '<div class="bae-bb-sb-head">';
    $out .= '<div><div style="font-size:12px;font-weight:800;color:var(--text);margin-bottom:2px;">Templates</div>';
    $out .= '<div style="font-size:10px;color:var(--text-3);">'.count($templates).' styles</div></div>';
    $out .= '<button class="bae-btn bae-btn-outline bae-btn-sm" type="button" onclick="baeBookToggleTpl()">Hide</button>';
    $out .= '</div>';
    $out .= '<div class="bae-bb-sb-body" id="bae-bb-sb-body">';
    $overall_count = 0;
    foreach ($cats as $cat => $tpls) {
        $out .= '<div class="bae-bb-cl">'.esc_html($cat).'</div><div class="bae-bb-tg">';
        foreach ($tpls as $id => $t) {
            $is_soon = ($t['cat'] !== 'Free');
            $active = ($id === $saved && !$is_soon) ? ' active' : '';
            $onClick = $is_soon ? '' : ' onclick="baeBookTpl(\''.esc_js($id).'\',this)"';
            $cursor = $is_soon ? 'default' : 'pointer';
            
            $out .= '<div class="bae-bb-tc'.$active.'" style="position:relative;cursor:'.$cursor.';"'.$onClick.' title="'.esc_attr($t['name']).'">';
            $out .= '<div class="bae-bb-th" style="background:'.esc_attr($t['cb']).';">';
            $out .= '<span style="color:'.esc_attr($t['ct']).';">'.esc_html($t['name']).'</span></div>';
            $out .= '<div class="bae-bb-tn">'.esc_html($t['name']).'</div>';
            
            if ($is_soon) {
                $out .= '<div style="position:absolute;inset:0;background:rgba(255,255,255,0.6);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;z-index:2;">';
                $out .= '<span style="background:var(--surface);border:1px solid var(--border-2);padding:4px 8px;border-radius:6px;font-size:8px;font-weight:800;color:var(--text);letter-spacing:0.05em;box-shadow:0 4px 12px rgba(0,0,0,0.08);">Coming Soon</span>';
                $out .= '</div>';
            }
            $out .= '</div>';
            
            $overall_count++;
        }
        $out .= '</div>';
    }
    $out .= '</div></div></div>';

    // Preview (carousel + filmstrip)
    $out .= '<div class="bae-bb-main"><div class="bae-card" style="padding:0;overflow:hidden;min-width:0;">';
    $out .= '<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--surface);border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px;">';
    $out .= '<div><div style="font-size:13px;font-weight:800;color:var(--text);">Preview</div>';
    $out .= '<div style="font-size:11px;color:var(--text-3);" id="bae-bb-lbl">'.esc_html($templates[$saved]['name']).'</div></div>';
    $out .= '<div style="display:flex;gap:7px;align-items:center;">';
    $out .= '<button class="bae-btn bae-btn-outline bae-btn-sm" type="button" onclick="baeBookPrev()">Prev</button>';
    $out .= '<div style="font-size:11px;color:var(--text-3);min-width:70px;text-align:center;" id="bae-bb-pg">1 / 12</div>';
    $out .= '<button class="bae-btn bae-btn-outline bae-btn-sm" type="button" onclick="baeBookNext()">Next</button>';
    if ($is_free) {
        $out .= '<button class="bae-btn bae-btn-outline bae-btn-sm" onclick="baePricingOpen(\'Export Brand Book\',\'Download as PDF on Starter plan.\')">Export PDF &mdash; Starter+</button>';
    } else {
        $out .= '<button class="bae-btn bae-btn-outline bae-btn-sm" onclick="baeBookPrint()">Print / Save PDF</button>';
    }
    $out .= '<button class="bae-btn bae-btn-primary bae-btn-sm" id="bae-bb-sbtn" onclick="baeBookSave()">Save Style</button>';
    $out .= '</div></div>';
    $out .= '<div class="bae-bb-stage">';
    $out .= '<div class="bae-bb-viewport">';
    $out .= '<div class="bae-bb-canvas"><div class="bae-bb-page" id="bae-bb-page"></div></div>';
    $out .= '</div></div>';
    $out .= '<div class="bae-bb-film" id="bae-bb-film" style="margin-top:-24%; position:relative; z-index:10;"></div>';
    $out .= '<div class="bae-bb-pages" id="bae-bb-pages">'.bae_render_book_pages($profile, $templates[$saved]).'</div>';
    $out .= '</div></div></div>';

    $out .= '<div id="bae-bb-pt"></div>';

    $out .= '<script>(function(){';
    $out .= 'var cur=\''.esc_js($saved).'\',nonce=\''.esc_js($nonce).'\';';
    $out .= 'var idx=0,total=12;';
    $out .= 'function q(id){return document.getElementById(id);}';
    $out .= 'function listPages(){var wrap=q("bae-bb-pages");if(!wrap)return[];return Array.prototype.slice.call(wrap.querySelectorAll(".bae-bp"));}';
    $out .= 'function setPage(i){var pages=listPages();total=pages.length||12;idx=Math.max(0,Math.min(i,total-1));var host=q("bae-bb-page");if(!host||!pages[idx])return;host.innerHTML=pages[idx].outerHTML;';
    $out .= 'var pg=q("bae-bb-pg");if(pg)pg.textContent=(idx+1)+" / "+total;';
    $out .= 'var pbtn=q("bae-bb-prev"),nbtn=q("bae-bb-next");if(pbtn)pbtn.disabled=(idx<=0);if(nbtn)nbtn.disabled=(idx>=total-1);';
    $out .= 'var f=q("bae-bb-film");if(f){f.querySelectorAll(".bae-bb-thumb-btn").forEach(function(b){b.classList.remove("active");});var ab=f.querySelector("[data-i=\'"+idx+"\']");if(ab){ab.classList.add("active");ab.scrollIntoView({block:"nearest",inline:"nearest"});} }';
    $out .= '}';
    $out .= 'function buildFilm(){var film=q("bae-bb-film");var pages=listPages();if(!film)return;film.innerHTML="";pages.forEach(function(p,i){var d=document.createElement("div");d.className="bae-bb-thumb";var b=document.createElement("button");b.type="button";b.className="bae-bb-thumb-btn"+(i===idx?" active":"");b.setAttribute("data-i",i);b.onclick=function(){setPage(i);};';
    $out .= 'var mini=document.createElement("div");mini.className="bae-bb-thumb-mini";mini.innerHTML=p.outerHTML;var lbl=document.createElement("div");lbl.className="bae-bb-thumb-lbl";lbl.textContent=(i+1);b.appendChild(mini);b.appendChild(lbl);d.appendChild(b);film.appendChild(d);});}';
    $out .= 'window.baeBookPrev=function(){setPage(idx-1);};window.baeBookNext=function(){setPage(idx+1);};';
    $out .= 'window.baeBookToggleTpl=function(){var sb=q("bae-bb-sb");if(!sb)return;sb.style.display="none";var wrap=document.querySelector(".bae-bb-wrap");if(wrap)wrap.style.gridTemplateColumns="1fr";';
    $out .= 'var top=document.querySelector(".bae-bb-main .bae-card > div"); if(top){var btn=document.createElement("button");btn.className="bae-btn bae-btn-outline bae-btn-sm";btn.type="button";btn.textContent="Show Templates";btn.onclick=function(){sb.style.display="flex";wrap.style.gridTemplateColumns=\'260px 1fr\';btn.remove();}; top.appendChild(btn);} };';
    $out .= 'window.baeBookTpl=function(id,el){';
    $out .= 'document.querySelectorAll(\'.bae-bb-tc\').forEach(function(c){c.classList.remove(\'active\');});';
    $out .= 'el.classList.add(\'active\');cur=id;';
    $out .= 'var pg=document.getElementById(\'bae-bb-pages\');';
    $out .= 'pg.innerHTML=\'<div style="padding:40px;text-align:center;color:var(--text-3);">Loading...</div>\';';
    $out .= 'var fd=new FormData();fd.append(\'action\',\'bae_render_book\');fd.append(\'nonce\',nonce);fd.append(\'template\',id);';
    $out .= 'fetch(ajaxurl,{method:\'POST\',body:fd}).then(function(r){return r.json();}).then(function(j){';
    $out .= 'if(j.success){pg.innerHTML=j.data.html;var lb=document.getElementById(\'bae-bb-lbl\');if(lb)lb.textContent=j.data.name;idx=0;buildFilm();setPage(0);}';
    $out .= '});};';
    $out .= 'window.baeBookSave=function(){var b=document.getElementById(\'bae-bb-sbtn\');if(!b)return;';
    $out .= 'b.disabled=true;b.textContent=\'Saving...\';';
    $out .= 'var fd=new FormData();fd.append(\'action\',\'bae_save_book_template\');fd.append(\'nonce\',nonce);fd.append(\'template\',cur);';
    $out .= 'fetch(ajaxurl,{method:\'POST\',body:fd}).then(function(r){return r.json();}).then(function(j){';
    $out .= 'b.disabled=false;b.textContent=j.success?\'Saved!\':\'Save Style\';';
    $out .= 'setTimeout(function(){b.textContent=\'Save Style\';},2000);});};';
    $out .= 'window.baeBookPrint=function(){var pg=document.getElementById(\'bae-bb-pages\'),tgt=document.getElementById(\'bae-bb-pt\');';
    $out .= 'if(!pg||!tgt)return;tgt.innerHTML=pg.innerHTML;setTimeout(function(){window.print();setTimeout(function(){tgt.innerHTML=\'\';},500);},200);};';
    $out .= 'buildFilm();setPage(0);';
    $out .= '})();</script>';

    return $out;
}

add_action('wp_ajax_bae_render_book',        'bntm_ajax_bae_render_book');
add_action('wp_ajax_nopriv_bae_render_book', 'bntm_ajax_bae_render_book');
function bntm_ajax_bae_render_book() {
    check_ajax_referer('bae_save_profile', 'nonce', false);
    $tpl_id    = sanitize_text_field($_POST['template'] ?? '');
    $templates = bae_get_book_templates();
    if (!isset($templates[$tpl_id])) wp_send_json_error(array());
    $ticket = bae_get_ticket_cookie();
    $profile = array();
    if ($ticket) {
        $profile = bae_get_profile_by_ticket($ticket) ?: [];
    }
    if (empty($profile) && is_user_logged_in()) {
        $p = bae_get_profile(get_current_user_id());
        if ($p) $profile = (array)$p;
    }
    wp_send_json_success(array(
        'html' => bae_render_book_pages($profile, $templates[$tpl_id]),
        'name' => $templates[$tpl_id]['name'],
    ));
}

add_action('wp_ajax_bae_save_book_template',        'bntm_ajax_bae_save_book_template');
add_action('wp_ajax_nopriv_bae_save_book_template', 'bntm_ajax_bae_save_book_template');
function bntm_ajax_bae_save_book_template() {
    check_ajax_referer('bae_save_profile', 'nonce', false);
    $tpl = sanitize_text_field($_POST['template'] ?? '');
    if (!isset(bae_get_book_templates()[$tpl])) wp_send_json_error(array());
    $key = '';
    if (!empty($_COOKIE['bae_ticket'])) {
        $raw = strtoupper(sanitize_text_field($_COOKIE['bae_ticket']));
        if (preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw)) $key = 'bae_bb_' . md5($raw);
    }
    if (!$key && is_user_logged_in()) $key = 'bae_bb_' . md5(get_current_user_id());
    if ($key) set_transient($key, $tpl, YEAR_IN_SECONDS);
    wp_send_json_success(array('saved' => true));
}
// =============================================================================
// DB MIGRATION: add asset_html_prev column if missing
// =============================================================================
add_action('admin_init', 'bae_migrate_asset_prev_column');
function bae_migrate_asset_prev_column() {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_assets';
    $col = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'asset_html_prev'");
    if (empty($col)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN asset_html_prev LONGTEXT NOT NULL DEFAULT '' AFTER asset_html");
    }
}

add_action('admin_init', 'bae_migrate_kit_view_columns');
function bae_migrate_kit_view_columns() {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';

    $kit_views = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'kit_views'");
    if (empty($kit_views)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN kit_views INT UNSIGNED NOT NULL DEFAULT 0 AFTER kit_slug");
    }

    $kit_unique_views = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'kit_unique_views'");
    if (empty($kit_unique_views)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN kit_unique_views INT UNSIGNED NOT NULL DEFAULT 0 AFTER kit_views");
    }
}

add_action('admin_init', 'bae_migrate_logo_builder_columns');
function bae_migrate_logo_builder_columns() {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';

    $cols = [
        'logo_icon_scale' => "ALTER TABLE {$table} ADD COLUMN logo_icon_scale INT NOT NULL DEFAULT 100 AFTER logo_icon",
        'logo_spacing'    => "ALTER TABLE {$table} ADD COLUMN logo_spacing INT NOT NULL DEFAULT 14 AFTER logo_icon_scale",
        'logo_position'   => "ALTER TABLE {$table} ADD COLUMN logo_position VARCHAR(20) NOT NULL DEFAULT 'auto' AFTER logo_spacing",
        'logo_text_case'  => "ALTER TABLE {$table} ADD COLUMN logo_text_case VARCHAR(20) NOT NULL DEFAULT 'default' AFTER logo_position",
    ];

    foreach ($cols as $col => $sql) {
        $exists = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE '{$col}'");
        if (empty($exists)) {
            $wpdb->query($sql);
        }
    }
}

function bntm_ajax_bae_mark_asset_viewed() {
    check_ajax_referer('bae_generate_asset', 'nonce');

    global $wpdb;
    $profiles_table = $wpdb->prefix . 'bae_profiles';
    $profile_id = intval($_POST['profile_id'] ?? 0);
    if (!$profile_id) {
        wp_send_json_error(['message' => 'Missing profile.']);
    }

    $where = ['id' => $profile_id];
    $where_format = ['%d'];

    $ticket = '';
    if (!empty($_COOKIE['bae_ticket'])) {
        $raw = strtoupper(sanitize_text_field($_COOKIE['bae_ticket']));
        if (preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw)) {
            $ticket = $raw;
        }
    }

    if ($ticket) {
        $where['ticket'] = $ticket;
        $where_format[] = '%s';
    } elseif (!empty($_COOKIE['bae_session'])) {
        $session = sanitize_text_field($_COOKIE['bae_session']);
        if (preg_match('/^[a-f0-9]{32}$/', $session)) {
            $where['session_id'] = $session;
            $where_format[] = '%s';
        } else {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
    } elseif (is_user_logged_in()) {
        $where['user_id'] = get_current_user_id();
        $where_format[] = '%d';
    } else {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $profile = $wpdb->get_row(
        $wpdb->prepare("SELECT id, onboarding_asset_viewed FROM {$profiles_table} WHERE id = %d", $profile_id),
        ARRAY_A
    );
    if (!$profile) {
        wp_send_json_error(['message' => 'Profile not found.']);
    }

    $result = $wpdb->update(
        $profiles_table,
        ['onboarding_asset_viewed' => 1],
        $where,
        ['%d'],
        $where_format
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Could not update onboarding progress.']);
    }

    wp_send_json_success([
        'first_time' => empty($profile['onboarding_asset_viewed']),
        'message' => 'Asset view recorded.'
    ]);
}

add_action('admin_init', 'bae_migrate_onboarding_asset_viewed_column');
function bae_migrate_onboarding_asset_viewed_column() {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    $col = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'onboarding_asset_viewed'");
    if (empty($col)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN onboarding_asset_viewed TINYINT(1) NOT NULL DEFAULT 0 AFTER kit_unique_views");
    }
}

add_action('admin_init', 'bae_migrate_ticket_column');
function bae_migrate_ticket_column() {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    $ticket_col = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'ticket'");
    if (empty($ticket_col)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN ticket VARCHAR(20) NOT NULL DEFAULT '' AFTER user_id");
        $wpdb->query("ALTER TABLE {$table} ADD INDEX idx_ticket (ticket)");
    }
}

add_action('admin_init', 'bae_migrate_session_id_column');
function bae_migrate_session_id_column() {
    global $wpdb;
    $table = $wpdb->prefix . 'bae_profiles';
    $col = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'session_id'");
    if (empty($col)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN session_id VARCHAR(64) NOT NULL DEFAULT '' AFTER ticket");
        $wpdb->query("ALTER TABLE {$table} ADD INDEX idx_session (session_id)");
    }
}

// =============================================================================
// AJAX: Undo asset — swap asset_html ↔ asset_html_prev
// =============================================================================
add_action('wp_ajax_bae_undo_asset',        'bntm_ajax_bae_undo_asset');
add_action('wp_ajax_nopriv_bae_undo_asset', 'bntm_ajax_bae_undo_asset');
function bntm_ajax_bae_undo_asset() {
    check_ajax_referer('bae_generate_asset', 'nonce');
    global $wpdb;
    $table      = $wpdb->prefix . 'bae_assets';
    $asset_type = sanitize_text_field($_POST['asset_type'] ?? '');
    $profile_id = intval($_POST['profile_id'] ?? 0);

    $user_id = is_user_logged_in() ? get_current_user_id() : 0;

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id, asset_html, asset_html_prev FROM {$table} WHERE asset_type = %s AND profile_id = %d AND user_id = %d LIMIT 1",
        $asset_type, $profile_id, $user_id
    ), ARRAY_A);

    if (!$row) {
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id, asset_html, asset_html_prev FROM {$table} WHERE asset_type = %s AND profile_id = %d LIMIT 1",
            $asset_type, $profile_id
        ), ARRAY_A);
    }

    if (!$row || empty($row['asset_html_prev'])) {
        wp_send_json_error(['message' => 'No previous version to restore.']);
    }

    // Swap current ↔ prev
    $wpdb->update($table, [
        'asset_html'      => $row['asset_html_prev'],
        'asset_html_prev' => $row['asset_html'],
    ], ['id' => $row['id']]);

    wp_send_json_success(['html' => $row['asset_html_prev'], 'message' => 'Restored previous version.']);
}

// =============================================================================
// AJAX: Live preview — render asset from raw form values without saving
// =============================================================================
add_action('wp_ajax_bae_preview_asset',        'bntm_ajax_bae_preview_asset');
add_action('wp_ajax_nopriv_bae_preview_asset', 'bntm_ajax_bae_preview_asset');
function bntm_ajax_bae_preview_asset() {
    // No nonce needed — read-only, no DB writes, no sensitive data
    $type = sanitize_text_field($_POST['asset_type'] ?? 'business_card');
    $allowed = ['business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'sitemap', 'invoice_template', 'price_list', 'flyer_template', 'thank_you_card', 'media_kit', 'poster_a3'];
    if (!in_array($type, $allowed)) wp_send_json_error(['message' => 'Invalid type.']);

    // Build a temporary profile from POST values — never saved to DB
    $profile = [
        'business_name'  => sanitize_text_field($_POST['business_name']  ?? 'Your Brand'),
        'tagline'        => sanitize_text_field($_POST['tagline']         ?? ''),
        'industry'       => sanitize_text_field($_POST['industry']        ?? ''),
        'personality'    => sanitize_text_field($_POST['personality']     ?? ''),
        'email'          => sanitize_email($_POST['email']                ?? ''),
        'phone'          => sanitize_text_field($_POST['phone']           ?? ''),
        'website'        => esc_url_raw($_POST['website']                 ?? ''),
        'address'        => sanitize_text_field($_POST['address']         ?? ''),
        'primary_color'  => bae_safe_color($_POST['primary_color']   ?? '', '#1a1a2e'),
        'secondary_color'=> bae_safe_color($_POST['secondary_color'] ?? '', '#16213e'),
        'accent_color'   => bae_safe_color($_POST['accent_color']    ?? '', '#e94560'),
        'font_heading'   => sanitize_text_field($_POST['font_heading']    ?? 'Inter'),
        'font_body'      => sanitize_text_field($_POST['font_body']       ?? 'Inter'),
        'logo_style'     => sanitize_text_field($_POST['logo_style']      ?? 'wordmark'),
        'logo_icon'      => sanitize_text_field($_POST['logo_icon']       ?? ''),
        'logo_icon_scale'=> max(70, min(160, intval($_POST['logo_icon_scale'] ?? 100))),
        'logo_spacing'   => max(6, min(28, intval($_POST['logo_spacing'] ?? 14))),
        'logo_position'  => sanitize_text_field($_POST['logo_position']   ?? 'auto'),
        'logo_text_case' => sanitize_text_field($_POST['logo_text_case']  ?? 'default'),
        'logo_url'       => esc_url_raw($_POST['logo_url']                ?? ''),
        'tone_statement' => '',
    ];

    $html = bae_generate_asset_html_static($type, $profile, '');
    wp_send_json_success(['html' => $html]);
}

// =============================================================================
// AJAX: Auto-fix all consistency issues using Gemini
// =============================================================================
add_action('wp_ajax_bae_autofix_consistency',       'bntm_ajax_bae_autofix_consistency');
add_action('wp_ajax_nopriv_bae_autofix_consistency','bntm_ajax_bae_autofix_consistency');
function bntm_ajax_bae_autofix_consistency() {
    check_ajax_referer('bae_generate_asset', 'nonce');
    if ( empty( bae_gemini_key_pool() ) && empty( bae_groq_key_pool() ) ) {
        wp_send_json_error(['message' => 'No AI API keys configured.']);
    }

    global $wpdb;
    $profile_id = intval($_POST['profile_id'] ?? 0);
    $issues_raw = sanitize_textarea_field($_POST['issues'] ?? '');
    $issues     = array_filter(explode('||', $issues_raw));

    if (!$profile_id || empty($issues)) {
        wp_send_json_error(['message' => 'Nothing to fix.']);
    }

    $pt = $wpdb->prefix . 'bae_profiles';
    $at = $wpdb->prefix . 'bae_assets';

    $ticket = '';
    if (!empty($_COOKIE['bae_ticket'])) {
        $raw = strtoupper(sanitize_text_field($_COOKIE['bae_ticket']));
        if (preg_match('/^BAE-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $raw)) $ticket = $raw;
    }

    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE id = %d", $profile_id), ARRAY_A);
    if (!$profile) wp_send_json_error(['message' => 'Profile not found.']);

    $assets = $wpdb->get_results($wpdb->prepare(
        "SELECT id, asset_type, asset_html FROM {$at} WHERE profile_id = %d AND is_generated = 1",
        $profile_id
    ), ARRAY_A);

    if (empty($assets)) wp_send_json_error(['message' => 'No generated assets found.']);

    $issues_text = implode("\n- ", $issues);
    $fixed_count      = 0;
    $errors           = [];
    $fixed_assets_map = [];

    foreach ($assets as $asset) {
        if (empty($asset['asset_html'])) continue;

        // Use fresh static HTML as base (same as regen) — avoids feeding Gemini a potentially corrupted stored blob
        $base_html = bae_generate_asset_html_static($asset['asset_type'], $profile, '');
        if (empty($base_html)) $base_html = $asset['asset_html'];

        $asset_type_label = str_replace('_', ' ', $asset['asset_type']);
        $prompt = "Improve this {$asset_type_label} HTML to fix these brand consistency issues:\n- {$issues_text}\n\nBrand colors: primary={$profile['primary_color']}, secondary={$profile['secondary_color']}, accent={$profile['accent_color']}\nBrand fonts: heading={$profile['font_heading']}, body={$profile['font_body']}\n\nReturn ONLY the fixed HTML for this single asset. No markdown, no explanations, no <style> tags, no <link> tags, no <html>/<head>/<body> wrappers. Inline styles only.\n\nCurrent HTML:\n{$base_html}";

        $result = bae_gemini_request($prompt);

        if (is_array($result) && !empty($result['error'])) {
            $errors[] = $asset['asset_type'] . ': ' . $result['error'];
            continue;
        }
        if (!is_string($result) || !trim($result)) continue;

        // Strip markdown fences
        $fixed_html = preg_replace('/^```[a-zA-Z]*\s*/m', '', $result);
        $fixed_html = preg_replace('/```\s*$/m', '', $fixed_html);
        $fixed_html = trim($fixed_html);
        // Bail if result doesn't look like HTML
        if (!preg_match('/<[a-zA-Z]/', $fixed_html)) continue;

        // Sanitize so echoed HTML can never break the page
        $fixed_html = bae_sanitize_asset_html( $fixed_html );

        // Save prev before overwriting
        $wpdb->update($at, [
            'asset_html_prev' => $asset['asset_html'],
            'asset_html'      => $fixed_html,
        ], ['id' => $asset['id']]);

        $fixed_assets_map[$asset['asset_type']] = $fixed_html;
        $fixed_count++;
    }

    if ($fixed_count === 0) {
        wp_send_json_error(['message' => 'Could not fix any assets.' . (!empty($errors) ? ' Errors: ' . implode(', ', $errors) : '')]);
    }

    wp_send_json_success([
        'message'      => "Fixed {$fixed_count} asset" . ($fixed_count !== 1 ? 's' : '') . " successfully.",
        'fixed'        => $fixed_count,
        'fixed_assets' => $fixed_assets_map,
    ]);
}

// =============================================================================
// POST-ONBOARDING HOME DASHBOARD
// Shown after all 4 stepper steps are complete
// =============================================================================
