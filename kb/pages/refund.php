<?php
/*
 * Fundora public page: Refund Policy
 */

if (!defined('ABSPATH')) {
    exit;
}

function bntm_kbf_render_refund() {
    $landing_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('landing') : home_url('/');
    $login_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : wp_login_url();
    ob_start();
    ?>
    <!-- ================== CSS ================== -->
    <style>
      html{ scroll-behavior:smooth; }
      body{ background:#f6f7fb; }
      .kbf-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 12px 20px;
        min-height: 60px;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.86);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid transparent;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        margin: 0;
        width: 100%;
        box-sizing: border-box;
      }
      .kbf-topbar.kbf-topbar-scrolled {
        border-bottom-color: #edf0f4;
        box-shadow: 0 6px 28px rgba(15, 40, 80, 0.10);
      }
      .kbf-topbar-left { display: flex; align-items: center; gap: 28px; flex-wrap: wrap; }
      .kbf-brand { display: flex; align-items: center; gap: 10px; font-weight: 800; }
      .kbf-nav { display: flex !important; flex-direction: row; gap: 20px; font-size: 12.5px; color: #4f5a6b; }
      .kbf-nav a { position: relative; display:inline-flex; align-items:center; color:#4f5a6b; text-decoration:none; }
      .kbf-nav a::after {
        content: ''; position: absolute; left: 0; bottom: -8px;
        width: 0; height: 2px; border-radius: 999px;
        background: #6fb6ff; transition: width .2s ease;
      }
      .kbf-nav a:hover::after { width: 100%; }
      .kbf-actions { display: flex; gap: 10px; align-items: center; }
      .kbf-btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 10px 18px; border-radius: 999px;
        font-weight: 500; font-size: 12.5px;
        border: 1px solid transparent;
        transition: transform .3s ease, box-shadow .3s ease, background .3s ease, filter .3s ease;
        background:#fff;
        color:#1f2937;
        text-decoration:none;
      }
      .kbf-btn.kbf-btn-block{
        width:100%;
        box-sizing:border-box;
      }
      .kbf-btn.kbf-btn-primary {
        background: linear-gradient(135deg, #5ba8f5 0%, #3d8ef0 55%, #1f6fe0 100%);
        color: #ffffff;
        box-shadow:
          0 1px 2px rgba(32, 112, 224, 0.20),
          0 4px 14px rgba(42, 120, 220, 0.28),
          0 0 0 0px rgba(111, 182, 255, 0),
          inset 0 1px 0 rgba(255, 255, 255, 0.18);
        font-weight: 600;
        letter-spacing: 0.01em;
        position: relative;
        isolation: isolate;
      }
      .kbf-btn.kbf-btn-primary::before {
        content: '';
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        background: linear-gradient(135deg, #7ec4ff 0%, #5aaaf8 40%, #2878e8 100%);
        opacity: 0;
        z-index: -1;
        transition: opacity .3s ease;
      }
      .kbf-btn.kbf-btn-primary:visited,
      .kbf-btn.kbf-btn-primary:active { color: #ffffff; }
      .kbf-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow:
          0 1px 3px rgba(32, 112, 224, 0.15),
          0 8px 24px rgba(42, 120, 220, 0.45),
          0 16px 40px rgba(61, 142, 240, 0.20),
          inset 0 1px 0 rgba(255, 255, 255, 0.25);
        filter: brightness(1.06);
      }
      .kbf-btn-primary:hover::before { opacity: 1; }
      .kbf-btn-primary:active {
        transform: translateY(0px);
        box-shadow:
          0 1px 2px rgba(32, 112, 224, 0.30),
          0 2px 8px rgba(61, 142, 240, 0.28),
          0 0 0 2px rgba(111, 182, 255, 0.15),
          inset 0 1px 0 rgba(255, 255, 255, 0.12);
        filter: brightness(0.96);
        transition: transform .1s ease, box-shadow .1s ease, filter .1s ease;
      }
      .kbf-hamburger{
        display:none !important;
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:10px;
        padding:6px 8px;
        cursor:pointer;
        color:#64748b;
        align-items:center;
        justify-content:center;
      }
      .kbf-hamburger img{
        width:18px;height:18px;display:block;
        filter: invert(30%) sepia(10%) saturate(800%) hue-rotate(185deg) brightness(0.9);
      }
      .kbf-mobile-overlay{
        display:none;
        position:fixed;
        inset:0;
        background:#00000073;
        z-index:998;
      }
      .kbf-mobile-overlay.kbf-overlay-open{ display:block; }
      .kbf-mobile-menu{
        position:fixed;
        top:0;left:0;right:0;
        z-index:999;
        border-radius:0;
        margin-top:0;
        transform:translateY(-110%);
        transition:transform .3s cubic-bezier(.4,0,.2,1);
        display:flex;
        flex-direction:column;
        overflow:hidden;
        background:#fff;
        border:1px solid #e2e8f0;
        box-shadow:0 10px 26px rgba(15,40,80,.18);
      }
      .kbf-mobile-menu.kbf-menu-open{ transform:translateY(0); display:flex; }
      .kbf-mobile-menu-header{
        display:flex;align-items:center;justify-content:space-between;
        padding:14px 16px;border-bottom:1px solid #e2e8f0;background:#fff;
      }
      .kbf-mobile-menu a{
        padding:13px 18px;
        font-size:13.5px;
        color:#0f172a;
        border-bottom:1px solid #e2e8f0;
        text-align:center;
        text-decoration:none;
      }
      .kbf-mobile-menu a:last-of-type{ border-bottom:none; }
      .kbf-mobile-menu-actions{
        display:flex;flex-direction:row;gap:8px;
        padding:12px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;
      }
      .kbf-mobile-menu-actions .kbf-btn{ flex:1; justify-content:center; }
      .kbf-legal {
        max-width: 1040px;
        margin: 28px auto 60px;
        padding: 80px 20px 0;
        color: #0f172a;
        font-family: "Poppins", system-ui, -apple-system, sans-serif;
        overflow: visible;
      }
      .kbf-legal h1 {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 4px;
      }
      .kbf-legal .kbf-legal-sub {
        color: #64748b;
        font-size: 12.5px;
        margin: 0 0 18px;
      }
      .kbf-legal-grid{
        display:grid;
        grid-template-columns: 260px minmax(0, 1fr);
        gap: 18px;
        align-items: start;
      }
      .kbf-legal-card{
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:16px;
        padding:16px 18px;
        box-shadow:0 10px 24px rgba(15,23,42,.04);
        box-sizing:border-box;
      }
      .kbf-legal-nav{
        position:sticky;
        top:88px;
        align-self:start;
      }
      .kbf-legal-nav h4{
        margin:0 0 10px;
        font-size:12px;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#94a3b8;
      }
      .kbf-legal-nav .kbf-legal-link{
        display:block;
        padding:8px 10px;
        border-radius:10px;
        color:#1f2937;
        text-decoration:none;
        font-size:12.5px;
      }
      .kbf-legal-nav .kbf-legal-link:hover{
        background:#f1f5f9;
      }
      .kbf-legal h2{
        font-size:16px;
        margin:18px 0 8px;
        scroll-margin-top: 96px;
      }
      .kbf-legal h3{
        font-size:14px;
        margin:14px 0 6px;
      }
      .kbf-legal p,
      .kbf-legal li{
        font-size:13.5px;
        line-height:1.7;
        color:#475569;
      }
      .kbf-legal ul{ padding-left:18px; margin:8px 0; }
      .kbf-legal .kbf-legal-meta{
        font-size:12px;
        color:#94a3b8;
      }
      @media (max-width: 900px){
        .kbf-legal-grid{ grid-template-columns: 1fr; }
        .kbf-legal-nav{ position:static; }
        .kbf-nav{ display:none !important; }
        .kbf-actions{ display:none !important; }
        .kbf-hamburger{ display:inline-flex !important; }
        .kbf-topbar{ flex-wrap:nowrap; gap:12px; justify-content:space-between; padding:12px 20px; }
      }
      .kbf-footer {
        margin: 44px auto 40px;
        max-width: 1040px;
        background: #0c0f14;
        color: #b9c0cc;
        border-radius: 22px;
        padding: 20px 22px;
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 14px;
      }
      .kbf-footer-left{ display:flex; flex-direction:column; gap:6px; }
      .kbf-footer p { margin: 0; font-size: 12px; color: #b9c0cc; line-height:1.6; }
      .kbf-footer small { color: #8590a6; display:block; margin-top:6px; font-size:11.5px; }
      .kbf-footer .kbf-social { display: flex; gap: 8px; }
      .kbf-footer .kbf-social a {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255,255,255,.18);
        background: transparent;
      }
      .kbf-footer .kbf-social img { width: 16px; height: 16px; filter: invert(100%); }
      .kbf-footer .kbf-footer-links{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
        margin:0;
      }
      .kbf-footer .kbf-footer-links a{
        color:#cbd5f5;
        font-size:12px;
        text-decoration:none;
      }
      .kbf-footer .kbf-footer-links a:hover{ color:#fff; }
      @media (max-width: 720px){
        .kbf-footer{
          grid-template-columns: 1fr;
          text-align: left;
          margin: 18px auto;
        }
        .kbf-social{ justify-content:flex-start; }
      }
    </style>
    <!-- ================== HTML ================== -->
    <section class="kbf-legal">
      <div class="kbf-mobile-overlay" id="kbf-mobile-overlay"></div>
      <div class="kbf-topbar">
        <div class="kbf-topbar-left">
          <div class="kbf-brand">
            <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logobanner.png'); ?>" alt="fundora" style="width:auto;height:24px;object-fit:contain;border-radius:6px;">
          </div>
          <nav class="kbf-nav">
            <a href="<?php echo esc_url($landing_url); ?>#kbf-home">Home</a>
            <a href="<?php echo esc_url($landing_url); ?>#kbf-how">Features</a>
            <a href="<?php echo esc_url($landing_url); ?>#kbf-donation">About</a>
            <a href="<?php echo esc_url($landing_url); ?>#kbf-faq">FAQ</a>
          </nav>
        </div>
        <div class="kbf-actions">
          <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($login_url); ?>">Sign In to Start</a>
        </div>
        <button class="kbf-hamburger" id="kbf-hamburger-btn" aria-label="Open menu">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/list.svg" alt="Menu" id="kbf-hamburger-icon">
        </button>
      </div>
      <div class="kbf-mobile-menu" id="kbf-mobile-menu">
        <div class="kbf-mobile-menu-header">
          <div class="kbf-brand">
            <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora" style="width:24px;height:24px;object-fit:contain;border-radius:6px;">
            <span class="kbf-brand-text" style="font-weight:800;">fundora</span>
          </div>
          <button class="kbf-hamburger" type="button" id="kbf-hamburger-close" aria-label="Close menu" style="display:inline-flex;">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/x-lg.svg" alt="Close">
          </button>
        </div>
        <a href="<?php echo esc_url($landing_url); ?>#kbf-home">Home</a>
        <a href="<?php echo esc_url($landing_url); ?>#kbf-how">Features</a>
        <a href="<?php echo esc_url($landing_url); ?>#kbf-donation">About</a>
        <a href="<?php echo esc_url($landing_url); ?>#kbf-faq">FAQ</a>
        <div class="kbf-mobile-menu-actions">
          <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($login_url); ?>">Sign In to Start</a>
        </div>
      </div>
      <h1>Refund Policy</h1>
      <p class="kbf-legal-sub">FUNDORA &mdash; Refund Policy</p>
      <div class="kbf-legal-grid">
        <aside class="kbf-legal-card kbf-legal-nav">
          <h4>Sections</h4>
          <a class="kbf-legal-link" href="#overview">1. Overview</a>
          <a class="kbf-legal-link" href="#auto-return">2. Campaign Auto-Return Refunds</a>
          <a class="kbf-legal-link" href="#disputes">3. Dispute and Fraud Refunds</a>
          <a class="kbf-legal-link" href="#non-refundable">4. Non-Refundable Items</a>
          <a class="kbf-legal-link" href="#method">5. Refund Method</a>
          <a class="kbf-legal-link" href="#contact">6. Contact</a>
          <div style="margin-top:14px;">
            <a class="kbf-btn kbf-btn-primary kbf-btn-block" href="<?php echo esc_url(BNTM_KBF_URL . 'assets/legal/Fundora-Refund-Policy.docx'); ?>" download>Download PDF</a>
          </div>
        </aside>
        <div class="kbf-legal-card">
          <div class="kbf-legal-meta">Effective Date: Upon Public Launch &mdash; Maramag, Bukidnon, Philippines</div>

          <h2 id="overview">1. Overview</h2>
          <p>This Refund Policy outlines the conditions under which refunds are issued on the Fundora platform. By donating to a campaign or creating a campaign on Fundora, you agree to the terms described in this policy.</p>
          <p>Fundora acts as a platform connecting organizers and sponsors. All funds flow through our payment gateway provider. Refunds are subject to the capabilities and timelines of our payment processor.</p>

          <h2 id="auto-return">2. Campaign Auto-Return Refunds</h2>
          <h3>2.1 How Auto-Return Works</h3>
          <p>Each campaign on Fundora has an auto-return setting configured by the organizer at the time of campaign creation. This setting is displayed prominently on the campaign page so sponsors can make an informed decision before donating.</p>
          <h3>2.2 Auto-Return Enabled</h3>
          <p>If a campaign has auto-return enabled and does not reach its fundraising goal by the deadline:</p>
          <ul>
            <li>All sponsors will be refunded their full donation amount.</li>
            <li>Refunds will be processed within thirty (30) business days of the campaign expiry date.</li>
            <li>Refunds will be returned to the original payment method used at the time of donation.</li>
            <li>Payment gateway processing fees are non-refundable as they are charged by the payment provider.</li>
          </ul>
          <h3>2.3 Auto-Return Disabled</h3>
          <p>If a campaign has auto-return disabled:</p>
          <ul>
            <li>Sponsors acknowledge before donating that the organizer retains all funds raised regardless of whether the goal is met.</li>
            <li>No refunds will be issued under this setting unless fraud or misrepresentation is determined through Fundora's dispute resolution process.</li>
            <li>This setting is clearly communicated on the campaign page before any donation is made.</li>
          </ul>

          <h2 id="disputes">3. Dispute and Fraud Refunds</h2>
          <h3>3.1 Filing a Complaint</h3>
          <p>If a sponsor believes that an organizer has acted fraudulently, provided false information, or misused campaign funds, they may file a formal complaint through Fundora's support channel. The complaint must include:</p>
          <ul>
            <li>The name of the campaign and the organizer.</li>
            <li>The amount donated and the date of donation.</li>
            <li>A clear description of the alleged fraud or misrepresentation.</li>
            <li>Any supporting evidence available.</li>
          </ul>
          <h3>3.2 Investigation Process</h3>
          <p>Upon receiving a valid complaint, Fundora will:</p>
          <ul>
            <li>Acknowledge receipt of the complaint within three (3) business days.</li>
            <li>Review all available evidence including campaign content, organizer communications, and transaction records.</li>
            <li>Contact the organizer for a response.</li>
            <li>Make a determination based on the evidence presented.</li>
            <li>Notify both parties of the outcome.</li>
          </ul>
          <h3>3.3 Refund Outcome</h3>
          <p>If Fundora determines that fraud or misrepresentation has occurred:</p>
          <ul>
            <li>Affected sponsors will receive a refund of their donation amount.</li>
            <li>Dispute refunds will be processed within fourteen (14) business days of the resolution of the investigation.</li>
            <li>Payment gateway processing fees are non-refundable.</li>
          </ul>
          <p>If Fundora determines that no fraud has occurred, no refund will be issued under the dispute process. Sponsors retain the right to pursue remedies through appropriate Philippine legal channels.</p>

          <h2 id="non-refundable">4. Non-Refundable Items</h2>
          <p>The following are non-refundable under all circumstances:</p>
          <ul>
            <li>Payment gateway processing fees charged at the time of donation.</li>
            <li>Platform fees (5%) deducted from organizer withdrawals.</li>
            <li>Donations to campaigns with auto-return disabled, unless fraud is determined.</li>
            <li>Donations to campaigns where funds have already been withdrawn, unless fraud is determined.</li>
          </ul>

          <h2 id="method">5. Refund Method</h2>
          <p>All refunds will be returned to the original payment method used at the time of donation. Fundora does not issue refunds via alternative payment methods or cash. Refund timelines may vary depending on the sponsor's bank or e-wallet provider after processing on Fundora's end.</p>

          <h2 id="contact">6. Contact</h2>
          <p>For refund-related questions or to file a complaint, please contact Fundora through our official support channel on the platform or via our official Facebook page.</p>
          <p>Fundora | Maramag, Bukidnon, Philippines</p>
        </div>
      </div>
    </section>
    <footer class="kbf-footer">
      <div class="kbf-footer-left">
        <div class="kbf-brand" style="margin-bottom:8px;">
          <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora" style="width:20px;height:20px;object-fit:contain;border-radius:6px;">
          <span class="kbf-brand-text" style="font-weight: 600;">fundora</span>
        </div>
        <p>Community fundraising rooted in bayanihan.</p>
        <div class="kbf-footer-links">
          <a href="<?php echo esc_url(kbf_get_page_url('privacy')); ?>">Privacy Policy</a>
          <a href="<?php echo esc_url(kbf_get_page_url('terms')); ?>">Terms of Service</a>
          <a href="<?php echo esc_url(kbf_get_page_url('refund')); ?>">Refund Policy</a>
        </div>
        <small>&copy; fundora. All rights reserved.</small>
      </div>
      <div class="kbf-social">
        <a href="https://www.instagram.com/bntmtechnologiesinc/" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/instagram.svg" alt="">
        </a>
        <a href="https://www.facebook.com/bentamosabentamo" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/facebook.svg" alt="">
        </a>
        <a href="https://www.linkedin.com/company/bentamo/" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/linkedin.svg" alt="">
        </a>
      </div>
    </footer>
    <script>
      (function(){
        var btn = document.getElementById('kbf-hamburger-btn');
        var closeBtn = document.getElementById('kbf-hamburger-close');
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        var icon = document.getElementById('kbf-hamburger-icon');
        if (!btn || !menu || !overlay) return;
        var open = false;
        function openMenu(){
          open = true;
          menu.classList.add('kbf-menu-open');
          overlay.classList.add('kbf-overlay-open');
          if (icon) icon.src = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/x-lg.svg';
        }
        function closeMenu(){
          open = false;
          menu.classList.remove('kbf-menu-open');
          overlay.classList.remove('kbf-overlay-open');
          if (icon) icon.src = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/list.svg';
        }
        btn.addEventListener('click', function(){
          if (open) closeMenu();
          else openMenu();
        });
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        if (overlay) overlay.addEventListener('click', closeMenu);
        window.addEventListener('resize', function(){
          if (window.innerWidth > 900 && open) closeMenu();
        });
      })();

      (function() {
        var topbar = document.querySelector('.kbf-topbar');
        if (!topbar) return;
        window.addEventListener('scroll', function() {
          if (window.scrollY > 10) {
            topbar.classList.add('kbf-topbar-scrolled');
          } else {
            topbar.classList.remove('kbf-topbar-scrolled');
          }
        }, { passive: true });
      })();

      (function(){
        var links = document.querySelectorAll('.kbf-legal-nav a[href^="#"]');
        if (!links.length) return;
        links.forEach(function(link){
          link.addEventListener('click', function(e){
            var id = link.getAttribute('href');
            if (!id) return;
            var target = document.querySelector(id);
            if (!target) return;
            e.preventDefault();
            var topbar = document.querySelector('.kbf-topbar');
            var offset = topbar ? (topbar.offsetHeight + 20) : 80;
            var y = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top: y, behavior: 'smooth' });
          });
        });
      })();
    </script>
    <?php
    return ob_get_clean();
}
