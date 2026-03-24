<?php
/*
 * KBF user wrapper: loads user UI, payments, and user AJAX handlers.
 */

if (!defined('ABSPATH')) exit;

require_once(BNTM_KBF_PATH . 'user/tabs/overview.php');
require_once(BNTM_KBF_PATH . 'user/tabs/my_funds.php');
require_once(BNTM_KBF_PATH . 'user/tabs/sponsorships.php');
require_once(BNTM_KBF_PATH . 'user/tabs/withdrawals.php');
require_once(BNTM_KBF_PATH . 'user/tabs/profile.php');
require_once(BNTM_KBF_PATH . 'user/tabs/find_funds.php');
require_once(BNTM_KBF_PATH . 'user/ui.php');
require_once(BNTM_KBF_PATH . 'includes/payments.php');
require_once(BNTM_KBF_PATH . 'includes/ajax-user.php');
