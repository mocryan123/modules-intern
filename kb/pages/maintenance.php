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
        --kbf-ink:#0f172a;
        --kbf-muted:#64748b;
        --kbf-green:#166534;
        --kbf-green-2:#22c55e;
        --kbf-green-3:#bbf7d0;
      }
      .kbf-maintenance, .kbf-maintenance body{
        font-family: 'Outfit', system-ui, -apple-system, sans-serif;
      }
      .kbf-maintenance{
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:40px 20px;
        background:#ffffff;
        color:var(--kbf-ink);
        text-align:center;
      }
      .kbf-maintenance-card{
        max-width:720px;
        width:100%;
        margin:0 auto;
      }
      .kbf-maintenance-title{
        font-size:28px;
        font-weight:600;
        color:var(--kbf-green);
        margin:18px 0 8px;
      }
      .kbf-maintenance-sub{
        font-size:14px;
        line-height:1.6;
        color:var(--kbf-muted);
        margin:0 auto;
        max-width:520px;
      }
      .kbf-maintenance-illus{
        width:260px;
        height:auto;
        margin:0 auto;
        display:block;
      }
      @media (max-width:640px){
        .kbf-maintenance-title{font-size:24px;}
        .kbf-maintenance-sub{font-size:13.5px;}
        .kbf-maintenance-illus{width:220px;}
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
        <h1 class="kbf-maintenance-title">Fundora: Maintainance</h1>
        <p class="kbf-maintenance-sub">
          The page you are trying to reach is temporarily unavailable.
          We are performing updates to improve your experience. Please check back soon.
        </p>
      </div>
    </section>
    <?php
    return ob_get_clean();
}
