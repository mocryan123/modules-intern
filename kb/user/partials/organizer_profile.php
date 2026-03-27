<?php
/* Organizer profile shortcode */
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
    <!-- ================== HTML ================== -->
    <div class="kbf-wrap">
    <div class="kbf-page-header">
      <div style="display:flex;align-items:center;gap:16px;">
        <?php if($profile&&$profile->avatar_url): ?>
          <img src="<?php echo esc_url($profile->avatar_url); ?>" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.3);">
        <?php else: ?>
          <div style="width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;"><svg width="32" height="32" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
        <?php endif; ?>
        <div>
          <h2 style="margin:0 0 4px;"><?php echo esc_html($user->display_name); ?></h2>
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