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
          (function(){
            var html = document.documentElement;
            var body = document.body;
            var locked = false;
            var isScrollable = function(){
              var doc = document.documentElement;
              var scrollHeight = (doc && doc.scrollHeight) || document.body.scrollHeight || 0;
              var clientHeight = (doc && doc.clientHeight) || window.innerHeight || 0;
              return scrollHeight > clientHeight;
            };
            var hasScrolled = function(){
              return (window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0) > 0;
            };
            if (!isScrollable() && !hasScrolled()) {
              if (html) html.style.overflow = 'hidden';
              if (body) body.style.overflow = 'hidden';
              locked = true;
            }
            window.addEventListener('load', function(){
              var el = document.getElementById('kbf-loading-overlay');
              var legacy = document.getElementById('bntmLoadingOverlay');
              if (legacy) { legacy.classList.add('hidden'); legacy.style.display = 'none'; }
              if (!el) return;
              setTimeout(function(){
                el.style.display = 'none';
                if (locked) {
                  if (html) html.style.overflow = '';
                  if (body) body.style.overflow = '';
                }
              }, 600);
            });
          })();
        </script>
        <?php
        return ob_get_clean();
    }
}

