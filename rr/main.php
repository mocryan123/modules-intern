<?php
/**
 * Module Name: Restaurant Reservations
 * Module Slug: rr
 * Description: A complete restaurant reservation system with table management, booking, availability checking, and cancellation.
 * Version: 1.0.0
 * Author: BNTM Framework
 * Icon: rr
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Module constants
define('BNTM_RR_PATH', dirname(__FILE__) . '/');
define('BNTM_RR_URL', plugin_dir_url(__FILE__));

// ============================================================
// 1. CORE MODULE CONFIGURATION FUNCTIONS
// ============================================================

function bntm_rr_get_pages() {
    return [
        'Restaurant Reservations' => '[bntm_rr_dashboard]',
        'Make a Reservation'      => '[bntm_rr_booking]',
    ];
}

function bntm_rr_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    return [
        'rr_tables' => "CREATE TABLE {$prefix}rr_tables (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            table_number VARCHAR(20) NOT NULL,
            capacity INT UNSIGNED NOT NULL DEFAULT 2,
            location VARCHAR(100) DEFAULT 'Main Hall',
            status ENUM('available','maintenance','inactive') NOT NULL DEFAULT 'available',
            notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_status (status)
        ) {$charset};",

        'rr_reservations' => "CREATE TABLE {$prefix}rr_reservations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            table_id BIGINT UNSIGNED NOT NULL,
            customer_name VARCHAR(150) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            email VARCHAR(150) DEFAULT NULL,
            num_guests INT UNSIGNED NOT NULL DEFAULT 1,
            reservation_date DATE NOT NULL,
            reservation_time TIME NOT NULL,
            duration_minutes INT UNSIGNED NOT NULL DEFAULT 90,
            status ENUM('confirmed','cancelled','completed','no_show') NOT NULL DEFAULT 'confirmed',
            special_requests TEXT DEFAULT NULL,
            cancellation_reason TEXT DEFAULT NULL,
            cancelled_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_table (table_id),
            INDEX idx_date (reservation_date),
            INDEX idx_status (status)
        ) {$charset};",
    ];
}

function bntm_rr_get_shortcodes() {
    return [
        'bntm_rr_dashboard' => 'bntm_shortcode_rr',
        'bntm_rr_booking'   => 'bntm_shortcode_rr_booking',
    ];
}

function bntm_rr_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_rr_get_tables();
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
    // Seed default tables if none exist
    rr_seed_default_tables();
    return count($tables);
}

function rr_seed_default_tables() {
    global $wpdb;
    $table = $wpdb->prefix . 'rr_tables';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    if ($count > 0) return;

    // For single restaurant setup, use business_id = 1
    // For multi-tenant (multiple restaurants), each restaurant should have their own business_id
    $business_id = 1;

    $defaults = [
        ['T01', 2, 'Window Seats'],
        ['T02', 2, 'Window Seats'],
        ['T03', 4, 'Main Hall'],
        ['T04', 4, 'Main Hall'],
        ['T05', 4, 'Main Hall'],
        ['T06', 6, 'Main Hall'],
        ['T07', 6, 'Private Room'],
        ['T08', 8, 'Private Room'],
        ['T09', 10, 'Banquet Hall'],
        ['T10', 12, 'Banquet Hall'],
    ];

    foreach ($defaults as $d) {
        $wpdb->insert($table, [
            'rand_id'      => bntm_rand_id(),
            'business_id'  => $business_id,
            'table_number' => $d[0],
            'capacity'     => $d[1],
            'location'     => $d[2],
            'status'       => 'available',
        ], ['%s','%d','%s','%d','%s','%s']);
    }
}

// ============================================================
// 2. AJAX ACTION HOOKS
// ============================================================

add_action('wp_ajax_rr_get_available_tables',   'bntm_ajax_rr_get_available_tables');
add_action('wp_ajax_rr_make_reservation',        'bntm_ajax_rr_make_reservation');
add_action('wp_ajax_rr_cancel_reservation',      'bntm_ajax_rr_cancel_reservation');
add_action('wp_ajax_rr_update_reservation',      'bntm_ajax_rr_update_reservation');
add_action('wp_ajax_rr_add_table',               'bntm_ajax_rr_add_table');
add_action('wp_ajax_rr_delete_table',            'bntm_ajax_rr_delete_table');
add_action('wp_ajax_rr_update_table_status',     'bntm_ajax_rr_update_table_status');
add_action('wp_ajax_rr_complete_reservation',    'bntm_ajax_rr_complete_reservation');
add_action('wp_ajax_rr_get_reservations',        'bntm_ajax_rr_get_reservations');

// Public (non-logged-in) access for booking page
add_action('wp_ajax_nopriv_rr_get_available_tables', 'bntm_ajax_rr_get_available_tables');
add_action('wp_ajax_nopriv_rr_make_reservation',     'bntm_ajax_rr_make_reservation');

// ============================================================
// 3. MAIN DASHBOARD SHORTCODE
// ============================================================

function bntm_shortcode_rr() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to access the reservation dashboard.</div>';
    }

    $current_user = wp_get_current_user();
    $business_id  = $current_user->ID;
    $active_tab   = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

    ob_start();
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>

    <div class="bntm-rr-container">

        <!-- Tab Navigation -->
        <div class="bntm-tabs">
            <a href="?tab=overview"      class="bntm-tab <?php echo $active_tab === 'overview'      ? 'active' : ''; ?>">Overview</a>
            <a href="?tab=reservations"  class="bntm-tab <?php echo $active_tab === 'reservations'  ? 'active' : ''; ?>">Reservations</a>
            <a href="?tab=tables"        class="bntm-tab <?php echo $active_tab === 'tables'        ? 'active' : ''; ?>">Tables</a>
            <a href="?tab=availability"  class="bntm-tab <?php echo $active_tab === 'availability'  ? 'active' : ''; ?>">Availability</a>
        </div>

        <!-- Tab Content -->
        <div class="bntm-tab-content">
            <?php
            switch ($active_tab) {
                case 'reservations':
                    echo rr_reservations_tab($business_id);
                    break;
                case 'tables':
                    echo rr_tables_tab($business_id);
                    break;
                case 'availability':
                    echo rr_availability_tab($business_id);
                    break;
                default:
                    echo rr_overview_tab($business_id);
            }
            ?>
        </div>
    </div>

    <!-- Global Modal -->
    <div id="rr-modal-overlay" class="rr-modal-overlay">
        <div class="rr-modal">
            <div class="rr-modal-header">
                <h3 id="rr-modal-title">Modal Title</h3>
                <button class="rr-modal-close" onclick="rrCloseModal()">&times;</button>
            </div>
            <div class="rr-modal-body" id="rr-modal-body"></div>
        </div>
    </div>

    <style>
    /* =============================================
       RR MODULE — GLOBAL STYLES
       ============================================= */
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap');

    :root {
        --rr-charcoal:  #1a1a2e;
        --rr-deep:      #16213e;
        --rr-mid:       #0f3460;
        --rr-gold:      #c9a84c;
        --rr-gold-lt:   #e8c97e;
        --rr-ivory:     #faf8f3;
        --rr-cream:     #f5f0e8;
        --rr-muted:     #8a8a9a;
        --rr-border:    #e2ddd4;
        --rr-success:   #2d7d55;
        --rr-danger:    #c0392b;
        --rr-warning:   #d4820a;
        --rr-shadow:    0 4px 24px rgba(26,26,46,0.10);
        --rr-shadow-lg: 0 12px 48px rgba(26,26,46,0.18);
        --rr-radius:    12px;
        --rr-radius-sm: 6px;
        --ff-display:   'Playfair Display', Georgia, serif;
        --ff-body:      'DM Sans', sans-serif;
    }

    .bntm-rr-container,
    .rr-booking-wrap {
        font-family: var(--ff-body);
        color: var(--rr-charcoal);
        max-width: 1160px;
        margin: 0 auto;
    }

    /* Tabs */
    .bntm-tabs {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid var(--rr-border);
        margin-bottom: 28px;
    }
    .bntm-tab {
        padding: 10px 22px;
        font-family: var(--ff-body);
        font-size: 14px;
        font-weight: 500;
        color: var(--rr-muted);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: color .2s, border-color .2s;
        letter-spacing: .3px;
    }
    .bntm-tab:hover { color: var(--rr-charcoal); }
    .bntm-tab.active {
        color: var(--rr-gold);
        border-bottom-color: var(--rr-gold);
        font-weight: 600;
    }

    /* Stat Cards */
    .rr-stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .rr-stat-card {
        background: #fff;
        border: 1px solid var(--rr-border);
        border-radius: var(--rr-radius);
        padding: 22px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: var(--rr-shadow);
        transition: transform .18s, box-shadow .18s;
    }
    .rr-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--rr-shadow-lg);
    }
    .rr-stat-icon {
        width: 48px; height: 48px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .rr-stat-info h4 {
        font-family: var(--ff-body);
        font-size: 12px;
        font-weight: 500;
        color: var(--rr-muted);
        margin: 0 0 4px;
        text-transform: uppercase;
        letter-spacing: .6px;
    }
    .rr-stat-number {
        font-family: var(--ff-display);
        font-size: 28px;
        font-weight: 700;
        color: var(--rr-charcoal);
        line-height: 1;
    }
    .rr-stat-sub {
        font-size: 12px;
        color: var(--rr-muted);
        margin-top: 3px;
    }

    /* Section Card */
    .rr-section {
        background: #fff;
        border: 1px solid var(--rr-border);
        border-radius: var(--rr-radius);
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: var(--rr-shadow);
    }
    .rr-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--rr-border);
    }
    .rr-section-title {
        font-family: var(--ff-display);
        font-size: 18px;
        font-weight: 600;
        color: var(--rr-charcoal);
        margin: 0;
    }

    /* Tables */
    .rr-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .rr-table thead tr {
        background: var(--rr-cream);
    }
    .rr-table th {
        padding: 11px 14px;
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: var(--rr-muted);
        border-bottom: 1px solid var(--rr-border);
    }
    .rr-table td {
        padding: 13px 14px;
        border-bottom: 1px solid #f0ede6;
        vertical-align: middle;
    }
    .rr-table tbody tr:hover { background: #fdf9f4; }
    .rr-table tbody tr:last-child td { border-bottom: none; }

    /* Badges */
    .rr-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .3px;
    }
    .rr-badge-confirmed  { background: #dcfce7; color: #15803d; }
    .rr-badge-cancelled  { background: #fee2e2; color: #dc2626; }
    .rr-badge-completed  { background: #dbeafe; color: #1d4ed8; }
    .rr-badge-no_show    { background: #fef3c7; color: #b45309; }
    .rr-badge-available  { background: #dcfce7; color: #15803d; }
    .rr-badge-maintenance{ background: #fef3c7; color: #b45309; }
    .rr-badge-inactive   { background: #f3f4f6; color: #6b7280; }

    /* Buttons */
    .rr-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        border: none;
        border-radius: var(--rr-radius-sm);
        font-family: var(--ff-body);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: opacity .18s, transform .1s, box-shadow .18s;
        text-decoration: none;
        line-height: 1;
    }
    .rr-btn:hover { opacity: .88; transform: translateY(-1px); }
    .rr-btn:active { transform: translateY(0); }
    .rr-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }

    .rr-btn-primary {
        background: linear-gradient(135deg, var(--rr-gold) 0%, #b8963c 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(201,168,76,.35);
    }
    .rr-btn-secondary {
        background: var(--rr-charcoal);
        color: #fff;
    }
    .rr-btn-danger {
        background: #fee2e2;
        color: var(--rr-danger);
    }
    .rr-btn-success {
        background: #dcfce7;
        color: var(--rr-success);
    }
    .rr-btn-ghost {
        background: var(--rr-cream);
        color: var(--rr-charcoal);
        border: 1px solid var(--rr-border);
    }
    .rr-btn-sm { padding: 6px 12px; font-size: 12px; }

    /* Forms */
    .rr-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }
    .rr-form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .rr-form-group.full { grid-column: 1 / -1; }
    .rr-form-group label {
        font-size: 12px;
        font-weight: 600;
        color: var(--rr-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .rr-input, .rr-select, .rr-textarea {
        padding: 10px 13px;
        border: 1px solid var(--rr-border);
        border-radius: var(--rr-radius-sm);
        font-family: var(--ff-body);
        font-size: 14px;
        color: var(--rr-charcoal);
        background: #fff;
        transition: border-color .18s, box-shadow .18s;
        outline: none;
        width: 100%;
        box-sizing: border-box;
    }
    .rr-input:focus, .rr-select:focus, .rr-textarea:focus {
        border-color: var(--rr-gold);
        box-shadow: 0 0 0 3px rgba(201,168,76,.15);
    }
    .rr-textarea { resize: vertical; min-height: 80px; }

    /* Notices */
    .rr-notice {
        padding: 12px 16px;
        border-radius: var(--rr-radius-sm);
        font-size: 13px;
        font-weight: 500;
        margin: 10px 0;
    }
    .rr-notice-success { background: #dcfce7; color: #15803d; border-left: 3px solid #15803d; }
    .rr-notice-error   { background: #fee2e2; color: #dc2626; border-left: 3px solid #dc2626; }
    .rr-notice-info    { background: #dbeafe; color: #1d4ed8; border-left: 3px solid #1d4ed8; }
    .rr-notice-warning { background: #fef3c7; color: #b45309; border-left: 3px solid #b45309; }

    /* Modal */
    .rr-modal-overlay {
        position: fixed; inset: 0;
        background: rgba(26,26,46,.55);
        backdrop-filter: blur(3px);
        z-index: 99998;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .rr-modal-overlay.rr-modal-open {
        display: flex;
    }
    .rr-modal {
        background: #fff;
        border-radius: var(--rr-radius);
        width: 100%;
        max-width: 540px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: var(--rr-shadow-lg);
        animation: rrModalIn .2s ease;
    }
    @keyframes rrModalIn {
        from { opacity:0; transform: translateY(-16px) scale(.97); }
        to   { opacity:1; transform: translateY(0) scale(1); }
    }
    .rr-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        border-bottom: 1px solid var(--rr-border);
    }
    .rr-modal-header h3 {
        font-family: var(--ff-display);
        font-size: 18px;
        margin: 0;
        color: var(--rr-charcoal);
    }
    .rr-modal-close {
        background: none; border: none;
        font-size: 22px; color: var(--rr-muted);
        cursor: pointer; padding: 0; line-height: 1;
        transition: color .15s;
    }
    .rr-modal-close:hover { color: var(--rr-charcoal); }
    .rr-modal-body { padding: 24px; }

    /* Divider */
    .rr-divider {
        border: none;
        border-top: 1px solid var(--rr-border);
        margin: 20px 0;
    }

    /* View Toggle Buttons */
    .rr-view-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 10px;
        border-radius: 4px;
        background: transparent;
        color: var(--rr-muted);
        transition: background .15s, color .15s;
        text-decoration: none;
    }
    .rr-view-btn:hover { background: rgba(26,26,46,.06); color: var(--rr-charcoal); }
    .rr-view-btn.active { background: #fff; color: var(--rr-gold); box-shadow: 0 1px 3px rgba(0,0,0,.08); }

    /* Table Management Grid */
    .rr-tables-management-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 16px;
    }
    .rr-table-mgmt-card {
        border: 1px solid var(--rr-border);
        border-radius: var(--rr-radius);
        padding: 18px;
        background: #fff;
        transition: transform .15s, box-shadow .15s;
    }
    .rr-table-mgmt-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--rr-shadow-lg);
    }
    .rr-table-mgmt-card.rr-status-available { border-left: 3px solid #10b981; }
    .rr-table-mgmt-card.rr-status-maintenance { border-left: 3px solid #f59e0b; }
    .rr-table-mgmt-card.rr-status-inactive { border-left: 3px solid #6b7280; }
    .rr-table-mgmt-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 14px;
    }
    .rr-table-mgmt-num {
        font-family: var(--ff-display);
        font-size: 18px;
        font-weight: 700;
        color: var(--rr-charcoal);
        margin-bottom: 2px;
    }
    .rr-table-mgmt-loc {
        font-size: 12px;
        color: var(--rr-muted);
    }
    .rr-table-mgmt-body {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 14px;
    }
    .rr-table-mgmt-stat {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--rr-muted);
    }
    .rr-table-mgmt-stat svg {
        flex-shrink: 0;
    }
    .rr-table-mgmt-notes {
        font-size: 12px;
        color: var(--rr-muted);
        padding: 10px;
        background: var(--rr-cream);
        border-radius: 6px;
        margin-bottom: 14px;
        font-style: italic;
    }
    .rr-table-mgmt-actions {
        display: flex;
        gap: 8px;
    }

    /* Table Grid (cards) */
    .rr-tables-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 14px;
    }
    .rr-table-card {
        border: 1px solid var(--rr-border);
        border-radius: var(--rr-radius);
        padding: 18px;
        background: #fff;
        cursor: pointer;
        transition: transform .18s, box-shadow .18s, border-color .18s;
        position: relative;
    }
    .rr-table-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--rr-shadow-lg);
        border-color: var(--rr-gold);
    }
    .rr-table-card.selected {
        border-color: var(--rr-gold);
        background: #fffbf0;
        box-shadow: 0 0 0 3px rgba(201,168,76,.2);
    }
    .rr-table-card-num {
        font-family: var(--ff-display);
        font-size: 22px;
        font-weight: 700;
        color: var(--rr-charcoal);
        margin-bottom: 6px;
    }
    .rr-table-card-cap {
        font-size: 12px;
        color: var(--rr-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .rr-table-card-loc {
        font-size: 11px;
        color: var(--rr-muted);
        margin-top: 4px;
    }

    /* Availability grid */
    .rr-avail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
    }
    .rr-avail-card {
        border: 1px solid var(--rr-border);
        border-radius: var(--rr-radius-sm);
        padding: 14px;
        text-align: center;
        background: #fff;
    }
    .rr-avail-card.free  { border-color: #86efac; background: #f0fdf4; }
    .rr-avail-card.taken { border-color: #fca5a5; background: #fef2f2; }
    .rr-avail-card-num   { font-family: var(--ff-display); font-size: 18px; font-weight: 700; }
    .rr-avail-card-cap   { font-size: 12px; color: var(--rr-muted); margin: 3px 0; }
    .rr-avail-card-status{ font-size: 12px; font-weight: 600; margin-top: 6px; }
    .free  .rr-avail-card-status { color: #15803d; }
    .taken .rr-avail-card-status { color: #dc2626; }

    /* Spinner */
    .rr-spinner {
        display: inline-block;
        width: 16px; height: 16px;
        border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .7s linear infinite;
        vertical-align: middle;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Today reservations timeline */
    .rr-timeline-item {
        display: flex;
        gap: 14px;
        padding: 12px 0;
        border-bottom: 1px solid #f0ede6;
    }
    .rr-timeline-item:last-child { border-bottom: none; }
    .rr-timeline-time {
        font-family: var(--ff-display);
        font-size: 15px;
        font-weight: 600;
        color: var(--rr-gold);
        min-width: 55px;
    }
    .rr-timeline-info { flex: 1; }
    .rr-timeline-name { font-weight: 600; font-size: 14px; color: var(--rr-charcoal); }
    .rr-timeline-meta { font-size: 12px; color: var(--rr-muted); margin-top: 2px; }

    /* Filters */
    .rr-filters {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 18px;
        align-items: center;
    }
    .rr-filter-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--rr-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    /* Empty state */
    .rr-empty {
        text-align: center;
        padding: 48px 20px;
        color: var(--rr-muted);
    }
    .rr-empty-icon { margin-bottom: 12px; opacity: .4; }
    .rr-empty h4 {
        font-family: var(--ff-display);
        font-size: 18px;
        color: var(--rr-charcoal);
        margin: 0 0 6px;
    }
    .rr-empty p { font-size: 13px; margin: 0; }

    /* Responsive Design */
    @media (max-width: 768px) {
        .rr-stats-row {
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }
        .rr-stat-number { font-size: 24px; }
        .rr-tabs {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .rr-tab {
            white-space: nowrap;
            padding: 10px 16px;
        }
        .rr-filters {
            flex-direction: column;
            align-items: stretch;
        }
        .rr-filter-label {
            margin-bottom: 6px;
        }
        .rr-table {
            font-size: 13px;
        }
        .rr-table th, .rr-table td {
            padding: 10px 8px;
        }
        .rr-section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        .rr-overview-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 14px;
        }
        .rr-tables-management-grid {
            grid-template-columns: 1fr;
        }
        .rr-avail-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }
    }

    @media (max-width: 480px) {
        .rr-stat-card {
            padding: 16px;
        }
        .rr-stat-icon {
            width: 40px;
            height: 40px;
        }
        .rr-stat-number { font-size: 22px; }
        .bntm-rr-container, .rr-booking-wrap {
            padding: 0 12px;
        }
        .rr-modal {
            max-width: 100%;
            margin: 10px;
        }
        .rr-form-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
    // Global modal helpers
    function rrOpenModal(title, bodyHTML) {
        document.getElementById('rr-modal-title').textContent = title;
        document.getElementById('rr-modal-body').innerHTML = bodyHTML;
        document.getElementById('rr-modal-overlay').classList.add('rr-modal-open');
    }
    function rrCloseModal() {
        document.getElementById('rr-modal-overlay').classList.remove('rr-modal-open');
    }
    // Close on backdrop click
    document.getElementById('rr-modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) rrCloseModal();
    });
    </script>
    <?php

    $content = ob_get_clean();
    return bntm_universal_container('Restaurant Reservations', $content);
}

// ============================================================
// 4. TAB FUNCTIONS
// ============================================================

/* ---- OVERVIEW TAB ---- */
function rr_overview_tab($business_id) {
    global $wpdb;
    $res_table = $wpdb->prefix . 'rr_reservations';
    $tbl_table = $wpdb->prefix . 'rr_tables';
    $today     = date('Y-m-d');
    $nonce     = wp_create_nonce('rr_nonce');

    $total_tables     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tbl_table} WHERE status='available'");
    $total_today      = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$res_table} WHERE reservation_date=%s AND status='confirmed'", $today));
    $total_upcoming   = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$res_table} WHERE reservation_date>%s AND status='confirmed'", $today));
    $total_cancelled  = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$res_table} WHERE status='cancelled'"));
    $total_completed  = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$res_table} WHERE status='completed'"));
    $total_all_time   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$res_table}");

    $today_res = $wpdb->get_results($wpdb->prepare(
        "SELECT r.*, t.table_number, t.location, t.capacity FROM {$res_table} r
         JOIN {$tbl_table} t ON r.table_id = t.id
         WHERE r.reservation_date=%s AND r.status='confirmed'
         ORDER BY r.reservation_time ASC",
        $today
    ));

    // Upcoming reservations (next 7 days)
    $upcoming_res = $wpdb->get_results($wpdb->prepare(
        "SELECT r.*, t.table_number, t.location FROM {$res_table} r
         JOIN {$tbl_table} t ON r.table_id = t.id
         WHERE r.reservation_date > %s 
         AND r.reservation_date <= DATE_ADD(%s, INTERVAL 7 DAY)
         AND r.status='confirmed'
         ORDER BY r.reservation_date ASC, r.reservation_time ASC
         LIMIT 5",
        $today, $today
    ));

    ob_start(); ?>
    <div class="rr-overview-header">
        <div>
            <h2 style="font-family:var(--ff-display);font-size:24px;margin:0 0 4px;color:var(--rr-charcoal);">Dashboard</h2>
            <p style="color:var(--rr-muted);font-size:14px;margin:0;">Today is <?php echo date('l, F j, Y'); ?></p>
        </div>
        <button class="rr-btn rr-btn-primary" onclick="rrOpenNewReservationModal()">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Reservation
        </button>
    </div>

    <div class="rr-stats-row">
        <div class="rr-stat-card">
            <div class="rr-stat-icon" style="background:linear-gradient(135deg,#c9a84c,#b8963c);">
                <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            </div>
            <div class="rr-stat-info">
                <h4>Available Tables</h4>
                <div class="rr-stat-number"><?php echo $total_tables; ?></div>
                <div class="rr-stat-sub">ready for booking</div>
            </div>
        </div>
        <div class="rr-stat-card">
            <div class="rr-stat-icon" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);">
                <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="rr-stat-info">
                <h4>Today's Bookings</h4>
                <div class="rr-stat-number"><?php echo $total_today; ?></div>
                <div class="rr-stat-sub"><?php echo date('M j'); ?></div>
            </div>
        </div>
        <div class="rr-stat-card">
            <div class="rr-stat-icon" style="background:linear-gradient(135deg,#059669,#10b981);">
                <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="rr-stat-info">
                <h4>Upcoming</h4>
                <div class="rr-stat-number"><?php echo $total_upcoming; ?></div>
                <div class="rr-stat-sub">future confirmed</div>
            </div>
        </div>
        <div class="rr-stat-card">
            <div class="rr-stat-icon" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);">
                <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="rr-stat-info">
                <h4>Completed</h4>
                <div class="rr-stat-number"><?php echo $total_completed; ?></div>
                <div class="rr-stat-sub">all-time: <?php echo $total_all_time; ?></div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
        <!-- Today's Schedule -->
        <div class="rr-section">
        <div class="rr-section-header">
            <h3 class="rr-section-title">Today's Schedule — <?php echo date('F j, Y'); ?></h3>
            <a href="?tab=reservations&res_date=<?php echo $today; ?>" class="rr-btn rr-btn-ghost rr-btn-sm">View All</a>
        </div>
        <?php if (empty($today_res)): ?>
        <div class="rr-empty">
            <div class="rr-empty-icon">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <h4>No reservations today</h4>
            <p>Today's bookings will appear here.</p>
        </div>
        <?php else: ?>
        <div>
            <?php foreach ($today_res as $r): ?>
            <div class="rr-timeline-item">
                <div class="rr-timeline-time"><?php echo date('g:i A', strtotime($r->reservation_time)); ?></div>
                <div class="rr-timeline-info">
                    <div class="rr-timeline-name"><?php echo esc_html($r->customer_name); ?></div>
                    <div class="rr-timeline-meta">
                        Table <?php echo esc_html($r->table_number); ?> &middot;
                        <?php echo esc_html($r->location); ?> &middot;
                        <?php echo $r->num_guests; ?> guest<?php echo $r->num_guests > 1 ? 's' : ''; ?>
                        <?php if ($r->phone): ?> &middot; <?php echo esc_html($r->phone); ?><?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <button class="rr-btn rr-btn-success rr-btn-sm"
                            onclick="rrCompleteReservation('<?php echo $r->id; ?>', '<?php echo esc_js($r->customer_name); ?>', '<?php echo $nonce; ?>')">
                        Complete
                    </button>
                    <button class="rr-btn rr-btn-ghost rr-btn-sm"
                            onclick="rrEditReservationModal(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES); ?>, '<?php echo $nonce; ?>')">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="rr-btn rr-btn-ghost rr-btn-sm"
                            onclick="rrViewReservation(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES); ?>)">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

        <!-- Upcoming Next 7 Days -->
        <div class="rr-section">
            <div class="rr-section-header">
                <h3 class="rr-section-title">Coming Up (Next 7 Days)</h3>
                <a href="?tab=reservations&res_status=confirmed" class="rr-btn rr-btn-ghost rr-btn-sm">View All</a>
            </div>
            <?php if (empty($upcoming_res)): ?>
            <div class="rr-empty" style="padding:32px;">
                <div class="rr-empty-icon">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <h4>No upcoming reservations</h4>
                <p>Reservations for the next week will show here.</p>
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <?php foreach ($upcoming_res as $r): ?>
                <div class="rr-upcoming-card">
                    <div class="rr-upcoming-date">
                        <div class="rr-upcoming-day"><?php echo date('d', strtotime($r->reservation_date)); ?></div>
                        <div class="rr-upcoming-month"><?php echo date('M', strtotime($r->reservation_date)); ?></div>
                    </div>
                    <div class="rr-upcoming-info">
                        <div class="rr-upcoming-name"><?php echo esc_html($r->customer_name); ?></div>
                        <div class="rr-upcoming-meta">
                            <?php echo date('g:i A', strtotime($r->reservation_time)); ?> &middot;
                            Table <?php echo esc_html($r->table_number); ?> &middot;
                            <?php echo $r->num_guests; ?> guest<?php echo $r->num_guests > 1 ? 's' : ''; ?>
                        </div>
                    </div>
                    <button class="rr-btn rr-btn-ghost rr-btn-sm"
                            onclick="rrViewReservation(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES); ?>)">
                        Details
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .rr-overview-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
    }
    .rr-upcoming-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 14px;
        background: #fff;
        border: 1px solid var(--rr-border);
        border-radius: var(--rr-radius-sm);
        transition: transform .15s, box-shadow .15s;
    }
    .rr-upcoming-card:hover {
        transform: translateX(3px);
        box-shadow: 0 2px 8px rgba(26,26,46,.08);
    }
    .rr-upcoming-date {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, var(--rr-gold), #b8963c);
        border-radius: 8px;
        flex-shrink: 0;
    }
    .rr-upcoming-day {
        font-family: var(--ff-display);
        font-size: 20px;
        font-weight: 700;
        color: #fff;
        line-height: 1;
    }
    .rr-upcoming-month {
        font-size: 11px;
        font-weight: 600;
        color: rgba(255,255,255,.85);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-top: 2px;
    }
    .rr-upcoming-info {
        flex: 1;
    }
    .rr-upcoming-name {
        font-weight: 600;
        font-size: 14px;
        color: var(--rr-charcoal);
        margin-bottom: 3px;
    }
    .rr-upcoming-meta {
        font-size: 12px;
        color: var(--rr-muted);
    }
    </style>

    <script>
    const rrNonce = '<?php echo $nonce; ?>';

    function rrOpenNewReservationModal() {
        const body = `
            <div id="rr-new-res-msg"></div>
            <p style="font-size:13px;color:var(--rr-muted);margin:0 0 18px;">Fill in the details below to create a new reservation.</p>
            <div class="rr-form-grid">
                <div class="rr-form-group">
                    <label>Customer Name *</label>
                    <input type="text" id="nr-name" class="rr-input" placeholder="John Santos" required>
                </div>
                <div class="rr-form-group">
                    <label>Phone Number *</label>
                    <input type="text" id="nr-phone" class="rr-input" placeholder="09xx-xxx-xxxx" required>
                </div>
                <div class="rr-form-group">
                    <label>Email</label>
                    <input type="email" id="nr-email" class="rr-input" placeholder="optional">
                </div>
                <div class="rr-form-group">
                    <label>Number of Guests *</label>
                    <input type="number" id="nr-guests" class="rr-input" min="1" max="30" value="2" required>
                </div>
                <div class="rr-form-group">
                    <label>Date *</label>
                    <input type="date" id="nr-date" class="rr-input" required>
                </div>
                <div class="rr-form-group">
                    <label>Time *</label>
                    <input type="time" id="nr-time" class="rr-input" value="12:00" required>
                </div>
                <div class="rr-form-group full">
                    <label>Table *</label>
                    <select id="nr-table" class="rr-select" required>
                        <option value="">— Select date/time first to see available tables —</option>
                    </select>
                </div>
                <div class="rr-form-group full">
                    <label>Special Requests</label>
                    <textarea id="nr-notes" class="rr-textarea" placeholder="Dietary needs, seating preferences..."></textarea>
                </div>
            </div>
            <hr class="rr-divider">
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button class="rr-btn rr-btn-ghost" onclick="rrCloseModal()">Cancel</button>
                <button class="rr-btn rr-btn-ghost rr-btn-sm" onclick="rrLoadAvailTables()" style="margin-right:auto;">
                    Check Availability
                </button>
                <button class="rr-btn rr-btn-primary" id="nr-submit-btn" onclick="rrSubmitReservation()">
                    Confirm Reservation
                </button>
            </div>
        `;
        rrOpenModal('New Reservation', body);
        document.getElementById('nr-date').min = new Date().toISOString().split('T')[0];
        ['nr-date', 'nr-time', 'nr-guests'].forEach(id => {
            document.getElementById(id).addEventListener('change', rrLoadAvailTables);
        });
    }

    function rrLoadAvailTables() {
        const date   = document.getElementById('nr-date').value;
        const time   = document.getElementById('nr-time').value;
        const guests = document.getElementById('nr-guests').value;
        const sel    = document.getElementById('nr-table');
        if (!date || !time || !guests) return;

        sel.innerHTML = '<option>Loading available tables...</option>';
        sel.disabled = true;

        const fd = new FormData();
        fd.append('action', 'rr_get_available_tables');
        fd.append('date', date);
        fd.append('time', time);
        fd.append('guests', guests);
        fd.append('nonce', rrNonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            sel.disabled = false;
            if (json.success && json.data.tables.length) {
                sel.innerHTML = '<option value="">— Choose a table —</option>' +
                    json.data.tables.map(t =>
                        `<option value="${t.id}">Table ${t.table_number} — ${t.location} (seats ${t.capacity})</option>`
                    ).join('');
            } else {
                sel.innerHTML = '<option value="">No tables available for this date/time</option>';
            }
        });
    }

    function rrSubmitReservation() {
        const name   = document.getElementById('nr-name').value.trim();
        const phone  = document.getElementById('nr-phone').value.trim();
        const email  = document.getElementById('nr-email').value.trim();
        const guests = document.getElementById('nr-guests').value;
        const date   = document.getElementById('nr-date').value;
        const time   = document.getElementById('nr-time').value;
        const table  = document.getElementById('nr-table').value;
        const notes  = document.getElementById('nr-notes').value.trim();
        const msgEl  = document.getElementById('rr-new-res-msg');

        if (!name || !phone || !guests || !date || !time || !table) {
            msgEl.innerHTML = '<div class="rr-notice rr-notice-error">Please fill in all required fields and select a table.</div>';
            return;
        }

        const btn = document.getElementById('nr-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="rr-spinner"></span> Confirming...';

        const fd = new FormData();
        fd.append('action', 'rr_make_reservation');
        fd.append('customer_name', name);
        fd.append('phone', phone);
        fd.append('email', email);
        fd.append('num_guests', guests);
        fd.append('reservation_date', date);
        fd.append('reservation_time', time);
        fd.append('table_id', table);
        fd.append('special_requests', notes);
        fd.append('nonce', rrNonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                msgEl.innerHTML = '<div class="rr-notice rr-notice-success">' + json.data.message + '</div>';
                setTimeout(() => location.reload(), 1500);
            } else {
                msgEl.innerHTML = '<div class="rr-notice rr-notice-error">' + json.data.message + '</div>';
                btn.disabled = false;
                btn.textContent = 'Confirm Reservation';
            }
        });
    }

    function rrCompleteReservation(id, name, nonce) {
        if (!confirm(`Mark reservation for "${name}" as completed?`)) return;
        const fd = new FormData();
        fd.append('action', 'rr_complete_reservation');
        fd.append('reservation_id', id);
        fd.append('nonce', nonce);
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            if (json.success) location.reload();
            else alert(json.data.message);
        });
    }

    function rrViewReservation(r) {
        const statusColors = {confirmed:'#dcfce7|#15803d', cancelled:'#fee2e2|#dc2626', completed:'#dbeafe|#1d4ed8', no_show:'#fef3c7|#b45309'};
        const [bg, fg] = (statusColors[r.status] || '#f3f4f6|#6b7280').split('|');
        const body = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:14px;">
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Reservation ID</div>
                     <code style="background:#f5f0e8;padding:3px 8px;border-radius:4px;">${r.rand_id}</code></div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Status</div>
                     <span class="rr-badge rr-badge-${r.status}">${r.status.replace('_',' ')}</span></div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Customer</div>
                     <strong>${r.customer_name}</strong></div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Phone</div>${r.phone}</div>
                ${r.email ? `<div style="grid-column:1/-1"><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Email</div>${r.email}</div>` : ''}
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Date</div>${new Date(r.reservation_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})}</div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Time</div>${r.reservation_time}</div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Table</div>Table ${r.table_number} — ${r.location}</div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Guests</div>${r.num_guests}</div>
                ${r.special_requests ? `<div style="grid-column:1/-1"><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Special Requests</div>${r.special_requests}</div>` : ''}
                ${r.cancellation_reason ? `<div style="grid-column:1/-1"><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Cancellation Reason</div>${r.cancellation_reason}</div>` : ''}
                <div style="grid-column:1/-1;font-size:12px;color:var(--rr-muted);border-top:1px solid var(--rr-border);padding-top:12px;margin-top:4px;">Booked on ${new Date(r.created_at).toLocaleString()}</div>
            </div>
            <hr class="rr-divider">
            <div style="text-align:right;"><button class="rr-btn rr-btn-ghost" onclick="rrCloseModal()">Close</button></div>
        `;
        rrOpenModal('Reservation Details', body);
    }
    </script>
    <?php
    return ob_get_clean();
}

/* ---- RESERVATIONS TAB ---- */
function rr_reservations_tab($business_id) {
    global $wpdb;
    $res_table = $wpdb->prefix . 'rr_reservations';
    $tbl_table = $wpdb->prefix . 'rr_tables';
    $nonce     = wp_create_nonce('rr_nonce');

    $filter_status = isset($_GET['res_status']) ? sanitize_text_field($_GET['res_status']) : 'all';
    $filter_date   = isset($_GET['res_date'])   ? sanitize_text_field($_GET['res_date'])   : '';
    $search_query  = isset($_GET['res_search']) ? sanitize_text_field($_GET['res_search']) : '';

    $where = [];
    if ($filter_status !== 'all') $where[] = $wpdb->prepare("r.status = %s", $filter_status);
    if ($filter_date)             $where[] = $wpdb->prepare("r.reservation_date = %s", $filter_date);
    if ($search_query)            $where[] = $wpdb->prepare("(r.customer_name LIKE %s OR r.phone LIKE %s OR r.email LIKE %s OR r.rand_id LIKE %s)", 
                                                             '%' . $wpdb->esc_like($search_query) . '%',
                                                             '%' . $wpdb->esc_like($search_query) . '%',
                                                             '%' . $wpdb->esc_like($search_query) . '%',
                                                             '%' . $wpdb->esc_like($search_query) . '%');
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $reservations = $wpdb->get_results(
        "SELECT r.*, t.table_number, t.location, t.capacity
         FROM {$res_table} r
         JOIN {$tbl_table} t ON r.table_id = t.id
         {$where_sql}
         ORDER BY r.reservation_date DESC, r.reservation_time DESC
         LIMIT 100"
    );

    $base_url = '?tab=reservations';
    $active_filters = [];
    if ($filter_status !== 'all') $active_filters[] = "status={$filter_status}";
    if ($filter_date) $active_filters[] = "date={$filter_date}";
    if ($search_query) $active_filters[] = "search=\"{$search_query}\"";

    ob_start(); ?>
    <div class="rr-section">
        <div class="rr-section-header">
            <h3 class="rr-section-title">All Reservations <?php if (!empty($active_filters)): ?><span style="font-weight:400;color:var(--rr-muted);font-size:13px;">(filtered: <?php echo implode(', ', $active_filters); ?>)</span><?php endif; ?></h3>
            <button class="rr-btn rr-btn-primary rr-btn-sm" onclick="rrOpenNewReservationModal()">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Reservation
            </button>
        </div>

        <!-- Search & Filters -->
        <div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
            <form method="get" style="flex:1;min-width:280px;display:flex;gap:8px;">
                <input type="hidden" name="tab" value="reservations">
                <?php if ($filter_status !== 'all'): ?><input type="hidden" name="res_status" value="<?php echo esc_attr($filter_status); ?>"><?php endif; ?>
                <?php if ($filter_date): ?><input type="hidden" name="res_date" value="<?php echo esc_attr($filter_date); ?>"><?php endif; ?>
                <div style="position:relative;flex:1;">
                    <input type="text" name="res_search" class="rr-input" placeholder="Search by name, phone, email, or ID..." 
                           value="<?php echo esc_attr($search_query); ?>" style="padding-left:36px;">
                    <svg width="16" height="16" fill="none" stroke="var(--rr-muted)" stroke-width="2" viewBox="0 0 24 24" 
                         style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </div>
                <button type="submit" class="rr-btn rr-btn-secondary rr-btn-sm">Search</button>
                <?php if ($search_query): ?>
                <a href="?tab=reservations<?php echo $filter_status !== 'all' ? '&res_status=' . $filter_status : ''; ?><?php echo $filter_date ? '&res_date=' . $filter_date : ''; ?>" 
                   class="rr-btn rr-btn-ghost rr-btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Filters -->
        <div class="rr-filters">
            <span class="rr-filter-label">Status:</span>
            <?php
            $base = $base_url . ($search_query ? '&res_search=' . urlencode($search_query) : '');
            $statuses = ['all'=>'All','confirmed'=>'Confirmed','cancelled'=>'Cancelled','completed'=>'Completed','no_show'=>'No Show'];
            foreach ($statuses as $k => $v):
                $active = $filter_status === $k ? 'rr-btn-secondary' : 'rr-btn-ghost';
                $url = $base . '&res_status=' . $k . ($filter_date ? '&res_date=' . $filter_date : '');
                echo "<a href='{$url}' class='rr-btn rr-btn-sm {$active}'>{$v}</a>";
            endforeach;
            ?>
            <span class="rr-filter-label" style="margin-left:14px;">Date:</span>
            <input type="date" class="rr-input" style="width:auto;" value="<?php echo esc_attr($filter_date); ?>"
                   onchange="window.location='?tab=reservations&res_status=<?php echo $filter_status; ?><?php echo $search_query ? '&res_search=' . urlencode($search_query) : ''; ?>&res_date='+this.value">
            <?php if ($filter_date || $search_query || $filter_status !== 'all'): ?>
            <a href="?tab=reservations" class="rr-btn rr-btn-danger rr-btn-sm">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Clear All Filters
            </a>
            <?php endif; ?>
        </div>

        <!-- Table -->
        <?php if (empty($reservations)): ?>
        <div class="rr-empty">
            <div class="rr-empty-icon">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 000 4h6a2 2 0 000-4M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <h4>No reservations found</h4>
            <p>Try adjusting the filters above.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="rr-table">
                <thead>
                    <tr>
                        <th>Reservation ID</th>
                        <th>Customer</th>
                        <th>Table</th>
                        <th>Date &amp; Time</th>
                        <th>Guests</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td><code style="font-size:11px;background:#f5f0e8;padding:2px 6px;border-radius:4px;"><?php echo esc_html($r->rand_id); ?></code></td>
                        <td>
                            <div style="font-weight:600;"><?php echo esc_html($r->customer_name); ?></div>
                            <?php if ($r->email): ?><div style="font-size:12px;color:var(--rr-muted);"><?php echo esc_html($r->email); ?></div><?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:600;">Table <?php echo esc_html($r->table_number); ?></div>
                            <div style="font-size:12px;color:var(--rr-muted);"><?php echo esc_html($r->location); ?></div>
                        </td>
                        <td>
                            <div><?php echo date('M j, Y', strtotime($r->reservation_date)); ?></div>
                            <div style="font-size:12px;color:var(--rr-muted);"><?php echo date('g:i A', strtotime($r->reservation_time)); ?></div>
                        </td>
                        <td><?php echo $r->num_guests; ?></td>
                        <td><?php echo esc_html($r->phone); ?></td>
                        <td><span class="rr-badge rr-badge-<?php echo $r->status; ?>"><?php echo ucfirst(str_replace('_',' ',$r->status)); ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <?php if ($r->status === 'confirmed'): ?>
                                <button class="rr-btn rr-btn-success rr-btn-sm"
                                        onclick="rrCompleteReservation('<?php echo $r->id; ?>', '<?php echo esc_js($r->customer_name); ?>', '<?php echo $nonce; ?>')">
                                    Complete
                                </button>
                                <button class="rr-btn rr-btn-ghost rr-btn-sm"
                                        onclick="rrEditReservationModal(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES); ?>, '<?php echo $nonce; ?>')">
                                    Edit
                                </button>
                                <button class="rr-btn rr-btn-danger rr-btn-sm"
                                        onclick="rrCancelModal('<?php echo $r->id; ?>', '<?php echo esc_js($r->customer_name); ?>', '<?php echo $nonce; ?>')">
                                    Cancel
                                </button>
                                <?php endif; ?>
                                <button class="rr-btn rr-btn-ghost rr-btn-sm"
                                        onclick="rrViewReservation(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES); ?>)">
                                    View
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
    const rrNonce = '<?php echo $nonce; ?>';

    function rrOpenNewReservationModal() {
        const body = `
            <div id="rr-new-res-msg"></div>
            <p style="font-size:13px;color:var(--rr-muted);margin:0 0 18px;">Fill in the details below to create a new reservation.</p>
            <div class="rr-form-grid">
                <div class="rr-form-group">
                    <label>Customer Name *</label>
                    <input type="text" id="nr-name" class="rr-input" placeholder="John Santos" required>
                </div>
                <div class="rr-form-group">
                    <label>Phone Number *</label>
                    <input type="text" id="nr-phone" class="rr-input" placeholder="09xx-xxx-xxxx" required>
                </div>
                <div class="rr-form-group">
                    <label>Email</label>
                    <input type="email" id="nr-email" class="rr-input" placeholder="optional">
                </div>
                <div class="rr-form-group">
                    <label>Number of Guests *</label>
                    <input type="number" id="nr-guests" class="rr-input" min="1" max="30" value="2" required>
                </div>
                <div class="rr-form-group">
                    <label>Date *</label>
                    <input type="date" id="nr-date" class="rr-input" required>
                </div>
                <div class="rr-form-group">
                    <label>Time *</label>
                    <input type="time" id="nr-time" class="rr-input" value="12:00" required>
                </div>
                <div class="rr-form-group full">
                    <label>Table *</label>
                    <select id="nr-table" class="rr-select" required>
                        <option value="">— Select date/time first to see available tables —</option>
                    </select>
                </div>
                <div class="rr-form-group full">
                    <label>Special Requests</label>
                    <textarea id="nr-notes" class="rr-textarea" placeholder="Dietary needs, seating preferences..."></textarea>
                </div>
            </div>
            <hr class="rr-divider">
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button class="rr-btn rr-btn-ghost" onclick="rrCloseModal()">Cancel</button>
                <button class="rr-btn rr-btn-ghost rr-btn-sm" onclick="rrLoadAvailTables()" style="margin-right:auto;">
                    Check Availability
                </button>
                <button class="rr-btn rr-btn-primary" id="nr-submit-btn" onclick="rrSubmitReservation()">
                    Confirm Reservation
                </button>
            </div>
        `;
        rrOpenModal('New Reservation', body);
        // Set minimum date to today
        document.getElementById('nr-date').min = new Date().toISOString().split('T')[0];
        // Auto-load tables when date/time changes
        ['nr-date', 'nr-time', 'nr-guests'].forEach(id => {
            document.getElementById(id).addEventListener('change', rrLoadAvailTables);
        });
    }

    function rrLoadAvailTables() {
        const date   = document.getElementById('nr-date').value;
        const time   = document.getElementById('nr-time').value;
        const guests = document.getElementById('nr-guests').value;
        const sel    = document.getElementById('nr-table');
        if (!date || !time || !guests) return;

        sel.innerHTML = '<option>Loading available tables...</option>';
        sel.disabled = true;

        const fd = new FormData();
        fd.append('action', 'rr_get_available_tables');
        fd.append('date', date);
        fd.append('time', time);
        fd.append('guests', guests);
        fd.append('nonce', rrNonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            sel.disabled = false;
            if (json.success && json.data.tables.length) {
                sel.innerHTML = '<option value="">— Choose a table —</option>' +
                    json.data.tables.map(t =>
                        `<option value="${t.id}">Table ${t.table_number} — ${t.location} (seats ${t.capacity})</option>`
                    ).join('');
            } else {
                sel.innerHTML = '<option value="">No tables available for this date/time</option>';
            }
        });
    }

    function rrSubmitReservation() {
        const name   = document.getElementById('nr-name').value.trim();
        const phone  = document.getElementById('nr-phone').value.trim();
        const email  = document.getElementById('nr-email').value.trim();
        const guests = document.getElementById('nr-guests').value;
        const date   = document.getElementById('nr-date').value;
        const time   = document.getElementById('nr-time').value;
        const table  = document.getElementById('nr-table').value;
        const notes  = document.getElementById('nr-notes').value.trim();
        const msgEl  = document.getElementById('rr-new-res-msg');

        if (!name || !phone || !guests || !date || !time || !table) {
            msgEl.innerHTML = '<div class="rr-notice rr-notice-error">Please fill in all required fields and select a table.</div>';
            return;
        }

        const btn = document.getElementById('nr-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="rr-spinner"></span> Confirming...';

        const fd = new FormData();
        fd.append('action', 'rr_make_reservation');
        fd.append('customer_name', name);
        fd.append('phone', phone);
        fd.append('email', email);
        fd.append('num_guests', guests);
        fd.append('reservation_date', date);
        fd.append('reservation_time', time);
        fd.append('table_id', table);
        fd.append('special_requests', notes);
        fd.append('nonce', rrNonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                msgEl.innerHTML = '<div class="rr-notice rr-notice-success">' + json.data.message + '</div>';
                setTimeout(() => location.reload(), 1500);
            } else {
                msgEl.innerHTML = '<div class="rr-notice rr-notice-error">' + json.data.message + '</div>';
                btn.disabled = false;
                btn.textContent = 'Confirm Reservation';
            }
        });
    }

    function rrCancelModal(id, name, nonce) {
        const body = `
            <p>You are about to <strong>cancel</strong> the reservation for <strong>${name}</strong>.</p>
            <div class="rr-form-group" style="margin-top:14px;">
                <label>Reason for Cancellation</label>
                <textarea id="cancel-reason" class="rr-textarea" placeholder="Optional reason..."></textarea>
            </div>
            <div id="cancel-msg" style="margin-top:10px;"></div>
            <hr class="rr-divider">
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button class="rr-btn rr-btn-ghost" onclick="rrCloseModal()">Keep Reservation</button>
                <button class="rr-btn rr-btn-danger" id="confirm-cancel-btn"
                        onclick="rrDoCancel(${id},'${nonce}')">
                    Cancel Reservation
                </button>
            </div>
        `;
        rrOpenModal('Cancel Reservation', body);
    }

    function rrDoCancel(id, nonce) {
        const reason = document.getElementById('cancel-reason').value.trim();
        const btn    = document.getElementById('confirm-cancel-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="rr-spinner" style="border-top-color:#dc2626;border-color:rgba(220,38,38,.2);"></span> Cancelling...';

        const fd = new FormData();
        fd.append('action', 'rr_cancel_reservation');
        fd.append('reservation_id', id);
        fd.append('reason', reason);
        fd.append('nonce', nonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            const msg = document.getElementById('cancel-msg');
            msg.innerHTML = '<div class="rr-notice rr-notice-' + (json.success ? 'success' : 'error') + '">' + json.data.message + '</div>';
            if (json.success) setTimeout(() => location.reload(), 1200);
            else { btn.disabled = false; btn.textContent = 'Cancel Reservation'; }
        });
    }

    function rrEditReservationModal(reservation, nonce) {
        const r = reservation;
        const body = `
            <div id="edit-res-msg"></div>
            <p style="font-size:13px;color:var(--rr-muted);margin:0 0 18px;">Update reservation details for <strong>${r.customer_name}</strong></p>
            <div class="rr-form-grid">
                <div class="rr-form-group">
                    <label>Customer Name *</label>
                    <input type="text" id="edit-name" class="rr-input" value="${r.customer_name}" required>
                </div>
                <div class="rr-form-group">
                    <label>Phone Number *</label>
                    <input type="text" id="edit-phone" class="rr-input" value="${r.phone}" required>
                </div>
                <div class="rr-form-group">
                    <label>Email</label>
                    <input type="email" id="edit-email" class="rr-input" value="${r.email || ''}">
                </div>
                <div class="rr-form-group">
                    <label>Number of Guests *</label>
                    <input type="number" id="edit-guests" class="rr-input" min="1" max="30" value="${r.num_guests}" required>
                </div>
                <div class="rr-form-group">
                    <label>Date *</label>
                    <input type="date" id="edit-date" class="rr-input" value="${r.reservation_date}" required>
                </div>
                <div class="rr-form-group">
                    <label>Time *</label>
                    <input type="time" id="edit-time" class="rr-input" value="${r.reservation_time}" required>
                </div>
                <div class="rr-form-group full">
                    <label>Table *</label>
                    <select id="edit-table" class="rr-select" required>
                        <option value="${r.table_id}">Current: Table ${r.table_number} — ${r.location}</option>
                    </select>
                    <button type="button" class="rr-btn rr-btn-ghost rr-btn-sm" style="margin-top:6px;width:100%;justify-content:center;" onclick="rrLoadAvailTablesForEdit()">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Check New Available Tables
                    </button>
                </div>
                <div class="rr-form-group full">
                    <label>Special Requests</label>
                    <textarea id="edit-notes" class="rr-textarea">${r.special_requests || ''}</textarea>
                </div>
            </div>
            <hr class="rr-divider">
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button class="rr-btn rr-btn-ghost" onclick="rrCloseModal()">Cancel</button>
                <button class="rr-btn rr-btn-primary" id="edit-submit-btn" onclick="rrSubmitEditReservation(${r.id}, ${r.table_id}, '${nonce}')">
                    Update Reservation
                </button>
            </div>
        `;
        rrOpenModal('Edit Reservation', body);
        document.getElementById('edit-date').min = new Date().toISOString().split('T')[0];
    }

    function rrLoadAvailTablesForEdit() {
        const date   = document.getElementById('edit-date').value;
        const time   = document.getElementById('edit-time').value;
        const guests = document.getElementById('edit-guests').value;
        const sel    = document.getElementById('edit-table');
        if (!date || !time || !guests) {
            document.getElementById('edit-res-msg').innerHTML = '<div class="rr-notice rr-notice-warning">Please fill in date, time, and number of guests first.</div>';
            return;
        }

        const currentOption = sel.options[0].cloneNode(true);
        sel.innerHTML = '<option>Loading available tables...</option>';
        sel.disabled = true;
        document.getElementById('edit-res-msg').innerHTML = '';

        const fd = new FormData();
        fd.append('action', 'rr_get_available_tables');
        fd.append('date', date);
        fd.append('time', time);
        fd.append('guests', guests);
        fd.append('nonce', rrNonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            sel.disabled = false;
            if (json.success && json.data.tables.length > 0) {
                sel.innerHTML = '';
                sel.appendChild(currentOption);
                json.data.tables.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = `Table ${t.table_number} — ${t.location} (seats ${t.capacity})`;
                    sel.appendChild(opt);
                });
            } else {
                sel.innerHTML = '';
                sel.appendChild(currentOption);
                const opt = document.createElement('option');
                opt.disabled = true;
                opt.textContent = 'No other tables available for this date/time';
                sel.appendChild(opt);
                document.getElementById('edit-res-msg').innerHTML = '<div class="rr-notice rr-notice-warning">No alternative tables available. You can keep the current table or choose a different time.</div>';
            }
        });
    }

    function rrSubmitEditReservation(resId, originalTableId, nonce) {
        const name   = document.getElementById('edit-name').value.trim();
        const phone  = document.getElementById('edit-phone').value.trim();
        const email  = document.getElementById('edit-email').value.trim();
        const guests = document.getElementById('edit-guests').value;
        const date   = document.getElementById('edit-date').value;
        const time   = document.getElementById('edit-time').value;
        const table  = document.getElementById('edit-table').value;
        const notes  = document.getElementById('edit-notes').value.trim();
        const msgEl  = document.getElementById('edit-res-msg');

        if (!name || !phone || !guests || !date || !time || !table) {
            msgEl.innerHTML = '<div class="rr-notice rr-notice-error">Please fill in all required fields.</div>';
            return;
        }

        const btn = document.getElementById('edit-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="rr-spinner"></span> Updating...';

        const fd = new FormData();
        fd.append('action', 'rr_update_reservation');
        fd.append('reservation_id', resId);
        fd.append('customer_name', name);
        fd.append('phone', phone);
        fd.append('email', email);
        fd.append('num_guests', guests);
        fd.append('reservation_date', date);
        fd.append('reservation_time', time);
        fd.append('table_id', table);
        fd.append('original_table_id', originalTableId);
        fd.append('special_requests', notes);
        fd.append('nonce', nonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                msgEl.innerHTML = '<div class="rr-notice rr-notice-success">' + json.data.message + '</div>';
                setTimeout(() => location.reload(), 1500);
            } else {
                msgEl.innerHTML = '<div class="rr-notice rr-notice-error">' + json.data.message + '</div>';
                btn.disabled = false;
                btn.textContent = 'Update Reservation';
            }
        });
    }

    function rrCompleteReservation(id, name, nonce) {
        if (!confirm(`Mark reservation for "${name}" as completed?`)) return;
        const fd = new FormData();
        fd.append('action', 'rr_complete_reservation');
        fd.append('reservation_id', id);
        fd.append('nonce', nonce);
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            if (json.success) location.reload();
            else alert(json.data.message);
        });
    }

    function rrViewReservation(r) {
        const statusColors = {confirmed:'#dcfce7|#15803d', cancelled:'#fee2e2|#dc2626', completed:'#dbeafe|#1d4ed8', no_show:'#fef3c7|#b45309'};
        const [bg, fg] = (statusColors[r.status] || '#f3f4f6|#6b7280').split('|');
        const body = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:14px;">
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Reservation ID</div>
                     <code style="background:#f5f0e8;padding:3px 8px;border-radius:4px;">${r.rand_id}</code></div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Status</div>
                     <span class="rr-badge rr-badge-${r.status}">${r.status.replace('_',' ')}</span></div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Customer</div>
                     <strong>${r.customer_name}</strong></div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Phone</div>${r.phone}</div>
                ${r.email ? `<div style="grid-column:1/-1"><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Email</div>${r.email}</div>` : ''}
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Date</div>${new Date(r.reservation_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})}</div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Time</div>${r.reservation_time}</div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Table</div>Table ${r.table_number} — ${r.location}</div>
                <div><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Guests</div>${r.num_guests}</div>
                ${r.special_requests ? `<div style="grid-column:1/-1"><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Special Requests</div>${r.special_requests}</div>` : ''}
                ${r.cancellation_reason ? `<div style="grid-column:1/-1"><div style="font-size:11px;color:var(--rr-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Cancellation Reason</div>${r.cancellation_reason}</div>` : ''}
                <div style="grid-column:1/-1;font-size:12px;color:var(--rr-muted);border-top:1px solid var(--rr-border);padding-top:12px;margin-top:4px;">Booked on ${new Date(r.created_at).toLocaleString()}</div>
            </div>
            <hr class="rr-divider">
            <div style="text-align:right;"><button class="rr-btn rr-btn-ghost" onclick="rrCloseModal()">Close</button></div>
        `;
        rrOpenModal('Reservation Details', body);
    }
    </script>
    <?php
    return ob_get_clean();
}

/* ---- TABLES TAB ---- */
function rr_tables_tab($business_id) {
    global $wpdb;
    $tbl   = $wpdb->prefix . 'rr_tables';
    $res   = $wpdb->prefix . 'rr_reservations';
    $nonce = wp_create_nonce('rr_nonce');

    $view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';

    $tables = $wpdb->get_results(
        "SELECT t.*,
            COUNT(CASE WHEN r.status='confirmed' AND r.reservation_date >= CURDATE() THEN 1 END) AS upcoming_count
         FROM {$tbl} t
         LEFT JOIN {$res} r ON r.table_id = t.id
         GROUP BY t.id
         ORDER BY t.table_number ASC"
    );

    $total_capacity = array_sum(array_column($tables, 'capacity'));
    $available_tables = count(array_filter($tables, fn($t) => $t->status === 'available'));

    ob_start(); ?>
    <!-- Quick Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:24px;">
        <div style="background:linear-gradient(135deg,#059669,#10b981);padding:18px;border-radius:var(--rr-radius);color:#fff;">
            <div style="font-size:11px;font-weight:600;opacity:.85;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Total Tables</div>
            <div style="font-family:var(--ff-display);font-size:28px;font-weight:700;line-height:1;"><?php echo count($tables); ?></div>
        </div>
        <div style="background:linear-gradient(135deg,#c9a84c,#b8963c);padding:18px;border-radius:var(--rr-radius);color:#fff;">
            <div style="font-size:11px;font-weight:600;opacity:.85;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Available Now</div>
            <div style="font-family:var(--ff-display);font-size:28px;font-weight:700;line-height:1;"><?php echo $available_tables; ?></div>
        </div>
        <div style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);padding:18px;border-radius:var(--rr-radius);color:#fff;">
            <div style="font-size:11px;font-weight:600;opacity:.85;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Total Capacity</div>
            <div style="font-family:var(--ff-display);font-size:28px;font-weight:700;line-height:1;"><?php echo $total_capacity; ?> <span style="font-size:14px;opacity:.8;">seats</span></div>
        </div>
    </div>

    <div class="rr-section">
        <div class="rr-section-header">
            <h3 class="rr-section-title">Manage Tables</h3>
            <div style="display:flex;gap:8px;">
                <div style="background:var(--rr-cream);border-radius:6px;padding:3px;display:flex;gap:2px;">
                    <a href="?tab=tables&view=list" class="rr-view-btn <?php echo $view_mode === 'list' ? 'active' : ''; ?>" title="List View">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </a>
                    <a href="?tab=tables&view=grid" class="rr-view-btn <?php echo $view_mode === 'grid' ? 'active' : ''; ?>" title="Grid View">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </a>
                </div>
                <button class="rr-btn rr-btn-primary rr-btn-sm" onclick="rrOpenAddTableModal()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Table
                </button>
            </div>
        </div>

        <?php if (empty($tables)): ?>
        <div class="rr-empty">
            <div class="rr-empty-icon">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            </div>
            <h4>No tables added yet</h4>
            <p>Add your restaurant tables to start accepting reservations.</p>
        </div>
        <?php elseif ($view_mode === 'grid'): ?>
        <!-- Grid View -->
        <div class="rr-tables-management-grid">
            <?php foreach ($tables as $t): ?>
            <div class="rr-table-mgmt-card rr-status-<?php echo $t->status; ?>">
                <div class="rr-table-mgmt-header">
                    <div>
                        <div class="rr-table-mgmt-num">Table <?php echo esc_html($t->table_number); ?></div>
                        <div class="rr-table-mgmt-loc"><?php echo esc_html($t->location); ?></div>
                    </div>
                    <span class="rr-badge rr-badge-<?php echo $t->status; ?>"><?php echo ucfirst($t->status); ?></span>
                </div>
                <div class="rr-table-mgmt-body">
                    <div class="rr-table-mgmt-stat">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                        <span><?php echo $t->capacity; ?> seats</span>
                    </div>
                    <div class="rr-table-mgmt-stat">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span><?php echo $t->upcoming_count; ?> upcoming</span>
                    </div>
                </div>
                <?php if ($t->notes): ?>
                <div class="rr-table-mgmt-notes"><?php echo esc_html($t->notes); ?></div>
                <?php endif; ?>
                <div class="rr-table-mgmt-actions">
                    <select class="rr-select rr-btn-sm" style="height:auto;padding:5px 8px;font-size:12px;flex:1;"
                            onchange="rrUpdateTableStatus(<?php echo $t->id; ?>, this.value, '<?php echo $nonce; ?>')">
                        <option value="available"    <?php selected($t->status,'available'); ?>>Available</option>
                        <option value="maintenance"  <?php selected($t->status,'maintenance'); ?>>Maintenance</option>
                        <option value="inactive"     <?php selected($t->status,'inactive'); ?>>Inactive</option>
                    </select>
                    <?php if ($t->upcoming_count == 0): ?>
                    <button class="rr-btn rr-btn-danger rr-btn-sm"
                            onclick="rrDeleteTable(<?php echo $t->id; ?>, 'Table <?php echo esc_js($t->table_number); ?>', '<?php echo $nonce; ?>')">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- List View -->
        <div style="overflow-x:auto;">
            <table class="rr-table">
                <thead>
                    <tr>
                        <th>Table Number</th>
                        <th>Capacity</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Upcoming</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $t): ?>
                    <tr>
                        <td><strong style="font-family:var(--ff-display);font-size:16px;">Table <?php echo esc_html($t->table_number); ?></strong></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:5px;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                                <?php echo $t->capacity; ?> seats
                            </div>
                        </td>
                        <td><?php echo esc_html($t->location); ?></td>
                        <td><span class="rr-badge rr-badge-<?php echo $t->status; ?>"><?php echo ucfirst($t->status); ?></span></td>
                        <td><?php echo $t->upcoming_count; ?> reservation<?php echo $t->upcoming_count != 1 ? 's' : ''; ?></td>
                        <td><span style="color:var(--rr-muted);font-size:13px;"><?php echo esc_html($t->notes ?: '—'); ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <select class="rr-select rr-btn-sm" style="height:auto;padding:5px 8px;font-size:12px;"
                                        onchange="rrUpdateTableStatus(<?php echo $t->id; ?>, this.value, '<?php echo $nonce; ?>')">
                                    <option value="available"    <?php selected($t->status,'available'); ?>>Available</option>
                                    <option value="maintenance"  <?php selected($t->status,'maintenance'); ?>>Maintenance</option>
                                    <option value="inactive"     <?php selected($t->status,'inactive'); ?>>Inactive</option>
                                </select>
                                <?php if ($t->upcoming_count == 0): ?>
                                <button class="rr-btn rr-btn-danger rr-btn-sm"
                                        onclick="rrDeleteTable(<?php echo $t->id; ?>, 'Table <?php echo esc_js($t->table_number); ?>', '<?php echo $nonce; ?>')">
                                    Delete
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function rrOpenAddTableModal() {
        const body = `
            <div id="add-table-msg"></div>
            <div class="rr-form-grid">
                <div class="rr-form-group">
                    <label>Table Number / Name *</label>
                    <input type="text" id="at-num" class="rr-input" placeholder="e.g. T11, Booth 1" required>
                </div>
                <div class="rr-form-group">
                    <label>Seating Capacity *</label>
                    <input type="number" id="at-cap" class="rr-input" min="1" max="50" value="4" required>
                </div>
                <div class="rr-form-group">
                    <label>Location / Area</label>
                    <input type="text" id="at-loc" class="rr-input" placeholder="Main Hall">
                </div>
                <div class="rr-form-group full">
                    <label>Notes</label>
                    <textarea id="at-notes" class="rr-textarea" placeholder="e.g. Near window, wheelchair accessible..."></textarea>
                </div>
            </div>
            <hr class="rr-divider">
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button class="rr-btn rr-btn-ghost" onclick="rrCloseModal()">Cancel</button>
                <button class="rr-btn rr-btn-primary" id="add-table-btn" onclick="rrDoAddTable()">Add Table</button>
            </div>
        `;
        rrOpenModal('Add New Table', body);
    }

    function rrDoAddTable() {
        const num   = document.getElementById('at-num').value.trim();
        const cap   = document.getElementById('at-cap').value;
        const loc   = document.getElementById('at-loc').value.trim();
        const notes = document.getElementById('at-notes').value.trim();
        const msg   = document.getElementById('add-table-msg');
        if (!num || !cap) { msg.innerHTML='<div class="rr-notice rr-notice-error">Table number and capacity are required.</div>'; return; }

        const btn = document.getElementById('add-table-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="rr-spinner"></span> Adding...';

        const fd = new FormData();
        fd.append('action', 'rr_add_table');
        fd.append('table_number', num);
        fd.append('capacity', cap);
        fd.append('location', loc || 'Main Hall');
        fd.append('notes', notes);
        fd.append('nonce', '<?php echo $nonce; ?>');

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            msg.innerHTML = '<div class="rr-notice rr-notice-' + (json.success ? 'success' : 'error') + '">' + json.data.message + '</div>';
            if (json.success) setTimeout(() => location.reload(), 1200);
            else { btn.disabled=false; btn.textContent='Add Table'; }
        });
    }

    function rrUpdateTableStatus(id, status, nonce) {
        const fd = new FormData();
        fd.append('action', 'rr_update_table_status');
        fd.append('table_id', id);
        fd.append('status', status);
        fd.append('nonce', nonce);
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => { if (!json.success) alert(json.data.message); });
    }

    function rrDeleteTable(id, name, nonce) {
        if (!confirm(`Delete ${name}? This cannot be undone.`)) return;
        const fd = new FormData();
        fd.append('action', 'rr_delete_table');
        fd.append('table_id', id);
        fd.append('nonce', nonce);
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            if (json.success) location.reload();
            else alert(json.data.message);
        });
    }
    </script>
    <?php
    return ob_get_clean();
}

/* ---- AVAILABILITY TAB ---- */
function rr_availability_tab($business_id) {
    global $wpdb;
    $tbl_table = $wpdb->prefix . 'rr_tables';
    $res_table = $wpdb->prefix . 'rr_reservations';
    $check_date = isset($_GET['av_date']) ? sanitize_text_field($_GET['av_date']) : date('Y-m-d');
    $check_time = isset($_GET['av_time']) ? sanitize_text_field($_GET['av_time']) : '';

    // Get ALL tables (not filtered by business_id for now, since seed creates them with business_id=1)
    // In production, you'd want to filter by business_id properly
    $tables = $wpdb->get_results(
        "SELECT * FROM {$tbl_table} WHERE status='available' ORDER BY table_number ASC"
    );

    $booked_table_ids = [];
    if ($check_time) {
        // Get booked tables for ANY business (or you can filter by business_id if needed)
        $booked = $wpdb->get_col($wpdb->prepare(
            "SELECT table_id FROM {$res_table}
             WHERE reservation_date=%s AND status='confirmed'
             AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, %s)) < 90",
            $check_date, $check_time
        ));
        $booked_table_ids = array_map('intval', $booked);
    }

    // Quick time slots
    $time_slots = ['11:00', '12:00', '13:00', '14:00', '17:00', '18:00', '19:00', '20:00'];

    ob_start(); ?>
    <div class="rr-section">
        <div class="rr-section-header">
            <h3 class="rr-section-title">Table Availability Checker</h3>
        </div>

        <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;margin-bottom:24px;padding:20px;background:linear-gradient(135deg, var(--rr-cream) 0%, #fff 100%);border-radius:var(--rr-radius);border:1px solid var(--rr-border);">
            <div class="rr-form-group" style="margin:0;min-width:160px;">
                <label>Date</label>
                <input type="date" id="av-date" class="rr-input" value="<?php echo esc_attr($check_date); ?>">
            </div>
            <div class="rr-form-group" style="margin:0;min-width:140px;">
                <label>Time <small style="font-weight:400;">(optional)</small></label>
                <input type="time" id="av-time" class="rr-input" value="<?php echo esc_attr($check_time); ?>">
            </div>
            <button class="rr-btn rr-btn-primary" onclick="rrCheckAvailability()">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Check Availability
            </button>
        </div>

        <!-- Quick Time Slots -->
        <div style="margin-bottom:20px;">
            <div style="font-size:12px;font-weight:600;color:var(--rr-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Quick Check</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php foreach ($time_slots as $slot): ?>
                <button class="rr-btn rr-btn-ghost rr-btn-sm" onclick="document.getElementById('av-time').value='<?php echo $slot; ?>'; rrCheckAvailability();">
                    <?php echo date('g:i A', strtotime($slot)); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($check_time): ?>
        <?php 
        $free_count = count($tables) - count($booked_table_ids);
        $occupancy_percent = count($tables) > 0 ? round((count($booked_table_ids) / count($tables)) * 100) : 0;
        ?>
        <div style="background:#fff;border:1px solid var(--rr-border);border-radius:var(--rr-radius);padding:18px;margin-bottom:18px;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                <div>
                    <div style="font-size:12px;color:var(--rr-muted);margin-bottom:4px;">Showing availability for</div>
                    <div style="font-weight:600;font-size:15px;color:var(--rr-charcoal);">
                        <?php echo date('F j, Y', strtotime($check_date)); ?> at <?php echo date('g:i A', strtotime($check_time)); ?>
                    </div>
                    <div style="font-size:12px;color:var(--rr-muted);margin-top:2px;">±90 minute window</div>
                </div>
                <div style="display:flex;gap:16px;">
                    <div style="text-align:center;">
                        <div style="font-family:var(--ff-display);font-size:28px;font-weight:700;color:#10b981;"><?php echo $free_count; ?></div>
                        <div style="font-size:11px;color:var(--rr-muted);text-transform:uppercase;letter-spacing:.5px;">Available</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-family:var(--ff-display);font-size:28px;font-weight:700;color:#dc2626;"><?php echo count($booked_table_ids); ?></div>
                        <div style="font-size:11px;color:var(--rr-muted);text-transform:uppercase;letter-spacing:.5px;">Reserved</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-family:var(--ff-display);font-size:28px;font-weight:700;color:var(--rr-gold);"><?php echo $occupancy_percent; ?>%</div>
                        <div style="font-size:11px;color:var(--rr-muted);text-transform:uppercase;letter-spacing:.5px;">Occupancy</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="rr-avail-grid">
            <?php foreach ($tables as $t):
                $is_taken = in_array((int)$t->id, $booked_table_ids);
                $cls = $is_taken ? 'taken' : 'free';
                ?>
                <div class="rr-avail-card <?php echo $cls; ?>">
                    <div class="rr-avail-card-num">Table <?php echo esc_html($t->table_number); ?></div>
                    <div class="rr-avail-card-cap">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <?php echo $t->capacity; ?> seats
                    </div>
                    <div style="font-size:11px;color:var(--rr-muted);"><?php echo esc_html($t->location); ?></div>
                    <div class="rr-avail-card-status">
                        <?php if ($is_taken): ?>
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        Reserved
                        <?php else: ?>
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Available
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Show all reservations for the date -->
        <?php
        $day_reservations = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, t.table_number, t.location, t.capacity
             FROM {$res_table} r
             JOIN {$tbl_table} t ON r.table_id = t.id
             WHERE r.reservation_date=%s AND r.status='confirmed'
             ORDER BY r.reservation_time ASC",
            $check_date
        ));
        ?>
        <p style="font-size:13px;color:var(--rr-muted);margin-bottom:16px;">
            Showing all confirmed reservations for <strong><?php echo date('F j, Y', strtotime($check_date)); ?></strong>. Enter a time to check specific availability.
        </p>
        <?php if (empty($day_reservations)): ?>
        <div class="rr-empty" style="padding:32px;">
            <div class="rr-empty-icon"><svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <h4>All clear!</h4>
            <p>No reservations for this date.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="rr-table">
                <thead>
                    <tr><th>Time</th><th>Table</th><th>Customer</th><th>Guests</th><th>Duration</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($day_reservations as $r): ?>
                    <tr>
                        <td style="font-family:var(--ff-display);font-weight:600;color:var(--rr-gold);"><?php echo date('g:i A', strtotime($r->reservation_time)); ?></td>
                        <td><strong>Table <?php echo esc_html($r->table_number); ?></strong><br><small style="color:var(--rr-muted);"><?php echo esc_html($r->location); ?></small></td>
                        <td><?php echo esc_html($r->customer_name); ?></td>
                        <td><?php echo $r->num_guests; ?></td>
                        <td><?php echo $r->duration_minutes; ?> min</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    function rrCheckAvailability() {
        const date = document.getElementById('av-date').value;
        const time = document.getElementById('av-time').value;
        if (!date) { alert('Please select a date.'); return; }
        window.location = '?tab=availability&av_date=' + date + (time ? '&av_time=' + time : '');
    }
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// 5. AJAX HANDLER FUNCTIONS
// ============================================================

function bntm_ajax_rr_get_available_tables() {
    check_ajax_referer('rr_nonce', 'nonce');

    global $wpdb;
    $tbl_table = $wpdb->prefix . 'rr_tables';
    $res_table = $wpdb->prefix . 'rr_reservations';

    $date   = sanitize_text_field($_POST['date']);
    $time   = sanitize_text_field($_POST['time']);
    $guests = intval($_POST['guests']);

    // Get tables that fit the guest count and are available
    // Note: Removed business_id filter to work with seed data (business_id=1)
    // In production multi-tenant setup, add: AND business_id = %d
    $tables = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$tbl_table}
         WHERE status='available' AND capacity >= %d
         ORDER BY capacity ASC",
        $guests
    ));

    // Find already booked tables for this date/time (within 90 minutes)
    // Also not filtering by business_id here
    $booked_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT table_id FROM {$res_table}
         WHERE reservation_date=%s AND status='confirmed'
         AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, %s)) < 90",
        $date, $time
    ));

    $available = array_filter($tables, function($t) use ($booked_ids) {
        return !in_array($t->id, $booked_ids);
    });

    wp_send_json_success(['tables' => array_values($available)]);
}

function bntm_ajax_rr_make_reservation() {
    check_ajax_referer('rr_nonce', 'nonce');

    global $wpdb;
    $res_table = $wpdb->prefix . 'rr_reservations';
    $tbl_table = $wpdb->prefix . 'rr_tables';

    $table_id         = intval($_POST['table_id']);
    $customer_name    = sanitize_text_field($_POST['customer_name']);
    $phone            = sanitize_text_field($_POST['phone']);
    $email            = sanitize_email($_POST['email'] ?? '');
    $num_guests       = intval($_POST['num_guests']);
    $reservation_date = sanitize_text_field($_POST['reservation_date']);
    $reservation_time = sanitize_text_field($_POST['reservation_time']);
    $special_requests = sanitize_textarea_field($_POST['special_requests'] ?? '');
    
    // Use business_id = 1 for single restaurant, or get from logged-in user for multi-tenant
    $business_id = is_user_logged_in() ? get_current_user_id() : 1;

    // Validation
    if (!$table_id || !$customer_name || !$phone || !$num_guests || !$reservation_date || !$reservation_time) {
        wp_send_json_error(['message' => 'All required fields must be filled in.']);
    }

    // Check table capacity
    $table = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tbl_table} WHERE id=%d AND status='available'",
        $table_id
    ));

    if (!$table) {
        wp_send_json_error(['message' => 'Table not found or unavailable.']);
    }

    if ($num_guests > $table->capacity) {
        wp_send_json_error(['message' => "This table seats up to {$table->capacity} guests, but {$num_guests} were requested."]);
    }

    // Double-booking check (not filtering by business_id since all reservations share tables)
    $conflict = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$res_table}
         WHERE table_id=%d AND reservation_date=%s AND status='confirmed'
         AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, %s)) < 90",
        $table_id, $reservation_date, $reservation_time
    ));

    if ($conflict) {
        wp_send_json_error(['message' => 'This table is already reserved within 90 minutes of your requested time. Please choose a different table or time.']);
    }

    // Insert reservation
    $result = $wpdb->insert($res_table, [
        'rand_id'          => bntm_rand_id(),
        'business_id'      => $business_id,
        'table_id'         => $table_id,
        'customer_name'    => $customer_name,
        'phone'            => $phone,
        'email'            => $email,
        'num_guests'       => $num_guests,
        'reservation_date' => $reservation_date,
        'reservation_time' => $reservation_time,
        'status'           => 'confirmed',
        'special_requests' => $special_requests,
    ], ['%s','%d','%d','%s','%s','%s','%d','%s','%s','%s','%s']);

    if ($result) {
        $rand_id = $wpdb->get_var("SELECT rand_id FROM {$res_table} WHERE id=" . $wpdb->insert_id);
        wp_send_json_success(['message' => "Reservation confirmed! Your booking ID is: <strong>{$rand_id}</strong>"]);
    } else {
        wp_send_json_error(['message' => 'Failed to create reservation. Please try again.']);
    }
}

function bntm_ajax_rr_cancel_reservation() {
    check_ajax_referer('rr_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $res_table     = $wpdb->prefix . 'rr_reservations';
    $reservation_id = intval($_POST['reservation_id']);
    $reason         = sanitize_textarea_field($_POST['reason'] ?? '');

    $reservation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$res_table} WHERE id=%d",
        $reservation_id
    ));

    if (!$reservation) wp_send_json_error(['message' => 'Reservation not found.']);
    if ($reservation->status === 'cancelled') wp_send_json_error(['message' => 'Reservation is already cancelled.']);

    $result = $wpdb->update(
        $res_table,
        [
            'status'               => 'cancelled',
            'cancellation_reason'  => $reason,
            'cancelled_at'         => current_time('mysql'),
        ],
        ['id' => $reservation_id],
        ['%s','%s','%s'],
        ['%d']
    );

    if ($result !== false) {
        wp_send_json_success(['message' => 'Reservation cancelled successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to cancel reservation.']);
    }
}

function bntm_ajax_rr_update_reservation() {
    check_ajax_referer('rr_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $res_table = $wpdb->prefix . 'rr_reservations';
    $tbl_table = $wpdb->prefix . 'rr_tables';

    $reservation_id    = intval($_POST['reservation_id']);
    $table_id          = intval($_POST['table_id']);
    $original_table_id = intval($_POST['original_table_id']);
    $customer_name     = sanitize_text_field($_POST['customer_name']);
    $phone             = sanitize_text_field($_POST['phone']);
    $email             = sanitize_email($_POST['email'] ?? '');
    $num_guests        = intval($_POST['num_guests']);
    $reservation_date  = sanitize_text_field($_POST['reservation_date']);
    $reservation_time  = sanitize_text_field($_POST['reservation_time']);
    $special_requests  = sanitize_textarea_field($_POST['special_requests'] ?? '');

    // Validation
    if (!$reservation_id || !$table_id || !$customer_name || !$phone || !$num_guests || !$reservation_date || !$reservation_time) {
        wp_send_json_error(['message' => 'All required fields must be filled in.']);
    }

    // Check if reservation exists
    $reservation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$res_table} WHERE id=%d",
        $reservation_id
    ));

    if (!$reservation) {
        wp_send_json_error(['message' => 'Reservation not found.']);
    }

    // Check table capacity
    $table = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tbl_table} WHERE id=%d AND status='available'",
        $table_id
    ));

    if (!$table) {
        wp_send_json_error(['message' => 'Table not found or unavailable.']);
    }

    if ($num_guests > $table->capacity) {
        wp_send_json_error(['message' => "This table seats up to {$table->capacity} guests, but {$num_guests} were requested."]);
    }

    // Double-booking check (exclude current reservation)
    $conflict = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$res_table}
         WHERE table_id=%d AND reservation_date=%s AND status='confirmed'
         AND id != %d
         AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, %s)) < 90",
        $table_id, $reservation_date, $reservation_id, $reservation_time
    ));

    if ($conflict) {
        wp_send_json_error(['message' => 'This table is already reserved within 90 minutes of your requested time. Please choose a different table or time.']);
    }

    // Update reservation
    $result = $wpdb->update(
        $res_table,
        [
            'table_id'          => $table_id,
            'customer_name'     => $customer_name,
            'phone'             => $phone,
            'email'             => $email,
            'num_guests'        => $num_guests,
            'reservation_date'  => $reservation_date,
            'reservation_time'  => $reservation_time,
            'special_requests'  => $special_requests,
        ],
        ['id' => $reservation_id],
        ['%d','%s','%s','%s','%d','%s','%s','%s'],
        ['%d']
    );

    if ($result !== false) {
        wp_send_json_success(['message' => 'Reservation updated successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to update reservation. Please try again.']);
    }
}

function bntm_ajax_rr_complete_reservation() {
    check_ajax_referer('rr_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $res_table      = $wpdb->prefix . 'rr_reservations';
    $reservation_id = intval($_POST['reservation_id']);

    $result = $wpdb->update(
        $res_table,
        ['status' => 'completed'],
        ['id' => $reservation_id, 'status' => 'confirmed'],
        ['%s'],
        ['%d','%s']
    );

    if ($result !== false) {
        wp_send_json_success(['message' => 'Reservation marked as completed.']);
    } else {
        wp_send_json_error(['message' => 'Could not complete reservation.']);
    }
}

function bntm_ajax_rr_add_table() {
    check_ajax_referer('rr_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $tbl = $wpdb->prefix . 'rr_tables';

    $table_number = sanitize_text_field($_POST['table_number']);
    $capacity     = intval($_POST['capacity']);
    $location     = sanitize_text_field($_POST['location']);
    $notes        = sanitize_textarea_field($_POST['notes'] ?? '');
    $business_id  = get_current_user_id();

    if (!$table_number || $capacity < 1) {
        wp_send_json_error(['message' => 'Table number and capacity are required.']);
    }

    // Check duplicate
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tbl} WHERE table_number=%s AND business_id=%d",
        $table_number, $business_id
    ));
    if ($exists) wp_send_json_error(['message' => "Table '{$table_number}' already exists."]);

    $result = $wpdb->insert($tbl, [
        'rand_id'      => bntm_rand_id(),
        'business_id'  => $business_id,
        'table_number' => $table_number,
        'capacity'     => $capacity,
        'location'     => $location ?: 'Main Hall',
        'notes'        => $notes,
        'status'       => 'available',
    ], ['%s','%d','%s','%d','%s','%s','%s']);

    if ($result) {
        wp_send_json_success(['message' => "Table {$table_number} added successfully!"]);
    } else {
        wp_send_json_error(['message' => 'Failed to add table.']);
    }
}

function bntm_ajax_rr_delete_table() {
    check_ajax_referer('rr_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $tbl      = $wpdb->prefix . 'rr_tables';
    $res      = $wpdb->prefix . 'rr_reservations';
    $table_id = intval($_POST['table_id']);

    // Safety: don't delete if has future confirmed reservations
    $future = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$res} WHERE table_id=%d AND status='confirmed' AND reservation_date>=CURDATE()",
        $table_id
    ));
    if ($future > 0) wp_send_json_error(['message' => 'Cannot delete table with upcoming reservations.']);

    $result = $wpdb->delete($tbl, ['id' => $table_id], ['%d']);
    if ($result) {
        wp_send_json_success(['message' => 'Table deleted.']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete table.']);
    }
}

function bntm_ajax_rr_update_table_status() {
    check_ajax_referer('rr_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $tbl      = $wpdb->prefix . 'rr_tables';
    $table_id = intval($_POST['table_id']);
    $status   = sanitize_text_field($_POST['status']);

    if (!in_array($status, ['available','maintenance','inactive'])) {
        wp_send_json_error(['message' => 'Invalid status.']);
    }

    $result = $wpdb->update($tbl, ['status' => $status], ['id' => $table_id], ['%s'], ['%d']);
    if ($result !== false) {
        wp_send_json_success(['message' => 'Status updated.']);
    } else {
        wp_send_json_error(['message' => 'Failed to update status.']);
    }
}

function bntm_ajax_rr_get_reservations() {
    check_ajax_referer('rr_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $res_table   = $wpdb->prefix . 'rr_reservations';
    $tbl_table   = $wpdb->prefix . 'rr_tables';
    $business_id = get_current_user_id();

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT r.*, t.table_number, t.location FROM {$res_table} r
         JOIN {$tbl_table} t ON r.table_id = t.id
         WHERE r.business_id=%d
         ORDER BY r.reservation_date DESC, r.reservation_time DESC
         LIMIT 50",
        $business_id
    ));

    wp_send_json_success(['reservations' => $results]);
}

// ============================================================
// 6. PUBLIC BOOKING SHORTCODE
// ============================================================

function bntm_shortcode_rr_booking() {
    $nonce = wp_create_nonce('rr_nonce');

    ob_start();
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>

    <div class="rr-booking-wrap">
        <style>
        .rr-booking-wrap {
            font-family: var(--ff-body, 'DM Sans', sans-serif);
            max-width: 680px;
            margin: 0 auto;
            padding: 0 16px;
        }
        .rr-booking-hero {
            text-align: center;
            padding: 40px 0 32px;
        }
        .rr-booking-hero h2 {
            font-family: var(--ff-display, 'Playfair Display', serif);
            font-size: 32px;
            color: var(--rr-charcoal, #1a1a2e);
            margin: 0 0 8px;
        }
        .rr-booking-hero p {
            color: var(--rr-muted, #8a8a9a);
            font-size: 15px;
        }
        .rr-booking-card {
            background: #fff;
            border: 1px solid var(--rr-border, #e2ddd4);
            border-radius: var(--rr-radius, 12px);
            padding: 32px;
            box-shadow: var(--rr-shadow, 0 4px 24px rgba(26,26,46,.10));
        }
        .rr-lookup-section {
            background: var(--rr-cream, #f5f0e8);
            border-radius: var(--rr-radius, 12px);
            padding: 24px;
            margin-top: 24px;
        }
        </style>

        <div class="rr-booking-hero">
            <h2>Make a Reservation</h2>
            <p>Reserve your table online — quick and easy.</p>
        </div>

        <div class="rr-booking-card">
            <div id="booking-msg"></div>
            <div id="booking-success" style="display:none;text-align:center;padding:24px 0;">
                <svg width="56" height="56" fill="none" stroke="#15803d" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 14px;display:block;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <h3 style="font-family:var(--ff-display,'Playfair Display',serif);color:#15803d;margin:0 0 8px;">Reservation Confirmed!</h3>
                <p id="booking-success-msg" style="color:var(--rr-muted,#8a8a9a);font-size:14px;"></p>
                <button class="rr-btn rr-btn-ghost" onclick="rrResetBookingForm()" style="margin-top:16px;">Make Another Reservation</button>
            </div>
            <div id="booking-form-wrap">
                <div class="rr-form-grid">
                    <div class="rr-form-group">
                        <label>Full Name *</label>
                        <input type="text" id="pub-name" class="rr-input" placeholder="Your full name" required>
                    </div>
                    <div class="rr-form-group">
                        <label>Phone Number *</label>
                        <input type="tel" id="pub-phone" class="rr-input" placeholder="09xx-xxx-xxxx" required>
                    </div>
                    <div class="rr-form-group">
                        <label>Email Address</label>
                        <input type="email" id="pub-email" class="rr-input" placeholder="Optional">
                    </div>
                    <div class="rr-form-group">
                        <label>Number of Guests *</label>
                        <input type="number" id="pub-guests" class="rr-input" min="1" max="30" value="2" required>
                    </div>
                    <div class="rr-form-group">
                        <label>Date *</label>
                        <input type="date" id="pub-date" class="rr-input" required>
                    </div>
                    <div class="rr-form-group">
                        <label>Preferred Time *</label>
                        <input type="time" id="pub-time" class="rr-input" value="12:00" required>
                    </div>
                    <div class="rr-form-group full">
                        <label>Available Tables</label>
                        <select id="pub-table" class="rr-select">
                            <option value="">— Fill in date, time &amp; guests to load tables —</option>
                        </select>
                        <button type="button" class="rr-btn rr-btn-ghost rr-btn-sm" style="margin-top:6px;width:100%;justify-content:center;" onclick="pubLoadTables()">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            Check Availability
                        </button>
                    </div>
                    <div class="rr-form-group full">
                        <label>Special Requests</label>
                        <textarea id="pub-notes" class="rr-textarea" placeholder="Dietary restrictions, special occasions, seating preferences..."></textarea>
                    </div>
                </div>
                <hr class="rr-divider">
                <button class="rr-btn rr-btn-primary" id="pub-submit-btn" onclick="pubSubmitReservation()" style="width:100%;justify-content:center;padding:13px;">
                    Confirm My Reservation
                </button>
            </div>
        </div>

        <!-- Cancellation Lookup -->
        <div class="rr-lookup-section">
            <h4 style="font-family:var(--ff-display,'Playfair Display',serif);font-size:17px;margin:0 0 12px;color:var(--rr-charcoal,#1a1a2e);">Cancel an Existing Reservation</h4>
            <p style="font-size:13px;color:var(--rr-muted,#8a8a9a);margin:0 0 14px;">Enter your reservation ID to cancel a booking.</p>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <input type="text" id="cancel-lookup-id" class="rr-input" placeholder="Reservation ID (e.g. ABC12345)" style="flex:1;min-width:200px;">
                <button class="rr-btn rr-btn-danger" onclick="pubCancelReservation()">Cancel Reservation</button>
            </div>
            <div id="cancel-lookup-msg" style="margin-top:10px;"></div>
        </div>
    </div>

    <script>
    const pubNonce = '<?php echo $nonce; ?>';

    // Set minimum date
    document.getElementById('pub-date').min = new Date().toISOString().split('T')[0];

    function pubLoadTables() {
        const date   = document.getElementById('pub-date').value;
        const time   = document.getElementById('pub-time').value;
        const guests = document.getElementById('pub-guests').value;
        const sel    = document.getElementById('pub-table');

        if (!date || !time || !guests) {
            document.getElementById('booking-msg').innerHTML = '<div class="rr-notice rr-notice-warning">Please fill in date, time, and number of guests first.</div>';
            return;
        }

        sel.innerHTML = '<option>Checking availability...</option>';
        sel.disabled  = true;
        document.getElementById('booking-msg').innerHTML = '';

        const fd = new FormData();
        fd.append('action', 'rr_get_available_tables');
        fd.append('date', date);
        fd.append('time', time);
        fd.append('guests', guests);
        fd.append('nonce', pubNonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            sel.disabled = false;
            if (json.success && json.data.tables.length > 0) {
                sel.innerHTML = '<option value="">— Choose a table —</option>' +
                    json.data.tables.map(t =>
                        `<option value="${t.id}">Table ${t.table_number} — ${t.location} (${t.capacity} seats)</option>`
                    ).join('');
            } else {
                sel.innerHTML = '<option value="">No tables available for your selection</option>';
                document.getElementById('booking-msg').innerHTML = '<div class="rr-notice rr-notice-warning">No tables available for that date/time with enough capacity. Please try a different time.</div>';
            }
        });
    }

    function pubSubmitReservation() {
        const name   = document.getElementById('pub-name').value.trim();
        const phone  = document.getElementById('pub-phone').value.trim();
        const email  = document.getElementById('pub-email').value.trim();
        const guests = document.getElementById('pub-guests').value;
        const date   = document.getElementById('pub-date').value;
        const time   = document.getElementById('pub-time').value;
        const table  = document.getElementById('pub-table').value;
        const notes  = document.getElementById('pub-notes').value.trim();
        const msgEl  = document.getElementById('booking-msg');

        if (!name || !phone || !guests || !date || !time || !table) {
            msgEl.innerHTML = '<div class="rr-notice rr-notice-error">Please fill in all required fields and select an available table.</div>';
            return;
        }

        const btn = document.getElementById('pub-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="rr-spinner"></span> Confirming your reservation...';

        const fd = new FormData();
        fd.append('action', 'rr_make_reservation');
        fd.append('customer_name', name);
        fd.append('phone', phone);
        fd.append('email', email);
        fd.append('num_guests', guests);
        fd.append('reservation_date', date);
        fd.append('reservation_time', time);
        fd.append('table_id', table);
        fd.append('special_requests', notes);
        fd.append('nonce', pubNonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                document.getElementById('booking-form-wrap').style.display = 'none';
                document.getElementById('booking-success-msg').innerHTML = json.data.message;
                document.getElementById('booking-success').style.display = 'block';
            } else {
                msgEl.innerHTML = '<div class="rr-notice rr-notice-error">' + json.data.message + '</div>';
                btn.disabled = false;
                btn.textContent = 'Confirm My Reservation';
            }
        });
    }

    function rrResetBookingForm() {
        document.getElementById('booking-form-wrap').style.display = 'block';
        document.getElementById('booking-success').style.display  = 'none';
        document.getElementById('booking-msg').innerHTML = '';
        document.getElementById('pub-name').value = '';
        document.getElementById('pub-phone').value = '';
        document.getElementById('pub-email').value = '';
        document.getElementById('pub-guests').value = '2';
        document.getElementById('pub-date').value = '';
        document.getElementById('pub-time').value = '12:00';
        document.getElementById('pub-table').innerHTML = '<option value="">— Fill in date, time &amp; guests to load tables —</option>';
        document.getElementById('pub-notes').value = '';
        document.getElementById('pub-submit-btn').disabled = false;
        document.getElementById('pub-submit-btn').textContent = 'Confirm My Reservation';
    }

    function pubCancelReservation() {
        const rid = document.getElementById('cancel-lookup-id').value.trim();
        const msg = document.getElementById('cancel-lookup-msg');
        if (!rid) { msg.innerHTML='<div class="rr-notice rr-notice-error">Please enter a reservation ID.</div>'; return; }

        if (!confirm('Are you sure you want to cancel reservation: ' + rid + '?')) return;

        const fd = new FormData();
        fd.append('action', 'rr_cancel_by_rand_id');
        fd.append('rand_id', rid);
        fd.append('nonce', pubNonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            msg.innerHTML = '<div class="rr-notice rr-notice-' + (json.success ? 'success' : 'error') + '">' + json.data.message + '</div>';
            if (json.success) document.getElementById('cancel-lookup-id').value = '';
        });
    }
    </script>
    <?php
    return ob_get_clean();
}

// Public cancel by rand_id (for customer self-service)
add_action('wp_ajax_rr_cancel_by_rand_id',        'bntm_ajax_rr_cancel_by_rand_id');
add_action('wp_ajax_nopriv_rr_cancel_by_rand_id', 'bntm_ajax_rr_cancel_by_rand_id');

function bntm_ajax_rr_cancel_by_rand_id() {
    check_ajax_referer('rr_nonce', 'nonce');

    global $wpdb;
    $res_table = $wpdb->prefix . 'rr_reservations';
    $rand_id   = sanitize_text_field($_POST['rand_id']);

    $reservation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$res_table} WHERE rand_id=%s",
        $rand_id
    ));

    if (!$reservation) {
        wp_send_json_error(['message' => "Reservation ID '{$rand_id}' not found."]);
    }

    if ($reservation->status === 'cancelled') {
        wp_send_json_error(['message' => 'This reservation has already been cancelled.']);
    }

    if ($reservation->status === 'completed') {
        wp_send_json_error(['message' => 'Completed reservations cannot be cancelled.']);
    }

    $result = $wpdb->update(
        $res_table,
        ['status' => 'cancelled', 'cancelled_at' => current_time('mysql')],
        ['rand_id' => $rand_id],
        ['%s','%s'],
        ['%s']
    );

    if ($result !== false) {
        wp_send_json_success(['message' => "Reservation for <strong>{$reservation->customer_name}</strong> on " .
            date('F j, Y', strtotime($reservation->reservation_date)) . " at " .
            date('g:i A', strtotime($reservation->reservation_time)) . " has been cancelled."]);
    } else {
        wp_send_json_error(['message' => 'Failed to cancel. Please try again or contact us.']);
    }
}

// ============================================================
// 7. HELPER FUNCTIONS
// ============================================================

function rr_get_stats($business_id) {
    global $wpdb;
    $res_table = $wpdb->prefix . 'rr_reservations';
    $tbl_table = $wpdb->prefix . 'rr_tables';
    $today     = date('Y-m-d');

    return [
        'total_tables'    => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tbl_table} WHERE business_id=%d AND status='available'", $business_id)),
        'today_bookings'  => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$res_table} WHERE business_id=%d AND reservation_date=%s AND status='confirmed'", $business_id, $today)),
        'upcoming'        => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$res_table} WHERE business_id=%d AND reservation_date>%s AND status='confirmed'", $business_id, $today)),
        'total_cancelled' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$res_table} WHERE business_id=%d AND status='cancelled'", $business_id)),
    ];
}