<?php
/*
 * KBF user-facing AJAX handlers (funds, sponsor actions, lookups).
 */

if (!defined('ABSPATH')) exit;

function bntm_ajax_kbf_create_fund() {
    check_ajax_referer('kbf_create_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$table=$wpdb->prefix.'kbf_funds';
    $biz=get_current_user_id();
    $location_full = isset($_POST['location_full']) && $_POST['location_full'] !== '' ? $_POST['location_full'] : (isset($_POST['location']) ? $_POST['location'] : '');
    foreach(['title','description','goal_amount','email','phone','category','funder_type','deadline'] as $f) {
        if(empty($_POST[$f])) wp_send_json_error(['message'=>'Please fill all required fields.']);
    }
    if (empty($location_full)) wp_send_json_error(['message'=>'Please fill all required fields.']);
    $goal=floatval($_POST['goal_amount']);
    $deadline = sanitize_text_field($_POST['deadline'] ?? '');
    if(!$deadline) wp_send_json_error(['message'=>'Please fill all required fields.']);
    $min_deadline = strtotime('+7 days', current_time('timestamp'));
    if(strtotime($deadline) < $min_deadline) {
        wp_send_json_error(['message'=>'Deadline must be at least 7 days from today.']);
    }
    if($goal<100) wp_send_json_error(['message'=>'Minimum goal is ?100.']);
    // Handle photos
    $photo_urls=[];
    if(!empty($_FILES['photos']['name'][0])) {
        if(!function_exists('wp_handle_upload')) require_once(ABSPATH.'wp-admin/includes/file.php');
        $count=min(5,count($_FILES['photos']['name']));
        for($i=0;$i<$count;$i++) {
            $file=['name'=>$_FILES['photos']['name'][$i],'type'=>$_FILES['photos']['type'][$i],'tmp_name'=>$_FILES['photos']['tmp_name'][$i],'error'=>$_FILES['photos']['error'][$i],'size'=>$_FILES['photos']['size'][$i]];
            $up=wp_handle_upload($file,['test_form'=>false]);
            if(isset($up['url'])) $photo_urls[]=$up['url'];
        }
    }
    $res=$wpdb->insert($table,[
        'rand_id'       =>bntm_rand_id(),
        'business_id'   =>$biz,
        'funder_type'   =>sanitize_text_field($_POST['funder_type']),
        'title'         =>sanitize_text_field($_POST['title']),
        'description'   =>sanitize_textarea_field($_POST['description']),
        'photos'        =>!empty($photo_urls)?json_encode($photo_urls):null,
        'goal_amount'   =>$goal,
        'category'      =>sanitize_text_field($_POST['category']),
        'email'         =>sanitize_email($_POST['email']),
        'phone'         =>sanitize_text_field($_POST['phone']),
        'location'      =>sanitize_text_field($location_full),
        'auto_return'   =>isset($_POST['auto_return'])?1:0,
        'deadline'      =>$deadline,
        'status'        =>'pending',
        'share_token'   =>wp_generate_password(32,false),
    ],['%s','%d','%s','%s','%s','%s','%f','%s','%s','%s','%s','%d','%s','%s','%s']);
    // Ensure organizer profile exists
    $pt=$wpdb->prefix.'kbf_organizer_profiles';
    if(!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d",$biz))) {
        $wpdb->insert($pt,['business_id'=>$biz],['%d']);
    }
    if (function_exists('kbf_get_or_create_organizer_token')) {
        kbf_get_or_create_organizer_token($biz);
    }
    if($res) wp_send_json_success(['message'=>'Fund submitted for review! We will notify you once approved.']);
    else wp_send_json_error(['message'=>'Failed to create fund. Please try again.']);
}


function bntm_ajax_kbf_update_fund() {
    check_ajax_referer('kbf_update_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    $location_full = isset($_POST['location_full']) && $_POST['location_full'] !== '' ? $_POST['location_full'] : (isset($_POST['location']) ? $_POST['location'] : '');
    $data=[
        'title'=>sanitize_text_field($_POST['title']),
        'description'=>sanitize_textarea_field($_POST['description']),
        'location'=>sanitize_text_field($location_full),
        'deadline'=>!empty($_POST['deadline']) ? sanitize_text_field($_POST['deadline']) : null,
        'auto_return'=>isset($_POST['auto_return']) ? 1 : 0
    ];
    // New photos
    if(!empty($_FILES['photos']['name'][0])) {
        if(!function_exists('wp_handle_upload')) require_once(ABSPATH.'wp-admin/includes/file.php');
        $existing=$fund->photos?json_decode($fund->photos,true):[];
        $count=min(5,count($_FILES['photos']['name']));
        for($i=0;$i<$count;$i++) {
            if(count($existing)>=5) break;
            $file=['name'=>$_FILES['photos']['name'][$i],'type'=>$_FILES['photos']['type'][$i],'tmp_name'=>$_FILES['photos']['tmp_name'][$i],'error'=>$_FILES['photos']['error'][$i],'size'=>$_FILES['photos']['size'][$i]];
            $up=wp_handle_upload($file,['test_form'=>false]);
            if(isset($up['url'])) $existing[]=$up['url'];
        }
        $data['photos']=json_encode($existing);
    }
    $res=$wpdb->update($t,$data,['id'=>$id],array_fill(0,count($data),'%s'),['%d']);
    if($res!==false) wp_send_json_success(['message'=>'Fund updated successfully!']);
    else wp_send_json_error(['message'=>'Failed to update fund.']);
}

function bntm_ajax_kbf_cancel_fund() {
    check_ajax_referer('kbf_cancel_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    if(in_array($fund->status,['cancelled','completed'])) wp_send_json_error(['message'=>'This fund cannot be cancelled.']);
    if($fund->raised_amount>0 && $fund->auto_return) kbf_refund_all_sponsors($id);
    $wpdb->update($t,['status'=>'cancelled'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Fund cancelled.']);
}

function bntm_ajax_kbf_mark_fund_complete() {
    check_ajax_referer('kbf_cancel_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund||$fund->status!=='active') wp_send_json_error(['message'=>'Fund not found or not active.']);
    $wpdb->update($t,['status'=>'completed','escrow_status'=>'released'],['id'=>$id],['%s','%s'],['%d']);
    wp_send_json_success(['message'=>'Fund marked as complete!']);
}

function bntm_ajax_kbf_request_withdrawal() {
    check_ajax_referer('kbf_withdrawal','nonce');
    global $wpdb;$ft=$wpdb->prefix.'kbf_funds';$wt=$wpdb->prefix.'kbf_withdrawals';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();$amount=floatval($_POST['amount']);
    $fund = $biz
        ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND business_id=%d",$id,$biz))
        : $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d",$id));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    if(!$biz) {
        $email = sanitize_email($_POST['funder_email']??'');
        if(empty($email) || strcasecmp($email, (string)$fund->email) !== 0) {
            wp_send_json_error(['message'=>'Unauthorized. Please use the funder email on record.']);
        }
    }
    if(!in_array($fund->status, ['active', 'completed'])) wp_send_json_error(['message'=>'Withdrawals are only available for active or completed fundraisers.']);
    if($fund->escrow_status === 'refunded') wp_send_json_error(['message'=>'Funds have been refunded and are no longer available for withdrawal.']);
    if($amount<=0) wp_send_json_error(['message'=>'Please enter a valid amount.']);
    if($amount>$fund->raised_amount) wp_send_json_error(['message'=>'Amount exceeds total raised funds (PHP '.number_format($fund->raised_amount,2).' ).']);
    if(empty($_POST['method'])||empty($_POST['account_type'])||empty($_POST['account_name'])||empty($_POST['account_number'])) wp_send_json_error(['message'=>'Please fill all required fields.']);
    // Prevent duplicate pending request for same fund
    $pending=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wt} WHERE fund_id=%d AND status='pending'",$id));
    if($pending>0) wp_send_json_error(['message'=>'You already have a pending withdrawal request for this fund. Please wait for admin to process it first.']);
    $funder_name = '';
    if($biz) {
        $user = get_userdata($biz);
        $funder_name = $user ? $user->display_name : '';
    } else {
        $funder_name = sanitize_text_field($_POST['funder_name']??'');
    }
    if(!$funder_name && $fund->business_id) {
        $u = get_userdata($fund->business_id);
        $funder_name = $u ? $u->display_name : '';
    }
    if(!$funder_name) $funder_name = 'Funder';
    $res=$wpdb->insert($wt,[
        'rand_id'        =>bntm_rand_id(),
        'fund_id'        =>$id,
        'funder_name'    =>$funder_name,
        'amount'         =>$amount,
        'method'         =>sanitize_text_field($_POST['method']),
        'account_type'   =>sanitize_text_field($_POST['account_type']),
        'account_name'   =>sanitize_text_field($_POST['account_name']),
        'account_number' =>sanitize_text_field($_POST['account_number']),
        'account_details'=>sanitize_textarea_field($_POST['account_details']??''),
        'status'         =>'pending',
    ],['%s','%d','%s','%f','%s','%s','%s','%s','%s']);
    if($res) wp_send_json_success(['message'=>'Withdrawal request submitted! Admin will review and process it within 2-3 business days.']);
    else wp_send_json_error(['message'=>'Failed to submit withdrawal request. Please try again.']);
}

function bntm_ajax_kbf_extend_deadline() {
    check_ajax_referer('kbf_extend','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    $deadline=sanitize_text_field($_POST['deadline']);
    if(strtotime($deadline)<=time()) wp_send_json_error(['message'=>'Deadline must be a future date.']);
    $res=$wpdb->update($t,['deadline'=>$deadline],['id'=>$id,'business_id'=>$biz],['%s'],['%d','%d']);
    if($res!==false) wp_send_json_success(['message'=>'Deadline extended to '.date('M d, Y',strtotime($deadline)).'!']);
    else wp_send_json_error(['message'=>'Failed to extend deadline.']);
}

function bntm_ajax_kbf_toggle_auto_return() {
    check_ajax_referer('kbf_cancel_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();$val=intval($_POST['auto_return']);
    $fund=$wpdb->get_row($wpdb->prepare("SELECT id FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    $wpdb->update($t,['auto_return'=>$val],['id'=>$id],['%d'],['%d']);
    wp_send_json_success(['message'=>'Auto-return setting updated.']);
}

function bntm_ajax_kbf_save_organizer_profile() {
    check_ajax_referer('kbf_organizer_profile','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$pt=$wpdb->prefix.'kbf_organizer_profiles';$biz=get_current_user_id();
    $avatar='';
    if(!empty($_FILES['avatar']['name'])) {
        if(!function_exists('wp_handle_upload')) require_once(ABSPATH.'wp-admin/includes/file.php');
        $up=wp_handle_upload($_FILES['avatar'],['test_form'=>false]);
        if(isset($up['url'])) $avatar=$up['url'];
    }
    $socials=json_encode([
        'facebook'=>esc_url_raw($_POST['social_facebook']??''),
        'instagram'=>esc_url_raw($_POST['social_instagram']??''),
        'twitter'=>esc_url_raw($_POST['social_twitter']??''),
        'website'=>esc_url_raw($_POST['social_website']??''),
    ]);
    $data=[
        'bio'=>sanitize_textarea_field($_POST['bio']??''),
        'social_links'=>$socials,
        'payout_type'=>sanitize_text_field($_POST['payout_type']??''),
        'payout_name'=>sanitize_text_field($_POST['payout_name']??''),
        'payout_number'=>sanitize_text_field($_POST['payout_number']??''),
        'business_id'=>$biz
    ];
    if($avatar) $data['avatar_url']=$avatar;
    if(isset($_POST['phone'])) update_user_meta($biz,'kbf_phone',sanitize_text_field($_POST['phone']));
    if(isset($_POST['address'])) update_user_meta($biz,'kbf_address',sanitize_text_field($_POST['address']));
    $exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d",$biz));
    if($exists) {
        unset($data['business_id']);
        $formats = array_fill(0, count($data), '%s');
        $wpdb->update($pt, $data, ['business_id'=>$biz], $formats, ['%d']);
    } else {
        $insert_formats = array_fill(0, count($data), '%s');
        $wpdb->insert($pt, $data, $insert_formats);
    }
    if (function_exists('kbf_get_or_create_organizer_token')) {
        kbf_get_or_create_organizer_token($biz);
    }
    wp_send_json_success(['message'=>'Profile saved successfully!']);
}


function bntm_ajax_kbf_request_verification() {
    check_ajax_referer('kbf_verify_account','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    if(empty($_FILES['verify_id_front']['name']) || empty($_FILES['verify_id_back']['name'])) {
        wp_send_json_error(['message'=>'Please upload both front and back of your ID.']);
    }
    if(!function_exists('wp_handle_upload')) require_once(ABSPATH.'wp-admin/includes/file.php');
    $front = wp_handle_upload($_FILES['verify_id_front'], ['test_form'=>false]);
    if(isset($front['error'])) wp_send_json_error(['message'=>'Front ID upload failed: '.$front['error']]);
    $back = wp_handle_upload($_FILES['verify_id_back'], ['test_form'=>false]);
    if(isset($back['error'])) wp_send_json_error(['message'=>'Back ID upload failed: '.$back['error']]);

    global $wpdb; $pt = $wpdb->prefix.'kbf_organizer_profiles'; $biz = get_current_user_id();
    $data = [
        'verify_id_front'     => $front['url'],
        'verify_id_back'      => $back['url'],
        'verify_status'       => 'pending',
        'verify_submitted_at' => current_time('mysql'),
    ];
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d", $biz));
    if($exists) {
        $wpdb->update($pt, $data, ['business_id'=>$biz], ['%s','%s','%s','%s'], ['%d']);
    } else {
        $data['business_id'] = $biz;
        $wpdb->insert($pt, $data, ['%s','%s','%s','%s','%d']);
    }
    wp_send_json_success(['message'=>'Verification request submitted! Admin will review your ID.']);
}

// ============================================================
// AJAX HANDLERS -- SPONSOR / PUBLIC
// ============================================================

// ============================================================
// MAYA CHECKOUT INTEGRATION
// ============================================================

/**
 * Get Maya secret key from settings.
 * Sandbox key when demo_mode ON, live key when OFF.
 */

function bntm_ajax_kbf_sponsor_fund() {
    check_ajax_referer('kbf_sponsor','nonce');
    global $wpdb;$ft=$wpdb->prefix.'kbf_funds';$st=$wpdb->prefix.'kbf_sponsorships';
    $id=intval($_POST['fund_id']);$amount=floatval($_POST['amount']);
    if($amount<50) wp_send_json_error(['message'=>'Minimum sponsorship is ₱50.']);
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND status='active'",$id));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found or not accepting sponsorships.']);
    if ($fund->goal_amount > 0) {
        $remaining = max(0, floatval($fund->goal_amount) - floatval($fund->raised_amount));
        if ($remaining <= 0) {
            wp_send_json_error(['message'=>'This fund has already reached its goal.']);
        }
        if ($amount > $remaining) {
            wp_send_json_error(['message'=>'Maximum allowed sponsorship is ₱'.number_format($remaining,2).' for this fund.']);
        }
    }
    $anon=intval($_POST['is_anonymous']??0);
    $method=sanitize_text_field($_POST['payment_method']??'');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    if (empty($email) || empty($phone)) {
        wp_send_json_error(['message'=>'Email and phone are required to proceed.']);
    }

    $demo_mode = (bool)kbf_get_setting('kbf_demo_mode', true);

    // In live mode, sponsor_fund is handled by kbf_create_checkout.
    // This handler only runs in demo mode (auto-confirm, no gateway).
    if (!$demo_mode) {
        wp_send_json_error(['message' => 'Please use the payment checkout flow.']);
        return;
    }

    // â”€â”€ DEMO MODE: auto-confirm sponsorship â”€â”€
    $res=$wpdb->insert($st,[
        'rand_id'          =>bntm_rand_id(),
        'fund_id'          =>$id,
        'sponsor_name'     =>$anon?'Anonymous':sanitize_text_field($_POST['sponsor_name']??'Anonymous'),
        'is_anonymous'     =>$anon,
        'amount'           =>$amount,
        'email'            =>$email,
        'phone'            =>$phone,
        'payment_method'   =>$method,
        'payment_status'   =>'completed',
        'message'          =>sanitize_textarea_field($_POST['message']??''),
    ],['%s','%d','%s','%d','%f','%s','%s','%s','%s','%s']);
    if($res) {
        $new_id = $wpdb->insert_id;
        $wpdb->query($wpdb->prepare("UPDATE {$ft} SET raised_amount=raised_amount+%f WHERE id=%d",$amount,$id));
        // Auto-complete: mark fund complete if goal reached
        $updated_fund = $wpdb->get_row($wpdb->prepare("SELECT raised_amount,goal_amount FROM {$ft} WHERE id=%d",$id));
        $just_completed = false;
        if($updated_fund && $updated_fund->goal_amount > 0 && $updated_fund->raised_amount >= $updated_fund->goal_amount) {
            $wpdb->update($ft,['status'=>'completed','escrow_status'=>'released'],['id'=>$id],['%s','%s'],['%d']);
            $just_completed = true;
            do_action('kbf_fund_goal_reached', $id);
        }
        $pt=$wpdb->prefix.'kbf_organizer_profiles';
        $total=$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(s.amount),0) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$fund->business_id));
        $cnt=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed'",$fund->business_id));
        $wpdb->update($pt,['total_raised'=>$total,'total_sponsors'=>$cnt],['business_id'=>$fund->business_id],['%f','%d'],['%d']);
        $msg = $just_completed
            ? 'Sponsorship confirmed! ₱'.number_format($amount,2).' added. This fund has now reached its goal!'
            : 'Sponsorship confirmed! ₱'.number_format($amount,2).' has been added to this fund. Thank you for your support!';
        wp_send_json_success(['message'=>$msg,'fund_completed'=>$just_completed]);
    } else {
        wp_send_json_error(['message'=>'Sponsorship failed. Please try again.']);
    }
}

function bntm_ajax_kbf_report_fund() {
    check_ajax_referer('kbf_report','nonce');
    global $wpdb;$t=$wpdb->prefix.'kbf_reports';
    $id=intval($_POST['fund_id']);$reason=sanitize_text_field($_POST['reason']);$details=sanitize_textarea_field($_POST['details']);
    if(empty($reason)||empty($details)) wp_send_json_error(['message'=>'Please fill all required fields.']);
    $report_image = '';
    if (!empty($_FILES['report_image']['name'])) {
        $file = $_FILES['report_image'];
        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            wp_send_json_error(['message'=>'Please upload a JPG, PNG, or WebP image.']);
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload = wp_handle_upload($file, ['test_form'=>false]);
        if (!empty($upload['url'])) {
            $report_image = esc_url_raw($upload['url']);
        } else {
            wp_send_json_error(['message'=>'Failed to upload image.']);
        }
    }
    $res=$wpdb->insert($t,[
        'rand_id'=>bntm_rand_id(),
        'fund_id'=>$id,
        'reporter_id'=>get_current_user_id(),
        'reporter_email'=>sanitize_email($_POST['reporter_email']??''),
        'reason'=>$reason,
        'details'=>$details,
        'report_image'=>$report_image,
        'status'=>'open'
    ],['%s','%d','%d','%s','%s','%s','%s','%s']);
    if($res) wp_send_json_success(['message'=>'Report submitted. Our team will review it shortly.']);
    else wp_send_json_error(['message'=>'Failed to submit report.']);
}

function bntm_ajax_kbf_toggle_save_fund() {
    check_ajax_referer('kbf_save_fund','nonce');
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Please log in to save funds.']); }
    global $wpdb;
    $sf = $wpdb->prefix.'kbf_saved_funds';
    $ft = $wpdb->prefix.'kbf_funds';
      // Ensure saved table exists (for fresh installs) without rebuilding all tables
      $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $sf));
      if(!$table_exists && function_exists('bntm_kbf_ensure_saved_funds_table')) {
          bntm_kbf_ensure_saved_funds_table();
      }
    $fund_id = intval($_POST['fund_id'] ?? 0);
    $user_id = get_current_user_id();
    if(!$fund_id) wp_send_json_error(['message'=>'Invalid fund.']);
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$ft} WHERE id=%d", $fund_id));
    if(!$exists) wp_send_json_error(['message'=>'Fund not found.']);
    $saved_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$sf} WHERE user_id=%d AND fund_id=%d", $user_id, $fund_id));
    if($saved_id) {
        $wpdb->delete($sf, ['id'=>$saved_id], ['%d']);
        wp_send_json_success(['saved'=>false,'message'=>'Removed from saved.']);
    } else {
        $wpdb->insert($sf, ['user_id'=>$user_id,'fund_id'=>$fund_id], ['%d','%d']);
        wp_send_json_success(['saved'=>true,'message'=>'Saved!']);
    }
}

function bntm_ajax_kbf_submit_appeal() {
    check_ajax_referer('kbf_appeal','nonce');
    if (!is_user_logged_in()) wp_send_json_error(['message'=>'Please log in to submit an appeal.']);
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $at = $wpdb->prefix.'kbf_appeals';
    $fund_id = intval($_POST['fund_id'] ?? 0);
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    if (!$fund_id || !$message) wp_send_json_error(['message'=>'Please provide a valid appeal message.']);
    $fund = $wpdb->get_row($wpdb->prepare("SELECT id,business_id,status FROM {$ft} WHERE id=%d", $fund_id));
    if (!$fund || $fund->business_id != get_current_user_id()) wp_send_json_error(['message'=>'Unauthorized appeal request.']);
    if ($fund->status !== 'suspended') wp_send_json_error(['message'=>'Only suspended funds can be appealed.']);
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$at} WHERE fund_id=%d AND status='open'", $fund_id));
    if ($exists) wp_send_json_error(['message'=>'You already have an open appeal for this fund.']);
    $wpdb->insert($at, [
        'rand_id'     => bntm_rand_id(),
        'fund_id'     => $fund_id,
        'business_id' => get_current_user_id(),
        'message'     => $message,
        'status'      => 'open',
    ], ['%s','%d','%d','%s','%s']);
    wp_send_json_success(['message'=>'Appeal submitted. Our admin team will review it.']);
}

function bntm_ajax_kbf_get_fund_details() {
    check_ajax_referer('kbf_sponsor','nonce');
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);
    $f=$wpdb->get_row($wpdb->prepare("SELECT id,title,description,goal_amount,raised_amount,location,category FROM {$t} WHERE id=%d AND status='active'",$id));
    if($f) wp_send_json_success($f);
    else wp_send_json_error(['message'=>'Fund not found.']);
}

function bntm_ajax_kbf_get_organizer_profile() {
    // Public endpoint: no nonce required -- returns only public profile data
    global $wpdb;
    $biz=intval($_POST['business_id']??0);
    if(!$biz) wp_send_json_error(['message'=>'Not found.']);
    $pt=$wpdb->prefix.'kbf_organizer_profiles';
    $ft=$wpdb->prefix.'kbf_funds';
    $rt=$wpdb->prefix.'kbf_ratings';
    $st=$wpdb->prefix.'kbf_sponsorships';
    $profile=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE business_id=%d",$biz));
    $user=get_userdata($biz);
    if(!$user) wp_send_json_error(['message'=>'Organizer not found.']);
    $active_funds=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status='active'",$biz));
    $total_funds=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ft} WHERE business_id=%d AND status IN ('active','completed')",$biz));
    // All funds (active + completed) for fund history
    $funds=$wpdb->get_results($wpdb->prepare(
        "SELECT id,title,goal_amount,raised_amount,status,category,deadline,created_at FROM {$ft} WHERE business_id=%d AND status IN ('active','completed') ORDER BY created_at DESC LIMIT 10",
        $biz
    ));
    $fund_data=array_map(function($f) use($wpdb,$st){
        $sponsor_count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} WHERE fund_id=%d AND payment_status='completed'",$f->id));
        $pct=$f->goal_amount>0?min(100,round(($f->raised_amount/$f->goal_amount)*100)):0;
        return [
            'id'           =>$f->id,
            'title'        =>$f->title,
            'raised'       =>number_format($f->raised_amount,0),
            'goal'         =>number_format($f->goal_amount,0),
            'pct'          =>$pct,
            'status'       =>$f->status,
            'category'     =>$f->category,
            'sponsor_count'=>$sponsor_count,
        ];
    }, $funds);
    // Recent reviews
    $reviews=$wpdb->get_results($wpdb->prepare(
        "SELECT r.*,f.title as fund_title FROM {$rt} r LEFT JOIN {$ft} f ON r.fund_id=f.id WHERE r.organizer_id=%d ORDER BY r.created_at DESC LIMIT 5",
        $biz
    ));
    $review_data=array_map(fn($r)=>[
        'rating'    =>$r->rating,
        'review'    =>$r->review,
        'email'     =>substr($r->sponsor_email,0,3).'***'.strstr($r->sponsor_email,'@'),
        'fund_title'=>$r->fund_title,
        'date'      =>date('M d, Y',strtotime($r->created_at)),
    ],$reviews);
    wp_send_json_success([
        'display_name'  =>$user->display_name,
        'avatar_url'    =>$profile?$profile->avatar_url:'',
        'bio'           =>$profile?$profile->bio:'',
        'is_verified'   =>$profile?(bool)$profile->is_verified:false,
        'total_raised'  =>$profile?$profile->total_raised:0,
        'total_sponsors'=>$profile?$profile->total_sponsors:0,
        'rating'        =>$profile?number_format($profile->rating,1):'0.0',
        'rating_count'  =>$profile?$profile->rating_count:0,
        'active_funds'  =>$active_funds,
        'total_funds'   =>$total_funds,
        'funds'         =>$fund_data,
        'reviews'       =>$review_data,
    ]);
}

function bntm_ajax_kbf_submit_rating() {
    check_ajax_referer('kbf_rating','nonce');
    global $wpdb;$rt=$wpdb->prefix.'kbf_ratings';$pt=$wpdb->prefix.'kbf_organizer_profiles';
    $org_id=intval($_POST['organizer_id']);$rating=min(5,max(1,intval($_POST['rating'])));
    $email=sanitize_email($_POST['sponsor_email']??'');
    if(empty($email)) wp_send_json_error(['message'=>'Email required to submit a score.']);
    // Prevent duplicate rating per email per organizer
    $exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$rt} WHERE organizer_id=%d AND sponsor_email=%s",$org_id,$email));
    if($exists) wp_send_json_error(['message'=>'You have already submitted a score for this organizer.']);
    $wpdb->insert($rt,['rand_id'=>bntm_rand_id(),'organizer_id'=>$org_id,'sponsor_email'=>$email,'rating'=>$rating,'review'=>sanitize_textarea_field($_POST['review']??''),'fund_id'=>intval($_POST['fund_id']??0)],['%s','%d','%s','%d','%s','%d']);
    // Recalculate average
    $avg=$wpdb->get_var($wpdb->prepare("SELECT AVG(rating) FROM {$rt} WHERE organizer_id=%d",$org_id));
    $cnt=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE organizer_id=%d",$org_id));
    $wpdb->update($pt,['rating'=>round($avg,2),'rating_count'=>(int)$cnt],['business_id'=>$org_id],['%f','%d'],['%d']);
    wp_send_json_success(['message'=>'Thank you for your score!']);
}

// ============================================================
// ADMIN TAB: Settings
// ============================================================


