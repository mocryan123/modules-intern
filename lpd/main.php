<?php
/**
 * Module Name: Laundry Pickup & Delivery
 * Module Slug: lpd
 * Description: Centralized order management system for laundry pickup and delivery businesses.
 *              Handles customers, orders, drivers, billing, email templates, and reporting.
 * Version: 1.0.0
 * Author: BNTM
 * Icon: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h18M3 18h18"/><circle cx="19" cy="6" r="2"/><circle cx="19" cy="18" r="2"/></svg>
 */

if (!defined('ABSPATH')) exit;

define('BNTM_LPD_PATH', dirname(__FILE__) . '/');
define('BNTM_LPD_URL',  plugin_dir_url(__FILE__));

// Add an Admin menu page so the LPD dashboard and tabs are always accessible
if (is_admin()) {
    add_action('admin_menu', 'bntm_lpd_register_admin_menu');
    function bntm_lpd_register_admin_menu() {
        if (!function_exists('add_menu_page')) return;
        add_menu_page(
            'Laundry Pickup & Delivery',
            'LPD',
            'manage_options',
            'bntm-lpd',
            'bntm_lpd_admin_page',
            'dashicons-archive',
            56
        );
    }

    function bntm_lpd_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>Laundry Pickup & Delivery</h1>';
        echo bntm_shortcode_lpd();
        echo '</div>';
    }
}

// ============================================================
// MODULE CONFIGURATION
// ============================================================

function bntm_lpd_get_pages() {
    return [
        'Track My Order'   => '[lpd_track_order]',
        'Customer Portal'  => '[lpd_customer_portal]',
    ];
}

function bntm_lpd_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $p       = $wpdb->prefix;

    return [
        'lpd_customers' => "CREATE TABLE {$p}lpd_customers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(200) NOT NULL,
            phone VARCHAR(50),
            pickup_address TEXT,
            delivery_address TEXT,
            service_preferences LONGTEXT,
            notes TEXT,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'lpd_orders' => "CREATE TABLE {$p}lpd_orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            reference_number VARCHAR(30) UNIQUE NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            driver_id BIGINT UNSIGNED DEFAULT NULL,
            service_type VARCHAR(100) NOT NULL,
            status VARCHAR(80) NOT NULL DEFAULT 'Pending Request',
            weight_kg DECIMAL(8,2) DEFAULT 0.00,
            item_count INT DEFAULT 0,
            items_detail LONGTEXT,
            pickup_address TEXT,
            delivery_address TEXT,
            scheduled_pickup_datetime DATETIME DEFAULT NULL,
            scheduled_delivery_datetime DATETIME DEFAULT NULL,
            actual_pickup_datetime DATETIME DEFAULT NULL,
            actual_delivery_datetime DATETIME DEFAULT NULL,
            subtotal DECIMAL(12,2) DEFAULT 0.00,
            delivery_fee DECIMAL(10,2) DEFAULT 0.00,
            express_fee DECIMAL(10,2) DEFAULT 0.00,
            discount_amount DECIMAL(10,2) DEFAULT 0.00,
            promo_code VARCHAR(50) DEFAULT NULL,
            tax_amount DECIMAL(10,2) DEFAULT 0.00,
            total_amount DECIMAL(12,2) DEFAULT 0.00,
            payment_status VARCHAR(50) NOT NULL DEFAULT 'unpaid',
            is_express TINYINT(1) DEFAULT 0,
            special_instructions TEXT,
            internal_notes TEXT,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'lpd_order_status_log' => "CREATE TABLE {$p}lpd_order_status_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            from_status VARCHAR(80),
            to_status VARCHAR(80) NOT NULL,
            changed_by BIGINT UNSIGNED DEFAULT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) {$charset};",

        'lpd_drivers' => "CREATE TABLE {$p}lpd_drivers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(200),
            phone VARCHAR(50),
            vehicle_info VARCHAR(255),
            status VARCHAR(50) NOT NULL DEFAULT 'available',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'lpd_invoices' => "CREATE TABLE {$p}lpd_invoices (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            invoice_number VARCHAR(30) UNIQUE NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            subtotal DECIMAL(12,2) DEFAULT 0.00,
            delivery_fee DECIMAL(10,2) DEFAULT 0.00,
            express_fee DECIMAL(10,2) DEFAULT 0.00,
            discount_amount DECIMAL(10,2) DEFAULT 0.00,
            tax_amount DECIMAL(10,2) DEFAULT 0.00,
            total_amount DECIMAL(12,2) DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'unpaid',
            due_date DATE DEFAULT NULL,
            paid_at DATETIME DEFAULT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'lpd_payments' => "CREATE TABLE {$p}lpd_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            invoice_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            payment_method VARCHAR(100),
            transaction_reference VARCHAR(255),
            payment_date DATETIME DEFAULT NULL,
            recorded_by BIGINT UNSIGNED DEFAULT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'lpd_pricing_rules' => "CREATE TABLE {$p}lpd_pricing_rules (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            rule_name VARCHAR(150) NOT NULL,
            rule_type VARCHAR(50) NOT NULL,
            service_type VARCHAR(100),
            item_category VARCHAR(100),
            unit VARCHAR(50),
            rate DECIMAL(10,2) DEFAULT 0.00,
            min_value DECIMAL(10,2) DEFAULT 0.00,
            max_value DECIMAL(10,2) DEFAULT 0.00,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'lpd_promo_codes' => "CREATE TABLE {$p}lpd_promo_codes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            code VARCHAR(50) UNIQUE NOT NULL,
            discount_type VARCHAR(20) NOT NULL DEFAULT 'percent',
            discount_value DECIMAL(10,2) DEFAULT 0.00,
            min_order_amount DECIMAL(10,2) DEFAULT 0.00,
            usage_limit INT DEFAULT 0,
            usage_count INT DEFAULT 0,
            valid_from DATE DEFAULT NULL,
            valid_until DATE DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'lpd_email_templates' => "CREATE TABLE {$p}lpd_email_templates (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            event_key VARCHAR(100) UNIQUE NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body_html LONGTEXT NOT NULL,
            placeholders LONGTEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'lpd_email_log' => "CREATE TABLE {$p}lpd_email_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            order_id BIGINT UNSIGNED DEFAULT NULL,
            customer_id BIGINT UNSIGNED DEFAULT NULL,
            recipient_email VARCHAR(200) NOT NULL,
            template_id BIGINT UNSIGNED DEFAULT NULL,
            event_key VARCHAR(100),
            subject VARCHAR(255),
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            sent_at DATETIME DEFAULT NULL,
            retry_count INT DEFAULT 0,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",

        'lpd_delivery_proofs' => "CREATE TABLE {$p}lpd_delivery_proofs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            driver_id BIGINT UNSIGNED NOT NULL,
            proof_type VARCHAR(50) NOT NULL DEFAULT 'note',
            file_url TEXT,
            notes TEXT,
            submitted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset};",
    ];
}

function bntm_lpd_get_shortcodes() {
    return [
        'lpd_track_order'     => 'bntm_shortcode_lpd_track_order',
        'lpd_customer_portal' => 'bntm_shortcode_lpd_customer_portal',
        'lpd_dashboard'       => 'bntm_shortcode_lpd',
    ];
}

function bntm_lpd_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_lpd_get_tables();
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
    lpd_seed_email_templates();
    return count($tables);
}

// ============================================================
// AJAX ACTION HOOKS
// ============================================================

// Dashboard
add_action('wp_ajax_lpd_get_dashboard_stats',   'bntm_ajax_lpd_get_dashboard_stats');
add_action('wp_ajax_lpd_get_active_orders',      'bntm_ajax_lpd_get_active_orders');
add_action('wp_ajax_lpd_get_recent_payments',    'bntm_ajax_lpd_get_recent_payments');

// Orders
add_action('wp_ajax_lpd_create_order',           'bntm_ajax_lpd_create_order');
add_action('wp_ajax_lpd_get_order_detail',       'bntm_ajax_lpd_get_order_detail');
add_action('wp_ajax_lpd_update_order_status',    'bntm_ajax_lpd_update_order_status');
add_action('wp_ajax_lpd_assign_driver',          'bntm_ajax_lpd_assign_driver');
add_action('wp_ajax_lpd_add_order_note',         'bntm_ajax_lpd_add_order_note');
add_action('wp_ajax_lpd_get_order_audit_log',    'bntm_ajax_lpd_get_order_audit_log');
add_action('wp_ajax_lpd_search_customers',       'bntm_ajax_lpd_search_customers');

// Customers
add_action('wp_ajax_lpd_create_customer',        'bntm_ajax_lpd_create_customer');
add_action('wp_ajax_lpd_update_customer',        'bntm_ajax_lpd_update_customer');
add_action('wp_ajax_lpd_get_customer_profile',   'bntm_ajax_lpd_get_customer_profile');
add_action('wp_ajax_lpd_get_customer_orders',    'bntm_ajax_lpd_get_customer_orders');
add_action('wp_ajax_lpd_delete_customer',        'bntm_ajax_lpd_delete_customer');
add_action('wp_ajax_lpd_export_customers',       'bntm_ajax_lpd_export_customers');

// Drivers
add_action('wp_ajax_lpd_create_driver',          'bntm_ajax_lpd_create_driver');
add_action('wp_ajax_lpd_update_driver',          'bntm_ajax_lpd_update_driver');
add_action('wp_ajax_lpd_get_driver_workload',    'bntm_ajax_lpd_get_driver_workload');
add_action('wp_ajax_lpd_get_driver_orders',      'bntm_ajax_lpd_get_driver_orders');
add_action('wp_ajax_lpd_update_driver_status',   'bntm_ajax_lpd_update_driver_status');
add_action('wp_ajax_lpd_submit_delivery_proof',  'bntm_ajax_lpd_submit_delivery_proof');

// Billing
add_action('wp_ajax_lpd_create_invoice',         'bntm_ajax_lpd_create_invoice');
add_action('wp_ajax_lpd_update_invoice',         'bntm_ajax_lpd_update_invoice');
add_action('wp_ajax_lpd_record_payment',         'bntm_ajax_lpd_record_payment');
add_action('wp_ajax_lpd_get_billing_summary',    'bntm_ajax_lpd_get_billing_summary');
add_action('wp_ajax_lpd_save_pricing_rules',     'bntm_ajax_lpd_save_pricing_rules');
add_action('wp_ajax_lpd_get_pricing_rules',      'bntm_ajax_lpd_get_pricing_rules');
add_action('wp_ajax_lpd_apply_promo_code',       'bntm_ajax_lpd_apply_promo_code');
add_action('wp_ajax_lpd_export_billing',         'bntm_ajax_lpd_export_billing');
add_action('wp_ajax_lpd_save_promo_code',        'bntm_ajax_lpd_save_promo_code');
add_action('wp_ajax_lpd_delete_promo_code',      'bntm_ajax_lpd_delete_promo_code');

// Finance module export
add_action('wp_ajax_lpd_fn_export_payment',      'bntm_ajax_lpd_fn_export_payment');
add_action('wp_ajax_lpd_fn_revert_payment',      'bntm_ajax_lpd_fn_revert_payment');

// Emails
add_action('wp_ajax_lpd_save_email_template',    'bntm_ajax_lpd_save_email_template');
add_action('wp_ajax_lpd_get_email_template',     'bntm_ajax_lpd_get_email_template');
add_action('wp_ajax_lpd_get_email_log',          'bntm_ajax_lpd_get_email_log');
add_action('wp_ajax_lpd_resend_email',           'bntm_ajax_lpd_resend_email');
add_action('wp_ajax_lpd_get_email_stats',        'bntm_ajax_lpd_get_email_stats');

// Reports
add_action('wp_ajax_lpd_get_report_data',        'bntm_ajax_lpd_get_report_data');
add_action('wp_ajax_lpd_export_report',          'bntm_ajax_lpd_export_report');

// Settings
add_action('wp_ajax_lpd_save_settings',          'bntm_ajax_lpd_save_settings');
add_action('wp_ajax_lpd_get_settings',           'bntm_ajax_lpd_get_settings');
add_action('wp_ajax_lpd_save_role_permissions',  'bntm_ajax_lpd_save_role_permissions');
add_action('wp_ajax_lpd_save_payment_source',    'bntm_ajax_lpd_save_payment_source');
add_action('wp_ajax_lpd_add_payment_method',     'bntm_ajax_lpd_add_payment_method');
add_action('wp_ajax_lpd_remove_payment_method',  'bntm_ajax_lpd_remove_payment_method');

// Public-facing
add_action('wp_ajax_nopriv_lpd_track_order_lookup', 'bntm_ajax_lpd_track_order_lookup');
add_action('wp_ajax_lpd_track_order_lookup',         'bntm_ajax_lpd_track_order_lookup');
add_action('wp_ajax_lpd_submit_pickup_request',      'bntm_ajax_lpd_submit_pickup_request');
add_action('wp_ajax_nopriv_lpd_submit_pickup_request','bntm_ajax_lpd_submit_pickup_request');

// Payment gateway callback
if (function_exists('lpd_handle_payment_success')) {
    add_action('template_redirect', 'lpd_handle_payment_success', 5);
}

// ============================================================
// MAIN DASHBOARD SHORTCODE
// ============================================================

function bntm_shortcode_lpd() {
    // Diagnostic logging to help troubleshoot redirects when opening the module
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $dbg = array(
            'uri' => isset($_SERVER['REQUEST_URI']) ? esc_html($_SERVER['REQUEST_URI']) : '',
            'user' => is_user_logged_in() ? get_current_user_id() : 'guest',
            'can_manage' => function_exists('current_user_can') ? (current_user_can('manage_options') ? '1' : '0') : 'n/a',
            'bntm_universal_container_exists' => function_exists('bntm_universal_container') ? '1' : '0',
            'bntm_is_module_enabled_exists' => function_exists('bntm_is_module_enabled') ? '1' : '0',
            'get_params' => $_GET,
        );
        error_log('[LPD DEBUG] ' . print_r($dbg, true));
    }

    // Admin debug bypass: append ?bntm_debug_lpd=1 to the URL to view the module content directly
    if (isset($_GET['bntm_debug_lpd']) && $_GET['bntm_debug_lpd'] && function_exists('current_user_can') && current_user_can('manage_options')) {
        // allow viewing without login redirect for troubleshooting
        // fall through to render content even if not logged-in
        $force_debug = true;
    } else {
        $force_debug = false;
    }

    if (!is_user_logged_in() && !$force_debug) {
        return '<div class="bntm-notice">Please log in to access this dashboard.</div>';
    }

    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
    $nonce      = wp_create_nonce('lpd_nonce');

    ob_start();
    ?>
    <script>
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var lpd_nonce = '<?php echo $nonce; ?>';
    </script>

    <div class="lpd-container">
        <div class="bntm-tabs">
            <?php
            $tabs = [
                'overview'  => 'Overview',
                'orders'    => 'Orders',
                'customers' => 'Customers',
                'drivers'   => 'Drivers',
                'billing'   => 'Billing',
                'emails'    => 'Emails',
                'reports'   => 'Reports',
                'settings'  => 'Settings',
            ];
            foreach ($tabs as $slug => $label):
            ?>
            <a href="?tab=<?php echo $slug; ?>" class="bntm-tab <?php echo $active_tab === $slug ? 'active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="bntm-tab-content">
            <?php
            switch ($active_tab) {
                case 'overview':  echo lpd_overview_tab();  break;
                case 'orders':    echo lpd_orders_tab();    break;
                case 'customers': echo lpd_customers_tab(); break;
                case 'drivers':   echo lpd_drivers_tab();   break;
                case 'billing':   echo lpd_billing_tab();   break;
                case 'emails':    echo lpd_emails_tab();    break;
                case 'reports':   echo lpd_reports_tab();   break;
                case 'settings':  echo lpd_settings_tab();  break;
                default:          echo lpd_overview_tab();  break;
            }
            ?>
        </div>
    </div>

    <style>
    .lpd-container { width: 100%; }
    .lpd-modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.5); z-index: 99999;
        align-items: center; justify-content: center;
    }
    .lpd-modal-overlay.open { display: flex; }
    .lpd-modal {
        background: #fff; border-radius: 12px; padding: 28px;
        width: 90%; max-width: 680px; max-height: 90vh;
        overflow-y: auto; position: relative;
    }
    .lpd-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 20px; padding-bottom: 14px;
        border-bottom: 1px solid #e5e7eb;
    }
    .lpd-modal-header h3 { font-size: 18px; font-weight: 600; color: #111827; }
    .lpd-modal-close {
        background: none; border: none; font-size: 22px;
        cursor: pointer; color: #6b7280; line-height: 1;
    }
    .lpd-modal-close:hover { color: #111827; }
    .lpd-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .lpd-form-row.single { grid-template-columns: 1fr; }
    .lpd-status-badge {
        display: inline-flex; align-items: center; padding: 3px 10px;
        border-radius: 20px; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap;
    }
    .lpd-status-pending     { background: #fef3c7; color: #92400e; }
    .lpd-status-scheduled   { background: #dbeafe; color: #1e40af; }
    .lpd-status-assigned    { background: #ede9fe; color: #5b21b6; }
    .lpd-status-pickedup    { background: #d1fae5; color: #065f46; }
    .lpd-status-facility    { background: #e0f2fe; color: #0369a1; }
    .lpd-status-processing  { background: #f3f4f6; color: #374151; }
    .lpd-status-ready       { background: #dcfce7; color: #166534; }
    .lpd-status-outdelivery { background: #fce7f3; color: #9d174d; }
    .lpd-status-delivered   { background: #d1fae5; color: #065f46; }
    .lpd-status-completed   { background: #6ee7b7; color: #064e3b; }
    .lpd-timeline { list-style: none; padding: 0; position: relative; }
    .lpd-timeline::before {
        content: ''; position: absolute; left: 10px; top: 0; bottom: 0;
        width: 2px; background: #e5e7eb;
    }
    .lpd-timeline li {
        position: relative; padding: 6px 0 6px 32px; font-size: 13px;
        color: #374151;
    }
    .lpd-timeline li::before {
        content: ''; position: absolute; left: 4px; top: 12px;
        width: 12px; height: 12px; border-radius: 50%;
        background: var(--bntm-primary, #6366f1); border: 2px solid #fff;
        box-shadow: 0 0 0 2px var(--bntm-primary, #6366f1);
    }
    .lpd-timeline li .lpd-tl-time { font-size: 11px; color: #9ca3af; display: block; }
    .lpd-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .lpd-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
    .lpd-info-card {
        background: #f9fafb; border: 1px solid #e5e7eb;
        border-radius: 10px; padding: 14px 16px;
    }
    .lpd-info-card h4 { font-size: 12px; color: #6b7280; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
    .lpd-info-card p  { font-size: 14px; color: #111827; font-weight: 500; }
    .lpd-search-results {
        position: absolute; background: #fff; border: 1px solid #e5e7eb;
        border-radius: 8px; top: 100%; left: 0; right: 0;
        z-index: 100; max-height: 200px; overflow-y: auto;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .lpd-search-result-item {
        padding: 10px 14px; cursor: pointer; font-size: 13px; color: #374151;
        border-bottom: 1px solid #f3f4f6;
    }
    .lpd-search-result-item:hover { background: #f9fafb; }
    .lpd-search-result-item:last-child { border-bottom: none; }
    .lpd-proof-section { border: 2px dashed #d1d5db; border-radius: 10px; padding: 20px; text-align: center; }
    @media (max-width: 640px) {
        .lpd-form-row, .lpd-grid-2, .lpd-grid-3 { grid-template-columns: 1fr; }
    }
    </style>

    <script>
    (function() {
        function lpdOpenModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('open');
        }
        function lpdCloseModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.remove('open');
        }
        window.lpdOpenModal  = lpdOpenModal;
        window.lpdCloseModal = lpdCloseModal;

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('lpd-modal-overlay')) {
                e.target.classList.remove('open');
            }
        });

        function lpdToast(msg, type) {
            type = type || 'success';
            var t = document.createElement('div');
            t.textContent = msg;
            t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:999999;padding:12px 20px;border-radius:8px;font-size:13px;font-weight:500;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            t.style.background = type === 'success' ? '#059669' : '#dc2626';
            document.body.appendChild(t);
            setTimeout(function() { t.remove(); }, 3500);
        }
        window.lpdToast = lpdToast;

        function lpdAjax(action, data, cb) {
            var fd = new FormData();
            fd.append('action', action);
            fd.append('nonce', lpd_nonce);
            if (data) Object.keys(data).forEach(function(k) { fd.append(k, data[k]); });
            fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(json) { cb(null, json); })
                .catch(function(err) { cb(err, null); });
        }
        window.lpdAjax = lpdAjax;
    })();
    </script>
    <?php
    $content = ob_get_clean();
    if (!empty($force_debug)) {
        // In debug bypass mode, return raw content to avoid wrapper redirects
        echo '<div style="padding:10px;border:2px dashed #f59e0b;margin-bottom:12px;font-size:13px;color:#92400e;">LPD DEBUG MODE: Wrapper bypassed</div>';
        return $content;
    }
    return bntm_universal_container('Laundry Pickup & Delivery', $content);
}

// ============================================================
// TAB: OVERVIEW
// ============================================================

function lpd_overview_tab() {
    global $wpdb;
    $p = $wpdb->prefix;

    $active    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}lpd_orders WHERE status NOT IN ('Completed','Delivered')");
    $pickups   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}lpd_orders WHERE status IN ('Pending Request','Pickup Scheduled','Driver Assigned')");
    $deliveries= (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}lpd_orders WHERE status IN ('Ready for Delivery','Out for Delivery')");
    $today_done= (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}lpd_orders WHERE status='Completed' AND DATE(updated_at)=CURDATE()");
    $today_rev = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM {$p}lpd_payments WHERE DATE(payment_date)=CURDATE()");
    $emails_sent=(int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}lpd_email_log WHERE status='sent' AND DATE(sent_at)=CURDATE()");

    $recent_orders = $wpdb->get_results("
        SELECT o.*, CONCAT(c.first_name,' ',c.last_name) AS customer_name
        FROM {$p}lpd_orders o
        LEFT JOIN {$p}lpd_customers c ON c.id=o.customer_id
        WHERE o.status NOT IN ('Completed')
        ORDER BY o.updated_at DESC LIMIT 8
    ");

    ob_start();
    ?>
    <div class="bntm-stats-row">
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,var(--bntm-primary),var(--bntm-primary-hover));">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div class="stat-content">
                <h3>Active Orders</h3>
                <p class="stat-number"><?php echo $active; ?></p>
                <span class="stat-label">in progress</span>
            </div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="stat-content">
                <h3>Pending Pickups</h3>
                <p class="stat-number"><?php echo $pickups; ?></p>
                <span class="stat-label">awaiting pickup</span>
            </div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <div class="stat-content">
                <h3>Pending Deliveries</h3>
                <p class="stat-number"><?php echo $deliveries; ?></p>
                <span class="stat-label">ready / out for delivery</span>
            </div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content">
                <h3>Completed Today</h3>
                <p class="stat-number"><?php echo $today_done; ?></p>
                <span class="stat-label">finished orders</span>
            </div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#ec4899,#db2777);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content">
                <h3>Revenue Today</h3>
                <p class="stat-number">&#8369;<?php echo number_format($today_rev, 2); ?></p>
                <span class="stat-label">collected payments</span>
            </div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#06b6d4,#0891b2);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="stat-content">
                <h3>Emails Sent Today</h3>
                <p class="stat-number"><?php echo $emails_sent; ?></p>
                <span class="stat-label">transactional emails</span>
            </div>
        </div>
    </div>

    <div class="bntm-form-section" style="margin-top:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3>Active Orders</h3>
            <a href="?tab=orders" class="bntm-btn-primary bntm-btn-small">View All Orders</a>
        </div>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead>
                    <tr>
                        <th>Reference</th><th>Customer</th><th>Service</th>
                        <th>Status</th><th>Total</th><th>Pickup Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#6b7280;">No active orders found.</td></tr>
                <?php else: foreach ($recent_orders as $ord): ?>
                    <tr>
                        <td><strong><?php echo esc_html($ord->reference_number); ?></strong></td>
                        <td><?php echo esc_html($ord->customer_name); ?></td>
                        <td><?php echo esc_html($ord->service_type); ?></td>
                        <td><span class="lpd-status-badge <?php echo lpd_status_class($ord->status); ?>"><?php echo esc_html($ord->status); ?></span></td>
                        <td>&#8369;<?php echo number_format($ord->total_amount, 2); ?></td>
                        <td><?php echo $ord->scheduled_pickup_datetime ? date('M d, Y H:i', strtotime($ord->scheduled_pickup_datetime)) : '—'; ?></td>
                        <td>
                            <button class="bntm-btn-primary bntm-btn-small" onclick="lpdViewOrder(<?php echo $ord->id; ?>)">View</button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bntm-form-section" style="margin-top:24px;">
        <h3>Frontend Pages</h3>
        <p style="color:#6b7280;margin-bottom:16px;">Public-facing pages for this module.</p>
        <div class="bntm-frontend-pages-grid">
            <div class="bntm-page-card">
                <div class="bntm-page-card-header">
                    <div class="bntm-page-card-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </div>
                    <span class="bntm-page-audience-badge bntm-badge-public">Public</span>
                </div>
                <div class="bntm-page-card-body">
                    <h4>Track My Order</h4>
                    <p>Customers enter reference number to view order status.</p>
                </div>
                <div class="bntm-page-card-footer">
                    <?php $pg = get_page_by_path('track-my-order'); $url = $pg ? get_permalink($pg->ID) : '#'; ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" class="bntm-btn-primary bntm-btn-small">Open Page</a>
                    <button class="bntm-btn-secondary bntm-btn-small copy-page-url" data-url="<?php echo esc_url($url); ?>">Copy URL</button>
                </div>
            </div>
            <div class="bntm-page-card">
                <div class="bntm-page-card-header">
                    <div class="bntm-page-card-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </div>
                    <span class="bntm-page-audience-badge bntm-badge-loggedin">Members</span>
                </div>
                <div class="bntm-page-card-body">
                    <h4>Customer Portal</h4>
                    <p>Logged-in customers view orders and request pickups.</p>
                </div>
                <div class="bntm-page-card-footer">
                    <?php $pg2 = get_page_by_path('customer-portal'); $url2 = $pg2 ? get_permalink($pg2->ID) : '#'; ?>
                    <a href="<?php echo esc_url($url2); ?>" target="_blank" class="bntm-btn-primary bntm-btn-small">Open Page</a>
                    <button class="bntm-btn-secondary bntm-btn-small copy-page-url" data-url="<?php echo esc_url($url2); ?>">Copy URL</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function() {
        document.querySelectorAll('.copy-page-url').forEach(function(btn) {
            btn.addEventListener('click', function() {
                navigator.clipboard.writeText(this.dataset.url).then(function() {
                    lpdToast('URL copied!');
                });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: ORDERS
// ============================================================

function lpd_orders_tab() {
    global $wpdb;
    $p = $wpdb->prefix;

    $status_filter  = isset($_GET['os']) ? sanitize_text_field($_GET['os']) : '';
    $search         = isset($_GET['osrch']) ? sanitize_text_field($_GET['osrch']) : '';

    $where = "WHERE 1=1";
    $args  = [];
    if ($status_filter) { $where .= $wpdb->prepare(" AND o.status=%s", $status_filter); }
    if ($search)        { $where .= $wpdb->prepare(" AND (o.reference_number LIKE %s OR c.first_name LIKE %s OR c.last_name LIKE %s)", "%{$search}%", "%{$search}%", "%{$search}%"); }

    $orders = $wpdb->get_results("
        SELECT o.*, CONCAT(c.first_name,' ',c.last_name) AS customer_name,
               CONCAT(d.first_name,' ',d.last_name) AS driver_name
        FROM {$p}lpd_orders o
        LEFT JOIN {$p}lpd_customers c ON c.id=o.customer_id
        LEFT JOIN {$p}lpd_drivers d ON d.id=o.driver_id
        {$where} ORDER BY o.created_at DESC LIMIT 100
    ");

    $all_statuses = lpd_all_statuses();
    $drivers      = $wpdb->get_results("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM {$p}lpd_drivers WHERE status='available' ORDER BY first_name");
    $customers    = $wpdb->get_results("SELECT id, CONCAT(first_name,' ',last_name) AS name, email, phone, pickup_address, delivery_address FROM {$p}lpd_customers WHERE status='active' ORDER BY first_name LIMIT 200");
    $nonce        = wp_create_nonce('lpd_nonce');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
            <h3>Orders</h3>
            <button class="bntm-btn-primary" onclick="lpdOpenModal('lpd-order-modal')">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-3px;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Order
            </button>
        </div>

        <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
            <input type="hidden" name="tab" value="orders">
            <input type="text" name="osrch" value="<?php echo esc_attr($search); ?>" placeholder="Search by ref or customer..." style="flex:1;min-width:180px;">
            <select name="os" style="min-width:180px;">
                <option value="">All Statuses</option>
                <?php foreach ($all_statuses as $s): ?>
                <option value="<?php echo esc_attr($s); ?>" <?php selected($status_filter,$s); ?>><?php echo esc_html($s); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bntm-btn-primary">Filter</button>
            <a href="?tab=orders" class="bntm-btn-secondary">Clear</a>
        </form>

        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead>
                    <tr><th>Reference</th><th>Customer</th><th>Service</th><th>Weight</th><th>Status</th><th>Driver</th><th>Total</th><th>Payment</th><th>Created</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="10" style="text-align:center;color:#6b7280;">No orders found.</td></tr>
                <?php else: foreach ($orders as $ord): ?>
                    <tr>
                        <td><strong><?php echo esc_html($ord->reference_number); ?></strong></td>
                        <td><?php echo esc_html($ord->customer_name); ?></td>
                        <td><?php echo esc_html($ord->service_type); ?></td>
                        <td><?php echo $ord->weight_kg > 0 ? number_format($ord->weight_kg,2).'kg' : '—'; ?></td>
                        <td><span class="lpd-status-badge <?php echo lpd_status_class($ord->status); ?>"><?php echo esc_html($ord->status); ?></span></td>
                        <td><?php echo $ord->driver_name ? esc_html($ord->driver_name) : '<em style="color:#9ca3af;">Unassigned</em>'; ?></td>
                        <td>&#8369;<?php echo number_format($ord->total_amount,2); ?></td>
                        <td><span class="lpd-status-badge <?php echo $ord->payment_status==='paid'?'lpd-status-completed':'lpd-status-pending'; ?>"><?php echo esc_html(ucfirst($ord->payment_status)); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($ord->created_at)); ?></td>
                        <td style="white-space:nowrap;">
                            <button class="bntm-btn-primary bntm-btn-small" onclick="lpdViewOrder(<?php echo $ord->id; ?>)">View</button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Order Modal -->
    <div class="lpd-modal-overlay" id="lpd-order-modal">
        <div class="lpd-modal" style="max-width:750px;">
            <div class="lpd-modal-header">
                <h3>Create New Order</h3>
                <button class="lpd-modal-close" onclick="lpdCloseModal('lpd-order-modal')">&times;</button>
            </div>
            <div>
                <div class="bntm-form-group" style="position:relative;">
                    <label>Customer *</label>
                    <input type="text" id="lpd-cust-search" placeholder="Search customer name or email..." autocomplete="off">
                    <div id="lpd-cust-results" class="lpd-search-results" style="display:none;"></div>
                    <input type="hidden" id="lpd-cust-id">
                </div>
                <div id="lpd-new-cust-section" style="display:none;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:12px;">
                    <p style="font-size:12px;color:#6b7280;margin-bottom:10px;">New customer details:</p>
                    <div class="lpd-form-row">
                        <div class="bntm-form-group"><label>First Name *</label><input type="text" id="lpd-new-fname"></div>
                        <div class="bntm-form-group"><label>Last Name *</label><input type="text" id="lpd-new-lname"></div>
                    </div>
                    <div class="lpd-form-row">
                        <div class="bntm-form-group"><label>Email *</label><input type="email" id="lpd-new-email"></div>
                        <div class="bntm-form-group"><label>Phone</label><input type="text" id="lpd-new-phone"></div>
                    </div>
                </div>
                <div class="lpd-form-row">
                    <div class="bntm-form-group">
                        <label>Service Type *</label>
                        <select id="lpd-ord-service">
                            <option value="">Select service...</option>
                            <option value="Wash &amp; Fold">Wash &amp; Fold</option>
                            <option value="Wash &amp; Iron">Wash &amp; Iron</option>
                            <option value="Dry Clean">Dry Clean</option>
                            <option value="Press Only">Press Only</option>
                            <option value="Wash &amp; Dry Clean">Wash &amp; Dry Clean</option>
                        </select>
                    </div>
                    <div class="bntm-form-group">
                        <label>Is Express?</label>
                        <select id="lpd-ord-express"><option value="0">No</option><option value="1">Yes (+surcharge)</option></select>
                    </div>
                </div>
                <div class="lpd-form-row">
                    <div class="bntm-form-group">
                        <label>Estimated Weight (kg)</label>
                        <input type="number" id="lpd-ord-weight" step="0.1" min="0" placeholder="0.00">
                    </div>
                    <div class="bntm-form-group">
                        <label>Item Count</label>
                        <input type="number" id="lpd-ord-items" min="0" placeholder="0">
                    </div>
                </div>
                <div class="lpd-form-row">
                    <div class="bntm-form-group">
                        <label>Scheduled Pickup</label>
                        <input type="datetime-local" id="lpd-ord-pickup">
                    </div>
                    <div class="bntm-form-group">
                        <label>Scheduled Delivery</label>
                        <input type="datetime-local" id="lpd-ord-delivery">
                    </div>
                </div>
                <div class="bntm-form-group">
                    <label>Pickup Address *</label>
                    <input type="text" id="lpd-ord-pickup-addr" placeholder="Pickup address">
                </div>
                <div class="bntm-form-group">
                    <label>Delivery Address</label>
                    <input type="text" id="lpd-ord-delivery-addr" placeholder="Delivery address (leave blank if same as pickup)">
                </div>
                <div class="lpd-form-row">
                    <div class="bntm-form-group">
                        <label>Promo Code</label>
                        <input type="text" id="lpd-ord-promo" placeholder="Enter promo code">
                    </div>
                    <div class="bntm-form-group">
                        <label>Assign Driver (optional)</label>
                        <select id="lpd-ord-driver">
                            <option value="">Unassigned</option>
                            <?php foreach ($drivers as $d): ?>
                            <option value="<?php echo $d->id; ?>"><?php echo esc_html($d->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="bntm-form-group">
                    <label>Special Instructions</label>
                    <textarea id="lpd-ord-instructions" rows="3" placeholder="Any special handling notes..."></textarea>
                </div>
                <div id="lpd-create-order-msg"></div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                    <button class="bntm-btn-secondary" onclick="lpdCloseModal('lpd-order-modal')">Cancel</button>
                    <button class="bntm-btn-primary" id="lpd-create-order-btn" onclick="lpdCreateOrder()">Create Order</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div class="lpd-modal-overlay" id="lpd-view-order-modal">
        <div class="lpd-modal" style="max-width:800px;">
            <div class="lpd-modal-header">
                <h3 id="lpd-vo-title">Order Details</h3>
                <button class="lpd-modal-close" onclick="lpdCloseModal('lpd-view-order-modal')">&times;</button>
            </div>
            <div id="lpd-vo-body">Loading...</div>
        </div>
    </div>

    <script>
    (function() {
        var custSearchTimer;
        document.getElementById('lpd-cust-search').addEventListener('input', function() {
            var q = this.value.trim();
            clearTimeout(custSearchTimer);
            if (q.length < 2) { document.getElementById('lpd-cust-results').style.display='none'; return; }
            custSearchTimer = setTimeout(function() {
                lpdAjax('lpd_search_customers', {query: q}, function(err, json) {
                    if (!json || !json.success) return;
                    var res = document.getElementById('lpd-cust-results');
                    if (!json.data.customers.length) {
                        res.innerHTML = '<div class="lpd-search-result-item" onclick="lpdShowNewCust()">+ Add as new customer</div>';
                    } else {
                        var html = json.data.customers.map(function(c) {
                            return '<div class="lpd-search-result-item" onclick="lpdSelectCust('+c.id+',\''+c.name+'\',\''+c.pickup_address+'\',\''+c.delivery_address+'\')">'+
                                '<strong>'+c.name+'</strong> &mdash; '+c.email+'</div>';
                        }).join('') + '<div class="lpd-search-result-item" onclick="lpdShowNewCust()">+ Add as new customer</div>';
                        res.innerHTML = html;
                    }
                    res.style.display = 'block';
                });
            }, 350);
        });

        window.lpdSelectCust = function(id, name, pickup, delivery) {
            document.getElementById('lpd-cust-id').value = id;
            document.getElementById('lpd-cust-search').value = name;
            document.getElementById('lpd-cust-results').style.display = 'none';
            document.getElementById('lpd-new-cust-section').style.display = 'none';
            if (pickup) document.getElementById('lpd-ord-pickup-addr').value = pickup;
            if (delivery) document.getElementById('lpd-ord-delivery-addr').value = delivery;
        };

        window.lpdShowNewCust = function() {
            document.getElementById('lpd-cust-id').value = '';
            document.getElementById('lpd-cust-results').style.display = 'none';
            document.getElementById('lpd-new-cust-section').style.display = 'block';
        };

        window.lpdCreateOrder = function() {
            var btn = document.getElementById('lpd-create-order-btn');
            var custId = document.getElementById('lpd-cust-id').value;
            var data = {
                customer_id: custId,
                new_fname: document.getElementById('lpd-new-fname').value,
                new_lname: document.getElementById('lpd-new-lname').value,
                new_email: document.getElementById('lpd-new-email').value,
                new_phone: document.getElementById('lpd-new-phone').value,
                service_type: document.getElementById('lpd-ord-service').value,
                is_express: document.getElementById('lpd-ord-express').value,
                weight_kg: document.getElementById('lpd-ord-weight').value,
                item_count: document.getElementById('lpd-ord-items').value,
                scheduled_pickup: document.getElementById('lpd-ord-pickup').value,
                scheduled_delivery: document.getElementById('lpd-ord-delivery').value,
                pickup_address: document.getElementById('lpd-ord-pickup-addr').value,
                delivery_address: document.getElementById('lpd-ord-delivery-addr').value,
                promo_code: document.getElementById('lpd-ord-promo').value,
                driver_id: document.getElementById('lpd-ord-driver').value,
                special_instructions: document.getElementById('lpd-ord-instructions').value,
            };
            btn.disabled = true; btn.textContent = 'Creating...';
            lpdAjax('lpd_create_order', data, function(err, json) {
                var msg = document.getElementById('lpd-create-order-msg');
                if (json && json.success) {
                    msg.innerHTML = '<div class="bntm-notice bntm-notice-success">Order '+json.data.reference+' created!</div>';
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    msg.innerHTML = '<div class="bntm-notice bntm-notice-error">'+(json ? json.data.message : 'Error') +'</div>';
                    btn.disabled = false; btn.textContent = 'Create Order';
                }
            });
        };

        window.lpdViewOrder = function(ordId) {
            document.getElementById('lpd-vo-body').innerHTML = '<p style="text-align:center;padding:30px;color:#6b7280;">Loading...</p>';
            lpdOpenModal('lpd-view-order-modal');
            lpdAjax('lpd_get_order_detail', {order_id: ordId}, function(err, json) {
                if (!json || !json.success) {
                    document.getElementById('lpd-vo-body').innerHTML = '<p style="color:#dc2626;">Failed to load order.</p>';
                    return;
                }
                var o = json.data.order;
                var log = json.data.log;
                var emails = json.data.emails;
                var proofs = json.data.proofs;
                document.getElementById('lpd-vo-title').textContent = 'Order — ' + o.reference_number;

                var statusOpts = <?php echo json_encode($all_statuses); ?>;
                var statusHtml = statusOpts.map(function(s) {
                    return '<option value="'+s+'"'+(s===o.status?' selected':'')+'>'+s+'</option>';
                }).join('');

                var logHtml = '<ul class="lpd-timeline">';
                if (log && log.length) {
                    log.forEach(function(l) {
                        logHtml += '<li><strong>'+l.to_status+'</strong>'+(l.from_status?' <span style="color:#9ca3af;">(from '+l.from_status+')</span>':'')+
                            (l.notes?'<br><em style="font-size:12px;color:#6b7280;">'+l.notes+'</em>':'')+
                            '<span class="lpd-tl-time">'+l.created_at+(l.changed_by?' &mdash; User #'+l.changed_by:'')+'</span></li>';
                    });
                } else { logHtml += '<li style="color:#9ca3af;">No status history yet.</li>'; }
                logHtml += '</ul>';

                var emailHtml = '<table class="bntm-table" style="font-size:12px;"><thead><tr><th>Event</th><th>Recipient</th><th>Status</th><th>Sent</th><th>Action</th></tr></thead><tbody>';
                if (emails && emails.length) {
                    emails.forEach(function(e) {
                        emailHtml += '<tr><td>'+e.event_key+'</td><td>'+e.recipient_email+'</td><td>'+e.status+'</td><td>'+(e.sent_at||'—')+'</td>'+
                            '<td><button class="bntm-btn-secondary bntm-btn-small" onclick="lpdResendEmail('+e.id+')">Resend</button></td></tr>';
                    });
                } else { emailHtml += '<tr><td colspan="5" style="text-align:center;color:#9ca3af;">No emails logged.</td></tr>'; }
                emailHtml += '</tbody></table>';

                var proofHtml = '';
                if (proofs && proofs.length) {
                    proofs.forEach(function(pr) {
                        proofHtml += '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin-bottom:8px;font-size:13px;">';
                        proofHtml += '<strong>'+pr.proof_type+'</strong> &mdash; '+pr.submitted_at+'<br>';
                        if (pr.notes) proofHtml += '<span style="color:#6b7280;">'+pr.notes+'</span><br>';
                        if (pr.file_url) proofHtml += '<a href="'+pr.file_url+'" target="_blank" class="bntm-btn-secondary bntm-btn-small" style="margin-top:6px;display:inline-block;">View File</a>';
                        proofHtml += '</div>';
                    });
                } else { proofHtml = '<p style="color:#9ca3af;font-size:13px;">No delivery proofs submitted.</p>'; }

                document.getElementById('lpd-vo-body').innerHTML =
                    '<div class="lpd-grid-2" style="margin-bottom:16px;">' +
                    '<div class="lpd-info-card"><h4>Customer</h4><p>'+o.customer_name+'</p></div>' +
                    '<div class="lpd-info-card"><h4>Service</h4><p>'+o.service_type+'</p></div>' +
                    '<div class="lpd-info-card"><h4>Driver</h4><p>'+(o.driver_name||'Unassigned')+'</p></div>' +
                    '<div class="lpd-info-card"><h4>Payment</h4><p>'+o.payment_status+' — &#8369;'+parseFloat(o.total_amount).toFixed(2)+'</p></div>' +
                    '<div class="lpd-info-card"><h4>Pickup Address</h4><p>'+(o.pickup_address||'—')+'</p></div>' +
                    '<div class="lpd-info-card"><h4>Delivery Address</h4><p>'+(o.delivery_address||'—')+'</p></div>' +
                    '</div>' +

                    '<div class="bntm-form-section" style="margin-bottom:16px;">' +
                    '<h3 style="font-size:14px;margin-bottom:10px;">Update Status</h3>' +
                    '<div style="display:flex;gap:10px;flex-wrap:wrap;">' +
                    '<select id="lpd-vo-status" style="flex:1;">'+statusHtml+'</select>' +
                    '<input type="text" id="lpd-vo-status-note" placeholder="Optional note..." style="flex:2;">' +
                    '<button class="bntm-btn-primary" onclick="lpdUpdateStatus('+o.id+')">Update</button>' +
                    '</div>' +
                    '</div>' +

                    '<div class="bntm-form-section" style="margin-bottom:16px;">' +
                    '<h3 style="font-size:14px;margin-bottom:10px;">Status History</h3>' + logHtml + '</div>' +

                    '<div class="bntm-form-section" style="margin-bottom:16px;">' +
                    '<h3 style="font-size:14px;margin-bottom:10px;">Email Activity</h3>' + emailHtml + '</div>' +

                    '<div class="bntm-form-section">' +
                    '<h3 style="font-size:14px;margin-bottom:10px;">Delivery Proofs</h3>' + proofHtml + '</div>';
            });
        };

        window.lpdUpdateStatus = function(ordId) {
            var status = document.getElementById('lpd-vo-status').value;
            var note   = document.getElementById('lpd-vo-status-note').value;
            lpdAjax('lpd_update_order_status', {order_id: ordId, status: status, notes: note}, function(err, json) {
                if (json && json.success) {
                    lpdToast('Status updated to: ' + status);
                    lpdCloseModal('lpd-view-order-modal');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    lpdToast((json ? json.data.message : 'Error updating status'), 'error');
                }
            });
        };

        window.lpdResendEmail = function(logId) {
            lpdAjax('lpd_resend_email', {log_id: logId}, function(err, json) {
                lpdToast(json && json.success ? 'Email queued for resend.' : 'Resend failed.', json && json.success ? 'success' : 'error');
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: CUSTOMERS
// ============================================================

function lpd_customers_tab() {
    global $wpdb;
    $p = $wpdb->prefix;
    $search = isset($_GET['csrch']) ? sanitize_text_field($_GET['csrch']) : '';
    $where  = $search ? $wpdb->prepare("WHERE (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)", "%{$search}%","%{$search}%","%{$search}%","%{$search}%") : '';
    $customers = $wpdb->get_results("SELECT c.*, (SELECT COUNT(*) FROM {$p}lpd_orders WHERE customer_id=c.id) AS total_orders,
        (SELECT MAX(created_at) FROM {$p}lpd_orders WHERE customer_id=c.id) AS last_order FROM {$p}lpd_customers c {$where} ORDER BY c.created_at DESC LIMIT 200");
    ob_start();
    ?>
    <div class="bntm-form-section">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
            <h3>Customers</h3>
            <div style="display:flex;gap:8px;">
                <button class="bntm-btn-secondary" onclick="lpdExportCustomers()">Export CSV</button>
                <button class="bntm-btn-primary" onclick="lpdOpenModal('lpd-cust-modal')">Add Customer</button>
            </div>
        </div>
        <form method="get" style="display:flex;gap:10px;margin-bottom:16px;">
            <input type="hidden" name="tab" value="customers">
            <input type="text" name="csrch" value="<?php echo esc_attr($search); ?>" placeholder="Search customers..." style="flex:1;">
            <button type="submit" class="bntm-btn-primary">Search</button>
            <a href="?tab=customers" class="bntm-btn-secondary">Clear</a>
        </form>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Total Orders</th><th>Last Order</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#6b7280;">No customers found.</td></tr>
                <?php else: foreach ($customers as $c): ?>
                    <tr>
                        <td><strong><?php echo esc_html($c->first_name.' '.$c->last_name); ?></strong></td>
                        <td><?php echo esc_html($c->email); ?></td>
                        <td><?php echo esc_html($c->phone ?: '—'); ?></td>
                        <td><?php echo (int)$c->total_orders; ?></td>
                        <td><?php echo $c->last_order ? date('M d, Y', strtotime($c->last_order)) : '—'; ?></td>
                        <td><span class="lpd-status-badge <?php echo $c->status==='active'?'lpd-status-completed':'lpd-status-pending'; ?>"><?php echo esc_html(ucfirst($c->status)); ?></span></td>
                        <td style="white-space:nowrap;">
                            <button class="bntm-btn-primary bntm-btn-small" onclick="lpdEditCustomer(<?php echo $c->id; ?>)">Edit</button>
                            <button class="bntm-btn-secondary bntm-btn-small" onclick="lpdViewCustomerOrders(<?php echo $c->id; ?>, '<?php echo esc_js($c->first_name.' '.$c->last_name); ?>')">Orders</button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Customer Modal -->
    <div class="lpd-modal-overlay" id="lpd-cust-modal">
        <div class="lpd-modal">
            <div class="lpd-modal-header">
                <h3 id="lpd-cust-modal-title">Add Customer</h3>
                <button class="lpd-modal-close" onclick="lpdCloseModal('lpd-cust-modal')">&times;</button>
            </div>
            <input type="hidden" id="lpd-cust-edit-id">
            <div class="lpd-form-row">
                <div class="bntm-form-group"><label>First Name *</label><input type="text" id="lpd-cf-fname"></div>
                <div class="bntm-form-group"><label>Last Name *</label><input type="text" id="lpd-cf-lname"></div>
            </div>
            <div class="lpd-form-row">
                <div class="bntm-form-group"><label>Email *</label><input type="email" id="lpd-cf-email"></div>
                <div class="bntm-form-group"><label>Phone</label><input type="text" id="lpd-cf-phone"></div>
            </div>
            <div class="bntm-form-group"><label>Default Pickup Address</label><input type="text" id="lpd-cf-pickup"></div>
            <div class="bntm-form-group"><label>Default Delivery Address</label><input type="text" id="lpd-cf-delivery"></div>
            <div class="bntm-form-group"><label>Service Preferences</label><input type="text" id="lpd-cf-prefs" placeholder="e.g. Wash & Fold, no bleach"></div>
            <div class="bntm-form-group"><label>Notes</label><textarea id="lpd-cf-notes" rows="2"></textarea></div>
            <div id="lpd-cust-form-msg"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                <button class="bntm-btn-secondary" onclick="lpdCloseModal('lpd-cust-modal')">Cancel</button>
                <button class="bntm-btn-primary" id="lpd-cust-save-btn" onclick="lpdSaveCustomer()">Save Customer</button>
            </div>
        </div>
    </div>

    <!-- Customer Orders Modal -->
    <div class="lpd-modal-overlay" id="lpd-cust-orders-modal">
        <div class="lpd-modal" style="max-width:750px;">
            <div class="lpd-modal-header">
                <h3 id="lpd-co-title">Customer Orders</h3>
                <button class="lpd-modal-close" onclick="lpdCloseModal('lpd-cust-orders-modal')">&times;</button>
            </div>
            <div id="lpd-co-body"><p style="color:#6b7280;text-align:center;padding:20px;">Loading...</p></div>
        </div>
    </div>

    <script>
    (function() {
        window.lpdSaveCustomer = function() {
            var id  = document.getElementById('lpd-cust-edit-id').value;
            var btn = document.getElementById('lpd-cust-save-btn');
            var data = {
                customer_id: id,
                first_name: document.getElementById('lpd-cf-fname').value,
                last_name:  document.getElementById('lpd-cf-lname').value,
                email:      document.getElementById('lpd-cf-email').value,
                phone:      document.getElementById('lpd-cf-phone').value,
                pickup_address: document.getElementById('lpd-cf-pickup').value,
                delivery_address: document.getElementById('lpd-cf-delivery').value,
                service_preferences: document.getElementById('lpd-cf-prefs').value,
                notes: document.getElementById('lpd-cf-notes').value,
            };
            var action = id ? 'lpd_update_customer' : 'lpd_create_customer';
            btn.disabled = true; btn.textContent = 'Saving...';
            lpdAjax(action, data, function(err, json) {
                var msg = document.getElementById('lpd-cust-form-msg');
                if (json && json.success) {
                    lpdToast('Customer saved!');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    msg.innerHTML = '<div class="bntm-notice bntm-notice-error">'+(json?json.data.message:'Error')+'</div>';
                    btn.disabled = false; btn.textContent = 'Save Customer';
                }
            });
        };

        window.lpdEditCustomer = function(id) {
            lpdAjax('lpd_get_customer_profile', {customer_id: id}, function(err, json) {
                if (!json || !json.success) return;
                var c = json.data.customer;
                document.getElementById('lpd-cust-modal-title').textContent = 'Edit Customer';
                document.getElementById('lpd-cust-edit-id').value = c.id;
                document.getElementById('lpd-cf-fname').value    = c.first_name || '';
                document.getElementById('lpd-cf-lname').value    = c.last_name  || '';
                document.getElementById('lpd-cf-email').value    = c.email      || '';
                document.getElementById('lpd-cf-phone').value    = c.phone      || '';
                document.getElementById('lpd-cf-pickup').value   = c.pickup_address || '';
                document.getElementById('lpd-cf-delivery').value = c.delivery_address || '';
                document.getElementById('lpd-cf-prefs').value    = c.service_preferences || '';
                document.getElementById('lpd-cf-notes').value    = c.notes || '';
                lpdOpenModal('lpd-cust-modal');
            });
        };

        window.lpdViewCustomerOrders = function(id, name) {
            document.getElementById('lpd-co-title').textContent = name + ' — Orders';
            lpdOpenModal('lpd-cust-orders-modal');
            lpdAjax('lpd_get_customer_orders', {customer_id: id}, function(err, json) {
                var body = document.getElementById('lpd-co-body');
                if (!json || !json.success || !json.data.orders.length) {
                    body.innerHTML = '<p style="color:#9ca3af;text-align:center;padding:20px;">No orders found.</p>'; return;
                }
                var html = '<div class="bntm-table-wrapper"><table class="bntm-table"><thead><tr><th>Ref</th><th>Service</th><th>Status</th><th>Total</th><th>Date</th></tr></thead><tbody>';
                json.data.orders.forEach(function(o) {
                    html += '<tr><td><strong>'+o.reference_number+'</strong></td><td>'+o.service_type+'</td><td>'+o.status+'</td><td>&#8369;'+parseFloat(o.total_amount).toFixed(2)+'</td><td>'+o.created_at.substring(0,10)+'</td></tr>';
                });
                html += '</tbody></table></div>';
                body.innerHTML = html;
            });
        };

        window.lpdExportCustomers = function() {
            lpdAjax('lpd_export_customers', {}, function(err, json) {
                if (!json || !json.success) { lpdToast('Export failed', 'error'); return; }
                var a = document.createElement('a');
                a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(json.data.csv);
                a.download = 'customers.csv'; a.click();
            });
        };

        document.getElementById('lpd-cust-modal').addEventListener('click', function(e) {
            if (e.target === this) lpdCloseModal('lpd-cust-modal');
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: DRIVERS
// ============================================================

function lpd_drivers_tab() {
    global $wpdb;
    $p       = $wpdb->prefix;
    $drivers = $wpdb->get_results("
        SELECT d.*,
            (SELECT COUNT(*) FROM {$p}lpd_orders WHERE driver_id=d.id AND status NOT IN ('Completed','Delivered')) AS active_orders,
            (SELECT COUNT(*) FROM {$p}lpd_orders WHERE driver_id=d.id AND status='Completed' AND DATE(updated_at)=CURDATE()) AS done_today
        FROM {$p}lpd_drivers d ORDER BY d.first_name
    ");
    ob_start();
    ?>
    <div class="bntm-form-section">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3>Drivers</h3>
            <button class="bntm-btn-primary" onclick="lpdResetDriverForm();lpdOpenModal('lpd-driver-modal')">Add Driver</button>
        </div>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead><tr><th>Name</th><th>Phone</th><th>Vehicle</th><th>Status</th><th>Active Orders</th><th>Done Today</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($drivers)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#6b7280;">No drivers found.</td></tr>
                <?php else: foreach ($drivers as $d): ?>
                    <tr>
                        <td><strong><?php echo esc_html($d->first_name.' '.$d->last_name); ?></strong></td>
                        <td><?php echo esc_html($d->phone ?: '—'); ?></td>
                        <td><?php echo esc_html($d->vehicle_info ?: '—'); ?></td>
                        <td>
                            <select class="bntm-btn-small" onchange="lpdUpdateDriverStatus(<?php echo $d->id; ?>, this.value)" style="padding:4px 8px;border-radius:6px;border:1px solid #d1d5db;">
                                <?php foreach (['available','busy','offline'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php selected($d->status,$s); ?>><?php echo ucfirst($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><?php echo (int)$d->active_orders; ?></td>
                        <td><?php echo (int)$d->done_today; ?></td>
                        <td style="white-space:nowrap;">
                            <button class="bntm-btn-primary bntm-btn-small" onclick="lpdEditDriver(<?php echo $d->id; ?>)">Edit</button>
                            <button class="bntm-btn-secondary bntm-btn-small" onclick="lpdViewDriverOrders(<?php echo $d->id; ?>, '<?php echo esc_js($d->first_name.' '.$d->last_name); ?>')">Orders</button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Driver Modal -->
    <div class="lpd-modal-overlay" id="lpd-driver-modal">
        <div class="lpd-modal">
            <div class="lpd-modal-header">
                <h3 id="lpd-driver-modal-title">Add Driver</h3>
                <button class="lpd-modal-close" onclick="lpdCloseModal('lpd-driver-modal')">&times;</button>
            </div>
            <input type="hidden" id="lpd-drv-id">
            <div class="lpd-form-row">
                <div class="bntm-form-group"><label>First Name *</label><input type="text" id="lpd-drv-fname"></div>
                <div class="bntm-form-group"><label>Last Name *</label><input type="text" id="lpd-drv-lname"></div>
            </div>
            <div class="lpd-form-row">
                <div class="bntm-form-group"><label>Email</label><input type="email" id="lpd-drv-email"></div>
                <div class="bntm-form-group"><label>Phone</label><input type="text" id="lpd-drv-phone"></div>
            </div>
            <div class="bntm-form-group"><label>Vehicle Info</label><input type="text" id="lpd-drv-vehicle" placeholder="e.g. Honda Wave 125 — ABC 1234"></div>
            <div class="bntm-form-group"><label>Notes</label><textarea id="lpd-drv-notes" rows="2"></textarea></div>
            <div id="lpd-driver-form-msg"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                <button class="bntm-btn-secondary" onclick="lpdCloseModal('lpd-driver-modal')">Cancel</button>
                <button class="bntm-btn-primary" id="lpd-drv-save-btn" onclick="lpdSaveDriver()">Save Driver</button>
            </div>
        </div>
    </div>

    <!-- Driver Orders Modal -->
    <div class="lpd-modal-overlay" id="lpd-driver-orders-modal">
        <div class="lpd-modal" style="max-width:750px;">
            <div class="lpd-modal-header">
                <h3 id="lpd-do-title">Driver Orders</h3>
                <button class="lpd-modal-close" onclick="lpdCloseModal('lpd-driver-orders-modal')">&times;</button>
            </div>
            <div id="lpd-do-body"></div>
        </div>
    </div>

    <script>
    (function() {
        window.lpdResetDriverForm = function() {
            document.getElementById('lpd-drv-id').value = '';
            document.getElementById('lpd-driver-modal-title').textContent = 'Add Driver';
            ['lpd-drv-fname','lpd-drv-lname','lpd-drv-email','lpd-drv-phone','lpd-drv-vehicle','lpd-drv-notes'].forEach(function(id) {
                document.getElementById(id).value = '';
            });
        };

        window.lpdSaveDriver = function() {
            var btn = document.getElementById('lpd-drv-save-btn');
            var id  = document.getElementById('lpd-drv-id').value;
            var data = {
                driver_id:    id,
                first_name:   document.getElementById('lpd-drv-fname').value,
                last_name:    document.getElementById('lpd-drv-lname').value,
                email:        document.getElementById('lpd-drv-email').value,
                phone:        document.getElementById('lpd-drv-phone').value,
                vehicle_info: document.getElementById('lpd-drv-vehicle').value,
                notes:        document.getElementById('lpd-drv-notes').value,
            };
            btn.disabled = true; btn.textContent = 'Saving...';
            lpdAjax(id ? 'lpd_update_driver' : 'lpd_create_driver', data, function(err, json) {
                if (json && json.success) { lpdToast('Driver saved!'); setTimeout(function() { location.reload(); }, 900); }
                else {
                    document.getElementById('lpd-driver-form-msg').innerHTML = '<div class="bntm-notice bntm-notice-error">'+(json?json.data.message:'Error')+'</div>';
                    btn.disabled = false; btn.textContent = 'Save Driver';
                }
            });
        };

        window.lpdEditDriver = function(id) {
            lpdAjax('lpd_get_driver_orders', {driver_id: id, info_only: 1}, function(err, json) {
                if (!json || !json.success) return;
                var d = json.data.driver;
                document.getElementById('lpd-driver-modal-title').textContent = 'Edit Driver';
                document.getElementById('lpd-drv-id').value      = d.id;
                document.getElementById('lpd-drv-fname').value   = d.first_name || '';
                document.getElementById('lpd-drv-lname').value   = d.last_name  || '';
                document.getElementById('lpd-drv-email').value   = d.email      || '';
                document.getElementById('lpd-drv-phone').value   = d.phone      || '';
                document.getElementById('lpd-drv-vehicle').value = d.vehicle_info || '';
                document.getElementById('lpd-drv-notes').value   = d.notes || '';
                lpdOpenModal('lpd-driver-modal');
            });
        };

        window.lpdUpdateDriverStatus = function(id, status) {
            lpdAjax('lpd_update_driver_status', {driver_id: id, status: status}, function(err, json) {
                lpdToast(json && json.success ? 'Driver status updated.' : 'Update failed.', json && json.success ? 'success' : 'error');
            });
        };

        window.lpdViewDriverOrders = function(id, name) {
            document.getElementById('lpd-do-title').textContent = name + ' — Assigned Orders';
            document.getElementById('lpd-do-body').innerHTML = '<p style="text-align:center;padding:20px;color:#6b7280;">Loading...</p>';
            lpdOpenModal('lpd-driver-orders-modal');
            lpdAjax('lpd_get_driver_orders', {driver_id: id}, function(err, json) {
                var body = document.getElementById('lpd-do-body');
                if (!json || !json.success || !json.data.orders.length) {
                    body.innerHTML = '<p style="color:#9ca3af;text-align:center;padding:20px;">No orders assigned.</p>'; return;
                }
                var html = '<div class="bntm-table-wrapper"><table class="bntm-table"><thead><tr><th>Ref</th><th>Customer</th><th>Status</th><th>Scheduled</th></tr></thead><tbody>';
                json.data.orders.forEach(function(o) {
                    html += '<tr><td><strong>'+o.reference_number+'</strong></td><td>'+o.customer_name+'</td><td>'+o.status+'</td><td>'+(o.scheduled_pickup_datetime||'—').substring(0,16)+'</td></tr>';
                });
                html += '</tbody></table></div>';
                body.innerHTML = html;
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

function lpd_billing_tab() {
    global $wpdb;
    $p = $wpdb->prefix;

    $inv_filter = isset($_GET['invst']) ? sanitize_text_field($_GET['invst']) : '';
    $where      = $inv_filter ? $wpdb->prepare("WHERE i.status=%s", $inv_filter) : '';

    $invoices = $wpdb->get_results("
        SELECT i.*, CONCAT(c.first_name,' ',c.last_name) AS customer_name, o.reference_number
        FROM {$p}lpd_invoices i
        LEFT JOIN {$p}lpd_customers c ON c.id=i.customer_id
        LEFT JOIN {$p}lpd_orders o ON o.id=i.order_id
        {$where} ORDER BY i.created_at DESC LIMIT 100
    ");

    $pricing_rules = $wpdb->get_results("SELECT * FROM {$p}lpd_pricing_rules ORDER BY service_type, rule_type");
    $promos        = $wpdb->get_results("SELECT * FROM {$p}lpd_promo_codes ORDER BY created_at DESC");

    $total_paid    = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM {$p}lpd_payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())");
    $total_unpaid  = (float) $wpdb->get_var("SELECT COALESCE(SUM(total_amount),0) FROM {$p}lpd_invoices WHERE status='unpaid'");

    $fn_enabled = bntm_is_module_enabled('fn') && bntm_is_module_visible('fn');
    ob_start();
    ?>
    <div class="bntm-stats-row" style="margin-bottom:24px;">
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content"><h3>Collected This Month</h3><p class="stat-number">&#8369;<?php echo number_format($total_paid,2); ?></p><span class="stat-label">total payments</span></div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="stat-content"><h3>Unpaid Balance</h3><p class="stat-number">&#8369;<?php echo number_format($total_unpaid,2); ?></p><span class="stat-label">outstanding invoices</span></div>
        </div>
    </div>

    <div class="bntm-form-section">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
            <h3>Invoices</h3>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="bntm-btn-secondary" onclick="lpdExportBilling()">Export CSV</button>
            </div>
        </div>
        <form method="get" style="display:flex;gap:10px;margin-bottom:16px;">
            <input type="hidden" name="tab" value="billing">
            <select name="invst" style="min-width:160px;">
                <option value="">All Statuses</option>
                <?php foreach (['unpaid','paid','overdue','refunded'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php selected($inv_filter,$s); ?>><?php echo ucfirst($s); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bntm-btn-primary">Filter</button>
            <a href="?tab=billing" class="bntm-btn-secondary">Clear</a>
        </form>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead><tr><th>Invoice #</th><th>Order Ref</th><th>Customer</th><th>Total</th><th>Status</th><th>Due Date</th><th>Paid At</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="8" style="text-align:center;color:#6b7280;">No invoices found.</td></tr>
                <?php else: foreach ($invoices as $inv): ?>
                    <tr>
                        <td><strong><?php echo esc_html($inv->invoice_number); ?></strong></td>
                        <td><?php echo esc_html($inv->reference_number); ?></td>
                        <td><?php echo esc_html($inv->customer_name); ?></td>
                        <td>&#8369;<?php echo number_format($inv->total_amount,2); ?></td>
                        <td><span class="lpd-status-badge <?php echo $inv->status==='paid'?'lpd-status-completed':($inv->status==='overdue'?'lpd-status-outdelivery':'lpd-status-pending'); ?>"><?php echo esc_html(ucfirst($inv->status)); ?></span></td>
                        <td><?php echo $inv->due_date ? date('M d, Y', strtotime($inv->due_date)) : '—'; ?></td>
                        <td><?php echo $inv->paid_at ? date('M d, Y', strtotime($inv->paid_at)) : '—'; ?></td>
                        <td style="white-space:nowrap;">
                            <?php if ($inv->status !== 'paid'): ?>
                            <button class="bntm-btn-primary bntm-btn-small" onclick="lpdRecordPayment(<?php echo $inv->id; ?>,<?php echo $inv->total_amount; ?>,'<?php echo esc_js($inv->invoice_number); ?>')">Mark Paid</button>
                            <?php endif; ?>
                            <?php if ($fn_enabled): ?>
                            <button class="bntm-btn-secondary bntm-btn-small" onclick="lpdFnExport(<?php echo $inv->id; ?>)">Export FN</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pricing Rules -->
    <div class="bntm-form-section" style="margin-top:24px;">
        <h3 style="margin-bottom:16px;">Pricing Rules</h3>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead><tr><th>Rule Name</th><th>Type</th><th>Service</th><th>Unit</th><th>Rate (&#8369;)</th><th>Active</th><th>Actions</th></tr></thead>
                <tbody id="pricing-rules-tbody">
                <?php if (empty($pricing_rules)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#6b7280;">No pricing rules configured.</td></tr>
                <?php else: foreach ($pricing_rules as $r): ?>
                    <tr>
                        <td><?php echo esc_html($r->rule_name); ?></td>
                        <td><?php echo esc_html($r->rule_type); ?></td>
                        <td><?php echo esc_html($r->service_type ?: 'All'); ?></td>
                        <td><?php echo esc_html($r->unit ?: '—'); ?></td>
                        <td>&#8369;<?php echo number_format($r->rate,2); ?></td>
                        <td><?php echo $r->is_active ? '<span style="color:#059669;">Yes</span>' : '<span style="color:#9ca3af;">No</span>'; ?></td>
                        <td><button class="bntm-btn-secondary bntm-btn-small" onclick="lpdDeletePricingRule(<?php echo $r->id; ?>)">Delete</button></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-top:16px;">
            <h4 style="margin-bottom:12px;font-size:14px;">Add Pricing Rule</h4>
            <div class="lpd-form-row">
                <div class="bntm-form-group"><label>Rule Name *</label><input type="text" id="pr-name" placeholder="e.g. Wash & Fold base rate"></div>
                <div class="bntm-form-group"><label>Rule Type *</label>
                    <select id="pr-type"><option value="base_rate">Base Rate</option><option value="weight_tier">Weight Tier</option><option value="express_surcharge">Express Surcharge</option><option value="delivery_fee">Delivery Fee</option><option value="item_category">Item Category</option></select>
                </div>
            </div>
            <div class="lpd-form-row">
                <div class="bntm-form-group"><label>Service Type</label>
                    <select id="pr-service"><option value="">All Services</option><option value="Wash &amp; Fold">Wash &amp; Fold</option><option value="Wash &amp; Iron">Wash &amp; Iron</option><option value="Dry Clean">Dry Clean</option><option value="Press Only">Press Only</option></select>
                </div>
                <div class="bntm-form-group"><label>Unit</label><input type="text" id="pr-unit" placeholder="per kg, per item, flat..."></div>
            </div>
            <div class="lpd-form-row">
                <div class="bntm-form-group"><label>Rate (&#8369;) *</label><input type="number" id="pr-rate" step="0.01" min="0" placeholder="0.00"></div>
                <div class="bntm-form-group"><label>Min Value</label><input type="number" id="pr-min" step="0.01" min="0" placeholder="0.00"></div>
            </div>
            <button class="bntm-btn-primary" onclick="lpdSavePricingRule()">Add Rule</button>
        </div>
    </div>

    <!-- Promo Codes -->
    <div class="bntm-form-section" style="margin-top:24px;">
        <h3 style="margin-bottom:16px;">Promo Codes</h3>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Usage</th><th>Valid Until</th><th>Active</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($promos)): ?>
                    <tr><td colspan="8" style="text-align:center;color:#6b7280;">No promo codes.</td></tr>
                <?php else: foreach ($promos as $promo): ?>
                    <tr>
                        <td><strong><?php echo esc_html($promo->code); ?></strong></td>
                        <td><?php echo esc_html(ucfirst($promo->discount_type)); ?></td>
                        <td><?php echo $promo->discount_type==='percent' ? $promo->discount_value.'%' : '&#8369;'.number_format($promo->discount_value,2); ?></td>
                        <td>&#8369;<?php echo number_format($promo->min_order_amount,2); ?></td>
                        <td><?php echo $promo->usage_count.'/'.($promo->usage_limit ?: '&#8734;'); ?></td>
                        <td><?php echo $promo->valid_until ?: '—'; ?></td>
                        <td><?php echo $promo->is_active ? '<span style="color:#059669;">Yes</span>' : '<span style="color:#9ca3af;">No</span>'; ?></td>
                        <td><button class="bntm-btn-danger bntm-btn-small" onclick="lpdDeletePromo(<?php echo $promo->id; ?>)">Delete</button></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-top:16px;">
            <h4 style="margin-bottom:12px;font-size:14px;">Add Promo Code</h4>
            <div class="lpd-form-row">
                <div class="bntm-form-group"><label>Code *</label><input type="text" id="pc-code" placeholder="SUMMER20" style="text-transform:uppercase;"></div>
                <div class="bntm-form-group"><label>Discount Type *</label><select id="pc-type"><option value="percent">Percent (%)</option><option value="fixed">Fixed Amount (&#8369;)</option></select></div>
            </div>
            <div class="lpd-form-row">
                <div class="bntm-form-group"><label>Discount Value *</label><input type="number" id="pc-value" step="0.01" min="0"></div>
                <div class="bntm-form-group"><label>Min Order Amount</label><input type="number" id="pc-min" step="0.01" min="0" placeholder="0.00"></div>
            </div>
            <div class="lpd-form-row">
                <div class="bntm-form-group"><label>Usage Limit (0 = unlimited)</label><input type="number" id="pc-limit" min="0" value="0"></div>
                <div class="bntm-form-group"><label>Valid Until</label><input type="date" id="pc-until"></div>
            </div>
            <button class="bntm-btn-primary" onclick="lpdSavePromo()">Add Promo Code</button>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div class="lpd-modal-overlay" id="lpd-payment-modal">
        <div class="lpd-modal" style="max-width:460px;">
            <div class="lpd-modal-header">
                <h3>Record Payment</h3>
                <button class="lpd-modal-close" onclick="lpdCloseModal('lpd-payment-modal')">&times;</button>
            </div>
            <input type="hidden" id="lpd-pay-inv-id">
            <div class="bntm-form-group"><label>Invoice</label><input type="text" id="lpd-pay-inv-no" readonly style="background:#f9fafb;"></div>
            <div class="bntm-form-group"><label>Amount *</label><input type="number" id="lpd-pay-amount" step="0.01" min="0"></div>
            <div class="bntm-form-group"><label>Payment Method *</label>
                <select id="lpd-pay-method">
                    <option value="cash">Cash</option>
                    <option value="gcash">GCash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="online">Online Payment</option>
                </select>
            </div>
            <div class="bntm-form-group"><label>Transaction Reference</label><input type="text" id="lpd-pay-ref" placeholder="GCash ref, bank ref, etc."></div>
            <div class="bntm-form-group"><label>Payment Date *</label><input type="date" id="lpd-pay-date"></div>
            <div class="bntm-form-group"><label>Notes</label><textarea id="lpd-pay-notes" rows="2"></textarea></div>
            <div id="lpd-pay-msg"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                <button class="bntm-btn-secondary" onclick="lpdCloseModal('lpd-payment-modal')">Cancel</button>
                <button class="bntm-btn-primary" id="lpd-pay-btn" onclick="lpdSubmitPayment()">Record Payment</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        window.lpdRecordPayment = function(invId, amount, invNo) {
            document.getElementById('lpd-pay-inv-id').value  = invId;
            document.getElementById('lpd-pay-inv-no').value  = invNo;
            document.getElementById('lpd-pay-amount').value  = amount;
            document.getElementById('lpd-pay-date').value    = new Date().toISOString().slice(0,10);
            document.getElementById('lpd-pay-msg').innerHTML = '';
            lpdOpenModal('lpd-payment-modal');
        };

        window.lpdSubmitPayment = function() {
            var btn = document.getElementById('lpd-pay-btn');
            btn.disabled = true; btn.textContent = 'Saving...';
            lpdAjax('lpd_record_payment', {
                invoice_id: document.getElementById('lpd-pay-inv-id').value,
                amount:     document.getElementById('lpd-pay-amount').value,
                payment_method: document.getElementById('lpd-pay-method').value,
                transaction_reference: document.getElementById('lpd-pay-ref').value,
                payment_date: document.getElementById('lpd-pay-date').value,
                notes: document.getElementById('lpd-pay-notes').value,
            }, function(err, json) {
                if (json && json.success) {
                    lpdToast('Payment recorded!');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    document.getElementById('lpd-pay-msg').innerHTML = '<div class="bntm-notice bntm-notice-error">'+(json?json.data.message:'Error')+'</div>';
                    btn.disabled = false; btn.textContent = 'Record Payment';
                }
            });
        };

        window.lpdSavePricingRule = function() {
            lpdAjax('lpd_save_pricing_rules', {
                rule_name: document.getElementById('pr-name').value,
                rule_type: document.getElementById('pr-type').value,
                service_type: document.getElementById('pr-service').value,
                unit: document.getElementById('pr-unit').value,
                rate: document.getElementById('pr-rate').value,
                min_value: document.getElementById('pr-min').value,
            }, function(err, json) {
                if (json && json.success) { lpdToast('Rule added!'); setTimeout(function(){ location.reload(); }, 800); }
                else lpdToast((json?json.data.message:'Error'), 'error');
            });
        };

        window.lpdDeletePricingRule = function(id) {
            if (!confirm('Delete this pricing rule?')) return;
            lpdAjax('lpd_save_pricing_rules', {delete_id: id}, function(err, json) {
                if (json && json.success) { lpdToast('Rule deleted!'); setTimeout(function(){ location.reload(); }, 800); }
            });
        };

        window.lpdSavePromo = function() {
            var code = document.getElementById('pc-code').value.trim().toUpperCase();
            lpdAjax('lpd_save_promo_code', {
                code: code,
                discount_type: document.getElementById('pc-type').value,
                discount_value: document.getElementById('pc-value').value,
                min_order_amount: document.getElementById('pc-min').value,
                usage_limit: document.getElementById('pc-limit').value,
                valid_until: document.getElementById('pc-until').value,
            }, function(err, json) {
                if (json && json.success) { lpdToast('Promo code added!'); setTimeout(function(){ location.reload(); }, 800); }
                else lpdToast((json?json.data.message:'Error'), 'error');
            });
        };

        window.lpdDeletePromo = function(id) {
            if (!confirm('Delete this promo code?')) return;
            lpdAjax('lpd_delete_promo_code', {promo_id: id}, function(err, json) {
                if (json && json.success) { lpdToast('Deleted!'); setTimeout(function(){ location.reload(); }, 800); }
            });
        };

        window.lpdExportBilling = function() {
            lpdAjax('lpd_export_billing', {}, function(err, json) {
                if (!json || !json.success) { lpdToast('Export failed', 'error'); return; }
                var a = document.createElement('a');
                a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(json.data.csv);
                a.download = 'invoices.csv'; a.click();
            });
        };

        window.lpdFnExport = function(invId) {
            lpdAjax('lpd_fn_export_payment', {invoice_id: invId}, function(err, json) {
                lpdToast(json && json.success ? 'Exported to Finance!' : (json?json.data.message:'Error'), json&&json.success?'success':'error');
            });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// Frontend shortcodes and AJAX handlers
// Track Order shortcode
function bntm_shortcode_lpd_track_order() {
    $nonce = wp_create_nonce('lpd_nonce');
    ob_start();
    ?>
    <div class="lpd-track-order">
        <div style="max-width:520px;">
            <input type="text" id="lpd-ref" placeholder="Enter reference number" style="width:70%;padding:8px;margin-right:6px;" />
            <button type="button" id="lpd-track-btn" class="bntm-btn-primary">Track</button>
            <div id="lpd-track-result" style="margin-top:12px;color:#374151;"></div>
        </div>
    </div>
    <script>
    (function(){
      var btn=document.getElementById('lpd-track-btn');
      btn.addEventListener('click', function(){
        var ref=document.getElementById('lpd-ref').value.trim();
        var out=document.getElementById('lpd-track-result');
        if(!ref){ out.innerHTML='<span style="color:#dc2626;">Please enter a reference number.</span>'; return; }
        out.innerHTML='Loading...';
        var fd=new FormData(); fd.append('action','lpd_track_order_lookup'); fd.append('nonce','<?php echo $nonce; ?>'); fd.append('reference', ref);
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {method:'POST', body:fd}).then(function(r){return r.json();}).then(function(json){
           if(json && json.success){ var o=json.data.order; out.innerHTML = '<strong>Reference:</strong> '+o.reference_number+'<br><strong>Status:</strong> '+o.status+'<br><strong>Pickup:</strong> '+(o.scheduled_pickup_datetime||'—')+'<br><strong>Total:</strong> ₱'+parseFloat(o.total_amount||0).toFixed(2);
           } else { out.innerHTML = '<span style="color:#dc2626;">'+(json && json.data && json.data.message ? json.data.message : 'Order not found.')+'</span>'; }
        }).catch(function(){ out.innerHTML='<span style="color:#dc2626;">Error contacting server.</span>'; });
      });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// Customer Portal shortcode
function bntm_shortcode_lpd_customer_portal() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to view your orders.</div>';
    }
    $uid = get_current_user_id();
    global $wpdb; $p = $wpdb->prefix;
    $orders = $wpdb->get_results($wpdb->prepare("SELECT o.*, (SELECT CONCAT(first_name,' ',last_name) FROM {$p}lpd_customers WHERE id=o.customer_id) AS customer_name FROM {$p}lpd_orders o WHERE o.created_by=%d ORDER BY o.created_at DESC LIMIT 100", $uid));
    ob_start();
    ?>
    <div class="lpd-customer-portal">
        <h3>Your Orders</h3>
        <?php if (empty($orders)): ?>
            <p style="color:#6b7280;">No orders found.</p>
        <?php else: ?>
            <table class="bntm-table" style="max-width:820px;"><thead><tr><th>Reference</th><th>Service</th><th>Status</th><th>Total</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong><?php echo esc_html($o->reference_number); ?></strong></td>
                    <td><?php echo esc_html($o->service_type); ?></td>
                    <td><?php echo esc_html($o->status); ?></td>
                    <td>₱<?php echo number_format($o->total_amount,2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($o->created_at)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// AJAX: Track order lookup (frontend)
function bntm_ajax_lpd_track_order_lookup() {
    if (function_exists('check_ajax_referer')) { check_ajax_referer('lpd_nonce','nonce',false); }
    if (empty($_POST['reference'])) wp_send_json_error(array('message' => 'Missing reference'));
    $ref = sanitize_text_field(wp_unslash($_POST['reference']));
    global $wpdb; $p = $wpdb->prefix;
    $order = $wpdb->get_row($wpdb->prepare("SELECT o.*, CONCAT(c.first_name,' ',c.last_name) AS customer_name FROM {$p}lpd_orders o LEFT JOIN {$p}lpd_customers c ON c.id=o.customer_id WHERE o.reference_number=%s OR o.rand_id=%s LIMIT 1", $ref, $ref));
    if (!$order) wp_send_json_error(array('message' => 'Order not found'));
    wp_send_json_success(array('order' => $order));
}

// AJAX: Submit pickup request (frontend)
function bntm_ajax_lpd_submit_pickup_request() {
    if (function_exists('check_ajax_referer')) { check_ajax_referer('lpd_nonce','nonce',false); }
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
    $email      = sanitize_email($_POST['email'] ?? '');
    $phone      = sanitize_text_field($_POST['phone'] ?? '');
    $pickup_address = sanitize_text_field($_POST['pickup_address'] ?? '');
    $service_type = sanitize_text_field($_POST['service_type'] ?? '');
    if (empty($first_name) || empty($email) || empty($pickup_address)) wp_send_json_error(array('message' => 'Missing required fields'));
    global $wpdb; $p = $wpdb->prefix;
    $rand_c = wp_generate_password(12,false,false);
    $now = current_time('mysql');
    $wpdb->insert("{$p}lpd_customers", array('rand_id'=>$rand_c,'first_name'=>$first_name,'last_name'=>$last_name,'email'=>$email,'phone'=>$phone,'pickup_address'=>$pickup_address,'status'=>'active','created_at'=>$now,'updated_at'=>$now));
    $cust_id = $wpdb->insert_id;
    $rand_o = wp_generate_password(12,false,false);
    $ref = 'LPD'.time();
    $wpdb->insert("{$p}lpd_orders", array('rand_id'=>$rand_o,'reference_number'=>$ref,'customer_id'=>$cust_id,'service_type'=>$service_type,'status'=>'Pending Request','pickup_address'=>$pickup_address,'total_amount'=>0.00,'payment_status'=>'unpaid','created_at'=>$now,'updated_at'=>$now));
    $order_id = $wpdb->insert_id;
    wp_send_json_success(array('message'=>'Pickup request submitted','reference'=>$ref,'order_id'=>$order_id));
}

// Register shortcodes if add_shortcode is available
if (function_exists('add_shortcode')) {
    add_shortcode('lpd_track_order', 'bntm_shortcode_lpd_track_order');
    add_shortcode('lpd_customer_portal', 'bntm_shortcode_lpd_customer_portal');
    add_shortcode('lpd_dashboard', 'bntm_shortcode_lpd');
}

// Fallback stubs for AJAX handlers and payment callback
// These prevent fatal errors while the full implementations are developed.
// ============================================================

$lpd_ajax_handlers = [
    'bntm_ajax_lpd_get_dashboard_stats', 'bntm_ajax_lpd_get_active_orders', 'bntm_ajax_lpd_get_recent_payments',
    'bntm_ajax_lpd_create_order', 'bntm_ajax_lpd_get_order_detail', 'bntm_ajax_lpd_update_order_status',
    'bntm_ajax_lpd_assign_driver', 'bntm_ajax_lpd_add_order_note', 'bntm_ajax_lpd_get_order_audit_log',
    'bntm_ajax_lpd_search_customers',
    'bntm_ajax_lpd_create_customer', 'bntm_ajax_lpd_update_customer', 'bntm_ajax_lpd_get_customer_profile',
    'bntm_ajax_lpd_get_customer_orders', 'bntm_ajax_lpd_delete_customer', 'bntm_ajax_lpd_export_customers',
    'bntm_ajax_lpd_create_driver', 'bntm_ajax_lpd_update_driver', 'bntm_ajax_lpd_get_driver_workload',
    'bntm_ajax_lpd_get_driver_orders', 'bntm_ajax_lpd_update_driver_status', 'bntm_ajax_lpd_submit_delivery_proof',
    'bntm_ajax_lpd_create_invoice', 'bntm_ajax_lpd_update_invoice', 'bntm_ajax_lpd_record_payment',
    'bntm_ajax_lpd_get_billing_summary', 'bntm_ajax_lpd_save_pricing_rules', 'bntm_ajax_lpd_get_pricing_rules',
    'bntm_ajax_lpd_apply_promo_code', 'bntm_ajax_lpd_export_billing', 'bntm_ajax_lpd_save_promo_code',
    'bntm_ajax_lpd_delete_promo_code', 'bntm_ajax_lpd_fn_export_payment', 'bntm_ajax_lpd_fn_revert_payment',
    'bntm_ajax_lpd_save_email_template', 'bntm_ajax_lpd_get_email_template', 'bntm_ajax_lpd_get_email_log',
    'bntm_ajax_lpd_resend_email', 'bntm_ajax_lpd_get_email_stats', 'bntm_ajax_lpd_get_report_data',
    'bntm_ajax_lpd_export_report', 'bntm_ajax_lpd_save_settings', 'bntm_ajax_lpd_get_settings',
    'bntm_ajax_lpd_save_role_permissions', 'bntm_ajax_lpd_save_payment_source', 'bntm_ajax_lpd_add_payment_method',
    'bntm_ajax_lpd_remove_payment_method', 'bntm_ajax_lpd_track_order_lookup', 'bntm_ajax_lpd_submit_pickup_request'
];

foreach ($lpd_ajax_handlers as $h) {
    if (!function_exists($h)) {
        eval("function $h() {\n            if (function_exists('check_ajax_referer')) { check_ajax_referer('lpd_nonce','nonce',false); }\n            wp_send_json_error(array('message' => 'Not implemented: $h'));\n        }");
    }
}

if (!function_exists('lpd_handle_payment_success')) {
    function lpd_handle_payment_success() {
        // Minimal no-op handler while payment integration is not implemented.
        return;
    }
}
