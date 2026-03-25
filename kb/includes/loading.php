<?php
/*
 * KBF shared loading overlay markup.
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('kbf_render_loading_overlay')) {
    function kbf_render_loading_overlay($message = 'Loading...', $logo_text = 'KB') {
        ob_start(); ?>
        <div id="kbf-loading-overlay" class="kbf-loading-overlay" style="display:none;">
          <div class="kbf-loading-card">
            <?php if(!empty($logo_text)): ?>
              <div class="kbf-loading-logo"><?php echo esc_html($logo_text); ?></div>
            <?php endif; ?>
            <div class="kbf-loading-spinner"></div>
            <div style="font-size:13px;color:#4f5a6b;"><?php echo esc_html($message); ?></div>
          </div>
        </div>
        <?php return ob_get_clean();
    }
}
