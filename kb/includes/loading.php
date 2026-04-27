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
        <div id="kbf-loading-overlay" aria-hidden="true" style="position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:#fff;overflow:hidden;">
          <div class="kbf-loading-mark" style="position:relative;width:54px;height:54px;display:flex;align-items:center;justify-content:center;">
            <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora" style="width:26px;height:26px;object-fit:contain;display:block;">
          </div>
        </div>
        <script>
          (function(){
            var finishLoader = function(){
              var el = document.getElementById('kbf-loading-overlay');
              var legacy = document.getElementById('bntmLoadingOverlay');
              if (legacy) { legacy.classList.add('hidden'); legacy.style.display = 'none'; }
              if (!el) return;
              setTimeout(function(){
                el.classList.add('is-ready');
                setTimeout(function(){
                  el.style.display = 'none';
                }, 380);
              }, 600);
            };
            if (document.readyState === 'complete') {
              finishLoader();
            } else {
              window.addEventListener('load', finishLoader);
            }
          })();
        </script>
        <?php
        return ob_get_clean();
    }
}

