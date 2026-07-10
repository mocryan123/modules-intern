<?php
/**
 * Module Name: MentorBe
 * Module Slug: mb
 * Description: Hyper-local professional marketplace connecting Clients who post tasks with skill-tagged Providers. Phase 1 covers user onboarding, task posting, skill-matched feed, and the application/selection workflow.
 * Version: 1.0.0
 * Author: BNTM
 * Icon: <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BNTM_MB_PATH', dirname( __FILE__ ) . '/' );
define( 'BNTM_MB_URL',  plugin_dir_url( __FILE__ ) );

/* ==========================================================================
   B. MODULE CONFIGURATION
========================================================================== */

function bntm_mb_get_pages() {
    return [
        // General
        'Landing Page'                        => '[mb_home]',
        'Help Center/FAQ'                     => '[mb_help]',
        'Terms of Service and Privacy Policy' => '[mb_terms]',
        'Login'                               => '[mb_login]',
        'Forgot Password'                     => '[mb_forgot_password]',
        'Reset Password'                      => '[mb_reset_password]',
        'Sign Up'                             => '[mb_register]',
        'Choose Role'                         => '[mb_role_choice]',
        'Profile Setup'                       => '[mb_profile_setup]',
        // Client
        "Client's Dashboard"                  => '[mb_client_dashboard]',
        'Checkout/Billing'                    => '[mb_checkout]',
        'Inbox'                               => '[mb_inbox]',
        'Public Profile View'                 => '[mb_profile_view]',
        // Provider
        'Home/Task Feed'                      => '[mb_provider_dashboard]',
        'Earnings'                            => '[mb_earnings]',
        // Admin
        'Dashboard/System Overview'           => '[mb_admin]',
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

        'mb_chat_conversations' => "CREATE TABLE {$prefix}mb_chat_conversations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            user_a_id BIGINT UNSIGNED NOT NULL,
            user_b_id BIGINT UNSIGNED NOT NULL,
            task_id BIGINT UNSIGNED DEFAULT NULL,
            last_message TEXT,
            last_message_at DATETIME DEFAULT NULL,
            unread_for_a TINYINT(1) NOT NULL DEFAULT 0,
            unread_for_b TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY user_a_idx (user_a_id),
            KEY user_b_idx (user_b_id)
        ) {$charset};",

        'mb_chat_messages' => "CREATE TABLE {$prefix}mb_chat_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            conversation_id BIGINT UNSIGNED NOT NULL,
            sender_id BIGINT UNSIGNED NOT NULL,
            body TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY conversation_idx (conversation_id)
        ) {$charset};",

        'mb_reviews' => "CREATE TABLE {$prefix}mb_reviews (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            task_id BIGINT UNSIGNED NOT NULL,
            reviewer_id BIGINT UNSIGNED NOT NULL,
            reviewee_id BIGINT UNSIGNED NOT NULL,
            reviewer_role VARCHAR(20) NOT NULL DEFAULT 'client',
            rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
            review_text TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY review_unique (task_id, reviewer_id),
            KEY reviewee_idx (reviewee_id),
            KEY task_idx (task_id)
        ) {$charset};",

        'mb_notifications' => "CREATE TABLE {$prefix}mb_notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'system',
            title VARCHAR(160) NOT NULL,
            body TEXT NOT NULL,
            target_url VARCHAR(255) DEFAULT '',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY user_idx (user_id),
            KEY read_idx (is_read)
        ) {$charset};",

        'mb_payouts' => "CREATE TABLE {$prefix}mb_payouts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            task_id BIGINT UNSIGNED NOT NULL,
            provider_id BIGINT UNSIGNED NOT NULL,
            gross_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            net_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payout_method VARCHAR(50) NOT NULL DEFAULT 'gcash',
            payout_account VARCHAR(120) DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY payout_task_unique (task_id),
            KEY provider_idx (provider_id)
        ) {$charset};",
    ];
}

function bntm_mb_get_shortcodes() {
    return [
        'mb_home'               => 'bntm_shortcode_mb_home',
        'mb_help'               => 'bntm_shortcode_mb_help',
        'mb_terms'              => 'bntm_shortcode_mb_terms',
        'mb_login'              => 'bntm_shortcode_mb_login',
        'mb_forgot_password'    => 'bntm_shortcode_mb_forgot_password',
        'mb_reset_password'     => 'bntm_shortcode_mb_reset_password',
        'mb_register'           => 'bntm_shortcode_mb_register',
        'mb_role_choice'        => 'bntm_shortcode_mb_role_choice',
        'mb_profile_setup'      => 'bntm_shortcode_mb_profile_setup',
        'mb_client_dashboard'   => 'bntm_shortcode_mb_client',
        'mb_checkout'           => 'bntm_shortcode_mb_checkout',
        'mb_inbox'              => 'bntm_shortcode_mb_inbox',
        'mb_profile_view'       => 'bntm_shortcode_mb_profile_view',
        'mb_provider_dashboard' => 'bntm_shortcode_mb_provider',
        'mb_earnings'           => 'bntm_shortcode_mb_earnings',
        'mb_admin'              => 'bntm_shortcode_mb_admin',
    ];
}

function bntm_mb_create_tables() {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    foreach ( bntm_mb_get_tables() as $sql ) {
        dbDelta( $sql );
    }
    bntm_mb_ensure_message_tables();
    $created = 0;
    foreach ( array_keys( bntm_mb_get_tables() ) as $table ) {
        if ( bntm_mb_table_exists( $table ) ) {
            $created++;
        }
    }
    return $created;
}

function bntm_mb_table_exists( $table ) {
    global $wpdb;
    return (bool) $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->prefix . $table ) );
}

function bntm_mb_maybe_add_table_column( $table, $column, $definition ) {
    global $wpdb;
    $table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) );
    if ( ! $exists ) {
        $wpdb->query( "ALTER TABLE {$table} ADD COLUMN {$definition}" );
    }
}

function bntm_mb_ensure_message_tables() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $tables = bntm_mb_get_tables();
    foreach ( [ 'mb_chat_conversations', 'mb_chat_messages' ] as $table ) {
        if ( isset( $tables[ $table ] ) ) {
            dbDelta( $tables[ $table ] );
        }
    }

    // dbDelta() sometimes silently fails to create these tables, so keep a
    // direct fallback path for the inbox tables only.
    $charset = $wpdb->get_charset_collate();
    $fallback_tables = [
        'mb_chat_conversations' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mb_chat_conversations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            user_a_id BIGINT UNSIGNED NOT NULL,
            user_b_id BIGINT UNSIGNED NOT NULL,
            task_id BIGINT UNSIGNED DEFAULT NULL,
            last_message TEXT,
            last_message_at DATETIME DEFAULT NULL,
            unread_for_a TINYINT(1) NOT NULL DEFAULT 0,
            unread_for_b TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY user_a_idx (user_a_id),
            KEY user_b_idx (user_b_id)
        ) {$charset};",
        'mb_chat_messages' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mb_chat_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            conversation_id BIGINT UNSIGNED NOT NULL,
            sender_id BIGINT UNSIGNED NOT NULL,
            body TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY conversation_idx (conversation_id)
        ) {$charset};",
    ];
    foreach ( $fallback_tables as $sql ) {
        $wpdb->query( $sql );
    }
}

function bntm_mb_ensure_review_notification_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $tables = [
        'mb_reviews' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mb_reviews (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            task_id BIGINT UNSIGNED NOT NULL,
            reviewer_id BIGINT UNSIGNED NOT NULL,
            reviewee_id BIGINT UNSIGNED NOT NULL,
            reviewer_role VARCHAR(20) NOT NULL DEFAULT 'client',
            rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
            review_text TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY review_unique (task_id, reviewer_id),
            KEY reviewee_idx (reviewee_id),
            KEY task_idx (task_id)
        ) {$charset};",
        'mb_notifications' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mb_notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'system',
            title VARCHAR(160) NOT NULL,
            body TEXT NOT NULL,
            target_url VARCHAR(255) DEFAULT '',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY user_idx (user_id),
            KEY read_idx (is_read)
        ) {$charset};",
        'mb_payouts' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mb_payouts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            task_id BIGINT UNSIGNED NOT NULL,
            provider_id BIGINT UNSIGNED NOT NULL,
            gross_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            net_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payout_method VARCHAR(50) NOT NULL DEFAULT 'gcash',
            payout_account VARCHAR(120) DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY payout_task_unique (task_id),
            KEY provider_idx (provider_id)
        ) {$charset};",
    ];

    foreach ( $tables as $sql ) {
        $wpdb->query( $sql );
    }
}

add_action( 'init', 'bntm_mb_ensure_message_tables', 21 );

function mb_get_review_summary( $user_id ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS review_count
         FROM {$wpdb->prefix}mb_reviews
         WHERE reviewee_id=%d AND status='active'",
        $user_id
    ) );

    $avg = $row ? round( (float) $row->avg_rating, 1 ) : 0.0;
    $count = $row ? (int) $row->review_count : 0;

    return [
        'avg'   => $avg,
        'count' => $count,
        'label' => $count > 0 ? number_format( $avg, 1 ) . '/5' : 'No ratings yet',
    ];
}

function mb_create_notification( $user_id, $title, $body, $target_url = '', $type = 'system' ) {
    global $wpdb;
    $user_id = (int) $user_id;
    if ( $user_id <= 0 ) {
        return 0;
    }

    $wpdb->insert( $wpdb->prefix . 'mb_notifications', [
        'rand_id'      => bntm_rand_id(),
        'user_id'      => $user_id,
        'type'         => sanitize_text_field( $type ),
        'title'        => sanitize_text_field( $title ),
        'body'         => wp_kses_post( $body ),
        'target_url'   => esc_url_raw( $target_url ),
        'is_read'      => 0,
        'created_at'   => current_time( 'mysql' ),
        'updated_at'   => current_time( 'mysql' ),
    ], [ '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ] );

    return (int) $wpdb->insert_id;
}

function mb_get_notifications( $user_id, $limit = 6 ) {
    global $wpdb;
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_notifications WHERE user_id=%d ORDER BY created_at DESC LIMIT %d",
        $user_id,
        $limit
    ) );
}

function mb_render_notifications_list( $user_id ) {
    $items = mb_get_notifications( $user_id, 6 );
    if ( empty( $items ) ) {
        return '<div class="notif-empty"><p>No notifications yet.</p></div>';
    }

    $html = '';
    foreach ( $items as $item ) {
        $html .= '<a class="notif-item" href="' . esc_url( $item->target_url ?: '#' ) . '">';
        $html .= '<div class="notif-item-body">';
        $html .= '<div class="notif-item-text"><strong>' . esc_html( $item->title ) . '</strong><br>' . esc_html( wp_strip_all_tags( $item->body ) ) . '</div>';
        $html .= '<div class="notif-item-date">' . esc_html( human_time_diff( strtotime( $item->created_at ), current_time( 'timestamp' ) ) ) . ' ago</div>';
        $html .= '</div></a>';
    }

    return $html;
}

function mb_get_task_payout_summary( $task_id ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_payouts WHERE task_id=%d", $task_id ) );
}

function mb_has_task_review( $task_id, $reviewer_id ) {
    global $wpdb;
    return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_reviews WHERE task_id=%d AND reviewer_id=%d AND status='active'", $task_id, $reviewer_id ) ) > 0;
}

function mb_record_task_payout( $task ) {
    global $wpdb;
    if ( ! $task || empty( $task->id ) || empty( $task->provider_id ) ) {
        return 0;
    }

    $existing = mb_get_task_payout_summary( (int) $task->id );
    if ( $existing ) {
        return (int) $existing->id;
    }

    $fee_rate = 0.05;
    $gross = (float) $task->budget;
    $fee = round( $gross * $fee_rate, 2 );
    $net = round( $gross - $fee, 2 );
    $payout_account = (string) get_user_meta( (int) $task->provider_id, 'mb_gcash_number', true );
    $payout_name = (string) get_user_meta( (int) $task->provider_id, 'mb_gcash_name', true );
    $destination = trim( $payout_name . ' ' . $payout_account );

    $wpdb->insert( $wpdb->prefix . 'mb_payouts', [
        'rand_id'        => bntm_rand_id(),
        'task_id'        => (int) $task->id,
        'provider_id'    => (int) $task->provider_id,
        'gross_amount'   => $gross,
        'fee_amount'     => $fee,
        'net_amount'     => $net,
        'payout_method'  => $destination ? 'gcash' : 'manual',
        'payout_account' => $destination,
        'status'         => 'pending',
        'created_at'     => current_time( 'mysql' ),
        'updated_at'     => current_time( 'mysql' ),
    ], [ '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s' ] );

    return (int) $wpdb->insert_id;
}

function mb_render_review_modal_shell() {
    ob_start(); ?>
    <div class="mb-review-modal" id="mb-review-modal" style="display:none;position:fixed;inset:0;z-index:4000;background:rgba(0,0,0,.35);align-items:center;justify-content:center;padding:16px;">
        <div class="mb-pro-card mb-pro-card-pad" style="width:min(100%,460px);box-shadow:0 25px 50px rgba(0,0,0,.25);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px;">
                <div>
                    <h3 style="font-size:18px;font-weight:800;margin:0 0 4px;color:#1e1e1e;">Leave a Rating</h3>
                    <p style="margin:0;color:#5a7474;font-size:13px;">Rate the completed task and share short feedback.</p>
                </div>
                <button type="button" class="notif-dd-close" id="mb-review-close" style="border:none;background:none;">%s</button>
            </div>
            <form id="mb-review-form">
                <input type="hidden" name="task_id" id="mb-review-task-id" value="">
                <input type="hidden" name="rating" id="mb-review-rating" value="5">
                <div style="display:flex;gap:8px;margin-bottom:14px;" id="mb-review-stars">
                    <button type="button" data-rating="1" class="mb-review-star mb-pv-btn" style="flex:1;min-width:0;">1</button>
                    <button type="button" data-rating="2" class="mb-review-star mb-pv-btn" style="flex:1;min-width:0;">2</button>
                    <button type="button" data-rating="3" class="mb-review-star mb-pv-btn" style="flex:1;min-width:0;">3</button>
                    <button type="button" data-rating="4" class="mb-review-star mb-pv-btn" style="flex:1;min-width:0;">4</button>
                    <button type="button" data-rating="5" class="mb-review-star mb-pv-btn" style="flex:1;min-width:0;">5</button>
                </div>
                <div style="margin-bottom:14px;">
                    <textarea id="mb-review-text" rows="4" class="ic2" style="width:100%;resize:vertical;" placeholder="What went well?"></textarea>
                </div>
                <div id="mb-review-msg" style="margin-bottom:10px;"></div>
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="outline-btn2" id="mb-review-cancel">Cancel</button>
                    <button type="submit" class="save-btn">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    (function(){
        var modal = document.getElementById('mb-review-modal');
        if (!modal) return;
        var form = document.getElementById('mb-review-form');
        var ratingInput = document.getElementById('mb-review-rating');
        var taskInput = document.getElementById('mb-review-task-id');
        var reviewText = document.getElementById('mb-review-text');
        var msg = document.getElementById('mb-review-msg');
        function setRating(value) {
            ratingInput.value = value;
            modal.querySelectorAll('.mb-review-star').forEach(function(btn) {
                var active = Number(btn.dataset.rating) <= value;
                btn.style.opacity = active ? '1' : '.72';
                btn.style.filter = active ? 'none' : 'saturate(.85)';
            });
        }
        window.mbOpenReviewModal = function(taskId, currentRating) {
            taskInput.value = taskId || '';
            setRating(currentRating || 5);
            reviewText.value = '';
            msg.innerHTML = '';
            modal.style.display = 'flex';
        };
        window.mbCloseReviewModal = function() {
            modal.style.display = 'none';
        };
        modal.querySelectorAll('.mb-review-star').forEach(function(btn) {
            btn.addEventListener('click', function() {
                setRating(Number(this.dataset.rating) || 5);
            });
        });
        document.getElementById('mb-review-close').addEventListener('click', window.mbCloseReviewModal);
        document.getElementById('mb-review-cancel').addEventListener('click', window.mbCloseReviewModal);
        document.addEventListener('click', function(ev) {
            var btn = ev.target.closest('.mb-review-btn');
            if (!btn) return;
            ev.preventDefault();
            window.mbOpenReviewModal(btn.dataset.task || '', 5);
        });
        form.addEventListener('submit', function(ev) {
            ev.preventDefault();
            var fd = new FormData();
            fd.append('action', 'mb_submit_review');
            fd.append('nonce', mbNonce);
            fd.append('task_id', taskInput.value);
            fd.append('rating', ratingInput.value);
            fd.append('review_text', reviewText.value || '');
            fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(j) {
                if (typeof bntmMbToast === 'function') {
                    bntmMbToast('mb-review-msg', j.data && j.data.message ? j.data.message : 'Saved.', !!j.success);
                } else if (msg) {
                    msg.innerHTML = '<div class="bntm-notice">' + (j.data && j.data.message ? j.data.message : 'Saved.') + '</div>';
                }
                if (j.success) {
                    setTimeout(function() { window.location.reload(); }, 700);
                }
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function bntm_mb_ensure_pages() {
    if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    $is_admin_request = is_admin() && ! ( doing_action( 'admin_post' ) || doing_action( 'admin_post_nopriv' ) );
    if ( $is_admin_request ) {
        return;
    }

    static $done = false;
    if ( $done ) {
        return;
    }
    $done = true;

    $page_slugs = [
        'Landing Page'                        => 'mentorbe-home',
        'Help Center/FAQ'                     => 'help-center-faq',
        'Terms of Service and Privacy Policy' => 'terms-of-service-and-privacy-policy',
        'Login'                               => 'mb-login',
        'Forgot Password'                     => 'mb-forgot-password',
        'Reset Password'                      => 'mb-reset-password',
        'Sign Up'                             => 'sign-up',
        'Choose Role'                         => 'choose-role',
        'Profile Setup'                       => 'profile-setup',
        "Client's Dashboard"                 => 'clients-dashboard',
        'Checkout/Billing'                    => 'checkout-billing',
        'Inbox'                               => 'inbox',
        'Public Profile View'                 => 'public-profile-view',
        'Home/Task Feed'                      => 'home-task-feed',
        'Earnings'                            => 'earnings',
        'Dashboard/System Overview'           => 'dashboard-system-overview',
    ];
    $core_page_titles = array_keys( bntm_mb_get_pages() );

    foreach ( bntm_mb_get_pages() as $page_title => $shortcode ) {
        $existing_page = get_page_by_title( $page_title, OBJECT, 'page' );
        if ( $existing_page ) {
            $update_args = [ 'ID' => $existing_page->ID ];
            $needs_update = false;

            if ( in_array( $page_title, $core_page_titles, true ) && strpos( (string) $existing_page->post_content, $shortcode ) === false ) {
                $update_args['post_content'] = $shortcode;
                $needs_update = true;
            }

            if ( isset( $page_slugs[ $page_title ] ) && $existing_page->post_name !== $page_slugs[ $page_title ] ) {
                $update_args['post_name'] = $page_slugs[ $page_title ];
                $needs_update = true;
            }

            if ( $needs_update ) {
                wp_update_post( $update_args );
            }

            continue;
        }

        $insert_args = [
            'post_title'   => wp_strip_all_tags( $page_title ),
            'post_content' => $shortcode,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ];

        if ( isset( $page_slugs[ $page_title ] ) ) {
            $insert_args['post_name'] = $page_slugs[ $page_title ];
        }

        wp_insert_post( $insert_args );
    }
}

add_action( 'init', 'bntm_mb_ensure_pages', 20 );

function bntm_mb_dashboard_url_for_role( $role ) {
    $slug = $role === 'provider' ? 'home-task-feed' : 'clients-dashboard';
    $page = get_page_by_path( $slug );
    return $page ? get_permalink( $page->ID ) : home_url( '/' );
}

function bntm_mb_wp_login_url( $redirect_to = '' ) {
    $redirect_to = $redirect_to ? $redirect_to : home_url( '/' );
    return home_url( '/mb-login/' ) . ( $redirect_to ? '?redirect_to=' . rawurlencode( $redirect_to ) : '' );
}

function bntm_mb_forgot_password_url() {
    return home_url( '/mb-forgot-password/' );
}

function bntm_mb_reset_password_url( $login = '', $key = '' ) {
    $url = home_url( '/mb-reset-password/' );
    if ( $login ) {
        $url = add_query_arg( 'login', rawurlencode( $login ), $url );
    }
    if ( $key ) {
        $url = add_query_arg( 'key', rawurlencode( $key ), $url );
    }
    return $url;
}

function bntm_mb_pending_signup_cookie_name() {
    return 'bntm_mb_pending_signup';
}

function bntm_mb_pending_signup_key( $token ) {
    $token = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $token );
    return $token ? 'mb_pending_signup_' . $token : '';
}

function bntm_mb_get_pending_signup_token() {
    $cookie = bntm_mb_pending_signup_cookie_name();
    if ( empty( $_COOKIE[ $cookie ] ) ) {
        return '';
    }
    return sanitize_text_field( wp_unslash( $_COOKIE[ $cookie ] ) );
}

function bntm_mb_get_pending_signup( $token = '' ) {
    $token = $token ? $token : bntm_mb_get_pending_signup_token();
    if ( ! $token ) {
        return [];
    }
    $data = get_transient( bntm_mb_pending_signup_key( $token ) );
    return is_array( $data ) ? $data : [];
}

function bntm_mb_set_pending_signup( array $data ) {
    $token = wp_generate_password( 24, false, false );
    set_transient( bntm_mb_pending_signup_key( $token ), $data, DAY_IN_SECONDS );

    $cookie = bntm_mb_pending_signup_cookie_name();
    $expire = time() + DAY_IN_SECONDS;
    $path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
    $domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
    setcookie( $cookie, $token, $expire, $path, $domain, is_ssl(), true );
    $_COOKIE[ $cookie ] = $token;

    return $token;
}

function bntm_mb_clear_pending_signup( $token = '' ) {
    $token = $token ? $token : bntm_mb_get_pending_signup_token();
    if ( $token ) {
        delete_transient( bntm_mb_pending_signup_key( $token ) );
    }

    $cookie = bntm_mb_pending_signup_cookie_name();
    $path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
    $domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
    setcookie( $cookie, '', time() - HOUR_IN_SECONDS, $path, $domain, is_ssl(), true );
    unset( $_COOKIE[ $cookie ] );
}

function bntm_mb_onboarding_urls() {
    return [
        'role'    => mb_find_page_url( '[mb_role_choice]' ),
        'profile' => mb_find_page_url( '[mb_profile_setup]' ),
    ];
}

function bntm_mb_onboarding_redirect_url( $user_id = 0, $profile = null ) {
    $user_id = $user_id ? (int) $user_id : get_current_user_id();
    if ( ! $user_id ) {
        return '';
    }

    $step    = (string) get_user_meta( $user_id, 'mb_onboarding_step', true );
    $profile  = $profile ?: mb_get_profile( $user_id );
    $urls     = bntm_mb_onboarding_urls();
    $role_url = ! empty( $urls['role'] ) ? $urls['role'] : home_url( '/' );
    $profile_url = ! empty( $urls['profile'] ) ? $urls['profile'] : $role_url;

    if ( $step === 'profile' ) {
        return $profile_url;
    }

    if ( $step === 'role' ) {
        return $role_url;
    }

    if ( ! $profile || empty( $profile->user_role ) || $profile->user_role === 'pending' ) {
        return $role_url;
    }

    return '';
}

/* ==========================================================================
   C. AJAX ACTION HOOKS
========================================================================== */

add_action( 'wp_ajax_mb_complete_onboarding',       'bntm_ajax_mb_complete_onboarding' );
add_action( 'wp_ajax_mb_search_skills',             'bntm_ajax_mb_search_skills' );
add_action( 'wp_ajax_mb_create_task',               'bntm_ajax_mb_create_task' );
add_action( 'wp_ajax_mb_cancel_task',               'bntm_ajax_mb_cancel_task' );
add_action( 'wp_ajax_mb_get_applicants',            'bntm_ajax_mb_get_applicants' );
add_action( 'wp_ajax_mb_accept_applicant',          'bntm_ajax_mb_accept_applicant' );
add_action( 'wp_ajax_mb_reject_applicant',          'bntm_ajax_mb_reject_applicant' );
add_action( 'wp_ajax_mb_complete_task',             'bntm_ajax_mb_complete_task' );
add_action( 'wp_ajax_mb_submit_review',              'bntm_ajax_mb_submit_review' );
add_action( 'wp_ajax_mb_admin_update_verification',  'bntm_ajax_mb_admin_update_verification' );
add_action( 'wp_ajax_mb_filter_feed',               'bntm_ajax_mb_filter_feed' );
add_action( 'wp_ajax_mb_apply_to_task',             'bntm_ajax_mb_apply_to_task' );
add_action( 'wp_ajax_mb_withdraw_application',      'bntm_ajax_mb_withdraw_application' );
add_action( 'wp_ajax_mb_mark_ready_for_review',     'bntm_ajax_mb_mark_ready_for_review' );
add_action( 'wp_ajax_mb_add_skill',                 'bntm_ajax_mb_add_skill' );
add_action( 'wp_ajax_mb_update_profile',            'bntm_ajax_mb_update_profile' );
add_action( 'wp_ajax_mb_upload_avatar',             'bntm_ajax_mb_upload_avatar' );
add_action( 'wp_ajax_mb_upload_cover',              'bntm_ajax_mb_upload_cover' );
add_action( 'wp_ajax_mb_update_account_info',       'bntm_ajax_mb_update_account_info' );
add_action( 'wp_ajax_mb_change_password',           'bntm_ajax_mb_change_password' );
add_action( 'wp_ajax_mb_update_notification_prefs', 'bntm_ajax_mb_update_notification_prefs' );
add_action( 'wp_ajax_mb_save_billing_details',      'bntm_ajax_mb_save_billing_details' );
add_action( 'wp_ajax_mb_unlink_billing_account',    'bntm_ajax_mb_unlink_billing_account' );
add_action( 'wp_ajax_mb_submit_verification',       'bntm_ajax_mb_submit_verification' );
add_action( 'wp_ajax_mb_browse_filter',             'bntm_ajax_mb_browse_filter' );
add_action( 'wp_ajax_mb_search_mentors',            'bntm_ajax_mb_search_mentors' );
add_action( 'wp_ajax_nopriv_mb_browse_filter',      'bntm_ajax_mb_browse_filter' );
add_action( 'admin_post_nopriv_mb_register',        'bntm_handle_mb_register' );
add_action( 'admin_post_mb_register',               'bntm_handle_mb_register' );
add_action( 'admin_post_mb_select_role',            'bntm_handle_mb_select_role' );
add_action( 'admin_post_mb_profile_setup',          'bntm_handle_mb_profile_setup' );
add_action( 'admin_post_mb_profile_setup',          'bntm_handle_mb_profile_setup' );
add_action( 'admin_post_nopriv_mb_forgot_password', 'bntm_handle_mb_forgot_password' );
add_action( 'admin_post_mb_forgot_password',        'bntm_handle_mb_forgot_password' );
add_action( 'admin_post_nopriv_mb_reset_password',  'bntm_handle_mb_reset_password' );
add_action( 'admin_post_mb_reset_password',         'bntm_handle_mb_reset_password' );
add_action( 'wp_ajax_mb_get_conversations',         'bntm_ajax_mb_get_conversations' );
add_action( 'wp_ajax_mb_get_messages',              'bntm_ajax_mb_get_messages' );
add_action( 'wp_ajax_mb_send_message',              'bntm_ajax_mb_send_message' );
add_action( 'wp_ajax_mb_start_conversation',        'bntm_ajax_mb_start_conversation' );
add_action( 'admin_post_nopriv_mb_login',           'bntm_handle_mb_login' );
add_action( 'admin_post_mb_login',                  'bntm_handle_mb_login' );


/* ==========================================================================
   D. SHARED DASHBOARD ASSETS HELPER
========================================================================== */

function mb_dashboard_assets() {
    $nonce = wp_create_nonce( 'mb_nonce' );
    ?>
    <script>
    var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
    var mbNonce = '<?php echo esc_js( $nonce ); ?>';
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet">
    <style>
    /* ---- MentorBe Brand Tokens ---- */
    :root {
        --mb-mint:      #f1fffe;
        --mb-mint-mid:  #97d6cf;
        --mb-mint-pill: #cfeeeb;
        --mb-teal:      #16796f;
        --mb-ink:       #1e1e1e;
        --mb-muted:     #5a7474;
        --mb-muted2:    #617876;
        --mb-body:      #314343;
        --mb-gold:      #e5cc4d;
    }
    body, .bntm-mb-wrap, .bntm-mb-wrap * {
        font-family: 'Montserrat', sans-serif;
    }

    /* ---- Dashboard wrapper ---- */
    .bntm-mb-wrap { max-width:1100px; background:var(--mb-mint); }

    /* ---- Tabs ---- */
    .bntm-tabs { display:flex; gap:0; border-bottom:2px solid var(--mb-mint-mid); margin-bottom:24px; flex-wrap:wrap; }
    .bntm-tab { padding:12px 20px; font-size:14px; font-weight:600; color:var(--mb-muted); text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .15s; }
    .bntm-tab:hover { color:var(--mb-teal); }
    .bntm-tab.active { color:var(--mb-teal); border-bottom-color:var(--mb-teal); }

    /* ---- Stat cards ---- */
    .bntm-stats-row { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
    .bntm-stat-card { display:flex; align-items:center; gap:14px; background:#fff; border-radius:12px; padding:18px 20px; flex:1; min-width:180px; box-shadow:4px 4px 7.2px rgba(0,0,0,.08); }
    .stat-icon { width:48px; height:48px; border-radius:12px; background:var(--mb-teal); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .stat-content h3 { margin:0; font-size:11px; color:var(--mb-muted); font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
    .stat-number { margin:3px 0 0; font-size:24px; font-weight:800; color:var(--mb-ink); }

    /* ---- Task cards ---- */
    .bntm-task-card { background:#fff; border-radius:12px; padding:18px 20px; margin-bottom:12px; box-shadow:4px 4px 7.2px rgba(0,0,0,.08); transition:box-shadow .15s; }
    .bntm-task-card:hover { box-shadow:0 10px 15px -3px rgba(0,0,0,.1); }
    .bntm-task-card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
    .bntm-task-card h4 { margin:0 0 4px; font-size:16px; font-weight:700; color:var(--mb-ink); }
    .bntm-task-meta { font-size:12px; color:var(--mb-muted); display:flex; gap:14px; flex-wrap:wrap; margin-top:6px; }

    /* ---- Skill pills / badges ---- */
    .bntm-skill-pill { display:inline-block; background:rgba(151,214,207,.35); color:var(--mb-muted); font-size:11px; font-weight:600; padding:3px 10px; border-radius:999px; margin:2px 4px 2px 0; }
    .bntm-status-badge { font-size:11px; font-weight:700; padding:4px 12px; border-radius:999px; text-transform:capitalize; }
    .bntm-status-draft    { background:#f3f4f6; color:#6b7280; }
    .bntm-status-live     { background:rgba(207,238,235,.6); color:var(--mb-teal); }
    .bntm-status-pending  { background:#fef3c7; color:#b45309; }
    .bntm-status-in_progress { background:#fef3c7; color:#b45309; }
    .bntm-status-completed { background:rgba(207,238,235,.9); color:var(--mb-teal); }
    .bntm-status-cancelled,.bntm-status-rejected,.bntm-status-withdrawn { background:#fee2e2; color:#b91c1c; }
    .bntm-status-accepted  { background:rgba(207,238,235,.9); color:var(--mb-teal); }

    /* ---- Skill tag input ---- */
    .bntm-skill-tag-input { display:flex; flex-wrap:wrap; gap:6px; border:1px solid var(--mb-teal); border-radius:12px; padding:8px; min-height:44px; }
    .bntm-skill-tag-input input { border:none; outline:none; flex:1; min-width:120px; font-size:14px; background:transparent; color:var(--mb-teal); }
    .bntm-skill-tag-input input::placeholder { color:var(--mb-teal); opacity:.6; }
    .bntm-skill-chip { background:var(--mb-teal); color:#fff; font-size:12px; padding:4px 8px 4px 10px; border-radius:999px; display:flex; align-items:center; gap:6px; }
    .bntm-skill-chip button { background:none; border:none; color:#fff; cursor:pointer; font-size:13px; line-height:1; opacity:.8; padding:0; }
    .bntm-skill-suggestion-list { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid var(--mb-mint-mid); border-radius:12px; box-shadow:0 6px 16px rgba(0,0,0,.1); z-index:20; max-height:200px; overflow-y:auto; }
    .bntm-skill-suggestion-item { padding:10px 14px; cursor:pointer; font-size:13px; color:var(--mb-body); }
    .bntm-skill-suggestion-item:hover { background:var(--mb-mint); color:var(--mb-teal); }

    /* ---- Onboarding / Role picker ---- */
    .bntm-onboarding-wrap { max-width:430px; margin:14px auto; text-align:center; }
    .bntm-onboarding-wrap .bntm-brand-mark { display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px; }
    .bntm-onboarding-wrap .bntm-brand-mark img { height:36px; width:auto; object-fit:contain; display:block; }
    .bntm-onboarding-wrap h2 { font-size:20px; font-weight:800; color:var(--mb-ink); margin-bottom:4px; }
    .bntm-onboarding-wrap > p { color:var(--mb-muted); font-weight:500; margin:0 0 10px; font-size:13px; }
    .bntm-role-cards { display:flex; gap:10px; margin-top:14px; }
    .bntm-role-card { flex:1; border:2px solid var(--mb-mint-mid); border-radius:12px; padding:16px 12px; cursor:pointer; transition:all .15s; background:#fff; }
    .bntm-role-card:hover, .bntm-role-card.selected { border-color:var(--mb-teal); box-shadow:0 2px 10px rgba(22,121,111,.10); }
    .bntm-role-card svg { color:var(--mb-teal); width:22px; height:22px; }
    .bntm-role-card h4 { margin:8px 0 3px; font-size:14px; font-weight:700; color:var(--mb-ink); }
    .bntm-role-card p { font-size:11px; color:var(--mb-muted); margin:0; font-weight:500; }

    /* ---- Buttons (override BNTM defaults with MentorBe brand) ---- */
    .bntm-btn-primary, button.bntm-btn-primary, a.bntm-btn-primary {
        background:var(--mb-teal) !important; color:#fff !important;
        border-radius:9999px !important; font-weight:600 !important;
        border:none !important; padding:12px 32px !important; cursor:pointer;
        transition:opacity .15s, transform .1s; box-shadow:0 4px 12px rgba(22,121,111,.3) !important;
        text-decoration:none; display:inline-block;
    }
    .bntm-btn-primary:hover { opacity:.9 !important; }
    .bntm-btn-secondary, button.bntm-btn-secondary, a.bntm-btn-secondary {
        background:transparent !important; color:var(--mb-teal) !important;
        border-radius:9999px !important; font-weight:600 !important;
        border:2px solid var(--mb-teal) !important; padding:10px 28px !important;
        cursor:pointer; transition:all .15s; text-decoration:none; display:inline-block;
    }
    .bntm-btn-secondary:hover { background:var(--mb-teal) !important; color:#fff !important; }
    .bntm-btn-danger, button.bntm-btn-danger {
        background:#b91c1c !important; color:#fff !important;
        border-radius:9999px !important; border:none !important;
        padding:8px 18px !important; font-weight:600 !important; cursor:pointer;
    }
    .bntm-btn-small { font-size:13px !important; padding:6px 16px !important; }

    /* ---- Forms ---- */
    .bntm-form-group { margin-bottom:18px; }
    .bntm-form-group label { display:block; font-size:12px; font-weight:700; color:var(--mb-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:.04em; }
    .bntm-form input[type="text"],.bntm-form input[type="email"],.bntm-form input[type="number"],.bntm-form input[type="password"],
    .bntm-form textarea, .bntm-form select {
        width:100%; border:1px solid var(--mb-teal); border-radius:12px; padding:12px 16px;
        color:var(--mb-teal); font-family:'Montserrat',sans-serif; font-size:15px; font-weight:500;
        background:transparent; outline:none; box-sizing:border-box;
    }
    .bntm-form input::placeholder,.bntm-form textarea::placeholder { color:var(--mb-teal); opacity:.7; }
    .bntm-form input:focus,.bntm-form textarea:focus,.bntm-form select:focus { box-shadow:0 0 0 2px rgba(22,121,111,.3); }
    .bntm-form-section { background:#fff; border-radius:16px; padding:24px; margin-bottom:20px; box-shadow:4px 4px 7.2px rgba(0,0,0,.06); }
    .bntm-form-section h3 { margin:0 0 18px; font-size:18px; font-weight:800; color:var(--mb-ink); }

    /* ---- Applicant rows ---- */
    .bntm-applicant-row { display:flex; justify-content:space-between; align-items:center; padding:14px 0; border-bottom:1px solid var(--mb-mint-pill); }
    .bntm-applicant-row:last-child { border-bottom:none; }
    .bntm-avatar-lg { width:64px; height:64px; border-radius:50%; object-fit:cover; background:var(--mb-mint-mid); }

    /* ---- Modal ---- */
    .bntm-modal { position:fixed; inset:0; background:rgba(30,30,30,.6); display:flex; align-items:center; justify-content:center; z-index:1000; }
    .bntm-modal-content { background:#fff; border-radius:24px; padding:32px; width:100%; max-width:480px; box-shadow:-5px 4px 13.7px rgba(0,0,0,.2); }
    .bntm-modal-content h3 { margin:0 0 20px; font-size:22px; font-weight:800; color:var(--mb-teal); text-align:center; }

    /* ---- Coming soon ---- */
    .mb-coming-soon-wrap { max-width:520px; margin:48px auto; text-align:center; padding:56px 32px; background:#fff; border-radius:24px; box-shadow:4px 4px 14px rgba(0,0,0,.1); }
    .mb-coming-soon-icon { width:88px; height:88px; background:var(--mb-mint); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; color:var(--mb-teal); }
    .mb-coming-soon-wrap h2 { margin:0 0 12px; font-size:24px; font-weight:800; color:var(--mb-ink); }
    .mb-coming-soon-wrap p { color:var(--mb-muted); font-size:15px; font-weight:500; line-height:1.7; margin-bottom:24px; }

    /* ---- Tables ---- */
    .bntm-table-wrapper { overflow-x:auto; }
    .bntm-table { width:100%; border-collapse:collapse; font-size:14px; }
    .bntm-table th { background:var(--mb-teal); color:#fff; padding:12px 14px; text-align:left; font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
    .bntm-table td { padding:12px 14px; border-bottom:1px solid var(--mb-mint-pill); color:var(--mb-body); }
    .bntm-table tr:last-child td { border-bottom:none; }
    .bntm-table tr:hover td { background:var(--mb-mint); }

    /* ---- Notices ---- */
    .bntm-notice { padding:12px 16px; border-radius:10px; font-size:14px; font-weight:500; margin-bottom:16px; background:var(--mb-mint-pill); color:var(--mb-teal); border-left:4px solid var(--mb-teal); }
    .bntm-notice-error { background:#fee2e2; color:#b91c1c; border-left-color:#b91c1c; }
    .bntm-notice-success { background:rgba(207,238,235,.8); color:var(--mb-teal); border-left-color:var(--mb-teal); }
    </style>
    <script>
    function bntmMbToast(id, msg, ok) {
        var el = document.getElementById(id);
        if (el) el.innerHTML = '<div class="bntm-notice bntm-notice-' + (ok ? 'success' : 'error') + '">' + msg + '</div>';
    }

    function bntmMbSkillTagInput(wrapperId, hiddenId, initial) {
        var wrapper  = document.getElementById(wrapperId);
        if (!wrapper) return;
        var hidden   = document.getElementById(hiddenId);
        var selected = initial || [];

        function sync() { hidden.value = JSON.stringify(selected); }

        function render() {
            wrapper.querySelectorAll('.bntm-skill-chip').forEach(function(c) { c.remove(); });
            var inp = wrapper.querySelector('input');
            selected.forEach(function(s) {
                var chip = document.createElement('span');
                chip.className = 'bntm-skill-chip';
                chip.innerHTML = s.name + ' <button type="button" data-id="' + s.id + '">&times;</button>';
                wrapper.insertBefore(chip, inp);
            });
            wrapper.querySelectorAll('.bntm-skill-chip button').forEach(function(b) {
                b.addEventListener('click', function() {
                    var id = this.dataset.id;
                    selected = selected.filter(function(s) { return String(s.id) !== String(id); });
                    sync(); render();
                });
            });
        }

        var inp  = wrapper.querySelector('input');
        var list = document.createElement('div');
        list.className = 'bntm-skill-suggestion-list';
        list.style.display = 'none';
        wrapper.parentElement.style.position = 'relative';
        wrapper.parentElement.appendChild(list);

        var timer;
        inp.addEventListener('input', function() {
            clearTimeout(timer);
            var q = this.value.trim();
            if (!q) { list.style.display = 'none'; return; }
            timer = setTimeout(function() {
                var fd = new FormData();
                fd.append('action', 'mb_search_skills');
                fd.append('query', q);
                fd.append('nonce', mbNonce);
                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    if (!json.success) return;
                    list.innerHTML = '';
                    json.data.skills.forEach(function(s) {
                        if (selected.some(function(sel) { return String(sel.id) === String(s.id); })) return;
                        var item = document.createElement('div');
                        item.className = 'bntm-skill-suggestion-item';
                        item.textContent = s.name + ' (' + s.category + ')';
                        item.addEventListener('click', function() {
                            selected.push({ id: s.id, name: s.name });
                            sync(); render();
                            inp.value = '';
                            list.style.display = 'none';
                        });
                        list.appendChild(item);
                    });
                    if (json.data.can_create) {
                        var ci = document.createElement('div');
                        ci.className = 'bntm-skill-suggestion-item';
                        ci.style.fontStyle = 'italic';
                        ci.textContent = 'Add new tag "' + q + '"';
                        ci.addEventListener('click', function() {
                            selected.push({ id: 'new:' + q, name: q });
                            sync(); render();
                            inp.value = '';
                            list.style.display = 'none';
                        });
                        list.appendChild(ci);
                    }
                    list.style.display = list.children.length ? 'block' : 'none';
                });
            }, 250);
        });

        document.addEventListener('click', function(e) {
            if (!wrapper.parentElement.contains(e.target)) list.style.display = 'none';
        });

        sync(); render();
    }
    </script>
    <?php
}

/* ==========================================================================
   E-1. CLIENT DASHBOARD v2 — DESIGN SYSTEM (scoped to .mb-cv2, client-only)
========================================================================== */

function mb_cv2_icon( $name, $size = 20, $cls = '' ) {
    $paths = [
        'home'        => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'bell'        => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'msg'         => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'clipboard'   => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',
        'briefcase'   => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M3 12h18"/>',
        'inbox'       => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5 4h14a2 2 0 0 1 2 2v6l-2 3h-4l-2 3H9l-2-3H3l-2-3V6a2 2 0 0 1 2-2z"/>',
        'dollar-sign' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'user'        => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'plus'        => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'search'      => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'star'        => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'chevdown'    => '<polyline points="6 9 12 15 18 9"/>',
        'chevright'   => '<polyline points="9 18 15 12 9 6"/>',
        'chevleft'    => '<polyline points="15 18 9 12 15 6"/>',
        'check'       => '<polyline points="20 6 9 17 4 12"/>',
        'x'           => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'camera'      => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'checkcircle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'upload'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        'mappin'      => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
    ];
    $d = isset( $paths[ $name ] ) ? $paths[ $name ] : '';
    return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . esc_attr( $cls ) . '">' . $d . '</svg>';
}

function mb_client_v2_assets() {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Inter:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
.mb-cv2 {
  --teal:#16796f; --teal-dark:#0f504a; --mint:#f1fffe; --mint-mid:#8aaba7; --mint-mid2:#bcd3d0;
  --mint-pill:#cfeeeb; --mint-tint:#97d6cf; --ink:#1e1e1e; --muted:#617876; --muted2:#899998;
  --border-teal:#acc8c5; --danger:#a62023; --gold:#EAC820; --skill-bg:#208479; --skill-border:#24b7a8;} 
  .mb-cv2 * {box-sizing:border-box;}.mb-cv2 {margin:0;padding:0;}.mb-cv2 {font-family:'Montserrat',sans-serif;color:var(--ink);background:var(--mint);}.mb-cv2 button {font-family:inherit;cursor:pointer;border:none;background:none;color:inherit;}.mb-cv2 input, .mb-cv2 textarea, .mb-cv2 select {font-family:inherit;}.mb-cv2 img {max-width:100%;display:block;}.mb-cv2 h1, .mb-cv2 h2, .mb-cv2 h3, .mb-cv2 p, .mb-cv2 ul {margin:0;}@keyframes mbcv2spin {to {transform:rotate(360deg);}}.mb-cv2 .toast-wrap {position:fixed;top:20px;right:20px;z-index:2000;display:flex;flex-direction:column;gap:10px;}.mb-cv2 .toast {background:#fff;border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,.15);padding:14px 18px;font-size:13px;font-weight:600;color:var(--ink);border-left:4px solid var(--teal);min-width:240px;animation:mbcv2toastin .2s ease-out;}.mb-cv2 .toast-error {border-left-color:#dc2626;}.mb-cv2 .toast-warning {border-left-color:#d97706;}.mb-cv2 .toast-info {border-left-color:#0369a1;}@keyframes mbcv2toastin {from {opacity:0;transform:translateX(20px);}to {opacity:1;transform:translateX(0);}}.mb-cv2 .topnav {background:var(--mint);box-shadow:0px 4px 16.3px 0px rgba(22,121,111,0.17);position:sticky;top:0;z-index:40;}.mb-cv2 .topnav-inner {max-width:1440px;margin:0 auto;padding:0 32px;height:72px;display:flex;align-items:center;justify-content:space-between;}.mb-cv2 .nav-logo img {height:36px;width:auto;object-fit:contain;}.mb-cv2 .nav-right {display:flex;align-items:center;gap:20px;}.mb-cv2 .post-task-btn {display:flex;align-items:center;gap:6px;border:2px solid #000;border-radius:16px;padding:8px 16px;font-size:15px;font-weight:600;color:#000;transition:all .15s;text-decoration:none;}.mb-cv2 .post-task-btn:hover, .mb-cv2 .post-task-btn.active {border-color:var(--teal);color:var(--teal);text-decoration:none;}.mb-cv2 .nav-icons-wrap {display:flex;align-items:center;gap:20px;position:relative;}.mb-cv2 .nav-icon-btn {position:relative;color:var(--ink);transition:color .15s;}.mb-cv2 .nav-icon-btn:hover, .mb-cv2 .nav-icon-btn.active {color:var(--teal);}.mb-cv2 .nav-dot {position:absolute;top:-2px;right:-2px;width:10px;height:10px;background:#ef4444;border-radius:9999px;}.mb-cv2 .nav-profile-wrap {position:relative;}.mb-cv2 .nav-avatar-btn {width:40px;height:40px;border-radius:9999px;border:3px solid var(--teal);background:#e8f5f3;display:flex;align-items:center;justify-content:center;color:var(--teal);transition:background .15s;}.mb-cv2 .nav-avatar-btn.open {background:var(--mint-pill);}.mb-cv2 .notif-dd {position:absolute;right:0;top:56px;width:320px;max-width:90vw;background:var(--mint);border:1px solid #000;border-radius:12px;box-shadow:0 20px 50px rgba(17, 126, 117, 0.25);overflow:hidden;z-index:60;}.mb-cv2 .notif-dd-header {display:flex;align-items:center;gap:8px;padding:16px 18px 8px;}.mb-cv2 .notif-dd-header span {font-weight:600;color:var(--muted);font-size:16px;letter-spacing:-.2px;}.mb-cv2 .notif-count {font-size:11px;font-weight:700;background:#ef4444;color:#fff;border-radius:9999px;padding:2px 8px;}.mb-cv2 .notif-dd-close {margin-left:auto;color:var(--muted);}.mb-cv2 .notif-dd-close:hover {color:var(--ink);}.mb-cv2 .notif-dd-list {box-shadow:inset 0 0 4px 0 var(--mint-mid);max-height:420px;overflow-y:auto;}.mb-cv2 .notif-empty {padding:60px 20px;text-align:center;color:var(--muted);font-weight:500;font-size:15px;}.mb-cv2 .notif-empty svg {margin:0 auto 12px;opacity:.3;display:block;}.mb-cv2 .notif-item {width:100%;display:flex;align-items:flex-start;border:1px solid var(--border-teal);text-align:left;background:#fff;transition:opacity .15s;}.mb-cv2 .notif-item:hover {opacity:.8;}.mb-cv2 .notif-item.unread {background:rgba(179,217,213,.28);}.mb-cv2 .notif-ic-wrap {flex-shrink:0;width:60px;height:70px;display:flex;align-items:center;justify-content:center;}.mb-cv2 .notif-ic-circle {width:46px;height:46px;border-radius:9999px;background:var(--muted);display:flex;align-items:center;justify-content:center;position:relative;color:#fff;}.mb-cv2 .notif-ic-dot {position:absolute;top:4px;right:4px;width:12px;height:12px;background:#ef4444;border-radius:9999px;border:2px solid #fff;}.mb-cv2 .notif-item-body {flex:1;min-width:0;padding:16px 16px 16px 0;}.mb-cv2 .notif-item-text {font-size:13px;line-height:1.3;font-weight:500;color:#444;}.mb-cv2 .notif-item.unread .notif-item-text {font-weight:600;color:var(--ink);}.mb-cv2 .notif-item-date {color:#899998;font-size:12px;margin-top:8px;}.mb-cv2 .notif-dd-footer {padding:12px 24px;border-top:1px solid var(--border-teal);background:#fff;}.mb-cv2 .notif-dd-footer button {color:var(--teal);font-weight:600;font-size:13px;}.mb-cv2 .notif-dd-footer button:hover {text-decoration:underline;}.mb-cv2 .profile-dd {position:absolute;right:0;top:56px;width:320px;max-width:90vw;background:var(--mint);border:1px solid var(--muted);border-radius:18px;box-shadow:0 20px 50px rgba(0,0,0,.25);overflow:hidden;z-index:60;}.mb-cv2 .profile-dd-header {display:flex;align-items:center;gap:10px;padding:18px 20px;}.mb-cv2 .profile-dd-avatar {width:46px;height:46px;border-radius:9999px;border:3px solid var(--teal);background:#e8f5f3;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--teal);}.mb-cv2 .profile-dd-name {font-weight:600;color:var(--ink);font-size:15px;letter-spacing:-.2px;}.mb-cv2 .profile-dd-email {font-weight:500;color:#899998;font-size:12px;}.mb-cv2 .profile-dd-divider {height:1px;background:var(--mint-mid);}.mb-cv2 .profile-dd-section {padding:10px 20px;}.mb-cv2 .profile-dd-cta {width:100%;height:42px;background:var(--teal-dark);border:1px solid var(--muted);border-radius:10px;color:#fff;font-weight:600;font-size:14px;transition:opacity .15s;}.mb-cv2 .profile-dd-label {font-weight:700;color:var(--ink);font-size:13px;margin-bottom:4px;}.mb-cv2 .profile-dd-link {display:block;width:100%;text-align:left;color:var(--teal-dark);font-weight:500;font-size:14px;padding:6px 0;transition:color .15s;}.mb-cv2 .profile-dd-link:hover {color:var(--teal);}.mb-cv2 .profile-dd-logout-row {padding:12px 20px;display:flex;justify-content:flex-end;}.mb-cv2 .profile-dd-logout-row button {font-weight:700;color:var(--danger);font-size:14px;display:flex;align-items:center;gap:8px;}.mb-cv2 .profile-dd-logout-row button:hover {opacity:.7;}.mb-cv2 .mentor-grid {display:grid;grid-template-columns:repeat(2,1fr);gap:24px;}@media(min-width:700px) {.mb-cv2 .mentor-grid {grid-template-columns:repeat(4,1fr);}}.mb-cv2 .mentor-grid.wide {grid-template-columns:repeat(2,1fr);}@media(min-width:700px) {.mb-cv2 .mentor-grid.wide {grid-template-columns:repeat(3,1fr);}}@media(min-width:1000px) {.mb-cv2 .mentor-grid.wide {grid-template-columns:repeat(4,1fr);}}@media(min-width:1300px) {.mb-cv2 .mentor-grid.wide {grid-template-columns:repeat(6,1fr);}}.mb-cv2 .mentor-card {width:100%;}.mb-cv2 .mentor-img-btn {width:100%;position:relative;height:183px;border-radius:19px;overflow:hidden;border:1px solid #000;display:block;cursor:pointer;transition:opacity .15s;padding:0;}.mb-cv2 .mentor-img-btn:hover {opacity:.95;}.mb-cv2 .mentor-img-btn img {width:100%;height:100%;object-fit:cover;}.mb-cv2 .mentor-img-fade {position:absolute;left:0;right:0;bottom:0;height:64px;background:linear-gradient(179.9deg,rgba(115,115,115,0) 0%,#000 99.6%);border-radius:0 0 19px 19px;}.mb-cv2 .mentor-name-overlay {position:absolute;bottom:12px;left:0;right:0;text-align:center;color:#fff;font-weight:700;font-size:22px;letter-spacing:-.3px;}.mb-cv2 .mentor-tagline {font-weight:600;color:var(--ink);font-size:18px;margin-top:8px;letter-spacing:-.2px;line-height:1.2;}.mb-cv2 .mentor-desc {color:var(--muted);font-size:14px;margin-top:4px;line-height:1.5;}.mb-cv2 .mentor-meta {display:flex;align-items:center;gap:6px;margin-top:8px;}.mb-cv2 .star-ic {fill:var(--gold);color:var(--gold);flex-shrink:0;}.mb-cv2 .star-off {fill:#e5e7eb;color:#d1d5db;flex-shrink:0;}.mb-cv2 .mentor-rating {font-weight:600;color:var(--ink);font-size:18px;}.mb-cv2 .mentor-reviews {color:var(--muted);font-size:14px;}.mb-cv2 .skills-dd-wrap {margin-left:auto;position:relative;}.mb-cv2 .skills-btn {display:flex;align-items:center;gap:4px;background:var(--skill-bg);border:1px solid var(--skill-border);border-radius:9px;color:#fff;font-size:10px;padding:2px 8px;}.mb-cv2 .skills-btn:hover {opacity:.9;}.mb-cv2 .skills-dd {display:none;position:absolute;right:0;top:100%;margin-top:4px;background:#fff;border:1px solid var(--skill-border);border-radius:8px;box-shadow:0 6px 16px rgba(0,0,0,.15);z-index:10;padding:4px 0;min-width:100px;}.mb-cv2 .skills-dd.open {display:block;}.mb-cv2 .skills-dd p {font-size:11px;padding:4px 12px;color:var(--skill-bg);font-weight:500;}.mb-cv2 .skills-dd p:hover {background:var(--mint-pill);}.mb-cv2 .hire-btn {margin-top:12px;width:100%;background:var(--teal);color:#fff;border-radius:9999px;padding:8px;font-size:13px;font-weight:600;}.mb-cv2 .hire-btn:hover {opacity:.9;}.mb-cv2 .home-wrap {background:var(--mint);min-height:100vh;}.mb-cv2 .home-inner {max-width:1400px;margin:0 auto;padding:0 32px;}.mb-cv2 .welcome {padding:40px 0 16px;text-align:center;}.mb-cv2 .welcome h1 {font-weight:800;color:var(--ink);line-height:1.1;font-size:clamp(40px,4.5vw,60px);}.mb-cv2 .welcome h1 span {color:var(--teal-dark);}.mb-cv2 .welcome p {color:var(--muted);font-weight:500;font-size:18px;margin-top:8px;}.mb-cv2 .home-search-wrap {padding-bottom:24px;}.mb-cv2 .home-search {position:relative;max-width:641px;margin:0 auto;}.mb-cv2 .home-search input {width:100%;background:var(--mint);border-radius:30px;padding:16px 24px;font-weight:500;font-size:18px;color:var(--muted);box-shadow:4px 8px 18px rgba(0,0,0,.25);border:none;outline:none;box-sizing:border-box;}.mb-cv2 .home-search input:focus {box-shadow:4px 8px 18px rgba(0,0,0,.25),0 0 0 2px rgba(22,121,111,.3);}.mb-cv2 .home-search svg {position:absolute;right:20px;top:50%;transform:translateY(-50%);color:#454646;}.mb-cv2 .cat-pills {display:flex;flex-direction:column;gap:12px;margin-bottom:32px;}.mb-cv2 .cat-pill-row {display:flex;gap:16px;flex-wrap:wrap;}.mb-cv2 .cat-pill-row.centered {justify-content:center;}.mb-cv2 .cat-pill {border-radius:25px;padding:12px 32px;font-size:18px;font-weight:500;box-shadow:0px 4px 8.7px rgba(0,0,0,.19);background:var(--teal-dark);color:#fff;transition:all .1s;}.mb-cv2 .cat-pill:hover {background:#0a3a36;}.mb-cv2 .cat-pill:active {transform:scale(.95);}.mb-cv2 .tra-stats-grid {display:grid;grid-template-columns:1fr;gap:24px;margin-bottom:32px;}@media(min-width:1024px) {.mb-cv2 .tra-stats-grid {grid-template-columns:1fr 1fr;}}.mb-cv2 .tra-card {background:#fff;border-radius:23px;box-shadow:5px 5px 26.5px rgba(113,180,173,.43);padding:24px;}.mb-cv2 .tra-card h2 {font-weight:600;color:var(--ink);font-size:22px;margin-bottom:16px;}.mb-cv2 .tra-empty {color:var(--muted);font-size:15px;text-align:center;padding:24px 0;}.mb-cv2 .tra-row {background:rgba(179,217,213,.77);border-radius:10px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}.mb-cv2 .tra-row div strong {display:block;font-weight:600;color:var(--ink);font-size:18px;}.mb-cv2 .tra-row div span {font-weight:600;color:var(--muted);font-size:14px;}.mb-cv2 .tra-row button {background:var(--teal-dark);color:#fff;border-radius:33px;padding:6px 20px;font-size:13px;font-weight:600;}.mb-cv2 .tra-row button:hover {opacity:.9;}.mb-cv2 .tra-more {margin-top:8px;width:100%;border:1px solid var(--border-teal);background:var(--mint);border-radius:20px;padding:6px;color:var(--teal-dark);font-weight:600;font-size:14px;text-decoration:underline;}.mb-cv2 .tra-more:hover {background:rgba(207,238,235,.3);}.mb-cv2 .stats-col {display:flex;flex-direction:column;gap:16px;}.mb-cv2 .stats-row {display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}.mb-cv2 .stat-box {border-radius:23px;box-shadow:5px 5px 26.5px rgba(113,180,173,.43);padding:20px;text-align:center;}.mb-cv2 .stat-box.teal {background:var(--mint-mid);}.mb-cv2 .stat-box.teal b {color:#fff;}.mb-cv2 .stat-box.teal span {color:#fff;}.mb-cv2 .stat-box.mint {background:var(--mint-mid2);}.mb-cv2 .stat-box.mint b, .mb-cv2 .stat-box.mint span {color:var(--teal-dark);}.mb-cv2 .stat-box.white {background:#fff;}.mb-cv2 .stat-box.white b, .mb-cv2 .stat-box.white span {color:var(--teal-dark);}.mb-cv2 .stat-box b {display:block;font-weight:600;font-size:32px;}.mb-cv2 .stat-box span {display:block;font-weight:600;font-size:14px;margin-top:8px;}.mb-cv2 .post-new-btn {width:100%;background:var(--teal-dark);color:#fff;border-radius:23px;box-shadow:5px 5px 26.5px rgba(113,180,173,.43);padding:20px;font-size:20px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:12px;transition:opacity .15s;}.mb-cv2 .post-new-btn:hover {opacity:.9;}.mb-cv2 .carousel-card {background:#fff;box-shadow:inset 0 0 22.7px rgba(0,0,0,.25);border-radius:16px;padding:32px 24px;margin-bottom:40px;}.mb-cv2 .carousel-head {display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}.mb-cv2 .carousel-head h2 {font-weight:600;color:var(--ink);font-size:28px;}.mb-cv2 .carousel-btns {display:flex;align-items:center;gap:8px;}.mb-cv2 .carousel-btns button {width:36px;height:36px;border-radius:9999px;border:2px solid var(--border-teal);display:flex;align-items:center;justify-content:center;color:var(--border-teal);transition:border-color .15s;}.mb-cv2 .carousel-btns button:last-child {border-color:var(--teal-dark);color:var(--teal-dark);}.mb-cv2 .carousel-btns button:disabled {opacity:.3;cursor:default;}.mb-cv2 .carousel-btns button:not(:disabled):hover {border-color:var(--teal-dark);}.mb-cv2 .popular-wrap {padding-bottom:48px;}.mb-cv2 .popular-title {font-weight:800;color:var(--ink);text-align:center;margin-bottom:40px;font-size:clamp(32px,4vw,48px);}.mb-cv2 .popular-section {margin-bottom:48px;}.mb-cv2 .popular-head {display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}.mb-cv2 .popular-head h3 {font-weight:700;color:var(--ink);font-size:22px;}.mb-cv2 .popular-head button {color:var(--teal-dark);font-weight:600;font-size:15px;display:flex;align-items:center;gap:4px;}.mb-cv2 .popular-head button:hover {text-decoration:underline;}.mb-cv2 .feed-wrap {background:var(--mint);min-height:100vh;}.mb-cv2 .feed-inner {max-width:1200px;margin:0 auto;padding:32px;}.mb-cv2 .back-link {display:flex;align-items:center;gap:8px;color:var(--muted);font-weight:600;font-size:15px;margin-bottom:24px;}.mb-cv2 .back-link:hover {color:var(--teal-dark);}.mb-cv2 .back-link.light {color:rgba(255,255,255,.7);}.mb-cv2 .back-link.light:hover {color:#fff;}.mb-cv2 .feed-head-row {display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:32px;}.mb-cv2 .feed-eyebrow {color:var(--muted);font-size:14px;font-weight:500;margin-bottom:4px;}.mb-cv2 .feed-head-row h1 {font-weight:800;color:var(--teal-dark);font-size:40px;line-height:1.1;}.mb-cv2 .feed-count {color:var(--muted);font-size:15px;margin-top:4px;}.mb-cv2 .sort-row {display:flex;align-items:center;gap:8px;}.mb-cv2 .sort-row span {color:var(--muted);font-size:14px;font-weight:500;}.mb-cv2 .sort-row button {padding:8px 16px;border-radius:9999px;font-size:14px;font-weight:600;background:var(--mint-pill);color:var(--teal-dark);transition:background .15s;}.mb-cv2 .sort-row button.active {background:var(--teal-dark);color:#fff;}.mb-cv2 .sort-row button:hover {opacity:.85;}.mb-cv2 .feed-search {position:relative;max-width:560px;margin-bottom:32px;}.mb-cv2 .feed-search input {width:100%;background:#fff;border-radius:30px;padding:14px 48px 14px 48px;font-weight:500;font-size:16px;color:var(--muted);box-shadow:4px 8px 18px rgba(0,0,0,.15);border:none;outline:none;box-sizing:border-box;}.mb-cv2 .feed-search svg {position:absolute;left:20px;top:50%;transform:translateY(-50%);color:var(--muted);}.mb-cv2 .empty-state {text-align:center;padding:96px 0;color:var(--muted);}.mb-cv2 .empty-state svg {margin:0 auto 16px;opacity:.2;display:block;}.mb-cv2 .empty-state .big {font-weight:600;font-size:18px;}.mb-cv2 .seeall-wrap {background:var(--mint);min-height:100vh;}.mb-cv2 .seeall-hero {background:var(--teal-dark);padding:48px 32px;}.mb-cv2 .seeall-hero-inner {max-width:1200px;margin:0 auto;}.mb-cv2 .seeall-hero-inner h1 {font-weight:800;color:#fff;font-size:48px;line-height:1.1;margin:16px 0 8px;}.mb-cv2 .seeall-hero-inner h1 span {color:var(--mint-tint);}.mb-cv2 .seeall-hero-inner>p {color:rgba(255,255,255,.7);font-size:16px;}.mb-cv2 .seeall-inner {max-width:1200px;margin:0 auto;padding:32px;}.mb-cv2 .seeall-filters {background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid var(--mint-pill);padding:16px;margin-bottom:32px;display:flex;flex-wrap:wrap;align-items:center;gap:16px;}.mb-cv2 .seeall-search {position:relative;flex:1;min-width:200px;}.mb-cv2 .seeall-search input {width:100%;background:var(--mint);border-radius:9999px;padding:10px 16px 10px 40px;font-size:14px;color:var(--muted);border:none;outline:none;box-sizing:border-box;}.mb-cv2 .seeall-search svg {position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--muted);}.mb-cv2 .seeall-results {margin-left:auto;color:var(--muted);font-size:13px;}.mb-cv2 .teal-page {background:var(--teal);min-height:100vh;}.mb-cv2 .teal-inner {max-width:960px;margin:0 auto;padding:40px 24px;}.mb-cv2 .teal-inner.wide {max-width:1300px;padding:40px 32px;}.mb-cv2 .teal-title {font-weight:800;color:#fff;font-size:56px;line-height:1.1;margin-bottom:32px;filter:drop-shadow(0 4px 4px rgba(0,0,0,.25));}.mb-cv2 .white-card {background:var(--mint);border-radius:16px;box-shadow:4px 4px 19.7px rgba(0,0,0,.25);padding:32px;margin-bottom:24px;}.mb-cv2 .white-card.no-max {max-width:none;}.mb-cv2 .white-card h2 {font-weight:700;color:var(--ink);font-size:32px;margin-bottom:24px;}.mb-cv2 .field {margin-bottom:20px;}.mb-cv2 .field label {display:block;font-weight:600;color:var(--teal-dark);font-size:18px;margin-bottom:8px;}.mb-cv2 .field input, .mb-cv2 .field textarea, .mb-cv2 .field select {width:100%;border:3px solid var(--teal-dark);border-radius:12px;padding:12px 16px;font-size:15px;font-weight:500;color:var(--ink);background:transparent;outline:none;box-sizing:border-box;font-family:'Montserrat',sans-serif;}.mb-cv2 .field textarea {resize:none;}.mb-cv2 .select-wrap {position:relative;}.mb-cv2 .select-wrap select {appearance:none;padding-right:40px;}.mb-cv2 .select-wrap svg {position:absolute;right:16px;top:50%;transform:translateY(-50%);color:var(--teal-dark);pointer-events:none;}.mb-cv2 .select-wrap.max624 {max-width:624px;}.mb-cv2 .budget-wrap {position:relative;}.mb-cv2 .budget-wrap span {position:absolute;left:16px;top:50%;transform:translateY(-50%);font-weight:600;color:var(--teal-dark);font-size:17px;}.mb-cv2 .budget-wrap input {padding-left:32px;}.mb-cv2 .hint {color:var(--muted);font-size:12px;margin-top:8px;}.mb-cv2 .post-submit-btn {width:100%;background:var(--ink);color:#fff;border-radius:9999px;padding:16px;font-weight:600;font-size:20px;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .15s;}.mb-cv2 .post-submit-btn:hover {opacity:.8;}.mb-cv2 .post-submit-btn:disabled {opacity:.5;}.mb-cv2 .checkout-grid {display:grid;grid-template-columns:1fr;gap:24px;}@media(min-width:1024px) {.mb-cv2 .checkout-grid {grid-template-columns:1fr 500px;}}.mb-cv2 .checkout-h2 {font-weight:700;color:var(--muted);font-size:36px;margin-bottom:24px;}.mb-cv2 .checkout-h2.center {text-align:center;}.mb-cv2 .box {border:1px solid var(--muted2);border-radius:7px;padding:20px;margin-bottom:20px;}.mb-cv2 .box-title {font-weight:700;color:var(--teal-dark);font-size:20px;margin-bottom:16px;}.mb-cv2 .method-row {display:flex;align-items:center;gap:12px;cursor:pointer;margin-bottom:12px;}.mb-cv2 .method-row:last-child {margin-bottom:0;}.mb-cv2 .method-row span {color:var(--ink);font-weight:500;font-size:16px;}.mb-cv2 .radio-dot {width:20px;height:20px;border-radius:9999px;border:2px solid var(--muted);display:flex;align-items:center;justify-content:center;flex-shrink:0;}.mb-cv2 .radio-dot.checked {background:var(--muted);border-color:var(--muted);color:#fff;}.mb-cv2 .small-label {color:var(--muted2);font-size:13px;margin-bottom:4px;}.mb-cv2 .ic {width:100%;border:1px solid #000;border-radius:7px;padding:8px 12px;font-size:14px;box-sizing:border-box;outline:none;}.mb-cv2 .ic:focus {box-shadow:0 0 0 2px rgba(22,121,111,.3);}.mb-cv2 .card-row {display:flex;gap:8px;align-items:center;margin-top:8px;}.mb-cv2 .card-row .grow {flex:1;}.mb-cv2 .card-row .w28 {width:112px;flex-shrink:0;}.mb-cv2 .card-row .w20 {width:80px;flex-shrink:0;}.mb-cv2 .ic.mm, .mb-cv2 .ic.yy {width:56px;flex-shrink:0;}.mb-cv2 .slash {font-weight:700;color:var(--ink);}.mb-cv2 .cvv-wrap {position:relative;width:80px;flex-shrink:0;}.mb-cv2 .cvv-wrap input {padding-right:32px;}.mb-cv2 .cvv-wrap button {position:absolute;right:8px;top:50%;transform:translateY(-50%);color:var(--muted2);}.mb-cv2 .auth-btn {width:100%;background:var(--mint-mid);color:#fff;border-radius:12px;padding:16px;font-weight:700;font-size:20px;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .15s;}.mb-cv2 .auth-btn:hover {opacity:.9;}.mb-cv2 .auth-btn:disabled {opacity:.5;}.mb-cv2 .terms-note {color:var(--ink);font-size:13px;margin-top:16px;line-height:1.5;}.mb-cv2 .terms-note strong {text-decoration:underline;cursor:pointer;}.mb-cv2 .line {color:var(--ink);font-weight:700;font-size:15px;margin-bottom:2px;}.mb-cv2 .line em {font-weight:500;font-style:italic;}.mb-cv2 .summary-line {display:flex;justify-content:space-between;font-size:15px;margin-bottom:4px;}.mb-cv2 .summary-line span {font-weight:500;color:var(--ink);}.mb-cv2 .summary-line strong {color:var(--ink);}.mb-cv2 .summary-total {background:rgba(211,236,233,.41);border-radius:11px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;}.mb-cv2 .summary-total span {font-weight:500;color:var(--ink);font-size:16px;}.mb-cv2 .summary-total strong {color:var(--ink);font-size:18px;}.mb-cv2 .white-card.fit {height:fit-content;}.mb-cv2 .cp-grid {display:grid;grid-template-columns:1fr;gap:24px;max-width:1300px;margin:0 auto;align-items:start;}@media(min-width:1024px) {.mb-cv2 .cp-grid {grid-template-columns:1fr 380px;}}.mb-cv2 .cp-main {padding:0;overflow:hidden;}.mb-cv2 .cp-cover {height:149px;background:var(--muted2);position:relative;}.mb-cv2 .cp-cover button {position:absolute;bottom:12px;right:16px;background:#fff;border-radius:6px;padding:6px 12px;display:flex;align-items:center;gap:6px;font-size:13px;font-weight:500;box-shadow:0 2px 6px rgba(0,0,0,.15);}.mb-cv2 .cp-cover button:hover {background:#f9fafb;}.mb-cv2 .cp-body {padding:0 32px 32px;margin-top:-70px;}.mb-cv2 .cp-avatar-wrap {position:relative;display:inline-block;margin-bottom:16px;}.mb-cv2 .cp-avatar {width:140px;height:140px;border-radius:9999px;border:3px solid #fff;background:#454646;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(0,0,0,.2);color:#fff;}.mb-cv2 .cp-avatar-wrap button {position:absolute;bottom:4px;right:4px;width:40px;height:40px;border-radius:9999px;background:var(--mint-mid2);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.15);color:var(--teal-dark);}.mb-cv2 .cp-avatar-wrap button:hover {opacity:.8;}.mb-cv2 .cp-name-row {display:flex;align-items:center;gap:8px;margin-bottom:4px;}.mb-cv2 .cp-name-row h2 {font-weight:700;color:var(--ink);font-size:28px;}.mb-cv2 .verified-ic {color:#24B7A8;}.mb-cv2 .cp-tagline {color:var(--muted);font-size:14px;margin-bottom:8px;}.mb-cv2 .cp-rating {display:flex;align-items:center;gap:8px;margin-bottom:12px;color:var(--ink);font-size:14px;}.mb-cv2 .cp-body h3 {font-weight:600;color:var(--ink);font-size:20px;margin-bottom:8px;}.mb-cv2 .cp-about {color:var(--ink);font-size:14px;line-height:1.6;text-align:justify;}.mb-cv2 .cp-reviews-h {font-weight:600;color:var(--ink);font-size:20px;margin-bottom:24px;}.mb-cv2 .review-grid {display:grid;grid-template-columns:1fr;gap:16px;margin-bottom:16px;}@media(min-width:768px) {.mb-cv2 .review-grid {grid-template-columns:repeat(3,1fr);}}.mb-cv2 .review-card {background:rgba(179,217,213,.77);border-radius:20px;padding:20px;}.mb-cv2 .review-task-title {font-weight:600;color:var(--ink);font-size:16px;margin-bottom:8px;}.mb-cv2 .review-stars {display:flex;align-items:center;gap:2px;margin-bottom:8px;}.mb-cv2 .review-stars .rn {color:var(--teal-dark);font-weight:700;font-size:12px;margin-left:4px;}.mb-cv2 .review-stars .rby {color:var(--teal-dark);font-size:11px;}.mb-cv2 .review-quote {color:var(--ink);font-size:12px;font-style:italic;line-height:1.5;}.mb-cv2 .center {text-align:center;}.mb-cv2 .link-btn {color:var(--teal);font-weight:600;font-size:16px;text-decoration:underline;}.mb-cv2 .link-btn:hover {opacity:.8;}.mb-cv2 .cp-side {display:flex;flex-direction:column;gap:16px;}.mb-cv2 .side-title {font-weight:600;color:var(--muted);font-size:20px;margin-bottom:16px;}.mb-cv2 .side-list {list-style:disc;padding-left:20px;font-size:16px;color:var(--ink);}.mb-cv2 .side-list li {margin-bottom:4px;}.mb-cv2 .verify-line {display:flex;align-items:flex-start;gap:8px;font-size:15px;margin-bottom:6px;color:var(--ink);}.mb-cv2 .check-ic {color:#24B7A8;flex-shrink:0;margin-top:2px;}.mb-cv2 .verify-cta {width:100%;background:var(--mint-mid2);border-radius:12px;padding:10px;font-weight:600;color:var(--ink);font-size:16px;display:flex;align-items:center;justify-content:center;gap:8px;}.mb-cv2 .verify-cta:hover {opacity:.85;}.mb-cv2 .pv-inner {max-width:1400px;margin:0 auto;padding:32px 24px;}.mb-cv2 .pv-grid {display:grid;grid-template-columns:1fr;gap:24px;align-items:start;}@media(min-width:1024px) {.mb-cv2 .pv-grid {grid-template-columns:1fr 420px;}}.mb-cv2 .pv-main {padding:0;overflow:hidden;}.mb-cv2 .pv-cover {height:149px;background:var(--muted2);}.mb-cv2 .pv-body {padding:0 32px 32px;margin-top:-70px;}.mb-cv2 .pv-avatar-wrap {width:140px;height:140px;border-radius:9999px;border:3px solid #fff;background:#454646;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(0,0,0,.2);margin-bottom:16px;overflow:hidden;}.mb-cv2 .pv-avatar-wrap img {width:100%;height:100%;object-fit:cover;border-radius:9999px;}.mb-cv2 .pv-body h2 {font-family:'Inter',sans-serif;font-weight:700;color:var(--ink);font-size:30px;line-height:1.1;}.mb-cv2 .pv-title-txt {font-family:'Inter',sans-serif;color:var(--ink);font-weight:500;font-size:16px;margin-top:4px;margin-bottom:8px;}.mb-cv2 .pv-rating-row {display:flex;align-items:center;gap:8px;margin-bottom:8px;}.mb-cv2 .pv-rating-row span {color:var(--ink);font-weight:500;font-size:15px;}.mb-cv2 .pv-location {display:flex;align-items:center;gap:6px;color:var(--teal-dark);font-weight:500;font-size:15px;margin-bottom:20px;}.mb-cv2 .pv-body h3 {font-family:'Inter',sans-serif;font-weight:600;color:var(--ink);font-size:22px;margin-bottom:12px;}.mb-cv2 .pv-about {color:var(--ink);font-size:15px;line-height:1.6;text-align:justify;margin-bottom:24px;}.mb-cv2 .pv-skills {display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;}.mb-cv2 .pv-skill-badge {background:var(--muted);color:#fff;font-weight:500;font-size:15px;padding:6px 16px;border-radius:9999px;}.mb-cv2 .pv-hire-btn {width:100%;background:var(--teal-dark);color:#fff;font-weight:700;font-size:18px;border-radius:9999px;padding:16px;box-shadow:0 8px 16px rgba(0,0,0,.15);transition:opacity .15s;}.mb-cv2 .pv-hire-btn:hover {opacity:.9;}.mb-cv2 .pv-side {display:flex;flex-direction:column;gap:20px;}.mb-cv2 .edu-degree {font-family:'Montserrat',sans-serif;font-weight:700;color:var(--ink);font-size:17px;}.mb-cv2 .edu-school {font-family:'Montserrat',sans-serif;color:var(--muted);font-weight:500;font-size:14px;margin-top:4px;}.mb-cv2 .edu-year {font-family:'Montserrat',sans-serif;color:var(--muted);font-style:italic;font-size:14px;margin-top:4px;}.mb-cv2 .pv-reviews {margin-top:24px;padding:32px;}.mb-cv2 .pv-reviews h2 {font-family:'Inter',sans-serif;font-weight:600;color:var(--ink);font-size:22px;margin-bottom:24px;}.mb-cv2 .manage-wrap {min-height:100vh;background-image:linear-gradient(to bottom,#f1fffe,#add8d3);}.mb-cv2 .manage-inner {max-width:1200px;margin:0 auto;padding:40px 32px;}.mb-cv2 .manage-inner h1 {font-weight:700;color:var(--ink);font-size:36px;margin-bottom:8px;}.mb-cv2 .manage-sub {color:var(--muted);font-size:14px;margin-bottom:24px;}.mb-cv2 .manage-toolbar {display:flex;align-items:center;gap:16px;margin-bottom:32px;flex-wrap:wrap;}.mb-cv2 .manage-search {position:relative;flex:1;max-width:800px;min-width:200px;}.mb-cv2 .manage-search input {width:100%;background:var(--mint);border-radius:9999px;padding:12px 48px 12px 24px;font-size:15px;color:var(--muted);box-shadow:4px 8px 18px rgba(0,0,0,.18);border:none;outline:none;box-sizing:border-box;}.mb-cv2 .manage-search svg {position:absolute;right:20px;top:50%;transform:translateY(-50%);color:#454646;}.mb-cv2 .filter-wrap {position:relative;}.mb-cv2 .filter-btn {background:var(--mint);border-radius:16px;box-shadow:3px 4px 8.5px rgba(0,0,0,.18);padding:12px 20px;display:flex;align-items:center;gap:8px;color:var(--muted);font-weight:500;font-size:15px;}.mb-cv2 .filter-btn:hover {background:rgba(207,238,235,.4);}.mb-cv2 .filter-btn svg.rot {transform:rotate(180deg);}.mb-cv2 .filter-badge {background:var(--teal);color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:9999px;}.mb-cv2 .filter-dd {position:absolute;right:0;top:100%;margin-top:8px;background:#fff;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.15);border:1px solid rgba(151,214,207,.4);z-index:20;padding:8px 0;min-width:190px;}.mb-cv2 .filter-dd button {width:100%;text-align:left;padding:10px 16px;font-size:14px;font-weight:500;display:flex;align-items:center;justify-content:space-between;color:var(--ink);}.mb-cv2 .filter-dd button:hover {background:#f8fffe;}.mb-cv2 .filter-dd button.active {background:var(--mint-pill);color:var(--teal);}.mb-cv2 .filter-chip {display:flex;align-items:center;gap:4px;background:var(--muted);color:#fff;border-radius:9999px;padding:6px 12px;font-size:13px;font-weight:500;}.mb-cv2 .filter-chip:hover {opacity:.8;}.mb-cv2 .task-grid {display:grid;grid-template-columns:1fr;gap:20px;}@media(min-width:700px) {.mb-cv2 .task-grid {grid-template-columns:repeat(2,1fr);}}@media(min-width:1024px) {.mb-cv2 .task-grid {grid-template-columns:repeat(3,1fr);}}.mb-cv2 .task-card {background:rgba(255,255,255,.77);border:1px solid rgba(97,120,118,.5);border-radius:23px;padding:20px;transition:box-shadow .15s;}.mb-cv2 .task-card:hover {box-shadow:0 10px 20px rgba(0,0,0,.1);}.mb-cv2 .task-card h3 {font-weight:700;color:var(--ink);font-size:22px;text-align:center;margin-bottom:12px;}.mb-cv2 .task-status-row {margin-bottom:8px;display:flex;align-items:center;gap:8px;}.mb-cv2 .task-status-row .label {font-weight:700;color:var(--muted);font-size:14px;}.mb-cv2 .status-pill {font-size:13px;font-weight:500;font-style:italic;padding:2px 8px;border-radius:5px;}.mb-cv2 .status-pill.st-draft {background:#f3f4f6;color:#6b7280;}.mb-cv2 .status-pill.st-live {background:var(--mint-mid);color:#fff;}.mb-cv2 .status-pill.st-pending {background:#fef3c7;color:#b45309;}.mb-cv2 .status-pill.st-completed {background:#d1fae5;color:#047857;}.mb-cv2 .status-pill.st-disputed {background:#fee2e2;color:#b91c1c;}.mb-cv2 .task-cat-pill {background:rgba(113,180,173,.43);border-radius:6px;padding:6px 12px;font-weight:700;color:var(--ink);font-size:14px;margin-bottom:8px;}.mb-cv2 .task-mentor {color:var(--muted);font-size:12px;font-weight:700;margin-bottom:8px;}.mb-cv2 .task-budget {background:rgba(113,180,173,.18);border-radius:6px;padding:6px 12px;font-weight:700;font-style:italic;color:var(--muted);font-size:13px;margin-bottom:16px;}.mb-cv2 .action-btn {width:100%;background:rgba(113,180,173,.43);border:1px solid rgba(97,120,118,.5);border-radius:6px;padding:8px;font-weight:700;color:var(--ink);font-size:14px;transition:background .15s;}.mb-cv2 .action-btn:hover {background:rgba(113,180,173,.6);}.mb-cv2 .new-task-tile {background:rgba(97,120,118,.4);border:4px solid #fff;border-radius:23px;padding:20px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;min-height:200px;color:#fff;transition:background .15s;}.mb-cv2 .new-task-tile:hover {background:rgba(97,120,118,.55);}.mb-cv2 .new-task-tile p {font-weight:600;font-size:24px;}.mb-cv2 .action-overlay {position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.3);}.mb-cv2 .action-card {position:relative;background:#fff;border:1px solid #000;border-radius:11px;width:360px;max-width:90vw;box-shadow:0 25px 50px rgba(0,0,0,.4);}.mb-cv2 .action-card-head {position:relative;padding:20px 24px 12px;display:flex;align-items:center;justify-content:space-between;}.mb-cv2 .action-card-head p {font-weight:700;color:var(--muted);font-size:22px;}.mb-cv2 .action-card-head button {color:var(--ink);}.mb-cv2 .action-card-head button:hover {opacity:.6;}.mb-cv2 .action-divider {margin:0 20px 4px;height:2px;background:#000;border-radius:9999px;}.mb-cv2 .action-list {padding:0 24px 20px;display:flex;flex-direction:column;gap:2px;}.mb-cv2 .action-list button {width:100%;text-align:left;font-weight:600;color:var(--ink);font-size:18px;padding:10px 0;transition:all .15s;}.mb-cv2 .action-list button:hover {color:var(--teal);transform:translateX(4px);}.mb-cv2 .inbox-shell {background:var(--mint-mid);display:flex;min-height:calc(100vh - 72px);}.mb-cv2 .inbox-sidebar {width:320px;flex-shrink:0;background:var(--mint);display:flex;flex-direction:column;}.mb-cv2 .inbox-title {padding:24px 24px 8px;}.mb-cv2 .inbox-title h1 {font-weight:800;color:var(--ink);font-size:36px;filter:drop-shadow(0 4px 4px rgba(0,0,0,.2));}.mb-cv2 .inbox-search {padding:0 16px;margin-bottom:12px;position:relative;}.mb-cv2 .inbox-search input {width:100%;background:#fff;border-radius:9999px;padding:10px 16px 10px 40px;font-size:14px;color:var(--muted);box-shadow:4px 4px 13.6px rgba(0,0,0,.2);border:none;outline:none;box-sizing:border-box;}.mb-cv2 .inbox-search svg {position:absolute;left:32px;top:50%;transform:translateY(-50%);color:var(--muted);}.mb-cv2 .inbox-tabs {padding:0 16px;margin-bottom:12px;display:flex;align-items:center;gap:12px;}.mb-cv2 .inbox-tabs button {padding:6px 20px;border-radius:9999px;font-size:15px;font-weight:600;color:var(--ink);}.mb-cv2 .inbox-tabs button.active {background:#c1e2de;color:#208479;}.mb-cv2 .inbox-tabs button.link {padding:0;border-radius:0;background:none !important;}.mb-cv2 .inbox-tabs button.link.active {color:var(--teal);text-decoration:underline;}.mb-cv2 .inbox-tabs button:hover {background:rgba(207,238,235,.4);}.mb-cv2 .convo-list {flex:1;overflow-y:auto;border-right:3px solid rgba(97,120,118,.2);}.mb-cv2 .inbox-empty-list {text-align:center;color:var(--muted);font-size:14px;padding:32px 0;}.mb-cv2 .convo-item {width:100%;display:flex;align-items:center;gap:12px;padding:16px;text-align:left;border-bottom:1px solid rgba(0,0,0,.05);transition:background .15s;}.mb-cv2 .convo-item:hover {background:rgba(207,238,235,.3);}.mb-cv2 .convo-item.active {background:rgba(179,217,213,.6);}.mb-cv2 .convo-avatar {position:relative;flex-shrink:0;width:48px;height:48px;border-radius:9999px;border:2px solid var(--teal);background:#e8f5f3;display:flex;align-items:center;justify-content:center;color:var(--teal);}.mb-cv2 .online-dot {position:absolute;bottom:0;right:0;width:16px;height:16px;border-radius:9999px;border:2px solid #fff;}.mb-cv2 .online-dot.on {background:#15FF00;}.mb-cv2 .online-dot.off {background:#D5D5D5;}.mb-cv2 .convo-meta {flex:1;min-width:0;}.mb-cv2 .convo-top {display:flex;align-items:center;justify-content:space-between;}.mb-cv2 .convo-top strong {font-weight:600;color:var(--ink);font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}.mb-cv2 .convo-time-wrap {display:flex;align-items:center;gap:4px;flex-shrink:0;}.mb-cv2 .convo-time-wrap span {color:var(--muted);font-size:13px;}.mb-cv2 .convo-badge {width:20px;height:20px;border-radius:9999px;background:var(--teal);color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;}.mb-cv2 .convo-meta p {color:var(--muted);font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}.mb-cv2 .chat-wrap {flex:1;display:flex;align-items:flex-start;justify-content:center;padding:24px;overflow-y:auto;}.mb-cv2 .chat-panel {width:100%;max-width:900px;background:var(--mint);border-radius:34px;box-shadow:4px 4px 10.3px rgba(0,0,0,.2);display:flex;flex-direction:column;min-height:calc(100vh - 144px);}.mb-cv2 .chat-header {display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid rgba(0,0,0,.1);flex-shrink:0;}.mb-cv2 .chat-header-left {display:flex;align-items:center;gap:12px;}.mb-cv2 .chat-header-left svg {width:20px;height:20px;color:var(--teal);background:#e8f5f3;border:2px solid var(--teal);border-radius:9999px;padding:8px;box-sizing:content-box;}.mb-cv2 .chat-header-left p {font-weight:600;color:var(--ink);font-size:18px;}.mb-cv2 .chat-header button {border:1px solid #000;border-radius:10px;padding:8px 20px;font-size:15px;font-weight:600;}.mb-cv2 .chat-header button:hover {background:rgba(207,238,235,.3);}.mb-cv2 .chat-body {flex:1;overflow-y:auto;padding:16px 24px;background:#fff;box-shadow:inset 0 0 8.4px rgba(0,0,0,.15);min-height:300px;}.mb-cv2 .chat-time-stamp {text-align:center;color:var(--muted);font-size:13px;margin-bottom:16px;}.mb-cv2 .bubble-row {display:flex;align-items:flex-end;gap:8px;margin-bottom:12px;}.mb-cv2 .bubble-row.mine {justify-content:flex-end;}.mb-cv2 .bubble-avatar {width:36px;height:36px;border-radius:9999px;border:2px solid var(--teal);background:#e8f5f3;display:flex;align-items:center;justify-content:center;color:var(--teal);flex-shrink:0;}.mb-cv2 .bubble {max-width:60%;padding:10px 20px;font-weight:600;font-size:17px;background:#d3ece9;color:var(--ink);border-radius:70px;}.mb-cv2 .chat-input-row {display:flex;align-items:center;gap:12px;padding:16px 20px;border-top:1px solid rgba(0,0,0,.05);flex-shrink:0;}.mb-cv2 .attach-btn {width:44px;height:44px;border-radius:13px;background:rgba(211,236,233,.6);display:flex;align-items:center;justify-content:center;color:var(--muted);flex-shrink:0;}.mb-cv2 .chat-input-box {flex:1;background:#fff;border-radius:9999px;box-shadow:4px 4px 13.6px rgba(0,0,0,.2);padding:10px 20px;}.mb-cv2 .chat-input-box input {width:100%;border:none;outline:none;background:transparent;font-size:16px;color:var(--muted);}.mb-cv2 .send-btn {color:var(--mint-mid);flex-shrink:0;}.mb-cv2 .send-btn:hover {color:var(--teal);}.mb-cv2 .settings-shell {background:var(--mint-mid);min-height:100vh;display:flex;}.mb-cv2 .settings-sidebar {width:320px;flex-shrink:0;background:var(--mint);display:flex;flex-direction:column;min-height:calc(100vh - 72px);}.mb-cv2 .settings-title {padding:32px 32px 12px;}.mb-cv2 .settings-title h1 {font-weight:800;color:var(--ink);font-size:36px;filter:drop-shadow(0 4px 4px rgba(0,0,0,.2));}.mb-cv2 .settings-search {padding:0 20px;margin-bottom:16px;position:relative;}.mb-cv2 .settings-search input {width:100%;background:#fff;border-radius:9999px;padding:10px 16px 10px 40px;font-size:14px;color:var(--muted);box-shadow:4px 4px 13.6px rgba(0,0,0,.2);border:none;outline:none;box-sizing:border-box;}.mb-cv2 .settings-search svg {position:absolute;left:36px;top:50%;transform:translateY(-50%);color:var(--muted);}.mb-cv2 .settings-nav {flex:1;padding:0 16px;display:flex;flex-direction:column;gap:4px;}.mb-cv2 .settings-nav-item {width:100%;text-align:left;padding:12px 20px;border-radius:9999px;font-size:16px;font-weight:600;color:var(--ink);transition:background .15s;}.mb-cv2 .settings-nav-item:hover {background:rgba(207,238,235,.4);}.mb-cv2 .settings-nav-item.active {background:#c1e2de;color:#208479;}.mb-cv2 .settings-logout {padding:24px 32px;margin-top:auto;}.mb-cv2 .settings-logout button {display:flex;align-items:center;gap:8px;color:var(--danger);font-weight:700;font-size:16px;}.mb-cv2 .settings-logout button:hover {opacity:.7;}.mb-cv2 .settings-content {flex:1;padding:32px;overflow-y:auto;}.mb-cv2 .settings-stack {display:flex;flex-direction:column;gap:24px;max-width:984px;}.mb-cv2 .settings-card {max-width:984px;overflow:hidden;padding:0;}.mb-cv2 .settings-card.pad {padding:32px;}.mb-cv2 .settings-card.wide-inner {max-width:984px;}.mb-cv2 .settings-card h2 {font-weight:700;color:var(--ink);font-size:30px;margin-bottom:8px;}.mb-cv2 .settings-card h3 {font-weight:700;color:var(--ink);font-size:22px;margin-bottom:20px;}.mb-cv2 .settings-cover {height:149px;background:var(--muted2);position:relative;}.mb-cv2 .settings-cover button {position:absolute;bottom:12px;right:16px;background:#fff;border-radius:6px;padding:6px 12px;display:flex;align-items:center;gap:6px;font-size:14px;font-weight:500;box-shadow:0 2px 6px rgba(0,0,0,.15);}.mb-cv2 .settings-cover button:hover {background:#f9fafb;}.mb-cv2 .settings-card-body {padding:0 32px 32px;}.mb-cv2 .settings-avatar-wrap {position:relative;display:inline-block;margin-top:-64px;margin-bottom:24px;}.mb-cv2 .settings-avatar {width:146px;height:146px;border-radius:9999px;border:3px solid #fff;background:#454646;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(0,0,0,.2);color:#fff;}.mb-cv2 .settings-avatar-wrap button {position:absolute;bottom:4px;right:4px;width:40px;height:40px;border-radius:9999px;background:var(--mint-mid2);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.15);color:var(--teal-dark);}.mb-cv2 .settings-avatar-wrap button:hover {opacity:.8;}.mb-cv2 .settings-grid2 {display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}.mb-cv2 .settings-grid3 {display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}.mb-cv2 .lbl {color:var(--ink);font-size:18px;margin-bottom:4px;}.mb-cv2 .lbl.bold {font-weight:700;}.mb-cv2 .lbl em {font-size:14px;color:var(--muted);font-style:italic;}.mb-cv2 .ic2 {width:100%;border:2px solid var(--muted);border-radius:6px;padding:12px 16px;font-size:16px;outline:none;box-sizing:border-box;font-family:'Montserrat',sans-serif;}.mb-cv2 .ic2:focus {box-shadow:0 0 0 2px rgba(22,121,111,.3);}.mb-cv2 textarea.ic2 {resize:none;}.mb-cv2 .char-count {text-align:right;color:#899998;font-size:13px;font-style:italic;margin-top:4px;}.mb-cv2 .mb14 {margin-bottom:14px;}.mb-cv2 .mb18 {margin-bottom:18px;}.mb-cv2 .mb20 {margin-bottom:20px;}.mb-cv2 .mb24 {margin-bottom:24px;}.mb-cv2 .mt8 {margin-top:8px;}.mb-cv2 .mt16 {margin-top:16px;}.mb-cv2 .mt20 {margin-top:20px;}.mb-cv2 .right {display:flex;justify-content:flex-end;}.mb-cv2 .right.gap {gap:12px;}.mb-cv2 .outline-btn {display:inline-flex;align-items:center;gap:8px;border:2px solid var(--teal-dark);border-radius:9999px;padding:8px 24px;color:var(--teal-dark);font-weight:600;font-size:16px;}.mb-cv2 .outline-btn:hover {background:rgba(207,238,235,.3);}.mb-cv2 .outline-btn2 {border:2px solid var(--teal);color:var(--teal);padding:10px 32px;border-radius:9999px;font-weight:600;font-size:17px;}.mb-cv2 .outline-btn2:hover {background:rgba(207,238,235,.2);}.mb-cv2 .save-btn {background:var(--teal);color:#fff;padding:10px 40px;border-radius:9999px;font-weight:600;font-size:18px;transition:opacity .15s;}.mb-cv2 .save-btn:hover {opacity:.9;}.mb-cv2 .save-btn.lg {padding:10px 32px;}.mb-cv2 .hint-italic {color:var(--muted);font-size:13px;font-style:italic;margin-top:4px;}.mb-cv2 .pw-wrap {position:relative;}.mb-cv2 .pw-wrap button {position:absolute;right:16px;top:50%;transform:translateY(-50%);color:var(--muted);}.mb-cv2 .danger-card {background:#ffeeee;border-radius:16px;box-shadow:0 4px 18.8px rgba(0,0,0,.25);padding:32px;max-width:984px;}.mb-cv2 .danger-card h3 {font-weight:700;color:var(--danger);font-size:22px;margin-bottom:8px;}.mb-cv2 .danger-sub {color:#786161;font-size:14px;font-style:italic;margin-bottom:24px;}.mb-cv2 .danger-row {display:flex;align-items:center;gap:16px;margin-bottom:16px;}.mb-cv2 .danger-row span {color:#786161;font-style:italic;font-size:16px;}.mb-cv2 .deactivate-btn {background:#794616;color:#fff;padding:12px 32px;border-radius:9999px;font-weight:600;font-size:18px;}.mb-cv2 .deactivate-btn:hover {opacity:.9;}.mb-cv2 .delete-btn {background:var(--danger);color:#fff;padding:12px 32px;border-radius:9999px;font-weight:600;font-size:18px;}.mb-cv2 .delete-btn:hover {opacity:.9;}.mb-cv2 .gcash-row {display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;}.mb-cv2 .gcash-status {background:rgba(179,217,213,.77);border-radius:9px;padding:4px 16px;display:inline-flex;align-items:center;gap:8px;margin-bottom:12px;color:var(--teal-dark);font-size:14px;font-style:italic;}.mb-cv2 .gcash-status strong {font-style:normal;}.mb-cv2 .line-em {color:var(--ink);font-size:17px;}.mb-cv2 .line-em em {font-style:italic;}.mb-cv2 .black-btn {background:#000;color:#fff;border-radius:9999px;padding:8px 24px;font-weight:500;font-size:16px;}.mb-cv2 .black-btn:hover {opacity:.8;}.mb-cv2 .txn-row {display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(137,153,152,.3);font-size:15px;}.mb-cv2 .txn-row:last-of-type {border-bottom:none;}.mb-cv2 .txn-title {font-weight:600;color:var(--ink);}.mb-cv2 .txn-date {color:var(--muted);font-size:12px;}.mb-cv2 .txn-amt {font-weight:700;color:var(--ink);}.mb-cv2 .teal-link-btn {display:flex;align-items:center;gap:8px;background:var(--teal);color:#fff;border-radius:16px;padding:10px 24px;font-weight:500;font-size:16px;}.mb-cv2 .teal-link-btn:hover {opacity:.9;}.mb-cv2 .notif-head {display:grid;grid-template-columns:1fr 80px 80px;gap:8px;padding:12px 16px;border-bottom:1px solid rgba(137,153,152,.3);margin-bottom:4px;}.mb-cv2 .notif-head span {font-weight:700;color:var(--ink);font-size:20px;}.mb-cv2 .notif-head span.center {text-align:center;}.mb-cv2 .notif-group-label {font-weight:700;color:var(--muted);font-size:18px;padding:16px;}.mb-cv2 .notif-row {display:grid;grid-template-columns:1fr 80px 80px;gap:8px;padding:8px 16px;align-items:center;border-bottom:1px solid rgba(137,153,152,.1);}.mb-cv2 .notif-row p {color:var(--ink);font-size:17px;font-style:italic;}.mb-cv2 .notif-cell {display:flex;justify-content:center;}.mb-cv2 .notif-toggle {width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:2px solid #1D1B20;border-radius:4px;color:#1D1B20;}.mb-cv2 .notif-toggle.on {border:none;background:none;}.mb-cv2 .verify-status-row {display:flex;align-items:center;gap:12px;margin-bottom:8px;}.mb-cv2 .verify-status-row p {font-weight:700;color:var(--muted);font-size:18px;}.mb-cv2 .status-badge-red {background:var(--danger);color:#fff;font-size:14px;font-style:italic;border-radius:9px;padding:4px 16px;}.mb-cv2 .id-select {width:100%;border:2px solid var(--muted);border-radius:7px;padding:12px 16px;color:var(--muted);font-size:17px;font-weight:600;appearance:none;background:#fff;font-family:'Montserrat',sans-serif;}.mb-cv2 .file-chip {display:flex;align-items:center;gap:8px;background:var(--mint-pill);border-radius:8px;padding:8px 16px;margin-bottom:16px;font-size:14px;color:var(--teal);font-weight:600;}.mb-cv2 .file-chip button {margin-left:auto;}.mb-cv2 .upload-btn {display:flex;align-items:center;gap:12px;background:var(--teal);color:#fff;border-radius:11px;padding:16px 32px;font-weight:600;font-size:18px;}.mb-cv2 .upload-btn:hover {opacity:.9;}.mb-cv2 .flex-gap {display:flex;gap:16px;flex-wrap:wrap;}.mb-cv2 .privacy-text {color:var(--muted);font-size:14px;text-align:justify;line-height:1.6;}.mb-cv2 .cancel-btn2 {border:2px solid #000;background:var(--mint);color:#000;border-radius:11px;padding:16px 32px;font-weight:600;font-size:18px;}.mb-cv2 .cancel-btn2:hover {background:#f9fafb;}.mb-cv2 .submit-verify-btn {background:#000;color:#fff;border-radius:11px;padding:16px 32px;font-weight:600;font-size:18px;}.mb-cv2 .submit-verify-btn:hover {opacity:.8;}.mb-cv2 .loggedout-wrap {min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--mint-mid);padding:24px;}.mb-cv2 .loggedout-card {background:var(--mint);border-radius:24px;padding:48px;max-width:420px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,.2);}.mb-cv2 .loggedout-card img {height:40px;margin:0 auto 24px;}.mb-cv2 .loggedout-card h2 {font-weight:800;color:var(--ink);font-size:24px;margin-bottom:12px;}.mb-cv2 .loggedout-card p {color:var(--muted);font-size:14px;line-height:1.6;margin-bottom:24px;}.mb-cv2 .loggedout-card button {background:var(--teal);color:#fff;border-radius:9999px;padding:12px 32px;font-weight:600;font-size:15px;}.mb-cv2 .loggedout-card button:hover {opacity:.9;}@media(max-width:900px) {.mb-cv2 .inbox-shell {flex-direction:column;}.mb-cv2 .inbox-sidebar {width:100%;}.mb-cv2 .settings-shell {flex-direction:column;}.mb-cv2 .settings-sidebar {width:100%;min-height:auto;}}
    </style>
    <style>
    .mb-cv2 .settings-search input::placeholder,
    .mb-cv2 .settings-search input,
    .mb-cv2 .settings-nav-item,
    .mb-cv2 .settings-card .ic2,
    .mb-cv2 .settings-card .id-select,
    .mb-cv2 .settings-card .field input,
    .mb-cv2 .settings-card .field textarea,
    .mb-cv2 .settings-card .field select {
        font-family: 'Montserrat', sans-serif;
    }
    .mb-cv2 .settings-shell { position:relative; }
    .mb-cv2 .settings-cover {
        background:linear-gradient(135deg, #c5d7d4 0%, #8eb7b1 100%);
        background-size:cover;
        background-position:center;
    }
    .mb-cv2 .settings-cover.has-image,
    .mb-cv2 .settings-cover.has-image button {
        background-size:cover;
        background-position:center;
    }
    .mb-cv2 .settings-avatar,
    .mb-cv2 .settings-avatar img,
    .mb-cv2 .profile-dd-avatar img,
    .mb-cv2 .nav-avatar-btn img {
        object-fit:cover;
    }
    .mb-cv2 .settings-contact-panel,
    .mb-cv2 .settings-subpanel {
        display:none;
    }
    .mb-cv2 .settings-contact-panel.open,
    .mb-cv2 .settings-subpanel.open {
        display:block;
    }
    .mb-cv2 .settings-help-note {
        color:var(--muted);
        font-size:13px;
        font-style:italic;
        margin-top:6px;
        line-height:1.5;
    }
    .mb-cv2 .settings-contact-grid {
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:20px;
    }
    @media (max-width: 700px) {
        .mb-cv2 .settings-contact-grid { grid-template-columns:1fr; }
    }
    .mb-cv2 .settings-contact-actions {
        display:flex;
        justify-content:flex-end;
        gap:12px;
        flex-wrap:wrap;
    }
    .mb-cv2 .settings-pill-status {
        display:inline-flex;
        align-items:center;
        gap:8px;
        border-radius:9999px;
        padding:4px 14px;
        font-size:13px;
        font-weight:700;
        background:rgba(207,238,235,.8);
        color:var(--teal-dark);
    }
    .mb-cv2 .billing-empty-state {
        border:1px dashed rgba(137,153,152,.45);
        border-radius:12px;
        padding:16px;
        color:var(--muted);
        font-size:14px;
        background:rgba(255,255,255,.45);
    }
    .mb-cv2 .billing-link-row {
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:16px;
        flex-wrap:wrap;
    }
    .mb-cv2 .billing-link-row .billing-meta {
        display:flex;
        flex-direction:column;
        gap:8px;
    }
    .mb-cv2 .billing-link-row .billing-meta .line-em {
        min-height:24px;
    }
    .mb-cv2 .settings-billing-btn[disabled] {
        opacity:.5;
        cursor:not-allowed;
    }
    .mb-cv2 .settings-modal {
        position:fixed;
        inset:0;
        z-index:3000;
        display:none;
        align-items:center;
        justify-content:center;
        background:rgba(30,30,30,.62);
        padding:20px;
    }
    .mb-cv2 .settings-modal.open { display:flex; }
    .mb-cv2 .settings-modal-card {
        width:min(100%, 480px);
        background:#fff;
        border-radius:24px;
        box-shadow:0 24px 60px rgba(0,0,0,.28);
        padding:28px;
    }
    .mb-cv2 .settings-modal-card h3 {
        margin:0 0 10px;
        font-size:24px;
        font-weight:800;
        color:var(--ink);
        text-align:center;
    }
    .mb-cv2 .settings-modal-card p {
        color:var(--muted);
        font-size:14px;
        line-height:1.6;
        text-align:center;
        margin-bottom:22px;
    }
    .mb-cv2 .settings-modal-actions {
        display:flex;
        gap:12px;
        justify-content:center;
        flex-wrap:wrap;
    }
    .mb-cv2 .settings-modal-actions .save-btn,
    .mb-cv2 .settings-modal-actions .outline-btn2 {
        min-width:140px;
        text-align:center;
        justify-content:center;
    }
    .mb-cv2 .settings-file-chip {
        display:flex;
        align-items:center;
        gap:8px;
        background:var(--mint-pill);
        color:var(--teal-dark);
        border-radius:9999px;
        padding:8px 14px;
        font-size:13px;
        font-weight:600;
        width:fit-content;
    }
    .mb-cv2 .settings-file-chip button {
        color:var(--danger);
        font-size:16px;
        line-height:1;
    }
    </style>
    <?php
}

/* ==========================================================================
   E. ONBOARDING
========================================================================== */

function mb_render_onboarding( $preferred_role = '' ) {
    ob_start(); ?>
    <div class="bntm-onboarding-wrap">
        <div class="bntm-brand-mark"><img src="<?php echo MB_LOGO_DARK; ?>" alt="MentorBe"></div>
        <h2>Welcome to MentorBe</h2>
        <p style="color:#6b7280;">Tell us how you will be using the platform.</p>
        <div class="bntm-role-cards">
            <div class="bntm-role-card <?php echo $preferred_role === 'client' ? 'selected' : ''; ?>" data-role="client">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h1M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <h4>I am a Client</h4>
                <p>I want to post tasks and hire skilled providers.</p>
            </div>
            <div class="bntm-role-card <?php echo $preferred_role === 'provider' ? 'selected' : ''; ?>" data-role="provider">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 1.79-8 4v2h16v-2c0-2.21-3.582-4-8-4z"/></svg>
                <h4>I am a Provider</h4>
                <p>I want to browse tasks and offer my skills.</p>
            </div>
        </div>
        <button id="mb-onboarding-btn" class="bntm-btn-primary" style="margin-top:20px;" <?php echo $preferred_role ? '' : 'disabled'; ?>>Continue</button>
        <div id="onboarding-msg"></div>
    </div>
    <script>
    (function() {
        var role = '<?php echo esc_js( $preferred_role ); ?>';
        document.querySelectorAll('.bntm-role-card').forEach(function(card) {
            card.addEventListener('click', function() {
                document.querySelectorAll('.bntm-role-card').forEach(function(c) { c.classList.remove('selected'); });
                this.classList.add('selected');
                role = this.dataset.role;
                document.getElementById('mb-onboarding-btn').disabled = false;
            });
        });
        document.getElementById('mb-onboarding-btn').addEventListener('click', function() {
            if (!role) return;
            this.disabled = true; this.textContent = 'Setting up...';
            var fd = new FormData();
            fd.append('action', 'mb_complete_onboarding');
            fd.append('role', role);
            fd.append('nonce', mbNonce);
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                if (json.success) { window.location.href = json.data.redirect; }
                else { bntmMbToast('onboarding-msg', json.data.message, false); document.getElementById('mb-onboarding-btn').disabled = false; document.getElementById('mb-onboarding-btn').textContent = 'Continue'; }
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

/* ==========================================================================
   F. CLIENT DASHBOARD  [mb_client_dashboard]
========================================================================== */

function bntm_shortcode_mb_client() {
    if ( ! is_user_logged_in() ) {
        $login_url = mb_find_page_url( '[mb_login]' );
        return '<div class="bntm-notice">Please <a href="' . esc_url( $login_url ) . '">log in</a> to access your dashboard.</div>';
    }

    $user    = wp_get_current_user();
    $profile = mb_get_profile( $user->ID );
    $onboarding_url = bntm_mb_onboarding_redirect_url( $user->ID, $profile );
    if ( $onboarding_url ) {
        wp_safe_redirect( $onboarding_url );
        exit;
    }

    ob_start();
    mb_dashboard_assets();
    mb_client_v2_assets();

    if ( ! $profile ) {
        echo mb_render_onboarding( 'client' );
    } elseif ( $profile->user_role === 'provider' ) {
        $pp = get_page_by_path( 'home-task-feed' );
        echo '<div class="bntm-notice">Your account is a Provider. <a href="' . esc_url( $pp ? get_permalink( $pp->ID ) : '#' ) . '">Go to your dashboard &rarr;</a></div>';
    } elseif ( $profile->user_role === 'admin' ) {
        $ap = get_page_by_path( 'dashboard-system-overview' );
        echo '<div class="bntm-notice">Admin account. <a href="' . esc_url( $ap ? get_permalink( $ap->ID ) : '#' ) . '">Go to Admin Dashboard &rarr;</a></div>';
    } else {
        mb_client_v2_assets();
        $tab        = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';
        $inbox_p    = get_page_by_path( 'inbox' );
        $inbox_u    = $inbox_p ? get_permalink( $inbox_p->ID ) : '#';
        $home_u     = '?tab=overview';
        $logout_u   = wp_logout_url( mb_find_page_url( '[mb_login]' ) );
        $avatar_url = $profile->pfp_url ? $profile->pfp_url : '';
        ?>
        <div class="mb-cv2">
            <nav class="topnav">
                <div class="topnav-inner">
                    <a href="<?php echo esc_url( $home_u ); ?>" class="nav-logo"><img src="<?php echo MB_LOGO_DARK; ?>" alt="MentorBe"></a>
                    <div class="nav-right">
                        <a href="?tab=post_task" class="post-task-btn <?php echo $tab === 'post_task' ? 'active' : ''; ?>"><?php echo mb_cv2_icon( 'plus', 16 ); ?> Post a task</a>
                        <div class="nav-icons-wrap">
                            <a class="nav-icon-btn <?php echo $tab === 'overview' ? 'active' : ''; ?>" title="Home" href="<?php echo esc_url( $home_u ); ?>"><?php echo mb_cv2_icon( 'home', 22 ); ?></a>
                            <button type="button" class="nav-icon-btn" id="mb-cv2-notif-btn" title="Notifications" onclick="mbCv2ToggleNotif(event)"><?php echo mb_cv2_icon( 'bell', 22 ); ?></button>
                            <a class="nav-icon-btn" title="Inbox" href="<?php echo esc_url( $inbox_u ); ?>"><?php echo mb_cv2_icon( 'msg', 22 ); ?></a>
                            <a class="nav-icon-btn <?php echo $tab === 'manage_tasks' ? 'active' : ''; ?>" title="Manage Tasks" href="?tab=manage_tasks"><?php echo mb_cv2_icon( 'clipboard', 22 ); ?></a>
                            <a class="nav-icon-btn <?php echo $tab === 'settings' ? 'active' : ''; ?>" title="Settings" href="?tab=settings"><?php echo mb_cv2_icon( 'settings', 22 ); ?></a>
                            <div class="notif-dd" id="mb-cv2-notif-dd" style="display:none;">
                                <div class="notif-dd-header"><?php echo mb_cv2_icon( 'bell', 28 ); ?><span>Notifications</span><button type="button" class="notif-dd-close" onclick="mbCv2CloseNotif()"><?php echo mb_cv2_icon( 'x', 16 ); ?></button></div>
                                <div class="notif-dd-list"><?php echo mb_render_notifications_list( $user_id ); ?></div>
                            </div>
                        </div>
                        <div class="nav-profile-wrap">
                            <button type="button" class="nav-avatar-btn" id="mb-cv2-profile-btn" onclick="mbCv2ToggleProfile(event)"><?php echo $avatar_url ? '<img src="' . esc_url( $avatar_url ) . '" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;">' : mb_cv2_icon( 'user', 20 ); ?></button>
                            <div class="profile-dd" id="mb-cv2-profile-dd" style="display:none;">
                                <div class="profile-dd-header">
                                    <div class="profile-dd-avatar"><?php echo $avatar_url ? '<img src="' . esc_url( $avatar_url ) . '" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;">' : mb_cv2_icon( 'user', 36 ); ?></div>
                                    <div><p class="profile-dd-name"><?php echo esc_html( $user->display_name ); ?></p><p class="profile-dd-email"><?php echo esc_html( $user->user_email ); ?></p></div>
                                </div>
                                <div class="profile-dd-divider"></div>
                                <div class="profile-dd-section">
                                    <button type="button" class="profile-dd-cta" onclick="alert('This would switch your account to a Provider.');">Become a Mentor</button>
                                </div>
                                <div class="profile-dd-section">
                                    <p class="profile-dd-label">Account</p>
                                    <a class="profile-dd-link" href="?tab=settings">Edit Profile</a>
                                    <a class="profile-dd-link" href="#">Language</a>
                                    <a class="profile-dd-link" href="<?php echo esc_url( mb_find_page_url( '[mb_help]' ) ); ?>">Help</a>
                                </div>
                                <div class="profile-dd-section">
                                    <a class="profile-dd-cta" style="display:flex;align-items:center;justify-content:center;text-decoration:none;" href="?tab=settings&stab=verification">Verify Account</a>
                                </div>
                                <div class="profile-dd-divider"></div>
                                <div class="profile-dd-logout-row"><button type="button" onclick="mbCv2RequestLogout('<?php echo esc_js( $logout_u ); ?>')" style="background:none;border:none;padding:0;color:var(--danger);font-weight:700;font-size:14px;display:flex;align-items:center;gap:8px;text-decoration:none;">Log out <?php echo mb_cv2_icon( 'logout', 18 ); ?></button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            <div class="settings-modal" id="mb-cv2-logout-modal" aria-hidden="true">
                <div class="settings-modal-card">
                    <h3>Log out?</h3>
                    <p>Are you sure you want to log out of MentorBe? You can sign back in anytime from the login page.</p>
                    <div class="settings-modal-actions">
                        <button type="button" class="outline-btn2" onclick="mbCv2CloseLogout()">Cancel</button>
                        <button type="button" class="save-btn" onclick="mbCv2ConfirmLogout()">Log out</button>
                    </div>
                </div>
            </div>
            <?php
            if      ( $tab === 'overview' )     { echo mb_client_overview_tab( $user->ID ); }
            elseif  ( $tab === 'post_task' )    { echo mb_client_post_task_tab( $user->ID ); }
            elseif  ( $tab === 'manage_tasks' ) { echo mb_client_manage_tasks_tab( $user->ID ); }
            elseif  ( $tab === 'profile' )      { echo mb_client_profile_tab_v2( $user->ID ); }
            elseif  ( $tab === 'settings' )     { echo mb_client_settings_tab_v2( $user->ID, $profile ); }
            ?>
        </div>
        <script>
        (function(){
            function closeAll(){ document.getElementById('mb-cv2-notif-dd').style.display='none'; document.getElementById('mb-cv2-profile-dd').style.display='none'; }
            window.mbCv2ToggleNotif = function(ev){ ev.stopPropagation(); var d=document.getElementById('mb-cv2-notif-dd'); var open=d.style.display==='block'; closeAll(); d.style.display = open?'none':'block'; };
            window.mbCv2ToggleProfile = function(ev){ ev.stopPropagation(); var d=document.getElementById('mb-cv2-profile-dd'); var open=d.style.display==='block'; closeAll(); d.style.display = open?'none':'block'; };
            window.mbCv2CloseNotif = function(){ document.getElementById('mb-cv2-notif-dd').style.display='none'; };
            window.mbCv2RequestLogout = function(url){
                var modal = document.getElementById('mb-cv2-logout-modal');
                if (!modal) { window.location.href = url; return; }
                modal.dataset.logoutUrl = url;
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            };
            window.mbCv2CloseLogout = function(){
                var modal = document.getElementById('mb-cv2-logout-modal');
                if (!modal) return;
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            };
            window.mbCv2ConfirmLogout = function(){
                var modal = document.getElementById('mb-cv2-logout-modal');
                if (!modal) return;
                var url = modal.dataset.logoutUrl || '<?php echo esc_js( $logout_u ); ?>';
                window.location.href = url;
            };
            document.getElementById('mb-cv2-logout-modal').addEventListener('click', function(e){
                if (e.target === this) mbCv2CloseLogout();
            });
            document.addEventListener('click', function(e){
                if(!e.target.closest('.nav-icons-wrap') && !e.target.closest('.nav-profile-wrap')) closeAll();
            });
        })();
        </script>
        <?php
    }

    $content = ob_get_clean();
    return $content;
}

function mb_client_settings_tab_v2( $user_id, $profile, $context = 'client' ) {
    $stab  = isset( $_GET['stab'] ) ? sanitize_text_field( $_GET['stab'] ) : 'personal';
    $items = [
        'personal'      => 'Personal Information',
        'security'      => 'Account Security',
        'billing'       => 'Billing & Payments',
        'notifications' => 'Notifications',
        'verification'  => 'Account Verification',
    ];
    if ( ! isset( $items[ $stab ] ) ) $stab = 'personal';

    ob_start(); ?>
    <div class="settings-shell">
        <aside class="settings-sidebar">
            <?php if ( $context !== 'provider' ): ?><div class="settings-title"><h1>Settings</h1></div><?php endif; ?>
            <nav class="settings-nav" id="mb-settings-nav">
                <?php foreach ( $items as $key => $label ): ?>
                    <a
                        class="settings-nav-item <?php echo $stab === $key ? 'active' : ''; ?>"
                        data-tab="<?php echo esc_attr( $key ); ?>"
                        href="?tab=settings&stab=<?php echo esc_attr( $key ); ?>"
                        style="text-decoration:none;display:block;"
                    ><?php echo esc_html( $label ); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="settings-logout">
                <button type="button" onclick="mbCv2RequestLogout('<?php echo esc_js( wp_logout_url( mb_find_page_url( '[mb_login]' ) ) ); ?>')" style="background:none;border:none;padding:0;display:flex;align-items:center;gap:8px;color:var(--danger);font-weight:700;font-size:16px;text-decoration:none;">
                    <?php echo mb_cv2_icon( 'logout', 18 ); ?> Log out
                </button>
            </div>
        </aside>
        <div class="settings-content">
            <?php
            if      ( $stab === 'personal' )      echo mb_settings_personal_panel_v2( $user_id, $profile );
            elseif  ( $stab === 'security' )      echo mb_settings_security_panel_v2( $user_id );
            elseif  ( $stab === 'billing' )       echo mb_settings_billing_panel_v2( $user_id );
            elseif  ( $stab === 'notifications' ) echo mb_settings_notifications_panel_v2( $user_id, $profile );
            elseif  ( $stab === 'verification' )  echo mb_settings_verification_panel_v2( $user_id, $profile );
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function mb_settings_personal_panel_v2( $user_id, $profile ) {
    $user = get_userdata( $user_id );
    $name_parts = preg_split( '/\s+/', trim( $user ? $user->display_name : '' ), 2 );
    $first_name = ! empty( $name_parts[0] ) ? $name_parts[0] : '';
    $last_name  = ! empty( $name_parts[1] ) ? $name_parts[1] : '';
    $headline   = (string) get_user_meta( $user_id, 'mb_profile_headline', true );
    $city       = (string) get_user_meta( $user_id, 'mb_profile_city', true );
    $region     = (string) get_user_meta( $user_id, 'mb_profile_region', true );
    $country    = (string) get_user_meta( $user_id, 'mb_profile_country', true );
    $phone      = (string) get_user_meta( $user_id, 'mb_phone_number', true );
    $cover_url  = (string) get_user_meta( $user_id, 'mb_cover_url', true );
    $cover_style = $cover_url ? 'style="background-image:url(' . esc_url( $cover_url ) . ');"' : '';
    ob_start();
    mb_ph_locations_script();
    ?>
    <div class="settings-stack">
        <div class="white-card settings-card">
            <div class="settings-cover <?php echo $cover_url ? 'has-image' : ''; ?>" <?php echo $cover_style; ?>>
                <button type="button" id="mb-set-cover-btn"><?php echo mb_cv2_icon( 'camera', 16 ); ?> Edit Cover Photo</button>
                <input type="file" id="mb-set-cover-inp" accept="image/*" style="display:none;">
            </div>
            <div class="settings-card-body">
                <div class="settings-avatar-wrap">
                    <div class="settings-avatar"><?php echo $profile->pfp_url ? '<img src="' . esc_url( $profile->pfp_url ) . '" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;">' : mb_cv2_icon( 'user', 60 ); ?></div>
                    <button type="button" id="mb-set-avatar-btn" title="Change Avatar"><?php echo mb_cv2_icon( 'camera', 18 ); ?></button>
                    <input type="file" id="mb-set-avatar-inp" accept="image/*" style="display:none;">
                </div>
                <form id="mb-set-personal-form">
                    <div class="settings-contact-grid">
                        <div class="mb18"><p class="lbl bold">First Name</p><input class="ic2" type="text" name="first_name" value="<?php echo esc_attr( $first_name ); ?>" placeholder="First Name"></div>
                        <div class="mb18"><p class="lbl bold">Last Name</p><input class="ic2" type="text" name="last_name" value="<?php echo esc_attr( $last_name ); ?>" placeholder="Last Name"></div>
                    </div>
                    <div class="mb18"><p class="lbl">Professional Headline <em>(This appears under your name in search results)</em></p><input class="ic2" type="text" name="headline" value="<?php echo esc_attr( $headline ); ?>" placeholder="e.g. Reliable Home Repair Specialist"></div>
                    <div class="mb18"><p class="lbl bold">About Me</p><textarea class="ic2" name="bio" rows="5" placeholder="Tell people a little about you..."><?php echo esc_textarea( $profile->bio ); ?></textarea><p class="char-count" data-for="mb-set-bio-count">0 / 500 characters used</p></div>
                    <div class="mb18">
                        <p class="lbl bold">Location Information</p>
                        <div class="settings-grid3">
                            <div>
                                <p class="lbl">Region</p>
                                <select class="ic2" name="region" id="mb-set-region" data-current="<?php echo esc_attr( $region ); ?>">
                                    <option value="">Select Region</option>
                                </select>
                            </div>
                            <div>
                                <p class="lbl">City</p>
                                <select class="ic2" name="city" id="mb-set-city" data-current="<?php echo esc_attr( $city ); ?>">
                                    <option value="">Select Region first</option>
                                </select>
                            </div>
                            <div><p class="lbl">Country</p><input class="ic2" type="text" name="country" value="Philippines" readonly></div>
                        </div>
                    </div>
                    <div class="mb24">
                        <p class="lbl bold">Contact Info</p>
                        <button type="button" class="outline-btn" id="mb-set-contact-btn"><?php echo mb_cv2_icon( 'camera', 16 ); ?> Edit contact info</button>
                    </div>
                    <div class="right"><button type="submit" class="save-btn">Save</button></div>
                </form>
                <div id="mb-set-personal-msg"></div>
            </div>
        </div>
        <div class="white-card settings-card pad settings-contact-panel" id="mb-settings-contact-panel">
            <h2>Edit Contact Info</h2>
            <form id="mb-set-contact-form">
                <div class="mb18">
                    <p class="lbl">Email Address</p>
                    <input class="ic2" type="email" name="email" value="<?php echo esc_attr( $user ? $user->user_email : '' ); ?>" placeholder="Email Address">
                    <p class="hint-italic">Note: Changing your email will require you to verify the new address.</p>
                </div>
                <div class="mb24">
                    <p class="lbl">Phone Number</p>
                    <input class="ic2" type="text" name="phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="Enter Phone Number">
                </div>
                <div class="settings-contact-actions">
                    <button type="button" class="outline-btn2" id="mb-set-contact-back">Back</button>
                    <button type="submit" class="save-btn">Save</button>
                </div>
            </form>
            <div id="mb-set-contact-msg"></div>
        </div>
    </div>
    <script>
    (function(){
        var coverBtn = document.getElementById('mb-set-cover-btn');
        var coverInp = document.getElementById('mb-set-cover-inp');
        var avatarBtn = document.getElementById('mb-set-avatar-btn');
        var avatarInp = document.getElementById('mb-set-avatar-inp');
        var contactBtn = document.getElementById('mb-set-contact-btn');
        var contactPanel = document.getElementById('mb-settings-contact-panel');
        var contactBack = document.getElementById('mb-set-contact-back');
        var personalForm = document.getElementById('mb-set-personal-form');
        var contactForm = document.getElementById('mb-set-contact-form');
        var bioField = personalForm ? personalForm.querySelector('textarea[name="bio"]') : null;
        var bioCount = document.querySelector('[data-for="mb-set-bio-count"]');

        function syncBioCount() {
            if (!bioField || !bioCount) return;
            bioCount.textContent = bioField.value.length + ' / 500 characters used';
        }

        if (bioField) {
            syncBioCount();
            bioField.addEventListener('input', syncBioCount);
        }

        var regionSel = document.getElementById('mb-set-region');
        var citySel = document.getElementById('mb-set-city');
        if (regionSel && citySel && window.MB_PH_LOCATIONS) {
            var currentRegion = regionSel.dataset.current || '';
            var currentCity = citySel.dataset.current || '';
            Object.keys(window.MB_PH_LOCATIONS).forEach(function(region){
                var opt = document.createElement('option');
                opt.value = region;
                opt.textContent = region;
                regionSel.appendChild(opt);
            });
            // If previously-saved region isn't in our dataset (e.g. old free-text
            // data), keep it selectable so the user's existing value isn't lost.
            if (currentRegion && !window.MB_PH_LOCATIONS[currentRegion]) {
                var extraRegion = document.createElement('option');
                extraRegion.value = currentRegion;
                extraRegion.textContent = currentRegion;
                regionSel.appendChild(extraRegion);
            }
            function populateCities(region, selectedCity) {
                var cities = window.MB_PH_LOCATIONS[region] || [];
                citySel.innerHTML = '';
                if (!region) {
                    citySel.disabled = true;
                    var ph = document.createElement('option');
                    ph.value = '';
                    ph.textContent = 'Select Region first';
                    citySel.appendChild(ph);
                    return;
                }
                citySel.disabled = false;
                var def = document.createElement('option');
                def.value = '';
                def.textContent = 'Select City';
                citySel.appendChild(def);
                cities.forEach(function(city){
                    var opt = document.createElement('option');
                    opt.value = city;
                    opt.textContent = city;
                    citySel.appendChild(opt);
                });
                if (selectedCity && cities.indexOf(selectedCity) === -1) {
                    var extraCity = document.createElement('option');
                    extraCity.value = selectedCity;
                    extraCity.textContent = selectedCity;
                    citySel.appendChild(extraCity);
                }
                if (selectedCity) citySel.value = selectedCity;
            }
            if (currentRegion) regionSel.value = currentRegion;
            populateCities(currentRegion, currentCity);
            regionSel.addEventListener('change', function(){ populateCities(this.value, ''); });
        }

        if (coverBtn && coverInp) {
            coverBtn.addEventListener('click', function(){ coverInp.click(); });
            coverInp.addEventListener('change', function(){
                if (!this.files[0]) return;
                var fd = new FormData();
                fd.append('action', 'mb_upload_cover');
                fd.append('cover', this.files[0]);
                fd.append('nonce', mbNonce);
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(j){
                    bntmMbToast('mb-set-personal-msg', j.data.message, j.success);
                    if (j.success) setTimeout(function(){ location.reload(); }, 900);
                });
            });
        }

        if (avatarBtn && avatarInp) {
            avatarBtn.addEventListener('click', function(){ avatarInp.click(); });
            avatarInp.addEventListener('change', function(){
                if (!this.files[0]) return;
                var fd = new FormData();
                fd.append('action','mb_upload_avatar');
                fd.append('avatar', this.files[0]);
                fd.append('nonce', mbNonce);
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(j){
                    bntmMbToast('mb-set-personal-msg', j.data.message, j.success);
                    if (j.success) setTimeout(function(){ location.reload(); }, 900);
                });
            });
        }

        if (contactBtn && contactPanel) {
            contactBtn.addEventListener('click', function(){
                contactPanel.classList.add('open');
                contactPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
        if (contactBack && contactPanel) {
            contactBack.addEventListener('click', function(){
                contactPanel.classList.remove('open');
            });
        }

        if (personalForm) {
            personalForm.addEventListener('submit', function(e){
                e.preventDefault();
                var fd = new FormData(this);
                fd.append('action','mb_update_profile');
                fd.append('nonce', mbNonce);
                var btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = 'Saving...';
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(j){
                    bntmMbToast('mb-set-personal-msg', j.data.message, j.success);
                    btn.disabled = false;
                    btn.textContent = 'Save';
                    if (j.success) setTimeout(function(){ location.reload(); }, 700);
                });
            });
        }

        if (contactForm) {
            contactForm.addEventListener('submit', function(e){
                e.preventDefault();
                var fd = new FormData(this);
                fd.append('action','mb_update_account_info');
                fd.append('nonce', mbNonce);
                var btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = 'Saving...';
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(j){
                    bntmMbToast('mb-set-contact-msg', j.data.message, j.success);
                    btn.disabled = false;
                    btn.textContent = 'Save';
                    if (j.success) setTimeout(function(){ location.reload(); }, 700);
                });
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_settings_security_panel_v2( $user_id ) {
    ob_start(); ?>
    <div class="settings-stack">
        <div class="white-card settings-card pad">
            <h2>Account Security</h2><h3>Change Password</h3>
            <form id="mb-set-pw-form">
                <div class="mb14"><p class="lbl">Current Password</p><input class="ic2" type="password" name="current_password" required></div>
                <div class="mb14"><p class="lbl">New Password</p><input class="ic2" type="password" name="new_password" minlength="8" required><p class="hint-italic">Must be at least 8 characters, including a number and a special character.</p></div>
                <div class="mb14"><p class="lbl">Confirm Password</p><input class="ic2" type="password" name="confirm_password" minlength="8" required></div>
                <div class="right"><button type="submit" class="save-btn">Update Password</button></div>
            </form>
            <div id="mb-set-pw-msg"></div>
        </div>
        <div class="danger-card">
            <h3>Danger Zone</h3>
            <p class="danger-sub">Once you delete your account, there is no going back. Please be certain.</p>
            <div class="danger-row"><button type="button" class="deactivate-btn" id="mb-deactivate-account-btn">Deactivate Account</button><span>(Temporarily hide your profile)</span></div>
            <div class="danger-row"><button type="button" class="delete-btn" id="mb-delete-account-btn">Delete Account</button><span>(Permanently delete your data)</span></div>
        </div>
    </div>
    <script>
    (function(){
        var form = document.getElementById('mb-set-pw-form');
        if (form) {
            form.addEventListener('submit', function(e){
                e.preventDefault();
                var newPw = this.querySelector('input[name="new_password"]').value;
                var confirmPw = this.querySelector('input[name="confirm_password"]').value;
                if (newPw !== confirmPw) {
                    bntmMbToast('mb-set-pw-msg', 'New password and confirmation do not match.', false);
                    return;
                }
                var fd = new FormData(this); fd.append('action','mb_change_password'); fd.append('nonce', mbNonce);
                var btn = this.querySelector('button[type="submit"]'); btn.disabled = true;
                btn.textContent = 'Updating...';
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(j){
                    bntmMbToast('mb-set-pw-msg', j.data.message, j.success);
                    btn.disabled = false;
                    btn.textContent = 'Update Password';
                    if (j.success) e.target.reset();
                });
            });
        }
        var deactivateBtn = document.getElementById('mb-deactivate-account-btn');
        var deleteBtn = document.getElementById('mb-delete-account-btn');
        if (deactivateBtn) {
            deactivateBtn.addEventListener('click', function(){
                bntmMbToast('mb-set-pw-msg', 'Deactivation requests are coming soon.', false);
            });
        }
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function(){
                bntmMbToast('mb-set-pw-msg', 'Account deletion is handled by support for now.', false);
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_settings_billing_panel_v2( $user_id ) {
    $billing_name    = (string) get_user_meta( $user_id, 'mb_billing_name', true );
    $billing_street  = (string) get_user_meta( $user_id, 'mb_billing_street', true );
    $billing_city    = (string) get_user_meta( $user_id, 'mb_billing_city', true );
    $billing_prov    = (string) get_user_meta( $user_id, 'mb_billing_province', true );
    $billing_zip     = (string) get_user_meta( $user_id, 'mb_billing_zip', true );
    $billing_country = (string) get_user_meta( $user_id, 'mb_billing_country', true );
    $gcash_name      = (string) get_user_meta( $user_id, 'mb_gcash_name', true );
    $gcash_number    = (string) get_user_meta( $user_id, 'mb_gcash_number', true );
    $billing_linked   = $gcash_name || $gcash_number;
    ob_start(); ?>
    <div class="settings-stack">
        <div class="white-card settings-card pad">
            <h2>Billing &amp; Payments</h2>
            <div class="billing-link-row">
                <div class="billing-meta">
                    <div class="billing-empty-state">
                        <?php if ( $billing_linked ): ?>
                            <span class="settings-pill-status">Status: <strong>Verified &amp; Linked</strong></span>
                            <p class="line-em"><em>Account Name:</em> <?php echo esc_html( $gcash_name ?: 'Not linked' ); ?></p>
                            <p class="line-em"><em>Mobile Number:</em> <?php echo esc_html( $gcash_number ?: 'Not linked' ); ?></p>
                        <?php else: ?>
                            <span class="settings-pill-status">Status: <strong>Unlinked</strong></span>
                            <p>No payment method is linked yet. You can add one below when you are ready.</p>
                        <?php endif; ?>
                    </div>
                    <p class="settings-help-note">Unlinking your GCash will require you to link a new payment method the next time you post a task.</p>
                </div>
                <?php if ( $billing_linked ): ?>
                    <button type="button" class="black-btn settings-billing-btn" id="mb-unlink-billing-btn">Unlink Account</button>
                <?php else: ?>
                    <button type="button" class="black-btn settings-billing-btn" id="mb-link-billing-btn">Link Account</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="white-card settings-card pad">
            <h3>Billing Details</h3>
            <form id="mb-billing-form">
                <div class="mb14"><p class="lbl">Full Name</p><input class="ic2" name="billing_name" value="<?php echo esc_attr( $billing_name ); ?>" placeholder="First Name, Last Name"></div>
                <div class="mb14"><p class="lbl">Street Address</p><input class="ic2" name="billing_street" value="<?php echo esc_attr( $billing_street ); ?>" placeholder="Street Address"></div>
                <div class="settings-grid3">
                    <div><p class="lbl">City</p><input class="ic2" name="billing_city" value="<?php echo esc_attr( $billing_city ); ?>" placeholder="City"></div>
                    <div><p class="lbl">Province</p><input class="ic2" name="billing_province" value="<?php echo esc_attr( $billing_prov ); ?>" placeholder="Province"></div>
                    <div><p class="lbl">ZIP</p><input class="ic2" name="billing_zip" value="<?php echo esc_attr( $billing_zip ); ?>" placeholder="ZIP"></div>
                </div>
                <div class="mb14" style="margin-top:16px;"><p class="lbl">Country</p><input class="ic2" name="billing_country" value="<?php echo esc_attr( $billing_country ?: 'Philippines' ); ?>" placeholder="Country"></div>
                <div class="right"><button type="submit" class="save-btn">Update Billing Details</button></div>
            </form>
            <div id="mb-billing-msg"></div>
        </div>
        <div class="white-card settings-card pad">
            <h3>Transaction History &amp; E-Receipts</h3>
            <div class="billing-empty-state">No transactions yet. Your completed payments will appear here once billing is used.</div>
            <div class="mt16"><button type="button" class="teal-link-btn" id="mb-view-transactions-btn">View All Transactions <?php echo mb_cv2_icon( 'chevright', 18 ); ?></button></div>
        </div>
    </div>
    <script>
    (function(){
        var billingForm = document.getElementById('mb-billing-form');
        if (billingForm) {
            billingForm.addEventListener('submit', function(e){
                e.preventDefault();
                var fd = new FormData(this);
                fd.append('action','mb_save_billing_details');
                fd.append('nonce', mbNonce);
                var btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = 'Saving...';
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(j){
                    bntmMbToast('mb-billing-msg', j.data.message, j.success);
                    btn.disabled = false;
                    btn.textContent = 'Update Billing Details';
                });
            });
        }
        var unlinkBtn = document.getElementById('mb-unlink-billing-btn');
        if (unlinkBtn) {
            unlinkBtn.addEventListener('click', function(){
                if (!confirm('Unlink your billing account?')) return;
                var fd = new FormData();
                fd.append('action', 'mb_unlink_billing_account');
                fd.append('nonce', mbNonce);
                this.disabled = true;
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(j){
                    bntmMbToast('mb-billing-msg', j.data.message, j.success);
                    if (j.success) setTimeout(function(){ location.reload(); }, 700);
                    else unlinkBtn.disabled = false;
                });
            });
        }
        var linkBtn = document.getElementById('mb-link-billing-btn');
        if (linkBtn) {
            linkBtn.addEventListener('click', function(){
                window.location.href = '<?php echo esc_js( get_page_by_path( 'checkout-billing' ) ? get_permalink( get_page_by_path( 'checkout-billing' )->ID ) : '#' ); ?>';
            });
        }
        var txBtn = document.getElementById('mb-view-transactions-btn');
        if (txBtn) {
            txBtn.addEventListener('click', function(){ bntmMbToast('mb-billing-msg', 'Full transaction history is coming soon.', false); });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_settings_notifications_panel_v2( $user_id, $profile ) {
    $defaults = [
        'Task & Application Updates' => [
            'A mentor applies to your task',
            'A mentor submits work for review',
            'Task status changes (e.g., live, disputed)',
        ],
        'Messages & Communication' => [
            'You receive a new direct message',
            'Unread message summary (Daily)',
        ],
        'Billing & Escrow' => [
            'Escrow funds are released to a mentor',
            'Escrow refunds are credited to GCash',
        ],
        'Platform & Marketing' => [
            'Tips on how to hire the best mentors',
            'Platform news and updates',
        ],
    ];
    $stored = json_decode( (string) get_user_meta( $user_id, 'mb_notification_prefs', true ), true );
    if ( ! is_array( $stored ) ) {
        $stored = [];
    }
    ob_start(); ?>
    <div class="white-card settings-card pad wide-inner">
        <h2>Notifications</h2>
        <div class="notif-head"><span>Alert Type</span><span class="center">Email</span><span class="center">Push</span></div>
        <?php foreach ( $defaults as $group => $keys ): ?>
            <p class="notif-group-label"><?php echo esc_html( $group ); ?></p>
            <?php foreach ( $keys as $key ): ?>
                <?php $pair = isset( $stored[ $key ] ) && is_array( $stored[ $key ] ) ? array_pad( array_values( $stored[ $key ] ), 2, true ) : [ true, true ]; ?>
                <div class="notif-row" data-notif-key="<?php echo esc_attr( $key ); ?>">
                    <p><?php echo esc_html( $key ); ?></p>
                    <div class="notif-cell"><button type="button" class="notif-toggle <?php echo $pair[0] ? 'on' : ''; ?>" data-col="0" data-key="<?php echo esc_attr( $key ); ?>"><?php echo $pair[0] ? mb_cv2_icon( 'check', 16 ) : ''; ?></button></div>
                    <div class="notif-cell"><button type="button" class="notif-toggle <?php echo $pair[1] ? 'on' : ''; ?>" data-col="1" data-key="<?php echo esc_attr( $key ); ?>"><?php echo $pair[1] ? mb_cv2_icon( 'check', 16 ) : ''; ?></button></div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <div class="mt20"><button type="button" class="save-btn lg" id="mb-save-notif-btn">Save Preferences</button></div>
        <div id="mb-set-notif-msg"></div>
    </div>
    <script>
    (function(){
        var prefs = <?php echo wp_json_encode( $stored ); ?>;
        function setToggle(btn, on) {
            btn.classList.toggle('on', !!on);
            btn.textContent = on ? '✓' : '';
        }
        document.querySelectorAll('.notif-toggle').forEach(function(btn){
            btn.addEventListener('click', function(){
                var key = this.dataset.key;
                var col = parseInt(this.dataset.col, 10);
                var pair = prefs[key] || [false, false];
                pair[col] = !pair[col];
                prefs[key] = pair;
                setToggle(this, pair[col]);
            });
        });
        var master = document.getElementById('mb-set-notif-master');
        var saveBtn = document.getElementById('mb-save-notif-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', function(){
                var fd = new FormData();
                fd.append('action','mb_update_notification_prefs');
                fd.append('notif_email', '1');
                fd.append('prefs', JSON.stringify(prefs));
                fd.append('nonce', mbNonce);
                this.disabled = true;
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(j){
                    bntmMbToast('mb-set-notif-msg', j.data.message, j.success);
                    saveBtn.disabled = false;
                });
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_settings_verification_panel_v2( $user_id, $profile ) {
    $verified = $profile->verification_status === 'verified';
    $id_type  = (string) get_user_meta( $user_id, 'mb_verification_id_type', true );
    $id_name  = (string) get_user_meta( $user_id, 'mb_verification_id_name', true );
    $selfie_name = (string) get_user_meta( $user_id, 'mb_verification_selfie_name', true );
    ob_start(); ?>
    <div class="settings-stack">
        <div class="white-card settings-card pad">
            <h2>Account Verification</h2>
            <div class="verify-status-row"><p>Verification Status:</p><span class="<?php echo $verified ? 'bntm-status-badge bntm-status-completed' : 'status-badge-red'; ?>"><?php echo esc_html( ucfirst( $profile->verification_status ) ); ?></span></div>
            <p class="hint-italic mb20">You currently cannot post tasks or fund escrow until verification is reviewed.</p>
            <h3>Step 1: Select ID Type</h3>
            <div class="select-wrap max624">
                <select class="id-select" id="mb-verify-id-type">
                    <?php
                    $id_types = [ 'Philippine National ID (PhilSys)', 'Passport', 'Driver\'s License', 'UMID', 'Postal ID' ];
                    foreach ( $id_types as $type ) {
                        printf( '<option %s>%s</option>', selected( $id_type, $type, false ), esc_html( $type ) );
                    }
                    ?>
                </select>
                <?php echo mb_cv2_icon( 'chevdown', 20 ); ?>
            </div>
            <p class="hint-italic mt8">(Other options: Passport, Driver's License, UMID, Postal ID)</p>
            <p class="hint-italic">We accept valid Philippine government-issued IDs.</p>
        </div>
        <div class="white-card settings-card pad">
            <h3>Step 2: Upload ID Images</h3>
            <p class="hint-italic mb20">Please ensure the document is clear, well-lit, and all text is readable.</p>
            <?php if ( $id_name ): ?>
                <div class="settings-file-chip" id="mb-verify-id-chip"><?php echo mb_cv2_icon( 'checkcircle', 16 ); ?><span><?php echo esc_html( $id_name ); ?></span></div>
            <?php endif; ?>
            <input type="file" id="mb-verify-id-file" accept=".jpg,.jpeg,.png,.pdf" style="display:none;">
            <button type="button" class="upload-btn" id="mb-verify-id-btn"><?php echo mb_cv2_icon( 'upload', 22 ); ?> Upload File</button>
            <p class="hint-italic mt8"><strong>JPG, PNG, or PDF only.</strong> Max size: <strong>5MB</strong> per file.</p>
        </div>
        <div class="white-card settings-card pad">
            <h3>Step 3: Liveness Verification (Selfie)</h3>
            <p class="hint-italic mb20">Take a clear selfie holding your ID next to your face to prevent identity theft.</p>
            <?php if ( $selfie_name ): ?>
                <div class="settings-file-chip" id="mb-verify-selfie-chip"><?php echo mb_cv2_icon( 'checkcircle', 16 ); ?><span><?php echo esc_html( $selfie_name ); ?></span></div>
            <?php endif; ?>
            <div class="flex-gap">
                <button type="button" class="upload-btn" id="mb-verify-selfie-camera"><?php echo mb_cv2_icon( 'camera', 22 ); ?> Take a Selfie</button>
                <button type="button" class="upload-btn" id="mb-verify-selfie-file-btn"><?php echo mb_cv2_icon( 'upload', 22 ); ?> Upload File</button>
            </div>
            <input type="file" id="mb-verify-selfie-file" accept="image/*" style="display:none;">
            <p class="hint-italic mt8"><strong>JPG, PNG, or PDF only.</strong> Max size: <strong>5MB</strong> per file.</p>
        </div>
        <div class="white-card settings-card pad">
            <h3>Data Privacy Notice</h3>
            <p class="privacy-text">Your privacy is our priority. MentorBe complies strictly with the Data Privacy Act of 2012 (R.A. 10173). Your documents are encrypted end-to-end, securely stored, and only used for Know Your Customer (KYC) and anti-fraud purposes. They will never be shared with Mentors or third-party marketers.</p>
        </div>
        <div class="right gap">
            <button type="button" class="cancel-btn2" id="mb-verify-cancel-btn">Cancel</button>
            <button type="button" class="submit-verify-btn" id="mb-verify-submit-btn">Submit for Verification</button>
        </div>
        <div id="mb-verify-msg"></div>
    </div>
    <script>
    (function(){
        var idBtn = document.getElementById('mb-verify-id-btn');
        var idFile = document.getElementById('mb-verify-id-file');
        var selfieBtn = document.getElementById('mb-verify-selfie-file-btn');
        var selfieCamera = document.getElementById('mb-verify-selfie-camera');
        var selfieFile = document.getElementById('mb-verify-selfie-file');
        var submitBtn = document.getElementById('mb-verify-submit-btn');
        var cancelBtn = document.getElementById('mb-verify-cancel-btn');
        if (idBtn && idFile) idBtn.addEventListener('click', function(){ idFile.click(); });
        if (selfieBtn && selfieFile) selfieBtn.addEventListener('click', function(){ selfieFile.click(); });
        if (selfieCamera) selfieCamera.addEventListener('click', function(){ selfieFile.click(); });
        if (cancelBtn) cancelBtn.addEventListener('click', function(){ window.location.href = '?tab=settings&stab=personal'; });
        if (submitBtn) {
            submitBtn.addEventListener('click', function(){
                var fd = new FormData();
                fd.append('action', 'mb_submit_verification');
                fd.append('id_type', document.getElementById('mb-verify-id-type').value || '');
                fd.append('nonce', mbNonce);
                if (idFile && idFile.files && idFile.files[0]) fd.append('id_file', idFile.files[0]);
                if (selfieFile && selfieFile.files && selfieFile.files[0]) fd.append('selfie_file', selfieFile.files[0]);
                this.disabled = true;
                this.textContent = 'Submitting...';
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(j){
                    bntmMbToast('mb-verify-msg', j.data.message, j.success);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit for Verification';
                    if (j.success) setTimeout(function(){ location.reload(); }, 900);
                });
            });
        }
        function showFileName(input, chipId, label) {
            if (!input) return;
            input.addEventListener('change', function(){
                var chip = document.getElementById(chipId);
                if (!this.files || !this.files[0]) return;
                if (chip) {
                    chip.innerHTML = '<span>✓</span><span>' + this.files[0].name.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</span>';
                } else {
                    var c = document.createElement('div');
                    c.className = 'settings-file-chip';
                    c.id = chipId;
                    c.innerHTML = '<span>✓</span><span>' + this.files[0].name.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</span>';
                    input.parentNode.insertBefore(c, input);
                }
            });
        }
        showFileName(idFile, 'mb-verify-id-chip', 'ID');
        showFileName(selfieFile, 'mb-verify-selfie-chip', 'Selfie');
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_client_profile_tab_v2( $user_id ) {
    global $wpdb;
    $user    = get_userdata( $user_id );
    $profile = mb_get_profile( $user_id );
    $is_prov = $profile && $profile->user_role === 'provider';

    $posted    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE client_id=%d", $user_id ) );
    $completed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE client_id=%d AND status='completed'", $user_id ) );
    $hire_rate = $posted ? round( ( $completed / $posted ) * 100 ) : 0;
    $spent     = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(budget),0) FROM {$wpdb->prefix}mb_tasks WHERE client_id=%d AND status='completed'", $user_id ) );
    $member_since = date( 'F Y', strtotime( $user->user_registered ) );

    $my_skills = $is_prov ? $wpdb->get_results( $wpdb->prepare(
        "SELECT s.id, s.name FROM {$wpdb->prefix}mb_provider_skills ps INNER JOIN {$wpdb->prefix}mb_skills s ON ps.skill_id=s.id WHERE ps.user_id=%d AND ps.status='active'", $user_id
    ) ) : [];
    $init_skills = array_map( function( $s ) { return [ 'id' => $s->id, 'name' => $s->name ]; }, $my_skills );

    ob_start(); ?>
    <div class="teal-page">
        <div class="cp-grid">
            <div>
                <div class="white-card no-max cp-main">
                    <div class="cp-cover"></div>
                    <div class="cp-body">
                        <div class="cp-avatar-wrap">
                            <div class="cp-avatar"><?php echo $profile->pfp_url ? '<img src="' . esc_url( $profile->pfp_url ) . '" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;">' : mb_cv2_icon( 'user', 56 ); ?></div>
                            <button type="button" id="mb-cp-avatar-btn"><?php echo mb_cv2_icon( 'camera', 17 ); ?></button>
                            <input type="file" id="mb-cp-avatar-inp" accept="image/*" style="display:none;">
                        </div>
                        <div class="cp-name-row"><h2><?php echo esc_html( $user->display_name ); ?></h2><?php if ( $profile->verification_status === 'verified' ): echo mb_cv2_icon( 'checkcircle', 22, 'verified-ic' ); endif; ?></div>
                        <p class="cp-tagline"><?php echo esc_html( ucfirst( $profile->user_role ) ); ?> on MentorBe</p>
                        <h3>About Me</h3>
                        <form id="mb-cp-form">
                            <textarea name="bio" class="ic2" rows="5" style="width:100%;margin-bottom:12px;"><?php echo esc_textarea( $profile->bio ); ?></textarea>
                            <div style="margin-bottom:12px;"><p class="lbl bold" style="margin-bottom:6px;">Full Name</p><input type="text" name="full_name" class="ic2" value="<?php echo esc_attr( $user->display_name ); ?>"></div>
                            <div class="right"><button type="submit" class="save-btn">Save</button></div>
                        </form>
                        <div id="mb-cp-msg"></div>
                    </div>
                </div>

                <?php if ( $is_prov ): ?>
                <div class="white-card no-max">
                    <h2 class="cp-reviews-h">My Skills</h2>
                    <div class="bntm-skill-tag-input" id="cp-skill-wrap" style="border-color:var(--teal-dark);"><input type="text" placeholder="Type to search or add skills..."></div>
                    <input type="hidden" id="cp-skills-hidden">
                    <div class="right" style="margin-top:12px;"><button type="button" id="mb-cp-save-skills" class="save-btn">Save Skills</button></div>
                    <div id="cp-skills-msg"></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="cp-side">
                <div class="white-card no-max">
                    <p class="side-title"><?php echo $is_prov ? 'Provider' : 'Client'; ?> Statistics</p>
                    <ul class="side-list">
                        <?php if ( $is_prov ): ?>
                            <li>Verification Status: <strong><?php echo esc_html( ucfirst( $profile->verification_status ) ); ?></strong></li>
                            <li>Member Since: <strong><?php echo esc_html( $member_since ); ?></strong></li>
                        <?php else: ?>
                            <li>Total Tasks Posted: <strong><?php echo number_format( $posted ); ?></strong></li>
                            <li>Completion Rate: <strong><?php echo $hire_rate; ?>%</strong></li>
                            <li>Total Spent (Completed): <strong><?php echo mb_price( $spent ); ?></strong></li>
                            <li>Member Since: <strong><?php echo esc_html( $member_since ); ?></strong></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="white-card no-max">
                    <p class="side-title">Verifications</p>
                    <div class="verify-line"><?php echo mb_cv2_icon( 'check', 14, 'check-ic' ); ?><span>Email Address: <strong>Verified</strong></span></div>
                    <div class="verify-line"><?php echo mb_cv2_icon( 'check', 14, 'check-ic' ); ?><span>Identity (KYC): <strong><?php echo esc_html( ucfirst( $profile->verification_status ) ); ?></strong></span></div>
                </div>
                <?php if ( $profile->verification_status !== 'verified' ): ?>
                <div class="white-card no-max">
                    <p class="side-title">Verify Your Account</p>
                    <a class="verify-cta" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;" href="?tab=settings&stab=verification">Verify <?php echo mb_cv2_icon( 'checkcircle', 18 ); ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    (function(){
        document.getElementById('mb-cp-avatar-btn').addEventListener('click', function(){ document.getElementById('mb-cp-avatar-inp').click(); });
        document.getElementById('mb-cp-avatar-inp').addEventListener('change', function(){
            if (!this.files[0]) return;
            var fd = new FormData(); fd.append('action','mb_upload_avatar'); fd.append('avatar', this.files[0]); fd.append('nonce', mbNonce);
            fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(j){
                bntmMbToast('mb-cp-msg', j.data.message, j.success); if (j.success) setTimeout(function(){ location.reload(); }, 900);
            });
        });
        document.getElementById('mb-cp-form').addEventListener('submit', function(e){
            e.preventDefault();
            var fd = new FormData(this); fd.append('action','mb_update_profile'); fd.append('nonce', mbNonce);
            var btn = this.querySelector('button'); btn.disabled = true;
            fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(j){
                bntmMbToast('mb-cp-msg', j.data.message, j.success); btn.disabled = false;
            });
        });
        <?php if ( $is_prov ): ?>
        bntmMbSkillTagInput('cp-skill-wrap', 'cp-skills-hidden', <?php echo wp_json_encode( $init_skills ); ?>);
        document.getElementById('mb-cp-save-skills').addEventListener('click', function(){
            var fd = new FormData(); fd.append('action','mb_add_skill'); fd.append('skills', document.getElementById('cp-skills-hidden').value); fd.append('nonce', mbNonce);
            this.disabled = true; var btn = this;
            fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(j){
                bntmMbToast('cp-skills-msg', j.data.message, j.success); btn.disabled = false;
            });
        });
        <?php endif; ?>
    })();
    </script>
    <?php
    return ob_get_clean();
}

/* ==========================================================================
   G. PROVIDER DASHBOARD  [mb_provider_dashboard]
========================================================================== */

function bntm_shortcode_mb_provider() {
    if ( ! is_user_logged_in() ) {
        $login_url = mb_find_page_url( '[mb_login]' );
        return '<div class="bntm-notice">Please <a href="' . esc_url( $login_url ) . '">log in</a> to access your dashboard.</div>';
    }

    $user    = wp_get_current_user();
    $profile = mb_get_profile( $user->ID );
    $onboarding_url = bntm_mb_onboarding_redirect_url( $user->ID, $profile );
    if ( $onboarding_url ) {
        wp_safe_redirect( $onboarding_url );
        exit;
    }

    ob_start();
    mb_dashboard_assets();
    mb_client_v2_assets();

    if ( ! $profile ) {
        echo mb_render_onboarding( 'provider' );
    } elseif ( $profile->user_role === 'client' ) {
        $cp = get_page_by_path( 'clients-dashboard' );
        echo '<div class="bntm-notice">Your account is a Client. <a href="' . esc_url( $cp ? get_permalink( $cp->ID ) : '#' ) . '">Go to your dashboard &rarr;</a></div>';
    } elseif ( $profile->user_role === 'admin' ) {
        $ap = get_page_by_path( 'dashboardsystem-overview' );
        echo '<div class="bntm-notice">Admin account. <a href="' . esc_url( $ap ? get_permalink( $ap->ID ) : '#' ) . '">Go to Admin Dashboard &rarr;</a></div>';
    } else {
        global $wpdb;
        $section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : ( isset( $_GET['tab'] ) && sanitize_text_field( $_GET['tab'] ) === 'settings' ? 'settings' : 'feed' );
        $inbox_p = get_page_by_path( 'inbox' );
        $inbox_u = $inbox_p ? get_permalink( $inbox_p->ID ) : '#';
        $earn_p  = get_page_by_path( 'earnings' );
        $earn_u  = $earn_p ? get_permalink( $earn_p->ID ) : '#';
        $logout_u = wp_logout_url( mb_find_page_url( '[mb_login]' ) );
        $avatar  = $profile->pfp_url ? $profile->pfp_url : '';
        $first   = strtok( $user->display_name, ' ' );
        $active_jobs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE provider_id=%d AND status='in_progress' ORDER BY updated_at DESC", $user->ID ) );
        $completed_tasks = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE provider_id=%d AND status='completed'", $user->ID ) );
        $gross_completed = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(budget),0) FROM {$wpdb->prefix}mb_tasks WHERE provider_id=%d AND status='completed'", $user->ID ) );
        $estimated_fee   = $gross_completed * 0.05;
        $rating_summary = mb_get_review_summary( $user->ID );
        $recent_activity  = $wpdb->get_results( $wpdb->prepare( "SELECT id, client_id, title, status, updated_at, budget FROM {$wpdb->prefix}mb_tasks WHERE provider_id=%d ORDER BY updated_at DESC LIMIT 4", $user->ID ) );
        $section_titles = [
            'feed'     => 'Task Feed',
            'jobs'     => 'Active Jobs',
            'earnings' => 'Earnings & Reviews',
            'settings' => 'Settings',
            'profile'  => 'Profile & Settings',
            'inbox'    => 'Inbox',
        ];
        $header_title = isset( $section_titles[ $section ] ) ? $section_titles[ $section ] : 'Task Feed';
        ?>
        <style>
        .mb-pro-shell{background:#f1fffe;min-height:100vh;display:flex;overflow:hidden;}
        .mb-pro-shell *{box-sizing:border-box;font-family:'Montserrat',sans-serif;}
        .mb-pro-sidebar{width:220px;min-width:220px;background:#16796f;display:flex;flex-direction:column;height:100vh;position:sticky;top:0;color:#fff;}
        .mb-pro-sidebar .nav-btn{width:100%;display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:8px;font-size:13px;font-weight:600;color:rgba(255,255,255,.65);background:transparent;border:none;cursor:pointer;transition:all .15s;text-align:left;}
        .mb-pro-sidebar .nav-btn:hover,.mb-pro-sidebar .nav-btn.active{background:rgba(255,255,255,.14);color:#fff;}
        .mb-pro-sidebar .nav-icon{width:17px;height:17px;flex-shrink:0;}
        .mb-pro-sidebar .nav-badge{font-size:10px;background:#e5cc4d;color:#1e1e1e;border-radius:999px;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-weight:800;margin-left:auto;}
        .mb-pro-sidebar .profile-footer{padding:0 12px 20px;border-top:1px solid rgba(255,255,255,.15);padding-top:12px;position:relative;}
        .mb-pro-sidebar .profile-row{width:100%;display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:8px;background:transparent;border:none;cursor:pointer;transition:background .15s;}
        .mb-pro-sidebar .profile-row:hover{background:rgba(255,255,255,.08);}
        .mb-pro-sidebar .profile-avatar-default{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.08);outline:2px solid rgba(255,255,255,.3);outline-offset:1px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;}
        .mb-pro-sidebar .profile-meta{flex:1;text-align:left;min-width:0;}
        .mb-pro-sidebar .profile-meta p{margin:0;}
        .mb-pro-sidebar .profile-name{color:#fff;font-size:12px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .mb-pro-sidebar .profile-role{color:rgba(255,255,255,.5);font-size:10px;}
        .mb-pro-sidebar .status-row{padding:0 4px 8px;display:flex;align-items:center;gap:6px;}
        .mb-pro-sidebar .status-dot{width:8px;height:8px;border-radius:50%;background:#4ade80;flex-shrink:0;box-shadow:0 0 0 2px rgba(74,222,128,.3);transition:background .15s,box-shadow .15s;}
        .mb-pro-sidebar .status-dot.offline{background:#9ca3af;box-shadow:0 0 0 2px rgba(156,163,175,.3);}
        .mb-pro-sidebar .status-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.7);letter-spacing:.5px;}
        .mb-pro-sidebar .status-toggle{margin-left:auto;font-size:9px;font-weight:700;color:rgba(255,255,255,.4);background:none;border:none;cursor:pointer;text-transform:uppercase;letter-spacing:.5px;}
        .mb-pro-sidebar .status-toggle:hover{color:rgba(255,255,255,.8);}
        .mb-pro-sidebar .profile-dd{position:absolute;left:12px;right:12px;bottom:calc(100% + 18px);width:auto;background:#f1fffe;border:1px solid rgba(0,0,0,.15);border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.25);overflow:hidden;display:none;z-index:60;}
        .mb-pro-sidebar .profile-dd.open{display:block;}
        .mb-pro-sidebar .profile-dd-header{display:flex;align-items:center;gap:10px;padding:18px 20px;}
        .mb-pro-sidebar .profile-dd-avatar{width:46px;height:46px;border-radius:9999px;border:3px solid #16796f;background:#e8f5f3;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#16796f;overflow:hidden;}
        .mb-pro-sidebar .profile-dd-name{font-weight:600;color:#1e1e1e;font-size:15px;letter-spacing:-.2px;margin:0;}
        .mb-pro-sidebar .profile-dd-email{font-weight:500;color:#899998;font-size:12px;margin:0;}
        .mb-pro-sidebar .profile-dd-divider{height:1px;background:#cfeeeb;}
        .mb-pro-sidebar .profile-dd-section{padding:10px 20px;}
        .mb-pro-sidebar .profile-dd-cta{width:100%;height:38px;background:#16796f;border-radius:10px;color:#fff;font-weight:600;font-size:13px;display:flex;align-items:center;justify-content:center;text-decoration:none;border:none;}
        .mb-pro-sidebar .profile-dd-link{display:block;width:100%;text-align:left;color:#0f504a;font-weight:600;font-size:14px;padding:6px 0;text-decoration:none;}
        .mb-pro-sidebar .profile-dd-link:hover{color:#16796f;}
        .mb-pro-sidebar .profile-dd-logout-row{padding:12px 20px;display:flex;justify-content:flex-end;}
        .mb-pro-sidebar .profile-dd-logout-row button{background:none;border:none;padding:0;font-weight:700;color:#a62023;font-size:14px;display:flex;align-items:center;gap:8px;}
        .mb-pro-sidebar .profile-dd-logout-row button:hover{opacity:.8;}
        .mb-pro-main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}
        .mb-pro-header{background:#f1fffe;box-shadow:0 4px 16.3px rgba(22,121,111,.17);position:sticky;top:0;z-index:30;}
        .mb-pro-header-inner{max-width:1440px;margin:0 auto;padding:0 32px;height:72px;display:flex;align-items:center;justify-content:space-between;gap:16px;}
        .mb-pro-header-title{font-size:22px;font-weight:800;color:#1e1e1e;letter-spacing:-.3px;}
        .mb-pro-header-right{display:flex;align-items:center;gap:20px;position:relative;}
        .mb-pro-header .nav-icon-btn{position:relative;color:#1e1e1e;transition:color .15s;}
        .mb-pro-header .nav-icon-btn:hover,.mb-pro-header .nav-icon-btn.active{color:#16796f;}
        .mb-pro-header .nav-icon-btn{background:none;border:none;padding:0;}
        .mb-pro-header .nav-icon-btn:hover{background:none;}
        .mb-pro-header .notif-dd{position:absolute;right:0;top:56px;width:320px;max-width:90vw;background:#f1fffe;border:1px solid #000;border-radius:12px;box-shadow:0 20px 50px rgba(17,126,117,.25);overflow:hidden;z-index:60;}
        .mb-pro-header .notif-dd-header{display:flex;align-items:center;gap:8px;padding:16px 18px 8px;}
        .mb-pro-header .notif-dd-header span{font-weight:600;color:#5a7474;font-size:16px;letter-spacing:-.2px;}
        .mb-pro-header .notif-dd-close{margin-left:auto;color:#5a7474;background:none;border:none;padding:0;cursor:pointer;display:flex;align-items:center;justify-content:center;}
        .mb-pro-header .notif-dd-close:hover{color:#1e1e1e;}
        .mb-pro-header .notif-dd-list{box-shadow:inset 0 0 4px 0 #8aaba7;max-height:420px;overflow-y:auto;}
        .mb-pro-header .notif-empty{padding:60px 20px;text-align:center;color:#5a7474;font-weight:500;font-size:15px;}
        .mb-pro-header .notif-item{width:100%;display:flex;align-items:flex-start;border:1px solid #acc8c5;text-align:left;background:#fff;transition:opacity .15s;}
        .mb-pro-header .notif-item:hover{opacity:.8;}
        .mb-pro-header .notif-item-body{flex:1;min-width:0;padding:16px 16px 16px 0;}
        .mb-pro-header .notif-item-text{font-size:13px;line-height:1.3;font-weight:500;color:#444;}
        .mb-pro-header .notif-item-date{color:#899998;font-size:12px;margin-top:8px;}
        .mb-pro-content{flex:1;overflow-y:auto;}
        .mb-pro-content-inner{max-width:1200px;margin:0 auto;padding:24px 20px 32px;}
        .mb-pro-section{display:none;}
        .mb-pro-section.active{display:block;}
        .mb-pro-hero{margin-bottom:20px;}
        .mb-pro-hero h1{font-size:22px;font-weight:800;margin:0 0 4px;color:#1e1e1e;}
        .mb-pro-hero p{color:#5a7474;font-size:13px;margin:0;}
        .mb-pro-card{background:#f1fffe;border-radius:16px;border:1px solid rgba(22,121,111,.18);box-shadow:0 4px 18px rgba(0,0,0,.12);}
        .mb-pro-card-pad{padding:20px;}
        .mb-pro-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
        .mb-pro-stat{border-radius:23px;box-shadow:5px 5px 26.5px rgba(113,180,173,.18);padding:20px;text-align:center;background:#fff;}
        .mb-pro-stat.teal{background:#97d6cf;color:#fff;}
        .mb-pro-stat.mint{background:#bcd3d0;color:#0f504a;}
        .mb-pro-stat.white{background:#fff;color:#0f504a;}
        .mb-pro-stat b{display:block;font-weight:600;font-size:32px;}
        .mb-pro-stat span{display:block;font-weight:600;font-size:14px;margin-top:8px;}
        .mb-pro-mentor-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px;}
        @media(min-width:700px){.mb-pro-mentor-grid{grid-template-columns:repeat(4,1fr);}}
        .mb-pro-filter-sep{width:1px;height:24px;background:rgba(22,121,111,.18);margin:0 2px;}
        #mb-pro-filter-row .filter-btn{font-size:11px;font-weight:700;padding:6px 10px;border-radius:8px;border:none;cursor:pointer;transition:all .15s;font-family:'Montserrat',sans-serif;}
        #mb-pro-filter-row .filter-btn.active{background:#16796f;color:#fff;}
        #mb-pro-filter-row .filter-btn.inactive{background:#eef7f6;color:#5a7474;}
        #mb-pro-filter-row .filter-btn.inactive:hover{background:#e4f4f1;}
        .mb-pro-header .mb-pro-nav-icons{display:flex;align-items:center;gap:20px;position:relative;}
        .mb-pro-header .mb-pro-topnav-btn{position:relative;color:#1e1e1e;transition:color .15s;background:none;border:none;padding:0;}
        .mb-pro-header .mb-pro-topnav-btn:hover,.mb-pro-header .mb-pro-topnav-btn.active{color:#16796f;}
        .mb-pro-task-grid{display:grid;grid-template-columns:1fr;gap:16px;}
        .mb-pro-task-card{background:#fff;border-radius:16px;border:1px solid rgba(22,121,111,.16);padding:18px;box-shadow:0 8px 18px rgba(0,0,0,.08);}
        .mb-pro-task-card h3{margin:0 0 8px;font-size:18px;color:#1e1e1e;}
        .mb-pro-task-card p{margin:0;color:#5a7474;font-size:13px;line-height:1.5;}
        .mb-pro-task-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px;}
        .mb-pro-pill{display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:5px 10px;font-size:11px;font-weight:700;background:#cfeeeb;color:#16796f;}
        .mb-pro-pill.inactive{background:#fff;border:1px solid rgba(22,121,111,.18);color:#5a7474;}
        #inbox-wrap{height:560px;display:flex;overflow:hidden;}
        .convo-btn{width:100%;display:flex;align-items:flex-start;gap:10px;padding:12px;text-align:left;border-bottom:1px solid rgba(22,121,111,.1);background:transparent;border-left:2px solid transparent;cursor:pointer;transition:all .1s;}
        .convo-btn:hover{background:rgba(0,0,0,.03);}
        .convo-btn.active{background:rgba(22,121,111,.05);border-left-color:#16796f;}
        .unread-dot{width:16px;height:16px;border-radius:50%;background:#e5cc4d;color:#1e1e1e;font-size:9px;font-weight:900;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .mb-pro-apply-btn{display:inline-flex;align-items:center;justify-content:center;border:none;border-radius:10px;padding:10px 18px;background:#16796f;color:#fff;font-weight:700;font-size:13px;box-shadow:0 6px 16px rgba(22,121,111,.18);transition:opacity .15s;font-family:'Montserrat',sans-serif;}
        .mb-pro-apply-btn:hover{opacity:.9;}
        .mb-pro-footer-spacer{height:18px;}
        .mb-pro-header-home-btn{display:none;}
        .mb-pro-shell.settings-active .mb-pro-sidebar{display:none;}
        .mb-pro-shell.settings-active .mb-pro-header-home-btn{display:inline-flex;align-items:center;justify-content:center;}
        @media (max-width:1023px){
            .mb-pro-shell{flex-direction:column;}
            .mb-pro-sidebar{width:100%;min-width:0;height:auto;position:relative;}
        }
        @media (max-width:700px){
            .mb-pro-header-inner,.mb-pro-content-inner{padding-left:16px;padding-right:16px;}
            .mb-pro-grid{grid-template-columns:1fr;}
        }
        </style>
        <div class="mb-pro-shell<?php echo $section === 'settings' ? ' settings-active' : ''; ?>" id="mb-pro-shell">
            <aside class="mb-pro-sidebar">
                <div style="padding:24px 20px 16px;">
                    <img src="<?php echo MB_LOGO_LIGHT; ?>" alt="MentorBe" style="height:34px;width:auto;display:block;">
                    <p style="color:rgba(255,255,255,.5);font-size:10px;margin-top:6px;font-weight:700;letter-spacing:3px;text-transform:uppercase;">Provider Portal</p>
                </div>
                <nav style="flex:1;padding:0 12px;display:flex;flex-direction:column;gap:2px;">
                    <button class="nav-btn active" data-section="feed" type="button"><span class="nav-icon" data-lucide="layout-dashboard"></span><span>Task Feed</span></button>
                    <button class="nav-btn" data-section="jobs" type="button"><span class="nav-icon" data-lucide="briefcase"></span><span>Active Jobs</span><span class="nav-badge"><?php echo number_format( count( $active_jobs ) ); ?></span></button>
                    <button class="nav-btn" data-section="earnings" type="button"><span class="nav-icon" data-lucide="dollar-sign"></span><span>Earnings & Reviews</span></button>
                </nav>
                <div class="profile-footer">
                    <div class="status-row">
                        <span class="status-dot" id="mb-pro-status-dot"></span>
                        <span class="status-label" id="mb-pro-status-label">Online</span>
                        <button class="status-toggle" type="button" onclick="toggleOnline()">Toggle</button>
                    </div>
                    <button class="profile-row" type="button" onclick="toggleProfileDD()">
                        <span class="profile-avatar-default"><?php echo $avatar ? '<img src="' . esc_url( $avatar ) . '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">' : mb_cv2_icon( 'user', 18 ); ?></span>
                        <div class="profile-meta">
                            <p class="profile-name"><?php echo esc_html( $user->display_name ); ?></p>
                            <p class="profile-role"><?php echo esc_html( ucfirst( $profile->user_role ) ); ?> · Provider</p>
                        </div>
                        <svg id="profile-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.5)" stroke-width="2" stroke-linecap="round" style="transition:transform .2s;flex-shrink:0;"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="profile-dd" id="mb-provider-profile-dd">
                        <div class="profile-dd-section">
                            <a class="profile-dd-cta" href="?tab=settings&stab=personal">Settings</a>
                        </div>
                        <div class="profile-dd-divider"></div>
                        <div class="profile-dd-logout-row">
                            <button type="button" onclick="mbCv2RequestLogout('<?php echo esc_js( $logout_u ); ?>')">Log Out <?php echo mb_cv2_icon( 'logout', 18 ); ?></button>
                        </div>
                    </div>
                </div>
            </aside>
            <main class="mb-pro-main">
                <header class="mb-pro-header">
                    <div class="mb-pro-header-inner">
                        <div class="mb-pro-header-title" id="mb-pro-header-title"><?php echo esc_html( $header_title ); ?></div>
                        <div class="mb-pro-header-right">
                            <button type="button" class="nav-icon-btn mb-pro-header-home-btn" title="Home" onclick="switchSection('feed')"><?php echo mb_cv2_icon( 'home', 22 ); ?></button>
                            <div class="mb-pro-nav-icons">
                                <a class="mb-pro-topnav-btn" href="<?php echo esc_url( $inbox_u ); ?>" title="Inbox"><?php echo mb_cv2_icon( 'msg', 22 ); ?></a>
                                <button type="button" class="mb-pro-topnav-btn" data-section="jobs" title="Active Jobs" onclick="switchSection('jobs')"><?php echo mb_cv2_icon( 'briefcase', 22 ); ?></button>
                                <button type="button" class="mb-pro-topnav-btn" data-section="earnings" title="Earnings & Reviews" onclick="switchSection('earnings')"><?php echo mb_cv2_icon( 'dollar-sign', 22 ); ?></button>
                            </div>
                            <button type="button" class="nav-icon-btn" id="mb-pro-notif-btn" title="Notifications" onclick="toggleNotif(event)"><?php echo mb_cv2_icon( 'bell', 22 ); ?></button>
                            <div class="notif-dd" id="mb-pro-notif-dd" style="display:none;">
                                <div class="notif-dd-header"><?php echo mb_cv2_icon( 'bell', 28 ); ?><span>Notifications</span><button type="button" class="notif-dd-close" onclick="closeAll()"><?php echo mb_cv2_icon( 'x', 16 ); ?></button></div>
                                <div class="notif-dd-list"><?php echo mb_render_notifications_list( $user->ID ); ?></div>
                            </div>
                        </div>
                    </div>
                </header>
                <div class="mb-pro-content">
                    <div class="mb-pro-content-inner">
                        <section class="mb-pro-section active" data-section="feed">
                            <div class="mb-pro-hero">
                                <h1>Find Local Tasks. Share Your Skills.</h1>
                                <p>Skill-matched tasks appear first. Your tags: <?php echo esc_html( $profile->user_role === 'provider' ? 'Provider profile' : 'No skills linked yet' ); ?>.</p>
                            </div>
                            <div class="mb-pro-card mb-pro-card-pad" style="margin-bottom:20px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                                <div style="position:relative;flex:1;min-width:160px;">
                                    <span data-lucide="search" style="width:13px;height:13px;color:#5a7474;position:absolute;left:12px;top:50%;transform:translateY(-50%);"></span>
                                    <input id="mb-pro-feed-search" type="text" placeholder="Search tasks…" style="width:100%;border:1px solid rgba(22,121,111,.18);border-radius:12px;padding:8px 14px 8px 32px;font-size:13px;outline:none;background:#fff;">
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;" id="mb-pro-filter-row">
                                    <span data-lucide="filter" style="width:13px;height:13px;color:#5a7474;"></span>
                                    <button class="filter-btn active" type="button" data-cat="all">All</button>
                                    <button class="filter-btn inactive" type="button" data-cat="tech">Tech & Mentoring</button>
                                    <button class="filter-btn inactive" type="button" data-cat="creative">Creative</button>
                                    <button class="filter-btn inactive" type="button" data-cat="home">Home</button>
                                    <button class="filter-btn inactive" type="button" data-cat="errands">Errands</button>
                                </div>
                            </div>
                            <div id="mb-pro-feed-list" class="mb-pro-mentor-grid"></div>
                        </section>
                        <section class="mb-pro-section" data-section="jobs">
                            <div class="mb-pro-hero">
                                <h1>Track Your Work from Start to Finish.</h1>
                                <p><?php echo number_format( count( $active_jobs ) ); ?> active engagement(s).</p>
                            </div>
                            <div class="mb-pro-task-grid">
                                <?php if ( empty( $active_jobs ) ): ?>
                                    <div class="mb-pro-card mb-pro-card-pad"><p style="margin:0;color:#5a7474;">No active jobs right now.</p></div>
                                <?php else: foreach ( $active_jobs as $job ): $job_client = get_userdata( $job->client_id ); ?>
                                    <div class="mb-pro-task-card">
                                        <div class="mb-pro-task-head">
                                            <h3><?php echo esc_html( $job->title ); ?></h3>
                                            <span class="mb-pro-pill">In Progress</span>
                                        </div>
                                        <p><?php echo esc_html( wp_trim_words( $job->description, 22 ) ); ?></p>
                                        <?php if ( $job_client ): ?><p style="margin:6px 0 0;color:#5a7474;font-size:12px;">Client: <?php echo esc_html( $job_client->display_name ); ?></p><?php endif; ?>
                                        <?php if ( $job->ready_for_review ): ?>
                                            <div class="mb-pro-pill" style="margin-top:10px;background:#fef3c7;color:#92400e;">Waiting on client review</div>
                                        <?php endif; ?>
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;flex-wrap:wrap;">
                                            <strong style="font-size:16px;color:#1e1e1e;"><?php echo mb_price( $job->budget ); ?></strong>
                                            <span style="color:#5a7474;font-size:12px;">Updated <?php echo esc_html( human_time_diff( strtotime( $job->updated_at ), current_time( 'timestamp' ) ) ); ?> ago</span>
                                        </div>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
                                            <?php if ( ! $job->ready_for_review ): ?>
                                                <button type="button" class="mb-pro-apply-btn mb-pro-ready-task" data-id="<?php echo (int) $job->id; ?>" style="flex:1;">Mark Ready for Review</button>
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url( $inbox_u ); ?>" class="mb-pro-apply-btn" style="flex:1;text-align:center;text-decoration:none;background:#fff;color:#16796f;border:1px solid rgba(22,121,111,.35);">Message Client</a>
                                        </div>
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </section>
                        <section class="mb-pro-section" data-section="inbox">
                            <div class="mb-pro-hero">
                                <h1>Connect and Clarify Before You Commit.</h1>
                                <p>Pre-commitment chats tied to specific listings.</p>
                            </div>
                            <div class="mb-pro-card" id="inbox-wrap">
                                <div style="width:220px;flex-shrink:0;border-right:1px solid rgba(22,121,111,.18);display:flex;flex-direction:column;">
                                    <div style="padding:12px;border-bottom:1px solid rgba(22,121,111,.18);">
                                        <p style="font-size:11px;font-weight:800;color:#5a7474;text-transform:uppercase;letter-spacing:2px;">Messages</p>
                                    </div>
                                    <div style="flex:1;overflow-y:auto;" id="convo-list">
                                        <button type="button" class="convo-btn active">
                                            <div class="unread-dot">1</div>
                                            <div style="min-width:0;flex:1;">
                                                <strong style="display:block;color:#1e1e1e;font-size:13px;">No conversations yet</strong>
                                                <p style="margin:4px 0 0;color:#617876;font-size:12px;line-height:1.4;">Open the inbox page to see real conversations and profile links.</p>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                                <div style="flex:1;display:flex;flex-direction:column;min-width:0;" id="chat-pane">
                                    <div style="margin:auto;text-align:center;color:#617876;padding:24px;">
                                        <div style="font-size:14px;font-weight:700;color:#1e1e1e;margin-bottom:6px;">Use the inbox page</div>
                                        <p style="margin:0;font-size:13px;">This panel is just a preview. Use the inbox button in the header to open the full inbox page.</p>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="mb-pro-section" data-section="earnings">
                            <div class="mb-pro-hero">
                                <h1>Your Hustle, Quantified.</h1>
                            </div>
                            <div class="mb-pro-grid" style="margin-bottom:24px;">
                                <div class="mb-pro-stat teal"><b><?php echo number_format( $completed_tasks ); ?></b><span>Completed Tasks</span></div>
                                <div class="mb-pro-stat mint"><b><?php echo mb_price( $gross_completed ); ?></b><span>Gross Earnings</span></div>
                                <div class="mb-pro-stat white"><b><?php echo mb_price( $estimated_fee ); ?></b><span>Estimated Fee</span></div>
                                <div class="mb-pro-stat white"><b><?php echo esc_html( $rating_summary['label'] ); ?></b><span>Average Rating</span></div>
                            </div>
                            <div class="mb-pro-card mb-pro-card-pad">
                                <strong style="display:block;font-size:18px;color:#1e1e1e;margin-bottom:14px;">Recent Activity</strong>
                                <?php if ( empty( $recent_activity ) ): ?>
                                    <p style="margin:0;color:#5a7474;">No recent activity to show.</p>
                                <?php else: foreach ( $recent_activity as $row ): ?>
                                    <?php $already_rated = mb_has_task_review( $row->id, $user->ID ); ?>
                                    <div style="display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid rgba(137,153,152,.25);">
                                        <div>
                                            <strong style="display:block;color:#1e1e1e;font-size:14px;"><?php echo esc_html( $row->title ); ?></strong>
                                            <span style="color:#5a7474;font-size:12px;"><?php echo esc_html( ucwords( str_replace( '_', ' ', $row->status ) ) ); ?></span>
                                        </div>
                                        <div style="text-align:right;">
                                            <strong style="display:block;color:#1e1e1e;font-size:14px;"><?php echo mb_price( $row->budget ); ?></strong>
                                            <span style="color:#5a7474;font-size:12px;"><?php echo esc_html( human_time_diff( strtotime( $row->updated_at ), current_time( 'timestamp' ) ) ); ?> ago</span>
                                        </div>
                                    </div>
                                    <?php if ( $row->status === 'completed' && ! $already_rated ): ?>
                                        <div style="margin-top:8px;display:flex;justify-content:flex-end;">
                                            <button type="button" class="mb-pv-btn mb-review-btn" data-task="<?php echo (int) $row->id; ?>" data-role="provider" data-reviewee="<?php echo (int) $row->client_id; ?>">Rate Client</button>
                                        </div>
                                    <?php elseif ( $row->status === 'completed' && $already_rated ): ?>
                                        <div style="margin-top:8px;display:flex;justify-content:flex-end;">
                                            <span class="mb-pro-pill" style="background:#ecfdf5;color:#065f46;">Completed &amp; Rated</span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; endif; ?>
                            </div>
                        </section>
                        <section class="mb-pro-section" data-section="settings">
                            <div class="mb-cv2">
                                <?php echo mb_client_settings_tab_v2( $user->ID, $profile, 'provider' ); ?>
                            </div>
                        </section>
                        <?php echo mb_render_review_modal_shell(); ?>
                        <section class="mb-pro-section" data-section="profile">
                            <div class="mb-pro-hero">
                                <h1>Profile & Settings</h1>
                                <p>Keep your identity, skills, and contact details up to date.</p>
                            </div>
                            <div class="mb-pro-grid" style="grid-template-columns:1fr 2fr;gap:20px;">
                                <div class="mb-pro-card mb-pro-card-pad" style="text-align:center;">
                                    <div style="position:relative;display:inline-block;margin-bottom:12px;">
                                        <img src="<?php echo esc_url( $avatar ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=80&h=80&fit=crop&auto=format' ); ?>" style="width:84px;height:84px;border-radius:50%;object-fit:cover;outline:4px solid rgba(22,121,111,.18);" alt="">
                                    </div>
                                    <h3 style="font-size:14px;font-weight:800;margin:0 0 4px;"><?php echo esc_html( $user->display_name ); ?></h3>
                                    <p style="font-size:11px;color:#5a7474;margin:0;"><?php echo esc_html( $user->user_email ); ?></p>
                                    <div style="display:flex;gap:6px;justify-content:center;margin-top:10px;flex-wrap:wrap;">
                                        <?php if ( $profile->verification_status === 'verified' ): ?><span class="mb-pro-pill" style="background:#ecfdf5;color:#065f46;">Verified</span><?php else: ?><span class="mb-pro-pill" style="background:#fef3c7;color:#92400e;">Pending</span><?php endif; ?>
                                    </div>
                                    <div style="margin-top:14px;">
                                        <a href="<?php echo esc_url( mb_find_page_url( '[mb_profile_view]' ) . '?mb_user=' . $user->ID ); ?>" class="mb-pv-btn" style="text-decoration:none;">View Public Profile</a>
                                    </div>
                                </div>
                                <div class="mb-pro-card mb-pro-card-pad">
                                    <strong style="display:block;font-size:18px;color:#1e1e1e;margin-bottom:10px;">About</strong>
                                    <p style="margin:0;color:#5a7474;line-height:1.6;"><?php echo esc_html( $profile->bio ?: 'Add a short bio to help clients understand what you do.' ); ?></p>
                                    <div class="mb-pro-footer-spacer"></div>
                                    <strong style="display:block;font-size:18px;color:#1e1e1e;margin-bottom:10px;">Quick Links</strong>
                                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                        <a href="<?php echo esc_url( $inbox_u ); ?>" class="mb-pv-btn" style="text-decoration:none;">Inbox</a>
                                        <a href="<?php echo esc_url( $earn_u ); ?>" class="mb-pv-btn" style="text-decoration:none;">Earnings</a>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <div id="mb-pro-apply-modal" style="display:none;position:fixed;inset:0;z-index:2000;align-items:center;justify-content:center;background:rgba(0,0,0,.32);padding:16px;">
                            <div class="mb-pro-card mb-pro-card-pad" style="width:min(100%,460px);box-shadow:0 25px 50px rgba(0,0,0,.25);">
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px;">
                                    <div>
                                        <h3 style="font-size:18px;font-weight:700;margin:0 0 4px;color:#1e1e1e;">Apply to Task</h3>
                                        <p id="mb-pro-apply-name" style="margin:0;color:#5a7474;font-size:13px;"></p>
                                    </div>
                                    <button type="button" id="mb-pro-apply-close" style="background:none;border:none;cursor:pointer;color:#5a7474;"><?php echo mb_cv2_icon( 'x', 18 ); ?></button>
                                </div>
                                <div style="display:flex;justify-content:space-between;gap:12px;padding:12px 14px;border-radius:12px;background:#f0faf9;margin-bottom:16px;font-size:13px;">
                                    <span style="color:#5a7474;">Budget</span>
                                    <strong id="mb-pro-apply-budget" style="color:#16796f;"></strong>
                                </div>
                                <form id="mb-pro-apply-form">
                                    <input type="hidden" id="mb-pro-apply-id" name="task_id" value="">
                                    <div style="margin-bottom:14px;">
                                        <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:#1e1e1e;">Proposed Rate *</label>
                                        <input type="number" id="mb-pro-apply-rate" name="proposed_rate" min="0" step="0.01" required style="width:100%;border:1px solid rgba(22,121,111,.18);border-radius:12px;padding:10px 14px;font-size:14px;outline:none;">
                                    </div>
                                    <div style="margin-bottom:16px;">
                                        <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:#1e1e1e;">Cover Message *</label>
                                        <textarea id="mb-pro-apply-cover" name="cover_message" required rows="5" placeholder="Introduce yourself and explain your approach." style="width:100%;border:1px solid rgba(22,121,111,.18);border-radius:12px;padding:10px 14px;font-size:14px;outline:none;resize:vertical;"></textarea>
                                    </div>
                                    <div style="display:flex;gap:8px;">
                                        <button type="button" id="mb-pro-apply-cancel" class="mb-pv-btn" style="flex:1;background:#fff;color:#1e1e1e;border:1px solid rgba(22,121,111,.18);text-decoration:none;">Cancel</button>
                                        <button type="submit" class="mb-pv-btn" style="flex:1;">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div class="mb-cv2">
            <div class="settings-modal" id="mb-cv2-logout-modal" aria-hidden="true">
                <div class="settings-modal-card">
                    <h3>Log out?</h3>
                    <p>Are you sure you want to log out of MentorBe? You can sign back in anytime from the login page.</p>
                    <div class="settings-modal-actions">
                        <button type="button" class="outline-btn2" onclick="mbCv2CloseLogout()">Cancel</button>
                        <button type="button" class="save-btn" onclick="mbCv2ConfirmLogout()">Log out</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function(){
            var section = '<?php echo esc_js( $section ); ?>';
            var feed = null;
            var shellEl = document.getElementById('mb-pro-shell');
            var headerTitleEl = document.getElementById('mb-pro-header-title');
            var sectionTitles = {
                feed: 'Task Feed',
                jobs: 'Active Jobs',
                earnings: 'Earnings & Reviews',
                settings: 'Settings',
                profile: 'Profile & Settings',
                inbox: 'Inbox'
            };
            function showSection(name, updateUrl){
                document.querySelectorAll('.mb-pro-section').forEach(function(el){ el.classList.toggle('active', el.dataset.section === name); });
                document.querySelectorAll('.mb-pro-topnav-btn').forEach(function(btn){
                    btn.classList.toggle('active', btn.dataset.section === name);
                });
                document.querySelectorAll('.mb-pro-sidebar .nav-btn').forEach(function(btn){
                    btn.classList.toggle('active', btn.dataset.section === name);
                });
                if (shellEl) shellEl.classList.toggle('settings-active', name === 'settings');
                if (headerTitleEl && sectionTitles[name]) headerTitleEl.textContent = sectionTitles[name];
                if (updateUrl !== false) {
                    try {
                        var url = new URL(window.location.href);
                        url.searchParams.set('section', name);
                        url.searchParams.delete('tab');
                        url.searchParams.delete('stab');
                        window.history.replaceState({}, '', url);
                    } catch (e) {}
                }
            }
            window.switchSection = function(name){ showSection(name); };
            showSection(section, false);
            document.querySelectorAll('.mb-pro-sidebar .nav-btn').forEach(function(btn){
                btn.addEventListener('click', function(){
                    showSection(this.dataset.section);
                });
            });
            var applyModal = document.getElementById('mb-pro-apply-modal');
            var applyClose = document.getElementById('mb-pro-apply-close');
            var applyCancel = document.getElementById('mb-pro-apply-cancel');
            var applyForm = document.getElementById('mb-pro-apply-form');
            if (applyClose) applyClose.addEventListener('click', function(){ if (applyModal) applyModal.style.display = 'none'; });
            if (applyCancel) applyCancel.addEventListener('click', function(){ if (applyModal) applyModal.style.display = 'none'; });
            if (applyModal) {
                applyModal.addEventListener('click', function(e){ if (e.target === this) this.style.display = 'none'; });
            }
            if (applyForm) {
                applyForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    var fd = new FormData(this);
                    fd.append('action', 'mb_apply_to_task');
                    fd.append('nonce', mbNonce);
                    var btn = this.querySelector('button[type="submit"]');
                    var original = btn ? btn.textContent : '';
                    if (btn) { btn.disabled = true; btn.textContent = 'Submitting...'; }
                    fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(json){
                        if (btn) { btn.disabled = false; btn.textContent = original; }
                        if (applyModal && json && json.success) applyModal.style.display = 'none';
                        if (json && json.data && json.data.message) {
                            alert(json.data.message);
                        }
                    }).catch(function(){
                        if (btn) { btn.disabled = false; btn.textContent = original; }
                    });
                });
            }
            window.toggleProfileDD = function(){
                var dd = document.getElementById('mb-provider-profile-dd');
                if (!dd) return;
                dd.classList.toggle('open');
            };
            var statusDot = document.getElementById('mb-pro-status-dot');
            var statusLabel = document.getElementById('mb-pro-status-label');
            var ONLINE_KEY = 'mb_provider_online_status';
            function applyOnlineState(isOnline){
                if (statusDot) statusDot.classList.toggle('offline', !isOnline);
                if (statusLabel) statusLabel.textContent = isOnline ? 'Online' : 'Offline';
            }
            (function initOnlineState(){
                var stored = null;
                try { stored = window.localStorage.getItem(ONLINE_KEY); } catch (e) {}
                applyOnlineState(stored !== 'offline');
            })();
            window.toggleOnline = function(){
                var isOffline = statusDot && statusDot.classList.contains('offline');
                var nextOnline = !!isOffline;
                applyOnlineState(nextOnline);
                try { window.localStorage.setItem(ONLINE_KEY, nextOnline ? 'online' : 'offline'); } catch (e) {}
            };
            window.mbCv2RequestLogout = function(url){
                var modal = document.getElementById('mb-cv2-logout-modal');
                if (!modal) { window.location.href = url; return; }
                modal.dataset.logoutUrl = url;
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            };
            window.mbCv2CloseLogout = function(){
                var modal = document.getElementById('mb-cv2-logout-modal');
                if (!modal) return;
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            };
            window.mbCv2ConfirmLogout = function(){
                var modal = document.getElementById('mb-cv2-logout-modal');
                if (!modal) return;
                window.location.href = modal.dataset.logoutUrl || '<?php echo esc_js( $logout_u ); ?>';
            };
            var mbProLogoutModal = document.getElementById('mb-cv2-logout-modal');
            if (mbProLogoutModal) {
                mbProLogoutModal.addEventListener('click', function(e){
                    if (e.target === this) mbCv2CloseLogout();
                });
            }
            document.querySelectorAll('.mb-pro-ready-task').forEach(function(btn){
                btn.addEventListener('click', function(){
                    if (!confirm('Mark this task as ready for the client to review?')) return;
                    var fd = new FormData();
                    fd.append('action', 'mb_mark_ready_for_review');
                    fd.append('task_id', this.dataset.id);
                    fd.append('nonce', mbNonce);
                    fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(json){
                        if (json && json.data && json.data.message) alert(json.data.message);
                        if (json && json.success) location.reload();
                    }).catch(function(){
                        alert('Unable to update this task right now.');
                    });
                });
            });
            window.toggleNotif = function(ev){
                if (ev) ev.stopPropagation();
                var dd = document.getElementById('mb-pro-notif-dd');
                if (!dd) return;
                dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
            };
            window.closeAll = function(){
                var dd = document.getElementById('mb-pro-notif-dd');
                var pd = document.getElementById('mb-provider-profile-dd');
                if (dd) dd.style.display = 'none';
                if (pd) pd.classList.remove('open');
            };
            document.addEventListener('click', function(e){
                if (!e.target.closest('.mb-pro-header-right') && !e.target.closest('.profile-footer')) {
                    closeAll();
                }
            });

            var feedBox = document.getElementById('mb-pro-feed-list');
            var searchInp = document.getElementById('mb-pro-feed-search');
            var catBtns = document.querySelectorAll('#mb-pro-filter-row [data-cat]');
            var currentCat = 'all';
            var allTasks = [];
            var timer = null;

            function esc(str) {
                return String(str || '').replace(/[&<>"']/g, function(ch) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
                });
            }
            function categoryMatches(task, cat) {
                if (!cat || cat === 'all') return true;
                return String(task.cat || 'all') === cat;
            }
            function renderTasks(list) {
                if (!feedBox) return;
                if (!list || !list.length) {
                    feedBox.innerHTML = '<div class="mb-pro-card mb-pro-card-pad" style="grid-column:1/-1;"><p style="margin:0;color:#5a7474;">No tasks match your filters.</p></div>';
                    return;
                }
                feedBox.innerHTML = list.map(function(t){
                    var skills = (t.skills || []).map(function(s){ return '<span class="mb-pro-pill" style="margin-right:6px;margin-bottom:6px;">'+esc(s)+'</span>'; }).join('');
                    var action = t.already_applied ? '<span class="mb-pro-pill" style="background:#eef2ff;color:#4338ca;">Already Applied</span>' : '<button type="button" class="mb-pro-apply-btn mb-open-apply" data-id="'+esc(t.id)+'">Apply</button>';
                    return '<div class="mb-pro-task-card">'
                        + '<div class="mb-pro-task-head"><h3>'+esc(t.title)+'</h3><strong style="font-size:16px;color:#16796f;">'+esc(t.budget_fmt)+'</strong></div>'
                        + '<p>'+esc(t.description)+'</p>'
                        + (skills ? '<div style="margin-top:10px;">'+skills+'</div>' : '')
                        + '<div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:14px;flex-wrap:wrap;">'
                        + '<span style="color:#5a7474;font-size:12px;">by '+esc(t.client_name)+' · '+esc(t.posted_ago)+'</span>'
                        + (t.already_applied ? action : '<button type="button" class="mb-pro-apply-btn mb-open-apply" data-id="'+esc(t.id)+'" data-title="'+esc(t.title)+'" data-budget="'+esc(t.budget_fmt)+'">Apply</button>')
                        + '</div>'
                        + '</div>';
                }).join('');
                feedBox.querySelectorAll('.mb-open-apply').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        var modal = document.getElementById('mb-pro-apply-modal');
                        if (!modal) return;
                        document.getElementById('mb-pro-apply-id').value = this.dataset.id || '';
                        document.getElementById('mb-pro-apply-name').textContent = this.dataset.title || '';
                        document.getElementById('mb-pro-apply-budget').textContent = this.dataset.budget || '';
                        document.getElementById('mb-pro-apply-rate').value = '';
                        document.getElementById('mb-pro-apply-cover').value = '';
                        modal.style.display = 'flex';
                    });
                });
            }
            function renderFilteredTasks() {
                renderTasks(allTasks.filter(function(t){ return categoryMatches(t, currentCat); }));
            }
            function syncFilterButtons() {
                catBtns.forEach(function(btn){
                    var active = btn.dataset.cat === currentCat;
                    btn.classList.toggle('active', active);
                    btn.classList.toggle('inactive', !active);
                });
            }
            function setCatFilter(cat) {
                currentCat = cat || 'all';
                syncFilterButtons();
                renderFilteredTasks();
            }
            function loadTasks(query) {
                if (!feedBox) return;
                feedBox.innerHTML = '<div class="mb-pro-card mb-pro-card-pad" style="grid-column:1/-1;"><p style="margin:0;color:#5a7474;">Loading tasks...</p></div>';
                var fd = new FormData();
                fd.append('action', 'mb_filter_feed');
                fd.append('skill_id', '');
                fd.append('search', query || '');
                fd.append('nonce', mbNonce);
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(json){
                    if (json && json.success && json.data && json.data.tasks) {
                        allTasks = json.data.tasks;
                        renderFilteredTasks();
                    } else {
                        feedBox.innerHTML = '<div class="mb-pro-card mb-pro-card-pad" style="grid-column:1/-1;"><p style="margin:0;color:#5a7474;">Unable to load tasks right now.</p></div>';
                    }
                }).catch(function(){
                    feedBox.innerHTML = '<div class="mb-pro-card mb-pro-card-pad" style="grid-column:1/-1;"><p style="margin:0;color:#5a7474;">Unable to load tasks right now.</p></div>';
                });
            }

            if (searchInp) {
                searchInp.addEventListener('input', function(){
                    clearTimeout(timer);
                    var q = this.value.trim();
                    timer = setTimeout(function(){ loadTasks(q); }, 300);
                });
            }
            catBtns.forEach(function(btn){
                btn.addEventListener('click', function(){
                    setCatFilter(this.dataset.cat || 'all');
                });
            });
            syncFilterButtons();
            loadTasks('');
        })();
        </script>
        <?php
    }

    $content = ob_get_clean();
    return $content;
}

/* ==========================================================================
   H. CLIENT TAB FUNCTIONS
========================================================================== */

function mb_client_overview_tab( $user_id ) {
    global $wpdb;
    $tt = $wpdb->prefix . 'mb_tasks';
    $at = $wpdb->prefix . 'mb_task_applications';

    $user      = get_userdata( $user_id );
    $first     = $user ? strtok( $user->display_name, ' ' ) : '';
    $active    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tt} WHERE client_id=%d AND status IN ('live','in_progress')", $user_id ) );
    $pend_apps = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$at} a INNER JOIN {$tt} t ON a.task_id=t.id WHERE t.client_id=%d AND a.status='pending'", $user_id ) );
    $done      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tt} WHERE client_id=%d AND status='completed'", $user_id ) );
    $budget    = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(budget),0) FROM {$tt} WHERE client_id=%d AND status!='cancelled'", $user_id ) );
    $needs_action = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tt} WHERE client_id=%d AND (ready_for_review=1 OR status IN ('live','in_progress')) ORDER BY updated_at DESC LIMIT 5", $user_id ) );

    ob_start(); ?>
    <div class="home-wrap">
        <div class="home-inner">
            <div class="welcome">
                <h1>Welcome, <span><?php echo esc_html( $first ?: 'there' ); ?>!</span></h1>
                <p>Here's what's happening with your tasks today.</p>
            </div>
            <div class="home-search-wrap" style="padding-bottom:24px;">
                <div class="home-search">
                    <input type="text" id="mb-mentor-search" placeholder="Search mentors by name or skill...">
                    <?php echo mb_cv2_icon( 'search', 20 ); ?>
                </div>
            </div>
            <div class="tra-stats-grid">
                <div class="tra-card">
                    <h2>Tasks Requiring Action</h2>
                    <?php if ( empty( $needs_action ) ): ?>
                        <p class="tra-empty">No tasks requiring action.</p>
                    <?php else: foreach ( $needs_action as $t ): ?>
                        <div class="tra-row">
                            <div><strong><?php echo esc_html( $t->title ); ?></strong><span><?php echo $t->ready_for_review ? 'Awaiting your review' : esc_html( ucwords( str_replace( '_', ' ', $t->status ) ) ); ?></span></div>
                            <button type="button" onclick="window.location.href='?tab=manage_tasks'">View</button>
                        </div>
                    <?php endforeach; endif; ?>
                    <button type="button" class="tra-more" onclick="window.location.href='?tab=manage_tasks'">See all tasks</button>
                </div>
            <div class="stats-col">
                <div class="stats-row">
                    <div class="stat-box teal"><b><?php echo number_format( $active ); ?></b><span>Active Tasks</span></div>
                    <div class="stat-box mint"><b><?php echo number_format( $pend_apps ); ?></b><span>Pending Applicants</span></div>
                    <div class="stat-box white"><b><?php echo number_format( $done ); ?></b><span>Completed</span></div>
                    </div>
                    <div class="stat-box white" style="text-align:center;">
                        <b style="font-size:24px;"><?php echo mb_price( $budget ); ?></b><span>Total Budget Committed</span>
                    </div>
                    <button type="button" class="post-new-btn" onclick="window.location.href='?tab=post_task'"><?php echo mb_cv2_icon( 'plus', 24 ); ?> Post a New Task</button>
                </div>
            </div>
            <div style="margin-top:28px;margin-bottom:40px;">
                <h2 style="font-weight:800;font-size:28px;color:var(--ink);margin-bottom:18px;">Available Providers/Mentors</h2>
                <div id="mb-mentor-results" class="mentor-grid wide">
                    <p class="tra-empty" style="grid-column:1/-1;">Loading mentors...</p>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var input = document.getElementById('mb-mentor-search');
        var results = document.getElementById('mb-mentor-results');
        var timer = null;

        function esc(str) {
            return String(str || '').replace(/[&<>"']/g, function(ch) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
            });
        }

        function renderMentors(list) {
            if (!results) return;
            if (!list || !list.length) {
                results.className = 'mentor-grid wide';
                results.innerHTML = '<p class="tra-empty" style="grid-column:1/-1;">No mentors found.</p>';
                return;
            }

            results.className = 'mentor-grid wide';
            results.innerHTML = list.map(function(m) {
                var avatar = m.avatar ? '<img src="'+esc(m.avatar)+'" alt="'+esc(m.name)+'">' : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#e8f5f3;color:#16796f;font-weight:800;font-size:42px;">'+esc((m.name||'M').charAt(0).toUpperCase())+'</div>';
                var skills = (m.skills || []).slice(0, 4).map(function(s) { return '<span style="display:inline-block;margin:0 6px 6px 0;padding:4px 10px;border-radius:9999px;background:#cfeeeb;color:#16796f;font-size:12px;font-weight:600;">'+esc(s)+'</span>'; }).join('');
                var headline = m.headline ? esc(m.headline) : (m.location ? esc(m.location) : 'Available mentor');
                var verified = m.verified ? '<span class="bntm-status-badge bntm-status-completed" style="display:inline-flex;margin-left:8px;">Verified</span>' : '';
                return ''
                    + '<div class="mentor-card">'
                    +   '<a href="'+esc(m.profile_url)+'" class="mentor-img-btn">'
                    +     avatar
                    +     '<div class="mentor-img-fade"></div>'
                    +     '<div class="mentor-name-overlay">'+esc(m.name)+verified+'</div>'
                    +   '</a>'
                    +   '<div class="mentor-tagline">'+headline+'</div>'
                    +   (m.bio ? '<p class="mentor-desc">'+esc(m.bio)+'</p>' : '')
                    +   (skills ? '<div style="margin-top:10px;">'+skills+'</div>' : '')
                    +   '<div class="mentor-meta"><span class="mentor-rating">'+esc(m.avg_rating ? Number(m.avg_rating).toFixed(1) + '/5' : 'No ratings yet')+'</span><span class="mentor-reviews">'+esc(m.review_count || 0)+' reviews • '+esc(m.completed)+' completed</span></div>'
                    +   '<div style="display:flex;gap:10px;margin-top:12px;">'
                    +     '<a class="hire-btn" href="'+esc(m.profile_url)+'" style="flex:1;text-align:center;text-decoration:none;line-height:1.2;padding:10px 12px;">View Profile</a>'
                    +     '<button type="button" class="hire-btn mb-mentor-message" data-user="'+esc(m.id)+'" style="flex:1;">Message</button>'
                    +   '</div>'
                    + '</div>';
            }).join('');

            results.querySelectorAll('.mb-mentor-message').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var fd = new FormData();
                    fd.append('action', 'mb_start_conversation');
                    fd.append('user_id', this.dataset.user);
                    fd.append('nonce', mbNonce);
                    fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(json) {
                        if (json.success && json.data && json.data.redirect) {
                            window.location.href = json.data.redirect;
                        }
                    });
                });
            });
        }

        function loadMentors(query) {
            if (!results) return;
            results.innerHTML = '<p class="tra-empty" style="grid-column:1/-1;">Loading mentors...</p>';
            var fd = new FormData();
            fd.append('action', 'mb_search_mentors');
            fd.append('query', query || '');
            fd.append('limit', '6');
            fd.append('nonce', mbNonce);
            fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(json) {
                if (json && json.success && json.data && json.data.mentors) {
                    renderMentors(json.data.mentors);
                } else {
                    results.innerHTML = '<p class="tra-empty" style="grid-column:1/-1;">Unable to load mentors right now.</p>';
                }
            }).catch(function() {
                results.innerHTML = '<p class="tra-empty" style="grid-column:1/-1;">Unable to load mentors right now.</p>';
            });
        }

        if (input) {
            input.addEventListener('input', function() {
                clearTimeout(timer);
                var q = this.value.trim();
                timer = setTimeout(function() { loadMentors(q); }, 300);
            });
            loadMentors('');
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_client_post_task_tab( $user_id ) {
    global $wpdb;
    $edit_task_id = isset( $_GET['edit_task'] ) ? intval( $_GET['edit_task'] ) : 0;
    $edit_task = null;
    $edit_skills = [];
    if ( $edit_task_id > 0 ) {
        $edit_task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id=%d AND client_id=%d AND status='draft'", $edit_task_id, $user_id ) );
        if ( $edit_task ) {
            $edit_skills = $wpdb->get_col( $wpdb->prepare( "SELECT skill_id FROM {$wpdb->prefix}mb_task_skills WHERE task_id=%d", $edit_task_id ) );
        }
    }
    ob_start(); ?>
    <div class="teal-page">
        <div class="teal-inner">
            <h1 class="teal-title"><?php echo $edit_task ? 'Edit Draft Task' : 'Post a Task'; ?></h1>
            <form id="mb-post-task-form">
                <input type="hidden" name="task_id" value="<?php echo esc_attr( $edit_task ? (int) $edit_task->id : 0 ); ?>">
                <div class="white-card">
                    <h2>Task Overview</h2>
                    <div class="field"><label>Task Title *</label><input type="text" name="title" required maxlength="255" placeholder="e.g. Fix leaking kitchen faucet" value="<?php echo esc_attr( $edit_task ? $edit_task->title : '' ); ?>"></div>
                    <div class="field"><label>Description *</label><textarea rows="5" name="description" required placeholder="Describe what needs to be done, scope, and requirements."><?php echo esc_textarea( $edit_task ? $edit_task->description : '' ); ?></textarea></div>
                    <div class="field">
                        <label>Required Skills</label>
                        <div class="bntm-skill-tag-input" id="pt-skill-wrap" style="border-color:var(--teal-dark);"><input type="text" placeholder="Type to search skills..."></div>
                        <input type="hidden" name="skills" id="pt-skills-hidden">
                    </div>
                </div>
                <div class="white-card">
                    <h2>Budget &amp; Escrow</h2>
                    <div class="field"><label>Proposed Budget (&#8369;) *</label><div class="budget-wrap"><span>&#8369;</span><input type="number" name="budget" min="0" step="0.01" required placeholder="0.00" value="<?php echo esc_attr( $edit_task ? $edit_task->budget : '' ); ?>"></div>
                        <p class="hint">Funds are held until you mark the task complete.</p>
                    </div>
                    <div style="display:flex;gap:12px;">
                        <button type="button" id="mb-draft-btn" class="cancel-btn2" style="flex:1;">Save as Draft</button>
                        <button type="button" id="mb-publish-btn" class="post-submit-btn" style="flex:2;"><?php echo $edit_task ? 'Update & Publish' : 'Publish Task'; ?></button>
                    </div>
                </div>
            </form>
            <div id="pt-msg"></div>
        </div>
    </div>
    <script>
    (function() {
        bntmMbSkillTagInput('pt-skill-wrap', 'pt-skills-hidden', <?php echo wp_json_encode( array_map( 'intval', $edit_skills ) ); ?>);

        function submit(mode, btn) {
            var form = document.getElementById('mb-post-task-form');
            if (!form.reportValidity()) return;
            var fd = new FormData(form);
            fd.append('action', 'mb_create_task'); fd.append('mode', mode); fd.append('nonce', mbNonce);
            btn.disabled = true; var orig = btn.textContent; btn.textContent = mode === 'publish' ? 'Publishing...' : 'Saving...';
            fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(json) {
                bntmMbToast('pt-msg', json.data.message, json.success);
                btn.disabled = false; btn.textContent = orig;
                if (json.success) setTimeout(function() { window.location.href = '?tab=manage_tasks'; }, 1200);
            });
        }
        document.getElementById('mb-draft-btn').addEventListener('click', function() { submit('draft', this); });
        document.getElementById('mb-publish-btn').addEventListener('click', function() { submit('publish', this); });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_client_manage_tasks_tab( $user_id ) {
    global $wpdb;
    $tasks     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE client_id=%d ORDER BY created_at DESC", $user_id ) );
    $checkout_p = get_page_by_path( 'checkout-billing' );
    $checkout_u = $checkout_p ? get_permalink( $checkout_p->ID ) : '#';

    $pill_class = [
        'draft'       => 'st-draft',
        'live'        => 'st-live',
        'in_progress' => 'st-live',
        'completed'   => 'st-completed',
        'cancelled'   => 'st-disputed',
        'rejected'    => 'st-disputed',
        'withdrawn'   => 'st-disputed',
    ];

    ob_start(); ?>
    <div class="manage-wrap">
        <div class="manage-inner">
            <h1>Manage Your Tasks</h1>
            <p class="manage-sub">Track your postings, review applicants, and manage completions.</p>
            <div class="manage-toolbar">
                <div class="manage-search"><input type="text" id="mb-manage-search" placeholder="Search your tasks..."><?php echo mb_cv2_icon( 'search', 18 ); ?></div>
            </div>
            <?php if ( empty( $tasks ) ): ?>
                <p style="color:var(--muted);">No tasks yet. <a href="?tab=post_task" style="color:var(--teal-dark);font-weight:600;">Post your first task</a>.</p>
            <?php else: ?>
            <div class="task-grid" id="mb-task-grid">
                <?php foreach ( $tasks as $t ): $pc = isset( $pill_class[ $t->status ] ) ? $pill_class[ $t->status ] : 'st-draft'; ?>
                <?php $already_rated = $t->status === 'completed' ? mb_has_task_review( $t->id, $user_id ) : false; ?>
                <div class="task-card" data-title="<?php echo esc_attr( strtolower( $t->title ) ); ?>">
                    <h3><?php echo esc_html( $t->title ); ?></h3>
                    <div class="task-status-row"><span class="label">Status:</span><span class="status-pill <?php echo esc_attr( $pc ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $t->status ) ) ); ?></span></div>
                    <p style="color:var(--muted);font-size:13px;margin-bottom:8px;"><?php echo esc_html( wp_trim_words( $t->description, 18 ) ); ?></p>
                    <?php if ( $t->ready_for_review ): ?><div class="task-cat-pill" style="color:#b45309;background:#fef3c7;">Provider ready for review</div><?php endif; ?>
                    <div class="task-budget">Budget: <?php echo mb_price( $t->budget ); ?></div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php if ( $t->status === 'draft' ): ?>
                            <a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'post_task', 'edit_task' => (int) $t->id ] ) ); ?>" class="action-btn" style="flex:1;text-align:center;text-decoration:none;">Edit Draft</a>
                            <button type="button" class="action-btn mb-cancel-task" data-id="<?php echo (int) $t->id; ?>" style="flex:1;color:#b91c1c;">Cancel</button>
                        <?php elseif ( $t->status === 'live' ): ?>
                            <button type="button" class="action-btn mb-view-apps" data-id="<?php echo (int) $t->id; ?>" style="flex:1;">View Applicants</button>
                            <button type="button" class="action-btn mb-cancel-task" data-id="<?php echo (int) $t->id; ?>" style="flex:1;color:#b91c1c;">Cancel</button>
                        <?php elseif ( $t->status === 'in_progress' ): ?>
                            <button type="button" class="action-btn mb-done-task" data-id="<?php echo (int) $t->id; ?>" style="flex:1;">Mark Completed</button>
                            <a href="<?php echo esc_url( $checkout_u ); ?>" class="action-btn" style="flex:1;text-align:center;text-decoration:none;">Checkout/Billing</a>
                        <?php elseif ( $t->status === 'completed' && ! empty( $t->provider_id ) && ! $already_rated ): ?>
                            <button type="button" class="action-btn mb-review-btn" data-task="<?php echo (int) $t->id; ?>" data-role="client" data-reviewee="<?php echo (int) $t->provider_id; ?>" style="flex:1;">Rate Provider</button>
                        <?php elseif ( $t->status === 'completed' && $already_rated ): ?>
                            <span class="status-pill st-completed" style="display:inline-flex;align-items:center;justify-content:center;flex:1;">Completed &amp; Rated</span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-apps-panel" id="apps-panel-<?php echo (int) $t->id; ?>" style="display:none;margin-top:14px;border-top:1px solid rgba(97,120,118,.2);padding-top:12px;"></div>
                </div>
                <?php endforeach; ?>
                <a href="?tab=post_task" class="new-task-tile"><?php echo mb_cv2_icon( 'plus', 40 ); ?><p>Post New Task</p></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    (function() {
        var searchInp = document.getElementById('mb-manage-search');
        if (searchInp) {
            searchInp.addEventListener('input', function() {
                var q = this.value.trim().toLowerCase();
                document.querySelectorAll('#mb-task-grid .task-card').forEach(function(card) {
                    card.style.display = card.dataset.title.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        }
        document.querySelectorAll('.mb-view-apps').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tid   = this.dataset.id;
                var panel = document.getElementById('apps-panel-' + tid);
                if (panel.style.display === 'block') { panel.style.display = 'none'; return; }
                panel.style.display = 'block';
                panel.innerHTML = '<p style="color:var(--muted);">Loading...</p>';
                var fd = new FormData();
                fd.append('action', 'mb_get_applicants'); fd.append('task_id', tid); fd.append('nonce', mbNonce);
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(json) {
                    if (!json.success || !json.data.applicants.length) { panel.innerHTML = '<p style="color:var(--muted);">' + (!json.success ? json.data.message : 'No applicants yet.') + '</p>'; return; }
                    var html = '';
                    json.data.applicants.forEach(function(a) {
                        html += '<div class="bntm-applicant-row"><div><strong>' + a.name + '</strong><div style="font-size:12px;color:var(--muted);">' + a.cover_message + '</div><div style="font-size:12px;margin-top:4px;">Proposed: &#8369;' + a.proposed_rate + '</div></div><div>';
                        if (a.status === 'pending') {
                            html += '<button class="action-btn mb-accept" data-app="' + a.id + '" data-task="' + tid + '" style="width:auto;padding:6px 14px;">Accept</button> <button class="action-btn mb-reject" data-app="' + a.id + '" style="width:auto;padding:6px 14px;color:#b91c1c;">Reject</button>';
                        } else { html += '<span class="status-pill st-completed">' + a.status + '</span>'; }
                        html += '</div></div>';
                    });
                    panel.innerHTML = html;
                    panel.querySelectorAll('.mb-accept').forEach(function(b) {
                        b.addEventListener('click', function() {
                            if (!confirm('Accept this applicant? Others will be rejected.')) return;
                            var fd2 = new FormData(); fd2.append('action','mb_accept_applicant'); fd2.append('application_id',this.dataset.app); fd2.append('task_id',this.dataset.task); fd2.append('nonce',mbNonce);
                            fetch(ajaxurl,{method:'POST',body:fd2}).then(function(r){return r.json();}).then(function(j){alert(j.data.message);if(j.success)location.reload();});
                        });
                    });
                    panel.querySelectorAll('.mb-reject').forEach(function(b) {
                        b.addEventListener('click', function() {
                            var fd2 = new FormData(); fd2.append('action','mb_reject_applicant'); fd2.append('application_id',this.dataset.app); fd2.append('nonce',mbNonce);
                            var row = this.closest('.bntm-applicant-row');
                            fetch(ajaxurl,{method:'POST',body:fd2}).then(function(r){return r.json();}).then(function(j){if(j.success)row.remove();else alert(j.data.message);});
                        });
                    });
                });
            });
        });
        document.querySelectorAll('.mb-cancel-task').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Cancel this task?')) return;
                var fd = new FormData(); fd.append('action','mb_cancel_task'); fd.append('task_id',this.dataset.id); fd.append('nonce',mbNonce);
                fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){alert(j.data.message);if(j.success)location.reload();});
            });
        });
        document.querySelectorAll('.mb-done-task').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Mark as completed?')) return;
                var fd = new FormData(); fd.append('action','mb_complete_task'); fd.append('task_id',this.dataset.id); fd.append('nonce',mbNonce);
                fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){alert(j.data.message);if(j.success)location.reload();});
            });
        });
    })();
    </script>
    <?php echo mb_render_review_modal_shell(); ?>
    <?php
    return ob_get_clean();
}

/* ==========================================================================
   I. PROVIDER TAB FUNCTIONS
========================================================================== */

function mb_provider_feed_tab( $user_id ) {
    global $wpdb;
    $skills = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}mb_skills WHERE status='active' ORDER BY category,name" );
    ob_start(); ?>
    <div class="bntm-form-section">
        <h3>Home / Task Feed</h3>
        <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
            <select id="mb-feed-skill">
                <option value="">All Skills</option>
                <?php foreach ( $skills as $s ): ?><option value="<?php echo $s->id; ?>"><?php echo esc_html( $s->name ); ?></option><?php endforeach; ?>
            </select>
            <input type="text" id="mb-feed-search" placeholder="Search tasks...">
        </div>
        <div id="mb-feed-box"><p style="color:#6b7280;">Loading tasks...</p></div>
    </div>

    <div id="mb-apply-modal" class="bntm-modal" style="display:none;">
        <div class="bntm-modal-content">
            <h3>Apply to Task</h3>
            <form id="mb-apply-form">
                <input type="hidden" name="task_id" id="mb-apply-tid">
                <div class="bntm-form-group"><label>Proposed Rate (&#8369;) *</label><input type="number" name="proposed_rate" min="0" step="0.01" required></div>
                <div class="bntm-form-group"><label>Cover Message *</label><textarea name="cover_message" rows="4" required placeholder="Introduce yourself and explain your approach."></textarea></div>
                <div style="display:flex;gap:8px;"><button type="submit" class="bntm-btn-primary">Submit</button><button type="button" id="mb-apply-close" class="bntm-btn-secondary">Cancel</button></div>
            </form>
            <div id="apply-msg"></div>
        </div>
    </div>

    <script>
    (function() {
        function loadFeed() {
            var fd = new FormData();
            fd.append('action','mb_filter_feed'); fd.append('skill_id',document.getElementById('mb-feed-skill').value); fd.append('search',document.getElementById('mb-feed-search').value); fd.append('nonce',mbNonce);
            fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(json){
                var box = document.getElementById('mb-feed-box');
                if (!json.success || !json.data.tasks.length) { box.innerHTML='<p style="color:#6b7280;">No matching tasks right now.</p>'; return; }
                var html = '';
                json.data.tasks.forEach(function(t) {
                    html += '<div class="bntm-task-card"><div class="bntm-task-card-head"><h4>'+t.title+'</h4><span>'+t.budget_fmt+'</span></div><p style="color:#4b5563;font-size:13px;">'+t.description+'</p><div>'+t.skills.map(function(s){return '<span class="bntm-skill-pill">'+s+'</span>';}).join('')+'</div><div class="bntm-task-meta"><span>by '+t.client_name+'</span><span>'+t.posted_ago+'</span></div>';
                    html += t.already_applied ? '<div style="margin-top:10px;"><span class="bntm-status-badge bntm-status-pending">Already Applied</span></div>' : '<div style="margin-top:10px;"><button class="bntm-btn-small bntm-btn-primary mb-open-apply" data-id="'+t.id+'">Apply</button></div>';
                    html += '</div>';
                });
                box.innerHTML = html;
                box.querySelectorAll('.mb-open-apply').forEach(function(b){
                    b.addEventListener('click',function(){ document.getElementById('mb-apply-tid').value=this.dataset.id; document.getElementById('mb-apply-modal').style.display='flex'; });
                });
            });
        }
        document.getElementById('mb-feed-skill').addEventListener('change', loadFeed);
        var st; document.getElementById('mb-feed-search').addEventListener('input',function(){clearTimeout(st);st=setTimeout(loadFeed,350);});
        document.getElementById('mb-apply-close').addEventListener('click',function(){document.getElementById('mb-apply-modal').style.display='none';});
        document.getElementById('mb-apply-form').addEventListener('submit',function(e){
            e.preventDefault();
            var fd=new FormData(this); fd.append('action','mb_apply_to_task'); fd.append('nonce',mbNonce);
            var btn=this.querySelector('button[type="submit"]'); btn.disabled=true; btn.textContent='Submitting...';
            fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(json){
                bntmMbToast('apply-msg',json.data.message,json.success); btn.disabled=false; btn.textContent='Submit';
                if(json.success){setTimeout(function(){document.getElementById('mb-apply-modal').style.display='none';loadFeed();},1000);}
            });
        });
        loadFeed();
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_provider_applications_tab( $user_id ) {
    global $wpdb;
    $apps = $wpdb->get_results( $wpdb->prepare(
        "SELECT a.*, t.title, t.budget FROM {$wpdb->prefix}mb_task_applications a INNER JOIN {$wpdb->prefix}mb_tasks t ON a.task_id=t.id WHERE a.provider_id=%d ORDER BY a.applied_at DESC", $user_id
    ) );
    ob_start(); ?>
    <div class="bntm-form-section">
        <h3>My Applications</h3>
        <?php if ( empty( $apps ) ): ?>
            <p style="color:#6b7280;">No applications yet. Browse the Task Feed to apply.</p>
        <?php else: ?>
        <div class="bntm-table-wrapper"><table class="bntm-table">
            <thead><tr><th>Task</th><th>Proposed Rate</th><th>Applied</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ( $apps as $a ): ?>
                <tr>
                    <td><?php echo esc_html( $a->title ); ?></td>
                    <td><?php echo mb_price( $a->proposed_rate ); ?></td>
                    <td><?php echo esc_html( human_time_diff( strtotime( $a->applied_at ), current_time( 'timestamp' ) ) ); ?> ago</td>
                    <td><span class="bntm-status-badge bntm-status-<?php echo esc_attr( $a->status ); ?>"><?php echo esc_html( $a->status ); ?></span></td>
                    <td><?php if ( $a->status === 'pending' ): ?><button class="bntm-btn-small bntm-btn-danger mb-withdraw" data-id="<?php echo $a->id; ?>">Withdraw</button><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
    <script>
    (function(){
        document.querySelectorAll('.mb-withdraw').forEach(function(btn){
            btn.addEventListener('click',function(){
                if(!confirm('Withdraw this application?')) return;
                var fd=new FormData(); fd.append('action','mb_withdraw_application'); fd.append('application_id',this.dataset.id); fd.append('nonce',mbNonce);
                fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){alert(j.data.message);if(j.success)location.reload();});
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_provider_active_jobs_tab( $user_id ) {
    global $wpdb;
    $jobs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE provider_id=%d AND status='in_progress' ORDER BY updated_at DESC", $user_id ) );
    ob_start(); ?>
    <div class="bntm-form-section">
        <h3>Active Jobs</h3>
        <?php if ( empty( $jobs ) ): ?>
            <p style="color:#6b7280;">No active jobs right now.</p>
        <?php else: foreach ( $jobs as $t ): ?>
            <div class="bntm-task-card">
                <div class="bntm-task-card-head"><h4><?php echo esc_html( $t->title ); ?></h4><span class="bntm-status-badge bntm-status-in_progress">In Progress</span></div>
                <p style="color:#4b5563;font-size:13px;"><?php echo esc_html( wp_trim_words( $t->description, 20 ) ); ?></p>
                <div class="bntm-task-meta"><span><?php echo mb_price( $t->budget ); ?></span></div>
                <div style="margin-top:10px;">
                    <?php if ( $t->ready_for_review ): ?>
                        <span class="bntm-status-badge bntm-status-pending">Awaiting client review</span>
                    <?php else: ?>
                        <button class="bntm-btn-small bntm-btn-primary mb-ready" data-id="<?php echo $t->id; ?>">Mark Ready for Review</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <script>
    (function(){
        document.querySelectorAll('.mb-ready').forEach(function(btn){
            btn.addEventListener('click',function(){
                var fd=new FormData(); fd.append('action','mb_mark_ready_for_review'); fd.append('task_id',this.dataset.id); fd.append('nonce',mbNonce);
                fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){alert(j.data.message);if(j.success)location.reload();});
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_provider_manage_tasks_tab( $user_id ) {
    global $wpdb;
    $tasks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE provider_id=%d ORDER BY updated_at DESC", $user_id ) );
    ob_start(); ?>
    <div class="bntm-form-section">
        <h3>Manage Tasks</h3>
        <?php if ( empty( $tasks ) ): ?>
            <p style="color:#6b7280;">No assigned tasks yet.</p>
        <?php else: foreach ( $tasks as $t ): ?>
            <div class="bntm-task-card">
                <div class="bntm-task-card-head"><h4><?php echo esc_html( $t->title ); ?></h4><span class="bntm-status-badge bntm-status-<?php echo esc_attr( $t->status ); ?>"><?php echo esc_html( str_replace( '_', ' ', $t->status ) ); ?></span></div>
                <div class="bntm-task-meta"><span><?php echo mb_price( $t->budget ); ?></span><span>Updated <?php echo esc_html( human_time_diff( strtotime( $t->updated_at ), current_time( 'timestamp' ) ) ); ?> ago</span></div>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/* ==========================================================================
   J. SHARED TAB FUNCTIONS (PROFILE + SETTINGS)
========================================================================== */

function mb_profile_tab( $user_id ) {
    global $wpdb;
    $profile = mb_get_profile( $user_id );
    $user    = get_userdata( $user_id );
    $is_prov = $profile && $profile->user_role === 'provider';

    $my_skills = $is_prov ? $wpdb->get_results( $wpdb->prepare(
        "SELECT s.id, s.name FROM {$wpdb->prefix}mb_provider_skills ps INNER JOIN {$wpdb->prefix}mb_skills s ON ps.skill_id=s.id WHERE ps.user_id=%d AND ps.status='active'", $user_id
    ) ) : [];
    $init_skills = array_map( function( $s ) { return [ 'id' => $s->id, 'name' => $s->name ]; }, $my_skills );

    $pub_p = get_page_by_path( 'public-profile-view' );
    $pub_u = $pub_p ? get_permalink( $pub_p->ID ) . '?mb_user=' . $user_id : '';

    ob_start(); ?>
    <div class="bntm-form-section">
        <h3>Profile</h3>
        <div style="display:flex;gap:18px;align-items:center;margin-bottom:18px;">
            <img class="bntm-avatar-lg" src="<?php echo esc_url( $profile->pfp_url ?: 'https://www.gravatar.com/avatar/?d=mp' ); ?>" alt="">
            <div><input type="file" id="mb-avatar-inp" accept="image/*"><div id="avatar-msg"></div></div>
        </div>
        <form id="mb-profile-form" class="bntm-form">
            <div class="bntm-form-group"><label>Full Name</label><input type="text" name="full_name" value="<?php echo esc_attr( $user->display_name ); ?>"></div>
            <div class="bntm-form-group"><label>Bio<?php echo $is_prov ? ' / Portfolio Note' : ''; ?></label><textarea name="bio" rows="4"><?php echo esc_textarea( $profile->bio ); ?></textarea></div>
            <button type="submit" class="bntm-btn-primary">Save Profile</button>
        </form>
        <div id="profile-msg"></div>
        <?php if ( $pub_u ): ?><p style="margin-top:14px;font-size:13px;"><a href="<?php echo esc_url( $pub_u ); ?>" target="_blank">View Public Profile &rarr;</a></p><?php endif; ?>
    </div>

    <?php if ( $is_prov ): ?>
    <div class="bntm-form-section">
        <h3>My Skills</h3>
        <div class="bntm-skill-tag-input" id="prov-skill-wrap"><input type="text" placeholder="Type to search or add skills..."></div>
        <input type="hidden" id="prov-skills-hidden">
        <button id="mb-save-skills" class="bntm-btn-primary" style="margin-top:12px;">Save Skills</button>
        <div id="skills-msg"></div>
    </div>
    <?php endif; ?>

    <script>
    (function(){
        document.getElementById('mb-profile-form').addEventListener('submit',function(e){
            e.preventDefault();
            var fd=new FormData(this); fd.append('action','mb_update_profile'); fd.append('nonce',mbNonce);
            var btn=this.querySelector('button'); btn.disabled=true; btn.textContent='Saving...';
            fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){bntmMbToast('profile-msg',j.data.message,j.success);btn.disabled=false;btn.textContent='Save Profile';});
        });
        document.getElementById('mb-avatar-inp').addEventListener('change',function(){
            if(!this.files[0]) return;
            var fd=new FormData(); fd.append('action','mb_upload_avatar'); fd.append('avatar',this.files[0]); fd.append('nonce',mbNonce);
            fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){bntmMbToast('avatar-msg',j.data.message,j.success);if(j.success)setTimeout(function(){location.reload();},900);});
        });
        <?php if ( $is_prov ): ?>
        bntmMbSkillTagInput('prov-skill-wrap','prov-skills-hidden',<?php echo wp_json_encode( $init_skills ); ?>);
        document.getElementById('mb-save-skills').addEventListener('click',function(){
            var fd=new FormData(); fd.append('action','mb_add_skill'); fd.append('skills',document.getElementById('prov-skills-hidden').value); fd.append('nonce',mbNonce);
            this.disabled=true; this.textContent='Saving...'; var btn=this;
            fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){bntmMbToast('skills-msg',j.data.message,j.success);btn.disabled=false;btn.textContent='Save Skills';});
        });
        <?php endif; ?>
    })();
    </script>
    <?php
    return ob_get_clean();
}

function mb_settings_tab( $user_id, $profile ) {
    $user      = get_userdata( $user_id );
    $checkout_p = get_page_by_path( 'checkout-billing' );
    $checkout_u = $checkout_p ? get_permalink( $checkout_p->ID ) : '#';
    ob_start(); ?>
    <div class="bntm-form-section">
        <h3>Personal Information</h3>
        <form id="mb-acct-form" class="bntm-form">
            <div class="bntm-form-group"><label>Email Address</label><input type="email" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" required></div>
            <button type="submit" class="bntm-btn-primary">Update</button>
        </form>
        <div id="acct-msg"></div>
    </div>

    <div class="bntm-form-section">
        <h3>Account Security</h3>
        <form id="mb-pw-form" class="bntm-form">
            <div class="bntm-form-group"><label>Current Password *</label><input type="password" name="current_password" required></div>
            <div class="bntm-form-group"><label>New Password *</label><input type="password" name="new_password" minlength="8" required></div>
            <button type="submit" class="bntm-btn-primary">Change Password</button>
        </form>
        <div id="pw-msg"></div>
    </div>

    <div class="bntm-form-section">
        <h3>Billing and Payment</h3>
        <p style="color:#6b7280;font-size:14px;">Manage your payment methods and billing history.</p>
        <a href="<?php echo esc_url( $checkout_u ); ?>" class="bntm-btn-secondary">Go to Checkout/Billing</a>
    </div>

    <div class="bntm-form-section">
        <h3>Notifications</h3>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" id="mb-notif" <?php checked( (int) $profile->notif_email, 1 ); ?>>
            Email me about new applicants, status changes, and messages
        </label>
        <div id="notif-msg"></div>
    </div>

    <div class="bntm-form-section">
        <h3>Identity Verification</h3>
        <p>Status: <span class="bntm-status-badge bntm-status-<?php echo $profile->verification_status === 'verified' ? 'completed' : 'pending'; ?>"><?php echo esc_html( ucfirst( $profile->verification_status ) ); ?></span></p>
        <p style="color:#6b7280;font-size:13px;">Full identity verification by MentorBe admins is coming in a later phase.</p>
    </div>

    <script>
    (function(){
        document.getElementById('mb-acct-form').addEventListener('submit',function(e){
            e.preventDefault();
            var fd=new FormData(this); fd.append('action','mb_update_account_info'); fd.append('nonce',mbNonce);
            var btn=this.querySelector('button'); btn.disabled=true;
            fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){bntmMbToast('acct-msg',j.data.message,j.success);btn.disabled=false;});
        });
        document.getElementById('mb-pw-form').addEventListener('submit',function(e){
            e.preventDefault();
            var fd=new FormData(this); fd.append('action','mb_change_password'); fd.append('nonce',mbNonce);
            var btn=this.querySelector('button'); btn.disabled=true;
            fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){bntmMbToast('pw-msg',j.data.message,j.success);btn.disabled=false;if(j.success)e.target.reset();});
        });
        document.getElementById('mb-notif').addEventListener('change',function(){
            var fd=new FormData(); fd.append('action','mb_update_notification_prefs'); fd.append('notif_email',this.checked?'1':'0'); fd.append('nonce',mbNonce);
            fetch(ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){bntmMbToast('notif-msg',j.data.message,j.success);});
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

/* ==========================================================================
   K. AJAX HANDLERS
========================================================================== */

function bntm_ajax_mb_complete_onboarding() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );

    global $wpdb;
    $user_id = get_current_user_id();
    $role    = sanitize_text_field( $_POST['role'] );

    if ( ! in_array( $role, [ 'client', 'provider' ], true ) ) wp_send_json_error( [ 'message' => 'Invalid role' ] );

    $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}mb_user_profiles WHERE user_id=%d", $user_id ) );
    if ( $existing ) wp_send_json_error( [ 'message' => 'Profile already exists' ] );

    $ok = $wpdb->insert( $wpdb->prefix . 'mb_user_profiles', [
        'rand_id' => bntm_rand_id(), 'user_id' => $user_id, 'user_role' => $role,
        'verification_status' => 'unverified', 'notif_email' => 1, 'status' => 'active',
        'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ),
    ], [ '%s','%d','%s','%s','%d','%s','%s','%s' ] );

    if ( ! $ok ) wp_send_json_error( [ 'message' => 'Could not create profile. Please try again.' ] );

    $slug     = $role === 'client' ? 'clients-dashboard' : 'hometask-feed';
    $dest     = get_page_by_path( $slug );
    $redirect = $dest ? get_permalink( $dest->ID ) : home_url( '/' );

    wp_send_json_success( [ 'message' => 'Welcome to MentorBe!', 'redirect' => $redirect ] );
}

function bntm_handle_mb_login() {
    $login_url = mb_find_page_url( '[mb_login]' );

    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) { wp_safe_redirect( $login_url ); exit; }

    if ( ! isset( $_POST['mb_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_login_nonce'] ) ), 'mb-login' ) ) {
        wp_safe_redirect( add_query_arg( 'mb_login_error', 'Security check failed. Please try again.', $login_url ) );
        exit;
    }

    $identifier = isset( $_POST['mb_identifier'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_identifier'] ) ) : '';
    $password   = isset( $_POST['mb_password'] ) ? (string) wp_unslash( $_POST['mb_password'] ) : '';

    if ( ! $identifier || ! $password ) {
        wp_safe_redirect( add_query_arg( 'mb_login_error', 'Please enter your email/username and password.', $login_url ) );
        exit;
    }

    $user = wp_signon( [ 'user_login' => $identifier, 'user_password' => $password, 'remember' => true ], is_ssl() );

    if ( is_wp_error( $user ) ) {
        wp_safe_redirect( add_query_arg( 'mb_login_error', 'Incorrect email/username or password.', $login_url ) );
        exit;
    }

    $profile        = mb_get_profile( $user->ID );
    $onboarding_url = bntm_mb_onboarding_redirect_url( $user->ID, $profile );
    if ( $onboarding_url ) { wp_safe_redirect( $onboarding_url ); exit; }

    wp_safe_redirect( $profile ? bntm_mb_dashboard_url_for_role( $profile->user_role ) : home_url( '/' ) );
    exit;
}

function bntm_handle_mb_forgot_password() {
    $forgot_url = bntm_mb_forgot_password_url();

    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        wp_safe_redirect( $forgot_url );
        exit;
    }

    if ( ! isset( $_POST['mb_forgot_password_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_forgot_password_nonce'] ) ), 'mb-forgot-password' ) ) {
        wp_safe_redirect( add_query_arg( 'mb_fp_error', 'Security check failed. Please try again.', $forgot_url ) );
        exit;
    }

    $identifier = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';
    if ( ! $identifier ) {
        wp_safe_redirect( add_query_arg( 'mb_fp_error', 'Please enter your email address or username.', $forgot_url ) );
        exit;
    }

    $user = is_email( $identifier ) ? get_user_by( 'email', $identifier ) : get_user_by( 'login', $identifier );
    if ( ! $user && is_email( $identifier ) ) {
        $user = get_user_by( 'login', $identifier );
    }

    if ( $user ) {
        $key = get_password_reset_key( $user );
        if ( is_wp_error( $key ) ) {
            wp_safe_redirect( add_query_arg( 'mb_fp_error', 'We could not create a reset link right now. Please try again later.', $forgot_url ) );
            exit;
        }

        $reset_url = bntm_mb_reset_password_url( $user->user_login, $key );
        $subject    = sprintf( '[%s] Password Reset', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
        $message    = "Someone requested a password reset for your MentorBe account.\n\n";
        $message   .= "Reset your password here:\n" . $reset_url . "\n\n";
        $message   .= "If you did not request this, you can safely ignore this email.";
        wp_mail( $user->user_email, $subject, $message );
    }

    wp_safe_redirect( add_query_arg( 'mb_fp_status', 'If an account matches that email or username, a reset link has been sent.', $forgot_url ) );
    exit;
}

function bntm_handle_mb_reset_password() {
    $reset_url = bntm_mb_reset_password_url();

    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        wp_safe_redirect( $reset_url );
        exit;
    }

    if ( ! isset( $_POST['mb_reset_password_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_reset_password_nonce'] ) ), 'mb-reset-password' ) ) {
        wp_safe_redirect( add_query_arg( 'mb_fp_error', 'Security check failed. Please try again.', $reset_url ) );
        exit;
    }

    $login = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
    $key   = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
    $pass  = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
    $pass2 = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

    if ( ! $login || ! $key ) {
        wp_safe_redirect( add_query_arg( 'mb_fp_error', 'Your reset link is missing required data. Please request a new one.', bntm_mb_forgot_password_url() ) );
        exit;
    }

    if ( strlen( $pass ) < 8 ) {
        wp_safe_redirect( add_query_arg( 'mb_fp_error', 'Password must be at least 8 characters.', bntm_mb_reset_password_url( $login, $key ) ) );
        exit;
    }

    if ( $pass !== $pass2 ) {
        wp_safe_redirect( add_query_arg( 'mb_fp_error', 'Passwords do not match.', bntm_mb_reset_password_url( $login, $key ) ) );
        exit;
    }

    $user = check_password_reset_key( $key, $login );
    if ( is_wp_error( $user ) ) {
        wp_safe_redirect( add_query_arg( 'mb_fp_error', 'This reset link is invalid or has expired. Please request a new one.', bntm_mb_forgot_password_url() ) );
        exit;
    }

    reset_password( $user, $pass );
    wp_safe_redirect( add_query_arg( 'mb_login_notice', 'Password reset successfully. You can log in now.', bntm_mb_wp_login_url() ) );
    exit;
}

function bntm_handle_mb_register() {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        wp_safe_redirect( mb_find_page_url( '[mb_register]' ) );
        exit;
    }

    $register_url = mb_find_page_url( '[mb_register]' );
    $role_url     = mb_find_page_url( '[mb_role_choice]' );

    if ( ! isset( $_POST['mb_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_register_nonce'] ) ), 'mb-register' ) ) {
        wp_safe_redirect( add_query_arg( 'mb_reg_error', 'Security check failed. Please try again.', $register_url ) );
        exit;
    }

    $full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
    $email     = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
    $pass      = isset( $_POST['user_pass'] ) ? (string) wp_unslash( $_POST['user_pass'] ) : '';
    $pass2     = isset( $_POST['user_pass_confirm'] ) ? (string) wp_unslash( $_POST['user_pass_confirm'] ) : '';
    $name_parts = preg_split( '/\s+/', trim( $full_name ) );

    if ( ! $full_name || ! is_array( $name_parts ) || count( $name_parts ) < 2 ) {
        wp_safe_redirect( add_query_arg( 'mb_reg_error', 'Please enter your full name using at least two words.', $register_url ) );
        exit;
    }

    if ( ! $email || ! is_email( $email ) ) {
        wp_safe_redirect( add_query_arg( 'mb_reg_error', 'Please enter a valid email address.', $register_url ) );
        exit;
    }

    if ( email_exists( $email ) ) {
        wp_safe_redirect( add_query_arg( 'mb_reg_error', 'That email address is already registered.', $register_url ) );
        exit;
    }

    if ( strlen( $pass ) < 8 ) {
        wp_safe_redirect( add_query_arg( 'mb_reg_error', 'Password must be at least 8 characters.', $register_url ) );
        exit;
    }

    if ( $pass !== $pass2 ) {
        wp_safe_redirect( add_query_arg( 'mb_reg_error', 'Passwords do not match.', $register_url ) );
        exit;
    }

    bntm_mb_set_pending_signup( [
        'full_name' => $full_name,
        'email'     => $email,
        'password'  => $pass,
        'step'      => 'role',
    ] );

    wp_safe_redirect( $role_url ? $role_url : home_url( '/' ) );
    exit;
}

function bntm_handle_mb_select_role() {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        wp_safe_redirect( mb_find_page_url( '[mb_role_choice]' ) );
        exit;
    }

    $role_url    = mb_find_page_url( '[mb_role_choice]' );
    $profile_url = mb_find_page_url( '[mb_profile_setup]' );

    if ( ! isset( $_POST['mb_select_role_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_select_role_nonce'] ) ), 'mb-select-role' ) ) {
        wp_safe_redirect( add_query_arg( 'mb_onboard_error', 'Security check failed. Please try again.', $role_url ) );
        exit;
    }

    $role = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
    if ( ! in_array( $role, [ 'client', 'provider' ], true ) ) {
        wp_safe_redirect( add_query_arg( 'mb_onboard_error', 'Please choose a role to continue.', $role_url ) );
        exit;
    }

    $pending_token = isset( $_POST['pending_token'] ) ? sanitize_text_field( wp_unslash( $_POST['pending_token'] ) ) : '';
    $pending = $pending_token ? bntm_mb_get_pending_signup( $pending_token ) : bntm_mb_get_pending_signup();
    if ( $pending ) {
        $pending['role'] = $role;
        $pending['step']  = 'profile';
        bntm_mb_set_pending_signup( $pending );
        wp_safe_redirect( $profile_url ? $profile_url : home_url( '/' ) );
        exit;
    }

    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( mb_find_page_url( '[mb_register]' ) );
        exit;
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $profile = mb_get_profile( $user_id );

    if ( ! $profile ) {
        $wpdb->insert( $wpdb->prefix . 'mb_user_profiles', [
            'rand_id'             => bntm_rand_id(),
            'user_id'             => $user_id,
            'user_role'           => $role,
            'verification_status'  => 'unverified',
            'notif_email'         => 1,
            'status'              => 'active',
            'created_at'          => current_time( 'mysql' ),
            'updated_at'          => current_time( 'mysql' ),
        ], [ '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s' ] );
    } else {
        $wpdb->update( $wpdb->prefix . 'mb_user_profiles', [
            'user_role'  => $role,
            'updated_at' => current_time( 'mysql' ),
        ], [ 'user_id' => $user_id ], [ '%s', '%s' ], [ '%d' ] );
    }

    update_user_meta( $user_id, 'mb_pending_role', $role );
    update_user_meta( $user_id, 'mb_onboarding_step', 'profile' );

    wp_safe_redirect( $profile_url ? $profile_url : home_url( '/' ) );
    exit;
}

function bntm_handle_mb_profile_setup() {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        wp_safe_redirect( mb_find_page_url( '[mb_profile_setup]' ) );
        exit;
    }

    $profile_url = mb_find_page_url( '[mb_profile_setup]' );

    if ( ! isset( $_POST['mb_profile_setup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_profile_setup_nonce'] ) ), 'mb-profile-setup' ) ) {
        wp_safe_redirect( add_query_arg( 'mb_onboard_error', 'Security check failed. Please try again.', $profile_url ) );
        exit;
    }

    $role          = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
    $region        = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
    $city          = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
    $bio           = isset( $_POST['bio'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bio'] ) ) : '';
    $portfolio_url = isset( $_POST['portfolio_url'] ) ? esc_url_raw( wp_unslash( $_POST['portfolio_url'] ) ) : '';
    $pending_token = isset( $_POST['pending_token'] ) ? sanitize_text_field( wp_unslash( $_POST['pending_token'] ) ) : '';
    $pending       = $pending_token ? bntm_mb_get_pending_signup( $pending_token ) : bntm_mb_get_pending_signup();
    $user_id       = get_current_user_id();

    // Locations are restricted to the Philippines: validate the submitted
    // region/city against the known dataset rather than trusting free text.
    $ph_locations = mb_ph_locations();
    if ( ! isset( $ph_locations[ $region ] ) || ! in_array( $city, $ph_locations[ $region ], true ) ) {
        $region = '';
        $city   = '';
    }
    $location = trim( $city . ( $city && $region ? ', ' : '' ) . $region );

    if ( ! in_array( $role, [ 'client', 'provider' ], true ) ) {
        wp_safe_redirect( add_query_arg( 'mb_onboard_error', 'Please select a valid role first.', mb_find_page_url( '[mb_role_choice]' ) ) );
        exit;
    }

    if ( ! $location || ! $bio ) {
        wp_safe_redirect( add_query_arg( 'mb_onboard_error', 'Please select a valid Philippine city/region and complete your bio.', $profile_url ) );
        exit;
    }

    if ( $portfolio_url && ! wp_http_validate_url( $portfolio_url ) ) {
        wp_safe_redirect( add_query_arg( 'mb_onboard_error', 'Please enter a valid portfolio URL.', $profile_url ) );
        exit;
    }

    global $wpdb;

    if ( $pending ) {
        $email     = isset( $pending['email'] ) ? sanitize_email( $pending['email'] ) : '';
        $full_name = isset( $pending['full_name'] ) ? sanitize_text_field( $pending['full_name'] ) : '';
        $password  = isset( $pending['password'] ) ? (string) $pending['password'] : '';
        $pending_role = ! empty( $pending['role'] ) ? sanitize_text_field( $pending['role'] ) : $role;

        if ( ! $email || ! is_email( $email ) || ! $full_name || ! $password ) {
            bntm_mb_clear_pending_signup( $pending_token );
            wp_safe_redirect( add_query_arg( 'mb_onboard_error', 'Your signup session expired. Please start again.', mb_find_page_url( '[mb_register]' ) ) );
            exit;
        }

        if ( email_exists( $email ) ) {
            bntm_mb_clear_pending_signup( $pending_token );
            wp_safe_redirect( add_query_arg( 'mb_onboard_error', 'That email address is already registered.', mb_find_page_url( '[mb_register]' ) ) );
            exit;
        }

        $name_parts = preg_split( '/\s+/', trim( $full_name ) );
        if ( ! is_array( $name_parts ) || count( $name_parts ) < 2 ) {
            bntm_mb_clear_pending_signup( $pending_token );
            wp_safe_redirect( add_query_arg( 'mb_onboard_error', 'Your signup session is missing profile data. Please register again.', mb_find_page_url( '[mb_register]' ) ) );
            exit;
        }

        $username_base = sanitize_user( sanitize_title( $full_name ), true );
        if ( ! $username_base ) {
            $username_base = sanitize_user( current( $name_parts ), true );
        }
        if ( ! $username_base ) {
            $email_bits = explode( '@', $email );
            $username_base = sanitize_user( $email_bits[0], true );
        }

        $username = $username_base;
        $suffix    = 1;
        while ( username_exists( $username ) ) {
            $username = $username_base . $suffix;
            $suffix++;
        }

        $new_user_id = wp_insert_user( [
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'display_name' => $full_name,
            'first_name'   => $name_parts[0],
            'last_name'    => implode( ' ', array_slice( $name_parts, 1 ) ),
            'role'         => 'subscriber',
        ] );

        if ( is_wp_error( $new_user_id ) ) {
            bntm_mb_clear_pending_signup( $pending_token );
            wp_safe_redirect( add_query_arg( 'mb_onboard_error', $new_user_id->get_error_message(), mb_find_page_url( '[mb_register]' ) ) );
            exit;
        }

        $wpdb->insert( $wpdb->prefix . 'mb_user_profiles', [
            'rand_id'              => bntm_rand_id(),
            'user_id'              => (int) $new_user_id,
            'user_role'            => $pending_role,
            'bio'                  => $bio,
            'verification_status'  => 'unverified',
            'notif_email'          => 1,
            'status'               => 'active',
            'created_at'           => current_time( 'mysql' ),
            'updated_at'           => current_time( 'mysql' ),
        ], [ '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ] );

        update_user_meta( (int) $new_user_id, 'mb_profile_location', $location );
        update_user_meta( (int) $new_user_id, 'mb_profile_city', $city );
        update_user_meta( (int) $new_user_id, 'mb_profile_region', $region );
        update_user_meta( (int) $new_user_id, 'mb_profile_country', 'Philippines' );
        update_user_meta( (int) $new_user_id, 'mb_portfolio_url', $pending_role === 'provider' ? $portfolio_url : '' );
        update_user_meta( (int) $new_user_id, 'mb_onboarding_step', 'complete' );
        delete_user_meta( (int) $new_user_id, 'mb_pending_role' );

        wp_set_current_user( (int) $new_user_id );
        wp_set_auth_cookie( (int) $new_user_id, true );
        bntm_mb_clear_pending_signup( $pending_token );

        $redirect = bntm_mb_dashboard_url_for_role( $pending_role );
        wp_safe_redirect( $redirect );
        exit;
    }

    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( mb_find_page_url( '[mb_register]' ) );
        exit;
    }

    $profile = mb_get_profile( $user_id );
    $data    = [
        'user_role'  => $role,
        'bio'        => $bio,
        'updated_at' => current_time( 'mysql' ),
    ];

    if ( $profile ) {
        $wpdb->update( $wpdb->prefix . 'mb_user_profiles', $data, [ 'user_id' => $user_id ], [ '%s', '%s', '%s' ], [ '%d' ] );
    } else {
        $wpdb->insert( $wpdb->prefix . 'mb_user_profiles', [
            'rand_id'              => bntm_rand_id(),
            'user_id'              => $user_id,
            'user_role'            => $role,
            'bio'                  => $bio,
            'verification_status'  => 'unverified',
            'notif_email'          => 1,
            'status'               => 'active',
            'created_at'           => current_time( 'mysql' ),
            'updated_at'           => current_time( 'mysql' ),
        ], [ '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ] );
    }

    update_user_meta( $user_id, 'mb_profile_location', $location );
    update_user_meta( $user_id, 'mb_profile_city', $city );
    update_user_meta( $user_id, 'mb_profile_region', $region );
    update_user_meta( $user_id, 'mb_profile_country', 'Philippines' );
    update_user_meta( $user_id, 'mb_portfolio_url', $role === 'provider' ? $portfolio_url : '' );
    update_user_meta( $user_id, 'mb_onboarding_step', 'complete' );
    delete_user_meta( $user_id, 'mb_pending_role' );

    $redirect = bntm_mb_dashboard_url_for_role( $role );
    wp_safe_redirect( $redirect );
    exit;
}

function bntm_ajax_mb_search_skills() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $q      = sanitize_text_field( $_POST['query'] );
    $skills = $wpdb->get_results( $wpdb->prepare( "SELECT id,name,category FROM {$wpdb->prefix}mb_skills WHERE status='active' AND name LIKE %s ORDER BY name LIMIT 10", '%' . $wpdb->esc_like( $q ) . '%' ) );
    $exact  = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}mb_skills WHERE LOWER(name)=LOWER(%s)", $q ) );
    wp_send_json_success( [ 'skills' => $skills, 'can_create' => empty( $exact ) ] );
}

function mb_resolve_skills( $json_str ) {
    global $wpdb;
    $list = json_decode( stripslashes( $json_str ), true );
    if ( ! is_array( $list ) ) return [];
    $ids = [];
    foreach ( $list as $s ) {
        if ( ! isset( $s['id'] ) ) continue;
        if ( strpos( (string) $s['id'], 'new:' ) === 0 ) {
            $name = sanitize_text_field( substr( $s['id'], 4 ) );
            if ( ! $name ) continue;
            $eid = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}mb_skills WHERE LOWER(name)=LOWER(%s)", $name ) );
            if ( $eid ) { $ids[] = (int) $eid; }
            else {
                $wpdb->insert( $wpdb->prefix . 'mb_skills', [ 'rand_id' => bntm_rand_id(), 'name' => $name, 'category' => 'General', 'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ], [ '%s','%s','%s','%s','%s','%s' ] );
                $ids[] = (int) $wpdb->insert_id;
            }
        } else { $ids[] = intval( $s['id'] ); }
    }
    return array_unique( array_filter( $ids ) );
}

function bntm_ajax_mb_create_task() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid  = get_current_user_id();
    $prof = mb_get_profile( $uid );
    if ( ! $prof || $prof->user_role !== 'client' ) wp_send_json_error( [ 'message' => 'Only clients can post tasks' ] );
    $title = sanitize_text_field( $_POST['title'] );
    $desc  = sanitize_textarea_field( $_POST['description'] );
    $bgt   = floatval( $_POST['budget'] );
    $mode  = sanitize_text_field( $_POST['mode'] );
    $task_id = intval( $_POST['task_id'] ?? 0 );
    if ( ! $title || ! $desc || $bgt <= 0 ) wp_send_json_error( [ 'message' => 'Please fill in all required fields with a valid budget.' ] );
    $status = $mode === 'publish' ? 'live' : 'draft';
    $wpdb->query( 'START TRANSACTION' );
    try {
        $skills = mb_resolve_skills( $_POST['skills'] ?? '[]' );
        if ( $mode === 'update' && $task_id > 0 ) {
            $task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id=%d AND client_id=%d AND status='draft'", $task_id, $uid ) );
            if ( ! $task ) throw new Exception( 'Draft task not found' );
            $ok = $wpdb->update( $wpdb->prefix . 'mb_tasks', [ 'title' => $title, 'description' => $desc, 'budget' => $bgt, 'status' => $status, 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $task_id ], [ '%s','%s','%f','%s','%s' ], [ '%d' ] );
            if ( $ok === false ) throw new Exception( 'Update failed' );
            $tid = $task_id;
            $wpdb->delete( $wpdb->prefix . 'mb_task_skills', [ 'task_id' => $tid ], [ '%d' ] );
        } else {
            $ok = $wpdb->insert( $wpdb->prefix . 'mb_tasks', [ 'rand_id' => bntm_rand_id(), 'client_id' => $uid, 'title' => $title, 'description' => $desc, 'budget' => $bgt, 'status' => $status, 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ], [ '%s','%d','%s','%s','%f','%s','%s','%s' ] );
            if ( ! $ok ) throw new Exception( 'Insert failed' );
            $tid = $wpdb->insert_id;
        }
        foreach ( $skills as $sid ) {
            $wpdb->insert( $wpdb->prefix . 'mb_task_skills', [ 'rand_id' => bntm_rand_id(), 'task_id' => $tid, 'skill_id' => $sid, 'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ], [ '%s','%d','%d','%s','%s','%s' ] );
        }
        $wpdb->query( 'COMMIT' );
        wp_send_json_success( [ 'message' => $mode === 'publish' ? ( $task_id ? 'Task updated and published!' : 'Task published!' ) : ( $task_id ? 'Draft updated.' : 'Task saved as draft.' ), 'task_id' => $tid ] );
    } catch ( Exception $e ) {
        $wpdb->query( 'ROLLBACK' );
        error_log( 'MB Create Task: ' . $e->getMessage() );
        wp_send_json_error( [ 'message' => 'Failed to create task.' ] );
    }
}

function bntm_ajax_mb_cancel_task() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid = get_current_user_id();
    $tid = intval( $_POST['task_id'] );
    $t   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id=%d AND client_id=%d", $tid, $uid ) );
    if ( ! $t ) wp_send_json_error( [ 'message' => 'Task not found' ] );
    if ( ! in_array( $t->status, [ 'draft', 'live' ], true ) ) wp_send_json_error( [ 'message' => 'Task cannot be cancelled in its current state' ] );
    $wpdb->update( $wpdb->prefix . 'mb_tasks', [ 'status' => 'cancelled', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $tid ], [ '%s','%s' ], [ '%d' ] );
    wp_send_json_success( [ 'message' => 'Task cancelled.' ] );
}

function bntm_ajax_mb_get_applicants() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid = get_current_user_id();
    $tid = intval( $_POST['task_id'] );
    $t   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id=%d AND client_id=%d", $tid, $uid ) );
    if ( ! $t ) wp_send_json_error( [ 'message' => 'Task not found' ] );
    $apps = $wpdb->get_results( $wpdb->prepare( "SELECT id,provider_id,cover_message,proposed_rate,status FROM {$wpdb->prefix}mb_task_applications WHERE task_id=%d ORDER BY applied_at DESC", $tid ) );
    $out  = [];
    foreach ( $apps as $a ) {
        $u    = get_userdata( $a->provider_id );
        $out[] = [ 'id' => $a->id, 'name' => $u ? esc_html( $u->display_name ) : 'Unknown', 'cover_message' => esc_html( $a->cover_message ), 'proposed_rate' => number_format( $a->proposed_rate, 2 ), 'status' => $a->status ];
    }
    wp_send_json_success( [ 'applicants' => $out ] );
}

function bntm_ajax_mb_accept_applicant() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid  = get_current_user_id();
    $aid  = intval( $_POST['application_id'] );
    $tid  = intval( $_POST['task_id'] );
    $task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id=%d AND client_id=%d AND status='live'", $tid, $uid ) );
    if ( ! $task ) wp_send_json_error( [ 'message' => 'Task not available for selection' ] );
    $app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_task_applications WHERE id=%d AND task_id=%d AND status='pending'", $aid, $tid ) );
    if ( ! $app ) wp_send_json_error( [ 'message' => 'Application not found' ] );
    $wpdb->query( 'START TRANSACTION' );
    try {
        $wpdb->update( $wpdb->prefix . 'mb_task_applications', [ 'status' => 'accepted', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $aid ], [ '%s','%s' ], [ '%d' ] );
        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}mb_task_applications SET status='rejected',updated_at=%s WHERE task_id=%d AND id!=%d AND status='pending'", current_time( 'mysql' ), $tid, $aid ) );
        $r = $wpdb->update( $wpdb->prefix . 'mb_tasks', [ 'provider_id' => $app->provider_id, 'status' => 'in_progress', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $tid ], [ '%d','%s','%s' ], [ '%d' ] );
        if ( $r === false ) throw new Exception( 'Update failed' );
        mb_create_notification( $app->provider_id, 'Application accepted', 'Your application for "' . $task->title . '" was accepted. The task is now in progress.', add_query_arg( 'tab', 'jobs', mb_find_page_url( '[mb_provider_dashboard]' ) ), 'task' );
        $wpdb->query( 'COMMIT' );
        wp_send_json_success( [ 'message' => 'Applicant accepted. Task is now In Progress.' ] );
    } catch ( Exception $e ) {
        $wpdb->query( 'ROLLBACK' );
        error_log( 'MB Accept: ' . $e->getMessage() );
        wp_send_json_error( [ 'message' => 'Failed to accept applicant.' ] );
    }
}

function bntm_ajax_mb_reject_applicant() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid = get_current_user_id();
    $aid = intval( $_POST['application_id'] );
    $app = $wpdb->get_row( $wpdb->prepare( "SELECT a.*,t.client_id,t.title AS task_title FROM {$wpdb->prefix}mb_task_applications a INNER JOIN {$wpdb->prefix}mb_tasks t ON a.task_id=t.id WHERE a.id=%d", $aid ) );
    if ( ! $app || (int) $app->client_id !== $uid ) wp_send_json_error( [ 'message' => 'Application not found' ] );
    if ( $app->status !== 'pending' ) wp_send_json_error( [ 'message' => 'Already processed' ] );
    $wpdb->update( $wpdb->prefix . 'mb_task_applications', [ 'status' => 'rejected', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $aid ], [ '%s','%s' ], [ '%d' ] );
    mb_create_notification( $app->provider_id, 'Application declined', 'Your application for "' . $app->task_title . '" was not selected this time.', add_query_arg( 'tab', 'applications', mb_find_page_url( '[mb_provider_dashboard]' ) ), 'task' );
    wp_send_json_success( [ 'message' => 'Applicant rejected.' ] );
}

function bntm_ajax_mb_complete_task() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid = get_current_user_id();
    $tid = intval( $_POST['task_id'] );
    $t   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id=%d AND client_id=%d AND status='in_progress'", $tid, $uid ) );
    if ( ! $t ) wp_send_json_error( [ 'message' => 'Task not available for completion' ] );
    $wpdb->update( $wpdb->prefix . 'mb_tasks', [ 'status' => 'completed', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $tid ], [ '%s','%s' ], [ '%d' ] );
    mb_record_task_payout( $t );
    mb_create_notification( $t->provider_id, 'Task completed', 'The client marked "' . $t->title . '" as completed. Your payout is now pending release.', mb_find_page_url( '[mb_earnings]' ), 'payment' );
    mb_create_notification( $uid, 'Task completed', 'You marked "' . $t->title . '" as completed. Leave a rating for the provider when ready.', mb_find_page_url( '[mb_client_dashboard]' ), 'task' );
    wp_send_json_success( [ 'message' => 'Task marked as completed.' ] );
}

function bntm_ajax_mb_filter_feed() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid     = get_current_user_id();
    $prof    = mb_get_profile( $uid );
    if ( ! $prof || $prof->user_role !== 'provider' ) wp_send_json_error( [ 'message' => 'Only providers can browse the task feed' ] );
    $skill   = intval( $_POST['skill_id'] );
    $search  = sanitize_text_field( $_POST['search'] );
    $sql     = "SELECT DISTINCT t.* FROM {$wpdb->prefix}mb_tasks t";
    $where   = [ "t.status='live'" ];
    $params  = [];
    if ( $skill )  { $sql .= " INNER JOIN {$wpdb->prefix}mb_task_skills ts ON ts.task_id=t.id"; $where[] = "ts.skill_id=%d"; $params[] = $skill; }
    if ( $search ) { $where[] = "(t.title LIKE %s OR t.description LIKE %s)"; $params[] = '%' . $wpdb->esc_like( $search ) . '%'; $params[] = '%' . $wpdb->esc_like( $search ) . '%'; }
    $sql    .= ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY t.created_at DESC LIMIT 30';
    $tasks   = empty( $params ) ? $wpdb->get_results( $sql ) : $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
    $applied = $wpdb->get_col( $wpdb->prepare( "SELECT task_id FROM {$wpdb->prefix}mb_task_applications WHERE provider_id=%d", $uid ) );
    $out     = [];
    foreach ( $tasks as $t ) {
        $sn    = $wpdb->get_col( $wpdb->prepare( "SELECT s.name FROM {$wpdb->prefix}mb_task_skills ts INNER JOIN {$wpdb->prefix}mb_skills s ON ts.skill_id=s.id WHERE ts.task_id=%d", $t->id ) );
        $cat_n = (string) $wpdb->get_var( $wpdb->prepare( "SELECT s.category FROM {$wpdb->prefix}mb_task_skills ts INNER JOIN {$wpdb->prefix}mb_skills s ON ts.skill_id=s.id WHERE ts.task_id=%d ORDER BY s.id ASC LIMIT 1", $t->id ) );
        $cat_l = strtolower( $cat_n );
        $cat_s = 'all';
        if ( $cat_l ) {
            if ( strpos( $cat_l, 'tech' ) !== false || strpos( $cat_l, 'mentor' ) !== false || strpos( $cat_l, 'educat' ) !== false || strpos( $cat_l, 'tutor' ) !== false ) {
                $cat_s = 'tech';
            } elseif ( strpos( $cat_l, 'creative' ) !== false || strpos( $cat_l, 'design' ) !== false || strpos( $cat_l, 'media' ) !== false ) {
                $cat_s = 'creative';
            } elseif ( strpos( $cat_l, 'home' ) !== false || strpos( $cat_l, 'repair' ) !== false || strpos( $cat_l, 'clean' ) !== false || strpos( $cat_l, 'plumb' ) !== false || strpos( $cat_l, 'elect' ) !== false ) {
                $cat_s = 'home';
            } elseif ( strpos( $cat_l, 'errand' ) !== false || strpos( $cat_l, 'delivery' ) !== false || strpos( $cat_l, 'admin' ) !== false || strpos( $cat_l, 'business' ) !== false ) {
                $cat_s = 'errands';
            } else {
                $cat_s = sanitize_title( $cat_n );
            }
        }
        $cl    = get_userdata( $t->client_id );
        $out[] = [ 'id' => $t->id, 'title' => esc_html( $t->title ), 'description' => esc_html( wp_trim_words( $t->description, 20 ) ), 'budget_fmt' => mb_price( $t->budget ), 'skills' => array_map( 'esc_html', $sn ), 'cat' => $cat_s, 'client_name' => $cl ? esc_html( $cl->display_name ) : 'Client', 'posted_ago' => human_time_diff( strtotime( $t->created_at ), current_time( 'timestamp' ) ) . ' ago', 'already_applied' => in_array( (string) $t->id, array_map( 'strval', $applied ), true ) ];
    }
    wp_send_json_success( [ 'tasks' => $out ] );
}

function bntm_ajax_mb_apply_to_task() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid   = get_current_user_id();
    $prof  = mb_get_profile( $uid );
    if ( ! $prof || $prof->user_role !== 'provider' ) wp_send_json_error( [ 'message' => 'Only providers can apply' ] );
    $tid   = intval( $_POST['task_id'] );
    $rate  = floatval( $_POST['proposed_rate'] );
    $cover = sanitize_textarea_field( $_POST['cover_message'] );
    if ( $rate <= 0 || ! $cover ) wp_send_json_error( [ 'message' => 'Please provide a valid rate and cover message.' ] );
    $task  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id=%d AND status='live'", $tid ) );
    if ( ! $task ) wp_send_json_error( [ 'message' => 'Task is no longer accepting applications' ] );
    if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}mb_task_applications WHERE task_id=%d AND provider_id=%d", $tid, $uid ) ) ) wp_send_json_error( [ 'message' => 'You already applied to this task' ] );
    $ok = $wpdb->insert( $wpdb->prefix . 'mb_task_applications', [ 'rand_id' => bntm_rand_id(), 'task_id' => $tid, 'provider_id' => $uid, 'cover_message' => $cover, 'proposed_rate' => $rate, 'status' => 'pending', 'applied_at' => current_time( 'mysql' ), 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ], [ '%s','%d','%d','%s','%f','%s','%s','%s','%s' ] );
    if ( $ok ) wp_send_json_success( [ 'message' => 'Application submitted!' ] );
    else wp_send_json_error( [ 'message' => 'Failed to submit application.' ] );
}

function bntm_ajax_mb_withdraw_application() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid = get_current_user_id();
    $aid = intval( $_POST['application_id'] );
    $app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_task_applications WHERE id=%d AND provider_id=%d AND status='pending'", $aid, $uid ) );
    if ( ! $app ) wp_send_json_error( [ 'message' => 'Application not found or cannot be withdrawn' ] );
    $wpdb->update( $wpdb->prefix . 'mb_task_applications', [ 'status' => 'withdrawn', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $aid ], [ '%s','%s' ], [ '%d' ] );
    wp_send_json_success( [ 'message' => 'Application withdrawn.' ] );
}

function bntm_ajax_mb_get_conversations() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid = get_current_user_id();
    $filter = sanitize_text_field( $_POST['filter'] ?? 'all' );

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mb_chat_conversations WHERE user_a_id=%d OR user_b_id=%d ORDER BY COALESCE(last_message_at,created_at) DESC",
        $uid, $uid
    ) );

    $out = [];
    foreach ( $rows as $c ) {
        $is_a     = (int) $c->user_a_id === $uid;
        $other_id = $is_a ? (int) $c->user_b_id : (int) $c->user_a_id;
        $unread   = $is_a ? (int) $c->unread_for_a : (int) $c->unread_for_b;
        if ( $filter === 'unread' && ! $unread ) continue;
        $u  = get_userdata( $other_id );
        $pf = mb_get_profile( $other_id );
        $profile_view = mb_find_page_url( '[mb_profile_view]' );
        $out[] = [
            'id'     => $c->id,
            'name'   => $u ? esc_html( $u->display_name ) : 'Unknown user',
            'avatar' => $pf && $pf->pfp_url ? esc_url( $pf->pfp_url ) : '',
            'last'   => $c->last_message ? esc_html( wp_trim_words( $c->last_message, 8 ) ) : 'No messages yet',
            'time'   => $c->last_message_at ? human_time_diff( strtotime( $c->last_message_at ), current_time( 'timestamp' ) ) : '',
            'profile_url' => $profile_view ? add_query_arg( 'mb_user', $other_id, $profile_view ) : '#',
            'unread' => (bool) $unread,
        ];
    }
    wp_send_json_success( [ 'conversations' => $out ] );
}

function bntm_ajax_mb_get_messages() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid = get_current_user_id();
    $cid = intval( $_POST['conversation_id'] );
    $c = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_chat_conversations WHERE id=%d", $cid ) );
    if ( ! $c || ( (int) $c->user_a_id !== $uid && (int) $c->user_b_id !== $uid ) ) wp_send_json_error( [ 'message' => 'Conversation not found' ] );

    $is_a = (int) $c->user_a_id === $uid;
    $wpdb->update( $wpdb->prefix . 'mb_chat_conversations', $is_a ? [ 'unread_for_a' => 0 ] : [ 'unread_for_b' => 0 ], [ 'id' => $cid ], [ '%d' ], [ '%d' ] );

    $other_id = $is_a ? (int) $c->user_b_id : (int) $c->user_a_id;
    $u  = get_userdata( $other_id );
    $profile_view_base = mb_find_page_url( '[mb_profile_view]' );
    $profile_view = $profile_view_base ? add_query_arg( 'mb_user', $other_id, $profile_view_base ) : '#';

    $msgs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_chat_messages WHERE conversation_id=%d ORDER BY created_at ASC", $cid ) );
    $out = [];
    foreach ( $msgs as $m ) {
        $out[] = [ 'mine' => (int) $m->sender_id === $uid, 'body' => esc_html( $m->body ) ];
    }
    wp_send_json_success( [ 'messages' => $out, 'other_name' => $u ? esc_html( $u->display_name ) : 'Unknown user', 'other_profile_url' => $profile_view ] );
}

function bntm_ajax_mb_send_message() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid  = get_current_user_id();
    $cid  = intval( $_POST['conversation_id'] );
    $body = sanitize_textarea_field( $_POST['body'] ?? '' );
    if ( ! $body ) wp_send_json_error( [ 'message' => 'Message cannot be empty' ] );

    $c = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_chat_conversations WHERE id=%d", $cid ) );
    if ( ! $c || ( (int) $c->user_a_id !== $uid && (int) $c->user_b_id !== $uid ) ) wp_send_json_error( [ 'message' => 'Conversation not found' ] );

    $wpdb->insert( $wpdb->prefix . 'mb_chat_messages', [
        'rand_id' => bntm_rand_id(), 'conversation_id' => $cid, 'sender_id' => $uid, 'body' => $body, 'created_at' => current_time( 'mysql' ),
    ], [ '%s','%d','%d','%s','%s' ] );

    $is_a = (int) $c->user_a_id === $uid;
    $wpdb->update( $wpdb->prefix . 'mb_chat_conversations', [
        'last_message' => $body, 'last_message_at' => current_time( 'mysql' ),
        'unread_for_a' => $is_a ? 0 : 1, 'unread_for_b' => $is_a ? 1 : 0, 'updated_at' => current_time( 'mysql' ),
    ], [ 'id' => $cid ], [ '%s','%s','%d','%d','%s' ], [ '%d' ] );

    wp_send_json_success();
}

function bntm_ajax_mb_start_conversation() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    $uid = get_current_user_id();
    $other = intval( $_POST['user_id'] ?? 0 );
    if ( ! $other || $other === $uid ) wp_send_json_error( [ 'message' => 'Invalid recipient' ] );
    $cid = mb_get_or_create_conversation( $uid, $other );
    $inbox_p = get_page_by_path( 'inbox' );
    wp_send_json_success( [ 'redirect' => add_query_arg( 'conv', $cid, $inbox_p ? get_permalink( $inbox_p->ID ) : home_url( '/' ) ) ] );
}

function bntm_ajax_mb_mark_ready_for_review() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid = get_current_user_id();
    $tid = intval( $_POST['task_id'] );
    $t   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id=%d AND provider_id=%d AND status='in_progress'", $tid, $uid ) );
    if ( ! $t ) wp_send_json_error( [ 'message' => 'Task not found' ] );
    $wpdb->update( $wpdb->prefix . 'mb_tasks', [ 'ready_for_review' => 1, 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $tid ], [ '%d','%s' ], [ '%d' ] );
    $task_url = add_query_arg( [ 'tab' => 'manage_tasks', 'task' => $tid ], mb_find_page_url( '[mb_client_dashboard]' ) );
    mb_create_notification( $t->client_id, 'Work ready for review', 'The provider marked "' . $t->title . '" as ready for review.', $task_url, 'task' );
    wp_send_json_success( [ 'message' => 'Marked ready for review. The client has been notified.' ] );
}

function bntm_ajax_mb_add_skill() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid  = get_current_user_id();
    $prof = mb_get_profile( $uid );
    if ( ! $prof || $prof->user_role !== 'provider' ) wp_send_json_error( [ 'message' => 'Only providers can manage skills' ] );
    $ids  = mb_resolve_skills( $_POST['skills'] ?? '[]' );
    $wpdb->query( 'START TRANSACTION' );
    try {
        $wpdb->delete( $wpdb->prefix . 'mb_provider_skills', [ 'user_id' => $uid ], [ '%d' ] );
        foreach ( $ids as $sid ) {
            $wpdb->insert( $wpdb->prefix . 'mb_provider_skills', [ 'rand_id' => bntm_rand_id(), 'user_id' => $uid, 'skill_id' => $sid, 'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ], [ '%s','%d','%d','%s','%s','%s' ] );
        }
        $wpdb->query( 'COMMIT' );
        wp_send_json_success( [ 'message' => 'Skills updated.' ] );
    } catch ( Exception $e ) {
        $wpdb->query( 'ROLLBACK' );
        wp_send_json_error( [ 'message' => 'Failed to update skills.' ] );
    }
}

function bntm_ajax_mb_update_profile() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid  = get_current_user_id();
    $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
    $last_name  = sanitize_text_field( $_POST['last_name'] ?? '' );
    $legacy_name = sanitize_text_field( $_POST['full_name'] ?? '' );
    $headline   = sanitize_text_field( $_POST['headline'] ?? '' );
    $city       = sanitize_text_field( $_POST['city'] ?? '' );
    $region     = sanitize_text_field( $_POST['region'] ?? '' );
    $country    = sanitize_text_field( $_POST['country'] ?? '' );
    $bio  = sanitize_textarea_field( $_POST['bio'] ?? '' );
    if ( ! $first_name && ! $last_name && $legacy_name ) {
        $parts = preg_split( '/\s+/', trim( $legacy_name ), 2 );
        $first_name = $parts[0] ?? '';
        $last_name  = $parts[1] ?? '';
    }
    $name = trim( $first_name . ' ' . $last_name );
    if ( $name ) {
        wp_update_user( [
            'ID'           => $uid,
            'display_name' => $name,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
        ] );
    }
    $wpdb->update( $wpdb->prefix . 'mb_user_profiles', [ 'bio' => $bio, 'updated_at' => current_time( 'mysql' ) ], [ 'user_id' => $uid ], [ '%s','%s' ], [ '%d' ] );
    update_user_meta( $uid, 'mb_profile_headline', $headline );
    update_user_meta( $uid, 'mb_profile_city', $city );
    update_user_meta( $uid, 'mb_profile_region', $region );
    update_user_meta( $uid, 'mb_profile_country', $country ?: 'Philippines' );
    // Keep the legacy composite location string (used by search/profile view) in sync.
    update_user_meta( $uid, 'mb_profile_location', trim( $city . ( $city && $region ? ', ' : '' ) . $region ) );
    wp_send_json_success( [ 'message' => 'Profile updated.' ] );
}

function bntm_ajax_mb_upload_avatar() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    if ( empty( $_FILES['avatar'] ) ) wp_send_json_error( [ 'message' => 'No file uploaded' ] );
    if ( ! in_array( $_FILES['avatar']['type'], [ 'image/jpeg','image/png','image/webp' ], true ) ) wp_send_json_error( [ 'message' => 'Please upload a JPG, PNG, or WEBP image.' ] );
    if ( $_FILES['avatar']['size'] > 2 * 1024 * 1024 ) wp_send_json_error( [ 'message' => 'Image must be under 2MB.' ] );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $id = media_handle_upload( 'avatar', 0 );
    if ( is_wp_error( $id ) ) wp_send_json_error( [ 'message' => 'Upload failed: ' . $id->get_error_message() ] );
    $url = wp_get_attachment_url( $id );
    global $wpdb;
    $wpdb->update( $wpdb->prefix . 'mb_user_profiles', [ 'pfp_url' => $url, 'updated_at' => current_time( 'mysql' ) ], [ 'user_id' => get_current_user_id() ], [ '%s','%s' ], [ '%d' ] );
    wp_send_json_success( [ 'message' => 'Avatar updated.', 'url' => $url ] );
}

function bntm_ajax_mb_upload_cover() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    if ( empty( $_FILES['cover'] ) ) wp_send_json_error( [ 'message' => 'No file uploaded' ] );
    if ( ! in_array( $_FILES['cover']['type'], [ 'image/jpeg', 'image/png', 'image/webp' ], true ) ) {
        wp_send_json_error( [ 'message' => 'Please upload a JPG, PNG, or WEBP image.' ] );
    }
    if ( $_FILES['cover']['size'] > 3 * 1024 * 1024 ) wp_send_json_error( [ 'message' => 'Image must be under 3MB.' ] );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $id = media_handle_upload( 'cover', 0 );
    if ( is_wp_error( $id ) ) wp_send_json_error( [ 'message' => 'Upload failed: ' . $id->get_error_message() ] );
    $url = wp_get_attachment_url( $id );
    update_user_meta( get_current_user_id(), 'mb_cover_url', $url );
    wp_send_json_success( [ 'message' => 'Cover photo updated.', 'url' => $url ] );
}

function bntm_ajax_mb_update_account_info() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    $uid   = get_current_user_id();
    $email = sanitize_email( $_POST['email'] ?? '' );
    $phone = sanitize_text_field( $_POST['phone'] ?? '' );
    if ( ! is_email( $email ) ) wp_send_json_error( [ 'message' => 'Please enter a valid email address.' ] );
    $ex = email_exists( $email );
    if ( $ex && (int) $ex !== $uid ) wp_send_json_error( [ 'message' => 'That email is already in use.' ] );
    $r = wp_update_user( [ 'ID' => $uid, 'user_email' => $email ] );
    if ( is_wp_error( $r ) ) wp_send_json_error( [ 'message' => $r->get_error_message() ] );
    update_user_meta( $uid, 'mb_phone_number', $phone );
    wp_send_json_success( [ 'message' => 'Account information updated.' ] );
}

function bntm_ajax_mb_change_password() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    $uid   = get_current_user_id();
    $cur   = (string) ( $_POST['current_password'] ?? '' );
    $new   = (string) ( $_POST['new_password'] ?? '' );
    $u     = get_userdata( $uid );
    if ( ! $u || ! wp_check_password( $cur, $u->user_pass, $uid ) ) wp_send_json_error( [ 'message' => 'Current password is incorrect.' ] );
    if ( strlen( $new ) < 8 ) wp_send_json_error( [ 'message' => 'New password must be at least 8 characters.' ] );
    wp_set_password( $new, $uid );
    wp_send_json_success( [ 'message' => 'Password changed successfully.' ] );
}

function bntm_ajax_mb_update_notification_prefs() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    global $wpdb;
    $uid = get_current_user_id();
    $val = intval( $_POST['notif_email'] ) ? 1 : 0;
    $prefs = [];
    if ( isset( $_POST['prefs'] ) ) {
        $decoded = json_decode( wp_unslash( $_POST['prefs'] ), true );
        if ( is_array( $decoded ) ) {
            $prefs = $decoded;
        }
    }
    $wpdb->update( $wpdb->prefix . 'mb_user_profiles', [ 'notif_email' => $val, 'updated_at' => current_time( 'mysql' ) ], [ 'user_id' => $uid ], [ '%d','%s' ], [ '%d' ] );
    update_user_meta( $uid, 'mb_notification_prefs', wp_json_encode( $prefs ) );
    wp_send_json_success( [ 'message' => 'Preferences saved.' ] );
}

function bntm_ajax_mb_save_billing_details() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );

    $uid = get_current_user_id();
    $billing_name    = sanitize_text_field( $_POST['billing_name'] ?? '' );
    $billing_street  = sanitize_text_field( $_POST['billing_street'] ?? '' );
    $billing_city    = sanitize_text_field( $_POST['billing_city'] ?? '' );
    $billing_province = sanitize_text_field( $_POST['billing_province'] ?? '' );
    $billing_zip     = sanitize_text_field( $_POST['billing_zip'] ?? '' );
    $billing_country = sanitize_text_field( $_POST['billing_country'] ?? '' );

    update_user_meta( $uid, 'mb_billing_name', $billing_name );
    update_user_meta( $uid, 'mb_billing_street', $billing_street );
    update_user_meta( $uid, 'mb_billing_city', $billing_city );
    update_user_meta( $uid, 'mb_billing_province', $billing_province );
    update_user_meta( $uid, 'mb_billing_zip', $billing_zip );
    update_user_meta( $uid, 'mb_billing_country', $billing_country );

    wp_send_json_success( [ 'message' => 'Billing details updated.' ] );
}

function bntm_ajax_mb_unlink_billing_account() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );

    $uid = get_current_user_id();
    delete_user_meta( $uid, 'mb_gcash_name' );
    delete_user_meta( $uid, 'mb_gcash_number' );
    wp_send_json_success( [ 'message' => 'Billing account unlinked.' ] );
}

function bntm_ajax_mb_submit_verification() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    if ( empty( $_POST['id_type'] ) ) wp_send_json_error( [ 'message' => 'Please select an ID type.' ] );
    if ( empty( $_FILES['id_file']['name'] ) || empty( $_FILES['selfie_file']['name'] ) ) {
        wp_send_json_error( [ 'message' => 'Please upload both your ID image and selfie.' ] );
    }

    $uid = get_current_user_id();
    $id_type = sanitize_text_field( wp_unslash( $_POST['id_type'] ) );

    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    $id_name = '';
    if ( ! empty( $_FILES['id_file']['name'] ) ) {
        $id_id = media_handle_upload( 'id_file', 0 );
        if ( is_wp_error( $id_id ) ) wp_send_json_error( [ 'message' => 'ID upload failed: ' . $id_id->get_error_message() ] );
        $id_name = basename( get_attached_file( $id_id ) );
        update_user_meta( $uid, 'mb_verification_id_attachment', (int) $id_id );
        update_user_meta( $uid, 'mb_verification_id_name', $id_name );
    }

    $selfie_name = '';
    if ( ! empty( $_FILES['selfie_file']['name'] ) ) {
        $selfie_id = media_handle_upload( 'selfie_file', 0 );
        if ( is_wp_error( $selfie_id ) ) wp_send_json_error( [ 'message' => 'Selfie upload failed: ' . $selfie_id->get_error_message() ] );
        $selfie_name = basename( get_attached_file( $selfie_id ) );
        update_user_meta( $uid, 'mb_verification_selfie_attachment', (int) $selfie_id );
        update_user_meta( $uid, 'mb_verification_selfie_name', $selfie_name );
    }

    update_user_meta( $uid, 'mb_verification_id_type', $id_type );
    if ( $id_name || $selfie_name ) {
        update_user_meta( $uid, 'mb_verification_submitted_at', current_time( 'mysql' ) );
    }

    global $wpdb;
    $wpdb->update(
        $wpdb->prefix . 'mb_user_profiles',
        [ 'verification_status' => 'pending', 'updated_at' => current_time( 'mysql' ) ],
        [ 'user_id' => $uid ],
        [ '%s', '%s' ],
        [ '%d' ]
    );

    $admins = $wpdb->get_col( "SELECT user_id FROM {$wpdb->prefix}mb_user_profiles WHERE user_role='admin' AND status='active'" );
    foreach ( $admins as $admin_id ) {
        mb_create_notification( $admin_id, 'New verification submission', 'A user has submitted identity documents for review.', add_query_arg( 'tab', 'users', mb_find_page_url( '[mb_admin]' ) ), 'kyc' );
    }

    wp_send_json_success( [ 'message' => 'Verification submitted! Review within 24 hours.' ] );
}

function bntm_ajax_mb_submit_review() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );

    global $wpdb;
    $uid = get_current_user_id();
    $tid = intval( $_POST['task_id'] ?? 0 );
    $rating = intval( $_POST['rating'] ?? 0 );
    $rating = max( 1, min( 5, $rating ) );
    $review_text = sanitize_textarea_field( $_POST['review_text'] ?? '' );

    $task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE id=%d AND status='completed'", $tid ) );
    if ( ! $task ) {
        wp_send_json_error( [ 'message' => 'Completed task not found.' ] );
    }

    $reviewee_id = 0;
    $reviewer_role = '';
    if ( (int) $task->client_id === $uid ) {
        $reviewee_id = (int) $task->provider_id;
        $reviewer_role = 'client';
    } elseif ( (int) $task->provider_id === $uid ) {
        $reviewee_id = (int) $task->client_id;
        $reviewer_role = 'provider';
    }

    if ( ! $reviewee_id ) {
        wp_send_json_error( [ 'message' => 'You can only review a task you completed.' ] );
    }

    $existing_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}mb_reviews WHERE task_id=%d AND reviewer_id=%d", $tid, $uid ) );
    $data = [
        'task_id'        => $tid,
        'reviewer_id'    => $uid,
        'reviewee_id'    => $reviewee_id,
        'reviewer_role'  => $reviewer_role,
        'rating'         => $rating,
        'review_text'    => $review_text,
        'status'         => 'active',
        'updated_at'     => current_time( 'mysql' ),
    ];

    if ( $existing_id ) {
        $wpdb->update( $wpdb->prefix . 'mb_reviews', $data, [ 'id' => $existing_id ], [ '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s' ], [ '%d' ] );
    } else {
        $wpdb->insert( $wpdb->prefix . 'mb_reviews', array_merge( [
            'rand_id'    => bntm_rand_id(),
            'created_at' => current_time( 'mysql' ),
        ], $data ), [ '%s', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s' ] );
    }

    mb_create_notification( $reviewee_id, 'New review received', 'You received a ' . $rating . '-star review for "' . $task->title . '".', mb_find_page_url( '[mb_profile_view]' ) . '?mb_user=' . $reviewee_id, 'review' );
    wp_send_json_success( [ 'message' => 'Review saved.' ] );
}

function bntm_ajax_mb_admin_update_verification() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    if ( ! current_user_can( 'manage_options' ) && ! is_super_admin() ) {
        $profile = mb_get_profile( get_current_user_id() );
        if ( ! $profile || $profile->user_role !== 'admin' ) {
            wp_send_json_error( [ 'message' => 'Access denied' ] );
        }
    }

    global $wpdb;
    $user_id = intval( $_POST['user_id'] ?? 0 );
    $status = sanitize_text_field( $_POST['status'] ?? '' );
    if ( ! $user_id || ! in_array( $status, [ 'verified', 'rejected' ], true ) ) {
        wp_send_json_error( [ 'message' => 'Invalid verification update' ] );
    }

    $profile = mb_get_profile( $user_id );
    if ( ! $profile ) {
        wp_send_json_error( [ 'message' => 'User profile not found' ] );
    }

    $wpdb->update( $wpdb->prefix . 'mb_user_profiles', [ 'verification_status' => $status, 'updated_at' => current_time( 'mysql' ) ], [ 'user_id' => $user_id ], [ '%s', '%s' ], [ '%d' ] );
    $title = $status === 'verified' ? 'Account verified' : 'Verification rejected';
    $body  = $status === 'verified'
        ? 'Your identity verification has been approved. You can now use the platform normally.'
        : 'Your identity verification needs another look. Please review your documents and resubmit.';
    mb_create_notification( $user_id, $title, $body, mb_find_page_url( '[mb_profile_setup]' ), 'kyc' );

    wp_send_json_success( [ 'message' => 'Verification updated.' ] );
}

function bntm_ajax_mb_browse_filter() {
    global $wpdb;
    $skill  = intval( $_POST['skill_id'] ?? 0 );
    $search = sanitize_text_field( $_POST['search'] ?? '' );
    $sql    = "SELECT DISTINCT t.* FROM {$wpdb->prefix}mb_tasks t";
    $where  = [ "t.status='live'" ];
    $params = [];
    if ( $skill )  { $sql .= " INNER JOIN {$wpdb->prefix}mb_task_skills ts ON ts.task_id=t.id"; $where[] = "ts.skill_id=%d"; $params[] = $skill; }
    if ( $search ) { $where[] = "(t.title LIKE %s OR t.description LIKE %s)"; $params[] = '%' . $wpdb->esc_like( $search ) . '%'; $params[] = '%' . $wpdb->esc_like( $search ) . '%'; }
    $sql   .= ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY t.created_at DESC LIMIT 30';
    $tasks  = empty( $params ) ? $wpdb->get_results( $sql ) : $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
    $out    = [];
    foreach ( $tasks as $t ) {
        $sn    = $wpdb->get_col( $wpdb->prepare( "SELECT s.name FROM {$wpdb->prefix}mb_task_skills ts INNER JOIN {$wpdb->prefix}mb_skills s ON ts.skill_id=s.id WHERE ts.task_id=%d", $t->id ) );
        $out[] = [ 'id' => $t->id, 'title' => esc_html( $t->title ), 'description' => esc_html( wp_trim_words( $t->description, 20 ) ), 'budget_fmt' => mb_price( $t->budget ), 'skills' => array_map( 'esc_html', $sn ), 'posted_ago' => human_time_diff( strtotime( $t->created_at ), current_time( 'timestamp' ) ) . ' ago' ];
    }
    wp_send_json_success( [ 'tasks' => $out ] );
}

function bntm_ajax_mb_search_mentors() {
    check_ajax_referer( 'mb_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    }

    global $wpdb;
    $q     = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
    $limit = isset( $_POST['limit'] ) ? max( 1, min( 24, intval( $_POST['limit'] ) ) ) : 6;
    $like  = '%' . $wpdb->esc_like( $q ) . '%';

    $sql = "
        SELECT
            p.user_id,
            p.pfp_url,
            p.bio,
            p.verification_status,
            mh.meta_value AS headline,
            u.display_name,
            COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM {$wpdb->prefix}mb_reviews r WHERE r.reviewee_id = p.user_id AND r.status='active'), 0) AS avg_rating,
            COALESCE((SELECT COUNT(*) FROM {$wpdb->prefix}mb_reviews r WHERE r.reviewee_id = p.user_id AND r.status='active'), 0) AS review_count,
            GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR '||') AS skills,
            (
                SELECT COUNT(*)
                FROM {$wpdb->prefix}mb_tasks t
                WHERE t.provider_id = p.user_id AND t.status = 'completed'
            ) AS completed_count
        FROM {$wpdb->prefix}mb_user_profiles p
        INNER JOIN {$wpdb->users} u ON u.ID = p.user_id
        LEFT JOIN {$wpdb->usermeta} mh ON mh.user_id = p.user_id AND mh.meta_key = 'mb_profile_headline'
        LEFT JOIN {$wpdb->prefix}mb_provider_skills ps ON ps.user_id = p.user_id AND ps.status = 'active'
        LEFT JOIN {$wpdb->prefix}mb_skills s ON s.id = ps.skill_id AND s.status = 'active'
        WHERE p.user_role = 'provider' AND p.status = 'active'
    ";
    $params = [];
    if ( $q !== '' ) {
        $sql .= " AND (u.display_name LIKE %s OR u.user_login LIKE %s OR p.bio LIKE %s OR mh.meta_value LIKE %s OR s.name LIKE %s)";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= " GROUP BY p.user_id ORDER BY completed_count DESC, u.display_name ASC LIMIT %d";
    $params[] = $limit;

    $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
    $profile_view = mb_find_page_url( '[mb_profile_view]' );
    $out = [];
    foreach ( $rows as $row ) {
        $skills = array_filter( array_map( 'trim', explode( '||', (string) $row->skills ) ) );
        $out[] = [
            'id' => (int) $row->user_id,
            'name' => $row->display_name,
            'headline' => (string) get_user_meta( $row->user_id, 'mb_profile_headline', true ),
            'location' => (string) get_user_meta( $row->user_id, 'mb_profile_location', true ),
            'bio' => wp_trim_words( wp_strip_all_tags( (string) $row->bio ), 18 ),
            'avatar' => (string) $row->pfp_url,
            'skills' => array_values( $skills ),
            'completed' => (int) $row->completed_count,
            'avg_rating' => (float) $row->avg_rating,
            'review_count' => (int) $row->review_count,
            'verified' => $row->verification_status === 'verified',
            'profile_url' => add_query_arg( 'mb_user', (int) $row->user_id, $profile_view ),
        ];
    }

    wp_send_json_success( [ 'mentors' => $out ] );
}

/* ==========================================================================
   L. FRONTEND SHORTCODE FUNCTIONS
========================================================================== */

/* ==========================================================================
   L1. PUBLIC PAGE BRAND ASSETS (Landing / Login / Sign Up / Terms / Help)
========================================================================== */

define( 'MB_LOGO_LIGHT', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAABdcAAAExCAYAAACeQJvcAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAALiMAAC4jAXilP3YAAPuiSURBVHhe7N15eJTV9Qfw77kz2dhC2EJkIFIjaoILBGtr3YIiS621rdW2trWttkqwv2pdATfcl7pDcKutWq1LrXUXUbHuCiRsCYpYBLIHkrBmm7nf3x8ZWrwuZJlk3pk5n+fJI3zPGxaZ5Z3z3vdcQCmllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFJKKaWUUkoppZRSSimllFKxQNxAKaWUUkoppZRSSkWU5OTkJDc0NCT37dvX39ra6k9JSfGRFGutAQARoTHGtrS0hJKTk4M7duwIZmRktK5du7YVAN1fUCmlVPRpc10ppZRSSimllFIq8kxmZmaaMSatoqKiP4B8kvnGmH2stTkAMgH0BTDAGCPW2jYADQAqjDFrrbWfishSAEtHjBixzVrbVFNT0wTAur+RUkoppZRSSimllFJKKRXT8vPzk0aNGpVhrc211s4MhUJvhkKhJnZRKBRqCoVCb1prZ1prc0eNGpWRn5+f5P6+SimllFJKKaWUUkoppVQs8gcCgUHW2kmhUOgfoVCoxW2Ud1coFGoJhUL/sNZOCgQCgwD43T+EUkoppZRSSimllFJKKRULZPDgwf2ttd8KhUIvhEIh6zbFIy0UCtlQKPS8tfZbgwcP7q9jf5VSSimllFJKKaWUUkrFjPz8/CRrbSAUCt3cndEvXRUeGXOztTago2KUUkoppZRSSimllFJKeV4gEEiz1haEQqHVbtO7t4VCodXW2oJAIJDm/jmVUkoppZRS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' ); // light-colored logo, use on dark/teal backgrounds
define( 'MB_LOGO_DARK',  'data:image/png;base64,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' );  // dark/teal-colored logo, use on light backgrounds

function mb_find_page_url( $shortcode ) {
    global $wpdb;

    bntm_mb_ensure_pages();

    $page_hints = [
        '[mb_home]'          => [ 'titles' => [ 'Landing Page', 'MentorBe Home', 'Home' ], 'slugs' => [ 'landing-page', 'mentorbe-home', 'home' ] ],
        '[mb_help]'          => [ 'titles' => [ 'Help Center/FAQ', 'Help Center', 'FAQ' ], 'slugs' => [ 'help-center-faq', 'help-center', 'faq' ] ],
        '[mb_terms]'         => [ 'titles' => [ 'Terms of Service and Privacy Policy', 'Terms of Service', 'Privacy Policy' ], 'slugs' => [ 'terms-of-service-and-privacy-policy', 'terms-of-service', 'privacy-policy' ] ],
        '[mb_login]'         => [ 'titles' => [ 'Login' ], 'slugs' => [ 'mb-login' ] ],
        '[mb_register]'      => [ 'titles' => [ 'Sign Up', 'Register' ], 'slugs' => [ 'sign-up', 'register' ] ],
        '[mb_role_choice]'   => [ 'titles' => [ 'Choose Role' ], 'slugs' => [ 'choose-role' ] ],
        '[mb_profile_setup]' => [ 'titles' => [ 'Profile Setup' ], 'slugs' => [ 'profile-setup' ] ],
    ];

    // While inside wp-admin, AJAX, or the REST API (e.g. Gutenberg saving/publishing a page),
    // never let this function write to the database. Only read/return URLs in that context.
    $is_admin_post_request = isset( $_SERVER['SCRIPT_NAME'] ) && strpos( $_SERVER['SCRIPT_NAME'], 'admin-post.php' ) !== false;
    $in_admin_context = ( is_admin() && ! $is_admin_post_request ) || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST );

    $id = $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_status IN ('publish','draft','pending','private') AND post_type='page' AND post_content LIKE %s ORDER BY (post_status='publish') DESC LIMIT 1",
        '%' . $wpdb->esc_like( $shortcode ) . '%'
    ) );

    if ( $id ) {
        if ( ! $in_admin_context && get_post_status( $id ) !== 'publish' ) {
            wp_update_post( [ 'ID' => $id, 'post_status' => 'publish' ] );
        }
        $url = get_permalink( $id );
        if ( $url ) {
            return $url;
        }
    }

    if ( ! empty( $page_hints[ $shortcode ] ) ) {
        foreach ( $page_hints[ $shortcode ]['titles'] as $title ) {
            $page = get_page_by_title( $title );
            if ( $page ) {
                if ( ! $in_admin_context && $page->post_status !== 'publish' ) {
                    wp_update_post( [ 'ID' => $page->ID, 'post_status' => 'publish', 'post_content' => $shortcode ] );
                }
                $url = get_permalink( $page->ID );
                if ( $url ) {
                    return $url;
                }
            }
        }

        foreach ( $page_hints[ $shortcode ]['slugs'] as $slug ) {
            $page = get_page_by_path( $slug );
            if ( $page ) {
                if ( ! $in_admin_context && $page->post_status !== 'publish' ) {
                    wp_update_post( [ 'ID' => $page->ID, 'post_status' => 'publish', 'post_content' => $shortcode ] );
                }
                $url = get_permalink( $page->ID );
                if ( $url ) {
                    return $url;
                }
            }
        }
    }

    $front_page_id = get_option( 'page_on_front' );
    if ( $front_page_id ) {
        $url = get_permalink( $front_page_id );
        if ( $url ) {
            return $url;
        }
    }

    return home_url( '/' );
}

function mb_public_nav( $dark = false, $cta_label = 'Login', $cta_url = '' ) {
    $home_url  = mb_find_page_url( '[mb_home]' );
    $login_url = mb_find_page_url( '[mb_login]' );
    $terms_url = mb_find_page_url( '[mb_terms]' );
    $help_url  = mb_find_page_url( '[mb_help]' );
    if ( ! $cta_url ) $cta_url = $login_url;


    ob_start(); ?>
    <nav class="mb-nav<?php echo $dark ? ' dark' : ''; ?>">
        <div class="mb-nav-inner">
            <a class="mb-nav-logo" href="<?php echo esc_url( $home_url ); ?>"><img src="<?php echo $dark ? MB_LOGO_LIGHT : MB_LOGO_DARK; ?>" alt="MentorBe"></a>
            <div class="mb-nav-links">
                <a href="<?php echo esc_url( $home_url . '#mb-how' ); ?>">How it Works</a>
                <a href="<?php echo esc_url( $terms_url ); ?>">Terms</a>
                <a href="<?php echo esc_url( $terms_url . '#mb-privacy' ); ?>">Privacy</a>
                <a href="<?php echo esc_url( $help_url ); ?>">Help Center / FAQ</a>
            </div>
            <a href="<?php echo esc_url( $cta_url ); ?>" class="mb-nav-cta"><?php echo esc_html( $cta_label ); ?></a>
        </div>
    </nav>
    <?php
    return ob_get_clean();
}

function mb_business_footer( $dark = false ) {
    $home_url  = mb_find_page_url( '[mb_home]' );
    $terms_url = mb_find_page_url( '[mb_terms]' );
    $help_url  = mb_find_page_url( '[mb_help]' );
    $dash_url  = mb_find_page_url( '[mb_client_dashboard]' );
    $prov_url  = mb_find_page_url( '[mb_provider_dashboard]' );
    $post_url  = $dash_url ? add_query_arg( 'tab', 'post_task', $dash_url ) : $dash_url;
    $privacy_u = $terms_url ? $terms_url . '#mb-privacy' : $terms_url;

    ob_start(); ?>
    <footer class="mb-business-footer<?php echo $dark ? ' dark' : ''; ?>">
        <div class="mb-business-footer-inner">
            <div class="mb-business-footer-brand">
                <img src="<?php echo $dark ? MB_LOGO_LIGHT : MB_LOGO_DARK; ?>" alt="MentorBe">
                <p>Built for thoughtful local work, safer hiring, and clearer communication.</p>
            </div>
            <div class="mb-business-footer-links">
                <div>
                    <h4>Platform</h4>
                    <a href="<?php echo esc_url( $home_url ); ?>">Home</a>
                    <a href="<?php echo esc_url( $post_url ); ?>">Post a Task</a>
                    <a href="<?php echo esc_url( $dash_url ); ?>">Client Dashboard</a>
                    <a href="<?php echo esc_url( $prov_url ); ?>">Provider Dashboard</a>
                </div>
                <div>
                    <h4>Support</h4>
                    <a href="<?php echo esc_url( $help_url ); ?>">Help Center / FAQ</a>
                    <a href="<?php echo esc_url( $terms_url ); ?>">Terms of Service</a>
                    <a href="<?php echo esc_url( $privacy_u ); ?>">Privacy Policy</a>
                </div>
                <div>
                    <h4>Contact</h4>
                    <a href="mailto:support@mentorbe.io">support@mentorbe.io</a>
                    <a href="mailto:business@mentorbe.io">business@mentorbe.io</a>
                </div>
            </div>
        </div>
        <div class="mb-business-footer-bottom">
            <span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> MentorBe</span>
            <span>Safer hiring for local tasks and mentorship</span>
        </div>
    </footer>
    <?php
    return ob_get_clean();
}

function mb_public_assets() {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet">
    <style>
    :root {
        --mb-mint:      #f1fffe;
        --mb-mint-mid:  #97d6cf;
        --mb-mint-pill: #cfeeeb;
        --mb-teal:      #16796f;
        --mb-ink:       #1e1e1e;
        --mb-muted:     #5a7474;
        --mb-muted2:    #617876;
        --mb-body:      #314343;
        --mb-gold:      #e5cc4d;
    }
    .mb-public,.mb-public * { font-family:'Montserrat',sans-serif; box-sizing:border-box; }
    .mb-public img { max-width:100%; display:block; }
    .mb-public a { text-decoration:none; }
    .mb-business-footer { background:var(--mb-mint); border-top:1px solid rgba(22,121,111,.12); margin-top:32px; width:100%; }
    .mb-business-footer.dark { background:var(--mb-teal); border-top-color:rgba(255,255,255,.12); color:#fff; }
    .mb-business-footer-inner { max-width:1200px; margin:0 auto; padding:36px 24px; display:flex; gap:32px; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; }
    .mb-business-footer-brand { max-width:340px; }
    .mb-business-footer-brand img { height:34px; width:auto; margin-bottom:14px; display:block; }
    .mb-business-footer-brand p { color:var(--mb-muted); font-size:14px; line-height:1.65; margin:0; }
    .mb-business-footer.dark .mb-business-footer-brand p { color:rgba(255,255,255,.75); }
    .mb-business-footer-links { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:28px; flex:1; min-width:0; }
    .mb-business-footer-links > div { min-width:0; }
    .mb-business-footer-links h4 { font-size:14px; font-weight:800; color:var(--mb-ink); margin:0 0 12px; }
    .mb-business-footer.dark .mb-business-footer-links h4 { color:#fff; }
    .mb-business-footer-links a { display:block; color:var(--mb-body); font-size:14px; font-weight:600; margin-bottom:8px; }
    .mb-business-footer.dark .mb-business-footer-links a { color:rgba(255,255,255,.8); }
    .mb-business-footer-links a:hover { text-decoration:underline; }
    .mb-business-footer-bottom { max-width:1200px; margin:0 auto; padding:0 24px 24px; display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; color:var(--mb-muted); font-size:12px; }
    .mb-business-footer.dark .mb-business-footer-bottom { color:rgba(255,255,255,.72); }
    @media (max-width:720px) {
        .mb-business-footer-links { grid-template-columns:1fr; gap:20px; }
        .mb-business-footer-bottom { flex-direction:column; align-items:flex-start; }
    }

    /* ---- Nav ---- */
    .mb-nav { position:sticky; top:0; z-index:40; background:var(--mb-mint); box-shadow:0px 4px 16.3px 0px rgba(22,121,111,0.17); width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw); }
    .mb-nav.dark { background:var(--mb-teal); box-shadow:0px 4px 16.3px 0px rgba(22,121,111,0.35); }
    .mb-nav-inner { max-width:1440px; margin:0 auto; padding:0 40px; height:80px; display:flex; align-items:center; justify-content:space-between; gap:24px; }
    .mb-nav-logo { flex-shrink:0; display:block; }
    .mb-nav-logo img { height:36px; width:auto; object-fit:contain; display:block; }
    .mb-nav-links { display:flex; align-items:center; gap:32px; flex:1; justify-content:center; }
    .mb-nav-links a { font-size:15px; font-weight:600; white-space:nowrap; transition:opacity .15s; color:var(--mb-ink); }
    .mb-nav-links a:hover { opacity:.7; }
    .mb-nav.dark .mb-nav-links a { color:#fff; }
    .mb-nav-cta { flex-shrink:0; padding:10px 28px; border-radius:9999px; font-size:15px; font-weight:600; letter-spacing:-0.2px; transition:all .15s; border:2px solid var(--mb-teal); color:var(--mb-teal); }
    .mb-nav-cta:hover { background:var(--mb-teal); color:#fff; }
    .mb-nav.dark .mb-nav-cta { border:none; background:var(--mb-mint); color:var(--mb-ink); }
    .mb-nav.dark .mb-nav-cta:hover { opacity:.8; }
    @media (max-width:820px) { .mb-nav-links{ display:none; } }

    /* ---- Hero (Landing) ---- */
    .mb-hero-section { max-width:1200px; margin:0 auto; padding:80px 40px 96px; display:grid; grid-template-columns:1fr; gap:64px; align-items:center; }
    @media(min-width:1024px) { .mb-hero-section { grid-template-columns:1fr 1fr; } }
    .mb-hero-section h1 { font-weight:700; color:var(--mb-ink); line-height:0.95; margin:0 0 24px; font-size:clamp(38px,5.2vw,68px); }
    .mb-hero-section h1 span { font-weight:900; color:var(--mb-teal); display:block; }
    .mb-hero-section p { color:var(--mb-ink); font-size:18px; font-weight:500; margin:0 0 40px; max-width:480px; line-height:1.6; }
    .mb-hero-cta { display:flex; gap:14px; flex-wrap:wrap; }
    .mb-hero-img { width:100%; border-radius:24px; object-fit:cover; box-shadow:0 20px 40px -12px rgba(0,0,0,.25); aspect-ratio:5/4; }

    /* ---- Stats ---- */
    .mb-stats-bar { display:flex; justify-content:center; gap:16px; padding:0 40px 64px; flex-wrap:wrap; max-width:1200px; margin:0 auto; }
    .mb-si { text-align:center; background:#fff; border-radius:16px; padding:18px 30px; box-shadow:4px 4px 7.2px rgba(0,0,0,.08); min-width:150px; }
    .mb-sn { display:block; font-size:26px; font-weight:800; color:var(--mb-teal); }
    .mb-sl { font-size:11px; color:var(--mb-muted); font-weight:600; text-transform:uppercase; letter-spacing:.04em; }

    /* ---- How it Works ---- */
    .mb-how { scroll-margin-top:32px; background:linear-gradient(to bottom, #f1f5f9 0%, #c8eae7 12%, #97d6cf 28%, #3a9e95 55%, #16796f 72%); }
    .mb-how-inner { max-width:1200px; margin:0 auto; padding:80px 40px 112px; }
    .mb-how-inner > p.mb-how-heading { font-weight:800; color:var(--mb-ink); font-size:clamp(30px,4.2vw,48px); margin:0 0 56px; }
    .mb-how-grid { display:grid; grid-template-columns:1fr; gap:40px; }
    @media(min-width:768px) { .mb-how-grid { grid-template-columns:repeat(3,1fr); } }
    .mb-how-step { text-align:center; }
    .mb-how-step .mb-step-label { color:#fff; font-weight:700; font-size:22px; margin:0 0 16px; filter:drop-shadow(0 1px 3px rgba(0,0,0,.4)); }
    .mb-how-step img { width:100%; border-radius:16px; object-fit:cover; margin-bottom:20px; box-shadow:0 10px 15px -3px rgba(0,0,0,.2); height:200px; }
    .mb-how-step .mb-step-title { color:var(--mb-gold); font-weight:700; font-size:20px; margin:0 0 12px; }
    .mb-how-step .mb-step-desc { color:var(--mb-mint); font-weight:500; font-size:14px; line-height:1.6; max-width:260px; margin:0 auto; }

    /* ---- Categories ---- */
    .mb-cats { padding:96px 0; background-image:linear-gradient(to bottom, #f1fffe 55%, #97d6cf); }
    .mb-cats-inner { max-width:1200px; margin:0 auto; padding:0 40px; }
    .mb-cats h2 { font-weight:800; color:var(--mb-ink); text-align:center; margin:0 0 32px; font-size:clamp(32px,5vw,56px); }
    .mb-carousel { position:relative; padding:0 32px; }
    .mb-carousel-btn { position:absolute; top:40%; transform:translateY(-50%); z-index:10; width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:9999px; background:#fff; box-shadow:0 10px 15px -3px rgba(0,0,0,.15); transition:background .15s; border:none; cursor:pointer; }
    .mb-carousel-btn:hover { background:var(--mb-mint-pill); }
    .mb-carousel-btn:disabled { opacity:.25; cursor:default; }
    .mb-carousel-btn.left { left:0; }
    .mb-carousel-btn.right { right:0; }
    .mb-carousel-btn svg { color:var(--mb-teal); }
    .mb-cat-grid { display:grid; grid-template-columns:1fr; gap:24px; }
    @media(min-width:768px) { .mb-cat-grid { grid-template-columns:repeat(3,1fr); } }
    .mb-cat-card { background:rgba(151,214,207,0.25); border-radius:16px; box-shadow:4px 4px 7.2px rgba(0,0,0,0.12); overflow:hidden; transition:box-shadow .15s; }
    .mb-cat-card:hover { box-shadow:0 10px 15px -3px rgba(0,0,0,.1); }
    .mb-cat-card img { width:100%; object-fit:cover; height:160px; }
    .mb-cat-card-body { padding:20px; }
    .mb-cat-card h3 { font-weight:800; color:var(--mb-ink); font-size:17px; margin:0 0 10px; line-height:1.2; }
    .mb-cat-card .mb-skills-label { color:var(--mb-teal); font-weight:700; font-size:13px; margin:0 0 8px; }
    .mb-cat-tags { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
    .mb-cat-tags span { background:rgba(90,116,116,0.55); color:#fff; font-size:12px; font-weight:600; padding:2px 10px; border-radius:9999px; }
    .mb-cat-card .mb-cat-desc { color:var(--mb-muted); font-size:13px; font-weight:500; margin:0 0 20px; line-height:1.5; }
    .mb-cat-btn { display:block; width:100%; text-align:center; color:#fff; font-size:13px; font-weight:700; border-radius:9999px; padding:10px; margin-bottom:8px; transition:opacity .15s; box-sizing:border-box; border:none; cursor:pointer; }
    .mb-cat-btn:hover { opacity:.8; }
    .mb-cat-btn.dark { background:var(--mb-ink); }
    .mb-cat-btn.muted { background:var(--mb-muted); margin-bottom:0; }
    .mb-cat-dots { display:flex; justify-content:center; gap:8px; margin-top:32px; }
    .mb-cat-dots button { width:8px; height:8px; border-radius:9999px; background:var(--mb-mint-mid); transition:background .15s; border:none; cursor:pointer; padding:0; }
    .mb-cat-dots button.active { background:var(--mb-teal); }

    /* ---- Final CTA (Landing) ---- */
    .mb-final-cta { max-width:800px; margin:64px auto; background:var(--mb-mint-pill); border-radius:24px; padding:56px 32px; text-align:center; }
    .mb-final-cta h2 { font-weight:800; color:var(--mb-ink); font-size:28px; margin:0 0 10px; }
    .mb-final-cta p { color:var(--mb-muted); font-weight:500; margin-bottom:24px; }

    /* ---- Auth pages (Login / Sign up) ---- */
    .mb-auth-page { background:var(--mb-mint-mid); border-radius:24px; }
    .mb-auth-body { display:flex; align-items:center; justify-content:center; padding:56px 32px; }
    .mb-auth-wrap { width:100%; max-width:1000px; display:grid; grid-template-columns:1fr; gap:56px; align-items:center; }
    @media(min-width:1024px) { .mb-auth-wrap { grid-template-columns:1fr 1fr; } }
    .mb-auth-hero { margin-bottom:32px; }
    .mb-auth-hero .mb-line1 { line-height:.9; font-weight:800; color:#fff; letter-spacing:-3px; filter:drop-shadow(0 4px 4px rgba(0,0,0,.25)); font-size:clamp(48px,7vw,80px); }
    .mb-auth-hero .mb-line2 { line-height:1.15; color:#fff; letter-spacing:-2px; filter:drop-shadow(0 4px 4px rgba(0,0,0,.25)); margin-top:4px; font-family:'Cormorant Garamond',serif; font-weight:700; font-size:clamp(32px,4.6vw,50px); }
    .mb-auth-side-img { border-radius:16px; width:100%; object-fit:cover; box-shadow:0 20px 25px -5px rgba(0,0,0,.2); border:1px solid rgba(255,255,255,.2); max-height:340px; }
    .mb-auth-card { background:var(--mb-mint); border-radius:48px; box-shadow:-5px 4px 13.7px rgba(0,0,0,0.25); padding:48px 40px; }
    .mb-auth-card h2 { font-weight:800; color:var(--mb-teal); font-size:40px; text-align:center; letter-spacing:-0.5px; margin:0 0 32px; }
    .mb-auth-input-group { display:flex; flex-direction:column; gap:16px; margin-bottom:12px; }
    .mb-auth-input { position:relative; }
    .mb-auth-input input { width:100%; border:1px solid var(--mb-teal); border-radius:12px; padding:14px 20px; color:var(--mb-teal); font-weight:500; font-size:16px; background:transparent; outline:none; box-sizing:border-box; font-family:'Montserrat',sans-serif; }
    .mb-auth-input input::placeholder { color:var(--mb-teal); opacity:.85; }
    .mb-auth-input input:focus { box-shadow:0 0 0 2px rgba(22,121,111,.5); }
    .mb-auth-submit { width:100%; background:var(--mb-teal); color:#fff; border:none; border-radius:12px; padding:16px; font-weight:600; font-size:16px; cursor:pointer; transition:opacity .15s; font-family:'Montserrat',sans-serif; margin-top:4px; }
    .mb-auth-submit:hover { opacity:.9; }
    .mb-auth-switch { text-align:center; color:var(--mb-muted); font-weight:500; font-size:14px; margin-top:24px; }
    .mb-auth-switch a { color:var(--mb-teal); font-weight:700; }
    .mb-auth-switch a:hover { text-decoration:underline; }
    .mb-auth-hint { font-size:12px; color:var(--mb-muted); margin:0 0 16px; }
    .mb-auth-notice-wrap { margin-bottom:16px; }

    /* ---- Terms / Privacy ---- */
    .mb-static-page { background:var(--mb-mint); }
    .mb-static-inner { max-width:768px; margin:0 auto; padding:48px 24px 72px; }
    .mb-static-inner h1 { font-weight:700; font-size:34px; color:var(--mb-ink); margin:0 0 4px; }
    .mb-static-inner .mb-updated { color:var(--mb-muted); font-weight:500; font-size:13px; margin:0 0 40px; }
    .mb-legal-section { margin-bottom:24px; }
    .mb-legal-section h2 { font-weight:700; font-size:17px; color:var(--mb-ink); margin:0 0 4px; }
    .mb-legal-section p { color:var(--mb-body); font-weight:500; font-size:15px; line-height:1.6; margin:0; }
    .mb-legal-sections-wrap { margin-bottom:64px; }
    .mb-privacy-block { border-top:4px solid var(--mb-teal); padding-top:48px; scroll-margin-top:32px; }

    /* ---- Help / FAQ ---- */
    .mb-help-wrap { max-width:1200px; margin:0 auto; padding:48px 24px 72px; display:flex; gap:40px; align-items:flex-start; }
    .mb-help-sidebar { width:240px; flex-shrink:0; position:sticky; top:120px; align-self:flex-start; }
    .mb-help-sidebar-inner { background:var(--mb-mint-mid); border-radius:28px; overflow:hidden; padding:16px 0; }
    .mb-help-sidebar-inner a { display:block; width:100%; text-align:left; padding:14px 24px; font-weight:700; font-size:15px; transition:all .15s; color:var(--mb-body); }
    .mb-help-sidebar-inner a:hover { color:var(--mb-teal); }
    .mb-help-sidebar-inner a.active { background:var(--mb-teal); color:#fff; }
    .mb-help-content { flex:1; min-width:0; }
    .mb-help-content h1 { font-weight:800; font-size:34px; color:var(--mb-teal); margin:0 0 4px; }
    .mb-help-content .mb-updated { color:var(--mb-muted); font-weight:500; font-size:13px; margin:0 0 40px; }
    .mb-faq-section { margin-bottom:48px; scroll-margin-top:112px; }
    .mb-faq-section h2 { font-weight:800; font-size:21px; color:var(--mb-ink); margin:0 0 20px; padding-bottom:8px; border-bottom:2px solid var(--mb-mint-mid); }
    .mb-faq-items { display:flex; flex-direction:column; gap:8px; }
    .mb-faq-item { border:1px solid var(--mb-mint-mid); border-radius:12px; overflow:hidden; }
    .mb-faq-btn { width:100%; background:transparent; border:none; display:flex; align-items:center; justify-content:space-between; padding:16px 20px; text-align:left; transition:background .15s; cursor:pointer; gap:16px; font-family:'Montserrat',sans-serif; }
    .mb-faq-btn:hover { background:rgba(207,238,235,.5); }
    .mb-faq-btn span { font-weight:600; color:var(--mb-ink); font-size:15px; }
    .mb-faq-chev { color:var(--mb-teal); flex-shrink:0; transition:transform .2s; }
    .mb-faq-item.open .mb-faq-chev { transform:rotate(180deg); }
    .mb-faq-ans { max-height:0; overflow:hidden; transition:max-height .2s ease; color:var(--mb-body); font-weight:500; font-size:14px; line-height:1.6; border-top:1px solid rgba(151,214,207,.4); }
    .mb-faq-item.open .mb-faq-ans { max-height:400px; }
    .mb-faq-ans-inner { padding:12px 20px 20px; }
    .mb-help-cta { background:var(--mb-teal); border-radius:16px; padding:32px; text-align:center; }
    .mb-help-cta p.mb-title { font-weight:800; color:#fff; font-size:20px; margin:0 0 8px; }
    .mb-help-cta p.mb-desc { color:var(--mb-mint-mid); font-weight:500; font-size:14px; margin:0 0 20px; line-height:1.6; }
    .mb-help-cta a { display:inline-block; background:var(--mb-mint); color:var(--mb-teal); font-weight:700; padding:12px 32px; border-radius:9999px; transition:opacity .15s; }
    .mb-help-cta a:hover { opacity:.9; }
    @media(max-width:900px) { .mb-help-wrap { flex-direction:column; } .mb-help-sidebar { width:100%; position:static; } .mb-help-sidebar-inner { display:flex; flex-wrap:wrap; padding:8px; gap:4px; } .mb-help-sidebar-inner a { width:auto; border-radius:9999px; padding:8px 16px; } }
    </style>
    <script>
    function mbTogglePass(btn, inputId) {
        var input = document.getElementById(inputId);
        var isPass = input.type === 'password';
        input.type = isPass ? 'text' : 'password';
    }

    document.addEventListener('click', function(e) {
        var link = e.target.closest('.mb-nav-links a[href*="#mb-how"]');
        if ( ! link ) return;

        var target = document.getElementById('mb-how');
        if ( ! target ) return;

        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });

        if ( history.pushState ) {
            history.pushState( null, '', '#mb-how' );
        }
    });
    </script>
    <?php
}

function bntm_shortcode_mb_home() {
    global $wpdb;
    $live  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE status='live'" );
    $provs = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_user_profiles WHERE user_role='provider' AND status='active'" );
    $done  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE status='completed'" );
    $cats  = $wpdb->get_results( "SELECT category,COUNT(*) as cnt FROM {$wpdb->prefix}mb_skills WHERE status='active' GROUP BY category ORDER BY cnt DESC LIMIT 8" );

    $ru   = mb_find_page_url( '[mb_register]' );
    $lu   = mb_find_page_url( '[mb_login]' );
    $cdp  = get_page_by_path( 'clients-dashboard' );  $cu = $cdp ? get_permalink( $cdp->ID ) : '#';
    $pdp  = get_page_by_path( 'hometask-feed' );      $pu = $pdp ? get_permalink( $pdp->ID ) : '#';

    // Build category cards (image / tags / description) from real skill data.
    $cat_images = [
        'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=308&h=218&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=308&h=218&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=308&h=218&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=308&h=218&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1568702846914-96b305d2aaeb?w=308&h=218&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=308&h=218&fit=crop&auto=format',
    ];
    $cat_cards = [];
    if ( ! empty( $cats ) ) {
        foreach ( $cats as $i => $c ) {
            $tag_rows = $wpdb->get_col( $wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}mb_skills WHERE category=%s AND status='active' ORDER BY id ASC LIMIT 4",
                $c->category
            ) );
            $cat_cards[] = [
                'title' => $c->category,
                'desc'  => sprintf( 'Find trusted local providers skilled in %s.', $c->category ),
                'tags'  => $tag_rows,
                'img'   => $cat_images[ $i % count( $cat_images ) ],
            ];
        }
    } else {
        // Fallback categories shown until real skills are added to mb_skills.
        $cat_cards = [
            [ 'title' => 'Tech & Development',    'desc' => 'Build apps, websites, and software with expert developers.',        'tags' => [ 'React', 'Python', 'Node.js', 'TypeScript' ], 'img' => $cat_images[0] ],
            [ 'title' => 'Design & Creative',      'desc' => 'Logos, branding, video editing, and visual content creation.',      'tags' => [ 'Figma', 'Canva', 'Photoshop', 'Premiere' ],  'img' => $cat_images[1] ],
            [ 'title' => 'Tutoring & Education',   'desc' => 'Expert tutors for school subjects, test prep, and skill building.', 'tags' => [ 'Math', 'Science', 'SAT Prep', 'Languages' ], 'img' => $cat_images[2] ],
            [ 'title' => 'Home Services',          'desc' => 'Assembly, repairs, cleaning, and hands-on home tasks.',            'tags' => [ 'Carpentry', 'Plumbing', 'Assembly', 'Cleaning' ], 'img' => $cat_images[3] ],
            [ 'title' => 'Errands & Delivery',     'desc' => 'Local pickups, deliveries, and everyday run-around tasks.',        'tags' => [ 'Grocery', 'Pharmacy', 'Moving', 'Delivery' ], 'img' => $cat_images[4] ],
            [ 'title' => 'Business & Admin',       'desc' => 'Data entry, virtual assistance, and back-office support.',         'tags' => [ 'Data Entry', 'Virtual Assistant', 'Bookkeeping', 'Research' ], 'img' => $cat_images[5] ],
        ];
    }

    mb_dashboard_assets();
    mb_public_assets();
    ob_start(); ?>
    <div class="mb-public">
        <?php echo mb_public_nav( false, 'Login', $lu ); ?>

        <section class="mb-hero-section">
            <div>
                <h1>Post. Match. <br><span>Get It Done.</span></h1>
                <p>Post a task, find the right person, and pay only when you're satisfied.</p>
                <div class="mb-hero-cta">
                    <?php if ( ! is_user_logged_in() ): ?>
                        <a href="<?php echo esc_url( $ru ); ?>" class="bntm-btn-primary">Create an account</a>
                    <?php else: $p = mb_get_profile( get_current_user_id() ); ?>
                        <a href="<?php echo esc_url( $p && $p->user_role === 'provider' ? $pu : $cu ); ?>" class="bntm-btn-primary">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <img class="mb-hero-img" src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600&h=480&fit=crop&auto=format" alt="Community collaboration">
            </div>
        </section>

        <div class="mb-stats-bar">
            <div class="mb-si"><span class="mb-sn"><?php echo number_format($live); ?>+</span><span class="mb-sl">Live Tasks</span></div>
            <div class="mb-si"><span class="mb-sn"><?php echo number_format($provs); ?>+</span><span class="mb-sl">Skilled Providers</span></div>
            <div class="mb-si"><span class="mb-sn"><?php echo number_format($done); ?>+</span><span class="mb-sl">Tasks Completed</span></div>
        </div>

        <section class="mb-how" id="mb-how">
            <div class="mb-how-inner">
                <p class="mb-how-heading">How it Works</p>
                <div class="mb-how-grid">
                    <div class="mb-how-step">
                        <p class="mb-step-label">Step 1</p>
                        <img src="https://images.unsplash.com/photo-1484480974693-6ca0a78fb36b?w=400&h=260&fit=crop&auto=format" alt="Post Your Task">
                        <p class="mb-step-title">Post a Task</p>
                        <p class="mb-step-desc">Post a task, find the right person, and pay only when you're satisfied.</p>
                    </div>
                    <div class="mb-how-step">
                        <p class="mb-step-label">Step 2</p>
                        <img src="https://images.unsplash.com/photo-1551836022-deb4988cc6c0?w=400&h=260&fit=crop&auto=format" alt="Receive Proposals">
                        <p class="mb-step-title">Pick Your Provider</p>
                        <p class="mb-step-desc">Review applicants, chat before you commit, and choose the best fit.</p>
                    </div>
                    <div class="mb-how-step">
                        <p class="mb-step-label">Step 3</p>
                        <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&h=260&fit=crop&auto=format" alt="Get It Done">
                        <p class="mb-step-title">Approve & Pay</p>
                        <p class="mb-step-desc">Work gets done, you approve, and payment is released automatically.</p>
                    </div>
                </div>
            </div>
        </section>

        <?php if ( ! empty( $cat_cards ) ): ?>
        <section class="mb-cats">
            <div class="mb-cats-inner">
                <h2>Popular Categories</h2>
                <div class="mb-carousel">
                    <button type="button" class="mb-carousel-btn left" id="mb-cat-prev" aria-label="Previous">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <div class="mb-cat-grid" id="mb-cat-grid"></div>
                    <button type="button" class="mb-carousel-btn right" id="mb-cat-next" aria-label="Next">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
                <div class="mb-cat-dots" id="mb-cat-dots"></div>
            </div>
        </section>
        <script>
        (function(){
            var CATS = <?php echo wp_json_encode( $cat_cards ); ?>;
            var idx = 0;
            var signupUrl = <?php echo wp_json_encode( $ru ); ?>;
            function render(){
                var grid = document.getElementById('mb-cat-grid');
                if(!grid) return;
                var visible = CATS.slice(idx, idx + 3);
                grid.innerHTML = visible.map(function(cat){
                    var tags = (cat.tags||[]).map(function(t){return '<span>'+t+'</span>';}).join('');
                    return '<div class="mb-cat-card">' +
                        '<img src="'+cat.img+'" alt="'+cat.title+'">' +
                        '<div class="mb-cat-card-body">' +
                            '<h3>'+cat.title+'</h3>' +
                            (tags ? '<p class="mb-skills-label">Popular Skills:</p><div class="mb-cat-tags">'+tags+'</div>' : '') +
                            '<p class="mb-cat-desc">'+cat.desc+'</p>' +
                            '<a class="mb-cat-btn dark" href="'+signupUrl+'">View Available Mentors</a>' +
                            '<a class="mb-cat-btn muted" href="'+signupUrl+'">View Open Tasks</a>' +
                        '</div></div>';
                }).join('');
                var prev = document.getElementById('mb-cat-prev');
                var next = document.getElementById('mb-cat-next');
                prev.disabled = idx === 0;
                next.disabled = idx >= CATS.length - 3;
                var dots = document.getElementById('mb-cat-dots');
                dots.innerHTML = '';
                for (var i = 0; i < Math.max(1, CATS.length - 2); i++) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    if (i === idx) b.classList.add('active');
                    (function(n){ b.addEventListener('click', function(){ idx = n; render(); }); })(i);
                    dots.appendChild(b);
                }
            }
            document.getElementById('mb-cat-prev').addEventListener('click', function(){ idx = Math.max(0, idx - 1); render(); });
            document.getElementById('mb-cat-next').addEventListener('click', function(){ idx = Math.min(Math.max(0, CATS.length - 3), idx + 1); render(); });
            render();
        })();
        </script>
        <?php endif; ?>

        <?php if ( ! is_user_logged_in() ): ?>
        <section class="mb-final-cta">
            <h2>Ready to get started?</h2>
            <p>Join as a Client to post tasks, or as a Provider to earn by sharing your skills.</p>
            <div class="mb-hero-cta" style="justify-content:center;">
                <a href="<?php echo esc_url( $ru ); ?>" class="bntm-btn-primary">Sign Up Now</a>
                <a href="<?php echo esc_url( $lu ); ?>" class="bntm-btn-secondary">Log In</a>
            </div>
        </section>
        <?php endif; ?>
        <?php echo mb_business_footer(); ?>
    </div>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_login() {
    if ( is_user_logged_in() ) {
        $p = mb_get_profile( get_current_user_id() );
        $onboarding_url = bntm_mb_onboarding_redirect_url( get_current_user_id(), $p );
        if ( $onboarding_url ) {
            wp_redirect( $onboarding_url );
            exit;
        }
        if ( $p ) {
            $dest_url = bntm_mb_dashboard_url_for_role( $p->user_role );
            if ( $dest_url ) { wp_redirect( $dest_url ); exit; }
        }
    }
    $ru  = mb_find_page_url( '[mb_register]' );

    mb_dashboard_assets();
    mb_public_assets();
    ob_start(); ?>
    <div class="mb-public">
        <?php echo mb_public_nav( true, 'Sign up', $ru ); ?>
        <div class="mb-auth-page">
            <div class="mb-auth-body">
                <div class="mb-auth-wrap">
                    <div>
                        <div class="mb-auth-hero">
                            <div class="mb-line1">Welcome</div>
                            <div class="mb-line2">Back!</div>
                        </div>
                        <img class="mb-auth-side-img" src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=500&h=360&fit=crop&auto=format" alt="Working">
                    </div>
                    <div class="mb-auth-card">
                        <h2>Login</h2>
                        <?php if ( isset( $_GET['mb_login_notice'] ) ) : ?>
                            <div class="bntm-notice mb-auth-notice-wrap"><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['mb_login_notice'] ) ) ); ?></div>
                        <?php endif; ?>
                        <?php if ( isset( $_GET['mb_login_error'] ) ) : ?>
                            <div class="bntm-notice bntm-notice-error mb-auth-notice-wrap"><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['mb_login_error'] ) ) ); ?></div>
                        <?php endif; ?>
                        <form id="mb-login-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="mb_login">
                            <div class="mb-auth-input-group">
                                <div class="mb-auth-input"><input type="text" name="mb_identifier" placeholder="Email" required autocomplete="username"></div>
                                <div class="mb-auth-input mb-pw-wrap">
                                    <input type="password" name="mb_password" id="mb-login-pass" placeholder="Password" required autocomplete="current-password">
                                    <button type="button" class="mb-pw-toggle" onclick="mbTogglePass(this,'mb-login-pass')">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </div>
                            <p class="mb-forgot-link"><a href="<?php echo esc_url( bntm_mb_forgot_password_url() ); ?>">Forgot Password?</a></p>
                            <p class="mb-login-as-label">Log in as</p>
                            <div class="mb-role-toggle" id="mb-login-role-toggle">
                                <button type="button" class="mb-role-toggle-btn active" data-role="client">Client</button>
                                <button type="button" class="mb-role-toggle-btn" data-role="provider">Provider</button>
                            </div>
                            <?php wp_nonce_field( 'mb-login', 'mb_login_nonce' ); ?>
                            <button type="submit" class="mb-auth-submit" style="margin-top:20px;">Login</button>
                        </form>
                        <p class="mb-auth-switch">Don't have an account? <a href="<?php echo esc_url( $ru ); ?>">Sign up</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
    .mb-pw-wrap{position:relative;}
    .mb-pw-wrap input{padding-right:44px;}
    .mb-pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--mb-teal);cursor:pointer;padding:0;}
    .mb-forgot-link{text-align:right;margin:6px 2px 0;}
    .mb-forgot-link a{color:var(--mb-muted);font-size:13px;font-weight:500;}
    .mb-forgot-link a:hover{color:var(--mb-teal);}
    .mb-login-as-label{font-size:12px;font-weight:700;color:var(--mb-muted);text-transform:uppercase;letter-spacing:.04em;margin:20px 0 8px;}
    .mb-role-toggle{display:flex;gap:10px;}
    .mb-role-toggle-btn{flex:1;padding:12px;border-radius:12px;border:0px solid var(--mb-teal);background:var(--mb-mint-pill);color:var(--mb-teal);font-weight:700;font-size:15px;cursor:pointer;transition:all .15s;font-family:'Montserrat',sans-serif;}
    .mb-role-toggle-btn.active{background:var(--mb-teal);color:#fff;}
    </style>
    <script>
    function mbTogglePass(btn, inputId) {
        var input = document.getElementById(inputId);
        if (!input) return;
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('mb-login-role-toggle');
        if (!toggle) return;
        toggle.querySelectorAll('.mb-role-toggle-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                toggle.querySelectorAll('.mb-role-toggle-btn').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_forgot_password() {
    if ( is_user_logged_in() ) {
        $p = mb_get_profile( get_current_user_id() );
        $onboarding_url = bntm_mb_onboarding_redirect_url( get_current_user_id(), $p );
        if ( $onboarding_url ) {
            wp_redirect( $onboarding_url );
            exit;
        }
        if ( $p ) {
            $dest_url = bntm_mb_dashboard_url_for_role( $p->user_role );
            if ( $dest_url ) { wp_redirect( $dest_url ); exit; }
        }
    }

    $lu = mb_find_page_url( '[mb_login]' );

    mb_dashboard_assets();
    mb_public_assets();
    ob_start(); ?>
    <div class="mb-public">
        <?php echo mb_public_nav( true, 'Login', $lu ); ?>
        <div class="mb-auth-page">
            <div class="mb-auth-body">
                <div class="mb-auth-wrap">
                    <div>
                        <div class="mb-auth-hero">
                            <div class="mb-line1">Reset</div>
                            <div class="mb-line2">Password</div>
                        </div>
                        <img class="mb-auth-side-img" src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=500&h=360&fit=crop&auto=format" alt="Forgot password">
                    </div>
                    <div class="mb-auth-card">
                        <h2>Forgot Password</h2>
                        <?php if ( isset( $_GET['mb_fp_status'] ) ) : ?>
                            <div class="bntm-notice mb-auth-notice-wrap"><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['mb_fp_status'] ) ) ); ?></div>
                        <?php endif; ?>
                        <?php if ( isset( $_GET['mb_fp_error'] ) ) : ?>
                            <div class="bntm-notice bntm-notice-error mb-auth-notice-wrap"><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['mb_fp_error'] ) ) ); ?></div>
                        <?php endif; ?>
                        <p class="mb-auth-hint">Enter your email address or username and we’ll send a reset link.</p>
                        <form id="mb-forgot-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="mb_forgot_password">
                            <div class="mb-auth-input-group">
                                <div class="mb-auth-input">
                                    <input type="text" name="user_login" placeholder="Email Address or Username" required autocomplete="username">
                                </div>
                            </div>
                            <?php wp_nonce_field( 'mb-forgot-password', 'mb_forgot_password_nonce' ); ?>
                            <button type="submit" class="mb-auth-submit" style="margin-top:20px;">Send Reset Link</button>
                        </form>
                        <p class="mb-auth-switch">Remembered it? <a href="<?php echo esc_url( $lu ); ?>">Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
    .mb-auth-hint{color:var(--mb-muted);font-size:14px;line-height:1.6;margin:0 0 16px;}
    </style>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_reset_password() {
    if ( is_user_logged_in() ) {
        $p = mb_get_profile( get_current_user_id() );
        $onboarding_url = bntm_mb_onboarding_redirect_url( get_current_user_id(), $p );
        if ( $onboarding_url ) {
            wp_redirect( $onboarding_url );
            exit;
        }
        if ( $p ) {
            $dest_url = bntm_mb_dashboard_url_for_role( $p->user_role );
            if ( $dest_url ) { wp_redirect( $dest_url ); exit; }
        }
    }

    $login_url = mb_find_page_url( '[mb_login]' );
    $key       = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
    $login     = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
    $user      = ( $key && $login ) ? check_password_reset_key( $key, $login ) : new WP_Error( 'invalid', 'Missing reset link data.' );
    $valid     = ! is_wp_error( $user );

    mb_dashboard_assets();
    mb_public_assets();
    ob_start(); ?>
    <div class="mb-public">
        <?php echo mb_public_nav( true, 'Login', $login_url ); ?>
        <div class="mb-auth-page">
            <div class="mb-auth-body">
                <div class="mb-auth-wrap">
                    <div>
                        <div class="mb-auth-hero">
                            <div class="mb-line1">Set New</div>
                            <div class="mb-line2">Password</div>
                        </div>
                        <img class="mb-auth-side-img" src="https://images.unsplash.com/photo-1481437642641-2f0ae875f836?w=500&h=360&fit=crop&auto=format" alt="Reset password">
                    </div>
                    <div class="mb-auth-card">
                        <h2>Reset Password</h2>
                        <?php if ( isset( $_GET['mb_fp_error'] ) ) : ?>
                            <div class="bntm-notice bntm-notice-error mb-auth-notice-wrap"><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['mb_fp_error'] ) ) ); ?></div>
                        <?php endif; ?>
                        <?php if ( ! $valid ) : ?>
                            <p class="mb-auth-hint">This reset link is invalid or has expired.</p>
                            <p class="mb-auth-switch">Please <a href="<?php echo esc_url( bntm_mb_forgot_password_url() ); ?>">request a new link</a> or go back to <a href="<?php echo esc_url( $login_url ); ?>">login</a>.</p>
                        <?php else : ?>
                            <p class="mb-auth-hint">Choose a new password for <?php echo esc_html( $login ); ?>.</p>
                            <form id="mb-reset-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                <input type="hidden" name="action" value="mb_reset_password">
                                <input type="hidden" name="login" value="<?php echo esc_attr( $login ); ?>">
                                <input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>">
                                <div class="mb-auth-input-group">
                                    <div class="mb-auth-input"><input type="password" name="new_password" placeholder="New Password" required minlength="8" autocomplete="new-password"></div>
                                    <div class="mb-auth-input"><input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="8" autocomplete="new-password"></div>
                                </div>
                                <?php wp_nonce_field( 'mb-reset-password', 'mb_reset_password_nonce' ); ?>
                                <button type="submit" class="mb-auth-submit" style="margin-top:20px;">Reset Password</button>
                            </form>
                        <?php endif; ?>
                        <p class="mb-auth-switch">Back to <a href="<?php echo esc_url( $login_url ); ?>">Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
    .mb-auth-hint{color:var(--mb-muted);font-size:14px;line-height:1.6;margin:0 0 16px;}
    </style>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_register() {
    if ( is_user_logged_in() ) {
        $p = mb_get_profile( get_current_user_id() );
        $onboarding_url = bntm_mb_onboarding_redirect_url( get_current_user_id(), $p );
        if ( $onboarding_url ) {
            wp_redirect( $onboarding_url );
            exit;
        }
        if ( $p ) {
            $dest_url = bntm_mb_dashboard_url_for_role( $p->user_role );
            if ( $dest_url ) { wp_redirect( $dest_url ); exit; }
        }
    }
    $lu = mb_find_page_url( '[mb_login]' );

    mb_dashboard_assets();
    mb_public_assets();
    ob_start(); ?>
    <div class="mb-public">
        <?php echo mb_public_nav( true, 'Login', $lu ); ?>
        <div class="mb-auth-page">
            <div class="mb-auth-body">
                <div class="mb-auth-wrap">
                    <div>
                        <div class="mb-auth-hero">
                            <div class="mb-line1">Welcome</div>
                            <div class="mb-line2">to MentorBe</div>
                        </div>
                        <img class="mb-auth-side-img" src="https://images.unsplash.com/photo-1543269664-56d93c1b41a6?w=500&h=360&fit=crop&auto=format" alt="Community">
                    </div>
                    <div class="mb-auth-card">
                        <h2>Sign up</h2>
                        <?php if ( isset( $_GET['mb_reg_error'] ) ) : ?>
                            <div class="bntm-notice bntm-notice-error mb-auth-notice-wrap"><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['mb_reg_error'] ) ) ); ?></div>
                        <?php endif; ?>
                        <form id="mb-reg-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="mb_register">
                            <div class="mb-auth-input-group">
                                <div class="mb-auth-input"><input type="text" id="mb-full-name" name="full_name" placeholder="Full Name" required autocomplete="name"></div>
                                <div class="mb-auth-input"><input type="email" name="user_email" placeholder="Email Address" required autocomplete="email"></div>
                                <div class="mb-auth-input"><input type="password" name="user_pass" placeholder="Password" required minlength="8" autocomplete="new-password"></div>
                                <div class="mb-auth-input"><input type="password" name="user_pass_confirm" placeholder="Confirm Password" required minlength="8" autocomplete="new-password"></div>
                            </div>
                            <p class="mb-auth-hint">Use at least 8 characters. You'll choose a role next and finish sign up on the profile setup page.</p>
                            <?php wp_nonce_field( 'mb-register', 'mb_register_nonce' ); ?>
                            <button type="submit" class="mb-auth-submit">Continue</button>
                        </form>
                        <p class="mb-auth-switch">Already have an account? <a href="<?php echo esc_url( $lu ); ?>">Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('mb-reg-form');
        if (!form) return;
        var fullName = form.querySelector('input[name="full_name"]');
        var pass = form.querySelector('input[name="user_pass"]');
        var pass2 = form.querySelector('input[name="user_pass_confirm"]');

        function validateFullName() {
            if (!fullName) return true;
            var parts = fullName.value.trim().split(/\s+/).filter(Boolean);
            var ok = parts.length >= 2;
            fullName.setCustomValidity(ok ? '' : 'Please enter at least two words for your full name.');
            return ok;
        }

        function validatePasswords() {
            if (!pass || !pass2) return true;
            var ok = pass.value === pass2.value;
            pass2.setCustomValidity(ok ? '' : 'Passwords do not match.');
            return ok;
        }

        if (fullName) fullName.addEventListener('input', validateFullName);
        if (pass && pass2) {
            pass.addEventListener('input', validatePasswords);
            pass2.addEventListener('input', validatePasswords);
        }

        form.addEventListener('submit', function (e) {
            var ok = validateFullName() && validatePasswords();
            if (!ok) {
                e.preventDefault();
                if (fullName && fullName.validationMessage) {
                    fullName.reportValidity();
                } else if (pass2 && pass2.validationMessage) {
                    pass2.reportValidity();
                }
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_role_choice() {
    $pending = bntm_mb_get_pending_signup();
    $register_url = mb_find_page_url( '[mb_register]' );
    if ( empty( $pending ) && ! is_user_logged_in() ) {
        wp_safe_redirect( $register_url ? $register_url : home_url( '/' ) );
        exit;
    }

    $default_role = ! empty( $pending['role'] ) ? $pending['role'] : ( is_user_logged_in() ? (string) get_user_meta( get_current_user_id(), 'mb_pending_role', true ) : '' );

    mb_dashboard_assets();
    mb_public_assets();
    ob_start(); ?>
    <div class="mb-onboard-shell mb-role-shell">
        <div class="mb-onboard-brand"><img src="<?php echo MB_LOGO_DARK; ?>" alt="MentorBe" class="mb-onboard-logo"></div>
        <div class="mb-onboard-card">
            <a class="mb-onboard-back" href="<?php echo esc_url( $register_url ); ?>" aria-label="Back"><?php echo mb_cv2_icon( 'chevleft', 26 ); ?></a>
            <h2>Choose Role</h2>
            <?php if ( isset( $_GET['mb_onboard_error'] ) ) : ?>
                <div class="bntm-notice bntm-notice-error mb-auth-notice-wrap"><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['mb_onboard_error'] ) ) ); ?></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mb-onboard-form" id="mb-role-choice-form">
                <input type="hidden" name="action" value="mb_select_role">
                <input type="hidden" name="role" id="mb-role-choice-input" value="<?php echo esc_attr( $default_role ); ?>">
                <?php if ( ! empty( $pending ) ) : ?><input type="hidden" name="pending_token" value="<?php echo esc_attr( bntm_mb_get_pending_signup_token() ); ?>"><?php endif; ?>
                <?php wp_nonce_field( 'mb-select-role', 'mb_select_role_nonce' ); ?>
                <button type="button" class="mb-role-option <?php echo $default_role === 'client' ? 'selected' : ''; ?>" data-role="client">Client</button>
                <button type="button" class="mb-role-option <?php echo $default_role === 'provider' ? 'selected' : ''; ?>" data-role="provider">Provider / Mentor</button>
                <button type="submit" class="mb-role-next" id="mb-role-next-btn" <?php echo $default_role ? '' : 'disabled'; ?>>Next</button>
            </form>
        </div>
    </div>
    <style>
    .mb-onboard-shell{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px 16px;position:relative;overflow:hidden;}
    .mb-role-shell{background:#9fd8d1;}
    .mb-role-shell:before{content:'';position:absolute;top:0;bottom:0;width:180px;background:#16796f;border-radius:0 52px 52px 0;box-shadow:0 8px 18px rgba(0,0,0,.12) inset,0 8px 18px rgba(0,0,0,.12);}
    .mb-role-shell:after{content:'';position:absolute;top:0;bottom:0;width:180px;background:#16796f;border-radius:52px 0 0 52px;box-shadow:0 8px 18px rgba(0,0,0,.12) inset,0 8px 18px rgba(0,0,0,.12);}
    .mb-role-shell:before{left:0;}
    .mb-role-shell:after{right:0;}
    .mb-onboard-brand{position:relative;z-index:1;margin-bottom:14px;display:flex;align-items:center;justify-content:center;}
    .mb-onboard-logo{height:42px;width:auto;object-fit:contain;display:block;}
    .mb-onboard-card{position:relative;z-index:1;width:min(100%,500px);background:#f1fffe;border-radius:28px;box-shadow:0 8px 18px rgba(0,0,0,.16);padding:20px 18px 24px;}
    .mb-onboard-back{width:38px;height:38px;border:1px solid #16796f;border-radius:9999px;display:inline-flex;align-items:center;justify-content:center;color:#16796f;background:#f1fffe;}
    .mb-onboard-card h2{font-family:'Montserrat',sans-serif;font-size:clamp(24px,3.2vw,30px);font-weight:800;color:#16796f;text-align:center;margin:12px 0 20px;}
    .mb-onboard-form{display:flex;flex-direction:column;gap:14px;align-items:center;}
    .mb-role-option{width:min(100%,360px);height:46px;border-radius:10px;background:#d7f3f1;color:#16796f;font-size:16px;font-weight:500;border:0px;}
    .mb-role-option.selected{background:#bde6e2;border:0px;box-shadow:0 0 0 1px rgba(22, 121, 111, 0.32) inset;}
    .mb-role-next{width:min(100%,360px);height:46px;border-radius:10px;background:#16796f;color:#fff;font-size:16px;font-weight:600;margin-top:2px;border:0px;}
    .mb-role-next:disabled{opacity:.55;cursor:not-allowed;}
    .mb-role-next:hover:not(:disabled),.mb-role-option:hover{filter:brightness(.98);}
    .mb-auth-notice-wrap{width:min(100%,360px);}
    @media(max-width:900px){.mb-role-shell:before,.mb-role-shell:after{width:100px;border-radius:0 0 24px 24px;}.mb-onboard-card{padding:18px 14px 20px;}}
    @media(max-width:640px){.mb-onboard-logo{height:34px;}.mb-onboard-card{border-radius:24px;}.mb-role-option,.mb-role-next{font-size:15px;}}
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('mb-role-choice-form');
        var input = document.getElementById('mb-role-choice-input');
        var next = document.getElementById('mb-role-next-btn');
        if (!form || !input || !next) return;
        form.querySelectorAll('.mb-role-option').forEach(function (btn) {
            btn.addEventListener('click', function () {
                form.querySelectorAll('.mb-role-option').forEach(function (b) { b.classList.remove('selected'); });
                this.classList.add('selected');
                input.value = this.dataset.role;
                next.disabled = false;
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_profile_setup() {
    $pending = bntm_mb_get_pending_signup();
    $profile = is_user_logged_in() ? mb_get_profile( get_current_user_id() ) : null;
    $role    = ! empty( $pending['role'] ) ? $pending['role'] : ( $profile && in_array( $profile->user_role, [ 'client', 'provider' ], true ) ? $profile->user_role : (string) get_user_meta( get_current_user_id(), 'mb_pending_role', true ) );
    $role    = in_array( $role, [ 'client', 'provider' ], true ) ? $role : '';

    if ( ! $role && empty( $pending ) ) {
        wp_safe_redirect( mb_find_page_url( '[mb_register]' ) );
        exit;
    }

    if ( ! $role ) {
        wp_safe_redirect( mb_find_page_url( '[mb_role_choice]' ) );
        exit;
    }

    $needs_portfolio = $role === 'provider';
    $register_url = mb_find_page_url( '[mb_register]' );
    $role_url     = mb_find_page_url( '[mb_role_choice]' );
    $login_url    = mb_find_page_url( '[mb_login]' );

    mb_dashboard_assets();
    mb_public_assets();
    ob_start(); ?>
    <div class="mb-profile-shell">
        <div class="mb-profile-stage">
            <div class="mb-profile-hero">
                <div class="mb-onboard-brand"><img src="<?php echo MB_LOGO_LIGHT; ?>" alt="MentorBe" class="mb-onboard-logo"></div>
                <h1>Profile Setup</h1>
                <p><?php echo $role === 'provider' ? 'Tell clients who you are and share your portfolio.' : 'Tell mentors and providers who you are so they can find you.'; ?></p>
            </div>
            <div class="mb-profile-card">
                <a class="mb-profile-back" href="<?php echo esc_url( $role_url ?: $register_url ); ?>" aria-label="Back"><?php echo mb_cv2_icon( 'chevleft', 26 ); ?></a>
                <h2><?php echo $role === 'provider' ? 'Provider / Mentor' : 'Client'; ?> Profile</h2>
                <?php if ( isset( $_GET['mb_onboard_error'] ) ) : ?>
                    <div class="bntm-notice bntm-notice-error mb-auth-notice-wrap"><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['mb_onboard_error'] ) ) ); ?></div>
                <?php endif; ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mb-profile-form">
                    <input type="hidden" name="action" value="mb_profile_setup">
                    <input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>">
                    <?php if ( ! empty( $pending ) ) : ?><input type="hidden" name="pending_token" value="<?php echo esc_attr( bntm_mb_get_pending_signup_token() ); ?>"><?php endif; ?>
                    <?php wp_nonce_field( 'mb-profile-setup', 'mb_profile_setup_nonce' ); ?>
                    <select name="region" id="mb-profile-region" class="mb-profile-input" required>
                        <option value="">Select Region</option>
                    </select>
                    <select name="city" id="mb-profile-city" class="mb-profile-input" required disabled>
                        <option value="">Select Region first</option>
                    </select>
                    <textarea name="bio" class="mb-profile-input mb-profile-textarea" placeholder="About / Bio" rows="5" required></textarea>
                    <?php if ( $needs_portfolio ) : ?>
                        <input type="url" name="portfolio_url" class="mb-profile-input" placeholder="Profile URL (optional)" autocomplete="url">
                    <?php endif; ?>
                    <button type="submit" class="mb-profile-submit">Sign Up</button>
                </form>
                <p class="mb-profile-footnote">Already have an account? <a href="<?php echo esc_url( $login_url ); ?>">Log in</a></p>
            </div>
        </div>
    </div>
<style>
.mb-profile-shell{
    min-height:100vh;
    padding:30px;
    background:linear-gradient(135deg,#16796f 0%,#7bcfc5 46%,#97d6cf 100%);
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}
.mb-profile-stage{
    width:min(1120px,100%);
    display:grid;
    grid-template-columns:minmax(0,1fr) minmax(0,1fr);
    gap:28px;
    align-items:center;
}
.mb-profile-hero{
    color:#fff;
    padding:24px 25px;
}
.mb-onboard-brand{
    position:relative;
    z-index:1;
    margin-bottom:14px;
    display:flex;
    align-items:left;
    justify-content:left;
}
.mb-onboard-logo{
    height:42px;
    width:auto;
    object-fit:contain;
    display:block;
}
.mb-profile-hero h1{
    font-family:'Montserrat',sans-serif;
    font-size:clamp(38px,4.5vw,60px);
    font-weight:800;
    line-height:0.95;
    letter-spacing:-2px;
    margin:0;
    text-shadow:0 4px 10px rgba(0,0,0,.18);
}
.mb-profile-hero p{
    max-width:390px;
    margin-top:12px;
    font-size:17px;
    line-height:1.6;
    color:rgba(255,255,255,.92);
    font-weight:500;
}
.mb-profile-card{
    width:min(100%,480px);
    justify-self:end;
    background:#f1fffe;
    border-radius:38px;
    box-shadow:0 20px 45px rgba(0,0,0,.18);
    padding:30px 28px 32px;
    position:relative;
}
.mb-profile-back{
    width:38px;
    height:38px;
    border:1px solid #16796f;
    border-radius:9999px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    color:#16796f;
    background:#f1fffe;
}
.mb-profile-card h2{
    font-family:'Montserrat',sans-serif;
    font-size:clamp(26px,3vw,30px);
    font-weight:800;
    color:#16796f;
    text-align:center;
    margin:12px 10px 18px;
}
.mb-profile-form{
    display:flex;
    flex-direction:column;
    gap:12px;
}
.mb-profile-input{
    width:100%;
    border:1px solid #16796f;
    border-radius:12px;
    padding:12px 16px;
    font-size:16px;
    font-weight:500;
    color:#16796f;
    background:transparent;
    font-family:'Montserrat',sans-serif;
    outline:none;
}
.mb-profile-input::placeholder{color:#16796f;opacity:.9;}
.mb-profile-textarea{min-height:140px;resize:vertical;}
.mb-profile-submit{
    width:100%;
    height:48px;
    border-radius:12px;
    background:#16796f;
    color:#fff;
    font-family:'Montserrat',sans-serif;
    font-size:16px;
    font-weight:700;
    border: 0px;
    cursor:pointer;
}
.mb-profile-footnote{
    margin-top:12px;
    text-align:center;
    color:rgba(255,255,255,.9);
    font-size:14px;
}
.mb-profile-footnote a{color:#fff;font-weight:700;}
.mb-auth-notice-wrap{width:100%;}
@media(max-width:900px){
    .mb-profile-stage{grid-template-columns:1fr;gap:18px;}
    .mb-profile-hero{padding:12px 4px 0;text-align:center;}
    .mb-profile-hero p{margin-left:auto;margin-right:auto;}
    .mb-profile-card{justify-self:stretch;border-radius:32px;padding:24px 18px 26px;}
}
@media(max-width:640px){
    .mb-profile-shell{padding:16px;}
    .mb-onboard-logo{height:34px;}
    .mb-profile-hero h1{font-size:38px;}
    .mb-profile-input{font-size:16px;}
    .mb-profile-submit{font-size:16px;height:48px;}
}
</style>
<?php mb_ph_locations_script(); ?>
<script>
(function(){
    var regionSel = document.getElementById('mb-profile-region');
    var citySel = document.getElementById('mb-profile-city');
    if (!regionSel || !citySel || !window.MB_PH_LOCATIONS) return;
    Object.keys(window.MB_PH_LOCATIONS).forEach(function(region){
        var opt = document.createElement('option');
        opt.value = region;
        opt.textContent = region;
        regionSel.appendChild(opt);
    });
    regionSel.addEventListener('change', function(){
        var cities = window.MB_PH_LOCATIONS[this.value] || [];
        citySel.innerHTML = '';
        if (!cities.length) {
            citySel.disabled = true;
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select Region first';
            citySel.appendChild(placeholder);
            return;
        }
        citySel.disabled = false;
        var def = document.createElement('option');
        def.value = '';
        def.textContent = 'Select City';
        citySel.appendChild(def);
        cities.forEach(function(city){
            var opt = document.createElement('option');
            opt.value = city;
            opt.textContent = city;
            citySel.appendChild(opt);
        });
    });
})();
</script>
<?php
return ob_get_clean();
}

function bntm_shortcode_mb_inbox() {
    if ( ! is_user_logged_in() ) {
        $login_url = bntm_mb_wp_login_url( home_url( '/inbox/' ) );
        return '<div class="bntm-notice">Please <a href="' . esc_url( $login_url ) . '">log in</a> to access your inbox.</div>';
    }
    mb_dashboard_assets();
    mb_client_v2_assets();
    $user_id  = get_current_user_id();
    $profile  = mb_get_profile( $user_id );
    $role     = $profile ? $profile->user_role : 'client';
    $dash_u   = bntm_mb_dashboard_url_for_role( $role );
    $set_u    = add_query_arg( 'tab', 'settings', $dash_u );
    $manage_u = add_query_arg( 'tab', 'manage_tasks', $dash_u );
    $user     = wp_get_current_user();
    $avatar_url = $profile && $profile->pfp_url ? $profile->pfp_url : '';
    $opened_conv = isset( $_GET['conv'] ) ? intval( $_GET['conv'] ) : 0;
    ob_start(); ?>
    <div class="mb-cv2">
        <nav class="topnav">
            <div class="topnav-inner">
                <a href="<?php echo esc_url( $dash_u ); ?>" class="nav-logo"><img src="<?php echo MB_LOGO_DARK; ?>" alt="MentorBe"></a>
                <div class="nav-right">
                    <?php if ( $role === 'client' ): ?><a href="<?php echo esc_url( add_query_arg( 'tab', 'post_task', $dash_u ) ); ?>" class="post-task-btn"><?php echo mb_cv2_icon( 'plus', 16 ); ?> Post a task</a><?php endif; ?>
                    <div class="nav-icons-wrap">
                        <a class="nav-icon-btn" title="Home" href="<?php echo esc_url( $dash_u ); ?>"><?php echo mb_cv2_icon( 'home', 22 ); ?></a>
                        <button type="button" class="nav-icon-btn" id="mb-cv2-notif-btn" title="Notifications" onclick="mbCv2ToggleNotif(event)"><?php echo mb_cv2_icon( 'bell', 22 ); ?></button>
                        <?php if ( $role === 'provider' ): ?>
                            <a class="nav-icon-btn active" title="Inbox" href="#"><?php echo mb_cv2_icon( 'msg', 22 ); ?></a>
                            <a class="nav-icon-btn" title="Active Jobs" href="<?php echo esc_url( add_query_arg( 'section', 'jobs', $dash_u ) ); ?>"><?php echo mb_cv2_icon( 'briefcase', 22 ); ?></a>
                            <a class="nav-icon-btn" title="Earnings & Reviews" href="<?php echo esc_url( add_query_arg( 'section', 'earnings', $dash_u ) ); ?>"><?php echo mb_cv2_icon( 'dollar-sign', 22 ); ?></a>
                        <?php else: ?>
                            <a class="nav-icon-btn active" title="Inbox" href="#"><?php echo mb_cv2_icon( 'msg', 22 ); ?></a>
                            <a class="nav-icon-btn" title="Manage Tasks" href="<?php echo esc_url( $manage_u ); ?>"><?php echo mb_cv2_icon( 'clipboard', 22 ); ?></a>
                            <a class="nav-icon-btn" title="Settings" href="<?php echo esc_url( $set_u ); ?>"><?php echo mb_cv2_icon( 'settings', 22 ); ?></a>
                        <?php endif; ?>
                        <div class="notif-dd" id="mb-cv2-notif-dd" style="display:none;">
                            <div class="notif-dd-header"><?php echo mb_cv2_icon( 'bell', 28 ); ?><span>Notifications</span><button type="button" class="notif-dd-close" onclick="mbCv2CloseNotif()"><?php echo mb_cv2_icon( 'x', 16 ); ?></button></div>
                            <div class="notif-dd-list"><?php echo mb_render_notifications_list( $user_id ); ?></div>
                        </div>
                    </div>
                    <?php if ( $role === 'client' ): ?>
                        <div class="nav-profile-wrap">
                            <button type="button" class="nav-avatar-btn" onclick="mbCv2ToggleProfile(event)"><?php echo $avatar_url ? '<img src="' . esc_url( $avatar_url ) . '" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;">' : mb_cv2_icon( 'user', 20 ); ?></button>
                            <div class="profile-dd" id="mb-cv2-profile-dd" style="display:none;">
                                <div class="profile-dd-header">
                                    <div class="profile-dd-avatar"><?php echo mb_cv2_icon( 'user', 36 ); ?></div>
                                    <div><p class="profile-dd-name"><?php echo esc_html( $user->display_name ); ?></p><p class="profile-dd-email"><?php echo esc_html( $user->user_email ); ?></p></div>
                                </div>
                                <div class="profile-dd-divider"></div>
                                <div class="profile-dd-logout-row"><a href="<?php echo esc_url( wp_logout_url( mb_find_page_url( '[mb_login]' ) ) ); ?>" style="color:var(--danger);font-weight:700;font-size:14px;display:flex;align-items:center;gap:8px;text-decoration:none;">Log out <?php echo mb_cv2_icon( 'logout', 18 ); ?></a></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        <div class="inbox-shell">
            <div class="inbox-sidebar">
                <div class="inbox-title"><h1>Inbox</h1></div>
                <div class="inbox-search">
                    <?php echo mb_cv2_icon( 'search', 16 ); ?>
                    <input type="text" id="mb-inbox-search" placeholder="Search...">
                </div>
                <div class="inbox-tabs">
                    <button type="button" class="active" data-filter="all">All</button>
                    <button type="button" data-filter="unread">Unread</button>
                </div>
                <div class="convo-list" id="mb-convo-list"><div class="inbox-empty-list">Loading...</div></div>
            </div>
            <div class="chat-wrap">
                <div class="chat-panel" id="mb-chat-panel" style="display:none;">
                    <div class="chat-header">
                        <div class="chat-header-left">
                            <?php echo mb_cv2_icon( 'user', 20 ); ?>
                            <p id="mb-chat-name">Select a conversation</p>
                        </div>
                        <button type="button" id="mb-chat-profile-btn" style="display:none;">View Profile</button>
                    </div>
                    <div class="chat-body" id="mb-chat-body"></div>
                    <div class="chat-input-row">
                        <button type="button" class="attach-btn"><?php echo mb_cv2_icon( 'upload', 18 ); ?></button>
                        <div class="chat-input-box"><input type="text" id="mb-chat-input" placeholder="Aa" disabled></div>
                        <button type="button" class="send-btn" id="mb-chat-send" disabled><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg></button>
                    </div>
                </div>
                <div class="inbox-empty-list" id="mb-chat-placeholder" style="margin:auto;">Select a conversation to start chatting.</div>
            </div>
        </div>
    </div>
    <script>
    (function(){
        var current = { id: 0 };
        var filter  = 'all';
        var openOnLoad = <?php echo (int) $opened_conv; ?>;

        function renderConvos(list) {
            var box = document.getElementById('mb-convo-list');
            if (!list.length) { box.innerHTML = '<div class="inbox-empty-list">No conversations yet.</div>'; return; }
            box.innerHTML = list.map(function(c){
                var avatar = c.avatar ? '<img src="'+c.avatar+'" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;">' : '<?php echo mb_cv2_icon( "user", 22 ); ?>';
                return '<button type="button" class="convo-item'+(c.unread?' unread':'')+'" data-id="'+c.id+'">'
                    + '<div class="convo-avatar">'+avatar+'<span class="online-dot off"></span></div>'
                    + '<div class="convo-meta"><div class="convo-top"><strong>'+c.name+'</strong><div class="convo-time-wrap"><span>'+c.time+'</span>'+(c.unread?'<span class="convo-badge">&nbsp;</span>':'')+'</div></div><p>'+c.last+'</p></div>'
                    + '</button>';
            }).join('');
            box.querySelectorAll('.convo-item').forEach(function(btn){
                btn.addEventListener('click', function(){ openConversation(this.dataset.id); });
            });
        }

        function loadConvos() {
            var fd = new FormData();
            fd.append('action', 'mb_get_conversations'); fd.append('filter', filter); fd.append('nonce', mbNonce);
            fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(json){
                if (json.success) {
                    renderConvos(json.data.conversations);
                    if (openOnLoad) { openConversation(openOnLoad); openOnLoad = 0; }
                }
            });
        }

        function openConversation(id) {
            current.id = id;
            document.querySelectorAll('.convo-item').forEach(function(b){ b.classList.toggle('active', b.dataset.id == id); b.classList.remove('unread'); });
            var fd = new FormData();
            fd.append('action', 'mb_get_messages'); fd.append('conversation_id', id); fd.append('nonce', mbNonce);
            fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(json){
                if (!json.success) return;
                document.getElementById('mb-chat-placeholder').style.display = 'none';
                document.getElementById('mb-chat-panel').style.display = 'flex';
                document.getElementById('mb-chat-name').textContent = json.data.other_name;
                document.getElementById('mb-chat-input').disabled = false;
                document.getElementById('mb-chat-send').disabled = false;
                var profileBtn = document.getElementById('mb-chat-profile-btn');
                if (profileBtn) {
                    profileBtn.style.display = json.data.other_profile_url ? 'inline-flex' : 'none';
                    profileBtn.onclick = json.data.other_profile_url ? function(){ window.location.href = json.data.other_profile_url; } : null;
                }
                renderMessages(json.data.messages);
            });
        }

        function renderMessages(msgs) {
            var body = document.getElementById('mb-chat-body');
            if (!msgs.length) { body.innerHTML = '<p style="text-align:center;color:#899998;margin-top:40px;">No messages yet. Say hello!</p>'; return; }
            body.innerHTML = msgs.map(function(m){ return '<div class="bubble-row'+(m.mine?' mine':'')+'"><div class="bubble">'+m.body+'</div></div>'; }).join('');
            body.scrollTop = body.scrollHeight;
        }

        function sendMessage() {
            var inp = document.getElementById('mb-chat-input');
            var text = inp.value.trim();
            if (!text || !current.id) return;
            inp.value = '';
            var fd = new FormData();
            fd.append('action', 'mb_send_message'); fd.append('conversation_id', current.id); fd.append('body', text); fd.append('nonce', mbNonce);
            fetch(ajaxurl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(json){
                if (json.success) { openConversation(current.id); loadConvos(); }
            });
        }

        document.getElementById('mb-chat-send').addEventListener('click', sendMessage);
        document.getElementById('mb-chat-input').addEventListener('keydown', function(e){ if (e.key === 'Enter') sendMessage(); });
        document.querySelectorAll('.inbox-tabs button').forEach(function(btn){
            btn.addEventListener('click', function(){
                document.querySelectorAll('.inbox-tabs button').forEach(function(b){b.classList.remove('active');});
                this.classList.add('active'); filter = this.dataset.filter; loadConvos();
            });
        });
        document.getElementById('mb-inbox-search').addEventListener('input', function(){
            var q = this.value.toLowerCase();
            document.querySelectorAll('#mb-convo-list .convo-item').forEach(function(item){
                item.style.display = item.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
            });
        });

        loadConvos();
    })();
    </script>
    <script>
    (function(){
        function closeAll(){ document.getElementById('mb-cv2-notif-dd').style.display='none'; document.getElementById('mb-cv2-profile-dd').style.display='none'; }
        window.mbCv2ToggleNotif = function(ev){ ev.stopPropagation(); var d=document.getElementById('mb-cv2-notif-dd'); var open=d.style.display==='block'; closeAll(); d.style.display = open?'none':'block'; };
        window.mbCv2ToggleProfile = function(ev){ ev.stopPropagation(); var d=document.getElementById('mb-cv2-profile-dd'); var open=d.style.display==='block'; closeAll(); d.style.display = open?'none':'block'; };
        window.mbCv2CloseNotif = function(){ document.getElementById('mb-cv2-notif-dd').style.display='none'; };
        document.addEventListener('click', function(e){ if(!e.target.closest('.nav-icons-wrap') && !e.target.closest('.nav-profile-wrap')) closeAll(); });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_earnings() {
    if ( ! is_user_logged_in() ) {
        $login_url = bntm_mb_wp_login_url( home_url( '/earnings/' ) );
        return '<div class="bntm-notice">Please <a href="' . esc_url( $login_url ) . '">log in</a> to view your earnings.</div>';
    }
    ob_start(); ?>
    <div class="mb-coming-soon-wrap">
        <div class="mb-coming-soon-icon"><svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2"/></svg></div>
        <h2>Earnings</h2>
        <p>Your full earnings ledger, payout history, and withdrawal options are coming in Phase 2 with the escrow system.</p>
    </div>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_profile_view() {
    mb_dashboard_assets();
    mb_public_assets();
    mb_client_v2_assets();
    global $wpdb;
    $uid     = isset( $_GET['mb_user'] ) ? intval( $_GET['mb_user'] ) : 0;
    if ( ! $uid ) return '<div class="bntm-notice">No profile specified.</div>';
    $user    = get_userdata( $uid );
    $profile = mb_get_profile( $uid );
    if ( ! $user || ! $profile ) return '<div class="bntm-notice">Profile not found.</div>';

    $is_provider = $profile->user_role === 'provider';
    $skills = $is_provider
        ? $wpdb->get_results( $wpdb->prepare( "SELECT s.name FROM {$wpdb->prefix}mb_provider_skills ps INNER JOIN {$wpdb->prefix}mb_skills s ON ps.skill_id=s.id WHERE ps.user_id=%d AND ps.status='active' ORDER BY s.name ASC", $uid ) )
        : [];

    $completed = $is_provider
        ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE provider_id=%d AND status='completed'", $uid ) )
        : (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE client_id=%d AND status='completed'", $uid ) );
    $posted = $is_provider
        ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE provider_id=%d", $uid ) )
        : (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE client_id=%d", $uid ) );
    $member_since  = date( 'F Y', strtotime( $user->user_registered ) );
    $success_rate  = $posted > 0 ? round( ( $completed / $posted ) * 100 ) . '%' : 'N/A';
    $rating_summary = mb_get_review_summary( $uid );
    $headline      = (string) get_user_meta( $uid, 'mb_profile_headline', true );
    $city          = (string) get_user_meta( $uid, 'mb_profile_city', true );
    $region        = (string) get_user_meta( $uid, 'mb_profile_region', true );
    $location      = trim( $city . ( $city && $region ? ', ' : '' ) . $region );
    $cover_url     = (string) get_user_meta( $uid, 'mb_cover_url', true );
    $cover_style   = $cover_url ? 'style="background-image:url(' . esc_url( $cover_url ) . ');"' : '';

    $viewer_id = get_current_user_id();
    $viewer_profile = $viewer_id ? mb_get_profile( $viewer_id ) : null;
    $viewer_role = $viewer_profile ? $viewer_profile->user_role : 'client';
    $dash_url = $viewer_profile ? bntm_mb_dashboard_url_for_role( $viewer_role ) : home_url( '/' );
    $post_task_url = add_query_arg( 'tab', 'post_task', $dash_url );
    $manage_tasks_url = add_query_arg( 'tab', 'manage_tasks', $dash_url );
    $settings_url = add_query_arg( 'tab', 'settings', $dash_url );
    $inbox_url = mb_find_page_url( '[mb_inbox]' );
    $viewer_avatar = $viewer_profile && $viewer_profile->pfp_url ? $viewer_profile->pfp_url : '';

    $recent_tasks = $is_provider
        ? $wpdb->get_results( $wpdb->prepare( "SELECT title, updated_at, budget, status FROM {$wpdb->prefix}mb_tasks WHERE provider_id=%d ORDER BY updated_at DESC LIMIT 3", $uid ) )
        : $wpdb->get_results( $wpdb->prepare( "SELECT title, updated_at, budget, status FROM {$wpdb->prefix}mb_tasks WHERE client_id=%d ORDER BY updated_at DESC LIMIT 3", $uid ) );

    ob_start(); ?>
    <div class="mb-pv2-shell">
    <div class="mb-pv2 mb-cv2">
        <nav class="topnav">
            <div class="topnav-inner">
                <a href="<?php echo esc_url( $dash_url ); ?>" class="nav-logo"><img src="<?php echo MB_LOGO_DARK; ?>" alt="MentorBe"></a>
                <div class="nav-right">
                    <?php if ( $viewer_role === 'client' ): ?>
                    <a href="<?php echo esc_url( $post_task_url ); ?>" class="post-task-btn"><?php echo mb_cv2_icon( 'plus', 16 ); ?> Post a task</a>
                    <?php endif; ?>
                    <div class="nav-icons-wrap">
                        <a class="nav-icon-btn" title="Home" href="<?php echo esc_url( $dash_url ); ?>"><?php echo mb_cv2_icon( 'home', 22 ); ?></a>
                        <button type="button" class="nav-icon-btn" id="mb-pv-notif-btn" title="Notifications" onclick="mbPvToggleNotif(event)"><?php echo mb_cv2_icon( 'bell', 22 ); ?></button>
                        <a class="nav-icon-btn" title="Inbox" href="<?php echo esc_url( $inbox_url ); ?>"><?php echo mb_cv2_icon( 'msg', 22 ); ?></a>
                        <a class="nav-icon-btn" title="Manage Tasks" href="<?php echo esc_url( $manage_tasks_url ); ?>"><?php echo mb_cv2_icon( 'clipboard', 22 ); ?></a>
                        <a class="nav-icon-btn" title="Settings" href="<?php echo esc_url( $settings_url ); ?>"><?php echo mb_cv2_icon( 'settings', 22 ); ?></a>
                        <div class="notif-dd" id="mb-pv-notif-dd" style="display:none;">
                            <div class="notif-dd-header"><?php echo mb_cv2_icon( 'bell', 28 ); ?><span>Notifications</span><button type="button" class="notif-dd-close" onclick="mbPvCloseNotif()"><?php echo mb_cv2_icon( 'x', 16 ); ?></button></div>
                            <div class="notif-dd-list"><?php echo mb_render_notifications_list( $viewer_id ); ?></div>
                        </div>
                    </div>
                    <div class="nav-profile-wrap">
                        <button type="button" class="nav-avatar-btn" onclick="mbPvToggleProfile(event)"><?php echo $viewer_avatar ? '<img src="' . esc_url( $viewer_avatar ) . '" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;">' : mb_cv2_icon( 'user', 20 ); ?></button>
                        <div class="profile-dd" id="mb-pv-profile-dd" style="display:none;">
                            <div class="profile-dd-header">
                                <div class="profile-dd-avatar"><?php echo $viewer_avatar ? '<img src="' . esc_url( $viewer_avatar ) . '" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;">' : mb_cv2_icon( 'user', 36 ); ?></div>
                                <div><p class="profile-dd-name"><?php echo esc_html( $viewer_profile ? ucfirst( $viewer_profile->user_role ) : 'Visitor' ); ?></p><p class="profile-dd-email"><?php echo esc_html( $viewer_id ? wp_get_current_user()->user_email : 'Not signed in' ); ?></p></div>
                            </div>
                            <div class="profile-dd-divider"></div>
                            <div class="profile-dd-section">
                                <p class="profile-dd-label">Account</p>
                                <a class="profile-dd-link" href="<?php echo esc_url( $settings_url ); ?>">Edit Profile</a>
                                <a class="profile-dd-link" href="<?php echo esc_url( mb_find_page_url( '[mb_help]' ) ); ?>">Help</a>
                            </div>
                            <div class="profile-dd-divider"></div>
                            <div class="profile-dd-logout-row"><button type="button" onclick="mbPvRequestLogout('<?php echo esc_js( wp_logout_url( mb_find_page_url( '[mb_login]' ) ) ); ?>')" style="background:none;border:none;padding:0;color:var(--danger);font-weight:700;font-size:14px;display:flex;align-items:center;gap:8px;text-decoration:none;">Log out <?php echo mb_cv2_icon( 'logout', 18 ); ?></button></div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="page">
            <div class="card about-card">
                <div class="cover <?php echo $cover_url ? 'has-image' : ''; ?>" <?php echo $cover_style; ?>></div>
                <div class="profile-body">
                    <div class="avatar-wrap">
                        <div class="avatar">
                            <?php if ( $profile->pfp_url ): ?>
                                <img src="<?php echo esc_url( $profile->pfp_url ); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="<?php echo esc_attr( $user->display_name ); ?>">
                            <?php else: ?>
                                <svg width="56" height="56" viewBox="0 0 46.6667 46.6667" fill="none"><path d="M23.3333 23.3333C20.125 23.3333 17.3785 22.191 15.0938 19.9063C12.809 17.6215 11.6667 14.875 11.6667 11.6667C11.6667 8.45833 12.809 5.71181 15.0938 3.42708C17.3785 1.14236 20.125 0 23.3333 0C26.5417 0 29.2882 1.14236 31.5729 3.42708C33.8576 5.71181 35 8.45833 35 11.6667C35 14.875 33.8576 17.6215 31.5729 19.9063C29.2882 22.191 26.5417 23.3333 23.3333 23.3333ZM0 46.6667V38.5C0 36.8472 0.425347 35.3281 1.27604 33.9427C2.12674 32.5573 3.25694 31.5 4.66667 30.7708C7.68056 29.2639 10.7431 28.1337 13.8542 27.3802C16.9653 26.6267 20.125 26.25 23.3333 26.25C26.5417 26.25 29.7014 26.6267 32.8125 27.3802C35.9236 28.1337 38.9861 29.2639 42 30.7708C43.4097 31.5 44.5399 32.5573 45.3906 33.9427C46.2413 35.3281 46.6667 36.8472 46.6667 38.5V46.6667H0Z" fill="white"/></svg>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="name-row">
                        <span class="name"><?php echo esc_html( $user->display_name ); ?></span>
                        <?php if ( $profile->verification_status === 'verified' ): ?>
                            <span class="verified-badge" title="Verified"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#24B7A8"/><path d="M6 10.2L8.6 12.8L14 7" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <?php endif; ?>
                    </div>
                    <div class="stars-row">
                        <span class="stars-label"><?php echo esc_html( ucfirst( $profile->user_role ) ); ?> &middot; <?php echo number_format( $completed ); ?> task<?php echo $completed === 1 ? '' : 's'; ?> completed</span>
                        <span class="stars-label" style="margin-left:10px;"><?php echo esc_html( $rating_summary['label'] ); ?> rating</span>
                    </div>
                    <p class="headline"><?php echo esc_html( $headline ?: ( $is_provider ? 'Professional services and mentorship profile' : 'Client profile and posting history' ) ); ?></p>
                    <?php if ( $location ): ?>
                    <div class="loc-row">
                        <svg width="18" height="18" viewBox="0 0 16 20" fill="none"><path d="M8 10C8.55 10 9.02083 9.80417 9.4125 9.4125C9.80417 9.02083 10 8.55 10 8C10 7.45 9.80417 6.97917 9.4125 6.5875C9.02083 6.19583 8.55 6 8 6C7.45 6 6.97917 6.19583 6.5875 6.5875C6.19583 6.97917 6 7.45 6 8C6 8.55 6.19583 9.02083 6.5875 9.4125C6.97917 9.80417 7.45 10 8 10ZM8 20C5.31667 17.7167 3.3125 15.5958 1.9875 13.6375C0.6625 11.6792 0 9.86667 0 8.2C0 5.7 0.804167 3.70833 2.4125 2.225C4.02083 0.741667 5.88333 0 8 0C10.1167 0 11.9792 0.741667 13.5875 2.225C15.1958 3.70833 16 5.7 16 8.2C16 9.86667 15.3375 11.6792 14.0125 13.6375C12.6875 15.5958 10.6833 17.7167 8 20Z" fill="#71928F"/></svg>
                        <span><?php echo esc_html( $location ); ?></span>
                    </div>
                    <?php endif; ?>
                    <p class="section-title">About Me</p>
                    <p class="about-text"><?php echo nl2br( esc_html( $profile->bio ?: 'No bio added yet.' ) ); ?></p>
                    <?php if ( $is_provider && ! empty( $skills ) ): ?>
                        <p class="section-title">Verified Skills</p>
                        <div class="skills">
                            <?php foreach ( $skills as $s ): ?>
                                <span class="skill-tag"><?php echo esc_html( $s->name ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ( get_current_user_id() && get_current_user_id() !== $uid ): ?>
                        <div style="margin-top:24px;">
                            <button type="button" id="mb-msg-btn" class="mb-pv2-btn" data-user="<?php echo (int) $uid; ?>">Message <?php echo esc_html( $user->display_name ); ?></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar">
                <div class="sidebar-card">
                    <h3><?php echo esc_html( $is_provider ? 'Mentor Statistics' : 'Client Statistics' ); ?></h3>
                    <ul class="stats-list">
                        <li><span style="font-weight:400">Tasks Completed:</span> <strong><?php echo number_format( $completed ); ?></strong></li>
                        <li><span style="font-weight:400">Success Rate:</span> <strong><?php echo esc_html( $success_rate ); ?></strong></li>
                        <li><span style="font-weight:400">Average Rating:</span> <strong><?php echo esc_html( $rating_summary['label'] ); ?></strong></li>
                        <li><span style="font-weight:400">Member Since:</span> <strong><?php echo esc_html( $member_since ); ?></strong></li>
                    </ul>
                </div>
                <div class="sidebar-card">
                    <h3>Verifications</h3>
                    <div class="verif-item">
                        <svg width="18" height="18" viewBox="0 0 15.3333 11.1667" fill="none"><path d="M14.3333 1L5.16667 10.1667L1 6" stroke="#24B7A8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                        <p class="verif-text"><span style="font-weight:400">Email Address:</span> <strong>Verified</strong></p>
                    </div>
                    <div class="verif-item">
                        <svg width="18" height="18" viewBox="0 0 15.3333 11.1667" fill="none"><path d="M14.3333 1L5.16667 10.1667L1 6" stroke="<?php echo $profile->verification_status === 'verified' ? '#24B7A8' : '#899998'; ?>" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                        <p class="verif-text"><span style="font-weight:400">Identity Document:</span> <strong><?php echo esc_html( ucfirst( $profile->verification_status ) ); ?></strong></p>
                    </div>
                </div>
            </div>

            <div class="card work-card">
                <h2>Recent Activity<?php echo $is_provider ? ' &amp; Reviews' : ''; ?></h2>
                <?php if ( empty( $recent_tasks ) ): ?>
                    <p class="review-quote" style="font-style:normal;">No recent activity yet.</p>
                <?php else: ?>
                    <div class="reviews">
                        <?php foreach ( $recent_tasks as $task ): ?>
                            <div class="review">
                                <p class="review-task"><?php echo esc_html( $task->title ); ?></p>
                                <div class="review-stars-row">
                                    <span class="review-meta"><strong><?php echo mb_price( $task->budget ); ?></strong> | <em><?php echo esc_html( ucwords( str_replace( '_', ' ', $task->status ) ) ); ?></em></span>
                                </div>
                                <p class="review-quote">Updated <?php echo esc_html( human_time_diff( strtotime( $task->updated_at ), current_time( 'timestamp' ) ) ); ?> ago</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ( $is_provider ): ?>
                    <p class="review-quote" style="font-style:normal;margin-top:8px;">Average rating: <?php echo esc_html( $rating_summary['label'] ); ?> from <?php echo number_format( $rating_summary['count'] ); ?> review<?php echo $rating_summary['count'] === 1 ? '' : 's'; ?>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>

    <div class="mb-cv2">
        <div class="settings-modal" id="mb-pv-logout-modal" aria-hidden="true">
            <div class="settings-modal-card">
                <h3>Log out?</h3>
                <p>Are you sure you want to log out of MentorBe? You can sign back in anytime from the login page.</p>
                <div class="settings-modal-actions">
                    <button type="button" class="outline-btn2" onclick="mbPvCloseLogout()">Cancel</button>
                    <button type="button" class="save-btn" onclick="mbPvConfirmLogout()">Log out</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .mb-pv2-shell { background:#16796f; min-height:100vh; }
    .mb-pv2 * { box-sizing:border-box; }
    .mb-pv2 { font-family:'Inter', sans-serif; }
    .mb-pv2 img { max-width:100%; display:block; }
    .mb-pv2 h1, .mb-pv2 h2, .mb-pv2 h3, .mb-pv2 p, .mb-pv2 ul { margin:0; }
    .mb-pv2 button { font-family:inherit; cursor:pointer; }


    .mb-pv2 .page { max-width:1200px; margin:32px auto; padding:0 32px 48px; display:grid; grid-template-columns:1fr 340px; grid-template-rows:auto auto; gap:20px; }
    .mb-pv2 .card { background:#f1fffe; border-radius:14px; box-shadow:0 4px 18px rgba(0,0,0,0.18); }

    .mb-pv2 .about-card { grid-column:1; grid-row:1; overflow:visible; }
    .mb-pv2 .cover { height:149px; background:#899998; border-radius:14px 14px 0 0; }
    .mb-pv2 .cover.has-image { background-size:cover; background-position:center; }
    .mb-pv2 .profile-body { padding:0 40px 32px; }
    .mb-pv2 .avatar-wrap { display:inline-block; margin-top:-64px; margin-bottom:16px; }
    .mb-pv2 .avatar { width:140px; height:140px; border-radius:50%; background:#454646; border:3px solid white; display:flex; align-items:center; justify-content:center; overflow:hidden; box-shadow:0 8px 20px rgba(0,0,0,.2); }
    .mb-pv2 .name-row { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
    .mb-pv2 .name { font-weight:700; font-size:26px; color:#000; }
    .mb-pv2 .verified-badge { width:20px; height:20px; display:inline-flex; }
    .mb-pv2 .stars-row { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
    .mb-pv2 .stars-label { font-size:14px; color:#333; }
    .mb-pv2 .headline { font-family:'Inter', sans-serif; font-weight:500; font-size:14px; color:#333; margin-bottom:10px; }
    .mb-pv2 .loc-row { display:flex; align-items:center; gap:5px; margin-bottom:22px; color:#0f504a; font-size:14px; font-weight:500; }
    .mb-pv2 .section-title { font-weight:600; font-size:18px; color:#000; margin-bottom:10px; margin-top:20px; }
    .mb-pv2 .about-text { font-family:'Montserrat', sans-serif; font-size:14px; color:#000; line-height:1.65; text-align:justify; margin-bottom:18px; white-space:normal; }
    .mb-pv2 .skills { display:flex; flex-wrap:wrap; gap:8px; margin-top:6px; }
    .mb-pv2 .skill-tag { background:#617876; color:white; font-family:'Montserrat', sans-serif; font-weight:500; font-size:13px; padding:5px 16px; border-radius:16px; }
    .mb-pv2 .mb-pv2-btn { display:inline-flex; align-items:center; justify-content:center; border:none; border-radius:9999px; padding:12px 22px; background:#16796f; color:#fff; font-weight:700; font-size:14px; transition:opacity .15s; }
    .mb-pv2 .mb-pv2-btn:hover { opacity:.9; }

    .mb-pv2 .sidebar { grid-column:2; grid-row:1; display:flex; flex-direction:column; gap:16px; }
    .mb-pv2 .sidebar-card { background:#f1fffe; border-radius:14px; box-shadow:0 4px 14px rgba(0,0,0,0.18); padding:22px 24px; }
    .mb-pv2 .sidebar-card h3 { font-weight:600; font-size:17px; color:#617876; margin-bottom:14px; }
    .mb-pv2 .stats-list { list-style:disc; padding-left:18px; display:flex; flex-direction:column; gap:5px; }
    .mb-pv2 .stats-list li { font-family:'Montserrat', sans-serif; font-size:14px; color:#000; line-height:1.5; }
    .mb-pv2 .verif-item { display:flex; align-items:flex-start; gap:8px; margin-bottom:8px; }
    .mb-pv2 .verif-item:last-child { margin-bottom:0; }
    .mb-pv2 .verif-item svg { flex-shrink:0; margin-top:3px; }
    .mb-pv2 .verif-text { font-family:'Montserrat', sans-serif; font-size:14px; color:#000; line-height:1.5; }

    .mb-pv2 .work-card { grid-column:1 / -1; grid-row:2; padding:30px 36px 28px; }
    .mb-pv2 .work-card h2 { font-weight:600; font-size:18px; color:#000; margin-bottom:20px; }
    .mb-pv2 .reviews { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
    @media (max-width:900px) { .mb-pv2 .reviews { grid-template-columns:1fr; } }
    .mb-pv2 .review { background:rgba(179,217,213,0.77); border-radius:16px; padding:18px 20px 16px; }
    .mb-pv2 .review-task { font-family:'Montserrat', sans-serif; font-weight:600; font-size:16px; color:#000; margin-bottom:8px; }
    .mb-pv2 .review-stars-row { display:flex; align-items:center; gap:6px; margin-bottom:10px; }
    .mb-pv2 .review-meta { font-size:13px; color:#0f504a; }
    .mb-pv2 .review-quote { font-family:'Montserrat', sans-serif; font-size:13px; color:#000; line-height:1.5; font-style:italic; }


    @media (max-width:900px) {
        .mb-pv2 .page { grid-template-columns:1fr; }
        .mb-pv2 .sidebar { grid-column:1; grid-row:auto; }
        .mb-pv2 .work-card { grid-column:1; grid-row:auto; }
    }
    @media (max-width:560px) {
        .mb-pv2 .page { padding-left:16px; padding-right:16px; }
        .mb-pv2 .profile-body { padding-left:20px; padding-right:20px; }
        .mb-pv2 .cover { height:100px; }
        .mb-pv2 .avatar-wrap { margin-top:-44px; }
        .mb-pv2 .avatar { width:88px; height:88px; }
        .mb-pv2 .name { font-size:22px; }
    }
    </style>
    <script>
    (function(){
        var btn = document.getElementById('mb-msg-btn');
        if (!btn) return;
        btn.addEventListener('click', function(){
            var fd = new FormData();
            fd.append('action','mb_start_conversation');
            fd.append('user_id', this.dataset.user);
            fd.append('nonce', mbNonce);
            fetch(ajaxurl, { method:'POST', body:fd }).then(function(r){ return r.json(); }).then(function(j){
                if (j.success && j.data && j.data.redirect) window.location.href = j.data.redirect;
            });
        });
    })();
    </script>
    <script>
    (function(){
        function closeAll() {
            var notif = document.getElementById('mb-pv-notif-dd');
            var prof = document.getElementById('mb-pv-profile-dd');
            if (notif) notif.style.display = 'none';
            if (prof) prof.style.display = 'none';
        }
        window.mbPvToggleNotif = function(ev){
            if (ev) ev.stopPropagation();
            var dd = document.getElementById('mb-pv-notif-dd');
            if (!dd) return;
            var open = dd.style.display === 'block';
            closeAll();
            dd.style.display = open ? 'none' : 'block';
        };
        window.mbPvCloseNotif = function(){
            var dd = document.getElementById('mb-pv-notif-dd');
            if (dd) dd.style.display = 'none';
        };
        window.mbPvToggleProfile = function(ev){
            if (ev) ev.stopPropagation();
            var dd = document.getElementById('mb-pv-profile-dd');
            if (!dd) return;
            var open = dd.style.display === 'block';
            closeAll();
            dd.style.display = open ? 'none' : 'block';
        };
        window.mbPvRequestLogout = function(url){
            var modal = document.getElementById('mb-pv-logout-modal');
            if (!modal) { window.location.href = url; return; }
            modal.dataset.logoutUrl = url;
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        };
        window.mbPvCloseLogout = function(){
            var modal = document.getElementById('mb-pv-logout-modal');
            if (!modal) return;
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        };
        window.mbPvConfirmLogout = function(){
            var modal = document.getElementById('mb-pv-logout-modal');
            if (!modal) return;
            window.location.href = modal.dataset.logoutUrl || '<?php echo esc_js( wp_logout_url( mb_find_page_url( '[mb_login]' ) ) ); ?>';
        };
        document.addEventListener('click', function(e){
            if (!e.target.closest('.nav-icons-wrap') && !e.target.closest('.nav-profile-wrap')) {
                closeAll();
            }
        });
        var modal = document.getElementById('mb-pv-logout-modal');
        if (modal) {
            modal.addEventListener('click', function(e){ if (e.target === this) mbPvCloseLogout(); });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_help() {
    $faqs = [
        'getting-started' => [ 'label' => 'Getting Started', 'items' => [
            [ 'q' => 'What is MentorBe?', 'a' => 'MentorBe is a platform that connects Clients who need work done with skilled Providers who can do it. Clients post tasks, Providers apply, and payment is secured through escrow until the work is approved.' ],
            [ 'q' => 'Who can use MentorBe?', 'a' => 'Anyone 18 years and older can create an account. You can sign up as a Client, a Provider, or both.' ],
            [ 'q' => 'How do I create an account?', 'a' => 'Click "Sign Up" on the landing page, enter your email address and password, then choose your role — Client or Provider. Complete your profile to get started.' ],
            [ 'q' => 'Can I be both a Client and a Provider?', 'a' => 'Yes. You can switch between roles within the same account depending on your needs.' ],
        ] ],
        'for-clients' => [ 'label' => 'For Clients', 'items' => [
            [ 'q' => 'How do I post a task?', 'a' => 'From your dashboard, click "Post a Task." Fill in the task title, description, required skills, budget, and deadline. Submit it for admin review — once approved, it goes live and Providers can apply.' ],
            [ 'q' => 'How long does task approval take?', 'a' => 'Task postings are typically reviewed within 3-5 business days. You will be notified once your task is approved or if any changes are needed.' ],
            [ 'q' => 'How do I choose a Provider?', 'a' => 'Once your task is live, you will receive applications from Providers. You can review their profiles, ratings, and past work, and chat with them before making a decision.' ],
            [ 'q' => 'What happens after I select a Provider?', 'a' => 'You will be prompted to fund escrow with your task budget. Once payment is secured, the Provider is notified and work begins.' ],
            [ 'q' => 'What if I am not satisfied with the work?', 'a' => 'You can request revisions by rejecting the submission and providing feedback. The Provider will be notified and can resubmit. If you cannot reach an agreement, you may escalate to a dispute.' ],
            [ 'q' => 'How do I approve completed work?', 'a' => 'When the Provider submits their work, you will receive a notification to review it. If you are satisfied, click "Approve" and payment will be released automatically.' ],
            [ 'q' => 'What happens if I do not respond to a submission?', 'a' => 'If you do not approve or request revisions within 3-5 days, the task will be automatically approved and payment will be released to the Provider.' ],
            [ 'q' => 'Can I cancel a task?', 'a' => 'You may cancel a task before a Provider is selected at no charge. Once a Provider has been hired and work has begun, cancellation is subject to the dispute resolution process.' ],
        ] ],
        'for-providers' => [ 'label' => 'For Providers', 'items' => [
            [ 'q' => 'How do I find tasks?', 'a' => 'Your home feed shows tasks matched to your skills and tags. You can also browse and filter tasks by category, budget, and deadline.' ],
            [ 'q' => 'How do I apply to a task?', 'a' => 'Click on a task to view its details, then click "Apply." You can include a message to the Client explaining why you are a good fit.' ],
            [ 'q' => 'What happens after I apply?', 'a' => 'Your application will be visible to the Client. They may message you before making a decision. You will be notified if you are selected or if your application is not accepted.' ],
            [ 'q' => 'When do I get paid?', 'a' => 'Payment is released to your MentorBe earnings once the Client approves your submitted work. If the task is auto-approved due to Client inactivity, payment is released after 10 days.' ],
            [ 'q' => 'How do I submit completed work?', 'a' => 'From your active task page, click "Submit Work" and upload your deliverables or proof of completion. The Client will be notified to review your submission.' ],
            [ 'q' => 'What if the Client keeps requesting revisions?', 'a' => 'You are entitled to 3 revision rounds as agreed upon in the task. If you feel revision requests are unreasonable or outside the original scope, you may escalate to a dispute.' ],
            [ 'q' => 'Can I cancel a task I accepted?', 'a' => 'Yes. You can submit a cancellation request through the task page. The Client will be notified and may accept or decline. If unresolved, the matter goes to admin for review.' ],
            [ 'q' => 'How do I withdraw my earnings?', 'a' => 'Go to your Earnings page and click "Withdraw." Enter your preferred payment details. Withdrawals are processed within 3-5 business days.' ],
        ] ],
        'payments-and-escrow' => [ 'label' => 'Payments and Escrow', 'items' => [
            [ 'q' => 'How does escrow work?', 'a' => 'When a Client hires a Provider, the task budget is held in escrow by MentorBe. The funds are only released to the Provider once the Client approves the completed work, protecting both parties.' ],
            [ 'q' => 'What payment methods are accepted?', 'a' => 'MentorBe currently accepts [list payment methods, e.g., GCash, bank transfer, credit/debit card].' ],
            [ 'q' => 'Is there a service fee?', 'a' => 'Yes. MentorBe charges a 5% service fee deducted from the Provider\'s payment upon task completion. Clients pay the full task budget upfront.' ],
            [ 'q' => 'What happens to escrow during a dispute?', 'a' => 'Funds remain held in escrow until the dispute is resolved by a MentorBe administrator.' ],
            [ 'q' => 'Are refunds available?', 'a' => 'Refunds are handled on a case-by-case basis through the dispute resolution process. MentorBe does not guarantee refunds outside of a formal dispute.' ],
        ] ],
        'disputes' => [ 'label' => 'Disputes', 'items' => [
            [ 'q' => 'How do I file a dispute?', 'a' => 'If you and the other party cannot resolve an issue through the discussion hub, click "Submit Dispute" on the task page and describe the issue. MentorBe admin will be notified.' ],
            [ 'q' => 'How long does dispute resolution take?', 'a' => 'MentorBe aims to resolve disputes within 5-7 business days. Complex cases may take longer.' ],
            [ 'q' => 'What can I do while a dispute is open?', 'a' => 'You may continue communicating through the Platform\'s discussion hub. Avoid taking any action outside the Platform while a dispute is open.' ],
            [ 'q' => 'What outcomes are possible in a dispute?', 'a' => 'MentorBe administrators may release full payment to the Provider, issue a full or partial refund to the Client, or split the escrowed amount based on work completed. The administrator\'s decision is final.' ],
            [ 'q' => 'Can I appeal a dispute decision?', 'a' => 'Dispute decisions are final. However, if you believe there was a procedural error, you may contact MentorBe support at support@mentorbe.com to request a review.' ],
        ] ],
        'account-and-profile' => [ 'label' => 'Account and Profile', 'items' => [
            [ 'q' => 'How do I edit my profile?', 'a' => 'Go to your Profile page and click "Edit." You can update your name, bio, skills, profile photo, and contact details.' ],
            [ 'q' => 'How do I change my password?', 'a' => 'Go to Settings > Account Security > Change Password. You will need to enter your current password to confirm the change.' ],
            [ 'q' => 'How do I enable notifications?', 'a' => 'Go to Settings > Notifications and toggle your preferences for email and in-app notifications.' ],
            [ 'q' => 'How do I verify my identity?', 'a' => 'Go to Settings > Identity Verification and follow the instructions to submit your valid government-issued ID. Verification may take 3-5 business days.' ],
            [ 'q' => 'How do I delete my account?', 'a' => 'Go to Settings > Account > Delete Account. Note that deleting your account is permanent. Any active tasks or pending payments must be resolved before deletion.' ],
        ] ],
        'safety-and-trust' => [ 'label' => 'Safety and Trust', 'items' => [
            [ 'q' => 'How does MentorBe keep the platform safe?', 'a' => 'All task postings are reviewed by administrators before going live. Users can report inappropriate content or behavior. Accounts involved in fraud or repeated violations are suspended or permanently banned.' ],
            [ 'q' => 'How do I report a user?', 'a' => 'On any user\'s profile or task page, click the "Report" button and describe the issue. MentorBe will review the report and take appropriate action.' ],
            [ 'q' => 'Are reviews verified?', 'a' => 'Yes. Reviews can only be submitted by users who have completed a task together, ensuring all feedback is based on real interactions.' ],
        ] ],
    ];
    $lu = mb_find_page_url( '[mb_login]' );

    mb_dashboard_assets();
    mb_public_assets();
    ob_start(); ?>
    <div class="mb-public">
        <?php echo mb_public_nav( true, 'Login', $lu ); ?>
        <div class="mb-static-page">
            <div class="mb-help-wrap">
                <aside class="mb-help-sidebar">
                    <div class="mb-help-sidebar-inner">
                        <?php foreach ( $faqs as $id => $sec ): ?>
                            <a href="#mb-faq-<?php echo esc_attr( $id ); ?>" class="mb-help-nav-link" data-sec="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $sec['label'] ); ?></a>
                        <?php endforeach; ?>
                    </div>
                </aside>
                <div class="mb-help-content">
                    <h1>Help Center / FAQ</h1>
                    <p class="mb-updated">Last updated: <?php echo date( 'F j, Y' ); ?></p>
                    <?php foreach ( $faqs as $id => $sec ): ?>
                    <div class="mb-faq-section" id="mb-faq-<?php echo esc_attr( $id ); ?>">
                        <h2><?php echo esc_html( $sec['label'] ); ?></h2>
                        <div class="mb-faq-items">
                        <?php foreach ( $sec['items'] as $i => $f ): $key = $id . '-' . $i; ?>
                            <div class="mb-faq-item" id="mb-faqitem-<?php echo esc_attr( $key ); ?>">
                                <button class="mb-faq-btn" type="button" data-key="<?php echo esc_attr( $key ); ?>">
                                    <span><?php echo esc_html( $f['q'] ); ?></span>
                                    <svg class="mb-faq-chev" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="mb-faq-ans"><div class="mb-faq-ans-inner"><?php echo esc_html( $f['a'] ); ?></div></div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="mb-help-cta">
                        <p class="mb-title">Still Need Help?</p>
                        <p class="mb-desc">If you can't find the answer you're looking for, our support team is happy to help.</p>
                        <a href="mailto:support@mentorbe.io">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php echo mb_business_footer(); ?>
    <script>
    (function(){
        document.querySelectorAll('.mb-faq-btn').forEach(function(btn){
            btn.addEventListener('click',function(){
                var item=document.getElementById('mb-faqitem-'+this.dataset.key);
                item.classList.toggle('open');
            });
        });
        var links = document.querySelectorAll('.mb-help-nav-link');
        links.forEach(function(link){
            link.addEventListener('click',function(e){
                e.preventDefault();
                links.forEach(function(l){l.classList.remove('active');});
                this.classList.add('active');
                document.getElementById('mb-faq-'+this.dataset.sec).scrollIntoView({behavior:'smooth'});
            });
        });
        if (links.length) links[0].classList.add('active');
    })();
    </script>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_mb_terms() {
    $lu = mb_find_page_url( '[mb_login]' );

    mb_dashboard_assets();
    mb_public_assets();
    ob_start(); ?>
    <div class="mb-public">
        <?php echo mb_public_nav( true, 'Login', $lu ); ?>
        <div class="mb-static-page">
            <div class="mb-static-inner">
                <div class="mb-legal-sections-wrap">
                    <h1>Terms of Service</h1><p class="mb-updated">Last updated: <?php echo date( 'F j, Y' ); ?></p>
                    <div class="mb-legal-section"><h2>1. Acceptance of Terms</h2><p>By accessing or using MentorBe ("Platform"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, do not use the Platform. These Terms apply to all users including Clients, Providers, and visitors.</p></div>
                    <div class="mb-legal-section"><h2>2. Definitions</h2><ul><li>"Platform" refers to the MentorBe website, application, and all related services.</li><li>"Client" refers to a user who posts tasks and hires Providers.</li><li>"Provider" refers to a user who applies to and completes tasks posted by Clients.</li><li>"Task" refers to a job or service posted by a Client on the Platform.</li><li>"Escrow" refers to the payment held by MentorBe pending task completion and approval.</li></ul></div>
                    <div class="mb-legal-section"><h2>3. Eligibility</h2><p></p></div>
                    <div class="mb-legal-section"><h2>4. Account Registration</h2><p>When registering an account, you agree to:</p><ul><li>Provide accurate, current, and complete information.</li><li>Maintain the security of your password and accept responsibility for all activity under your account.</li><li>Notify MentorBe immediately of any unauthorized use of your account.</li><li>Not create more than one account or transfer your account to another person.</li></ul></div>
                    <div class="mb-legal-section"><h2>5. Platform Use</h2><h3>5.1 For Clients</h3><ul><li>Clients may post tasks describing the work to be done, budget, and required skills.</li><li>All task postings are subject to review and approval by MentorBe administrators.</li><li>Clients must fund escrow before a Provider begins work.</li><li>Clients must review and either approve or request revisions within 3-5 days of task submission. Failure to respond within this period may result in automatic approval and payment release.</li></ul><h3>5.2 For Providers</h3><ul><li>Providers may browse and apply to tasks that match their skills.</li><li>Providers must complete tasks to the standard described in the task posting.</li><li>Providers may submit refund or cancellation requests through the Platform.</li><li>MentorBe deducts a service fee from each completed task payment. The current fee rate is 5%.</li></ul></div>
                    <div class="mb-legal-section"><h2>6. Payments and Escrow</h2><ul><li>Clients must deposit payment into escrow when hiring a Provider. Funds are held securely by MentorBe until the task is approved.</li><li>Upon Client approval, funds are released to the Provider minus MentorBe's service fee.</li><li>MentorBe is not responsible for delays caused by third-party payment processors.</li><li>All payments are processed in [currency]. Currency conversion fees, if any, are the responsibility of the user.</li><li>In the event of a dispute, funds remain in escrow until a resolution is reached.</li></ul></div>
                    <div class="mb-legal-section"><h2>7. Dispute Resolution</h2><p>If a Client and Provider cannot resolve a disagreement through the Platform's built-in discussion hub, either party may escalate to a formal dispute. MentorBe administrators will review the dispute and may:</p><ul><li>Release full payment to the Provider.</li><li>Issue a full or partial refund to the Client.</li><li>Split the escrowed amount based on work completed.</li></ul><p>MentorBe's decision on dispute outcomes is final. MentorBe reserves the right to suspend or terminate accounts involved in repeated disputes.</p></div>
                    <div class="mb-legal-section"><h2>8. Prohibited Conduct</h2><p>You agree not to:</p><ul><li>Circumvent the Platform by transacting with other users outside of MentorBe.</li><li>Post false, misleading, or fraudulent tasks or profiles.</li><li>Harass, abuse, or threaten other users.</li><li>Use the Platform for any illegal purpose.</li><li>Upload malicious software or content.</li><li>Attempt to gain unauthorized access to any part of the Platform.</li><li>Create fake reviews or manipulate the review system.</li></ul></div>
                    <div class="mb-legal-section"><h2>9. Reviews and Feedback</h2><p>After a task is completed, both Clients and Providers may leave a review. Reviews must be honest, relevant, and respectful. MentorBe reserves the right to remove reviews that violate these Terms or that are determined to be fraudulent.</p></div>
                    <div class="mb-legal-section"><h2>10. Intellectual Property</h2><p>Unless otherwise agreed upon between a Client and Provider, all work product delivered through the Platform is owned by the Client upon full payment. MentorBe does not claim ownership over any work product exchanged between users.</p></div>
                    <div class="mb-legal-section"><h2>11. Privacy</h2><p>Your use of the Platform is also governed by our Privacy Policy, which is incorporated into these Terms by reference. By using MentorBe, you consent to the collection and use of your data as described in the Privacy Policy.</p></div>
                    <div class="mb-legal-section"><h2>12. Termination</h2><p>MentorBe reserves the right to suspend or permanently terminate any account that violates these Terms, with or without notice. Upon termination, any escrowed funds will be handled in accordance with the dispute resolution process. You may also delete your account at any time through your account settings.</p></div>
                    <div class="mb-legal-section"><h2>13. Limitation of Liability</h2><p>MentorBe acts solely as an intermediary platform connecting Clients and Providers. MentorBe is not responsible for the quality, safety, legality, or delivery of any task or service. To the fullest extent permitted by law, MentorBe shall not be liable for any indirect, incidental, or consequential damages arising from your use of the Platform.</p></div>
                    <div class="mb-legal-section"><h2>14. Changes to These Terms</h2><p>MentorBe reserves the right to update these Terms at any time. We will notify users of significant changes via email or in-app notification. Continued use of the Platform after changes are posted constitutes your acceptance of the revised Terms.</p></div>
                    <div class="mb-legal-section"><h2>15. Governing Law</h2><p>These Terms shall be governed by and construed in accordance with the laws of the Republic of the Philippines. Any disputes arising from these Terms shall be submitted to the appropriate courts of the Philippines.</p></div>
                    <div class="mb-legal-section"><h2>16. Contact Us</h2><ul><li>Email: <a href="mailto:support@mentorbe.com">support@mentorbe.com</a></li><li>Website: <a href="http://www.mentorbe.com" target="_blank" rel="noopener noreferrer">www.mentorbe.com</a></li><li>Address: Company Address</li></ul></div>
                </div>
                <div class="mb-privacy-block" id="mb-privacy">
                    <h1>Privacy Policy</h1><p class="mb-updated">Last updated: <?php echo date( 'F j, Y' ); ?></p>
                    <div class="mb-legal-section"><h2>1. Introduction</h2><p>MentorBe (&quot;we,&quot; &quot;our,&quot; or &quot;us&quot;) is committed to protecting your personal information. This Privacy Policy explains how we collect, use, store, and share your data when you use the MentorBe platform (&quot;Platform&quot;). By using MentorBe, you agree to the practices described in this policy.</p></div>
                    <div class="mb-legal-section"><h2>2. Information We Collect</h2><h3>2.1 Information You Provide</h3><ul><li>Full name, email address, and password upon registration.</li><li>Profile information including bio, skills, and profile photo.</li><li>Payment information such as GCash number or bank details.</li><li>Task postings, applications, messages, and submitted work.</li><li>Reviews and feedback submitted after task completion.</li><li>Identity verification documents, if required.</li></ul><h3>2.2 Information We Collect Automatically</h3><ul><li>Device information including IP address, browser type, and operating system.</li><li>Usage data such as pages visited, features used, and time spent on the Platform.</li><li>Log data including access times, error reports, and referring URLs.</li><li>Cookies and similar tracking technologies.</li></ul><h3>2.3 Information from Third Parties</h3><ul><li>Payment processors may share transaction confirmation data with us.</li><li>If you sign in using a third-party service (e.g., Google), we may receive basic profile information from that service.</li></ul></div>
                    <div class="mb-legal-section"><h2>3. How We Use Your Information</h2><p>We use the information we collect to:</p><ul><li>Create and manage your account.</li><li>Facilitate task posting, matching, and completion.</li><li>Process payments and manage escrow.</li><li>Send notifications related to your tasks, applications, and account activity.</li><li>Resolve disputes between Clients and Providers.</li><li>Moderate reviews and task postings.</li><li>Improve the Platform's features and user experience.</li><li>Comply with legal obligations.</li><li>Detect and prevent fraud or misuse of the Platform.</li></ul></div>
                    <div class="mb-legal-section"><h2>4. How We Share Your Information</h2><h3>4.1 With Other Users</h3><ul><li>Your public profile (name, bio, skills, reviews) is visible to other users.</li><li>Your messages within a task thread are visible to the other party in that task.</li><li>Task postings made by Clients are visible to all Providers on the Platform.</li></ul><h3>4.2 With Service Providers</h3><p>We may share your data with trusted third-party service providers who assist us in operating the Platform, including payment processors, cloud storage providers, and email services. These providers are bound by confidentiality agreements and may not use your data for their own purposes.</p><h3>4.3 For Legal Reasons</h3><p>We may disclose your information if required by law, court order, or government authority, or if we believe disclosure is necessary to protect the rights, safety, or property of MentorBe or its users.</p><h3>4.4 Business Transfers</h3><p>In the event of a merger, acquisition, or sale of assets, your information may be transferred as part of that transaction. We will notify you before your data is transferred and becomes subject to a different privacy policy.</p></div>
                    <div class="mb-legal-section"><h2>5. Cookies and Tracking</h2><p>We use cookies and similar technologies to keep you logged in, remember your preferences, and analyze Platform usage. You may disable cookies through your browser settings, but some features of the Platform may not function properly as a result.</p></div>
                    <div class="mb-legal-section"><h2>6. Data Retention</h2><p>We retain your personal data for as long as your account is active or as needed to provide our services. If you delete your account, we will delete or anonymize your data within [X] days, except where retention is required by law or for legitimate business purposes such as dispute records.</p></div>
                    <div class="mb-legal-section"><h2>7. Data Security</h2><p>We implement industry-standard security measures to protect your personal information, including encryption, secure servers, and access controls. However, no method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.</p></div>
                    <div class="mb-legal-section"><h2>8. Your Rights</h2><p>Depending on your location and applicable law, you may have the right to:</p><ul><li>Access the personal data we hold about you.</li><li>Request correction of inaccurate or incomplete data.</li><li>Request deletion of your personal data.</li><li>Object to or restrict how we process your data.</li><li>Request a copy of your data in a portable format.</li><li>Withdraw consent at any time where processing is based on consent.</li></ul><p>To exercise any of these rights, contact us at <a href="mailto:support@mentorbe.com">support@mentorbe.com</a>.</p></div>
                    <div class="mb-legal-section"><h2>9. Children's Privacy</h2><p>MentorBe is not intended for users under the age of 18. We do not knowingly collect personal information from minors. If we become aware that a minor has created an account, we will terminate the account and delete the associated data promptly.</p></div>
                    <div class="mb-legal-section"><h2>10. Third-Party Links</h2><p>The Platform may contain links to third-party websites or services. We are not responsible for the privacy practices of those third parties and encourage you to review their privacy policies before providing any personal information.</p></div>
                    <div class="mb-legal-section"><h2>11. Changes to This Policy</h2><p>We may update this Privacy Policy from time to time. We will notify you of significant changes via email or in-app notification. Your continued use of the Platform after changes are posted constitutes your acceptance of the updated policy.</p></div>
                    <div class="mb-legal-section"><h2>12. Governing Law</h2><p>This Privacy Policy is governed by the laws of the Republic of the Philippines, including the Data Privacy Act of 2012 (Republic Act No. 10173) and its implementing rules and regulations.</p></div>
                    <div class="mb-legal-section"><h2>13. Contact Us</h2><p>If you have questions, concerns, or requests regarding this Privacy Policy, please contact us at:</p><ul><li>Email: <a href="mailto:support@mentorbe.com">support@mentorbe.com</a></li><li>Website: <a href="http://www.mentorbe.com" target="_blank" rel="noopener noreferrer">www.mentorbe.com</a></li><li>Address: Company Address</li></ul></div>
                </div>
            </div>
        </div>
    </div>
    <?php echo mb_business_footer(); ?>
    <?php
    return ob_get_clean();
}

function mb_admin_export_users_csv() {
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT p.rand_id, u.display_name, u.user_email, p.user_role, p.verification_status, p.status, p.created_at
         FROM {$wpdb->prefix}mb_user_profiles p
         INNER JOIN {$wpdb->users} u ON u.ID = p.user_id
         ORDER BY p.created_at DESC"
    );
    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=mentorbe-users-' . date( 'Y-m-d' ) . '.csv' );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, [ 'User ID', 'Name', 'Email', 'Role', 'KYC Status', 'Account Status', 'Joined' ] );
    foreach ( $rows as $r ) {
        fputcsv( $out, [ $r->rand_id, $r->display_name, $r->user_email, ucfirst( $r->user_role ), ucfirst( $r->verification_status ), ucfirst( $r->status ), $r->created_at ] );
    }
    fclose( $out );
    exit;
}

function mb_admin_assets() {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    :root{
        --ac-purple:#7c3aed; --ac-purple-dk:#2e1065; --ac-purple-pale:#efe9fb; --ac-purple-bg:#f6f4fb;
        --ac-muted:#8b80a8; --ac-border:#ececf5; --ac-green:#16a34a; --ac-red:#dc2626; --ac-orange:#d97706;
        --ac-ink:#1c1130;
    }
    .ac-shell,.ac-shell *{ font-family:'Inter',sans-serif; box-sizing:border-box; }
    .ac-shell{ display:flex; align-items:stretch; min-height:100vh; background:var(--ac-purple-bg); margin:0; width:100%; }
    .ac-sidebar{ width:280px; flex-shrink:0; background:#fff; border-right:1px solid var(--ac-border); display:flex; flex-direction:column; padding:28px 20px; }
    .ac-logo{ display:flex; align-items:center; gap:10px; font-weight:800; font-size:19px; color:var(--ac-ink); margin-bottom:32px; padding:0 8px; }
    .ac-logo svg{ color:var(--ac-purple); flex-shrink:0; }
    .ac-nav{ display:flex; flex-direction:column; gap:4px; flex:1; }
    .ac-nav a{ display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:10px; color:#4b4560; font-weight:600; font-size:14.5px; text-decoration:none; transition:background .15s,color .15s; position:relative; }
    .ac-nav a:hover{ background:var(--ac-purple-pale); }
    .ac-nav a.active{ background:var(--ac-purple-pale); color:var(--ac-purple); }
    .ac-nav a svg{ flex-shrink:0; width:19px; height:19px; }
    .ac-nav-badge{ margin-left:auto; background:#fee2e2; color:var(--ac-red); font-size:12px; font-weight:700; padding:2px 9px; border-radius:9999px; }
    .ac-sidebar-footer{ border-top:1px solid var(--ac-border); padding-top:16px; margin-top:16px; }
    .ac-sidebar-footer a{ display:flex; align-items:center; gap:12px; padding:10px 14px; color:#6b6480; font-weight:600; font-size:14.5px; text-decoration:none; border-radius:10px; }
    .ac-sidebar-footer a:hover{ background:var(--ac-purple-pale); color:var(--ac-purple); }
    .ac-main{ flex:1; min-width:0; display:flex; flex-direction:column; }
    .ac-topbar{ height:76px; flex-shrink:0; background:#fff; border-bottom:1px solid var(--ac-border); display:flex; align-items:center; gap:20px; padding:0 24px; }
    .ac-search{ flex:1; max-width:420px; position:relative; }
    .ac-search input{ width:100%; background:var(--ac-purple-bg); border:1px solid var(--ac-border); border-radius:9999px; padding:11px 18px 11px 42px; font-size:14px; color:var(--ac-ink); outline:none; font-family:'Inter',sans-serif; }
    .ac-search input::placeholder{ color:#b3a9d6; }
    .ac-search input:focus{ box-shadow:0 0 0 2px rgba(124,58,237,.25); }
    .ac-search svg{ position:absolute; left:15px; top:50%; transform:translateY(-50%); color:var(--ac-purple); }
    .ac-topbar-right{ margin-left:auto; display:flex; align-items:center; gap:18px; }
    .ac-status{ display:flex; align-items:center; gap:7px; font-weight:700; font-size:13px; color:var(--ac-green); }
    .ac-status .dot{ width:8px; height:8px; border-radius:9999px; background:var(--ac-green); }
    .ac-divider{ width:1px; height:24px; background:var(--ac-border); }
    .ac-avatar{ width:38px; height:38px; border-radius:9999px; background:linear-gradient(135deg,#9333ea,#6d28d9); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; }
    .ac-content{ padding:30px 28px 52px; flex:1; }
    .ac-content h1{ font-weight:800; font-size:26px; color:var(--ac-ink); margin:0 0 6px; }
    .ac-content-sub{ color:var(--ac-muted); font-weight:500; font-size:14.5px; margin:0 0 28px; }
    .ac-content-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .ac-badge-pill{ display:inline-block; margin-left:10px; vertical-align:middle; background:var(--ac-red); color:#fff; font-size:12px; font-weight:700; padding:3px 12px; border-radius:9999px; }
    .ac-badge-pill.orange{ background:var(--ac-orange); }

    /* Buttons */
    .ac-btn{ display:inline-flex; align-items:center; gap:8px; padding:11px 20px; border-radius:10px; font-weight:700; font-size:14px; border:1px solid var(--ac-border); background:#fff; color:var(--ac-ink); cursor:pointer; text-decoration:none; transition:all .15s; }
    .ac-btn:hover{ border-color:var(--ac-purple); color:var(--ac-purple); }
    .ac-btn-primary{ background:var(--ac-purple); border-color:var(--ac-purple); color:#fff; }
    .ac-btn-primary:hover{ opacity:.9; color:#fff; }
    .ac-btn-sm{ padding:7px 14px; font-size:12.5px; border-radius:8px; }
    .ac-btn-danger{ background:var(--ac-red); border-color:var(--ac-red); color:#fff; }
    .ac-btn-danger:hover{ opacity:.9; color:#fff; }
    .ac-btn-ghost{ background:transparent; border:none; color:var(--ac-purple); }
    .ac-btn-disabled{ background:#9ca3af; border-color:#9ca3af; color:#fff; cursor:not-allowed; opacity:.7; }

    /* Stat cards (Overview) */
    .ac-stats-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-bottom:22px; }
    .ac-stat-card{ background:#fff; border:1px solid var(--ac-border); border-radius:16px; padding:22px; position:relative; overflow:hidden; }
    .ac-stat-card.danger{ background:#fef2f2; border-color:#fecaca; }
    .ac-stat-label{ font-size:12px; font-weight:700; color:#8a8298; text-transform:uppercase; letter-spacing:.04em; margin:0 0 10px; }
    .ac-stat-card.danger .ac-stat-label{ color:var(--ac-red); }
    .ac-stat-value{ font-size:28px; font-weight:800; color:var(--ac-ink); margin:0 0 8px; }
    .ac-stat-value.green{ color:var(--ac-green); }
    .ac-stat-value.red{ color:var(--ac-red); }
    .ac-stat-foot{ font-size:13px; font-weight:600; color:var(--ac-purple); display:flex; align-items:center; gap:5px; }
    .ac-stat-foot.green{ color:var(--ac-green); }
    .ac-stat-foot.muted{ color:#9c94b3; font-weight:500; }
    .ac-stat-foot.red{ color:var(--ac-red); }
    .ac-glow{ position:absolute; top:-20px; right:-20px; width:90px; height:90px; border-radius:9999px; background:radial-gradient(circle,rgba(34,197,94,.18),transparent 70%); }

    .ac-card{ background:#fff; border:1px solid var(--ac-border); border-radius:16px; padding:24px; margin-bottom:20px; }
    .ac-card-title{ display:flex; align-items:center; gap:10px; font-weight:700; font-size:15px; color:var(--ac-ink); margin:0 0 20px; }
    .ac-card-title svg{ color:var(--ac-purple); }
    .ac-chart-placeholder{ height:230px; display:flex; align-items:flex-end; justify-content:space-between; border-bottom:1px solid var(--ac-border); padding-bottom:14px; }
    .ac-chart-days{ display:flex; justify-content:space-between; margin-top:14px; }
    .ac-chart-days span{ color:#b3a9d6; font-weight:600; font-size:13px; }

    /* Table (Users / Financials) */
    .ac-table-wrap{ overflow-x:auto; }
    .ac-table{ width:100%; border-collapse:collapse; font-size:13.5px; }
    .ac-table th{ text-align:left; color:var(--ac-purple); font-weight:700; font-size:12.5px; padding:12px 14px; border-bottom:2px solid var(--ac-border); white-space:nowrap; }
    .ac-table td{ padding:14px; border-bottom:1px solid var(--ac-border); color:#463f5c; vertical-align:middle; }
    .ac-table tr:last-child td{ border-bottom:none; }
    .ac-user-cell{ display:flex; align-items:center; gap:12px; }
    .ac-user-avatar{ width:38px; height:38px; border-radius:9999px; background:var(--ac-purple-pale); color:var(--ac-purple); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; flex-shrink:0; }
    .ac-user-name{ font-weight:700; color:var(--ac-ink); }
    .ac-user-email{ font-size:12.5px; color:#9c94b3; }
    .ac-role-pill{ display:inline-block; background:#f1f0f6; color:#6b6480; font-weight:700; font-size:12.5px; padding:5px 14px; border-radius:8px; }
    .ac-role-pill.provider{ background:var(--ac-purple-pale); color:var(--ac-purple); }
    .ac-kyc{ display:inline-flex; align-items:center; gap:6px; font-weight:700; font-size:13.5px; }
    .ac-kyc.verified{ color:var(--ac-green); }
    .ac-kyc.pending{ color:var(--ac-orange); }
    .ac-icon-btn{ background:none; border:none; cursor:pointer; color:#a79fc2; padding:4px; border-radius:6px; }
    .ac-icon-btn:hover{ color:var(--ac-purple); background:var(--ac-purple-pale); }
    .ac-icon-btn.danger:hover{ color:var(--ac-red); background:#fee2e2; }

    /* Disputes */
    .ac-disputes-grid{ display:grid; grid-template-columns:1fr; gap:20px; }
    @media(min-width:960px){ .ac-disputes-grid{ grid-template-columns:1fr 1fr; } }
    .ac-dispute-card{ border:1px solid var(--ac-border); border-radius:16px; overflow:hidden; background:#fff; }
    .ac-dispute-card.urgent{ border-color:#fecaca; box-shadow:0 0 0 1px #fecaca; }
    .ac-dispute-head{ display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:#fef2f2; gap:10px; flex-wrap:wrap; }
    .ac-dispute-card:not(.urgent) .ac-dispute-head{ background:#faf9fc; }
    .ac-ticket-id{ display:flex; align-items:center; gap:7px; font-weight:700; font-size:13.5px; color:var(--ac-red); }
    .ac-dispute-card:not(.urgent) .ac-ticket-id{ color:#6b6480; }
    .ac-frozen-pill{ display:flex; align-items:center; gap:6px; background:#fff; border:1px solid var(--ac-border); border-radius:9999px; padding:6px 14px; font-weight:700; font-size:13px; color:var(--ac-ink); }
    .ac-dispute-body{ padding:20px; }
    .ac-dispute-body h3{ margin:0 0 8px; font-size:16.5px; color:var(--ac-ink); }
    .ac-dispute-body > p.ac-desc{ color:#6b6480; font-size:14px; margin:0 0 18px; line-height:1.55; }
    .ac-party-row{ display:flex; border:1px solid var(--ac-border); border-radius:12px; margin-bottom:16px; overflow:hidden; }
    .ac-party{ flex:1; padding:12px 16px; text-align:center; }
    .ac-party:first-child{ border-right:1px solid var(--ac-border); }
    .ac-party-label{ font-size:11px; font-weight:700; color:#9c94b3; text-transform:uppercase; letter-spacing:.03em; margin:0 0 4px; }
    .ac-party-name{ font-weight:700; color:var(--ac-purple); font-size:15px; }
    .ac-chat-box{ background:var(--ac-purple-bg); border-radius:12px; padding:14px 16px; margin-bottom:18px; font-size:13.5px; color:#463f5c; line-height:1.6; }
    .ac-chat-box p{ margin:0 0 8px; }
    .ac-chat-box p:last-child{ margin-bottom:0; }
    .ac-note-box{ background:var(--ac-purple-bg); border-radius:12px; padding:16px; font-size:13.5px; color:#463f5c; line-height:1.6; margin-bottom:18px; }
    .ac-dispute-actions{ display:flex; gap:10px; padding:0 20px 20px; }
    .ac-dispute-actions .ac-btn{ flex:1; justify-content:center; }
    .ac-dispute-actions.single{ padding:0; }
    .ac-dispute-actions.single .ac-btn{ border-radius:0; padding:16px; }

    /* Task moderation */
    .ac-flag-card{ border:1px solid var(--ac-border); border-left:4px solid var(--ac-orange); border-radius:14px; background:#fff; padding:22px 24px; margin-bottom:20px; display:flex; gap:20px; flex-wrap:wrap; }
    .ac-flag-card.community{ border-left-color:var(--ac-purple); }
    .ac-flag-main{ flex:1; min-width:260px; }
    .ac-flag-tag{ display:inline-flex; align-items:center; gap:6px; background:#fef3c7; color:var(--ac-orange); font-weight:700; font-size:12px; padding:4px 12px; border-radius:9999px; margin-right:10px; }
    .ac-flag-card.community .ac-flag-tag{ background:var(--ac-purple-pale); color:var(--ac-purple); }
    .ac-flag-time{ font-size:12.5px; color:#9c94b3; font-weight:500; }
    .ac-flag-title{ font-weight:700; font-size:17px; color:var(--ac-ink); margin:12px 0 12px; }
    .ac-flag-quote{ background:var(--ac-purple-bg); border-radius:10px; padding:14px 16px; font-size:13.5px; color:#463f5c; line-height:1.65; margin-bottom:14px; font-style:italic; }
    .ac-flag-quote mark{ background:#fde68a; color:#7c2d12; font-style:normal; font-weight:700; padding:1px 3px; border-radius:3px; }
    .ac-flag-meta{ display:flex; align-items:center; gap:10px; font-size:13px; color:#6b6480; }
    .ac-flag-meta a{ color:var(--ac-purple); font-weight:700; text-decoration:none; }
    .ac-flag-reason{ font-size:13px; color:var(--ac-purple); font-weight:600; }
    .ac-flag-actions{ display:flex; flex-direction:column; gap:8px; min-width:170px; }
    .ac-flag-actions .ac-btn{ justify-content:center; width:100%; }

    /* Financials extras */
    .ac-select{ padding:9px 16px; border-radius:10px; border:1px solid var(--ac-border); background:#fff; font-weight:600; font-size:13.5px; color:var(--ac-ink); font-family:'Inter',sans-serif; }
    .ac-flow-pill{ display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; padding:4px 10px; border-radius:9999px; }
    .ac-flow-pill.blue{ background:#e0edff; color:#1d4ed8; }
    .ac-flow-pill.gray{ background:#f1f0f6; color:#6b6480; }
    .ac-flow-pill.green{ background:#dcfce7; color:var(--ac-green); }
    .ac-flow-pill.purple{ background:var(--ac-purple-pale); color:var(--ac-purple); }
    .ac-webhook-ok{ display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:var(--ac-green); }
    .ac-webhook-ok .dot{ width:6px; height:6px; border-radius:9999px; background:var(--ac-green); }
    .ac-webhook-retry{ display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:var(--ac-orange); background:#fef3c7; padding:4px 10px; border-radius:8px; }
    .ac-fee-row td{ color:#9c94b3; font-size:13px; }
    .ac-link{ color:var(--ac-purple); font-weight:700; font-size:13px; text-decoration:none; }
    .ac-link:hover{ text-decoration:underline; }

    @media(max-width:960px){
        .ac-shell{ flex-direction:column; }
        .ac-sidebar{ width:100%; flex-direction:row; flex-wrap:wrap; align-items:center; padding:16px 20px; }
        .ac-logo{ margin-bottom:0; margin-right:auto; }
        .ac-nav{ flex-direction:row; flex-wrap:wrap; }
        .ac-sidebar-footer{ border-top:none; margin-top:0; padding-top:0; }
        .ac-content{ padding:24px 20px 40px; }
    }
    </style>
    <script>
    var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
    var mbNonce = '<?php echo esc_js( wp_create_nonce( 'mb_nonce' ) ); ?>';
    document.addEventListener('DOMContentLoaded', function () {
        var search = document.getElementById('ac-user-search');
        if (search) {
            search.addEventListener('input', function () {
                var q = this.value.toLowerCase().trim();
                document.querySelectorAll('#ac-users-table tbody tr').forEach(function (row) {
                    row.style.display = !q || row.dataset.search.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        }
        document.querySelectorAll('.ac-verify-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var userId = this.dataset.user;
                var status = this.dataset.status;
                var label = status === 'verified' ? 'verify' : 'reject';
                if (!confirm('Are you sure you want to ' + label + ' this user KYC?')) return;
                var row = this.closest('tr');
                var buttons = row ? row.querySelectorAll('.ac-verify-btn') : [this];
                buttons.forEach(function (b) { b.disabled = true; });
                var fd = new FormData();
                fd.append('action', 'mb_admin_update_verification');
                fd.append('user_id', userId);
                fd.append('status', status);
                fd.append('nonce', mbNonce);
                fetch(ajaxurl, { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (j) {
                    if (j && j.success) {
                        location.reload();
                    } else {
                        alert((j && j.data && j.data.message) ? j.data.message : 'Unable to update verification.');
                        buttons.forEach(function (b) { b.disabled = false; });
                    }
                }).catch(function () {
                    alert('Unable to update verification right now.');
                    buttons.forEach(function (b) { b.disabled = false; });
                });
            });
        });
    });
    </script>
    <?php
}

function bntm_shortcode_mb_admin() {
    if ( ! is_user_logged_in() ) return '<div class="bntm-notice">Please log in.</div>';
    $profile = mb_get_profile( get_current_user_id() );
    $is_admin_user = current_user_can( 'manage_options' ) || is_super_admin();
    if ( ! $is_admin_user && ( ! $profile || $profile->user_role !== 'admin' ) ) return '<div class="bntm-notice">Access restricted to administrators.</div>';

    global $wpdb;

    $tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';
    if ( ! in_array( $tab, [ 'overview', 'users', 'disputes', 'tasks', 'financials' ], true ) ) $tab = 'overview';

    // Real-data CSV export for the Users tab.
    if ( $tab === 'users' && isset( $_GET['export'] ) && $_GET['export'] === 'csv' ) {
        mb_admin_export_users_csv();
    }

    $total_users = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_user_profiles" );
    $providers   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_user_profiles WHERE user_role='provider'" );
    $clients     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_user_profiles WHERE user_role='client'" );
    $live_tasks  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE status='live'" );
    $escrow_liabilities = (float) $wpdb->get_var( "SELECT COALESCE(SUM(budget),0) FROM {$wpdb->prefix}mb_tasks WHERE status='live'" );
    $platform_revenue   = $escrow_liabilities * 0.05;
    $completed_budget_total = (float) $wpdb->get_var( "SELECT COALESCE(SUM(budget),0) FROM {$wpdb->prefix}mb_tasks WHERE status='completed'" );
    $platform_fee_rate      = 0.05;
    $net_platform_revenue   = $completed_budget_total * $platform_fee_rate;
    $open_disputes      = 0;

    $users = $wpdb->get_results(
        "SELECT p.id, p.rand_id, p.user_id, p.user_role, p.verification_status, p.status, u.display_name, u.user_email
         FROM {$wpdb->prefix}mb_user_profiles p
         INNER JOIN {$wpdb->users} u ON u.ID = p.user_id
         ORDER BY p.created_at DESC LIMIT 100"
    );

    mb_admin_assets();
    ob_start(); ?>
    <div class="ac-shell">
        <aside class="ac-sidebar">
            <div class="ac-logo">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l8 3v6c0 5-3.4 8.9-8 11-4.6-2.1-8-6-8-11V5l8-3z"/><path d="M9.5 12.2l1.8 1.8 3.2-3.6" stroke="#fff" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                AdminCenter
            </div>
            <nav class="ac-nav">
                <a href="?tab=overview" class="<?php echo $tab === 'overview' ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    System Overview
                </a>
                <a href="?tab=users" class="<?php echo $tab === 'users' ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m5-4.13a4 4 0 100-8 4 4 0 000 8zm6 4c1.1.3 2 1 2.7 2M6.3 12c-1.1.3-2 1-2.7 2"/></svg>
                    User Management
                </a>
                <a href="?tab=disputes" class="<?php echo $tab === 'disputes' ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.5 3.5l6 6L9 21l-6-1 1-6 10.5-10.5z"/><path stroke-linecap="round" d="M13 5l6 6"/></svg>
                    Disputes
                    <?php if ( $open_disputes > 0 ): ?><span class="ac-nav-badge"><?php echo (int) $open_disputes; ?></span><?php endif; ?>
                </a>
                <a href="?tab=tasks" class="<?php echo $tab === 'tasks' ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="7" height="16" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/></svg>
                    Task Moderation
                </a>
                <a href="?tab=financials" class="<?php echo $tab === 'financials' ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M4 21V9l8-5 8 5v12M9 21v-6h6v6"/></svg>
                    Financials &amp; Ledger
                </a>
            </nav>
            <div class="ac-sidebar-footer">
                <a href="<?php echo esc_url( wp_logout_url( mb_find_page_url( '[mb_login]' ) ) ); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="19" height="19"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    Secure Logout
                </a>
            </div>
        </aside>

        <div class="ac-main">
            <div class="ac-topbar">
                <div class="ac-search">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="ac-user-search" placeholder="Search UUIDs, Transactions, or Users...">
                </div>
                <div class="ac-topbar-right">
                    <div class="ac-status"><span class="dot"></span> SYSTEM OPERATIONAL</div>
                    <div class="ac-divider"></div>
                    <div class="ac-avatar">ADM</div>
                </div>
            </div>

            <div class="ac-content">
            <?php if ( $tab === 'overview' ): ?>

                <h1>System Overview</h1>
                <p class="ac-content-sub">Real-time metrics for Escrow liabilities and platform health.</p>

                <div class="ac-stats-grid">
                    <div class="ac-stat-card">
                        <p class="ac-stat-label">Total Escrow Liabilities</p>
                        <p class="ac-stat-value"><?php echo mb_price( $escrow_liabilities ); ?></p>
                        <p class="ac-stat-foot"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 118 0v4"/></svg> Live task budgets</p>
                    </div>
                    <div class="ac-stat-card">
                        <div class="ac-glow"></div>
                        <p class="ac-stat-label">Platform Revenue (5%)</p>
                        <p class="ac-stat-value green"><?php echo mb_price( $platform_revenue ); ?></p>
                        <p class="ac-stat-foot green"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M23 6l-9.5 9.5-5-5L1 18"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 6h6v6"/></svg> Auto-derived from live tasks</p>
                    </div>
                    <div class="ac-stat-card">
                        <p class="ac-stat-label">Active Users</p>
                        <p class="ac-stat-value"><?php echo number_format( $total_users ); ?></p>
                        <p class="ac-stat-foot muted"><?php echo number_format( $clients ); ?> Clients / <?php echo number_format( $providers ); ?> Mentors</p>
                    </div>
                    <div class="ac-stat-card danger">
                        <p class="ac-stat-label">Open Disputes</p>
                        <p class="ac-stat-value red"><?php echo number_format( $open_disputes ); ?> <span style="font-size:15px;font-weight:700;">Tickets</span></p>
                        <p class="ac-stat-foot red"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> No seeded sample data</p>
                    </div>
                </div>

                <div class="ac-card">
                    <p class="ac-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l4-4 3 3 5-6"/></svg> 7-Day Escrow Flow</p>
                    <div class="ac-chart-placeholder"></div>
                    <div class="ac-chart-days">
                        <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Today</span>
                    </div>
                </div>

            <?php elseif ( $tab === 'users' ): ?>

                <div class="ac-content-head">
                    <div>
                        <h1>User Management (KYC)</h1>
                <?php echo mb_render_review_modal_shell(); ?>
                        <p class="ac-content-sub">Review Identity Documents and manage platform access.</p>
                    </div>
                    <a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'users', 'export' => 'csv' ] ) ); ?>" class="ac-btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        Export CSV
                    </a>
                </div>

                <div class="ac-card" style="padding:0;">
                    <div class="ac-table-wrap">
                        <table class="ac-table" id="ac-users-table">
                            <thead><tr><th>User</th><th>Role</th><th>KYC Status (PhilSys)</th><th>Financial DB</th><th style="text-align:right;">Actions</th></tr></thead>
                            <tbody>
                            <?php if ( empty( $users ) ): ?>
                                <tr><td colspan="5" style="text-align:center;color:#9c94b3;padding:32px;">No registered users yet.</td></tr>
                            <?php else: foreach ( $users as $u ):
                                $initials = '';
                                foreach ( array_slice( explode( ' ', trim( $u->display_name ) ), 0, 2 ) as $w ) { $initials .= mb_strtoupper( mb_substr( $w, 0, 1 ) ); }
                                $verified = $u->verification_status === 'verified';
                                $edit_link = get_edit_user_link( $u->user_id );
                            ?>
                                <tr data-search="<?php echo esc_attr( strtolower( $u->display_name . ' ' . $u->user_email ) ); ?>">
                                    <td>
                                        <div class="ac-user-cell">
                                            <div class="ac-user-avatar"><?php echo esc_html( $initials ?: '?' ); ?></div>
                                            <div><div class="ac-user-name"><?php echo esc_html( $u->display_name ); ?></div><div class="ac-user-email"><?php echo esc_html( $u->user_email ); ?></div></div>
                                        </div>
                                    </td>
                                    <td><span class="ac-role-pill <?php echo $u->user_role === 'provider' ? 'provider' : ''; ?>"><?php echo esc_html( ucfirst( $u->user_role ) ); ?></span></td>
                                    <td>
                                        <?php if ( $verified ): ?>
                                            <span class="ac-kyc verified"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg> Verified ID</span>
                                        <?php else: ?>
                                            <span class="ac-kyc pending"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 7v5l3 3"/></svg> Pending Review</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $verified ? 'Linked' : 'Pending'; ?></td>
                                    <td style="text-align:right;">
                                        <?php if ( $verified ): ?>
                                            <a href="<?php echo esc_url( $edit_link ); ?>" class="ac-icon-btn" title="Edit user"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                                            <button type="button" class="ac-icon-btn danger" title="Suspend user"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.9" y1="4.9" x2="19.1" y2="19.1"/></svg></button>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url( $edit_link ); ?>" class="ac-btn ac-btn-sm">Review Docs</a>
                                            <button type="button" class="ac-btn ac-btn-sm ac-verify-btn" data-user="<?php echo (int) $u->user_id; ?>" data-status="verified" style="margin-left:6px;color:var(--ac-green);border-color:var(--ac-green);">Verify</button>
                                            <button type="button" class="ac-btn ac-btn-sm ac-verify-btn" data-user="<?php echo (int) $u->user_id; ?>" data-status="rejected" style="margin-left:6px;color:var(--ac-red);border-color:var(--ac-red);">Reject</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ( $tab === 'disputes' ): ?>
                <h1>Dispute Resolution</h1>
                <p class="ac-content-sub">Mediate conflicts and route frozen Escrow funds.</p>
                <div class="ac-card" style="text-align:center;padding:56px 24px;">
                    <p style="font-weight:700;font-size:16px;color:var(--ac-ink);margin:0 0 6px;">No open disputes</p>
                    <p style="color:#9c94b3;font-size:14px;margin:0;">Disputes raised by Clients or Providers will appear here once the dispute system is enabled.</p>
                </div>

            <?php elseif ( $tab === 'tasks' ): ?>
                <h1>Task Moderation</h1>
                <p class="ac-content-sub">Review live listings flagged by the community or automated filters.</p>
                <div class="ac-card" style="text-align:center;padding:56px 24px;">
                    <p style="font-weight:700;font-size:16px;color:var(--ac-ink);margin:0 0 6px;">No flagged tasks</p>
                    <p style="color:#9c94b3;font-size:14px;margin:0;">Reported or auto-flagged postings will show up here once reporting is enabled.</p>
                </div>
                
            <?php elseif ( $tab === 'financials' ): ?>

                <div class="ac-content-head">
                    <div>
                        <h1>Financial Ledger</h1>
                        <p class="ac-content-sub">Deep dive into platform revenues, Escrow movements, and API gateway logs.</p>
                    </div>
                    <button type="button" class="ac-btn ac-btn-primary"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg> Download BIR Report</button>
                </div>

                <div class="ac-stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
                    <div class="ac-stat-card">
                        <p class="ac-stat-label">Gross Completed Task Value</p>
                        <p class="ac-stat-value"><?php echo mb_price( $completed_budget_total ); ?></p>
                        <p class="ac-stat-foot muted">Total budget of completed tasks</p>
                    </div>
                    <div class="ac-stat-card">
                        <div class="ac-glow"></div>
                        <p class="ac-stat-label">Net Revenue (5% Fee)</p>
                        <p class="ac-stat-value green"><?php echo mb_price( $net_platform_revenue ); ?></p>
                        <p class="ac-stat-foot muted">Estimated platform fee</p>
                    </div>
                    <div class="ac-stat-card">
                        <p class="ac-stat-label">Gateway Fees</p>
                        <p class="ac-stat-value" style="color:#9c94b3;">Not yet integrated</p>
                        <p class="ac-stat-foot muted">Payment gateway not connected</p>
                    </div>
                </div>

                <div class="ac-card" style="padding:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;flex-wrap:wrap;gap:12px;">
                        <p class="ac-card-title" style="margin:0;">Master Transaction Logs</p>
                        <select class="ac-select"><option>All Gateways</option><option>PayMongo</option><option>GCash</option></select>
                    </div>
                    <div class="ac-table-wrap">
                        <table class="ac-table">
                            <thead><tr><th>Timestamp / TXN ID</th><th>Transaction Path</th><th>Amount</th><th>Status</th><th style="text-align:right;">Action</th></tr></thead>
                            <tbody>
                            <?php
                            $recent_completed = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}mb_tasks WHERE status='completed' ORDER BY updated_at DESC LIMIT 20" );
                            if ( empty( $recent_completed ) ): ?>
                                <tr><td colspan="5" style="text-align:center;color:#9c94b3;padding:32px;">No transactions yet. Completed task payouts will be logged here.</td></tr>
                            <?php else: foreach ( $recent_completed as $t ): $fee = $t->budget * $platform_fee_rate; ?>
                                <tr>
                                    <td><strong><?php echo esc_html( date( 'M j', strtotime( $t->updated_at ) ) ); ?></strong><br><span style="color:#9c94b3;font-size:12px;">Task #<?php echo esc_html( $t->rand_id ); ?></span></td>
                                    <td><span class="ac-flow-pill gray">System Escrow</span> &rarr; <span class="ac-flow-pill green">Provider Payout</span></td>
                                    <td><strong><?php echo mb_price( $t->budget - $fee ); ?></strong></td>
                                    <td><span class="ac-webhook-ok"><span class="dot"></span> Recorded</span></td>
                                    <td style="text-align:right;">&mdash;</td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* ==========================================================================
   M. HELPER FUNCTIONS
========================================================================== */

function mb_get_profile( $user_id ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mb_user_profiles WHERE user_id=%d", $user_id ) );
}

/**
 * Philippines-only region => city/municipality dataset used to drive the
 * cascading Region/City dropdowns at signup and in Settings. Single source
 * of truth so both places always stay consistent.
 */
function mb_ph_locations() {
    return [
        'National Capital Region (NCR)' => [ 'Manila', 'Quezon City', 'Caloocan', 'Las Piñas', 'Makati', 'Malabon', 'Mandaluyong', 'Marikina', 'Muntinlupa', 'Navotas', 'Parañaque', 'Pasay', 'Pasig', 'San Juan', 'Taguig', 'Valenzuela', 'Pateros' ],
        'Ilocos Region (Region I)' => [ 'Laoag', 'Batac', 'San Fernando (La Union)', 'Vigan', 'Candon', 'Alaminos', 'Dagupan', 'San Carlos (Pangasinan)', 'Urdaneta' ],
        'Cagayan Valley (Region II)' => [ 'Tuguegarao', 'Ilagan', 'Cauayan', 'Santiago' ],
        'Central Luzon (Region III)' => [ 'San Fernando (Pampanga)', 'Angeles', 'Balanga', 'Malolos', 'Meycauayan', 'San Jose del Monte', 'Cabanatuan', 'Gapan', 'Muñoz', 'Palayan', 'San Jose (Nueva Ecija)', 'Tarlac City', 'Olongapo' ],
        'Calabarzon (Region IV-A)' => [ 'Batangas City', 'Lipa', 'Tanauan', 'Cavite City', 'Bacoor', 'Dasmariñas', 'General Trias', 'Imus', 'Tagaytay', 'Trece Martires', 'Antipolo', 'Cainta', 'Taytay', 'Lucena', 'Tayabas', 'San Pablo', 'Santa Rosa', 'Biñan', 'Cabuyao', 'Calamba', 'San Pedro' ],
        'Mimaropa (Region IV-B)' => [ 'Calapan', 'Puerto Princesa', 'Odiongan', 'Boac' ],
        'Bicol Region (Region V)' => [ 'Legazpi', 'Naga', 'Iriga', 'Tabaco', 'Ligao', 'Sorsogon City', 'Masbate City', 'Daet' ],
        'Western Visayas (Region VI)' => [ 'Iloilo City', 'Bacolod', 'Roxas', 'Kalibo', 'San Jose de Buenavista', 'Passi', 'Silay', 'Talisay (Negros Occidental)', 'Bago' ],
        'Central Visayas (Region VII)' => [ 'Cebu City', 'Mandaue', 'Lapu-Lapu', 'Talisay (Cebu)', 'Toledo', 'Tagbilaran', 'Dumaguete', 'Bais' ],
        'Eastern Visayas (Region VIII)' => [ 'Tacloban', 'Ormoc', 'Catbalogan', 'Calbayog', 'Maasin', 'Borongan' ],
        'Zamboanga Peninsula (Region IX)' => [ 'Zamboanga City', 'Dipolog', 'Pagadian', 'Isabela (Basilan)' ],
        'Northern Mindanao (Region X)' => [ 'Cagayan de Oro', 'Iligan', 'Malaybalay', 'Valencia', 'Gingoog', 'Ozamiz', 'Oroquieta', 'Tangub' ],
        'Davao Region (Region XI)' => [ 'Davao City', 'Tagum', 'Panabo', 'Digos', 'Mati', 'Island Garden City of Samal' ],
        'Soccsksargen (Region XII)' => [ 'General Santos', 'Koronadal', 'Kidapawan', 'Tacurong', 'Cotabato City' ],
        'Caraga (Region XIII)' => [ 'Butuan', 'Surigao City', 'Bislig', 'Tandag', 'Bayugan' ],
        'Cordillera Administrative Region (CAR)' => [ 'Baguio', 'Tabuk', 'La Trinidad' ],
        'Bangsamoro (BARMM)' => [ 'Marawi', 'Lamitan', 'Jolo' ],
    ];
}

/**
 * Echoes a <script> tag exposing the PH locations dataset to the browser.
 * Safe to call more than once per page (just redefines the same object).
 */
function mb_ph_locations_script() {
    echo '<script>window.MB_PH_LOCATIONS = ' . wp_json_encode( mb_ph_locations() ) . ';</script>';
}

function mb_get_or_create_conversation( $user_one, $user_two, $task_id = null ) {
    global $wpdb;
    if ( $user_one == $user_two ) return 0;
    $a = min( $user_one, $user_two ); $b = max( $user_one, $user_two );
    $table = $wpdb->prefix . 'mb_chat_conversations';

    $sql = "SELECT id FROM {$table} WHERE user_a_id=%d AND user_b_id=%d";
    $params = [ $a, $b ];
    $sql .= $task_id ? " AND task_id=%d" : " AND task_id IS NULL";
    if ( $task_id ) $params[] = $task_id;

    $id = $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
    if ( $id ) return (int) $id;

    $wpdb->insert( $table, [
        'rand_id' => bntm_rand_id(), 'user_a_id' => $a, 'user_b_id' => $b, 'task_id' => $task_id ?: null,
        'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ),
    ], [ '%s','%d','%d','%d','%s','%s','%s' ] );
    return (int) $wpdb->insert_id;
}

function mb_price( $amount ) {
    return '₱' . number_format( (float) $amount, 2 );
}

function mb_get_stats() {
    global $wpdb;
    return [
        'total_tasks'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks" ),
        'live_tasks'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE status='live'" ),
        'completed_tasks' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mb_tasks WHERE status='completed'" ),
    ];
}