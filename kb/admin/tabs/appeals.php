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
      <?php else: foreach($rows as $a): ?>
        <div class="kbf-card" style="border-left:3px solid <?php echo $a->status==='open'?'var(--kbf-blue)':($a->status==='approved'?'var(--kbf-green)':'var(--kbf-border)'); ?>;">
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
      <?php endforeach; endif; ?>
    </div>
    <?php return ob_get_clean();
}