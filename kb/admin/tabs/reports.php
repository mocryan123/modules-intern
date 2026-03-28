<?php
/*
 * KBF admin tab: Reports.
 */

function kbf_admin_reports_tab() {
    global $wpdb;$rt=$wpdb->prefix.'kbf_reports';$ft=$wpdb->prefix.'kbf_funds';
    $fund_details_url = kbf_get_page_url('fund_details');
    $rows=$wpdb->get_results("SELECT r.*,f.title as fund_title FROM {$rt} r JOIN {$ft} f ON r.fund_id=f.id ORDER BY FIELD(r.status,'open','dismissed'),r.created_at DESC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Fund Reports</h3>
      <?php if(empty($rows)): ?><div class="kbf-empty"><p>No reports filed.</p></div>
      <?php else: ?>
      <div class="kbf-admin-card-list" data-kbf-card-pager="reports">
        <?php foreach($rows as $r): ?>
        <div class="kbf-card kbf-admin-card">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
            <div>
              <strong style="font-size:14px;">Fund: <?php echo esc_html($r->fund_title); ?></strong>
              <div style="font-size:13px;color:var(--kbf-red);font-weight:700;margin-top:2px;">Reason: <?php echo esc_html($r->reason); ?></div>
              <p style="font-size:13px;color:var(--kbf-text-sm);margin:6px 0 0;"><?php echo esc_html($r->details); ?></p>
              <div class="kbf-meta" style="margin-top:6px;"><?php echo $r->reporter_email?esc_html($r->reporter_email):'Anonymous reporter'; ?> &bull; <?php echo date('M d, Y H:i',strtotime($r->created_at)); ?></div>
              <?php if($r->admin_notes): ?><div class="kbf-alert kbf-alert-info" style="margin-top:8px;font-size:12px;"><strong>Admin Note:</strong> <?php echo esc_html($r->admin_notes); ?></div><?php endif; ?>
            </div>
            <span class="kbf-badge kbf-badge-<?php echo $r->status; ?>"><?php echo ucfirst($r->status); ?></span>
          </div>
          <?php if($r->status==='open'): ?>
          <div class="kbf-btn-group">
            <a class="kbf-btn kbf-btn-primary kbf-btn-sm" href="<?php echo esc_url(add_query_arg('fund_id',$r->fund_id,$fund_details_url)); ?>">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/box-arrow-up-right.svg" alt="" width="12" height="12" style="filter:invert(100%);">
              View Fund
            </a>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfSuspend(<?php echo $r->fund_id; ?>)">Suspend Fund</button>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfDismissReport(<?php echo $r->id; ?>)">Dismiss</button>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <script>
      (function(){
        var wrap = document.querySelector('.kbf-admin-card-list[data-kbf-card-pager="reports"]');
        if(!wrap || wrap.dataset.kbfPager === 'on') return;
        var cards = Array.prototype.slice.call(wrap.querySelectorAll('.kbf-admin-card'));
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
        var perPage = 5;

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
        render();
      })();
    </script>
    <?php return ob_get_clean();
}
