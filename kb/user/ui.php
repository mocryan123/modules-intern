<?php
/*
 * KBF user UI rendering and shortcode output.
 */

if (!defined('ABSPATH')) exit;

function bntm_shortcode_kbf_dashboard() {
    if (!is_user_logged_in()) {
        return '<div class="kbf-wrap"><div class="kbf-alert kbf-alert-warning">Please log in to access your dashboard.</div></div>';
    }
    kbf_global_assets();
    $user        = wp_get_current_user();
    global $wpdb;
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $nav_profile = $wpdb->get_row($wpdb->prepare("SELECT avatar_url FROM {$pt} WHERE business_id=%d", $user->ID));
    $business_id = $user->ID;
    $tab         = isset($_GET['kbf_tab']) ? sanitize_text_field($_GET['kbf_tab']) : 'find_funds';
    $nonce_create = wp_create_nonce('kbf_create_fund');
    $nonce_edit   = wp_create_nonce('kbf_update_fund');
    $nonce_cancel = wp_create_nonce('kbf_cancel_fund');
    $nonce_wd     = wp_create_nonce('kbf_withdrawal');
    $nonce_extend = wp_create_nonce('kbf_extend');
    $nonce_appeal = wp_create_nonce('kbf_appeal');
    $payment_state = isset($_GET['kbf_payment']) ? sanitize_text_field($_GET['kbf_payment']) : '';

    if ($payment_state === 'success') {
        $find_url = add_query_arg('kbf_tab', 'find_funds', kbf_get_page_url('dashboard'));
        ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <div class="kbf-wrap kbf-user-ui">
          <div class="kbf-card" style="max-width:640px;margin:50px auto;padding:34px 30px;text-align:center;">
            <div style="font-size:26px;font-weight:800;color:var(--kbf-navy);margin-bottom:8px;">Thank You</div>
            <div style="font-size:14px;color:var(--kbf-slate);margin-bottom:22px;">Thank you for your donation or support.</div>
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($find_url); ?>">Find Funds</a>
          </div>
        </div>
        <?php
        return ob_get_clean();
    }

    ob_start();
    ?>
    
    
    
    
    

    
    <!-- ================== CSS ================== -->
    <style>
    .kbf-user-ui{
        font-family:'Poppins',system-ui,-apple-system,sans-serif;
        color:#0f1115;
        background:#ffffff;
        border-radius:18px;
        padding:22px 18px 28px;
        border:none;
        box-shadow:none;
    }
    .kbf-user-ui .kbf-btn{font-weight:400;}
    .kbf-user-ui .kbf-card,
    .kbf-user-ui .kbf-fund-card,
    .kbf-user-ui .kbf-filter-bar{
        border-radius:18px;
        border:1px solid #edf0f4;
        box-shadow:0 18px 40px rgba(16,24,40,0.08);
        transition:transform .2s ease, box-shadow .2s ease;
    }
    .kbf-user-ui .kbf-card:hover,
    .kbf-user-ui .kbf-fund-card:hover{
        transform:translateY(-3px);
        box-shadow:0 16px 34px rgba(15,23,42,0.08);
    }
    .kbf-user-ui .kbf-btn-primary{
        background:linear-gradient(135deg,#79c0ff 0%,#6fb6ff 45%,#4a98ff 100%);
        color:#ffffff;
        box-shadow:0 10px 22px rgba(111,182,255,0.35);
    }
    .kbf-user-ui .kbf-btn-primary:hover{
        background:linear-gradient(135deg,#79c0ff 0%,#6fb6ff 45%,#4a98ff 100%);
        color:#ffffff;
        transform:translateY(-1px);
        box-shadow:0 14px 30px rgba(90,160,255,0.45),0 0 0 6px rgba(111,182,255,0.15);
        filter:saturate(1.05);
    }
    .kbf-user-ui .kbf-btn-secondary{
        background:#ffffff;
        border:1px solid #dfe7f3;
        color:#344055;
    }
    .kbf-user-ui .kbf-meta,
    .kbf-user-ui .kbf-text-sm,
    .kbf-user-ui .kbf-browse-tab,
    .kbf-user-ui .kbf-browse-search input{
        color:#4f5a6b;
    }
    .kbf-dashboard-topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        padding:8px 4px 14px;
        background:transparent;
        border:none;
        border-radius:0;
        box-shadow:none;
        margin-bottom:10px;
    }
    .kbf-dashboard-brand{
        display:flex;
        align-items:center;
        gap:10px;
        font-weight:800;
        color:#0f172a;
        font-size:15px;
    }
    .kbf-dashboard-brand .kbf-logo-dot{
        width:26px;height:26px;border-radius:10px;
        background:linear-gradient(135deg,#79c0ff 0%,#4a98ff 100%);
        display:inline-flex;align-items:center;justify-content:center;
        color:#fff;font-weight:800;font-size:12px;
        box-shadow:0 8px 18px rgba(79,147,255,0.35);
    }
    .kbf-dashboard-nav{
        display:flex;
        gap:18px;
        font-size:12.5px;
        color:var(--kbf-muted);
        align-items:center;
        flex-wrap:wrap;
    }
    .kbf-dashboard-nav a{
        position:relative;
        text-decoration:none;
        color:#64748b;
        font-weight:600;
    }
    .kbf-dashboard-nav a::after{
        content:'';
        position:absolute;
        left:0;
        bottom:-8px;
        width:0;
        height:2px;
        border-radius:999px;
        background:#4a98ff;
        transition:width .2s ease;
    }
    .kbf-dashboard-nav a:hover::after,
    .kbf-dashboard-nav a.active::after{ width:100%; }
    .kbf-dashboard-nav a.active{ color:#1f2a44; }
    .kbf-dashboard-actions{
        display:flex;align-items:center;gap:10px;
    }
    .kbf-dashboard-avatar{
        width:34px;height:34px;border-radius:50%;
        border:2px solid #e5efff;object-fit:cover;
        box-shadow:0 8px 18px rgba(16,24,40,0.12);
    }
    .kbf-hero-wrap{
        padding:10px 6px 0;
    }
    .kbf-hero-banner{
        background:linear-gradient(135deg,#edf4ff 0%,#ffffff 50%,#e5f0ff 100%);
        background-image:
          radial-gradient(140% 160% at 0% 0%, rgba(79,147,255,0.28) 0%, rgba(79,147,255,0) 55%),
          radial-gradient(140% 160% at 100% 0%, rgba(161,210,255,0.30) 0%, rgba(161,210,255,0) 55%),
          linear-gradient(135deg,#edf4ff 0%,#ffffff 50%,#e5f0ff 100%);
        border:1.5px solid #cfe0f7;
        border-radius:22px;
        padding:22px 24px;
        box-shadow:none;
        margin-bottom:18px;
    }
    .kbf-user-ui,
    .kbf-hero-banner{
        background-image:none !important;
    }
    .kbf-user-ui{
        border:none !important;
        box-shadow:none !important;
    }
    .kbf-hero-title{
        font-size:28px;
        font-weight:800;
        color:#0f172a;
        margin:0 0 6px;
    }
    .kbf-hero-sub{
        color:#4b5563;
        font-size:13.5px;
        margin:0 0 18px;
        max-width:520px;
    }
    .kbf-hero-grid{
        display:grid;
        grid-template-columns:1.2fr 1fr;
        gap:18px;
        margin-bottom:20px;
    }
    .kbf-hero-card{
        background:linear-gradient(180deg,#ffffff 0%,#f7faff 100%);
        border:1.5px solid #dfe7f3;
        border-radius:20px;
        padding:18px 20px;
        box-shadow:none;
        display:flex;
        flex-direction:column;
        gap:10px;
        min-height:150px;
        position:relative;
        overflow:hidden;
    }
    .kbf-hero-card.kbf-hero-primary{
        background:linear-gradient(135deg,#2f7bdc 0%,#4a98ff 100%);
        border:1.5px solid #8cc0ff;
        color:#fff;
    }
    .kbf-hero-card h4{
        margin:0;
        font-size:16px;
        font-weight:700;
    }
    .kbf-hero-card p{
        margin:0;
        font-size:12.5px;
        color:inherit;
        opacity:.9;
    }
    .kbf-hero-card .kbf-hero-icon{
        width:34px;height:34px;border-radius:12px;
        display:inline-flex;align-items:center;justify-content:center;
        background:#eef4ff;color:#1f2a44;
    }
    .kbf-hero-card.kbf-hero-primary .kbf-hero-icon{
        background:rgba(255,255,255,.2);
        color:#fff;
    }
    @media (max-width: 900px){
        .kbf-hero-grid{ grid-template-columns:1fr; }
        .kbf-dashboard-topbar{ flex-wrap:wrap; }
    }
    .kbf-user-ui .kbf-modal-overlay{
        position:fixed;
        inset:0;
        display:flex;
        align-items:center;
        justify-content:center;
        background:rgba(11,20,38,0.45);
        backdrop-filter:blur(6px);
        padding:24px;
        z-index:9999;
    }
    .kbf-user-ui .kbf-modal{
        width:100%;
        max-width:720px;
        background:#fff;
        border-radius:22px;
        border:1px solid #dfe7f3;
        box-shadow:0 30px 80px rgba(15,40,80,0.22);
        overflow:hidden;
        max-height:90vh;
        display:flex;
        flex-direction:column;
    }
    .kbf-user-ui .kbf-modal.kbf-modal-sm{
        max-width:520px;
    }
    .kbf-user-ui .kbf-modal-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:18px 20px;
        background:
            radial-gradient(520px 200px at 10% -40%, rgba(111,182,255,0.25), transparent 70%),
            #f8fbff;
        border-bottom:1px solid #edf0f4;
    }
    .kbf-user-ui .kbf-modal-header h3{
        margin:0;
        font-size:16px;
        font-weight:600;
        color:#0d1a2e;
    }
    .kbf-user-ui .kbf-modal-close{
        width:32px;
        height:32px;
        border-radius:50%;
        border:1px solid #dfe7f3;
        background:#fff;
        color:#6b7a90;
        font-size:18px;
        line-height:1;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        transition:transform .15s ease, box-shadow .2s ease;
    }
    .kbf-user-ui .kbf-modal-close:hover{
        transform:translateY(-1px);
        box-shadow:0 8px 18px rgba(15,40,80,0.12);
    }
    .kbf-user-ui .kbf-modal-body{
        padding:20px;
        background:#fff;
        overflow-y:auto;
        max-height:70vh;
    }
    .kbf-user-ui .kbf-modal-footer{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        padding:16px 20px 20px;
        background:#fbfcff;
        border-top:1px solid #edf0f4;
    }
    .kbf-user-ui .kbf-modal input,
    .kbf-user-ui .kbf-modal select,
    .kbf-user-ui .kbf-modal textarea{
        border-radius:12px;
        border:1.5px solid #e2e8f0;
        background:#fff;
    }
    .kbf-loading-overlay{
        position:fixed;
        inset:0;
        display:flex;
        align-items:center;
        justify-content:center;
        background:rgba(11,20,38,0.55);
        backdrop-filter:blur(6px);
        z-index:10000;
    }
    .kbf-loading-card{
        background:#ffffff;
        border:1px solid #dfe7f3;
        border-radius:22px;
        padding:24px 28px;
        box-shadow:0 30px 80px rgba(15,40,80,0.22);
        text-align:center;
        min-width:220px;
    }
    .kbf-loading-spinner{
        width:34px;
        height:34px;
        border-radius:50%;
        border:4px solid rgba(111,182,255,0.25);
        border-top-color:#6fb6ff;
        margin:0 auto 12px;
        animation:kbfSpin .8s linear infinite;
    }
    @keyframes kbfSpin { to { transform: rotate(360deg); } }
    .kbf-user-ui .kbf-field-error{
        margin-top:6px;
        font-size:11.5px;
        color:#e11d48;
    }
    .kbf-user-ui .kbf-desc-counter{
        display:block;
        margin-top:6px;
        font-size:11.5px;
        color:#4f5a6b;
    }
    .kbf-user-ui .kbf-char-count{
        display:block;
        margin-top:6px;
        font-size:11.5px;
        color:#4f5a6b;
    }
    .kbf-user-ui .kbf-title-counter{
        display:block;
        margin-top:6px;
        font-size:11.5px;
        color:#4f5a6b;
    }
    html.kbf-modal-lock, body.kbf-modal-lock { overflow: hidden; }
    </style>

    <!-- ================== HTML ================== -->
    <div class="kbf-wrap kbf-user-ui">
    <div id="kbf-loading-overlay" class="kbf-loading-overlay" style="display:none;">
      <div class="kbf-loading-card">
        <div class="kbf-loading-spinner"></div>
        <div style="font-size:13px;color:#4f5a6b;">Submitting your fund...</div>
      </div>
    </div>
    <!-- ================== CSS ================== -->
    <style>
    .kbf-user-ui{
        font-family:'Poppins',system-ui,-apple-system,sans-serif;
        color:#0f1115;
        background:#ffffff;
        border-radius:18px;
        padding:22px 18px 28px;
    }
    .kbf-user-ui .kbf-card,
    .kbf-user-ui .kbf-fund-card,
    .kbf-user-ui .kbf-filter-bar{
        border-radius:18px;
        border:1px solid #edf0f4;
        box-shadow:0 18px 40px rgba(16,24,40,0.08);
        transition:transform .2s ease, box-shadow .2s ease;
    }
    .kbf-user-ui .kbf-card:hover,
    .kbf-user-ui .kbf-fund-card:hover{
        transform:translateY(-3px);
        box-shadow:0 16px 34px rgba(15,23,42,0.08);
    }
    .kbf-user-ui .kbf-btn{font-weight:400;}
    .kbf-user-ui .kbf-btn-primary{
        background:linear-gradient(135deg,#79c0ff 0%,#6fb6ff 45%,#4a98ff 100%);
        color:#ffffff;
        box-shadow:0 10px 22px rgba(111,182,255,0.35);
    }
    .kbf-user-ui .kbf-btn-primary:hover{
        background:linear-gradient(135deg,#79c0ff 0%,#6fb6ff 45%,#4a98ff 100%);
        color:#ffffff;
        transform:translateY(-1px);
        box-shadow:0 14px 30px rgba(90,160,255,0.45),0 0 0 6px rgba(111,182,255,0.15);
        filter:saturate(1.05);
    }
    .kbf-user-ui .kbf-btn-secondary{
        background:#ffffff;
        border:1px solid #dfe7f3;
        color:#344055;
    }
    .kbf-user-ui .kbf-meta,
    .kbf-user-ui .kbf-browse-tab,
    .kbf-user-ui .kbf-browse-search input{
        color:#4f5a6b;
    }
    </style>

    <!-- ===== MODAL: Create Fund ===== -->
    <div id="kbf-modal-create" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Create New Fund</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-create')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-create-fund-form" enctype="multipart/form-data">
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Funding For *</label>
                <select name="funder_type" required>
                  <option value="yourself">Yourself</option>
                  <option value="someone_else">Someone Else</option>
                  <option value="animal_care">Animal Care</option>
                  <option value="charity_event">Charity or Event</option>
                </select>
              </div>
              <div class="kbf-form-group">
                <label>Category *</label>
                <select name="category" required>
                  <?php foreach (kbf_get_categories() as $c): ?>
                    <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Fund Title *</label>
              <input type="text" name="title" id="kbf-create-title" placeholder="Clear, compelling title" maxlength="50" required>
              <small class="kbf-title-counter">0 / 40</small>
            </div>
            <div class="kbf-form-group">
              <label>Description *</label>
              <textarea name="description" rows="10" maxlength="800" placeholder="Tell your story and why this fund matters..." required></textarea>
              <small class="kbf-desc-counter">0 / 800</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Goal Amount (PHP) *</label>
                <input type="number" name="goal_amount" placeholder="0.00" min="100" step="0.01" required>
              </div>
            <div class="kbf-form-group">
              <label>Deadline</label>
              <input type="date" name="deadline" min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
              <small>Optional — set an end date (minimum 7 days from today).</small>
            </div>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Contact Email *</label>
                <input type="email" name="email" required>
              </div>
              <div class="kbf-form-group">
                <label>Contact Phone *</label>
                <input type="text" name="phone" placeholder="+63 9XX XXX XXXX" required>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Province *</label>
              <select name="location" id="kbf-province" required>
                <option value="">Select Province</option>
                <?php foreach (kbf_get_provinces() as $p): ?>
                  <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                <?php endforeach; ?>
              </select>
              <small>Select your province first.</small>
            </div>
            <div class="kbf-form-group">
              <label>Municipality *</label>
              <select name="municipality" id="kbf-municipality" required disabled>
                <option value="">Select Municipality</option>
              </select>
              <small>Municipality list will load based on province.</small>
            </div>
            <div class="kbf-form-group">
              <label>Barangay *</label>
              <select name="barangay" id="kbf-barangay" required disabled>
                <option value="">Select Barangay</option>
              </select>
              <small>Barangay list will load based on municipality.</small>
            </div>
            <div class="kbf-form-group">
              <label>Valid Government / Company ID *</label>
              <input type="file" name="valid_id" accept="image/*,.pdf" required>
              <small>Required for identity verification before fund approval.</small>
            </div>
            <div class="kbf-form-group">
              <label>Add Photos (up to 5)</label>
              <input type="file" name="photos[]" accept="image/*" multiple required>
              <small>Required for identity verification before fund approval.</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label class="kbf-checkbox-row">
                <input type="checkbox" name="auto_return" value="1">
                Auto-return funds to sponsors if goal not met by deadline
              </label>
            </div>
            <div class="kbf-form-group">
              <label class="kbf-checkbox-row">
                <input type="checkbox" name="agree_terms" id="kbf-agree-terms" required>
                I agree to the <a href="<?php echo esc_url(kbf_get_page_url('terms')); ?>" target="_blank" rel="noopener noreferrer">Terms &amp; Agreement</a>.
              </label>
              <div class="kbf-field-error"></div>
            </div>
            <div id="kbf-create-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-create')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitCreate()">Submit for Review</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Edit Fund ===== -->
    <div id="kbf-modal-edit" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Edit Fund</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-edit')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-edit-fund-form" enctype="multipart/form-data">
            <input type="hidden" name="fund_id" id="edit-fund-id">
            <div class="kbf-form-group">
              <label>Title</label>
              <input type="text" name="title" id="edit-fund-title" maxlength="80" required>
              <small class="kbf-title-counter">0 / 80</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Description</label>
              <textarea name="description" id="edit-fund-desc" rows="10" maxlength="800" required></textarea>
              <small class="kbf-desc-counter">0 / 800</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Province</label>
              <select id="kbf-edit-province" required>
                <option value="">Select Province</option>
                <?php foreach (kbf_get_provinces() as $p): ?>
                  <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                <?php endforeach; ?>
              </select>
              <small>Select your province first.</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Municipality</label>
              <select id="kbf-edit-municipality" required disabled>
                <option value="">Select Municipality</option>
              </select>
              <small>Municipality list will load based on province.</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Barangay</label>
              <select id="kbf-edit-barangay" required disabled>
                <option value="">Select Barangay</option>
              </select>
              <small>Barangay list will load based on municipality.</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Deadline</label>
              <input type="date" name="deadline" id="edit-fund-deadline">
              <small>Optional — update the end date.</small>
            </div>
            <div class="kbf-form-group">
              <label class="kbf-checkbox-row">
                <input type="checkbox" name="auto_return" id="edit-fund-auto-return" value="1">
                Auto-return funds to sponsors if goal not met by deadline
              </label>
            </div>
            <input type="hidden" name="location" id="edit-fund-location-hidden">
            <div id="kbf-edit-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-edit')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitEdit()">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Withdrawal ===== -->
    <div id="kbf-modal-wd" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Request Withdrawal</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-wd')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-wd-form">
            <input type="hidden" name="fund_id" id="wd-fund-id">
            <input type="hidden" name="funder_name" value="<?php echo esc_attr(isset($user->display_name) ? $user->display_name : ''); ?>">
            <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:12px 14px;margin-bottom:16px;">
              <div style="font-size:12px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">Fund</div>
              <div id="wd-fund-title" style="font-size:14px;font-weight:700;color:var(--kbf-navy);margin-bottom:6px;"></div>
              <div style="font-size:13px;color:var(--kbf-green);font-weight:700;"><span id="wd-available-label"></span> available</div>
            </div>
            <div class="kbf-form-group">
              <label>Amount to Withdraw (PHP) *</label>
              <input type="number" name="amount" id="wd-amount" placeholder="0.00" min="1" step="0.01" required>
              <small style="color:var(--kbf-slate);font-size:11.5px;">Admin will review and process your request within 1-3 business days.</small>
            </div>
            <div class="kbf-form-group">
              <label>Withdrawal Method *</label>
              <select name="method" required>
                <option value="">Select Method</option>
                <option value="online_payment">Online Payment (GCash / Maya / E-Wallet)</option>
                <option value="bank_payment">Bank Payment (Bank Transfer / Over-the-Counter)</option>
              </select>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group">
                <label>Account Name *</label>
                <input type="text" name="account_name" placeholder="Full name on account" required>
              </div>
              <div class="kbf-form-group">
                <label>Account Number *</label>
                <input type="text" name="account_number" placeholder="e.g. 09XX XXX XXXX" required>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Additional Details</label>
              <textarea name="account_details" rows="2" placeholder="Bank name, branch, or any other details..."></textarea>
            </div>
            <div id="kbf-wd-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-wd')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitWd()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Submit Request
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Appeal Suspension ===== -->
    <div id="kbf-modal-appeal" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Appeal Suspension</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-appeal')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-appeal-form">
            <input type="hidden" name="fund_id" id="kbf-appeal-fund-id">
            <div class="kbf-form-group">
              <label>Appeal Message *</label>
              <textarea name="message" rows="4" placeholder="Explain why this fund should be reinstated..." required></textarea>
            </div>
            <div id="kbf-appeal-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-appeal')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitAppeal('<?php echo $nonce_appeal; ?>')">Submit Appeal</button>
        </div>
      </div>
    </div>

    <!-- Topbar (Landing-style) -->
    <div class="kbf-dashboard-topbar">
      <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <div class="kbf-dashboard-brand">
          <span class="kbf-logo-dot">KB</span>
          KonekBayan
        </div>
        <nav class="kbf-dashboard-nav">
          <a href="?kbf_tab=overview" class="<?php echo $tab==='overview'?'active':''; ?>">Home</a>
          <a href="?kbf_tab=sponsorships" class="<?php echo $tab==='sponsorships'?'active':''; ?>">Supporters</a>
          <a href="?kbf_tab=withdrawals" class="<?php echo $tab==='withdrawals'?'active':''; ?>">Cashout</a>
          <a href="?kbf_tab=find_funds" class="<?php echo $tab==='find_funds'?'active':''; ?>">Explore</a>
        </nav>
      </div>
      <div class="kbf-dashboard-actions">
        <?php
          $avatar_url = ($nav_profile && $nav_profile->avatar_url) ? $nav_profile->avatar_url : get_avatar_url($user->ID, ['size'=>64]);
        ?>
        <a href="?kbf_tab=profile" title="Profile">
          <img class="kbf-dashboard-avatar" src="<?php echo esc_url($avatar_url); ?>" alt="User avatar" id="kbf-navbar-avatar">
        </a>
      </div>
    </div>

    <div class="kbf-hero-wrap">
      <div class="kbf-hero-banner">
        <h2 class="kbf-hero-title">Welcome, <?php echo esc_html($user->display_name); ?></h2>
        <p class="kbf-hero-sub">Empower change today. Oversee and manage your community-driven impact initiatives.</p>
      </div>
      <div class="kbf-hero-grid">
        <div class="kbf-hero-card">
          <span class="kbf-hero-icon">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/search.svg" alt="" width="16" height="16">
          </span>
          <h4>Find Funds</h4>
          <p>Discover verified local causes needing urgent resources and support.</p>
          <a href="?kbf_tab=find_funds" class="kbf-btn kbf-btn-secondary" style="align-self:flex-start;">Browse Initiatives</a>
        </div>
        <div class="kbf-hero-card kbf-hero-primary">
          <span class="kbf-hero-icon">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/plus.svg" alt="" width="16" height="16">
          </span>
          <h4>Start a Fund</h4>
          <p>Launch a fundraising campaign for a verified social good project.</p>
          <button class="kbf-btn kbf-btn-primary" style="background:#ffffff;color:#1f2a44;align-self:flex-start;" onclick="kbfOpenModal('kbf-modal-create')">Get Started</button>
        </div>
      </div>
    </div>
    <div class="kbf-tab-content">
      <?php
      if ($tab === 'overview')         echo kbf_dashboard_overview_tab($business_id);
      elseif ($tab === 'sponsorships') echo kbf_dashboard_sponsorships_tab($business_id);
      elseif ($tab === 'withdrawals')  echo kbf_dashboard_withdrawals_tab($business_id);
      elseif ($tab === 'find_funds')   echo kbf_dashboard_find_funds_tab();
      elseif ($tab === 'profile')      echo kbf_dashboard_profile_tab($business_id);
      elseif ($tab === 'fund_details') echo bntm_shortcode_kbf_fund_details();
      elseif ($tab === 'organizer_profile') echo bntm_shortcode_kbf_organizer_profile();
      elseif ($tab === 'sponsor_history') echo bntm_shortcode_kbf_sponsor_history();
      ?>
    </div>
    </div><!-- .kbf-wrap -->

        <!-- ================== JS ================== -->
    <script>if(typeof ajaxurl==='undefined') var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';</script>
    <script>
    function kbfCloseModal(id) {
        document.getElementById(id).style.display = 'none';
        if (id === 'kbf-modal-create') {
            document.documentElement.classList.remove('kbf-modal-lock');
            document.body.classList.remove('kbf-modal-lock');
        }
    }
    function kbfOpenModal(id)  {
        document.getElementById(id).style.display = 'flex';
        if (id === 'kbf-modal-create') {
            document.documentElement.classList.add('kbf-modal-lock');
            document.body.classList.add('kbf-modal-lock');
        }
    }
    var kbfPsgcData = null;
    var kbfPsgcLoading = false;
    function kbfTitleCase(str){
        return String(str).toLowerCase().replace(/\b\w/g,function(c){return c.toUpperCase();});
    }
    function kbfSetMuniOptions(muniEl, list){
        if (!muniEl) return;
        muniEl.innerHTML = '<option value="">Select Municipality</option>';
        for (var i=0;i<list.length;i++){
            var opt = document.createElement('option');
            opt.value = list[i].label;
            opt.textContent = list[i].label;
            muniEl.appendChild(opt);
        }
    }
    function kbfSetBrgyOptions(brgyEl, list){
        if (!brgyEl) return;
        brgyEl.innerHTML = '<option value="">Select Barangay</option>';
        for (var i=0;i<list.length;i++){
            var opt = document.createElement('option');
            opt.value = list[i];
            opt.textContent = list[i];
            brgyEl.appendChild(opt);
        }
    }
    function kbfBuildMunicipalities(provinceUpper){
        var out = [];
        if (!kbfPsgcData) return out;
        for (var regionKey in kbfPsgcData){
            if (!kbfPsgcData.hasOwnProperty(regionKey)) continue;
            var provList = kbfPsgcData[regionKey].province_list || {};
            if (provList[provinceUpper]) {
                var munList = provList[provinceUpper].municipality_list || [];
                for (var i=0;i<munList.length;i++){
                    var obj = munList[i];
                    for (var muniName in obj){
                        if (obj.hasOwnProperty(muniName)){
                            var barangays = obj[muniName].barangay_list || [];
                            var brgyList = [];
                            for (var b=0;b<barangays.length;b++){
                                brgyList.push(kbfTitleCase(barangays[b]));
                            }
                            out.push({ key: muniName, label: kbfTitleCase(muniName), barangays: brgyList });
                        }
                    }
                }
                break;
            }
        }
        return out;
    }
    function kbfEnsurePsgc(cb){
        if (kbfPsgcData){ cb(); return; }
        if (kbfPsgcLoading) return;
        kbfPsgcLoading = true;
        fetch('<?php echo esc_url(BNTM_KBF_URL . 'data/psgc_2016.json'); ?>')
          .then(function(r){ return r.json(); })
          .then(function(j){ kbfPsgcData = j; cb(); })
          .catch(function(){ kbfPsgcData = null; })
          .finally(function(){ kbfPsgcLoading = false; });
    }
    function kbfInitLocationPicker(provinceEl, muniEl, brgyEl){
        if (!provinceEl || !muniEl || !brgyEl) return;
        var muniData = [];
        function handleProvinceChange(){
            var val = provinceEl.value || '';
            if (!val){
                muniEl.disabled = true;
                brgyEl.disabled = true;
                kbfSetMuniOptions(muniEl, []);
                kbfSetBrgyOptions(brgyEl, []);
                return;
            }
            muniEl.disabled = true;
            brgyEl.disabled = true;
            kbfSetMuniOptions(muniEl, []);
            kbfSetBrgyOptions(brgyEl, []);
            kbfEnsurePsgc(function(){
                muniData = kbfBuildMunicipalities(String(val).toUpperCase());
                kbfSetMuniOptions(muniEl, muniData);
                muniEl.disabled = muniData.length === 0;
            });
        }
        function handleMunicipalityChange(){
            var val = muniEl.value || '';
            if (!val){
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
                return;
            }
            var upperVal = String(val).toUpperCase();
            var found = null;
            for (var i=0;i<muniData.length;i++){
                if (muniData[i].key === upperVal){
                    found = muniData[i];
                    break;
                }
            }
            if (!found){
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
                return;
            }
            kbfSetBrgyOptions(brgyEl, found.barangays);
            brgyEl.disabled = found.barangays.length === 0;
        }
        provinceEl.addEventListener('change', handleProvinceChange);
        muniEl.addEventListener('change', handleMunicipalityChange);
        handleProvinceChange();
        return { handleProvinceChange: handleProvinceChange, handleMunicipalityChange: handleMunicipalityChange };
    }
    function kbfApplyLocationSelection(provinceEl, muniEl, brgyEl, loc){
        if (!provinceEl || !muniEl || !brgyEl) return;
        var parts = String(loc || '').split(',').map(function(p){ return p.trim(); }).filter(Boolean);
        var barangay = parts.length > 0 ? parts[0] : '';
        var municipality = parts.length > 1 ? parts[1] : '';
        var province = parts.length > 2 ? parts[2] : (parts.length === 1 ? parts[0] : (parts.length === 2 ? parts[1] : ''));
        provinceEl.value = province;
        if (!province) {
            muniEl.disabled = true; brgyEl.disabled = true;
            kbfSetMuniOptions(muniEl, []); kbfSetBrgyOptions(brgyEl, []);
            return;
        }
        kbfEnsurePsgc(function(){
            var muniData = kbfBuildMunicipalities(String(province).toUpperCase());
            kbfSetMuniOptions(muniEl, muniData);
            muniEl.disabled = muniData.length === 0;
            if (municipality) muniEl.value = municipality;
            var upperVal = String(muniEl.value || '').toUpperCase();
            var found = null;
            for (var i=0;i<muniData.length;i++){
                if (muniData[i].key === upperVal){
                    found = muniData[i];
                    break;
                }
            }
            if (found){
                kbfSetBrgyOptions(brgyEl, found.barangays);
                brgyEl.disabled = found.barangays.length === 0;
                if (barangay) brgyEl.value = barangay;
            } else {
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
            }
        });
    }
    (function(){
        kbfInitLocationPicker(
            document.getElementById('kbf-province'),
            document.getElementById('kbf-municipality'),
            document.getElementById('kbf-barangay')
        );
        kbfInitLocationPicker(
            document.getElementById('kbf-edit-province'),
            document.getElementById('kbf-edit-municipality'),
            document.getElementById('kbf-edit-barangay')
        );
        kbfInitLocationPicker(
            document.getElementById('kbf-profile-province'),
            document.getElementById('kbf-profile-municipality'),
            document.getElementById('kbf-profile-barangay')
        );
        var profProv = document.getElementById('kbf-profile-province');
        var profMuni = document.getElementById('kbf-profile-municipality');
        var profBrgy = document.getElementById('kbf-profile-barangay');
        var profHidden = document.getElementById('kbf-profile-address');
        if (profProv && profMuni && profBrgy) {
            if (profHidden) {
                kbfApplyLocationSelection(profProv, profMuni, profBrgy, profHidden.value || '');
            }
            var updateProfileAddress = function(){
                if (!profHidden) return;
                var parts = [];
                if (profBrgy.value) parts.push(profBrgy.value);
                if (profMuni.value) parts.push(profMuni.value);
                if (profProv.value) parts.push(profProv.value);
                profHidden.value = parts.join(', ');
            };
            profProv.addEventListener('change', updateProfileAddress);
            profMuni.addEventListener('change', updateProfileAddress);
            profBrgy.addEventListener('change', updateProfileAddress);
        }
    })();
    function kbfSetBtnLoading(btn, on, label) {
        if (!btn) return;
        if (on) {
            btn.dataset.kbfLabel = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = label || 'Loading...';
        } else {
            btn.disabled = false;
            if (btn.dataset.kbfLabel) btn.innerHTML = btn.dataset.kbfLabel;
        }
    }
    function kbfSetSkeleton(el, on) {
        if (!el) return;
        if (on) {
            el.dataset.kbfPrev = el.innerHTML;
            el.innerHTML =
                '<div class="kbf-skeleton kbf-skel-line lg"></div>' +
                '<div class="kbf-skeleton kbf-skel-line md"></div>' +
                '<div class="kbf-skeleton kbf-skel-line sm"></div>' +
                '<div class="kbf-skeleton kbf-skel-box"></div>';
        } else if (el.dataset.kbfPrev !== undefined) {
            el.innerHTML = el.dataset.kbfPrev;
            delete el.dataset.kbfPrev;
        }
    }

    function kbfSetFieldError(field, message) {
        var group = field.closest('.kbf-form-group');
        if (!group) return;
        var err = group.querySelector('.kbf-field-error');
        if (!err) {
            err = document.createElement('div');
            err.className = 'kbf-field-error';
            group.appendChild(err);
        }
        err.textContent = message;
    }

    function kbfClearFieldError(field) {
        var group = field.closest('.kbf-form-group');
        if (!group) return;
        var err = group.querySelector('.kbf-field-error');
        if (err) err.textContent = '';
    }

    function kbfValidateEditFund(form) {
        var fields = [
            document.getElementById('edit-fund-title'),
            document.getElementById('edit-fund-desc')
        ];
        var firstInvalid = null;
        for (var i = 0; i < fields.length; i++) {
            var f = fields[i];
            if (!f) continue;
            var valid = String(f.value || '').trim() !== '';
            if (!valid) {
                if (!firstInvalid) firstInvalid = f;
                kbfSetFieldError(f, 'This field is required.');
            } else {
                kbfClearFieldError(f);
            }
        }
        return firstInvalid;
    }

    function kbfValidateCreateForm(form) {
        var fields = form.querySelectorAll('input, select, textarea');
        var firstInvalid = null;
        for (var i=0; i<fields.length; i++) {
            var f = fields[i];
            if (f.disabled) continue;
            if (f.hasAttribute('required')) {
                var valid = true;
                if (f.type === 'file') {
                    valid = f.files && f.files.length > 0;
                } else if (f.type === 'checkbox') {
                    valid = f.checked;
                } else {
                    valid = String(f.value || '').trim() !== '';
                }
                if (!valid) {
                    if (!firstInvalid) firstInvalid = f;
                    kbfSetFieldError(f, 'This field is required.');
                } else {
                    kbfClearFieldError(f);
                }
            } else {
                kbfClearFieldError(f);
            }
        }
        return firstInvalid;
    }

    function kbfSetLoadingPage(on) {
        var el = document.getElementById('kbf-loading-overlay');
        if (!el) return;
        el.style.display = on ? 'flex' : 'none';
    }

    function kbfInitDescCounter(textarea){
        if (!textarea) return;
        var counter = textarea.parentNode.querySelector('.kbf-desc-counter');
        function update(){
            if (!counter) return;
            var len = (textarea.value || '').length;
            counter.textContent = len + ' / 800';
        }
        textarea.addEventListener('input', update);
        update();
    }
    function kbfInitTitleCounter(input){
        if (!input) return;
        var counter = input.parentNode.querySelector('.kbf-title-counter');
        function update(){
            if (!counter) return;
            var len = (input.value || '').length;
            counter.textContent = len + ' / 80';
        }
        input.addEventListener('input', update);
        update();
    }
    (function(){
        kbfInitDescCounter(document.querySelector('#kbf-create-fund-form textarea[name="description"]'));
        kbfInitDescCounter(document.getElementById('edit-fund-desc'));
        kbfInitTitleCounter(document.querySelector('#kbf-create-fund-form input[name="title"]'));
        kbfInitTitleCounter(document.getElementById('edit-fund-title'));
        var funderSelect = document.querySelector('#kbf-create-fund-form select[name="funder_type"]');
        var titleInput = document.getElementById('kbf-create-title');
        if (funderSelect && titleInput) {
            var placeholders = {
                yourself: 'e.g., Help with my medical bills',
                someone_else: 'e.g., Support Maria’s recovery',
                charity_event: 'e.g., Barangay relief drive 2026'
            };
            var updatePlaceholder = function(){
                var key = funderSelect.value || 'yourself';
                titleInput.placeholder = placeholders[key] || 'Clear, compelling title';
            };
            funderSelect.addEventListener('change', updatePlaceholder);
            updatePlaceholder();
        }
    })();

    function kbfSubmitCreate() {
        const form = document.getElementById('kbf-create-fund-form');
        const btn  = document.querySelector('#kbf-modal-create .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-create-msg');
        const invalid = kbfValidateCreateForm(form);
        if (invalid) {
            invalid.focus();
            return;
        }
        kbfSetLoadingPage(true);
        kbfSetBtnLoading(btn, true, 'Submitting...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        var provEl = document.getElementById('kbf-province');
        var muniEl = document.getElementById('kbf-municipality');
        var brgyEl = document.getElementById('kbf-barangay');
        var prov = provEl ? provEl.value : '';
        var muni = muniEl ? muniEl.value : '';
        var brgy = brgyEl ? brgyEl.value : '';
        var parts = [];
        if (brgy) parts.push(brgy);
        if (muni) parts.push(muni);
        if (prov) parts.push(prov);
        if (parts.length) fd.append('location_full', parts.join(', '));
        fd.append('action', 'kbf_create_fund');
        fd.append('nonce', '<?php echo $nonce_create; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-create-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json && (json.success === true || json.success === 1 || json.success === '1')) {
                kbfCloseModal('kbf-modal-create');
                var modal = document.getElementById('kbf-modal-create');
                if (modal) modal.style.display = 'none';
                document.documentElement.classList.remove('kbf-modal-lock');
                document.body.classList.remove('kbf-modal-lock');
                setTimeout(()=>location.reload(), 1800);
            }
            else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetLoadingPage(false); kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

    function kbfSubmitEdit() {
        const form = document.getElementById('kbf-edit-fund-form');
        const btn  = document.querySelector('#kbf-modal-edit .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-edit-msg');
        const invalid = kbfValidateEditFund(form);
        if (invalid) {
            invalid.focus();
            return;
        }
        kbfSetBtnLoading(btn, true, 'Saving...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        var eProv = document.getElementById('kbf-edit-province');
        var eMuni = document.getElementById('kbf-edit-municipality');
        var eBrgy = document.getElementById('kbf-edit-barangay');
        var eProvVal = eProv ? eProv.value : '';
        var eMuniVal = eMuni ? eMuni.value : '';
        var eBrgyVal = eBrgy ? eBrgy.value : '';
        var eParts = [];
        if (eBrgyVal) eParts.push(eBrgyVal);
        if (eMuniVal) eParts.push(eMuniVal);
        if (eProvVal) eParts.push(eProvVal);
        if (eParts.length) fd.append('location_full', eParts.join(', '));
        fd.append('action', 'kbf_update_fund');
        fd.append('nonce', '<?php echo $nonce_edit; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-edit-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json.success) setTimeout(()=>location.reload(), 1500);
            else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

    function kbfSubmitWd() {
        const form = document.getElementById('kbf-wd-form');
        const btn  = document.querySelector('#kbf-modal-wd .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-wd-msg');
        kbfSetBtnLoading(btn, true, 'Submitting...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        fd.append('action', 'kbf_request_withdrawal');
        fd.append('nonce', '<?php echo $nonce_wd; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-wd-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json.success) setTimeout(()=>location.reload(), 1500);
            else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

    function kbfSubmitAppeal(nonce) {
        const form = document.getElementById('kbf-appeal-form');
        const btn  = document.querySelector('#kbf-modal-appeal .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-appeal-msg');
        kbfSetBtnLoading(btn, true, 'Submitting...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        fd.append('action', 'kbf_submit_appeal');
        fd.append('nonce', nonce);
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            msg.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if (json.success) setTimeout(()=>{ kbfCloseModal('kbf-modal-appeal'); }, 1600);
            else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

    window.kbfOpenEdit = function(id, title, desc, loc, deadline, autoReturn) {
        document.getElementById('edit-fund-id').value = id;
        document.getElementById('edit-fund-title').value = title;
        document.getElementById('edit-fund-desc').value = desc;
        var hiddenLoc = document.getElementById('edit-fund-location-hidden');
        if (hiddenLoc) hiddenLoc.value = loc || '';
        var titleCounter = document.getElementById('edit-fund-title').parentNode.querySelector('.kbf-title-counter');
        if (titleCounter) titleCounter.textContent = (title || '').length + ' / 80';
        var deadlineEl = document.getElementById('edit-fund-deadline');
        if (deadlineEl) deadlineEl.value = deadline || '';
        var autoEl = document.getElementById('edit-fund-auto-return');
        if (autoEl) autoEl.checked = String(autoReturn) === '1';
        kbfApplyLocationSelection(
            document.getElementById('kbf-edit-province'),
            document.getElementById('kbf-edit-municipality'),
            document.getElementById('kbf-edit-barangay'),
            loc
        );
        var counter = document.getElementById('edit-fund-desc').parentNode.querySelector('.kbf-desc-counter');
        if (counter) counter.textContent = (desc || '').length + ' / 800';
        kbfOpenModal('kbf-modal-edit');
    };

    window.kbfOpenWd = function(fundId, available, title) {
        document.getElementById('wd-fund-id').value = fundId;
        document.getElementById('wd-fund-title').textContent = title || 'Fund #'+fundId;
        document.getElementById('wd-available-label').textContent = '₱' + parseFloat(available).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        document.getElementById('wd-amount').max = available;
        document.getElementById('wd-amount').placeholder = 'Max ₱'+parseFloat(available).toLocaleString('en-PH',{minimumFractionDigits:2});
        document.getElementById('kbf-wd-msg').innerHTML = '';
        document.getElementById('kbf-wd-form').reset();
        document.getElementById('wd-fund-id').value = fundId; // re-set after reset
        kbfOpenModal('kbf-modal-wd');
    };

    window.kbfOpenAppeal = function(fundId, title) {
        document.getElementById('kbf-appeal-fund-id').value = fundId;
        document.getElementById('kbf-appeal-msg').innerHTML = '';
        kbfOpenModal('kbf-modal-appeal');
    };

    window.kbfCancelFund = function(fundId) {
        if (!confirm('Cancel this fund? This cannot be undone.')) return;
        const fd = new FormData();
        fd.append('action','kbf_cancel_fund'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo $nonce_cancel; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };

    window.kbfExtendDeadline = function(fundId) {
        const d = prompt('New deadline (YYYY-MM-DD):'); if(!d) return;
        const fd = new FormData();
        fd.append('action','kbf_extend_deadline'); fd.append('fund_id',fundId);
        fd.append('deadline',d); fd.append('nonce','<?php echo $nonce_extend; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };


    window.kbfMarkComplete = function(fundId) {
        if(!confirm('Mark this fund as complete?')) return;
        const fd = new FormData();
        fd.append('action','kbf_mark_fund_complete'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo wp_create_nonce('kbf_cancel_fund'); ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };
    </script>
    <?php
    $content = ob_get_clean();
    return bntm_universal_container('KonekBayan -- Finding Platform', $content, ['show_topbar'=>false,'show_header'=>false]);
}


// ============================================================
// DASHBOARD TAB: Overview
// ============================================================

function kbf_dashboard_admin_embed() {
    if (!current_user_can('manage_options')) return '';
    // Reuse all admin tab functions directly -- they share the same JS
    // already loaded by bntm_shortcode_kbf_admin, but here we need
    // to output the admin JS inline since we're inside the dashboard.
    global $wpdb;
    $adm_tab = isset($_GET['adm_tab']) ? sanitize_text_field($_GET['adm_tab']) : 'pending';
    $nonce   = wp_create_nonce('kbf_admin_action');

    ob_start();
    ?>
    <!-- ================== JS ================== -->
    <script>
    var _kbfAdminNonce='<?php echo $nonce; ?>';
    if(typeof window.kbfAdmin==='undefined'){
        window.kbfAdmin=function(action,params){
            const fd=new FormData();fd.append('action',action);fd.append('_ajax_nonce',_kbfAdminNonce);
            Object.keys(params).forEach(k=>fd.append(k,params[k]));
            return fetch((window.ajaxurl||'<?php echo admin_url('admin-ajax.php'); ?>'),{method:'POST',body:fd})
            .then(async r=>{
                const text = await r.text();
                let j = null;
                try {
                    const cleaned = String(text || '').trim();
                    const start = cleaned.indexOf('{');
                    const end = cleaned.lastIndexOf('}');
                    const payload = (start !== -1 && end !== -1 && end > start) ? cleaned.slice(start, end + 1) : cleaned;
                    j = JSON.parse(payload);
                } catch(e) {}
                if(!j){
                    console.error('kbfAdmin raw response:', text);
                    throw new Error('Server returned non-JSON response.');
                }
                return j;
            }).then(j=>{
                alert((j.data&&j.data.message)?j.data.message:(j.data||'Done.'));
                if(j.success)location.reload();
            }).catch(err=>{
                alert('Request failed. Please try again.');
                console.error('kbfAdmin error:', err);
                console.log('kbfAdmin action:', action, 'params:', params);
            });
        };
        window.kbfApprove     = function(id){if(!confirm('Approve this fund?'))return;kbfAdmin('kbf_admin_approve_fund',{fund_id:id});};
        window.kbfReject      = function(id){const r=prompt('Reason for rejection (optional):');if(r===null)return;kbfAdmin('kbf_admin_reject_fund',{fund_id:id,reason:r});};
        window.kbfSuspend     = function(id){if(!confirm('Suspend this fund?'))return;kbfAdmin('kbf_admin_suspend_fund',{fund_id:id});};
        window.kbfVerifyBadge = function(id,cur){kbfAdmin('kbf_admin_verify_badge',{fund_id:id,verified:cur?'0':'1'});};
        window.kbfEscrow      = function(id,act){kbfAdmin('kbf_admin_'+act+'_escrow',{fund_id:id});};
        window.kbfDismissReport  = function(id){kbfAdmin('kbf_admin_dismiss_report',{report_id:id});};
        window.kbfReviewReport   = function(id){const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_report',{report_id:id,notes:n});};
        window.kbfReviewAppeal   = function(id,action){const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_appeal',{appeal_id:id,action_type:action,notes:n});};
        window.kbfProcessWd      = function(id,type){if(type==='reject'){const r=prompt('Reason:');if(!r)return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'reject',notes:r});}else{if(!confirm('Approve & release?'))return;kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'approve'});}};
        window.kbfConfirmPayment = function(id){if(!confirm('Mark as paid?'))return;kbfAdmin('kbf_admin_confirm_payment',{sponsorship_id:id});};
        window.kbfVerifyOrg      = function(id,cur){kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:cur?'0':'1'});};
    } else {
        // Already defined -- just refresh the nonce value
        _kbfAdminNonce = '<?php echo $nonce; ?>';
    }
    </script>

    <?php
    $pending_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_funds WHERE status='pending'"); // phpcs:ignore
    $reports_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_reports WHERE status='open'"); // phpcs:ignore
    $wd_count      = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_withdrawals WHERE status='pending'"); // phpcs:ignore
    $counts = ['pending'=>$pending_count,'reports'=>$reports_count,'withdrawals'=>$wd_count];
    $adm_tabs = ['pending'=>'Pending Funds','all_funds'=>'All Funds','transactions'=>'Transactions','withdrawals'=>'Withdrawals','reports'=>'Reports','appeals'=>'Appeals','organizers'=>'Organizers','settings'=>'Settings'];
    ?>

    <div style="margin:-28px -28px 0;background:var(--kbf-navy);border-radius:0;">
      <div style="padding:20px 28px 0;display:flex;align-items:center;gap:10px;">
        <svg width="16" height="16" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
        <span style="color:#fff;font-weight:700;font-size:15px;">Admin Panel</span>
      </div>
      <div class="kbf-tabs" style="border-radius:0;padding:0 28px;">
        <?php foreach($adm_tabs as $k=>$label): ?>
        <a href="?kbf_tab=admin&adm_tab=<?php echo $k; ?>" class="kbf-tab <?php echo $adm_tab===$k?'active':''; ?>">
          <?php echo $label; ?>
          <?php if(!empty($counts[$k])&&$counts[$k]>0): ?>
            <span style="background:var(--kbf-red);color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;font-weight:800;line-height:1.5;"><?php echo $counts[$k]; ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div style="margin-top:24px;">
      <?php
      if     ($adm_tab==='pending')      echo kbf_admin_pending_tab();
      elseif ($adm_tab==='all_funds')    echo kbf_admin_all_funds_tab();
      elseif ($adm_tab==='transactions') echo kbf_admin_transactions_tab();
      elseif ($adm_tab==='withdrawals')  echo kbf_admin_withdrawals_tab();
      elseif ($adm_tab==='reports')      echo kbf_admin_reports_tab();
      elseif ($adm_tab==='appeals')      echo kbf_admin_appeals_tab();
      elseif ($adm_tab==='organizers')   echo kbf_admin_organizers_tab();
      elseif ($adm_tab==='settings')     echo kbf_admin_settings_tab();
      ?>
    </div>
    <?php
    return ob_get_clean();
}

// ============================================================
// PUBLIC BROWSE SHORTCODE
// ============================================================

function bntm_shortcode_kbf_browse() {
    kbf_global_assets();
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $q   = isset($_GET['q'])   ? sanitize_text_field($_GET['q'])   : '';
    $cat = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : '';
    $loc = isset($_GET['loc']) ? sanitize_text_field($_GET['loc']) : '';
    $sort= isset($_GET['sort'])? sanitize_text_field($_GET['sort']): 'newest';

    $where='WHERE f.status=\'active\''; $params=[];
    if($q)  { $where.=" AND (f.title LIKE %s OR f.description LIKE %s)"; $params[]="%".$wpdb->esc_like($q)."%"; $params[]="%".$wpdb->esc_like($q)."%"; }
    if($cat){ $where.=" AND f.category=%s"; $params[]=$cat; }
    if($loc){ $where.=" AND f.location LIKE %s"; $params[]="%".$wpdb->esc_like($loc)."%"; }
    $order = $sort==='most_funded' ? 'f.raised_amount DESC' : ($sort==='ending_soon' ? 'f.deadline ASC' : 'f.created_at DESC');
    $sql="SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID {$where} ORDER BY {$order}";
    $funds = !empty($params)?$wpdb->get_results($wpdb->prepare($sql,...$params)):$wpdb->get_results($sql); // phpcs:ignore
    $cats  = kbf_get_categories();
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $fund_details_url = kbf_get_page_url('fund_details');
    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);
    $total_active = count($funds);
    ob_start();
    ?>
    <!-- ================== CSS ================== -->
    <style>
    .kbf-user-ui{
        font-family:'Poppins',system-ui,-apple-system,sans-serif;
        color:#0f1115;
        background:#ffffff;
        border-radius:18px;
        padding:22px 18px 28px;
    }
    .kbf-user-ui .kbf-card,
    .kbf-user-ui .kbf-fund-card,
    .kbf-user-ui .kbf-filter-bar{
        border-radius:18px;
        border:1px solid #edf0f4;
        box-shadow:0 18px 40px rgba(16,24,40,0.08);
    }
    .kbf-user-ui .kbf-btn-primary{
        background:linear-gradient(135deg,#79c0ff 0%,#6fb6ff 45%,#4a98ff 100%);
        color:#ffffff;
        box-shadow:0 10px 22px rgba(111,182,255,0.35);
    }
    .kbf-user-ui .kbf-btn-primary:hover{
        transform:translateY(-1px);
        box-shadow:0 14px 30px rgba(90,160,255,0.45),0 0 0 6px rgba(111,182,255,0.15);
        filter:saturate(1.05);
    }
    .kbf-user-ui .kbf-btn-secondary{
        background:#ffffff;
        border:1px solid #dfe7f3;
        color:#344055;
    }
    .kbf-user-ui .kbf-meta,
    .kbf-user-ui .kbf-browse-tab,
    .kbf-user-ui .kbf-browse-search input{
        color:#4f5a6b;
    }
    .kbf-browse-shell{display:grid;grid-template-columns:84px 1fr;gap:22px;}
    .kbf-browse-sidebar{background:var(--kbf-slate-lt);border:1px solid var(--kbf-border);border-radius:18px;padding:16px;display:flex;flex-direction:column;align-items:center;gap:16px;min-height:620px;position:sticky;top:24px;}
    .kbf-browse-sidebar .kbf-side-logo{width:48px;height:48px;border-radius:14px;background:var(--kbf-accent-lt);color:var(--kbf-navy);display:flex;align-items:center;justify-content:center;font-weight:800;}
    .kbf-side-btn{width:44px;height:44px;border-radius:14px;border:1px solid var(--kbf-border);display:flex;align-items:center;justify-content:center;color:var(--kbf-slate);background:#fff;}
    .kbf-side-btn.active{background:var(--kbf-accent);color:#fff;border-color:var(--kbf-accent);}
    .kbf-browse-main{min-width:0;}
    .kbf-browse-topbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px;}
    .kbf-browse-search{flex:1;max-width:520px;display:flex;align-items:center;gap:8px;background:var(--kbf-slate-lt);border:1px solid var(--kbf-border);border-radius:14px;padding:10px 14px;}
    .kbf-browse-search input{border:none;background:transparent;outline:none;width:100%;font-size:13.5px;color:var(--kbf-text);}
    .kbf-browse-actions{display:flex;gap:10px;align-items:center;}
    .kbf-browse-header{margin:6px 0 8px;}
    .kbf-browse-header h2{margin:0;font-size:24px;font-weight:800;color:var(--kbf-navy);}
    .kbf-browse-tabs{display:flex;gap:14px;align-items:center;margin:12px 0 16px;flex-wrap:wrap;}
    .kbf-browse-tab{font-size:13px;font-weight:600;color:var(--kbf-slate);text-decoration:none;position:relative;padding-bottom:6px;}
    .kbf-browse-tab.active{color:var(--kbf-navy);}
    .kbf-browse-tab.active::after{content:'';position:absolute;left:0;bottom:0;width:18px;height:4px;border-radius:999px;background:var(--kbf-accent);}
    .kbf-fund-card{background:#fff;border:1px solid var(--kbf-border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .2s,transform .15s;}
    .kbf-fund-card:hover{box-shadow:0 8px 28px rgba(15,32,68,.13);transform:translateY(-2px);}
    .kbf-fund-photo{width:100%;height:190px;object-fit:cover;background:linear-gradient(135deg,var(--kbf-navy),var(--kbf-navy-light));display:flex;align-items:center;justify-content:center;position:relative;}
    .kbf-fund-photo-placeholder{width:100%;height:190px;background:linear-gradient(135deg,#0f2044 0%,#243b78 100%);display:flex;align-items:center;justify-content:center;position:relative;}
    .kbf-fund-photo img{width:100%;height:190px;object-fit:cover;display:block;}
    .kbf-fund-cat-badge{position:absolute;top:12px;left:12px;background:rgba(15,32,68,.85);backdrop-filter:blur(4px);color:var(--kbf-accent);padding:4px 10px;border-radius:99px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
    .kbf-fund-days-badge{position:absolute;top:12px;right:12px;padding:4px 10px;border-radius:99px;font-size:10.5px;font-weight:800;}
    .kbf-filter-bar{background:#fff;border:1px solid var(--kbf-border);border-radius:var(--kbf-radius);padding:16px 18px;margin-bottom:18px;}
    .kbf-sort-pills{display:flex;gap:6px;flex-wrap:wrap;}
    .kbf-sort-pill{padding:6px 14px;border-radius:99px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid var(--kbf-border);color:var(--kbf-slate);transition:all .15s;}
    .kbf-sort-pill:hover,.kbf-sort-pill.active{background:var(--kbf-navy);color:#fff;border-color:var(--kbf-navy);}
    .kbf-browse-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:22px;}
    @media(max-width:900px){.kbf-browse-shell{grid-template-columns:1fr;}.kbf-browse-sidebar{position:static;min-height:auto;flex-direction:row;justify-content:space-between;padding:12px;}}
    @media(max-width:640px){.kbf-browse-grid{grid-template-columns:1fr;}.kbf-browse-search{max-width:100%;}}
    </style>
    
    
    

    
    <!-- ================== CSS ================== -->
    <style>
    .kbf-user-ui{
        font-family:'Poppins',system-ui,-apple-system,sans-serif;
        color:#0f1115;
        background:#ffffff;
        border-radius:18px;
        padding:22px 18px 28px;
        border:none;
        box-shadow:none;
    }
    .kbf-user-ui .kbf-btn{font-weight:400;}
    .kbf-user-ui .kbf-card,
    .kbf-user-ui .kbf-fund-card,
    .kbf-user-ui .kbf-filter-bar{
        border-radius:18px;
        border:1px solid #edf0f4;
        box-shadow:0 18px 40px rgba(16,24,40,0.08);
        transition:transform .2s ease, box-shadow .2s ease;
    }
    .kbf-user-ui .kbf-card:hover,
    .kbf-user-ui .kbf-fund-card:hover{
        transform:translateY(-3px);
        box-shadow:0 16px 34px rgba(15,23,42,0.08);
    }
    .kbf-user-ui .kbf-btn-primary{
        background:linear-gradient(135deg,#79c0ff 0%,#6fb6ff 45%,#4a98ff 100%);
        color:#ffffff;
        box-shadow:0 10px 22px rgba(111,182,255,0.35);
    }
    .kbf-user-ui .kbf-btn-primary:hover{
        background:linear-gradient(135deg,#79c0ff 0%,#6fb6ff 45%,#4a98ff 100%);
        color:#ffffff;
        transform:translateY(-1px);
        box-shadow:0 14px 30px rgba(90,160,255,0.45),0 0 0 6px rgba(111,182,255,0.15);
        filter:saturate(1.05);
    }
    .kbf-user-ui .kbf-btn-secondary{
        background:#ffffff;
        border:1px solid #dfe7f3;
        color:#344055;
    }
    .kbf-user-ui .kbf-meta,
    .kbf-user-ui .kbf-text-sm,
    .kbf-user-ui .kbf-browse-tab,
    .kbf-user-ui .kbf-browse-search input{
        color:#4f5a6b;
    }
    .kbf-dashboard-topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        padding:8px 4px 14px;
        background:transparent;
        border:none;
        border-radius:0;
        box-shadow:none;
        margin-bottom:10px;
    }
    .kbf-dashboard-brand{
        display:flex;
        align-items:center;
        gap:10px;
        font-weight:800;
        color:#0f172a;
        font-size:15px;
    }
    .kbf-dashboard-brand .kbf-logo-dot{
        width:26px;height:26px;border-radius:10px;
        background:linear-gradient(135deg,#79c0ff 0%,#4a98ff 100%);
        display:inline-flex;align-items:center;justify-content:center;
        color:#fff;font-weight:800;font-size:12px;
        box-shadow:0 8px 18px rgba(79,147,255,0.35);
    }
    .kbf-dashboard-nav{
        display:flex;
        gap:18px;
        font-size:12.5px;
        color:var(--kbf-muted);
        align-items:center;
        flex-wrap:wrap;
    }
    .kbf-dashboard-nav a{
        position:relative;
        text-decoration:none;
        color:#64748b;
        font-weight:600;
    }
    .kbf-dashboard-nav a::after{
        content:'';
        position:absolute;
        left:0;
        bottom:-8px;
        width:0;
        height:2px;
        border-radius:999px;
        background:#4a98ff;
        transition:width .2s ease;
    }
    .kbf-dashboard-nav a:hover::after,
    .kbf-dashboard-nav a.active::after{ width:100%; }
    .kbf-dashboard-nav a.active{ color:#1f2a44; }
    .kbf-dashboard-actions{
        display:flex;align-items:center;gap:10px;
    }
    .kbf-dashboard-avatar{
        width:34px;height:34px;border-radius:50%;
        border:2px solid #e5efff;object-fit:cover;
        box-shadow:0 8px 18px rgba(16,24,40,0.12);
    }
    .kbf-hero-wrap{
        padding:10px 6px 0;
    }
    .kbf-hero-banner{
        background:linear-gradient(135deg,#edf4ff 0%,#ffffff 50%,#e5f0ff 100%);
        background-image:
          radial-gradient(140% 160% at 0% 0%, rgba(79,147,255,0.28) 0%, rgba(79,147,255,0) 55%),
          radial-gradient(140% 160% at 100% 0%, rgba(161,210,255,0.30) 0%, rgba(161,210,255,0) 55%),
          linear-gradient(135deg,#edf4ff 0%,#ffffff 50%,#e5f0ff 100%);
        border:1.5px solid #cfe0f7;
        border-radius:22px;
        padding:22px 24px;
        box-shadow:none;
        margin-bottom:18px;
    }
    .kbf-user-ui,
    .kbf-hero-banner{
        background-image:none !important;
    }
    .kbf-user-ui{
        border:none !important;
        box-shadow:none !important;
    }
    .kbf-hero-title{
        font-size:28px;
        font-weight:800;
        color:#0f172a;
        margin:0 0 6px;
    }
    .kbf-hero-sub{
        color:#4b5563;
        font-size:13.5px;
        margin:0 0 18px;
        max-width:520px;
    }
    .kbf-hero-grid{
        display:grid;
        grid-template-columns:1.2fr 1fr;
        gap:18px;
        margin-bottom:20px;
    }
    .kbf-hero-card{
        background:linear-gradient(180deg,#ffffff 0%,#f7faff 100%);
        border:1.5px solid #dfe7f3;
        border-radius:20px;
        padding:18px 20px;
        box-shadow:none;
        display:flex;
        flex-direction:column;
        gap:10px;
        min-height:150px;
        position:relative;
        overflow:hidden;
    }
    .kbf-hero-card.kbf-hero-primary{
        background:linear-gradient(135deg,#2f7bdc 0%,#4a98ff 100%);
        border:1.5px solid #8cc0ff;
        color:#fff;
    }
    .kbf-hero-card h4{
        margin:0;
        font-size:16px;
        font-weight:700;
    }
    .kbf-hero-card p{
        margin:0;
        font-size:12.5px;
        color:inherit;
        opacity:.9;
    }
    .kbf-hero-card .kbf-hero-icon{
        width:34px;height:34px;border-radius:12px;
        display:inline-flex;align-items:center;justify-content:center;
        background:#eef4ff;color:#1f2a44;
    }
    .kbf-hero-card.kbf-hero-primary .kbf-hero-icon{
        background:rgba(255,255,255,.2);
        color:#fff;
    }
    @media (max-width: 900px){
        .kbf-hero-grid{ grid-template-columns:1fr; }
        .kbf-dashboard-topbar{ flex-wrap:wrap; }
    }
    .kbf-user-ui .kbf-modal-overlay{
        position:fixed;
        inset:0;
        display:flex;
        align-items:center;
        justify-content:center;
        background:rgba(11,20,38,0.45);
        backdrop-filter:blur(6px);
        padding:24px;
        z-index:9999;
    }
    .kbf-user-ui .kbf-modal{
        width:100%;
        max-width:720px;
        background:#fff;
        border-radius:22px;
        border:1px solid #dfe7f3;
        box-shadow:0 30px 80px rgba(15,40,80,0.22);
        overflow:hidden;
        max-height:90vh;
        display:flex;
        flex-direction:column;
    }
    .kbf-user-ui .kbf-modal.kbf-modal-sm{
        max-width:520px;
    }
    .kbf-user-ui .kbf-modal-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:18px 20px;
        background:
            radial-gradient(520px 200px at 10% -40%, rgba(111,182,255,0.25), transparent 70%),
            #f8fbff;
        border-bottom:1px solid #edf0f4;
    }
    .kbf-user-ui .kbf-modal-header h3{
        margin:0;
        font-size:16px;
        font-weight:600;
        color:#0d1a2e;
    }
    .kbf-user-ui .kbf-modal-close{
        width:32px;
        height:32px;
        border-radius:50%;
        border:1px solid #dfe7f3;
        background:#fff;
        color:#6b7a90;
        font-size:18px;
        line-height:1;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        transition:transform .15s ease, box-shadow .2s ease;
    }
    .kbf-user-ui .kbf-modal-close:hover{
        transform:translateY(-1px);
        box-shadow:0 8px 18px rgba(15,40,80,0.12);
    }
    .kbf-user-ui .kbf-modal-body{
        padding:20px;
        background:#fff;
        overflow-y:auto;
        max-height:70vh;
    }
    .kbf-user-ui .kbf-modal-footer{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        padding:16px 20px 20px;
        background:#fbfcff;
        border-top:1px solid #edf0f4;
    }
    .kbf-user-ui .kbf-modal input,
    .kbf-user-ui .kbf-modal select,
    .kbf-user-ui .kbf-modal textarea{
        border-radius:12px;
        border:1.5px solid #e2e8f0;
        background:#fff;
    }
    .kbf-loading-overlay{
        position:fixed;
        inset:0;
        display:flex;
        align-items:center;
        justify-content:center;
        background:rgba(11,20,38,0.55);
        backdrop-filter:blur(6px);
        z-index:10000;
    }
    .kbf-loading-card{
        background:#ffffff;
        border:1px solid #dfe7f3;
        border-radius:22px;
        padding:24px 28px;
        box-shadow:0 30px 80px rgba(15,40,80,0.22);
        text-align:center;
        min-width:220px;
    }
    .kbf-loading-spinner{
        width:34px;
        height:34px;
        border-radius:50%;
        border:4px solid rgba(111,182,255,0.25);
        border-top-color:#6fb6ff;
        margin:0 auto 12px;
        animation:kbfSpin .8s linear infinite;
    }
    @keyframes kbfSpin { to { transform: rotate(360deg); } }
    .kbf-user-ui .kbf-field-error{
        margin-top:6px;
        font-size:11.5px;
        color:#e11d48;
    }
    .kbf-user-ui .kbf-desc-counter{
        display:block;
        margin-top:6px;
        font-size:11.5px;
        color:#4f5a6b;
    }
    .kbf-user-ui .kbf-char-count{
        display:block;
        margin-top:6px;
        font-size:11.5px;
        color:#4f5a6b;
    }
    .kbf-user-ui .kbf-title-counter{
        display:block;
        margin-top:6px;
        font-size:11.5px;
        color:#4f5a6b;
    }
    html.kbf-modal-lock, body.kbf-modal-lock { overflow: hidden; }
    </style>

    <!-- ================== HTML ================== -->
    <div class="kbf-wrap kbf-user-ui">

    <?php echo kbf_role_nav('sponsor'); ?>

    <!-- MODAL: Sponsor -->
    <div id="kbf-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header"><h3>Sponsor This Fund</h3><button class="kbf-modal-close" onclick="kbfSponsorClose()">&times;</button></div>
        <div class="kbf-modal-body">
          <div id="kbf-fund-preview" style="background:var(--kbf-slate-lt);border-radius:8px;padding:14px;margin-bottom:18px;"></div>
          <form id="kbf-sponsor-form" onsubmit="return false;">
            <input type="hidden" name="fund_id" id="sponsor-fund-id">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Organization</label><input type="text" name="sponsor_name" id="sponsor-name-field" placeholder="Your name, company, or org"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;">
                <label class="kbf-checkbox-row"><input type="checkbox" id="anon-check" onchange="document.getElementById('sponsor-name-field').disabled=this.checked"> Sponsor Anonymously</label>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Amount (PHP) *</label>
              <input type="number" name="amount" placeholder="Enter amount" min="10" step="1" required>
              <div id="kbf-sponsor-limit" class="kbf-meta" style="margin-top:4px;"></div>
            </div>
            <div class="kbf-form-group"><label>Message (optional)</label><textarea name="message" rows="2" placeholder="Leave an encouraging message..."></textarea></div>
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Email (for receipt)</label><input type="email" name="email" placeholder="your@email.com"></div>
              <div class="kbf-form-group"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX"></div>
            </div>
            <input type="hidden" name="payment_method" value="online_payment">
            <?php if($demo_mode): ?>
            <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;margin-top:4px;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/exclamation-triangle-fill.svg" alt="" width="16" height="16" style="flex-shrink:0;margin-top:1px;filter:invert(31%) sepia(86%) saturate(1160%) hue-rotate(16deg) brightness(95%) contrast(95%);">
              <div><strong>Demo Mode:</strong> Redirects to Maya sandbox checkout. No real payment is processed.</div>
            </div>
            <?php else: ?>
            <div id="kbf-payment-placeholder" class="kbf-payment-placeholder" style="display:none;"><strong>Payment API Integration Point</strong><span id="kbf-payment-label"></span><br><small>Hook: <code>do_action('kbf_process_payment', $method, $amount, $fund_id)</code></small></div>
            <?php endif; ?>
            <div id="kbf-sponsor-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfSponsorClose()">Cancel</button>
          <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSubmitSponsor('<?php echo $nonce_sponsor; ?>')">Confirm Sponsorship</button>
        </div>
      </div>
    </div>

    <!-- MODAL: Report -->
    <div id="kbf-modal-report" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Report This Fund</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-report').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-report-form">
            <input type="hidden" name="fund_id" id="report-fund-id">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email" placeholder="your@email.com"></div>
            <div class="kbf-form-group"><label>Reason *</label><select name="reason" required><option value="">Select Reason</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Information</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select></div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" placeholder="Describe the issue..." required></textarea></div>
            <div id="kbf-report-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-report').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbfSubmitReport('<?php echo $nonce_report; ?>')">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- MODAL: Organizer -->
    <div id="kbf-modal-organizer" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal"><div class="kbf-modal-header"><h3>Organizer Profile</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-organizer').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body" id="kbf-organizer-body"><div style="text-align:center;padding:30px;color:var(--kbf-slate);">Loading...</div></div>
      </div>
    </div>

    <div class="kbf-browse-shell">
      <aside class="kbf-browse-sidebar">
        <div class="kbf-side-logo">KB</div>
        <div class="kbf-side-btn active" title="Browse">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4h8a1 1 0 011 1v6a1 1 0 01-1 1H3a1 1 0 01-1-1V5a1 1 0 011-1zm10 0h8a1 1 0 011 1v14a1 1 0 01-1 1h-8a1 1 0 01-1-1V5a1 1 0 011-1zM3 14h8a1 1 0 011 1v4a1 1 0 01-1 1H3a1 1 0 01-1-1v-4a1 1 0 011-1z"/></svg>
        </div>
        <div class="kbf-side-btn" title="Favorites">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21s-7-4.35-9.33-8.02C.5 9.5 2.5 6 6 6c2 0 3.33 1.02 4 2 0.67-0.98 2-2 4-2 3.5 0 5.5 3.5 3.33 6.98C19 16.65 12 21 12 21z"/></svg>
        </div>
        <div class="kbf-side-btn" title="Settings">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94a7.5 7.5 0 000-1.88l2.03-1.58a.5.5 0 00.12-.64l-1.92-3.32a.5.5 0 00-.6-.22l-2.39.96a7.48 7.48 0 00-1.63-.94l-.36-2.54a.5.5 0 00-.5-.42h-3.84a.5.5 0 00-.5.42l-.36 2.54a7.48 7.48 0 00-1.63.94l-2.39-.96a.5.5 0 00-.6.22L2.7 8.84a.5.5 0 00.12.64l2.03 1.58a7.5 7.5 0 000 1.88L2.82 14.52a.5.5 0 00-.12.64l1.92 3.32c.14.24.43.34.6.22l2.39-.96c.5.39 1.05.72 1.63.94l.36 2.54c.04.24.25.42.5.42h3.84c.25 0 .46-.18.5-.42l.36-2.54c.58-.22 1.13-.55 1.63-.94l2.39.96c.17.12.46.02.6-.22l1.92-3.32a.5.5 0 00-.12-.64l-2.03-1.58zM12 15.5a3.5 3.5 0 110-7 3.5 3.5 0 010 7z"/></svg>
        </div>
      </aside>
      <div class="kbf-browse-main">
        <div class="kbf-browse-topbar">
          <form method="GET" class="kbf-browse-search" id="kbf-browse-search-form">
            <input type="text" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Search funds, organizers, or causes...">
            <button type="submit" style="border:none;background:none;cursor:pointer;color:var(--kbf-slate);">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
          </form>
          <div class="kbf-browse-actions">
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url(add_query_arg('kbf_tab','overview',kbf_get_page_url('dashboard'))); ?>">Start a campaign</a>
          </div>
        </div>

        <div class="kbf-browse-header">
          <h2>All Projects</h2>
          <div style="font-size:13px;color:var(--kbf-slate);margin-top:4px;">
            Discover <?php echo $total_active; ?> active cause<?php echo $total_active!==1?'s':''; ?> near you.
          </div>
        </div>

        <div class="kbf-browse-tabs">
          <a class="kbf-browse-tab <?php echo !$sort || $sort==='newest'?'active':''; ?>" href="<?php echo esc_url(add_query_arg('sort','newest')); ?>">All Projects</a>
          <a class="kbf-browse-tab <?php echo $sort==='most_funded'?'active':''; ?>" href="<?php echo esc_url(add_query_arg('sort','most_funded')); ?>">Popular</a>
          <a class="kbf-browse-tab <?php echo $sort==='ending_soon'?'active':''; ?>" href="<?php echo esc_url(add_query_arg('sort','ending_soon')); ?>">Ending Soon</a>
        </div>

        <div class="kbf-filter-bar">
          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
              <span style="font-size:12px;font-weight:700;color:var(--kbf-slate);text-transform:uppercase;letter-spacing:.5px;">Category:</span>
              <a href="<?php echo esc_url(add_query_arg('cat','')); ?>" class="kbf-sort-pill <?php echo !$cat?'active':''; ?>">All</a>
              <?php foreach($cats as $c): ?><a href="<?php echo esc_url(add_query_arg('cat',$c)); ?>" class="kbf-sort-pill <?php echo $cat===$c?'active':''; ?>"><?php echo $c; ?></a><?php endforeach; ?>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
              <span style="font-size:12px;font-weight:700;color:var(--kbf-slate);text-transform:uppercase;letter-spacing:.5px;">Sort:</span>
              <div class="kbf-sort-pills">
                <a href="<?php echo esc_url(add_query_arg('sort','newest')); ?>" class="kbf-sort-pill <?php echo $sort==='newest'||!$sort?'active':''; ?>">Newest</a>
                <a href="<?php echo esc_url(add_query_arg('sort','most_funded')); ?>" class="kbf-sort-pill <?php echo $sort==='most_funded'?'active':''; ?>">Most Funded</a>
                <a href="<?php echo esc_url(add_query_arg('sort','ending_soon')); ?>" class="kbf-sort-pill <?php echo $sort==='ending_soon'?'active':''; ?>">Ending Soon</a>
              </div>
            </div>
          </div>
          <?php if($loc): ?>
          <div style="margin-top:10px;display:flex;align-items:center;gap:6px;font-size:13px;color:var(--kbf-slate);">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <strong><?php echo esc_html($loc); ?></strong> <a href="<?php echo esc_url(remove_query_arg('loc')); ?>" style="color:var(--kbf-red);margin-left:4px;text-decoration:none;">× Remove</a>
          </div>
          <?php endif; ?>
        </div>

    <!-- Fund grid -->
    <?php if(empty($funds)): ?>
    <div class="kbf-empty" style="padding:80px 20px;">
      <svg width="52" height="52" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 16px;display:block;opacity:.3;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <p style="font-size:16px;font-weight:600;color:var(--kbf-navy);margin-bottom:6px;">No funds found</p>
      <p style="color:var(--kbf-slate);">Try adjusting your search or filters.</p>
      <?php if($q||$cat||$loc): ?><a href="?" class="kbf-btn kbf-btn-primary" style="margin-top:16px;">Clear All Filters</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="kbf-browse-grid">
      <?php foreach($funds as $f):
        $pct   = $f->goal_amount>0 ? min(100,($f->raised_amount/$f->goal_amount)*100) : 0;
        $days  = $f->deadline ? max(0,ceil((strtotime($f->deadline)-time())/86400)) : null;
        $photos = $f->photos ? json_decode($f->photos,true) : [];
        $cover  = !empty($photos[0]) ? $photos[0] : null;
        $detail_url = esc_url(add_query_arg('fund_id',$f->id,$fund_details_url));
        $days_color = $days!==null&&$days<7 ? '#fca5a5' : 'rgba(255,255,255,.85)';
        $days_bg    = $days!==null&&$days<7 ? 'rgba(220,38,38,.85)' : 'rgba(15,32,68,.7)';
      ?>
      <div class="kbf-fund-card">
        <!-- Photo hero -->
        <a href="<?php echo $detail_url; ?>" style="text-decoration:none;display:block;">
          <?php if($cover): ?>
          <div class="kbf-fund-photo" style="position:relative;">
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($f->title); ?>" style="width:100%;height:190px;object-fit:cover;display:block;">
            <div class="kbf-fund-cat-badge"><?php echo esc_html($f->category); ?></div>
            <?php if($days!==null): ?><div class="kbf-fund-days-badge" style="background:<?php echo $days_bg; ?>;color:<?php echo $days_color; ?>;backdrop-filter:blur(4px);"><?php echo $days; ?>d left</div><?php endif; ?>
          </div>
          <?php else: ?>
          <div class="kbf-fund-photo-placeholder" style="position:relative;">
            <svg width="48" height="48" fill="none" stroke="rgba(255,255,255,.2)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <div class="kbf-fund-cat-badge"><?php echo esc_html($f->category); ?></div>
            <?php if($days!==null): ?><div class="kbf-fund-days-badge" style="background:<?php echo $days_bg; ?>;color:<?php echo $days_color; ?>;"><?php echo $days; ?>d left</div><?php endif; ?>
          </div>
          <?php endif; ?>
        </a>

        <!-- Card body -->
        <div style="padding:18px;flex:1;display:flex;flex-direction:column;">
          <a href="<?php echo $detail_url; ?>" style="text-decoration:none;">
            <h4 style="font-size:15.5px;font-weight:700;color:var(--kbf-navy);margin:0 0 6px;line-height:1.4;"><?php echo esc_html($f->title); ?></h4>
          </a>
          <p style="font-size:13px;color:var(--kbf-slate);margin:0 0 12px;flex:1;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.55;"><?php echo esc_html(wp_trim_words($f->description,18)); ?></p>

          <!-- Location + Organizer -->
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--kbf-slate);margin-bottom:12px;gap:6px;flex-wrap:wrap;">
            <span style="display:flex;align-items:center;gap:4px;">
              <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <?php echo esc_html($f->location); ?>
            </span>
            <button onclick="kbfViewOrganizer(<?php echo $f->business_id; ?>)" style="background:none;border:none;color:var(--kbf-blue);cursor:pointer;font-size:12px;padding:0;font-weight:600;">
              by <a href="<?php echo esc_url(add_query_arg('organizer_id',$f->business_id,kbf_get_page_url('organizer_profile'))); ?>" style="color:inherit;text-decoration:none;font-weight:700;"><?php echo esc_html($f->organizer_name?:'Organizer'); ?></a>
            </button>
          </div>

          <!-- Progress -->
          <div class="kbf-progress-wrap" style="margin-bottom:8px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:16px;">
            <span><strong style="color:var(--kbf-navy);font-size:14px;">₱<?php echo number_format($f->raised_amount,0); ?></strong> <span style="color:var(--kbf-slate);">raised</span></span>
            <span style="color:var(--kbf-slate);"><?php echo round($pct); ?>% of ₱<?php echo number_format($f->goal_amount,0); ?></span>
          </div>

          <!-- Actions -->
          <div style="display:grid;grid-template-columns:1fr auto auto auto;gap:8px;align-items:center;">
            <button class="kbf-btn kbf-btn-primary" style="font-size:13px;" onclick="kbfOpenSponsor(<?php echo $f->id; ?>,'<?php echo esc_js($f->title); ?>',<?php echo $f->goal_amount; ?>,<?php echo $f->raised_amount; ?>,'<?php echo esc_js(isset($cover) ? $cover : ''); ?>')">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
              Sponsor
            </button>
            <a href="<?php echo $detail_url; ?>" class="kbf-btn kbf-btn-secondary kbf-btn-sm" title="View details">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
            <button class="kbf-btn kbf-btn-secondary kbf-btn-sm" onclick="kbfShareFund('<?php echo esc_js($f->share_token); ?>','<?php echo esc_js($f->title); ?>','<?php echo esc_js(wp_trim_words($f->description,18)); ?>')" title="Share">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            </button>
            <button class="kbf-btn kbf-btn-danger kbf-btn-sm" onclick="kbfOpenReport(<?php echo $f->id; ?>)" title="Report">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
      </div>
    </div>
    </div><!-- .kbf-wrap -->
    <!-- ================== JS ================== -->
    <script>
    window.kbfOpenReport=function(id){document.getElementById('report-fund-id').value=id;document.getElementById('kbf-modal-report').style.display='flex';};
    window.kbfSponsorClose=function(){document.getElementById('kbf-modal-sponsor').style.display='none';};
    window.kbfOpenSponsor=function(id,title,goal,raised,img){
        document.getElementById('sponsor-fund-id').value=id;
        const pct=goal>0?Math.min(100,Math.round((raised/goal)*100)):0;
        const remaining = goal>0 ? Math.max(0, goal - raised) : 0;
        const limitEl = document.getElementById('kbf-sponsor-limit');
        const amountEl = document.querySelector('#kbf-sponsor-form input[name="amount"]');
        if (remaining > 0) {
            if (limitEl) limitEl.textContent = 'Max allowed: ₱' + parseFloat(remaining).toLocaleString() + ' (remaining goal)';
            if (amountEl) amountEl.max = remaining;
        } else {
            if (limitEl) limitEl.textContent = '';
            if (amountEl) amountEl.removeAttribute('max');
        }
        document.getElementById('kbf-fund-preview').innerHTML=
            (img?'<img src="'+img+'" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:10px;display:block;">':'')
            +'<strong style="font-size:15px;color:var(--kbf-navy);">'+title+'</strong>'
            +'<div style="margin-top:8px;" class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:'+pct+'%"></div></div>'
            +'<div style="display:flex;justify-content:space-between;font-size:12px;margin-top:6px;color:var(--kbf-slate);"><span>₱'+parseFloat(raised).toLocaleString()+' raised</span><span>'+pct+'% funded</span></div>';
        document.getElementById('kbf-modal-sponsor').style.display='flex';
    };
    function kbfGetActiveSponsorModal(){
        var modals = document.querySelectorAll('#kbf-modal-sponsor');
        for (var i = 0; i < modals.length; i++) {
            var d = window.getComputedStyle(modals[i]).display;
            if (d && d !== 'none') return modals[i];
        }
        return modals[0] || null;
    }
    function kbfGetActiveSponsorForm(){
        if (document.activeElement) {
            var activeModal = document.activeElement.closest('.kbf-modal');
            if (activeModal) {
                var activeForm = activeModal.querySelector('form');
                if (activeForm) return activeForm;
            }
        }
        var modal = kbfGetActiveSponsorModal();
        if (modal) {
            var f = modal.querySelector('form');
            if (f) return f;
        }
        return document.getElementById('kbf-sponsor-form');
    }
    function kbfGetActiveSponsorMsg(){
        var modal = kbfGetActiveSponsorModal();
        if (!modal) return null;
        return modal.querySelector('#kbf-spd-msg') || modal.querySelector('#kbf-sponsor-msg');
    }
    function kbfValidateRequired(form){
        var first = null;
        form.querySelectorAll('.kbf-field-error').forEach(function(el){ el.remove(); });
        form.querySelectorAll('[required]').forEach(function(el){
            if (el.disabled) return;
            if (el.type === 'hidden') return;
            if(!el.value || !el.value.trim()){
                var group = el.closest('.kbf-form-group');
                if(group){
                    var err = document.createElement('div');
                    err.className = 'kbf-field-error';
                    err.textContent = 'This field is required.';
                    group.appendChild(err);
                }
                if(!first) first = el;
            }
        });
        if(first){ first.focus(); return false; }
        return true;
    }
    window.kbfSubmitSponsor=function(nonce){
        const form=kbfGetActiveSponsorForm();
        const modal=kbfGetActiveSponsorModal();
        const btn=modal ? modal.querySelector('.kbf-modal-footer .kbf-btn-primary') : document.querySelector('#kbf-modal-sponsor .kbf-modal-footer .kbf-btn-primary');
        const msg=kbfGetActiveSponsorMsg();
        if(!kbfValidateRequired(form)) return;
        const amountEl = form.querySelector('input[name="amount"]');
        const maxVal = amountEl && amountEl.max ? parseFloat(amountEl.max) : null;
        const amt = amountEl ? parseFloat(amountEl.value || '0') : 0;
        if (maxVal && amt > maxVal) {
            msg.innerHTML = '<div class="kbf-alert kbf-alert-error">You cannot give more than ₱' + maxVal.toLocaleString() + ' for this fund.</div>';
            return;
        }
        kbfSetBtnLoading(btn,true,'Processing...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);
        fd.append('action', 'kbf_create_checkout');
        fd.append('nonce',nonce);
        fd.append('is_anonymous',document.getElementById('anon-check').checked?'1':'0');
        kbfFetchJson(ajaxurl, fd, (j)=>{
            if(j.success){
                console.log('KBF checkout response:', j);
                if(j.data && j.data.checkout_url){
                    btn.innerHTML='Redirecting to payment...';
                    window.open(j.data.checkout_url, '_blank');
                } else {
                    msg.innerHTML='<div class="kbf-alert kbf-alert-error">Maya checkout URL was not returned. Please check your Maya API keys and try again.</div>';
                    kbfSetBtnLoading(btn,false);
                    kbfSetSkeleton(msg,false);
                }
            } else {
                msg.innerHTML='<div class="kbf-alert kbf-alert-error">'+j.data.message+'</div>';
                kbfSetBtnLoading(btn,false);
                kbfSetSkeleton(msg,false);
            }
        }, (err)=>{
            console.error('KBF checkout error:', err);
            msg.innerHTML='<div class="kbf-alert kbf-alert-error">'+err+'</div>';
            kbfSetBtnLoading(btn,false);
            kbfSetSkeleton(msg,false);
        });
    };
    window.kbfSubmitReport=function(nonce){
        const form=document.getElementById('kbf-report-form');
        const btn=document.querySelector('#kbf-modal-report .kbf-modal-footer .kbf-btn-danger');
        const msg=document.getElementById('kbf-report-msg');
        kbfSetBtnLoading(btn,true,'Submitting...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            msg.innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if(j.success)setTimeout(()=>{document.getElementById('kbf-modal-report').style.display='none';},2000);else{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    };
    window.kbfViewOrganizer=function(bizId){
        document.getElementById('kbf-modal-organizer').style.display='flex';
        kbfSetSkeleton(document.getElementById('kbf-organizer-body'), true);
        const fd=new FormData();fd.append('action','kbf_get_organizer_profile');fd.append('business_id',bizId);fd.append('nonce','<?php echo wp_create_nonce('kbf_sponsor'); ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            if(j.success){
                const d=j.data;
                const starSvg=(filled)=>{
                    const src = filled ? 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg' : 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg';
                    const filter = filled
                        ? 'invert(70%) sepia(85%) saturate(531%) hue-rotate(3deg) brightness(98%) contrast(92%)'
                        : 'invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%)';
                    return '<img src="'+src+'" width="14" height="14" style="filter:'+filter+';">';
                };
                const stars=Array.from({length:5},(_,i)=>starSvg(i<Math.round(parseFloat(d.rating)))).join('');
                const statusColor={'active':'var(--kbf-green)','completed':'var(--kbf-blue)'};
                const statusBg={'active':'var(--kbf-green-lt)','completed':'#dbeafe'};

                // Fund history HTML
                const fundHistory = d.funds&&d.funds.length
                    ? '<div style="margin-top:20px;"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;"><h4 style="font-size:13px;font-weight:800;color:var(--kbf-navy);margin:0;">Fund History</h4><span style="font-size:11.5px;color:var(--kbf-slate);">'+d.total_funds+' total fund'+(d.total_funds!==1?'s':'')+'</span></div>'
                      + d.funds.map(f=>'<div style="border:1px solid var(--kbf-border);border-radius:8px;padding:12px;margin-bottom:8px;">'
                        +'<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">'
                        +'<div style="font-weight:700;font-size:13.5px;color:var(--kbf-navy);flex:1;margin-right:8px;">'+f.title+'</div>'
                        +'<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:'+(statusBg[f.status]||'var(--kbf-slate-lt)')+';color:'+(statusColor[f.status]||'var(--kbf-slate)')+';white-space:nowrap;">'+f.status.toUpperCase()+'</span>'
                        +'</div>'
                        +'<div style="font-size:11.5px;color:var(--kbf-slate);margin-bottom:6px;">'+f.category+' &bull; '+f.sponsor_count+' sponsor'+(f.sponsor_count!==1?'s':'')+'</div>'
                        +'<div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:'+f.pct+'%"></div></div>'
                        +'<div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--kbf-slate);margin-top:4px;"><span>₱'+f.raised+' raised</span><span>'+f.pct+'% of ₱'+f.goal+'</span></div>'
                        +'</div>').join('')
                      + '</div>'
                    : '<div style="text-align:center;padding:16px;color:var(--kbf-slate);font-size:13px;margin-top:16px;">No fund history yet.</div>';

                // Reviews HTML
                const reviewsHtml = d.reviews&&d.reviews.length
                    ? '<div style="margin-top:20px;"><h4 style="font-size:13px;font-weight:800;color:var(--kbf-navy);margin:0 0 10px;">Recent Reviews</h4>'
                      + d.reviews.map(r=>'<div style="border:1px solid var(--kbf-border);border-radius:8px;padding:10px 12px;margin-bottom:8px;">'
                        +'<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">'
                        +'<div style="display:flex;gap:2px;">'+Array.from({length:5},(_,i)=>starSvg(i<r.rating)).join('')+'</div>'
                        +'<span style="font-size:11px;color:var(--kbf-slate);">'+r.date+'</span>'
                        +'</div>'
                        +(r.review?'<p style="font-size:12.5px;color:var(--kbf-text-sm);margin:0 0 4px;font-style:italic;">"'+r.review+'"</p>':'')
                        +'<div style="font-size:11px;color:var(--kbf-slate);">'+r.email+(r.fund_title?' &bull; on "'+r.fund_title+'"':'')+'</div>'
                        +'</div>').join('')
                      + '</div>'
                    : '';

                document.getElementById('kbf-organizer-body').innerHTML=
                // Header
                '<div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:16px;">'
                +(d.avatar_url?'<img src="'+d.avatar_url+'" style="width:62px;height:62px;border-radius:50%;object-fit:cover;border:2px solid var(--kbf-border);flex-shrink:0;">':'<div style="width:62px;height:62px;border-radius:50%;background:var(--kbf-navy);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><img src=\"https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg\" width=\"28\" height=\"28\" style=\"filter:invert(100%);\"></div>')
                +'<div><div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;"><strong style="font-size:16px;color:var(--kbf-navy);">'+d.display_name+'</strong>'
                +(d.is_verified?'<span class="kbf-badge kbf-badge-verified" style="font-size:10px;">Verified</span>':'')
                +'</div>'
                +'<div style="display:flex;gap:3px;align-items:center;margin-top:5px;">'+stars
                +'<span style="font-size:12px;color:var(--kbf-slate);margin-left:5px;"><strong>'+d.rating+'</strong>/5 &nbsp;&bull;&nbsp; '+d.rating_count+' review'+(d.rating_count!==1?'s':'')+'</span></div>'
                +(d.bio?'<p style="font-size:13px;color:var(--kbf-slate);margin:6px 0 0;line-height:1.55;">'+d.bio+'</p>':'')
                +'</div></div>'
                // Stats
                +'<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:4px;">'
                +'<div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px;text-align:center;"><div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--kbf-slate);margin-bottom:3px;">Raised</div><div style="font-size:15px;font-weight:800;color:var(--kbf-navy);">₱'+parseFloat(d.total_raised).toLocaleString()+'</div></div>'
                +'<div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px;text-align:center;"><div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--kbf-slate);margin-bottom:3px;">Sponsors</div><div style="font-size:15px;font-weight:800;color:var(--kbf-navy);">'+d.total_sponsors+'</div></div>'
                +'<div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px;text-align:center;"><div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--kbf-slate);margin-bottom:3px;">Funds</div><div style="font-size:15px;font-weight:800;color:var(--kbf-navy);">'+d.total_funds+'</div></div>'
                +'</div>'
                + fundHistory
                + reviewsHtml;
            } else {
                document.getElementById('kbf-organizer-body').innerHTML='<div class="kbf-alert kbf-alert-error">Profile not found.</div>';
            }
        }).catch(()=>{ document.getElementById('kbf-organizer-body').innerHTML='<div class="kbf-alert kbf-alert-error">Profile not found.</div>'; });
    };
    </script>
    <?php
    $c=ob_get_clean();
    return bntm_universal_container('Browse Funds -- KonekBayan',$c, ['show_topbar'=>false,'show_header'=>false]);
}

// ============================================================
// SPONSOR DONATION HISTORY SHORTCODE
// ============================================================

function bntm_shortcode_kbf_sponsor_history() {
    kbf_global_assets();
    global $wpdb;
    $st = $wpdb->prefix.'kbf_sponsorships';
    $ft = $wpdb->prefix.'kbf_funds';
    $email = sanitize_email(isset($_GET['email']) ? $_GET['email'] : '');
    $rows = [];
    if ($email) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*,f.title as fund_title FROM {$st} s LEFT JOIN {$ft} f ON s.fund_id=f.id WHERE s.email=%s ORDER BY s.created_at DESC",
            $email
        ));
    }
    $fund_details_url = kbf_get_page_url('fund_details');
    ob_start();
    ?>
    <div class="kbf-wrap">
      <div class="kbf-page-header">
        <h2>Donation History</h2>
        <p>View your sponsorship records by email.</p>
      </div>

      <div class="kbf-card" style="margin-bottom:18px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
          <div class="kbf-form-group" style="flex:1;min-width:220px;margin-bottom:0;">
            <label>Your Email</label>
            <input type="email" name="email" value="<?php echo esc_attr($email); ?>" placeholder="you@example.com" required>
          </div>
          <button class="kbf-btn kbf-btn-primary" type="submit">View History</button>
        </form>
        <small style="color:var(--kbf-slate);display:block;margin-top:8px;">Enter the email you used when sponsoring a fund.</small>

      </div>

      <?php if(!$email): ?>
        <div class="kbf-empty"><p>Enter an email to view your donations.</p></div>
      <?php else: ?>
      <div class="kbf-table-wrap">
        <table class="kbf-table">
          <thead><tr><th>Fundraiser</th><th>Sponsor</th><th>Amount</th><th>Status</th><th>Method</th><th>Date</th></tr></thead>
          <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--kbf-slate);">No donations found for this email.</td></tr>
          <?php else: foreach($rows as $s): ?>
            <tr>
              <td>
                <strong><?php echo esc_html($s->fund_title ?: 'Fundraiser'); ?></strong>
                <?php if($s->fund_id): ?>
                  <div class="kbf-meta"><a href="<?php echo esc_url(add_query_arg('fund_id',$s->fund_id,$fund_details_url)); ?>" style="color:var(--kbf-blue);text-decoration:none;">View fundraiser</a></div>
                <?php endif; ?>
              </td>
              <td><?php echo $s->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($s->sponsor_name ?: 'Sponsor'); ?></td>
              <td><strong style="color:var(--kbf-green);">PHP <?php echo number_format($s->amount,2); ?></strong></td>
              <td><span class="kbf-badge kbf-badge-<?php echo $s->payment_status; ?>"><?php echo ucfirst($s->payment_status); ?></span></td>
              <td><?php echo esc_html($s->payment_method==='online_payment'?'Online Payment':($s->payment_method==='bank_payment'?'Bank Payment':ucfirst(str_replace('_',' ',isset($s->payment_method) ? $s->payment_method : '')))); ?></td>
              <td class="kbf-meta"><?php echo date('M d, Y',strtotime($s->created_at)); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}



// ============================================================
// FUND DETAILS SHORTCODE
// ============================================================

function bntm_shortcode_kbf_fund_details() {
    kbf_global_assets();
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $fund = null;
    $current_user_id = get_current_user_id();
    if(!empty($_GET['fund_id'])) {
        $fid = intval($_GET['fund_id']);
        $fund = $wpdb->get_row($wpdb->prepare(
            "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.id=%d AND (f.status IN ('active','completed') OR f.business_id=%d)",
            $fid, $current_user_id
        ));
    } elseif(!empty($_GET['kbf_share'])) {
        $token = sanitize_text_field($_GET['kbf_share']);
        $fund = $wpdb->get_row($wpdb->prepare(
            "SELECT f.*,u.display_name as organizer_name FROM {$ft} f LEFT JOIN {$wpdb->users} u ON f.business_id=u.ID WHERE f.share_token=%s AND (f.status IN ('active','completed') OR f.business_id=%d)",
            $token, $current_user_id
        ));
    }
    $is_owner = $fund && $current_user_id && $fund->business_id == $current_user_id;
    if(!$fund) return bntm_universal_container('Fund Details', '<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Fund not found or no longer active.</div></div>', ['show_topbar'=>false,'show_header'=>false]);

    $st = $wpdb->prefix.'kbf_sponsorships';
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $pct      = $fund->goal_amount>0 ? min(100,($fund->raised_amount/$fund->goal_amount)*100) : 0;
    $sponsors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$st} WHERE fund_id=%d AND payment_status='completed' ORDER BY created_at DESC LIMIT 20",$fund->id));
    $sponsor_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed'",$fund->id));
    // Leaderboard: group by sponsor name/email, sum total contributed, rank by total DESC
    $leaderboard = $wpdb->get_results($wpdb->prepare(
        "SELECT
            CASE WHEN is_anonymous=1 THEN 'Anonymous' ELSE COALESCE(NULLIF(sponsor_name,''),'Anonymous') END AS display_name,
            is_anonymous,
            SUM(amount) AS total_given,
            COUNT(*) AS num_donations
         FROM {$st}
         WHERE fund_id=%d AND payment_status='completed'
         GROUP BY display_name, is_anonymous
         ORDER BY total_given DESC
         LIMIT 10",
        $fund->id
    ));
    $organizer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$fund->business_id));
    $days     = $fund->deadline ? max(0,ceil((strtotime($fund->deadline)-time())/86400)) : null;
    $photos   = $fund->photos ? json_decode($fund->photos,true) : [];
    $browse_url = kbf_get_page_url('browse');
    $profile_url = $fund ? add_query_arg('organizer_id', $fund->business_id, kbf_get_page_url('organizer_profile')) : kbf_get_page_url('organizer_profile');
    $demo_mode  = (bool)kbf_get_setting('kbf_demo_mode',true);
    $nonce_sponsor = wp_create_nonce('kbf_sponsor');
    $nonce_report  = wp_create_nonce('kbf_report');
    $nonce_rating  = wp_create_nonce('kbf_rating');

    ob_start();
    ?>
    <!-- ================== CSS ================== -->
    <style>
    .kbf-detail-wrap{max-width:1000px;margin:0 auto;}
    .kbf-detail-layout{display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start;}
    .kbf-detail-sticky{position:sticky;top:24px;}
    .kbf-photo-gallery{display:grid;grid-template-columns:1fr;gap:8px;margin-bottom:24px;}
    .kbf-photo-gallery.multi{grid-template-columns:1fr 1fr;grid-template-rows:auto auto;}
    .kbf-photo-gallery.multi .kbf-photo-main{grid-column:1/-1;}
    .kbf-photo-main img,.kbf-photo-thumb img{width:100%;object-fit:cover;border-radius:10px;display:block;}
    .kbf-photo-main img{height:340px;}
    .kbf-photo-thumb img{height:150px;}
    .kbf-detail-sponsor-box{background:#fff;border:1px solid var(--kbf-border);border-radius:12px;padding:24px;box-shadow:var(--kbf-shadow);}
    .kbf-sponsor-wall{display:flex;flex-direction:column;gap:10px;margin-top:14px;}
    .kbf-sponsor-item{display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--kbf-slate-lt);border-radius:8px;}
    .kbf-sponsor-avatar{width:36px;height:36px;border-radius:50%;background:var(--kbf-navy);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;font-weight:700;color:#fff;}
    .kbf-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--kbf-slate);margin-bottom:20px;}
    .kbf-breadcrumb a{color:var(--kbf-blue);text-decoration:none;font-weight:600;}
    .kbf-breadcrumb a:hover{text-decoration:underline;}
    @media(max-width:760px){.kbf-detail-layout{grid-template-columns:1fr;}.kbf-detail-sticky{position:static;}.kbf-photo-main img{height:220px;}.kbf-photo-thumb img{height:110px;}}
    </style>
    <div class="kbf-wrap">

    <!-- Sponsor Modal -->
    <div id="kbf-modal-sponsor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header"><h3>Sponsor "<?php echo esc_html(wp_trim_words($fund->title,6)); ?>"</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-sponsor').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:12px 16px;margin-bottom:18px;display:flex;justify-content:space-between;font-size:13px;">
            <span><strong style="color:var(--kbf-green);">₱<?php echo number_format($fund->raised_amount,2); ?></strong> raised</span>
            <span style="color:var(--kbf-slate);"><?php echo round($pct); ?>% of ₱<?php echo number_format($fund->goal_amount,2); ?> goal</span>
          </div>
          <form id="kbf-sponsor-form" onsubmit="return false;">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Name / Company / Organization</label><input type="text" name="sponsor_name" id="spd-name" placeholder="Your name, company, or org"></div>
              <div class="kbf-form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;"><label class="kbf-checkbox-row"><input type="checkbox" id="spd-anon" onchange="document.getElementById('spd-name').disabled=this.checked"> Sponsor Anonymously</label></div>
            </div>
            <div class="kbf-form-group">
              <label>Amount (PHP) *</label>
              <input type="number" name="amount" placeholder="Min. ₱1" min="1" step="1" max="<?php echo $fund->goal_amount>0?max(0,$fund->goal_amount-$fund->raised_amount):''; ?>" required>
              <?php if($fund->goal_amount>0): ?>
                <div class="kbf-meta" style="margin-top:4px;">Max allowed: ₱<?php echo number_format(max(0,$fund->goal_amount-$fund->raised_amount),2); ?> (remaining goal)</div>
              <?php endif; ?>
            </div>
            <div class="kbf-form-group">
              <label>Encouraging Message (optional)</label>
              <textarea name="message" id="kbf-sponsor-message" rows="2" maxlength="300" placeholder="Leave a message for the organizer..."></textarea>
              <div class="kbf-char-count" id="kbf-sponsor-message-count">0/300</div>
            </div>
            <div class="kbf-form-row">
              <div class="kbf-form-group"><label>Email (for receipt)</label><input type="email" name="email" placeholder="your@email.com"></div>
              <div class="kbf-form-group"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX"></div>
            </div>
            <input type="hidden" name="payment_method" value="online_payment">
            <?php if($demo_mode): ?>
            <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;margin-top:4px;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/exclamation-triangle-fill.svg" alt="" width="16" height="16" style="flex-shrink:0;margin-top:1px;filter:invert(31%) sepia(86%) saturate(1160%) hue-rotate(16deg) brightness(95%) contrast(95%);">
              <div><strong>Demo Mode:</strong> Redirects to Maya sandbox checkout. No real payment is processed.</div>
            </div>
            <?php endif; ?>
            <div id="kbf-spd-msg" style="margin-top:10px;"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-sponsor').style.display='none'">Cancel</button>
          <button type="button" class="kbf-btn kbf-btn-primary" onclick="kbfSpdSponsor('<?php echo $nonce_sponsor; ?>')">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="14" height="14" style="filter:invert(100%);">
            Confirm Sponsorship
          </button>
        </div>
      </div>
    </div>

    <!-- Report Modal -->
    <div id="kbf-modal-report" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Report This Fund</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-report').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-report-form">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-group"><label>Your Email (optional)</label><input type="email" name="reporter_email"></div>
            <div class="kbf-form-group"><label>Reason *</label><select name="reason" required><option value="">Select</option><option value="Fraud">Fraudulent Campaign</option><option value="Misleading">Misleading Info</option><option value="Inappropriate">Inappropriate Content</option><option value="Scam">Suspected Scam</option><option value="Other">Other</option></select></div>
            <div class="kbf-form-group"><label>Details *</label><textarea name="details" rows="4" required></textarea></div>
            <div id="kbf-rpt-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-report').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" onclick="kbfSpdReport('<?php echo $nonce_report; ?>')">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- Rating Modal -->
    <div id="kbf-modal-rating" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header"><h3>Rate This Organizer</h3><button class="kbf-modal-close" onclick="document.getElementById('kbf-modal-rating').style.display='none'">&times;</button></div>
        <div class="kbf-modal-body">
          <form id="kbf-rating-form">
            <input type="hidden" name="organizer_id" value="<?php echo $fund->business_id; ?>">
            <input type="hidden" name="fund_id" value="<?php echo $fund->id; ?>">
            <div class="kbf-form-group"><label>Rating</label>
              <div id="kbf-star-picker" style="display:flex;gap:8px;margin-top:6px;">
                <?php for($i=1;$i<=5;$i++): ?>
                  <img class="kbf-star-btn" data-val="<?php echo $i; ?>" data-filled="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg" data-empty="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg" src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg" alt="" width="32" height="32" style="cursor:pointer;filter:invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%);" onclick="kbfSetRating(<?php echo $i; ?>)">
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="kbf-rating-val" value="5">
            </div>
            <div class="kbf-form-group"><label>Your Email *</label><input type="email" name="sponsor_email" required placeholder="your@email.com"></div>
            <div class="kbf-form-group"><label>Review (optional)</label><textarea name="review" rows="3" placeholder="Share your experience..."></textarea></div>
            <div id="kbf-rate-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="document.getElementById('kbf-modal-rating').style.display='none'">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitRating('<?php echo $nonce_rating; ?>')">Submit Review</button>
        </div>
      </div>
    </div>

    <!-- Breadcrumb -->
    <div class="kbf-breadcrumb">
      <a href="<?php echo esc_url($browse_url); ?>">Browse Funds</a>
      <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/chevron-right.svg" alt="" width="14" height="14" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
      <span><?php echo esc_html(wp_trim_words($fund->title,6)); ?></span>
    </div>

    <?php if($fund->status==='pending' && $is_owner): ?>
    <div class="kbf-alert kbf-alert-warning" style="margin-bottom:20px;"><strong>Under Review:</strong> This fund is not yet visible to sponsors. Once approved it goes live.</div>
    <?php elseif($fund->status==='suspended'): ?>
    <div class="kbf-alert kbf-alert-error" style="margin-bottom:20px;"><strong>Suspended:</strong> <?php echo esc_html($fund->admin_notes?:'Contact support.'); ?></div>
    <?php endif; ?>

    <div class="kbf-detail-layout">
      <!-- LEFT: Main content -->
      <div>
        <!-- Photo gallery -->
        <?php if(!empty($photos)): ?>
        <div class="kbf-photo-gallery <?php echo count($photos)>1?'multi':''; ?>" style="margin-bottom:24px;">
          <div class="kbf-photo-main"><img src="<?php echo esc_url($photos[0]); ?>" alt="<?php echo esc_attr($fund->title); ?>"></div>
          <?php if(count($photos)>1): foreach(array_slice($photos,1,4) as $ph): ?>
          <div class="kbf-photo-thumb"><img src="<?php echo esc_url($ph); ?>" alt=""></div>
          <?php endforeach; endif; ?>
        </div>
        <?php else: ?>
        <div style="width:100%;height:280px;background:linear-gradient(135deg,var(--kbf-navy) 0%,var(--kbf-navy-light) 100%);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
          <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="64" height="64" style="opacity:.25;filter:invert(100%);">
        </div>
        <?php endif; ?>

        <!-- Title + Meta -->
        <div style="margin-bottom:20px;">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
            <span style="background:var(--kbf-accent-lt);color:#92400e;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><?php echo esc_html($fund->category); ?></span>
            <span class="kbf-badge kbf-badge-<?php echo $fund->status; ?>"><?php echo ucfirst($fund->status); ?></span>
          </div>
          <h1 style="font-size:24px;font-weight:800;color:var(--kbf-navy);margin:0 0 10px;line-height:1.3;"><?php echo esc_html($fund->title); ?></h1>
          <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:13px;color:var(--kbf-slate);">
            <span style="display:flex;align-items:center;gap:5px;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/geo-alt.svg" alt="" width="14" height="14" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
              <?php echo esc_html($fund->location); ?>
            </span>
            <span style="display:flex;align-items:center;gap:5px;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg" alt="" width="14" height="14" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
              by <strong style="color:var(--kbf-text);"><a href="<?php echo esc_url($profile_url); ?>" style="color:inherit;text-decoration:none;"><?php echo esc_html($fund->organizer_name?:'Organizer'); ?></a></strong>
            </span>
            <?php if($days!==null): ?>
            <span style="display:flex;align-items:center;gap:5px;color:<?php echo $days<7?'var(--kbf-red)':'var(--kbf-slate)'; ?>;font-weight:<?php echo $days<7?'700':'400'; ?>;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/clock-fill.svg" alt="" width="14" height="14" style="filter:<?php echo $days<7?'invert(34%) sepia(82%) saturate(5110%) hue-rotate(344deg) brightness(100%) contrast(97%)':'invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%)'; ?>;">
              <?php echo $days; ?> day<?php echo $days!==1?'s':''; ?> left <?php echo $fund->deadline?'(ends '.date('M d, Y',strtotime($fund->deadline)).')':''; ?>
            </span>
            <?php endif; ?>
            <span style="display:flex;align-items:center;gap:5px;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/people-fill.svg" alt="" width="14" height="14" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
              <?php echo $sponsor_count; ?> sponsor<?php echo $sponsor_count!==1?'s':''; ?>
            </span>
          </div>
        </div>

        <!-- Description -->
        <div class="kbf-card" style="margin-bottom:20px;">
          <h3 style="font-size:15px;font-weight:700;color:var(--kbf-navy);margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">About This Fund</h3>
          <div style="font-size:14.5px;color:var(--kbf-text-sm);line-height:1.8;"><?php echo nl2br(esc_html($fund->description)); ?></div>
        </div>

        <!-- Organizer card -->
        <?php if($fund->organizer_name): ?>
        <div class="kbf-card" style="margin-bottom:20px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">
            <h3 style="font-size:15px;font-weight:700;color:var(--kbf-navy);margin:0;">About the Organizer</h3>
            <a href="<?php echo esc_url($profile_url); ?>" style="background:none;border:none;color:var(--kbf-blue);cursor:pointer;font-size:12.5px;font-weight:600;padding:0;display:flex;align-items:center;gap:4px;text-decoration:none;">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg" alt="" width="13" height="13" style="filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);">
              View Full Profile & History
            </a>
          </div>
          <a href="<?php echo esc_url($profile_url); ?>" style="display:flex;align-items:center;gap:14px;cursor:pointer;text-decoration:none;color:inherit;" title="View organizer profile">
            <?php if($organizer&&$organizer->avatar_url): ?>
              <img src="<?php echo esc_url($organizer->avatar_url); ?>" style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--kbf-border);flex-shrink:0;transition:border-color .15s;" onmouseover="this.style.borderColor='var(--kbf-blue)'" onmouseout="this.style.borderColor='var(--kbf-border)'">
            <?php else: ?>
              <div style="width:52px;height:52px;border-radius:50%;background:var(--kbf-navy);display:flex;align-items:center;justify-content:center;flex-shrink:0;cursor:pointer;"><img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/person-fill.svg" alt="" width="24" height="24" style="filter:invert(100%);"></div>
            <?php endif; ?>
            <div style="flex:1;">
              <div style="font-weight:700;font-size:15px;color:var(--kbf-navy);"><?php echo esc_html($fund->organizer_name); ?><?php if($organizer&&$organizer->is_verified): ?><span class="kbf-badge kbf-badge-verified" style="margin-left:8px;font-size:10px;">Verified</span><?php endif; ?></div>
              <?php if($organizer&&$organizer->bio): ?><p style="font-size:13px;color:var(--kbf-text-sm);margin:4px 0 0;line-height:1.55;"><?php echo esc_html(wp_trim_words($organizer->bio,30)); ?></p><?php endif; ?>
              <?php if($organizer&&$organizer->rating_count>0): ?>
              <div style="display:flex;align-items:center;gap:4px;margin-top:5px;">
                <?php for($i=1;$i<=5;$i++): ?>
                  <img src="<?php echo $i<=round($organizer->rating)?'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg':'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star.svg'; ?>" width="12" height="12" alt="" style="filter:<?php echo $i<=round($organizer->rating)?'invert(70%) sepia(85%) saturate(531%) hue-rotate(3deg) brightness(98%) contrast(92%)':'invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%)'; ?>;">
                <?php endfor; ?>
                <span style="font-size:11.5px;color:var(--kbf-slate);margin-left:2px;"><?php echo number_format($organizer->rating,1); ?>/5 &bull; ₱<?php echo number_format($organizer->total_raised,0); ?> raised</span>
              </div>
              <?php else: ?>
              <div style="font-size:12px;color:var(--kbf-slate);margin-top:4px;">Click to view fund history &amp; reviews</div>
              <?php endif; ?>
            </div>
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/chevron-right.svg" alt="" width="16" height="16" style="flex-shrink:0;opacity:.7;filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);">
          </a>
        </div>
        <?php endif; ?>

        <!-- Sponsors wall -->
        <?php if(!empty($sponsors)): ?>
        <div class="kbf-card">
          <h3 style="font-size:15px;font-weight:700;color:var(--kbf-navy);margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">
            Sponsors <span style="background:var(--kbf-green-lt);color:var(--kbf-green);padding:2px 8px;border-radius:99px;font-size:12px;margin-left:6px;"><?php echo $sponsor_count; ?></span>
          </h3>
          <div class="kbf-sponsor-wall">
            <?php foreach($sponsors as $sp):
              $initials = $sp->is_anonymous ? '?' : strtoupper(substr(isset($sp->sponsor_name) ? $sp->sponsor_name : 'A',0,1));
            ?>
            <div class="kbf-sponsor-item">
              <div class="kbf-sponsor-avatar"><?php echo $initials; ?></div>
              <div style="flex:1;">
                <div style="font-weight:600;font-size:13.5px;color:var(--kbf-text);"><?php echo $sp->is_anonymous?'<em style="color:var(--kbf-slate);">Anonymous</em>':esc_html($sp->sponsor_name); ?></div>
                <?php if($sp->message): ?><div style="font-size:12px;color:var(--kbf-slate);font-style:italic;margin-top:2px;">"<?php echo esc_html($sp->message); ?>"</div><?php endif; ?>
              </div>
              <div style="text-align:right;flex-shrink:0;">
                <div style="font-weight:800;font-size:14px;color:var(--kbf-green);">₱<?php echo number_format($sp->amount,0); ?></div>
                <div style="font-size:11px;color:var(--kbf-slate);"><?php echo date('M d',strtotime($sp->created_at)); ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: Sticky action sidebar -->
      <div class="kbf-detail-sticky">
        <div class="kbf-detail-sponsor-box">
          <!-- Progress -->
          <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
              <span style="font-size:24px;font-weight:800;color:var(--kbf-green);">₱<?php echo number_format($fund->raised_amount,2); ?></span>
              <span style="font-size:13px;color:var(--kbf-slate);">of ₱<?php echo number_format($fund->goal_amount,2); ?></span>
            </div>
            <div class="kbf-progress-wrap" style="height:10px;margin-bottom:10px;"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%;height:10px;"></div></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center;">
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div style="font-size:16px;font-weight:800;color:var(--kbf-navy);"><?php echo round($pct); ?>%</div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Funded</div>
              </div>
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div style="font-size:16px;font-weight:800;color:var(--kbf-navy);"><?php echo $sponsor_count; ?></div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Sponsors</div>
              </div>
              <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:10px 6px;">
                <div style="font-size:16px;font-weight:800;color:var(--kbf-navy);"><?php echo $days!==null?$days:'âˆž'; ?></div>
                <div style="font-size:11px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Days Left</div>
              </div>
            </div>
          </div>

          <?php if($fund->status==='active' && !$is_owner): ?>
          <?php if($demo_mode): ?>
          <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:8px;padding:10px 14px;font-size:12.5px;color:#92400e;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/exclamation-triangle-fill.svg" alt="" width="14" height="14" style="filter:invert(31%) sepia(86%) saturate(1160%) hue-rotate(16deg) brightness(95%) contrast(95%);">
            <span><strong>Demo Mode</strong> -- no real payment processed</span>
          </div>
          <?php endif; ?>
          <button class="kbf-btn kbf-btn-primary" style="width:100%;padding:13px;font-size:15px;font-weight:700;margin-bottom:10px;" onclick="document.getElementById('kbf-modal-sponsor').style.display='flex'">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="16" height="16" style="filter:invert(100%);">
            Sponsor This Fund
          </button>
          <?php elseif($fund->status==='completed'): ?>
          <div class="kbf-alert kbf-alert-success" style="margin-bottom:10px;text-align:center;font-weight:700;">This fund has been completed!</div>
          <?php elseif($is_owner && $fund->status==='active'): ?>
          <div style="background:var(--kbf-slate-lt);border:1.5px dashed var(--kbf-border);border-radius:8px;padding:12px 14px;font-size:12.5px;color:var(--kbf-slate);margin-bottom:12px;text-align:center;">
            <strong style="color:var(--kbf-navy);display:block;margin-bottom:4px;">This is your fund</strong>
            Sponsors can contribute using the Sponsor button.
          </div>
          <?php if($demo_mode): ?>
          <button class="kbf-btn kbf-btn-secondary" style="width:100%;padding:11px;font-size:13px;font-weight:600;margin-bottom:10px;border-style:dashed;" onclick="document.getElementById('kbf-modal-sponsor').style.display='flex'">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/exclamation-triangle-fill.svg" alt="" width="14" height="14" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
            Test Demo Sponsorship
          </button>
          <?php endif; ?>
          <?php endif; ?>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
            <button class="kbf-btn kbf-btn-secondary" style="font-size:13px;" onclick="kbfShareFundDetail('<?php echo esc_js($fund->share_token); ?>','<?php echo esc_js($fund->title); ?>','<?php echo esc_js(wp_trim_words($fund->description,18)); ?>')">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/share-fill.svg" alt="" width="13" height="13" style="filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);"> Share
            </button>
            <?php if(!$is_owner): ?>
            <button class="kbf-btn kbf-btn-secondary" style="font-size:13px;" onclick="document.getElementById('kbf-modal-rating').style.display='flex'">
              <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/star-fill.svg" alt="" width="13" height="13" style="filter:invert(70%) sepia(85%) saturate(531%) hue-rotate(3deg) brightness(98%) contrast(92%);"> Rate
            </button>
            <?php else: ?>
            <button class="kbf-btn kbf-btn-secondary" style="font-size:13px;" onclick="window.history.back()">Go Back</button>
            <?php endif; ?>
          </div>
          <button class="kbf-btn kbf-btn-danger" style="width:100%;font-size:13px;" onclick="document.getElementById('kbf-modal-report').style.display='flex'">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/flag-fill.svg" alt="" width="13" height="13" style="filter:invert(34%) sepia(82%) saturate(5110%) hue-rotate(344deg) brightness(100%) contrast(97%);">
            Report this fund
          </button>
        </div>

        <!-- Funder type info -->
        <div style="background:var(--kbf-slate-lt);border-radius:10px;padding:14px 16px;margin-top:14px;font-size:13px;color:var(--kbf-text-sm);">
          <div style="font-weight:700;color:var(--kbf-navy);margin-bottom:4px;">Fund Type</div>
          <div><?php echo ucwords(str_replace('_',' ',$fund->funder_type)); ?></div>
          <?php if($fund->auto_return): ?><div style="margin-top:6px;display:flex;align-items:center;gap:6px;color:var(--kbf-green);font-size:12px;"><img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/check-circle-fill.svg" alt="" width="12" height="12" style="filter:invert(41%) sepia(98%) saturate(342%) hue-rotate(83deg) brightness(93%) contrast(89%);">Auto-refund if goal not met</div><?php endif; ?>
        </div>

        <!-- Top Sponsors Leaderboard -->
        <div style="background:#fff;border:1px solid var(--kbf-border);border-radius:12px;padding:18px;margin-top:14px;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--kbf-border);">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/bar-chart-fill.svg" alt="" width="16" height="16" style="filter:invert(47%) sepia(87%) saturate(1955%) hue-rotate(200deg) brightness(97%) contrast(96%);flex-shrink:0;">
            <span style="font-size:14px;font-weight:800;color:var(--kbf-navy);">Top Sponsors</span>
            <?php if(!empty($leaderboard)): ?><span style="background:var(--kbf-accent);color:#fff;border-radius:99px;padding:1px 8px;font-size:10px;font-weight:800;margin-left:auto;"><?php echo count($leaderboard); ?></span><?php endif; ?>
          </div>

          <?php if(empty($leaderboard)): ?>
          <div style="text-align:center;padding:20px 10px;">
            <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/heart-fill.svg" alt="" width="32" height="32" style="margin:0 auto 10px;display:block;opacity:.25;filter:invert(27%) sepia(12%) saturate(1090%) hue-rotate(182deg) brightness(92%) contrast(88%);">
            <p style="font-size:13px;color:var(--kbf-slate);margin:0;font-weight:600;">No sponsors yet</p>
            <p style="font-size:12px;color:var(--kbf-slate);margin:4px 0 0;opacity:.7;">Be the first to support!</p>
          </div>
          <?php else: ?>
          <?php
          $rank_colors = [
              1 => ['bg'=>'linear-gradient(135deg,#f59e0b,#fbbf24)', 'icon'=>'ðŸ¥‡', 'label'=>'1st'],
              2 => ['bg'=>'linear-gradient(135deg,#6b7280,#9ca3af)', 'icon'=>'ðŸ¥ˆ', 'label'=>'2nd'],
              3 => ['bg'=>'linear-gradient(135deg,#b45309,#d97706)', 'icon'=>'ðŸ¥‰', 'label'=>'3rd'],
          ];
          foreach($leaderboard as $rank => $entry):
            $pos = $rank + 1;
            $rc  = isset($rank_colors[$pos]) ? $rank_colors[$pos] : ['bg'=>'var(--kbf-navy)', 'icon'=>null, 'label'=>$pos.'th'];
            $initials = $entry->is_anonymous ? '?' : strtoupper(substr($entry->display_name, 0, 1));
            $bar_pct = $leaderboard[0]->total_given > 0 ? min(100, ($entry->total_given / $leaderboard[0]->total_given) * 100) : 0;
          ?>
          <div style="margin-bottom:<?php echo $pos < count($leaderboard)?'14':'0'; ?>px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;">
              <!-- Rank badge -->
              <div style="width:28px;height:28px;border-radius:50%;background:<?php echo $rc['bg']; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:<?php echo $pos<=3?'13':'11'; ?>px;font-weight:800;color:#fff;">
                <?php echo $pos <= 3 ? $pos : $pos; ?>
              </div>
              <!-- Name -->
              <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:700;color:var(--kbf-navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?php echo $entry->is_anonymous ? '<em style="color:var(--kbf-slate);font-style:italic;">Anonymous</em>' : esc_html($entry->display_name); ?>
                  <?php if($pos===1 && !empty($leaderboard) && count($leaderboard)>1): ?>
                  <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:99px;margin-left:4px;font-weight:700;font-style:normal;">TOP</span>
                  <?php endif; ?>
                </div>
                <?php if($entry->num_donations > 1): ?><div style="font-size:10.5px;color:var(--kbf-slate);"><?php echo $entry->num_donations; ?> donations</div><?php endif; ?>
              </div>
              <!-- Amount -->
              <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:13.5px;font-weight:800;color:var(--kbf-green);">₱<?php echo number_format($entry->total_given, 0); ?></div>
              </div>
            </div>
            <!-- Relative bar -->
            <div style="height:4px;background:var(--kbf-border);border-radius:99px;overflow:hidden;margin-left:38px;">
              <div style="height:100%;width:<?php echo $bar_pct; ?>%;background:<?php echo $pos===1?'linear-gradient(90deg,#f59e0b,#fbbf24)':($pos===2?'linear-gradient(90deg,#6b7280,#9ca3af)':($pos===3?'linear-gradient(90deg,#b45309,#d97706)':'var(--kbf-navy)')); ?>;border-radius:99px;transition:width .4s;"></div>
            </div>
          </div>
          <?php endforeach; ?>

          <?php if($sponsor_count > count($leaderboard)): ?>
          <div style="text-align:center;margin-top:12px;padding-top:10px;border-top:1px solid var(--kbf-border);font-size:12px;color:var(--kbf-slate);">
            +<?php echo $sponsor_count - count($leaderboard); ?> more sponsor<?php echo ($sponsor_count - count($leaderboard)) !== 1 ? 's' : ''; ?>
          </div>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    </div><!-- .kbf-wrap -->
    <!-- ================== JS ================== -->
    <script>
    function kbfGetActiveSponsorModal(){
        var modals = document.querySelectorAll('#kbf-modal-sponsor');
        for (var i = 0; i < modals.length; i++) {
            var d = window.getComputedStyle(modals[i]).display;
            if (d && d !== 'none') return modals[i];
        }
        return modals[0] || null;
    }
    function kbfGetActiveSponsorForm(){
        if (document.activeElement) {
            var activeModal = document.activeElement.closest('.kbf-modal');
            if (activeModal) {
                var activeForm = activeModal.querySelector('form');
                if (activeForm) return activeForm;
            }
        }
        var modal = kbfGetActiveSponsorModal();
        if (modal) {
            var f = modal.querySelector('form');
            if (f) return f;
        }
        return document.getElementById('kbf-sponsor-form');
    }
    function kbfGetActiveSponsorMsg(){
        var modal = kbfGetActiveSponsorModal();
        if (!modal) return null;
        return modal.querySelector('#kbf-spd-msg') || modal.querySelector('#kbf-sponsor-msg');
    }
    function kbfValidateRequired(form){
        var first = null;
        form.querySelectorAll('.kbf-field-error').forEach(function(el){ el.remove(); });
        form.querySelectorAll('[required]').forEach(function(el){
            if (el.disabled) return;
            if (el.type === 'hidden') return;
            if(!el.value || !el.value.trim()){
                var group = el.closest('.kbf-form-group');
                if(group){
                    var err = document.createElement('div');
                    err.className = 'kbf-field-error';
                    err.textContent = 'This field is required.';
                    group.appendChild(err);
                }
                if(!first) first = el;
            }
        });
        if(first){ first.focus(); return false; }
        return true;
    }
    (function(){
        var msg = document.getElementById('kbf-sponsor-message');
        var msgCount = document.getElementById('kbf-sponsor-message-count');
        if(!msg || !msgCount) return;
        function updateMsgCount(){
            msgCount.textContent = (msg.value ? msg.value.length : 0) + '/300';
        }
        updateMsgCount();
        msg.addEventListener('input', updateMsgCount);
    })();
    window.kbfSpdSponsor=function(nonce){
        const form=kbfGetActiveSponsorForm();
        const modal=kbfGetActiveSponsorModal();
        const btn=modal ? modal.querySelector('.kbf-modal-footer .kbf-btn-primary') : document.querySelector('#kbf-modal-sponsor .kbf-modal-footer .kbf-btn-primary');
        const msg=kbfGetActiveSponsorMsg();
        if(!kbfValidateRequired(form)) return;
        const amountEl = form.querySelector('input[name="amount"]');
        const maxVal = amountEl && amountEl.max ? parseFloat(amountEl.max) : null;
        const amt = amountEl ? parseFloat(amountEl.value || '0') : 0;
        if (maxVal && amt > maxVal) {
            msg.innerHTML = '<div class="kbf-alert kbf-alert-error">You cannot give more than ₱' + maxVal.toLocaleString() + ' for this fund.</div>';
            return;
        }
        kbfSetBtnLoading(btn,true,'Processing...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);
        fd.append('action', 'kbf_create_checkout');
        fd.append('nonce',nonce);
        fd.append('is_anonymous',document.getElementById('spd-anon').checked?'1':'0');
        kbfFetchJson(ajaxurl, fd, (j)=>{
            if(j.success){
                console.log('KBF checkout response:', j);
                if(j.data && j.data.checkout_url){
                    btn.innerHTML='Redirecting to payment...';
                    window.open(j.data.checkout_url, '_blank');
                } else {
                    msg.innerHTML='<div class="kbf-alert kbf-alert-error">Maya checkout URL was not returned. Please check your Maya API keys and try again.</div>';
                    kbfSetBtnLoading(btn,false);
                    kbfSetSkeleton(msg,false);
                }
            } else {
                msg.innerHTML='<div class="kbf-alert kbf-alert-error">'+j.data.message+'</div>';
                kbfSetBtnLoading(btn,false);
                kbfSetSkeleton(msg,false);
            }
        }, (err)=>{
            console.error('KBF checkout error:', err);
            msg.innerHTML='<div class="kbf-alert kbf-alert-error">'+err+'</div>';
            kbfSetBtnLoading(btn,false);
            kbfSetSkeleton(msg,false);
        });
    };
    window.kbfSpdReport=function(nonce){
        const form=document.getElementById('kbf-report-form');
        const btn=document.querySelector('#kbf-modal-report .kbf-modal-footer .kbf-btn-danger');
        const msg=document.getElementById('kbf-rpt-msg');
        kbfSetBtnLoading(btn,true,'Submitting...');
        kbfSetSkeleton(msg,true);
        const fd=new FormData(form);fd.append('action','kbf_report_fund');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            msg.innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if(j.success)setTimeout(()=>{document.getElementById('kbf-modal-report').style.display='none';},1800);else{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    };
    var _kbfRating=5;
    window.kbfSetRating=function(v){
        _kbfRating=v;
        document.getElementById('kbf-rating-val').value=v;
        document.querySelectorAll('.kbf-star-btn').forEach((s,i)=>{
            const filled = i < v;
            const src = filled ? s.getAttribute('data-filled') : s.getAttribute('data-empty');
            s.src = src;
            s.style.filter = filled
                ? 'invert(70%) sepia(85%) saturate(531%) hue-rotate(3deg) brightness(98%) contrast(92%)'
                : 'invert(85%) sepia(9%) saturate(184%) hue-rotate(181deg) brightness(94%) contrast(90%)';
        });
    };
    kbfSetRating(5);
    window.kbfSubmitRating=function(nonce){
        const form=document.getElementById('kbf-rating-form');
        const btn=document.querySelector('#kbf-modal-rating .kbf-modal-footer .kbf-btn-primary');
        btn.disabled=true;btn.textContent='Submitting...';
        const fd=new FormData(form);fd.append('action','kbf_submit_rating');fd.append('nonce',nonce);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
            document.getElementById('kbf-rate-msg').innerHTML='<div class="kbf-alert kbf-alert-'+(j.success?'success':'error')+'">'+j.data.message+'</div>';
            if(j.success)setTimeout(()=>{document.getElementById('kbf-modal-rating').style.display='none';},1800);else{btn.disabled=false;btn.textContent='Submit Review';}
        });
    };
    </script>
    <?php
    $c=ob_get_clean();
    if (!empty($_GET['kbf_tab']) && $_GET['kbf_tab'] === 'fund_details') {
        return $c;
    }
    return bntm_universal_container('Fund Details -- KonekBayan',$c, ['show_topbar'=>false,'show_header'=>false]);
}

function bntm_shortcode_kbf_organizer_profile() {
    kbf_global_assets();
    global $wpdb;
    $biz_id = isset($_GET['organizer_id'])?intval($_GET['organizer_id']):0;
    if(!$biz_id) return bntm_universal_container('Organizer Profile','<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Organizer not found.</div></div>', ['show_topbar'=>false,'show_header'=>false]);
    $pt=$wpdb->prefix.'kbf_organizer_profiles';$ft=$wpdb->prefix.'kbf_funds';$rt=$wpdb->prefix.'kbf_ratings';
    $profile=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$biz_id));
    $user=get_userdata($biz_id);
    if(!$user) return bntm_universal_container('Organizer Profile','<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Organizer not found.</div></div>', ['show_topbar'=>false,'show_header'=>false]);
    $funds=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$ft} WHERE business_id=%d AND status IN ('active','completed') ORDER BY created_at DESC LIMIT 10",$biz_id));
    $reviews=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$rt} WHERE organizer_id=%d ORDER BY created_at DESC LIMIT 10",$biz_id));
    $socials=$profile&&$profile->social_links?json_decode($profile->social_links,true):[];
    ob_start();
    ?>
    <div class="kbf-wrap">
    <div class="kbf-page-header">
      <div style="display:flex;align-items:center;gap:16px;">
        <?php if($profile&&$profile->avatar_url): ?>
          <img src="<?php echo esc_url($profile->avatar_url); ?>" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.3);">
        <?php else: ?>
          <div style="width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;"><svg width="32" height="32" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
        <?php endif; ?>
        <div>
          <h2 style="margin:0 0 4px;"><?php echo esc_html($user->display_name); ?><?php if($profile&&$profile->is_verified): ?><span class="kbf-verified-badge" style="margin-left:8px;"><svg viewBox="0 0 24 24" fill="currentColor" width="11" height="11"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Verified</span><?php endif; ?></h2>
          <?php if($profile&&$profile->rating_count>0): ?>
          <div style="display:flex;align-items:center;gap:6px;">
            <?php for($i=1;$i<=5;$i++): ?><svg width="14" height="14" viewBox="0 0 24 24" fill="<?php echo $i<=round($profile->rating)?'#fbbf24':'#d1d5db'; ?>"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg><?php endfor; ?>
            <span style="color:rgba(255,255,255,.8);font-size:13px;"><?php echo number_format($profile->rating,1); ?>/5 &bull; <?php echo $profile->rating_count; ?> reviews</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;">
      <div>
        <?php if($profile&&$profile->bio): ?>
        <div class="kbf-card"><p style="font-size:14px;line-height:1.75;color:var(--kbf-text-sm);margin:0;"><?php echo nl2br(esc_html($profile->bio)); ?></p></div>
        <?php endif; ?>

        <h3 class="kbf-section-title" style="margin:20px 0 12px;">Campaigns</h3>
        <?php if(empty($funds)): ?>
          <div class="kbf-empty"><p>No active campaigns.</p></div>
        <?php else: foreach($funds as $f):
          $pct=$f->goal_amount>0?min(100,($f->raised_amount/$f->goal_amount)*100):0; ?>
          <div class="kbf-card">
            <div class="kbf-card-header">
              <div><strong style="font-size:14px;"><?php echo esc_html($f->title); ?></strong><div class="kbf-meta"><?php echo esc_html($f->category); ?> &bull; <?php echo esc_html($f->location); ?></div></div>
              <span class="kbf-badge kbf-badge-<?php echo $f->status; ?>"><?php echo ucfirst($f->status); ?></span>
            </div>
            <div class="kbf-progress-wrap"><div class="kbf-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
            <div class="kbf-fund-amounts"><span><strong>₱<?php echo number_format($f->raised_amount,2); ?></strong>raised</span><span><strong>₱<?php echo number_format($f->goal_amount,2); ?></strong>goal</span><span><strong><?php echo round($pct); ?>%</strong>funded</span></div>
            <?php if($f->status==='active'): ?><a href="?page_id=<?php echo urlencode(get_the_ID()); ?>&fund_id=<?php echo $f->id; ?>" class="kbf-btn kbf-btn-primary kbf-btn-sm" style="margin-top:10px;">View Fund</a><?php endif; ?>
          </div>
        <?php endforeach; endif; ?>

        <?php if(!empty($reviews)): ?>
        <h3 class="kbf-section-title" style="margin:24px 0 12px;">Reviews</h3>
        <?php foreach($reviews as $r):
          $stars=array_fill(0,$r->rating,'â˜…');$empty=array_fill(0,5-$r->rating,'â˜†'); ?>
          <div class="kbf-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
              <div style="color:#fbbf24;font-size:16px;"><?php echo implode('',$stars).implode('',$empty); ?></div>
              <span class="kbf-meta"><?php echo date('M d, Y',strtotime($r->created_at)); ?></span>
            </div>
            <?php if($r->review): ?><p style="margin:0;font-size:13.5px;color:var(--kbf-text-sm);font-style:italic;">"<?php echo esc_html($r->review); ?>"</p><?php endif; ?>
            <div class="kbf-meta" style="margin-top:6px;"><?php echo esc_html($r->sponsor_email?:'Anonymous'); ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Sidebar -->
      <div>
        <?php if($profile): ?>
        <div class="kbf-card" style="margin-bottom:16px;">
          <h4 style="font-size:13px;font-weight:700;color:var(--kbf-navy);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;">Stats</h4>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Total Raised</span><strong style="color:var(--kbf-green);">₱<?php echo number_format($profile->total_raised,0); ?></strong></div>
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Total Sponsors</span><strong><?php echo number_format($profile->total_sponsors); ?></strong></div>
            <div style="display:flex;justify-content:space-between;"><span class="kbf-meta">Active Funds</span><strong><?php $active_count=0; foreach($funds as $f){ if($f->status==='active') $active_count++; } echo $active_count; ?></strong></div>
          </div>
        </div>
        <?php endif; ?>
        <?php if(!empty(array_filter($socials))): ?>
        <div class="kbf-card">
          <h4 style="font-size:13px;font-weight:700;color:var(--kbf-navy);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;">Connect</h4>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach(['facebook'=>'Facebook','instagram'=>'Instagram','twitter'=>'Twitter/X'] as $k=>$label): if(!empty($socials[$k])): ?>
              <a href="<?php echo esc_url($socials[$k]); ?>" target="_blank" rel="noopener" class="kbf-btn kbf-btn-secondary kbf-btn-sm"><?php echo $label; ?></a>
            <?php endif; endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    </div>
    <?php
    $c=ob_get_clean();
    if (!empty($_GET['kbf_tab']) && $_GET['kbf_tab'] === 'organizer_profile') {
        return $c;
    }
    return bntm_universal_container('Organizer Profile -- KonekBayan',$c, ['show_topbar'=>false,'show_header'=>false]);
}















