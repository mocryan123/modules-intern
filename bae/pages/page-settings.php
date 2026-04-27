<?php if (!defined('ABSPATH')) exit;
function bae_settings_tab($user_id, $profile) {
    $nonce     = wp_create_nonce('bae_reset_profile');
    $user_plan = bae_get_user_plan($user_id, $profile);
    $is_free   = $user_plan === 'free';

    ob_start();
    ?>
    <!-- Current Plan Card -->
    <div class="bae-card" style="background:linear-gradient(135deg,rgba(195,25,106,.08),rgba(243,45,134,.04));border-color:rgba(243,45,134,.2);">
        <div class="bae-card-header" style="margin-bottom:0;">
            <div>
                <div class="bae-card-title">Current Plan</div>
                <div class="bae-card-desc" style="margin-top:4px;">
                    <?php if ($user_plan === 'pro'): ?>
                        You're on <strong>Pro</strong> — everything is unlocked. Thank you!
                    <?php elseif ($user_plan === 'starter'): ?>
                        You're on <strong>Starter</strong> — assets, regeneration, and brand kit sharing enabled.
                    <?php else: ?>
                        You're on the <strong>Free plan</strong> — generate each asset once, identity board and wizard always free.
                    <?php endif; ?>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                <?php if ($user_plan === 'pro'): ?>
                    <span class="bae-badge bae-badge-brand" style="font-size:13px;padding:6px 14px;">Pro ✦</span>
                <?php elseif ($user_plan === 'starter'): ?>
                    <span class="bae-badge bae-badge-green" style="font-size:13px;padding:6px 14px;">Starter</span>
                    <button class="bae-btn bae-btn-primary bae-btn-sm" onclick="baePricingOpen()">Upgrade to Pro ✦</button>
                <?php else: ?>
                    <span class="bae-badge bae-badge-gray" style="font-size:13px;padding:6px 14px;">Free</span>
                    <button class="bae-btn bae-btn-primary bae-btn-sm" onclick="baePricingOpen()" style="white-space:nowrap;">Upgrade Now ✦</button>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($is_free): ?>
        <div style="margin-top:20px;display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <?php
            $features = [
                ['label' => 'Wizard & Identity Board', 'free' => true],
                ['label' => 'Generate 7 assets (once)', 'free' => true],
                ['label' => 'Regenerate assets anytime', 'free' => false],
                ['label' => 'Public brand kit link', 'free' => false],
                ['label' => 'Custom AI Generator', 'free' => false],
                ['label' => 'Single brand workspace', 'free' => false],
            ];
            foreach ($features as $f):
            ?>
            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:<?php echo $f['free'] ? 'var(--text-2)' : 'var(--text-3)'; ?>;">
                <?php if ($f['free']): ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                <?php else: ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#5c5972" stroke-width="2"><rect width="11" height="11" x="6.5" y="11" rx="1"/><path d="M12 11V7a4 4 0 0 1 4 4"/></svg>
                <?php endif; ?>
                <?php echo $f['label']; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="bae-card">
        <div class="bae-card-title">Export</div>
        <div class="bae-card-desc" style="margin-top:4px;margin-bottom:16px;">Download all your generated brand assets as a ZIP file — each asset as a standalone HTML file.</div>
        <?php if (!empty($profile)): ?>
        <button class="bae-btn bae-btn-outline" id="bae-export-zip-btn"
                data-nonce="<?php echo wp_create_nonce('bae_reset_profile'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export ZIP
        </button>
        <span id="bae-export-zip-msg" style="font-size:12px;color:var(--text-3);margin-left:12px;"></span>
        <script>
        (function(){
            var btn = document.getElementById('bae-export-zip-btn');
            if (!btn) return;
            btn.addEventListener('click', function() {
                var origText = btn.innerHTML;
                btn.disabled = true;
                btn.textContent = 'Preparing ZIP...';
                var msg = document.getElementById('bae-export-zip-msg');
                msg.textContent = '';

                var fd = new FormData();
                fd.append('action', 'bae_export_zip');
                fd.append('nonce', btn.dataset.nonce);

                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(json) {
                    if (json.success) {
                        // Decode base64 and trigger download
                        var byteChars = atob(json.data.data);
                        var byteArr = new Uint8Array(byteChars.length);
                        for (var i = 0; i < byteChars.length; i++) byteArr[i] = byteChars.charCodeAt(i);
                        var blob = new Blob([byteArr], { type: 'application/zip' });
                        var url  = URL.createObjectURL(blob);
                        var a    = document.createElement('a');
                        a.href     = url;
                        a.download = json.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        msg.textContent = json.data.count + ' assets downloaded.';
                        msg.style.color = '#059669';
                    } else {
                        msg.textContent = json.data.message || 'Export failed.';
                        msg.style.color = '#dc2626';
                    }
                    btn.disabled = false;
                    btn.innerHTML = origText;
                })
                .catch(function() {
                    msg.textContent = 'Network error. Try again.';
                    msg.style.color = '#dc2626';
                    btn.disabled = false;
                    btn.innerHTML = origText;
                });
            });
        })();
        </script>
        <?php else: ?>
        <button class="bae-btn bae-btn-outline" disabled>Export ZIP — Complete your profile first</button>
        <?php endif; ?>
    </div>

    <div class="bae-card">
        <div class="bae-card-title">System Information</div>
        <div style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <?php
            $info = [
                'System'      => 'Mothie',
                'Version'     => '1.1.0',
                'Slug'        => 'bae',
                'Status'      => !empty($profile) ? 'Profile Active' : 'No Profile',
            ];
            foreach ($info as $k => $v):
            ?>
            <div style="font-size:13px;">
                <span style="color:var(--text-3);"><?php echo $k; ?>: </span>
                <span style="color:var(--text);font-weight:500;"><?php echo $v; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($profile)): ?>
    <div class="bae-danger-zone">
        <div class="bae-card-header" style="margin-bottom:16px;">
            <div>
                <div class="bae-card-title">Danger Zone</div>
                <div class="bae-card-desc">Irreversible actions — proceed with caution.</div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--surface)8f8;border:1px solid rgba(244,63,94,0.3);border-radius:8px;flex-wrap:wrap;gap:10px;">
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--text);">Reset Brand Profile</div>
                    <div style="font-size:13px;color:var(--text-3);margin-top:2px;">Clears your brand profile and all generated assets. Cannot be undone.</div>
                </div>
                <button class="bae-btn bae-btn-danger bae-btn-sm" id="bae-reset-btn" data-nonce="<?php echo $nonce; ?>">
                    Reset Everything
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    (function() {
        var resetBtn = document.getElementById('bae-reset-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                var nonce = this.dataset.nonce;
                baeConfirm('This will permanently delete your brand profile and all generated assets. Are you sure?', function() {
                    var formData = new FormData();
                    formData.append('action', 'bae_reset_profile');
                    formData.append('nonce', nonce);
                    fetch(ajaxurl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(json) {
                        if (json.success) location.href = '?tab=overview';
                        else if (window.baeToast) window.baeToast((json.data && json.data.message) ? json.data.message : 'Reset failed. Please try again.', 'error');
                    });
                });
            });
        }
    })();
    </script>
    <?php
    $output = ob_get_clean();
    return apply_filters( 'bae_settings_tab_output', $output );
}
