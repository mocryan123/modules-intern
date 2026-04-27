<?php
if (!defined('ABSPATH'))
    exit;

// MAIN DASHBOARD SHORTCODE
// ============================================================

function ch_render_avatar($display_name, $avatar_url = '', $wrapper_class = 'ch-avatar-sm', $img_class = '', $attrs = '')
{
    $display_name = $display_name ?: 'U';
    $initial = strtoupper(substr($display_name, 0, 1));
    $avatar_url = trim((string) $avatar_url);
    $img_class_attr = trim('ch-avatar-img ' . $img_class);

    ob_start(); ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>" <?php echo $attrs; ?>>
        <?php if ($avatar_url): ?>
            <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>"
                class="<?php echo esc_attr($img_class_attr); ?>">
        <?php else: ?>
            <span class="ch-avatar-fallback"><?php echo esc_html($initial); ?></span>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function ch_render_avatar_content($display_name, $avatar_url = '', $img_class = 'ch-avatar-img')
{
    $display_name = $display_name ?: 'U';
    $initial = strtoupper(substr($display_name, 0, 1));
    $avatar_url = trim((string) $avatar_url);

    if ($avatar_url) {
        return '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($display_name) . '" class="' . esc_attr($img_class) . '">';
    }

    return '<span class="ch-avatar-fallback">' . esc_html($initial) . '</span>';
}

function ch_render_category_options($categories)
{
    ob_start();
    foreach ($categories as $cat): ?>
        <option value="<?php echo (int) $cat->id; ?>" data-private="<?php echo !empty($cat->is_private) ? '1' : '0'; ?>">
            <?php echo esc_html($cat->name); ?></option>
    <?php endforeach;
    return ob_get_clean();
}

function ch_render_post_composer_modal($args = [])
{
    $defaults = [
        'mode' => 'create',
        'modal_id' => 'ch-modal-create-post',
        'modal_title' => 'Create Post',
        'user_id' => 0,
        'display_name' => 'Guest',
        'avatar_url' => '',
        'categories' => [],
        'submit_nonce' => '',
        'submit_handler' => 'chSubmitPost',
        'submit_label' => 'Post',
        'hint_text' => 'Be respectful and follow community guidelines.',
        'message_id' => 'ch-post-msg',
        'category_id' => 'ch-post-cat',
        'title_id' => 'ch-post-title',
        'content_id' => 'ch-post-content',
        'tags_id' => 'ch-post-tags',
        'anon_id' => 'ch-post-anon',
        'media_input_id' => 'ch-post-media',
        'media_label_id' => 'ch-post-media-label',
        'media_preview_id' => 'ch-post-media-preview',
        'guest_name_id' => 'ch-post-guest-name',
        'show_guest_name' => false,
        'show_anonymous' => true,
        'title_placeholder' => 'Write a title...',
        'content_placeholder' => "What's on your mind?",
        'show_hidden_post_id' => false,
        'post_id_input' => 'ch-edit-post-id',
    ];
    $args = array_merge($defaults, $args);
    $is_edit = $args['mode'] === 'edit';

    ob_start(); ?>
    <div id="<?php echo esc_attr($args['modal_id']); ?>" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg ch-composer-modal">
            <div class="ch-composer-header">
                <h3><?php echo esc_html($args['modal_title']); ?></h3>
                <button type="button" class="ch-composer-close"
                    onclick="chCloseModal('<?php echo esc_js($args['modal_id']); ?>')" aria-label="Close">&times;</button>
            </div>
            <div class="ch-composer-divider"></div>

            <div class="ch-composer-body">
                <?php if ($args['show_hidden_post_id']): ?>
                    <input type="hidden" id="<?php echo esc_attr($args['post_id_input']); ?>">
                <?php endif; ?>

                <div class="ch-composer-author-row">
                    <div class="ch-composer-avatar">
                        <?php
                        if ($args['user_id']) {
                            echo ch_render_avatar_content($args['display_name'], $args['avatar_url']);
                        } else {
                            echo 'G';
                        }
                        ?>
                    </div>
                    <div class="ch-composer-author-info">
                        <div class="ch-composer-author-name"><?php echo esc_html($args['display_name']); ?></div>
                        <div class="ch-composer-meta-row">
                            <select id="<?php echo esc_attr($args['category_id']); ?>" class="ch-composer-cat-select">
                                <option value="">Select category</option>
                                <?php echo ch_render_category_options($args['categories']); ?>
                            </select>
                            <?php if ($args['user_id'] && $args['show_anonymous']): ?>
                                <label class="ch-composer-anon-toggle">
                                    <input type="checkbox" id="<?php echo esc_attr($args['anon_id']); ?>">
                                    <span>Anonymous</span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($args['show_guest_name']): ?>
                    <input type="text" id="<?php echo esc_attr($args['guest_name_id']); ?>" class="ch-composer-guest-name"
                        placeholder="Your name (leave blank to post anonymously)" maxlength="100">
                <?php endif; ?>

                <input type="text" id="<?php echo esc_attr($args['title_id']); ?>" class="ch-composer-title"
                    placeholder="<?php echo esc_attr($args['title_placeholder']); ?>">
                <textarea id="<?php echo esc_attr($args['content_id']); ?>" class="ch-composer-textarea" rows="4"
                    placeholder="<?php echo esc_attr($args['content_placeholder']); ?>"></textarea>

                <div class="ch-composer-tags-row">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z" />
                        <line x1="7" y1="7" x2="7.01" y2="7" />
                    </svg>
                    <input type="text" id="<?php echo esc_attr($args['tags_id']); ?>" class="ch-composer-tags-input"
                        placeholder="Tags: road, drainage, urgent...">
                </div>

                <label class="ch-composer-media-area" id="<?php echo esc_attr($args['media_label_id']); ?>"
                    for="<?php echo esc_attr($args['media_input_id']); ?>">
                    <div class="ch-composer-media-inner">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.8">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <circle cx="8.5" cy="8.5" r="1.5" />
                            <polyline points="21 15 16 10 5 21" />
                        </svg>
                        <span>Add photos / videos</span>
                        <span class="ch-composer-media-sub">or drag and drop</span>
                    </div>
                    <div id="<?php echo esc_attr($args['media_preview_id']); ?>" class="ch-composer-media-preview"></div>
                    <input type="file" id="<?php echo esc_attr($args['media_input_id']); ?>" style="display:none" multiple
                        accept="image/*,video/*,audio/*"
                        onchange="chHandleComposerMediaChange(this, '<?php echo esc_js($args['media_label_id']); ?>', '<?php echo esc_js($args['media_preview_id']); ?>')">
                </label>
            </div>

            <div id="<?php echo esc_attr($args['message_id']); ?>"></div>

            <div class="ch-composer-footer">
                <div class="ch-composer-footer-hint">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <?php echo esc_html($args['hint_text']); ?>
                </div>
                <button type="button" class="ch-btn ch-btn-primary ch-composer-submit"
                    id="<?php echo esc_attr($is_edit ? 'ch-update-post-btn' : 'ch-submit-post-btn'); ?>"
                    onclick="<?php echo esc_attr($args['submit_handler']); ?>('<?php echo esc_js($args['submit_nonce']); ?>')">
                    <?php echo esc_html($args['submit_label']); ?>
                </button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function ch_get_media_urls($media_urls)
{
    if (empty($media_urls))
        return [];
    $decoded = json_decode($media_urls, true);
    return is_array($decoded) ? array_values(array_filter($decoded)) : [];
}

function ch_media_kind_from_url($url)
{
    $path = wp_parse_url($url, PHP_URL_PATH) ?: $url;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true))
        return 'image';
    if (in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'm4v'], true))
        return 'video';
    if (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'], true))
        return 'audio';
    return 'file';
}

function ch_render_post_media_preview($media_urls, $context = 'feed')
{
    $media = ch_get_media_urls($media_urls);
    if (empty($media))
        return '';

    $images = [];
    $other_media = [];

    foreach ($media as $url) {
        $kind = ch_media_kind_from_url($url);
        if ($kind === 'image') {
            $images[] = $url;
        } else {
            $other_media[] = [
                'url' => $url,
                'kind' => $kind,
            ];
        }
    }

    ob_start(); ?>
    <div class="ch-post-media ch-post-media-<?php echo esc_attr($context); ?>">
        <?php if (!empty($images)):
            $image_count = count($images);
            $visible_count = min($image_count, 4);
            $gallery_class = 'ch-post-media-gallery-' . ($image_count >= 4 ? '4' : $image_count);
            ?>
            <div class="ch-post-media-preview ch-post-media-preview-<?php echo esc_attr($context); ?> ch-post-media-gallery <?php echo esc_attr($gallery_class); ?><?php echo $image_count > 4 ? ' ch-post-media-gallery-more' : ''; ?>"
                data-ch-gallery-items="<?php echo esc_attr(wp_json_encode(array_values($images))); ?>">
                <?php foreach ($images as $index => $url):
                    if ($index >= $visible_count) {
                        break;
                    }
                    $remaining = $image_count - 4;
                    ?>
                    <button type="button" class="ch-post-media-thumb ch-post-media-thumb-button"
                        data-ch-gallery-index="<?php echo (int) $index; ?>"
                        aria-label="View image <?php echo (int) ($index + 1); ?> of <?php echo (int) $image_count; ?>">
                        <img src="<?php echo esc_url($url); ?>" alt="Post image <?php echo (int) ($index + 1); ?>"
                            class="ch-post-media-thumb-img">
                        <?php if ($image_count > 4 && $index === 3): ?>
                            <span class="ch-post-media-more">+<?php echo (int) $remaining; ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($other_media)): ?>
            <div
                class="ch-post-media-preview ch-post-media-preview-<?php echo esc_attr($context); ?> ch-post-media-preview-mixed">
                <?php foreach ($other_media as $item): ?>
                    <?php if ($item['kind'] === 'video'): ?>
                        <div class="ch-post-media-thumb">
                            <video controls preload="metadata" class="ch-post-media-thumb-video">
                                <source src="<?php echo esc_url($item['url']); ?>">
                            </video>
                        </div>
                    <?php elseif ($item['kind'] === 'audio'): ?>
                        <div class="ch-post-media-audio-wrap">
                            <audio controls class="ch-post-media-audio-inline">
                                <source src="<?php echo esc_url($item['url']); ?>">
                            </audio>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function ch_guest_landing_page()
{
    global $wpdb;

    $feed_url = ch_get_feed_url();
    $login_url = ch_get_auth_url('login');
    $reg_url = ch_get_auth_url('register');

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
    <script>
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var chFeedUrl = '<?php echo esc_js(remove_query_arg('view_post')); ?>';
    </script>
    <nav class="ch-top-nav">
                <div class="ch-top-nav-logo">
        <button class="ch-burger-menu-btn" type="button" aria-label="Toggle menu" aria-expanded="false"
            onclick="chToggleMobileMenu(this, '#ch-feed-drawer');">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path d="M3 12h18M3 6h18M3 18h18" />
            </svg>
        </button>

            <img src="<?php echo esc_url(bntm_ch_logo_url()); ?>" alt="CivicHub Logo" class="ch-brand-logo">
        </div>

        <!-- Mobile drawer -->
        <div id="ch-feed-drawer" class="ch-mobile-drawer-wrap">
            <button class="ch-top-drawer-close" type="button" onclick="chCloseAllMobileMenus()"
                aria-label="Close menu">&times;</button>
            <div class="ch-nav-links">
                <?php if ($user_id): ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'my_feed', $feed_url)); ?>"
                        class="ch-nav-link <?php echo ($tab === 'my_feed') ? 'active' : ''; ?>" data-ch-feed-nav="my_feed"
                        onclick="return window.chHandleFeedNavClick ? window.chHandleFeedNavClick(this, event) : true;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span class="ch-nav-label">My Feed</span>
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url($feed_url); ?>"
                    class="ch-nav-link <?php echo !$bookmarks && $sort === 'new' && $tab === '' && !$cat_slug && !$search ? 'active' : ''; ?>"
                    data-ch-feed-nav="home"
                    onclick="return window.chHandleFeedNavClick ? window.chHandleFeedNavClick(this, event) : true;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="ch-nav-label">Home</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('sort', 'trending', $feed_url)); ?>"
                    class="ch-nav-link <?php echo $sort === 'trending' && $tab === '' && !$bookmarks ? 'active' : ''; ?>"
                    data-ch-feed-nav="trending"
                    onclick="return window.chHandleFeedNavClick ? window.chHandleFeedNavClick(this, event) : true;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                    </svg>
                    <span class="ch-nav-label">Trending</span>
                </a>
                <?php if ($user_id): ?>
                    <a href="<?php echo esc_url(add_query_arg('bookmarks', '1', $feed_url)); ?>"
                        class="ch-nav-link <?php echo $bookmarks && $tab === '' ? 'active' : ''; ?>"
                        data-ch-feed-nav="bookmarks"
                        onclick="return window.chHandleFeedNavClick ? window.chHandleFeedNavClick(this, event) : true;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z" />
                        </svg>
                        <span class="ch-nav-label">Bookmarks</span>
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!$user_id): ?>
                <!-- Guest auth buttons inside drawer (mobile only) -->
                <div class="ch-user-bar">
                    <a href="<?php echo esc_url(ch_get_auth_url('login')); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">Sign
                        In</a>
                    <a href="<?php echo esc_url(ch_get_auth_url('register')); ?>"
                        class="ch-btn ch-btn-primary ch-btn-sm">Join</a>
                </div>
            <?php endif; ?>
        </div><!-- /#ch-feed-drawer -->

        <?php if ($user_id): ?>
            <?php $current_display = wp_get_current_user()->display_name ?: 'U'; ?>
            <!-- ── Desktop logged-in user bar — pinned to the right of the nav ── -->
            <div class="ch-user-bar ch-nav-user-desktop">
                <div class="ch-notifications-dropdown ch-top-nav-notifications">
                    <button class="ch-icon-action-btn ch-notifications-btn" id="ch-notif-btn" onclick="chToggleNotifications(event)"
                        aria-label="Notifications">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
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
                    <button class="ch-avatar-btn" id="ch-profile-btn" onclick="chToggleProfileMenu(event)"
                        aria-label="Profile" data-ch-current-user-avatar="1"
                        data-avatar-name="<?php echo esc_attr($current_display); ?>">
                        <?php echo ch_render_avatar($current_display, $current_profile->avatar_url ?? '', 'ch-avatar-btn-inner', 'ch-current-user-avatar-img'); ?>
                    </button>
                    <div class="ch-dropdown-panel ch-dropdown-panel-sm" id="ch-profile-menu" style="display:none;">
                        <div class="ch-dropdown-user-info">
                            <div class="ch-avatar-btn ch-avatar-btn-lg" data-ch-current-user-avatar="1"
                                data-avatar-name="<?php echo esc_attr($current_display); ?>">
                                <?php echo ch_render_avatar($current_display, $current_profile->avatar_url ?? '', 'ch-avatar-btn-inner ch-avatar-btn-inner-lg', 'ch-current-user-avatar-img'); ?>
                            </div>
                            <div>
                                <div class="ch-dropdown-username"><?php echo esc_html($current_display); ?></div>
                                <div class="ch-dropdown-usermeta">Community Member</div>
                            </div>
                        </div>
                        <div class="ch-dropdown-divider"></div>
                        <a href="javascript:void(0)" class="ch-dropdown-item"
                            onclick="chOpenSettingsModal(); chCloseProfileMenu();">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <circle cx="12" cy="12" r="3" />
                                <path
                                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                            </svg>
                            Settings
                        </a>
                        <div class="ch-dropdown-divider"></div>
                        <a href="<?php echo esc_url(ch_get_logout_url(get_permalink())); ?>"
                            class="ch-dropdown-item ch-dropdown-item-danger">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                            Sign Out
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- ── Desktop-only guest auth buttons ── -->
            <div class="ch-user-bar ch-nav-guest-desktop">
                <a href="<?php echo esc_url(ch_get_auth_url('login')); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">Sign In</a>
                <a href="<?php echo esc_url(ch_get_auth_url('register')); ?>" class="ch-btn ch-btn-primary ch-btn-sm">Join</a>
            </div>
        <?php endif; ?>
    </nav>

    <div class="ch-guest-landing-wrap">
        <aside class="ch-guest-sidebar">
            <!-- Mobile Drawer Close -->
            <button class="ch-drawer-close" onclick="document.body.classList.remove('ch-drawer-open')"
                aria-label="Close Menu"
                style="display:none;position:absolute;top:16px;right:16px;background:none;border:none;font-size:24px;cursor:pointer;color:var(--ch-text-muted);">&times;</button>

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
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                                    style="opacity:.5;flex-shrink:0" title="Private">
                                    <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            <?php endif; ?>
                            <span class="ch-cat-count"><?php echo (int) $cat->post_count; ?></span>
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
                                        <a href="<?php echo esc_url($feed_url . '?view_post=' . rawurlencode($post->rand_id)); ?>"
                                            style="text-decoration:none; color:inherit;">
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
                                <?php echo ch_render_avatar($name, $m->avatar_url ?? '', 'ch-avatar-sm'); ?>
                                <div style="display:flex; flex-direction:column; min-width:0;">
                                    <div class="ch-list-title"><?php echo esc_html($name); ?></div>
                                    <?php if (!empty($m->location)): ?>
                                        <div class="ch-list-meta"><?php echo esc_html($m->location); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ch-list-item-stats">
                                <span class="ch-mini-stat">+<?php echo (int) $m->karma_points; ?></span>
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
                <a href="<?php echo esc_url($login_url); ?>" class="ch-btn ch-btn-secondary ch-btn-full"
                    style="margin-bottom:10px;">
                    Sign In
                </a>
                <a href="<?php echo esc_url($reg_url); ?>" class="ch-btn ch-btn-primary ch-btn-full">
                    Join
                </a>
            </div>
        </aside>
    </div>

    <?php /* Guaranteed inline fallback for themes without wp_head/wp_footer */ ?>
    <?php
    ch_output_global_styles_fallback();
    echo ch_global_scripts();
    $content = ob_get_clean();
    return bntm_universal_container('CivicHub', $content);
}

function bntm_shortcode_ch()
{
    if (!is_user_logged_in()) {
        return ch_guest_landing_page();
    }

    // Handle settings save
    if (isset($_POST['ch_save_settings']) && current_user_can('manage_options')) {
        if (!wp_verify_nonce($_POST['ch_settings_nonce'], 'ch_settings_nonce')) {
            wp_die('Security check failed');
        }
        $threshold = max(1, min(50, (int) ($_POST['ch_report_auto_hide_threshold'] ?? 5)));
        update_option('ch_report_auto_hide_threshold', $threshold);
        $post_approval = isset($_POST['ch_post_approval_enabled']) ? 1 : 0;
        update_option('ch_post_approval_enabled', $post_approval);
        echo '<div class="bntm-notice bntm-notice-success">Settings saved successfully!</div>';
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

    // Ensure user profile exists
    ch_ensure_profile($user_id);

    $is_admin = current_user_can('manage_options') || current_user_can('administrator');

    ob_start();
    ?>
    <script>
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        window.chAdminTabNonce = '<?php echo wp_create_nonce('ch_admin_tab_nonce'); ?>';
    </script>
    <div class="ch-dashboard-wrap">
        <div class="ch-sidebar">
            <div class="ch-sidebar-header" style="display:flex; justify-content:space-between; align-items:center;">
                <button class="ch-burger-menu-btn" type="button" aria-label="Toggle menu" aria-expanded="false"
                    onclick="chToggleMobileMenu(this, '.ch-nav');" style="margin-right: 12px;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M3 12h18M3 6h18M3 18h18" />
                    </svg>
                </button>
                <img src="<?php echo esc_url(bntm_ch_logo_url()); ?>" alt="CivicHub Logo" class="ch-brand-logo">
                <span>CivicHub</span>
            </div>
            <nav class="ch-nav" id="ch-admin-nav">
                <button type="button" class="ch-nav-item <?php echo $active_tab === 'overview' ? 'active' : ''; ?>"
                    data-tab="overview">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="14" y="14" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                    </svg>
                    Overview
                </button>
                <button type="button" class="ch-nav-item <?php echo $active_tab === 'categories' ? 'active' : ''; ?>"
                    data-tab="categories">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                    Categories
                </button>
                <button type="button" class="ch-nav-item <?php echo $active_tab === 'posts' ? 'active' : ''; ?>"
                    data-tab="posts">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                    </svg>
                    Posts
                </button>
                <button type="button" class="ch-nav-item <?php echo $active_tab === 'users' ? 'active' : ''; ?>"
                    data-tab="users">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    Users
                </button>
                <button type="button" class="ch-nav-item <?php echo $active_tab === 'reports' ? 'active' : ''; ?>"
                    data-tab="reports">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                        <line x1="12" y1="9" x2="12" y2="13" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                    Reports
                </button>
                <?php if ($is_admin): ?>
                    <button type="button" class="ch-nav-item <?php echo $active_tab === 'moderation' ? 'active' : ''; ?>"
                        data-tab="moderation">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                        Moderation
                    </button>
                    <button type="button" class="ch-nav-item <?php echo $active_tab === 'activity' ? 'active' : ''; ?>"
                        data-tab="activity">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                        </svg>
                        Activity Log
                    </button>
                    <button type="button" class="ch-nav-item <?php echo $active_tab === 'announcements' ? 'active' : ''; ?>"
                        data-tab="announcements">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 17H2a3 3 0 0 0 3-3V9a7 7 0 0 1 14 0v5a3 3 0 0 0 3 3zm-8.27 4a2 2 0 0 1-3.46 0" />
                        </svg>
                        Announcements
                    </button>
                <?php endif; ?>
            </nav>
        </div>

        <div class="ch-main-content" id="ch-admin-content">
            <?php
            switch ($active_tab) {
                case 'overview':
                    echo ch_admin_overview_tab($user_id, $is_admin);
                    break;
                case 'categories':
                    echo ch_categories_tab($user_id, $is_admin);
                    break;
                case 'posts':
                    echo ch_posts_tab($user_id, $is_admin);
                    break;
                case 'users':
                    echo ch_users_tab($user_id, $is_admin);
                    break;
                case 'reports':
                    echo ch_reports_tab($user_id, $is_admin);
                    break;
                case 'moderation':
                    echo $is_admin ? ch_moderation_tab($user_id) : '';
                    break;
                case 'activity':
                    echo $is_admin ? ch_activity_tab($user_id) : '';
                    break;
                case 'announcements':
                    echo $is_admin ? ch_announcements_tab() : '';
                    break;
                default:
                    echo ch_admin_overview_tab($user_id, $is_admin);
                    break;
            }
            ?>
        </div>
    </div>

    <script>
        // ── Admin tab AJAX loader ──────────────────────────────
        (function () {
            var nav = document.getElementById('ch-admin-nav');
            var content = document.getElementById('ch-admin-content');
            if (!nav || !content) return;

            // Reinitialize all tab-specific event listeners after AJAX content loads
            window.chInitTabListeners = function () {
                // ── Posts filter AJAX loader ──────────────────────────────
                var postsFilters = document.getElementById('ch-posts-filter');
                var postsContent = document.getElementById('ch-posts-content');
                if (postsFilters && postsContent) {
                    var newPostsFilters = postsFilters.cloneNode(true);
                    postsFilters.parentNode.replaceChild(newPostsFilters, postsFilters);
                    postsFilters = newPostsFilters;

                    postsFilters.addEventListener('click', function (e) {
                        var btn = e.target.closest('[data-filter]');
                        if (!btn || btn.classList.contains('active')) return;

                        var filter = btn.dataset.filter;

                        postsFilters.querySelectorAll('.ch-filter-btn').forEach(function (t) {
                            t.classList.toggle('active', t === btn);
                        });

                        postsContent.style.opacity = '0.45';
                        postsContent.style.pointerEvents = 'none';

                        var fd = new FormData();
                        fd.append('action', 'ch_admin_tab');
                        fd.append('nonce', window.chAdminTabNonce || '');
                        fd.append('tab', 'posts');
                        fd.append('mode', 'content');
                        fd.append('filter', filter);

                        fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                if (json.success) {
                                    postsContent.innerHTML = json.data.html;
                                    if (window.chRunEmbeddedScripts) window.chRunEmbeddedScripts(postsContent);

                                    var url = new URL(window.location.href);
                                    url.searchParams.set('filter', filter);
                                    history.replaceState(null, '', url.toString());
                                }
                            })
                            .catch(function () { })
                            .finally(function () {
                                postsContent.style.opacity = '';
                                postsContent.style.pointerEvents = '';
                            });
                    });
                }

                // ── Posts search AJAX loader ──────────────────────────────
                var postsSearchBtn = document.getElementById('ch-posts-search-btn');
                postsContent = document.getElementById('ch-posts-content');
                if (postsSearchBtn && postsContent) {
                    var newPostsSearchBtn = postsSearchBtn.cloneNode(true);
                    postsSearchBtn.parentNode.replaceChild(newPostsSearchBtn, postsSearchBtn);
                    postsSearchBtn = newPostsSearchBtn;

                    function chPostsSearch() {
                        var search = document.getElementById('ch-posts-search-input').value.trim();
                        var cat = document.getElementById('ch-posts-search-cat').value;

                        postsContent.style.opacity = '0.45';
                        postsContent.style.pointerEvents = 'none';

                        var fd = new FormData();
                        fd.append('action', 'ch_admin_tab');
                        fd.append('nonce', window.chAdminTabNonce || '');
                        fd.append('tab', 'posts');
                        fd.append('mode', 'content');
                        fd.append('s', search);
                        fd.append('cat', cat);

                        fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                if (json.success) {
                                    postsContent.innerHTML = json.data.html;
                                    if (window.chRunEmbeddedScripts) window.chRunEmbeddedScripts(postsContent);
                                    var url = new URL(window.location.href);
                                    url.searchParams.set('s', search);
                                    url.searchParams.set('cat', cat);
                                    history.replaceState(null, '', url.toString());
                                }
                            })
                            .catch(function () { })
                            .finally(function () {
                                postsContent.style.opacity = '';
                                postsContent.style.pointerEvents = '';
                            });
                    }

                    postsSearchBtn.addEventListener('click', chPostsSearch);
                    var postsSearchInput = document.getElementById('ch-posts-search-input');
                    if (postsSearchInput) {
                        postsSearchInput.addEventListener('keypress', function (e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                chPostsSearch();
                            }
                        });
                    }
                }

                // ── Users status filter AJAX loader ──────────────────────────────
                var usersFilters = document.getElementById('ch-users-status-filters');
                var usersContent = document.getElementById('ch-users-content');
                if (usersFilters && usersContent) {
                    var newUsersFilters = usersFilters.cloneNode(true);
                    usersFilters.parentNode.replaceChild(newUsersFilters, usersFilters);
                    usersFilters = newUsersFilters;

                    usersFilters.addEventListener('click', function (e) {
                        var btn = e.target.closest('[data-status]');
                        if (!btn || btn.classList.contains('active')) return;

                        var status = btn.dataset.status;

                        usersFilters.querySelectorAll('.ch-filter-btn').forEach(function (t) {
                            t.classList.toggle('active', t === btn);
                        });

                        usersContent.style.opacity = '0.45';
                        usersContent.style.pointerEvents = 'none';

                        var fd = new FormData();
                        fd.append('action', 'ch_admin_tab');
                        fd.append('nonce', window.chAdminTabNonce || '');
                        fd.append('tab', 'users');
                        fd.append('mode', 'content');
                        fd.append('status', status);

                        fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                if (json.success) {
                                    usersContent.innerHTML = json.data.html;
                                    if (window.chRunEmbeddedScripts) window.chRunEmbeddedScripts(usersContent);

                                    var url = new URL(window.location.href);
                                    url.searchParams.set('status', status);
                                    history.replaceState(null, '', url.toString());
                                }
                            })
                            .catch(function () { })
                            .finally(function () {
                                usersContent.style.opacity = '';
                                usersContent.style.pointerEvents = '';
                            });
                    });
                }

                // ── Users search AJAX loader ──────────────────────────────
                var usersSearchBtn = document.getElementById('ch-users-search-btn');
                if (usersSearchBtn && usersContent) {
                    var newUsersSearchBtn = usersSearchBtn.cloneNode(true);
                    usersSearchBtn.parentNode.replaceChild(newUsersSearchBtn, usersSearchBtn);
                    usersSearchBtn = newUsersSearchBtn;

                    function chUsersSearch() {
                        var search = document.getElementById('ch-users-search-input').value.trim();

                        usersContent.style.opacity = '0.45';
                        usersContent.style.pointerEvents = 'none';

                        var fd = new FormData();
                        fd.append('action', 'ch_admin_tab');
                        fd.append('nonce', window.chAdminTabNonce || '');
                        fd.append('tab', 'users');
                        fd.append('mode', 'content');
                        fd.append('s', search);

                        fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                if (json.success) {
                                    usersContent.innerHTML = json.data.html;
                                    if (window.chRunEmbeddedScripts) window.chRunEmbeddedScripts(usersContent);
                                    var url = new URL(window.location.href);
                                    url.searchParams.set('s', search);
                                    history.replaceState(null, '', url.toString());
                                }
                            })
                            .catch(function () { })
                            .finally(function () {
                                usersContent.style.opacity = '';
                                usersContent.style.pointerEvents = '';
                            });
                    }

                    usersSearchBtn.addEventListener('click', chUsersSearch);
                    var usersSearchInput = document.getElementById('ch-users-search-input');
                    if (usersSearchInput) {
                        usersSearchInput.addEventListener('keypress', function (e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                chUsersSearch();
                            }
                        });
                    }
                }

                // ── Reports filter AJAX loader ──────────────────────────────
                var reportsFilters = document.getElementById('ch-reports-status-filters');
                var reportsContent = document.getElementById('ch-reports-content');
                if (reportsFilters && reportsContent) {
                    var newReportsFilters = reportsFilters.cloneNode(true);
                    reportsFilters.parentNode.replaceChild(newReportsFilters, reportsFilters);
                    reportsFilters = newReportsFilters;

                    reportsFilters.addEventListener('click', function (e) {
                        var btn = e.target.closest('[data-rstatus]');
                        if (!btn || btn.classList.contains('active')) return;

                        var rstatus = btn.dataset.rstatus;

                        reportsFilters.querySelectorAll('.ch-filter-btn').forEach(function (t) {
                            t.classList.toggle('active', t === btn);
                        });

                        reportsContent.style.opacity = '0.45';
                        reportsContent.style.pointerEvents = 'none';

                        var fd = new FormData();
                        fd.append('action', 'ch_admin_tab');
                        fd.append('nonce', window.chAdminTabNonce || '');
                        fd.append('tab', 'reports');
                        fd.append('rstatus', rstatus);

                        fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                if (json.success) {
                                    reportsContent.innerHTML = json.data.html;
                                    if (window.chRunEmbeddedScripts) window.chRunEmbeddedScripts(reportsContent);

                                    var url = new URL(window.location.href);
                                    url.searchParams.set('rstatus', rstatus);
                                    history.replaceState(null, '', url.toString());
                                }
                            })
                            .catch(function () { })
                            .finally(function () {
                                reportsContent.style.opacity = '';
                                reportsContent.style.pointerEvents = '';
                            });
                    });
                }
            };

            nav.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-tab]');
                if (!btn || btn.classList.contains('active')) return;

                var tab = btn.dataset.tab;

                nav.querySelectorAll('.ch-nav-item').forEach(function (t) {
                    t.classList.toggle('active', t === btn);
                });

                content.style.opacity = '0.45';
                content.style.pointerEvents = 'none';

                var fd = new FormData();
                fd.append('action', 'ch_admin_tab');
                fd.append('nonce', window.chAdminTabNonce || '');
                fd.append('tab', tab);

                fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.success) {
                            content.innerHTML = json.data.html;
                            if (window.chRunEmbeddedScripts) window.chRunEmbeddedScripts(content);

                            var url = new URL(window.location.href);
                            url.searchParams.set('tab', tab);
                            history.replaceState(null, '', url.toString());

                            if (window.chInitTabListeners) {
                                window.chInitTabListeners();
                            }
                        }
                    })
                    .catch(function () { })
                    .finally(function () {
                        content.style.opacity = '';
                        content.style.pointerEvents = '';
                    });
            });

            if (window.chInitTabListeners) {
                window.chInitTabListeners();
            }
        })();
    </script>

    <!-- Moderation Modal (persistent across AJAX tab switches) -->
    <div id="ch-moderation-modal" class="ch-modal-overlay" style="display:none;">
        <div class="ch-modal" style="max-width:480px;">
            <div class="ch-modal-header">
                <h3 id="ch-moderation-modal-title">Moderate User</h3>
                <button onclick="chCloseModerationModal()"
                    style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--ch-text-subtle);">&times;</button>
            </div>
            <div class="ch-modal-body">
                <p id="ch-moderation-user-info"
                    style="font-size:14px;font-weight:600;margin:0 0 12px;color:var(--ch-text);"></p>
                <div class="ch-field-group">
                    <label class="ch-label">Reason <span class="ch-required">*</span></label>
                    <textarea id="ch-moderation-reason" class="ch-input ch-textarea" rows="3"
                        placeholder="Provide a reason for this action..." style="min-height:80px;"></textarea>
                </div>
                <div id="ch-moderation-options" style="display:none;margin-top:12px;">
                    <div class="ch-field-group">
                        <label class="ch-label">Duration</label>
                        <select id="ch-moderation-duration" class="ch-input">
                            <option value="1">1 day</option>
                            <option value="3">3 days</option>
                            <option value="7" selected>7 days</option>
                            <option value="14">14 days</option>
                            <option value="30">30 days</option>
                        </select>
                    </div>
                </div>
                <div id="ch-moderation-ban-options" style="display:none;margin-top:12px;">
                    <div class="ch-field-group">
                        <label class="ch-label">Ban Type</label>
                        <select id="ch-moderation-ban-type" class="ch-input">
                            <option value="permanent">Permanent Ban</option>
                            <option value="temporary">Temporary Ban</option>
                        </select>
                    </div>
                    <div id="ch-moderation-ban-duration" style="display:none;margin-top:10px;">
                        <label class="ch-label">Duration</label>
                        <select id="ch-moderation-ban-days" class="ch-input">
                            <option value="7">7 days</option>
                            <option value="14">14 days</option>
                            <option value="30" selected>30 days</option>
                            <option value="90">90 days</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button onclick="chCloseModerationModal()" class="ch-btn ch-btn-secondary">Cancel</button>
                <button onclick="chSubmitModeration()" id="ch-moderation-submit-btn"
                    class="ch-btn ch-btn-primary">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // ── Moderation modal functions (global, persist across AJAX) ──
        (function () {
            var chModerationState = { uid: null, action: null, nonce: null, username: '' };

            window.chModerateUser = function (uid, action, nonce, username) {
                chModerationState = { uid: uid, action: action, nonce: nonce, username: username || 'User' };

                var modal = document.getElementById('ch-moderation-modal');
                var title = document.getElementById('ch-moderation-modal-title');
                var userInfo = document.getElementById('ch-moderation-user-info');
                var reason = document.getElementById('ch-moderation-reason');
                var options = document.getElementById('ch-moderation-options');
                var banOptions = document.getElementById('ch-moderation-ban-options');

                userInfo.textContent = chModerationState.username;
                reason.value = '';

                if (action === 'suspend_user') {
                    title.textContent = 'Suspend User';
                    options.style.display = 'block';
                    banOptions.style.display = 'none';
                } else if (action === 'ban_user') {
                    title.textContent = 'Ban User';
                    options.style.display = 'none';
                    banOptions.style.display = 'block';
                } else {
                    title.textContent = 'Restore User';
                    options.style.display = 'none';
                    banOptions.style.display = 'none';
                }

                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            };

            window.chCloseModerationModal = function () {
                var modal = document.getElementById('ch-moderation-modal');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            };

            // Ban type change handler
            var banTypeEl = document.getElementById('ch-moderation-ban-type');
            if (banTypeEl) {
                banTypeEl.addEventListener('change', function () {
                    document.getElementById('ch-moderation-ban-duration').style.display = this.value === 'temporary' ? 'block' : 'none';
                });
            }

            window.chSubmitModeration = function () {
                var reason = document.getElementById('ch-moderation-reason').value.trim();
                if (!reason) {
                    alert('A reason is required for this action.');
                    return;
                }

                var finalReason = reason;
                if (chModerationState.action === 'suspend_user') {
                    var days = document.getElementById('ch-moderation-duration').value;
                    finalReason += ' (Suspended for ' + days + ' day' + (days > 1 ? 's' : '') + ')';
                } else if (chModerationState.action === 'ban_user') {
                    var banType = document.getElementById('ch-moderation-ban-type').value;
                    if (banType === 'temporary') {
                        var days = document.getElementById('ch-moderation-ban-days').value;
                        finalReason += ' (Temporary ban for ' + days + ' days)';
                    } else {
                        finalReason += ' (Permanent ban)';
                    }
                }

                var fd = new FormData();
                fd.append('action', 'ch_moderate_action');
                fd.append('mod_action', chModerationState.action);
                fd.append('target_id', chModerationState.uid);
                fd.append('reason', finalReason);
                fd.append('nonce', chModerationState.nonce);

                var btn = document.getElementById('ch-moderation-submit-btn');
                btn.disabled = true;
                btn.textContent = 'Processing...';

                fetch(ajaxurl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.success) {
                            chCloseModerationModal();
                            // If users tab/content exists, reload it via AJAX instead of full page reload
                            var usersContent = document.getElementById('ch-users-content');
                            if (usersContent) {
                                var activeStatusBtn = document.querySelector('#ch-users-status-filters .ch-filter-btn.active');
                                var status = activeStatusBtn ? activeStatusBtn.dataset.status : 'all';
                                var fd2 = new FormData();
                                fd2.append('action', 'ch_admin_tab');
                                fd2.append('nonce', window.chAdminTabNonce || '');
                                fd2.append('tab', 'users');
                                fd2.append('status', status);
                                fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd2 })
                                    .then(function (r2) { return r2.json(); })
                                    .then(function (json2) {
                                        if (json2.success) {
                                            usersContent.innerHTML = json2.data.html;
                                        }
                                    })
                                    .catch(function () { });
                            } else {
                                // Fallback to reload if users tab not present
                                if (typeof chReloadAfterSuccess === 'function') chReloadAfterSuccess(0);
                            }
                        } else {
                            alert(json.data?.message || 'Action failed.');
                        }
                    })
                    .catch(function () { alert('Network error.'); })
                    .finally(function () {
                        btn.disabled = false;
                        btn.textContent = 'Confirm';
                    });
            };

            // Close on overlay click
            var modOverlay = document.getElementById('ch-moderation-modal');
            if (modOverlay) {
                modOverlay.addEventListener('click', function (e) {
                    if (e.target === this) chCloseModerationModal();
                });
            }
        })();
    </script>

    <?php
    ch_output_global_styles_fallback();
    echo ch_global_scripts();

    $content = ob_get_clean();
    return bntm_universal_container('CivicHub Admin', $content);
}

// ============================================================
// TAB: OVERVIEW
// ============================================================

function ch_admin_overview_tab($user_id, $is_admin)
{
    global $wpdb;

    $counts = ch_get_overview_counts();

    $total_posts = (int) ($counts['total_posts'] ?? 0);
    $total_comments = (int) ($counts['total_comments'] ?? 0);
    $total_users = (int) ($counts['total_users'] ?? 0);
    $total_cats = (int) ($counts['total_cats'] ?? 0);
    $pending_reports = (int) ($counts['pending_reports'] ?? 0);
    $pending_posts = (int) ($counts['pending_posts'] ?? 0);

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
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
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
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
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
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
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
                    <path d="M4 6h16M4 12h8m-8 6h16" />
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num"><?php echo number_format($total_cats); ?></span>
                <span class="ch-stat-label">Categories</span>
            </div>
        </div>
        <div class="ch-stat-card ch-stat-alert" id="pending-posts-card"
            style="<?php echo $pending_posts > 0 ? '' : 'display:none'; ?>">
            <div class="ch-stat-icon" style="background: linear-gradient(135deg,#f97316,#ea580c)">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num" id="stat-pending-posts"><?php echo number_format($pending_posts); ?></span>
                <span class="ch-stat-label">Pending Posts</span>
            </div>
        </div>
        <div class="ch-stat-card ch-stat-alert" id="pending-reports-card"
            style="<?php echo $pending_reports > 0 ? '' : 'display:none'; ?>">
            <div class="ch-stat-icon" style="background: linear-gradient(135deg,#ef4444,#dc2626)">
                <svg width="22" height="22" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                    <line x1="12" y1="9" x2="12" y2="13" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
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
                <?php else:
                    foreach ($recent_posts as $post): ?>
                        <div class="ch-list-item">
                            <div class="ch-list-item-main">
                                <span class="ch-cat-badge"
                                    style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                                    <?php echo esc_html($post->cat_name ?? 'Uncategorized'); ?>
                                </span>
                                <p class="ch-list-title"><?php echo esc_html($post->title); ?></p>
                                <span class="ch-list-meta">
                                    by <?php echo $post->is_anonymous ? 'Anonymous' : esc_html($post->author_name ?? 'User'); ?>
                                    &bull;
                                    <?php echo human_time_diff(strtotime($post->created_at), current_time('timestamp')); ?> ago
                                </span>
                            </div>
                            <div class="ch-list-item-stats">
                                <span><?php echo (int) $post->vote_count; ?> votes</span>
                                <span><?php echo (int) $post->comment_count; ?> replies</span>
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
                <?php else:
                    foreach ($trending as $i => $post): ?>
                        <div class="ch-trending-item">
                            <span class="ch-trending-rank"><?php echo $i + 1; ?></span>
                            <div class="ch-trending-content">
                                <p class="ch-list-title"><?php echo esc_html($post->title); ?></p>
                                <span class="ch-cat-badge"
                                    style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
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
        (function () {
            let statsInterval;

            function updateLiveStats() {
                // Check if we're still on the overview tab
                const statsContainer = document.getElementById('stat-total-posts');
                if (!statsContainer) {
                    // Overview tab not visible, skip update
                    return;
                }

                fetch(ajaxurl + '?action=ch_live_stats', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'nonce=' + encodeURIComponent('<?php echo wp_create_nonce("ch_live_stats_nonce"); ?>')
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const stats = data.data;
                            const el1 = document.getElementById('stat-total-posts');
                            const el2 = document.getElementById('stat-total-comments');
                            const el3 = document.getElementById('stat-total-users');

                            if (el1) el1.textContent = stats.total_posts.toLocaleString();
                            if (el2) el2.textContent = stats.total_comments.toLocaleString();
                            if (el3) el3.textContent = stats.total_users.toLocaleString();

                            const reportsCard = document.getElementById('pending-reports-card');
                            const reportsNum = document.getElementById('stat-pending-reports');

                            if (reportsCard && reportsNum && stats.pending_reports > 0) {
                                reportsNum.textContent = stats.pending_reports.toLocaleString();
                                reportsCard.style.display = '';
                            } else if (reportsCard) {
                                reportsCard.style.display = 'none';
                            }

                            const postsCard = document.getElementById('pending-posts-card');
                            const postsNum = document.getElementById('stat-pending-posts');

                            if (postsCard && postsNum && stats.pending_posts > 0) {
                                postsNum.textContent = stats.pending_posts.toLocaleString();
                                postsCard.style.display = '';
                            } else if (postsCard) {
                                postsCard.style.display = 'none';
                            }
                        }
                    })
                    .catch(err => console.log('Stats update skipped (tab changed):', err.message));
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

function ch_update_live_stats()
{
    return ch_get_overview_counts();
}

function ch_get_dashboard_day_bounds()
{
    $now = current_time('timestamp');

    return [
        wp_date('Y-m-d 00:00:00', $now),
        wp_date('Y-m-d 00:00:00', strtotime('+1 day', $now)),
    ];
}

function ch_get_overview_counts()
{
    $counts = get_transient('ch_overview_counts');
    if ($counts !== false) {
        return $counts;
    }

    global $wpdb;
    [$day_start, $day_end] = ch_get_dashboard_day_bounds();
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE status = 'active') AS total_posts,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_comments WHERE status = 'active') AS total_comments,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_user_profiles) AS total_users,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_categories WHERE status = 'active') AS total_cats,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_reports WHERE status = 'pending') AS pending_reports,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE status = 'pending') AS pending_posts,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE created_at >= %s AND created_at < %s) AS posts_today,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_comments WHERE created_at >= %s AND created_at < %s) AS comments_today",
            $day_start,
            $day_end,
            $day_start,
            $day_end
        )
    );

    $counts = [
        'total_posts' => (int) ($row->total_posts ?? 0),
        'total_comments' => (int) ($row->total_comments ?? 0),
        'total_users' => (int) ($row->total_users ?? 0),
        'total_cats' => (int) ($row->total_cats ?? 0),
        'pending_reports' => (int) ($row->pending_reports ?? 0),
        'pending_posts' => (int) ($row->pending_posts ?? 0),
        'posts_today' => (int) ($row->posts_today ?? 0),
        'comments_today' => (int) ($row->comments_today ?? 0),
    ];

    set_transient('ch_overview_counts', $counts, 60);
    return $counts;
}

function ch_get_moderation_stats()
{
    $stats = get_transient('ch_moderation_stats');
    if ($stats !== false) {
        return $stats;
    }

    global $wpdb;
    $stats_row = $wpdb->get_row(
        "SELECT
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE status = 'active') AS active_posts,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts WHERE status = 'removed') AS removed_posts,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_reports WHERE status = 'pending') AS pending_reports,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_user_profiles WHERE status = 'suspended') AS suspended_users,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_user_profiles WHERE status = 'banned') AS banned_users,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ch_votes) AS total_votes"
    );

    $stats = [
        'active_posts' => (int) ($stats_row->active_posts ?? 0),
        'removed_posts' => (int) ($stats_row->removed_posts ?? 0),
        'pending_reports' => (int) ($stats_row->pending_reports ?? 0),
        'suspended_users' => (int) ($stats_row->suspended_users ?? 0),
        'banned_users' => (int) ($stats_row->banned_users ?? 0),
        'total_votes' => (int) ($stats_row->total_votes ?? 0),
    ];

    set_transient('ch_moderation_stats', $stats, 60);
    return $stats;
}

function ch_get_available_locations()
{
    $locations = get_transient('ch_available_locations');
    if ($locations !== false) {
        return $locations;
    }

    global $wpdb;
    $locations = $wpdb->get_col(
        "SELECT DISTINCT location
         FROM {$wpdb->prefix}ch_user_profiles
         WHERE location != '' AND location IS NOT NULL
         ORDER BY location"
    );

    set_transient('ch_available_locations', $locations, 300);
    return $locations;
}

// ============================================================
// TAB: CATEGORIES
// ============================================================

function ch_categories_tab($user_id, $is_admin)
{
    global $wpdb;

    $search = sanitize_text_field($_GET['cat_search'] ?? '');
    $sort = sanitize_text_field($_GET['cat_sort'] ?? 'sort_order');

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
                        <input type="text" name="cat_search" class="ch-input" placeholder="Search categories"
                            value="<?php echo esc_attr($search); ?>">
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
                <button class="ch-btn ch-btn-primary"
                    onclick="if (typeof chResetCreateCategoryForm === 'function') chResetCreateCategoryForm(); chOpenModal('ch-modal-create-cat')">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    New Category
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="ch-card" id="ch-categories-content">
        <div class="ch-categories-grid" id="ch-categories-grid">
            <?php if (empty($categories)): ?>
                <div class="ch-empty-state">
                    <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" stroke-width="1.5">
                        <path d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                    <p>No categories yet. Create the first one!</p>
                </div>
            <?php else:
                foreach ($categories as $cat): ?>
                    <div class="ch-cat-card" data-id="<?php echo (int) $cat->id; ?>">
                        <div class="ch-cat-card-color" style="background:<?php echo esc_attr($cat->color); ?>"></div>
                        <div class="ch-cat-card-body">
                            <div class="ch-cat-card-header">
                                <h4><?php echo esc_html($cat->name); ?></h4>
                                <?php if ($is_admin): ?>
                                    <div class="ch-actions-row">
                                        <button class="ch-icon-btn" title="Edit"
                                            onclick="chEditCategory(<?php echo (int) $cat->id; ?>, '<?php echo esc_js($cat->name); ?>', '<?php echo esc_js($cat->description); ?>', '<?php echo esc_attr($cat->color); ?>', '<?php echo esc_js($cat->icon); ?>', <?php echo (int) $cat->is_private; ?>, <?php echo (int) $cat->require_post_approval; ?>)">
                                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </button>
                                        <button class="ch-icon-btn" title="Toggle visibility"
                                            onclick="chToggleCategoryStatus(<?php echo (int) $cat->id; ?>, '<?php echo esc_js($cat->status); ?>', this)">
                                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                stroke-width="2">
                                                <path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                        </button>
                                        <button class="ch-icon-btn ch-icon-btn-danger" title="Delete"
                                            onclick="chDeleteCategory(<?php echo (int) $cat->id; ?>, '<?php echo esc_js($cat->name); ?>', this)">
                                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                            </svg>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="ch-cat-desc"><?php echo esc_html($cat->description ?: 'No description.'); ?></p>
                            <div class="ch-cat-stats">
                                <span><?php echo number_format($cat->post_count); ?> posts</span>
                                <span><?php echo number_format($cat->follower_count); ?> followers</span>
                                <span
                                    class="ch-status-badge ch-status-<?php echo $cat->status; ?>"><?php echo ucfirst($cat->status); ?></span>
                                <?php if ($cat->is_private): ?>
                                    <span class="ch-status-badge" style="background:#fef3c7;color:#92400e;">
                                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <rect x="3" y="11" width="18" height="11" rx="2" />
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                        </svg>
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
    </div>

    <!-- Create Category Modal -->
    <div id="ch-modal-create-cat" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal">
            <div class="ch-modal-header">
                <h3>Create Category</h3>
                <button class="ch-modal-close" onclick="chCloseCreateCategoryModal()">&times;</button>
            </div>
            <div class="ch-modal-body">
                <div class="ch-field-group">
                    <label class="ch-label">Category Name *</label>
                    <input type="text" id="ch-cat-name" class="ch-input" placeholder="e.g., Community Problems">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Description</label>
                    <textarea id="ch-cat-desc" class="ch-input ch-textarea" rows="3"
                        placeholder="Brief description of this category"></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Color</label>
                    <div class="ch-color-picker-wrap">
                        <div class="ch-color-row">
                            <input type="color" id="ch-cat-color-wheel" class="ch-color-wheel" value="#FF7551"
                                oninput="chSyncColorWheel('ch-cat-color-wheel','ch-cat-color','ch-cat-color-preview')">
                            <span class="ch-color-hex-preview" id="ch-cat-color-preview" style="background:#FF7551;"></span>
                            <input type="text" id="ch-cat-color" class="ch-input ch-color-hex-input" value="#FF7551"
                                placeholder="#FF7551" maxlength="7"
                                oninput="chSyncColorHex('ch-cat-color-wheel','ch-cat-color','ch-cat-color-preview')">
                        </div>
                        <div class="ch-color-swatches">
                            <?php foreach (['#FF7551', '#FF6640', '#ef4444', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#ec4899', '#f97316', '#84cc16'] as $sw): ?>
                                <button type="button" class="ch-swatch" style="background:<?php echo $sw; ?>;"
                                    title="<?php echo $sw; ?>"
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
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                Public
                            </span>
                            <span class="ch-vis-desc">Anyone can view, guests can post</span>
                        </label>
                        <label class="ch-vis-option">
                            <input type="radio" name="ch-cat-visibility" id="ch-cat-vis-private" value="1">
                            <span class="ch-vis-label">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                Private
                            </span>
                            <span class="ch-vis-desc">Only followers can view and post</span>
                        </label>
                    </div>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Post Moderation</label>
                    <label class="ch-checkbox-wrap">
                        <input type="checkbox" id="ch-cat-require-approval" value="1">
                        <span>Require post approval in this category</span>
                    </label>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseCreateCategoryModal()">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-save-cat-btn"
                    onclick="chSaveCategory('<?php echo esc_attr($nonce); ?>')">Create Category</button>
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
                            <span class="ch-color-hex-preview" id="ch-edit-cat-color-preview"
                                style="background:#FF7551;"></span>
                            <input type="text" id="ch-edit-cat-color" class="ch-input ch-color-hex-input" value="#FF7551"
                                placeholder="#FF7551" maxlength="7"
                                oninput="chSyncColorHex('ch-edit-cat-color-wheel','ch-edit-cat-color','ch-edit-cat-color-preview')">
                        </div>
                        <div class="ch-color-swatches">
                            <?php foreach (['#FF7551', '#FF6640', '#ef4444', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#ec4899', '#f97316', '#84cc16'] as $sw): ?>
                                <button type="button" class="ch-swatch" style="background:<?php echo $sw; ?>;"
                                    title="<?php echo $sw; ?>"
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
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                Public
                            </span>
                        </label>
                        <label class="ch-vis-option">
                            <input type="radio" name="ch-edit-cat-visibility" id="ch-edit-cat-vis-private" value="1">
                            <span class="ch-vis-label">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                Private
                            </span>
                        </label>
                    </div>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Post Moderation</label>
                    <label class="ch-checkbox-wrap">
                        <input type="checkbox" id="ch-edit-cat-require-approval" value="1">
                        <span>Require post approval in this category</span>
                    </label>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-edit-cat')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-update-cat-btn"
                    onclick="chUpdateCategory('<?php echo esc_attr($nonce); ?>')">Save Changes</button>
            </div>
            <div id="ch-edit-cat-msg"></div>
        </div>
    </div>

    <script>
        (function () {
            window.chResetCreateCategoryForm = function () {
                var nameEl = document.getElementById('ch-cat-name');
                var descEl = document.getElementById('ch-cat-desc');
                var colorEl = document.getElementById('ch-cat-color');
                var wheelEl = document.getElementById('ch-cat-color-wheel');
                var previewEl = document.getElementById('ch-cat-color-preview');
                var publicEl = document.getElementById('ch-cat-vis-public');
                var privateEl = document.getElementById('ch-cat-vis-private');
                var approvalEl = document.getElementById('ch-cat-require-approval');
                var msgEl = document.getElementById('ch-cat-msg');
                var btn = document.getElementById('ch-save-cat-btn');

                if (nameEl) nameEl.value = '';
                if (descEl) descEl.value = '';
                if (colorEl) colorEl.value = '#FF7551';
                if (wheelEl) wheelEl.value = '#FF7551';
                if (previewEl) {
                    previewEl.style.background = '#FF7551';
                    previewEl.style.opacity = '1';
                }
                if (publicEl) publicEl.checked = true;
                if (privateEl) privateEl.checked = false;
                if (approvalEl) approvalEl.checked = false;
                if (msgEl) msgEl.innerHTML = '';
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Create Category';
                }
            };

            window.chCloseCreateCategoryModal = function () {
                if (typeof chCloseModal === 'function') {
                    chCloseModal('ch-modal-create-cat');
                }
                if (typeof window.chResetCreateCategoryForm === 'function') {
                    window.chResetCreateCategoryForm();
                }
            };

            window.chEditCategory = function (id, name, desc, color, icon, isPrivate, requireApproval) {
                document.getElementById('ch-edit-cat-id').value = id;
                document.getElementById('ch-edit-cat-name').value = name;
                document.getElementById('ch-edit-cat-desc').value = desc;
                document.getElementById('ch-edit-cat-color').value = color.toUpperCase();
                const wheel = document.getElementById('ch-edit-cat-color-wheel');
                if (wheel) wheel.value = color;
                const prev = document.getElementById('ch-edit-cat-color-preview');
                if (prev) { prev.style.background = color; prev.style.opacity = '1'; }
                const privRadio = document.getElementById('ch-edit-cat-vis-private');
                const pubRadio = document.getElementById('ch-edit-cat-vis-public');
                if (privRadio && pubRadio) {
                    privRadio.checked = !!isPrivate;
                    pubRadio.checked = !isPrivate;
                }
                const requireApprovalCheckbox = document.getElementById('ch-edit-cat-require-approval');
                if (requireApprovalCheckbox) requireApprovalCheckbox.checked = !!requireApproval;
                chOpenModal('ch-modal-edit-cat');
            };

            window.chSaveCategory = function (nonce) {
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
                fd.append('require_post_approval', document.getElementById('ch-cat-require-approval')?.checked ? 1 : 0);
                fd.append('nonce', nonce);

                fetch(ajaxurl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        const msgEl = document.getElementById('ch-cat-msg');
                        if (msgEl) {
                            // Show detailed error message if available
                            let errorMsg = json.data?.message || 'Failed to create category';
                            if (!json.success && json.data?.db_error) {
                                errorMsg += '<br><small style="opacity:0.7;">Database error: ' + json.data.db_error + '</small>';
                            }
                            msgEl.innerHTML = '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">' + errorMsg + '</div>';
                        }
                        if (json.success) {
                            if (typeof window.chResetCreateCategoryForm === 'function') {
                                window.chResetCreateCategoryForm();
                            }
                            // Close modal before reload to prevent modal HTML from being replaced
                            if (typeof window.chCloseCreateCategoryModal === 'function') {
                                window.chCloseCreateCategoryModal();
                            }
                            // Small delay to let modal close animation finish
                            setTimeout(function () {
                                chAjaxReloadCategoriesSidebar();
                            }, 300);
                        }
                        else { btn.disabled = false; btn.textContent = 'Create Category'; }
                    })
                    .catch((err) => {
                        const msgEl = document.getElementById('ch-cat-msg');
                        if (msgEl) {
                            msgEl.innerHTML = '<div class="bntm-notice bntm-notice-error">Network error: ' + err.message + '</div>';
                        }
                        btn.disabled = false; btn.textContent = 'Create Category';
                    });
            };

            document.addEventListener('click', function (e) {
                if (e.target && e.target.id === 'ch-modal-create-cat' && typeof window.chResetCreateCategoryForm === 'function') {
                    window.chResetCreateCategoryForm();
                }
            });

            window.chUpdateCategory = function (nonce) {
                const id = document.getElementById('ch-edit-cat-id').value;
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
                fd.append('require_post_approval', document.getElementById('ch-edit-cat-require-approval')?.checked ? 1 : 0);
                fd.append('nonce', nonce);

                fetch(ajaxurl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        document.getElementById('ch-edit-cat-msg').innerHTML =
                            '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">' + (json.data?.message || '') + '</div>';
                        if (json.success) {
                            chCloseModal('ch-modal-edit-cat');
                            chAjaxReloadCategoriesSidebar();
                        }
                        else { btn.disabled = false; btn.textContent = 'Save Changes'; }
                    })
                    .catch(() => {
                        document.getElementById('ch-edit-cat-msg').innerHTML = '<div class="bntm-notice bntm-notice-error">Network error. Please try again.</div>';
                        btn.disabled = false; btn.textContent = 'Save Changes';
                    });
            };

            window.chDeleteCategory = function (id, name, triggerBtn) {
                if (!confirm('Delete category "' + name + '"? This cannot be undone.')) return;
                if (triggerBtn) { triggerBtn.disabled = true; }
                const fd = new FormData();
                fd.append('action', 'ch_delete_category');
                fd.append('category_id', id);
                fd.append('nonce', '<?php echo esc_attr($nonce); ?>');

                fetch(ajaxurl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            chAjaxReloadCategoriesSidebar();
                        }
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

            window.chToggleCategoryStatus = function (id, currentStatus, btn) {
                const newLabel = currentStatus === 'active' ? 'Archiving...' : 'Unarchiving...';
                const originalTitle = btn.title;
                btn.title = newLabel;
                btn.disabled = true;

                const fd = new FormData();
                fd.append('action', 'ch_toggle_category_status');
                fd.append('category_id', id);
                fd.append('nonce', '<?php echo esc_attr($nonce); ?>');

                fetch(ajaxurl, { method: 'POST', body: fd })
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
                            // AJAX reload so list respects filters
                            chAjaxReloadCategoriesSidebar();
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

function ch_posts_tab($user_id, $is_admin)
{
    global $wpdb;

    $page = max(1, (int) ($_GET['paged'] ?? 1));
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    $filter = sanitize_text_field($_GET['filter'] ?? $_POST['filter'] ?? 'all');
    $search = sanitize_text_field($_GET['s'] ?? $_POST['s'] ?? '');
    $cat_id = (int) ($_GET['cat'] ?? $_POST['cat'] ?? 0);

    $where = "WHERE p.status != 'removed'";
    if ($filter === 'pinned')
        $where .= " AND p.is_pinned = 1";
    if ($filter === 'pending')
        $where .= " AND p.status = 'pending'";
    if ($filter === 'removed')
        $where .= " AND p.status = 'removed'";
    if ($cat_id)
        $where .= $wpdb->prepare(" AND p.category_id = %d", $cat_id);
    if ($search)
        $where .= $wpdb->prepare(" AND (p.title LIKE %s OR p.content LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');

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
        <div class="ch-toolbar-filters" id="ch-posts-filter">
            <button type="button" class="ch-filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>"
                data-filter="all">All</button>
            <button type="button" class="ch-filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>"
                data-filter="pending">Pending</button>
            <button type="button" class="ch-filter-btn <?php echo $filter === 'pinned' ? 'active' : ''; ?>"
                data-filter="pinned">Pinned</button>
            <button type="button" class="ch-filter-btn <?php echo $filter === 'removed' ? 'active' : ''; ?>"
                data-filter="removed">Removed</button>
        </div>
        <div class="ch-toolbar-right">
            <form method="get" class="ch-search-form" id="ch-posts-search-form" onsubmit="return false;">
                <input type="hidden" name="tab" value="posts">
                <select name="cat" class="ch-input ch-select-sm" id="ch-posts-search-cat">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int) $cat->id; ?>" <?php selected($cat_id, $cat->id); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search posts..."
                    class="ch-input ch-search-input" id="ch-posts-search-input">
                <button type="button" class="ch-btn ch-btn-secondary" id="ch-posts-search-btn">Search</button>
            </form>
        </div>
    </div>

    <div class="ch-card" id="ch-posts-content">
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
                        <?php if ($is_admin): ?>
                            <th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="7" class="ch-table-empty">No posts found.</td>
                        </tr>
                    <?php else:
                        foreach ($posts as $post): ?>
                            <tr>
                                <td>
                                    <div class="ch-post-cell">
                                        <?php if ($post->is_pinned): ?>
                                            <span class="ch-pin-badge">
                                                <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                                                </svg>
                                                Pinned
                                            </span>
                                        <?php endif; ?>
                                        <strong><?php echo esc_html(wp_trim_words($post->title, 10)); ?></strong>
                                        <p class="ch-post-excerpt">
                                            <?php echo esc_html(wp_trim_words(strip_tags($post->content), 15)); ?></p>
                                    </div>
                                </td>
                                <td><span class="ch-cat-badge"
                                        style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>"><?php echo esc_html($post->cat_name ?? '—'); ?></span>
                                </td>
                                <td><?php echo $post->is_anonymous ? '<em>Anonymous</em>' : esc_html($post->author_name ?? 'Unknown'); ?>
                                </td>
                                <td>
                                    <span class="ch-mini-stat">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path d="M14 9V5a3 3 0 0 0-6 0v4" />
                                            <rect x="2" y="9" width="20" height="13" rx="2" />
                                        </svg>
                                        <?php echo (int) $post->vote_count; ?>
                                    </span>
                                    <span class="ch-mini-stat">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                        </svg>
                                        <?php echo (int) $post->comment_count; ?>
                                    </span>
                                </td>
                                <td><span
                                        class="ch-status-badge ch-status-<?php echo $post->status; ?>"><?php echo ucfirst($post->status); ?></span>
                                </td>
                                <td><span class="ch-date"><?php echo date('M d, Y', strtotime($post->created_at)); ?></span></td>
                                <?php if ($is_admin): ?>
                                    <td>
                                        <div class="ch-actions-row">
                                            <?php if ($post->status === 'pending'): ?>
                                                <button class="ch-btn-xs ch-btn-success" title="Approve"
                                                    onclick="chModeratePost(<?php echo (int) $post->id; ?>, 'approve_post', '<?php echo esc_attr($nonce); ?>', this)">Approve</button>
                                                <button class="ch-btn-xs ch-btn-danger" title="Reject"
                                                    onclick="chModeratePost(<?php echo (int) $post->id; ?>, 'reject_post', '<?php echo esc_attr($nonce); ?>', this)">Reject</button>
                                            <?php else: ?>
                                                <button class="ch-icon-btn" title="<?php echo $post->is_pinned ? 'Unpin' : 'Pin'; ?>"
                                                    onclick="chPinPost(<?php echo (int) $post->id; ?>, <?php echo $post->is_pinned ? 0 : 1; ?>, '<?php echo esc_attr($nonce); ?>', this)">
                                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                        stroke-width="2">
                                                        <path d="M12 2L2 7l10 5 10-5-10-5z" />
                                                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5" />
                                                    </svg>
                                                </button>
                                                <?php if ($post->status !== 'removed'): ?>
                                                    <button class="ch-icon-btn ch-icon-btn-danger" title="Remove"
                                                        onclick="chModeratePost(<?php echo (int) $post->id; ?>, 'remove_post', '<?php echo esc_attr($nonce); ?>', this)">
                                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                            stroke-width="2">
                                                            <polyline points="3 6 5 6 21 6" />
                                                            <path
                                                                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                                        </svg>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="ch-icon-btn" title="Restore"
                                                        onclick="chModeratePost(<?php echo (int) $post->id; ?>, 'restore_post', '<?php echo esc_attr($nonce); ?>', this)">
                                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                            stroke-width="2">
                                                            <polyline points="1 4 1 10 7 10" />
                                                            <path d="M3.51 15a9 9 0 1 0 .49-3.37" />
                                                        </svg>
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
        (function () {
            window.chPinPost = function (id, pinVal, nonce, btn) {
                if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
                const fd = new FormData();
                fd.append('action', 'ch_pin_post');
                fd.append('post_id', id);
                fd.append('pin', pinVal);
                fd.append('nonce', nonce);
                fetch(ajaxurl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            chAjaxReloadContent('posts', {
                                filter: '<?php echo esc_js($filter); ?>',
                                s: '<?php echo esc_js($search); ?>',
                                cat: '<?php echo esc_js($cat_id); ?>',
                                paged: '<?php echo esc_js($page); ?>'
                            });
                        }
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

            window.chModeratePost = function (id, action, nonce, btn) {
                const label = action === 'remove_post' ? 'remove' : 'restore';
                if (!confirm('Are you sure you want to ' + label + ' this post?')) return;
                const sendModeration = function (reasonText) {
                    if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
                    const fd = new FormData();
                    fd.append('action', 'ch_moderate_action');
                    fd.append('mod_action', action);
                    fd.append('target_id', id);
                    fd.append('nonce', nonce);
                    if (reasonText) fd.append('reason', reasonText);
                    fetch(ajaxurl, { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(json => {
                            if (json.success) {
                                chAjaxReloadContent('posts', {
                                    filter: '<?php echo esc_js($filter); ?>',
                                    s: '<?php echo esc_js($search); ?>',
                                    cat: '<?php echo esc_js($cat_id); ?>',
                                    paged: '<?php echo esc_js($page); ?>'
                                });
                            }
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

function ch_users_tab($user_id, $is_admin)
{
    global $wpdb;

    $page = max(1, (int) ($_GET['paged'] ?? 1));
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    $search = sanitize_text_field($_GET['s'] ?? $_POST['s'] ?? '');
    $status = sanitize_text_field($_GET['status'] ?? $_POST['status'] ?? 'all');

    $where = "WHERE 1=1";
    if ($status !== 'all')
        $where .= $wpdb->prepare(" AND p.status = %s", $status);
    if ($search)
        $where .= $wpdb->prepare(" AND (p.display_name LIKE %s OR u.user_email LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');

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
    $user_dashboard_base = ch_get_feed_url();

    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>User Management</h1>
            <p>View and manage community members</p>
        </div>
    </div>

    <div class="ch-toolbar">
        <div class="ch-toolbar-filters" id="ch-users-status-filters">
            <button type="button" class="ch-filter-btn <?php echo $status === 'all' ? 'active' : ''; ?>"
                data-status="all">All</button>
            <button type="button" class="ch-filter-btn <?php echo $status === 'active' ? 'active' : ''; ?>"
                data-status="active">Active</button>
            <button type="button" class="ch-filter-btn <?php echo $status === 'suspended' ? 'active' : ''; ?>"
                data-status="suspended">Suspended</button>
            <button type="button" class="ch-filter-btn <?php echo $status === 'banned' ? 'active' : ''; ?>"
                data-status="banned">Banned</button>
        </div>
        <form method="get" class="ch-search-form" id="ch-users-search-form" onsubmit="return false;">
            <input type="hidden" name="tab" value="users">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search users..."
                class="ch-input ch-search-input" id="ch-users-search-input">
            <button type="button" class="ch-btn ch-btn-secondary" id="ch-users-search-btn">Search</button>
        </form>
    </div>

    <div class="ch-card" id="ch-users-content">
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
                        <?php if ($is_admin): ?>
                            <th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="ch-table-empty">No users found.</td>
                        </tr>
                    <?php else:
                        foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="ch-user-cell">
                                        <?php echo ch_render_avatar($u->display_name ?: 'U', $u->avatar_url ?? '', 'ch-avatar-sm'); ?>
                                        <span><?php echo esc_html($u->display_name ?: 'User #' . $u->user_id); ?></span>
                                        <?php if ($u->is_anonymous): ?>
                                            <span class="ch-anon-badge">Anon</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo esc_html($u->user_email); ?></td>
                                <td><?php echo number_format($u->post_count); ?></td>
                                <td><?php echo number_format($u->comment_count); ?></td>
                                <td><strong><?php echo number_format($u->karma_points); ?></strong></td>
                                <td><span
                                        class="ch-status-badge ch-status-<?php echo $u->status; ?>"><?php echo ucfirst($u->status); ?></span>
                                </td>
                                <td><span
                                        class="ch-date"><?php echo date('M d, Y', strtotime($u->user_registered ?? $u->created_at)); ?></span>
                                </td>
                                <?php if ($is_admin && $u->user_id != $user_id): ?>
                                    <td>
                                        <div class="ch-actions-row">
                                            <a href="<?php echo esc_url(add_query_arg(['tab' => 'user_profile', 'uid' => (int) $u->user_id], $user_dashboard_base)); ?>"
                                                target="_blank" class="ch-btn-xs ch-btn-secondary">View Dashboard</a>
                                            <?php if ($u->status === 'active'): ?>
                                                <button class="ch-btn-xs ch-btn-warning"
                                                    onclick="chModerateUser(<?php echo (int) $u->user_id; ?>, 'suspend_user', '<?php echo esc_attr($nonce); ?>', '<?php echo addslashes($u->display_name ?? $u->user_login); ?>')">Suspend</button>
                                                <button class="ch-btn-xs ch-btn-danger"
                                                    onclick="chModerateUser(<?php echo (int) $u->user_id; ?>, 'ban_user', '<?php echo esc_attr($nonce); ?>', '<?php echo addslashes($u->display_name ?? $u->user_login); ?>')">Ban</button>
                                            <?php else: ?>
                                                <button class="ch-btn-xs ch-btn-success"
                                                    onclick="chModerateUser(<?php echo (int) $u->user_id; ?>, 'unsuspend_user', '<?php echo esc_attr($nonce); ?>', '<?php echo addslashes($u->display_name ?? $u->user_login); ?>')">Restore</button>
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

    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: REPORTS
// ============================================================

function ch_reports_tab($user_id, $is_admin)
{
    global $wpdb;

    $status = sanitize_text_field($_GET['rstatus'] ?? $_POST['rstatus'] ?? 'pending');

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

    $nonce = wp_create_nonce('ch_report_nonce');
    $feed_url = ch_get_feed_url();

    // Pre-load target links in two bulk queries — eliminates N+1 inside the loop.
    // Collect distinct post IDs and comment IDs from the report list.
    $post_ids = array_values(array_unique(array_map(
        'intval',
        array_column(array_filter((array) $reports, fn($r) => $r->target_type === 'post'), 'target_id')
    )));
    $comment_ids = array_values(array_unique(array_map(
        'intval',
        array_column(array_filter((array) $reports, fn($r) => $r->target_type === 'comment'), 'target_id')
    )));

    // Map post_id → rand_id
    $post_rand_map = [];
    if (!empty($post_ids)) {
        $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, rand_id FROM {$wpdb->prefix}ch_posts WHERE id IN ($placeholders)",
            ...$post_ids
        ));
        foreach ($rows as $row) {
            $post_rand_map[(int) $row->id] = $row->rand_id;
        }
    }

    // Map comment_id → parent post rand_id  (one extra join, still one query)
    $comment_post_rand_map = [];
    if (!empty($comment_ids)) {
        $placeholders = implode(',', array_fill(0, count($comment_ids), '%d'));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id AS comment_id, p.rand_id
             FROM {$wpdb->prefix}ch_comments c
             JOIN {$wpdb->prefix}ch_posts p ON c.post_id = p.id
             WHERE c.id IN ($placeholders)",
            ...$comment_ids
        ));
        foreach ($rows as $row) {
            $comment_post_rand_map[(int) $row->comment_id] = $row->rand_id;
        }
    }

    ob_start(); ?>
    <div class="ch-page-header">
        <div>
            <h1>Reports</h1>
            <p>Review and manage reported content and users</p>
        </div>
    </div>

    <div class="ch-toolbar">
        <div class="ch-toolbar-filters" id="ch-reports-status-filters">
            <button type="button" class="ch-filter-btn <?php echo $status === 'pending' ? 'active' : ''; ?>"
                data-rstatus="pending">Pending</button>
            <button type="button" class="ch-filter-btn <?php echo $status === 'reviewed' ? 'active' : ''; ?>"
                data-rstatus="reviewed">Reviewed</button>
            <button type="button" class="ch-filter-btn <?php echo $status === 'resolved' ? 'active' : ''; ?>"
                data-rstatus="resolved">Resolved</button>
            <button type="button" class="ch-filter-btn <?php echo $status === 'dismissed' ? 'active' : ''; ?>"
                data-rstatus="dismissed">Dismissed</button>
        </div>
    </div>

    <div class="ch-card" id="ch-reports-content">
        <div class="bntm-table-wrapper">
            <table class="bntm-table ch-table">
                <thead>
                    <tr>
                        <th>Target</th>
                        <th>Reason</th>
                        <th>Reporter</th>
                        <th>Date</th>
                        <th>Status</th>
                        <?php if ($is_admin): ?>
                            <th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="6" class="ch-table-empty">No <?php echo esc_html($status); ?> reports found.</td>
                        </tr>
                    <?php else:
                        foreach ($reports as $r):
                            // Resolve target link using pre-fetched maps — zero extra queries
                            $target_link = '';
                            if ($r->target_type === 'post' && isset($post_rand_map[(int) $r->target_id])) {
                                $target_link = add_query_arg('view_post', $post_rand_map[(int) $r->target_id], $feed_url);
                            } elseif ($r->target_type === 'comment' && isset($comment_post_rand_map[(int) $r->target_id])) {
                                $target_link = add_query_arg('view_post', $comment_post_rand_map[(int) $r->target_id], $feed_url);
                            }
                            ?>
                            <tr id="ch-report-row-<?php echo $r->id; ?>">
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:4px;">
                                        <span class="ch-type-badge ch-type-<?php echo esc_attr($r->target_type); ?>">
                                            <?php echo ucfirst($r->target_type); ?> #<?php echo $r->target_id; ?>
                                        </span>
                                        <?php if ($r->details): ?>
                                            <span
                                                style="font-size:12px;color:var(--ch-text-muted);max-width:220px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                                title="<?php echo esc_attr($r->details); ?>">
                                                <?php echo esc_html(wp_trim_words($r->details, 10)); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($target_link): ?>
                                            <a href="<?php echo esc_url($target_link); ?>" target="_blank" class="ch-link-btn"
                                                style="font-size:12px;">View post</a>
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
                                        <div style="font-size:11px;color:#9ca3af;margin-top:2px;">by
                                            <?php echo esc_html($r->reviewer_name); ?></div>
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
        (function () {
            function doPost(fd, rowId, btn) {
                if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
                fetch(ajaxurl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            const row = document.getElementById('ch-report-row-' + rowId);
                            if (row) {
                                row.style.opacity = '0';
                                row.style.transition = 'opacity 0.3s ease';
                            }
                            chAjaxReloadContent('reports');
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

            window.chResolveReport = function (reportId, resolution, nonce, btn) {
                const label = resolution === 'resolved' ? 'resolve' : 'dismiss';
                if (!confirm('Are you sure you want to ' + label + ' this report?')) return;
                const fd = new FormData();
                fd.append('action', 'ch_moderate_action');
                fd.append('mod_action', 'resolve_report');
                fd.append('target_id', reportId);
                fd.append('resolution', resolution);
                fd.append('nonce', nonce);
                doPost(fd, reportId, btn);
            };

            window.chResolveAndRemove = function (reportId, targetId, targetType, nonce, btn) {
                const label = targetType === 'post' ? 'post' : 'comment';
                if (!confirm('Remove this ' + label + ' AND resolve the report?')) return;
                const doRemoveAndResolve = function (reasonText) {
                    if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
                    const modAction = targetType === 'post' ? 'remove_post' : 'remove_comment';
                    // Step 1: remove content
                    const fd1 = new FormData();
                    fd1.append('action', 'ch_moderate_action');
                    fd1.append('mod_action', modAction);
                    fd1.append('target_id', targetId);
                    fd1.append('nonce', nonce);
                    if (reasonText) fd1.append('reason', reasonText);
                    fetch(ajaxurl, { method: 'POST', body: fd1 })
                        .then(r => r.json())
                        .then(() => {
                            // Step 2: resolve the report
                            const fd2 = new FormData();
                            fd2.append('action', 'ch_moderate_action');
                            fd2.append('mod_action', 'resolve_report');
                            fd2.append('target_id', reportId);
                            fd2.append('resolution', 'resolved');
                            fd2.append('nonce', nonce);
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

            window.chResolveAndSuspend = function (reportId, targetUserId, nonce, btn) {
                if (!confirm('Suspend this user AND resolve the report?')) return;
                if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
                // Step 1: suspend user
                const fd1 = new FormData();
                fd1.append('action', 'ch_moderate_action');
                fd1.append('mod_action', 'suspend_user');
                fd1.append('target_id', targetUserId);
                fd1.append('nonce', nonce);
                fetch(ajaxurl, { method: 'POST', body: fd1 })
                    .then(r => r.json())
                    .then(() => {
                        // Step 2: resolve report
                        const fd2 = new FormData();
                        fd2.append('action', 'ch_moderate_action');
                        fd2.append('mod_action', 'resolve_report');
                        fd2.append('target_id', reportId);
                        fd2.append('resolution', 'resolved');
                        fd2.append('nonce', nonce);
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

function ch_moderation_tab($user_id)
{
    global $wpdb;

    // Handle settings save
    if (isset($_POST['ch_save_moderation_settings']) && current_user_can('manage_options')) {
        if (!wp_verify_nonce($_POST['ch_moderation_settings_nonce'], 'ch_moderation_settings_nonce')) {
            wp_die('Security check failed');
        }
        $hide_threshold = max(1, min(50, (int) ($_POST['ch_report_auto_hide_threshold'] ?? 5)));
        update_option('ch_report_auto_hide_threshold', $hide_threshold);
        $approval = isset($_POST['ch_post_approval_enabled']) ? 1 : 0;
        update_option('ch_post_approval_enabled', $approval);
        $suspend_threshold = max(1, min(100, (int) ($_POST['ch_auto_suspend_threshold'] ?? 10)));
        update_option('ch_auto_suspend_threshold', $suspend_threshold);
        $media_upload_limit = max(1, min(20, (int) ($_POST['ch_media_upload_limit'] ?? 6)));
        update_option('ch_media_upload_limit', $media_upload_limit);
        $cat_karma = max(0, min(10000, (int) ($_POST['ch_category_creation_karma'] ?? 100)));
        update_option('ch_category_creation_karma', $cat_karma);
        $cat_creation_enabled = isset($_POST['ch_user_category_creation']) ? 1 : 0;
        update_option('ch_user_category_creation', $cat_creation_enabled);
        $retention_days = max(1, min(365, (int) ($_POST['ch_notification_retention_days'] ?? 30)));
        update_option('ch_notification_retention_days', $retention_days);

        $terms_url = esc_url_raw($_POST['ch_terms_url'] ?? '');
        $privacy_url = esc_url_raw($_POST['ch_privacy_url'] ?? '');
        $terms_version = sanitize_text_field($_POST['ch_terms_version'] ?? '');
        if ($terms_version === '') {
            $terms_version = '2026-04-21';
        }
        update_option('ch_terms_url', $terms_url);
        update_option('ch_privacy_url', $privacy_url);
        update_option('ch_terms_version', $terms_version);

        $guidelines = wp_kses_post($_POST['ch_community_guidelines'] ?? '');
        update_option('ch_community_guidelines', $guidelines);
        $terms_content = wp_kses_post($_POST['ch_terms_content'] ?? '');
        $privacy_content = wp_kses_post($_POST['ch_privacy_policy_content'] ?? '');
        update_option('ch_terms_content', $terms_content);
        update_option('ch_privacy_policy_content', $privacy_content);

        if (function_exists('ch_cleanup_old_notifications')) {
            ch_cleanup_old_notifications();
        }
        echo '<div class="bntm-notice bntm-notice-success">Moderation settings saved successfully!</div>';
    }

    $stats = ch_get_moderation_stats();

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
                <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num"><?php echo $stats['active_posts']; ?></span>
                <span class="ch-stat-label">Active Posts</span>
            </div>
        </div>
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
                <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num"><?php echo $stats['removed_posts']; ?></span>
                <span class="ch-stat-label">Removed Posts</span>
            </div>
        </div>
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                    <line x1="12" y1="9" x2="12" y2="13" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            </div>
            <div class="ch-stat-body">
                <span class="ch-stat-num"><?php echo $stats['pending_reports']; ?></span>
                <span class="ch-stat-label">Pending Reports</span>
            </div>
        </div>
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background:linear-gradient(135deg,#FF7551,#FF9A7F)">
                <svg width="20" height="20" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                </svg>
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
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                    style="margin-right:6px;vertical-align:-2px;">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                </svg>
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
                            <div class="ch-setting-desc">Posts with this many reports or more are automatically hidden from
                                the feed.</div>
                        </div>
                        <div class="ch-setting-control">
                            <input type="number" name="ch_report_auto_hide_threshold"
                                value="<?php echo esc_attr(get_option('ch_report_auto_hide_threshold', 5)); ?>" min="1"
                                max="50" class="ch-input ch-setting-number">
                            <span class="ch-setting-unit">reports</span>
                        </div>
                    </div>

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Auto-suspend threshold</div>
                            <div class="ch-setting-desc">Users accumulating this many reports across their content are
                                automatically suspended.</div>
                        </div>
                        <div class="ch-setting-control">
                            <input type="number" name="ch_auto_suspend_threshold"
                                value="<?php echo esc_attr(get_option('ch_auto_suspend_threshold', 10)); ?>" min="1"
                                max="100" class="ch-input ch-setting-number">
                            <span class="ch-setting-unit">reports</span>
                        </div>
                    </div>

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Notification retention period</div>
                            <div class="ch-setting-desc">Delete read notifications older than this many days.</div>
                        </div>
                        <div class="ch-setting-control">
                            <input type="number" name="ch_notification_retention_days"
                                value="<?php echo esc_attr(get_option('ch_notification_retention_days', 30)); ?>" min="1"
                                max="365" class="ch-input ch-setting-number">
                            <span class="ch-setting-unit">days</span>
                        </div>
                    </div>

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Require post approval</div>
                            <div class="ch-setting-desc">All new posts must be reviewed and approved by an admin before
                                appearing in the feed.</div>
                        </div>
                        <div class="ch-setting-control">
                            <label class="ch-toggle">
                                <input type="checkbox" name="ch_post_approval_enabled" value="1" <?php checked(get_option('ch_post_approval_enabled', 0), 1); ?>>
                                <span class="ch-toggle-track">
                                    <span class="ch-toggle-thumb"></span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Allow users to create categories</div>
                            <div class="ch-setting-desc">Let community members create their own topics and categories once
                                they reach the karma threshold below.</div>
                        </div>
                        <div class="ch-setting-control">
                            <label class="ch-toggle">
                                <input type="checkbox" name="ch_user_category_creation" value="1" <?php checked(get_option('ch_user_category_creation', 0), 1); ?>>
                                <span class="ch-toggle-track">
                                    <span class="ch-toggle-thumb"></span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Media upload limit</div>
                            <div class="ch-setting-desc">Maximum number of files that can be attached to one post.</div>
                        </div>
                        <div class="ch-setting-control">
                            <input type="number" name="ch_media_upload_limit"
                                value="<?php echo esc_attr(get_option('ch_media_upload_limit', 6)); ?>" min="1" max="20"
                                class="ch-input ch-setting-number">
                            <span class="ch-setting-unit">files</span>
                        </div>
                    </div>

                    <div class="ch-setting-row">
                        <div class="ch-setting-info">
                            <div class="ch-setting-label">Category creation karma threshold</div>
                            <div class="ch-setting-desc">Minimum karma points a user must have before they can create a
                                category (when the setting above is enabled).</div>
                        </div>
                        <div class="ch-setting-control">
                            <input type="number" name="ch_category_creation_karma"
                                value="<?php echo esc_attr(get_option('ch_category_creation_karma', 100)); ?>" min="0"
                                max="10000" class="ch-input ch-setting-number">
                            <span class="ch-setting-unit">karma</span>
                        </div>
                    </div>

                </div>
                <div
                    style="margin-top:20px; padding-top:20px; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end;">
                    <button type="submit" name="ch_save_moderation_settings" class="ch-btn ch-btn-primary">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="ch-card" style="margin-bottom:20px;">
        <div class="ch-card-header">
            <h3>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                    style="margin-right:6px;vertical-align:-2px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
                Community Guidelines
            </h3>
            <p style="font-size:13px;color:var(--ch-text-muted);margin:4px 0 0;">Displayed to all users in the community
                guidelines modal. Supports basic HTML tags.</p>
        </div>
        <div class="ch-card-body" style="padding:24px;">
            <form method="post">
                <?php wp_nonce_field('ch_moderation_settings_nonce', 'ch_moderation_settings_nonce'); ?>
                <div class="ch-field-group">
                    <label class="ch-label">Guidelines Content</label>
                    <textarea name="ch_community_guidelines" class="ch-input ch-textarea" rows="12"
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
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Save Guidelines
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="ch-card" style="margin-bottom:20px;">
        <div class="ch-card-header">
            <h3>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                    style="margin-right:6px;vertical-align:-2px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
                Legal Policies (Registration)
            </h3>
            <p style="font-size:13px;color:var(--ch-text-muted);margin:4px 0 0;">Used by the Terms and Conditions and
                Privacy Policy links in the registration form.</p>
        </div>
        <div class="ch-card-body" style="padding:24px;">
            <form method="post">
                <?php wp_nonce_field('ch_moderation_settings_nonce', 'ch_moderation_settings_nonce'); ?>

                <div class="ch-field-row" style="margin-bottom:12px;">
                    <div class="ch-field-group ch-field-half">
                        <label class="ch-label">Terms URL <span class="ch-optional">(optional)</span></label>
                        <input type="url" name="ch_terms_url" class="ch-input"
                            value="<?php echo esc_attr(get_option('ch_terms_url', '')); ?>"
                            placeholder="https://your-site.com/terms-and-conditions">
                    </div>
                    <div class="ch-field-group ch-field-half">
                        <label class="ch-label">Privacy Policy URL <span class="ch-optional">(optional)</span></label>
                        <input type="url" name="ch_privacy_url" class="ch-input"
                            value="<?php echo esc_attr(get_option('ch_privacy_url', '')); ?>"
                            placeholder="https://your-site.com/privacy-policy">
                    </div>
                </div>

                <div class="ch-field-group">
                    <label class="ch-label">Terms Version</label>
                    <input type="text" name="ch_terms_version" class="ch-input"
                        value="<?php echo esc_attr(get_option('ch_terms_version', '2026-04-21')); ?>"
                        placeholder="e.g. 2026-04-21 or v1.0">
                    <div style="margin-top:6px;font-size:12px;color:#9ca3af;">
                        Stored with user consent records for auditability.
                    </div>
                </div>

                <div class="ch-field-group" style="margin-top:14px;">
                    <label class="ch-label">Terms and Conditions Content</label>
                    <textarea name="ch_terms_content" class="ch-input ch-textarea" rows="10"
                        style="font-family:monospace;font-size:13px;"
                        placeholder="Leave blank to use standard default terms content."><?php echo esc_textarea(get_option('ch_terms_content', '')); ?></textarea>
                </div>

                <div class="ch-field-group" style="margin-top:14px;">
                    <label class="ch-label">Privacy Policy Content</label>
                    <textarea name="ch_privacy_policy_content" class="ch-input ch-textarea" rows="10"
                        style="font-family:monospace;font-size:13px;"
                        placeholder="Leave blank to use standard default privacy policy content."><?php echo esc_textarea(get_option('ch_privacy_policy_content', '')); ?></textarea>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                    <button type="button" class="ch-btn ch-btn-secondary ch-btn-sm"
                        onclick="document.querySelector('[name=ch_terms_content]').value='';document.querySelector('[name=ch_privacy_policy_content]').value='';this.textContent='Cleared — save to reset defaults';">
                        Reset Legal Content
                    </button>
                    <button type="submit" name="ch_save_moderation_settings" class="ch-btn ch-btn-primary">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Save Legal Policies
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
            <?php else:
                foreach ($recent_removed as $post): ?>
                    <div class="ch-list-item">
                        <div class="ch-list-item-main">
                            <strong><?php echo esc_html($post->title); ?></strong>
                            <p class="ch-list-meta">by <?php echo esc_html($post->author_name ?? 'Unknown'); ?> &bull; removed
                                <?php echo human_time_diff(strtotime($post->updated_at)); ?> ago</p>
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

function ch_activity_tab($user_id)
{
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
                        <tr>
                            <td colspan="6" class="ch-table-empty">No activity logged yet.</td>
                        </tr>
                    <?php else:
                        foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->admin_name ?? 'System'); ?></td>
                                <td><code class="ch-action-code"><?php echo esc_html($log->action); ?></code></td>
                                <td><?php echo $log->target_type ? esc_html(ucfirst($log->target_type) . ' #' . $log->target_id) : '—'; ?>
                                </td>
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

function ch_profile_tab($user_id)
{
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
                        <input type="text" id="ch-profile-display-name" class="ch-input"
                            value="<?php echo esc_attr($profile->display_name); ?>" placeholder="Your display name">
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-label">Bio</label>
                        <textarea id="ch-profile-bio" class="ch-input ch-textarea" rows="4"
                            placeholder="Tell us about yourself..."><?php echo esc_textarea($profile->bio); ?></textarea>
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-label">Location</label>
                        <input type="text" id="ch-profile-location" class="ch-input"
                            value="<?php echo esc_attr($profile->location); ?>" placeholder="Your location">
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-label">Avatar</label>
                        <div style="display:flex;align-items:center;gap:14px;margin-bottom:6px;">
                            <div id="ch-avatar-preview-wrap"
                                style="width:64px;height:64px;border-radius:50%;overflow:hidden;border:2px solid var(--ch-border);flex-shrink:0;background:var(--ch-accent-light);display:flex;align-items:center;justify-content:center;">
                                <?php if ($profile->avatar_url): ?>
                                    <img id="ch-avatar-preview-img" src="<?php echo esc_url($profile->avatar_url); ?>"
                                        alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <span id="ch-avatar-preview-initials"
                                        style="font-size:22px;font-weight:700;color:var(--ch-accent);"><?php echo esc_html(strtoupper(substr($profile->display_name ?: 'U', 0, 1))); ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <input type="file" id="ch-profile-avatar" class="ch-input" accept="image/*"
                                    style="margin-bottom:4px;" onchange="chPreviewAvatar(this)">
                                <div style="font-size:11px;color:var(--ch-text-subtle);">JPG, PNG or GIF · Max 5MB · Will be
                                    resized to 400×400</div>
                            </div>
                        </div>
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-checkbox-label">
                            <input type="checkbox" id="ch-profile-anonymous" <?php checked($profile->is_anonymous, 1); ?>>
                            Post anonymously by default
                        </label>
                    </div>
                    <button type="button" class="ch-btn ch-btn-primary"
                        onclick="chUpdateProfile('<?php echo wp_create_nonce('ch_profile_nonce'); ?>')">Save
                        Profile</button>
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

function bntm_shortcode_ch_my_feed()
{
    global $wpdb;

    $view_uid = (int) ($_GET['uid'] ?? 0);
    $viewer_id = get_current_user_id();
    $target_id = ($view_uid && current_user_can('manage_options')) ? $view_uid : $viewer_id;
    $is_own = ($target_id === $viewer_id);

    ch_ensure_profile($target_id);

    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d",
        $target_id
    ));
    $wp_user = get_userdata($target_id);
    if (!$profile || !$wp_user) {
        return '<p class="ch-empty" style="padding:40px;text-align:center;">User not found.</p>';
    }

    $display_name = $profile->display_name ?: $wp_user->display_name ?: 'Community Member';
    $joined = $wp_user->user_registered ? date('F Y', strtotime($wp_user->user_registered)) : 'Unknown';
    $feed_url = ch_get_feed_url();
    $subtab = sanitize_text_field($_GET['subtab'] ?? 'posts');

    $user_posts = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, c.name as cat_name, c.color as cat_color
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         WHERE p.user_id = %d AND p.status = 'active'
         ORDER BY p.created_at DESC LIMIT 50",
        $target_id
    ));

    $bookmarked_posts = [];
    $upvoted_posts = [];
    $user_comments = [];

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

    $upvotes_given = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ch_votes WHERE user_id=%d AND value=1",
        $target_id
    ));
    $saved_count = $is_own ? (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ch_bookmarks WHERE user_id=%d",
        $target_id
    )) : 0;

    $categories_for_modal = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}ch_categories WHERE status='active' ORDER BY name ASC"
    );

    ob_start(); ?>
    <script>window.chMyFeedNonce = '<?php echo wp_create_nonce('ch_myfeed_nonce'); ?>';</script>
    <div class="ch-my-feed-wrap">
        <div style="margin-bottom:18px;">
            <a href="<?php echo esc_url($feed_url); ?>" class="ch-back-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <polyline points="15 18 9 12 15 6" />
                </svg>
                Back to Forum
            </a>
        </div>

        <div class="ch-mf-profile-card">
            <div class="ch-mf-avatar-wrap">
                <?php if ($profile->avatar_url): ?>
                    <img src="<?php echo esc_url($profile->avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>"
                        class="ch-mf-avatar-img">
                <?php else: ?>
                    <div class="ch-mf-avatar-initials"><?php echo esc_html(strtoupper(substr($display_name, 0, 1))); ?></div>
                <?php endif; ?>
                <span
                    class="ch-status-badge ch-status-<?php echo esc_attr($profile->status); ?>"><?php echo esc_html(ucfirst($profile->status)); ?></span>
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
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                            <?php echo esc_html($profile->location); ?>
                        </span>
                    <?php endif; ?>
                    <span class="ch-mf-meta-item">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                        Joined <?php echo esc_html($joined); ?>
                    </span>
                    <span class="ch-mf-meta-item">@<?php echo esc_html($wp_user->user_login); ?></span>
                </div>
                <?php if ($is_own): ?>
                    <div style="margin-top:14px;">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'profile', $feed_url)); ?>"
                            class="ch-btn ch-btn-secondary ch-btn-sm">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
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
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <polygon
                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                    </svg>
                    Karma
                </span>
            </div>
            <div class="ch-mf-stat">
                <span class="ch-mf-stat-num"><?php echo number_format($profile->post_count); ?></span>
                <span class="ch-mf-stat-label">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                    Posts
                </span>
            </div>
            <div class="ch-mf-stat">
                <span class="ch-mf-stat-num"><?php echo number_format($profile->comment_count); ?></span>
                <span class="ch-mf-stat-label">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    Comments
                </span>
            </div>
            <div class="ch-mf-stat">
                <span class="ch-mf-stat-num"><?php echo $upvotes_given; ?></span>
                <span class="ch-mf-stat-label">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <polyline points="18 15 12 9 6 15" />
                    </svg>
                    Upvotes given
                </span>
            </div>
            <?php if ($is_own): ?>
                <div class="ch-mf-stat">
                    <span class="ch-mf-stat-num"><?php echo $saved_count; ?></span>
                    <span class="ch-mf-stat-label">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                        </svg>
                        Saved
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div class="ch-mf-subnav" id="ch-mf-subnav">
            <button type="button" class="ch-mf-subnav-item <?php echo $subtab === 'posts' ? 'active' : ''; ?>"
                data-subtab="posts">My Posts</button>
            <?php if ($is_own): ?>
                <button type="button" class="ch-mf-subnav-item <?php echo $subtab === 'saved' ? 'active' : ''; ?>"
                    data-subtab="saved">Saved</button>
                <button type="button" class="ch-mf-subnav-item <?php echo $subtab === 'upvoted' ? 'active' : ''; ?>"
                    data-subtab="upvoted">Upvoted</button>
                <button type="button" class="ch-mf-subnav-item <?php echo $subtab === 'comments' ? 'active' : ''; ?>"
                    data-subtab="comments">Comments</button>
            <?php endif; ?>
        </div>

        <div class="ch-mf-content" id="ch-mf-content">
            <?php if ($subtab === 'posts'): ?>
                <?php if (empty($user_posts)): ?>
                    <div class="ch-mf-empty">
                        <svg width="40" height="40" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                        </svg>
                        <p><?php echo $is_own ? "You haven't posted anything yet." : "This user has no public posts yet."; ?></p>
                        <?php if ($is_own): ?><a href="<?php echo esc_url($feed_url); ?>" class="ch-btn ch-btn-primary">Go to Forum
                                Feed</a><?php endif; ?>
                    </div>
                <?php else:
                    foreach ($user_posts as $p): ?>
                        <div class="ch-mf-post-card">
                            <div class="ch-mf-post-top">
                                <span class="ch-cat-badge"
                                    style="background:<?php echo esc_attr($p->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($p->cat_color ?? '#FF7551'); ?>"><?php echo esc_html($p->cat_name ?? 'General'); ?></span>
                                <?php if ($p->is_pinned): ?><span class="ch-mf-pinned-badge">📌 Pinned</span><?php endif; ?>
                                <span
                                    class="ch-mf-time"><?php echo human_time_diff(strtotime($p->created_at), current_time('timestamp')); ?>
                                    ago</span>
                                <?php if ($is_own || current_user_can('manage_options')): ?>
                                    <div class="ch-mf-post-actions">
                                        <button class="ch-btn-xs ch-btn-secondary"
                                            onclick="chOpenEditPostModal(<?php echo (int) $p->id; ?>)">Edit</button>
                                        <button class="ch-btn-xs ch-btn-danger"
                                            onclick="chDeletePost(<?php echo (int) $p->id; ?>, '<?php echo esc_attr(wp_create_nonce('ch_post_view_nonce')); ?>')">Delete</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo esc_url(add_query_arg('view_post', $p->rand_id, $feed_url)); ?>"
                                class="ch-mf-post-title"><?php echo esc_html($p->title); ?></a>
                            <p class="ch-mf-post-excerpt"><?php echo esc_html(wp_trim_words($p->content, 22)); ?></p>
                            <div class="ch-mf-post-footer">
                                <span>▲ <?php echo (int) $p->vote_count; ?> upvotes</span>
                                <span>💬 <?php echo (int) $p->comment_count; ?> comments</span>
                                <span>👁 <?php echo number_format($p->view_count); ?> views</span>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>

            <?php elseif ($subtab === 'saved' && $is_own): ?>
                <?php if (empty($bookmarked_posts)): ?>
                    <div class="ch-mf-empty">
                        <svg width="40" height="40" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                        </svg>
                        <p>You haven't saved any posts yet.</p>
                    </div>
                <?php else:
                    foreach ($bookmarked_posts as $p): ?>
                        <div class="ch-mf-post-card">
                            <div class="ch-mf-post-top">
                                <span class="ch-cat-badge"
                                    style="background:<?php echo esc_attr($p->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($p->cat_color ?? '#FF7551'); ?>"><?php echo esc_html($p->cat_name ?? 'General'); ?></span>
                                <span
                                    class="ch-mf-time"><?php echo human_time_diff(strtotime($p->created_at), current_time('timestamp')); ?>
                                    ago</span>
                            </div>
                            <a href="<?php echo esc_url(add_query_arg('view_post', $p->rand_id, $feed_url)); ?>"
                                class="ch-mf-post-title"><?php echo esc_html($p->title); ?></a>
                            <p class="ch-mf-post-excerpt"><?php echo esc_html(wp_trim_words($p->content, 22)); ?></p>
                            <div class="ch-mf-post-footer">
                                <span>▲ <?php echo (int) $p->vote_count; ?></span>
                                <span>💬 <?php echo (int) $p->comment_count; ?></span>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>

            <?php elseif ($subtab === 'upvoted' && $is_own): ?>
                <?php if (empty($upvoted_posts)): ?>
                    <div class="ch-mf-empty">
                        <svg width="40" height="40" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5">
                            <polyline points="18 15 12 9 6 15" />
                        </svg>
                        <p>You haven't upvoted any posts yet.</p>
                    </div>
                <?php else:
                    foreach ($upvoted_posts as $p): ?>
                        <div class="ch-mf-post-card">
                            <div class="ch-mf-post-top">
                                <span class="ch-cat-badge"
                                    style="background:<?php echo esc_attr($p->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($p->cat_color ?? '#FF7551'); ?>"><?php echo esc_html($p->cat_name ?? 'General'); ?></span>
                                <span
                                    class="ch-mf-time"><?php echo human_time_diff(strtotime($p->created_at), current_time('timestamp')); ?>
                                    ago</span>
                            </div>
                            <a href="<?php echo esc_url(add_query_arg('view_post', $p->rand_id, $feed_url)); ?>"
                                class="ch-mf-post-title"><?php echo esc_html($p->title); ?></a>
                            <p class="ch-mf-post-excerpt"><?php echo esc_html(wp_trim_words($p->content, 22)); ?></p>
                            <div class="ch-mf-post-footer">
                                <span>▲ <?php echo (int) $p->vote_count; ?></span>
                                <span>💬 <?php echo (int) $p->comment_count; ?></span>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>

            <?php elseif ($subtab === 'comments' && $is_own): ?>
                <?php if (empty($user_comments)): ?>
                    <div class="ch-mf-empty">
                        <svg width="40" height="40" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        <p>You haven't commented on anything yet.</p>
                    </div>
                <?php else:
                    foreach ($user_comments as $cm): ?>
                        <div class="ch-mf-comment-card">
                            <div class="ch-mf-comment-post-ref">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                </svg>
                                On: <a href="<?php echo esc_url(add_query_arg('view_post', $cm->post_rand_id, $feed_url)); ?>"
                                    class="ch-mf-ref-link"><?php echo esc_html(wp_trim_words($cm->post_title, 8)); ?></a>
                                <span class="ch-mf-time"
                                    style="margin-left:auto;"><?php echo human_time_diff(strtotime($cm->created_at), current_time('timestamp')); ?>
                                    ago</span>
                            </div>
                            <p class="ch-mf-comment-content"><?php echo esc_html($cm->content); ?></p>
                            <div class="ch-mf-post-footer"><span>▲ <?php echo (int) $cm->vote_count; ?> votes</span></div>
                        </div>
                    <?php endforeach; endif; ?>
            <?php endif; ?>
        </div>

        <?php
        echo ch_render_post_composer_modal([
            'mode' => 'edit',
            'modal_id' => 'ch-modal-edit-post',
            'modal_title' => 'Edit Post',
            'user_id' => $viewer_id,
            'display_name' => wp_get_current_user()->display_name ?: 'You',
            'avatar_url' => $profile->avatar_url ?? '',
            'categories' => $categories_for_modal,
            'submit_nonce' => wp_create_nonce('ch_post_view_nonce'),
            'submit_handler' => 'chUpdatePost',
            'submit_label' => 'Save Changes',
            'hint_text' => 'Edit your post. Changes are visible immediately.',
            'message_id' => 'ch-edit-post-msg',
            'category_id' => 'ch-edit-post-cat',
            'title_id' => 'ch-edit-post-title',
            'content_id' => 'ch-edit-post-content',
            'tags_id' => 'ch-edit-post-tags',
            'anon_id' => 'ch-edit-post-anon',
            'media_input_id' => 'ch-edit-post-media',
            'media_label_id' => 'ch-edit-post-media-label2',
            'media_preview_id' => 'ch-edit-post-media-preview2',
            'show_hidden_post_id' => true,
            'post_id_input' => 'ch-edit-post-id',
        ]);
        ?>
    </div><!-- .ch-my-feed-wrap -->

    <script>
        // ── My Feed subtab AJAX loader ──────────────────────────────
        (function () {
            var subnav = document.getElementById('ch-mf-subnav');
            var content = document.getElementById('ch-mf-content');
            if (!subnav || !content) return;

            subnav.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-subtab]');
                if (!btn || btn.classList.contains('active')) return;

                var subtab = btn.dataset.subtab;

                // Update active state
                subnav.querySelectorAll('.ch-mf-subnav-item').forEach(function (t) {
                    t.classList.remove('active');
                });
                btn.classList.add('active');

                // Loading state
                content.style.opacity = '0.45';
                content.style.pointerEvents = 'none';

                var fd = new FormData();
                fd.append('action', 'ch_myfeed_subtab');
                fd.append('nonce', window.chMyFeedNonce || '');
                fd.append('subtab', subtab);

                fetch(window.chAjaxUrl || window.ajaxurl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.success) {
                            content.innerHTML = json.data.html;
                            if (window.chRunEmbeddedScripts) window.chRunEmbeddedScripts(content);

                            // Update URL
                            var url = new URL(window.location.href);
                            url.searchParams.set('subtab', subtab);
                            history.replaceState(null, '', url.toString());
                        } else {
                            content.innerHTML = '<p class="ch-empty" style="padding:20px;text-align:center;color:var(--ch-text-muted);">' + (json.data?.message || 'Failed to load content.') + '</p>';
                        }
                    })
                    .catch(function () {
                        content.innerHTML = '<p class="ch-empty" style="padding:20px;text-align:center;color:var(--ch-text-muted);">Network error. Please try again.</p>';
                    })
                    .finally(function () {
                        content.style.opacity = '';
                        content.style.pointerEvents = '';
                    });
            });
        })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// PUBLIC USER PROFILE VIEW
// ============================================================

function ch_public_user_profile($view_uid)
{
    global $wpdb;

    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d",
        $view_uid
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

    $feed_url = ch_get_feed_url();
    $joined = $wp_user->user_registered ? date('F Y', strtotime($wp_user->user_registered)) : 'Unknown';

    ob_start();
    ch_output_global_styles_fallback();
    echo ch_global_scripts();
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>
    <style>
        html,
        body {
            background: var(--ch-bg);
            color: var(--ch-text);
            margin: 0;
        }

        .ch-public-profile-shell {
            min-height: 100vh;
            background: var(--ch-bg);
            color: var(--ch-text);
            font-family: var(--ch-font);
        }

        .ch-public-profile-shell .ch-top-nav {
            background: var(--ch-surface);
            border-bottom: 1px solid var(--ch-border);
            padding: 0 24px;
            min-height: 56px;
            box-shadow: var(--ch-shadow-sm);
        }

        .ch-public-profile-shell .ch-nav-links {
            display: flex;
            gap: 2px;
            height: 100%;
            align-items: center;
        }

        @media (min-width: 781px) {
            .ch-public-profile-shell .ch-top-nav {
                display: grid;
                grid-template-columns: auto 1fr auto;
                align-items: center;
            }

            .ch-public-profile-shell .ch-top-nav .ch-mobile-drawer-wrap > .ch-nav-links {
                grid-column: 2;
                grid-row: 1;
                display: flex;
                align-items: center;
                width: 100%;
                min-width: 0;
                justify-self: stretch;
                align-self: center;
            }

            .ch-public-profile-shell .ch-top-nav .ch-mobile-drawer-wrap > .ch-user-bar {
                display: none !important;
            }

            .ch-public-profile-shell .ch-top-nav > .ch-nav-user-desktop,
            .ch-public-profile-shell .ch-top-nav > .ch-nav-guest-desktop {
                grid-column: 3;
                grid-row: 1;
                justify-self: end;
                align-self: center;
            }
        }

        .ch-public-profile-page {
            max-width: 980px;
            margin: 0 auto;
            padding: 26px 20px 40px;
        }
    </style>
    <div class="ch-public-profile-shell">
        <nav class="ch-top-nav">
                        <div class="ch-top-nav-logo">
            <button class="ch-burger-menu-btn" type="button" aria-label="Toggle menu" aria-expanded="false"
                onclick="chToggleMobileMenu(this, '#ch-feed-drawer-pp');">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M3 12h18M3 6h18M3 18h18" />
                </svg>
            </button>

                <a href="<?php echo esc_url($feed_url); ?>" class="ch-brand-logo-link">
                    <img src="<?php echo esc_url(bntm_ch_logo_url()); ?>" alt="CivicHub Logo" class="ch-brand-logo">
                </a>
            </div>

            <!-- Mobile drawer -->
            <div id="ch-feed-drawer-pp" class="ch-mobile-drawer-wrap">
                <button class="ch-top-drawer-close" type="button" onclick="chCloseAllMobileMenus()"
                    aria-label="Close menu">&times;</button>
                <div class="ch-nav-links">
                    <a href="<?php echo esc_url($feed_url); ?>" class="ch-nav-link">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="ch-nav-label">Back to Forum</span>
                    </a>
                </div>
                <?php if (!$current_user_id_pp): ?>
                    <div class="ch-user-bar">
                        <a href="<?php echo esc_url(ch_get_auth_url('login')); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">Sign In</a>
                        <a href="<?php echo esc_url(ch_get_auth_url('register')); ?>" class="ch-btn ch-btn-primary ch-btn-sm">Join</a>
                    </div>
                <?php endif; ?>
            </div><!-- /#ch-feed-drawer-pp -->

            <?php if ($current_user_id_pp): ?>
                <?php $current_display_pp = wp_get_current_user()->display_name ?: 'U'; ?>
                <!-- ── Desktop logged-in user bar ── -->
                <div class="ch-user-bar ch-nav-user-desktop">
                    <div class="ch-notifications-dropdown ch-top-nav-notifications">
                        <button class="ch-icon-action-btn ch-notifications-btn" id="ch-notif-btn" onclick="chToggleNotifications(event)"
                            aria-label="Notifications">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg>
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
                        <button class="ch-avatar-btn" id="ch-profile-btn" onclick="chToggleProfileMenu(event)"
                            aria-label="Profile" data-ch-current-user-avatar="1"
                            data-avatar-name="<?php echo esc_attr($current_display_pp); ?>">
                            <?php echo ch_render_avatar($current_display_pp, $current_profile_pp->avatar_url ?? '', 'ch-avatar-btn-inner', 'ch-current-user-avatar-img'); ?>
                        </button>
                        <div class="ch-dropdown-panel ch-dropdown-panel-sm" id="ch-profile-menu" style="display:none;">
                            <div class="ch-dropdown-user-info">
                                <div class="ch-avatar-btn ch-avatar-btn-lg" data-ch-current-user-avatar="1"
                                    data-avatar-name="<?php echo esc_attr($current_display_pp); ?>">
                                    <?php echo ch_render_avatar($current_display_pp, $current_profile_pp->avatar_url ?? '', 'ch-avatar-btn-inner ch-avatar-btn-inner-lg', 'ch-current-user-avatar-img'); ?>
                                </div>
                                <div>
                                    <div class="ch-dropdown-username"><?php echo esc_html($current_display_pp); ?></div>
                                    <div class="ch-dropdown-usermeta">Community Member</div>
                                </div>
                            </div>
                            <div class="ch-dropdown-divider"></div>
                            <a href="javascript:void(0)" class="ch-dropdown-item"
                                onclick="chOpenSettingsModal(); chCloseProfileMenu();">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <circle cx="12" cy="12" r="3" />
                                    <path
                                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                </svg>
                                Settings
                            </a>
                            <div class="ch-dropdown-divider"></div>
                            <a href="<?php echo esc_url(ch_get_logout_url($feed_url)); ?>"
                                class="ch-dropdown-item ch-dropdown-item-danger">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                                Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- ── Desktop guest auth buttons ── -->
                <div class="ch-user-bar ch-nav-guest-desktop">
                    <a href="<?php echo esc_url(ch_get_auth_url('login')); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">Sign In</a>
                    <a href="<?php echo esc_url(ch_get_auth_url('register')); ?>" class="ch-btn ch-btn-primary ch-btn-sm">Join</a>
                </div>
            <?php endif; ?>
        </nav>
        <div class="ch-public-profile-page">
            <div class="ch-public-profile-wrap">

                <div class="ch-public-profile-card">
                    <div class="ch-public-profile-avatar">
                        <?php if ($profile->avatar_url): ?>
                            <img src="<?php echo esc_url($profile->avatar_url); ?>"
                                alt="<?php echo esc_attr($display_name); ?>">
                        <?php else: ?>
                            <div class="ch-avatar-lg"><?php echo esc_html(strtoupper(substr($display_name, 0, 1))); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="ch-public-profile-info">
                        <h1 class="ch-public-profile-name"><?php echo esc_html($display_name); ?></h1>
                        <?php if ($profile->location): ?>
                            <div class="ch-public-profile-meta">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                    <circle cx="12" cy="10" r="3" />
                                </svg>
                                <?php echo esc_html($profile->location); ?>
                            </div>
                        <?php endif; ?>
                        <div class="ch-public-profile-meta">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                            Joined <?php echo esc_html($joined); ?>
                        </div>
                        <?php if ($profile->bio): ?>
                            <p class="ch-public-profile-bio"><?php echo esc_html($profile->bio); ?></p>
                        <?php endif; ?>
                        <span
                            class="ch-status-badge ch-status-<?php echo esc_attr($profile->status); ?>"><?php echo esc_html(ucfirst($profile->status)); ?></span>
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
                    <?php else:
                        foreach ($user_posts as $p): ?>
                            <a href="<?php echo esc_url(add_query_arg('view_post', $p->rand_id, $feed_url)); ?>"
                                class="ch-public-post-card">
                                <div class="ch-public-post-top">
                                    <span class="ch-cat-badge"
                                        style="background:<?php echo esc_attr($p->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($p->cat_color ?? '#FF7551'); ?>">
                                        <?php echo esc_html($p->cat_name ?? 'General'); ?>
                                    </span>
                                    <span
                                        class="ch-public-post-time"><?php echo human_time_diff(strtotime($p->created_at), current_time('timestamp')); ?>
                                        ago</span>
                                </div>
                                <h3 class="ch-public-post-title"><?php echo esc_html($p->title); ?></h3>
                                <p class="ch-public-post-excerpt"><?php echo esc_html(wp_trim_words($p->content, 20)); ?></p>
                                <div class="ch-public-post-footer">
                                    <span>▲ <?php echo (int) $p->vote_count; ?></span>
                                    <span>💬 <?php echo (int) $p->comment_count; ?></span>
                                    <span>👁 <?php echo number_format($p->view_count); ?></span>
                                </div>
                            </a>
                        <?php endforeach; endif; ?>
                </div>

            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ============================================================
// FRONTEND: FORUM FEED
// ============================================================

function bntm_shortcode_ch_feed()
{
    global $wpdb;

    $tab = sanitize_text_field($_GET['tab'] ?? '');

    // Route: viewing a single post
    $rand_id = sanitize_text_field($_GET['view_post'] ?? '');
    if ($rand_id) {
        return bntm_shortcode_ch_post_view();
    }

    // Route: public user profile view (admin "View Dashboard" button)
    if ($tab === 'user_profile') {
        $view_uid = (int) ($_GET['uid'] ?? 0);
        if ($view_uid) {
            return ch_public_user_profile($view_uid);
        }
    }

    // Route: my feed — handled below in the main render path so the navbar stays visible

    // Route: profile tab
    if ($tab === 'profile') {
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_redirect(ch_get_auth_url('login', get_permalink() . '?tab=profile'));
            exit;
        }
        // Inject dark mode class BEFORE the theme renders — eliminates flash of white
        add_action('wp_head', function () {
            echo '<script>try{if(localStorage.getItem("ch_dark_mode")==="1"){document.documentElement.classList.add("ch-dark");document.documentElement.style.background="var(--ch-bg,#121214)";document.body&&(document.body.style.background="var(--ch-bg,#121214)");}}catch(e){}</script>';
        }, 1);
        ob_start();
        ch_output_global_styles_fallback();
        echo ch_global_scripts();
        $current_profile_p = $wpdb->get_row($wpdb->prepare("SELECT avatar_url FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id));
        $feed_url_back = ch_get_feed_url();
        $current_display_p = wp_get_current_user()->display_name ?: 'U';
        $current_avatar_p = $current_profile_p->avatar_url ?? '';
        ?>
        <style>
            html,
            body {
                background: var(--ch-bg) !important;
                color: var(--ch-text);
                margin: 0;
            }

            .ch-dark html,
            .ch-dark body {
                background: #121214 !important;
            }

            .ch-profile-standalone {
                font-family: var(--ch-font);
                background: var(--ch-bg);
                min-height: 100vh;
                color: var(--ch-text);
            }

            .ch-profile-standalone .ch-top-nav {
                background: var(--ch-surface);
                border-bottom: 1px solid var(--ch-border);
                padding: 0 24px;
                height: 56px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                position: sticky;
                top: 0;
                z-index: 200;
                box-shadow: var(--ch-shadow-sm);
            }

            .ch-profile-standalone .ch-nav-links {
                display: flex;
                gap: 2px;
                height: 100%;
                align-items: center;
            }

            .ch-profile-page-wrap {
                max-width: 880px;
                margin: 0 auto;
                padding: 26px 20px;
            }

            .ch-profile-page-wrap .ch-card-body {
                padding: 20px;
            }

            .ch-stat-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }

            .ch-stat-item {
                text-align: center;
                padding: 16px;
                background: var(--ch-bg);
                border-radius: var(--ch-radius);
                border: 1px solid var(--ch-border);
            }

            .ch-stat-item .ch-stat-num {
                display: block;
                font-size: 22px;
                font-weight: 700;
                color: var(--ch-accent);
                letter-spacing: -0.3px;
            }

            .ch-stat-item .ch-stat-label {
                display: block;
                font-size: 11.5px;
                color: var(--ch-text-subtle);
                margin-top: 3px;
                font-weight: 500;
            }
        </style>
        <div class="ch-profile-standalone">
            <nav class="ch-top-nav">
                <div class="ch-nav-links">
                    <a href="<?php echo esc_url($feed_url_back); ?>" class="ch-nav-link">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="15 18 9 12 15 6" />
                        </svg>
                        Back to Forum
                    </a>
                </div>
                <div class="ch-user-bar">
                    <button class="ch-avatar-btn" id="ch-profile-btn" onclick="chToggleProfileMenu(event)" aria-label="Profile"
                        data-ch-current-user-avatar="1" data-avatar-name="<?php echo esc_attr($current_display_p); ?>">
                        <?php echo ch_render_avatar($current_display_p, $current_avatar_p, 'ch-avatar-btn-inner', 'ch-current-user-avatar-img'); ?>
                    </button>
                    <div class="ch-dropdown-panel ch-dropdown-panel-sm" id="ch-profile-menu" style="display:none;">
                        <div class="ch-dropdown-user-info">
                            <div class="ch-avatar-btn ch-avatar-btn-lg" data-ch-current-user-avatar="1"
                                data-avatar-name="<?php echo esc_attr($current_display_p); ?>">
                                <?php echo ch_render_avatar($current_display_p, $current_avatar_p, 'ch-avatar-btn-inner ch-avatar-btn-inner-lg', 'ch-current-user-avatar-img'); ?>
                            </div>
                            <div>
                                <div class="ch-dropdown-username"><?php echo esc_html($current_display_p); ?></div>
                                <div class="ch-dropdown-usermeta">Community Member</div>
                            </div>
                        </div>
                        <div class="ch-dropdown-divider"></div>
                        <a href="javascript:void(0)" class="ch-dropdown-item"
                            onclick="chOpenSettingsModal(); chCloseProfileMenu();">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <circle cx="12" cy="12" r="3" />
                                <path
                                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                            </svg>
                            Settings
                        </a>
                        <div class="ch-dropdown-divider"></div>
                        <a href="<?php echo esc_url(ch_get_logout_url($feed_url_back)); ?>"
                            class="ch-dropdown-item ch-dropdown-item-danger">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
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

    $user_id = get_current_user_id();
    $sort = sanitize_text_field($_GET['sort'] ?? 'new');
    $cat_slug = sanitize_text_field($_GET['cat'] ?? '');
    $search = sanitize_text_field($_GET['s'] ?? '');
    $location = sanitize_text_field($_GET['location'] ?? '');
    $bookmarks = isset($_GET['bookmarks']) ? 1 : 0;
    $page = max(1, (int) ($_GET['paged'] ?? 1));
    $per_page = 15;
    $offset = ($page - 1) * $per_page;

    $cat_filter = '';
    $cat_obj = null;
    if ($cat_slug) {
        $cat_obj = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ch_categories
             WHERE slug = %s AND (status = 'active' OR status = '' OR status IS NULL)",
            $cat_slug
        ));
        if ($cat_obj)
            $cat_filter = " AND p.category_id = {$cat_obj->id}";
    }

    $location_filter = '';
    if ($location) {
        $location_filter = $wpdb->prepare(" AND up.location = %s", $location);
    }

    $followed = [];
    if ($user_id) {
        $fids = $wpdb->get_col($wpdb->prepare("SELECT category_id FROM {$wpdb->prefix}ch_follows WHERE user_id = %d", $user_id));
        $followed = array_flip($fids);
    }

    $sidebar_category_limit = 8;
    $categories_safe = is_array($categories) ? $categories : [];
    $followed_safe = is_array($followed) ? $followed : [];
    $followed_sidebar_category_ids = array_slice(array_keys($followed_safe), 0, $sidebar_category_limit);
    $has_more_categories = count($categories_safe) > $sidebar_category_limit;
    $has_more_followed = count($followed_safe) > $sidebar_category_limit;

    $bookmark_filter = '';
    $user_bookmarks = [];
    if ($user_id) {
        $bms = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}ch_bookmarks WHERE user_id = %d", $user_id));
        $user_bookmarks = array_flip($bms);
        if ($bookmarks) {
            $bookmark_ids = array_keys($user_bookmarks);
            if (empty($bookmark_ids)) {
                $bookmark_filter = " AND 1 = 0";
            } else {
                $placeholders = implode(',', array_fill(0, count($bookmark_ids), '%d'));
                $bookmark_filter = $wpdb->prepare(" AND p.id IN ($placeholders)", ...$bookmark_ids);
            }
        }
    }

    $search_filter = '';
    if ($search) {
        $search_like = '%' . $wpdb->esc_like($search) . '%';
        $search_filter = $wpdb->prepare(" AND (p.title LIKE %s OR p.content LIKE %s)", $search_like, $search_like);
    }

    $order_by = match ($sort) {
        'top' => "p.vote_count DESC",
        'trending' => "(p.vote_count + p.comment_count * 2) DESC",
        default => "p.created_at DESC",
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
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ch_posts p LEFT JOIN {$wpdb->prefix}ch_user_profiles up ON p.user_id = up.user_id $base_where");

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
    $cat_sort = sanitize_text_field($_GET['cat_sort'] ?? 'name');

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
    $available_locations = ch_get_available_locations();
    $current_profile = $user_id ? $wpdb->get_row($wpdb->prepare(
        "SELECT display_name, avatar_url, karma_points FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d",
        $user_id
    )) : null;
    $categories_by_id = [];
    foreach ($categories as $category_row) {
        $categories_by_id[(int) $category_row->id] = $category_row;
    }


    // Fetch current user's votes on the visible posts for active-up/active-down state
    $user_post_votes = [];
    if ($user_id && !empty($posts)) {
        $post_ids = array_map(fn($p) => (int) $p->id, $posts);
        $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
        $vote_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT target_id, value FROM {$wpdb->prefix}ch_votes
             WHERE user_id = %d AND target_type = 'post' AND target_id IN ($placeholders)",
            array_merge([$user_id], $post_ids)
        ));
        foreach ($vote_rows as $vr) {
            $user_post_votes[(int) $vr->target_id] = (int) $vr->value;
        }
    }

    $nonce = wp_create_nonce('ch_feed_nonce');

    ob_start();
    ?>
    <script>
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var chFeedUrl = '<?php echo esc_js($feed_url); ?>';
        window.chFeedState = {
            nonce: '<?php echo esc_js($nonce); ?>',
            sort: '<?php echo esc_js($sort); ?>',
            cat: '<?php echo esc_js($cat_slug); ?>',
            s: '<?php echo esc_js($search); ?>',
            location: '<?php echo esc_js($location); ?>',
            paged: <?php echo (int) $page; ?>,
            bookmarks: <?php echo (int) $bookmarks; ?>
        };
    </script>
    <div class="ch-feed-shell">
        <nav class="ch-top-nav">
            <?php if ($user_id): ?>
                <?php $current_display = wp_get_current_user()->display_name ?: 'U'; ?>
            <?php endif; ?>
                        <div class="ch-top-nav-logo">
            <button class="ch-burger-menu-btn" type="button" aria-label="Toggle menu" aria-expanded="false"
                onclick="chToggleMobileMenu(this, '#ch-feed-drawer');">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M3 12h18M3 6h18M3 18h18" />
                </svg>
            </button>

                <img src="<?php echo esc_url(bntm_ch_logo_url()); ?>" alt="CivicHub Logo" class="ch-brand-logo">
            </div>
            <div id="ch-feed-drawer" class="ch-mobile-drawer-wrap">
                <button class="ch-top-drawer-close" type="button" onclick="chCloseAllMobileMenus()"
                    aria-label="Close menu">&times;</button>
                <div class="ch-nav-links">
                    <?php if ($user_id): ?>
                        <a href="?tab=my_feed" class="ch-nav-link <?php echo ($tab === 'my_feed') ? 'active' : ''; ?>">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            <span class="ch-nav-label">My Feed</span>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo get_permalink(); ?>"
                        class="ch-nav-link <?php echo !$bookmarks && $sort === 'new' && $tab === '' && !$cat_slug && !$search ? 'active' : ''; ?>">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="ch-nav-label">Home</span>
                    </a>
                    <a href="?sort=trending"
                        class="ch-nav-link <?php echo $sort === 'trending' && $tab === '' && !$bookmarks ? 'active' : ''; ?>">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                        </svg>
                        <span class="ch-nav-label">Trending</span>
                    </a>
                    <?php if ($user_id): ?>
                        <a href="?bookmarks=1" class="ch-nav-link <?php echo $bookmarks && $tab === '' ? 'active' : ''; ?>">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z" />
                            </svg>
                            <span class="ch-nav-label">Bookmarks</span>
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ($user_id): ?>
                    <div class="ch-user-bar ch-user-bar-mobile-profile">
                        <button type="button" class="ch-mobile-profile-trigger" id="ch-mobile-profile-btn"
                            onclick="chToggleMobileProfileMenu(event)">
                            <div class="ch-avatar-btn" data-ch-current-user-avatar="1"
                                data-avatar-name="<?php echo esc_attr($current_display); ?>">
                                <?php echo ch_render_avatar($current_display, $current_profile->avatar_url ?? '', 'ch-avatar-btn-inner', 'ch-current-user-avatar-img'); ?>
                            </div>
                            <span class="ch-mobile-profile-label">Profile</span>
                        </button>
                        <div class="ch-mobile-profile-menu" id="ch-mobile-profile-menu" style="display:none;">
                            <a href="?tab=profile" class="ch-dropdown-item" onclick="chCloseAllMobileMenus()">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                My Profile
                            </a>
                            <a href="javascript:void(0)" class="ch-dropdown-item"
                                onclick="chOpenSettingsModal(); chCloseMobileProfileMenu(); chCloseAllMobileMenus();">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <circle cx="12" cy="12" r="3" />
                                    <path
                                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                </svg>
                                Settings
                            </a>
                            <a href="<?php echo esc_url(ch_get_logout_url(get_permalink())); ?>" class="ch-dropdown-item ch-dropdown-item-danger"
                                onclick="chCloseAllMobileMenus()">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                                Sign Out
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="ch-user-bar">
                        <?php
                        $auth_url = ch_get_auth_url('login');
                        $reg_url = ch_get_auth_url('register');
                        ?>
                        <a href="<?php echo esc_url($auth_url); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">Sign In</a>
                        <a href="<?php echo esc_url($reg_url); ?>" class="ch-btn ch-btn-primary ch-btn-sm">Join</a>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($user_id): ?>
                <div class="ch-user-bar ch-nav-user-desktop">
                    <div class="ch-notifications-dropdown ch-top-nav-notifications">
                        <button class="ch-icon-action-btn ch-notifications-btn" id="ch-notif-btn" onclick="chToggleNotifications(event)"
                            aria-label="Notifications">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg>
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
                        <button class="ch-avatar-btn" id="ch-profile-btn" onclick="chToggleProfileMenu(event)"
                            aria-label="Profile" data-ch-current-user-avatar="1"
                            data-avatar-name="<?php echo esc_attr($current_display); ?>">
                            <?php echo ch_render_avatar($current_display, $current_profile->avatar_url ?? '', 'ch-avatar-btn-inner', 'ch-current-user-avatar-img'); ?>
                        </button>
                        <div class="ch-dropdown-panel ch-dropdown-panel-sm" id="ch-profile-menu" style="display:none;">
                            <div class="ch-dropdown-user-info">
                                <div class="ch-avatar-btn ch-avatar-btn-lg" data-ch-current-user-avatar="1"
                                    data-avatar-name="<?php echo esc_attr($current_display); ?>">
                                    <?php echo ch_render_avatar($current_display, $current_profile->avatar_url ?? '', 'ch-avatar-btn-inner ch-avatar-btn-inner-lg', 'ch-current-user-avatar-img'); ?>
                                </div>
                                <div>
                                    <div class="ch-dropdown-username"><?php echo esc_html($current_display); ?></div>
                                    <div class="ch-dropdown-usermeta">Community Member</div>
                                </div>
                            </div>
                            <div class="ch-dropdown-divider"></div>
                            <a href="javascript:void(0)" class="ch-dropdown-item"
                                onclick="chOpenSettingsModal(); chCloseProfileMenu();">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <circle cx="12" cy="12" r="3" />
                                    <path
                                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                </svg>
                                Settings
                            </a>
                            <div class="ch-dropdown-divider"></div>
                            <a href="<?php echo esc_url(ch_get_logout_url(get_permalink())); ?>"
                                class="ch-dropdown-item ch-dropdown-item-danger">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                                Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </nav>

        <div class="ch-feed-loading-overlay" aria-hidden="true">
            <div class="ch-feed-loading-inner">
                <div class="ch-feed-loading-spinner" aria-hidden="true"></div>
                <div class="ch-feed-loading-copy">Loading feed…</div>
            </div>
        </div>

        <?php if ($tab === 'my_feed'): ?>
            <div class="ch-mf-page-wrap">
                <?php echo bntm_shortcode_ch_my_feed(); ?>
            </div>
        <?php elseif ($sort === 'trending' && !$bookmarks && $tab === ''): ?>
            <!-- ════════════════════════════════════════════════════ -->
            <!--  TRENDING PAGE                                       -->
            <!-- ════════════════════════════════════════════════════ -->
            <?php
            $trending_privacy_filter = " AND (c.is_private = 0 OR c.is_private IS NULL)";
            if ($user_id && !current_user_can('manage_options')) {
                $followed_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT category_id FROM {$wpdb->prefix}ch_follows WHERE user_id = %d",
                    $user_id
                ));
                $followed_ids = array_map('intval', (array) $followed_ids);
                if (!empty($followed_ids)) {
                    $trending_privacy_filter = " AND (c.is_private = 0 OR c.is_private IS NULL OR p.category_id IN (" . implode(',', $followed_ids) . "))";
                }
            } elseif ($user_id && current_user_can('manage_options')) {
                $trending_privacy_filter = '';
            }

            $trending_posts = $wpdb->get_results(
                "SELECT p.*, c.name as cat_name, c.color as cat_color,
                COALESCE(u.display_name, 'Community Member') as author_name
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
         WHERE p.status = 'active'
           AND (c.status = 'active' OR c.status = '' OR c.status IS NULL)
           {$trending_privacy_filter}
         ORDER BY (COALESCE(p.vote_count, 0) * 2 + COALESCE(p.comment_count, 0) * 3 + COALESCE(p.view_count, 0)) DESC,
                  p.created_at DESC
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
                        <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="ch-special-hero-title">Trending</h1>
                        <p class="ch-special-hero-sub">The hottest discussions in your community right now</p>
                    </div>
                </div>

                <div class="ch-special-layout">
                    <!-- Main trending posts -->
                    <main id="ch-posts-list" class="ch-special-main">
                        <?php if (empty($trending_posts)): ?>
                            <div class="ch-empty-state">
                                <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" stroke-width="1.5">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                                </svg>
                                <p>No trending posts yet. Be the first to spark a discussion!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($trending_posts as $i => $post): ?>
                                <article class="ch-trending-post-card">
                                    <div class="ch-trending-rank"><?php echo $i + 1; ?></div>
                                    <div class="ch-trending-body">
                                        <div class="ch-post-meta-row" style="margin-bottom:6px;">
                                            <span class="ch-cat-badge"
                                                style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                                                <?php echo esc_html($post->cat_name ?? 'General'); ?>
                                            </span>
                                            <span
                                                class="ch-post-author"><?php echo $post->is_anonymous ? 'Anonymous' : esc_html($post->author_name); ?></span>
                                            <span
                                                class="ch-post-time"><?php echo human_time_diff(strtotime($post->created_at), current_time('timestamp')); ?>
                                                ago</span>
                                        </div>
                                        <a href="?view_post=<?php echo esc_attr($post->rand_id); ?>" class="ch-trending-post-title">
                                            <?php echo esc_html($post->title); ?>
                                        </a>
                                        <p class="ch-trending-post-excerpt">
                                            <?php echo esc_html(wp_trim_words(strip_tags($post->content), 20)); ?></p>
                                        <div class="ch-trending-stats">
                                            <span class="ch-trending-stat">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2">
                                                    <polyline points="18 15 12 9 6 15" />
                                                </svg>
                                                <?php echo (int) $post->vote_count; ?> votes
                                            </span>
                                            <span class="ch-trending-stat">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2">
                                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                                </svg>
                                                <?php echo (int) $post->comment_count; ?> comments
                                            </span>
                                            <span class="ch-trending-stat">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                                <?php echo (int) $post->view_count; ?> views
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </main>

                    <!-- Trending sidebar -->
                    <aside class="ch-special-sidebar">
                        <!-- Mobile Drawer Close -->
                        <button class="ch-drawer-close" onclick="document.body.classList.remove('ch-drawer-open')"
                            aria-label="Close Menu"
                            style="display:none;position:absolute;top:16px;right:16px;background:none;border:none;font-size:24px;cursor:pointer;color:var(--ch-text-muted);">&times;</button>

                        <div class="ch-sidebar-widget">
                            <h4>🔥 Hot Categories</h4>
                            <?php foreach ($trending_cats as $tcat): ?>
                                <a href="?cat=<?php echo esc_attr($tcat->slug); ?>" class="ch-cat-link"
                                    style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                                    <span style="display:flex;align-items:center;gap:6px;">
                                        <span class="ch-cat-dot" style="background:<?php echo esc_attr($tcat->color); ?>"></span>
                                        <?php echo esc_html($tcat->name); ?>
                                    </span>
                                    <span class="ch-cat-count"><?php echo (int) $tcat->post_count; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($user_id): ?>
                            <div class="ch-sidebar-widget">
                                <h4>Quick Post</h4>
                                <button class="ch-btn ch-btn-primary ch-btn-full" onclick="chOpenModal('ch-modal-create-post')">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
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
                if ($bp->cat_name && !isset($bm_cats[$bp->cat_name]))
                    $bm_cats[$bp->cat_name] = ['color' => $bp->cat_color, 'slug' => $bp->cat_name, 'count' => 0];
                if ($bp->cat_name)
                    $bm_cats[$bp->cat_name]['count']++;
            }
            ?>
            <div class="ch-special-page-wrap">
                <div class="ch-special-hero ch-bookmarks-hero">
                    <div class="ch-special-hero-icon">
                        <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="ch-special-hero-title">My Bookmarks</h1>
                        <p class="ch-special-hero-sub"><?php echo count($bookmarked_posts); ?> saved
                            post<?php echo count($bookmarked_posts) !== 1 ? 's' : ''; ?></p>
                    </div>
                </div>

                <div class="ch-special-layout">
                    <main id="ch-posts-list" class="ch-special-main">
                        <?php if (empty($bookmarked_posts)): ?>
                            <div class="ch-empty-state">
                                <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 0-2 2h10a2 2 0 0 1 2 2z" />
                                </svg>
                                <p>No bookmarks yet. Save posts you want to come back to!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($bookmarked_posts as $post): ?>
                                <article class="ch-post-card" data-id="<?php echo (int) $post->id; ?>"
                                    data-category="<?php echo $post->category_id; ?>"
                                    data-anonymous="<?php echo $post->is_anonymous; ?>">
                                    <div class="ch-post-vote-col">
                                        <?php $uv = $user_post_votes[$post->id] ?? 0; ?>
                                        <button class="ch-vote-btn ch-vote-up <?php echo $uv === 1 ? 'active-up' : ''; ?>"
                                            data-id="<?php echo (int) $post->id; ?>" data-type="post" data-val="1"
                                            onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                stroke-width="2.5">
                                                <polyline points="18 15 12 9 6 15" />
                                            </svg>
                                        </button>
                                        <span class="ch-vote-count"><?php echo (int) $post->vote_count; ?></span>
                                        <button class="ch-vote-btn ch-vote-down <?php echo $uv === -1 ? 'active-down' : ''; ?>"
                                            data-id="<?php echo (int) $post->id; ?>" data-type="post" data-val="-1"
                                            onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                stroke-width="2.5">
                                                <polyline points="6 9 12 15 18 9" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="ch-post-body">
                                        <div class="ch-post-meta-row">
                                            <span class="ch-cat-badge"
                                                style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                                                <?php echo esc_html($post->cat_name ?? 'General'); ?>
                                            </span>
                                            <span
                                                class="ch-post-author"><?php echo $post->is_anonymous ? 'Anonymous' : esc_html($post->author_name); ?></span>
                                            <span
                                                class="ch-post-time"><?php echo human_time_diff(strtotime($post->created_at), current_time('timestamp')); ?>
                                                ago</span>
                                            <span style="font-size:11px;color:var(--ch-text-subtle);margin-left:4px;">
                                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2" style="vertical-align:-1px">
                                                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                                </svg>
                                                Saved
                                                <?php echo human_time_diff(strtotime($post->bookmarked_at), current_time('timestamp')); ?>
                                                ago
                                            </span>
                                        </div>
                                        <a href="?view_post=<?php echo esc_attr($post->rand_id); ?>" class="ch-post-title-link">
                                            <h3 class="ch-post-title"><?php echo esc_html($post->title); ?></h3>
                                        </a>
                                        <?php echo ch_render_post_media_preview($post->media_urls ?? '', 'feed'); ?>
                                        <p class="ch-post-excerpt">
                                            <?php echo esc_html(wp_trim_words(strip_tags($post->content), 25)); ?></p>
                                        <div class="ch-post-actions-row">
                                            <span class="ch-post-stat">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2">
                                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                                </svg>
                                                <?php echo (int) $post->comment_count; ?>
                                            </span>
                                            <button
                                                class="ch-post-action ch-bookmark-btn <?php echo isset($user_bookmarks[$post->id]) ? 'ch-bookmarked' : ''; ?>"
                                                data-post-id="<?php echo (int) $post->id; ?>"
                                                onclick="chToggleBookmark(this, '<?php echo esc_attr($nonce); ?>')">
                                                <svg width="14" height="14"
                                                    fill="<?php echo isset($user_bookmarks[$post->id]) ? 'currentColor' : 'none'; ?>"
                                                    stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                                </svg>
                                                <?php echo isset($user_bookmarks[$post->id]) ? 'Saved' : 'Save'; ?>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; endif; ?>
                    </main>

                    <aside class="ch-special-sidebar">
                        <!-- Mobile Drawer Close -->
                        <button class="ch-drawer-close" onclick="document.body.classList.remove('ch-drawer-open')"
                            aria-label="Close Menu"
                            style="display:none;position:absolute;top:16px;right:16px;background:none;border:none;font-size:24px;cursor:pointer;color:var(--ch-text-muted);">&times;</button>

                        <?php if (!empty($bm_cats)): ?>
                            <div class="ch-sidebar-widget">
                                <h4>Saved by Category</h4>
                                <?php foreach ($bm_cats as $cname => $cdata): ?>
                                    <div class="ch-cat-link"
                                        style="display:flex;align-items:center;justify-content:space-between;cursor:default;">
                                        <span style="display:flex;align-items:center;gap:6px;">
                                            <span class="ch-cat-dot"
                                                style="background:<?php echo esc_attr($cdata['color'] ?? '#FF7551'); ?>"></span>
                                            <?php echo esc_html($cname); ?>
                                        </span>
                                        <span class="ch-cat-count"><?php echo (int) $cdata['count']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="ch-sidebar-widget">
                            <h4>Browse Forum</h4>
                            <a href="<?php echo esc_url(get_permalink()); ?>"
                                class="ch-btn ch-btn-secondary ch-btn-full ch-btn-sm"
                                style="text-align:center;text-decoration:none;">
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
                    <!-- Mobile Drawer Close -->
                    <button class="ch-drawer-close" onclick="document.body.classList.remove('ch-drawer-open')"
                        aria-label="Close Menu"
                        style="display:none;position:absolute;top:16px;right:16px;background:none;border:none;font-size:24px;cursor:pointer;color:var(--ch-text-muted);">&times;</button>

                    <div class="ch-sidebar-widget">
                        <h4>Categories</h4>
                        <form method="get" class="ch-categories-filter-form ch-categories-filter-sidebar"
                            style="margin-bottom:12px;">
                            <input type="hidden" name="cat" value="<?php echo esc_attr($cat_slug); ?>">
                            <div class="ch-field-group">
                                <input type="text" name="cat_search" class="ch-input" placeholder="Search categories"
                                    value="<?php echo esc_attr($cat_search); ?>">
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

                        <a href="<?php echo get_permalink(); ?>" class="ch-cat-link <?php echo !$cat_slug ? 'active' : ''; ?>"
                            data-cat-slug="">
                            All Topics
                        </a>
                        <?php $shown_category_count = 0; ?>
                        <?php foreach ($categories_safe as $cat):
                            if ($shown_category_count >= $sidebar_category_limit) {
                                break;
                            }
                            if ($cat->is_private) {
                                if (!$user_id)
                                    continue;
                                if (!current_user_can('manage_options') && !isset($followed[$cat->id]))
                                    continue;
                            }
                            ?>
                            <div class="ch-cat-item"
                                style="display:flex; align-items:center; justify-content:space-between; gap:4px;">
                                <a href="?cat=<?php echo esc_attr($cat->slug); ?>"
                                    class="ch-cat-link <?php echo $cat_slug === $cat->slug ? 'active' : ''; ?>"
                                    style="flex:1;min-width:0;" data-cat-slug="<?php echo esc_attr($cat->slug); ?>">
                                    <span class="ch-cat-dot" style="background:<?php echo esc_attr($cat->color); ?>"></span>
                                    <span class="ch-cat-name"><?php echo esc_html($cat->name); ?></span>
                                    <?php if ($cat->is_private): ?>
                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2" style="opacity:.5;margin-left:2px;flex-shrink:0" title="Private">
                                            <rect x="3" y="11" width="18" height="11" rx="2" />
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                        </svg>
                                    <?php endif; ?>
                                    <span class="ch-cat-count"><?php echo $cat->post_count; ?></span>
                                </a>

                            </div>
                            <?php $shown_category_count++; ?>
                        <?php endforeach; ?>

                        <?php if ($has_more_categories || $has_more_followed): ?>
                            <div style="margin-top:10px;">
                                <button type="button" class="ch-btn ch-btn-outline ch-btn-full ch-btn-sm"
                                    onclick="chOpenModal('ch-modal-category-browser')"
                                    style="font-size:13px;">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.4">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 8v4l3 3" />
                                    </svg>
                                    View all categories
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php
                        $cat_creation_on = get_option('ch_user_category_creation', 0);
                        $cat_karma_threshold = (int) get_option('ch_category_creation_karma', 100);
                        $user_karma_pts = (int) ($current_profile->karma_points ?? 0);
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
                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
                                    New Category
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($user_id): ?>
                            <div id="ch-followed-categories" class="ch-followed-categories"
                                style="margin-top: 18px; padding-top: 12px; border-top: 1px solid var(--ch-border);<?php echo empty($followed) ? 'display:none;' : ''; ?>">
                                <h5
                                    style="margin: 0 0 8px; font-size: 10.5px; font-weight: 700; color: var(--ch-text-subtle); text-transform: uppercase; letter-spacing: 0.8px;">
                                    Following</h5>
                                <div id="ch-followed-categories-list">
                                    <?php foreach ($followed_sidebar_category_ids as $cat_id): ?>
                                        <?php $cat = $categories_by_id[(int) $cat_id] ?? null; ?>
                                        <?php if ($cat): ?>
                                            <a href="?cat=<?php echo esc_attr($cat->slug); ?>"
                                                class="ch-cat-link <?php echo $cat_slug === $cat->slug ? 'active' : ''; ?>"
                                                style="font-size: 14px;" data-followed-cat-id="<?php echo (int) $cat->id; ?>"
                                                data-cat-slug="<?php echo esc_attr($cat->slug); ?>">
                                                <span class="ch-cat-dot" style="background:<?php echo esc_attr($cat->color); ?>"></span>
                                                <span class="ch-cat-name"><?php echo esc_html($cat->name); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="ch-sidebar-widget">
                        <h4>Quick Post</h4>
                        <?php if ($cat_obj && $cat_obj->is_private && !$user_id): ?>
                            <p style="font-size:12px;color:#9ca3af;display:flex;align-items:center;gap:6px;">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                Sign in and follow to post here.
                            </p>
                        <?php else: ?>
                            <button class="ch-btn ch-btn-primary ch-btn-full" onclick="chOpenModal('ch-modal-create-post')">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2.5">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                                <?php echo $user_id ? 'New Post' : 'Post as Guest'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </aside>

                <?php if ($has_more_categories || $has_more_followed): ?>
                    <div id="ch-modal-category-browser" class="ch-modal-overlay" style="display:none;">
                        <div class="ch-modal ch-modal-lg">
                            <div class="ch-modal-header">
                                <h3>Browse Categories</h3>
                                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-category-browser')">&times;</button>
                            </div>
                            <div class="ch-modal-body">
                                <div class="ch-category-browser-grid">
                                    <div class="ch-category-browser-section">
                                        <h4>All Categories</h4>
                                        <div class="ch-category-browser-list">
                                            <?php
                                            $browser_count = 0;
                                            foreach ($categories as $cat):
                                                if ($cat->is_private) {
                                                    if (!$user_id)
                                                        continue;
                                                    if (!current_user_can('manage_options') && !isset($followed_safe[$cat->id]))
                                                        continue;
                                                }
                                                $browser_count++;
                                                ?>
                                                <a href="?cat=<?php echo esc_attr($cat->slug); ?>"
                                                    class="ch-cat-link <?php echo $cat_slug === $cat->slug ? 'active' : ''; ?>"
                                                    data-cat-slug="<?php echo esc_attr($cat->slug); ?>">
                                                    <span class="ch-cat-dot" style="background:<?php echo esc_attr($cat->color); ?>"></span>
                                                    <span class="ch-cat-name"><?php echo esc_html($cat->name); ?></span>
                                                    <?php if ($cat->is_private): ?>
                                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                            stroke-width="2" style="opacity:.5;margin-left:2px;flex-shrink:0" title="Private">
                                                            <rect x="3" y="11" width="18" height="11" rx="2" />
                                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                                        </svg>
                                                    <?php endif; ?>
                                                    <span class="ch-cat-count"><?php echo (int) $cat->post_count; ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                            <?php if ($browser_count === 0): ?>
                                                <div class="ch-empty-state" style="padding:14px 10px;">No categories available.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($user_id): ?>
                                        <div class="ch-category-browser-section">
                                            <h4>Following</h4>
                                            <div class="ch-category-browser-list">
                                                <?php if (!empty($followed_safe)): ?>
                                                    <?php foreach ($followed_safe as $cat_id => $dummy): ?>
                                                        <?php $cat = $categories_by_id[(int) $cat_id] ?? null; ?>
                                                        <?php if ($cat): ?>
                                                            <a href="?cat=<?php echo esc_attr($cat->slug); ?>"
                                                                class="ch-cat-link <?php echo $cat_slug === $cat->slug ? 'active' : ''; ?>"
                                                                data-followed-cat-id="<?php echo (int) $cat->id; ?>"
                                                                data-cat-slug="<?php echo esc_attr($cat->slug); ?>">
                                                                <span class="ch-cat-dot" style="background:<?php echo esc_attr($cat->color); ?>"></span>
                                                                <span class="ch-cat-name"><?php echo esc_html($cat->name); ?></span>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="ch-empty-state" style="padding:14px 10px;">You are not following any categories yet.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Main Feed -->
                <main class="ch-feed-main">
                    <div class="ch-feed-header">
                        <div id="ch-feed-header-inner">
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
                                            <span class="ch-cat-meta-num ch-cat-followers"
                                                data-cat-id="<?php echo (int) $cat_obj->id; ?>"><?php echo number_format($cat_obj->follower_count); ?></span>
                                            <span class="ch-cat-meta-label">followers</span>
                                        </div>
                                        <?php if ($user_id): ?>
                                            <?php $is_following = isset($followed[$cat_obj->id]); ?>
                                            <button
                                                class="ch-btn ch-btn-sm ch-follow-toggle <?php echo $is_following ? 'ch-btn-outline' : 'ch-btn-primary'; ?>"
                                                data-follow-cat-id="<?php echo (int) $cat_obj->id; ?>"
                                                data-follow-cat-slug="<?php echo esc_attr($cat_obj->slug); ?>"
                                                data-follow-cat-name="<?php echo esc_attr($cat_obj->name); ?>"
                                                data-follow-cat-color="<?php echo esc_attr($cat_obj->color); ?>"
                                                onclick="chToggleFollowCategory(<?php echo (int) $cat_obj->id; ?>, this, '<?php echo esc_attr($nonce); ?>')">
                                                <?php echo $is_following ? 'Following' : 'Follow'; ?>
                                            </button>
                                            <button class="ch-btn ch-btn-sm ch-btn-primary"
                                                onclick="chOpenPostInCategory(<?php echo (int) $cat_obj->id; ?>)">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2.5">
                                                    <line x1="12" y1="5" x2="12" y2="19" />
                                                    <line x1="5" y1="12" x2="19" y2="12" />
                                                </svg>
                                                New Post
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($user_id && ($cat_obj->business_id == $user_id || current_user_can('manage_options'))): ?>
                                            <button class="ch-btn ch-btn-sm ch-btn-outline" title="Edit category"
                                                onclick="chFeedOpenEditCat(<?php echo (int) $cat_obj->id; ?>, '<?php echo esc_js($cat_obj->name); ?>', '<?php echo esc_js($cat_obj->description ?? ''); ?>', '<?php echo esc_attr($cat_obj->color); ?>', <?php echo (int) $cat_obj->is_private; ?>, <?php echo (int) $cat_obj->require_post_approval; ?>)">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                </svg>
                                                Edit
                                            </button>
                                            <button class="ch-btn ch-btn-sm" style="color:#ef4444;border-color:#fca5a5;"
                                                title="Delete category"
                                                onclick="chFeedDeleteCat(<?php echo (int) $cat_obj->id; ?>, '<?php echo esc_js($cat_obj->name); ?>', '<?php echo esc_attr(wp_create_nonce('ch_category_nonce')); ?>')">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6" />
                                                    <path
                                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                </svg>
                                                Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <h2>Community Forum</h2>
                            <?php endif; ?>
                        </div><!-- /ch-feed-header-inner -->

                        <div class="ch-feed-toolbar-card">
                            <form method="get" class="ch-search-form" id="ch-feed-search-form" onsubmit="return false;">
                                <?php if ($cat_slug): ?><input type="hidden" name="cat"
                                        value="<?php echo esc_attr($cat_slug); ?>"><?php endif; ?>
                                <input type="text" name="s" value="<?php echo esc_attr($search); ?>"
                                    placeholder="Search discussions..." class="ch-input ch-search-input">
                                <button type="submit" class="ch-btn ch-btn-primary" style="flex-shrink:0;">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <circle cx="11" cy="11" r="8" />
                                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                    </svg>
                                    Search
                                </button>
                            </form>
                            <div class="ch-filter-row">
                                <form method="get" class="ch-location-form" id="ch-feed-location-form" onsubmit="return false;">
                                    <?php if ($cat_slug): ?><input type="hidden" name="cat"
                                            value="<?php echo esc_attr($cat_slug); ?>"><?php endif; ?>
                                    <?php if ($search): ?><input type="hidden" name="s"
                                            value="<?php echo esc_attr($search); ?>"><?php endif; ?>
                                    <select name="location" class="ch-location-select">
                                        <option value="">All Locations</option>
                                        <?php foreach ($available_locations as $loc): ?>
                                            <option value="<?php echo esc_attr($loc); ?>" <?php selected($location, $loc); ?>>
                                                <?php echo esc_html($loc); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <div class="ch-sort-tabs" id="ch-sort-tabs">
                                    <button type="button" class="ch-sort-tab <?php echo $sort === 'new' ? 'active' : ''; ?>"
                                        data-sort="new">New</button>
                                    <button type="button" class="ch-sort-tab <?php echo $sort === 'top' ? 'active' : ''; ?>"
                                        data-sort="top">Top</button>
                                    <button type="button" class="ch-sort-tab <?php echo $sort === 'trending' ? 'active' : ''; ?>"
                                        data-sort="trending">Trending</button>
                                </div>
                                <a href="#" class="ch-guidelines-link" onclick="chShowGuidelines(); return false;"
                                    style="margin-left:auto;">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
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
                 ORDER BY post_count DESC LIMIT 3"
                    );
                    if (!empty($popular_cats) && !$cat_slug && !$search && $page === 1): ?>
                        <div class="ch-popular-cats-card">
                            <div class="ch-popular-cats-header">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <polygon
                                        points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                </svg>
                                Popular Categories
                            </div>
                            <div class="ch-popular-cats-grid">
                                <?php foreach ($popular_cats as $pcat): ?>
                                    <a href="?cat=<?php echo esc_attr($pcat->slug); ?>" class="ch-pop-cat-chip"
                                        data-cat-slug="<?php echo esc_attr($pcat->slug); ?>">
                                        <span class="ch-pop-cat-dot" style="background:<?php echo esc_attr($pcat->color); ?>"></span>
                                        <span class="ch-pop-cat-name"><?php echo esc_html($pcat->name); ?></span>
                                        <span class="ch-pop-cat-count"><?php echo (int) $pcat->post_count; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- ── Facebook-style Composer Trigger ── -->
                    <?php if ($user_id || true): // show for both guests and logged-in ?>
                        <?php
                        $can_post_here = true;
                        if ($cat_obj && $cat_obj->is_private && !$user_id)
                            $can_post_here = false;
                        $current_display_feed = $user_id ? (wp_get_current_user()->display_name ?: 'You') : 'Guest';
                        $current_initial_feed = strtoupper(substr($current_display_feed, 0, 1));
                        ?>
                        <div class="ch-composer-trigger-card"
                            onclick="<?php echo $can_post_here ? "chOpenModal('ch-modal-create-post')" : "void(0)"; ?>"
                            role="button" tabindex="0"
                            onkeydown="if(event.key==='Enter'||event.key===' '){<?php echo $can_post_here ? "chOpenModal('ch-modal-create-post')" : ''; ?>}">
                            <div class="ch-composer-trigger-avatar <?php echo !$user_id ? 'ch-composer-trigger-guest' : ''; ?>"
                                <?php echo $user_id ? 'data-ch-current-user-avatar="1" data-avatar-name="' . esc_attr($current_display_feed) . '"' : ''; ?>>
                                <?php if ($user_id): ?>
                                    <?php echo ch_render_avatar($current_display_feed, $current_profile->avatar_url ?? '', 'ch-composer-trigger-avatar-inner', 'ch-current-user-avatar-img'); ?>
                                <?php else: ?>
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
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
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" />
                                            <circle cx="8.5" cy="8.5" r="1.5" />
                                            <polyline points="21 15 16 10 5 21" />
                                        </svg>
                                        Photo
                                    </span>
                                    <span class="ch-composer-trigger-btn ch-composer-trigger-btn-tag">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z" />
                                            <line x1="7" y1="7" x2="7.01" y2="7" />
                                        </svg>
                                        Tag
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="ch-composer-trigger-input ch-composer-trigger-locked">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
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
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                stroke-width="2">
                                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                                            </svg>
                                        </div>
                                        <div class="ch-announcement-meta">
                                            <span class="ch-announcement-label">Community Announcement</span>
                                            <span class="ch-announcement-author">by
                                                <?php echo esc_html($announcement->admin_name ?? 'Admin'); ?></span>
                                            <span
                                                class="ch-announcement-time"><?php echo human_time_diff(strtotime($announcement->created_at), current_time('timestamp')); ?>
                                                ago</span>
                                        </div>
                                    </div>
                                    <div class="ch-announcement-body">
                                        <h4 class="ch-announcement-title"><?php echo esc_html($announcement->title); ?></h4>
                                        <div class="ch-announcement-content">
                                            <?php echo wp_kses_post(wpautop($announcement->content)); ?></div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (empty($posts)): ?>
                            <div class="ch-empty-state">
                                <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                </svg>
                                <p>No posts yet. Be the first to start a discussion!</p>
                            </div>
                        <?php else:
                            foreach ($posts as $post): ?>
                                <article class="ch-post-card" data-id="<?php echo (int) $post->id; ?>"
                                    data-category="<?php echo $post->category_id; ?>"
                                    data-anonymous="<?php echo $post->is_anonymous; ?>">
                                    <?php if ($post->is_pinned): ?>
                                        <div class="ch-post-pinned-ribbon">
                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                                            </svg>
                                            Pinned
                                        </div>
                                    <?php endif; ?>
                                    <div class="ch-post-vote-col">
                                        <?php if ($user_id):
                                            $uv = $user_post_votes[$post->id] ?? 0; ?>
                                            <button class="ch-vote-btn ch-vote-up <?php echo $uv === 1 ? 'active-up' : ''; ?>"
                                                data-id="<?php echo (int) $post->id; ?>" data-type="post" data-val="1"
                                                onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2.5">
                                                    <polyline points="18 15 12 9 6 15" />
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                        <span class="ch-vote-count"><?php echo (int) $post->vote_count; ?></span>
                                        <?php if ($user_id): ?>
                                            <button class="ch-vote-btn ch-vote-down <?php echo $uv === -1 ? 'active-down' : ''; ?>"
                                                data-id="<?php echo (int) $post->id; ?>" data-type="post" data-val="-1"
                                                onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2.5">
                                                    <polyline points="6 9 12 15 18 9" />
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ch-post-body">
                                        <div class="ch-post-meta-row">
                                            <span class="ch-cat-badge"
                                                style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
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
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                        stroke-width="2">
                                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                                        <circle cx="12" cy="10" r="3" />
                                                    </svg>
                                                    <?php echo esc_html($post->author_location); ?>
                                                </span>
                                            <?php endif; ?>
                                            <span
                                                class="ch-post-time"><?php echo human_time_diff(strtotime($post->created_at), current_time('timestamp')); ?>
                                                ago</span>
                                        </div>
                                        <h3 class="ch-post-title">
                                            <a href="?view_post=<?php echo $post->rand_id; ?>"><?php echo esc_html($post->title); ?></a>
                                        </h3>
                                        <?php echo ch_render_post_media_preview($post->media_urls ?? '', 'feed'); ?>
                                        <p class="ch-post-preview">
                                            <?php echo esc_html(wp_trim_words(strip_tags($post->content), 25)); ?></p>
                                        <?php if ($post->tags): ?>
                                            <div class="ch-post-tags" data-tags="<?php echo esc_attr($post->tags); ?>">
                                                <?php foreach (explode(',', $post->tags) as $tag): ?>
                                                    <span class="ch-tag">#<?php echo esc_html(trim($tag)); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="ch-post-actions-row">
                                            <a href="?view_post=<?php echo $post->rand_id; ?>" class="ch-post-action">
                                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    stroke-width="2">
                                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                                </svg>
                                                <?php echo (int) $post->comment_count; ?> comments
                                            </a>
                                            <?php if ($user_id): ?>
                                                <button
                                                    class="ch-post-action ch-bookmark-btn <?php echo isset($user_bookmarks[$post->id]) ? 'ch-bookmarked' : ''; ?>"
                                                    data-post-id="<?php echo (int) $post->id; ?>"
                                                    onclick="chBookmark(<?php echo (int) $post->id; ?>, this, '<?php echo esc_attr($nonce); ?>')">
                                                    <svg width="14" height="14"
                                                        fill="<?php echo isset($user_bookmarks[$post->id]) ? 'currentColor' : 'none'; ?>"
                                                        stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                                    </svg>
                                                    <?php echo isset($user_bookmarks[$post->id]) ? 'Saved' : 'Save'; ?>
                                                </button>
                                                <button class="ch-post-action"
                                                    onclick="chReportPost(<?php echo (int) $post->id; ?>, '<?php echo esc_attr($nonce); ?>')">
                                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                        stroke-width="2">
                                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
                                                        <line x1="4" y1="22" x2="4" y2="15" />
                                                    </svg>
                                                    Report
                                                </button>
                                                <?php if ($user_id && ($post->user_id !== 0 && $post->user_id == $user_id || current_user_can('manage_options'))): ?>
                                                    <button class="ch-post-action ch-post-action-edit"
                                                        onclick="chOpenEditPostModal(<?php echo (int) $post->id; ?>)">
                                                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                            stroke-width="2">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                        </svg>
                                                        Edit
                                                    </button>
                                                    <button class="ch-post-action ch-post-action-delete"
                                                        onclick="chDeletePost(<?php echo (int) $post->id; ?>, '<?php echo esc_attr(wp_create_nonce('ch_post_view_nonce')); ?>')">
                                                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                            stroke-width="2">
                                                            <polyline points="3 6 5 6 21 6" />
                                                            <path
                                                                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                        </svg>
                                                        Delete
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <div class="ch-share-dropdown">
                                                <button class="ch-post-action ch-share-btn" onclick="chToggleShareMenu(this)">
                                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                        stroke-width="2">
                                                        <circle cx="18" cy="5" r="3" />
                                                        <circle cx="6" cy="12" r="3" />
                                                        <circle cx="18" cy="19" r="3" />
                                                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                                                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                                                    </svg>
                                                    Share
                                                </button>
                                                <div class="ch-share-menu">
                                                    <button class="ch-share-option"
                                                        onclick="chShareToSocial('twitter', '<?php echo get_permalink(); ?>?view_post=<?php echo $post->rand_id; ?>', '<?php echo esc_attr($post->title); ?>')">
                                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                                        </svg>
                                                        Twitter
                                                    </button>
                                                    <button class="ch-share-option"
                                                        onclick="chShareToSocial('facebook', '<?php echo get_permalink(); ?>?view_post=<?php echo $post->rand_id; ?>', '<?php echo esc_attr($post->title); ?>')">
                                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                                        </svg>
                                                        Facebook
                                                    </button>
                                                    <button class="ch-share-option"
                                                        onclick="chShareToSocial('linkedin', '<?php echo get_permalink(); ?>?view_post=<?php echo $post->rand_id; ?>', '<?php echo esc_attr($post->title); ?>')">
                                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                                        </svg>
                                                        LinkedIn
                                                    </button>
                                                    <button class="ch-share-option"
                                                        onclick="chShareToSocial('copy', '<?php echo get_permalink(); ?>?view_post=<?php echo $post->rand_id; ?>')">
                                                        <svg width="16" height="16" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24" stroke-width="2">
                                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                                        </svg>
                                                        Copy Link
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; endif; ?>
                    </div>

                    <div id="ch-feed-pagination-wrap">
                        <?php if ($total_pages > 1): ?>
                            <div class="ch-pagination" id="ch-feed-pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?paged=<?php echo $i; ?>&sort=<?php echo $sort; ?><?php echo $cat_slug ? '&cat=' . $cat_slug : ''; ?>"
                                        class="ch-page-btn <?php echo $i === $page ? 'active' : ''; ?>" data-page="<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </main>
            </div>

            <?php
            echo ch_render_post_composer_modal([
                'mode' => 'create',
                'modal_id' => 'ch-modal-create-post',
                'modal_title' => 'Create Post',
                'user_id' => $user_id,
                'display_name' => $user_id ? (wp_get_current_user()->display_name ?: 'You') : 'Guest',
                'avatar_url' => $current_profile->avatar_url ?? '',
                'categories' => $categories,
                'submit_nonce' => $user_id ? wp_create_nonce('ch_feed_nonce') : '',
                'submit_handler' => 'chSubmitPost',
                'submit_label' => 'Post',
                'hint_text' => 'Be respectful and follow community guidelines.',
                'message_id' => 'ch-post-msg',
                'category_id' => 'ch-post-cat',
                'title_id' => 'ch-post-title',
                'content_id' => 'ch-post-content',
                'tags_id' => 'ch-post-tags',
                'anon_id' => 'ch-post-anon',
                'media_input_id' => 'ch-post-media',
                'media_label_id' => 'ch-post-media-label',
                'media_preview_id' => 'ch-post-media-preview',
                'guest_name_id' => 'ch-post-guest-name',
                'show_guest_name' => !$user_id,
                'show_anonymous' => true,
                'title_placeholder' => 'Write a title...',
                'content_placeholder' => "What's on your mind? Share your thoughts, concerns, or suggestions with the community...",
            ]);
            if ($user_id) {
                echo ch_render_post_composer_modal([
                    'mode' => 'edit',
                    'modal_id' => 'ch-modal-edit-post',
                    'modal_title' => 'Edit Post',
                    'user_id' => $user_id,
                    'display_name' => wp_get_current_user()->display_name ?: 'You',
                    'avatar_url' => $current_profile->avatar_url ?? '',
                    'categories' => $categories,
                    'submit_nonce' => wp_create_nonce('ch_post_view_nonce'),
                    'submit_handler' => 'chUpdatePost',
                    'submit_label' => 'Save Changes',
                    'hint_text' => 'Edit your post. Changes are visible immediately.',
                    'message_id' => 'ch-edit-post-msg',
                    'category_id' => 'ch-edit-post-cat',
                    'title_id' => 'ch-edit-post-title',
                    'content_id' => 'ch-edit-post-content',
                    'tags_id' => 'ch-edit-post-tags',
                    'anon_id' => 'ch-edit-post-anon',
                    'media_input_id' => 'ch-edit-post-media',
                    'media_label_id' => 'ch-edit-post-media-label',
                    'media_preview_id' => 'ch-edit-post-media-preview',
                    'show_hidden_post_id' => true,
                    'post_id_input' => 'ch-edit-post-id',
                ]);
            }
            ?>
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
                            <textarea id="ch-report-details" class="ch-input ch-textarea" rows="3"
                                placeholder="Provide more context..."></textarea>
                        </div>
                    </div>
                    <div class="ch-modal-footer">
                        <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-report')">Cancel</button>
                        <button class="ch-btn ch-btn-danger" onclick="chSubmitReport('<?php echo esc_attr($nonce); ?>')">Submit
                            Report</button>
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
                        <button class="ch-btn ch-btn-primary" onclick="chCloseModal('ch-modal-guidelines')">I
                            Understand</button>
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
                                    <span class="ch-color-hex-preview" id="ch-feed-edit-cat-color-preview"
                                        style="background:#FF7551;"></span>
                                    <input type="text" id="ch-feed-edit-cat-color" class="ch-input ch-color-hex-input"
                                        value="#FF7551" placeholder="#FF7551" maxlength="7"
                                        oninput="chSyncColorHex('ch-feed-edit-cat-color-wheel','ch-feed-edit-cat-color','ch-feed-edit-cat-color-preview')">
                                </div>
                                <div class="ch-color-swatches">
                                    <?php foreach (['#FF7551', '#FF6640', '#ef4444', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#ec4899', '#f97316', '#84cc16'] as $sw): ?>
                                        <button type="button" class="ch-swatch" style="background:<?php echo $sw; ?>;"
                                            title="<?php echo $sw; ?>"
                                            onclick="chPickSwatch('ch-feed-edit-cat-color-wheel','ch-feed-edit-cat-color','ch-feed-edit-cat-color-preview','<?php echo $sw; ?>')"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="ch-field-group">
                            <label class="ch-label">Visibility</label>
                            <div class="ch-visibility-toggle">
                                <label class="ch-vis-option">
                                    <input type="radio" name="ch-feed-edit-cat-visibility" id="ch-feed-edit-cat-vis-public"
                                        value="0">
                                    <span class="ch-vis-label">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        Public
                                    </span>
                                </label>
                                <label class="ch-vis-option">
                                    <input type="radio" name="ch-feed-edit-cat-visibility" id="ch-feed-edit-cat-vis-private"
                                        value="1">
                                    <span class="ch-vis-label">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <rect x="3" y="11" width="18" height="11" rx="2" />
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                        </svg>
                                        Private
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="ch-field-group">
                            <label class="ch-label">Post Moderation</label>
                            <label class="ch-checkbox-wrap">
                                <input type="checkbox" id="ch-feed-edit-cat-require-approval" value="1">
                                <span>Require post approval in this category</span>
                            </label>
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
                                    <span class="ch-color-hex-preview" id="ch-feed-cat-color-preview"
                                        style="background:#FF7551;"></span>
                                    <input type="text" id="ch-feed-cat-color" class="ch-input ch-color-hex-input"
                                        value="#FF7551" placeholder="#FF7551" maxlength="7"
                                        oninput="chSyncColorHex('ch-feed-cat-color-wheel','ch-feed-cat-color','ch-feed-cat-color-preview')">
                                </div>
                                <div class="ch-color-swatches">
                                    <?php foreach (['#FF7551', '#FF6640', '#ef4444', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#ec4899', '#f97316', '#84cc16'] as $sw): ?>
                                        <button type="button" class="ch-swatch" style="background:<?php echo $sw; ?>;"
                                            title="<?php echo $sw; ?>"
                                            onclick="chPickSwatch('ch-feed-cat-color-wheel','ch-feed-cat-color','ch-feed-cat-color-preview','<?php echo $sw; ?>')"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="ch-field-group">
                            <label class="ch-label">Visibility</label>
                            <div class="ch-visibility-toggle">
                                <label class="ch-vis-option">
                                    <input type="radio" name="ch-feed-cat-visibility" id="ch-feed-cat-vis-public" value="0"
                                        checked>
                                    <span class="ch-vis-label">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        Public
                                    </span>
                                    <span class="ch-vis-desc">Anyone can view and guests can post</span>
                                </label>
                                <label class="ch-vis-option">
                                    <input type="radio" name="ch-feed-cat-visibility" id="ch-feed-cat-vis-private" value="1">
                                    <span class="ch-vis-label">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <rect x="3" y="11" width="18" height="11" rx="2" />
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                        </svg>
                                        Private
                                    </span>
                                    <span class="ch-vis-desc">Only followers can view and post</span>
                                </label>
                            </div>
                        </div>
                        <div class="ch-field-group">
                            <label class="ch-label">Post Moderation</label>
                            <label class="ch-checkbox-wrap">
                                <input type="checkbox" id="ch-feed-cat-require-approval" value="1">
                                <span>Require post approval in this category</span>
                            </label>
                        </div>
                    </div>
                    <div class="ch-modal-footer">
                        <button class="ch-btn ch-btn-secondary"
                            onclick="chCloseModal('ch-modal-feed-create-cat')">Cancel</button>
                        <button class="ch-btn ch-btn-primary" id="ch-feed-save-cat-btn"
                            onclick="chFeedSaveCategory('<?php echo esc_attr(wp_create_nonce('ch_category_nonce')); ?>')">
                            Create Category
                        </button>
                    </div>
                    <div id="ch-feed-cat-msg"></div>
                </div>
            </div>
            <script>
                (function () {
                    window.chFeedSaveCategory = function (nonce) {
                        const name = document.getElementById('ch-feed-cat-name').value.trim();
                        const desc = document.getElementById('ch-feed-cat-desc').value.trim();
                        const color = document.getElementById('ch-feed-cat-color').value;
                        if (!name) {
                            document.getElementById('ch-feed-cat-msg').innerHTML =
                                '<div class="bntm-notice bntm-notice-error">Please enter a category name.</div>';
                            return;
                        }
                        const btn = document.getElementById('ch-feed-save-cat-btn');
                        btn.disabled = true; btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Creating...</span>';
                        const fd = new FormData();
                        fd.append('action', 'ch_create_category');
                        fd.append('name', name);
                        fd.append('description', desc);
                        fd.append('color', color);
                        fd.append('sort_order', 0);
                        fd.append('is_private', document.getElementById('ch-feed-cat-vis-private')?.checked ? 1 : 0);
                        fd.append('require_post_approval', document.getElementById('ch-feed-cat-require-approval')?.checked ? 1 : 0);
                        fd.append('nonce', nonce);
                        fetch(ajaxurl, { method: 'POST', body: fd })
                            .then(r => r.json())
                            .then(json => {
                                document.getElementById('ch-feed-cat-msg').innerHTML =
                                    '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">'
                                    + (json.data?.message || '') + '</div>';
                                if (json.success) {
                                    // The server already auto-followed; AJAX reload feed
                                    chCloseModal('ch-modal-feed-create-cat');
                                    chAjaxReloadFeed();
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
                (function () {
                    const _catNonce = '<?php echo esc_attr(wp_create_nonce('ch_category_nonce')); ?>';

                    // Open the edit modal pre-filled
                    window.chFeedOpenEditCat = function (id, name, desc, color, isPrivate, requireApproval) {
                        document.getElementById('ch-feed-edit-cat-id').value = id;
                        document.getElementById('ch-feed-edit-cat-name').value = name;
                        document.getElementById('ch-feed-edit-cat-desc').value = desc;
                        document.getElementById('ch-feed-edit-cat-color').value = color.toUpperCase();
                        const wheel = document.getElementById('ch-feed-edit-cat-color-wheel');
                        if (wheel) wheel.value = color;
                        const prev = document.getElementById('ch-feed-edit-cat-color-preview');
                        if (prev) { prev.style.background = color; prev.style.opacity = '1'; }
                        const privRadio = document.getElementById('ch-feed-edit-cat-vis-private');
                        const pubRadio = document.getElementById('ch-feed-edit-cat-vis-public');
                        if (privRadio && pubRadio) {
                            privRadio.checked = !!isPrivate;
                            pubRadio.checked = !isPrivate;
                        }
                        const requireApprovalCheckbox = document.getElementById('ch-feed-edit-cat-require-approval');
                        if (requireApprovalCheckbox) requireApprovalCheckbox.checked = !!requireApproval;
                        document.getElementById('ch-feed-edit-cat-msg').innerHTML = '';
                        chOpenModal('ch-modal-feed-edit-cat');
                    };

                    // Save the edited category
                    window.chFeedSaveEditCat = function (nonce) {
                        const id = document.getElementById('ch-feed-edit-cat-id').value;
                        const name = document.getElementById('ch-feed-edit-cat-name').value.trim();
                        const desc = document.getElementById('ch-feed-edit-cat-desc').value.trim();
                        const color = document.getElementById('ch-feed-edit-cat-color').value;
                        if (!name) {
                            document.getElementById('ch-feed-edit-cat-msg').innerHTML =
                                '<div class="bntm-notice bntm-notice-error">Category name is required.</div>';
                            return;
                        }
                        const btn = document.getElementById('ch-feed-edit-cat-btn');
                        btn.disabled = true; btn.innerHTML = '<svg class="ch-btn-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ch-spin 0.7s linear infinite;flex-shrink:0;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg><span>Saving...</span>';
                        const fd = new FormData();
                        fd.append('action', 'ch_edit_category');
                        fd.append('category_id', id);
                        fd.append('name', name);
                        fd.append('description', desc);
                        fd.append('color', color);
                        fd.append('is_private', document.getElementById('ch-feed-edit-cat-vis-private')?.checked ? 1 : 0);
                        fd.append('require_post_approval', document.getElementById('ch-feed-edit-cat-require-approval')?.checked ? 1 : 0);
                        fd.append('nonce', nonce);
                        fetch(ajaxurl, { method: 'POST', body: fd })
                            .then(r => r.json())
                            .then(json => {
                                document.getElementById('ch-feed-edit-cat-msg').innerHTML =
                                    '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">'
                                    + (json.data?.message || '') + '</div>';
                                if (json.success) {
                                    chCloseModal('ch-modal-feed-edit-cat');
                                    chAjaxReloadFeed();
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
                    window.chFeedDeleteCat = function (id, name, nonce) {
                        if (!confirm('Delete category "' + name + '"? This cannot be undone.')) return;
                        const fd = new FormData();
                        fd.append('action', 'ch_delete_category');
                        fd.append('category_id', id);
                        fd.append('nonce', nonce);
                        fetch(ajaxurl, { method: 'POST', body: fd })
                            .then(r => r.json())
                            .then(json => {
                                if (json.success) {
                                    chAjaxReloadFeed();
                                }
                                else alert(json.data?.message || 'Could not delete category.');
                            })
                            .catch(() => alert('Network error.'));
                    };
                })();
            </script>
        <?php endif; ?>

        <script>
            (function () {
                // Open the create-post modal pre-set to a specific category
                window.chOpenPostInCategory = function (catId) {
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
                        <img src="<?php echo esc_url(bntm_ch_logo_url()); ?>" alt="CivicHub Logo" class="ch-welcome-logo">
                    </div>
                    <h2 class="ch-welcome-title">Welcome to CivicHub!</h2>
                    <p class="ch-welcome-subtitle">Your community's space to discuss, share, and connect with neighbors.</p>
                    <div class="ch-welcome-features">
                        <div class="ch-welcome-feature">
                            <span class="ch-welcome-feat-icon">💬</span>
                            <span>Join discussions on local issues</span>
                            <div class="ch-welcome-feature-copy">
                                <strong>Local Discussions</strong>
                                <span>Discover neighborhood conversations, updates, and concerns in one place.</span>
                            </div>
                        </div>
                        <div class="ch-welcome-feature">
                            <span class="ch-welcome-feat-icon">📌</span>
                            <span>Follow topics that matter to you</span>
                            <div class="ch-welcome-feature-copy">
                                <strong>Topic Following</strong>
                                <span>Keep tabs on the issues you care about and return to them instantly.</span>
                            </div>
                        </div>
                        <div class="ch-welcome-feature">
                            <span class="ch-welcome-feat-icon">🗳️</span>
                            <span>Vote and share your opinions</span>
                            <div class="ch-welcome-feature-copy">
                                <strong>Community Voting</strong>
                                <span>Support the ideas that matter most and help useful posts rise to the top.</span>
                            </div>
                        </div>
                    </div>
                    <div class="ch-welcome-actions">
                        <?php
                        $reg_link = ch_get_auth_url('register');
                        $login_link = ch_get_auth_url('login');
                        ?>
                        <a href="<?php echo esc_url($reg_link); ?>" class="ch-welcome-btn ch-welcome-btn-primary">Join Free</a>
                        <a href="<?php echo esc_url($login_link); ?>" class="ch-welcome-btn ch-welcome-btn-secondary">Sign
                            In</a>
                    </div>
                    <button class="ch-welcome-skip" onclick="chCloseWelcome()">Browse as guest</button>
                </div>
            </div>

            <div id="ch-onboarding-modal" class="ch-modal-overlay" style="display:none;">
                <div class="ch-modal ch-onboarding-modal">
                    <div class="ch-modal-header">
                        <h3>Getting Started</h3>
                        <button type="button" class="ch-modal-close" onclick="chCloseOnboardingWalkthrough()"
                            aria-label="Close">&times;</button>
                    </div>
                    <div class="ch-modal-body">
                        <div class="ch-onboarding-progress" aria-hidden="true">
                            <div class="ch-onboarding-progress-bar" data-step-bar="0"><span></span></div>
                            <div class="ch-onboarding-progress-bar" data-step-bar="1"><span></span></div>
                            <div class="ch-onboarding-progress-bar" data-step-bar="2"><span></span></div>
                        </div>

                        <div class="ch-onboarding-slide is-active" data-step="0">
                            <div class="ch-onboarding-stage-label">Step 1 of 3</div>
                            <div class="ch-onboarding-hero">
                                <div class="ch-onboarding-hero-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="1.9">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4>Explore conversations first</h4>
                                    <p>Browse categories and active discussions to quickly see what your community is already
                                        talking about.</p>
                                </div>
                            </div>
                            <div class="ch-onboarding-checklist">
                                <div class="ch-onboarding-point"><span class="ch-onboarding-dot"></span>
                                    <div><strong>Use categories to focus</strong><span>Open the category list to jump into
                                            topics like safety, infrastructure, and local updates.</span></div>
                                </div>
                                <div class="ch-onboarding-point"><span class="ch-onboarding-dot"></span>
                                    <div><strong>Check the feed for context</strong><span>Reading a few recent posts helps you
                                            avoid duplicate posts and find the right thread faster.</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="ch-onboarding-slide" data-step="1">
                            <div class="ch-onboarding-stage-label">Step 2 of 3</div>
                            <div class="ch-onboarding-hero">
                                <div class="ch-onboarding-hero-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="1.9">
                                        <path d="M12 5v14M5 12h14" />
                                    </svg>
                                </div>
                                <div>
                                    <h4>Share clearly and visually</h4>
                                    <p>Create posts with a clear title, helpful details, and media that supports your report
                                        without overwhelming the feed.</p>
                                </div>
                            </div>
                            <div class="ch-onboarding-checklist">
                                <div class="ch-onboarding-point"><span class="ch-onboarding-dot"></span>
                                    <div><strong>Pick the right category</strong><span>Choosing the closest category makes your
                                            post easier for the right people to discover.</span></div>
                                </div>
                                <div class="ch-onboarding-point"><span class="ch-onboarding-dot"></span>
                                    <div><strong>Add only useful media</strong><span>Keep uploads focused so your post stays
                                            clean and readable on both desktop and mobile.</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="ch-onboarding-slide" data-step="2">
                            <div class="ch-onboarding-stage-label">Step 3 of 3</div>
                            <div class="ch-onboarding-hero">
                                <div class="ch-onboarding-hero-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="1.9">
                                        <path d="M12 12c2.761 0 5-2.239 5-5S14.761 2 12 2 7 4.239 7 7s2.239 5 5 5Z" />
                                        <path d="M4 22a8 8 0 0 1 16 0" />
                                    </svg>
                                </div>
                                <div>
                                    <h4>Participate at your own pace</h4>
                                    <p>You can keep browsing as a guest, or join later when you want to post, save updates, and
                                        personalize your profile.</p>
                                </div>
                            </div>
                            <div class="ch-onboarding-checklist">
                                <div class="ch-onboarding-point"><span class="ch-onboarding-dot"></span>
                                    <div><strong>Browse without pressure</strong><span>Guests can read discussions, explore
                                            categories, and understand the community before signing up.</span></div>
                                </div>
                                <div class="ch-onboarding-point"><span class="ch-onboarding-dot"></span>
                                    <div><strong>Join when you are ready</strong><span>Create an account later if you want to
                                            post, bookmark threads, and get notified about replies.</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ch-modal-footer ch-onboarding-footer">
                        <div class="ch-onboarding-footer-left">
                            <button type="button" class="ch-btn ch-btn-secondary"
                                onclick="chCloseOnboardingWalkthrough()">Skip</button>
                        </div>
                        <div class="ch-onboarding-footer-right">
                            <button type="button" class="ch-btn ch-btn-secondary" id="ch-onboarding-back"
                                onclick="chAdvanceOnboarding(-1)" style="display:none;">Back</button>
                            <button type="button" class="ch-btn ch-btn-primary" id="ch-onboarding-next"
                                onclick="chAdvanceOnboarding(1)">Next</button>
                        </div>
                    </div>
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
                                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                                        </svg>
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
                                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                            <circle cx="12" cy="7" r="4" />
                                        </svg>
                                        Edit Profile
                                    </div>
                                    <div class="ch-settings-row-desc">Change name, bio, location, avatar</div>
                                </div>
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <polyline points="9 18 15 12 9 6" />
                                </svg>
                            </a>
                            <a href="?bookmarks=1" class="ch-settings-row ch-settings-row-link">
                                <div class="ch-settings-row-info">
                                    <div class="ch-settings-row-label">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                        </svg>
                                        My Bookmarks
                                    </div>
                                    <div class="ch-settings-row-desc">View your saved posts</div>
                                </div>
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <polyline points="9 18 15 12 9 6" />
                                </svg>
                            </a>
                        </div>

                        <!-- Community -->
                        <div class="ch-settings-section">
                            <div class="ch-settings-section-title">Community</div>
                            <div class="ch-settings-row">
                                <div class="ch-settings-row-info">
                                    <div class="ch-settings-row-label">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Community Guidelines
                                    </div>
                                    <div class="ch-settings-row-desc">Review the rules of the forum</div>
                                </div>
                                <button onclick="chCloseModal('ch-modal-settings'); chShowGuidelines();"
                                    style="background:none;border:none;cursor:pointer;color:var(--ch-accent);font-size:12px;font-weight:600;font-family:var(--ch-font);">View</button>
                            </div>
                        </div>

                        <!-- Danger zone -->
                        <div class="ch-settings-section" style="border-bottom:none;">
                            <div class="ch-settings-section-title" style="color:#ef4444;">Account Actions</div>
                            <a href="<?php echo esc_url(ch_get_logout_url(get_permalink())); ?>" class="ch-settings-row ch-settings-row-link"
                                style="color:#ef4444;">
                                <div class="ch-settings-row-info">
                                    <div class="ch-settings-row-label" style="color:#ef4444;">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                            <polyline points="16 17 21 12 16 7" />
                                            <line x1="21" y1="12" x2="9" y2="12" />
                                        </svg>
                                        Sign Out
                                    </div>
                                    <div class="ch-settings-row-desc">Log out of your account</div>
                                </div>
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <polyline points="9 18 15 12 9 6" />
                                </svg>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        <?php endif; ?>

        <script>
            (function () {
                // ---- Welcome popup ----
                window.chCloseWelcome = function () {
                    const el = document.getElementById('ch-welcome-popup');
                    if (el) {
                        el.style.opacity = '0';
                        el.style.transform = 'scale(0.96)';
                        setTimeout(() => { el.style.display = 'none'; }, 250);
                    }
                    try { sessionStorage.setItem('ch_welcomed', '1'); } catch (e) { }
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
                    } catch (e) { }
                }
                // Close welcome on overlay click
                if (popup) {
                    popup.addEventListener('click', function (e) {
                        if (e.target === popup) chCloseWelcome();
                    });
                }

                // ---- First-time onboarding walkthrough ----
                const onboardingModal = document.getElementById('ch-onboarding-modal');
                const onboardingSlides = onboardingModal ? Array.from(onboardingModal.querySelectorAll('.ch-onboarding-slide')) : [];
                const onboardingBars = onboardingModal ? Array.from(onboardingModal.querySelectorAll('[data-step-bar]')) : [];
                const onboardingBack = document.getElementById('ch-onboarding-back');
                const onboardingNext = document.getElementById('ch-onboarding-next');
                const onboardingKey = 'ch_guest_onboarding_seen_v1';
                const isGuestUser = <?php echo $user_id ? 'false' : 'true'; ?>;
                window.chOnboardingStep = 0;

                function chRenderOnboardingStep(step) {
                    if (!onboardingModal || !onboardingSlides.length) return;
                    const clampedStep = Math.max(0, Math.min(step, onboardingSlides.length - 1));
                    window.chOnboardingStep = clampedStep;

                    onboardingSlides.forEach((slide, index) => {
                        slide.classList.toggle('is-active', index === clampedStep);
                    });
                    onboardingBars.forEach((bar, index) => {
                        bar.classList.toggle('is-active', index === clampedStep);
                        bar.classList.toggle('is-done', index < clampedStep);
                    });

                    if (onboardingBack) {
                        onboardingBack.style.display = clampedStep === 0 ? 'none' : 'inline-flex';
                    }
                    if (onboardingNext) {
                        onboardingNext.textContent = clampedStep === onboardingSlides.length - 1 ? 'Finish' : 'Next';
                    }
                }

                window.chCloseOnboardingWalkthrough = function (markSeen) {
                    if (!onboardingModal) return;
                    onboardingModal.style.display = 'none';
                    document.body.style.overflow = '';
                    if (markSeen !== false) {
                        try { localStorage.setItem(onboardingKey, '1'); } catch (e) { }
                    }
                };

                window.chOpenOnboardingWalkthrough = function (forceOpen) {
                    if (!onboardingModal) return;
                    chRenderOnboardingStep(0);
                    onboardingModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    if (forceOpen === true) {
                        onboardingModal.dataset.manual = '1';
                    } else {
                        onboardingModal.dataset.manual = '0';
                    }
                };

                window.chAdvanceOnboarding = function (direction) {
                    if (!onboardingModal || !onboardingSlides.length) return;
                    const nextStep = window.chOnboardingStep + (direction > 0 ? 1 : -1);
                    if (nextStep >= onboardingSlides.length) {
                        window.chCloseOnboardingWalkthrough(true);
                        return;
                    }
                    chRenderOnboardingStep(nextStep);
                };

                if (onboardingModal) {
                    onboardingModal.addEventListener('click', function (e) {
                        if (e.target === onboardingModal) {
                            window.chCloseOnboardingWalkthrough(true);
                        }
                    });

                    try {
                        const hasSeenOnboarding = localStorage.getItem(onboardingKey) === '1';
                        const isCommunitySurface = document.querySelector('.ch-feed-wrap, .ch-post-view-wrap, .ch-my-feed-wrap, .ch-public-profile-wrap, .ch-mf-page-wrap');
                        if (isGuestUser && !hasSeenOnboarding && isCommunitySurface) {
                            setTimeout(() => {
                                window.chOpenOnboardingWalkthrough(false);
                            }, 700);
                        }
                    } catch (e) { }
                }

                // ---- Settings modal ----
                window.chOpenSettingsModal = function () {
                    // Sync dark mode toggle state
                    const dashWrap = document.querySelector('.ch-dashboard-wrap');
                    const isDark = dashWrap ? dashWrap.classList.contains('ch-dark') : document.documentElement.classList.contains('ch-dark');
                    const tog = document.getElementById('ch-dark-mode-toggle');
                    if (tog) tog.checked = isDark;
                    chOpenModal('ch-modal-settings');
                };
                window.chCloseProfileMenu = function () {
                    const m = document.getElementById('ch-profile-menu');
                    if (m) m.style.display = 'none';
                };

                // ---- Dark mode ----
                window.chToggleDarkMode = function (enabled) {
                    // In the admin panel the BNTM universal container wraps the page,
                    // so we scope ch-dark to the inner dashboard wrap only.
                    var dashWrap = document.querySelector('.ch-dashboard-wrap');
                    if (dashWrap) {
                        if (enabled) {
                            dashWrap.classList.add('ch-dark');
                        } else {
                            dashWrap.classList.remove('ch-dark');
                        }
                        document.documentElement.classList.remove('ch-dark');
                        document.body && document.body.classList.remove('ch-dark');
                    } else {
                        // On non-admin pages (feed, post view, etc.) apply to html as before
                        if (enabled) {
                            document.documentElement.classList.add('ch-dark');
                        } else {
                            document.documentElement.classList.remove('ch-dark');
                        }
                    }
                    try {
                        if (enabled) localStorage.setItem('ch_dark_mode', '1');
                        else localStorage.removeItem('ch_dark_mode');
                    } catch (e) { }
                };
                // Apply dark mode on load
                try {
                    if (localStorage.getItem('ch_dark_mode') === '1') {
                        var dashWrap = document.querySelector('.ch-dashboard-wrap');
                        if (dashWrap) {
                            dashWrap.classList.add('ch-dark');
                        } else {
                            document.documentElement.classList.add('ch-dark');
                        }
                    }
                } catch (e) { }
            })();
        </script>
    </div>


    <?php
    ch_output_global_styles_fallback();
    echo ch_global_scripts();
    echo ch_feed_scripts();
    return ob_get_clean();
}

// ============================================================
// FRONTEND: POST VIEW
// ============================================================

function bntm_shortcode_ch_post_view()
{
    global $wpdb;

    $rand_id = sanitize_text_field($_GET['view_post'] ?? '');
    if (!$rand_id)
        return '<p>Post not found.</p>';

    $post = $wpdb->get_row($wpdb->prepare(
        "SELECT p.*, c.name as cat_name, c.color as cat_color, c.slug as cat_slug,
                u.display_name as author_name, u.karma_points as author_karma, u.bio as author_bio
         FROM {$wpdb->prefix}ch_posts p
         LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
         LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
         WHERE p.rand_id = %s AND p.status = 'active'",
        $rand_id
    ));

    if (!$post)
        return '<p class="ch-empty">Post not found or has been removed.</p>';

    // Private category access check
    if ($post->cat_slug) {
        $cat_privacy = $wpdb->get_var($wpdb->prepare(
            "SELECT is_private FROM {$wpdb->prefix}ch_categories WHERE id = %d",
            $post->category_id
        ));
        if ($cat_privacy) {
            $viewer = get_current_user_id();
            if (!$viewer) {
                $auth_url = ch_get_auth_url('login');
                return '<div style="padding:60px 20px;text-align:center;font-family:-apple-system,sans-serif;">
                    <svg width="48" height="48" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" stroke-width="1.5" style="display:block;margin:0 auto 16px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <h3 style="color:var(--ch-text);margin:0 0 8px">Private Category</h3>
                    <p style="color:#9ca3af;margin:0 0 16px">Please sign in and follow this category to view this post.</p>
                    <a href="' . esc_url($auth_url) . '" style="background:#FF7551;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600;">Sign In</a>
                </div>';
            }
            $is_following = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ch_follows WHERE user_id = %d AND category_id = %d",
                $viewer,
                $post->category_id
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
    if (!empty($comments)) {
        $comment_ids = array_map(fn($c) => (int) $c->id, $comments);
        $placeholders = implode(',', array_fill(0, count($comment_ids), '%d'));
        $reply_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT cm2.*, u.display_name as author_name
             FROM {$wpdb->prefix}ch_comments cm2
             LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON cm2.user_id = u.user_id
             WHERE cm2.parent_id IN ($placeholders) AND cm2.status = 'active'
             ORDER BY cm2.created_at ASC",
            ...$comment_ids
        ));
        foreach ($comments as $cm) {
            $replies_map[(int) $cm->id] = [];
        }
        foreach ($reply_rows as $reply) {
            $replies_map[(int) $reply->parent_id][] = $reply;
        }
    }

    $user_id = get_current_user_id();
    $nonce = wp_create_nonce('ch_post_view_nonce');

    // Fetch current user's vote on this post and all comments
    $user_vote_on_post = 0;
    $user_comment_votes = [];
    if ($user_id) {
        $all_comment_ids = array_map(fn($c) => (int) $c->id, $comments);
        foreach ($replies_map as $rlist) {
            foreach ($rlist as $r) {
                $all_comment_ids[] = (int) $r->id;
            }
        }

        $pv = $wpdb->get_var($wpdb->prepare(
            "SELECT value FROM {$wpdb->prefix}ch_votes WHERE user_id=%d AND target_type='post' AND target_id=%d",
            $user_id,
            $post->id
        ));
        $user_vote_on_post = (int) ($pv ?? 0);

        if (!empty($all_comment_ids)) {
            $ph = implode(',', array_fill(0, count($all_comment_ids), '%d'));
            $cvs = $wpdb->get_results($wpdb->prepare(
                "SELECT target_id, value FROM {$wpdb->prefix}ch_votes
                 WHERE user_id = %d AND target_type = 'comment' AND target_id IN ($ph)",
                array_merge([$user_id], $all_comment_ids)
            ));
            foreach ($cvs as $cv) {
                $user_comment_votes[(int) $cv->target_id] = (int) $cv->value;
            }
        }
    }

    // Get categories for modals
    $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ch_categories WHERE status='active' ORDER BY name ASC");

    ob_start(); ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>

    <nav class="ch-top-nav">
                <div class="ch-top-nav-logo">
        <button class="ch-burger-menu-btn" type="button" aria-label="Toggle menu" aria-expanded="false"
            onclick="chToggleMobileMenu(this, '#ch-feed-drawer-pv');">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path d="M3 12h18M3 6h18M3 18h18" />
            </svg>
        </button>

            <img src="<?php echo esc_url(bntm_ch_logo_url()); ?>" alt="CivicHub Logo" class="ch-brand-logo">
        </div>

        <!-- Mobile drawer -->
        <div id="ch-feed-drawer-pv" class="ch-mobile-drawer-wrap">
            <button class="ch-top-drawer-close" type="button" onclick="chCloseAllMobileMenus()"
                aria-label="Close menu">&times;</button>
            <div class="ch-nav-links">
                <a href="<?php echo remove_query_arg('view_post'); ?>" class="ch-nav-link">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <polyline points="15 18 9 12 15 6" />
                    </svg>
                    <span class="ch-nav-label">Back to Forum</span>
                </a>
            </div>
            <?php if (!$user_id): ?>
                <div class="ch-user-bar">
                    <a href="<?php echo esc_url(ch_get_auth_url('login')); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">Sign In</a>
                    <a href="<?php echo esc_url(ch_get_auth_url('register')); ?>" class="ch-btn ch-btn-primary ch-btn-sm">Join</a>
                </div>
            <?php endif; ?>
        </div><!-- /#ch-feed-drawer-pv -->

        <?php if ($user_id): ?>
            <?php $current_display_pv = wp_get_current_user()->display_name ?: 'U'; ?>
            <!-- ── Desktop logged-in user bar ── -->
            <div class="ch-user-bar ch-nav-user-desktop">
                <div class="ch-notifications-dropdown ch-top-nav-notifications">
                    <button class="ch-icon-action-btn ch-notifications-btn" id="ch-notif-btn" onclick="chToggleNotifications(event)"
                        aria-label="Notifications">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
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
                    <button class="ch-avatar-btn" id="ch-profile-btn" onclick="chToggleProfileMenu(event)"
                        aria-label="Profile" data-ch-current-user-avatar="1"
                        data-avatar-name="<?php echo esc_attr($current_display_pv); ?>">
                        <?php echo ch_render_avatar($current_display_pv, $current_profile->avatar_url ?? '', 'ch-avatar-btn-inner', 'ch-current-user-avatar-img'); ?>
                    </button>
                    <div class="ch-dropdown-panel ch-dropdown-panel-sm" id="ch-profile-menu" style="display:none;">
                        <div class="ch-dropdown-user-info">
                            <div class="ch-avatar-btn ch-avatar-btn-lg" data-ch-current-user-avatar="1"
                                data-avatar-name="<?php echo esc_attr($current_display_pv); ?>">
                                <?php echo ch_render_avatar($current_display_pv, $current_profile->avatar_url ?? '', 'ch-avatar-btn-inner ch-avatar-btn-inner-lg', 'ch-current-user-avatar-img'); ?>
                            </div>
                            <div>
                                <div class="ch-dropdown-username"><?php echo esc_html($current_display_pv); ?></div>
                                <div class="ch-dropdown-usermeta">Community Member</div>
                            </div>
                        </div>
                        <div class="ch-dropdown-divider"></div>
                        <a href="javascript:void(0)" class="ch-dropdown-item"
                            onclick="chOpenSettingsModal(); chCloseProfileMenu();">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <circle cx="12" cy="12" r="3" />
                                <path
                                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                            </svg>
                            Settings
                        </a>
                        <div class="ch-dropdown-divider"></div>
                        <a href="<?php echo esc_url(ch_get_logout_url(remove_query_arg('view_post'))); ?>"
                            class="ch-dropdown-item ch-dropdown-item-danger">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                            Sign Out
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- ── Desktop guest auth buttons ── -->
            <div class="ch-user-bar ch-nav-guest-desktop">
                <a href="<?php echo esc_url(ch_get_auth_url('login')); ?>" class="ch-btn ch-btn-secondary ch-btn-sm">Sign In</a>
                <a href="<?php echo esc_url(ch_get_auth_url('register')); ?>" class="ch-btn ch-btn-primary ch-btn-sm">Join</a>
            </div>
        <?php endif; ?>
    </nav>

    <div class="ch-post-view-wrap">
        <div class="ch-post-view-grid">
            <div class="ch-post-view-main">
                <article class="ch-post-full">
                    <div class="ch-post-meta-row">
                        <span class="ch-cat-badge"
                            style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
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
                        <span
                            class="ch-post-time"><?php echo human_time_diff(strtotime($post->created_at), current_time('timestamp')); ?>
                            ago</span>
                        <span class="ch-post-views"><?php echo number_format($post->view_count); ?> views</span>
                    </div>

                    <h1 class="ch-post-full-title"><?php echo esc_html($post->title); ?></h1>

                    <div class="ch-post-full-content">
                        <?php echo wp_kses_post(nl2br($post->content)); ?>
                    </div>

                    <?php echo ch_render_post_media_preview($post->media_urls ?? '', 'post'); ?>

                    <?php if ($post->tags): ?>
                        <div class="ch-post-tags">
                            <?php foreach (explode(',', $post->tags) as $tag): ?>
                                <span class="ch-tag">#<?php echo esc_html(trim($tag)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="ch-post-vote-bar">
                        <?php if ($user_id): ?>
                            <button class="ch-vote-btn-lg ch-vote-up <?php echo $user_vote_on_post === 1 ? 'active-up' : ''; ?>"
                                data-id="<?php echo (int) $post->id; ?>" data-type="post" data-val="1"
                                onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2.5">
                                    <polyline points="18 15 12 9 6 15" />
                                </svg>
                                Upvote
                            </button>
                        <?php endif; ?>
                        <span class="ch-vote-score" id="ch-post-score"><?php echo (int) $post->vote_count; ?> points</span>
                        <?php if ($user_id): ?>
                            <button
                                class="ch-vote-btn-lg ch-vote-down <?php echo $user_vote_on_post === -1 ? 'active-down' : ''; ?>"
                                data-id="<?php echo (int) $post->id; ?>" data-type="post" data-val="-1"
                                onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2.5">
                                    <polyline points="6 9 12 15 18 9" />
                                </svg>
                                Downvote
                            </button>
                        <?php endif; ?>
                        <button class="ch-vote-btn-lg ch-share-btn" onclick="chOpenModal('ch-modal-share-post')">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <circle cx="18" cy="5" r="3" />
                                <circle cx="6" cy="12" r="3" />
                                <circle cx="18" cy="19" r="3" />
                                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                            </svg>
                            Share
                        </button>
                        <?php $post_share_url = get_permalink() . '?view_post=' . $post->rand_id; ?>
                        <div id="ch-modal-share-post" class="ch-modal-overlay" style="display:none">
                            <div class="ch-modal" style="max-width:420px;">
                                <div class="ch-modal-header">
                                    <h3>Share this post</h3>
                                    <button class="ch-modal-close" type="button" onclick="chCloseModal('ch-modal-share-post')"
                                        aria-label="Close">&times;</button>
                                </div>
                                <div class="ch-modal-body" style="display:grid;gap:8px;">
                                    <button class="ch-share-option"
                                        onclick="chShareToSocial('twitter', '<?php echo esc_url($post_share_url); ?>', '<?php echo esc_attr($post->title); ?>'); chCloseModal('ch-modal-share-post');">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                        </svg>
                                        Twitter
                                    </button>
                                    <button class="ch-share-option"
                                        onclick="chShareToSocial('facebook', '<?php echo esc_url($post_share_url); ?>', '<?php echo esc_attr($post->title); ?>'); chCloseModal('ch-modal-share-post');">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                        </svg>
                                        Facebook
                                    </button>
                                    <button class="ch-share-option"
                                        onclick="chShareToSocial('linkedin', '<?php echo esc_url($post_share_url); ?>', '<?php echo esc_attr($post->title); ?>'); chCloseModal('ch-modal-share-post');">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                        </svg>
                                        LinkedIn
                                    </button>
                                    <button class="ch-share-option"
                                        onclick="chShareToSocial('copy', '<?php echo esc_url($post_share_url); ?>'); chCloseModal('ch-modal-share-post');">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                        </svg>
                                        Copy Link
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php if ($user_id && $post->user_id != $user_id && !current_user_can('manage_options')): ?>
                            <button class="ch-vote-btn-lg"
                                onclick="chReportPost(<?php echo (int) $post->id; ?>, '<?php echo esc_attr($nonce); ?>')">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
                                    <line x1="4" y1="22" x2="4" y2="15" />
                                </svg>
                                Report
                            </button>
                        <?php endif; ?>
                        <?php if ($user_id && ($post->user_id !== 0 && $post->user_id == $user_id || current_user_can('manage_options'))): ?>
                            <button class="ch-vote-btn-lg" onclick="chOpenEditPostModal(<?php echo (int) $post->id; ?>)">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                                Edit
                            </button>
                            <button class="ch-vote-btn-lg ch-danger"
                                onclick="chDeletePost(<?php echo (int) $post->id; ?>, '<?php echo esc_attr($nonce); ?>')">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <polyline points="3 6 5 6 21 6" />
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    <line x1="10" y1="11" x2="10" y2="17" />
                                    <line x1="14" y1="11" x2="14" y2="17" />
                                </svg>
                                Delete
                            </button>
                        <?php endif; ?>
                    </div>
                </article>

                <!-- Comments -->
                <section class="ch-comments-section">
                    <h3 class="ch-comments-title"><?php echo (int) $post->comment_count; ?> Comments</h3>

                    <?php if ($user_id): ?>
                        <div class="ch-comment-form" id="ch-comment-form-main">
                            <div class="ch-avatar-sm">
                                <?php echo strtoupper(substr(wp_get_current_user()->display_name ?: 'U', 0, 1)); ?></div>
                            <div class="ch-comment-input-wrap">
                                <textarea id="ch-comment-content" class="ch-input ch-textarea" rows="3"
                                    placeholder="Share your thoughts..."></textarea>
                                <div class="ch-comment-form-footer">
                                    <label class="ch-checkbox-label">
                                        <input type="checkbox" id="ch-comment-anon"> Comment anonymously
                                    </label>
                                    <button class="ch-btn ch-btn-primary"
                                        onclick="chSubmitComment(<?php echo (int) $post->id; ?>, 0, '<?php echo esc_attr($nonce); ?>')">Post
                                        Comment</button>
                                </div>
                            </div>
                        </div>
                    <?php elseif (!$post->is_private): ?>
                        <!-- Guest comment form for public categories -->
                        <div class="ch-comment-form" id="ch-comment-form-main">
                            <div class="ch-avatar-sm">?</div>
                            <div class="ch-comment-input-wrap">
                                <input type="text" id="ch-guest-comment-name" class="ch-input"
                                    placeholder="Your name (optional — leave blank for Anonymous)" maxlength="100"
                                    style="margin-bottom:8px;">
                                <textarea id="ch-comment-content" class="ch-input ch-textarea" rows="3"
                                    placeholder="Share your thoughts..."></textarea>
                                <div class="ch-comment-form-footer">
                                    <span style="font-size:12px;color:#9ca3af;">Posting as guest &bull; <a
                                            href="<?php echo esc_url(ch_get_auth_url('login', get_permalink())); ?>" style="color:#FF7551;">Sign
                                            in</a> for full access</span>
                                    <button class="ch-btn ch-btn-primary"
                                        onclick="chSubmitGuestComment(<?php echo (int) $post->id; ?>, 0, '<?php echo esc_attr($nonce); ?>')">Post
                                        Comment</button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="ch-login-prompt">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            This is a private category. Please <a href="<?php echo esc_url(ch_get_auth_url('login', get_permalink())); ?>">log in</a>
                            and follow to comment.
                        </p>
                    <?php endif; ?>

                    <div class="ch-comments-list" id="ch-comments-list">
                        <?php if (empty($comments)): ?>
                            <p class="ch-empty">No comments yet. Start the discussion!</p>
                        <?php else:
                            foreach ($comments as $cm): ?>
                                <div class="ch-comment" id="ch-comment-<?php echo (int) $cm->id; ?>">
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
                                            <span
                                                class="ch-comment-time"><?php echo human_time_diff(strtotime($cm->created_at), current_time('timestamp')); ?>
                                                ago</span>
                                        </div>
                                        <p><?php echo ch_highlight_mentions(nl2br(esc_html($cm->content))); ?></p>
                                        <div class="ch-comment-actions">
                                            <?php if ($user_id):
                                                $ucv = $user_comment_votes[$cm->id] ?? 0; ?>
                                                <button
                                                    class="ch-comment-action ch-vote-up <?php echo $ucv === 1 ? 'active-up' : ''; ?>"
                                                    data-id="<?php echo (int) $cm->id; ?>" data-type="comment" data-val="1"
                                                    onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                        stroke-width="2.5">
                                                        <polyline points="18 15 12 9 6 15" />
                                                    </svg>
                                                    <?php echo $cm->vote_count; ?>
                                                </button>
                                                <button class="ch-comment-action"
                                                    onclick="chToggleReplyForm(<?php echo (int) $cm->id; ?>)">Reply</button>
                                                <?php if ($user_id && ($cm->user_id === 0 || $cm->user_id != $user_id) && !current_user_can('manage_options')): ?>
                                                    <button class="ch-comment-action"
                                                        onclick="chReportComment(<?php echo (int) $cm->id; ?>, '<?php echo esc_attr($nonce); ?>')">Report</button>
                                                <?php endif; ?>
                                                <?php if ($user_id && ($cm->user_id !== 0 && $cm->user_id == $user_id || current_user_can('manage_options'))): ?>
                                                    <button class="ch-comment-action"
                                                        onclick="chEditComment(<?php echo (int) $cm->id; ?>)">Edit</button>
                                                    <button class="ch-comment-action ch-danger-action"
                                                        onclick="chDeleteComment(<?php echo (int) $cm->id; ?>, '<?php echo esc_attr($nonce); ?>', this)">Delete</button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($user_id): ?>
                                            <div class="ch-reply-form" id="ch-reply-form-<?php echo (int) $cm->id; ?>"
                                                style="display:none">
                                                <textarea class="ch-input ch-textarea" id="ch-reply-content-<?php echo (int) $cm->id; ?>"
                                                    rows="2" placeholder="Write a reply..."></textarea>
                                                <div style="margin-top:8px">
                                                    <button class="ch-btn ch-btn-primary ch-btn-sm"
                                                        onclick="chSubmitComment(<?php echo (int) $post->id; ?>, <?php echo (int) $cm->id; ?>, '<?php echo esc_attr($nonce); ?>')">Reply</button>
                                                    <button class="ch-btn ch-btn-secondary ch-btn-sm"
                                                        onclick="chToggleReplyForm(<?php echo (int) $cm->id; ?>)">Cancel</button>
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
                                                                <span
                                                                    class="ch-comment-time"><?php echo human_time_diff(strtotime($reply->created_at), current_time('timestamp')); ?>
                                                                    ago</span>
                                                            </div>
                                                            <p><?php echo ch_highlight_mentions(nl2br(esc_html($reply->content))); ?></p>
                                                            <?php if ($user_id): ?>
                                                                <div class="ch-comment-actions">
                                                                    <?php if (($reply->user_id === 0 || $reply->user_id != $user_id) && !current_user_can('manage_options')): ?>
                                                                        <button class="ch-comment-action"
                                                                            onclick="chReportComment(<?php echo (int) $reply->id; ?>, '<?php echo esc_attr($nonce); ?>')">Report</button>
                                                                    <?php endif; ?>
                                                                    <?php if ($reply->user_id !== 0 && $reply->user_id == $user_id || current_user_can('manage_options')): ?>
                                                                        <button class="ch-comment-action"
                                                                            onclick="chEditComment(<?php echo $reply->id; ?>)">Edit</button>
                                                                        <button class="ch-comment-action ch-danger-action"
                                                                            onclick="chDeleteComment(<?php echo $reply->id; ?>, '<?php echo esc_attr($nonce); ?>', this)">Delete</button>
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
                            <span class="ch-cat-badge"
                                style="background:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#FF7551'); ?>">
                                <?php echo esc_html($post->cat_name ?? 'General'); ?>
                            </span>
                        </div>
                        <div class="ch-info-item">
                            <span class="ch-info-label">Score</span>
                            <strong><?php echo (int) $post->vote_count; ?> points</strong>
                        </div>
                        <div class="ch-info-item">
                            <span class="ch-info-label">Comments</span>
                            <strong><?php echo (int) $post->comment_count; ?></strong>
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

    <?php if ($user_id): ?>
        <?php
        echo ch_render_post_composer_modal([
            'mode' => 'create',
            'modal_id' => 'ch-modal-create-post',
            'modal_title' => 'Create Post',
            'user_id' => $user_id,
            'display_name' => wp_get_current_user()->display_name ?: 'You',
            'avatar_url' => $profile->avatar_url ?? '',
            'categories' => $categories,
            'submit_nonce' => wp_create_nonce('ch_feed_nonce'),
            'submit_handler' => 'chSubmitPost',
            'submit_label' => 'Post',
            'hint_text' => 'Be respectful and follow community guidelines.',
            'message_id' => 'ch-post-msg',
            'category_id' => 'ch-post-cat',
            'title_id' => 'ch-post-title',
            'content_id' => 'ch-post-content',
            'tags_id' => 'ch-post-tags',
            'anon_id' => 'ch-post-anon',
            'media_input_id' => 'ch-post-media',
            'media_label_id' => 'ch-post-media-label',
            'media_preview_id' => 'ch-post-media-preview',
            'title_placeholder' => 'Write a title...',
            'content_placeholder' => "What's on your mind? Share your thoughts, concerns, or suggestions...",
        ]);
        echo ch_render_post_composer_modal([
            'mode' => 'edit',
            'modal_id' => 'ch-modal-edit-post',
            'modal_title' => 'Edit Post',
            'user_id' => $user_id,
            'display_name' => wp_get_current_user()->display_name ?: 'You',
            'avatar_url' => $profile->avatar_url ?? '',
            'categories' => $categories,
            'submit_nonce' => $nonce,
            'submit_handler' => 'chUpdatePost',
            'submit_label' => 'Save Changes',
            'hint_text' => 'Edit your post. Changes are visible immediately.',
            'message_id' => 'ch-edit-post-msg',
            'category_id' => 'ch-edit-post-cat',
            'title_id' => 'ch-edit-post-title',
            'content_id' => 'ch-edit-post-content',
            'tags_id' => 'ch-edit-post-tags',
            'anon_id' => 'ch-edit-post-anon',
            'media_input_id' => 'ch-edit-post-media',
            'media_label_id' => 'ch-edit-post-media-label',
            'media_preview_id' => 'ch-edit-post-media-preview',
            'show_hidden_post_id' => true,
            'post_id_input' => 'ch-edit-post-id',
        ]);
    ?>
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
                    <textarea id="ch-report-details" class="ch-input ch-textarea" rows="3"
                        placeholder="Provide more context..."></textarea>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-report')">Cancel</button>
                <button class="ch-btn ch-btn-danger" onclick="chSubmitReport('<?php echo esc_attr($nonce); ?>')">Submit
                    Report</button>
            </div>
            <div id="ch-report-msg"></div>
        </div>
    </div>

    <script>
        window.chCurrentPost = {
            id: <?php echo (int) $post->id; ?>,
            category_id: <?php echo $post->category_id; ?>,
            title: '<?php echo addslashes($post->title); ?>',
            content: '<?php echo addslashes($post->content); ?>',
            tags: '<?php echo addslashes($post->tags); ?>',
            is_anonymous: <?php echo $post->is_anonymous; ?>,
            media_urls: '<?php echo addslashes($post->media_urls); ?>',
            edit_comment_nonce: '<?php echo wp_create_nonce('ch_post_view_nonce'); ?>'
        };
    </script>


    <?php
    ch_output_global_styles_fallback();
    echo ch_global_scripts();
    echo ch_feed_scripts();
    echo ch_post_view_scripts();
    return ob_get_clean();
}

// ============================================================
// CONTENT-ONLY TAB HELPERS (for AJAX filter reloads)
// ============================================================

function _ch_extract_content_html($full_html)
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $full_html . '</div>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    // Find the main content card (ch-card div)
    $card_nodes = $xpath->query("//div[contains(@class, 'ch-card')]");

    if ($card_nodes->length > 0) {
        $card = $card_nodes->item(0);
        // Return inner HTML of the card (without the ch-card wrapper)
        $inner_html = '';
        foreach ($card->childNodes as $node) {
            $inner_html .= $dom->saveHTML($node);
        }
        return $inner_html;
    }
    return $full_html;
}

function ch_admin_overview_tab_content($user_id, $is_admin)
{
    return _ch_extract_content_html(ch_admin_overview_tab($user_id, $is_admin));
}

function ch_categories_tab_content($user_id, $is_admin)
{
    return _ch_extract_content_html(ch_categories_tab($user_id, $is_admin));
}

function ch_posts_tab_content($user_id, $is_admin)
{
    return _ch_extract_content_html(ch_posts_tab($user_id, $is_admin));
}

function ch_users_tab_content($user_id, $is_admin)
{
    return _ch_extract_content_html(ch_users_tab($user_id, $is_admin));
}

function ch_reports_tab_content($user_id, $is_admin)
{
    return _ch_extract_content_html(ch_reports_tab($user_id, $is_admin));
}

function ch_moderation_tab_content($user_id)
{
    return _ch_extract_content_html(ch_moderation_tab($user_id));
}

function ch_activity_tab_content($user_id)
{
    return _ch_extract_content_html(ch_activity_tab($user_id));
}

function ch_announcements_tab_content()
{
    return _ch_extract_content_html(ch_announcements_tab());
}

require_once __DIR__ . '/ajax.php';
require_once __DIR__ . '/assets.php';
