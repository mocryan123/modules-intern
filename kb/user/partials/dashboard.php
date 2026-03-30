<?php
/* User dashboard shortcode */
function bntm_shortcode_kbf_dashboard() {
    if (!is_user_logged_in()) {
        return '<div class="kbf-wrap"><div class="kbf-alert kbf-alert-warning">Please log in to access your dashboard.</div></div>';
    }
    kbf_global_assets();
    $user        = wp_get_current_user();
    global $wpdb;
    $pt = $wpdb->prefix.'kbf_organizer_profiles';
    $nav_profile = $wpdb->get_row($wpdb->prepare("SELECT avatar_url,is_verified FROM {$pt} WHERE business_id=%d", $user->ID));
    $payout_profile = $wpdb->get_row($wpdb->prepare("SELECT payout_type, payout_name, payout_number FROM {$pt} WHERE business_id=%d", $user->ID));
    $payout_type = $payout_profile->payout_type ?? '';
    $payout_name = $payout_profile->payout_name ?? '';
    $payout_number = $payout_profile->payout_number ?? '';
    $business_id = $user->ID;
    $tab         = isset($_GET['kbf_tab']) ? sanitize_text_field($_GET['kbf_tab']) : 'overview';
    if (!isset($_GET['kbf_tab'])) {
        $current_url = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $current_url = home_url($_SERVER['REQUEST_URI']);
        }
        if (!$current_url) {
            $current_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        }
        $current_url = remove_query_arg('kbf_tab', $current_url);
        $target = add_query_arg('kbf_tab', 'overview', $current_url);
        wp_safe_redirect($target);
        exit;
    }
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
    
    <div class="kbf-user-ui">
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
    <?php if(false): ?><div></div><?php endif; ?>


    <?php include __DIR__ . '/dashboard/styles.php'; ?>

    <div class="kbf-user-ui">
    <?php include __DIR__ . '/dashboard/modals.php'; ?>
    <?php include __DIR__ . '/dashboard/sections.php'; ?>
    </div><!-- .kbf-user-ui -->

    <?php include __DIR__ . '/dashboard/scripts.php'; ?>

    <?php
    $content = ob_get_clean();
    return bntm_universal_container('KonekBayan -- Finding Platform', $content, ['show_topbar'=>false,'show_header'=>false,'wrap'=>false]);
}
