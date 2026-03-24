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

    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:10px;">Edit Profile</h3>
      <p style="margin:0 0 18px;color:var(--kbf-slate);font-size:13px;">Keep your information up to date for sponsors and partners.</p>
      <form id="kbf-profile-form" enctype="multipart/form-data">
        <div class="kbf-card" style="padding:18px;margin-bottom:18px;">
          <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <?php
              $avatar = $profile && $profile->avatar_url ? $profile->avatar_url : get_avatar_url($user->ID, ['size'=>96]);
            ?>
            <img src="<?php echo esc_url($avatar); ?>" alt="Profile photo" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--kbf-border);">
            <div style="flex:1;min-width:220px;">
              <div style="font-weight:700;margin-bottom:6px;">Profile Photo</div>
              <input type="file" name="avatar" accept="image/*">
              <div style="font-size:12px;color:var(--kbf-slate);margin-top:6px;">Recommended 800×800px JPG or PNG.</div>
            </div>
          </div>
        </div>

        <div class="kbf-card" style="padding:18px;margin-bottom:18px;">
          <div style="font-weight:700;margin-bottom:12px;">Personal Info</div>
          <div class="kbf-form-row" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;">
            <div class="kbf-form-group">
              <label>Display Name</label>
              <input type="text" value="<?php echo esc_attr($user->display_name); ?>" disabled style="background:var(--kbf-slate-lt);">
            </div>
            <div class="kbf-form-group">
              <label>Email</label>
              <input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled style="background:var(--kbf-slate-lt);">
            </div>
            <div class="kbf-form-group">
              <label>Phone</label>
              <input type="text" name="phone" value="<?php echo esc_attr($phone); ?>" placeholder="+63 9XX XXX XXXX">
            </div>
          </div>
        </div>

        <div class="kbf-card" style="padding:18px;margin-bottom:18px;">
          <div style="font-weight:700;margin-bottom:12px;">Address</div>
          <div class="kbf-form-row" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;">
            <div class="kbf-form-group">
              <label>Province</label>
              <select id="kbf-profile-province">
                <option value="">Select Province</option>
                <?php foreach (kbf_get_provinces() as $p): ?>
                  <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
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

        <div class="kbf-card" style="padding:18px;margin-bottom:18px;">
          <div style="font-weight:700;margin-bottom:12px;">Bio</div>
          <div class="kbf-form-group">
            <label>Bio / About</label>
            <textarea name="bio" rows="4" placeholder="Tell sponsors about yourself or your organization..."><?php echo esc_textarea(isset($profile->bio) ? $profile->bio : ''); ?></textarea>
          </div>
        </div>
        <div id="kbf-profile-msg"></div>
        <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSaveProfile('<?php echo $nonce; ?>')">Save Changes</button>
      </form>

      <?php if($profile): ?>
      <hr class="kbf-divider">
      <div class="kbf-stats" style="margin-top:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
        <div class="kbf-stat" style="background:#fff;border:1px solid var(--kbf-border);border-radius:16px;padding:14px 16px;display:flex;align-items:center;gap:12px;">
          <div class="kbf-stat-icon" style="background:#142a57;">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/currency-dollar.svg" alt="" width="16" height="16" style="filter:invert(100%);">
          </div>
          <div>
            <div style="font-size:11px;font-weight:700;color:#64748b;letter-spacing:.6px;">TOTAL RAISED</div>
            <div style="font-size:18px;font-weight:800;color:#0f172a;">₱<?php echo number_format($profile->total_raised,0); ?></div>
          </div>
        </div>
        <div class="kbf-stat" style="background:#fff;border:1px solid var(--kbf-border);border-radius:16px;padding:14px 16px;display:flex;align-items:center;gap:12px;">
          <div class="kbf-stat-icon" style="background:#e0951a;">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/people.svg" alt="" width="16" height="16" style="filter:invert(100%);">
          </div>
          <div>
            <div style="font-size:11px;font-weight:700;color:#64748b;letter-spacing:.6px;">TOTAL SPONSORS</div>
            <div style="font-size:18px;font-weight:800;color:#0f172a;"><?php echo number_format($profile->total_sponsors); ?></div>
          </div>
        </div>
        <div class="kbf-stat" style="background:#fff;border:1px solid var(--kbf-border);border-radius:16px;padding:14px 16px;display:flex;align-items:center;gap:12px;">
          <div class="kbf-stat-icon" style="background:#f1a51a;">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg" alt="" width="16" height="16" style="filter:invert(100%);">
          </div>
          <div>
            <div style="font-size:11px;font-weight:700;color:#64748b;letter-spacing:.6px;">RATING</div>
            <div style="font-size:18px;font-weight:800;color:#0f172a;"><?php echo number_format($profile->rating,1); ?>/5</div>
            <div style="font-size:12px;color:#64748b;"><?php echo $profile->rating_count; ?> reviews</div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <script>
    window.kbfSaveProfile = function(nonce) {
        const form = document.getElementById('kbf-profile-form');
        const fd = new FormData(form);
        fd.append('action','kbf_save_organizer_profile');
        fd.append('nonce',nonce);
        const btn = form.querySelector('.kbf-btn-primary');
        btn.disabled=true; btn.textContent='Saving...';
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            document.getElementById('kbf-profile-msg').innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if (j && j.success) {
                setTimeout(function(){ location.reload(); }, 600);
            }
        }).catch(function(){
            document.getElementById('kbf-profile-msg').innerHTML='<div class="kbf-alert kbf-alert-error">Save failed. Please try again.</div>';
        }).finally(function(){
            btn.disabled=false; btn.textContent='Save Changes';
        });
    };
    </script>
    <?php return ob_get_clean();
}


// ============================================================
// DASHBOARD TAB: Find Funds (sponsor view -- browse & support)
// ============================================================

