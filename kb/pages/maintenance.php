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
      :root{
        --kbf-navy:#1a3a66;
        --kbf-blue:#3d8ef0;
        --kbf-slate:#64748b;
        --kbf-bg:#f8fafc;
        --kbf-border:#e2e8f0;
      }
      .kbf-maintenance, .kbf-maintenance body{
        font-family: 'Poppins', system-ui, -apple-system, sans-serif;
      }
      .kbf-maintenance{
        height:100vh;
        height:100dvh;
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:24px 18px;
        background:var(--kbf-bg);
        color:var(--kbf-navy);
        text-align:center;
      }
      .kbf-maintenance-card{
        max-width:640px;
        width:100%;
        margin:0 auto;
        padding:24px 20px;
        border:1px solid var(--kbf-border);
        border-radius:16px;
        background:#fff;
      }
      .kbf-maintenance-title{
        font-size:34px;
        line-height:1.18;
        font-weight:700;
        letter-spacing:-0.02em;
        color:var(--kbf-navy);
        margin:12px 0 10px;
      }
      .kbf-maintenance-sub{
        font-size:14px;
        line-height:1.7;
        color:var(--kbf-slate);
        margin:0 auto;
        max-width:540px;
        font-weight:500;
      }
      .kbf-maintenance-illus{
        width:220px;
        height:auto;
        margin:0 auto;
        display:block;
      }
      @media (max-width:640px){
        .kbf-maintenance-card{padding:20px 16px;}
        .kbf-maintenance-title{font-size:28px;}
        .kbf-maintenance-sub{font-size:13px;}
        .kbf-maintenance-illus{width:190px;}
      }
    </style>

    <section class="kbf-maintenance">
      <div class="kbf-maintenance-card">
        <svg class="kbf-maintenance-illus" viewBox="0 0 320 260" aria-hidden="true">
          <defs>
            <linearGradient id="kbfMtnGrass" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="#bbf7d0"/>
              <stop offset="1" stop-color="#86efac"/>
            </linearGradient>
          </defs>
          <g fill="none" stroke="#14532d" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M46 70c8-6 18-6 26 0"/>
            <path d="M252 70c8-6 18-6 26 0"/>
            <path d="M118 48c8-6 18-6 26 0"/>
          </g>
          <circle cx="240" cy="40" r="10" fill="#d9f99d"/>
          <ellipse cx="160" cy="222" rx="90" ry="28" fill="url(#kbfMtnGrass)"/>
          <g>
            <circle cx="160" cy="82" r="24" fill="#e2e8f0" stroke="#0f172a" stroke-width="3"/>
            <rect x="142" y="96" width="36" height="70" rx="10" fill="#16a34a" stroke="#0f172a" stroke-width="3"/>
            <rect x="134" y="118" width="52" height="58" rx="10" fill="#15803d" stroke="#0f172a" stroke-width="3"/>
            <rect x="138" y="176" width="20" height="40" rx="8" fill="#0f172a"/>
            <rect x="162" y="176" width="20" height="40" rx="8" fill="#0f172a"/>
            <rect x="126" y="112" width="16" height="36" rx="8" fill="#e2e8f0" stroke="#0f172a" stroke-width="3"/>
            <rect x="178" y="112" width="16" height="36" rx="8" fill="#e2e8f0" stroke="#0f172a" stroke-width="3"/>
            <rect x="116" y="110" width="28" height="18" rx="6" fill="#ffffff" stroke="#0f172a" stroke-width="3"/>
            <path d="M120 116h20" stroke="#0f172a" stroke-width="3"/>
            <polygon points="105,70 145,60 145,110 105,110" fill="#ffffff" stroke="#0f172a" stroke-width="3"/>
            <path d="M118 78h14" stroke="#0f172a" stroke-width="3"/>
          </g>
          <g fill="#22c55e">
            <circle cx="96" cy="198" r="6"/>
            <circle cx="226" cy="198" r="6"/>
            <circle cx="206" cy="208" r="5"/>
            <circle cx="116" cy="210" r="5"/>
          </g>
          <g fill="none" stroke="#166534" stroke-width="4" stroke-linecap="round">
            <path d="M120 228c8-8 16-8 24 0"/>
            <path d="M196 228c8-8 16-8 24 0"/>
          </g>
        </svg>
        <h1 class="kbf-maintenance-title">System Update</h1>
        <p class="kbf-maintenance-sub">
          The page you are trying to reach is temporarily unavailable.
          We are performing updates to improve your experience. Please check back soon.
        </p>
      </div>
    </section>
    <?php
    return ob_get_clean();
}

