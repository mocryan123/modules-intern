<?php
if (!defined('ABSPATH')) exit;

function bntm_kbf_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    return [
        'kbf_funds' => "CREATE TABLE {$prefix}kbf_funds (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_token VARCHAR(64) UNIQUE,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            funder_type ENUM('yourself','someone_else','charity_event') NOT NULL DEFAULT 'yourself',
            title VARCHAR(255) NOT NULL,
            description LONGTEXT,
            photos TEXT COMMENT 'JSON array of photo URLs',
            goal_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            raised_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            category VARCHAR(100) DEFAULT 'Others',
            email VARCHAR(255),
            phone VARCHAR(50),
            valid_id_path VARCHAR(500),
            location VARCHAR(255),
            deadline DATE,
            auto_return TINYINT(1) DEFAULT 0,
            status ENUM('draft','pending','active','completed','cancelled','suspended') DEFAULT 'pending',
            verified_badge TINYINT(1) DEFAULT 0,
            share_token VARCHAR(64) UNIQUE,
            escrow_status ENUM('holding','released','refunded') DEFAULT 'holding',
            admin_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_status (status),
            INDEX idx_category (category),
            INDEX idx_share_token (share_token),
            INDEX idx_fund_token (fund_token)
        ) {$charset};",

        'kbf_sponsorships' => "CREATE TABLE {$prefix}kbf_sponsorships (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            sponsor_name VARCHAR(255),
            is_anonymous TINYINT(1) DEFAULT 0,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            email VARCHAR(255),
            phone VARCHAR(50),
            payment_method ENUM('gcash','paymaya','bank_transfer') DEFAULT 'gcash',
            payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
            payment_reference VARCHAR(255),
            receipt_path VARCHAR(500),
            gateway_payload LONGTEXT COMMENT 'Placeholder: store raw gateway JSON response here',
            receipt_sent TINYINT(1) DEFAULT 0,
            notified TINYINT(1) DEFAULT 0,
            message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_fund (fund_id),
            INDEX idx_status (payment_status)
        ) {$charset};",

        'kbf_withdrawals' => "CREATE TABLE {$prefix}kbf_withdrawals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            funder_name VARCHAR(255),
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            method VARCHAR(100),
            account_type VARCHAR(100),
            account_name VARCHAR(255),
            account_number VARCHAR(255),
            account_details TEXT,
            status ENUM('pending','approved','released','rejected') DEFAULT 'pending',
            admin_notes TEXT,
            requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME,
            INDEX idx_fund (fund_id),
            INDEX idx_status (status)
        ) {$charset};",

        'kbf_reports' => "CREATE TABLE {$prefix}kbf_reports (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            reporter_id BIGINT UNSIGNED DEFAULT 0,
            reporter_email VARCHAR(255),
            reason VARCHAR(255),
            details TEXT,
            report_image VARCHAR(500),
            status ENUM('open','reviewed','dismissed') DEFAULT 'open',
            admin_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_fund (fund_id),
            INDEX idx_status (status)
        ) {$charset};",

        'kbf_appeals' => "CREATE TABLE {$prefix}kbf_appeals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            status ENUM('open','reviewed','approved','rejected') DEFAULT 'open',
            admin_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_fund (fund_id),
            INDEX idx_business (business_id),
            INDEX idx_status (status)
        ) {$charset};",

        'kbf_organizer_profiles' => "CREATE TABLE {$prefix}kbf_organizer_profiles (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            business_id BIGINT UNSIGNED NOT NULL UNIQUE,
            organizer_token VARCHAR(64) UNIQUE,
            bio TEXT,
            avatar_url VARCHAR(500),
            social_links TEXT COMMENT 'JSON: facebook, instagram, twitter',
            payout_type VARCHAR(50),
            payout_name VARCHAR(255),
            payout_number VARCHAR(255),
            is_verified TINYINT(1) DEFAULT 0,
            verify_id_front VARCHAR(500),
            verify_id_back VARCHAR(500),
            verify_status ENUM('none','pending','approved','rejected') DEFAULT 'none',
            verify_submitted_at DATETIME,
            verify_reviewed_at DATETIME,
            verify_notes TEXT,
            total_raised DECIMAL(15,2) DEFAULT 0.00,
            total_sponsors INT DEFAULT 0,
            rating DECIMAL(3,2) DEFAULT 0.00,
            rating_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_organizer_token (organizer_token)
        ) {$charset};",

        'kbf_ratings' => "CREATE TABLE {$prefix}kbf_ratings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            organizer_id BIGINT UNSIGNED NOT NULL,
            sponsor_email VARCHAR(255),
            rating TINYINT NOT NULL DEFAULT 5,
            review TEXT,
            fund_id BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_organizer (organizer_id)
        ) {$charset};",

        'kbf_saved_funds' => "CREATE TABLE {$prefix}kbf_saved_funds (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_fund (user_id, fund_id),
            INDEX idx_user (user_id),
            INDEX idx_fund (fund_id)
        ) {$charset};",
    ];
}

function bntm_kbf_ensure_profile_columns() {
    global $wpdb;
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $existing = $wpdb->get_col("SHOW COLUMNS FROM {$pt}");
    $cols = [
        'verify_id_front'     => "ALTER TABLE {$pt} ADD COLUMN verify_id_front VARCHAR(500) NULL",
        'verify_id_back'      => "ALTER TABLE {$pt} ADD COLUMN verify_id_back VARCHAR(500) NULL",
        'verify_status'       => "ALTER TABLE {$pt} ADD COLUMN verify_status ENUM('none','pending','approved','rejected') DEFAULT 'none'",
        'verify_submitted_at' => "ALTER TABLE {$pt} ADD COLUMN verify_submitted_at DATETIME NULL",
        'verify_reviewed_at'  => "ALTER TABLE {$pt} ADD COLUMN verify_reviewed_at DATETIME NULL",
        'verify_notes'        => "ALTER TABLE {$pt} ADD COLUMN verify_notes TEXT NULL",
        'organizer_token'     => "ALTER TABLE {$pt} ADD COLUMN organizer_token VARCHAR(64) NULL",
        'payout_type'         => "ALTER TABLE {$pt} ADD COLUMN payout_type VARCHAR(50) NULL",
        'payout_name'         => "ALTER TABLE {$pt} ADD COLUMN payout_name VARCHAR(255) NULL",
        'payout_number'       => "ALTER TABLE {$pt} ADD COLUMN payout_number VARCHAR(255) NULL",
    ];
    foreach ($cols as $col => $sql) {
        if (!in_array($col, $existing, true)) {
            $wpdb->query($sql);
        }
    }
    if (in_array('organizer_token', $existing, true)) {
        $idx = $wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$pt} WHERE Key_name=%s", 'idx_organizer_token'));
        if (!$idx) {
            $wpdb->query("CREATE INDEX idx_organizer_token ON {$pt} (organizer_token)");
        }
        // Backfill tokens for existing rows
        $missing = $wpdb->get_col("SELECT business_id FROM {$pt} WHERE organizer_token IS NULL OR organizer_token=''");
        foreach ($missing as $biz_id) {
            kbf_get_or_create_organizer_token((int)$biz_id);
        }
    }
}
add_action('init', 'bntm_kbf_ensure_profile_columns');

function bntm_kbf_ensure_fund_columns() {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $existing = $wpdb->get_col("SHOW COLUMNS FROM {$ft}");
    $cols = [
        'fund_token' => "ALTER TABLE {$ft} ADD COLUMN fund_token VARCHAR(64) NULL",
    ];
    foreach ($cols as $col => $sql) {
        if (!in_array($col, $existing, true)) {
            $wpdb->query($sql);
        }
    }
    if (in_array('fund_token', $existing, true)) {
        $idx = $wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$ft} WHERE Key_name=%s", 'idx_fund_token'));
        if (!$idx) {
            $wpdb->query("CREATE INDEX idx_fund_token ON {$ft} (fund_token)");
        }
        $missing = $wpdb->get_col("SELECT id FROM {$ft} WHERE fund_token IS NULL OR fund_token=''");
        foreach ($missing as $fid) {
            kbf_get_or_create_fund_token((int)$fid);
        }
    }
}
add_action('init', 'bntm_kbf_ensure_fund_columns');

function bntm_kbf_ensure_report_columns() {
    global $wpdb;
    $rt = $wpdb->prefix.'kbf_reports';
    $existing = $wpdb->get_col("SHOW COLUMNS FROM {$rt}");
    $cols = [
        'report_image' => "ALTER TABLE {$rt} ADD COLUMN report_image VARCHAR(500) NULL",
    ];
    foreach ($cols as $col => $sql) {
        if (!in_array($col, $existing, true)) {
            $wpdb->query($sql);
        }
    }
}
add_action('init', 'bntm_kbf_ensure_report_columns');

function bntm_kbf_ensure_withdrawal_columns() {
    global $wpdb;
    $wt = $wpdb->prefix.'kbf_withdrawals';
    $existing = $wpdb->get_col("SHOW COLUMNS FROM {$wt}");
    $cols = [
        'account_type' => "ALTER TABLE {$wt} ADD COLUMN account_type VARCHAR(100) NULL",
    ];
    foreach ($cols as $col => $sql) {
        if (!in_array($col, $existing, true)) {
            $wpdb->query($sql);
        }
    }
}
add_action('init', 'bntm_kbf_ensure_withdrawal_columns');

function bntm_kbf_ensure_perf_indexes() {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $st = $wpdb->prefix.'kbf_sponsorships';
    $rt = $wpdb->prefix.'kbf_ratings';

    $idx = $wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$ft} WHERE Key_name=%s", 'idx_business_status'));
    if (!$idx) {
        $wpdb->query("CREATE INDEX idx_business_status ON {$ft} (business_id, status)");
    }

    $idx = $wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$st} WHERE Key_name=%s", 'idx_fund_status'));
    if (!$idx) {
        $wpdb->query("CREATE INDEX idx_fund_status ON {$st} (fund_id, payment_status)");
    }

    $idx = $wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$rt} WHERE Key_name=%s", 'idx_organizer_email'));
    if (!$idx) {
        $wpdb->query("CREATE INDEX idx_organizer_email ON {$rt} (organizer_id, sponsor_email)");
    }
}
add_action('init', 'bntm_kbf_ensure_perf_indexes');

function kbf_get_or_create_organizer_token($business_id) {
    global $wpdb;
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $token = $wpdb->get_var($wpdb->prepare("SELECT organizer_token FROM {$pt} WHERE business_id=%d", $business_id));
    if (!empty($token)) return $token;
    // Ensure profile row exists
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d", $business_id));
    if (!$exists) {
        $wpdb->insert($pt, ['business_id'=>$business_id], ['%d']);
    }
    // Generate unique token
    do {
        $token = wp_generate_password(32, false, false);
        $dup = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE organizer_token=%s", $token));
    } while ($dup);
    $wpdb->update($pt, ['organizer_token'=>$token], ['business_id'=>$business_id], ['%s'], ['%d']);
    return $token;
}

function kbf_get_or_create_fund_token($fund_id) {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $token = $wpdb->get_var($wpdb->prepare("SELECT fund_token FROM {$ft} WHERE id=%d", $fund_id));
    if (!empty($token)) return $token;
    do {
        $token = wp_generate_password(32, false, false);
        $dup = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$ft} WHERE fund_token=%s", $token));
    } while ($dup);
    $wpdb->update($ft, ['fund_token'=>$token], ['id'=>$fund_id], ['%s'], ['%d']);
    return $token;
}

function kbf_get_fund_tokens($fund_ids) {
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)$fund_ids))));
    if (empty($ids)) return [];

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, fund_token FROM {$ft} WHERE id IN ($placeholders)",
        $ids
    ));
    $map = [];
    foreach ($rows as $r) {
        if (!empty($r->fund_token)) {
            $map[(int)$r->id] = (string)$r->fund_token;
        }
    }
    foreach ($ids as $id) {
        if (empty($map[$id])) {
            $map[$id] = kbf_get_or_create_fund_token($id);
        }
    }
    return $map;
}

function bntm_kbf_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_kbf_get_tables();
    foreach ($tables as $sql) { dbDelta($sql); }
    return count($tables);
}

function bntm_kbf_ensure_saved_funds_table() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_kbf_get_tables();
    if (!empty($tables['kbf_saved_funds'])) {
        dbDelta($tables['kbf_saved_funds']);
    }
}
add_action('init', 'bntm_kbf_maybe_update_db', 1);
function bntm_kbf_maybe_update_db() {
    $target = '2.0.3';
    $installed = get_option('kbf_db_version');
    if ($installed !== $target) {
        bntm_kbf_create_tables();
        update_option('kbf_db_version', $target);
    }
}

