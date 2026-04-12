    <?php
      $landing_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('landing') : home_url('/');
      $signin_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : home_url('/wp-login.php');
      $signup_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signup') : $signin_url;
      $avatar_url = ($is_logged_in && $nav_profile && $nav_profile->avatar_url)
        ? $nav_profile->avatar_url
        : '';
      $logout_url = $is_logged_in ? wp_logout_url($landing_url) : '';
    ?>
    <?php if (function_exists('kbf_render_loading_overlay')): ?>
      <?php echo kbf_render_loading_overlay(); ?>
    <?php endif; ?>
    <!-- Topbar (Landing-style) -->
    <div class="kbf-mobile-overlay" id="kbf-mobile-overlay"></div>
    <div class="kbf-mobile-menu" id="kbf-mobile-menu">
      <a href="?kbf_tab=overview" class="<?php echo !$is_logged_in ? 'kbf-auth-required' : ''; ?>" data-auth-tab="Home" onclick="kbfCloseMobileMenu()">Home</a>
      <a href="?kbf_tab=sponsorships" class="<?php echo !$is_logged_in ? 'kbf-auth-required' : ''; ?>" data-auth-tab="Supporters" onclick="kbfCloseMobileMenu()">Supporters</a>
      <a href="?kbf_tab=withdrawals" class="<?php echo !$is_logged_in ? 'kbf-auth-required' : ''; ?>" data-auth-tab="Cashout" onclick="kbfCloseMobileMenu()">Cashout</a>
      <a href="?kbf_tab=find_funds" onclick="kbfCloseMobileMenu()">Explore</a>
        <div class="kbf-mobile-menu-actions">
          <?php if($is_logged_in): ?>
            <a class="kbf-btn kbf-btn-secondary" href="<?php echo esc_url(add_query_arg('kbf_tab','profile', kbf_get_page_url('dashboard'))); ?>">Profile</a>
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($logout_url); ?>">Sign out</a>
          <?php else: ?>
            <a class="kbf-btn kbf-btn-secondary kbf-auth-required" data-auth-tab="Profile" href="<?php echo esc_url(add_query_arg('kbf_tab','profile', kbf_get_page_url('dashboard'))); ?>">Profile</a>
            <a class="kbf-btn kbf-btn-primary" href="?kbf_tab=find_funds">Find Funds</a>
          <?php endif; ?>
        </div>
    </div>

    <div class="kbf-topbar">
      <div class="kbf-topbar-left">
        <div class="kbf-brand">
          <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logobanner.png'); ?>" alt="fundora" style="width:auto;height:25px;object-fit:contain;border-radius:6px;">
          
        </div>
        <nav class="kbf-nav">
          <a href="?kbf_tab=overview" class="<?php echo $tab==='overview'?'active':''; ?> <?php echo !$is_logged_in ? 'kbf-auth-required' : ''; ?>" data-auth-tab="Home">Home</a>
          <a href="?kbf_tab=sponsorships" class="<?php echo $tab==='sponsorships'?'active':''; ?> <?php echo !$is_logged_in ? 'kbf-auth-required' : ''; ?>" data-auth-tab="Supporters">Supporters</a>
          <a href="?kbf_tab=withdrawals" class="<?php echo $tab==='withdrawals'?'active':''; ?> <?php echo !$is_logged_in ? 'kbf-auth-required' : ''; ?>" data-auth-tab="Cashout">Cashout</a>
          <a href="?kbf_tab=find_funds" class="<?php echo $tab==='find_funds'?'active':''; ?>">Explore</a>
        </nav>
      </div>
      <div class="kbf-actions">
        <button class="kbf-hamburger" type="button" onclick="kbfToggleMobileMenu()">
          <i id="kbf-mobile-menu-icon" data-open="ph ph-x" data-close="ph ph-list" class="ph ph-list kbf-icon" aria-hidden="true"></i>
        </button>
        <?php if($is_logged_in): ?>
          <div class="kbf-user-menu" id="kbf-user-menu">
            <button class="kbf-dashboard-user" type="button" id="kbf-user-menu-btn" aria-haspopup="true" aria-expanded="false">
              <span class="kbf-dashboard-avatar-wrap">
                <?php if($avatar_url): ?>
                  <img class="kbf-dashboard-avatar" src="<?php echo esc_url($avatar_url); ?>" alt="User avatar" id="kbf-navbar-avatar">
                <?php else: ?>
                  <span class="kbf-dashboard-avatar-fallback" aria-hidden="true">
                    <i class="ph ph-user kbf-icon kbf-stat-icon-img" aria-hidden="true"></i>
                  </span>
                <?php endif; ?>
                <?php if($nav_profile && !empty($nav_profile->is_verified)): ?>
                  <span class="kbf-dashboard-verified" aria-hidden="true"><i class="ph-fill ph-seal-check kbf-icon" aria-hidden="true"></i></span>
                <?php endif; ?>
              </span>
              <span class="kbf-dashboard-name"><?php echo esc_html($user->display_name); ?></span>
            </button>
            <div class="kbf-user-dropdown" id="kbf-user-dropdown" role="menu" aria-label="User menu">
              <a href="?kbf_tab=profile" role="menuitem">Profile</a>
              <a href="<?php echo esc_url($logout_url); ?>" role="menuitem">Sign out</a>
            </div>
          </div>
        <?php else: ?>
          <a class="kbf-btn kbf-btn-secondary" href="<?php echo esc_url($signin_url); ?>">Sign in</a>
          <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($signup_url); ?>">Create account</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="kbf-dashboard-shell">

    <div class="kbf-hero-wrap">
      <div class="kbf-hero-banner">
        <?php if($is_logged_in): ?>
          <h2 class="kbf-hero-title">Welcome, <?php echo esc_html($user->display_name); ?></h2>
          <p class="kbf-hero-sub">Empower change today. Oversee and manage your community-driven impact initiatives.</p>
        <?php else: ?>
          <h2 class="kbf-hero-title">Explore fundraisers</h2>
          <p class="kbf-hero-sub">Browse verified campaigns and sign in when you're ready to save or manage funds.</p>
        <?php endif; ?>
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



