<?php
/* User dashboard shortcode */
if (!function_exists('kbf_dashboard_public_tabs')) {
    /**
     * @function  kbf_dashboard_public_tabs
     * @purpose   Returns the list of dashboard tabs accessible to logged-out users.
     * @used-by   [bntm_shortcode_kbf_dashboard]
     * @calls     [none]
     * @params    [none]
     * @returns   [array list of allowed public tab slugs]
     * @status    ACTIVE
     */
    function kbf_dashboard_public_tabs() {
        return ['find_funds','fund_details','organizer_profile'];
    }
}

if (!function_exists('kbf_dashboard_default_tab')) {
    /**
     * @function  kbf_dashboard_default_tab
     * @purpose   Returns the default dashboard tab slug used for fallback routing.
     * @used-by   [bntm_shortcode_kbf_dashboard]
     * @calls     [none]
     * @params    [none]
     * @returns   [string default tab slug]
     * @status    ACTIVE
     */
    function kbf_dashboard_default_tab() {
        return 'find_funds';
    }
}

if (!function_exists('kbf_dashboard_get_current_url')) {
    /**
     * @function  kbf_dashboard_get_current_url
     * @purpose   Builds the current dashboard URL from request URI or fallback dashboard URL.
     * @used-by   [bntm_shortcode_kbf_dashboard]
     * @calls     [wp_unslash, esc_url_raw, home_url, function_exists, kbf_get_page_url]
     * @params    [none]
     * @returns   [string current or fallback URL]
     * @status    ACTIVE
     */
    function kbf_dashboard_get_current_url() {
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = wp_unslash((string) $_SERVER['REQUEST_URI']);
            return home_url(esc_url_raw($request_uri));
        }
        return function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
    }
}

if (!function_exists('kbf_dashboard_build_nonces')) {
    /**
     * @function  kbf_dashboard_build_nonces
     * @purpose   Generates all nonce tokens required by dashboard actions.
     * @used-by   [bntm_shortcode_kbf_dashboard]
     * @calls     [wp_create_nonce]
     * @params    [none]
     * @returns   [array associative nonce map]
     * @status    ACTIVE
     */
    function kbf_dashboard_build_nonces() {
        return [
            'create'  => wp_create_nonce('kbf_create_fund'),
            'edit'    => wp_create_nonce('kbf_update_fund'),
            'cancel'  => wp_create_nonce('kbf_cancel_fund'),
            'wd'      => wp_create_nonce('kbf_withdrawal'),
            'extend'  => wp_create_nonce('kbf_extend'),
            'appeal'  => wp_create_nonce('kbf_appeal'),
            'escrow'  => wp_create_nonce('kbf_request_escrow'),
            'refresh' => wp_create_nonce('kbf_user_refresh'),
            'trash'   => wp_create_nonce('kbf_trash_fund'),
            'complete'=> wp_create_nonce('kbf_mark_fund_complete'),
            'auto_return' => wp_create_nonce('kbf_auto_return'),
        ];
    }
}

if (!function_exists('kbf_dashboard_get_blocked_label')) {
    /**
     * @function  kbf_dashboard_get_blocked_label
     * @purpose   Resolves a human-readable section label for blocked tab access messaging.
     * @used-by   [bntm_shortcode_kbf_dashboard]
     * @calls     [none]
     * @params    [$tab (string) blocked tab slug]
     * @returns   [string section label or fallback text]
     * @status    ACTIVE
     */
    function kbf_dashboard_get_blocked_label($tab) {
        $labels = [
            'overview' => 'Home',
            'sponsorships' => 'Supporters',
            'withdrawals' => 'Cashout',
            'profile' => 'Profile',
        ];
        return $labels[$tab] ?? 'this section';
    }
}

if (!function_exists('kbf_dashboard_handle_payment_success')) {
    /**
     * @function  kbf_dashboard_handle_payment_success
     * @purpose   Validates payment reference ownership and marks the matching sponsorship as completed.
     * @used-by   [bntm_shortcode_kbf_dashboard]
     * @calls     [get_userdata, user_can, intval, sanitize_text_field, wpdb->prepare, wpdb->get_row, kbf_mark_sponsorship_completed]
     * @params    [$payment_state (string) payment result state, $user_id (int) current user ID]
     * @returns   [bool true when a sponsorship is marked completed, otherwise false]
     * @status    NEEDS REVIEW
     */
    function kbf_dashboard_handle_payment_success($payment_state, $user_id) {
        if ($payment_state !== 'success' || !$user_id) return false;
        global $wpdb;
        $st = $wpdb->prefix . 'kbf_sponsorships';
        $user_obj = get_userdata((int) $user_id);
        $current_email = $user_obj && !empty($user_obj->user_email) ? strtolower((string) $user_obj->user_email) : '';
        $is_admin = user_can((int) $user_id, 'manage_options');
        $can_mark = function($email) use ($is_admin, $current_email) {
            if ($is_admin) return true;
            if ($current_email === '' || $email === null) return false;
            return strtolower((string) $email) === $current_email;
        };

        $sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $ref = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';

        if ($sid > 0) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT id,email FROM {$st} WHERE id=%d", $sid));
            if ($row && isset($row->id) && $can_mark(isset($row->email) ? $row->email : null)) {
                kbf_mark_sponsorship_completed((int)$row->id);
                return true;
            }
        } elseif ($ref !== '') {
            $row = $wpdb->get_row($wpdb->prepare("SELECT id,email FROM {$st} WHERE rand_id=%s", $ref));
            if ($row && isset($row->id) && $can_mark(isset($row->email) ? $row->email : null)) {
                kbf_mark_sponsorship_completed((int)$row->id);
                return true;
            }
        }
        return false;
    }
}

/**
 * @function  bntm_shortcode_kbf_dashboard
 * @purpose   Renders the user dashboard shortcode output including routing, gating, and payment feedback UI.
 * @used-by   [add_shortcode('kbf_dashboard','bntm_shortcode_kbf_dashboard') in modules/kb/includes/shortcodes.php]
 * @calls     [kbf_global_assets, is_user_logged_in, wp_get_current_user, wpdb->prepare, wpdb->get_row, sanitize_text_field, kbf_dashboard_public_tabs, kbf_dashboard_default_tab, in_array, remove_query_arg, kbf_dashboard_get_current_url, add_query_arg, wp_safe_redirect, kbf_dashboard_build_nonces, kbf_get_setting, current_user_can, kbf_dashboard_handle_payment_success, kbf_get_page_url, ob_start, ob_get_clean, bntm_universal_container, kbf_dashboard_get_blocked_label]
 * @params    [none]
 * @returns   [string rendered dashboard HTML]
 * @status    ACTIVE
 */
function bntm_shortcode_kbf_dashboard() {
    kbf_global_assets();
    $is_logged_in = is_user_logged_in();
    $user         = $is_logged_in ? wp_get_current_user() : null;
    global $wpdb;
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $nav_profile = $is_logged_in
        ? $wpdb->get_row($wpdb->prepare("SELECT avatar_url,is_verified FROM {$pt} WHERE business_id=%d", $user->ID))
        : null;
    $payout_profile = $is_logged_in
        ? $wpdb->get_row($wpdb->prepare("SELECT payout_type, payout_name, payout_number FROM {$pt} WHERE business_id=%d", $user->ID))
        : null;
    $payout_type = $payout_profile->payout_type ?? '';
    $payout_name = $payout_profile->payout_name ?? '';
    $payout_number = $payout_profile->payout_number ?? '';
    $business_id = $is_logged_in ? $user->ID : 0;
    $tab         = isset($_GET['kbf_tab']) ? sanitize_text_field($_GET['kbf_tab']) : '';
    $blocked_tab = '';

    if (!$is_logged_in) {
        $public_tabs = kbf_dashboard_public_tabs();
        if (!$tab) {
            $tab = kbf_dashboard_default_tab();
        }
        if (!in_array($tab, $public_tabs, true)) {
            $blocked_tab = $tab;
            $tab = kbf_dashboard_default_tab();
        }
    } else {
        if (!$tab) {
            $current_url = remove_query_arg('kbf_tab', kbf_dashboard_get_current_url());
            $target = add_query_arg('kbf_tab', 'overview', $current_url);
            wp_safe_redirect($target);
            exit;
        }
    }
    $nonces = kbf_dashboard_build_nonces();
    $nonce_create = $nonces['create'];
    $nonce_edit   = $nonces['edit'];
    $nonce_cancel = $nonces['cancel'];
    $nonce_wd     = $nonces['wd'];
    $nonce_extend = $nonces['extend'];
    $nonce_appeal = $nonces['appeal'];
    $nonce_escrow = $nonces['escrow'];
    $nonce_refresh = $nonces['refresh'];
    $payment_state = isset($_GET['kbf_payment']) ? sanitize_text_field($_GET['kbf_payment']) : '';
    $demo_mode = (bool) kbf_get_setting('kbf_demo_mode', true);
    $has_valid_payment_ref = false;
    if ($is_logged_in && $payment_state === 'success') {
        $sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $ref = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';
        if ($sid > 0 || $ref !== '') {
            $st = $wpdb->prefix . 'kbf_sponsorships';
            $row = null;
            if ($sid > 0) {
                $row = $wpdb->get_row($wpdb->prepare("SELECT id,email FROM {$st} WHERE id=%d", $sid));
            } elseif ($ref !== '') {
                $row = $wpdb->get_row($wpdb->prepare("SELECT id,email FROM {$st} WHERE rand_id=%s", $ref));
            }
            if ($row && isset($row->id)) {
                $is_admin = current_user_can('manage_options');
                $current_email = $user && !empty($user->user_email) ? strtolower((string) $user->user_email) : '';
                $row_email = isset($row->email) ? strtolower((string) $row->email) : '';
                $has_valid_payment_ref = $is_admin || ($current_email !== '' && $row_email !== '' && $current_email === $row_email);
            }
        }
    }

    // Confirm payment in demo mode only (webhook is disabled in demo, so we confirm on redirect).
    if ($is_logged_in && $demo_mode && $payment_state === 'success' && $has_valid_payment_ref) {
        kbf_dashboard_handle_payment_success($payment_state, $business_id);
    }

    // Show "Thank You" card only when opened as a popup tab from Maya.
    // Regular same-tab redirect shows the premium banner in Find Funds instead.
    $is_popup = !empty($_GET['kbf_popup']) && $_GET['kbf_popup'] === '1';
    if ($is_logged_in && $demo_mode && $is_popup && $payment_state === 'success' && $has_valid_payment_ref) {
        $find_url = add_query_arg('kbf_tab', 'find_funds', kbf_get_page_url('dashboard'));
        ob_start();
    ?>

    <div class="kbf-user-ui">
          <div class="kbf-card" style="max-width:640px;margin:50px auto;padding:34px 30px;text-align:center;">
            <div style="font-size:26px;font-weight:800;color:var(--kbf-navy);margin-bottom:8px;">Thank You</div>
            <div style="font-size:14px;color:var(--kbf-slate);margin-bottom:22px;">Thank you for your donation or support.</div>
            <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url($find_url); ?>" onclick="window.close();return false;">Close Tab</a>
          </div>
        </div>
        <script>
          (function(){
            try{
              // Notify opener tab via localStorage (reliable across tabs)
              try {
                localStorage.setItem('kbf_payment_success', JSON.stringify({
                  ts: Date.now(),
                  sid: '<?php echo isset($_GET['sid']) ? esc_js($_GET['sid']) : ''; ?>',
                  ref: '<?php echo isset($_GET['ref']) ? esc_js($_GET['ref']) : ''; ?>'
                }));
              } catch(e){}
              if (window.opener && !window.opener.closed) {
                var targetOrigin = window.location.origin;
                try { targetOrigin = new URL('<?php echo esc_js(home_url('/')); ?>', window.location.href).origin; } catch(e) {}
                window.opener.postMessage({type:'kbf_payment_success', sid:'<?php echo isset($_GET['sid']) ? esc_js($_GET['sid']) : ''; ?>'}, targetOrigin);
                setTimeout(function(){ window.close(); }, 400);
              }
            } catch(e){}
          })();
        </script>
        <?php
        return ob_get_clean();
    }

    ob_start();
    ?>
    <?php if(false): ?><div></div><?php endif; ?>


    <?php include __DIR__ . '/dashboard/styles.php'; ?>

    <div class="kbf-user-ui">
    <?php include __DIR__ . '/dashboard/modals.php'; ?>
    <?php include __DIR__ . '/dashboard/sections.php'; ?>
    </div><!-- .kbf-user-ui -->

    <?php include __DIR__ . '/dashboard/scripts.php'; ?>
    <?php if (!$is_logged_in && $blocked_tab): ?>
      <?php
        $blocked_label = kbf_dashboard_get_blocked_label($blocked_tab);
      ?>
      <script>
        window.addEventListener('load', function(){
          if (window.kbfOpenAuthModal) {
            window.kbfOpenAuthModal('Sign in to access <?php echo esc_js($blocked_label); ?>.');
          }
        });
      </script>
    <?php endif; ?>

    <?php
    $content = ob_get_clean();
    return bntm_universal_container('KonekBayan -- Finding Platform', $content, ['show_topbar'=>false,'show_header'=>false,'wrap'=>false]);
}
