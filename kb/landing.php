<?php

if (!defined('ABSPATH')) exit;

function bntm_kbf_render_landing() {
    $cta_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('browse') : home_url('/');
    $login_url = '#';

    $hero_img = 'https://images.unsplash.com/photo-1676147376197-203c810944a8?auto=format&fit=crop&fm=jpg&q=80&w=1800';
    $urgent_1 = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/58/Elderly_woman_gazing_at_art_%28Unsplash%29.jpg/1200px-Elderly_woman_gazing_at_art_%28Unsplash%29.jpg';
    $urgent_2 = 'https://upload.wikimedia.org/wikipedia/commons/1/1c/Womens_wheelchair_basketball_%28Unsplash%29.jpg';
    $urgent_3 = 'https://images.unsplash.com/photo-1642059893618-22daf30e92a2?auto=format&fit=crop&fm=jpg&q=80&w=1800';

    $bw_1 = 'https://images.unsplash.com/photo-1693811925446-49844c0b5961?auto=format&fit=crop&fm=jpg&q=80&w=600';
    $bw_2 = 'https://images.unsplash.com/photo-1736997426731-72ae5eed4bc2?auto=format&fit=crop&fm=jpg&q=80&w=600';
    $bw_3 = 'https://images.unsplash.com/photo-1724480092357-3cfdc8077bb9?auto=format&fit=crop&fm=jpg&q=80&w=600';
    $bw_4 = 'https://images.unsplash.com/photo-1724480118484-d57f0de3a4fc?auto=format&fit=crop&fm=jpg&q=80&w=600';

    ob_start();
    ?>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&display=swap');

    :root {
        --kbf-ink: #0f1115;
        --kbf-muted: #6f7785;
        --kbf-border: #edf0f4;
        --kbf-surface: #ffffff;
        --kbf-soft: #f7f8fb;
        --kbf-lime: #6fb6ff;
        --kbf-lime-dark: #0f2a52;
        --kbf-shadow: 0 18px 40px rgba(16, 24, 40, 0.08);
        --kbf-radius: 26px;
    }

    .kbf-landing {
        font-family: 'Manrope', system-ui, -apple-system, sans-serif;
        color: var(--kbf-ink);
        background: var(--kbf-surface);
        width: 100%;
        padding: 18px 16px 40px;
        border-radius: 18px;
    }

    .kbf-landing * { box-sizing: border-box; }
    .kbf-landing a { color: inherit; text-decoration: none; }
    .kbf-landing .kbf-container { max-width: 1120px; margin: 0 auto; }
    html { scroll-behavior: smooth; }
    .kbf-landing { scroll-behavior: smooth; }

    .kbf-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 8px 0 18px;
    }
    .kbf-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 800;
    }
    .kbf-brand-badge {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #b9dcff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #103054;
        font-weight: 800;
        font-size: 14px;
    }
    .kbf-nav {
        display: flex;
        gap: 20px;
        font-size: 12.5px;
        color: var(--kbf-muted);
    }
    .kbf-actions { display: flex; gap: 10px; align-items: center; }

    .kbf-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 18px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 12.5px;
        border: 1px solid transparent;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .kbf-btn-primary {
        background: var(--kbf-lime);
        color: var(--kbf-lime-dark);
        box-shadow: 0 10px 22px rgba(111, 182, 255, 0.5);
    }
    .kbf-btn-primary:hover { transform: translateY(-1px); }
    .kbf-btn-ghost {
        background: transparent;
        border-color: var(--kbf-border);
        color: var(--kbf-muted);
    }
    .kbf-btn-ghost[aria-disabled="true"] { opacity: .7; cursor: not-allowed; }

    .kbf-hero {
        position: relative;
        border-radius: var(--kbf-radius);
        overflow: hidden;
        background: #dae1e8;
        min-height: 320px;
        display: flex;
        align-items: flex-end;
        padding: 26px 28px;
        box-shadow: var(--kbf-shadow);
        background-image: url('<?php echo esc_url($hero_img); ?>');
        background-size: cover;
        background-position: center;
    }
    .kbf-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(0,0,0,.35) 0%, rgba(0,0,0,.1) 60%, rgba(0,0,0,0) 100%);
    }
    .kbf-hero-content { position: relative; z-index: 2; display: flex; align-items: flex-end; gap: 18px; }
    .kbf-hero-title {
        font-size: 84px;
        font-weight: 800;
        line-height: .9;
        color: #fff;
        margin: 0;
        letter-spacing: -1.5px;
    }
    .kbf-hero-sub {
        font-size: 28px;
        font-weight: 700;
        color: #f1f3f6;
        margin: 0 0 6px;
        line-height: 1.05;
    }
    .kbf-hero-cta { margin-left: auto; }

    .kbf-section { margin-top: 28px; }
    .kbf-section h2 { font-size: 22px; margin: 0 0 6px; }
    .kbf-section p { color: var(--kbf-muted); margin: 0; font-size: 13.5px; }

    .kbf-feature-grid {
        margin-top: 18px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .kbf-card {
        background: var(--kbf-surface);
        border: 1px solid var(--kbf-border);
        border-radius: 18px;
        padding: 16px;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.04);
    }
    .kbf-card .kbf-chip {
        width: 30px;
        height: 30px;
        border-radius: 12px;
        background: #e7f1ff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2a5a9e;
        font-weight: 700;
        font-size: 13px;
    }
    .kbf-card h4 { margin: 12px 0 6px; font-size: 14px; }
    .kbf-card p { font-size: 12.5px; }

    .kbf-urgent-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-top: 18px;
    }
    .kbf-urgent-card {
        background: var(--kbf-surface);
        border: 1px solid var(--kbf-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: var(--kbf-shadow);
    }
    .kbf-urgent-thumb {
        height: 180px;
        background-size: cover;
        background-position: center;
    }
    .kbf-urgent-body { padding: 14px 16px 16px; }
    .kbf-urgent-meta { font-size: 11.5px; color: var(--kbf-muted); }
    .kbf-urgent-title { font-size: 13.5px; font-weight: 700; margin: 6px 0 10px; }
    .kbf-progress { height: 6px; background: #edf1f7; border-radius: 999px; overflow: hidden; }
    .kbf-progress span { display: block; height: 100%; background: var(--kbf-lime); width: 68%; }
    .kbf-amounts { display: flex; justify-content: space-between; font-size: 12px; color: var(--kbf-muted); margin-top: 10px; }

    .kbf-stat {
        margin-top: 30px;
        display: grid;
        grid-template-columns: 1fr;
        align-items: center;
        position: relative;
        text-align: center;
        padding: 18px 12px 24px;
    }
    .kbf-stat h3 {
        font-size: 72px;
        font-weight: 800;
        margin: 10px 0 6px;
    }
    .kbf-stat p { margin: 0 0 16px; color: var(--kbf-muted); }
    .kbf-stat .kbf-photo-grid {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 1;
    }
    .kbf-stat .kbf-photo-grid img {
        width: 90px;
        height: 90px;
        border-radius: 18px;
        object-fit: cover;
        filter: grayscale(100%);
        box-shadow: var(--kbf-shadow);
        position: absolute;
    }
    .kbf-stat .kbf-photo-grid img:nth-child(1) { left: 3%; top: 8%; }
    .kbf-stat .kbf-photo-grid img:nth-child(2) { right: 4%; top: 12%; }
    .kbf-stat .kbf-photo-grid img:nth-child(3) { left: 8%; bottom: 6%; }
    .kbf-stat .kbf-photo-grid img:nth-child(4) { right: 6%; bottom: 6%; }
    .kbf-stat .kbf-stat-content { position: relative; z-index: 2; }
    .kbf-stat .kbf-btn-primary { margin-top: 6px; }

    .kbf-faq { margin-top: 16px; border-top: 1px solid var(--kbf-border); }
    .kbf-faq details {
        border-bottom: 1px solid var(--kbf-border);
        padding: 12px 0;
    }
    .kbf-faq summary {
        list-style: none;
        cursor: pointer;
        font-size: 13.5px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .kbf-faq summary::-webkit-details-marker { display: none; }
    .kbf-faq summary::after {
        content: '+';
        color: var(--kbf-muted);
        font-weight: 700;
    }
    .kbf-faq details[open] summary::after { content: '–'; }
    .kbf-faq details p {
        margin: 10px 0 0;
        color: var(--kbf-muted);
        font-size: 12.5px;
        line-height: 1.5;
    }

    .kbf-footer {
        margin-top: 34px;
        background: #0c0f14;
        color: #b9c0cc;
        border-radius: 26px;
        padding: 26px 28px;
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr 1fr;
        gap: 18px;
    }
    .kbf-footer h5 { margin: 0 0 8px; color: #fff; }
    .kbf-footer small { color: #8590a6; }
    .kbf-footer .kbf-social { display: flex; gap: 8px; margin-top: 12px; }
    .kbf-footer .kbf-social a {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 1px solid rgba(255,255,255,.18);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 12px;
    }

    @media (max-width: 900px) {
        .kbf-nav { display: none; }
        .kbf-hero-title { font-size: 56px; }
        .kbf-hero-sub { font-size: 22px; }
        .kbf-feature-grid, .kbf-urgent-grid { grid-template-columns: 1fr; }
        .kbf-footer { grid-template-columns: 1fr; }
        .kbf-stat .kbf-photo-grid { display: none; }
    }
    @media (max-width: 720px) {
        .kbf-topbar { flex-wrap: wrap; }
        .kbf-actions { width: 100%; justify-content: flex-start; flex-wrap: wrap; }
        .kbf-hero { padding: 20px; min-height: 260px; }
        .kbf-hero-content { flex-direction: column; align-items: flex-start; gap: 10px; }
        .kbf-hero-cta { margin-left: 0; }
        .kbf-hero-title { font-size: 46px; }
        .kbf-hero-sub { font-size: 20px; }
    }
    @media (max-width: 520px) {
        .kbf-landing { padding: 14px 10px 30px; }
        .kbf-hero-title { font-size: 40px; }
        .kbf-hero-sub { font-size: 18px; }
        .kbf-section h2 { font-size: 20px; }
        .kbf-stat h3 { font-size: 54px; }
        .kbf-btn { width: 100%; justify-content: center; }
        .kbf-actions .kbf-btn { width: auto; }
        .kbf-urgent-thumb { height: 160px; }
    }
    </style>

    <section class="kbf-landing">
      <div class="kbf-container">
        <div class="kbf-topbar">
          <div class="kbf-brand">
            <span class="kbf-brand-badge">✳</span>
            <span>Fund</span>
          </div>
          <nav class="kbf-nav">
            <a href="#kbf-home">Home</a>
            <a href="#kbf-donation">Donation</a>
            <a href="#kbf-how">How It Works</a>
            <a href="#kbf-about">About Us</a>
          </nav>
          <div class="kbf-actions">
            <a class="kbf-btn kbf-btn-ghost" aria-disabled="true" href="<?php echo esc_url($login_url); ?>">Login (Soon)</a>
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($cta_url); ?>">Download App</a>
          </div>
        </div>

        <div id="kbf-home" class="kbf-hero">
          <div class="kbf-hero-content">
            <h1 class="kbf-hero-title">Fund</h1>
            <div>
              <p class="kbf-hero-sub">Help<br>Others</p>
            </div>
            <div class="kbf-hero-cta">
              <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($cta_url); ?>">Start Fundraising</a>
            </div>
          </div>
        </div>

        <div id="kbf-how" class="kbf-section">
          <h2>Fund, Fast As Flash</h2>
          <p>Fundraise at the speed of thought! Elevate your cause in just a minute with our lightning-fast fundraising platform.</p>
          <div class="kbf-feature-grid">
            <div class="kbf-card">
              <div class="kbf-chip">⚡</div>
              <h4>Ignite Impact</h4>
              <p>Spark joy by sharing your cause and the positive impact it brings. Clearly express how contributions will make a meaningful difference.</p>
            </div>
            <div class="kbf-card">
              <div class="kbf-chip">🌿</div>
              <h4>Spread The Word</h4>
              <p>Leverage the speed of social media and online networks. Share your fundraising campaign swiftly across various platforms.</p>
            </div>
            <div class="kbf-card">
              <div class="kbf-chip">🌍</div>
              <h4>Connect Globally</h4>
              <p>Build a strong social network around your cause. Encourage supporters to share the campaign within their local communities.</p>
            </div>
          </div>
        </div>

        <div id="kbf-donation" class="kbf-section">
          <h2>Urgent Fundraising!</h2>
          <p>Time is of the essence! Join our mission NOW to make an immediate impact. Every second counts!</p>
          <div class="kbf-urgent-grid">
            <article class="kbf-urgent-card">
              <div class="kbf-urgent-thumb" style="background-image:url('<?php echo esc_url($urgent_1); ?>');"></div>
              <div class="kbf-urgent-body">
                <div class="kbf-urgent-meta">We Care</div>
                <div class="kbf-urgent-title">GreenFund: Sustain Earth Now</div>
                <div class="kbf-progress"><span></span></div>
                <div class="kbf-amounts"><span>₱50,240</span><span>7 days left</span></div>
              </div>
            </article>
            <article class="kbf-urgent-card">
              <div class="kbf-urgent-thumb" style="background-image:url('<?php echo esc_url($urgent_2); ?>');"></div>
              <div class="kbf-urgent-body">
                <div class="kbf-urgent-meta">Unicef</div>
                <div class="kbf-urgent-title">SeniorHealth: Support Campaign</div>
                <div class="kbf-progress"><span style="width: 52%;"></span></div>
                <div class="kbf-amounts"><span>₱4,240</span><span>19 days left</span></div>
              </div>
            </article>
            <article class="kbf-urgent-card">
              <div class="kbf-urgent-thumb" style="background-image:url('<?php echo esc_url($urgent_3); ?>');"></div>
              <div class="kbf-urgent-body">
                <div class="kbf-urgent-meta">Unity Foundation</div>
                <div class="kbf-urgent-title">DisasterCare: Urgent Support</div>
                <div class="kbf-progress"><span style="width: 35%;"></span></div>
                <div class="kbf-amounts"><span>₱2,100</span><span>23 days left</span></div>
              </div>
            </article>
          </div>
        </div>

        <div id="kbf-about" class="kbf-section kbf-stat">
          <div class="kbf-photo-grid" aria-hidden="true">
            <img src="<?php echo esc_url($bw_1); ?>" alt="">
            <img src="<?php echo esc_url($bw_2); ?>" alt="">
            <img src="<?php echo esc_url($bw_3); ?>" alt="">
            <img src="<?php echo esc_url($bw_4); ?>" alt="">
          </div>
          <div class="kbf-stat-content">
            <p>Be The Part Of FundRaisers With Over</p>
            <h3>217,924+</h3>
            <p>People From Around The World Joined</p>
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($cta_url); ?>">Join FundRaisers Now!</a>
          </div>
        </div>

        <div class="kbf-section">
          <h2>Frequently Asked Questions.</h2>
          <div class="kbf-faq">
            <details>
              <summary>How Can I Make Donation?</summary>
              <p>Browse live campaigns, select a cause, and complete your sponsorship using the available payment options.</p>
            </details>
            <details>
              <summary>Is My Donation Tax-Deductible?</summary>
              <p>Tax benefits depend on organizer accreditation and local regulations. Please check with the organizer first.</p>
            </details>
            <details>
              <summary>Can I Donate In Honor Or Memory Of Someone?</summary>
              <p>Yes. Organizers can add dedication notes in campaign updates and acknowledgments.</p>
            </details>
            <details>
              <summary>How Will My Donation Be Used?</summary>
              <p>Organizers share budgets and progress updates so sponsors can see how funds are allocated.</p>
            </details>
            <details>
              <summary>Can I Set Up A Recurring Donation?</summary>
              <p>Recurring sponsorships are planned and will be available in a future update.</p>
            </details>
          </div>
        </div>

        <footer class="kbf-footer">
          <div>
            <div class="kbf-brand" style="margin-bottom:8px;">
              <span class="kbf-brand-badge">✳</span>
              <span>Fund</span>
            </div>
            <p>Elevating Experience & Seize Control Of Your Smart Home!</p>
            <small>© Fund Inc. 2023. All Rights Reserved.</small>
            <div class="kbf-social">
              <a href="#" aria-label="Instagram">IG</a>
              <a href="#" aria-label="Facebook">FB</a>
              <a href="#" aria-label="Twitter">X</a>
              <a href="#" aria-label="LinkedIn">IN</a>
            </div>
          </div>
          <div>
            <h5>Donate</h5>
            <p>Education</p>
            <p>Social</p>
            <p>Medicine</p>
            <p>Disaster</p>
          </div>
          <div>
            <h5>Help</h5>
            <p>FAQ</p>
            <p>Privacy Policy</p>
            <p>Accessibility</p>
            <p>Contact Us</p>
          </div>
          <div>
            <h5>Company</h5>
            <p>About Us</p>
            <p>Careers</p>
            <p>Services</p>
            <p>Pricing</p>
          </div>
        </footer>
      </div>
    </section>
    <script>
    (function() {
        const nav = document.querySelector('.kbf-nav');
        if (!nav) return;
        nav.addEventListener('click', function(e) {
            const link = e.target.closest('a[href^="#"]');
            if (!link) return;
            const id = link.getAttribute('href').slice(1);
            const target = document.getElementById(id);
            if (!target) return;
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
