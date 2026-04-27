<?php
/*
 * KBF user-facing AJAX handlers (funds, sponsor actions, lookups).
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('kbf_get_client_ip')) {
    function kbf_get_client_ip() {
        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        
        // Cloudflare always sends the real visitor IP in this header.
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $cf_ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cf_ip, FILTER_VALIDATE_IP)) {
                return $cf_ip;
            }
        }

        // Fallback: trust X-Forwarded-For only if behind a known local proxy.
        $trusted_proxies = ['127.0.0.1', '::1'];
        if (!in_array($remote_addr, $trusted_proxies, true)) {
            return $remote_addr !== '' ? $remote_addr : '';
        }

        $keys = ['HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP'];
        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $value = sanitize_text_field($_SERVER[$key]);
            $ip = trim(explode(',', $value)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return $remote_addr !== '' ? $remote_addr : '';
    }
}

if (!function_exists('kbf_get_user_notifications')) {
    function kbf_get_user_notifications($user_id) {
        $items = get_user_meta((int)$user_id, 'kbf_notifications', true);
        return is_array($items) ? $items : [];
    }
}

if (!function_exists('kbf_notify_admin_users')) {
    function kbf_notify_admin_users($payload = []) {
        if (!function_exists('kbf_push_user_notification')) {
            return;
        }
        $admin_ids = get_users([
            'role' => 'administrator',
            'fields' => 'ID',
        ]);
        if (empty($admin_ids) || !is_array($admin_ids)) {
            return;
        }
        foreach ($admin_ids as $admin_id) {
            kbf_push_user_notification((int)$admin_id, $payload);
        }
    }
}

if (!function_exists('kbf_get_user_notifications_latest')) {
    function kbf_get_user_notifications_latest($user_id, $limit = 10) {
        $items = kbf_get_user_notifications($user_id);
        $items = array_reverse($items);
        if ($limit > 0) {
            $items = array_slice($items, 0, (int)$limit);
        }
        return $items;
    }
}

if (!function_exists('kbf_get_user_unread_notification_count')) {
    function kbf_get_user_unread_notification_count($user_id) {
        $items = kbf_get_user_notifications($user_id);
        $count = 0;
        foreach ($items as $item) {
            if (empty($item['read'])) {
                $count++;
            }
        }
        return (int)$count;
    }
}

if (!function_exists('kbf_push_user_notification')) {
    function kbf_push_user_notification($user_id, $payload = []) {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return 0;
        }
        $items = kbf_get_user_notifications($user_id);
        $type = sanitize_key($payload['type'] ?? 'general');
        $target_id = isset($payload['target_id']) ? (string)$payload['target_id'] : '';
        $dedupe_window = isset($payload['dedupe_window']) ? max(0, (int)$payload['dedupe_window']) : 90;
        if ($dedupe_window > 0 && !empty($items)) {
            $now_ts = current_time('timestamp');
            for ($i = count($items) - 1; $i >= 0; $i--) {
                $existing = $items[$i];
                if (($existing['type'] ?? '') !== $type) {
                    continue;
                }
                if ($target_id !== '' && (string)($existing['target_id'] ?? '') !== $target_id) {
                    continue;
                }
                $created_at = !empty($existing['created_at']) ? strtotime((string)$existing['created_at']) : 0;
                if ($created_at && ($now_ts - $created_at) <= $dedupe_window) {
                    return kbf_get_user_unread_notification_count($user_id);
                }
                break;
            }
        }
        $items[] = [
            'id' => 'kbfn_' . wp_generate_uuid4(),
            'type' => $type,
            'title' => sanitize_text_field($payload['title'] ?? 'Notification'),
            'message' => sanitize_text_field($payload['message'] ?? ''),
            'url' => esc_url_raw($payload['url'] ?? ''),
            'target_id' => $target_id,
            'meta' => !empty($payload['meta']) && is_array($payload['meta']) ? array_map('sanitize_text_field', $payload['meta']) : [],
            'created_at' => current_time('mysql'),
            'read' => 0,
        ];
        if (count($items) > 40) {
            $items = array_slice($items, -40);
        }
        update_user_meta($user_id, 'kbf_notifications', $items);
        return kbf_get_user_unread_notification_count($user_id);
    }
}

if (!function_exists('kbf_mark_user_notifications_read')) {
    function kbf_mark_user_notifications_read($user_id) {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return 0;
        }
        $items = kbf_get_user_notifications($user_id);
        if (empty($items)) {
            return 0;
        }
        $changed = false;
        foreach ($items as $idx => $item) {
            if (empty($item['read'])) {
                $items[$idx]['read'] = 1;
                $items[$idx]['read_at'] = current_time('mysql');
                $changed = true;
            }
        }
        if ($changed) {
            update_user_meta($user_id, 'kbf_notifications', $items);
        }
        return 0;
    }
}

if (!function_exists('kbf_mark_single_notification_read')) {
    function kbf_mark_single_notification_read($user_id, $notification_id) {
        $user_id = (int)$user_id;
        $notification_id = sanitize_text_field((string)$notification_id);
        if ($user_id <= 0 || $notification_id === '') {
            return kbf_get_user_unread_notification_count($user_id);
        }
        $items = kbf_get_user_notifications($user_id);
        if (empty($items)) {
            return 0;
        }
        $changed = false;
        foreach ($items as $idx => $item) {
            if (($item['id'] ?? '') !== $notification_id) {
                continue;
            }
            if (empty($item['read'])) {
                $items[$idx]['read'] = 1;
                $items[$idx]['read_at'] = current_time('mysql');
                $changed = true;
            }
            break;
        }
        if ($changed) {
            update_user_meta($user_id, 'kbf_notifications', $items);
        }
        return kbf_get_user_unread_notification_count($user_id);
    }
}

if (!function_exists('kbf_rate_limit_ok')) {
    function kbf_rate_limit_ok($bucket, $limit = 60, $window = 60) {
        $ip = kbf_get_client_ip();
        if (!$ip) return true;
        $key = 'kbf_rl_' . $bucket . '_' . md5($ip);
        $count = (int) get_transient($key);
        if ($count >= $limit) {
            if (function_exists('kbf_log_security_event')) {
                kbf_log_security_event('rate_limit_block', [
                    'bucket' => $bucket,
                    'limit'  => $limit,
                    'window' => $window
                ]);
            } else {
                error_log('[KBF][RateLimit] Blocked bucket=' . $bucket . ' ip=' . $ip);
            }
            return false;
        }
        set_transient($key, $count + 1, $window);
        return true;
    }
}

if (!function_exists('kbf_is_onboarding_complete')) {
    function kbf_is_onboarding_complete($user_id) {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return false;
        }
        global $wpdb;
        $pt = $wpdb->prefix.'kbf_organizer_profiles';
        $profile = $wpdb->get_row($wpdb->prepare("SELECT bio,payout_type,payout_name,payout_number FROM {$pt} WHERE business_id=%d", $user_id));
        $user = get_userdata($user_id);
        $social_name = (string)get_user_meta($user_id, 'kbf_social_name', true);
        $address = (string)get_user_meta($user_id, 'kbf_address', true);

        $has_display_name = $user && !empty(trim((string)$user->display_name));
        $has_social_name = !empty(trim($social_name));
        $has_bio = $profile && !empty(trim((string)$profile->bio));
        $has_profile_type = $profile && !empty(trim((string)$profile->profile_type));
        $has_payout = $profile && !empty($profile->payout_type) && !empty($profile->payout_name) && !empty($profile->payout_number);
        $has_address = !empty(trim((string)$address));

        return ($has_display_name && $has_social_name && $has_bio && $has_profile_type && $has_payout && $has_address);
    }
}

if (!function_exists('kbf_require_onboarding_complete')) {
    function kbf_require_onboarding_complete($user_id) {
        if (kbf_is_onboarding_complete($user_id)) {
            return true;
        }
        wp_send_json_error([
            'message' => 'Please complete onboarding in your Profile before using this action.',
            'code' => 'onboarding_required'
        ]);
        return false;
    }
}

if (!function_exists('kbf_handle_image_upload')) {
    function kbf_handle_image_upload($file, $args = []) {
        $defaults = [
            'max_bytes'    => 8 * 1024 * 1024,
            'max_dim'      => 2500,
            'quality'      => 78,
            'allowed_exts' => ['jpg','jpeg','png','webp'],
            'convert_webp' => true,
        ];
        $cfg = array_merge($defaults, is_array($args) ? $args : []);

        if (empty($file) || empty($file['tmp_name'])) {
            return ['error' => 'No image uploaded.'];
        }
        if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Upload failed. Please try again.'];
        }
        if (!empty($file['size']) && $file['size'] > $cfg['max_bytes']) {
            return ['error' => 'Image too large. Max size is 8MB.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $cfg['allowed_exts'], true)) {
            return ['error' => 'Please upload a JPG, PNG, or WebP image.'];
        }

        $allowed_mimes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
        ];
        $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);
        if (empty($check['ext']) || empty($check['type'])) {
            return ['error' => 'Invalid image file.'];
        }

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH.'wp-admin/includes/file.php');
        }
        $upload = wp_handle_upload($file, ['test_form' => false, 'mimes' => $allowed_mimes]);
        if (!empty($upload['error'])) {
            return ['error' => $upload['error']];
        }

        $path = $upload['file'];
        $url  = $upload['url'];
        $type = $upload['type'];
        $original_path = $path; // Keep reference to delete later if replaced

        $editor = wp_get_image_editor($path);
        if (!is_wp_error($editor)) {
            $size = $editor->get_size();
            if (!empty($size['width']) && !empty($size['height'])) {
                $max_dim = (int) $cfg['max_dim'];
                $largest = max($size['width'], $size['height']);
                if ($largest > $max_dim) {
                    $editor->resize($max_dim, $max_dim, false);
                }
            }
            $editor->set_quality((int) $cfg['quality']);
            $saved = $editor->save();
            if (!is_wp_error($saved) && !empty($saved['path']) && !empty($saved['url'])) {
                if ($saved['path'] !== $original_path && file_exists($original_path)) {
                    @unlink($original_path);
                }
                $path = $saved['path'];
                $url  = $saved['url'];
                $type = $saved['mime-type'];
                $original_path = $path; // Update reference for next step
            }

            if (!empty($cfg['convert_webp']) && method_exists($editor, 'supports_mime_type') && $editor->supports_mime_type('image/webp')) {
                $editor->set_quality((int) $cfg['quality']);
                $webp = $editor->save(null, 'image/webp');
                if (!is_wp_error($webp) && !empty($webp['path']) && !empty($webp['url'])) {
                    if ($webp['path'] !== $original_path && file_exists($original_path)) {
                        @unlink($original_path);
                    }
                    $path = $webp['path'];
                    $url  = $webp['url'];
                    $type = $webp['mime-type'];
                }
            }
        }

        return [
            'file' => $path,
            'url'  => $url,
            'type' => $type,
        ];
    }
}

function bntm_ajax_kbf_create_fund() {
    check_ajax_referer('kbf_create_fund','nonce');
    if (!kbf_rate_limit_ok('create_fund', 8, 60)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$table=$wpdb->prefix.'kbf_funds';
    $biz=get_current_user_id();
    if (!kbf_require_onboarding_complete($biz)) { return; }
    $location_full = isset($_POST['location_full']) && $_POST['location_full'] !== '' ? wp_unslash($_POST['location_full']) : (isset($_POST['location']) ? wp_unslash($_POST['location']) : '');
    if (empty($location_full)) {
        $parts = [];
        if (!empty($_POST['barangay'])) $parts[] = sanitize_text_field(wp_unslash($_POST['barangay']));
        if (!empty($_POST['municipality'])) $parts[] = sanitize_text_field(wp_unslash($_POST['municipality']));
        if (!empty($_POST['province'])) $parts[] = sanitize_text_field(wp_unslash($_POST['province']));
        if (!empty($parts)) $location_full = implode(', ', $parts);
    }
    foreach(['title','description','goal_amount','email','phone','category','funder_type','deadline'] as $f) {
        if(empty($_POST[$f])) wp_send_json_error(['message'=>'Please fill all required fields.']);
    }
    if (empty($location_full)) wp_send_json_error(['message'=>'Please fill all required fields.']);
    $goal=floatval($_POST['goal_amount']);
    $deadline = sanitize_text_field(wp_unslash($_POST['deadline'] ?? ''));
    if(!$deadline) wp_send_json_error(['message'=>'Please fill all required fields.']);
    $min_deadline = strtotime('+7 days', current_time('timestamp'));
    if(strtotime($deadline) < $min_deadline) {
        wp_send_json_error(['message'=>'Deadline must be at least 7 days from today.']);
    }
    if($goal<100) wp_send_json_error(['message'=>'Minimum goal is ?100.']);
    $benefits_raw = isset($_POST['benefits']) ? wp_unslash($_POST['benefits']) : '';
    $benefits_clean = [];
    if (!empty($benefits_raw)) {
        $benefits = json_decode($benefits_raw, true);
        if (is_array($benefits)) {
            foreach ($benefits as $b) {
                if (!is_array($b)) continue;
                $title = sanitize_text_field($b['title'] ?? '');
                $desc  = sanitize_textarea_field($b['description'] ?? '');
                $amount_raw = isset($b['amount']) ? preg_replace('/[^\d.]/', '', (string)$b['amount']) : '';
                $amount = $amount_raw !== '' ? (float)$amount_raw : 0;
                if ($title === '' && $desc === '' && $amount <= 0) continue;
                $benefits_clean[] = [
                    'title'       => $title,
                    'description' => $desc,
                    'amount'      => $amount,
                ];
            }
        }
    }
    $benefits_json = !empty($benefits_clean) ? wp_json_encode($benefits_clean) : null;
    // Handle photos
    $photo_urls=[];
    if(!empty($_FILES['photos']['name'][0])) {
        $count=min(5,count($_FILES['photos']['name']));
        for($i=0;$i<$count;$i++) {
            $file=['name'=>$_FILES['photos']['name'][$i],'type'=>$_FILES['photos']['type'][$i],'tmp_name'=>$_FILES['photos']['tmp_name'][$i],'error'=>$_FILES['photos']['error'][$i],'size'=>$_FILES['photos']['size'][$i]];
            $up = kbf_handle_image_upload($file);
            if(isset($up['error'])) wp_send_json_error(['message'=>$up['error']]);
            if(isset($up['url'])) $photo_urls[]=$up['url'];
        }
    }
    $res=$wpdb->insert($table,[
        'rand_id'       =>bntm_rand_id(),
        'business_id'   =>$biz,
        'funder_type'   =>sanitize_text_field(wp_unslash($_POST['funder_type'])),
        'title'         =>sanitize_text_field(wp_unslash($_POST['title'])),
        'description'   =>sanitize_textarea_field(wp_unslash($_POST['description'])),
        'photos'        =>!empty($photo_urls)?json_encode($photo_urls):null,
        'benefits'      =>$benefits_json,
        'goal_amount'   =>$goal,
        'category'      =>sanitize_text_field(wp_unslash($_POST['category'])),
        'email'         =>sanitize_email(wp_unslash($_POST['email'])),
        'phone'         =>sanitize_text_field(wp_unslash($_POST['phone'])),
        'location'      =>sanitize_text_field($location_full),
        'auto_return'   =>isset($_POST['auto_return'])?1:0,
        'deadline'      =>$deadline,
        'status'        =>'pending',
        'share_token'   =>wp_generate_password(32,false),
    ],['%s','%d','%s','%s','%s','%s','%s','%f','%s','%s','%s','%s','%d','%s','%s','%s']);
    // Ensure organizer profile exists
    $pt=$wpdb->prefix.'kbf_organizer_profiles';
    if(!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d",$biz))) {
        $wpdb->insert($pt,['business_id'=>$biz],['%d']);
    }
    if (function_exists('kbf_get_or_create_organizer_token')) {
        kbf_get_or_create_organizer_token($biz);
    }
    if($res) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        $fund_url = add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$wpdb->insert_id], $dashboard_url);
        kbf_push_user_notification($biz, [
            'type' => 'fund_submitted_for_review',
            'title' => 'Fund submitted for review',
            'message' => 'Your campaign was submitted and is pending admin approval.',
            'url' => $fund_url,
            'target_id' => (string)((int)$wpdb->insert_id),
        ]);
        kbf_notify_admin_users([
            'type' => 'admin_new_pending_fund',
            'title' => 'New fund pending review',
            'message' => sanitize_text_field(wp_unslash($_POST['title'])),
            'url' => add_query_arg(['kbf_tab' => 'pending'], $dashboard_url),
            'target_id' => (string)((int)$wpdb->insert_id),
        ]);
        wp_send_json_success(['message'=>'Fund submitted for review! We will notify you once approved.']);
    }
    else wp_send_json_error(['message'=>'Failed to create fund. Please try again.']);
}


function bntm_ajax_kbf_update_fund() {
    check_ajax_referer('kbf_update_fund','nonce');
    if (!kbf_rate_limit_ok('update_fund', 20, 60)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    if (!kbf_require_onboarding_complete($biz)) { return; }
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    if(!in_array($fund->status, ['pending', 'active'])) wp_send_json_error(['message'=>'Only pending or active funds can be updated.']);
    $location_full = isset($_POST['location_full']) && $_POST['location_full'] !== '' ? wp_unslash($_POST['location_full']) : (isset($_POST['location']) ? wp_unslash($_POST['location']) : '');
    $deadline = !empty($_POST['deadline']) ? sanitize_text_field(wp_unslash($_POST['deadline'])) : null;
    if ($deadline) {
        $min_deadline = strtotime('+7 days', current_time('timestamp'));
        if(strtotime($deadline) < $min_deadline) {
            wp_send_json_error(['message'=>'Deadline must be at least 7 days from today.']);
        }
    }
    $data=[
        'title'=>sanitize_text_field(wp_unslash($_POST['title'])),
        'description'=>sanitize_textarea_field(wp_unslash($_POST['description'])),
        'location'=>sanitize_text_field($location_full),
        'deadline'=>$deadline,
        'auto_return'=>isset($_POST['auto_return']) ? 1 : 0
    ];
    // Allow goal_amount edits, but reset to 'pending' if changed to force admin review
    if (isset($_POST['goal_amount']) && is_numeric($_POST['goal_amount'])) {
        $new_goal = floatval($_POST['goal_amount']);
        if ($new_goal >= 100 && $new_goal !== floatval($fund->goal_amount)) {
            $data['goal_amount'] = $new_goal;
            $data['status'] = 'pending';
        }
    }
    if (isset($_POST['benefits'])) {
        $benefits_raw = wp_unslash($_POST['benefits']);
        $benefits_clean = [];
        if (!empty($benefits_raw)) {
            $benefits = json_decode($benefits_raw, true);
            if (is_array($benefits)) {
                foreach ($benefits as $b) {
                    if (!is_array($b)) continue;
                    $title = sanitize_text_field($b['title'] ?? '');
                    $desc  = sanitize_textarea_field($b['description'] ?? '');
                    $amount_raw = isset($b['amount']) ? preg_replace('/[^\d.]/', '', (string)$b['amount']) : '';
                    $amount = $amount_raw !== '' ? (float)$amount_raw : 0;
                    if ($title === '' && $desc === '' && $amount <= 0) continue;
                    $benefits_clean[] = [
                        'title'       => $title,
                        'description' => $desc,
                        'amount'      => $amount,
                    ];
                }
            }
        }
        $data['benefits'] = !empty($benefits_clean) ? wp_json_encode($benefits_clean) : null;
    }
    $existing=$fund->photos?json_decode($fund->photos,true):[];
    if (!is_array($existing)) $existing = [];
    // Remove photos
    if (!empty($_POST['remove_photos'])) {
        $remove_list = json_decode(stripslashes((string)$_POST['remove_photos']), true);
        if (is_array($remove_list) && !empty($remove_list)) {
            $existing = array_values(array_filter($existing, function($u) use ($remove_list){
                return !in_array($u, $remove_list, true);
            }));
        }
    }
    // New photos
    if(!empty($_FILES['photos']['name'][0])) {
        $count=min(5,count($_FILES['photos']['name']));
        for($i=0;$i<$count;$i++) {
            if(count($existing)>=5) break;
            $file=['name'=>$_FILES['photos']['name'][$i],'type'=>$_FILES['photos']['type'][$i],'tmp_name'=>$_FILES['photos']['tmp_name'][$i],'error'=>$_FILES['photos']['error'][$i],'size'=>$_FILES['photos']['size'][$i]];
            $up = kbf_handle_image_upload($file);
            if(isset($up['error'])) wp_send_json_error(['message'=>$up['error']]);
            if(isset($up['url'])) $existing[]=$up['url'];
        }
    }
    $unique_photos = array_values(array_unique($existing));
    $should_update_photos = !empty($_POST['remove_photos'])
        || !empty($_FILES['photos']['name'][0])
        || count($unique_photos) !== count($existing);
    if ($should_update_photos) {
        $data['photos'] = !empty($unique_photos) ? json_encode($unique_photos) : null;
    }
    $res=$wpdb->update($t,$data,['id'=>$id],array_fill(0,count($data),'%s'),['%d']);
    if($res!==false){
        if (function_exists('kbf_push_user_notification')) {
            $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
            $fund_url = add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$id], $dashboard_url);
            $is_pending_review = isset($data['status']) && $data['status'] === 'pending';
            kbf_push_user_notification($biz, [
                'type' => $is_pending_review ? 'fund_update_pending_review' : 'fund_updated',
                'title' => $is_pending_review ? 'Fund update submitted for review' : 'Fund updated',
                'message' => $is_pending_review
                    ? 'Your changes were saved and are now pending admin approval.'
                    : 'Your campaign changes were saved successfully.',
                'url' => $fund_url,
                'target_id' => (string)((int)$id),
            ]);
        }
        wp_send_json_success(['message'=>'Fund updated successfully!']);
    }
    else wp_send_json_error(['message'=>'Failed to update fund.']);
}

function bntm_ajax_kbf_cancel_fund() {
    check_ajax_referer('kbf_cancel_fund','nonce');
    if (!kbf_rate_limit_ok('cancel_fund', 10, 300)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    if (!kbf_require_onboarding_complete($biz)) { return; }
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    if(in_array($fund->status,['cancelled','completed'])) wp_send_json_error(['message'=>'This fund cannot be cancelled.']);
    if($fund->raised_amount > 0) wp_send_json_error(['message'=>'Cannot cancel fund with existing sponsorships. Contact support.']);
    // Auto-refund disabled.
    $wpdb->update($t,['status'=>'cancelled'],['id'=>$id],['%s'],['%d']);
    wp_send_json_success(['message'=>'Fund cancelled.']);
}

function bntm_ajax_kbf_trash_fund() {
    check_ajax_referer('kbf_trash_fund','nonce');
    if (!kbf_rate_limit_ok('trash_fund', 6, 300)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $t = $wpdb->prefix.'kbf_funds';
    $id = intval($_POST['fund_id']);
    $biz = get_current_user_id();
    if (!kbf_require_onboarding_complete($biz)) { return; }
    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    if(!in_array($fund->status, ['cancelled', 'suspended'])) wp_send_json_error(['message'=>'Only cancelled or suspended funds can be trashed.']);

    // === STORAGE CLEANUP: Collect all associated image URLs ===
    $files_to_delete = [];

    // 1. Main fund photos
    if (!empty($fund->photos)) {
        $decoded = json_decode($fund->photos, true);
        if (is_array($decoded)) $files_to_delete = array_merge($files_to_delete, $decoded);
    }

    // 2. Milestone photos
    if (!empty($fund->milestones)) {
        $decoded_ms = json_decode($fund->milestones, true);
        if (is_array($decoded_ms)) {
            foreach ($decoded_ms as $ms) {
                if (!empty($ms['photos']) && is_array($ms['photos'])) {
                    $files_to_delete = array_merge($files_to_delete, $ms['photos']);
                }
            }
        }
    }

    // Clean related database records to avoid orphans.
    $wpdb->delete($wpdb->prefix.'kbf_sponsorships', ['fund_id'=>$id], ['%d']);
    $wpdb->delete($wpdb->prefix.'kbf_withdrawals', ['fund_id'=>$id], ['%d']);
    $wpdb->delete($wpdb->prefix.'kbf_reports', ['fund_id'=>$id], ['%d']);
    $wpdb->delete($wpdb->prefix.'kbf_appeals', ['fund_id'=>$id], ['%d']);
    $wpdb->delete($wpdb->prefix.'kbf_saved_funds', ['fund_id'=>$id], ['%d']);

    // === DELETE FILES FROM DISK ===
    if (!empty($files_to_delete)) {
        $upload_dir = wp_upload_dir();
        $base_url   = trailingslashit($upload_dir['baseurl']);
        $base_dir   = trailingslashit($upload_dir['basedir']);

        foreach (array_unique($files_to_delete) as $file_url) {
            if (!is_string($file_url) || empty($file_url)) continue;
            if (strpos($file_url, $base_url) === 0) {
                $relative  = str_replace($base_url, '', $file_url);
                $file_path = $base_dir . ltrim($relative, '/');
                // Security check: ensure path resolves safely within upload dir
                if (file_exists($file_path) && strpos(wp_normalize_path($file_path), wp_normalize_path($base_dir)) === 0) {
                    @unlink($file_path);
                }
            }
        }
    }

    $res = $wpdb->delete($t, ['id'=>$id, 'business_id'=>$biz], ['%d','%d']);
    if($res) wp_send_json_success(['message'=>'Fund trashed.']);
    wp_send_json_error(['message'=>'Unable to trash fund.']);
}

function bntm_ajax_kbf_request_escrow() {
    check_ajax_referer('kbf_request_escrow','nonce');
    if (!kbf_rate_limit_ok('request_escrow', 6, 300)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $et = $wpdb->prefix.'kbf_escrow_requests';
    $id = intval($_POST['fund_id']);
    $biz = get_current_user_id();
    if (!kbf_require_onboarding_complete($biz)) { return; }
    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    if($fund->status !== 'active') wp_send_json_error(['message'=>'Only active funds can request escrow.']);
    if(!$fund->deadline || strtotime($fund->deadline) > time()) wp_send_json_error(['message'=>'Escrow requests are allowed only after the deadline.']);
    if($fund->raised_amount >= $fund->goal_amount) wp_send_json_error(['message'=>'Goal already met. Escrow release is handled normally.']);
    if($fund->escrow_status !== 'holding') wp_send_json_error(['message'=>'Escrow is already released or refunded.']);

    $pending = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$et} WHERE fund_id=%d AND status='pending'",$id));
    if($pending > 0) wp_send_json_error(['message'=>'An escrow request is already pending review.']);

    $res = $wpdb->insert($et, [
        'fund_id' => $id,
        'business_id' => $biz,
        'status' => 'pending'
    ], ['%d','%d','%s']);
    if($res) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        $fund_url = add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => $id], $dashboard_url);
        kbf_push_user_notification($biz, [
            'type' => 'escrow_request_submitted',
            'title' => 'Escrow request submitted',
            'message' => 'Your escrow release request is now pending admin review.',
            'url' => $fund_url,
            'target_id' => (string)$id,
        ]);
        kbf_notify_admin_users([
            'type' => 'admin_escrow_request_submitted',
            'title' => 'New escrow request',
            'message' => 'A campaign owner submitted an escrow release request.',
            'url' => add_query_arg(['kbf_tab' => 'withdrawals'], $dashboard_url),
            'target_id' => (string)$id,
        ]);
        wp_send_json_success(['message'=>'Escrow request submitted for review.']);
    }
    wp_send_json_error(['message'=>'Unable to submit request.']);
}

function bntm_ajax_kbf_mark_fund_complete() {
    check_ajax_referer('kbf_mark_fund_complete','nonce');
    if (!kbf_rate_limit_ok('mark_complete', 6, 300)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    if (!kbf_require_onboarding_complete($biz)) { return; }
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund||$fund->status!=='active') wp_send_json_error(['message'=>'Fund not found or not active.']);
    if($fund->raised_amount < $fund->goal_amount) wp_send_json_error(['message'=>'Cannot complete fund until goal amount is reached.']);
    $wpdb->update($t,['status'=>'completed','escrow_status'=>'released'],['id'=>$id],['%s','%s'],['%d']);
    wp_send_json_success(['message'=>'Fund marked as complete!']);
}

function bntm_ajax_kbf_request_withdrawal() {
    check_ajax_referer('kbf_withdrawal','nonce');
    if (!kbf_rate_limit_ok('withdrawal', 6, 300)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Please log in to request a withdrawal.']); }
    global $wpdb;$ft=$wpdb->prefix.'kbf_funds';$wt=$wpdb->prefix.'kbf_withdrawals';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();$amount=floatval($_POST['amount']);
    if (!kbf_require_onboarding_complete($biz)) { return; }
    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    if(!in_array($fund->status, ['active', 'completed'])) wp_send_json_error(['message'=>'Withdrawals are only available for active or completed fundraisers.']);
    if($fund->escrow_status === 'refunded') wp_send_json_error(['message'=>'Funds have been refunded and are no longer available for withdrawal.']);
    if($amount<=0) wp_send_json_error(['message'=>'Please enter a valid amount.']);
    if($amount>$fund->raised_amount) wp_send_json_error(['message'=>'Amount exceeds total raised funds (PHP '.number_format($fund->raised_amount,2).' ).']);
    if(empty($_POST['method'])||empty($_POST['account_type'])||empty($_POST['account_name'])||empty($_POST['account_number'])) wp_send_json_error(['message'=>'Please fill all required fields.']);
    // Prevent duplicate pending request for same fund
    $pending=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wt} WHERE fund_id=%d AND status='pending'",$id));
    if($pending>0) wp_send_json_error(['message'=>'You already have a pending withdrawal request for this fund. Please wait for admin to process it first.']);
    $funder_name = '';
    $user = get_userdata($biz);
    $funder_name = $user ? $user->display_name : '';
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
    if($res) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        $fund_url = add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => $id], $dashboard_url);
        kbf_push_user_notification($biz, [
            'type' => 'withdrawal_submitted',
            'title' => 'Withdrawal request submitted',
            'message' => 'Your withdrawal request is pending admin review.',
            'url' => $fund_url,
            'target_id' => (string)$id,
        ]);
        kbf_notify_admin_users([
            'type' => 'admin_withdrawal_submitted',
            'title' => 'New withdrawal request',
            'message' => 'A campaign owner submitted a withdrawal request.',
            'url' => add_query_arg(['kbf_tab' => 'withdrawals'], $dashboard_url),
            'target_id' => (string)$id,
        ]);
        wp_send_json_success(['message'=>'Withdrawal request submitted! Admin will review and process it within 2-3 business days.']);
    }
    else wp_send_json_error(['message'=>'Failed to submit withdrawal request. Please try again.']);
}

function bntm_ajax_kbf_add_milestone() {
    check_ajax_referer('kbf_add_milestone','nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    if (!kbf_rate_limit_ok('add_milestone', 10, 300)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if (function_exists('bntm_kbf_ensure_fund_columns')) {
        bntm_kbf_ensure_fund_columns();
    }
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $cols = $wpdb->get_col("SHOW COLUMNS FROM {$ft}");
    if (!in_array('milestones', $cols, true)) {
        wp_send_json_error(['message'=>'Milestones column missing. Please refresh and try again.']);
    }
    $biz = get_current_user_id();
    if (!kbf_require_onboarding_complete($biz)) { return; }
    $fund_id = isset($_POST['fund_id']) ? intval($_POST['fund_id']) : 0;
    if (!$fund_id) wp_send_json_error(['message'=>'Invalid fund.']);
    $fund = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND business_id=%d", $fund_id, $biz));
    if (!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    if (!in_array($fund->status, ['active','completed'], true)) {
        wp_send_json_error(['message'=>'Milestones can only be added for active or completed fundraisers.']);
    }
    // Sanitize and enforce max lengths (matches frontend maxlength)
    $title = mb_substr(sanitize_text_field($_POST['milestone_title'] ?? ''), 0, 150);
    $body  = mb_substr(sanitize_textarea_field($_POST['milestone_body'] ?? ''), 0, 300);
    if ($title === '' && $body === '') {
        wp_send_json_error(['message'=>'Please add a title or update details.']);
    }
    $photo_urls = [];
    if (!empty($_FILES['milestone_photos']['name'][0])) {
        $count = min(5, count($_FILES['milestone_photos']['name']));
        for ($i=0; $i<$count; $i++) {
            $file = [
                'name'     => $_FILES['milestone_photos']['name'][$i],
                'type'     => $_FILES['milestone_photos']['type'][$i],
                'tmp_name' => $_FILES['milestone_photos']['tmp_name'][$i],
                'error'    => $_FILES['milestone_photos']['error'][$i],
                'size'     => $_FILES['milestone_photos']['size'][$i],
            ];
            $up = kbf_handle_image_upload($file);
            if (isset($up['error'])) wp_send_json_error(['message'=>$up['error']]);
            if (isset($up['url'])) $photo_urls[] = $up['url'];
        }
    }
    $existing = $fund->milestones ? json_decode($fund->milestones, true) : [];
    if (!is_array($existing)) {
        error_log('[KBF][Milestone] Corrupted milestones JSON for fund_id=' . $fund_id . ': ' . substr($fund->milestones, 0, 200));
        $existing = [];
    }
    $milestone = [
        'id'        => bntm_rand_id(),
        'title'     => $title,
        'body'      => $body,
        'photos'    => $photo_urls,
        'created_at'=> current_time('mysql'),
    ];
    array_unshift($existing, $milestone);
    $res = $wpdb->update(
        $ft,
        ['milestones' => wp_json_encode($existing)],
        ['id' => $fund_id],
        ['%s'],
        ['%d']
    );
    if ($res === false) {
        wp_send_json_error(['message'=>'Failed to save milestone.', 'debug'=>$wpdb->last_error]);
    }
    if (function_exists('kbf_push_user_notification')) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        $fund_url = add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$fund_id], $dashboard_url);
        kbf_push_user_notification($biz, [
            'type' => 'fund_story_added',
            'title' => 'Story posted',
            'message' => 'Your story/update was posted successfully.',
            'url' => $fund_url,
            'target_id' => (string)((int)$fund_id),
        ]);
    }
    $fresh = $wpdb->get_row($wpdb->prepare("SELECT milestones FROM {$ft} WHERE id=%d", $fund_id));
    $saved_raw = $fresh ? $fresh->milestones : null;
    wp_send_json_success([
        'message'=>'Milestone saved.',
        'milestone'=>$milestone,
        'fund_id'=>$fund_id,
        'saved_milestones'=>$saved_raw
    ]);
}

function bntm_ajax_kbf_extend_deadline() {
    check_ajax_referer('kbf_extend','nonce');
    if (!kbf_rate_limit_ok('extend_deadline', 10, 300)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();
    if (!kbf_require_onboarding_complete($biz)) { return; }
    $deadline=sanitize_text_field($_POST['deadline']);
    if(strtotime($deadline)<=time()) wp_send_json_error(['message'=>'Deadline must be a future date.']);
    $res=$wpdb->update($t,['deadline'=>$deadline],['id'=>$id,'business_id'=>$biz],['%s'],['%d','%d']);
    if($res!==false) wp_send_json_success(['message'=>'Deadline extended to '.date('M d, Y',strtotime($deadline)).'!']);
    else wp_send_json_error(['message'=>'Failed to extend deadline.']);
}

function bntm_ajax_kbf_toggle_auto_return() {
    check_ajax_referer('kbf_auto_return','nonce');
    if (!kbf_rate_limit_ok('auto_return', 12, 300)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$biz=get_current_user_id();$val=intval($_POST['auto_return']);
    if (!kbf_require_onboarding_complete($biz)) { return; }
    $fund=$wpdb->get_row($wpdb->prepare("SELECT id FROM {$t} WHERE id=%d AND business_id=%d",$id,$biz));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found.']);
    $wpdb->update($t,['auto_return'=>$val],['id'=>$id],['%d'],['%d']);
    wp_send_json_success(['message'=>'Auto-return setting updated.']);
}

function bntm_ajax_kbf_save_organizer_profile() {
    // Clear any buffered output to prevent BOM/whitespace in JSON response
    if (ob_get_length()) ob_clean();
    
    check_ajax_referer('kbf_organizer_profile','_ajax_nonce');
    if (!kbf_rate_limit_ok('save_profile', 12, 300)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    global $wpdb;$pt=$wpdb->prefix.'kbf_organizer_profiles';$biz=get_current_user_id();
    // Ensure expected profile columns exist before writing (legacy installs may miss these).
    if (function_exists('bntm_kbf_ensure_profile_columns')) {
        bntm_kbf_ensure_profile_columns();
    }
    $avatar='';
    if(!empty($_FILES['avatar']['name'])) {
        $up = kbf_handle_image_upload($_FILES['avatar']);
        if(isset($up['error'])) wp_send_json_error(['message'=>$up['error']]);
        if(isset($up['url'])) $avatar=$up['url'];
    }
    $socials=json_encode([
        'facebook'=>esc_url_raw($_POST['social_facebook']??''),
        'instagram'=>esc_url_raw($_POST['social_instagram']??''),
        'twitter'=>esc_url_raw($_POST['social_twitter']??''),
        'website'=>esc_url_raw($_POST['social_website']??''),
    ]);
    $province     = isset($_POST['province']) ? sanitize_text_field(wp_unslash($_POST['province'])) : '';
    $municipality = isset($_POST['municipality']) ? sanitize_text_field(wp_unslash($_POST['municipality'])) : '';
    $barangay     = isset($_POST['barangay']) ? sanitize_text_field(wp_unslash($_POST['barangay'])) : '';
    if ($province === '' || $municipality === '' || $barangay === '') {
        wp_send_json_error(['message' => 'Please select province, municipality, and barangay.']);
    }
    $address = implode(', ', array_filter([$barangay, $municipality, $province]));
    
    // Server-side validation for profile_type: only allow known values
    $allowed_types = ['individual','nonprofit','business'];
    $profile_type_post = isset($_POST['profile_type']) ? sanitize_text_field(wp_unslash($_POST['profile_type'])) : '';
    if ($profile_type_post !== '' && !in_array($profile_type_post, $allowed_types, true)) {
        wp_send_json_error(['message' => 'Invalid account type selected.']);
    }
    $data=[
        'bio'=>sanitize_textarea_field($_POST['bio']??''),
        'social_links'=>$socials,
        'profile_type'=>$profile_type_post,
        'payout_type'=>sanitize_text_field($_POST['payout_type']??''),
        'payout_name'=>sanitize_text_field($_POST['payout_name']??''),
        'payout_number'=>sanitize_text_field($_POST['payout_number']??''),
        'business_id'=>$biz
    ];
    if($avatar) $data['avatar_url']=$avatar;
    if(isset($_POST['phone'])) update_user_meta($biz,'kbf_phone',sanitize_text_field($_POST['phone']));
    update_user_meta($biz,'kbf_address',$address);
    if(!empty($_POST['display_name'])) {
        $new_name = sanitize_text_field($_POST['display_name']);
        $current_user = wp_get_current_user();
        if ($new_name !== $current_user->display_name) {
            wp_update_user(['ID' => $biz, 'display_name' => $new_name]);
            // Clear user cache
            clean_user_cache($biz);
            wp_cache_delete($biz, 'users');
        }
    }
    // Social Name: validate format, uniqueness, then save.
    $raw_social = isset($_POST['kbf_social_name']) ? trim(sanitize_text_field($_POST['kbf_social_name'])) : '';
    $raw_social = ltrim($raw_social, '@');
    if ($raw_social !== '') {
        if (!preg_match('/^[a-zA-Z0-9_]{2,30}$/', $raw_social)) {
            wp_send_json_error(['message' => 'Social name must be 2–30 characters: letters, numbers, and underscores only.']);
        }
        $existing = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='kbf_social_name' AND meta_value=%s AND user_id != %d", $raw_social, $biz));
        if ($existing) {
            wp_send_json_error(['message' => 'That social name is already taken. Please choose another.']);
        }
        update_user_meta($biz, 'kbf_social_name', $raw_social);
    } else {
        delete_user_meta($biz, 'kbf_social_name');
    }
    $exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$pt} WHERE business_id=%d",$biz));
    $write_ok = true;
    if($exists) {
        unset($data['business_id']);
        // Update existing profile
        $updated = $wpdb->update(
            $pt,
            $data,
            ['business_id' => $biz],
            array_fill(0, count($data), '%s'),
            ['%d']
        );
        if ($updated === false) {
            $write_ok = false;
        }
    } else {
        // Insert new profile
        $inserted = $wpdb->insert(
            $pt,
            $data,
            array_fill(0, count($data), '%s')
        );
        if ($inserted === false) {
            $write_ok = false;
        }
    }

    if (!$write_ok) {
        $db_error = (string) $wpdb->last_error;
        if (function_exists('kbf_log')) {
            kbf_log('profile_save_db_failed', [
                'user_id' => (int) $biz,
                'table' => $pt,
                'db_error' => $db_error,
                'data_keys' => array_keys($data),
            ]);
        }
        $msg = 'Profile save failed while writing profile details.';
        if ((defined('WP_DEBUG') && WP_DEBUG) || current_user_can('manage_options')) {
            $msg .= ($db_error !== '' ? ' DB: ' . $db_error : ' DB: unknown error.');
        }
        wp_send_json_error([
            'message' => $msg,
            'error_code' => 'profile_write_failed',
        ]);
    }
    if (function_exists('kbf_get_or_create_organizer_token')) {
        kbf_get_or_create_organizer_token($biz);
    }

    // Clear onboarding flag if all required fields are now filled.
    // Check from POST data (what we just wrote) to avoid DB cache timing issues.
    $post_display_name = isset($_POST['display_name']) ? trim(sanitize_text_field($_POST['display_name'])) : '';
    $post_social_name  = isset($_POST['kbf_social_name']) ? trim(ltrim(sanitize_text_field($_POST['kbf_social_name']), '@')) : '';
    $post_bio          = isset($_POST['bio']) ? trim(sanitize_textarea_field($_POST['bio'])) : '';
    $post_profile_type = isset($_POST['profile_type']) ? trim(sanitize_text_field($_POST['profile_type'])) : '';
    $post_payout_type  = isset($_POST['payout_type']) ? trim(sanitize_text_field($_POST['payout_type'])) : '';
    $post_payout_name  = isset($_POST['payout_name']) ? trim(sanitize_text_field($_POST['payout_name'])) : '';
    $post_payout_num   = isset($_POST['payout_number']) ? trim(sanitize_text_field($_POST['payout_number'])) : '';
    $post_address      = $address !== '' ? $address : (string) get_user_meta($biz, 'kbf_address', true);

    $has_display_name = !empty($post_display_name);
    $has_social_name  = !empty($post_social_name) && preg_match('/^[a-zA-Z0-9_]{2,30}$/', $post_social_name);
    $has_bio          = !empty($post_bio);
    $has_profile_type = !empty($post_profile_type);
    $has_payout       = !empty($post_payout_type) && !empty($post_payout_name) && !empty($post_payout_num);
    $has_address      = !empty($post_address);

    $was_onboarding_flag = (bool)get_user_meta($biz, 'kbf_show_onboarding', true);
    $onboarding_done = ($has_display_name && $has_social_name && $has_bio && $has_profile_type && $has_payout && $has_address);
    if ($onboarding_done) {
        delete_user_meta($biz, 'kbf_show_onboarding');
        // Force cache flush so the next page load sees the deletion immediately.
        wp_cache_delete($biz, 'user_meta');
        wp_cache_delete($biz, 'users');
    }

    // Return updated avatar and display name for instant topbar update
    clean_user_cache($biz);
    wp_cache_delete($biz, 'user_meta');
    wp_cache_delete($biz, 'users');

    $updated_user = get_userdata($biz);
    $updated_profile = $wpdb->get_row($wpdb->prepare("SELECT avatar_url,bio,payout_type,payout_name,payout_number FROM {$pt} WHERE business_id=%d", $biz));

    $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
    $profile_tab_url = add_query_arg('kbf_tab', 'profile', $dashboard_url);
    $notification_unread = kbf_push_user_notification($biz, [
        'type' => 'profile',
        'title' => 'Profile updated',
        'message' => 'Your organizer profile was saved successfully.',
        'url' => $profile_tab_url
    ]);
    if ($onboarding_done && $was_onboarding_flag) {
        $notification_unread = kbf_push_user_notification($biz, [
            'type' => 'onboarding_completed',
            'title' => 'Profile setup completed',
            'message' => 'Great job! Your organizer onboarding is now complete.',
            'url' => $profile_tab_url,
        ]);
    }

    wp_send_json_success([
        'message' => 'Profile saved successfully!',
        'data' => [
            'onboarding_done' => $onboarding_done,
            'avatar_url' => $updated_profile ? $updated_profile->avatar_url : '',
            'display_name' => $updated_user ? $updated_user->display_name : '',
            'bio' => $updated_profile ? (string)$updated_profile->bio : '',
            'payout_type' => $updated_profile ? (string)$updated_profile->payout_type : '',
            'payout_name' => $updated_profile ? (string)$updated_profile->payout_name : '',
            'payout_number' => $updated_profile ? (string)$updated_profile->payout_number : '',
            'notification_unread' => $notification_unread,
        ]
    ]);
}

function bntm_ajax_kbf_dismiss_onboarding() {
    check_ajax_referer('kbf_onboarding','nonce');
    if (!kbf_rate_limit_ok('dismiss_onboarding', 10, 300)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    $biz = get_current_user_id();
    delete_user_meta($biz, 'kbf_show_onboarding');
    wp_send_json_success(['message'=>'Onboarding dismissed.']);
}

function bntm_ajax_kbf_mark_notifications_read() {
    check_ajax_referer('kbf_notifications', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    $uid = get_current_user_id();
    kbf_mark_user_notifications_read($uid);
    wp_send_json_success(['unread' => 0]);
}

function bntm_ajax_kbf_mark_notification_read() {
    check_ajax_referer('kbf_notifications', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    $uid = get_current_user_id();
    $notif_id = isset($_POST['notification_id']) ? sanitize_text_field($_POST['notification_id']) : '';
    $unread = kbf_mark_single_notification_read($uid, $notif_id);
    wp_send_json_success(['unread' => (int)$unread]);
}

function bntm_ajax_kbf_clear_notifications() {
    check_ajax_referer('kbf_notifications', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    $uid = get_current_user_id();
    update_user_meta($uid, 'kbf_notifications', []);
    wp_send_json_success(['unread' => 0, 'cleared' => 1]);
}

function bntm_ajax_kbf_get_notifications() {
    check_ajax_referer('kbf_notifications', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    $uid = get_current_user_id();
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
    if ($limit <= 0 || $limit > 30) {
        $limit = 10;
    }
    $items = kbf_get_user_notifications_latest($uid, $limit);
    $clean = [];
    foreach ($items as $item) {
        $clean[] = [
            'id' => sanitize_text_field($item['id'] ?? ''),
            'type' => sanitize_key($item['type'] ?? 'general'),
            'title' => sanitize_text_field($item['title'] ?? 'Notification'),
            'message' => sanitize_text_field($item['message'] ?? ''),
            'url' => esc_url_raw($item['url'] ?? ''),
            'created_at' => sanitize_text_field($item['created_at'] ?? ''),
            'read' => !empty($item['read']) ? 1 : 0,
        ];
    }
    wp_send_json_success([
        'items' => $clean,
        'unread' => kbf_get_user_unread_notification_count($uid),
    ]);
}


function bntm_ajax_kbf_request_verification() {
    check_ajax_referer('kbf_verify_account','nonce');
    if (!kbf_rate_limit_ok('verify_account', 6, 600)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if(!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    wp_send_json_error(['message' => 'Manual verification is disabled. Please use the Didit verification button in your Profile.']);
}

// ============================================================
// DIDIT VERIFICATION (DEMO: POLLING ONLY)
// ============================================================

function fundora_ajax_start_verification() {
    check_ajax_referer('fundora_didit_nonce', 'nonce');
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'not_logged_in']);
    }
    $result = fundora_didit_create_session($user_id);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    $url = isset($result['url']) ? $result['url'] : '';
    if (!$url) {
        wp_send_json_error(['message' => 'Verification URL not returned.']);
    }
    $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
    $profile_tab_url = add_query_arg('kbf_tab', 'profile', $dashboard_url);
    kbf_notify_admin_users([
        'type' => 'admin_kyc_submitted',
        'title' => 'New KYC verification started',
        'message' => 'An organizer started identity verification.',
        'url' => add_query_arg(['kbf_tab' => 'organizers'], $dashboard_url),
        'target_id' => (string)$user_id,
    ]);
    kbf_push_user_notification($user_id, [
        'type' => 'kyc_in_review',
        'title' => 'Verification in progress',
        'message' => 'Your identity verification has started and is now in review.',
        'url' => $profile_tab_url,
        'target_id' => (string)$user_id,
    ]);
    wp_send_json_success(['url' => esc_url_raw($url)]);
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
    if (!kbf_rate_limit_ok('sponsor_fund', 40, 60)) {
        wp_send_json_error(['message'=>'Too many requests. Please try again shortly.']);
    }
    global $wpdb;$ft=$wpdb->prefix.'kbf_funds';$st=$wpdb->prefix.'kbf_sponsorships';
    $id=intval($_POST['fund_id']);$amount=floatval($_POST['amount']);
    if($amount<50) wp_send_json_error(['message'=>'Minimum sponsorship is &#8369;50.']);
    $fund=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$ft} WHERE id=%d AND status='active'",$id));
    if(!$fund) wp_send_json_error(['message'=>'Fund not found or not accepting sponsorships.']);
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
        $total=$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(s.amount),0) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed' AND s.is_anonymous=0",$fund->business_id));
        $cnt=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$st} s JOIN {$ft} f ON s.fund_id=f.id WHERE f.business_id=%d AND s.payment_status='completed' AND s.is_anonymous=0",$fund->business_id));
        $wpdb->update($pt,['total_raised'=>$total,'total_sponsors'=>$cnt],['business_id'=>$fund->business_id],['%f','%d'],['%d']);
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        $fund_url = add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => $id], $dashboard_url);
        kbf_push_user_notification((int)$fund->business_id, [
            'type' => 'donation_received',
            'title' => 'New sponsorship received',
            'message' => 'You received a new sponsorship worth PHP ' . number_format($amount, 2) . '.',
            'url' => $fund_url,
            'target_id' => (string)$new_id,
        ]);
        if (is_user_logged_in()) {
            kbf_push_user_notification((int)get_current_user_id(), [
                'type' => 'payment_confirmed',
                'title' => 'Payment confirmed',
                'message' => 'Your sponsorship payment was confirmed successfully.',
                'url' => $fund_url,
                'target_id' => (string)$new_id,
            ]);
        }
        if ($just_completed) {
            kbf_push_user_notification((int)$fund->business_id, [
                'type' => 'goal_reached_fund_completed',
                'title' => 'Goal reached',
                'message' => 'Your campaign reached its goal and is now completed.',
                'url' => $fund_url,
                'target_id' => (string)$id,
            ]);
        }
        $msg = $just_completed
            ? 'Sponsorship confirmed! &#8369;'.number_format($amount,2).' added. This fund has now reached its goal!'
            : 'Sponsorship confirmed! &#8369;'.number_format($amount,2).' has been added to this fund. Thank you for your support!';
        wp_send_json_success(['message'=>$msg,'fund_completed'=>$just_completed]);
    } else {
        wp_send_json_error(['message'=>'Sponsorship failed. Please try again.']);
    }
}

function bntm_ajax_kbf_report_fund() {
    check_ajax_referer('kbf_report','nonce');
    if (!kbf_rate_limit_ok('report_fund', 10, 300)) {
        wp_send_json_error(['message'=>'Too many reports. Please wait a bit and try again.']);
    }
    global $wpdb;$t=$wpdb->prefix.'kbf_reports';$ft=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);$reason=sanitize_text_field($_POST['reason']);$details=sanitize_textarea_field($_POST['details']);
    if(empty($reason)||empty($details)) wp_send_json_error(['message'=>'Please fill all required fields.']);
    $fund_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$ft} WHERE id=%d", $id));
    if(!$fund_exists) wp_send_json_error(['message'=>'Fund not found.']);
    $report_image = '';
    if (!empty($_FILES['report_image']['name'])) {
        $upload = kbf_handle_image_upload($_FILES['report_image']);
        if (isset($upload['error'])) {
            wp_send_json_error(['message'=>$upload['error']]);
        }
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
    if($res) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        kbf_notify_admin_users([
            'type' => 'admin_fund_reported',
            'title' => 'New fund report submitted',
            'message' => 'A fundraiser was reported for review.',
            'url' => add_query_arg(['kbf_tab' => 'reports'], $dashboard_url),
            'target_id' => (string)$id,
        ]);
        wp_send_json_success(['message'=>'Report submitted. Our team will review it shortly.']);
    }
    else wp_send_json_error(['message'=>'Failed to submit report.']);
}

function bntm_ajax_kbf_toggle_save_fund() {
    check_ajax_referer('kbf_save_fund','nonce');
    if (!kbf_rate_limit_ok('toggle_save', 40, 60)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
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
    if (!kbf_rate_limit_ok('submit_appeal', 6, 600)) {
        wp_send_json_error(['message'=>'Too many requests. Please wait a moment.']);
    }
    if (!is_user_logged_in()) wp_send_json_error(['message'=>'Please log in to submit an appeal.']);
    $biz = get_current_user_id();
    if (!kbf_require_onboarding_complete($biz)) { return; }
    global $wpdb;
    $ft = $wpdb->prefix.'kbf_funds';
    $at = $wpdb->prefix.'kbf_appeals';
    $fund_id = intval($_POST['fund_id'] ?? 0);
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    if (!$fund_id || !$message) wp_send_json_error(['message'=>'Please provide a valid appeal message.']);
    $fund = $wpdb->get_row($wpdb->prepare("SELECT id,business_id,status FROM {$ft} WHERE id=%d", $fund_id));
    if (!$fund || $fund->business_id != $biz) wp_send_json_error(['message'=>'Unauthorized appeal request.']);
    if ($fund->status !== 'suspended') wp_send_json_error(['message'=>'Only suspended funds can be appealed.']);
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$at} WHERE fund_id=%d AND status='open'", $fund_id));
    if ($exists) wp_send_json_error(['message'=>'You already have an open appeal for this fund.']);
    $wpdb->insert($at, [
        'rand_id'     => bntm_rand_id(),
        'fund_id'     => $fund_id,
        'business_id' => $biz,
        'message'     => $message,
        'status'      => 'open',
    ], ['%s','%d','%d','%s','%s']);
    $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
    kbf_notify_admin_users([
        'type' => 'admin_appeal_submitted',
        'title' => 'New suspension appeal',
        'message' => 'An organizer submitted an appeal for a suspended fund.',
        'url' => add_query_arg(['kbf_tab' => 'appeals'], $dashboard_url),
        'target_id' => (string)$fund_id,
    ]);
    wp_send_json_success(['message'=>'Appeal submitted. Our admin team will review it.']);
}

function bntm_ajax_kbf_get_fund_details() {
    check_ajax_referer('kbf_sponsor','nonce');
    if (!kbf_rate_limit_ok('fund_details', 120, 60)) {
        wp_send_json_error(['message'=>'Too many requests. Please slow down.']);
    }
    global $wpdb;$t=$wpdb->prefix.'kbf_funds';
    $id=intval($_POST['fund_id']);
    $f=$wpdb->get_row($wpdb->prepare("SELECT id,title,description,goal_amount,raised_amount,location,category FROM {$t} WHERE id=%d AND status='active'",$id));
    if($f) wp_send_json_success($f);
    else wp_send_json_error(['message'=>'Fund not found.']);
}

function bntm_ajax_kbf_get_organizer_profile() {
    check_ajax_referer('kbf_sponsor','nonce');
    if (!kbf_rate_limit_ok('organizer_profile', 80, 60)) {
        wp_send_json_error(['message' => 'Too many requests. Please slow down.']);
    }
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
    if (!kbf_rate_limit_ok('submit_rating', 15, 300)) {
        wp_send_json_error(['message' => 'Too many requests. Please wait and try again.']);
    }
    global $wpdb;$rt=$wpdb->prefix.'kbf_ratings';$pt=$wpdb->prefix.'kbf_organizer_profiles';$ft=$wpdb->prefix.'kbf_funds';
    $org_id=intval($_POST['organizer_id']);$rating=min(5,max(1,intval($_POST['rating'])));
    $email=sanitize_email($_POST['sponsor_email']??'');
    if(empty($email)) wp_send_json_error(['message'=>'Email required to submit a score.']);
    $fund_id = intval($_POST['fund_id']??0);
    if ($fund_id) {
        $fund = $wpdb->get_row($wpdb->prepare("SELECT id,business_id FROM {$ft} WHERE id=%d", $fund_id));
        if (!$fund || (int)$fund->business_id !== $org_id) {
            wp_send_json_error(['message'=>'Invalid organizer/fund combination.']);
        }
    }
    // Prevent duplicate rating per email per organizer
    $exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$rt} WHERE organizer_id=%d AND sponsor_email=%s",$org_id,$email));
    if($exists) wp_send_json_error(['message'=>'You have already submitted a score for this organizer.']);
    $wpdb->insert($rt,['rand_id'=>bntm_rand_id(),'organizer_id'=>$org_id,'sponsor_email'=>$email,'rating'=>$rating,'review'=>sanitize_textarea_field($_POST['review']??''),'fund_id'=>$fund_id],['%s','%d','%s','%d','%s','%d']);
    $rating_id = (int)$wpdb->insert_id;
    // Recalculate average
    $avg=$wpdb->get_var($wpdb->prepare("SELECT AVG(rating) FROM {$rt} WHERE organizer_id=%d",$org_id));
    $cnt=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE organizer_id=%d",$org_id));
    $wpdb->update($pt,['rating'=>round($avg,2),'rating_count'=>(int)$cnt],['business_id'=>$org_id],['%f','%d'],['%d']);
    if (function_exists('kbf_push_user_notification')) {
        $dashboard_url = function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/');
        $notify_url = $fund_id
            ? add_query_arg(['kbf_tab' => 'fund_details', 'fund_id' => (int)$fund_id], $dashboard_url)
            : add_query_arg(['kbf_tab' => 'organizer_profile', 'organizer_id' => (int)$org_id], $dashboard_url);
        kbf_push_user_notification((int)$org_id, [
            'type' => 'new_rating_received',
            'title' => 'New credibility score received',
            'message' => 'A sponsor submitted a new rating on your profile.',
            'url' => $notify_url,
            'target_id' => (string)$rating_id,
        ]);
    }
    wp_send_json_success(['message'=>'Thank you for your score!']);
}

function bntm_ajax_kbf_user_refresh_tab() {
    check_ajax_referer('kbf_user_refresh');
    if (!kbf_rate_limit_ok('user_refresh', 60, 60)) {
        wp_send_json_error(['message'=>'Too many requests. Please slow down.']);
    }
    if (!is_user_logged_in()) { wp_send_json_error(['message'=>'Unauthorized']); }
    $tab = sanitize_text_field(isset($_POST['tab']) ? $_POST['tab'] : 'overview');
    $business_id = get_current_user_id();

    $allowed = ['overview','sponsorships','withdrawals','sponsor_history','my_funds'];
    if (!in_array($tab, $allowed, true)) {
        wp_send_json_error(['message'=>'Refresh not allowed for this tab.']);
    }

    $html = '';
    if ($tab === 'overview') {
        $html = kbf_dashboard_overview_tab($business_id);
    } elseif ($tab === 'sponsorships') {
        $html = kbf_dashboard_sponsorships_tab($business_id);
    } elseif ($tab === 'withdrawals') {
        $html = kbf_dashboard_withdrawals_tab($business_id);
    } elseif ($tab === 'sponsor_history') {
        $html = bntm_shortcode_kbf_sponsor_history();
    } elseif ($tab === 'my_funds') {
        $nonce_cancel = wp_create_nonce('kbf_cancel_fund');
        $nonce_extend = wp_create_nonce('kbf_extend');
        $html = kbf_dashboard_my_funds_tab($business_id, $nonce_cancel, $nonce_extend);
    }

    if ($html === '') {
        $html = '<div class="kbf-empty"><p>Nothing to refresh.</p></div>';
    }

    wp_send_json_success(['html' => $html]);
}

// ============================================================
// ADMIN TAB: Settings
// ============================================================


