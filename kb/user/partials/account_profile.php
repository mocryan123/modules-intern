<?php
/* Organizer profile shortcode */
function bntm_shortcode_kbf_organizer_profile() {
    kbf_global_assets();
    global $wpdb;
    $biz_id = 0;
    $org_token = !empty($_GET['organizer']) ? sanitize_text_field($_GET['organizer']) : '';
    if ($org_token) {
        $pt = $wpdb->prefix.'kbf_organizer_profiles';
        $biz_id = (int)$wpdb->get_var($wpdb->prepare("SELECT business_id FROM {$pt} WHERE organizer_token=%s", $org_token));
    }
    if(!$biz_id && isset($_GET['organizer_id'])) {
        $biz_id = intval($_GET['organizer_id']);
    }
    if(!$biz_id) return bntm_universal_container('Organizer Profile','<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Organizer not found.</div></div>', ['show_topbar'=>false,'show_header'=>false]);
    $pt=$wpdb->prefix.'kbf_organizer_profiles';
    $ft=$wpdb->prefix.'kbf_funds';
    $rt=$wpdb->prefix.'kbf_ratings';
    $st=$wpdb->prefix.'kbf_sponsorships';
    $profile=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$biz_id));
    $user=get_userdata($biz_id);
    if(!$user) return bntm_universal_container('Organizer Profile','<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Organizer not found.</div></div>', ['show_topbar'=>false,'show_header'=>false]);
    $funds=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$ft} WHERE business_id=%d AND status IN ('active','completed') ORDER BY created_at DESC LIMIT 50",$biz_id));
    $reviews=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$rt} WHERE organizer_id=%d ORDER BY created_at DESC LIMIT 50",$biz_id));
    $fund_details_url = kbf_get_page_url('fund_details');
    $browse_url = kbf_get_page_url('browse');
    $back_url = $browse_url;
    $back_label = 'Back to Browse';
    if (!empty($_GET['fund'])) {
        $back_url = add_query_arg('fund', sanitize_text_field($_GET['fund']), $fund_details_url);
        $back_label = 'Back to Fund Details';
    } elseif (!empty($_GET['fund_id'])) {
        $back_url = add_query_arg('fund_id', intval($_GET['fund_id']), $fund_details_url);
        $back_label = 'Back to Fund Details';
    } elseif (!empty($_GET['kbf_share'])) {
        $back_url = add_query_arg('kbf_share', sanitize_text_field($_GET['kbf_share']), $fund_details_url);
        $back_label = 'Back to Fund Details';
    }
    $socials=$profile&&$profile->social_links?json_decode($profile->social_links,true):[];
    $nonce_rating = wp_create_nonce('kbf_rating');
    $current_user = wp_get_current_user();
    $current_email = $current_user && !empty($current_user->user_email) ? $current_user->user_email : '';
    $has_reviewed = false;
    if ($current_email) {
        $has_reviewed = (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$rt} WHERE organizer_id=%d AND sponsor_email=%s LIMIT 1",
            $biz_id, $current_email
        ));
    }
    $bio_text = '';
    if ($profile && trim((string)$profile->bio) !== '') {
        $bio_text = (string)$profile->bio;
    } else {
        $bio_text = (string)get_user_meta($biz_id, 'description', true);
    }
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-wrap">
      <style>
        .kbf-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--kbf-slate);margin-bottom:20px;}
        .kbf-breadcrumb a{color:var(--kbf-blue);text-decoration:none;font-weight:600;}
        .kbf-breadcrumb a:hover{text-decoration:underline;}
        .kbf-card-title{
          font-size:15px;
          font-weight:600;
          overflow:hidden;
          white-space:nowrap;
          text-overflow:ellipsis;
          max-width:100%;
        }
        .kbf-org-avatar{position:relative;width:70px;height:70px;flex-shrink:0;}
        .kbf-org-avatar > img,
        .kbf-org-avatar > .kbf-org-avatar-fallback{
          width:70px;height:70px;border-radius:50%;object-fit:cover;display:block;
          border:3px solid rgba(255,255,255,.3);
        }
        .kbf-org-verified{
          position:absolute;right:-2px;bottom:-2px;width:20px;height:20px;border-radius:50%;
          background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 1px #fff;
        }
        .kbf-org-verified::before{
          content:'';width:14px;height:14px;background:#1d4ed8;display:block;
          -webkit-mask:url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/patch-check-fill.svg') no-repeat center/contain;
          mask:url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/patch-check-fill.svg') no-repeat center/contain;
        }
      </style>
      <!-- Breadcrumb -->
      <div class="kbf-breadcrumb">
        <a href="<?php echo esc_url($back_url); ?>" style="display:inline-flex;align-items:center;gap:6px;">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/arrow-left.svg" alt="" width="14" height="14" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
          <?php echo esc_html($back_label); ?>
        </a>
      </div>
    <div class="kbf-page-header">
      <div style="display:flex;align-items:center;gap:16px;">
        <div class="kbf-org-avatar">
          <?php if($profile&&$profile->avatar_url): ?>
            <img src="<?php echo esc_url($profile->avatar_url); ?>" alt="">
          <?php else: ?>
            <div class="kbf-org-avatar-fallback" style="background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
              <svg width="32" height="32" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
          <?php endif; ?>
          <?php if($profile && (int)$profile->is_verified === 1): ?>
            <span class="kbf-org-verified"></span>
          <?php endif; ?>
        </div>
        <div>
          <h2 style="margin:0 0 6px;"><?php echo esc_html($user->display_name); ?></h2>
          <?php if(trim($bio_text) !== ''): ?>
            <div style="color:#4f5a6b;font-size:13px;line-height:1.6;max-width:520px;">
              <?php echo nl2br(esc_html($bio_text)); ?>
            </div>
          <?php endif; ?>
          <?php if($profile&&$profile->rating_count>0): ?>
          <div style="display:flex;align-items:center;gap:6px;margin-top:6px;">
            <?php for($i=1;$i<=5;$i++): ?><svg width="14" height="14" viewBox="0 0 24 24" fill="<?php echo $i<=round($profile->rating)?'#fbbf24':'#d1d5db'; ?>"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg><?php endfor; ?>
            <span style="color:var(--kbf-slate);font-size:13px;"><?php echo number_format($profile->rating,1); ?>/5 (<?php echo (int)$profile->rating_count; ?>)</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;">
      <div>
        <?php if(false): ?><div></div><?php endif; ?>

        <div class="kbf-section-header" style="margin-bottom:14px;align-items:center;">
          <h3 class="kbf-section-title">Campaigns</h3>
          <div class="kbf-inline-filters" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="width:28px;height:28px;border-radius:8px;background:#eef4ff;display:inline-flex;align-items:center;justify-content:center;">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/tag-fill.svg" alt="" width="14" height="14">
              </span>
              <select id="kbf-filter-status" style="padding:7px 10px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:12.5px;background:#fff;color:var(--kbf-text);min-width:160px;">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="suspended">Suspended</option>
                <option value="cancelled">Cancelled</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="width:28px;height:28px;border-radius:8px;background:#eef4ff;display:inline-flex;align-items:center;justify-content:center;">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/funnel-fill.svg" alt="" width="14" height="14">
              </span>
              <select id="kbf-filter-escrow" style="padding:7px 10px;border-radius:10px;border:1.5px solid var(--kbf-border);font-size:12.5px;background:#fff;color:var(--kbf-text);min-width:160px;">
                <option value="all">All Escrow</option>
                <option value="holding">Holding</option>
                <option value="released">Released</option>
              </select>
            </div>
          </div>
        </div>
        <?php if(empty($funds)): ?>
          <div class="kbf-empty"><p>No active campaigns.</p></div>
        <?php else: ?>
        <div class="kbf-card-list" data-kbf-card-pager="organizer-campaigns">
        <?php foreach($funds as $f):
          $pct=$f->goal_amount>0?min(100,($f->raised_amount/$f->goal_amount)*100):0;
          $sc  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed'",$f->id));
          $days_left = $f->deadline ? max(0, ceil((strtotime($f->deadline)-time())/86400)) : null;
        ?>
          <div class="kbf-card" data-status="<?php echo esc_attr($f->status); ?>" data-escrow="<?php echo esc_attr($f->escrow_status ?? ''); ?>">
            <div class="kbf-card-header">
              <div style="flex:1;">
                <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:4px;">
                  <strong class="kbf-card-title"><?php echo esc_html($f->title); ?></strong>
                  <span class="kbf-badge kbf-badge-<?php echo $f->status; ?>" style="width:max-content;"><?php echo ucfirst($f->status); ?></span>
                </div>
                <div class="kbf-meta">
                  <div class="kbf-meta-row">
                    <span class="kbf-meta-item">
                      <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/tag-fill.svg" alt="">
                      <?php echo esc_html($f->category); ?>
                    </span>
                    <span class="kbf-meta-divider"></span>
                    <span class="kbf-meta-item">
                      <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/geo-alt-fill.svg" alt="">
                      <?php echo esc_html($f->location); ?>
                    </span>
                  </div>
                  <div class="kbf-meta-row">
                    <?php if($days_left!==null): ?>
                      <span class="kbf-meta-item kbf-meta-strong" style="color:<?php echo $days_left<7?'#dc2626':'#64748b';?>;">
                        <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/clock-fill.svg" alt="">
                        <?php echo $days_left; ?>d left
                      </span>
                      <span class="kbf-meta-divider"></span>
                    <?php endif; ?>
                    <span class="kbf-meta-item">
                      <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/people-fill.svg" alt="">
                      <?php echo $sc; ?> sponsors
                    </span>
                    <span class="kbf-meta-divider"></span>
                    <span class="kbf-meta-item">
                      <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/shield-fill-check.svg" alt="">
                      Escrow
                      <span class="kbf-badge kbf-badge-<?php echo $f->escrow_status; ?>" style="font-size:10px;"><?php echo ucfirst($f->escrow_status); ?></span>
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div class="kbf-progress-wrap" style="margin-bottom:12px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
            <div class="kbf-fund-amounts"><span><strong>₱<?php echo number_format($f->raised_amount,2); ?></strong>raised</span><span><strong>₱<?php echo number_format($f->goal_amount,2); ?></strong>goal</span><span><strong><?php echo round($pct); ?>%</strong>funded</span></div>
            <div class="kbf-card-actions">
              <?php $fund_token = function_exists('kbf_get_or_create_fund_token') ? kbf_get_or_create_fund_token($f->id) : ''; ?>
              <a class="kbf-btn kbf-btn-primary kbf-btn-sm" href="<?php echo esc_url(add_query_arg('fund', $fund_token ?: $f->id, $fund_details_url)); ?>">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/box-arrow-up-right.svg" alt="" width="12" height="12" style="filter:invert(100%);">
                View Details
              </a>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
        <div class="kbf-table-pager" data-kbf-card-pager-ui="organizer-campaigns"></div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <div>
        <?php if($profile): ?>
        <div class="kbf-card" style="margin-bottom:16px;">
          <h4 style="font-size:13px;font-weight:700;color:var(--kbf-navy);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;">Stats</h4>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Total Raised</span><strong style="color:var(--kbf-green);">₱<?php echo number_format($profile->total_raised,0); ?></strong></div>
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Total Sponsors</span><strong><?php echo number_format($profile->total_sponsors); ?></strong></div>
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Active Funds</span><strong><?php $active_count=0; foreach($funds as $f){ if($f->status==='active') $active_count++; } echo $active_count; ?></strong></div>             
          </div>
        </div>
        <?php endif; ?>

        <div class="kbf-card" style="margin-bottom:16px;">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;flex-wrap:wrap;">
            <h4 style="font-size:13px;font-weight:700;color:var(--kbf-navy);margin:0;text-transform:uppercase;letter-spacing:.5px;">Ratings</h4>
            <div style="display:flex;align-items:center;gap:6px;">
              <span style="display:inline-flex;gap:2px;line-height:1;font-size:12px;">
                <?php for($i=1;$i<=5;$i++): ?>
                  <span class="<?php echo $i<=round((float)$profile->rating)?'kbf-star':'kbf-star-empty'; ?>">★</span>
                <?php endfor; ?>
              </span>
              <strong style="font-size:12.5px;"><?php echo number_format((float)$profile->rating,1); ?>/5 (<?php echo (int)$profile->rating_count; ?>)</strong>
            </div>
          </div>
          <?php if(is_user_logged_in() && !$has_reviewed): ?>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" type="button" style="margin-bottom:10px;" onclick="document.getElementById('kbf-modal-rating').style.display='flex'">Add Review</button>
          <?php elseif(is_user_logged_in() && $has_reviewed): ?>
            <span class="kbf-meta" style="font-size:11.5px;display:block;margin-bottom:10px;">Already reviewed</span>
          <?php else: ?>
            <span class="kbf-meta" style="font-size:11.5px;display:block;margin-bottom:10px;">Log in to review</span>
          <?php endif; ?>
          <?php if(empty($reviews)): ?>
            <div class="kbf-empty" style="padding:18px 10px;"><p>No reviews yet.</p></div>
          <?php else: ?>
          <div class="kbf-card-list" data-kbf-card-pager="organizer-reviews">
          <?php foreach($reviews as $r): ?>
            <div class="kbf-card" style="padding:14px 16px;">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
                <div style="display:flex;align-items:center;gap:3px;">
                  <?php for($i=1;$i<=5;$i++): ?>
                    <img src="<?php echo $i <= (int)$r->rating ? 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg' : 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg'; ?>" width="12" height="12" alt="" style="filter:<?php echo $i <= (int)$r->rating ? 'invert(70%) sepia(85%) saturate(531%) hue-rotate(3deg) brightness(98%) contrast(92%)' : 'invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%)'; ?>;">
                  <?php endfor; ?>
                </div>
                <span class="kbf-meta"><?php echo date('M d, Y',strtotime($r->created_at)); ?></span>
              </div>
              <?php if($r->review): ?><p style="margin:0;font-size:13.5px;color:var(--kbf-text-sm);font-style:italic;">"<?php echo esc_html($r->review); ?>"</p><?php endif; ?>
              <div class="kbf-meta" style="margin-top:6px;"><?php echo esc_html($r->sponsor_email?:'Anonymous'); ?></div>
            </div>
          <?php endforeach; ?>
          </div>
          <div class="kbf-table-pager" data-kbf-card-pager-ui="organizer-reviews"></div>
          <?php endif; ?>
        </div>

        <?php if(!empty(array_filter($socials))): ?>
        <div class="kbf-card">
          <h4 style="font-size:13px;font-weight:700;color:var(--kbf-navy);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;">Connect</h4>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach(['facebook'=>'Facebook','instagram'=>'Instagram','twitter'=>'Twitter/X'] as $k=>$label): if(!empty($socials[$k])): ?>
              <a href="<?php echo esc_url($socials[$k]); ?>" target="_blank" rel="noopener" class="kbf-btn kbf-btn-secondary kbf-btn-sm"><?php echo $label; ?></a>
            <?php endif; endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    </div>
    <!-- Rating Modal -->
    <div id="kbf-modal-rating" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Rate This Organizer</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-rating').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-rating-form" onsubmit="return false;">
            <input type="hidden" name="organizer_id" value="<?php echo (int)$biz_id; ?>">
            <input type="hidden" name="fund_id" value="0">
            <div class="kbf-form-group"><label>Rating</label>
              <div id="kbf-star-picker" style="display:flex;gap:8px;margin-top:6px;">
                <?php for($i=1;$i<=5;$i++): ?>
                  <img class="kbf-star-btn" data-val="<?php echo $i; ?>" data-filled="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg" data-empty="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg" src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg" alt="" width="28" height="28" style="cursor:pointer;filter:invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%);" onclick="kbfSetRating(<?php echo $i; ?>)">
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="kbf-rating-val" value="5">
            </div>
            <div class="kbf-form-group"><label>Your Email *</label><input type="email" name="sponsor_email" required placeholder="your@email.com" value="<?php echo esc_attr($current_email); ?>"></div>
            <div class="kbf-form-group"><label>Review (optional)</label><textarea name="review" rows="3" placeholder="Share your experience..."></textarea></div>
            <div id="kbf-rate-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-rating').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitRating('<?php echo $nonce_rating; ?>')">Submit Review</button>
        </div>
      </div>
    </div>
    <script>
    (function(){
      window.kbfSetRating = function(v){
        var stars = document.querySelectorAll('#kbf-star-picker .kbf-star-btn');
        stars.forEach(function(star){
          var val = parseInt(star.getAttribute('data-val'),10);
          star.src = val <= v ? star.getAttribute('data-filled') : star.getAttribute('data-empty');
          star.style.filter = val <= v
            ? 'invert(70%) sepia(85%) saturate(531%) hue-rotate(3deg) brightness(98%) contrast(92%)'
            : 'invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%)';
        });
        var inp = document.getElementById('kbf-rating-val');
        if(inp) inp.value = v;
      };
      window.kbfSubmitRating = function(nonce){
        var form = document.getElementById('kbf-rating-form');
        var btn = document.querySelector('#kbf-modal-rating .kbf-modal-footer .kbf-btn-primary');
        if(!form || !btn) return;
        var fd = new FormData(form);
        fd.append('action','kbf_submit_rating');
        fd.append('nonce',nonce);
        btn.disabled = true;
        var old = btn.textContent;
        btn.textContent = 'Submitting...';
        window.kbfFetchJson(ajaxurl, fd, function(j){
          var msg = document.getElementById('kbf-rate-msg');
          if(msg) msg.innerHTML = '<div class="kbf-alert ' + (j.success?'kbf-alert-success':'kbf-alert-error') + ' kbf-alert-compact">' + (j.data && j.data.message ? j.data.message : (j.success?'Submitted':'Failed')) + '</div>';
          if(j.success) setTimeout(function(){ window.location.reload(); }, 800);
          btn.disabled = false;
          btn.textContent = old;
        }, function(err){
          var msg = document.getElementById('kbf-rate-msg');
          if(msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error kbf-alert-compact">' + err + '</div>';
          btn.disabled = false;
          btn.textContent = old;
        });
      };
      function initCardPager(scope){
        var wrap = document.querySelector('.kbf-card-list[data-kbf-card-pager="'+scope+'"]');
        var pager = document.querySelector('.kbf-table-pager[data-kbf-card-pager-ui="'+scope+'"]');
        if(!wrap || !pager) return;
        var cards = Array.prototype.slice.call(wrap.querySelectorAll('.kbf-card'));
        if(cards.length === 0) return;
        if(pager.dataset.ready === '1') return;
        pager.dataset.ready = '1';
        pager.innerHTML = '' +
          '<div class="kbf-table-pager-left">Show ' +
          '<select class="kbf-table-rows">' +
            '<option value="5" selected>5</option>' +
            '<option value="10">10</option>' +
            '<option value="20">20</option>' +
          '</select> rows' +
          '</div>' +
          '<div class="kbf-table-pager-right">' +
            '<button class="kbf-table-pager-btn kbf-table-prev" type="button">Prev</button>' +
            '<span class="kbf-table-pager-page">1 / 1</span>' +
            '<button class="kbf-table-pager-btn kbf-table-next" type="button">Next</button>' +
          '</div>';
        var select = pager.querySelector('.kbf-table-rows');
        var prevBtn = pager.querySelector('.kbf-table-prev');
        var nextBtn = pager.querySelector('.kbf-table-next');
        var pageLabel = pager.querySelector('.kbf-table-pager-page');
        var page = 1;
        var perPage = 5;
        function getFilteredCards(){
          if(scope !== 'organizer-campaigns') return cards;
          var statusSel = document.getElementById('kbf-filter-status');
          var escrowSel = document.getElementById('kbf-filter-escrow');
          var statusVal = statusSel ? String(statusSel.value || '').trim() : '';
          var escrowVal = escrowSel ? String(escrowSel.value || '').trim() : '';
          return cards.filter(function(card){
            var s = (card.getAttribute('data-status') || '').toLowerCase();
            var e = (card.getAttribute('data-escrow') || '').toLowerCase();
            var okStatus = !statusVal || statusVal === 'all' || s === statusVal;
            var okEscrow = !escrowVal || escrowVal === 'all' || e === escrowVal;
            return okStatus && okEscrow;
          });
        }
        function render(){
          var filtered = getFilteredCards();
          var total = filtered.length;
          var pages = Math.max(1, Math.ceil(total / perPage));
          if(page > pages) page = pages;
          var start = (page - 1) * perPage;
          var end = start + perPage;
          cards.forEach(function(card){
            card.style.display = 'none';
          });
          filtered.forEach(function(card, i){
            card.style.display = (i >= start && i < end) ? '' : 'none';
          });
          pageLabel.textContent = page + ' / ' + pages;
          prevBtn.disabled = page <= 1;
          nextBtn.disabled = page >= pages;
          pager.style.display = total > 0 ? 'flex' : 'none';
        }
        select.addEventListener('change', function(){
          perPage = parseInt(this.value, 10) || 5;
          page = 1;
          render();
        });
        prevBtn.addEventListener('click', function(){ if(page > 1){ page--; render(); } });
        nextBtn.addEventListener('click', function(){ if(page < Math.ceil(cards.length / perPage)){ page++; render(); } });
        if(scope === 'organizer-campaigns') {
          var statusSel = document.getElementById('kbf-filter-status');
          var escrowSel = document.getElementById('kbf-filter-escrow');
          if(statusSel) statusSel.addEventListener('change', function(){ page = 1; render(); });
          if(escrowSel) escrowSel.addEventListener('change', function(){ page = 1; render(); });
        }
        render();
      }
      document.addEventListener('DOMContentLoaded', function(){
        initCardPager('organizer-campaigns');
        initCardPager('organizer-reviews');
      });
    })();
    </script>
    <?php
    $c=ob_get_clean();
    if (!empty($_GET['kbf_tab']) && $_GET['kbf_tab'] === 'organizer_profile') {
        return $c;
    }
    return bntm_universal_container('Organizer Profile -- KonekBayan',$c, ['show_topbar'=>false,'show_header'=>false]);
}
