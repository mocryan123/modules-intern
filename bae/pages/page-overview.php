<?php if (!defined('ABSPATH')) exit;
function bae_overview_tab($user_id, $profile) {
    $nonce  = wp_create_nonce('bae_save_profile');
    $p      = $profile ?: [];
    $is_new = empty($profile);

    // Count assets scoped to this specific profile (not all users with user_id=0)
    $assets_count = bae_count_assets($user_id, $p['id'] ?? 0);
    $kit_status   = !empty($p['kit_visibility']) ? $p['kit_visibility'] : 'private';

    ob_start();

    // Payment success flash banner
    $pm_success = get_transient('bae_pm_success_' . $user_id);
    if ($pm_success) {
        delete_transient('bae_pm_success_' . $user_id);
        $plan_label = $pm_success === 'pro' ? 'Pro ✦' : 'Starter';
        echo '<div style="background:linear-gradient(135deg,#065f46,#047857);color:#fff;padding:16px 20px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">'
           . '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6ee7b7" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>'
           . '<div><strong>Payment successful!</strong> You\'re now on the <strong>' . esc_html($plan_label) . '</strong> plan. All features are unlocked. 🎉</div>'
           . '</div>';
    }

    $user_plan = bae_get_user_plan($user_id, $profile);
    $is_free   = $user_plan === 'free';
    ?>
    <!-- Stats Row -->
    <div class="bae-stats-row">
        <div class="bae-stat-card">
            <div class="bae-stat-label">Profile Status</div>
            <div class="bae-stat-value" style="font-size:18px;margin-top:10px;">
                <?php if (!$is_new): ?>
                    <span class="bae-badge bae-badge-green">Complete</span>
                <?php else: ?>
                    <span class="bae-badge bae-badge-yellow">Incomplete</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="bae-stat-card">
            <div class="bae-stat-label">Assets Generated</div>
            <div class="bae-stat-value"><?php echo $assets_count; ?></div>
            <div class="bae-stat-sub">of 12 available</div>
        </div>
        <div class="bae-stat-card" style="cursor:<?php echo $is_free ? 'pointer' : 'default'; ?>;" <?php if ($is_free) echo 'onclick="baePricingOpen()"'; ?>>
            <div class="bae-stat-label">Current Plan</div>
            <div class="bae-stat-value" style="font-size:18px;margin-top:10px;">
                <?php if ($user_plan === 'pro'): ?>
                    <span class="bae-badge bae-badge-purple">Pro ✦</span>
                <?php elseif ($user_plan === 'starter'): ?>
                    <span class="bae-badge bae-badge-green">Starter</span>
                <?php else: ?>
                    <span class="bae-badge bae-badge-gray">Free</span>
                <?php endif; ?>
            </div>
            <?php if ($is_free): ?>
            <div class="bae-stat-sub" style="color:var(--brand-soft);font-size:11px;margin-top:4px;">Upgrade ✦</div>
            <?php endif; ?>
        </div>
        <div class="bae-stat-card">
            <div class="bae-stat-label">Last Updated</div>
            <div class="bae-stat-value" style="font-size:15px;margin-top:10px;">
                <?php echo !empty($p['updated_at']) ? date('M d, Y', strtotime($p['updated_at'])) : '—'; ?>
            </div>
        </div>
    </div>

    <?php
    // Completeness indicator — check which fields are filled
    $fields_done = [];
    $fields_todo = [];

    $check = [
        'business_name' => 'Business name',
        'industry'      => 'Industry',
        'tagline'       => 'Tagline',
        'email'         => 'Email',
        'phone'         => 'Phone',
        'primary_color' => 'Brand colors',
        'logo_style'    => 'Logo style',
        'font_heading'  => 'Typography',
    ];
    foreach ($check as $field => $label) {
        if (!empty($p[$field])) {
            $fields_done[] = $label;
        } else {
            $fields_todo[] = $label;
        }
    }
    $total = count($check);
    $done  = count($fields_done);
    $pct   = $total > 0 ? round(($done / $total) * 100) : 0;
    ?>
    <div class="bae-completeness-bar">
        <div class="bae-completeness-top">
            <span class="bae-completeness-label">Profile Completeness</span>
            <span class="bae-completeness-pct"><?php echo $pct; ?>%</span>
        </div>
        <div class="bae-completeness-track">
            <div class="bae-completeness-fill" style="width:<?php echo $pct; ?>%;"></div>
        </div>
        <div class="bae-completeness-items">
            <?php foreach ($fields_done as $f): ?>
            <span class="bae-completeness-item done">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                <?php echo esc_html($f); ?>
            </span>
            <?php endforeach; ?>
            <?php foreach ($fields_todo as $f): ?>
            <span class="bae-completeness-item todo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <?php echo esc_html($f); ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Live Preview Split Layout -->
    <div class="bae-profile-split" id="bae-profile-split">
    <!-- Profile Form -->
    <div class="bae-card bae-profile-form-col">
        <div class="bae-card-header">
            <div>
                <div class="bae-card-title">Brand Profile</div>
                <div class="bae-card-desc">Define your business identity — this drives every asset generated.</div>
            </div>
            <?php if (!$is_new): ?>
            <button type="button" class="bae-btn bae-btn-outline bae-btn-sm" id="bae-preview-toggle-btn" onclick="baeTogglePreview()" style="white-space:nowrap;display:inline-flex;align-items:center;gap:6px;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Live Preview
            </button>
            <?php endif; ?>
        </div>

        <form id="bae-profile-form">
        <!-- ╔════════════════════════════════════╗ -->
        <!-- ║  BENTO GRID — Brand Profile Form   ║ -->
        <!-- ╚════════════════════════════════════╝ -->
        <div class="bae-bento-grid">

        <!-- ══ CARD 1: Core Identity (wide) ══ -->
        <div class="bae-bento-card span-8">
            <div class="bae-bento-label">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                Core Identity
            </div>
            <div class="bae-form-grid">
                <div class="bae-form-group">
                    <label>Business Name *</label>
                    <input type="text" name="business_name" placeholder="e.g. Dela Cruz Bakery"
                           value="<?php echo esc_attr($p['business_name'] ?? ''); ?>" required>
                </div>
                <div class="bae-form-group">
                    <label>Industry / Sector *</label>
                    <select name="industry">
                        <?php
                        $industries = ['Food & Beverage','Retail & Commerce','Fashion & Apparel','Health & Wellness',
                                      'Beauty & Cosmetics','Technology','Professional Services','Education & Training',
                                      'Home & Lifestyle','Agriculture','Construction & Trades','Creative & Media','Other'];
                        $sel = $p['industry'] ?? '';
                        foreach ($industries as $ind):
                        ?>
                            <option value="<?php echo $ind; ?>" <?php selected($sel, $ind); ?>><?php echo $ind; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="bae-form-group" style="margin-top:12px;">
                <label>Tagline</label>
                <div style="position:relative;">
                    <input type="text" name="tagline" id="bae-tagline-input" placeholder="e.g. Fresh baked with love, every day"
                           value="<?php echo esc_attr($p['tagline'] ?? ''); ?>">
                    <button type="button" id="bae-ai-tagline-btn" class="bae-ai-pill" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                        AI Suggest
                    </button>
                </div>
                <div id="bae-ai-tagline-panel" style="display:none;margin-top:10px;padding:12px;background:var(--bg-3);border-radius:12px;border:1px solid var(--border-2);">
                    <div id="bae-ai-tagline-results" style="display:flex;flex-direction:column;gap:6px;">
                        <div style="font-size:13px;color:var(--text-3);text-align:center;padding:8px;">Writing taglines...</div>
                    </div>
                </div>
            </div>
            <div class="bae-form-group" style="margin-top:12px;">
                <label>Target Audience</label>
                <input type="text" name="personality" placeholder="e.g. Families in Quezon City who value quality"
                       value="<?php echo esc_attr($p['personality'] ?? ''); ?>">
                <small>Describe your ideal customer in one sentence.</small>
            </div>
        </div>

        <!-- ══ CARD 2: Contact Info (narrow, right) ══ -->
        <div class="bae-bento-card span-4">
            <div class="bae-bento-label">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.08 6.08l.94-.94a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 17.42z"/></svg>
                Contact Info
            </div>
            <div class="bae-form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="hello@yourbusiness.com"
                       value="<?php echo esc_attr($p['email'] ?? ''); ?>">
            </div>
            <div class="bae-form-group" style="margin-top:12px;">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="+63 900 000 0000"
                       value="<?php echo esc_attr($p['phone'] ?? ''); ?>">
            </div>
            <div class="bae-form-group" style="margin-top:12px;">
                <label>Website</label>
                <input type="url" name="website" placeholder="https://yourbusiness.com"
                       value="<?php echo esc_attr($p['website'] ?? ''); ?>">
            </div>
            <div class="bae-form-group" style="margin-top:12px;">
                <label>Business Address</label>
                <input type="text" name="address" placeholder="123 Main St, Quezon City"
                       value="<?php echo esc_attr($p['address'] ?? ''); ?>">
            </div>
        </div>

        <!-- ══ CARD 3: Aesthetics — Colors ══ -->
        <div class="bae-bento-card span-6">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <div class="bae-bento-label" style="margin-bottom:0;flex:1;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                    Brand Colors
                </div>
                <button type="button" id="bae-ai-color-btn" class="bae-ai-pill">
                    <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                    AI Suggest
                </button>
            </div>
            <!-- AI color suggestions panel -->
            <div id="bae-ai-color-panel" style="display:none;margin-bottom:16px;padding:16px;background:var(--bg-3);border-radius:14px;border:1px solid var(--border-2);">
                <div style="font-size:11px;font-weight:700;color:var(--brand-soft);text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                    AI Palette Suggestions
                </div>
                <div id="bae-ai-color-results" style="display:flex;flex-direction:column;gap:8px;">
                    <div style="font-size:13px;color:var(--text-3);text-align:center;padding:16px;">Generating palettes...</div>
                </div>
            </div>
            <div class="bae-form-grid three">
                <div class="bae-form-group">
                    <label>Primary</label>
                    <div class="bae-color-row bae-color-pair">
                        <input type="color" name="primary_color_picker" value="<?php echo esc_attr(bae_safe_color($p['primary_color'] ?? '', '#1a1a2e')); ?>">
                        <input type="text" name="primary_color" id="bae-primary-color" value="<?php echo esc_attr(bae_safe_color($p['primary_color'] ?? '', '#1a1a2e')); ?>" maxlength="7" placeholder="#1a1a2e">
                    </div>
                    <small>Headers, buttons</small>
                </div>
                <div class="bae-form-group">
                    <label>Secondary</label>
                    <div class="bae-color-row bae-color-pair">
                        <input type="color" name="secondary_color_picker" value="<?php echo esc_attr(bae_safe_color($p['secondary_color'] ?? '', '#16213e')); ?>">
                        <input type="text" name="secondary_color" id="bae-secondary-color" value="<?php echo esc_attr(bae_safe_color($p['secondary_color'] ?? '', '#16213e')); ?>" maxlength="7" placeholder="#16213e">
                    </div>
                    <small>Backgrounds, cards</small>
                </div>
                <div class="bae-form-group">
                    <label>Accent</label>
                    <div class="bae-color-row bae-color-pair">
                        <input type="color" name="accent_color_picker" value="<?php echo esc_attr(bae_safe_color($p['accent_color'] ?? '', '#e94560')); ?>">
                        <input type="text" name="accent_color" id="bae-accent-color" value="<?php echo esc_attr(bae_safe_color($p['accent_color'] ?? '', '#e94560')); ?>" maxlength="7" placeholder="#e94560">
                    </div>
                    <small>Badges, links, CTAs</small>
                </div>
            </div>
            <div class="bae-notice bae-notice-error" id="bae-color-warning" style="display:none;margin-top:12px;"></div>
        </div>

        <!-- ══ CARD 4: Aesthetics — Typography ══ -->
        <div class="bae-bento-card span-6">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <div class="bae-bento-label" style="margin-bottom:0;flex:1;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                    Typography
                </div>
                <button type="button" id="bae-ai-font-btn" class="bae-ai-pill">
                    <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                    AI Suggest
                </button>
            </div>
            <!-- AI font pairing panel -->
            <div id="bae-ai-font-panel" style="display:none;margin-bottom:16px;padding:16px;background:var(--bg-3);border-radius:14px;border:1px solid var(--border-2);">
                <div style="font-size:11px;font-weight:700;color:var(--brand-soft);text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                    AI Font Pairing Suggestions
                </div>
                <div id="bae-ai-font-results" style="display:flex;flex-direction:column;gap:8px;">
                    <div style="font-size:13px;color:var(--text-3);text-align:center;padding:16px;">Generating font pairings...</div>
                </div>
            </div>
            <div class="bae-form-grid">
                <div class="bae-form-group">
                    <label>Heading Font</label>
                    <select name="font_heading">
                        <?php
                        $fonts = ['Inter','Playfair Display','Montserrat','Raleway','Oswald','Lora','Poppins','Nunito','Roboto Slab','Merriweather'];
                        $sfh = $p['font_heading'] ?? 'Inter';
                        foreach ($fonts as $f):
                        ?>
                            <option value="<?php echo $f; ?>" <?php selected($sfh, $f); ?>><?php echo $f; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Titles &amp; headlines in assets.</small>
                </div>
                <div class="bae-form-group">
                    <label>Body Font</label>
                    <select name="font_body">
                        <?php
                        $sfb = $p['font_body'] ?? 'Inter';
                        foreach ($fonts as $f):
                        ?>
                            <option value="<?php echo $f; ?>" <?php selected($sfb, $f); ?>><?php echo $f; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Body text, descriptions, addresses.</small>
                </div>
            </div>
        </div>

        </div><!-- /bae-bento-grid -->

        <style>
            .bae-icon-tile:hover { border-color: rgba(243,45,134,.4) !important; background: rgba(243,45,134,.08) !important; }
            .bae-icon-tile.selected { border-color: #F32D86 !important; background: rgba(243,45,134,.15) !important; }
            #bae-icon-picker::-webkit-scrollbar { width: 4px; }
            #bae-icon-picker::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 999px; }
            @media (max-width: 700px) {
                .bae-bento-card .bae-form-grid.three { grid-template-columns: 1fr; }
            }
        </style>

        <input type="hidden" name="action" value="bae_save_profile">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">

        <div style="display:flex;align-items:center;gap:12px;margin-top:8px;">
            <button type="submit" class="bae-btn bae-btn-primary">Save Brand Profile</button>
        </div>
        <div id="bae-profile-msg"></div>
        </form>
    </div><!-- /bae-profile-form-col -->

    <!-- Live Preview Panel -->
    <div class="bae-preview-panel" id="bae-preview-panel" style="display:none;">
        <div class="bae-preview-panel-header">
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--text);">Live Preview</div>
                <div style="font-size:11px;color:var(--text-3);">Updates as you edit</div>
            </div>
            <button id="bae-preview-panel-close" type="button" aria-label="Close preview" style="background:none;border:none;cursor:pointer;color:var(--text-3);padding:4px;border-radius:6px;display:flex;align-items:center;justify-content:center;transition:color .15s,background .15s;" onmouseover="this.style.background='var(--bg-3)';this.style.color='var(--text)';" onmouseout="this.style.background='none';this.style.color='var(--text-3)';"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <?php
        // Only show tabs for assets that have been generated
        global $wpdb;
        $preview_generated = [];
        if (!$is_new && !empty($p['id'])) {
            $gen_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT asset_type FROM {$wpdb->prefix}bae_assets WHERE profile_id = %d AND is_generated = 1",
                $p['id']
            ), ARRAY_A) ?: [];
            foreach ($gen_rows as $gr) $preview_generated[] = $gr['asset_type'];
        }
        $all_preview_types = ['business_card'=>'Card','letterhead'=>'Letterhead','email_signature'=>'Email Sig','social_kit'=>'Social','brand_guidelines'=>'Guidelines'];
        // Filter to only generated ones — fallback to all if none generated yet
        $show_preview_types = array_intersect_key($all_preview_types, array_flip($preview_generated));
        if (empty($show_preview_types)) $show_preview_types = []; // show empty state
        $first_preview_type = !empty($show_preview_types) ? array_key_first($show_preview_types) : 'business_card';
        ?>
        <div class="bae-preview-panel-tabs">
            <?php if (!empty($show_preview_types)): ?>
            <?php foreach ($show_preview_types as $pt => $pl): ?>
            <button class="bae-preview-tab-btn <?php echo $pt === $first_preview_type ? 'active' : ''; ?>"
                    data-ptype="<?php echo $pt; ?>"
                    onclick="baeSetPreviewType('<?php echo $pt; ?>', this)">
                <?php echo $pl; ?>
            </button>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="bae-preview-panel-content">
            <?php if (empty($show_preview_types)): ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:10px;color:var(--text-3);text-align:center;padding:20px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".4"><rect width="8" height="8" x="3" y="3" rx="1"/><rect width="8" height="5" x="13" y="3" rx="1"/><rect width="8" height="8" x="13" y="12" rx="1"/><rect width="8" height="5" x="3" y="15" rx="1"/></svg>
                <div style="font-size:12px;font-weight:600;">No assets generated yet</div>
                <div style="font-size:11px;">Generate assets in the Asset Generator step — they'll appear here as live previews.</div>
                <a href="?tab=assets" style="font-size:11px;color:var(--brand-soft);text-decoration:none;font-weight:600;">Go to Asset Generator →</a>
            </div>
            <?php else: ?>
            <div id="bae-live-preview-frame" style="transform:scale(0.55);transform-origin:top left;width:182%;pointer-events:none;">
                <?php echo bae_generate_asset_html_static($first_preview_type, $p, ''); ?>
            </div>
            <?php endif; ?>
            <div id="bae-live-preview-loading" style="display:none;position:absolute;inset:0;background:var(--bg-2);align-items:center;justify-content:center;border-radius:12px;">
                <div style="width:24px;height:24px;border:2px solid rgba(243,45,134,0.2);border-top-color:#F32D86;border-radius:50%;animation:bae-spin 0.8s linear infinite;"></div>
            </div>
        </div>
    </div>

    </div><!-- /bae-profile-split -->

    <?php if (!$is_new): ?>
    <!-- Brand Intelligence (absorbed from Identity Board) -->
    <div class="bae-card bae-brand-intel" id="bae-brand-intel">
        <div class="bae-card-header" style="cursor:pointer;" onclick="baeToggleIntel()">
            <div>
                <div class="bae-card-title" style="display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--brand-soft)"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                    Brand Intelligence
                </div>
                <div class="bae-card-desc">Your logo concept, color palette, typography, and tone analysis.</div>
            </div>
            <svg id="bae-intel-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transition:transform .2s;flex-shrink:0;"><path d="m6 9 6 6 6-6"/></svg>
        </div>
        <div id="bae-intel-body" style="display:none;">
            <?php
            $initials_bi  = bae_get_initials($p['business_name']);
            $tone_tags_bi = bae_derive_tone_tags($p['industry'], $p['personality'] ?? '');
            $pc_bi = bae_safe_color($p['primary_color'], '#1a1a2e');
            $sc_bi = bae_safe_color($p['secondary_color'], '#16213e');
            $ac_bi = bae_safe_color($p['accent_color'], '#e94560');
            ?>
            <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($p['font_heading']); ?>:wght@400;700&family=<?php echo urlencode($p['font_body']); ?>&display=swap" rel="stylesheet">

            <!-- Logo Concept -->
            <div style="margin-bottom:20px;">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);margin-bottom:10px;">Logo Concept</div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <div style="padding:16px 20px;background:#ffffff;border-radius:10px;flex:1;min-width:200px;">
                        <?php echo bae_render_logo_lockup($p, ['compact' => true]); ?>
                    </div>
                    <div style="padding:16px 20px;background:<?php echo $pc_bi; ?>;border-radius:10px;flex:1;min-width:200px;">
                        <?php echo bae_render_logo_lockup($p, ['compact' => true, 'dark' => true]); ?>
                    </div>
                </div>
            </div>

            <!-- Color Palette -->
            <div style="margin-bottom:20px;">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);margin-bottom:10px;">Color Palette</div>
                <div class="bae-color-swatches">
                    <?php foreach (['Primary'=>$pc_bi,'Secondary'=>$sc_bi,'Accent'=>$ac_bi] as $lbl=>$hex): ?>
                    <div class="bae-swatch" data-color="<?php echo esc_attr(strtoupper($hex)); ?>">
                        <div class="bae-swatch-block" style="background:<?php echo $hex; ?>;"></div>
                        <div class="bae-swatch-label"><?php echo $lbl; ?></div>
                        <div class="bae-swatch-hex"><?php echo strtoupper($hex); ?></div>
                        <div class="bae-swatch-copy-wrap">
                            <button type="button" class="bae-swatch-copy-btn" onclick="baeToggleColorMenu(event, this)">
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
                </div>
            </div>

            <!-- Typography -->
            <div style="margin-bottom:20px;">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);margin-bottom:10px;">Typography</div>
                <div class="bae-font-sample">
                    <div class="bae-font-heading-sample" style="font-family:'<?php echo esc_attr($p['font_heading']); ?>',sans-serif;color:<?php echo $pc_bi; ?>;">
                        <?php echo esc_html($p['business_name']); ?>
                    </div>
                    <div class="bae-font-body-sample" style="font-family:'<?php echo esc_attr($p['font_body']); ?>',sans-serif;">
                        <?php echo !empty($p['tagline']) ? esc_html($p['tagline']) : 'Quality crafted with care for every customer.'; ?>
                    </div>
                </div>
            </div>

            <!-- Tone of Voice -->
            <div>
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);margin-bottom:10px;">Tone of Voice</div>
                <div class="bae-tone-tags" style="margin-bottom:10px;">
                    <?php foreach ($tone_tags_bi as $tag): ?>
                    <span class="bae-tone-tag"><?php echo esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($p['personality'])): ?>
                <div style="padding:12px 16px;background:var(--bg-3);border-radius:8px;font-size:13px;color:var(--text-2);line-height:1.6;">
                    <strong>Target Audience:</strong> <?php echo esc_html($p['personality']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Launch Toolkit shortcut card -->
    <?php if (!$is_new): ?>
    <div class="bae-card" style="margin-top:0;">
        <div class="bae-card-header" style="margin-bottom:0;">
            <div>
                <div class="bae-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:6px;color:var(--brand-soft)"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/></svg>
                    Launch Toolkit
                </div>
                <div class="bae-card-desc">Domain ideas, social handles, tagline variants, email suggestions, and a full legal launch checklist — all generated from your profile.</div>
            </div>
            <a href="?tab=startup" class="bae-btn bae-btn-primary bae-btn-sm" style="white-space:nowrap;">Open Toolkit &rarr;</a>
        </div>
    </div>
    <?php endif; ?>

    <script>
    (function() {
        var overviewNonce = '<?php echo esc_js(wp_create_nonce('bae_save_profile')); ?>';
        var bizName       = <?php echo json_encode($p['business_name'] ?? ''); ?>;
        var bizIndustry   = <?php echo json_encode($p['industry'] ?? ''); ?>;

        var form = document.getElementById('bae-profile-form');
        var msg  = document.getElementById('bae-profile-msg');

        // ── Profile save ─────────────────────────────────────────────────
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = 'Saving...';
                msg.innerHTML = '';
                var formData = new FormData(form);
                fetch(ajaxurl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    btn.disabled = false;
                    btn.textContent = 'Save Brand Profile';
                    if (json.success) {
                        // Show save success + resync option
                        msg.innerHTML = '<div class="bae-notice bae-notice-success" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">' +
                            '<span>' + json.data.message + '</span>' +
                            '<button type="button" id="bae-resync-btn" style="background:rgba(243,45,134,.15);border:1px solid rgba(243,45,134,.3);border-radius:8px;padding:5px 12px;font-size:12px;font-weight:700;color:var(--brand-soft);cursor:pointer;font-family:\'Geist\',sans-serif;white-space:nowrap;flex-shrink:0;" onclick="baeResyncAllAssets()">' +
                            'Re-sync assets with new profile</button></div>';
                        // Refresh live preview if open
                        if (typeof baeFirePreview === 'function') baeFirePreview();
                        baeRefreshAssetPreviews(new FormData(form));
                        setTimeout(function() { location.reload(); }, 2500);
                    } else {
                        msg.innerHTML = '<div class="bae-notice bae-notice-error">' + json.data.message + '</div>';
                    }
                })
                .catch(function() {
                    msg.innerHTML = '<div class="bae-notice bae-notice-error">An error occurred. Please try again.</div>';
                    btn.disabled = false;
                    btn.textContent = 'Save Brand Profile';
                });
            });
        }

        function baeHexToRgbWarn(hex) {
            hex = (hex || '').trim().replace('#', '');
            if (hex.length !== 6) return null;
            return {
                r: parseInt(hex.slice(0, 2), 16),
                g: parseInt(hex.slice(2, 4), 16),
                b: parseInt(hex.slice(4, 6), 16)
            };
        }

        function baeWarnLuminance(hex) {
            var rgb = baeHexToRgbWarn(hex);
            if (!rgb) return 0;
            return Math.round((rgb.r * 0.299) + (rgb.g * 0.587) + (rgb.b * 0.114));
        }

        function baeWarnHue(hex) {
            var rgb = baeHexToRgbWarn(hex);
            if (!rgb) return 0;
            var r = rgb.r / 255, g = rgb.g / 255, b = rgb.b / 255;
            var max = Math.max(r, g, b), min = Math.min(r, g, b), delta = max - min;
            if (!delta) return 0;
            var h;
            if (max === r) h = ((g - b) / delta) % 6;
            else if (max === g) h = ((b - r) / delta) + 2;
            else h = ((r - g) / delta) + 4;
            h *= 60;
            return h < 0 ? h + 360 : h;
        }

        function baeWarnHueDiff(a, b) {
            var diff = Math.abs(baeWarnHue(a) - baeWarnHue(b));
            return Math.min(diff, 360 - diff);
        }

        function baeUpdateColorWarning() {
            var warning = document.getElementById('bae-color-warning');
            var primary = document.getElementById('bae-primary-color');
            var secondary = document.getElementById('bae-secondary-color');
            var accent = document.getElementById('bae-accent-color');
            if (!warning || !primary || !secondary || !accent) return;

            var messages = [];
            if (Math.abs(baeWarnLuminance(primary.value) - baeWarnLuminance(secondary.value)) < 30) {
                messages.push('Primary and secondary are very close in brightness.');
            }
            if (baeWarnHueDiff(primary.value, accent.value) < 30) {
                messages.push('Primary and accent are very close in hue.');
            }

            if (!messages.length) {
                warning.style.display = 'none';
                warning.textContent = '';
                return;
            }

            warning.style.display = 'block';
            warning.textContent = messages.join(' ');
        }

        // ── Live Preview ─────────────────────────────────────────────────
        var _previewType   = 'business_card';
        var _previewTimer  = null;
        var _previewOpen   = false;
        var previewPanel   = document.getElementById('bae-preview-panel');
        var previewFrame   = document.getElementById('bae-live-preview-frame');
        var previewLoading = document.getElementById('bae-live-preview-loading');
        var previewCloseBtn = document.getElementById('bae-preview-panel-close');
        if (previewCloseBtn) {
            previewCloseBtn.addEventListener('click', function() {
                if (previewPanel) {
                    previewPanel.classList.remove('open');
                    setTimeout(function(){ previewPanel.style.display = 'none'; }, 300);
                }
                _previewOpen = false;
            });
        }

        function baeGetFormProfile() {
            if (!form) return {};
            var fd = new FormData(form);
            var obj = {};
            fd.forEach(function(v,k){ obj[k] = v; });
            return obj;
        }

        function baeFirePreview() {
            if (!_previewOpen || !previewFrame) return;
            if (previewLoading) previewLoading.style.display = 'flex';
            var data = baeGetFormProfile();
            data.action = 'bae_preview_asset';
            data.asset_type = _previewType;
            var fd = new FormData();
            Object.keys(data).forEach(function(k){ fd.append(k, data[k]); });
            fetch(ajaxurl, { method:'POST', body:fd })
            .then(function(r){ return r.json(); })
            .then(function(j) {
                if (j.success && previewFrame) {
                    previewFrame.innerHTML = j.data.html;
                }
                if (previewLoading) previewLoading.style.display = 'none';
            })
            .catch(function(){ if (previewLoading) previewLoading.style.display = 'none'; });
        }

        function baeDebouncePreview() {
            clearTimeout(_previewTimer);
            _previewTimer = setTimeout(baeFirePreview, 600);
        }

        // Re-sync all generated assets with current profile data (full regenerate)
        window.baeResyncAllAssets = function() {
            var btn = document.getElementById('bae-resync-btn');
            if (btn) { btn.disabled = true; btn.textContent = 'Re-syncing...'; }
            var nonce = '<?php echo esc_js(wp_create_nonce("bae_generate_asset")); ?>';
            var pid   = '<?php echo esc_js($p["id"] ?? ""); ?>';
            if (!pid) { if (btn) btn.textContent = 'No profile ID'; return; }
            var types = ['business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'sitemap', 'invoice_template', 'price_list', 'flyer_template', 'thank_you_card', 'media_kit', 'poster_a3'];
            var done  = 0;
            types.forEach(function(type) {
                var card = document.getElementById('bae-card-' + type);
                if (!card || !card.querySelector('.bae-asset-preview-inner')) return; // not generated, skip
                var fd = new FormData();
                fd.append('action', 'bae_generate_asset');
                fd.append('asset_type', type);
                fd.append('profile_id', pid);
                fd.append('nonce', nonce);
                fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(j) {
                    done++;
                    if (j.success) {
                        var inner = card.querySelector('.bae-asset-preview-inner');
                        if (inner) inner.innerHTML = j.data.html;
                    }
                    if (done === types.length && btn) {
                        btn.textContent = 'Re-synced!';
                        btn.style.color = '#34d399';
                        setTimeout(function(){ location.reload(); }, 1000);
                    }
                });
            });
        };

        // Refresh all visible asset card previews with latest form data
        window.baeRefreshAssetPreviews = function(formData) {
            var assetTypes = ['business_card', 'letterhead', 'email_signature', 'social_kit', 'brand_guidelines', 'sitemap', 'invoice_template', 'price_list', 'flyer_template', 'thank_you_card', 'media_kit', 'poster_a3'];
            assetTypes.forEach(function(type) {
                var card = document.getElementById('bae-card-' + type);
                if (!card) return;
                var inner = card.querySelector('.bae-asset-preview-inner');
                if (!inner) return; // not yet generated, skip
                var fd = new FormData();
                if (formData) { formData.forEach(function(v,k){ fd.append(k,v); }); }
                fd.set('action', 'bae_preview_asset');
                fd.set('asset_type', type);
                fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(j) {
                    if (j.success && inner) inner.innerHTML = j.data.html;
                });
            });
        };

        window.baeTogglePreview = function() {
            _previewOpen = !_previewOpen;
            var isMobile = window.innerWidth < 680;
            if (previewPanel) {
                if (_previewOpen) {
                    previewPanel.style.display = 'block';
                    if (isMobile) {
                        setTimeout(function(){ previewPanel.classList.add('open'); }, 10);
                    }
                    baeFirePreview();
                } else {
                    if (isMobile) {
                        previewPanel.classList.remove('open');
                        setTimeout(function(){ previewPanel.style.display = 'none'; }, 300);
                    } else {
                        previewPanel.style.display = 'none';
                    }
                }
            }
            var toggleBtn = document.getElementById('bae-preview-toggle-btn');
            if (toggleBtn) toggleBtn.style.borderColor = _previewOpen ? 'var(--brand)' : '';
        };

        window.baeSetPreviewType = function(type, btn) {
            _previewType = type;
            document.querySelectorAll('.bae-preview-tab-btn').forEach(function(b){ b.classList.remove('active'); });
            if (btn) btn.classList.add('active');
            baeFirePreview();
        };

        // Attach debounced listeners to form inputs
        if (form) {
            form.querySelectorAll('input, select, textarea').forEach(function(el) {
                el.addEventListener('input', baeDebouncePreview);
                el.addEventListener('change', baeDebouncePreview);
            });
        }

        ['bae-primary-color', 'bae-secondary-color', 'bae-accent-color'].forEach(function(id) {
            var input = document.getElementById(id);
            if (!input) return;
            input.addEventListener('input', baeUpdateColorWarning);
            input.addEventListener('change', baeUpdateColorWarning);
        });
        ['primary_color_picker', 'secondary_color_picker', 'accent_color_picker'].forEach(function(name) {
            var picker = document.querySelector('[name="' + name + '"]');
            if (!picker) return;
            picker.addEventListener('input', function() { setTimeout(baeUpdateColorWarning, 0); });
            picker.addEventListener('change', function() { setTimeout(baeUpdateColorWarning, 0); });
        });
        baeUpdateColorWarning();

        function baeSyncLogoControlLabels() {
            var iconScale = document.getElementById('bae-logo-icon-scale');
            var iconScaleValue = document.getElementById('bae-logo-icon-scale-value');
            var spacing = document.getElementById('bae-logo-spacing');
            var spacingValue = document.getElementById('bae-logo-spacing-value');
            if (iconScale && iconScaleValue) iconScaleValue.textContent = iconScale.value + '%';
            if (spacing && spacingValue) spacingValue.textContent = spacing.value + 'px';
        }
        ['bae-logo-icon-scale', 'bae-logo-spacing'].forEach(function(id) {
            var input = document.getElementById(id);
            if (!input) return;
            input.addEventListener('input', baeSyncLogoControlLabels);
            input.addEventListener('change', baeSyncLogoControlLabels);
        });
        baeSyncLogoControlLabels();

        // Brand Intelligence toggle
        window.baeToggleIntel = function() {
            var body    = document.getElementById('bae-intel-body');
            var chevron = document.getElementById('bae-intel-chevron');
            if (!body) return;
            var isOpen = body.style.display !== 'none';
            body.style.display = isOpen ? 'none' : 'block';
            if (chevron) chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
        };

        // ── AI Color Suggest ──────────────────────────────────────────────
        var colorBtn   = document.getElementById('bae-ai-color-btn');
        var colorPanel = document.getElementById('bae-ai-color-panel');
        var colorResults = document.getElementById('bae-ai-color-results');

        if (colorBtn) {
            colorBtn.addEventListener('click', function() {
                var isOpen = colorPanel.style.display !== 'none';
                if (isOpen) { colorPanel.style.display = 'none'; return; }
                colorPanel.style.display = 'block';
                colorResults.innerHTML = '<div style="font-size:13px;color:var(--text-3);text-align:center;padding:16px;">Generating palettes...</div>';

                var name = (document.querySelector('[name="business_name"]') || {}).value || bizName;
                var industry = (document.querySelector('[name="industry"]') || {}).value || bizIndustry;

                var fd = new FormData();
                fd.append('action', 'bae_suggest_colors');
                fd.append('nonce', overviewNonce);
                fd.append('name', name);
                fd.append('industry', industry);

                fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (!j.success || !j.data.palettes) {
                        colorResults.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:8px;">Could not generate suggestions. Try again.</div>';
                        return;
                    }
                    colorResults.innerHTML = '';
                    j.data.palettes.forEach(function(p) {
                        var card = document.createElement('div');
                        card.style.cssText = 'display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--surface);border-radius:10px;border:1.5px solid var(--border);cursor:pointer;transition:all .2s;';
                        card.innerHTML =
                            '<div style="display:flex;gap:4px;flex-shrink:0;">' +
                                '<div style="width:22px;height:38px;border-radius:5px;background:'+p.primary+';"></div>' +
                                '<div style="width:22px;height:38px;border-radius:5px;background:'+p.secondary+';"></div>' +
                                '<div style="width:22px;height:38px;border-radius:5px;background:'+p.accent+';"></div>' +
                            '</div>' +
                            '<div style="flex:1;min-width:0;">' +
                                '<div style="font-size:13px;font-weight:600;color:var(--text);">'+p.name+'</div>' +
                                '<div style="font-size:11px;color:var(--text-3);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+p.reason+'</div>' +
                            '</div>' +
                            '<button style="background:var(--brand);color:white;border:none;border-radius:7px;padding:5px 12px;font-size:11px;font-weight:700;cursor:pointer;font-family:\'Geist\',sans-serif;white-space:nowrap;">Apply</button>';

                        card.querySelector('button').addEventListener('click', function(e) {
                            e.stopPropagation();
                            // Apply colors to pickers
                            function applyColor(nameAttr, val) {
                                var txt = document.querySelector('[name="'+nameAttr+'"]');
                                var picker = document.querySelector('[name="'+nameAttr+'_picker"]');
                                if (txt) txt.value = val;
                                if (picker) picker.value = val;
                            }
                            applyColor('primary_color', p.primary);
                            applyColor('secondary_color', p.secondary);
                            applyColor('accent_color', p.accent);
                            if (typeof baeUpdateColorWarning === 'function') baeUpdateColorWarning();
                            card.style.borderColor = '#F32D86';
                            card.style.background = 'rgba(243,45,134,.08)';
                            colorPanel.style.display = 'none';
                            if (window.gsap) gsap.fromTo(card, {scale:.97}, {scale:1, duration:.25, ease:'back.out(2)'});
                        });
                        colorResults.appendChild(card);
                    });
                })
                .catch(function() {
                    colorResults.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:8px;">Connection error. Try again.</div>';
                });
            });
        }

        // ── AI Tagline Suggest ────────────────────────────────────────────
        var taglineBtn    = document.getElementById('bae-ai-tagline-btn');
        var taglinePanel  = document.getElementById('bae-ai-tagline-panel');
        var taglineResult = document.getElementById('bae-ai-tagline-results');
        var taglineInput  = document.getElementById('bae-tagline-input');

        if (taglineBtn) {
            taglineBtn.addEventListener('click', function() {
                var isOpen = taglinePanel.style.display !== 'none';
                if (isOpen) { taglinePanel.style.display = 'none'; return; }
                taglinePanel.style.display = 'block';
                taglineResult.innerHTML = '<div style="font-size:13px;color:var(--text-3);text-align:center;padding:8px;">Writing taglines...</div>';

                var name     = (document.querySelector('[name="business_name"]') || {}).value || bizName;
                var industry = (document.querySelector('[name="industry"]') || {}).value || bizIndustry;

                var fd = new FormData();
                fd.append('action', 'bae_suggest_tagline');
                fd.append('nonce', overviewNonce);
                fd.append('name', name);
                fd.append('industry', industry);

                fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (!j.success || !j.data.taglines) {
                        taglineResult.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:4px;">Could not generate. Try again.</div>';
                        return;
                    }
                    taglineResult.innerHTML = '';
                    j.data.taglines.forEach(function(t) {
                        var row = document.createElement('div');
                        row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 12px;background:var(--surface);border-radius:8px;border:1px solid var(--border);cursor:pointer;transition:all .2s;';
                        row.innerHTML = '<span style="font-size:13px;color:var(--text);font-style:italic;">"'+t+'"</span>' +
                            '<button style="background:none;border:1px solid var(--border-2);border-radius:6px;padding:3px 10px;font-size:11px;font-weight:600;color:var(--text-2);cursor:pointer;font-family:\'Geist\',sans-serif;white-space:nowrap;">Use</button>';
                        row.querySelector('button').addEventListener('click', function(e) {
                            e.stopPropagation();
                            if (taglineInput) taglineInput.value = t;
                            taglinePanel.style.display = 'none';
                        });
                        taglineResult.appendChild(row);
                    });
                })
                .catch(function() {
                    taglineResult.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:4px;">Connection error.</div>';
                });
            });
        }

        // ── AI Font Pairing Suggest (Typography) ─────────────────────────
        var fontBtn   = document.getElementById('bae-ai-font-btn');
        var fontPanel = document.getElementById('bae-ai-font-panel');
        var fontResults = document.getElementById('bae-ai-font-results');

        function baeEnsureSelectOption(sel, value) {
            if (!sel || !value) return;
            var exists = Array.prototype.some.call(sel.options, function(o) { return o.value === value; });
            if (!exists) {
                var opt = document.createElement('option');
                opt.value = value;
                opt.textContent = value;
                sel.appendChild(opt);
            }
        }

        if (fontBtn) {
            fontBtn.addEventListener('click', function() {
                var isOpen = fontPanel && fontPanel.style.display !== 'none';
                if (fontPanel && isOpen) { fontPanel.style.display = 'none'; return; }
                if (!fontPanel || !fontResults) return;

                fontPanel.style.display = 'block';
                fontResults.innerHTML = '<div style="font-size:13px;color:var(--text-3);text-align:center;padding:16px;">Generating font pairings...</div>';

                var fd = new FormData();
                fd.append('action', 'bae_suggest_fonts');
                fd.append('nonce', overviewNonce);
                fd.append('profile_id', <?php echo json_encode( (int) ($p['id'] ?? 0) ); ?>);

                fetch(ajaxurl, { method:'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (!j.success || !j.data || !j.data.pairs) {
                        fontResults.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:8px;">Could not generate suggestions. Try again.</div>';
                        return;
                    }
                    var pairs = j.data.pairs || [];
                    if (!pairs.length) {
                        fontResults.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:8px;">No suggestions returned.</div>';
                        return;
                    }

                    fontResults.innerHTML = '';
                    pairs.forEach(function(p) {
                        var h = p.heading || '';
                        var b = p.body || '';
                        var reason = p.reason || '';

                        var card = document.createElement('div');
                        card.style.cssText = 'display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--surface);border-radius:10px;border:1.5px solid var(--border);transition:all .2s;';
                        card.innerHTML =
                            '<div style="flex:1;min-width:0;">' +
                                '<div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + h + ' <span style="opacity:.6;">+</span> ' + b + '</div>' +
                                (reason ? '<div style="font-size:11px;color:var(--text-3);margin-top:2px;line-height:1.35;">' + reason + '</div>' : '') +
                            '</div>' +
                            '<button style="background:var(--brand);color:white;border:none;border-radius:7px;padding:5px 12px;font-size:11px;font-weight:700;cursor:pointer;font-family:\'Geist\',sans-serif;white-space:nowrap;">Apply</button>';

                        card.querySelector('button').addEventListener('click', function(e) {
                            e.stopPropagation();
                            var hSel = document.querySelector('[name="font_heading"]');
                            var bSel = document.querySelector('[name="font_body"]');
                            baeEnsureSelectOption(hSel, h);
                            baeEnsureSelectOption(bSel, b);
                            if (hSel) hSel.value = h;
                            if (bSel) bSel.value = b;
                            card.style.borderColor = '#F32D86';
                            card.style.background = 'rgba(243,45,134,.08)';
                            fontPanel.style.display = 'none';
                            if (window.gsap) gsap.fromTo(card, {scale:.97}, {scale:1, duration:.25, ease:'back.out(2)'});
                        });

                        fontResults.appendChild(card);
                    });
                })
                .catch(function() {
                    fontResults.innerHTML = '<div style="font-size:13px;color:#fb7185;padding:8px;">Connection error. Try again.</div>';
                });
            });
        }

        // ── Logo Upload ───────────────────────────────────────────────────
        var fileInput    = document.getElementById('bae-logo-file-input');
        var uploadStatus = document.getElementById('bae-logo-upload-status');
        var downloadStatus = document.getElementById('bae-logo-download-status');
        var logoUrlHidden = document.getElementById('bae-logo-url-hidden');
        var logoPreviewWrap = document.getElementById('bae-logo-preview-wrap');
        var downloadPngBtn = document.getElementById('bae-logo-download-png');
        var downloadSvgBtn = document.getElementById('bae-logo-download-svg');

        function baeSetLogoDownloadStatus(message, color) {
            if (!downloadStatus) return;
            downloadStatus.textContent = message || '';
            downloadStatus.style.color = color || 'var(--text-3)';
        }

        function baeGetLogoDownloadName(ext) {
            var businessName = ((document.querySelector('[name="business_name"]') || {}).value || 'brand-logo')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') || 'brand-logo';
            return businessName + '-logo.' + ext;
        }

        function baeGetLogoControlValue(name, fallback) {
            var field = document.querySelector('[name="' + name + '"]');
            return field ? field.value : fallback;
        }

        function baeIsSvgUpload(url) {
            return /\.svg(?:$|\?)/i.test(url || '');
        }

        function baeTriggerBlobDownload(blob, filename) {
            var blobUrl = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = blobUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(function() { URL.revokeObjectURL(blobUrl); }, 1000);
        }

        function baeTriggerUrlDownload(url, filename) {
            var link = document.createElement('a');
            link.href = url;
            if (filename) link.download = filename;
            link.target = '_blank';
            link.rel = 'noopener';
            document.body.appendChild(link);
            link.click();
            link.remove();
        }

        function baeEscapeXml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&apos;');
        }

        function baeBuildLogoSvgMarkup() {
            var uploadedUrl = (logoUrlHidden && logoUrlHidden.value) ? logoUrlHidden.value : '';
            if (uploadedUrl) {
                if (baeIsSvgUpload(uploadedUrl)) {
                    return null;
                }
                return null;
            }

            var businessName = ((document.querySelector('[name="business_name"]') || {}).value || 'Brand Name').trim();
            var tagline = ((document.querySelector('[name="tagline"]') || {}).value || '').trim();
            var primary = ((document.getElementById('bae-primary-color') || {}).value || '#1a1a2e').trim();
            var secondary = ((document.getElementById('bae-secondary-color') || {}).value || '#16213e').trim();
            var accent = ((document.getElementById('bae-accent-color') || {}).value || '#e94560').trim();
            var styleField = document.querySelector('[name="logo_style"]');
            var logoStyle = styleField ? styleField.value : 'wordmark';
            var headingField = document.querySelector('[name="font_heading"]');
            var headingFont = headingField ? headingField.value : 'Inter';
            var iconScale = Math.max(70, Math.min(160, parseInt(baeGetLogoControlValue('logo_icon_scale', '100'), 10) || 100));
            var spacing = Math.max(6, Math.min(28, parseInt(baeGetLogoControlValue('logo_spacing', '14'), 10) || 14));
            var textCase = baeGetLogoControlValue('logo_text_case', 'default');
            var iconPosition = baeGetLogoControlValue('logo_position', 'auto');
            var iconTile = document.querySelector('.bae-icon-tile.selected');
            var iconMarkup = iconTile ? iconTile.innerHTML : '';
            var initials = businessName.split(/\s+/).filter(Boolean).slice(0, 2).map(function(part) {
                return part.charAt(0).toUpperCase();
            }).join('') || 'B';
            if (textCase === 'uppercase') {
                businessName = businessName.toUpperCase();
                tagline = tagline.toUpperCase();
            } else if (textCase === 'lowercase') {
                businessName = businessName.toLowerCase();
                tagline = tagline.toLowerCase();
            } else if (textCase === 'title') {
                businessName = businessName.toLowerCase().replace(/\b\w/g, function(c){ return c.toUpperCase(); });
                tagline = tagline.toLowerCase().replace(/\b\w/g, function(c){ return c.toUpperCase(); });
            }
            var usesIcon = logoStyle !== 'wordmark' && logoStyle !== 'lettermark' && iconMarkup;
            var iconBlock = '';
            var textX = 56;
            var nameY = 78;
            var taglineY = 104;
            var iconRect = Math.round(72 * (iconScale / 100));
            var iconRadius = Math.round(22 * (iconScale / 100));

            if (logoStyle === 'lettermark') {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-label="' + baeEscapeXml(businessName) + ' logo">' +
                    '<rect width="1200" height="420" rx="40" fill="white"/>' +
                    '<rect x="72" y="86" width="132" height="132" rx="34" fill="' + baeEscapeXml(primary) + '"/>' +
                    '<text x="138" y="172" text-anchor="middle" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="74" font-weight="800" fill="#ffffff">' + baeEscapeXml(initials) + '</text>' +
                    '<text x="246" y="160" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="82" font-weight="800" fill="' + baeEscapeXml(primary) + '" letter-spacing="8">' + baeEscapeXml(initials) + '</text>' +
                    '<text x="250" y="212" font-family="Arial, sans-serif" font-size="24" fill="' + baeEscapeXml(secondary) + '" letter-spacing="10" text-transform="uppercase">' + baeEscapeXml(businessName) + '</text>' +
                    (tagline ? '<text x="250" y="254" font-family="Arial, sans-serif" font-size="32" fill="' + baeEscapeXml(secondary) + '">' + baeEscapeXml(tagline) + '</text>' : '') +
                    '</svg>';
            }

            if (logoStyle === 'monogram') {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-label="' + baeEscapeXml(businessName) + ' logo">' +
                    '<rect width="1200" height="420" rx="40" fill="white"/>' +
                    '<text x="600" y="178" text-anchor="middle" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="130" font-weight="800" fill="' + baeEscapeXml(primary) + '" letter-spacing="10">' + baeEscapeXml(initials) + '</text>' +
                    '<rect x="548" y="204" width="104" height="6" rx="3" fill="' + baeEscapeXml(accent) + '"/>' +
                    '<text x="600" y="268" text-anchor="middle" font-family="Arial, sans-serif" font-size="32" fill="' + baeEscapeXml(secondary) + '" letter-spacing="12">' + baeEscapeXml(businessName) + '</text>' +
                    '</svg>';
            }

            if (usesIcon) {
                var cleanedIcon = iconMarkup
                    .replace(/currentColor/g, primary)
                    .replace(/width=\"18\"/g, 'width="22"')
                    .replace(/height=\"18\"/g, 'height="22"');
                var whiteIcon = cleanedIcon.replace(new RegExp(primary, 'g'), '#ffffff');
                if (logoStyle === 'emblem') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-label="' + baeEscapeXml(businessName) + ' logo">' +
                        '<rect width="1200" height="420" rx="40" fill="white"/>' +
                        '<circle cx="190" cy="170" r="88" fill="' + baeEscapeXml(primary) + '"/>' +
                        '<circle cx="190" cy="170" r="68" fill="' + baeEscapeXml(secondary) + '"/>' +
                        '<g transform="translate(179 159)">' + whiteIcon + '</g>' +
                        '<text x="320" y="162" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="78" font-weight="700" fill="' + baeEscapeXml(primary) + '">' + baeEscapeXml(businessName) + '</text>' +
                        (tagline ? '<text x="320" y="212" font-family="Arial, sans-serif" font-size="34" fill="' + baeEscapeXml(secondary) + '">' + baeEscapeXml(tagline) + '</text>' : '') +
                        '</svg>';
                }
                if (logoStyle === 'badge') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-label="' + baeEscapeXml(businessName) + ' logo">' +
                        '<rect width="1200" height="420" rx="40" fill="white"/>' +
                        '<rect x="120" y="110" width="610" height="126" rx="63" fill="#ffffff" stroke="' + baeEscapeXml(primary) + '" stroke-width="6"/>' +
                        '<circle cx="196" cy="173" r="42" fill="' + baeEscapeXml(primary) + '"/>' +
                        '<g transform="translate(185 162)">' + whiteIcon + '</g>' +
                        '<text x="262" y="184" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="58" font-weight="700" fill="' + baeEscapeXml(primary) + '">' + baeEscapeXml(businessName) + '</text>' +
                        '</svg>';
                }
                if (logoStyle === 'stacked') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-label="' + baeEscapeXml(businessName) + ' logo">' +
                        '<rect width="1200" height="420" rx="40" fill="white"/>' +
                        '<rect x="554" y="70" width="92" height="92" rx="28" fill="' + baeEscapeXml(primary) + '"/>' +
                        '<g transform="translate(589 105)">' + whiteIcon + '</g>' +
                        '<text x="600" y="246" text-anchor="middle" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="78" font-weight="700" fill="' + baeEscapeXml(primary) + '">' + baeEscapeXml(businessName) + '</text>' +
                        (tagline ? '<text x="600" y="292" text-anchor="middle" font-family="Arial, sans-serif" font-size="32" fill="' + baeEscapeXml(secondary) + '">' + baeEscapeXml(tagline) + '</text>' : '') +
                        '</svg>';
                }
                if (logoStyle === 'abstract') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-label="' + baeEscapeXml(businessName) + ' logo">' +
                        '<rect width="1200" height="420" rx="40" fill="white"/>' +
                        '<g transform="translate(600 170) rotate(-8)">' +
                        '<rect x="-56" y="-56" width="112" height="112" rx="28" fill="' + baeEscapeXml(primary) + '"/>' +
                        '<g transform="translate(-11 -11)">' + whiteIcon + '</g>' +
                        '</g>' +
                        '<text x="600" y="310" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" fill="' + baeEscapeXml(secondary) + '" letter-spacing="10">ABSTRACT MARK</text>' +
                        '</svg>';
                }
                if (logoStyle === 'combination') {
                    if (iconPosition === 'top') {
                        return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-label="' + baeEscapeXml(businessName) + ' logo">' +
                            '<rect width="1200" height="420" rx="40" fill="white"/>' +
                            '<rect x="' + (600 - Math.round(iconRect / 2)) + '" y="60" width="' + iconRect + '" height="' + iconRect + '" rx="' + iconRadius + '" fill="' + baeEscapeXml(primary) + '"/>' +
                            '<g transform="translate(589 85)">' + whiteIcon + '</g>' +
                            '<text x="600" y="' + (210 + spacing) + '" text-anchor="middle" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="72" font-weight="700" fill="' + baeEscapeXml(primary) + '">' + baeEscapeXml(businessName) + '</text>' +
                            (tagline ? '<text x="600" y="' + (252 + spacing) + '" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" fill="' + baeEscapeXml(secondary) + '">' + baeEscapeXml(tagline) + '</text>' : '') +
                            '</svg>';
                    }
                    iconBlock = '<rect x="' + (iconPosition === 'right' ? 1030 : 86) + '" y="70" width="' + iconRect + '" height="' + iconRect + '" rx="' + iconRadius + '" fill="' + baeEscapeXml(primary) + '"/>' +
                        '<g transform="translate(' + ((iconPosition === 'right' ? 1030 : 86) + Math.round(iconRect / 2) - 11) + ' 95)">' + whiteIcon + '</g>';
                    textX = iconPosition === 'right' ? 120 : (86 + iconRect + spacing + 20);
                } else {
                    iconBlock = '<circle cx="132" cy="120" r="58" fill="' + baeEscapeXml(primary) + '"/>' +
                        '<g transform="translate(121 109)">' + cleanedIcon + '</g>';
                    textX = 220;
                }
            }

            if (logoStyle === 'outlined') {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-label="' + baeEscapeXml(businessName) + ' logo">' +
                    '<rect width="1200" height="420" rx="40" fill="white"/>' +
                    '<rect x="132" y="108" width="640" height="146" rx="28" fill="white" stroke="' + baeEscapeXml(primary) + '" stroke-width="4"/>' +
                    '<text x="176" y="176" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="68" font-weight="700" fill="' + baeEscapeXml(primary) + '">' + baeEscapeXml(businessName) + '</text>' +
                    (tagline ? '<text x="178" y="216" font-family="Arial, sans-serif" font-size="24" fill="' + baeEscapeXml(secondary) + '" letter-spacing="6">' + baeEscapeXml(tagline) + '</text>' : '') +
                    '</svg>';
            }

            if (logoStyle === 'minimal') {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-label="' + baeEscapeXml(businessName) + ' logo">' +
                    '<rect width="1200" height="420" rx="40" fill="white"/>' +
                    '<text x="160" y="194" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="72" font-weight="800" fill="' + baeEscapeXml(primary) + '">' + baeEscapeXml(initials) + '</text>' +
                    '<rect x="248" y="178" width="42" height="6" rx="3" fill="' + baeEscapeXml(accent) + '"/>' +
                    '<text x="' + (320 + spacing) + '" y="194" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="62" font-weight="600" fill="' + baeEscapeXml(primary) + '">' + baeEscapeXml(businessName) + '</text>' +
                    '</svg>';
            }

            return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-label="' + baeEscapeXml(businessName) + ' logo">' +
                '<rect width="1200" height="420" rx="40" fill="white"/>' +
                iconBlock +
                '<text x="' + textX + '" y="' + nameY + '" font-family="' + baeEscapeXml(headingFont) + ', Arial, sans-serif" font-size="82" font-weight="700" fill="' + baeEscapeXml(primary) + '">' + baeEscapeXml(businessName) + '</text>' +
                (tagline ? '<text x="' + textX + '" y="' + taglineY + '" font-family="Arial, sans-serif" font-size="34" fill="' + baeEscapeXml(secondary) + '">' + baeEscapeXml(tagline) + '</text>' : '') +
                '</svg>';
        }

        function baeDownloadGeneratedLogoSvg() {
            var svgMarkup = baeBuildLogoSvgMarkup();
            if (!svgMarkup) {
                baeSetLogoDownloadStatus('SVG export is available for generated logos or uploaded SVG files.', '#fb7185');
                return;
            }
            baeTriggerBlobDownload(new Blob([svgMarkup], { type: 'image/svg+xml;charset=utf-8' }), baeGetLogoDownloadName('svg'));
            baeSetLogoDownloadStatus('SVG downloaded.', '#34d399');
        }

        function baeDownloadSvgAsPng(svgMarkup) {
            if (!svgMarkup) {
                baeSetLogoDownloadStatus('PNG export is available for uploaded logo files or generated logos.', '#fb7185');
                return;
            }
            var svgBlob = new Blob([svgMarkup], { type: 'image/svg+xml;charset=utf-8' });
            var svgUrl = URL.createObjectURL(svgBlob);
            var img = new Image();
            img.onload = function() {
                var canvas = document.createElement('canvas');
                canvas.width = 1200;
                canvas.height = 420;
                var ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(svgUrl);
                canvas.toBlob(function(blob) {
                    if (!blob) {
                        baeSetLogoDownloadStatus('PNG export failed. Please try again.', '#fb7185');
                        return;
                    }
                    baeTriggerBlobDownload(blob, baeGetLogoDownloadName('png'));
                    baeSetLogoDownloadStatus('PNG downloaded.', '#34d399');
                }, 'image/png');
            };
            img.onerror = function() {
                URL.revokeObjectURL(svgUrl);
                baeSetLogoDownloadStatus('PNG export failed. Please try again.', '#fb7185');
            };
            img.src = svgUrl;
        }

        function baeDownloadUploadedLogoAsPng(url) {
            if (!url) {
                baeSetLogoDownloadStatus('Upload or build a logo first.', '#fb7185');
                return;
            }
            baeTriggerUrlDownload(url, baeGetLogoDownloadName('png'));
            baeSetLogoDownloadStatus('Logo download started.', '#34d399');
        }

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                var file = this.files[0];
                if (!file) return;
                baeSetLogoDownloadStatus('');
                uploadStatus.textContent = 'Uploading...';
                uploadStatus.style.color = 'var(--text-3)';

                var fd = new FormData();
                fd.append('action', 'bae_upload_logo');
                fd.append('nonce', overviewNonce);
                fd.append('logo_file', file);

                fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (j.success && j.data.url) {
                        logoUrlHidden.value = j.data.url;
                        uploadStatus.textContent = 'Uploaded!';
                        uploadStatus.style.color = '#34d399';
                        // Show preview
                        logoPreviewWrap.innerHTML = '<img src="'+j.data.url+'" style="max-width:100%;max-height:100%;object-fit:contain;" id="bae-logo-preview-img">';
                        // Hide CSS builder — uploaded logo takes over
                        var cssBuilderPanel = document.getElementById('bae-logo-css-builder-panel');
                        if (cssBuilderPanel) { cssBuilderPanel.style.opacity = '0'; setTimeout(function(){ cssBuilderPanel.style.display = 'none'; }, 300); }
                        // Show check bg button
                        var checkBtn = document.getElementById('bae-logo-check-btn');
                        if (!checkBtn) {
                            var removeBtn = document.getElementById('bae-logo-remove-btn');
                            var newCheckBtn = document.createElement('button');
                            newCheckBtn.type = 'button';
                            newCheckBtn.id = 'bae-logo-check-btn';
                            newCheckBtn.className = 'bae-btn bae-btn-outline bae-btn-sm';
                            newCheckBtn.style.cssText = 'display:inline-flex;align-items:center;gap:5px;';
                            newCheckBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg> Check Background';
                            newCheckBtn.addEventListener('click', showBgChecker);
                            uploadStatus.parentNode.insertBefore(newCheckBtn, uploadStatus);
                        }
                        setTimeout(function() { uploadStatus.textContent = ''; }, 2500);
                    } else {
                        uploadStatus.textContent = (j.data && j.data.message) ? j.data.message : 'Upload failed.';
                        uploadStatus.style.color = '#fb7185';
                    }
                })
                .catch(function() {
                    uploadStatus.textContent = 'Upload error.';
                    uploadStatus.style.color = '#fb7185';
                });
            });
        }

        // Remove logo
        var removeBtn = document.getElementById('bae-logo-remove-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                logoUrlHidden.value = '';
                logoPreviewWrap.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';
                var bgChecker = document.getElementById('bae-logo-bg-checker');
                if (bgChecker) bgChecker.style.display = 'none';
                removeBtn.style.display = 'none';
                // Show CSS builder again — no uploaded logo
                var cssBuilderPanel = document.getElementById('bae-logo-css-builder-panel');
                if (cssBuilderPanel) { cssBuilderPanel.style.display = ''; setTimeout(function(){ cssBuilderPanel.style.opacity = '1'; }, 20); }
                baeSetLogoDownloadStatus('Using generated logo export.', 'var(--text-3)');
            });
        }

        if (downloadPngBtn) {
            downloadPngBtn.addEventListener('click', function() {
                var uploadedUrl = (logoUrlHidden && logoUrlHidden.value) ? logoUrlHidden.value : '';
                baeSetLogoDownloadStatus('');
                if (uploadedUrl) {
                    baeDownloadUploadedLogoAsPng(uploadedUrl);
                    return;
                }
                baeDownloadSvgAsPng(baeBuildLogoSvgMarkup());
            });
        }

        if (downloadSvgBtn) {
            downloadSvgBtn.addEventListener('click', function() {
                var uploadedUrl = (logoUrlHidden && logoUrlHidden.value) ? logoUrlHidden.value : '';
                baeSetLogoDownloadStatus('');
                if (uploadedUrl) {
                    if (!baeIsSvgUpload(uploadedUrl)) {
                        baeSetLogoDownloadStatus('SVG download is only available for uploaded SVG files or generated logos.', '#fb7185');
                        return;
                    }
                    baeTriggerUrlDownload(uploadedUrl, baeGetLogoDownloadName('svg'));
                    baeSetLogoDownloadStatus('SVG download started.', '#34d399');
                    return;
                }
                baeDownloadGeneratedLogoSvg();
            });
        }

        // ── Logo Background Checker ───────────────────────────────────────
        var checkBtn = document.getElementById('bae-logo-check-btn');
        if (checkBtn) checkBtn.addEventListener('click', showBgChecker);

        function showBgChecker() {
            var logoUrl = (logoUrlHidden && logoUrlHidden.value) ? logoUrlHidden.value : '';
            if (!logoUrl) return;

            var checker = document.getElementById('bae-logo-bg-checker');
            var grid    = document.getElementById('bae-logo-bg-grid');
            checker.style.display = 'block';
            grid.innerHTML = '';

            // Get current brand colors
            var pc = (document.getElementById('bae-primary-color')   || {}).value || '#1a1a2e';
            var sc = (document.getElementById('bae-secondary-color') || {}).value || '#16213e';
            var ac = (document.getElementById('bae-accent-color')    || {}).value || '#e94560';

            var bgs = [
                { color:'#ffffff', label:'White' },
                { color:'#f9fafb', label:'Off-White' },
                { color:'#111827', label:'Near Black' },
                { color:'#000000', label:'Black' },
                { color:pc,        label:'Primary' },
                { color:sc,        label:'Secondary' },
                { color:ac,        label:'Accent' },
                { color:'#f3f4f6', label:'Light Gray' },
                { color:'#1f2937', label:'Dark Gray' },
                { color:'#fef3c7', label:'Cream' },
                { color:'#dbeafe', label:'Sky Blue' },
                { color:'#d1fae5', label:'Mint' },
            ];

            bgs.forEach(function(bg) {
                var tile = document.createElement('div');
                tile.style.cssText = 'border-radius:10px;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:all .2s;';
                tile.title = 'Use ' + bg.label + ' as primary color';

                var preview = document.createElement('div');
                preview.style.cssText = 'height:70px;background:'+bg.color+';display:flex;align-items:center;justify-content:center;padding:8px;';
                var img = document.createElement('img');
                img.src = logoUrl;
                img.style.cssText = 'max-width:100%;max-height:100%;object-fit:contain;';
                preview.appendChild(img);

                var label = document.createElement('div');
                label.style.cssText = 'background:var(--surface);padding:4px 6px;font-size:10px;font-weight:600;color:var(--text-2);text-align:center;';
                label.textContent = bg.label;

                tile.appendChild(preview);
                tile.appendChild(label);

                tile.addEventListener('mouseenter', function() { tile.style.borderColor = '#F32D86'; });
                tile.addEventListener('mouseleave', function() { tile.style.borderColor = 'transparent'; });
                tile.addEventListener('click', function() {
                    // Apply this bg color as primary
                    var pcInput  = document.querySelector('[name="primary_color"]');
                    var pcPicker = document.querySelector('[name="primary_color_picker"]');
                    if (pcInput)  pcInput.value  = bg.color;
                    if (pcPicker) pcPicker.value = bg.color;
                    tile.style.borderColor = '#F32D86';
                    setTimeout(function() { tile.style.borderColor = 'transparent'; }, 1500);
                });

                grid.appendChild(tile);
            });

            if (window.gsap) gsap.fromTo(checker, {opacity:0, y:8}, {opacity:1, y:0, duration:.3, ease:'power3.out'});
        }

        // Icon picker
        window.baeSelectIcon = function(el) {
            document.querySelectorAll('.bae-icon-tile').forEach(function(t) {
                t.classList.remove('selected');
                t.style.borderColor = 'transparent';
                t.style.background  = 'var(--surface)';
            });
            el.classList.add('selected');
            el.style.borderColor = '#F32D86';
            el.style.background  = 'rgba(243,45,134,.15)';
            var hidden = document.getElementById('bae-logo-icon-hidden');
            if (hidden) hidden.value = el.dataset.value;
            if (window.gsap) gsap.fromTo(el, {scale:.9}, {scale:1, duration:.2, ease:'back.out(2)'});
        };

    })();
    </script>
    <?php
    return ob_get_clean();
}

// =============================================================================
// TAB: IDENTITY BOARD
// CHANGED: Colors run through bae_safe_color() before rendering
// =============================================================================
