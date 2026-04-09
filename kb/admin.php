<?php
/*
 * KBF admin wrapper: loads admin UI and admin AJAX handlers.
 */

if (!defined('ABSPATH')) exit;

function kbf_admin_get_date_range() {
    $from = isset($_REQUEST['date_from']) ? sanitize_text_field($_REQUEST['date_from']) : '';
    $to = isset($_REQUEST['date_to']) ? sanitize_text_field($_REQUEST['date_to']) : '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $from = '';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $to = '';
    }
    return ['from' => $from, 'to' => $to];
}

function kbf_admin_date_where($column, &$params) {
    $range = kbf_admin_get_date_range();
    $sql = '';
    if (!empty($range['from'])) {
        $sql .= " AND {$column} >= %s";
        $params[] = $range['from'] . ' 00:00:00';
    }
    if (!empty($range['to'])) {
        $sql .= " AND {$column} <= %s";
        $params[] = $range['to'] . ' 23:59:59';
    }
    return $sql;
}

require_once(BNTM_KBF_PATH . 'admin/tabs/for_review.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/fundraisers.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/payments.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/cashouts.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/reports.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/appeals.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/accounts.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/settings.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/security_logs.php');
require_once(BNTM_KBF_PATH . 'admin/ui.php');
require_once(BNTM_KBF_PATH . 'includes/ajax-admin.php');

