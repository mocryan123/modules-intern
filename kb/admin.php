<?php
/*
 * KBF admin wrapper: loads admin UI and admin AJAX handlers.
 */

if (!defined('ABSPATH')) exit;

require_once(BNTM_KBF_PATH . 'admin/tabs/pending.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/all_funds.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/transactions.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/withdrawals.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/reports.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/appeals.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/organizers.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/settings.php');
require_once(BNTM_KBF_PATH . 'admin/ui.php');
require_once(BNTM_KBF_PATH . 'includes/ajax-admin.php');
