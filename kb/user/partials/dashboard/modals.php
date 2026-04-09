<?php $fee_disabled = (bool)kbf_get_setting('kbf_disable_platform_fee', false); ?>
    <!-- ===== MODAL: Create Fund ===== -->
    <div id="kbf-modal-create" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Create New Fund</h3>
          <button class="kbf-modal-close" onclick="kbfRequestCloseCreate()">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-create-fund-form" enctype="multipart/form-data">
            <div class="kbf-stepper" aria-label="Create fund steps">
              <div class="kbf-step is-active" data-step="1"><span>1</span> Type</div>
              <div class="kbf-step" data-step="2"><span>2</span> Details</div>
              <div class="kbf-step" data-step="3"><span>3</span> Location</div>
            </div>

            <div class="kbf-step-content is-active" data-step="1">
              <div class="kbf-step-note">Step 1: Select fundraiser type and category, then set title and description.</div>
              <div class="kbf-form-row">
                <div class="kbf-form-group">
                  <label>Funding For *</label>
                  <select name="funder_type" required>
                    <option value="yourself">Yourself</option>
                    <option value="someone_else">Someone Else</option>
                    <option value="animal_care">Animal Care</option>
                    <option value="charity_event">Charity or Event</option>
                  </select>
                </div>
                <div class="kbf-form-group">
                  <label>Category *</label>
                  <select name="category" required>
                    <?php foreach (kbf_get_categories() as $c): ?>
                      <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="kbf-form-group">
                <label>Fund Title *</label>
                <input type="text" name="title" id="kbf-create-title" placeholder="Clear, compelling title" maxlength="150" required>
                <small class="kbf-title-counter">0 / 150</small>
              </div>
              <div class="kbf-form-group">
                <label>Description *</label>
                <textarea name="description" rows="10" maxlength="800" placeholder="Tell your story and why this fund matters..." required></textarea>
                <small class="kbf-desc-counter">0 / 800</small>
                <div class="kbf-field-error"></div>
              </div>
            </div>

            <div class="kbf-step-content kbf-step-content-flex" data-step="2">
              <div class="kbf-step-note">Step 2: Set amount, deadline, and photos.</div>
              <div class="kbf-form-row">
                <div class="kbf-form-group">
                  <label>Goal Amount (PHP) *</label>
                  <input type="text" name="goal_amount" id="kbf-goal-amount" placeholder="0.00" inputmode="decimal" autocomplete="off" required>
                  <small class="kbf-text-sm" id="kbf-fee-note">
                    <?php echo $fee_disabled ? 'Platform fee: 0% (disabled).' : 'Platform fee: 5% per transaction.'; ?>
                  </small>
                  <div class="kbf-meta" id="kbf-fee-preview" style="margin-top:6px;">
                    Platform cut: ₱0.00 &nbsp;•&nbsp; Net goal: ₱0.00
                  </div>
                </div>
              <div class="kbf-form-group">
                <label>Deadline *</label>
                <input type="date" name="deadline" min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                <small>Required — set an end date (minimum 7 days from today).</small>
              </div>
              </div>
              <div class="kbf-form-group">
                <label>Add Photos (up to 5)</label>
                <input type="file" id="kbf-create-photos" name="photos[]" accept="image/*" multiple required style="display:none;">
                <small></small>
                <div class="kbf-field-error"></div>
                <div class="kbf-photo-previews" id="kbf-create-photo-previews">
                  <button class="kbf-photo-add" type="button" id="kbf-create-photo-add" aria-label="Add photos">+</button>
                </div>
              </div>
              <div class="kbf-photo-tips kbf-photo-tips-bottom">
                <div class="kbf-photo-tips-title">
                  <span class="kbf-photo-tips-icon">i</span>
                  Photo tips checklist
                </div>
                <div class="kbf-photo-tips-list">
                  <div class="kbf-photo-tip-item"><span class="kbf-photo-tip-check">✓</span> Use clear, well-lit images (avoid blur).</div>
                  <div class="kbf-photo-tip-item"><span class="kbf-photo-tip-check">✓</span> Add at least 2 photos to build trust.</div>
                  <div class="kbf-photo-tip-item"><span class="kbf-photo-tip-check">✓</span> Show the cause, not just text graphics.</div>
                </div>
              </div>
            </div>

            <div class="kbf-step-content" data-step="3">
              <div class="kbf-step-note">Step 3: Location and terms.</div>
              <div class="kbf-form-row">
                <div class="kbf-form-group">
                  <label>Contact Email *</label>
                  <input type="email" name="email" required>
                </div>
                <div class="kbf-form-group">
                  <label>Contact Phone *</label>
                  <input type="text" name="phone" placeholder="+63 9XX XXX XXXX" required>
                </div>
              </div>
              <div class="kbf-form-group">
                <label>Province *</label>
                <select name="location" id="kbf-province" required>
                  <option value="">Select Province</option>
                  <?php foreach (kbf_get_provinces() as $p): ?>
                    <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                  <?php endforeach; ?>
                </select>
                <small>Select your province first.</small>
              </div>
              <div class="kbf-form-group">
                <label>Municipality *</label>
                <select name="municipality" id="kbf-municipality" required disabled>
                  <option value="">Select Municipality</option>
                </select>
                <small>Municipality list will load based on province.</small>
              </div>
              <div class="kbf-form-group">
                <label>Barangay *</label>
                <select name="barangay" id="kbf-barangay" required disabled>
                  <option value="">Select Barangay</option>
                </select>
                <small>Barangay list will load based on municipality.</small>
              </div>
              <div class="kbf-form-group">
                <label class="kbf-checkbox-row">
                  <input type="checkbox" name="auto_return" value="1">
                  Auto-return funds to sponsors if goal not met by deadline
                </label>
              </div>
              <div class="kbf-form-group">
                <label class="kbf-checkbox-row">
                  <input type="checkbox" name="agree_terms" id="kbf-agree-terms" required>
                  I agree to the <a href="<?php echo esc_url(kbf_get_page_url('terms')); ?>" target="_blank" rel="noopener noreferrer">Terms &amp; Agreement</a> and <a href="<?php echo esc_url(kbf_get_page_url('refund')); ?>" target="_blank" rel="noopener noreferrer">Refund Policy</a>.
                </label>
                <div class="kbf-field-error"></div>
              </div>
            </div>
            <div id="kbf-create-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" id="kbf-create-prev" type="button">Back</button>
          <button class="kbf-btn kbf-btn-primary" id="kbf-create-next" type="button">Next</button>
          <button class="kbf-btn kbf-btn-primary" id="kbf-create-submit" type="button" style="display:none;">Finish</button>
        </div>
      </div>
    </div>

    
    

    
    <div id="kbf-modal-draft" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Save as Draft?</h3>
          <button class="kbf-modal-close" type="button" onclick="kbfCancelDraftPrompt()">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <p style="margin:0;color:var(--kbf-slate);font-size:13px;">Do you want to save your progress so you can continue later?</p>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" id="kbf-draft-discard" type="button" onclick="kbfDiscardCreateDraft()">No, discard</button>
          <button class="kbf-btn kbf-btn-primary" id="kbf-draft-save" type="button" onclick="kbfSaveCreateDraft()">Yes, save draft</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Auth Required ===== -->
    <div id="kbf-auth-modal" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-auth-modal">
        <button class="kbf-modal-close" type="button" onclick="kbfCloseAuthModal()" aria-label="Close">&times;</button>
        <div class="kbf-auth-grid">
          <div class="kbf-auth-side">
            <div class="kbf-auth-side-inner">
              <div class="kbf-auth-chip">Fundora Access</div>
              <div class="kbf-auth-quote">“Bayanihan works best when people can see real progress.”</div>
              <div class="kbf-auth-sub">Sign in to save campaigns, manage funds, and support with confidence.</div>
            </div>
          </div>
          <div class="kbf-auth-main">
            <div class="kbf-auth-main-inner">
              <h3>Sign in required</h3>
              <p id="kbf-auth-reason">Please sign in to continue.</p>
              <div class="kbf-auth-actions">
                <a class="kbf-btn kbf-btn-primary" href="<?php echo esc_url(function_exists('kbf_get_page_url') ? kbf_get_page_url('signin') : home_url('/wp-login.php')); ?>">Sign in</a>
                <a class="kbf-btn kbf-btn-secondary" href="<?php echo esc_url(function_exists('kbf_get_page_url') ? kbf_get_page_url('signup') : home_url('/wp-login.php')); ?>">Create account</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Edit Fund ===== -->    <div id="kbf-modal-edit" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal">
        <div class="kbf-modal-header">
          <h3>Edit Fund</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-edit')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-edit-fund-form" enctype="multipart/form-data">
            <input type="hidden" name="fund_id" id="edit-fund-id">
            <div class="kbf-stepper" aria-label="Edit fund steps">
              <div class="kbf-step is-active" data-step="1"><span>1</span> Details</div>
              <div class="kbf-step" data-step="2"><span>2</span> Location</div>
            </div>

            <div class="kbf-step-content is-active" data-step="1">
              <div class="kbf-step-note">Step 1: Update title, description, and photos.</div>
              <div class="kbf-form-group">
                <label>Title</label>
                <input type="text" name="title" id="edit-fund-title" maxlength="150" required>
                <small class="kbf-title-counter">0 / 150</small>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Description</label>
                <textarea name="description" id="edit-fund-desc" rows="10" maxlength="800" required></textarea>
                <small class="kbf-desc-counter">0 / 800</small>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Add Photos (up to 5)</label>
                <input type="file" id="kbf-edit-photos" name="photos[]" accept="image/*" multiple style="display:none;">
                <small></small>
                <div class="kbf-field-error"></div>
                <div class="kbf-photo-previews" id="kbf-edit-photo-previews">
                  <button class="kbf-photo-add" type="button" id="kbf-edit-photo-add" aria-label="Add photos">+</button>
                </div>
              </div>
            </div>

            <div class="kbf-step-content" data-step="2">
              <div class="kbf-step-note">Step 2: Update location and deadline.</div>
              <div class="kbf-form-group">
                <label>Province</label>
                <select id="kbf-edit-province" name="province" required>
                  <option value="">Select Province</option>
                  <?php foreach (kbf_get_provinces() as $p): ?>
                    <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                  <?php endforeach; ?>
                </select>
                <small>Select your province first.</small>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Municipality</label>
                <select id="kbf-edit-municipality" name="municipality" required disabled>
                  <option value="">Select Municipality</option>
                </select>
                <small>Municipality list will load based on province.</small>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Barangay</label>
                <select id="kbf-edit-barangay" name="barangay" required disabled>
                  <option value="">Select Barangay</option>
                </select>
                <small>Barangay list will load based on municipality.</small>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Deadline</label>
                <input type="date" name="deadline" id="edit-fund-deadline">
                <small>Optional — update the end date.</small>
              </div>
              <div class="kbf-form-group">
                <label class="kbf-checkbox-row">
                  <input type="checkbox" name="auto_return" id="edit-fund-auto-return" value="1">
                  Auto-return funds to sponsors if goal not met by deadline
                </label>
              </div>
            </div>
            <input type="hidden" name="location" id="edit-fund-location-hidden">
            <input type="hidden" name="remove_photos" id="kbf-edit-removed-photos">
            <div id="kbf-edit-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" id="kbf-edit-prev" type="button">Back</button>
          <button class="kbf-btn kbf-btn-primary" id="kbf-edit-next" type="button">Next</button>
          <button class="kbf-btn kbf-btn-primary" id="kbf-edit-submit" type="button" style="display:none;" onclick="kbfSubmitEdit()">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Photo Editor ===== -->
    <div id="kbf-photo-editor" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-photo-editor-modal">
        <div class="kbf-modal-header">
          <h3>Edit Photo</h3>
          <button class="kbf-modal-close" type="button" id="kbf-photo-editor-close">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <div class="kbf-photo-editor-stage">
            <img id="kbf-photo-editor-img" alt="Photo preview">
            <div class="kbf-photo-editor-crop" id="kbf-photo-editor-crop" aria-hidden="true"></div>
          </div>
          <div class="kbf-photo-editor-controls">
            <div class="kbf-photo-editor-zoom">
              <label for="kbf-photo-zoom">Zoom</label>
              <input type="range" id="kbf-photo-zoom" min="1" max="2.5" step="0.01" value="1">
            </div>
            <div class="kbf-photo-editor-actions">
              <button type="button" class="kbf-photo-editor-icon-btn" id="kbf-photo-rotate-left" aria-label="Rotate left" title="Rotate Left" data-tooltip="Rotate Left">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/arrow-counterclockwise.svg" alt="">
              </button>
              <button type="button" class="kbf-photo-editor-icon-btn" id="kbf-photo-rotate-right" aria-label="Rotate right" title="Rotate Right" data-tooltip="Rotate Right">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/arrow-clockwise.svg" alt="">
              </button>
              <button type="button" class="kbf-photo-editor-icon-btn" id="kbf-photo-flip-x" aria-label="Flip horizontal" title="Flip Horizontal" data-tooltip="Flip Horizontal">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/symmetry-vertical.svg" alt="">
              </button>
              <button type="button" class="kbf-photo-editor-icon-btn" id="kbf-photo-flip-y" aria-label="Flip vertical" title="Flip Vertical" data-tooltip="Flip Vertical">
                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/icons/symmetry-horizontal.svg" alt="">
              </button>
              <button type="button" class="kbf-btn kbf-btn-secondary kbf-photo-editor-reset" id="kbf-photo-reset" aria-label="Reset" title="Reset" data-tooltip="Reset">
                Reset
              </button>
            </div>
          </div>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" type="button" id="kbf-photo-editor-cancel">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" type="button" id="kbf-photo-editor-apply">Apply</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Withdrawal ===== -->
    <div id="kbf-modal-wd" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Request Withdrawal</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-wd')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-wd-form">
            <input type="hidden" name="fund_id" id="wd-fund-id">
            <input type="hidden" name="funder_name" value="<?php echo esc_attr(isset($user->display_name) ? $user->display_name : ''); ?>">
            <div style="background:var(--kbf-slate-lt);border-radius:8px;padding:12px 14px;margin-bottom:16px;">
              <div style="font-size:12px;color:var(--kbf-slate);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">Fund</div>
              <div id="wd-fund-title" style="font-size:14px;font-weight:700;color:var(--kbf-navy);margin-bottom:6px;"></div>
              <div style="font-size:13px;color:var(--kbf-green);font-weight:700;"><span id="wd-available-label"></span> available</div>
            </div>
            <div class="kbf-form-group">
              <label>Amount to Withdraw (PHP) *</label>
              <input type="number" name="amount" id="wd-amount" placeholder="0.00" min="1" step="0.01" required>
              <small style="color:var(--kbf-slate);font-size:11.5px;">Admin will review and process your request within 1-3 business days.</small>
            </div>
            <input type="hidden" name="method" value="online_payment">
              <div class="kbf-form-row">
                <div class="kbf-form-group">
                  <label>Account Type *</label>
                  <select name="account_type" required>
                    <option value="" <?php echo $payout_type===''?'selected':''; ?>>Select type</option>
                    <option value="maya_wallet" <?php echo $payout_type==='maya_wallet'?'selected':''; ?>>Maya Wallet</option>
                    <option value="gcash" <?php echo $payout_type==='gcash'?'selected':''; ?>>GCash</option>
                    <option value="card" <?php echo $payout_type==='card'?'selected':''; ?>>Credit/Debit Card</option>
                  </select>
                </div>
                <div class="kbf-form-group">
                  <label>Account Name *</label>
                  <input type="text" name="account_name" value="<?php echo esc_attr($payout_name); ?>" placeholder="Full name on account" required>
                </div>
              <div class="kbf-form-group">
                <label>Account Number *</label>
                <input type="text" name="account_number" value="<?php echo esc_attr($payout_number); ?>" placeholder="e.g. 09XX XXX XXXX" required>
              </div>
            </div>
            <div class="kbf-form-group">
              <label>Additional Details</label>
              <textarea name="account_details" rows="2" placeholder="Bank name, branch, or any other details..."></textarea>
            </div>
            <div id="kbf-wd-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-wd')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitWd()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Submit Request
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Appeal Suspension ===== -->
    <div id="kbf-modal-appeal" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Appeal Suspension</h3>
          <button class="kbf-modal-close" onclick="kbfCloseModal('kbf-modal-appeal')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <form id="kbf-appeal-form">
            <input type="hidden" name="fund_id" id="kbf-appeal-fund-id">
            <div class="kbf-form-group">
              <label>Appeal Message *</label>
              <textarea name="message" rows="4" placeholder="Explain why this fund should be reinstated..." required></textarea>
            </div>
            <div id="kbf-appeal-msg"></div>
          </form>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" onclick="kbfCloseModal('kbf-modal-appeal')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitAppeal('<?php echo $nonce_appeal; ?>')">Submit Appeal</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Trash Fund ===== -->
    <div id="kbf-modal-trash-fund" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3 id="kbf-trash-title">Trash?</h3>
          <button class="kbf-modal-close" type="button" onclick="kbfCloseModal('kbf-modal-trash-fund')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <p id="kbf-trash-message" style="margin:0;color:var(--kbf-slate);font-size:13px;">
            This will move the fundraiser to cancelled status and it won’t be visible to sponsors.
            Are you sure you want to continue?
          </p>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" type="button" onclick="kbfCloseModal('kbf-modal-trash-fund')">No</button>
          <button class="kbf-btn kbf-btn-primary" type="button" onclick="kbfConfirmTrashFund()">Yes</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Request Escrow ===== -->
    <div id="kbf-modal-escrow-request" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Request Escrow?</h3>
          <button class="kbf-modal-close" type="button" onclick="kbfCloseModal('kbf-modal-escrow-request')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <p style="margin:0;color:var(--kbf-slate);font-size:13px;">
            The fundraiser deadline has passed and the goal wasn’t met. Your request will be reviewed by admin.
            Continue?
          </p>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" type="button" onclick="kbfCloseModal('kbf-modal-escrow-request')">No</button>
          <button class="kbf-btn kbf-btn-primary" type="button" onclick="kbfConfirmEscrowRequest()">Yes</button>
        </div>
      </div>
    </div>



