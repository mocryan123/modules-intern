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

    ob_start();
    ?>

    <!-- ================== CSS ================== -->
    <style>
      .kbf-explore-grid{
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
        gap:18px;
        overflow:visible;
      }
      .kbf-explore-card{
        background:#fff;
        border:1px solid var(--kbf-border);
        border-radius:18px;
        overflow:visible;
        box-shadow:var(--kbf-shadow);
        display:flex;
        flex-direction:column;
        transition:box-shadow .2s ease, transform .15s ease;
        position:relative;
        z-index:1;
      }
      .kbf-explore-card.is-menu-open{z-index:60;}
      .kbf-explore-card:hover{
        box-shadow:var(--kbf-shadow-lg);
        transform:translateY(-2px);
      }
      .kbf-explore-media{
        position:relative;
        display:block;
        text-decoration:none;
        padding:12px 12px 0;
      }
      .kbf-explore-media-frame{
        border-radius:16px;
        overflow:hidden;
      }
      .kbf-explore-media img{
        width:100%;
        height:170px;
        object-fit:cover;
        display:block;
        border-radius:12px;
        background:#f1f5f9;
      }
      .kbf-explore-fallback{
        width:100%;
        height:170px;
        border-radius:12px;
        background:linear-gradient(135deg,#dce9ff 0%,#cfe2ff 55%,#eaf2ff 100%);
        display:flex;
        align-items:center;
        justify-content:center;
      }
      .kbf-explore-chip{
        position:absolute;
        top:22px;
        left:22px;
        background:rgba(15,23,42,.55);
        color:#ffffff;
        padding:4px 10px;
        border-radius:999px;
        font-size:10px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.4px;
        border:1px solid rgba(15,23,42,.25);
        backdrop-filter:blur(2px);
        text-shadow:0 1px 2px rgba(15,23,42,.35);
      }
      .kbf-explore-tag{
        position:absolute;
        bottom:12px;
        left:22px;
        background:rgba(15,23,42,.55);
        color:#ffffff;
        padding:4px 10px;
        border-radius:999px;
        font-size:10px;
        font-weight:700;
        border:1px solid rgba(15,23,42,.25);
        backdrop-filter:blur(2px);
        text-shadow:0 1px 2px rgba(15,23,42,.35);
      }
      .kbf-explore-body{
        padding:14px 16px 16px;
        display:flex;
        flex-direction:column;
        gap:10px;
        flex:1;
        position:relative;
        z-index:2;
      }
      .kbf-explore-title{
        font-size:14.5px;
        font-weight:700;
        color:var(--kbf-navy);
        margin:0;
        line-height:1.45;
      }
      .kbf-explore-title-row{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
      }
      .kbf-explore-title-text{
        flex:1;
        min-width:0;
        overflow:hidden;
        white-space:nowrap;
        text-overflow:ellipsis;
      }
      .kbf-explore-days-inline{
        font-size:11.5px;
        color:#64748b;
        font-weight:600;
        flex-shrink:0;
      }
      .kbf-explore-desc{
        font-size:12.5px;
        color:var(--kbf-slate);
        margin:0;
        overflow:hidden;
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        line-height:1.5;
      }
      .kbf-explore-meta{
        display:flex;
        align-items:center;
        justify-content:space-between;
        font-size:11.5px;
        color:var(--kbf-slate);
        gap:8px;
      }
      .kbf-explore-loc{
        flex:1;
        min-width:0;
        overflow:hidden;
        white-space:nowrap;
        text-overflow:ellipsis;
      }
      .kbf-explore-progress{
        height:6px;
        background:#eef2f7;
        border-radius:999px;
        overflow:hidden;
      }
      .kbf-explore-progress span{
        display:block;
        height:100%;
        background:linear-gradient(90deg,#63a4ff 0%,#3b82f6 100%);
        border-radius:999px;
      }
      .kbf-explore-footer{
        display:flex;
        align-items:center;
        justify-content:space-between;
        font-size:12px;
        color:var(--kbf-slate);
        margin-top:2px;
      }
      .kbf-explore-amount{
        font-weight:800;
        color:#1f2a44;
        font-size:13.5px;
      }
      .kbf-explore-actions{
        display:grid;
        gap:8px;
      }
      .kbf-explore-actions.is-own{ grid-template-columns:1fr auto auto; }
      .kbf-explore-actions.is-public{ grid-template-columns:1fr auto auto auto; }
      .kbf-explore-card .kbf-btn-primary{
        box-shadow:
          0 1px 2px rgba(32, 112, 224, 0.18),
          0 3px 10px rgba(42, 120, 220, 0.18);
      }
      .kbf-explore-card .kbf-btn-primary::before{
        opacity:0;
      }
      .kbf-explore-card .kbf-btn-primary:hover{
        box-shadow:
          0 2px 6px rgba(32, 112, 224, 0.16),
          0 6px 16px rgba(42, 120, 220, 0.22);
      }
      .kbf-explore-more-wrap{position:relative;z-index:30;}
      .kbf-explore-more-menu{
        position:absolute;
        right:0;
        top:calc(100% + 8px);
        background:#fff;
        border:1px solid var(--kbf-border);
        border-radius:12px;
        box-shadow:var(--kbf-shadow);
        padding:6px;
        min-width:150px;
        display:none;
        z-index:50;
      }
      .kbf-explore-more-menu.open{display:block;}
      .kbf-explore-actions .kbf-btn-sm{
        height:32px;
        min-width:32px;
        padding:0;
        display:inline-flex;
        align-items:center;
        justify-content:center;
      }
      .kbf-explore-actions .kbf-btn-sm img{margin:0;}
      .kbf-explore-more-menu button{
        width:100%;
        justify-content:flex-start;
        gap:8px;
        margin:4px 0;
      }
      .kbf-explore-pager{
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        padding:10px 2px 0;
        border:none;
        background:transparent;
        font-size:12px;
        color:var(--kbf-slate);
        flex-wrap:wrap;
      }
      .kbf-explore-pager .kbf-pager-pages{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:wrap;
      }
      .kbf-explore-pager .kbf-table-pager-btn{
        border:1px solid #dbe3ef;
        background:#fff;
        color:var(--kbf-navy);
        padding:6px 10px;
        border-radius:8px;
        font-size:12px;
        font-weight:600;
        cursor:pointer;
        transition:all .15s ease;
      }
      .kbf-explore-pager .kbf-table-pager-btn:hover{border-color:#bcd2f3;background:#f8fafc;}
      .kbf-explore-pager .kbf-table-pager-btn:disabled{opacity:.45;cursor:not-allowed;}
      .kbf-explore-pager .kbf-page-btn{
        min-width:30px;
        height:30px;
        padding:0 8px;
        border-radius:10px;
        border:1px solid transparent;
        background:#fff;
        color:var(--kbf-slate);
        font-weight:600;
        cursor:pointer;
        transition:all .15s ease;
      }
      .kbf-explore-pager .kbf-page-btn:hover{border-color:#cbd5f1;color:#1f2a44;}
      .kbf-explore-pager .kbf-page-btn.is-active{
        background:#eef4ff;
        border-color:#dbe7ff;
        color:#1f2a44;
      }
      .kbf-explore-pager .kbf-page-gap{padding:0 2px;color:#94a3b8;font-weight:600;}
    </style>

    <!-- ================== HTML ================== -->
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
        <form method="GET" style="display:flex;gap:8px;flex-wrap:nowrap;flex:1;align-items:center;" id="kbff-search-form">
          <input type="hidden" name="kbf_tab" value="find_funds">
          <?php if($cat): ?><input type="hidden" name="ff_cat" value="<?php echo esc_attr($cat); ?>"><?php endif; ?>
          <?php if($sort && $sort!=='newest'): ?><input type="hidden" name="ff_sort" value="<?php echo esc_attr($sort); ?>"><?php endif; ?>

          <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
            <span style="width:28px;height:28px;border-radius:8px;background:#eef4ff;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/tag-fill.svg" alt="" width="14" height="14">
            </span>
            <select id="kbff-cat-select" style="padding:7px 10px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:12.5px;background:#fff;color:var(--kbf-text);min-width:140px;">
              <option value="">All Categories</option>
              <?php foreach($cats as $c): ?>
                <option value="<?php echo esc_attr($c); ?>" <?php echo $cat===$c?'selected':''; ?>><?php echo $c; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
            <span style="width:28px;height:28px;border-radius:8px;background:#eef4ff;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/funnel-fill.svg" alt="" width="14" height="14">
            </span>
            <select id="kbff-sort-select" style="padding:7px 10px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:12.5px;background:#fff;color:var(--kbf-text);min-width:130px;">
              <option value="newest" <?php echo ($sort==='newest'||!$sort)?'selected':''; ?>>Newest</option>
              <option value="most_funded" <?php echo $sort==='most_funded'?'selected':''; ?>>Most Funded</option>
              <option value="ending_soon" <?php echo $sort==='ending_soon'?'selected':''; ?>>Ending Soon</option>
            </select>
          </div>

          <input type="text" name="ff_q" value="<?php echo esc_attr($q); ?>" placeholder="Search by title or description..." style="flex:2;min-width:0;padding:9px 12px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:13px;background:#fff;color:var(--kbf-text);">
          <input type="text" name="ff_loc" id="kbff-loc-input" value="<?php echo esc_attr($loc); ?>" placeholder="Location (city, province)..." style="flex:1;min-width:0;padding:9px 12px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:13px;background:#fff;color:var(--kbf-text);">

          <button type="button" id="kbff-near-me-btn" onclick="kbffNearMe()" class="kbf-btn kbf-btn-secondary" style="white-space:nowrap;">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/geo-alt.svg" alt="" width="14" height="14" style="margin-right:6px;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
            Near Me
          </button>
          <button type="submit" class="kbf-btn kbf-btn-primary">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/search.svg" alt="" width="14" height="14" style="filter:invert(100%);margin-right:6px;">
            Search
          </button>
          <?php if($q||$cat||$loc): ?><a href="?kbf_tab=find_funds" class="kbf-btn kbf-btn-secondary" style="padding:9px 14px;">Clear</a><?php endif; ?>
        </form>
      </div>
      <?php if($loc): ?>
      <div style="margin-top:10px;font-size:12.5px;color:var(--kbf-text-sm);display:flex;align-items:center;gap:6px;">
        <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/geo-alt.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
        Showing funds near: <strong><?php echo esc_html($loc); ?></strong>
      </div>
      <?php endif; ?>
      <div class="kbf-cta-note" id="kbf-explore-tip"><strong>Tip:</strong> Funds with regular updates raise up to 3x more.</div>
    </div>

    <!-- Fund grid -->
    <?php if(empty($funds)): ?>
    <div class="kbf-empty" style="padding:60px 20px;">
      <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/search.svg" alt="" width="44" height="44" style="margin:0 auto 14px;display:block;opacity:.35;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
      <p style="font-size:15px;font-weight:600;color:var(--kbf-navy);margin-bottom:4px;">No funds found</p>
      <p style="color:var(--kbf-slate);font-size:13px;">Try adjusting your search or category filter.</p>
      <?php if($q||$cat): ?><a href="?kbf_tab=find_funds" class="kbf-btn kbf-btn-primary" style="margin-top:14px;">Clear Filters</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="kbf-card-list" data-kbf-card-pager="explore">
    <div class="kbf-explore-grid">
      <?php foreach($funds as $f):
        $pct   = $f->goal_amount > 0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $days  = $f->deadline ? max(0,ceil((strtotime($f->deadline)-time())/86400)) : null;
        $photos = $f->photos ? json_decode($f->photos,true) : [];
        $cover  = !empty($photos[0]) ? $photos[0] : null;
        $detail_url = esc_url(add_query_arg('fund_id',$f->id,$fund_details_url));
        $is_own = ($f->business_id == $current_user_id);
        $supporters = isset($f->sponsors_count) ? (int)$f->sponsors_count : (isset($f->sponsor_count) ? (int)$f->sponsor_count : 0);
        $supporters_label = $supporters > 0 ? $supporters.' people' : 'New';
      ?>
      <div class="kbf-explore-card">

        <!-- Photo / cover -->
        <a href="<?php echo $detail_url; ?>" class="kbf-explore-media">
          <div class="kbf-explore-media-frame">
            <?php if($cover): ?>
              <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($f->title); ?>">
            <?php else: ?>
              <div class="kbf-explore-fallback">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="40" height="40" style="opacity:.35;filter:invert(36%) sepia(16%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
              </div>
            <?php endif; ?>
          </div>
          <!-- Overlays -->
          <div class="kbf-explore-chip"><?php echo esc_html($f->category); ?></div>
        </a>

        <!-- Card body -->
        <div class="kbf-explore-body">
          <a href="<?php echo $detail_url; ?>" style="text-decoration:none;">
            <div class="kbf-explore-title-row">
              <h4 class="kbf-explore-title kbf-explore-title-text"><?php echo esc_html($f->title); ?></h4>
              <?php if($days!==null): ?>
          
              <?php endif; ?>
            </div>
          </a>

          <!-- Location + Organizer -->
          <div class="kbf-explore-meta">
            <span class="kbf-explore-loc"><?php echo esc_html($f->location); ?></span>
          </div>

          <!-- Progress -->
          <div class="kbf-explore-progress"><span style="width:<?php echo $pct; ?>%"></span></div>
          <div class="kbf-explore-footer">
            <span><span class="kbf-explore-amount">₱<?php echo number_format($f->raised_amount,0); ?></span> · <?php echo round($pct); ?>%</span>
            <span class="kbf-explore-days-inline"><?php echo $days; ?> days left</span>
          </div>

          <!-- Action buttons -->
          <?php if($is_own): ?>
          <div class="kbf-explore-actions is-own">
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-primary" style="font-size:12.5px;text-align:center;">View Details</a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveFund('<?php echo esc_js($f->id); ?>')" title="Save" data-tooltip="Save">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/bookmark-fill.svg" alt="" width="13" height="13" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
            </button>
            <div class="kbf-explore-more-wrap">
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfToggleExploreMore(event,'<?php echo esc_js($f->id); ?>')" title="More" data-tooltip="More">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/three-dots-vertical.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
              </button>
              <div class="kbf-explore-more-menu" id="kbf-explore-more-<?php echo esc_attr($f->id); ?>">
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="event.stopPropagation();kbffShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/share-fill.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
                  Share
                </button>
                <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="event.stopPropagation();kbffOpenReport(<?php echo $f->id; ?>)">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/flag-fill.svg" alt="" width="12" height="12" style="filter:invert(34%) sepia(82%) saturate(5110%) hue-rotate(344deg) brightness(100%) contrast(97%);">
                  Report
                </button>
              </div>
            </div>
          </div>
          <?php else: ?>
          <div class="kbf-explore-actions is-public">
            <button class="kbf-btn kbf-btn-primary" style="font-size:12.5px;" onclick="kbffOpenSponsor(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>',<?php echo $f->goal_amount; ?>,<?php echo $f->raised_amount; ?>,'<?php echo esc_js(isset($cover) ? $cover : ''); ?>')">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="12" height="12" style="filter:invert(100%);">
              Sponsor
            </button>
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-secondary kbf-btn-sm" title="View full details">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/eye-fill.svg" alt="" width="13" height="13" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
            </a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfSaveFund('<?php echo esc_js($f->id); ?>')" title="Save" data-tooltip="Save">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/bookmark-fill.svg" alt="" width="13" height="13" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
            </button>
            <div class="kbf-explore-more-wrap">
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfToggleExploreMore(event,'<?php echo esc_js($f->id); ?>')" title="More" data-tooltip="More">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/three-dots-vertical.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
              </button>
              <div class="kbf-explore-more-menu" id="kbf-explore-more-<?php echo esc_attr($f->id); ?>">
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="event.stopPropagation();kbffShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/share-fill.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
                  Share
                </button>
                <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="event.stopPropagation();kbffOpenReport(<?php echo $f->id; ?>)">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/flag-fill.svg" alt="" width="12" height="12" style="filter:invert(34%) sepia(82%) saturate(5110%) hue-rotate(344deg) brightness(100%) contrast(97%);">
                  Report
                </button>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    </div>
    <?php endif; ?>
    
    <!-- ================== JS ================== -->
    <script>
      if(typeof ajaxurl==='undefined') 
        var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';
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

    (function(){
        var wrap = document.querySelector('.kbf-card-list[data-kbf-card-pager="explore"]');
        if(!wrap || wrap.dataset.kbfPager === 'on') return;
        var cards = Array.prototype.slice.call(wrap.querySelectorAll('.kbf-explore-card'));
        if(cards.length === 0) return;
        wrap.dataset.kbfPager = 'on';

        var pager = document.createElement('div');
        pager.className = 'kbf-explore-pager';
        pager.innerHTML = '' +
          '<button class="kbf-table-pager-btn kbf-table-prev" type="button">Previous</button>' +
          '<div class="kbf-pager-pages"></div>' +
          '<button class="kbf-table-pager-btn kbf-table-next" type="button">Next</button>';
        wrap.insertAdjacentElement('afterend', pager);

        var prevBtn = pager.querySelector('.kbf-table-prev');
        var nextBtn = pager.querySelector('.kbf-table-next');
        var pagesWrap = pager.querySelector('.kbf-pager-pages');
        var page = 1;
        var perPage = 9;

        function buildPageModel(pages, current){
          var items = [];
          if(pages <= 7){
            for(var i=1;i<=pages;i++) items.push(i);
            return items;
          }
          items.push(1);
          if(current > 3) items.push('gap');
          var start = Math.max(2, current - 1);
          var end = Math.min(pages - 1, current + 1);
          for(var i=start;i<=end;i++) items.push(i);
          if(current < pages - 2) items.push('gap');
          items.push(pages);
          return items;
        }
        function renderPages(pages, current){
          pagesWrap.innerHTML = '';
          buildPageModel(pages, current).forEach(function(p){
            if(p === 'gap'){
              var span = document.createElement('span');
              span.className = 'kbf-page-gap';
              span.textContent = '…';
              pagesWrap.appendChild(span);
              return;
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'kbf-page-btn' + (p === current ? ' is-active' : '');
            btn.textContent = String(p);
            btn.addEventListener('click', function(){
              page = p;
              render();
            });
            pagesWrap.appendChild(btn);
          });
        }

        function render(){
          var total = cards.length;
          var pages = Math.max(1, Math.ceil(total / perPage));
          if(page > pages) page = pages;
          var start = (page - 1) * perPage;
          var end = start + perPage;
          cards.forEach(function(card, i){
            card.style.display = (i >= start && i < end) ? '' : 'none';
          });
          renderPages(pages, page);
          prevBtn.disabled = page <= 1;
          nextBtn.disabled = page >= pages;
          pager.style.display = total > 0 ? 'flex' : 'none';
        }
        function setLoading(btn){
          btn.classList.add('is-loading');
          btn.disabled = true;
          setTimeout(function(){ btn.classList.remove('is-loading'); render(); }, 250);
        }
        prevBtn.addEventListener('click', function(){
          if(page > 1){ page--; setLoading(prevBtn); }
        });
        nextBtn.addEventListener('click', function(){
          page++; setLoading(nextBtn);
        });
        render();
    })();

    if (typeof window.kbfSaveFund === 'undefined') {
        window.kbfSaveFund = function(id){
            alert('Saved! (Feature coming soon)');
        };
    }
    window.kbfToggleExploreMore=function(e,id){
        if(e) e.stopPropagation();
        var menu = document.getElementById('kbf-explore-more-' + id);
        if(!menu) return;
        var card = menu.closest('.kbf-explore-card');
        menu.onclick = function(ev){ ev.stopPropagation(); };
        document.querySelectorAll('.kbf-explore-more-menu.open').forEach(function(m){
            if(m !== menu) m.classList.remove('open');
        });
        document.querySelectorAll('.kbf-explore-card.is-menu-open').forEach(function(c){
            if(!card || c !== card) c.classList.remove('is-menu-open');
        });
        menu.classList.toggle('open');
        if(card){ card.classList.toggle('is-menu-open', menu.classList.contains('open')); }
    };
    document.addEventListener('click', function(){
        document.querySelectorAll('.kbf-explore-more-menu.open').forEach(function(m){
            m.classList.remove('open');
        });
        document.querySelectorAll('.kbf-explore-card.is-menu-open').forEach(function(c){
            c.classList.remove('is-menu-open');
        });
    });

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
    (function(){
        var tipEl = document.getElementById('kbf-explore-tip');
        if(!tipEl) return;
        var tips = [
            'Tip: Funds with regular updates raise up to 3x more.',
            'Tip: Add 2–3 photos to build trust quickly.',
            'Tip: Clear titles get more clicks in search.',
            'Tip: Short, specific titles perform better in search.',
            'Tip: Goals with clear purposes get more sponsors.',
            'Tip: Share to your closest circles first for momentum.',
            'Tip: Use real photos to improve credibility.',
            'Tip: Thank early sponsors to build social proof.',
            'Tip: Post updates after big milestones.',
            'Tip: Found a malicious fund post? Report it to us.'
        ];
        function pickRandom(){
            return tips[Math.floor(Math.random()*tips.length)];
        }
        function setTip(){
            var next = pickRandom();
            if(next.indexOf('Tip:') === 0){
                tipEl.innerHTML = '<strong>Tip:</strong> ' + next.replace(/^Tip:\s*/,'');
            } else {
                tipEl.textContent = next;
            }
        }
        setTip();
        setInterval(setTip, 180000);
    })();
    </script>
    <?php
    return ob_get_clean();
}




