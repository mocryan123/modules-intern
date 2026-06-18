<?php
/**
 * Module Name: Boarding House Management
 * Module Slug: bh
 * Description: Centralizes tenant management, room allocation, rental billing, payment processing, maintenance request management, notification services, reporting, and system administration for boarding house operators.
 * Version: 1.0.0
 * Author: BNTM
 * Icon: <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
 */

if (!defined('ABSPATH')) exit;

define('BNTM_BH_PATH', dirname(__FILE__) . '/');
define('BNTM_BH_URL', plugin_dir_url(__FILE__));

// ============================================================
// MODULE CONFIGURATION
// ============================================================

function bntm_bh_get_pages() {
    return [
        'Boarding House Dashboard' => '[bh_dashboard]',
        'Tenant Portal'            => '[bh_tenant_portal]',
        'Maintenance Request Form' => '[bh_maintenance_form]',
    ];
}

function bntm_bh_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    return [
        'bh_tenants' => "CREATE TABLE {$prefix}bh_tenants (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(30) DEFAULT '',
            address TEXT DEFAULT '',
            birthdate DATE DEFAULT NULL,
            gender VARCHAR(20) DEFAULT '',
            id_type VARCHAR(50) DEFAULT '',
            id_number VARCHAR(100) DEFAULT '',
            id_photo TEXT DEFAULT '',
            emergency_contact_name VARCHAR(150) DEFAULT '',
            emergency_contact_phone VARCHAR(30) DEFAULT '',
            emergency_contact_relation VARCHAR(50) DEFAULT '',
            move_in_date DATE DEFAULT NULL,
            move_out_date DATE DEFAULT NULL,
            lease_start DATE DEFAULT NULL,
            lease_end DATE DEFAULT NULL,
            deposit_amount DECIMAL(12,2) DEFAULT 0.00,
            account_balance DECIMAL(12,2) DEFAULT 0.00,
            notes TEXT DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'bh_rooms' => "CREATE TABLE {$prefix}bh_rooms (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            room_number VARCHAR(20) NOT NULL,
            floor VARCHAR(20) DEFAULT '',
            category VARCHAR(30) DEFAULT 'single',
            capacity INT DEFAULT 1,
            monthly_rate DECIMAL(12,2) DEFAULT 0.00,
            description TEXT DEFAULT '',
            amenities TEXT DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'vacant',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'bh_room_assignments' => "CREATE TABLE {$prefix}bh_room_assignments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            tenant_id BIGINT UNSIGNED NOT NULL,
            room_id BIGINT UNSIGNED NOT NULL,
            assigned_date DATE NOT NULL,
            vacated_date DATE DEFAULT NULL,
            assigned_by BIGINT UNSIGNED DEFAULT NULL,
            notes TEXT DEFAULT '',
            is_current TINYINT(1) DEFAULT 1,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'bh_invoices' => "CREATE TABLE {$prefix}bh_invoices (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            tenant_id BIGINT UNSIGNED NOT NULL,
            room_id BIGINT UNSIGNED NOT NULL,
            billing_period_start DATE NOT NULL,
            billing_period_end DATE NOT NULL,
            due_date DATE NOT NULL,
            base_rent DECIMAL(12,2) DEFAULT 0.00,
            penalty_amount DECIMAL(12,2) DEFAULT 0.00,
            discount_amount DECIMAL(12,2) DEFAULT 0.00,
            deposit_applied DECIMAL(12,2) DEFAULT 0.00,
            other_fees TEXT DEFAULT '',
            total_amount DECIMAL(12,2) DEFAULT 0.00,
            amount_paid DECIMAL(12,2) DEFAULT 0.00,
            balance DECIMAL(12,2) DEFAULT 0.00,
            invoice_status VARCHAR(30) NOT NULL DEFAULT 'unpaid',
            notes TEXT DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'bh_payments' => "CREATE TABLE {$prefix}bh_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            invoice_id BIGINT UNSIGNED NOT NULL,
            tenant_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            payment_method VARCHAR(30) DEFAULT 'cash',
            reference_number VARCHAR(100) DEFAULT '',
            receipt_number VARCHAR(50) DEFAULT '',
            payment_date DATE NOT NULL,
            recorded_by BIGINT UNSIGNED DEFAULT NULL,
            notes TEXT DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'completed',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'bh_maintenance_requests' => "CREATE TABLE {$prefix}bh_maintenance_requests (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            ticket_number VARCHAR(30) UNIQUE NOT NULL,
            tenant_id BIGINT UNSIGNED NOT NULL,
            room_id BIGINT UNSIGNED DEFAULT NULL,
            category VARCHAR(50) DEFAULT 'other',
            priority VARCHAR(20) DEFAULT 'medium',
            description TEXT DEFAULT '',
            attachment TEXT DEFAULT '',
            assigned_to BIGINT UNSIGNED DEFAULT NULL,
            resolution_notes TEXT DEFAULT '',
            resolved_at DATETIME DEFAULT NULL,
            verified_at DATETIME DEFAULT NULL,
            closed_at DATETIME DEFAULT NULL,
            workflow_status VARCHAR(30) NOT NULL DEFAULT 'submitted',
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'bh_maintenance_history' => "CREATE TABLE {$prefix}bh_maintenance_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            request_id BIGINT UNSIGNED NOT NULL,
            changed_by BIGINT UNSIGNED DEFAULT NULL,
            old_status VARCHAR(30) DEFAULT '',
            new_status VARCHAR(30) DEFAULT '',
            note TEXT DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'bh_notifications' => "CREATE TABLE {$prefix}bh_notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            tenant_id BIGINT UNSIGNED DEFAULT NULL,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            type VARCHAR(60) DEFAULT '',
            channel VARCHAR(20) DEFAULT 'in_app',
            subject VARCHAR(255) DEFAULT '',
            message TEXT DEFAULT '',
            is_read TINYINT(1) DEFAULT 0,
            sent_at DATETIME DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'sent',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'bh_notification_templates' => "CREATE TABLE {$prefix}bh_notification_templates (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            template_key VARCHAR(80) UNIQUE NOT NULL,
            name VARCHAR(150) NOT NULL,
            subject VARCHAR(255) DEFAULT '',
            body TEXT DEFAULT '',
            channel VARCHAR(20) DEFAULT 'in_app',
            placeholders TEXT DEFAULT '',
            is_active TINYINT(1) DEFAULT 1,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'bh_audit_log' => "CREATE TABLE {$prefix}bh_audit_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            action VARCHAR(80) DEFAULT '',
            entity_type VARCHAR(60) DEFAULT '',
            entity_id BIGINT UNSIGNED DEFAULT NULL,
            old_value LONGTEXT DEFAULT '',
            new_value LONGTEXT DEFAULT '',
            ip_address VARCHAR(45) DEFAULT '',
            description TEXT DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",
    ];
}

function bntm_bh_get_shortcodes() {
    return [
        'bh_dashboard'        => 'bntm_shortcode_bh',
        'bh_tenant_portal'    => 'bntm_shortcode_bh_tenant_portal',
        'bh_maintenance_form' => 'bntm_shortcode_bh_maintenance_form',
    ];
}

function bntm_bh_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_bh_get_tables();
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
    bh_seed_notification_templates();
    return count($tables);
}

// ============================================================
// AJAX ACTION HOOKS
// ============================================================

// Dashboard / Admin
add_action('wp_ajax_bh_get_dashboard_stats',        'bntm_ajax_bh_get_dashboard_stats');
add_action('wp_ajax_bh_get_recent_activity',        'bntm_ajax_bh_get_recent_activity');

// Tenants
add_action('wp_ajax_bh_add_tenant',                 'bntm_ajax_bh_add_tenant');
add_action('wp_ajax_bh_edit_tenant',                'bntm_ajax_bh_edit_tenant');
add_action('wp_ajax_bh_get_tenant',                 'bntm_ajax_bh_get_tenant');
add_action('wp_ajax_bh_update_tenant_status',       'bntm_ajax_bh_update_tenant_status');
add_action('wp_ajax_bh_delete_tenant',              'bntm_ajax_bh_delete_tenant');

// Rooms
add_action('wp_ajax_bh_add_room',                   'bntm_ajax_bh_add_room');
add_action('wp_ajax_bh_edit_room',                  'bntm_ajax_bh_edit_room');
add_action('wp_ajax_bh_delete_room',                'bntm_ajax_bh_delete_room');
add_action('wp_ajax_bh_assign_tenant',              'bntm_ajax_bh_assign_tenant');
add_action('wp_ajax_bh_transfer_tenant',            'bntm_ajax_bh_transfer_tenant');
add_action('wp_ajax_bh_get_room_history',           'bntm_ajax_bh_get_room_history');
add_action('wp_ajax_bh_toggle_room_status',         'bntm_ajax_bh_toggle_room_status');

// Billing
add_action('wp_ajax_bh_generate_invoice',           'bntm_ajax_bh_generate_invoice');
add_action('wp_ajax_bh_bulk_generate_invoices',     'bntm_ajax_bh_bulk_generate_invoices');
add_action('wp_ajax_bh_add_charge',                 'bntm_ajax_bh_add_charge');
add_action('wp_ajax_bh_get_invoice',                'bntm_ajax_bh_get_invoice');
add_action('wp_ajax_bh_delete_invoice',             'bntm_ajax_bh_delete_invoice');
add_action('wp_ajax_bh_get_ledger',                 'bntm_ajax_bh_get_ledger');

// Payments
add_action('wp_ajax_bh_record_payment',             'bntm_ajax_bh_record_payment');
add_action('wp_ajax_bh_get_payment',                'bntm_ajax_bh_get_payment');
add_action('wp_ajax_bh_delete_payment',             'bntm_ajax_bh_delete_payment');
add_action('wp_ajax_bh_get_outstanding_balances',   'bntm_ajax_bh_get_outstanding_balances');

// Maintenance
add_action('wp_ajax_bh_get_request',                'bntm_ajax_bh_get_request');
add_action('wp_ajax_bh_assign_request',             'bntm_ajax_bh_assign_request');
add_action('wp_ajax_bh_update_request_status',      'bntm_ajax_bh_update_request_status');
add_action('wp_ajax_bh_add_request_note',           'bntm_ajax_bh_add_request_note');
add_action('wp_ajax_bh_delete_request',             'bntm_ajax_bh_delete_request');

// Tenant: submit maintenance from portal
add_action('wp_ajax_bh_submit_maintenance',         'bntm_ajax_bh_submit_maintenance');
add_action('wp_ajax_nopriv_bh_submit_maintenance',  'bntm_ajax_bh_submit_maintenance');

// Notifications
add_action('wp_ajax_bh_get_notification_log',       'bntm_ajax_bh_get_notification_log');
add_action('wp_ajax_bh_save_template',              'bntm_ajax_bh_save_template');
add_action('wp_ajax_bh_get_template',               'bntm_ajax_bh_get_template');
add_action('wp_ajax_bh_send_manual_notification',   'bntm_ajax_bh_send_manual_notification');
add_action('wp_ajax_bh_mark_notification_read',     'bntm_ajax_bh_mark_notification_read');
add_action('wp_ajax_nopriv_bh_mark_notification_read', 'bntm_ajax_bh_mark_notification_read');

// Reports
add_action('wp_ajax_bh_get_occupancy_report',       'bntm_ajax_bh_get_occupancy_report');
add_action('wp_ajax_bh_get_revenue_report',         'bntm_ajax_bh_get_revenue_report');
add_action('wp_ajax_bh_get_tenant_report',          'bntm_ajax_bh_get_tenant_report');
add_action('wp_ajax_bh_get_maintenance_report',     'bntm_ajax_bh_get_maintenance_report');

// Audit
add_action('wp_ajax_bh_get_audit_log',              'bntm_ajax_bh_get_audit_log');

// Settings
add_action('wp_ajax_bh_save_settings',              'bntm_ajax_bh_save_settings');
add_action('wp_ajax_bh_add_staff',                  'bntm_ajax_bh_add_staff');
add_action('wp_ajax_bh_delete_staff',               'bntm_ajax_bh_delete_staff');
add_action('wp_ajax_bh_add_payment_method',         'bntm_ajax_bh_add_payment_method');
add_action('wp_ajax_bh_remove_payment_method',      'bntm_ajax_bh_remove_payment_method');

// Finance export
add_action('wp_ajax_bh_fn_export_payment',          'bntm_ajax_bh_fn_export_payment');
add_action('wp_ajax_bh_fn_revert_payment',          'bntm_ajax_bh_fn_revert_payment');

// Tenant portal data
add_action('wp_ajax_bh_portal_get_data',            'bntm_ajax_bh_portal_get_data');
add_action('wp_ajax_bh_portal_pay_invoice',         'bntm_ajax_bh_portal_pay_invoice');
add_action('wp_ajax_bh_portal_submit_payment',     'bntm_ajax_bh_portal_submit_payment');

// ============================================================
// MAIN DASHBOARD SHORTCODE
// ============================================================

function bntm_shortcode_bh() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to access the dashboard.</div>';
    }

    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
    $nonce      = wp_create_nonce('bh_nonce');

    ob_start();
    ?>
    <script>
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var bh_nonce = '<?php echo $nonce; ?>';
    </script>

    <div class="bntm-bh-container">
        <div class="bntm-tabs">
            <?php
            $tabs = [
                'overview'      => 'Overview',
                'tenants'       => 'Tenants',
                'rooms'         => 'Rooms',
                'billing'       => 'Billing',
                'payments'      => 'Payments',
                'maintenance'   => 'Maintenance',
                'notifications' => 'Notifications',
                'reports'       => 'Reports',
                'audit'         => 'Audit Log',
                'settings'      => 'Settings',
            ];
            foreach ($tabs as $slug => $label):
            ?>
            <a href="?tab=<?php echo $slug; ?>" class="bntm-tab <?php echo $active_tab === $slug ? 'active' : ''; ?>">
                <?php echo $label; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="bntm-tab-content">
            <?php
            switch ($active_tab) {
                case 'overview':      echo bh_overview_tab();      break;
                case 'tenants':       echo bh_tenants_tab();       break;
                case 'rooms':         echo bh_rooms_tab();         break;
                case 'billing':       echo bh_billing_tab();       break;
                case 'payments':      echo bh_payments_tab();      break;
                case 'maintenance':   echo bh_maintenance_tab();   break;
                case 'notifications': echo bh_notifications_tab(); break;
                case 'reports':       echo bh_reports_tab();       break;
                case 'audit':         echo bh_audit_tab();         break;
                case 'settings':      echo bh_settings_tab();      break;
                default:              echo bh_overview_tab();      break;
            }
            ?>
        </div>
    </div>

    <div id="bh-modal-overlay" class="bh-modal-overlay" style="display:none;">
        <div id="bh-modal-box" class="bh-modal-box">
            <div class="bh-modal-header">
                <h3 id="bh-modal-title">Modal</h3>
                <button class="bh-modal-close" onclick="bhCloseModal()">&times;</button>
            </div>
            <div id="bh-modal-body" class="bh-modal-body"></div>
        </div>
    </div>

    <div id="bh-toast" class="bh-toast" style="display:none;"></div>

    <style>
    .bntm-bh-container { width:100%; }
    .bh-modal-overlay { position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.55);z-index:99990;display:flex;align-items:center;justify-content:center; }
    .bh-modal-box { background:#fff;border-radius:12px;width:90%;max-width:680px;max-height:88vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3); }
    .bh-modal-header { display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;border-bottom:1px solid #e5e7eb; }
    .bh-modal-header h3 { margin:0;font-size:16px;font-weight:600;color:#111827; }
    .bh-modal-close { background:none;border:none;font-size:22px;cursor:pointer;color:#9ca3af;line-height:1; }
    .bh-modal-close:hover { color:#374151; }
    .bh-modal-body { padding:24px; }
    .bh-form-row { display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px; }
    .bh-form-row.single { grid-template-columns:1fr; }
    .bh-form-row.triple { grid-template-columns:1fr 1fr 1fr; }
    .bh-form-group { display:flex;flex-direction:column;gap:5px; }
    .bh-form-group label { font-size:13px;font-weight:500;color:#374151; }
    .bh-form-group input, .bh-form-group select, .bh-form-group textarea { border:1px solid #d1d5db;border-radius:6px;padding:8px 12px;font-size:14px;color:#111827;background:#fff;transition:border-color .15s; }
    .bh-form-group input:focus, .bh-form-group select:focus, .bh-form-group textarea:focus { outline:none;border-color:var(--bntm-primary,#6366f1); }
    .bh-form-group textarea { resize:vertical;min-height:80px; }
    .bh-modal-footer { padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px; }
    .bh-status-badge { display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:500; }
    .bh-badge-active, .bh-badge-paid, .bh-badge-occupied, .bh-badge-completed, .bh-badge-closed, .bh-badge-resolved { background:#d1fae5;color:#065f46; }
    .bh-badge-pending, .bh-badge-unpaid, .bh-badge-submitted, .bh-badge-under_review { background:#fef3c7;color:#92400e; }
    .bh-badge-inactive, .bh-badge-vacant, .bh-badge-cancelled { background:#f3f4f6;color:#374151; }
    .bh-badge-overdue, .bh-badge-emergency, .bh-badge-moved-out { background:#fee2e2;color:#991b1b; }
    .bh-badge-partial, .bh-badge-in_progress, .bh-badge-assigned { background:#dbeafe;color:#1e40af; }
    .bh-badge-maintenance, .bh-badge-pending_approval, .bh-badge-pending_materials { background:#ede9fe;color:#5b21b6; }
    .bh-badge-verified { background:#cffafe;color:#155e75; }
    .bh-toast { position:fixed;bottom:28px;right:28px;z-index:99999;padding:14px 22px;border-radius:8px;font-size:14px;font-weight:500;color:#fff;box-shadow:0 4px 20px rgba(0,0,0,.18);max-width:340px; }
    .bh-toast.success { background:#059669; }
    .bh-toast.error   { background:#dc2626; }
    .bh-section-actions { display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:12px;flex-wrap:wrap; }
    .bh-filter-row { display:flex;gap:10px;align-items:center;flex-wrap:wrap; }
    .bh-filter-row select, .bh-filter-row input { border:1px solid #d1d5db;border-radius:6px;padding:7px 12px;font-size:13px;background:#fff; }
    @media(max-width:600px){.bh-form-row{grid-template-columns:1fr;}.bh-form-row.triple{grid-template-columns:1fr;}}
    </style>

    <script>
    function bhOpenModal(title, bodyHtml, size) {
        document.getElementById('bh-modal-title').textContent = title;
        document.getElementById('bh-modal-body').innerHTML = bodyHtml;
        if (size === 'lg') document.getElementById('bh-modal-box').style.maxWidth = '860px';
        else document.getElementById('bh-modal-box').style.maxWidth = '680px';
        document.getElementById('bh-modal-overlay').style.display = 'flex';
    }
    function bhCloseModal() {
        document.getElementById('bh-modal-overlay').style.display = 'none';
        document.getElementById('bh-modal-body').innerHTML = '';
    }
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'bh-modal-overlay') bhCloseModal();
    });
    function bhToast(msg, type) {
        const t = document.getElementById('bh-toast');
        t.textContent = msg;
        t.className = 'bh-toast ' + (type || 'success');
        t.style.display = 'block';
        setTimeout(() => t.style.display = 'none', 3200);
    }
    function bhPost(action, data, cb) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', bh_nonce);
        for (const k in data) fd.append(k, data[k]);
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(cb)
        .catch(() => bhToast('Request failed.', 'error'));
    }
    function bhStatusBadge(s) {
        return `<span class="bh-status-badge bh-badge-${s}">${s.replace(/_/g,' ')}</span>`;
    }
    </script>
    <?php
    $content = ob_get_clean();
    return bntm_universal_container('Boarding House Management', $content);
}

// ============================================================
// TAB: OVERVIEW
// ============================================================

function bh_overview_tab() {
    global $wpdb;
    $t  = $wpdb->prefix . 'bh_tenants';
    $r  = $wpdb->prefix . 'bh_rooms';
    $i  = $wpdb->prefix . 'bh_invoices';
    $p  = $wpdb->prefix . 'bh_payments';
    $mr = $wpdb->prefix . 'bh_maintenance_requests';

    $total_tenants    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='active'");
    $occupied_rooms   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$r} WHERE status='occupied'");
    $vacant_rooms     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$r} WHERE status='vacant'");
    $total_rooms      = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$r}");
    $month_start      = date('Y-m-01');
    $month_end        = date('Y-m-t');
    $monthly_revenue  = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$p} WHERE payment_date BETWEEN %s AND %s", $month_start, $month_end));
    $overdue          = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$i} WHERE invoice_status='overdue'");
    $open_requests    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$mr} WHERE workflow_status NOT IN ('resolved','verified','closed')");
    $occupancy_rate   = $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100) : 0;

    $recent_payments = $wpdb->get_results("
        SELECT p.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name, p.amount
        FROM {$p} p
        LEFT JOIN {$t} te ON p.tenant_id = te.id
        ORDER BY p.created_at DESC LIMIT 5
    ");

    $recent_requests = $wpdb->get_results("
        SELECT mr.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name
        FROM {$mr} mr
        LEFT JOIN {$t} te ON mr.tenant_id = te.id
        ORDER BY mr.created_at DESC LIMIT 5
    ");

    ob_start();
    ?>
    <div class="bntm-stats-row">
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,var(--bntm-primary,#6366f1),var(--bntm-primary-hover,#4f46e5));">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
            </div>
            <div class="stat-content">
                <h3>Active Tenants</h3>
                <p class="stat-number"><?php echo number_format($total_tenants); ?></p>
                <span class="stat-label">currently renting</span>
            </div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div class="stat-content">
                <h3>Occupied Rooms</h3>
                <p class="stat-number"><?php echo number_format($occupied_rooms); ?></p>
                <span class="stat-label"><?php echo $occupancy_rate; ?>% occupancy rate</span>
            </div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            </div>
            <div class="stat-content">
                <h3>Vacant Rooms</h3>
                <p class="stat-number"><?php echo number_format($vacant_rooms); ?></p>
                <span class="stat-label">available to assign</span>
            </div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
            <div class="stat-content">
                <h3>Revenue This Month</h3>
                <p class="stat-number">&#8369;<?php echo number_format($monthly_revenue, 2); ?></p>
                <span class="stat-label"><?php echo date('F Y'); ?></span>
            </div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="stat-content">
                <h3>Overdue Accounts</h3>
                <p class="stat-number"><?php echo number_format($overdue); ?></p>
                <span class="stat-label">unpaid past due</span>
            </div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <div class="stat-content">
                <h3>Open Requests</h3>
                <p class="stat-number"><?php echo number_format($open_requests); ?></p>
                <span class="stat-label">maintenance tickets</span>
            </div>
        </div>
    </div>

    <div class="bntm-form-section" style="margin-bottom:20px;">
        <h3>Occupancy Rate</h3>
        <div style="background:#f3f4f6;border-radius:8px;height:14px;overflow:hidden;margin-top:10px;">
            <div style="height:100%;width:<?php echo $occupancy_rate; ?>%;background:linear-gradient(90deg,var(--bntm-primary,#6366f1),var(--bntm-primary-hover,#4f46e5));border-radius:8px;transition:width .6s ease;"></div>
        </div>
        <p style="margin:6px 0 0;font-size:13px;color:#6b7280;"><?php echo $occupied_rooms; ?> of <?php echo $total_rooms; ?> rooms occupied</p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;flex-wrap:wrap;">
        <div class="bntm-form-section">
            <h3>Recent Payments</h3>
            <?php if (empty($recent_payments)): ?>
                <p style="color:#9ca3af;font-size:13px;">No payments recorded yet.</p>
            <?php else: ?>
            <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead><tr><th>Tenant</th><th>Amount</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($recent_payments as $pay): ?>
                <tr>
                    <td><?php echo esc_html($pay->tenant_name); ?></td>
                    <td>&#8369;<?php echo number_format($pay->amount, 2); ?></td>
                    <td><?php echo date('M d', strtotime($pay->payment_date)); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="bntm-form-section">
            <h3>Recent Maintenance Requests</h3>
            <?php if (empty($recent_requests)): ?>
                <p style="color:#9ca3af;font-size:13px;">No requests submitted yet.</p>
            <?php else: ?>
            <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead><tr><th>Ticket</th><th>Tenant</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recent_requests as $req): ?>
                <tr>
                    <td><?php echo esc_html($req->ticket_number); ?></td>
                    <td><?php echo esc_html($req->tenant_name); ?></td>
                    <td><span class="bh-status-badge bh-badge-<?php echo esc_attr($req->workflow_status); ?>"><?php echo str_replace('_',' ', $req->workflow_status); ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="bntm-form-section">
        <h3>Frontend Pages</h3>
        <p style="color:#6b7280;margin-bottom:16px;">Pages for tenants. Share these links with your residents.</p>
        <div class="bntm-frontend-pages-grid">
            <?php
            $ext_pages = [
                ['slug'=>'tenant-portal','title'=>'Tenant Portal','desc'=>'Tenants view invoices, pay rent, and track maintenance requests.','audience'=>'Logged-in'],
                ['slug'=>'maintenance-request-form','title'=>'Maintenance Request Form','desc'=>'Standalone form for tenants to submit service requests.','audience'=>'Logged-in'],
            ];
            foreach ($ext_pages as $ep):
                $pg  = get_page_by_path($ep['slug']);
                $url = $pg ? get_permalink($pg->ID) : '#';
            ?>
            <div class="bntm-page-card">
                <div class="bntm-page-card-header">
                    <div class="bntm-page-card-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </div>
                    <span class="bntm-page-audience-badge bntm-badge-loggedin"><?php echo $ep['audience']; ?></span>
                </div>
                <div class="bntm-page-card-body">
                    <h4><?php echo esc_html($ep['title']); ?></h4>
                    <p><?php echo esc_html($ep['desc']); ?></p>
                </div>
                <div class="bntm-page-card-footer">
                    <a href="<?php echo esc_url($url); ?>" target="_blank" class="bntm-btn-primary bntm-btn-small">Open Page</a>
                    <button class="bntm-btn-secondary bntm-btn-small copy-page-url" data-url="<?php echo esc_url($url); ?>">Copy URL</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    (function(){
        document.querySelectorAll('.copy-page-url').forEach(btn => {
            btn.addEventListener('click', function(){
                navigator.clipboard.writeText(this.dataset.url).then(()=>{
                    bhToast('URL copied to clipboard!','success');
                });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: TENANTS
// ============================================================

function bh_tenants_tab() {
    global $wpdb;
    $t   = $wpdb->prefix . 'bh_tenants';
    $ra  = $wpdb->prefix . 'bh_room_assignments';
    $r   = $wpdb->prefix . 'bh_rooms';
    $nonce = wp_create_nonce('bh_nonce');

    $status_filter = isset($_GET['tenant_status']) ? sanitize_text_field($_GET['tenant_status']) : '';
    $where = "WHERE 1=1";
    if ($status_filter) $where .= $wpdb->prepare(" AND t.status = %s", $status_filter);

    $tenants = $wpdb->get_results("
        SELECT t.*, ra.room_id,
               CONCAT(r.room_number,' (',r.category,')') AS room_label
        FROM {$t} t
        LEFT JOIN {$ra} ra ON ra.tenant_id = t.id AND ra.is_current = 1
        LEFT JOIN {$r} r ON r.id = ra.room_id
        {$where}
        ORDER BY t.created_at DESC
    ");

    $vacant_rooms = $wpdb->get_results("SELECT id, room_number, category, monthly_rate FROM {$r} WHERE status='vacant' ORDER BY room_number ASC");

    ob_start();
    ?>
    <div class="bntm-form-section">
        <div class="bh-section-actions">
            <h3 style="margin:0;">Tenants</h3>
            <div style="display:flex;gap:10px;align-items:center;">
                <div class="bh-filter-row">
                    <select onchange="window.location='?tab=tenants&tenant_status='+this.value">
                        <option value="" <?php selected($status_filter,''); ?>>All Statuses</option>
                        <option value="active" <?php selected($status_filter,'active'); ?>>Active</option>
                        <option value="pending" <?php selected($status_filter,'pending'); ?>>Pending</option>
                        <option value="inactive" <?php selected($status_filter,'inactive'); ?>>Inactive</option>
                        <option value="moved-out" <?php selected($status_filter,'moved-out'); ?>>Moved Out</option>
                    </select>
                </div>
                <button class="bntm-btn-primary" onclick="bhShowAddTenantModal()">+ Add Tenant</button>
            </div>
        </div>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Room</th>
                    <th>Move-In</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($tenants)): ?>
                <tr><td colspan="8" style="text-align:center;color:#9ca3af;">No tenants found.</td></tr>
            <?php else: foreach ($tenants as $tn): ?>
                <tr>
                    <td><strong><?php echo esc_html($tn->first_name . ' ' . $tn->last_name); ?></strong></td>
                    <td><?php echo esc_html($tn->email); ?></td>
                    <td><?php echo esc_html($tn->phone); ?></td>
                    <td><?php echo esc_html($tn->room_label ?: '—'); ?></td>
                    <td><?php echo $tn->move_in_date ? date('M d, Y', strtotime($tn->move_in_date)) : '—'; ?></td>
                    <td style="color:<?php echo $tn->account_balance > 0 ? '#dc2626' : '#059669'; ?>;">
                        &#8369;<?php echo number_format($tn->account_balance, 2); ?>
                    </td>
                    <td><span class="bh-status-badge bh-badge-<?php echo esc_attr($tn->status); ?>"><?php echo esc_html($tn->status); ?></span></td>
                    <td>
                        <button class="bntm-btn-small bntm-btn-secondary" onclick="bhViewTenant(<?php echo $tn->id; ?>)">View</button>
                        <button class="bntm-btn-small bntm-btn-primary" onclick="bhEditTenant(<?php echo $tn->id; ?>)">Edit</button>
                        <?php if (!$tn->room_id && $tn->status === 'active'): ?>
                        <button class="bntm-btn-small" style="background:#f0fdf4;color:#15803d;border:1px solid #86efac;" onclick="bhShowAssignRoom(<?php echo $tn->id; ?>, '<?php echo esc_js($tn->first_name . ' ' . $tn->last_name); ?>')">Assign Room</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script>
    (function(){
        const nonce = '<?php echo $nonce; ?>';
        const vacantRooms = <?php echo json_encode($vacant_rooms); ?>;

        window.bhShowAddTenantModal = function() {
            bhOpenModal('Add New Tenant', `
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>First Name *</label><input id="bh-fn" type="text" placeholder="Juan"/></div>
                    <div class="bh-form-group"><label>Last Name *</label><input id="bh-ln" type="text" placeholder="Dela Cruz"/></div>
                </div>
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Email *</label><input id="bh-em" type="email" placeholder="juan@email.com"/></div>
                    <div class="bh-form-group"><label>Phone</label><input id="bh-ph" type="text" placeholder="09XX-XXX-XXXX"/></div>
                </div>
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Address</label><input id="bh-addr" type="text" placeholder="Street, City"/></div>
                </div>
                <div class="bh-form-row triple">
                    <div class="bh-form-group"><label>Birthdate</label><input id="bh-bd" type="date"/></div>
                    <div class="bh-form-group"><label>Gender</label>
                        <select id="bh-gd"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select>
                    </div>
                    <div class="bh-form-group"><label>Status</label>
                        <select id="bh-st"><option value="active">Active</option><option value="pending">Pending</option></select>
                    </div>
                </div>
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>ID Type</label><input id="bh-idt" type="text" placeholder="e.g. UMID, Driver's License"/></div>
                    <div class="bh-form-group"><label>ID Number</label><input id="bh-idn" type="text"/></div>
                </div>
                <div class="bh-form-row triple">
                    <div class="bh-form-group"><label>Emergency Contact</label><input id="bh-ecn" type="text"/></div>
                    <div class="bh-form-group"><label>Contact Phone</label><input id="bh-ecp" type="text"/></div>
                    <div class="bh-form-group"><label>Relation</label><input id="bh-ecr" type="text" placeholder="e.g. Parent"/></div>
                </div>
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Move-In Date</label><input id="bh-mi" type="date"/></div>
                    <div class="bh-form-group"><label>Deposit Amount</label><input id="bh-dep" type="number" step="0.01" value="0"/></div>
                </div>
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Lease Start</label><input id="bh-ls" type="date"/></div>
                    <div class="bh-form-group"><label>Lease End</label><input id="bh-le" type="date"/></div>
                </div>
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Notes</label><textarea id="bh-nt"></textarea></div>
                </div>
                <div class="bh-modal-footer">
                    <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                    <button class="bntm-btn-primary" id="bh-save-tenant-btn">Save Tenant</button>
                </div>
            `);
            document.getElementById('bh-save-tenant-btn').addEventListener('click', function(){
                const btn = this; btn.disabled=true; btn.textContent='Saving...';
                bhPost('bh_add_tenant', {
                    first_name: document.getElementById('bh-fn').value,
                    last_name: document.getElementById('bh-ln').value,
                    email: document.getElementById('bh-em').value,
                    phone: document.getElementById('bh-ph').value,
                    address: document.getElementById('bh-addr').value,
                    birthdate: document.getElementById('bh-bd').value,
                    gender: document.getElementById('bh-gd').value,
                    status: document.getElementById('bh-st').value,
                    id_type: document.getElementById('bh-idt').value,
                    id_number: document.getElementById('bh-idn').value,
                    emergency_contact_name: document.getElementById('bh-ecn').value,
                    emergency_contact_phone: document.getElementById('bh-ecp').value,
                    emergency_contact_relation: document.getElementById('bh-ecr').value,
                    move_in_date: document.getElementById('bh-mi').value,
                    deposit_amount: document.getElementById('bh-dep').value,
                    lease_start: document.getElementById('bh-ls').value,
                    lease_end: document.getElementById('bh-le').value,
                    notes: document.getElementById('bh-nt').value,
                }, json => {
                    if (json.success) { bhToast(json.data.message,'success'); bhCloseModal(); setTimeout(()=>location.reload(),1200); }
                    else { bhToast(json.data.message,'error'); btn.disabled=false; btn.textContent='Save Tenant'; }
                });
            });
        };

        window.bhViewTenant = function(id) {
            bhPost('bh_get_tenant', {tenant_id: id}, json => {
                if (!json.success) { bhToast(json.data.message,'error'); return; }
                const d = json.data.tenant;
                bhOpenModal('Tenant Profile — ' + d.first_name + ' ' + d.last_name, `
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;font-size:14px;">
                        <div><span style="color:#6b7280;">Full Name</span><p style="margin:2px 0;font-weight:600;">${d.first_name} ${d.last_name}</p></div>
                        <div><span style="color:#6b7280;">Status</span><p style="margin:2px 0;">${d.status}</p></div>
                        <div><span style="color:#6b7280;">Email</span><p style="margin:2px 0;">${d.email}</p></div>
                        <div><span style="color:#6b7280;">Phone</span><p style="margin:2px 0;">${d.phone || '—'}</p></div>
                        <div><span style="color:#6b7280;">ID</span><p style="margin:2px 0;">${d.id_type || '—'}: ${d.id_number || '—'}</p></div>
                        <div><span style="color:#6b7280;">Deposit</span><p style="margin:2px 0;">&#8369;${parseFloat(d.deposit_amount).toFixed(2)}</p></div>
                        <div><span style="color:#6b7280;">Move-In</span><p style="margin:2px 0;">${d.move_in_date || '—'}</p></div>
                        <div><span style="color:#6b7280;">Lease</span><p style="margin:2px 0;">${d.lease_start || '—'} to ${d.lease_end || '—'}</p></div>
                        <div><span style="color:#6b7280;">Emergency Contact</span><p style="margin:2px 0;">${d.emergency_contact_name || '—'} (${d.emergency_contact_relation || '—'})</p></div>
                        <div><span style="color:#6b7280;">Emergency Phone</span><p style="margin:2px 0;">${d.emergency_contact_phone || '—'}</p></div>
                        <div style="grid-column:1/-1;"><span style="color:#6b7280;">Notes</span><p style="margin:2px 0;">${d.notes || '—'}</p></div>
                    </div>
                    <div class="bh-modal-footer" style="padding:16px 0 0;">
                        <button class="bntm-btn-secondary bntm-btn-small" onclick="bhUpdateStatus(${d.id}, 'moved-out')">Mark Moved Out</button>
                        <button class="bntm-btn-danger bntm-btn-small" onclick="bhDeleteTenant(${d.id})">Delete</button>
                        <button class="bntm-btn-primary bntm-btn-small" onclick="bhEditTenant(${d.id})">Edit</button>
                    </div>
                `);
            });
        };

        window.bhEditTenant = function(id) {
            bhPost('bh_get_tenant', {tenant_id: id}, json => {
                if (!json.success) return;
                const d = json.data.tenant;
                bhOpenModal('Edit Tenant', `
                    <div class="bh-form-row">
                        <div class="bh-form-group"><label>First Name</label><input id="bh-efn" value="${d.first_name}"/></div>
                        <div class="bh-form-group"><label>Last Name</label><input id="bh-eln" value="${d.last_name}"/></div>
                    </div>
                    <div class="bh-form-row">
                        <div class="bh-form-group"><label>Email</label><input id="bh-eem" type="email" value="${d.email}"/></div>
                        <div class="bh-form-group"><label>Phone</label><input id="bh-eph" value="${d.phone || ''}"/></div>
                    </div>
                    <div class="bh-form-row single">
                        <div class="bh-form-group"><label>Address</label><input id="bh-eaddr" value="${d.address || ''}"/></div>
                    </div>
                    <div class="bh-form-row">
                        <div class="bh-form-group"><label>Status</label>
                            <select id="bh-est">
                                <option value="active" ${d.status==='active'?'selected':''}>Active</option>
                                <option value="pending" ${d.status==='pending'?'selected':''}>Pending</option>
                                <option value="inactive" ${d.status==='inactive'?'selected':''}>Inactive</option>
                                <option value="moved-out" ${d.status==='moved-out'?'selected':''}>Moved Out</option>
                            </select>
                        </div>
                        <div class="bh-form-group"><label>Deposit Amount</label><input id="bh-edep" type="number" step="0.01" value="${d.deposit_amount}"/></div>
                    </div>
                    <div class="bh-form-row">
                        <div class="bh-form-group"><label>Move-In Date</label><input id="bh-emi" type="date" value="${d.move_in_date || ''}"/></div>
                        <div class="bh-form-group"><label>Move-Out Date</label><input id="bh-emo" type="date" value="${d.move_out_date || ''}"/></div>
                    </div>
                    <div class="bh-form-row single">
                        <div class="bh-form-group"><label>Notes</label><textarea id="bh-ent">${d.notes || ''}</textarea></div>
                    </div>
                    <div class="bh-modal-footer">
                        <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                        <button class="bntm-btn-primary" id="bh-update-tenant-btn">Update Tenant</button>
                    </div>
                `);
                document.getElementById('bh-update-tenant-btn').addEventListener('click', function(){
                    const btn = this; btn.disabled=true; btn.textContent='Updating...';
                    bhPost('bh_edit_tenant', {
                        tenant_id: id,
                        first_name: document.getElementById('bh-efn').value,
                        last_name: document.getElementById('bh-eln').value,
                        email: document.getElementById('bh-eem').value,
                        phone: document.getElementById('bh-eph').value,
                        address: document.getElementById('bh-eaddr').value,
                        status: document.getElementById('bh-est').value,
                        deposit_amount: document.getElementById('bh-edep').value,
                        move_in_date: document.getElementById('bh-emi').value,
                        move_out_date: document.getElementById('bh-emo').value,
                        notes: document.getElementById('bh-ent').value,
                    }, json => {
                        if (json.success) { bhToast(json.data.message,'success'); bhCloseModal(); setTimeout(()=>location.reload(),1200); }
                        else { bhToast(json.data.message,'error'); btn.disabled=false; btn.textContent='Update Tenant'; }
                    });
                });
            });
        };

        window.bhUpdateStatus = function(id, newStatus) {
            if (!confirm('Mark this tenant as ' + newStatus + '?')) return;
            bhPost('bh_update_tenant_status', {tenant_id: id, new_status: newStatus}, json => {
                bhToast(json.data.message, json.success ? 'success' : 'error');
                if (json.success) { bhCloseModal(); setTimeout(()=>location.reload(),1200); }
            });
        };

        window.bhDeleteTenant = function(id) {
            if (!confirm('Permanently delete this tenant? This cannot be undone.')) return;
            bhPost('bh_delete_tenant', {tenant_id: id}, json => {
                bhToast(json.data.message, json.success ? 'success' : 'error');
                if (json.success) { bhCloseModal(); setTimeout(()=>location.reload(),1200); }
            });
        };

        window.bhShowAssignRoom = function(tenantId, tenantName) {
            const opts = vacantRooms.map(r => `<option value="${r.id}">Room ${r.room_number} (${r.category}) — &#8369;${parseFloat(r.monthly_rate).toFixed(2)}/mo</option>`).join('');
            bhOpenModal('Assign Room — ' + tenantName, `
                <div class="bh-form-group" style="margin-bottom:16px;">
                    <label>Select Vacant Room *</label>
                    <select id="bh-assign-room">${opts || '<option>No vacant rooms available</option>'}</select>
                </div>
                <div class="bh-form-group" style="margin-bottom:16px;">
                    <label>Assignment Date</label>
                    <input id="bh-assign-date" type="date" value="${new Date().toISOString().slice(0,10)}"/>
                </div>
                <div class="bh-modal-footer">
                    <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                    <button class="bntm-btn-primary" id="bh-do-assign-btn">Assign Room</button>
                </div>
            `);
            document.getElementById('bh-do-assign-btn').addEventListener('click', function(){
                const btn = this; btn.disabled=true; btn.textContent='Assigning...';
                bhPost('bh_assign_tenant', {
                    tenant_id: tenantId,
                    room_id: document.getElementById('bh-assign-room').value,
                    assigned_date: document.getElementById('bh-assign-date').value,
                }, json => {
                    if (json.success) { bhToast(json.data.message,'success'); bhCloseModal(); setTimeout(()=>location.reload(),1200); }
                    else { bhToast(json.data.message,'error'); btn.disabled=false; btn.textContent='Assign Room'; }
                });
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: ROOMS
// ============================================================

function bh_rooms_tab() {
    global $wpdb;
    $r  = $wpdb->prefix . 'bh_rooms';
    $t  = $wpdb->prefix . 'bh_tenants';
    $ra = $wpdb->prefix . 'bh_room_assignments';

    $rooms = $wpdb->get_results("
        SELECT r.*,
               COUNT(CASE WHEN ra.is_current=1 THEN 1 END) AS current_occupants,
               GROUP_CONCAT(CASE WHEN ra.is_current=1 THEN CONCAT(te.first_name,' ',te.last_name) END SEPARATOR ', ') AS tenant_names
        FROM {$r} r
        LEFT JOIN {$ra} ra ON ra.room_id = r.id
        LEFT JOIN {$t} te ON te.id = ra.tenant_id
        GROUP BY r.id
        ORDER BY r.floor ASC, r.room_number ASC
    ");
    $nonce = wp_create_nonce('bh_nonce');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <div class="bh-section-actions">
            <h3 style="margin:0;">Room Inventory</h3>
            <button class="bntm-btn-primary" onclick="bhShowAddRoomModal()">+ Add Room</button>
        </div>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead>
                <tr><th>Room #</th><th>Floor</th><th>Category</th><th>Capacity</th><th>Rate/Mo</th><th>Occupants</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($rooms)): ?>
                <tr><td colspan="8" style="text-align:center;color:#9ca3af;">No rooms yet.</td></tr>
            <?php else: foreach ($rooms as $rm): ?>
                <tr>
                    <td><strong><?php echo esc_html($rm->room_number); ?></strong></td>
                    <td><?php echo esc_html($rm->floor ?: '—'); ?></td>
                    <td><?php echo esc_html(ucfirst($rm->category)); ?></td>
                    <td><?php echo (int)$rm->current_occupants; ?> / <?php echo (int)$rm->capacity; ?></td>
                    <td>&#8369;<?php echo number_format($rm->monthly_rate, 2); ?></td>
                    <td style="font-size:13px;"><?php echo esc_html($rm->tenant_names ?: '—'); ?></td>
                    <td><span class="bh-status-badge bh-badge-<?php echo esc_attr($rm->status); ?>"><?php echo esc_html(ucfirst($rm->status)); ?></span></td>
                    <td>
                        <button class="bntm-btn-small bntm-btn-secondary" onclick="bhEditRoom(<?php echo $rm->id; ?>)">Edit</button>
                        <button class="bntm-btn-small" style="background:#f0fdf4;color:#15803d;border:1px solid #86efac;" onclick="bhViewRoomHistory(<?php echo $rm->id; ?>, '<?php echo esc_js($rm->room_number); ?>')">History</button>
                        <button class="bntm-btn-small bntm-btn-danger" onclick="bhDeleteRoom(<?php echo $rm->id; ?>)">Delete</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script>
    (function(){
        window.bhShowAddRoomModal = function() {
            bhOpenModal('Add New Room', `
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Room Number *</label><input id="bh-rn" placeholder="e.g. 101"/></div>
                    <div class="bh-form-group"><label>Floor</label><input id="bh-rf" placeholder="e.g. 1st Floor"/></div>
                </div>
                <div class="bh-form-row triple">
                    <div class="bh-form-group"><label>Category</label>
                        <select id="bh-rcat">
                            <option value="single">Single</option><option value="double">Double</option>
                            <option value="studio">Studio</option><option value="suite">Suite</option>
                        </select>
                    </div>
                    <div class="bh-form-group"><label>Capacity</label><input id="bh-rcap" type="number" value="1" min="1"/></div>
                    <div class="bh-form-group"><label>Monthly Rate (&#8369;)</label><input id="bh-rrate" type="number" step="0.01" value="0"/></div>
                </div>
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Description</label><textarea id="bh-rdesc"></textarea></div>
                </div>
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Amenities (comma-separated)</label><input id="bh-ramen" placeholder="e.g. WiFi, Aircon, Private Bath"/></div>
                </div>
                <div class="bh-modal-footer">
                    <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                    <button class="bntm-btn-primary" id="bh-save-room-btn">Save Room</button>
                </div>
            `);
            document.getElementById('bh-save-room-btn').addEventListener('click', function(){
                const btn=this; btn.disabled=true; btn.textContent='Saving...';
                bhPost('bh_add_room', {
                    room_number: document.getElementById('bh-rn').value,
                    floor: document.getElementById('bh-rf').value,
                    category: document.getElementById('bh-rcat').value,
                    capacity: document.getElementById('bh-rcap').value,
                    monthly_rate: document.getElementById('bh-rrate').value,
                    description: document.getElementById('bh-rdesc').value,
                    amenities: document.getElementById('bh-ramen').value,
                }, json => {
                    if (json.success) { bhToast(json.data.message,'success'); bhCloseModal(); setTimeout(()=>location.reload(),1200); }
                    else { bhToast(json.data.message,'error'); btn.disabled=false; btn.textContent='Save Room'; }
                });
            });
        };

        window.bhEditRoom = function(id) {
            bhPost('bh_get_tenant', {room_id: id, type:'room'}, json => {});
            // Inline fetch room for edit
            const fd = new FormData();
            fd.append('action','bh_edit_room'); fd.append('nonce',bh_nonce);
            fd.append('get_room_data','1'); fd.append('room_id',id);
            fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(json=>{
                if(!json.success){bhToast(json.data.message,'error');return;}
                const d=json.data.room;
                bhOpenModal('Edit Room — ' + d.room_number, `
                    <div class="bh-form-row">
                        <div class="bh-form-group"><label>Room Number</label><input id="bh-ern" value="${d.room_number}"/></div>
                        <div class="bh-form-group"><label>Floor</label><input id="bh-erf" value="${d.floor||''}"/></div>
                    </div>
                    <div class="bh-form-row triple">
                        <div class="bh-form-group"><label>Category</label>
                            <select id="bh-ercat">
                                <option value="single" ${d.category==='single'?'selected':''}>Single</option>
                                <option value="double" ${d.category==='double'?'selected':''}>Double</option>
                                <option value="studio" ${d.category==='studio'?'selected':''}>Studio</option>
                                <option value="suite" ${d.category==='suite'?'selected':''}>Suite</option>
                            </select>
                        </div>
                        <div class="bh-form-group"><label>Capacity</label><input id="bh-ercap" type="number" value="${d.capacity}"/></div>
                        <div class="bh-form-group"><label>Monthly Rate</label><input id="bh-errate" type="number" step="0.01" value="${d.monthly_rate}"/></div>
                    </div>
                    <div class="bh-form-row single">
                        <div class="bh-form-group"><label>Status</label>
                            <select id="bh-erst">
                                <option value="vacant" ${d.status==='vacant'?'selected':''}>Vacant</option>
                                <option value="occupied" ${d.status==='occupied'?'selected':''}>Occupied</option>
                                <option value="maintenance" ${d.status==='maintenance'?'selected':''}>Maintenance</option>
                                <option value="reserved" ${d.status==='reserved'?'selected':''}>Reserved</option>
                            </select>
                        </div>
                    </div>
                    <div class="bh-form-row single">
                        <div class="bh-form-group"><label>Description</label><textarea id="bh-erdesc">${d.description||''}</textarea></div>
                    </div>
                    <div class="bh-modal-footer">
                        <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                        <button class="bntm-btn-primary" id="bh-update-room-btn">Update Room</button>
                    </div>
                `);
                document.getElementById('bh-update-room-btn').addEventListener('click',function(){
                    const btn=this;btn.disabled=true;btn.textContent='Updating...';
                    bhPost('bh_edit_room',{
                        room_id:id,
                        room_number:document.getElementById('bh-ern').value,
                        floor:document.getElementById('bh-erf').value,
                        category:document.getElementById('bh-ercat').value,
                        capacity:document.getElementById('bh-ercap').value,
                        monthly_rate:document.getElementById('bh-errate').value,
                        status:document.getElementById('bh-erst').value,
                        description:document.getElementById('bh-erdesc').value,
                    },json=>{
                        if(json.success){bhToast(json.data.message,'success');bhCloseModal();setTimeout(()=>location.reload(),1200);}
                        else{bhToast(json.data.message,'error');btn.disabled=false;btn.textContent='Update Room';}
                    });
                });
            });
        };

        window.bhDeleteRoom = function(id) {
            if (!confirm('Delete this room? This will also remove assignments.')) return;
            bhPost('bh_delete_room',{room_id:id},json=>{
                bhToast(json.data.message,json.success?'success':'error');
                if(json.success) setTimeout(()=>location.reload(),1200);
            });
        };

        window.bhViewRoomHistory = function(id, rn) {
            bhPost('bh_get_room_history',{room_id:id},json=>{
                if(!json.success){bhToast(json.data.message,'error');return;}
                const rows = json.data.history.length
                    ? json.data.history.map(h=>`<tr><td>${h.tenant_name}</td><td>${h.assigned_date}</td><td>${h.vacated_date||'Present'}</td><td>${h.notes||'—'}</td></tr>`).join('')
                    : '<tr><td colspan="4" style="text-align:center;color:#9ca3af;">No history.</td></tr>';
                bhOpenModal('Occupancy History — Room ' + rn, `
                    <div class="bntm-table-wrapper"><table class="bntm-table">
                        <thead><tr><th>Tenant</th><th>Moved In</th><th>Moved Out</th><th>Notes</th></tr></thead>
                        <tbody>${rows}</tbody>
                    </table></div>
                `);
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: BILLING
// ============================================================

function bh_billing_tab() {
    global $wpdb;
    $i  = $wpdb->prefix . 'bh_invoices';
    $t  = $wpdb->prefix . 'bh_tenants';
    $r  = $wpdb->prefix . 'bh_rooms';

    $status_f = isset($_GET['inv_status']) ? sanitize_text_field($_GET['inv_status']) : '';
    $where    = "WHERE 1=1";
    if ($status_f) $where .= $wpdb->prepare(" AND inv.invoice_status = %s", $status_f);

    $invoices = $wpdb->get_results("
        SELECT inv.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name, r.room_number
        FROM {$i} inv
        LEFT JOIN {$t} te ON te.id = inv.tenant_id
        LEFT JOIN {$r} r ON r.id = inv.room_id
        {$where}
        ORDER BY inv.created_at DESC
    ");

    $active_tenants = $wpdb->get_results("
        SELECT te.id, CONCAT(te.first_name,' ',te.last_name) AS name, ra.room_id, r.room_number, r.monthly_rate
        FROM {$t} te
        LEFT JOIN {$wpdb->prefix}bh_room_assignments ra ON ra.tenant_id=te.id AND ra.is_current=1
        LEFT JOIN {$r} r ON r.id=ra.room_id
        WHERE te.status='active'
        ORDER BY te.first_name ASC
    ");

    ob_start();
    ?>
    <div class="bntm-form-section">
        <div class="bh-section-actions">
            <h3 style="margin:0;">Invoices</h3>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <div class="bh-filter-row">
                    <select onchange="window.location='?tab=billing&inv_status='+this.value">
                        <option value="" <?php selected($status_f,''); ?>>All</option>
                        <option value="unpaid" <?php selected($status_f,'unpaid'); ?>>Unpaid</option>
                        <option value="partial" <?php selected($status_f,'partial'); ?>>Partial</option>
                        <option value="paid" <?php selected($status_f,'paid'); ?>>Paid</option>
                        <option value="overdue" <?php selected($status_f,'overdue'); ?>>Overdue</option>
                    </select>
                </div>
                <button class="bntm-btn-secondary" onclick="bhShowBulkGenModal()">Bulk Generate</button>
                <button class="bntm-btn-primary" onclick="bhShowGenerateInvoiceModal()">+ New Invoice</button>
            </div>
        </div>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead><tr><th>Invoice ID</th><th>Tenant</th><th>Room</th><th>Period</th><th>Total</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="10" style="text-align:center;color:#9ca3af;">No invoices found.</td></tr>
            <?php else: foreach ($invoices as $inv): ?>
                <tr>
                    <td><strong>#<?php echo $inv->id; ?></strong></td>
                    <td><?php echo esc_html($inv->tenant_name); ?></td>
                    <td><?php echo esc_html($inv->room_number ?: '—'); ?></td>
                    <td style="font-size:12px;"><?php echo date('M d', strtotime($inv->billing_period_start)); ?> – <?php echo date('M d, Y', strtotime($inv->billing_period_end)); ?></td>
                    <td>&#8369;<?php echo number_format($inv->total_amount, 2); ?></td>
                    <td>&#8369;<?php echo number_format($inv->amount_paid, 2); ?></td>
                    <td style="color:<?php echo $inv->balance > 0 ? '#dc2626':'#059669'; ?>;">&#8369;<?php echo number_format($inv->balance, 2); ?></td>
                    <td style="font-size:12px;"><?php echo date('M d, Y', strtotime($inv->due_date)); ?></td>
                    <td><span class="bh-status-badge bh-badge-<?php echo esc_attr($inv->invoice_status); ?>"><?php echo esc_html($inv->invoice_status); ?></span></td>
                    <td>
                        <button class="bntm-btn-small bntm-btn-secondary" onclick="bhViewInvoice(<?php echo $inv->id; ?>)">View</button>
                        <?php if ($inv->invoice_status !== 'paid' && $inv->invoice_status !== 'cancelled'): ?>
                        <button class="bntm-btn-small bntm-btn-danger" onclick="bhDeleteInvoice(<?php echo $inv->id; ?>)">Cancel</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script>
    (function(){
        const tenants = <?php echo json_encode($active_tenants); ?>;

        window.bhShowGenerateInvoiceModal = function() {
            const tOpts = tenants.map(t=>`<option value="${t.id}" data-room="${t.room_id||''}" data-rate="${t.monthly_rate||0}">${t.name}${t.room_number?' — Room '+t.room_number:''}</option>`).join('');
            bhOpenModal('Generate Invoice', `
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Tenant *</label><select id="bh-inv-tenant" onchange="bhFillInvRate(this)">${tOpts}</select></div>
                </div>
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Period Start</label><input id="bh-inv-ps" type="date"/></div>
                    <div class="bh-form-group"><label>Period End</label><input id="bh-inv-pe" type="date"/></div>
                </div>
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Due Date</label><input id="bh-inv-dd" type="date"/></div>
                    <div class="bh-form-group"><label>Base Rent (&#8369;)</label><input id="bh-inv-rent" type="number" step="0.01" value="0"/></div>
                </div>
                <div class="bh-form-row triple">
                    <div class="bh-form-group"><label>Penalty (&#8369;)</label><input id="bh-inv-pen" type="number" step="0.01" value="0"/></div>
                    <div class="bh-form-group"><label>Discount (&#8369;)</label><input id="bh-inv-disc" type="number" step="0.01" value="0"/></div>
                    <div class="bh-form-group"><label>Deposit Applied (&#8369;)</label><input id="bh-inv-dep" type="number" step="0.01" value="0"/></div>
                </div>
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Notes</label><textarea id="bh-inv-notes"></textarea></div>
                </div>
                <div class="bh-modal-footer">
                    <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                    <button class="bntm-btn-primary" id="bh-gen-inv-btn">Generate Invoice</button>
                </div>
            `);
            window.bhFillInvRate = function(sel){
                const opt = sel.selectedOptions[0];
                document.getElementById('bh-inv-rent').value = parseFloat(opt.dataset.rate||0).toFixed(2);
            };
            if (tenants.length) {
                document.getElementById('bh-inv-tenant').dispatchEvent(new Event('change'));
            }
            document.getElementById('bh-gen-inv-btn').addEventListener('click',function(){
                const btn=this;btn.disabled=true;btn.textContent='Generating...';
                const sel = document.getElementById('bh-inv-tenant');
                const roomId = sel.selectedOptions[0].dataset.room;
                bhPost('bh_generate_invoice',{
                    tenant_id: sel.value, room_id: roomId,
                    billing_period_start: document.getElementById('bh-inv-ps').value,
                    billing_period_end: document.getElementById('bh-inv-pe').value,
                    due_date: document.getElementById('bh-inv-dd').value,
                    base_rent: document.getElementById('bh-inv-rent').value,
                    penalty_amount: document.getElementById('bh-inv-pen').value,
                    discount_amount: document.getElementById('bh-inv-disc').value,
                    deposit_applied: document.getElementById('bh-inv-dep').value,
                    notes: document.getElementById('bh-inv-notes').value,
                },json=>{
                    if(json.success){bhToast(json.data.message,'success');bhCloseModal();setTimeout(()=>location.reload(),1200);}
                    else{bhToast(json.data.message,'error');btn.disabled=false;btn.textContent='Generate Invoice';}
                });
            });
        };

        window.bhShowBulkGenModal = function() {
            bhOpenModal('Bulk Generate Invoices', `
                <p style="color:#374151;font-size:14px;">This will generate invoices for all active tenants with a room assignment for the specified billing period.</p>
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Period Start *</label><input id="bh-bulk-ps" type="date"/></div>
                    <div class="bh-form-group"><label>Period End *</label><input id="bh-bulk-pe" type="date"/></div>
                </div>
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Due Date *</label><input id="bh-bulk-dd" type="date"/></div>
                </div>
                <div class="bh-modal-footer">
                    <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                    <button class="bntm-btn-primary" id="bh-bulk-gen-btn">Generate All</button>
                </div>
            `);
            document.getElementById('bh-bulk-gen-btn').addEventListener('click',function(){
                const btn=this;btn.disabled=true;btn.textContent='Generating...';
                bhPost('bh_bulk_generate_invoices',{
                    billing_period_start: document.getElementById('bh-bulk-ps').value,
                    billing_period_end: document.getElementById('bh-bulk-pe').value,
                    due_date: document.getElementById('bh-bulk-dd').value,
                },json=>{
                    bhToast(json.data.message,json.success?'success':'error');
                    if(json.success){bhCloseModal();setTimeout(()=>location.reload(),1500);}
                    else{btn.disabled=false;btn.textContent='Generate All';}
                });
            });
        };

        window.bhViewInvoice = function(id) {
            bhPost('bh_get_invoice',{invoice_id:id},json=>{
                if(!json.success){bhToast(json.data.message,'error');return;}
                const d=json.data.invoice;
                bhOpenModal('Invoice #'+id, `
                    <div style="font-size:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;">
                        <div><span style="color:#6b7280;">Tenant</span><p style="margin:2px 0;font-weight:600;">${d.tenant_name}</p></div>
                        <div><span style="color:#6b7280;">Room</span><p style="margin:2px 0;">${d.room_number||'—'}</p></div>
                        <div><span style="color:#6b7280;">Period</span><p style="margin:2px 0;">${d.billing_period_start} to ${d.billing_period_end}</p></div>
                        <div><span style="color:#6b7280;">Due Date</span><p style="margin:2px 0;">${d.due_date}</p></div>
                        <div><span style="color:#6b7280;">Base Rent</span><p style="margin:2px 0;">&#8369;${parseFloat(d.base_rent).toFixed(2)}</p></div>
                        <div><span style="color:#6b7280;">Penalty</span><p style="margin:2px 0;">&#8369;${parseFloat(d.penalty_amount).toFixed(2)}</p></div>
                        <div><span style="color:#6b7280;">Discount</span><p style="margin:2px 0;">&#8369;${parseFloat(d.discount_amount).toFixed(2)}</p></div>
                        <div><span style="color:#6b7280;">Total</span><p style="margin:2px 0;font-weight:700;font-size:16px;">&#8369;${parseFloat(d.total_amount).toFixed(2)}</p></div>
                        <div><span style="color:#6b7280;">Amount Paid</span><p style="margin:2px 0;">&#8369;${parseFloat(d.amount_paid).toFixed(2)}</p></div>
                        <div><span style="color:#6b7280;">Balance</span><p style="margin:2px 0;color:#dc2626;font-weight:600;">&#8369;${parseFloat(d.balance).toFixed(2)}</p></div>
                        <div style="grid-column:1/-1;"><span style="color:#6b7280;">Status</span><p style="margin:2px 0;">${d.invoice_status}</p></div>
                        <div style="grid-column:1/-1;"><span style="color:#6b7280;">Notes</span><p style="margin:2px 0;">${d.notes||'—'}</p></div>
                    </div>
                `,'lg');
            });
        };

        window.bhDeleteInvoice = function(id) {
            if (!confirm('Cancel this invoice?')) return;
            bhPost('bh_delete_invoice',{invoice_id:id},json=>{
                bhToast(json.data.message,json.success?'success':'error');
                if(json.success) setTimeout(()=>location.reload(),1200);
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: PAYMENTS
// ============================================================

function bh_payments_tab() {
    global $wpdb;
    $p  = $wpdb->prefix . 'bh_payments';
    $i  = $wpdb->prefix . 'bh_invoices';
    $t  = $wpdb->prefix . 'bh_tenants';

    $payments = $wpdb->get_results("
        SELECT p.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name, p.receipt_number
        FROM {$p} p
        LEFT JOIN {$t} te ON te.id = p.tenant_id
        ORDER BY p.created_at DESC
        LIMIT 100
    ");

    $unpaid_invoices = $wpdb->get_results("
        SELECT inv.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name
        FROM {$i} inv
        LEFT JOIN {$t} te ON te.id = inv.tenant_id
        WHERE inv.invoice_status IN ('unpaid','partial','overdue')
        ORDER BY inv.due_date ASC
    ");

    ob_start();
    ?>
    <div class="bntm-form-section">
        <div class="bh-section-actions">
            <h3 style="margin:0;">Payment Records</h3>
            <button class="bntm-btn-primary" onclick="bhShowRecordPaymentModal()">+ Record Payment</button>
        </div>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead><tr><th>Receipt #</th><th>Tenant</th><th>Invoice #</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="8" style="text-align:center;color:#9ca3af;">No payments recorded.</td></tr>
            <?php else: foreach ($payments as $pay): ?>
                <tr>
                    <td><strong><?php echo esc_html($pay->receipt_number ?: '#'.$pay->id); ?></strong></td>
                    <td><?php echo esc_html($pay->tenant_name); ?></td>
                    <td>#<?php echo $pay->invoice_id; ?></td>
                    <td>&#8369;<?php echo number_format($pay->amount, 2); ?></td>
                    <td><?php echo esc_html(ucfirst($pay->payment_method)); ?></td>
                    <td><?php echo esc_html($pay->reference_number ?: '—'); ?></td>
                    <td><?php echo date('M d, Y', strtotime($pay->payment_date)); ?></td>
                    <td>
                        <button class="bntm-btn-small bntm-btn-secondary" onclick="bhViewReceipt(<?php echo $pay->id; ?>)">Receipt</button>
                        <button class="bntm-btn-small bntm-btn-danger" onclick="bhDeletePayment(<?php echo $pay->id; ?>)">Void</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <?php if (bntm_is_module_enabled('fn') && bntm_is_module_visible('fn')): ?>
    <?php echo bh_finance_export_section(); ?>
    <?php endif; ?>

    <script>
    (function(){
        const unpaidInvoices = <?php echo json_encode($unpaid_invoices); ?>;

        window.bhShowRecordPaymentModal = function() {
            const iOpts = unpaidInvoices.map(inv=>`<option value="${inv.id}" data-tenant="${inv.tenant_id}" data-balance="${inv.balance}">#${inv.id} — ${inv.tenant_name} — &#8369;${parseFloat(inv.balance).toFixed(2)} due</option>`).join('');
            bhOpenModal('Record Payment', `
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Invoice *</label>
                        <select id="bh-pay-inv" onchange="document.getElementById('bh-pay-amt').value=parseFloat(this.selectedOptions[0].dataset.balance||0).toFixed(2)">${iOpts||'<option>No unpaid invoices</option>'}</select>
                    </div>
                </div>
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Amount (&#8369;) *</label><input id="bh-pay-amt" type="number" step="0.01" value="0"/></div>
                    <div class="bh-form-group"><label>Payment Date *</label><input id="bh-pay-date" type="date" value="${new Date().toISOString().slice(0,10)}"/></div>
                </div>
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Payment Method</label>
                        <select id="bh-pay-method">
                            <option value="cash">Cash</option><option value="gcash">GCash</option>
                            <option value="bank">Bank Transfer</option><option value="online">Online</option>
                        </select>
                    </div>
                    <div class="bh-form-group"><label>Reference Number</label><input id="bh-pay-ref" placeholder="Optional"/></div>
                </div>
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Notes</label><textarea id="bh-pay-notes"></textarea></div>
                </div>
                <div class="bh-modal-footer">
                    <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                    <button class="bntm-btn-primary" id="bh-record-pay-btn">Record Payment</button>
                </div>
            `);
            if (unpaidInvoices.length) document.getElementById('bh-pay-inv').dispatchEvent(new Event('change'));
            document.getElementById('bh-record-pay-btn').addEventListener('click',function(){
                const btn=this;btn.disabled=true;btn.textContent='Recording...';
                const sel=document.getElementById('bh-pay-inv');
                bhPost('bh_record_payment',{
                    invoice_id: sel.value,
                    tenant_id: sel.selectedOptions[0].dataset.tenant,
                    amount: document.getElementById('bh-pay-amt').value,
                    payment_date: document.getElementById('bh-pay-date').value,
                    payment_method: document.getElementById('bh-pay-method').value,
                    reference_number: document.getElementById('bh-pay-ref').value,
                    notes: document.getElementById('bh-pay-notes').value,
                },json=>{
                    if(json.success){bhToast(json.data.message,'success');bhCloseModal();setTimeout(()=>location.reload(),1200);}
                    else{bhToast(json.data.message,'error');btn.disabled=false;btn.textContent='Record Payment';}
                });
            });
        };

        window.bhViewReceipt = function(id) {
            bhPost('bh_get_payment',{payment_id:id},json=>{
                if(!json.success){bhToast(json.data.message,'error');return;}
                const d=json.data.payment;
                bhOpenModal('Receipt — ' + (d.receipt_number||'#'+id),`
                    <div style="border:2px solid #e5e7eb;border-radius:10px;padding:24px;font-size:14px;">
                        <div style="text-align:center;margin-bottom:20px;">
                            <h2 style="margin:0;font-size:20px;color:#1e3a5f;">${bntm_get_setting ? '' : ''}OFFICIAL RECEIPT</h2>
                            <p style="color:#6b7280;margin:4px 0;">Receipt No: <strong>${d.receipt_number||'#'+id}</strong></p>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;">
                            <div><span style="color:#6b7280;">Tenant</span><p style="margin:2px 0;">${d.tenant_name}</p></div>
                            <div><span style="color:#6b7280;">Date</span><p style="margin:2px 0;">${d.payment_date}</p></div>
                            <div><span style="color:#6b7280;">Invoice #</span><p style="margin:2px 0;">${d.invoice_id}</p></div>
                            <div><span style="color:#6b7280;">Method</span><p style="margin:2px 0;">${d.payment_method}</p></div>
                            <div><span style="color:#6b7280;">Reference</span><p style="margin:2px 0;">${d.reference_number||'—'}</p></div>
                        </div>
                        <div style="margin-top:16px;padding-top:16px;border-top:2px dashed #e5e7eb;text-align:right;">
                            <span style="color:#6b7280;font-size:13px;">Amount Paid</span>
                            <p style="font-size:28px;font-weight:700;color:#059669;margin:4px 0;">&#8369;${parseFloat(d.amount).toFixed(2)}</p>
                        </div>
                        <p style="text-align:center;color:#9ca3af;font-size:12px;margin-top:16px;">Thank you for your payment.</p>
                    </div>
                    <div class="bh-modal-footer" style="padding:16px 0 0;justify-content:center;">
                        <button class="bntm-btn-secondary" onclick="window.print()">Print</button>
                    </div>
                `);
            });
        };

        window.bhDeletePayment = function(id) {
            if (!confirm('Void this payment? This will reverse the invoice balance.')) return;
            bhPost('bh_delete_payment',{payment_id:id},json=>{
                bhToast(json.data.message,json.success?'success':'error');
                if(json.success) setTimeout(()=>location.reload(),1200);
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: MAINTENANCE
// ============================================================

function bh_maintenance_tab() {
    global $wpdb;
    $mr = $wpdb->prefix . 'bh_maintenance_requests';
    $t  = $wpdb->prefix . 'bh_tenants';
    $r  = $wpdb->prefix . 'bh_rooms';

    $status_f = isset($_GET['maint_status']) ? sanitize_text_field($_GET['maint_status']) : '';
    $prio_f   = isset($_GET['maint_prio']) ? sanitize_text_field($_GET['maint_prio']) : '';
    $where = "WHERE 1=1";
    if ($status_f) $where .= $wpdb->prepare(" AND mr.workflow_status = %s", $status_f);
    if ($prio_f)   $where .= $wpdb->prepare(" AND mr.priority = %s", $prio_f);

    $requests = $wpdb->get_results("
        SELECT mr.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name, r.room_number
        FROM {$mr} mr
        LEFT JOIN {$t} te ON te.id = mr.tenant_id
        LEFT JOIN {$r} r ON r.id = mr.room_id
        {$where}
        ORDER BY FIELD(mr.priority,'emergency','high','medium','low'), mr.created_at DESC
    ");

    $staff = get_users(['role__in'=>['administrator','editor']]);

    ob_start();
    ?>
    <div class="bntm-form-section">
        <div class="bh-section-actions">
            <h3 style="margin:0;">Maintenance Requests</h3>
            <div class="bh-filter-row">
                <select onchange="window.location='?tab=maintenance&maint_status='+this.value+'&maint_prio=<?php echo esc_js($prio_f); ?>'">
                    <option value="" <?php selected($status_f,''); ?>>All Statuses</option>
                    <?php foreach(['submitted','under_review','assigned','in_progress','pending_approval','pending_materials','resolved','verified','closed'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php selected($status_f,$s); ?>><?php echo str_replace('_',' ',ucfirst($s)); ?></option>
                    <?php endforeach; ?>
                </select>
                <select onchange="window.location='?tab=maintenance&maint_status=<?php echo esc_js($status_f); ?>&maint_prio='+this.value">
                    <option value="" <?php selected($prio_f,''); ?>>All Priorities</option>
                    <option value="emergency" <?php selected($prio_f,'emergency'); ?>>Emergency</option>
                    <option value="high" <?php selected($prio_f,'high'); ?>>High</option>
                    <option value="medium" <?php selected($prio_f,'medium'); ?>>Medium</option>
                    <option value="low" <?php selected($prio_f,'low'); ?>>Low</option>
                </select>
            </div>
        </div>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead><tr><th>Ticket</th><th>Tenant</th><th>Room</th><th>Category</th><th>Priority</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="8" style="text-align:center;color:#9ca3af;">No requests found.</td></tr>
            <?php else: foreach ($requests as $req): ?>
                <tr>
                    <td><strong><?php echo esc_html($req->ticket_number); ?></strong></td>
                    <td><?php echo esc_html($req->tenant_name); ?></td>
                    <td><?php echo esc_html($req->room_number ?: '—'); ?></td>
                    <td><?php echo esc_html(ucfirst($req->category)); ?></td>
                    <td><span class="bh-status-badge bh-badge-<?php echo esc_attr($req->priority); ?>"><?php echo esc_html(ucfirst($req->priority)); ?></span></td>
                    <td><span class="bh-status-badge bh-badge-<?php echo esc_attr($req->workflow_status); ?>"><?php echo str_replace('_',' ', $req->workflow_status); ?></span></td>
                    <td style="font-size:12px;"><?php echo date('M d, Y', strtotime($req->created_at)); ?></td>
                    <td>
                        <button class="bntm-btn-small bntm-btn-secondary" onclick="bhViewRequest(<?php echo $req->id; ?>)">View</button>
                        <button class="bntm-btn-small bntm-btn-primary" onclick="bhUpdateRequestStatus(<?php echo $req->id; ?>)">Update</button>
                        <button class="bntm-btn-small bntm-btn-danger" onclick="bhDeleteRequest(<?php echo $req->id; ?>)">Delete</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script>
    (function(){
        const staffList = <?php echo json_encode(array_map(fn($u)=>['id'=>$u->ID,'name'=>$u->display_name], $staff)); ?>;
        const statusFlow = ['submitted','under_review','assigned','in_progress','pending_approval','pending_materials','resolved','verified','closed'];

        window.bhViewRequest = function(id) {
            bhPost('bh_get_request',{request_id:id},json=>{
                if(!json.success){bhToast(json.data.message,'error');return;}
                const d=json.data.request;
                const hist=json.data.history.map(h=>`<tr><td>${h.old_status.replace(/_/g,' ')}</td><td>${h.new_status.replace(/_/g,' ')}</td><td>${h.changed_by_name||'System'}</td><td style="font-size:11px;">${h.created_at}</td><td>${h.note||'—'}</td></tr>`).join('');
                bhOpenModal('Request — '+d.ticket_number,`
                    <div style="font-size:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;margin-bottom:16px;">
                        <div><span style="color:#6b7280;">Tenant</span><p style="margin:2px 0;">${d.tenant_name}</p></div>
                        <div><span style="color:#6b7280;">Room</span><p style="margin:2px 0;">${d.room_number||'—'}</p></div>
                        <div><span style="color:#6b7280;">Category</span><p style="margin:2px 0;">${d.category}</p></div>
                        <div><span style="color:#6b7280;">Priority</span><p style="margin:2px 0;">${d.priority}</p></div>
                        <div style="grid-column:1/-1;"><span style="color:#6b7280;">Description</span><p style="margin:2px 0;">${d.description}</p></div>
                        <div style="grid-column:1/-1;"><span style="color:#6b7280;">Resolution Notes</span><p style="margin:2px 0;">${d.resolution_notes||'—'}</p></div>
                    </div>
                    <h4 style="font-size:13px;color:#374151;margin:0 0 8px;">Status History</h4>
                    <div class="bntm-table-wrapper"><table class="bntm-table">
                        <thead><tr><th>From</th><th>To</th><th>By</th><th>When</th><th>Note</th></tr></thead>
                        <tbody>${hist||'<tr><td colspan="5" style="text-align:center;color:#9ca3af;">No history.</td></tr>'}</tbody>
                    </table></div>
                `,'lg');
            });
        };

        window.bhUpdateRequestStatus = function(id) {
            bhPost('bh_get_request',{request_id:id},json=>{
                if(!json.success)return;
                const d=json.data.request;
                const sOpts=statusFlow.map(s=>`<option value="${s}" ${s===d.workflow_status?'selected':''}>${s.replace(/_/g,' ')}</option>`).join('');
                const aOpts=staffList.map(s=>`<option value="${s.id}" ${s.id==d.assigned_to?'selected':''}>${s.name}</option>`).join('');
                bhOpenModal('Update Status — '+d.ticket_number,`
                    <div class="bh-form-row single">
                        <div class="bh-form-group"><label>New Status</label><select id="bh-mreq-status">${sOpts}</select></div>
                    </div>
                    <div class="bh-form-row single">
                        <div class="bh-form-group"><label>Assign To</label><select id="bh-mreq-assign"><option value="">Unassigned</option>${aOpts}</select></div>
                    </div>
                    <div class="bh-form-row single">
                        <div class="bh-form-group"><label>Note / Resolution Notes</label><textarea id="bh-mreq-note"></textarea></div>
                    </div>
                    <div class="bh-modal-footer">
                        <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                        <button class="bntm-btn-primary" id="bh-mreq-update-btn">Update Request</button>
                    </div>
                `);
                document.getElementById('bh-mreq-update-btn').addEventListener('click',function(){
                    const btn=this;btn.disabled=true;btn.textContent='Updating...';
                    bhPost('bh_update_request_status',{
                        request_id:id,
                        workflow_status:document.getElementById('bh-mreq-status').value,
                        assigned_to:document.getElementById('bh-mreq-assign').value,
                        note:document.getElementById('bh-mreq-note').value,
                    },json=>{
                        if(json.success){bhToast(json.data.message,'success');bhCloseModal();setTimeout(()=>location.reload(),1200);}
                        else{bhToast(json.data.message,'error');btn.disabled=false;btn.textContent='Update Request';}
                    });
                });
            });
        };

        window.bhDeleteRequest = function(id) {
            if(!confirm('Delete this maintenance request?')) return;
            bhPost('bh_delete_request',{request_id:id},json=>{
                bhToast(json.data.message,json.success?'success':'error');
                if(json.success) setTimeout(()=>location.reload(),1200);
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: NOTIFICATIONS
// ============================================================

function bh_notifications_tab() {
    global $wpdb;
    $n = $wpdb->prefix . 'bh_notifications';
    $nt = $wpdb->prefix . 'bh_notification_templates';
    $t  = $wpdb->prefix . 'bh_tenants';

    $logs = $wpdb->get_results("
        SELECT n.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name
        FROM {$n} n
        LEFT JOIN {$t} te ON te.id = n.tenant_id
        ORDER BY n.created_at DESC LIMIT 100
    ");
    $templates = $wpdb->get_results("SELECT * FROM {$nt} ORDER BY name ASC");
    $tenants   = $wpdb->get_results("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM {$t} WHERE status='active' ORDER BY first_name ASC");

    ob_start();
    ?>
    <div class="bntm-form-section">
        <div class="bh-section-actions">
            <h3 style="margin:0;">Notification Log</h3>
            <button class="bntm-btn-primary" onclick="bhShowSendNotifModal()">+ Send Notification</button>
        </div>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead><tr><th>Type</th><th>Recipient</th><th>Channel</th><th>Subject</th><th>Sent</th></tr></thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;">No notifications sent yet.</td></tr>
            <?php else: foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->type); ?></td>
                    <td><?php echo esc_html($log->tenant_name ?: '—'); ?></td>
                    <td><?php echo esc_html(ucfirst($log->channel)); ?></td>
                    <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html($log->subject); ?></td>
                    <td style="font-size:12px;"><?php echo $log->sent_at ? date('M d, Y H:i', strtotime($log->sent_at)) : date('M d, Y H:i', strtotime($log->created_at)); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="bntm-form-section">
        <h3>Notification Templates</h3>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead><tr><th>Name</th><th>Key</th><th>Channel</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($templates)): ?>
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;">No templates.</td></tr>
            <?php else: foreach ($templates as $tmpl): ?>
                <tr>
                    <td><?php echo esc_html($tmpl->name); ?></td>
                    <td><code style="font-size:12px;"><?php echo esc_html($tmpl->template_key); ?></code></td>
                    <td><?php echo esc_html(ucfirst($tmpl->channel)); ?></td>
                    <td><?php echo $tmpl->is_active ? '<span style="color:#059669;">Yes</span>' : '<span style="color:#9ca3af;">No</span>'; ?></td>
                    <td><button class="bntm-btn-small bntm-btn-secondary" onclick="bhEditTemplate(<?php echo $tmpl->id; ?>)">Edit</button></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script>
    (function(){
        const tenants = <?php echo json_encode($tenants); ?>;
        const templates = <?php echo json_encode($templates); ?>;

        window.bhShowSendNotifModal = function() {
            const tOpts = tenants.map(t=>`<option value="${t.id}">${t.name}</option>`).join('');
            bhOpenModal('Send Manual Notification',`
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Recipient (Tenant)</label><select id="bh-notif-tenant">${tOpts}</select></div>
                </div>
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Type</label><input id="bh-notif-type" placeholder="e.g. manual_reminder"/></div>
                    <div class="bh-form-group"><label>Channel</label>
                        <select id="bh-notif-channel"><option value="in_app">In-App</option><option value="email">Email</option></select>
                    </div>
                </div>
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Subject</label><input id="bh-notif-subj"/></div>
                </div>
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Message</label><textarea id="bh-notif-msg" style="min-height:100px;"></textarea></div>
                </div>
                <div class="bh-modal-footer">
                    <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                    <button class="bntm-btn-primary" id="bh-send-notif-btn">Send</button>
                </div>
            `);
            document.getElementById('bh-send-notif-btn').addEventListener('click',function(){
                const btn=this;btn.disabled=true;btn.textContent='Sending...';
                bhPost('bh_send_manual_notification',{
                    tenant_id:document.getElementById('bh-notif-tenant').value,
                    type:document.getElementById('bh-notif-type').value,
                    channel:document.getElementById('bh-notif-channel').value,
                    subject:document.getElementById('bh-notif-subj').value,
                    message:document.getElementById('bh-notif-msg').value,
                },json=>{
                    if(json.success){bhToast(json.data.message,'success');bhCloseModal();setTimeout(()=>location.reload(),1200);}
                    else{bhToast(json.data.message,'error');btn.disabled=false;btn.textContent='Send';}
                });
            });
        };

        window.bhEditTemplate = function(id) {
            bhPost('bh_get_template',{template_id:id},json=>{
                if(!json.success){bhToast(json.data.message,'error');return;}
                const d=json.data.template;
                bhOpenModal('Edit Template — '+d.name,`
                    <div class="bh-form-row single">
                        <div class="bh-form-group"><label>Subject</label><input id="bh-tmpl-subj" value="${d.subject||''}"/></div>
                    </div>
                    <div class="bh-form-row single">
                        <div class="bh-form-group"><label>Body</label><textarea id="bh-tmpl-body" style="min-height:130px;">${d.body||''}</textarea></div>
                    </div>
                    <div class="bh-form-row single">
                        <div class="bh-form-group"><label>Active</label>
                            <select id="bh-tmpl-active"><option value="1" ${d.is_active?'selected':''}>Yes</option><option value="0" ${!d.is_active?'selected':''}>No</option></select>
                        </div>
                    </div>
                    <p style="font-size:12px;color:#9ca3af;">Available placeholders: {{tenant_name}} {{room_number}} {{invoice_amount}} {{due_date}} {{ticket_number}}</p>
                    <div class="bh-modal-footer">
                        <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                        <button class="bntm-btn-primary" id="bh-save-tmpl-btn">Save Template</button>
                    </div>
                `);
                document.getElementById('bh-save-tmpl-btn').addEventListener('click',function(){
                    const btn=this;btn.disabled=true;btn.textContent='Saving...';
                    bhPost('bh_save_template',{
                        template_id:id,
                        subject:document.getElementById('bh-tmpl-subj').value,
                        body:document.getElementById('bh-tmpl-body').value,
                        is_active:document.getElementById('bh-tmpl-active').value,
                    },json=>{
                        if(json.success){bhToast(json.data.message,'success');bhCloseModal();setTimeout(()=>location.reload(),1200);}
                        else{bhToast(json.data.message,'error');btn.disabled=false;btn.textContent='Save Template';}
                    });
                });
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: REPORTS
// ============================================================

function bh_reports_tab() {
    ob_start();
    ?>
    <div class="bntm-form-section">
        <div class="bh-section-actions">
            <h3 style="margin:0;">Analytics & Reports</h3>
            <div class="bh-filter-row">
                <input type="date" id="bh-rep-from" value="<?php echo date('Y-m-01'); ?>"/>
                <input type="date" id="bh-rep-to" value="<?php echo date('Y-m-t'); ?>"/>
                <button class="bntm-btn-primary" onclick="bhLoadAllReports()">Run Reports</button>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" id="bh-reports-grid">
            <div class="bntm-form-section" id="bh-report-occupancy"><p style="color:#9ca3af;text-align:center;">Click "Run Reports" to load.</p></div>
            <div class="bntm-form-section" id="bh-report-revenue"><p style="color:#9ca3af;text-align:center;"></p></div>
            <div class="bntm-form-section" id="bh-report-tenants"><p></p></div>
            <div class="bntm-form-section" id="bh-report-maintenance"><p></p></div>
        </div>
    </div>

    <style>
    .bh-report-stat { display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px; }
    .bh-report-stat:last-child{border-bottom:none;}
    .bh-report-stat .val{font-weight:600;color:#1e3a5f;}
    </style>

    <script>
    (function(){
        function bhLoadReport(action, divId, title, render) {
            const el = document.getElementById(divId);
            el.innerHTML = '<p style="color:#9ca3af;text-align:center;font-size:13px;">Loading...</p>';
            bhPost(action, {
                from: document.getElementById('bh-rep-from').value,
                to: document.getElementById('bh-rep-to').value,
            }, json => {
                if (!json.success) { el.innerHTML = '<p style="color:#dc2626;text-align:center;">Failed to load.</p>'; return; }
                el.innerHTML = '<h3 style="margin:0 0 12px;">'+title+'</h3>' + render(json.data);
            });
        }

        window.bhLoadAllReports = function() {
            bhLoadReport('bh_get_occupancy_report','bh-report-occupancy','Occupancy', d=>`
                <div class="bh-report-stat"><span>Total Rooms</span><span class="val">${d.total_rooms}</span></div>
                <div class="bh-report-stat"><span>Occupied</span><span class="val">${d.occupied}</span></div>
                <div class="bh-report-stat"><span>Vacant</span><span class="val">${d.vacant}</span></div>
                <div class="bh-report-stat"><span>Occupancy Rate</span><span class="val">${d.rate}%</span></div>
                <div class="bh-report-stat"><span>Single Rooms</span><span class="val">${d.by_category.single||0}</span></div>
                <div class="bh-report-stat"><span>Double Rooms</span><span class="val">${d.by_category.double||0}</span></div>
            `);
            bhLoadReport('bh_get_revenue_report','bh-report-revenue','Revenue', d=>`
                <div class="bh-report-stat"><span>Total Invoiced</span><span class="val">&#8369;${parseFloat(d.total_invoiced).toFixed(2)}</span></div>
                <div class="bh-report-stat"><span>Total Collected</span><span class="val">&#8369;${parseFloat(d.total_collected).toFixed(2)}</span></div>
                <div class="bh-report-stat"><span>Outstanding</span><span class="val" style="color:#dc2626;">&#8369;${parseFloat(d.outstanding).toFixed(2)}</span></div>
                <div class="bh-report-stat"><span>Collection Rate</span><span class="val">${d.collection_rate}%</span></div>
                <div class="bh-report-stat"><span>Overdue Invoices</span><span class="val">${d.overdue_count}</span></div>
            `);
            bhLoadReport('bh_get_tenant_report','bh-report-tenants','Tenants', d=>`
                <div class="bh-report-stat"><span>Active Tenants</span><span class="val">${d.active}</span></div>
                <div class="bh-report-stat"><span>Move-Ins (Period)</span><span class="val">${d.move_ins}</span></div>
                <div class="bh-report-stat"><span>Move-Outs (Period)</span><span class="val">${d.move_outs}</span></div>
                <div class="bh-report-stat"><span>Pending Tenants</span><span class="val">${d.pending}</span></div>
                <div class="bh-report-stat"><span>Total Registered</span><span class="val">${d.total}</span></div>
            `);
            bhLoadReport('bh_get_maintenance_report','bh-report-maintenance','Maintenance', d=>`
                <div class="bh-report-stat"><span>Total Requests</span><span class="val">${d.total}</span></div>
                <div class="bh-report-stat"><span>Open</span><span class="val">${d.open}</span></div>
                <div class="bh-report-stat"><span>Resolved (Period)</span><span class="val">${d.resolved}</span></div>
                <div class="bh-report-stat"><span>Emergency</span><span class="val" style="color:#dc2626;">${d.emergency}</span></div>
                <div class="bh-report-stat"><span>Avg Resolution Time</span><span class="val">${d.avg_days} days</span></div>
            `);
        };

        bhLoadAllReports();
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: AUDIT LOG
// ============================================================

function bh_audit_tab() {
    global $wpdb;
    $a = $wpdb->prefix . 'bh_audit_log';

    $logs = $wpdb->get_results("
        SELECT al.*, u.display_name AS user_name
        FROM {$a} al
        LEFT JOIN {$wpdb->users} u ON u.ID = al.user_id
        ORDER BY al.created_at DESC
        LIMIT 200
    ");

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Audit Trail</h3>
        <p style="color:#6b7280;font-size:13px;margin-bottom:16px;">Complete record of all system activity. Read-only.</p>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead><tr><th>User</th><th>Action</th><th>Entity</th><th>Entity ID</th><th>IP</th><th>Description</th><th>When</th></tr></thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="7" style="text-align:center;color:#9ca3af;">No audit records yet.</td></tr>
            <?php else: foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->user_name ?: 'System'); ?></td>
                    <td><span class="bh-status-badge" style="background:#f3f4f6;color:#374151;"><?php echo esc_html($log->action); ?></span></td>
                    <td><?php echo esc_html($log->entity_type); ?></td>
                    <td><?php echo $log->entity_id ?: '—'; ?></td>
                    <td style="font-size:12px;"><?php echo esc_html($log->ip_address ?: '—'); ?></td>
                    <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;"><?php echo esc_html($log->description); ?></td>
                    <td style="font-size:12px;"><?php echo date('M d, Y H:i', strtotime($log->created_at)); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: SETTINGS
// ============================================================

function bh_settings_tab() {
    $prop_name    = bntm_get_setting('bh_property_name', '');
    $prop_address = bntm_get_setting('bh_property_address', '');
    $owner_name   = bntm_get_setting('bh_owner_name', '');
    $owner_contact= bntm_get_setting('bh_owner_contact', '');
    $billing_day  = bntm_get_setting('bh_billing_day', '1');
    $grace_days   = bntm_get_setting('bh_grace_days', '5');
    $penalty_rate = bntm_get_setting('bh_penalty_rate', '2');
    $currency     = bntm_get_setting('bh_currency', 'PHP');
    $manual_methods = json_decode(bntm_get_setting('bh_payment_methods', '[]'), true);
    if (!is_array($manual_methods)) $manual_methods = [];

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Property Information</h3>
        <div class="bh-form-row">
            <div class="bh-form-group"><label>Property Name</label><input id="bh-set-pname" value="<?php echo esc_attr($prop_name); ?>"/></div>
            <div class="bh-form-group"><label>Owner Name</label><input id="bh-set-oname" value="<?php echo esc_attr($owner_name); ?>"/></div>
        </div>
        <div class="bh-form-row">
            <div class="bh-form-group"><label>Address</label><input id="bh-set-addr" value="<?php echo esc_attr($prop_address); ?>"/></div>
            <div class="bh-form-group"><label>Contact</label><input id="bh-set-ocontact" value="<?php echo esc_attr($owner_contact); ?>"/></div>
        </div>
    </div>

    <div class="bntm-form-section">
        <h3>Billing Configuration</h3>
        <div class="bh-form-row triple">
            <div class="bh-form-group"><label>Billing Day of Month</label><input id="bh-set-bday" type="number" min="1" max="28" value="<?php echo esc_attr($billing_day); ?>"/></div>
            <div class="bh-form-group"><label>Grace Period (Days)</label><input id="bh-set-grace" type="number" min="0" value="<?php echo esc_attr($grace_days); ?>"/></div>
            <div class="bh-form-group"><label>Penalty Rate (%)</label><input id="bh-set-penalty" type="number" step="0.01" value="<?php echo esc_attr($penalty_rate); ?>"/></div>
        </div>
        <div class="bh-form-row single">
            <div class="bh-form-group"><label>Currency</label>
                <select id="bh-set-currency">
                    <option value="PHP" <?php selected($currency,'PHP'); ?>>PHP — Philippine Peso</option>
                    <option value="USD" <?php selected($currency,'USD'); ?>>USD — US Dollar</option>
                </select>
            </div>
        </div>
        <button class="bntm-btn-primary" id="bh-save-settings-btn">Save Settings</button>
        <div id="bh-settings-msg"></div>
    </div>

    <div class="bntm-form-section">
        <h3>Payment Methods</h3>
        <?php if (empty($manual_methods)): ?>
            <p style="color:#9ca3af;font-size:13px;margin-bottom:12px;">No payment methods configured.</p>
        <?php else: ?>
        <div style="margin-bottom:16px;">
        <?php foreach ($manual_methods as $idx => $m): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#f9fafb;border-radius:8px;margin-bottom:8px;font-size:14px;">
                <div>
                    <strong><?php echo esc_html($m['name']); ?></strong>
                    <span style="color:#6b7280;margin-left:10px;"><?php echo esc_html($m['type']); ?></span>
                    <?php if (!empty($m['account_number'])): ?><span style="color:#6b7280;margin-left:10px;"><?php echo esc_html($m['account_number']); ?></span><?php endif; ?>
                </div>
                <button class="bntm-btn-small bntm-btn-danger" onclick="bhRemovePaymentMethod(<?php echo $idx; ?>)">Remove</button>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div style="background:#f9fafb;border-radius:8px;padding:18px;">
            <h4 style="margin:0 0 14px;font-size:14px;">Add Payment Method</h4>
            <div class="bh-form-row">
                <div class="bh-form-group"><label>Type</label>
                    <select id="bh-pm-type"><option value="cash">Cash</option><option value="gcash">GCash</option><option value="bank">Bank Transfer</option><option value="cod">COD</option></select>
                </div>
                <div class="bh-form-group"><label>Display Name *</label><input id="bh-pm-name" placeholder="e.g. BDO Bank Transfer"/></div>
            </div>
            <div class="bh-form-row">
                <div class="bh-form-group"><label>Account Name</label><input id="bh-pm-aname"/></div>
                <div class="bh-form-group"><label>Account Number</label><input id="bh-pm-anum"/></div>
            </div>
            <button class="bntm-btn-primary" id="bh-add-pm-btn">Add Method</button>
            <div id="bh-pm-msg"></div>
        </div>
    </div>

    <script>
    (function(){
        document.getElementById('bh-save-settings-btn').addEventListener('click',function(){
            const btn=this;btn.disabled=true;btn.textContent='Saving...';
            bhPost('bh_save_settings',{
                property_name: document.getElementById('bh-set-pname').value,
                owner_name: document.getElementById('bh-set-oname').value,
                property_address: document.getElementById('bh-set-addr').value,
                owner_contact: document.getElementById('bh-set-ocontact').value,
                billing_day: document.getElementById('bh-set-bday').value,
                grace_days: document.getElementById('bh-set-grace').value,
                penalty_rate: document.getElementById('bh-set-penalty').value,
                currency: document.getElementById('bh-set-currency').value,
            },json=>{
                document.getElementById('bh-settings-msg').innerHTML='<div class="bntm-notice bntm-notice-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
                btn.disabled=false;btn.textContent='Save Settings';
            });
        });

        document.getElementById('bh-add-pm-btn').addEventListener('click',function(){
            const btn=this;btn.disabled=true;btn.textContent='Adding...';
            bhPost('bh_add_payment_method',{
                payment_type: document.getElementById('bh-pm-type').value,
                payment_name: document.getElementById('bh-pm-name').value,
                account_name: document.getElementById('bh-pm-aname').value,
                account_number: document.getElementById('bh-pm-anum').value,
            },json=>{
                if(json.success){bhToast(json.data.message,'success');setTimeout(()=>location.reload(),1200);}
                else{bhToast(json.data.message,'error');}
                btn.disabled=false;btn.textContent='Add Method';
            });
        });

        window.bhRemovePaymentMethod = function(idx) {
            if(!confirm('Remove this payment method?')) return;
            bhPost('bh_remove_payment_method',{index:idx},json=>{
                bhToast(json.data.message,json.success?'success':'error');
                if(json.success) setTimeout(()=>location.reload(),1200);
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// FINANCE EXPORT SECTION (helper)
// ============================================================

function bh_finance_export_section() {
    global $wpdb;
    $p  = $wpdb->prefix . 'bh_payments';
    $t  = $wpdb->prefix . 'bh_tenants';
    $fn = $wpdb->prefix . 'fn_transactions';
    $nonce = wp_create_nonce('bh_fn_action');

    $payments = $wpdb->get_results("
        SELECT p.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name,
        (SELECT COUNT(*) FROM {$fn} WHERE reference_type='bh_payment' AND reference_id=p.id) AS is_exported
        FROM {$p} p
        LEFT JOIN {$t} te ON te.id = p.tenant_id
        WHERE p.status='completed'
        ORDER BY p.created_at DESC LIMIT 50
    ");

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Export Payments to Finance Module</h3>
        <div style="margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;">
            <label style="cursor:pointer;font-size:13px;"><input type="checkbox" id="bh-sel-notexp"> Select All (Not Exported)</label>
            <label style="cursor:pointer;font-size:13px;"><input type="checkbox" id="bh-sel-exp"> Select All (Exported)</label>
            <button class="bntm-btn-primary bntm-btn-small" id="bh-bulk-exp-btn" data-nonce="<?php echo $nonce; ?>">Export Selected</button>
            <button class="bntm-btn-secondary bntm-btn-small" id="bh-bulk-rev-btn" data-nonce="<?php echo $nonce; ?>">Revert Selected</button>
        </div>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead><tr><th width="40"></th><th>Receipt</th><th>Tenant</th><th>Amount</th><th>Date</th><th>Export Status</th></tr></thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="6" style="text-align:center;color:#9ca3af;">No payments.</td></tr>
            <?php else: foreach ($payments as $pay): ?>
            <tr>
                <td><input type="checkbox" class="bh-fn-chk <?php echo $pay->is_exported ? 'bh-fn-exp':'bh-fn-notexp'; ?>"
                    data-id="<?php echo $pay->id; ?>" data-amount="<?php echo $pay->amount; ?>" data-exported="<?php echo $pay->is_exported ? 1:0; ?>"/></td>
                <td><?php echo esc_html($pay->receipt_number ?: '#'.$pay->id); ?></td>
                <td><?php echo esc_html($pay->tenant_name); ?></td>
                <td>&#8369;<?php echo number_format($pay->amount,2); ?></td>
                <td><?php echo date('M d, Y', strtotime($pay->payment_date)); ?></td>
                <td><?php echo $pay->is_exported ? '<span style="color:#059669;">Exported</span>':'<span style="color:#9ca3af;">Not Exported</span>'; ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <script>
    (function(){
        const nonce='<?php echo $nonce; ?>';
        document.getElementById('bh-sel-notexp').addEventListener('change',function(){
            document.querySelectorAll('.bh-fn-notexp').forEach(c=>c.checked=this.checked);
        });
        document.getElementById('bh-sel-exp').addEventListener('change',function(){
            document.querySelectorAll('.bh-fn-exp').forEach(c=>c.checked=this.checked);
        });
        document.getElementById('bh-bulk-exp-btn').addEventListener('click',function(){
            const selected=Array.from(document.querySelectorAll('.bh-fn-chk:checked')).filter(c=>c.dataset.exported==='0');
            if(!selected.length){bhToast('Select at least one unexported payment.','error');return;}
            if(!confirm('Export '+selected.length+' payment(s) to Finance?')) return;
            this.disabled=true;this.textContent='Exporting...';
            let done=0;
            selected.forEach(c=>{
                bhPost('bh_fn_export_payment',{payment_id:c.dataset.id,amount:c.dataset.amount},json=>{
                    done++;if(done===selected.length){bhToast('Exported '+done+' payment(s).','success');setTimeout(()=>location.reload(),1500);}
                });
            });
        });
        document.getElementById('bh-bulk-rev-btn').addEventListener('click',function(){
            const selected=Array.from(document.querySelectorAll('.bh-fn-chk:checked')).filter(c=>c.dataset.exported==='1');
            if(!selected.length){bhToast('Select at least one exported payment.','error');return;}
            if(!confirm('Revert '+selected.length+' payment(s) from Finance?')) return;
            this.disabled=true;this.textContent='Reverting...';
            let done=0;
            selected.forEach(c=>{
                bhPost('bh_fn_revert_payment',{payment_id:c.dataset.id},json=>{
                    done++;if(done===selected.length){bhToast('Reverted.','success');setTimeout(()=>location.reload(),1500);}
                });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// AJAX HANDLERS — TENANTS
// ============================================================

function bntm_ajax_bh_add_tenant() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $table = $wpdb->prefix . 'bh_tenants';

    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name  = sanitize_text_field($_POST['last_name']);
    $email      = sanitize_email($_POST['email']);
    if (empty($first_name) || empty($last_name) || empty($email)) {
        wp_send_json_error(['message'=>'First name, last name, and email are required.']);
    }
    $data = [
        'rand_id'                    => bntm_rand_id(),
        'first_name'                 => $first_name,
        'last_name'                  => $last_name,
        'email'                      => $email,
        'phone'                      => sanitize_text_field($_POST['phone'] ?? ''),
        'address'                    => sanitize_text_field($_POST['address'] ?? ''),
        'birthdate'                  => sanitize_text_field($_POST['birthdate'] ?? '') ?: null,
        'gender'                     => sanitize_text_field($_POST['gender'] ?? ''),
        'id_type'                    => sanitize_text_field($_POST['id_type'] ?? ''),
        'id_number'                  => sanitize_text_field($_POST['id_number'] ?? ''),
        'emergency_contact_name'     => sanitize_text_field($_POST['emergency_contact_name'] ?? ''),
        'emergency_contact_phone'    => sanitize_text_field($_POST['emergency_contact_phone'] ?? ''),
        'emergency_contact_relation' => sanitize_text_field($_POST['emergency_contact_relation'] ?? ''),
        'move_in_date'               => sanitize_text_field($_POST['move_in_date'] ?? '') ?: null,
        'deposit_amount'             => floatval($_POST['deposit_amount'] ?? 0),
        'lease_start'                => sanitize_text_field($_POST['lease_start'] ?? '') ?: null,
        'lease_end'                  => sanitize_text_field($_POST['lease_end'] ?? '') ?: null,
        'notes'                      => sanitize_textarea_field($_POST['notes'] ?? ''),
        'status'                     => sanitize_text_field($_POST['status'] ?? 'active'),
    ];
    $result = $wpdb->insert($table, $data);
    if ($result) {
        bh_log_audit('add_tenant', 'tenant', $wpdb->insert_id, '', json_encode($data), 'Added tenant: '.$first_name.' '.$last_name);
        bh_send_notification_by_key('tenant_registered', $wpdb->insert_id, ['tenant_name'=>$first_name.' '.$last_name]);
        wp_send_json_success(['message'=>'Tenant added successfully!']);
    } else {
        wp_send_json_error(['message'=>'Failed to add tenant.']);
    }
}

function bntm_ajax_bh_edit_tenant() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $table     = $wpdb->prefix . 'bh_tenants';
    $tenant_id = intval($_POST['tenant_id']);
    $old       = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d",$tenant_id));
    if (!$old) { wp_send_json_error(['message'=>'Tenant not found.']); }
    $data = [
        'first_name'  => sanitize_text_field($_POST['first_name']),
        'last_name'   => sanitize_text_field($_POST['last_name']),
        'email'       => sanitize_email($_POST['email']),
        'phone'       => sanitize_text_field($_POST['phone'] ?? ''),
        'address'     => sanitize_text_field($_POST['address'] ?? ''),
        'status'      => sanitize_text_field($_POST['status'] ?? $old->status),
        'deposit_amount' => floatval($_POST['deposit_amount'] ?? $old->deposit_amount),
        'move_in_date'   => sanitize_text_field($_POST['move_in_date'] ?? '') ?: null,
        'move_out_date'  => sanitize_text_field($_POST['move_out_date'] ?? '') ?: null,
        'notes'          => sanitize_textarea_field($_POST['notes'] ?? ''),
    ];
    $result = $wpdb->update($table, $data, ['id'=>$tenant_id]);
    if ($result !== false) {
        if (isset($data['move_out_date']) && $data['move_out_date'] && !$old->move_out_date) {
            bh_vacate_room_for_tenant($tenant_id);
        }
        bh_log_audit('edit_tenant','tenant',$tenant_id,json_encode((array)$old),json_encode($data),'Edited tenant #'.$tenant_id);
        wp_send_json_success(['message'=>'Tenant updated.']);
    } else {
        wp_send_json_error(['message'=>'Update failed.']);
    }
}

function bntm_ajax_bh_get_tenant() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;

    // Support both tenant_id and room_id (for room edit prefill)
    if (!empty($_POST['room_id']) && !empty($_POST['get_room_data'])) {
        $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_rooms WHERE id=%d", intval($_POST['room_id'])));
        if (!$room) { wp_send_json_error(['message'=>'Room not found.']); }
        wp_send_json_success(['room'=>$room]);
    }

    $tenant_id = intval($_POST['tenant_id']);
    $tenant    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_tenants WHERE id=%d",$tenant_id));
    if (!$tenant) { wp_send_json_error(['message'=>'Tenant not found.']); }
    wp_send_json_success(['tenant'=>$tenant]);
}

function bntm_ajax_bh_update_tenant_status() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $table     = $wpdb->prefix . 'bh_tenants';
    $tenant_id = intval($_POST['tenant_id']);
    $new_status = sanitize_text_field($_POST['new_status']);
    $allowed    = ['active','inactive','pending','moved-out'];
    if (!in_array($new_status, $allowed)) { wp_send_json_error(['message'=>'Invalid status.']); }
    $result = $wpdb->update($table, ['status'=>$new_status], ['id'=>$tenant_id]);
    if ($new_status === 'moved-out') {
        $wpdb->update($table, ['move_out_date'=>current_time('Y-m-d')], ['id'=>$tenant_id]);
        bh_vacate_room_for_tenant($tenant_id);
    }
    if ($result !== false) {
        bh_log_audit('update_tenant_status','tenant',$tenant_id,'','status='.$new_status,'Tenant status updated to '.$new_status);
        wp_send_json_success(['message'=>'Status updated to '.$new_status.'.']);
    } else {
        wp_send_json_error(['message'=>'Update failed.']);
    }
}

function bntm_ajax_bh_delete_tenant() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $tenant_id = intval($_POST['tenant_id']);
    bh_vacate_room_for_tenant($tenant_id);
    $result = $wpdb->delete($wpdb->prefix.'bh_tenants', ['id'=>$tenant_id]);
    if ($result) {
        bh_log_audit('delete_tenant','tenant',$tenant_id,'','','Deleted tenant #'.$tenant_id);
        wp_send_json_success(['message'=>'Tenant deleted.']);
    } else {
        wp_send_json_error(['message'=>'Delete failed.']);
    }
}

// ============================================================
// AJAX HANDLERS — ROOMS
// ============================================================

function bntm_ajax_bh_add_room() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $table = $wpdb->prefix . 'bh_rooms';
    $room_number = sanitize_text_field($_POST['room_number']);
    if (empty($room_number)) { wp_send_json_error(['message'=>'Room number required.']); }
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE room_number=%s", $room_number));
    if ($exists) { wp_send_json_error(['message'=>'Room number already exists.']); }
    $result = $wpdb->insert($table, [
        'rand_id'      => bntm_rand_id(),
        'room_number'  => $room_number,
        'floor'        => sanitize_text_field($_POST['floor'] ?? ''),
        'category'     => sanitize_text_field($_POST['category'] ?? 'single'),
        'capacity'     => intval($_POST['capacity'] ?? 1),
        'monthly_rate' => floatval($_POST['monthly_rate'] ?? 0),
        'description'  => sanitize_textarea_field($_POST['description'] ?? ''),
        'amenities'    => sanitize_text_field($_POST['amenities'] ?? ''),
        'status'       => 'vacant',
    ]);
    if ($result) {
        bh_log_audit('add_room','room',$wpdb->insert_id,'','room_number='.$room_number,'Added room '.$room_number);
        wp_send_json_success(['message'=>'Room added successfully!']);
    } else {
        wp_send_json_error(['message'=>'Failed to add room.']);
    }
}

function bntm_ajax_bh_edit_room() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $table   = $wpdb->prefix . 'bh_rooms';
    $room_id = intval($_POST['room_id']);
    // Handle get_room_data flag
    if (!empty($_POST['get_room_data'])) {
        $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d",$room_id));
        if (!$room) { wp_send_json_error(['message'=>'Room not found.']); }
        wp_send_json_success(['room'=>$room]);
        return;
    }
    $old    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d",$room_id));
    $result = $wpdb->update($table,[
        'room_number'  => sanitize_text_field($_POST['room_number']),
        'floor'        => sanitize_text_field($_POST['floor'] ?? ''),
        'category'     => sanitize_text_field($_POST['category'] ?? 'single'),
        'capacity'     => intval($_POST['capacity'] ?? 1),
        'monthly_rate' => floatval($_POST['monthly_rate'] ?? 0),
        'status'       => sanitize_text_field($_POST['status'] ?? $old->status),
        'description'  => sanitize_textarea_field($_POST['description'] ?? ''),
    ],['id'=>$room_id]);
    if ($result !== false) {
        bh_log_audit('edit_room','room',$room_id,json_encode((array)$old),'','Updated room #'.$room_id);
        wp_send_json_success(['message'=>'Room updated.']);
    } else {
        wp_send_json_error(['message'=>'Update failed.']);
    }
}

function bntm_ajax_bh_delete_room() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $room_id = intval($_POST['room_id']);
    $wpdb->update($wpdb->prefix.'bh_room_assignments', ['is_current'=>0], ['room_id'=>$room_id]);
    $result = $wpdb->delete($wpdb->prefix.'bh_rooms', ['id'=>$room_id]);
    if ($result) {
        bh_log_audit('delete_room','room',$room_id,'','','Deleted room #'.$room_id);
        wp_send_json_success(['message'=>'Room deleted.']);
    } else {
        wp_send_json_error(['message'=>'Delete failed.']);
    }
}

function bntm_ajax_bh_assign_tenant() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $ra     = $wpdb->prefix . 'bh_room_assignments';
    $rooms  = $wpdb->prefix . 'bh_rooms';
    $tenant_id    = intval($_POST['tenant_id']);
    $room_id      = intval($_POST['room_id']);
    $assigned_date = sanitize_text_field($_POST['assigned_date']) ?: current_time('Y-m-d');

    $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$rooms} WHERE id=%d",$room_id));
    if (!$room) { wp_send_json_error(['message'=>'Room not found.']); }

    $current_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ra} WHERE room_id=%d AND is_current=1",$room_id));
    if ($current_count >= $room->capacity) { wp_send_json_error(['message'=>'Room is at full capacity.']); }

    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$ra} WHERE tenant_id=%d AND is_current=1",$tenant_id));
    if ($existing) { $wpdb->update($ra,['is_current'=>0],['id'=>$existing]); }

    $wpdb->query('START TRANSACTION');
    $ins = $wpdb->insert($ra,[
        'rand_id'       => bntm_rand_id(),
        'tenant_id'     => $tenant_id,
        'room_id'       => $room_id,
        'assigned_date' => $assigned_date,
        'assigned_by'   => get_current_user_id(),
        'is_current'    => 1,
        'status'        => 'active',
    ]);
    $upd = $wpdb->update($rooms, ['status'=>'occupied'], ['id'=>$room_id]);
    if ($ins && $upd !== false) {
        $wpdb->query('COMMIT');
        bh_log_audit('assign_room','room_assignment',$wpdb->insert_id,'','tenant='.$tenant_id.'/room='.$room_id,'Assigned tenant to room');
        bh_send_notification_by_key('room_assigned', $tenant_id, ['room_number'=>$room->room_number]);
        wp_send_json_success(['message'=>'Room assigned successfully!']);
    } else {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message'=>'Assignment failed.']);
    }
}

function bntm_ajax_bh_transfer_tenant() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $ra        = $wpdb->prefix . 'bh_room_assignments';
    $rooms     = $wpdb->prefix . 'bh_rooms';
    $tenant_id = intval($_POST['tenant_id']);
    $new_room_id = intval($_POST['new_room_id']);

    $current_assign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ra} WHERE tenant_id=%d AND is_current=1",$tenant_id));
    $wpdb->query('START TRANSACTION');
    if ($current_assign) {
        $wpdb->update($ra,['is_current'=>0,'vacated_date'=>current_time('Y-m-d')],['id'=>$current_assign->id]);
        $remaining = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ra} WHERE room_id=%d AND is_current=1",$current_assign->room_id));
        if ($remaining === 0) $wpdb->update($rooms,['status'=>'vacant'],['id'=>$current_assign->room_id]);
    }
    $ins = $wpdb->insert($ra,[
        'rand_id'=>bntm_rand_id(),'tenant_id'=>$tenant_id,'room_id'=>$new_room_id,
        'assigned_date'=>current_time('Y-m-d'),'assigned_by'=>get_current_user_id(),'is_current'=>1,'status'=>'active',
    ]);
    $wpdb->update($rooms,['status'=>'occupied'],['id'=>$new_room_id]);
    if ($ins) {
        $wpdb->query('COMMIT');
        wp_send_json_success(['message'=>'Tenant transferred successfully!']);
    } else {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message'=>'Transfer failed.']);
    }
}

function bntm_ajax_bh_get_room_history() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $ra = $wpdb->prefix . 'bh_room_assignments';
    $t  = $wpdb->prefix . 'bh_tenants';
    $room_id = intval($_POST['room_id']);
    $history = $wpdb->get_results($wpdb->prepare("
        SELECT ra.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name
        FROM {$ra} ra LEFT JOIN {$t} te ON te.id=ra.tenant_id
        WHERE ra.room_id=%d ORDER BY ra.created_at DESC
    ",$room_id));
    wp_send_json_success(['history'=>$history]);
}

function bntm_ajax_bh_toggle_room_status() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $room_id = intval($_POST['room_id']);
    $status  = sanitize_text_field($_POST['status']);
    $wpdb->update($wpdb->prefix.'bh_rooms',['status'=>$status],['id'=>$room_id]);
    wp_send_json_success(['message'=>'Room status updated.']);
}

// ============================================================
// AJAX HANDLERS — BILLING
// ============================================================

function bntm_ajax_bh_generate_invoice() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $table     = $wpdb->prefix . 'bh_invoices';
    $tenant_id = intval($_POST['tenant_id']);
    $room_id   = intval($_POST['room_id']);
    $base_rent = floatval($_POST['base_rent']);
    $penalty   = floatval($_POST['penalty_amount'] ?? 0);
    $discount  = floatval($_POST['discount_amount'] ?? 0);
    $deposit   = floatval($_POST['deposit_applied'] ?? 0);
    $period_start = sanitize_text_field($_POST['billing_period_start']);
    $period_end   = sanitize_text_field($_POST['billing_period_end']);
    $due_date     = sanitize_text_field($_POST['due_date']);
    if (!$tenant_id || !$period_start || !$due_date) { wp_send_json_error(['message'=>'Required fields missing.']); }
    $total   = $base_rent + $penalty - $discount - $deposit;
    $balance = $total;
    $result = $wpdb->insert($table,[
        'rand_id'              => bntm_rand_id(),
        'tenant_id'            => $tenant_id,
        'room_id'              => $room_id,
        'billing_period_start' => $period_start,
        'billing_period_end'   => $period_end,
        'due_date'             => $due_date,
        'base_rent'            => $base_rent,
        'penalty_amount'       => $penalty,
        'discount_amount'      => $discount,
        'deposit_applied'      => $deposit,
        'total_amount'         => $total,
        'amount_paid'          => 0,
        'balance'              => $balance,
        'invoice_status'       => 'unpaid',
        'notes'                => sanitize_textarea_field($_POST['notes'] ?? ''),
    ]);
    if ($result) {
        bh_send_notification_by_key('invoice_generated',$tenant_id,['invoice_amount'=>'₱'.number_format($total,2),'due_date'=>$due_date]);
        bh_log_audit('generate_invoice','invoice',$wpdb->insert_id,'','tenant='.$tenant_id.'/total='.$total,'Invoice generated');
        wp_send_json_success(['message'=>'Invoice generated successfully!']);
    } else {
        wp_send_json_error(['message'=>'Failed to generate invoice.']);
    }
}

function bntm_ajax_bh_bulk_generate_invoices() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $t  = $wpdb->prefix . 'bh_tenants';
    $r  = $wpdb->prefix . 'bh_rooms';
    $ra = $wpdb->prefix . 'bh_room_assignments';
    $ps  = sanitize_text_field($_POST['billing_period_start']);
    $pe  = sanitize_text_field($_POST['billing_period_end']);
    $dd  = sanitize_text_field($_POST['due_date']);
    if (!$ps || !$pe || !$dd) { wp_send_json_error(['message'=>'All date fields required.']); }
    $active = $wpdb->get_results("
        SELECT te.id AS tenant_id, ra.room_id, r.monthly_rate
        FROM {$t} te
        LEFT JOIN {$ra} ra ON ra.tenant_id=te.id AND ra.is_current=1
        LEFT JOIN {$r} r ON r.id=ra.room_id
        WHERE te.status='active' AND ra.room_id IS NOT NULL
    ");
    $count = 0;
    foreach ($active as $row) {
        $total = floatval($row->monthly_rate);
        $wpdb->insert($wpdb->prefix.'bh_invoices',[
            'rand_id'=>bntm_rand_id(),'tenant_id'=>$row->tenant_id,'room_id'=>$row->room_id,
            'billing_period_start'=>$ps,'billing_period_end'=>$pe,'due_date'=>$dd,
            'base_rent'=>$total,'total_amount'=>$total,'balance'=>$total,'invoice_status'=>'unpaid',
            'amount_paid'=>0,
        ]);
        $count++;
        bh_send_notification_by_key('invoice_generated',$row->tenant_id,['invoice_amount'=>'₱'.number_format($total,2),'due_date'=>$dd]);
    }
    wp_send_json_success(['message'=>"Generated {$count} invoice(s) successfully!"]);
}

function bntm_ajax_bh_add_charge() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $inv_id    = intval($_POST['invoice_id']);
    $type      = sanitize_text_field($_POST['charge_type']); // penalty | discount | other
    $amount    = floatval($_POST['amount']);
    $table     = $wpdb->prefix . 'bh_invoices';
    $inv       = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d",$inv_id));
    if (!$inv) { wp_send_json_error(['message'=>'Invoice not found.']); }
    $updates = [];
    if ($type === 'penalty')  $updates['penalty_amount']  = $inv->penalty_amount + $amount;
    if ($type === 'discount') $updates['discount_amount'] = $inv->discount_amount + $amount;
    $new_total   = $inv->base_rent + ($updates['penalty_amount'] ?? $inv->penalty_amount) - ($updates['discount_amount'] ?? $inv->discount_amount) - $inv->deposit_applied;
    $updates['total_amount'] = $new_total;
    $updates['balance']      = $new_total - $inv->amount_paid;
    $wpdb->update($table, $updates, ['id'=>$inv_id]);
    wp_send_json_success(['message'=>'Charge added.']);
}

function bntm_ajax_bh_get_invoice() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $inv_id = intval($_POST['invoice_id']);
    $inv = $wpdb->get_row($wpdb->prepare("
        SELECT inv.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name, r.room_number
        FROM {$wpdb->prefix}bh_invoices inv
        LEFT JOIN {$wpdb->prefix}bh_tenants te ON te.id=inv.tenant_id
        LEFT JOIN {$wpdb->prefix}bh_rooms r ON r.id=inv.room_id
        WHERE inv.id=%d
    ",$inv_id));
    if (!$inv) { wp_send_json_error(['message'=>'Invoice not found.']); }
    wp_send_json_success(['invoice'=>$inv]);
}

function bntm_ajax_bh_delete_invoice() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $inv_id = intval($_POST['invoice_id']);
    $result = $wpdb->update($wpdb->prefix.'bh_invoices',['invoice_status'=>'cancelled'],['id'=>$inv_id]);
    if ($result !== false) {
        bh_log_audit('cancel_invoice','invoice',$inv_id,'','','Cancelled invoice #'.$inv_id);
        wp_send_json_success(['message'=>'Invoice cancelled.']);
    } else {
        wp_send_json_error(['message'=>'Failed to cancel invoice.']);
    }
}

function bntm_ajax_bh_get_ledger() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $tenant_id = intval($_POST['tenant_id']);
    $invoices  = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_invoices WHERE tenant_id=%d ORDER BY created_at DESC",$tenant_id));
    $payments  = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_payments WHERE tenant_id=%d ORDER BY created_at DESC",$tenant_id));
    wp_send_json_success(['invoices'=>$invoices,'payments'=>$payments]);
}

// ============================================================
// AJAX HANDLERS — PAYMENTS
// ============================================================

function bntm_ajax_bh_record_payment() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $p_table   = $wpdb->prefix . 'bh_payments';
    $i_table   = $wpdb->prefix . 'bh_invoices';
    $inv_id    = intval($_POST['invoice_id']);
    $tenant_id = intval($_POST['tenant_id']);
    $amount    = floatval($_POST['amount']);
    $pdate     = sanitize_text_field($_POST['payment_date']);
    if (!$inv_id || $amount <= 0 || !$pdate) { wp_send_json_error(['message'=>'Required fields missing.']); }
    $inv = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$i_table} WHERE id=%d",$inv_id));
    if (!$inv) { wp_send_json_error(['message'=>'Invoice not found.']); }
    $receipt_num = 'RCP-' . strtoupper(substr(md5(time().$inv_id), 0, 8));
    $wpdb->query('START TRANSACTION');
    $ins = $wpdb->insert($p_table,[
        'rand_id'          => bntm_rand_id(),
        'invoice_id'       => $inv_id,
        'tenant_id'        => $tenant_id,
        'amount'           => $amount,
        'payment_method'   => sanitize_text_field($_POST['payment_method'] ?? 'cash'),
        'reference_number' => sanitize_text_field($_POST['reference_number'] ?? ''),
        'receipt_number'   => $receipt_num,
        'payment_date'     => $pdate,
        'recorded_by'      => get_current_user_id(),
        'notes'            => sanitize_textarea_field($_POST['notes'] ?? ''),
        'status'           => 'completed',
    ]);
    if ($ins) {
        $new_paid    = $inv->amount_paid + $amount;
        $new_balance = max(0, $inv->total_amount - $new_paid);
        $inv_status  = $new_balance <= 0 ? 'paid' : ($new_paid > 0 ? 'partial' : 'unpaid');
        $wpdb->update($i_table,['amount_paid'=>$new_paid,'balance'=>$new_balance,'invoice_status'=>$inv_status],['id'=>$inv_id]);
        // Update tenant balance
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}bh_tenants SET account_balance = account_balance - %f WHERE id=%d",$amount,$tenant_id));
        $wpdb->query('COMMIT');
        bh_send_notification_by_key('payment_confirmed',$tenant_id,['invoice_amount'=>'₱'.number_format($amount,2),'due_date'=>$pdate]);
        bh_log_audit('record_payment','payment',$wpdb->insert_id,'','amount='.$amount,'Payment recorded for invoice #'.$inv_id);
        wp_send_json_success(['message'=>'Payment recorded. Receipt: '.$receipt_num]);
    } else {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message'=>'Failed to record payment.']);
    }
}

function bntm_ajax_bh_get_payment() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $pay_id = intval($_POST['payment_id']);
    $pay = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name
        FROM {$wpdb->prefix}bh_payments p
        LEFT JOIN {$wpdb->prefix}bh_tenants te ON te.id=p.tenant_id
        WHERE p.id=%d
    ",$pay_id));
    if (!$pay) { wp_send_json_error(['message'=>'Payment not found.']); }
    wp_send_json_success(['payment'=>$pay]);
}

function bntm_ajax_bh_delete_payment() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $pay_id = intval($_POST['payment_id']);
    $pay    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_payments WHERE id=%d",$pay_id));
    if (!$pay) { wp_send_json_error(['message'=>'Payment not found.']); }
    $wpdb->query('START TRANSACTION');
    $del = $wpdb->delete($wpdb->prefix.'bh_payments',['id'=>$pay_id]);
    if ($del) {
        $inv = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_invoices WHERE id=%d",$pay->invoice_id));
        if ($inv) {
            $new_paid    = max(0, $inv->amount_paid - $pay->amount);
            $new_balance = $inv->total_amount - $new_paid;
            $inv_status  = $new_paid <= 0 ? 'unpaid' : 'partial';
            $wpdb->update($wpdb->prefix.'bh_invoices',['amount_paid'=>$new_paid,'balance'=>$new_balance,'invoice_status'=>$inv_status],['id'=>$inv->id]);
        }
        $wpdb->query('COMMIT');
        bh_log_audit('void_payment','payment',$pay_id,'','','Voided payment #'.$pay_id);
        wp_send_json_success(['message'=>'Payment voided.']);
    } else {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message'=>'Failed to void payment.']);
    }
}

function bntm_ajax_bh_get_outstanding_balances() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $balances = $wpdb->get_results("
        SELECT inv.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name
        FROM {$wpdb->prefix}bh_invoices inv
        LEFT JOIN {$wpdb->prefix}bh_tenants te ON te.id=inv.tenant_id
        WHERE inv.invoice_status IN ('unpaid','partial','overdue')
        ORDER BY inv.due_date ASC
    ");
    wp_send_json_success(['balances'=>$balances]);
}

// ============================================================
// AJAX HANDLERS — MAINTENANCE
// ============================================================

function bntm_ajax_bh_get_request() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $req_id = intval($_POST['request_id']);
    $req = $wpdb->get_row($wpdb->prepare("
        SELECT mr.*, CONCAT(te.first_name,' ',te.last_name) AS tenant_name, r.room_number
        FROM {$wpdb->prefix}bh_maintenance_requests mr
        LEFT JOIN {$wpdb->prefix}bh_tenants te ON te.id=mr.tenant_id
        LEFT JOIN {$wpdb->prefix}bh_rooms r ON r.id=mr.room_id
        WHERE mr.id=%d
    ",$req_id));
    if (!$req) { wp_send_json_error(['message'=>'Request not found.']); }
    $history = $wpdb->get_results($wpdb->prepare("
        SELECT mh.*, u.display_name AS changed_by_name
        FROM {$wpdb->prefix}bh_maintenance_history mh
        LEFT JOIN {$wpdb->users} u ON u.ID=mh.changed_by
        WHERE mh.request_id=%d ORDER BY mh.created_at ASC
    ",$req_id));
    wp_send_json_success(['request'=>$req,'history'=>$history]);
}

function bntm_ajax_bh_assign_request() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $req_id     = intval($_POST['request_id']);
    $assigned   = intval($_POST['assigned_to']);
    $result     = $wpdb->update($wpdb->prefix.'bh_maintenance_requests',['assigned_to'=>$assigned,'workflow_status'=>'assigned'],['id'=>$req_id]);
    bh_log_maintenance_history($req_id,'assigned');
    if ($result !== false) { wp_send_json_success(['message'=>'Assigned successfully.']); }
    else { wp_send_json_error(['message'=>'Assignment failed.']); }
}

function bntm_ajax_bh_update_request_status() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $req_id    = intval($_POST['request_id']);
    $new_stat  = sanitize_text_field($_POST['workflow_status']);
    $assigned  = intval($_POST['assigned_to'] ?? 0);
    $note      = sanitize_textarea_field($_POST['note'] ?? '');
    $allowed   = ['submitted','under_review','assigned','in_progress','pending_approval','pending_materials','resolved','verified','closed'];
    if (!in_array($new_stat, $allowed)) { wp_send_json_error(['message'=>'Invalid status.']); }
    $old = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_maintenance_requests WHERE id=%d",$req_id));
    $upd = ['workflow_status'=>$new_stat];
    if ($assigned) $upd['assigned_to'] = $assigned;
    if ($note && in_array($new_stat,['resolved','closed'])) $upd['resolution_notes'] = $note;
    if ($new_stat === 'resolved') $upd['resolved_at'] = current_time('mysql');
    if ($new_stat === 'verified') $upd['verified_at'] = current_time('mysql');
    if ($new_stat === 'closed')   $upd['closed_at']   = current_time('mysql');
    $wpdb->update($wpdb->prefix.'bh_maintenance_requests',$upd,['id'=>$req_id]);
    $wpdb->insert($wpdb->prefix.'bh_maintenance_history',[
        'rand_id'    => bntm_rand_id(),
        'request_id' => $req_id,
        'changed_by' => get_current_user_id(),
        'old_status' => $old->workflow_status,
        'new_status' => $new_stat,
        'note'       => $note,
    ]);
    bh_send_notification_by_key('maintenance_update',$old->tenant_id,['ticket_number'=>$old->ticket_number,'new_status'=>$new_stat]);
    bh_log_audit('update_maintenance_status','maintenance_request',$req_id,$old->workflow_status,$new_stat,'Status changed to '.$new_stat);
    wp_send_json_success(['message'=>'Status updated to '.str_replace('_',' ',$new_stat).'.']);
}

function bntm_ajax_bh_add_request_note() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $req_id = intval($_POST['request_id']);
    $note   = sanitize_textarea_field($_POST['note']);
    $old    = $wpdb->get_row($wpdb->prepare("SELECT workflow_status FROM {$wpdb->prefix}bh_maintenance_requests WHERE id=%d",$req_id));
    $wpdb->insert($wpdb->prefix.'bh_maintenance_history',[
        'rand_id'=>bntm_rand_id(),'request_id'=>$req_id,'changed_by'=>get_current_user_id(),
        'old_status'=>$old->workflow_status,'new_status'=>$old->workflow_status,'note'=>$note,
    ]);
    wp_send_json_success(['message'=>'Note added.']);
}

function bntm_ajax_bh_delete_request() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $req_id = intval($_POST['request_id']);
    $wpdb->delete($wpdb->prefix.'bh_maintenance_history',['request_id'=>$req_id]);
    $result = $wpdb->delete($wpdb->prefix.'bh_maintenance_requests',['id'=>$req_id]);
    if ($result) {
        bh_log_audit('delete_maintenance_request','maintenance_request',$req_id,'','','Deleted request #'.$req_id);
        wp_send_json_success(['message'=>'Request deleted.']);
    } else {
        wp_send_json_error(['message'=>'Delete failed.']);
    }
}

function bntm_ajax_bh_submit_maintenance() {
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Please log in to submit a request.']); }
    global $wpdb;
    $mr = $wpdb->prefix . 'bh_maintenance_requests';
    $t  = $wpdb->prefix . 'bh_tenants';
    $current_user = wp_get_current_user();
    $tenant = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE user_id=%d",$current_user->ID));
    if (!$tenant) { wp_send_json_error(['message'=>'No tenant profile linked to your account.']); }
    $ra = $wpdb->get_row($wpdb->prepare("SELECT room_id FROM {$wpdb->prefix}bh_room_assignments WHERE tenant_id=%d AND is_current=1",$tenant->id));
    $ticket = 'TKT-'.strtoupper(substr(md5(time().$tenant->id),0,6));
    $result = $wpdb->insert($mr,[
        'rand_id'        => bntm_rand_id(),
        'ticket_number'  => $ticket,
        'tenant_id'      => $tenant->id,
        'room_id'        => $ra ? $ra->room_id : null,
        'category'       => sanitize_text_field($_POST['category'] ?? 'other'),
        'priority'       => sanitize_text_field($_POST['priority'] ?? 'medium'),
        'description'    => sanitize_textarea_field($_POST['description']),
        'workflow_status'=> 'submitted',
    ]);
    if ($result) {
        bh_log_audit('submit_maintenance','maintenance_request',$wpdb->insert_id,'','ticket='.$ticket,'Tenant submitted request');
        wp_send_json_success(['message'=>'Request submitted! Ticket: '.$ticket,'ticket'=>$ticket]);
    } else {
        wp_send_json_error(['message'=>'Submission failed.']);
    }
}

// ============================================================
// AJAX HANDLERS — NOTIFICATIONS
// ============================================================

function bntm_ajax_bh_get_notification_log() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bh_notifications ORDER BY created_at DESC LIMIT 100");
    wp_send_json_success(['logs'=>$logs]);
}

function bntm_ajax_bh_save_template() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $tmpl_id  = intval($_POST['template_id']);
    $result   = $wpdb->update($wpdb->prefix.'bh_notification_templates',[
        'subject'   => sanitize_text_field($_POST['subject']),
        'body'      => sanitize_textarea_field($_POST['body']),
        'is_active' => intval($_POST['is_active']),
    ],['id'=>$tmpl_id]);
    if ($result !== false) { wp_send_json_success(['message'=>'Template saved.']); }
    else { wp_send_json_error(['message'=>'Save failed.']); }
}

function bntm_ajax_bh_get_template() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $tmpl = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_notification_templates WHERE id=%d",intval($_POST['template_id'])));
    if (!$tmpl) { wp_send_json_error(['message'=>'Template not found.']); }
    wp_send_json_success(['template'=>$tmpl]);
}

function bntm_ajax_bh_send_manual_notification() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $tenant_id = intval($_POST['tenant_id']);
    $type      = sanitize_text_field($_POST['type']);
    $channel   = sanitize_text_field($_POST['channel']);
    $subject   = sanitize_text_field($_POST['subject']);
    $message   = sanitize_textarea_field($_POST['message']);
    $result = $wpdb->insert($wpdb->prefix.'bh_notifications',[
        'rand_id'=>bntm_rand_id(),'tenant_id'=>$tenant_id,'type'=>$type,
        'channel'=>$channel,'subject'=>$subject,'message'=>$message,'sent_at'=>current_time('mysql'),
    ]);
    if ($result) {
        if ($channel === 'email') {
            $tenant = $wpdb->get_row($wpdb->prepare("SELECT email FROM {$wpdb->prefix}bh_tenants WHERE id=%d",$tenant_id));
            if ($tenant) wp_mail($tenant->email, $subject, $message);
        }
        wp_send_json_success(['message'=>'Notification sent.']);
    } else {
        wp_send_json_error(['message'=>'Failed to send.']);
    }
}

function bntm_ajax_bh_mark_notification_read() {
    global $wpdb;
    $notif_id = intval($_POST['notification_id'] ?? 0);
    if (!$notif_id) { wp_send_json_error(['message'=>'Invalid ID.']); }
    $wpdb->update($wpdb->prefix.'bh_notifications',['is_read'=>1],['id'=>$notif_id]);
    wp_send_json_success(['message'=>'Marked read.']);
}

// ============================================================
// AJAX HANDLERS — REPORTS
// ============================================================

function bntm_ajax_bh_get_occupancy_report() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $r = $wpdb->prefix . 'bh_rooms';
    $total    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$r}");
    $occupied = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$r} WHERE status='occupied'");
    $vacant   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$r} WHERE status='vacant'");
    $by_cat   = $wpdb->get_results("SELECT category, COUNT(*) as cnt FROM {$r} WHERE status='occupied' GROUP BY category");
    $cat_arr  = [];
    foreach ($by_cat as $row) $cat_arr[$row->category] = $row->cnt;
    wp_send_json_success([
        'total_rooms'=>$total,'occupied'=>$occupied,'vacant'=>$vacant,
        'rate'=>$total ? round(($occupied/$total)*100) : 0,
        'by_category'=>$cat_arr,
    ]);
}

function bntm_ajax_bh_get_revenue_report() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $from = sanitize_text_field($_POST['from']);
    $to   = sanitize_text_field($_POST['to']);
    $invoiced  = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total_amount),0) FROM {$wpdb->prefix}bh_invoices WHERE due_date BETWEEN %s AND %s",$from,$to));
    $collected = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}bh_payments WHERE payment_date BETWEEN %s AND %s",$from,$to));
    $outstanding = max(0,$invoiced - $collected);
    $overdue     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bh_invoices WHERE invoice_status='overdue'");
    wp_send_json_success([
        'total_invoiced'=>$invoiced,'total_collected'=>$collected,'outstanding'=>$outstanding,
        'collection_rate'=>$invoiced ? round(($collected/$invoiced)*100,1) : 0,
        'overdue_count'=>$overdue,
    ]);
}

function bntm_ajax_bh_get_tenant_report() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $from = sanitize_text_field($_POST['from']);
    $to   = sanitize_text_field($_POST['to']);
    $t    = $wpdb->prefix . 'bh_tenants';
    wp_send_json_success([
        'active'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='active'"),
        'pending'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='pending'"),
        'total'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t}"),
        'move_ins'  => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE move_in_date BETWEEN %s AND %s",$from,$to)),
        'move_outs' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE move_out_date BETWEEN %s AND %s",$from,$to)),
    ]);
}

function bntm_ajax_bh_get_maintenance_report() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $from = sanitize_text_field($_POST['from']);
    $to   = sanitize_text_field($_POST['to']);
    $mr   = $wpdb->prefix . 'bh_maintenance_requests';
    $total     = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$mr} WHERE created_at BETWEEN %s AND %s",$from.' 00:00:00',$to.' 23:59:59'));
    $open      = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$mr} WHERE workflow_status NOT IN ('resolved','verified','closed')");
    $resolved  = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$mr} WHERE resolved_at BETWEEN %s AND %s",$from.' 00:00:00',$to.' 23:59:59'));
    $emergency = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$mr} WHERE priority='emergency' AND workflow_status NOT IN ('closed')");
    $avg_days  = $wpdb->get_var("SELECT AVG(DATEDIFF(resolved_at,created_at)) FROM {$mr} WHERE resolved_at IS NOT NULL");
    wp_send_json_success(['total'=>$total,'open'=>$open,'resolved'=>$resolved,'emergency'=>$emergency,'avg_days'=>$avg_days ? round($avg_days,1) : 0]);
}

// ============================================================
// AJAX HANDLERS — AUDIT
// ============================================================

function bntm_ajax_bh_get_audit_log() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $logs = $wpdb->get_results("SELECT al.*, u.display_name AS user_name FROM {$wpdb->prefix}bh_audit_log al LEFT JOIN {$wpdb->users} u ON u.ID=al.user_id ORDER BY al.created_at DESC LIMIT 200");
    wp_send_json_success(['logs'=>$logs]);
}

// ============================================================
// AJAX HANDLERS — SETTINGS
// ============================================================

function bntm_ajax_bh_save_settings() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    bntm_set_setting('bh_property_name',    sanitize_text_field($_POST['property_name']));
    bntm_set_setting('bh_owner_name',       sanitize_text_field($_POST['owner_name']));
    bntm_set_setting('bh_property_address', sanitize_text_field($_POST['property_address']));
    bntm_set_setting('bh_owner_contact',    sanitize_text_field($_POST['owner_contact']));
    bntm_set_setting('bh_billing_day',      intval($_POST['billing_day']));
    bntm_set_setting('bh_grace_days',       intval($_POST['grace_days']));
    bntm_set_setting('bh_penalty_rate',     floatval($_POST['penalty_rate']));
    bntm_set_setting('bh_currency',         sanitize_text_field($_POST['currency']));
    bh_log_audit('save_settings','settings',0,'','','Settings saved');
    wp_send_json_success(['message'=>'Settings saved successfully!']);
}

function bntm_ajax_bh_add_staff() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    $email    = sanitize_email($_POST['email']);
    $username = sanitize_user($_POST['username']);
    $role     = sanitize_text_field($_POST['role'] ?? 'subscriber');
    if (email_exists($email) || username_exists($username)) {
        wp_send_json_error(['message'=>'User already exists.']);
    }
    $uid = wp_create_user($username, wp_generate_password(), $email);
    if (is_wp_error($uid)) { wp_send_json_error(['message'=>$uid->get_error_message()]); }
    wp_update_user(['ID'=>$uid,'role'=>$role]);
    wp_send_json_success(['message'=>'Staff account created.']);
}

function bntm_ajax_bh_delete_staff() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    $uid = intval($_POST['user_id']);
    if ($uid === get_current_user_id()) { wp_send_json_error(['message'=>'Cannot delete your own account.']); }
    require_once(ABSPATH.'wp-admin/includes/user.php');
    wp_delete_user($uid);
    wp_send_json_success(['message'=>'Staff account deleted.']);
}

function bntm_ajax_bh_add_payment_method() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    $name = sanitize_text_field($_POST['payment_name']);
    if (empty($name)) { wp_send_json_error(['message'=>'Payment name required.']); }
    $methods = json_decode(bntm_get_setting('bh_payment_methods','[]'),true);
    if (!is_array($methods)) $methods = [];
    $methods[] = [
        'type'=>sanitize_text_field($_POST['payment_type']),'name'=>$name,
        'account_name'=>sanitize_text_field($_POST['account_name']??''),
        'account_number'=>sanitize_text_field($_POST['account_number']??''),
    ];
    bntm_set_setting('bh_payment_methods', json_encode($methods));
    wp_send_json_success(['message'=>'Payment method added.']);
}

function bntm_ajax_bh_remove_payment_method() {
    check_ajax_referer('bh_nonce','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    $index   = intval($_POST['index']);
    $methods = json_decode(bntm_get_setting('bh_payment_methods','[]'),true);
    if (!is_array($methods) || !isset($methods[$index])) { wp_send_json_error(['message'=>'Method not found.']); }
    array_splice($methods,$index,1);
    bntm_set_setting('bh_payment_methods',json_encode($methods));
    wp_send_json_success(['message'=>'Payment method removed.']);
}

// ============================================================
// AJAX HANDLERS — FINANCE EXPORT
// ============================================================

function bntm_ajax_bh_fn_export_payment() {
    check_ajax_referer('bh_fn_action');
    if (!is_user_logged_in()) { wp_send_json_error('Unauthorized'); }
    global $wpdb;
    $fn_table  = $wpdb->prefix . 'fn_transactions';
    $pay_id    = intval($_POST['payment_id']);
    $amount    = floatval($_POST['amount']);
    $exists    = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$fn_table} WHERE reference_type='bh_payment' AND reference_id=%d",$pay_id));
    if ($exists) { wp_send_json_error('Already exported.'); }
    $result = $wpdb->insert($fn_table,[
        'rand_id'=>bntm_rand_id(),'type'=>'income','amount'=>$amount,
        'category'=>'Boarding House Rental Payment',
        'notes'=>'BHMIS Payment #'.$pay_id,
        'reference_type'=>'bh_payment','reference_id'=>$pay_id,
        'created_at'=>current_time('mysql'),
    ],['%s','%s','%f','%s','%s','%s','%d','%s']);
    if ($result) {
        if (function_exists('bntm_fn_update_cashflow_summary')) bntm_fn_update_cashflow_summary();
        wp_send_json_success('Exported.');
    } else {
        wp_send_json_error('Export failed.');
    }
}

function bntm_ajax_bh_fn_revert_payment() {
    check_ajax_referer('bh_fn_action');
    if (!is_user_logged_in()) { wp_send_json_error('Unauthorized'); }
    global $wpdb;
    $result = $wpdb->delete($wpdb->prefix.'fn_transactions',['reference_type'=>'bh_payment','reference_id'=>intval($_POST['payment_id'])],['%s','%d']);
    if ($result) {
        if (function_exists('bntm_fn_update_cashflow_summary')) bntm_fn_update_cashflow_summary();
        wp_send_json_success('Reverted.');
    } else {
        wp_send_json_error('Revert failed.');
    }
}

// ============================================================
// AJAX HANDLERS — TENANT PORTAL
// ============================================================

function bntm_ajax_bh_portal_get_data() {
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Please log in.']); }
    global $wpdb;
    $t   = $wpdb->prefix . 'bh_tenants';
    $i   = $wpdb->prefix . 'bh_invoices';
    $p   = $wpdb->prefix . 'bh_payments';
    $mr  = $wpdb->prefix . 'bh_maintenance_requests';
    $ra  = $wpdb->prefix . 'bh_room_assignments';
    $r   = $wpdb->prefix . 'bh_rooms';
    $n   = $wpdb->prefix . 'bh_notifications';
    $uid = get_current_user_id();
    $tenant = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE user_id=%d", $uid));
    if (!$tenant) {
        // Try to find tenant by the current user's email and link the account,
        // otherwise create a minimal tenant record so the portal works for logged-in users.
        $current_user = wp_get_current_user();
        $email = isset($current_user->user_email) ? $current_user->user_email : '';

        if ($email) {
            $tenant = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE email=%s", $email));
            if ($tenant) {
                // Link existing tenant record to WP user
                $wpdb->update($t, ['user_id' => $uid], ['id' => $tenant->id], ['%d'], ['%d']);
            } else {
                // Create a minimal tenant profile from WP user data
                $first_name = get_user_meta($uid, 'first_name', true) ?: '';
                $last_name  = get_user_meta($uid, 'last_name', true) ?: '';
                if (empty($first_name) && !empty($current_user->display_name) && strpos($current_user->display_name, ' ') !== false) {
                    list($first_name, $last_name) = explode(' ', $current_user->display_name, 2);
                }

                $insert_data = [
                    'rand_id'     => function_exists('bntm_rand_id') ? bntm_rand_id() : uniqid('bh_', true),
                    'user_id'     => $uid,
                    'first_name'  => $first_name ?: $current_user->user_login,
                    'last_name'   => $last_name,
                    'email'       => $email,
                    'status'      => 'active',
                    'created_at'  => current_time('mysql'),
                ];
                $formats = ['%s','%d','%s','%s','%s','%s','%s'];
                $wpdb->insert($t, $insert_data, $formats);
                if ($wpdb->insert_id) {
                    $tenant = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d", $wpdb->insert_id));
                }
            }
        }

        if (!$tenant) {
            wp_send_json_error(['message'=>'No tenant profile linked.']);
        }
    }
    $room = $wpdb->get_row($wpdb->prepare("SELECT r.* FROM {$r} r INNER JOIN {$ra} ra ON ra.room_id=r.id WHERE ra.tenant_id=%d AND ra.is_current=1",$tenant->id));
    $invoices  = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$i} WHERE tenant_id=%d ORDER BY due_date DESC",$tenant->id));
    $payments  = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$p} WHERE tenant_id=%d ORDER BY created_at DESC",$tenant->id));
    $requests  = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$mr} WHERE tenant_id=%d ORDER BY created_at DESC",$tenant->id));
    $notifs    = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$n} WHERE tenant_id=%d ORDER BY created_at DESC LIMIT 20",$tenant->id));
    wp_send_json_success(['tenant'=>$tenant,'room'=>$room,'invoices'=>$invoices,'payments'=>$payments,'requests'=>$requests,'notifications'=>$notifs]);
}

function bntm_ajax_bh_portal_pay_invoice() {
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Please log in.']); }
    global $wpdb;
    $inv_id = intval($_POST['invoice_id']);
    $inv    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_invoices WHERE id=%d",$inv_id));
    $tenant = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}bh_tenants WHERE user_id=%d",get_current_user_id()));
    if (!$inv || !$tenant || $inv->tenant_id !== $tenant->id) { wp_send_json_error(['message'=>'Unauthorized.']); }
    $methods = json_decode(bntm_get_setting('bh_payment_methods','[]'),true);
    wp_send_json_success(['invoice'=>$inv,'payment_methods'=>$methods,'message'=>'Please follow the payment instructions below.']);
}

function bntm_ajax_bh_portal_submit_payment() {
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Please log in.']); }
    check_ajax_referer('bh_nonce','nonce');
    global $wpdb;
    $inv_id = intval($_POST['invoice_id']);
    $inv    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_invoices WHERE id=%d",$inv_id));
    $tenant = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bh_tenants WHERE user_id=%d",get_current_user_id()));
    if (!$inv || !$tenant || $inv->tenant_id !== $tenant->id) { wp_send_json_error(['message'=>'Unauthorized.']); }

    $amount = floatval($_POST['amount'] ?? 0);
    $method = sanitize_text_field($_POST['payment_method'] ?? 'manual');
    $reference = sanitize_text_field($_POST['reference_number'] ?? '');
    $pdate = sanitize_text_field($_POST['payment_date'] ?? current_time('Y-m-d'));

    if ($amount <= 0) { wp_send_json_error(['message'=>'Invalid amount.']); }

    // Record as pending so admin can verify and complete the payment
    $receipt_num = 'PENDING-' . strtoupper(substr(md5(time().$inv_id),0,8));
    $ins = $wpdb->insert($wpdb->prefix . 'bh_payments', [
        'rand_id' => bntm_rand_id(),
        'invoice_id' => $inv_id,
        'tenant_id' => $tenant->id,
        'amount' => $amount,
        'payment_method' => $method,
        'reference_number' => $reference,
        'receipt_number' => $receipt_num,
        'payment_date' => $pdate,
        'recorded_by' => $tenant->user_id ?? get_current_user_id(),
        'notes' => 'Submitted via tenant portal',
        'status' => 'pending',
    ]);

    if ($ins) {
        bh_log_audit('portal_payment_submit','payment',$wpdb->insert_id,'','amount='.$amount,'Tenant submitted payment intent');
        // Notify admin/user
        bh_send_notification_by_key('payment_confirmed', $tenant->id, ['invoice_amount'=>'₱'.number_format($amount,2),'due_date'=>$pdate]);
        wp_send_json_success(['message'=>'Payment submitted. Admin will verify and record it shortly.']);
    }

    wp_send_json_error(['message'=>'Failed to submit payment.']);
}

// ============================================================
// FRONTEND SHORTCODES
// ============================================================

function bntm_shortcode_bh_tenant_portal() {
    if (!is_user_logged_in()) {
        return '<div style="padding:40px;text-align:center;"><a href="'.wp_login_url(get_permalink()).'" style="background:var(--bntm-primary,#6366f1);color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">Log In to Access Tenant Portal</a></div>';
    }
    $nonce = wp_create_nonce('bh_nonce');
    ob_start();
    ?>
    <script>var ajaxurl='<?php echo admin_url('admin-ajax.php'); ?>';var bh_nonce='<?php echo $nonce; ?>';</script>
    <div class="bh-portal-wrap">
        <div id="bh-portal-loading" style="text-align:center;padding:40px;color:#6b7280;">Loading your portal...</div>
        <div id="bh-portal-content" style="display:none;"></div>
    </div>

    <style>
    .bh-portal-wrap{max-width:900px;margin:0 auto;padding:20px;}
    .bh-portal-header{background:linear-gradient(135deg,var(--bntm-primary,#6366f1),var(--bntm-primary-hover,#4f46e5));color:#fff;border-radius:14px;padding:28px 32px;margin-bottom:24px;}
    .bh-portal-header h2{margin:0 0 6px;font-size:22px;}
    .bh-portal-header p{margin:0;opacity:.85;font-size:14px;}
    .bh-portal-tabs{display:flex;gap:4px;background:#f3f4f6;border-radius:10px;padding:4px;margin-bottom:20px;flex-wrap:wrap;}
    .bh-portal-tab{flex:1;padding:9px 14px;border:none;background:transparent;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#374151;transition:.15s;}
    .bh-portal-tab.active{background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.1);color:var(--bntm-primary,#6366f1);}
    .bh-portal-panel{display:none;}.bh-portal-panel.active{display:block;}
    .bh-invoice-card{border:1px solid #e5e7eb;border-radius:10px;padding:16px 20px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;}
    .bh-invoice-card .inv-amount{font-size:20px;font-weight:700;color:#1e3a5f;}
    .bh-invoice-card .inv-due{font-size:12px;color:#6b7280;}
    @media(max-width:600px){.bh-portal-tabs{flex-direction:column;}.bh-portal-tab{text-align:center;}}
    </style>

    <script>
    (function(){
        let portalData = null;

        function renderPortal(data) {
            portalData = data;
            const t = data.tenant;
            const r = data.room;
            const nextInv = data.invoices.find(i=>['unpaid','partial','overdue'].includes(i.invoice_status));
            document.getElementById('bh-portal-loading').style.display='none';
            document.getElementById('bh-portal-content').style.display='block';
            document.getElementById('bh-portal-content').innerHTML = `
                <div class="bh-portal-header">
                    <h2>Welcome, ${t.first_name} ${t.last_name}</h2>
                    <p>${r ? 'Room '+r.room_number+' ('+r.category+')' : 'No room assigned'} &bull; Balance: <strong>&#8369;${parseFloat(t.account_balance||0).toFixed(2)}</strong>${nextInv?' &bull; Next due: <strong>'+nextInv.due_date+'</strong>':''}</p>
                </div>
                <div class="bh-portal-tabs">
                    <button class="bh-portal-tab active" onclick="bhPortalTab(this,'invoices')">Invoices</button>
                    <button class="bh-portal-tab" onclick="bhPortalTab(this,'payments')">Payments</button>
                    <button class="bh-portal-tab" onclick="bhPortalTab(this,'maintenance')">Maintenance</button>
                    <button class="bh-portal-tab" onclick="bhPortalTab(this,'notifications')">Notifications</button>
                    <button class="bh-portal-tab" onclick="bhPortalTab(this,'profile')">My Profile</button>
                </div>
                <div id="portal-panel-invoices" class="bh-portal-panel active">${renderInvoices(data.invoices)}</div>
                <div id="portal-panel-payments" class="bh-portal-panel">${renderPayments(data.payments)}</div>
                <div id="portal-panel-maintenance" class="bh-portal-panel">${renderMaintenance(data.requests)}</div>
                <div id="portal-panel-notifications" class="bh-portal-panel">${renderNotifications(data.notifications)}</div>
                <div id="portal-panel-profile" class="bh-portal-panel">${renderProfile(data.tenant)}</div>
            `;
        }

        function renderInvoices(invoices) {
            if (!invoices.length) return '<p style="color:#9ca3af;text-align:center;padding:30px;">No invoices yet.</p>';
            return invoices.map(inv=>`
                <div class="bh-invoice-card">
                    <div>
                        <p style="margin:0 0 4px;font-size:12px;color:#6b7280;">Invoice #${inv.id} &bull; ${inv.billing_period_start} to ${inv.billing_period_end}</p>
                        <p class="inv-amount">&#8369;${parseFloat(inv.total_amount).toFixed(2)}</p>
                        <p class="inv-due">Balance: &#8369;${parseFloat(inv.balance).toFixed(2)} &bull; Due: ${inv.due_date}</p>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <span class="bh-status-badge bh-badge-${inv.invoice_status}">${inv.invoice_status}</span>
                        ${['unpaid','partial','overdue'].includes(inv.invoice_status)?`<button class="bntm-btn-primary bntm-btn-small" onclick="bhPortalPayInvoice(${inv.id})">Pay</button>`:''}
                    </div>
                </div>
            `).join('');
        }

        function renderPayments(payments) {
            if (!payments.length) return '<p style="color:#9ca3af;text-align:center;padding:30px;">No payments recorded.</p>';
            return `<div class="bntm-table-wrapper"><table class="bntm-table">
                <thead><tr><th>Receipt</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>${payments.map(p=>`<tr>
                    <td>${p.receipt_number||'#'+p.id}</td><td>#${p.invoice_id}</td>
                    <td>&#8369;${parseFloat(p.amount).toFixed(2)}</td>
                    <td>${p.payment_method}</td><td>${p.payment_date}</td>
                </tr>`).join('')}</tbody>
            </table></div>`;
        }

        function renderMaintenance(requests) {
            let html = `<div style="margin-bottom:16px;"><button class="bntm-btn-primary" onclick="bhPortalSubmitRequest()">+ Submit New Request</button></div>`;
            if (!requests.length) return html+'<p style="color:#9ca3af;text-align:center;padding:20px;">No requests submitted.</p>';
            return html+`<div class="bntm-table-wrapper"><table class="bntm-table">
                <thead><tr><th>Ticket</th><th>Category</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>${requests.map(r=>`<tr>
                    <td><strong>${r.ticket_number}</strong></td>
                    <td>${r.category}</td>
                    <td><span class="bh-status-badge bh-badge-${r.priority}">${r.priority}</span></td>
                    <td><span class="bh-status-badge bh-badge-${r.workflow_status}">${r.workflow_status.replace(/_/g,' ')}</span></td>
                    <td style="font-size:12px;">${r.created_at.slice(0,10)}</td>
                </tr>`).join('')}</tbody>
            </table></div>`;
        }

        function renderNotifications(notifs) {
            if (!notifs.length) return '<p style="color:#9ca3af;text-align:center;padding:30px;">No notifications.</p>';
            return notifs.map(n=>`
                <div style="padding:12px 16px;border-bottom:1px solid #f3f4f6;${n.is_read?'':'background:#f0f4ff;'}border-radius:6px;margin-bottom:4px;">
                    <p style="margin:0 0 4px;font-weight:${n.is_read?'400':'600'};font-size:14px;">${n.subject}</p>
                    <p style="margin:0 0 4px;font-size:13px;color:#374151;">${n.message}</p>
                    <p style="margin:0;font-size:11px;color:#9ca3af;">${n.created_at}</p>
                </div>
            `).join('');
        }

        function renderProfile(t) {
            return `<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;font-size:14px;padding:8px 0;">
                <div><span style="color:#6b7280;">Name</span><p style="margin:2px 0;font-weight:600;">${t.first_name} ${t.last_name}</p></div>
                <div><span style="color:#6b7280;">Email</span><p style="margin:2px 0;">${t.email}</p></div>
                <div><span style="color:#6b7280;">Phone</span><p style="margin:2px 0;">${t.phone||'—'}</p></div>
                <div><span style="color:#6b7280;">Lease</span><p style="margin:2px 0;">${t.lease_start||'—'} to ${t.lease_end||'—'}</p></div>
                <div><span style="color:#6b7280;">Move-In</span><p style="margin:2px 0;">${t.move_in_date||'—'}</p></div>
                <div><span style="color:#6b7280;">Deposit</span><p style="margin:2px 0;">&#8369;${parseFloat(t.deposit_amount).toFixed(2)}</p></div>
            </div>`;
        }

        window.bhPortalTab = function(btn, panel) {
            document.querySelectorAll('.bh-portal-tab').forEach(b=>b.classList.remove('active'));
            document.querySelectorAll('.bh-portal-panel').forEach(p=>p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('portal-panel-'+panel).classList.add('active');
        };

        window.bhPortalPayInvoice = function(invId) {
            bhPost('bh_portal_pay_invoice',{invoice_id:invId},json=>{
                if(!json.success){alert(json.data.message);return;}
                const d=json.data;
                const methodsList = d.payment_methods || [];
                const methodsHtml = methodsList.length
                    ? methodsList.map((m,i)=>`<option value="${i}">${m.name} (${m.type}) ${m.account_number?'- '+m.account_number:''}</option>`).join('')
                    : '';

                const body = `
                    <p style="font-size:14px;margin-bottom:12px;">Total due: <strong>&#8369;${parseFloat(d.invoice.balance).toFixed(2)}</strong></p>
                    ${methodsList.length?`<div style="margin-bottom:10px;"><label style="font-weight:600;display:block;margin-bottom:6px;">Choose Payment Method</label><select id="bh-pay-method" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;">${methodsHtml}</select></div>`:'<p style="color:#9ca3af;">No payment methods configured. Contact the admin.</p>'}
                    <div style="margin-bottom:10px;"><label style="display:block;margin-bottom:6px;">Amount</label><input id="bh-pay-amount" type="number" step="0.01" value="${parseFloat(d.invoice.balance).toFixed(2)}" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;"/></div>
                    <div style="margin-bottom:10px;"><label style="display:block;margin-bottom:6px;">Reference / Transaction ID</label><input id="bh-pay-ref" type="text" placeholder="e.g. TRX12345" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px;"/></div>
                    <div style="margin-bottom:6px;"><label style="display:block;margin-bottom:6px;">Payment Date</label><input id="bh-pay-date" type="date" value="${new Date().toISOString().slice(0,10)}" style="padding:8px;border:1px solid #e5e7eb;border-radius:6px;"/></div>
                    <p style="font-size:12px;color:#9ca3af;margin-top:12px;">After submitting, the payment will be recorded as pending and the administrator will verify it.</p>
                    <div style="display:flex;gap:8px;margin-top:12px;"><button id="bh-pay-submit" class="bntm-btn-primary">Submit Payment</button><button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button></div>
                `;

                bhOpenModal('Pay Invoice #'+invId, body);

                document.getElementById('bh-pay-submit').addEventListener('click', function(){
                    const btn = this; btn.disabled = true; btn.textContent = 'Submitting...';
                    const amount = parseFloat(document.getElementById('bh-pay-amount').value || 0);
                    if (amount <= 0) { bhToast('Enter a valid amount.','error'); btn.disabled=false; btn.textContent='Submit Payment'; return; }
                    const methodIndex = document.getElementById('bh-pay-method') ? document.getElementById('bh-pay-method').value : null;
                    const method = methodIndex !== null && methodsList[methodIndex] ? methodsList[methodIndex].name : 'manual';
                    const ref = document.getElementById('bh-pay-ref').value || '';
                    const pdate = document.getElementById('bh-pay-date').value || new Date().toISOString().slice(0,10);

                    bhPost('bh_portal_submit_payment',{
                        invoice_id: invId,
                        amount: amount,
                        payment_method: method,
                        reference_number: ref,
                        payment_date: pdate
                    }, resp=>{
                        if (resp.success) {
                            bhToast(resp.data.message || 'Payment submitted.','success');
                            bhCloseModal();
                            // Refresh portal data
                            bhPost('bh_portal_get_data',{},json2=>{ if(json2.success) renderPortal(json2.data); else location.reload(); });
                        } else {
                            bhToast(resp.data.message || 'Failed to submit.','error');
                            btn.disabled=false; btn.textContent='Submit Payment';
                        }
                    });
                });
            });
        };

        window.bhPortalSubmitRequest = function() {
            bhOpenModal('Submit Maintenance Request',`
                <div class="bh-form-row">
                    <div class="bh-form-group"><label>Category *</label>
                        <select id="bh-pr-cat">
                            <option value="plumbing">Plumbing</option><option value="electrical">Electrical</option>
                            <option value="structural">Structural</option><option value="appliance">Appliance</option>
                            <option value="cleaning">Cleaning</option><option value="other">Other</option>
                        </select>
                    </div>
                    <div class="bh-form-group"><label>Priority</label>
                        <select id="bh-pr-prio">
                            <option value="low">Low</option><option value="medium" selected>Medium</option>
                            <option value="high">High</option><option value="emergency">Emergency</option>
                        </select>
                    </div>
                </div>
                <div class="bh-form-row single">
                    <div class="bh-form-group"><label>Description *</label><textarea id="bh-pr-desc" style="min-height:100px;" placeholder="Describe the issue in detail..."></textarea></div>
                </div>
                <div class="bh-modal-footer">
                    <button class="bntm-btn-secondary" onclick="bhCloseModal()">Cancel</button>
                    <button class="bntm-btn-primary" id="bh-submit-req-btn">Submit Request</button>
                </div>
            `);
            document.getElementById('bh-submit-req-btn').addEventListener('click',function(){
                const btn=this;btn.disabled=true;btn.textContent='Submitting...';
                const desc = document.getElementById('bh-pr-desc').value;
                if(!desc.trim()){bhToast('Description required.','error');btn.disabled=false;btn.textContent='Submit Request';return;}
                bhPost('bh_submit_maintenance',{
                    category:document.getElementById('bh-pr-cat').value,
                    priority:document.getElementById('bh-pr-prio').value,
                    description:desc,
                },json=>{
                    if(json.success){bhToast('Request submitted! Ticket: '+json.data.ticket,'success');bhCloseModal();setTimeout(()=>location.reload(),2000);}
                    else{bhToast(json.data.message,'error');btn.disabled=false;btn.textContent='Submit Request';}
                });
            });
        };

        // Load portal data
        bhPost('bh_portal_get_data',{},json=>{
            if(json.success){renderPortal(json.data);}
            else{
                document.getElementById('bh-portal-loading').innerHTML='<p style="color:#dc2626;">'+json.data.message+'<br><a href="<?php echo wp_login_url(get_permalink()); ?>">Log in again</a></p>';
            }
        });

        // Shared modal and toast for portal
        function bhOpenModal(title,body){
            let ov=document.getElementById('bh-portal-modal');
            if(!ov){
                ov=document.createElement('div');
                ov.id='bh-portal-modal';
                ov.style='position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.55);z-index:99990;display:flex;align-items:center;justify-content:center;';
                ov.innerHTML='<div style="background:#fff;border-radius:12px;width:90%;max-width:580px;max-height:88vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);">'+
                    '<div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #e5e7eb;">'+
                    '<h3 id="bh-pm-title" style="margin:0;font-size:16px;"></h3>'+
                    '<button onclick="bhCloseModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#9ca3af;">&times;</button></div>'+
                    '<div id="bh-pm-body" style="padding:22px;"></div></div>';
                document.body.appendChild(ov);
                ov.addEventListener('click',function(e){if(e.target===ov)bhCloseModal();});
            }
            document.getElementById('bh-pm-title').textContent=title;
            document.getElementById('bh-pm-body').innerHTML=body;
            ov.style.display='flex';
        }
        function bhCloseModal(){const ov=document.getElementById('bh-portal-modal');if(ov)ov.style.display='none';}
        function bhToast(msg,type){
            let t=document.getElementById('bh-portal-toast');
            if(!t){t=document.createElement('div');t.id='bh-portal-toast';t.style='position:fixed;bottom:28px;right:28px;z-index:99999;padding:14px 22px;border-radius:8px;font-size:14px;font-weight:500;color:#fff;';document.body.appendChild(t);}
            t.textContent=msg;t.style.background=type==='error'?'#dc2626':'#059669';t.style.display='block';
            setTimeout(()=>t.style.display='none',3200);
        }
        function bhPost(action,data,cb){
            const fd=new FormData();fd.append('action',action);fd.append('nonce',bh_nonce);
            for(const k in data)fd.append(k,data[k]);
            fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(cb).catch(()=>bhToast('Request failed.','error'));
        }
        window.bhOpenModal=bhOpenModal;window.bhCloseModal=bhCloseModal;window.bhToast=bhToast;window.bhPost=bhPost;
    })();
    </script>
    <?php
    return ob_get_clean();
}

function bntm_shortcode_bh_maintenance_form() {
    if (!is_user_logged_in()) {
        return '<div style="text-align:center;padding:40px;"><a href="'.wp_login_url(get_permalink()).'" style="background:var(--bntm-primary,#6366f1);color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">Log In to Submit a Request</a></div>';
    }
    $nonce = wp_create_nonce('bh_nonce');
    ob_start();
    ?>
    <script>var ajaxurl='<?php echo admin_url('admin-ajax.php'); ?>';var bh_nonce='<?php echo $nonce; ?>';</script>
    <div style="max-width:560px;margin:0 auto;padding:24px;">
        <div style="background:linear-gradient(135deg,var(--bntm-primary,#6366f1),var(--bntm-primary-hover,#4f46e5));color:#fff;border-radius:14px;padding:24px 28px;margin-bottom:24px;">
            <h2 style="margin:0 0 6px;font-size:20px;">Submit a Maintenance Request</h2>
            <p style="margin:0;opacity:.85;font-size:13px;">Describe the issue and we'll get it resolved as soon as possible.</p>
        </div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;">
            <div style="margin-bottom:16px;">
                <label style="font-size:13px;font-weight:500;color:#374151;display:block;margin-bottom:5px;">Category *</label>
                <select id="bh-mf-cat" style="width:100%;border:1px solid #d1d5db;border-radius:6px;padding:9px 12px;font-size:14px;">
                    <option value="plumbing">Plumbing</option>
                    <option value="electrical">Electrical</option>
                    <option value="structural">Structural</option>
                    <option value="appliance">Appliance</option>
                    <option value="cleaning">Cleaning</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div style="margin-bottom:16px;">
                <label style="font-size:13px;font-weight:500;color:#374151;display:block;margin-bottom:5px;">Priority</label>
                <select id="bh-mf-prio" style="width:100%;border:1px solid #d1d5db;border-radius:6px;padding:9px 12px;font-size:14px;">
                    <option value="low">Low — Not urgent</option>
                    <option value="medium" selected>Medium — Needs attention soon</option>
                    <option value="high">High — Affects comfort/safety</option>
                    <option value="emergency">Emergency — Immediate action needed</option>
                </select>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:13px;font-weight:500;color:#374151;display:block;margin-bottom:5px;">Description *</label>
                <textarea id="bh-mf-desc" rows="5" style="width:100%;border:1px solid #d1d5db;border-radius:6px;padding:9px 12px;font-size:14px;resize:vertical;" placeholder="Describe the issue, where it is located, and when it started..."></textarea>
            </div>
            <div id="bh-mf-msg" style="margin-bottom:12px;"></div>
            <button id="bh-mf-submit" style="width:100%;background:var(--bntm-primary,#6366f1);color:#fff;border:none;border-radius:8px;padding:12px;font-size:15px;font-weight:600;cursor:pointer;">Submit Request</button>
        </div>
    </div>

    <script>
    (function(){
        document.getElementById('bh-mf-submit').addEventListener('click',function(){
            const btn=this;
            const desc=document.getElementById('bh-mf-desc').value.trim();
            const msg=document.getElementById('bh-mf-msg');
            if(!desc){msg.innerHTML='<div style="color:#dc2626;font-size:13px;padding:8px 0;">Please describe the issue.</div>';return;}
            btn.disabled=true;btn.textContent='Submitting...';
            const fd=new FormData();
            fd.append('action','bh_submit_maintenance');
            fd.append('nonce',bh_nonce);
            fd.append('category',document.getElementById('bh-mf-cat').value);
            fd.append('priority',document.getElementById('bh-mf-prio').value);
            fd.append('description',desc);
            fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(json=>{
                if(json.success){
                    msg.innerHTML='<div style="background:#d1fae5;color:#065f46;border-radius:8px;padding:14px 16px;font-size:14px;"><strong>Request submitted!</strong><br>Your ticket number: <code style="font-size:15px;">'+json.data.ticket+'</code><br><span style="font-size:12px;">We will update you on the status of your request.</span></div>';
                    document.getElementById('bh-mf-desc').value='';
                    btn.disabled=false;btn.textContent='Submit Request';
                } else {
                    msg.innerHTML='<div style="color:#dc2626;font-size:13px;padding:8px 0;">'+json.data.message+'</div>';
                    btn.disabled=false;btn.textContent='Submit Request';
                }
            }).catch(()=>{msg.innerHTML='<div style="color:#dc2626;">Request failed. Try again.</div>';btn.disabled=false;btn.textContent='Submit Request';});
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function bh_log_audit($action, $entity_type, $entity_id, $old_value, $new_value, $description) {
    global $wpdb;
    $wpdb->insert($wpdb->prefix.'bh_audit_log',[
        'rand_id'     => bntm_rand_id(),
        'user_id'     => get_current_user_id(),
        'action'      => $action,
        'entity_type' => $entity_type,
        'entity_id'   => $entity_id,
        'old_value'   => $old_value,
        'new_value'   => $new_value,
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        'description' => $description,
    ]);
}

function bh_seed_notification_templates() {
    global $wpdb;
    $table = $wpdb->prefix . 'bh_notification_templates';
    $templates = [
        'tenant_registered' => [
            'name' => 'Tenant Registered',
            'subject' => 'Welcome, {{tenant_name}}!',
            'body' => 'A new tenant has been registered: {{tenant_name}}. Please review their profile in the system.',
            'channel' => 'email',
            'placeholders' => ['tenant_name'],
        ],
        'room_assigned' => [
            'name' => 'Room Assigned',
            'subject' => 'Room Assignment Completed',
            'body' => 'A room assignment has been completed for tenant ID {{tenant_id}}. Assigned room: {{room_number}}.',
            'channel' => 'email',
            'placeholders' => ['tenant_id', 'room_number'],
        ],
        'invoice_generated' => [
            'name' => 'Invoice Generated',
            'subject' => 'Invoice Generated',
            'body' => 'An invoice has been generated for your account. Amount: {{invoice_amount}}. Due date: {{due_date}}.',
            'channel' => 'email',
            'placeholders' => ['invoice_amount', 'due_date'],
        ],
        'payment_confirmed' => [
            'name' => 'Payment Confirmed',
            'subject' => 'Payment Received',
            'body' => 'Payment of {{invoice_amount}} has been confirmed on {{due_date}}. Thank you.',
            'channel' => 'email',
            'placeholders' => ['invoice_amount', 'due_date'],
        ],
        'maintenance_update' => [
            'name' => 'Maintenance Update',
            'subject' => 'Maintenance Request Update',
            'body' => 'Your maintenance ticket {{ticket_number}} status has changed to {{new_status}}.',
            'channel' => 'email',
            'placeholders' => ['ticket_number', 'new_status'],
        ],
    ];

    foreach ($templates as $template_key => $template) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE template_key = %s",
            $template_key
        ));

        if (!$exists) {
            $wpdb->insert($table, [
                'rand_id' => bntm_rand_id(),
                'template_key' => $template_key,
                'name' => $template['name'],
                'subject' => $template['subject'],
                'body' => $template['body'],
                'channel' => $template['channel'],
                'placeholders' => json_encode($template['placeholders']),
                'is_active' => 1,
            ], ['%s','%s','%s','%s','%s','%s','%d']);
        }
    }
}

function bh_send_notification_by_key($template_key, $tenant_id = null, $data = []) {
    global $wpdb;
    $template = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bh_notification_templates WHERE template_key = %s AND status = 'active' AND is_active = 1",
        $template_key
    ));

    if (!$template) {
        return false;
    }

    $subject = $template->subject;
    $message = $template->body;

    foreach ($data as $key => $value) {
        $subject = str_replace('{{' . $key . '}}', $value, $subject);
        $message = str_replace('{{' . $key . '}}', $value, $message);
    }

    $wpdb->insert($wpdb->prefix . 'bh_notifications', [
        'rand_id' => bntm_rand_id(),
        'tenant_id' => $tenant_id,
        'user_id' => get_current_user_id(),
        'type' => $template_key,
        'channel' => $template->channel,
        'subject' => $subject,
        'message' => $message,
        'is_read' => 0,
        'status' => 'sent',
        'sent_at' => current_time('mysql'),
    ], ['%s','%d','%d','%s','%s','%s','%d','%s','%s']);

    $insert_id = $wpdb->insert_id;

    if ($template->channel === 'email' && $tenant_id) {
        $tenant = $wpdb->get_row($wpdb->prepare("SELECT email, first_name, last_name FROM {$wpdb->prefix}bh_tenants WHERE id=%d", $tenant_id));
        if ($tenant && !empty($tenant->email)) {
            $to = $tenant->email;
            $email_subject = $subject;
            $email_message = $message;
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $sent = wp_mail($to, $email_subject, $email_message, $headers);
            if (!$sent) {
                $wpdb->update($wpdb->prefix . 'bh_notifications', ['status' => 'failed'], ['id' => $insert_id]);
            }
        }
    }

    return true;
}

function bh_log_maintenance_history($request_id, $new_status, $note = '') {
    global $wpdb;
    $old = $wpdb->get_var($wpdb->prepare("SELECT workflow_status FROM {$wpdb->prefix}bh_maintenance_requests WHERE id=%d",$request_id));
    $wpdb->insert($wpdb->prefix.'bh_maintenance_history',[
        'rand_id'=>bntm_rand_id(),'request_id'=>$request_id,
        'changed_by'=>get_current_user_id(),'old_status'=>$old,'new_status'=>$new_status,'note'=>$note,
    ]);
}

function bh_vacate_room_for_tenant($tenant_id) {
    global $wpdb;
    $ra    = $wpdb->prefix . 'bh_room_assignments';
    $rooms = $wpdb->prefix . 'bh_rooms';
    $assign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ra} WHERE tenant_id=%d AND is_current=1",$tenant_id));
    if (!$assign) return;
    $wpdb->update($ra,['is_current'=>0,'vacated_date'=>current_time('Y-m-d')],['id'=>$assign->id]);
    $remaining = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ra} WHERE room_id=%d AND is_current=1",$assign->room_id));
    if ($remaining === 0) {
        $wpdb->update($rooms,['is_occupied'=>0],['id'=>$assign->room_id]);
    }
}