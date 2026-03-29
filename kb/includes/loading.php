<?php
/*
 * KBF shared loading overlay markup.
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('kbf_render_loading_overlay')) {
    function kbf_render_loading_overlay($message = 'Loading...', $logo_text = 'BS') {
        // Overlay loader removed. Keep function for compatibility, but render nothing.
        return '';
    }
}
