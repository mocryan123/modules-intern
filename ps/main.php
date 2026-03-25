<?php
/**
 * Module Name: Print Shop
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
add_action('wp_ajax_ps_delete_order',            'bntm_ajax_ps_delete_order');
add_action('wp_ajax_ps_save_pricing',            'bntm_ajax_ps_save_pricing');
add_action('wp_ajax_ps_calculate_price',         'bntm_ajax_ps_calculate_price');
add_action('wp_ajax_nopriv_ps_calculate_price',  'bntm_ajax_ps_calculate_price');
add_action('wp_ajax_ps_mark_picked_up',          'bntm_ajax_ps_mark_picked_up');
add_action('wp_ajax_ps_mark_paid',               'bntm_ajax_ps_mark_paid');

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
    <script>var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';</script>

    <div class="bntm-ps-container">
        <div class="bntm-tabs">
            <a href="?tab=overview"  class="bntm-tab <?php echo $active_tab === 'overview'  ? 'active' : ''; ?>">Overview</a>
            <a href="?tab=orders"    class="bntm-tab <?php echo $active_tab === 'orders'    ? 'active' : ''; ?>">Orders</a>
            <a href="?tab=settings"  class="bntm-tab <?php echo $active_tab === 'settings'  ? 'active' : ''; ?>">Settings</a>
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
    .ps-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; letter-spacing:.3px; text-transform:capitalize; }
    .ps-badge-pending   { background:#fef3c7; color:#92400e; }
    .ps-badge-printing  { background:#dbeafe; color:#1e40af; }
    .ps-badge-ready     { background:#d1fae5; color:#065f46; }
    .ps-badge-picked_up { background:#e5e7eb; color:#374151; }
    .ps-badge-cancelled { background:#fee2e2; color:#991b1b; }
    .ps-options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
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
    })();
    </script>
    <?php
    $content = ob_get_clean();
    return bntm_universal_container('Print Shop', $content);
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

    ob_start();
    ?>
    <div class="bntm-stats-row">
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
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
            <div class="stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17H17.01M17 20H5a2 2 0 01-2-2V9a2 2 0 012-2h2V5a2 2 0 012-2h6a2 2 0 012 2v2h2a2 2 0 012 2v7a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="stat-content"><h3>Printing</h3><p class="stat-number"><?php echo number_format($printing); ?></p><span class="stat-label">In progress</span></div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content"><h3>Ready</h3><p class="stat-number"><?php echo number_format($ready); ?></p><span class="stat-label">For pickup</span></div>
        </div>
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#ec4899,#be185d);">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content"><h3>Revenue</h3><p class="stat-number">&#8369;<?php echo number_format($revenue, 2); ?></p><span class="stat-label">Total paid</span></div>
        </div>
    </div>

    <div class="bntm-form-section">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;">Recent Orders</h3>
            <a href="?tab=orders" class="bntm-btn-secondary" style="font-size:13px;">View All</a>
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
                    <td style="font-size:12px;color:#6b7280;"><?php echo date('M d, Y', strtotime($o->created_at)); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────
// 5. TAB: ORDERS
// ─────────────────────────────────────────────────────────────

function ps_orders_tab($business_id) {
    global $wpdb;
    $t = $wpdb->prefix . 'ps_orders';

    $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $search        = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

    $where  = 'WHERE 1=1';
    $params = [];
    if ($filter_status) { $where .= ' AND status=%s'; $params[] = $filter_status; }
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
                <input type="hidden" name="tab" value="orders">
                <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search by name, email, order ID..." class="bntm-input" style="flex:1;min-width:200px;">
                <select name="status" class="bntm-select">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?php echo $s; ?>" <?php selected($filter_status, $s); ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bntm-btn-primary">Filter</button>
                <?php if ($filter_status || $search): ?><a href="?tab=orders" class="bntm-btn-secondary">Clear</a><?php endif; ?>
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
                            <a href="<?php echo esc_url(ps_get_file_url($o->file_path)); ?>" target="_blank" style="font-size:11px;color:#3b82f6;">Download</a>
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
                        <select class="ps-status-select bntm-select" data-id="<?php echo $o->id; ?>" data-nonce="<?php echo $nonce; ?>" style="font-size:12px;padding:4px 8px;">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?php echo $s; ?>" <?php selected($o->status, $s); ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="font-size:12px;color:#6b7280;white-space:nowrap;"><?php echo date('M d, Y', strtotime($o->created_at)); ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-direction:column;">
                            <button class="bntm-btn-small bntm-btn-primary ps-view-btn" data-id="<?php echo $o->id; ?>" data-nonce="<?php echo $nonce; ?>">View</button>
                            <?php if ($o->status === 'ready'): ?>
                            <button class="bntm-btn-small bntm-btn-secondary ps-pickup-btn" data-id="<?php echo $o->id; ?>" data-nonce="<?php echo $nonce; ?>">Picked Up</button>
                            <?php endif; ?>
                            <?php if ($o->payment_status === 'unpaid'): ?>
                            <button class="bntm-btn-small bntm-btn-primary ps-markpaid-btn" data-id="<?php echo $o->id; ?>" data-nonce="<?php echo $nonce; ?>" style="background:#059669;">Mark Paid</button>
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
        const nonce = '<?php echo esc_js($nonce); ?>';

        document.querySelectorAll('.ps-status-select').forEach(sel => {
            sel.addEventListener('change', function() {
                const fd = new FormData();
                fd.append('action', 'ps_update_order_status');
                fd.append('order_id', this.dataset.id);
                fd.append('status', this.value);
                fd.append('nonce', nonce);
                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                    if (!json.success) alert('Failed to update status: ' + json.data.message);
                });
            });
        });

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
    $color_a4          = ps_get_setting('color_a4', 8.00);
    $color_a3          = ps_get_setting('color_a3', 14.00);
    $color_letter      = ps_get_setting('color_letter', 8.00);
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
        </div>

        <h4 style="margin-bottom:12px;font-size:14px;color:#374151;">Color</h4>
        <div class="ps-options-grid" style="margin-bottom:20px;">
            <div class="bntm-form-group"><label>A4 per page (₱)</label><input type="number" id="color_a4" value="<?php echo esc_attr($color_a4); ?>" step="0.01" min="0" class="bntm-input"></div>
            <div class="bntm-form-group"><label>A3 per page (₱)</label><input type="number" id="color_a3" value="<?php echo esc_attr($color_a3); ?>" step="0.01" min="0" class="bntm-input"></div>
            <div class="bntm-form-group"><label>Short/Letter per page (₱)</label><input type="number" id="color_letter" value="<?php echo esc_attr($color_letter); ?>" step="0.01" min="0" class="bntm-input"></div>
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
        <h3>Upload Configuration</h3>
        <div class="ps-options-grid">
            <div class="bntm-form-group"><label>Allowed File Types (comma-separated)</label><input type="text" id="allowed_file_types" value="<?php echo esc_attr($allowed_types); ?>" class="bntm-input" placeholder="pdf,doc,docx,jpg,png"></div>
            <div class="bntm-form-group"><label>Max File Size (MB)</label><input type="number" id="max_file_mb" value="<?php echo esc_attr($max_file_mb); ?>" min="1" max="100" class="bntm-input"></div>
        </div>
        <button id="ps-save-shop-btn" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Save Settings</button>
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
            saveSettings(collectSettings(['bw_a4','bw_a3','bw_letter','color_a4','color_a3','color_letter','binding_spiral','binding_staple','binding_hardcover']), this, this.dataset.nonce);
        });

        const shopBtn = document.getElementById('ps-save-shop-btn');
        shopBtn.dataset.label = 'Save Settings';
        shopBtn.addEventListener('click', function() {
            saveSettings(collectSettings(['shop_name','shop_address','shop_hours','shop_note','allowed_file_types','max_file_mb']), this, this.dataset.nonce);
        });

        const gcashBtn = document.getElementById('ps-save-gcash-btn');
        gcashBtn.dataset.label = 'Save GCash Details';
        gcashBtn.addEventListener('click', function() {
            saveSettings(collectSettings(['gcash_name','gcash_number','gcash_qr_url']), this, this.dataset.nonce);
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────
// 7. FRONTEND: ORDER SUBMISSION SHORTCODE (Redesigned)
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

    ob_start();
    ?>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script>var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';</script>

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
                        <div class="pso-brand-sub">Online Print Orders</div>
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
                <div class="pso-panel-header">
                    <h1 class="pso-heading">Upload your document</h1>
                    <p class="pso-subheading">Accepted: <strong><?php echo esc_html($allowed_types); ?></strong> &nbsp;&middot;&nbsp; Max <strong><?php echo $max_mb; ?>MB</strong></p>
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
                        <button type="button" class="pso-btn-outline" onclick="document.getElementById('pso-file-input').click()">Browse files</button>
                    </div>

                    <div class="pso-upload-progress" id="pso-upload-progress" style="display:none;">
                        <div class="pso-file-icon">
                            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0120 9.414V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div style="flex:1;">
                            <div id="pso-progress-filename" class="pso-progress-filename"></div>
                            <div class="pso-progress-track"><div class="pso-progress-fill" id="pso-progress-fill"></div></div>
                            <div id="pso-progress-pct" class="pso-progress-pct">0%</div>
                        </div>
                    </div>

                    <div class="pso-upload-success" id="pso-upload-success" style="display:none;">
                        <div class="pso-success-check">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <div id="pso-success-filename" class="pso-success-filename"></div>
                            <div id="pso-success-meta" class="pso-success-meta"></div>
                        </div>
                        <button type="button" class="pso-reupload-btn" onclick="psoResetUpload()">
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

                <div class="pso-options-grid">
                    <div class="pso-option-group">
                        <label class="pso-label">Paper Size</label>
                        <div class="pso-radio-cards" id="opt-paper-size">
                            <label class="pso-radio-card active"><input type="radio" name="paper_size" value="A4" checked><div class="pso-radio-card-inner"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" stroke-width="1.5"/></svg><span>A4</span><small>210 × 297 mm</small></div></label>
                            <label class="pso-radio-card"><input type="radio" name="paper_size" value="A3"><div class="pso-radio-card-inner"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2" stroke-width="1.5"/></svg><span>A3</span><small>297 × 420 mm</small></div></label>
                            <label class="pso-radio-card"><input type="radio" name="paper_size" value="Short"><div class="pso-radio-card-inner"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2" stroke-width="1.5"/></svg><span>Short</span><small>216 × 279 mm</small></div></label>
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
                </div>

                <div class="pso-price-card" id="pso-price-card">
                    <div class="pso-price-card-bg"></div>
                    <div class="pso-price-card-content">
                        <div class="pso-price-rows">
                            <div class="pso-price-row"><span>Pages &times; Copies</span><span id="pv-pages" class="pso-price-val">—</span></div>
                            <div class="pso-price-row"><span>Print cost</span><span id="pv-print" class="pso-price-val">₱0.00</span></div>
                            <div class="pso-price-row" id="pv-binding-row" style="display:none;"><span>Binding</span><span id="pv-binding" class="pso-price-val">₱0.00</span></div>
                        </div>
                        <div class="pso-price-total-row"><span>Total</span><span id="pv-total" class="pso-price-total-val">₱0.00</span></div>
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
                            <label class="pso-payment-card active"><input type="radio" name="payment_method" value="cash" checked><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>Cash on Pickup</label>
                            <label class="pso-payment-card"><input type="radio" name="payment_method" value="gcash"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>GCash</label>
                        </div>
                    </div>
                </div>

                <!-- GCash Details Panel -->
                <div id="pso-gcash-panel" style="display:none;margin-top:16px;padding:20px;background:#f0fdf4;border:1.5px solid #86efac;border-radius:var(--pso-radius-sm);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                        <svg width="20" height="20" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <strong style="font-size:13px;color:#065f46;">Send payment via GCash</strong>
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
                            <strong style="color:#065f46;"><?php echo esc_html($gcash_name); ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if ($gcash_number): ?>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:#6b7280;">GCash Number</span>
                            <strong style="color:#065f46;"><?php echo esc_html($gcash_number); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!$gcash_name && !$gcash_number && !$gcash_qr_url): ?>
                    <p style="font-size:13px;color:#6b7280;margin:0;">GCash details not yet configured. Please contact the shop.</p>
                    <?php endif; ?>
                    <p style="font-size:11px;color:#6b7280;margin:10px 0 0;font-style:italic;">Send the exact amount and include your name as reference.</p>
                    <label id="pso-gcash-confirm-wrap" style="display:flex;align-items:center;gap:10px;margin-top:16px;padding:12px 16px;background:#fff;border:1.5px solid #86efac;border-radius:8px;cursor:pointer;">
                        <input type="checkbox" id="pso-gcash-confirm" style="width:18px;height:18px;accent-color:#059669;cursor:pointer;flex-shrink:0;">
                        <span style="font-size:13px;font-weight:600;color:#065f46;">I have already sent the GCash payment</span>
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
                            Track Order
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
        --pso-surface:#fff; --pso-surface-2:#f8f9fa; --pso-border:#e5e7eb;
        --pso-accent:#1a56db; --pso-accent-2:#1e40af; --pso-accent-bg:#eff6ff;
        --pso-green:#059669; --pso-green-bg:#ecfdf5; --pso-red:#dc2626; --pso-red-bg:#fef2f2;
        --pso-sidebar-w:300px; --pso-radius:12px; --pso-radius-sm:8px;
        --pso-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);
        --pso-shadow-lg:0 8px 32px rgba(0,0,0,.12);
        --pso-font-body:'DM Sans',system-ui,sans-serif;
        --pso-font-head:'DM Serif Display',Georgia,serif;
    }
    .pso-root { display:flex; min-height:600px; font-family:var(--pso-font-body); color:var(--pso-ink); background:var(--pso-surface); border-radius:var(--pso-radius); overflow:hidden; box-shadow:var(--pso-shadow-lg); border:1px solid var(--pso-border); }
    .pso-sidebar { width:var(--pso-sidebar-w); background:var(--pso-ink); flex-shrink:0; position:relative; overflow:hidden; }
    .pso-sidebar::before { content:''; position:absolute; bottom:-80px; right:-80px; width:260px; height:260px; border-radius:50%; background:rgba(255,255,255,.03); pointer-events:none; }
    .pso-sidebar::after  { content:''; position:absolute; top:-60px; left:-60px; width:200px; height:200px; border-radius:50%; background:rgba(26,86,219,.12); pointer-events:none; }
    .pso-sidebar-inner { padding:36px 28px; height:100%; display:flex; flex-direction:column; gap:40px; position:relative; z-index:1; }
    .pso-brand { display:flex; align-items:center; gap:12px; }
    .pso-brand-icon { width:48px; height:48px; background:rgba(255,255,255,.1); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0; }
    .pso-brand-name { font-family:var(--pso-font-head); font-size:16px; color:#fff; line-height:1.2; }
    .pso-brand-sub { font-size:11px; color:rgba(255,255,255,.45); margin-top:2px; letter-spacing:.3px; }
    .pso-step-nav { display:flex; flex-direction:column; gap:0; }
    .pso-step-item { display:flex; align-items:flex-start; gap:14px; padding:12px 0; opacity:.4; transition:opacity .25s; }
    .pso-step-item.active { opacity:1; }
    .pso-step-item.done   { opacity:.7; }
    .pso-step-connector { width:2px; height:20px; background:rgba(255,255,255,.1); margin-left:19px; }
    .pso-step-dot { width:38px; height:38px; border-radius:50%; border:2px solid rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .25s; }
    .pso-step-item.active .pso-step-dot { border-color:var(--pso-accent); background:var(--pso-accent); }
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
    .pso-info-card { margin-top:auto; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08); border-radius:var(--pso-radius-sm); padding:16px; font-size:12px; color:rgba(255,255,255,.6); line-height:1.6; }
    .pso-info-card-title { display:flex; align-items:center; gap:6px; color:rgba(255,255,255,.9); font-weight:600; font-size:11px; letter-spacing:.4px; text-transform:uppercase; margin-bottom:10px; }
    .pso-info-card-text { margin-bottom:4px; }
    .pso-info-card-note { margin-top:8px; font-size:11px; color:rgba(255,255,255,.35); font-style:italic; }
    .pso-main { flex:1; padding:48px 52px; overflow-y:auto; display:flex; flex-direction:column; }
    .pso-panel { display:flex; flex-direction:column; flex:1; animation:psoFadeIn .3s ease; }
    @keyframes psoFadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
    .pso-panel-header { margin-bottom:32px; }
    .pso-heading { font-family:var(--pso-font-head); font-size:30px; font-weight:400; color:var(--pso-ink); margin:0 0 6px; line-height:1.2; }
    .pso-subheading { font-size:14px; color:var(--pso-ink-3); margin:0; }
    .pso-panel-footer { display:flex; justify-content:space-between; align-items:center; margin-top:auto; padding-top:32px; border-top:1px solid var(--pso-border); }
    .pso-btn-primary { display:inline-flex; align-items:center; gap:8px; background:var(--pso-accent); color:#fff; border:none; border-radius:var(--pso-radius-sm); padding:12px 24px; font-size:14px; font-weight:600; font-family:var(--pso-font-body); cursor:pointer; text-decoration:none; transition:background .2s,transform .1s,box-shadow .2s; box-shadow:0 2px 8px rgba(26,86,219,.25); }
    .pso-btn-primary:hover { background:var(--pso-accent-2); box-shadow:0 4px 16px rgba(26,86,219,.35); }
    .pso-btn-primary:active { transform:scale(.98); }
    .pso-btn-primary:disabled { background:#9ca3af; box-shadow:none; cursor:not-allowed; }
    .pso-btn-ghost { display:inline-flex; align-items:center; gap:8px; background:none; border:1px solid var(--pso-border); color:var(--pso-ink-3); border-radius:var(--pso-radius-sm); padding:11px 20px; font-size:14px; font-weight:500; font-family:var(--pso-font-body); cursor:pointer; transition:border-color .2s,color .2s,background .2s; }
    .pso-btn-ghost:hover { border-color:var(--pso-ink-3); color:var(--pso-ink); background:var(--pso-surface-2); }
    .pso-btn-outline { display:inline-flex; align-items:center; gap:8px; background:none; border:1.5px solid var(--pso-accent); color:var(--pso-accent); border-radius:var(--pso-radius-sm); padding:10px 22px; font-size:14px; font-weight:600; font-family:var(--pso-font-body); cursor:pointer; transition:all .2s; }
    .pso-btn-outline:hover { background:var(--pso-accent); color:#fff; }
    .pso-upload-zone { border:2px dashed var(--pso-border); border-radius:var(--pso-radius); padding:56px 32px; text-align:center; cursor:pointer; transition:border-color .2s,background .2s; background:var(--pso-surface-2); position:relative; }
    .pso-upload-zone:hover,.pso-upload-zone.dragging { border-color:var(--pso-accent); background:var(--pso-accent-bg); }
    .pso-upload-zone.has-file { border-color:var(--pso-green); background:var(--pso-green-bg); cursor:default; }
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
    .pso-progress-filename { font-size:13px; font-weight:600; color:var(--pso-ink); margin-bottom:8px; }
    .pso-progress-track { height:6px; background:var(--pso-border); border-radius:99px; overflow:hidden; }
    .pso-progress-fill { height:100%; width:0%; background:var(--pso-accent); border-radius:99px; transition:width .3s; }
    .pso-progress-pct { font-size:11px; color:var(--pso-ink-4); margin-top:4px; }
    .pso-upload-success { display:flex; align-items:center; gap:16px; text-align:left; }
    .pso-success-check { width:48px; height:48px; border-radius:50%; background:var(--pso-green); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .pso-success-filename { font-size:14px; font-weight:600; color:var(--pso-ink); }
    .pso-success-meta { font-size:12px; color:var(--pso-ink-3); margin-top:3px; }
    .pso-reupload-btn { margin-left:auto; display:inline-flex; align-items:center; gap:5px; background:none; border:1px solid var(--pso-border); border-radius:6px; padding:6px 12px; font-size:12px; color:var(--pso-ink-3); cursor:pointer; transition:all .2s; }
    .pso-reupload-btn:hover { border-color:var(--pso-ink-3); color:var(--pso-ink); }
    .pso-upload-error { margin-top:12px; padding:12px 16px; background:var(--pso-red-bg); border:1px solid #fecaca; border-radius:var(--pso-radius-sm); color:var(--pso-red); font-size:13px; }
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
    .pso-price-card { position:relative; overflow:hidden; border-radius:var(--pso-radius); margin-top:8px; }
    .pso-price-card-bg { position:absolute; inset:0; background:linear-gradient(135deg,#0d1117 0%,#1a2744 60%,#1a3a6b 100%); }
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
    .pso-total-banner { display:flex; justify-content:space-between; align-items:center; background:var(--pso-ink); color:#fff; border-radius:var(--pso-radius-sm); padding:20px 28px; margin-bottom:20px; }
    .pso-total-label { font-size:12px; color:rgba(255,255,255,.55); text-transform:uppercase; letter-spacing:.5px; }
    .pso-total-note  { font-size:13px; color:rgba(255,255,255,.7); margin-top:3px; }
    .pso-total-amount { font-family:var(--pso-font-head); font-size:36px; color:#fff; }
    .pso-error-msg { padding:12px 16px; background:var(--pso-red-bg); border:1px solid #fecaca; border-radius:var(--pso-radius-sm); color:var(--pso-red); font-size:13px; margin-bottom:16px; }
    .pso-btn-submit { padding:14px 32px; font-size:15px; }
    .pso-success-panel { justify-content:center; }
    .pso-success-inner { max-width:480px; margin:0 auto; text-align:center; padding:32px 0; }
    .pso-success-icon { width:80px; height:80px; border-radius:50%; background:var(--pso-green); color:#fff; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; box-shadow:0 8px 24px rgba(5,150,105,.3); }
    .pso-success-heading { font-family:var(--pso-font-head); font-size:32px; font-weight:400; color:var(--pso-ink); margin:0 0 10px; }
    .pso-success-sub { font-size:14px; color:var(--pso-ink-3); margin:0 0 28px; }
    .pso-order-id-chip { display:inline-flex; align-items:center; gap:12px; background:var(--pso-surface-2); border:1px solid var(--pso-border); border-radius:99px; padding:10px 20px; margin-bottom:28px; }
    .pso-order-id-label { font-size:11px; color:var(--pso-ink-4); text-transform:uppercase; letter-spacing:.5px; }
    .pso-order-id-value { font-size:16px; font-weight:800; color:var(--pso-ink); letter-spacing:1px; }
    .pso-copy-btn { background:none; border:none; cursor:pointer; color:var(--pso-ink-4); padding:2px; transition:color .2s; }
    .pso-copy-btn:hover { color:var(--pso-accent); }
    .pso-pickup-box { background:var(--pso-surface-2); border:1px solid var(--pso-border); border-radius:var(--pso-radius-sm); padding:20px; text-align:left; font-size:13px; line-height:1.7; margin-bottom:28px; }
    .pso-pickup-box-title { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:var(--pso-ink-3); margin-bottom:10px; }
    .pso-pickup-note { font-size:11px; color:var(--pso-ink-4); margin-top:6px; font-style:italic; }
    @media(max-width:900px){
        .pso-root { flex-direction:column; }
        .pso-sidebar { width:100%; }
        .pso-sidebar-inner { padding:24px 24px 20px; gap:20px; }
        .pso-step-nav { flex-direction:row; gap:0; overflow-x:auto; }
        .pso-step-connector { width:20px; height:2px; margin:0; align-self:center; }
        .pso-step-item { flex-direction:column; gap:6px; align-items:center; min-width:70px; }
        .pso-step-info { text-align:center; padding-top:0; }
        .pso-step-label { display:none; }
        .pso-info-card { display:none; }
        .pso-main { padding:28px 24px; }
        .pso-review-grid { grid-template-columns:1fr; }
        .pso-fields-grid { grid-template-columns:1fr; }
        .pso-two-col { grid-template-columns:1fr; }
    }
    @media(max-width:500px){
        .pso-main { padding:20px 16px; }
        .pso-heading { font-size:24px; }
        .pso-radio-cards { gap:8px; }
        .pso-radio-card-inner { padding:10px 12px; min-width:64px; }
        .pso-payment-options { flex-direction:column; }
        .pso-total-amount { font-size:28px; }
    }
    </style>

    <script>
    (function(){
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

        dropZone.addEventListener('click', () => { if (!dropZone.classList.contains('has-file')) fileInput.click(); });
        dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragging'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragging'));
        dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('dragging'); if (e.dataTransfer.files[0]) uploadFile(e.dataTransfer.files[0]); });
        fileInput.addEventListener('change', () => { if (fileInput.files[0]) uploadFile(fileInput.files[0]); });

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
                    nextBtn1.disabled = false;
                } else {
                    document.getElementById('pso-upload-idle').style.display = 'block';
                    errEl.style.display = 'block';
                    errEl.textContent   = json.data.message || 'Upload failed. Please try again.';
                }
            };
            xhr.onerror = () => {
                document.getElementById('pso-upload-progress').style.display = 'none';
                document.getElementById('pso-upload-idle').style.display = 'block';
                errEl.style.display = 'block';
                errEl.textContent   = 'Upload failed. Check your connection and try again.';
            };
            xhr.open('POST', ajaxurl);
            xhr.send(fd);
        }

        window.psoResetUpload = function() {
            uploadedFile = null; fileInput.value = '';
            dropZone.classList.remove('has-file');
            document.getElementById('pso-upload-idle').style.display    = 'block';
            document.getElementById('pso-upload-success').style.display = 'none';
            document.getElementById('pso-upload-error').style.display   = 'none';
            nextBtn1.disabled = true;
        };

        nextBtn1.addEventListener('click', () => { goToStep(2); recalculate(); });

        function initRadioCards(sel) {
            document.querySelectorAll(sel + ' .pso-radio-card').forEach(card => {
                card.addEventListener('click', () => {
                    document.querySelectorAll(sel + ' .pso-radio-card').forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                    card.querySelector('input').checked = true;
                    recalculate();
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
                // Show/hide GCash panel
                const gcashPanel = document.getElementById('pso-gcash-panel');
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
        document.getElementById('pso-qty-minus').addEventListener('click', () => { if (parseInt(qtyInput.value) > 1) { qtyInput.value = parseInt(qtyInput.value) - 1; recalculate(); } });
        document.getElementById('pso-qty-plus').addEventListener('click',  () => { if (parseInt(qtyInput.value) < 999) { qtyInput.value = parseInt(qtyInput.value) + 1; recalculate(); } });
        document.getElementById('opt-binding').addEventListener('change', recalculate);

        function getSelected(name) { const el = document.querySelector('input[name="' + name + '"]:checked'); return el ? el.value : ''; }

        function recalculate() {
            if (!uploadedFile) return;
            const fd = new FormData();
            fd.append('action', 'ps_calculate_price'); fd.append('nonce', calculateNonce);
            fd.append('page_count', uploadedFile.page_count || 1); fd.append('copies', qtyInput.value);
            fd.append('paper_size', getSelected('paper_size')); fd.append('color_mode', getSelected('color_mode'));
            fd.append('sides', getSelected('sides')); fd.append('binding', document.getElementById('opt-binding').value);
            fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                if (!json.success) return;
                calculatedPrice = json.data;
                document.getElementById('pv-pages').textContent = json.data.total_pages + ' pages';
                document.getElementById('pv-print').textContent = '₱' + json.data.print_cost;
                if (parseFloat(json.data.binding_cost) > 0) {
                    document.getElementById('pv-binding-row').style.display = 'flex';
                    document.getElementById('pv-binding').textContent = '₱' + json.data.binding_cost;
                } else { document.getElementById('pv-binding-row').style.display = 'none'; }
                document.getElementById('pv-total').textContent = '₱' + json.data.total_price;
            });
        }

        document.getElementById('pso-back-2').addEventListener('click', () => goToStep(1));
        document.getElementById('pso-next-2').addEventListener('click', () => goToStep(3));
        document.getElementById('pso-back-3').addEventListener('click', () => goToStep(2));
        document.getElementById('pso-next-3').addEventListener('click', () => {
            const name  = document.getElementById('cust-name').value.trim();
            if (!name)  { document.getElementById('cust-name').focus();  alert('Please enter your full name.'); return; }

            // Block if GCash selected but not confirmed
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

        const paymentLabels = {cash:'Cash on Pickup', gcash:'GCash'};
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
            document.getElementById('rv-total').textContent       = calculatedPrice ? '₱' + calculatedPrice.total_price : '₱0.00';
            document.getElementById('rv-total-note').textContent  = calculatedPrice ? calculatedPrice.total_pages + ' pages × ₱' + calculatedPrice.unit_price + '/page' : '';
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
            if (calculatedPrice) { fd.append('total_pages', calculatedPrice.total_pages); fd.append('unit_price', calculatedPrice.unit_price); fd.append('total_price', calculatedPrice.total_price); }

            this.disabled  = true;
            this.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="animation:spin .8s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Submitting…';

            fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(json => {
                if (json.success) {
                    document.getElementById('pso-order-id-display').textContent = json.data.rand_id;
                    const emailVal = document.getElementById('cust-email').value;
                    document.getElementById('pso-success-msg').textContent = emailVal
                        ? 'We\'ll notify you at ' + emailVal + ' when it\'s ready.'
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
            navigator.clipboard.writeText(val).then(() => { this.style.color = '#059669'; setTimeout(() => this.style.color = '', 1500); });
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
    <script>var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';</script>

    <div style="max-width:560px;margin:0 auto;font-family:'Segoe UI',system-ui,sans-serif;">
        <h2 style="font-size:24px;font-weight:700;margin-bottom:8px;">Track Your Order</h2>
        <p style="color:#6b7280;margin-bottom:24px;">Enter your Order ID to check the status of your print job.</p>
        <div style="display:flex;gap:10px;margin-bottom:24px;">
            <input type="text" id="ps-track-input" class="bntm-input" placeholder="e.g. PS-XXXXXXX" style="flex:1;">
            <button id="ps-track-btn" class="bntm-btn-primary" data-nonce="<?php echo $nonce; ?>">Track</button>
        </div>
        <div id="ps-track-result"></div>
    </div>

    <style>
    .ps-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; text-transform:capitalize; letter-spacing:.3px; }
    .ps-badge-pending   { background:#fef3c7; color:#92400e; }
    .ps-badge-printing  { background:#dbeafe; color:#1e40af; }
    .ps-badge-ready     { background:#d1fae5; color:#065f46; }
    .ps-badge-picked_up { background:#e5e7eb; color:#374151; }
    .ps-badge-cancelled { background:#fee2e2; color:#991b1b; }
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
                        const lineColor = i < currentIdx ? '#10b981' : '#e5e7eb';
                        stepsHtml += `<div style="display:flex;align-items:flex-start;gap:16px;">
                            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                                <div style="width:40px;height:40px;border-radius:50%;background:${active?'#3b82f6':done?'#10b981':'#e5e7eb'};display:flex;align-items:center;justify-content:center;">
                                    <svg width="20" height="20" fill="none" stroke="${done||active?'#fff':'#9ca3af'}" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${step.icon}"/></svg>
                                </div>
                                ${i < steps.length-1 ? `<div style="width:2px;height:40px;background:${lineColor};"></div>` : ''}
                            </div>
                            <div style="padding-top:10px;"><div style="font-weight:${active?'700':'500'};font-size:14px;color:${active?'#1e40af':done?'#065f46':'#9ca3af'};">${step.label}</div></div>
                        </div>`;
                    });
                    stepsHtml += '</div>';

                    resultEl.innerHTML = `<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
                            <div><div style="font-size:12px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Order ID</div><div style="font-size:20px;font-weight:800;color:#111827;">${o.rand_id}</div></div>
                            <span class="ps-badge ps-badge-${o.status}">${o.status.replace('_',' ')}</span>
                        </div>
                        <div style="margin-bottom:24px;font-size:13px;display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                            <div><span style="color:#9ca3af;">File: </span><span style="color:#374151;">${o.file_name}</span></div>
                            <div><span style="color:#9ca3af;">Customer: </span><span style="color:#374151;">${o.customer_name}</span></div>
                            <div><span style="color:#9ca3af;">Copies: </span><span style="color:#374151;">${o.copies}</span></div>
                            <div><span style="color:#9ca3af;">Paper: </span><span style="color:#374151;">${o.paper_size} ${o.color_mode==='color'?'Color':'B&W'}</span></div>
                            <div><span style="color:#9ca3af;">Total: </span><span style="font-weight:700;color:#1d4ed8;">₱${parseFloat(o.total_price).toFixed(2)}</span></div>
                            <div><span style="color:#9ca3af;">Payment: </span><span style="color:#374151;">${o.payment_status}</span></div>
                        </div>
                        ${stepsHtml}
                        ${o.status==='ready'?'<div style="margin-top:20px;padding:12px 16px;background:#d1fae5;border-radius:8px;color:#065f46;font-weight:600;font-size:14px;">Your order is ready! Please visit the shop to pick it up.</div>':''}
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
    $max_mb = (int) ps_get_setting('max_file_mb', 20);
    if ($file['size'] > $max_mb * 1024 * 1024) wp_send_json_error(['message' => "File exceeds maximum size of {$max_mb}MB."]);

    $allowed_raw   = ps_get_setting('allowed_file_types', 'pdf,doc,docx,ppt,pptx,jpg,png');
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
        'file_url'   => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $target_path),
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

    $prices = [
        'bw'    => ['A4' => ps_get_setting('bw_a4', 2), 'A3' => ps_get_setting('bw_a3', 4), 'Short' => ps_get_setting('bw_letter', 2)],
        'color' => ['A4' => ps_get_setting('color_a4', 8), 'A3' => ps_get_setting('color_a3', 14), 'Short' => ps_get_setting('color_letter', 8)],
    ];
    $unit_price   = floatval($prices[$color_mode][$paper_size] ?? $prices['bw']['A4']);
    $total_pages  = $page_count * $copies;
    $print_cost   = round($unit_price * $total_pages, 2);
    $binding_map  = ['spiral' => ps_get_setting('binding_spiral', 35), 'staple' => ps_get_setting('binding_staple', 5), 'hardcover' => ps_get_setting('binding_hardcover', 80), 'none' => 0];
    $binding_cost = floatval($binding_map[$binding] ?? 0) * $copies;
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

    if (!$customer_name || !$file_name) wp_send_json_error(['message' => 'Missing required fields.']);

    // Server-side recalculation
    $prices = [
        'bw'    => ['A4' => ps_get_setting('bw_a4', 2), 'A3' => ps_get_setting('bw_a3', 4), 'Short' => ps_get_setting('bw_letter', 2)],
        'color' => ['A4' => ps_get_setting('color_a4', 8), 'A3' => ps_get_setting('color_a3', 14), 'Short' => ps_get_setting('color_letter', 8)],
    ];
    $real_unit        = floatval($prices[$color_mode][$paper_size] ?? $prices['bw']['A4']);
    $real_total_pages = max(1, $page_count) * $copies;
    $binding_map      = ['spiral' => ps_get_setting('binding_spiral', 35), 'staple' => ps_get_setting('binding_staple', 5), 'hardcover' => ps_get_setting('binding_hardcover', 80), 'none' => 0];
    $real_binding     = floatval($binding_map[$binding] ?? 0) * $copies;
    $real_total       = round($real_unit * $real_total_pages + $real_binding, 2);

    $rand_id     = 'PS-' . strtoupper(substr(md5(uniqid() . $customer_email), 0, 8));
    $business_id = is_user_logged_in() ? get_current_user_id() : 0;

    $result = $wpdb->insert($t, [
        'rand_id' => $rand_id, 'business_id' => $business_id,
        'customer_name' => $customer_name, 'customer_email' => $customer_email, 'customer_phone' => $customer_phone,
        'file_name' => $file_name, 'file_path' => $file_path, 'file_size' => $file_size, 'page_count' => $page_count,
        'copies' => $copies, 'paper_size' => $paper_size, 'color_mode' => $color_mode,
        'orientation' => $orientation, 'sides' => $sides, 'binding' => $binding, 'notes' => $notes,
        'total_pages' => $real_total_pages, 'unit_price' => $real_unit, 'total_price' => $real_total,
        'payment_method' => $payment_method, 'status' => 'pending',
        'payment_status' => 'unpaid',
    ], ['%s','%d','%s','%s','%s','%s','%s','%d','%d','%d','%s','%s','%s','%s','%s','%s','%d','%f','%f','%s','%s','%s']);

    if ($result) {
        ps_send_confirmation_email($customer_email, $customer_name, $rand_id, $real_total, $file_name);
        wp_send_json_success(['rand_id' => $rand_id, 'total' => $real_total]);
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
    $file_url      = '';
    if ($o->file_path && file_exists($o->file_path)) {
        $upload_dir = wp_upload_dir();
        $file_url   = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $o->file_path);
    }

    ob_start();
    ?>
    <h2 style="margin:0 0 20px;font-size:20px;font-weight:700;">Order #<?php echo esc_html($o->rand_id); ?></h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:14px;margin-bottom:20px;">
        <div><div style="color:#9ca3af;font-size:12px;margin-bottom:2px;">Customer</div><div style="font-weight:600;"><?php echo esc_html($o->customer_name); ?></div></div>
        <div><div style="color:#9ca3af;font-size:12px;margin-bottom:2px;">Email</div><div><?php echo esc_html($o->customer_email); ?></div></div>
        <div><div style="color:#9ca3af;font-size:12px;margin-bottom:2px;">Phone</div><div><?php echo esc_html($o->customer_phone ?: '—'); ?></div></div>
        <div><div style="color:#9ca3af;font-size:12px;margin-bottom:2px;">Order Date</div><div><?php echo date('M d, Y h:i A', strtotime($o->created_at)); ?></div></div>
    </div>
    <div style="background:#f9fafb;border-radius:10px;padding:16px;margin-bottom:20px;">
        <h4 style="margin:0 0 12px;font-size:14px;">File Details</h4>
        <div style="font-size:13px;display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <div><span style="color:#9ca3af;">File name: </span><?php echo esc_html($o->file_name); ?></div>
            <div><span style="color:#9ca3af;">Size: </span><?php echo ps_format_filesize($o->file_size); ?></div>
            <div><span style="color:#9ca3af;">Doc pages: </span><?php echo $o->page_count ?: 'N/A'; ?></div>
            <?php if ($file_url): ?><div><a href="<?php echo esc_url($file_url); ?>" target="_blank" style="color:#3b82f6;">Download File</a></div><?php endif; ?>
        </div>
    </div>
    <div style="background:#f9fafb;border-radius:10px;padding:16px;margin-bottom:20px;">
        <h4 style="margin:0 0 12px;font-size:14px;">Print Configuration</h4>
        <div style="font-size:13px;display:grid;grid-template-columns:1fr 1fr;gap:8px;">
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
        <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:700;margin-top:8px;"><span>Total</span><span style="color:#059669;">&#8369;<?php echo number_format($o->total_price, 2); ?></span></div>
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

    // Send ready email after update
    if ($status === 'ready') {
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d", $order_id));
        if ($order) ps_send_status_email($order->customer_email, $order->customer_name, $order->rand_id, 'ready');
    }

    if ($result !== false) wp_send_json_success(['message' => 'Status updated']);
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

function bntm_ajax_ps_save_pricing() {
    check_ajax_referer('ps_admin_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized']);

    $keys = ['bw_a4','bw_a3','bw_letter','color_a4','color_a3','color_letter',
             'binding_spiral','binding_staple','binding_hardcover',
             'shop_name','shop_address','shop_hours','shop_note','allowed_file_types','max_file_mb',
             'gcash_name','gcash_number','gcash_qr_url'];
    $text_keys = ['shop_name','shop_address','shop_hours','shop_note','allowed_file_types',
                  'gcash_name','gcash_number','gcash_qr_url'];

    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $val = in_array($key, $text_keys) ? sanitize_text_field($_POST[$key]) : floatval($_POST[$key]);
            ps_set_setting($key, $val);
        }
    }
    wp_send_json_success(['message' => 'Settings saved successfully!']);
}

// ─────────────────────────────────────────────────────────────
// 10. HELPER FUNCTIONS
// ─────────────────────────────────────────────────────────────

function ps_get_setting($key, $default = '') {
    global $wpdb;
    $t   = $wpdb->prefix . 'ps_settings';
    $val = $wpdb->get_var($wpdb->prepare("SELECT setting_value FROM {$t} WHERE setting_key=%s LIMIT 1", $key));
    return $val !== null ? $val : $default;
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
    $upload_dir = wp_upload_dir();
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

function ps_send_confirmation_email($to, $name, $rand_id, $total, $file_name) {
    $shop_name  = ps_get_setting('shop_name', 'PrintEase');
    $shop_hours = ps_get_setting('shop_hours', '');
    $shop_addr  = ps_get_setting('shop_address', '');
    $subject    = "[{$shop_name}] Print Order Received — #{$rand_id}";
    $message    = "Hi {$name},\n\nYour print order has been received!\n\nOrder ID: {$rand_id}\nFile: {$file_name}\nTotal: ₱" . number_format($total, 2) . "\n\nWe will notify you when your order is ready for pickup.\n\n";
    if ($shop_addr)  $message .= "Pickup Location: {$shop_addr}\n";
    if ($shop_hours) $message .= "Hours: {$shop_hours}\n";
    $message .= "\nThank you!\n{$shop_name}";
    wp_mail($to, $subject, $message);
}

function ps_send_status_email($to, $name, $rand_id, $status) {
    $shop_name = ps_get_setting('shop_name', 'PrintEase');
    $shop_addr = ps_get_setting('shop_address', '');
    $shop_note = ps_get_setting('shop_note', '');
    if ($status !== 'ready') return;
    $subject = "[{$shop_name}] Your order #{$rand_id} is ready for pickup!";
    $message = "Hi {$name},\n\nGreat news! Your print order #{$rand_id} is now READY for pickup.\n\n";
    if ($shop_addr) $message .= "Location: {$shop_addr}\n";
    if ($shop_note) $message .= "{$shop_note}\n";
    $message .= "\nThank you for choosing {$shop_name}!";
    wp_mail($to, $subject, $message);
}