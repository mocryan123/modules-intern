<?php
/*
 * KBF user dashboard tab: Profile (Enhanced UX)
 */

// Constants for magic numbers
if ( ! defined( 'KBF_PROFILE_BIO_MAX_LENGTH' ) ) {
    define( 'KBF_PROFILE_BIO_MAX_LENGTH', 250 );
}
if ( ! defined( 'KBF_PROFILE_SOCIAL_NAME_MAX_LENGTH' ) ) {
    define( 'KBF_PROFILE_SOCIAL_NAME_MAX_LENGTH', 30 );
}
if ( ! defined( 'KBF_PROFILE_DISPLAY_NAME_MAX_LENGTH' ) ) {
    define( 'KBF_PROFILE_DISPLAY_NAME_MAX_LENGTH', 50 );
}
if ( ! defined( 'KBF_PROFILE_CHECKLIST_ITEMS' ) ) {
    define( 'KBF_PROFILE_CHECKLIST_ITEMS', 5 );
}

/**
 * @function  kbf_dashboard_profile_tab
 * @purpose   Renders the full profile edit page (form, cropper, save bar) for the organizer dashboard
 * @used-by   sections.php → kbf_dashboard_profile_tab($business_id)
 * @calls     $wpdb->get_row, get_userdata, get_user_meta, wp_create_nonce, kbf_get_page_url, ob_start/ob_get_clean
 * @params    int $business_id — WordPress user ID of the organizer
 * @returns   string — buffered HTML output of the profile tab
 * @status    ACTIVE
 */
function kbf_dashboard_profile_tab( $business_id ) {
    // Sanitize input
    $business_id = absint( $business_id );
    if ( $business_id <= 0 ) {
        return '<div class="kbf-alert kbf-alert-error">Invalid profile ID.</div>';
    }

    global $wpdb;
    $pt = $wpdb->prefix . 'kbf_organizer_profiles';
    $profile = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$pt} WHERE business_id=%d", $business_id ) );

    $user = get_userdata( $business_id );
    if ( ! $user ) {
        return '<div class="kbf-alert kbf-alert-error">User not found.</div>';
    }

    $avatar = $profile && ! empty( $profile->avatar_url ) ? $profile->avatar_url : '';

    // Safely decode JSON social links
    $socials = array();
    if ( $profile && ! empty( $profile->social_links ) ) {
        $decoded = json_decode( $profile->social_links, true );
        if ( is_array( $decoded ) ) {
            $socials = $decoded;
        }
    }

    $profile_value = function ( $key, $default = '' ) use ( $profile ) {
        return ( $profile && isset( $profile->$key ) ) ? $profile->$key : $default;
    };

    $payout_type   = $profile_value( 'payout_type', '' );
    $payout_name   = $profile_value( 'payout_name', '' );
    $payout_number = $profile_value( 'payout_number', '' );

    $phone       = sanitize_text_field( get_user_meta( $business_id, 'kbf_phone', true ) );
    $address     = sanitize_text_field( get_user_meta( $business_id, 'kbf_address', true ) );
    $social_name = sanitize_text_field( get_user_meta( $business_id, 'kbf_social_name', true ) );

    $nonce        = wp_create_nonce( 'kbf_organizer_profile' );
    $didit_status = get_user_meta( $business_id, 'fundora_didit_verification_status', true );

    $stats_total_raised   = (float) $profile_value( 'total_raised', 0 );
    $stats_total_sponsors = (int) $profile_value( 'total_sponsors', 0 );
    $stats_rating         = (float) $profile_value( 'rating', 0 );
    $stats_rating_count   = (int) $profile_value( 'rating_count', 0 );

    // Profile completion
    $has_name    = ! empty( trim( $user->display_name ) );
    $has_social  = ! empty( trim( $social_name ) );
    $has_bio     = $profile && ! empty( trim( (string) $profile->bio ) );
    $has_payout  = ! empty( $payout_type ) && ! empty( $payout_name ) && ! empty( $payout_number );
    $has_address = ! empty( trim( $address ) );

    $onboard_done = ( $has_name ? 1 : 0 ) + ( $has_social ? 1 : 0 ) + ( $has_bio ? 1 : 0 ) + ( $has_payout ? 1 : 0 ) + ( $has_address ? 1 : 0 );
    $onboard_pct  = round( ( $onboard_done / KBF_PROFILE_CHECKLIST_ITEMS ) * 100 );

    ob_start();
    ?>
    <style>
      /* Profile Container */
      .kbf-profile-wrap { max-width: 720px; margin: 0 auto; padding: 24px 16px 120px; }

      /* Profile Header Card */
      .kbf-profile-header {
        background: #fff;
        padding: 100px 24px 24px;
        margin-bottom: 20px;
        text-align: center;
        position: relative;
        overflow: hidden;
      }
      .kbf-profile-header::before {
        display: none;
      }
      .kbf-avatar-wrap {
        position: relative;
        display: inline-block;
        cursor: pointer;
        margin-bottom: 16px;
      }
      .kbf-avatar-wrap img,
      .kbf-avatar-fallback {
        width: 112px;
        height: 112px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 4px 16px rgba(15,23,42,.12);
        position: relative;
        z-index: 1;
      }
      .kbf-avatar-fallback {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .kbf-avatar-fallback i { font-size: 44px; color: #fff; }
      .kbf-avatar-overlay {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 44px; height: 44px;
        background: rgba(15,23,42,.55);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity .2s, transform .2s;
        z-index: 2;
        pointer-events: none;
      }
      .kbf-avatar-wrap:hover .kbf-avatar-overlay { opacity: 1; }
      .kbf-avatar-overlay i { font-size: 20px; color: #fff; }
      .kbf-file-input {
        position: absolute; width: 1px; height: 1px;
        overflow: hidden; clip: rect(0,0,0,0);
        border: 0; padding: 0; margin: -1px;
      }
      .kbf-header-name {
        font-size: 20px;
        font-weight: 600;
        color: #0f172a;
        margin: 0 0 4px;
      }
      .kbf-header-social {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 8px;
      }
      .kbf-header-social span { color: #3b82f6; font-weight: 500; }
      .kbf-verify-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
      }
      .kbf-verify-badge.verified { background: #dcfce7; color: #166534; }
      .kbf-verify-badge.pending { background: #fef3c7; color: #92400e; }
      .kbf-verify-badge.failed { background: #fee2e2; color: #991b1b; }
      .kbf-verify-badge.unverified { background: #f1f5f9; color: #64748b; }

      /* Progress Bar */
      .kbf-profile-progress {
        margin-top: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
      }
      .kbf-progress-text {
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
      }
      .kbf-progress-bar {
        width: 140px;
        height: 6px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
      }
      .kbf-progress-bar span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #2563eb);
        border-radius: 999px;
        transition: width .4s ease;
      }

      /* Section Cards */
      .kbf-profile-section {
        background: #fff;
        border: 1px solid var(--kbf-border);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 16px;
      }
      .kbf-profile-section-title {
        font-size: 15px;
        font-weight: 600;
        color: #0f172a;
        margin: 0 0 20px;
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .kbf-profile-section-title .ph {
        font-size: 18px;
        color: #3b82f6;
      }

      /* Forms - matches global kbf form styles */
      .kbf-form-row {
        display: grid;
        gap: 16px;
      }
      .kbf-form-row:last-child { margin-bottom: 0; }
      .kbf-form-row-2 { grid-template-columns: 1fr 1fr; }
      .kbf-form-row-3 { grid-template-columns: 1fr 1fr 1fr; }

      .kbf-form-group {
        margin-bottom: 16px;
      }
      .kbf-form-group:last-child { margin-bottom: 0; }

      .kbf-form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--kbf-text-sm);
        margin-bottom: 6px;
      }

      .kbf-form-group input,
      .kbf-form-group select,
      .kbf-form-group textarea {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        padding: 9px 12px;
        border: 1.5px solid var(--kbf-border);
        border-radius: 7px;
        font-size: 13.5px;
        color: var(--kbf-text);
        background: #fff;
        transition: border-color .15s, box-shadow .15s;
        font-family: inherit;
      }
      .kbf-form-group select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding-right: 36px;
        margin-top: 2px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364758b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 14px;
      }
      .kbf-form-group input::placeholder,
      .kbf-form-group textarea::placeholder {
        color: #8aa0b8;
        font-weight: 400;
      }
      .kbf-form-group input:focus,
      .kbf-form-group select:focus,
      .kbf-form-group textarea:focus {
        outline: none;
        border-color: var(--kbf-navy-light);
        box-shadow: 0 0 0 3px rgba(59,130,246,.12);
      }
      .kbf-form-group input:disabled,
      .kbf-form-group select:disabled {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
      }
      .kbf-form-group small,
      .kbf-form-hint {
        display: block;
        font-size: 11.5px;
        color: var(--kbf-slate);
        margin-top: 4px;
      }

      /* Input with prefix (e.g. @ social name) */
      .kbf-input-with-prefix {
        position: relative;
      }
      .kbf-input-prefix {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 13.5px;
        font-weight: 500;
        color: #94a3b8;
        pointer-events: none;
        z-index: 1;
      }
      .kbf-input-with-prefix input {
        padding-left: 28px !important;
      }
      /* Address selects - force fill grid column */
      #kbf-profile-province,
      #kbf-profile-municipality,
      #kbf-profile-barangay {
        width: 100% !important;
        min-width: 0 !important;
      }
      .kbf-form-group .kbf-field-error {
        display: none;
        margin-top: 6px;
        font-size: 12px;
        color: #dc2626;
      }
      .kbf-input-error {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 3px rgba(220,38,38,.08) !important;
      }
      .kbf-form-hint {
        font-size: 12px;
        color: #64748b;
        margin-top: 6px;
        line-height: 1.5;
      }
      .kbf-bio-wrap textarea {
        min-height: 100px;
        resize: vertical;
      }
      .kbf-char-count {
        text-align: right;
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
      }

      /* Payout Section */
      .kbf-payout-section-hint {
        font-size: 13px;
        color: #64748b;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid var(--kbf-border);
        line-height: 1.5;
      }

      /* Social Links Toggle */
      .kbf-social-section { padding: 0; }
      .kbf-social-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        padding: 24px 24px;
      }
      .kbf-social-toggle .kbf-profile-section-title { margin: 0; }
      .kbf-social-toggle .ph {
        font-size: 16px;
        color: #3b82f6;
        transition: transform .2s;
      }
      .kbf-social-toggle.open .ph { transform: rotate(180deg); }
      .kbf-social-body {
        max-height: 0;
        overflow: hidden;
        transition: max-height .3s ease, padding .3s ease;
      }
      .kbf-social-body.open {
        max-height: 320px;
        padding: 16px 24px 24px;
      }

      /* Stats */
      .kbf-stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
      }
      .kbf-stat-card {
        text-align: center;
        padding: 20px 12px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
      }
      .kbf-stat-card .ph {
        font-size: 20px;
        color: #3b82f6;
        margin-bottom: 8px;
      }
      .kbf-stat-value {
        font-size: 18px;
        font-weight: 600;
        color: #0f172a;
      }
      .kbf-stat-label {
        font-size: 11px;
        font-weight: 500;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-top: 4px;
      }

      /* Save Bar */
      .kbf-profile-save-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255,255,255,.95);
        backdrop-filter: blur(8px);
        border-top: 1px solid var(--kbf-border);
        padding: 16px 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        z-index: 100;
      }
      .kbf-profile-save-bar .kbf-btn {
        border-radius: 10px;
        padding: 12px 28px;
        font-size: 14px;
      }

      /* Messages */
      #kbf-profile-msg, #fundora-didit-msg { margin-top: 12px; }

      /* Cropper */
      .kbf-cropper-backdrop {
        position: fixed; inset: 0;
        background: rgba(15,23,42,.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
      }
      .kbf-cropper-modal {
        background: #fff;
        border-radius: 18px;
        padding: 20px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 50px rgba(15,23,42,.25);
      }
      .kbf-cropper-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
      }
      .kbf-cropper-title { font-weight: 700; font-size: 16px; }
      .kbf-cropper-sub { font-size: 12px; color: var(--kbf-slate); margin-bottom: 12px; }
      .kbf-cropper-stage {
        position: relative;
        width: 240px; height: 240px;
        margin: 0 auto;
        border-radius: 50%;
        overflow: hidden;
        background: #f1f5f9;
        cursor: grab;
        border: 2px dashed #cbd5f5;
      }
      .kbf-cropper-image {
        position: absolute; top: 0; left: 0;
        user-select: none; pointer-events: none;
        transform-origin: 0 0;
      }
      .kbf-cropper-mask {
        position: absolute; inset: 0;
        box-shadow: 0 0 0 9999px rgba(15,23,42,.35);
        border-radius: 50%;
        pointer-events: none;
      }
      .kbf-cropper-controls {
        display: flex; align-items: center; gap: 10px;
        margin-top: 12px;
      }
      .kbf-cropper-controls input[type=range] { flex: 1; }
      .kbf-cropper-actions { display: flex; gap: 10px; margin-top: 16px; }
      .kbf-cropper-actions .kbf-btn { width: 100%; justify-content: center; }
      .kbf-cropper-close {
        border: none; background: #f1f5f9; color: #64748b;
        width: 28px; height: 28px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 16px;
      }

      /* Responsive */
      @media (max-width: 640px) {
        .kbf-profile-wrap { padding: 16px 12px 120px; }
        .kbf-profile-section,
        .kbf-profile-header { padding: 20px 16px; }
        .kbf-form-row-2, .kbf-form-row-3 { grid-template-columns: 1fr; }
        .kbf-stats-row { grid-template-columns: 1fr; }
        .kbf-social-body.open { padding: 16px; }
      }
    </style>

    <div class="kbf-profile-wrap">
      <form id="kbf-profile-form" enctype="multipart/form-data" data-nonce="<?php echo esc_attr($nonce); ?>">

        <!-- Header Card -->
        <div class="kbf-profile-header">
          <label class="kbf-avatar-wrap" for="kbf-avatar">
            <?php if($avatar): ?>
              <img src="<?php echo esc_url($avatar); ?>" alt="Profile">
            <?php else: ?>
              <div class="kbf-avatar-fallback"><i class="ph ph-user"></i></div>
            <?php endif; ?>
            <div class="kbf-avatar-overlay"><i class="ph ph-camera"></i></div>
          </label>
          <input id="kbf-avatar" class="kbf-file-input" type="file" name="avatar" accept="image/*">
          <div class="kbf-header-name"><?php echo esc_html($user->display_name); ?></div>
          <?php if($social_name): ?>
            <div class="kbf-header-social">@<?php echo esc_html($social_name); ?></div>
          <?php endif; ?>
          <?php if($didit_status === 'Approved'): ?>
            <span class="kbf-verify-badge verified"><i class="ph-fill ph-seal-check"></i> Verified</span>
          <?php elseif($didit_status === 'In Review'): ?>
            <span class="kbf-verify-badge pending"><i class="ph ph-clock"></i> Pending</span>
          <?php elseif($didit_status === 'Declined'): ?>
            <span class="kbf-verify-badge failed"><i class="ph ph-x-circle"></i> Failed</span>
          <?php else: ?>
            <span class="kbf-verify-badge unverified">Not Verified</span>
          <?php endif; ?>
          <?php if($onboard_done < 5): ?>
            <div class="kbf-profile-progress">
              <span class="kbf-progress-text"><?php echo $onboard_done; ?>/5 complete</span>
              <div class="kbf-progress-bar"><span style="width:<?php echo $onboard_pct; ?>%;"></span></div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Identity -->
        <div class="kbf-profile-section">
          <div class="kbf-profile-section-title"><i class="ph ph-user-circle"></i> Identity</div>
          <div class="kbf-form-row kbf-form-row-2">
            <div class="kbf-form-group">
              <label>Display Name</label>
              <input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" placeholder="Your display name" maxlength="50">
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Social Name</label>
              <div class="kbf-input-with-prefix">
                <span class="kbf-input-prefix">@</span>
                <input type="text" name="kbf_social_name" id="kbf-social-name" value="<?php echo esc_attr($social_name); ?>" placeholder="yourname" maxlength="30">
              </div>
              <div class="kbf-form-hint">Letters, numbers, underscores only. Used for signing in.</div>
              <div class="kbf-field-error" id="kbf-social-name-error"></div>
            </div>
          </div>
          <div class="kbf-form-group kbf-bio-wrap">
            <label>Bio / About</label>
            <textarea name="bio" rows="4" maxlength="250" placeholder="Tell sponsors about yourself..."><?php echo esc_textarea(isset($profile->bio) ? str_replace('\\', '', wp_unslash($profile->bio)) : ''); ?></textarea>
            <div class="kbf-char-count" id="kbf-profile-bio-count">0 / 250</div>
            <div class="kbf-field-error"></div>
          </div>
        </div>

        <!-- Contact -->
        <div class="kbf-profile-section">
          <div class="kbf-profile-section-title"><i class="ph ph-phone"></i> Contact Info</div>
          <div class="kbf-form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo esc_attr($phone); ?>" placeholder="+63 9XX XXX XXXX">
          </div>
        </div>

        <!-- Payout -->
        <div class="kbf-profile-section">
          <div class="kbf-profile-section-title"><i class="ph ph-bank"></i> Payout Details</div>
          <div class="kbf-form-group" style="max-width:280px;">
            <label>Payout Method</label>
            <select name="payout_type" id="kbf-payout-type">
              <option value="">Select a method</option>
              <option value="gcash" <?php echo $payout_type==='gcash'?'selected':''; ?>>GCash</option>
              <option value="maya_wallet" <?php echo $payout_type==='maya_wallet'?'selected':''; ?>>Maya Wallet</option>
              <option value="card" <?php echo $payout_type==='card'?'selected':''; ?>>Credit/Debit Card</option>
            </select>
            <div class="kbf-field-error"></div>
            <div class="kbf-form-hint" id="kbf-payout-hint" style="display:none;"></div>
          </div>
          <div id="kbf-payout-fields" style="display:none;">
            <div class="kbf-form-row kbf-form-row-2">
              <div class="kbf-form-group">
                <label id="kbf-payout-label-1">Account Name</label>
                <input type="text" name="payout_name" id="kbf-payout-name" value="<?php echo esc_attr($payout_name); ?>" placeholder="Enter name" <?php echo $payout_type===''?'disabled':''; ?>>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label id="kbf-payout-label-2">Account Number</label>
                <input type="text" name="payout_number" id="kbf-payout-number" value="<?php echo esc_attr($payout_number); ?>" placeholder="Enter number" <?php echo $payout_type===''?'disabled':''; ?>>
                <div class="kbf-field-error"></div>
              </div>
            </div>
            <div class="kbf-form-hint" id="kbf-payout-format-hint" style="text-align:right;margin-top:8px;"></div>
          </div>
          <div class="kbf-payout-section-hint">We'll use this for withdrawals. Double-check to avoid delays.</div>
        </div>

        <!-- Address -->
        <div class="kbf-profile-section">
          <div class="kbf-profile-section-title"><i class="ph ph-map-pin"></i> Address</div>
          <div class="kbf-form-row kbf-form-row-3">
            <div class="kbf-form-group">
              <label>Province</label>
              <select id="kbf-profile-province">
                <option value="">Select</option>
                <?php foreach (kbf_get_provinces() as $p): ?>
                  <option value="<?php echo esc_attr($p); ?>"><?php echo esc_html($p); ?></option>
                <?php endforeach; ?>
              </select>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Municipality</label>
              <select id="kbf-profile-municipality" disabled><option value="">Select Municipality</option></select>
            </div>
            <div class="kbf-form-group">
              <label>Barangay</label>
              <select id="kbf-profile-barangay" disabled><option value="">Select Barangay</option></select>
            </div>
          </div>
          <input type="hidden" name="address" id="kbf-profile-address" value="<?php echo esc_attr($address); ?>">
        </div>

        <!-- Social Links (Collapsible) -->
        <div class="kbf-profile-section kbf-social-section">
          <div class="kbf-social-toggle" id="kbf-social-toggle">
            <div class="kbf-profile-section-title"><i class="ph ph-share-network"></i> Social Links</div>
            <i class="ph ph-caret-down"></i>
          </div>
          <div class="kbf-social-body">
            <div class="kbf-form-row kbf-form-row-2">
              <div class="kbf-form-group">
                <label>Facebook</label>
                <input type="url" name="social_facebook" placeholder="https://facebook.com/yourname" value="<?php echo esc_attr($socials['facebook'] ?? ''); ?>">
              </div>
              <div class="kbf-form-group">
                <label>Instagram</label>
                <input type="url" name="social_instagram" placeholder="https://instagram.com/yourname" value="<?php echo esc_attr($socials['instagram'] ?? ''); ?>">
              </div>
            </div>
            <div class="kbf-form-row kbf-form-row-2">
              <div class="kbf-form-group">
                <label>X (Twitter)</label>
                <input type="url" name="social_twitter" placeholder="https://x.com/yourname" value="<?php echo esc_attr($socials['twitter'] ?? ''); ?>">
              </div>
              <div class="kbf-form-group">
                <label>Website</label>
                <input type="url" name="social_website" placeholder="https://yourwebsite.com" value="<?php echo esc_attr($socials['website'] ?? ''); ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Stats -->
        <div class="kbf-profile-section">
          <div class="kbf-profile-section-title"><i class="ph ph-chart-bar"></i> Profile Stats</div>
          <div class="kbf-stats-row">
            <div class="kbf-stat-card">
              <i class="ph ph-piggy-bank"></i>
              <div class="kbf-stat-value">&#8369;<?php echo number_format($stats_total_raised, 0); ?></div>
              <div class="kbf-stat-label">Total Raised</div>
            </div>
            <div class="kbf-stat-card">
              <i class="ph ph-users"></i>
              <div class="kbf-stat-value"><?php echo number_format($stats_total_sponsors); ?></div>
              <div class="kbf-stat-label">Sponsors</div>
            </div>
            <div class="kbf-stat-card">
              <i class="ph ph-thumbs-up"></i>
              <div class="kbf-stat-value"><?php echo number_format($stats_rating, 1); ?>/5</div>
              <div class="kbf-stat-label"><?php echo $stats_rating_count; ?> ratings</div>
            </div>
          </div>
        </div>

        <div id="kbf-profile-msg"></div>
        <div id="fundora-didit-msg"></div>
      </form>
    </div>

    <!-- Sticky Save Bar -->
    <div class="kbf-profile-save-bar">
      <?php if($didit_status === 'Approved'): ?>
        <button type="button" class="kbf-btn kbf-btn-secondary" disabled style="opacity:.6;">Verified</button>
      <?php elseif($didit_status === 'In Review'): ?>
        <button type="button" class="kbf-btn kbf-btn-secondary" disabled style="opacity:.6;">Pending</button>
      <?php elseif($didit_status === 'Declined'): ?>
        <button type="button" class="kbf-btn kbf-btn-secondary" id="fundora-didit-start">Retry Verification</button>
      <?php else: ?>
        <button type="button" class="kbf-btn kbf-btn-secondary" id="fundora-didit-start"><i class="ph ph-shield-check" style="margin-right:4px;"></i> Verify Identity</button>
      <?php endif; ?>
      <button type="button" class="kbf-btn kbf-btn-primary" id="kbf-profile-save-btn" onclick="kbfSaveProfile('<?php echo esc_js($nonce); ?>')">Save Changes</button>
    </div>

    <!-- Image Cropper -->
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

    <script>
    if (typeof ajaxurl === 'undefined') var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    window.fundoraDidit = { ajaxurl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', nonce: '<?php echo esc_js(wp_create_nonce('fundora_didit_nonce')); ?>' };

    // ===== PAYOUT FIELDS =====
    /**
     * @function  kbfUpdatePayoutFields
     * @purpose   Dynamically updates payout field labels, placeholders, and hints based on selected payout method
     * @used-by   onchange event on #kbf-payout-type; called once on page load
     * @calls     DOM manipulation (no external functions)
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfUpdatePayoutFields = function(){
        const typeSel = document.getElementById('kbf-payout-type');
        const fieldsWrap = document.getElementById('kbf-payout-fields');
        const nameEl = document.getElementById('kbf-payout-name');
        const numEl = document.getElementById('kbf-payout-number');
        const label1 = document.getElementById('kbf-payout-label-1');
        const label2 = document.getElementById('kbf-payout-label-2');
        const hint = document.getElementById('kbf-payout-hint');
        const formatHint = document.getElementById('kbf-payout-format-hint');
        if(!typeSel || !fieldsWrap) return;
        const val = typeSel.value;
        if(!val){ fieldsWrap.style.display = 'none'; hint.style.display = 'none'; return; }
        fieldsWrap.style.display = 'block';
        nameEl.disabled = false; numEl.disabled = false; hint.style.display = 'block';
        if(val === 'gcash' || val === 'maya_wallet'){
            label1.textContent = 'Account Holder Name'; label2.textContent = 'Mobile Number';
            nameEl.placeholder = 'Full name on account'; numEl.placeholder = '09XX XXX XXXX';
            numEl.maxLength = 13;
            hint.textContent = (val === 'gcash') ? 'Payouts sent to the mobile number linked to your GCash account.' : 'Payouts sent to the mobile number linked to your Maya account.';
            formatHint.textContent = 'Format: 09XX XXX XXXX';
        } else if(val === 'card'){
            label1.textContent = 'Cardholder Name'; label2.textContent = 'Card Number';
            nameEl.placeholder = 'Name as it appears on card'; numEl.placeholder = 'XXXX XXXX XXXX XXXX';
            numEl.maxLength = 19;
            hint.textContent = 'Card payouts use your cardholder name and card number.';
            formatHint.textContent = 'Format: XXXX XXXX XXXX XXXX';
        }
    };
    document.getElementById('kbf-payout-type')?.addEventListener('change', window.kbfUpdatePayoutFields);
    window.kbfUpdatePayoutFields();

    // ===== AUTO-FORMAT PAYOUT NUMBER =====
    (function(){
        const numEl = document.getElementById('kbf-payout-number');
        const typeSel = document.getElementById('kbf-payout-type');
        if(!numEl || !typeSel) return;
        numEl.addEventListener('input', function(){
            const val = this.value.replace(/\D/g,'');
            const type = typeSel.value;
            if(type === 'gcash' || type === 'maya_wallet'){
                const trimmed = val.substring(0, 11);
                if(trimmed.length <= 4) this.value = trimmed;
                else if(trimmed.length <= 7) this.value = trimmed.substring(0,4) + ' ' + trimmed.substring(4);
                else this.value = trimmed.substring(0,4) + ' ' + trimmed.substring(4,7) + ' ' + trimmed.substring(7);
            } else if(type === 'card'){
                const trimmed = val.substring(0, 16);
                const groups = trimmed.match(/.{1,4}/g);
                this.value = groups ? groups.join(' ') : trimmed;
            }
        });
    })();

    // ===== AUTO-FORMAT PHONE NUMBER =====
    (function(){
        const phone = document.querySelector('[name="phone"]');
        if(!phone) return;
        phone.addEventListener('input', function(){
            let val = this.value;
            // Remove '0' after '+630' -> '+63'
            if(val.startsWith('+630')) {
                this.value = '+63' + val.substring(4);
                this.setSelectionRange(this.value.length, this.value.length);
            }
            // Add '+' after '630' and remove '0' -> '+63'
            else if(val.startsWith('630')) {
                this.value = '+63' + val.substring(3);
                this.setSelectionRange(this.value.length, this.value.length);
            }
            // Auto-add '+63' if starting with '09'
            else if(val.startsWith('09')) {
                this.value = '+63' + val.substring(1);
                this.setSelectionRange(this.value.length, this.value.length);
            }
        });
    })();

    // ===== IMAGE CROPPER =====
    /**
     * @function  Image Cropper IIFE
     * @purpose   Provides drag-to-pan, zoom, and circular crop for avatar photos; replaces file input with cropped blob
     * @used-by   onchange on #kbf-avatar file input; onclick on #kbf-cropper-close, #kbf-cropper-cancel, #kbf-cropper-apply; click on backdrop
     * @calls     FileReader, Canvas API, DataTransfer, DOM event listeners
     * @params    none (self-contained IIFE with internal private functions)
     * @returns   void
     * @status    ACTIVE
     */
    (function(){
        const fileInput = document.getElementById('kbf-avatar');
        const backdrop = document.getElementById('kbf-cropper-backdrop');
        const stage = document.getElementById('kbf-cropper-stage');
        const img = document.getElementById('kbf-cropper-image');
        const zoomSlider = document.getElementById('kbf-cropper-zoom');
        const closeBtn = document.getElementById('kbf-cropper-close');
        const cancelBtn = document.getElementById('kbf-cropper-cancel');
        const applyBtn = document.getElementById('kbf-cropper-apply');

        let imageSrc = null;
        let scale = 1;
        let panX = 0;
        let panY = 0;
        let isDragging = false;
        let startX, startY;
        let stageSize = 240;

        function openCropper(src){
            imageSrc = src;
            img.src = src;
            scale = 1;
            panX = 0;
            panY = 0;
            zoomSlider.value = 1;
            img.addEventListener('load', fitImage, { once: true });
            renderImage();
            backdrop.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeCropper(){
            backdrop.style.display = 'none';
            document.body.style.overflow = '';
            imageSrc = null;
            img.removeAttribute('src');
        }

        function renderImage(){
            if(!img.src) return;
            img.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
        }

        function fitImage(){
            const natW = img.naturalWidth || 1;
            const natH = img.naturalHeight || 1;
            const minScale = Math.max(stageSize / natW, stageSize / natH);
            scale = minScale;
            zoomSlider.min = minScale;
            zoomSlider.max = minScale * 3;
            zoomSlider.step = minScale / 100;
            zoomSlider.value = minScale;
            panX = (stageSize - natW * scale) / 2;
            panY = (stageSize - natH * scale) / 2;
            renderImage();
        }

        function getCroppedCanvas(){
            const canvas = document.createElement('canvas');
            const size = 300;
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');

            ctx.save();
            ctx.beginPath();
            ctx.arc(size/2, size/2, size/2, 0, Math.PI * 2);
            ctx.clip();

            const natW = img.naturalWidth || 1;
            const natH = img.naturalHeight || 1;
            const centerX = (stageSize / 2 - panX) / scale;
            const centerY = (stageSize / 2 - panY) / scale;
            const cropHalf = (stageSize / 2) / scale;

            const sx = centerX - cropHalf;
            const sy = centerY - cropHalf;
            const sw = cropHalf * 2;
            const sh = cropHalf * 2;

            ctx.drawImage(img, sx, sy, sw, sh, 0, 0, size, size);
            ctx.restore();
            return canvas;
        }

        // File input opens cropper
        fileInput.addEventListener('change', function(){
            if(!this.files || !this.files[0]) return;
            const reader = new FileReader();
            reader.onload = function(e){ openCropper(e.target.result); };
            reader.readAsDataURL(this.files[0]);
        });

        // Zoom slider
        zoomSlider.addEventListener('input', function(){
            const newScale = parseFloat(this.value);
            const ratio = newScale / scale;
            panX = stageSize / 2 - (stageSize / 2 - panX) * ratio;
            panY = stageSize / 2 - (stageSize / 2 - panY) * ratio;
            scale = newScale;
            renderImage();
        });

        // Drag to pan
        stage.addEventListener('mousedown', function(e){
            e.preventDefault();
            isDragging = true;
            startX = e.clientX - panX;
            startY = e.clientY - panY;
        });
        window.addEventListener('mousemove', function(e){
            if(!isDragging) return;
            panX = e.clientX - startX;
            panY = e.clientY - startY;
            renderImage();
        });
        window.addEventListener('mouseup', function(){ isDragging = false; });

        // Touch support
        stage.addEventListener('touchstart', function(e){
            const t = e.touches[0];
            isDragging = true;
            startX = t.clientX - panX;
            startY = t.clientY - panY;
        }, {passive: true});
        window.addEventListener('touchmove', function(e){
            if(!isDragging) return;
            const t = e.touches[0];
            panX = t.clientX - startX;
            panY = t.clientY - startY;
            renderImage();
        }, {passive: true});
        window.addEventListener('touchend', function(){ isDragging = false; });

        // Buttons
        closeBtn.addEventListener('click', closeCropper);
        cancelBtn.addEventListener('click', closeCropper);
        applyBtn.addEventListener('click', function(){
            const canvas = getCroppedCanvas();
            canvas.toBlob(function(blob){
                // Replace the file input's file with cropped blob
                const file = new File([blob], 'avatar.png', {type: 'image/png'});
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;

                // Update avatar preview in header
                const avatarWrap = document.querySelector('.kbf-avatar-wrap');
                const oldImg = avatarWrap.querySelector('img');
                const oldFallback = avatarWrap.querySelector('.kbf-avatar-fallback');
                if(oldImg) oldImg.remove();
                if(oldFallback) oldFallback.remove();
                const previewImg = document.createElement('img');
                previewImg.src = canvas.toDataURL('image/png');
                previewImg.alt = 'Profile';
                avatarWrap.insertBefore(previewImg, avatarWrap.firstChild);

                closeCropper();
            }, 'image/png');
        });

        // Click backdrop to close
        backdrop.addEventListener('click', function(e){
            if(e.target === backdrop) closeCropper();
        });
    })();

    // ===== BIO CHAR COUNT =====
    (function(){
        const bio = document.querySelector('textarea[name="bio"]');
        const count = document.getElementById('kbf-profile-bio-count');
        if(!bio || !count) return;
        const update = () => { count.textContent = (bio.value.length || 0) + ' / 250'; };
        bio.addEventListener('input', update); update();
    })();

    // ===== SOCIAL TOGGLE =====
    (function(){
        const toggle = document.getElementById('kbf-social-toggle');
        if(!toggle) return;
        toggle.addEventListener('click', function(){
            this.classList.toggle('open');
            const body = this.nextElementSibling;
            if(body && body.classList.contains('kbf-social-body')){
                body.classList.toggle('open');
            }
        });
    })();

    // ===== PREVENT SPACES IN SOCIAL NAME =====
    (function(){
        const sn = document.getElementById('kbf-social-name');
        if(sn) sn.addEventListener('input', function(){ if(/\s/.test(this.value)) this.value = this.value.replace(/\s+/g,''); });
    })();

    // ===== ADDRESS SYNC =====
    (function(){
        const prov = document.getElementById('kbf-profile-province');
        const muni = document.getElementById('kbf-profile-municipality');
        const brgy = document.getElementById('kbf-profile-barangay');
        const hidden = document.getElementById('kbf-profile-address');
        if(!prov || !hidden) return;

        const savedAddr = (hidden.value || '').trim();

        /**
         * @function  updateAddress
         * @purpose   Reads selected province/municipality/barangay dropdowns and writes the combined address to the hidden input
         * @used-by   onchange events on dropdowns; called by kbfSaveProfile before form submission
         * @calls     none
         * @params    none (reads from DOM, writes to hidden input)
         * @returns   void
         * @status    ACTIVE
         */
        function updateAddress(){
            const parts = [];
            if(brgy && brgy.value) parts.push(brgy.value);
            if(muni && muni.value) parts.push(muni.value);
            if(prov && prov.value) parts.push(prov.value);
            hidden.value = parts.join(', ');
        }
        // Expose globally for kbfSaveProfile
        window.updateAddress = updateAddress;

        if(savedAddr){
            function tryRestore(){
                if(typeof window.kbfApplyLocationSelection !== 'function') return false;
                window.kbfApplyLocationSelection(prov, muni, brgy, savedAddr);
                return true;
            }
            var attempts = 0;
            var timer = setInterval(function(){
                if(tryRestore() || ++attempts > 60){
                    clearInterval(timer);
                }
            }, 200);
        }

        prov.addEventListener('change', updateAddress);
        if(muni) muni.addEventListener('change', updateAddress);
        if(brgy) brgy.addEventListener('change', updateAddress);
        setTimeout(updateAddress, 500);
    })();

    // ===== VERIFY BUTTON (DIDIT) =====
    /**
     * @function  Verify Button IIFE
     * @purpose   Starts the Didit identity verification flow via AJAX; opens verification URL in new tab
     * @used-by   onclick on #fundora-didit-start (Verify Identity button)
     * @calls     fetch(ajaxurl), JSON.parse, window.open
     * @params    none
     * @returns   void
     * @status    NEEDS REVIEW — fundora_didit_verification_status user meta is never written by any webhook in the codebase
     */
    (function(){
        const btn = document.getElementById('fundora-didit-start');
        if(!btn) return;
        btn.addEventListener('click', function(){
            const msgEl = document.getElementById('fundora-didit-msg');
            if(!window.fundoraDidit || !window.fundoraDidit.nonce) {
                msgEl.innerHTML = '<div class="kbf-alert kbf-alert-error">Configuration missing. Please reload the page.</div>';
                return;
            }
            btn.disabled = true; btn.textContent = 'Starting...';
            const fd = new FormData();
            fd.append('action', 'fundora_ajax_start_verification');
            fd.append('nonce', window.fundoraDidit.nonce);
            fetch(ajaxurl, {method:'POST', body:fd})
              .then(r => r.text().then(t => {
                  try { return JSON.parse(t.replace(/^\uFEFF+/, '').trim()); }
                  catch(e) { 
                      console.error('Didit verification JSON parse error:', e, 'Raw response:', t);
                      throw new Error('Invalid JSON: ' + t.substring(0, 100)); 
                  }
              }))
              .then(j => {
                  if(j.success && j.data && j.data.url){
                      window.open(j.data.url, '_blank');
                      msgEl.innerHTML = '<div class="kbf-alert kbf-alert-success">Verification started. Check the new window.</div>';
                  } else {
                      msgEl.innerHTML = '<div class="kbf-alert kbf-alert-error">'+((j.data && j.data.message) || 'Unknown error.')+'</div>';
                  }
              })
              .catch(err => {
                  console.error('Didit verification request failed:', err);
                  msgEl.innerHTML = '<div class="kbf-alert kbf-alert-error">Request failed: ' + (err.message || 'Unknown error') + '</div>';
              })
              .finally(() => { btn.disabled=false; btn.textContent='Verify Identity'; });
        });
    })();

    // ===== SAVE PROFILE =====
    /**
     * @function  kbfSaveProfile
     * @purpose   Validates the profile form, submits it via AJAX, and reloads the page on success
     * @used-by   onclick on #kbf-profile-save-btn (Save Changes button)
     * @calls     window.updateAddress, fetch(ajaxurl), FormData, JSON.parse
     * @params    string nonce — WordPress AJAX nonce string
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfSaveProfile = function(nonce){
        const form = document.getElementById('kbf-profile-form');
        const msgEl = document.getElementById('kbf-profile-msg');
        const btn = document.getElementById('kbf-profile-save-btn') || document.querySelector('.kbf-profile-save-bar .kbf-btn-primary');
        if(!btn || !form) return;

        // Ensure address hidden field is synced before submit
        if(typeof updateAddress === 'function') updateAddress();

        let isValid = true, errors = [];
        function showErr(input, msg){
            input.classList.add('kbf-input-error');
            const group = input.closest('.kbf-form-group');
            if(group){
                let err = group.querySelector('.kbf-field-error');
                if(!err){ err = document.createElement('div'); err.className='kbf-field-error'; group.appendChild(err); }
                err.textContent=msg; err.style.display='block';
            }
            errors.push(msg); isValid = false;
        }
        // Validation: Display Name
        const dn = form.querySelector('[name="display_name"]');
        if(!dn || !dn.value.trim()) showErr(dn, 'Display name is required.');
        // Validation: Bio
        const bio = form.querySelector('textarea[name="bio"]');
        if(bio && bio.value.length > 250) showErr(bio, 'Bio must be 250 characters or less.');
        // Validation: Payout
        const pType = form.querySelector('[name="payout_type"]');
        const pName = form.querySelector('[name="payout_name"]');
        const pNum = form.querySelector('[name="payout_number"]');
        if(pType && pType.value){
            if(!pName.value.trim()) showErr(pName, 'Account name is required.');
            if(!pNum.value.trim()) showErr(pNum, 'Account number is required.');
        }
        if(!isValid){
            msgEl.innerHTML = '<div class="kbf-alert kbf-alert-error">'+errors[0]+'</div>';
            msgEl.scrollIntoView({behavior:'smooth', block:'center'});
            return;
        }
        btn.disabled = true; btn.textContent = 'Saving...';
        const fd = new FormData(form);
        fd.append('action', 'kbf_save_organizer_profile');
        fd.append('_ajax_nonce', nonce);
        fetch(ajaxurl, {method:'POST', body:fd})
          .then(r => r.text())
          .then(text => {
              const cleanText = text.replace(/^\uFEFF+/, '').trim();
              try {
                  return JSON.parse(cleanText);
              } catch(e) {
                  console.error('Profile save JSON parse error:', e, 'Raw response:', text);
                  throw new Error('Invalid server response: ' + cleanText.substring(0, 100));
              }
          })
          .then(j => {
              if(j.success){
                  msgEl.innerHTML = '<div class="kbf-alert kbf-alert-success">Profile saved successfully! Reloading...</div>';
                  setTimeout(() => location.reload(), 800);
              } else {
                  msgEl.innerHTML = '<div class="kbf-alert kbf-alert-error">'+((j.data && j.data.message) || 'Save failed.')+'</div>';
                  btn.disabled = false; btn.textContent = 'Save Changes';
              }
          })
          .catch(err => {
              console.error('Profile save request failed:', err);
              msgEl.innerHTML = '<div class="kbf-alert kbf-alert-error">Request failed: ' + (err.message || 'Unknown error') + '</div>';
              btn.disabled = false; btn.textContent = 'Save Changes';
          });
    };
    </script>
<?php
    return ob_get_clean();
}