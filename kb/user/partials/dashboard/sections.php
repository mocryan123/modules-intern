    <div id="kbf-loading-overlay">
      <div class="kbf-loading-mark">
        <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora">
      </div>
    </div>
    <!-- Topbar (Landing-style) -->
    <div class="kbf-mobile-overlay" id="kbf-mobile-overlay"></div>
    <div class="kbf-mobile-menu" id="kbf-mobile-menu">
      <div class="kbf-mobile-menu-header">
        <div class="kbf-brand">
          <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora" style="width:22px;height:22px;object-fit:contain;border-radius:6px;">
          <span class="kbf-brand-text">fundora</span>
        </div>
        <button class="kbf-hamburger" type="button" onclick="kbfCloseMobileMenu()">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/x-lg.svg" alt="">
        </button>
      </div>
      <a href="?kbf_tab=overview" onclick="kbfCloseMobileMenu()">Home</a>
      <a href="?kbf_tab=sponsorships" onclick="kbfCloseMobileMenu()">Supporters</a>
      <a href="?kbf_tab=withdrawals" onclick="kbfCloseMobileMenu()">Cashout</a>
      <a href="?kbf_tab=find_funds" onclick="kbfCloseMobileMenu()">Explore</a>
      <div class="kbf-mobile-menu-actions">
        <a class="kbf-btn kbf-btn-secondary" href="?kbf_tab=profile">Profile</a>
        <a class="kbf-btn kbf-btn-primary" href="?kbf_tab=find_funds">Find Funds</a>
      </div>
    </div>

    <div class="kbf-topbar">
      <div class="kbf-topbar-left">
        <div class="kbf-brand">
          <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora" style="width:22px;height:22px;object-fit:contain;border-radius:6px;">
          <span class="kbf-brand-text">fundora</span>
        </div>
        <nav class="kbf-nav">
          <a href="?kbf_tab=overview" class="<?php echo $tab==='overview'?'active':''; ?>">Home</a>
          <a href="?kbf_tab=sponsorships" class="<?php echo $tab==='sponsorships'?'active':''; ?>">Supporters</a>
          <a href="?kbf_tab=withdrawals" class="<?php echo $tab==='withdrawals'?'active':''; ?>">Cashout</a>
          <a href="?kbf_tab=find_funds" class="<?php echo $tab==='find_funds'?'active':''; ?>">Explore</a>
        </nav>
      </div>
      <div class="kbf-actions">
        <button class="kbf-hamburger" type="button" onclick="kbfToggleMobileMenu()">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/list.svg" alt="">
        </button>
        <?php
          $avatar_url = ($nav_profile && $nav_profile->avatar_url) ? $nav_profile->avatar_url : get_avatar_url($user->ID, ['size'=>64]);
        ?>
        <?php
          $landing_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('landing') : home_url('/');
          $logout_url = wp_logout_url($landing_url);
        ?>
        <div class="kbf-user-menu" id="kbf-user-menu">
          <button class="kbf-dashboard-user" type="button" id="kbf-user-menu-btn" aria-haspopup="true" aria-expanded="false">
            <span class="kbf-dashboard-avatar-wrap">
              <img class="kbf-dashboard-avatar" src="<?php echo esc_url($avatar_url); ?>" alt="User avatar" id="kbf-navbar-avatar">
              <?php if($nav_profile && !empty($nav_profile->is_verified)): ?>
                <span class="kbf-dashboard-verified" aria-hidden="true"></span>
              <?php endif; ?>
            </span>
            <span class="kbf-dashboard-name"><?php echo esc_html($user->display_name); ?></span>
          </button>
          <div class="kbf-user-dropdown" id="kbf-user-dropdown" role="menu" aria-label="User menu">
            <a href="?kbf_tab=profile" role="menuitem">Profile</a>
            <a href="<?php echo esc_url($logout_url); ?>" role="menuitem">Sign out</a>
          </div>
        </div>
      </div>
    </div>

    <div class="kbf-dashboard-shell">

    <div class="kbf-hero-wrap">
      <div class="kbf-hero-banner">
        <h2 class="kbf-hero-title">Welcome, <?php echo esc_html($user->display_name); ?></h2>
        <p class="kbf-hero-sub">Empower change today. Oversee and manage your community-driven impact initiatives.</p>
      </div>
    </div>
    <div class="kbf-tab-content">
      <?php
      if ($tab === 'overview')         echo kbf_dashboard_overview_tab($business_id);
      elseif ($tab === 'sponsorships') echo kbf_dashboard_sponsorships_tab($business_id);
      elseif ($tab === 'withdrawals')  echo kbf_dashboard_withdrawals_tab($business_id);
      elseif ($tab === 'find_funds')   echo kbf_dashboard_find_funds_tab();
      elseif ($tab === 'profile')      echo kbf_dashboard_profile_tab($business_id);
      elseif ($tab === 'fund_details') echo bntm_shortcode_kbf_fund_details();
      elseif ($tab === 'organizer_profile') echo bntm_shortcode_kbf_organizer_profile();
      elseif ($tab === 'sponsor_history') echo bntm_shortcode_kbf_sponsor_history();
      ?>
      </div>
      </div><!-- .kbf-dashboard-shell -->

