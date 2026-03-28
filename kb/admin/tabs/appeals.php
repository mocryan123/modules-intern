<?php
/*
 * KBF admin tab: Appeals.
 */

function kbf_admin_appeals_tab() {
    global $wpdb;
    $at = $wpdb->prefix.'kbf_appeals';
    $ft = $wpdb->prefix.'kbf_funds';
    $rows = $wpdb->get_results("SELECT a.*,f.title as fund_title FROM {$at} a JOIN {$ft} f ON a.fund_id=f.id ORDER BY FIELD(a.status,'open','reviewed','approved','rejected'),a.created_at DESC"); // phpcs:ignore
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Suspension Appeals</h3>
      <?php if(empty($rows)): ?><div class="kbf-empty"><p>No appeals filed.</p></div>
      <?php else: ?>
      <div class="kbf-admin-card-list" data-kbf-card-pager="appeals">
        <?php foreach($rows as $a): ?>
        <div class="kbf-card kbf-admin-card">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
            <div>
              <strong style="font-size:14px;">Fund: <?php echo esc_html($a->fund_title); ?></strong>
              <p style="font-size:13px;color:var(--kbf-text-sm);margin:6px 0 0;"><?php echo esc_html($a->message); ?></p>
              <div class="kbf-meta" style="margin-top:6px;">Appeal ID: <?php echo esc_html($a->rand_id); ?> &bull; <?php echo date('M d, Y H:i',strtotime($a->created_at)); ?></div>
              <?php if($a->admin_notes): ?><div class="kbf-alert kbf-alert-info" style="margin-top:8px;font-size:12px;"><strong>Admin Note:</strong> <?php echo esc_html($a->admin_notes); ?></div><?php endif; ?>
            </div>
            <span class="kbf-badge kbf-badge-<?php echo $a->status; ?>"><?php echo ucfirst($a->status); ?></span>
          </div>
          <?php if($a->status==='open'): ?>
          <div class="kbf-btn-group">
            <button class="kbf-btn kbf-btn-success kbf-btn-sm" onclick="kbfReviewAppeal(<?php echo $a->id; ?>,'approve')">Approve & Reinstate</button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfReviewAppeal(<?php echo $a->id; ?>,'reject')">Reject</button>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <script>
      (function(){
        var wrap = document.querySelector('.kbf-admin-card-list[data-kbf-card-pager="appeals"]');
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
