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
            .kbf-preload.kbf-preload-hide{
              opacity:0;
              visibility:hidden;
              pointer-events:none;
            }
            .kbf-preload-card{
              width:min(320px, 84vw);
              background:#ffffff;
              border:1px solid #edf0f4;
              border-radius:18px;
              padding:20px 22px;
              box-shadow:0 14px 36px rgba(15,40,80,0.12);
              display:flex;
              flex-direction:column;
              align-items:center;
              gap:8px;
              text-align:center;
            }
            .kbf-preload-mark{
              width:48px;
              height:48px;
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
            }
            .kbf-preload-mark img{
              width:26px;height:26px;object-fit:contain;display:block;
              filter:brightness(0) invert(1);
            }
            .kbf-preload-title{
              font-size:13px;
              font-weight:700;
              color:#0f172a;
              letter-spacing:.4px;
            }
            .kbf-preload-spinner{
              width:36px;
              height:36px;
              border-radius:50%;
              border:3px solid #e6edf7;
              border-top-color:#3d8ef0;
              animation:kbfpreloadspin 1s linear infinite;
            }
            .kbf-preload-msg{
              font-size:12px;
              color:#6f7785;
            }
            .kbf-preload-dots::after{
              content:'';
              display:inline-block;
              width:16px;
              text-align:left;
              animation:kbfpreloaddots 1.2s steps(4,end) infinite;
            }
            @keyframes kbfpreloadspin{to{transform:rotate(360deg);}}
            @keyframes kbfpreloaddots{
              0%{content:'';}
              25%{content:'.';}
              50%{content:'..';}
              75%{content:'...';}
              100%{content:'';}
            }
          </style>
          <div class="kbf-preload-card">
            <div class="kbf-preload-mark">
              <?php if(defined('BNTM_KBF_URL')): ?>
                <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="">
              <?php else: ?>
                <?php echo esc_html($logo_text); ?>
              <?php endif; ?>
            </div>
            <div class="kbf-preload-title">ambag</div>
            <div class="kbf-preload-spinner"></div>
            <div class="kbf-preload-msg kbf-preload-dots"><?php echo esc_html($message); ?></div>
          </div>
        </div>
        <script>
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
              if (pre && pre.parentNode) pre.parentNode.removeChild(pre);
            }, 400);
          }, wait);
        });
        </script>
        <?php return ob_get_clean();
    }
}
