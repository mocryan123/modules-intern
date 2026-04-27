<?php
/*
 * KBF admin UI rendering and shortcode output.
 */

if (!defined('ABSPATH')) exit;

/**
 * @function  bntm_shortcode_kbf_admin
 * @purpose   Handles bntm_shortcode_kbf_admin behavior for the admin UI renderer.
 * @used-by   [shortcode render flow, same file function call]
 * @calls     [WordPress APIs, same file helpers]
 * @params    [none]
 * @returns   [mixed rendered output or helper value]
 * @status    ACTIVE | NEEDS REVIEW
 *            ACTIVE = confirmed it is called somewhere
 *            NEEDS REVIEW = could not confirm caller,
 *                           may be unused/dead code
 */
function bntm_shortcode_kbf_admin() {
    if(!current_user_can('manage_options')) return '<div class="kbf-wrap"><div class="kbf-alert kbf-alert-error">Access denied.</div></div>';
    kbf_global_assets();
    global $wpdb;
    /**
     * @function  get_param
     * @purpose   Handles get_param behavior for the admin UI renderer.
     * @used-by   [shortcode render flow, same file function call]
     * @calls     [WordPress APIs, same file helpers]
     * @params    [mixed $key - parameter, mixed $default - parameter]
     * @returns   [mixed rendered output or helper value]
     * @status    ACTIVE | NEEDS REVIEW
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $get_param = function($key, $default = '') {
        return isset($_GET[$key]) ? sanitize_text_field($_GET[$key]) : $default;
    };
    $tab = $get_param('adm_tab', 'pending');
    $nonce = wp_create_nonce('kbf_admin_action');
    /**
     * @function  count_query
     * @purpose   Handles count_query behavior for the admin UI renderer.
     * @used-by   [shortcode render flow, same file function call]
     * @calls     [WordPress APIs, same file helpers]
     * @params    [mixed $table - parameter, mixed $where - parameter]
     * @returns   [mixed rendered output or helper value]
     * @status    ACTIVE | NEEDS REVIEW
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $count_query = function($table, $where) use ($wpdb) {
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE {$where}"); // phpcs:ignore
    };
    /**
     * @function  format_nav_count
     * @purpose   Handles format_nav_count behavior for the admin UI renderer.
     * @used-by   [shortcode render flow, same file function call]
     * @calls     [WordPress APIs, same file helpers]
     * @params    [mixed $raw - parameter]
     * @returns   [mixed rendered output or helper value]
     * @status    ACTIVE | NEEDS REVIEW
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $format_nav_count = function($raw) {
        return $raw >= 100 ? '99+' : (string)$raw;
    };
    ob_start();
    ?>
    <!-- ================== HTML ================== -->
    <style>
      .bntm-topbar,
      body .bntm-topbar,
      .kbf-admin-wrap .bntm-topbar{
        display:none !important;
      }
      html, body, body.admin-bar{
        margin-top:0 !important;
        padding-top:0 !important;
      }
      #wpadminbar{
        display:none !important;
      }
    </style>
    <?php if(false): ?><div></div><?php endif; ?>
    <div class="kbf-wrap kbf-admin-wrap">
    <?php
    $pending_count_admin = $count_query('kbf_funds', "status='pending'");
    $open_reports_count  = $count_query('kbf_reports', "status='open'");
    $pending_wd_count    = $count_query('kbf_withdrawals', "status='pending'");
    $open_appeals_count  = $count_query('kbf_appeals', "status='open'");
    $tabs=[
      'pending'=>'For Review',
      'all_funds'=>'Fundraisers',
      'transactions'=>'Payments',
      'withdrawals'=>'Cashouts',
      'reports'=>'Reports',
      'appeals'=>'Appeals',
      'organizers'=>'Accounts',
      'security'=>'Security Logs',
      'settings'=>'Settings'
    ];
    $nav_groups=[
      'Overview'=>['pending'=>'For Review'],
      'Fundraising'=>['all_funds'=>'Fundraisers','transactions'=>'Payments','withdrawals'=>'Cashouts'],
      'Trust & Safety'=>['reports'=>'Reports','appeals'=>'Appeals'],
      'Administration'=>['organizers'=>'Accounts','security'=>'Security Logs','settings'=>'Settings'],
    ];
    $counts=['pending'=>$pending_count_admin,'reports'=>$open_reports_count,'withdrawals'=>$pending_wd_count,'appeals'=>$open_appeals_count];
    /**
     * @function  render_nav_link
     * @purpose   Handles render_nav_link behavior for the admin UI renderer.
     * @used-by   [shortcode render flow, same file function call]
     * @calls     [WordPress APIs, same file helpers]
     * @params    [mixed $key - parameter, mixed $label - parameter]
     * @returns   [mixed rendered output or helper value]
     * @status    ACTIVE | NEEDS REVIEW
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $render_nav_link = function($key, $label) use ($tab, $counts, $format_nav_count) {
        $raw_count = !empty($counts[$key]) ? (int)$counts[$key] : 0;
        $display_count = $format_nav_count($raw_count);
        $href = esc_url(add_query_arg('adm_tab', $key));
        ?>
        <a href="<?php echo $href; ?>" class="<?php echo $tab===$key?'active':''; ?>" data-kbf-adm-tab="<?php echo esc_attr($key); ?>">
          <?php echo esc_html($label); ?>
          <?php if($raw_count > 0): ?>
            <span class="kbf-nav-count"><?php echo esc_html($display_count); ?></span>
          <?php endif; ?>
        </a>
        <?php
    };
    /**
     * @function  render_mobile_link
     * @purpose   Handles render_mobile_link behavior for the admin UI renderer.
     * @used-by   [shortcode render flow, same file function call]
     * @calls     [WordPress APIs, same file helpers]
     * @params    [mixed $key - parameter, mixed $label - parameter]
     * @returns   [mixed rendered output or helper value]
     * @status    ACTIVE | NEEDS REVIEW
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $render_mobile_link = function($key, $label) use ($tab, $counts, $format_nav_count) {
        $raw_count = !empty($counts[$key]) ? (int)$counts[$key] : 0;
        $display_count = $format_nav_count($raw_count);
        $href = esc_url(add_query_arg('adm_tab', $key));
        ?>
        <a href="<?php echo $href; ?>" class="<?php echo $tab===$key?'active':''; ?>" data-kbf-adm-tab="<?php echo esc_attr($key); ?>" onclick="kbfCloseMobileMenu()">
          <?php echo esc_html($label); ?>
          <?php if($raw_count > 0): ?>
            <span class="kbf-nav-count"><?php echo esc_html($display_count); ?></span>
          <?php endif; ?>
        </a>
        <?php
    };
    ?>
    <div class="kbf-dashboard-topbar kbf-admin-topbar">
      <div class="kbf-dashboard-brand">
        <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora" style="width:22px;height:22px;object-fit:contain;border-radius:6px;">
        
      </div>
      <button class="kbf-hamburger" type="button" onclick="kbfToggleMobileMenu()" aria-label="Toggle menu">
        <i class="ph ph-list kbf-icon" aria-hidden="true"></i>
      </button>
    </div>

    <div class="kbf-admin-layout">
      <aside class="kbf-admin-sidebar">
        <div class="kbf-admin-sidebar-brand">
          <img src="<?php echo esc_url(BNTM_KBF_URL . 'assets/branding/logo.png'); ?>" alt="fundora">
          
        </div>
        <nav class="kbf-dashboard-nav kbf-admin-nav" id="kbf-admin-nav">
          <?php foreach($nav_groups as $group_label=>$items): ?>
            <div style="margin-top:12px;"><?php echo esc_html($group_label); ?></div>
            <?php foreach($items as $k=>$label): ?>
              <?php $render_nav_link($k, $label); ?>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </nav>
        <div class="kbf-admin-sidebar-note">Fundora Admin</div>
      </aside>

      <main class="kbf-admin-main">
        <div class="kbf-admin-shell">
          <div class="kbf-page-header"><h2>fundora Admin Panel</h2><p>Moderate funds, manage escrow, review reports, and process withdrawals.</p></div>
          <div class="kbf-tab-content">
            <?php
            if($tab==='pending')      echo kbf_admin_pending_tab();
            elseif($tab==='all_funds')     echo kbf_admin_all_funds_tab();
            elseif($tab==='transactions')  echo kbf_admin_transactions_tab();
            elseif($tab==='withdrawals')   echo kbf_admin_withdrawals_tab();
            elseif($tab==='reports')       echo kbf_admin_reports_tab();
            elseif($tab==='appeals')       echo kbf_admin_appeals_tab();
            elseif($tab==='organizers')    echo kbf_admin_organizers_tab();
            elseif($tab==='security')      echo kbf_admin_security_logs_tab();
            elseif($tab==='settings')      echo kbf_admin_settings_tab();
            ?>
          </div>
        </div>
      </main>
    </div>
    </div>
    <div class="kbf-mobile-overlay" id="kbf-mobile-overlay"></div>
    <div class="kbf-mobile-menu" id="kbf-mobile-menu">
      <div class="kbf-mobile-menu-header" style="justify-content:flex-end;">
        <button class="kbf-hamburger" type="button" onclick="kbfCloseMobileMenu()" aria-label="Close menu">
          <i class="ph-bold ph-x kbf-icon" aria-hidden="true"></i>
        </button>
      </div>
      <?php foreach($nav_groups as $group_label=>$items): ?>
        <div style="margin:10px 0 6px;"><?php echo esc_html($group_label); ?></div>
        <?php foreach($items as $k=>$label): ?>
          <?php $render_mobile_link($k, $label); ?>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
    <div class="kbf-modal-overlay kbf-admin-reject-modal" id="kbf-admin-reject-modal" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3 id="kbf-admin-reject-title">Provide Reason</h3>
          <button class="kbf-modal-close" type="button" onclick="kbfCloseAdminRejectModal()" aria-label="Close">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <div class="kbf-form-group">
            <label for="kbf-admin-reject-template">Pre-Generated Message</label>
            <select id="kbf-admin-reject-template">
              <option value="">Select a reason...</option>
            </select>
          </div>
          <div class="kbf-form-group">
            <label for="kbf-admin-reject-notes">Reason (required)</label>
            <textarea id="kbf-admin-reject-notes" placeholder="Write the reason for rejection..."></textarea>
            <small style="color:var(--kbf-slate);display:block;margin-top:6px;">This message will be shown to the user.</small>
          </div>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" type="button" onclick="kbfCloseAdminRejectModal()">Cancel</button>
          <button class="kbf-btn kbf-btn-danger" type="button" onclick="kbfSubmitAdminReject()">Submit Rejection</button>
        </div>
      </div>
    </div>

       <!-- ================== JS ================== -->
    <script>
    var ajaxurl = window.ajaxurl || '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    var _kbfAdminNonce='<?php echo esc_js($nonce); ?>';
    var kbfAdminTab = '<?php echo esc_js($tab); ?>';
    var kbfAdminAutoRefresh = <?php echo $tab === 'settings' ? 'false' : 'true'; ?>;
    var kbfAdminRefreshInterval = 25000;
    var kbfAdminRefreshTimer = null;
    var kbfAdminDateFrom = '';
    var kbfAdminDateTo = '';
    try {
        var _kbfParams = new URLSearchParams(window.location.search || '');
        kbfAdminDateFrom = _kbfParams.get('date_from') || '';
        kbfAdminDateTo = _kbfParams.get('date_to') || '';
    } catch (e) {}
    /**
     * @function  kbfAdminStartRefresh
     * @purpose   Handles kbfAdminStartRefresh behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [none]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    function kbfAdminStartRefresh(){
        if (!kbfAdminAutoRefresh) return;
        if (kbfAdminRefreshTimer) clearInterval(kbfAdminRefreshTimer);
        kbfAdminRefreshTimer = setInterval(kbfAdminRefreshTab, kbfAdminRefreshInterval);
    }
    /**
     * @function  kbfAdminStopRefresh
     * @purpose   Handles kbfAdminStopRefresh behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [none]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    function kbfAdminStopRefresh(){
        if (!kbfAdminRefreshTimer) return;
        clearInterval(kbfAdminRefreshTimer);
        kbfAdminRefreshTimer = null;
    }
    var kbfRejectState = { id: null, btn: null };
    var kbfAdminRejectState = { context: '', params: null };
    var kbfAdminRejectTemplates = {
        fund: [
            'Insufficient details provided. Please add more context and resubmit.',
            'Fund description does not meet our guidelines. Please update and resubmit.',
            'Uploaded images are unclear or incomplete. Please provide clearer photos.',
            'Fund category or information appears incorrect. Please revise and resubmit.',
            'Other'
        ],
        withdrawal: [
            'Withdrawal details are incomplete. Please update your payout info.',
            'Account details do not match our records. Please verify and resubmit.',
            'Withdrawal request exceeds available balance.',
            'Pending compliance review. Please contact support.',
            'Other'
        ],
        escrow: [
            'Escrow request is not eligible at this time.',
            'Fund deadline has not passed. Please request after the deadline.',
            'Required documentation is missing. Please update and resubmit.',
            'Other'
        ],
        appeal: [
            'Appeal does not meet reinstatement criteria at this time.',
            'Provided explanation is insufficient. Please add more details.',
            'Evidence submitted does not support reinstatement.',
            'Other'
        ]
    };
    /**
     * @function  kbfSetLoadingPage
     * @purpose   Handles kbfSetLoadingPage behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed on - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfSetLoadingPage = function(on){
        var el = document.getElementById('kbf-loading-overlay');
        if(!el) return;
        el.style.display = on ? 'flex' : 'none';
    };
    /**
     * @function  kbfAdmin
     * @purpose   Handles kbfAdmin behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed action - parameter, mixed params - parameter, mixed opts - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfAdmin=function(action,params,opts){
        opts = opts || {};
        const fd=new FormData();fd.append('action',action);fd.append('_ajax_nonce',_kbfAdminNonce);
        Object.keys(params).forEach(k=>fd.append(k,params[k]));
        return fetch(ajaxurl,{method:'POST',body:fd}).then(async r=>{
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
            if (opts.onSuccess) { opts.onSuccess(j); }
            alert((j.data&&j.data.message)?j.data.message:(j.data||'Done.'));
            if(j.success && !opts.noReload)location.reload();
        }).catch(err=>{
            alert('Request failed. Please try again.');
            console.error('kbfAdmin error:', err);
            console.log('kbfAdmin action:', action, 'params:', params);
        });
    };
    /**
     * @function  kbfOpenAdminRejectModal
     * @purpose   Handles kbfOpenAdminRejectModal behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed context - parameter, mixed params - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfOpenAdminRejectModal = function(context, params){
        var modal = document.getElementById('kbf-admin-reject-modal');
        var title = document.getElementById('kbf-admin-reject-title');
        var select = document.getElementById('kbf-admin-reject-template');
        var notes = document.getElementById('kbf-admin-reject-notes');
        if (!modal || !select || !notes) return;
        kbfAdminRejectState.context = context || '';
        kbfAdminRejectState.params = params || null;
        if (title) {
            var label = context === 'fund' ? 'Reject Fund' :
                (context === 'withdrawal' ? 'Reject Withdrawal' :
                (context === 'escrow' ? 'Reject Escrow Request' :
                (context === 'appeal' ? 'Reject Appeal' : 'Provide Reason')));
            title.textContent = label;
        }
        select.innerHTML = '<option value="">Select a reason...</option>';
        var list = kbfAdminRejectTemplates[context] || ['Other'];
        list.forEach(function(item){
            var opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            select.appendChild(opt);
        });
        select.value = '';
        notes.value = '';
        modal.style.display = 'flex';
        modal.classList.add('is-open');
        document.documentElement.classList.add('kbf-modal-lock');
        document.body.classList.add('kbf-modal-lock');
    };
    /**
     * @function  kbfCloseAdminRejectModal
     * @purpose   Handles kbfCloseAdminRejectModal behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [none]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfCloseAdminRejectModal = function(){
        var modal = document.getElementById('kbf-admin-reject-modal');
        if (modal) modal.style.display = 'none';
        if (modal) modal.classList.remove('is-open');
        document.documentElement.classList.remove('kbf-modal-lock');
        document.body.classList.remove('kbf-modal-lock');
    };
    /**
     * @function  kbfSubmitAdminReject
     * @purpose   Handles kbfSubmitAdminReject behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [none]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfSubmitAdminReject = function(){
        var notes = document.getElementById('kbf-admin-reject-notes');
        var text = notes ? String(notes.value || '').trim() : '';
        if (!text) {
            alert('Please provide a reason for rejection.');
            return;
        }
        var ctx = kbfAdminRejectState.context;
        var params = kbfAdminRejectState.params || {};
        if (!ctx || !params) return;
        if (ctx === 'fund') {
            kbfAdmin('kbf_admin_reject_fund', {fund_id: params.fund_id, reason: text});
        } else if (ctx === 'withdrawal') {
            kbfAdmin('kbf_admin_process_withdrawal', {withdrawal_id: params.withdrawal_id, action_type: 'reject', notes: text});
        } else if (ctx === 'escrow') {
            kbfAdmin('kbf_admin_process_escrow_request', {request_id: params.request_id, action_type: 'reject', notes: text});
        } else if (ctx === 'appeal') {
            kbfAdmin('kbf_admin_review_appeal', {appeal_id: params.appeal_id, action_type: 'reject', notes: text});
        }
        kbfAdminRejectState.context = '';
        kbfAdminRejectState.params = null;
        window.kbfCloseAdminRejectModal();
    };
    document.addEventListener('change', function(e){
        if (!e.target || e.target.id !== 'kbf-admin-reject-template') return;
        var notes = document.getElementById('kbf-admin-reject-notes');
        if (!notes) return;
        var val = String(e.target.value || '').trim();
        if (!val || val === 'Other') return;
        notes.value = val;
    });
    /**
     * @function  kbfAdminApplyCounts
     * @purpose   Handles kbfAdminApplyCounts behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed counts - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    function kbfAdminApplyCounts(counts){
        if (!counts) return;
        Object.keys(counts).forEach(function(key){
            var value = parseInt(counts[key], 10) || 0;
            var label = value >= 100 ? '99+' : String(value);
            var links = document.querySelectorAll('[data-kbf-adm-tab="'+key+'"]');
            links.forEach(function(link){
                var badge = link.querySelector('.kbf-nav-count');
                if (value > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'kbf-nav-count';
                        link.appendChild(badge);
                    }
                    badge.textContent = label;
                } else if (badge && badge.parentNode) {
                    badge.parentNode.removeChild(badge);
                }
            });
        });
    }
    /**
     * @function  kbfAdminInitCardPagers
     * @purpose   Initializes client-side card pagination controls for card-list tabs without executing inline scripts.
     * @used-by   [initial page load, kbfAdminRefreshTab]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed scope - optional DOM scope root]
     * @returns   [void]
     * @status    ACTIVE
     */
    function kbfAdminInitCardPagers(scope){
        var root = scope || document;
        var wraps = Array.prototype.slice.call(root.querySelectorAll('.kbf-admin-card-list[data-kbf-card-pager]'));
        if (!wraps.length) return;
        wraps.forEach(function(wrap){
            if (!wrap || wrap.dataset.kbfPager === 'on') return;
            var cards = Array.prototype.slice.call(wrap.querySelectorAll('.kbf-admin-card'));
            if (!cards.length) return;
            wrap.dataset.kbfPager = 'on';

            var pager = document.createElement('div');
            pager.className = 'kbf-table-pager';
            pager.innerHTML = '' +
                '<div class="kbf-table-pager-left">Show&nbsp;' +
                '<select class="kbf-table-rows">' +
                    '<option value="3">3</option>' +
                    '<option value="5" selected>5</option>' +
                    '<option value="10">10</option>' +
                '</select>&nbsp;cards</div>' +
                '<div class="kbf-table-pager-right">' +
                    '<button class="kbf-table-pager-btn kbf-table-prev" type="button">Prev</button>' +
                    '<span class="kbf-table-pager-page">1 / 1</span>' +
                    '<button class="kbf-table-pager-btn kbf-table-next" type="button">Next</button>' +
                '</div>';
            wrap.insertAdjacentElement('afterend', pager);

            var select = pager.querySelector('.kbf-table-rows');
            var prevBtn = pager.querySelector('.kbf-table-prev');
            var nextBtn = pager.querySelector('.kbf-table-next');
            var pageLabel = pager.querySelector('.kbf-table-pager-page');
            var page = 1;
            var perPage = 5;

            function render(){
                var total = cards.length;
                var pages = Math.max(1, Math.ceil(total / perPage));
                if (page > pages) page = pages;
                var start = (page - 1) * perPage;
                var end = start + perPage;
                cards.forEach(function(card, i){
                    card.style.display = (i >= start && i < end) ? '' : 'none';
                });
                pageLabel.textContent = page + ' / ' + pages;
                prevBtn.disabled = page <= 1;
                nextBtn.disabled = page >= pages;
                pager.style.display = total > 0 ? 'flex' : 'none';
            }

            function setLoading(btn){
                btn.classList.add('is-loading');
                btn.disabled = true;
                setTimeout(function(){
                    btn.classList.remove('is-loading');
                    render();
                }, 250);
            }

            select.addEventListener('change', function(){
                perPage = parseInt(this.value, 10) || 5;
                page = 1;
                render();
            });
            prevBtn.addEventListener('click', function(){
                if (page > 1) {
                    page--;
                    setLoading(prevBtn);
                }
            });
            nextBtn.addEventListener('click', function(){
                page++;
                setLoading(nextBtn);
            });
            render();
        });
    }
    /**
     * @function  kbfAdminInitTableTools
     * @purpose   Handles kbfAdminInitTableTools behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed scope - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    function kbfAdminInitTableTools(scope){
        var root = scope || document;
        var targets = Array.prototype.slice.call(root.querySelectorAll('.kbf-table-wrap, .kbf-table-empty'));
        if (targets.length === 0) return;
        targets.forEach(function(target){
            if (target.dataset.kbfToolsInit === '1') return;
            target.dataset.kbfToolsInit = '1';

            var tools = document.createElement('div');
            tools.className = 'kbf-table-tools';
            tools.innerHTML = '' +
                '<div class="kbf-table-tools-left">' +
                  '<div class="kbf-form-group kbf-table-filter">' +
                    '<input type="text" class="kbf-table-search" placeholder="Search table...">' +
                  '</div>' +
                  '<div class="kbf-form-group kbf-table-filter">' +
                    '<select class="kbf-table-status"><option value="">All statuses</option></select>' +
                  '</div>' +
                  '<div class="kbf-form-group kbf-table-filter">' +
                    '<select class="kbf-table-range">' +
                      '<option value="">Any time</option>' +
                      '<option value="today">Today</option>' +
                      '<option value="last7">Last 7 days</option>' +
                      '<option value="last30">Last 30 days</option>' +
                      '<option value="month">This month</option>' +
                    '</select>' +
                  '</div>' +
                '</div>' +
                '<div class="kbf-table-tools-right">' +
                  '<button type="button" class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-table-apply">Apply</button>' +
                  '<button type="button" class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-table-clear">Clear</button>' +
                  '<button type="button" class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-table-refresh" aria-label="Refresh">' +
                    '<i class="ph ph-arrow-clockwise kbf-refresh-icon kbf-icon" aria-hidden="true"></i>' +
                  '</button>' +
                '</div>';
            target.parentNode.insertBefore(tools, target);

            var table = target.querySelector('table');
            var searchInput = tools.querySelector('.kbf-table-search');
            var statusSelect = tools.querySelector('.kbf-table-status');
            var rangeSelect = tools.querySelector('.kbf-table-range');
            var applyBtn = tools.querySelector('.kbf-table-apply');
            var clearBtn = tools.querySelector('.kbf-table-clear');
            var refreshBtn = tools.querySelector('.kbf-table-refresh');

            var statuses = {};
            if (table) {
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(row){
                    var badge = row.querySelector('.kbf-badge');
                    if (!badge) return;
                    var label = (badge.textContent || '').trim();
                    if (!label) return;
                    statuses[label] = true;
                });
            }
            Object.keys(statuses).sort().forEach(function(label){
                var opt = document.createElement('option');
                opt.value = label;
                opt.textContent = label;
                statusSelect.appendChild(opt);
            });
            if (!Object.keys(statuses).length) {
                statusSelect.setAttribute('disabled', 'disabled');
            }

            /**
             * @function  applyFilter
             * @purpose   Handles applyFilter behavior for the admin UI script.
             * @used-by   [same file event flow, onclick handler, or function call]
             * @calls     [same file helpers and browser APIs]
             * @params    [none]
             * @returns   [void]
             * @status    ACTIVE | NEEDS REVIEW
             */
            function applyFilter(){
                if (!table) return;
                var q = String(searchInput.value || '').toLowerCase().trim();
                var statusVal = String(statusSelect.value || '').trim();
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(row){
                    var text = row.textContent ? row.textContent.toLowerCase() : '';
                    var matchText = !q || text.indexOf(q) !== -1;
                    var matchStatus = true;
                    if (statusVal) {
                        var badge = row.querySelector('.kbf-badge');
                        var label = badge ? (badge.textContent || '').trim() : '';
                        matchStatus = label === statusVal;
                    }
                    row.style.display = (matchText && matchStatus) ? '' : 'none';
                });
            }
            if (searchInput) searchInput.addEventListener('input', applyFilter);
            if (statusSelect) statusSelect.addEventListener('change', applyFilter);
            if (rangeSelect) rangeSelect.addEventListener('change', function(){
                var val = String(rangeSelect.value || '');
                var now = new Date();
                /**
                 * @function  pad
                 * @purpose   Handles pad behavior for the admin UI script.
                 * @used-by   [same file event flow, onclick handler, or function call]
                 * @calls     [same file helpers and browser APIs]
                 * @params    [mixed n - parameter]
                 * @returns   [void]
                 * @status    ACTIVE | NEEDS REVIEW
                 */
                function pad(n){ return String(n).padStart(2, '0'); }
                /**
                 * @function  fmt
                 * @purpose   Handles fmt behavior for the admin UI script.
                 * @used-by   [same file event flow, onclick handler, or function call]
                 * @calls     [same file helpers and browser APIs]
                 * @params    [mixed d - parameter]
                 * @returns   [void]
                 * @status    ACTIVE | NEEDS REVIEW
                 */
                function fmt(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }
                var from = '';
                var to = '';
                if (val === 'today') {
                    from = fmt(now);
                    to = fmt(now);
                } else if (val === 'last7') {
                    var d7 = new Date(now);
                    d7.setDate(d7.getDate() - 6);
                    from = fmt(d7);
                    to = fmt(now);
                } else if (val === 'last30') {
                    var d30 = new Date(now);
                    d30.setDate(d30.getDate() - 29);
                    from = fmt(d30);
                    to = fmt(now);
                } else if (val === 'month') {
                    var start = new Date(now.getFullYear(), now.getMonth(), 1);
                    var end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                    from = fmt(start);
                    to = fmt(end);
                }
                kbfAdminDateFrom = from;
                kbfAdminDateTo = to;
                if (typeof kbfAdminRefreshTab === 'function') kbfAdminRefreshTab();
            });
            if (applyBtn) applyBtn.addEventListener('click', function(){
                if (typeof kbfAdminRefreshTab === 'function') kbfAdminRefreshTab();
            });
            if (clearBtn) clearBtn.addEventListener('click', function(){
                if (rangeSelect) rangeSelect.value = '';
                kbfAdminDateFrom = '';
                kbfAdminDateTo = '';
                if (typeof kbfAdminRefreshTab === 'function') kbfAdminRefreshTab();
            });
            if (refreshBtn) refreshBtn.addEventListener('click', function(){
                if (typeof kbfAdminRefreshTab === 'function') kbfAdminRefreshTab();
            });
        });
    }
    /**
     * @function  kbfAdminRefreshTab
     * @purpose   Handles kbfAdminRefreshTab behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [none]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    function kbfAdminRefreshTab(){
        if (!kbfAdminAutoRefresh || document.hidden) return;
        if (window.kbfAdminRefreshing) return;
        window.kbfAdminRefreshing = true;
        document.querySelectorAll('.kbf-table-refresh').forEach(function(btn){
            btn.classList.add('is-loading');
            btn.setAttribute('disabled', 'disabled');
        });
        const fd = new FormData();
        fd.append('action','kbf_admin_refresh_tab');
        fd.append('_ajax_nonce', _kbfAdminNonce);
        fd.append('tab', kbfAdminTab);
        if (kbfAdminDateFrom) fd.append('date_from', kbfAdminDateFrom);
        if (kbfAdminDateTo) fd.append('date_to', kbfAdminDateTo);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.text()).then(t=>{
            var cleaned = String(t || '').replace(/^\uFEFF+/, '').trim();
            var start = cleaned.indexOf('{');
            var end = cleaned.lastIndexOf('}');
            var payload = (start !== -1 && end !== -1 && end > start) ? cleaned.slice(start, end + 1) : cleaned;
            return JSON.parse(payload);
        }).then(j=>{
            if (!j || !j.success || !j.data || !j.data.html) return;
            var container = document.querySelector('.kbf-tab-content');
            if (!container) return;
            var rawHtml = String(j.data.html || '');
            var safeHtml = rawHtml.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
            container.innerHTML = safeHtml;
            kbfAdminInitTableTools(container);
            kbfAdminInitCardPagers(container);
            if (window.kbfInitTablePager) window.kbfInitTablePager();
            if (j.data.counts) kbfAdminApplyCounts(j.data.counts);
            if (window.kbfInitTableDescriptions) window.kbfInitTableDescriptions();
        }).catch(function(err){
            console.error('kbfAdminRefreshTab error:', err);
        }).finally(function(){
            window.kbfAdminRefreshing = false;
            document.querySelectorAll('.kbf-table-refresh').forEach(function(btn){
                btn.classList.remove('is-loading');
                btn.removeAttribute('disabled');
            });
        });
    }
    /**
     * @function  kbfSetTableLoading
     * @purpose   Handles kbfSetTableLoading behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed target - parameter, mixed on - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfSetTableLoading = function(target, on){
        var el = null;
        if (typeof target === 'string') {
            el = document.querySelector(target);
        } else {
            el = target;
        }
        if (!el) return;
        var wrap = el.classList.contains('kbf-table-wrap') ? el : el.closest('.kbf-table-wrap');
        if (!wrap) return;
        if (on) wrap.classList.add('is-loading');
        else wrap.classList.remove('is-loading');
    };
    /**
     * @function  kbfApprove
     * @purpose   Handles kbfApprove behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed id - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfApprove=function(id){if(!confirm('Approve this fund?'))return;kbfAdmin('kbf_admin_approve_fund',{fund_id:id});};
    /**
     * @function  kbfReject
     * @purpose   Handles kbfReject behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed id - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfReject=function(id){
        window.kbfOpenAdminRejectModal('fund',{fund_id:id});
    };
    /**
     * @function  kbfSuspend
     * @purpose   Handles kbfSuspend behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed id - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfSuspend=function(id){if(!confirm('Suspend this fund?'))return;kbfAdmin('kbf_admin_suspend_fund',{fund_id:id});};
    /**
     * @function  kbfVerifyBadge
     * @purpose   Handles kbfVerifyBadge behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed id - parameter, mixed cur - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfVerifyBadge=function(id,cur){kbfAdmin('kbf_admin_verify_badge',{fund_id:id,verified:cur?'0':'1'});};
    /**
     * @function  kbfEscrow
     * @purpose   Handles kbfEscrow behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed id - parameter, mixed act - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfEscrow=function(id,act){kbfAdmin('kbf_admin_'+act+'_escrow',{fund_id:id});};
        /**
         * @function  kbfDismissReport
         * @purpose   Handles kbfDismissReport behavior for the admin UI script.
         * @used-by   [same file event flow, onclick handler, or function call]
         * @calls     [same file helpers and browser APIs]
         * @params    [mixed id - parameter]
         * @returns   [void]
         * @status    ACTIVE | NEEDS REVIEW
         */
        window.kbfDismissReport=function(id){kbfAdmin('kbf_admin_dismiss_report',{report_id:id});};
        window.kbfAppealQuickAction=function(btn,id,action){
            if(!id || !action) return false;
            if (typeof window.kbfReviewAppeal === 'function') {
                window.kbfReviewAppeal(id, action);
                return false;
            }
            if (action === 'reject') {
                if (typeof window.kbfOpenAdminRejectModal === 'function') {
                    window.kbfOpenAdminRejectModal('appeal', { appeal_id: id });
                } else if (typeof window.kbfAdmin === 'function') {
                    var rejectNotes = prompt('Reason for rejection (required):');
                    if (rejectNotes === null) return false;
                    rejectNotes = String(rejectNotes || '').trim();
                    if (!rejectNotes) { alert('Please provide a reason for rejection.'); return false; }
                    window.kbfAdmin('kbf_admin_review_appeal', { appeal_id: id, action_type: 'reject', notes: rejectNotes });
                }
                return false;
            }
            if (typeof window.kbfAdmin === 'function') {
                var approveNotes = prompt('Admin notes (optional):');
                if (approveNotes === null) return false;
                window.kbfAdmin('kbf_admin_review_appeal', { appeal_id: id, action_type: action, notes: approveNotes });
            }
            return false;
        };
        /**
         * @function  kbfReviewAppeal
         * @purpose   Handles kbfReviewAppeal behavior for the admin UI script.
         * @used-by   [same file event flow, onclick handler, or function call]
         * @calls     [same file helpers and browser APIs]
         * @params    [mixed id - parameter, mixed action - parameter]
         * @returns   [void]
         * @status    ACTIVE | NEEDS REVIEW
         */
        window.kbfReviewAppeal=function(id,action){
            if(action==='reject'){
                window.kbfOpenAdminRejectModal('appeal',{appeal_id:id});
                return;
            }
            const n=prompt('Admin notes (optional):');if(n===null)return;kbfAdmin('kbf_admin_review_appeal',{appeal_id:id,action_type:action,notes:n});
        };
/**
 * @function  kbfProcessWd
 * @purpose   Handles kbfProcessWd behavior for the admin UI script.
 * @used-by   [same file event flow, onclick handler, or function call]
 * @calls     [same file helpers and browser APIs]
 * @params    [mixed id - parameter, mixed type - parameter]
 * @returns   [void]
 * @status    ACTIVE | NEEDS REVIEW
 */
window.kbfProcessWd=function(id,type){
    if(type==='reject'){
        window.kbfOpenAdminRejectModal('withdrawal',{withdrawal_id:id});
    } else {
        if(!confirm('Approve & release this withdrawal?'))return;
        kbfAdmin('kbf_admin_process_withdrawal',{withdrawal_id:id,action_type:'approve'});
    }
};
/**
 * @function  kbfProcessEscrowRequest
 * @purpose   Handles kbfProcessEscrowRequest behavior for the admin UI script.
 * @used-by   [same file event flow, onclick handler, or function call]
 * @calls     [same file helpers and browser APIs]
 * @params    [mixed id - parameter, mixed type - parameter]
 * @returns   [void]
 * @status    ACTIVE | NEEDS REVIEW
 */
window.kbfProcessEscrowRequest=function(id,type){
    if(type==='reject'){
        window.kbfOpenAdminRejectModal('escrow',{request_id:id});
    } else {
        if(!confirm('Approve escrow release for this fund?'))return;
        kbfAdmin('kbf_admin_process_escrow_request',{request_id:id,action_type:'approve'});
    }
};
    /**
     * @function  kbfConfirmPayment
     * @purpose   Handles kbfConfirmPayment behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [mixed id - parameter]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    window.kbfConfirmPayment=function(id){if(!confirm('Mark this sponsorship as paid?'))return;kbfAdmin('kbf_admin_confirm_payment',{sponsorship_id:id});};
    /**
     * @function  kbfToggleMobileMenu
     * @purpose   Handles kbfToggleMobileMenu behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [none]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    function kbfToggleMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (!menu || !overlay) return;
        menu.classList.toggle('kbf-menu-open');
        overlay.classList.toggle('kbf-overlay-open');
    }
    /**
     * @function  kbfCloseMobileMenu
     * @purpose   Handles kbfCloseMobileMenu behavior for the admin UI script.
     * @used-by   [same file event flow, onclick handler, or function call]
     * @calls     [same file helpers and browser APIs]
     * @params    [none]
     * @returns   [void]
     * @status    ACTIVE | NEEDS REVIEW
     */
    function kbfCloseMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (menu) menu.classList.remove('kbf-menu-open');
        if (overlay) overlay.classList.remove('kbf-overlay-open');
    }
    window.kbfToggleMobileMenu = kbfToggleMobileMenu;
    window.kbfCloseMobileMenu = kbfCloseMobileMenu;
    window.addEventListener('resize', function(){
        if (window.innerWidth > 900) kbfCloseMobileMenu();
    });
    document.addEventListener('click', function(e){
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (overlay && e.target === overlay) {
            kbfCloseMobileMenu();
        }
    });
/**
 * @function  kbfVerifyOrg
 * @purpose   Handles kbfVerifyOrg behavior for the admin UI script.
 * @used-by   [same file event flow, onclick handler, or function call]
 * @calls     [same file helpers and browser APIs]
 * @params    [mixed btn - parameter, mixed id - parameter, mixed verified - parameter]
 * @returns   [void]
 * @status    ACTIVE | NEEDS REVIEW
 */
window.kbfVerifyOrg=function(btn,id,verified){
    var v = parseInt(verified,10) ? 1 : 0;
    if(v === 0){
        kbfRejectState.id = id;
        kbfRejectState.btn = btn || null;
        if (typeof window.kbfOpenRejectModal === 'function') window.kbfOpenRejectModal();
        return;
    }
    var stack = btn && btn.closest ? btn.closest('.kbf-verify-stack') : null;
    kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:v,notes:''},{
        noReload:true,
        onSuccess:function(j){
            if(!j || !j.success) return;
            if (stack) {
                stack.classList.add('is-locked');
                var buttons = stack.querySelectorAll('.kbf-btn');
                buttons.forEach(function(b){ b.classList.remove('is-selected'); b.setAttribute('disabled','disabled'); });
                if (btn) btn.classList.add('is-selected');
            }
        }
    });
};

/**
 * @function  kbfOpenRejectModal
 * @purpose   Handles kbfOpenRejectModal behavior for the admin UI script.
 * @used-by   [same file event flow, onclick handler, or function call]
 * @calls     [same file helpers and browser APIs]
 * @params    [none]
 * @returns   [void]
 * @status    ACTIVE | NEEDS REVIEW
 */
window.kbfOpenRejectModal = function(){
    var modal = document.getElementById('kbf-reject-modal');
    if (!modal) return;
    var select = document.getElementById('kbf-reject-template');
    var notes = document.getElementById('kbf-reject-notes');
    if (select) select.value = '';
    if (notes) notes.value = '';
    modal.style.display = 'flex';
    document.documentElement.classList.add('kbf-modal-lock');
    document.body.classList.add('kbf-modal-lock');
};
/**
 * @function  kbfCloseRejectModal
 * @purpose   Handles kbfCloseRejectModal behavior for the admin UI script.
 * @used-by   [same file event flow, onclick handler, or function call]
 * @calls     [same file helpers and browser APIs]
 * @params    [none]
 * @returns   [void]
 * @status    ACTIVE | NEEDS REVIEW
 */
window.kbfCloseRejectModal = function(){
    var modal = document.getElementById('kbf-reject-modal');
    if (modal) modal.style.display = 'none';
    document.documentElement.classList.remove('kbf-modal-lock');
    document.body.classList.remove('kbf-modal-lock');
};
/**
 * @function  kbfSubmitReject
 * @purpose   Handles kbfSubmitReject behavior for the admin UI script.
 * @used-by   [same file event flow, onclick handler, or function call]
 * @calls     [same file helpers and browser APIs]
 * @params    [none]
 * @returns   [void]
 * @status    ACTIVE | NEEDS REVIEW
 */
window.kbfSubmitReject = function(){
    var notes = document.getElementById('kbf-reject-notes');
    var text = notes ? String(notes.value || '').trim() : '';
    if (!text) {
        alert('Please provide a reason for rejection.');
        return;
    }
    var id = kbfRejectState.id;
    if (!id) return;
    var btn = kbfRejectState.btn;
    var stack = btn && btn.closest ? btn.closest('.kbf-verify-stack') : null;
    kbfAdmin('kbf_admin_verify_organizer',{business_id:id,verified:0,notes:text},{
        noReload:true,
        onSuccess:function(j){
            if(!j || !j.success) return;
            if (stack) {
                stack.classList.add('is-locked');
                var buttons = stack.querySelectorAll('.kbf-btn');
                buttons.forEach(function(b){ b.classList.remove('is-selected'); b.setAttribute('disabled','disabled'); });
                if (btn) btn.classList.add('is-selected');
            }
            kbfRejectState.id = null;
            kbfRejectState.btn = null;
            window.kbfCloseRejectModal();
        }
    });
};
    document.addEventListener('change', function(e){
        if (!e.target || e.target.id !== 'kbf-reject-template') return;
        var notes = document.getElementById('kbf-reject-notes');
        if (!notes) return;
        var val = String(e.target.value || '').trim();
        if (!val || val === 'Other') return;
        notes.value = val;
    });
    kbfAdminInitTableTools(document);
    kbfAdminInitCardPagers(document);
    /**
     * @function  kbfToggleAccountSuspension
     * @purpose   Handles suspend/unsuspend account actions for organizer accounts.
     * @used-by   [accounts tab action buttons]
     * @calls     [kbfAdmin, browser confirm]
     * @params    [mixed id - organizer user ID, mixed suspended - 1 to suspend, 0 to unsuspend]
     * @returns   [void]
     * @status    ACTIVE
     */
    window.kbfToggleAccountSuspension = function(id, suspended){
        var next = parseInt(suspended, 10) === 1 ? 1 : 0;
        var prompt = next
            ? 'Suspend this account? The user will be signed out and blocked from signing in.'
            : 'Unsuspend this account? The user can sign in again.';
        if(!confirm(prompt)) return;
        kbfAdmin('kbf_admin_toggle_account_suspend', {
            business_id: id,
            suspended: next
        });
    };
    if (kbfAdminAutoRefresh) {
        kbfAdminStartRefresh();
        document.addEventListener('visibilitychange', function(){
            if (document.hidden) {
                kbfAdminStopRefresh();
                return;
            }
            kbfAdminStartRefresh();
            kbfAdminRefreshTab();
        });
    }
    </script>
    <?php
    $c=ob_get_clean();
    return bntm_universal_container('KonekBayan Admin Panel',$c, ['show_topbar'=>false,'show_header'=>false,'wrap'=>false]);
}




