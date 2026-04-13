<?php
/*
 * KBF user dashboard tab: Profile (Redesigned UX)
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
    <style>
      .kbf-profile-wrap { max-width: 680px; margin: 0 auto; padding: 32px 0px 40px; }
      .kbf-profile-section { background:#fff; border:1px solid var(--kbf-border); border-radius:16px; padding:24px; margin-bottom:18px; }
      .kbf-profile-section-title { font-size:15px; font-weight:600; color:#0f172a; margin:0 0 16px; display:flex; align-items:center; gap:8px; }
      .kbf-profile-section-title .ph { font-size:18px; color:#3b82f6; }
      .kbf-profile-avatar-section { text-align:center; padding:28px 24px 20px; margin-bottom:18px; position:relative; }
      .kbf-avatar-wrap { position:relative; display:inline-block; cursor:pointer; }
      .kbf-avatar-wrap img, .kbf-avatar-fallback { width:128px; height:128px; border-radius:50%; object-fit:cover; border:4px solid #fff; box-shadow:0 8px 24px rgba(15,23,42,.15); }
      .kbf-avatar-fallback { background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%); display:flex; align-items:center; justify-content:center; }
      .kbf-avatar-fallback i { font-size:52px; color:#fff; }
      .kbf-avatar-overlay { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:48px; height:48px; background:rgba(15,23,42,.6); border-radius:50%; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .2s, transform .2s; pointer-events:none; }
      .kbf-avatar-wrap:hover .kbf-avatar-overlay { opacity:1; transform:translate(-50%,-50%) scale(1); }
      .kbf-avatar-overlay i { font-size:22px; color:#fff; }
      .kbf-file-input { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); border:0; padding:0; margin:-1px; }
      .kbf-avatar-name { font-size:17px; font-weight:600; color:#0f172a; margin:10px 0 2px; }
      .kbf-avatar-social { font-size:13px; color:#64748b; margin-bottom:6px; }
      .kbf-avatar-social span { color:#3b82f6; font-weight:500; }
      .kbf-avatar-email { font-size:12px; color:#94a3b8; margin-bottom:8px; }
      .kbf-verify-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:500; }
      .kbf-verify-badge.verified { background:#dcfce7; color:#166534; }
      .kbf-verify-badge.pending { background:#fef3c7; color:#92400e; }
      .kbf-verify-badge.failed { background:#fee2e2; color:#991b1b; }
      .kbf-verify-badge.unverified { background:#f1f5f9; color:#64748b; }
      .kbf-profile-progress { display:flex; align-items:center; justify-content:center; gap:8px; margin-top:10px; font-size:12px; color:#64748b; }
      .kbf-profile-progress-bar { width:120px; height:4px; background:#e2e8f0; border-radius:999px; overflow:hidden; }
      .kbf-profile-progress-bar span { display:block; height:100%; background:#3b82f6; border-radius:999px; }
      .kbf-form-row { display:grid; gap:14px; margin-bottom:14px; }
      .kbf-form-row-2 { grid-template-columns:repeat(2,1fr); }
      .kbf-form-row-3 { grid-template-columns:repeat(3,1fr); }
      .kbf-form-group { margin-bottom:0; }
      .kbf-form-group label { display:block; font-size:13px; font-weight:500; color:#334155; margin-bottom:6px; }
      .kbf-form-group input, .kbf-form-group select, .kbf-form-group textarea { width:100%; padding:10px 12px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; color:#0f172a; background:#fff; transition:border-color .15s, box-shadow .15s; }
      .kbf-form-group input:focus, .kbf-form-group select:focus, .kbf-form-group textarea:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.12); }
      .kbf-form-group input:disabled { background:#f8fafc; color:#64748b; cursor:not-allowed; }
      .kbf-form-group .kbf-field-error { display:none; margin-top:4px; font-size:12px; color:#dc2626; }
      .kbf-input-error { border-color:#dc2626 !important; box-shadow:0 0 0 3px rgba(220,38,38,.08) !important; }
      .kbf-form-hint { font-size:12px; color:#64748b; margin-top:4px; }
      .kbf-bio-wrap textarea { min-height:100px; resize:vertical; }
      .kbf-char-count { text-align:right; font-size:11px; color:#94a3b8; margin-top:4px; }
      .kbf-payout-hint { font-size:12px; color:#64748b; margin-top:8px; line-height:1.5; }
      .kbf-payout-desc { font-size:12px; color:#475569; margin-top:4px; font-style:italic; }
      .kbf-input-with-toggle { position:relative; }
      .kbf-input-with-toggle input { padding-right:50px; }
      .kbf-toggle-visibility { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; font-size:12px; color:#3b82f6; cursor:pointer; font-weight:500; }
      .kbf-social-links-toggle { display:flex; align-items:center; justify-content:space-between; cursor:pointer; user-select:none; }
      .kbf-social-links-toggle .ph { transition:transform .2s; }
      .kbf-social-links-toggle.open .ph { transform:rotate(180deg); }
      .kbf-social-links-body { max-height:0; overflow:hidden; transition:max-height .3s ease, padding .3s ease; }
      .kbf-social-links-body.open { max-height:300px; padding-top:16px; }
      .kbf-stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
      .kbf-stat-item { text-align:center; padding:16px 8px; background:#f8fafc; border-radius:12px; }
      .kbf-stat-item .ph { font-size:20px; color:#3b82f6; margin-bottom:6px; }
      .kbf-stat-item .stat-value { font-size:18px; font-weight:600; color:#0f172a; }
      .kbf-stat-item .stat-label { font-size:11px; font-weight:500; color:#64748b; text-transform:uppercase; letter-spacing:.5px; margin-top:2px; }
      .kbf-profile-save-bar { text-align:center; padding:24px 0 40px; display:flex; align-items:center; justify-content:center; gap:12px; }
      .kbf-profile-save-bar .kbf-btn { border-radius:12px; padding:12px 24px; }
      #kbf-profile-msg, #fundora-didit-msg { margin-top:10px; }
      .kbf-cropper-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.6); display:none; align-items:center; justify-content:center; z-index:9999; }
      .kbf-cropper-modal { background:#fff; border-radius:18px; padding:16px; max-width:420px; width:92%; box-shadow:0 20px 50px rgba(15,23,42,.25); }
      .kbf-cropper-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
      .kbf-cropper-title { font-weight:700; }
      .kbf-cropper-sub { font-size:12px; color:var(--kbf-slate); margin-bottom:12px; }
      .kbf-cropper-stage { position:relative; width:240px; height:240px; margin:0 auto; border-radius:14px; overflow:hidden; background:#f1f5f9; cursor:grab; border:1px dashed #cbd5f5; }
      .kbf-cropper-image { position:absolute; top:0; left:0; user-select:none; pointer-events:none; transform-origin:0 0; }
      .kbf-cropper-mask { position:absolute; inset:0; box-shadow:0 0 0 9999px rgba(15,23,42,.35); border-radius:16px; pointer-events:none; }
      .kbf-cropper-controls { display:flex; align-items:center; gap:10px; margin-top:12px; }
      .kbf-cropper-controls input[type=range] { flex:1; }
      .kbf-cropper-actions { display:flex; gap:10px; margin-top:12px; }
      .kbf-cropper-actions .kbf-btn { width:100%; justify-content:center; }
      .kbf-cropper-close { border:none; background:#f1f5f9; color:#64748b; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; }
      @media (max-width:600px) {
        .kbf-profile-wrap { padding:20px 0px 100px; }
        .kbf-profile-section { padding:18px 16px; }
        .kbf-form-row-2, .kbf-form-row-3 { grid-template-columns:1fr; }
        .kbf-stats-row { grid-template-columns:1fr; }
      }
    </style>

    <div class="kbf-profile-wrap">
      <form id="kbf-profile-form" enctype="multipart/form-data" data-nonce="<?php echo esc_attr($nonce); ?>">
        <?php 
        $avatar = $profile && $profile->avatar_url ? $profile->avatar_url : '';
        $social_name = get_user_meta($business_id, 'kbf_social_name', true);
        // Calculate profile completion for progress bar
        $has_name = !empty(trim($user->display_name));
        $has_social = !empty(trim($social_name));
        $has_bio = $profile && !empty(trim((string)$profile->bio));
        $has_payout = !empty($payout_type) && !empty($payout_name) && !empty($payout_number);
        $has_address = !empty(trim($address));
        $onboard_done = ($has_name?1:0) + ($has_social?1:0) + ($has_bio?1:0) + ($has_payout?1:0) + ($has_address?1:0);
        $onboard_required = 5;
        $onboard_pct = round(($onboard_done / $onboard_required) * 100);
        ?>
        <div class="kbf-profile-avatar-section">
          <label class="kbf-avatar-wrap" for="kbf-avatar">
            <?php if($avatar): ?>
              <img src="<?php echo esc_url($avatar); ?>" alt="Profile">
            <?php else: ?>
              <div class="kbf-avatar-fallback"><i class="ph ph-user"></i></div>
            <?php endif; ?>
            <div class="kbf-avatar-overlay"><i class="ph ph-camera"></i></div>
          </label>
          <input id="kbf-avatar" class="kbf-file-input" type="file" name="avatar" accept="image/*">
          <div class="kbf-avatar-name"><?php echo esc_html($user->display_name); ?></div>
          <?php if($social_name): ?>
            <div class="kbf-avatar-social">@<?php echo esc_html($social_name); ?></div>
          <?php endif; ?>
          <div class="kbf-verify-wrap">
            <?php if($didit_status === 'Approved'): ?>
              <span class="kbf-verify-badge verified"><i class="ph-fill ph-seal-check"></i> Verified</span>
            <?php elseif($didit_status === 'In Review'): ?>
              <span class="kbf-verify-badge pending"><i class="ph ph-clock"></i> Pending</span>
            <?php elseif($didit_status === 'Declined'): ?>
              <span class="kbf-verify-badge failed"><i class="ph ph-x-circle"></i> Failed</span>
            <?php else: ?>
              <span class="kbf-verify-badge unverified">Not Verified</span>
            <?php endif; ?>
          </div>
          <?php if($onboard_done < $onboard_required): ?>
            <div class="kbf-profile-progress">
              <span><?php echo $onboard_done; ?>/<?php echo $onboard_required; ?> complete</span>
              <div class="kbf-profile-progress-bar"><span style="width:<?php echo $onboard_pct; ?>%;"></span></div>
            </div>
          <?php endif; ?>
        </div>

        <div class="kbf-profile-section">
          <div class="kbf-profile-section-title"><i class="ph ph-user-circle"></i> Identity</div>
          <div class="kbf-form-row">
            <div class="kbf-form-group">
              <label>Display Name</label>
              <input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" placeholder="Your display name" maxlength="50">
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Social Name</label>
              <div style="display:flex;align-items:center;gap:4px;">
                <span style="color:#64748b;font-size:14px;">@</span>
                <input type="text" name="kbf_social_name" id="kbf-social-name" value="<?php echo esc_attr(get_user_meta($business_id, 'kbf_social_name', true)); ?>" placeholder="yourname" maxlength="30" style="flex:1;">
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

        <div class="kbf-profile-section">
          <div class="kbf-profile-section-title"><i class="ph ph-phone"></i> Contact Info</div>
          <div class="kbf-form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo esc_attr($phone); ?>" placeholder="+63 9XX XXX XXXX">
          </div>
        </div>

        <div class="kbf-profile-section">
          <div class="kbf-profile-section-title"><i class="ph ph-bank"></i> Payout Details</div>
          <div class="kbf-form-row">
            <div class="kbf-form-group">
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
                <div class="kbf-form-hint" id="kbf-payout-format-hint"></div>
              </div>
            </div>
          </div>
          <div class="kbf-payout-hint">We'll use this for withdrawals. Double-check to avoid delays.</div>
        </div>

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
              <select id="kbf-profile-municipality" disabled><option value="">Select</option></select>
            </div>
            <div class="kbf-form-group">
              <label>Barangay</label>
              <select id="kbf-profile-barangay" disabled><option value="">Select</option></select>
            </div>
          </div>
          <input type="hidden" name="address" id="kbf-profile-address" value="<?php echo esc_attr($address); ?>">
        </div>

        <div class="kbf-profile-section">
          <div class="kbf-social-links-toggle" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open');">
            <div class="kbf-profile-section-title" style="margin:0;"><i class="ph ph-share-network"></i> Social Links</div>
            <i class="ph ph-caret-down"></i>
          </div>
          <div class="kbf-social-links-body">
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

        <div class="kbf-profile-section">
          <div class="kbf-profile-section-title"><i class="ph ph-chart-bar"></i> Profile Stats</div>
          <div class="kbf-stats-row">
            <div class="kbf-stat-item">
              <i class="ph ph-piggy-bank"></i>
              <div class="stat-value">&#8369;<?php echo number_format($stats_total_raised, 0); ?></div>
              <div class="stat-label">Total Raised</div>
            </div>
            <div class="kbf-stat-item">
              <i class="ph ph-users"></i>
              <div class="stat-value"><?php echo number_format($stats_total_sponsors); ?></div>
              <div class="stat-label">Sponsors</div>
            </div>
            <div class="kbf-stat-item">
              <i class="ph ph-thumbs-up"></i>
              <div class="stat-value"><?php echo number_format($stats_rating, 1); ?>/5</div>
              <div class="stat-label"><?php echo $stats_rating_count; ?> ratings</div>
            </div>
          </div>
        </div>

        <div id="kbf-profile-msg"></div>
        <div id="fundora-didit-msg"></div>

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
          <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSaveProfile('<?php echo $nonce; ?>')">Save Changes</button>
        </div>
      </form>
    </div>

    <div class="kbf-cropper-backdrop" id="kbf-cropper-backdrop" aria-hidden="true">
      <div class="kbf-cropper-modal" role="dialog" aria-modal="true" aria-label="Crop profile photo">
        <div class="kbf-cropper-header"><div class="kbf-cropper-title">Crop your photo</div><button type="button" class="kbf-cropper-close" id="kbf-cropper-close">&times;</button></div>
        <div class="kbf-cropper-sub">Adjust the image to fit the circle.</div>
        <div class="kbf-cropper-stage" id="kbf-cropper-stage"><img id="kbf-cropper-image" class="kbf-cropper-image" alt="Crop preview"><div class="kbf-cropper-mask"></div></div>
        <div class="kbf-cropper-controls"><span style="font-size:12px;color:var(--kbf-slate);">Zoom</span><input type="range" id="kbf-cropper-zoom" min="1" max="3" step="0.01" value="1"></div>
        <div class="kbf-cropper-actions"><button type="button" class="kbf-btn" id="kbf-cropper-cancel">Cancel</button><button type="button" class="kbf-btn kbf-btn-primary" id="kbf-cropper-apply">Confirm photo</button></div>
      </div>
    </div>

    <script>
    if (typeof ajaxurl === 'undefined') var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    window.fundoraDidit = { ajaxurl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', nonce: '<?php echo esc_js(wp_create_nonce('fundora_didit_nonce')); ?>' };
    
    // Payout fields progressive disclosure with dynamic labels
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
        if(!val){
            fieldsWrap.style.display = 'none';
            hint.style.display = 'none';
            return;
        }
        
        fieldsWrap.style.display = 'block';
        nameEl.disabled = false;
        numEl.disabled = false;
        hint.style.display = 'block';
        
        if(val === 'gcash' || val === 'maya_wallet'){
            label1.textContent = 'Account Holder Name';
            label2.textContent = 'Mobile Number';
            nameEl.placeholder = 'Full name on account';
            numEl.placeholder = '09XX XXX XXXX';
            numEl.type = 'text';
            numEl.maxLength = 13;
            hint.textContent = (val === 'gcash') ? 'Payouts sent to the mobile number linked to your GCash account.' : 'Payouts sent to the mobile number linked to your Maya account.';
            formatHint.textContent = 'Format: 09XX XXX XXXX';
        } else if(val === 'card'){
            label1.textContent = 'Cardholder Name';
            label2.textContent = 'Card Number';
            nameEl.placeholder = 'Name as it appears on card';
            numEl.placeholder = 'XXXX XXXX XXXX XXXX';
            numEl.type = 'text';
            numEl.maxLength = 19;
            hint.textContent = 'Card payouts use your cardholder name and card number.';
            formatHint.textContent = 'Format: XXXX XXXX XXXX XXXX';
        }
    };
    document.getElementById('kbf-payout-type')?.addEventListener('change', window.kbfUpdatePayoutFields);
    window.kbfUpdatePayoutFields();

    // Auto-format payout number as user types
    (function(){
        const numEl = document.getElementById('kbf-payout-number');
        const typeSel = document.getElementById('kbf-payout-type');
        if(!numEl || !typeSel) return;
        
        numEl.addEventListener('input', function(){
            const val = this.value.replace(/\D/g,''); // Remove non-digits
            const type = typeSel.value;
            
            if(type === 'gcash' || type === 'maya_wallet'){
                // Format: 09XX XXX XXXX (max 11 digits)
                const trimmed = val.substring(0, 11);
                if(trimmed.length <= 4) this.value = trimmed;
                else if(trimmed.length <= 7) this.value = trimmed.substring(0,4) + ' ' + trimmed.substring(4);
                else this.value = trimmed.substring(0,4) + ' ' + trimmed.substring(4,7) + ' ' + trimmed.substring(7);
            } else if(type === 'card'){
                // Format: XXXX XXXX XXXX XXXX (max 16 digits)
                const trimmed = val.substring(0, 16);
                const groups = trimmed.match(/.{1,4}/g);
                this.value = groups ? groups.join(' ') : trimmed;
            }
        });
    })();

    // Bio char count
    (function(){
        const bio = document.querySelector('textarea[name="bio"]');
        const count = document.getElementById('kbf-profile-bio-count');
        if(!bio || !count) return;
        const update = () => { count.textContent = (bio.value.length || 0) + ' / 250'; };
        bio.addEventListener('input', update); update();
    })();

    // Prevent spaces in social name
    (function(){
        const sn = document.getElementById('kbf-social-name');
        if(sn) sn.addEventListener('input', function(){ if(/\s/.test(this.value)) this.value = this.value.replace(/\s+/g,''); });
    })();

    // Verify Identity Button
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
                  try { 
                      // Strip BOM (\uFEFF) and whitespace
                      const clean = t.replace(/^\uFEFF+/, '').trim();
                      return JSON.parse(clean); 
                  } catch(e) { throw new Error('Server returned invalid JSON: ' + t.substring(0, 100)); }
              }))
              .then(j => {
                  if(j.success && j.data && j.data.url){
                      window.open(j.data.url, '_blank');
                      msgEl.innerHTML = '<div class="kbf-alert kbf-alert-success">Verification started. Check the new window.</div>';
                  } else {
                      const msg = (j.data && j.data.message) || 'Unknown error.';
                      msgEl.innerHTML = '<div class="kbf-alert kbf-alert-error">'+msg+'</div>';
                  }
              })
              .catch(err => {
                  console.error('Didit Verification Error:', err);
                  msgEl.innerHTML = '<div class="kbf-alert kbf-alert-error">Request failed. Check browser console (F12) for details.</div>';
              })
              .finally(() => { btn.disabled=false; btn.textContent='Verify Identity'; });
        });
    })();

    // Save profile with validation
    window.kbfSaveProfile = function(nonce){
        const form = document.getElementById('kbf-profile-form');
        const msgEl = document.getElementById('kbf-profile-msg');
        const btn = document.querySelector('.kbf-profile-save-bar .kbf-btn-primary');
        let isValid = true, errors = [];
        function showErr(input, msg){
            input.classList.add('kbf-input-error');
            const group = input.closest('.kbf-form-group') || input.closest('.kbf-bio-wrap');
            if(group){ let err = group.querySelector('.kbf-field-error'); if(!err){ err = document.createElement('div'); err.className='kbf-field-error'; err.style.marginTop='4px'; group.appendChild(err); } err.textContent=msg; err.style.display='block'; }
            errors.push(msg);
        }
        function clearErr(input){
            input.classList.remove('kbf-input-error');
            const group = input.closest('.kbf-form-group') || input.closest('.kbf-bio-wrap');
            if(group){ const err = group.querySelector('.kbf-field-error'); if(err){ err.textContent=''; err.style.display='none'; } }
        }
        form.querySelectorAll('.kbf-input-error').forEach(clearErr);
        form.querySelectorAll('.kbf-field-error').forEach(el=>{el.textContent='';el.style.display='none';});
        msgEl.innerHTML = '';

        const displayName = form.querySelector('input[name="display_name"]');
        if(!displayName.value.trim()){ showErr(displayName,'Display Name is required.'); isValid=false; }
        const socialName = form.querySelector('input[name="kbf_social_name"]');
        const snVal = socialName.value.trim();
        if(!snVal){ showErr(socialName,'Social Name is required.'); isValid=false; }
        else if(!/^[a-zA-Z0-9_]{2,30}$/.test(snVal)){ showErr(socialName,'2-30 chars, letters/numbers/underscores only.'); isValid=false; }
        const bio = form.querySelector('textarea[name="bio"]');
        if(!bio.value.trim()){ showErr(bio,'Bio/About is required.'); isValid=false; }
        const payoutType = form.querySelector('select[name="payout_type"]');
        const ptVal = payoutType.value.trim();
        if(!ptVal){ showErr(payoutType,'Select a Payout Type.'); isValid=false; }
        const payoutName = form.querySelector('input[name="payout_name"]');
        if(ptVal && !payoutName.value.trim()){ showErr(payoutName,'Account Name is required.'); isValid=false; }
        const payoutNum = form.querySelector('input[name="payout_number"]');
        if(ptVal && !payoutNum.value.trim()){ showErr(payoutNum,'Account Number is required.'); isValid=false; }
        const province = form.querySelector('#kbf-profile-province');
        const addrHidden = document.getElementById('kbf-profile-address');
        if(!province.value.trim() && (!addrHidden || !addrHidden.value.trim())){ showErr(province,'Complete your Address.'); isValid=false; }
        if(typeof window.kbfValidateSocialLinks === 'function' && !window.kbfValidateSocialLinks()){ errors.push('Fix social links.'); isValid=false; }
        if(!isValid){
            if(errors.length > 0) msgEl.innerHTML = '<div class="kbf-alert kbf-alert-error" style="margin-bottom:14px;">'+errors[0]+'</div>';
            const firstErr = form.querySelector('.kbf-input-error');
            if(firstErr){ firstErr.scrollIntoView({behavior:'smooth',block:'center'}); firstErr.focus(); }
            return;
        }
        const fd = new FormData(form);
        if(payoutType){
            const tv = (payoutType.value||'').trim(); fd.set('payout_type',tv);
            if(!tv){ fd.set('payout_name',''); fd.set('payout_number',''); }
            else { fd.set('payout_name',(payoutName.value||'').trim()); fd.set('payout_number',(payoutNum.value||'').trim()); }
        }
        fd.append('action','kbf_save_organizer_profile'); fd.append('nonce',nonce);
        btn.disabled = true; btn.textContent = 'Saving...';
        fetch(ajaxurl,{method:'POST',body:fd})
          .then(async r=>{ const t=await r.text(); try{ return JSON.parse(t.replace(/^\uFEFF/,'').trim()); }catch(e){ console.error('JSON parse failed',e,t); throw e; } })
          .then(function(j){
              const snInput = document.getElementById('kbf-social-name');
              const snErr = document.getElementById('kbf-social-name-error');
              if(snErr){ snErr.textContent=''; snErr.style.display='none'; }
              if(snInput) snInput.classList.remove('kbf-input-error');
              if(j && !j.success){
                  const msg = j.data.message || 'Unknown error.';
                  if(msg.toLowerCase().includes('social name') && snErr){ snErr.textContent=msg; snErr.style.display='block'; snInput.classList.add('kbf-input-error'); msgEl.innerHTML=''; }
                  else msgEl.innerHTML = '<div class="kbf-alert kbf-alert-error">'+msg+'</div>';
                  return;
              }
              msgEl.innerHTML = '<div class="kbf-alert kbf-alert-success">'+j.data.message+'</div>';
              if(j && j.success) setTimeout(()=>{location.reload();},400);
          })
          .catch(err=>{ console.error('Save failed',err); msgEl.innerHTML='<div class="kbf-alert kbf-alert-error">Save failed. Try again.</div>'; })
          .finally(()=>{ btn.disabled=false; btn.textContent='Save Changes'; });
    };
    </script>
    <?php return ob_get_clean();
}
?>
