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
            <div class="kbf-create-stepper" id="kbf-create-stepper" aria-label="Create campaign steps">
              <button class="kbf-create-step is-active" type="button" data-step="1">
                <span class="kbf-step-index">1</span>
                <span class="kbf-step-label">Who &amp; Category</span>
              </button>
              <span class="kbf-create-step-line"></span>
              <button class="kbf-create-step" type="button" data-step="2">
                <span class="kbf-step-index">2</span>
                <span class="kbf-step-label">Campaign Info</span>
              </button>
              <span class="kbf-create-step-line"></span>
              <button class="kbf-create-step" type="button" data-step="3">
                <span class="kbf-step-index">3</span>
                <span class="kbf-step-label">Photos &amp; Tiers</span>
              </button>
              <span class="kbf-create-step-line"></span>
              <button class="kbf-create-step" type="button" data-step="4">
                <span class="kbf-step-index">4</span>
                <span class="kbf-step-label">Goal &amp; Contact</span>
              </button>
            </div>

            <div class="kbf-create-panel is-active" data-step="1">
              <div class="kbf-step-note">Step 1 - Choose who you are raising for and a category.</div>
              <div class="kbf-form-group">
                <label>Funding For *</label>
                <div class="kbf-choice-grid" id="kbf-funder-grid">
                  <button type="button" class="kbf-choice-card" data-value="yourself">Yourself</button>
                  <button type="button" class="kbf-choice-card" data-value="someone_else">Someone Else</button>
                  <button type="button" class="kbf-choice-card" data-value="animal_care">Animal Care</button>
                  <button type="button" class="kbf-choice-card" data-value="charity">Charity</button>
                  <button type="button" class="kbf-choice-card" data-value="event">Event</button>
                </div>
                <input type="hidden" name="funder_type" id="kbf-create-funder">
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Category *</label>
                <div class="kbf-category-grid" id="kbf-category-grid">
                  <button type="button" class="kbf-category-card" data-value="Community">
                    <i class="ph ph-users"></i><span>Community</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Sports">
                    <i class="ph ph-tennis-ball"></i><span>Sports</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Family">
                    <i class="ph ph-house"></i><span>Family</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Medical">
                    <i class="ph ph-heartbeat"></i><span>Medical</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Education">
                    <i class="ph ph-graduation-cap"></i><span>Education</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Emergency">
                    <i class="ph ph-siren"></i><span>Emergency</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Business">
                    <i class="ph ph-briefcase"></i><span>Business</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Religion">
                    <i class="ph ph-cross"></i><span>Religion</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Arts &amp; Culture">
                    <i class="ph ph-palette"></i><span>Arts &amp; Culture</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Environment">
                    <i class="ph ph-leaf"></i><span>Environment</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Animals">
                    <i class="ph ph-paw-print"></i><span>Animals</span>
                  </button>
                  <button type="button" class="kbf-category-card" data-value="Others">
                    <i class="ph ph-dots-three"></i><span>Others</span>
                  </button>
                </div>
                <input type="hidden" name="category" id="kbf-create-category">
                <div class="kbf-field-error"></div>
              </div>
            </div>

            <div class="kbf-create-panel" data-step="2">
              <div class="kbf-step-note">Step 2 - Add the campaign name and description.</div>
              <div class="kbf-form-group">
                <label>Campaign Name *</label>
                <input type="text" name="title" id="kbf-create-title" maxlength="150" data-max="150" required>
                <small class="kbf-counter" data-counter-for="kbf-create-title">0 / 150</small>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Campaign Description *</label>
                <textarea name="description" id="kbf-create-description" rows="7" maxlength="800" data-max="800" required></textarea>
                <small class="kbf-counter" data-counter-for="kbf-create-description">0 / 800</small>
                <div class="kbf-field-error"></div>
              </div>
            </div>

            <div class="kbf-create-panel" data-step="3">
              <div class="kbf-step-note">Step 3 - Add photos and optional support tiers.</div>
              <div class="kbf-form-group">
                <label>Photos (up to 5)</label>
                <input type="file" id="kbf-create-photos" name="photos[]" accept="image/*" multiple style="display:none;">
                <div class="kbf-photo-grid" id="kbf-create-photo-grid"></div>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Support Tiers (optional)</label>
                <div id="kbf-tier-list" class="kbf-benefits-list"></div>
                <button type="button" class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-benefit-add" id="kbf-add-tier">+ Add Tier</button>
                <input type="hidden" name="benefits" id="kbf-create-benefits-input">
                <small>Max 5 tiers. Add a name, amount, and what supporters receive.</small>
              </div>
            </div>

            <div class="kbf-create-panel" data-step="4">
              <div class="kbf-step-note">Step 4 - Set your goal, deadline, and contact details.</div>
              <div class="kbf-form-row">
                <div class="kbf-form-group">
                  <label>Goal Amount (PHP) *</label>
                  <input type="text" name="goal_amount" id="kbf-goal-amount" inputmode="decimal" autocomplete="off" required>
                  <small class="kbf-text-sm" id="kbf-fee-note">
                    <?php echo $fee_disabled ? 'Platform fee: 0% (disabled).' : 'Platform fee: 3% per transaction.'; ?>
                  </small>
                  <div class="kbf-meta" id="kbf-fee-preview" style="margin-top:6px;">
                    Platform cut: ?0.00 - Net goal: ?0.00
                  </div>
                  <div class="kbf-field-error"></div>
                </div>
                <div class="kbf-form-group">
                  <label>Deadline *</label>
                  <input type="date" name="deadline" id="kbf-create-deadline" min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                  <small>Minimum 7 days from today.</small>
                  <div class="kbf-meta" id="kbf-create-deadline-today" style="margin-top:6px;"></div>
                  <div class="kbf-field-error"></div>
                </div>
              </div>
              <div class="kbf-form-row">
                <div class="kbf-form-group">
                  <label>Contact Email *</label>
                  <?php $kbf_user = wp_get_current_user(); ?>
                  <input type="email" name="email" id="kbf-create-email" value="<?php echo esc_attr($kbf_user ? $kbf_user->user_email : ''); ?>" required>
                  <div class="kbf-field-error"></div>
                </div>
                <div class="kbf-form-group">
                  <label>Contact Phone *</label>
                  <input type="text" name="phone" id="kbf-create-phone" placeholder="+63 9XX XXX XXXX" required>
                  <div class="kbf-field-error"></div>
                </div>
              </div>
              <div class="kbf-form-group">
                <label>Province *</label>
                <select name="province" id="kbf-province" required>
                  <option value="">Select Province</option>
                  <?php foreach (kbf_get_provinces() as $p): ?>
                    <option value="<?php echo esc_attr($p); ?>"><?php echo esc_html($p); ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Municipality *</label>
                <select name="municipality" id="kbf-municipality" required disabled>
                  <option value="">Select Municipality</option>
                </select>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Barangay *</label>
                <select name="barangay" id="kbf-barangay" required disabled>
                  <option value="">Select Barangay</option>
                </select>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label class="kbf-auth-legal">
                  <input type="checkbox" name="agree_terms" id="kbf-agree-terms" required>
                  I agree to the <a href="<?php echo esc_url(kbf_get_page_url('terms')); ?>" target="_blank" rel="noopener noreferrer">Terms &amp; Agreement</a> and <a href="<?php echo esc_url(kbf_get_page_url('refund')); ?>" target="_blank" rel="noopener noreferrer">Refund Policy</a>.
                </label>
                <div class="kbf-field-error"></div>
              </div>
            </div>

            <div id="kbf-create-msg"></div>
          </form>

          <div class="kbf-create-success" id="kbf-create-success" aria-live="polite">
            <div class="kbf-success-icon">
              <svg viewBox="0 0 52 52" aria-hidden="true">
                <circle class="kbf-success-ring" cx="26" cy="26" r="25" fill="none"></circle>
                <path class="kbf-success-check" fill="none" d="M14 27 L22 35 L38 19"></path>
              </svg>
            </div>
            <h3>Campaign Created!</h3>
            <p>Your campaign is under review. Usually takes 3-5 days or more during peak hours.</p>
            <div class="kbf-success-actions">
              <a href="#" class="kbf-btn kbf-btn-primary" id="kbf-success-view">Back to Home</a>
            </div>
          </div>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary kbf-modal-left" id="kbf-create-prev" type="button">Back</button>
          <div class="kbf-create-footer-actions">
            <button class="kbf-btn kbf-btn-secondary" id="kbf-create-save-close" type="button">Save &amp; Close</button>
            <button class="kbf-btn kbf-btn-primary" id="kbf-create-next" type="button">Next</button>
          </div>
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
              <div class="kbf-auth-quote">"Bayanihan works best when people can see real progress."</div>
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

    <!-- ===== MODAL: Add Story ===== -->
    <div id="kbf-modal-milestone" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3>Add Story</h3>
          <button class="kbf-modal-close" type="button" onclick="kbfCloseModal('kbf-modal-milestone')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <div class="kbf-meta" id="kbf-milestone-fund-title" style="margin-bottom:12px;"></div>
          <form id="kbf-milestone-form" onsubmit="return false;" enctype="multipart/form-data">
            <input type="hidden" id="kbf-milestone-fund-id" name="fund_id">
            <div class="kbf-form-group">
              <label>Story Title</label>
              <input type="text" name="milestone_title" placeholder="e.g., Goal reached!" maxlength="150" required>
              <small class="kbf-title-counter">0 / 150</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Update Details</label>
              <textarea name="milestone_body" rows="4" placeholder="Share an update for your supporters..." maxlength="300" required></textarea>
              <small class="kbf-desc-counter">0 / 300</small>
              <div class="kbf-field-error"></div>
            </div>
            <div class="kbf-form-group">
              <label>Add Photos (optional)</label>
              <input type="file" id="kbf-milestone-photos" class="kbf-milestone-photo-input" name="milestone_photos[]" accept="image/*" multiple>
              <div id="kbf-milestone-photo-previews" class="kbf-photo-previews kbf-milestone-photo-grid"></div>
              <small>Up to 5 photos. JPG/PNG/WebP.</small>
              <div class="kbf-field-error"></div>
            </div>
          </form>
          <div class="kbf-milestone-success" id="kbf-milestone-success" aria-live="polite">
            <div class="kbf-success-icon">
              <svg viewBox="0 0 52 52" aria-hidden="true">
                <circle class="kbf-success-ring" cx="26" cy="26" r="25" fill="none"></circle>
                <path class="kbf-success-check" fill="none" d="M14 27 L22 35 L38 19"></path>
              </svg>
            </div>
            <h3>Story Saved!</h3>
            <p>Your update was posted successfully.</p>
          </div>
          <div id="kbf-milestone-msg" style="margin-top:10px;"></div>
          <div class="kbf-meta">This will appear in the Stories & Updates section.</div>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" type="button" onclick="kbfCloseModal('kbf-modal-milestone')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" type="button" id="kbf-milestone-save">Save Story</button>
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
            <div class="kbf-stepper kbf-create-stepper" aria-label="Edit fund steps">
              <div class="kbf-step is-active" data-step="1"><span>1</span> Campaign Info</div>
              <div class="kbf-step" data-step="2"><span>2</span> Photos &amp; Tiers</div>
              <div class="kbf-step" data-step="3"><span>3</span> Goal &amp; Contact</div>
            </div>

            <div class="kbf-step-content is-active" data-step="1">
              <div class="kbf-step-note">Step 1 - Add the campaign name and description.</div>
              <div class="kbf-form-group">
                <label>Campaign Name *</label>
                <input type="text" name="title" id="edit-fund-title" maxlength="150" required>
                <small class="kbf-title-counter">0 / 150</small>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Campaign Description *</label>
                <textarea name="description" id="edit-fund-desc" rows="7" maxlength="800" required></textarea>
                <small class="kbf-desc-counter">0 / 800</small>
                <div class="kbf-field-error"></div>
              </div>
            </div>

            <div class="kbf-step-content" data-step="2">
              <div class="kbf-step-note">Step 2 - Add photos and optional support tiers.</div>
              <div class="kbf-form-group">
                <label>Photos (up to 5)</label>
                <input type="file" id="kbf-edit-photos" name="photos[]" accept="image/*" multiple style="display:none;">
                <small></small>
                <div class="kbf-photo-previews kbf-photo-grid" id="kbf-edit-photo-previews">
                  <button class="kbf-photo-add" type="button" id="kbf-edit-photo-add" aria-label="Add photos">+</button>
                </div>
                <div class="kbf-field-error"></div>
              </div>
              <div class="kbf-form-group">
                <label>Support Tiers (optional)</label>
                <div class="kbf-benefits-list" id="kbf-edit-benefits"></div>
                <button class="kbf-btn kbf-btn-secondary kbf-btn-sm kbf-benefit-add" type="button" id="kbf-edit-benefit-add">+ Add Tier</button>
                <input type="hidden" name="benefits" id="kbf-edit-benefits-input">
                <small>Max 5 tiers. Add a name, amount, and what supporters receive.</small>
              </div>
            </div>

            <div class="kbf-step-content" data-step="3">
              <div class="kbf-step-note">Step 3: Update deadline and address.</div>
              <div class="kbf-form-group">
                <label>Province</label>
                <select id="kbf-edit-province" name="province" required>
                  <option value="">Select Province</option>
                  <?php foreach (kbf_get_provinces() as $p): ?>
                    <option value="<?php echo esc_attr($p); ?>"><?php echo esc_html($p); ?></option>
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
                <input type="date" name="deadline" id="edit-fund-deadline" min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                <small>Optional - if set, minimum 7 days from today.</small>
                <div class="kbf-meta" id="kbf-edit-deadline-today" style="margin-top:6px;"></div>
                <div class="kbf-field-error"></div>
              </div>
              <!-- Auto-return UI removed -->
            </div>
            <input type="hidden" name="location" id="edit-fund-location-hidden">
            <input type="hidden" name="remove_photos" id="kbf-edit-removed-photos">
            <div id="kbf-edit-msg"></div>
          </form>
          <div class="kbf-edit-success" id="kbf-edit-success" aria-live="polite">
            <div class="kbf-success-icon">
              <svg viewBox="0 0 52 52" aria-hidden="true">
                <circle class="kbf-success-ring" cx="26" cy="26" r="25" fill="none"></circle>
                <path class="kbf-success-check" fill="none" d="M14 27 L22 35 L38 19"></path>
              </svg>
            </div>
            <h3>Campaign Updated!</h3>
            <p>Your changes were saved successfully.</p>
          </div>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary kbf-modal-left" id="kbf-edit-prev" type="button">Back</button>
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
                <i class="ph ph-arrow-counter-clockwise kbf-icon" aria-hidden="true"></i>
              </button>
              <button type="button" class="kbf-photo-editor-icon-btn" id="kbf-photo-rotate-right" aria-label="Rotate right" title="Rotate Right" data-tooltip="Rotate Right">
                <i class="ph ph-arrow-clockwise kbf-icon" aria-hidden="true"></i>
              </button>
              <button type="button" class="kbf-photo-editor-icon-btn" id="kbf-photo-flip-x" aria-label="Flip horizontal" title="Flip Horizontal" data-tooltip="Flip Horizontal">
                <i class="ph ph-flip-vertical kbf-icon" aria-hidden="true"></i>
              </button>
              <button type="button" class="kbf-photo-editor-icon-btn" id="kbf-photo-flip-y" aria-label="Flip vertical" title="Flip Vertical" data-tooltip="Flip Vertical">
                <i class="ph ph-flip-horizontal kbf-icon" aria-hidden="true"></i>
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
              <div style="font-size:13px;color:var(--kbf-blue);font-weight:700;"><span id="wd-available-label"></span> available</div>
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
          <button class="kbf-btn kbf-btn-secondary" type="button" onclick="kbfCloseModal('kbf-modal-wd')">Cancel</button>
          <button class="kbf-btn kbf-btn-primary" type="button" onclick="kbfSubmitWd()">
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
          <button class="kbf-btn kbf-btn-primary" onclick="kbfSubmitAppeal('<?php echo esc_js($nonce_appeal); ?>')">Submit Appeal</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: Trash Fund ===== -->
    <div id="kbf-modal-trash-fund" class="kbf-modal-overlay" style="display:none;">
      <div class="kbf-modal kbf-modal-sm">
        <div class="kbf-modal-header">
          <h3 id="kbf-trash-title">Cancel Campaign</h3>
          <button class="kbf-modal-close" type="button" onclick="kbfCloseModal('kbf-modal-trash-fund')">&times;</button>
        </div>
        <div class="kbf-modal-body">
          <p id="kbf-trash-message" style="margin:0;color:var(--kbf-slate);font-size:13px;">
            This will move the fundraiser to cancelled status and it won't be visible to sponsors.
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
          <p style="margin:0 0 8px;color:var(--kbf-slate);font-size:13px;">
            The fundraiser deadline has passed and the goal wasn't met. Your request will be reviewed by admin.
          </p>
          <p style="margin:0;color:var(--kbf-slate);font-size:12.5px;">
            Escrow releases after deadline are subject to a 5% platform deduction. See our
            <a href="<?php echo esc_url(kbf_get_page_url('refund')); ?>" target="_blank" rel="noopener noreferrer">Refund Policy</a>.
          </p>
        </div>
        <div class="kbf-modal-footer">
          <button class="kbf-btn kbf-btn-secondary" type="button" onclick="kbfCloseModal('kbf-modal-escrow-request')">No</button>
          <button class="kbf-btn kbf-btn-primary" type="button" onclick="kbfConfirmEscrowRequest()">Yes</button>
        </div>
      </div>
    </div>






