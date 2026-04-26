<?php
/*
 * KBF admin AJAX handlers (approvals, escrow, reports, settings).
 */

if (!defined('ABSPATH')) exit;

function bntm_ajax_kbf_admin_approve_fund() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $fund = $wpdb->get_row($wpdb->prepare("SELECT id,business_id,title FROM {$t} WHERE id=%d", $id));
    $wpdb->update($t,['status'=>'active'],['id'=>$id],['%s'],['%d']);
    if ($fund && function_exists('kbf_push_user_notification')) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        kbf_push_user_notification((int)$fund->business_id, [
            'type' => 'fund_approved',
            'title' => 'Fund approved',
            'message' => 'Your fundraiser is now live and visible to supporters.',
            'url' => add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$fund->id], $dashboard_url),
            'target_id' => (string)((int)$fund->id),
        ]);
    }
    wp_send_json_success(['message'=>'Fund approved and is now live!']);
}

function bntm_ajax_kbf_admin_reject_fund() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $notes=sanitize_text_field(isset($_POST['reason']) ? $_POST['reason'] : '');
    $fund = $wpdb->get_row($wpdb->prepare("SELECT id,business_id,title FROM {$t} WHERE id=%d", $id));
    $wpdb->update($t,['status'=>'cancelled','admin_notes'=>$notes],['id'=>$id],['%s','%s'],['%d']);
    if ($fund && function_exists('kbf_push_user_notification')) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        kbf_push_user_notification((int)$fund->business_id, [
            'type' => 'fund_rejected',
            'title' => 'Fund rejected',
            'message' => $notes !== '' ? ('Reason: ' . $notes) : 'Your fundraiser was not approved.',
            'url' => add_query_arg(['kbf_tab' => 'profile'], $dashboard_url),
            'target_id' => (string)((int)$fund->id),
        ]);
    }
    wp_send_json_success(['message'=>'Fund rejected.']);
}

function bntm_ajax_kbf_admin_suspend_fund() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';$id=intval($_POST['fund_id']);
    $fund = $wpdb->get_row($wpdb->prepare("SELECT id,business_id FROM {$t} WHERE id=%d", $id));
    $wpdb->update($t,['status'=>'suspended'],['id'=>$id],['%s'],['%d']);
    if ($fund && function_exists('kbf_push_user_notification')) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        kbf_push_user_notification((int)$fund->business_id, [
            'type' => 'fund_suspended',
            'title' => 'Fund suspended',
            'message' => 'Your fundraiser was suspended after review.',
            'url' => add_query_arg(['kbf_tab' => 'find_funds', 'fund_id' => (int)$fund->id], $dashboard_url),
            'target_id' => (string)((int)$fund->id),
        ]);
    }
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
        $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d", $wd->fund_id));
        $rate = function_exists('kbf_get_platform_fee_rate') ? kbf_get_platform_fee_rate($fund) : 0.05;
        $gross = (float) $wd->amount;
        $fee = round($gross * $rate, 2);
        $net = max(0, $gross - $fee);
        $fee_note = $rate > 0
            ? sprintf('Platform fee %.0f%%: PHP %s. Net payout: PHP %s (from PHP %s).', $rate * 100, number_format($fee, 2), number_format($net, 2), number_format($gross, 2))
            : sprintf('Platform fee disabled. Net payout: PHP %s (from PHP %s).', number_format($net, 2), number_format($gross, 2));
        $final_notes = trim($notes . ($notes ? "\n" : "") . $fee_note);
        $wpdb->update($wt, [
            'status' => 'released',
            'processed_at' => current_time('mysql'),
            'admin_notes' => $final_notes,
            'amount' => $net,
        ], ['id'=>$id], ['%s','%s','%s','%f'], ['%d']);
        $wpdb->update($ft,['escrow_status'=>'released'],['id'=>$wd->fund_id],['%s'],['%d']);
        $fund = $wpdb->get_row($wpdb->prepare("SELECT id,business_id FROM {$ft} WHERE id=%d", $wd->fund_id));
        if ($fund && function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            kbf_push_user_notification((int)$fund->business_id, [
                'type' => 'withdrawal_released',
                'title' => 'Withdrawal approved',
                'message' => 'Your withdrawal request was approved and released.',
                'url' => add_query_arg(['kbf_tab' => 'withdrawals'], $dashboard_url),
                'target_id' => (string)((int)$wd->fund_id),
            ]);
        }
        wp_send_json_success(['message'=>'Withdrawal approved and released!']);
    } else {
        $wpdb->update($wt,['status'=>'rejected','processed_at'=>current_time('mysql'),'admin_notes'=>$notes],['id'=>$id],['%s','%s','%s'],['%d']);
        $fund = $wpdb->get_row($wpdb->prepare("SELECT id,business_id FROM {$ft} WHERE id=%d", $wd->fund_id));
        if ($fund && function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            kbf_push_user_notification((int)$fund->business_id, [
                'type' => 'withdrawal_rejected',
                'title' => 'Withdrawal rejected',
                'message' => $notes !== '' ? ('Reason: ' . $notes) : 'Your withdrawal request was rejected.',
                'url' => add_query_arg(['kbf_tab' => 'withdrawals'], $dashboard_url),
                'target_id' => (string)((int)$wd->fund_id),
            ]);
        }
        wp_send_json_success(['message'=>'Withdrawal rejected.']);
    }
}

function bntm_ajax_kbf_admin_dismiss_report() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_reports';$id=intval($_POST['report_id']);$ft=$wpdb->prefix.'kbf_funds';
    $report = $wpdb->get_row($wpdb->prepare("SELECT fund_id FROM {$t} WHERE id=%d", $id));
    $wpdb->update($t,['status'=>'dismissed'],['id'=>$id],['%s'],['%d']);
    if ($report && function_exists('kbf_push_user_notification')) {
        $fund = $wpdb->get_row($wpdb->prepare("SELECT id,business_id FROM {$ft} WHERE id=%d", (int)$report->fund_id));
        if ($fund) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            kbf_push_user_notification((int)$fund->business_id, [
                'type' => 'report_dismissed',
                'title' => 'Report dismissed',
                'message' => 'A report on your fundraiser was reviewed and dismissed.',
                'url' => add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$fund->id], $dashboard_url),
                'target_id' => (string)((int)$fund->id),
            ]);
        }
    }
    wp_send_json_success(['message'=>'Report dismissed.']);
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
        $wpdb->update($ft, ['status'=>'active','admin_notes'=>''], ['id'=>$appeal->fund_id], ['%s','%s'], ['%d']);
        if (function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            kbf_push_user_notification((int)$appeal->business_id, [
                'type' => 'appeal_approved_reinstated',
                'title' => 'Appeal approved',
                'message' => 'Your appeal was approved and the fund is active again.',
                'url' => add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$appeal->fund_id], $dashboard_url),
                'target_id' => (string)((int)$appeal->fund_id),
            ]);
        }
        wp_send_json_success(['message'=>'Appeal approved. Fund reinstated.']);
    } else {
        $wpdb->update($at, ['status'=>'rejected','admin_notes'=>$notes], ['id'=>$id], ['%s','%s'], ['%d']);
        if ($notes !== '') {
            $wpdb->update($ft, ['status'=>'suspended','admin_notes'=>$notes], ['id'=>$appeal->fund_id], ['%s','%s'], ['%d']);
        } else {
            $wpdb->update($ft, ['status'=>'suspended'], ['id'=>$appeal->fund_id], ['%s'], ['%d']);
        }
        if (function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            kbf_push_user_notification((int)$appeal->business_id, [
                'type' => 'appeal_rejected',
                'title' => 'Appeal rejected',
                'message' => $notes !== '' ? ('Reason: ' . $notes) : 'Your appeal was rejected.',
                'url' => add_query_arg(['kbf_tab' => 'find_funds'], $dashboard_url),
                'target_id' => (string)((int)$appeal->fund_id),
            ]);
        }
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
        $total=$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(s.amount),0) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed' AND s.is_anonymous=0",$fund->business_id));
        $cnt=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed' AND s.is_anonymous=0",$fund->business_id));
        $wpdb->update($pt,['total_raised'=>$total,'total_sponsors'=>$cnt],['business_id'=>$fund->business_id],['%f','%d'],['%d']);
        if (function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            $fund_url = add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$sp->fund_id], $dashboard_url);
            kbf_push_user_notification((int)$fund->business_id, [
                'type' => 'donation_received',
                'title' => 'New sponsorship received',
                'message' => 'You received a new sponsorship worth PHP ' . number_format((float)$sp->amount, 2) . '.',
                'url' => $fund_url,
                'target_id' => (string)((int)$sp->id),
            ]);
            $updated = $wpdb->get_row($wpdb->prepare("SELECT raised_amount,goal_amount,status FROM {$ft} WHERE id=%d", $sp->fund_id));
            if ($updated && $updated->goal_amount > 0 && $updated->raised_amount >= $updated->goal_amount && $updated->status === 'completed') {
                kbf_push_user_notification((int)$fund->business_id, [
                    'type' => 'goal_reached_fund_completed',
                    'title' => 'Goal reached',
                    'message' => 'Your campaign reached its goal and is now completed.',
                    'url' => $fund_url,
                    'target_id' => (string)((int)$sp->fund_id),
                ]);
            }
            $sponsor_user = get_user_by('email', (string)$sp->email);
            if ($sponsor_user && !empty($sponsor_user->ID)) {
                kbf_push_user_notification((int)$sponsor_user->ID, [
                    'type' => 'payment_confirmed',
                    'title' => 'Payment confirmed',
                    'message' => 'Your sponsorship payment was confirmed successfully.',
                    'url' => $fund_url,
                    'target_id' => (string)((int)$sp->id),
                ]);
            }
        }
    }
    wp_send_json_success(['message'=>'Payment confirmed! Sponsor notified.']);
}

function bntm_ajax_kbf_admin_verify_organizer() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$pt=$wpdb->prefix.'kbf_organizer_profiles';
    $biz=intval($_POST['business_id']);$v=intval($_POST['verified']);
    $notes = sanitize_text_field(isset($_POST['notes']) ? $_POST['notes'] : '');
    $exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d",$biz));
    $status = $v ? 'approved' : 'rejected';
    $data = [
        'is_verified' => $v,
        'verify_status' => $status,
        'verify_reviewed_at' => current_time('mysql'),
        'verify_notes' => $notes,
    ];
    if($exists) $wpdb->update($pt,$data,['business_id'=>$biz],['%d','%s','%s','%s'],['%d']);
    else { $data['business_id']=$biz; $wpdb->insert($pt,$data,['%d','%s','%s','%s','%d']); }
    if (function_exists('kbf_push_user_notification')) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        kbf_push_user_notification((int)$biz, [
            'type' => $v ? 'kyc_approved' : 'kyc_declined',
            'title' => $v ? 'Identity verified' : 'Identity verification declined',
            'message' => $v ? 'Your identity verification is approved.' : (($notes !== '') ? ('Reason: ' . $notes) : 'Your identity verification was declined.'),
            'url' => add_query_arg(['kbf_tab' => 'profile'], $dashboard_url),
            'target_id' => (string)((int)$biz),
        ]);
    }
    wp_send_json_success(['message'=>$v?'Organizer verified!':'Verification revoked.']);
}

function bntm_ajax_kbf_admin_process_escrow_request() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $et = $wpdb->prefix.'kbf_escrow_requests';
    $ft = $wpdb->prefix.'kbf_funds';
    $id = intval($_POST['request_id']);
    $action = sanitize_text_field($_POST['action_type']);
    $notes = sanitize_text_field(isset($_POST['notes']) ? $_POST['notes'] : '');
    $req = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$et} WHERE id=%d",$id));
    if(!$req) wp_send_json_error(['message'=>'Request not found.']);
    if($req->status !== 'pending') wp_send_json_error(['message'=>'Request already processed.']);

    if($action === 'approve') {
        $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d", $req->fund_id));
        $rate = function_exists('kbf_get_platform_fee_rate') ? kbf_get_platform_fee_rate($fund) : 0.05;
        $gross = $fund ? (float) $fund->raised_amount : 0.0;
        $fee = round($gross * $rate, 2);
        $net = max(0, $gross - $fee);
        $fee_note = $rate > 0
            ? sprintf('Platform fee %.0f%%: PHP %s. Net payout: PHP %s (from PHP %s).', $rate * 100, number_format($fee, 2), number_format($net, 2), number_format($gross, 2))
            : sprintf('Platform fee disabled. Net payout: PHP %s (from PHP %s).', number_format($net, 2), number_format($gross, 2));
        $final_notes = trim($notes . ($notes ? "\n" : "") . $fee_note);
        $wpdb->update($et, ['status'=>'approved','admin_notes'=>$final_notes,'reviewed_at'=>current_time('mysql')], ['id'=>$id], ['%s','%s','%s'], ['%d']);
        $wpdb->update($ft, ['escrow_status'=>'released'], ['id'=>$req->fund_id], ['%s'], ['%d']);
        if (function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            kbf_push_user_notification((int)$req->business_id, [
                'type' => 'escrow_request_approved',
                'title' => 'Escrow request approved',
                'message' => 'Your escrow request was approved and funds were released.',
                'url' => add_query_arg(['kbf_tab' => 'withdrawals'], $dashboard_url),
                'target_id' => (string)((int)$req->fund_id),
            ]);
        }
        wp_send_json_success(['message'=>'Escrow request approved. Funds released.']);
    } else {
        $wpdb->update($et, ['status'=>'rejected','admin_notes'=>$notes,'reviewed_at'=>current_time('mysql')], ['id'=>$id], ['%s','%s','%s'], ['%d']);
        if (function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            kbf_push_user_notification((int)$req->business_id, [
                'type' => 'escrow_request_rejected',
                'title' => 'Escrow request rejected',
                'message' => $notes !== '' ? ('Reason: ' . $notes) : 'Your escrow release request was rejected.',
                'url' => add_query_arg(['kbf_tab' => 'withdrawals'], $dashboard_url),
                'target_id' => (string)((int)$req->fund_id),
            ]);
        }
        wp_send_json_success(['message'=>'Escrow request rejected.']);
    }
}

function bntm_ajax_kbf_admin_trigger_onboarding() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    $biz = intval(isset($_POST['business_id']) ? $_POST['business_id'] : 0);
    if(!$biz) { wp_send_json_error(['message'=>'Invalid account.']); }
    update_user_meta($biz, 'kbf_show_onboarding', 1);
    wp_send_json_success(['message'=>'Onboarding has been triggered for this account.']);
}


function bntm_kbf_allowed_settings_map() {
    return [
        'kbf_demo_mode' => 'bool',
        'kbf_disable_platform_fee' => 'bool',
        'kbf_maya_sandbox_public' => 'text',
        'kbf_maya_sandbox_secret' => 'text',
        'kbf_maya_live_public' => 'text',
        'kbf_maya_live_secret' => 'text',
        'kbf_maya_webhook_secret' => 'text',
        'kbf_didit_sandbox_api_key' => 'text',
        'kbf_didit_sandbox_app_id' => 'text',
        'kbf_didit_sandbox_workflow_id' => 'text',
        'kbf_didit_live_api_key' => 'text',
        'kbf_didit_live_app_id' => 'text',
        'kbf_didit_live_workflow_id' => 'text',
        'kbf_didit_webhook_secret' => 'text',
    ];
}

function bntm_kbf_normalize_setting_value($key, $raw_val) {
    $allowed = bntm_kbf_allowed_settings_map();
    if (!isset($allowed[$key])) {
        return [false, ''];
    }
    if ($allowed[$key] === 'bool') {
        return [true, ((string)$raw_val === '1' ? '1' : '0')];
    }
    return [true, sanitize_text_field($raw_val)];
}

function bntm_kbf_settings_equal($key, $expected, $actual_raw) {
    list($ok, $actual) = bntm_kbf_normalize_setting_value($key, $actual_raw);
    if (!$ok) {
        return false;
    }
    return (string)$expected === (string)$actual;
}

function bntm_ajax_kbf_save_setting() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    $key = sanitize_key(isset($_POST['setting_key']) ? $_POST['setting_key'] : '');
    if(empty($key)) wp_send_json_error(['message'=>'Invalid setting key.']);
    $raw_val = isset($_POST['setting_val']) ? wp_unslash($_POST['setting_val']) : '';
    list($ok, $val) = bntm_kbf_normalize_setting_value($key, $raw_val);
    if (!$ok) {
        wp_send_json_error(['message' => 'Setting key is not allowed.']);
    }
    kbf_set_setting($key, $val);
    $labels = [
        'kbf_demo_mode' => [
            '0' => 'Live Mode activated. Sponsorships now require payment confirmation.',
            '1' => 'Demo Mode activated. Sponsorships will be auto-confirmed.',
        ],
        'kbf_disable_platform_fee' => [
            '0' => 'Platform fee enabled (5%).',
            '1' => 'Platform fee disabled (0%).',
        ],
    ];
    $msg = (isset($labels[$key]) && isset($labels[$key][$val])) ? $labels[$key][$val] : 'Setting saved!';
    wp_send_json_success(['message'=>$msg]);
}

function bntm_ajax_kbf_save_settings_batch() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }

    $raw_json = isset($_POST['settings_json']) ? wp_unslash($_POST['settings_json']) : '';
    if ($raw_json === '') {
        wp_send_json_error(['message' => 'No settings payload provided.']);
    }

    $decoded = json_decode($raw_json, true);
    if (!is_array($decoded) || empty($decoded)) {
        wp_send_json_error(['message' => 'Invalid settings payload.']);
    }

    $normalized = [];
    foreach ($decoded as $raw_key => $raw_val) {
        $key = sanitize_key((string)$raw_key);
        if ($key === '') {
            wp_send_json_error(['message' => 'Invalid setting key in payload.']);
        }
        list($ok, $val) = bntm_kbf_normalize_setting_value($key, $raw_val);
        if (!$ok) {
            wp_send_json_error(['message' => 'Setting key is not allowed: ' . $key]);
        }
        $normalized[$key] = $val;
    }

    $previous = [];
    foreach ($normalized as $key => $_) {
        $previous[$key] = kbf_get_setting($key, '');
    }

    try {
        foreach ($normalized as $key => $val) {
            kbf_set_setting($key, $val);
            $saved = kbf_get_setting($key, '');
            if (!bntm_kbf_settings_equal($key, $val, $saved)) {
                throw new Exception('Failed to persist setting: ' . $key);
            }
        }
    } catch (Throwable $e) {
        $rollback_failed = [];
        foreach ($previous as $key => $old_val) {
            kbf_set_setting($key, $old_val);
            $restored = kbf_get_setting($key, '');
            if (!bntm_kbf_settings_equal($key, $old_val, $restored)) {
                $rollback_failed[] = $key;
            }
        }
        if (!empty($rollback_failed)) {
            wp_send_json_error([
                'message' => 'Save failed and rollback was incomplete for: ' . implode(', ', $rollback_failed) . '. Please retry.',
            ]);
        }
        wp_send_json_error(['message' => 'Save aborted. Previous settings were restored.']);
    }

    wp_send_json_success(['message' => 'Settings saved successfully.']);
}

function bntm_ajax_kbf_admin_refresh_tab() {
    check_ajax_referer('kbf_admin_action');
    if(!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized']); }
    $tab = sanitize_text_field(isset($_POST['tab']) ? $_POST['tab'] : 'pending');
    $html = '';
    if ($tab === 'pending') {
        $html = kbf_admin_pending_tab();
    } elseif ($tab === 'all_funds') {
        $html = kbf_admin_all_funds_tab();
    } elseif ($tab === 'transactions') {
        $html = kbf_admin_transactions_tab();
    } elseif ($tab === 'withdrawals') {
        $html = kbf_admin_withdrawals_tab();
    } elseif ($tab === 'reports') {
        $html = kbf_admin_reports_tab();
    } elseif ($tab === 'appeals') {
        $html = kbf_admin_appeals_tab();
    } elseif ($tab === 'organizers') {
        $html = kbf_admin_organizers_tab();
    } elseif ($tab === 'security') {
        $html = kbf_admin_security_logs_tab();
    } elseif ($tab === 'settings') {
        $html = kbf_admin_settings_tab();
    }

    if ($html === '') {
        $html = '<div class="kbf-empty"><p>Nothing to refresh.</p></div>';
    }

    global $wpdb;
    $counts = [
        'pending'     => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_funds WHERE status='pending'"), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        'reports'     => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_reports WHERE status='open'"),   // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        'withdrawals' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_withdrawals WHERE status='pending'"), // phpcs:ignore
        'appeals'     => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kbf_appeals WHERE status='open'"),  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    ];

    wp_send_json_success([
        'html'   => $html,
        'counts' => $counts,
    ]);
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================


