<?php
/**
 * Module Name: CivicHub
 * Module Slug: ch
 * Description: Community-based forum platform where residents can share concerns, discuss local issues, and express opinions.
 * Version: 1.0.0
 * Author: BNTM
 * Icon: forum
 */

if (!defined('ABSPATH')) exit;

define('BNTM_CH_PATH', dirname(__FILE__) . '/');
define('BNTM_CH_URL', plugin_dir_url(__FILE__));

// ============================================================
// CORE MODULE FUNCTIONS
// ============================================================

function ch_get_guidelines_html() {
    $saved = get_option('ch_community_guidelines', '');
    if ($saved) return wp_kses_post($saved);
    // Default guidelines
    return '<h4>Our Community Standards</h4>
<p>Welcome to our community forum! To ensure a positive and respectful environment for all members, please follow these guidelines:</p>
<h5>Be Respectful</h5>
<ul>
<li>Treat others with kindness and respect</li>
<li>No harassment, bullying, or hate speech</li>
<li>Respect differing opinions and backgrounds</li>
</ul>
<h5>Content Guidelines</h5>
<ul>
<li>Post relevant and meaningful content</li>
<li>No spam, misleading information, or inappropriate content</li>
<li>Use appropriate language and avoid offensive material</li>
</ul>
<h5>Reporting</h5>
<ul>
<li>Report violations using the report buttons</li>
<li>Provide details when reporting to help moderators</li>
<li>False reports may result in account restrictions</li>
</ul>
<h5>Consequences</h5>
<p>Violations may result in content removal, temporary suspension, or permanent bans. We reserve the right to moderate content at our discretion.</p>
<p><strong>Thank you for helping keep our community safe and welcoming!</strong></p>';
}

function bntm_ch_get_pages() {
    return [
        'CivicHub Dashboard' => '[ch_dashboard]',
        'Forum Feed'         => '[ch_feed]',
        'Post View'          => '[ch_post_view]',
        'Login / Register'   => '[ch_auth]',
    ];
}

function bntm_ch_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    return [
        'ch_categories' => "CREATE TABLE {$prefix}ch_categories (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            name VARCHAR(150) NOT NULL,
            slug VARCHAR(150) NOT NULL,
            description TEXT,
            color VARCHAR(10) DEFAULT '#FF7551',
            icon VARCHAR(50) DEFAULT 'forum',
            post_count INT UNSIGNED DEFAULT 0,
            follower_count INT UNSIGNED DEFAULT 0,
            sort_order INT DEFAULT 0,
            status ENUM('active','archived') DEFAULT 'active',
            is_private TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_slug (slug),
            INDEX idx_private (is_private)
        ) {$charset};",

        'ch_posts' => "CREATE TABLE {$prefix}ch_posts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            title VARCHAR(300) NOT NULL,
            content LONGTEXT NOT NULL,
            media_urls TEXT,
            tags VARCHAR(500),
            is_anonymous TINYINT(1) DEFAULT 0,
            guest_name VARCHAR(100) DEFAULT NULL,
            is_pinned TINYINT(1) DEFAULT 0,
            status ENUM('active','removed','pending','hidden') DEFAULT 'active',
            vote_count INT DEFAULT 0,
            comment_count INT UNSIGNED DEFAULT 0,
            view_count INT UNSIGNED DEFAULT 0,
            share_count INT UNSIGNED DEFAULT 0,
            report_count INT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_user (user_id),
            INDEX idx_category (category_id),
            INDEX idx_status (status),
            FULLTEXT idx_search (title, content)
        ) {$charset};",

        'ch_comments' => "CREATE TABLE {$prefix}ch_comments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            parent_id BIGINT UNSIGNED DEFAULT 0,
            content TEXT NOT NULL,
            is_anonymous TINYINT(1) DEFAULT 0,
            guest_name VARCHAR(100) DEFAULT NULL,
            vote_count INT DEFAULT 0,
            status ENUM('active','removed','hidden') DEFAULT 'active',
            report_count INT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_post (post_id),
            INDEX idx_user (user_id),
            INDEX idx_parent (parent_id)
        ) {$charset};",

        'ch_votes' => "CREATE TABLE {$prefix}ch_votes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            target_type ENUM('post','comment') NOT NULL,
            target_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            value TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (user_id, target_type, target_id),
            INDEX idx_target (target_type, target_id)
        ) {$charset};",

        'ch_follows' => "CREATE TABLE {$prefix}ch_follows (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_follow (user_id, category_id)
        ) {$charset};",

        'ch_bookmarks' => "CREATE TABLE {$prefix}ch_bookmarks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_bookmark (user_id, post_id)
        ) {$charset};",

        'ch_notifications' => "CREATE TABLE {$prefix}ch_notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            type ENUM('reply','mention','vote','announcement','report_resolved') NOT NULL,
            actor_id BIGINT UNSIGNED DEFAULT 0,
            post_id BIGINT UNSIGNED DEFAULT 0,
            comment_id BIGINT UNSIGNED DEFAULT 0,
            message TEXT,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_read (user_id, is_read)
        ) {$charset};",

        'ch_announcements' => "CREATE TABLE {$prefix}ch_announcements (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            admin_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'ch_reports' => "CREATE TABLE {$prefix}ch_reports (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            reporter_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            target_type ENUM('post','comment','user') NOT NULL,
            target_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            reason VARCHAR(100) NOT NULL,
            details TEXT,
            status ENUM('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
            reviewed_by BIGINT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_target (target_type, target_id)
        ) {$charset};",

        'ch_user_profiles' => "CREATE TABLE {$prefix}ch_user_profiles (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL UNIQUE,
            display_name VARCHAR(100),
            bio TEXT,
            location VARCHAR(150),
            avatar_url VARCHAR(500),
            is_anonymous TINYINT(1) DEFAULT 0,
            karma_points INT DEFAULT 0,
            post_count INT UNSIGNED DEFAULT 0,
            comment_count INT UNSIGNED DEFAULT 0,
            status ENUM('active','suspended','banned') DEFAULT 'active',
            ban_reason TEXT,
            ban_expires DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id)
        ) {$charset};",

        'ch_activity_logs' => "CREATE TABLE {$prefix}ch_activity_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(50),
            target_id BIGINT UNSIGNED DEFAULT 0,
            details TEXT,
            ip_address VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin (admin_id),
            INDEX idx_created (created_at)
        ) {$charset};",
    ];
}

function bntm_ch_get_shortcodes() {
    return [
        'ch_dashboard' => 'bntm_shortcode_ch',
        'ch_feed'      => 'bntm_shortcode_ch_feed',
        'ch_post_view' => 'bntm_shortcode_ch_post_view',
        'ch_auth'      => 'bntm_shortcode_ch_auth',
    ];
}

function bntm_ch_create_tables() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_ch_get_tables();
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
    // Ensure new columns exist on existing installs
    $cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ch_categories LIKE 'is_private'");
    if (empty($cols)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}ch_categories ADD COLUMN is_private TINYINT(1) DEFAULT 0 AFTER status");
    }
    $post_cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ch_posts LIKE 'guest_name'");
    if (empty($post_cols)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}ch_posts ADD COLUMN guest_name VARCHAR(100) DEFAULT NULL AFTER is_anonymous");
    }
    $cm_cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ch_comments LIKE 'guest_name'");
    if (empty($cm_cols)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}ch_comments ADD COLUMN guest_name VARCHAR(100) DEFAULT NULL AFTER is_anonymous");
    }
    return count($tables);
}

// ============================================================
// FRONTEND: LOGIN / REGISTER PAGE
// ============================================================

function bntm_shortcode_ch_auth() {
    // If already logged in, redirect to feed
    if (is_user_logged_in()) {
        $feed_page = get_page_by_path('forum-feed');
        $feed_url  = $feed_page ? get_permalink($feed_page) : home_url('/forum-feed/');
        wp_redirect($feed_url);
        exit;
    }

    $redirect = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : '';
    $active   = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';

    ob_start(); ?>
    <div class="ch-auth-wrap">
        <div class="ch-auth-card">

            <div class="ch-auth-brand">
                <div class="ch-auth-logo">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="ch-auth-brand-name">CivicHub</div>
                    <div class="ch-auth-brand-tagline">Community Forum</div>
                </div>
            </div>

            <div class="ch-auth-tabs">
                <a href="?tab=login<?php echo $redirect ? '&redirect_to='.urlencode($redirect) : ''; ?>"
                   class="ch-auth-tab <?php echo $active === 'login' ? 'active' : ''; ?>">Sign In</a>
                <a href="?tab=register<?php echo $redirect ? '&redirect_to='.urlencode($redirect) : ''; ?>"
                   class="ch-auth-tab <?php echo $active === 'register' ? 'active' : ''; ?>">Create Account</a>
            </div>

            <div id="ch-auth-msg"></div>

            <?php if ($active === 'login'): ?>
            <!-- LOGIN FORM -->
            <div class="ch-auth-form" id="ch-login-form">
                <div class="ch-field-group">
                    <label class="ch-label">Username or Email</label>
                    <input type="text" id="ch-login-user" class="ch-input" placeholder="Enter your username or email" autocomplete="username">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Password</label>
                    <div class="ch-password-wrap">
                        <input type="password" id="ch-login-pass" class="ch-input" placeholder="Enter your password" autocomplete="current-password">
                        <button type="button" class="ch-password-toggle" onclick="chTogglePassword('ch-login-pass', this)" tabindex="-1">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="ch-auth-row">
                    <label class="ch-checkbox-label">
                        <input type="checkbox" id="ch-login-remember"> Remember me
                    </label>
                    <a href="<?php echo wp_lostpassword_url(); ?>" class="ch-auth-link">Forgot password?</a>
                </div>
                <button class="ch-btn ch-btn-primary ch-btn-full ch-auth-submit" id="ch-login-btn"
                        onclick="chSubmitLogin('<?php echo wp_create_nonce('ch_auth_nonce'); ?>', '<?php echo esc_js($redirect); ?>')">
                    Sign In
                </button>
                <p class="ch-auth-switch">
                    New to CivicHub?
                    <a href="?tab=register<?php echo $redirect ? '&redirect_to='.urlencode($redirect) : ''; ?>" class="ch-auth-link">Create an account</a>
                </p>
            </div>

            <?php else: ?>
            <!-- REGISTER FORM -->
            <div class="ch-auth-form" id="ch-register-form">
                <div class="ch-field-row">
                    <div class="ch-field-group ch-field-half">
                        <label class="ch-label">First Name</label>
                        <input type="text" id="ch-reg-firstname" class="ch-input" placeholder="First name" autocomplete="given-name">
                    </div>
                    <div class="ch-field-group ch-field-half">
                        <label class="ch-label">Last Name</label>
                        <input type="text" id="ch-reg-lastname" class="ch-input" placeholder="Last name" autocomplete="family-name">
                    </div>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Username <span class="ch-required">*</span></label>
                    <input type="text" id="ch-reg-username" class="ch-input" placeholder="Choose a username" autocomplete="username">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Email Address <span class="ch-required">*</span></label>
                    <input type="email" id="ch-reg-email" class="ch-input" placeholder="your@email.com" autocomplete="email">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Password <span class="ch-required">*</span></label>
                    <div class="ch-password-wrap">
                        <input type="password" id="ch-reg-pass" class="ch-input" placeholder="Choose a strong password" autocomplete="new-password">
                        <button type="button" class="ch-password-toggle" onclick="chTogglePassword('ch-reg-pass', this)" tabindex="-1">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="ch-password-strength" id="ch-pass-strength"></div>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Location <span class="ch-optional">(optional)</span></label>
                    <input type="text" id="ch-reg-location" class="ch-input" placeholder="e.g., Barangay San Jose, Cagayan de Oro">
                </div>
                <div class="ch-field-group">
                    <label class="ch-checkbox-label">
                        <input type="checkbox" id="ch-reg-terms" required>
                        I agree to the <a href="#" class="ch-auth-link" onclick="chOpenModal('ch-modal-guidelines'); return false;">Community Guidelines</a>
                    </label>
                </div>
                <button class="ch-btn ch-btn-primary ch-btn-full ch-auth-submit" id="ch-register-btn"
                        onclick="chSubmitRegister('<?php echo wp_create_nonce('ch_auth_nonce'); ?>', '<?php echo esc_js($redirect); ?>')">
                    Create Account
                </button>
                <p class="ch-auth-switch">
                    Already have an account?
                    <a href="?tab=login<?php echo $redirect ? '&redirect_to='.urlencode($redirect) : ''; ?>" class="ch-auth-link">Sign in</a>
                </p>
            </div>
            <?php endif; ?>

        </div>

        <p class="ch-auth-footer">
            By joining, you agree to keep our community respectful and constructive.
        </p>
    </div>

    <style>
    /* Auth page inherits ch_global_styles tokens */
    @media (max-width: 500px) { .ch-auth-card { padding: 22px 16px; } }
    </style>

    <script>
    (function(){
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

        window.chTogglePassword = function(inputId, btn) {
            const inp = document.getElementById(inputId);
            if (!inp) return;
            const isPass = inp.type === 'password';
            inp.type = isPass ? 'text' : 'password';
            btn.innerHTML = isPass
                ? '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
                : '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        };

        // Password strength meter
        const passInput = document.getElementById('ch-reg-pass');
        const strengthBar = document.getElementById('ch-pass-strength');
        if (passInput && strengthBar) {
            passInput.addEventListener('input', function() {
                const val = this.value;
                let score = 0;
                if (val.length >= 8)          score++;
                if (/[A-Z]/.test(val))        score++;
                if (/[0-9]/.test(val))        score++;
                if (/[^A-Za-z0-9]/.test(val)) score++;
                strengthBar.setAttribute('data-strength', val.length === 0 ? 0 : score);
            });
        }

        window.chSubmitLogin = function(nonce, redirect) {
            const user     = document.getElementById('ch-login-user').value.trim();
            const pass     = document.getElementById('ch-login-pass').value;
            const remember = document.getElementById('ch-login-remember').checked ? 1 : 0;
            const msgEl    = document.getElementById('ch-auth-msg');
            const btn      = document.getElementById('ch-login-btn');

            if (!user || !pass) { msgEl.innerHTML = '<div class="bntm-notice-error">Please fill in all fields.</div>'; return; }

            btn.disabled = true; btn.textContent = 'Signing in…';
            msgEl.innerHTML = '';

            const fd = new FormData();
            fd.append('action',      'ch_login');
            fd.append('username',    user);
            fd.append('password',    pass);
            fd.append('remember',    remember);
            fd.append('redirect_to', redirect);
            fd.append('nonce',       nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    msgEl.innerHTML = '<div class="bntm-notice-success">Welcome back! Redirecting…</div>';
                    setTimeout(() => { window.location.href = json.data.redirect || redirect || window.location.href; }, 800);
                } else {
                    msgEl.innerHTML = '<div class="bntm-notice-error">' + (json.data?.message || 'Login failed. Please try again.') + '</div>';
                    btn.disabled = false; btn.textContent = 'Sign In';
                }
            })
            .catch(() => {
                msgEl.innerHTML = '<div class="bntm-notice-error">Network error. Please try again.</div>';
                btn.disabled = false; btn.textContent = 'Sign In';
            });
        };

        window.chSubmitRegister = function(nonce, redirect) {
            const username  = document.getElementById('ch-reg-username').value.trim();
            const email     = document.getElementById('ch-reg-email').value.trim();
            const pass      = document.getElementById('ch-reg-pass').value;
            const firstname = document.getElementById('ch-reg-firstname').value.trim();
            const lastname  = document.getElementById('ch-reg-lastname').value.trim();
            const location  = document.getElementById('ch-reg-location').value.trim();
            const terms     = document.getElementById('ch-reg-terms').checked;
            const msgEl     = document.getElementById('ch-auth-msg');
            const btn       = document.getElementById('ch-register-btn');

            if (!username || !email || !pass) { msgEl.innerHTML = '<div class="bntm-notice-error">Username, email, and password are required.</div>'; return; }
            if (!terms)    { msgEl.innerHTML = '<div class="bntm-notice-error">Please agree to the Community Guidelines.</div>'; return; }
            if (pass.length < 8) { msgEl.innerHTML = '<div class="bntm-notice-error">Password must be at least 8 characters.</div>'; return; }

            btn.disabled = true; btn.textContent = 'Creating account…';
            msgEl.innerHTML = '';

            const fd = new FormData();
            fd.append('action',     'ch_register');
            fd.append('username',   username);
            fd.append('email',      email);
            fd.append('password',   pass);
            fd.append('first_name', firstname);
            fd.append('last_name',  lastname);
            fd.append('location',   location);
            fd.append('redirect_to',redirect);
            fd.append('nonce',      nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    msgEl.innerHTML = '<div class="bntm-notice-success">Account created! Signing you in…</div>';
                    setTimeout(() => { window.location.href = json.data.redirect || redirect || window.location.href; }, 1000);
                } else {
                    msgEl.innerHTML = '<div class="bntm-notice-error">' + (json.data?.message || 'Registration failed. Please try again.') + '</div>';
                    btn.disabled = false; btn.textContent = 'Create Account';
                }
            })
            .catch(() => {
                msgEl.innerHTML = '<div class="bntm-notice-error">Network error. Please try again.</div>';
                btn.disabled = false; btn.textContent = 'Create Account';
            });
        };

        // Submit login on Enter
        ['ch-login-user','ch-login-pass'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') document.getElementById('ch-login-btn')?.click();
            });
        });
        ['ch-reg-username','ch-reg-email','ch-reg-pass','ch-reg-location'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') document.getElementById('ch-register-btn')?.click();
            });
        });
    })();
    </script>

    <!-- Community Guidelines Modal (for registration page) -->
    <div id="ch-modal-guidelines" class="ch-modal-overlay"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;align-items:center;justify-content:center;">
        <div class="ch-modal" style="background:#fff;border-radius:16px;max-width:540px;width:90%;max-height:80vh;overflow:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <div class="ch-modal-header" style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f3f4f6;">
                <h3 style="margin:0;font-size:18px;font-weight:700;color:var(--ch-text);">Community Guidelines</h3>
                <button onclick="document.getElementById('ch-modal-guidelines').style.display='none';document.body.style.overflow='';"
                        style="background:none;border:none;font-size:22px;cursor:pointer;color:#9ca3af;line-height:1;">&times;</button>
            </div>
            <div style="padding:24px;font-size:14px;color:var(--ch-text-muted);line-height:1.7;">
                <?php echo ch_get_guidelines_html(); ?>
            </div>
            <div style="padding:16px 24px;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;">
                <button onclick="document.getElementById('ch-modal-guidelines').style.display='none';document.body.style.overflow='';"
                        class="ch-btn ch-btn-primary">I Understand</button>
            </div>
        </div>
    </div>
    <script>
    window.chOpenModal = window.chOpenModal || function(id) {
        var el = document.getElementById(id);
        if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    };
    window.chCloseModal = window.chCloseModal || function(id) {
        var el = document.getElementById(id);
        if (el) { el.style.display = 'none'; document.body.style.overflow = ''; }
    };
    // Close on overlay click
    document.addEventListener('click', function(e) {
        if (e.target.id === 'ch-modal-guidelines') {
            e.target.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
    </script>
    <?php
    echo ch_global_styles();
    echo ch_global_scripts();
    return ob_get_clean();
}

// ============================================================
// AJAX: LOGIN & REGISTER
// ============================================================

add_action('wp_ajax_nopriv_ch_login',    'bntm_ajax_ch_login');
add_action('wp_ajax_nopriv_ch_register', 'bntm_ajax_ch_register');

// ---- Open Graph meta tags for post sharing ----
add_action('wp_head', 'bntm_ch_og_meta_tags');

// Ensure viewport meta is present for mobile
add_action('wp_head', function() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">' . "\n";
}, 1);
function bntm_ch_og_meta_tags() {
    $rand_id = sanitize_text_field($_GET['view_post'] ?? '');
    if (!$rand_id) return;

    global $wpdb;
    $post = $wpdb->get_row($wpdb->prepare(
        "SELECT p.title, p.content, p.media_urls, p.is_anonymous,
                u.display_name as author_name
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
         WHERE p.rand_id = %s AND p.status = 'active'",
        $rand_id
    ));
    if (!$post) return;

    $title       = esc_attr($post->title);
    $description = esc_attr(wp_strip_all_tags(wp_trim_words($post->content, 30)));
    $url         = esc_url(set_url_scheme('http' . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
    $site_name   = esc_attr(get_bloginfo('name'));

    // Use first attached image as og:image, fall back to site icon
    $image = '';
    if ($post->media_urls) {
        $media = json_decode($post->media_urls, true);
        foreach ((array)$media as $m) {
            if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $m)) {
                $image = esc_url($m);
                break;
            }
        }
    }
    if (!$image) {
        $icon_id = get_option('site_icon');
        if ($icon_id) $image = esc_url(wp_get_attachment_image_url($icon_id, 'full'));
    }

    echo "\n<!-- CivicHub OG Tags -->\n";
    echo "<meta property=\"og:type\"        content=\"article\" />\n";
    echo "<meta property=\"og:title\"       content=\"{$title}\" />\n";
    echo "<meta property=\"og:description\" content=\"{$description}\" />\n";
    echo "<meta property=\"og:url\"         content=\"{$url}\" />\n";
    echo "<meta property=\"og:site_name\"   content=\"{$site_name}\" />\n";
    if ($image) echo "<meta property=\"og:image\" content=\"{$image}\" />\n";
    echo "<meta name=\"twitter:card\"        content=\"summary_large_image\" />\n";
    echo "<meta name=\"twitter:title\"       content=\"{$title}\" />\n";
    echo "<meta name=\"twitter:description\" content=\"{$description}\" />\n";
    if ($image) echo "<meta name=\"twitter:image\" content=\"{$image}\" />\n";
    echo "<!-- /CivicHub OG Tags -->\n";
}

function bntm_ajax_ch_login() {
    check_ajax_referer('ch_auth_nonce', 'nonce');

    $username    = sanitize_text_field($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';
    $remember    = !empty($_POST['remember']);
    $redirect_to = esc_url_raw($_POST['redirect_to'] ?? '');

    if (!$username || !$password) {
        wp_send_json_error(['message' => 'Username and password are required.']);
    }

    $credentials = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    ];

    $user = wp_signon($credentials, is_ssl());

    if (is_wp_error($user)) {
        $msg = $user->get_error_code() === 'incorrect_password' || $user->get_error_code() === 'invalid_username'
            ? 'Incorrect username or password.'
            : $user->get_error_message();
        wp_send_json_error(['message' => strip_tags($msg)]);
    }

    // Ensure CivicHub profile exists
    ch_ensure_profile($user->ID);

    $feed_page   = get_page_by_path('forum-feed');
    $default_url = $feed_page ? get_permalink($feed_page) : home_url('/forum-feed/');
    $redirect    = $redirect_to ?: $default_url;

    wp_send_json_success(['redirect' => $redirect]);
}

function bntm_ajax_ch_register() {
    check_ajax_referer('ch_auth_nonce', 'nonce');

    $username    = sanitize_user($_POST['username'] ?? '');
    $email       = sanitize_email($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $first_name  = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name   = sanitize_text_field($_POST['last_name'] ?? '');
    $location    = sanitize_text_field($_POST['location'] ?? '');
    $redirect_to = esc_url_raw($_POST['redirect_to'] ?? '');

    // Validate
    if (!$username || !$email || !$password) {
        wp_send_json_error(['message' => 'Username, email, and password are required.']);
    }
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address.']);
    }
    if (strlen($password) < 8) {
        wp_send_json_error(['message' => 'Password must be at least 8 characters.']);
    }
    if (username_exists($username)) {
        wp_send_json_error(['message' => 'That username is already taken.']);
    }
    if (email_exists($email)) {
        wp_send_json_error(['message' => 'An account with that email already exists.']);
    }

    // Create WordPress user
    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
    }

    // Set display name
    $display_name = trim("$first_name $last_name") ?: $username;
    wp_update_user(['ID' => $user_id, 'first_name' => $first_name, 'last_name' => $last_name, 'display_name' => $display_name]);

    // Create CivicHub profile
    global $wpdb;
    $wpdb->insert("{$wpdb->prefix}ch_user_profiles", [
        'user_id'      => $user_id,
        'display_name' => $display_name,
        'location'     => $location,
        'status'       => 'active',
    ], ['%d', '%s', '%s', '%s']);

    // Auto-login
    $credentials = ['user_login' => $username, 'user_password' => $password, 'remember' => true];
    $user = wp_signon($credentials, is_ssl());
    if (is_wp_error($user)) {
        wp_send_json_error(['message' => 'Account created but login failed. Please sign in manually.']);
    }

    $feed_page   = get_page_by_path('forum-feed');
    $default_url = $feed_page ? get_permalink($feed_page) : home_url('/forum-feed/');
    $redirect    = $redirect_to ?: $default_url;

    wp_send_json_success(['redirect' => $redirect]);
}

$ajax_actions = [
    'ch_create_category'     => ['bntm_ajax_ch_create_category', true],
    'ch_edit_category'       => ['bntm_ajax_ch_edit_category', true],
    'ch_delete_category'     => ['bntm_ajax_ch_delete_category', true],
    'ch_toggle_category_status' => ['bntm_ajax_ch_toggle_category_status', true],
    'ch_create_post'         => ['bntm_ajax_ch_create_post', false],
    'ch_edit_post'           => ['bntm_ajax_ch_edit_post', false],
    'ch_delete_post'         => ['bntm_ajax_ch_delete_post', false],
    'ch_get_posts'           => ['bntm_ajax_ch_get_posts', false],
    'ch_get_post_detail'     => ['bntm_ajax_ch_get_post_detail', false],
    'ch_add_comment'         => ['bntm_ajax_ch_add_comment', false],
    'ch_edit_comment'        => ['bntm_ajax_ch_edit_comment', false],
    'ch_delete_comment'      => ['bntm_ajax_ch_delete_comment', false],
    'ch_vote'                => ['bntm_ajax_ch_vote', false],
    'ch_follow_category'     => ['bntm_ajax_ch_follow_category', false],
    'ch_bookmark_post'       => ['bntm_ajax_ch_bookmark_post', false],
    'ch_report'              => ['bntm_ajax_ch_report', false],
    'ch_update_profile'      => ['bntm_ajax_ch_update_profile', false],
    'ch_get_notifications'   => ['bntm_ajax_ch_get_notifications', false],
    'ch_mark_notifications'  => ['bntm_ajax_ch_mark_notifications', false],
    'ch_moderate_action'     => ['bntm_ajax_ch_moderate_action', true],
    'ch_search'              => ['bntm_ajax_ch_search', false],
    'ch_admin_stats'         => ['bntm_ajax_ch_admin_stats', true],
    'ch_live_stats'          => ['bntm_ajax_ch_live_stats', true],
    'ch_pin_post'            => ['bntm_ajax_ch_pin_post', true],
    'ch_create_announcement' => ['bntm_ajax_ch_create_announcement', true],
    'ch_edit_announcement'   => ['bntm_ajax_ch_edit_announcement', true],
    'ch_delete_announcement' => ['bntm_ajax_ch_delete_announcement', true],
    'ch_toggle_announcement' => ['bntm_ajax_ch_toggle_announcement', true],
    'ch_get_announcements'   => ['bntm_ajax_ch_get_announcements', true],
    'ch_get_announcement'    => ['bntm_ajax_ch_get_announcement', true],
    'ch_mention_search'      => ['bntm_ajax_ch_mention_search', false],
];

foreach ($ajax_actions as $action => [$callback, $admin_only]) {
    add_action("wp_ajax_{$action}", function() use ($callback, $admin_only) {
        if ($admin_only && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized access']);
        }
        if (is_callable($callback)) {
            call_user_func($callback);
        } else {
            wp_send_json_error(['message' => 'Callback not found']);
        }
    });

    if (!$admin_only) {
        add_action("wp_ajax_nopriv_{$action}", $callback);
    }
}

// ============================================================

require_once BNTM_CH_PATH . 'frontend.php';
