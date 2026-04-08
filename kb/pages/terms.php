<?php
/*
 * Fundora public page: Terms of Service
 */

if (!defined('ABSPATH')) {
    exit;
}

function bntm_kbf_render_terms() {
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
        padding: 0;
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
      .kbf-container {
        overflow: visible;
        max-width: 1120px;
        margin: 0 auto;
        padding: 62px 22px 0;
      }
      @media (max-width: 720px){
        .kbf-footer{
          grid-template-columns: 1fr;
          text-align: center;
          margin: 18px auto;
        }
        .kbf-footer-left{ align-items:center; }
        .kbf-footer .kbf-footer-links{ justify-content:center; }
        .kbf-social{ justify-content:center; }
        .kbf-brand{ justify-content:center; }
      }
    </style>
    <!-- ================== HTML ================== -->
    <section class="kbf-legal">
      <div class="kbf-mobile-overlay" id="kbf-mobile-overlay"></div>
      <div class="kbf-container">
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
      <h1>Terms of Service</h1>
      <p class="kbf-legal-sub">FUNDORA &mdash; Terms of Service</p>
      <div class="kbf-legal-grid">
        <aside class="kbf-legal-card kbf-legal-nav">
          <h4>Sections</h4>
          <a class="kbf-legal-link" href="#intro">1. Introduction and Acceptance</a>
          <a class="kbf-legal-link" href="#eligibility">2. Eligibility</a>
          <a class="kbf-legal-link" href="#accounts">3. Account Registration and Security</a>
          <a class="kbf-legal-link" href="#verification">4. Identity Verification</a>
          <a class="kbf-legal-link" href="#rules">5. Campaign Rules and Prohibited Content</a>
          <a class="kbf-legal-link" href="#fees">6. Fees and Payment Processing</a>
          <a class="kbf-legal-link" href="#refunds">7. Refunds and Failed Campaigns</a>
          <a class="kbf-legal-link" href="#organizers">8. Organizer Responsibilities</a>
          <a class="kbf-legal-link" href="#sponsors">9. Sponsor Responsibilities</a>
          <a class="kbf-legal-link" href="#disputes">10. Dispute Resolution</a>
          <a class="kbf-legal-link" href="#liability">11. Limitation of Liability</a>
          <a class="kbf-legal-link" href="#termination">12. Termination</a>
          <a class="kbf-legal-link" href="#law">13. Governing Law</a>
          <a class="kbf-legal-link" href="#changes">14. Changes to These Terms</a>
          <a class="kbf-legal-link" href="#contact">15. Contact</a>
          <div style="margin-top:14px;">
            <a class="kbf-btn kbf-btn-primary kbf-btn-block" href="<?php echo esc_url(BNTM_KBF_URL . 'assets/legal/Fundora-Terms-of-Service.docx'); ?>" download>Download PDF</a>
          </div>
        </aside>
        <div class="kbf-legal-card">
          <div class="kbf-legal-meta">Effective Date: Upon Public Launch &mdash; Maramag, Bukidnon, Philippines</div>

          <h2 id="intro">1. Introduction and Acceptance</h2>
          <p>Welcome to Fundora, a community crowdfunding platform operated by Fundora, co-founded and based in Maramag, Bukidnon, Philippines. By accessing or using the Fundora platform, you agree to be legally bound by these Terms of Service. Please read them carefully before registering an account or using any features of the platform.</p>
          <p>If you do not agree to these Terms, you must not use the platform. These Terms govern your use of the Fundora website, mobile interface, and all associated services.</p>

          <h2 id="eligibility">2. Eligibility</h2>
          <p>To use Fundora, you must satisfy all of the following requirements:</p>
          <ul>
            <li>You must be at least eighteen (18) years of age.</li>
            <li>You must be a Filipino citizen or a resident of the Philippines.</li>
            <li>You must provide accurate, complete, and up-to-date information during registration.</li>
            <li>You must not have been previously suspended or removed from the Fundora platform.</li>
            <li>You must have the legal capacity to enter into a binding agreement under Philippine law.</li>
          </ul>
          <p>By creating an account, you represent and warrant that you meet all eligibility requirements above.</p>

          <h2 id="accounts">3. Account Registration and Security</h2>
          <p>When registering for a Fundora account, you agree to:</p>
          <ul>
            <li>Provide truthful and accurate personal information including your full name, email address, phone number, and address.</li>
            <li>Keep your account credentials confidential and not share your password with any third party.</li>
            <li>Notify Fundora immediately if you suspect unauthorized access to your account.</li>
            <li>Accept sole responsibility for all activities that occur under your account.</li>
          </ul>
          <p>Fundora reserves the right to suspend or terminate accounts containing false, inaccurate, or misleading information.</p>

          <h2 id="verification">4. Identity Verification</h2>
          <p>Campaign organizers are required to complete identity verification through our third-party KYC (Know Your Customer) provider before launching a fundraising campaign. This process requires submission of a valid government-issued identification document and biometric verification.</p>
          <p>Identity verification is used to reduce fraud, protect sponsors, and maintain the integrity of campaigns on the platform. Verification status is displayed on your public account profile.</p>
          <p>Fundora does not store identity documents directly. They are processed and retained according to our KYC provider's data retention and privacy policies.</p>

          <h2 id="rules">5. Campaign Rules and Prohibited Content</h2>
          <h3>5.1 Permitted Campaigns</h3>
          <p>Fundora is designed for legitimate community fundraising including but not limited to medical expenses, disaster relief, educational needs, community projects, and personal emergencies.</p>
          <h3>5.2 Prohibited Campaigns</h3>
          <p>The following types of campaigns are strictly prohibited on Fundora:</p>
          <ul>
            <li>Adult content, sexually explicit material, or content of a pornographic nature.</li>
            <li>Gambling, betting, or any games of chance.</li>
            <li>Any activity that is illegal under the laws of the Republic of the Philippines.</li>
            <li>Campaigns intended to deceive or defraud sponsors.</li>
            <li>Campaigns that promote hate speech, discrimination, or violence.</li>
            <li>Campaigns that violate the intellectual property rights of any person or entity.</li>
          </ul>
          <p>Fundora reserves the right to remove any campaign that violates these rules at any time without prior notice.</p>

          <h2 id="fees">6. Fees and Payment Processing</h2>
          <h3>6.1 Platform Fee</h3>
          <p>Fundora charges a platform fee of five percent (5%) of the total amount raised by an organizer. This fee is deducted exclusively at the time of withdrawal and is not charged to sponsors.</p>
          <h3>6.2 Payment Gateway Fee</h3>
          <p>A payment processing fee is charged by our payment gateway provider and is passed on to the sponsor at checkout. This fee is displayed clearly to the sponsor before donation confirmation. The exact fee percentage is determined by the payment gateway and is subject to change.</p>
          <h3>6.3 No Exceptions</h3>
          <p>Platform fees apply to all campaigns and all organizers without exception, including registered non-governmental organizations and charitable institutions.</p>

          <h2 id="refunds">7. Refunds and Failed Campaigns</h2>
          <h3>7.1 Campaign Auto-Return Policy</h3>
          <p>Each campaign on Fundora has an auto-return setting configured by the organizer at the time of campaign creation. Sponsors are clearly informed of this setting before making a donation.</p>
          <ul>
            <li>If auto-return is enabled: In the event a campaign does not reach its fundraising goal by the deadline, all sponsors will be refunded their donation amount within thirty (30) business days of the campaign expiry date.</li>
            <li>If auto-return is disabled: Sponsors acknowledge before donating that the organizer retains all funds raised regardless of whether the goal is met.</li>
          </ul>
          <h3>7.2 Dispute Refunds</h3>
          <p>Where Fundora determines through investigation that fraud or misrepresentation has occurred, refunds may be issued to affected sponsors within fourteen (14) business days of the resolution of the investigation.</p>
          <h3>7.3 Non-Refundable Fees</h3>
          <p>Payment gateway processing fees are non-refundable as they are collected by the payment provider and are outside of Fundora's control.</p>

          <h2 id="organizers">8. Organizer Responsibilities</h2>
          <p>As a campaign organizer on Fundora, you agree to:</p>
          <ul>
            <li>Use funds raised exclusively for the purpose stated in your campaign.</li>
            <li>Post honest and accurate updates to your campaign sponsors.</li>
            <li>Comply with all applicable Philippine laws regarding fundraising and the use of donations.</li>
            <li>Respond to Fundora inquiries in a timely manner during any investigation.</li>
            <li>Not misrepresent your identity, your organization, or the nature of your campaign.</li>
          </ul>
          <p>Fundora does not verify how organizers spend funds after withdrawal. Organizers are solely responsible for the appropriate use of all funds raised.</p>

          <h2 id="sponsors">9. Sponsor Responsibilities</h2>
          <p>As a sponsor on Fundora, you acknowledge that:</p>
          <ul>
            <li>Donations are made voluntarily and at your own discretion.</li>
            <li>Fundora does not guarantee the success of any campaign.</li>
            <li>Fundora does not verify how organizers spend funds after withdrawal.</li>
            <li>You have read and understood the refund policy of the specific campaign before donating.</li>
            <li>You are solely responsible for conducting your own due diligence before donating.</li>
          </ul>

          <h2 id="disputes">10. Dispute Resolution</h2>
          <p>If a sponsor believes that an organizer has acted fraudulently or in bad faith, they may submit a formal complaint to Fundora. Fundora will investigate and may take the following actions:</p>
          <ul>
            <li>Suspend or remove the campaign.</li>
            <li>Suspend or ban the organizer's account.</li>
            <li>Issue refunds to affected sponsors where feasible.</li>
            <li>Refer the matter to appropriate Philippine law enforcement authorities.</li>
          </ul>
          <p>Fundora acts as a mediator in disputes and will make reasonable efforts to resolve complaints fairly. Fundora's decisions within the scope of the platform are final.</p>

          <h2 id="liability">11. Limitation of Liability</h2>
          <p>To the maximum extent permitted by Philippine law, Fundora and its co-founders shall not be liable for:</p>
          <ul>
            <li>Any indirect, incidental, or consequential damages arising from your use of the platform.</li>
            <li>The actions or conduct of any organizer or sponsor on the platform.</li>
            <li>Any loss of funds resulting from fraudulent campaigns, subject to our dispute resolution process.</li>
            <li>Any service interruptions, technical errors, or data loss.</li>
          </ul>

          <h2 id="termination">12. Termination</h2>
          <p>Fundora reserves the right to suspend or terminate your account at any time for violation of these Terms, fraudulent activity, failure to comply with identity verification, or any conduct determined to be harmful to the platform or its users.</p>

          <h2 id="law">13. Governing Law</h2>
          <p>These Terms of Service are governed by and construed in accordance with the laws of the Republic of the Philippines. Any disputes arising from these Terms shall be subject to the exclusive jurisdiction of the courts of Bukidnon, Philippines.</p>

          <h2 id="changes">14. Changes to These Terms</h2>
          <p>Fundora reserves the right to modify these Terms at any time. Registered users will be notified of material changes via email or through a prominent notice on the platform. Continued use of Fundora after notification constitutes acceptance of the updated Terms.</p>

          <h2 id="contact">15. Contact</h2>
          <p>For questions or concerns regarding these Terms of Service, please contact us through the Fundora platform support channel or our official Facebook page.</p>
          <p>Fundora | Maramag, Bukidnon, Philippines</p>
        </div>
      </div>
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
      </div>
    </section>
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

