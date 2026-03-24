<?php
/*
 * KBF user dashboard tab: Find Funds.
 */

function kbf_dashboard_find_funds_tab() {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $current_user_id = get_current_user_id();

    // Filters from GET
    $q    = isset($_GET['ff_q'])   ? sanitize_text_field($_GET['ff_q'])   : '';
    $cat  = isset($_GET['ff_cat']) ? sanitize_text_field($_GET['ff_cat']) : '';
    $loc  = isset($_GET['ff_loc']) ? sanitize_text_field($_GET['ff_loc']) : '';
    $sort = isset($_GET['ff_sort'])? sanitize_text_field($_GET['ff_sort']): 'newest';

    $where = "WHERE f.status='active'"; $params = [];
    if($q)  { $where .= " AND (f.title LIKE %s OR f.description LIKE %s)"; $params[] = "%".$wpdb->esc_like($q)."%"; $params[] = "%".$wpdb->esc_like($q)."%"; }
    if($cat){ $where .= " AND f.category=%s"; $params[] = $cat; }
    if($loc){ $where .= " AND f.location LIKE %s"; $params[] = "%".$wpdb->esc_like($loc)."%"; }
    $order = $sort === 'most_funded' ? 'f.raised_amount DESC' : ($sort === 'ending_soon' ? 'f.deadline ASC' : 'f.created_at DESC');
    $sql = "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID {$where} ORDER BY {$order}";
    $funds = !empty($params) ? $wpdb->get_results($wpdb->prepare($sql,...$params)) : $wpdb->get_results($sql); // phpcs:ignore
    $cats  = kbf_get_categories();
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $fund_details_url = kbf_get_page_url('fund_details');
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $base_url = strtok($_SERVER['REQUEST_URI'],'?').'?kbf_tab=find_funds';

    ob_start(); ?>
    <script>if(typeof ajaxurl==='undefined') var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';</script>

    <!-- MODAL: Sponsor -->
    <div id="kbff-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Sponsor This Fund</h3>
          <button class="kbf-modal-close" onclick="document.getElementById('kbff-modal-sponsor').style.display='none'">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <div id="kbff-fund-preview" style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:18px;"></div>
          <form id="kbff-sponsor-form" onsubmit="return false;">
            <input type="hidden" name="fund_id" id="kbff-fund-id">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Organization</label><input type="text" name="sponsor_name" id="kbff-name" placeholder="Your name, company, or org"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;">
                <label class="kbf-checkbox-row"><input type="checkbox" id="kbff-anon" onchange="document.getElementById('kbff-name').disabled=this.checked"> Sponsor Anonymously</label>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Amount (PHP) *</label>
              <input type="number" name="amount" placeholder="Min. ₱1" min="1" step="1" required>
              <div id="kbff-sponsor-limit" class="kbf-meta" style="margin-top:4px;"></div>
            </div>
            <div class="kbf-form-group">
              <label>Encouraging Message (optional)</label>
              <textarea name="message" id="kbff-message" rows="2" maxlength="300" placeholder="Leave a message for the organizer..."></textarea>
              <div class="kbf-char-count" id="kbff-message-count">0/300</div>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Email (for receipt)</label><input type="email" name="email" placeholder="your@email.com"></div>
              <div class="kbf-form-group"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX"></div>
            </div>
            <input type="hidden" name="payment_method" value="online_payment">
            <?php if($demo_mode): ?>
            <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;margin-top:6px;">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
              <div><strong>Demo Mode:</strong> Redirects to Maya sandbox checkout. No real payment is processed.</div>
            </div>
            <?php endif; ?>
            <div id="kbff-sponsor-msg" style="margin-top:10px;"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbff-modal-sponsor').style.display='none'">Cancel</button>
          <button type="button" class="kbf-btn kbf-btn-primary" id="kbff-sponsor-submit" onclick="kbffSubmitSponsor('<?php echo $nonce_sponsor; ?>')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            Confirm Sponsorship
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: Report -->
    <div id="kbff-modal-report" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Report This Fund</h3><button class="kbf-modal-close" onclick="document.getElementById('kbff-modal-report').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbff-report-form">
            <input type="hidden" name="fund_id" id="kbff-report-fund-id">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email" placeholder="your@email.com"></div>
            <div class="kbf-form-group"><label>Reason *</label>
              <select name="reason" required><option value="">Select Reason</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Information</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select>
            </div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" placeholder="Describe the issue..." required></textarea></div>
            <div id="kbff-report-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbff-modal-report').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbffSubmitReport('<?php echo $nonce_report; ?>')">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- Header -->
    <div style="background:#fff;border:none;border-radius:16px;padding:18px 20px;margin-bottom:18px;box-shadow:none;">
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;flex:1;align-items:center;" id="kbff-search-form">
          <input type="hidden" name="kbf_tab" value="find_funds">
          <?php if($cat): ?><input type="hidden" name="ff_cat" value="<?php echo esc_attr($cat); ?>"><?php endif; ?>
          <?php if($sort && $sort!=='newest'): ?><input type="hidden" name="ff_sort" value="<?php echo esc_attr($sort); ?>"><?php endif; ?>

          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="width:28px;height:28px;border-radius:8px;background:#eef4ff;display:inline-flex;align-items:center;justify-content:center;">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/tags.svg" alt="" width="14" height="14">
              </span>
              <select id="kbff-cat-select" style="padding:7px 10px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:12.5px;background:#fff;color:var(--kbf-text);min-width:160px;">
                <option value="">All Categories</option>
                <?php foreach($cats as $c): ?>
                  <option value="<?php echo esc_attr($c); ?>" <?php echo $cat===$c?'selected':''; ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="width:28px;height:28px;border-radius:8px;background:#eef4ff;display:inline-flex;align-items:center;justify-content:center;">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/filter.svg" alt="" width="14" height="14">
              </span>
              <select id="kbff-sort-select" style="padding:7px 10px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:12.5px;background:#fff;color:var(--kbf-text);min-width:140px;">
                <option value="newest" <?php echo ($sort==='newest'||!$sort)?'selected':''; ?>>Newest</option>
                <option value="most_funded" <?php echo $sort==='most_funded'?'selected':''; ?>>Most Funded</option>
                <option value="ending_soon" <?php echo $sort==='ending_soon'?'selected':''; ?>>Ending Soon</option>
              </select>
            </div>
          </div>

          <input type="text" name="ff_q" value="<?php echo esc_attr($q); ?>" placeholder="Search by title or description..." style="flex:2;min-width:220px;padding:9px 12px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:13px;background:#fff;color:var(--kbf-text);">
          <input type="text" name="ff_loc" id="kbff-loc-input" value="<?php echo esc_attr($loc); ?>" placeholder="Location (city, province)..." style="flex:1;min-width:180px;padding:9px 12px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:13px;background:#fff;color:var(--kbf-text);">

          <button type="button" id="kbff-near-me-btn" onclick="kbffNearMe()" class="kbf-btn kbf-btn-secondary" style="white-space:nowrap;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Near Me
          </button>
          <button type="submit" class="kbf-btn kbf-btn-primary">Search</button>
          <?php if($q||$cat||$loc): ?><a href="?kbf_tab=find_funds" class="kbf-btn kbf-btn-secondary" style="padding:9px 14px;">Clear</a><?php endif; ?>
        </form>
      </div>
      <?php if($loc): ?>
      <div style="margin-top:10px;font-size:12.5px;color:var(--kbf-text-sm);display:flex;align-items:center;gap:6px;">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Showing funds near: <strong><?php echo esc_html($loc); ?></strong>
      </div>
      <?php endif; ?>
    </div>

    <!-- Fund grid -->
    <?php if(empty($funds)): ?>
    <div class="kbf-empty" style="padding:60px 20px;">
      <svg width="44" height="44" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 14px;display:block;opacity:.3;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <p style="font-size:15px;font-weight:600;color:var(--kbf-navy);margin-bottom:4px;">No funds found</p>
      <p style="color:var(--kbf-slate);font-size:13px;">Try adjusting your search or category filter.</p>
      <?php if($q||$cat): ?><a href="?kbf_tab=find_funds" class="kbf-btn kbf-btn-primary" style="margin-top:14px;">Clear Filters</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:20px;">
      <?php foreach($funds as $f):
        $pct   = $f->goal_amount > 0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $days  = $f->deadline ? max(0,ceil((strtotime($f->deadline)-time())/86400)) : null;
        $photos = $f->photos ? json_decode($f->photos,true) : [];
        $cover  = !empty($photos[0]) ? $photos[0] : null;
        $detail_url = esc_url(add_query_arg('fund_id',$f->id,$fund_details_url));
        $is_own = ($f->business_id == $current_user_id);
      ?>
      <div style="background:#fff;border:1px solid var(--kbf-border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .2s,transform .15s;" onmouseover="this.style.boxShadow='0 8px 28px rgba(15,32,68,.13)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">

        <!-- Photo / cover -->
        <a href="<?php echo $detail_url; ?>" style="text-decoration:none;display:block;position:relative;">
          <?php if($cover): ?>
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($f->title); ?>" style="width:100%;height:180px;object-fit:cover;display:block;">
          <?php else: ?>
            <div style="width:100%;height:180px;background:linear-gradient(135deg,var(--kbf-navy) 0%,#243b78 100%);display:flex;align-items:center;justify-content:center;">
              <svg width="40" height="40" fill="none" stroke="rgba(255,255,255,.2)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </div>
          <?php endif; ?>
          <!-- Overlays -->
          <div style="position:absolute;top:10px;left:10px;background:rgba(15,32,68,.8);backdrop-filter:blur(4px);color:var(--kbf-accent);padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><?php echo esc_html($f->category); ?></div>
          <?php if($days!==null): ?>
          <div style="position:absolute;top:10px;right:10px;background:<?php echo $days<7?'rgba(220,38,38,.85)':'rgba(15,32,68,.75)'; ?>;backdrop-filter:blur(4px);color:#fff;padding:3px 10px;border-radius:99px;font-size:10px;font-weight:800;"><?php echo $days; ?>d left</div>
          <?php endif; ?>
          <?php if($is_own): ?>
          <div style="position:absolute;bottom:10px;left:10px;background:rgba(15,32,68,.85);color:var(--kbf-accent);padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700;">Your Fund</div>
          <?php endif; ?>
        </a>

        <!-- Card body -->
        <div style="padding:16px;flex:1;display:flex;flex-direction:column;">
          <a href="<?php echo $detail_url; ?>" style="text-decoration:none;">
            <h4 style="font-size:14.5px;font-weight:700;color:var(--kbf-navy);margin:0 0 5px;line-height:1.4;"><?php echo esc_html($f->title); ?></h4>
          </a>
          <p style="font-size:12.5px;color:var(--kbf-slate);margin:0 0 10px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.5;flex:1;"><?php echo esc_html(wp_trim_words($f->description,16)); ?></p>

          <!-- Location + Organizer -->
          <div style="font-size:11.5px;color:var(--kbf-slate);margin-bottom:10px;display:flex;flex-direction:column;gap:4px;">
            <span style="display:flex;align-items:center;gap:3px;">
              <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <?php echo esc_html($f->location); ?>
            </span>
            <span style="color:var(--kbf-blue);font-weight:600;">by <a href="<?php echo esc_url(add_query_arg('organizer_id',$f->business_id,kbf_get_page_url('organizer_profile'))); ?>" style="color:inherit;text-decoration:none;font-weight:700;"><?php echo esc_html($f->organizer_name?:'Organizer'); ?></a></span>
          </div>

          <!-- Progress -->
          <div class="kbf-progress-wrap" style="margin-bottom:6px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:14px;">
            <span><strong style="color:var(--kbf-navy);font-size:13.5px;">₱<?php echo number_format($f->raised_amount,0); ?></strong> <span style="color:var(--kbf-slate);">raised</span></span>
            <span style="color:var(--kbf-slate);"><?php echo round($pct); ?>% of ₱<?php echo number_format($f->goal_amount,0); ?></span>
          </div>

          <!-- Action buttons -->
          <?php if($is_own): ?>
          <div style="display:grid;grid-template-columns:1fr auto auto;gap:8px;">
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-secondary" style="font-size:12.5px;text-align:center;">View Details</a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbffShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')" title="Share">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            </button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbffOpenReport(<?php echo $f->id; ?>)" title="Report">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </button>
          </div>
          <?php else: ?>
          <div style="display:grid;grid-template-columns:1fr auto auto auto;gap:7px;align-items:center;">
            <button class="kbf-btn kbf-btn-primary" style="font-size:12.5px;" onclick="kbffOpenSponsor(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>',<?php echo $f->goal_amount; ?>,<?php echo $f->raised_amount; ?>,'<?php echo esc_js(isset($cover) ? $cover : ''); ?>')">
              <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
              Sponsor
            </button>
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-secondary kbf-btn-sm" title="View full details">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbffShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')" title="Share">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            </button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbffOpenReport(<?php echo $f->id; ?>)" title="Report">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </button>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <script>
    (function(){
        var catSel = document.getElementById('kbff-cat-select');
        var sortSel = document.getElementById('kbff-sort-select');
        var msg = document.getElementById('kbff-message');
        var msgCount = document.getElementById('kbff-message-count');
        if (!catSel || !sortSel) return;
        function updateMsgCount(){
            if(!msg || !msgCount) return;
            msgCount.textContent = (msg.value ? msg.value.length : 0) + '/300';
        }
        if (msg && msgCount){
            updateMsgCount();
            msg.addEventListener('input', updateMsgCount);
        }
        function buildUrl(){
            var url = '<?php echo esc_url($base_url); ?>';
            var params = [];
            <?php if($q): ?>params.push('ff_q=<?php echo urlencode($q); ?>');<?php endif; ?>
            <?php if($loc): ?>params.push('ff_loc=<?php echo urlencode($loc); ?>');<?php endif; ?>
            if (catSel.value) params.push('ff_cat=' + encodeURIComponent(catSel.value));
            if (sortSel.value) params.push('ff_sort=' + encodeURIComponent(sortSel.value));
            if (params.length) url += '&' + params.join('&');
            return url;
        }
        catSel.addEventListener('change', function(){ window.location.href = buildUrl(); });
        sortSel.addEventListener('change', function(){ window.location.href = buildUrl(); });
    })();

    window.kbffOpenReport=function(id){document.getElementById('kbff-report-fund-id').value=id;document.getElementById('kbff-modal-report').style.display='flex';};
    window.kbffOpenSponsor=function(id,title,goal,raised,img){
        document.getElementById('kbff-fund-id').value=id;
        document.getElementById('kbff-sponsor-form').reset();
        const pct=goal>0?Math.min(100,Math.round((raised/goal)*100)):0;
        const remaining = goal>0 ? Math.max(0, goal - raised) : 0;
        const limitEl = document.getElementById('kbff-sponsor-limit');
        const amountEl = document.querySelector('#kbff-sponsor-form input[name="amount"]');
        if (remaining > 0) {
            if (limitEl) limitEl.textContent = 'Max allowed: ₱' + parseFloat(remaining).toLocaleString() + ' (remaining goal)';
            if (amountEl) amountEl.max = remaining;
        } else {
            if (limitEl) limitEl.textContent = '';
            if (amountEl) amountEl.removeAttribute('max');
        }
        document.getElementById('kbff-fund-preview').innerHTML=
            (img?'<img src="'+img+'" style="width:100%;height:110px;object-fit:cover;border-radius:6px;margin-bottom:10px;display:block;">':'')
            +'<strong style="font-size:14px;color:var(--kbf-navy);">'+title+'</strong>'
            +'<div style="margin-top:8px;" class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:'+pct+'%"></div></div>'
            +'<div style="display:flex;justify-content:space-between;font-size:12px;margin-top:5px;color:var(--kbf-slate);">'
            +'<span>₱'+parseFloat(raised).toLocaleString()+' raised</span><span>'+pct+'% of ₱'+parseFloat(goal).toLocaleString()+'</span></div>';
        document.getElementById('kbff-modal-sponsor').style.display='flex';
    };
    window.kbffSubmitSponsor=function(nonce){
        const form=document.getElementById('kbff-sponsor-form');
        const btn=document.getElementById('kbff-sponsor-submit');
        const msg=document.getElementById('kbff-sponsor-msg');
        form.querySelectorAll('.kbf-field-error').forEach(function(el){ el.remove(); });
        var first=null;
        form.querySelectorAll('[required]').forEach(function(el){
            if(!el.value || !el.value.trim()){
                var group = el.closest('.kbf-form-group');
                if(group){
                    var err = document.createElement('div');
                    err.className = 'kbf-field-error';
                    err.textContent = 'This field is required.';
                    group.appendChild(err);
                }
                if(!first) first = el;
            }
        });
        if(first){ first.focus(); return; }
        const amountEl = form.querySelector('input[name="amount"]');
        const maxVal = amountEl && amountEl.max ? parseFloat(amountEl.max) : null;
        const amt = amountEl ? parseFloat(amountEl.value || '0') : 0;
        if (maxVal && amt > maxVal) {
            msg.innerHTML = '<div class="kbf-alert kbf-alert-error">You cannot give more than ₱' + maxVal.toLocaleString() + ' for this fund.</div>';
            return;
        }
        kbfSetBtnLoading(btn,true,'Processing...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);
        fd.append('action', 'kbf_create_checkout');
        fd.append('nonce',nonce);
        fd.append('is_anonymous',document.getElementById('kbff-anon').checked?'1':'0');
        kbfFetchJson(ajaxurl, fd, (j)=>{
            if(j.success){
                console.log('KBF checkout response:', j);
                if(j.data && j.data.checkout_url){
                    btn.innerHTML='Redirecting to payment...';
                    window.open(j.data.checkout_url, '_blank');
                } else {
                    msg.innerHTML='<div class="kbf-alert kbf-alert-error">Maya checkout URL was not returned. Please check your Maya API keys and try again.</div>';
                    kbfSetBtnLoading(btn,false);
                    kbfSetSkeleton(msg,false);
                }
            } else {
                msg.innerHTML='<div class="kbf-alert kbf-alert-error">'+j.data.message+'</div>';
                kbfSetBtnLoading(btn,false);
                kbfSetSkeleton(msg,false);
            }
        }, (err)=>{
            console.error('KBF checkout error:', err);
            msg.innerHTML='<div class="kbf-alert kbf-alert-error">'+err+'</div>';
            kbfSetBtnLoading(btn,false);
            kbfSetSkeleton(msg,false);
        });
    };
    window.kbffSubmitReport=function(nonce){
        const form=document.getElementById('kbff-report-form');
        const btn=document.querySelector('#kbff-modal-report .kbf-modal-footer .kbf-btn-danger');
        const msg=document.getElementById('kbff-report-msg');
        kbfSetBtnLoading(btn,true,'Submitting...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            msg.innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if(j.success)setTimeout(()=>{document.getElementById('kbff-modal-report').style.display='none';},1800);
            else{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    };
    </script>
    <?php
    return ob_get_clean();
}


// ============================================================
// DASHBOARD TAB: Admin Embed (admin-only inline panel)
// ============================================================

