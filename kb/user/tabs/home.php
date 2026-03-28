<?php
/*
 * KBF user dashboard tab: Overview.
 */

function kbf_dashboard_overview_tab($business_id) {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $st = $wpdb->prefix.'kbf_sponsorships';
    $wt = $wpdb->prefix.'kbf_withdrawals';
    $fund_details_url = kbf_get_page_url('fund_details');

    $total_funds    = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d",$business_id));
    $active_funds   = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status='active'",$business_id));
    $pending_funds  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status='pending'",$business_id));
    $total_raised   = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(raised_amount),0) FROM {$ft} WHERE business_id=%d",$business_id));
    $total_sponsors = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$business_id));
    $funds = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$ft} WHERE business_id=%d ORDER BY created_at DESC",$business_id));

    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <style>
        .kbf-card-list[data-kbf-card-pager="home"] + .kbf-table-pager{
          margin-bottom:0;
          padding-bottom:0;
        }
      </style>
      <style>
        .kbf-sponsor-details{border-top:1px solid var(--kbf-border);margin-top:14px;}
        .kbf-sponsor-details summary{
          cursor:pointer;
          font-size:13px;
          font-weight:600;
          color:var(--kbf-navy);
          list-style:none;
          padding:12px 0;
          display:flex;
          align-items:center;
          gap:8px;
        }
        .kbf-sponsor-details summary::-webkit-details-marker{display:none;}
        .kbf-sponsor-details-content{
          display:none;
        }
        .kbf-sponsor-details[open] .kbf-sponsor-details-content{
          display:block;
        }
      </style>
      <div class="kbf-section-header">
        <h3 class="kbf-section-title">Dashboard Overview</h3>
        <button class="kbf-btn kbf-btn-primary" onclick="kbfOpenModal('kbf-modal-create')">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Create Fund
        </button>
      </div>
      <?php if($pending_funds > 0): ?>
      <div class="kbf-alert kbf-alert-warning kbf-alert-noicon" style="margin-bottom:20px;display:flex;align-items:center;gap:12px;">
        <span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.964 0L.165 13.233c-.457.778.091 1.767.982 1.767h13.706c.89 0 1.438-.99.982-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1-2.002 0 1 1 0 0 1 2.002 0z"/>
          </svg>
        </span>
        <div>
          <strong><?php echo $pending_funds; ?> fund<?php echo $pending_funds>1?'s':''; ?> under review.</strong>
          Not visible to sponsors yet. You’ll be notified after approval.
          <span style="margin-left:6px;font-weight:700;">View all funds below.</span>
        </div>
      </div>
      <?php endif; ?>
      <div class="kbf-stats">
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/stack.svg" alt="" width="20" height="20" class="kbf-stat-icon-img">
          </div>
          <div><div class="kbf-stat-label">Total Funds</div><div class="kbf-stat-value"><?php echo $total_funds; ?></div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/piggy-bank-fill.svg" alt="" width="20" height="20" class="kbf-stat-icon-img">
          </div>
          <div><div class="kbf-stat-label">Total Raised</div><div class="kbf-stat-value">₱<?php echo number_format($total_raised,0); ?></div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg" alt="" width="20" height="20" class="kbf-stat-icon-img">
          </div>
          <div><div class="kbf-stat-label">Total Sponsors</div><div class="kbf-stat-value"><?php echo $total_sponsors; ?></div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/eye-fill.svg" alt="" width="20" height="20" class="kbf-stat-icon-img">
          </div>
          <div><div class="kbf-stat-label">Active Now</div><div class="kbf-stat-value"><?php echo $active_funds; ?></div></div>
        </div>
        <?php if($pending_funds > 0): ?>
        <div class="kbf-stat" style="border-color:#fcd34d;background:#fffbeb;">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/clock-fill.svg" alt="" width="20" height="20" class="kbf-stat-icon-img">
          </div>
          <div><div class="kbf-stat-label">Pending Review</div><div class="kbf-stat-value" style="color:#92400e;"><?php echo $pending_funds; ?></div></div>
        </div>
        <?php endif; ?>
      </div>

      <div class="kbf-section-header" style="margin-bottom:14px;align-items:center;">
        <h3 class="kbf-section-title" style="margin:0;">All My Funds</h3>
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
        <div class="kbf-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><p>No funds created yet.</p></div>
      <?php else: ?>
      <div class="kbf-card-list" data-kbf-card-pager="home">
      <?php foreach($funds as $f):
        $pct = $f->goal_amount > 0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $sc  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed'",$f->id));
        $days_left = $f->deadline ? max(0, ceil((strtotime($f->deadline)-time())/86400)) : null;
        $last_wd = $wpdb->get_row($wpdb->prepare("SELECT status, admin_notes FROM {$wt} WHERE fund_id=%d ORDER BY requested_at DESC, id DESC LIMIT 1",$f->id));
        ?>
        <div class="kbf-card" data-status="<?php echo esc_attr($f->status); ?>" data-escrow="<?php echo esc_attr($f->escrow_status); ?>">
          <?php if($last_wd && $last_wd->status === 'rejected'): ?>
          <div class="kbf-alert kbf-alert-error kbf-alert-noicon" style="margin-bottom:12px;display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;">
            <span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
              </svg>
            </span>
            <div>
              <strong>Withdrawal Rejected:</strong>
              <?php if(!empty($last_wd->admin_notes)): ?>
                <?php echo esc_html($last_wd->admin_notes); ?>
              <?php else: ?>
                <span>No rejection note was provided.</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          <?php if($f->status === 'pending'): ?>
          <div class="kbf-alert kbf-alert-warning kbf-alert-noicon" style="margin-bottom:12px;display:flex;align-items:center;gap:10px;">
            <span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.964 0L.165 13.233c-.457.778.091 1.767.982 1.767h13.706c.89 0 1.438-.99.982-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1-2.002 0 1 1 0 0 1 2.002 0z"/>
              </svg>
            </span>
            <div><strong>Under Review</strong> — Awaiting admin approval. Not visible to sponsors yet. Usually 24–48 hours.</div>
          </div>
          <?php elseif($f->status === 'suspended'): ?>
          <div style="background:#fce7f3;border-left:3px solid #db2777;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#831843;display:flex;align-items:flex-start;gap:10px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <div><strong>Fund Suspended</strong> -- Not visible to sponsors.<?php if($f->admin_notes): ?> Admin note: <?php echo esc_html($f->admin_notes); ?><?php else: ?> Contact support for details.<?php endif; ?></div>
          </div>
          <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenAppeal(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>')" style="margin:-6px 0 12px;">
            Appeal Suspension
          </button>
          <?php elseif($f->status === 'cancelled'): ?>
          <div class="kbf-alert kbf-alert-error kbf-alert-noicon" style="margin-bottom:12px;display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;">
            <span style="flex-shrink:0;color:inherit;display:inline-flex;align-items:center;">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
              </svg>
            </span>
            <div>
              <strong>Rejected:</strong>
              <?php if(!empty($f->admin_notes)): ?>
                <?php echo esc_html($f->admin_notes); ?>
              <?php else: ?>
                <span>No rejection message was provided. Please contact support if you need details.</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          <div class="kbf-card-header">
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                <strong style="font-size:15px;"><?php echo esc_html($f->title); ?></strong>
                <span class="kbf-badge kbf-badge-<?php echo $f->status; ?>"><?php echo ucfirst($f->status); ?></span>
              </div>
              <div class="kbf-meta">
                <?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?>
                <?php if($days_left!==null): ?> &bull; <span style="color:<?php echo $days_left<7?'#dc2626':'#64748b';?>;font-weight:600;"><?php echo $days_left; ?> days left</span><?php endif; ?>
                &bull; <?php echo $sc; ?> sponsors
                &bull; Escrow: <span class="kbf-badge kbf-badge-<?php echo $f->escrow_status; ?>" style="font-size:10px;"><?php echo ucfirst($f->escrow_status); ?></span>
              </div>
            </div>
          </div>
          <div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="kbf-fund-amounts">
            <span><strong>₱<?php echo number_format($f->raised_amount,2); ?></strong>raised</span>
            <span><strong>₱<?php echo number_format($f->goal_amount,2); ?></strong>goal</span>
            <span><strong><?php echo round($pct); ?>%</strong>funded</span>
          </div>
          <div class="kbf-btn-group" style="margin-top:12px;">
            <a class="kbf-btn kbf-btn-primary kbf-btn-sm" href="<?php echo esc_url(add_query_arg('fund_id',$f->id,$fund_details_url)); ?>">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/box-arrow-up-right.svg" alt="" width="12" height="12" style="filter:invert(100%);">
              View Details
            </a>
            <?php if(in_array($f->status,['active','pending'])): ?>
              <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfOpenEdit(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>','<?php echo esc_js($f->description); ?>','<?php echo esc_js($f->location); ?>','<?php echo esc_js($f->deadline); ?>',<?php echo (int)$f->auto_return; ?>)">Edit</button>
            <?php endif; ?>
            <?php
              $wd_block = $last_wd && in_array($last_wd->status, ['pending','approved','released']);
            ?>
            <?php if(in_array($f->status,['active','completed']) && $f->raised_amount>0 && !$wd_block): ?>
              <button class="kbf-btn kbf-btn-primary kbf-btn-sm" onclick="kbfOpenWd(<?php echo $f->id; ?>,<?php echo $f->raised_amount; ?>,'<?php echo esc_js($f->title); ?>')">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Request Withdrawal
              </button>
            <?php endif; ?>
            <?php if($f->status==='active' && $f->raised_amount>=$f->goal_amount): ?>
              <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfMarkComplete(<?php echo $f->id; ?>)">Mark Complete</button>
            <?php endif; ?>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/share-fill.svg" alt="" width="12" height="12" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
              Share
            </button>
            <?php if(!in_array($f->status,['cancelled','completed'])): ?>
              <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfCancelFund(<?php echo $f->id; ?>)">Cancel</button>
            <?php endif; ?>
          </div>
          <?php
          $sponsors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$st} WHERE fund_id=%d AND payment_status='completed' ORDER BY amount DESC LIMIT 5",$f->id));
          if(!empty($sponsors)): ?>
          <details class="kbf-sponsor-details">
            <summary>View Sponsors (<?php echo $sc; ?>)</summary>
            <div class="kbf-sponsor-details-content">
              <div class="kbf-table-wrap" style="margin-top:10px;">
              <table class="kbf-table">
                <thead><tr><th>Sponsor</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($sponsors as $sp): ?>
                  <tr>
                    <td><?php echo $sp->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($sp->sponsor_name); ?></td>
                    <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($sp->amount,2); ?></strong></td>
                    <td><?php echo esc_html($sp->payment_method==='online_payment'?'Online Payment':($sp->payment_method==='bank_payment'?'Bank Payment':ucfirst(str_replace('_',' ',isset($sp->payment_method) ? $sp->payment_method : '--')))); ?></td>
                    <td class="kbf-meta"><?php echo date('M d, Y',strtotime($sp->created_at)); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
              </div>
            </div>
          </details>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      </div>
      <?php endif; ?>
      
    <!-- ================== JS ================== -->
    <script>
      (function(){
        var statusEl = document.getElementById('kbf-filter-status');
        var escrowEl = document.getElementById('kbf-filter-escrow');
        if(!statusEl || !escrowEl) return;
        function applyFilters(){
          var statusVal = statusEl.value;
          var escrowVal = escrowEl.value;
          var cards = document.querySelectorAll('.kbf-section .kbf-card[data-status]');
          cards.forEach(function(card){
            var matchStatus = (statusVal === 'all') || (card.getAttribute('data-status') === statusVal);
            var matchEscrow = (escrowVal === 'all') || (card.getAttribute('data-escrow') === escrowVal);
            card.dataset.kbfFilterHidden = (matchStatus && matchEscrow) ? '0' : '1';
          });
          if (window.kbfHomeRenderCards) window.kbfHomeRenderCards();
        }
        statusEl.addEventListener('change', applyFilters);
        escrowEl.addEventListener('change', applyFilters);
        applyFilters();
      })();

      (function(){
        var wrap = document.querySelector('.kbf-card-list[data-kbf-card-pager="home"]');
        if(!wrap || wrap.dataset.kbfPager === 'on') return;
        var cards = Array.prototype.slice.call(wrap.querySelectorAll('.kbf-card[data-status]'));
        if(cards.length === 0) return;
        wrap.dataset.kbfPager = 'on';

        var pager = document.createElement('div');
        pager.className = 'kbf-table-pager';
        pager.innerHTML = '' +
          '<div class="kbf-table-pager-left">Show ' +
          '<select class="kbf-table-rows">' +
            '<option value="3">3</option>' +
            '<option value="5" selected>5</option>' +
            '<option value="10">10</option>' +
          '</select> cards</div>' +
          '<div class="kbf-table-pager-right">' +
            '<button class="kbf-table-pager-btn kbf-table-prev" type="button">Prev</button>' +
            '<span class="kbf-table-pager-page">1 / 1</span>' +
            '<button class="kbf-table-pager-btn kbf-table-next" type="button">Next</button>' +
          '</div>';
        wrap.insertAdjacentElement('afterend', pager);

        var select = pager.querySelector('.kbf-table-rows');
        var prevBtn = pager.querySelector('.kbf-table-prev');
        var nextBtn = pager.querySelector('.kbf-table-next');
        var pageLabel = pager.querySelector('.kbf-table-pager-page');
        var page = 1;
        var perPage = parseInt(select.value, 10) || 5;

        function getFilteredCards(){
          return cards.filter(function(card){ return card.dataset.kbfFilterHidden !== '1'; });
        }

        function render(){
          var visible = getFilteredCards();
          var total = visible.length;
          var pages = Math.max(1, Math.ceil(total / perPage));
          if(page > pages) page = pages;
          var start = (page - 1) * perPage;
          var end = start + perPage;
          cards.forEach(function(card){
            card.style.display = 'none';
          });
          visible.forEach(function(card, i){
            if (i >= start && i < end) card.style.display = '';
          });
          pageLabel.textContent = page + ' / ' + pages;
          prevBtn.disabled = page <= 1;
          nextBtn.disabled = page >= pages;
          pager.style.display = total > 0 ? 'flex' : 'none';
        }
        function setLoading(btn){
          btn.classList.add('is-loading');
          btn.disabled = true;
          setTimeout(function(){ btn.classList.remove('is-loading'); render(); }, 250);
        }
        select.addEventListener('change', function(){
          perPage = parseInt(this.value, 10) || 5;
          page = 1;
          render();
        });
        prevBtn.addEventListener('click', function(){
          if(page > 1){ page--; setLoading(prevBtn); }
        });
        nextBtn.addEventListener('click', function(){
          page++; setLoading(nextBtn);
        });

        window.kbfHomeRenderCards = function(){
          page = 1;
          render();
        };

        render();
      })();
      </script>
    </div>
    <?php return ob_get_clean();
}



