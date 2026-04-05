<?php
if (!defined('ABSPATH')) exit;

// ============================================================
// MODULE CONFIGURATION FUNCTIONS
// ============================================================

function bntm_kbf_get_pages() {
    return [
        'Fundora: Landing' => '[kbf_landing]',
        'Fundora: User'  => '[kbf_dashboard]',
        'Fundora: Terms' => '[kbf_terms]',
        'Fundora: Privacy Policy' => '[kbf_privacy]',
        'Fundora: Refund Policy' => '[kbf_refund]',
        'Fundora: Admin' => '[kbf_admin]',
        'Fundora: Sign In' => '[kbf_signin]',
        'Fundora: Sign Up' => '[kbf_signup]',
    ];
}

function bntm_kbf_get_page_slug_map() {
    return [
        'Fundora: Landing' => 'fundora',
        'Fundora: User'    => 'fundora-user',
        'Fundora: Terms'   => 'fundora-terms',
        'Fundora: Privacy Policy' => 'fundora-privacy',
        'Fundora: Refund Policy' => 'fundora-refund',
        'Fundora: Admin'   => 'fundora-admin',
        'Fundora: Sign In' => 'fundora-sign-in',
        'Fundora: Sign Up' => 'fundora-sign-up',
    ];
}

function bntm_kbf_get_shortcode_slug_map() {
    return [
        'kbf_landing'  => 'fundora',
        'kbf_dashboard'=> 'fundora-user',
        'kbf_terms'    => 'fundora-terms',
        'kbf_privacy'  => 'fundora-privacy',
        'kbf_refund'   => 'fundora-refund',
        'kbf_admin'    => 'fundora-admin',
        'kbf_signin'   => 'fundora-sign-in',
        'kbf_signup'   => 'fundora-sign-up',
    ];
}

function bntm_kbf_should_use_fullwidth_template($title) {
    return in_array($title, ['Fundora: User', 'Fundora: Admin'], true);
}

// Ensure required pages exist and have the correct shortcodes.
add_action('admin_init', 'bntm_kbf_ensure_pages');
function bntm_kbf_ensure_pages() {
    if (!current_user_can('manage_options')) return;
    $pages = bntm_kbf_get_pages();
    foreach ($pages as $title => $shortcode) {
        $shortcode_tag = trim($shortcode, '[]');
        $force_fullwidth = bntm_kbf_should_use_fullwidth_template($title);

        // 1) Prefer an existing page that already contains the shortcode.
        $existing = get_posts([
            'post_type'   => 'page',
            'post_status' => ['publish','draft','private'],
            'numberposts' => -1,
            's'           => '[' . $shortcode_tag . ']',
        ]);
        $page = null;
        foreach ($existing as $p) {
            if (has_shortcode($p->post_content, $shortcode_tag)) { $page = $p; break; }
        }
        if ($page) {
            if ($page->post_title !== $title) {
                wp_update_post(['ID' => $page->ID, 'post_title' => $title]);
            }
            $slug_map = bntm_kbf_get_page_slug_map();
            if (isset($slug_map[$title]) && $page->post_name !== $slug_map[$title]) {
                wp_update_post(['ID' => $page->ID, 'post_name' => $slug_map[$title]]);
            }
            if ($page->post_status !== 'publish') {
                wp_update_post(['ID' => $page->ID, 'post_status' => 'publish']);
            }
            if ($force_fullwidth) {
                update_post_meta($page->ID, '_wp_page_template', 'kbf-fullwidth.php');
            }
            continue;
        }

        // 2) If a page with the desired title exists but is empty, inject the shortcode.
        $title_page = get_page_by_title($title, OBJECT, 'page');
        if ($title_page) {
            $content = $title_page->post_content;
            if (!has_shortcode($content, $shortcode_tag) && trim(wp_strip_all_tags($content)) === '') {
                wp_update_post(['ID' => $title_page->ID, 'post_content' => $shortcode]);
            }
            $slug_map = bntm_kbf_get_page_slug_map();
            if (isset($slug_map[$title]) && $title_page->post_name !== $slug_map[$title]) {
                wp_update_post(['ID' => $title_page->ID, 'post_name' => $slug_map[$title]]);
            }
            if ($title_page->post_status !== 'publish') {
                wp_update_post(['ID' => $title_page->ID, 'post_status' => 'publish']);
            }
            if ($force_fullwidth) {
                update_post_meta($title_page->ID, '_wp_page_template', 'kbf-fullwidth.php');
            }
            continue;
        }

        // 3) Otherwise, create the page.
        $new_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $shortcode,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
        $slug_map = bntm_kbf_get_page_slug_map();
        if (!is_wp_error($new_id) && isset($slug_map[$title])) {
            wp_update_post(['ID' => $new_id, 'post_name' => $slug_map[$title]]);
        }
        if ($force_fullwidth && !is_wp_error($new_id)) {
            update_post_meta($new_id, '_wp_page_template', 'kbf-fullwidth.php');
        }
    }
}

// One-time slug migration for existing pages that still use old slugs (e.g., konekbayan-*)
add_action('init', function() {
    if (!current_user_can('manage_options')) return;
    $flag = get_option('kbf_slug_migration_fundora_v2', '');
    if ($flag === 'done') return;

    $legacy_slug_map = [
        'konekbayan'          => ['shortcode' => 'kbf_landing',  'new_slug' => 'fundora',         'title' => 'Fundora: Landing'],
        'konekbayan-landing'  => ['shortcode' => 'kbf_landing',  'new_slug' => 'fundora',         'title' => 'Fundora: Landing'],
        'konekbayan-user'     => ['shortcode' => 'kbf_dashboard','new_slug' => 'fundora-user',    'title' => 'Fundora: User'],
        'konekbayan-terms'    => ['shortcode' => 'kbf_terms',    'new_slug' => 'fundora-terms',   'title' => 'Fundora: Terms'],
        'konekbayan-admin'    => ['shortcode' => 'kbf_admin',    'new_slug' => 'fundora-admin',   'title' => 'Fundora: Admin'],
        'konekbayan-sign-in'  => ['shortcode' => 'kbf_signin',   'new_slug' => 'fundora-sign-in', 'title' => 'Fundora: Sign In'],
        'konekbayan-sign-up'  => ['shortcode' => 'kbf_signup',   'new_slug' => 'fundora-sign-up', 'title' => 'Fundora: Sign Up'],
    ];
    foreach ($legacy_slug_map as $old_slug => $meta) {
        $p = get_page_by_path($old_slug);
        if ($p) {
            if ($p->post_title !== $meta['title']) {
                wp_update_post(['ID' => $p->ID, 'post_title' => $meta['title']]);
            }
            if ($p->post_name !== $meta['new_slug']) {
                wp_update_post(['ID' => $p->ID, 'post_name' => $meta['new_slug']]);
            }
            if (!has_shortcode($p->post_content, $meta['shortcode'])) {
                wp_update_post(['ID' => $p->ID, 'post_content' => '[' . $meta['shortcode'] . ']']);
            }
        }
    }

    $map = bntm_kbf_get_shortcode_slug_map();
    foreach ($map as $shortcode => $slug) {
        $pages = get_posts([
            'post_type'   => 'page',
            'post_status' => ['publish','draft','private'],
            'numberposts' => -1,
            's'           => '[' . $shortcode . ']',
        ]);
        foreach ($pages as $p) {
            if (has_shortcode($p->post_content, $shortcode) && $p->post_name !== $slug) {
                wp_update_post(['ID' => $p->ID, 'post_name' => $slug]);
            }
        }
    }
    update_option('kbf_slug_migration_fundora_v2', 'done');
});

// Register custom full-width page template from the plugin.
add_filter('theme_page_templates', function($templates){
    $templates['kbf-fullwidth.php'] = 'KBF Full Width (KonekBayan)';
    return $templates;
});

// Use the plugin template file when selected.
add_filter('template_include', function($template){
    if (!is_page()) return $template;
    $page_id = get_queried_object_id();
    if (!$page_id) return $template;
    $custom = BNTM_KBF_PATH . 'templates/kbf-fullwidth.php';
    if (!file_exists($custom)) return $template;

    $tpl = get_page_template_slug($page_id);
    if ($tpl === 'kbf-fullwidth.php') return $custom;

    $content = get_post_field('post_content', $page_id);
    if (!$content) return $template;
    if (has_shortcode($content, 'kbf_dashboard') || has_shortcode($content, 'kbf_admin')) {
        return $custom;
    }
    return $template;
});

add_filter('body_class', function($classes){
    if (!is_page()) return $classes;
    $page_id = get_queried_object_id();
    if (!$page_id) return $classes;
    $content = get_post_field('post_content', $page_id);
    $tpl = get_page_template_slug($page_id);
    if ($tpl === 'kbf-fullwidth.php' || has_shortcode($content, 'kbf_dashboard') || has_shortcode($content, 'kbf_admin')) {
        $classes[] = 'kbf-fullwidth-page';
    }
    return $classes;
});

add_action('wp_head', function(){
    if (!is_page()) return;
    $page_id = get_queried_object_id();
    if (!$page_id) return;
    $content = get_post_field('post_content', $page_id);
    $tpl = get_page_template_slug($page_id);
    if ($tpl !== 'kbf-fullwidth.php' && !has_shortcode($content, 'kbf_dashboard') && !has_shortcode($content, 'kbf_admin')) {
        return;
    }
    ?>
    <style>
    body.kbf-fullwidth-page,
    body.kbf-fullwidth-page #page,
    body.kbf-fullwidth-page .wp-site-blocks,
    body.kbf-fullwidth-page .wp-block-group,
    body.kbf-fullwidth-page .wp-block-group__inner-container,
    body.kbf-fullwidth-page .is-layout-constrained,
    body.kbf-fullwidth-page .wp-block-post-content,
    body.kbf-fullwidth-page .entry-content,
    body.kbf-fullwidth-page .site-content,
    body.kbf-fullwidth-page .content-area,
    body.kbf-fullwidth-page .wp-site-blocks > * {
        width: 100% !important;
        max-width: none !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        margin-top: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0 !important;
    }
    body.kbf-fullwidth-page .bntm-container,
    body.kbf-fullwidth-page .bntm-content{
        width: 100% !important;
        max-width: none !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        margin-top: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0 !important;
    }
    </style>
    <?php
}, 99);

