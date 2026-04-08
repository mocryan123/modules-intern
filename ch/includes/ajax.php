<?php
if (!defined('ABSPATH')) exit;
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
    $require_post_approval = (int)(!empty($_POST['require_post_approval']));

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
        'require_post_approval' => $require_post_approval,
    ], ['%s','%d','%s','%s','%s','%s','%d','%d','%d']);

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
        ch_flush_overview_cache();
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
    $require_post_approval = (int)(!empty($_POST['require_post_approval']));
    $table = "{$wpdb->prefix}ch_categories";
    // Cache schema introspection — avoids DESC on every edit request
    $has_post_approval_col = get_transient('ch_has_post_approval_col');
    if ($has_post_approval_col === false) {
        $columns = $wpdb->get_col("DESC {$table}", 0);
        $has_post_approval_col = in_array('require_post_approval', $columns, true) ? '1' : '0';
        set_transient('ch_has_post_approval_col', $has_post_approval_col, DAY_IN_SECONDS);
    }
    $has_post_approval_col = $has_post_approval_col === '1';

    $select_fields = ['name', 'description', 'color', 'slug', 'is_private'];
    if ($has_post_approval_col) {
        $select_fields[] = 'require_post_approval';
    }

    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT " . implode(', ', $select_fields) . " FROM {$table} WHERE id = %d",
        $id
    ));

    if (!$existing) {
        wp_send_json_error(['message' => 'Category not found']);
    }

    $update_data = [];
    $update_format = [];
    $next_slug = sanitize_title($name);

    if ((string)$existing->name !== (string)$name) {
        $update_data['name'] = $name;
        $update_format[] = '%s';
    }
    if ((string)($existing->description ?? '') !== (string)$desc) {
        $update_data['description'] = $desc;
        $update_format[] = '%s';
    }
    if (strtoupper((string)$existing->color) !== strtoupper((string)$color)) {
        $update_data['color'] = $color;
        $update_format[] = '%s';
    }
    if ((string)$existing->slug !== (string)$next_slug) {
        $update_data['slug'] = $next_slug;
        $update_format[] = '%s';
    }
    if ((int)$existing->is_private !== $is_private) {
        $update_data['is_private'] = $is_private;
        $update_format[] = '%d';
    }
    if ($has_post_approval_col && (int)($existing->require_post_approval ?? 0) !== $require_post_approval) {
        $update_data['require_post_approval'] = $require_post_approval;
        $update_format[] = '%d';
    }

    if (empty($update_data)) {
        wp_send_json_error(['message' => 'No changes made']);
    }

    $result = $wpdb->update(
        $table,
        $update_data,
        ['id' => $id],
        $update_format,
        ['%d']
    );

    if ($result !== false) {
        ch_flush_overview_cache();
        ch_log_activity('edit_category', 'category', $id, "Updated category: $name");
        wp_send_json_success(['message' => 'Category updated!']);
    } else {
        wp_send_json_error(['message' => $wpdb->last_error ?: 'Failed to update category']);
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
        ch_flush_overview_cache();
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
        ch_flush_overview_cache();
        ch_log_activity('toggle_category_status', 'category', $id, "Status changed to $new");
        wp_send_json_success(['status' => $new]);
    } else {
        wp_send_json_error(['message' => 'Failed to update status']);
    }
}

function bntm_ajax_ch_create_post() {
    global $wpdb;
    $user_id    = get_current_user_id();

    // Verify nonce first for logged-in users before any DB work
    if ($user_id) {
        check_ajax_referer('ch_feed_nonce', 'nonce');
    }

    $title      = sanitize_text_field($_POST['title'] ?? '');
    $content    = sanitize_textarea_field($_POST['content'] ?? '');
    $cat_input  = sanitize_text_field($_POST['category_id'] ?? '');
    $cat_id     = (int)$cat_input;
    $tags       = sanitize_text_field($_POST['tags'] ?? '');
    $is_anon    = (int)(!empty($_POST['is_anonymous']));
    $guest_name = sanitize_text_field($_POST['guest_name'] ?? '');
    $media_limit = max(1, min(20, (int)get_option('ch_media_upload_limit', 6)));

    if ($cat_id <= 0 && $cat_input !== '') {
        $cat_id = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ch_categories WHERE (slug = %s OR name = %s) AND status = 'active' LIMIT 1",
            sanitize_title($cat_input),
            $cat_input
        ));
    }

    if (!$title || !$content || !$cat_id) {
        wp_send_json_error(['message' => 'Title, content, and category are required']);
    }

    // Ban check for logged-in users
    if ($user_id) {
        $profile_row = $wpdb->get_row($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id
        ));
        if ($profile_row && in_array($profile_row->status, ['banned', 'suspended'])) {
            wp_send_json_error(['message' => 'Your account is restricted from posting']);
        }
        ch_ensure_profile($user_id);
    }

    // Cache schema introspection — avoids SHOW COLUMNS on every post request
    $has_post_approval_col = get_transient('ch_has_post_approval_col');
    if ($has_post_approval_col === false) {
        $category_cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ch_categories");
        $has_post_approval_col = in_array('require_post_approval', $category_cols, true) ? '1' : '0';
        set_transient('ch_has_post_approval_col', $has_post_approval_col, DAY_IN_SECONDS);
    }
    $has_post_approval_col = $has_post_approval_col === '1';
    $category_select = $has_post_approval_col
        ? "SELECT id, is_private, require_post_approval, status FROM {$wpdb->prefix}ch_categories"
        : "SELECT id, is_private, 0 AS require_post_approval, status FROM {$wpdb->prefix}ch_categories";

    // Check category exists and treat legacy empty/null status as active.
    $cat = $wpdb->get_row($wpdb->prepare(
        "{$category_select}
         WHERE id = %d AND (status = 'active' OR status = '' OR status IS NULL)",
        $cat_id
    ));
    if (!$cat) {
        $category_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ch_categories WHERE id = %d",
            $cat_id
        ));
        if ($category_exists) {
            wp_send_json_error(['message' => 'This category is unavailable for posting right now.']);
        }
        wp_send_json_error(['message' => 'Category not found']);
    }

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
    }
    // Guests posting to public categories: no nonce needed, guest_name used as display name

    $rand_id = bntm_rand_id();
    $global_requires_approval = (int)get_option('ch_post_approval_enabled', 0);
    $category_requires_approval = (int)($cat->require_post_approval ?? 0);
    $status  = ($global_requires_approval || $category_requires_approval) ? 'pending' : 'active';
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
        $upload_result = ch_process_uploaded_media();
        if (count($upload_result['urls']) > $media_limit) {
            $wpdb->delete("{$wpdb->prefix}ch_posts", ['id' => $post_id], ['%d']);
            wp_send_json_error(['message' => "You can upload up to {$media_limit} files per post."]);
        }
        if ($upload_result['urls']) {
            $wpdb->update("{$wpdb->prefix}ch_posts", ['media_urls' => wp_json_encode($upload_result['urls'])], ['id' => $post_id], ['%s'], ['%d']);
        }
        if ($upload_result['had_files'] && !$upload_result['urls']) {
            $wpdb->delete("{$wpdb->prefix}ch_posts", ['id' => $post_id], ['%d']);
            wp_send_json_error(['message' => implode(' ', $upload_result['errors']) ?: 'Image upload failed. Please try a smaller JPG or PNG image.']);
        }

        // Update post count and category post count
        if ($user_id) {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_user_profiles SET post_count = post_count + 1, karma_points = karma_points + 2 WHERE user_id = %d", $user_id));
        }
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_categories SET post_count = post_count + 1 WHERE id = %d", $cat_id));
        ch_flush_overview_cache();
        $success_message = $status === 'pending'
            ? 'Post submitted for approval.'
            : 'Post created!';
        wp_send_json_success(['message' => $success_message, 'rand_id' => $rand_id, 'status' => $status]);
    } else {
        $db_error = trim((string)$wpdb->last_error);
        wp_send_json_error(['message' => $db_error ? 'Failed to create post: ' . $db_error : 'Failed to create post']);
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
    $kept_existing_media = json_decode(wp_unslash($_POST['existing_media_urls'] ?? '[]'), true);
    $kept_existing_media = is_array($kept_existing_media) ? array_values(array_filter(array_map('esc_url_raw', $kept_existing_media))) : [];
    $media_limit = max(1, min(20, (int)get_option('ch_media_upload_limit', 6)));

    if (!$post_id || !$title || !$content || !$cat_id) wp_send_json_error(['message' => 'All fields are required']);

    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ch_posts WHERE id = %d", $post_id));
    if (!$post) wp_send_json_error(['message' => 'Post not found']);
    $category_owner_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT business_id FROM {$wpdb->prefix}ch_categories WHERE id = %d",
        (int)$post->category_id
    ));
    $can_delete_post = (
        (int)$post->user_id === (int)$user_id ||
        current_user_can('manage_options') ||
        $category_owner_id === (int)$user_id
    );
    if (!$can_delete_post) wp_send_json_error(['message' => 'Unauthorized']);

    $result = $wpdb->update("{$wpdb->prefix}ch_posts", [
        'title'        => $title,
        'content'      => $content,
        'category_id'  => $cat_id,
        'tags'         => $tags,
        'is_anonymous' => $is_anon,
    ], ['id' => $post_id], ['%s','%s','%d','%s','%d'], ['%d']);

    if ($result !== false) {
        // Handle media uploads if any
        $upload_result = ch_process_uploaded_media();
        if ($upload_result['had_files'] && !$upload_result['urls']) {
            wp_send_json_error(['message' => implode(' ', $upload_result['errors']) ?: 'Image upload failed. Please try a smaller JPG or PNG image.']);
        }
        $final_media = array_values(array_filter(array_merge($kept_existing_media, $upload_result['urls'])));
        if (count($final_media) > $media_limit) {
            wp_send_json_error(['message' => "You can upload up to {$media_limit} files per post."]);
        }
        $original_media = json_decode($post->media_urls, true);
        $original_media = is_array($original_media) ? array_values(array_filter($original_media)) : [];
        $media_changed = wp_json_encode($final_media) !== wp_json_encode($original_media);
        if ($media_changed) {
            $media_update = $wpdb->update("{$wpdb->prefix}ch_posts", ['media_urls' => wp_json_encode($final_media)], ['id' => $post_id], ['%s'], ['%d']);
            if ($media_update === false) {
                wp_send_json_error(['message' => 'Failed to update post media']);
            }
        }

        ch_flush_overview_cache();
        wp_send_json_success(['message' => 'Post updated!']);
    } else {
        wp_send_json_error(['message' => 'No changes made']);
    }
}

function bntm_ajax_ch_delete_post() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    if (!wp_verify_nonce($nonce, 'ch_post_nonce') && !wp_verify_nonce($nonce, 'ch_post_view_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $post_id = (int)($_POST['post_id'] ?? 0);

    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ch_posts WHERE id = %d", $post_id));
    if (!$post) wp_send_json_error(['message' => 'Post not found']);
    $category_owner_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT business_id FROM {$wpdb->prefix}ch_categories WHERE id = %d",
        (int)$post->category_id
    ));
    if ((int)$post->user_id !== (int)$user_id && !current_user_can('manage_options') && $category_owner_id !== (int)$user_id) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $deleted = $wpdb->update("{$wpdb->prefix}ch_posts", ['status' => 'removed'], ['id' => $post_id], ['%s'], ['%d']);
    if ($deleted === false) {
        wp_send_json_error(['message' => 'Failed to delete post']);
    }
    ch_flush_overview_cache();
    wp_send_json_success(['message' => 'Post deleted']);
}

function bntm_ajax_ch_get_posts() {
    global $wpdb;
    $cat_id = (int)($_POST['category_id'] ?? 0);
    $sort   = sanitize_text_field($_POST['sort'] ?? 'new');
    $page   = max(1, (int)($_POST['page'] ?? 1));

    // Whitelist sort column — never interpolate user input into ORDER BY
    $order  = $sort === 'top' ? 'vote_count DESC' : 'created_at DESC';
    $offset = ($page - 1) * 15;

    if ($cat_id > 0) {
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ch_posts
                 WHERE status = 'active' AND category_id = %d
                 ORDER BY {$order} LIMIT 15 OFFSET %d",
                $cat_id,
                $offset
            )
        );
    } else {
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ch_posts
                 WHERE status = 'active'
                 ORDER BY {$order} LIMIT 15 OFFSET %d",
                $offset
            )
        );
    }

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
        ch_flush_overview_cache();

        // Process mentions
        $mentioned_users = ch_extract_mentions($content);
        foreach ($mentioned_users as $mentioned_user_id) {
            if ($mentioned_user_id != $user_id) { // Don't notify self-mentions
                ch_create_notification($mentioned_user_id, 'mention', $user_id, $post_id, $comment_id);
            }
        }

        // Notify post author using the post row we already loaded above.
        if ($post_row->user_id && $post_row->user_id != $user_id) {
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
    ch_flush_overview_cache();

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
    $current_count = (int) $wpdb->get_var($wpdb->prepare("SELECT vote_count FROM $table WHERE id = %d", $target_id));

    if ($existing) {
        if ($existing->value == $value) {
            // Remove vote (toggle off)
            $wpdb->delete("{$wpdb->prefix}ch_votes", ['id' => $existing->id], ['%d']);
            $wpdb->query($wpdb->prepare("UPDATE $table SET vote_count = vote_count - %d WHERE id = %d", $value, $target_id));
            $new_count = $current_count - $value;
            wp_send_json_success(['vote_count' => $new_count, 'action' => 'removed']);
        } else {
            // Change vote
            $wpdb->update("{$wpdb->prefix}ch_votes", ['value' => $value], ['id' => $existing->id], ['%d'], ['%d']);
            $diff = $value * 2;
            $wpdb->query($wpdb->prepare("UPDATE $table SET vote_count = vote_count + %d WHERE id = %d", $diff, $target_id));
            $new_count = $current_count + $diff;
            wp_send_json_success(['vote_count' => $new_count, 'action' => 'changed']);
        }
    } else {
        $wpdb->insert("{$wpdb->prefix}ch_votes", [
            'user_id' => $user_id, 'target_type' => $target_type, 'target_id' => $target_id, 'value' => $value
        ], ['%d', '%s', '%d', '%d']);
        $wpdb->query($wpdb->prepare("UPDATE $table SET vote_count = vote_count + %d WHERE id = %d", $value, $target_id));
        $new_count = $current_count + $value;

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
        ch_flush_overview_cache();

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
            ch_flush_overview_cache();
            ch_log_activity('approve_post', 'post', $target_id, 'Post approved for publication');
            wp_send_json_success(['message' => 'Post approved']);
            break;

        case 'reject_post':
            $wpdb->update("{$wpdb->prefix}ch_posts", ['status' => 'removed'], ['id' => $target_id], ['%s'], ['%d']);
            ch_flush_overview_cache();
            ch_log_activity('reject_post', 'post', $target_id, 'Post rejected');
            wp_send_json_success(['message' => 'Post rejected']);
            break;

        case 'remove_post':
            if ($reason === '') {
                wp_send_json_error(['message' => 'Reason is required when removing a post.']);
            }
            $wpdb->update("{$wpdb->prefix}ch_posts", ['status' => 'removed'], ['id' => $target_id], ['%s'], ['%d']);
            ch_flush_overview_cache();
            ch_log_activity('remove_post', 'post', $target_id, 'Post removed by admin: ' . $reason);
            wp_send_json_success(['message' => 'Post removed']);
            break;

        case 'remove_comment':
            $wpdb->update("{$wpdb->prefix}ch_comments", ['status' => 'removed'], ['id' => $target_id], ['%s'], ['%d']);
            ch_flush_overview_cache();
            ch_log_activity('remove_comment', 'comment', $target_id, 'Comment removed by admin');
            wp_send_json_success(['message' => 'Comment removed']);
            break;

        case 'restore_post':
            $wpdb->update("{$wpdb->prefix}ch_posts", ['status' => 'active'], ['id' => $target_id], ['%s'], ['%d']);
            ch_flush_overview_cache();
            ch_log_activity('restore_post', 'post', $target_id, 'Post restored by admin');
            wp_send_json_success(['message' => 'Post restored']);
            break;

        case 'suspend_user':
            $wpdb->update("{$wpdb->prefix}ch_user_profiles", ['status' => 'suspended'], ['user_id' => $target_id], ['%s'], ['%d']);
            ch_flush_overview_cache();
            ch_log_activity('suspend_user', 'user', $target_id, 'User suspended: ' . $reason);
            wp_send_json_success(['message' => 'User suspended']);
            break;

        case 'ban_user':
            $wpdb->update("{$wpdb->prefix}ch_user_profiles", ['status' => 'banned'], ['user_id' => $target_id], ['%s'], ['%d']);
            ch_flush_overview_cache();
            ch_log_activity('ban_user', 'user', $target_id, 'User banned: ' . $reason);
            wp_send_json_success(['message' => 'User banned']);
            break;

        case 'unsuspend_user':
            $wpdb->update("{$wpdb->prefix}ch_user_profiles", ['status' => 'active'], ['user_id' => $target_id], ['%s'], ['%d']);
            ch_flush_overview_cache();
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
            ch_flush_overview_cache();
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
    ch_flush_overview_cache();
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
        "SELECT n.id, n.type, n.message, n.is_read, n.created_at, n.post_id, n.comment_id,
                p.title as post_title, p.rand_id as post_rand_id
         FROM {$wpdb->prefix}ch_notifications n
         LEFT JOIN {$wpdb->prefix}ch_posts p ON n.post_id = p.id
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

function bntm_ajax_ch_get_notification_count() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $user_id = get_current_user_id();
    $unread_count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ch_notifications WHERE user_id = %d AND is_read = 0",
        $user_id
    ));

    wp_send_json_success([
        'unread_count' => $unread_count,
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

    $terms = preg_split('/\s+/', trim($query));
    $terms = array_filter(array_map(static function($term) {
        $term = preg_replace('/[^\p{L}\p{N}_-]/u', '', $term);
        return $term !== '' ? $term . '*' : '';
    }, $terms));
    $search_query = implode(' ', $terms);
    if ($search_query === '') wp_send_json_error(['message' => 'Query too short']);

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, rand_id, title, LEFT(content,100) as excerpt, vote_count, comment_count, created_at
             FROM {$wpdb->prefix}ch_posts
             WHERE status = 'active'
               AND MATCH(title, content) AGAINST(%s IN BOOLEAN MODE)
             ORDER BY vote_count DESC
             LIMIT 10",
            $search_query
        )
    );

    wp_send_json_success(['results' => $results]);
}

function bntm_ajax_ch_update_profile() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    // Verify the nonce sent from the Save Profile button
    check_ajax_referer('ch_profile_nonce', 'nonce');

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
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $file = [
            'name'     => $_FILES['avatar']['name'],
            'type'     => $_FILES['avatar']['type'],
            'tmp_name' => $_FILES['avatar']['tmp_name'],
            'error'    => $_FILES['avatar']['error'],
            'size'     => $_FILES['avatar']['size'],
        ];
        $upload = wp_handle_upload($file, ['test_form' => false]);
        if (!isset($upload['error'])) {
            $update_data['avatar_url'] = $upload['url'];
        }
    }

    $result = $wpdb->update(
        "{$wpdb->prefix}ch_user_profiles",
        $update_data,
        ['user_id' => $user_id],
        array_fill(0, count($update_data), '%s'),
        ['%d']
    );

    // $result is 0 when data is unchanged (not false), so treat both 0 and >0 as success
    if ($result !== false) {
        ch_flush_overview_cache();
        wp_send_json_success(['message' => 'Profile updated!', 'avatar_url' => $update_data['avatar_url'] ?? '']);
    } else {
        wp_send_json_error(['message' => 'Failed to save profile. Please try again.']);
    }
}

function bntm_ajax_ch_admin_stats() {
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $stats = ch_get_overview_counts();
    $new_users_week = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ch_user_profiles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );

    wp_send_json_success([
        'posts_today'    => (int)($stats['posts_today'] ?? 0),
        'comments_today' => (int)($stats['comments_today'] ?? 0),
        'new_users_week' => $new_users_week,
    ]);
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
    static $checked = [];

    if (!$user_id) return false;
    if (isset($checked[$user_id])) return true;

    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id));
    if (!$exists) {
        $user = get_userdata($user_id);
        if (!$user) return false;
        $wpdb->insert("{$wpdb->prefix}ch_user_profiles", [
            'user_id'      => $user_id,
            'display_name' => $user->display_name ?: $user->user_login,
        ], ['%d','%s']);
        ch_flush_overview_cache();
    }
    $checked[$user_id] = true;
    return true;
}

function ch_flush_overview_cache() {
    delete_transient('ch_overview_counts');
    delete_transient('ch_moderation_stats');
    delete_transient('ch_available_locations');
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

/**
 * Insert announcement notifications for every user in a single bulk SQL query
 * instead of looping get_users() + one INSERT per user.
 * On a site with 1 000 users this is 1 query vs 1 001 queries.
 */
function ch_bulk_announcement_notifications($announcement_id, $actor_id) {
    global $wpdb;
    $rand_prefix = substr(md5(uniqid('', true)), 0, 8);
    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}ch_notifications
                 (rand_id, user_id, type, actor_id, post_id, comment_id, created_at)
             SELECT
                 CONCAT(%s, LPAD(ID, 8, '0')),
                 ID,
                 'announcement',
                 %d,
                 %d,
                 0,
                 NOW()
             FROM {$wpdb->users}
             WHERE ID NOT IN (
                 SELECT user_id FROM {$wpdb->prefix}ch_notifications
                 WHERE type = 'announcement' AND post_id = %d
             )",
            $rand_prefix,
            (int)$actor_id,
            (int)$announcement_id,
            (int)$announcement_id
        )
    );
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
    $raw_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    // Validate as a real IP address; fall back to empty string if spoofed/malformed
    $ip = filter_var($raw_ip, FILTER_VALIDATE_IP) ? $raw_ip : '';
    $wpdb->insert("{$wpdb->prefix}ch_activity_logs", [
        'admin_id'    => get_current_user_id(),
        'action'      => $action,
        'target_type' => $target_type,
        'target_id'   => $target_id,
        'details'     => $details,
        'ip_address'  => $ip,
    ], ['%d','%s','%s','%d','%s','%s']);
}

function ch_normalize_uploaded_files($field_names = ['media', 'media[]']) {
    foreach ($field_names as $field_name) {
        if (empty($_FILES[$field_name])) {
            continue;
        }

        $file_group = $_FILES[$field_name];
        $names = $file_group['name'] ?? [];
        if ($names === '' || $names === null) {
            continue;
        }

        if (!is_array($names)) {
            return [[
                'name' => $file_group['name'] ?? '',
                'type' => $file_group['type'] ?? '',
                'tmp_name' => $file_group['tmp_name'] ?? '',
                'error' => $file_group['error'] ?? UPLOAD_ERR_NO_FILE,
                'size' => $file_group['size'] ?? 0,
            ]];
        }

        $normalized = [];
        $count = count($names);
        for ($i = 0; $i < $count; $i++) {
            $normalized[] = [
                'name' => $file_group['name'][$i] ?? '',
                'type' => $file_group['type'][$i] ?? '',
                'tmp_name' => $file_group['tmp_name'][$i] ?? '',
                'error' => $file_group['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $file_group['size'][$i] ?? 0,
            ];
        }

        return $normalized;
    }

    return [];
}

function ch_process_uploaded_media($field_names = ['media', 'media[]']) {
    $uploaded_files = ch_normalize_uploaded_files($field_names);
    if (!$uploaded_files) {
        return [
            'urls' => [],
            'errors' => [],
            'had_files' => false,
        ];
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');

    $media_urls = [];
    $errors = [];

    foreach ($uploaded_files as $file) {
        $file_name = trim((string)($file['name'] ?? ''));
        $file_error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $tmp_name = trim((string)($file['tmp_name'] ?? ''));

        if ($file_name === '' || $file_error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($file_error !== UPLOAD_ERR_OK || $tmp_name === '') {
            $errors[] = $file_name !== '' ? ($file_name . ': upload failed.') : 'Upload failed.';
            continue;
        }

        $upload = wp_handle_upload($file, [
            'test_form' => false,
            'upload_error_handler' => function($unused_file, $message) {
                return ['error' => $message];
            },
        ]);

        if (!empty($upload['error'])) {
            $errors[] = $file_name . ': ' . $upload['error'];
            continue;
        }

        if (!empty($upload['url'])) {
            $media_urls[] = $upload['url'];
        }
    }

    return [
        'urls' => $media_urls,
        'errors' => $errors,
        'had_files' => true,
    ];
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

    // Bulk-insert notifications for all users — one query instead of N inserts
    if ($status == 1) {
        ch_bulk_announcement_notifications($announcement_id, get_current_user_id());
    }

    ch_flush_overview_cache();
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

    // Bulk-insert notifications if newly published
    if ($status == 1 && $current->is_active == 0) {
        ch_bulk_announcement_notifications($announcement_id, get_current_user_id());
    }

    ch_flush_overview_cache();
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
        'post_id' => $announcement_id
    ]);

    ch_flush_overview_cache();
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

    // Bulk-insert notifications if newly published
    if ($status == 1) {
        ch_bulk_announcement_notifications($announcement_id, get_current_user_id());
    }

    ch_flush_overview_cache();
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
