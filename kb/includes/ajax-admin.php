<?php
/*
 * KBF admin AJAX handlers (approvals, escrow, reports, settings).
 */

if (!defined('ABSPATH')) exit;

function bntm_ajax_kbf_admin_approve_fund() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $wpdb->update($t,['status'=>'active'],['id'=>$id],['%s'],['%d']);
    // =====================================================
    // NOTIFICATION PLACEHOLDER
    // TODO: Notify funder their fund is now live
    // do_action('kbf_fund_approved', $id);
    // =====================================================
    wp_send_json_success(['message'=>'Fund approved and is now live!']);
}

function bntm_ajax_kbf_admin_reject_fund() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $notes=sanitize_text_field(isset($_POST['reason']) ? $_POST['reason'] : '');
    $wpdb->update($t,['status'=>'cancelled','admin_notes'=>$notes],['id'=>$id],['%s','%s'],['%d']);
    wp_send_json_success(['message'=>'Fund rejected.']);
}

function bntm_ajax_kbf_admin_suspend_fund() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $wpdb->update($t,['status'=>'suspended'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Fund suspended.']);
}

function bntm_ajax_kbf_admin_verify_badge() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);$v=intval($_POST['verified']);
    $wpdb->update($t,['verified_badge'=>$v],['id'=>$id],['%d'],['%d']);
    wp_send_json_success(['message'=>$v?'Verified badge granted!':'Badge removed.']);
}

function bntm_ajax_kbf_admin_release_escrow() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $wpdb->update($t,['escrow_status'=>'released'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Escrow released. Organizer can now withdraw funds.']);
}

function bntm_ajax_kbf_admin_hold_escrow() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $wpdb->update($t,['escrow_status'=>'holding'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Funds placed on hold.']);
}

function bntm_ajax_kbf_admin_process_withdrawal() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$wt=$wpdb->prefix.'kbf_withdrawals';$ft=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['withdrawal_id']);$type=sanitize_text_field($_POST['action_type']);$notes=sanitize_text_field(isset($_POST['notes']) ? $_POST['notes'] : '');
    $wd=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wt} WHERE id=%d",$id));
    if(!$wd) wp_send_json_error(['message'=>'Withdrawal not found.']);
    if($type==='approve') {
        $wpdb->update($wt,['status'=>'released','processed_at'=>current_time('mysql'),'admin_notes'=>$notes],['id'=>$id],['%s','%s','%s'],['%d']);
        $wpdb->update($ft,['escrow_status'=>'released'],['id'=>$wd->fund_id],['%s'],['%d']);
        wp_send_json_success(['message'=>'Withdrawal approved and released!']);
    } else {
        $wpdb->update($wt,['status'=>'rejected','processed_at'=>current_time('mysql'),'admin_notes'=>$notes],['id'=>$id],['%s','%s','%s'],['%d']);
        wp_send_json_success(['message'=>'Withdrawal rejected.']);
    }
}

function bntm_ajax_kbf_admin_dismiss_report() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_reports';$id=intval($_POST['report_id']);
    $wpdb->update($t,['status'=>'dismissed'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Report dismissed.']);
}

function bntm_ajax_kbf_admin_review_report() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_reports';$id=intval($_POST['report_id']);
    $notes=sanitize_text_field(isset($_POST['notes']) ? $_POST['notes'] : '');
    $wpdb->update($t,['status'=>'reviewed','admin_notes'=>$notes],['id'=>$id],['%s','%s'],['%d']);
    wp_send_json_success(['message'=>'Report marked as reviewed.']);
}

function bntm_ajax_kbf_admin_review_appeal() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $at = $wpdb->prefix.'kbf_appeals';
    $ft = $wpdb->prefix.'kbf_funds';
    $id = intval(isset($_POST['appeal_id']) ? $_POST['appeal_id'] : 0);
    $action = sanitize_text_field(isset($_POST['action_type']) ? $_POST['action_type'] : '');
    $notes = sanitize_text_field(isset($_POST['notes']) ? $_POST['notes'] : '');
    $appeal = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$at} WHERE id=%d", $id));
    if (!$appeal) wp_send_json_error(['message'=>'Appeal not found.']);

    if ($action === 'approve') {
        $wpdb->update($at, ['status'=>'approved','admin_notes'=>$notes], ['id'=>$id], ['%s','%s'], ['%d']);
        $wpdb->update($ft, ['status'=>'active'], ['id'=>$appeal->fund_id], ['%s'], ['%d']);
        wp_send_json_success(['message'=>'Appeal approved. Fund reinstated.']);
    } else {
        $wpdb->update($at, ['status'=>'rejected','admin_notes'=>$notes], ['id'=>$id], ['%s','%s'], ['%d']);
        wp_send_json_success(['message'=>'Appeal rejected.']);
    }
}

function bntm_ajax_kbf_admin_confirm_payment() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$st=$wpdb->prefix.'kbf_sponsorships';$ft=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['sponsorship_id']);
    $sp=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$st} WHERE id=%d",$id));
    if(!$sp||$sp->payment_status==='completed') wp_send_json_error(['message'=>'Sponsorship not found or already confirmed.']);
    $wpdb->update($st,['payment_status'=>'completed'],['id'=>$id],['%s'],['%d']);
    // Update raised amount on fund
    $wpdb->query($wpdb->prepare("UPDATE {$ft} SET raised_amount=raised_amount+%f WHERE id=%d",$sp->amount,$sp->fund_id));
    // Update organizer profile stats
    $fund=$wpdb->get_row($wpdb->prepare("SELECT business_id FROM {$ft} WHERE id=%d",$sp->fund_id));
    if($fund) {
        $pt=$wpdb->prefix.'kbf_organizer_profiles';
        $total=$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(s.amount),0) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$fund->business_id));
        $cnt=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$fund->business_id));
        $wpdb->update($pt,['total_raised'=>$total,'total_sponsors'=>$cnt],['business_id'=>$fund->business_id],['%f','%d'],['%d']);
    }
    // =====================================================
    // NOTIFICATION PLACEHOLDER
    // TODO: Send receipt to sponsor here
    // do_action('kbf_send_sponsorship_receipt', $id, $sp->fund_id);
    // =====================================================
    wp_send_json_success(['message'=>'Payment confirmed! Sponsor notified.']);
}

function bntm_ajax_kbf_admin_verify_organizer() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$pt=$wpdb->prefix.'kbf_organizer_profiles';
    $biz=intval($_POST['business_id']);$v=intval($_POST['verified']);
    $exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d",$biz));
    if($exists) $wpdb->update($pt,['is_verified'=>$v],['business_id'=>$biz],['%d'],['%d']);
    else $wpdb->insert($pt,['business_id'=>$biz,'is_verified'=>$v],['%d','%d']);
    wp_send_json_success(['message'=>$v?'Organizer verified!':'Verification revoked.']);
}

function bntm_ajax_kbf_save_setting() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    $key = sanitize_key(isset($_POST['setting_key']) ? $_POST['setting_key'] : '');
    $val = sanitize_text_field(isset($_POST['setting_val']) ? $_POST['setting_val'] : '');
    if(empty($key)) wp_send_json_error(['message'=>'Invalid setting key.']);
    kbf_set_setting($key, $val);
    $labels = ['kbf_demo_mode' => ['0'=>'Live Mode activated. Sponsorships now require payment confirmation.','1'=>'Demo Mode activated. Sponsorships will be auto-confirmed.']];
    $msg = (isset($labels[$key]) && isset($labels[$key][$val])) ? $labels[$key][$val] : 'Setting saved!';
    wp_send_json_success(['message'=>$msg]);
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================


