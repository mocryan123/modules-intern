<?php
/*
 * KBF admin tab: Organizers.
 */

function kbf_admin_organizers_tab() {
    global $wpdb;$pt=$wpdb->prefix.'kbf_organizer_profiles';
    $rows=$wpdb->get_results("SELECT p.*,u.display_name,u.user_email FROM {$pt} p JOIN {$wpdb->users} u ON p.business_id=u.ID ORDER BY p.total_raised DESC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:16px;">Organizer Management</h3>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Account</th><th>Email</th><th>Raised</th><th>Supporters</th><th>Rating</th><th>Verified</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--kbf-slate);">No organizer profiles yet.</td></tr>
          <?php else: foreach($rows as $p): ?>
            <tr>
              <td><strong><?php echo esc_html($p->display_name); ?></strong></td>
              <td class="kbf-meta"><?php echo esc_html($p->user_email); ?></td>
              <td><strong style="color:var(--kbf-green);">₱<?php echo number_format($p->total_raised,0); ?></strong></td>
              <td><?php echo number_format($p->total_sponsors); ?></td>
              <td><?php echo number_format($p->rating,1); ?>/5 (<?php echo $p->rating_count; ?>)</td>
              <td><?php echo $p->is_verified?'<span style="color:var(--kbf-green);font-weight:700;">Verified</span>':'--'; ?></td>
              <td><button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfVerifyOrg(<?php echo $p->business_id; ?>,<?php echo $p->is_verified; ?>)"><?php echo $p->is_verified?'Revoke Verification':'Verify Organizer'; ?></button></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php return ob_get_clean();
}