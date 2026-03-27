<?php
if (!defined('ABSPATH')) exit;

// MAIN DASHBOARD SHORTCODE
// ============================================================

function ch_guest_landing_page() {
    global $wpdb;

    $feed_page = get_page_by_path('forum-feed');
    $feed_url  = $feed_page ? get_permalink($feed_page) : home_url('/');

    $auth_page = get_page_by_path('login-register');
    $auth_perm = $auth_page ? get_permalink($auth_page) : get_permalink();
    $login_url = $auth_page ? add_query_arg('tab', 'login', $auth_perm) : wp_login_url($auth_perm);
    $reg_url   = $auth_page ? add_query_arg('tab', 'register', $auth_perm) : wp_registration_url();

    // Preview data for guests
    $categories = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}ch_categories
         WHERE status='active'
         ORDER BY sort_order ASC, post_count DESC
         LIMIT 8"
    );

    $trending_posts = $wpdb->get_results(
        "SELECT p.*, c.name as cat_name, c.color as cat_color, c.slug as cat_slug
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         WHERE p.status = 'active'
           AND (c.is_private = 0 OR c.is_private IS NULL)
         ORDER BY (p.vote_count + p.comment_count * 2) DESC, p.created_at DESC
         LIMIT 4"
    );

    $members = $wpdb->get_results(
        "SELECT user_id, display_name, karma_points, location, avatar_url
         FROM {$wpdb->prefix}ch_user_profiles
         WHERE status='active'
         ORDER BY karma_points DESC, created_at DESC
         LIMIT 8"
    );

    ob_start();
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>

    <nav class="ch-top-nav">
        <div class="ch-nav-links">
            <a href="<?php echo esc_url($feed_url); ?>" class="ch-nav-link active">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Home
            </a>
            <a href="<?php echo esc_url($feed_url . '?sort=trending'); ?>" class="ch-nav-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
                Trending
            </a>
        </div>

        <div class="ch-user-bar">
            <a href="<?php echo esc_url($login_url); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">Sign In</a>
            <a href="<?php echo esc_url($reg_url); ?>" class="ch-btn ch-btn-primary ch-btn-sm">Join</a>
        </div>
    </nav>

    <div class="ch-guest-landing-wrap">
        <aside class="ch-guest-sidebar">
            <div class="ch-sidebar-widget">
                <h4>Categories</h4>
                <p class="ch-guest-helper">Follow topics to unlock posting on private categories.</p>

                <div class="ch-guest-cat-list">
                    <?php foreach ($categories as $cat): ?>
                        <a href="<?php echo esc_url($feed_url . '?cat=' . rawurlencode($cat->slug)); ?>" class="ch-cat-link">
                            <span class="ch-cat-dot" style="background:<?php echo esc_attr($cat->color); ?>"></span>
                            <span style="flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo esc_html($cat->name); ?>
                            </span>
                            <?php if (!empty($cat->is_private)): ?>
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="opacity:.5;flex-shrink:0" title="Private">
                                    <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            <?php endif; ?>
                            <span class="ch-cat-count"><?php echo (int)$cat->post_count; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="ch-guest-cta">
                    <a href="<?php echo esc_url($reg_url); ?>" class="ch-btn ch-btn-primary ch-btn-full">
                        Join to post
                    </a>
                </div>
            </div>
        </aside>

        <main class="ch-guest-main">
            <div class="ch-page-header">
                <div>
                    <h1>Community Forum</h1>
                    <p>Browse discussions and join when you are ready to share.</p>
                </div>
                <div class="ch-page-header-actions">
                    <a href="<?php echo esc_url($feed_url); ?>" class="ch-btn ch-btn-secondary">View forum feed</a>
                </div>
            </div>

            <div class="ch-card">
                <div class="ch-card-header">
                    <h3>Popular discussions</h3>
                    <a href="<?php echo esc_url($feed_url . '?sort=trending'); ?>" class="ch-link-btn">See more</a>
                </div>

                <div class="ch-card-body">
                    <?php if (!empty($trending_posts)): ?>
                        <div class="ch-info-list">
                            <?php foreach ($trending_posts as $post): ?>
                                <div class="ch-info-item">
                                    <div style="display:flex; flex-direction:column; min-width:0; gap:3px;">
                                        <a href="<?php echo esc_url($feed_url . '?view_post=' . rawurlencode($post->rand_id)); ?>" style="text-decoration:none; color:inherit;">
                                            <span class="ch-list-title" style="white-space:nowrap;">
                                                <?php echo esc_html($post->title); ?>
                                            </span>
                                        </a>
                                        <span class="ch-info-label" style="font-size:12px; color:var(--ch-text-muted);">
                                            <?php echo esc_html($post->cat_name ?? 'General'); ?>
                                        </span>
                                    </div>
                                    <span class="ch-date">
                                        <?php
                                        $ts = !empty($post->created_at) ? strtotime($post->created_at) : 0;
                                        echo $ts ? esc_html(human_time_diff($ts, current_time('timestamp')) . ' ago') : '';
                                        ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="ch-empty-state">
                            <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" stroke-width="1.5">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <p>No discussions yet. Be the first to start one.</p>
                            <a href="<?php echo esc_url($reg_url); ?>" class="ch-btn ch-btn-primary" style="margin-top:14px;">
                                Join the community
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <aside class="ch-guest-right">
            <div class="ch-sidebar-widget">
                <h4>Members</h4>
                <div class="ch-list-scroll" style="max-height: 320px;">
                    <?php foreach ($members as $m): ?>
                        <?php
                        $name = $m->display_name ?: 'User';
                        $initial = strtoupper(substr($name, 0, 1));
                        ?>
                        <div class="ch-list-item">
                            <div class="ch-user-cell">
                                <div class="ch-avatar-sm"><?php echo esc_html($initial); ?></div>
                                <div style="display:flex; flex-direction:column; min-width:0;">
                                    <div class="ch-list-title"><?php echo esc_html($name); ?></div>
                                    <?php if (!empty($m->location)): ?>
                                        <div class="ch-list-meta"><?php echo esc_html($m->location); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ch-list-item-stats">
                                <span class="ch-mini-stat">+<?php echo (int)$m->karma_points; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ch-sidebar-widget">
                <h4>Get started</h4>
                <p style="font-size:13px; color:var(--ch-text-muted); margin:0 0 14px;">
                    Create an account to follow categories and post.
                </p>
                <a href="<?php echo esc_url($login_url); ?>" class="ch-btn ch-btn-secondary ch-btn-full" style="margin-bottom:10px;">
                    Sign In
                </a>
                <a href="<?php echo esc_url($reg_url); ?>" class="ch-btn ch-btn-primary ch-btn-full">
                    Join
                </a>
            </div>
        </aside>
    </div>

    <?php /* Guest layout uses ch_global_styles */ ?>

    <?php
    echo ch_global_styles();
    echo ch_global_scripts();

    $content = ob_get_clean();
    return bntm_universal_container('CivicHub', $content);
}

function bntm_shortcode_ch() {
    if (!is_user_logged_in()) {
        return ch_guest_landing_page();
    }

    // Handle settings save
    if (isset($_POST['ch_save_settings']) && current_user_can('manage_options')) {
        if (!wp_verify_nonce($_POST['ch_settings_nonce'], 'ch_settings_nonce')) {
            wp_die('Security check failed');
        }
        $threshold = max(1, min(50, (int)($_POST['ch_report_auto_hide_threshold'] ?? 5)));
        update_option('ch_report_auto_hide_threshold', $threshold);
        $post_approval = isset($_POST['ch_post_approval_enabled']) ? 1 : 0;
        update_option('ch_post_approval_enabled', $post_approval);
        echo '<div class="bntm-notice bntm-notice-success">Settings saved successfully!</div>';
    }

    $current_user = wp_get_current_user();
    $user_id      = $current_user->ID;
    $active_tab   = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

    // Ensure user profile exists
    ch_ensure_profile($user_id);

    $is_admin = current_user_can('manage_options') || current_user_can('administrator');

    ob_start();
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>
    <div class="ch-dashboard-wrap">
        <div class="ch-sidebar">
            <div class="ch-sidebar-header">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <span>CivicHub</span>
            </div>
            <nav class="ch-nav">
                <a href="?tab=overview"    class="ch-nav-item <?php echo $active_tab==='overview'    ?'active':''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Overview
                </a>
                <a href="?tab=categories"  class="ch-nav-item <?php echo $active_tab==='categories'  ?'active':''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h8m-8 6h16"/></svg>
                    Categories
                </a>
                <a href="?tab=posts"       class="ch-nav-item <?php echo $active_tab==='posts'       ?'active':''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Posts
                </a>
                <a href="?tab=users"       class="ch-nav-item <?php echo $active_tab==='users'       ?'active':''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Users
                </a>
                <a href="?tab=reports"     class="ch-nav-item <?php echo $active_tab==='reports'     ?'active':''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Reports
                </a>
                <?php if ($is_admin): ?>
                <a href="?tab=moderation"  class="ch-nav-item <?php echo $active_tab==='moderation'  ?'active':''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Moderation
                </a>
                <a href="?tab=activity"    class="ch-nav-item <?php echo $active_tab==='activity'    ?'active':''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Activity Log
                </a>
                <a href="?tab=announcements" class="ch-nav-item <?php echo $active_tab==='announcements' ?'active':''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 17H2a3 3 0 0 0 3-3V9a7 7 0 0 1 14 0v5a3 3 0 0 0 3 3zm-8.27 4a2 2 0 0 1-3.46 0"/></svg>
                    Announcements
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="ch-main-content">
            <?php
            switch ($active_tab) {
                case 'overview':      echo ch_admin_overview_tab($user_id, $is_admin);     break;
                case 'categories':    echo ch_categories_tab($user_id, $is_admin);       break;
                case 'posts':         echo ch_posts_tab($user_id, $is_admin);            break;
                case 'users':         echo ch_users_tab($user_id, $is_admin);            break;
                case 'reports':       echo ch_reports_tab($user_id, $is_admin);          break;
                case 'moderation':    echo $is_admin ? ch_moderation_tab($user_id) : ''; break;
                case 'activity':      echo $is_admin ? ch_activity_tab($user_id) : '';         break;
                case 'announcements': echo $is_admin ? ch_announcements_tab() : '';                break;
                default:              echo ch_admin_overview_tab($user_id, $is_admin);             break;
            }
            ?>
        </div>
    </div>

    <?php echo ch_global_styles(); ?>
    <?php echo ch_global_scripts(); ?>
    <?php

    $content = ob_get_clean();
    return bntm_universal_container('CivicHub Admin', $content);
}

// ============================================================
// TAB: OVERVIEW
// ============================================================

function ch_admin_overview_tab($user_id, $is_admin) {
    global $wpdb;

    $total_posts     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE status='active'");
    $total_comments  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_comments WHERE status='active'");
    $total_users     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_user_profiles");
    $total_cats      = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_categories WHERE status='active'");
    $pending_reports = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_reports WHERE status='pending'");
    $pending_posts   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE status='pending'");

    $recent_posts = $wpdb->get_results(
        "SELECT p.*, c.name as cat_name, c.color as cat_color,
                u.display_name as author_name
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
         WHERE p.status = 'active'
         ORDER BY p.created_at DESC
         LIMIT 8"
    );

    $trending = $wpdb->get_results(
        "SELECT p.*, c.name as cat_name, c.color as cat_color
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         WHERE p.status = 'active'
           AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY (p.vote_count + p.comment_count * 2) DESC
         LIMIT 5"
    );

    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>Dashboard Overview</h1>
            <p>Community activity and platform statistics</p>
        </div>

    </div>

    <div class="ch-stats-grid">
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background: linear-gradient(135deg,#FF7551,#FF9A7F)">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num" id="stat-total-posts"><?php echo number_format($total_posts); ?></span>
                <span class="ch-stat-label">Total Posts</span>
            </div>
        </div>
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background: linear-gradient(135deg,#0ea5e9,#06b6d4)">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num" id="stat-total-comments"><?php echo number_format($total_comments); ?></span>
                <span class="ch-stat-label">Comments</span>
            </div>
        </div>
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background: linear-gradient(135deg,#10b981,#059669)">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num" id="stat-total-users"><?php echo number_format($total_users); ?></span>
                <span class="ch-stat-label">Members</span>
            </div>
        </div>
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background: linear-gradient(135deg,#f59e0b,#d97706)">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M4 6h16M4 12h8m-8 6h16"/>
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num"><?php echo number_format($total_cats); ?></span>
                <span class="ch-stat-label">Categories</span>
            </div>
        </div>
        <div class="ch-stat-card ch-stat-alert" id="pending-posts-card" style="<?php echo $pending_posts > 0 ? '' : 'display:none'; ?>">
            <div class="ch-stat-icon" style="background: linear-gradient(135deg,#f97316,#ea580c)">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num" id="stat-pending-posts"><?php echo number_format($pending_posts); ?></span>
                <span class="ch-stat-label">Pending Posts</span>
            </div>
        </div>
        <div class="ch-stat-card ch-stat-alert" id="pending-reports-card" style="<?php echo $pending_reports > 0 ? '' : 'display:none'; ?>">
            <div class="ch-stat-icon" style="background: linear-gradient(135deg,#ef4444,#dc2626)">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num" id="stat-pending-reports"><?php echo number_format($pending_reports); ?></span>
                <span class="ch-stat-label">Pending Reports</span>
            </div>
        </div>
    </div>

    <div class="ch-two-col">
        <div class="ch-card">
            <div class="ch-card-header">
                <h3>Recent Posts</h3>
                <a href="?tab=posts" class="ch-link-btn">View All</a>
            </div>
            <div class="ch-card-body ch-list-scroll">
                <?php if (empty($recent_posts)): ?>
                    <p class="ch-empty">No posts yet.</p>
                <?php else: foreach ($recent_posts as $post): ?>
                <div class="ch-list-item">
                    <div class="ch-list-item-main">
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                            <?php echo esc_html($post->cat_name ?? 'Uncategorized'); ?>
                        </span>
                        <p class="ch-list-title"><?php echo esc_html($post->title); ?></p>
                        <span class="ch-list-meta">
                            by <?php echo $post->is_anonymous ? 'Anonymous' : esc_html($post->author_name ?? 'User'); ?> &bull;
                            <?php echo human_time_diff(strtotime($post->created_at), current_time('timestamp')); ?> ago
                        </span>
                    </div>
                    <div class="ch-list-item-stats">
                        <span><?php echo (int)$post->vote_count; ?> votes</span>
                        <span><?php echo (int)$post->comment_count; ?> replies</span>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="ch-card">
            <div class="ch-card-header">
                <h3>Trending This Week</h3>
            </div>
            <div class="ch-card-body">
                <?php if (empty($trending)): ?>
                    <p class="ch-empty">No trending posts.</p>
                <?php else: foreach ($trending as $i => $post): ?>
                <div class="ch-trending-item">
                    <span class="ch-trending-rank"><?php echo $i+1; ?></span>
                    <div class="ch-trending-content">
                        <p class="ch-list-title"><?php echo esc_html($post->title); ?></p>
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                            <?php echo esc_html($post->cat_name ?? 'General'); ?>
                        </span>
                    </div>
                    <span class="ch-trending-score"><?php echo ($post->vote_count + $post->comment_count * 2); ?> pts</span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <script>
    (function() {
        let statsInterval;

        function updateLiveStats() {
            fetch(ajaxurl + '?action=ch_live_stats', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'nonce=' + encodeURIComponent('<?php echo wp_create_nonce("ch_live_stats_nonce"); ?>')
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const stats = data.data;
                    document.getElementById('stat-total-posts').textContent = stats.total_posts.toLocaleString();
                    document.getElementById('stat-total-comments').textContent = stats.total_comments.toLocaleString();
                    document.getElementById('stat-total-users').textContent = stats.total_users.toLocaleString();

                    const reportsCard = document.getElementById('pending-reports-card');
                    const reportsNum = document.getElementById('stat-pending-reports');

                    if (stats.pending_reports > 0) {
                        reportsNum.textContent = stats.pending_reports.toLocaleString();
                        reportsCard.style.display = '';
                    } else {
                        reportsCard.style.display = 'none';
                    }

                    const postsCard = document.getElementById('pending-posts-card');
                    const postsNum = document.getElementById('stat-pending-posts');

                    if (stats.pending_posts > 0) {
                        postsNum.textContent = stats.pending_posts.toLocaleString();
                        postsCard.style.display = '';
                    } else {
                        postsCard.style.display = 'none';
                    }
                }
            })
            .catch(err => console.log('Stats update failed:', err));
        }

        // Update stats every 30 seconds
        statsInterval = setInterval(updateLiveStats, 30000);

        // Clear interval when page unloads
        window.addEventListener('beforeunload', () => {
            if (statsInterval) clearInterval(statsInterval);
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// LIVE STATS UPDATE FUNCTION
// ============================================================

function ch_update_live_stats() {
    global $wpdb;
    $stats = [
        'total_posts'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE status='active'"),
        'total_comments'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_comments WHERE status='active'"),
        'total_users'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_user_profiles"),
        'pending_reports' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_reports WHERE status='pending'"),
        'pending_posts'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE status='pending'"),
        'posts_today'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE DATE(created_at) = CURDATE()"),
        'comments_today'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_comments WHERE DATE(created_at) = CURDATE()"),
    ];
    return $stats;
}

// ============================================================
// TAB: CATEGORIES
// ============================================================

function ch_categories_tab($user_id, $is_admin) {
    global $wpdb;

    $search = sanitize_text_field($_GET['cat_search'] ?? '');
    $sort   = sanitize_text_field($_GET['cat_sort'] ?? 'sort_order');

    $where = "WHERE 1=1";
    if ($search) {
        $where .= $wpdb->prepare(" AND name LIKE %s", '%' . $wpdb->esc_like($search) . '%');
    }

    if ($sort === 'activity') {
        $order_by = 'post_count DESC';
    } elseif ($sort === 'popularity') {
        $order_by = 'follower_count DESC';
    } elseif ($sort === 'name') {
        $order_by = 'name ASC';
    } else {
        $order_by = 'sort_order ASC, name ASC';
    }

    $categories = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}ch_categories $where ORDER BY $order_by"
    );
    $nonce = wp_create_nonce('ch_category_nonce');

    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>Forum Categories</h1>
            <p>Manage discussion categories and topics</p>
        </div>
        <div class="ch-page-header-actions">
            <form method="get" class="ch-categories-filter-form">
                <input type="hidden" name="tab" value="categories">
                <div class="ch-field-row ch-categories-filter-row" style="align-items:center; gap:10px;">
                    <div class="ch-field-group ch-filter-search-group">
                        <label class="ch-label">Search</label>
                        <input type="text" name="cat_search" class="ch-input" placeholder="Search categories" value="<?php echo esc_attr($search); ?>">
                    </div>
                    <div class="ch-field-group ch-filter-sort-group">
                        <label class="ch-label">Sort by</label>
                        <select name="cat_sort" class="ch-input">
                            <option value="sort_order" <?php selected($sort, 'sort_order'); ?>>Custom order</option>
                            <option value="name" <?php selected($sort, 'name'); ?>>Name</option>
                            <option value="activity" <?php selected($sort, 'activity'); ?>>Activity</option>
                            <option value="popularity" <?php selected($sort, 'popularity'); ?>>Popularity</option>
                        </select>
                    </div>
                    <div class="ch-filter-actions ch-filter-btn-group">
                        <button type="submit" class="ch-btn ch-btn-secondary">Apply</button>
                        <a href="?tab=categories" class="ch-btn ch-btn-outline">Reset</a>
                    </div>
                </div>
            </form>
            <?php if ($is_admin): ?>
            <button class="ch-btn ch-btn-primary" onclick="chOpenModal('ch-modal-create-cat')">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Category
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="ch-categories-grid" id="ch-categories-grid">
        <?php if (empty($categories)): ?>
            <div class="ch-empty-state">
                <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" stroke-width="1.5"><path d="M4 6h16M4 12h8m-8 6h16"/></svg>
                <p>No categories yet. Create the first one!</p>
            </div>
        <?php else: foreach ($categories as $cat): ?>
        <div class="ch-cat-card" data-id="<?php echo (int)$cat->id; ?>">
            <div class="ch-cat-card-color" style="background:<?php echo esc_attr($cat->color); ?>"></div>
            <div class="ch-cat-card-body">
                <div class="ch-cat-card-header">
                    <h4><?php echo esc_html($cat->name); ?></h4>
                    <?php if ($is_admin): ?>
                    <div class="ch-actions-row">
                        <button class="ch-icon-btn" title="Edit" onclick="chEditCategory(<?php echo (int)$cat->id; ?>, '<?php echo esc_js($cat->name); ?>', '<?php echo esc_js($cat->description); ?>', '<?php echo esc_attr($cat->color); ?>', '<?php echo esc_js($cat->icon); ?>', <?php echo (int)$cat->is_private; ?>)">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="ch-icon-btn" title="Toggle visibility" onclick="chToggleCategoryStatus(<?php echo (int)$cat->id; ?>, '<?php echo esc_js($cat->status); ?>', this)">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button class="ch-icon-btn ch-icon-btn-danger" title="Delete" onclick="chDeleteCategory(<?php echo (int)$cat->id; ?>, '<?php echo esc_js($cat->name); ?>', this)">>
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <p class="ch-cat-desc"><?php echo esc_html($cat->description ?: 'No description.'); ?></p>
                <div class="ch-cat-stats">
                    <span><?php echo number_format($cat->post_count); ?> posts</span>
                    <span><?php echo number_format($cat->follower_count); ?> followers</span>
                    <span class="ch-status-badge ch-status-<?php echo $cat->status; ?>"><?php echo ucfirst($cat->status); ?></span>
                    <?php if ($cat->is_private): ?>
                    <span class="ch-status-badge" style="background:#fef3c7;color:#92400e;">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Private
                    </span>
                    <?php else: ?>
                    <span class="ch-status-badge" style="background:#d1fae5;color:#065f46;">Public</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Create Category Modal -->
    <div id="ch-modal-create-cat" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal">
            <div class="ch-modal-header">
                <h3>Create Category</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-create-cat')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <div class="ch-field-group">
                    <label class="ch-label">Category Name *</label>
                    <input type="text" id="ch-cat-name" class="ch-input" placeholder="e.g., Community Problems">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Description</label>
                    <textarea id="ch-cat-desc" class="ch-input ch-textarea" rows="3" placeholder="Brief description of this category"></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Color</label>
                    <div class="ch-color-picker-wrap">
                    <div class="ch-color-row">
                        <input type="color" id="ch-cat-color-wheel" class="ch-color-wheel" value="#FF7551"
                               oninput="chSyncColorWheel('ch-cat-color-wheel','ch-cat-color','ch-cat-color-preview')">
                        <span class="ch-color-hex-preview" id="ch-cat-color-preview" style="background:#FF7551;"></span>
                        <input type="text" id="ch-cat-color" class="ch-input ch-color-hex-input" value="#FF7551" placeholder="#FF7551" maxlength="7"
                               oninput="chSyncColorHex('ch-cat-color-wheel','ch-cat-color','ch-cat-color-preview')">
                    </div>
                    <div class="ch-color-swatches">
                        <?php foreach (['#FF7551','#FF6640','#ef4444','#f59e0b','#10b981','#06b6d4','#8b5cf6','#ec4899','#f97316','#84cc16'] as $sw): ?>
                        <button type="button" class="ch-swatch" style="background:<?php echo $sw; ?>;" title="<?php echo $sw; ?>"
                                onclick="chPickSwatch('ch-cat-color-wheel','ch-cat-color','ch-cat-color-preview','<?php echo $sw; ?>')"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Visibility</label>
                    <div class="ch-visibility-toggle">
                        <label class="ch-vis-option">
                            <input type="radio" name="ch-cat-visibility" id="ch-cat-vis-public" value="0" checked>
                            <span class="ch-vis-label">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                Public
                            </span>
                            <span class="ch-vis-desc">Anyone can view, guests can post</span>
                        </label>
                        <label class="ch-vis-option">
                            <input type="radio" name="ch-cat-visibility" id="ch-cat-vis-private" value="1">
                            <span class="ch-vis-label">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Private
                            </span>
                            <span class="ch-vis-desc">Only followers can view and post</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-create-cat')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-save-cat-btn" onclick="chSaveCategory('<?php echo esc_attr($nonce); ?>')">Create Category</button>
            </div>
            <div id="ch-cat-msg"></div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="ch-modal-edit-cat" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal">
            <div class="ch-modal-header">
                <h3>Edit Category</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-edit-cat')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <input type="hidden" id="ch-edit-cat-id">
                <div class="ch-field-group">
                    <label class="ch-label">Category Name *</label>
                    <input type="text" id="ch-edit-cat-name" class="ch-input">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Description</label>
                    <textarea id="ch-edit-cat-desc" class="ch-input ch-textarea" rows="3"></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Color</label>
                    <div class="ch-color-picker-wrap">
                    <div class="ch-color-row">
                        <input type="color" id="ch-edit-cat-color-wheel" class="ch-color-wheel" value="#FF7551"
                               oninput="chSyncColorWheel('ch-edit-cat-color-wheel','ch-edit-cat-color','ch-edit-cat-color-preview')">
                        <span class="ch-color-hex-preview" id="ch-edit-cat-color-preview" style="background:#FF7551;"></span>
                        <input type="text" id="ch-edit-cat-color" class="ch-input ch-color-hex-input" value="#FF7551" placeholder="#FF7551" maxlength="7"
                               oninput="chSyncColorHex('ch-edit-cat-color-wheel','ch-edit-cat-color','ch-edit-cat-color-preview')">
                    </div>
                    <div class="ch-color-swatches">
                        <?php foreach (['#FF7551','#FF6640','#ef4444','#f59e0b','#10b981','#06b6d4','#8b5cf6','#ec4899','#f97316','#84cc16'] as $sw): ?>
                        <button type="button" class="ch-swatch" style="background:<?php echo $sw; ?>;" title="<?php echo $sw; ?>"
                                onclick="chPickSwatch('ch-edit-cat-color-wheel','ch-edit-cat-color','ch-edit-cat-color-preview','<?php echo $sw; ?>')"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Visibility</label>
                    <div class="ch-visibility-toggle">
                        <label class="ch-vis-option">
                            <input type="radio" name="ch-edit-cat-visibility" id="ch-edit-cat-vis-public" value="0">
                            <span class="ch-vis-label">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                Public
                            </span>
                        </label>
                        <label class="ch-vis-option">
                            <input type="radio" name="ch-edit-cat-visibility" id="ch-edit-cat-vis-private" value="1">
                            <span class="ch-vis-label">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Private
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-edit-cat')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-update-cat-btn" onclick="chUpdateCategory('<?php echo esc_attr($nonce); ?>')">Save Changes</button>
            </div>
            <div id="ch-edit-cat-msg"></div>
        </div>
    </div>

    <script>
    (function() {
        window.chEditCategory = function(id, name, desc, color, icon, isPrivate) {
            document.getElementById('ch-edit-cat-id').value = id;
            document.getElementById('ch-edit-cat-name').value = name;
            document.getElementById('ch-edit-cat-desc').value = desc;
            document.getElementById('ch-edit-cat-color').value = color.toUpperCase();
            const wheel = document.getElementById('ch-edit-cat-color-wheel');
            if (wheel) wheel.value = color;
            const prev = document.getElementById('ch-edit-cat-color-preview');
            if (prev) { prev.style.background = color; prev.style.opacity = '1'; }
            const privRadio = document.getElementById('ch-edit-cat-vis-private');
            const pubRadio  = document.getElementById('ch-edit-cat-vis-public');
            if (privRadio && pubRadio) {
                privRadio.checked = !!isPrivate;
                pubRadio.checked  = !isPrivate;
            }
            chOpenModal('ch-modal-edit-cat');
        };

        window.chSaveCategory = function(nonce) {
            const name = document.getElementById('ch-cat-name').value.trim();
            if (!name) { alert('Please enter a category name'); return; }

            const btn = document.getElementById('ch-save-cat-btn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Creating...</span>';

            const fd = new FormData();
            fd.append('action', 'ch_create_category');
            fd.append('name', name);
            fd.append('description', document.getElementById('ch-cat-desc').value);
            fd.append('color', document.getElementById('ch-cat-color').value);
            fd.append('sort_order', 0);
            fd.append('is_private', document.getElementById('ch-cat-vis-private')?.checked ? 1 : 0);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) chReloadAfterSuccess();
                else { btn.disabled = false; btn.textContent = 'Create Category'; }
            })
            .catch(() => {
                document.getElementById('ch-cat-msg').innerHTML = '<div class="bntm-notice bntm-notice-error">Network error. Please try again.</div>';
                btn.disabled = false; btn.textContent = 'Create Category';
            });
        };

        window.chUpdateCategory = function(nonce) {
            const id   = document.getElementById('ch-edit-cat-id').value;
            const name = document.getElementById('ch-edit-cat-name').value.trim();
            if (!name) { alert('Please enter a category name'); return; }

            const btn = document.getElementById('ch-update-cat-btn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving...</span>';

            const fd = new FormData();
            fd.append('action', 'ch_edit_category');
            fd.append('category_id', id);
            fd.append('name', name);
            fd.append('description', document.getElementById('ch-edit-cat-desc').value);
            fd.append('color', document.getElementById('ch-edit-cat-color').value);
            fd.append('is_private', document.getElementById('ch-edit-cat-vis-private')?.checked ? 1 : 0);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-edit-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) chReloadAfterSuccess();
                else { btn.disabled = false; btn.textContent = 'Save Changes'; }
            })
            .catch(() => {
                document.getElementById('ch-edit-cat-msg').innerHTML = '<div class="bntm-notice bntm-notice-error">Network error. Please try again.</div>';
                btn.disabled = false; btn.textContent = 'Save Changes';
            });
        };

        window.chDeleteCategory = function(id, name, triggerBtn) {
            if (!confirm('Delete category "'+name+'"? This cannot be undone.')) return;
            if (triggerBtn) { triggerBtn.disabled = true; }
            const fd = new FormData();
            fd.append('action', 'ch_delete_category');
            fd.append('category_id', id);
            fd.append('nonce', '<?php echo esc_attr($nonce); ?>');

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) chReloadAfterSuccess(0);
                else {
                    if (triggerBtn) triggerBtn.disabled = false;
                    alert(json.data?.message || 'Failed to delete.');
                }
            })
            .catch(() => {
                if (triggerBtn) triggerBtn.disabled = false;
                alert('Network error.');
            });
        };

        window.chToggleCategoryStatus = function(id, currentStatus, btn) {
            const newLabel = currentStatus === 'active' ? 'Archiving...' : 'Unarchiving...';
            const originalTitle = btn.title;
            btn.title = newLabel;
            btn.disabled = true;

            const fd = new FormData();
            fd.append('action', 'ch_toggle_category_status');
            fd.append('category_id', id);
            fd.append('nonce', '<?php echo esc_attr($nonce); ?>');

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                btn.disabled = false;
                btn.title = originalTitle;
                if (json.success) {
                    const badge = btn.closest('.ch-cat-card').querySelector('.ch-status-badge');
                    if (badge) {
                        badge.textContent = json.data.status.charAt(0).toUpperCase() + json.data.status.slice(1);
                        badge.classList.toggle('ch-status-active', json.data.status === 'active');
                        badge.classList.toggle('ch-status-archived', json.data.status !== 'active');
                    }
                    // Reload so list respects filters
                    chReloadAfterSuccess(400);
                } else {
                    alert(json.data?.message || 'Failed to update status.');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.title = originalTitle;
                alert('Network error.');
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: POSTS
// ============================================================

function ch_posts_tab($user_id, $is_admin) {
    global $wpdb;

    $page     = max(1, (int) ($_GET['paged'] ?? 1));
    $per_page = 20;
    $offset   = ($page - 1) * $per_page;
    $filter   = sanitize_text_field($_GET['filter'] ?? 'all');
    $search   = sanitize_text_field($_GET['s'] ?? '');
    $cat_id   = (int) ($_GET['cat'] ?? 0);

    $where = "WHERE p.status != 'removed'";
    if ($filter === 'pinned')  $where .= " AND p.is_pinned = 1";
    if ($filter === 'pending') $where .= " AND p.status = 'pending'";
    if ($filter === 'removed') $where .= " AND p.status = 'removed'";
    if ($cat_id)               $where .= $wpdb->prepare(" AND p.category_id = %d", $cat_id);
    if ($search)               $where .= $wpdb->prepare(" AND (p.title LIKE %s OR p.content LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');

    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts p $where");
    $posts = $wpdb->get_results(
        "SELECT p.*, c.name as cat_name, c.color as cat_color,
                u.display_name as author_name
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
         $where
         ORDER BY p.is_pinned DESC, p.created_at DESC
         LIMIT {$per_page} OFFSET {$offset}"
    );

    $categories = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}ch_categories WHERE status='active' ORDER BY name ASC");
    $nonce = wp_create_nonce('ch_post_nonce');
    $total_pages = ceil($total / $per_page);

    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>Posts Management</h1>
            <p>Monitor and manage all community posts</p>
        </div>
    </div>

    <div class="ch-toolbar">
        <div class="ch-toolbar-filters">
            <a href="?tab=posts&filter=all"     class="ch-filter-btn <?php echo $filter==='all'    ?'active':''; ?>">All</a>
            <a href="?tab=posts&filter=pending" class="ch-filter-btn <?php echo $filter==='pending'?'active':''; ?>">Pending</a>
            <a href="?tab=posts&filter=pinned"  class="ch-filter-btn <?php echo $filter==='pinned' ?'active':''; ?>">Pinned</a>
            <a href="?tab=posts&filter=removed" class="ch-filter-btn <?php echo $filter==='removed'?'active':''; ?>">Removed</a>
        </div>
        <div class="ch-toolbar-right">
            <form method="get" class="ch-search-form">
                <input type="hidden" name="tab" value="posts">
                <select name="cat" class="ch-input ch-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat->id; ?>" <?php selected($cat_id, $cat->id); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search posts..." class="ch-input ch-search-input">
                <button type="submit" class="ch-btn ch-btn-secondary">Search</button>
            </form>
        </div>
    </div>

    <div class="ch-card">
        <div class="bntm-table-wrapper">
            <table class="bntm-table ch-table">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Stats</th>
                        <th>Status</th>
                        <th>Date</th>
                        <?php if ($is_admin): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                    <tr><td colspan="7" class="ch-table-empty">No posts found.</td></tr>
                    <?php else: foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <div class="ch-post-cell">
                                <?php if ($post->is_pinned): ?>
                                <span class="ch-pin-badge">
                                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                    Pinned
                                </span>
                                <?php endif; ?>
                                <strong><?php echo esc_html(wp_trim_words($post->title, 10)); ?></strong>
                                <p class="ch-post-excerpt"><?php echo esc_html(wp_trim_words(strip_tags($post->content), 15)); ?></p>
                            </div>
                        </td>
                        <td><span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>"><?php echo esc_html($post->cat_name ?? '—'); ?></span></td>
                        <td><?php echo $post->is_anonymous ? '<em>Anonymous</em>' : esc_html($post->author_name ?? 'Unknown'); ?></td>
                        <td>
                            <span class="ch-mini-stat">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="13" rx="2"/></svg>
                                <?php echo (int)$post->vote_count; ?>
                            </span>
                            <span class="ch-mini-stat">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <?php echo (int)$post->comment_count; ?>
                            </span>
                        </td>
                        <td><span class="ch-status-badge ch-status-<?php echo $post->status; ?>"><?php echo ucfirst($post->status); ?></span></td>
                        <td><span class="ch-date"><?php echo date('M d, Y', strtotime($post->created_at)); ?></span></td>
                        <?php if ($is_admin): ?>
                        <td>
                            <div class="ch-actions-row">
                                <?php if ($post->status === 'pending'): ?>
                                <button class="ch-btn-xs ch-btn-success" title="Approve"
                                    onclick="chModeratePost(<?php echo (int)$post->id; ?>, 'approve_post', '<?php echo esc_attr($nonce); ?>', this)">Approve</button>
                                <button class="ch-btn-xs ch-btn-danger" title="Reject"
                                    onclick="chModeratePost(<?php echo (int)$post->id; ?>, 'reject_post', '<?php echo esc_attr($nonce); ?>', this)">Reject</button>
                                <?php else: ?>
                                <button class="ch-icon-btn" title="<?php echo $post->is_pinned ? 'Unpin' : 'Pin'; ?>"
                                    onclick="chPinPost(<?php echo (int)$post->id; ?>, <?php echo $post->is_pinned ? 0 : 1; ?>, '<?php echo esc_attr($nonce); ?>', this)">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                </button>
                                <?php if ($post->status !== 'removed'): ?>
                                <button class="ch-icon-btn ch-icon-btn-danger" title="Remove"
                                    onclick="chModeratePost(<?php echo (int)$post->id; ?>, 'remove_post', '<?php echo esc_attr($nonce); ?>', this)">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                                <?php else: ?>
                                <button class="ch-icon-btn" title="Restore"
                                    onclick="chModeratePost(<?php echo (int)$post->id; ?>, 'restore_post', '<?php echo esc_attr($nonce); ?>', this)">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.37"/></svg>
                                </button>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="ch-pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?tab=posts&paged=<?php echo $i; ?>&filter=<?php echo $filter; ?>&s=<?php echo urlencode($search); ?>"
               class="ch-page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    (function() {
        window.chPinPost = function(id, pinVal, nonce, btn) {
            if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
            const fd = new FormData();
            fd.append('action', 'ch_pin_post');
            fd.append('post_id', id);
            fd.append('pin', pinVal);
            fd.append('nonce', nonce);
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) chReloadAfterSuccess(0);
                else {
                    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                    alert(json.data?.message);
                }
            })
            .catch(() => {
                if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                alert('Network error.');
            });
        };

        window.chModeratePost = function(id, action, nonce, btn) {
            const label = action === 'remove_post' ? 'remove' : 'restore';
            if (!confirm('Are you sure you want to '+label+' this post?')) return;
            const sendModeration = function(reasonText) {
                if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
                const fd = new FormData();
                fd.append('action', 'ch_moderate_action');
                fd.append('mod_action', action);
                fd.append('target_id', id);
                fd.append('nonce', nonce);
                if (reasonText) fd.append('reason', reasonText);
                fetch(ajaxurl, {method:'POST', body:fd})
                .then(r => r.json())
                .then(json => {
                    if (json.success) chReloadAfterSuccess(0);
                    else {
                        if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                        alert(json.data?.message);
                    }
                })
                .catch(() => {
                    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                    alert('Network error.');
                });
            };
            if (action === 'remove_post') {
                if (typeof chPromptModerationReason === 'function') {
                    chPromptModerationReason('Remove Post', 'Please provide a reason for removing this post...', sendModeration);
                } else {
                    const reason = prompt('Reason for removing this post:');
                    if (reason === null || !reason.trim()) return;
                    sendModeration(reason.trim());
                }
                return;
            }
            sendModeration('');
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: USERS
// ============================================================

function ch_users_tab($user_id, $is_admin) {
    global $wpdb;

    $page     = max(1, (int) ($_GET['paged'] ?? 1));
    $per_page = 20;
    $offset   = ($page - 1) * $per_page;
    $search   = sanitize_text_field($_GET['s'] ?? '');
    $status   = sanitize_text_field($_GET['status'] ?? 'all');

    $where = "WHERE 1=1";
    if ($status !== 'all') $where .= $wpdb->prepare(" AND p.status = %s", $status);
    if ($search)           $where .= $wpdb->prepare(" AND (p.display_name LIKE %s OR u.user_email LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');

    $total = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ch_user_profiles p
         LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID $where"
    );

    $users = $wpdb->get_results(
        "SELECT p.*, u.user_email, u.user_registered
         FROM {$wpdb->prefix}ch_user_profiles p
         LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
         $where
         ORDER BY p.created_at DESC
         LIMIT {$per_page} OFFSET {$offset}"
    );

    $nonce = wp_create_nonce('ch_moderate_nonce');
    $total_pages = ceil($total / $per_page);

    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>User Management</h1>
            <p>View and manage community members</p>
        </div>
    </div>

    <div class="ch-toolbar">
        <div class="ch-toolbar-filters">
            <a href="?tab=users&status=all"       class="ch-filter-btn <?php echo $status==='all'       ?'active':''; ?>">All</a>
            <a href="?tab=users&status=active"    class="ch-filter-btn <?php echo $status==='active'    ?'active':''; ?>">Active</a>
            <a href="?tab=users&status=suspended" class="ch-filter-btn <?php echo $status==='suspended' ?'active':''; ?>">Suspended</a>
            <a href="?tab=users&status=banned"    class="ch-filter-btn <?php echo $status==='banned'    ?'active':''; ?>">Banned</a>
        </div>
        <form method="get" class="ch-search-form">
            <input type="hidden" name="tab" value="users">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search users..." class="ch-input ch-search-input">
            <button type="submit" class="ch-btn ch-btn-secondary">Search</button>
        </form>
    </div>

    <div class="ch-card">
        <div class="bntm-table-wrapper">
            <table class="bntm-table ch-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Posts</th>
                        <th>Comments</th>
                        <th>Karma</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <?php if ($is_admin): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="ch-table-empty">No users found.</td></tr>
                    <?php else: foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="ch-user-cell">
                                <div class="ch-avatar-sm"><?php echo strtoupper(substr($u->display_name ?: 'U', 0, 1)); ?></div>
                                <span><?php echo esc_html($u->display_name ?: 'User #'.$u->user_id); ?></span>
                                <?php if ($u->is_anonymous): ?>
                                <span class="ch-anon-badge">Anon</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo esc_html($u->user_email); ?></td>
                        <td><?php echo number_format($u->post_count); ?></td>
                        <td><?php echo number_format($u->comment_count); ?></td>
                        <td><strong><?php echo number_format($u->karma_points); ?></strong></td>
                        <td><span class="ch-status-badge ch-status-<?php echo $u->status; ?>"><?php echo ucfirst($u->status); ?></span></td>
                        <td><span class="ch-date"><?php echo date('M d, Y', strtotime($u->user_registered ?? $u->created_at)); ?></span></td>
                        <?php if ($is_admin && $u->user_id != $user_id): ?>
                        <td>
                            <div class="ch-actions-row">
                                <a href="<?php
                                    $feed_pg  = get_page_by_path('forum-feed');
                                    $feed_base = $feed_pg ? get_permalink($feed_pg) : home_url('/forum-feed/');
                                    $feed_pg_vd  = get_page_by_path('forum-feed');
                                    $feed_base_vd = $feed_pg_vd ? get_permalink($feed_pg_vd) : home_url('/forum-feed/');
                                    echo esc_url(add_query_arg(['tab' => 'user_profile', 'uid' => (int)$u->user_id], $feed_base_vd));
                                ?>" target="_blank" class="ch-btn-xs ch-btn-secondary">View Dashboard</a>
                                <?php if ($u->status === 'active'): ?>
                                <button class="ch-btn-xs ch-btn-warning" onclick="chModerateUser(<?php echo (int)$u->user_id; ?>, 'suspend_user', '<?php echo esc_attr($nonce); ?>')">Suspend</button>
                                <button class="ch-btn-xs ch-btn-danger"  onclick="chModerateUser(<?php echo (int)$u->user_id; ?>, 'ban_user', '<?php echo esc_attr($nonce); ?>')">Ban</button>
                                <?php else: ?>
                                <button class="ch-btn-xs ch-btn-success" onclick="chModerateUser(<?php echo (int)$u->user_id; ?>, 'unsuspend_user', '<?php echo esc_attr($nonce); ?>')">Restore</button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php elseif ($is_admin): ?>
                        <td><em style="color:#9ca3af;font-size:12px">You</em></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="ch-pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?tab=users&paged=<?php echo $i; ?>&status=<?php echo $status; ?>&s=<?php echo urlencode($search); ?>"
               class="ch-page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    (function() {
        window.chModerateUser = function(uid, action, nonce) {
            const labels = {suspend_user:'suspend', ban_user:'ban', unsuspend_user:'restore'};
            const reason = prompt('Please provide a reason for this action:');
            if (reason === null) return; // User cancelled
            if (!reason.trim()) {
                alert('A reason is required for this action.');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'ch_moderate_action');
            fd.append('mod_action', action);
            fd.append('target_id', uid);
            fd.append('reason', reason.trim());
            fd.append('nonce', nonce);
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => { if (json.success) chReloadAfterSuccess(0); else alert(json.data?.message); })
            .catch(() => alert('Network error.'));
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: REPORTS
// ============================================================

function ch_reports_tab($user_id, $is_admin) {
    global $wpdb;

    $status = sanitize_text_field($_GET['rstatus'] ?? 'pending');

    $reports = $wpdb->get_results($wpdb->prepare(
        "SELECT r.*,
                reporter.display_name as reporter_name,
                reviewer.display_name as reviewer_name
         FROM {$wpdb->prefix}ch_reports r
         LEFT JOIN {$wpdb->prefix}ch_user_profiles reporter ON r.reporter_id = reporter.user_id
         LEFT JOIN {$wpdb->prefix}ch_user_profiles reviewer ON r.reviewed_by = reviewer.user_id
         WHERE r.status = %s
         ORDER BY r.created_at DESC",
        $status
    ));

    $nonce     = wp_create_nonce('ch_report_nonce');
    $feed_page = get_page_by_path('forum-feed');
    $feed_url  = $feed_page ? get_permalink($feed_page) : home_url('/forum-feed/');

    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>Reports</h1>
            <p>Review and manage reported content and users</p>
        </div>
    </div>

    <div class="ch-toolbar">
        <div class="ch-toolbar-filters">
            <a href="?tab=reports&rstatus=pending"   class="ch-filter-btn <?php echo $status==='pending'   ?'active':''; ?>">Pending</a>
            <a href="?tab=reports&rstatus=reviewed"  class="ch-filter-btn <?php echo $status==='reviewed'  ?'active':''; ?>">Reviewed</a>
            <a href="?tab=reports&rstatus=resolved"  class="ch-filter-btn <?php echo $status==='resolved'  ?'active':''; ?>">Resolved</a>
            <a href="?tab=reports&rstatus=dismissed" class="ch-filter-btn <?php echo $status==='dismissed' ?'active':''; ?>">Dismissed</a>
        </div>
    </div>

    <div class="ch-card">
        <div class="bntm-table-wrapper">
            <table class="bntm-table ch-table">
                <thead>
                    <tr>
                        <th>Target</th>
                        <th>Reason</th>
                        <th>Reporter</th>
                        <th>Date</th>
                        <th>Status</th>
                        <?php if ($is_admin): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                    <tr><td colspan="6" class="ch-table-empty">No <?php echo esc_html($status); ?> reports found.</td></tr>
                    <?php else: foreach ($reports as $r):
                        $target_link = '';
                        if ($r->target_type === 'post') {
                            $target_post = $wpdb->get_row($wpdb->prepare("SELECT rand_id FROM {$wpdb->prefix}ch_posts WHERE id = %d", $r->target_id));
                            if ($target_post) {
                                $target_link = add_query_arg('view_post', $target_post->rand_id, $feed_url);
                            }
                        } elseif ($r->target_type === 'comment') {
                            $target_comment = $wpdb->get_row($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}ch_comments WHERE id = %d", $r->target_id));
                            if ($target_comment) {
                                $target_post = $wpdb->get_row($wpdb->prepare("SELECT rand_id FROM {$wpdb->prefix}ch_posts WHERE id = %d", $target_comment->post_id));
                                if ($target_post) {
                                    $target_link = add_query_arg('view_post', $target_post->rand_id, $feed_url);
                                }
                            }
                        }
                    ?>
                    <tr id="ch-report-row-<?php echo $r->id; ?>">
                        <td>
                            <div style="display:flex;flex-direction:column;gap:4px;">
                                <span class="ch-type-badge ch-type-<?php echo esc_attr($r->target_type); ?>">
                                    <?php echo ucfirst($r->target_type); ?> #<?php echo $r->target_id; ?>
                                </span>
                                <?php if ($r->details): ?>
                                <span style="font-size:12px;color:var(--ch-text-muted);max-width:220px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo esc_attr($r->details); ?>">
                                    <?php echo esc_html(wp_trim_words($r->details, 10)); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($target_link): ?>
                                <a href="<?php echo esc_url($target_link); ?>" target="_blank" class="ch-link-btn" style="font-size:12px;">View post</a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <strong style="font-size:13px;"><?php echo esc_html(ucfirst($r->reason)); ?></strong>
                        </td>
                        <td>
                            <span style="font-size:13px;"><?php echo esc_html($r->reporter_name ?? 'Unknown'); ?></span>
                        </td>
                        <td><span class="ch-date"><?php echo date('M d, Y', strtotime($r->created_at)); ?></span></td>
                        <td>
                            <span class="ch-status-badge ch-status-<?php echo esc_attr($r->status); ?>">
                                <?php echo ucfirst($r->status); ?>
                            </span>
                            <?php if ($r->reviewer_name): ?>
                            <div style="font-size:11px;color:#9ca3af;margin-top:2px;">by <?php echo esc_html($r->reviewer_name); ?></div>
                            <?php endif; ?>
                        </td>
                        <?php if ($is_admin): ?>
                        <td>
                            <div class="ch-actions-row" style="flex-wrap:wrap;gap:6px;">
                                <?php if ($r->status === 'pending'): ?>
                                    <button class="ch-btn-xs ch-btn-success"
                                            onclick="chResolveReport(<?php echo $r->id; ?>, 'resolved', '<?php echo esc_attr($nonce); ?>', this)">
                                        ✓ Resolve
                                    </button>
                                    <button class="ch-btn-xs ch-btn-secondary"
                                            onclick="chResolveReport(<?php echo $r->id; ?>, 'dismissed', '<?php echo esc_attr($nonce); ?>', this)">
                                        Dismiss
                                    </button>
                                    <?php if ($r->target_type === 'post'): ?>
                                    <button class="ch-btn-xs ch-btn-danger"
                                            onclick="chResolveAndRemove(<?php echo $r->id; ?>, <?php echo $r->target_id; ?>, 'post', '<?php echo esc_attr($nonce); ?>', this)">
                                        Remove Post
                                    </button>
                                    <?php elseif ($r->target_type === 'comment'): ?>
                                    <button class="ch-btn-xs ch-btn-danger"
                                            onclick="chResolveAndRemove(<?php echo $r->id; ?>, <?php echo $r->target_id; ?>, 'comment', '<?php echo esc_attr($nonce); ?>', this)">
                                        Remove Comment
                                    </button>
                                    <?php elseif ($r->target_type === 'user'): ?>
                                    <button class="ch-btn-xs ch-btn-warning"
                                            onclick="chResolveAndSuspend(<?php echo $r->id; ?>, <?php echo $r->target_id; ?>, '<?php echo esc_attr($nonce); ?>', this)">
                                        Suspend User
                                    </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="font-size:12px;color:#9ca3af;">No actions</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    (function() {
        function doPost(fd, rowId, btn) {
            if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const row = document.getElementById('ch-report-row-' + rowId);
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transition = 'opacity 0.3s ease';
                    }
                    chReloadAfterSuccess(300);
                } else {
                    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                    alert(json.data?.message || 'Action failed. Please try again.');
                }
            })
            .catch(() => {
                if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                alert('Network error. Please try again.');
            });
        }

        window.chResolveReport = function(reportId, resolution, nonce, btn) {
            const label = resolution === 'resolved' ? 'resolve' : 'dismiss';
            if (!confirm('Are you sure you want to ' + label + ' this report?')) return;
            const fd = new FormData();
            fd.append('action',      'ch_moderate_action');
            fd.append('mod_action',  'resolve_report');
            fd.append('target_id',   reportId);
            fd.append('resolution',  resolution);
            fd.append('nonce',       nonce);
            doPost(fd, reportId, btn);
        };

        window.chResolveAndRemove = function(reportId, targetId, targetType, nonce, btn) {
            const label = targetType === 'post' ? 'post' : 'comment';
            if (!confirm('Remove this ' + label + ' AND resolve the report?')) return;
            const doRemoveAndResolve = function(reasonText) {
                if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
                const modAction = targetType === 'post' ? 'remove_post' : 'remove_comment';
                // Step 1: remove content
                const fd1 = new FormData();
                fd1.append('action',     'ch_moderate_action');
                fd1.append('mod_action', modAction);
                fd1.append('target_id',  targetId);
                fd1.append('nonce',      nonce);
                if (reasonText) fd1.append('reason', reasonText);
                fetch(ajaxurl, {method:'POST', body:fd1})
                .then(r => r.json())
                .then(() => {
                    // Step 2: resolve the report
                    const fd2 = new FormData();
                    fd2.append('action',     'ch_moderate_action');
                    fd2.append('mod_action', 'resolve_report');
                    fd2.append('target_id',  reportId);
                    fd2.append('resolution', 'resolved');
                    fd2.append('nonce',      nonce);
                    doPost(fd2, reportId, btn);
                })
                .catch(() => {
                    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                    alert('Network error.');
                });
            };
            if (targetType === 'post') {
                if (typeof chPromptModerationReason === 'function') {
                    chPromptModerationReason('Remove Reported Post', 'Please provide a reason for removal...', doRemoveAndResolve);
                } else {
                    const reason = prompt('Reason for removing this post:');
                    if (reason === null || !reason.trim()) return;
                    doRemoveAndResolve(reason.trim());
                }
                return;
            }
            doRemoveAndResolve('');
        };

        window.chResolveAndSuspend = function(reportId, targetUserId, nonce, btn) {
            if (!confirm('Suspend this user AND resolve the report?')) return;
            if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
            // Step 1: suspend user
            const fd1 = new FormData();
            fd1.append('action',     'ch_moderate_action');
            fd1.append('mod_action', 'suspend_user');
            fd1.append('target_id',  targetUserId);
            fd1.append('nonce',      nonce);
            fetch(ajaxurl, {method:'POST', body:fd1})
            .then(r => r.json())
            .then(() => {
                // Step 2: resolve report
                const fd2 = new FormData();
                fd2.append('action',     'ch_moderate_action');
                fd2.append('mod_action', 'resolve_report');
                fd2.append('target_id',  reportId);
                fd2.append('resolution', 'resolved');
                fd2.append('nonce',      nonce);
                doPost(fd2, reportId, btn);
            })
            .catch(() => {
                if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                alert('Network error.');
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: MODERATION
// ============================================================

function ch_moderation_tab($user_id) {
    global $wpdb;

    // Handle settings save
    if (isset($_POST['ch_save_moderation_settings']) && current_user_can('manage_options')) {
        if (!wp_verify_nonce($_POST['ch_moderation_settings_nonce'], 'ch_moderation_settings_nonce')) {
            wp_die('Security check failed');
        }
        $hide_threshold = max(1, min(50, (int)($_POST['ch_report_auto_hide_threshold'] ?? 5)));
        update_option('ch_report_auto_hide_threshold', $hide_threshold);
        $approval = isset($_POST['ch_post_approval_enabled']) ? 1 : 0;
        update_option('ch_post_approval_enabled', $approval);
        $suspend_threshold = max(1, min(100, (int)($_POST['ch_auto_suspend_threshold'] ?? 10)));
        update_option('ch_auto_suspend_threshold', $suspend_threshold);
        $cat_karma = max(0, min(10000, (int)($_POST['ch_category_creation_karma'] ?? 100)));
        update_option('ch_category_creation_karma', $cat_karma);
        $cat_creation_enabled = isset($_POST['ch_user_category_creation']) ? 1 : 0;
        update_option('ch_user_category_creation', $cat_creation_enabled);
        $guidelines = wp_kses_post($_POST['ch_community_guidelines'] ?? '');
        if ($guidelines !== '') {
            update_option('ch_community_guidelines', $guidelines);
        }
        echo '<div class="bntm-notice bntm-notice-success">Moderation settings saved successfully!</div>';
    }

    $stats = [
        'active_posts'      => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE status='active'"),
        'removed_posts'     => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE status='removed'"),
        'pending_reports'   => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_reports WHERE status='pending'"),
        'suspended_users'   => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_user_profiles WHERE status='suspended'"),
        'banned_users'      => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_user_profiles WHERE status='banned'"),
        'total_votes'       => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_votes"),
    ];

    $recent_removed = $wpdb->get_results(
        "SELECT p.*, u.display_name as author_name
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
         WHERE p.status = 'removed'
         ORDER BY p.updated_at DESC
         LIMIT 10"
    );

    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>Moderation Panel</h1>
            <p>Platform health and enforcement actions</p>
        </div>
    </div>

    <div class="ch-stats-grid">
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background:linear-gradient(135deg,#10b981,#059669)">
                <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num"><?php echo $stats['active_posts']; ?></span>
                <span class="ch-stat-label">Active Posts</span>
            </div>
        </div>
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
                <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num"><?php echo $stats['removed_posts']; ?></span>
                <span class="ch-stat-label">Removed Posts</span>
            </div>
        </div>
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num"><?php echo $stats['pending_reports']; ?></span>
                <span class="ch-stat-label">Pending Reports</span>
            </div>
        </div>
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background:linear-gradient(135deg,#FF7551,#FF9A7F)">
                <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num"><?php echo $stats['suspended_users'] + $stats['banned_users']; ?></span>
                <span class="ch-stat-label">Restricted Users</span>
            </div>
        </div>
    </div>

    <div class="ch-card" style="margin-bottom:20px;">
        <div class="ch-card-header">
            <h3>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="margin-right:6px;vertical-align:-2px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Moderation Settings
            </h3>
        </div>
        <div class="ch-card-body" style="padding:24px;">
            <form method="post">
                <?php wp_nonce_field('ch_moderation_settings_nonce', 'ch_moderation_settings_nonce'); ?>
                <div class="ch-settings-grid">

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Auto-hide threshold</div>
                            <div class="ch-setting-desc">Posts with this many reports or more are automatically hidden from the feed.</div>
                        </div>
                        <div class="ch-setting-control">
                            <input type="number" name="ch_report_auto_hide_threshold"
                                   value="<?php echo esc_attr(get_option('ch_report_auto_hide_threshold', 5)); ?>"
                                   min="1" max="50" class="ch-input ch-setting-number">
                            <span class="ch-setting-unit">reports</span>
                        </div>
                    </div>

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Auto-suspend threshold</div>
                            <div class="ch-setting-desc">Users accumulating this many reports across their content are automatically suspended.</div>
                        </div>
                        <div class="ch-setting-control">
                            <input type="number" name="ch_auto_suspend_threshold"
                                   value="<?php echo esc_attr(get_option('ch_auto_suspend_threshold', 10)); ?>"
                                   min="1" max="100" class="ch-input ch-setting-number">
                            <span class="ch-setting-unit">reports</span>
                        </div>
                    </div>

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Require post approval</div>
                            <div class="ch-setting-desc">All new posts must be reviewed and approved by an admin before appearing in the feed.</div>
                        </div>
                        <div class="ch-setting-control">
                            <label class="ch-toggle">
                                <input type="checkbox" name="ch_post_approval_enabled" value="1"
                                       <?php checked(get_option('ch_post_approval_enabled', 0), 1); ?>>
                                <span class="ch-toggle-track">
                                    <span class="ch-toggle-thumb"></span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Allow users to create categories</div>
                            <div class="ch-setting-desc">Let community members create their own topics and categories once they reach the karma threshold below.</div>
                        </div>
                        <div class="ch-setting-control">
                            <label class="ch-toggle">
                                <input type="checkbox" name="ch_user_category_creation" value="1"
                                       <?php checked(get_option('ch_user_category_creation', 0), 1); ?>>
                                <span class="ch-toggle-track">
                                    <span class="ch-toggle-thumb"></span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Category creation karma threshold</div>
                            <div class="ch-setting-desc">Minimum karma points a user must have before they can create a category (when the setting above is enabled).</div>
                        </div>
                        <div class="ch-setting-control">
                            <input type="number" name="ch_category_creation_karma"
                                   value="<?php echo esc_attr(get_option('ch_category_creation_karma', 100)); ?>"
                                   min="0" max="10000" class="ch-input ch-setting-number">
                            <span class="ch-setting-unit">karma</span>
                        </div>
                    </div>

                </div>
                <div style="margin-top:20px; padding-top:20px; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end;">
                    <button type="submit" name="ch_save_moderation_settings" class="ch-btn ch-btn-primary">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="ch-card" style="margin-bottom:20px;">
        <div class="ch-card-header">
            <h3>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="margin-right:6px;vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Community Guidelines
            </h3>
            <p style="font-size:13px;color:var(--ch-text-muted);margin:4px 0 0;">Displayed to all users in the guidelines modal and on the registration page. Supports basic HTML tags.</p>
        </div>
        <div class="ch-card-body" style="padding:24px;">
            <form method="post">
                <?php wp_nonce_field('ch_moderation_settings_nonce', 'ch_moderation_settings_nonce'); ?>
                <div class="ch-field-group">
                    <label class="ch-label">Guidelines Content</label>
                    <textarea name="ch_community_guidelines"
                              class="ch-input ch-textarea"
                              rows="12"
                              style="font-family:monospace;font-size:13px;"
                              placeholder="Enter your community guidelines here. Supports HTML tags like &lt;h4&gt;, &lt;h5&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;."><?php echo esc_textarea(get_option('ch_community_guidelines', '')); ?></textarea>
                    <div style="margin-top:8px;font-size:12px;color:#9ca3af;">
                        Leave blank to use the default guidelines. Supported tags: h4, h5, p, ul, ol, li, strong, em, a.
                    </div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                    <button type="button" class="ch-btn ch-btn-secondary ch-btn-sm"
                            onclick="document.querySelector('[name=ch_community_guidelines]').value='';this.textContent='Cleared — save to reset to default';">
                        Reset to Default
                    </button>
                    <button type="submit" name="ch_save_moderation_settings" class="ch-btn ch-btn-primary">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Save Guidelines
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="ch-card">
        <div class="ch-card-header">
            <h3>Recently Removed Posts</h3>
        </div>
        <div class="ch-card-body">
            <?php if (empty($recent_removed)): ?>
                <p class="ch-empty">No removed posts.</p>
            <?php else: foreach ($recent_removed as $post): ?>
            <div class="ch-list-item">
                <div class="ch-list-item-main">
                    <strong><?php echo esc_html($post->title); ?></strong>
                    <p class="ch-list-meta">by <?php echo esc_html($post->author_name ?? 'Unknown'); ?> &bull; removed <?php echo human_time_diff(strtotime($post->updated_at)); ?> ago</p>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: ACTIVITY LOG
// ============================================================

function ch_activity_tab($user_id) {
    global $wpdb;

    $logs = $wpdb->get_results(
        "SELECT l.*, u.display_name as admin_name
         FROM {$wpdb->prefix}ch_activity_logs l
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON l.admin_id = u.user_id
         ORDER BY l.created_at DESC
         LIMIT 50"
    );

    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>Activity Log</h1>
            <p>System and moderation actions history</p>
        </div>
    </div>

    <div class="ch-card">
        <div class="bntm-table-wrapper">
            <table class="bntm-table ch-table">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Details</th>
                        <th>IP</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="ch-table-empty">No activity logged yet.</td></tr>
                    <?php else: foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log->admin_name ?? 'System'); ?></td>
                        <td><code class="ch-action-code"><?php echo esc_html($log->action); ?></code></td>
                        <td><?php echo $log->target_type ? esc_html(ucfirst($log->target_type).' #'.$log->target_id) : '—'; ?></td>
                        <td><?php echo esc_html(wp_trim_words($log->details ?? '', 10)); ?></td>
                        <td><span class="ch-date"><?php echo esc_html($log->ip_address ?? '—'); ?></span></td>
                        <td><span class="ch-date"><?php echo date('M d, Y H:i', strtotime($log->created_at)); ?></span></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function ch_profile_tab($user_id) {
    global $wpdb;

    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id));
    $wp_user = get_userdata($user_id);

    if (!$profile) {
        $profile = (object) [
            'display_name' => $wp_user->display_name ?? '',
            'bio' => '',
            'location' => '',
            'avatar_url' => '',
            'is_anonymous' => 0,
            'karma_points' => 0,
            'post_count' => 0,
            'comment_count' => 0,
            'status' => 'active'
        ];
    }

    ob_start(); ?>
    <div class="ch-page-header">
        <h1>My Profile</h1>
        <p>Manage your community profile and settings</p>
    </div>

    <div class="ch-two-col">
        <div class="ch-card">
            <div class="ch-card-header">
                <h3>Profile Information</h3>
            </div>
            <div class="ch-card-body">
                <form id="ch-profile-form" enctype="multipart/form-data">
                    <div class="ch-field-group">
                        <label class="ch-label">Display Name</label>
                        <input type="text" id="ch-profile-display-name" class="ch-input" value="<?php echo esc_attr($profile->display_name); ?>" placeholder="Your display name">
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-label">Bio</label>
                        <textarea id="ch-profile-bio" class="ch-input ch-textarea" rows="4" placeholder="Tell us about yourself..."><?php echo esc_textarea($profile->bio); ?></textarea>
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-label">Location</label>
                        <input type="text" id="ch-profile-location" class="ch-input" value="<?php echo esc_attr($profile->location); ?>" placeholder="Your location">
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-label">Avatar</label>
                        <div style="display:flex;align-items:center;gap:14px;margin-bottom:6px;">
                            <div id="ch-avatar-preview-wrap" style="width:64px;height:64px;border-radius:50%;overflow:hidden;border:2px solid var(--ch-border);flex-shrink:0;background:var(--ch-accent-light);display:flex;align-items:center;justify-content:center;">
                                <?php if ($profile->avatar_url): ?>
                                <img id="ch-avatar-preview-img" src="<?php echo esc_url($profile->avatar_url); ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                <span id="ch-avatar-preview-initials" style="font-size:22px;font-weight:700;color:var(--ch-accent);"><?php echo esc_html(strtoupper(substr($profile->display_name ?: 'U', 0, 1))); ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <input type="file" id="ch-profile-avatar" class="ch-input" accept="image/*" style="margin-bottom:4px;" onchange="chPreviewAvatar(this)">
                                <div style="font-size:11px;color:var(--ch-text-subtle);">JPG, PNG or GIF · Max 5MB · Will be resized to 400×400</div>
                            </div>
                        </div>
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-checkbox-label">
                            <input type="checkbox" id="ch-profile-anonymous" <?php checked($profile->is_anonymous, 1); ?>>
                            Post anonymously by default
                        </label>
                    </div>
                    <button type="button" class="ch-btn ch-btn-primary" onclick="chUpdateProfile('<?php echo wp_create_nonce('ch_profile_nonce'); ?>')">Save Profile</button>
                </form>
                <div id="ch-profile-msg"></div>
            </div>
        </div>

        <div class="ch-card">
            <div class="ch-card-header">
                <h3>Account Statistics</h3>
            </div>
            <div class="ch-card-body">
                <div class="ch-stat-grid">
                    <div class="ch-stat-item">
                        <span class="ch-stat-num"><?php echo number_format($profile->karma_points); ?></span>
                        <span class="ch-stat-label">Karma Points</span>
                    </div>
                    <div class="ch-stat-item">
                        <span class="ch-stat-num"><?php echo number_format($profile->post_count); ?></span>
                        <span class="ch-stat-label">Posts</span>
                    </div>
                    <div class="ch-stat-item">
                        <span class="ch-stat-num"><?php echo number_format($profile->comment_count); ?></span>
                        <span class="ch-stat-label">Comments</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ============================================================
// MY FEED PAGE  [ch_my_feed]
// ============================================================

function bntm_shortcode_ch_my_feed() {
    global $wpdb;

    $view_uid  = (int)($_GET['uid'] ?? 0);
    $viewer_id = get_current_user_id();
    $target_id = ($view_uid && current_user_can('manage_options')) ? $view_uid : $viewer_id;
    $is_own    = ($target_id === $viewer_id);

    ch_ensure_profile($target_id);

    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $target_id
    ));
    $wp_user = get_userdata($target_id);
    if (!$profile || !$wp_user) {
        return '<p class="ch-empty" style="padding:40px;text-align:center;">User not found.</p>';
    }

    $display_name = $profile->display_name ?: $wp_user->display_name ?: 'Community Member';
    $joined       = $wp_user->user_registered ? date('F Y', strtotime($wp_user->user_registered)) : 'Unknown';
    $feed_page    = get_page_by_path('forum-feed');
    $feed_url     = $feed_page ? get_permalink($feed_page) : home_url('/forum-feed/');
    $subtab       = sanitize_text_field($_GET['subtab'] ?? 'posts');

    $user_posts = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, c.name as cat_name, c.color as cat_color
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         WHERE p.user_id = %d AND p.status = 'active'
         ORDER BY p.created_at DESC LIMIT 50",
        $target_id
    ));

    $bookmarked_posts = [];
    $upvoted_posts    = [];
    $user_comments    = [];

    if ($is_own) {
        $bookmarked_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.name as cat_name, c.color as cat_color
             FROM {$wpdb->prefix}ch_posts p
             JOIN {$wpdb->prefix}ch_bookmarks b ON p.id = b.post_id
             LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
             WHERE b.user_id = %d AND p.status = 'active'
             ORDER BY b.created_at DESC LIMIT 50",
            $target_id
        ));

        $upvoted_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.name as cat_name, c.color as cat_color
             FROM {$wpdb->prefix}ch_posts p
             JOIN {$wpdb->prefix}ch_votes v ON p.id = v.target_id AND v.target_type = 'post'
             LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
             WHERE v.user_id = %d AND v.value = 1 AND p.status = 'active'
             ORDER BY v.created_at DESC LIMIT 50",
            $target_id
        ));

        $user_comments = $wpdb->get_results($wpdb->prepare(
            "SELECT cm.*, p.title as post_title, p.rand_id as post_rand_id
             FROM {$wpdb->prefix}ch_comments cm
             JOIN {$wpdb->prefix}ch_posts p ON cm.post_id = p.id
             WHERE cm.user_id = %d AND cm.status = 'active'
             ORDER BY cm.created_at DESC LIMIT 20",
            $target_id
        ));
    }

    $upvotes_given = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ch_votes WHERE user_id=%d AND value=1", $target_id
    ));
    $saved_count = $is_own ? (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ch_bookmarks WHERE user_id=%d", $target_id
    )) : 0;

    $categories_for_modal = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}ch_categories WHERE status='active' ORDER BY name ASC"
    );

    ob_start(); ?>
    <div class="ch-my-feed-wrap">
        <div style="margin-bottom:18px;">
            <a href="<?php echo esc_url($feed_url); ?>" class="ch-back-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Forum
            </a>
        </div>

        <div class="ch-mf-profile-card">
            <div class="ch-mf-avatar-wrap">
                <?php if ($profile->avatar_url): ?>
                <img src="<?php echo esc_url($profile->avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" class="ch-mf-avatar-img">
                <?php else: ?>
                <div class="ch-mf-avatar-initials"><?php echo esc_html(strtoupper(substr($display_name, 0, 1))); ?></div>
                <?php endif; ?>
                <span class="ch-status-badge ch-status-<?php echo esc_attr($profile->status); ?>"><?php echo esc_html(ucfirst($profile->status)); ?></span>
            </div>
            <div class="ch-mf-profile-info">
                <div class="ch-mf-name-row">
                    <h1 class="ch-mf-name"><?php echo esc_html($display_name); ?></h1>
                    <?php if ($profile->is_anonymous): ?><span class="ch-anon-badge">Anonymous mode</span><?php endif; ?>
                </div>
                <?php if ($profile->bio): ?>
                <p class="ch-mf-bio"><?php echo esc_html($profile->bio); ?></p>
                <?php endif; ?>
                <div class="ch-mf-meta-row">
                    <?php if ($profile->location): ?>
                    <span class="ch-mf-meta-item">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo esc_html($profile->location); ?>
                    </span>
                    <?php endif; ?>
                    <span class="ch-mf-meta-item">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Joined <?php echo esc_html($joined); ?>
                    </span>
                    <span class="ch-mf-meta-item">@<?php echo esc_html($wp_user->user_login); ?></span>
                </div>
                <?php if ($is_own): ?>
                <div style="margin-top:14px;">
                    <a href="<?php echo esc_url(add_query_arg('tab', 'profile', $feed_url)); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit Profile
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="ch-mf-stats-bar">
            <div class="ch-mf-stat">
                <span class="ch-mf-stat-num"><?php echo number_format($profile->karma_points); ?></span>
                <span class="ch-mf-stat-label">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Karma
                </span>
            </div>
            <div class="ch-mf-stat">
                <span class="ch-mf-stat-num"><?php echo number_format($profile->post_count); ?></span>
                <span class="ch-mf-stat-label">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Posts
                </span>
            </div>
            <div class="ch-mf-stat">
                <span class="ch-mf-stat-num"><?php echo number_format($profile->comment_count); ?></span>
                <span class="ch-mf-stat-label">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Comments
                </span>
            </div>
            <div class="ch-mf-stat">
                <span class="ch-mf-stat-num"><?php echo $upvotes_given; ?></span>
                <span class="ch-mf-stat-label">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                    Upvotes given
                </span>
            </div>
            <?php if ($is_own): ?>
            <div class="ch-mf-stat">
                <span class="ch-mf-stat-num"><?php echo $saved_count; ?></span>
                <span class="ch-mf-stat-label">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    Saved
                </span>
            </div>
            <?php endif; ?>
        </div>

        <div class="ch-mf-subnav">
            <a href="?tab=my_feed&subtab=posts"    class="ch-mf-subnav-item <?php echo $subtab==='posts'    ?'active':''; ?>">My Posts</a>
            <?php if ($is_own): ?>
            <a href="?tab=my_feed&subtab=saved"    class="ch-mf-subnav-item <?php echo $subtab==='saved'    ?'active':''; ?>">Saved</a>
            <a href="?tab=my_feed&subtab=upvoted"  class="ch-mf-subnav-item <?php echo $subtab==='upvoted'  ?'active':''; ?>">Upvoted</a>
            <a href="?tab=my_feed&subtab=comments" class="ch-mf-subnav-item <?php echo $subtab==='comments' ?'active':''; ?>">Comments</a>
            <?php endif; ?>
        </div>

        <div class="ch-mf-content">
            <?php if ($subtab === 'posts'): ?>
                <?php if (empty($user_posts)): ?>
                <div class="ch-mf-empty">
                    <svg width="40" height="40" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <p><?php echo $is_own ? "You haven't posted anything yet." : "This user has no public posts yet."; ?></p>
                    <?php if ($is_own): ?><a href="<?php echo esc_url($feed_url); ?>" class="ch-btn ch-btn-primary">Go to Forum Feed</a><?php endif; ?>
                </div>
                <?php else: foreach ($user_posts as $p): ?>
                <div class="ch-mf-post-card">
                    <div class="ch-mf-post-top">
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($p->cat_color??'#FF7551'); ?>20;color:<?php echo esc_attr($p->cat_color??'#FF7551'); ?>"><?php echo esc_html($p->cat_name??'General'); ?></span>
                        <?php if ($p->is_pinned): ?><span class="ch-mf-pinned-badge">📌 Pinned</span><?php endif; ?>
                        <span class="ch-mf-time"><?php echo human_time_diff(strtotime($p->created_at), current_time('timestamp')); ?> ago</span>
                        <?php if ($is_own || current_user_can('manage_options')): ?>
                        <div class="ch-mf-post-actions">
                            <button class="ch-btn-xs ch-btn-secondary" onclick="chOpenEditPostModal(<?php echo (int)$p->id; ?>)">Edit</button>
                            <button class="ch-btn-xs ch-btn-danger" onclick="chDeletePost(<?php echo (int)$p->id; ?>, '<?php echo esc_attr(wp_create_nonce('ch_post_view_nonce')); ?>')">Delete</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo esc_url(add_query_arg('view_post', $p->rand_id, $feed_url)); ?>" class="ch-mf-post-title"><?php echo esc_html($p->title); ?></a>
                    <p class="ch-mf-post-excerpt"><?php echo esc_html(wp_trim_words($p->content, 22)); ?></p>
                    <div class="ch-mf-post-footer">
                        <span>▲ <?php echo (int)$p->vote_count; ?> upvotes</span>
                        <span>💬 <?php echo (int)$p->comment_count; ?> comments</span>
                        <span>👁 <?php echo number_format($p->view_count); ?> views</span>
                    </div>
                </div>
                <?php endforeach; endif; ?>

            <?php elseif ($subtab === 'saved' && $is_own): ?>
                <?php if (empty($bookmarked_posts)): ?>
                <div class="ch-mf-empty">
                    <svg width="40" height="40" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    <p>You haven't saved any posts yet.</p>
                </div>
                <?php else: foreach ($bookmarked_posts as $p): ?>
                <div class="ch-mf-post-card">
                    <div class="ch-mf-post-top">
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($p->cat_color??'#FF7551'); ?>20;color:<?php echo esc_attr($p->cat_color??'#FF7551'); ?>"><?php echo esc_html($p->cat_name??'General'); ?></span>
                        <span class="ch-mf-time"><?php echo human_time_diff(strtotime($p->created_at), current_time('timestamp')); ?> ago</span>
                    </div>
                    <a href="<?php echo esc_url(add_query_arg('view_post', $p->rand_id, $feed_url)); ?>" class="ch-mf-post-title"><?php echo esc_html($p->title); ?></a>
                    <p class="ch-mf-post-excerpt"><?php echo esc_html(wp_trim_words($p->content, 22)); ?></p>
                    <div class="ch-mf-post-footer">
                        <span>▲ <?php echo (int)$p->vote_count; ?></span>
                        <span>💬 <?php echo (int)$p->comment_count; ?></span>
                    </div>
                </div>
                <?php endforeach; endif; ?>

            <?php elseif ($subtab === 'upvoted' && $is_own): ?>
                <?php if (empty($upvoted_posts)): ?>
                <div class="ch-mf-empty">
                    <svg width="40" height="40" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5"><polyline points="18 15 12 9 6 15"/></svg>
                    <p>You haven't upvoted any posts yet.</p>
                </div>
                <?php else: foreach ($upvoted_posts as $p): ?>
                <div class="ch-mf-post-card">
                    <div class="ch-mf-post-top">
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($p->cat_color??'#FF7551'); ?>20;color:<?php echo esc_attr($p->cat_color??'#FF7551'); ?>"><?php echo esc_html($p->cat_name??'General'); ?></span>
                        <span class="ch-mf-time"><?php echo human_time_diff(strtotime($p->created_at), current_time('timestamp')); ?> ago</span>
                    </div>
                    <a href="<?php echo esc_url(add_query_arg('view_post', $p->rand_id, $feed_url)); ?>" class="ch-mf-post-title"><?php echo esc_html($p->title); ?></a>
                    <p class="ch-mf-post-excerpt"><?php echo esc_html(wp_trim_words($p->content, 22)); ?></p>
                    <div class="ch-mf-post-footer">
                        <span>▲ <?php echo (int)$p->vote_count; ?></span>
                        <span>💬 <?php echo (int)$p->comment_count; ?></span>
                    </div>
                </div>
                <?php endforeach; endif; ?>

            <?php elseif ($subtab === 'comments' && $is_own): ?>
                <?php if (empty($user_comments)): ?>
                <div class="ch-mf-empty">
                    <svg width="40" height="40" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <p>You haven't commented on anything yet.</p>
                </div>
                <?php else: foreach ($user_comments as $cm): ?>
                <div class="ch-mf-comment-card">
                    <div class="ch-mf-comment-post-ref">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        On: <a href="<?php echo esc_url(add_query_arg('view_post', $cm->post_rand_id, $feed_url)); ?>" class="ch-mf-ref-link"><?php echo esc_html(wp_trim_words($cm->post_title, 8)); ?></a>
                        <span class="ch-mf-time" style="margin-left:auto;"><?php echo human_time_diff(strtotime($cm->created_at), current_time('timestamp')); ?> ago</span>
                    </div>
                    <p class="ch-mf-comment-content"><?php echo esc_html($cm->content); ?></p>
                    <div class="ch-mf-post-footer"><span>▲ <?php echo (int)$cm->vote_count; ?> votes</span></div>
                </div>
                <?php endforeach; endif; ?>
            <?php endif; ?>
        </div>

        <!-- Edit post modal — Facebook-style composer -->
        <div id="ch-modal-edit-post" class="ch-modal-overlay" style="display:none">
            <div class="ch-modal ch-modal-lg ch-composer-modal">
                <div class="ch-composer-header">
                    <h3>Edit Post</h3>
                    <button class="ch-composer-close" onclick="chCloseModal('ch-modal-edit-post')" aria-label="Close">&times;</button>
                </div>
                <div class="ch-composer-divider"></div>

                <div class="ch-composer-body">
                    <input type="hidden" id="ch-edit-post-id">

                    <div class="ch-composer-author-row">
                        <div class="ch-composer-avatar">
                            <?php $cd4 = wp_get_current_user()->display_name ?: 'U'; echo esc_html(strtoupper(substr($cd4, 0, 1))); ?>
                        </div>
                        <div class="ch-composer-author-info">
                            <div class="ch-composer-author-name"><?php echo esc_html(wp_get_current_user()->display_name ?: 'You'); ?></div>
                            <div class="ch-composer-meta-row">
                                <select id="ch-edit-post-cat" class="ch-composer-cat-select">
                                    <option value="">📂 Select category</option>
                                    <?php foreach ($categories_for_modal as $cat): ?>
                                    <option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="ch-composer-anon-toggle">
                                    <input type="checkbox" id="ch-edit-post-anon">
                                    <span>Anonymous</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <input type="text" id="ch-edit-post-title" class="ch-composer-title" placeholder="Write a title…">
                    <textarea id="ch-edit-post-content" class="ch-composer-textarea" rows="4"
                        placeholder="What's on your mind?"></textarea>

                    <div class="ch-composer-tags-row">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        <input type="text" id="ch-edit-post-tags" class="ch-composer-tags-input" placeholder="Tags: road, drainage, urgent…">
                    </div>

                    <label class="ch-composer-media-area" id="ch-edit-post-media-label2" for="ch-edit-post-media2">
                        <div class="ch-composer-media-inner">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <span>Add photos / videos</span>
                            <span class="ch-composer-media-sub">or drag and drop</span>
                        </div>
                        <input type="file" id="ch-edit-post-media" style="display:none" multiple accept="image/*,video/*"
                            onchange="chUpdateMediaLabel(this, 'ch-edit-post-media-label2')">
                    </label>
                </div>

                <div id="ch-edit-post-msg"></div>

                <div class="ch-composer-footer">
                    <div class="ch-composer-footer-hint">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Edit your post. Changes are visible immediately.
                    </div>
                    <button class="ch-btn ch-btn-primary ch-composer-submit" id="ch-update-post-btn"
                        onclick="chUpdatePost('<?php echo esc_attr(wp_create_nonce('ch_post_view_nonce')); ?>')">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

    </div><!-- .ch-my-feed-wrap -->
    <?php
    return ob_get_clean();
}

// ============================================================
// PUBLIC USER PROFILE VIEW
// ============================================================

function ch_public_user_profile($view_uid) {
    global $wpdb;

    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $view_uid
    ));
    $wp_user = get_userdata($view_uid);

    if (!$profile || !$wp_user) {
        return '<p class="ch-empty" style="padding:40px;text-align:center;">User not found.</p>';
    }

    $display_name = $profile->display_name ?: $wp_user->display_name ?: 'Community Member';

    // Get user's recent posts
    $user_posts = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, c.name as cat_name, c.color as cat_color
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         WHERE p.user_id = %d AND p.status = 'active' AND p.is_anonymous = 0
         ORDER BY p.created_at DESC
         LIMIT 20",
        $view_uid
    ));

    $feed_page  = get_page_by_path('forum-feed');
    $feed_url   = $feed_page ? get_permalink($feed_page) : home_url('/forum-feed/');
    $joined     = $wp_user->user_registered ? date('F Y', strtotime($wp_user->user_registered)) : 'Unknown';

    ob_start();
    echo ch_global_styles();
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>
    <div class="ch-public-profile-wrap">

        <div class="ch-public-profile-header">
            <a href="<?php echo esc_url($feed_url); ?>" class="ch-back-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Forum
            </a>
        </div>

        <div class="ch-public-profile-card">
            <div class="ch-public-profile-avatar">
                <?php if ($profile->avatar_url): ?>
                <img src="<?php echo esc_url($profile->avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
                <?php else: ?>
                <div class="ch-avatar-lg"><?php echo esc_html(strtoupper(substr($display_name, 0, 1))); ?></div>
                <?php endif; ?>
            </div>
            <div class="ch-public-profile-info">
                <h1 class="ch-public-profile-name"><?php echo esc_html($display_name); ?></h1>
                <?php if ($profile->location): ?>
                <div class="ch-public-profile-meta">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?php echo esc_html($profile->location); ?>
                </div>
                <?php endif; ?>
                <div class="ch-public-profile-meta">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Joined <?php echo esc_html($joined); ?>
                </div>
                <?php if ($profile->bio): ?>
                <p class="ch-public-profile-bio"><?php echo esc_html($profile->bio); ?></p>
                <?php endif; ?>
                <span class="ch-status-badge ch-status-<?php echo esc_attr($profile->status); ?>"><?php echo esc_html(ucfirst($profile->status)); ?></span>
            </div>
        </div>

        <div class="ch-public-profile-stats">
            <div class="ch-public-stat-card">
                <span class="ch-stat-num"><?php echo number_format($profile->karma_points); ?></span>
                <span class="ch-stat-label">Karma Points</span>
            </div>
            <div class="ch-public-stat-card">
                <span class="ch-stat-num"><?php echo number_format($profile->post_count); ?></span>
                <span class="ch-stat-label">Posts</span>
            </div>
            <div class="ch-public-stat-card">
                <span class="ch-stat-num"><?php echo number_format($profile->comment_count); ?></span>
                <span class="ch-stat-label">Comments</span>
            </div>
        </div>

        <div class="ch-public-profile-posts">
            <h2 class="ch-section-title">Posts by <?php echo esc_html($display_name); ?></h2>
            <?php if (empty($user_posts)): ?>
            <p class="ch-empty">This user hasn't made any public posts yet.</p>
            <?php else: foreach ($user_posts as $p): ?>
            <a href="<?php echo esc_url(add_query_arg('view_post', $p->rand_id, $feed_url)); ?>" class="ch-public-post-card">
                <div class="ch-public-post-top">
                    <span class="ch-cat-badge" style="background:<?php echo esc_attr($p->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($p->cat_color ?? '#FF7551'); ?>">
                        <?php echo esc_html($p->cat_name ?? 'General'); ?>
                    </span>
                    <span class="ch-public-post-time"><?php echo human_time_diff(strtotime($p->created_at), current_time('timestamp')); ?> ago</span>
                </div>
                <h3 class="ch-public-post-title"><?php echo esc_html($p->title); ?></h3>
                <p class="ch-public-post-excerpt"><?php echo esc_html(wp_trim_words($p->content, 20)); ?></p>
                <div class="ch-public-post-footer">
                    <span>▲ <?php echo (int)$p->vote_count; ?></span>
                    <span>💬 <?php echo (int)$p->comment_count; ?></span>
                    <span>👁 <?php echo number_format($p->view_count); ?></span>
                </div>
            </a>
            <?php endforeach; endif; ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
}

// ============================================================
// FRONTEND: FORUM FEED
// ============================================================

function bntm_shortcode_ch_feed() {
    global $wpdb;

    $tab     = sanitize_text_field($_GET['tab'] ?? '');

    // Route: viewing a single post
    $rand_id = sanitize_text_field($_GET['view_post'] ?? '');
    if ($rand_id) {
        return bntm_shortcode_ch_post_view();
    }

    // Route: public user profile view (admin "View Dashboard" button)
    if ($tab === 'user_profile') {
        $view_uid = (int)($_GET['uid'] ?? 0);
        if ($view_uid) {
            return ch_public_user_profile($view_uid);
        }
    }

    // Route: my feed — handled below in the main render path so the navbar stays visible

    // Route: profile tab
    if ($tab === 'profile') {
        $user_id = get_current_user_id();
        if (!$user_id) {
            $auth_page = get_page_by_path('login-register');
            $auth_url  = $auth_page ? get_permalink($auth_page) : wp_login_url(get_permalink());
            wp_redirect(add_query_arg('redirect_to', urlencode(get_permalink() . '?tab=profile'), $auth_url));
            exit;
        }
        // Inject dark mode class BEFORE the theme renders — eliminates flash of white
        add_action('wp_head', function() {
            echo '<script>try{if(localStorage.getItem("ch_dark_mode")==="1"){document.documentElement.classList.add("ch-dark");document.documentElement.style.background="var(--ch-bg,#121214)";document.body&&(document.body.style.background="var(--ch-bg,#121214)");}}catch(e){}</script>';
        }, 1);
        ob_start();
        echo ch_global_styles();
        echo ch_global_scripts();
        $feed_pg_back  = get_page_by_path('forum-feed');
        $feed_url_back = $feed_pg_back ? get_permalink($feed_pg_back) : home_url('/forum-feed/');
        $current_display_p = wp_get_current_user()->display_name ?: 'U';
        ?>
        <style>
            html, body { background: var(--ch-bg) !important; color: var(--ch-text); margin: 0; }
            .ch-dark html, .ch-dark body { background: #121214 !important; }
            .ch-profile-standalone { font-family: var(--ch-font); background: var(--ch-bg); min-height: 100vh; color: var(--ch-text); }
            .ch-profile-standalone .ch-top-nav { background: var(--ch-surface); border-bottom: 1px solid var(--ch-border); padding: 0 24px; height: 56px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 200; box-shadow: var(--ch-shadow-sm); }
            .ch-profile-standalone .ch-nav-links { display: flex; gap: 2px; height: 100%; align-items: center; }
            .ch-profile-page-wrap { max-width: 880px; margin: 0 auto; padding: 26px 20px; }
            .ch-profile-page-wrap .ch-card-body { padding: 20px; }
            .ch-stat-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
            .ch-stat-item { text-align: center; padding: 16px; background: var(--ch-bg); border-radius: var(--ch-radius); border: 1px solid var(--ch-border); }
            .ch-stat-item .ch-stat-num   { display: block; font-size: 22px; font-weight: 700; color: var(--ch-accent); letter-spacing: -0.3px; }
            .ch-stat-item .ch-stat-label { display: block; font-size: 11.5px; color: var(--ch-text-subtle); margin-top: 3px; font-weight: 500; }
        </style>
        <div class="ch-profile-standalone">
            <nav class="ch-top-nav">
                <div class="ch-nav-links">
                    <a href="<?php echo esc_url($feed_url_back); ?>" class="ch-nav-link">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                        Back to Forum
                    </a>
                </div>
                <div class="ch-user-bar">
                    <button class="ch-avatar-btn" id="ch-profile-btn" onclick="chToggleProfileMenu(event)" aria-label="Profile">
                        <?php echo esc_html(strtoupper(substr($current_display_p, 0, 1))); ?>
                    </button>
                    <div class="ch-dropdown-panel ch-dropdown-panel-sm" id="ch-profile-menu" style="display:none;">
                        <div class="ch-dropdown-user-info">
                            <div class="ch-avatar-btn ch-avatar-btn-lg"><?php echo esc_html(strtoupper(substr($current_display_p, 0, 1))); ?></div>
                            <div>
                                <div class="ch-dropdown-username"><?php echo esc_html($current_display_p); ?></div>
                                <div class="ch-dropdown-usermeta">Community Member</div>
                            </div>
                        </div>
                        <div class="ch-dropdown-divider"></div>
                        <a href="javascript:void(0)" class="ch-dropdown-item" onclick="chOpenSettingsModal(); chCloseProfileMenu();">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                            Settings
                        </a>
                        <div class="ch-dropdown-divider"></div>
                        <a href="<?php echo wp_logout_url(esc_url($feed_url_back)); ?>" class="ch-dropdown-item ch-dropdown-item-danger">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Sign Out
                        </a>
                    </div>
                </div>
            </nav>
            <div class="ch-profile-page-wrap">
                <?php echo ch_profile_tab($user_id); ?>
            </div>
        </div>
        <?php
        // Settings modal (needed for dark mode toggle)
        echo ch_settings_modal_html();
        return ob_get_clean();
    }

    $user_id    = get_current_user_id();
    $sort       = sanitize_text_field($_GET['sort']   ?? 'new');
    $cat_slug   = sanitize_text_field($_GET['cat']    ?? '');
    $search     = sanitize_text_field($_GET['s']      ?? '');
    $location   = sanitize_text_field($_GET['location'] ?? '');
    $bookmarks  = isset($_GET['bookmarks']) ? 1 : 0;
    $page       = max(1, (int)($_GET['paged'] ?? 1));
    $per_page   = 15;
    $offset     = ($page - 1) * $per_page;

    $cat_filter = '';
    $cat_obj    = null;
    if ($cat_slug) {
        $cat_obj = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ch_categories WHERE slug = %s", $cat_slug));
        if ($cat_obj) $cat_filter = " AND p.category_id = {$cat_obj->id}";
    }

    $location_filter = '';
    if ($location) {
        $location_filter = $wpdb->prepare(" AND up.location = %s", $location);
    }

    $bookmark_filter = '';
    if ($bookmarks && $user_id) {
        $bookmark_filter = $wpdb->prepare(" AND p.id IN (SELECT post_id FROM {$wpdb->prefix}ch_bookmarks WHERE user_id = %d)", $user_id);
    }

    $search_filter = '';
    if ($search) {
        $search_filter = " AND (p.title LIKE '%" . esc_sql($search) . "%' OR p.content LIKE '%" . esc_sql($search) . "%')";
    }

    $order_by = match($sort) {
        'top'      => "p.vote_count DESC",
        'trending' => "(p.vote_count + p.comment_count * 2) DESC",
        default    => "p.created_at DESC",
    };

    // Privacy filter: exclude private category posts for guests/non-followers
    $privacy_filter = '';
    if (!$user_id) {
        $privacy_filter = " AND (c.is_private = 0 OR c.is_private IS NULL)";
    } elseif (!current_user_can('manage_options')) {
        $followed_ids = empty($followed) ? [0] : array_keys($followed);
        $placeholders = implode(',', array_fill(0, count($followed_ids), '%d'));
        $privacy_filter = $wpdb->prepare(
            " AND (c.is_private = 0 OR c.is_private IS NULL OR p.category_id IN ($placeholders))",
            ...$followed_ids
        );
    }
    $base_where = "WHERE p.status = 'active' $cat_filter $location_filter $bookmark_filter $search_filter$privacy_filter";
    $total      = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts p LEFT JOIN {$wpdb->prefix}ch_user_profiles up ON p.user_id = up.user_id $base_where");

    $posts = $wpdb->get_results(
        "SELECT p.*, c.name as cat_name, c.color as cat_color, c.slug as cat_slug,
                u.display_name as author_name, u.karma_points as author_karma, u.location as author_location
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
         $base_where
         ORDER BY p.is_pinned DESC, $order_by
         LIMIT {$per_page} OFFSET {$offset}"
    );

    // Get published announcements
    $announcements = $wpdb->get_results(
        "SELECT a.*, u.display_name as admin_name
         FROM {$wpdb->prefix}ch_announcements a
         LEFT JOIN {$wpdb->users} u ON a.admin_id = u.ID
         WHERE a.is_active = 1
         ORDER BY a.created_at DESC
         LIMIT 5"
    );

    $cat_search = sanitize_text_field($_GET['cat_search'] ?? '');
    $cat_sort   = sanitize_text_field($_GET['cat_sort'] ?? 'name');

    $cat_where = "WHERE status='active'";
    if ($cat_search) {
        $cat_where .= $wpdb->prepare(" AND name LIKE %s", '%' . $wpdb->esc_like($cat_search) . '%');
    }

    if ($cat_sort === 'activity') {
        $cat_order = 'post_count DESC';
    } elseif ($cat_sort === 'popularity') {
        $cat_order = 'follower_count DESC';
    } else {
        $cat_order = 'name ASC';
    }

    $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ch_categories $cat_where ORDER BY $cat_order");
    $total_pages = ceil($total / $per_page);

    // Get available locations for filtering
    $available_locations = $wpdb->get_col("SELECT DISTINCT location FROM {$wpdb->prefix}ch_user_profiles WHERE location != '' AND location IS NOT NULL ORDER BY location");

    $followed = [];
    if ($user_id) {
        $fids = $wpdb->get_col($wpdb->prepare("SELECT category_id FROM {$wpdb->prefix}ch_follows WHERE user_id = %d", $user_id));
        $followed = array_flip($fids);
    }

    $user_bookmarks = [];
    if ($user_id) {
        $bms = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}ch_bookmarks WHERE user_id = %d", $user_id));
        $user_bookmarks = array_flip($bms);
    }

    // Fetch current user's votes on the visible posts for active-up/active-down state
    $user_post_votes = [];
    if ($user_id && !empty($posts)) {
        $post_ids     = array_map(fn($p) => (int)$p->id, $posts);
        $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
        $vote_rows    = $wpdb->get_results($wpdb->prepare(
            "SELECT target_id, value FROM {$wpdb->prefix}ch_votes
             WHERE user_id = %d AND target_type = 'post' AND target_id IN ($placeholders)",
            array_merge([$user_id], $post_ids)
        ));
        foreach ($vote_rows as $vr) {
            $user_post_votes[(int)$vr->target_id] = (int)$vr->value;
        }
    }

    $nonce = wp_create_nonce('ch_feed_nonce');

    ob_start();
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>'; var chFeedUrl = '<?php echo esc_js($feed_url); ?>';</script>
    <nav class="ch-top-nav">
        <div class="ch-nav-links">
            <?php if ($user_id): ?>
            <a href="?tab=my_feed" class="ch-nav-link <?php echo ($tab === 'my_feed') ? 'active' : ''; ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span class="ch-nav-label">My Feed</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo get_permalink(); ?>" class="ch-nav-link <?php echo !$bookmarks && $sort === 'new' && $tab === '' && !$cat_slug && !$search ? 'active' : ''; ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="ch-nav-label">Home</span>
            </a>
            <a href="?sort=trending" class="ch-nav-link <?php echo $sort === 'trending' && $tab === '' && !$bookmarks ? 'active' : ''; ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <span class="ch-nav-label">Trending</span>
            </a>
            <?php if ($user_id): ?>
            <a href="?bookmarks=1" class="ch-nav-link <?php echo $bookmarks && $tab === '' ? 'active' : ''; ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                <span class="ch-nav-label">Bookmarks</span>
            </a>
            <?php endif; ?>
        </div>
    <?php if ($user_id): ?>
        <div class="ch-user-bar">
            <div class="ch-notifications-dropdown">
                <button class="ch-icon-action-btn ch-notifications-btn" id="ch-notif-btn" onclick="chToggleNotifications(event)" aria-label="Notifications">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="ch-notification-badge" id="ch-notification-count" style="display:none;"></span>
                </button>
                <div class="ch-dropdown-panel" id="ch-notifications-menu" style="display:none;">
                    <div class="ch-dropdown-header">
                        <span>Notifications</span>
                        <button class="ch-dropdown-action" onclick="chMarkAllNotificationsRead()">Mark all read</button>
                    </div>
                    <div id="ch-notifications-list" class="ch-notifications-list">
                        <div class="ch-no-notifications">Loading...</div>
                    </div>
                    <div class="ch-dropdown-footer">
                        <a href="?tab=profile">View all notifications</a>
                    </div>
                </div>
            </div>
            <div class="ch-profile-dropdown">
                <button class="ch-avatar-btn" id="ch-profile-btn" onclick="chToggleProfileMenu(event)" aria-label="Profile">
                    <?php
                    $current_display = wp_get_current_user()->display_name ?: 'U';
                    echo strtoupper(substr($current_display, 0, 1));
                    ?>
                </button>
                <div class="ch-dropdown-panel ch-dropdown-panel-sm" id="ch-profile-menu" style="display:none;">
                    <div class="ch-dropdown-user-info">
                        <div class="ch-avatar-btn ch-avatar-btn-lg"><?php echo strtoupper(substr($current_display, 0, 1)); ?></div>
                        <div>
                            <div class="ch-dropdown-username"><?php echo esc_html($current_display); ?></div>
                            <div class="ch-dropdown-usermeta">Community Member</div>
                        </div>
                    </div>
                    <div class="ch-dropdown-divider"></div>
                    <a href="javascript:void(0)" class="ch-dropdown-item" onclick="chOpenSettingsModal(); chCloseProfileMenu();">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Settings
                    </a>
                    <div class="ch-dropdown-divider"></div>
                    <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="ch-dropdown-item ch-dropdown-item-danger">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Sign Out
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="ch-user-bar">
            <?php
            $auth_page  = get_page_by_path('login-register');
            $auth_url   = $auth_page ? get_permalink($auth_page) : wp_login_url(get_permalink());
            $reg_url    = $auth_page ? add_query_arg('tab','register', get_permalink($auth_page)) : wp_registration_url();
            ?>
            <a href="<?php echo esc_url($auth_url); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">Sign In</a>
            <a href="<?php echo esc_url($reg_url); ?>" class="ch-btn ch-btn-primary ch-btn-sm">Join</a>
        </div>
    <?php endif; ?>
    </nav>

    <?php if ($tab === 'my_feed'): ?>
    <div class="ch-mf-page-wrap">
        <?php echo bntm_shortcode_ch_my_feed(); ?>
    </div>
    <?php elseif ($sort === 'trending' && !$bookmarks && $tab === ''): ?>
    <!-- ════════════════════════════════════════════════════ -->
    <!--  TRENDING PAGE                                       -->
    <!-- ════════════════════════════════════════════════════ -->
    <?php
    $trending_posts = $wpdb->get_results(
        "SELECT p.*, c.name as cat_name, c.color as cat_color,
                COALESCE(u.display_name, 'Community Member') as author_name
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
         WHERE p.status = 'active'
         ORDER BY (p.vote_count * 2 + p.comment_count * 3 + p.view_count) DESC, p.created_at DESC
         LIMIT 30"
    );
    $trending_cats = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}ch_categories WHERE status='active' AND (is_private=0 OR is_private IS NULL)
         ORDER BY (post_count + follower_count) DESC LIMIT 8"
    );
    ?>
    <div class="ch-special-page-wrap">
        <div class="ch-special-hero ch-trending-hero">
            <div class="ch-special-hero-icon">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div>
                <h1 class="ch-special-hero-title">Trending</h1>
                <p class="ch-special-hero-sub">The hottest discussions in your community right now</p>
            </div>
        </div>

        <div class="ch-special-layout">
            <!-- Main trending posts -->
            <main class="ch-special-main">
                <?php if (empty($trending_posts)): ?>
                <div class="ch-empty-state">
                    <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    <p>No trending posts yet. Be the first to spark a discussion!</p>
                </div>
                <?php else: ?>
                <?php foreach ($trending_posts as $i => $post): ?>
                <article class="ch-trending-post-card">
                    <div class="ch-trending-rank"><?php echo $i + 1; ?></div>
                    <div class="ch-trending-body">
                        <div class="ch-post-meta-row" style="margin-bottom:6px;">
                            <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                                <?php echo esc_html($post->cat_name ?? 'General'); ?>
                            </span>
                            <span class="ch-post-author"><?php echo $post->is_anonymous ? 'Anonymous' : esc_html($post->author_name); ?></span>
                            <span class="ch-post-time"><?php echo human_time_diff(strtotime($post->created_at), current_time('timestamp')); ?> ago</span>
                        </div>
                        <a href="?view_post=<?php echo esc_attr($post->rand_id); ?>" class="ch-trending-post-title">
                            <?php echo esc_html($post->title); ?>
                        </a>
                        <p class="ch-trending-post-excerpt"><?php echo esc_html(wp_trim_words(strip_tags($post->content), 20)); ?></p>
                        <div class="ch-trending-stats">
                            <span class="ch-trending-stat">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                                <?php echo (int)$post->vote_count; ?> votes
                            </span>
                            <span class="ch-trending-stat">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <?php echo (int)$post->comment_count; ?> comments
                            </span>
                            <span class="ch-trending-stat">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <?php echo (int)$post->view_count; ?> views
                            </span>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </main>

            <!-- Trending sidebar -->
            <aside class="ch-special-sidebar">
                <div class="ch-sidebar-widget">
                    <h4>🔥 Hot Categories</h4>
                    <?php foreach ($trending_cats as $tcat): ?>
                    <a href="?cat=<?php echo esc_attr($tcat->slug); ?>" class="ch-cat-link" style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                        <span style="display:flex;align-items:center;gap:6px;">
                            <span class="ch-cat-dot" style="background:<?php echo esc_attr($tcat->color); ?>"></span>
                            <?php echo esc_html($tcat->name); ?>
                        </span>
                        <span class="ch-cat-count"><?php echo (int)$tcat->post_count; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($user_id): ?>
                <div class="ch-sidebar-widget">
                    <h4>Quick Post</h4>
                    <button class="ch-btn ch-btn-primary ch-btn-full" onclick="chOpenModal('ch-modal-create-post')">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        New Post
                    </button>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>

    <?php elseif ($bookmarks && $user_id && $tab === ''): ?>
    <!-- ════════════════════════════════════════════════════ -->
    <!--  BOOKMARKS PAGE                                      -->
    <!-- ════════════════════════════════════════════════════ -->
    <?php
    $bookmarked_posts = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, c.name as cat_name, c.color as cat_color,
                COALESCE(u.display_name, 'Community Member') as author_name,
                bm.created_at as bookmarked_at
         FROM {$wpdb->prefix}ch_bookmarks bm
         JOIN {$wpdb->prefix}ch_posts p ON bm.post_id = p.id
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
         WHERE bm.user_id = %d AND p.status = 'active'
         ORDER BY bm.created_at DESC",
        $user_id
    ));
    $bm_cats = [];
    foreach ($bookmarked_posts as $bp) {
        if ($bp->cat_name && !isset($bm_cats[$bp->cat_name])) $bm_cats[$bp->cat_name] = ['color' => $bp->cat_color, 'slug' => $bp->cat_name, 'count' => 0];
        if ($bp->cat_name) $bm_cats[$bp->cat_name]['count']++;
    }
    ?>
    <div class="ch-special-page-wrap">
        <div class="ch-special-hero ch-bookmarks-hero">
            <div class="ch-special-hero-icon">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div>
                <h1 class="ch-special-hero-title">My Bookmarks</h1>
                <p class="ch-special-hero-sub"><?php echo count($bookmarked_posts); ?> saved post<?php echo count($bookmarked_posts) !== 1 ? 's' : ''; ?></p>
            </div>
        </div>

        <div class="ch-special-layout">
            <main class="ch-special-main">
                <?php if (empty($bookmarked_posts)): ?>
                <div class="ch-empty-state">
                    <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" stroke-width="1.5"><path d="M19 21l-7-5-7 5V5a2 2 0 0 0-2 2h10a2 2 0 0 1 2 2z"/></svg>
                    <p>No bookmarks yet. Save posts you want to come back to!</p>
                </div>
                <?php else: ?>
                <?php foreach ($bookmarked_posts as $post): ?>
                <article class="ch-post-card" data-id="<?php echo (int)$post->id; ?>" data-category="<?php echo $post->category_id; ?>" data-anonymous="<?php echo $post->is_anonymous; ?>">
                    <div class="ch-post-vote-col">
                        <?php $uv = $user_post_votes[$post->id] ?? 0; ?>
                        <button class="ch-vote-btn ch-vote-up <?php echo $uv === 1 ? 'active-up' : ''; ?>" data-id="<?php echo (int)$post->id; ?>" data-type="post" data-val="1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                        </button>
                        <span class="ch-vote-count"><?php echo (int)$post->vote_count; ?></span>
                        <button class="ch-vote-btn ch-vote-down <?php echo $uv === -1 ? 'active-down' : ''; ?>" data-id="<?php echo (int)$post->id; ?>" data-type="post" data-val="-1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                    </div>
                    <div class="ch-post-body">
                        <div class="ch-post-meta-row">
                            <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                                <?php echo esc_html($post->cat_name ?? 'General'); ?>
                            </span>
                            <span class="ch-post-author"><?php echo $post->is_anonymous ? 'Anonymous' : esc_html($post->author_name); ?></span>
                            <span class="ch-post-time"><?php echo human_time_diff(strtotime($post->created_at), current_time('timestamp')); ?> ago</span>
                            <span style="font-size:11px;color:var(--ch-text-subtle);margin-left:4px;">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="vertical-align:-1px"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                                Saved <?php echo human_time_diff(strtotime($post->bookmarked_at), current_time('timestamp')); ?> ago
                            </span>
                        </div>
                        <a href="?view_post=<?php echo esc_attr($post->rand_id); ?>" class="ch-post-title-link">
                            <h3 class="ch-post-title"><?php echo esc_html($post->title); ?></h3>
                        </a>
                        <p class="ch-post-excerpt"><?php echo esc_html(wp_trim_words(strip_tags($post->content), 25)); ?></p>
                        <div class="ch-post-actions-row">
                            <span class="ch-post-stat">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <?php echo (int)$post->comment_count; ?>
                            </span>
                            <button class="ch-post-action ch-bookmark-btn <?php echo isset($user_bookmarks[$post->id]) ? 'ch-bookmarked' : ''; ?>"
                                    data-post-id="<?php echo (int)$post->id; ?>"
                                    onclick="chToggleBookmark(this, '<?php echo esc_attr($nonce); ?>')">
                                <svg width="14" height="14" fill="<?php echo isset($user_bookmarks[$post->id]) ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                                <?php echo isset($user_bookmarks[$post->id]) ? 'Saved' : 'Save'; ?>
                            </button>
                        </div>
                    </div>
                </article>
                <?php endforeach; endif; ?>
            </main>

            <aside class="ch-special-sidebar">
                <?php if (!empty($bm_cats)): ?>
                <div class="ch-sidebar-widget">
                    <h4>Saved by Category</h4>
                    <?php foreach ($bm_cats as $cname => $cdata): ?>
                    <div class="ch-cat-link" style="display:flex;align-items:center;justify-content:space-between;cursor:default;">
                        <span style="display:flex;align-items:center;gap:6px;">
                            <span class="ch-cat-dot" style="background:<?php echo esc_attr($cdata['color'] ?? '#FF7551'); ?>"></span>
                            <?php echo esc_html($cname); ?>
                        </span>
                        <span class="ch-cat-count"><?php echo (int)$cdata['count']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="ch-sidebar-widget">
                    <h4>Browse Forum</h4>
                    <a href="<?php echo esc_url(get_permalink()); ?>" class="ch-btn ch-btn-secondary ch-btn-full ch-btn-sm" style="text-align:center;text-decoration:none;">
                        ← Back to Home Feed
                    </a>
                </div>
            </aside>
        </div>
    </div>

    <?php else: ?>
    <div class="ch-feed-wrap">
        <!-- Sidebar -->
        <aside class="ch-feed-sidebar">
            <div class="ch-sidebar-widget">
                <h4>Categories</h4>
                <form method="get" class="ch-categories-filter-form ch-categories-filter-sidebar" style="margin-bottom:12px;">
                    <input type="hidden" name="cat" value="<?php echo esc_attr($cat_slug); ?>">
                    <div class="ch-field-group">
                        <input type="text" name="cat_search" class="ch-input" placeholder="Search categories" value="<?php echo esc_attr($cat_search); ?>">
                    </div>
                    <div class="ch-field-group">
                        <select name="cat_sort" class="ch-input">
                            <option value="name" <?php selected($cat_sort, 'name'); ?>>Name</option>
                            <option value="activity" <?php selected($cat_sort, 'activity'); ?>>Activity</option>
                            <option value="popularity" <?php selected($cat_sort, 'popularity'); ?>>Popularity</option>
                        </select>
                    </div>
                    <button type="submit" class="ch-btn ch-btn-secondary" style="width:100%;">Apply</button>
                </form>

                <a href="<?php echo get_permalink(); ?>" class="ch-cat-link <?php echo !$cat_slug ? 'active' : ''; ?>">
                    All Topics
                </a>
                <?php foreach ($categories as $cat):
                    if ($cat->is_private) {
                        if (!$user_id) continue;
                        if (!current_user_can('manage_options') && !isset($followed[$cat->id])) continue;
                    }
                ?>
                    <div class="ch-cat-item" style="display:flex; align-items:center; justify-content:space-between; gap:4px;">
                        <a href="?cat=<?php echo esc_attr($cat->slug); ?>" class="ch-cat-link <?php echo $cat_slug === $cat->slug ? 'active' : ''; ?>" style="flex:1;min-width:0;">
                            <span class="ch-cat-dot" style="background:<?php echo esc_attr($cat->color); ?>"></span>
                            <?php echo esc_html($cat->name); ?>
                            <?php if ($cat->is_private): ?>
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="opacity:.5;margin-left:2px;flex-shrink:0" title="Private"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <?php endif; ?>
                            <span class="ch-cat-count"><?php echo $cat->post_count; ?></span>
                        </a>

                    </div>
                <?php endforeach; ?>

                <?php
                $cat_creation_on     = get_option('ch_user_category_creation', 0);
                $cat_karma_threshold = (int)get_option('ch_category_creation_karma', 100);
                $user_karma_pts      = $user_id ? (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT karma_points FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id
                )) : 0;
                $can_create_cat = $user_id && (
                    current_user_can('manage_options') ||
                    ($cat_creation_on && $user_karma_pts >= $cat_karma_threshold)
                );
                ?>
                <?php if ($can_create_cat): ?>
                <div style="margin-top:14px;padding-top:12px;border-top:1px solid #f3f4f6;">
                    <button class="ch-btn ch-btn-secondary ch-btn-full ch-btn-sm"
                            onclick="document.getElementById('ch-feed-cat-vis-public').checked=true; chOpenModal('ch-modal-feed-create-cat');"
                            style="font-size:13px;">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        New Category
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($user_id && !empty($followed)): ?>
                <div class="ch-followed-categories" style="margin-top: 18px; padding-top: 12px; border-top: 1px solid var(--ch-border);">
                    <h5 style="margin: 0 0 8px; font-size: 10.5px; font-weight: 700; color: var(--ch-text-subtle); text-transform: uppercase; letter-spacing: 0.8px;">Following</h5>
                    <?php foreach ($followed as $cat_id => $dummy): ?>
                        <?php $cat = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ch_categories WHERE id = %d", $cat_id)); ?>
                        <?php if ($cat): ?>
                        <a href="?cat=<?php echo esc_attr($cat->slug); ?>" class="ch-cat-link <?php echo $cat_slug === $cat->slug ? 'active' : ''; ?>" style="font-size: 14px;">
                            <span class="ch-cat-dot" style="background:<?php echo esc_attr($cat->color); ?>"></span>
                            <?php echo esc_html($cat->name); ?>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="ch-sidebar-widget">
                <h4>Quick Post</h4>
                <?php if ($cat_obj && $cat_obj->is_private && !$user_id): ?>
                <p style="font-size:12px;color:#9ca3af;display:flex;align-items:center;gap:6px;">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Sign in and follow to post here.
                </p>
                <?php else: ?>
                <button class="ch-btn ch-btn-primary ch-btn-full" onclick="chOpenModal('ch-modal-create-post')">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <?php echo $user_id ? 'New Post' : 'Post as Guest'; ?>
                </button>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Feed -->
        <main class="ch-feed-main">
            <div class="ch-feed-header">
                <?php if ($cat_obj): ?>
                <div class="ch-cat-hero" style="border-left: 4px solid <?php echo esc_attr($cat_obj->color); ?>">
                    <div class="ch-cat-hero-main">
                        <div class="ch-cat-hero-text">
                            <h1><?php echo esc_html($cat_obj->name); ?></h1>
                            <p><?php echo esc_html($cat_obj->description); ?></p>
                        </div>
                    </div>

                    <div class="ch-cat-hero-meta">
                        <div class="ch-cat-meta-item">
                            <span class="ch-cat-meta-num"><?php echo number_format($cat_obj->post_count); ?></span>
                            <span class="ch-cat-meta-label">discussions</span>
                        </div>
                        <div class="ch-cat-meta-item">
                            <span class="ch-cat-meta-num"><?php echo number_format($cat_obj->follower_count); ?></span>
                            <span class="ch-cat-meta-label">followers</span>
                        </div>
                        <?php if ($user_id): ?>
                        <?php $is_following = isset($followed[$cat_obj->id]); ?>
                        <button class="ch-btn ch-btn-sm <?php echo $is_following ? 'ch-btn-outline' : 'ch-btn-primary'; ?>" onclick="chToggleFollowCategory(<?php echo $cat_obj->id; ?>, this, '<?php echo esc_attr($nonce); ?>')">
                            <?php echo $is_following ? 'Following' : 'Follow'; ?>
                        </button>
                        <button class="ch-btn ch-btn-sm ch-btn-primary"
                                onclick="chOpenPostInCategory(<?php echo (int)$cat_obj->id; ?>)">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            New Post
                        </button>
                        <?php endif; ?>
                        <?php if ($user_id && ($cat_obj->business_id == $user_id || current_user_can('manage_options'))): ?>
                        <button class="ch-btn ch-btn-sm ch-btn-outline"
                                title="Edit category"
                                onclick="chFeedOpenEditCat(<?php echo (int)$cat_obj->id; ?>, '<?php echo esc_js($cat_obj->name); ?>', '<?php echo esc_js($cat_obj->description ?? ''); ?>', '<?php echo esc_attr($cat_obj->color); ?>')">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Edit
                        </button>
                        <button class="ch-btn ch-btn-sm"
                                style="color:#ef4444;border-color:#fca5a5;"
                                title="Delete category"
                                onclick="chFeedDeleteCat(<?php echo (int)$cat_obj->id; ?>, '<?php echo esc_js($cat_obj->name); ?>', '<?php echo esc_attr(wp_create_nonce('ch_category_nonce')); ?>')">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Delete
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <h2>Community Forum</h2>
                <?php endif; ?>

                <div class="ch-feed-toolbar-card">
                    <form method="get" class="ch-search-form">
                        <?php if ($cat_slug): ?><input type="hidden" name="cat" value="<?php echo esc_attr($cat_slug); ?>"><?php endif; ?>
                        <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search discussions..." class="ch-input ch-search-input">
                        <button type="submit" class="ch-btn ch-btn-primary" style="flex-shrink:0;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            Search
                        </button>
                    </form>
                    <div class="ch-filter-row">
                        <form method="get" class="ch-location-form">
                            <?php if ($cat_slug): ?><input type="hidden" name="cat" value="<?php echo esc_attr($cat_slug); ?>"><?php endif; ?>
                            <?php if ($search): ?><input type="hidden" name="s" value="<?php echo esc_attr($search); ?>"><?php endif; ?>
                            <select name="location" onchange="this.form.submit()" class="ch-location-select">
                                <option value="">All Locations</option>
                                <?php foreach ($available_locations as $loc): ?>
                                <option value="<?php echo esc_attr($loc); ?>" <?php selected($location, $loc); ?>><?php echo esc_html($loc); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <div class="ch-sort-tabs">
                            <a href="?sort=new<?php echo $cat_slug ? '&cat='.$cat_slug : ''; ?><?php echo $location ? '&location='.urlencode($location) : ''; ?><?php echo $search ? '&s='.urlencode($search) : ''; ?>"      class="ch-sort-tab <?php echo $sort==='new'      ?'active':''; ?>">New</a>
                            <a href="?sort=top<?php echo $cat_slug ? '&cat='.$cat_slug : ''; ?><?php echo $location ? '&location='.urlencode($location) : ''; ?><?php echo $search ? '&s='.urlencode($search) : ''; ?>"      class="ch-sort-tab <?php echo $sort==='top'      ?'active':''; ?>">Top</a>
                            <a href="?sort=trending<?php echo $cat_slug ? '&cat='.$cat_slug : ''; ?><?php echo $location ? '&location='.urlencode($location) : ''; ?><?php echo $search ? '&s='.urlencode($search) : ''; ?>" class="ch-sort-tab <?php echo $sort==='trending' ?'active':''; ?>">Trending</a>
                        </div>
                        <a href="#" class="ch-guidelines-link" onclick="chShowGuidelines(); return false;" style="margin-left:auto;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Guidelines
                        </a>
                    </div>
                </div>
            </div>

            <!-- Popular Categories Card (visible to all) -->
            <?php
            $popular_cats = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}ch_categories
                 WHERE status='active' AND (is_private = 0 OR is_private IS NULL)
                 ORDER BY post_count DESC LIMIT 6"
            );
            if (!empty($popular_cats) && !$cat_slug && !$search && $page === 1): ?>
            <div class="ch-popular-cats-card">
                <div class="ch-popular-cats-header">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Popular Categories
                </div>
                <div class="ch-popular-cats-grid">
                    <?php foreach ($popular_cats as $pcat): ?>
                    <a href="?cat=<?php echo esc_attr($pcat->slug); ?>" class="ch-pop-cat-chip">
                        <span class="ch-pop-cat-dot" style="background:<?php echo esc_attr($pcat->color); ?>"></span>
                        <span class="ch-pop-cat-name"><?php echo esc_html($pcat->name); ?></span>
                        <span class="ch-pop-cat-count"><?php echo (int)$pcat->post_count; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Facebook-style Composer Trigger ── -->
            <?php if ($user_id || true): // show for both guests and logged-in ?>
            <?php
                $can_post_here = true;
                if ($cat_obj && $cat_obj->is_private && !$user_id) $can_post_here = false;
                $current_display_feed = $user_id ? (wp_get_current_user()->display_name ?: 'You') : 'Guest';
                $current_initial_feed = strtoupper(substr($current_display_feed, 0, 1));
            ?>
            <div class="ch-composer-trigger-card" onclick="<?php echo $can_post_here ? "chOpenModal('ch-modal-create-post')" : "void(0)"; ?>" role="button" tabindex="0"
                 onkeydown="if(event.key==='Enter'||event.key===' '){<?php echo $can_post_here ? "chOpenModal('ch-modal-create-post')" : ''; ?>}">
                <div class="ch-composer-trigger-avatar <?php echo !$user_id ? 'ch-composer-trigger-guest' : ''; ?>">
                    <?php if ($user_id): ?>
                        <?php echo esc_html($current_initial_feed); ?>
                    <?php else: ?>
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?php endif; ?>
                </div>
                <?php if ($can_post_here): ?>
                <div class="ch-composer-trigger-input">
                    <?php echo $user_id
                        ? 'What\'s on your mind, ' . esc_html(explode(' ', $current_display_feed)[0]) . '?'
                        : 'Share something with the community…'; ?>
                </div>
                <div class="ch-composer-trigger-actions">
                    <span class="ch-composer-trigger-btn">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Photo
                    </span>
                    <span class="ch-composer-trigger-btn ch-composer-trigger-btn-tag">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        Tag
                    </span>
                </div>
                <?php else: ?>
                <div class="ch-composer-trigger-input ch-composer-trigger-locked">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Sign in and follow this category to post here.
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="ch-posts-list" id="ch-posts-list">
                <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                <article class="ch-announcement-card">
                    <div class="ch-announcement-header">
                        <div class="ch-announcement-icon">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                        </div>
                        <div class="ch-announcement-meta">
                            <span class="ch-announcement-label">Community Announcement</span>
                            <span class="ch-announcement-author">by <?php echo esc_html($announcement->admin_name ?? 'Admin'); ?></span>
                            <span class="ch-announcement-time"><?php echo human_time_diff(strtotime($announcement->created_at), current_time('timestamp')); ?> ago</span>
                        </div>
                    </div>
                    <div class="ch-announcement-body">
                        <h4 class="ch-announcement-title"><?php echo esc_html($announcement->title); ?></h4>
                        <div class="ch-announcement-content"><?php echo wp_kses_post(wpautop($announcement->content)); ?></div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if (empty($posts)): ?>
                <div class="ch-empty-state">
                    <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <p>No posts yet. Be the first to start a discussion!</p>
                </div>
                <?php else: foreach ($posts as $post): ?>
                <article class="ch-post-card" data-id="<?php echo (int)$post->id; ?>" data-category="<?php echo $post->category_id; ?>" data-anonymous="<?php echo $post->is_anonymous; ?>">
                    <?php if ($post->is_pinned): ?>
                    <div class="ch-post-pinned-ribbon">
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        Pinned
                    </div>
                    <?php endif; ?>
                    <div class="ch-post-vote-col">
                        <?php if ($user_id):
                            $uv = $user_post_votes[$post->id] ?? 0; ?>
                        <button class="ch-vote-btn ch-vote-up <?php echo $uv === 1 ? 'active-up' : ''; ?>" data-id="<?php echo (int)$post->id; ?>" data-type="post" data-val="1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                        </button>
                        <?php endif; ?>
                        <span class="ch-vote-count"><?php echo (int)$post->vote_count; ?></span>
                        <?php if ($user_id): ?>
                        <button class="ch-vote-btn ch-vote-down <?php echo $uv === -1 ? 'active-down' : ''; ?>" data-id="<?php echo (int)$post->id; ?>" data-type="post" data-val="-1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="ch-post-body">
                        <div class="ch-post-meta-row">
                            <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                                <?php echo esc_html($post->cat_name ?? 'General'); ?>
                            </span>
                            <span class="ch-post-author">
                                <?php if ($post->is_anonymous): ?>
                                Anonymous
                                <?php else: ?>
                                <?php echo esc_html($post->author_name ?? 'Community Member'); ?>
                                <?php endif; ?>
                            </span>
                            <?php if (!$post->is_anonymous && !empty($post->author_location)): ?>
                            <span class="ch-post-location">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <?php echo esc_html($post->author_location); ?>
                            </span>
                            <?php endif; ?>
                            <span class="ch-post-time"><?php echo human_time_diff(strtotime($post->created_at), current_time('timestamp')); ?> ago</span>
                        </div>
                        <h3 class="ch-post-title">
                            <a href="?view_post=<?php echo $post->rand_id; ?>"><?php echo esc_html($post->title); ?></a>
                        </h3>
                        <p class="ch-post-preview"><?php echo esc_html(wp_trim_words(strip_tags($post->content), 25)); ?></p>
                        <?php if ($post->tags): ?>
                        <div class="ch-post-tags" data-tags="<?php echo esc_attr($post->tags); ?>">
                            <?php foreach (explode(',', $post->tags) as $tag): ?>
                            <span class="ch-tag">#<?php echo esc_html(trim($tag)); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="ch-post-actions-row">
                            <a href="?view_post=<?php echo $post->rand_id; ?>" class="ch-post-action">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <?php echo (int)$post->comment_count; ?> comments
                            </a>
                            <?php if ($user_id): ?>
                            <button class="ch-post-action <?php echo isset($user_bookmarks[$post->id]) ? 'ch-bookmarked' : ''; ?>"
                                    onclick="chBookmark(<?php echo (int)$post->id; ?>, this, '<?php echo esc_attr($nonce); ?>')">
                                <svg width="14" height="14" fill="<?php echo isset($user_bookmarks[$post->id]) ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                                <?php echo isset($user_bookmarks[$post->id]) ? 'Saved' : 'Save'; ?>
                            </button>
                            <button class="ch-post-action" onclick="chReportPost(<?php echo (int)$post->id; ?>, '<?php echo esc_attr($nonce); ?>')">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                                Report
                            </button>
                            <?php if ($user_id && ($post->user_id !== 0 && $post->user_id == $user_id || current_user_can('manage_options'))): ?>
                            <button class="ch-post-action ch-post-action-edit"
                                    onclick="chOpenEditPostModal(<?php echo (int)$post->id; ?>)">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Edit
                            </button>
                            <button class="ch-post-action ch-post-action-delete"
                                    onclick="chDeletePost(<?php echo (int)$post->id; ?>, '<?php echo esc_attr(wp_create_nonce('ch_post_view_nonce')); ?>')">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                Delete
                            </button>
                            <?php endif; ?>
                            <?php endif; ?>
                            <div class="ch-share-dropdown">
                                <button class="ch-post-action ch-share-btn" onclick="chToggleShareMenu(this)">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                    Share
                                </button>
                                <div class="ch-share-menu">
                                    <button class="ch-share-option" onclick="chShareToSocial('twitter', '<?php echo get_permalink(); ?>?view_post=<?php echo $post->rand_id; ?>', '<?php echo esc_attr($post->title); ?>')">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                                        Twitter
                                    </button>
                                    <button class="ch-share-option" onclick="chShareToSocial('facebook', '<?php echo get_permalink(); ?>?view_post=<?php echo $post->rand_id; ?>', '<?php echo esc_attr($post->title); ?>')">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        Facebook
                                    </button>
                                    <button class="ch-share-option" onclick="chShareToSocial('linkedin', '<?php echo get_permalink(); ?>?view_post=<?php echo $post->rand_id; ?>', '<?php echo esc_attr($post->title); ?>')">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                        LinkedIn
                                    </button>
                                    <button class="ch-share-option" onclick="chShareToSocial('copy', '<?php echo get_permalink(); ?>?view_post=<?php echo $post->rand_id; ?>')">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        Copy Link
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="ch-pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?paged=<?php echo $i; ?>&sort=<?php echo $sort; ?><?php echo $cat_slug ? '&cat='.$cat_slug : ''; ?>"
                   class="ch-page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Create Post Modal -->
    <!-- Create Post Modal — Facebook-style composer -->
    <div id="ch-modal-create-post" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg ch-composer-modal">
            <div class="ch-composer-header">
                <h3>Create Post</h3>
                <button class="ch-composer-close" onclick="chCloseModal('ch-modal-create-post')" aria-label="Close">&times;</button>
            </div>
            <div class="ch-composer-divider"></div>

            <div class="ch-composer-body">
                <!-- Author row -->
                <div class="ch-composer-author-row">
                    <div class="ch-composer-avatar">
                        <?php if ($user_id): ?>
                            <?php $cd = wp_get_current_user()->display_name ?: 'U'; echo esc_html(strtoupper(substr($cd, 0, 1))); ?>
                        <?php else: ?>G<?php endif; ?>
                    </div>
                    <div class="ch-composer-author-info">
                        <div class="ch-composer-author-name">
                            <?php echo $user_id ? esc_html(wp_get_current_user()->display_name ?: 'You') : 'Guest'; ?>
                        </div>
                        <div class="ch-composer-meta-row">
                            <select id="ch-post-cat" class="ch-composer-cat-select">
                                <option value="">📂 Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($user_id): ?>
                            <label class="ch-composer-anon-toggle">
                                <input type="checkbox" id="ch-post-anon">
                                <span>Anonymous</span>
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Guest name field -->
                <?php if (!$user_id): ?>
                <input type="text" id="ch-post-guest-name" class="ch-composer-guest-name" placeholder="Your name (leave blank to post anonymously)" maxlength="100">
                <?php endif; ?>

                <!-- Title field -->
                <input type="text" id="ch-post-title" class="ch-composer-title" placeholder="Write a title…">

                <!-- Main content area -->
                <textarea id="ch-post-content" class="ch-composer-textarea" rows="4"
                    placeholder="What's on your mind? Share your thoughts, concerns, or suggestions with the community…"></textarea>

                <!-- Tags -->
                <div class="ch-composer-tags-row">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <input type="text" id="ch-post-tags" class="ch-composer-tags-input" placeholder="Tags: road, drainage, urgent…">
                </div>

                <!-- Media upload area -->
                <label class="ch-composer-media-area" id="ch-post-media-label" for="ch-post-media">
                    <div class="ch-composer-media-inner">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>Add photos / videos</span>
                        <span class="ch-composer-media-sub">or drag and drop</span>
                    </div>
                    <input type="file" id="ch-post-media" style="display:none" multiple accept="image/*,video/*,audio/*"
                        onchange="chUpdateMediaLabel(this, 'ch-post-media-label')">
                </label>
                <div id="ch-post-media-preview" class="ch-composer-media-preview"></div>
            </div>

            <div id="ch-post-msg"></div>

            <div class="ch-composer-footer">
                <div class="ch-composer-footer-hint">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Be respectful and follow community guidelines.
                </div>
                <button class="ch-btn ch-btn-primary ch-composer-submit" id="ch-submit-post-btn"
                    onclick="chSubmitPost('<?php echo $user_id ? esc_attr(wp_create_nonce('ch_feed_nonce')) : ''; ?>')">
                    Post
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Post Modal — Facebook-style composer -->
    <?php if ($user_id): ?>
    <div id="ch-modal-edit-post" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg ch-composer-modal">
            <div class="ch-composer-header">
                <h3>Edit Post</h3>
                <button class="ch-composer-close" onclick="chCloseModal('ch-modal-edit-post')" aria-label="Close">&times;</button>
            </div>
            <div class="ch-composer-divider"></div>

            <div class="ch-composer-body">
                <input type="hidden" id="ch-edit-post-id">

                <div class="ch-composer-author-row">
                    <div class="ch-composer-avatar">
                        <?php $cd = wp_get_current_user()->display_name ?: 'U'; echo esc_html(strtoupper(substr($cd, 0, 1))); ?>
                    </div>
                    <div class="ch-composer-author-info">
                        <div class="ch-composer-author-name"><?php echo esc_html(wp_get_current_user()->display_name ?: 'You'); ?></div>
                        <div class="ch-composer-meta-row">
                            <select id="ch-edit-post-cat" class="ch-composer-cat-select">
                                <option value="">📂 Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="ch-composer-anon-toggle">
                                <input type="checkbox" id="ch-edit-post-anon">
                                <span>Anonymous</span>
                            </label>
                        </div>
                    </div>
                </div>

                <input type="text" id="ch-edit-post-title" class="ch-composer-title" placeholder="Write a title…">
                <textarea id="ch-edit-post-content" class="ch-composer-textarea" rows="4"
                    placeholder="What's on your mind?"></textarea>

                <div class="ch-composer-tags-row">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <input type="text" id="ch-edit-post-tags" class="ch-composer-tags-input" placeholder="Tags: road, drainage, urgent…">
                </div>

                <label class="ch-composer-media-area" id="ch-edit-post-media-label" for="ch-edit-post-media">
                    <div class="ch-composer-media-inner">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>Add photos / videos</span>
                        <span class="ch-composer-media-sub">or drag and drop</span>
                    </div>
                    <input type="file" id="ch-edit-post-media" style="display:none" multiple accept="image/*,video/*,audio/*"
                        onchange="chUpdateMediaLabel(this, 'ch-edit-post-media-label')">
                </label>
                <div id="ch-edit-post-media-preview" class="ch-composer-media-preview"></div>
            </div>

            <div id="ch-edit-post-msg"></div>

            <div class="ch-composer-footer">
                <div class="ch-composer-footer-hint">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Edit your post. Changes are visible immediately.
                </div>
                <button class="ch-btn ch-btn-primary ch-composer-submit" id="ch-update-post-btn"
                    onclick="chUpdatePost('<?php echo esc_attr(wp_create_nonce('ch_post_view_nonce')); ?>')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Report Modal -->
    <div id="ch-modal-report" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal">
            <div class="ch-modal-header">
                <h3>Report Content</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-report')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <input type="hidden" id="ch-report-target-id">
                <input type="hidden" id="ch-report-target-type">
                <div class="ch-field-group">
                    <label class="ch-label">Reason *</label>
                    <select id="ch-report-reason" class="ch-input">
                        <option value="">Select reason</option>
                        <option value="spam">Spam or Misleading</option>
                        <option value="harassment">Harassment or Bullying</option>
                        <option value="inappropriate">Inappropriate Content</option>
                        <option value="misinformation">Misinformation</option>
                        <option value="hate">Hate Speech</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Additional Details</label>
                    <textarea id="ch-report-details" class="ch-input ch-textarea" rows="3" placeholder="Provide more context..."></textarea>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-report')">Cancel</button>
                <button class="ch-btn ch-btn-danger" onclick="chSubmitReport('<?php echo esc_attr($nonce); ?>')">Submit Report</button>
            </div>
            <div id="ch-report-msg"></div>
        </div>
    </div>

    <!-- Community Guidelines Modal -->
    <div id="ch-modal-guidelines" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal">
            <div class="ch-modal-header">
                <h3>Community Guidelines</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-guidelines')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <div class="ch-guidelines-content">
                    <?php echo ch_get_guidelines_html(); ?>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-primary" onclick="chCloseModal('ch-modal-guidelines')">I Understand</button>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <?php if (isset($can_create_cat) && $can_create_cat): ?>
    <!-- Edit Category Modal (feed sidebar — for category owners) -->
    <div id="ch-modal-feed-edit-cat" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal">
            <div class="ch-modal-header">
                <h3>Edit Category</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-feed-edit-cat')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <input type="hidden" id="ch-feed-edit-cat-id">
                <div class="ch-field-group">
                    <label class="ch-label">Category Name <span class="ch-required">*</span></label>
                    <input type="text" id="ch-feed-edit-cat-name" class="ch-input" placeholder="Category name">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Description</label>
                    <textarea id="ch-feed-edit-cat-desc" class="ch-input ch-textarea" rows="3"></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Color</label>
                    <div class="ch-color-picker-wrap">
                    <div class="ch-color-row">
                        <input type="color" id="ch-feed-edit-cat-color-wheel" class="ch-color-wheel" value="#FF7551"
                               oninput="chSyncColorWheel('ch-feed-edit-cat-color-wheel','ch-feed-edit-cat-color','ch-feed-edit-cat-color-preview')">
                        <span class="ch-color-hex-preview" id="ch-feed-edit-cat-color-preview" style="background:#FF7551;"></span>
                        <input type="text" id="ch-feed-edit-cat-color" class="ch-input ch-color-hex-input" value="#FF7551" placeholder="#FF7551" maxlength="7"
                               oninput="chSyncColorHex('ch-feed-edit-cat-color-wheel','ch-feed-edit-cat-color','ch-feed-edit-cat-color-preview')">
                    </div>
                    <div class="ch-color-swatches">
                        <?php foreach (['#FF7551','#FF6640','#ef4444','#f59e0b','#10b981','#06b6d4','#8b5cf6','#ec4899','#f97316','#84cc16'] as $sw): ?>
                        <button type="button" class="ch-swatch" style="background:<?php echo $sw; ?>;" title="<?php echo $sw; ?>"
                                onclick="chPickSwatch('ch-feed-edit-cat-color-wheel','ch-feed-edit-cat-color','ch-feed-edit-cat-color-preview','<?php echo $sw; ?>')"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-feed-edit-cat')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-feed-edit-cat-btn"
                        onclick="chFeedSaveEditCat('<?php echo esc_attr(wp_create_nonce('ch_category_nonce')); ?>')">
                    Save Changes
                </button>
            </div>
            <div id="ch-feed-edit-cat-msg"></div>
        </div>
    </div>

    <!-- New Category Modal (feed sidebar) -->
    <div id="ch-modal-feed-create-cat" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal">
            <div class="ch-modal-header">
                <h3>Create New Category</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-feed-create-cat')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <div class="ch-field-group">
                    <label class="ch-label">Category Name <span class="ch-required">*</span></label>
                    <input type="text" id="ch-feed-cat-name" class="ch-input" placeholder="e.g., Road Safety">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Description</label>
                    <textarea id="ch-feed-cat-desc" class="ch-input ch-textarea" rows="3"
                              placeholder="What topics belong in this category?"></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Color</label>
                    <div class="ch-color-picker-wrap">
                    <div class="ch-color-row">
                        <input type="color" id="ch-feed-cat-color-wheel" class="ch-color-wheel" value="#FF7551"
                               oninput="chSyncColorWheel('ch-feed-cat-color-wheel','ch-feed-cat-color','ch-feed-cat-color-preview')">
                        <span class="ch-color-hex-preview" id="ch-feed-cat-color-preview" style="background:#FF7551;"></span>
                        <input type="text" id="ch-feed-cat-color" class="ch-input ch-color-hex-input" value="#FF7551" placeholder="#FF7551" maxlength="7"
                               oninput="chSyncColorHex('ch-feed-cat-color-wheel','ch-feed-cat-color','ch-feed-cat-color-preview')">
                    </div>
                    <div class="ch-color-swatches">
                        <?php foreach (['#FF7551','#FF6640','#ef4444','#f59e0b','#10b981','#06b6d4','#8b5cf6','#ec4899','#f97316','#84cc16'] as $sw): ?>
                        <button type="button" class="ch-swatch" style="background:<?php echo $sw; ?>;" title="<?php echo $sw; ?>"
                                onclick="chPickSwatch('ch-feed-cat-color-wheel','ch-feed-cat-color','ch-feed-cat-color-preview','<?php echo $sw; ?>')"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Visibility</label>
                    <div class="ch-visibility-toggle">
                        <label class="ch-vis-option">
                            <input type="radio" name="ch-feed-cat-visibility" id="ch-feed-cat-vis-public" value="0" checked>
                            <span class="ch-vis-label">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                Public
                            </span>
                            <span class="ch-vis-desc">Anyone can view and guests can post</span>
                        </label>
                        <label class="ch-vis-option">
                            <input type="radio" name="ch-feed-cat-visibility" id="ch-feed-cat-vis-private" value="1">
                            <span class="ch-vis-label">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Private
                            </span>
                            <span class="ch-vis-desc">Only followers can view and post</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-feed-create-cat')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-feed-save-cat-btn"
                        onclick="chFeedSaveCategory('<?php echo esc_attr(wp_create_nonce('ch_category_nonce')); ?>')">
                    Create Category
                </button>
            </div>
            <div id="ch-feed-cat-msg"></div>
        </div>
    </div>
    <script>
    (function() {
        window.chFeedSaveCategory = function(nonce) {
            const name  = document.getElementById('ch-feed-cat-name').value.trim();
            const desc  = document.getElementById('ch-feed-cat-desc').value.trim();
            const color = document.getElementById('ch-feed-cat-color').value;
            if (!name) {
                document.getElementById('ch-feed-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-error">Please enter a category name.</div>';
                return;
            }
            const btn = document.getElementById('ch-feed-save-cat-btn');
            btn.disabled = true; btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Creating...</span>';
            const fd = new FormData();
            fd.append('action',      'ch_create_category');
            fd.append('name',        name);
            fd.append('description', desc);
            fd.append('color',       color);
            fd.append('sort_order',  0);
            fd.append('is_private',  document.getElementById('ch-feed-cat-vis-private')?.checked ? 1 : 0);
            fd.append('nonce',       nonce);
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-feed-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">'
                    + (json.data?.message || '') + '</div>';
                if (json.success) {
                    // The server already auto-followed; just reload
                    chCloseModal('ch-modal-feed-create-cat'); chReloadAfterSuccess(0);
                } else {
                    btn.disabled = false; btn.textContent = 'Create Category';
                }
            })
            .catch(() => {
                document.getElementById('ch-feed-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-error">Network error. Please try again.</div>';
                btn.disabled = false; btn.textContent = 'Create Category';
            });
        };
    })();
    </script>
    <?php endif; ?>

    <?php if (isset($can_create_cat) && $can_create_cat): ?>
    <script>
    (function() {
        const _catNonce = '<?php echo esc_attr(wp_create_nonce('ch_category_nonce')); ?>';

        // Open the edit modal pre-filled
        window.chFeedOpenEditCat = function(id, name, desc, color) {
            document.getElementById('ch-feed-edit-cat-id').value    = id;
            document.getElementById('ch-feed-edit-cat-name').value  = name;
            document.getElementById('ch-feed-edit-cat-desc').value  = desc;
            document.getElementById('ch-feed-edit-cat-color').value = color.toUpperCase();
            const wheel = document.getElementById('ch-feed-edit-cat-color-wheel');
            if (wheel) wheel.value = color;
            const prev = document.getElementById('ch-feed-edit-cat-color-preview');
            if (prev) { prev.style.background = color; prev.style.opacity = '1'; }
            document.getElementById('ch-feed-edit-cat-msg').innerHTML = '';
            chOpenModal('ch-modal-feed-edit-cat');
        };

        // Save the edited category
        window.chFeedSaveEditCat = function(nonce) {
            const id    = document.getElementById('ch-feed-edit-cat-id').value;
            const name  = document.getElementById('ch-feed-edit-cat-name').value.trim();
            const desc  = document.getElementById('ch-feed-edit-cat-desc').value.trim();
            const color = document.getElementById('ch-feed-edit-cat-color').value;
            if (!name) {
                document.getElementById('ch-feed-edit-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-error">Category name is required.</div>';
                return;
            }
            const btn = document.getElementById('ch-feed-edit-cat-btn');
            btn.disabled = true; btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving...</span>';
            const fd = new FormData();
            fd.append('action',      'ch_edit_category');
            fd.append('category_id', id);
            fd.append('name',        name);
            fd.append('description', desc);
            fd.append('color',       color);
            fd.append('nonce',       nonce);
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-feed-edit-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">'
                    + (json.data?.message || '') + '</div>';
                if (json.success) {
                    chCloseModal('ch-modal-feed-edit-cat'); chReloadAfterSuccess(0);
                } else {
                    btn.disabled = false; btn.textContent = 'Save Changes';
                }
            })
            .catch(() => {
                document.getElementById('ch-feed-edit-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-error">Network error.</div>';
                btn.disabled = false; btn.textContent = 'Save Changes';
            });
        };

        // Delete a category (with confirmation)
        window.chFeedDeleteCat = function(id, name, nonce) {
            if (!confirm('Delete category "' + name + '"? This cannot be undone.')) return;
            const fd = new FormData();
            fd.append('action',      'ch_delete_category');
            fd.append('category_id', id);
            fd.append('nonce',       nonce);
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) chReloadAfterSuccess(0);
                else alert(json.data?.message || 'Could not delete category.');
            })
            .catch(() => alert('Network error.'));
        };
    })();
    </script>
    <?php endif; ?>

    <script>
    (function() {
        // Open the create-post modal pre-set to a specific category
        window.chOpenPostInCategory = function(catId) {
            const sel = document.getElementById('ch-post-cat');
            if (sel) sel.value = catId;
            chOpenModal('ch-modal-create-post');
        };
    })();
    </script>

    <!-- ===== WELCOME POPUP (new visitors only) ===== -->
    <?php if (!$user_id): ?>
    <div id="ch-welcome-popup" class="ch-welcome-overlay" style="display:none;">
        <div class="ch-welcome-card">
            <div class="ch-welcome-glow"></div>
            <button class="ch-welcome-close" onclick="chCloseWelcome()">&times;</button>
            <div class="ch-welcome-icon">
                <svg width="36" height="36" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="1.8">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <h2 class="ch-welcome-title">Welcome to CivicHub!</h2>
            <p class="ch-welcome-subtitle">Your community's space to discuss, share, and connect with neighbors.</p>
            <div class="ch-welcome-features">
                <div class="ch-welcome-feature">
                    <span class="ch-welcome-feat-icon">💬</span>
                    <span>Join discussions on local issues</span>
                </div>
                <div class="ch-welcome-feature">
                    <span class="ch-welcome-feat-icon">📌</span>
                    <span>Follow topics that matter to you</span>
                </div>
                <div class="ch-welcome-feature">
                    <span class="ch-welcome-feat-icon">🗳️</span>
                    <span>Vote and share your opinions</span>
                </div>
            </div>
            <div class="ch-welcome-actions">
                <?php
                $auth_pg = get_page_by_path('login-register');
                $reg_link = $auth_pg ? add_query_arg('tab','register', get_permalink($auth_pg)) : wp_registration_url();
                $login_link = $auth_pg ? get_permalink($auth_pg) : wp_login_url(get_permalink());
                ?>
                <a href="<?php echo esc_url($reg_link); ?>" class="ch-btn ch-btn-primary" style="flex:1;justify-content:center;">Join Free</a>
                <a href="<?php echo esc_url($login_link); ?>" class="ch-btn ch-btn-secondary" style="flex:1;justify-content:center;">Sign In</a>
            </div>
            <button class="ch-welcome-skip" onclick="chCloseWelcome()">Browse as guest</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== SETTINGS MODAL (logged-in users) ===== -->
    <?php if ($user_id): ?>
    <div id="ch-modal-settings" class="ch-modal-overlay" style="display:none;">
        <div class="ch-modal" style="max-width:420px;">
            <div class="ch-modal-header">
                <h3>Settings</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-settings')">&times;</button>
            </div>
            <div class="ch-modal-body" style="padding:0;">

                <!-- Appearance -->
                <div class="ch-settings-section">
                    <div class="ch-settings-section-title">Appearance</div>
                    <div class="ch-settings-row">
                        <div class="ch-settings-row-info">
                            <div class="ch-settings-row-label">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                                Dark Mode
                            </div>
                            <div class="ch-settings-row-desc">Switch to a darker interface</div>
                        </div>
                        <label class="ch-toggle">
                            <input type="checkbox" id="ch-dark-mode-toggle" onchange="chToggleDarkMode(this.checked)">
                            <span class="ch-toggle-track"><span class="ch-toggle-thumb"></span></span>
                        </label>
                    </div>
                </div>

                <!-- Account -->
                <div class="ch-settings-section">
                    <div class="ch-settings-section-title">Account</div>
                    <a href="?tab=profile" class="ch-settings-row ch-settings-row-link">
                        <div class="ch-settings-row-info">
                            <div class="ch-settings-row-label">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Edit Profile
                            </div>
                            <div class="ch-settings-row-desc">Change name, bio, location, avatar</div>
                        </div>
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                    <a href="?bookmarks=1" class="ch-settings-row ch-settings-row-link">
                        <div class="ch-settings-row-info">
                            <div class="ch-settings-row-label">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                                My Bookmarks
                            </div>
                            <div class="ch-settings-row-desc">View your saved posts</div>
                        </div>
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

                <!-- Community -->
                <div class="ch-settings-section">
                    <div class="ch-settings-section-title">Community</div>
                    <div class="ch-settings-row">
                        <div class="ch-settings-row-info">
                            <div class="ch-settings-row-label">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Community Guidelines
                            </div>
                            <div class="ch-settings-row-desc">Review the rules of the forum</div>
                        </div>
                        <button onclick="chCloseModal('ch-modal-settings'); chShowGuidelines();" style="background:none;border:none;cursor:pointer;color:var(--ch-accent);font-size:12px;font-weight:600;font-family:var(--ch-font);">View</button>
                    </div>
                </div>

                <!-- Danger zone -->
                <div class="ch-settings-section" style="border-bottom:none;">
                    <div class="ch-settings-section-title" style="color:#ef4444;">Account Actions</div>
                    <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="ch-settings-row ch-settings-row-link" style="color:#ef4444;">
                        <div class="ch-settings-row-info">
                            <div class="ch-settings-row-label" style="color:#ef4444;">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                Sign Out
                            </div>
                            <div class="ch-settings-row-desc">Log out of your account</div>
                        </div>
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    (function() {
        // ---- Welcome popup ----
        window.chCloseWelcome = function() {
            const el = document.getElementById('ch-welcome-popup');
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'scale(0.96)';
                setTimeout(() => { el.style.display = 'none'; }, 250);
            }
            try { sessionStorage.setItem('ch_welcomed', '1'); } catch(e) {}
        };
        // Show welcome after short delay, only once per session
        const popup = document.getElementById('ch-welcome-popup');
        if (popup) {
            try {
                if (!sessionStorage.getItem('ch_welcomed')) {
                    setTimeout(() => {
                        popup.style.display = 'flex';
                        popup.style.opacity = '0';
                        popup.style.transform = 'scale(0.94)';
                        requestAnimationFrame(() => {
                            popup.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            popup.style.opacity = '1';
                            popup.style.transform = 'scale(1)';
                        });
                    }, 900);
                }
            } catch(e) {}
        }
        // Close welcome on overlay click
        if (popup) {
            popup.addEventListener('click', function(e) {
                if (e.target === popup) chCloseWelcome();
            });
        }

        // ---- Settings modal ----
        window.chOpenSettingsModal = function() {
            // Sync dark mode toggle state
            const isDark = document.documentElement.classList.contains('ch-dark');
            const tog = document.getElementById('ch-dark-mode-toggle');
            if (tog) tog.checked = isDark;
            chOpenModal('ch-modal-settings');
        };
        window.chCloseProfileMenu = function() {
            const m = document.getElementById('ch-profile-menu');
            if (m) m.style.display = 'none';
        };

        // ---- Dark mode ----
        window.chToggleDarkMode = function(enabled) {
            if (enabled) {
                document.documentElement.classList.add('ch-dark');
                try { localStorage.setItem('ch_dark_mode', '1'); } catch(e) {}
            } else {
                document.documentElement.classList.remove('ch-dark');
                try { localStorage.removeItem('ch_dark_mode'); } catch(e) {}
            }
        };
        // Apply dark mode on load
        try {
            if (localStorage.getItem('ch_dark_mode') === '1') {
                document.documentElement.classList.add('ch-dark');
            }
        } catch(e) {}
    })();
    </script>

    <?php echo ch_global_styles(); ?>
    <?php echo ch_global_scripts(); ?>
    <?php echo ch_feed_scripts(); ?>
    <?php
    return ob_get_clean();
}

// ============================================================
// FRONTEND: POST VIEW
// ============================================================

function bntm_shortcode_ch_post_view() {
    global $wpdb;

    $rand_id = sanitize_text_field($_GET['view_post'] ?? '');
    if (!$rand_id) return '<p>Post not found.</p>';

    $post = $wpdb->get_row($wpdb->prepare(
        "SELECT p.*, c.name as cat_name, c.color as cat_color, c.slug as cat_slug,
                u.display_name as author_name, u.karma_points as author_karma, u.bio as author_bio
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
         WHERE p.rand_id = %s AND p.status = 'active'",
        $rand_id
    ));

    if (!$post) return '<p class="ch-empty">Post not found or has been removed.</p>';

    // Private category access check
    if ($post->cat_slug) {
        $cat_privacy = $wpdb->get_var($wpdb->prepare(
            "SELECT is_private FROM {$wpdb->prefix}ch_categories WHERE id = %d", $post->category_id
        ));
        if ($cat_privacy) {
            $viewer = get_current_user_id();
            if (!$viewer) {
                $auth_url = get_page_by_path('login-register') ? get_permalink(get_page_by_path('login-register')) : wp_login_url(get_permalink());
                return '<div style="padding:60px 20px;text-align:center;font-family:-apple-system,sans-serif;">
                    <svg width="48" height="48" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5" style="display:block;margin:0 auto 16px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <h3 style="color:var(--ch-text);margin:0 0 8px">Private Category</h3>
                    <p style="color:#9ca3af;margin:0 0 16px">Please sign in and follow this category to view this post.</p>
                    <a href="' . esc_url($auth_url) . '" style="background:#FF7551;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600;">Sign In</a>
                </div>';
            }
            $is_following = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ch_follows WHERE user_id = %d AND category_id = %d",
                $viewer, $post->category_id
            ));
            if (!$is_following && !current_user_can('manage_options')) {
                return '<div style="padding:60px 20px;text-align:center;font-family:-apple-system,sans-serif;">
                    <svg width="48" height="48" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5" style="display:block;margin:0 auto 16px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <h3 style="color:var(--ch-text);margin:0 0 8px">Private Category</h3>
                    <p style="color:#9ca3af;margin:0 0 4px">Follow <strong>' . esc_html($post->cat_name) . '</strong> to access this post.</p>
                </div>';
            }
        }
    }

    // Increment view count
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_posts SET view_count = view_count + 1 WHERE rand_id = %s", $rand_id));

    $comments = $wpdb->get_results($wpdb->prepare(
        "SELECT cm.*, u.display_name as author_name
         FROM {$wpdb->prefix}ch_comments cm
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON cm.user_id = u.user_id
         WHERE cm.post_id = %d AND cm.status = 'active' AND cm.parent_id = 0
         ORDER BY cm.created_at ASC",
        $post->id
    ));

    $replies_map = [];
    foreach ($comments as $cm) {
        $replies = $wpdb->get_results($wpdb->prepare(
            "SELECT cm2.*, u.display_name as author_name
             FROM {$wpdb->prefix}ch_comments cm2
             LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON cm2.user_id = u.user_id
             WHERE cm2.parent_id = %d AND cm2.status = 'active'
             ORDER BY cm2.created_at ASC",
            $cm->id
        ));
        $replies_map[$cm->id] = $replies;
    }

    $user_id = get_current_user_id();
    $nonce   = wp_create_nonce('ch_post_view_nonce');

    // Fetch current user's vote on this post and all comments
    $user_vote_on_post    = 0;
    $user_comment_votes   = [];
    if ($user_id) {
        $all_comment_ids = array_map(fn($c) => (int)$c->id, $comments);
        foreach ($replies_map as $rlist) {
            foreach ($rlist as $r) { $all_comment_ids[] = (int)$r->id; }
        }

        $pv = $wpdb->get_var($wpdb->prepare(
            "SELECT value FROM {$wpdb->prefix}ch_votes WHERE user_id=%d AND target_type='post' AND target_id=%d",
            $user_id, $post->id
        ));
        $user_vote_on_post = (int)($pv ?? 0);

        if (!empty($all_comment_ids)) {
            $ph   = implode(',', array_fill(0, count($all_comment_ids), '%d'));
            $cvs  = $wpdb->get_results($wpdb->prepare(
                "SELECT target_id, value FROM {$wpdb->prefix}ch_votes
                 WHERE user_id = %d AND target_type = 'comment' AND target_id IN ($ph)",
                array_merge([$user_id], $all_comment_ids)
            ));
            foreach ($cvs as $cv) {
                $user_comment_votes[(int)$cv->target_id] = (int)$cv->value;
            }
        }
    }

    // Get categories for modals
    $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ch_categories WHERE status='active' ORDER BY name ASC");

    ob_start(); ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>

    <div class="ch-post-view-wrap">
        <div class="ch-post-view-header">
            <a href="<?php echo remove_query_arg('view_post'); ?>" class="ch-back-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Forum
            </a>
        </div>

        <div class="ch-post-view-grid">
            <div class="ch-post-view-main">
                <article class="ch-post-full">
                    <div class="ch-post-meta-row">
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                            <?php echo esc_html($post->cat_name ?? 'General'); ?>
                        </span>
                        <span class="ch-post-author">
                            <?php
                            if ($post->user_id == 0 && !empty($post->guest_name)) {
                                echo esc_html($post->guest_name) . ' <span style="font-size:11px;color:#9ca3af;">(guest)</span>';
                            } elseif ($post->is_anonymous) {
                                echo 'Anonymous';
                            } else {
                                echo esc_html($post->author_name ?? 'Community Member');
                            }
                            ?>
                        </span>
                        <span class="ch-post-time"><?php echo human_time_diff(strtotime($post->created_at), current_time('timestamp')); ?> ago</span>
                        <span class="ch-post-views"><?php echo number_format($post->view_count); ?> views</span>
                    </div>

                    <h1 class="ch-post-full-title"><?php echo esc_html($post->title); ?></h1>

                    <div class="ch-post-full-content">
                        <?php echo wp_kses_post(nl2br($post->content)); ?>
                    </div>

                    <?php if ($post->media_urls): ?>
                    <div class="ch-post-media">
                        <?php $media = json_decode($post->media_urls, true);
                        foreach ($media as $url): ?>
                        <div class="ch-media-item">
                            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $url)): ?>
                            <img src="<?php echo esc_url($url); ?>" alt="Media" class="ch-media-image">
                            <?php elseif (preg_match('/\.(mp4|webm|ogg)$/i', $url)): ?>
                            <video controls class="ch-media-video">
                                <source src="<?php echo esc_url($url); ?>" type="video/mp4">
                            </video>
                            <?php elseif (preg_match('/\.(mp3|wav|ogg)$/i', $url)): ?>
                            <audio controls class="ch-media-audio">
                                <source src="<?php echo esc_url($url); ?>" type="audio/mpeg">
                            </audio>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($post->tags): ?>
                    <div class="ch-post-tags">
                        <?php foreach (explode(',', $post->tags) as $tag): ?>
                        <span class="ch-tag">#<?php echo esc_html(trim($tag)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="ch-post-vote-bar">
                        <?php if ($user_id): ?>
                        <button class="ch-vote-btn-lg ch-vote-up <?php echo $user_vote_on_post === 1 ? 'active-up' : ''; ?>" data-id="<?php echo (int)$post->id; ?>" data-type="post" data-val="1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                            Upvote
                        </button>
                        <?php endif; ?>
                        <span class="ch-vote-score" id="ch-post-score"><?php echo (int)$post->vote_count; ?> points</span>
                        <?php if ($user_id): ?>
                        <button class="ch-vote-btn-lg ch-vote-down <?php echo $user_vote_on_post === -1 ? 'active-down' : ''; ?>" data-id="<?php echo (int)$post->id; ?>" data-type="post" data-val="-1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                            Downvote
                        </button>
                        <?php endif; ?>
                        <div class="ch-share-dropdown">
                            <button class="ch-vote-btn-lg ch-share-btn" onclick="chToggleShareMenu(this)">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                Share
                            </button>
                            <div class="ch-share-menu">
                                <button class="ch-share-option" onclick="chShareToSocial('twitter', window.location.href, '<?php echo esc_attr($post->title); ?>')">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                                    Twitter
                                </button>
                                <button class="ch-share-option" onclick="chShareToSocial('facebook', window.location.href, '<?php echo esc_attr($post->title); ?>')">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    Facebook
                                </button>
                                <button class="ch-share-option" onclick="chShareToSocial('linkedin', window.location.href, '<?php echo esc_attr($post->title); ?>')">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                    LinkedIn
                                </button>
                                <button class="ch-share-option" onclick="chShareToSocial('copy', window.location.href)">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    Copy Link
                                </button>
                            </div>
                        </div>
                        <?php if ($user_id && $post->user_id != $user_id && !current_user_can('manage_options')): ?>
                        <button class="ch-vote-btn-lg" onclick="chReportPost(<?php echo (int)$post->id; ?>, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                            Report
                        </button>
                        <?php endif; ?>
                        <?php if ($user_id && ($post->user_id !== 0 && $post->user_id == $user_id || current_user_can('manage_options'))): ?>
                        <button class="ch-vote-btn-lg" onclick="chOpenEditPostModal(<?php echo (int)$post->id; ?>)">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Edit
                        </button>
                        <button class="ch-vote-btn-lg ch-danger" onclick="chDeletePost(<?php echo (int)$post->id; ?>, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                            Delete
                        </button>
                        <?php endif; ?>
                    </div>
                </article>

                <!-- Comments -->
                <section class="ch-comments-section">
                    <h3 class="ch-comments-title"><?php echo (int)$post->comment_count; ?> Comments</h3>

                    <?php if ($user_id): ?>
                    <div class="ch-comment-form" id="ch-comment-form-main">
                        <div class="ch-avatar-sm"><?php echo strtoupper(substr(wp_get_current_user()->display_name ?: 'U', 0, 1)); ?></div>
                        <div class="ch-comment-input-wrap">
                            <textarea id="ch-comment-content" class="ch-input ch-textarea" rows="3" placeholder="Share your thoughts..."></textarea>
                            <div class="ch-comment-form-footer">
                                <label class="ch-checkbox-label">
                                    <input type="checkbox" id="ch-comment-anon"> Comment anonymously
                                </label>
                                <button class="ch-btn ch-btn-primary" onclick="chSubmitComment(<?php echo (int)$post->id; ?>, 0, '<?php echo esc_attr($nonce); ?>')">Post Comment</button>
                            </div>
                        </div>
                    </div>
                    <?php elseif (!$post->is_private): ?>
                    <!-- Guest comment form for public categories -->
                    <div class="ch-comment-form" id="ch-comment-form-main">
                        <div class="ch-avatar-sm">?</div>
                        <div class="ch-comment-input-wrap">
                            <input type="text" id="ch-guest-comment-name" class="ch-input" placeholder="Your name (optional — leave blank for Anonymous)" maxlength="100" style="margin-bottom:8px;">
                            <textarea id="ch-comment-content" class="ch-input ch-textarea" rows="3" placeholder="Share your thoughts..."></textarea>
                            <div class="ch-comment-form-footer">
                                <span style="font-size:12px;color:#9ca3af;">Posting as guest &bull; <a href="<?php echo wp_login_url(get_permalink()); ?>" style="color:#FF7551;">Sign in</a> for full access</span>
                                <button class="ch-btn ch-btn-primary" onclick="chSubmitGuestComment(<?php echo (int)$post->id; ?>, 0, '<?php echo esc_attr($nonce); ?>')">Post Comment</button>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="ch-login-prompt">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        This is a private category. Please <a href="<?php echo wp_login_url(get_permalink()); ?>">log in</a> and follow to comment.
                    </p>
                    <?php endif; ?>

                    <div class="ch-comments-list" id="ch-comments-list">
                        <?php if (empty($comments)): ?>
                        <p class="ch-empty">No comments yet. Start the discussion!</p>
                        <?php else: foreach ($comments as $cm): ?>
                        <div class="ch-comment" id="ch-comment-<?php echo (int)$cm->id; ?>">
                            <div class="ch-comment-avatar">
                                <?php echo strtoupper(substr($cm->is_anonymous ? 'A' : ($cm->author_name ?: 'U'), 0, 1)); ?>
                            </div>
                            <div class="ch-comment-body">
                                <div class="ch-comment-header">
                                    <strong><?php
                                    if ($cm->user_id == 0 && !empty($cm->guest_name)) {
                                        echo esc_html($cm->guest_name) . ' <span style="font-size:11px;font-weight:400;color:#9ca3af;">(guest)</span>';
                                    } elseif ($cm->is_anonymous) {
                                        echo 'Anonymous';
                                    } else {
                                        echo esc_html($cm->author_name ?? 'Member');
                                    }
                                    ?></strong>
                                    <span class="ch-comment-time"><?php echo human_time_diff(strtotime($cm->created_at), current_time('timestamp')); ?> ago</span>
                                </div>
                                <p><?php echo ch_highlight_mentions(nl2br(esc_html($cm->content))); ?></p>
                                <div class="ch-comment-actions">
                                    <?php if ($user_id):
                                        $ucv = $user_comment_votes[$cm->id] ?? 0; ?>
                                    <button class="ch-comment-action ch-vote-up <?php echo $ucv === 1 ? 'active-up' : ''; ?>" data-id="<?php echo (int)$cm->id; ?>" data-type="comment" data-val="1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                                        <?php echo $cm->vote_count; ?>
                                    </button>
                                    <button class="ch-comment-action" onclick="chToggleReplyForm(<?php echo (int)$cm->id; ?>)">Reply</button>
                                    <?php if ($user_id && ($cm->user_id === 0 || $cm->user_id != $user_id) && !current_user_can('manage_options')): ?>
                                    <button class="ch-comment-action" onclick="chReportComment(<?php echo (int)$cm->id; ?>, '<?php echo esc_attr($nonce); ?>')">Report</button>
                                    <?php endif; ?>
                                    <?php if ($user_id && ($cm->user_id !== 0 && $cm->user_id == $user_id || current_user_can('manage_options'))): ?>
                                    <button class="ch-comment-action" onclick="chEditComment(<?php echo (int)$cm->id; ?>)">Edit</button>
                                    <button class="ch-comment-action ch-danger-action" onclick="chDeleteComment(<?php echo (int)$cm->id; ?>, '<?php echo esc_attr($nonce); ?>', this)">Delete</button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if ($user_id): ?>
                                <div class="ch-reply-form" id="ch-reply-form-<?php echo (int)$cm->id; ?>" style="display:none">
                                    <textarea class="ch-input ch-textarea" id="ch-reply-content-<?php echo (int)$cm->id; ?>" rows="2" placeholder="Write a reply..."></textarea>
                                    <div style="margin-top:8px">
                                        <button class="ch-btn ch-btn-primary ch-btn-sm" onclick="chSubmitComment(<?php echo (int)$post->id; ?>, <?php echo (int)$cm->id; ?>, '<?php echo esc_attr($nonce); ?>')">Reply</button>
                                        <button class="ch-btn ch-btn-secondary ch-btn-sm" onclick="chToggleReplyForm(<?php echo (int)$cm->id; ?>)">Cancel</button>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Nested replies -->
                                <?php if (!empty($replies_map[$cm->id])): ?>
                                <div class="ch-replies">
                                    <?php foreach ($replies_map[$cm->id] as $reply): ?>
                                    <div class="ch-comment ch-comment-reply" id="ch-comment-<?php echo $reply->id; ?>">
                                        <div class="ch-comment-avatar ch-avatar-xs">
                                            <?php echo strtoupper(substr($reply->is_anonymous ? 'A' : ($reply->author_name ?: 'U'), 0, 1)); ?>
                                        </div>
                                        <div class="ch-comment-body">
                                            <div class="ch-comment-header">
                                                <strong><?php echo $reply->is_anonymous ? 'Anonymous' : esc_html($reply->author_name ?? 'Member'); ?></strong>
                                                <span class="ch-comment-time"><?php echo human_time_diff(strtotime($reply->created_at), current_time('timestamp')); ?> ago</span>
                                            </div>
                                            <p><?php echo ch_highlight_mentions(nl2br(esc_html($reply->content))); ?></p>
                                            <?php if ($user_id): ?>
                                            <div class="ch-comment-actions">
                                                <?php if (($reply->user_id === 0 || $reply->user_id != $user_id) && !current_user_can('manage_options')): ?>
                                                <button class="ch-comment-action" onclick="chReportComment(<?php echo (int)$reply->id; ?>, '<?php echo esc_attr($nonce); ?>')">Report</button>
                                                <?php endif; ?>
                                                <?php if ($reply->user_id !== 0 && $reply->user_id == $user_id || current_user_can('manage_options')): ?>
                                                <button class="ch-comment-action" onclick="chEditComment(<?php echo $reply->id; ?>)">Edit</button>
                                                <button class="ch-comment-action ch-danger-action" onclick="chDeleteComment(<?php echo $reply->id; ?>, '<?php echo esc_attr($nonce); ?>', this)">Delete</button>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </section>
            </div>

            <!-- Post Sidebar -->
            <aside class="ch-post-view-sidebar">
                <div class="ch-sidebar-widget">
                    <h4>Post Info</h4>
                    <div class="ch-info-list">
                        <div class="ch-info-item">
                            <span class="ch-info-label">Category</span>
                            <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                                <?php echo esc_html($post->cat_name ?? 'General'); ?>
                            </span>
                        </div>
                        <div class="ch-info-item">
                            <span class="ch-info-label">Score</span>
                            <strong><?php echo (int)$post->vote_count; ?> points</strong>
                        </div>
                        <div class="ch-info-item">
                            <span class="ch-info-label">Comments</span>
                            <strong><?php echo (int)$post->comment_count; ?></strong>
                        </div>
                        <div class="ch-info-item">
                            <span class="ch-info-label">Views</span>
                            <strong><?php echo number_format($post->view_count); ?></strong>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Create Post Modal (for post view page) — Facebook-style composer -->
    <?php if ($user_id): ?>
    <div id="ch-modal-create-post" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg ch-composer-modal">
            <div class="ch-composer-header">
                <h3>Create Post</h3>
                <button class="ch-composer-close" onclick="chCloseModal('ch-modal-create-post')" aria-label="Close">&times;</button>
            </div>
            <div class="ch-composer-divider"></div>

            <div class="ch-composer-body">
                <div class="ch-composer-author-row">
                    <div class="ch-composer-avatar">
                        <?php $cd2 = wp_get_current_user()->display_name ?: 'U'; echo esc_html(strtoupper(substr($cd2, 0, 1))); ?>
                    </div>
                    <div class="ch-composer-author-info">
                        <div class="ch-composer-author-name"><?php echo esc_html(wp_get_current_user()->display_name ?: 'You'); ?></div>
                        <div class="ch-composer-meta-row">
                            <select id="ch-post-cat" class="ch-composer-cat-select">
                                <option value="">📂 Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="ch-composer-anon-toggle">
                                <input type="checkbox" id="ch-post-anon">
                                <span>Anonymous</span>
                            </label>
                        </div>
                    </div>
                </div>

                <input type="text" id="ch-post-title" class="ch-composer-title" placeholder="Write a title…">
                <textarea id="ch-post-content" class="ch-composer-textarea" rows="4"
                    placeholder="What's on your mind? Share your thoughts, concerns, or suggestions…"></textarea>

                <div class="ch-composer-tags-row">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <input type="text" id="ch-post-tags" class="ch-composer-tags-input" placeholder="Tags: road, drainage, urgent…">
                </div>

                <label class="ch-composer-media-area" id="ch-post-media-label" for="ch-post-media">
                    <div class="ch-composer-media-inner">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>Add photos / videos</span>
                        <span class="ch-composer-media-sub">or drag and drop</span>
                    </div>
                    <input type="file" id="ch-post-media" style="display:none" multiple accept="image/*,video/*,audio/*"
                        onchange="chUpdateMediaLabel(this, 'ch-post-media-label')">
                </label>
                <div id="ch-post-media-preview" class="ch-composer-media-preview"></div>
            </div>

            <div id="ch-post-msg"></div>

            <div class="ch-composer-footer">
                <div class="ch-composer-footer-hint">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Be respectful and follow community guidelines.
                </div>
                <button class="ch-btn ch-btn-primary ch-composer-submit" id="ch-submit-post-btn"
                    onclick="chSubmitPost('<?php echo $user_id ? wp_create_nonce('ch_feed_nonce') : ''; ?>')">
                    Post
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Post Modal (for post view page) — Facebook-style composer -->
    <div id="ch-modal-edit-post" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg ch-composer-modal">
            <div class="ch-composer-header">
                <h3>Edit Post</h3>
                <button class="ch-composer-close" onclick="chCloseModal('ch-modal-edit-post')" aria-label="Close">&times;</button>
            </div>
            <div class="ch-composer-divider"></div>

            <div class="ch-composer-body">
                <input type="hidden" id="ch-edit-post-id">

                <div class="ch-composer-author-row">
                    <div class="ch-composer-avatar">
                        <?php $cd3 = wp_get_current_user()->display_name ?: 'U'; echo esc_html(strtoupper(substr($cd3, 0, 1))); ?>
                    </div>
                    <div class="ch-composer-author-info">
                        <div class="ch-composer-author-name"><?php echo esc_html(wp_get_current_user()->display_name ?: 'You'); ?></div>
                        <div class="ch-composer-meta-row">
                            <select id="ch-edit-post-cat" class="ch-composer-cat-select">
                                <option value="">📂 Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="ch-composer-anon-toggle">
                                <input type="checkbox" id="ch-edit-post-anon">
                                <span>Anonymous</span>
                            </label>
                        </div>
                    </div>
                </div>

                <input type="text" id="ch-edit-post-title" class="ch-composer-title" placeholder="Write a title…">
                <textarea id="ch-edit-post-content" class="ch-composer-textarea" rows="4"
                    placeholder="What's on your mind?"></textarea>

                <div class="ch-composer-tags-row">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <input type="text" id="ch-edit-post-tags" class="ch-composer-tags-input" placeholder="Tags: road, drainage, urgent…">
                </div>

                <label class="ch-composer-media-area" id="ch-edit-post-media-label" for="ch-edit-post-media">
                    <div class="ch-composer-media-inner">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>Add photos / videos</span>
                        <span class="ch-composer-media-sub">or drag and drop</span>
                    </div>
                    <input type="file" id="ch-edit-post-media" style="display:none" multiple accept="image/*,video/*,audio/*"
                        onchange="chUpdateMediaLabel(this, 'ch-edit-post-media-label')">
                </label>
                <div id="ch-edit-post-media-preview" class="ch-composer-media-preview"></div>
            </div>

            <div id="ch-edit-post-msg"></div>

            <div class="ch-composer-footer">
                <div class="ch-composer-footer-hint">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Edit your post. Changes are visible immediately.
                </div>
                <button class="ch-btn ch-btn-primary ch-composer-submit" id="ch-update-post-btn"
                    onclick="chUpdatePost('<?php echo esc_attr($nonce); ?>')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Report Modal -->
    <div id="ch-modal-report" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal">
            <div class="ch-modal-header">
                <h3>Report Content</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-report')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <input type="hidden" id="ch-report-target-id">
                <input type="hidden" id="ch-report-target-type">
                <div class="ch-field-group">
                    <label class="ch-label">Reason *</label>
                    <select id="ch-report-reason" class="ch-input">
                        <option value="">Select reason</option>
                        <option value="spam">Spam or Misleading</option>
                        <option value="harassment">Harassment or Bullying</option>
                        <option value="inappropriate">Inappropriate Content</option>
                        <option value="misinformation">Misinformation</option>
                        <option value="hate">Hate Speech</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Additional Details</label>
                    <textarea id="ch-report-details" class="ch-input ch-textarea" rows="3" placeholder="Provide more context..."></textarea>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-report')">Cancel</button>
                <button class="ch-btn ch-btn-danger" onclick="chSubmitReport('<?php echo esc_attr($nonce); ?>')">Submit Report</button>
            </div>
            <div id="ch-report-msg"></div>
        </div>
    </div>

    <script>
        window.chCurrentPost = {
            id: <?php echo (int)$post->id; ?>,
            category_id: <?php echo $post->category_id; ?>,
            title: '<?php echo addslashes($post->title); ?>',
            content: '<?php echo addslashes($post->content); ?>',
            tags: '<?php echo addslashes($post->tags); ?>',
            is_anonymous: <?php echo $post->is_anonymous; ?>,
            media_urls: '<?php echo addslashes($post->media_urls); ?>'
        };
    </script>

    <?php echo ch_global_styles(); ?>
    <?php echo ch_global_scripts(); ?>
    <?php echo ch_feed_scripts(); ?>
    <?php echo ch_post_view_scripts(); ?>
    <?php
    return ob_get_clean();
}

// ============================================================
// AJAX HANDLERS
// ============================================================

function bntm_ajax_ch_create_category() {
    check_ajax_referer('ch_category_nonce', 'nonce');

    global $wpdb;
    $user_id = get_current_user_id();
    $user_karma = (int)$wpdb->get_var($wpdb->prepare("SELECT karma_points FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id));

    $cat_creation_enabled = get_option('ch_user_category_creation', 0);
    $karma_threshold       = (int)get_option('ch_category_creation_karma', 100);
    if (!current_user_can('manage_options')) {
        if (!$cat_creation_enabled) {
            wp_send_json_error(['message' => 'Category creation by users is currently disabled.']);
        }
        if ($user_karma < $karma_threshold) {
            wp_send_json_error(['message' => "You need at least {$karma_threshold} karma points to create categories (you have {$user_karma})."]);
        }
    }

    $name       = sanitize_text_field($_POST['name'] ?? '');
    $desc       = sanitize_textarea_field($_POST['description'] ?? '');
    $color      = sanitize_hex_color($_POST['color'] ?? '#FF7551') ?: '#FF7551';
    $order      = (int)($_POST['sort_order'] ?? 0);
    $is_private = (int)(!empty($_POST['is_private']));

    if (!$name) wp_send_json_error(['message' => 'Category name is required']);

    $slug = sanitize_title($name);
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ch_categories WHERE slug = %s", $slug));
    if ($exists) wp_send_json_error(['message' => 'A category with this name already exists']);

    $result = $wpdb->insert("{$wpdb->prefix}ch_categories", [
        'rand_id'     => bntm_rand_id(),
        'business_id' => $user_id,
        'name'        => $name,
        'slug'        => $slug,
        'description' => $desc,
        'color'       => $color,
        'sort_order'  => $order,
        'is_private'  => $is_private,
    ], ['%s','%d','%s','%s','%s','%s','%d','%d']);

    if ($result) {
        $new_cat_id = $wpdb->insert_id;
        // Auto-follow the newly created category for the creator
        $wpdb->insert(
            "{$wpdb->prefix}ch_follows",
            ['user_id' => $user_id, 'category_id' => $new_cat_id],
            ['%d', '%d']
        );
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}ch_categories SET follower_count = follower_count + 1 WHERE id = %d",
            $new_cat_id
        ));
        ch_log_activity('create_category', 'category', $new_cat_id, "Created category: $name");
        wp_send_json_success(['message' => 'Category created successfully!', 'cat_id' => $new_cat_id]);
    } else {
        wp_send_json_error(['message' => 'Failed to create category']);
    }
}

function bntm_ajax_ch_edit_category() {
    check_ajax_referer('ch_category_nonce', 'nonce');

    global $wpdb;
    $user_id = get_current_user_id();
    $id      = (int)($_POST['category_id'] ?? 0);
    $name    = sanitize_text_field($_POST['name'] ?? '');
    $desc    = sanitize_textarea_field($_POST['description'] ?? '');
    $color   = sanitize_hex_color($_POST['color'] ?? '#FF7551') ?: '#FF7551';

    if (!$id || !$name) wp_send_json_error(['message' => 'Invalid input']);

    // Allow admin or the category creator (business_id) to edit
    $owner = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT business_id FROM {$wpdb->prefix}ch_categories WHERE id = %d", $id
    ));
    if (!current_user_can('manage_options') && $owner !== $user_id) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $is_private = (int)(!empty($_POST['is_private']));
    $result = $wpdb->update("{$wpdb->prefix}ch_categories",
        ['name' => $name, 'description' => $desc, 'color' => $color, 'slug' => sanitize_title($name), 'is_private' => $is_private],
        ['id' => $id], ['%s','%s','%s','%s','%d'], ['%d']
    );

    if ($result !== false) {
        ch_log_activity('edit_category', 'category', $id, "Updated category: $name");
        wp_send_json_success(['message' => 'Category updated!']);
    } else {
        wp_send_json_error(['message' => 'No changes made']);
    }
}

function bntm_ajax_ch_delete_category() {
    check_ajax_referer('ch_category_nonce', 'nonce');

    global $wpdb;
    $user_id = get_current_user_id();
    $id      = (int)($_POST['category_id'] ?? 0);
    if (!$id) wp_send_json_error(['message' => 'Invalid category']);

    // Allow admin or the category creator (business_id) to delete
    $owner = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT business_id FROM {$wpdb->prefix}ch_categories WHERE id = %d", $id
    ));
    if (!current_user_can('manage_options') && $owner !== $user_id) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $post_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE category_id = %d", $id));
    if ($post_count > 0) wp_send_json_error(['message' => "Cannot delete — category has $post_count post(s)"]);

    $result = $wpdb->delete("{$wpdb->prefix}ch_categories", ['id' => $id], ['%d']);
    if ($result) {
        ch_log_activity('delete_category', 'category', $id, 'Deleted category');
        wp_send_json_success(['message' => 'Category deleted']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete category']);
    }
}

function bntm_ajax_ch_toggle_category_status() {
    check_ajax_referer('ch_category_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $id = (int)($_POST['category_id'] ?? 0);
    if (!$id) wp_send_json_error(['message' => 'Invalid category']);

    $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}ch_categories WHERE id=%d", $id));
    if (!$current) wp_send_json_error(['message' => 'Category not found']);
    $new = $current === 'active' ? 'archived' : 'active';

    $updated = $wpdb->update("{$wpdb->prefix}ch_categories", ['status' => $new], ['id' => $id], ['%s'], ['%d']);
    if ($updated !== false) {
        ch_log_activity('toggle_category_status', 'category', $id, "Status changed to $new");
        wp_send_json_success(['status' => $new]);
    } else {
        wp_send_json_error(['message' => 'Failed to update status']);
    }
}

function bntm_ajax_ch_create_post() {
    global $wpdb;
    $user_id   = get_current_user_id();
    $title     = sanitize_text_field($_POST['title'] ?? '');
    $content   = sanitize_textarea_field($_POST['content'] ?? '');
    $cat_id    = (int)($_POST['category_id'] ?? 0);
    $tags      = sanitize_text_field($_POST['tags'] ?? '');
    $is_anon   = (int)(!empty($_POST['is_anonymous']));
    $guest_name = sanitize_text_field($_POST['guest_name'] ?? '');

    if (!$title || !$content || !$cat_id) wp_send_json_error(['message' => 'Title, content, and category are required']);

    // Check category privacy
    $cat = $wpdb->get_row($wpdb->prepare("SELECT is_private FROM {$wpdb->prefix}ch_categories WHERE id = %d", $cat_id));
    if (!$cat) wp_send_json_error(['message' => 'Category not found']);

    if ($cat->is_private) {
        // Private category: must be logged in AND following
        if (!$user_id) wp_send_json_error(['message' => 'You must be logged in to post in this category']);
        $is_following = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ch_follows WHERE user_id = %d AND category_id = %d",
            $user_id, $cat_id
        ));
        if (!$is_following && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You must follow this category to post in it']);
        }
    } else {
        // Public category: guests allowed, registered users must not be banned
        if ($user_id) {
            check_ajax_referer('ch_feed_nonce', 'nonce');
            $profile_row = $wpdb->get_row($wpdb->prepare("SELECT status FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id));
            if ($profile_row && in_array($profile_row->status, ['banned','suspended'])) {
                wp_send_json_error(['message' => 'Your account is restricted from posting']);
            }
            ch_ensure_profile($user_id);
        }
        // Guests: no nonce needed, guest_name used as display name
    }

    $rand_id = bntm_rand_id();
    $status  = get_option('ch_post_approval_enabled', 0) ? 'pending' : 'active';
    $result  = $wpdb->insert("{$wpdb->prefix}ch_posts", [
        'rand_id'      => $rand_id,
        'business_id'  => $user_id,
        'user_id'      => $user_id,
        'category_id'  => $cat_id,
        'title'        => $title,
        'content'      => $content,
        'tags'         => $tags,
        'media_urls'   => '',
        'is_anonymous' => $user_id ? $is_anon : 1,
        'guest_name'   => (!$user_id && $guest_name) ? $guest_name : null,
        'status'       => $status,
    ], ['%s','%d','%d','%d','%s','%s','%s','%s','%d','%s','%s']);

    if ($result) {
        $post_id = $wpdb->insert_id;

        // Handle media uploads
        $media_urls = [];
        if (!empty($_FILES['media']['name'][0])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $files = $_FILES['media'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];
                    $upload = wp_handle_upload($file, ['test_form' => false, 'upload_error_handler' => function($file, $message) { return ['error' => $message]; }]);
                    if (!isset($upload['error'])) {
                        $media_urls[] = $upload['url'];
                    }
                }
            }
            if ($media_urls) {
                $wpdb->update("{$wpdb->prefix}ch_posts", ['media_urls' => json_encode($media_urls)], ['id' => $post_id], ['%s'], ['%d']);
            }
        }

        // Update post count and category post count
        if ($user_id) {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_user_profiles SET post_count = post_count + 1, karma_points = karma_points + 2 WHERE user_id = %d", $user_id));
        }
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_categories SET post_count = post_count + 1 WHERE id = %d", $cat_id));
        wp_send_json_success(['message' => 'Post created!', 'rand_id' => $rand_id]);
    } else {
        wp_send_json_error(['message' => 'Failed to create post']);
    }
}

function bntm_ajax_ch_edit_post() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please log in']);
    check_ajax_referer('ch_post_view_nonce', 'nonce');

    global $wpdb;
    $user_id  = get_current_user_id();
    $post_id  = (int)($_POST['post_id'] ?? 0);
    $title    = sanitize_text_field($_POST['title'] ?? '');
    $content  = sanitize_textarea_field($_POST['content'] ?? '');
    $cat_id   = (int)($_POST['category_id'] ?? 0);
    $tags     = sanitize_text_field($_POST['tags'] ?? '');
    $is_anon  = (int)(!empty($_POST['is_anonymous']));

    if (!$post_id || !$title || !$content || !$cat_id) wp_send_json_error(['message' => 'All fields are required']);

    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ch_posts WHERE id = %d", $post_id));
    if (!$post) wp_send_json_error(['message' => 'Post not found']);
    if ($post->user_id != $user_id && !current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $result = $wpdb->update("{$wpdb->prefix}ch_posts", [
        'title'        => $title,
        'content'      => $content,
        'category_id'  => $cat_id,
        'tags'         => $tags,
        'is_anonymous' => $is_anon,
    ], ['id' => $post_id], ['%s','%s','%d','%s','%d'], ['%d']);

    if ($result !== false) {
        // Handle media uploads if any
        $media_urls = [];
        if (!empty($_FILES['media']['name'][0])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $files = $_FILES['media'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];
                    $upload = wp_handle_upload($file, ['test_form' => false, 'upload_error_handler' => function($file, $message) { return ['error' => $message]; }]);
                    if (!isset($upload['error'])) {
                        $media_urls[] = $upload['url'];
                    }
                }
            }
            if ($media_urls) {
                $existing = json_decode($post->media_urls, true) ?: [];
                $all_media = array_merge($existing, $media_urls);
                $wpdb->update("{$wpdb->prefix}ch_posts", ['media_urls' => json_encode($all_media)], ['id' => $post_id], ['%s'], ['%d']);
            }
        }

        wp_send_json_success(['message' => 'Post updated!']);
    } else {
        wp_send_json_error(['message' => 'No changes made']);
    }
}

function bntm_ajax_ch_delete_post() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);
    check_ajax_referer('ch_post_nonce', 'nonce') || check_ajax_referer('ch_post_view_nonce', 'nonce');

    global $wpdb;
    $user_id = get_current_user_id();
    $post_id = (int)($_POST['post_id'] ?? 0);

    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ch_posts WHERE id = %d", $post_id));
    if (!$post) wp_send_json_error(['message' => 'Post not found']);
    if ($post->user_id != $user_id && !current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $wpdb->update("{$wpdb->prefix}ch_posts", ['status' => 'removed'], ['id' => $post_id], ['%s'], ['%d']);
    wp_send_json_success(['message' => 'Post deleted']);
}

function bntm_ajax_ch_get_posts() {
    global $wpdb;
    $cat_id = (int)($_POST['category_id'] ?? 0);
    $sort   = sanitize_text_field($_POST['sort'] ?? 'new');
    $page   = max(1, (int)($_POST['page'] ?? 1));

    $order  = $sort === 'top' ? 'vote_count DESC' : 'created_at DESC';
    $offset = ($page - 1) * 15;

    $where = $cat_id ? "AND category_id = $cat_id" : '';
    $posts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ch_posts WHERE status='active' $where ORDER BY $order LIMIT 15 OFFSET $offset");

    wp_send_json_success(['posts' => $posts]);
}

function bntm_ajax_ch_get_post_detail() {
    global $wpdb;
    $post_id = (int)($_POST['post_id'] ?? 0);
    $rand_id = sanitize_text_field($_POST['rand_id'] ?? '');

    if ($post_id) {
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as cat_name, c.color as cat_color, c.slug as cat_slug,
                    u.display_name as author_name, u.karma_points as author_karma, u.location as author_location
             FROM {$wpdb->prefix}ch_posts p
             LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
             LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
             WHERE p.status IN ('active','hidden') AND p.id = %d",
            $post_id
        ));
    } elseif ($rand_id) {
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as cat_name, c.color as cat_color, c.slug as cat_slug,
                    u.display_name as author_name, u.karma_points as author_karma, u.location as author_location
             FROM {$wpdb->prefix}ch_posts p
             LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
             LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
             WHERE p.status IN ('active','hidden') AND p.rand_id = %s",
            $rand_id
        ));
    } else {
        wp_send_json_error(['message' => 'No post identifier provided']);
    }

    if (!$post) wp_send_json_error(['message' => 'Post not found']);

    wp_send_json_success(['post' => $post]);
}

function bntm_ajax_ch_add_comment() {
    global $wpdb;
    $user_id    = get_current_user_id();
    $post_id    = (int)($_POST['post_id'] ?? 0);
    $parent_id  = (int)($_POST['parent_id'] ?? 0);
    $content    = sanitize_textarea_field($_POST['content'] ?? '');
    $is_anon    = (int)(!empty($_POST['is_anonymous']));
    $guest_name = sanitize_text_field($_POST['guest_name'] ?? '');

    if (!$post_id || !$content) wp_send_json_error(['message' => 'Content is required']);

    // Determine category privacy from the post
    $post_row = $wpdb->get_row($wpdb->prepare(
        "SELECT p.user_id, p.category_id, c.is_private
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         WHERE p.id = %d AND p.status = 'active'",
        $post_id
    ));
    if (!$post_row) wp_send_json_error(['message' => 'Post not found']);

    if ($post_row->is_private) {
        // Private: must be logged in and following
        if (!$user_id) wp_send_json_error(['message' => 'You must be logged in to comment in this category']);
        check_ajax_referer('ch_post_view_nonce', 'nonce');
        $is_following = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ch_follows WHERE user_id = %d AND category_id = %d",
            $user_id, $post_row->category_id
        ));
        if (!$is_following && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You must follow this category to comment']);
        }
    } else {
        // Public: logged-in users need nonce + status check; guests are welcome
        if ($user_id) {
            check_ajax_referer('ch_post_view_nonce', 'nonce');
            $profile = $wpdb->get_row($wpdb->prepare("SELECT status FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id));
            if ($profile && in_array($profile->status, ['banned','suspended'])) {
                wp_send_json_error(['message' => 'Your account is restricted']);
            }
        }
    }

    $result = $wpdb->insert("{$wpdb->prefix}ch_comments", [
        'rand_id'      => bntm_rand_id(),
        'business_id'  => $user_id,
        'post_id'      => $post_id,
        'user_id'      => $user_id,
        'parent_id'    => $parent_id,
        'content'      => $content,
        'is_anonymous' => $user_id ? $is_anon : 1,
        'guest_name'   => (!$user_id && $guest_name) ? $guest_name : null,
    ], ['%s','%d','%d','%d','%d','%s','%d','%s']);

    if ($result) {
        $comment_id = $wpdb->insert_id;
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_posts SET comment_count = comment_count + 1 WHERE id = %d", $post_id));
        if ($user_id) {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_user_profiles SET comment_count = comment_count + 1, karma_points = karma_points + 1 WHERE user_id = %d", $user_id));
        }

        // Process mentions
        $mentioned_users = ch_extract_mentions($content);
        foreach ($mentioned_users as $mentioned_user_id) {
            if ($mentioned_user_id != $user_id) { // Don't notify self-mentions
                ch_create_notification($mentioned_user_id, 'mention', $user_id, $post_id, $comment_id);
            }
        }

        // Notify post author
        $post_row = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}ch_posts WHERE id = %d", $post_id));
        if ($post_row && $post_row->user_id != $user_id) {
            ch_create_notification($post_row->user_id, 'reply', $user_id, $post_id, $comment_id);
        }

        wp_send_json_success(['message' => 'Comment added!']);
    } else {
        wp_send_json_error(['message' => 'Failed to add comment']);
    }
}

function bntm_ajax_ch_delete_comment() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);
    check_ajax_referer('ch_post_view_nonce', 'nonce');

    global $wpdb;
    $user_id    = get_current_user_id();
    $comment_id = (int)($_POST['comment_id'] ?? 0);

    $cm = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ch_comments WHERE id = %d", $comment_id));
    if (!$cm) wp_send_json_error(['message' => 'Comment not found']);
    if ($cm->user_id != $user_id && !current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $wpdb->update("{$wpdb->prefix}ch_comments", ['status' => 'removed'], ['id' => $comment_id], ['%s'], ['%d']);
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_posts SET comment_count = GREATEST(0, comment_count - 1) WHERE id = %d", $cm->post_id));

    wp_send_json_success(['message' => 'Comment deleted']);
}

function bntm_ajax_ch_edit_comment() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please log in']);
    check_ajax_referer('ch_post_view_nonce', 'nonce');

    global $wpdb;
    $user_id   = get_current_user_id();
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    $content   = sanitize_textarea_field($_POST['content'] ?? '');

    if (!$comment_id || !$content) wp_send_json_error(['message' => 'Content is required']);

    $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ch_comments WHERE id = %d", $comment_id));
    if (!$comment) wp_send_json_error(['message' => 'Comment not found']);
    if ($comment->user_id != $user_id && !current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $result = $wpdb->update("{$wpdb->prefix}ch_comments", [
        'content' => $content,
    ], ['id' => $comment_id], ['%s'], ['%d']);

    if ($result !== false) {
        // Process mentions in edited content
        $mentioned_users = ch_extract_mentions($content);
        foreach ($mentioned_users as $mentioned_user_id) {
            if ($mentioned_user_id != $user_id) { // Don't notify self-mentions
                ch_create_notification($mentioned_user_id, 'mention', $user_id, $comment->post_id, $comment_id);
            }
        }

        wp_send_json_success(['message' => 'Comment updated!']);
    } else {
        wp_send_json_error(['message' => 'No changes made']);
    }
}

function bntm_ajax_ch_vote() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please log in to vote']);

    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'ch_feed_nonce') && !wp_verify_nonce($nonce, 'ch_post_view_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    global $wpdb;
    $user_id     = get_current_user_id();
    $target_type = sanitize_text_field($_POST['target_type'] ?? 'post');
    $target_id   = (int)($_POST['target_id'] ?? 0);
    $value       = (int)($_POST['value'] ?? 1);

    if (!in_array($target_type, ['post','comment']) || !$target_id) wp_send_json_error(['message' => 'Invalid vote target']);
    $value = $value > 0 ? 1 : -1;

    // Check existing vote
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ch_votes WHERE user_id=%d AND target_type=%s AND target_id=%d",
        $user_id, $target_type, $target_id
    ));

    $table = $target_type === 'post' ? "{$wpdb->prefix}ch_posts" : "{$wpdb->prefix}ch_comments";

    if ($existing) {
        if ($existing->value == $value) {
            // Remove vote (toggle off)
            $wpdb->delete("{$wpdb->prefix}ch_votes", ['id' => $existing->id], ['%d']);
            $wpdb->query($wpdb->prepare("UPDATE $table SET vote_count = vote_count - %d WHERE id = %d", $value, $target_id));
            $new_count = (int) $wpdb->get_var($wpdb->prepare("SELECT vote_count FROM $table WHERE id = %d", $target_id));
            wp_send_json_success(['vote_count' => $new_count, 'action' => 'removed']);
        } else {
            // Change vote
            $wpdb->update("{$wpdb->prefix}ch_votes", ['value' => $value], ['id' => $existing->id], ['%d'], ['%d']);
            $diff = $value * 2;
            $wpdb->query($wpdb->prepare("UPDATE $table SET vote_count = vote_count + %d WHERE id = %d", $diff, $target_id));
            $new_count = (int) $wpdb->get_var($wpdb->prepare("SELECT vote_count FROM $table WHERE id = %d", $target_id));
            wp_send_json_success(['vote_count' => $new_count, 'action' => 'changed']);
        }
    } else {
        $wpdb->insert("{$wpdb->prefix}ch_votes", [
            'user_id' => $user_id, 'target_type' => $target_type, 'target_id' => $target_id, 'value' => $value
        ], ['%d', '%s', '%d', '%d']);
        $wpdb->query($wpdb->prepare("UPDATE $table SET vote_count = vote_count + %d WHERE id = %d", $value, $target_id));
        $new_count = (int) $wpdb->get_var($wpdb->prepare("SELECT vote_count FROM $table WHERE id = %d", $target_id));

        // Award karma to author
        if ($value === 1 && $target_type === 'post') {
            $author = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $table WHERE id = %d", $target_id));
            if ($author && $author != $user_id) {
                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_user_profiles SET karma_points = karma_points + 1 WHERE user_id = %d", $author));
                // Notify post author of the upvote
                ch_create_notification($author, 'vote', $user_id, $target_id);
            }
        }

        wp_send_json_success(['vote_count' => $new_count, 'action' => 'voted']);
    }
}

function bntm_ajax_ch_follow_category() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please log in']);
    check_ajax_referer('ch_feed_nonce', 'nonce');

    global $wpdb;
    $user_id = get_current_user_id();
    $cat_id  = (int)($_POST['category_id'] ?? 0);

    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ch_follows WHERE user_id=%d AND category_id=%d", $user_id, $cat_id
    ));

    if ($exists) {
        $wpdb->delete("{$wpdb->prefix}ch_follows", ['user_id' => $user_id, 'category_id' => $cat_id], ['%d','%d']);
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_categories SET follower_count = GREATEST(0, follower_count - 1) WHERE id = %d", $cat_id));
        wp_send_json_success(['following' => false, 'message' => 'Unfollowed']);
    } else {
        $wpdb->insert("{$wpdb->prefix}ch_follows", ['user_id' => $user_id, 'category_id' => $cat_id], ['%d','%d']);
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_categories SET follower_count = follower_count + 1 WHERE id = %d", $cat_id));
        wp_send_json_success(['following' => true, 'message' => 'Following!']);
    }
}

function bntm_ajax_ch_bookmark_post() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please log in']);
    check_ajax_referer('ch_feed_nonce', 'nonce');

    global $wpdb;
    $user_id = get_current_user_id();
    $post_id = (int)($_POST['post_id'] ?? 0);

    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ch_bookmarks WHERE user_id=%d AND post_id=%d", $user_id, $post_id
    ));

    if ($exists) {
        $wpdb->delete("{$wpdb->prefix}ch_bookmarks", ['user_id' => $user_id, 'post_id' => $post_id], ['%d','%d']);
        wp_send_json_success(['bookmarked' => false, 'message' => 'Bookmark removed']);
    } else {
        $wpdb->insert("{$wpdb->prefix}ch_bookmarks", ['user_id' => $user_id, 'post_id' => $post_id], ['%d','%d']);
        wp_send_json_success(['bookmarked' => true, 'message' => 'Post saved!']);
    }
}

function bntm_ajax_ch_report() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please log in to report']);
    check_ajax_referer('ch_feed_nonce', 'nonce');

    global $wpdb;
    $reporter_id  = get_current_user_id();
    $target_type  = sanitize_text_field($_POST['target_type'] ?? 'post');
    $target_id    = (int)($_POST['target_id'] ?? 0);
    $reason       = sanitize_text_field($_POST['reason'] ?? '');
    $details      = sanitize_textarea_field($_POST['details'] ?? '');

    if (!in_array($target_type, ['post','comment','user']) || !$target_id || !$reason) {
        wp_send_json_error(['message' => 'Invalid report data']);
    }

    // Prevent duplicate pending report
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ch_reports WHERE reporter_id=%d AND target_type=%s AND target_id=%d AND status='pending'",
        $reporter_id, $target_type, $target_id
    ));
    if ($existing) wp_send_json_error(['message' => 'You already reported this content']);

    $result = $wpdb->insert("{$wpdb->prefix}ch_reports", [
        'rand_id'    => bntm_rand_id(),
        'reporter_id'=> $reporter_id,
        'target_type'=> $target_type,
        'target_id'  => $target_id,
        'reason'     => $reason,
        'details'    => $details,
    ], ['%s','%d','%s','%d','%s','%s']);

    if ($result) {
        // Increment report count on target content
        $table = $target_type === 'post' ? "{$wpdb->prefix}ch_posts" : "{$wpdb->prefix}ch_comments";
        $wpdb->query($wpdb->prepare("UPDATE $table SET report_count = report_count + 1 WHERE id = %d", $target_id));

        // Check for auto-hide threshold
        $threshold = get_option('ch_report_auto_hide_threshold', 5); // Default 5 reports
        $current_reports = (int) $wpdb->get_var($wpdb->prepare("SELECT report_count FROM $table WHERE id = %d", $target_id));

        if ($current_reports >= $threshold) {
            $wpdb->update($table, ['status' => 'hidden'], ['id' => $target_id], ['%s'], ['%d']);
            // Log auto-hide action
            ch_log_activity('auto_hide', $target_type, $target_id, "Auto-hidden due to $current_reports reports");
        }

        wp_send_json_success(['message' => 'Report submitted. Thank you for keeping the community safe.']);
    } else {
        wp_send_json_error(['message' => 'Failed to submit report']);
    }
}

function bntm_ajax_ch_moderate_action() {
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $nonce = $_POST['nonce'] ?? '';
    if (
        !wp_verify_nonce($nonce, 'ch_moderate_nonce') &&
        !wp_verify_nonce($nonce, 'ch_post_nonce')     &&
        !wp_verify_nonce($nonce, 'ch_report_nonce')   &&
        !wp_verify_nonce($nonce, 'ch_post_view_nonce')
    ) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    global $wpdb;
    $action    = sanitize_text_field($_POST['mod_action'] ?? '');
    $target_id = (int)($_POST['target_id'] ?? 0);
    $reason    = sanitize_text_field($_POST['reason'] ?? '');
    $admin_id  = get_current_user_id();

    switch ($action) {
        case 'approve_post':
            $wpdb->update("{$wpdb->prefix}ch_posts", ['status' => 'active'], ['id' => $target_id], ['%s'], ['%d']);
            ch_log_activity('approve_post', 'post', $target_id, 'Post approved for publication');
            wp_send_json_success(['message' => 'Post approved']);
            break;

        case 'reject_post':
            $wpdb->update("{$wpdb->prefix}ch_posts", ['status' => 'removed'], ['id' => $target_id], ['%s'], ['%d']);
            ch_log_activity('reject_post', 'post', $target_id, 'Post rejected');
            wp_send_json_success(['message' => 'Post rejected']);
            break;

        case 'remove_post':
            if ($reason === '') {
                wp_send_json_error(['message' => 'Reason is required when removing a post.']);
            }
            $wpdb->update("{$wpdb->prefix}ch_posts", ['status' => 'removed'], ['id' => $target_id], ['%s'], ['%d']);
            ch_log_activity('remove_post', 'post', $target_id, 'Post removed by admin: ' . $reason);
            wp_send_json_success(['message' => 'Post removed']);
            break;

        case 'remove_comment':
            $wpdb->update("{$wpdb->prefix}ch_comments", ['status' => 'removed'], ['id' => $target_id], ['%s'], ['%d']);
            ch_log_activity('remove_comment', 'comment', $target_id, 'Comment removed by admin');
            wp_send_json_success(['message' => 'Comment removed']);
            break;

        case 'restore_post':
            $wpdb->update("{$wpdb->prefix}ch_posts", ['status' => 'active'], ['id' => $target_id], ['%s'], ['%d']);
            ch_log_activity('restore_post', 'post', $target_id, 'Post restored by admin');
            wp_send_json_success(['message' => 'Post restored']);
            break;

        case 'suspend_user':
            $wpdb->update("{$wpdb->prefix}ch_user_profiles", ['status' => 'suspended'], ['user_id' => $target_id], ['%s'], ['%d']);
            ch_log_activity('suspend_user', 'user', $target_id, 'User suspended: ' . $reason);
            wp_send_json_success(['message' => 'User suspended']);
            break;

        case 'ban_user':
            $wpdb->update("{$wpdb->prefix}ch_user_profiles", ['status' => 'banned'], ['user_id' => $target_id], ['%s'], ['%d']);
            ch_log_activity('ban_user', 'user', $target_id, 'User banned: ' . $reason);
            wp_send_json_success(['message' => 'User banned']);
            break;

        case 'unsuspend_user':
            $wpdb->update("{$wpdb->prefix}ch_user_profiles", ['status' => 'active'], ['user_id' => $target_id], ['%s'], ['%d']);
            ch_log_activity('unsuspend_user', 'user', $target_id, 'User restored: ' . $reason);
            wp_send_json_success(['message' => 'User restored']);
            break;

        case 'resolve_report':
            $resolution = sanitize_text_field($_POST['resolution'] ?? 'resolved');
            if (!in_array($resolution, ['resolved', 'dismissed'])) {
                $resolution = 'resolved';
            }
            $wpdb->update("{$wpdb->prefix}ch_reports",
                ['status' => $resolution, 'reviewed_by' => $admin_id],
                ['id' => $target_id], ['%s', '%d'], ['%d']
            );
            ch_log_activity('resolve_report', 'report', $target_id, "Report $resolution");
            wp_send_json_success(['message' => "Report $resolution"]);
            break;

        default:
            wp_send_json_error(['message' => 'Unknown action']);
    }
}

function bntm_ajax_ch_pin_post() {
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);
    check_ajax_referer('ch_post_nonce', 'nonce');

    global $wpdb;
    $post_id = (int)($_POST['post_id'] ?? 0);
    $pin     = (int)($_POST['pin'] ?? 0);

    $wpdb->update("{$wpdb->prefix}ch_posts", ['is_pinned' => $pin ? 1 : 0], ['id' => $post_id], ['%d'], ['%d']);
    ch_log_activity($pin ? 'pin_post' : 'unpin_post', 'post', $post_id);
    wp_send_json_success(['message' => $pin ? 'Post pinned' : 'Post unpinned']);
}

function bntm_ajax_ch_get_notifications() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id = get_current_user_id();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    $notifications = $wpdb->get_results($wpdb->prepare(
        "SELECT n.*, p.title as post_title, p.rand_id as post_rand_id, c.content as comment_content
         FROM {$wpdb->prefix}ch_notifications n
         LEFT JOIN {$wpdb->prefix}ch_posts p ON n.post_id = p.id
         LEFT JOIN {$wpdb->prefix}ch_comments c ON n.comment_id = c.id
         WHERE n.user_id = %d
         ORDER BY n.created_at DESC
         LIMIT %d OFFSET %d",
        $user_id, $per_page, $offset
    ));

    $unread_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ch_notifications WHERE user_id = %d AND is_read = 0",
        $user_id
    ));

    $formatted = [];
    foreach ($notifications as $n) {
        $message = '';
        switch ($n->type) {
            case 'reply':
                $message = 'Someone replied to your post: ' . esc_html(wp_trim_words($n->post_title, 5));
                break;
            case 'mention':
                $message = 'You were mentioned in a post';
                break;
            case 'vote':
                $message = 'Your post received a vote';
                break;
            case 'announcement':
                $message = 'New community announcement';
                break;
            case 'report_resolved':
                $message = 'A report you submitted has been resolved';
                break;
            default:
                $message = $n->message ?? 'New notification';
        }

        $formatted[] = [
            'id'           => $n->id,
            'type'         => $n->type,
            'message'      => $message,
            'is_read'      => $n->is_read,
            'created_at'   => human_time_diff(strtotime($n->created_at), current_time('timestamp')) . ' ago',
            'post_id'      => $n->post_id,
            'post_rand_id' => $n->post_rand_id ?? '',
            'comment_id'   => $n->comment_id
        ];
    }

    wp_send_json_success([
        'notifications' => $formatted,
        'unread_count' => (int)$unread_count,
        'has_more' => count($notifications) === $per_page
    ]);
}

function bntm_ajax_ch_mark_notifications() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id = get_current_user_id();
    $notification_ids = $_POST['notification_ids'] ?? [];

    if (!empty($notification_ids)) {
        $placeholders = implode(',', array_fill(0, count($notification_ids), '%d'));
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}ch_notifications SET is_read = 1 WHERE user_id = %d AND id IN ($placeholders)",
            array_merge([$user_id], $notification_ids)
        ));
    } else {
        // Mark all as read
        $wpdb->update("{$wpdb->prefix}ch_notifications", ['is_read' => 1], ['user_id' => $user_id, 'is_read' => 0], ['%d'], ['%d', '%d']);
    }

    wp_send_json_success(['message' => 'Notifications marked as read']);
}

function bntm_ajax_ch_search() {
    global $wpdb;
    $query = sanitize_text_field($_POST['query'] ?? '');
    if (strlen($query) < 2) wp_send_json_error(['message' => 'Query too short']);

    $like = '%' . esc_sql($query) . '%';
    $results = $wpdb->get_results(
        "SELECT id, rand_id, title, LEFT(content,100) as excerpt, vote_count, comment_count, created_at
         FROM {$wpdb->prefix}ch_posts
         WHERE status='active' AND (title LIKE '$like' OR content LIKE '$like')
         ORDER BY vote_count DESC
         LIMIT 10"
    );

    wp_send_json_success(['results' => $results]);
}

function bntm_ajax_ch_update_profile() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id  = get_current_user_id();
    $name     = sanitize_text_field($_POST['display_name'] ?? '');
    $bio      = sanitize_textarea_field($_POST['bio'] ?? '');
    $location = sanitize_text_field($_POST['location'] ?? '');
    $is_anon  = (int)(!empty($_POST['is_anonymous']));

    ch_ensure_profile($user_id);

    $update_data = [
        'display_name' => $name,
        'bio'          => $bio,
        'location'     => $location,
        'is_anonymous' => $is_anon,
    ];

    // Handle avatar upload
    if (!empty($_FILES['avatar']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $file = [
            'name' => $_FILES['avatar']['name'],
            'type' => $_FILES['avatar']['type'],
            'tmp_name' => $_FILES['avatar']['tmp_name'],
            'error' => $_FILES['avatar']['error'],
            'size' => $_FILES['avatar']['size']
        ];
        $upload = wp_handle_upload($file, ['test_form' => false, 'upload_error_handler' => function($file, $message) { return ['error' => $message]; }]);
        if (!isset($upload['error'])) {
            $update_data['avatar_url'] = $upload['url'];
        }
    }

    $result = $wpdb->update("{$wpdb->prefix}ch_user_profiles", $update_data, ['user_id' => $user_id], array_fill(0, count($update_data), '%s'), ['%d']);

    if ($result !== false) wp_send_json_success(['message' => 'Profile updated!', 'avatar_url' => $update_data['avatar_url'] ?? '']);
    else                   wp_send_json_error(['message' => 'No changes made']);
}

function bntm_ajax_ch_admin_stats() {
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $stats = [
        'posts_today'    => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE DATE(created_at) = CURDATE()"),
        'comments_today' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_comments WHERE DATE(created_at) = CURDATE()"),
        'new_users_week' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_user_profiles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
    ];

    wp_send_json_success($stats);
}

function bntm_ajax_ch_live_stats() {
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    check_ajax_referer('ch_live_stats_nonce', 'nonce');

    $stats = ch_update_live_stats();
    wp_send_json_success($stats);
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function ch_ensure_profile($user_id) {
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id));
    if (!$exists) {
        $user = get_userdata($user_id);
        $wpdb->insert("{$wpdb->prefix}ch_user_profiles", [
            'user_id'      => $user_id,
            'display_name' => $user->display_name ?: $user->user_login,
        ], ['%d','%s']);
    }
    return true;
}

function ch_create_notification($user_id, $type, $actor_id, $post_id = 0, $comment_id = 0) {
    global $wpdb;
    $wpdb->insert("{$wpdb->prefix}ch_notifications", [
        'rand_id'    => bntm_rand_id(),
        'user_id'    => $user_id,
        'type'       => $type,
        'actor_id'   => $actor_id,
        'post_id'    => $post_id,
        'comment_id' => $comment_id,
    ], ['%s','%d','%s','%d','%d','%d']);
}

function ch_extract_mentions($content) {
    global $wpdb;
    $mentioned_users = [];

    // Extract @username patterns
    if (preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches)) {
        $usernames = array_unique($matches[1]);

        foreach ($usernames as $username) {
            // Find user by display name or user_login
            $user = $wpdb->get_row($wpdb->prepare(
                "SELECT ID FROM {$wpdb->users} WHERE user_login = %s OR display_name = %s LIMIT 1",
                $username, $username
            ));

            if ($user) {
                $mentioned_users[] = $user->ID;
            }
        }
    }

    return $mentioned_users;
}

function ch_highlight_mentions($content) {
    // Highlight @mentions with a special class
    return preg_replace('/(@[a-zA-Z0-9_]+)/', '<span class="ch-mention">$1</span>', $content);
}

function bntm_ajax_ch_mention_search() {
    global $wpdb;
    $query = sanitize_text_field($_POST['query'] ?? '');
    if (strlen($query) < 1) {
        wp_send_json_success(['users' => []]);
        return;
    }
    $like = '%' . $wpdb->esc_like($query) . '%';
    $users = $wpdb->get_results($wpdb->prepare(
        "SELECT u.user_login, p.display_name
         FROM {$wpdb->users} u
         LEFT JOIN {$wpdb->prefix}ch_user_profiles p ON u.ID = p.user_id
         WHERE u.user_login LIKE %s OR p.display_name LIKE %s
         ORDER BY u.user_login ASC
         LIMIT 8",
        $like, $like
    ));
    $result = array_map(fn($u) => [
        'username'     => $u->user_login,
        'display_name' => $u->display_name ?: $u->user_login,
    ], $users);
    wp_send_json_success(['users' => $result]);
}

function ch_log_activity($action, $target_type = null, $target_id = 0, $details = '') {
    global $wpdb;
    $wpdb->insert("{$wpdb->prefix}ch_activity_logs", [
        'admin_id'    => get_current_user_id(),
        'action'      => $action,
        'target_type' => $target_type,
        'target_id'   => $target_id,
        'details'     => $details,
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
    ], ['%d','%s','%s','%d','%s','%s']);
}

// ============================================================
// GLOBAL STYLES
// ============================================================

function ch_global_styles() {
    ob_start(); ?>
    <style>
    @import url('https://api.fontshare.com/v2/css?f[]=satoshi@300,400,500,700&display=swap');

    :root {
        --ch-accent:       #FF7551;
        --ch-accent-dark:  #FF6640;
        --ch-accent-light: #FFE3DA;
        --ch-accent-mid:   #FF9A7F;
        --ch-bg:           #FFFFFF;
        --ch-surface:      #FFF8F5;
        --ch-border:       #EAE4E1;
        --ch-border-soft:  #F4EFEC;
        --ch-text:         #1E1E22;
        --ch-text-muted:   #5F616B;
        --ch-text-subtle:  #8A8E99;
        --ch-radius-sm:    6px;
        --ch-radius:       10px;
        --ch-radius-lg:    14px;
        --ch-radius-xl:    18px;
        --ch-shadow-sm:    0 1px 4px rgba(255,117,81,0.10);
        --ch-shadow:       0 2px 12px rgba(255,117,81,0.14);
        --ch-shadow-md:    0 4px 20px rgba(255,117,81,0.18);
        --ch-font:         'Satoshi', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .ch-dashboard-wrap,.ch-feed-wrap,.ch-post-view-wrap,
    .ch-auth-wrap,.ch-my-feed-wrap,.ch-public-profile-wrap,
    .ch-guest-landing-wrap,.ch-mf-page-wrap {
        font-family: var(--ch-font); color: var(--ch-text);
        line-height: 1.55; background: var(--ch-bg);
    }

    /* TOP NAV */
    .ch-top-nav {
        background: var(--ch-surface); border-bottom: 1px solid var(--ch-border);
        padding: 0 24px; height: 56px;
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; z-index: 200; box-shadow: 0 2px 10px rgba(255,117,81,0.12);
    }
    .ch-nav-links { display: flex; gap: 2px; height: 100%; align-items: center; }
    .ch-nav-label { display: inline; } /* hidden at 480px breakpoint */
    .ch-nav-link {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 6px 14px; border-radius: var(--ch-radius-sm);
        text-decoration: none; font-size: 13.5px; font-weight: 500;
        color: var(--ch-text-muted); transition: all 0.15s; height: 36px;
    }
    .ch-nav-link:hover  { background: var(--ch-accent-light); color: var(--ch-accent); }
    .ch-nav-link.active { color: var(--ch-accent); font-weight: 600; position: relative; }
    .ch-nav-link.active::after {
        content: ''; position: absolute; bottom: -10px; left: 0; right: 0;
        height: 2px; background: var(--ch-accent); border-radius: 2px 2px 0 0;
    }
    .ch-user-bar { display: flex; align-items: center; gap: 8px; }
    .ch-icon-action-btn {
        width: 34px; height: 34px; border-radius: var(--ch-radius-sm);
        border: 1px solid var(--ch-border); background: var(--ch-surface);
        cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
        position: relative; color: var(--ch-text-muted); transition: all 0.15s;
    }
    .ch-icon-action-btn:hover { background: var(--ch-accent-light); border-color: var(--ch-accent-mid); color: var(--ch-accent); }
    .ch-avatar-btn {
        width: 34px; height: 34px; border-radius: 50%;
        background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
        color: white; font-size: 13px; font-weight: 700; border: none; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .ch-avatar-btn:hover { transform: scale(1.05); box-shadow: 0 2px 10px rgba(99,102,241,0.4); }
    .ch-avatar-btn-lg { width: 40px; height: 40px; font-size: 15px; flex-shrink: 0; }
    .ch-notification-badge {
        position: absolute; top: -4px; right: -4px;
        background: #ef4444; color: #fff; border-radius: 999px;
        font-size: 9px; font-weight: 700; min-width: 16px; height: 16px;
        display: flex; align-items: center; justify-content: center;
        padding: 0 3px; border: 2px solid var(--ch-surface); pointer-events: none; z-index: 1;
    }

    /* DROPDOWNS */
    .ch-dropdown-panel {
        position: fixed; background: var(--ch-surface); border: 1px solid var(--ch-border);
        border-radius: var(--ch-radius-lg); box-shadow: var(--ch-shadow-md);
        min-width: 300px; max-width: 360px; z-index: 9999; overflow: hidden;
    }
    .ch-dropdown-panel-sm { min-width: 210px; max-width: 240px; }
    .ch-dropdown-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 16px; border-bottom: 1px solid var(--ch-border-soft);
        font-size: 13px; font-weight: 600; color: var(--ch-text);
    }
    .ch-dropdown-action {
        background: none; border: none; color: var(--ch-accent); font-size: 12px;
        cursor: pointer; padding: 3px 8px; border-radius: var(--ch-radius-sm); font-weight: 500;
    }
    .ch-dropdown-action:hover { background: var(--ch-accent-light); }
    .ch-dropdown-footer { padding: 10px 16px; border-top: 1px solid var(--ch-border-soft); text-align: center; }
    .ch-dropdown-footer a { color: var(--ch-accent); text-decoration: none; font-size: 12.5px; font-weight: 500; }
    .ch-dropdown-user-info { display: flex; align-items: center; gap: 10px; padding: 14px 16px; }
    .ch-dropdown-username { font-size: 13.5px; font-weight: 600; color: var(--ch-text); }
    .ch-dropdown-usermeta { font-size: 11.5px; color: var(--ch-text-subtle); margin-top: 1px; }
    .ch-dropdown-divider { height: 1px; background: var(--ch-border-soft); margin: 3px 0; }
    .ch-dropdown-item {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 16px; font-size: 13px; color: var(--ch-text-muted);
        text-decoration: none; transition: background 0.1s;
    }
    .ch-dropdown-item:hover { background: var(--ch-bg); color: var(--ch-text); }
    .ch-dropdown-item-danger { color: #ef4444; }
    .ch-dropdown-item-danger:hover { background: #fff5f5; color: #dc2626; }
    .ch-notifications-list { max-height: 360px; overflow-y: auto; }
    .ch-notification-item {
        display: flex; align-items: flex-start; gap: 11px;
        padding: 12px 16px; border-bottom: 1px solid var(--ch-border-soft);
        cursor: pointer; transition: background 0.12s; position: relative;
        border-left: 3px solid transparent;
    }
    .ch-notification-item:last-child { border-bottom: none; }
    .ch-notification-item:hover { background: var(--ch-bg); }
    /* ── Unread state ── */
    .ch-notification-item.unread {
        background: color-mix(in srgb, var(--ch-accent) 5%, var(--ch-surface));
        border-left-color: var(--ch-accent);
    }
    .ch-notification-item.unread:hover {
        background: color-mix(in srgb, var(--ch-accent) 9%, var(--ch-surface));
    }
    .ch-notification-item.unread .ch-notification-message { font-weight: 600; color: var(--ch-text); }
    .ch-notification-item.unread .ch-notification-time   { color: var(--ch-accent); }
    /* ── Unread dot ── */
    .ch-notification-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: var(--ch-accent); flex-shrink: 0; margin-top: 5px;
        transition: opacity 0.2s;
    }
    .ch-notification-item:not(.unread) .ch-notification-dot { opacity: 0; }
    /* ── Icon ── */
    .ch-notification-icon {
        width: 34px; height: 34px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        transition: background 0.2s;
    }
    .ch-notification-icon.type-reply        { background: #ede9fe; color: #7c3aed; }
    .ch-notification-icon.type-mention      { background: #fef3c7; color: #d97706; }
    .ch-notification-icon.type-vote         { background: #dcfce7; color: #16a34a; }
    .ch-notification-icon.type-announcement { background: #dbeafe; color: #2563eb; }
    .ch-notification-icon.type-report_resolved { background: #fce7f3; color: #db2777; }
    .ch-notification-icon.type-default      { background: var(--ch-accent-light); color: var(--ch-accent); }
    .ch-notification-item:not(.unread) .ch-notification-icon { opacity: 0.55; }
    /* ── Content ── */
    .ch-notification-content { flex: 1; min-width: 0; }
    .ch-notification-message { font-size: 13px; line-height: 1.45; margin: 0 0 3px; color: var(--ch-text-muted); }
    .ch-notification-time { font-size: 11px; color: var(--ch-text-subtle); }
    .ch-no-notifications { padding: 32px 16px; text-align: center; color: var(--ch-text-subtle); font-size: 13px; }

    /* DASHBOARD LAYOUT */
    .ch-dashboard-wrap { display: flex; min-height: 600px; }
    .ch-sidebar {
        width: 210px; flex-shrink: 0; background: var(--ch-surface);
        border-right: 1px solid var(--ch-border); padding: 20px 0;
        box-shadow: inset -1px 0 0 rgba(255,117,81,0.08);
    }
    .ch-sidebar-header {
        display: flex; align-items: center; gap: 9px;
        padding: 0 18px 18px; font-weight: 700; font-size: 15px; color: var(--ch-accent);
        border-bottom: 1px solid var(--ch-border-soft); margin-bottom: 10px; letter-spacing: -0.2px;
    }
    .ch-main-content { flex: 1; min-width: 0; padding: 28px 32px 48px; background: var(--ch-bg); }
    .ch-nav { display: flex; flex-direction: column; gap: 1px; padding: 0 10px; }
    .ch-nav-item {
        display: flex; align-items: center; gap: 9px;
        padding: 8px 12px; border-radius: var(--ch-radius-sm);
        text-decoration: none; font-size: 13.5px; font-weight: 500;
        color: var(--ch-text-muted); transition: all 0.15s;
    }
    .ch-nav-item svg { opacity: 0.7; flex-shrink: 0; }
    .ch-nav-item:hover { background: var(--ch-accent-light); color: var(--ch-accent); }
    .ch-nav-item:hover svg { opacity: 1; }
    .ch-nav-item.active {
        background: var(--ch-accent-light); color: var(--ch-accent); font-weight: 600;
        border-left: 3px solid var(--ch-accent); padding-left: 9px;
    }
    .ch-nav-item.active svg { opacity: 1; }

    /* PAGE HEADER */
    .ch-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 22px; }
    .ch-page-header h1 { font-size: 20px; font-weight: 700; margin: 0 0 3px; color: var(--ch-text); letter-spacing: -0.3px; }
    .ch-page-header p  { font-size: 13px; color: var(--ch-text-muted); margin: 0; }

    /* STATS GRID */
    .ch-stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(175px,1fr)); gap: 14px; margin-bottom: 22px; }
    .ch-stat-card {
        background: var(--ch-surface); border: 1px solid var(--ch-border);
        border-radius: var(--ch-radius-lg); padding: 16px 18px;
        display: flex; align-items: center; gap: 12px;
        box-shadow: var(--ch-shadow-sm); transition: box-shadow 0.15s;
    }
    .ch-stat-card:hover { box-shadow: var(--ch-shadow); }
    .ch-stat-card.ch-stat-alert { border-color: #fca5a5; background: #fffbfb; }
    .ch-stat-icon { width: 40px; height: 40px; border-radius: var(--ch-radius); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ch-stat-body { display: flex; flex-direction: column; }
    .ch-stat-num   { font-size: 22px; font-weight: 700; line-height: 1.1; letter-spacing: -0.5px; }
    .ch-stat-label { font-size: 11.5px; color: var(--ch-text-muted); margin-top: 2px; font-weight: 500; }

    /* CARDS */
    .ch-card {
        background: var(--ch-surface); border: 1px solid var(--ch-border);
        border-radius: var(--ch-radius-lg); overflow: hidden; margin-bottom: 18px;
        box-shadow: 0 2px 8px rgba(255,117,81,0.10), 0 0 0 1px rgba(255,117,81,0.04);
    }
    .ch-card-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 14px 20px; border-bottom: 1px solid var(--ch-border-soft); background: var(--ch-surface);
    }
    .ch-card-header h3 { margin: 0; font-size: 14px; font-weight: 600; color: var(--ch-text); }
    .ch-card-body { padding: 0; }
    .ch-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

    /* LISTS */
    .ch-list-scroll { max-height: 360px; overflow-y: auto; }
    .ch-list-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 20px; border-bottom: 1px solid var(--ch-border-soft); transition: background 0.1s;
    }
    .ch-list-item:hover { background: var(--ch-bg); }
    .ch-list-item:last-child { border-bottom: none; }
    .ch-list-item-main { flex: 1; min-width: 0; }
    .ch-list-title { font-size: 13.5px; font-weight: 600; margin: 3px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--ch-text); }
    .ch-list-meta  { font-size: 11.5px; color: var(--ch-text-muted); }
    .ch-list-item-stats { display: flex; gap: 10px; font-size: 11.5px; color: var(--ch-text-subtle); flex-shrink: 0; margin-left: 12px; }
    .ch-trending-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 20px; border-bottom: 1px solid var(--ch-border-soft); transition: background 0.1s;
    }
    .ch-trending-item:hover { background: var(--ch-bg); }
    .ch-trending-item:last-child { border-bottom: none; }
    .ch-trending-rank { font-size: 18px; font-weight: 800; color: var(--ch-accent-mid); min-width: 26px; text-align: center; }
    .ch-trending-content { flex: 1; min-width: 0; }
    .ch-trending-score { font-size: 11.5px; font-weight: 600; color: var(--ch-accent); background: var(--ch-accent-light); padding: 2px 8px; border-radius: 20px; }

    /* BADGES */
    .ch-cat-badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
    .ch-status-badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .ch-status-active    { background: #d1fae5; color: #065f46; }
    .ch-status-archived  { background: var(--ch-bg); color: var(--ch-text-muted); }
    .ch-status-removed   { background: #fee2e2; color: #991b1b; }
    .ch-status-pending   { background: #fef3c7; color: #92400e; }
    .ch-status-suspended { background: #fed7aa; color: #9a3412; }
    .ch-status-banned    { background: #fee2e2; color: #991b1b; }
    .ch-status-reviewed  { background: #dbeafe; color: #1e40af; }
    .ch-status-resolved  { background: #d1fae5; color: #065f46; }
    .ch-status-dismissed { background: var(--ch-bg); color: var(--ch-text-muted); }
    .ch-pin-badge { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 600; color: var(--ch-accent); background: var(--ch-accent-light); padding: 2px 6px; border-radius: var(--ch-radius-sm); margin-bottom: 3px; }
    .ch-anon-badge { font-size: 10px; background: var(--ch-bg); color: var(--ch-text-subtle); padding: 1px 6px; border-radius: 10px; margin-left: 5px; }
    .ch-type-badge { padding: 2px 8px; border-radius: var(--ch-radius-sm); font-size: 11px; font-weight: 600; background: var(--ch-bg); color: var(--ch-text-muted); }

    /* TABLE */
    .bntm-table-wrapper { overflow-x: auto; }
    .bntm-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .bntm-table th { background: var(--ch-bg); padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 600; color: var(--ch-text-subtle); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--ch-border); }
    .bntm-table td { padding: 11px 16px; border-bottom: 1px solid var(--ch-border-soft); vertical-align: middle; }
    .bntm-table tr:hover td { background: var(--ch-bg); }
    .bntm-table tr:last-child td { border-bottom: none; }
    .ch-table th { background: var(--ch-bg); }
    .ch-table-empty { text-align: center; padding: 40px; color: var(--ch-text-subtle); font-size: 13px; }
    .ch-post-cell { max-width: 280px; }
    .ch-ann-title   { color: var(--ch-text); }
    .ch-ann-excerpt { color: var(--ch-text-subtle); }
    .ch-post-excerpt { font-size: 12px; color: var(--ch-text-muted); margin: 2px 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ch-mini-stat { display: inline-flex; align-items: center; gap: 3px; font-size: 12px; color: var(--ch-text-muted); margin-right: 8px; }
    .ch-date { font-size: 11.5px; color: var(--ch-text-subtle); }
    .ch-action-code { font-size: 11px; background: var(--ch-bg); padding: 2px 6px; border-radius: var(--ch-radius-sm); font-family: monospace; color: var(--ch-accent); }
    .ch-info-list { display: flex; flex-direction: column; gap: 10px; padding: 14px 0; }
    .ch-info-item { display: flex; justify-content: space-between; align-items: center; font-size: 13px; padding: 0 20px; }
    .ch-info-label { color: var(--ch-text-muted); font-size: 12.5px; }
    .ch-user-cell { display: flex; align-items: center; gap: 8px; }

    /* AVATAR */
    .ch-avatar-sm { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark)); color: white; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ch-avatar-xs { width: 24px; height: 24px; font-size: 10px; }

    /* BUTTONS */
    .ch-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: var(--ch-radius); font-size: 13px; font-weight: 600; font-family: var(--ch-font); border: none; cursor: pointer; transition: transform 0.12s ease, box-shadow 0.16s ease, background-color 0.16s ease, color 0.16s ease; text-decoration: none; white-space: nowrap; letter-spacing: -0.1px; }
    .ch-btn-primary   { background: var(--ch-accent); color: white; }
    .ch-btn-primary:hover { background: var(--ch-accent-dark); box-shadow: 0 2px 10px rgba(255,117,81,0.35); }
    .ch-btn:active { transform: translateY(1px) scale(0.99); }
    .ch-btn-secondary { background: var(--ch-bg); color: var(--ch-text); border: 1px solid var(--ch-border); }
    .ch-btn-secondary:hover { background: var(--ch-accent-light); border-color: var(--ch-accent-mid); color: var(--ch-accent); }
    .ch-btn-outline   { background: transparent; color: var(--ch-accent); border: 1px solid var(--ch-accent-mid); }
    .ch-btn-outline:hover { background: var(--ch-accent-light); }
    .ch-btn-danger    { background: #ef4444; color: white; }
    .ch-btn-danger:hover { background: #dc2626; }
    .ch-btn-full { width: 100%; justify-content: center; }
    .ch-btn-sm   { padding: 5px 12px; font-size: 12px; }
    .ch-btn-xs   { padding: 3px 10px; font-size: 11px; font-weight: 600; border-radius: var(--ch-radius-sm); border: none; cursor: pointer; transition: all 0.12s; }
    .ch-btn-warning { background: #fef3c7; color: #92400e; }
    .ch-btn-warning:hover { background: #fde68a; }
    .ch-btn-success { background: #d1fae5; color: #065f46; }
    .ch-btn-success:hover { background: #a7f3d0; }
    .ch-icon-btn { width: 28px; height: 28px; border-radius: var(--ch-radius-sm); border: 1px solid var(--ch-border); background: var(--ch-surface); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s; color: var(--ch-text-muted); }
    .ch-icon-btn:hover { background: var(--ch-accent-light); border-color: var(--ch-accent-mid); color: var(--ch-accent); }
    .ch-icon-btn-danger:hover { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }
    .ch-link-btn { font-size: 12px; color: var(--ch-accent); text-decoration: none; font-weight: 600; }
    .ch-link-btn:hover { text-decoration: underline; }
    .ch-actions-row { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; justify-content: flex-end; }

    /* FORM */
    .ch-input { width: 100%; padding: 8px 12px; border: 1px solid var(--ch-border); border-radius: var(--ch-radius); font-size: 13.5px; font-family: var(--ch-font); background: var(--ch-surface); color: var(--ch-text); box-sizing: border-box; outline: none; transition: border-color 0.15s, box-shadow 0.15s; }
    .ch-input:focus { border-color: var(--ch-accent); box-shadow: 0 0 0 3px rgba(255,117,81,0.16); }

    /* Lightweight motion (GPU-friendly transforms/opacity only) */
    .ch-card, .ch-cat-card, .ch-stat-card { animation: ch-fade-up 0.2s ease-out; }
    .ch-notification-item.unread .ch-notification-dot { animation: ch-dot-pulse 1.8s ease-in-out infinite; }
    @keyframes ch-fade-up { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes ch-dot-pulse { 0%,100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.25); opacity: .8; } }
    .ch-input::placeholder { color: var(--ch-text-subtle); }
    .ch-textarea { resize: vertical; min-height: 80px; line-height: 1.55; }
    .ch-textarea-lg { min-height: 130px; }
    .ch-color-input { height: 40px; padding: 3px 6px; cursor: pointer; }
    .ch-select-sm { padding: 6px 10px; font-size: 13px; border-radius: var(--ch-radius-sm); }
    .ch-field-group { margin-bottom: 14px; }
    .ch-field-row { display: flex; gap: 14px; }
    .ch-field-half { flex: 1; }
    .ch-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--ch-text); margin-bottom: 5px; }
    .ch-checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; color: var(--ch-text-muted); }

    /* TOOLBAR */
    .ch-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px; }
    .ch-toolbar-left { display: flex; align-items: center; gap: 10px; }
    .ch-toolbar-filters { display: flex; gap: 4px; }
    .ch-toolbar-right { display: flex; gap: 8px; align-items: center; }
    .ch-filter-btn { padding: 5px 13px; border-radius: 20px; font-size: 12.5px; font-weight: 500; text-decoration: none; color: var(--ch-text-muted); background: var(--ch-surface); border: 1px solid var(--ch-border); transition: all 0.15s; }
    .ch-filter-btn:hover { background: var(--ch-accent-light); border-color: var(--ch-accent-mid); color: var(--ch-accent); }
    .ch-filter-btn.active { background: var(--ch-accent-light); color: var(--ch-accent); font-weight: 600; border-color: var(--ch-accent-mid); }
    .ch-guidelines-link { display: flex; align-items: center; gap: 5px; font-size: 12.5px; font-weight: 500; color: var(--ch-text-muted); text-decoration: none; padding: 5px 10px; border-radius: var(--ch-radius-sm); transition: all 0.15s; }
    .ch-guidelines-link:hover { background: var(--ch-accent-light); color: var(--ch-accent); }
    .ch-search-form { display: flex; gap: 6px; align-items: center; }
    .ch-search-input { min-width: 180px; }

    /* MODAL */
    .ch-modal-overlay { position: fixed; inset: 0; background: rgba(26,26,46,0.45); display: flex; align-items: center; justify-content: center; z-index: 99999; padding: 20px; backdrop-filter: blur(2px); }
    .ch-modal { background: var(--ch-surface); border-radius: var(--ch-radius-xl); width: 100%; max-width: 490px; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 60px rgba(26,26,46,0.2); }
    .ch-modal-lg { max-width: 620px; }
    .ch-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 22px; border-bottom: 1px solid var(--ch-border-soft); }
    .ch-modal-header h3 { margin: 0; font-size: 15px; font-weight: 700; color: var(--ch-text); }
    .ch-modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--ch-text-subtle); line-height: 1; padding: 2px 4px; border-radius: var(--ch-radius-sm); transition: background 0.1s; }
    .ch-modal-close:hover { background: var(--ch-bg); color: var(--ch-text); }
    .ch-modal-body { padding: 20px 22px; }
    .ch-modal-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 14px 22px; border-top: 1px solid var(--ch-border-soft); }
    .ch-reason-textarea { min-height: 90px; }

    /* ── FACEBOOK-STYLE COMPOSER MODAL ── */
    .ch-composer-modal { max-width: 548px; border-radius: 12px; overflow: hidden; }
    .ch-composer-header { display: flex; align-items: center; justify-content: center; position: relative; padding: 14px 52px; }
    .ch-composer-header h3 { margin: 0; font-size: 17px; font-weight: 700; color: var(--ch-text); letter-spacing: -0.2px; }
    .ch-composer-close { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); width: 34px; height: 34px; border-radius: 50%; border: none; background: var(--ch-bg); color: var(--ch-text-muted); font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s, color 0.15s; }
    .ch-composer-close:hover { background: var(--ch-border); color: var(--ch-text); }
    .ch-composer-divider { height: 1px; background: var(--ch-border-soft); margin: 0; }
    .ch-composer-body { padding: 14px 16px 8px; display: flex; flex-direction: column; gap: 10px; }

    /* Author row with avatar */
    .ch-composer-author-row { display: flex; align-items: flex-start; gap: 10px; }
    .ch-composer-avatar { width: 40px; height: 40px; min-width: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark)); color: #fff; font-size: 16px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ch-composer-author-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
    .ch-composer-author-name { font-size: 14px; font-weight: 700; color: var(--ch-text); line-height: 1.2; }
    .ch-composer-meta-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .ch-composer-cat-select { font-size: 12px; font-weight: 600; font-family: var(--ch-font); color: var(--ch-text); background: var(--ch-bg); border: 1px solid var(--ch-border); border-radius: 6px; padding: 4px 8px; cursor: pointer; outline: none; max-width: 200px; transition: border-color 0.15s; }
    .ch-composer-cat-select:focus { border-color: var(--ch-accent); }
    .ch-composer-anon-toggle { display: flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; color: var(--ch-text-muted); cursor: pointer; user-select: none; white-space: nowrap; }
    .ch-composer-anon-toggle input { accent-color: var(--ch-accent); cursor: pointer; }

    /* Guest name */
    .ch-composer-guest-name { width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid var(--ch-border-soft); padding: 6px 2px; font-size: 13px; font-family: var(--ch-font); color: var(--ch-text); background: transparent; outline: none; }
    .ch-composer-guest-name:focus { border-bottom-color: var(--ch-accent); }
    .ch-composer-guest-name::placeholder { color: var(--ch-text-subtle); }

    /* Title field */
    .ch-composer-title { width: 100%; box-sizing: border-box; border: none; padding: 4px 2px; font-size: 15px; font-weight: 600; font-family: var(--ch-font); color: var(--ch-text); background: transparent; outline: none; border-bottom: 1px solid var(--ch-border-soft); }
    .ch-composer-title:focus { border-bottom-color: var(--ch-accent); }
    .ch-composer-title::placeholder { color: var(--ch-text-subtle); font-weight: 400; }

    /* Main textarea */
    .ch-composer-textarea { width: 100%; box-sizing: border-box; border: none; resize: none; font-size: 15px; font-family: var(--ch-font); color: var(--ch-text); background: transparent; outline: none; line-height: 1.55; min-height: 90px; }
    .ch-composer-textarea::placeholder { color: var(--ch-text-subtle); }

    /* Tags row */
    .ch-composer-tags-row { display: flex; align-items: center; gap: 7px; padding: 6px 10px; background: var(--ch-bg); border-radius: 8px; border: 1px solid var(--ch-border-soft); }
    .ch-composer-tags-row svg { flex-shrink: 0; color: var(--ch-text-subtle); }
    .ch-composer-tags-input { flex: 1; border: none; background: transparent; font-size: 13px; font-family: var(--ch-font); color: var(--ch-text); outline: none; }
    .ch-composer-tags-input::placeholder { color: var(--ch-text-subtle); }

    /* Media upload area */
    .ch-composer-media-area { display: block; border: 2px dashed var(--ch-border); border-radius: 10px; cursor: pointer; transition: border-color 0.15s, background 0.15s; text-decoration: none; }
    .ch-composer-media-area:hover { border-color: var(--ch-accent-mid); background: var(--ch-accent-light); }
    .ch-composer-media-inner { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; padding: 16px 12px; color: var(--ch-text-muted); }
    .ch-composer-media-inner svg { color: var(--ch-text-subtle); }
    .ch-composer-media-inner span { font-size: 13.5px; font-weight: 600; }
    .ch-composer-media-sub { font-size: 11.5px !important; font-weight: 400 !important; color: var(--ch-text-subtle); }
    .ch-composer-media-preview { display: flex; flex-wrap: wrap; gap: 6px; }
    .ch-composer-media-preview img, .ch-composer-media-preview video { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--ch-border); }

    /* Footer */
    .ch-composer-footer { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-top: 1px solid var(--ch-border-soft); gap: 10px; }
    .ch-composer-footer-hint { display: flex; align-items: center; gap: 5px; font-size: 11.5px; color: var(--ch-text-subtle); flex: 1; min-width: 0; }
    .ch-composer-footer-hint svg { flex-shrink: 0; color: var(--ch-text-subtle); }
    .ch-composer-submit { padding: 9px 28px; font-size: 14px; border-radius: 8px; }

    /* Dark mode tweaks */
    .ch-dark .ch-composer-modal { background: var(--ch-surface); }
    .ch-dark .ch-composer-cat-select { background: var(--ch-bg); color: var(--ch-text); border-color: var(--ch-border); }
    .ch-dark .ch-composer-media-area { border-color: var(--ch-border); }
    .ch-dark .ch-composer-close { background: rgba(255,255,255,0.08); }
    .ch-dark .ch-composer-close:hover { background: rgba(255,255,255,0.15); color: var(--ch-text); }

    /* Responsive */
    @media (max-width: 520px) {
        .ch-composer-modal { border-radius: 0; max-height: 100dvh; }
        .ch-composer-body { padding: 12px 12px 6px; }
        .ch-composer-footer { padding: 10px 12px; }
        .ch-composer-submit { padding: 8px 18px; }
        .ch-composer-footer-hint { display: none; }
    }

    /* ── COMPOSER TRIGGER CARD (Feed) ── */
    .ch-composer-trigger-card {
        display: flex; align-items: center; gap: 10px;
        background: var(--ch-surface);
        border: 1px solid var(--ch-border-soft);
        border-radius: var(--ch-radius-lg);
        padding: 10px 14px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s;
        outline: none;
    }
    .ch-composer-trigger-card:hover { border-color: var(--ch-accent-mid); box-shadow: 0 2px 10px rgba(99,102,241,0.08); }
    .ch-composer-trigger-card:focus-visible { border-color: var(--ch-accent); box-shadow: 0 0 0 3px var(--ch-accent-light); }
    .ch-composer-trigger-avatar {
        width: 38px; height: 38px; min-width: 38px; border-radius: 50%;
        background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
        color: #fff; font-size: 15px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ch-composer-trigger-guest { background: var(--ch-bg); border: 1.5px solid var(--ch-border); color: var(--ch-text-subtle); }
    .ch-composer-trigger-input {
        flex: 1; min-width: 0;
        background: var(--ch-bg);
        border: 1px solid var(--ch-border-soft);
        border-radius: 20px;
        padding: 8px 16px;
        font-size: 14px; color: var(--ch-text-subtle);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        transition: border-color 0.15s;
        pointer-events: none;
        display: flex; align-items: center; gap: 6px;
    }
    .ch-composer-trigger-card:hover .ch-composer-trigger-input { border-color: var(--ch-accent-mid); color: var(--ch-text-muted); }
    .ch-composer-trigger-locked { color: var(--ch-text-subtle); cursor: default; }
    .ch-composer-trigger-actions {
        display: flex; align-items: center; gap: 4px; flex-shrink: 0;
    }
    .ch-composer-trigger-btn {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 6px 10px; border-radius: 6px;
        font-size: 12.5px; font-weight: 600; color: var(--ch-text-muted);
        transition: background 0.13s, color 0.13s;
        white-space: nowrap;
    }
    .ch-composer-trigger-btn:hover { background: var(--ch-accent-light); color: var(--ch-accent); }
    .ch-composer-trigger-btn-tag:hover { background: #fef3c7; color: #92400e; }
    @media (max-width: 600px) {
        .ch-composer-trigger-actions { display: none; }
        .ch-composer-trigger-input { font-size: 13px; padding: 7px 14px; }
    }
    @media (max-width: 400px) {
        .ch-composer-trigger-card { padding: 8px 10px; gap: 8px; }
        .ch-composer-trigger-avatar { width: 32px; height: 32px; min-width: 32px; font-size: 13px; }
    }

    /* GUIDELINES */
    .ch-guidelines-content h4 { margin: 0 0 14px; font-size: 15px; font-weight: 700; color: var(--ch-text); }
    .ch-guidelines-content h5 { margin: 18px 0 7px; font-size: 13px; font-weight: 600; color: var(--ch-accent); }
    .ch-guidelines-content ul { margin: 6px 0 14px; padding-left: 18px; }
    .ch-guidelines-content li { margin-bottom: 4px; font-size: 13.5px; color: var(--ch-text-muted); line-height: 1.55; }
    .ch-guidelines-content p { margin: 10px 0; font-size: 13.5px; color: var(--ch-text-muted); line-height: 1.6; }
    .ch-guidelines-content strong { font-weight: 600; color: var(--ch-text); }

    /* CATEGORIES */
    .ch-page-header-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; justify-content: flex-end; }
    .ch-page-header-actions > .ch-btn { height: 36px; }
    .ch-categories-filter-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin: 0; }
    .ch-categories-filter-form .ch-field-group { flex: 1; min-width: 160px; margin: 0; }
    .ch-categories-filter-sidebar { flex-direction: column; align-items: stretch; }
    .ch-categories-filter-sidebar .ch-field-group { width: 100%; margin-bottom: 6px; }
    .ch-categories-filter-sidebar button { width: 100%; }
    .ch-filter-actions { display: flex; gap: 6px; align-items: flex-end; }
    .ch-categories-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: 14px; }
    .ch-cat-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); overflow: hidden; transition: box-shadow 0.15s, transform 0.15s; box-shadow: var(--ch-shadow-sm); }
    .ch-cat-card:hover { box-shadow: var(--ch-shadow); transform: translateY(-1px); }
    .ch-cat-card-color { height: 3px; }
    .ch-cat-card-body { padding: 14px 16px; }
    .ch-cat-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 7px; }
    .ch-cat-card-header h4 { margin: 0; font-size: 14px; font-weight: 600; color: var(--ch-text); }
    .ch-cat-desc { font-size: 12.5px; color: var(--ch-text-muted); margin: 0 0 10px; line-height: 1.5; }
    .ch-cat-stats { display: flex; gap: 10px; align-items: center; font-size: 11.5px; color: var(--ch-text-subtle); flex-wrap: wrap; }

    /* PAGINATION */
    .ch-pagination { display: flex; gap: 4px; padding: 14px 20px; justify-content: center; }
    .ch-page-btn { width: 32px; height: 32px; border-radius: var(--ch-radius-sm); display: flex; align-items: center; justify-content: center; font-size: 12.5px; font-weight: 500; text-decoration: none; color: var(--ch-text-muted); background: var(--ch-surface); border: 1px solid var(--ch-border); transition: all 0.15s; }
    .ch-page-btn:hover { background: var(--ch-accent-light); border-color: var(--ch-accent-mid); color: var(--ch-accent); }
    .ch-page-btn.active { background: var(--ch-accent); color: white; border-color: var(--ch-accent); }

    /* EMPTY STATE */
    .ch-empty { text-align: center; padding: 28px; color: var(--ch-text-subtle); font-size: 13px; }
    .ch-empty-state { text-align: center; padding: 56px 20px; color: var(--ch-text-subtle); }
    .ch-empty-state p { margin-top: 12px; font-size: 13.5px; }

    /* NOTICES */
    .bntm-notice { padding: 10px 14px; border-radius: var(--ch-radius); font-size: 13px; margin-bottom: 14px; font-weight: 500; }
    .bntm-notice-success { background: #d1fae5; color: #065f46; border-left: 3px solid #10b981; }
    .bntm-notice-error   { background: #fee2e2; color: #991b1b; border-left: 3px solid #ef4444; }
    .bntm-notice-warning { background: #fef3c7; color: #92400e; border-left: 3px solid #f59e0b; }

    /* FEED LAYOUT */
    .ch-feed-wrap { display: flex; gap: 24px; max-width: 1120px; margin: 0 auto; padding: 24px 20px; }
    .ch-feed-sidebar { width: 220px; flex-shrink: 0; }
    .ch-feed-main { flex: 1; min-width: 0; }
    .ch-sidebar-widget { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); padding: 14px; margin-bottom: 14px; box-shadow: var(--ch-shadow-sm); }
    .ch-sidebar-widget h4 { margin: 0 0 10px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.9px; color: var(--ch-text-subtle); }
    .ch-cat-link { display: flex; align-items: center; gap: 7px; padding: 6px 8px; border-radius: var(--ch-radius-sm); text-decoration: none; font-size: 13px; color: var(--ch-text-muted); transition: all 0.15s; justify-content: space-between; }
    .ch-cat-link:hover { background: var(--ch-bg); color: var(--ch-text); }
    .ch-cat-link.active { background: var(--ch-accent-light); color: var(--ch-accent); font-weight: 600; }
    .ch-cat-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .ch-cat-count { font-size: 11px; background: var(--ch-bg); color: var(--ch-text-subtle); padding: 1px 6px; border-radius: 10px; }
    .ch-cat-item { display: flex; align-items: center; justify-content: space-between; gap: 4px; }
    .ch-cat-owner-actions { display: none; align-items: center; gap: 2px; flex-shrink: 0; }
    .ch-cat-item:hover .ch-cat-owner-actions { display: flex; }
    .ch-cat-action-btn { width: 20px; height: 20px; border-radius: 4px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--ch-text-subtle); transition: background 0.15s, color 0.15s; padding: 0; }
    .ch-cat-action-btn:hover { background: var(--ch-bg); color: var(--ch-text); }
    .ch-cat-action-delete:hover { background: #fee2e2; color: #ef4444; }

    /* CAT HERO */
    .ch-cat-hero { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); padding: 18px 20px; margin-bottom: 16px; box-shadow: var(--ch-shadow-sm); }
    .ch-cat-hero h1,.ch-cat-hero h2 { margin: 0 0 4px; font-size: 17px; font-weight: 700; letter-spacing: -0.2px; }
    .ch-cat-hero p  { margin: 0; font-size: 12.5px; color: var(--ch-text-muted); }
    .ch-cat-hero-main { display: flex; align-items: flex-start; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; }
    .ch-cat-hero-text { flex: 1; }
    .ch-cat-hero-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .ch-cat-meta-item { display: flex; flex-direction: column; text-align: center; }
    .ch-cat-meta-num { font-size: 16px; font-weight: 700; color: var(--ch-text); }
    .ch-cat-meta-label { font-size: 11px; color: var(--ch-text-subtle); font-weight: 500; }
    .ch-btn-sm { height: 32px; padding: 5px 12px; font-size: 12.5px; }

    /* FEED TOOLBAR */
    .ch-feed-header { margin-bottom: 16px; }
    .ch-feed-header h2 { font-size: 18px; font-weight: 700; margin: 0 0 12px; letter-spacing: -0.2px; }
    .ch-feed-toolbar-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); padding: 12px 14px; margin-bottom: 14px; box-shadow: var(--ch-shadow-sm); }
    .ch-filter-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .ch-location-form { flex-shrink: 0; }
    .ch-location-select { width: 150px; padding: 5px 9px; border-radius: var(--ch-radius-sm); font-size: 12.5px; border: 1px solid var(--ch-border); background: var(--ch-surface); font-family: var(--ch-font); color: var(--ch-text-muted); }
    .ch-sort-tabs { display: flex; gap: 3px; }
    .ch-sort-tab { padding: 4px 12px; border-radius: 20px; font-size: 12.5px; font-weight: 500; text-decoration: none; color: var(--ch-text-muted); background: var(--ch-bg); transition: all 0.15s; border: 1px solid var(--ch-border); }
    .ch-sort-tab:hover  { background: var(--ch-accent-light); color: var(--ch-accent); border-color: var(--ch-accent-mid); }
    .ch-sort-tab.active { background: var(--ch-accent); color: #fff; border-color: var(--ch-accent); font-weight: 600; }

    /* POST CARDS */
    .ch-posts-list { display: flex; flex-direction: column; gap: 8px; }
    .ch-post-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); padding: 16px 16px 12px; display: flex; gap: 12px; transition: box-shadow 0.15s, border-color 0.15s; position: relative; box-shadow: var(--ch-shadow-sm); }
    .ch-post-card:hover { box-shadow: var(--ch-shadow); border-color: var(--ch-accent-mid); }
    .ch-post-pinned-ribbon { position: absolute; top: -1px; right: 16px; background: var(--ch-accent); color: white; font-size: 10px; font-weight: 700; padding: 2px 9px 4px; border-radius: 0 0 7px 7px; display: inline-flex; align-items: center; gap: 4px; }
    .ch-post-vote-col { display: flex; flex-direction: column; align-items: center; gap: 3px; flex-shrink: 0; padding-top: 2px; min-width: 28px; }
    .ch-vote-btn { width: 28px; height: 28px; border-radius: var(--ch-radius-sm); border: 1px solid var(--ch-border); background: var(--ch-surface); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--ch-text-subtle); transition: all 0.15s; }
    .ch-vote-btn:hover { border-color: var(--ch-accent-mid); color: var(--ch-accent); background: var(--ch-accent-light); }
    .ch-vote-btn.active-up { background: var(--ch-accent-light); border-color: var(--ch-accent); color: var(--ch-accent); }
    .ch-vote-btn.active-down { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }
    .ch-vote-btn-lg.active-up { background: var(--ch-accent-light); border-color: var(--ch-accent); color: var(--ch-accent); }
    .ch-vote-btn-lg.active-down { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }
    .ch-comment-action.active-up { color: var(--ch-accent); font-weight: 700; }
    .ch-comment-action.active-down { color: #ef4444; font-weight: 700; }
    .ch-vote-count { font-size: 12px; font-weight: 700; color: var(--ch-text); min-width: 20px; text-align: center; }
    .ch-post-body { flex: 1; min-width: 0; }
    .ch-post-meta-row { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; margin-bottom: 7px; }
    .ch-post-author { font-size: 12px; font-weight: 600; color: var(--ch-text-muted); }
    .ch-post-location { display: inline-flex; align-items: center; gap: 3px; font-size: 11.5px; color: var(--ch-text-subtle); }
    .ch-post-time   { font-size: 11.5px; color: var(--ch-text-subtle); margin-left: auto; }
    .ch-post-views  { font-size: 11.5px; color: var(--ch-text-subtle); }
    .ch-post-title-link { text-decoration: none; color: var(--ch-text); display: block; }
    .ch-post-title-link:visited { color: var(--ch-text); }
    .ch-post-title-link:hover { color: var(--ch-accent); }
    .ch-post-title-link:hover .ch-post-title { color: var(--ch-accent); }
    .ch-post-title  { margin: 0 0 6px; font-size: 14.5px; font-weight: 700; line-height: 1.4; letter-spacing: -0.2px; color: inherit; }
    .ch-post-title a { text-decoration: none; color: var(--ch-text); }
    .ch-post-title a:hover { color: var(--ch-accent); }
    .ch-post-preview { font-size: 13px; color: var(--ch-text-muted); margin: 0 0 9px; line-height: 1.6; }
    .ch-post-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
    .ch-tag { font-size: 11px; background: var(--ch-bg); color: var(--ch-text-subtle); padding: 2px 7px; border-radius: 20px; cursor: pointer; transition: all 0.1s; border: 1px solid var(--ch-border); }
    .ch-tag:hover { background: var(--ch-accent-light); color: var(--ch-accent); border-color: var(--ch-accent-mid); }
    .ch-post-actions-row { display: flex; gap: 2px; align-items: center; padding-top: 8px; border-top: 1px solid var(--ch-border-soft); flex-wrap: wrap; }
    .ch-post-action { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; color: var(--ch-text-subtle); background: none; border: none; cursor: pointer; padding: 4px 9px; border-radius: var(--ch-radius-sm); text-decoration: none; transition: all 0.15s; font-weight: 500; font-family: var(--ch-font); }
    .ch-post-action:hover { color: var(--ch-accent); background: var(--ch-accent-light); }
    .ch-post-action.ch-bookmarked { color: var(--ch-accent); }
    .ch-share-dropdown { position: relative; margin-left: auto; }
    .ch-share-menu { position: fixed; background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); box-shadow: var(--ch-shadow-md); min-width: 155px; display: none; z-index: 10000; overflow: hidden; }
    .ch-share-menu.show { display: block; }
    .ch-share-option { display: flex; align-items: center; gap: 8px; width: 100%; padding: 9px 13px; font-size: 12.5px; color: var(--ch-text-muted); background: none; border: none; cursor: pointer; text-align: left; transition: background 0.1s; font-family: var(--ch-font); }
    .ch-share-option:hover { background: var(--ch-bg); color: var(--ch-accent); }

    /* MODERATION SETTINGS */
    .ch-settings-grid { display: flex; flex-direction: column; gap: 0; }
    .ch-setting-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 16px 0; border-bottom: 1px solid var(--ch-border-soft); }
    .ch-setting-row:last-child { border-bottom: none; }
    .ch-setting-info { flex: 1; min-width: 0; }
    .ch-setting-label { font-size: 13.5px; font-weight: 600; color: var(--ch-text); margin-bottom: 3px; }
    .ch-setting-desc  { font-size: 12.5px; color: var(--ch-text-muted); line-height: 1.5; }
    .ch-setting-control { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .ch-setting-number { width: 76px; text-align: center; }
    .ch-setting-unit { font-size: 12.5px; color: var(--ch-text-subtle); white-space: nowrap; }
    .ch-toggle { position: relative; display: inline-block; cursor: pointer; }
    .ch-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
    .ch-toggle-track { display: block; width: 42px; height: 22px; border-radius: 11px; background: var(--ch-border); transition: background 0.2s; position: relative; }
    .ch-toggle input:checked + .ch-toggle-track { background: var(--ch-accent); }
    .ch-toggle-thumb { position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; border-radius: 50%; background: white; box-shadow: 0 1px 4px rgba(0,0,0,0.18); transition: transform 0.2s; }
    .ch-toggle input:checked + .ch-toggle-track .ch-toggle-thumb { transform: translateX(20px); }

    /* POST VIEW */
    .ch-post-view-wrap { max-width: 980px; margin: 0 auto; padding: 22px 16px; }
    .ch-post-view-header { margin-bottom: 18px; }
    .ch-back-link { display: inline-flex; align-items: center; gap: 5px; text-decoration: none; color: var(--ch-accent); font-size: 13.5px; font-weight: 600; padding: 5px 10px; border-radius: var(--ch-radius-sm); border: 1px solid var(--ch-accent-mid); background: var(--ch-accent-light); transition: all 0.15s; }
    .ch-back-link:hover { background: var(--ch-accent); color: white; }
    .ch-post-view-grid { display: grid; grid-template-columns: 1fr 250px; gap: 22px; }
    .ch-post-full { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-xl); padding: 26px; margin-bottom: 18px; box-shadow: var(--ch-shadow-sm); }
    .ch-post-full-title { font-size: 22px; font-weight: 700; margin: 0 0 14px; line-height: 1.3; letter-spacing: -0.4px; color: var(--ch-text); }
    .ch-post-full-content { font-size: 14.5px; line-height: 1.72; color: var(--ch-text-muted); margin-bottom: 16px; }
    .ch-post-media { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; }
    .ch-media-item { border-radius: var(--ch-radius); overflow: hidden; }
    .ch-media-image { width: 100%; height: auto; display: block; }
    .ch-media-video { width: 100%; height: auto; display: block; }
    .ch-media-audio { width: 100%; display: block; }
    .ch-post-vote-bar { display: flex; align-items: center; gap: 10px; padding-top: 14px; border-top: 1px solid var(--ch-border-soft); flex-wrap: wrap; }
    .ch-vote-btn-lg { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: var(--ch-radius); border: 1px solid var(--ch-border); background: var(--ch-surface); cursor: pointer; font-size: 12.5px; font-weight: 600; color: var(--ch-text-muted); transition: all 0.15s; font-family: var(--ch-font); }
    .ch-vote-btn-lg:hover { background: var(--ch-bg); }
    .ch-vote-btn-lg.ch-vote-up:hover  { background: var(--ch-accent-light); border-color: var(--ch-accent-mid); color: var(--ch-accent); }
    .ch-vote-btn-lg.ch-vote-down:hover{ background: #fee2e2; border-color: #fca5a5; color: #ef4444; }
    .ch-vote-btn-lg.ch-danger         { color: #ef4444; border-color: #fca5a5; }
    .ch-vote-btn-lg.ch-danger:hover   { background: #fee2e2; border-color: #ef4444; }
    .ch-vote-score { font-size: 15px; font-weight: 700; color: var(--ch-accent); }

    /* COMMENTS */
    .ch-comments-section { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-xl); padding: 22px; box-shadow: var(--ch-shadow-sm); }
    .ch-comments-title { font-size: 15px; font-weight: 700; margin: 0 0 18px; color: var(--ch-text); }
    .ch-comment-form { display: flex; gap: 10px; margin-bottom: 20px; }
    .ch-comment-input-wrap { flex: 1; }
    .ch-comment-form-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 7px; }
    .ch-comments-list { display: flex; flex-direction: column; gap: 2px; }
    .ch-comment { display: flex; gap: 10px; padding: 12px 0; border-top: 1px solid var(--ch-border-soft); }
    .ch-comment:first-child { border-top: none; }
    .ch-comment-reply { padding-left: 10px; }
    .ch-comment-body { flex: 1; }
    .ch-comment-header { display: flex; align-items: center; gap: 7px; margin-bottom: 5px; }
    .ch-comment-header strong { font-size: 13px; color: var(--ch-text); }
    .ch-comment-time { font-size: 11px; color: var(--ch-text-subtle); }
    .ch-comment-body p { font-size: 13.5px; color: var(--ch-text-muted); margin: 0 0 7px; line-height: 1.6; }
    .ch-comment-actions { display: flex; gap: 10px; }
    .ch-comment-action { font-size: 12px; color: var(--ch-text-subtle); background: none; border: none; cursor: pointer; padding: 0; transition: color 0.15s; font-family: var(--ch-font); }
    .ch-comment-action:hover { color: var(--ch-accent); }
    .ch-danger-action:hover  { color: #ef4444; }
    .ch-mention { background-color: var(--ch-accent-light); color: var(--ch-accent-dark); padding: 1px 5px; border-radius: var(--ch-radius-sm); font-weight: 500; font-size: 0.95em; }
    .ch-mention-dropdown { position: absolute; z-index: 9999; background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius); box-shadow: 0 6px 20px rgba(0,0,0,0.12); min-width: 200px; max-width: 280px; overflow: hidden; }
    .ch-mention-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; cursor: pointer; font-size: 13.5px; transition: background 0.1s; }
    .ch-mention-item:hover, .ch-mention-item.active { background: var(--ch-accent-light); }
    .ch-mention-item-avatar { width: 26px; height: 26px; border-radius: 50%; background: var(--ch-accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
    .ch-mention-item-name { font-weight: 600; color: var(--ch-text); line-height: 1.2; }
    .ch-mention-item-handle { font-size: 11.5px; color: var(--ch-text-subtle); }
    .ch-mention-no-results { padding: 10px 14px; font-size: 13px; color: var(--ch-text-subtle); }
    .ch-replies { margin-top: 2px; padding-left: 14px; border-left: 2px solid var(--ch-border-soft); }
    .ch-reply-form { margin-top: 8px; padding: 10px; background: var(--ch-bg); border-radius: var(--ch-radius); }
    .ch-login-prompt { font-size: 13.5px; color: var(--ch-text-muted); }
    .ch-login-prompt a { color: var(--ch-accent); }
    .ch-post-view-sidebar > .ch-sidebar-widget { margin-bottom: 14px; }

    /* AUTH PAGE */
    .ch-auth-wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 16px; background: var(--ch-bg); font-family: var(--ch-font); }
    .ch-auth-card { background: var(--ch-surface); border-radius: var(--ch-radius-xl); box-shadow: var(--ch-shadow-md); border: 1px solid var(--ch-border); padding: 32px 36px; width: 100%; max-width: 440px; }
    .ch-auth-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--ch-border-soft); }
    .ch-auth-logo { width: 46px; height: 46px; border-radius: var(--ch-radius-lg); background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark)); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ch-auth-brand-name    { font-size: 18px; font-weight: 800; color: var(--ch-text); letter-spacing: -0.3px; }
    .ch-auth-brand-tagline { font-size: 12px; color: var(--ch-text-subtle); margin-top: 1px; }
    .ch-auth-tabs { display: flex; background: var(--ch-bg); border-radius: var(--ch-radius); padding: 3px; margin-bottom: 20px; border: 1px solid var(--ch-border); }
    .ch-auth-tab { flex: 1; text-align: center; padding: 7px 0; border-radius: var(--ch-radius-sm); font-size: 13.5px; font-weight: 600; text-decoration: none; color: var(--ch-text-muted); transition: all 0.2s; }
    .ch-auth-tab.active { background: var(--ch-surface); color: var(--ch-accent); box-shadow: var(--ch-shadow-sm); }
    .ch-auth-form .ch-field-group { margin-bottom: 16px; }
    .ch-auth-form .ch-label { font-size: 12.5px; font-weight: 600; color: var(--ch-text); margin-bottom: 5px; display: block; }
    .ch-auth-form .ch-input { width: 100%; padding: 9px 13px; font-size: 13.5px; border: 1px solid var(--ch-border); border-radius: var(--ch-radius); outline: none; transition: border 0.15s, box-shadow 0.15s; box-sizing: border-box; background: var(--ch-surface); color: var(--ch-text); }
    .ch-auth-form .ch-input:focus { border-color: var(--ch-accent); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .ch-password-wrap    { position: relative; }
    .ch-password-toggle  { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--ch-text-subtle); display: flex; align-items: center; padding: 4px; transition: color 0.15s; }
    .ch-password-toggle:hover { color: var(--ch-accent); }
    .ch-password-strength { height: 3px; border-radius: 2px; margin-top: 7px; background: var(--ch-border); overflow: hidden; }
    .ch-password-strength::after { content: ''; display: block; height: 100%; border-radius: 2px; transition: width 0.3s, background 0.3s; }
    .ch-password-strength[data-strength="0"]::after { width: 0%; }
    .ch-password-strength[data-strength="1"]::after { width: 25%; background: #ef4444; }
    .ch-password-strength[data-strength="2"]::after { width: 50%; background: #f59e0b; }
    .ch-password-strength[data-strength="3"]::after { width: 75%; background: #3b82f6; }
    .ch-password-strength[data-strength="4"]::after { width: 100%; background: #10b981; }
    .ch-auth-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 7px; }
    .ch-auth-submit { height: 42px; font-size: 14px; border-radius: var(--ch-radius); margin-top: 2px; }
    .ch-auth-submit:disabled { opacity: 0.65; cursor: not-allowed; }
    .ch-auth-switch { text-align: center; font-size: 12.5px; color: var(--ch-text-muted); margin: 14px 0 0; }
    .ch-auth-link   { color: var(--ch-accent); text-decoration: none; font-weight: 600; }
    .ch-auth-link:hover { text-decoration: underline; }
    .ch-required { color: #ef4444; }
    .ch-optional { color: var(--ch-text-subtle); font-weight: 400; }
    .ch-auth-footer { text-align: center; font-size: 11.5px; color: var(--ch-text-subtle); margin-top: 18px; max-width: 380px; line-height: 1.5; }
    #ch-auth-msg .bntm-notice-success { background:#d1fae5; color:#065f46; padding:9px 13px; border-radius:var(--ch-radius); font-size:12.5px; margin-bottom:14px; }
    #ch-auth-msg .bntm-notice-error   { background:#fee2e2; color:#991b1b; padding:9px 13px; border-radius:var(--ch-radius); font-size:12.5px; margin-bottom:14px; }

    /* GUEST LANDING */
    .ch-guest-landing-wrap { display: flex; gap: 24px; max-width: 1100px; margin: 0 auto; padding: 24px 20px; }
    .ch-guest-sidebar { width: 220px; flex-shrink: 0; }
    .ch-guest-main { flex: 1; min-width: 0; }
    .ch-guest-right { width: 250px; flex-shrink: 0; }
    .ch-guest-helper { margin: -4px 0 12px; font-size: 12.5px; color: var(--ch-text-subtle); line-height: 1.5; }
    .ch-guest-cta { margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--ch-border-soft); }

    /* MY FEED */
    .ch-my-feed-wrap { max-width: 840px; margin: 0 auto; padding: 26px 20px; }
    .ch-mf-profile-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-xl); padding: 24px; display: flex; gap: 20px; align-items: flex-start; margin-bottom: 14px; box-shadow: var(--ch-shadow-sm); }
    .ch-mf-avatar-wrap { display: flex; flex-direction: column; align-items: center; gap: 7px; flex-shrink: 0; }
    .ch-mf-avatar-img { width: 82px; height: 82px; border-radius: 50%; object-fit: cover; border: 3px solid var(--ch-border); }
    .ch-mf-avatar-initials { width: 82px; height: 82px; border-radius: 50%; background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark)); color: #fff; font-size: 30px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
    .ch-mf-profile-info { flex: 1; min-width: 0; }
    .ch-mf-name-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 5px; }
    .ch-mf-name { font-size: 21px; font-weight: 800; color: var(--ch-text); margin: 0; letter-spacing: -0.3px; }
    .ch-mf-bio { font-size: 13.5px; color: var(--ch-text-muted); margin: 5px 0 8px; line-height: 1.6; }
    .ch-mf-meta-row { display: flex; flex-wrap: wrap; gap: 12px; }
    .ch-mf-meta-item { display: flex; align-items: center; gap: 4px; font-size: 12.5px; color: var(--ch-text-subtle); }
    .ch-mf-stats-bar { display: flex; gap: 9px; margin-bottom: 14px; flex-wrap: wrap; }
    .ch-mf-stat { flex: 1; min-width: 95px; background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); padding: 14px 10px; text-align: center; box-shadow: var(--ch-shadow-sm); }
    .ch-mf-stat-num { display: block; font-size: 20px; font-weight: 800; color: var(--ch-accent); letter-spacing: -0.3px; }
    .ch-mf-stat-label { display: flex; align-items: center; justify-content: center; gap: 3px; font-size: 10.5px; color: var(--ch-text-subtle); margin-top: 3px; text-transform: uppercase; letter-spacing: 0.4px; font-weight: 600; }
    .ch-mf-subnav { display: flex; gap: 3px; background: var(--ch-bg); border-radius: var(--ch-radius); padding: 3px; margin-bottom: 18px; border: 1px solid var(--ch-border); }
    .ch-mf-subnav-item { flex: 1; text-align: center; padding: 7px 0; border-radius: var(--ch-radius-sm); font-size: 13px; font-weight: 600; text-decoration: none; color: var(--ch-text-muted); transition: all .18s; }
    .ch-mf-subnav-item.active { background: var(--ch-surface); color: var(--ch-accent); box-shadow: var(--ch-shadow-sm); }
    .ch-mf-content { display: flex; flex-direction: column; gap: 10px; }
    .ch-mf-post-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); padding: 16px 18px; transition: border-color 0.15s, box-shadow 0.15s; box-shadow: var(--ch-shadow-sm); }
    .ch-mf-post-card:hover { border-color: var(--ch-accent-mid); box-shadow: var(--ch-shadow); }
    .ch-mf-post-top { display: flex; align-items: center; gap: 7px; margin-bottom: 8px; flex-wrap: wrap; }
    .ch-mf-time { font-size: 11.5px; color: var(--ch-text-subtle); margin-left: auto; }
    .ch-mf-pinned-badge { font-size: 11px; color: var(--ch-accent); font-weight: 600; }
    .ch-mf-post-actions { display: flex; gap: 5px; margin-left: 6px; }
    .ch-mf-post-title { font-size: 15px; font-weight: 700; color: var(--ch-text); text-decoration: none; display: block; margin-bottom: 5px; line-height: 1.4; letter-spacing: -0.2px; }
    .ch-mf-post-title:hover { color: var(--ch-accent); }
    .ch-mf-post-excerpt { font-size: 13px; color: var(--ch-text-muted); margin: 0 0 10px; line-height: 1.55; }
    .ch-mf-post-footer { display: flex; gap: 14px; font-size: 12px; color: var(--ch-text-subtle); }
    .ch-mf-comment-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); padding: 14px 18px; box-shadow: var(--ch-shadow-sm); }
    .ch-mf-comment-post-ref { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--ch-text-subtle); margin-bottom: 7px; }
    .ch-mf-ref-link { color: var(--ch-accent); text-decoration: none; font-weight: 600; }
    .ch-mf-comment-content { font-size: 13.5px; color: var(--ch-text-muted); margin: 0 0 8px; line-height: 1.55; }
    .ch-mf-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; padding: 52px 20px; text-align: center; background: var(--ch-surface); border: 1px dashed var(--ch-border); border-radius: var(--ch-radius-lg); }
    .ch-mf-empty p { font-size: 14px; color: var(--ch-text-subtle); margin: 0; }

    /* PUBLIC PROFILE */
    .ch-public-profile-wrap { max-width: 780px; margin: 0 auto; padding: 24px 20px; }
    .ch-public-profile-header { margin-bottom: 18px; }
    .ch-public-profile-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-xl); padding: 24px; display: flex; gap: 22px; align-items: flex-start; margin-bottom: 16px; box-shadow: var(--ch-shadow-sm); }
    .ch-public-profile-avatar img { width: 76px; height: 76px; border-radius: 50%; object-fit: cover; border: 2px solid var(--ch-border); }
    .ch-avatar-lg { width: 76px; height: 76px; border-radius: 50%; background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark)); color: #fff; font-size: 28px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ch-public-profile-name { font-size: 20px; font-weight: 800; color: var(--ch-text); margin: 0 0 6px; letter-spacing: -0.3px; }
    .ch-public-profile-meta { display: flex; align-items: center; gap: 5px; font-size: 12.5px; color: var(--ch-text-muted); margin-bottom: 3px; }
    .ch-public-profile-bio { font-size: 13.5px; color: var(--ch-text-muted); margin: 8px 0; }
    .ch-public-profile-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-bottom: 22px; }
    .ch-public-stat-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); padding: 18px; text-align: center; box-shadow: var(--ch-shadow-sm); }
    .ch-public-stat-card .ch-stat-num { display: block; font-size: 24px; font-weight: 700; color: var(--ch-accent); letter-spacing: -0.4px; }
    .ch-public-stat-card .ch-stat-label { display: block; font-size: 11.5px; color: var(--ch-text-subtle); margin-top: 3px; font-weight: 500; }
    .ch-section-title { font-size: 16px; font-weight: 700; color: var(--ch-text); margin: 0 0 14px; }
    .ch-public-post-card { display: block; background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); padding: 16px; margin-bottom: 10px; text-decoration: none; color: inherit; transition: border-color 0.15s, box-shadow 0.15s; box-shadow: var(--ch-shadow-sm); }
    .ch-public-post-card:hover { border-color: var(--ch-accent-mid); box-shadow: var(--ch-shadow); }
    .ch-public-post-top { display: flex; align-items: center; gap: 9px; margin-bottom: 7px; }
    .ch-public-post-time { font-size: 11.5px; color: var(--ch-text-subtle); margin-left: auto; }
    .ch-public-post-title { font-size: 14.5px; font-weight: 700; color: var(--ch-text); margin: 0 0 5px; letter-spacing: -0.2px; }
    .ch-public-post-excerpt { font-size: 13px; color: var(--ch-text-muted); margin: 0 0 9px; line-height: 1.55; }
    .ch-public-post-footer { display: flex; gap: 12px; font-size: 12px; color: var(--ch-text-subtle); }

    /* VISIBILITY TOGGLE */
    .ch-visibility-toggle { display: flex; gap: 8px; }
    .ch-vis-option { flex: 1; border: 1.5px solid var(--ch-border); border-radius: var(--ch-radius); padding: 10px 12px; cursor: pointer; transition: all .15s; display: flex; flex-direction: column; gap: 3px; }
    .ch-vis-option:has(input:checked) { border-color: var(--ch-accent); background: var(--ch-accent-light); }
    .ch-vis-option input { display: none; }
    .ch-vis-label { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--ch-text); }
    .ch-vis-desc { font-size: 11px; color: var(--ch-text-subtle); }

    /* ANNOUNCEMENTS */
    .ch-announcement-card { background: linear-gradient(135deg, #fefce8, #fef9c3); border: 1px solid #fde68a; border-radius: var(--ch-radius-lg); padding: 18px 20px; margin-bottom: 14px; position: relative; overflow: hidden; }
    .ch-announcement-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #f59e0b, #d97706); }
    .ch-announcement-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .ch-announcement-icon { color: #d97706; flex-shrink: 0; }
    .ch-announcement-meta { display: flex; flex-direction: column; gap: 2px; }
    .ch-announcement-label { font-weight: 700; font-size: 11px; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px; }
    .ch-announcement-author { font-size: 12.5px; color: #a16207; }
    .ch-announcement-time   { font-size: 11.5px; color: #a16207; opacity: 0.8; }
    .ch-announcement-body { margin-left: 30px; }
    .ch-announcement-title { font-size: 16px; font-weight: 700; color: #92400e; margin: 0 0 7px; }
    .ch-announcement-content { color: #78350f; line-height: 1.6; font-size: 13.5px; }
    .ch-announcement-content p { margin: 0 0 6px 0; }
    .ch-announcement-content p:last-child { margin-bottom: 0; }

    /* ── SPECIAL PAGES (Trending / Bookmarks) ───────────────────── */
    .ch-special-page-wrap { max-width: 1100px; margin: 0 auto; padding: 0 20px 48px; }
    .ch-special-hero { display: flex; align-items: center; gap: 16px; padding: 32px 0 24px; border-bottom: 1px solid var(--ch-border); margin-bottom: 28px; }
    .ch-special-hero-icon { width: 56px; height: 56px; border-radius: var(--ch-radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ch-trending-hero .ch-special-hero-icon { background: linear-gradient(135deg,#fef3c7,#fde68a); color: #d97706; }
    .ch-dark .ch-trending-hero .ch-special-hero-icon { background: #451a03; color: #fbbf24; }
    .ch-bookmarks-hero .ch-special-hero-icon { background: linear-gradient(135deg,#ede9fe,#ddd6fe); color: #7c3aed; }
    .ch-dark .ch-bookmarks-hero .ch-special-hero-icon { background: #1e1b4b; color: #a78bfa; }
    .ch-special-hero-title { font-size: 26px; font-weight: 800; color: var(--ch-text); margin: 0 0 4px; letter-spacing: -0.4px; }
    .ch-special-hero-sub { font-size: 14px; color: var(--ch-text-muted); margin: 0; }
    .ch-special-layout { display: flex; gap: 24px; align-items: flex-start; }
    .ch-special-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 10px; }
    .ch-special-sidebar { width: 220px; flex-shrink: 0; }

    /* Trending post card */
    .ch-trending-post-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-lg); padding: 16px 18px; display: flex; gap: 14px; align-items: flex-start; box-shadow: var(--ch-shadow-sm); transition: border-color 0.15s, box-shadow 0.15s; }
    .ch-trending-post-card:hover { border-color: var(--ch-accent-mid); box-shadow: var(--ch-shadow); }
    .ch-trending-rank { font-size: 22px; font-weight: 900; color: var(--ch-accent); min-width: 32px; line-height: 1; padding-top: 2px; opacity: 0.7; }
    .ch-trending-body { flex: 1; min-width: 0; }
    .ch-trending-post-title { font-size: 15px; font-weight: 700; color: var(--ch-text); text-decoration: none; line-height: 1.4; display: block; margin-bottom: 5px; }
    .ch-trending-post-title:hover { color: var(--ch-accent); }
    .ch-trending-post-excerpt { font-size: 13px; color: var(--ch-text-muted); margin: 0 0 10px; line-height: 1.5; }
    .ch-trending-stats { display: flex; gap: 14px; }
    .ch-trending-stat { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--ch-text-subtle); }

    /* Profile standalone nav */
    .ch-profile-standalone { font-family: var(--ch-font); background: var(--ch-bg); min-height: 100vh; color: var(--ch-text); }

    /* ── DARK MODE: special pages sidebar ────────────────────────── */
    .ch-dark .ch-trending-post-card { background: var(--ch-surface); border-color: var(--ch-border); }

    /* ============================================================
       RESPONSIVE — comprehensive mobile/tablet fixes
       ============================================================ */

    /* Filter form helper classes */
    .ch-categories-filter-row { align-items: flex-end; flex-wrap: wrap; }
    .ch-filter-search-group   { flex: 1; min-width: 0; }
    .ch-filter-sort-group     { min-width: 140px; }
    .ch-filter-btn-group      { display: flex; gap: 6px; align-items: flex-end; flex-shrink: 0; }

    /* Touch targets — 44px minimum for tappable elements */
    @media (hover: none) and (pointer: coarse) {
        .ch-btn, .ch-nav-link, .ch-cat-link, .ch-filter-btn,
        .ch-vote-btn, .ch-icon-btn, .ch-page-btn,
        .ch-post-action, .ch-comment-action { min-height: 44px; }
        .ch-vote-btn, .ch-icon-btn { min-width: 44px; }
        .ch-input, .ch-textarea { font-size: 16px; } /* prevents iOS zoom on focus */
    }

    /* ── Tablet: 1024px ───────────────────────────────────────── */
    @media (max-width: 1024px) {
        .ch-feed-wrap { gap: 18px; padding: 18px 16px; }
        .ch-feed-sidebar { width: 200px; }
        .ch-post-view-grid { grid-template-columns: 1fr 220px; gap: 16px; }
        .ch-stats-grid { grid-template-columns: repeat(3, 1fr); }
        .ch-two-col { gap: 14px; }
        .ch-guest-landing-wrap { gap: 18px; }
        .ch-guest-right { width: 220px; }
        .ch-guest-sidebar { width: 200px; }
        .ch-main-content { padding: 22px 20px 40px; }
    }

    /* ── Small tablet / phablet: 780px ───────────────────────── */
    @media (max-width: 780px) {
        .ch-dashboard-wrap { flex-direction: column; }
        .ch-sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--ch-border); padding: 10px 0; }
        .ch-nav { flex-direction: row; flex-wrap: wrap; padding: 0 8px; gap: 2px; }
        .ch-nav-item { font-size: 12px; padding: 6px 10px; }
        .ch-nav-item.active { border-left: none; border-bottom: 2px solid var(--ch-accent); padding-left: 10px; }
        .ch-sidebar-header { padding: 8px 16px 12px; }
        .ch-main-content { padding: 16px 14px 28px; }
        .ch-two-col { grid-template-columns: 1fr; gap: 12px; }
        .ch-stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .ch-categories-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
        .ch-page-header { flex-direction: column; gap: 12px; }
        .ch-page-header-actions { width: 100%; justify-content: flex-start; flex-wrap: wrap; }
        .ch-page-header-actions .ch-btn { flex: 1; justify-content: center; min-width: 120px; }
        .ch-toolbar { flex-direction: column; align-items: flex-start; gap: 8px; }
        .ch-toolbar-right { width: 100%; }
        .ch-toolbar-filters { flex-wrap: wrap; }
        .ch-categories-filter-row { flex-direction: column; gap: 8px; }
        .ch-filter-search-group, .ch-filter-sort-group { width: 100%; min-width: unset; }
        .ch-filter-btn-group { width: 100%; }
        .ch-filter-btn-group .ch-btn { flex: 1; justify-content: center; }
        .ch-search-form { width: 100%; }
        .ch-search-form input { flex: 1; min-width: 0; }
        .ch-feed-wrap { flex-direction: column; padding: 14px 12px; gap: 12px; }
        .ch-feed-sidebar { width: 100%; }
        .ch-feed-toolbar-card { padding: 10px 12px; }
        .ch-filter-row { flex-wrap: wrap; gap: 8px; }
        .ch-sort-tabs { flex-wrap: wrap; }
        .ch-location-form { width: 100%; }
        .ch-location-select { width: 100%; }
        .ch-post-view-grid { grid-template-columns: 1fr; }
        .ch-post-view-wrap { padding: 14px 12px; }
        .ch-post-full { padding: 18px 16px; }
        .ch-post-full-title { font-size: 18px; }
        .ch-post-vote-bar { gap: 6px; flex-wrap: wrap; }
        .ch-vote-btn-lg { padding: 5px 10px; font-size: 12px; }
        .ch-comments-section { padding: 16px 14px; }
        .ch-comment-form { gap: 8px; }
        .ch-comment-form-footer { flex-direction: column; align-items: flex-start; gap: 8px; }
        .ch-mf-profile-card { flex-direction: column; align-items: center; text-align: center; padding: 18px 16px; }
        .ch-mf-meta-row { justify-content: center; }
        .ch-mf-stats-bar { gap: 6px; }
        .ch-mf-stat { min-width: 70px; padding: 10px 8px; }
        .ch-mf-stat-num { font-size: 17px; }
        .ch-mf-subnav-item { font-size: 12px; padding: 6px 0; }
        .ch-auth-card { padding: 22px 16px; }
        .ch-auth-wrap { padding: 20px 12px; }
        .ch-field-row { flex-direction: column; gap: 0; }
        .ch-guest-landing-wrap { flex-direction: column; padding: 14px 12px; }
        .ch-guest-sidebar, .ch-guest-right { width: 100%; }
        .ch-popular-cats-grid { grid-template-columns: repeat(2, 1fr); }
        .ch-welcome-card { padding: 24px 18px; }
        .ch-welcome-title { font-size: 19px; }
        .ch-setting-row { flex-direction: column; align-items: flex-start; gap: 10px; }
        .ch-setting-control { width: 100%; }
        .ch-setting-number { width: 100%; }
        .ch-visibility-toggle { flex-direction: column; }
        .ch-cat-hero-meta { flex-wrap: wrap; gap: 10px; }
        .ch-cat-hero { padding: 14px 16px; }
        .bntm-table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .bntm-table { min-width: 560px; font-size: 12px; }
        .bntm-table th, .bntm-table td { padding: 9px 12px; }
        .ch-post-actions-row { flex-wrap: wrap; }
        .ch-share-dropdown { margin-left: 0; }

        /* Special pages (trending / bookmarks) */
        .ch-special-page-wrap { padding: 0 14px 32px; }
        .ch-special-hero { padding: 20px 0 18px; gap: 12px; margin-bottom: 20px; }
        .ch-special-hero-title { font-size: 20px; }
        .ch-special-layout { flex-direction: column; }
        .ch-special-sidebar { width: 100%; }
    }

    /* ── Mobile: 480px ────────────────────────────────────────── */
    @media (max-width: 480px) {
        .ch-top-nav { padding: 0 12px; height: 52px; }
        .ch-nav-link { padding: 5px 9px; font-size: 12px; gap: 5px; }
        .ch-nav-label { display: none; }
        .ch-stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
        .ch-stat-card { padding: 12px 14px; gap: 10px; }
        .ch-stat-num { font-size: 19px; }
        .ch-stat-icon { width: 36px; height: 36px; }
        .ch-categories-grid { grid-template-columns: 1fr; }
        .ch-post-card { padding: 12px 12px 10px; gap: 8px; }
        .ch-post-title { font-size: 14px; }
        .ch-post-preview { font-size: 12.5px; }
        .ch-post-action { padding: 3px 7px; font-size: 11.5px; }
        .ch-post-full-title { font-size: 16px; }
        .ch-modal { border-radius: 16px; margin: 8px; width: calc(100% - 16px); }
        .ch-modal-body { padding: 14px 16px; }
        .ch-modal-footer { padding: 10px 16px; gap: 6px; }
        .ch-modal-header { padding: 13px 16px; }
        .ch-modal-footer .ch-btn { flex: 1; justify-content: center; }
        .ch-dropdown-panel { min-width: unset !important; width: calc(100vw - 24px) !important; right: 12px !important; left: 12px !important; }
        .ch-popular-cats-grid { grid-template-columns: 1fr 1fr; gap: 6px; }
        .ch-pop-cat-chip { padding: 6px 8px; font-size: 11.5px; }
        .ch-pop-cat-count { display: none; }
        .ch-mf-stats-bar { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
        .ch-mf-stat { min-width: unset; }
        .ch-mf-profile-card { padding: 16px 14px; }
        .ch-welcome-card { padding: 22px 16px; border-radius: 16px; }
        .ch-welcome-icon { width: 54px; height: 54px; border-radius: 14px; }
        .ch-welcome-title { font-size: 17px; }
        .ch-welcome-subtitle { font-size: 12.5px; margin-bottom: 16px; }
        .ch-welcome-feature { padding: 8px 10px; font-size: 12px; }
        .ch-welcome-actions { flex-direction: column; gap: 6px; }
        .ch-welcome-actions .ch-btn { width: 100%; justify-content: center; }
        .ch-auth-form .ch-field-row { flex-direction: column; gap: 0; }
        .ch-auth-card { padding: 20px 16px; }
        .ch-feed-toolbar-card { padding: 10px; }
        .ch-search-form { flex-direction: column; gap: 6px; }
        .ch-search-form button { width: 100%; justify-content: center; }
        .ch-filter-row { flex-direction: column; align-items: flex-start; }
        .ch-sort-tabs { width: 100%; justify-content: space-between; }
        .ch-sort-tab { flex: 1; text-align: center; padding: 5px 4px; font-size: 12px; }
        .ch-page-header h1 { font-size: 17px; }
        .ch-page-header p { font-size: 12px; }
        .ch-comment .ch-avatar-sm, .ch-comment-reply .ch-avatar-sm { display: none; }
        .ch-pagination { gap: 2px; padding: 10px 12px; }
        .ch-page-btn { width: 28px; height: 28px; font-size: 11.5px; }
        .ch-color-row { flex-wrap: wrap; gap: 6px; }
        .ch-color-hex-input { width: 100%; }
        .ch-card-header { flex-wrap: wrap; gap: 6px; }
        .ch-sidebar-widget { padding: 12px; }
        .ch-post-view-sidebar { display: none; }
        .ch-announcement-body { margin-left: 0; margin-top: 8px; }
        .ch-announcement-title { font-size: 14px; }

        /* Special pages on mobile */
        .ch-special-hero { flex-direction: column; text-align: center; padding: 16px 0 14px; }
        .ch-special-hero-icon { width: 46px; height: 46px; }
        .ch-special-hero-title { font-size: 18px; }
        .ch-special-hero-sub { font-size: 12.5px; }
        .ch-trending-post-card { padding: 12px 14px; gap: 10px; }
        .ch-trending-rank { font-size: 18px; min-width: 24px; }

        /* Profile standalone nav on mobile */
        .ch-profile-standalone .ch-top-nav { padding: 0 12px; }
    }

    /* ── Very small: 360px ────────────────────────────────────── */
    @media (max-width: 360px) {
        .ch-top-nav { padding: 0 8px; height: 48px; }
        .ch-nav-link { padding: 4px 7px; }
        .ch-post-card { padding: 10px 10px 8px; }
        .ch-mf-stats-bar { grid-template-columns: repeat(2, 1fr); }
        .ch-welcome-actions .ch-btn { padding: 9px 12px; }
        .ch-auth-card { padding: 18px 12px; }
        .ch-stats-grid { grid-template-columns: 1fr; }
        .ch-popular-cats-grid { grid-template-columns: 1fr; }
        .ch-filter-btn-group { flex-direction: column; }
        .ch-filter-btn-group .ch-btn { width: 100%; }
    }

    @media (max-width: 680px) {
        .ch-special-layout { flex-direction: column; }
        .ch-special-sidebar { width: 100%; }
    }

    /* ============================================================
       POPULAR CATEGORIES CARD
       ============================================================ */
    .ch-popular-cats-card {
        background: var(--ch-surface);
        border: 1px solid var(--ch-border);
        border-radius: var(--ch-radius-lg);
        padding: 14px 16px 16px;
        margin-bottom: 14px;
        box-shadow: var(--ch-shadow-sm);
    }
    .ch-popular-cats-header {
        display: flex; align-items: center; gap: 6px;
        font-size: 10.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.9px; color: var(--ch-text-subtle);
        margin-bottom: 12px;
    }
    .ch-popular-cats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    .ch-pop-cat-chip {
        display: flex; align-items: center; gap: 6px;
        padding: 7px 10px; border-radius: var(--ch-radius);
        background: var(--ch-bg); border: 1px solid var(--ch-border);
        text-decoration: none; color: var(--ch-text-muted);
        font-size: 12.5px; font-weight: 500;
        transition: all 0.15s; overflow: hidden;
    }
    .ch-pop-cat-chip:hover {
        background: var(--ch-accent-light);
        border-color: var(--ch-accent-mid);
        color: var(--ch-accent);
    }
    .ch-pop-cat-dot {
        width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
    }
    .ch-pop-cat-name {
        flex: 1; min-width: 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .ch-pop-cat-count {
        font-size: 10.5px; background: var(--ch-surface);
        color: var(--ch-text-subtle); padding: 1px 5px;
        border-radius: 10px; border: 1px solid var(--ch-border);
        flex-shrink: 0; line-height: 1.6;
    }

    /* ============================================================
       WELCOME POPUP
       ============================================================ */
    .ch-welcome-overlay {
        position: fixed; inset: 0;
        background: rgba(26,26,46,0.55);
        display: flex; align-items: center; justify-content: center;
        z-index: 999999; padding: 20px;
        backdrop-filter: blur(4px);
    }
    .ch-welcome-card {
        background: var(--ch-surface);
        border-radius: 22px;
        padding: 36px 32px 28px;
        width: 100%; max-width: 400px;
        box-shadow: 0 24px 64px rgba(99,102,241,0.22), 0 4px 16px rgba(0,0,0,0.1);
        border: 1px solid var(--ch-border);
        position: relative; overflow: hidden;
        text-align: center;
    }
    .ch-welcome-glow {
        position: absolute; top: -60px; left: 50%; transform: translateX(-50%);
        width: 200px; height: 200px; border-radius: 50%;
        background: radial-gradient(circle, rgba(99,102,241,0.18) 0%, transparent 70%);
        pointer-events: none;
    }
    .ch-welcome-close {
        position: absolute; top: 14px; right: 16px;
        background: var(--ch-bg); border: 1px solid var(--ch-border);
        border-radius: 50%; width: 28px; height: 28px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 16px; color: var(--ch-text-subtle);
        line-height: 1; transition: all 0.15s;
    }
    .ch-welcome-close:hover { background: var(--ch-accent-light); color: var(--ch-accent); border-color: var(--ch-accent-mid); }
    .ch-welcome-icon {
        width: 64px; height: 64px; border-radius: 18px;
        background: linear-gradient(135deg, var(--ch-accent), var(--ch-accent-dark));
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 18px;
        box-shadow: 0 8px 24px rgba(99,102,241,0.35);
    }
    .ch-welcome-title {
        font-size: 22px; font-weight: 800; color: var(--ch-text);
        margin: 0 0 8px; letter-spacing: -0.4px;
    }
    .ch-welcome-subtitle {
        font-size: 13.5px; color: var(--ch-text-muted);
        margin: 0 0 22px; line-height: 1.6;
    }
    .ch-welcome-features {
        display: flex; flex-direction: column; gap: 10px;
        margin-bottom: 24px; text-align: left;
    }
    .ch-welcome-feature {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px; border-radius: var(--ch-radius);
        background: var(--ch-bg); border: 1px solid var(--ch-border);
        font-size: 13px; color: var(--ch-text-muted);
    }
    .ch-welcome-feat-icon { font-size: 16px; flex-shrink: 0; }
    .ch-welcome-actions {
        display: flex; gap: 8px; margin-bottom: 12px;
    }
    .ch-welcome-skip {
        background: none; border: none; cursor: pointer;
        font-size: 12px; color: var(--ch-text-subtle);
        font-family: var(--ch-font); transition: color 0.15s;
        text-decoration: underline; text-underline-offset: 2px;
    }
    .ch-welcome-skip:hover { color: var(--ch-text-muted); }

    /* ============================================================
       SETTINGS MODAL
       ============================================================ */
    .ch-settings-section {
        padding: 6px 0;
        border-bottom: 1px solid var(--ch-border-soft);
    }
    .ch-settings-section-title {
        font-size: 10.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.8px; color: var(--ch-text-subtle);
        padding: 12px 22px 6px;
    }
    .ch-settings-row {
        display: flex; align-items: center; gap: 14px;
        padding: 11px 22px; transition: background 0.1s;
    }
    .ch-settings-row-link {
        text-decoration: none; color: inherit; cursor: pointer;
    }
    .ch-settings-row-link:hover { background: var(--ch-bg); }
    .ch-settings-row-info { flex: 1; min-width: 0; }
    .ch-settings-row-label {
        display: flex; align-items: center; gap: 8px;
        font-size: 13.5px; font-weight: 600; color: var(--ch-text);
        margin-bottom: 2px;
    }
    .ch-settings-row-desc { font-size: 12px; color: var(--ch-text-subtle); }

    /* ============================================================
       DARK MODE
       ============================================================ */
    .ch-dark {
        --ch-accent:       #FF7551;
        --ch-accent-dark:  #FF6640;
        --ch-accent-light: #2A1B17;
        --ch-accent-mid:   #8A4B3A;
        --ch-bg:           #121214;
        --ch-surface:      #1A1A1E;
        --ch-border:       #31323A;
        --ch-border-soft:  #2A2B31;
        --ch-text:         #F6F7FB;
        --ch-text-muted:   #B5B8C2;
        --ch-text-subtle:  #8E93A3;
        --ch-shadow-sm:    0 1px 4px rgba(0,0,0,0.3);
        --ch-shadow:       0 2px 12px rgba(0,0,0,0.4);
        --ch-shadow-md:    0 4px 20px rgba(0,0,0,0.5);
    }
    .ch-dark .ch-top-nav { background: var(--ch-surface); }
    .ch-dark .ch-post-card:hover { border-color: var(--ch-accent-mid); }
    .ch-dark .ch-welcome-card { background: var(--ch-surface); }
    .ch-dark .ch-pop-cat-count { background: var(--ch-bg); border-color: var(--ch-border); }
    .ch-dark .bntm-notice-success { background: #064e3b; color: #6ee7b7; }
    .ch-dark .bntm-notice-error   { background: #7f1d1d; color: #fca5a5; }
    .ch-dark .ch-announcement-card { background: linear-gradient(135deg, #1f1a08, #2a2210); border-color: #5c3d0a; }
    .ch-dark .ch-announcement-card::before { background: linear-gradient(90deg, #b45309, #92400e); }
    .ch-dark .ch-announcement-label { color: #fbbf24; }
    .ch-dark .ch-announcement-author,
    .ch-dark .ch-announcement-time { color: #d97706; }
    .ch-dark .ch-announcement-icon { color: #f59e0b; }
    .ch-dark .ch-announcement-title { color: #fcd34d; }
    .ch-dark .ch-announcement-content { color: #e5c97a; }

    /* TABLE — dark mode fix: td has no background set so it bleeds white from the card */
    .ch-dark .bntm-table tr td { background: var(--ch-surface); color: var(--ch-text); }
    .ch-dark .bntm-table tr:hover td { background: var(--ch-bg); }
    .ch-dark .bntm-table th { background: var(--ch-bg); color: var(--ch-text-subtle); border-bottom-color: var(--ch-border); }
    .ch-dark .bntm-table td { border-bottom-color: var(--ch-border-soft); }

    /* Status badges — dark mode */
    .ch-dark .ch-status-active    { background: #064e3b; color: #6ee7b7; }
    .ch-dark .ch-status-removed   { background: #7f1d1d; color: #fca5a5; }
    .ch-dark .ch-status-pending   { background: #451a03; color: #fcd34d; }
    .ch-dark .ch-status-suspended { background: #431407; color: #fdba74; }
    .ch-dark .ch-status-banned    { background: #7f1d1d; color: #fca5a5; }
    .ch-dark .ch-status-reviewed  { background: #1e3a5f; color: #93c5fd; }
    .ch-dark .ch-status-resolved  { background: #064e3b; color: #6ee7b7; }
    .ch-dark .ch-status-archived  { background: var(--ch-bg); color: var(--ch-text-muted); }
    .ch-dark .ch-status-dismissed { background: var(--ch-bg); color: var(--ch-text-muted); }

    /* Announcement admin table — JS-rendered rows use hardcoded colors */
    .ch-dark #ch-ann-tbody td { background: var(--ch-surface); color: var(--ch-text); }
    .ch-dark #ch-ann-tbody tr:hover td { background: var(--ch-bg); }
    .ch-dark #ch-ann-tbody [style*="color:#111827"] { color: var(--ch-text) !important; }
    .ch-dark #ch-ann-tbody [style*="color:#9ca3af"] { color: var(--ch-text-subtle) !important; }

    /* Button variants — dark mode */
    .ch-dark .ch-btn-secondary { background: var(--ch-bg); color: var(--ch-text); border-color: var(--ch-border); }
    .ch-dark .ch-btn-secondary:hover { background: var(--ch-accent-light); border-color: var(--ch-accent-mid); color: var(--ch-accent); }
    .ch-dark .ch-btn-warning { background: #451a03; color: #fcd34d; }
    .ch-dark .ch-btn-warning:hover { background: #78350f; }
    .ch-dark .ch-btn-success { background: #064e3b; color: #6ee7b7; }
    .ch-dark .ch-btn-success:hover { background: #065f46; }

    /* Filter buttons — dark mode */
    .ch-dark .ch-filter-btn { background: var(--ch-surface); color: var(--ch-text-muted); border-color: var(--ch-border); }
    .ch-dark .ch-filter-btn:hover { background: var(--ch-accent-light); border-color: var(--ch-accent-mid); color: var(--ch-accent); }
    .ch-dark .ch-filter-btn.active { background: var(--ch-accent-light); color: var(--ch-accent); border-color: var(--ch-accent-mid); }

    /* Notification unread item — dark mode */
    .ch-dark .ch-notification-item.unread { background: color-mix(in srgb, var(--ch-accent) 10%, var(--ch-surface)); }
    .ch-dark .ch-notification-item.unread:hover { background: color-mix(in srgb, var(--ch-accent) 15%, var(--ch-surface)); }
    .ch-dark .ch-notification-icon.type-reply        { background: #2e1065; color: #a78bfa; }
    .ch-dark .ch-notification-icon.type-mention      { background: #451a03; color: #fbbf24; }
    .ch-dark .ch-notification-icon.type-vote         { background: #052e16; color: #4ade80; }
    .ch-dark .ch-notification-icon.type-announcement { background: #1e3a5f; color: #60a5fa; }
    .ch-dark .ch-notification-icon.type-report_resolved { background: #4a044e; color: #f472b6; }

    /* Dropdown danger hover — dark mode */
    .ch-dark .ch-dropdown-item-danger:hover { background: #3b0a0a; color: #f87171; }

    /* Stat alert card — dark mode */
    .ch-dark .ch-stat-card.ch-stat-alert { border-color: #7f1d1d; background: #1a0a0a; }

    /* ── DARK MODE: PAGE & BODY BACKGROUND ──────────────────────── */
    /* WordPress body/page background bleeds through without this */
    .ch-dark body,
    html.ch-dark body { background: #121214 !important; }

    /* ── DARK MODE: FEED PAGE ELEMENTS ──────────────────────────── */
    /* Feed search bar area */
    .ch-dark .ch-feed-header { background: transparent; }
    .ch-dark .ch-location-select {
        background: var(--ch-surface); color: var(--ch-text);
        border-color: var(--ch-border);
    }
    .ch-dark .ch-location-select option { background: var(--ch-surface); color: var(--ch-text); }
    .ch-dark select, .ch-dark .ch-select-sm {
        background: var(--ch-surface); color: var(--ch-text);
        border-color: var(--ch-border);
    }
    .ch-dark select option { background: var(--ch-surface); color: var(--ch-text); }

    /* Sidebar widgets on feed */
    .ch-dark .ch-sidebar-widget { background: var(--ch-surface); border-color: var(--ch-border); }

    /* ── DARK MODE: POST CARDS ───────────────────────────────────── */
    .ch-dark .ch-vote-btn.active-down { background: #3b0a0a; border-color: #7f1d1d; color: #f87171; }
    .ch-dark .ch-vote-btn-lg.active-up { background: var(--ch-accent-light); border-color: var(--ch-accent); color: var(--ch-accent); }
    .ch-dark .ch-vote-btn-lg.active-down { background: #3b0a0a; border-color: #7f1d1d; color: #f87171; }
    .ch-dark .ch-vote-btn-lg.ch-vote-down:hover { background: #3b0a0a; border-color: #7f1d1d; color: #f87171; }
    .ch-dark .ch-vote-btn-lg.ch-danger:hover { background: #3b0a0a; border-color: #ef4444; }
    .ch-dark .ch-icon-btn-danger:hover { background: #3b0a0a; border-color: #7f1d1d; color: #f87171; }

    /* ── DARK MODE: NOTICES ──────────────────────────────────────── */
    .ch-dark .bntm-notice-warning { background: #451a03; color: #fcd34d; border-left-color: #f59e0b; }

    /* ── DARK MODE: PROFILE (MY FEED) ───────────────────────────── */
    .ch-dark .ch-mf-profile-card { background: var(--ch-surface); border-color: var(--ch-border); }
    .ch-dark .ch-mf-stat { background: var(--ch-surface); border-color: var(--ch-border); }
    .ch-dark .ch-mf-subnav { background: var(--ch-bg); border-color: var(--ch-border); }
    .ch-dark .ch-mf-subnav-item.active { background: var(--ch-surface); }
    .ch-dark .ch-mf-post-card { background: var(--ch-surface); border-color: var(--ch-border); }
    .ch-dark .ch-mf-post-card:hover { border-color: var(--ch-accent-mid); }
    /* Profile avatar image border */
    .ch-dark .ch-mf-avatar-img { border-color: var(--ch-border); }

    /* ── DARK MODE: PROFILE EDIT FORM (inline style overrides) ─── */
    .ch-dark .ch-profile-page-wrap { background: var(--ch-bg); color: var(--ch-text); }
    .ch-dark .ch-profile-page-wrap .ch-card { background: var(--ch-surface); border-color: var(--ch-border); }
    .ch-dark .ch-profile-page-wrap .ch-card-body { background: var(--ch-surface); }
    .ch-dark .ch-stat-item { background: var(--ch-bg); border-color: var(--ch-border); color: var(--ch-text); }

    /* ── DARK MODE: MODAL ────────────────────────────────────────── */
    .ch-dark .ch-modal { background: var(--ch-surface); }
    .ch-dark .ch-modal-header { border-bottom-color: var(--ch-border-soft); }
    .ch-dark .ch-modal-footer { border-top-color: var(--ch-border-soft); }

    /* ── DARK MODE: GUEST LANDING / AUTH ─────────────────────────── */
    .ch-dark .ch-auth-wrap { background: var(--ch-bg); }
    .ch-dark .ch-auth-card { background: var(--ch-surface); border-color: var(--ch-border); }
    .ch-dark .ch-auth-tab { color: var(--ch-text-muted); }
    .ch-dark .ch-auth-tab.active { color: var(--ch-accent); border-bottom-color: var(--ch-accent); }

    /* ── DARK MODE: POPULAR CATEGORY CHIPS ──────────────────────── */
    .ch-dark .ch-pop-cat-chip { background: var(--ch-surface); border-color: var(--ch-border); }
    .ch-dark .ch-pop-cat-chip:hover { background: var(--ch-accent-light); border-color: var(--ch-accent-mid); }
    .ch-dark .ch-pop-cat-name { color: var(--ch-text); }

    /* ── DARK MODE: TOP NAV ELEMENTS ─────────────────────────────── */
    .ch-dark .ch-nav-link { color: var(--ch-text-muted); }
    .ch-dark .ch-icon-action-btn { background: var(--ch-surface); border-color: var(--ch-border); color: var(--ch-text-muted); }
    /* Notification badge border matches dark nav */
    .ch-dark .ch-notification-badge { border-color: var(--ch-surface); }

    /* ============================================================
       COLOR PICKER — wheel + hex input + swatches
       ============================================================ */
    .ch-color-picker-wrap { display: flex; flex-direction: column; gap: 10px; }
    .ch-color-row {
        display: flex; align-items: center; gap: 8px;
    }
    .ch-color-wheel {
        width: 38px; height: 38px; border-radius: var(--ch-radius);
        border: 1px solid var(--ch-border); cursor: pointer;
        padding: 2px; background: var(--ch-surface);
        flex-shrink: 0; appearance: none; -webkit-appearance: none;
        overflow: hidden;
    }
    .ch-color-wheel::-webkit-color-swatch-wrapper { padding: 0; border-radius: 6px; }
    .ch-color-wheel::-webkit-color-swatch { border: none; border-radius: 6px; }
    .ch-color-wheel::-moz-color-swatch { border: none; border-radius: 6px; }
    .ch-color-hex-preview {
        width: 36px; height: 36px; border-radius: var(--ch-radius);
        border: 1px solid var(--ch-border); flex-shrink: 0;
        transition: background 0.12s, opacity 0.12s; display: block;
    }
    .ch-color-hex-input {
        flex: 1; font-family: monospace; font-size: 13px;
        letter-spacing: 0.5px; text-transform: uppercase;
    }
    .ch-color-swatches { display: flex; flex-wrap: wrap; gap: 6px; }
    .ch-swatch {
        width: 26px; height: 26px; border-radius: 6px;
        border: 2px solid transparent; cursor: pointer;
        transition: transform 0.12s, box-shadow 0.12s;
        padding: 0;
    }
    .ch-swatch:hover {
        transform: scale(1.18);
        box-shadow: 0 0 0 2px var(--ch-surface), 0 0 0 4px var(--ch-accent);
    }
    .ch-dark .ch-color-wheel { background: var(--ch-surface); border-color: var(--ch-border); }
    .ch-dark .ch-color-hex-preview { border-color: var(--ch-border); }
    .ch-dark .ch-swatch:hover { box-shadow: 0 0 0 2px var(--ch-bg), 0 0 0 4px var(--ch-accent); }
    /* ============================================================
       BUTTON LOADING STATES & SPINNER
       ============================================================ */
    @keyframes ch-spin {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }
    .ch-btn-spinner {
        animation: ch-spin 0.7s linear infinite;
        flex-shrink: 0;
    }
    .ch-btn-loading {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        pointer-events: none;
        opacity: 0.82;
    }
    .ch-btn:disabled {
        cursor: not-allowed;
        opacity: 0.72;
    }
    .ch-icon-btn:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* ============================================================
       NAV ITEM ACTIVE TRANSITION (tab switching feel)
       ============================================================ */
    .ch-nav-item {
        transition: background 0.12s ease, color 0.12s ease, border-color 0.12s ease;
    }
    .ch-nav-link {
        transition: background 0.12s ease, color 0.12s ease;
    }

    /* Subtle click feedback on nav items */
    .ch-nav-item:active,
    .ch-nav-link:active {
        opacity: 0.7;
        transform: scale(0.98);
    }

    /* ============================================================
       MODAL OPEN / CLOSE ANIMATIONS
       ============================================================ */
    .ch-modal-overlay {
        animation: ch-overlay-in 0.18s ease;
    }
    @keyframes ch-overlay-in {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    .ch-modal {
        animation: ch-modal-in 0.2s cubic-bezier(0.34, 1.3, 0.64, 1);
    }
    @keyframes ch-modal-in {
        from { opacity: 0; transform: scale(0.94) translateY(8px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    </style>
    <?php
    return ob_get_clean();
}


// ============================================================
// GLOBAL SCRIPTS (shared modals & utilities)
// ============================================================

function ch_global_scripts() {
    ob_start(); ?>
    <script>
    (function() {
        // Dark mode persistence — apply on every page load
        try {
            if (localStorage.getItem('ch_dark_mode') === '1') {
                document.documentElement.classList.add('ch-dark');
            }
        } catch(e) {}

        // ---- Composer media label updater ----
        window.chUpdateMediaLabel = function(input, labelId) {
            const label = document.getElementById(labelId);
            if (!label) return;
            const inner = label.querySelector('.ch-composer-media-inner');
            if (!inner) return;
            const count = input.files.length;
            if (count === 0) {
                inner.innerHTML = '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><span>Add photos / videos</span><span class="ch-composer-media-sub">or drag and drop</span>';
            } else {
                inner.innerHTML = '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><span>' + count + ' file' + (count !== 1 ? 's' : '') + ' selected</span><span class="ch-composer-media-sub">Click to change</span>';
            }
        };

        // ---- Color picker helpers (wheel + hex text + preview, all synced) ----
        window.chSyncColorWheel = function(wheelId, hexId, previewId) {
            const wheel = document.getElementById(wheelId);
            const hex   = document.getElementById(hexId);
            const prev  = document.getElementById(previewId);
            if (!wheel) return;
            const val = wheel.value;
            if (hex)  hex.value = val.toUpperCase();
            if (prev) { prev.style.background = val; prev.style.opacity = '1'; }
        };
        window.chSyncColorHex = function(wheelId, hexId, previewId) {
            const wheel = document.getElementById(wheelId);
            const hex   = document.getElementById(hexId);
            const prev  = document.getElementById(previewId);
            if (!hex) return;
            const val = hex.value.trim();
            if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                if (wheel) wheel.value = val;
                if (prev)  { prev.style.background = val; prev.style.opacity = '1'; }
            } else {
                if (prev) prev.style.opacity = '0.4';
            }
        };
        window.chPickSwatch = function(wheelId, hexId, previewId, color) {
            const wheel = document.getElementById(wheelId);
            const hex   = document.getElementById(hexId);
            const prev  = document.getElementById(previewId);
            if (wheel) wheel.value = color;
            if (hex)   { hex.value = color.toUpperCase(); }
            if (prev)  { prev.style.background = color; prev.style.opacity = '1'; }
        };

        // ============================================================
        // PAGE NAVIGATION LOADING BAR
        // ============================================================
        (function() {
            // Create progress bar element
            const bar = document.createElement('div');
            bar.id = 'ch-page-loading-bar';
            bar.style.cssText = [
                'position:fixed','top:0','left:0','height:5px','width:0%',
                'background:linear-gradient(90deg,#FF6640,#FF7551,#FF9A7F)',
                'z-index:99999','transition:width 0.28s ease,opacity 0.45s ease',
                'border-radius:0 3px 3px 0','box-shadow:0 0 12px rgba(255,117,81,0.7)',
                'pointer-events:none'
            ].join(';');
            document.body.appendChild(bar);

            const label = document.createElement('div');
            label.id = 'ch-page-loading-label';
            label.textContent = 'Loading...';
            label.style.cssText = [
                'position:fixed','top:8px','right:10px','padding:3px 8px',
                'font-size:11px','font-weight:700','letter-spacing:.2px',
                'background:rgba(255,117,81,.92)','color:#fff','border-radius:999px',
                'z-index:100000','opacity:0','transform:translateY(-6px)',
                'transition:opacity .2s ease,transform .2s ease','pointer-events:none'
            ].join(';');
            document.body.appendChild(label);

            let _navTimer = null;

            function chStartNavBar() {
                bar.style.transition = 'width 0.25s ease, opacity 0.1s ease';
                bar.style.opacity = '1';
                bar.style.width = '0%';
                label.style.opacity = '1';
                label.style.transform = 'translateY(0)';
                // Animate to 85% quickly then slow down
                requestAnimationFrame(() => {
                    bar.style.transition = 'width 6s cubic-bezier(0.1,0.4,0.3,1), opacity 0.1s ease';
                    bar.style.width = '88%';
                });
            }

            function chFinishNavBar() {
                bar.style.transition = 'width 0.2s ease, opacity 0.5s ease 0.2s';
                bar.style.width = '100%';
                label.style.opacity = '0';
                label.style.transform = 'translateY(-6px)';
                setTimeout(() => { bar.style.opacity = '0'; setTimeout(() => { bar.style.width = '0%'; }, 500); }, 200);
            }

            // Hook all same-page navigation links (tab navigation)
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[href]');
                if (!link) return;
                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript') || link.target === '_blank') return;
                // Only intercept internal navigation (tab switches, same-origin)
                try {
                    const url = new URL(href, window.location.href);
                    if (url.origin !== window.location.origin) return;
                } catch(err) { return; }

                chStartNavBar();
                // Fallback: clear bar if navigation stalls
                clearTimeout(_navTimer);
                _navTimer = setTimeout(chFinishNavBar, 10000);
            });

            // Finish bar when page is about to unload
            window.addEventListener('pagehide', chFinishNavBar);

            // If page was loaded (e.g. from back/forward), finish any lingering bar
            window.addEventListener('pageshow', chFinishNavBar);

            // Expose globally for AJAX-triggered reloads
            window.chNavBarStart  = chStartNavBar;
            window.chNavBarFinish = chFinishNavBar;
        })();

        // ============================================================
        // BUTTON LOADING STATE HELPERS
        // ============================================================
        const _CH_SPINNER_SVG = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>';

        /**
         * Set a button into loading state. Returns a restore function.
         * Usage:  const restore = chBtnLoading(btn, 'Saving...');
         *         ...on error: restore();
         */
        window.chBtnLoading = function(btn, loadingText) {
            if (!btn) return () => {};
            const originalHTML = btn.innerHTML;
            const originalDisabled = btn.disabled;
            btn.disabled = true;
            btn.innerHTML = _CH_SPINNER_SVG + '<span>' + (loadingText || 'Loading...') + '</span>';
            btn.classList.add('ch-btn-loading');
            return function restore(newText) {
                btn.disabled = originalDisabled;
                btn.innerHTML = newText !== undefined ? newText : originalHTML;
                btn.classList.remove('ch-btn-loading');
            };
        };

        /**
         * Helper: after AJAX success, trigger quick-reload with bar animation.
         * Delay defaults to 600ms (faster than old 1000-1200ms).
         */
        window.chReloadAfterSuccess = function(delay) {
            delay = delay !== undefined ? delay : 600;
            if (window.chNavBarStart) window.chNavBarStart();
            setTimeout(() => location.reload(), delay);
        };

        window.chOpenModal = function(id) {
            const el = document.getElementById(id);
            if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
        };
        window.chCloseModal = function(id) {
            const el = document.getElementById(id);
            if (el) { el.style.display = 'none'; document.body.style.overflow = ''; }
        };
        window.chPromptModerationReason = function(title, placeholder, onSubmit) {
            const existing = document.getElementById('ch-modal-reason');
            if (existing) existing.remove();
            const overlay = document.createElement('div');
            overlay.id = 'ch-modal-reason';
            overlay.className = 'ch-modal-overlay';
            overlay.innerHTML = `
                <div class="ch-modal" style="max-width:520px;">
                    <div class="ch-modal-header">
                        <h3>${title || 'Provide a reason'}</h3>
                        <button class="ch-modal-close" type="button">&times;</button>
                    </div>
                    <div class="ch-modal-body">
                        <textarea id="ch-reason-input" class="ch-input ch-textarea ch-reason-textarea" placeholder="${placeholder || 'Enter reason...'}"></textarea>
                    </div>
                    <div class="ch-modal-footer">
                        <button type="button" class="ch-btn ch-btn-secondary" id="ch-reason-cancel">Cancel</button>
                        <button type="button" class="ch-btn ch-btn-primary" id="ch-reason-submit">Submit</button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
            const close = () => overlay.remove();
            overlay.querySelector('.ch-modal-close')?.addEventListener('click', close);
            overlay.querySelector('#ch-reason-cancel')?.addEventListener('click', close);
            overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
            overlay.querySelector('#ch-reason-submit')?.addEventListener('click', () => {
                const reason = (overlay.querySelector('#ch-reason-input')?.value || '').trim();
                if (!reason) { alert('A reason is required.'); return; }
                close();
                if (typeof onSubmit === 'function') onSubmit(reason);
            });
            const ta = overlay.querySelector('#ch-reason-input');
            if (ta) ta.focus();
        };
        // Close on overlay click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('ch-modal-overlay')) {
                e.target.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
        window.chSharePost = function(url) {
            if (navigator.share) {
                navigator.share({url: url}).catch(() => {});
            } else {
                navigator.clipboard.writeText(url).then(() => alert('Link copied to clipboard!'));
            }
        };

        window.chToggleShareMenu = function(btn) {
            // Close other menus
            document.querySelectorAll('.ch-share-menu.show').forEach(menu => {
                if (menu !== btn.nextElementSibling) menu.classList.remove('show');
            });

            const menu = btn.nextElementSibling;
            const isVisible = menu.classList.contains('show');

            if (!isVisible) {
                // Position the menu below the button
                const rect = btn.getBoundingClientRect();
                menu.style.position = 'fixed';
                menu.style.top = (rect.bottom + 8) + 'px';
                menu.style.left = (rect.right - menu.offsetWidth) + 'px'; // Align to right edge
                menu.style.zIndex = '10000';
                menu.classList.add('show');
            } else {
                menu.classList.remove('show');
            }
        };

        window.chShareToSocial = function(platform, url, title = '') {
            let shareUrl = '';
            const encodedUrl = encodeURIComponent(url);
            const encodedTitle = encodeURIComponent(title || 'Check this out');

            switch(platform) {
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`;
                    break;
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
                    break;
                case 'linkedin':
                    shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
                    break;
                case 'copy':
                    navigator.clipboard.writeText(url).then(() => alert('Link copied to clipboard!'));
                    return;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }

            // Close the menu
            document.querySelectorAll('.ch-share-menu.show').forEach(menu => menu.classList.remove('show'));
        };

        // Close share menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.ch-share-dropdown')) {
                document.querySelectorAll('.ch-share-menu.show').forEach(menu => menu.classList.remove('show'));
            }
        });

        // Close profile and notifications menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.ch-profile-dropdown') && !e.target.closest('#ch-profile-btn')) {
                const menu = document.getElementById('ch-profile-menu');
                if (menu) menu.style.display = 'none';
            }
            if (!e.target.closest('.ch-notifications-dropdown') && !e.target.closest('#ch-notif-btn')) {
                const menu = document.getElementById('ch-notifications-menu');
                if (menu) menu.style.display = 'none';
            }
        });

        window.chToggleProfileMenu = function(e) {
            e && e.stopPropagation();
            const menu = document.getElementById('ch-profile-menu');
            const btn  = document.getElementById('ch-profile-btn');
            if (!menu || !btn) return;
            const notifMenu = document.getElementById('ch-notifications-menu');
            if (notifMenu) notifMenu.style.display = 'none';
            const isVisible = menu.style.display === 'block';
            if (!isVisible) {
                const rect = btn.getBoundingClientRect();
                menu.style.top  = (rect.bottom + 6) + 'px';
                menu.style.right = (window.innerWidth - rect.right) + 'px';
                menu.style.left = 'auto';
            }
            menu.style.display = isVisible ? 'none' : 'block';
        };

        window.chToggleNotifications = function(e) {
            e && e.stopPropagation();
            const menu = document.getElementById('ch-notifications-menu');
            const btn  = document.getElementById('ch-notif-btn');
            if (!menu || !btn) return;
            const profileMenu = document.getElementById('ch-profile-menu');
            if (profileMenu) profileMenu.style.display = 'none';
            const isVisible = menu.style.display === 'block';
            if (!isVisible) {
                const rect = btn.getBoundingClientRect();
                menu.style.top  = (rect.bottom + 6) + 'px';
                menu.style.right = (window.innerWidth - rect.right) + 'px';
                menu.style.left = 'auto';
                chLoadNotifications();
            }
            menu.style.display = isVisible ? 'none' : 'block';
        };

        window.chLoadNotifications = function() {
            fetch(ajaxurl + '?action=ch_get_notifications')
            .then(r => r.json())
            .then(json => {
                if (!json.success) return;
                const list = document.getElementById('ch-notifications-list');

                if (json.data.notifications.length === 0) {
                    list.innerHTML = '<div class="ch-no-notifications">You\'re all caught up!</div>';
                    return;
                }

                const typeIcons = {
                    reply: {
                        cls: 'type-reply',
                        svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'
                    },
                    mention: {
                        cls: 'type-mention',
                        svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"/></svg>'
                    },
                    vote: {
                        cls: 'type-vote',
                        svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>'
                    },
                    announcement: {
                        cls: 'type-announcement',
                        svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>'
                    },
                    report_resolved: {
                        cls: 'type-report_resolved',
                        svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
                    },
                };

                list.innerHTML = json.data.notifications.map(n => {
                    const postUrl   = n.post_rand_id
                        ? (chFeedUrl + '?view_post=' + encodeURIComponent(n.post_rand_id))
                        : null;
                    const clickAttr = postUrl
                        ? `onclick="chMarkNotificationRead(${n.id}, '${postUrl}', this)"`
                        : `onclick="chMarkNotificationRead(${n.id}, null, this)"`;
                    const icon = typeIcons[n.type] || { cls: 'type-default', svg: '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>' };
                    return `
                    <div class="ch-notification-item ${n.is_read ? '' : 'unread'}" ${clickAttr} data-id="${n.id}">
                        <div class="ch-notification-dot"></div>
                        <div class="ch-notification-icon ${icon.cls}">${icon.svg}</div>
                        <div class="ch-notification-content">
                            <div class="ch-notification-message">${n.message}</div>
                            <div class="ch-notification-time">${n.created_at}</div>
                        </div>
                    </div>`;
                }).join('');
            })
            .catch(error => {
                console.error('Failed to load notifications:', error);
            });
        };

        window.chMarkNotificationRead = function(notificationId, postUrl, el) {
            // Instantly update UI — remove unread state and decrement badge
            const item = el?.closest('.ch-notification-item') || document.querySelector(`.ch-notification-item[data-id="${notificationId}"]`);
            if (item && item.classList.contains('unread')) {
                item.classList.remove('unread');
                const countEl = document.getElementById('ch-notification-count');
                if (countEl && countEl.style.display !== 'none') {
                    const current = parseInt(countEl.textContent) || 0;
                    const next    = current - 1;
                    if (next <= 0) {
                        countEl.style.display = 'none';
                    } else {
                        countEl.textContent = next > 99 ? '99+' : next;
                    }
                }
            }

            // Fire mark-read in background — don't block navigation
            const fd = new FormData();
            fd.append('action', 'ch_mark_notifications');
            fd.append('notification_ids[]', notificationId);
            fetch(ajaxurl, {method:'POST', body:fd}).catch(() => {});

            // Navigate if there's a destination
            if (postUrl) {
                window.location.href = postUrl;
            }
        };

        window.chMarkAllNotificationsRead = function() {
            // Instantly clear all unread states in UI
            document.querySelectorAll('.ch-notification-item.unread').forEach(el => el.classList.remove('unread'));
            const countEl = document.getElementById('ch-notification-count');
            if (countEl) countEl.style.display = 'none';

            const fd = new FormData();
            fd.append('action', 'ch_mark_notifications');
            fetch(ajaxurl, {method:'POST', body:fd}).catch(() => {});
        };

        window.chLoadNotificationCount = function() {
            fetch(ajaxurl + '?action=ch_get_notifications')
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const countEl = document.getElementById('ch-notification-count');
                    if (json.data.unread_count > 0) {
                        countEl.textContent = json.data.unread_count > 99 ? '99+' : json.data.unread_count;
                        countEl.style.display = 'block';
                    } else {
                        countEl.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Failed to load notification count:', error);
            });
        };

        // Load notification count on page load
        if (document.getElementById('ch-notification-count')) {
            chLoadNotificationCount();
        }

        window.chPreviewAvatar = function(input) {
            if (!input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const wrap = document.getElementById('ch-avatar-preview-wrap');
                if (!wrap) return;
                let img = document.getElementById('ch-avatar-preview-img');
                const initials = document.getElementById('ch-avatar-preview-initials');
                if (initials) initials.style.display = 'none';
                if (!img) {
                    img = document.createElement('img');
                    img.id = 'ch-avatar-preview-img';
                    img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
                    wrap.appendChild(img);
                }
                img.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        };

        function chResizeImageToBlob(file, maxPx, quality, callback) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    let w = img.width, h = img.height;
                    if (w > maxPx || h > maxPx) {
                        if (w > h) { h = Math.round(h * maxPx / w); w = maxPx; }
                        else       { w = Math.round(w * maxPx / h); h = maxPx; }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    canvas.toBlob(callback, 'image/jpeg', quality || 0.8);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        window.chUpdateProfile = function(nonce) {
            const displayName = document.getElementById('ch-profile-display-name').value.trim();
            const bio         = document.getElementById('ch-profile-bio').value.trim();
            const location    = document.getElementById('ch-profile-location').value.trim();
            const isAnonymous = document.getElementById('ch-profile-anonymous').checked ? 1 : 0;
            const avatarFile  = document.getElementById('ch-profile-avatar').files[0];

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving...</span>';

            function doSave(blob) {
                const fd = new FormData();
                fd.append('action',       'ch_update_profile');
                fd.append('display_name', displayName);
                fd.append('bio',          bio);
                fd.append('location',     location);
                fd.append('is_anonymous', isAnonymous);
                fd.append('nonce',        nonce);
                if (blob) fd.append('avatar', blob, 'avatar.jpg');

                fetch(ajaxurl, {method:'POST', body:fd})
                .then(r => r.json())
                .then(json => {
                    document.getElementById('ch-profile-msg').innerHTML =
                        '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                    if (json.success) {
                        btn.textContent = 'Saved!';
                        // Update all nav avatars on the page without a full reload
                        if (json.data?.avatar_url) {
                            document.querySelectorAll('.ch-avatar-btn img, .ch-mf-avatar-img').forEach(el => {
                                el.src = json.data.avatar_url + '?t=' + Date.now();
                            });
                        }
                        setTimeout(() => { btn.disabled = false; btn.textContent = 'Save Profile'; }, 2000);
                    } else {
                        btn.disabled = false;
                        btn.textContent = 'Save Profile';
                    }
                })
                .catch(() => {
                    document.getElementById('ch-profile-msg').innerHTML = '<div class="bntm-notice bntm-notice-error">Network error. Please try again.</div>';
                    btn.disabled = false;
                    btn.textContent = 'Save Profile';
                });
            }

            if (avatarFile) {
                // Fast path: avoid client-side resize for already-small uploads.
                const isCommonImage = /^image\/(jpeg|jpg|png|webp)$/i.test(avatarFile.type || '');
                const smallEnough = avatarFile.size <= 450 * 1024;
                if (isCommonImage && smallEnough) {
                    doSave(avatarFile);
                    return;
                }
                btn.textContent = 'Optimizing image…';
                chResizeImageToBlob(avatarFile, 320, 0.8, function(blob) {
                    btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving...</span>';
                    doSave(blob);
                });
            } else {
                doSave(null);
            }
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// Reusable settings modal (dark mode toggle + sign out) for standalone pages
function ch_settings_modal_html($logout_url = '') {
    if (!$logout_url) $logout_url = wp_logout_url(home_url('/forum-feed/'));
    ob_start(); ?>
    <div id="ch-modal-settings" class="ch-modal-overlay" style="display:none;">
        <div class="ch-modal" style="max-width:380px;">
            <div class="ch-modal-header">
                <h3>Settings</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-settings')">&times;</button>
            </div>
            <div class="ch-modal-body" style="padding:0;">
                <div class="ch-settings-section">
                    <div class="ch-settings-section-title">Appearance</div>
                    <div class="ch-settings-row">
                        <div class="ch-settings-row-info">
                            <div class="ch-settings-row-label">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                                Dark Mode
                            </div>
                            <div class="ch-settings-row-desc">Switch to a darker interface</div>
                        </div>
                        <label class="ch-toggle">
                            <input type="checkbox" id="ch-dark-mode-toggle" onchange="chToggleDarkMode(this.checked)">
                            <span class="ch-toggle-track"><span class="ch-toggle-thumb"></span></span>
                        </label>
                    </div>
                </div>
                <div class="ch-settings-section" style="border-bottom:none;">
                    <div class="ch-settings-section-title" style="color:#ef4444;">Account Actions</div>
                    <a href="<?php echo esc_url($logout_url); ?>" class="ch-settings-row ch-settings-row-link" style="color:#ef4444;">
                        <div class="ch-settings-row-info">
                            <div class="ch-settings-row-label" style="color:#ef4444;">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                Sign Out
                            </div>
                        </div>
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function(){
        window.chOpenSettingsModal = function() {
            const tog = document.getElementById('ch-dark-mode-toggle');
            if (tog) tog.checked = document.documentElement.classList.contains('ch-dark');
            if (typeof chOpenModal === 'function') chOpenModal('ch-modal-settings');
        };
        window.chCloseProfileMenu = window.chCloseProfileMenu || function() {
            const m = document.getElementById('ch-profile-menu');
            if (m) m.style.display = 'none';
        };
        window.chToggleProfileMenu = window.chToggleProfileMenu || function(e) {
            e.stopPropagation();
            const m = document.getElementById('ch-profile-menu');
            if (!m) return;
            const isShown = m.style.display !== 'none';
            m.style.display = isShown ? 'none' : 'block';
            if (!isShown) {
                const btn = document.getElementById('ch-profile-btn');
                const rect = btn.getBoundingClientRect();
                m.style.position = 'fixed';
                m.style.top = (rect.bottom + 6) + 'px';
                m.style.right = (window.innerWidth - rect.right) + 'px';
            }
        };
        document.addEventListener('click', function(e) {
            const m = document.getElementById('ch-profile-menu');
            const btn = document.getElementById('ch-profile-btn');
            if (m && btn && !btn.contains(e.target) && !m.contains(e.target)) {
                m.style.display = 'none';
            }
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function ch_feed_scripts() {
    ob_start(); ?>
    <script>
    (function() {
        window.chOpenModal  = window.chOpenModal  || function(id){ document.getElementById(id).style.display='flex'; };
        window.chCloseModal = window.chCloseModal || function(id){ document.getElementById(id).style.display='none'; };

        window.chShowGuidelines = function() { chOpenModal('ch-modal-guidelines'); };

        window.chVote = function(btn, nonce) {
            const targetId   = btn.dataset.id;
            const targetType = btn.dataset.type;
            const value      = parseInt(btn.dataset.val);

            const fd = new FormData();
            fd.append('action', 'ch_vote');
            fd.append('target_id', targetId);
            fd.append('target_type', targetType);
            fd.append('value', value);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    // Update vote count displays
                    document.querySelectorAll('.ch-vote-count, .ch-vote-score').forEach(el => {
                        const inCard = el.closest('[data-id="'+targetId+'"]');
                        const isScore = el.id === 'ch-post-score' && targetType === 'post';
                        if (inCard || isScore) {
                            el.textContent = json.data.vote_count + (isScore ? ' points' : '');
                        }
                    });
                    // Also update inline comment vote count (text node inside ch-vote-up button)
                    if (targetType === 'comment') {
                        const upBtn = document.querySelector('.ch-vote-up[data-id="'+targetId+'"]');
                        if (upBtn) {
                            const textNode = [...upBtn.childNodes].find(n => n.nodeType === 3);
                            if (textNode) textNode.textContent = ' ' + json.data.vote_count;
                        }
                    }

                    // Find the sibling up/down buttons for this target
                    const upBtn   = document.querySelector('.ch-vote-up[data-id="'+targetId+'"]');
                    const downBtn = document.querySelector('.ch-vote-down[data-id="'+targetId+'"]');

                    // Clear both active states first
                    upBtn?.classList.remove('active-up');
                    downBtn?.classList.remove('active-down');

                    // Apply the correct active state based on what the server says
                    if (json.data.action === 'voted' || json.data.action === 'changed') {
                        if (value === 1)  upBtn?.classList.add('active-up');
                        if (value === -1) downBtn?.classList.add('active-down');
                    }
                    // action === 'removed' → both stay cleared (already done above)
                } else {
                    alert(json.data?.message || 'Vote failed');
                }
            })
            .catch(error => {
                console.error('Vote request failed:', error);
                alert('Network error. Please try again.');
            });
        };

        window.chBookmark = function(postId, btn, nonce) {
            const fd = new FormData();
            fd.append('action', 'ch_bookmark_post');
            fd.append('post_id', postId);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const isBookmarked = json.data.bookmarked;
                    btn.classList.toggle('ch-bookmarked', isBookmarked);
                    btn.innerHTML = isBookmarked
                        ? '<svg width="14" height="14" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg> Saved'
                        : '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg> Save';
                }
            });
        };

        window.chToggleFollowCategory = function(categoryId, btn, nonce) {
            const fd = new FormData();
            fd.append('action', 'ch_follow_category');
            fd.append('category_id', categoryId);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (!json.success) {
                    alert(json.data?.message || 'Failed to update follow status');
                    return;
                }

                const following = json.data.following;
                btn.textContent = following ? 'Unfollow' : 'Follow';

                const countEl = document.querySelector('.ch-cat-followers[data-cat-id="' + categoryId + '"]');
                if (countEl) {
                    const current = parseInt(countEl.textContent, 10) || 0;
                    const next = following ? current + 1 : Math.max(0, current - 1);
                    countEl.textContent = next + ' followers';
                }
            });
        };

        // ---- @mention autocomplete ----
        (function() {
            let _mentionDropdown = null;
            let _mentionTextarea = null;
            let _mentionStart    = -1;
            let _mentionActive   = -1;
            let _debounceTimer   = null;

            function createDropdown() {
                if (_mentionDropdown) return;
                _mentionDropdown = document.createElement('div');
                _mentionDropdown.className = 'ch-mention-dropdown';
                _mentionDropdown.style.display = 'none';
                document.body.appendChild(_mentionDropdown);
            }

            function positionDropdown(textarea) {
                // Approximate caret position using a mirror div
                const style = window.getComputedStyle(textarea);
                const mirror = document.createElement('div');
                mirror.style.cssText = [
                    'position:absolute','visibility:hidden','overflow:auto',
                    'white-space:pre-wrap','word-wrap:break-word',
                    `width:${textarea.clientWidth}px`,
                    `font:${style.font}`,
                    `padding:${style.padding}`,
                    `border:${style.border}`,
                    `line-height:${style.lineHeight}`,
                ].join(';');
                const text = textarea.value.substring(0, _mentionStart);
                mirror.textContent = text;
                const cursor = document.createElement('span');
                cursor.textContent = '|';
                mirror.appendChild(cursor);
                document.body.appendChild(mirror);

                const taRect   = textarea.getBoundingClientRect();
                const mirrorRect = mirror.getBoundingClientRect();
                const cursorRect = cursor.getBoundingClientRect();
                document.body.removeChild(mirror);

                const top  = taRect.top  + window.scrollY + (cursorRect.top - mirrorRect.top) + parseInt(style.lineHeight || 20);
                const left = taRect.left + window.scrollX + (cursorRect.left - mirrorRect.left);

                _mentionDropdown.style.top  = Math.min(top,  window.scrollY + window.innerHeight - 200) + 'px';
                _mentionDropdown.style.left = Math.min(left, window.scrollX + window.innerWidth  - 290) + 'px';
            }

            function showResults(users, query) {
                _mentionActive = -1;
                if (!users.length) {
                    _mentionDropdown.innerHTML = `<div class="ch-mention-no-results">No users found for "@${query}"</div>`;
                } else {
                    _mentionDropdown.innerHTML = users.map((u, i) => `
                        <div class="ch-mention-item" data-username="${u.username}" data-index="${i}">
                            <div class="ch-mention-item-avatar">${u.display_name.charAt(0).toUpperCase()}</div>
                            <div>
                                <div class="ch-mention-item-name">${u.display_name}</div>
                                <div class="ch-mention-item-handle">@${u.username}</div>
                            </div>
                        </div>`).join('');
                    _mentionDropdown.querySelectorAll('.ch-mention-item').forEach(item => {
                        item.addEventListener('mousedown', e => {
                            e.preventDefault();
                            insertMention(item.dataset.username);
                        });
                    });
                }
                _mentionDropdown.style.display = 'block';
            }

            function hideDropdown() {
                if (_mentionDropdown) _mentionDropdown.style.display = 'none';
                _mentionStart  = -1;
                _mentionActive = -1;
            }

            function insertMention(username) {
                if (!_mentionTextarea || _mentionStart < 0) return;
                const val    = _mentionTextarea.value;
                const before = val.substring(0, _mentionStart);
                const after  = val.substring(_mentionTextarea.selectionStart);
                _mentionTextarea.value = before + '@' + username + ' ' + after;
                const pos = _mentionStart + username.length + 2;
                _mentionTextarea.setSelectionRange(pos, pos);
                _mentionTextarea.focus();
                hideDropdown();
            }

            function fetchUsers(query) {
                const fd = new FormData();
                fd.append('action', 'ch_mention_search');
                fd.append('query', query);
                fetch(ajaxurl, {method:'POST', body:fd})
                .then(r => r.json())
                .then(json => {
                    if (json.success && _mentionStart >= 0) {
                        showResults(json.data.users, query);
                    }
                })
                .catch(() => hideDropdown());
            }

            function onKeydown(e) {
                if (_mentionDropdown?.style.display !== 'block') return;
                const items = _mentionDropdown.querySelectorAll('.ch-mention-item');
                if (!items.length) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    _mentionActive = Math.min(_mentionActive + 1, items.length - 1);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    _mentionActive = Math.max(_mentionActive - 1, 0);
                } else if (e.key === 'Enter' || e.key === 'Tab') {
                    if (_mentionActive >= 0 && items[_mentionActive]) {
                        e.preventDefault();
                        insertMention(items[_mentionActive].dataset.username);
                        return;
                    }
                } else if (e.key === 'Escape') {
                    hideDropdown();
                    return;
                } else {
                    return;
                }
                items.forEach((item, i) => item.classList.toggle('active', i === _mentionActive));
            }

            function onInput(e) {
                const ta  = e.target;
                const val = ta.value;
                const pos = ta.selectionStart;

                // Find the @ that starts the current mention token
                let start = -1;
                for (let i = pos - 1; i >= 0; i--) {
                    if (val[i] === '@') { start = i; break; }
                    if (/\s/.test(val[i])) break;
                }

                if (start < 0) { hideDropdown(); return; }

                const query = val.substring(start + 1, pos);
                if (query.length === 0) { hideDropdown(); return; }

                _mentionTextarea = ta;
                _mentionStart    = start;
                _mentionDropdown.style.display = 'block';
                positionDropdown(ta);

                clearTimeout(_debounceTimer);
                _debounceTimer = setTimeout(() => fetchUsers(query), 180);
            }

            // Attach to existing and future textareas via delegation
            createDropdown();
            document.addEventListener('input',   e => { if (e.target.matches('textarea')) onInput(e); });
            document.addEventListener('keydown',  e => { if (e.target.matches('textarea')) onKeydown(e); });
            document.addEventListener('click',    e => { if (!e.target.closest('.ch-mention-dropdown') && !e.target.matches('textarea')) hideDropdown(); });
            document.addEventListener('focusout', e => { if (e.target.matches('textarea')) setTimeout(hideDropdown, 150); });
        })();

        window.chReportPost = function(postId, nonce) {
            document.getElementById('ch-report-target-id').value   = postId;
            document.getElementById('ch-report-target-type').value = 'post';
            chOpenModal('ch-modal-report');
        };

        window.chReportComment = function(commentId, nonce) {
            document.getElementById('ch-report-target-id').value   = commentId;
            document.getElementById('ch-report-target-type').value = 'comment';
            chOpenModal('ch-modal-report');
        };

        window.chSubmitReport = function(nonce) {
            const reason  = document.getElementById('ch-report-reason').value;
            const details = document.getElementById('ch-report-details').value;
            const tid     = document.getElementById('ch-report-target-id').value;
            const ttype   = document.getElementById('ch-report-target-type').value;

            if (!reason) { alert('Please select a reason'); return; }

            const fd = new FormData();
            fd.append('action', 'ch_report');
            fd.append('target_type', ttype);
            fd.append('target_id', tid);
            fd.append('reason', reason);
            fd.append('details', details);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-report-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) setTimeout(() => chCloseModal('ch-modal-report'), 1500);
            });
        };

        // ---- Profile & Notifications dropdowns ----
        let _dropdownJustOpened = false;

        function positionDropdown(menu, btn) {
            const rect = btn.getBoundingClientRect();
            menu.style.top   = (rect.bottom + 6) + 'px';
            menu.style.right = (window.innerWidth - rect.right) + 'px';
            menu.style.left  = 'auto';
        }

        // Close dropdowns on outside click
        document.addEventListener('click', function(e) {
            if (_dropdownJustOpened) { _dropdownJustOpened = false; return; }
            if (!e.target.closest('.ch-profile-dropdown')) {
                const m = document.getElementById('ch-profile-menu');
                if (m) m.style.display = 'none';
            }
            if (!e.target.closest('.ch-notifications-dropdown')) {
                const m = document.getElementById('ch-notifications-menu');
                if (m) m.style.display = 'none';
            }
        });

        // ---- Notification count on load ----

        // Load count on page load
        if (document.getElementById('ch-notification-count')) {
            chLoadNotificationCount();
        }

        window.chSubmitPost = function(nonce) {
            const title     = document.getElementById('ch-post-title').value.trim();
            const content   = document.getElementById('ch-post-content').value.trim();
            const catId     = document.getElementById('ch-post-cat').value;
            const tags      = document.getElementById('ch-post-tags').value;
            const anonEl    = document.getElementById('ch-post-anon');
            const isAnon    = anonEl ? (anonEl.checked ? 1 : 0) : 1;
            const guestEl   = document.getElementById('ch-post-guest-name');
            const guestName = guestEl ? guestEl.value.trim() : '';

            if (!title || !content || !catId) { alert('Please fill in all required fields'); return; }

            // Block guest from posting to private categories (client-side hint)
            const catSelect = document.getElementById('ch-post-cat');
            const selOpt = catSelect?.options[catSelect.selectedIndex];
            if (selOpt && selOpt.dataset.private === '1' && !nonce) {
                alert('This is a private category. Please sign in and follow it to post.');
                return;
            }

            const btn = document.getElementById('ch-submit-post-btn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Posting...</span>';

            const fd = new FormData();
            fd.append('action', 'ch_create_post');
            fd.append('title', title);
            fd.append('content', content);
            fd.append('category_id', catId);
            fd.append('tags', tags);
            fd.append('is_anonymous', isAnon);
            if (guestName) fd.append('guest_name', guestName);
            if (nonce) fd.append('nonce', nonce);

            const media = document.getElementById('ch-post-media').files;
            for (let file of media) {
                fd.append('media[]', file);
            }

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-post-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) chReloadAfterSuccess();
                else { btn.disabled = false; btn.textContent = 'Post to Community'; }
            });
        };

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.ch-share-dropdown')) {
                document.querySelectorAll('.ch-share-menu.show').forEach(menu => menu.classList.remove('show'));
            }
        });

        window.chOpenEditPostModal = function(postId) {
            // Load post data via AJAX
            const fd = new FormData();
            fd.append('action', 'ch_get_post_detail');
            fd.append('post_id', postId);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const post = json.data.post;
                    document.getElementById('ch-edit-post-id').value = post.id;
                    document.getElementById('ch-edit-post-cat').value = post.category_id;
                    document.getElementById('ch-edit-post-title').value = post.title;
                    document.getElementById('ch-edit-post-content').value = post.content;
                    document.getElementById('ch-edit-post-tags').value = post.tags;
                    document.getElementById('ch-edit-post-anon').checked = post.is_anonymous;
                    chOpenModal('ch-modal-edit-post');
                } else {
                    alert('Failed to load post data');
                }
            })
            .catch(error => {
                console.error('Failed to load post:', error);
                alert('Network error');
            });
        };

        window.chUpdatePost = function(nonce) {
            const id = document.getElementById('ch-edit-post-id').value;
            const title = document.getElementById('ch-edit-post-title').value.trim();
            const content = document.getElementById('ch-edit-post-content').value.trim();
            const catId = document.getElementById('ch-edit-post-cat').value;
            const tags = document.getElementById('ch-edit-post-tags').value;
            const isAnon = document.getElementById('ch-edit-post-anon').checked ? 1 : 0;

            if (!title || !content || !catId) { alert('Please fill in all required fields'); return; }

            const btn = document.getElementById('ch-update-post-btn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Updating...</span>';

            const fd = new FormData();
            fd.append('action', 'ch_edit_post');
            fd.append('post_id', id);
            fd.append('title', title);
            fd.append('content', content);
            fd.append('category_id', catId);
            fd.append('tags', tags);
            fd.append('is_anonymous', isAnon);
            fd.append('nonce', nonce);

            const media = document.getElementById('ch-edit-post-media').files;
            for (let file of media) {
                fd.append('media[]', file);
            }

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-edit-post-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) chReloadAfterSuccess();
                else { btn.disabled = false; btn.textContent = 'Update Post'; }
            });
        };

        window.chDeletePost = function(id, nonce) {
            if (!confirm('Are you sure you want to delete this post?')) return;

            const fd = new FormData();
            fd.append('action', 'ch_delete_post');
            fd.append('post_id', id);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    alert('Post deleted successfully');
                    window.location.href = '<?php echo get_permalink(get_page_by_path('forum-feed')); ?>';
                } else {
                    alert(json.data?.message || 'Error deleting post');
                }
            });
        };

        // ---- Nav item instant active feedback on click ----
        document.addEventListener('click', function(e) {
            const navItem = e.target.closest('.ch-nav-item');
            if (navItem && !navItem.classList.contains('active')) {
                // Optimistically mark clicked item as active for instant feel
                document.querySelectorAll('.ch-nav-item').forEach(n => n.classList.remove('active'));
                navItem.classList.add('active');
            }
        });

        // Close modals on overlay click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('ch-modal-overlay')) {
                e.target.style.display = 'none';
            }
        });

        window.chUpdateProfile = window.chUpdateProfile || function(nonce) {
            const displayName = (document.getElementById('ch-profile-display-name')?.value || '').trim();
            const bio         = (document.getElementById('ch-profile-bio')?.value || '').trim();
            const location    = (document.getElementById('ch-profile-location')?.value || '').trim();
            const isAnonymous = document.getElementById('ch-profile-anonymous')?.checked ? 1 : 0;
            const btn         = document.querySelector('#ch-profile-form .ch-btn-primary') || event?.target;
            const msgEl       = document.getElementById('ch-profile-msg');

            const fd = new FormData();
            fd.append('action',       'ch_update_profile');
            fd.append('display_name', displayName);
            fd.append('bio',          bio);
            fd.append('location',     location);
            fd.append('is_anonymous', isAnonymous);
            fd.append('nonce',        nonce);

            const avatar = document.getElementById('ch-profile-avatar')?.files[0];
            if (avatar) fd.append('avatar', avatar);

            if (btn) { btn.disabled = true; btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving…</span>'; }

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (msgEl) msgEl.innerHTML = '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) {
                    chReloadAfterSuccess();
                } else {
                    if (btn) { btn.disabled = false; btn.textContent = 'Save Profile'; }
                }
            })
            .catch(() => {
                if (msgEl) msgEl.innerHTML = '<div class="bntm-notice bntm-notice-error">Network error.</div>';
                if (btn) { btn.disabled = false; btn.textContent = 'Save Profile'; }
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

function ch_post_view_scripts() {
    ob_start(); ?>
    <script>
    (function() {

        window.chSubmitComment = function(postId, parentId, nonce) {
            let contentEl;
            if (parentId > 0) {
                contentEl = document.getElementById('ch-reply-content-' + parentId);
            } else {
                contentEl = document.getElementById('ch-comment-content');
            }

            const content = contentEl ? contentEl.value.trim() : '';
            if (!content) { alert('Please enter your comment'); return; }

            const isAnon = document.getElementById('ch-comment-anon')?.checked ? 1 : 0;

            // Find the submit button closest to the textarea
            const btn = contentEl ? contentEl.closest('.ch-comment-form, .ch-reply-form')?.querySelector('.ch-btn-primary') : null;
            const restore = btn ? chBtnLoading(btn, 'Posting...') : () => {};

            const fd = new FormData();
            fd.append('action', 'ch_add_comment');
            fd.append('post_id', postId);
            fd.append('parent_id', parentId);
            fd.append('content', content);
            fd.append('is_anonymous', isAnon);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    chReloadAfterSuccess(0);
                } else {
                    restore();
                    alert(json.data?.message || 'Failed to post comment');
                }
            })
            .catch(() => { restore(); alert('Network error.'); });
        };

        window.chSubmitGuestComment = function(postId, parentId, nonce) {
            const nameEl    = document.getElementById('ch-guest-comment-name');
            const contentEl = document.getElementById('ch-comment-content');
            const content   = contentEl ? contentEl.value.trim() : '';
            const guestName = nameEl ? nameEl.value.trim() : '';
            if (!content) { alert('Please enter your comment'); return; }

            const fd = new FormData();
            fd.append('action',     'ch_add_comment');
            fd.append('post_id',    postId);
            fd.append('parent_id',  parentId);
            fd.append('content',    content);
            fd.append('guest_name', guestName);
            fd.append('nonce',      nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    chReloadAfterSuccess(0);
                } else {
                    alert(json.data?.message || 'Failed to post comment');
                }
            })
            .catch(() => alert('Network error.'));
        };

        window.chDeleteComment = function(commentId, nonce, triggerBtn) {
            if (!confirm('Delete this comment?')) return;
            if (triggerBtn) { triggerBtn.disabled = true; triggerBtn.textContent = 'Deleting...'; }
            const fd = new FormData();
            fd.append('action', 'ch_delete_comment');
            fd.append('comment_id', commentId);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const el = document.getElementById('ch-comment-' + commentId);
                    if (el) {
                        el.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                        el.style.opacity = '0';
                        el.style.transform = 'translateX(-8px)';
                        setTimeout(() => el.remove(), 200);
                    }
                } else {
                    if (triggerBtn) { triggerBtn.disabled = false; triggerBtn.textContent = 'Delete'; }
                    alert(json.data?.message || 'Failed to delete');
                }
            })
            .catch(() => {
                if (triggerBtn) { triggerBtn.disabled = false; triggerBtn.textContent = 'Delete'; }
                alert('Network error.');
            });
        };

        window.chToggleReplyForm = function(commentId) {
            const form = document.getElementById('ch-reply-form-' + commentId);
            if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
        };

        window.chEditComment = function(commentId) {
            const commentEl = document.getElementById('ch-comment-' + commentId);
            if (!commentEl) return;

            const contentEl = commentEl.querySelector('p');
            if (!contentEl) return;

            const originalContent = contentEl.textContent.trim();

            // Replace content with textarea
            contentEl.innerHTML = `
                <textarea class="ch-input ch-textarea" id="ch-edit-comment-${commentId}" rows="3">${originalContent}</textarea>
                <div style="margin-top:8px">
                    <button class="ch-btn ch-btn-primary ch-btn-sm" onclick="chSaveCommentEdit(${commentId}, '${wp_create_nonce('ch_post_view_nonce')}')">Save</button>
                    <button class="ch-btn ch-btn-secondary ch-btn-sm" onclick="chCancelCommentEdit(${commentId}, '${originalContent}')">Cancel</button>
                </div>
            `;
        };

        window.chSaveCommentEdit = function(commentId, nonce) {
            const textarea = document.getElementById('ch-edit-comment-' + commentId);
            if (!textarea) return;

            const newContent = textarea.value.trim();
            if (!newContent) {
                alert('Content cannot be empty');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'ch_edit_comment');
            fd.append('comment_id', commentId);
            fd.append('content', newContent);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const commentEl = document.getElementById('ch-comment-' + commentId);
                    const contentEl = commentEl.querySelector('p');
                    contentEl.innerHTML = newContent.replace(/\n/g, '<br>');
                } else {
                    alert(json.data?.message || 'Failed to update comment');
                }
            });
        };

        window.chCancelCommentEdit = function(commentId, originalContent) {
            const commentEl = document.getElementById('ch-comment-' + commentId);
            const contentEl = commentEl.querySelector('p');
            contentEl.innerHTML = originalContent.replace(/\n/g, '<br>');
        };

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.ch-share-dropdown')) {
                document.querySelectorAll('.ch-share-menu.show').forEach(menu => menu.classList.remove('show'));
            }
        });

        // Open/close modals
        window.chOpenModal  = window.chOpenModal  || function(id){ const el=document.getElementById(id); if(el){el.style.display='flex'; document.body.style.overflow='hidden';} };
        window.chCloseModal = window.chCloseModal || function(id){ const el=document.getElementById(id); if(el){el.style.display='none'; document.body.style.overflow='';} };
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('ch-modal-overlay')) {
                e.target.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function ch_announcements_tab() {
    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>Announcements</h1>
            <p>Create and manage community announcements shown in the forum feed</p>
        </div>
        <button class="ch-btn ch-btn-primary" onclick="chOpenModal('ch-modal-create-announcement')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Announcement
        </button>
    </div>

    <div class="ch-card">
        <div class="bntm-table-wrapper">
            <table class="bntm-table ch-table" id="ch-ann-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ch-ann-tbody">
                    <tr><td colspan="4" class="ch-table-empty">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="ch-modal-create-announcement" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg">
            <div class="ch-modal-header">
                <h3>Create Announcement</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-create-announcement')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <div class="ch-field-group">
                    <label class="ch-label">Title <span class="ch-required">*</span></label>
                    <input type="text" id="ch-ann-create-title" class="ch-input" placeholder="Announcement title" maxlength="255">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Content <span class="ch-required">*</span></label>
                    <textarea id="ch-ann-create-content" class="ch-input ch-textarea ch-textarea-lg" rows="6" placeholder="Write your announcement..."></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-checkbox-label">
                        <input type="checkbox" id="ch-ann-create-status" checked>
                        Publish immediately
                    </label>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-create-announcement')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-ann-create-btn" onclick="chAnnCreate()">Create Announcement</button>
            </div>
            <div id="ch-ann-create-msg"></div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="ch-modal-edit-announcement" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg">
            <div class="ch-modal-header">
                <h3>Edit Announcement</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-edit-announcement')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <input type="hidden" id="ch-ann-edit-id">
                <div class="ch-field-group">
                    <label class="ch-label">Title <span class="ch-required">*</span></label>
                    <input type="text" id="ch-ann-edit-title" class="ch-input" placeholder="Announcement title" maxlength="255">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Content <span class="ch-required">*</span></label>
                    <textarea id="ch-ann-edit-content" class="ch-input ch-textarea ch-textarea-lg" rows="6"></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-checkbox-label">
                        <input type="checkbox" id="ch-ann-edit-status">
                        Published
                    </label>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-edit-announcement')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-ann-edit-btn" onclick="chAnnEdit()">Update Announcement</button>
            </div>
            <div id="ch-ann-edit-msg"></div>
        </div>
    </div>

    <script>
    (function() {
        const nonce = '<?php echo wp_create_nonce('ch_announcements_nonce'); ?>';

        function post(action, data) {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('nonce', nonce);
            Object.entries(data).forEach(([k,v]) => fd.append(k, v));
            return fetch(ajaxurl, {method:'POST', body:fd}).then(r => r.json());
        }

        function showMsg(elId, msg, ok) {
            const el = document.getElementById(elId);
            if (!el) return;
            el.innerHTML = '<div class="bntm-notice bntm-notice-'+(ok?'success':'error')+'">'+msg+'</div>';
            if (ok) setTimeout(() => { el.innerHTML = ''; }, 3000);
        }

        function loadAnnouncements() {
            post('ch_get_announcements', {}).then(json => {
                const tbody = document.getElementById('ch-ann-tbody');
                if (!json.success || !json.data.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="ch-table-empty">No announcements yet.</td></tr>';
                    return;
                }
                tbody.innerHTML = json.data.map(a => {
                    const statusLabel = a.is_active == 1 ? 'Published' : 'Draft';
                    const statusClass = a.is_active == 1 ? 'active' : 'pending';
                    const date = new Date(a.created_at).toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'});
                    return `<tr id="ch-ann-row-${a.id}">
                        <td>
                            <div class="ch-ann-title" style="font-weight:600;font-size:14px;">${a.title}</div>
                            <div class="ch-ann-excerpt" style="font-size:12px;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:340px;">${a.content.replace(/<[^>]+>/g,'').substring(0,80)}…</div>
                        </td>
                        <td><span class="ch-status-badge ch-status-${statusClass}">${statusLabel}</span></td>
                        <td><span class="ch-date">${date}</span></td>
                        <td>
                            <div class="ch-actions-row">
                                <button class="ch-btn-xs ch-btn-secondary" onclick="chAnnOpenEdit(${a.id})">Edit</button>
                                <button class="ch-btn-xs ${a.is_active==1?'ch-btn-warning':'ch-btn-success'}" onclick="chAnnToggle(${a.id},${a.is_active})">
                                    ${a.is_active==1?'Unpublish':'Publish'}
                                </button>
                                <button class="ch-btn-xs ch-btn-danger" onclick="chAnnDelete(${a.id})">Delete</button>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            });
        }

        window.chAnnCreate = function() {
            const title   = document.getElementById('ch-ann-create-title').value.trim();
            const content = document.getElementById('ch-ann-create-content').value.trim();
            const status  = document.getElementById('ch-ann-create-status').checked ? 1 : 0;
            if (!title || !content) { showMsg('ch-ann-create-msg','Title and content are required.',false); return; }
            const btn = document.getElementById('ch-ann-create-btn');
            btn.disabled = true; btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Creating...</span>';
            post('ch_create_announcement', {title, content, status}).then(json => {
                showMsg('ch-ann-create-msg', json.data?.message || (json.success?'Created!':'Failed'), json.success);
                if (json.success) {
                    chCloseModal('ch-modal-create-announcement'); document.getElementById('ch-ann-create-title').value=''; document.getElementById('ch-ann-create-content').value=''; document.getElementById('ch-ann-create-status').checked=true; loadAnnouncements();
                }
                btn.disabled = false; btn.textContent = 'Create Announcement';
            });
        };

        window.chAnnOpenEdit = function(id) {
            post('ch_get_announcement', {announcement_id:id}).then(json => {
                if (!json.success) { alert('Failed to load announcement'); return; }
                const a = json.data;
                document.getElementById('ch-ann-edit-id').value      = a.id;
                document.getElementById('ch-ann-edit-title').value   = a.title;
                document.getElementById('ch-ann-edit-content').value = a.content;
                document.getElementById('ch-ann-edit-status').checked = a.is_active == 1;
                chOpenModal('ch-modal-edit-announcement');
            });
        };

        window.chAnnEdit = function() {
            const id      = document.getElementById('ch-ann-edit-id').value;
            const title   = document.getElementById('ch-ann-edit-title').value.trim();
            const content = document.getElementById('ch-ann-edit-content').value.trim();
            const status  = document.getElementById('ch-ann-edit-status').checked ? 1 : 0;
            if (!title || !content) { showMsg('ch-ann-edit-msg','Title and content are required.',false); return; }
            const btn = document.getElementById('ch-ann-edit-btn');
            btn.disabled = true; btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving...</span>';
            post('ch_edit_announcement', {announcement_id:id, title, content, status}).then(json => {
                showMsg('ch-ann-edit-msg', json.data?.message || (json.success?'Updated!':'Failed'), json.success);
                if (json.success) { chCloseModal('ch-modal-edit-announcement'); loadAnnouncements(); }
                btn.disabled = false; btn.textContent = 'Update Announcement';
            });
        };

        window.chAnnToggle = function(id, currentStatus) {
            const newStatus = currentStatus == 1 ? 0 : 1;
            post('ch_toggle_announcement', {announcement_id:id, status:newStatus}).then(json => {
                if (json.success) loadAnnouncements();
                else alert(json.data?.message || 'Toggle failed');
            });
        };

        window.chAnnDelete = function(id) {
            if (!confirm('Delete this announcement? This cannot be undone.')) return;
            post('ch_delete_announcement', {announcement_id:id}).then(json => {
                if (json.success) {
                    const row = document.getElementById('ch-ann-row-'+id);
                    if (row) { row.style.opacity='0'; row.style.transition='opacity .3s'; setTimeout(()=>row.remove(),300); }
                } else { alert(json.data?.message || 'Delete failed'); }
            });
        };

        loadAnnouncements();
    })();
    </script>
    <?php
    return ob_get_clean();
}

// AJAX handlers for announcements
function bntm_ajax_ch_create_announcement() {
    check_ajax_referer('ch_announcements_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    $title = sanitize_text_field($_POST['title']);
    $content = wp_kses_post($_POST['content']);
    $status = (int)($_POST['status'] ?? 0) === 1 ? 1 : 0;

    if (empty($title) || empty($content)) {
        wp_send_json_error(['message' => 'Title and content are required']);
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ch_announcements';

    $result = $wpdb->insert($table, [
        'admin_id'  => get_current_user_id(),
        'rand_id'   => bntm_rand_id(),
        'title'     => $title,
        'content'   => $content,
        'is_active' => $status,
        'created_at' => current_time('mysql'),
    ]);

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to create announcement']);
        return;
    }

    $announcement_id = $wpdb->insert_id;

    // Create notifications for all users if published
    if ($status == 1) {
        $users = get_users(['fields' => 'ID']);
        foreach ($users as $user_id) {
            ch_create_notification($user_id, 'announcement', get_current_user_id(), $announcement_id);
        }
    }

    wp_send_json_success(['message' => 'Announcement created successfully', 'id' => $announcement_id]);
}

function bntm_ajax_ch_edit_announcement() {
    check_ajax_referer('ch_announcements_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    $announcement_id = intval($_POST['announcement_id']);
    $title = sanitize_text_field($_POST['title']);
    $content = wp_kses_post($_POST['content']);
    $status = (int)($_POST['status'] ?? 0) === 1 ? 1 : 0;

    if (empty($title) || empty($content) || !$announcement_id) {
        wp_send_json_error(['message' => 'Title, content, and announcement ID are required']);
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ch_announcements';

    // Get current status
    $current = $wpdb->get_row($wpdb->prepare("SELECT is_active FROM $table WHERE id = %d", $announcement_id));
    if (!$current) {
        wp_send_json_error(['message' => 'Announcement not found']);
        return;
    }

    $result = $wpdb->update($table, [
        'title'     => $title,
        'content'   => $content,
        'is_active' => $status,
    ], ['id' => $announcement_id]);

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to update announcement']);
        return;
    }

    // Create notifications if newly published
    if ($status == 1 && $current->is_active == 0) {
        $users = get_users(['fields' => 'ID']);
        foreach ($users as $user_id) {
            ch_create_notification($user_id, 'announcement', get_current_user_id(), $announcement_id);
        }
    }

    wp_send_json_success(['message' => 'Announcement updated successfully']);
}

function bntm_ajax_ch_delete_announcement() {
    check_ajax_referer('ch_announcements_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    $announcement_id = intval($_POST['announcement_id']);

    if (!$announcement_id) {
        wp_send_json_error(['message' => 'Announcement ID is required']);
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ch_announcements';

    $result = $wpdb->delete($table, ['id' => $announcement_id]);

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to delete announcement']);
        return;
    }

    // Delete related notifications
    $notifications_table = $wpdb->prefix . 'ch_notifications';
    $wpdb->delete($notifications_table, [
        'type' => 'announcement',
        'target_id' => $announcement_id
    ]);

    wp_send_json_success(['message' => 'Announcement deleted successfully']);
}

function bntm_ajax_ch_toggle_announcement() {
    check_ajax_referer('ch_announcements_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    $announcement_id = intval($_POST['announcement_id']);
    $status = intval($_POST['status']);

    if (!$announcement_id || !in_array($status, [0, 1])) {
        wp_send_json_error(['message' => 'Invalid parameters']);
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ch_announcements';

    $result = $wpdb->update($table, ['is_active' => $status], ['id' => $announcement_id]);

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to update announcement status']);
        return;
    }

    // Create notifications if newly published
    if ($status == 1) {
        $users = get_users(['fields' => 'ID']);
        foreach ($users as $user_id) {
            ch_create_notification($user_id, 'announcement', get_current_user_id(), $announcement_id);
        }
    }

    wp_send_json_success(['message' => 'Announcement status updated successfully']);
}

function bntm_ajax_ch_get_announcements() {
    check_ajax_referer('ch_announcements_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ch_announcements';

    $announcements = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

    wp_send_json_success($announcements);
}

function bntm_ajax_ch_get_announcement() {
    check_ajax_referer('ch_announcements_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    $announcement_id = intval($_POST['announcement_id']);

    if (!$announcement_id) {
        wp_send_json_error(['message' => 'Announcement ID is required']);
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ch_announcements';

    $announcement = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $announcement_id));

    if (!$announcement) {
        wp_send_json_error(['message' => 'Announcement not found']);
        return;
    }

    wp_send_json_success($announcement);
}