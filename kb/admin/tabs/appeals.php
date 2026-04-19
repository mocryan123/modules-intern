<?php
/*
 * KBF admin tab: Appeals.
 */

/**
 * @function  kbf_admin_appeals_tab
 * @purpose   Renders the admin suspension appeals tab with appeal cards and client-side pagination controls.
 * @used-by   [admin/ui.php tab router, includes/ajax-admin.php tab refresh handler, user/partials/admin_embed.php tab renderer]
 * @calls     [kbf_admin_date_where, $wpdb->prepare, $wpdb->get_results, date, strtotime, ob_start, ob_get_clean, esc_html, esc_attr, sanitize_html_class, ucfirst]
 * @params    [none]
 * @returns   [string buffered HTML markup for the appeals tab]
 * @status    ACTIVE
 *            ACTIVE = confirmed it is called somewhere
 *            NEEDS REVIEW = could not confirm caller,
 *                           may be unused/dead code
 */
function kbf_admin_appeals_tab() {
    global $wpdb;
    $at = $wpdb->prefix.'kbf_appeals';
    $ft = $wpdb->prefix.'kbf_funds';
    $params = [];
    $where = "WHERE 1=1";
    $where .= kbf_admin_date_where('a.created_at', $params);
    $sql = "SELECT a.*,f.title as fund_title FROM {$at} a JOIN {$ft} f ON a.fund_id=f.id {$where} ORDER BY FIELD(a.status,'open','reviewed','approved','rejected'),a.created_at DESC";
    $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql); // phpcs:ignore
    /**
     * @function  format_date
     * @purpose   Formats an appeal timestamp into a human-readable admin date string.
     * @used-by   [kbf_admin_appeals_tab output block for appeal created_at]
     * @calls     [date, strtotime]
     * @params    [mixed $value - date/time string from database]
     * @returns   [string formatted date or fallback placeholder]
     * @status    ACTIVE
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $format_date = function($value) {
        return $value ? date('M d, Y H:i', strtotime($value)) : '--';
    };
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title">Suspension Appeals</h3>
      <?php if(empty($rows)): ?><div class="kbf-empty"><p>No appeals filed.</p></div>
      <?php else: ?>
      <div class="kbf-admin-card-list" data-kbf-card-pager="appeals">
        <?php foreach($rows as $a): ?>
        <div class="kbf-card kbf-admin-card">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
            <div>
              <span style="font-size:14px;" class="kbf-strong">Fund: <?php echo esc_html($a->fund_title); ?></span>
              <p style="font-size:13px;color:var(--kbf-text-sm);margin:6px 0 0;"><?php echo esc_html($a->message); ?></p>
              <div class="kbf-meta" style="margin-top:6px;">Appeal ID: <?php echo esc_html($a->rand_id); ?> &bull; <?php echo $format_date($a->created_at); ?></div>
              <?php if($a->admin_notes): ?><div class="kbf-alert kbf-alert-info kbf-alert-compact" style="margin-top:8px;"><span class="kbf-strong">Admin Note:</span> <?php echo esc_html($a->admin_notes); ?></div><?php endif; ?>
            </div>
            <span class="kbf-badge kbf-badge-<?php echo esc_attr(sanitize_html_class((string)$a->status)); ?>"><?php echo esc_html(ucfirst((string)$a->status)); ?></span>
          </div>
          <?php if($a->status==='open'): ?>
          <div class="kbf-btn-group" style="justify-content:flex-end;gap:8px;position:relative;z-index:5;pointer-events:auto;">
            <button type="button" class="kbf-btn kbf-btn-secondary kbf-btn-sm" style="cursor:pointer;pointer-events:auto;position:relative;z-index:6;" data-kbf-appeal-id="<?php echo (int)$a->id; ?>" data-kbf-appeal-action="reject" onclick="return window.kbfAppealQuickAction ? window.kbfAppealQuickAction(this, <?php echo (int)$a->id; ?>, 'reject') : false;">Reject</button>
            <button type="button" class="kbf-btn kbf-btn-success kbf-btn-sm" style="cursor:pointer;pointer-events:auto;position:relative;z-index:6;" data-kbf-appeal-id="<?php echo (int)$a->id; ?>" data-kbf-appeal-action="approve" onclick="return window.kbfAppealQuickAction ? window.kbfAppealQuickAction(this, <?php echo (int)$a->id; ?>, 'approve') : false;">Approve & Reinstate</button>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="kbf-table-desc">Lists organizer appeals against suspensions and their status.</div>
    </div>
    <script>
      (function(){
        window.kbfAppealQuickAction = function(btn, id, action){
          console.log('[kbfAppeal] click', { id: id, action: action, hasReview: typeof window.kbfReviewAppeal === 'function', hasOpenRejectModal: typeof window.kbfOpenAdminRejectModal === 'function', hasAdmin: typeof window.kbfAdmin === 'function' });
          if (!id || !action) return false;
          if (typeof window.kbfReviewAppeal === 'function') {
            console.log('[kbfAppeal] using window.kbfReviewAppeal');
            window.kbfReviewAppeal(id, action);
            return false;
          }
          console.warn('[kbfAppeal] window.kbfReviewAppeal is missing, using fallback');
          if (action === 'reject') {
            if (typeof window.kbfOpenAdminRejectModal === 'function') {
              console.log('[kbfAppeal] opening reject modal fallback');
              window.kbfOpenAdminRejectModal('appeal', { appeal_id: id });
              return false;
            }
            console.warn('[kbfAppeal] reject modal function missing, using prompt fallback');
            if (typeof window.kbfAdmin === 'function') {
              var rejectNotes = prompt('Reason for rejection (required):');
              if (rejectNotes === null) return false;
              rejectNotes = String(rejectNotes || '').trim();
              if (!rejectNotes) { alert('Please provide a reason for rejection.'); return false; }
              console.log('[kbfAppeal] sending reject via kbfAdmin');
              window.kbfAdmin('kbf_admin_review_appeal', { appeal_id: id, action_type: 'reject', notes: rejectNotes });
            } else {
              console.error('[kbfAppeal] window.kbfAdmin is missing; cannot submit reject');
            }
            return false;
          }
          if (typeof window.kbfAdmin === 'function') {
            var approveNotes = prompt('Admin notes (optional):');
            if (approveNotes === null) return false;
            console.log('[kbfAppeal] sending approve via kbfAdmin');
            window.kbfAdmin('kbf_admin_review_appeal', { appeal_id: id, action_type: action, notes: approveNotes });
          } else {
            console.error('[kbfAppeal] window.kbfAdmin is missing; cannot submit approve');
          }
          return false;
        };
        var wrap = document.querySelector('.kbf-admin-card-list[data-kbf-card-pager="appeals"]');
        if(!wrap || wrap.dataset.kbfPager === 'on') return;
        var cards = Array.prototype.slice.call(wrap.querySelectorAll('.kbf-admin-card'));
        if(cards.length === 0) return;
        wrap.dataset.kbfPager = 'on';

        var pager = document.createElement('div');
        pager.className = 'kbf-table-pager';
        pager.innerHTML = '' +
          '<div class="kbf-table-pager-left">Show&nbsp;' +
          '<select class="kbf-table-rows">' +
            '<option value="3">3</option>' +
            '<option value="5" selected>5</option>' +
            '<option value="10">10</option>' +
          '</select>&nbsp;cards</div>' +
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
        var perPage = 5;
        function handleAppealAction(id, action){
          if(!id || !action) return;
          if (typeof window.kbfAppealQuickAction === 'function') {
            window.kbfAppealQuickAction(null, id, action);
          }
        }

        /**
         * @function  render
         * @purpose   Updates visible appeal cards and pager button states for the current page settings.
         * @used-by   [IIFE initialization, rows-per-page select change handler, prev button callback, next button callback]
         * @calls     [Math.max, Math.ceil, Array.forEach]
         * @params    [none]
         * @returns   [void]
         * @status    ACTIVE
         */
        function render(){
          var total = cards.length;
          var pages = Math.max(1, Math.ceil(total / perPage));
          if(page > pages) page = pages;
          var start = (page - 1) * perPage;
          var end = start + perPage;
          cards.forEach(function(card, i){
            card.style.display = (i >= start && i < end) ? '' : 'none';
          });
          pageLabel.textContent = page + ' / ' + pages;
          prevBtn.disabled = page <= 1;
          nextBtn.disabled = page >= pages;
          pager.style.display = total > 0 ? 'flex' : 'none';
        }
        /**
         * @function  setLoading
         * @purpose   Applies a temporary loading state to a pager button before rerendering card pagination.
         * @used-by   [prev button click handler, next button click handler]
         * @calls     [setTimeout, render]
         * @params    [HTMLElement btn - pager button element to toggle loading state]
         * @returns   [void]
         * @status    ACTIVE
         */
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
        wrap.addEventListener('click', function(e){
          var btn = e.target && e.target.closest ? e.target.closest('[data-kbf-appeal-action][data-kbf-appeal-id]') : null;
          if(!btn || !wrap.contains(btn)) return;
          e.preventDefault();
          var id = parseInt(btn.getAttribute('data-kbf-appeal-id'), 10) || 0;
          var action = String(btn.getAttribute('data-kbf-appeal-action') || '').trim();
          console.log('[kbfAppeal] delegated click', { id: id, action: action });
          handleAppealAction(id, action);
        });
        render();
      })();
    </script>
    <?php return ob_get_clean();
}


