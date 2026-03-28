<?php
if (!defined('ABSPATH')) exit;

// ============================================================
// CRON
// ============================================================

add_action('kbf_check_deadlines', 'kbf_cron_check_deadlines');
if (!wp_next_scheduled('kbf_check_deadlines')) {
    wp_schedule_event(time(), 'hourly', 'kbf_check_deadlines');
}

function kbf_cron_check_deadlines() {
    global $wpdb;
    $table = $wpdb->prefix . 'kbf_funds';
    $expired = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE status='active' AND deadline IS NOT NULL AND deadline < %s",
        current_time('Y-m-d')
    ));
    foreach ($expired as $fund) {
        if ($fund->raised_amount >= $fund->goal_amount) {
            $wpdb->update($table, ['status' => 'completed'], ['id' => $fund->id], ['%s'], ['%d']);
        } elseif ($fund->auto_return) {
            kbf_refund_all_sponsors($fund->id);
            $wpdb->update($table, ['status' => 'cancelled', 'escrow_status' => 'refunded'], ['id' => $fund->id], ['%s','%s'], ['%d']);
        } else {
            $wpdb->update($table, ['status' => 'completed'], ['id' => $fund->id], ['%s'], ['%d']);
        }
        // =====================================================
        // NOTIFICATION PLACEHOLDER
        // TODO: Hook your 3rd-party notification service here
        // Example: do_action('kbf_fund_deadline_reached', $fund);
        // =====================================================
    }
}

