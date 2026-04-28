<?php
/*
 * Fundora public page: Under Maintenance
 */

if (!defined('ABSPATH')) {
    exit;
}

function bntm_kbf_render_maintenance() {
    ob_start();
    ?>
    <style>
      html, body{
        height:100% !important;
        min-height:100% !important;
        margin:0 !important;
        padding:0 !important;
        overflow:hidden !important;
      }
      #page, .site, .site-content, .content-area, .entry-content, .ast-container, .ast-container-fluid, .wp-site-blocks{
        height:100% !important;
        min-height:100% !important;
        margin:0 !important;
        padding:0 !important;
        overflow:hidden !important;
      }
      :root{
        --kbf-navy:#1f2a44;
        --kbf-blue:#3d8ef0;
        --kbf-slate:#64748b;
        --kbf-bg:#f8fafc;
        --kbf-border:#dbe4f0;
      }
      .kbf-maintenance, .kbf-maintenance body{
        font-family: 'Poppins', system-ui, -apple-system, sans-serif;
      }
      .kbf-maintenance{
        height:100vh;
        min-height:100vh;
        height:100dvh;
        min-height:100dvh;
        box-sizing:border-box;
        overflow:hidden;
        width:100%;
        max-width:100vw;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:24px 16px;
        background:var(--kbf-bg);
        color:var(--kbf-navy);
        text-align:center;
      }
      .kbf-maintenance-card{
        max-width:580px;
        width:100%;
        margin:0 auto;
        padding:0;
        border:none;
        border-radius:0;
        background:transparent;
      }
      .kbf-maintenance-title{
        font-size:42px;
        line-height:1.08;
        font-weight:600;
        letter-spacing:-0.02em;
        color:var(--kbf-navy);
        margin:12px 0 14px;
      }
      .kbf-maintenance-sub{
        font-size:15px;
        line-height:1.65;
        color:var(--kbf-slate);
        margin:0 auto;
        max-width:500px;
        font-weight:500;
      }
      .kbf-maintenance-illus{
        width:500px;
        max-width:100%;
        height:auto;
        margin:0 auto 10px;
        display:block;
      }
      .kbf-maintenance-line{
        width:72px;
        height:2px;
        border-radius:999px;
        background:linear-gradient(90deg,#3d8ef0 0%, #7fb6f7 100%);
        margin:16px auto 0;
      }
      @media (max-width:640px){
        .kbf-maintenance-title{font-size:34px;}
        .kbf-maintenance-sub{font-size:13.5px;}
        .kbf-maintenance-illus{width:100%;max-width:340px;}
      }
    </style>

    <section class="kbf-maintenance">
      <div class="kbf-maintenance-card">
        <img
          class="kbf-maintenance-illus"
          src="<?php echo esc_url(BNTM_KBF_URL . 'assets/illustrations/maintainance.png'); ?>"
          alt="System update illustration"
          loading="lazy"
        >
        <h1 class="kbf-maintenance-title">System Update</h1>
        <p class="kbf-maintenance-sub">
          The page you are trying to reach is temporarily unavailable.
          We are performing updates to improve your experience. Please check back soon.
        </p>
        <div class="kbf-maintenance-line" aria-hidden="true"></div>
      </div>
    </section>
    <?php
    return ob_get_clean();
}

