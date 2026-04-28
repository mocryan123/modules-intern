<?php
if (!defined('ABSPATH')) exit;

// ============================================================
// CRON
// ============================================================

add_filter('cron_schedules', 'kbf_register_cron_intervals');
function kbf_register_cron_intervals($schedules) {
    if (!isset($schedules['kbf_every_5_minutes'])) {
        $schedules['kbf_every_5_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => 'Every 5 Minutes (KBF)',
        ];
    }
    return $schedules;
}

add_action('kbf_check_deadlines', 'kbf_cron_check_deadlines');
if (!wp_next_scheduled('kbf_check_deadlines')) {
    wp_schedule_event(time(), 'hourly', 'kbf_check_deadlines');
}

add_action('kbf_cleanup_pending_sponsorships', 'kbf_cron_cleanup_pending_sponsorships');
if (!wp_next_scheduled('kbf_cleanup_pending_sponsorships')) {
    wp_schedule_event(time(), 'hourly', 'kbf_cleanup_pending_sponsorships');
}

add_action('kbf_reconcile_pending_payments', 'kbf_cron_reconcile_pending_payments');
if (!wp_next_scheduled('kbf_reconcile_pending_payments')) {
    wp_schedule_event(time(), 'kbf_every_5_minutes', 'kbf_reconcile_pending_payments');
}

function kbf_cron_check_deadlines() {
    global $wpdb;
    $table = $wpdb->prefix . 'kbf_funds';
    $today = current_time('Y-m-d');
    $in_three_days = date('Y-m-d', strtotime('+3 days', current_time('timestamp')));

    if (function_exists('kbf_push_user_notification')) {
        $upcoming = $wpdb->get_results($wpdb->prepare(
            "SELECT id,business_id,title,deadline FROM {$table} WHERE status='active' AND deadline IS NOT NULL AND DATE(deadline) >= %s AND DATE(deadline) <= %s",
            $today,
            $in_three_days
        ));
        if (!empty($upcoming)) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            $today_ts = strtotime($today . ' 00:00:00');
            foreach ($upcoming as $fund) {
                $deadline_ts = strtotime((string)$fund->deadline . ' 00:00:00');
                if (!$deadline_ts || $deadline_ts <= $today_ts) {
                    continue;
                }
                $days_left = (int)ceil(($deadline_ts - $today_ts) / DAY_IN_SECONDS);
                if ($days_left !== 3 && $days_left !== 1) {
                    continue;
                }
                kbf_push_user_notification((int)$fund->business_id, [
                    'type' => 'fund_deadline_soon',
                    'title' => 'Campaign deadline is near',
                    'message' => $days_left === 1
                        ? 'Your campaign deadline is tomorrow.'
                        : 'Your campaign deadline is in 3 days.',
                    'url' => add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$fund->id], $dashboard_url),
                    'target_id' => (string)((int)$fund->id . '_' . $days_left . 'd'),
                    'dedupe_window' => $days_left === 1 ? 172800 : 345600,
                ]);
            }
        }
    }

    $expired = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE status='active' AND deadline IS NOT NULL AND DATE(deadline) < %s",
        $today
    ));
    foreach ($expired as $fund) {
        if ($fund->raised_amount >= $fund->goal_amount) {
            $wpdb->update($table, ['status' => 'completed', 'escrow_status' => 'released'], ['id' => $fund->id], ['%s','%s'], ['%d']);
        } elseif ($fund->auto_return) {
            // Auto-refund disabled.
        } else {
            $wpdb->update($table, ['status' => 'completed', 'escrow_status' => 'released'], ['id' => $fund->id], ['%s','%s'], ['%d']);
        }
        // =====================================================
        // NOTIFICATION PLACEHOLDER
        // TODO: Hook your 3rd-party notification service here
        // Example: do_action('kbf_fund_deadline_reached', $fund);
        // =====================================================
    }
}

function kbf_cron_cleanup_pending_sponsorships() {
    if (function_exists('kbf_cleanup_stale_pending_sponsorships')) {
        kbf_cleanup_stale_pending_sponsorships();
    }
}

function kbf_cron_reconcile_pending_payments() {
    if (function_exists('kbf_reconcile_pending_sponsorships')) {
        kbf_reconcile_pending_sponsorships(20, 2, 48);
    }
}

