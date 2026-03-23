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
            color VARCHAR(10) DEFAULT '#6366f1',
            icon VARCHAR(50) DEFAULT 'forum',
            post_count INT UNSIGNED DEFAULT 0,
            follower_count INT UNSIGNED DEFAULT 0,
            sort_order INT DEFAULT 0,
            status ENUM('active','archived') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_slug (slug)
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
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_ch_get_tables();
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
    return count($tables);
}

// ============================================================
// SEED DATA
// ============================================================

function bntm_ch_seed_data() {
    global $wpdb;
    $p = $wpdb->prefix;

    // ---- Categories ----
    $categories = [
        ['name'=>'Road & Infrastructure',  'slug'=>'road-infrastructure',  'color'=>'#ef4444', 'icon'=>'road',        'desc'=>'Report road damage, potholes, broken streetlights, and other infrastructure issues.'],
        ['name'=>'Public Safety',          'slug'=>'public-safety',        'color'=>'#f59e0b', 'icon'=>'shield',      'desc'=>'Concerns about crime, illegal activities, and community safety.'],
        ['name'=>'Environment',            'slug'=>'environment',          'color'=>'#10b981', 'icon'=>'leaf',        'desc'=>'Flooding, garbage disposal, illegal dumping, and environmental hazards.'],
        ['name'=>'Water & Utilities',      'slug'=>'water-utilities',      'color'=>'#3b82f6', 'icon'=>'droplet',     'desc'=>'Water supply issues, power outages, sewage problems.'],
        ['name'=>'Health & Sanitation',    'slug'=>'health-sanitation',    'color'=>'#8b5cf6', 'icon'=>'heart',       'desc'=>'Community health concerns, sanitation, and medical access.'],
        ['name'=>'Education',              'slug'=>'education',            'color'=>'#06b6d4', 'icon'=>'book',        'desc'=>'Schools, literacy programs, and youth development.'],
        ['name'=>'General Discussion',     'slug'=>'general-discussion',   'color'=>'#6b7280', 'icon'=>'forum',       'desc'=>'Open forum for general community conversations and announcements.'],
    ];

    $cat_ids = [];
    foreach ($categories as $i => $cat) {
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}ch_categories WHERE slug=%s", $cat['slug']));
        if (!$exists) {
            $wpdb->insert("{$p}ch_categories", [
                'rand_id'     => bntm_rand_id(),
                'business_id' => 1,
                'name'        => $cat['name'],
                'slug'        => $cat['slug'],
                'description' => $cat['desc'],
                'color'       => $cat['color'],
                'icon'        => $cat['icon'],
                'sort_order'  => $i + 1,
                'status'      => 'active',
            ], ['%s','%d','%s','%s','%s','%s','%s','%d','%s']);
            $cat_ids[$cat['slug']] = $wpdb->insert_id;
        } else {
            $cat_ids[$cat['slug']] = $exists;
        }
    }

    // ---- Sample WP users (if they don't exist) ----
    $sample_users = [
        ['login'=>'maria_santos',   'email'=>'maria@example.com',   'display'=>'Maria Santos',   'location'=>'Barangay Lapasan, CDO'],
        ['login'=>'jose_reyes',     'email'=>'jose@example.com',     'display'=>'Jose Reyes',     'location'=>'Barangay Carmen, CDO'],
        ['login'=>'ana_villanueva', 'email'=>'ana@example.com',      'display'=>'Ana Villanueva', 'location'=>'Barangay Bulua, CDO'],
        ['login'=>'pedro_lim',      'email'=>'pedro@example.com',    'display'=>'Pedro Lim',      'location'=>'Barangay Macabalan, CDO'],
        ['login'=>'rosa_garcia',    'email'=>'rosa@example.com',     'display'=>'Rosa Garcia',    'location'=>'Barangay Nazareth, CDO'],
    ];

    $user_ids = [];
    foreach ($sample_users as $u) {
        $uid = username_exists($u['login']);
        if (!$uid) {
            $uid = wp_create_user($u['login'], wp_generate_password(16), $u['email']);
            if (!is_wp_error($uid)) {
                wp_update_user(['ID'=>$uid, 'display_name'=>$u['display']]);
            }
        }
        if (!is_wp_error($uid) && $uid) {
            $profile_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}ch_user_profiles WHERE user_id=%d", $uid));
            if (!$profile_exists) {
                $wpdb->insert("{$p}ch_user_profiles", [
                    'user_id'      => $uid,
                    'display_name' => $u['display'],
                    'location'     => $u['location'],
                    'karma_points' => rand(10, 150),
                    'post_count'   => 0,
                    'comment_count'=> 0,
                    'status'       => 'active',
                ], ['%d','%s','%s','%d','%d','%d','%s']);
            }
            $user_ids[] = $uid;
        }
    }
    if (empty($user_ids)) $user_ids[] = 1;

    // ---- Sample posts ----
    $posts = [
        ['cat'=>'road-infrastructure', 'title'=>'Large pothole on Kauswagan Highway near Gaisano',
         'content'=>"There is a massive pothole near the Gaisano Mall entrance on Kauswagan Highway that has been there for over 3 months. Several motorcycles have already had accidents because of this. The pothole is approximately 1 meter wide and 20cm deep.\n\nI have reported this to the barangay office twice but no action has been taken. Can anyone else who passes this area help escalate this to the city engineering office?",
         'tags'=>'road,pothole,kauswagan,urgent', 'anon'=>0, 'pinned'=>1],

        ['cat'=>'environment', 'title'=>'Illegal dumping of garbage near Cagayan River bank in Macabalan',
         'content'=>"Residents near the Cagayan River bank in Macabalan have been noticing an increase in illegal garbage dumping. Piles of household waste, construction debris, and even medical waste have been seen.\n\nThis is not only an eyesore but also a serious flood risk during rainy season as the garbage blocks water flow. We need proper signage, regular monitoring, and strict enforcement of anti-littering ordinances in this area.",
         'tags'=>'environment,garbage,river,flooding', 'anon'=>0, 'pinned'=>0],

        ['cat'=>'water-utilities', 'title'=>'No water supply for 5 days in Barangay Nazareth',
         'content'=>"Our entire street in Barangay Nazareth has had no water supply for 5 consecutive days. MCWD says they are doing maintenance but there has been no update since Monday.\n\nFamilies with young children and elderly are severely affected. We are spending money buying water daily. Please, MCWD, give us a clear timeline on when service will be restored.",
         'tags'=>'water,mcwd,nazareth,utilities', 'anon'=>0, 'pinned'=>0],

        ['cat'=>'public-safety', 'title'=>'Broken streetlights in Barangay Carmen — area is dangerously dark at night',
         'content'=>"At least 8 streetlights along the main road in Barangay Carmen have been broken for over 2 months. The area is completely dark at night, making it dangerous for pedestrians and motorists.\n\nThere have already been 2 reported snatching incidents in this area after dark. The barangay captain has been informed but no repairs have been made. Who can we contact at the city level to get this fixed faster?",
         'tags'=>'streetlight,safety,carmen,crime', 'anon'=>0, 'pinned'=>0],

        ['cat'=>'health-sanitation', 'title'=>'Foul smell from drainage canal in Lapasan — health hazard',
         'content'=>"The drainage canal running along the main road in Lapasan has not been cleaned in months. The smell is unbearable, especially in the afternoon heat. Residents worry about leptospirosis and other waterborne diseases especially with kids playing nearby.\n\nWe need the city sanitation team to schedule a proper cleaning and desilting of this canal before the rainy season begins.",
         'tags'=>'drainage,sanitation,health,lapasan', 'anon'=>0, 'pinned'=>0],

        ['cat'=>'education', 'title'=>'Requesting covered walkway for students at Bulua Elementary School',
         'content'=>"Students at Bulua Elementary School have to walk across an open area to reach their classrooms during recess and dismissal. During heavy rain, students get completely soaked and classes get disrupted.\n\nThe PTA has been requesting a covered walkway from the division office for 2 school years now with no progress. Parents are willing to contribute labor if the materials can be provided. Please help us amplify this request.",
         'tags'=>'school,education,bulua,students', 'anon'=>0, 'pinned'=>0],

        ['cat'=>'general-discussion', 'title'=>'Welcome to CivicHub CDO — let\'s build our community together!',
         'content'=>"Hello neighbors! Welcome to CivicHub, our community platform for sharing concerns, discussing local issues, and working together to improve our barangays and city.\n\nThis is a safe space to voice your concerns constructively. Remember:\n\n✅ Be respectful and factual\n✅ Provide specific locations when reporting issues\n✅ Tag your posts for better visibility\n✅ Upvote important issues to help them get attention\n\nLet's make Cagayan de Oro better, one concern at a time! 🏙️",
         'tags'=>'welcome,community,civichub', 'anon'=>0, 'pinned'=>1],
    ];

    $post_ids = [];
    foreach ($posts as $i => $p_data) {
        $cat_id  = $cat_ids[$p_data['cat']] ?? $cat_ids['general-discussion'];
        $user_id = $user_ids[$i % count($user_ids)];
        $rand_id = bntm_rand_id();
        $date    = date('Y-m-d H:i:s', strtotime("-" . (count($posts) - $i) . " days"));

        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}ch_posts WHERE title=%s", $p_data['title']));
        if ($exists) { $post_ids[] = $exists; continue; }

        $wpdb->insert("{$p}ch_posts", [
            'rand_id'      => $rand_id,
            'business_id'  => $user_id,
            'user_id'      => $user_id,
            'category_id'  => $cat_id,
            'title'        => $p_data['title'],
            'content'      => $p_data['content'],
            'tags'         => $p_data['tags'],
            'media_urls'   => '',
            'is_anonymous' => $p_data['anon'],
            'is_pinned'    => $p_data['pinned'],
            'status'       => 'active',
            'vote_count'   => rand(3, 24),
            'view_count'   => rand(20, 200),
            'created_at'   => $date,
            'updated_at'   => $date,
        ], ['%s','%d','%d','%d','%s','%s','%s','%s','%d','%d','%s','%d','%d','%s','%s']);

        $post_id = $wpdb->insert_id;
        $post_ids[] = $post_id;

        // Update category post count
        $wpdb->query($wpdb->prepare("UPDATE {$p}ch_categories SET post_count = post_count + 1 WHERE id = %d", $cat_id));
        // Update user post count
        $wpdb->query($wpdb->prepare("UPDATE {$p}ch_user_profiles SET post_count = post_count + 1 WHERE user_id = %d", $user_id));
    }

    // ---- Sample comments ----
    $comment_data = [
        [0, "I pass this road every day and it's terrible! My tire got damaged last week because of this pothole. Fully support this report.",       0],
        [0, "I've seen at least 3 motorcycle accidents near that spot. This needs to be fixed immediately before someone gets seriously hurt.",       0],
        [0, "I already filed a complaint at the City Engineering Office. They said it's in the queue. Let's keep pushing!",                          0],
        [1, "This is a huge environmental problem. The last major flood we had was partly because of clogged waterways from illegal dumping.",        0],
        [1, "I saw a dump truck unloading waste there at midnight last week. We need CCTV cameras in that area.",                                    0],
        [2, "Same problem in our street! No water for days and MCWD just says 'ongoing maintenance' with no ETA.",                                  0],
        [2, "Water tankers are available from the barangay but only in small quantities. Please coordinate with your barangay captain.",             0],
        [3, "These broken lights are a serious safety issue. I'll bring this up in the next barangay assembly.",                                     0],
        [4, "The smell has gotten worse recently. City health officials need to inspect this area for public health violations.",                     0],
        [5, "We have the same issue at our school. Covered walkways should be standard infrastructure, not an optional upgrade.",                    0],
        [6, "Great initiative! CDO residents have been needing a platform like this. Let's make our voices heard!",                                  0],
        [6, "Welcome everyone! Let's keep this community respectful and focused on solutions. 👍",                                                   0],
    ];

    foreach ($comment_data as $cd) {
        list($post_idx, $content, $is_anon) = $cd;
        $post_id = $post_ids[$post_idx] ?? ($post_ids[0] ?? 0);
        if (!$post_id) continue;
        $user_id = $user_ids[array_rand($user_ids)];
        $rand_id = bntm_rand_id();
        $date    = date('Y-m-d H:i:s', strtotime('-' . rand(1, 10) . ' hours'));

        $already = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}ch_comments WHERE post_id=%d AND content=%s", $post_id, $content));
        if ($already) continue;

        $wpdb->insert("{$p}ch_comments", [
            'rand_id'      => $rand_id,
            'business_id'  => $user_id,
            'post_id'      => $post_id,
            'user_id'      => $user_id,
            'parent_id'    => 0,
            'content'      => $content,
            'is_anonymous' => $is_anon,
            'vote_count'   => rand(0, 8),
            'status'       => 'active',
            'created_at'   => $date,
        ], ['%s','%d','%d','%d','%d','%s','%d','%d','%s','%s']);

        $wpdb->query($wpdb->prepare("UPDATE {$p}ch_posts SET comment_count = comment_count + 1 WHERE id = %d", $post_id));
        $wpdb->query($wpdb->prepare("UPDATE {$p}ch_user_profiles SET comment_count = comment_count + 1 WHERE user_id = %d", $user_id));
    }

    // ---- Sample announcement ----
    $ann_exists = $wpdb->get_var("SELECT id FROM {$p}ch_announcements LIMIT 1");
    if (!$ann_exists) {
        $wpdb->insert("{$p}ch_announcements", [
            'rand_id'   => bntm_rand_id(),
            'admin_id'  => 1,
            'title'     => '📣 Welcome to CivicHub — Your Community Voice Platform',
            'content'   => '<p>We are thrilled to launch <strong>CivicHub</strong>, your official community forum for Cagayan de Oro residents!</p><p>Use this platform to report local issues, share concerns with your neighbors, and work together toward a better community. All posts are monitored to ensure respectful and constructive discussions.</p><p><strong>Remember:</strong> Be specific, be respectful, and be constructive. Together we can make CDO a better place for everyone.</p>',
            'is_active' => 1,
        ], ['%s','%d','%s','%s','%d']);
    }

    // ---- Sample reports ----
    $reports_seeded = 0;
    $rep_exists = $wpdb->get_var("SELECT COUNT(*) FROM {$p}ch_reports");
    if (!$rep_exists) {
        $report_data = [
            // [reporter_idx, target_type, post_idx, reason, details, status, reviewed_by]
            [0, 'post',    1, 'spam',           'This post looks like it was copy-pasted from another site with no original content.',                                          'dismissed',  1],
            [1, 'post',    3, 'misinformation',  'The claim about MCWD is inaccurate. I contacted them and the outage was only 2 days, not 5.',                                 'reviewed',   1],
            [2, 'comment', 0, 'harassment',      'This comment is directed personally at me and is insulting.',                                                                 'resolved',   1],
            [3, 'post',    4, 'off_topic',        'This post belongs in health and sanitation, not public safety.',                                                              'dismissed',  1],
            [4, 'comment', 2, 'spam',            'The commenter is promoting a private water delivery business in the replies.',                                                 'pending',    0],
            [0, 'post',    5, 'misinformation',  'The school mentioned hasn\'t made any formal PTA request. This is false.',                                                    'pending',    0],
            [1, 'user',    0, 'harassment',      'This user has been sending threatening messages to other community members outside the platform.', 'pending',    0],
            [2, 'comment', 3, 'off_topic',        'Completely unrelated comment just to farm upvotes.',                                                                         'reviewed',   1],
        ];

        foreach ($report_data as $rd) {
            [$rep_idx, $target_type, $target_idx, $reason, $details, $status, $reviewed_by] = $rd;

            $reporter_id = $user_ids[$rep_idx % count($user_ids)];
            $date        = date('Y-m-d H:i:s', strtotime('-' . rand(1, 14) . ' days'));

            if ($target_type === 'post') {
                $target_id = $post_ids[$target_idx] ?? ($post_ids[0] ?? 0);
            } elseif ($target_type === 'comment') {
                $target_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$p}ch_comments ORDER BY id LIMIT 1 OFFSET %d", $target_idx
                )) ?? 0;
            } else {
                // user report — target a different user than the reporter
                $other_users = array_values(array_filter($user_ids, fn($id) => $id !== $reporter_id));
                $target_id   = $other_users[$target_idx % max(1, count($other_users))] ?? ($user_ids[0] ?? 0);
            }

            if (!$target_id) continue;

            $wpdb->insert("{$p}ch_reports", [
                'rand_id'     => bntm_rand_id(),
                'reporter_id' => $reporter_id,
                'target_type' => $target_type,
                'target_id'   => $target_id,
                'reason'      => $reason,
                'details'     => $details,
                'status'      => $status,
                'reviewed_by' => $reviewed_by,
                'created_at'  => $date,
                'updated_at'  => $date,
            ], ['%s','%d','%s','%d','%s','%s','%s','%d','%s','%s']);

            $reports_seeded++;

            // Increment report_count on the target post/comment
            if ($target_type === 'post') {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$p}ch_posts SET report_count = report_count + 1 WHERE id = %d", $target_id
                ));
            } elseif ($target_type === 'comment') {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$p}ch_comments SET report_count = report_count + 1 WHERE id = %d", $target_id
                ));
            }
        }
    }

    // ---- Sample notifications ----
    $notifs_seeded = 0;
    $notif_exists = $wpdb->get_var("SELECT COUNT(*) FROM {$p}ch_notifications");
    if (!$notif_exists) {
        $notif_data = [
            // [recipient_idx, type, actor_idx, post_idx, comment_idx_or_null, message, is_read]
            [0, 'reply',            1, 0, 0,    'Jose Reyes replied to your post "Large pothole on Kauswagan Highway near Gaisano".',        1],
            [0, 'reply',            2, 0, 1,    'Ana Villanueva also replied to your post about the Kauswagan pothole.',                      1],
            [0, 'vote',             1, 0, null, 'Your post received 5 new upvotes.',                                                          1],
            [1, 'reply',            3, 1, 3,    'Pedro Lim commented on your post about illegal dumping near Cagayan River.',                 0],
            [1, 'mention',          0, 1, 3,    'Maria Santos mentioned you in a comment on the garbage dumping post.',                       0],
            [2, 'vote',             4, 2, null, 'Your post about the water supply in Barangay Nazareth received 8 upvotes.',                  1],
            [2, 'reply',            0, 2, 5,    'Maria Santos replied to your water supply post with a helpful tip.',                         0],
            [3, 'reply',            1, 3, 6,    'Jose Reyes commented on your broken streetlights post.',                                     1],
            [3, 'vote',             2, 3, null, 'Your post about streetlights in Barangay Carmen is gaining traction — 12 upvotes!',          0],
            [4, 'reply',            2, 4, 7,    'Ana Villanueva replied to your drainage canal post.',                                        0],
            [4, 'mention',          3, 4, 7,    'Pedro Lim mentioned you in the sanitation discussion.',                                      0],
            [0, 'report_resolved',  1, 0, 2,    'A report you submitted has been reviewed and resolved by a moderator.',                      1],
            [2, 'report_resolved',  1, 3, 6,    'A report on content in the streetlights post has been dismissed.',                          0],
            [0, 'announcement',     1, 0, null, 'New announcement: Welcome to CivicHub — Your Community Voice Platform.',                     1],
            [1, 'announcement',     1, 0, null, 'New announcement: Welcome to CivicHub — Your Community Voice Platform.',                     1],
            [2, 'announcement',     1, 0, null, 'New announcement: Welcome to CivicHub — Your Community Voice Platform.',                     0],
            [3, 'announcement',     1, 0, null, 'New announcement: Welcome to CivicHub — Your Community Voice Platform.',                     0],
            [4, 'announcement',     1, 0, null, 'New announcement: Welcome to CivicHub — Your Community Voice Platform.',                     0],
        ];

        foreach ($notif_data as $nd) {
            [$recip_idx, $type, $actor_idx, $post_idx, $comment_idx, $message, $is_read] = $nd;

            $recipient_id = $user_ids[$recip_idx % count($user_ids)];
            $actor_id     = $user_ids[$actor_idx  % count($user_ids)];
            $post_id      = $post_ids[$post_idx]  ?? ($post_ids[0] ?? 0);
            $comment_id   = 0;

            if ($comment_idx !== null) {
                $comment_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$p}ch_comments ORDER BY id LIMIT 1 OFFSET %d", $comment_idx
                )) ?? 0;
            }

            $date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 72) . ' hours'));

            $wpdb->insert("{$p}ch_notifications", [
                'rand_id'    => bntm_rand_id(),
                'user_id'    => $recipient_id,
                'type'       => $type,
                'actor_id'   => $actor_id,
                'post_id'    => $post_id,
                'comment_id' => $comment_id,
                'message'    => $message,
                'is_read'    => $is_read,
                'created_at' => $date,
            ], ['%s','%d','%s','%d','%d','%d','%s','%d','%s']);

            $notifs_seeded++;
        }
    }

    return [
        'categories'    => count($categories),
        'posts'         => count($post_ids),
        'comments'      => count($comment_data),
        'users'         => count($user_ids),
        'reports'       => $reports_seeded,
        'notifications' => $notifs_seeded,
    ];
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
    .ch-auth-wrap {
        min-height: 100vh; display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        padding: 40px 16px;
        background: linear-gradient(135deg, #f0f0ff 0%, #faf5ff 50%, #f0f9ff 100%);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .ch-auth-card {
        background: #fff; border-radius: 20px;
        box-shadow: 0 8px 40px rgba(99,102,241,0.12), 0 2px 8px rgba(0,0,0,0.06);
        padding: 36px 40px; width: 100%; max-width: 460px;
    }
    .ch-auth-brand {
        display: flex; align-items: center; gap: 14px;
        margin-bottom: 28px; padding-bottom: 24px;
        border-bottom: 1px solid #f3f4f6;
    }
    .ch-auth-logo {
        width: 52px; height: 52px; border-radius: 14px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .ch-auth-brand-name    { font-size: 20px; font-weight: 800; color: #111827; }
    .ch-auth-brand-tagline { font-size: 13px; color: #9ca3af; margin-top: 1px; }

    .ch-auth-tabs {
        display: flex; gap: 0; background: #f3f4f6;
        border-radius: 10px; padding: 4px; margin-bottom: 24px;
    }
    .ch-auth-tab {
        flex: 1; text-align: center; padding: 8px 0;
        border-radius: 8px; font-size: 14px; font-weight: 600;
        text-decoration: none; color: #6b7280; transition: all 0.2s;
    }
    .ch-auth-tab.active { background: #fff; color: #6366f1; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }

    .ch-auth-form .ch-field-group { margin-bottom: 18px; }
    .ch-auth-form .ch-label       { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block; }
    .ch-auth-form .ch-input       {
        width: 100%; padding: 10px 14px; font-size: 14px;
        border: 1.5px solid #e5e7eb; border-radius: 10px;
        outline: none; transition: border 0.15s, box-shadow 0.15s;
        box-sizing: border-box; background: #fff; color: #111827;
    }
    .ch-auth-form .ch-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }

    .ch-password-wrap    { position: relative; }
    .ch-password-toggle  {
        position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
        background: none; border: none; cursor: pointer; color: #9ca3af;
        display: flex; align-items: center; padding: 4px;
        transition: color 0.15s;
    }
    .ch-password-toggle:hover { color: #6366f1; }

    .ch-password-strength {
        height: 4px; border-radius: 2px; margin-top: 8px;
        background: #e5e7eb; overflow: hidden; transition: all 0.3s;
    }
    .ch-password-strength::after {
        content: ''; display: block; height: 100%; border-radius: 2px;
        transition: width 0.3s, background 0.3s;
    }
    .ch-password-strength[data-strength="0"]::after { width: 0%; }
    .ch-password-strength[data-strength="1"]::after { width: 25%; background: #ef4444; }
    .ch-password-strength[data-strength="2"]::after { width: 50%; background: #f59e0b; }
    .ch-password-strength[data-strength="3"]::after { width: 75%; background: #3b82f6; }
    .ch-password-strength[data-strength="4"]::after { width: 100%; background: #10b981; }

    .ch-auth-row {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 20px; flex-wrap: wrap; gap: 8px;
    }
    .ch-auth-submit {
        height: 46px; font-size: 15px; border-radius: 10px; margin-top: 4px;
    }
    .ch-auth-submit:disabled { opacity: 0.7; cursor: not-allowed; }

    .ch-auth-switch { text-align: center; font-size: 13px; color: #6b7280; margin: 16px 0 0; }
    .ch-auth-link   { color: #6366f1; text-decoration: none; font-weight: 600; }
    .ch-auth-link:hover { text-decoration: underline; }
    .ch-required { color: #ef4444; }
    .ch-optional { color: #9ca3af; font-weight: 400; }

    .ch-auth-footer {
        text-align: center; font-size: 12px; color: #9ca3af;
        margin-top: 20px; max-width: 400px;
    }

    #ch-auth-msg .bntm-notice-success { background:#d1fae5; color:#065f46; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:16px; }
    #ch-auth-msg .bntm-notice-error   { background:#fee2e2; color:#991b1b; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:16px; }

    @media (max-width: 500px) {
        .ch-auth-card { padding: 28px 20px; }
        .ch-auth-brand { margin-bottom: 20px; padding-bottom: 16px; }
    }
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
    <?php
    return ob_get_clean();
}

// ============================================================
// AJAX: LOGIN & REGISTER
// ============================================================

add_action('wp_ajax_nopriv_ch_login',    'bntm_ajax_ch_login');
add_action('wp_ajax_nopriv_ch_register', 'bntm_ajax_ch_register');

// ---- Open Graph meta tags for post sharing ----
add_action('wp_head', 'bntm_ch_og_meta_tags');
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
    'ch_seed_data'           => ['bntm_ajax_ch_seed_data', true],
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
// MAIN DASHBOARD SHORTCODE
// ============================================================

function bntm_shortcode_ch() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to access CivicHub.</div>';
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
        <?php if ($is_admin): ?>
        <div style="display:flex;align-items:center;gap:10px;">
            <div id="ch-seed-result" style="font-size:13px;color:#059669;display:none;"></div>
            <button class="ch-btn ch-btn-secondary" id="ch-seed-btn"
                    onclick="chSeedData('<?php echo wp_create_nonce('ch_seed_nonce'); ?>')">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M12 2a9 9 0 0 1 9 9c0 4.97-4.03 9-9 9S3 15.97 3 11a9 9 0 0 1 9-9z"/>
                    <path d="M12 8v4l3 3"/>
                </svg>
                Load Sample Data
            </button>
        </div>
        <?php endif; ?>
    </div>

    <div class="ch-stats-grid">
        <div class="ch-stat-card">
            <div class="ch-stat-icon" style="background: linear-gradient(135deg,#6366f1,#8b5cf6)">
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
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>">
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
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>">
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
                <div class="ch-field-row" style="align-items:center; gap:10px;">
                    <div class="ch-field-group" style="flex:1; min-width:180px;">
                        <label class="ch-label">Search</label>
                        <input type="text" name="cat_search" class="ch-input" placeholder="Search categories" value="<?php echo esc_attr($search); ?>">
                    </div>
                    <div class="ch-field-group" style="min-width:160px;">
                        <label class="ch-label">Sort by</label>
                        <select name="cat_sort" class="ch-input">
                            <option value="sort_order" <?php selected($sort, 'sort_order'); ?>>Custom order</option>
                            <option value="name" <?php selected($sort, 'name'); ?>>Name</option>
                            <option value="activity" <?php selected($sort, 'activity'); ?>>Activity</option>
                            <option value="popularity" <?php selected($sort, 'popularity'); ?>>Popularity</option>
                        </select>
                    </div>
                    <div class="ch-filter-actions" style="min-width:120px;">
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
                        <button class="ch-icon-btn" title="Edit" onclick="chEditCategory(<?php echo (int)$cat->id; ?>, '<?php echo esc_js($cat->name); ?>', '<?php echo esc_js($cat->description); ?>', '<?php echo esc_attr($cat->color); ?>', '<?php echo esc_js($cat->icon); ?>')">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="ch-icon-btn" title="Toggle visibility" onclick="chToggleCategoryStatus(<?php echo (int)$cat->id; ?>, '<?php echo esc_js($cat->status); ?>', this)">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button class="ch-icon-btn ch-icon-btn-danger" title="Delete" onclick="chDeleteCategory(<?php echo (int)$cat->id; ?>, '<?php echo esc_js($cat->name); ?>')">
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
                <div class="ch-field-row">
                    <div class="ch-field-group ch-field-half">
                        <label class="ch-label">Color</label>
                        <input type="color" id="ch-cat-color" class="ch-input ch-color-input" value="#6366f1">
                    </div>
                    <div class="ch-field-group ch-field-half">
                        <label class="ch-label">Sort Order</label>
                        <input type="number" id="ch-cat-order" class="ch-input" value="0" min="0">
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
                <div class="ch-field-row">
                    <div class="ch-field-group ch-field-half">
                        <label class="ch-label">Color</label>
                        <input type="color" id="ch-edit-cat-color" class="ch-input ch-color-input">
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
        window.chEditCategory = function(id, name, desc, color, icon) {
            document.getElementById('ch-edit-cat-id').value = id;
            document.getElementById('ch-edit-cat-name').value = name;
            document.getElementById('ch-edit-cat-desc').value = desc;
            document.getElementById('ch-edit-cat-color').value = color;
            chOpenModal('ch-modal-edit-cat');
        };

        window.chSaveCategory = function(nonce) {
            const name = document.getElementById('ch-cat-name').value.trim();
            if (!name) { alert('Please enter a category name'); return; }

            const btn = document.getElementById('ch-save-cat-btn');
            btn.disabled = true; btn.textContent = 'Creating...';

            const fd = new FormData();
            fd.append('action', 'ch_create_category');
            fd.append('name', name);
            fd.append('description', document.getElementById('ch-cat-desc').value);
            fd.append('color', document.getElementById('ch-cat-color').value);
            fd.append('sort_order', document.getElementById('ch-cat-order').value || 0);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) setTimeout(() => location.reload(), 1000);
                else { btn.disabled = false; btn.textContent = 'Create Category'; }
            });
        };

        window.chUpdateCategory = function(nonce) {
            const id   = document.getElementById('ch-edit-cat-id').value;
            const name = document.getElementById('ch-edit-cat-name').value.trim();
            if (!name) { alert('Please enter a category name'); return; }

            const btn = document.getElementById('ch-update-cat-btn');
            btn.disabled = true; btn.textContent = 'Saving...';

            const fd = new FormData();
            fd.append('action', 'ch_edit_category');
            fd.append('category_id', id);
            fd.append('name', name);
            fd.append('description', document.getElementById('ch-edit-cat-desc').value);
            fd.append('color', document.getElementById('ch-edit-cat-color').value);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-edit-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) setTimeout(() => location.reload(), 1000);
                else { btn.disabled = false; btn.textContent = 'Save Changes'; }
            });
        };

        window.chDeleteCategory = function(id, name) {
            if (!confirm('Delete category "'+name+'"? This cannot be undone.')) return;
            const fd = new FormData();
            fd.append('action', 'ch_delete_category');
            fd.append('category_id', id);
            fd.append('nonce', '<?php echo esc_attr($nonce); ?>');

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) location.reload();
                else alert(json.data?.message || 'Failed to delete.');
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
                    // Optionally reload so list respects filters
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert(json.data?.message || 'Failed to update status.');
                }
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
                        <td><span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>"><?php echo esc_html($post->cat_name ?? '—'); ?></span></td>
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
                                    onclick="chModeratePost(<?php echo (int)$post->id; ?>, 'approve_post', '<?php echo esc_attr($nonce); ?>')">Approve</button>
                                <button class="ch-btn-xs ch-btn-danger" title="Reject"
                                    onclick="chModeratePost(<?php echo (int)$post->id; ?>, 'reject_post', '<?php echo esc_attr($nonce); ?>')">Reject</button>
                                <?php else: ?>
                                <button class="ch-icon-btn" title="<?php echo $post->is_pinned ? 'Unpin' : 'Pin'; ?>"
                                    onclick="chPinPost(<?php echo (int)$post->id; ?>, <?php echo $post->is_pinned ? 0 : 1; ?>, '<?php echo esc_attr($nonce); ?>')">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                </button>
                                <?php if ($post->status !== 'removed'): ?>
                                <button class="ch-icon-btn ch-icon-btn-danger" title="Remove"
                                    onclick="chModeratePost(<?php echo (int)$post->id; ?>, 'remove_post', '<?php echo esc_attr($nonce); ?>')">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                                <?php else: ?>
                                <button class="ch-icon-btn" title="Restore"
                                    onclick="chModeratePost(<?php echo (int)$post->id; ?>, 'restore_post', '<?php echo esc_attr($nonce); ?>')">
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
        window.chPinPost = function(id, pinVal, nonce) {
            const fd = new FormData();
            fd.append('action', 'ch_pin_post');
            fd.append('post_id', id);
            fd.append('pin', pinVal);
            fd.append('nonce', nonce);
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => { if (json.success) location.reload(); else alert(json.data?.message); });
        };

        window.chModeratePost = function(id, action, nonce) {
            const label = action === 'remove_post' ? 'remove' : 'restore';
            if (!confirm('Are you sure you want to '+label+' this post?')) return;
            const fd = new FormData();
            fd.append('action', 'ch_moderate_action');
            fd.append('mod_action', action);
            fd.append('target_id', id);
            fd.append('nonce', nonce);
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => { if (json.success) location.reload(); else alert(json.data?.message); });
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
            .then(json => { if (json.success) location.reload(); else alert(json.data?.message); });
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
                                <span style="font-size:12px;color:#6b7280;max-width:220px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo esc_attr($r->details); ?>">
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
                                            onclick="chResolveReport(<?php echo $r->id; ?>, 'resolved', '<?php echo esc_attr($nonce); ?>')">
                                        ✓ Resolve
                                    </button>
                                    <button class="ch-btn-xs ch-btn-secondary"
                                            onclick="chResolveReport(<?php echo $r->id; ?>, 'dismissed', '<?php echo esc_attr($nonce); ?>')">
                                        Dismiss
                                    </button>
                                    <?php if ($r->target_type === 'post'): ?>
                                    <button class="ch-btn-xs ch-btn-danger"
                                            onclick="chResolveAndRemove(<?php echo $r->id; ?>, <?php echo $r->target_id; ?>, 'post', '<?php echo esc_attr($nonce); ?>')">
                                        Remove Post
                                    </button>
                                    <?php elseif ($r->target_type === 'comment'): ?>
                                    <button class="ch-btn-xs ch-btn-danger"
                                            onclick="chResolveAndRemove(<?php echo $r->id; ?>, <?php echo $r->target_id; ?>, 'comment', '<?php echo esc_attr($nonce); ?>')">
                                        Remove Comment
                                    </button>
                                    <?php elseif ($r->target_type === 'user'): ?>
                                    <button class="ch-btn-xs ch-btn-warning"
                                            onclick="chResolveAndSuspend(<?php echo $r->id; ?>, <?php echo $r->target_id; ?>, '<?php echo esc_attr($nonce); ?>')">
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
        function doPost(fd, rowId) {
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const row = document.getElementById('ch-report-row-' + rowId);
                    if (row) {
                        row.style.opacity = '0.4';
                        row.style.transition = 'opacity 0.3s';
                    }
                    setTimeout(() => location.reload(), 600);
                } else {
                    alert(json.data?.message || 'Action failed. Please try again.');
                }
            })
            .catch(() => alert('Network error. Please try again.'));
        }

        window.chResolveReport = function(reportId, resolution, nonce) {
            const label = resolution === 'resolved' ? 'resolve' : 'dismiss';
            if (!confirm('Are you sure you want to ' + label + ' this report?')) return;
            const fd = new FormData();
            fd.append('action',      'ch_moderate_action');
            fd.append('mod_action',  'resolve_report');
            fd.append('target_id',   reportId);
            fd.append('resolution',  resolution);
            fd.append('nonce',       nonce);
            doPost(fd, reportId);
        };

        window.chResolveAndRemove = function(reportId, targetId, targetType, nonce) {
            const label = targetType === 'post' ? 'post' : 'comment';
            if (!confirm('Remove this ' + label + ' AND resolve the report?')) return;
            const modAction = targetType === 'post' ? 'remove_post' : 'remove_comment';
            // Step 1: remove content
            const fd1 = new FormData();
            fd1.append('action',     'ch_moderate_action');
            fd1.append('mod_action', modAction);
            fd1.append('target_id',  targetId);
            fd1.append('nonce',      nonce);
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
                doPost(fd2, reportId);
            });
        };

        window.chResolveAndSuspend = function(reportId, targetUserId, nonce) {
            if (!confirm('Suspend this user AND resolve the report?')) return;
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
                doPost(fd2, reportId);
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
            <div class="ch-stat-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">
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
                        <input type="file" id="ch-profile-avatar" class="ch-input" accept="image/*">
                        <?php if ($profile->avatar_url): ?>
                        <div class="ch-avatar-preview">
                            <img src="<?php echo esc_url($profile->avatar_url); ?>" alt="Current avatar" style="width:50px;height:50px;border-radius:50%;margin-top:8px;">
                        </div>
                        <?php endif; ?>
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
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($p->cat_color??'#6366f1'); ?>20;color:<?php echo esc_attr($p->cat_color??'#6366f1'); ?>"><?php echo esc_html($p->cat_name??'General'); ?></span>
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
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($p->cat_color??'#6366f1'); ?>20;color:<?php echo esc_attr($p->cat_color??'#6366f1'); ?>"><?php echo esc_html($p->cat_name??'General'); ?></span>
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
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($p->cat_color??'#6366f1'); ?>20;color:<?php echo esc_attr($p->cat_color??'#6366f1'); ?>"><?php echo esc_html($p->cat_name??'General'); ?></span>
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

        <!-- Edit post modal -->
        <div id="ch-modal-edit-post" class="ch-modal-overlay" style="display:none">
            <div class="ch-modal ch-modal-lg">
                <div class="ch-modal-header">
                    <h3>Edit Post</h3>
                    <button class="ch-modal-close" onclick="chCloseModal('ch-modal-edit-post')">&times;</button>
                </div>
                <div class="ch-modal-body">
                    <input type="hidden" id="ch-edit-post-id">
                    <div class="ch-field-group">
                        <label class="ch-label">Category *</label>
                        <select id="ch-edit-post-cat" class="ch-input">
                            <option value="">Select a category</option>
                            <?php foreach ($categories_for_modal as $cat): ?>
                            <option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-label">Title *</label>
                        <input type="text" id="ch-edit-post-title" class="ch-input" placeholder="Post title">
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-label">Content *</label>
                        <textarea id="ch-edit-post-content" class="ch-input ch-textarea ch-textarea-lg" rows="6"></textarea>
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-label">Tags</label>
                        <input type="text" id="ch-edit-post-tags" class="ch-input" placeholder="e.g., road, drainage">
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-label">Attach Media (optional)</label>
                        <input type="file" id="ch-edit-post-media" class="ch-input" multiple accept="image/*,video/*">
                    </div>
                    <div class="ch-field-group">
                        <label class="ch-checkbox-label"><input type="checkbox" id="ch-edit-post-anon"> Post Anonymously</label>
                    </div>
                </div>
                <div class="ch-modal-footer">
                    <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-edit-post')">Cancel</button>
                    <button class="ch-btn ch-btn-primary" id="ch-update-post-btn" onclick="chUpdatePost('<?php echo esc_attr(wp_create_nonce('ch_post_view_nonce')); ?>')">Update Post</button>
                </div>
                <div id="ch-edit-post-msg"></div>
            </div>
        </div>

    </div><!-- .ch-my-feed-wrap -->

    <style>
    .ch-my-feed-wrap{max-width:860px;margin:0 auto;padding:28px 20px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#111827}
    .ch-mf-profile-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:28px;display:flex;gap:22px;align-items:flex-start;margin-bottom:16px;box-shadow:0 2px 12px rgba(99,102,241,.06)}
    .ch-mf-avatar-wrap{display:flex;flex-direction:column;align-items:center;gap:8px;flex-shrink:0}
    .ch-mf-avatar-img{width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid #e5e7eb}
    .ch-mf-avatar-initials{width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-size:34px;font-weight:800;display:flex;align-items:center;justify-content:center}
    .ch-mf-profile-info{flex:1;min-width:0}
    .ch-mf-name-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px}
    .ch-mf-name{font-size:24px;font-weight:800;color:#111827;margin:0}
    .ch-mf-bio{font-size:14px;color:#374151;margin:6px 0 10px;line-height:1.6}
    .ch-mf-meta-row{display:flex;flex-wrap:wrap;gap:14px}
    .ch-mf-meta-item{display:flex;align-items:center;gap:5px;font-size:13px;color:#6b7280}
    .ch-mf-stats-bar{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
    .ch-mf-stat{flex:1;min-width:100px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px 12px;text-align:center}
    .ch-mf-stat-num{display:block;font-size:22px;font-weight:800;color:#6366f1}
    .ch-mf-stat-label{display:flex;align-items:center;justify-content:center;gap:4px;font-size:11px;color:#9ca3af;margin-top:4px;text-transform:uppercase;letter-spacing:.4px}
    .ch-mf-subnav{display:flex;gap:4px;background:#f3f4f6;border-radius:10px;padding:4px;margin-bottom:20px}
    .ch-mf-subnav-item{flex:1;text-align:center;padding:8px 0;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:#6b7280;transition:all .18s}
    .ch-mf-subnav-item.active{background:#fff;color:#6366f1;box-shadow:0 1px 4px rgba(0,0,0,.1)}
    .ch-mf-content{display:flex;flex-direction:column;gap:12px}
    .ch-mf-post-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 20px;transition:border-color .15s,box-shadow .15s}
    .ch-mf-post-card:hover{border-color:#c7d2fe;box-shadow:0 2px 12px rgba(99,102,241,.08)}
    .ch-mf-post-top{display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap}
    .ch-mf-time{font-size:12px;color:#9ca3af;margin-left:auto}
    .ch-mf-pinned-badge{font-size:11px;color:#6366f1;font-weight:600}
    .ch-mf-post-actions{display:flex;gap:6px;margin-left:8px}
    .ch-mf-post-title{font-size:16px;font-weight:700;color:#111827;text-decoration:none;display:block;margin-bottom:6px;line-height:1.4}
    .ch-mf-post-title:hover{color:#6366f1}
    .ch-mf-post-excerpt{font-size:13px;color:#6b7280;margin:0 0 12px;line-height:1.55}
    .ch-mf-post-footer{display:flex;gap:16px;font-size:12px;color:#9ca3af}
    .ch-mf-comment-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px 20px}
    .ch-mf-comment-post-ref{display:flex;align-items:center;gap:6px;font-size:12px;color:#9ca3af;margin-bottom:8px}
    .ch-mf-ref-link{color:#6366f1;text-decoration:none;font-weight:600}
    .ch-mf-comment-content{font-size:14px;color:#374151;margin:0 0 10px;line-height:1.55}
    .ch-mf-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:60px 20px;text-align:center;background:#fff;border:1px dashed #e5e7eb;border-radius:14px}
    .ch-mf-empty p{font-size:15px;color:#9ca3af;margin:0}
    @media(max-width:600px){.ch-mf-profile-card{flex-direction:column;align-items:center;text-align:center}.ch-mf-meta-row{justify-content:center}.ch-mf-stats-bar{gap:8px}.ch-mf-stat{min-width:80px}}
    </style>
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
                    <span class="ch-cat-badge" style="background:<?php echo esc_attr($p->cat_color ?? '#6366f1'); ?>20;color:<?php echo esc_attr($p->cat_color ?? '#6366f1'); ?>">
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
    <style>
    .ch-public-profile-wrap { max-width: 800px; margin: 0 auto; padding: 28px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .ch-public-profile-header { margin-bottom: 20px; }
    .ch-public-profile-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 28px; display: flex; gap: 24px; align-items: flex-start; margin-bottom: 20px; }
    .ch-public-profile-avatar img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
    .ch-avatar-lg { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg,#6366f1,#8b5cf6); color: #fff; font-size: 32px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ch-public-profile-name { font-size: 22px; font-weight: 800; color: #111827; margin: 0 0 8px; }
    .ch-public-profile-meta { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6b7280; margin-bottom: 4px; }
    .ch-public-profile-bio { font-size: 14px; color: #374151; margin: 10px 0; }
    .ch-public-profile-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 24px; }
    .ch-public-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center; }
    .ch-public-stat-card .ch-stat-num { display: block; font-size: 28px; font-weight: 700; color: #6366f1; }
    .ch-public-stat-card .ch-stat-label { display: block; font-size: 12px; color: #9ca3af; margin-top: 4px; }
    .ch-section-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 16px; }
    .ch-public-post-card { display: block; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; margin-bottom: 12px; text-decoration: none; color: inherit; transition: border-color 0.15s, box-shadow 0.15s; }
    .ch-public-post-card:hover { border-color: #6366f1; box-shadow: 0 2px 12px rgba(99,102,241,0.1); }
    .ch-public-post-top { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .ch-public-post-time { font-size: 12px; color: #9ca3af; margin-left: auto; }
    .ch-public-post-title { font-size: 15px; font-weight: 700; color: #111827; margin: 0 0 6px; }
    .ch-public-post-excerpt { font-size: 13px; color: #6b7280; margin: 0 0 10px; }
    .ch-public-post-footer { display: flex; gap: 14px; font-size: 12px; color: #9ca3af; }
    </style>
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
        ob_start();
        echo ch_global_styles();
        echo ch_feed_scripts();
        echo '<div class="ch-profile-page-wrap">';
        echo ch_profile_tab($user_id);
        echo '</div>';
        echo '<style>
            .ch-profile-page-wrap { max-width: 900px; margin: 0 auto; padding: 28px 20px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .ch-profile-page-wrap .ch-card-body { padding: 20px; }
            .ch-stat-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
            .ch-stat-item { text-align: center; padding: 16px; background: #f9fafb;
                border-radius: 10px; }
            .ch-stat-item .ch-stat-num   { display: block; font-size: 24px; font-weight: 700; color: #6366f1; }
            .ch-stat-item .ch-stat-label { display: block; font-size: 12px; color: #9ca3af; margin-top: 4px; }
        </style>';
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

    $base_where = "WHERE p.status = 'active' $cat_filter $location_filter $bookmark_filter $search_filter";
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

    $nonce = wp_create_nonce('ch_feed_nonce');

    ob_start();
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>

    <!-- Top Navigation -->
    <nav class="ch-top-nav">
        <div class="ch-nav-links">
            <a href="<?php echo get_permalink(); ?>" class="ch-nav-link <?php echo !$bookmarks && $sort === 'new' && $tab === '' && !$cat_slug && !$search ? 'active' : ''; ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Home
            </a>
            <a href="?sort=trending" class="ch-nav-link <?php echo $sort === 'trending' && $tab === '' ? 'active' : ''; ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Trending
            </a>
            <?php if ($user_id): ?>
            <a href="?bookmarks=1" class="ch-nav-link <?php echo $bookmarks && $tab === '' ? 'active' : ''; ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                Bookmarks
            </a>
            <a href="?tab=my_feed" class="ch-nav-link <?php echo ($tab === 'my_feed') ? 'active' : ''; ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                My Feed
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
                    <a href="?tab=profile" class="ch-dropdown-item">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        My Profile
                    </a>
                    <a href="?bookmarks=1" class="ch-dropdown-item">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        My Bookmarks
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
                <?php foreach ($categories as $cat): ?>
                    <div class="ch-cat-item" style="display:flex; align-items:center; justify-content:space-between; gap:4px;">
                        <a href="?cat=<?php echo esc_attr($cat->slug); ?>" class="ch-cat-link <?php echo $cat_slug === $cat->slug ? 'active' : ''; ?>" style="flex:1;min-width:0;">
                            <span class="ch-cat-dot" style="background:<?php echo esc_attr($cat->color); ?>"></span>
                            <?php echo esc_html($cat->name); ?>
                            <span class="ch-cat-count"><?php echo $cat->post_count; ?></span>
                        </a>
                        <?php if ($user_id && ($cat->business_id == $user_id || current_user_can('manage_options'))): ?>
                        <div class="ch-cat-owner-actions">
                            <button class="ch-cat-action-btn"
                                    title="Edit category"
                                    onclick="chFeedOpenEditCat(<?php echo (int)$cat->id; ?>, '<?php echo esc_js($cat->name); ?>', '<?php echo esc_js($cat->description ?? ''); ?>', '<?php echo esc_attr($cat->color); ?>')">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button class="ch-cat-action-btn ch-cat-action-delete"
                                    title="Delete category"
                                    onclick="chFeedDeleteCat(<?php echo (int)$cat->id; ?>, '<?php echo esc_js($cat->name); ?>', '<?php echo esc_attr(wp_create_nonce('ch_category_nonce')); ?>')">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                        <?php endif; ?>
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
                            onclick="chOpenModal('ch-modal-feed-create-cat')"
                            style="font-size:13px;">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        New Category
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($user_id && !empty($followed)): ?>
                <div class="ch-followed-categories" style="margin-top: 20px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                    <h5 style="margin: 0 0 8px; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Following</h5>
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
                        <div class="ch-announcement-content"><?php echo wp_kses_post($announcement->content); ?></div>
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
                        <?php if ($user_id): ?>
                        <button class="ch-vote-btn ch-vote-up" data-id="<?php echo (int)$post->id; ?>" data-type="post" data-val="1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                        </button>
                        <?php endif; ?>
                        <span class="ch-vote-count"><?php echo (int)$post->vote_count; ?></span>
                        <?php if ($user_id): ?>
                        <button class="ch-vote-btn ch-vote-down" data-id="<?php echo (int)$post->id; ?>" data-type="post" data-val="-1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="ch-post-body">
                        <div class="ch-post-meta-row">
                            <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>">
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
                            <?php if ($user_id && ($post->user_id == $user_id || current_user_can('manage_options'))): ?>
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
    <?php if ($user_id): ?>
    <div id="ch-modal-create-post" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg">
            <div class="ch-modal-header">
                <h3>Create New Post</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-create-post')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <div class="ch-field-group">
                    <label class="ch-label">Category *</label>
                    <select id="ch-post-cat" class="ch-input">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Title *</label>
                    <input type="text" id="ch-post-title" class="ch-input" placeholder="What's on your mind?">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Content *</label>
                    <textarea id="ch-post-content" class="ch-input ch-textarea ch-textarea-lg" rows="6" placeholder="Share your thoughts, concerns, or suggestions with the community..."></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Tags (comma-separated)</label>
                    <input type="text" id="ch-post-tags" class="ch-input" placeholder="e.g., road, drainage, urgent">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Attach Media (optional)</label>
                    <input type="file" id="ch-post-media" class="ch-input" multiple accept="image/*,video/*,audio/*">
                </div>
                <div class="ch-field-group">
                    <label class="ch-checkbox-label">
                        <input type="checkbox" id="ch-post-anon">
                        Post Anonymously
                    </label>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-create-post')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-submit-post-btn" onclick="chSubmitPost('<?php echo esc_attr($nonce); ?>')">Post to Community</button>
            </div>
            <div id="ch-post-msg"></div>
        </div>
    </div>

    <!-- Edit Post Modal -->
    <?php if ($user_id): ?>
    <div id="ch-modal-edit-post" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg">
            <div class="ch-modal-header">
                <h3>Edit Post</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-edit-post')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <input type="hidden" id="ch-edit-post-id">
                <div class="ch-field-group">
                    <label class="ch-label">Category *</label>
                    <select id="ch-edit-post-cat" class="ch-input">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Title *</label>
                    <input type="text" id="ch-edit-post-title" class="ch-input" placeholder="What's on your mind?">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Content *</label>
                    <textarea id="ch-edit-post-content" class="ch-input ch-textarea ch-textarea-lg" rows="6" placeholder="Share your thoughts, concerns, or suggestions with the community..."></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Tags (comma-separated)</label>
                    <input type="text" id="ch-edit-post-tags" class="ch-input" placeholder="e.g., road, drainage, urgent">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Attach Media (optional)</label>
                    <input type="file" id="ch-edit-post-media" class="ch-input" multiple accept="image/*,video/*,audio/*">
                </div>
                <div class="ch-field-group">
                    <label class="ch-checkbox-label">
                        <input type="checkbox" id="ch-edit-post-anon">
                        Post Anonymously
                    </label>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-edit-post')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-update-post-btn" onclick="chUpdatePost('<?php echo esc_attr(wp_create_nonce('ch_post_view_nonce')); ?>')">Update Post</button>
            </div>
            <div id="ch-edit-post-msg"></div>
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
                    <h4>Our Community Standards</h4>
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

                    <p><strong>Thank you for helping keep our community safe and welcoming!</strong></p>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-primary" onclick="chCloseModal('ch-modal-guidelines')">I Understand</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                    <input type="color" id="ch-feed-edit-cat-color" class="ch-input ch-color-input">
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
                    <input type="color" id="ch-feed-cat-color" class="ch-input ch-color-input" value="#6366f1">
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
            btn.disabled = true; btn.textContent = 'Creating...';
            const fd = new FormData();
            fd.append('action',      'ch_create_category');
            fd.append('name',        name);
            fd.append('description', desc);
            fd.append('color',       color);
            fd.append('sort_order',  0);
            fd.append('nonce',       nonce);
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-feed-cat-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">'
                    + (json.data?.message || '') + '</div>';
                if (json.success) {
                    // The server already auto-followed; just reload
                    setTimeout(() => { chCloseModal('ch-modal-feed-create-cat'); location.reload(); }, 900);
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
            document.getElementById('ch-feed-edit-cat-color').value = color;
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
            btn.disabled = true; btn.textContent = 'Saving...';
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
                    setTimeout(() => { chCloseModal('ch-modal-feed-edit-cat'); location.reload(); }, 800);
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
                if (json.success) location.reload();
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
                        <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>">
                            <?php echo esc_html($post->cat_name ?? 'General'); ?>
                        </span>
                        <span class="ch-post-author">
                            <?php echo $post->is_anonymous ? 'Anonymous' : esc_html($post->author_name ?? 'Community Member'); ?>
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
                        <button class="ch-vote-btn-lg ch-vote-up" data-id="<?php echo (int)$post->id; ?>" data-type="post" data-val="1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                            Upvote
                        </button>
                        <?php endif; ?>
                        <span class="ch-vote-score" id="ch-post-score"><?php echo (int)$post->vote_count; ?> points</span>
                        <?php if ($user_id): ?>
                        <button class="ch-vote-btn-lg ch-vote-down" data-id="<?php echo (int)$post->id; ?>" data-type="post" data-val="-1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
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
                        <?php if ($post->user_id == $user_id || current_user_can('manage_options')): ?>
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
                    <?php else: ?>
                    <p class="ch-login-prompt">Please <a href="<?php echo wp_login_url(get_permalink()); ?>">log in</a> to comment.</p>
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
                                    <strong><?php echo $cm->is_anonymous ? 'Anonymous' : esc_html($cm->author_name ?? 'Member'); ?></strong>
                                    <span class="ch-comment-time"><?php echo human_time_diff(strtotime($cm->created_at), current_time('timestamp')); ?> ago</span>
                                </div>
                                <p><?php echo ch_highlight_mentions(nl2br(esc_html($cm->content))); ?></p>
                                <div class="ch-comment-actions">
                                    <?php if ($user_id): ?>
                                    <button class="ch-comment-action ch-vote-up" data-id="<?php echo (int)$cm->id; ?>" data-type="comment" data-val="1" onclick="chVote(this, '<?php echo esc_attr($nonce); ?>')">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                                        <?php echo $cm->vote_count; ?>
                                    </button>
                                    <button class="ch-comment-action" onclick="chToggleReplyForm(<?php echo (int)$cm->id; ?>)">Reply</button>
                                    <?php if ($cm->user_id == $user_id || current_user_can('manage_options')): ?>
                                    <button class="ch-comment-action" onclick="chEditComment(<?php echo (int)$cm->id; ?>)">Edit</button>
                                    <button class="ch-comment-action ch-danger-action" onclick="chDeleteComment(<?php echo (int)$cm->id; ?>, '<?php echo esc_attr($nonce); ?>')">Delete</button>
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
                                            <?php if ($user_id && ($reply->user_id == $user_id || current_user_can('manage_options'))): ?>
                                            <div class="ch-comment-actions">
                                                <button class="ch-comment-action" onclick="chEditComment(<?php echo $reply->id; ?>)">Edit</button>
                                                <button class="ch-comment-action ch-danger-action" onclick="chDeleteComment(<?php echo $reply->id; ?>, '<?php echo esc_attr($nonce); ?>')">Delete</button>
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
                            <span class="ch-cat-badge" style="background:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>20;color:<?php echo esc_attr($post->cat_color ?? '#6366f1'); ?>">
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

    <!-- Create Post Modal (for post view page) -->
    <?php if ($user_id): ?>
    <div id="ch-modal-create-post" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg">
            <div class="ch-modal-header">
                <h3>Create New Post</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-create-post')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <div class="ch-field-group">
                    <label class="ch-label">Category *</label>
                    <select id="ch-post-cat" class="ch-input">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Title *</label>
                    <input type="text" id="ch-post-title" class="ch-input" placeholder="What's on your mind?">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Content *</label>
                    <textarea id="ch-post-content" class="ch-input ch-textarea ch-textarea-lg" rows="6" placeholder="Share your thoughts, concerns, or suggestions with the community..."></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Tags (comma-separated)</label>
                    <input type="text" id="ch-post-tags" class="ch-input" placeholder="e.g., road, drainage, urgent">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Attach Media (optional)</label>
                    <input type="file" id="ch-post-media" class="ch-input" multiple accept="image/*,video/*,audio/*">
                </div>
                <div class="ch-field-group">
                    <label class="ch-checkbox-label">
                        <input type="checkbox" id="ch-post-anon">
                        Post Anonymously
                    </label>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-create-post')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-submit-post-btn" onclick="chSubmitPost('<?php echo wp_create_nonce('ch_feed_nonce'); ?>')">Post to Community</button>
            </div>
            <div id="ch-post-msg"></div>
        </div>
    </div>

    <!-- Edit Post Modal -->
    <div id="ch-modal-edit-post" class="ch-modal-overlay" style="display:none">
        <div class="ch-modal ch-modal-lg">
            <div class="ch-modal-header">
                <h3>Edit Post</h3>
                <button class="ch-modal-close" onclick="chCloseModal('ch-modal-edit-post')">&times;</button>
            </div>
            <div class="ch-modal-body">
                <input type="hidden" id="ch-edit-post-id">
                <div class="ch-field-group">
                    <label class="ch-label">Category *</label>
                    <select id="ch-edit-post-cat" class="ch-input">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Title *</label>
                    <input type="text" id="ch-edit-post-title" class="ch-input" placeholder="What's on your mind?">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Content *</label>
                    <textarea id="ch-edit-post-content" class="ch-input ch-textarea ch-textarea-lg" rows="6" placeholder="Share your thoughts, concerns, or suggestions with the community..."></textarea>
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Tags (comma-separated)</label>
                    <input type="text" id="ch-edit-post-tags" class="ch-input" placeholder="e.g., road, drainage, urgent">
                </div>
                <div class="ch-field-group">
                    <label class="ch-label">Attach Media (optional)</label>
                    <input type="file" id="ch-edit-post-media" class="ch-input" multiple accept="image/*,video/*,audio/*">
                </div>
                <div class="ch-field-group">
                    <label class="ch-checkbox-label">
                        <input type="checkbox" id="ch-edit-post-anon">
                        Post Anonymously
                    </label>
                </div>
            </div>
            <div class="ch-modal-footer">
                <button class="ch-btn ch-btn-secondary" onclick="chCloseModal('ch-modal-edit-post')">Cancel</button>
                <button class="ch-btn ch-btn-primary" id="ch-update-post-btn" onclick="chUpdatePost('<?php echo esc_attr($nonce); ?>')">Update Post</button>
            </div>
            <div id="ch-edit-post-msg"></div>
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
    <?php echo ch_post_view_scripts(); ?>
    <?php
    return ob_get_clean();
}

// ============================================================
// AJAX: SEED DATA
// ============================================================

function bntm_ajax_ch_seed_data() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    check_ajax_referer('ch_seed_nonce', 'nonce');
    $result = bntm_ch_seed_data();
    wp_send_json_success([
        'message' => sprintf(
            'Seed complete: %d categories, %d posts, %d comments, %d users created.',
            $result['categories'], $result['posts'], $result['comments'], $result['users']
        ),
    ]);
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

    $name  = sanitize_text_field($_POST['name'] ?? '');
    $desc  = sanitize_textarea_field($_POST['description'] ?? '');
    $color = sanitize_hex_color($_POST['color'] ?? '#6366f1') ?: '#6366f1';
    $order = (int)($_POST['sort_order'] ?? 0);

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
    ], ['%s','%d','%s','%s','%s','%s','%d']);

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
    $color   = sanitize_hex_color($_POST['color'] ?? '#6366f1') ?: '#6366f1';

    if (!$id || !$name) wp_send_json_error(['message' => 'Invalid input']);

    // Allow admin or the category creator (business_id) to edit
    $owner = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT business_id FROM {$wpdb->prefix}ch_categories WHERE id = %d", $id
    ));
    if (!current_user_can('manage_options') && $owner !== $user_id) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $result = $wpdb->update("{$wpdb->prefix}ch_categories",
        ['name' => $name, 'description' => $desc, 'color' => $color, 'slug' => sanitize_title($name)],
        ['id' => $id], ['%s','%s','%s','%s'], ['%d']
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
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please log in to post']);
    check_ajax_referer('ch_feed_nonce', 'nonce');

    global $wpdb;
    $user_id  = get_current_user_id();
    $title    = sanitize_text_field($_POST['title'] ?? '');
    $content  = sanitize_textarea_field($_POST['content'] ?? '');
    $cat_id   = (int)($_POST['category_id'] ?? 0);
    $tags     = sanitize_text_field($_POST['tags'] ?? '');
    $is_anon  = (int)(!empty($_POST['is_anonymous']));

    if (!$title || !$content || !$cat_id) wp_send_json_error(['message' => 'Title, content, and category are required']);

    $profile = ch_ensure_profile($user_id);

    // Check if user is banned/suspended
    $profile_row = $wpdb->get_row($wpdb->prepare("SELECT status FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id));
    if ($profile_row && in_array($profile_row->status, ['banned','suspended'])) {
        wp_send_json_error(['message' => 'Your account is restricted from posting']);
    }

    $rand_id = bntm_rand_id();
    $status = get_option('ch_post_approval_enabled', 0) ? 'pending' : 'active';
    $result  = $wpdb->insert("{$wpdb->prefix}ch_posts", [
        'rand_id'      => $rand_id,
        'business_id'  => $user_id,
        'user_id'      => $user_id,
        'category_id'  => $cat_id,
        'title'        => $title,
        'content'      => $content,
        'tags'         => $tags,
        'media_urls'   => '',
        'is_anonymous' => $is_anon,
        'status'       => $status,
    ], ['%s','%d','%d','%d','%s','%s','%s','%s','%d','%s']);

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
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_user_profiles SET post_count = post_count + 1, karma_points = karma_points + 2 WHERE user_id = %d", $user_id));
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

    $where = $post_id ? "id = $post_id" : "rand_id = '$rand_id'";
    $post = $wpdb->get_row("SELECT p.*, c.name as cat_name, c.color as cat_color, c.slug as cat_slug,
                                   u.display_name as author_name, u.karma_points as author_karma, u.location as author_location
                            FROM {$wpdb->prefix}ch_posts p
                            LEFT JOIN {$wpdb->prefix}ch_categories c ON p.category_id = c.id
                            LEFT JOIN {$wpdb->prefix}ch_user_profiles u ON p.user_id = u.user_id
                            WHERE p.status IN ('active','hidden') AND $where");

    if (!$post) wp_send_json_error(['message' => 'Post not found']);

    // Increment view count
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_posts SET view_count = view_count + 1 WHERE id = %d", $post->id));

    wp_send_json_success(['post' => $post]);
}

function bntm_ajax_ch_add_comment() {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please log in to comment']);
    check_ajax_referer('ch_post_view_nonce', 'nonce');

    global $wpdb;
    $user_id   = get_current_user_id();
    $post_id   = (int)($_POST['post_id'] ?? 0);
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $content   = sanitize_textarea_field($_POST['content'] ?? '');
    $is_anon   = (int)(!empty($_POST['is_anonymous']));

    if (!$post_id || !$content) wp_send_json_error(['message' => 'Content is required']);

    // Check user status
    $profile = $wpdb->get_row($wpdb->prepare("SELECT status FROM {$wpdb->prefix}ch_user_profiles WHERE user_id = %d", $user_id));
    if ($profile && in_array($profile->status, ['banned','suspended'])) {
        wp_send_json_error(['message' => 'Your account is restricted']);
    }

    $result = $wpdb->insert("{$wpdb->prefix}ch_comments", [
        'rand_id'      => bntm_rand_id(),
        'business_id'  => $user_id,
        'post_id'      => $post_id,
        'user_id'      => $user_id,
        'parent_id'    => $parent_id,
        'content'      => $content,
        'is_anonymous' => $is_anon,
    ], ['%s','%d','%d','%d','%d','%s','%d']);

    if ($result) {
        $comment_id = $wpdb->insert_id;
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_posts SET comment_count = comment_count + 1 WHERE id = %d", $post_id));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ch_user_profiles SET comment_count = comment_count + 1, karma_points = karma_points + 1 WHERE user_id = %d", $user_id));

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
            $wpdb->update("{$wpdb->prefix}ch_posts", ['status' => 'removed'], ['id' => $target_id], ['%s'], ['%d']);
            ch_log_activity('remove_post', 'post', $target_id, 'Post removed by admin');
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
        "SELECT n.*, p.title as post_title, c.content as comment_content
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
            'id' => $n->id,
            'type' => $n->type,
            'message' => $message,
            'is_read' => $n->is_read,
            'created_at' => human_time_diff(strtotime($n->created_at), current_time('timestamp')) . ' ago',
            'post_id' => $n->post_id,
            'comment_id' => $n->comment_id
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

    if ($result !== false) wp_send_json_success(['message' => 'Profile updated!']);
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
    /* ---- RESET & BASE ---- */
    .ch-dashboard-wrap, .ch-feed-wrap, .ch-post-view-wrap {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: var(--bntm-text, #111827);
        line-height: 1.5;
    }

    /* ---- TOP NAV ---- */
    .ch-top-nav {
        background: var(--bntm-surface, #fff);
        border-bottom: 1px solid var(--bntm-border, #e5e7eb);
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 200;
    }
    .ch-nav-links { display: flex; gap: 4px; }
    .ch-nav-link {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 14px; border-radius: 8px;
        text-decoration: none; font-size: 13px; font-weight: 500;
        color: #6b7280; transition: all 0.15s;
    }
    .ch-nav-link:hover  { background: #f3f4f6; color: #111827; }
    .ch-nav-link.active { background: #ede9fe; color: #6366f1; }

    .ch-user-bar { display: flex; align-items: center; gap: 8px; }

    /* Icon action button (bell) */
    .ch-icon-action-btn {
        width: 36px; height: 36px; border-radius: 10px;
        border: 1px solid #e5e7eb; background: #fff;
        cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
        position: relative; color: #6b7280; transition: all 0.15s;
        overflow: visible;
    }
    .ch-icon-action-btn:hover { background: #f3f4f6; border-color: #d1d5db; color: #374151; }

    /* Avatar button */
    .ch-avatar-btn {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white; font-size: 14px; font-weight: 700;
        border: none; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .ch-avatar-btn:hover { transform: scale(1.05); box-shadow: 0 2px 8px rgba(99,102,241,0.35); }
    .ch-avatar-btn-lg { width: 42px; height: 42px; font-size: 16px; flex-shrink: 0; }

    /* Badge on bell */
    .ch-notification-badge {
        position: absolute; top: -5px; right: -5px;
        background: #ef4444; color: #fff; border-radius: 999px;
        font-size: 10px; font-weight: 700; line-height: 1;
        min-width: 18px; height: 18px;
        display: flex; align-items: center; justify-content: center;
        padding: 0 4px;
        border: 2px solid #fff;
        pointer-events: none;
        z-index: 1;
    }

    /* Shared dropdown panel */
    .ch-dropdown-panel {
        position: fixed;
        background: var(--bntm-surface, #fff);
        border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
        min-width: 320px; max-width: 380px;
        z-index: 9999;
        overflow: hidden;
    }
    .ch-dropdown-panel-sm { min-width: 220px; max-width: 260px; }
    .ch-dropdown-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 16px; border-bottom: 1px solid #f3f4f6;
        font-size: 14px; font-weight: 600;
    }
    .ch-dropdown-action {
        background: none; border: none; color: #6366f1; font-size: 12px;
        cursor: pointer; padding: 3px 8px; border-radius: 6px; transition: background 0.1s;
    }
    .ch-dropdown-action:hover { background: #ede9fe; }
    .ch-dropdown-footer {
        padding: 10px 16px; border-top: 1px solid #f3f4f6; text-align: center;
    }
    .ch-dropdown-footer a { color: #6366f1; text-decoration: none; font-size: 13px; font-weight: 500; }
    .ch-dropdown-footer a:hover { text-decoration: underline; }
    .ch-dropdown-user-info {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 16px;
    }
    .ch-dropdown-username { font-size: 14px; font-weight: 600; }
    .ch-dropdown-usermeta { font-size: 12px; color: #9ca3af; margin-top: 2px; }
    .ch-dropdown-divider { height: 1px; background: #f3f4f6; margin: 4px 0; }
    .ch-dropdown-item {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 16px; font-size: 13px; color: #374151;
        text-decoration: none; transition: background 0.1s;
    }
    .ch-dropdown-item:hover { background: #f9fafb; }
    .ch-dropdown-item-danger { color: #ef4444; }
    .ch-dropdown-item-danger:hover { background: #fff5f5; }

    /* Notifications list */
    .ch-notifications-list { max-height: 320px; overflow-y: auto; }
    .ch-notification-item {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 12px 16px; border-bottom: 1px solid #f9fafb;
        cursor: pointer; transition: background 0.1s;
    }
    .ch-notification-item:hover { background: #f9fafb; }
    .ch-notification-item.unread { background: #fef9ee; }
    .ch-notification-icon {
        width: 32px; height: 32px; border-radius: 50%;
        background: #ede9fe; color: #6366f1;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ch-notification-content { flex: 1; min-width: 0; }
    .ch-notification-message { font-size: 13px; line-height: 1.4; margin: 0 0 3px; }
    .ch-notification-time { font-size: 11px; color: #9ca3af; }
    .ch-no-notifications { padding: 32px 16px; text-align: center; color: #9ca3af; font-size: 14px; }

    /* ---- DASHBOARD LAYOUT ---- */
    .ch-dashboard-wrap {
        display: flex;
        min-height: 600px;
        gap: 0;
    }
    .ch-sidebar {
        width: 220px;
        flex-shrink: 0;
        background: var(--bntm-surface, #fff);
        border-right: 1px solid var(--bntm-border, #e5e7eb);
        padding: 20px 0;
    }
    .ch-sidebar-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 20px 20px;
        font-weight: 700;
        font-size: 16px;
        color: #6366f1;
        border-bottom: 1px solid var(--bntm-border, #e5e7eb);
        margin-bottom: 12px;
    }
    .ch-main-content {
        flex: 1;
        min-width: 0;
        padding: 28px 28px 40px;
        background: var(--bntm-bg, #f9fafb);
    }

    /* ---- NAV ---- */
    .ch-nav { display: flex; flex-direction: column; gap: 2px; padding: 0 10px; }
    .ch-nav-item {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 12px; border-radius: 8px;
        text-decoration: none; font-size: 14px; font-weight: 500;
        color: var(--bntm-text-muted, #6b7280);
        transition: all 0.15s;
    }
    .ch-nav-item:hover { background: #f3f4f6; color: #111827; }
    .ch-nav-item.active { background: #ede9fe; color: #6366f1; }

    /* ---- PAGE HEADER ---- */
    .ch-page-header {
        display: flex; justify-content: space-between; align-items: flex-start;
        margin-bottom: 24px;
    }
    .ch-page-header h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; }
    .ch-page-header p  { font-size: 13px; color: #6b7280; margin: 0; }

    /* ---- STATS ---- */
    .ch-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .ch-stat-card {
        background: var(--bntm-surface, #fff);
        border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 12px;
        padding: 18px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .ch-stat-card.ch-stat-alert { border-color: #fca5a5; background: #fff5f5; }
    .ch-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ch-stat-body { display: flex; flex-direction: column; }
    .ch-stat-num  { font-size: 24px; font-weight: 700; line-height: 1.1; }
    .ch-stat-label{ font-size: 12px; color: #6b7280; margin-top: 2px; }

    /* ---- CARDS ---- */
    .ch-card {
        background: var(--bntm-surface, #fff);
        border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .ch-card-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid var(--bntm-border, #e5e7eb);
    }
    .ch-card-header h3 { margin: 0; font-size: 15px; font-weight: 600; }
    .ch-card-body { padding: 0; }
    .ch-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

    /* ---- LISTS ---- */
    .ch-list-scroll { max-height: 380px; overflow-y: auto; }
    .ch-list-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 14px 20px;
        border-bottom: 1px solid #f3f4f6;
    }
    .ch-list-item:last-child { border-bottom: none; }
    .ch-list-item-main { flex: 1; min-width: 0; }
    .ch-list-title { font-size: 14px; font-weight: 600; margin: 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ch-list-meta  { font-size: 12px; color: #6b7280; }
    .ch-list-item-stats { display: flex; gap: 12px; font-size: 12px; color: #6b7280; flex-shrink: 0; margin-left: 12px; }

    /* ---- TRENDING ---- */
    .ch-trending-item {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 20px;
        border-bottom: 1px solid #f3f4f6;
    }
    .ch-trending-item:last-child { border-bottom: none; }
    .ch-trending-rank { font-size: 20px; font-weight: 800; color: #e5e7eb; min-width: 28px; text-align: center; }
    .ch-trending-content { flex: 1; min-width: 0; }
    .ch-trending-score { font-size: 12px; font-weight: 600; color: #6366f1; }

    /* ---- BADGES ---- */
    .ch-cat-badge {
        display: inline-block; padding: 2px 8px; border-radius: 20px;
        font-size: 11px; font-weight: 600; white-space: nowrap;
    }
    .ch-status-badge {
        display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }
    .ch-status-active    { background: #d1fae5; color: #065f46; }
    .ch-status-archived  { background: #f3f4f6; color: #6b7280; }
    .ch-status-removed   { background: #fee2e2; color: #991b1b; }
    .ch-status-pending   { background: #fef3c7; color: #92400e; }
    .ch-status-suspended { background: #fed7aa; color: #9a3412; }
    .ch-status-banned    { background: #fee2e2; color: #991b1b; }
    .ch-status-reviewed  { background: #dbeafe; color: #1e40af; }
    .ch-status-resolved  { background: #d1fae5; color: #065f46; }
    .ch-status-dismissed { background: #f3f4f6; color: #6b7280; }
    .ch-pin-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 600; color: #6366f1; background: #ede9fe; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px; }
    .ch-anon-badge { font-size: 10px; background: #f3f4f6; color: #6b7280; padding: 1px 6px; border-radius: 10px; margin-left: 6px; }
    .ch-type-badge { padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background: #f3f4f6; color: #374151; }

    /* ---- TABLE ---- */
    .ch-table th { background: #f9fafb; }
    .ch-table-empty { text-align: center; padding: 40px; color: #9ca3af; }
    .ch-post-cell { max-width: 300px; }
    .ch-post-excerpt { font-size: 12px; color: #6b7280; margin: 2px 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ch-mini-stat { display: inline-flex; align-items: center; gap: 3px; font-size: 12px; color: #6b7280; margin-right: 8px; }
    .ch-date { font-size: 12px; color: #9ca3af; }
    .ch-action-code { font-size: 11px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
    .ch-info-list { display: flex; flex-direction: column; gap: 12px; padding: 16px 0; }
    .ch-info-item { display: flex; justify-content: space-between; align-items: center; font-size: 13px; padding: 0 20px; }
    .ch-info-label { color: #6b7280; }
    .ch-user-cell { display: flex; align-items: center; gap: 8px; }

    /* ---- AVATAR ---- */
    .ch-avatar-sm {
        width: 32px; height: 32px; border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white; font-size: 13px; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .ch-avatar-xs { width: 26px; height: 26px; font-size: 11px; }

    /* ---- BUTTONS ---- */
    .ch-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
        border: none; cursor: pointer; transition: all 0.15s; text-decoration: none;
        white-space: nowrap;
    }
    .ch-btn-primary   { background: #6366f1; color: white; }
    .ch-btn-primary:hover { background: #4f46e5; }
    .ch-btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
    .ch-btn-secondary:hover { background: #e5e7eb; }
    .ch-btn-danger    { background: #ef4444; color: white; }
    .ch-btn-danger:hover { background: #dc2626; }
    .ch-btn-full { width: 100%; justify-content: center; }
    .ch-btn-sm   { padding: 5px 12px; font-size: 12px; }
    .ch-btn-xs   { padding: 3px 10px; font-size: 11px; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; }
    .ch-btn-warning { background: #fef3c7; color: #92400e; }
    .ch-btn-warning:hover { background: #fde68a; }
    .ch-btn-success { background: #d1fae5; color: #065f46; }
    .ch-btn-success:hover { background: #a7f3d0; }
    .ch-icon-btn {
        width: 30px; height: 30px; border-radius: 6px; border: 1px solid #e5e7eb;
        background: #fff; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
        transition: all 0.15s;
    }
    .ch-icon-btn:hover { background: #f3f4f6; border-color: #9ca3af; }
    .ch-icon-btn-danger:hover { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }
    .ch-link-btn { font-size: 12px; color: #6366f1; text-decoration: none; font-weight: 600; }
    .ch-link-btn:hover { text-decoration: underline; }
    .ch-actions-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: flex-end; }
    .bntm-table-wrapper { overflow-x: auto; }

    /* ---- FORM ---- */
    .ch-input {
        width: 100%; padding: 9px 12px; border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 8px; font-size: 14px; background: var(--bntm-surface, #fff);
        color: var(--bntm-text, #111827); box-sizing: border-box; outline: none;
        transition: border-color 0.15s;
    }
    .ch-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px #ede9fe; }
    .ch-textarea { resize: vertical; min-height: 80px; }
    .ch-textarea-lg { min-height: 130px; }
    .ch-color-input { height: 42px; padding: 3px 6px; cursor: pointer; }
    .ch-select-sm { padding: 6px 10px; font-size: 13px; border-radius: 6px; }
    .ch-field-group { margin-bottom: 16px; }
    .ch-field-row { display: flex; gap: 16px; }
    .ch-field-half { flex: 1; }
    .ch-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .ch-checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; }

    /* ---- TOOLBAR ---- */
    .ch-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
    .ch-toolbar-left { display: flex; align-items: center; gap: 12px; }
    .ch-toolbar-filters { display: flex; gap: 6px; }
    .ch-toolbar-right { display: flex; gap: 8px; align-items: center; }
    .ch-filter-btn {
        padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500;
        text-decoration: none; color: #6b7280; background: #f3f4f6;
        transition: all 0.15s;
    }
    .ch-filter-btn:hover { background: #e5e7eb; color: #111827; }
    .ch-filter-btn.active { background: #ede9fe; color: #6366f1; font-weight: 600; }
    .ch-guidelines-link {
        display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500;
        color: #6b7280; text-decoration: none; padding: 6px 12px; border-radius: 6px;
        transition: all 0.15s;
    }
    .ch-guidelines-link:hover { background: #f3f4f6; color: #111827; }
    .ch-guidelines-link svg { width: 14px; height: 14px; }
    .ch-search-form { display: flex; gap: 6px; align-items: center; }
    .ch-search-input { min-width: 200px; }

    /* ---- MODAL ---- */
    .ch-modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.45);
        display: flex; align-items: center; justify-content: center;
        z-index: 99999; padding: 20px;
    }
    .ch-modal {
        background: var(--bntm-surface, #fff);
        border-radius: 14px; width: 100%; max-width: 500px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .ch-modal-lg { max-width: 640px; }
    .ch-modal-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 18px 22px; border-bottom: 1px solid var(--bntm-border, #e5e7eb);
    }
    .ch-modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; }
    .ch-modal-close { background: none; border: none; font-size: 22px; cursor: pointer; color: #9ca3af; line-height: 1; padding: 0; }
    .ch-modal-close:hover { color: #374151; }
    .ch-modal-body { padding: 22px; }
    .ch-modal-footer {
        display: flex; justify-content: flex-end; gap: 10px;
        padding: 14px 22px; border-top: 1px solid var(--bntm-border, #e5e7eb);
    }

    /* ---- GUIDELINES ---- */
    .ch-guidelines-content h4 { margin: 0 0 16px; font-size: 16px; font-weight: 700; }
    .ch-guidelines-content h5 { margin: 20px 0 8px; font-size: 14px; font-weight: 600; color: #374151; }
    .ch-guidelines-content ul { margin: 8px 0 16px; padding-left: 20px; }
    .ch-guidelines-content li { margin-bottom: 4px; }
    .ch-guidelines-content p { margin: 12px 0; }
    .ch-guidelines-content strong { font-weight: 600; }

    /* ---- CATEGORIES GRID ---- */
    .ch-page-header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
        justify-content: flex-end;
    }
    .ch-page-header-actions > .ch-btn { height: 38px; }

    .ch-cat-hero-title { display: flex; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
    .ch-cat-hero-icon {
        width: 56px; height: 56px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; flex-shrink: 0;
    }
    .ch-cat-hero-text { flex: 1; }
    .ch-cat-hero-text h2 { margin: 0 0 4px; font-size: 22px; }
    .ch-cat-hero-text p { margin: 0; color: #6b7280; font-size: 13px; }

    .ch-cat-hero-meta {
        display: flex; align-items: center; justify-content: space-between;
        gap: 14px; margin-top: 18px;
        flex-wrap: wrap;
    }
    .ch-cat-meta-item { display: flex; flex-direction: column; text-align: center; }
    .ch-cat-meta-num { font-size: 18px; font-weight: 700; }
    .ch-cat-meta-label { font-size: 12px; color: #6b7280; }
    .ch-btn-sm { height: 34px; padding: 6px 14px; }

    .ch-categories-filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
        margin: 0;
    }
    .ch-categories-filter-form .ch-field-group { flex: 1; min-width: 170px; }
    .ch-categories-filter-form button { height: 38px; }

    .ch-categories-filter-sidebar {
        flex-direction: column;
        align-items: stretch;
    }
    .ch-categories-filter-sidebar .ch-field-group { width: 100%; margin-bottom: 8px; }
    .ch-categories-filter-sidebar button { width: 100%; }

    .ch-categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }
    .ch-cat-card {
        background: var(--bntm-surface, #fff);
        border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 12px;
        overflow: hidden;
        transition: box-shadow 0.15s;
    }
    .ch-cat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .ch-cat-card-color { height: 4px; }
    .ch-cat-card-body { padding: 16px; }
    .ch-cat-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
    .ch-cat-card-header h4 { margin: 0; font-size: 15px; font-weight: 600; }
    .ch-cat-desc { font-size: 13px; color: #6b7280; margin: 0 0 12px; line-height: 1.5; }
    .ch-cat-stats { display: flex; gap: 12px; align-items: center; font-size: 12px; color: #9ca3af; }

    /* ---- PAGINATION ---- */
    .ch-pagination { display: flex; gap: 4px; padding: 16px 20px; justify-content: center; }
    .ch-page-btn {
        width: 34px; height: 34px; border-radius: 6px; display: flex; align-items: center;
        justify-content: center; font-size: 13px; font-weight: 500; text-decoration: none;
        color: #374151; background: #f3f4f6; transition: all 0.15s;
    }
    .ch-page-btn:hover { background: #e5e7eb; }
    .ch-page-btn.active { background: #6366f1; color: white; }

    /* ---- EMPTY STATE ---- */
    .ch-empty { text-align: center; padding: 30px; color: #9ca3af; font-size: 14px; }
    .ch-empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
    .ch-empty-state p { margin-top: 12px; font-size: 14px; }

    /* ---- FEED LAYOUT ---- */
    .ch-feed-wrap { display: flex; gap: 28px; max-width: 1140px; margin: 0 auto; padding: 28px 20px; }
    .ch-feed-sidebar { width: 230px; flex-shrink: 0; }
    .ch-feed-main { flex: 1; min-width: 0; }

    .ch-sidebar-widget {
        background: var(--bntm-surface, #fff);
        border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 14px; padding: 16px; margin-bottom: 16px;
    }
    .ch-sidebar-widget h4 {
        margin: 0 0 14px;
        font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af;
    }
    .ch-cat-link {
        display: flex; align-items: center; gap: 8px; padding: 7px 10px;
        border-radius: 8px; text-decoration: none; font-size: 13px;
        color: #374151; transition: all 0.15s; justify-content: space-between;
    }
    .ch-cat-link:hover { background: #f3f4f6; }
    .ch-cat-link.active { background: #ede9fe; color: #6366f1; font-weight: 600; }
    .ch-cat-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .ch-cat-count { font-size: 11px; background: #f3f4f6; color: #9ca3af; padding: 1px 7px; border-radius: 10px; }
    .ch-cat-owner-actions { display: none; align-items: center; gap: 2px; flex-shrink: 0; }
    .ch-cat-item:hover .ch-cat-owner-actions { display: flex; }
    .ch-cat-action-btn {
        width: 22px; height: 22px; border-radius: 5px; border: none; background: transparent;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        color: #9ca3af; transition: background 0.15s, color 0.15s; padding: 0;
    }
    .ch-cat-action-btn:hover { background: #f3f4f6; color: #374151; }
    .ch-cat-action-delete:hover { background: #fee2e2; color: #ef4444; }

    .ch-cat-hero {
        background: var(--bntm-surface, #fff);
        border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 14px; padding: 20px; margin-bottom: 18px;
    }
    .ch-cat-hero h2 { margin: 0 0 4px; font-size: 18px; }
    .ch-cat-hero p  { margin: 0; font-size: 13px; color: #6b7280; }

    /* ---- FEED HEADER & TOOLBAR ---- */
    .ch-feed-header { margin-bottom: 18px; }
    .ch-feed-header h2 { font-size: 20px; font-weight: 700; margin: 0 0 14px; }
    .ch-feed-toolbar-card {
        background: var(--bntm-surface, #fff);
        border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 14px; padding: 14px 16px; margin-bottom: 18px;
    }
    .ch-search-form { display: flex; gap: 8px; margin-bottom: 12px; width: 100%; }
    .ch-search-input { flex: 1; }
    .ch-filter-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .ch-location-form { flex-shrink: 0; }
    .ch-location-select { width: 160px; padding: 6px 10px; border-radius: 8px; font-size: 13px; border: 1px solid #e5e7eb; background: #fff; }
    .ch-sort-tabs { display: flex; gap: 4px; }
    .ch-sort-tab {
        padding: 5px 14px; border-radius: 20px; font-size: 13px; font-weight: 500;
        text-decoration: none; color: #6b7280; background: #f3f4f6; transition: all 0.15s;
    }
    .ch-sort-tab:hover  { background: #e5e7eb; }
    .ch-sort-tab.active { background: #6366f1; color: #fff; font-weight: 600; }

    /* ---- POST CARDS ---- */
    .ch-posts-list { display: flex; flex-direction: column; gap: 10px; }
    .ch-post-card {
        background: var(--bntm-surface, #fff);
        border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 14px; padding: 18px 18px 14px;
        display: flex; gap: 14px;
        transition: box-shadow 0.15s, border-color 0.15s;
        position: relative;
    }
    .ch-post-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); border-color: #d1d5db; }
    .ch-post-pinned-ribbon {
        position: absolute; top: -1px; right: 18px;
        background: #6366f1; color: white; font-size: 10px; font-weight: 700;
        padding: 3px 10px 5px; border-radius: 0 0 8px 8px;
        display: inline-flex; align-items: center; gap: 4px;
    }

    /* Vote column */
    .ch-post-vote-col {
        display: flex; flex-direction: column; align-items: center; gap: 4px;
        flex-shrink: 0; padding-top: 2px;
    }
    .ch-vote-btn {
        width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e5e7eb;
        background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;
        color: #9ca3af; transition: all 0.15s;
    }
    .ch-vote-btn:hover { border-color: #6366f1; color: #6366f1; background: #ede9fe; }
    .ch-vote-btn.active-up { background: #ede9fe; border-color: #6366f1; color: #6366f1; }
    .ch-vote-btn.active-down { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }
    .ch-vote-count { font-size: 13px; font-weight: 700; color: #374151; min-width: 20px; text-align: center; }

    /* Post body */
    .ch-post-body { flex: 1; min-width: 0; }
    .ch-post-meta-row {
        display: flex; align-items: center; gap: 8px;
        flex-wrap: wrap; margin-bottom: 8px;
    }
    .ch-post-author { font-size: 12px; font-weight: 600; color: #374151; }
    .ch-post-location { display: inline-flex; align-items: center; gap: 3px; font-size: 12px; color: #9ca3af; }
    .ch-post-time   { font-size: 12px; color: #9ca3af; margin-left: auto; }
    .ch-post-title  { margin: 0 0 8px; font-size: 15px; font-weight: 600; line-height: 1.4; }
    .ch-post-title a { text-decoration: none; color: inherit; }
    .ch-post-title a:hover { color: #6366f1; }
    .ch-post-preview { font-size: 13px; color: #6b7280; margin: 0 0 10px; line-height: 1.6; }
    .ch-post-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
    .ch-tag { font-size: 11px; background: #f3f4f6; color: #6b7280; padding: 2px 8px; border-radius: 20px; cursor: pointer; transition: background 0.1s; }
    .ch-tag:hover { background: #ede9fe; color: #6366f1; }

    /* Action row */
    .ch-post-actions-row {
        display: flex; gap: 4px; align-items: center;
        padding-top: 10px; border-top: 1px solid #f3f4f6;
        flex-wrap: wrap;
    }
    .ch-post-action {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; color: #9ca3af; background: none; border: none;
        cursor: pointer; padding: 5px 10px; border-radius: 8px;
        text-decoration: none; transition: all 0.15s; font-weight: 500;
    }
    .ch-post-action:hover { color: #374151; background: #f3f4f6; }
    .ch-post-action.ch-bookmarked { color: #6366f1; }
    .ch-post-action.ch-bookmarked:hover { background: #ede9fe; }
    .ch-share-dropdown { position: relative; margin-left: auto; }
    .ch-share-menu {
        position: fixed;
        background: var(--bntm-surface, #fff); border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); min-width: 160px;
        display: none; z-index: 10000; overflow: hidden;
    }
    .ch-share-menu.show { display: block; }
    .ch-share-option {
        display: flex; align-items: center; gap: 9px; width: 100%;
        padding: 10px 14px; font-size: 13px; color: #374151; background: none; border: none;
        cursor: pointer; text-align: left; transition: background 0.1s;
    }
    .ch-share-option:hover { background: #f9fafb; }

    /* ---- MODERATION SETTINGS ---- */
    .ch-settings-grid { display: flex; flex-direction: column; gap: 0; }
    .ch-setting-row {
        display: flex; justify-content: space-between; align-items: center;
        gap: 24px; padding: 18px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .ch-setting-row:last-child { border-bottom: none; }
    .ch-setting-info { flex: 1; min-width: 0; }
    .ch-setting-label { font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 4px; }
    .ch-setting-desc  { font-size: 13px; color: #6b7280; line-height: 1.5; }
    .ch-setting-control { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .ch-setting-number { width: 80px; text-align: center; }
    .ch-setting-unit { font-size: 13px; color: #9ca3af; white-space: nowrap; }

    /* Toggle switch */
    .ch-toggle { position: relative; display: inline-block; cursor: pointer; }
    .ch-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
    .ch-toggle-track {
        display: block; width: 44px; height: 24px; border-radius: 12px;
        background: #e5e7eb; transition: background 0.2s;
        position: relative;
    }
    .ch-toggle input:checked + .ch-toggle-track { background: #6366f1; }
    .ch-toggle-thumb {
        position: absolute; top: 3px; left: 3px;
        width: 18px; height: 18px; border-radius: 50%;
        background: white; box-shadow: 0 1px 4px rgba(0,0,0,0.2);
        transition: transform 0.2s;
    }
    .ch-toggle input:checked + .ch-toggle-track .ch-toggle-thumb { transform: translateX(20px); }

    /* ---- POST VIEW ---- */
    .ch-post-view-wrap { max-width: 1000px; margin: 0 auto; padding: 24px 16px; }
    .ch-post-view-header { margin-bottom: 20px; }
    .ch-back-link {
        display: inline-flex; align-items: center; gap: 6px;
        text-decoration: none; color: #6366f1; font-size: 14px; font-weight: 600;
    }
    .ch-post-view-grid { display: grid; grid-template-columns: 1fr 260px; gap: 24px; }
    .ch-post-full {
        background: var(--bntm-surface, #fff);
        border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 14px; padding: 28px; margin-bottom: 20px;
    }
    .ch-post-full-title { font-size: 24px; font-weight: 700; margin: 0 0 16px; line-height: 1.3; }
    .ch-post-full-content { font-size: 15px; line-height: 1.7; color: #374151; margin-bottom: 16px; }
    .ch-post-media { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
    .ch-media-item { border-radius: 8px; overflow: hidden; }
    .ch-media-image { width: 100%; height: auto; display: block; }
    .ch-media-video { width: 100%; height: auto; display: block; }
    .ch-media-audio { width: 100%; display: block; }
    .ch-post-vote-bar { display: flex; align-items: center; gap: 14px; padding-top: 16px; border-top: 1px solid #f3f4f6; }
    .ch-vote-btn-lg {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 14px; border-radius: 8px; border: 1px solid #e5e7eb;
        background: #fff; cursor: pointer; font-size: 13px; font-weight: 600; color: #374151;
        transition: all 0.15s;
    }
    .ch-vote-btn-lg:hover { background: #f3f4f6; }
    .ch-vote-btn-lg.ch-vote-up:hover  { background: #ede9fe; border-color: #6366f1; color: #6366f1; }
    .ch-vote-btn-lg.ch-vote-down:hover{ background: #fee2e2; border-color: #fca5a5; color: #ef4444; }
    .ch-vote-btn-lg.ch-danger         { color: #ef4444; border-color: #fca5a5; }
    .ch-vote-btn-lg.ch-danger:hover   { background: #fee2e2; border-color: #ef4444; }
    .ch-vote-score { font-size: 16px; font-weight: 700; color: #6366f1; }

    /* ---- COMMENTS ---- */
    .ch-comments-section {
        background: var(--bntm-surface, #fff);
        border: 1px solid var(--bntm-border, #e5e7eb);
        border-radius: 14px; padding: 24px;
    }
    .ch-comments-title { font-size: 16px; font-weight: 700; margin: 0 0 20px; }
    .ch-comment-form { display: flex; gap: 12px; margin-bottom: 24px; }
    .ch-comment-input-wrap { flex: 1; }
    .ch-comment-form-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
    .ch-comments-list { display: flex; flex-direction: column; gap: 4px; }
    .ch-comment { display: flex; gap: 12px; padding: 14px 0; border-top: 1px solid #f3f4f6; }
    .ch-comment:first-child { border-top: none; }
    .ch-comment-reply { padding-left: 12px; }
    .ch-comment-body { flex: 1; }
    .ch-comment-header { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
    .ch-comment-header strong { font-size: 13px; }
    .ch-comment-time { font-size: 11px; color: #9ca3af; }
    .ch-comment-body p { font-size: 14px; color: #374151; margin: 0 0 8px; }
    .ch-comment-actions { display: flex; gap: 12px; }
    .ch-comment-action {
        font-size: 12px; color: #6b7280; background: none; border: none;
        cursor: pointer; padding: 0; transition: color 0.15s;
    }
    .ch-comment-action:hover { color: #374151; }
    .ch-danger-action:hover  { color: #ef4444; }
    .ch-mention {
        background-color: #dbeafe;
        color: #1d4ed8;
        padding: 2px 4px;
        border-radius: 4px;
        font-weight: 500;
    }
    .ch-replies { margin-top: 4px; padding-left: 16px; border-left: 2px solid #f3f4f6; }
    .ch-reply-form { margin-top: 10px; padding: 10px; background: #f9fafb; border-radius: 8px; }
    .ch-login-prompt { font-size: 14px; color: #6b7280; }
    .ch-login-prompt a { color: #6366f1; }
    .ch-post-view-sidebar > .ch-sidebar-widget { margin-bottom: 16px; }

    /* ---- RESPONSIVE ---- */
    @media (max-width: 768px) {
        .ch-dashboard-wrap { flex-direction: column; }
        .ch-sidebar { width: 100%; border-right: none; border-bottom: 1px solid #e5e7eb; }
        .ch-nav { flex-direction: row; flex-wrap: wrap; }
        .ch-two-col { grid-template-columns: 1fr; }
        .ch-feed-wrap { flex-direction: column; }
        .ch-feed-sidebar { width: 100%; }
        .ch-post-view-grid { grid-template-columns: 1fr; }
        .ch-categories-grid { grid-template-columns: 1fr; }
    }

    /* ---- ANNOUNCEMENTS ---- */
    .ch-announcement-card {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 1px solid #f59e0b;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        position: relative;
        overflow: hidden;
    }
    .ch-announcement-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #f59e0b, #d97706);
    }
    .ch-announcement-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }
    .ch-announcement-icon {
        color: #d97706;
        flex-shrink: 0;
    }
    .ch-announcement-meta {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .ch-announcement-label {
        font-weight: 600;
        font-size: 12px;
        color: #92400e;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .ch-announcement-author {
        font-size: 13px;
        color: #a16207;
    }
    .ch-announcement-time {
        font-size: 12px;
        color: #a16207;
        opacity: 0.8;
    }
    .ch-announcement-body {
        margin-left: 32px;
    }
    .ch-announcement-title {
        font-size: 18px;
        font-weight: 600;
        color: #92400e;
        margin: 0 0 8px 0;
    }
    .ch-announcement-content {
        color: #78350f;
        line-height: 1.6;
    }
    .ch-announcement-content p {
        margin: 0 0 8px 0;
    }
    .ch-announcement-content p:last-child {
        margin-bottom: 0;
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
        window.chOpenModal = function(id) {
            const el = document.getElementById(id);
            if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
        };
        window.chCloseModal = function(id) {
            const el = document.getElementById(id);
            if (el) { el.style.display = 'none'; document.body.style.overflow = ''; }
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
                if (json.success) {
                    const list = document.getElementById('ch-notifications-list');

                    if (json.data.notifications.length === 0) {
                        list.innerHTML = '<div class="ch-no-notifications">No notifications yet</div>';
                    } else {
                        list.innerHTML = json.data.notifications.map(n => `
                            <div class="ch-notification-item ${n.is_read ? '' : 'unread'}" onclick="chMarkNotificationRead(${n.id})">
                                <div class="ch-notification-icon">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                    </svg>
                                </div>
                                <div class="ch-notification-content">
                                    <div class="ch-notification-message">${n.message}</div>
                                    <div class="ch-notification-time">${n.created_at}</div>
                                </div>
                            </div>
                        `).join('');
                    }
                }
            })
            .catch(error => {
                console.error('Failed to load notifications:', error);
            });
        };

        window.chMarkNotificationRead = function(notificationId) {
            const fd = new FormData();
            fd.append('action', 'ch_mark_notifications');
            fd.append('notification_ids[]', notificationId);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    // Reload notifications
                    chLoadNotifications();
                    chLoadNotificationCount();
                }
            });
        };

        window.chMarkAllNotificationsRead = function() {
            const fd = new FormData();
            fd.append('action', 'ch_mark_notifications');

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    chLoadNotifications();
                    chLoadNotificationCount();
                }
            });
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

        window.chUpdateProfile = function(nonce) {
            const displayName = document.getElementById('ch-profile-display-name').value.trim();
            const bio = document.getElementById('ch-profile-bio').value.trim();
            const location = document.getElementById('ch-profile-location').value.trim();
            const isAnonymous = document.getElementById('ch-profile-anonymous').checked ? 1 : 0;

            const fd = new FormData();
            fd.append('action', 'ch_update_profile');
            fd.append('display_name', displayName);
            fd.append('bio', bio);
            fd.append('location', location);
            fd.append('is_anonymous', isAnonymous);

            const avatar = document.getElementById('ch-profile-avatar').files[0];
            if (avatar) {
                fd.append('avatar', avatar);
            }

            fd.append('nonce', nonce);

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Saving...';

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-profile-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) {
                    setTimeout(() => location.reload(), 1000);
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Save Profile';
                }
            })
            .catch(error => {
                console.error('Profile update failed:', error);
                document.getElementById('ch-profile-msg').innerHTML = '<div class="bntm-notice bntm-notice-error">Network error. Please try again.</div>';
                btn.disabled = false;
                btn.textContent = 'Save Profile';
            });
        };

        window.chSeedData = function(nonce) {
            const btn    = document.getElementById('ch-seed-btn');
            const result = document.getElementById('ch-seed-result');
            if (!btn) return;
            if (!confirm('This will insert sample categories, posts, comments, and users. Existing data will NOT be deleted. Continue?')) return;
            btn.disabled = true;
            btn.textContent = 'Loading…';
            const fd = new FormData();
            fd.append('action', 'ch_seed_data');
            fd.append('nonce',  nonce);
            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                btn.disabled = false;
                btn.textContent = 'Load Sample Data';
                if (result) {
                    result.textContent = json.success ? ('✓ ' + json.data.message) : ('✗ ' + (json.data?.message || 'Failed'));
                    result.style.color   = json.success ? '#059669' : '#dc2626';
                    result.style.display = 'block';
                }
                if (json.success) setTimeout(() => location.reload(), 1500);
            })
            .catch(() => {
                btn.disabled = false;
                btn.textContent = 'Load Sample Data';
                if (result) { result.textContent = '✗ Network error'; result.style.color = '#dc2626'; result.style.display = 'block'; }
            });
        };
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
                    const card = btn.closest('[data-id]') || btn.closest('.ch-post-card');
                    const counters = document.querySelectorAll('.ch-vote-count, .ch-vote-score');
                    counters.forEach(el => {
                        if (el.closest('[data-id="'+targetId+'"]') || (el.id === 'ch-post-score' && targetType === 'post')) {
                            el.textContent = json.data.vote_count + (el.id === 'ch-post-score' ? ' points' : '');
                        }
                    });
                    // Simple toggle highlight
                    if (value > 0) { btn.style.color = json.data.action === 'voted' ? '#6366f1' : '#9ca3af'; }
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

        window.chReportPost = function(postId, nonce) {
            document.getElementById('ch-report-target-id').value   = postId;
            document.getElementById('ch-report-target-type').value = 'post';
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
            const title   = document.getElementById('ch-post-title').value.trim();
            const content = document.getElementById('ch-post-content').value.trim();
            const catId   = document.getElementById('ch-post-cat').value;
            const tags    = document.getElementById('ch-post-tags').value;
            const isAnon  = document.getElementById('ch-post-anon').checked ? 1 : 0;

            if (!title || !content || !catId) { alert('Please fill in all required fields'); return; }

            const btn = document.getElementById('ch-submit-post-btn');
            btn.disabled = true; btn.textContent = 'Posting...';

            const fd = new FormData();
            fd.append('action', 'ch_create_post');
            fd.append('title', title);
            fd.append('content', content);
            fd.append('category_id', catId);
            fd.append('tags', tags);
            fd.append('is_anonymous', isAnon);
            fd.append('nonce', nonce);

            const media = document.getElementById('ch-post-media').files;
            for (let file of media) {
                fd.append('media[]', file);
            }

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                document.getElementById('ch-post-msg').innerHTML =
                    '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) setTimeout(() => location.reload(), 1200);
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
            btn.disabled = true; btn.textContent = 'Updating...';

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
                if (json.success) setTimeout(() => location.reload(), 1200);
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

            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (msgEl) msgEl.innerHTML = '<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+(json.data?.message||'')+'</div>';
                if (json.success) {
                    setTimeout(() => location.reload(), 1000);
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
                    location.reload();
                } else {
                    alert(json.data?.message || 'Failed to post comment');
                }
            });
        };

        window.chDeleteComment = function(commentId, nonce) {
            if (!confirm('Delete this comment?')) return;
            const fd = new FormData();
            fd.append('action', 'ch_delete_comment');
            fd.append('comment_id', commentId);
            fd.append('nonce', nonce);

            fetch(ajaxurl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const el = document.getElementById('ch-comment-' + commentId);
                    if (el) el.remove();
                } else {
                    alert(json.data?.message || 'Failed to delete');
                }
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
                            <div style="font-weight:600;font-size:14px;color:#111827">${a.title}</div>
                            <div style="font-size:12px;color:#9ca3af;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:340px">${a.content.replace(/<[^>]+>/g,'').substring(0,80)}…</div>
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
            btn.disabled = true; btn.textContent = 'Creating...';
            post('ch_create_announcement', {title, content, status}).then(json => {
                showMsg('ch-ann-create-msg', json.data?.message || (json.success?'Created!':'Failed'), json.success);
                if (json.success) {
                    setTimeout(() => { chCloseModal('ch-modal-create-announcement'); document.getElementById('ch-ann-create-title').value=''; document.getElementById('ch-ann-create-content').value=''; document.getElementById('ch-ann-create-status').checked=true; loadAnnouncements(); }, 800);
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
            btn.disabled = true; btn.textContent = 'Saving...';
            post('ch_edit_announcement', {announcement_id:id, title, content, status}).then(json => {
                showMsg('ch-ann-edit-msg', json.data?.message || (json.success?'Updated!':'Failed'), json.success);
                if (json.success) { setTimeout(() => { chCloseModal('ch-modal-edit-announcement'); loadAnnouncements(); }, 800); }
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
    $status = isset($_POST['status']) ? 1 : 0;

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
    $status = isset($_POST['status']) ? 1 : 0;

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