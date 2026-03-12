<?php
/**
 * Module Name: KonekBayan Finding Platform
 * Module Slug: kb
 * Description: Community crowdfunding and sponsorship platform for funders and sponsors.
 * Version: 1.0.0
 * Author: KonekBayan
 * Icon: finding-platform
 */

if (!defined('ABSPATH')) exit;

define('BNTM_KB_PATH', dirname(__FILE__) . '/');
define('BNTM_KB_URL', plugin_dir_url(__FILE__));

// ============================================================
// MODULE CONFIGURATION FUNCTIONS
// ============================================================

function bntm_kb_get_pages() {
    return [
        'KonekBayan Dashboard'  => '[kb_dashboard]',
        'Browse Funds'          => '[kb_browse]',
        'Fund Details'          => '[kb_fund_details]',
        'Admin Panel'           => '[kb_admin]',
    ];
}

function bntm_kb_get_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $prefix  = $wpdb->prefix;

    return [
        'kb_funds' => "CREATE TABLE {$prefix}kb_funds (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            funder_type ENUM('yourself','someone_else','charity_event') NOT NULL DEFAULT 'yourself',
            title VARCHAR(255) NOT NULL,
            description LONGTEXT,
            photos TEXT,
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
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_business (business_id),
            INDEX idx_status (status),
            INDEX idx_category (category)
        ) {$charset};",

        'kb_sponsorships' => "CREATE TABLE {$prefix}kb_sponsorships (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            sponsor_name VARCHAR(255),
            is_anonymous TINYINT(1) DEFAULT 0,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            email VARCHAR(255),
            phone VARCHAR(50),
            payment_method VARCHAR(50),
            payment_gateway VARCHAR(50),
            payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
            payment_reference VARCHAR(255),
            receipt_sent TINYINT(1) DEFAULT 0,
            notified TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_fund (fund_id),
            INDEX idx_payment_status (payment_status)
        ) {$charset};",

        'kb_withdrawals' => "CREATE TABLE {$prefix}kb_withdrawals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            method VARCHAR(100),
            account_details TEXT,
            status ENUM('pending','approved','released','rejected') DEFAULT 'pending',
            admin_notes TEXT,
            requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME,
            INDEX idx_fund (fund_id)
        ) {$charset};",

        'kb_reports' => "CREATE TABLE {$prefix}kb_reports (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            rand_id VARCHAR(20) UNIQUE NOT NULL,
            fund_id BIGINT UNSIGNED NOT NULL,
            reporter_id BIGINT UNSIGNED DEFAULT 0,
            reason VARCHAR(255),
            details TEXT,
            status ENUM('open','reviewed','dismissed') DEFAULT 'open',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_fund (fund_id),
            INDEX idx_status (status)
        ) {$charset};",
    ];
}

function bntm_kb_get_shortcodes() {
    return [
        'kb_dashboard'    => 'bntm_shortcode_kb_dashboard',
        'kb_browse'       => 'bntm_shortcode_kb_browse',
        'kb_fund_details' => 'bntm_shortcode_kb_fund_details',
        'kb_admin'        => 'bntm_shortcode_kb_admin',
    ];
}

function bntm_kb_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = bntm_kb_get_tables();
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
    return count($tables);
}

// ============================================================
// AJAX ACTION HOOKS
// ============================================================

// Funder actions (logged in)
add_action('wp_ajax_kb_create_fund',          'bntm_ajax_kb_create_fund');
add_action('wp_ajax_kb_update_fund',          'bntm_ajax_kb_update_fund');
add_action('wp_ajax_kb_cancel_fund',          'bntm_ajax_kb_cancel_fund');
add_action('wp_ajax_kb_request_withdrawal',   'bntm_ajax_kb_request_withdrawal');
add_action('wp_ajax_kb_extend_deadline',      'bntm_ajax_kb_extend_deadline');
add_action('wp_ajax_kb_toggle_auto_return',   'bntm_ajax_kb_toggle_auto_return');

// Sponsor actions (public)
add_action('wp_ajax_kb_sponsor_fund',         'bntm_ajax_kb_sponsor_fund');
add_action('wp_ajax_nopriv_kb_sponsor_fund',  'bntm_ajax_kb_sponsor_fund');
add_action('wp_ajax_kb_report_fund',          'bntm_ajax_kb_report_fund');
add_action('wp_ajax_nopriv_kb_report_fund',   'bntm_ajax_kb_report_fund');
add_action('wp_ajax_kb_get_fund_details',         'bntm_ajax_kb_get_fund_details');
add_action('wp_ajax_nopriv_kb_get_fund_details',  'bntm_ajax_kb_get_fund_details');

// Admin actions
add_action('wp_ajax_kb_admin_approve_fund',   'bntm_ajax_kb_admin_approve_fund');
add_action('wp_ajax_kb_admin_reject_fund',    'bntm_ajax_kb_admin_reject_fund');
add_action('wp_ajax_kb_admin_suspend',        'bntm_ajax_kb_admin_suspend');
add_action('wp_ajax_kb_admin_verify_badge',   'bntm_ajax_kb_admin_verify_badge');
add_action('wp_ajax_kb_admin_release_escrow', 'bntm_ajax_kb_admin_release_escrow');
add_action('wp_ajax_kb_admin_hold_escrow',    'bntm_ajax_kb_admin_hold_escrow');
add_action('wp_ajax_kb_admin_dismiss_report', 'bntm_ajax_kb_admin_dismiss_report');
add_action('wp_ajax_kb_admin_process_withdrawal', 'bntm_ajax_kb_admin_process_withdrawal');

// ============================================================
// DEADLINE CRON — auto-return funds
// ============================================================
add_action('kb_check_deadlines', 'kb_cron_check_deadlines');
if (!wp_next_scheduled('kb_check_deadlines')) {
    wp_schedule_event(time(), 'hourly', 'kb_check_deadlines');
}

function kb_cron_check_deadlines() {
    global $wpdb;
    $table = $wpdb->prefix . 'kb_funds';
    $now   = current_time('mysql');

    $expired = $wpdb->get_results(
        "SELECT * FROM {$table}
         WHERE status = 'active'
           AND deadline IS NOT NULL
           AND deadline < '{$now}'"
    );

    foreach ($expired as $fund) {
        if ($fund->raised_amount >= $fund->goal_amount) {
            $wpdb->update($table, ['status' => 'completed'], ['id' => $fund->id], ['%s'], ['%d']);
        } elseif ($fund->auto_return) {
            kb_refund_all_sponsors($fund->id);
            $wpdb->update($table, ['status' => 'cancelled', 'escrow_status' => 'refunded'], ['id' => $fund->id], ['%s', '%s'], ['%d']);
        } else {
            $wpdb->update($table, ['status' => 'completed'], ['id' => $fund->id], ['%s'], ['%d']);
        }
    }
}

// ============================================================
// MAIN FUNDER DASHBOARD SHORTCODE
// ============================================================

function bntm_shortcode_kb_dashboard() {
    if (!is_user_logged_in()) {
        return '<div class="bntm-notice">Please log in to access your dashboard.</div>';
    }

    $current_user = wp_get_current_user();
    $business_id  = $current_user->ID;
    $active_tab   = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

    ob_start();
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>

    <!-- MODAL: Create Fund -->
    <div id="kb-create-fund-modal" class="kb-modal-overlay" style="display:none;">
        <div class="kb-modal">
            <div class="kb-modal-header">
                <h3>Create New Fund</h3>
                <button class="kb-modal-close" onclick="document.getElementById('kb-create-fund-modal').style.display='none'">&times;</button>
            </div>
            <div class="kb-modal-body">
                <form id="kb-create-fund-form">
                    <div class="kb-form-row">
                        <div class="bntm-form-group">
                            <label>Funding For *</label>
                            <select name="funder_type" required>
                                <option value="yourself">Yourself</option>
                                <option value="someone_else">Someone Else</option>
                                <option value="charity_event">Charity or Event</option>
                            </select>
                        </div>
                        <div class="bntm-form-group">
                            <label>Category *</label>
                            <select name="category" required>
                                <option value="Community">Community</option>
                                <option value="Sports">Sports</option>
                                <option value="Family">Family</option>
                                <option value="Emergency">Emergency</option>
                                <option value="Education">Education</option>
                                <option value="Medical">Medical</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>

                    <div class="bntm-form-group">
                        <label>Fund Title *</label>
                        <input type="text" name="title" placeholder="Enter a clear, compelling title" required>
                    </div>

                    <div class="bntm-form-group">
                        <label>Description *</label>
                        <textarea name="description" rows="4" placeholder="Tell your story and why this funding matters..." required></textarea>
                    </div>

                    <div class="kb-form-row">
                        <div class="bntm-form-group">
                            <label>Goal Amount (PHP) *</label>
                            <input type="number" name="goal_amount" placeholder="0.00" min="100" step="0.01" required>
                        </div>
                        <div class="bntm-form-group">
                            <label>Deadline</label>
                            <input type="date" name="deadline" min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                        </div>
                    </div>

                    <div class="kb-form-row">
                        <div class="bntm-form-group">
                            <label>Contact Email *</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="bntm-form-group">
                            <label>Contact Phone *</label>
                            <input type="text" name="phone" placeholder="+63 9XX XXX XXXX" required>
                        </div>
                    </div>

                    <div class="bntm-form-group">
                        <label>Location *</label>
                        <input type="text" name="location" placeholder="City, Province" required>
                    </div>

                    <div class="bntm-form-group">
                        <label>Valid ID Upload *</label>
                        <input type="file" name="valid_id" accept="image/*,.pdf" required>
                        <small>Government or Company ID required for verification</small>
                    </div>

                    <div class="bntm-form-group">
                        <label>Fund Photos</label>
                        <input type="file" name="photos[]" accept="image/*" multiple>
                        <small>Upload up to 5 photos to support your campaign</small>
                    </div>

                    <div class="bntm-form-group kb-checkbox-group">
                        <label>
                            <input type="checkbox" name="auto_return" value="1">
                            Auto-return funds to sponsors if goal not met by deadline
                        </label>
                    </div>

                    <div id="kb-create-fund-message"></div>
                    <div class="kb-modal-footer">
                        <button type="button" class="bntm-btn-secondary" onclick="document.getElementById('kb-create-fund-modal').style.display='none'">Cancel</button>
                        <button type="submit" class="bntm-btn-primary">Submit Fund for Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: Edit Fund -->
    <div id="kb-edit-fund-modal" class="kb-modal-overlay" style="display:none;">
        <div class="kb-modal">
            <div class="kb-modal-header">
                <h3>Edit Fund</h3>
                <button class="kb-modal-close" onclick="document.getElementById('kb-edit-fund-modal').style.display='none'">&times;</button>
            </div>
            <div class="kb-modal-body">
                <form id="kb-edit-fund-form">
                    <input type="hidden" name="fund_id" id="edit-fund-id">
                    <div class="bntm-form-group">
                        <label>Title</label>
                        <input type="text" name="title" id="edit-fund-title">
                    </div>
                    <div class="bntm-form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit-fund-desc" rows="4"></textarea>
                    </div>
                    <div class="bntm-form-group">
                        <label>Location</label>
                        <input type="text" name="location" id="edit-fund-location">
                    </div>
                    <div id="kb-edit-fund-message"></div>
                    <div class="kb-modal-footer">
                        <button type="button" class="bntm-btn-secondary" onclick="document.getElementById('kb-edit-fund-modal').style.display='none'">Cancel</button>
                        <button type="submit" class="bntm-btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: Withdrawal -->
    <div id="kb-withdrawal-modal" class="kb-modal-overlay" style="display:none;">
        <div class="kb-modal">
            <div class="kb-modal-header">
                <h3>Request Withdrawal</h3>
                <button class="kb-modal-close" onclick="document.getElementById('kb-withdrawal-modal').style.display='none'">&times;</button>
            </div>
            <div class="kb-modal-body">
                <form id="kb-withdrawal-form">
                    <input type="hidden" name="fund_id" id="withdrawal-fund-id">
                    <div class="bntm-form-group">
                        <label>Amount to Withdraw (PHP) *</label>
                        <input type="number" name="amount" id="withdrawal-amount" placeholder="0.00" min="1" step="0.01" required>
                        <small id="withdrawal-available"></small>
                    </div>
                    <div class="bntm-form-group">
                        <label>Withdrawal Method *</label>
                        <select name="method" required>
                            <option value="">Select Method</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="bntm-form-group">
                        <label>Account Details *</label>
                        <textarea name="account_details" rows="3" placeholder="Account name, number, and other details" required></textarea>
                    </div>
                    <div id="kb-withdrawal-message"></div>
                    <div class="kb-modal-footer">
                        <button type="button" class="bntm-btn-secondary" onclick="document.getElementById('kb-withdrawal-modal').style.display='none'">Cancel</button>
                        <button type="submit" class="bntm-btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="bntm-kb-container">
        <div class="bntm-tabs">
            <a href="?tab=overview" class="bntm-tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>">Overview</a>
            <a href="?tab=my_funds" class="bntm-tab <?php echo $active_tab === 'my_funds' ? 'active' : ''; ?>">My Funds</a>
            <a href="?tab=sponsorships" class="bntm-tab <?php echo $active_tab === 'sponsorships' ? 'active' : ''; ?>">Sponsorships</a>
            <a href="?tab=withdrawals" class="bntm-tab <?php echo $active_tab === 'withdrawals' ? 'active' : ''; ?>">Withdrawals</a>
        </div>
        <div class="bntm-tab-content">
            <?php
            if ($active_tab === 'overview')       echo kb_dashboard_overview_tab($business_id);
            elseif ($active_tab === 'my_funds')   echo kb_dashboard_my_funds_tab($business_id);
            elseif ($active_tab === 'sponsorships') echo kb_dashboard_sponsorships_tab($business_id);
            elseif ($active_tab === 'withdrawals') echo kb_dashboard_withdrawals_tab($business_id);
            ?>
        </div>
    </div>

    <style>
    /* ===== GLOBAL KB STYLES ===== */
    .bntm-kb-container { font-family: 'Segoe UI', system-ui, sans-serif; }
    .kb-modal-overlay {
        position: fixed; inset: 0; background: rgba(15,23,42,0.65);
        display: flex; align-items: center; justify-content: center;
        z-index: 99999; backdrop-filter: blur(4px);
    }
    .kb-modal {
        background: #fff; border-radius: 16px; width: 90%; max-width: 680px;
        max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.25);
    }
    .kb-modal-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 24px 28px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;
    }
    .kb-modal-header h3 { margin: 0; font-size: 18px; font-weight: 700; color: #1e293b; }
    .kb-modal-close {
        background: none; border: none; font-size: 24px; cursor: pointer;
        color: #94a3b8; line-height: 1; padding: 0;
    }
    .kb-modal-close:hover { color: #ef4444; }
    .kb-modal-body { padding: 24px 28px; }
    .kb-modal-footer {
        display: flex; justify-content: flex-end; gap: 12px;
        padding: 16px 28px; border-top: 1px solid #e2e8f0;
        background: #f8fafc; border-radius: 0 0 16px 16px;
    }
    .kb-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .kb-checkbox-group label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
    .kb-checkbox-group input[type="checkbox"] { width: 16px; height: 16px; }

    .kb-progress-bar-wrap { background: #e2e8f0; border-radius: 99px; height: 8px; margin: 8px 0; }
    .kb-progress-bar { background: linear-gradient(90deg, #3b82f6, #6366f1); border-radius: 99px; height: 8px; transition: width 0.5s ease; }

    .kb-status-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .kb-status-pending    { background: #fef3c7; color: #92400e; }
    .kb-status-active     { background: #d1fae5; color: #065f46; }
    .kb-status-completed  { background: #dbeafe; color: #1e40af; }
    .kb-status-cancelled  { background: #fee2e2; color: #991b1b; }
    .kb-status-suspended  { background: #fce7f3; color: #9d174d; }
    .kb-status-draft      { background: #f1f5f9; color: #475569; }

    .kb-fund-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 20px; margin-bottom: 16px; transition: box-shadow 0.2s;
    }
    .kb-fund-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .kb-fund-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
    .kb-fund-card-title { font-weight: 700; font-size: 16px; color: #1e293b; margin: 0 0 4px; }
    .kb-fund-card-meta { font-size: 12px; color: #94a3b8; }
    .kb-fund-card-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
    .kb-fund-amounts { display: flex; gap: 24px; margin: 12px 0; }
    .kb-fund-amounts span { font-size: 13px; color: #64748b; }
    .kb-fund-amounts strong { display: block; font-size: 18px; color: #1e293b; }

    .kb-verified-badge {
        display: inline-flex; align-items: center; gap: 4px;
        background: #dbeafe; color: #1e40af; padding: 2px 8px;
        border-radius: 99px; font-size: 11px; font-weight: 600;
    }
    .kb-verified-badge svg { width: 12px; height: 12px; }

    .kb-share-btn {
        background: none; border: 1px solid #e2e8f0; border-radius: 8px;
        padding: 6px 12px; font-size: 13px; cursor: pointer; color: #475569;
        display: inline-flex; align-items: center; gap: 6px;
        transition: all 0.2s;
    }
    .kb-share-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }

    @media (max-width: 640px) {
        .kb-form-row { grid-template-columns: 1fr; }
        .kb-fund-card-header { flex-direction: column; }
    }
    </style>

    <script>
    (function() {
        // Create Fund Form
        const createForm = document.getElementById('kb-create-fund-form');
        if (createForm) {
            createForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true; btn.textContent = 'Submitting...';
                const formData = new FormData(this);
                formData.append('action', 'kb_create_fund');
                formData.append('nonce', '<?php echo wp_create_nonce('kb_create_fund'); ?>');
                fetch(ajaxurl, {method: 'POST', body: formData})
                .then(r => r.json())
                .then(json => {
                    const msg = document.getElementById('kb-create-fund-message');
                    msg.innerHTML = '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">' + json.data.message + '</div>';
                    if (json.success) setTimeout(() => location.reload(), 1800);
                    else { btn.disabled = false; btn.textContent = 'Submit Fund for Review'; }
                });
            });
        }

        // Edit Fund Form
        const editForm = document.getElementById('kb-edit-fund-form');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true; btn.textContent = 'Saving...';
                const formData = new FormData(this);
                formData.append('action', 'kb_update_fund');
                formData.append('nonce', '<?php echo wp_create_nonce('kb_update_fund'); ?>');
                fetch(ajaxurl, {method: 'POST', body: formData})
                .then(r => r.json())
                .then(json => {
                    const msg = document.getElementById('kb-edit-fund-message');
                    msg.innerHTML = '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">' + json.data.message + '</div>';
                    if (json.success) setTimeout(() => location.reload(), 1500);
                    else { btn.disabled = false; btn.textContent = 'Save Changes'; }
                });
            });
        }

        // Withdrawal Form
        const wdForm = document.getElementById('kb-withdrawal-form');
        if (wdForm) {
            wdForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true; btn.textContent = 'Submitting...';
                const formData = new FormData(this);
                formData.append('action', 'kb_request_withdrawal');
                formData.append('nonce', '<?php echo wp_create_nonce('kb_withdrawal'); ?>');
                fetch(ajaxurl, {method: 'POST', body: formData})
                .then(r => r.json())
                .then(json => {
                    const msg = document.getElementById('kb-withdrawal-message');
                    msg.innerHTML = '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">' + json.data.message + '</div>';
                    if (json.success) setTimeout(() => location.reload(), 1500);
                    else { btn.disabled = false; btn.textContent = 'Submit Request'; }
                });
            });
        }

        // Global share fund handler
        window.kbShareFund = function(token) {
            const url = window.location.origin + '/?kb_share=' + token;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => alert('Fund link copied to clipboard!'));
            } else {
                prompt('Copy this link:', url);
            }
        };

        // Open edit modal
        window.kbOpenEdit = function(id, title, desc, location) {
            document.getElementById('edit-fund-id').value = id;
            document.getElementById('edit-fund-title').value = title;
            document.getElementById('edit-fund-desc').value = desc;
            document.getElementById('edit-fund-location').value = location;
            document.getElementById('kb-edit-fund-modal').style.display = 'flex';
        };

        // Open withdrawal modal
        window.kbOpenWithdrawal = function(fundId, available) {
            document.getElementById('withdrawal-fund-id').value = fundId;
            document.getElementById('withdrawal-available').textContent = 'Available: ₱' + parseFloat(available).toFixed(2);
            document.getElementById('withdrawal-amount').max = available;
            document.getElementById('kb-withdrawal-modal').style.display = 'flex';
        };

        // Cancel fund
        window.kbCancelFund = function(fundId, nonce) {
            if (!confirm('Cancel this fund? This action cannot be undone.')) return;
            const fd = new FormData();
            fd.append('action', 'kb_cancel_fund');
            fd.append('fund_id', fundId);
            fd.append('nonce', nonce);
            fetch(ajaxurl, {method: 'POST', body: fd})
            .then(r => r.json())
            .then(json => {
                alert(json.data.message);
                if (json.success) location.reload();
            });
        };
    })();
    </script>
    <?php
    $content = ob_get_clean();
    return bntm_universal_container('KonekBayan — Finding Platform', $content);
}

// ============================================================
// TAB: Overview
// ============================================================

function kb_dashboard_overview_tab($business_id) {
    global $wpdb;
    $funds_table  = $wpdb->prefix . 'kb_funds';
    $spons_table  = $wpdb->prefix . 'kb_sponsorships';

    $total_funds    = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$funds_table} WHERE business_id=%d", $business_id));
    $active_funds   = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$funds_table} WHERE business_id=%d AND status='active'", $business_id));
    $total_raised   = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(raised_amount),0) FROM {$funds_table} WHERE business_id=%d", $business_id));
    $total_sponsors = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$spons_table} s JOIN {$funds_table} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",
        $business_id
    ));

    $recent_funds = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$funds_table} WHERE business_id=%d ORDER BY created_at DESC LIMIT 5",
        $business_id
    ));

    ob_start();
    ?>
    <div class="bntm-stats-row">
        <div class="bntm-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #6366f1);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3>Total Funds</h3>
                <p class="stat-number"><?php echo number_format($total_funds); ?></p>
                <span class="stat-label"><?php echo $active_funds; ?> active</span>
            </div>
        </div>

        <div class="bntm-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3>Total Raised</h3>
                <p class="stat-number">₱<?php echo number_format($total_raised, 0); ?></p>
                <span class="stat-label">across all funds</span>
            </div>
        </div>

        <div class="bntm-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3>Sponsors</h3>
                <p class="stat-number"><?php echo number_format($total_sponsors); ?></p>
                <span class="stat-label">total contributions</span>
            </div>
        </div>

        <div class="bntm-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3>Active Campaigns</h3>
                <p class="stat-number"><?php echo number_format($active_funds); ?></p>
                <span class="stat-label">currently running</span>
            </div>
        </div>
    </div>

    <div class="bntm-form-section">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3>Recent Funds</h3>
            <button class="bntm-btn-primary" onclick="document.getElementById('kb-create-fund-modal').style.display='flex'">
                + Create Fund
            </button>
        </div>

        <?php if (empty($recent_funds)): ?>
            <div style="text-align:center; padding:40px 0; color:#94a3b8;">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px; display:block;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <p>No funds yet. Create your first fund!</p>
            </div>
        <?php else: foreach ($recent_funds as $fund):
            $pct = $fund->goal_amount > 0 ? min(100, ($fund->raised_amount / $fund->goal_amount) * 100) : 0;
            ?>
            <div class="kb-fund-card">
                <div class="kb-fund-card-header">
                    <div>
                        <p class="kb-fund-card-title">
                            <?php echo esc_html($fund->title); ?>
                            <?php if ($fund->verified_badge): ?>
                                <span class="kb-verified-badge">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Verified
                                </span>
                            <?php endif; ?>
                        </p>
                        <span class="kb-fund-card-meta"><?php echo esc_html($fund->category); ?> &bull; <?php echo esc_html($fund->location); ?></span>
                    </div>
                    <span class="kb-status-badge kb-status-<?php echo $fund->status; ?>"><?php echo ucfirst($fund->status); ?></span>
                </div>
                <div class="kb-progress-bar-wrap">
                    <div class="kb-progress-bar" style="width:<?php echo $pct; ?>%"></div>
                </div>
                <div class="kb-fund-amounts">
                    <span><strong>₱<?php echo number_format($fund->raised_amount, 2); ?></strong>raised</span>
                    <span><strong>₱<?php echo number_format($fund->goal_amount, 2); ?></strong>goal</span>
                    <span><strong><?php echo round($pct); ?>%</strong>funded</span>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: My Funds
// ============================================================

function kb_dashboard_my_funds_tab($business_id) {
    global $wpdb;
    $funds_table = $wpdb->prefix . 'kb_funds';
    $spons_table = $wpdb->prefix . 'kb_sponsorships';

    $funds = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$funds_table} WHERE business_id=%d ORDER BY created_at DESC",
        $business_id
    ));

    $cancel_nonce = wp_create_nonce('kb_cancel_fund');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3>All My Funds</h3>
            <button class="bntm-btn-primary" onclick="document.getElementById('kb-create-fund-modal').style.display='flex'">
                + Create Fund
            </button>
        </div>

        <?php if (empty($funds)): ?>
            <p style="text-align:center; color:#94a3b8; padding:40px 0;">No funds created yet.</p>
        <?php else: foreach ($funds as $fund):
            $pct = $fund->goal_amount > 0 ? min(100, ($fund->raised_amount / $fund->goal_amount) * 100) : 0;
            $sponsors_count = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$spons_table} WHERE fund_id=%d AND payment_status='completed'",
                $fund->id
            ));
            ?>
            <div class="kb-fund-card">
                <div class="kb-fund-card-header">
                    <div style="flex:1;">
                        <p class="kb-fund-card-title">
                            <?php echo esc_html($fund->title); ?>
                            <?php if ($fund->verified_badge): ?>
                                <span class="kb-verified-badge">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Verified
                                </span>
                            <?php endif; ?>
                        </p>
                        <span class="kb-fund-card-meta">
                            <?php echo esc_html($fund->category); ?> &bull; <?php echo esc_html($fund->location); ?>
                            <?php if ($fund->deadline): ?> &bull; Deadline: <?php echo date('M d, Y', strtotime($fund->deadline)); ?><?php endif; ?>
                            &bull; <?php echo $sponsors_count; ?> sponsors
                        </span>
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                        <span class="kb-status-badge kb-status-<?php echo $fund->status; ?>"><?php echo ucfirst($fund->status); ?></span>
                        <span style="font-size:12px; color:#94a3b8;">
                            Escrow: <?php echo ucfirst($fund->escrow_status); ?>
                        </span>
                    </div>
                </div>

                <div class="kb-progress-bar-wrap">
                    <div class="kb-progress-bar" style="width:<?php echo $pct; ?>%"></div>
                </div>
                <div class="kb-fund-amounts">
                    <span><strong>₱<?php echo number_format($fund->raised_amount, 2); ?></strong>raised</span>
                    <span><strong>₱<?php echo number_format($fund->goal_amount, 2); ?></strong>goal</span>
                    <span><strong><?php echo round($pct); ?>%</strong>funded</span>
                </div>

                <div class="kb-fund-card-actions">
                    <?php if (in_array($fund->status, ['active', 'pending'])): ?>
                        <button class="bntm-btn-secondary bntm-btn-small"
                            onclick="kbOpenEdit(<?php echo $fund->id; ?>, '<?php echo esc_js($fund->title); ?>', '<?php echo esc_js($fund->description); ?>', '<?php echo esc_js($fund->location); ?>')">
                            Edit
                        </button>
                    <?php endif; ?>

                    <?php if ($fund->status === 'active' && $fund->raised_amount > 0): ?>
                        <button class="bntm-btn-primary bntm-btn-small"
                            onclick="kbOpenWithdrawal(<?php echo $fund->id; ?>, <?php echo $fund->raised_amount; ?>)">
                            Withdraw Funds
                        </button>
                        <button class="bntm-btn-secondary bntm-btn-small" id="extend-btn-<?php echo $fund->id; ?>"
                            onclick="kbExtendDeadline(<?php echo $fund->id; ?>, '<?php echo wp_create_nonce('kb_extend'); ?>')">
                            Extend Deadline
                        </button>
                    <?php endif; ?>

                    <button class="kb-share-btn" onclick="kbShareFund('<?php echo esc_js($fund->share_token); ?>')">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        Share
                    </button>

                    <?php if (!in_array($fund->status, ['cancelled', 'completed', 'suspended'])): ?>
                        <button class="bntm-btn-danger bntm-btn-small"
                            onclick="kbCancelFund(<?php echo $fund->id; ?>, '<?php echo $cancel_nonce; ?>')">
                            Cancel Fund
                        </button>
                    <?php endif; ?>
                </div>

                <?php
                // Sponsors mini-list
                $top_sponsors = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$spons_table} WHERE fund_id=%d AND payment_status='completed' ORDER BY amount DESC LIMIT 5",
                    $fund->id
                ));
                if (!empty($top_sponsors)): ?>
                <details style="margin-top:12px;">
                    <summary style="cursor:pointer; font-size:13px; color:#475569; font-weight:600;">
                        View Sponsors (<?php echo $sponsors_count; ?>)
                    </summary>
                    <div class="bntm-table-wrapper" style="margin-top:10px;">
                        <table class="bntm-table" style="font-size:13px;">
                            <thead><tr><th>Sponsor</th><th>Amount</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($top_sponsors as $sp): ?>
                                <tr>
                                    <td><?php echo $sp->is_anonymous ? '<em style="color:#94a3b8;">Anonymous</em>' : esc_html($sp->sponsor_name); ?></td>
                                    <td><strong>₱<?php echo number_format($sp->amount, 2); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($sp->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <script>
    window.kbExtendDeadline = function(fundId, nonce) {
        const newDate = prompt('Enter new deadline (YYYY-MM-DD):');
        if (!newDate) return;
        const fd = new FormData();
        fd.append('action', 'kb_extend_deadline');
        fd.append('fund_id', fundId);
        fd.append('deadline', newDate);
        fd.append('nonce', nonce);
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r => r.json())
        .then(json => { alert(json.data.message); if (json.success) location.reload(); });
    };
    </script>
    <?php
    return ob_get_clean();
}

// ============================================================
// TAB: Sponsorships Received
// ============================================================

function kb_dashboard_sponsorships_tab($business_id) {
    global $wpdb;
    $funds_table = $wpdb->prefix . 'kb_funds';
    $spons_table = $wpdb->prefix . 'kb_sponsorships';

    $sponsorships = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, f.title as fund_title FROM {$spons_table} s
         JOIN {$funds_table} f ON s.fund_id = f.id
         WHERE f.business_id = %d
         ORDER BY s.created_at DESC",
        $business_id
    ));

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>All Sponsorships Received</h3>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead>
                    <tr>
                        <th>Fund</th>
                        <th>Sponsor</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sponsorships)): ?>
                        <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:40px;">No sponsorships received yet.</td></tr>
                    <?php else: foreach ($sponsorships as $sp): ?>
                        <tr>
                            <td><strong><?php echo esc_html($sp->fund_title); ?></strong></td>
                            <td><?php echo $sp->is_anonymous ? '<em style="color:#94a3b8;">Anonymous</em>' : esc_html($sp->sponsor_name); ?></td>
                            <td><strong style="color:#059669;">₱<?php echo number_format($sp->amount, 2); ?></strong></td>
                            <td><?php echo esc_html(ucfirst($sp->payment_method ?? '—')); ?></td>
                            <td>
                                <span class="kb-status-badge kb-status-<?php echo $sp->payment_status; ?>">
                                    <?php echo ucfirst($sp->payment_status); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($sp->created_at)); ?></td>
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
// TAB: Withdrawals
// ============================================================

function kb_dashboard_withdrawals_tab($business_id) {
    global $wpdb;
    $funds_table = $wpdb->prefix . 'kb_funds';
    $wd_table    = $wpdb->prefix . 'kb_withdrawals';

    $withdrawals = $wpdb->get_results($wpdb->prepare(
        "SELECT w.*, f.title as fund_title FROM {$wd_table} w
         JOIN {$funds_table} f ON w.fund_id = f.id
         WHERE f.business_id = %d
         ORDER BY w.requested_at DESC",
        $business_id
    ));

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Withdrawal History</h3>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead>
                    <tr>
                        <th>Fund</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Processed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($withdrawals)): ?>
                        <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:40px;">No withdrawal requests yet.</td></tr>
                    <?php else: foreach ($withdrawals as $wd): ?>
                        <tr>
                            <td><strong><?php echo esc_html($wd->fund_title); ?></strong></td>
                            <td><strong>₱<?php echo number_format($wd->amount, 2); ?></strong></td>
                            <td><?php echo esc_html($wd->method); ?></td>
                            <td>
                                <span class="kb-status-badge kb-status-<?php echo $wd->status; ?>">
                                    <?php echo ucfirst($wd->status); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($wd->requested_at)); ?></td>
                            <td><?php echo $wd->processed_at ? date('M d, Y', strtotime($wd->processed_at)) : '—'; ?></td>
                        </tr>
                        <?php if (!empty($wd->admin_notes)): ?>
                        <tr>
                            <td colspan="6" style="background:#f8fafc; font-size:13px; color:#64748b; padding:8px 16px;">
                                <strong>Admin Note:</strong> <?php echo esc_html($wd->admin_notes); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ============================================================
// PUBLIC BROWSE SHORTCODE
// ============================================================

function bntm_shortcode_kb_browse() {
    $search    = isset($_GET['q'])        ? sanitize_text_field($_GET['q'])        : '';
    $category  = isset($_GET['cat'])      ? sanitize_text_field($_GET['cat'])      : '';
    $location  = isset($_GET['loc'])      ? sanitize_text_field($_GET['loc'])      : '';

    global $wpdb;
    $funds_table = $wpdb->prefix . 'kb_funds';

    $where   = "WHERE f.status = 'active'";
    $params  = [];

    if ($search) {
        $where .= " AND (f.title LIKE %s OR f.description LIKE %s)";
        $params[] = '%' . $wpdb->esc_like($search) . '%';
        $params[] = '%' . $wpdb->esc_like($search) . '%';
    }
    if ($category) {
        $where .= " AND f.category = %s";
        $params[] = $category;
    }
    if ($location) {
        $where .= " AND f.location LIKE %s";
        $params[] = '%' . $wpdb->esc_like($location) . '%';
    }

    $sql   = "SELECT f.*, u.display_name as organizer_name FROM {$funds_table} f LEFT JOIN {$wpdb->users} u ON f.business_id = u.ID {$where} ORDER BY f.created_at DESC";
    $funds = !empty($params) ? $wpdb->get_results($wpdb->prepare($sql, ...$params)) : $wpdb->get_results($sql);

    $categories = ['Community', 'Sports', 'Family', 'Emergency', 'Education', 'Medical', 'Others'];

    ob_start();
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>

    <!-- Sponsor Modal -->
    <div id="kb-sponsor-modal" class="kb-modal-overlay" style="display:none;">
        <div class="kb-modal">
            <div class="kb-modal-header">
                <h3>Sponsor This Fund</h3>
                <button class="kb-modal-close" onclick="document.getElementById('kb-sponsor-modal').style.display='none'">&times;</button>
            </div>
            <div class="kb-modal-body">
                <div id="kb-fund-preview" style="background:#f8fafc; border-radius:10px; padding:16px; margin-bottom:20px;"></div>
                <form id="kb-sponsor-form">
                    <input type="hidden" name="fund_id" id="sponsor-fund-id">
                    <div class="kb-form-row">
                        <div class="bntm-form-group">
                            <label>Your Name</label>
                            <input type="text" name="sponsor_name" id="sponsor-name-field" placeholder="Your full name">
                        </div>
                        <div class="bntm-form-group">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:8px;">
                                <input type="checkbox" id="anon-check" onchange="document.getElementById('sponsor-name-field').disabled=this.checked">
                                Sponsor Anonymously
                            </label>
                        </div>
                    </div>
                    <div class="bntm-form-group">
                        <label>Amount (PHP) *</label>
                        <input type="number" name="amount" placeholder="Enter amount" min="10" step="1" required>
                    </div>
                    <div class="kb-form-row">
                        <div class="bntm-form-group">
                            <label>Email (for receipt)</label>
                            <input type="email" name="email" placeholder="your@email.com">
                        </div>
                        <div class="bntm-form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" placeholder="+63 9XX XXX XXXX">
                        </div>
                    </div>
                    <div class="bntm-form-group">
                        <label>Payment Method *</label>
                        <select name="payment_method" id="sponsor-payment-method" required onchange="kbTogglePaymentDetails(this.value)">
                            <option value="">Select Payment Method</option>
                            <option value="gcash">GCash</option>
                            <option value="paymaya">PayMaya</option>
                            <option value="bank">Bank Transfer</option>
                        </select>
                    </div>
                    <div id="kb-payment-instructions" style="display:none; background:#eff6ff; border-radius:8px; padding:12px; font-size:13px; color:#1e40af; margin-bottom:12px;"></div>
                    <div id="kb-sponsor-message"></div>
                    <div class="kb-modal-footer">
                        <button type="button" class="bntm-btn-secondary" onclick="document.getElementById('kb-sponsor-modal').style.display='none'">Cancel</button>
                        <button type="submit" class="bntm-btn-primary">Confirm Sponsorship</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div id="kb-report-modal" class="kb-modal-overlay" style="display:none;">
        <div class="kb-modal" style="max-width:480px;">
            <div class="kb-modal-header">
                <h3>Report This Fund</h3>
                <button class="kb-modal-close" onclick="document.getElementById('kb-report-modal').style.display='none'">&times;</button>
            </div>
            <div class="kb-modal-body">
                <form id="kb-report-form">
                    <input type="hidden" name="fund_id" id="report-fund-id">
                    <div class="bntm-form-group">
                        <label>Reason *</label>
                        <select name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="Fraud">Fraudulent Campaign</option>
                            <option value="Misleading">Misleading Information</option>
                            <option value="Inappropriate">Inappropriate Content</option>
                            <option value="Scam">Suspected Scam</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="bntm-form-group">
                        <label>Details *</label>
                        <textarea name="details" rows="4" placeholder="Please describe the issue..." required></textarea>
                    </div>
                    <div id="kb-report-message"></div>
                    <div class="kb-modal-footer">
                        <button type="button" class="bntm-btn-secondary" onclick="document.getElementById('kb-report-modal').style.display='none'">Cancel</button>
                        <button type="submit" class="bntm-btn-danger">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="bntm-kb-container">
        <!-- Search & Filter Bar -->
        <div class="bntm-form-section" style="margin-bottom:20px;">
            <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div class="bntm-form-group" style="flex:2; min-width:200px; margin:0;">
                    <label>Search Funds</label>
                    <input type="text" name="q" value="<?php echo esc_attr($search); ?>" placeholder="Search by title or description...">
                </div>
                <div class="bntm-form-group" style="min-width:160px; margin:0;">
                    <label>Category</label>
                    <select name="cat">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php selected($category, $cat); ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="bntm-form-group" style="min-width:160px; margin:0;">
                    <label>Location</label>
                    <input type="text" name="loc" value="<?php echo esc_attr($location); ?>" placeholder="City, Province">
                </div>
                <button type="submit" class="bntm-btn-primary" style="height:42px; align-self:flex-end;">Search</button>
                <?php if ($search || $category || $location): ?>
                    <a href="?" class="bntm-btn-secondary" style="height:42px; display:inline-flex; align-items:center; text-decoration:none; align-self:flex-end;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Fund Grid -->
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:20px;">
            <?php if (empty($funds)): ?>
                <div style="grid-column:1/-1; text-align:center; padding:60px; color:#94a3b8;">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px; display:block;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p>No funds found. Try adjusting your filters.</p>
                </div>
            <?php else: foreach ($funds as $fund):
                $pct = $fund->goal_amount > 0 ? min(100, ($fund->raised_amount / $fund->goal_amount) * 100) : 0;
                $days_left = $fund->deadline ? max(0, ceil((strtotime($fund->deadline) - time()) / 86400)) : null;
                ?>
                <div class="kb-fund-card" style="display:flex; flex-direction:column;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <div>
                            <span style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:#6366f1; background:#eef2ff; padding:2px 8px; border-radius:99px;">
                                <?php echo esc_html($fund->category); ?>
                            </span>
                            <?php if ($fund->verified_badge): ?>
                                <span class="kb-verified-badge" style="margin-left:6px;">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Verified
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($days_left !== null): ?>
                            <span style="font-size:12px; color:#<?php echo $days_left < 7 ? 'ef4444' : '64748b'; ?>; font-weight:600;">
                                <?php echo $days_left; ?> days left
                            </span>
                        <?php endif; ?>
                    </div>

                    <h4 style="font-size:16px; font-weight:700; color:#1e293b; margin:0 0 6px;"><?php echo esc_html($fund->title); ?></h4>
                    <p style="font-size:13px; color:#64748b; margin:0 0 12px; flex:1; overflow:hidden; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical;">
                        <?php echo esc_html(wp_trim_words($fund->description, 25)); ?>
                    </p>

                    <div style="font-size:12px; color:#94a3b8; margin-bottom:10px; display:flex; align-items:center; gap:4px;">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <?php echo esc_html($fund->location); ?>
                        <span style="margin-left:8px;">by <?php echo esc_html($fund->organizer_name ?: 'Organizer'); ?></span>
                    </div>

                    <div class="kb-progress-bar-wrap">
                        <div class="kb-progress-bar" style="width:<?php echo $pct; ?>%"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:13px; margin:6px 0 14px;">
                        <span><strong style="color:#1e293b;">₱<?php echo number_format($fund->raised_amount, 0); ?></strong> <span style="color:#94a3b8;">raised</span></span>
                        <span style="color:#94a3b8;"><?php echo round($pct); ?>% of ₱<?php echo number_format($fund->goal_amount, 0); ?></span>
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button class="bntm-btn-primary" style="flex:1;"
                            onclick="kbOpenSponsor(<?php echo $fund->id; ?>, '<?php echo esc_js($fund->title); ?>', <?php echo $fund->goal_amount; ?>, <?php echo $fund->raised_amount; ?>)">
                            Sponsor Now
                        </button>
                        <button class="kb-share-btn"
                            onclick="kbShareFund('<?php echo esc_js($fund->share_token); ?>')">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                        </button>
                        <button class="kb-share-btn" style="border-color:#fecaca; color:#ef4444;"
                            onclick="kbOpenReport(<?php echo $fund->id; ?>)">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <style>
    .kb-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.65); display:flex; align-items:center; justify-content:center; z-index:99999; backdrop-filter:blur(4px); }
    .kb-modal { background:#fff; border-radius:16px; width:90%; max-width:680px; max-height:90vh; overflow-y:auto; box-shadow:0 25px 60px rgba(0,0,0,.25); }
    .kb-modal-header { display:flex; justify-content:space-between; align-items:center; padding:24px 28px 16px; border-bottom:1px solid #e2e8f0; }
    .kb-modal-header h3 { margin:0; font-size:18px; font-weight:700; color:#1e293b; }
    .kb-modal-close { background:none; border:none; font-size:24px; cursor:pointer; color:#94a3b8; line-height:1; padding:0; }
    .kb-modal-close:hover { color:#ef4444; }
    .kb-modal-body { padding:24px 28px; }
    .kb-modal-footer { display:flex; justify-content:flex-end; gap:12px; padding:16px 28px; border-top:1px solid #e2e8f0; background:#f8fafc; border-radius:0 0 16px 16px; }
    .kb-form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .kb-progress-bar-wrap { background:#e2e8f0; border-radius:99px; height:8px; margin:8px 0; }
    .kb-progress-bar { background:linear-gradient(90deg,#3b82f6,#6366f1); border-radius:99px; height:8px; transition:width .5s; }
    .kb-fund-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; transition:box-shadow .2s; }
    .kb-fund-card:hover { box-shadow:0 4px 20px rgba(0,0,0,.08); }
    </style>

    <script>
    window.kbShareFund = function(token) {
        const url = window.location.origin + '/?kb_share=' + token;
        if (navigator.clipboard) navigator.clipboard.writeText(url).then(() => alert('Link copied!'));
        else prompt('Copy this link:', url);
    };

    window.kbOpenSponsor = function(fundId, title, goal, raised) {
        document.getElementById('sponsor-fund-id').value = fundId;
        const pct = goal > 0 ? Math.min(100, Math.round((raised/goal)*100)) : 0;
        document.getElementById('kb-fund-preview').innerHTML =
            '<strong style="font-size:15px;">' + title + '</strong>' +
            '<div style="margin-top:8px; background:#e2e8f0; border-radius:99px; height:6px;">' +
            '<div style="width:' + pct + '%; background:linear-gradient(90deg,#3b82f6,#6366f1); border-radius:99px; height:6px;"></div></div>' +
            '<div style="display:flex; justify-content:space-between; font-size:12px; margin-top:6px; color:#64748b;">' +
            '<span>₱' + raised.toLocaleString() + ' raised</span><span>' + pct + '% funded</span></div>';
        document.getElementById('kb-sponsor-modal').style.display = 'flex';
    };

    window.kbOpenReport = function(fundId) {
        document.getElementById('report-fund-id').value = fundId;
        document.getElementById('kb-report-modal').style.display = 'flex';
    };

    window.kbTogglePaymentDetails = function(method) {
        const el = document.getElementById('kb-payment-instructions');
        const instructions = {
            gcash: 'Send payment to GCash number provided by the organizer. Use the fund title as your reference.',
            paymaya: 'Send payment via PayMaya to the account provided. Include fund title as reference.',
            bank: 'Transfer to the bank account details on file. Keep your transfer receipt as proof.'
        };
        if (instructions[method]) {
            el.textContent = instructions[method];
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    };

    (function() {
        // Sponsor form
        const sponsorForm = document.getElementById('kb-sponsor-form');
        if (sponsorForm) {
            sponsorForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true; btn.textContent = 'Processing...';
                const formData = new FormData(this);
                const anonCheck = document.getElementById('anon-check');
                formData.append('is_anonymous', anonCheck && anonCheck.checked ? '1' : '0');
                formData.append('action', 'kb_sponsor_fund');
                formData.append('nonce', '<?php echo wp_create_nonce('kb_sponsor'); ?>');
                fetch(ajaxurl, {method:'POST', body:formData})
                .then(r => r.json())
                .then(json => {
                    document.getElementById('kb-sponsor-message').innerHTML =
                        '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">' + json.data.message + '</div>';
                    if (json.success) setTimeout(() => location.reload(), 2000);
                    else { btn.disabled = false; btn.textContent = 'Confirm Sponsorship'; }
                });
            });
        }

        // Report form
        const reportForm = document.getElementById('kb-report-form');
        if (reportForm) {
            reportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true; btn.textContent = 'Submitting...';
                const formData = new FormData(this);
                formData.append('action', 'kb_report_fund');
                formData.append('nonce', '<?php echo wp_create_nonce('kb_report'); ?>');
                fetch(ajaxurl, {method:'POST', body:formData})
                .then(r => r.json())
                .then(json => {
                    document.getElementById('kb-report-message').innerHTML =
                        '<div class="bntm-notice bntm-notice-' + (json.success ? 'success' : 'error') + '">' + json.data.message + '</div>';
                    if (json.success) setTimeout(() => document.getElementById('kb-report-modal').style.display = 'none', 2000);
                    else { btn.disabled = false; btn.textContent = 'Submit Report'; }
                });
            });
        }
    })();
    </script>
    <?php
    $content = ob_get_clean();
    return bntm_universal_container('Browse Funds — KonekBayan', $content);
}

// ============================================================
// ADMIN PANEL SHORTCODE
// ============================================================

function bntm_shortcode_kb_admin() {
    if (!current_user_can('manage_options')) {
        return '<div class="bntm-notice">Access denied.</div>';
    }

    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';

    ob_start();
    ?>
    <script>
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

    window.kbAdminApprove = function(id, nonce) {
        if (!confirm('Approve this fund?')) return;
        kbAdminAction('kb_admin_approve_fund', {fund_id: id, _ajax_nonce: nonce});
    };
    window.kbAdminReject = function(id, nonce) {
        const reason = prompt('Reason for rejection (optional):');
        if (reason === null) return;
        kbAdminAction('kb_admin_reject_fund', {fund_id: id, reason: reason, _ajax_nonce: nonce});
    };
    window.kbAdminToggleBadge = function(id, current, nonce) {
        kbAdminAction('kb_admin_verify_badge', {fund_id: id, verified: current ? '0' : '1', _ajax_nonce: nonce});
    };
    window.kbAdminSuspend = function(id, nonce) {
        if (!confirm('Suspend this fund?')) return;
        kbAdminAction('kb_admin_suspend', {fund_id: id, _ajax_nonce: nonce});
    };
    window.kbAdminEscrow = function(id, action, nonce) {
        kbAdminAction('kb_admin_' + action + '_escrow', {fund_id: id, _ajax_nonce: nonce});
    };
    window.kbAdminAction = function(action, params) {
        const fd = new FormData();
        fd.append('action', action);
        Object.keys(params).forEach(function(k) { fd.append(k, params[k]); });
        fetch(ajaxurl, {method: 'POST', body: fd})
        .then(function(r) { return r.json(); })
        .then(function(json) {
            var msg = (json.data && json.data.message) ? json.data.message : (json.data || 'Action completed.');
            alert(msg);
            if (json.success) location.reload();
        })
        .catch(function(err) { alert('Request failed. Please try again.'); console.error(err); });
    };
    </script>

    <div class="bntm-kb-container">
        <div class="bntm-tabs">
            <a href="?tab=pending"      class="bntm-tab <?php echo $active_tab === 'pending'      ? 'active' : ''; ?>">Pending Approval</a>
            <a href="?tab=all_funds"    class="bntm-tab <?php echo $active_tab === 'all_funds'    ? 'active' : ''; ?>">All Funds</a>
            <a href="?tab=transactions" class="bntm-tab <?php echo $active_tab === 'transactions' ? 'active' : ''; ?>">Transactions</a>
            <a href="?tab=withdrawals"  class="bntm-tab <?php echo $active_tab === 'withdrawals'  ? 'active' : ''; ?>">Withdrawals</a>
            <a href="?tab=reports"      class="bntm-tab <?php echo $active_tab === 'reports'      ? 'active' : ''; ?>">Reports</a>
        </div>
        <div class="bntm-tab-content">
            <?php
            if ($active_tab === 'pending')      echo kb_admin_pending_tab();
            elseif ($active_tab === 'all_funds') echo kb_admin_all_funds_tab();
            elseif ($active_tab === 'transactions') echo kb_admin_transactions_tab();
            elseif ($active_tab === 'withdrawals')  echo kb_admin_withdrawals_tab();
            elseif ($active_tab === 'reports')      echo kb_admin_reports_tab();
            ?>
        </div>
    </div>
    <style>
    .kb-admin-actions { display:flex; gap:8px; flex-wrap:wrap; }
    </style>
    <?php
    $content = ob_get_clean();
    return bntm_universal_container('KonekBayan Admin Panel', $content);
}

function kb_admin_pending_tab() {
    global $wpdb;
    $table = $wpdb->prefix . 'kb_funds';
    $funds = $wpdb->get_results("SELECT f.*, u.display_name as organizer FROM {$table} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.status='pending' ORDER BY f.created_at ASC");
    $nonce = wp_create_nonce('kb_admin_action');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Funds Pending Approval</h3>
        <?php if (empty($funds)): ?>
            <p style="text-align:center; color:#94a3b8; padding:40px;">No funds pending review.</p>
        <?php else: foreach ($funds as $fund): ?>
            <div class="kb-fund-card">
                <div class="kb-fund-card-header">
                    <div>
                        <p class="kb-fund-card-title"><?php echo esc_html($fund->title); ?></p>
                        <span class="kb-fund-card-meta">
                            by <?php echo esc_html($fund->organizer); ?> &bull;
                            <?php echo esc_html($fund->category); ?> &bull;
                            <?php echo esc_html($fund->location); ?> &bull;
                            <?php echo esc_html($fund->funder_type); ?>
                        </span>
                        <p style="font-size:13px; color:#475569; margin:8px 0 0;"><?php echo esc_html(wp_trim_words($fund->description, 40)); ?></p>
                        <div style="display:flex; gap:20px; margin-top:10px; font-size:13px; color:#64748b;">
                            <span><strong>Goal:</strong> ₱<?php echo number_format($fund->goal_amount, 2); ?></span>
                            <span><strong>Deadline:</strong> <?php echo $fund->deadline ? date('M d, Y', strtotime($fund->deadline)) : 'None'; ?></span>
                            <span><strong>Email:</strong> <?php echo esc_html($fund->email); ?></span>
                            <span><strong>Phone:</strong> <?php echo esc_html($fund->phone); ?></span>
                        </div>
                        <?php if (!empty($fund->valid_id_path)): ?>
                            <div style="margin-top:8px;"><a href="<?php echo esc_url($fund->valid_id_path); ?>" target="_blank" class="bntm-btn-secondary bntm-btn-small">View Valid ID</a></div>
                        <?php endif; ?>
                    </div>
                    <span class="kb-status-badge kb-status-pending">Pending</span>
                </div>
                <div class="kb-admin-actions" style="margin-top:14px;">
                    <button class="bntm-btn-primary bntm-btn-small" onclick="kbAdminApprove(<?php echo $fund->id; ?>, '<?php echo $nonce; ?>')">Approve</button>
                    <button class="bntm-btn-danger bntm-btn-small" onclick="kbAdminReject(<?php echo $fund->id; ?>, '<?php echo $nonce; ?>')">Reject</button>
                    <button class="bntm-btn-secondary bntm-btn-small" onclick="kbAdminToggleBadge(<?php echo $fund->id; ?>, <?php echo $fund->verified_badge; ?>, '<?php echo $nonce; ?>')">
                        <?php echo $fund->verified_badge ? 'Remove Badge' : 'Grant Verified Badge'; ?>
                    </button>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <?php
    return ob_get_clean();
}

function kb_admin_all_funds_tab() {
    global $wpdb;
    $table = $wpdb->prefix . 'kb_funds';
    $funds = $wpdb->get_results("SELECT f.*, u.display_name as organizer FROM {$table} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID ORDER BY f.created_at DESC LIMIT 100");
    $nonce = wp_create_nonce('kb_admin_action');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>All Funds</h3>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead>
                    <tr>
                        <th>Title</th><th>Organizer</th><th>Category</th>
                        <th>Goal</th><th>Raised</th><th>Status</th><th>Escrow</th>
                        <th>Verified</th><th>Created</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($funds)): ?>
                        <tr><td colspan="10" style="text-align:center; color:#94a3b8; padding:40px;">No funds found.</td></tr>
                    <?php else: foreach ($funds as $fund): ?>
                        <tr>
                            <td><strong><?php echo esc_html(wp_trim_words($fund->title, 6)); ?></strong></td>
                            <td><?php echo esc_html($fund->organizer); ?></td>
                            <td><?php echo esc_html($fund->category); ?></td>
                            <td>₱<?php echo number_format($fund->goal_amount, 0); ?></td>
                            <td>₱<?php echo number_format($fund->raised_amount, 0); ?></td>
                            <td><span class="kb-status-badge kb-status-<?php echo $fund->status; ?>"><?php echo ucfirst($fund->status); ?></span></td>
                            <td><?php echo ucfirst($fund->escrow_status); ?></td>
                            <td><?php echo $fund->verified_badge ? '<span style="color:#059669;">Yes</span>' : 'No'; ?></td>
                            <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($fund->created_at)); ?></td>
                            <td>
                                <div class="kb-admin-actions">
                                    <button class="bntm-btn-secondary bntm-btn-small" onclick="kbAdminToggleBadge(<?php echo $fund->id; ?>, <?php echo $fund->verified_badge; ?>, '<?php echo $nonce; ?>')">
                                        <?php echo $fund->verified_badge ? 'Remove Badge' : 'Verify'; ?>
                                    </button>
                                    <?php if ($fund->status === 'active'): ?>
                                        <button class="bntm-btn-secondary bntm-btn-small" onclick="kbAdminEscrow(<?php echo $fund->id; ?>, '<?php echo $fund->escrow_status === 'holding' ? 'release' : 'hold'; ?>', '<?php echo $nonce; ?>')">
                                            <?php echo $fund->escrow_status === 'holding' ? 'Release Escrow' : 'Hold Escrow'; ?>
                                        </button>
                                        <button class="bntm-btn-danger bntm-btn-small" onclick="kbAdminSuspend(<?php echo $fund->id; ?>, '<?php echo $nonce; ?>')">Suspend</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kb_admin_transactions_tab() {
    global $wpdb;
    $spons_table = $wpdb->prefix . 'kb_sponsorships';
    $funds_table = $wpdb->prefix . 'kb_funds';

    $transactions = $wpdb->get_results(
        "SELECT s.*, f.title as fund_title FROM {$spons_table} s JOIN {$funds_table} f ON s.fund_id=f.id ORDER BY s.created_at DESC LIMIT 200"
    );

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>All Transactions</h3>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead>
                    <tr>
                        <th>Fund</th><th>Sponsor</th><th>Amount</th>
                        <th>Payment</th><th>Status</th><th>Receipt</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:40px;">No transactions found.</td></tr>
                    <?php else: foreach ($transactions as $txn): ?>
                        <tr>
                            <td><strong><?php echo esc_html(wp_trim_words($txn->fund_title, 5)); ?></strong></td>
                            <td><?php echo $txn->is_anonymous ? '<em style="color:#94a3b8;">Anonymous</em>' : esc_html($txn->sponsor_name); ?></td>
                            <td><strong style="color:#059669;">₱<?php echo number_format($txn->amount, 2); ?></strong></td>
                            <td><?php echo esc_html(ucfirst($txn->payment_method ?? '—')); ?></td>
                            <td><span class="kb-status-badge kb-status-<?php echo $txn->payment_status; ?>"><?php echo ucfirst($txn->payment_status); ?></span></td>
                            <td><?php echo $txn->receipt_sent ? '<span style="color:#059669;">Sent</span>' : '<span style="color:#94a3b8;">Pending</span>'; ?></td>
                            <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($txn->created_at)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kb_admin_withdrawals_tab() {
    global $wpdb;
    $wd_table    = $wpdb->prefix . 'kb_withdrawals';
    $funds_table = $wpdb->prefix . 'kb_funds';

    $withdrawals = $wpdb->get_results(
        "SELECT w.*, f.title as fund_title FROM {$wd_table} w JOIN {$funds_table} f ON w.fund_id=f.id ORDER BY w.requested_at DESC"
    );
    $nonce = wp_create_nonce('kb_admin_action');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Withdrawal Requests</h3>
        <div class="bntm-table-wrapper">
            <table class="bntm-table">
                <thead>
                    <tr><th>Fund</th><th>Amount</th><th>Method</th><th>Account Details</th><th>Status</th><th>Requested</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($withdrawals)): ?>
                        <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:40px;">No withdrawal requests.</td></tr>
                    <?php else: foreach ($withdrawals as $wd): ?>
                        <tr>
                            <td><strong><?php echo esc_html(wp_trim_words($wd->fund_title, 5)); ?></strong></td>
                            <td><strong>₱<?php echo number_format($wd->amount, 2); ?></strong></td>
                            <td><?php echo esc_html($wd->method); ?></td>
                            <td style="font-size:12px; max-width:200px;"><?php echo esc_html($wd->account_details); ?></td>
                            <td><span class="kb-status-badge kb-status-<?php echo $wd->status; ?>"><?php echo ucfirst($wd->status); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($wd->requested_at)); ?></td>
                            <td>
                                <?php if ($wd->status === 'pending'): ?>
                                <div class="kb-admin-actions">
                                    <button class="bntm-btn-primary bntm-btn-small"
                                        onclick="kbAdminAction('kb_admin_process_withdrawal', {withdrawal_id: <?php echo $wd->id; ?>, action_type: 'approve', _ajax_nonce: '<?php echo $nonce; ?>'})">
                                        Approve & Release
                                    </button>
                                    <button class="bntm-btn-danger bntm-btn-small"
                                        onclick="kbAdminAction('kb_admin_process_withdrawal', {withdrawal_id: <?php echo $wd->id; ?>, action_type: 'reject', _ajax_nonce: '<?php echo $nonce; ?>'})">
                                        Reject
                                    </button>
                                </div>
                                <?php else: echo '—'; endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kb_admin_reports_tab() {
    global $wpdb;
    $reports_table = $wpdb->prefix . 'kb_reports';
    $funds_table   = $wpdb->prefix . 'kb_funds';

    $reports = $wpdb->get_results(
        "SELECT r.*, f.title as fund_title FROM {$reports_table} r JOIN {$funds_table} f ON r.fund_id=f.id WHERE r.status='open' ORDER BY r.created_at DESC"
    );
    $nonce = wp_create_nonce('kb_admin_action');

    ob_start();
    ?>
    <div class="bntm-form-section">
        <h3>Open Reports</h3>
        <?php if (empty($reports)): ?>
            <p style="text-align:center; color:#94a3b8; padding:40px;">No open reports.</p>
        <?php else: foreach ($reports as $report): ?>
            <div class="kb-fund-card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <p style="font-weight:700; margin:0 0 4px;">Fund: <?php echo esc_html($report->fund_title); ?></p>
                        <p style="font-size:13px; color:#ef4444; font-weight:600; margin:0 0 4px;">Reason: <?php echo esc_html($report->reason); ?></p>
                        <p style="font-size:13px; color:#475569; margin:0;"><?php echo esc_html($report->details); ?></p>
                        <span class="kb-fund-card-meta" style="margin-top:6px; display:block;"><?php echo date('M d, Y H:i', strtotime($report->created_at)); ?></span>
                    </div>
                    <span class="kb-status-badge kb-status-pending">Open</span>
                </div>
                <div class="kb-admin-actions" style="margin-top:12px;">
                    <button class="bntm-btn-danger bntm-btn-small"
                        onclick="kbAdminAction('kb_admin_suspend', {fund_id: <?php echo $report->fund_id; ?>, _ajax_nonce: '<?php echo $nonce; ?>'})">
                        Suspend Fund
                    </button>
                    <button class="bntm-btn-secondary bntm-btn-small"
                        onclick="kbAdminAction('kb_admin_dismiss_report', {report_id: <?php echo $report->id; ?>, _ajax_nonce: '<?php echo $nonce; ?>'})">
                        Dismiss Report
                    </button>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ============================================================
// AJAX HANDLERS — FUNDER
// ============================================================

function bntm_ajax_kb_create_fund() {
    check_ajax_referer('kb_create_fund', 'nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table = $wpdb->prefix . 'kb_funds';
    $business_id = get_current_user_id();

    $required = ['title', 'description', 'goal_amount', 'email', 'phone', 'location', 'category', 'funder_type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error(['message' => 'Please fill all required fields.']);
        }
    }

    $goal = floatval($_POST['goal_amount']);
    if ($goal < 100) { wp_send_json_error(['message' => 'Minimum goal amount is ₱100.']); }

    $valid_id_path = '';
    if (!empty($_FILES['valid_id']['name'])) {
        if (!function_exists('wp_handle_upload')) require_once(ABSPATH . 'wp-admin/includes/file.php');
        $uploaded = wp_handle_upload($_FILES['valid_id'], ['test_form' => false]);
        if (isset($uploaded['url'])) {
            $valid_id_path = $uploaded['url'];
        }
    }

    $share_token = wp_generate_password(32, false);

    $result = $wpdb->insert($table, [
        'rand_id'       => bntm_rand_id(),
        'business_id'   => $business_id,
        'funder_type'   => sanitize_text_field($_POST['funder_type']),
        'title'         => sanitize_text_field($_POST['title']),
        'description'   => sanitize_textarea_field($_POST['description']),
        'goal_amount'   => $goal,
        'category'      => sanitize_text_field($_POST['category']),
        'email'         => sanitize_email($_POST['email']),
        'phone'         => sanitize_text_field($_POST['phone']),
        'location'      => sanitize_text_field($_POST['location']),
        'valid_id_path' => $valid_id_path,
        'auto_return'   => isset($_POST['auto_return']) ? 1 : 0,
        'deadline'      => !empty($_POST['deadline']) ? sanitize_text_field($_POST['deadline']) : null,
        'status'        => 'pending',
        'share_token'   => $share_token,
    ], ['%s','%d','%s','%s','%s','%f','%s','%s','%s','%s','%s','%d','%s','%s','%s']);

    if ($result) {
        wp_send_json_success(['message' => 'Fund submitted for review! We will notify you once approved.']);
    } else {
        wp_send_json_error(['message' => 'Failed to create fund. Please try again.']);
    }
}

function bntm_ajax_kb_update_fund() {
    check_ajax_referer('kb_update_fund', 'nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table = $wpdb->prefix . 'kb_funds';
    $fund_id     = intval($_POST['fund_id']);
    $business_id = get_current_user_id();

    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d AND business_id=%d", $fund_id, $business_id));
    if (!$fund) { wp_send_json_error(['message' => 'Fund not found.']); }

    $result = $wpdb->update($table, [
        'title'       => sanitize_text_field($_POST['title']),
        'description' => sanitize_textarea_field($_POST['description']),
        'location'    => sanitize_text_field($_POST['location']),
    ], ['id' => $fund_id], ['%s','%s','%s'], ['%d']);

    if ($result !== false) {
        wp_send_json_success(['message' => 'Fund updated successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to update fund.']);
    }
}

function bntm_ajax_kb_cancel_fund() {
    check_ajax_referer('kb_cancel_fund', 'nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table = $wpdb->prefix . 'kb_funds';
    $fund_id     = intval($_POST['fund_id']);
    $business_id = get_current_user_id();

    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d AND business_id=%d", $fund_id, $business_id));
    if (!$fund) { wp_send_json_error(['message' => 'Fund not found.']); }

    if ($fund->raised_amount > 0 && $fund->auto_return) {
        kb_refund_all_sponsors($fund_id);
    }

    $wpdb->update($table, ['status' => 'cancelled'], ['id' => $fund_id], ['%s'], ['%d']);
    wp_send_json_success(['message' => 'Fund cancelled successfully.']);
}

function bntm_ajax_kb_request_withdrawal() {
    check_ajax_referer('kb_withdrawal', 'nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $funds_table = $wpdb->prefix . 'kb_funds';
    $wd_table    = $wpdb->prefix . 'kb_withdrawals';
    $fund_id     = intval($_POST['fund_id']);
    $amount      = floatval($_POST['amount']);
    $business_id = get_current_user_id();

    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$funds_table} WHERE id=%d AND business_id=%d", $fund_id, $business_id));
    if (!$fund) { wp_send_json_error(['message' => 'Fund not found.']); }
    if ($amount > $fund->raised_amount) { wp_send_json_error(['message' => 'Amount exceeds available funds.']); }
    if ($fund->escrow_status !== 'released') { wp_send_json_error(['message' => 'Funds are still held in escrow. Please wait for admin review.']); }

    $result = $wpdb->insert($wd_table, [
        'rand_id'         => bntm_rand_id(),
        'fund_id'         => $fund_id,
        'amount'          => $amount,
        'method'          => sanitize_text_field($_POST['method']),
        'account_details' => sanitize_textarea_field($_POST['account_details']),
        'status'          => 'pending',
    ], ['%s','%d','%f','%s','%s','%s']);

    if ($result) {
        wp_send_json_success(['message' => 'Withdrawal request submitted! We will process it within 2-3 business days.']);
    } else {
        wp_send_json_error(['message' => 'Failed to submit withdrawal request.']);
    }
}

function bntm_ajax_kb_extend_deadline() {
    check_ajax_referer('kb_extend', 'nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table = $wpdb->prefix . 'kb_funds';
    $fund_id     = intval($_POST['fund_id']);
    $deadline    = sanitize_text_field($_POST['deadline']);
    $business_id = get_current_user_id();

    if (strtotime($deadline) <= time()) { wp_send_json_error(['message' => 'Deadline must be a future date.']); }

    $result = $wpdb->update($table, ['deadline' => $deadline], ['id' => $fund_id, 'business_id' => $business_id], ['%s'], ['%d','%d']);

    if ($result !== false) {
        wp_send_json_success(['message' => 'Deadline extended successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to extend deadline.']);
    }
}

// ============================================================
// AJAX HANDLERS — SPONSOR / PUBLIC
// ============================================================

function bntm_ajax_kb_sponsor_fund() {
    check_ajax_referer('kb_sponsor', 'nonce');

    global $wpdb;
    $funds_table = $wpdb->prefix . 'kb_funds';
    $spons_table = $wpdb->prefix . 'kb_sponsorships';

    $fund_id = intval($_POST['fund_id']);
    $amount  = floatval($_POST['amount']);

    if ($amount < 10) { wp_send_json_error(['message' => 'Minimum sponsorship is ₱10.']); }

    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$funds_table} WHERE id=%d AND status='active'", $fund_id));
    if (!$fund) { wp_send_json_error(['message' => 'Fund not found or not active.']); }

    $is_anonymous = intval($_POST['is_anonymous'] ?? 0);

    $result = $wpdb->insert($spons_table, [
        'rand_id'          => bntm_rand_id(),
        'fund_id'          => $fund_id,
        'sponsor_name'     => $is_anonymous ? 'Anonymous' : sanitize_text_field($_POST['sponsor_name'] ?? 'Anonymous'),
        'is_anonymous'     => $is_anonymous,
        'amount'           => $amount,
        'email'            => sanitize_email($_POST['email'] ?? ''),
        'phone'            => sanitize_text_field($_POST['phone'] ?? ''),
        'payment_method'   => sanitize_text_field($_POST['payment_method'] ?? ''),
        'payment_status'   => 'pending',
    ], ['%s','%d','%s','%d','%f','%s','%s','%s','%s']);

    if ($result) {
        // Update raised amount
        $wpdb->query($wpdb->prepare(
            "UPDATE {$funds_table} SET raised_amount = raised_amount + %f WHERE id = %d",
            $amount, $fund_id
        ));

        // Check if goal reached
        $updated_fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$funds_table} WHERE id=%d", $fund_id));
        if ($updated_fund->raised_amount >= $updated_fund->goal_amount) {
            $wpdb->update($funds_table, ['status' => 'completed'], ['id' => $fund_id], ['%s'], ['%d']);
        }

        wp_send_json_success(['message' => 'Thank you for your sponsorship! A confirmation will be sent to your contact details.']);
    } else {
        wp_send_json_error(['message' => 'Sponsorship failed. Please try again.']);
    }
}

function bntm_ajax_kb_report_fund() {
    check_ajax_referer('kb_report', 'nonce');

    global $wpdb;
    $table   = $wpdb->prefix . 'kb_reports';
    $fund_id = intval($_POST['fund_id']);
    $reason  = sanitize_text_field($_POST['reason']);
    $details = sanitize_textarea_field($_POST['details']);

    if (empty($reason) || empty($details)) { wp_send_json_error(['message' => 'Please fill all required fields.']); }

    $result = $wpdb->insert($table, [
        'rand_id'     => bntm_rand_id(),
        'fund_id'     => $fund_id,
        'reporter_id' => get_current_user_id(),
        'reason'      => $reason,
        'details'     => $details,
        'status'      => 'open',
    ], ['%s','%d','%d','%s','%s','%s']);

    if ($result) {
        wp_send_json_success(['message' => 'Report submitted. Our team will review it shortly.']);
    } else {
        wp_send_json_error(['message' => 'Failed to submit report.']);
    }
}

function bntm_ajax_kb_get_fund_details() {
    check_ajax_referer('kb_sponsor', 'nonce');

    global $wpdb;
    $table   = $wpdb->prefix . 'kb_funds';
    $fund_id = intval($_POST['fund_id']);
    $fund    = $wpdb->get_row($wpdb->prepare("SELECT id, title, description, goal_amount, raised_amount, location, category FROM {$table} WHERE id=%d AND status='active'", $fund_id));

    if ($fund) {
        wp_send_json_success($fund);
    } else {
        wp_send_json_error(['message' => 'Fund not found.']);
    }
}

// ============================================================
// AJAX HANDLERS — ADMIN
// ============================================================

function bntm_ajax_kb_admin_approve_fund() {
    check_ajax_referer('kb_admin_action');
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table   = $wpdb->prefix . 'kb_funds';
    $fund_id = intval($_POST['fund_id']);

    $wpdb->update($table, ['status' => 'active'], ['id' => $fund_id], ['%s'], ['%d']);
    wp_send_json_success(['message' => 'Fund approved and is now live!']);
}

function bntm_ajax_kb_admin_reject_fund() {
    check_ajax_referer('kb_admin_action');
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table   = $wpdb->prefix . 'kb_funds';
    $fund_id = intval($_POST['fund_id']);

    $wpdb->update($table, ['status' => 'cancelled'], ['id' => $fund_id], ['%s'], ['%d']);
    wp_send_json_success(['message' => 'Fund rejected.']);
}

function bntm_ajax_kb_admin_suspend() {
    check_ajax_referer('kb_admin_action');
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table   = $wpdb->prefix . 'kb_funds';
    $fund_id = intval($_POST['fund_id']);

    $wpdb->update($table, ['status' => 'suspended'], ['id' => $fund_id], ['%s'], ['%d']);
    wp_send_json_success(['message' => 'Fund suspended.']);
}

function bntm_ajax_kb_admin_verify_badge() {
    check_ajax_referer('kb_admin_action');
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table    = $wpdb->prefix . 'kb_funds';
    $fund_id  = intval($_POST['fund_id']);
    $verified = intval($_POST['verified']);

    $wpdb->update($table, ['verified_badge' => $verified], ['id' => $fund_id], ['%d'], ['%d']);
    wp_send_json_success(['message' => $verified ? 'Verified badge granted!' : 'Verified badge removed.']);
}

function bntm_ajax_kb_admin_release_escrow() {
    check_ajax_referer('kb_admin_action');
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table   = $wpdb->prefix . 'kb_funds';
    $fund_id = intval($_POST['fund_id']);

    $wpdb->update($table, ['escrow_status' => 'released'], ['id' => $fund_id], ['%s'], ['%d']);
    wp_send_json_success(['message' => 'Escrow released. Organizer can now withdraw funds.']);
}

function bntm_ajax_kb_admin_hold_escrow() {
    check_ajax_referer('kb_admin_action');
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table   = $wpdb->prefix . 'kb_funds';
    $fund_id = intval($_POST['fund_id']);

    $wpdb->update($table, ['escrow_status' => 'holding'], ['id' => $fund_id], ['%s'], ['%d']);
    wp_send_json_success(['message' => 'Funds placed on hold.']);
}

function bntm_ajax_kb_admin_dismiss_report() {
    check_ajax_referer('kb_admin_action');
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $table     = $wpdb->prefix . 'kb_reports';
    $report_id = intval($_POST['report_id']);

    $wpdb->update($table, ['status' => 'dismissed'], ['id' => $report_id], ['%s'], ['%d']);
    wp_send_json_success(['message' => 'Report dismissed.']);
}

function bntm_ajax_kb_admin_process_withdrawal() {
    check_ajax_referer('kb_admin_action');
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Unauthorized']); }

    global $wpdb;
    $wd_table     = $wpdb->prefix . 'kb_withdrawals';
    $funds_table  = $wpdb->prefix . 'kb_funds';
    $withdrawal_id = intval($_POST['withdrawal_id']);
    $action_type   = sanitize_text_field($_POST['action_type']);

    $wd = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wd_table} WHERE id=%d", $withdrawal_id));
    if (!$wd) { wp_send_json_error(['message' => 'Withdrawal not found.']); }

    if ($action_type === 'approve') {
        $wpdb->update($wd_table, [
            'status'       => 'released',
            'processed_at' => current_time('mysql'),
        ], ['id' => $withdrawal_id], ['%s', '%s'], ['%d']);

        // Deduct from fund
        $wpdb->query($wpdb->prepare(
            "UPDATE {$funds_table} SET raised_amount = raised_amount - %f WHERE id = %d",
            $wd->amount, $wd->fund_id
        ));

        wp_send_json_success(['message' => 'Withdrawal approved and released!']);
    } else {
        $wpdb->update($wd_table, [
            'status'       => 'rejected',
            'processed_at' => current_time('mysql'),
        ], ['id' => $withdrawal_id], ['%s', '%s'], ['%d']);

        wp_send_json_success(['message' => 'Withdrawal rejected.']);
    }
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function kb_refund_all_sponsors($fund_id) {
    global $wpdb;
    $spons_table = $wpdb->prefix . 'kb_sponsorships';
    $wpdb->update(
        $spons_table,
        ['payment_status' => 'refunded'],
        ['fund_id' => $fund_id, 'payment_status' => 'completed'],
        ['%s'],
        ['%d', '%s']
    );
    error_log("[KonekBayan] Auto-refund triggered for fund #{$fund_id}");
}

function kb_get_fund_stats($business_id) {
    global $wpdb;
    $funds_table = $wpdb->prefix . 'kb_funds';
    $spons_table = $wpdb->prefix . 'kb_sponsorships';

    return [
        'total_funds'    => (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$funds_table} WHERE business_id=%d", $business_id)),
        'active_funds'   => (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$funds_table} WHERE business_id=%d AND status='active'", $business_id)),
        'total_raised'   => (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(raised_amount),0) FROM {$funds_table} WHERE business_id=%d", $business_id)),
        'total_sponsors' => (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$spons_table} s JOIN {$funds_table} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",
            $business_id
        )),
    ];
}