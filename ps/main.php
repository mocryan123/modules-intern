<?php
/**
 * Module Name: PrintEase
 * Module Slug: ps
 * Description: Online document printing service — upload files, select print options, submit orders, track status, and manage fulfillment.
 * Version: 1.0.0
 * Author: BNTM Framework
 * Icon: printer
 */

if (!defined('ABSPATH')) exit;

define('BNTM_PS_PATH', dirname(__FILE__) . '/');
define('BNTM_PS_URL', plugin_dir_url(__FILE__));

// ─────────────────────────────────────────────────────────────
// EMAILJS CONFIGURATION
// ─────────────────────────────────────────────────────────────
define('PS_EMAILJS_PUBLIC_KEY',   'uBQKj9AWTDWMuW40_');
define('PS_EMAILJS_SERVICE_ID',   'service_uu81rw4');
define('PS_EMAILJS_TEMPLATE_ID',  'template_usp73c8');

// ─────────────────────────────────────────────────────────────
// 1. CORE MODULE CONFIGURATION FUNCTIONS
// ─────────────────────────────────────────────────────────────

function bntm_ps_get_pages() {
    return [
        'Print Shop'         => '[bntm_ps_dashboard]',
        'Submit Print Order' => '[bntm_ps_order]',
        'Order Tracking'     => '[bntm_ps_tracking]',
    ];
}

function bntm_ps_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    return [
        'ps_orders' => "CREATE TABLE {$prefix}ps_orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            customer_name VARCHAR(255) NOT NULL DEFAULT '',
            customer_email VARCHAR(255) NOT NULL DEFAULT '',
            customer_phone VARCHAR(50) NOT NULL DEFAULT '',
            file_name VARCHAR(500) NOT NULL DEFAULT '',
            file_path VARCHAR(1000) NOT NULL DEFAULT '',
            file_size BIGINT NOT NULL DEFAULT 0,
            page_count INT NOT NULL DEFAULT 0,
            copies INT NOT NULL DEFAULT 1,
            paper_size VARCHAR(20) NOT NULL DEFAULT 'A4',
            color_mode VARCHAR(20) NOT NULL DEFAULT 'bw',
            binding VARCHAR(50) NOT NULL DEFAULT 'none',
            orientation VARCHAR(20) NOT NULL DEFAULT 'portrait',
            sides VARCHAR(20) NOT NULL DEFAULT 'single',
            notes TEXT DEFAULT NULL,
            total_pages INT NOT NULL DEFAULT 0,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            payment_status VARCHAR(50) NOT NULL DEFAULT 'unpaid',
            payment_method VARCHAR(100) NOT NULL DEFAULT '',
            admin_notes TEXT DEFAULT NULL,
            printed_at DATETIME DEFAULT NULL,
            ready_at DATETIME DEFAULT NULL,
            picked_up_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) {$charset};",

        'ps_settings' => "CREATE TABLE {$prefix}ps_settings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_biz_key (business_id, setting_key)
        ) {$charset};",
    ];
}

function bntm_ps_get_shortcodes() {
    return [
        'bntm_ps_dashboard' => 'bntm_shortcode_ps_dashboard',
        'bntm_ps_order'     => 'bntm_shortcode_ps_order',
        'bntm_ps_tracking'  => 'bntm_shortcode_ps_tracking',
    ];
}

function bntm_ps_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_ps_get_tables();
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
    return count($tables);
}

// ─────────────────────────────────────────────────────────────
// 2. AJAX ACTION HOOKS
// ─────────────────────────────────────────────────────────────

add_action('wp_ajax_ps_submit_order',            'bntm_ajax_ps_submit_order');
add_action('wp_ajax_nopriv_ps_submit_order',     'bntm_ajax_ps_submit_order');
add_action('wp_ajax_ps_upload_file',             'bntm_ajax_ps_upload_file');
add_action('wp_ajax_nopriv_ps_upload_file',      'bntm_ajax_ps_upload_file');
add_action('wp_ajax_ps_get_order_status',        'bntm_ajax_ps_get_order_status');
add_action('wp_ajax_nopriv_ps_get_order_status', 'bntm_ajax_ps_get_order_status');
add_action('wp_ajax_ps_update_order_status',     'bntm_ajax_ps_update_order_status');
add_action('wp_ajax_ps_get_orders',              'bntm_ajax_ps_get_orders');
add_action('wp_ajax_ps_search_orders',           'bntm_ajax_ps_search_orders');
add_action('wp_ajax_ps_delete_order',            'bntm_ajax_ps_delete_order');
add_action('wp_ajax_ps_save_pricing',            'bntm_ajax_ps_save_pricing');
add_action('wp_ajax_ps_calculate_price',         'bntm_ajax_ps_calculate_price');
add_action('wp_ajax_nopriv_ps_calculate_price',  'bntm_ajax_ps_calculate_price');
add_action('wp_ajax_ps_mark_picked_up',          'bntm_ajax_ps_mark_picked_up');
add_action('wp_ajax_ps_mark_paid',               'bntm_ajax_ps_mark_paid');
add_action('wp_ajax_ps_check_new_orders',        'bntm_ajax_ps_check_new_orders');
add_action('wp_ajax_nopriv_ps_check_new_orders', 'bntm_ajax_ps_check_new_orders');

// ─────────────────────────────────────────────────────────────
// 3. ADMIN DASHBOARD SHORTCODE
// ─────────────────────────────────────────────────────────────

function bntm_shortcode_ps_dashboard() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to access the Print Shop dashboard.</div>';
    }

    $current_user = wp_get_current_user();
    $business_id  = $current_user->ID;
    $active_tab   = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

    ob_start();
    ?>
    <script>
    var ajaxurl = '<?php echo esc_js( set_url_scheme( admin_url( 'admin-ajax.php' ), is_ssl() ? 'https' : 'http' ) ); ?>';
    </script>

    <!-- EmailJS SDK (needed for ready-for-pickup notifications) -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script>
    (function(){
        emailjs.init('<?php echo esc_js(PS_EMAILJS_PUBLIC_KEY); ?>');
    })();
    </script>

    <div class="bntm-ps-container">
        <div id="ps-dashboard-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;gap:8px;">
            <div class="bntm-tabs" style="flex:1;min-width:0;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;">
                <a href="?page_id=<?php echo get_the_ID(); ?>&tab=overview"  class="bntm-tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>">Overview</a>
                <a href="?page_id=<?php echo get_the_ID(); ?>&tab=orders"    class="bntm-tab <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">Orders</a>
                <a href="?page_id=<?php echo get_the_ID(); ?>&tab=settings"  class="bntm-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">Settings</a>
            </div>
            <!-- Notification Bell -->
            <div id="ps-notification-bell-wrapper" class="ps-notification-wrapper" style="position:relative;display:inline-flex;align-items:center;flex-shrink:0;">
                <button id="ps-notification-bell" class="ps-notification-btn" style="background:none;border:none;cursor:pointer;position:relative;transition:transform .2s;" title="New Orders" aria-label="New Orders Notifications">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#1a3c8f;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0018 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span id="ps-notification-badge" class="ps-notification-badge" style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;display:none;border:2px solid #fff;box-shadow:0 2px 4px rgba(0,0,0,.2);">0</span>
                </button>
                <!-- Notification Popup -->
                <div id="ps-notification-panel" class="ps-notification-panel" style="position:absolute;top:100%;right:-10px;margin-top:8px;background:#fff;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.15);min-width:280px;max-width:400px;max-height:400px;overflow-y:auto;display:none;z-index:1000;border:1px solid #e5e7eb;">
                    <div style="padding:16px;border-bottom:1px solid #e5e7eb;">
                        <h3 style="margin:0;font-size:14px;font-weight:700;">Recent Orders</h3>
                    </div>
                    <div id="ps-notification-items" class="ps-notification-items" style="max-height:320px;overflow-y:auto;">
                        <!-- New orders will be listed here -->
                    </div>
                </div>
            </div>
        </div>
        <div class="bntm-tab-content">
            <?php
            if ($active_tab === 'overview')        echo ps_overview_tab($business_id);
            elseif ($active_tab === 'orders')       echo ps_orders_tab($business_id);
            elseif ($active_tab === 'settings')     echo ps_settings_tab($business_id);
            ?>
        </div>
    </div>

    <!-- Global Modal -->
    <div id="ps-modal-overlay" class="ps-modal-overlay" style="display:none;">
        <div class="ps-modal" id="ps-modal">
            <button class="ps-modal-close" id="ps-modal-close">&times;</button>
            <div id="ps-modal-content"></div>
        </div>
    </div>

    <style>
    .bntm-ps-container { font-family: 'Segoe UI', system-ui, sans-serif; }

    /* ── PrintEase Brand Overrides ── */
    .bntm-btn-primary {
        background: linear-gradient(135deg, #16a34a, #22c55e) !important;
        border-color: #16a34a !important;
        box-shadow: 0 2px 8px rgba(22,163,74,.25) !important;
    }
    .bntm-btn-primary:hover {
        background: linear-gradient(135deg, #15803d, #16a34a) !important;
        box-shadow: 0 4px 14px rgba(22,163,74,.35) !important;
    }
    .bntm-tab.active {
        color: #1a3c8f !important;
        border-bottom-color: #1a3c8f !important;
    }
    .bntm-tab:hover { color: #1a3c8f !important; }
    .bntm-input:focus, .bntm-select:focus { border-color: #1a3c8f !important; outline-color: #1a3c8f !important; }
    .bntm-stat-card .stat-number { color: #1a3c8f !important; }
    a.bntm-btn-secondary:hover { border-color: #1a3c8f !important; color: #1a3c8f !important; }
    .ps-view-btn { background: linear-gradient(135deg,#f59e0b,#d97706) !important; border-color: #d97706 !important; color: #fff !important; box-shadow: 0 2px 6px rgba(217,119,6,.3) !important; }
    .ps-view-btn:hover { background: linear-gradient(135deg,#d97706,#b45309) !important; }
    .ps-pickup-btn { background: linear-gradient(135deg,#16a34a,#22c55e) !important; border-color: #16a34a !important; color: #fff !important; box-shadow: 0 2px 6px rgba(22,163,74,.3) !important; }
    .ps-pickup-btn:hover { background: linear-gradient(135deg,#15803d,#16a34a) !important; }

    /* ── Stat Cards ── */
    .bntm-stats-row {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)) !important;
        gap: 12px !important;
        margin-bottom: 24px !important;
    }
    .bntm-stat-card {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        gap: 10px !important;
        overflow: hidden !important;
        min-width: 0 !important;
        padding: 16px 12px !important;
    }
    .bntm-stat-card .stat-icon {
        width: 52px !important; height: 52px !important;
        min-width: 52px !important; min-height: 52px !important;
        border-radius: 14px !important;
        display: flex !important; align-items: center !important; justify-content: center !important;
        flex-shrink: 0 !important; overflow: hidden !important; margin: 0 auto !important;
    }
    .bntm-stat-card .stat-icon svg { display: block !important; flex-shrink: 0 !important; }
    .bntm-stat-card .stat-content { flex: unset !important; min-width: 0 !important; width: 100% !important; text-align: center !important; }
    .bntm-stat-card .stat-content h3 { text-align: center !important; margin: 0 0 4px !important; }
    .bntm-stat-card .stat-content .stat-number { text-align: center !important; margin: 0 0 4px !important; }
    .bntm-stat-card .stat-content .stat-label { text-align: center !important; display: block !important; }

    /* ── Modal ── */
    .ps-modal-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.55);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex; align-items: center; justify-content: center;
    }
    .ps-modal {
        background: #fff; border-radius: 16px; padding: 32px;
        width: 90%; max-width: 640px; max-height: 85vh;
        overflow-y: auto; position: relative;
        box-shadow: 0 24px 80px rgba(0,0,0,0.2);
    }
    .ps-modal-close {
        position: absolute; top: 16px; right: 20px;
        background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; line-height: 1;
    }
    .ps-modal-close:hover { color: #111; }

    /* ── Notification Bell ── */
    #ps-notification-bell, .ps-notification-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; border-radius: 8px;
        transition: all .2s ease; -webkit-tap-highlight-color: transparent; touch-action: manipulation;
    }
    #ps-notification-bell:hover, .ps-notification-btn:hover { background: #f0f4f8; }
    #ps-notification-bell:active, .ps-notification-btn:active { transform: scale(0.95); }
    #ps-notification-badge, .ps-notification-badge {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    #ps-notification-panel, .ps-notification-panel {
        box-shadow: 0 10px 40px rgba(0,0,0,.15);
        animation: slideDown .3s ease;
        min-width: 280px;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    #ps-notification-items, .ps-notification-items { max-height: 320px; overflow-y: auto; }
    #ps-notification-items::-webkit-scrollbar, .ps-notification-items::-webkit-scrollbar { width: 6px; }
    #ps-notification-items::-webkit-scrollbar-track, .ps-notification-items::-webkit-scrollbar-track { background: transparent; }
    #ps-notification-items::-webkit-scrollbar-thumb, .ps-notification-items::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }

    /* ── Mobile: tabs + bell on one row, 2-col stat grid ── */
    @media (max-width: 768px) {
        /* Header row: tabs left, bell right, never wraps */
        #ps-dashboard-header {
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 6px !important;
            margin-bottom: 12px !important;
        }
        .bntm-tabs {
            flex: 1 1 0 !important;
            min-width: 0 !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            scrollbar-width: none !important;
            display: flex !important;
        }
        .bntm-tabs::-webkit-scrollbar { display: none !important; }
        .bntm-tab {
            font-size: 13px !important;
            padding: 8px 12px !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }
        .ps-notification-wrapper {
            flex-shrink: 0 !important;
            width: auto !important;
        }
        /* Stat cards: 2 columns */
        .bntm-stats-row {
            grid-template-columns: 1fr 1fr !important;
            gap: 10px !important;
        }
        .bntm-stat-card { padding: 14px 10px !important; }
        .bntm-stat-card .stat-icon {
            width: 46px !important; height: 46px !important;
            min-width: 46px !important; min-height: 46px !important;
        }
        .bntm-stat-card .stat-content h3 { font-size: 12px !important; }
        .bntm-stat-card .stat-content .stat-number { font-size: 22px !important; }
        .bntm-stat-card .stat-content .stat-label { font-size: 11px !important; }
        /* Notification panel slides up */
        .ps-notification-panel {
            position: fixed !important;
            top: auto !important; bottom: 0 !important;
            left: 0 !important; right: 0 !important;
            width: 100% !important; max-width: 100% !important;
            max-height: 65vh !important; min-width: unset !important;
            border-radius: 20px 20px 0 0 !important;
            margin: 0 !important; animation: slideUp .25s ease !important;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(100%); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .ps-notification-items { max-height: calc(65vh - 60px) !important; }
        /* Modal slides up from bottom */
        .ps-modal-overlay { align-items: flex-end !important; }
        .ps-modal {
            width: 100% !important; max-width: 100% !important;
            border-radius: 20px 20px 0 0 !important;
            padding: 20px 16px !important; max-height: 90vh !important;
        }
    }
    @media (max-width: 400px) {
        .bntm-tab { font-size: 12px !important; padding: 7px 9px !important; }
        .bntm-stats-row { gap: 8px !important; }
        .bntm-stat-card { padding: 10px 8px !important; }
    }

    /* ── Badges ── */
    .ps-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; letter-spacing:.3px; text-transform:capitalize; }
    .ps-badge-pending   { background:#fef3c7; color:#92400e; }
    .ps-badge-printing  { background:#e8eeff; color:#1a3c8f; }
    .ps-badge-ready     { background:#dcfce7; color:#15803d; }
    .ps-badge-picked_up { background:#e5e7eb; color:#374151; }
    .ps-badge-cancelled { background:#fee2e2; color:#991b1b; }
    .ps-options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .bntm-table-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 16px; }
    .bntm-table { min-width: 800px; }
    @media(max-width:600px){ .ps-options-grid { grid-template-columns: 1fr; } }
    </style>

    <script>
    (function(){
        const overlay = document.getElementById('ps-modal-overlay');
        if (!overlay) return;
        window.psOpenModal = function(html) {
            document.getElementById('ps-modal-content').innerHTML = html;
            overlay.style.display = 'flex';
        };
        window.psCloseModal = function() { overlay.style.display = 'none'; };
        document.getElementById('ps-modal-close').addEventListener('click', psCloseModal);
        overlay.addEventListener('click', function(e) { if (e.target === overlay) psCloseModal(); });

        // ── Real-time Notification Bell ──
        const bellBtn     = document.getElementById('ps-notification-bell');
        const bellPanel   = document.getElementById('ps-notification-panel');
        const bellWrapper = document.getElementById('ps-notification-bell-wrapper');
        const bellBadge   = document.getElementById('ps-notification-badge');
        const notifyItems = document.getElementById('ps-notification-items');
        let notificationCount = 0;
        let isMobileView = window.innerWidth <= 768;

        // WP timezone offset injected from PHP — ensures JS parses DB timestamps correctly
        // regardless of whether MySQL uses UTC or local time on the server.
        const WP_TZ        = '<?php echo esc_js( wp_timezone_string() ); ?>';           // e.g. "Asia/Manila"
        const WP_TZ_OFFSET = '<?php echo esc_js( (new DateTime("now", wp_timezone()))->format("P") ); ?>'; // e.g. "+08:00"

        // Parse a DB datetime string (no timezone info) as WP local time → JS Date
        function parseDbTime(str) {
            if (!str) return new Date();
            return new Date(str.replace(' ', 'T') + WP_TZ_OFFSET);
        }

        // Format a JS Date as PH time (Asia/Manila timezone)
        function formatPhTime(date) {
            const time = date.toLocaleTimeString('en-PH', {
                timeZone: 'Asia/Manila',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            const d = date.toLocaleDateString('en-PH', {
                timeZone: 'Asia/Manila',
                month: 'numeric',
                day: 'numeric',
                year: 'numeric'
            });
            return time + '<br>' + d;
        }

        window.addEventListener('resize', function() { isMobileView = window.innerWidth <= 768; });

        // ── Persistence helpers (localStorage survives reloads and tab closes) ──
        const LS_READ   = 'ps_read_order_ids';
        const LS_ORDERS = 'ps_cached_orders';
        const LS_TIME   = 'ps_last_check_time';

        function loadReadIds() {
            try { return new Set(JSON.parse(localStorage.getItem(LS_READ) || '[]')); }
            catch(e) { return new Set(); }
        }
        function saveReadIds() {
            try { localStorage.setItem(LS_READ, JSON.stringify([...readOrderIds])); }
            catch(e) {}
        }
        function loadCachedOrders() {
            try { return JSON.parse(localStorage.getItem(LS_ORDERS) || '[]'); }
            catch(e) { return []; }
        }
        function saveCachedOrders(orders) {
            try { localStorage.setItem(LS_ORDERS, JSON.stringify(orders)); }
            catch(e) {}
        }
        // Restore lastCheckTime from localStorage so we never re-fetch already-seen orders.
        // First-ever load defaults to 24 hours ago so today's orders are shown immediately.
        function loadLastCheckTime() {
            try {
                const saved = localStorage.getItem(LS_TIME);
                if (saved) return parseInt(saved, 10);
            } catch(e) {}
            return new Date().getTime() - (24 * 60 * 60 * 1000);
        }
        function saveLastCheckTime(t) {
            try { localStorage.setItem(LS_TIME, String(t)); }
            catch(e) {}
        }

        let readOrderIds  = loadReadIds();
        let lastCheckTime = loadLastCheckTime();

        function markOrderRead(orderId) {
            const id = String(orderId);
            readOrderIds.add(id);
            saveReadIds();
            // Dim the item in the panel
            const el = notifyItems.querySelector('[data-order-id="' + id + '"]');
            if (el) {
                el.style.opacity = '0.5';
                const dot = el.querySelector('.ps-unread-dot');
                if (dot) dot.style.display = 'none';
            }
            // Recalculate badge count from remaining unread items
            const unreadCount = [...notifyItems.querySelectorAll('[data-order-id]')]
                .filter(function(el) { return !readOrderIds.has(el.getAttribute('data-order-id')); }).length;
            if (unreadCount > 0) {
                notificationCount = unreadCount;
                bellBadge.textContent = unreadCount;
                bellBadge.style.display = 'flex';
            } else {
                notificationCount = 0;
                bellBadge.style.display = 'none';
                bellBadge.textContent = '0';
            }
        }

        // Toggle notification panel — does NOT mark anything as read
        bellBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isVisible = bellPanel.style.display === 'block';
            bellPanel.style.display = isVisible ? 'none' : 'block';
            if (isMobileView && !isVisible) {
                addMobileBackdrop();
            }
        });

        // Add mobile backdrop to close panel on tap
        function addMobileBackdrop() {
            if (isMobileView) {
                const backdrop = document.createElement('div');
                backdrop.id = 'ps-notification-backdrop';
                backdrop.style.cssText = 'position:fixed;inset:0;z-index:999;background:rgba(0,0,0,0.3);';
                backdrop.addEventListener('click', function(e) {
                    if (e.target === backdrop) {
                        bellPanel.style.display = 'none';
                        backdrop.remove();
                    }
                });
                document.body.appendChild(backdrop);
            }
        }

        // Close panel when clicking outside (desktop only)
        document.addEventListener('click', function(e) {
            if (!isMobileView && !bellBtn.contains(e.target) && !bellPanel.contains(e.target) && !bellWrapper.contains(e.target)) {
                bellPanel.style.display = 'none';
            }
        });

        // Close panel when pressing Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && bellPanel.style.display === 'block') {
                bellPanel.style.display = 'none';
                const backdrop = document.getElementById('ps-notification-backdrop');
                if (backdrop) backdrop.remove();
            }
        });

        // Remove backdrop when panel is hidden
        bellPanel.addEventListener('transitionend', function() {
            if (bellPanel.style.display === 'none') {
                const backdrop = document.getElementById('ps-notification-backdrop');
                if (backdrop) backdrop.remove();
            }
        });

        // ── AudioContext: create once on first user gesture so mobile allows sound ──
        let audioCtx = null;
        function ensureAudioCtx() {
            if (!audioCtx) {
                try { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch(e) {}
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume().catch(() => {});
            }
        }
        // Unlock audio on first touch/click (required by iOS/Android)
        document.addEventListener('touchstart', ensureAudioCtx, { once: true, passive: true });
        document.addEventListener('click',      ensureAudioCtx, { once: true });

        function playNotificationSound() {
            try {
                ensureAudioCtx();
                if (!audioCtx) return;
                const osc  = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.frequency.value = 800;
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
                osc.start(audioCtx.currentTime);
                osc.stop(audioCtx.currentTime + 0.5);
            } catch(e) {
                // Sound not available — silent fail is fine
            }
        }

        // ── Polling ──
        let pollTimer = null;
        let isFetching = false; // prevent overlapping requests on slow connections

        function checkNewOrders() {
            if (isFetching) return;
            isFetching = true;
            const checkTime = lastCheckTime; // snapshot before fetch
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=ps_check_new_orders&nonce=<?php echo wp_create_nonce('ps_check_nonce'); ?>&last_check=' + checkTime
            })
            .then(r => r.json())
            .then(res => {
                // Always advance the clock and persist it
                lastCheckTime = new Date().getTime();
                saveLastCheckTime(lastCheckTime);
                if (res.success && res.data.orders && res.data.orders.length > 0) {
                    // Merge new orders with cached ones, deduplicate by id, keep latest 20
                    const existing  = loadCachedOrders();
                    const existMap  = {};
                    existing.forEach(o => existMap[o.id] = o);
                    res.data.orders.forEach(o => existMap[o.id] = o);
                    const merged = Object.values(existMap)
                        .sort((a, b) => parseDbTime(b.created_at) - parseDbTime(a.created_at))
                        .slice(0, 20);
                    saveCachedOrders(merged);

                    const unreadOrders = merged.filter(o => !readOrderIds.has(String(o.id)));
                    updateNotificationPanel(merged);
                    if (unreadOrders.length > 0) {
                        notificationCount = unreadOrders.length;
                        bellBadge.textContent = notificationCount;
                        bellBadge.style.display = 'flex';
                        playNotificationSound();
                        bellBtn.style.transform = 'scale(1.15)';
                        setTimeout(() => bellBtn.style.transform = 'scale(1)', 200);
                    }
                }
            })
            .catch(() => {}) // silent on network errors (common on mobile)
            .finally(() => { isFetching = false; });
        }

        function startPolling() {
            if (pollTimer) return;
            checkNewOrders(); // immediate check when we (re)gain focus
            pollTimer = setInterval(checkNewOrders, 5000);
        }
        function stopPolling() {
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        }

        // ── Pause polling when tab/screen is hidden, resume when visible ──
        // This is the key fix for mobile: browsers throttle/kill setInterval
        // when the page is backgrounded. We restart it fresh on visibility restore.
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                startPolling();
            } else {
                stopPolling();
            }
        });

        // Also re-poll on page focus (covers PWA / app-switcher return on iOS)
        window.addEventListener('focus', function() {
            if (document.visibilityState === 'visible') startPolling();
        });
        window.addEventListener('pageshow', function(e) {
            // pageshow fires on back/forward cache restore on iOS Safari
            startPolling();
        });

        function updateNotificationPanel(orders) {
            notifyItems.innerHTML = '';
            orders.forEach(order => {
                const item = document.createElement('div');
                const padding = isMobileView ? '16px' : '12px 16px';
                const fontSize = isMobileView ? '14px' : '13px';
                const customerFontSize = isMobileView ? '13px' : '12px';
                const timeFontSize = isMobileView ? '12px' : '11px';
                const minTouchHeight = isMobileView ? '60px' : 'auto';

                const isRead = readOrderIds.has(String(order.id));
                item.style.cssText = `padding:${padding};border-bottom:1px solid #f3f4f6;cursor:pointer;transition:background .2s;min-height:${minTouchHeight};display:flex;align-items:center;opacity:${isRead ? '0.5' : '1'};`;
                item.setAttribute('data-order-id', order.id);
                item.innerHTML = `
                    <div style="display:flex;justify-content:space-between;align-items:start;gap:8px;width:100%;">
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span class="ps-unread-dot" style="display:${isRead ? 'none' : 'inline-block'};width:8px;height:8px;border-radius:50%;background:#ef4444;flex-shrink:0;"></span>
                                <div style="font-weight:600;font-size:${fontSize};color:#111;word-break:break-word;">Order #${order.rand_id}</div>
                            </div>
                            <div style="font-size:${customerFontSize};color:#6b7280;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${order.customer_name}</div>
                            <div style="font-size:${timeFontSize};color:#9ca3af;margin-top:2px;white-space:nowrap;">${formatPhTime(parseDbTime(order.created_at))}</div>
                        </div>
                        <div style="text-align:right;font-weight:600;font-size:${fontSize};color:#16a34a;white-space:nowrap;margin-left:8px;flex-shrink:0;">₱${parseFloat(order.total_price).toFixed(2)}</div>
                    </div>
                `;
                // Touch-friendly: use touchend for tap on mobile, click on desktop
                if (isMobileView) {
                    let touchMoved = false;
                    item.addEventListener('touchstart', () => { touchMoved = false; item.style.background = '#f0f4ff'; }, { passive: true });
                    item.addEventListener('touchmove',  () => { touchMoved = true;  item.style.background = 'transparent'; }, { passive: true });
                    item.addEventListener('touchend',   () => {
                        item.style.background = 'transparent';
                        if (!touchMoved) { markOrderRead(item.getAttribute('data-order-id')); openNotificationOrder(item.getAttribute('data-order-id')); }
                    });
                } else {
                    item.addEventListener('mouseover', () => item.style.background = '#f9fafb');
                    item.addEventListener('mouseout',  () => item.style.background = 'transparent');
                    item.addEventListener('click', () => { markOrderRead(item.getAttribute('data-order-id')); openNotificationOrder(item.getAttribute('data-order-id')); });
                }
                notifyItems.appendChild(item);
            });
        }

        function openNotificationOrder(orderId) {
            bellPanel.style.display = 'none';
            const backdrop = document.getElementById('ps-notification-backdrop');
            if (backdrop) backdrop.remove();

            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=ps_get_orders&nonce=<?php echo wp_create_nonce('ps_admin_nonce'); ?>&order_id=' + orderId
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    psOpenModal(res.data.html);
                } else {
                    alert('Failed to load order details: ' + (res.data?.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Order fetch error:', err);
                alert('Error loading order details');
            });
        }

        // ── Restore panel from cache immediately on load (no flash/wait) ──
        (function restoreFromCache() {
            const cached = loadCachedOrders();
            if (cached.length > 0) {
                updateNotificationPanel(cached);
                const unread = cached.filter(o => !readOrderIds.has(String(o.id)));
                if (unread.length > 0) {
                    notificationCount = unread.length;
                    bellBadge.textContent = notificationCount;
                    bellBadge.style.display = 'flex';
                }
            }
        })();

        // Start polling
        startPolling();
    })();
    </script>
    <?php
    $content = ob_get_clean();
    return bntm_universal_container('PrintEase', $content);
}

// ─────────────────────────────────────────────────────────────
// 4. TAB: OVERVIEW
// ─────────────────────────────────────────────────────────────

function ps_overview_tab($business_id) {
    global $wpdb;
    $t = $wpdb->prefix . 'ps_orders';

    $total    = (int)   $wpdb->get_var("SELECT COUNT(*) FROM {$t}");
    $pending  = (int)   $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='pending'");
    $printing = (int)   $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='printing'");
    $ready    = (int)   $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='ready'");
    $revenue  = (float) $wpdb->get_var("SELECT COALESCE(SUM(total_price),0) FROM {$t} WHERE payment_status='paid'");
    $recent   = $wpdb->get_results("SELECT * FROM {$t} ORDER BY created_at DESC LIMIT 8");

    $order_page     = get_page_by_path('submit-print-order');
    $order_page_url = $order_page ? get_permalink($order_page->ID) : home_url('/submit-print-order/');

    ob_start();
    ?>

    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;padding:12px 16px;background:#f8f9fa;border:1px solid #e5e7eb;border-radius:10px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;min-width:0;flex:1;">
            <svg width="16" height="16" style="flex-shrink:0;" fill="none" stroke="#6b7280" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17H17.01M17 20H5a2 2 0 01-2-2V9a2 2 0 012-2h2V5a2 2 0 012-2h6a2 2 0 012 2v2h2a2 2 0 012 2v7a2 2 0 01-2 2z"/></svg>
            <span style="font-size:13px;color:#6b7280;white-space:nowrap;flex-shrink:0;">Share your order page:</span>
            <code style="font-size:12px;color:#374151;background:#e5e7eb;padding:3px 8px;border-radius:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;display:inline-block;"><?php echo esc_html($order_page_url); ?></code>
        </div>
        <button
            id="ps-copy-order-link"
            data-url="<?php echo esc_attr($order_page_url); ?>"
            style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1px solid #d1d5db;border-radius:6px;background:#fff;font-size:13px;font-weight:500;color:#374151;cursor:pointer;white-space:nowrap;flex-shrink:0;transition:border-color .15s,color .15s;"
        >
            <svg id="ps-copy-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <svg id="ps-check-icon" width="14" height="14" fill="none" stroke="#16a34a" stroke-width="2.5" viewBox="0 0 24 24" style="display:none;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span id="ps-copy-label">Copy link</span>
        </button>
    </div>

    <div class="bntm-stats-row">
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#1a3c8f,#0d2466);">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0120 9.414V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="stat-content"><h3>Total Orders</h3><p class="stat-number"><?php echo number_format($total); ?></p><span class="stat-label">All time</span></div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content"><h3>Pending</h3><p class="stat-number"><?php echo number_format($pending); ?></p><span class="stat-label">Awaiting print</span></div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#2563eb,#1a3c8f);">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17H17.01M17 20H5a2 2 0 01-2-2V9a2 2 0 012-2h2V5a2 2 0 012-2h6a2 2 0 012 2v2h2a2 2 0 012 2v7a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="stat-content"><h3>Printing</h3><p class="stat-number"><?php echo number_format($printing); ?></p><span class="stat-label">In progress</span></div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a);">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content"><h3>Ready</h3><p class="stat-number"><?php echo number_format($ready); ?></p><span class="stat-label">For pickup</span></div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#0f766e,#0d9488);">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content"><h3>Revenue</h3><p class="stat-number">&#8369;<?php echo number_format($revenue, 2); ?></p><span class="stat-label">Total paid</span></div>
        </div>
    </div>

    <div class="bntm-form-section">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;">Recent Orders</h3>
            <a href="?page_id=<?php echo get_the_ID(); ?>&tab=orders" class="bntm-btn-secondary" style="font-size:13px;">
                View All
            </a>
        </div>
        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead><tr><th>Order ID</th><th>Customer</th><th>File</th><th>Options</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                <?php if (empty($recent)): ?>
                <tr><td colspan="7" style="text-align:center;color:#9ca3af;">No orders yet.</td></tr>
                <?php else: foreach ($recent as $o): ?>
                <tr>
                    <td><strong>#<?php echo esc_html($o->rand_id); ?></strong></td>
                    <td><?php echo esc_html($o->customer_name); ?></td>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($o->file_name); ?>"><?php echo esc_html($o->file_name); ?></td>
                    <td style="font-size:12px;color:#6b7280;"><?php echo (int)$o->copies; ?>x &bull; <?php echo esc_html($o->paper_size); ?> &bull; <?php echo $o->color_mode === 'color' ? 'Color' : 'B&amp;W'; ?></td>
                    <td><strong>&#8369;<?php echo number_format($o->total_price, 2); ?></strong></td>
                    <td><span class="ps-badge ps-badge-<?php echo esc_attr($o->status); ?>"><?php echo esc_html(ucfirst(str_replace('_',' ',$o->status))); ?></span></td>
                    <td style="font-size:12px;color:#6b7280;"><?php echo date('g:i A', strtotime($o->created_at)); ?><br><?php echo date('n/j/Y', strtotime($o->created_at)); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script>
    (function(){
        var btn = document.getElementById('ps-copy-order-link');
        if (!btn) return;
        btn.addEventListener('click', function() {
            var url      = this.dataset.url;
            var copyIcon = document.getElementById('ps-copy-icon');
            var checkIcon= document.getElementById('ps-check-icon');
            var label    = document.getElementById('ps-copy-label');

            function onCopied() {
                copyIcon.style.display  = 'none';
                checkIcon.style.display = 'inline';
                label.textContent       = 'Copied!';
                btn.style.borderColor   = '#22c55e';
                btn.style.color         = '#16a34a';
                setTimeout(function() {
                    copyIcon.style.display  = 'inline';
                    checkIcon.style.display = 'none';
                    label.textContent       = 'Copy link';
                    btn.style.borderColor   = '';
                    btn.style.color         = '';
                }, 2500);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(onCopied).catch(function() {
                    fallbackCopy(url, onCopied);
                });
            } else {
                fallbackCopy(url, onCopied);
            }
        });

        function fallbackCopy(text, callback) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity  = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try { document.execCommand('copy'); callback(); } catch(e) {}
            document.body.removeChild(ta);
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────
// 5. TAB: ORDERS
// ─────────────────────────────────────────────────────────────

function ps_orders_tab($business_id) {
    global $wpdb;
    $t = $wpdb->prefix . 'ps_orders';

    $filter_status  = isset($_GET['status'])  ? sanitize_text_field($_GET['status'])  : '';
    $filter_payment = isset($_GET['payment']) ? sanitize_text_field($_GET['payment']) : '';
    $search         = isset($_GET['search'])  ? sanitize_text_field($_GET['search'])  : '';

    $where  = 'WHERE 1=1';
    $params = [];
    if ($filter_status)  { $where .= ' AND status=%s';         $params[] = $filter_status; }
    if ($filter_payment) { $where .= ' AND payment_status=%s'; $params[] = $filter_payment; }
    if ($search) {
        $where .= ' AND (customer_name LIKE %s OR customer_email LIKE %s OR rand_id LIKE %s OR file_name LIKE %s)';
        $like = '%' . $wpdb->esc_like($search) . '%';
        $params = array_merge($params, [$like,$like,$like,$like]);
    }

    $sql    = "SELECT * FROM {$t} {$where} ORDER BY created_at DESC";
    $orders = empty($params) ? $wpdb->get_results($sql) : $wpdb->get_results($wpdb->prepare($sql, ...$params));
    $statuses = ['pending','printing','ready','picked_up','cancelled'];
    $nonce    = wp_create_nonce('ps_admin_nonce');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;align-items:center;">
            <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;flex:1;">
                    <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
                    <input type="hidden" name="tab" value="orders">
                    <input type="text" id="ps-search-input" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search by name, email, order ID..." class="bntm-input" style="flex:1;min-width:200px;">
                    <select id="ps-status-filter" name="status" class="bntm-select">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?php echo $s; ?>" <?php selected($filter_status, $s); ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="ps-payment-filter" name="payment" class="bntm-select">
                        <option value="">All Payments</option>
                        <option value="unpaid" <?php selected($filter_payment, 'unpaid'); ?>>Unpaid</option>
                        <option value="paid"   <?php selected($filter_payment, 'paid');   ?>>Paid</option>
                    </select>
                    <button type="submit" class="bntm-btn-primary">Filter</button>
                    <?php if ($filter_status || $filter_payment || $search): ?><a href="?page_id=<?php echo get_the_ID(); ?>&tab=orders" class="bntm-btn-secondary">Clear</a><?php endif; ?>
            </form>
        </div>

        <div class="bntm-table-wrapper">
        <table class="bntm-table">
            <thead>
                <tr><th>Order ID</th><th>Customer</th><th>File</th><th>Print Options</th><th>Pages</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody id="ps-orders-tbody">
                <?php if (empty($orders)): ?>
                <tr><td colspan="10" style="text-align:center;color:#9ca3af;padding:40px;">No orders found.</td></tr>
                <?php else: foreach ($orders as $o): ?>
                <tr id="ps-order-row-<?php echo $o->id; ?>">
                    <td><strong>#<?php echo esc_html($o->rand_id); ?></strong></td>
                    <td>
                        <div style="font-weight:600;font-size:13px;"><?php echo esc_html($o->customer_name); ?></div>
                        <div style="font-size:11px;color:#6b7280;"><?php echo esc_html($o->customer_email); ?></div>
                        <?php if ($o->customer_phone): ?><div style="font-size:11px;color:#6b7280;"><?php echo esc_html($o->customer_phone); ?></div><?php endif; ?>
                    </td>
                    <td style="max-width:150px;">
                        <div style="font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($o->file_name); ?>"><?php echo esc_html($o->file_name); ?></div>
                        <div style="font-size:11px;color:#9ca3af;"><?php echo ps_format_filesize($o->file_size); ?></div>
                        <?php if ($o->file_path && file_exists($o->file_path)): ?>
                            <a href="<?php echo esc_url(ps_get_file_url($o->file_path)); ?>" download="<?php echo esc_attr($o->file_name); ?>" target="_blank" style="font-size:11px;color:#1a3c8f;">Download</a>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;">
                        <div><?php echo (int)$o->copies; ?> copy/ies</div>
                        <div><?php echo esc_html($o->paper_size); ?> &bull; <?php echo $o->color_mode === 'color' ? 'Color' : 'B&amp;W'; ?></div>
                        <div><?php echo esc_html(ucfirst($o->orientation)); ?> &bull; <?php echo esc_html(ucfirst($o->sides)); ?>-sided</div>
                        <?php if ($o->binding !== 'none'): ?><div>Binding: <?php echo esc_html(ucfirst($o->binding)); ?></div><?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-weight:700;"><?php echo (int)$o->total_pages; ?></span>
                        <div style="font-size:11px;color:#9ca3af;"><?php echo $o->page_count; ?> doc pg</div>
                    </td>
                    <td><strong>&#8369;<?php echo number_format($o->total_price, 2); ?></strong></td>
                    <td>
                        <span class="ps-badge <?php echo $o->payment_status === 'paid' ? 'ps-badge-ready' : 'ps-badge-pending'; ?>"><?php echo ucfirst($o->payment_status); ?></span>
                        <?php if ($o->payment_method): ?><div style="font-size:11px;color:#6b7280;margin-top:4px;"><?php echo esc_html($o->payment_method); ?></div><?php endif; ?>
                    </td>
                    <td>
                        <select class="ps-status-select bntm-select" data-id="<?php echo $o->id; ?>" data-nonce="<?php echo $nonce; ?>" style="font-size:12px;padding:4px 8px;" <?php echo in_array($o->status, ['picked_up','cancelled']) ? 'disabled' : ''; ?>>
                            <?php foreach ($statuses as $s):
                                if ($s === 'picked_up') continue;
                            ?>
                                <option value="<?php echo $s; ?>" <?php selected($o->status, $s); ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
                            <?php endforeach; ?>
                            <?php if ($o->status === 'picked_up'): ?>
                                <option value="picked_up" selected disabled>Picked Up</option>
                            <?php endif; ?>
                        </select>
                    </td>
                    <td style="font-size:12px;color:#6b7280;white-space:nowrap;"><?php echo date('g:i A', strtotime($o->created_at)); ?><br><?php echo date('n/j/Y', strtotime($o->created_at)); ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-direction:column;">
                            <button class="bntm-btn-small bntm-btn-primary ps-view-btn" data-id="<?php echo $o->id; ?>" data-nonce="<?php echo $nonce; ?>">View All Details</button>
                            <?php if (!in_array($o->status, ['picked_up', 'cancelled'])): ?>
                            <button class="bntm-btn-small bntm-btn-secondary ps-pickup-btn" data-id="<?php echo $o->id; ?>" data-nonce="<?php echo $nonce; ?>">Picked Up</button>
                            <?php endif; ?>
                            <?php if ($o->payment_status === 'unpaid'): ?>
                            <button class="bntm-btn-small bntm-btn-primary ps-markpaid-btn" data-id="<?php echo $o->id; ?>" data-nonce="<?php echo $nonce; ?>" style="background:linear-gradient(135deg,#16a34a,#22c55e);border-color:#16a34a;">Mark Paid</button>
                            <?php endif; ?>
                            <button class="bntm-btn-small bntm-btn-danger ps-delete-btn" data-id="<?php echo $o->id; ?>" data-nonce="<?php echo $nonce; ?>">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script>
    (function(){
        const nonce       = '<?php echo esc_js($nonce); ?>';
        const SERVICE_ID  = '<?php echo esc_js(PS_EMAILJS_SERVICE_ID); ?>';
        const TEMPLATE_ID = '<?php echo esc_js(PS_EMAILJS_TEMPLATE_ID); ?>';
        const SHOP_NAME   = '<?php echo esc_js(ps_get_setting("shop_name", "PrintEase")); ?>';
        const SHOP_ADDR   = '<?php echo esc_js(ps_get_setting("shop_address", "")); ?>';
        const SHOP_HOURS  = '<?php echo esc_js(ps_get_setting("shop_hours", "Mon–Sat, 8:00 AM – 6:00 PM")); ?>';

        function psSendReadyEmail(data) {
            if (!data.customer_email) return;
            emailjs.send(SERVICE_ID, TEMPLATE_ID, {
                to_email             : data.customer_email,
                to_name              : data.customer_name,
                reply_to             : data.customer_email,
                order_id             : data.rand_id,
                file_name            : data.file_name,
                paper_size           : data.paper_size,
                color_mode           : data.color_mode,
                copies               : data.copies,
                sides                : data.sides,
                orientation          : data.orientation,
                binding              : data.binding,
                total_pages          : data.total_pages,
                additional_services  : data.additional_services || 'None',
                total_price          : '₱' + data.total_price,
                payment_method       : data.payment_method,
                shop_name            : SHOP_NAME,
                shop_address         : SHOP_ADDR,
                shop_hours           : SHOP_HOURS,
                message              : 'Your print order is now ready for pickup! Please bring your Order ID when you visit the shop.',
            }).catch(err => console.warn('EmailJS ready email error:', err));
        }

        document.querySelectorAll('.ps-status-select').forEach(sel => {
            sel.addEventListener('change', function() {
                const fd = new FormData();
                fd.append('action', 'ps_update_order_status');
                fd.append('order_id', this.dataset.id);
                fd.append('status', this.value);
                fd.append('nonce', nonce);
                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                    if (!json.success) {
                        alert('Failed to update status: ' + json.data.message);
                    } else if (json.data.send_ready_email) {
                        psSendReadyEmail({
                            customer_email      : json.data.customer_email,
                            customer_name       : json.data.customer_name,
                            rand_id             : json.data.rand_id,
                            file_name           : json.data.file_name,
                            paper_size          : json.data.paper_size,
                            color_mode          : json.data.color_mode,
                            copies              : json.data.copies,
                            sides               : json.data.sides,
                            orientation         : json.data.orientation,
                            binding             : json.data.binding,
                            total_pages         : json.data.total_pages,
                            additional_services : json.data.additional_services,
                            total_price         : json.data.total_price,
                            payment_method      : json.data.payment_method,
                        });
                    }
                });
            });
        });

        // ── Auto-search functionality ──
        let searchTimeout;
        const searchInput = document.getElementById('ps-search-input');
        const statusFilter = document.getElementById('ps-status-filter');
        const paymentFilter = document.getElementById('ps-payment-filter');
        const tableBody = document.getElementById('ps-orders-tbody');

        function performSearch() {
            if (!tableBody) return;
            
            const fd = new FormData();
            fd.append('action', 'ps_search_orders');
            fd.append('search', searchInput.value);
            fd.append('status', statusFilter.value);
            fd.append('payment', paymentFilter.value);
            fd.append('nonce', nonce);
            
            fetch(ajaxurl, {method: 'POST', body: fd})
                .then(r => r.json())
                .then(json => {
                    if (json.success) {
                        tableBody.innerHTML = json.data.html;
                        // Re-attach event listeners to newly rendered status selects
                        document.querySelectorAll('.ps-status-select').forEach(sel => {
                            sel.addEventListener('change', function() {
                                const fd = new FormData();
                                fd.append('action', 'ps_update_order_status');
                                fd.append('order_id', this.dataset.id);
                                fd.append('status', this.value);
                                fd.append('nonce', nonce);
                                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                                    if (!json.success) {
                                        alert('Failed to update status: ' + json.data.message);
                                    } else if (json.data.send_ready_email) {
                                        psSendReadyEmail({
                                            customer_email      : json.data.customer_email,
                                            customer_name       : json.data.customer_name,
                                            rand_id             : json.data.rand_id,
                                            file_name           : json.data.file_name,
                                            paper_size          : json.data.paper_size,
                                            color_mode          : json.data.color_mode,
                                            copies              : json.data.copies,
                                            sides               : json.data.sides,
                                            orientation         : json.data.orientation,
                                            binding             : json.data.binding,
                                            total_pages         : json.data.total_pages,
                                            additional_services : json.data.additional_services,
                                            total_price         : json.data.total_price,
                                            payment_method      : json.data.payment_method,
                                        });
                                    }
                                });
                            });
                        });
                        // Re-attach event listeners to view buttons
                        document.querySelectorAll('.ps-view-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const fd = new FormData();
                                fd.append('action', 'ps_get_orders');
                                fd.append('order_id', this.dataset.id);
                                fd.append('nonce', nonce);
                                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                                    if (json.success && json.data.html) psOpenModal(json.data.html);
                                });
                            });
                        });
                        // Re-attach event listeners to pickup buttons
                        document.querySelectorAll('.ps-pickup-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                if (!confirm('Mark this order as picked up?')) return;
                                const fd = new FormData();
                                fd.append('action', 'ps_mark_picked_up');
                                fd.append('order_id', this.dataset.id);
                                fd.append('nonce', nonce);
                                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                                    if (json.success) location.reload();
                                    else alert('Error: ' + json.data.message);
                                });
                            });
                        });
                        // Re-attach event listeners to mark paid buttons
                        document.querySelectorAll('.ps-markpaid-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                if (!confirm('Mark this order as paid?')) return;
                                const orderId = this.dataset.id;
                                const fd = new FormData();
                                fd.append('action', 'ps_mark_paid');
                                fd.append('order_id', orderId);
                                fd.append('nonce', nonce);
                                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                                    if (json.success) location.reload();
                                    else alert('Error: ' + json.data.message);
                                });
                            });
                        });
                        // Re-attach event listeners to delete buttons
                        document.querySelectorAll('.ps-delete-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                if (!confirm('Delete this order permanently?')) return;
                                const orderId = this.dataset.id;
                                const fd = new FormData();
                                fd.append('action', 'ps_delete_order');
                                fd.append('order_id', orderId);
                                fd.append('nonce', nonce);
                                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                                    if (json.success) { const row = document.getElementById('ps-order-row-' + orderId); if (row) row.remove(); }
                                    else alert('Error: ' + json.data.message);
                                });
                            });
                        });
                    }
                })
                .catch(err => console.error('Search error:', err));
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', performSearch);
        }

        if (paymentFilter) {
            paymentFilter.addEventListener('change', performSearch);
        }

        document.querySelectorAll('.ps-view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const fd = new FormData();
                fd.append('action', 'ps_get_orders');
                fd.append('order_id', this.dataset.id);
                fd.append('nonce', nonce);
                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                    if (json.success && json.data.html) psOpenModal(json.data.html);
                });
            });
        });

        document.querySelectorAll('.ps-pickup-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Mark this order as picked up?')) return;
                const fd = new FormData();
                fd.append('action', 'ps_mark_picked_up');
                fd.append('order_id', this.dataset.id);
                fd.append('nonce', nonce);
                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                    if (json.success) location.reload();
                    else alert('Error: ' + json.data.message);
                });
            });
        });

        document.querySelectorAll('.ps-markpaid-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Mark this order as paid?')) return;
                const orderId = this.dataset.id;
                const fd = new FormData();
                fd.append('action', 'ps_mark_paid');
                fd.append('order_id', orderId);
                fd.append('nonce', nonce);
                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                    if (json.success) location.reload();
                    else alert('Error: ' + json.data.message);
                });
            });
        });

        document.querySelectorAll('.ps-delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Delete this order permanently?')) return;
                const orderId = this.dataset.id;
                const fd = new FormData();
                fd.append('action', 'ps_delete_order');
                fd.append('order_id', orderId);
                fd.append('nonce', nonce);
                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                    if (json.success) { const row = document.getElementById('ps-order-row-' + orderId); if (row) row.remove(); }
                    else alert('Error: ' + json.data.message);
                });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────
// 6. TAB: SETTINGS
// ─────────────────────────────────────────────────────────────

function ps_settings_tab($business_id) {
    $nonce = wp_create_nonce('ps_admin_nonce');

    $bw_a4             = ps_get_setting('bw_a4', 2.00);
    $bw_a3             = ps_get_setting('bw_a3', 4.00);
    $bw_letter         = ps_get_setting('bw_letter', 2.00);
    $bw_long           = ps_get_setting('bw_long', 2.00);
    $color_a4          = ps_get_setting('color_a4', 8.00);
    $color_a3          = ps_get_setting('color_a3', 14.00);
    $color_letter      = ps_get_setting('color_letter', 8.00);
    $color_long        = ps_get_setting('color_long', 8.00);
    $binding_spiral    = ps_get_setting('binding_spiral', 35.00);
    $binding_staple    = ps_get_setting('binding_staple', 5.00);
    $binding_hardcover = ps_get_setting('binding_hardcover', 80.00);
    $shop_name         = ps_get_setting('shop_name', 'PrintEase');
    $shop_address      = ps_get_setting('shop_address', '');
    $shop_hours        = ps_get_setting('shop_hours', 'Mon–Sat, 8:00 AM – 6:00 PM');
    $shop_note         = ps_get_setting('shop_note', 'Please bring your Order ID when picking up.');
    $allowed_types     = ps_get_setting('allowed_file_types', 'pdf,doc,docx,ppt,pptx,jpg,png');
    $max_file_mb       = ps_get_setting('max_file_mb', 20);
    $gcash_name        = ps_get_setting('gcash_name', '');
    $gcash_number      = ps_get_setting('gcash_number', '');
    $gcash_qr_url      = ps_get_setting('gcash_qr_url', '');
    $admin_email       = ps_get_setting('admin_email', get_option('admin_email'));

    $extra_services_raw = ps_get_setting('extra_services', '[]');
    $extra_services     = json_decode($extra_services_raw, true);
    if (!is_array($extra_services)) $extra_services = [];

    ob_start();
    ?>
    <div id="ps-settings-message"></div>

    <div class="bntm-form-section">
        <h3>Pricing Configuration</h3>
        <p style="color:#6b7280;margin-bottom:20px;">Set price per page for each paper size and color mode (in ₱).</p>

        <h4 style="margin-bottom:12px;font-size:14px;color:#374151;">Black &amp; White</h4>
        <div class="ps-options-grid" style="margin-bottom:20px;">
            <div class="bntm-form-group"><label>A4 per page (₱)</label><input type="number" id="bw_a4" value="<?php echo esc_attr($bw_a4); ?>" step="0.01" min="0" class="bntm-input"></div>
            <div class="bntm-form-group"><label>A3 per page (₱)</label><input type="number" id="bw_a3" value="<?php echo esc_attr($bw_a3); ?>" step="0.01" min="0" class="bntm-input"></div>
            <div class="bntm-form-group"><label>Short/Letter per page (₱)</label><input type="number" id="bw_letter" value="<?php echo esc_attr($bw_letter); ?>" step="0.01" min="0" class="bntm-input"></div>
            <div class="bntm-form-group"><label>Long/Legal per page (₱)</label><input type="number" id="bw_long" value="<?php echo esc_attr($bw_long); ?>" step="0.01" min="0" class="bntm-input"></div>
        </div>

        <h4 style="margin-bottom:12px;font-size:14px;color:#374151;">Color</h4>
        <div class="ps-options-grid" style="margin-bottom:20px;">
            <div class="bntm-form-group"><label>A4 per page (₱)</label><input type="number" id="color_a4" value="<?php echo esc_attr($color_a4); ?>" step="0.01" min="0" class="bntm-input"></div>
            <div class="bntm-form-group"><label>A3 per page (₱)</label><input type="number" id="color_a3" value="<?php echo esc_attr($color_a3); ?>" step="0.01" min="0" class="bntm-input"></div>
            <div class="bntm-form-group"><label>Short/Letter per page (₱)</label><input type="number" id="color_letter" value="<?php echo esc_attr($color_letter); ?>" step="0.01" min="0" class="bntm-input"></div>
            <div class="bntm-form-group"><label>Long/Legal per page (₱)</label><input type="number" id="color_long" value="<?php echo esc_attr($color_long); ?>" step="0.01" min="0" class="bntm-input"></div>
        </div>

        <h4 style="margin-bottom:12px;font-size:14px;color:#374151;">Binding Add-ons</h4>
        <div class="ps-options-grid" style="margin-bottom:24px;">
            <div class="bntm-form-group"><label>Spiral Binding (₱)</label><input type="number" id="binding_spiral" value="<?php echo esc_attr($binding_spiral); ?>" step="0.01" min="0" class="bntm-input"></div>
            <div class="bntm-form-group"><label>Staple Binding (₱)</label><input type="number" id="binding_staple" value="<?php echo esc_attr($binding_staple); ?>" step="0.01" min="0" class="bntm-input"></div>
            <div class="bntm-form-group"><label>Hard Cover (₱)</label><input type="number" id="binding_hardcover" value="<?php echo esc_attr($binding_hardcover); ?>" step="0.01" min="0" class="bntm-input"></div>
        </div>
        <button id="ps-save-pricing-btn" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Save Pricing</button>
    </div>

    <div class="bntm-form-section">
        <h3>Shop Information</h3>
        <p style="color:#6b7280;margin-bottom:20px;">Shown to customers on the order and tracking pages.</p>
        <div class="bntm-form-group"><label>Shop Name</label><input type="text" id="shop_name" value="<?php echo esc_attr($shop_name); ?>" class="bntm-input"></div>
        <div class="bntm-form-group"><label>Address / Location</label><input type="text" id="shop_address" value="<?php echo esc_attr($shop_address); ?>" placeholder="e.g. Room 12, Main Building" class="bntm-input"></div>
        <div class="bntm-form-group"><label>Operating Hours</label><input type="text" id="shop_hours" value="<?php echo esc_attr($shop_hours); ?>" class="bntm-input"></div>
        <div class="bntm-form-group"><label>Pickup Note</label><textarea id="shop_note" rows="2" class="bntm-input"><?php echo esc_textarea($shop_note); ?></textarea></div>
    </div>

    <div class="bntm-form-section">
        <h3>Email Notification Settings</h3>
        <p style="color:#6b7280;margin-bottom:20px;">Admin email for new order notifications via EmailJS.</p>
        <div class="bntm-form-group"><label>Admin Email Address</label><input type="email" id="admin_email" value="<?php echo esc_attr($admin_email); ?>" placeholder="admin@yourshop.com" class="bntm-input"></div>
        <button id="ps-save-shop-btn" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Save Settings</button>
    </div>

    <div class="bntm-form-section">
        <h3>Upload Configuration</h3>
        <div class="ps-options-grid">
            <div class="bntm-form-group"><label>Allowed File Types (comma-separated)</label><input type="text" id="allowed_file_types" value="<?php echo esc_attr($allowed_types); ?>" class="bntm-input" placeholder="pdf,doc,docx,jpg,png"></div>
            <div class="bntm-form-group"><label>Max File Size (MB)</label><input type="number" id="max_file_mb" value="<?php echo esc_attr($max_file_mb); ?>" min="1" max="100" class="bntm-input"></div>
        </div>
        <button id="ps-save-upload-btn" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Save Upload Settings</button>
    </div>

    <div class="bntm-form-section">
        <h3>GCash Payment Details</h3>
        <p style="color:#6b7280;margin-bottom:20px;">Shown to customers when they select GCash as payment method.</p>
        <div class="ps-options-grid" style="margin-bottom:16px;">
            <div class="bntm-form-group"><label>Account Name</label><input type="text" id="gcash_name" value="<?php echo esc_attr($gcash_name); ?>" placeholder="e.g. Juan dela Cruz" class="bntm-input"></div>
            <div class="bntm-form-group"><label>GCash Number</label><input type="text" id="gcash_number" value="<?php echo esc_attr($gcash_number); ?>" placeholder="e.g. 09XX XXX XXXX" class="bntm-input"></div>
        </div>
        <div class="bntm-form-group">
            <label>QR Code Image URL <span style="color:#9ca3af;font-weight:400;">(optional — paste a direct image URL)</span></label>
            <input type="text" id="gcash_qr_url" value="<?php echo esc_attr($gcash_qr_url); ?>" placeholder="https://..." class="bntm-input">
        </div>
        <button id="ps-save-gcash-btn" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Save GCash Details</button>
    </div>

    <!-- ── ADDITIONAL SERVICES ── -->
    <div class="bntm-form-section">
        <h3>Additional Services</h3>
        <p style="color:#6b7280;margin-bottom:20px;">Custom add-ons customers can select on the order form (e.g. lamination, colored paper). Price is per order unless "Per Copy" is enabled.</p>

        <div style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 12px; padding-bottom: 8px;">
            <div id="ps-extra-services-list" style="min-width: 650px;">
                <div style="display:grid;grid-template-columns:1.2fr 1.4fr 110px 90px 65px;gap:8px;margin-bottom:6px;align-items:center;">
                    <span style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.4px;">Name</span>
                    <span style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.4px;">Description</span>
                    <span style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.4px;">Price (₱)</span>
                    <span style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.4px;">Per Copy</span>
                    <span></span>
                </div>
                <?php foreach ($extra_services as $svc): ?>
                <div class="ps-svc-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 110px 90px 65px;gap:8px;margin-bottom:8px;align-items:center;">
                    <input type="text" class="bntm-input" value="<?php echo esc_attr($svc['name'] ?? ''); ?>" placeholder="e.g. Lamination">
                    <input type="text" class="bntm-input" value="<?php echo esc_attr($svc['desc'] ?? ''); ?>" placeholder="Short description">
                    <input type="number" class="bntm-input" value="<?php echo esc_attr($svc['price'] ?? '0.00'); ?>" step="0.01" min="0">
                    <select class="bntm-select">
                        <option value="0" <?php selected(empty($svc['per_copy'])); ?>>No</option>
                        <option value="1" <?php selected(!empty($svc['per_copy'])); ?>>Yes</option>
                    </select>
                    <button type="button" class="bntm-btn-small bntm-btn-danger ps-remove-svc">Remove</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="button" id="ps-add-svc-row" class="bntm-btn-secondary" style="margin-top:4px;">+ Add Service</button>
        <div style="margin-top:10px;padding:10px 14px;background:#e8eeff;border-left:3px solid #1a3c8f;border-radius:6px;font-size:12px;color:#1a3c8f;">
            Services appear as checkboxes on the order form and are added to the order total.
        </div>
        <button id="ps-save-services-btn" class="bntm-btn-primary" data-nonce="<?php echo esc_attr($nonce); ?>" style="margin-top:16px;">Save Additional Services</button>
    </div>

    <script>
    (function(){
        function showMsg(el, msg, type) {
            el.innerHTML = '<div class="bntm-notice bntm-notice-' + type + '">' + msg + '</div>';
            setTimeout(() => el.innerHTML = '', 3500);
        }
        const msgEl = document.getElementById('ps-settings-message');

        function saveSettings(settings, btn, nonce) {
            const fd = new FormData();
            fd.append('action', 'ps_save_pricing');
            fd.append('nonce', nonce);
            Object.entries(settings).forEach(([k,v]) => fd.append(k, v));
            btn.disabled = true; btn.textContent = 'Saving...';
            fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                showMsg(msgEl, json.data.message, json.success ? 'success' : 'error');
                btn.disabled = false; btn.textContent = btn.dataset.label || 'Save';
            });
        }

        function collectSettings(keys) {
            const data = {};
            keys.forEach(k => { const el = document.getElementById(k); if (el) data[k] = el.value; });
            return data;
        }

        const pricingBtn = document.getElementById('ps-save-pricing-btn');
        pricingBtn.dataset.label = 'Save Pricing';
        pricingBtn.addEventListener('click', function() {
            saveSettings(collectSettings(['bw_a4','bw_a3','bw_letter','bw_long','color_a4','color_a3','color_letter','color_long','binding_spiral','binding_staple','binding_hardcover']), this, this.dataset.nonce);
        });

        const shopBtn = document.getElementById('ps-save-shop-btn');
        shopBtn.dataset.label = 'Save Settings';
        shopBtn.addEventListener('click', function() {
            saveSettings(collectSettings(['shop_name','shop_address','shop_hours','shop_note','admin_email']), this, this.dataset.nonce);
        });

        const uploadBtn = document.getElementById('ps-save-upload-btn');
        uploadBtn.dataset.label = 'Save Upload Settings';
        uploadBtn.addEventListener('click', function() {
            saveSettings(collectSettings(['allowed_file_types','max_file_mb']), this, this.dataset.nonce);
        });

        const gcashBtn = document.getElementById('ps-save-gcash-btn');
        gcashBtn.dataset.label = 'Save GCash Details';
        gcashBtn.addEventListener('click', function() {
            saveSettings(collectSettings(['gcash_name','gcash_number','gcash_qr_url']), this, this.dataset.nonce);
        });

        // ── Additional Services ──────────────────────────────────
        function newSvcRow(name, desc, price, perCopy) {
            const row = document.createElement('div');
            row.className = 'ps-svc-row';
            row.style.cssText = 'display:grid;grid-template-columns:1.2fr 1.4fr 110px 90px 65px;gap:8px;margin-bottom:8px;align-items:center;';
            row.innerHTML = `
                <input type="text" class="bntm-input" value="${name||''}" placeholder="e.g. Lamination">
                <input type="text" class="bntm-input" value="${desc||''}" placeholder="Short description">
                <input type="number" class="bntm-input" value="${price||'0.00'}" step="0.01" min="0">
                <select class="bntm-select">
                    <option value="0" ${!perCopy?'selected':''}>No</option>
                    <option value="1" ${perCopy?'selected':''}>Yes</option>
                </select>
                <button type="button" class="bntm-btn-small bntm-btn-danger ps-remove-svc">Remove</button>
            `;
            return row;
        }

        const addSvcBtn = document.getElementById('ps-add-svc-row');
        if (addSvcBtn) {
            addSvcBtn.addEventListener('click', function() {
                const row = newSvcRow();
                document.getElementById('ps-extra-services-list').appendChild(row);
                row.querySelector('input').focus();
            });
        }

        const svcList = document.getElementById('ps-extra-services-list');
        if (svcList) {
            svcList.addEventListener('click', function(e) {
                if (e.target.classList.contains('ps-remove-svc')) {
                    e.target.closest('.ps-svc-row').remove();
                }
            });
        }

        const saveSvcBtn = document.getElementById('ps-save-services-btn');
        if (saveSvcBtn) {
            saveSvcBtn.addEventListener('click', function() {
                const rows = document.querySelectorAll('.ps-svc-row');
                const services = [];
                rows.forEach(row => {
                    const inputs = row.querySelectorAll('input');
                    const sel    = row.querySelector('select');
                    const name   = inputs[0].value.trim();
                    if (!name) return;
                    services.push({
                        name     : name,
                        desc     : inputs[1].value.trim(),
                        price    : parseFloat(inputs[2].value) || 0,
                        per_copy : sel.value === '1' ? 1 : 0,
                    });
                });

                const btn = this;
                btn.disabled = true; btn.textContent = 'Saving...';
                const fd = new FormData();
                fd.append('action', 'ps_save_pricing');
                fd.append('nonce', btn.dataset.nonce);
                fd.append('extra_services', JSON.stringify(services));
                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                    showMsg(msgEl, json.data.message, json.success ? 'success' : 'error');
                    btn.disabled = false; btn.textContent = 'Save Additional Services';
                });
            });
        }

    })();
    </script>
    <?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────
// 7. FRONTEND: ORDER SUBMISSION SHORTCODE
// ─────────────────────────────────────────────────────────────

function bntm_shortcode_ps_order() {
    $upload_nonce    = wp_create_nonce('ps_upload_nonce');
    $order_nonce     = wp_create_nonce('ps_order_nonce');
    $calculate_nonce = wp_create_nonce('ps_calc_nonce');

    $allowed_types = ps_get_setting('allowed_file_types', 'pdf,doc,docx,ppt,pptx,jpg,png');
    $max_mb        = (int) ps_get_setting('max_file_mb', 20);
    $shop_name     = ps_get_setting('shop_name', 'PrintEase');
    $shop_address  = ps_get_setting('shop_address', '');
    $shop_hours    = ps_get_setting('shop_hours', 'Mon–Sat, 8:00 AM – 6:00 PM');
    $shop_note     = ps_get_setting('shop_note', 'Bring your Order ID when picking up.');
    $type_list     = implode(',', array_map(fn($t) => '.' . trim($t), explode(',', $allowed_types)));
    $gcash_name    = ps_get_setting('gcash_name', '');
    $gcash_number  = ps_get_setting('gcash_number', '');
    $gcash_qr_url  = ps_get_setting('gcash_qr_url', '');
    $admin_email   = ps_get_setting('admin_email', get_option('admin_email'));

    $extra_services_raw = ps_get_setting('extra_services', '[]');
    $extra_services     = json_decode($extra_services_raw, true);
    if (!is_array($extra_services)) $extra_services = [];

    ob_start();
    ?>
    <!-- EmailJS SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script>
    (function(){
        emailjs.init('<?php echo esc_js(PS_EMAILJS_PUBLIC_KEY); ?>');
    })();
    </script>

    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script>var ajaxurl = '<?php echo esc_js( set_url_scheme( admin_url( 'admin-ajax.php' ), is_ssl() ? 'https' : 'http' ) ); ?>';</script>

    <div class="pso-root">

        <!-- ── LEFT PANEL ── -->
        <aside class="pso-sidebar">
            <div class="pso-sidebar-inner">
                <div class="pso-brand">
                    <div class="pso-brand-icon">
                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17H17.01M17 20H5a2 2 0 01-2-2V9a2 2 0 012-2h2V5a2 2 0 012-2h6a2 2 0 012 2v2h2a2 2 0 012 2v7a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <div class="pso-brand-name"><?php echo esc_html($shop_name); ?></div>
                        <div class="pso-brand-sub">Fast &amp; Easy Online Printing</div>
                        <?php $pso_tracking_url = get_permalink(get_page_by_path('order-tracking')) ?: home_url('/order-tracking/'); ?>
                        <a href="<?php echo esc_url($pso_tracking_url); ?>" class="pso-brand-link">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            Order Tracking
                        </a>
                    </div>
                </div>

                <nav class="pso-step-nav">
                    <?php
                    $steps = [1 => 'Upload Document', 2 => 'Print Options', 3 => 'Your Details', 4 => 'Review & Submit'];
                    foreach ($steps as $n => $label):
                    ?>
                    <?php if ($n > 1): ?><div class="pso-step-connector"></div><?php endif; ?>
                    <div class="pso-step-item <?php echo $n === 1 ? 'active' : ''; ?>" data-step="<?php echo $n; ?>">
                        <div class="pso-step-dot">
                            <span class="pso-step-num"><?php echo $n; ?></span>
                            <svg class="pso-step-check" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div class="pso-step-info">
                            <div class="pso-step-label">Step <?php echo $n; ?></div>
                            <div class="pso-step-title"><?php echo $label; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </nav>

                <div class="pso-info-card">
                    <div class="pso-info-card-title">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Pickup Location
                    </div>
                    <?php if ($shop_address): ?><div class="pso-info-card-text"><?php echo esc_html($shop_address); ?></div><?php endif; ?>
                    <div class="pso-info-card-text"><?php echo esc_html($shop_hours); ?></div>
                    <?php if ($shop_note): ?><div class="pso-info-card-note"><?php echo esc_html($shop_note); ?></div><?php endif; ?>
                </div>
            </div>
        </aside>

        <!-- ── RIGHT PANEL ── -->
        <main class="pso-main">

            <!-- STEP 1: Upload -->
            <div class="pso-panel active" id="pso-panel-1">
                <div class="pso-upload-header-layout">
                    <div class="pso-upload-header-title">
                        <h1 class="pso-heading">Upload your document</h1>
                        <p class="pso-subheading">Securely upload your file to begin configuring your print.</p>
                    </div>
                    <div class="pso-accepted-files">
                        <span class="pso-accepted-label">Accepted formats:</span>
                        <?php 
                        $types_array = array_map('trim', explode(',', $allowed_types));
                        foreach ($types_array as $t): 
                            $t = strtolower($t);
                            $clean_t = ltrim($t, '.');
                            if (in_array($clean_t, ['doc', 'pptx'])) continue;

                            $color = '#6b7280';
                            if (in_array($clean_t, ['pdf'])) $color = '#ef4444';
                            elseif (in_array($clean_t, ['doc','docx'])) $color = '#3b82f6';
                            elseif (in_array($clean_t, ['ppt','pptx'])) $color = '#f97316';
                            elseif (in_array($clean_t, ['xls','xlsx'])) $color = '#10b981';
                            elseif (in_array($clean_t, ['jpg','jpeg','png'])) $color = '#8b5cf6';
                        ?>
                        <div class="pso-file-badge" style="--badge-color:<?php echo $color; ?>;" title="<?php echo strtoupper($clean_t); ?>">
                            <div class="pso-file-badge-fold"></div>
                            <div class="pso-file-badge-band">
                                <span><?php echo strtoupper($clean_t); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="pso-accepted-max">
                            &bull; Max <?php echo $max_mb; ?>MB
                        </div>
                    </div>
                </div>

                <div class="pso-upload-zone" id="pso-drop-zone">
                    <input type="file" id="pso-file-input" accept="<?php echo esc_attr($type_list); ?>" style="display:none;">

                    <div class="pso-upload-idle" id="pso-upload-idle">
                        <div class="pso-upload-graphic">
                            <div class="pso-upload-circle">
                                <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                            </div>
                            <div class="pso-upload-arrows">
                                <div class="pso-arrow-line"></div>
                                <div class="pso-arrow-line"></div>
                                <div class="pso-arrow-line"></div>
                            </div>
                        </div>
                        <p class="pso-upload-cta">Drop your file here</p>
                        <p class="pso-upload-or">or</p>
                        <button type="button" class="pso-btn-outline" id="pso-browse-btn">Browse files</button>
                    </div>

                    <div class="pso-upload-progress" id="pso-upload-progress" style="display:none;">
                        <div class="pso-file-icon">
                            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0120 9.414V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div id="pso-progress-filename" class="pso-progress-filename"></div>
                            <div class="pso-progress-track"><div class="pso-progress-fill" id="pso-progress-fill"></div></div>
                            <div id="pso-progress-pct" class="pso-progress-pct">0%</div>
                        </div>
                    </div>

                    <div class="pso-upload-success" id="pso-upload-success" style="display:none;">
                        <div class="pso-success-check">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div id="pso-success-filename" class="pso-success-filename"></div>
                            <div id="pso-success-meta" class="pso-success-meta"></div>
                        </div>
                        <button type="button" class="pso-reupload-btn" id="pso-reupload-btn">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Change
                        </button>
                    </div>
                </div>

                <div class="pso-upload-error" id="pso-upload-error" style="display:none;"></div>

                <div class="pso-panel-footer">
                    <div></div>
                    <button class="pso-btn-primary" id="pso-next-1" disabled>
                        Continue
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </div>
            </div>

            <!-- STEP 2: Print Options -->
            <div class="pso-panel" id="pso-panel-2" style="display:none;">
                <div class="pso-panel-header">
                    <h1 class="pso-heading">Configure print options</h1>
                    <p class="pso-subheading">Your price updates live as you adjust the settings below.</p>
                </div>

                <div class="pso-step2-layout">
                    <div class="pso-step2-options">
                        <div class="pso-options-grid">
                            <div class="pso-option-group">
                                <label class="pso-label">Paper Size</label>
                                <div class="pso-radio-cards" id="opt-paper-size">
                                    <label class="pso-radio-card active"><input type="radio" name="paper_size" value="A4" checked><div class="pso-radio-card-inner"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" stroke-width="1.5"/></svg><span>A4</span><small>210 × 297 mm</small></div></label>
                                    <label class="pso-radio-card"><input type="radio" name="paper_size" value="A3"><div class="pso-radio-card-inner"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2" stroke-width="1.5"/></svg><span>A3</span><small>297 × 420 mm</small></div></label>
                                    <label class="pso-radio-card"><input type="radio" name="paper_size" value="Short"><div class="pso-radio-card-inner"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2" stroke-width="1.5"/></svg><span>Short</span><small>216 × 279 mm</small></div></label>
                                    <label class="pso-radio-card"><input type="radio" name="paper_size" value="Long"><div class="pso-radio-card-inner"><svg width="18" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 18 22"><rect x="1" y="1" width="16" height="20" rx="2" stroke-width="1.5"/></svg><span>Long</span><small>216 × 330 mm</small></div></label>
                                </div>
                            </div>

                            <div class="pso-option-group">
                                <label class="pso-label">Color Mode</label>
                                <div class="pso-radio-cards" id="opt-color-mode">
                                    <label class="pso-radio-card active"><input type="radio" name="color_mode" value="bw" checked><div class="pso-radio-card-inner"><div class="pso-color-swatch bw"></div><span>Black &amp; White</span><small>Grayscale</small></div></label>
                                    <label class="pso-radio-card"><input type="radio" name="color_mode" value="color"><div class="pso-radio-card-inner"><div class="pso-color-swatch color"></div><span>Full Color</span><small>CMYK</small></div></label>
                                </div>
                            </div>

                            <div class="pso-two-col">
                                <div class="pso-option-group">
                                    <label class="pso-label">Orientation</label>
                                    <div class="pso-radio-cards" id="opt-orientation">
                                        <label class="pso-radio-card active"><input type="radio" name="orientation" value="portrait" checked><div class="pso-radio-card-inner"><svg width="16" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 16 20"><rect x="1" y="1" width="14" height="18" rx="1.5"/></svg><span>Portrait</span></div></label>
                                        <label class="pso-radio-card"><input type="radio" name="orientation" value="landscape"><div class="pso-radio-card-inner"><svg width="20" height="16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 20 16"><rect x="1" y="1" width="18" height="14" rx="1.5"/></svg><span>Landscape</span></div></label>
                                    </div>
                                </div>
                                <div class="pso-option-group">
                                    <label class="pso-label">Sides</label>
                                    <div class="pso-radio-cards" id="opt-sides">
                                        <label class="pso-radio-card active"><input type="radio" name="sides" value="single" checked><div class="pso-radio-card-inner"><span>Single</span><small>One-sided</small></div></label>
                                        <label class="pso-radio-card"><input type="radio" name="sides" value="double"><div class="pso-radio-card-inner"><span>Double</span><small>Two-sided</small></div></label>
                                    </div>
                                </div>
                            </div>

                            <div class="pso-two-col">
                                <div class="pso-option-group">
                                    <label class="pso-label">Number of Copies</label>
                                    <div class="pso-qty-control">
                                        <button type="button" class="pso-qty-btn" id="pso-qty-minus"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg></button>
                                        <input type="number" id="opt-copies" value="1" min="1" max="999" class="pso-qty-input" readonly>
                                        <button type="button" class="pso-qty-btn" id="pso-qty-plus"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg></button>
                                    </div>
                                </div>
                                <div class="pso-option-group">
                                    <label class="pso-label">Binding</label>
                                    <select id="opt-binding" class="pso-select">
                                        <option value="none">None</option>
                                        <option value="staple">Staple</option>
                                        <option value="spiral">Spiral Binding</option>
                                        <option value="hardcover">Hard Cover</option>
                                    </select>
                                </div>
                            </div>

                            <div class="pso-option-group">
                                <label class="pso-label">Special Instructions <span class="pso-optional">(optional)</span></label>
                                <textarea id="opt-notes" class="pso-textarea" rows="3" placeholder="e.g. Print pages 1–10 only, do not staple..."></textarea>
                            </div>

                            <?php if (!empty($extra_services)): ?>
                            <div class="pso-option-group">
                                <label class="pso-label">Additional Services <span class="pso-optional">(optional)</span></label>
                                <div style="display:flex;flex-direction:column;gap:10px;">
                                    <?php foreach ($extra_services as $svc): ?>
                                    <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid var(--pso-border);border-radius:var(--pso-radius-sm);cursor:pointer;transition:border-color .2s;background:#fff;">
                                        <input type="checkbox"
                                            class="pso-extra-svc"
                                            data-name="<?php echo esc_attr($svc['name']); ?>"
                                            data-price="<?php echo esc_attr($svc['price']); ?>"
                                            data-per-copy="<?php echo esc_attr($svc['per_copy'] ?? 0); ?>"
                                            style="width:16px;height:16px;accent-color:var(--pso-accent);cursor:pointer;flex-shrink:0;">
                                        <div>
                                            <div style="font-size:13px;font-weight:600;color:var(--pso-ink);">
                                                <?php echo esc_html($svc['name']); ?> — <span style="color:var(--pso-accent);">₱<?php echo number_format($svc['price'], 2); ?></span><?php if (!empty($svc['per_copy'])): ?> <span style="font-size:11px;color:var(--pso-ink-4);">per copy</span><?php endif; ?>
                                            </div>
                                            <?php if (!empty($svc['desc'])): ?>
                                            <div style="font-size:11px;color:var(--pso-ink-4);margin-top:2px;"><?php echo esc_html($svc['desc']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="pso-price-card" id="pso-price-card">
                            <div class="pso-price-card-bg"></div>
                            <div class="pso-price-card-content">
                                <div class="pso-price-rows">
                                    <div class="pso-price-row"><span>Pages &times; Copies</span><span id="pv-pages" class="pso-price-val">—</span></div>
                                    <div class="pso-price-row"><span>Print cost</span><span id="pv-print" class="pso-price-val">₱0.00</span></div>
                                    <div class="pso-price-row" id="pv-binding-row" style="display:none;"><span>Binding</span><span id="pv-binding" class="pso-price-val">₱0.00</span></div>
                                    <div class="pso-price-row" id="pv-extras-row" style="display:none;"><span>Extra services</span><span id="pv-extras" class="pso-price-val">₱0.00</span></div>
                                </div>
                                <div class="pso-price-total-row"><span>Total</span><span id="pv-total" class="pso-price-total-val">₱0.00</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Live Preview -->
                    <div class="pso-step2-preview">
                        <div class="pso-preview-wrap">
                            <div class="pso-preview-header">
                                <div class="pso-preview-title">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Live Preview
                                </div>
                                <span class="pso-preview-badge" id="pso-preview-badge">A4 · B&amp;W · Portrait</span>
                            </div>

                            <div class="pso-preview-stage" id="pso-preview-stage">
                                <div class="pso-preview-desk">
                                    <div class="pso-page-shadow"></div>
                                    <div class="pso-page" id="pso-preview-page">
                                        <div class="pso-copies-stack" id="pso-copies-stack"></div>
                                        <div class="pso-page-inner" id="pso-page-inner">
                                            <div class="pso-binding-indicator" id="pso-binding-indicator" style="display:none;"></div>
                                            <div class="pso-page-header-block">
                                                <div class="pso-page-line pso-line-title"></div>
                                                <div class="pso-page-line pso-line-sub" style="width:58%;"></div>
                                            </div>
                                            <div class="pso-page-body">
                                                <div class="pso-page-line" style="width:100%"></div>
                                                <div class="pso-page-line" style="width:93%"></div>
                                                <div class="pso-page-line" style="width:97%"></div>
                                                <div class="pso-page-line" style="width:86%"></div>
                                                <div class="pso-page-line pso-line-gap"></div>
                                                <div class="pso-page-line" style="width:100%"></div>
                                                <div class="pso-page-line" style="width:89%"></div>
                                                <div class="pso-page-line" style="width:95%"></div>
                                                <div class="pso-page-line" style="width:79%"></div>
                                                <div class="pso-page-line pso-line-gap"></div>
                                                <div class="pso-page-line" style="width:100%"></div>
                                                <div class="pso-page-line" style="width:91%"></div>
                                                <div class="pso-page-line" style="width:84%"></div>
                                                <div class="pso-page-line" style="width:100%"></div>
                                                <div class="pso-page-line" style="width:72%"></div>
                                            </div>
                                            <div class="pso-page-color-swatches" id="pso-color-swatches" style="display:none;">
                                                <div class="pso-swatch-row">
                                                    <div class="pso-swatch" style="background:#e63946;"></div>
                                                    <div class="pso-swatch" style="background:#2a9d8f;"></div>
                                                    <div class="pso-swatch" style="background:#e9c46a;"></div>
                                                    <div class="pso-swatch" style="background:#264653;"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="pso-page-number">1</div>
                                    </div>
                                </div>

                                <div class="pso-preview-specs">
                                    <span class="pso-spec-pill" id="spec-size">A4 · 210×297mm</span>
                                    <span class="pso-spec-pill" id="spec-color">Black &amp; White</span>
                                    <span class="pso-spec-pill" id="spec-sides">Single-sided</span>
                                    <span class="pso-spec-pill" id="spec-copies">1 copy</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pso-panel-footer">
                    <button class="pso-btn-ghost" id="pso-back-2"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>Back</button>
                    <button class="pso-btn-primary" id="pso-next-2">Continue<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></button>
                </div>
            </div>

            <!-- STEP 3: Customer Info -->
            <div class="pso-panel" id="pso-panel-3" style="display:none;">
                <div class="pso-panel-header">
                    <h1 class="pso-heading">Your details</h1>
                    <p class="pso-subheading">We'll use this to notify you when your order is ready.</p>
                </div>

                <div class="pso-fields-grid">
                    <div class="pso-field-group pso-full"><label class="pso-label">Full Name <span class="pso-required">*</span></label><input type="text" id="cust-name" class="pso-input" placeholder="Juan dela Cruz" autocomplete="name"></div>
                    <div class="pso-field-group"><label class="pso-label">Email Address</label><input type="email" id="cust-email" class="pso-input" placeholder="juan@email.com" autocomplete="email"></div>
                    <div class="pso-field-group"><label class="pso-label">Phone Number</label><input type="tel" id="cust-phone" class="pso-input" placeholder="09XX XXX XXXX" autocomplete="tel"></div>
                    <div class="pso-field-group pso-full">
                        <label class="pso-label">Payment Method <span class="pso-required">*</span></label>
                        <div class="pso-payment-options">
                            <label class="pso-payment-card active"><input type="radio" name="payment_method" value="cash" checked><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>Cash</label>
                            <label class="pso-payment-card"><input type="radio" name="payment_method" value="gcash"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>GCash</label>
                        </div>
                    </div>
                </div>

                <!-- GCash Details Panel -->
                <div id="pso-gcash-panel" style="display:none;margin-top:16px;padding:20px;background:#f0fdf4;border:1.5px solid #86efac;border-radius:var(--pso-radius-sm);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                        <svg width="20" height="20" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <strong style="font-size:13px;color:#15803d;">Send payment via GCash</strong>
                    </div>
                    <?php if ($gcash_qr_url): ?>
                    <div style="text-align:center;margin-bottom:14px;">
                        <img src="<?php echo esc_url($gcash_qr_url); ?>" alt="GCash QR Code" style="max-width:180px;border-radius:8px;border:1px solid #bbf7d0;">
                        <div style="font-size:11px;color:#6b7280;margin-top:6px;">Scan QR to pay</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($gcash_number || $gcash_name): ?>
                    <div style="background:#fff;border-radius:8px;padding:14px;font-size:13px;line-height:1.8;">
                        <?php if ($gcash_name): ?>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:#6b7280;">Account Name</span>
                            <strong style="color:#15803d;"><?php echo esc_html($gcash_name); ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if ($gcash_number): ?>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:#6b7280;">GCash Number</span>
                            <strong style="color:#15803d;"><?php echo esc_html($gcash_number); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!$gcash_name && !$gcash_number && !$gcash_qr_url): ?>
                    <p style="font-size:13px;color:#6b7280;margin:0;">GCash details not yet configured. Please contact the shop.</p>
                    <?php endif; ?>
                    <p style="font-size:11px;color:#6b7280;margin:10px 0 0;font-style:italic;">Send the exact amount and include your name as reference.</p>
                    <label id="pso-gcash-confirm-wrap" style="display:flex;align-items:center;gap:10px;margin-top:16px;padding:12px 16px;background:#fff;border:1.5px solid #86efac;border-radius:8px;cursor:pointer;">
                        <input type="checkbox" id="pso-gcash-confirm" style="width:18px;height:18px;accent-color:#16a34a;cursor:pointer;flex-shrink:0;">
                        <span style="font-size:13px;font-weight:600;color:#15803d;">I have already sent the GCash payment</span>
                    </label>
                    <div id="pso-gcash-confirm-error" style="display:none;margin-top:8px;font-size:12px;color:#dc2626;font-weight:500;">⚠ Please confirm your GCash payment before proceeding.</div>
                </div>

                <div class="pso-panel-footer">
                    <button class="pso-btn-ghost" id="pso-back-3"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>Back</button>
                    <button class="pso-btn-primary" id="pso-next-3">Review Order<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></button>
                </div>
            </div>

            <!-- STEP 4: Review -->
            <div class="pso-panel" id="pso-panel-4" style="display:none;">
                <div class="pso-panel-header">
                    <h1 class="pso-heading">Review your order</h1>
                    <p class="pso-subheading">Please check everything before submitting.</p>
                </div>

                <div class="pso-review-grid">
                    <div class="pso-review-section">
                        <div class="pso-review-section-title"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0120 9.414V19a2 2 0 01-2 2z"/></svg>Document</div>
                        <div class="pso-review-item"><span>File</span><strong id="rv-file">—</strong></div>
                        <div class="pso-review-item"><span>Pages detected</span><strong id="rv-pages">—</strong></div>
                    </div>
                    <div class="pso-review-section">
                        <div class="pso-review-section-title"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17H17.01M17 20H5a2 2 0 01-2-2V9a2 2 0 012-2h2V5a2 2 0 012-2h6a2 2 0 012 2v2h2a2 2 0 012 2v7a2 2 0 01-2 2z"/></svg>Print Settings</div>
                        <div class="pso-review-item"><span>Paper</span><strong id="rv-paper">—</strong></div>
                        <div class="pso-review-item"><span>Color</span><strong id="rv-color">—</strong></div>
                        <div class="pso-review-item"><span>Orientation</span><strong id="rv-orientation">—</strong></div>
                        <div class="pso-review-item"><span>Sides</span><strong id="rv-sides">—</strong></div>
                        <div class="pso-review-item"><span>Copies</span><strong id="rv-copies">—</strong></div>
                        <div class="pso-review-item"><span>Binding</span><strong id="rv-binding">—</strong></div>
                        <div class="pso-review-item" id="rv-extras-row" style="display:none;"><span>Add-ons</span><strong id="rv-extras">—</strong></div>
                    </div>
                    <div class="pso-review-section">
                        <div class="pso-review-section-title"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Customer</div>
                        <div class="pso-review-item"><span>Name</span><strong id="rv-name">—</strong></div>
                        <div class="pso-review-item"><span>Email</span><strong id="rv-email">—</strong></div>
                        <div class="pso-review-item"><span>Phone</span><strong id="rv-phone">—</strong></div>
                        <div class="pso-review-item"><span>Payment</span><strong id="rv-payment">—</strong></div>
                    </div>
                </div>

                <div class="pso-total-banner">
                    <div>
                        <div class="pso-total-label">Order Total</div>
                        <div class="pso-total-note" id="rv-total-note">—</div>
                    </div>
                    <div class="pso-total-amount" id="rv-total">₱0.00</div>
                </div>

                <div id="pso-submit-error" class="pso-error-msg" style="display:none;"></div>

                <div class="pso-panel-footer">
                    <button class="pso-btn-ghost" id="pso-back-4"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>Back</button>
                    <button class="pso-btn-primary pso-btn-submit" id="pso-submit-order" data-nonce="<?php echo esc_attr($order_nonce); ?>">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Submit Order
                    </button>
                </div>
            </div>

            <!-- SUCCESS -->
            <div class="pso-panel pso-success-panel" id="pso-panel-success" style="display:none;">
                <div class="pso-success-inner">
                    <div class="pso-success-icon"><svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                    <h2 class="pso-success-heading">Order Submitted!</h2>
                    <p class="pso-success-sub" id="pso-success-msg"></p>

                    <div class="pso-order-id-chip">
                        <span class="pso-order-id-label">Your Order ID</span>
                        <span class="pso-order-id-value" id="pso-order-id-display">—</span>
                        <button class="pso-copy-btn" id="pso-copy-id" title="Copy Order ID">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>

                    <div class="pso-pickup-box">
                        <div class="pso-pickup-box-title">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Pickup Details
                        </div>
                        <div><?php echo esc_html($shop_name); ?></div>
                        <?php if ($shop_address): ?><div style="color:#6b7280;"><?php echo esc_html($shop_address); ?></div><?php endif; ?>
                        <div style="color:#6b7280;"><?php echo esc_html($shop_hours); ?></div>
                        <?php if ($shop_note): ?><div class="pso-pickup-note"><?php echo esc_html($shop_note); ?></div><?php endif; ?>
                    </div>

                    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:8px;">
                        <?php $tracking_url = get_permalink(get_page_by_path('order-tracking')); ?>
                        <?php if ($tracking_url): ?>
                        <a href="<?php echo esc_url($tracking_url); ?>" class="pso-btn-primary">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            Order Tracking
                        </a>
                        <?php endif; ?>
                        <button class="pso-btn-ghost" id="pso-new-order">Place Another Order</button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap');
    :root {
        --pso-ink:#0d1117; --pso-ink-2:#374151; --pso-ink-3:#6b7280; --pso-ink-4:#9ca3af;
        --pso-surface:#fff; --pso-surface-2:#f0f4ff; --pso-border:#dde3f0;
        --pso-accent:#1a3c8f; --pso-accent-2:#0d2466; --pso-accent-bg:#e8eeff;
        --pso-green:#16a34a; --pso-green-light:#22c55e; --pso-green-bg:#dcfce7; --pso-red:#dc2626; --pso-red-bg:#fef2f2;
        --pso-sidebar-w:300px; --pso-radius:12px; --pso-radius-sm:8px;
        --pso-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);
        --pso-shadow-lg:0 8px 40px rgba(13,36,102,.15);
        --pso-font-body:'DM Sans',system-ui,sans-serif;
        --pso-font-head:'DM Serif Display',Georgia,serif;
    }
    .pso-root { display:flex; min-height:100vh; height:100vh; font-family:var(--pso-font-body); color:var(--pso-ink); background:var(--pso-surface); border-radius:0; overflow:hidden; box-shadow:none; border:none; position:fixed; inset:0; z-index:9990; }
    .pso-sidebar { width:var(--pso-sidebar-w); background:linear-gradient(160deg,#0d2466 0%,#1a3c8f 60%,#0f5c3a 100%); flex-shrink:0; position:relative; overflow-y:auto; overflow-x:hidden; height:100%; box-sizing:border-box; }
    .pso-sidebar::before { content:''; position:absolute; bottom:-80px; right:-80px; width:260px; height:260px; border-radius:50%; background:rgba(34,197,94,.08); pointer-events:none; }
    .pso-sidebar::after  { content:''; position:absolute; top:-60px; left:-60px; width:200px; height:200px; border-radius:50%; background:rgba(255,255,255,.05); pointer-events:none; }
    .pso-sidebar-inner { padding:36px 28px 32px; min-height:100%; height:100%; box-sizing:border-box; display:flex; flex-direction:column; gap:32px; position:relative; z-index:1; }
    .pso-brand { display:flex; align-items:center; gap:12px; }
    .pso-brand-icon { width:48px; height:48px; background:linear-gradient(135deg,rgba(34,197,94,.3),rgba(22,163,74,.5)); border:1px solid rgba(34,197,94,.4); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0; }
    .pso-brand-name { font-family:var(--pso-font-head); font-size:16px; color:#fff; line-height:1.2; }
    .pso-brand-sub { font-size:11px; color:rgba(255,255,255,.45); margin-top:2px; letter-spacing:.3px; }
    .pso-brand-link { display:inline-flex; align-items:center; gap:4px; font-size:11px; color:rgba(255,255,255,.6); margin-top:8px; text-decoration:none; transition:color .2s; }
    .pso-brand-link:hover { color:#fff; }
    .pso-step-nav { display:flex; flex-direction:column; gap:0; }
    .pso-step-item { display:flex; align-items:flex-start; gap:14px; padding:12px 0; opacity:.4; transition:opacity .25s; }
    .pso-step-item.active { opacity:1; }
    .pso-step-item.done   { opacity:.7; }
    .pso-step-connector { width:2px; height:20px; background:rgba(255,255,255,.1); margin-left:19px; }
    .pso-step-dot { width:38px; height:38px; border-radius:50%; border:2px solid rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .25s; }
    .pso-step-item.active .pso-step-dot { border-color:var(--pso-green-light); background:var(--pso-green-light); }
    .pso-step-item.done   .pso-step-dot { border-color:var(--pso-green); background:var(--pso-green); }
    .pso-step-num { font-size:13px; font-weight:600; color:rgba(255,255,255,.6); }
    .pso-step-item.active .pso-step-num { color:#fff; }
    .pso-step-check { display:none; color:#fff; }
    .pso-step-item.done .pso-step-num   { display:none; }
    .pso-step-item.done .pso-step-check { display:block; }
    .pso-step-info { padding-top:6px; }
    .pso-step-label { font-size:10px; color:rgba(255,255,255,.4); letter-spacing:.5px; text-transform:uppercase; }
    .pso-step-title { font-size:13px; color:rgba(255,255,255,.8); font-weight:500; margin-top:1px; }
    .pso-step-item.active .pso-step-title { color:#fff; font-weight:600; }
    .pso-info-card { margin-top:auto; background:rgba(34,197,94,.07); border:1px solid rgba(34,197,94,.2); border-radius:var(--pso-radius-sm); padding:16px; font-size:12px; color:rgba(255,255,255,.7); line-height:1.6; flex-shrink:0; }
    .pso-info-card-title { display:flex; align-items:center; gap:6px; color:rgba(255,255,255,.9); font-weight:600; font-size:11px; letter-spacing:.4px; text-transform:uppercase; margin-bottom:10px; }
    .pso-info-card-text { margin-bottom:4px; }
    .pso-info-card-note { margin-top:8px; font-size:11px; color:rgba(255,255,255,.35); font-style:italic; }
    .pso-main { flex:1; padding:48px 52px; overflow-y:auto; display:flex; flex-direction:column; height:100%; box-sizing:border-box; }
    .pso-panel { display:flex; flex-direction:column; flex:1; animation:psoFadeIn .3s ease; }
    @keyframes psoFadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
    .pso-panel-header { margin-bottom:32px; }
    .pso-heading { font-family:var(--pso-font-head); font-size:30px; font-weight:400; color:var(--pso-ink); margin:0 0 6px; line-height:1.2; }
    .pso-subheading { font-size:14px; color:var(--pso-ink-3); margin:0; }
    .pso-upload-header-layout { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:20px; margin-bottom:32px; padding-bottom:16px; border-bottom:1px solid var(--pso-border); }
    .pso-upload-header-title { flex:1; min-width:280px; }
    .pso-upload-header-title .pso-heading { margin-bottom:4px; }
    .pso-accepted-files { display:flex; align-items:center; justify-content:flex-end; gap:8px; flex-wrap:wrap; }
    .pso-accepted-label { font-size:12px; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-right:4px; }
    .pso-file-badge { position:relative; width:34px; height:44px; background:#f8f9fa; border-radius:4px 12px 4px 4px; border:1px solid #e5e7eb; box-shadow:0 2px 5px rgba(0,0,0,0.05); display:flex; align-items:flex-end; overflow:hidden; }
    .pso-file-badge-fold { position:absolute; top:-1px; right:-1px; width:12px; height:12px; background:#fff; border-bottom:1px solid #e5e7eb; border-left:1px solid #e5e7eb; border-bottom-left-radius:4px; z-index:1; }
    .pso-file-badge-band { width:100%; height:16px; background:var(--badge-color); display:flex; align-items:center; justify-content:center; }
    .pso-file-badge-band span { color:#fff; font-size:9px; font-weight:700; letter-spacing:0.5px; line-height:1; }
    .pso-accepted-max { font-size:13px; color:#9ca3af; margin-left:8px; font-weight:500; }
    .pso-panel-footer { display:flex; justify-content:space-between; align-items:center; margin-top:auto; padding-top:32px; border-top:1px solid var(--pso-border); }
    .pso-btn-primary { display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#16a34a,#22c55e); color:#fff; border:none; border-radius:var(--pso-radius-sm); padding:12px 24px; font-size:14px; font-weight:600; font-family:var(--pso-font-body); cursor:pointer; text-decoration:none; transition:background .2s,transform .1s,box-shadow .2s; box-shadow:0 2px 8px rgba(22,163,74,.3); }
    .pso-btn-primary:hover { background:linear-gradient(135deg,#15803d,#16a34a); box-shadow:0 4px 16px rgba(22,163,74,.4); }
    .pso-btn-primary:active { transform:scale(.98); }
    .pso-btn-primary:disabled { background:#9ca3af; box-shadow:none; cursor:not-allowed; }
    .pso-btn-ghost { display:inline-flex; align-items:center; gap:8px; background:none; border:1px solid var(--pso-border); color:var(--pso-ink-3); border-radius:var(--pso-radius-sm); padding:11px 20px; font-size:14px; font-weight:500; font-family:var(--pso-font-body); cursor:pointer; transition:border-color .2s,color .2s,background .2s; }
    .pso-btn-ghost:hover { border-color:var(--pso-ink-3); color:var(--pso-ink); background:var(--pso-surface-2); }
    .pso-btn-outline { display:inline-flex; align-items:center; gap:8px; background:none; border:1.5px solid var(--pso-accent); color:var(--pso-accent); border-radius:var(--pso-radius-sm); padding:10px 22px; font-size:14px; font-weight:600; font-family:var(--pso-font-body); cursor:pointer; transition:all .2s; }
    .pso-btn-outline:hover { background:var(--pso-accent); color:#fff; }
    .pso-upload-zone { border:2px dashed var(--pso-border); border-radius:var(--pso-radius); padding:56px 32px; text-align:center; transition:border-color .2s,background .2s; background:var(--pso-surface-2); position:relative; }
    .pso-upload-zone.idle { cursor:pointer; }
    .pso-upload-zone.idle:hover,.pso-upload-zone.dragging { border-color:var(--pso-accent); background:var(--pso-accent-bg); }
    .pso-upload-zone.has-file { border-color:var(--pso-green-light); background:var(--pso-green-bg); cursor:default; }
    .pso-upload-graphic { position:relative; display:inline-block; margin-bottom:16px; }
    .pso-upload-circle { width:72px; height:72px; border-radius:50%; background:#fff; border:1.5px solid var(--pso-border); display:flex; align-items:center; justify-content:center; color:var(--pso-accent); box-shadow:var(--pso-shadow); margin:0 auto; }
    .pso-upload-arrows { position:absolute; top:-8px; left:50%; transform:translateX(-50%); display:flex; gap:4px; }
    .pso-arrow-line { width:2px; height:10px; background:var(--pso-accent); border-radius:1px; opacity:.3; animation:psoArrow 1.4s ease-in-out infinite; }
    .pso-arrow-line:nth-child(2) { animation-delay:.2s; opacity:.6; }
    .pso-arrow-line:nth-child(3) { animation-delay:.4s; opacity:.9; }
    @keyframes psoArrow { 0%,100%{transform:scaleY(1)} 50%{transform:scaleY(1.6)} }
    .pso-upload-cta { font-size:16px; font-weight:600; color:var(--pso-ink); margin:0 0 6px; }
    .pso-upload-or  { font-size:13px; color:var(--pso-ink-4); margin:0 0 14px; }
    .pso-upload-progress { display:flex; align-items:center; gap:16px; text-align:left; }
    .pso-file-icon { color:var(--pso-accent); flex-shrink:0; }
    .pso-progress-filename { font-size:13px; font-weight:600; color:var(--pso-ink); margin-bottom:8px; word-break:break-word; overflow-wrap:anywhere; }
    .pso-progress-track { height:6px; background:var(--pso-border); border-radius:99px; overflow:hidden; }
    .pso-progress-fill { height:100%; width:0%; background:var(--pso-accent); border-radius:99px; transition:width .3s; }
    .pso-progress-pct { font-size:11px; color:var(--pso-ink-4); margin-top:4px; }
    .pso-upload-success { display:flex; align-items:center; gap:16px; text-align:left; }
    .pso-success-check { width:48px; height:48px; border-radius:50%; background:var(--pso-green); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .pso-success-filename { font-size:14px; font-weight:600; color:var(--pso-ink); word-break:break-word; overflow-wrap:anywhere; }
    .pso-success-meta { font-size:12px; color:var(--pso-ink-3); margin-top:3px; }
    .pso-reupload-btn { margin-left:auto; display:inline-flex; align-items:center; gap:5px; background:none; border:1px solid var(--pso-border); border-radius:6px; padding:6px 12px; font-size:12px; color:var(--pso-ink-3); cursor:pointer; transition:all .2s; }
    .pso-reupload-btn:hover { border-color:var(--pso-ink-3); color:var(--pso-ink); }
    .pso-upload-error { margin-top:12px; padding:12px 16px; background:var(--pso-red-bg); border:1px solid #fecaca; border-radius:var(--pso-radius-sm); color:var(--pso-red); font-size:13px; }
    .pso-step2-layout { display:grid; grid-template-columns:1fr 320px; gap:28px; align-items:start; }
    .pso-step2-options { min-width:0; }
    .pso-step2-preview { position:sticky; top:20px; }
    .pso-preview-wrap { border:1.5px solid var(--pso-border); border-radius:var(--pso-radius); overflow:hidden; background:var(--pso-surface-2); }
    .pso-preview-header { display:flex; align-items:center; justify-content:space-between; padding:11px 16px; border-bottom:1px solid var(--pso-border); background:#fff; }
    .pso-preview-title { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--pso-ink-3); }
    .pso-preview-badge { font-size:10px; font-weight:600; color:var(--pso-accent); background:var(--pso-accent-bg); padding:3px 9px; border-radius:99px; letter-spacing:.2px; transition:all .3s; white-space:nowrap; }
    .pso-preview-stage { padding:28px 20px 18px; display:flex; flex-direction:column; align-items:center; gap:16px; background:repeating-linear-gradient(45deg,transparent,transparent 10px,rgba(0,0,0,.018) 10px,rgba(0,0,0,.018) 20px),#eef0f4; }
    .pso-preview-desk { position:relative; display:flex; align-items:flex-end; justify-content:center; }
    .pso-page-shadow { position:absolute; bottom:-8px; left:50%; transform:translateX(-50%); width:85%; height:18px; background:radial-gradient(ellipse at center,rgba(0,0,0,.25) 0%,transparent 70%); filter:blur(5px); pointer-events:none; }
    .pso-page { position:relative; width:130px; height:184px; background:#fff; border-radius:2px; box-shadow:0 1px 3px rgba(0,0,0,.1),0 6px 20px rgba(0,0,0,.14),inset 0 0 0 1px rgba(0,0,0,.06); transition:width .4s cubic-bezier(.4,0,.2,1), height .4s cubic-bezier(.4,0,.2,1); overflow:visible; }
    .pso-page.double-sided::after { content:''; position:absolute; top:5px; right:-6px; width:100%; height:100%; background:#f5f5f5; border-radius:2px; box-shadow:0 1px 4px rgba(0,0,0,.1),inset 0 0 0 1px rgba(0,0,0,.05); z-index:-1; }
    .pso-copies-stack { position:absolute; inset:0; pointer-events:none; z-index:-1; }
    .pso-copy-ghost { position:absolute; background:#fff; border-radius:2px; box-shadow:0 1px 4px rgba(0,0,0,.1),inset 0 0 0 1px rgba(0,0,0,.05); }
    .pso-page-inner { padding:13px 13px 10px; height:100%; box-sizing:border-box; overflow:hidden; display:flex; flex-direction:column; gap:0; position:relative; }
    .pso-page-header-block { margin-bottom:9px; }
    .pso-page-line { height:4px; border-radius:99px; background:#e2e5ea; margin-bottom:4px; transition:background .35s; }
    .pso-page-line.pso-line-title { height:7px; width:68%; background:#b8c0ce; margin-bottom:5px; }
    .pso-page-line.pso-line-sub   { height:4px; background:#cdd2db; }
    .pso-line-gap { margin-top:3px; }
    .pso-page-inner.bw-mode .pso-page-line  { background:#e2e5ea; }
    .pso-page-inner.bw-mode .pso-line-title { background:#b8c0ce; }
    .pso-page-inner.bw-mode .pso-line-sub   { background:#cdd2db; }
    .pso-page-inner.color-mode .pso-page-line  { background:#c8e0f4; }
    .pso-page-inner.color-mode .pso-line-title { background:#5a9fd4; }
    .pso-page-inner.color-mode .pso-line-sub   { background:#8bbde0; }
    .pso-page-color-swatches { margin-top:auto; padding-top:6px; }
    .pso-swatch-row { display:flex; gap:3px; }
    .pso-swatch { width:14px; height:14px; border-radius:3px; transition:filter .35s; }
    .pso-page-inner.bw-mode .pso-swatch { filter:grayscale(1); }
    .pso-binding-indicator { position:absolute; top:0; bottom:0; left:0; width:7px; background:repeating-linear-gradient(180deg,#c0c8d8 0px,#c0c8d8 5px,transparent 5px,transparent 9px); border-radius:2px 0 0 2px; }
    .pso-binding-indicator.staple { background:repeating-linear-gradient(180deg,#8899bb 0px,#8899bb 3px,transparent 3px,transparent 14px); width:5px; }
    .pso-binding-indicator.spiral { background:repeating-linear-gradient(180deg,#7a8fbb 0px,#7a8fbb 4px,transparent 4px,transparent 8px); width:8px; }
    .pso-binding-indicator.hardcover { background:linear-gradient(180deg,#4a5a7a,#2e3d5e); width:11px; box-shadow:inset -2px 0 4px rgba(0,0,0,.25); }
    .pso-page-number { position:absolute; bottom:5px; right:8px; font-size:6px; color:#c0c8d8; font-family:var(--pso-font-body); user-select:none; }
    .pso-preview-specs { display:flex; gap:6px; flex-wrap:wrap; justify-content:center; }
    .pso-spec-pill { font-size:10px; font-weight:600; padding:3px 10px; border-radius:99px; background:rgba(255,255,255,.85); border:1px solid rgba(0,0,0,.08); color:var(--pso-ink-3); backdrop-filter:blur(4px); transition:all .2s; }
    .pso-options-grid { display:flex; flex-direction:column; gap:24px; }
    .pso-label { display:block; font-size:13px; font-weight:600; color:var(--pso-ink-2); margin-bottom:10px; }
    .pso-optional { font-weight:400; color:var(--pso-ink-4); }
    .pso-required { color:var(--pso-red); }
    .pso-two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    .pso-radio-cards { display:flex; gap:10px; flex-wrap:wrap; }
    .pso-radio-card { cursor:pointer; }
    .pso-radio-card input { display:none; }
    .pso-radio-card-inner { display:flex; flex-direction:column; align-items:center; gap:4px; padding:14px 18px; border:1.5px solid var(--pso-border); border-radius:var(--pso-radius-sm); background:var(--pso-surface); transition:all .2s; min-width:80px; text-align:center; }
    .pso-radio-card-inner span { font-size:13px; font-weight:600; color:var(--pso-ink-2); }
    .pso-radio-card-inner small { font-size:10px; color:var(--pso-ink-4); }
    .pso-radio-card.active .pso-radio-card-inner { border-color:var(--pso-accent); background:var(--pso-accent-bg); }
    .pso-radio-card.active .pso-radio-card-inner span { color:var(--pso-accent-2); }
    .pso-radio-card.active .pso-radio-card-inner svg { color:var(--pso-accent); }
    .pso-radio-card:hover .pso-radio-card-inner { border-color:var(--pso-accent); }
    .pso-color-swatch { width:20px; height:20px; border-radius:50%; border:1.5px solid var(--pso-border); }
    .pso-color-swatch.bw    { background:linear-gradient(135deg,#000 50%,#fff 50%); }
    .pso-color-swatch.color { background:conic-gradient(red 0deg,yellow 120deg,cyan 240deg,red 360deg); }
    .pso-qty-control { display:flex; align-items:center; border:1.5px solid var(--pso-border); border-radius:var(--pso-radius-sm); overflow:hidden; background:#fff; }
    .pso-qty-btn { width:40px; height:42px; background:none; border:none; display:flex; align-items:center; justify-content:center; color:var(--pso-ink-3); cursor:pointer; transition:background .15s,color .15s; }
    .pso-qty-btn:hover { background:var(--pso-surface-2); color:var(--pso-ink); }
    .pso-qty-input { flex:1; text-align:center; border:none; outline:none; font-size:15px; font-weight:700; font-family:var(--pso-font-body); color:var(--pso-ink); background:transparent; -moz-appearance:textfield; }
    .pso-qty-input::-webkit-outer-spin-button,.pso-qty-input::-webkit-inner-spin-button { -webkit-appearance:none; }
    .pso-select { width:100%; padding:11px 14px; border:1.5px solid var(--pso-border); border-radius:var(--pso-radius-sm); font-size:14px; font-family:var(--pso-font-body); color:var(--pso-ink); background:#fff; cursor:pointer; transition:border-color .2s; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%236b7280' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 14px center; padding-right:40px; }
    .pso-select:focus { outline:none; border-color:var(--pso-accent); }
    .pso-textarea { width:100%; box-sizing:border-box; padding:12px 14px; border:1.5px solid var(--pso-border); border-radius:var(--pso-radius-sm); font-size:14px; font-family:var(--pso-font-body); color:var(--pso-ink); resize:vertical; transition:border-color .2s; background:#fff; }
    .pso-textarea:focus { outline:none; border-color:var(--pso-accent); }
    .pso-textarea::placeholder { color:var(--pso-ink-4); }
    .pso-price-card { position:relative; overflow:hidden; border-radius:var(--pso-radius); margin-top:20px; }
    .pso-price-card-bg { position:absolute; inset:0; background:linear-gradient(135deg,#0d2466 0%,#1a3c8f 60%,#0f5c3a 100%); }
    .pso-price-card-content { position:relative; padding:24px 28px; }
    .pso-price-rows { display:flex; flex-direction:column; gap:8px; margin-bottom:16px; }
    .pso-price-row { display:flex; justify-content:space-between; font-size:13px; color:rgba(255,255,255,.65); }
    .pso-price-val { color:rgba(255,255,255,.9); font-weight:500; }
    .pso-price-total-row { display:flex; justify-content:space-between; align-items:center; padding-top:16px; border-top:1px solid rgba(255,255,255,.12); color:rgba(255,255,255,.8); font-size:14px; font-weight:500; }
    .pso-price-total-val { font-size:28px; font-weight:700; color:#fff; font-family:var(--pso-font-head); }
    .pso-fields-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:8px; }
    .pso-field-group { display:flex; flex-direction:column; }
    .pso-field-group.pso-full { grid-column:1 / -1; }
    .pso-input { padding:12px 14px; border:1.5px solid var(--pso-border); border-radius:var(--pso-radius-sm); font-size:14px; font-family:var(--pso-font-body); color:var(--pso-ink); background:#fff; transition:border-color .2s; }
    .pso-input:focus { outline:none; border-color:var(--pso-accent); }
    .pso-input::placeholder { color:var(--pso-ink-4); }
    .pso-payment-options { display:flex; gap:10px; flex-wrap:wrap; }
    .pso-payment-card { display:flex; align-items:center; gap:8px; padding:12px 18px; border:1.5px solid var(--pso-border); border-radius:var(--pso-radius-sm); cursor:pointer; font-size:13px; font-weight:500; color:var(--pso-ink-2); transition:all .2s; background:#fff; }
    .pso-payment-card input { display:none; }
    .pso-payment-card:hover { border-color:var(--pso-accent); color:var(--pso-accent); }
    .pso-payment-card.active { border-color:var(--pso-accent); background:var(--pso-accent-bg); color:var(--pso-accent-2); }
    .pso-payment-card.active svg { stroke:var(--pso-accent); }
    .pso-review-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; margin-bottom:24px; }
    .pso-review-section { background:var(--pso-surface-2); border:1px solid var(--pso-border); border-radius:var(--pso-radius-sm); padding:18px; }
    .pso-review-section-title { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:var(--pso-ink-3); margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--pso-border); }
    .pso-review-item { display:flex; justify-content:space-between; align-items:baseline; font-size:12px; color:var(--pso-ink-3); padding:5px 0; border-bottom:1px solid var(--pso-border); }
    .pso-review-item:last-child { border-bottom:none; }
    .pso-review-item strong { font-size:13px; color:var(--pso-ink); font-weight:600; text-align:right; }
    .pso-total-banner { display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg,#0d2466,#1a3c8f); color:#fff; border-radius:var(--pso-radius-sm); padding:20px 28px; margin-bottom:20px; }
    .pso-total-label { font-size:12px; color:rgba(255,255,255,.55); text-transform:uppercase; letter-spacing:.5px; }
    .pso-total-note  { font-size:13px; color:rgba(255,255,255,.7); margin-top:3px; }
    .pso-total-amount { font-family:var(--pso-font-head); font-size:36px; color:#fff; }
    .pso-error-msg { padding:12px 16px; background:var(--pso-red-bg); border:1px solid #fecaca; border-radius:var(--pso-radius-sm); color:var(--pso-red); font-size:13px; margin-bottom:16px; }
    .pso-btn-submit { padding:14px 32px; font-size:15px; }
    .pso-success-panel { justify-content:center; }
    .pso-success-inner { max-width:480px; margin:0 auto; text-align:center; padding:32px 0; }
    .pso-success-icon { width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; box-shadow:0 8px 24px rgba(34,197,94,.35); }
    .pso-success-heading { font-family:var(--pso-font-head); font-size:32px; font-weight:400; color:var(--pso-ink); margin:0 0 10px; }
    .pso-success-sub { font-size:14px; color:var(--pso-ink-3); margin:0 0 28px; }
    .pso-order-id-chip { display:inline-flex; align-items:center; gap:12px; background:#e8eeff; border:1px solid #c7d4f7; border-radius:99px; padding:10px 20px; margin-bottom:28px; }
    .pso-order-id-label { font-size:11px; color:var(--pso-ink-4); text-transform:uppercase; letter-spacing:.5px; }
    .pso-order-id-value { font-size:16px; font-weight:800; color:#1a3c8f; letter-spacing:1px; }
    .pso-copy-btn { background:none; border:none; cursor:pointer; color:var(--pso-ink-4); padding:2px; transition:color .2s; }
    .pso-copy-btn:hover { color:#1a3c8f; }
    .pso-pickup-box { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:var(--pso-radius-sm); padding:20px; text-align:left; font-size:13px; line-height:1.7; margin-bottom:28px; }
    .pso-pickup-box-title { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:#16a34a; margin-bottom:10px; }
    .pso-pickup-note { font-size:11px; color:var(--pso-ink-4); margin-top:6px; font-style:italic; }
    .pso-extra-svc-label:has(input:checked) { border-color:var(--pso-accent) !important; background:var(--pso-accent-bg) !important; }
    @media(max-width:1100px){
        .pso-step2-layout { grid-template-columns:1fr; }
        .pso-step2-preview { position:static; }
        .pso-preview-stage { flex-direction:row; flex-wrap:wrap; justify-content:center; padding:20px; }
    }
    @media(max-width:900px){
        .pso-root { flex-direction:column; position:fixed; inset:0; overflow-y:auto; height:100dvh; display:block; }
        .pso-sidebar { width:100%; height:auto; flex-shrink:0; }
        .pso-sidebar-inner { padding:24px 24px 20px; gap:20px; height:auto; min-height:0; }
        .pso-step-nav { flex-direction:row; gap:0; overflow-x:auto; padding-bottom:10px; }
        .pso-step-nav::-webkit-scrollbar { display:none; }
        .pso-step-connector { width:20px; height:2px; margin:0; align-self:center; }
        .pso-step-item { flex-direction:column; gap:6px; align-items:center; min-width:70px; }
        .pso-step-info { text-align:center; padding-top:0; }
        .pso-step-label { display:none; }
        .pso-info-card { display:none; }
        .pso-main { height:auto; overflow-y:visible; flex:none; padding:28px 24px; min-height:100%; display:flex; flex-direction:column; }
        .pso-review-grid { grid-template-columns:1fr; }
        .pso-fields-grid { grid-template-columns:1fr; }
        .pso-two-col { grid-template-columns:1fr; }
    }
    @media(max-width:640px){
        .pso-main { padding:20px 16px; }
        .pso-heading { font-size:24px; }
        .pso-radio-cards { gap:8px; }
        .pso-radio-card-inner { padding:10px 12px; min-width:64px; }
        .pso-payment-options { flex-direction:column; }
        .pso-total-amount { font-size:28px; }
        .pso-upload-header-layout { flex-direction:column; align-items:flex-start; gap:12px; padding-bottom:16px; margin-bottom:24px; }
        .pso-accepted-files { justify-content:flex-start; gap:6px; margin-top:0; }
        .pso-accepted-label { font-size:11px; }
        .pso-file-badge { width:28px; height:36px; border-radius:3px 8px 3px 3px; }
        .pso-file-badge-fold { width:8px; height:8px; border-bottom-left-radius:3px; }
        .pso-file-badge-band { height:12px; }
        .pso-file-badge-band span { font-size:8px; letter-spacing:0; }
        .pso-accepted-max { font-size:12px; margin-left:4px; }
    }
    </style>

    <script>
    (function(){
        const EMAILJS_SERVICE_ID  = '<?php echo esc_js(PS_EMAILJS_SERVICE_ID); ?>';
        const EMAILJS_TEMPLATE_ID = '<?php echo esc_js(PS_EMAILJS_TEMPLATE_ID); ?>';
        const ADMIN_EMAIL         = '<?php echo esc_js($admin_email); ?>';
        const SHOP_NAME           = '<?php echo esc_js($shop_name); ?>';
        const SHOP_ADDRESS        = '<?php echo esc_js($shop_address); ?>';
        const SHOP_HOURS          = '<?php echo esc_js($shop_hours); ?>';

        function psSendEmail(params) {
            return emailjs.send(EMAILJS_SERVICE_ID, EMAILJS_TEMPLATE_ID, params)
                .catch(err => console.warn('EmailJS error:', err));
        }

        function psSendConfirmation(orderData) {
            if (!orderData.customer_email) return;
            return psSendEmail({
                to_email             : orderData.customer_email,
                to_name              : orderData.customer_name,
                reply_to             : orderData.customer_email,
                order_id             : orderData.rand_id,
                file_name            : orderData.file_name,
                paper_size           : orderData.paper_size,
                color_mode           : orderData.color_mode === 'color' ? 'Full Color' : 'Black & White',
                copies               : orderData.copies,
                sides                : orderData.sides,
                orientation          : orderData.orientation,
                binding              : orderData.binding,
                total_pages          : orderData.total_pages,
                additional_services  : orderData.additional_services || 'None',
                total_price          : '₱' + orderData.total_price,
                payment_method       : orderData.payment_method === 'gcash' ? 'GCash' : 'Cash',
                shop_name            : SHOP_NAME,
                shop_address         : SHOP_ADDRESS,
                shop_hours           : SHOP_HOURS,
                message              : 'Your print order has been received! We will notify you when it is ready for pickup.',
            });
        }

        function psSendAdminAlert(orderData) {
            if (!ADMIN_EMAIL) return;
            return psSendEmail({
                to_email             : ADMIN_EMAIL,
                to_name              : 'Admin',
                reply_to             : orderData.customer_email || ADMIN_EMAIL,
                order_id             : orderData.rand_id,
                file_name            : orderData.file_name,
                paper_size           : orderData.paper_size,
                color_mode           : orderData.color_mode === 'color' ? 'Full Color' : 'Black & White',
                copies               : orderData.copies,
                sides                : orderData.sides,
                orientation          : orderData.orientation,
                binding              : orderData.binding,
                total_pages          : orderData.total_pages,
                additional_services  : orderData.additional_services || 'None',
                total_price          : '₱' + orderData.total_price,
                payment_method       : orderData.payment_method === 'gcash' ? 'GCash' : 'Cash',
                shop_name            : SHOP_NAME,
                message              : 'New print order received from ' + orderData.customer_name + ' (' + orderData.customer_email + ').',
            });
        }

        let uploadedFile    = null;
        let calculatedPrice = null;

        const uploadNonce    = '<?php echo esc_js($upload_nonce); ?>';
        const orderNonce     = '<?php echo esc_js($order_nonce); ?>';
        const calculateNonce = '<?php echo esc_js($calculate_nonce); ?>';

        function goToStep(n) {
            document.querySelectorAll('.pso-panel').forEach(p => p.style.display = 'none');
            const panel = document.getElementById('pso-panel-' + n);
            if (panel) panel.style.display = 'flex';
            document.querySelectorAll('.pso-step-item').forEach((el, i) => {
                el.classList.toggle('active', i + 1 === n);
                el.classList.toggle('done',   i + 1 < n);
            });
        }

        const dropZone  = document.getElementById('pso-drop-zone');
        const fileInput = document.getElementById('pso-file-input');
        const nextBtn1  = document.getElementById('pso-next-1');

        dropZone.addEventListener('click', function(e) {
            if (e.target.closest('button')) return;
            if (!dropZone.classList.contains('has-file') &&
                document.getElementById('pso-upload-progress').style.display === 'none') {
                fileInput.click();
            }
        });

        document.getElementById('pso-browse-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            fileInput.click();
        });

        document.getElementById('pso-reupload-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            psoResetUpload();
            fileInput.click();
        });

        dropZone.addEventListener('dragover',  e => { e.preventDefault(); if (!dropZone.classList.contains('has-file')) dropZone.classList.add('dragging'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragging'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragging');
            if (!dropZone.classList.contains('has-file') && e.dataTransfer.files[0]) {
                uploadFile(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files[0]) uploadFile(this.files[0]);
            this.value = '';
        });

        function uploadFile(file) {
            const maxBytes = <?php echo $max_mb; ?> * 1024 * 1024;
            const errEl    = document.getElementById('pso-upload-error');
            errEl.style.display = 'none';
            if (file.size > maxBytes) { errEl.style.display = 'block'; errEl.textContent = 'File too large. Maximum allowed size is <?php echo $max_mb; ?>MB.'; return; }

            document.getElementById('pso-upload-idle').style.display     = 'none';
            document.getElementById('pso-upload-progress').style.display = 'flex';
            document.getElementById('pso-upload-success').style.display  = 'none';
            document.getElementById('pso-progress-filename').textContent  = file.name;
            nextBtn1.disabled = true;

            const fd = new FormData();
            fd.append('action', 'ps_upload_file');
            fd.append('nonce', uploadNonce);
            fd.append('file', file);

            const xhr = new XMLHttpRequest();
            xhr.upload.onprogress = e => {
                if (e.lengthComputable) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    document.getElementById('pso-progress-fill').style.width = pct + '%';
                    document.getElementById('pso-progress-pct').textContent  = pct + '%';
                }
            };
            xhr.onload = () => {
                const json = JSON.parse(xhr.responseText);
                document.getElementById('pso-upload-progress').style.display = 'none';
                if (json.success) {
                    uploadedFile = json.data;
                    document.getElementById('pso-success-filename').textContent = json.data.file_name;
                    document.getElementById('pso-success-meta').textContent = psoFormatBytes(json.data.file_size) + (json.data.page_count ? '  ·  ' + json.data.page_count + ' pages detected' : '');
                    document.getElementById('pso-upload-success').style.display = 'flex';
                    dropZone.classList.add('has-file');
                    dropZone.classList.remove('idle');
                    nextBtn1.disabled = false;
                } else {
                    document.getElementById('pso-upload-idle').style.display = 'block';
                    dropZone.classList.add('idle');
                    errEl.style.display = 'block';
                    errEl.textContent   = json.data.message || 'Upload failed. Please try again.';
                }
            };
            xhr.onerror = () => {
                document.getElementById('pso-upload-progress').style.display = 'none';
                document.getElementById('pso-upload-idle').style.display = 'block';
                dropZone.classList.add('idle');
                errEl.style.display = 'block';
                errEl.textContent   = 'Upload failed. Check your connection and try again.';
            };
            xhr.open('POST', ajaxurl);
            xhr.send(fd);
        }

        dropZone.classList.add('idle');

        window.psoResetUpload = function() {
            uploadedFile = null;
            dropZone.classList.remove('has-file');
            dropZone.classList.add('idle');
            document.getElementById('pso-upload-idle').style.display    = 'block';
            document.getElementById('pso-upload-success').style.display = 'none';
            document.getElementById('pso-upload-error').style.display   = 'none';
            nextBtn1.disabled = true;
        };

        nextBtn1.addEventListener('click', () => { goToStep(2); recalculate(); updatePreview(); });

        function initRadioCards(sel) {
            document.querySelectorAll(sel + ' .pso-radio-card').forEach(card => {
                card.addEventListener('click', () => {
                    document.querySelectorAll(sel + ' .pso-radio-card').forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                    card.querySelector('input').checked = true;
                    recalculate();
                    updatePreview();
                });
            });
        }
        initRadioCards('#opt-paper-size');
        initRadioCards('#opt-color-mode');
        initRadioCards('#opt-orientation');
        initRadioCards('#opt-sides');

        document.querySelectorAll('.pso-payment-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.pso-payment-card').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                card.querySelector('input').checked = true;
                const gcashPanel   = document.getElementById('pso-gcash-panel');
                const gcashConfirm = document.getElementById('pso-gcash-confirm');
                const gcashErr     = document.getElementById('pso-gcash-confirm-error');
                const isGcash = card.querySelector('input').value === 'gcash';
                if (gcashPanel) gcashPanel.style.display = isGcash ? 'block' : 'none';
                if (!isGcash && gcashConfirm) {
                    gcashConfirm.checked = false;
                    if (gcashErr) gcashErr.style.display = 'none';
                    const wrap = document.getElementById('pso-gcash-confirm-wrap');
                    if (wrap) wrap.style.borderColor = '#86efac';
                }
            });
        });

        const qtyInput = document.getElementById('opt-copies');
        document.getElementById('pso-qty-minus').addEventListener('click', () => {
            if (parseInt(qtyInput.value) > 1) { qtyInput.value = parseInt(qtyInput.value) - 1; recalculate(); updatePreview(); }
        });
        document.getElementById('pso-qty-plus').addEventListener('click', () => {
            if (parseInt(qtyInput.value) < 999) { qtyInput.value = parseInt(qtyInput.value) + 1; recalculate(); updatePreview(); }
        });
        document.getElementById('opt-binding').addEventListener('change', () => { recalculate(); updatePreview(); });

        // Recalculate when extra services are toggled
        document.querySelectorAll('.pso-extra-svc').forEach(chk => {
            chk.addEventListener('change', () => recalculate());
        });

        function getSelected(name) { const el = document.querySelector('input[name="' + name + '"]:checked'); return el ? el.value : ''; }

        function getExtraCost() {
            let extra = 0;
            document.querySelectorAll('.pso-extra-svc:checked').forEach(chk => {
                const price   = parseFloat(chk.dataset.price) || 0;
                const perCopy = chk.dataset.perCopy === '1';
                extra += perCopy ? price * (parseInt(qtyInput.value) || 1) : price;
            });
            return extra;
        }

        function getSelectedExtrasLabel() {
            const names = [];
            document.querySelectorAll('.pso-extra-svc:checked').forEach(chk => names.push(chk.dataset.name));
            return names.length ? names.join(', ') : 'None';
        }

        function recalculate() {
            if (!uploadedFile) return;
            const fd = new FormData();
            fd.append('action', 'ps_calculate_price'); fd.append('nonce', calculateNonce);
            fd.append('page_count', uploadedFile.page_count || 1); fd.append('copies', qtyInput.value);
            fd.append('paper_size', getSelected('paper_size')); fd.append('color_mode', getSelected('color_mode'));
            fd.append('sides', getSelected('sides')); fd.append('binding', document.getElementById('opt-binding').value);
            fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                if (!json.success) return;
                const extraCost  = getExtraCost();
                const grandTotal = parseFloat(json.data.total_price) + extraCost;
                calculatedPrice  = { ...json.data, extra_cost: extraCost.toFixed(2), grand_total: grandTotal.toFixed(2) };

                document.getElementById('pv-pages').textContent = json.data.total_pages + ' pages';
                document.getElementById('pv-print').textContent = '₱' + json.data.print_cost;

                if (parseFloat(json.data.binding_cost) > 0) {
                    document.getElementById('pv-binding-row').style.display = 'flex';
                    document.getElementById('pv-binding').textContent = '₱' + json.data.binding_cost;
                } else { document.getElementById('pv-binding-row').style.display = 'none'; }

                if (extraCost > 0) {
                    document.getElementById('pv-extras-row').style.display = 'flex';
                    document.getElementById('pv-extras').textContent = '₱' + extraCost.toFixed(2);
                } else { document.getElementById('pv-extras-row').style.display = 'none'; }

                document.getElementById('pv-total').textContent = '₱' + grandTotal.toFixed(2);
            });
        }

        // ── Live Print Preview ──────────────────────────────────
        const PAPER_DIMS = {
            'A4':    { w: 210, h: 297, label: '210×297mm' },
            'A3':    { w: 297, h: 420, label: '297×420mm' },
            'Short': { w: 216, h: 279, label: '216×279mm' },
            'Long':  { w: 216, h: 330, label: '216×330mm' },
        };
        const PREVIEW_BASE_H = 184;

        function updatePreview() {
            const paper       = getSelected('paper_size') || 'A4';
            const colorMode   = getSelected('color_mode') || 'bw';
            const orientation = getSelected('orientation') || 'portrait';
            const sides       = getSelected('sides') || 'single';
            const binding     = document.getElementById('opt-binding').value;
            const copies      = parseInt(qtyInput.value) || 1;

            const dims        = PAPER_DIMS[paper] || PAPER_DIMS['A4'];
            const isLandscape = orientation === 'landscape';
            const isColor     = colorMode === 'color';
            const isDouble    = sides === 'double';

            const mmW   = isLandscape ? dims.h : dims.w;
            const mmH   = isLandscape ? dims.w : dims.h;
            const ratio = mmW / mmH;
            const pageH = PREVIEW_BASE_H;
            const pageW = Math.round(pageH * ratio);

            const page      = document.getElementById('pso-preview-page');
            const inner     = document.getElementById('pso-page-inner');
            const bindingEl = document.getElementById('pso-binding-indicator');
            const swatches  = document.getElementById('pso-color-swatches');
            const stack     = document.getElementById('pso-copies-stack');
            const badge     = document.getElementById('pso-preview-badge');

            page.style.width  = pageW + 'px';
            page.style.height = pageH + 'px';
            page.classList.toggle('double-sided', isDouble);

            if (isColor) {
                inner.classList.add('color-mode');
                inner.classList.remove('bw-mode');
                swatches.style.display = 'block';
            } else {
                inner.classList.add('bw-mode');
                inner.classList.remove('color-mode');
                swatches.style.display = 'none';
            }

            if (binding === 'none') {
                bindingEl.style.display = 'none';
            } else {
                bindingEl.style.display = 'block';
                bindingEl.className = 'pso-binding-indicator ' + binding;
            }

            stack.innerHTML = '';
            const visibleGhosts = Math.min(copies - 1, 4);
            for (let i = 0; i < visibleGhosts; i++) {
                const ghost = document.createElement('div');
                ghost.className = 'pso-copy-ghost';
                const offset = (i + 1) * 3;
                ghost.style.top    = offset + 'px';
                ghost.style.left   = offset + 'px';
                ghost.style.right  = (-offset) + 'px';
                ghost.style.bottom = (-offset) + 'px';
                ghost.style.zIndex = -(i + 1);
                stack.appendChild(ghost);
            }

            const mmLabel = isLandscape ? dims.label.split('×').reverse().join('×') : dims.label;
            document.getElementById('spec-size').textContent   = paper + ' · ' + mmLabel;
            document.getElementById('spec-color').textContent  = isColor ? 'Full Color' : 'Black & White';
            document.getElementById('spec-sides').textContent  = isDouble ? 'Double-sided' : 'Single-sided';
            document.getElementById('spec-copies').textContent = copies + (copies === 1 ? ' copy' : ' copies');
            badge.textContent = paper + ' · ' + (isColor ? 'Color' : 'B&W') + ' · ' + (isLandscape ? 'Landscape' : 'Portrait');
        }

        updatePreview();

        document.getElementById('pso-back-2').addEventListener('click', () => goToStep(1));
        document.getElementById('pso-next-2').addEventListener('click', () => goToStep(3));
        document.getElementById('pso-back-3').addEventListener('click', () => goToStep(2));
        document.getElementById('pso-next-3').addEventListener('click', () => {
            const name = document.getElementById('cust-name').value.trim();
            if (!name) { document.getElementById('cust-name').focus(); alert('Please enter your full name.'); return; }
            const paymentVal = getSelected('payment_method');
            if (paymentVal === 'gcash') {
                const confirmed = document.getElementById('pso-gcash-confirm');
                const errEl     = document.getElementById('pso-gcash-confirm-error');
                if (confirmed && !confirmed.checked) {
                    errEl.style.display = 'block';
                    confirmed.closest('label').style.borderColor = '#dc2626';
                    confirmed.closest('label').scrollIntoView({behavior:'smooth', block:'center'});
                    return;
                }
                if (errEl) errEl.style.display = 'none';
            }
            buildReview(); goToStep(4);
        });

        const paymentLabels = {cash:'Cash', gcash:'GCash'};
        const bindingLabels = {none:'None', staple:'Staple', spiral:'Spiral Binding', hardcover:'Hard Cover'};

        function buildReview() {
            document.getElementById('rv-file').textContent        = uploadedFile.file_name;
            document.getElementById('rv-pages').textContent       = uploadedFile.page_count ? uploadedFile.page_count + ' pages' : 'N/A';
            document.getElementById('rv-paper').textContent       = getSelected('paper_size');
            document.getElementById('rv-color').textContent       = getSelected('color_mode') === 'color' ? 'Full Color' : 'Black & White';
            document.getElementById('rv-orientation').textContent = getSelected('orientation').charAt(0).toUpperCase() + getSelected('orientation').slice(1);
            document.getElementById('rv-sides').textContent       = getSelected('sides').charAt(0).toUpperCase() + getSelected('sides').slice(1) + '-sided';
            document.getElementById('rv-copies').textContent      = qtyInput.value + ' copy/ies';
            document.getElementById('rv-binding').textContent     = bindingLabels[document.getElementById('opt-binding').value] || 'None';
            document.getElementById('rv-name').textContent        = document.getElementById('cust-name').value;
            document.getElementById('rv-email').textContent       = document.getElementById('cust-email').value;
            document.getElementById('rv-phone').textContent       = document.getElementById('cust-phone').value || '—';
            document.getElementById('rv-payment').textContent     = paymentLabels[getSelected('payment_method')] || '—';

            const extrasLabel = getSelectedExtrasLabel();
            const extrasRow   = document.getElementById('rv-extras-row');
            if (extrasLabel !== 'None') {
                extrasRow.style.display = 'flex';
                document.getElementById('rv-extras').textContent = extrasLabel;
            } else {
                extrasRow.style.display = 'none';
            }

            const total = calculatedPrice ? calculatedPrice.grand_total : '0.00';
            document.getElementById('rv-total').textContent      = '₱' + total;
            document.getElementById('rv-total-note').textContent = calculatedPrice ? calculatedPrice.total_pages + ' pages × ₱' + calculatedPrice.unit_price + '/page' : '';
        }

        document.getElementById('pso-back-4').addEventListener('click', () => goToStep(3));

        document.getElementById('pso-submit-order').addEventListener('click', function() {
            const errEl = document.getElementById('pso-submit-error');
            errEl.style.display = 'none';
            const fd = new FormData();
            fd.append('action', 'ps_submit_order'); fd.append('nonce', orderNonce);
            fd.append('file_rand_id', uploadedFile.rand_id); fd.append('file_name', uploadedFile.file_name);
            fd.append('file_size', uploadedFile.file_size); fd.append('file_path', uploadedFile.file_path);
            fd.append('page_count', uploadedFile.page_count || 0); fd.append('copies', qtyInput.value);
            fd.append('paper_size', getSelected('paper_size')); fd.append('color_mode', getSelected('color_mode'));
            fd.append('orientation', getSelected('orientation')); fd.append('sides', getSelected('sides'));
            fd.append('binding', document.getElementById('opt-binding').value);
            fd.append('notes', document.getElementById('opt-notes').value);
            fd.append('customer_name', document.getElementById('cust-name').value);
            fd.append('customer_email', document.getElementById('cust-email').value);
            fd.append('customer_phone', document.getElementById('cust-phone').value);
            fd.append('payment_method', getSelected('payment_method'));
            if (calculatedPrice) {
                fd.append('total_pages', calculatedPrice.total_pages);
                fd.append('unit_price', calculatedPrice.unit_price);
                fd.append('total_price', calculatedPrice.grand_total);
                fd.append('extra_services_selected', getSelectedExtrasLabel());
            }

            this.disabled  = true;
            this.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="animation:spin .8s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Submitting…';

            fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                if (json.success) {
                    const orderData = {
                        rand_id             : json.data.rand_id,
                        customer_name       : document.getElementById('cust-name').value,
                        customer_email      : document.getElementById('cust-email').value,
                        file_name           : uploadedFile.file_name,
                        paper_size          : getSelected('paper_size'),
                        color_mode          : getSelected('color_mode'),
                        copies              : qtyInput.value,
                        sides               : getSelected('sides'),
                        orientation         : getSelected('orientation'),
                        binding             : bindingLabels[document.getElementById('opt-binding').value] || 'None',
                        total_pages         : calculatedPrice ? calculatedPrice.total_pages : '—',
                        additional_services : getSelectedExtrasLabel(),
                        total_price         : calculatedPrice ? calculatedPrice.grand_total : '0.00',
                        payment_method      : getSelected('payment_method'),
                    };

                    psSendConfirmation(orderData);
                    psSendAdminAlert(orderData);

                    document.getElementById('pso-order-id-display').textContent = json.data.rand_id;
                    const emailVal = document.getElementById('cust-email').value;
                    document.getElementById('pso-success-msg').textContent = emailVal
                        ? 'A confirmation email has been sent to ' + emailVal + '.'
                        : 'Your order has been submitted. Please check back for updates.';
                    goToStep('success');
                } else {
                    errEl.style.display = 'block';
                    errEl.textContent   = json.data.message || 'Submission failed. Please try again.';
                    this.disabled       = false;
                    this.innerHTML      = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Submit Order';
                }
            });
        });

        document.getElementById('pso-copy-id').addEventListener('click', function() {
            const val = document.getElementById('pso-order-id-display').textContent;
            navigator.clipboard.writeText(val).then(() => { this.style.color = '#16a34a'; setTimeout(() => this.style.color = '', 1500); });
        });

        document.getElementById('pso-new-order').addEventListener('click', () => location.reload());

        const spinStyle = document.createElement('style');
        spinStyle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
        document.head.appendChild(spinStyle);

        function psoFormatBytes(b) {
            if (!b) return ''; if (b < 1024) return b + ' B';
            if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
            return (b / 1048576).toFixed(1) + ' MB';
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────
// 8. FRONTEND: ORDER TRACKING SHORTCODE
// ─────────────────────────────────────────────────────────────

function bntm_shortcode_ps_tracking() {
    $nonce = wp_create_nonce('ps_track_nonce');
    ob_start();
    ?>
    <!-- EmailJS SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script>
    (function(){
        emailjs.init('<?php echo esc_js(PS_EMAILJS_PUBLIC_KEY); ?>');
    })();
    </script>
    <script>var ajaxurl = '<?php echo esc_js( set_url_scheme( admin_url( 'admin-ajax.php' ), is_ssl() ? 'https' : 'http' ) ); ?>';</script>

    <div class="ps-track-wrapper">
        <h2 style="font-size:24px;font-weight:700;margin-bottom:8px;color:#1a3c8f;">Track Your Order</h2>
        <p style="color:#6b7280;margin-bottom:24px;">Enter your Order ID to check the status of your print job.</p>
        <div class="ps-track-form">
            <input type="text" id="ps-track-input" class="bntm-input" placeholder="e.g. PS-XXXXXXX">
            <button id="ps-track-btn" class="bntm-btn-primary" style="background:linear-gradient(135deg,#16a34a,#22c55e);border-color:#16a34a;" data-nonce="<?php echo $nonce; ?>">Track</button>
        </div>
        <div id="ps-track-result"></div>
    </div>

    <style>
    .ps-track-wrapper { max-width:560px; margin:0 auto; padding:20px; font-family:'Segoe UI',system-ui,sans-serif; box-sizing:border-box; }
    .ps-track-form { display:flex; gap:10px; margin-bottom:24px; }
    .ps-track-form .bntm-input { flex:1; min-width:0; }
    .ps-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; text-transform:capitalize; letter-spacing:.3px; }
    .ps-badge-pending   { background:#fef3c7; color:#92400e; }
    .ps-badge-printing  { background:#e8eeff; color:#1a3c8f; }
    .ps-badge-ready     { background:#dcfce7; color:#15803d; }
    .ps-badge-picked_up { background:#e5e7eb; color:#374151; }
    .ps-badge-cancelled { background:#fee2e2; color:#991b1b; }
    .ps-track-details-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    @media(max-width:640px) {
        .ps-track-wrapper { padding:15px; }
        .ps-track-form { flex-direction:column; }
        .ps-track-form #ps-track-btn { width: 100%; }
        .ps-track-details-grid { grid-template-columns:1fr; }
        .ps-track-order-header { flex-direction:column; align-items:flex-start !important; gap:12px; }
    }
    </style>

    <script>
    (function(){
        document.getElementById('ps-track-btn').addEventListener('click', function() {
            const orderId  = document.getElementById('ps-track-input').value.trim();
            const resultEl = document.getElementById('ps-track-result');
            if (!orderId) { resultEl.innerHTML = '<div class="bntm-notice bntm-notice-error">Please enter an Order ID.</div>'; return; }

            this.disabled = true; this.textContent = 'Searching...';
            const fd = new FormData();
            fd.append('action', 'ps_get_order_status');
            fd.append('rand_id', orderId);
            fd.append('nonce', '<?php echo esc_js($nonce); ?>');

            fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                this.disabled = false; this.textContent = 'Track';
                if (json.success) {
                    const o = json.data;
                    const steps = [
                        {key:'pending',   label:'Order Received',   icon:'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0120 9.414V19a2 2 0 01-2 2z'},
                        {key:'printing',  label:'Being Printed',    icon:'M17 17H17.01M17 20H5a2 2 0 01-2-2V9a2 2 0 012-2h2V5a2 2 0 012-2h6a2 2 0 012 2v2h2a2 2 0 012 2v7a2 2 0 01-2 2z'},
                        {key:'ready',     label:'Ready for Pickup', icon:'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'},
                        {key:'picked_up', label:'Picked Up',        icon:'M5 13l4 4L19 7'},
                    ];
                    const statusOrder = ['pending','printing','ready','picked_up'];
                    const currentIdx  = statusOrder.indexOf(o.status);

                    let stepsHtml = '<div style="display:flex;flex-direction:column;">';
                    steps.forEach((step, i) => {
                        const done = i <= currentIdx, active = i === currentIdx;
                        const lineColor = i < currentIdx ? '#22c55e' : '#e5e7eb';
                        stepsHtml += `<div style="display:flex;align-items:flex-start;gap:16px;">
                            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                                <div style="width:40px;height:40px;border-radius:50%;background:${active?'#1a3c8f':done?'#22c55e':'#e5e7eb'};display:flex;align-items:center;justify-content:center;">
                                    <svg width="20" height="20" fill="none" stroke="${done||active?'#fff':'#9ca3af'}" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${step.icon}"/></svg>
                                </div>
                                ${i < steps.length-1 ? `<div style="width:2px;height:40px;background:${lineColor};"></div>` : ''}
                            </div>
                            <div style="padding-top:10px;"><div style="font-weight:${active?'700':'500'};font-size:14px;color:${active?'#1a3c8f':done?'#15803d':'#9ca3af'};">${step.label}</div></div>
                        </div>`;
                    });
                    stepsHtml += '</div>';

                    resultEl.innerHTML = `<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;">
                        <div class="ps-track-order-header" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
                            <div><div style="font-size:12px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Order ID</div><div style="font-size:20px;font-weight:800;color:#1a3c8f;word-break:break-all;">${o.rand_id}</div></div>
                            <span class="ps-badge ps-badge-${o.status}" style="flex-shrink:0;">${o.status.replace('_',' ')}</span>
                        </div>
                        <div class="ps-track-details-grid" style="margin-bottom:24px;font-size:13px;">
                            <div><span style="color:#9ca3af;">File: </span><span style="color:#374151;word-break:break-all;">${o.file_name}</span></div>
                            <div><span style="color:#9ca3af;">Customer: </span><span style="color:#374151;">${o.customer_name}</span></div>
                            <div><span style="color:#9ca3af;">Copies: </span><span style="color:#374151;">${o.copies}</span></div>
                            <div><span style="color:#9ca3af;">Paper: </span><span style="color:#374151;">${o.paper_size} ${o.color_mode==='color'?'Color':'B&W'}</span></div>
                            <div><span style="color:#9ca3af;">Total: </span><span style="font-weight:700;color:#1a3c8f;">₱${parseFloat(o.total_price).toFixed(2)}</span></div>
                            <div><span style="color:#9ca3af;">Payment: </span><span style="color:#374151;">${o.payment_status}</span></div>
                        </div>
                        ${stepsHtml}
                        ${o.status==='ready'?'<div style="margin-top:20px;padding:12px 16px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;color:#15803d;font-weight:600;font-size:14px;">✓ Your order is ready! Please visit the shop to pick it up.</div>':''}
                    </div>`;
                } else {
                    resultEl.innerHTML = '<div class="bntm-notice bntm-notice-error">' + json.data.message + '</div>';
                }
            });
        });

        document.getElementById('ps-track-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') document.getElementById('ps-track-btn').click();
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────
// 9. AJAX HANDLERS
// ─────────────────────────────────────────────────────────────

function bntm_ajax_ps_upload_file() {
    check_ajax_referer('ps_upload_nonce', 'nonce');
    if (empty($_FILES['file'])) wp_send_json_error(['message' => 'No file received.']);

    $file   = $_FILES['file'];
    $s       = ps_get_all_settings();
    $max_mb  = (int)($s['max_file_mb'] ?? 20);
    if ($file['size'] > $max_mb * 1024 * 1024) wp_send_json_error(['message' => "File exceeds maximum size of {$max_mb}MB."]);

    $allowed_raw   = $s['allowed_file_types'] ?? 'pdf,doc,docx,ppt,pptx,jpg,png';
    $allowed_types = array_map('trim', explode(',', strtolower($allowed_raw)));
    $ext           = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) wp_send_json_error(['message' => "File type '.{$ext}' is not allowed."]);

    $upload_dir  = wp_upload_dir();
    $target_dir  = $upload_dir['basedir'] . '/ps-orders/' . date('Y/m/');
    if (!file_exists($target_dir)) wp_mkdir_p($target_dir);

    $safe_name   = sanitize_file_name($file['name']);
    $unique_name = uniqid('ps_') . '_' . $safe_name;
    $target_path = $target_dir . $unique_name;
    if (!move_uploaded_file($file['tmp_name'], $target_path)) wp_send_json_error(['message' => 'Failed to save file. Please try again.']);

    $page_count = 0;
    if ($ext === 'pdf') $page_count = ps_count_pdf_pages($target_path);

    $rand_id = 'PS-' . strtoupper(substr(md5(uniqid()), 0, 8));
    wp_send_json_success([
        'rand_id'    => $rand_id,
        'file_name'  => $safe_name,
        'file_size'  => $file['size'],
        'file_path'  => $target_path,
        'file_url'   => ps_get_file_url($target_path),
        'page_count' => $page_count,
    ]);
}

function bntm_ajax_ps_calculate_price() {
    check_ajax_referer('ps_calc_nonce', 'nonce');

    $page_count = max(1, intval($_POST['page_count'] ?? 1));
    $copies     = max(1, intval($_POST['copies'] ?? 1));
    $paper_size = sanitize_text_field($_POST['paper_size'] ?? 'A4');
    $color_mode = sanitize_text_field($_POST['color_mode'] ?? 'bw');
    $sides      = sanitize_text_field($_POST['sides'] ?? 'single');
    $binding    = sanitize_text_field($_POST['binding'] ?? 'none');

    // One call loads all settings from cache (single DB query for the whole request)
    $s = ps_get_all_settings();
    $prices = [
        'bw'    => ['A4' => floatval($s['bw_a4'] ?? 2),    'A3' => floatval($s['bw_a3'] ?? 4),    'Short' => floatval($s['bw_letter'] ?? 2),    'Long' => floatval($s['bw_long'] ?? 2)],
        'color' => ['A4' => floatval($s['color_a4'] ?? 8), 'A3' => floatval($s['color_a3'] ?? 14), 'Short' => floatval($s['color_letter'] ?? 8), 'Long' => floatval($s['color_long'] ?? 8)],
    ];
    $unit_price   = $prices[$color_mode][$paper_size] ?? $prices['bw']['A4'];
    $total_pages  = $page_count * $copies;
    $print_cost   = round($unit_price * $total_pages, 2);
    $binding_map  = ['spiral' => floatval($s['binding_spiral'] ?? 35), 'staple' => floatval($s['binding_staple'] ?? 5), 'hardcover' => floatval($s['binding_hardcover'] ?? 80), 'none' => 0];
    $binding_cost = ($binding_map[$binding] ?? 0) * $copies;
    $total_price  = $print_cost + $binding_cost;

    wp_send_json_success([
        'total_pages'  => $total_pages,
        'unit_price'   => number_format($unit_price, 2),
        'print_cost'   => number_format($print_cost, 2),
        'binding_cost' => number_format($binding_cost, 2),
        'total_price'  => number_format($total_price, 2),
    ]);
}

function bntm_ajax_ps_submit_order() {
    check_ajax_referer('ps_order_nonce', 'nonce');
    global $wpdb;
    $t = $wpdb->prefix . 'ps_orders';

    $customer_name  = sanitize_text_field($_POST['customer_name'] ?? '');
    $customer_email = sanitize_email($_POST['customer_email'] ?? '');
    $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
    $file_name      = sanitize_text_field($_POST['file_name'] ?? '');
    $file_path      = sanitize_text_field($_POST['file_path'] ?? '');
    $file_size      = intval($_POST['file_size'] ?? 0);
    $page_count     = intval($_POST['page_count'] ?? 0);
    $copies         = max(1, intval($_POST['copies'] ?? 1));
    $paper_size     = sanitize_text_field($_POST['paper_size'] ?? 'A4');
    $color_mode     = sanitize_text_field($_POST['color_mode'] ?? 'bw');
    $orientation    = sanitize_text_field($_POST['orientation'] ?? 'portrait');
    $sides          = sanitize_text_field($_POST['sides'] ?? 'single');
    $binding        = sanitize_text_field($_POST['binding'] ?? 'none');
    $notes          = sanitize_textarea_field($_POST['notes'] ?? '');
    $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
    $extra_selected = sanitize_text_field($_POST['extra_services_selected'] ?? '');
    $client_total   = floatval($_POST['total_price'] ?? 0);

    if (!$customer_name || !$file_name) wp_send_json_error(['message' => 'Missing required fields.']);

    // Recalculate server-side to be safe — one cache load, zero extra DB queries
    $s = ps_get_all_settings();
    $prices = [
        'bw'    => ['A4' => floatval($s['bw_a4'] ?? 2),    'A3' => floatval($s['bw_a3'] ?? 4),    'Short' => floatval($s['bw_letter'] ?? 2),    'Long' => floatval($s['bw_long'] ?? 2)],
        'color' => ['A4' => floatval($s['color_a4'] ?? 8), 'A3' => floatval($s['color_a3'] ?? 14), 'Short' => floatval($s['color_letter'] ?? 8), 'Long' => floatval($s['color_long'] ?? 8)],
    ];
    $real_unit        = $prices[$color_mode][$paper_size] ?? $prices['bw']['A4'];
    $real_total_pages = max(1, $page_count) * $copies;
    $binding_map      = ['spiral' => floatval($s['binding_spiral'] ?? 35), 'staple' => floatval($s['binding_staple'] ?? 5), 'hardcover' => floatval($s['binding_hardcover'] ?? 80), 'none' => 0];
    $real_binding     = ($binding_map[$binding] ?? 0) * $copies;
    $real_total       = round($real_unit * $real_total_pages + $real_binding, 2);

    // Use client total if provided (includes extra services), otherwise use server-computed
    $final_total = $client_total > 0 ? $client_total : $real_total;

    // Append extra services to notes if any
    if ($extra_selected && $extra_selected !== 'None') {
        $notes = $notes ? $notes . "\nExtra services: " . $extra_selected : "Extra services: " . $extra_selected;
    }

    $rand_id     = 'PS-' . strtoupper(substr(md5(uniqid() . $customer_email), 0, 8));
    $business_id = is_user_logged_in() ? get_current_user_id() : 0;

    $result = $wpdb->insert($t, [
        'rand_id' => $rand_id, 'business_id' => $business_id,
        'customer_name' => $customer_name, 'customer_email' => $customer_email, 'customer_phone' => $customer_phone,
        'file_name' => $file_name, 'file_path' => $file_path, 'file_size' => $file_size, 'page_count' => $page_count,
        'copies' => $copies, 'paper_size' => $paper_size, 'color_mode' => $color_mode,
        'orientation' => $orientation, 'sides' => $sides, 'binding' => $binding, 'notes' => $notes,
        'total_pages' => $real_total_pages, 'unit_price' => $real_unit, 'total_price' => $final_total,
        'payment_method' => $payment_method, 'status' => 'pending',
        'payment_status' => 'unpaid',
        'created_at' => current_time('mysql'),
    ], ['%s','%d','%s','%s','%s','%s','%s','%d','%d','%d','%s','%s','%s','%s','%s','%s','%d','%f','%f','%s','%s','%s','%s']);

    if ($result) {
        wp_send_json_success(['rand_id' => $rand_id, 'total' => $final_total]);
    } else {
        wp_send_json_error(['message' => 'Failed to save order. Please try again.']);
    }
}

function bntm_ajax_ps_get_order_status() {
    check_ajax_referer('ps_track_nonce', 'nonce');
    global $wpdb;
    $t       = $wpdb->prefix . 'ps_orders';
    $rand_id = sanitize_text_field($_POST['rand_id'] ?? '');
    if (!$rand_id) wp_send_json_error(['message' => 'Please enter an Order ID.']);
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE rand_id=%s", $rand_id));
    if (!$order) wp_send_json_error(['message' => 'Order not found. Please check your Order ID.']);
    wp_send_json_success([
        'rand_id' => $order->rand_id, 'customer_name' => $order->customer_name,
        'customer_email' => $order->customer_email,
        'file_name' => $order->file_name, 'copies' => $order->copies,
        'paper_size' => $order->paper_size, 'color_mode' => $order->color_mode,
        'total_price' => $order->total_price, 'status' => $order->status,
        'payment_status' => $order->payment_status, 'created_at' => $order->created_at,
    ]);
}

function bntm_ajax_ps_get_orders() {
    check_ajax_referer('ps_admin_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $t        = $wpdb->prefix . 'ps_orders';
    $order_id = intval($_POST['order_id'] ?? 0);
    if (!$order_id) wp_send_json_error(['message' => 'Invalid order ID']);

    $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d", $order_id));
    if (!$o) wp_send_json_error(['message' => 'Order not found']);

    $color_label   = $o->color_mode === 'color' ? 'Full Color' : 'Black & White';
    $binding_label = $o->binding === 'none' ? 'None' : ucfirst($o->binding);
    $file_url = '';
    if ($o->file_path && file_exists($o->file_path)) {
        $file_url = ps_get_file_url($o->file_path);
    }

    ob_start();
    ?>
    <h2 style="margin:0 0 20px;font-size:20px;font-weight:700;">Order #<?php echo esc_html($o->rand_id); ?></h2>
    <div class="ps-options-grid" style="gap:16px;font-size:14px;margin-bottom:20px;">
        <div><div style="color:#9ca3af;font-size:12px;margin-bottom:2px;">Customer</div><div style="font-weight:600;"><?php echo esc_html($o->customer_name); ?></div></div>
        <div><div style="color:#9ca3af;font-size:12px;margin-bottom:2px;">Email</div><div><?php echo esc_html($o->customer_email); ?></div></div>
        <div><div style="color:#9ca3af;font-size:12px;margin-bottom:2px;">Phone</div><div><?php echo esc_html($o->customer_phone ?: '—'); ?></div></div>
        <div><div style="color:#9ca3af;font-size:12px;margin-bottom:2px;">Order Date</div><div><?php echo date('g:i A', strtotime($o->created_at)); ?><br><?php echo date('n/j/Y', strtotime($o->created_at)); ?></div></div>
    </div>
    <div style="background:#f9fafb;border-radius:10px;padding:16px;margin-bottom:20px;">
        <h4 style="margin:0 0 12px;font-size:14px;">File Details</h4>
        <div class="ps-options-grid" style="font-size:13px;gap:8px;">
            <div><span style="color:#9ca3af;">File name: </span><?php echo esc_html($o->file_name); ?></div>
            <div><span style="color:#9ca3af;">Size: </span><?php echo ps_format_filesize($o->file_size); ?></div>
            <div><span style="color:#9ca3af;">Doc pages: </span><?php echo $o->page_count ?: 'N/A'; ?></div>
            <?php if ($file_url): ?><div><a href="<?php echo esc_url($file_url); ?>" download="<?php echo esc_attr($o->file_name); ?>" target="_blank" style="color:#1a3c8f;">Download File</a></div><?php endif; ?>
        </div>
    </div>
    <div style="background:#f9fafb;border-radius:10px;padding:16px;margin-bottom:20px;">
        <h4 style="margin:0 0 12px;font-size:14px;">Print Configuration</h4>
        <div class="ps-options-grid" style="font-size:13px;gap:8px;">
            <div><span style="color:#9ca3af;">Paper: </span><?php echo esc_html($o->paper_size); ?></div>
            <div><span style="color:#9ca3af;">Color: </span><?php echo $color_label; ?></div>
            <div><span style="color:#9ca3af;">Orientation: </span><?php echo ucfirst($o->orientation); ?></div>
            <div><span style="color:#9ca3af;">Sides: </span><?php echo ucfirst($o->sides); ?>-sided</div>
            <div><span style="color:#9ca3af;">Copies: </span><?php echo $o->copies; ?></div>
            <div><span style="color:#9ca3af;">Binding: </span><?php echo $binding_label; ?></div>
            <div><span style="color:#9ca3af;">Total pages: </span><?php echo $o->total_pages; ?></div>
        </div>
        <?php if ($o->notes): ?><div style="margin-top:10px;font-size:13px;"><span style="color:#9ca3af;">Notes: </span><?php echo esc_html($o->notes); ?></div><?php endif; ?>
    </div>
    <div style="background:#f0fdf4;border-radius:10px;padding:16px;">
        <div style="display:flex;justify-content:space-between;font-size:14px;"><span style="color:#6b7280;">Unit Price</span><span>&#8369;<?php echo number_format($o->unit_price, 2); ?>/pg</span></div>
        <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:700;margin-top:8px;"><span>Total</span><span style="color:#16a34a;">&#8369;<?php echo number_format($o->total_price, 2); ?></span></div>
        <div style="font-size:12px;color:#6b7280;margin-top:4px;">Payment: <?php echo ucfirst($o->payment_method); ?> &bull; <?php echo ucfirst($o->payment_status); ?></div>
    </div>
    <?php if ($o->admin_notes): ?><div style="margin-top:16px;padding:12px;background:#fef3c7;border-radius:8px;font-size:13px;"><strong>Admin Notes:</strong> <?php echo esc_html($o->admin_notes); ?></div><?php endif; ?>
    <?php
    wp_send_json_success(['html' => ob_get_clean()]);
}

function bntm_ajax_ps_update_order_status() {
    check_ajax_referer('ps_admin_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $t        = $wpdb->prefix . 'ps_orders';
    $order_id = intval($_POST['order_id'] ?? 0);
    $status   = sanitize_text_field($_POST['status'] ?? '');
    $allowed  = ['pending','printing','ready','picked_up','cancelled'];
    if (!in_array($status, $allowed)) wp_send_json_error(['message' => 'Invalid status']);

    $update_data   = ['status' => $status];
    $update_format = ['%s'];

    if ($status === 'printing')  { $update_data['printed_at']   = current_time('mysql'); $update_format[] = '%s'; }
    if ($status === 'ready')     { $update_data['ready_at']     = current_time('mysql'); $update_format[] = '%s'; }
    if ($status === 'picked_up') { $update_data['picked_up_at'] = current_time('mysql'); $update_format[] = '%s'; }

    $result = $wpdb->update($t, $update_data, ['id' => $order_id], $update_format, ['%d']);

    if ($status === 'ready') {
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d", $order_id));
        if ($order && $result !== false) {
            // Extract additional services from notes if present
            $additional_services = 'None';
            if ($order->notes && strpos($order->notes, 'Extra services:') !== false) {
                preg_match('/Extra services: (.+)/i', $order->notes, $m);
                if (!empty($m[1])) $additional_services = trim($m[1]);
            }
            wp_send_json_success([
                'message'             => 'Status updated',
                'send_ready_email'    => true,
                'customer_email'      => $order->customer_email,
                'customer_name'       => $order->customer_name,
                'rand_id'             => $order->rand_id,
                'file_name'           => $order->file_name,
                'paper_size'          => $order->paper_size,
                'color_mode'          => $order->color_mode === 'color' ? 'Full Color' : 'Black & White',
                'copies'              => $order->copies,
                'sides'               => ucfirst($order->sides) . '-sided',
                'orientation'         => ucfirst($order->orientation),
                'binding'             => $order->binding === 'none' ? 'None' : ucfirst($order->binding),
                'total_pages'         => $order->total_pages,
                'additional_services' => $additional_services,
                'total_price'         => number_format($order->total_price, 2),
                'payment_method'      => ucfirst($order->payment_method),
            ]);
        }
    }

    if ($result !== false) wp_send_json_success(['message' => 'Status updated', 'send_ready_email' => false]);
    else wp_send_json_error(['message' => 'Update failed']);
}

function bntm_ajax_ps_mark_picked_up() {
    check_ajax_referer('ps_admin_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);
    global $wpdb;
    $t        = $wpdb->prefix . 'ps_orders';
    $order_id = intval($_POST['order_id'] ?? 0);
    $result   = $wpdb->update($t, ['status' => 'picked_up', 'picked_up_at' => current_time('mysql')], ['id' => $order_id], ['%s','%s'], ['%d']);
    if ($result !== false) wp_send_json_success(['message' => 'Order marked as picked up.']);
    else wp_send_json_error(['message' => 'Update failed.']);
}

function bntm_ajax_ps_mark_paid() {
    check_ajax_referer('ps_admin_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);
    global $wpdb;
    $t        = $wpdb->prefix . 'ps_orders';
    $order_id = intval($_POST['order_id'] ?? 0);
    if (!$order_id) wp_send_json_error(['message' => 'Invalid order ID.']);
    $result = $wpdb->update($t, ['payment_status' => 'paid'], ['id' => $order_id], ['%s'], ['%d']);
    if ($result !== false) wp_send_json_success(['message' => 'Order marked as paid.']);
    else wp_send_json_error(['message' => 'Update failed.']);
}

function bntm_ajax_ps_search_orders() {
    check_ajax_referer('ps_admin_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $t = $wpdb->prefix . 'ps_orders';
    $current_user = wp_get_current_user();
    $business_id = $current_user->ID;

    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $payment = isset($_POST['payment']) ? sanitize_text_field($_POST['payment']) : '';

    $where = "WHERE business_id=%d";
    $params = [$business_id];
    
    if ($status) {
        $where .= " AND status=%s";
        $params[] = $status;
    }
    if ($payment) {
        $where .= " AND payment_status=%s";
        $params[] = $payment;
    }
    if ($search) {
        $where .= " AND (customer_name LIKE %s OR customer_email LIKE %s OR rand_id LIKE %s OR file_name LIKE %s)";
        $like = '%' . $wpdb->esc_like($search) . '%';
        $params = array_merge($params, [$like, $like, $like, $like]);
    }

    $sql = "SELECT * FROM {$t} {$where} ORDER BY created_at DESC LIMIT 100";
    $orders = $wpdb->get_results($wpdb->prepare($sql, ...$params));

    if (empty($orders)) {
        wp_send_json_success(['html' => '<tr><td colspan="10" style="text-align:center;color:#9ca3af;padding:40px;">No orders found.</td></tr>']);
        return;
    }

    $statuses = ['pending', 'printing', 'ready', 'picked_up', 'cancelled'];
    $nonce = wp_create_nonce('ps_admin_nonce');
    $html = '';

    foreach ($orders as $o) {
        $html .= '<tr id="ps-order-row-' . $o->id . '">';
        $html .= '<td><strong>#' . esc_html($o->rand_id) . '</strong></td>';
        $html .= '<td>';
        $html .= '<div style="font-weight:600;font-size:13px;">' . esc_html($o->customer_name) . '</div>';
        $html .= '<div style="font-size:11px;color:#6b7280;">' . esc_html($o->customer_email) . '</div>';
        if ($o->customer_phone) $html .= '<div style="font-size:11px;color:#6b7280;">' . esc_html($o->customer_phone) . '</div>';
        $html .= '</td>';
        
        $html .= '<td style="max-width:150px;">';
        $html .= '<div style="font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' . esc_attr($o->file_name) . '">' . esc_html($o->file_name) . '</div>';
        $html .= '<div style="font-size:11px;color:#9ca3af;">' . ps_format_filesize($o->file_size) . '</div>';
        if ($o->file_path && file_exists($o->file_path)) {
            $html .= '<a href="' . esc_url(ps_get_file_url($o->file_path)) . '" download="' . esc_attr($o->file_name) . '" target="_blank" style="font-size:11px;color:#1a3c8f;">Download</a>';
        }
        $html .= '</td>';

        $html .= '<td style="font-size:12px;">';
        $html .= '<div>' . (int)$o->copies . ' copy/ies</div>';
        $html .= '<div>' . esc_html($o->paper_size) . ' &bull; ' . ($o->color_mode === 'color' ? 'Color' : 'B&amp;W') . '</div>';
        $html .= '<div>' . esc_html(ucfirst($o->orientation)) . ' &bull; ' . esc_html(ucfirst($o->sides)) . '-sided</div>';
        if ($o->binding !== 'none') $html .= '<div>Binding: ' . esc_html(ucfirst($o->binding)) . '</div>';
        $html .= '</td>';

        $html .= '<td style="text-align:center;"><span style="font-weight:700;">' . (int)$o->total_pages . '</span><div style="font-size:11px;color:#9ca3af;">' . $o->page_count . ' doc pg</div></td>';
        $html .= '<td><strong>&#8369;' . number_format($o->total_price, 2) . '</strong></td>';
        
        $html .= '<td>';
        $html .= '<span class="ps-badge ' . ($o->payment_status === 'paid' ? 'ps-badge-ready' : 'ps-badge-pending') . '">' . ucfirst($o->payment_status) . '</span>';
        if ($o->payment_method) $html .= '<div style="font-size:11px;color:#6b7280;margin-top:4px;">' . esc_html($o->payment_method) . '</div>';
        $html .= '</td>';

        $html .= '<td>';
        $html .= '<select class="ps-status-select bntm-select" data-id="' . $o->id . '" data-nonce="' . $nonce . '" style="font-size:12px;padding:4px 8px;" ' . (in_array($o->status, ['picked_up', 'cancelled']) ? 'disabled' : '') . '>';
        foreach ($statuses as $s) {
            if ($s === 'picked_up') continue;
            $selected = $o->status === $s ? 'selected' : '';
            $html .= '<option value="' . $s . '" ' . $selected . '>' . ucfirst(str_replace('_', ' ', $s)) . '</option>';
        }
        if ($o->status === 'picked_up') {
            $html .= '<option value="picked_up" selected disabled>Picked Up</option>';
        }
        $html .= '</select></td>';

        $html .= '<td style="font-size:12px;color:#6b7280;white-space:nowrap;">' . date('g:i A', strtotime($o->created_at)) . '<br>' . date('n/j/Y', strtotime($o->created_at)) . '</td>';
        
        $html .= '<td><div style="display:flex;gap:6px;flex-direction:column;">';
        $html .= '<button class="bntm-btn-small bntm-btn-primary ps-view-btn" data-id="' . $o->id . '" data-nonce="' . $nonce . '">View All Details</button>';
        if (!in_array($o->status, ['picked_up', 'cancelled'])) {
            $html .= '<button class="bntm-btn-small bntm-btn-secondary ps-pickup-btn" data-id="' . $o->id . '" data-nonce="' . $nonce . '">Picked Up</button>';
        }
        if ($o->payment_status === 'unpaid') {
            $html .= '<button class="bntm-btn-small bntm-btn-primary ps-markpaid-btn" data-id="' . $o->id . '" data-nonce="' . $nonce . '" style="background:linear-gradient(135deg,#16a34a,#22c55e);border-color:#16a34a;">Mark Paid</button>';
        }
        $html .= '<button class="bntm-btn-small bntm-btn-danger ps-delete-btn" data-id="' . $o->id . '" data-nonce="' . $nonce . '">Delete</button>';
        $html .= '</div></td></tr>';
    }

    wp_send_json_success(['html' => $html]);
}

function bntm_ajax_ps_delete_order() {
    check_ajax_referer('ps_admin_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);
    global $wpdb;
    $t        = $wpdb->prefix . 'ps_orders';
    $order_id = intval($_POST['order_id'] ?? 0);
    $order    = $wpdb->get_row($wpdb->prepare("SELECT file_path FROM {$t} WHERE id=%d", $order_id));
    if ($order && $order->file_path && file_exists($order->file_path)) @unlink($order->file_path);
    $result = $wpdb->delete($t, ['id' => $order_id], ['%d']);
    if ($result) wp_send_json_success(['message' => 'Order deleted.']);
    else wp_send_json_error(['message' => 'Delete failed.']);
}

function bntm_ajax_ps_check_new_orders() {
    $nonce_result = check_ajax_referer( 'ps_check_nonce', 'nonce', false );
    if ( ! $nonce_result ) {
        // Nonce failed — likely a cookie/domain issue on the subdomain.
        // Log it for debugging, then bail.
        if ( defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
            error_log( 'PrintEase: ps_check_new_orders nonce failed. Referer: ' . ( $_SERVER['HTTP_REFERER'] ?? 'none' ) );
        }
        wp_send_json_error( [ 'message' => 'Security check failed. Please refresh the page and try again.' ] );
    }
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    global $wpdb;
    $t = $wpdb->prefix . 'ps_orders';
    $last_check = intval($_POST['last_check'] ?? 0);
    
    // Convert JS milliseconds (UTC) to the same timezone WordPress uses for created_at
    $last_check_time = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', floor( $last_check / 1000 ) ) );
    
    // Get new orders since last check, limit to 10
    $orders = $wpdb->get_results($wpdb->prepare(
        "SELECT id, rand_id, customer_name, customer_email, total_price, status, created_at 
         FROM {$t} 
         WHERE created_at > %s 
         ORDER BY created_at DESC 
         LIMIT 10",
        $last_check_time
    ));

    if ($orders) {
        wp_send_json_success(['orders' => $orders]);
    } else {
        wp_send_json_success(['orders' => []]);
    }
}

function bntm_ajax_ps_save_pricing() {
    check_ajax_referer('ps_admin_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    $keys = ['bw_a4','bw_a3','bw_letter','bw_long','color_a4','color_a3','color_letter','color_long',
             'binding_spiral','binding_staple','binding_hardcover',
             'shop_name','shop_address','shop_hours','shop_note',
             'allowed_file_types','max_file_mb','admin_email',
             'gcash_name','gcash_number','gcash_qr_url'];

    $text_keys = ['shop_name','shop_address','shop_hours','shop_note','allowed_file_types',
                  'admin_email','gcash_name','gcash_number','gcash_qr_url'];

    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $val = in_array($key, $text_keys) ? sanitize_text_field($_POST[$key]) : floatval($_POST[$key]);
            ps_set_setting($key, $val);
        }
    }

    // Handle extra_services separately — sanitize_text_field() destroys JSON
    if (isset($_POST['extra_services'])) {
        $raw     = wp_unslash($_POST['extra_services']);
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $clean = [];
            foreach ($decoded as $svc) {
                $clean[] = [
                    'name'     => sanitize_text_field($svc['name'] ?? ''),
                    'desc'     => sanitize_text_field($svc['desc'] ?? ''),
                    'price'    => floatval($svc['price'] ?? 0),
                    'per_copy' => intval($svc['per_copy'] ?? 0),
                ];
            }
            ps_set_setting('extra_services', wp_json_encode($clean));
        } else {
            ps_set_setting('extra_services', '[]');
        }
    }

    wp_send_json_success(['message' => 'Settings saved successfully!']);
}

// ─────────────────────────────────────────────────────────────
// 10. HELPER FUNCTIONS
// ─────────────────────────────────────────────────────────────

/**
 * Load ALL settings in one query and cache in memory for the request lifetime.
 * This turns N individual DB hits into a single SELECT per request.
 */
function ps_get_all_settings() {
    static $cache = null;
    if ($cache !== null) return $cache;
    global $wpdb;
    $t    = $wpdb->prefix . 'ps_settings';
    $rows = $wpdb->get_results("SELECT setting_key, setting_value FROM {$t}", ARRAY_A);
    $cache = [];
    if ($rows) {
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache;
}

function ps_get_setting($key, $default = '') {
    $cache = ps_get_all_settings();
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function ps_set_setting($key, $value) {
    global $wpdb;
    $t = $wpdb->prefix . 'ps_settings';
    $wpdb->query($wpdb->prepare(
        "INSERT INTO {$t} (setting_key, setting_value, business_id) VALUES (%s, %s, 0)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        $key, $value
    ));
}

function ps_format_filesize($bytes) {
    if (!$bytes) return '—';
    if ($bytes < 1024)    return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

function ps_get_file_url($file_path) {
    static $upload_dir = null;
    if ($upload_dir === null) $upload_dir = wp_upload_dir();
    return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);
}

function ps_count_pdf_pages($file_path) {
    $count = 0;
    try {
        $handle = @fopen($file_path, 'rb');
        if ($handle) {
            $content = fread($handle, 1024 * 64);
            fclose($handle);
            preg_match_all('/\/Type\s*\/Page[^s]/i', $content, $matches);
            if (!empty($matches[0])) { $count = count($matches[0]); }
            if (!$count) { preg_match('/\/Count\s+(\d+)/i', $content, $m); if (!empty($m[1])) $count = (int) $m[1]; }
        }
    } catch (Exception $e) {}
    return $count;
}