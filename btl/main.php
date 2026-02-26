<?php
/**
 * Module Name: Business To-Do List
 * Module Slug: btl
 * Description: Task management module for MSME businesses. Manage tasks by category, priority, and due date.
 * Version: 1.0.1
 * Author: BNTM Framework
 * Icon: tasks
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Module constants
if (!defined('BNTM_BTL_PATH')) {
    define('BNTM_BTL_PATH', dirname(__FILE__) . '/');
}
if (!defined('BNTM_BTL_URL')) {
    define('BNTM_BTL_URL', plugin_dir_url(__FILE__));
}

// =============================================================================
// MODULE CONFIGURATION FUNCTIONS
// =============================================================================

function bntm_btl_get_pages() {
    return [
        'Business Tasks' => '[bntm_btl_dashboard]',
    ];
}

function bntm_btl_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    return [
        'btl_tasks' => "CREATE TABLE {$prefix}btl_tasks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            title VARCHAR(255) NOT NULL,
            category ENUM('Sales','Operations','Finance','HR') NOT NULL DEFAULT 'Operations',
            priority ENUM('High','Medium','Low') NOT NULL DEFAULT 'Medium',
            status ENUM('pending','completed') NOT NULL DEFAULT 'pending',
            due_date DATE NULL,
            completed_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_status (status),
            INDEX idx_category (category)
        ) {$charset};"
    ];
}

function bntm_btl_get_shortcodes() {
    return [
        'bntm_btl_dashboard' => 'bntm_shortcode_btl',
    ];
}

function bntm_btl_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_btl_get_tables();
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
    return count($tables);
}

// =============================================================================
// AJAX ACTION HOOKS
// =============================================================================

add_action('wp_ajax_btl_add_task',      'bntm_ajax_btl_add_task');
add_action('wp_ajax_btl_toggle_task',   'bntm_ajax_btl_toggle_task');
add_action('wp_ajax_btl_delete_task',   'bntm_ajax_btl_delete_task');

// =============================================================================
// MAIN DASHBOARD SHORTCODE
// =============================================================================

function bntm_shortcode_btl() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to view your tasks.</div>';
    }

    $current_user = wp_get_current_user();
    $business_id  = $current_user->ID;
    $active_tab   = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
    
    // Create global nonce for all AJAX operations
    $ajax_nonce = wp_create_nonce('btl_task_nonce');

    ob_start();
    ?>
    <script>
    var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    var btlNonce = '<?php echo esc_js($ajax_nonce); ?>';
    </script>

    <style>
    /* ===== GLOBAL DASHBOARD STYLES ===== */
    .btl-wrap * { box-sizing: border-box; }
    .btl-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #111827; }

    /* Tabs */
    .btl-tabs { display: flex; gap: 4px; border-bottom: 1px solid #e5e7eb; margin-bottom: 24px; }
    .btl-tab-btn {
        padding: 10px 18px; border: none; background: none; cursor: pointer;
        font-size: 14px; font-weight: 500; color: #6b7280; border-bottom: 2px solid transparent;
        margin-bottom: -1px; transition: all .15s;
    }
    .btl-tab-btn:hover { color: #374151; }
    .btl-tab-btn.active { color: #4f46e5; border-bottom-color: #4f46e5; }

    /* Stat Cards */
    .btl-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 28px; }
    .btl-stat-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
        padding: 18px 20px; display: flex; align-items: center; gap: 14px;
    }
    .btl-stat-icon {
        width: 44px; height: 44px; border-radius: 10px; display: flex;
        align-items: center; justify-content: center; flex-shrink: 0;
    }
    .btl-stat-icon svg { width: 22px; height: 22px; }
    .btl-stat-label { font-size: 12px; color: #6b7280; margin-bottom: 2px; }
    .btl-stat-value { font-size: 22px; font-weight: 700; color: #111827; line-height: 1; }

    /* Section card */
    .btl-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 22px 24px; margin-bottom: 20px; }
    .btl-card-title { font-size: 15px; font-weight: 600; color: #111827; margin: 0 0 18px 0; }

    /* Add Task Form */
    .btl-add-form { display: grid; grid-template-columns: 1fr repeat(3, auto) auto; gap: 10px; align-items: end; }
    @media (max-width: 768px) { .btl-add-form { grid-template-columns: 1fr; } }

    .btl-form-group { display: flex; flex-direction: column; gap: 5px; }
    .btl-form-group label { font-size: 12px; font-weight: 500; color: #374151; }
    .btl-input, .btl-select {
        height: 38px; padding: 0 12px; border: 1px solid #d1d5db; border-radius: 7px;
        font-size: 14px; color: #111827; background: #fff; width: 100%;
        outline: none; transition: border-color .15s;
    }
    .btl-input:focus, .btl-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
    .btl-select { appearance: none; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center; padding-right: 30px;
    }

    /* Buttons */
    .btl-btn {
        height: 38px; padding: 0 18px; border: none; border-radius: 7px; font-size: 14px;
        font-weight: 500; cursor: pointer; transition: all .15s; display: inline-flex;
        align-items: center; gap: 7px; white-space: nowrap;
    }
    .btl-btn-primary { background: #4f46e5; color: #fff; }
    .btl-btn-primary:hover { background: #4338ca; }
    .btl-btn-primary:disabled { background: #a5b4fc; cursor: not-allowed; }
    .btl-btn-ghost { background: transparent; color: #6b7280; border: 1px solid #e5e7eb; }
    .btl-btn-ghost:hover { background: #f9fafb; color: #374151; }
    .btl-btn-danger-ghost { background: transparent; color: #dc2626; border: 1px solid #fee2e2; padding: 0 10px; height: 30px; font-size: 12px; }
    .btl-btn-danger-ghost:hover { background: #fef2f2; }

    /* Filter Bar */
    .btl-filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
    .btl-filter-chip {
        padding: 5px 14px; border-radius: 20px; font-size: 13px; font-weight: 500;
        border: 1px solid #e5e7eb; background: #fff; cursor: pointer; color: #374151;
        transition: all .15s;
    }
    .btl-filter-chip:hover { border-color: #4f46e5; color: #4f46e5; }
    .btl-filter-chip.active { background: #4f46e5; border-color: #4f46e5; color: #fff; }

    /* Task List */
    .btl-task-list { display: flex; flex-direction: column; gap: 8px; }
    .btl-task-item {
        display: flex; align-items: center; gap: 12px; padding: 14px 16px;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 9px;
        transition: box-shadow .15s;
    }
    .btl-task-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .btl-task-item.completed { opacity: .6; background: #f9fafb; }
    .btl-task-item.overdue { border-left: 3px solid #dc2626; }

    .btl-task-check {
        width: 18px; height: 18px; border-radius: 4px; border: 2px solid #d1d5db;
        flex-shrink: 0; cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: all .15s;
    }
    .btl-task-check.checked { background: #059669; border-color: #059669; }
    .btl-task-check svg { display: none; }
    .btl-task-check.checked svg { display: block; }

    .btl-task-body { flex: 1; min-width: 0; }
    .btl-task-title { font-size: 14px; font-weight: 500; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .completed .btl-task-title { text-decoration: line-through; color: #9ca3af; }
    .btl-task-meta { display: flex; gap: 8px; align-items: center; margin-top: 4px; flex-wrap: wrap; }

    /* Badges */
    .btl-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .4px;
    }
    .badge-cat-Sales       { background: #ede9fe; color: #7c3aed; }
    .badge-cat-Operations  { background: #dbeafe; color: #1d4ed8; }
    .badge-cat-Finance     { background: #d1fae5; color: #065f46; }
    .badge-cat-HR          { background: #fce7f3; color: #9d174d; }

    .badge-pri-High        { background: #fee2e2; color: #dc2626; }
    .badge-pri-Medium      { background: #fef3c7; color: #b45309; }
    .badge-pri-Low         { background: #f3f4f6; color: #6b7280; }

    .badge-due { font-size: 11px; color: #6b7280; }
    .badge-due.overdue { color: #dc2626; font-weight: 600; }

    .btl-task-actions { display: flex; gap: 6px; flex-shrink: 0; }

    /* Empty state */
    .btl-empty { text-align: center; padding: 48px 24px; color: #9ca3af; }
    .btl-empty svg { width: 48px; height: 48px; margin: 0 auto 12px; display: block; opacity: .4; }
    .btl-empty p { font-size: 14px; margin: 0; }

    /* Notice */
    .btl-notice { padding: 10px 14px; border-radius: 7px; font-size: 13px; margin: 10px 0; }
    .btl-notice-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .btl-notice-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    /* Category count row */
    .btl-cat-counts { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .btl-cat-count-item {
        padding: 12px 16px; border-radius: 9px; border: 1px solid #e5e7eb; background: #fff;
        display: flex; justify-content: space-between; align-items: center;
    }
    .btl-cat-count-label { font-size: 13px; font-weight: 500; }
    .btl-cat-count-val { font-size: 18px; font-weight: 700; }
    </style>

    <div class="btl-wrap">
        <div class="btl-tabs">
            <button class="btl-tab-btn <?php echo $active_tab === 'overview'   ? 'active' : ''; ?>" onclick="btlSwitchTab('overview')">Overview</button>
            <button class="btl-tab-btn <?php echo $active_tab === 'tasks'      ? 'active' : ''; ?>" onclick="btlSwitchTab('tasks')">All Tasks</button>
            <button class="btl-tab-btn <?php echo $active_tab === 'completed'  ? 'active' : ''; ?>" onclick="btlSwitchTab('completed')">Completed</button>
        </div>

        <div id="btl-tab-overview"  class="btl-tab-pane" style="display:<?php echo $active_tab==='overview'  ?'block':'none';?>">
            <?php echo btl_overview_tab($business_id); ?>
        </div>
        <div id="btl-tab-tasks"     class="btl-tab-pane" style="display:<?php echo $active_tab==='tasks'     ?'block':'none';?>">
            <?php echo btl_tasks_tab($business_id, 'pending'); ?>
        </div>
        <div id="btl-tab-completed" class="btl-tab-pane" style="display:<?php echo $active_tab==='completed' ?'block':'none';?>">
            <?php echo btl_tasks_tab($business_id, 'completed'); ?>
        </div>
    </div>

    <script>
    function btlSwitchTab(tab) {
        document.querySelectorAll('.btl-tab-pane').forEach(p => p.style.display = 'none');
        document.querySelectorAll('.btl-tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('btl-tab-' + tab).style.display = 'block';
        event.currentTarget.classList.add('active');
    }

    function btlAddTask(btn) {
        const form  = btn.closest('.btl-add-form');
        const title = form.querySelector('.btl-task-input').value.trim();
        const cat   = form.querySelector('.btl-cat-select').value;
        const pri   = form.querySelector('.btl-pri-select').value;
        const due   = form.querySelector('.btl-due-input').value;
        const tab   = btn.dataset.tab;
        const msgEl = document.querySelector('.btl-add-msg-' + tab);

        if (!title) { 
            alert('Please enter a task title.'); 
            return; 
        }

        btn.disabled = true;
        btn.textContent = 'Adding...';

        const fd = new FormData();
        fd.append('action', 'btl_add_task');
        fd.append('title', title);
        fd.append('category', cat);
        fd.append('priority', pri);
        fd.append('due_date', due);
        fd.append('nonce', btlNonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            msgEl.innerHTML = '<div class="btl-notice btl-notice-' + (json.success ? 'success' : 'error') + '">' + json.data.message + '</div>';
            if (json.success) {
                form.querySelector('.btl-task-input').value = '';
                form.querySelector('.btl-due-input').value  = '';
                setTimeout(() => location.reload(), 900);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Task';
            }
        })
        .catch(err => {
            console.error('Error adding task:', err);
            msgEl.innerHTML = '<div class="btl-notice btl-notice-error">Network error. Please try again.</div>';
            btn.disabled = false;
            btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Task';
        });
    }

    function btlToggleTask(id, el) {
        const item = el.closest('.btl-task-item');
        const fd   = new FormData();
        fd.append('action', 'btl_toggle_task');
        fd.append('task_id', id);
        fd.append('nonce', btlNonce);

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                const newStatus = json.data.status;
                if (newStatus === 'completed') {
                    el.classList.add('checked');
                    item.classList.add('completed');
                } else {
                    el.classList.remove('checked');
                    item.classList.remove('completed');
                }
            } else {
                alert(json.data.message);
            }
        })
        .catch(err => {
            console.error('Error toggling task:', err);
            alert('Network error. Please try again.');
        });
    }

    function btlDeleteTask(id, btn) {
        if (!confirm('Delete this task?')) return;
        
        const item = btn.closest('.btl-task-item');
        const fd   = new FormData();
        fd.append('action', 'btl_delete_task');
        fd.append('task_id', id);
        fd.append('nonce', btlNonce);

        btn.disabled = true;
        btn.textContent = 'Deleting...';

        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                item.style.transition = 'opacity .2s';
                item.style.opacity = '0';
                setTimeout(() => item.remove(), 200);
            } else {
                alert(json.data.message);
                btn.disabled = false;
                btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Delete';
            }
        })
        .catch(err => {
            console.error('Error deleting task:', err);
            alert('Network error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Delete';
        });
    }

    function btlFilter(el, filter) {
        document.querySelectorAll('#btl-filter-bar .btl-filter-chip').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        
        const today = new Date(); 
        today.setHours(0,0,0,0);
        
        document.querySelectorAll('#btl-task-list .btl-task-item').forEach(function(item) {
            let show = true;
            if (filter === 'all') {
                show = true;
            } else if (filter === 'overdue') {
                const due = item.dataset.due;
                show = due && new Date(due) < today;
            } else {
                show = item.dataset.category === filter || item.dataset.priority === filter;
            }
            item.style.display = show ? '' : 'none';
        });
    }
    </script>
    <?php
    $content = ob_get_clean();
    return bntm_universal_container('Business To-Do List', $content);
}

// =============================================================================
// TAB: OVERVIEW
// =============================================================================

function btl_overview_tab($business_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'btl_tasks';

    $total_pending   = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE business_id=%d AND status='pending'", $business_id));
    $total_completed = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE business_id=%d AND status='completed'", $business_id));
    $total_high      = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE business_id=%d AND priority='High' AND status='pending'", $business_id));
    $total_overdue   = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE business_id=%d AND status='pending' AND due_date IS NOT NULL AND due_date < CURDATE()", $business_id));

    // Per-category pending counts
    $cat_counts_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT category, COUNT(*) as cnt FROM {$table} WHERE business_id=%d AND status='pending' GROUP BY category",
        $business_id
    ));
    $cat_counts = ['Sales' => 0, 'Operations' => 0, 'Finance' => 0, 'HR' => 0];
    foreach ($cat_counts_raw as $row) {
        $cat_counts[$row->category] = (int)$row->cnt;
    }

    // Recent pending tasks (top 5 priority)
    $recent = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE business_id=%d AND status='pending' ORDER BY
            CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END,
            CASE WHEN due_date IS NOT NULL THEN 0 ELSE 1 END, due_date ASC
        LIMIT 5",
        $business_id
    ));

    ob_start(); ?>
    <div class="btl-stats">
        <div class="btl-stat-card">
            <div class="btl-stat-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
                <svg fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
            <div><div class="btl-stat-label">Pending</div><div class="btl-stat-value"><?php echo $total_pending; ?></div></div>
        </div>
        <div class="btl-stat-card">
            <div class="btl-stat-icon" style="background:linear-gradient(135deg,#10b981,#059669)">
                <svg fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div><div class="btl-stat-label">Completed</div><div class="btl-stat-value"><?php echo $total_completed; ?></div></div>
        </div>
        <div class="btl-stat-card">
            <div class="btl-stat-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
                <svg fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div><div class="btl-stat-label">Overdue</div><div class="btl-stat-value"><?php echo $total_overdue; ?></div></div>
        </div>
        <div class="btl-stat-card">
            <div class="btl-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <svg fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div><div class="btl-stat-label">High Priority</div><div class="btl-stat-value"><?php echo $total_high; ?></div></div>
        </div>
    </div>

    <!-- Pending by category -->
    <div class="btl-card">
        <div class="btl-card-title">Pending Tasks by Category</div>
        <div class="btl-cat-counts">
            <?php foreach ($cat_counts as $cat => $cnt): ?>
            <div class="btl-cat-count-item">
                <span class="btl-cat-count-label btl-badge badge-cat-<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></span>
                <span class="btl-cat-count-val"><?php echo $cnt; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quick add -->
    <div class="btl-card">
        <div class="btl-card-title">Quick Add Task</div>
        <?php echo btl_render_add_form('overview'); ?>
    </div>

    <!-- Recent / Priority tasks -->
    <div class="btl-card">
        <div class="btl-card-title">Priority Tasks</div>
        <?php if (empty($recent)): ?>
        <div class="btl-empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p>No pending tasks. You're all caught up!</p>
        </div>
        <?php else: ?>
        <div class="btl-task-list">
            <?php foreach ($recent as $task): echo btl_render_task_row($task); endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// =============================================================================
// TAB: TASKS (pending or completed)
// =============================================================================

function btl_tasks_tab($business_id, $status = 'pending') {
    global $wpdb;
    $table = $wpdb->prefix . 'btl_tasks';

    $tasks = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE business_id=%d AND status=%s
         ORDER BY
            CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END,
            CASE WHEN due_date IS NOT NULL THEN 0 ELSE 1 END,
            due_date ASC, created_at DESC",
        $business_id, $status
    ));

    ob_start();

    if ($status === 'pending'): ?>
    <!-- Add Task form on the tasks tab -->
    <div class="btl-card" style="margin-bottom:20px;">
        <div class="btl-card-title">Add New Task</div>
        <?php echo btl_render_add_form('tasks'); ?>
    </div>
    <?php endif; ?>

    <div class="btl-card">
        <div class="btl-card-title">
            <?php echo $status === 'pending' ? 'All Pending Tasks' : 'Completed Tasks'; ?>
            <span style="font-weight:400;color:#6b7280;margin-left:8px;font-size:13px;"><?php echo count($tasks); ?> tasks</span>
        </div>

        <?php if ($status === 'pending'): ?>
        <!-- Filter chips -->
        <div class="btl-filter-bar" id="btl-filter-bar">
            <button class="btl-filter-chip active" data-filter="all" onclick="btlFilter(this,'all')">All</button>
            <button class="btl-filter-chip" data-filter="Sales"       onclick="btlFilter(this,'Sales')">Sales</button>
            <button class="btl-filter-chip" data-filter="Operations"  onclick="btlFilter(this,'Operations')">Operations</button>
            <button class="btl-filter-chip" data-filter="Finance"     onclick="btlFilter(this,'Finance')">Finance</button>
            <button class="btl-filter-chip" data-filter="HR"          onclick="btlFilter(this,'HR')">HR</button>
            <button class="btl-filter-chip" data-filter="High"        onclick="btlFilter(this,'High')" style="border-color:#fee2e2;color:#dc2626;">High Priority</button>
            <button class="btl-filter-chip" data-filter="overdue"     onclick="btlFilter(this,'overdue')" style="border-color:#fecaca;color:#b91c1c;">Overdue</button>
        </div>
        <?php endif; ?>

        <?php if (empty($tasks)): ?>
        <div class="btl-empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p><?php echo $status === 'completed' ? 'No completed tasks yet.' : 'No pending tasks. Great work!'; ?></p>
        </div>
        <?php else: ?>
        <div class="btl-task-list" id="btl-task-list">
            <?php foreach ($tasks as $task): echo btl_render_task_row($task); endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// =============================================================================
// HELPER: RENDER SINGLE TASK ROW
// =============================================================================

function btl_render_task_row($task) {
    $today    = new DateTime();
    $today->setTime(0,0,0);
    $is_done  = $task->status === 'completed';
    $is_over  = !$is_done && !empty($task->due_date) && new DateTime($task->due_date) < $today;

    $row_class = 'btl-task-item';
    if ($is_done)  $row_class .= ' completed';
    if ($is_over)  $row_class .= ' overdue';

    $due_label = '';
    if (!empty($task->due_date)) {
        $d = new DateTime($task->due_date);
        $due_label = $d->format('M j, Y');
    }

    ob_start(); ?>
    <div class="<?php echo esc_attr($row_class); ?>"
         data-id="<?php echo esc_attr($task->id); ?>"
         data-category="<?php echo esc_attr($task->category); ?>"
         data-priority="<?php echo esc_attr($task->priority); ?>"
         data-due="<?php echo esc_attr($task->due_date ?? ''); ?>">

        <!-- Checkbox -->
        <div class="btl-task-check <?php echo $is_done ? 'checked' : ''; ?>"
             onclick="btlToggleTask(<?php echo esc_js($task->id); ?>, this)">
            <svg width="10" height="10" fill="none" stroke="white" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <!-- Body -->
        <div class="btl-task-body">
            <div class="btl-task-title"><?php echo esc_html($task->title); ?></div>
            <div class="btl-task-meta">
                <span class="btl-badge badge-cat-<?php echo esc_attr($task->category); ?>"><?php echo esc_html($task->category); ?></span>
                <span class="btl-badge badge-pri-<?php echo esc_attr($task->priority); ?>"><?php echo esc_html($task->priority); ?></span>
                <?php if (!empty($due_label)): ?>
                <span class="badge-due <?php echo $is_over ? 'overdue' : ''; ?>">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:middle;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <?php echo $is_over ? 'Overdue: ' : ''; echo esc_html($due_label); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="btl-task-actions">
            <button class="btl-btn btl-btn-danger-ghost" onclick="btlDeleteTask(<?php echo esc_js($task->id); ?>, this)">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// =============================================================================
// HELPER: RENDER ADD TASK FORM
// =============================================================================

function btl_render_add_form($reload_tab = 'overview') {
    ob_start(); ?>
    <div class="btl-add-form" id="btl-add-form-<?php echo esc_attr($reload_tab); ?>">
        <div class="btl-form-group" style="flex:1;">
            <label>Task Title</label>
            <input type="text" class="btl-input btl-task-input" placeholder="Enter task title..." />
        </div>
        <div class="btl-form-group">
            <label>Category</label>
            <select class="btl-select btl-cat-select">
                <option value="Sales">Sales</option>
                <option value="Operations" selected>Operations</option>
                <option value="Finance">Finance</option>
                <option value="HR">HR</option>
            </select>
        </div>
        <div class="btl-form-group">
            <label>Priority</label>
            <select class="btl-select btl-pri-select">
                <option value="High">High</option>
                <option value="Medium" selected>Medium</option>
                <option value="Low">Low</option>
            </select>
        </div>
        <div class="btl-form-group">
            <label>Due Date</label>
            <input type="date" class="btl-input btl-due-input" style="width:160px;" />
        </div>
        <div class="btl-form-group">
            <label>&nbsp;</label>
            <button class="btl-btn btl-btn-primary btl-add-btn"
                    data-tab="<?php echo esc_attr($reload_tab); ?>"
                    onclick="btlAddTask(this)">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Task
            </button>
        </div>
    </div>
    <div class="btl-add-msg-<?php echo esc_attr($reload_tab); ?>"></div>
    <?php
    return ob_get_clean();
}

// =============================================================================
// AJAX HANDLERS
// =============================================================================

function bntm_ajax_btl_add_task() {
    check_ajax_referer('btl_task_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'btl_tasks';

    $title    = sanitize_text_field($_POST['title']);
    $category = sanitize_text_field($_POST['category']);
    $priority = sanitize_text_field($_POST['priority']);
    $due_date = sanitize_text_field($_POST['due_date']);

    if (empty($title)) {
        wp_send_json_error(['message' => 'Task title is required.']);
    }

    $allowed_cats = ['Sales', 'Operations', 'Finance', 'HR'];
    $allowed_pris = ['High', 'Medium', 'Low'];

    if (!in_array($category, $allowed_cats, true)) {
        wp_send_json_error(['message' => 'Invalid category.']);
    }
    if (!in_array($priority, $allowed_pris, true)) {
        wp_send_json_error(['message' => 'Invalid priority.']);
    }

    // Validate date format if provided
    if (!empty($due_date)) {
        $date_check = DateTime::createFromFormat('Y-m-d', $due_date);
        if (!$date_check || $date_check->format('Y-m-d') !== $due_date) {
            wp_send_json_error(['message' => 'Invalid date format.']);
        }
    }

    // Check if bntm_rand_id function exists
    if (!function_exists('bntm_rand_id')) {
        wp_send_json_error(['message' => 'System error: missing required function.']);
    }

    $data = [
        'rand_id'     => bntm_rand_id(),
        'business_id' => get_current_user_id(),
        'title'       => $title,
        'category'    => $category,
        'priority'    => $priority,
        'status'      => 'pending',
        'due_date'    => !empty($due_date) ? $due_date : null,
    ];

    $result = $wpdb->insert($table, $data, ['%s','%d','%s','%s','%s','%s','%s']);

    if ($result) {
        wp_send_json_success(['message' => 'Task added successfully!', 'id' => $wpdb->insert_id]);
    } else {
        error_log('BTL Task Insert Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Failed to add task. Please try again.']);
    }
}

function bntm_ajax_btl_toggle_task() {
    check_ajax_referer('btl_task_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    global $wpdb;
    $table   = $wpdb->prefix . 'btl_tasks';
    $task_id = intval($_POST['task_id']);
    $uid     = get_current_user_id();

    if ($task_id <= 0) {
        wp_send_json_error(['message' => 'Invalid task ID.']);
    }

    $task = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id=%d AND business_id=%d",
        $task_id, $uid
    ));

    if (!$task) {
        wp_send_json_error(['message' => 'Task not found.']);
    }

    $new_status   = $task->status === 'pending' ? 'completed' : 'pending';
    $completed_at = $new_status === 'completed' ? current_time('mysql') : null;

    $update_result = $wpdb->update(
        $table,
        [
            'status'       => $new_status, 
            'completed_at' => $completed_at
        ],
        ['id' => $task_id],
        ['%s', '%s'],
        ['%d']
    );

    if ($update_result !== false) {
        wp_send_json_success(['message' => 'Task updated.', 'status' => $new_status]);
    } else {
        error_log('BTL Task Toggle Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Failed to update task.']);
    }
}

function bntm_ajax_btl_delete_task() {
    check_ajax_referer('btl_task_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    global $wpdb;
    $table   = $wpdb->prefix . 'btl_tasks';
    $task_id = intval($_POST['task_id']);
    $uid     = get_current_user_id();

    if ($task_id <= 0) {
        wp_send_json_error(['message' => 'Invalid task ID.']);
    }

    $result = $wpdb->delete($table, ['id' => $task_id, 'business_id' => $uid], ['%d', '%d']);

    if ($result) {
        wp_send_json_success(['message' => 'Task deleted.']);
    } else {
        error_log('BTL Task Delete Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Could not delete task.']);
    }
}