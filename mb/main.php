<?php
/**
 * Module Name: MentorBe
 * Module Slug: mb
 * Description: Hyper-local professional marketplace connecting Clients who post tasks with skill-tagged Providers. Phase 1 covers user roles, skill tagging, task posting, skill-matched browsing, and the application/selection workflow.
 * Version: 1.0.0
 * Author: BNTM
 * Icon: <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 8.5l-2.5 2.5a1.5 1.5 0 01-2.12 0L10 9.62a1.5 1.5 0 00-2.12 0L5 12.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 11l4.6-4.6a2 2 0 012.8 0L12 8l1.6-1.6a2 2 0 012.8 0L21 11v3a2 2 0 01-2 2h-1l-1.4 1.4a2 2 0 01-2.8 0L12.4 16a2 2 0 00-2.8 0L8.2 17.4a2 2 0 01-2.8 0L4 16H3a2 2 0 01-2 2"/><path stroke-linecap="round" stroke-linejoin="round" d="M2 11v5a2 2 0 002 2"/></svg>
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Module constants
define('BNTM_MB_PATH', dirname(__FILE__) . '/');
define('BNTM_MB_URL', plugin_dir_url(__FILE__));

/* ===========================================================================
   B. MODULE CONFIGURATION FUNCTIONS
=========================================================================== */

function bntm_mb_get_pages() {
    return [
        'MentorBe Dashboard' => '[mb_dashboard]',
        'Browse Tasks'       => '[mb_browse]',
        'Public Profile'     => '[mb_profile_view]',
        'MentorBe Home'      => '[mb_home]',
        'Help Center'        => '[mb_help]',
        'Terms and Privacy'  => '[mb_terms]',
        'Inbox'              => '[mb_inbox]',
        'Checkout'           => '[mb_checkout]',
        'Earnings'           => '[mb_earnings]',
        'Admin Dashboard'    => '[mb_admin]',
    ];
}

function bntm_mb_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    return [
        'mb_user_profiles' => "CREATE TABLE {$prefix}mb_user_profiles (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            user_role VARCHAR(20) NOT NULL DEFAULT 'client',
            pfp_url VARCHAR(255) DEFAULT '',
            bio TEXT,
            verification_status VARCHAR(20) NOT NULL DEFAULT 'unverified',
            notif_email TINYINT(1) NOT NULL DEFAULT 1,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY user_unique (user_id)
        ) {$charset};",

        'mb_skills' => "CREATE TABLE {$prefix}mb_skills (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT 'General',
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY name_unique (name)
        ) {$charset};",

        'mb_provider_skills' => "CREATE TABLE {$prefix}mb_provider_skills (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            skill_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY provider_skill_unique (user_id, skill_id)
        ) {$charset};",

        'mb_tasks' => "CREATE TABLE {$prefix}mb_tasks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            client_id BIGINT UNSIGNED NOT NULL,
            provider_id BIGINT UNSIGNED DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            budget DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            ready_for_review TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(50) NOT NULL DEFAULT 'draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'mb_task_skills' => "CREATE TABLE {$prefix}mb_task_skills (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            task_id BIGINT UNSIGNED NOT NULL,
            skill_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY task_skill_unique (task_id, skill_id)
        ) {$charset};",

        'mb_task_applications' => "CREATE TABLE {$prefix}mb_task_applications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            task_id BIGINT UNSIGNED NOT NULL,
            provider_id BIGINT UNSIGNED NOT NULL,
            cover_message TEXT,
            proposed_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY task_provider_unique (task_id, provider_id)
        ) {$charset};",
    ];
}

function bntm_mb_get_shortcodes() {
    return [
        'mb_dashboard'    => 'bntm_shortcode_mb',
        'mb_browse'       => 'bntm_shortcode_mb_browse',
        'mb_profile_view' => 'bntm_shortcode_mb_profile_view',
        'mb_home'         => 'bntm_shortcode_mb_home',
        'mb_help'         => 'bntm_shortcode_mb_help',
        'mb_terms'        => 'bntm_shortcode_mb_terms',
        'mb_inbox'        => 'bntm_shortcode_mb_inbox',
        'mb_checkout'     => 'bntm_shortcode_mb_checkout',
        'mb_earnings'     => 'bntm_shortcode_mb_earnings',
        'mb_admin'        => 'bntm_shortcode_mb_admin',
    ];
}

function bntm_mb_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_mb_get_tables();
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
    return count($tables);
}

/* ===========================================================================
   C. AJAX ACTION HOOKS
=========================================================================== */

add_action('wp_ajax_mb_complete_onboarding', 'bntm_ajax_mb_complete_onboarding');

add_action('wp_ajax_mb_search_skills', 'bntm_ajax_mb_search_skills');

add_action('wp_ajax_mb_create_task', 'bntm_ajax_mb_create_task');
add_action('wp_ajax_mb_publish_task', 'bntm_ajax_mb_publish_task');
add_action('wp_ajax_mb_cancel_task', 'bntm_ajax_mb_cancel_task');
add_action('wp_ajax_mb_get_applicants', 'bntm_ajax_mb_get_applicants');
add_action('wp_ajax_mb_accept_applicant', 'bntm_ajax_mb_accept_applicant');
add_action('wp_ajax_mb_reject_applicant', 'bntm_ajax_mb_reject_applicant');
add_action('wp_ajax_mb_complete_task', 'bntm_ajax_mb_complete_task');

add_action('wp_ajax_mb_filter_feed', 'bntm_ajax_mb_filter_feed');
add_action('wp_ajax_mb_apply_to_task', 'bntm_ajax_mb_apply_to_task');
add_action('wp_ajax_mb_withdraw_application', 'bntm_ajax_mb_withdraw_application');
add_action('wp_ajax_mb_mark_ready_for_review', 'bntm_ajax_mb_mark_ready_for_review');
add_action('wp_ajax_mb_add_skill', 'bntm_ajax_mb_add_skill');
add_action('wp_ajax_mb_remove_skill', 'bntm_ajax_mb_remove_skill');

add_action('wp_ajax_mb_update_profile', 'bntm_ajax_mb_update_profile');
add_action('wp_ajax_mb_upload_avatar', 'bntm_ajax_mb_upload_avatar');
add_action('wp_ajax_mb_update_account_info', 'bntm_ajax_mb_update_account_info');
add_action('wp_ajax_mb_change_password', 'bntm_ajax_mb_change_password');
add_action('wp_ajax_mb_update_notification_prefs', 'bntm_ajax_mb_update_notification_prefs');

// Public browse page filtering (logged-in and guests can both browse)
add_action('wp_ajax_mb_browse_filter', 'bntm_ajax_mb_browse_filter');
add_action('wp_ajax_nopriv_mb_browse_filter', 'bntm_ajax_mb_browse_filter');

/* ===========================================================================
   D. MAIN DASHBOARD SHORTCODE FUNCTION
=========================================================================== */

function bntm_shortcode_mb() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to access your MentorBe dashboard.</div>';
    }

    $current_user = wp_get_current_user();
    $profile      = mb_get_user_profile($current_user->ID);

    ob_start();
    ?>
    <script>
    var ajaxurl  = '<?php echo admin_url('admin-ajax.php'); ?>';
    var mbNonce  = '<?php echo wp_create_nonce('mb_nonce'); ?>';
    </script>

    <?php if (!$profile): ?>

        <?php echo mb_render_onboarding(); ?>

    <?php else:

        $role       = $profile->user_role;
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : ($role === 'provider' ? 'feed' : 'overview');
    ?>

    <div class="bntm-mb-container">
        <div class="bntm-tabs">
            <?php if ($role === 'client'): ?>
                <a href="?tab=overview" class="bntm-tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>">Home</a>
                <a href="?tab=post_task" class="bntm-tab <?php echo $active_tab === 'post_task' ? 'active' : ''; ?>">Post a Task</a>
                <a href="?tab=manage_tasks" class="bntm-tab <?php echo $active_tab === 'manage_tasks' ? 'active' : ''; ?>">Manage Tasks</a>
                <a href="?tab=profile" class="bntm-tab <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">Profile</a>
                <a href="?tab=settings" class="bntm-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">Settings</a>
            <?php elseif ($role === 'provider'): ?>
                <a href="?tab=feed" class="bntm-tab <?php echo $active_tab === 'feed' ? 'active' : ''; ?>">Task Feed</a>
                <a href="?tab=applications" class="bntm-tab <?php echo $active_tab === 'applications' ? 'active' : ''; ?>">My Applications</a>
                <a href="?tab=active_jobs" class="bntm-tab <?php echo $active_tab === 'active_jobs' ? 'active' : ''; ?>">Active Jobs</a>
                <a href="?tab=profile" class="bntm-tab <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">Profile</a>
                <a href="?tab=settings" class="bntm-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">Settings</a>
            <?php else: ?>
                <a href="?tab=overview" class="bntm-tab active">Admin</a>
            <?php endif; ?>
        </div>

        <div class="bntm-tab-content">
            <?php
            if ($role === 'admin') {
                echo '<div class="bntm-notice">The Admin dashboard (verification, moderation, disputes, financials) ships in a later phase of MentorBe. Your account is marked as admin.</div>';
            } elseif ($role === 'client') {
                if ($active_tab === 'overview') { echo mb_client_overview_tab($current_user->ID); }
                elseif ($active_tab === 'post_task') { echo mb_client_post_task_tab($current_user->ID); }
                elseif ($active_tab === 'manage_tasks') { echo mb_client_manage_tasks_tab($current_user->ID); }
                elseif ($active_tab === 'profile') { echo mb_client_profile_tab($current_user->ID); }
                elseif ($active_tab === 'settings') { echo mb_settings_tab($current_user->ID, $profile); }
            } elseif ($role === 'provider') {
                if ($active_tab === 'feed') { echo mb_provider_feed_tab($current_user->ID); }
                elseif ($active_tab === 'applications') { echo mb_provider_applications_tab($current_user->ID); }
                elseif ($active_tab === 'active_jobs') { echo mb_provider_active_jobs_tab($current_user->ID); }
                elseif ($active_tab === 'profile') { echo mb_provider_profile_tab($current_user->ID); }
                elseif ($active_tab === 'settings') { echo mb_settings_tab($current_user->ID, $profile); }
            }
            ?>
        </div>
    </div>

    <style>
    .bntm-mb-container { max-width: 1100px; }
    .bntm-stats-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
    .bntm-stat-card { display: flex; align-items: center; gap: 14px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 18px; flex: 1; min-width: 200px; }
    .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-content h3 { margin: 0; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
    .stat-number { margin: 2px 0 0; font-size: 22px; font-weight: 700; color: #111827; }
    .stat-label { font-size: 12px; color: #9ca3af; }
    .bntm-task-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 18px; margin-bottom: 12px; }
    .bntm-task-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .bntm-task-card h4 { margin: 0 0 4px; font-size: 16px; color: #111827; }
    .bntm-task-meta { font-size: 12px; color: #6b7280; display: flex; gap: 14px; flex-wrap: wrap; margin-top: 6px; }
    .bntm-skill-pill { display: inline-block; background: #f3f4f6; color: #374151; font-size: 11px; padding: 3px 9px; border-radius: 999px; margin: 2px 4px 2px 0; }
    .bntm-status-badge { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; text-transform: capitalize; }
    .bntm-status-draft { background: #f3f4f6; color: #6b7280; }
    .bntm-status-live { background: #dbeafe; color: #1d4ed8; }
    .bntm-status-pending { background: #fef3c7; color: #b45309; }
    .bntm-status-in_progress { background: #fef3c7; color: #b45309; }
    .bntm-status-completed { background: #d1fae5; color: #047857; }
    .bntm-status-cancelled, .bntm-status-rejected, .bntm-status-withdrawn { background: #fee2e2; color: #b91c1c; }
    .bntm-status-accepted { background: #d1fae5; color: #047857; }
    .bntm-skill-tag-input { display: flex; flex-wrap: wrap; gap: 6px; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px; min-height: 44px; }
    .bntm-skill-tag-input input { border: none; outline: none; flex: 1; min-width: 120px; font-size: 14px; }
    .bntm-skill-chip { background: var(--bntm-primary); color: #fff; font-size: 12px; padding: 4px 8px 4px 10px; border-radius: 999px; display: flex; align-items: center; gap: 6px; }
    .bntm-skill-chip button { background: none; border: none; color: #fff; cursor: pointer; font-size: 13px; line-height: 1; opacity: .8; }
    .bntm-skill-suggestions { position: relative; }
    .bntm-skill-suggestion-list { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 6px 16px rgba(0,0,0,.08); z-index: 20; max-height: 200px; overflow-y: auto; }
    .bntm-skill-suggestion-item { padding: 8px 12px; cursor: pointer; font-size: 13px; }
    .bntm-skill-suggestion-item:hover { background: #f9fafb; }
    .bntm-onboarding-wrap { max-width: 560px; margin: 20px auto; text-align: center; }
    .bntm-role-cards { display: flex; gap: 16px; margin-top: 20px; }
    .bntm-role-card { flex: 1; border: 2px solid #e5e7eb; border-radius: 12px; padding: 24px 16px; cursor: pointer; transition: border-color .15s; }
    .bntm-role-card:hover, .bntm-role-card.selected { border-color: var(--bntm-primary); }
    .bntm-role-card h4 { margin: 10px 0 6px; }
    .bntm-role-card p { font-size: 13px; color: #6b7280; margin: 0; }
    .bntm-applicant-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
    .bntm-applicant-row:last-child { border-bottom: none; }
    .bntm-avatar { width: 40px; height: 40px; border-radius: 50%; background: #e5e7eb; object-fit: cover; }
    .mb-coming-soon-wrap { max-width: 520px; margin: 40px auto; text-align: center; padding: 48px 24px; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; }
    .mb-coming-soon-icon { width: 80px; height: 80px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; color: var(--bntm-primary); }
    .mb-coming-soon-wrap h2 { margin: 0 0 10px; font-size: 22px; }
    .mb-coming-soon-wrap p { color: #6b7280; font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
    </style>

    <script>
    function bntmMbToast(containerId, message, success) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = '<div class="bntm-notice bntm-notice-' + (success ? 'success' : 'error') + '">' + message + '</div>';
    }

    function bntmMbSkillTagInput(wrapperId, hiddenInputId, initialSkills) {
        var wrapper  = document.getElementById(wrapperId);
        if (!wrapper) return;
        var hidden   = document.getElementById(hiddenInputId);
        var selected = initialSkills || [];

        function syncHidden() { hidden.value = JSON.stringify(selected); }

        function renderChips() {
            wrapper.querySelectorAll('.bntm-skill-chip').forEach(function(c) { c.remove(); });
            var input = wrapper.querySelector('input');
            selected.forEach(function(s) {
                var chip = document.createElement('span');
                chip.className = 'bntm-skill-chip';
                chip.innerHTML = s.name + ' <button type="button" data-id="' + s.id + '">&times;</button>';
                wrapper.insertBefore(chip, input);
            });
            wrapper.querySelectorAll('.bntm-skill-chip button').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.dataset.id;
                    selected = selected.filter(function(s) { return String(s.id) !== String(id); });
                    syncHidden();
                    renderChips();
                });
            });
        }

        var input = wrapper.querySelector('input');
        var listBox = document.createElement('div');
        listBox.className = 'bntm-skill-suggestion-list';
        listBox.style.display = 'none';
        wrapper.parentElement.style.position = 'relative';
        wrapper.parentElement.appendChild(listBox);

        var debounceTimer;
        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            var q = this.value.trim();
            if (q.length < 1) { listBox.style.display = 'none'; return; }
            debounceTimer = setTimeout(function() {
                var formData = new FormData();
                formData.append('action', 'mb_search_skills');
                formData.append('query', q);
                formData.append('nonce', mbNonce);
                fetch(ajaxurl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    if (!json.success) return;
                    listBox.innerHTML = '';
                    json.data.skills.forEach(function(s) {
                        var already = selected.some(function(sel) { return String(sel.id) === String(s.id); });
                        if (already) return;
                        var item = document.createElement('div');
                        item.className = 'bntm-skill-suggestion-item';
                        item.textContent = s.name + ' (' + s.category + ')';
                        item.addEventListener('click', function() {
                            selected.push({ id: s.id, name: s.name });
                            syncHidden();
                            renderChips();
                            input.value = '';
                            listBox.style.display = 'none';
                        });
                        listBox.appendChild(item);
                    });
                    if (json.data.can_create && q.length > 1) {
                        var createItem = document.createElement('div');
                        createItem.className = 'bntm-skill-suggestion-item';
                        createItem.style.fontStyle = 'italic';
                        createItem.textContent = 'Use new tag "' + q + '"';
                        createItem.addEventListener('click', function() {
                            selected.push({ id: 'new:' + q, name: q });
                            syncHidden();
                            renderChips();
                            input.value = '';
                            listBox.style.display = 'none';
                        });
                        listBox.appendChild(createItem);
                    }
                    listBox.style.display = (json.data.skills.length || json.data.can_create) ? 'block' : 'none';
                });
            }, 250);
        });

        document.addEventListener('click', function(e) {
            if (!wrapper.parentElement.contains(e.target)) listBox.style.display = 'none';
        });

        syncHidden();
        renderChips();
    }
    </script>

    <?php endif;

    $content = ob_get_clean();
    return bntm_universal_container('MentorBe', $content);
}

/* ===========================================================================
   ONBOARDING SCREEN (shown when a logged-in user has no mb_user_profiles row)
=========================================================================== */

function mb_render_onboarding() {
    ob_start();
    ?>
    <div class="bntm-onboarding-wrap">
        <h2>Welcome to MentorBe</h2>
        <p style="color:#6b7280;">Tell us how you'll be using the platform.</p>
        <div class="bntm-role-cards">
            <div class="bntm-role-card" data-role="client">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h1M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <h4>I'm a Client</h4>
                <p>I want to post tasks and hire skilled providers.</p>
            </div>
            <div class="bntm-role-card" data-role="provider">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 1.79-8 4v2h16v-2c0-2.21-3.582-4-8-4z"/></svg>
                <h4>I'm a Provider</h4>
                <p>I want to browse tasks and offer my skills.</p>
            </div>
        </div>
        <button id="mb-onboarding-submit" class="bntm-btn-primary" style="margin-top:20px;" disabled>Continue</button>
        <div id="onboarding-message"></div>
    </div>
    <script>
    (function() {
        var selectedRole = null;
        document.querySelectorAll('.bntm-role-card').forEach(function(card) {
            card.addEventListener('click', function() {
                document.querySelectorAll('.bntm-role-card').forEach(function(c) { c.classList.remove('selected'); });
                this.classList.add('selected');
                selectedRole = this.dataset.role;
                document.getElementById('mb-onboarding-submit').disabled = false;
            });
        });
        document.getElementById('mb-onboarding-submit').addEventListener('click', function() {
            if (!selectedRole) return;
            this.disabled = true;
            this.textContent = 'Setting up...';
            var formData = new FormData();
            formData.append('action', 'mb_complete_onboarding');
            formData.append('role', selectedRole);
            formData.append('nonce', mbNonce);
            fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                if (json.success) {
                    location.reload();
                } else {
                    bntmMbToast('onboarding-message', json.data.message, false);
                    document.getElementById('mb-onboarding-submit').disabled = false;
                    document.getElementById('mb-onboarding-submit').textContent = 'Continue';
                }
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

/* ===========================================================================
   E. TAB RENDERING FUNCTIONS — CLIENT
=========================================================================== */

function mb_client_overview_tab($user_id) {
    global $wpdb;
    $tasks_table = $wpdb->prefix . 'mb_tasks';
    $apps_table  = $wpdb->prefix . 'mb_task_applications';

    $active_count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tasks_table} WHERE client_id = %d AND status IN ('live','in_progress')", $user_id
    ));
    $pending_apps = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$apps_table} a INNER JOIN {$tasks_table} t ON a.task_id = t.id
         WHERE t.client_id = %d AND a.status = 'pending'", $user_id
    ));
    $completed_count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tasks_table} WHERE client_id = %d AND status = 'completed'", $user_id
    ));
    $total_budget = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(budget),0) FROM {$tasks_table} WHERE client_id = %d AND status != 'cancelled'", $user_id
    ));

    $recent = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$tasks_table} WHERE client_id = %d ORDER BY updated_at DESC LIMIT 5", $user_id
    ));

    ob_start();
    ?>
    <div class="bntm-stats-row">
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:var(--bntm-primary);">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h1M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <div class="stat-content"><h3>Active Tasks</h3><p class="stat-number"><?php echo number_format($active_count); ?></p></div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:var(--bntm-primary);">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content"><h3>Pending Applicants</h3><p class="stat-number"><?php echo number_format($pending_apps); ?></p></div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:var(--bntm-primary);">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="stat-content"><h3>Completed</h3><p class="stat-number"><?php echo number_format($completed_count); ?></p></div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:var(--bntm-primary);">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2"/></svg>
            </div>
            <div class="stat-content"><h3>Total Budget Posted</h3><p class="stat-number"><?php echo mb_format_price($total_budget); ?></p></div>
        </div>
    </div>

    <div class="bntm-form-section">
        <h3>Recent Activity</h3>
        <?php if (empty($recent)): ?>
            <p style="color:#6b7280;">No tasks yet. <a href="?tab=post_task">Post your first task</a>.</p>
        <?php else: foreach ($recent as $t): ?>
            <div class="bntm-task-card">
                <div class="bntm-task-card-head">
                    <h4><?php echo esc_html($t->title); ?></h4>
                    <span class="bntm-status-badge bntm-status-<?php echo esc_attr($t->status); ?>"><?php echo esc_html(str_replace('_',' ',$t->status)); ?></span>
                </div>
                <div class="bntm-task-meta">
                    <span><?php echo mb_format_price($t->budget); ?></span>
                    <span>Updated <?php echo esc_html(human_time_diff(strtotime($t->updated_at), current_time('timestamp'))); ?> ago</span>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function mb_client_post_task_tab($user_id) {
    $nonce = wp_create_nonce('mb_nonce');
    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Post a Task</h3>
        <form id="mb-post-task-form" class="bntm-form">
            <div class="bntm-form-group">
                <label>Task Title *</label>
                <input type="text" name="title" required maxlength="255" placeholder="e.g. Fix leaking kitchen faucet">
            </div>
            <div class="bntm-form-group">
                <label>Description *</label>
                <textarea name="description" rows="5" required placeholder="Describe what needs to be done, scope, and any requirements."></textarea>
            </div>
            <div class="bntm-form-group">
                <label>Required Skills</label>
                <div class="bntm-skill-tag-input" id="post-task-skill-wrapper">
                    <input type="text" placeholder="Type to search skills...">
                </div>
                <input type="hidden" name="skills" id="post-task-skills-hidden">
            </div>
            <div class="bntm-form-group">
                <label>Budget (&#8369;) *</label>
                <input type="number" name="budget" min="0" step="0.01" required placeholder="0.00">
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" id="mb-save-draft-btn" class="bntm-btn-secondary" data-nonce="<?php echo $nonce; ?>">Save as Draft</button>
                <button type="button" id="mb-publish-task-btn" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Publish Task</button>
            </div>
        </form>
        <div id="post-task-message"></div>
    </div>

    <script>
    (function() {
        bntmMbSkillTagInput('post-task-skill-wrapper', 'post-task-skills-hidden', []);

        function submitTask(mode, btn) {
            var form = document.getElementById('mb-post-task-form');
            if (!form.reportValidity()) return;

            var formData = new FormData(form);
            formData.append('action', 'mb_create_task');
            formData.append('mode', mode);
            formData.append('nonce', btn.dataset.nonce);

            btn.disabled = true;
            var originalText = btn.textContent;
            btn.textContent = mode === 'publish' ? 'Publishing...' : 'Saving...';

            fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                bntmMbToast('post-task-message', json.data.message, json.success);
                btn.disabled = false;
                btn.textContent = originalText;
                if (json.success) {
                    form.reset();
                    bntmMbSkillTagInput('post-task-skill-wrapper', 'post-task-skills-hidden', []);
                    setTimeout(function() { window.location.href = '?tab=manage_tasks'; }, 1200);
                }
            });
        }

        document.getElementById('mb-save-draft-btn').addEventListener('click', function() { submitTask('draft', this); });
        document.getElementById('mb-publish-task-btn').addEventListener('click', function() { submitTask('publish', this); });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_client_manage_tasks_tab($user_id) {
    global $wpdb;
    $tasks_table = $wpdb->prefix . 'mb_tasks';
    $tasks = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$tasks_table} WHERE client_id = %d ORDER BY created_at DESC", $user_id
    ));
    $nonce = wp_create_nonce('mb_nonce');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Manage Tasks</h3>
        <?php if (empty($tasks)): ?>
            <p style="color:#6b7280;">You haven't posted any tasks yet.</p>
        <?php else: foreach ($tasks as $t): ?>
            <div class="bntm-task-card" data-task-id="<?php echo $t->id; ?>">
                <div class="bntm-task-card-head">
                    <h4><?php echo esc_html($t->title); ?></h4>
                    <span class="bntm-status-badge bntm-status-<?php echo esc_attr($t->status); ?>"><?php echo esc_html(str_replace('_',' ',$t->status)); ?></span>
                </div>
                <p style="color:#4b5563;font-size:13px;"><?php echo esc_html(wp_trim_words($t->description, 24)); ?></p>
                <div class="bntm-task-meta">
                    <span><?php echo mb_format_price($t->budget); ?></span>
                    <?php if ($t->ready_for_review): ?><span style="color:#b45309;font-weight:600;">Marked ready for review</span><?php endif; ?>
                </div>
                <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                    <?php if (in_array($t->status, ['draft','live'])): ?>
                        <button class="bntm-btn-small bntm-btn-secondary mb-view-applicants" data-id="<?php echo $t->id; ?>">View Applicants</button>
                        <button class="bntm-btn-small bntm-btn-danger mb-cancel-task" data-id="<?php echo $t->id; ?>" data-nonce="<?php echo $nonce; ?>">Cancel</button>
                    <?php elseif ($t->status === 'in_progress'): ?>
                        <button class="bntm-btn-small bntm-btn-primary mb-complete-task" data-id="<?php echo $t->id; ?>" data-nonce="<?php echo $nonce; ?>">Mark Completed</button>
                    <?php endif; ?>
                </div>
                <div class="mb-applicants-panel" id="applicants-panel-<?php echo $t->id; ?>" style="display:none;margin-top:14px;border-top:1px solid #f3f4f6;padding-top:12px;"></div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <script>
    (function() {
        var nonce = '<?php echo $nonce; ?>';

        document.querySelectorAll('.mb-view-applicants').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var taskId = this.dataset.id;
                var panel  = document.getElementById('applicants-panel-' + taskId);
                if (panel.style.display === 'block') { panel.style.display = 'none'; return; }

                panel.style.display = 'block';
                panel.innerHTML = '<p style="color:#6b7280;">Loading applicants...</p>';

                var formData = new FormData();
                formData.append('action', 'mb_get_applicants');
                formData.append('task_id', taskId);
                formData.append('nonce', nonce);

                fetch(ajaxurl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    if (!json.success) { panel.innerHTML = '<p>' + json.data.message + '</p>'; return; }
                    if (json.data.applicants.length === 0) { panel.innerHTML = '<p style="color:#6b7280;">No applicants yet.</p>'; return; }

                    var html = '';
                    json.data.applicants.forEach(function(a) {
                        html += '<div class="bntm-applicant-row">' +
                            '<div><strong>' + a.name + '</strong><div style="font-size:12px;color:#6b7280;">' + a.cover_message + '</div>' +
                            '<div style="font-size:12px;color:#374151;margin-top:4px;">Proposed: &#8369;' + a.proposed_rate + '</div></div>' +
                            '<div>';
                        if (a.status === 'pending') {
                            html += '<button class="bntm-btn-small bntm-btn-primary mb-accept-app" data-app="' + a.id + '" data-task="' + taskId + '">Accept</button> ' +
                                    '<button class="bntm-btn-small bntm-btn-danger mb-reject-app" data-app="' + a.id + '">Reject</button>';
                        } else {
                            html += '<span class="bntm-status-badge bntm-status-' + a.status + '">' + a.status + '</span>';
                        }
                        html += '</div></div>';
                    });
                    panel.innerHTML = html;

                    panel.querySelectorAll('.mb-accept-app').forEach(function(b) {
                        b.addEventListener('click', function() {
                            if (!confirm('Accept this applicant? All other applicants will be rejected and the task will move to In Progress.')) return;
                            var fd = new FormData();
                            fd.append('action', 'mb_accept_applicant');
                            fd.append('application_id', this.dataset.app);
                            fd.append('task_id', this.dataset.task);
                            fd.append('nonce', nonce);
                            fetch(ajaxurl, { method: 'POST', body: fd })
                            .then(function(r) { return r.json(); })
                            .then(function(json) { alert(json.data.message); if (json.success) location.reload(); });
                        });
                    });
                    panel.querySelectorAll('.mb-reject-app').forEach(function(b) {
                        b.addEventListener('click', function() {
                            var fd = new FormData();
                            fd.append('action', 'mb_reject_applicant');
                            fd.append('application_id', this.dataset.app);
                            fd.append('nonce', nonce);
                            var row = this.closest('.bntm-applicant-row');
                            fetch(ajaxurl, { method: 'POST', body: fd })
                            .then(function(r) { return r.json(); })
                            .then(function(json) { if (json.success) row.remove(); else alert(json.data.message); });
                        });
                    });
                });
            });
        });

        document.querySelectorAll('.mb-cancel-task').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Cancel this task?')) return;
                var fd = new FormData();
                fd.append('action', 'mb_cancel_task');
                fd.append('task_id', this.dataset.id);
                fd.append('nonce', this.dataset.nonce);
                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(json) { alert(json.data.message); if (json.success) location.reload(); });
            });
        });

        document.querySelectorAll('.mb-complete-task').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Mark this task as completed?')) return;
                var fd = new FormData();
                fd.append('action', 'mb_complete_task');
                fd.append('task_id', this.dataset.id);
                fd.append('nonce', this.dataset.nonce);
                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(json) { alert(json.data.message); if (json.success) location.reload(); });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_client_profile_tab($user_id) {
    $profile = mb_get_user_profile($user_id);
    $user    = get_userdata($user_id);
    $nonce   = wp_create_nonce('mb_nonce');
    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Profile</h3>
        <div style="display:flex;gap:18px;align-items:center;margin-bottom:18px;">
            <img class="bntm-avatar" style="width:64px;height:64px;" src="<?php echo esc_url($profile->pfp_url ?: 'https://www.gravatar.com/avatar/?d=mp'); ?>" alt="">
            <div>
                <input type="file" id="mb-avatar-input" accept="image/*" data-nonce="<?php echo $nonce; ?>">
                <div id="avatar-message"></div>
            </div>
        </div>
        <form id="mb-profile-form" class="bntm-form">
            <div class="bntm-form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo esc_attr($user->display_name); ?>">
            </div>
            <div class="bntm-form-group">
                <label>Bio</label>
                <textarea name="bio" rows="4"><?php echo esc_textarea($profile->bio); ?></textarea>
            </div>
            <button type="submit" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Save Profile</button>
        </form>
        <div id="profile-message"></div>
        <p style="margin-top:14px;font-size:13px;"><a href="<?php echo esc_url(home_url('/?mb_user=' . $user_id)); ?>" target="_blank">View Public Profile &rarr;</a></p>
    </div>
    <script>
    (function() {
        document.getElementById('mb-profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'mb_update_profile');
            fd.append('nonce', this.querySelector('button').dataset.nonce);
            var btn = this.querySelector('button');
            btn.disabled = true; btn.textContent = 'Saving...';
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                bntmMbToast('profile-message', json.data.message, json.success);
                btn.disabled = false; btn.textContent = 'Save Profile';
            });
        });

        document.getElementById('mb-avatar-input').addEventListener('change', function() {
            if (!this.files[0]) return;
            var fd = new FormData();
            fd.append('action', 'mb_upload_avatar');
            fd.append('avatar', this.files[0]);
            fd.append('nonce', this.dataset.nonce);
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                bntmMbToast('avatar-message', json.data.message, json.success);
                if (json.success) setTimeout(function() { location.reload(); }, 1000);
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

/* ===========================================================================
   E. TAB RENDERING FUNCTIONS — PROVIDER
=========================================================================== */

function mb_provider_feed_tab($user_id) {
    global $wpdb;
    $skills = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mb_skills WHERE status='active' ORDER BY category, name");
    $nonce  = wp_create_nonce('mb_nonce');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Task Feed</h3>
        <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
            <select id="mb-feed-skill-filter">
                <option value="">All Skills</option>
                <?php foreach ($skills as $s): ?>
                    <option value="<?php echo $s->id; ?>"><?php echo esc_html($s->name); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="mb-feed-search" placeholder="Search tasks...">
        </div>
        <div id="mb-feed-results">
            <p style="color:#6b7280;">Loading tasks...</p>
        </div>
    </div>

    <div id="mb-apply-modal" class="bntm-modal" style="display:none;">
        <div class="bntm-modal-content">
            <h3>Apply to Task</h3>
            <form id="mb-apply-form">
                <input type="hidden" name="task_id" id="mb-apply-task-id">
                <div class="bntm-form-group">
                    <label>Proposed Rate (&#8369;) *</label>
                    <input type="number" name="proposed_rate" min="0" step="0.01" required>
                </div>
                <div class="bntm-form-group">
                    <label>Cover Message *</label>
                    <textarea name="cover_message" rows="4" required placeholder="Introduce yourself and explain your approach."></textarea>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="bntm-btn-primary">Submit Application</button>
                    <button type="button" id="mb-apply-cancel" class="bntm-btn-secondary">Cancel</button>
                </div>
            </form>
            <div id="apply-message"></div>
        </div>
    </div>

    <style>
    .bntm-modal { position: fixed; inset: 0; background: rgba(17,24,39,.5); display: flex; align-items: center; justify-content: center; z-index: 100; }
    .bntm-modal-content { background: #fff; border-radius: 12px; padding: 24px; width: 100%; max-width: 460px; }
    </style>

    <script>
    (function() {
        var nonce = '<?php echo $nonce; ?>';

        function loadFeed() {
            var fd = new FormData();
            fd.append('action', 'mb_filter_feed');
            fd.append('skill_id', document.getElementById('mb-feed-skill-filter').value);
            fd.append('search', document.getElementById('mb-feed-search').value);
            fd.append('nonce', nonce);

            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                var box = document.getElementById('mb-feed-results');
                if (!json.success) { box.innerHTML = '<p>' + json.data.message + '</p>'; return; }
                if (json.data.tasks.length === 0) { box.innerHTML = '<p style="color:#6b7280;">No matching tasks right now.</p>'; return; }

                var html = '';
                json.data.tasks.forEach(function(t) {
                    html += '<div class="bntm-task-card">' +
                        '<div class="bntm-task-card-head"><h4>' + t.title + '</h4><span>' + t.budget_fmt + '</span></div>' +
                        '<p style="color:#4b5563;font-size:13px;">' + t.description + '</p>' +
                        '<div>' + t.skills.map(function(s) { return '<span class="bntm-skill-pill">' + s + '</span>'; }).join('') + '</div>' +
                        '<div class="bntm-task-meta"><span>by ' + t.client_name + '</span><span>posted ' + t.posted_ago + '</span></div>';
                    if (t.already_applied) {
                        html += '<div style="margin-top:10px;"><span class="bntm-status-badge bntm-status-pending">Already Applied</span></div>';
                    } else {
                        html += '<div style="margin-top:10px;"><button class="bntm-btn-small bntm-btn-primary mb-open-apply" data-id="' + t.id + '" data-title="' + t.title.replace(/"/g,'&quot;') + '">Apply</button></div>';
                    }
                    html += '</div>';
                });
                box.innerHTML = html;

                box.querySelectorAll('.mb-open-apply').forEach(function(b) {
                    b.addEventListener('click', function() {
                        document.getElementById('mb-apply-task-id').value = this.dataset.id;
                        document.getElementById('mb-apply-modal').style.display = 'flex';
                    });
                });
            });
        }

        document.getElementById('mb-feed-skill-filter').addEventListener('change', loadFeed);
        var searchTimer;
        document.getElementById('mb-feed-search').addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(loadFeed, 350);
        });

        document.getElementById('mb-apply-cancel').addEventListener('click', function() {
            document.getElementById('mb-apply-modal').style.display = 'none';
        });

        document.getElementById('mb-apply-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'mb_apply_to_task');
            fd.append('nonce', nonce);
            var btn = this.querySelector('button[type="submit"]');
            btn.disabled = true; btn.textContent = 'Submitting...';
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                bntmMbToast('apply-message', json.data.message, json.success);
                btn.disabled = false; btn.textContent = 'Submit Application';
                if (json.success) {
                    setTimeout(function() {
                        document.getElementById('mb-apply-modal').style.display = 'none';
                        loadFeed();
                    }, 1000);
                }
            });
        });

        loadFeed();
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_provider_applications_tab($user_id) {
    global $wpdb;
    $apps_table  = $wpdb->prefix . 'mb_task_applications';
    $tasks_table = $wpdb->prefix . 'mb_tasks';

    $apps = $wpdb->get_results($wpdb->prepare(
        "SELECT a.*, t.title, t.budget, t.status as task_status
         FROM {$apps_table} a INNER JOIN {$tasks_table} t ON a.task_id = t.id
         WHERE a.provider_id = %d ORDER BY a.applied_at DESC", $user_id
    ));
    $nonce = wp_create_nonce('mb_nonce');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>My Applications</h3>
        <?php if (empty($apps)): ?>
            <p style="color:#6b7280;">You haven't applied to any tasks yet. Check the Task Feed.</p>
        <?php else: ?>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead><tr><th>Task</th><th>Proposed Rate</th><th>Applied</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($apps as $a): ?>
                <tr>
                    <td><?php echo esc_html($a->title); ?></td>
                    <td><?php echo mb_format_price($a->proposed_rate); ?></td>
                    <td><?php echo esc_html(human_time_diff(strtotime($a->applied_at), current_time('timestamp'))); ?> ago</td>
                    <td><span class="bntm-status-badge bntm-status-<?php echo esc_attr($a->status); ?>"><?php echo esc_html($a->status); ?></span></td>
                    <td>
                        <?php if ($a->status === 'pending'): ?>
                        <button class="bntm-btn-small bntm-btn-danger mb-withdraw-app" data-id="<?php echo $a->id; ?>" data-nonce="<?php echo $nonce; ?>">Withdraw</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
    <script>
    (function() {
        document.querySelectorAll('.mb-withdraw-app').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Withdraw this application?')) return;
                var fd = new FormData();
                fd.append('action', 'mb_withdraw_application');
                fd.append('application_id', this.dataset.id);
                fd.append('nonce', this.dataset.nonce);
                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(json) { alert(json.data.message); if (json.success) location.reload(); });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_provider_active_jobs_tab($user_id) {
    global $wpdb;
    $tasks_table = $wpdb->prefix . 'mb_tasks';
    $jobs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$tasks_table} WHERE provider_id = %d AND status IN ('in_progress','completed') ORDER BY updated_at DESC", $user_id
    ));
    $nonce = wp_create_nonce('mb_nonce');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Active Jobs</h3>
        <?php if (empty($jobs)): ?>
            <p style="color:#6b7280;">No active jobs right now.</p>
        <?php else: foreach ($jobs as $t): ?>
            <div class="bntm-task-card">
                <div class="bntm-task-card-head">
                    <h4><?php echo esc_html($t->title); ?></h4>
                    <span class="bntm-status-badge bntm-status-<?php echo esc_attr($t->status); ?>"><?php echo esc_html(str_replace('_',' ',$t->status)); ?></span>
                </div>
                <p style="color:#4b5563;font-size:13px;"><?php echo esc_html(wp_trim_words($t->description, 24)); ?></p>
                <div class="bntm-task-meta"><span><?php echo mb_format_price($t->budget); ?></span></div>
                <?php if ($t->status === 'in_progress'): ?>
                    <div style="margin-top:10px;">
                        <?php if ($t->ready_for_review): ?>
                            <span class="bntm-status-badge bntm-status-pending">Awaiting client review</span>
                        <?php else: ?>
                            <button class="bntm-btn-small bntm-btn-primary mb-ready-review" data-id="<?php echo $t->id; ?>" data-nonce="<?php echo $nonce; ?>">Mark Ready for Review</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <script>
    (function() {
        document.querySelectorAll('.mb-ready-review').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var fd = new FormData();
                fd.append('action', 'mb_mark_ready_for_review');
                fd.append('task_id', this.dataset.id);
                fd.append('nonce', this.dataset.nonce);
                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(json) { alert(json.data.message); if (json.success) location.reload(); });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_provider_profile_tab($user_id) {
    global $wpdb;
    $profile = mb_get_user_profile($user_id);
    $user    = get_userdata($user_id);
    $nonce   = wp_create_nonce('mb_nonce');

    $my_skills = $wpdb->get_results($wpdb->prepare(
        "SELECT s.id, s.name FROM {$wpdb->prefix}mb_provider_skills ps
         INNER JOIN {$wpdb->prefix}mb_skills s ON ps.skill_id = s.id
         WHERE ps.user_id = %d AND ps.status = 'active'", $user_id
    ));
    $initial_skills = array_map(function($s) { return ['id' => $s->id, 'name' => $s->name]; }, $my_skills);

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Profile</h3>
        <div style="display:flex;gap:18px;align-items:center;margin-bottom:18px;">
            <img class="bntm-avatar" style="width:64px;height:64px;" src="<?php echo esc_url($profile->pfp_url ?: 'https://www.gravatar.com/avatar/?d=mp'); ?>" alt="">
            <div>
                <input type="file" id="mb-avatar-input" accept="image/*" data-nonce="<?php echo $nonce; ?>">
                <div id="avatar-message"></div>
            </div>
        </div>
        <form id="mb-profile-form" class="bntm-form">
            <div class="bntm-form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo esc_attr($user->display_name); ?>">
            </div>
            <div class="bntm-form-group">
                <label>Bio / Portfolio Note</label>
                <textarea name="bio" rows="4"><?php echo esc_textarea($profile->bio); ?></textarea>
            </div>
            <button type="submit" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Save Profile</button>
        </form>
        <div id="profile-message"></div>
    </div>

    <div class="bntm-form-section">
        <h3>My Skills</h3>
        <div class="bntm-skill-tag-input" id="provider-skill-wrapper">
            <input type="text" placeholder="Type to search or add skills...">
        </div>
        <input type="hidden" id="provider-skills-hidden">
        <button id="mb-save-skills-btn" class="bntm-btn-primary" style="margin-top:12px;" data-nonce="<?php echo $nonce; ?>">Save Skills</button>
        <div id="skills-message"></div>
    </div>
    <script>
    (function() {
        document.getElementById('mb-profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'mb_update_profile');
            fd.append('nonce', this.querySelector('button').dataset.nonce);
            var btn = this.querySelector('button');
            btn.disabled = true; btn.textContent = 'Saving...';
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                bntmMbToast('profile-message', json.data.message, json.success);
                btn.disabled = false; btn.textContent = 'Save Profile';
            });
        });

        document.getElementById('mb-avatar-input').addEventListener('change', function() {
            if (!this.files[0]) return;
            var fd = new FormData();
            fd.append('action', 'mb_upload_avatar');
            fd.append('avatar', this.files[0]);
            fd.append('nonce', this.dataset.nonce);
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                bntmMbToast('avatar-message', json.data.message, json.success);
                if (json.success) setTimeout(function() { location.reload(); }, 1000);
            });
        });

        bntmMbSkillTagInput('provider-skill-wrapper', 'provider-skills-hidden', <?php echo wp_json_encode($initial_skills); ?>);

        document.getElementById('mb-save-skills-btn').addEventListener('click', function() {
            var fd = new FormData();
            fd.append('action', 'mb_add_skill');
            fd.append('skills', document.getElementById('provider-skills-hidden').value);
            fd.append('nonce', this.dataset.nonce);
            this.disabled = true; this.textContent = 'Saving...';
            var btn = this;
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                bntmMbToast('skills-message', json.data.message, json.success);
                btn.disabled = false; btn.textContent = 'Save Skills';
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

/* ===========================================================================
   E. TAB RENDERING FUNCTIONS — SHARED SETTINGS (CLIENT + PROVIDER)
=========================================================================== */

function mb_settings_tab($user_id, $profile) {
    $user  = get_userdata($user_id);
    $nonce = wp_create_nonce('mb_nonce');
    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Personal Information</h3>
        <form id="mb-account-info-form" class="bntm-form">
            <div class="bntm-form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" required>
            </div>
            <button type="submit" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Update Information</button>
        </form>
        <div id="account-info-message"></div>
    </div>

    <div class="bntm-form-section">
        <h3>Account Security</h3>
        <form id="mb-password-form" class="bntm-form">
            <div class="bntm-form-group">
                <label>Current Password *</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="bntm-form-group">
                <label>New Password *</label>
                <input type="password" name="new_password" minlength="8" required>
            </div>
            <button type="submit" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Change Password</button>
        </form>
        <div id="password-message"></div>
    </div>

    <div class="bntm-form-section">
        <h3>Notifications</h3>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" id="mb-notif-email" <?php checked((int) $profile->notif_email, 1); ?>>
            Email me about new applicants, status changes, and messages
        </label>
        <div id="notif-message"></div>
    </div>

    <div class="bntm-form-section">
        <h3>Identity Verification</h3>
        <p>
            Status:
            <span class="bntm-status-badge bntm-status-<?php echo $profile->verification_status === 'verified' ? 'completed' : 'pending'; ?>">
                <?php echo esc_html(ucfirst($profile->verification_status)); ?>
            </span>
        </p>
        <p style="color:#6b7280;font-size:13px;">Identity verification review by MentorBe admins is coming in a later phase.</p>
    </div>

    <script>
    (function() {
        var nonce = '<?php echo $nonce; ?>';

        document.getElementById('mb-account-info-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'mb_update_account_info');
            fd.append('nonce', this.querySelector('button').dataset.nonce);
            var btn = this.querySelector('button');
            btn.disabled = true;
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                bntmMbToast('account-info-message', json.data.message, json.success);
                btn.disabled = false;
            });
        });

        document.getElementById('mb-password-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'mb_change_password');
            fd.append('nonce', this.querySelector('button').dataset.nonce);
            var btn = this.querySelector('button');
            btn.disabled = true;
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                bntmMbToast('password-message', json.data.message, json.success);
                btn.disabled = false;
                if (json.success) e.target.reset();
            });
        });

        document.getElementById('mb-notif-email').addEventListener('change', function() {
            var fd = new FormData();
            fd.append('action', 'mb_update_notification_prefs');
            fd.append('notif_email', this.checked ? '1' : '0');
            fd.append('nonce', nonce);
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) { bntmMbToast('notif-message', json.data.message, json.success); });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

/* ===========================================================================
   F. AJAX HANDLER FUNCTIONS
=========================================================================== */

function bntm_ajax_mb_complete_onboarding() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id = get_current_user_id();
    $role    = sanitize_text_field($_POST['role']);

    if (!in_array($role, ['client', 'provider'], true)) {
        wp_send_json_error(['message' => 'Invalid role selected']);
    }

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}mb_user_profiles WHERE user_id = %d", $user_id
    ));
    if ($existing) wp_send_json_error(['message' => 'Profile already exists']);

    $result = $wpdb->insert($wpdb->prefix . 'mb_user_profiles', [
        'rand_id'              => bntm_rand_id(),
        'user_id'              => $user_id,
        'user_role'            => $role,
        'verification_status'  => 'unverified',
        'notif_email'          => 1,
        'status'               => 'active',
        'created_at'           => current_time('mysql'),
        'updated_at'           => current_time('mysql'),
    ], ['%s','%d','%s','%s','%d','%s','%s','%s']);

    if ($result) {
        wp_send_json_success(['message' => 'Welcome to MentorBe!']);
    } else {
        wp_send_json_error(['message' => 'Could not create your profile. Please try again.']);
    }
}

function bntm_ajax_mb_search_skills() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $query = sanitize_text_field($_POST['query']);

    $skills = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, category FROM {$wpdb->prefix}mb_skills
         WHERE status = 'active' AND name LIKE %s ORDER BY name LIMIT 10",
        '%' . $wpdb->esc_like($query) . '%'
    ));

    $exact = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}mb_skills WHERE LOWER(name) = LOWER(%s)", $query
    ));

    wp_send_json_success([
        'skills'    => $skills,
        'can_create' => empty($exact),
    ]);
}

/**
 * Resolves a mixed array of skill refs (existing numeric IDs or "new:Name" tokens)
 * into real skill IDs, creating new skill rows as needed.
 */
function mb_resolve_skill_ids($skills_json) {
    global $wpdb;
    $skills = json_decode(stripslashes($skills_json), true);
    if (!is_array($skills)) return [];

    $ids = [];
    foreach ($skills as $s) {
        if (!isset($s['id'])) continue;
        if (strpos((string) $s['id'], 'new:') === 0) {
            $name = sanitize_text_field(substr($s['id'], 4));
            if ($name === '') continue;
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}mb_skills WHERE LOWER(name) = LOWER(%s)", $name
            ));
            if ($existing_id) {
                $ids[] = (int) $existing_id;
            } else {
                $wpdb->insert($wpdb->prefix . 'mb_skills', [
                    'rand_id'    => bntm_rand_id(),
                    'name'       => $name,
                    'category'   => 'General',
                    'status'     => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ], ['%s','%s','%s','%s','%s','%s']);
                $ids[] = (int) $wpdb->insert_id;
            }
        } else {
            $ids[] = intval($s['id']);
        }
    }
    return array_unique(array_filter($ids));
}

function bntm_ajax_mb_create_task() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id = get_current_user_id();
    $profile = mb_get_user_profile($user_id);
    if (!$profile || $profile->user_role !== 'client') wp_send_json_error(['message' => 'Only clients can post tasks']);

    $title       = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $budget      = floatval($_POST['budget']);
    $mode        = sanitize_text_field($_POST['mode']);

    if (empty($title) || empty($description) || $budget <= 0) {
        wp_send_json_error(['message' => 'Please fill in all required fields with a valid budget.']);
    }

    $status = ($mode === 'publish') ? 'live' : 'draft';

    $wpdb->query('START TRANSACTION');
    try {
        $inserted = $wpdb->insert($wpdb->prefix . 'mb_tasks', [
            'rand_id'     => bntm_rand_id(),
            'client_id'   => $user_id,
            'title'       => $title,
            'description' => $description,
            'budget'      => $budget,
            'status'      => $status,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
        ], ['%s','%d','%s','%s','%f','%s','%s','%s']);

        if (!$inserted) throw new Exception('Failed to create task');
        $task_id = $wpdb->insert_id;

        if (!empty($_POST['skills'])) {
            $skill_ids = mb_resolve_skill_ids($_POST['skills']);
            foreach ($skill_ids as $sid) {
                $wpdb->insert($wpdb->prefix . 'mb_task_skills', [
                    'rand_id'    => bntm_rand_id(),
                    'task_id'    => $task_id,
                    'skill_id'   => $sid,
                    'status'     => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ], ['%s','%d','%d','%s','%s','%s']);
            }
        }

        $wpdb->query('COMMIT');
        wp_send_json_success(['message' => $mode === 'publish' ? 'Task published!' : 'Task saved as draft.', 'task_id' => $task_id]);
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('MB Create Task Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to create task. Please try again.']);
    }
}

function bntm_ajax_mb_publish_task() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id = get_current_user_id();
    $task_id = intval($_POST['task_id']);

    $task = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id = %d AND client_id = %d", $task_id, $user_id
    ));
    if (!$task) wp_send_json_error(['message' => 'Task not found']);
    if ($task->status !== 'draft') wp_send_json_error(['message' => 'Only draft tasks can be published']);

    $wpdb->update($wpdb->prefix . 'mb_tasks',
        ['status' => 'live', 'updated_at' => current_time('mysql')],
        ['id' => $task_id], ['%s','%s'], ['%d']
    );

    wp_send_json_success(['message' => 'Task published!']);
}

function bntm_ajax_mb_cancel_task() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id = get_current_user_id();
    $task_id = intval($_POST['task_id']);

    $task = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id = %d AND client_id = %d", $task_id, $user_id
    ));
    if (!$task) wp_send_json_error(['message' => 'Task not found']);
    if (!in_array($task->status, ['draft', 'live'], true)) {
        wp_send_json_error(['message' => 'Only draft or live tasks can be cancelled']);
    }

    $updated = $wpdb->update($wpdb->prefix . 'mb_tasks',
        ['status' => 'cancelled', 'updated_at' => current_time('mysql')],
        ['id' => $task_id], ['%s','%s'], ['%d']
    );

    if ($updated !== false) {
        wp_send_json_success(['message' => 'Task cancelled.']);
    } else {
        wp_send_json_error(['message' => 'Failed to cancel task.']);
    }
}

function bntm_ajax_mb_get_applicants() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id = get_current_user_id();
    $task_id = intval($_POST['task_id']);

    $task = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id = %d AND client_id = %d", $task_id, $user_id
    ));
    if (!$task) wp_send_json_error(['message' => 'Task not found']);

    $apps = $wpdb->get_results($wpdb->prepare(
        "SELECT a.id, a.provider_id, a.cover_message, a.proposed_rate, a.status
         FROM {$wpdb->prefix}mb_task_applications a
         WHERE a.task_id = %d ORDER BY a.applied_at DESC", $task_id
    ));

    $applicants = [];
    foreach ($apps as $a) {
        $u = get_userdata($a->provider_id);
        $applicants[] = [
            'id'             => $a->id,
            'name'           => $u ? esc_html($u->display_name) : 'Unknown',
            'cover_message'  => esc_html($a->cover_message),
            'proposed_rate'  => number_format($a->proposed_rate, 2),
            'status'         => $a->status,
        ];
    }

    wp_send_json_success(['applicants' => $applicants]);
}

function bntm_ajax_mb_accept_applicant() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id        = get_current_user_id();
    $application_id = intval($_POST['application_id']);
    $task_id        = intval($_POST['task_id']);

    $task = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id = %d AND client_id = %d", $task_id, $user_id
    ));
    if (!$task) wp_send_json_error(['message' => 'Task not found']);
    if ($task->status !== 'live') wp_send_json_error(['message' => 'This task is not open for selection']);

    $application = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_task_applications WHERE id = %d AND task_id = %d AND status = 'pending'",
        $application_id, $task_id
    ));
    if (!$application) wp_send_json_error(['message' => 'Application not found or already processed']);

    $wpdb->query('START TRANSACTION');
    try {
        $wpdb->update($wpdb->prefix . 'mb_task_applications',
            ['status' => 'accepted', 'updated_at' => current_time('mysql')],
            ['id' => $application_id], ['%s','%s'], ['%d']
        );

        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}mb_task_applications SET status = 'rejected', updated_at = %s
             WHERE task_id = %d AND id != %d AND status = 'pending'",
            current_time('mysql'), $task_id, $application_id
        ));

        $updated = $wpdb->update($wpdb->prefix . 'mb_tasks', [
            'provider_id' => $application->provider_id,
            'status'      => 'in_progress',
            'updated_at'  => current_time('mysql'),
        ], ['id' => $task_id], ['%d','%s','%s'], ['%d']);

        if ($updated === false) throw new Exception('Failed to update task');

        $wpdb->query('COMMIT');
        wp_send_json_success(['message' => 'Applicant accepted. Task moved to In Progress.']);
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('MB Accept Applicant Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to accept applicant.']);
    }
}

function bntm_ajax_mb_reject_applicant() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id        = get_current_user_id();
    $application_id = intval($_POST['application_id']);

    $application = $wpdb->get_row($wpdb->prepare(
        "SELECT a.*, t.client_id FROM {$wpdb->prefix}mb_task_applications a
         INNER JOIN {$wpdb->prefix}mb_tasks t ON a.task_id = t.id
         WHERE a.id = %d", $application_id
    ));
    if (!$application || (int) $application->client_id !== $user_id) {
        wp_send_json_error(['message' => 'Application not found']);
    }
    if ($application->status !== 'pending') wp_send_json_error(['message' => 'Application already processed']);

    $wpdb->update($wpdb->prefix . 'mb_task_applications',
        ['status' => 'rejected', 'updated_at' => current_time('mysql')],
        ['id' => $application_id], ['%s','%s'], ['%d']
    );

    wp_send_json_success(['message' => 'Applicant rejected.']);
}

function bntm_ajax_mb_complete_task() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id = get_current_user_id();
    $task_id = intval($_POST['task_id']);

    $task = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id = %d AND client_id = %d", $task_id, $user_id
    ));
    if (!$task) wp_send_json_error(['message' => 'Task not found']);
    if ($task->status !== 'in_progress') wp_send_json_error(['message' => 'Only in-progress tasks can be completed']);

    $updated = $wpdb->update($wpdb->prefix . 'mb_tasks',
        ['status' => 'completed', 'updated_at' => current_time('mysql')],
        ['id' => $task_id], ['%s','%s'], ['%d']
    );

    if ($updated !== false) {
        wp_send_json_success(['message' => 'Task marked as completed. (Escrow release arrives in a later phase.)']);
    } else {
        wp_send_json_error(['message' => 'Failed to update task.']);
    }
}

function bntm_ajax_mb_filter_feed() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id  = get_current_user_id();
    $profile  = mb_get_user_profile($user_id);
    if (!$profile || $profile->user_role !== 'provider') wp_send_json_error(['message' => 'Only providers can browse the task feed']);

    $skill_id = intval($_POST['skill_id']);
    $search   = sanitize_text_field($_POST['search']);

    $sql = "SELECT DISTINCT t.* FROM {$wpdb->prefix}mb_tasks t";
    $where = ["t.status = 'live'"];
    $params = [];

    if ($skill_id) {
        $sql .= " INNER JOIN {$wpdb->prefix}mb_task_skills ts ON ts.task_id = t.id";
        $where[] = "ts.skill_id = %d";
        $params[] = $skill_id;
    }
    if (!empty($search)) {
        $where[] = "(t.title LIKE %s OR t.description LIKE %s)";
        $params[] = '%' . $wpdb->esc_like($search) . '%';
        $params[] = '%' . $wpdb->esc_like($search) . '%';
    }

    $sql .= ' WHERE ' . implode(' AND ', $where) . ' ORDER BY t.created_at DESC LIMIT 30';
    $tasks = empty($params) ? $wpdb->get_results($sql) : $wpdb->get_results($wpdb->prepare($sql, $params));

    $already_applied_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT task_id FROM {$wpdb->prefix}mb_task_applications WHERE provider_id = %d", $user_id
    ));

    $out = [];
    foreach ($tasks as $t) {
        $skill_names = $wpdb->get_col($wpdb->prepare(
            "SELECT s.name FROM {$wpdb->prefix}mb_task_skills ts
             INNER JOIN {$wpdb->prefix}mb_skills s ON ts.skill_id = s.id
             WHERE ts.task_id = %d", $t->id
        ));
        $client = get_userdata($t->client_id);
        $out[] = [
            'id'              => $t->id,
            'title'           => esc_html($t->title),
            'description'     => esc_html(wp_trim_words($t->description, 20)),
            'budget_fmt'      => mb_format_price($t->budget),
            'skills'          => array_map('esc_html', $skill_names),
            'client_name'     => $client ? esc_html($client->display_name) : 'Client',
            'posted_ago'      => human_time_diff(strtotime($t->created_at), current_time('timestamp')) . ' ago',
            'already_applied' => in_array((string) $t->id, array_map('strval', $already_applied_ids), true),
        ];
    }

    wp_send_json_success(['tasks' => $out]);
}

function bntm_ajax_mb_apply_to_task() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id       = get_current_user_id();
    $profile       = mb_get_user_profile($user_id);
    if (!$profile || $profile->user_role !== 'provider') wp_send_json_error(['message' => 'Only providers can apply to tasks']);

    $task_id       = intval($_POST['task_id']);
    $proposed_rate = floatval($_POST['proposed_rate']);
    $cover_message = sanitize_textarea_field($_POST['cover_message']);

    if ($proposed_rate <= 0 || empty($cover_message)) {
        wp_send_json_error(['message' => 'Please provide a valid rate and cover message.']);
    }

    $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id = %d", $task_id));
    if (!$task || $task->status !== 'live') wp_send_json_error(['message' => 'This task is no longer accepting applications']);

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}mb_task_applications WHERE task_id = %d AND provider_id = %d",
        $task_id, $user_id
    ));
    if ($existing) wp_send_json_error(['message' => 'You already applied to this task']);

    $result = $wpdb->insert($wpdb->prefix . 'mb_task_applications', [
        'rand_id'        => bntm_rand_id(),
        'task_id'        => $task_id,
        'provider_id'    => $user_id,
        'cover_message'  => $cover_message,
        'proposed_rate'  => $proposed_rate,
        'status'         => 'pending',
        'applied_at'     => current_time('mysql'),
        'created_at'     => current_time('mysql'),
        'updated_at'     => current_time('mysql'),
    ], ['%s','%d','%d','%s','%f','%s','%s','%s','%s']);

    if ($result) {
        wp_send_json_success(['message' => 'Application submitted!']);
    } else {
        wp_send_json_error(['message' => 'Failed to submit application.']);
    }
}

function bntm_ajax_mb_withdraw_application() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id        = get_current_user_id();
    $application_id = intval($_POST['application_id']);

    $application = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_task_applications WHERE id = %d AND provider_id = %d",
        $application_id, $user_id
    ));
    if (!$application) wp_send_json_error(['message' => 'Application not found']);
    if ($application->status !== 'pending') wp_send_json_error(['message' => 'Only pending applications can be withdrawn']);

    $wpdb->update($wpdb->prefix . 'mb_task_applications',
        ['status' => 'withdrawn', 'updated_at' => current_time('mysql')],
        ['id' => $application_id], ['%s','%s'], ['%d']
    );

    wp_send_json_success(['message' => 'Application withdrawn.']);
}

function bntm_ajax_mb_mark_ready_for_review() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id = get_current_user_id();
    $task_id = intval($_POST['task_id']);

    $task = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id = %d AND provider_id = %d", $task_id, $user_id
    ));
    if (!$task) wp_send_json_error(['message' => 'Task not found']);
    if ($task->status !== 'in_progress') wp_send_json_error(['message' => 'Task is not in progress']);

    $wpdb->update($wpdb->prefix . 'mb_tasks',
        ['ready_for_review' => 1, 'updated_at' => current_time('mysql')],
        ['id' => $task_id], ['%d','%s'], ['%d']
    );

    wp_send_json_success(['message' => 'Marked ready for review. The client has been notified.']);
}

function bntm_ajax_mb_add_skill() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id   = get_current_user_id();
    $profile   = mb_get_user_profile($user_id);
    if (!$profile || $profile->user_role !== 'provider') wp_send_json_error(['message' => 'Only providers can manage skills']);

    $skill_ids = mb_resolve_skill_ids($_POST['skills'] ?? '[]');

    $wpdb->query('START TRANSACTION');
    try {
        $wpdb->delete($wpdb->prefix . 'mb_provider_skills', ['user_id' => $user_id], ['%d']);
        foreach ($skill_ids as $sid) {
            $wpdb->insert($wpdb->prefix . 'mb_provider_skills', [
                'rand_id'    => bntm_rand_id(),
                'user_id'    => $user_id,
                'skill_id'   => $sid,
                'status'     => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ], ['%s','%d','%d','%s','%s','%s']);
        }
        $wpdb->query('COMMIT');
        wp_send_json_success(['message' => 'Skills updated.']);
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('MB Add Skill Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to update skills.']);
    }
}

function bntm_ajax_mb_remove_skill() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id  = get_current_user_id();
    $skill_id = intval($_POST['skill_id']);

    $wpdb->delete($wpdb->prefix . 'mb_provider_skills', ['user_id' => $user_id, 'skill_id' => $skill_id], ['%d','%d']);

    wp_send_json_success(['message' => 'Skill removed.']);
}

function bntm_ajax_mb_update_profile() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id   = get_current_user_id();
    $full_name = sanitize_text_field($_POST['full_name'] ?? '');
    $bio       = sanitize_textarea_field($_POST['bio'] ?? '');

    if (!empty($full_name)) {
        wp_update_user(['ID' => $user_id, 'display_name' => $full_name]);
    }

    $updated = $wpdb->update($wpdb->prefix . 'mb_user_profiles',
        ['bio' => $bio, 'updated_at' => current_time('mysql')],
        ['user_id' => $user_id], ['%s','%s'], ['%d']
    );

    if ($updated !== false) {
        wp_send_json_success(['message' => 'Profile updated.']);
    } else {
        wp_send_json_error(['message' => 'Failed to update profile.']);
    }
}

function bntm_ajax_mb_upload_avatar() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    if (empty($_FILES['avatar'])) wp_send_json_error(['message' => 'No file uploaded']);

    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($_FILES['avatar']['type'], $allowed_types, true)) {
        wp_send_json_error(['message' => 'Please upload a JPG, PNG, or WEBP image.']);
    }
    if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
        wp_send_json_error(['message' => 'Image must be under 2MB.']);
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attachment_id = media_handle_upload('avatar', 0);
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(['message' => 'Upload failed: ' . $attachment_id->get_error_message()]);
    }

    $url = wp_get_attachment_url($attachment_id);
    global $wpdb;
    $user_id = get_current_user_id();
    $wpdb->update($wpdb->prefix . 'mb_user_profiles',
        ['pfp_url' => $url, 'updated_at' => current_time('mysql')],
        ['user_id' => $user_id], ['%s','%s'], ['%d']
    );

    wp_send_json_success(['message' => 'Avatar updated.', 'url' => $url]);
}

function bntm_ajax_mb_update_account_info() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    $user_id = get_current_user_id();
    $email   = sanitize_email($_POST['email'] ?? '');

    if (empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address.']);
    }

    $existing = email_exists($email);
    if ($existing && (int) $existing !== $user_id) {
        wp_send_json_error(['message' => 'That email is already in use by another account.']);
    }

    $result = wp_update_user(['ID' => $user_id, 'user_email' => $email]);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => 'Account information updated.']);
}

function bntm_ajax_mb_change_password() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    $user_id          = get_current_user_id();
    $current_password = (string) ($_POST['current_password'] ?? '');
    $new_password      = (string) ($_POST['new_password'] ?? '');

    $user = get_userdata($user_id);
    if (!$user || !wp_check_password($current_password, $user->user_pass, $user_id)) {
        wp_send_json_error(['message' => 'Current password is incorrect.']);
    }
    if (strlen($new_password) < 8) {
        wp_send_json_error(['message' => 'New password must be at least 8 characters.']);
    }

    wp_set_password($new_password, $user_id);
    wp_send_json_success(['message' => 'Password changed. Please log in again next session.']);
}

function bntm_ajax_mb_update_notification_prefs() {
    check_ajax_referer('mb_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id     = get_current_user_id();
    $notif_email = intval($_POST['notif_email']) ? 1 : 0;

    $updated = $wpdb->update($wpdb->prefix . 'mb_user_profiles',
        ['notif_email' => $notif_email, 'updated_at' => current_time('mysql')],
        ['user_id' => $user_id], ['%d','%s'], ['%d']
    );

    if ($updated !== false) {
        wp_send_json_success(['message' => 'Notification preferences saved.']);
    } else {
        wp_send_json_error(['message' => 'Failed to save preferences.']);
    }
}

function bntm_ajax_mb_browse_filter() {
    global $wpdb;
    $skill_id = intval($_POST['skill_id'] ?? 0);
    $search   = sanitize_text_field($_POST['search'] ?? '');

    $sql = "SELECT DISTINCT t.* FROM {$wpdb->prefix}mb_tasks t";
    $where = ["t.status = 'live'"];
    $params = [];

    if ($skill_id) {
        $sql .= " INNER JOIN {$wpdb->prefix}mb_task_skills ts ON ts.task_id = t.id";
        $where[] = "ts.skill_id = %d";
        $params[] = $skill_id;
    }
    if (!empty($search)) {
        $where[] = "(t.title LIKE %s OR t.description LIKE %s)";
        $params[] = '%' . $wpdb->esc_like($search) . '%';
        $params[] = '%' . $wpdb->esc_like($search) . '%';
    }

    $sql .= ' WHERE ' . implode(' AND ', $where) . ' ORDER BY t.created_at DESC LIMIT 30';
    $tasks = empty($params) ? $wpdb->get_results($sql) : $wpdb->get_results($wpdb->prepare($sql, $params));

    $out = [];
    foreach ($tasks as $t) {
        $skill_names = $wpdb->get_col($wpdb->prepare(
            "SELECT s.name FROM {$wpdb->prefix}mb_task_skills ts
             INNER JOIN {$wpdb->prefix}mb_skills s ON ts.skill_id = s.id
             WHERE ts.task_id = %d", $t->id
        ));
        $out[] = [
            'id'         => $t->id,
            'title'      => esc_html($t->title),
            'description'=> esc_html(wp_trim_words($t->description, 20)),
            'budget_fmt' => mb_format_price($t->budget),
            'skills'     => array_map('esc_html', $skill_names),
            'posted_ago' => human_time_diff(strtotime($t->created_at), current_time('timestamp')) . ' ago',
        ];
    }

    wp_send_json_success(['tasks' => $out]);
}

/* ===========================================================================
   G. FRONTEND SHORTCODE FUNCTIONS (PUBLIC-FACING)
=========================================================================== */

function bntm_shortcode_mb_browse() {
    global $wpdb;
    $skills = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mb_skills WHERE status='active' ORDER BY category, name");
    $categories = [];
    foreach ($skills as $s) { $categories[$s->category] = ($categories[$s->category] ?? 0) + 1; }

    ob_start();
    ?>
    <script>
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    </script>
    <div class="bntm-mb-browse-page">
        <div class="bntm-mb-browse-hero">
            <h1>Find skilled help nearby</h1>
            <p>Browse live tasks posted by clients in your area, filtered by category.</p>
            <?php if (!is_user_logged_in()): ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="bntm-btn-primary">Sign Up / Log In</a>
            <?php endif; ?>
        </div>

        <div class="bntm-mb-browse-filters">
            <select id="mb-browse-skill-filter">
                <option value="">All Categories</option>
                <?php foreach ($skills as $s): ?>
                    <option value="<?php echo $s->id; ?>"><?php echo esc_html($s->name . ' (' . $s->category . ')'); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="mb-browse-search" placeholder="Search tasks...">
        </div>

        <div id="mb-browse-results" class="bntm-mb-browse-grid">
            <p style="color:#6b7280;">Loading tasks...</p>
        </div>
    </div>

    <style>
    .bntm-mb-browse-page { max-width: 1100px; margin: 0 auto; padding: 32px 16px; }
    .bntm-mb-browse-hero { text-align: center; padding: 40px 16px; margin-bottom: 24px; }
    .bntm-mb-browse-hero h1 { font-size: 30px; margin-bottom: 8px; }
    .bntm-mb-browse-hero p { color: #6b7280; margin-bottom: 18px; }
    .bntm-mb-browse-filters { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-bottom: 24px; }
    .bntm-mb-browse-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: 16px; }
    .bntm-mb-browse-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; }
    .bntm-mb-browse-card h4 { margin: 0 0 6px; font-size: 16px; }
    .bntm-mb-browse-card p { font-size: 13px; color: #4b5563; margin: 0 0 10px; }
    @media (max-width: 600px) {
        .bntm-mb-browse-hero h1 { font-size: 22px; }
        .bntm-mb-browse-filters { flex-direction: column; }
    }
    </style>

    <script>
    (function() {
        function loadBrowse() {
            var fd = new FormData();
            fd.append('action', 'mb_browse_filter');
            fd.append('skill_id', document.getElementById('mb-browse-skill-filter').value);
            fd.append('search', document.getElementById('mb-browse-search').value);

            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                var box = document.getElementById('mb-browse-results');
                if (!json.success || json.data.tasks.length === 0) {
                    box.innerHTML = '<p style="color:#6b7280;">No live tasks match right now.</p>';
                    return;
                }
                var html = '';
                json.data.tasks.forEach(function(t) {
                    html += '<div class="bntm-mb-browse-card"><h4>' + t.title + '</h4><p>' + t.description + '</p>' +
                        '<div>' + t.skills.map(function(s) { return '<span class="bntm-skill-pill">' + s + '</span>'; }).join('') + '</div>' +
                        '<div class="bntm-task-meta" style="margin-top:10px;"><span>' + t.budget_fmt + '</span><span>' + t.posted_ago + '</span></div></div>';
                });
                box.innerHTML = html;
            });
        }
        document.getElementById('mb-browse-skill-filter').addEventListener('change', loadBrowse);
        var t;
        document.getElementById('mb-browse-search').addEventListener('input', function() { clearTimeout(t); t = setTimeout(loadBrowse, 350); });
        loadBrowse();
    })();
    </script>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_profile_view() {
    global $wpdb;
    $user_id = isset($_GET['mb_user']) ? intval($_GET['mb_user']) : 0;
    if (!$user_id) return '<div class="bntm-notice">No profile specified.</div>';

    $user    = get_userdata($user_id);
    $profile = mb_get_user_profile($user_id);
    if (!$user || !$profile) return '<div class="bntm-notice">Profile not found.</div>';

    $skills = [];
    if ($profile->user_role === 'provider') {
        $skills = $wpdb->get_results($wpdb->prepare(
            "SELECT s.name FROM {$wpdb->prefix}mb_provider_skills ps
             INNER JOIN {$wpdb->prefix}mb_skills s ON ps.skill_id = s.id
             WHERE ps.user_id = %d AND ps.status = 'active'", $user_id
        ));
    }

    $completed_count = 0;
    if ($profile->user_role === 'provider') {
        $completed_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE provider_id = %d AND status = 'completed'", $user_id
        ));
    } else {
        $completed_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE client_id = %d AND status = 'completed'", $user_id
        ));
    }

    ob_start();
    ?>
    <div class="bntm-mb-profile-view">
        <div class="bntm-mb-profile-header">
            <img class="bntm-avatar" style="width:84px;height:84px;" src="<?php echo esc_url($profile->pfp_url ?: 'https://www.gravatar.com/avatar/?d=mp'); ?>" alt="">
            <div>
                <h2><?php echo esc_html($user->display_name); ?>
                    <?php if ($profile->verification_status === 'verified'): ?>
                        <span class="bntm-status-badge bntm-status-completed">Verified</span>
                    <?php endif; ?>
                </h2>
                <p style="color:#6b7280;text-transform:capitalize;"><?php echo esc_html($profile->user_role); ?> &middot; <?php echo $completed_count; ?> tasks completed</p>
            </div>
        </div>
        <?php if (!empty($profile->bio)): ?>
            <div class="bntm-form-section"><h3>About</h3><p><?php echo nl2br(esc_html($profile->bio)); ?></p></div>
        <?php endif; ?>
        <?php if (!empty($skills)): ?>
            <div class="bntm-form-section">
                <h3>Skills</h3>
                <?php foreach ($skills as $s): ?><span class="bntm-skill-pill"><?php echo esc_html($s->name); ?></span><?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <style>
    .bntm-mb-profile-view { max-width: 700px; margin: 0 auto; padding: 32px 16px; }
    .bntm-mb-profile-header { display: flex; gap: 18px; align-items: center; margin-bottom: 20px; }
    .bntm-mb-profile-header h2 { margin: 0; }
    @media (max-width: 500px) { .bntm-mb-profile-header { flex-direction: column; text-align: center; } }
    </style>
    <?php
    return ob_get_clean();
}

/* ===========================================================================
   G2. ADDITIONAL FRONTEND SHORTCODE FUNCTIONS
=========================================================================== */

// ---------------------------------------------------------------------------
// [mb_home] — Landing / Marketing Page
// ---------------------------------------------------------------------------
function bntm_shortcode_mb_home() {
    global $wpdb;
    $live_count      = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE status = 'live'");
    $provider_count  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mb_user_profiles WHERE user_role = 'provider' AND status = 'active'");
    $completed_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE status = 'completed'");

    $browse_page  = get_page_by_path('browse-tasks');
    $browse_url   = $browse_page ? get_permalink($browse_page->ID) : '#';
    $login_url    = wp_login_url();
    $register_url = wp_registration_url();

    ob_start();
    ?>
    <div class="mb-home-wrap">

        <!-- Hero -->
        <section class="mb-hero">
            <div class="mb-hero-inner">
                <h1>Find skilled help, right in your community.</h1>
                <p>MentorBe connects you with verified local providers for any task — home repairs, tutoring, design, and more.</p>
                <div class="mb-hero-cta">
                    <?php if (!is_user_logged_in()): ?>
                        <a href="<?php echo esc_url($register_url); ?>" class="bntm-btn-primary mb-btn-lg">Get Started Free</a>
                        <a href="<?php echo esc_url($browse_url); ?>" class="bntm-btn-secondary mb-btn-lg">Browse Tasks</a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(home_url('/mentorbe-dashboard')); ?>" class="bntm-btn-primary mb-btn-lg">Go to Dashboard</a>
                        <a href="<?php echo esc_url($browse_url); ?>" class="bntm-btn-secondary mb-btn-lg">Browse Tasks</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="mb-stats-bar">
            <div class="mb-stat-item">
                <span class="mb-stat-num"><?php echo number_format($live_count); ?>+</span>
                <span class="mb-stat-label">Live Tasks</span>
            </div>
            <div class="mb-stat-item">
                <span class="mb-stat-num"><?php echo number_format($provider_count); ?>+</span>
                <span class="mb-stat-label">Skilled Providers</span>
            </div>
            <div class="mb-stat-item">
                <span class="mb-stat-num"><?php echo number_format($completed_count); ?>+</span>
                <span class="mb-stat-label">Tasks Completed</span>
            </div>
        </section>

        <!-- How it works -->
        <section class="mb-how-section">
            <h2>How MentorBe Works</h2>
            <div class="mb-how-grid">
                <div class="mb-how-card">
                    <div class="mb-how-icon">
                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <h4>1. Post a Task</h4>
                    <p>Describe what you need, set your budget, and add the skills required. Takes less than 2 minutes.</p>
                </div>
                <div class="mb-how-card">
                    <div class="mb-how-icon">
                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h4>2. Receive Proposals</h4>
                    <p>Skilled providers in your area apply with their rate and a cover message. Review and compare at your own pace.</p>
                </div>
                <div class="mb-how-card">
                    <div class="mb-how-icon">
                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h4>3. Get It Done</h4>
                    <p>Accept the best applicant, collaborate through your inbox, and approve the work when you're satisfied.</p>
                </div>
            </div>
        </section>

        <!-- Categories -->
        <?php
        $categories = $wpdb->get_results(
            "SELECT category, COUNT(*) as skill_count FROM {$wpdb->prefix}mb_skills WHERE status='active' GROUP BY category ORDER BY skill_count DESC LIMIT 8"
        );
        if (!empty($categories)): ?>
        <section class="mb-categories-section">
            <h2>Popular Categories</h2>
            <div class="mb-categories-grid">
                <?php foreach ($categories as $cat): ?>
                    <a href="<?php echo esc_url($browse_url); ?>" class="mb-category-chip">
                        <?php echo esc_html($cat->category); ?>
                        <span><?php echo $cat->skill_count; ?> skills</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- CTA Banner -->
        <?php if (!is_user_logged_in()): ?>
        <section class="mb-cta-banner">
            <h2>Ready to get started?</h2>
            <p>Join MentorBe as a Client to post tasks, or as a Provider to earn by sharing your skills.</p>
            <div class="mb-hero-cta">
                <a href="<?php echo esc_url($register_url); ?>" class="bntm-btn-primary mb-btn-lg">Sign Up Now</a>
                <a href="<?php echo esc_url($login_url); ?>" class="bntm-btn-secondary mb-btn-lg">Log In</a>
            </div>
        </section>
        <?php endif; ?>

    </div>

    <style>
    .mb-home-wrap { max-width: 1100px; margin: 0 auto; padding: 0 16px 48px; }
    .mb-hero { background: var(--bntm-primary); border-radius: 16px; padding: 64px 32px; margin-bottom: 32px; text-align: center; }
    .mb-hero h1 { font-size: 34px; color: #fff; margin: 0 0 12px; line-height: 1.2; }
    .mb-hero p { font-size: 17px; color: rgba(255,255,255,.85); margin: 0 0 28px; }
    .mb-hero-cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .mb-btn-lg { padding: 12px 28px; font-size: 15px; }
    .mb-stats-bar { display: flex; justify-content: center; gap: 48px; padding: 28px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 40px; flex-wrap: wrap; }
    .mb-stat-item { text-align: center; }
    .mb-stat-num { display: block; font-size: 28px; font-weight: 700; color: var(--bntm-primary); }
    .mb-stat-label { font-size: 13px; color: #6b7280; }
    .mb-how-section, .mb-categories-section, .mb-cta-banner { margin-bottom: 48px; }
    .mb-how-section h2, .mb-categories-section h2, .mb-cta-banner h2 { text-align: center; font-size: 24px; margin-bottom: 24px; }
    .mb-how-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 20px; }
    .mb-how-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; text-align: center; }
    .mb-how-icon { width: 52px; height: 52px; background: var(--bntm-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; color: #fff; }
    .mb-how-card h4 { margin: 0 0 8px; font-size: 16px; }
    .mb-how-card p { font-size: 13px; color: #6b7280; margin: 0; }
    .mb-categories-grid { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
    .mb-category-chip { display: flex; flex-direction: column; align-items: center; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 20px; text-decoration: none; color: #111827; font-weight: 600; font-size: 14px; transition: border-color .15s; min-width: 120px; }
    .mb-category-chip:hover { border-color: var(--bntm-primary); color: var(--bntm-primary); }
    .mb-category-chip span { font-size: 11px; color: #9ca3af; font-weight: 400; margin-top: 3px; }
    .mb-cta-banner { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 16px; padding: 48px 24px; text-align: center; }
    .mb-cta-banner p { color: #6b7280; margin-bottom: 20px; }
    @media (max-width: 600px) {
        .mb-hero { padding: 40px 20px; }
        .mb-hero h1 { font-size: 24px; }
        .mb-stats-bar { gap: 24px; }
    }
    </style>
    <?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// [mb_help] — Help Center / FAQ
// ---------------------------------------------------------------------------
function bntm_shortcode_mb_help() {
    $faqs = [
        'For Clients' => [
            [
                'q' => 'How do I post a task?',
                'a' => 'Log in, go to your Client Dashboard, and click "Post a Task". Fill in the title, description, required skills, and your budget. You can save it as a draft or publish it immediately for providers to see.'
            ],
            [
                'q' => 'How do I choose a provider?',
                'a' => 'Once your task is live, providers will submit applications with their proposed rate and a cover message. Go to Manage Tasks, click "View Applicants", review their profiles and proposals, then click Accept on the one you want. All other applicants are automatically declined.'
            ],
            [
                'q' => 'What happens after I accept a provider?',
                'a' => 'The task moves to "In Progress". You can communicate with the provider through your Inbox. When the provider marks their work ready for review, you can inspect the deliverables and mark the task as Completed.'
            ],
            [
                'q' => 'Can I cancel a task?',
                'a' => 'Yes — you can cancel any task that is still in Draft or Live status from your Manage Tasks tab. Tasks that are already In Progress cannot be cancelled directly; please use the dispute process if there is an issue.'
            ],
            [
                'q' => 'How is payment handled?',
                'a' => 'MentorBe uses an escrow-based payment system (coming soon). Your budget is held securely and released to the provider only after you approve the completed work. Refunds for cancelled tasks are processed automatically.'
            ],
        ],
        'For Providers' => [
            [
                'q' => 'How do I find tasks that match my skills?',
                'a' => 'Go to your Provider Dashboard and open the Task Feed tab. Use the skill filter or search bar to find tasks relevant to your expertise. Only live tasks are shown.'
            ],
            [
                'q' => 'How do I apply to a task?',
                'a' => 'Click "Apply" on any task card in the feed. Enter your proposed rate and write a cover message explaining your approach and experience. You can only apply to each task once.'
            ],
            [
                'q' => 'How do I withdraw an application?',
                'a' => 'Go to My Applications and click "Withdraw" next to any pending application. Accepted applications cannot be withdrawn — contact support if you need assistance.'
            ],
            [
                'q' => 'How do I get paid?',
                'a' => 'Once a client accepts your application and you complete the work, submit it for review via the Active Jobs tab. When the client approves, your earnings are released to your account. The Earnings page shows your full payment history.'
            ],
            [
                'q' => 'How do I get verified?',
                'a' => 'Go to Settings and find the Identity Verification section. Verification by MentorBe admins is being rolled out — once verified, a badge will appear on your public profile, increasing trust with clients.'
            ],
        ],
        'Account & General' => [
            [
                'q' => 'Can I switch between Client and Provider?',
                'a' => 'Your role is set during onboarding. To change roles, please contact support — we\'re building multi-role support in an upcoming update.'
            ],
            [
                'q' => 'How do I update my email or password?',
                'a' => 'Go to your Dashboard, open the Settings tab, and find the Personal Information and Account Security sections.'
            ],
            [
                'q' => 'How do I report a problem or dispute?',
                'a' => 'The dispute resolution system is coming in the next platform update. In the meantime, please reach out to our support team through the contact form.'
            ],
        ],
    ];

    ob_start();
    ?>
    <div class="mb-help-wrap">
        <div class="mb-help-hero">
            <h1>Help Center</h1>
            <p>Find answers to common questions about using MentorBe.</p>
            <input type="text" id="mb-faq-search" placeholder="Search questions..." class="mb-faq-search-input">
        </div>

        <div id="mb-faq-content">
        <?php foreach ($faqs as $section => $items): ?>
            <div class="mb-faq-section" data-section="<?php echo esc_attr(strtolower($section)); ?>">
                <h2><?php echo esc_html($section); ?></h2>
                <?php foreach ($items as $faq): ?>
                    <div class="mb-faq-item" data-question="<?php echo esc_attr(strtolower($faq['q'] . ' ' . $faq['a'])); ?>">
                        <button class="mb-faq-question" type="button">
                            <span><?php echo esc_html($faq['q']); ?></span>
                            <svg class="mb-faq-chevron" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="mb-faq-answer">
                            <p><?php echo esc_html($faq['a']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <p id="mb-faq-no-results" style="display:none;text-align:center;color:#6b7280;padding:32px 0;">No results found for your search.</p>
        </div>
    </div>

    <style>
    .mb-help-wrap { max-width: 760px; margin: 0 auto; padding: 32px 16px 64px; }
    .mb-help-hero { text-align: center; margin-bottom: 36px; }
    .mb-help-hero h1 { font-size: 28px; margin-bottom: 8px; }
    .mb-help-hero p { color: #6b7280; margin-bottom: 18px; }
    .mb-faq-search-input { display: block; width: 100%; max-width: 440px; margin: 0 auto; padding: 11px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
    .mb-faq-section { margin-bottom: 32px; }
    .mb-faq-section h2 { font-size: 18px; color: var(--bntm-primary); margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
    .mb-faq-item { border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 8px; overflow: hidden; }
    .mb-faq-question { width: 100%; background: #fff; border: none; padding: 14px 16px; text-align: left; font-size: 15px; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .mb-faq-question:hover { background: #f9fafb; }
    .mb-faq-chevron { flex-shrink: 0; transition: transform .2s; }
    .mb-faq-item.open .mb-faq-chevron { transform: rotate(180deg); }
    .mb-faq-answer { display: none; padding: 0 16px 14px; background: #fff; }
    .mb-faq-answer p { margin: 0; color: #4b5563; font-size: 14px; line-height: 1.6; }
    .mb-faq-item.open .mb-faq-answer { display: block; }
    </style>
    <script>
    (function() {
        document.querySelectorAll('.mb-faq-question').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var item = this.closest('.mb-faq-item');
                var isOpen = item.classList.contains('open');
                document.querySelectorAll('.mb-faq-item.open').forEach(function(el) { el.classList.remove('open'); });
                if (!isOpen) item.classList.add('open');
            });
        });

        document.getElementById('mb-faq-search').addEventListener('input', function() {
            var q = this.value.toLowerCase().trim();
            var anyVisible = false;
            document.querySelectorAll('.mb-faq-item').forEach(function(item) {
                var text = item.dataset.question;
                var match = !q || text.indexOf(q) !== -1;
                item.style.display = match ? '' : 'none';
                if (match) anyVisible = true;
            });
            document.querySelectorAll('.mb-faq-section').forEach(function(sec) {
                var hasVisible = Array.from(sec.querySelectorAll('.mb-faq-item')).some(function(el) { return el.style.display !== 'none'; });
                sec.style.display = hasVisible ? '' : 'none';
            });
            document.getElementById('mb-faq-no-results').style.display = anyVisible ? 'none' : 'block';
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// [mb_terms] — Terms of Service & Privacy Policy
// ---------------------------------------------------------------------------
function bntm_shortcode_mb_terms() {
    ob_start();
    ?>
    <div class="mb-terms-wrap">
        <div class="mb-terms-nav">
            <a href="#terms" class="mb-terms-nav-link active">Terms of Service</a>
            <a href="#privacy" class="mb-terms-nav-link">Privacy Policy</a>
        </div>

        <section id="terms" class="mb-terms-section">
            <h1>Terms of Service</h1>
            <p class="mb-terms-date">Last updated: <?php echo date('F j, Y'); ?></p>

            <h2>1. Acceptance of Terms</h2>
            <p>By accessing or using MentorBe, you agree to be bound by these Terms of Service. If you do not agree, please do not use the platform.</p>

            <h2>2. User Roles</h2>
            <p>MentorBe operates with two primary user roles: <strong>Clients</strong>, who post tasks and hire providers, and <strong>Providers</strong>, who offer their skills and complete tasks. Each role carries distinct responsibilities as described throughout these terms.</p>

            <h2>3. Account Registration</h2>
            <p>You must provide accurate information during registration. You are responsible for maintaining the security of your account credentials. MentorBe is not liable for losses resulting from unauthorized account access.</p>

            <h2>4. Task Posting</h2>
            <p>Clients may post tasks that comply with applicable laws. Tasks involving illegal activity, harassment, or violation of third-party rights are strictly prohibited and will result in immediate account suspension.</p>

            <h2>5. Applications and Contracts</h2>
            <p>When a Client accepts a Provider application, a binding service agreement is formed between those two parties. MentorBe facilitates but is not a party to this agreement.</p>

            <h2>6. Payments and Escrow</h2>
            <p>MentorBe holds task budgets in escrow upon applicant acceptance. Funds are released to the Provider upon Client approval of completed work. Disputed payments are subject to the Dispute Resolution process.</p>

            <h2>7. Dispute Resolution</h2>
            <p>If a Client and Provider cannot resolve a disagreement, either party may escalate to MentorBe moderation. Admin decisions on financial resolutions are final.</p>

            <h2>8. Prohibited Conduct</h2>
            <p>Users must not: impersonate others, post false reviews, circumvent platform fees, use MentorBe for spam, or engage in any fraudulent activity. Violations result in suspension or permanent banning.</p>

            <h2>9. Limitation of Liability</h2>
            <p>MentorBe is provided "as is". We are not liable for the quality of services rendered by Providers or for damages arising from platform use beyond the amount held in escrow for any given transaction.</p>

            <h2>10. Modifications</h2>
            <p>We may update these terms at any time. Continued use of MentorBe after updates constitutes acceptance of the revised terms.</p>
        </section>

        <section id="privacy" class="mb-terms-section" style="margin-top:48px;">
            <h1>Privacy Policy</h1>
            <p class="mb-terms-date">Last updated: <?php echo date('F j, Y'); ?></p>

            <h2>1. Information We Collect</h2>
            <p>We collect information you provide during registration (name, email, role), profile updates (bio, avatar, skills), task activity (posts, applications, messages), and payment data processed through our escrow system.</p>

            <h2>2. How We Use Your Information</h2>
            <p>Your information is used to operate the MentorBe platform, match tasks with providers, process payments, communicate platform updates, and resolve disputes.</p>

            <h2>3. Information Sharing</h2>
            <p>We do not sell your personal information. We share data only with parties directly involved in a task transaction and with service providers necessary to operate the platform.</p>

            <h2>4. Profile Visibility</h2>
            <p>Your display name, bio, skills, and completed task count are visible on your public profile. Your email address and financial information are never publicly displayed.</p>

            <h2>5. Data Retention</h2>
            <p>We retain your account data for as long as your account is active. You may request account deletion at any time; financial records may be retained for up to 7 years to comply with legal obligations.</p>

            <h2>6. Security</h2>
            <p>Passwords are stored as salted hashes. Financial transactions use secure escrow channels. We apply reasonable technical and organizational measures to protect your data.</p>

            <h2>7. Cookies</h2>
            <p>MentorBe uses cookies for authentication and session management only. We do not use third-party advertising cookies.</p>

            <h2>8. Your Rights</h2>
            <p>You have the right to access, correct, or delete your personal data. Contact our support team to exercise these rights.</p>

            <h2>9. Contact</h2>
            <p>Questions about this Privacy Policy may be sent to the site administrator through the Help Center.</p>
        </section>
    </div>

    <style>
    .mb-terms-wrap { max-width: 760px; margin: 0 auto; padding: 32px 16px 64px; }
    .mb-terms-nav { display: flex; gap: 8px; margin-bottom: 32px; border-bottom: 2px solid #e5e7eb; padding-bottom: 0; }
    .mb-terms-nav-link { padding: 10px 20px; font-weight: 600; font-size: 14px; text-decoration: none; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: color .15s, border-color .15s; }
    .mb-terms-nav-link:hover, .mb-terms-nav-link.active { color: var(--bntm-primary); border-bottom-color: var(--bntm-primary); }
    .mb-terms-section h1 { font-size: 26px; margin-bottom: 4px; }
    .mb-terms-date { color: #9ca3af; font-size: 13px; margin-bottom: 28px; }
    .mb-terms-section h2 { font-size: 16px; margin: 24px 0 8px; }
    .mb-terms-section p { color: #4b5563; font-size: 14px; line-height: 1.7; margin: 0 0 12px; }
    </style>
    <script>
    (function() {
        document.querySelectorAll('.mb-terms-nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                document.querySelectorAll('.mb-terms-nav-link').forEach(function(l) { l.classList.remove('active'); });
                this.classList.add('active');
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// [mb_inbox] — Inbox / Messaging (Phase 2 stub)
// ---------------------------------------------------------------------------
function bntm_shortcode_mb_inbox() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to access your inbox.</div>';
    }
    ob_start();
    ?>
    <div class="mb-coming-soon-wrap">
        <div class="mb-coming-soon-icon">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        </div>
        <h2>Inbox</h2>
        <p>Pre-commitment messaging and in-task communication is coming in Phase 2. You will be able to negotiate terms, share files, and communicate securely with clients or providers directly here.</p>
        <a href="<?php echo esc_url(home_url('/mentorbe-dashboard')); ?>" class="bntm-btn-secondary">Back to Dashboard</a>
    </div>
    <?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// [mb_checkout] — Checkout / Billing (Phase 2 stub)
// ---------------------------------------------------------------------------
function bntm_shortcode_mb_checkout() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to access billing.</div>';
    }
    ob_start();
    ?>
    <div class="mb-coming-soon-wrap">
        <div class="mb-coming-soon-icon">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        </div>
        <h2>Checkout &amp; Billing</h2>
        <p>Escrow-based payments are coming in Phase 2. Once live, your budget will be securely held here upon accepting a provider and released automatically when you approve completed work.</p>
        <a href="<?php echo esc_url(home_url('/mentorbe-dashboard')); ?>" class="bntm-btn-secondary">Back to Dashboard</a>
    </div>
    <?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// [mb_earnings] — Provider Earnings (Phase 2 stub)
// ---------------------------------------------------------------------------
function bntm_shortcode_mb_earnings() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to view your earnings.</div>';
    }
    ob_start();
    ?>
    <div class="mb-coming-soon-wrap">
        <div class="mb-coming-soon-icon">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2M4.93 4.93l14.14 14.14"/></svg>
        </div>
        <h2>Earnings</h2>
        <p>Your full earnings ledger, payout history, and withdrawal options are coming in Phase 2. Completed task payments will be tracked and displayed here once the escrow system is live.</p>
        <a href="<?php echo esc_url(home_url('/mentorbe-dashboard')); ?>" class="bntm-btn-secondary">Back to Dashboard</a>
    </div>
    <?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// [mb_admin] — Admin Dashboard (Phase 3 stub)
// ---------------------------------------------------------------------------
function bntm_shortcode_mb_admin() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in.</div>';
    }
    $profile = mb_get_user_profile(get_current_user_id());
    if (!$profile || $profile->user_role !== 'admin') {
        return '<div class="bntm-notice">Access restricted to administrators.</div>';
    }
    ob_start();
    ?>
    <div class="mb-coming-soon-wrap">
        <div class="mb-coming-soon-icon">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <h2>Admin Dashboard</h2>
        <p>The full admin panel — including user verification, task moderation, dispute resolution, and financial auditing — is coming in Phase 3.</p>
        <p style="font-size:13px;color:#9ca3af;">You are logged in as an administrator. Your role is recognized.</p>
    </div>

    <?php
    // Quick admin stats while the full dashboard is pending
    global $wpdb;
    $stats = [
        'Total Users'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mb_user_profiles"),
        'Live Tasks'       => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE status = 'live'"),
        'In Progress'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE status = 'in_progress'"),
        'Completed Tasks'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE status = 'completed'"),
        'Total Providers'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mb_user_profiles WHERE user_role = 'provider'"),
        'Unverified Users' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mb_user_profiles WHERE verification_status = 'unverified'"),
    ];
    ?>
    <div class="bntm-stats-row" style="margin-top:24px;flex-wrap:wrap;">
        <?php foreach ($stats as $label => $value): ?>
        <div class="bntm-stat-card">
            <div class="stat-content">
                <h3><?php echo esc_html($label); ?></h3>
                <p class="stat-number"><?php echo number_format($value); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/* ===========================================================================
   H. HELPER FUNCTIONS
=========================================================================== */

function mb_get_user_profile($user_id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_user_profiles WHERE user_id = %d", $user_id
    ));
}

function mb_format_price($amount) {
    return '&#8369;' . number_format((float) $amount, 2);
}

function mb_get_stats() {
    global $wpdb;
    $tasks_table = $wpdb->prefix . 'mb_tasks';
    return [
        'total_tasks'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tasks_table}"),
        'live_tasks'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tasks_table} WHERE status = 'live'"),
        'completed_tasks' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tasks_table} WHERE status = 'completed'"),
    ];
}

function mb_check_dependencies() {
    $required = [];
    $missing  = [];
    foreach ($required as $mod) {
        if (!bntm_is_module_enabled($mod)) $missing[] = $mod;
    }
    return empty($missing) ? true : $missing;
}