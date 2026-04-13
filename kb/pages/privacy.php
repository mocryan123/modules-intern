<?php
/*
 * Fundora public page: Privacy Policy
 */

if (!defined('ABSPATH')) {
    exit;
}

function bntm_kbf_render_privacy() {
    $landing_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('landing') : home_url('/');
    $login_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : wp_login_url();
    ob_start();
    ?>
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css" />
    <style>
      /* Typography scale (match landing) */
      :root{
        --kbf-type-h1: 64px;
        --kbf-type-h2: 32px;
        --kbf-type-h3: 24px;
        --kbf-type-h4: 18px;
        --kbf-type-body: 16px;
        --kbf-type-lead: 18px;
        --kbf-type-meta: 12.5px;
      }
      .kbf-landing, .kbf-legal, body{
        font-family: 'Outfit', system-ui, -apple-system, sans-serif;
      }
      h1{font-size:var(--kbf-type-h1);line-height:1.05;font-weight:600;letter-spacing:-1.5px;color:#0d1a2e;}
      h2{font-size:var(--kbf-type-h2);line-height:1.2;font-weight:500;letter-spacing:-0.5px;color:#0f172a;}
      h3{font-size:var(--kbf-type-h3);line-height:1.3;font-weight:500;letter-spacing:-0.2px;color:#0f172a;}
      h4{font-size:var(--kbf-type-h4);line-height:1.35;font-weight:500;color:#0f172a;}
      p, li{font-size:var(--kbf-type-body);line-height:1.65;font-weight:400;color:#334155;}
      .kbf-lead{font-size:var(--kbf-type-lead);line-height:1.7;}
      small, .kbf-meta{font-size:var(--kbf-type-meta);line-height:1.5;font-weight:500;color:#64748b;}
      @media (max-width:720px){
        :root{
          --kbf-type-h1: 40px;
          --kbf-type-h2: 26px;
          --kbf-type-h3: 20px;
          --kbf-type-h4: 16px;
          --kbf-type-body: 15px;
          --kbf-type-lead: 16px;
          --kbf-type-meta: 12px;
        }
      }
      .ph{font-family:'Phosphor' !important;font-style:normal;font-weight:400;line-height:1;}
      .ph-bold{font-weight:700;}
      .ph-fill{font-weight:400;}
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
        background: #ffffff;
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
        border-radius: 8px !important;
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
      .kbf-hamburger i{
        font-size:18px;
        display:block;
        color:#64748b;
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
        align-items:center;
        height:68px;
        padding:12px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;
        box-sizing:border-box;
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
        font-weight: 600;
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
        background: linear-gradient(135deg, rgba(74, 152, 255, 0.9) 0%, rgba(47, 123, 220, 0.95) 100%) !important;
        color: rgba(255,255,255,0.5) !important;
        font-size: 12.5px !important;
        border-top: 1px solid rgba(255,255,255,0.07) !important;
        border-radius: 22px;
        padding: 20px 22px;
        gap: 18px;
        display: flex;
        flex-direction: column;
      }
      .kbf-footer-top{
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        column-gap: 14px;
        row-gap: 4px;
      }
      .kbf-footer-left{ display:flex; flex-direction:column; gap:0; text-align:left; }
      .kbf-footer-left p{ margin-top:0; }
      .kbf-footer-left.kbf-footer-brand{ grid-column: 1; justify-self: start; }
      .kbf-footer-bottom{
        display:flex;
        align-items:center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
      }
      .kbf-footer,
      .kbf-footer p,
      .kbf-footer small,
      .kbf-footer a,
      .kbf-footer .kbf-footer-links a,
      .kbf-footer .kbf-brand,
      .kbf-footer .kbf-brand-text,
      .kbf-footer h5{
        color:#ffffff !important;
        font-weight:400 !important;
        font-size:12.5px !important;
      }
      .kbf-footer .kbf-brand img{
        filter: invert(100%) brightness(1.1);
      }
      .kbf-footer .kbf-social { display: flex; gap: 8px; justify-self: end; }
      .kbf-footer .kbf-social a {
        width: 40px;
        height: 40px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 28px;
        text-decoration: none;
      }
      .kbf-footer .kbf-social i { font-size: 25px; }
      .kbf-footer .kbf-footer-links{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
        margin:0;
      }
      .kbf-footer .kbf-footer-links a{
        color:#ffffff !important;
        font-size:12.5px !important;
        text-decoration:none;
        position: relative;
        padding-bottom: 2px;
      }
      .kbf-footer .kbf-footer-links a::after{
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 100%;
        height: 2px;
        border-radius: 999px;
        background: rgba(255,255,255,0.9);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s ease;
      }
      .kbf-footer .kbf-footer-links a:hover{
        color:#ffffff !important;
      }
      .kbf-footer .kbf-footer-links a:hover::after{
        transform: scaleX(1);
      }
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
        .kbf-footer-top{
          grid-template-columns: 1fr;
          gap: 10px;
        }
        .kbf-footer-left{ align-items:center; }
        .kbf-footer-left.kbf-footer-brand{ grid-column: auto; }
        .kbf-footer-bottom{
          flex-direction: column;
          align-items: center;
          gap: 10px;
        }
        .kbf-footer .kbf-footer-links{ justify-content:center; }
        .kbf-footer .kbf-social{ justify-content:center; justify-self: center; }
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
          <i id="kbf-hamburger-icon" class="ph ph-list kbf-icon" role="img" aria-label="Menu"></i>
        </button>
      </div>
      <div class="kbf-mobile-menu" id="kbf-mobile-menu">
        <div class="kbf-mobile-menu-header">
          <div class="kbf-brand">
            <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora" style="width:24px;height:24px;object-fit:contain;border-radius:6px;">
            
          </div>
          <button class="kbf-hamburger" type="button" id="kbf-hamburger-close" aria-label="Close menu" style="display:inline-flex;">
            <i class="ph ph-x kbf-icon" role="img" aria-label="Close"></i>
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
      <h1>Privacy Policy</h1>
      <p class="kbf-legal-sub">FUNDORA - How Fundora collects, uses, and protects your data.</p>
      <div class="kbf-legal-grid">
        <aside class="kbf-legal-card kbf-legal-nav">
          <h4>Sections</h4>
          <a class="kbf-legal-link" href="#intro">1. Introduction</a>
          <a class="kbf-legal-link" href="#collect">2. Information We Collect</a>
          <a class="kbf-legal-link" href="#use">3. How We Use Your Information</a>
          <a class="kbf-legal-link" href="#share">4. Data Sharing with Third Parties</a>
          <a class="kbf-legal-link" href="#retain">5. Data Retention</a>
          <a class="kbf-legal-link" href="#rights">6. Your Rights as a Data Subject</a>
          <a class="kbf-legal-link" href="#security">7. Data Security</a>
          <a class="kbf-legal-link" href="#cookies">8. Cookies and Tracking</a>
          <a class="kbf-legal-link" href="#changes">9. Changes to This Policy</a>
          <a class="kbf-legal-link" href="#contact">10. Contact and Complaints</a>
          <hr style="border: none; border-top: 1px solid #edf0f4; margin: 24px 0;">
          <div style="margin-top:14px;">
            <a class="kbf-btn kbf-btn-primary kbf-btn-block" href="<?php echo esc_url(BNTM_KBF_URL . 'assets/legal/Fundora-Privacy-Policy.docx'); ?>" download>Download PDF</a>
          </div>
        </aside>
        <div class="kbf-legal-card">
          <div class="kbf-legal-meta">Effective Date: Upon Public Launch | Maramag, Bukidnon, Philippines</div>

          <h2 id="intro">1. Introduction</h2>
          <p>Fundora is committed to protecting your personal information in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173) of the Philippines and its Implementing Rules and Regulations. This Privacy Policy explains what data we collect, why we collect it, how we use it, and your rights as a data subject.</p>
          <p>By using Fundora, you consent to the collection and use of your information as described in this policy.</p>

          <h2 id="collect">2. Information We Collect</h2>
          <h3>2.1 Account Registration</h3>
          <ul>
            <li>Full name (personal or organization name)</li>
            <li>Email address</li>
            <li>Phone number</li>
            <li>Physical address</li>
          </ul>
          <h3>2.2 Identity Verification</h3>
          <p>If you are an organizer, we collect identity verification data through our third-party KYC provider, Didit. This includes government-issued ID images and biometric facial data. This data is processed and stored by Didit according to their privacy policy.</p>
          <h3>2.3 Payout Details</h3>
          <ul>
            <li>Maya Wallet mobile number</li>
            <li>GCash mobile number</li>
            <li>Credit or debit card details (processed securely through our payment gateway)</li>
          </ul>
          <h3>2.4 Campaign Data</h3>
          <ul>
            <li>Campaign title, description, photos, and updates</li>
            <li>Fundraising goal and deadline</li>
            <li>Story information</li>
          </ul>
          <h3>2.5 Transaction Data</h3>
          <ul>
            <li>Donation amounts and timestamps</li>
            <li>Payment status and method</li>
            <li>Withdrawal requests and processing history</li>
          </ul>
          <h3>2.6 Technical Data</h3>
          <ul>
            <li>IP address and device information</li>
            <li>Browser type and operating system</li>
            <li>Log data and platform usage patterns</li>
          </ul>

          <h2 id="use">3. How We Use Your Information</h2>
          <ul>
            <li>To create and manage your Fundora account.</li>
            <li>To verify your identity before allowing you to create campaigns.</li>
            <li>To process donations and withdrawals through our payment gateway.</li>
            <li>To display your public account profile and campaign information.</li>
            <li>To send you transactional notifications related to your campaigns and donations.</li>
            <li>To investigate complaints and resolve disputes between users.</li>
            <li>To comply with applicable Philippine laws and regulations.</li>
            <li>To improve the Fundora platform and user experience.</li>
            <li>To prevent fraud, abuse, and unauthorized access.</li>
          </ul>

          <h2 id="share">4. Data Sharing with Third Parties</h2>
          <p>We share your personal information only with the following third parties, and only to the extent necessary to provide our services:</p>
          <ul>
            <li>PayMongo or Maya â€” for payment processing. Your payment information is transmitted securely to our payment gateway provider to complete transactions. We do not store full card details on our servers.</li>
            <li>Didit â€” for identity verification. Organizer ID documents and biometric data are processed by Didit according to their own privacy policy and data retention practices.</li>
          </ul>
          <p>We do not sell, rent, or trade your personal information to any third party for marketing purposes.</p>

          <h2 id="retain">5. Data Retention</h2>
          <p>We retain your personal data for as long as your account is active or as needed to provide you with our services. Specific retention periods are as follows:</p>
          <ul>
            <li>Account data â€” retained for the duration of your account and for a period of five (5) years after account closure, as required by Philippine law.</li>
            <li>Transaction records â€” retained for seven (7) years for financial audit and legal compliance purposes.</li>
            <li>Identity verification data â€” retained according to Didit's data retention policy. Please refer to Didit's privacy policy for details.</li>
            <li>Security logs â€” retained for twelve (12) months.</li>
          </ul>

          <h2 id="rights">6. Your Rights as a Data Subject</h2>
          <p>Under the Data Privacy Act of 2012, you have the following rights with respect to your personal information:</p>
          <ul>
            <li>Right to be informed â€” You have the right to know what personal data we collect and how we use it.</li>
            <li>Right to access â€” You may request a copy of the personal data we hold about you.</li>
            <li>Right to rectification â€” You may request correction of any inaccurate or incomplete data.</li>
            <li>Right to erasure â€” You may request deletion of your personal data, subject to legal retention requirements.</li>
            <li>Right to data portability â€” You may request your data in a structured, machine-readable format.</li>
            <li>Right to object â€” You may object to the processing of your data in certain circumstances.</li>
          </ul>
          <p>To exercise any of these rights, please contact us through the Fundora platform support channel.</p>

          <h2 id="security">7. Data Security</h2>
          <p>Fundora implements reasonable technical and organizational measures to protect your personal information against unauthorized access, disclosure, alteration, or destruction. These measures include:</p>
          <ul>
            <li>Encrypted transmission of sensitive data using HTTPS.</li>
            <li>Secure storage of API keys and payment credentials.</li>
            <li>Rate limiting and abuse prevention on all platform endpoints.</li>
            <li>Regular security audits and vulnerability assessments.</li>
          </ul>
          <p>However, no method of transmission over the internet is completely secure. We cannot guarantee absolute security of your data.</p>

          <h2 id="cookies">8. Cookies and Tracking</h2>
          <p>Fundora uses session cookies to maintain your logged-in state and to ensure the proper functioning of the platform. We do not use tracking cookies for advertising purposes. By using Fundora, you consent to our use of cookies as described here.</p>

          <h2 id="changes">9. Changes to This Policy</h2>
          <p>We may update this Privacy Policy from time to time to reflect changes in our practices or applicable law. We will notify you of material changes via email or through a prominent notice on the platform. Your continued use of Fundora after notification constitutes acceptance of the updated policy.</p>

          <h2 id="contact">10. Contact and Complaints</h2>
          <p>If you have questions about this Privacy Policy or wish to exercise your data subject rights, please contact us through the Fundora platform support channel.</p>
          <p>If you believe your data privacy rights have been violated, you may file a complaint with the National Privacy Commission (NPC) of the Philippines at www.privacy.gov.ph.</p>
          <p>Fundora | Maramag, Bukidnon, Philippines</p>
        </div>
      </div>
       <!-- FOOTER -->
        <footer class="kbf-footer kbf-reveal delay-3" style="margin-top:80px;">
          <div class="kbf-footer-top">
            <div class="kbf-footer-left kbf-footer-brand">
              <div class="kbf-brand" style="margin-bottom:8px;">
                <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logobanner.png'); ?>" alt="fundora" style="width:auto;height:30px;object-fit:contain;border-radius:6px;filter:brightness(0) invert(1);">
              </div>
              <p>Community fundraising rooted in bayanihan.</p>
            </div>
            <div class="kbf-social">
            <a href="https://www.instagram.com/bntmtechnologiesinc/" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
              <i class="ph ph-instagram-logo" aria-hidden="true"></i>
            </a>
            <a href="https://www.facebook.com/bentamosabentamo" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
              <i class="ph ph-facebook-logo" aria-hidden="true"></i>
            </a>
            <a href="https://www.linkedin.com/company/bentamo/" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
              <i class="ph ph-linkedin-logo" aria-hidden="true"></i>
            </a>
            </div>
          </div>
          <div class="kbf-footer-bottom">
            <div class="kbf-footer-links">
              <a href="<?php echo esc_url(kbf_get_page_url('privacy')); ?>">Privacy Policy</a>
              <a href="<?php echo esc_url(kbf_get_page_url('terms')); ?>">Terms of Service</a>
              <a href="<?php echo esc_url(kbf_get_page_url('refund')); ?>">Refund Policy</a>
            </div>
            <small>&copy; fundora. All rights reserved.</small>
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
        function setIcon(stateOpen){
          if (!icon) return;
          var openCls = ['ph','ph-x'];
          var closeCls = ['ph','ph-list'];
          icon.classList.remove.apply(icon.classList, openCls);
          icon.classList.remove.apply(icon.classList, closeCls);
          icon.classList.add.apply(icon.classList, stateOpen ? openCls : closeCls);
        }
        function openMenu(){
          open = true;
          menu.classList.add('kbf-menu-open');
          overlay.classList.add('kbf-overlay-open');
          setIcon(true);
        }
        function closeMenu(){
          open = false;
          menu.classList.remove('kbf-menu-open');
          overlay.classList.remove('kbf-overlay-open');
          setIcon(false);
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











