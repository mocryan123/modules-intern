<?php
/*
 * KBF user UI rendering and shortcode output.
 */

if (!defined('ABSPATH')) exit;

// ================== PARTIALS ==================
$partials = [
    __DIR__ . '/partials/dashboard.php',
    __DIR__ . '/partials/admin_embed.php',
    __DIR__ . '/partials/browse.php',
    __DIR__ . '/partials/sponsor_history.php',
    __DIR__ . '/partials/fund_details.php',
    __DIR__ . '/partials/account_profile.php',
    dirname(__DIR__) . '/includes/preload.php',
];
foreach ($partials as $partial) {
    require_once $partial;
}

