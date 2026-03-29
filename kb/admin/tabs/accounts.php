<?php
/*
 * KBF admin tab: Organizers.
 */

function kbf_admin_organizers_tab() {
    global $wpdb;$pt=$wpdb->prefix.'kbf_organizer_profiles';
    $rows=$wpdb->get_results("SELECT p.*,u.display_name,u.user_email FROM {$pt} p JOIN {$wpdb->users} u ON p.business_id=u.ID ORDER BY p.total_raised DESC"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    $total_orgs = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt}"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    $new_orgs = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$pt} p JOIN {$wpdb->users} u ON p.business_id=u.ID WHERE u.user_registered >= %s",
        gmdate('Y-m-d H:i:s', strtotime('-7 days'))
    ));
    $pending_verify = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$pt} WHERE verify_status='pending'"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <h3 class="kbf-section-title">Organizer Management</h3>
      <style>
        .kbf-table-wrap{
          max-width:100%;
          overflow-x:auto;
        }
        .kbf-table-accounts{
          min-width:980px;
        }
        .kbf-table-accounts .kbf-verify-cell{
          text-align:center;
        }
        .kbf-table-accounts .kbf-verify-stack{
          display:flex;
          flex-direction:column;
          align-items:center;
          gap:6px;
        }
        .kbf-table-accounts .kbf-btn.kbf-btn-sm{
          min-width:76px;
          justify-content:center;
          padding:0 9px;
          font-size:11px;
          border-radius:9px;
          height:32px;
        }
        .kbf-table-accounts .kbf-btn-group .kbf-btn{
          min-width:76px;
        }
        .kbf-table-accounts .kbf-verify-empty{
          color:var(--kbf-slate);
          font-size:12px;
          display:inline-block;
        }
      </style>
      <div class="kbf-stats" style="margin-bottom:18px;">
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <img class="kbf-stat-icon-img" src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/people-fill.svg" alt="" width="18" height="18">
          </div>
          <div>
            <div class="kbf-stat-label">Total Accounts</div>
            <div class="kbf-stat-value"><?php echo number_format($total_orgs); ?></div>
          </div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <img class="kbf-stat-icon-img" src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-plus-fill.svg" alt="" width="18" height="18">
          </div>
          <div>
            <div class="kbf-stat-label">New (7 days)</div>
            <div class="kbf-stat-value"><?php echo number_format($new_orgs); ?></div>
          </div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon kbf-stat-icon--plain">
            <img class="kbf-stat-icon-img" src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/shield-fill-check.svg" alt="" width="18" height="18">
          </div>
          <div>
            <div class="kbf-stat-label">Pending Verification</div>
            <div class="kbf-stat-value"><?php echo number_format($pending_verify); ?></div>
          </div>
        </div>
      </div>
      <?php if(empty($rows)): ?>
        <div class="kbf-table-empty">
          <div class="kbf-table-empty-head" style="grid-template-columns:1.2fr 1.4fr .7fr .7fr 1fr 1.1fr .9fr .7fr;">
            <span>Account</span>
            <span>Email</span>
            <span>Raised</span>
            <span>Supporters</span>
            <span>Credibility</span>
            <span>ID Verification</span>
            <span>Verify</span>
            <span>Onboarding</span>
          </div>
          <div class="kbf-table-empty-body">No organizer profiles yet.</div>
        </div>
      <?php else: ?>
      <div class="kbf-table-wrap">
        <table class="kbf-table kbf-table-accounts">
          <thead><tr><th>Account</th><th>Email</th><th>Raised</th><th>Supporters</th><th>Credibility Score</th><th>ID Verification</th><th>Verify</th><th>Onboarding</th></tr></thead>
          <tbody>
          <?php foreach($rows as $p): ?>
            <?php $onboarding_active = !empty(get_user_meta($p->business_id, 'kbf_show_onboarding', true)); ?>
            <tr>
              <td>
              <div class="kbf-cell-spacer"></div>
              <div class="kbf-cell-spacer"></div>
              <strong><?php echo esc_html($p->display_name); ?></strong>
              <div class="kbf-cell-spacer"></div>
              <div class="kbf-cell-spacer"></div>
              </td>
              <td class="kbf-meta" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?php echo esc_html($p->user_email); ?>
              </td>
              <td><strong>₱<?php echo number_format($p->total_raised,0); ?></strong></td>
              <td><?php echo number_format($p->total_sponsors); ?></td>
            <td><?php echo number_format($p->rating,1); ?>/5 (<?php echo $p->rating_count; ?>)</td>
            <td class="kbf-verify-cell">
              <?php if(!empty($p->verify_id_front) || !empty($p->verify_id_back)): ?>
                <div class="kbf-btn-group kbf-verify-stack">
                  <?php if(!empty($p->verify_id_front)): ?><a class="kbf-btn kbf-btn-secondary kbf-btn-sm" href="<?php echo esc_url($p->verify_id_front); ?>" target="_blank">Front ID</a><?php endif; ?>
                  <?php if(!empty($p->verify_id_back)): ?><a class="kbf-btn kbf-btn-secondary kbf-btn-sm" href="<?php echo esc_url($p->verify_id_back); ?>" target="_blank">Back ID</a><?php endif; ?>
                </div>
              <?php else: ?><span class="kbf-verify-empty">—</span><?php endif; ?>
            </td>
              <td class="kbf-verify-cell">
                <div class="kbf-btn-group kbf-verify-stack">
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfVerifyOrg(<?php echo $p->business_id; ?>,1)">Approve</button>
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfVerifyOrg(<?php echo $p->business_id; ?>,0)">Reject</button>
                </div>
              </td>
              <td class="kbf-verify-cell">
                <div class="kbf-btn-group kbf-verify-stack">
                  <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfTriggerOnboarding(<?php echo $p->business_id; ?>)">Send</button>
                  <span class="kbf-verify-empty"><?php echo $onboarding_active ? 'Active' : 'Off'; ?></span>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}








