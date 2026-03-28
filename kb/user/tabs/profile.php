<?php
/*
 * KBF user dashboard tab: Profile.
 */

function kbf_dashboard_profile_tab($business_id) {
    global $wpdb;
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$business_id));
    $user = get_userdata($business_id);
    $socials = $profile && $profile->social_links ? json_decode($profile->social_links,true) : [];
    $phone = get_user_meta($business_id, 'kbf_phone', true);
    $address = get_user_meta($business_id, 'kbf_address', true);
    $nonce = wp_create_nonce('kbf_organizer_profile');
    $nonce_verify = wp_create_nonce('kbf_verify_account');

    ob_start();
    ?>
    <!-- ================== CSS ================== -->
    <style>
      .kbf-section {
        width: 100%;
        box-sizing: border-box;
        overflow: hidden;
      }
      .kbf-section form {
        width: 100%;
        box-sizing: border-box;
      }
      .kbf-profile-title { margin-bottom: 6px; }
      .kbf-profile-subtitle { margin: 0 0 18px; color: var(--kbf-slate); font-size: 13px; }

      /* ── Main two-column grid ── */
      .kbf-profile-grid {
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
        gap: 18px;
        align-items: start;
        width: 100%;
        box-sizing: border-box;
      }
      .kbf-profile-grid > * {
        min-width: 0;
        box-sizing: border-box;
      }
      .kbf-profile-card-left,
      .kbf-profile-stack {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
      }
      .kbf-profile-stack { display: flex; flex-direction: column; gap: 18px; }

      /* ── Cards ── */
      .kbf-profile-card { padding: 18px; border-radius: 18px; width: 100%; box-sizing: border-box; }
      .kbf-profile-card-left {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: center;
        text-align: left;
      }

      /* ── Card header ── */
      .kbf-profile-header { display: flex; justify-content: space-between; align-items: center; width: 100%; }
      .kbf-profile-card-title { font-weight: 700; margin-bottom: 8px; }

      /* ── Avatar ── */
      .kbf-profile-photo {
        width: 190px;
        height: 190px;
        border-radius: 28px;
        object-fit: cover;
        border: 1px solid var(--kbf-border);
        background: #fff;
      }
      .kbf-photo-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
      }
      .kbf-photo-overlay {
        position: absolute;
        inset: 0;
        border-radius: 28px;
        background: rgba(15,23,42,.45);
        opacity: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity .2s ease;
        pointer-events: none;
      }
      .kbf-photo-wrap:hover .kbf-photo-overlay { opacity: 1; }
      .kbf-photo-edit {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg,#60a5fa,#3b82f6);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 18px rgba(59,130,246,.35);
      }
      .kbf-photo-edit img { filter: invert(100%); }

      /* ── Meta / misc ── */
      .kbf-profile-meta { font-size: 13px; color: var(--kbf-slate); }
      .kbf-profile-note { margin-top: 4px; }
      .kbf-profile-verify-tag{margin-top:10px;text-align:center;font-size:12px;font-weight:700;padding:6px 10px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;gap:6px;}
      .kbf-profile-verify-tag.kbf-verified{background:#e7f1ff;color:#1f3b8a;}
      .kbf-profile-verify-tag.kbf-not-verified{background:#eef2f7;color:#64748b;}
      .kbf-profile-verify-wrap{text-align:center;}
      .kbf-profile-divider { height: 1px; background: var(--kbf-border); margin: 10px 0; }
      .kbf-profile-actions { display: flex; gap: 10px; align-items: center; width: 100%; }
      .kbf-profile-actions .kbf-btn { width: 100%; justify-content: center; }

      /* ── Password toggle ── */
      .kbf-input-with-toggle { position: relative; }
      .kbf-input-with-toggle input { padding-right: 42px; }
      .kbf-toggle-visibility {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: var(--kbf-slate);
        cursor: pointer;
        font-size: 12px;
      }
      .kbf-toggle-visibility:hover { color: var(--kbf-primary); }

      /* ── Bio ── */
      .kbf-profile-bio { margin-top: 8px; width: 100%; }
      .kbf-profile-bio textarea { width: 100%; box-sizing: border-box; }
      .kbf-profile-card-left .kbf-form-group { width: 100%; }
      .kbf-profile-card-left .kbf-form-group input,
      .kbf-profile-card-left .kbf-form-group textarea { width: 100%; box-sizing: border-box; }

      /* ── 3-col form row ── */
      .kbf-form-row-3 {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        width: 100%;
        box-sizing: border-box;
      }

      /* ── File input ── */
      .kbf-file-input { display: none; }
      .kbf-file-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        border: 1px solid var(--kbf-border);
        background: #fff;
        color: var(--kbf-text);
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        width: 100%;
        justify-content: center;
        box-sizing: border-box;
      }
      .kbf-file-btn:hover { border-color: var(--kbf-primary); color: var(--kbf-primary); }

      /* ── Stats grid ── */
      .kbf-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        width: 100%;
        box-sizing: border-box;
      }
      .kbf-stat-card {
        background: #fff;
        border: 1px solid var(--kbf-border);
        border-radius: 16px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        box-sizing: border-box;
      }
      .kbf-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        flex-shrink: 0;
      }
      .kbf-stat-icon img { width: 20px; height: 20px; filter: invert(36%) sepia(88%) saturate(2029%) hue-rotate(198deg) brightness(95%) contrast(95%); }
      .kbf-stat-icon--amber img { filter: invert(54%) sepia(93%) saturate(1461%) hue-rotate(14deg) brightness(96%) contrast(92%); }
      .kbf-stat-label { font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: .6px; }
      .kbf-stat-value { font-size: 18px; font-weight: 800; color: #0f172a; }
      .kbf-stat-sub { font-size: 12px; color: #64748b; }

      /* ── Cropper ── */
      .kbf-cropper-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
      }
      .kbf-cropper-modal {
        background: #fff;
        border-radius: 18px;
        padding: 16px;
        max-width: 420px;
        width: 92%;
        box-shadow: 0 20px 50px rgba(15,23,42,.25);
      }
      .kbf-cropper-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
      .kbf-cropper-title { font-weight: 700; }
      .kbf-cropper-sub { font-size: 12px; color: var(--kbf-slate); margin-bottom: 12px; }
      .kbf-cropper-stage {
        position: relative;
        width: 240px;
        height: 240px;
        margin: 0 auto;
        border-radius: 14px;
        overflow: hidden;
        background: #f1f5f9;
        cursor: grab;
        border: 1px dashed #cbd5f5;
      }
      .kbf-cropper-stage.is-dragging { cursor: grabbing; }
      .kbf-cropper-image { position: absolute; top: 0; left: 0; user-select: none; pointer-events: none; transform-origin: 0 0; }
      .kbf-cropper-mask {
        position: absolute;
        inset: 0;
        box-shadow: 0 0 0 9999px rgba(15,23,42,.35);
        border-radius: 16px;
        pointer-events: none;
      }
      .kbf-cropper-controls { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
      .kbf-cropper-controls input[type=range] { flex: 1; }
      #kbf-cropper-zoom{
        accent-color: #3b82f6;
      }
      #kbf-cropper-zoom::-webkit-slider-thumb{
        background:#3b82f6;
        border:2px solid #dbeafe;
      }
      #kbf-cropper-zoom::-moz-range-thumb{
        background:#3b82f6;
        border:2px solid #dbeafe;
      }
      .kbf-cropper-actions { display: flex; gap: 10px; margin-top: 12px; }
      .kbf-cropper-actions .kbf-btn { width: 100%; justify-content: center; }
      .kbf-cropper-close {
        border: none;
        background: #f1f5f9;
        color: #64748b;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
      }

      /* ── Responsive ── */
      @media (max-width: 900px) {
        .kbf-profile-grid { grid-template-columns: 1fr; }
        .kbf-profile-card-left,
        .kbf-profile-stack { width: 100%; min-width: 0; }
        .kbf-stats-grid { grid-template-columns: repeat(2, 1fr); }
        .kbf-form-row-3 { grid-template-columns: 1fr; }
      }
      @media (max-width: 480px) {
        .kbf-stats-grid { grid-template-columns: 1fr; }
      }
    </style>

    <!-- ================== HTML ================== -->
    <div class="kbf-section">
      <form id="kbf-profile-form" enctype="multipart/form-data" data-nonce="<?php echo esc_attr($nonce); ?>">
        <div class="kbf-profile-grid">

          <!-- ══ LEFT CARD ══ -->
          <div class="kbf-card kbf-profile-card kbf-profile-card-left">
            <div class="kbf-profile-header">
              <div style="font-weight:700;">My profile</div>
              <div class="kbf-profile-meta">Last update: <?php echo esc_html($profile && $profile->updated_at ? date('M d, Y', strtotime($profile->updated_at)) : 'Recently'); ?></div>
            </div>

            <?php
              $avatar = $profile && $profile->avatar_url ? $profile->avatar_url : get_avatar_url($user->ID, ['size'=>240]);
    ?>
            <label class="kbf-photo-wrap" for="kbf-avatar" id="kbf-photo-wrap">
              <img src="<?php echo esc_url($avatar); ?>" alt="Profile photo" class="kbf-profile-photo">
              <div class="kbf-photo-overlay">
                <div class="kbf-photo-edit">
                  <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/pencil-fill.svg" alt="Edit" width="16" height="16">
                </div>
              </div>
            </label>
            <input id="kbf-avatar" class="kbf-file-input" type="file" name="avatar" accept="image/*">
            <div class="kbf-profile-meta kbf-profile-note">Recommended 800x800px JPG or PNG.</div>
            <div class="kbf-profile-verify-wrap" style="margin-bottom:20px">
              <?php if($profile && $profile->is_verified): ?>
                <div class="kbf-profile-verify-tag kbf-verified">Verified</div>
              <?php elseif($profile && !empty($profile->verify_status) && $profile->verify_status==='pending'): ?>
                <div class="kbf-profile-verify-tag kbf-not-verified">Pending Review</div>
              <?php elseif($profile && !empty($profile->verify_status) && $profile->verify_status==='rejected'): ?>
                <div class="kbf-profile-verify-tag kbf-not-verified">Rejected</div>
              <?php else: ?>
                <div class="kbf-profile-verify-tag kbf-not-verified">Not Verified</div>
              <?php endif; ?>
            </div>
           

            <div class="kbf-form-group">
              <label>Display Name</label>
              <input type="text" value="<?php echo esc_attr($user->display_name); ?>" disabled style="background:var(--kbf-slate-lt);">
            </div>

            <div class="kbf-profile-bio">
              <div class="kbf-profile-card-title">Bio / About</div>
              <textarea id="kbf-profile-bio" name="bio" rows="4" maxlength="300" placeholder="Tell sponsors about yourself or your organization..."><?php echo esc_textarea(isset($profile->bio) ? $profile->bio : ''); ?></textarea>
              <div class="kbf-char-count" id="kbf-profile-bio-count">0 / 300</div>
            </div>

            <div class="kbf-form-group">
              <label>Email</label>
              <input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled style="background:var(--kbf-slate-lt);">
            </div>

            <div class="kbf-form-group">
              <label>Phone</label>
              <input type="text" name="phone" value="<?php echo esc_attr($phone); ?>" placeholder="+63 9XX XXX XXXX">
            </div>

            <?php if($profile && !empty($profile->verify_status) && $profile->verify_status==='pending'): ?>
              <div class="kbf-alert kbf-alert-warning kbf-alert-compact kbf-alert-center kbf-alert-block" style="margin:6px 0;">
                Pending.
              </div>
            <?php endif; ?>
            <?php if($profile && !empty($profile->verify_status) && $profile->verify_status==='rejected' && !empty($profile->verify_notes)): ?>
              <div class="kbf-alert kbf-alert-error kbf-alert-noicon kbf-alert-compact kbf-alert-center kbf-alert-block" style="margin:6px 0;">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/x-circle-fill.svg" alt="" width="14" height="14" style="filter:invert(21%) sepia(70%) saturate(4683%) hue-rotate(342deg) brightness(88%) contrast(101%);">
                Verification rejected: <?php echo esc_html($profile->verify_notes); ?>
              </div>
            <?php endif; ?>
            <div class="kbf-profile-divider"></div>
            <div class="kbf-profile-actions">
              <?php
                $is_pending = ($profile && !empty($profile->verify_status) && $profile->verify_status==='pending');
              ?>
              <button type="button" class="kbf-btn kbf-btn-secondary" <?php echo $is_pending ? 'disabled' : ''; ?> onclick="if(!this.disabled) kbfOpenVerifyModal()">
                <?php echo $is_pending ? 'Pending' : 'Verify Account'; ?>
              </button>
              <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSaveProfile('<?php echo $nonce; ?>')">Save Changes</button>
            </div>
          </div>

          <!-- ══ RIGHT STACK ══ -->
          <div class="kbf-profile-stack">

            <!-- Payout Details -->
            <div class="kbf-card kbf-profile-card">
              <div class="kbf-profile-card-title">Payout Details</div>
              <div class="kbf-form-row kbf-form-row-3">
                <div class="kbf-form-group">
                  <label>Account Type</label>
                  <select name="payout_type">
                    <option value="">Select type</option>
                    <option value="maya_wallet">Maya Wallet</option>
                    <option value="gcash">GCash</option>
                    <option value="card">Credit/Debit Card</option>
                  </select>
                </div>
                <div class="kbf-form-group">
                  <label>Account Name</label>
                  <input type="text" name="payout_name" placeholder="Full name on the account">
                </div>
                <div class="kbf-form-group">
                  <label>Account Number</label>
                  <div class="kbf-input-with-toggle">
                    <input type="password" name="payout_number" id="kbf-payout-number" placeholder="e.g., 09XX XXX XXXX or card number">
                    <button type="button" class="kbf-toggle-visibility" onclick="kbfTogglePayoutNumber()">Show</button>
                  </div>
                </div>
              </div>
              <div class="kbf-profile-meta" style="margin-top:8px;">We'll use this for Maya/GCash payouts. Double-check your details to avoid delays.</div>
            </div>

            <!-- Address -->
            <div class="kbf-card kbf-profile-card">
              <div class="kbf-profile-card-title">Address</div>
              <div class="kbf-form-row kbf-form-row-3">
                <div class="kbf-form-group">
                  <label>Province</label>
                  <select id="kbf-profile-province">
                    <option value="">Select Province</option>
                    <?php foreach (kbf_get_provinces() as $p): ?>
                      <option value="<?php echo esc_attr($p); ?>"><?php echo esc_html($p); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="kbf-form-group">
                  <label>Municipality</label>
                  <select id="kbf-profile-municipality" disabled>
                    <option value="">Select Municipality</option>
                  </select>
                </div>
                <div class="kbf-form-group">
                  <label>Barangay</label>
                  <select id="kbf-profile-barangay" disabled>
                    <option value="">Select Barangay</option>
                  </select>
                </div>
              </div>
              <input type="hidden" name="address" id="kbf-profile-address" value="<?php echo esc_attr($address); ?>">
            </div>

            <!-- Profile Stats -->
            <div class="kbf-card kbf-profile-card">
              <div class="kbf-profile-card-title">Profile Stats</div>
              <?php if ($profile): ?>
              <div class="kbf-stats kbf-stats-grid">
                <div class="kbf-stat kbf-stat-card">
                  <div class="kbf-stat-icon">
                    <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/piggy-bank-fill.svg" alt="" width="16" height="16">
                  </div>
                  <div>
                    <div class="kbf-stat-label">TOTAL RAISED</div>
                    <div class="kbf-stat-value">&#8369;<?php echo number_format($profile->total_raised, 0); ?></div>
                  </div>
                </div>
                <div class="kbf-stat kbf-stat-card">
                  <div class="kbf-stat-icon">
                    <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg" alt="" width="16" height="16">
                  </div>
                  <div>
                    <div class="kbf-stat-label">TOTAL SPONSORS</div>
                    <div class="kbf-stat-value"><?php echo number_format($profile->total_sponsors); ?></div>
                  </div>
                </div>
                <div class="kbf-stat kbf-stat-card">
                  <div class="kbf-stat-icon">
                    <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg" alt="" width="16" height="16" class="kbf-stat-icon-img">
                  </div>
                  <div>
                    <div class="kbf-stat-label">RATING</div>
                    <div class="kbf-stat-value"><?php echo number_format($profile->rating, 1); ?>/5</div>
                    <div class="kbf-stat-sub"><?php echo esc_html($profile->rating_count); ?> reviews</div>
                  </div>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <div id="kbf-profile-msg"></div>

          </div><!-- /.kbf-profile-stack -->
        </div><!-- /.kbf-profile-grid -->

        <!-- ══ CROPPER MODAL ══ -->
        <div class="kbf-cropper-backdrop" id="kbf-cropper-backdrop" aria-hidden="true">
          <div class="kbf-cropper-modal" role="dialog" aria-modal="true" aria-label="Crop profile photo">
            <div class="kbf-cropper-header">
              <div class="kbf-cropper-title">Crop your photo</div>
              <button type="button" class="kbf-cropper-close" id="kbf-cropper-close">&times;</button>
            </div>
            <div class="kbf-cropper-sub">Adjust the image to fit the circle.</div>
            <div class="kbf-cropper-stage" id="kbf-cropper-stage">
              <img id="kbf-cropper-image" class="kbf-cropper-image" alt="Crop preview">
              <div class="kbf-cropper-mask"></div>
            </div>
            <div class="kbf-cropper-controls">
              <span style="font-size:12px;color:var(--kbf-slate);">Zoom</span>
              <input type="range" id="kbf-cropper-zoom" min="1" max="3" step="0.01" value="1">
            </div>
            <div class="kbf-cropper-actions">
              <button type="button" class="kbf-btn" id="kbf-cropper-cancel">Cancel</button>
              <button type="button" class="kbf-btn kbf-btn-primary" id="kbf-cropper-apply">Confirm photo</button>
            </div>
          </div>
        </div>

      </form>

      <!-- Verify Account Modal -->
      <div id="kbf-modal-verify-account" class="kbf-modal-overlay" style="display:none;">
        <div class="kbf-modal kbf-modal-sm">
          <div class="kbf-modal-header">
            <h3 class="kbf-section-title">Verify Account</h3>
            <button type="button" class="kbf-modal-close" onclick="kbfCloseVerifyModal()">&times;</button>
          </div>
          <div class="kbf-modal-body">
            <div style="background:var(--kbf-slate-lt);border-radius:10px;padding:12px 14px;font-size:12.5px;color:var(--kbf-text-sm);margin-bottom:12px;">
              <div style="font-weight:700;color:var(--kbf-navy);margin-bottom:6px;">ID Photo Guidelines</div>
              <div>Do:</div>
              <ul style="margin:6px 0 8px 18px;padding:0;">
                <li>Use good lighting (not too dark or too bright)</li>
                <li>Make sure all corners are visible</li>
                <li>Text and photo must be readable</li>
              </ul>
              <div>Don’t:</div>
              <ul style="margin:6px 0 0 18px;padding:0;">
                <li>Cover any part of the ID</li>
                <li>Use blurry or cropped photos</li>
              </ul>
            </div>
            <form id="kbf-verify-form" enctype="multipart/form-data" onsubmit="return false;">
              <div class="kbf-form-group">
                <label>Valid ID (Front) *</label>
                <input type="file" name="verify_id_front" accept="image/*" required>
              </div>
              <div class="kbf-form-group">
                <label>Valid ID (Back) *</label>
                <input type="file" name="verify_id_back" accept="image/*" required>
              </div>
            </form>
            <div id="kbf-verify-msg" style="margin-top:8px;"></div>
          </div>
          <div class="kbf-modal-footer">
            <button type="button" class="kbf-btn kbf-btn-secondary" onclick="kbfCloseVerifyModal()">Cancel</button>
            <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSubmitVerification('<?php echo $nonce_verify; ?>')">Submit Verification</button>
          </div>
        </div>
      </div>
    </div>    
    
    <!-- ================== JS ================== -->    <script>
    if (typeof ajaxurl === 'undefined') {
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    }
    window.kbfPhotoClick = function(){
        var input = document.getElementById('kbf-avatar');
        if (input) {
            input.click();
        } else {
            console.error('kbfPhotoClick: #kbf-avatar not found');
        }
    };

    window.kbfTogglePayoutNumber = function(){
        const input = document.getElementById('kbf-payout-number');
        const btn = document.querySelector('.kbf-toggle-visibility');
        if (!input) return;
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        if (btn) btn.textContent = isHidden ? 'Hide' : 'Show';
    };

    window.kbfProfileBioCount = function(){
        var ta = document.getElementById('kbf-profile-bio');
        var out = document.getElementById('kbf-profile-bio-count');
        if (!ta || !out) return;
        out.textContent = (ta.value || '').length + ' / 300';
    };

    document.addEventListener('DOMContentLoaded', function(){
        var wrap = document.getElementById('kbf-photo-wrap');
        if (wrap) {
            wrap.addEventListener('click', function(e){
                e.preventDefault();
                window.kbfPhotoClick();
            });
        }
        window.kbfProfileBioCount();
    });

    document.addEventListener('input', function(e){
        if (e.target && e.target.id === 'kbf-profile-bio') {
            window.kbfProfileBioCount();
        }
    });

    (function(){
        const fileInput  = document.getElementById('kbf-avatar');
        const backdrop   = document.getElementById('kbf-cropper-backdrop');
        const stage      = document.getElementById('kbf-cropper-stage');
        const img        = document.getElementById('kbf-cropper-image');
        const zoom       = document.getElementById('kbf-cropper-zoom');
        const btnCancel  = document.getElementById('kbf-cropper-cancel');
        const btnClose   = document.getElementById('kbf-cropper-close');
        const btnApply   = document.getElementById('kbf-cropper-apply');
        if (!fileInput || !backdrop || !stage || !img || !zoom || !btnCancel || !btnApply) return;

        let naturalW = 0, naturalH = 0;
        let posX = 0, posY = 0;
        let scale = 1;
        let dragging = false;
        let startX = 0, startY = 0;

        function openCropper(file) {
            const url = URL.createObjectURL(file);
            backdrop.style.display = 'flex';
            img.onload = function(){
                naturalW = img.naturalWidth;
                naturalH = img.naturalHeight;
                requestAnimationFrame(function(){
                    const stageW = stage.clientWidth || 240;
                    const stageH = stage.clientHeight || 240;
                    const baseScale = Math.max(stageW / naturalW, stageH / naturalH);
                    scale = baseScale;
                    zoom.value = 1;
                    posX = (stageW - naturalW * scale) / 2;
                    posY = (stageH - naturalH * scale) / 2;
                    applyTransform();
                });
            };
            img.src = url;
        }

        function applyTransform() {
            img.style.transform = 'translate(' + posX + 'px, ' + posY + 'px) scale(' + scale + ')';
        }

        function clampPosition() {
            const stageW = stage.clientWidth;
            const stageH = stage.clientHeight;
            const imgW = naturalW * scale;
            const imgH = naturalH * scale;
            posX = Math.min(0, Math.max(stageW - imgW, posX));
            posY = Math.min(0, Math.max(stageH - imgH, posY));
        }

        fileInput.addEventListener('change', function(){
            if (!fileInput.files || !fileInput.files[0]) return;
            openCropper(fileInput.files[0]);
        });

        stage.addEventListener('mousedown', function(e){
            dragging = true;
            stage.classList.add('is-dragging');
            startX = e.clientX - posX;
            startY = e.clientY - posY;
        });
        window.addEventListener('mouseup', function(){ dragging = false; stage.classList.remove('is-dragging'); });
        window.addEventListener('mousemove', function(e){
            if (!dragging) return;
            posX = e.clientX - startX;
            posY = e.clientY - startY;
            clampPosition();
            applyTransform();
        });

        zoom.addEventListener('input', function(){
            const stageW = stage.clientWidth;
            const stageH = stage.clientHeight;
            const baseScale = Math.max(stageW / naturalW, stageH / naturalH);
            scale = baseScale * parseFloat(zoom.value);
            clampPosition();
            applyTransform();
        });

        btnCancel.addEventListener('click', function(){ backdrop.style.display = 'none'; });
        if (btnClose) {
            btnClose.addEventListener('click', function(){ backdrop.style.display = 'none'; });
        }

        btnApply.addEventListener('click', function(){
            const size = 400;
            const canvas = document.createElement('canvas');
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');
            const stageW = stage.clientWidth;
            const stageH = stage.clientHeight;
            const srcX = Math.max(0, (-posX) / scale);
            const srcY = Math.max(0, (-posY) / scale);
            const srcW = Math.min(naturalW, stageW / scale);
            const srcH = Math.min(naturalH, stageH / scale);
            ctx.drawImage(img, srcX, srcY, srcW, srcH, 0, 0, size, size);
            canvas.toBlob(function(blob){
                if (!blob) return;
                const file = new File([blob], 'profile.jpg', {type: 'image/jpeg'});
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
                const preview = document.querySelector('.kbf-profile-photo');
                if (preview) preview.src = URL.createObjectURL(blob);
                backdrop.style.display = 'none';
                const form = document.getElementById('kbf-profile-form');
                if (form) {
                    const nonce = form.getAttribute('data-nonce');
                    if (nonce && window.kbfSaveProfile) {
                        window.kbfSaveProfile(nonce);
                    }
                }
            }, 'image/jpeg', 0.92);
        });
    })();

    
    window.kbfOpenVerifyModal = function(){
        var m = document.getElementById('kbf-modal-verify-account');
        if(m) m.style.display = 'flex';
    };
    window.kbfCloseVerifyModal = function(){
        var m = document.getElementById('kbf-modal-verify-account');
        if(m) m.style.display = 'none';
    };
    window.kbfSubmitVerification = function(nonce){
        var form = document.getElementById('kbf-verify-form');
        var msg = document.getElementById('kbf-verify-msg');
        if(!form) return;
        var fd = new FormData(form);
        fd.append('action','kbf_request_verification');
        fd.append('nonce',nonce);
        console.log('kbfSubmitVerification: posting to', ajaxurl);
        fetch(ajaxurl,{method:'POST',body:fd}).then(async function(r){
            const text = await r.text();
            console.log('kbfSubmitVerification: raw response', text);
            const cleaned = text.replace(/^\uFEFF/, '').trim();
            try { return JSON.parse(cleaned); } catch(e){ console.error('kbfSubmitVerification: JSON parse failed', e, cleaned); throw e; }
        }).then(function(j){
            msg.innerHTML = '<div class="kbf-alert kbf-alert-' + (j.success?'success':'error') + '">' + j.data.message + '</div>';
            if(j.success){
                var btn = document.querySelector('.kbf-profile-actions .kbf-btn-secondary');
                if (btn) { btn.disabled = true; btn.textContent = 'Pending'; }
                var tag = document.querySelector('.kbf-profile-verify-tag');
                if (tag) { tag.textContent = 'Pending Review'; }
                kbfCloseVerifyModal();
            }
        }).catch(function(){
            console.error('kbfSubmitVerification: request failed');
            msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Upload failed. Please try again.</div>';
        });
    };

window.kbfSaveProfile = function(nonce) {
        const form = document.getElementById('kbf-profile-form');
        const fd = new FormData(form);
        fd.append('action', 'kbf_save_organizer_profile');
        fd.append('nonce', nonce);
        const btn = form.querySelector('.kbf-btn-primary');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        fetch(ajaxurl, {method: 'POST', body: fd})
            .then(async function(r){
                const text = await r.text();
                try {
                    const cleaned = text.replace(/^\uFEFF/, '').trim();
                    return JSON.parse(cleaned);
                } catch(e) {
                    console.error('kbfSaveProfile: JSON parse failed', e, text);
                    throw e;
                }
            })
            .then(function(j){
                document.getElementById('kbf-profile-msg').innerHTML =
                    '<div class="kbf-alert kbf-alert-' + (j.success ? 'success' : 'error') + '">' + j.data.message + '</div>';
            if (j && j.success) {
                // No full-page reload; keep the user on the form.
            }
            })
            .catch(function(err){
                console.error('kbfSaveProfile: request failed', err);
                document.getElementById('kbf-profile-msg').innerHTML =
                    '<div class="kbf-alert kbf-alert-error">Save failed. Please try again.</div>';
            })
            .finally(function(){
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            });
    };
    </script>
    <?php return ob_get_clean();
}
