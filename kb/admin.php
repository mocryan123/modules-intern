<?php
/*
 * KBF admin wrapper: loads admin UI and admin AJAX handlers.
 */

if (!defined('ABSPATH')) exit;

require_once(BNTM_KBF_PATH . 'admin/tabs/for_review.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/fundraisers.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/payments.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/cashouts.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/reports.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/appeals.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/accounts.php');
require_once(BNTM_KBF_PATH . 'admin/tabs/settings.php');
require_once(BNTM_KBF_PATH . 'admin/ui.php');
require_once(BNTM_KBF_PATH . 'includes/ajax-admin.php');

