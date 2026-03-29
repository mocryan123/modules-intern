<?php
/*
 * KBF shared preload screen (refresh/load).
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('kbf_render_preload_screen')) {
    function kbf_render_preload_screen($message = 'Loading...', $logo_text = 'BS') {
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;
        ob_start(); ?>
        <div id="kbf-preload" class="kbf-preload" aria-hidden="true">
          <style>
            .kbf-preload{
              position:fixed;
              inset:0;
              z-index:99999;
              display:flex;
              align-items:center;
              justify-content:center;
              background:rgba(248,250,252,0.78);
              backdrop-filter: blur(8px);
              transition:opacity .35s ease, visibility .35s ease;
              font-family:"Poppins", system-ui, -apple-system, sans-serif;
              color:#0f172a;
            }
            html.kbf-preload-lock,
            body.kbf-preload-lock{
              overflow:hidden !important;
              height:100%;
            }
            .kbf-preload.kbf-preload-hide{
              opacity:0;
              visibility:hidden;
              pointer-events:none;
            }
            .kbf-preload-mark{
              width:54px;
              height:54px;
              border-radius:14px;
              background:linear-gradient(135deg, #5ba8f5, #3d8ef0);
              display:inline-flex;
              align-items:center;
              justify-content:center;
              color:#ffffff;
              font-weight:800;
              letter-spacing:.6px;
              box-shadow:0 8px 18px rgba(61,142,240,.2);
              overflow:hidden;
              animation:kbfpreloadjump 1.2s cubic-bezier(.34,1.2,.64,1) infinite;
            }
            .kbf-preload-mark img{
              width:26px;height:26px;object-fit:contain;display:block;
              filter:brightness(0) invert(1);
            }
            @keyframes kbfpreloadjump{
              0%{transform:translateY(0) rotate(0deg) scale(1); box-shadow:0 8px 18px rgba(61,142,240,.2);}
              25%{transform:translateY(-14px) rotate(-8deg) scale(1.01); box-shadow:0 16px 28px rgba(61,142,240,.3);}
              50%{transform:translateY(2px) rotate(6deg) scale(.99); box-shadow:0 6px 14px rgba(61,142,240,.18);}
              75%{transform:translateY(-8px) rotate(-6deg) scale(1.005); box-shadow:0 12px 24px rgba(61,142,240,.26);}
              100%{transform:translateY(0) rotate(0deg) scale(1); box-shadow:0 8px 18px rgba(61,142,240,.2);}
            }
          </style>
          <div class="kbf-preload-mark">
            <?php if(defined('BNTM_KBF_URL')): ?>
              <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="">
            <?php else: ?>
              <?php echo esc_html($logo_text); ?>
            <?php endif; ?>
          </div>
        </div>
        <script>
        (function(){
          var root = document.documentElement;
          if (root) root.classList.add('kbf-preload-lock');
          if (document.body) document.body.classList.add('kbf-preload-lock');
        })();
        window.addEventListener('load', function(){
          var pre = document.getElementById('kbf-preload');
          if (!pre) return;
          var minTime = 3000;
          var elapsed = (window.performance && performance.timing)
            ? (Date.now() - performance.timing.navigationStart)
            : 0;
          var wait = Math.max(0, minTime - elapsed);
          setTimeout(function(){
            pre.classList.add('kbf-preload-hide');
            setTimeout(function(){
              document.documentElement.classList.remove('kbf-preload-lock');
              if (document.body) document.body.classList.remove('kbf-preload-lock');
              if (pre && pre.parentNode) pre.parentNode.removeChild(pre);
            }, 400);
          }, wait);
        });
        </script>
        <?php return ob_get_clean();
    }
}
