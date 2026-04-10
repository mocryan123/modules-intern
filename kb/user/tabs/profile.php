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
    $profile_value = function($key, $default = '') use ($profile) {
        return ($profile && isset($profile->$key)) ? $profile->$key : $default;
    };
    $payout_type = $profile_value('payout_type', '');
    $payout_name = $profile_value('payout_name', '');
    $payout_number = $profile_value('payout_number', '');
    $phone = get_user_meta($business_id, 'kbf_phone', true);
    $address = get_user_meta($business_id, 'kbf_address', true);
    $nonce = wp_create_nonce('kbf_organizer_profile');
    $didit_status = get_user_meta($business_id, 'fundora_didit_verification_status', true);
    $stats_total_raised = (float) $profile_value('total_raised', 0);
    $stats_total_sponsors = (int) $profile_value('total_sponsors', 0);
    $stats_rating = (float) $profile_value('rating', 0);
    $stats_rating_count = (int) $profile_value('rating_count', 0);

    ob_start();
    ?>
    <!-- ================== CSS ================== -->
    <style>
      .kbf-section {
        width: 100%;
        box-sizing: border-box;
        overflow: visible;
      }
      .kbf-section form {
        width: 100%;
        box-sizing: border-box;
      }
      .kbf-profile-title { margin-bottom: 6px; }
      .kbf-profile-subtitle { margin: 0 0 18px; color: var(--kbf-slate); font-size: 13px; }
      .kbf-section-title{
        font-size:16px;
        font-weight:600 !important;
        color:#0f172a;
        margin:0 0 6px;
        line-height:1.35;
      }
      .kbf-profile-card .kbf-select-display span{
        display:block;
        min-width:0;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
      }
      .kbf-select .kbf-select-display{
        padding:9px 9px;
      }

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
      .kbf-profile-card.kbf-card{
        box-shadow: none;
        border: 1px solid var(--kbf-border);
        transform: none;
      }
      .kbf-profile-card.kbf-card:hover{
        box-shadow: none;
        transform: none;
      }
      .kbf-profile-card-left {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: center;
        text-align: left;
      }

      /* ── Card header ── */
      .kbf-profile-header { display: flex; justify-content: space-between; align-items: center; width: 100%; }
      .kbf-profile-card-title { font-size:16px; font-weight:600; color:#0f172a; margin:0 0 6px; line-height:1.35; }

      /* ── Avatar ── */
      .kbf-profile-photo {
        width: 190px;
        height: 190px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid var(--kbf-border);
        background: #fff;
        position: relative;
        z-index: 1;
      }
      .kbf-profile-photo-fallback{
        width: 190px;
        height: 190px;
        border-radius: 50%;
        border: 1px solid var(--kbf-border);
        background: var(--kbf-navy);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
      }
      .kbf-profile-photo-fallback img{filter:invert(100%);width:40px;height:40px;}
      .kbf-photo-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border-radius: 50%;
        overflow: hidden;
      }
      .kbf-photo-overlay {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(15,23,42,.45);
        opacity: 0;
        visibility: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity .2s ease, visibility .2s ease;
        pointer-events: none;
        z-index: 2;
        text-align: center;
      }
      .kbf-photo-wrap:hover .kbf-photo-overlay,
      .kbf-photo-wrap:focus-within .kbf-photo-overlay { opacity: 1; visibility: visible; }
      .kbf-photo-edit{
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:6px;
        color:#fff;
        opacity:0;
        transform: scale(0.98);
        transition: opacity .2s ease, transform .2s ease;
        padding:0 10px;
        width:100%;
        text-align:center;
        position:absolute;
        left:50%;
        top:50%;
        transform: translate(-50%, -50%) scale(0.98);
      }
      .kbf-photo-edit-icon{
        width:36px;height:36px;border-radius:50%;
        background: transparent;
        display:flex;align-items:center;justify-content:center;
        box-shadow:none;
      }
      .kbf-photo-edit-icon img{display:block;width:18px;height:18px;filter:invert(100%);}
      .kbf-photo-edit-text{
        font-size:12px;
        font-weight:600;
        letter-spacing:.01em;
        text-transform:none;
      }
      .kbf-photo-wrap:hover .kbf-photo-edit,
      .kbf-photo-wrap:focus-within .kbf-photo-edit { opacity: 1; transform: translate(-50%, -50%) scale(1); }
      .kbf-user-ui .kbf-profile-card-left .kbf-photo-edit{
        left:50% !important;
        top:50% !important;
        right:auto !important;
        bottom:auto !important;
        transform: translate(-50%, -50%) scale(0.98) !important;
      }
      .kbf-user-ui .kbf-profile-card-left .kbf-photo-wrap:hover .kbf-photo-edit,
      .kbf-user-ui .kbf-profile-card-left .kbf-photo-wrap:focus-within .kbf-photo-edit{
        transform: translate(-50%, -50%) scale(1) !important;
      }

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
      .kbf-payout-desc{
        margin-top:6px;
        font-size:12px;
        color:var(--kbf-slate);
        line-height:1.5;
      }
      .kbf-payout-hint{
        margin-top:8px;
        font-size:12.5px;
        color:var(--kbf-slate);
      }
      .kbf-inline-preload{
        position:fixed;
        inset:0;
        z-index:99999;
        display:flex;
        align-items:center;
        justify-content:center;
        background:rgba(248,250,252,0.78);
        backdrop-filter: blur(8px);
        transition:opacity .35s ease, visibility .35s ease;
      }
      .kbf-inline-preload.kbf-preload-hide{
        opacity:0;
        visibility:hidden;
        pointer-events:none;
      }
      html.kbf-preload-lock,
      body.kbf-preload-lock{
        overflow:hidden !important;
        height:100%;
      }
      .kbf-inline-preload-mark{
        width:54px;
        height:54px;
        border-radius:14px;
        background:linear-gradient(135deg, #5ba8f5, #3d8ef0);
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:#ffffff;
        font-weight:800;
        letter-spacing:.6px;
        box-shadow:0 8px 18px rgba(61,142,240,.2);
        overflow:hidden;
        animation:kbfpreloadjump 1.2s cubic-bezier(.34,1.2,.64,1) infinite;
      }
      .kbf-inline-preload-mark img{
        width:26px;height:26px;object-fit:contain;display:block;
        filter:brightness(0) invert(1);
      }
      .kbf-input-error{
        border-color:#dc2626 !important;
        box-shadow:0 0 0 3px rgba(220,38,38,.12);
      }
      .kbf-field-error{
        display:none;
        margin-top:6px;
        color:#dc2626;
        font-size:11.5px;
        font-weight:600;
      }

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
      .kbf-profile-bio textarea {
        width: 100%;
        box-sizing: border-box;
        min-height: 180px;
        border-radius: 10px;
        border: 1.5px solid var(--kbf-border);
        padding: 12px 14px;
        font-size: 13.5px;
        line-height: 1.6;
        color: var(--kbf-text);
        background: #fff;
        font-family: inherit;
        resize: none;
      }
      .kbf-profile-bio textarea:focus{
        outline: none;
        border-color: var(--kbf-navy-light);
        box-shadow: none;
      }
      .kbf-profile-bio textarea::placeholder{
        color: #8aa0b8;
        font-weight: 400;
        font-family: inherit;
      }
      .kbf-profile-bio .kbf-char-count{
        margin-top: 6px;
        font-size: 11.5px;
        color: #4f5a6b;
      }
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
      .kbf-stat-label { font-size: 11px; font-weight: 600; color: #64748b; letter-spacing: .5px; }
      .kbf-profile-card .kbf-stat-value { font-size: 16px; font-weight: 600; color: #0f172a; }
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
              <div class="kbf-profile-card-title" style="margin:0;">My profile</div>
              <div class="kbf-profile-meta">Last update: <?php echo esc_html($profile && $profile->updated_at ? date('M d, Y', strtotime($profile->updated_at)) : 'Recently'); ?></div>
            </div>

            <?php
              $avatar = $profile && $profile->avatar_url ? $profile->avatar_url : '';
            ?>
            <label class="kbf-photo-wrap" for="kbf-avatar" id="kbf-photo-wrap">
              <?php if($avatar): ?>
                <img src="<?php echo esc_url($avatar); ?>" alt="Profile photo" class="kbf-profile-photo">
              <?php else: ?>
                <div class="kbf-profile-photo-fallback" aria-hidden="true">
                  <i class="ph ph-user kbf-icon" aria-hidden="true"></i>
                </div>
              <?php endif; ?>
              <div class="kbf-photo-overlay">
                <div class="kbf-photo-edit" aria-hidden="true">
                  <div class="kbf-photo-edit-icon">
                    <i class="ph ph-camera kbf-icon" aria-hidden="true"></i>
                  </div>
                  <div class="kbf-photo-edit-text">Change Photo</div>
                </div>
              </div>
            </label>
            <input id="kbf-avatar" class="kbf-file-input" type="file" name="avatar" accept="image/*">
            <div class="kbf-profile-meta kbf-profile-note">Recommended 800x800px JPG or PNG.</div>
              <div class="kbf-profile-verify-wrap" style="margin-bottom:20px">
                <?php if($didit_status === 'Approved'): ?>
                  <div class="kbf-profile-verify-tag kbf-verified">Verified</div>
                <?php elseif($didit_status === 'In Review'): ?>
                  <div class="kbf-profile-verify-tag kbf-not-verified">Pending Verification</div>
                <?php elseif($didit_status === 'Declined'): ?>
                  <div class="kbf-profile-verify-tag kbf-not-verified">Verification Failed</div>
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
              <textarea id="kbf-profile-bio" name="bio" rows="10" maxlength="250" placeholder="Tell sponsors about yourself or your account..."><?php echo esc_textarea(isset($profile->bio) ? str_replace('\\', '', wp_unslash($profile->bio)) : ''); ?></textarea>
              <div class="kbf-char-count" id="kbf-profile-bio-count">0 / 250</div>
            </div>

            <div class="kbf-form-group">
              <label>Email</label>
              <input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled style="background:var(--kbf-slate-lt);">
            </div>

            <div class="kbf-form-group">
              <label>Phone</label>
              <input type="text" name="phone" value="<?php echo esc_attr($phone); ?>" placeholder="+63 9XX XXX XXXX">
            </div>

              <div class="kbf-profile-divider"></div>
              <div class="kbf-profile-actions">
                <?php if($didit_status === 'Approved'): ?>
                  <button type="button" class="kbf-btn kbf-btn-secondary" disabled>Verified</button>
                <?php elseif($didit_status === 'In Review'): ?>
                  <button type="button" class="kbf-btn kbf-btn-secondary" disabled>Pending Verification</button>
                <?php else: ?>
                  <button type="button" class="kbf-btn kbf-btn-secondary" id="fundora-didit-start">Verify Account</button>
                <?php endif; ?>
                <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSaveProfile('<?php echo $nonce; ?>')">Save Changes</button>
              </div>
              <div id="fundora-didit-msg" style="margin-top:10px;"></div>
            </div>

          <!-- ══ RIGHT STACK ══ -->
          <div class="kbf-profile-stack">

            <!-- Payout Details -->
            <div class="kbf-card kbf-profile-card">
              <div class="kbf-profile-card-title">Payout Details</div>
              <div class="kbf-form-row kbf-form-row-3">
                <div class="kbf-form-group">
                  <label>Account Type</label>
                  <select name="payout_type" id="kbf-payout-type" autocomplete="off">
                    <option value="" data-desc="Select a payout type to continue." <?php echo $payout_type===''?'selected':''; ?>>Select type</option>
                    <option value="maya_wallet" data-desc="Maya Wallet payouts are sent to the mobile number linked to your Maya account." <?php echo $payout_type==='maya_wallet'?'selected':''; ?>>Maya Wallet</option>
                    <option value="gcash" data-desc="GCash payouts are sent to the mobile number linked to your GCash account." <?php echo $payout_type==='gcash'?'selected':''; ?>>GCash</option>
                    <option value="card" data-desc="Card payouts use your cardholder name and card number. Ensure the card can receive payouts." <?php echo $payout_type==='card'?'selected':''; ?>>Credit/Debit Card</option>
                  </select>
                  <div class="kbf-payout-desc" id="kbf-payout-desc"></div>
                </div>
                <div class="kbf-form-group" id="kbf-payout-name-group">
                  <label id="kbf-payout-name-label">Account Name</label>
                  <input type="text" name="payout_name" id="kbf-payout-name" value="<?php echo esc_attr($payout_name); ?>" placeholder="Account name" autocomplete="off" autocapitalize="none" spellcheck="false" <?php echo $payout_type===''?'disabled':''; ?>>
                </div>
                <div class="kbf-form-group" id="kbf-payout-number-group">
                  <label id="kbf-payout-number-label">Account Number</label>
                  <div class="kbf-input-with-toggle">
                    <input type="password" name="payout_number" id="kbf-payout-number" value="<?php echo esc_attr($payout_number); ?>" placeholder="Account number" autocomplete="new-password" autocapitalize="none" spellcheck="false" <?php echo $payout_type===''?'disabled':''; ?>>
                    <button type="button" class="kbf-toggle-visibility" onclick="kbfTogglePayoutNumber()">Show</button>
                  </div>
                </div>
              </div>
              <div class="kbf-payout-hint">We’ll use this for payouts. Double‑check your details to avoid delays.</div>
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
              <div class="kbf-stats kbf-stats-grid">
                <div class="kbf-stat kbf-stat-card">
                  <div class="kbf-stat-icon">
                    <i class="ph ph-piggy-bank kbf-icon" style="font-size:16px" aria-hidden="true"></i>
                  </div>
                  <div>
                    <div class="kbf-stat-label">TOTAL RAISED</div>
                    <div class="kbf-stat-value">&#8369;<?php echo number_format($stats_total_raised, 0); ?></div>
                  </div>
                </div>
                <div class="kbf-stat kbf-stat-card">
                  <div class="kbf-stat-icon">
                    <i class="ph ph-users kbf-icon" style="font-size:16px" aria-hidden="true"></i>
                  </div>
                  <div>
                    <div class="kbf-stat-label">TOTAL SPONSORS</div>
                    <div class="kbf-stat-value"><?php echo number_format($stats_total_sponsors); ?></div>
                  </div>
                </div>
                <div class="kbf-stat kbf-stat-card">
                  <div class="kbf-stat-icon">
                    <i class="ph-fill ph-thumbs-up kbf-stat-icon-img kbf-icon" style="font-size:16px" aria-hidden="true"></i>
                  </div>
                  <div>
                    <div class="kbf-stat-label">CREDIBILITY</div>
                    <div class="kbf-stat-value"><?php echo number_format($stats_rating, 1); ?>/5 (<?php echo $stats_rating_count; ?>)</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Social Links -->
            <div class="kbf-card kbf-profile-card">
              <div class="kbf-profile-card-title">Social Links</div>
              <div class="kbf-form-row kbf-form-row-2">
                <div class="kbf-form-group">
                  <label>Facebook</label>
                  <input type="url" name="social_facebook" id="kbf-social-facebook" placeholder="https://facebook.com/yourname" value="<?php echo esc_attr($socials['facebook'] ?? ''); ?>">
                  <div class="kbf-field-error" id="kbf-social-facebook-error"></div>
                </div>
                <div class="kbf-form-group">
                  <label>Instagram</label>
                  <input type="url" name="social_instagram" id="kbf-social-instagram" placeholder="https://instagram.com/yourname" value="<?php echo esc_attr($socials['instagram'] ?? ''); ?>">
                  <div class="kbf-field-error" id="kbf-social-instagram-error"></div>
                </div>
              </div>
              <div class="kbf-form-row kbf-form-row-2">
                <div class="kbf-form-group">
                  <label>X (Twitter)</label>
                  <input type="url" name="social_twitter" id="kbf-social-twitter" placeholder="https://x.com/yourname" value="<?php echo esc_attr($socials['twitter'] ?? ''); ?>">
                  <div class="kbf-field-error" id="kbf-social-twitter-error"></div>
                </div>
                <div class="kbf-form-group">
                  <label>Website</label>
                  <input type="url" name="social_website" id="kbf-social-website" placeholder="https://yourwebsite.com" value="<?php echo esc_attr($socials['website'] ?? ''); ?>">
                </div>
              </div>
              <div class="kbf-meta" style="font-size:12.5px;">These links will appear on your public profile.</div>
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

    </div>    
    
      <!-- ================== JS ================== -->    <?php ?>
      <script>
      window.fundoraDidit = {
          ajaxurl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
          nonce: '<?php echo esc_js(wp_create_nonce('fundora_didit_nonce')); ?>'
      };
      </script>
    <script>
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

window.kbfTogglePayoutInputs = function(){
    const typeSel = document.getElementById('kbf-payout-type');
    const nameEl = document.getElementById('kbf-payout-name');
    const numEl = document.getElementById('kbf-payout-number');
    const nameLabel = document.getElementById('kbf-payout-name-label');
    const numLabel = document.getElementById('kbf-payout-number-label');
    const descEl = document.getElementById('kbf-payout-desc');
    if (!typeSel || !nameEl || !numEl) return;
    const enabled = !!typeSel.value;
    const typeVal = typeSel.value || '';
    nameEl.disabled = !enabled;
    numEl.disabled = !enabled;

    if (descEl) {
        const opt = typeSel.options[typeSel.selectedIndex];
        const desc = opt && opt.getAttribute('data-desc') ? opt.getAttribute('data-desc') : '';
        descEl.textContent = desc;
    }

    if (!enabled) {
        if (nameLabel) nameLabel.textContent = 'Account Name';
        if (numLabel) numLabel.textContent = 'Account Number';
        if (nameEl) nameEl.placeholder = 'Account name';
        if (numEl) {
            numEl.placeholder = 'Account number';
            numEl.removeAttribute('inputmode');
        }
        return;
    }

    if (typeVal === 'card') {
        if (nameLabel) nameLabel.textContent = 'Cardholder Name';
        if (numLabel) numLabel.textContent = 'Card Number';
        if (nameEl) nameEl.placeholder = 'Name on card';
        if (numEl) {
            numEl.placeholder = 'XXXX XXXX XXXX XXXX';
            numEl.setAttribute('inputmode', 'numeric');
        }
    } else {
        if (nameLabel) nameLabel.textContent = 'Account Name';
        if (numLabel) numLabel.textContent = 'Mobile Number';
        if (nameEl) nameEl.placeholder = 'Account name';
        if (numEl) {
            numEl.placeholder = '09XXXXXXXXX';
            numEl.setAttribute('inputmode', 'numeric');
        }
    }
};

function kbfProfileTitleCase(str){
    return String(str).toLowerCase().replace(/\b\w/g,function(c){return c.toUpperCase();});
}
function kbfProfileSetMuniOptions(muniEl, list){
    if (!muniEl) return;
    muniEl.innerHTML = '<option value="">Select Municipality</option>';
    for (var i=0;i<list.length;i++){
        var opt = document.createElement('option');
        opt.value = list[i].label;
        opt.textContent = list[i].label;
        muniEl.appendChild(opt);
    }
    if (window.kbfRefreshSelect) window.kbfRefreshSelect(muniEl);
}
function kbfProfileSetBrgyOptions(brgyEl, list){
    if (!brgyEl) return;
    brgyEl.innerHTML = '<option value="">Select Barangay</option>';
    for (var i=0;i<list.length;i++){
        var opt = document.createElement('option');
        opt.value = list[i];
        opt.textContent = list[i];
        brgyEl.appendChild(opt);
    }
    if (window.kbfRefreshSelect) window.kbfRefreshSelect(brgyEl);
}
var kbfProfilePsgcData = null;
var kbfProfilePsgcLoading = false;
function kbfProfileEnsurePsgc(cb){
    if (kbfProfilePsgcData){ cb(); return; }
    if (kbfProfilePsgcLoading) return;
    kbfProfilePsgcLoading = true;
    fetch('<?php echo esc_url(BNTM_KBF_URL . 'data/psgc_2016.json'); ?>')
      .then(function(r){ return r.json(); })
      .then(function(j){ kbfProfilePsgcData = j; cb(); })
      .catch(function(){ kbfProfilePsgcData = null; })
      .finally(function(){ kbfProfilePsgcLoading = false; });
}
function kbfProfileBuildMunicipalities(provinceUpper){
    var out = [];
    if (!kbfProfilePsgcData) return out;
    for (var regionKey in kbfProfilePsgcData){
        if (!kbfProfilePsgcData.hasOwnProperty(regionKey)) continue;
        var provList = kbfProfilePsgcData[regionKey].province_list || {};
        if (provList[provinceUpper]) {
            var munList = provList[provinceUpper].municipality_list || [];
            for (var i=0;i<munList.length;i++){
                var obj = munList[i];
                for (var muniName in obj){
                    if (obj.hasOwnProperty(muniName)){
                        var barangays = obj[muniName].barangay_list || [];
                        var brgyList = [];
                        for (var b=0;b<barangays.length;b++){
                            brgyList.push(kbfProfileTitleCase(barangays[b]));
                        }
                        out.push({ key: muniName, label: kbfProfileTitleCase(muniName), barangays: brgyList });
                    }
                }
            }
            break;
        }
    }
    return out;
}
function kbfProfileInitLocationPicker(provinceEl, muniEl, brgyEl, hiddenEl){
    if (!provinceEl || !muniEl || !brgyEl) return;
    var muniData = [];
    function updateHidden(){
        if (!hiddenEl) return;
        var prov = provinceEl.value || '';
        var muni = muniEl.value || '';
        var brgy = brgyEl.value || '';
        var parts = [];
        if (brgy) parts.push(brgy);
        if (muni) parts.push(muni);
        if (prov) parts.push(prov);
        hiddenEl.value = parts.join(', ');
    }
    function handleProvinceChange(){
        var val = provinceEl.value || '';
        if (!val){
            muniEl.disabled = true;
            brgyEl.disabled = true;
            kbfProfileSetMuniOptions(muniEl, []);
            kbfProfileSetBrgyOptions(brgyEl, []);
            if (window.kbfRefreshSelect) { window.kbfRefreshSelect(muniEl); window.kbfRefreshSelect(brgyEl); }
            updateHidden();
            return;
        }
        muniEl.disabled = true;
        brgyEl.disabled = true;
        kbfProfileSetMuniOptions(muniEl, []);
        kbfProfileSetBrgyOptions(brgyEl, []);
        if (window.kbfRefreshSelect) { window.kbfRefreshSelect(muniEl); window.kbfRefreshSelect(brgyEl); }
        kbfProfileEnsurePsgc(function(){
            muniData = kbfProfileBuildMunicipalities(String(val).toUpperCase());
            kbfProfileSetMuniOptions(muniEl, muniData);
            muniEl.disabled = muniData.length === 0;
            if (window.kbfRefreshSelect) window.kbfRefreshSelect(muniEl);
        });
        updateHidden();
    }
    function handleMunicipalityChange(){
        var val = muniEl.value || '';
        if (!val){
            brgyEl.disabled = true;
            kbfProfileSetBrgyOptions(brgyEl, []);
            if (window.kbfRefreshSelect) window.kbfRefreshSelect(brgyEl);
            updateHidden();
            return;
        }
        var upperVal = String(val).toUpperCase();
        var found = null;
        for (var i=0;i<muniData.length;i++){
            if (muniData[i].key === upperVal){
                found = muniData[i];
                break;
            }
        }
        if (!found){
            brgyEl.disabled = true;
            kbfProfileSetBrgyOptions(brgyEl, []);
            if (window.kbfRefreshSelect) window.kbfRefreshSelect(brgyEl);
            updateHidden();
            return;
        }
        kbfProfileSetBrgyOptions(brgyEl, found.barangays);
        brgyEl.disabled = found.barangays.length === 0;
        if (window.kbfRefreshSelect) window.kbfRefreshSelect(brgyEl);
        updateHidden();
    }
    provinceEl.addEventListener('change', handleProvinceChange);
    muniEl.addEventListener('change', handleMunicipalityChange);
    brgyEl.addEventListener('change', updateHidden);
    handleProvinceChange();
    return { handleProvinceChange: handleProvinceChange, handleMunicipalityChange: handleMunicipalityChange };
}
function kbfProfileApplyLocationSelection(provinceEl, muniEl, brgyEl, loc){
    if (!provinceEl || !muniEl || !brgyEl) return;
    var parts = String(loc || '').split(',').map(function(p){ return p.trim(); }).filter(Boolean);
    var barangay = parts.length > 0 ? parts[0] : '';
    var municipality = parts.length > 1 ? parts[1] : '';
    var province = parts.length > 2 ? parts[2] : (parts.length === 1 ? parts[0] : (parts.length === 2 ? parts[1] : ''));
    provinceEl.value = province;
    if (!province) {
        muniEl.disabled = true; brgyEl.disabled = true;
        kbfProfileSetMuniOptions(muniEl, []); kbfProfileSetBrgyOptions(brgyEl, []);
        if (window.kbfRefreshSelect) { window.kbfRefreshSelect(muniEl); window.kbfRefreshSelect(brgyEl); }
        return;
    }
    kbfProfileEnsurePsgc(function(){
        var muniData = kbfProfileBuildMunicipalities(String(province).toUpperCase());
        kbfProfileSetMuniOptions(muniEl, muniData);
        muniEl.disabled = muniData.length === 0;
        if (window.kbfRefreshSelect) window.kbfRefreshSelect(muniEl);
        if (municipality) muniEl.value = municipality;
        var upperVal = String(muniEl.value || '').toUpperCase();
        var found = null;
        for (var i=0;i<muniData.length;i++){
            if (muniData[i].key === upperVal){
                found = muniData[i];
                break;
            }
        }
        if (found){
            kbfProfileSetBrgyOptions(brgyEl, found.barangays);
            brgyEl.disabled = found.barangays.length === 0;
            if (window.kbfRefreshSelect) window.kbfRefreshSelect(brgyEl);
            if (barangay) brgyEl.value = barangay;
        } else {
            brgyEl.disabled = true;
            kbfProfileSetBrgyOptions(brgyEl, []);
            if (window.kbfRefreshSelect) window.kbfRefreshSelect(brgyEl);
        }
    });
}

document.addEventListener('DOMContentLoaded', function(){
    const typeSel = document.getElementById('kbf-payout-type');
    if (typeSel) {
        typeSel.addEventListener('change', kbfTogglePayoutInputs);
        kbfTogglePayoutInputs();
    }

    var prov = document.getElementById('kbf-profile-province');
    var muni = document.getElementById('kbf-profile-municipality');
    var brgy = document.getElementById('kbf-profile-barangay');
    var addr = document.getElementById('kbf-profile-address');
    if (prov && muni && brgy) {
        kbfProfileInitLocationPicker(prov, muni, brgy, addr);
        if (addr && addr.value) {
            kbfProfileApplyLocationSelection(prov, muni, brgy, addr.value);
        }
    }
});

    window.kbfProfileBioCount = function(){
        var ta = document.getElementById('kbf-profile-bio');
        var out = document.getElementById('kbf-profile-bio-count');
        if (!ta || !out) return;
        out.textContent = (ta.value || '').length + ' / 250';
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

    window.kbfValidateSocialLinks = function(){
        var rules = [
            { id: 'kbf-social-facebook',  err: 'kbf-social-facebook-error',  re: /^(https?:\/\/)?(www\.)?(facebook\.com|fb\.me)\/[A-Za-z0-9._%\-/?=&#]+$/i, msg: 'Please enter a valid Facebook profile URL.' },
            { id: 'kbf-social-instagram', err: 'kbf-social-instagram-error', re: /^(https?:\/\/)?(www\.)?instagram\.com\/[A-Za-z0-9._-]+\/?$/i,          msg: 'Please enter a valid Instagram profile URL.' },
            { id: 'kbf-social-twitter',   err: 'kbf-social-twitter-error',   re: /^(https?:\/\/)?(www\.)?(x\.com|twitter\.com)\/[A-Za-z0-9._-]+\/?$/i,    msg: 'Please enter a valid X (Twitter) profile URL.' }
        ];
        var ok = true;
        rules.forEach(function(rule){
            var input = document.getElementById(rule.id);
            var error = document.getElementById(rule.err);
            if (!input || !error) return;
            var val = (input.value || '').trim();
            if (val && !rule.re.test(val)) {
                input.classList.add('kbf-input-error');
                error.textContent = rule.msg;
                error.style.display = 'block';
                ok = false;
            } else {
                input.classList.remove('kbf-input-error');
                error.textContent = '';
                error.style.display = 'none';
            }
        });
        return ok;
    };

    document.addEventListener('blur', function(e){
        if (!e.target) return;
        if (['kbf-social-facebook','kbf-social-instagram','kbf-social-twitter'].indexOf(e.target.id) > -1) {
            window.kbfValidateSocialLinks();
        }
    }, true);

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
                // Do not auto-save on photo change; wait for "Save Changes"
            }, 'image/jpeg', 0.92);
        });
    })();

    (function(){
        var btn = document.getElementById('fundora-didit-start');
        var msg = document.getElementById('fundora-didit-msg');
        if (!btn) return;
        var COOLDOWN_MAX_ATTEMPTS = 3;
        var COOLDOWN_MS = 5 * 60 * 1000; // 5 minutes
        var COOLDOWN_KEY = 'kbf_verify_cooldown';
        var COOLDOWN_COUNT_KEY = 'kbf_verify_attempts';
        var cooldownTimer = null;

        function getCooldownUntil(){
            var raw = localStorage.getItem(COOLDOWN_KEY);
            var val = raw ? parseInt(raw, 10) : 0;
            return isNaN(val) ? 0 : val;
        }
        function setCooldownUntil(ts){
            localStorage.setItem(COOLDOWN_KEY, String(ts));
        }
        function getAttempts(){
            var raw = localStorage.getItem(COOLDOWN_COUNT_KEY);
            var val = raw ? parseInt(raw, 10) : 0;
            return isNaN(val) ? 0 : val;
        }
        function setAttempts(n){
            localStorage.setItem(COOLDOWN_COUNT_KEY, String(n));
        }
        function clearAttempts(){
            localStorage.removeItem(COOLDOWN_COUNT_KEY);
        }
        function formatTime(ms){
            var total = Math.max(0, Math.ceil(ms / 1000));
            var m = Math.floor(total / 60);
            var s = total % 60;
            return m + ':' + (s < 10 ? '0' + s : s);
        }
        function applyCooldownUI(untilTs){
            var now = Date.now();
            if (untilTs <= now) {
                btn.disabled = false;
                btn.textContent = 'Verify Account';
                clearAttempts();
                if (cooldownTimer) { clearInterval(cooldownTimer); cooldownTimer = null; }
                return;
            }
            btn.disabled = true;
            var remaining = untilTs - now;
            btn.textContent = 'Try again in ' + formatTime(remaining);
            if (!cooldownTimer) {
                cooldownTimer = setInterval(function(){
                    var left = getCooldownUntil() - Date.now();
                    if (left <= 0) {
                        applyCooldownUI(0);
                    } else {
                        btn.textContent = 'Try again in ' + formatTime(left);
                    }
                }, 1000);
            }
        }

        // Initialize cooldown state on load
        applyCooldownUI(getCooldownUntil());

        btn.addEventListener('click', function(){
            var now = Date.now();
            var until = getCooldownUntil();
            if (until > now) {
                applyCooldownUI(until);
                if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-warning">Too many attempts. Please wait before trying again.</div>';
                return;
            }
            var attempts = getAttempts() + 1;
            setAttempts(attempts);
            if (attempts >= COOLDOWN_MAX_ATTEMPTS) {
                var lockUntil = Date.now() + COOLDOWN_MS;
                setCooldownUntil(lockUntil);
                applyCooldownUI(lockUntil);
                if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-warning">Too many attempts. Please wait before trying again.</div>';
                return;
            }
            if (!window.fundoraDidit || !window.fundoraDidit.ajaxurl || !window.fundoraDidit.nonce) {
                if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Verification is not configured.</div>';
                return;
            }
            var fd = new FormData();
            fd.append('action', 'fundora_start_verification');
            fd.append('nonce', window.fundoraDidit.nonce);
            btn.disabled = true;
            btn.textContent = 'Starting...';
            fetch(window.fundoraDidit.ajaxurl, { method: 'POST', body: fd })
                .then(function(r){
                    return r.text().then(function(text){
                        var cleaned = (text || '').replace(/^\uFEFF/, '').trim();
                        if (!r.ok) {
                            console.error('fundora_didit: HTTP error', r.status, cleaned);
                        }
                        try {
                            return JSON.parse(cleaned);
                        } catch (e) {
                            console.error('fundora_didit: JSON parse failed', e, cleaned);
                            throw e;
                        }
                    });
                })
                .then(function(j){
                    console.log('fundora_didit: response', j);
                    if (j && j.success && j.data && j.data.url) {
                        window.open(j.data.url, '_blank', 'noopener');
                        btn.textContent = 'Pending Verification';
                        return;
                    }
                    var message = (j && j.data && j.data.message) ? j.data.message : 'Unable to start verification.';
                    if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error">' + message + '</div>';
                })
                .catch(function(e){
                    console.error('fundora_didit: request failed', e);
                    if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Unable to start verification.</div>';
                })
                .finally(function(){
                    btn.disabled = false;
                    btn.textContent = 'Verify Account';
                });
        });
    })();

      window.kbfSaveProfile = function(nonce) {
        const form = document.getElementById('kbf-profile-form');
        if (!window.kbfValidateSocialLinks()) {
            document.getElementById('kbf-profile-msg').innerHTML =
                '<div class="kbf-alert kbf-alert-error">Please fix the highlighted social links.</div>';
            return;
        }
        const fd = new FormData(form);
        const typeSel = document.getElementById('kbf-payout-type');
        const nameEl = document.getElementById('kbf-payout-name');
        const numEl = document.getElementById('kbf-payout-number');
        if (typeSel) {
            const typeVal = (typeSel.value || '').trim();
            fd.set('payout_type', typeVal);
            if (!typeVal) {
                fd.set('payout_name', '');
                fd.set('payout_number', '');
            } else {
                if (nameEl) fd.set('payout_name', (nameEl.value || '').trim());
                if (numEl) fd.set('payout_number', (numEl.value || '').trim());
            }
        }
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
                    setTimeout(function(){ location.reload(); }, 400);
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
        // Preloader intentionally disabled on profile page.
      </script>
    <?php return ob_get_clean();
}

