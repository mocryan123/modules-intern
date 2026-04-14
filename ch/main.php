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

function bntm_ch_is_frontend_context() {
    if (is_admin()) return false;
    if ((defined('REST_REQUEST') && REST_REQUEST) || wp_doing_ajax()) return false;

    // Query-arg driven CivicHub states (e.g. post view, tabs, bookmarks).
    foreach (['view_post', 'bookmarks', 'tab', 'sort'] as $key) {
        if (isset($_GET[$key])) return true;
    }

    if (!is_singular()) return false;
    $post = get_post();
    if (!$post || empty($post->post_content)) return false;

    foreach (array_keys(bntm_ch_get_shortcodes()) as $shortcode) {
        if (has_shortcode($post->post_content, $shortcode)) return true;
    }
    return false;
}

function bntm_ch_page_has_shortcode($shortcode) {
    if (!is_singular()) return false;
    $post = get_post();
    if (!$post || empty($post->post_content)) return false;
    return has_shortcode($post->post_content, $shortcode);
}

function bntm_ch_extract_inline_asset($html, $type) {
    $pattern = $type === 'css'
        ? '~<style[^>]*>(.*?)</style>~is'
        : '~<script[^>]*>(.*?)</script>~is';
    if (!preg_match($pattern, $html, $m)) return '';
    return trim($m[1]);
}

function bntm_ch_get_inline_asset_content($type) {
    static $cache = [];
    if (!in_array($type, ['css', 'js'], true)) return '';
    if (!function_exists('ch_global_styles') || !function_exists('ch_global_scripts')) return '';
    if (array_key_exists($type, $cache)) return $cache[$type];
    $inline_html = $type === 'css' ? ch_global_styles() : ch_global_scripts();
    $cache[$type] = bntm_ch_extract_inline_asset($inline_html, $type);
    return $cache[$type];
}

function bntm_ch_get_compiled_asset_url($type) {
    if (!in_array($type, ['css', 'js'], true)) return '';
    if (!function_exists('ch_global_styles') || !function_exists('ch_global_scripts')) return '';

    $content = bntm_ch_get_inline_asset_content($type);
    if ($content === '') return '';

    $hash = substr(md5($content), 0, 16);
    $upload = wp_upload_dir();
    if (!empty($upload['error'])) return '';

    $dir = trailingslashit($upload['basedir']) . 'civichub-assets';
    if (!wp_mkdir_p($dir)) return '';

    $filename = "ch-global-{$hash}.{$type}";
    $filepath = trailingslashit($dir) . $filename;
    if (!file_exists($filepath)) {
        file_put_contents($filepath, $content);
    }

    return trailingslashit($upload['baseurl']) . 'civichub-assets/' . $filename;
}

function bntm_ch_get_compiled_callback_asset_url($callback, $type = 'js') {
    if ($type !== 'js') return '';
    if (!is_callable($callback)) return '';

    $inline_html = call_user_func($callback);
    $content = bntm_ch_extract_inline_asset($inline_html, 'js');
    if ($content === '') return '';

    $hash = substr(md5($callback . '|' . $content), 0, 16);
    $upload = wp_upload_dir();
    if (!empty($upload['error'])) return '';

    $dir = trailingslashit($upload['basedir']) . 'civichub-assets';
    if (!wp_mkdir_p($dir)) return '';

    $filename = sanitize_file_name($callback) . "-{$hash}.js";
    $filepath = trailingslashit($dir) . $filename;
    if (!file_exists($filepath)) {
        file_put_contents($filepath, $content);
    }

    return trailingslashit($upload['baseurl']) . 'civichub-assets/' . $filename;
}

function bntm_ch_logo_url() {
    return BNTM_CH_URL . rawurlencode('logo.png');
}

function ch_output_global_styles_fallback() {
    // Only print once per request. The wp_head hook (priority 2) is the
    // canonical place; all shortcode/body calls become no-ops after that.
    static $printed = false;
    if ($printed) return;
    $printed = true;
    if (function_exists('ch_global_styles')) {
        echo ch_global_styles();
    }
}

function ch_get_email_verification_template_id() {
    return (int) apply_filters('ch_email_verification_template_id', 2);
}

function ch_get_brevo_api_key() {
    $api_key = trim((string) get_option('sib_api_key_v3', ''));
    return $api_key;
}

function ch_is_user_email_verified($user) {
    $user = $user instanceof WP_User ? $user : get_user_by('id', (int) $user);
    if (!$user) return false;
    if (user_can($user, 'manage_options')) return true;

    $verified = get_user_meta($user->ID, 'ch_email_verified', true);
    if ($verified === '') {
        return true;
    }

    return $verified === '1';
}

function ch_set_user_email_verification($user_id, $verified) {
    update_user_meta($user_id, 'ch_email_verified', $verified ? '1' : '0');
    if ($verified) {
        delete_user_meta($user_id, 'ch_email_verification_token_hash');
        delete_user_meta($user_id, 'ch_email_verification_expires');
    }
}

function ch_generate_email_verification_token($user_id) {
    $token = wp_generate_password(48, false, false);
    update_user_meta($user_id, 'ch_email_verification_token_hash', wp_hash_password($token));
    update_user_meta($user_id, 'ch_email_verification_expires', time() + DAY_IN_SECONDS);
    update_user_meta($user_id, 'ch_email_verification_sent_at', time());
    update_user_meta($user_id, 'ch_email_verified', '0');
    return $token;
}

function ch_get_email_verification_url($user_id, $token) {
    return add_query_arg([
        'tab' => 'login',
        'verify_email' => rawurlencode($token),
        'uid' => (int) $user_id,
    ], ch_get_auth_url('login'));
}

function ch_send_email_verification($user_id, $email = '') {
    $user = get_user_by('id', (int) $user_id);
    if (!$user) {
        return new WP_Error('ch_missing_user', 'Unable to find the new account.');
    }

    $api_key = ch_get_brevo_api_key();
    if ($api_key === '') {
        return new WP_Error('ch_missing_brevo_key', 'Brevo is not connected yet. Add your Brevo API key before enabling email verification.');
    }

    $token = ch_generate_email_verification_token($user->ID);
    $email = $email !== '' ? $email : $user->user_email;
    $verification_url = ch_get_email_verification_url($user->ID, $token);
    $display_name = $user->display_name ?: $user->user_login;

    $response = wp_remote_post('https://api.brevo.com/v3/smtp/email', [
        'timeout' => 20,
        'headers' => [
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'api-key' => $api_key,
        ],
        'body' => wp_json_encode([
            'templateId' => ch_get_email_verification_template_id(),
            'to' => [[
                'email' => $email,
                'name' => $display_name,
            ]],
            'params' => [
                'user_name' => $display_name,
                'verification_url' => $verification_url,
                'expiry_text' => '24 hours',
                'logo_url' => bntm_ch_logo_url(),
                'site_name' => get_bloginfo('name') ?: 'CivicHub',
            ],
        ]),
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('ch_brevo_request_failed', 'Could not send the verification email. Please try again.');
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    if ($status_code < 200 || $status_code >= 300) {
        return new WP_Error('ch_brevo_send_failed', 'Could not send the verification email. Please check your Brevo template and sender settings.');
    }

    return true;
}

function ch_verify_email_token($user_id, $token) {
    $user = get_user_by('id', (int) $user_id);
    if (!$user) {
        return new WP_Error('ch_verify_missing_user', 'Invalid verification link.');
    }

    if (ch_is_user_email_verified($user)) {
        return true;
    }

    $hash = (string) get_user_meta($user->ID, 'ch_email_verification_token_hash', true);
    $expires = (int) get_user_meta($user->ID, 'ch_email_verification_expires', true);

    if ($hash === '' || $expires <= 0) {
        return new WP_Error('ch_verify_invalid', 'This verification link is no longer valid.');
    }
    if (time() > $expires) {
        return new WP_Error('ch_verify_expired', 'This verification link has expired.');
    }
    if (!wp_check_password($token, $hash, $user->ID)) {
        return new WP_Error('ch_verify_invalid', 'This verification link is invalid.');
    }

    ch_set_user_email_verification($user->ID, true);
    return true;
}

function ch_get_auth_notice() {
    $notice = ['type' => '', 'message' => '', 'email' => ''];

    if (!empty($_GET['verify_email']) && !empty($_GET['uid'])) {
        $result = ch_verify_email_token((int) $_GET['uid'], sanitize_text_field(wp_unslash($_GET['verify_email'])));
        if (is_wp_error($result)) {
            $notice['type'] = 'error';
            $notice['message'] = $result->get_error_message();
        } else {
            $notice['type'] = 'success';
            $notice['message'] = 'Your email has been verified. You can sign in now.';
        }
    } elseif (!empty($_GET['verification_pending'])) {
        $notice['type'] = 'success';
        $notice['message'] = 'Account created. Please verify your email before signing in.';
    } elseif (!empty($_GET['verification_resent'])) {
        $notice['type'] = 'success';
        $notice['message'] = 'A new verification email has been sent.';
    } elseif (!empty($_GET['verification_error'])) {
        $notice['type'] = 'error';
        $notice['message'] = sanitize_text_field(wp_unslash($_GET['verification_error']));
    }

    if (!empty($_GET['email'])) {
        $notice['email'] = sanitize_email(wp_unslash($_GET['email']));
    }

    return $notice;
}

function ch_block_unverified_login($user, $username, $password) {
    if ($user instanceof WP_Error || !($user instanceof WP_User)) {
        return $user;
    }
    if ($password === '') {
        return $user;
    }

    global $wpdb;
    $profile_row = $wpdb->get_row($wpdb->prepare(
        "SELECT status FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d",
        $user->ID
    ));

    if ($profile_row && in_array($profile_row->status, ['banned', 'suspended'])) {
        return new WP_Error('ch_account_restricted', 'Your account has been restricted. Please contact support.');
    }

    if (ch_is_user_email_verified($user)) {
        return $user;
    }

    return new WP_Error('ch_email_unverified', 'Verify your email before signing in.');
}
add_filter('authenticate', 'ch_block_unverified_login', 30, 3);

function ch_get_feed_url() {
    static $url = null;

    if ($url === null) {
        if (bntm_ch_page_has_shortcode('ch_feed')) {
            $url = get_permalink(get_queried_object_id());
        } else {
            $page = get_page_by_path('forum-feed');
            $url = $page ? get_permalink($page) : home_url('/forum-feed/');
        }
    }

    return $url;
}

function ch_get_auth_url($tab = 'login', $redirect_to = '') {
    static $base = null;

    if ($base === null) {
        if (bntm_ch_page_has_shortcode('ch_auth')) {
            $base = get_permalink(get_queried_object_id());
        } else {
            $page = get_page_by_path('login-register');
            $base = $page ? get_permalink($page) : wp_login_url();
        }
    }

    $args = ['tab' => $tab];
    if ($redirect_to !== '') {
        $args['redirect_to'] = $redirect_to;
    }

    return add_query_arg($args, $base);
}

function ch_establish_user_session($user, $remember = false) {
    $user = $user instanceof WP_User ? $user : get_user_by('id', (int) $user);
    if (!$user) {
        return false;
    }

    // Reset local auth state before issuing a new login cookie so account
    // switching and stale browser sessions do not interfere with sign-in.
    wp_set_current_user(0);
    wp_clear_auth_cookie();

    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, (bool) $remember, is_ssl());

    return true;
}

add_action('wp_enqueue_scripts', function() {
    if (!bntm_ch_is_frontend_context()) return;

    $css_url = bntm_ch_get_compiled_asset_url('css');
    if ($css_url) {
        wp_enqueue_style('bntm-ch-global-style', $css_url, [], null);
    } else {
        $css_inline = bntm_ch_get_inline_asset_content('css');
        if ($css_inline !== '') {
            wp_register_style('bntm-ch-global-style-inline', false, [], null);
            wp_enqueue_style('bntm-ch-global-style-inline');
            wp_add_inline_style('bntm-ch-global-style-inline', $css_inline);
        }
    }

    $js_url = bntm_ch_get_compiled_asset_url('js');
    if ($js_url) {
        wp_enqueue_script('bntm-ch-global-script', $js_url, [], null, true);
    } else {
        $js_inline = bntm_ch_get_inline_asset_content('js');
        if ($js_inline !== '') {
            wp_register_script('bntm-ch-global-script-inline', '', [], null, true);
            wp_enqueue_script('bntm-ch-global-script-inline');
            wp_add_inline_script('bntm-ch-global-script-inline', $js_inline);
        }
    }

    $needs_feed_script = isset($_GET['view_post']) || bntm_ch_page_has_shortcode('ch_feed');
    if ($needs_feed_script && function_exists('ch_feed_scripts')) {
        $feed_js_url = bntm_ch_get_compiled_callback_asset_url('ch_feed_scripts');
        if ($feed_js_url) {
            wp_enqueue_script('bntm-ch-feed-script', $feed_js_url, ['bntm-ch-global-script'], null, true);
        } else {
            $feed_inline = bntm_ch_extract_inline_asset(ch_feed_scripts(), 'js');
            if ($feed_inline !== '') {
                wp_register_script('bntm-ch-feed-script-inline', '', ['bntm-ch-global-script'], null, true);
                wp_enqueue_script('bntm-ch-feed-script-inline');
                wp_add_inline_script('bntm-ch-feed-script-inline', $feed_inline);
            }
        }
    }

    if (isset($_GET['view_post']) && function_exists('ch_post_view_scripts')) {
        $post_view_js_url = bntm_ch_get_compiled_callback_asset_url('ch_post_view_scripts');
        if ($post_view_js_url) {
            wp_enqueue_script('bntm-ch-post-view-script', $post_view_js_url, ['bntm-ch-global-script','bntm-ch-feed-script'], null, true);
        } else {
            $post_view_inline = bntm_ch_extract_inline_asset(ch_post_view_scripts(), 'js');
            if ($post_view_inline !== '') {
                wp_register_script('bntm-ch-post-view-script-inline', '', ['bntm-ch-global-script'], null, true);
                wp_enqueue_script('bntm-ch-post-view-script-inline');
                wp_add_inline_script('bntm-ch-post-view-script-inline', $post_view_inline);
            }
        }
    }
}, 20);

add_action('wp_head', function() {
    if (!bntm_ch_is_frontend_context()) return;
    // Synchronous FOUC prevention: keep body invisible immediately
    echo '<script>document.documentElement.classList.add("ch-ui-pending");</script>';
    echo '<style>html.ch-ui-pending{opacity:0}html.ch-ui-ready{opacity:1}html.ch-ui-ready body,html.ch-ui-pending body{visibility:visible!important;opacity:inherit}html.ch-ui-pending body{opacity:0}html.ch-ui-ready body{opacity:1;transition:opacity .15s ease}</style>';
}, -1);

add_action('wp_head', function() {
    if (!bntm_ch_is_frontend_context()) return;
    ?>
    <script>
    (function() {
        function chRevealCommunityUi() {
            if (document.documentElement.classList.contains('ch-ui-ready')) return;
            document.documentElement.classList.remove('ch-ui-pending');
            document.documentElement.classList.add('ch-ui-ready');
        }
        try {
            if (localStorage.getItem('ch_dark_mode') === '1') {
                document.documentElement.classList.add('ch-dark');
            }
        } catch (e) {}
        // Reveal immediately if DOM is ready, otherwise wait for DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                requestAnimationFrame(chRevealCommunityUi);
            }, { once: true });
        } else {
            // DOM is already loaded, reveal immediately
            chRevealCommunityUi();
        }
        // Safety fallback: reveal after 3s even if DOMContentLoaded hasn't fired
        setTimeout(function() {
            chRevealCommunityUi();
        }, 3000);
    })();
    </script>
    <?php
}, 1);

add_action('wp_head', function() {
    if (!bntm_ch_is_frontend_context()) return;
    if (!function_exists('ch_global_styles')) return;
    echo ch_global_styles();
}, 2);

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
            icon VARCHAR(50) DEFAULT 'Forum',
            post_count INT UNSIGNED DEFAULT 0,
            follower_count INT UNSIGNED DEFAULT 0,
            sort_order INT DEFAULT 0,
            status ENUM('active','archived') DEFAULT 'active',
            is_private TINYINT(1) DEFAULT 0,
            require_post_approval TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_slug (slug),
            INDEX idx_private (is_private),
            INDEX idx_post_approval (require_post_approval)
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
            INDEX idx_status_created (status, created_at),
            INDEX idx_status_category_created (status, category_id, created_at),
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
            INDEX idx_parent (parent_id),
            INDEX idx_post_status_parent_created (post_id, status, parent_id, created_at)
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
            INDEX idx_read (user_id, is_read),
            INDEX idx_user_created (user_id, created_at)
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
            INDEX idx_target (target_type, target_id),
            INDEX idx_status_created (status, created_at)
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
            INDEX idx_user (user_id),
            INDEX idx_status_created (status, created_at),
            INDEX idx_location (location)
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

    // Column-migration guards — run only when the stored schema version is behind.
    // This block is called exclusively from the activation hook and the
    // admin upgrade routine, never on ordinary page loads.
    $schema_version = (int)get_option('ch_schema_version', 0);

    if ($schema_version < 1) {
        $cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ch_categories LIKE 'is_private'");
        $migration_ok = !empty($cols);
        if (!$migration_ok) {
            $migration_ok = $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}ch_categories ADD COLUMN is_private TINYINT(1) DEFAULT 0 AFTER status"
            ) !== false;
        }
        if ($migration_ok) {
            update_option('ch_schema_version', 1);
            $schema_version = 1;
        }
    }

    if ($schema_version < 2) {
        $approval_cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ch_categories LIKE 'require_post_approval'");
        $migration_ok = !empty($approval_cols);
        if (!$migration_ok) {
            $migration_ok = $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}ch_categories ADD COLUMN require_post_approval TINYINT(1) DEFAULT 0 AFTER is_private"
            ) !== false;
        }
        if ($migration_ok) {
            update_option('ch_schema_version', 2);
            $schema_version = 2;
        }
    }

    if ($schema_version < 3) {
        $post_cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ch_posts LIKE 'guest_name'");
        $post_migration_ok = !empty($post_cols);
        if (!$post_migration_ok) {
            $post_migration_ok = $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}ch_posts ADD COLUMN guest_name VARCHAR(100) DEFAULT NULL AFTER is_anonymous"
            ) !== false;
        }

        $cm_cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ch_comments LIKE 'guest_name'");
        $comment_migration_ok = !empty($cm_cols);
        if (!$comment_migration_ok) {
            $comment_migration_ok = $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}ch_comments ADD COLUMN guest_name VARCHAR(100) DEFAULT NULL AFTER is_anonymous"
            ) !== false;
        }

        if ($post_migration_ok && $comment_migration_ok) {
            update_option('ch_schema_version', 3);
            $schema_version = 3;
        }
    }

    if ($schema_version < 4) {
        $index_migration_ok = true;
        $index_migration_ok = bntm_ch_add_index_if_missing("{$wpdb->prefix}ch_posts", 'idx_status_created', 'INDEX idx_status_created (status, created_at)') && $index_migration_ok;
        $index_migration_ok = bntm_ch_add_index_if_missing("{$wpdb->prefix}ch_posts", 'idx_status_category_created', 'INDEX idx_status_category_created (status, category_id, created_at)') && $index_migration_ok;
        $index_migration_ok = bntm_ch_add_index_if_missing("{$wpdb->prefix}ch_comments", 'idx_post_status_parent_created', 'INDEX idx_post_status_parent_created (post_id, status, parent_id, created_at)') && $index_migration_ok;
        $index_migration_ok = bntm_ch_add_index_if_missing("{$wpdb->prefix}ch_notifications", 'idx_user_created', 'INDEX idx_user_created (user_id, created_at)') && $index_migration_ok;
        $index_migration_ok = bntm_ch_add_index_if_missing("{$wpdb->prefix}ch_reports", 'idx_status_created', 'INDEX idx_status_created (status, created_at)') && $index_migration_ok;
        $index_migration_ok = bntm_ch_add_index_if_missing("{$wpdb->prefix}ch_user_profiles", 'idx_status_created', 'INDEX idx_status_created (status, created_at)') && $index_migration_ok;
        $index_migration_ok = bntm_ch_add_index_if_missing("{$wpdb->prefix}ch_user_profiles", 'idx_location', 'INDEX idx_location (location)') && $index_migration_ok;

        if ($index_migration_ok) {
            update_option('ch_schema_version', 4);
            $schema_version = 4;
        }
    }

    // Bust the cached schema flag so the next request re-checks the live column list
    delete_transient('ch_has_post_approval_col');

    return count($tables);
}

function bntm_ch_add_index_if_missing($table, $index_name, $index_sql) {
    global $wpdb;

    $existing_index = $wpdb->get_var(
        $wpdb->prepare("SHOW INDEX FROM {$table} WHERE Key_name = %s", $index_name)
    );

    if ($existing_index) {
        return true;
    }

    return $wpdb->query("ALTER TABLE {$table} ADD {$index_sql}") !== false;
}

// ============================================================
// FRONTEND: LOGIN / REGISTER PAGE
// ============================================================

function bntm_shortcode_ch_auth() {
    if (!empty($_GET['verify_email']) && !empty($_GET['uid'])) {
        $verify_user_id = (int) $_GET['uid'];
        $result = ch_verify_email_token($verify_user_id, sanitize_text_field(wp_unslash($_GET['verify_email'])));
        if (!is_wp_error($result)) {
            $verified_user = get_user_by('id', $verify_user_id);
            if ($verified_user instanceof WP_User) {
                ch_establish_user_session($verified_user, true);
                ch_ensure_profile($verified_user->ID);

                $redirect_after_verify = esc_url_raw(wp_unslash($_GET['redirect_to'] ?? ''));
                wp_safe_redirect($redirect_after_verify ?: ch_get_feed_url());
                exit;
            }
        }
    }

    $notice = ch_get_auth_notice();

    // If already logged in, redirect to feed
    if (is_user_logged_in()) {
        wp_redirect(ch_get_feed_url());
        exit;
    }

    $redirect = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : '';
    $active   = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';

    ob_start();
    $feed_url_auth = ch_get_feed_url();
    $auth_user_id  = get_current_user_id();
    ?>
    <nav class="ch-top-nav" style="position:relative;">
        <div class="ch-top-nav-logo" style="margin:0 16px 0 0;display:flex;align-items:center;">
            <a href="<?php echo esc_url($feed_url_auth); ?>" style="display:flex;align-items:center;text-decoration:none;">
                <img src="<?php echo esc_url(bntm_ch_logo_url()); ?>" alt="CivicHub Logo" class="ch-brand-logo" style="height:28px;">
            </a>
        </div>
        <div style="display:flex;align-items:center;gap:6px;margin-left:auto;">
            <?php if (!$auth_user_id): ?>
            <a href="<?php echo esc_url(ch_get_auth_url('login')); ?>"    class="ch-btn ch-btn-secondary ch-btn-sm">Sign In</a>
            <a href="<?php echo esc_url(ch_get_auth_url('register')); ?>" class="ch-btn ch-btn-primary  ch-btn-sm">Join</a>
            <?php endif; ?>
            <a href="<?php echo esc_url($feed_url_auth); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Forum
            </a>
        </div>
    </nav>
    <div class="ch-auth-wrap">
        <div class="ch-auth-card">

            <div class="ch-auth-brand">
                <div class="ch-auth-logo">
                    <img src="<?php echo esc_url(bntm_ch_logo_url()); ?>" alt="CivicHub Logo">
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
            <?php if (!empty($notice['message'])): ?>
                <div class="bntm-notice-<?php echo $notice['type'] === 'success' ? 'success' : 'error'; ?>" style="margin-bottom:14px;">
                    <?php echo esc_html($notice['message']); ?>
                </div>
            <?php endif; ?>
            <div class="ch-auth-resend" id="ch-auth-resend-wrap" <?php echo !empty($notice['email']) && $active === 'login' ? 'data-email="' . esc_attr($notice['email']) . '"' : 'style="display:none"'; ?>>
                <?php if (!empty($notice['email']) && $active === 'login'): ?>
                    <div class="ch-auth-resend-copy">Need another verification email for <strong><?php echo esc_html($notice['email']); ?></strong>?</div>
                    <button type="button" class="ch-btn ch-btn-secondary ch-btn-full" id="ch-resend-verification-btn"
                            onclick="chResendVerification('<?php echo wp_create_nonce('ch_auth_nonce'); ?>')">
                        Resend Verification Email
                    </button>
                <?php endif; ?>
            </div>

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
                        <input type="checkbox" id="ch-reg-terms" required disabled>
                        I agree to the <a href="#" class="ch-auth-link" id="ch-guidelines-link" onclick="chOpenGuidelinesModal(); return false;">Community Guidelines</a>
                        <span id="ch-guidelines-hint" style="display:block;font-size:11px;color:var(--ch-text-subtle);margin-top:3px;">Please read the Community Guidelines before agreeing.</span>
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
    .ch-auth-resend { margin-bottom: 14px; padding: 14px 16px; border: 1px solid var(--ch-border); border-radius: 14px; background: color-mix(in srgb, var(--ch-surface) 76%, var(--ch-bg) 24%); }
    .ch-auth-resend-copy { font-size: 13px; color: var(--ch-text-muted); margin-bottom: 10px; line-height: 1.6; }
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

            fetch(ajaxurl, {method:'POST', body:fd, credentials: 'same-origin'})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    msgEl.innerHTML = '<div class="bntm-notice-success">Welcome back! Redirecting…</div>';
                    if(window.chNavBarStart) window.chNavBarStart();
                    setTimeout(() => { window.location.href = json.data.redirect || redirect || window.location.href; }, 800);
                } else {
                    msgEl.innerHTML = '<div class="bntm-notice-error">' + (json.data?.message || 'Login failed. Please try again.') + '</div>';
                    if (json.data?.requires_verification && json.data?.email) {
                        const resendWrap = document.getElementById('ch-auth-resend-wrap');
                        if (resendWrap) {
                            resendWrap.dataset.email = json.data.email;
                            resendWrap.style.display = 'block';
                            resendWrap.innerHTML = '<div class="ch-auth-resend-copy">Need another verification email for <strong>' + json.data.email + '</strong>?</div><button type="button" class="ch-btn ch-btn-secondary ch-btn-full" id="ch-resend-verification-btn" onclick="chResendVerification(\'' + nonce + '\')">Resend Verification Email</button>';
                        }
                    }
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

            fetch(ajaxurl, {method:'POST', body:fd, credentials: 'same-origin'})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    msgEl.innerHTML = '<div class="bntm-notice-success">Account created! Signing you in…</div>';
                    if(window.chNavBarStart) window.chNavBarStart();
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

        window.chResendVerification = function(nonce) {
            const resendWrap = document.getElementById('ch-auth-resend-wrap');
            const email = resendWrap?.dataset?.email || document.getElementById('ch-reg-email')?.value.trim() || document.getElementById('ch-login-user')?.value.trim();
            const msgEl = document.getElementById('ch-auth-msg');
            const btn = document.getElementById('ch-resend-verification-btn');

            if (!email) {
                msgEl.innerHTML = '<div class="bntm-notice-error">Enter your email first so we know where to resend the verification link.</div>';
                return;
            }

            if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; }

            const fd = new FormData();
            fd.append('action', 'ch_resend_verification');
            fd.append('email', email);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd, credentials: 'same-origin'})
            .then(r => r.json())
            .then(json => {
                msgEl.innerHTML = '<div class="bntm-notice-' + (json.success ? 'success' : 'error') + '">' + (json.data?.message || 'Unable to resend verification email.') + '</div>';
                if (btn) { btn.disabled = false; btn.textContent = 'Resend Verification Email'; }
            })
            .catch(() => {
                msgEl.innerHTML = '<div class="bntm-notice-error">Network error. Please try again.</div>';
                if (btn) { btn.disabled = false; btn.textContent = 'Resend Verification Email'; }
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
                <button onclick="chCloseGuidelinesModal()"
                        style="background:none;border:none;font-size:22px;cursor:pointer;color:#9ca3af;line-height:1;">&times;</button>
            </div>
            <div style="padding:24px;font-size:14px;color:var(--ch-text-muted);line-height:1.7;">
                <?php echo ch_get_guidelines_html(); ?>
            </div>
            <div style="padding:16px 24px;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;">
                <button onclick="chCloseGuidelinesModal()"
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

    // Guidelines modal with checkbox enable logic
    window.chOpenGuidelinesModal = function() {
        var modal = document.getElementById('ch-modal-guidelines');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    };
    window.chCloseGuidelinesModal = function() {
        var modal = document.getElementById('ch-modal-guidelines');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        // Enable checkbox and hide hint after viewing guidelines
        var terms = document.getElementById('ch-reg-terms');
        var hint = document.getElementById('ch-guidelines-hint');
        if (terms) {
            terms.disabled = false;
            terms.title = 'You can now agree to the Community Guidelines';
        }
        if (hint) {
            hint.style.display = 'none';
        }
    };

    // Close on overlay click
    document.addEventListener('click', function(e) {
        if (e.target.id === 'ch-modal-guidelines') {
            chCloseGuidelinesModal();
        }
    });
    </script>
    <?php
    ch_output_global_styles_fallback();
    echo ch_global_scripts();
    return ob_get_clean();
}

// ============================================================
// AJAX: LOGIN & REGISTER
// ============================================================

// NOTE: ch_login and ch_register nopriv hooks are registered via the
// $ajax_actions registry loop above (admin_only = false). No duplicate needed.

// ---- Open Graph meta tags for post sharing ----
add_action('wp_head', 'bntm_ch_og_meta_tags');

// Ensure viewport meta is present for mobile
add_action('wp_head', function() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">' . "\n";
}, 1);

// Performance: preload civic font resources only on CivicHub pages.
add_filter('wp_resource_hints', function($urls, $relation_type) {
    if (!bntm_ch_is_frontend_context()) return $urls;
    if ($relation_type === 'preconnect') {
        $urls[] = 'https://api.fontshare.com';
    }
    if ($relation_type === 'dns-prefetch') {
        $urls[] = 'https://api.fontshare.com';
    }
    return array_values(array_unique($urls));
}, 10, 2);

add_action('wp_head', function() {
    if (!bntm_ch_is_frontend_context()) return;
    echo "<link rel=\"preload\" as=\"style\" href=\"https://api.fontshare.com/v2/css?f[]=satoshi@300,400,500,700&display=swap\" onload=\"this.onload=null;this.rel='stylesheet'\">\n";
    echo "<noscript><link rel=\"stylesheet\" href=\"https://api.fontshare.com/v2/css?f[]=satoshi@300,400,500,700&display=swap\"></noscript>\n";
}, 2);
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
    check_ajax_referer( 'ch_auth_nonce', 'nonce' );
 
    $username    = sanitize_text_field( $_POST['username'] ?? '' );
    $password    = $_POST['password'] ?? '';
    $remember    = ! empty( $_POST['remember'] );
    $redirect_to = esc_url_raw( $_POST['redirect_to'] ?? '' );
 
    if ( ! $username || ! $password ) {
        wp_send_json_error( [ 'message' => 'Username and password are required.' ] );
    }
 
    // ── Step 1: Authenticate (does NOT set any cookie) ──────────────────────
    $user = wp_authenticate( $username, $password );
 
    if ( is_wp_error( $user ) ) {
        // Surface the ch_email_unverified and ch_account_restricted errors
        // that are injected by the ch_block_unverified_login filter.
        $code = $user->get_error_code();
 
        if ( $code === 'ch_account_restricted' ) {
            wp_send_json_error( [
                'message'             => 'Your account has been restricted. Please contact support.',
                'account_restricted'  => true,
            ] );
        }
 
        if ( $code === 'ch_email_unverified' ) {
            // Try to resolve the email address so the front-end can show a
            // "Resend verification" prompt pre-filled with the user's email.
            $blocked_user = get_user_by( 'login', $username );
            if ( ! $blocked_user && is_email( $username ) ) {
                $blocked_user = get_user_by( 'email', $username );
            }
 
            wp_send_json_error( [
                'message'                => 'Verify your email before signing in.',
                'requires_verification'  => true,
                'email'                  => $blocked_user ? $blocked_user->user_email
                                                          : ( is_email( $username ) ? $username : '' ),
            ] );
        }
 
        $msg = in_array( $code, [ 'incorrect_password', 'invalid_username', 'invalid_email' ], true )
            ? 'Incorrect username or password.'
            : $user->get_error_message();
 
        wp_send_json_error( [ 'message' => strip_tags( $msg ) ] );
    }
 
    // ── Step 2: Profile / status check ──────────────────────────────────────
    global $wpdb;
    $profile_row = $wpdb->get_row( $wpdb->prepare(
        "SELECT status FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d",
        $user->ID
    ) );
 
    if ( $profile_row && in_array( $profile_row->status, [ 'banned', 'suspended' ], true ) ) {
        wp_send_json_error( [
            'message'            => 'Your account has been restricted. Please contact support.',
            'account_restricted' => true,
        ] );
    }
 
    // ── Step 3: Set auth cookie explicitly ──────────────────────────────────
    //
    // Use is_ssl() so the Secure flag always matches the site's actual
    // protocol. This is the key fix for live servers behind proxies or
    // load balancers where wp_signon()'s internal check can disagree.
    ch_establish_user_session($user, $remember);
    do_action( 'wp_login', $user->user_login, $user );
 
    // ── Step 4: Ensure CivicHub profile row exists ──────────────────────────
    ch_ensure_profile( $user->ID );
 
    $default_url = ch_get_feed_url();
    $redirect    = $redirect_to ?: $default_url;
 
    wp_send_json_success( [ 'redirect' => $redirect ] );
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
    if (ch_get_brevo_api_key() === '') {
        wp_send_json_error(['message' => 'Brevo is not connected yet. Add your Brevo API key before enabling email verification.']);
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
    ch_flush_overview_cache();

    $verification_sent = ch_send_email_verification($user_id, $email);
    if (is_wp_error($verification_sent)) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        $wpdb->delete("{$wpdb->prefix}ch_user_profiles", ['user_id' => $user_id], ['%d']);
        wp_delete_user($user_id);
        wp_send_json_error(['message' => $verification_sent->get_error_message()]);
    }

    $redirect = add_query_arg([
        'tab' => 'login',
        'verification_pending' => 1,
        'email' => $email,
    ], ch_get_auth_url('login', $redirect_to));

    wp_send_json_success([
        'message' => 'Account created. Check your email to verify your account before signing in.',
        'redirect' => $redirect,
    ]);
}

function bntm_ajax_ch_resend_verification() {
    check_ajax_referer('ch_auth_nonce', 'nonce');

    $email = sanitize_email($_POST['email'] ?? '');
    if (!$email) {
        wp_send_json_error(['message' => 'A valid email address is required.']);
    }

    $user = get_user_by('email', $email);
    if (!$user) {
        wp_send_json_error(['message' => 'No account was found for that email address.']);
    }
    if (ch_is_user_email_verified($user)) {
        wp_send_json_error(['message' => 'That account is already verified. You can sign in now.']);
    }

    $last_sent = (int) get_user_meta($user->ID, 'ch_email_verification_sent_at', true);
    if ($last_sent > 0 && (time() - $last_sent) < MINUTE_IN_SECONDS) {
        wp_send_json_error(['message' => 'Please wait a moment before requesting another verification email.']);
    }

    $verification_sent = ch_send_email_verification($user->ID, $email);
    if (is_wp_error($verification_sent)) {
        wp_send_json_error(['message' => $verification_sent->get_error_message()]);
    }

    wp_send_json_success(['message' => 'A new verification email has been sent.']);
}

$ajax_actions = [
    'ch_login'               => ['bntm_ajax_ch_login', false],
    'ch_register'            => ['bntm_ajax_ch_register', false],
    'ch_resend_verification' => ['bntm_ajax_ch_resend_verification', false],
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
    'ch_get_notification_count' => ['bntm_ajax_ch_get_notification_count', false],
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
    'ch_feed_sort'           => ['bntm_ajax_ch_feed_sort', false],
    'ch_admin_tab'           => ['bntm_ajax_ch_admin_tab', false],
    'ch_myfeed_subtab'       => ['bntm_ajax_ch_myfeed_subtab', false],
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

require_once BNTM_CH_PATH . 'includes/frontend.php';
