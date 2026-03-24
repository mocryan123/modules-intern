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
    $nonce = wp_create_nonce('kbf_organizer_profile');

    ob_start(); ?>
    <div class="kbf-section">
      <h3 class="kbf-section-title" style="margin-bottom:18px;">Organizer Profile</h3>
      <div class="kbf-alert kbf-alert-info" style="margin-bottom:18px;">
        This profile is publicly visible when sponsors view your funds.
      </div>
      <form id="kbf-profile-form" enctype="multipart/form-data">
        <div class="kbf-form-row">
          <div class="kbf-form-group">
            <label>Display Name</label>
            <input type="text" value="<?php echo esc_attr($user->display_name); ?>" disabled style="background:var(--kbf-slate-lt);">
            <small>Set in WordPress user settings.</small>
          </div>
          <div class="kbf-form-group">
            <label>Profile Photo</label>
            <input type="file" name="avatar" accept="image/*">
            <?php if($profile && $profile->avatar_url): ?>
              <div style="margin-top:8px;"><img src="<?php echo esc_url($profile->avatar_url); ?>" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--kbf-border);"></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="kbf-form-group">
          <label>Bio / About</label>
          <textarea name="bio" rows="4" placeholder="Tell sponsors about yourself or your organization..."><?php echo esc_textarea(isset($profile->bio) ? $profile->bio : ''); ?></textarea>
        </div>
        <div class="kbf-form-row-3">
          <div class="kbf-form-group">
            <label>Facebook URL</label>
            <input type="url" name="social_facebook" value="<?php echo esc_attr(isset($socials['facebook']) ? $socials['facebook'] : ''); ?>" placeholder="https://facebook.com/...">
          </div>
          <div class="kbf-form-group">
            <label>Instagram URL</label>
            <input type="url" name="social_instagram" value="<?php echo esc_attr(isset($socials['instagram']) ? $socials['instagram'] : ''); ?>" placeholder="https://instagram.com/...">
          </div>
          <div class="kbf-form-group">
            <label>Twitter/X URL</label>
            <input type="url" name="social_twitter" value="<?php echo esc_attr(isset($socials['twitter']) ? $socials['twitter'] : ''); ?>" placeholder="https://x.com/...">
          </div>
        </div>
        <div id="kbf-profile-msg"></div>
        <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSaveProfile('<?php echo $nonce; ?>')">Save Profile</button>
      </form>

      <?php if($profile): ?>
      <hr class="kbf-divider">
      <div class="kbf-stats" style="margin-top:18px;">
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#0f2044,#243b78);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Total Raised</div><div class="kbf-stat-value">₱<?php echo number_format($profile->total_raised,0); ?></div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#e8a020,#d4911a);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Total Sponsors</div><div class="kbf-stat-value"><?php echo number_format($profile->total_sponsors); ?></div></div>
        </div>
        <div class="kbf-stat">
          <div class="kbf-stat-icon" style="background:linear-gradient(135deg,#fbbf24,#f59e0b);">
            <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
          </div>
          <div><div class="kbf-stat-label">Rating</div><div class="kbf-stat-value"><?php echo number_format($profile->rating,1); ?>/5</div><div class="kbf-stat-sub"><?php echo $profile->rating_count; ?> reviews</div></div>
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
            btn.disabled=false; btn.textContent='Save Profile';
        });
    };
    </script>
    <?php return ob_get_clean();
}


// ============================================================
// DASHBOARD TAB: Find Funds (sponsor view -- browse & support)
// ============================================================

