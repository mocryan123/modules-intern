<?php
/* User dashboard shortcode */
if (!function_exists('kbf_dashboard_public_tabs')) {
    function kbf_dashboard_public_tabs() {
        return ['find_funds','fund_details','organizer_profile'];
    }
}

if (!function_exists('kbf_dashboard_default_tab')) {
    function kbf_dashboard_default_tab() {
        return 'find_funds';
    }
}

if (!function_exists('kbf_dashboard_get_current_url')) {
    function kbf_dashboard_get_current_url() {
        if (isset($_SERVER['REQUEST_URI'])) {
            return home_url($_SERVER['REQUEST_URI']);
        }
        return function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
    }
}

if (!function_exists('kbf_dashboard_build_nonces')) {
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
        ];
    }
}

if (!function_exists('kbf_dashboard_get_blocked_label')) {
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
    function kbf_dashboard_handle_payment_success($payment_state, $user_id) {
        if ($payment_state !== 'success' || !$user_id) return false;

        $sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $ref = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';

        if ($sid > 0) {
            kbf_mark_sponsorship_completed($sid);
        } elseif ($ref !== '') {
            global $wpdb;
            $st = $wpdb->prefix . 'kbf_sponsorships';
            $row = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$st} WHERE rand_id=%s", $ref));
            if ($row && isset($row->id)) {
                kbf_mark_sponsorship_completed((int)$row->id);
            }
        }
        return true;
    }
}

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

    // Confirm payment in demo mode (webhook is disabled in demo, so we confirm on redirect).
    if ($is_logged_in && $payment_state === 'success') {
        kbf_dashboard_handle_payment_success($payment_state, $business_id);
    }

    // Show "Thank You" card only when opened as a popup tab from Maya.
    // Regular same-tab redirect shows the premium banner in Find Funds instead.
    $is_popup = !empty($_GET['kbf_popup']) && $_GET['kbf_popup'] === '1';
    if ($is_logged_in && $is_popup && $payment_state === 'success') {
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
                window.opener.postMessage({type:'kbf_payment_success', sid:'<?php echo isset($_GET['sid']) ? esc_js($_GET['sid']) : ''; ?>'}, '*');
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
