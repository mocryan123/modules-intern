<?php
/*
 * KBF shared loading overlay markup.
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('kbf_render_loading_overlay')) {
    function kbf_render_loading_overlay() {
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;
        ob_start(); ?>
        <div id="kbf-loading-overlay" aria-hidden="true">
          <div class="kbf-loading-mark">
            <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora">
          </div>
        </div>
        <script>
          window.addEventListener('load', function(){
            var el = document.getElementById('kbf-loading-overlay');
            if (!el) return;
            setTimeout(function(){ el.style.display = 'none'; }, 600);
          });
        </script>
        <?php
        return ob_get_clean();
    }
}
