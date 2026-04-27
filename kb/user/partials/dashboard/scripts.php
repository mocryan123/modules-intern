        <!-- ================== JS ================== -->
    <script>if(typeof ajaxurl==='undefined') var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';</script>
    <script>
      window.kbfIsLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
      // If page is restored from back-forward cache, force a fresh request.
      // This prevents stale signed-in UI from appearing after logout.
      window.addEventListener('pageshow', function(event){
        if (event && event.persisted) {
          window.location.reload();
        }
      });
    /**
     * @function  kbfOpenAuthModal
     * @purpose   Handles kbfOpenAuthModal behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    reason: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfOpenAuthModal = function(reason){
        var modal = document.getElementById('kbf-auth-modal');
        if (!modal) return;
        var msg = document.getElementById('kbf-auth-reason');
        if (msg && reason) msg.textContent = reason;
        modal.style.display = 'flex';
        requestAnimationFrame(function(){ modal.classList.add('is-open'); });
        // keep page scroll enabled
    };
    /**
     * @function  kbfCloseAuthModal
     * @purpose   Handles kbfCloseAuthModal behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfCloseAuthModal = function(){
        var modal = document.getElementById('kbf-auth-modal');
        if (!modal) return;
        modal.classList.remove('is-open');
        setTimeout(function(){ modal.style.display = 'none'; }, 220);
        // keep page scroll enabled
    };
      if (typeof window.kbfEscapeHtml !== 'function') {
        /**
         * @function  kbfEscapeHtml
         * @purpose   Handles kbfEscapeHtml behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    value: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfEscapeHtml = function(value){
          return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        };
      }
      if (typeof window.kbfSafeUrl !== 'function') {
        /**
         * @function  kbfSafeUrl
         * @purpose   Handles kbfSafeUrl behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    value: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfSafeUrl = function(value){
          var url = String(value == null ? '' : value).trim();
          if (!url) return '';
          if (/^(https?:|\/|blob:|data:image\/)/i.test(url)) return url;
          return '';
        };
      }
      if (typeof window.kbfShowTransientNotice !== 'function') {
        /**
         * @function  kbfShowTransientNotice
         * @purpose   Shows a temporary floating notice on the page
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    message: any - parameter; type: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfShowTransientNotice = function(message, type){
          var text = String(message || '').trim();
          if (!text) return;
          var notice = document.createElement('div');
          notice.className = 'kbf-alert kbf-alert-' + (type || 'success') + ' kbf-alert-noicon';
          notice.textContent = text;
          notice.style.position = 'fixed';
          notice.style.bottom = '16px';
          notice.style.right = '16px';
          notice.style.zIndex = '9999';
          notice.style.maxWidth = '360px';
          notice.style.fontFamily = "'Poppins', sans-serif";
          notice.style.boxShadow = '0 8px 24px rgba(15,23,42,.12)';
          notice.style.opacity = '0';
          notice.style.transform = 'translateY(-8px)';
          notice.style.transition = 'opacity .18s ease, transform .18s ease';
          document.body.appendChild(notice);
          requestAnimationFrame(function(){
            notice.style.opacity = '1';
            notice.style.transform = 'translateY(0)';
          });
          setTimeout(function(){
            notice.style.opacity = '0';
            notice.style.transform = 'translateY(-8px)';
            setTimeout(function(){
              if (notice && notice.parentNode) notice.parentNode.removeChild(notice);
            }, 220);
          }, 2600);
        };
      }
      document.addEventListener('DOMContentLoaded', function(){
        try {
          var updatedAt = parseInt(localStorage.getItem('kbf_fund_updated') || '0', 10);
          if (!updatedAt) return;
          localStorage.removeItem('kbf_fund_updated');
          if ((Date.now() - updatedAt) > (5 * 60 * 1000)) return;
          window.kbfShowTransientNotice('Campaign updated successfully.', 'success');
        } catch(e) {}
      });
      document.addEventListener('click', function(e){
        var modal = document.getElementById('kbf-auth-modal');
        if (modal && e.target === modal) window.kbfCloseAuthModal();
      });
    </script>

    <script>
      // ===== CREATE MODAL REDESIGN =====
      document.addEventListener('DOMContentLoaded', function(){
        var modal = document.getElementById('kbf-modal-create');
        var form = document.getElementById('kbf-create-fund-form');
        if (!modal || !form || !form.querySelector('.kbf-create-panel')) return;

        var stepper = document.getElementById('kbf-create-stepper');
        var stepButtons = stepper ? Array.from(stepper.querySelectorAll('.kbf-create-step')) : [];
        var stepLines = stepper ? Array.from(stepper.querySelectorAll('.kbf-create-step-line')) : [];
        var panels = Array.from(form.querySelectorAll('.kbf-create-panel'));
        var btnPrev = document.getElementById('kbf-create-prev');
        var btnNext = document.getElementById('kbf-create-next');
        var btnSave = document.getElementById('kbf-create-save-close');
        var msg = document.getElementById('kbf-create-msg');
        var success = document.getElementById('kbf-create-success');
        var successView = document.getElementById('kbf-success-view');
        var successShare = document.getElementById('kbf-success-share');

        var funderGrid = document.getElementById('kbf-funder-grid');
        var funderInput = document.getElementById('kbf-create-funder');
        var categoryGrid = document.getElementById('kbf-category-grid');
        var categoryInput = document.getElementById('kbf-create-category');
        var categoryMore = document.getElementById('kbf-category-more');

        var titleInput = document.getElementById('kbf-create-title');
        var descInput = document.getElementById('kbf-create-description');
        var goalInput = document.getElementById('kbf-goal-amount');
        var deadlineInput = document.getElementById('kbf-create-deadline');
        var deadlineTodayMeta = document.getElementById('kbf-create-deadline-today');
        var photoInput = document.getElementById('kbf-create-photos');
        var photoGrid = document.getElementById('kbf-create-photo-grid');
        var tierList = document.getElementById('kbf-tier-list');
        var addTierBtn = document.getElementById('kbf-add-tier');
        var benefitsInput = document.getElementById('kbf-create-benefits-input');
        var emailInput = document.getElementById('kbf-create-email');
        var phoneInput = document.getElementById('kbf-create-phone');
        var provInput = document.getElementById('kbf-province');
        var muniInput = document.getElementById('kbf-municipality');
        var brgyInput = document.getElementById('kbf-barangay');
        var termsInput = document.getElementById('kbf-agree-terms');
        var profileAddressSeed = <?php echo wp_json_encode(is_user_logged_in() ? sanitize_text_field((string) get_user_meta((int) $business_id, 'kbf_address', true)) : ''); ?>;
        var profilePhoneSeed = <?php echo wp_json_encode(is_user_logged_in() ? sanitize_text_field((string) get_user_meta((int) $business_id, 'kbf_phone', true)) : ''); ?>;
        var createPhotoMaxSize = 5 * 1024 * 1024;

        var campaignData = {
          step: 1,
          maxStepReached: 1,
          funder_type: '',
          category: '',
          title: '',
          description: '',
          goal_amount: '',
          deadline: '',
          photos: [],
          tiers: [],
          email: '',
          phone: '',
          province: '',
          municipality: '',
          barangay: '',
          agree_terms: false
        };
        window.campaignData = campaignData;

        var draftKey = 'kbf_create_draft_<?php echo (int)$business_id; ?>';
        var lastSavedHash = '';

        /**
         * @function  draftStorageOk
         * @purpose   Handles draftStorageOk behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function draftStorageOk(){
          try {
            var t = '__kbf__';
            localStorage.setItem(t, '1');
            localStorage.removeItem(t);
            return true;
          } catch(e) { return false; }
        }

        /**
         * @function  setError
         * @purpose   Handles setError behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    el: any - parameter; message: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function setError(el, message){
          if (!el) return;
          var group = el.closest('.kbf-form-group');
          if (!group) return;
          var msgEl = group.querySelector('.kbf-field-error');
          if (msgEl) {
            msgEl.textContent = message || 'This field is required.';
            msgEl.style.display = 'block';
          }
        }

        /**
         * @function  clearError
         * @purpose   Handles clearError behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    el: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function clearError(el){
          if (!el) return;
          var group = el.closest('.kbf-form-group');
          if (!group) return;
          var msgEl = group.querySelector('.kbf-field-error');
          if (msgEl) {
            msgEl.textContent = '';
            msgEl.style.display = '';
          }
        }

        /**
         * @function  updateCounter
         * @purpose   Handles updateCounter behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    input: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function updateCounter(input){
          if (!input || !input.id) return;
          var max = parseInt(input.getAttribute('data-max') || input.getAttribute('maxlength') || '0', 10);
          if (!max) return;
          var counter = form.querySelector('[data-counter-for=\"' + input.id + '\"]');
          if (!counter) return;
          counter.textContent = (input.value || '').length + ' / ' + max;
        }

        /**
         * @function  bindCounter
         * @purpose   Handles bindCounter behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    input: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function bindCounter(input){
          if (!input) return;
          updateCounter(input);
          input.addEventListener('input', function(){ updateCounter(input); });
        }

        /**
         * @function  setStep
         * @purpose   Handles setStep behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    step: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function setStep(step){
          var nextStep = Math.max(1, Math.min(4, parseInt(step || '1', 10) || 1));
          campaignData.step = nextStep;
          campaignData.maxStepReached = Math.max(campaignData.maxStepReached, nextStep);

          panels.forEach(function(panel){
            var pStep = parseInt(panel.getAttribute('data-step') || '0', 10);
            panel.classList.toggle('is-active', pStep === nextStep);
          });

          stepButtons.forEach(function(btn, idx){
            var s = parseInt(btn.getAttribute('data-step') || (idx + 1), 10);
            var indexEl = btn.querySelector('.kbf-step-index');
            var isActive = s === nextStep;
            var isComplete = s < nextStep || (s <= campaignData.maxStepReached && s !== nextStep);
            btn.classList.toggle('is-active', isActive);
            btn.classList.toggle('is-complete', isComplete);
            btn.disabled = s > campaignData.maxStepReached;
            if (indexEl) indexEl.textContent = isComplete ? '\u2713' : String(s);
          });

          stepLines.forEach(function(line, idx){
            line.classList.toggle('is-complete', idx + 1 < campaignData.maxStepReached);
          });

          if (btnPrev) btnPrev.style.visibility = nextStep === 1 ? 'hidden' : 'visible';
          if (btnNext) {
            btnNext.textContent = nextStep === 4 ? 'Review & Submit' : 'Next';
          }

          var body = modal.querySelector('.kbf-modal-body');
          if (body) {
            if (typeof body.scrollTo === 'function') body.scrollTo({ top: 0, behavior: 'smooth' });
            else body.scrollTop = 0;
          }
          updateSaveCloseState();
        }

        /**
         * @function  hasValue
         * @purpose   Handles hasValue behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function hasValue(){
          return !!(
            campaignData.funder_type ||
            campaignData.category ||
            campaignData.title ||
            campaignData.description ||
            campaignData.goal_amount ||
            campaignData.deadline ||
            campaignData.email ||
            campaignData.phone ||
            campaignData.province ||
            campaignData.municipality ||
            campaignData.barangay ||
            campaignData.agree_terms ||
            campaignData.photos.length ||
            campaignData.tiers.length
          );
        }

        /**
         * @function  draftComparable
         * @purpose   Handles draftComparable behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    draft: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function draftComparable(draft){
          if (!draft) return '';
          return JSON.stringify({
            data: draft.data || {},
            photos: Array.isArray(draft.photos) ? draft.photos.map(function(p){ return p && p.dataUrl ? p.dataUrl : ''; }) : []
          });
        }

        /**
         * @function  buildDraft
         * @purpose   Handles buildDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function buildDraft(){
          return {
            data: Object.assign({}, campaignData),
            photos: campaignData.photos.map(function(file){
              return {
                dataUrl: file && file._kbfDataUrl ? file._kbfDataUrl : '',
                name: file && file.name ? file.name : 'photo.jpg',
                type: file && file.type ? file.type : 'image/jpeg'
              };
            }),
            saved_at: Date.now()
          };
        }

        /**
         * @function  dataUrlToFile
         * @purpose   Handles dataUrlToFile behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    dataUrl: any - parameter; name: any - parameter; type: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function dataUrlToFile(dataUrl, name, type){
          if (!dataUrl || dataUrl.indexOf('data:') !== 0) return null;
          var parts = dataUrl.split(',');
          if (parts.length < 2) return null;
          var mime = type || (parts[0].match(/data:([^;]+)/) || [])[1] || 'image/jpeg';
          var binary = atob(parts[1]);
          var len = binary.length;
          var bytes = new Uint8Array(len);
          for (var i=0;i<len;i++) bytes[i] = binary.charCodeAt(i);
          return new File([bytes], name || ('photo-' + Date.now() + '.jpg'), { type: mime });
        }

        /**
         * @function  ensurePhotoData
         * @purpose   Handles ensurePhotoData behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function ensurePhotoData(){
          var promises = campaignData.photos.map(function(file){
            return new Promise(function(resolve){
              if (!file) return resolve();
              if (file._kbfDataUrl) return resolve();
              var reader = new FileReader();
              reader.onload = function(e){
                file._kbfDataUrl = e && e.target ? e.target.result : '';
                resolve();
              };
              reader.onerror = function(){ resolve(); };
              reader.readAsDataURL(file);
            });
          });
          return Promise.all(promises);
        }

        /**
         * @function  getDraft
         * @purpose   Handles getDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function getDraft(){
          if (!draftStorageOk()) return null;
          try {
            var raw = localStorage.getItem(draftKey);
            return raw ? JSON.parse(raw) : null;
          } catch(e) { return null; }
        }

        /**
         * @function  setDraft
         * @purpose   Handles setDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    data: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function setDraft(data){
          if (!draftStorageOk()) return;
          if (!data) {
            localStorage.removeItem(draftKey);
            lastSavedHash = '';
            updateSaveCloseState();
            return;
          }
          try {
            localStorage.setItem(draftKey, JSON.stringify(data));
          } catch(e) {
            try {
              var safe = Object.assign({}, data, { photos: [] });
              localStorage.setItem(draftKey, JSON.stringify(safe));
            } catch(e2) {}
          }
          lastSavedHash = draftComparable(data);
          updateSaveCloseState();
        }

        /**
         * @function  applyDraft
         * @purpose   Handles applyDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    draft: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function applyDraft(draft){
          if (!draft || !draft.data) return false;
          Object.assign(campaignData, draft.data);
          if (campaignData.tagline !== undefined) delete campaignData.tagline;
          campaignData.photos = [];

          if (funderInput) funderInput.value = campaignData.funder_type || '';
          if (categoryInput) categoryInput.value = campaignData.category || '';
          if (titleInput) titleInput.value = campaignData.title || '';
          if (descInput) descInput.value = campaignData.description || '';
          if (goalInput) goalInput.value = campaignData.goal_amount || '';
          if (deadlineInput) deadlineInput.value = campaignData.deadline || '';
          if (emailInput) emailInput.value = campaignData.email || emailInput.value || '';
          if (phoneInput) phoneInput.value = campaignData.phone || '';
          if (provInput) provInput.value = campaignData.province || '';

          if (funderGrid) {
            funderGrid.querySelectorAll('.kbf-choice-card').forEach(function(btn){
              btn.classList.toggle('is-selected', btn.getAttribute('data-value') === campaignData.funder_type);
            });
          }
          if (categoryGrid) {
            categoryGrid.querySelectorAll('.kbf-category-card').forEach(function(btn){
              btn.classList.toggle('is-selected', btn.getAttribute('data-value') === campaignData.category);
            });
          }

          if (goalInput) goalInput.dispatchEvent(new Event('input'));
          if (titleInput) updateCounter(titleInput);
          if (descInput) updateCounter(descInput);

          if (draft.photos && Array.isArray(draft.photos)) {
            draft.photos.slice(0, 5).forEach(function(p){
              var file = dataUrlToFile(p.dataUrl, p.name, p.type);
              if (file) {
                file._kbfDataUrl = p.dataUrl;
                campaignData.photos.push(file);
              }
            });
          }
          syncPhotoInput();
          renderPhotoGrid();

          campaignData.tiers = Array.isArray(campaignData.tiers) ? campaignData.tiers : [];
          renderTiers();

          if (provInput && muniInput && brgyInput) {
            var parts = [];
            if (campaignData.barangay) parts.push(campaignData.barangay);
            if (campaignData.municipality) parts.push(campaignData.municipality);
            if (campaignData.province) parts.push(campaignData.province);
            if (parts.length && typeof window.kbfApplyLocationSelection === 'function') {
              window.kbfApplyLocationSelection(provInput, muniInput, brgyInput, parts.join(', '));
            }
          }

          if (termsInput) termsInput.checked = !!campaignData.agree_terms;
          setStep(campaignData.step || 1);
          return true;
        }

        /**
         * @function  updateSaveCloseState
         * @purpose   Handles updateSaveCloseState behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function updateSaveCloseState(){
          if (!btnSave) return;
          if (!hasValue()) {
            btnSave.disabled = true;
            return;
          }
          var hash = draftComparable(buildDraft());
          btnSave.disabled = hash === lastSavedHash;
        }

        /**
         * @function  setTierData
         * @purpose   Handles setTierData behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    index: any - parameter; field: any - parameter; value: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function setTierData(index, field, value){
          if (!campaignData.tiers[index]) return;
          campaignData.tiers[index][field] = value;
          updateBenefitsInput();
          updateSaveCloseState();
        }

        /**
         * @function  updateBenefitsInput
         * @purpose   Handles updateBenefitsInput behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function updateBenefitsInput(){
          if (!benefitsInput) return;
          var clean = campaignData.tiers.filter(function(t){
            return (t.name || '').trim() || (t.amount || '').toString().trim() || (t.perks || '').trim();
          }).map(function(t){
            return { title: t.name || '', amount: t.amount || '', description: t.perks || '' };
          });
          benefitsInput.value = clean.length ? JSON.stringify(clean) : '';
        }

        /**
         * @function  getCreateBenefitsPayload
         * @purpose   Builds the final tier payload from the redesign state for AJAX submission.
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   string
         * @status    ACTIVE
         */
        function getCreateBenefitsPayload(){
          var clean = campaignData.tiers.filter(function(t){
            return (t.name || '').trim() || (t.amount || '').toString().trim() || (t.perks || '').trim();
          }).map(function(t){
            return {
              title: (t.name || '').trim(),
              amount: (t.amount || '').toString().trim(),
              description: (t.perks || '').trim()
            };
          });
          return clean.length ? JSON.stringify(clean) : '';
        }

        /**
         * @function  formatBenefitAmountDisplay
         * @purpose   Formats benefit amounts with comma separators while preserving decimals.
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    value: any - parameter
         * @returns   string
         * @status    ACTIVE
         */
        function formatBenefitAmountDisplay(value){
          var clean = String(value || '').replace(/[^0-9.]/g, '');
          if (!clean) return '';
          var parts = clean.split('.');
          var whole = parts.shift() || '';
          var decimal = parts.length ? parts.join('').slice(0, 2) : '';
          var wholeFormatted = whole ? whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0';
          return decimal ? (wholeFormatted + '.' + decimal) : wholeFormatted;
        }
        function getCreateTierValidation(index){
          var tier = campaignData.tiers[index] || {};
          var title = String(tier.name || '').trim();
          var amount = String(tier.amount || '').replace(/[^0-9.]/g, '');
          var desc = String(tier.perks || '').trim();
          var hasAny = !!(title || amount || desc);
          var errors = { title: '', amount: '', desc: '' };
          if (title.length > 80) errors.title = 'Tier name must be 80 characters or fewer.';
          if (desc.length > 200) errors.desc = 'Description must be 200 characters or fewer.';
          if (amount) {
            var amountNum = parseFloat(amount);
            if (isNaN(amountNum) || amountNum <= 0) errors.amount = 'Enter a valid amount greater than 0.';
          }
          if (hasAny) {
            if (!title) errors.title = errors.title || 'Tier name is required.';
            if (!amount) errors.amount = errors.amount || 'Amount is required.';
            if (!desc) errors.desc = errors.desc || 'Description is required.';
          }
          return {
            hasAny: hasAny,
            valid: !errors.title && !errors.amount && !errors.desc,
            errors: errors
          };
        }
        function validateCreateTiers(){
          var firstInvalid = null;
          campaignData.tiers.forEach(function(_, idx){
            var result = getCreateTierValidation(idx);
            if (!result.valid && !firstInvalid) {
              firstInvalid = tierList && tierList.querySelector('[data-tier-index="' + idx + '"].is-invalid input, [data-tier-index="' + idx + '"].is-invalid textarea');
            }
          });
          renderTiers();
          if (!firstInvalid && tierList) {
            firstInvalid = tierList.querySelector('.kbf-benefit-card.is-invalid input, .kbf-benefit-card.is-invalid textarea');
          }
          return firstInvalid;
        }

        /**
         * @function  renderTiers
         * @purpose   Handles renderTiers behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function renderTiers(){
          if (!tierList) return;
          tierList.innerHTML = '';
          campaignData.tiers.forEach(function(tier, idx){
            var validation = getCreateTierValidation(idx);
            var card = document.createElement('div');
            card.className = 'kbf-benefit-card';
            card.setAttribute('data-tier-index', String(idx));
            if (!validation.valid) card.classList.add('is-invalid');

            var row = document.createElement('div');
            row.className = 'kbf-benefit-row';

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'kbf-benefit-remove';
            remove.innerHTML = '&times;';
            remove.setAttribute('aria-label', 'Remove tier ' + (idx + 1));
            remove.addEventListener('click', function(){
              campaignData.tiers.splice(idx, 1);
              renderTiers();
              updateSaveCloseState();
            });

            var name = document.createElement('input');
            name.type = 'text';
            name.className = 'kbf-benefit-title';
            name.placeholder = 'Tier name';
            name.value = tier.name || '';
            name.id = 'kbf-tier-name-' + idx;
            name.setAttribute('maxlength', '80');
            name.setAttribute('data-max', '80');
            name.addEventListener('input', function(){
              setTierData(idx, 'name', name.value);
            });
            var nameMeta = document.createElement('div');
            nameMeta.className = 'kbf-benefit-meta kbf-benefit-meta-name';
            var nameErr = document.createElement('small');
            nameErr.className = 'kbf-benefit-field-error';
            nameErr.textContent = validation.errors.title || '';
            var nameCounter = document.createElement('small');
            nameCounter.className = 'kbf-benefit-counter';
            nameCounter.textContent = (name.value || '').length + ' / 80';
            name.addEventListener('input', function(){
              nameCounter.textContent = (name.value || '').length + ' / 80';
            });

            var amount = document.createElement('input');
            amount.type = 'text';
            amount.inputMode = 'decimal';
            amount.className = 'kbf-benefit-amount';
            amount.placeholder = 'Amount (PHP)';
            amount.value = formatBenefitAmountDisplay(tier.amount || '');
            amount.addEventListener('input', function(){
              var rawAmount = String(amount.value || '').replace(/[^0-9.]/g, '');
              amount.value = formatBenefitAmountDisplay(rawAmount);
              setTierData(idx, 'amount', rawAmount);
            });
            var amountMeta = document.createElement('div');
            amountMeta.className = 'kbf-benefit-meta kbf-benefit-meta-amount';
            var amountErr = document.createElement('small');
            amountErr.className = 'kbf-benefit-field-error';
            amountErr.textContent = validation.errors.amount || '';
            if (validation.errors.amount) amountMeta.classList.add('has-error');
            amountMeta.appendChild(amountErr);

            var perks = document.createElement('textarea');
            perks.className = 'kbf-benefit-desc';
            perks.rows = 3;
            perks.placeholder = 'Short description';
            perks.value = tier.perks || '';
            perks.id = 'kbf-tier-perks-' + idx;
            perks.setAttribute('maxlength', '200');
            perks.setAttribute('data-max', '200');
            perks.addEventListener('input', function(){
              setTierData(idx, 'perks', perks.value);
            });
            var perksMeta = document.createElement('div');
            perksMeta.className = 'kbf-benefit-meta kbf-benefit-meta-desc';
            var perksErr = document.createElement('small');
            perksErr.className = 'kbf-benefit-field-error';
            perksErr.textContent = validation.errors.desc || '';
            var perksCounter = document.createElement('small');
            perksCounter.className = 'kbf-benefit-counter';
            perksCounter.textContent = (perks.value || '').length + ' / 200';
            perks.addEventListener('input', function(){
              perksCounter.textContent = (perks.value || '').length + ' / 200';
            });
            nameMeta.appendChild(nameErr);
            nameMeta.appendChild(nameCounter);
            perksMeta.appendChild(perksErr);
            perksMeta.appendChild(perksCounter);

            row.appendChild(name);
            row.appendChild(amount);
            row.appendChild(remove);
            card.appendChild(row);
            card.appendChild(nameMeta);
            card.appendChild(amountMeta);
            card.appendChild(perks);
            card.appendChild(perksMeta);
            tierList.appendChild(card);
          });
          updateBenefitsInput();
        }

        /**
         * @function  addTier
         * @purpose   Handles addTier behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function addTier(){
          if (campaignData.tiers.length >= 5) return;
          campaignData.tiers.push({ name: '', amount: '', perks: '' });
          renderTiers();
          updateSaveCloseState();
        }

        /**
         * @function  syncPhotoInput
         * @purpose   Handles syncPhotoInput behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function syncPhotoInput(){
          if (!photoInput) return;
          var dt = new DataTransfer();
          campaignData.photos.forEach(function(file){
            if (file) dt.items.add(file);
          });
          photoInput.files = dt.files;
        }

        /**
         * @function  renderPhotoGrid
         * @purpose   Handles renderPhotoGrid behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function renderPhotoGrid(){
          if (!photoGrid) return;
          photoGrid.innerHTML = '';
          for (var i=0;i<5;i++){
            (function(idx){
              var slot = document.createElement('button');
              slot.type = 'button';
              slot.className = 'kbf-photo-slot';
              var file = campaignData.photos[idx];
              if (file && file._kbfDataUrl) {
                var img = document.createElement('img');
                img.alt = '';
                img.src = file._kbfDataUrl;
                slot.appendChild(img);
                var edit = document.createElement('button');
                edit.type = 'button';
                edit.className = 'kbf-photo-edit';
                edit.innerHTML = '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/></svg>';
                edit.addEventListener('click', function(e){
                  e.preventDefault();
                  e.stopPropagation();
                  if (typeof window.kbfOpenPhotoEditor === 'function') {
                    window.kbfOpenPhotoEditor(file, 'create-redesign', idx);
                  }
                });
                slot.appendChild(edit);
                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'kbf-photo-remove';
                remove.textContent = '\u00D7';
                remove.addEventListener('click', function(e){
                  e.preventDefault();
                  e.stopPropagation();
                  campaignData.photos.splice(idx, 1);
                  syncPhotoInput();
                  renderPhotoGrid();
                  updateSaveCloseState();
                });
                slot.appendChild(remove);
                if (idx === 0) {
                  var cover = document.createElement('span');
                  cover.className = 'kbf-photo-cover';
                  cover.textContent = 'Cover';
                  slot.appendChild(cover);
                }
              } else {
                slot.textContent = idx === 0 ? 'Add cover' : 'Add photo';
              }
              slot.addEventListener('click', function(){
                if (photoInput) photoInput.click();
              });
              photoGrid.appendChild(slot);
            })(i);
          }
        }

        /**
         * @function  handlePhotoFiles
         * @purpose   Handles handlePhotoFiles behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    files: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function handlePhotoFiles(files){
          var incoming = Array.from(files || []);
          if (!incoming.length) return;
          clearError(photoInput || photoGrid);
          var room = Math.max(0, 5 - campaignData.photos.length);
          if (room <= 0) {
            setError(photoInput || photoGrid, 'You can upload up to 5 photos only.');
            if (photoInput) photoInput.value = '';
            return;
          }

          var accepted = [];
          var firstError = '';
          incoming.forEach(function(file){
            if (accepted.length >= room) return;
            var type = String((file && file.type) ? file.type : '').toLowerCase();
            var isImage = /^image\//.test(type);
            if (!isImage) {
              if (!firstError) firstError = 'Only image files are allowed.';
              return;
            }
            if (file && file.size > createPhotoMaxSize) {
              if (!firstError) firstError = 'Each photo must be 5MB or smaller.';
              return;
            }
            accepted.push(file);
          });

          if (!accepted.length) {
            setError(photoInput || photoGrid, firstError || 'Please select valid image files.');
            if (photoInput) photoInput.value = '';
            return;
          }

          accepted.forEach(function(file){
            campaignData.photos.push(file);
            var reader = new FileReader();
            reader.onload = function(e){
              file._kbfDataUrl = e && e.target ? e.target.result : '';
              renderPhotoGrid();
            };
            reader.readAsDataURL(file);
          });
          syncPhotoInput();
          updateSaveCloseState();
          if (firstError) setError(photoInput || photoGrid, firstError);
          if (photoInput) photoInput.value = '';
        }

        /**
         * @function  validateStep
         * @purpose   Handles validateStep behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    step: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function validateStep(step){
          var current = parseInt(step || campaignData.step || 1, 10);
          var valid = true;
          var firstInvalidTarget = null;

          /**
           * @function  invalidate
           * @purpose   Handles invalidate behavior in the dashboard script flow
           * @used-by   [same file references detected]
           * @calls     [none explicitly documented]
           * @params    el: any - parameter; message: any - parameter
           * @returns   void
           * @status    ACTIVE
           */
          function invalidate(el, message){
            valid = false;
            if (!firstInvalidTarget && el) firstInvalidTarget = el;
            setError(el, message);
          }

          if (current === 1) {
            clearError(funderGrid);
            clearError(categoryGrid);
            if (!campaignData.funder_type) invalidate(funderGrid, 'Please choose who you are raising for.');
            if (!campaignData.category) invalidate(categoryGrid, 'Please choose a category.');
          }
          if (current === 2) {
            clearError(titleInput);
            clearError(descInput);
            if (!campaignData.title.trim()) invalidate(titleInput, 'Campaign name is required.');
            if (!campaignData.description.trim()) invalidate(descInput, 'Campaign description is required.');
          }
          if (current === 3) {
            clearError(photoInput || photoGrid);
            if (!campaignData.photos.length) {
              invalidate(photoInput || photoGrid, 'Please upload at least one photo.');
            }
            var tierInvalid = validateCreateTiers();
            if (tierInvalid) {
              valid = false;
              if (!firstInvalidTarget) firstInvalidTarget = tierInvalid;
            }
          }
          if (current === 4) {
            clearError(goalInput);
            clearError(deadlineInput);
            clearError(emailInput);
            clearError(phoneInput);
            clearError(provInput);
            clearError(muniInput);
            clearError(brgyInput);
            clearError(termsInput);
            var goalVal = parseFloat(String(campaignData.goal_amount || '').replace(/,/g, ''));
            if (isNaN(goalVal) || goalVal < 500) invalidate(goalInput, 'Minimum goal amount is 500.');
            if (!campaignData.deadline) invalidate(deadlineInput, 'Please set a deadline.');
            if (!campaignData.email.trim()) invalidate(emailInput, 'Email is required.');
            if (!campaignData.phone.trim()) invalidate(phoneInput, 'Phone is required.');
            if (!campaignData.province.trim()) invalidate(provInput, 'Please select a province.');
            if (!campaignData.municipality.trim()) invalidate(muniInput, 'Please select a municipality.');
            if (!campaignData.barangay.trim()) invalidate(brgyInput, 'Please select a barangay.');
            if (!campaignData.agree_terms) invalidate(termsInput, 'You must accept the terms and refund policy.');
          }

          if (!valid) {
            var panel = form.querySelector('.kbf-create-panel.is-active');
            var scrollTarget = firstInvalidTarget;
            if (!scrollTarget && panel) {
              scrollTarget = panel.querySelector('.kbf-input-error, .kbf-field-error');
            }
            if (scrollTarget && typeof scrollTarget.scrollIntoView === 'function') {
              scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            if (scrollTarget && typeof scrollTarget.focus === 'function') {
              try { scrollTarget.focus({ preventScroll: true }); }
              catch(e) { scrollTarget.focus(); }
            }
          }
          return valid;
        }

        /**
         * @function  updateCampaignData
         * @purpose   Handles updateCampaignData behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function updateCampaignData(){
          campaignData.title = titleInput ? titleInput.value : '';
          campaignData.description = descInput ? descInput.value : '';
          campaignData.goal_amount = goalInput ? (goalInput.dataset.kbfRaw || goalInput.value || '') : '';
          campaignData.deadline = deadlineInput ? deadlineInput.value : '';
          campaignData.email = emailInput ? emailInput.value : '';
          campaignData.phone = phoneInput ? phoneInput.value : '';
          campaignData.province = provInput ? provInput.value : '';
          campaignData.municipality = muniInput ? muniInput.value : '';
          campaignData.barangay = brgyInput ? brgyInput.value : '';
          campaignData.agree_terms = termsInput ? termsInput.checked : false;
          updateSaveCloseState();
        }

        /**
         * @function  renderCreateDeadlineMeta
         * @purpose   Renders a visible current-date highlighter and selected date context for create deadline input.
         * @used-by   [initialization, deadline focus/click/change handlers]
         * @calls     [none]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function renderCreateDeadlineMeta(){
          if (!deadlineTodayMeta || !deadlineInput) return;
          var now = new Date();
          var todayLabel = now.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
          var minRaw = String(deadlineInput.getAttribute('min') || '').trim();
          var minLabel = '';
          if (minRaw) {
            var minDate = new Date(minRaw + 'T00:00:00');
            if (!isNaN(minDate.getTime())) {
              minLabel = minDate.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
            }
          }

          var selectedLabel = '';
          if (deadlineInput.value) {
            var selectedDate = new Date(deadlineInput.value + 'T00:00:00');
            if (!isNaN(selectedDate.getTime())) {
              selectedLabel = selectedDate.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
            }
          }

          var html = '<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-weight:700;">Today: ' + todayLabel + '</span>';
          if (minLabel) {
            html += '<span style="margin-left:8px;color:#64748b;">Earliest: ' + minLabel + '</span>';
          }
          if (selectedLabel) {
            html += '<span style="margin-left:8px;color:#0f172a;font-weight:600;">Selected: ' + selectedLabel + '</span>';
          }
          deadlineTodayMeta.innerHTML = html;
        }

        /**
         * @function  formatCreatePhoneNumber
         * @purpose   Formats create-modal phone input to 09XX XXX XXXX style, matching profile behavior.
         * @used-by   [phone input listeners, applyProfilePhonePrefill]
         * @calls     [none]
         * @params    value: any - raw phone input value
         * @returns   string
         * @status    ACTIVE
         */
        function formatCreatePhoneNumber(value){
          var digits = String(value || '').replace(/\D/g, '').substring(0, 11);
          if (!digits) return '';
          if (digits.charAt(0) !== '0') digits = '0' + digits.substring(0, 10);
          if (digits.length > 1 && digits.charAt(1) !== '9') digits = '09' + digits.substring(2);
          digits = digits.substring(0, 11);
          if (digits.length <= 4) return digits;
          if (digits.length <= 7) return digits.substring(0, 4) + ' ' + digits.substring(4);
          return digits.substring(0, 4) + ' ' + digits.substring(4, 7) + ' ' + digits.substring(7);
        }

        /**
         * @function  applyProfilePhonePrefill
         * @purpose   Seeds create-modal phone field from saved profile phone when no draft/value exists.
         * @used-by   [openCreateModal]
         * @calls     [formatCreatePhoneNumber]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function applyProfilePhonePrefill(){
          if (!phoneInput || !profilePhoneSeed) return;
          var hasPhone = !!(
            String(campaignData.phone || '').trim() ||
            String(phoneInput.value || '').trim()
          );
          if (hasPhone) return;
          var formattedPhone = formatCreatePhoneNumber(profilePhoneSeed);
          if (!formattedPhone) return;
          phoneInput.value = formattedPhone;
          campaignData.phone = formattedPhone;
        }

        /**
         * @function  applyProfileAddressPrefill
         * @purpose   Seeds create-modal address fields from saved profile address when no draft/location is present.
         * @used-by   [openCreateModal]
         * @calls     [window.kbfApplyLocationSelection]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function applyProfileAddressPrefill(){
          if (!profileAddressSeed || !provInput || !muniInput || !brgyInput) return;
          var hasLocation = !!(
            String(campaignData.province || '').trim() ||
            String(campaignData.municipality || '').trim() ||
            String(campaignData.barangay || '').trim() ||
            String(provInput.value || '').trim() ||
            String(muniInput.value || '').trim() ||
            String(brgyInput.value || '').trim()
          );
          if (hasLocation || typeof window.kbfApplyLocationSelection !== 'function') return;

          var parts = String(profileAddressSeed || '').split(',').map(function(p){ return p.trim(); }).filter(Boolean);
          if (!parts.length) return;

          // Seed state immediately so step validation has location context before async option loading finishes.
          campaignData.barangay = parts.length >= 3 ? parts[0] : '';
          campaignData.municipality = parts.length >= 2 ? parts[parts.length >= 3 ? 1 : 0] : '';
          campaignData.province = parts.length >= 3 ? parts.slice(2).join(', ') : (parts.length === 2 ? parts[1] : parts[0]);

          window.kbfApplyLocationSelection(provInput, muniInput, brgyInput, profileAddressSeed);

          var syncCount = 0;
          var syncTimer = setInterval(function(){
            syncCount++;
            if (provInput.value) campaignData.province = provInput.value;
            if (muniInput.value) campaignData.municipality = muniInput.value;
            if (brgyInput.value) campaignData.barangay = brgyInput.value;
            if (syncCount >= 12 || (provInput.value && (muniInput.value || muniInput.disabled))) {
              clearInterval(syncTimer);
            }
          }, 120);
        }

        /**
         * @function  submitCreate
         * @purpose   Handles submitCreate behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function submitCreate(){
          if (!validateStep(4)) return;
          if (typeof window.ajaxurl === 'undefined' || !window.ajaxurl) {
            if (msg) msg.innerHTML = '<div class=\"kbf-alert kbf-alert-error\">Submit failed: ajaxurl is not defined.</div>';
            return;
          }
          if (msg) msg.innerHTML = '';
          if (btnNext) kbfSetBtnLoading(btnNext, true, 'Submitting...');
          if (typeof kbfSetLoadingPage === 'function') kbfSetLoadingPage(true);

          if (funderInput) funderInput.value = campaignData.funder_type || '';
          if (categoryInput) categoryInput.value = campaignData.category || '';
          if (benefitsInput) updateBenefitsInput();
          syncPhotoInput();

          var fd = new FormData(form);
          var expectedPhotos = campaignData.photos.filter(function(file){ return !!file; });
          if (expectedPhotos.length && typeof fd.delete === 'function') {
            fd.delete('photos[]');
            expectedPhotos.forEach(function(file){
              fd.append('photos[]', file, file.name || 'photo.jpg');
            });
          }
          fd.set('benefits', getCreateBenefitsPayload());
          var goalRaw = (goalInput && goalInput.dataset.kbfRaw) ? goalInput.dataset.kbfRaw : (campaignData.goal_amount || '');
          if (goalRaw) fd.set('goal_amount', String(goalRaw).replace(/,/g, ''));

          var parts = [];
          if (campaignData.barangay) parts.push(campaignData.barangay);
          if (campaignData.municipality) parts.push(campaignData.municipality);
          if (campaignData.province) parts.push(campaignData.province);
          if (parts.length) fd.set('location_full', parts.join(', '));

          fd.set('action', 'kbf_create_fund');
          fd.set('nonce', '<?php echo $nonce_create; ?>');

          fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r){
              return r.text().then(function(t){ return { ok: r.ok, status: r.status, text: t }; });
            })
            .then(function(res){
              var json = null;
              try { json = JSON.parse(res.text); } catch(e) {}
              var ok = false;
              if (json) {
                ok = (json.success === true || json.success === 1 || json.success === '1' || json.success === 'true' || json.status === 'success');
                if (!ok && json.data && (json.data.success === true || json.data.success === 1 || json.data.success === '1')) ok = true;
                if (!ok && json.data && json.data.status === 'success') ok = true;
                if (!ok && json.data && json.data.message && !/error|fail|invalid/i.test(String(json.data.message))) ok = true;
              } else if (res.ok) {
                ok = true;
              }
              if (ok) {
                setDraft(null);
                modal.classList.add('is-success');
                if (success) success.classList.add('is-visible');
                var dashUrl = '<?php echo esc_url(add_query_arg('kbf_tab','overview', function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/'))); ?>';
                if (successView) successView.href = dashUrl;
                if (successShare) successShare.href = dashUrl;
              } else if (msg) {
                var message = json && json.data && json.data.message ? json.data.message : 'Submission failed. Please try again.';
                msg.innerHTML = '<div class=\"kbf-alert kbf-alert-error\">' + window.kbfEscapeHtml(message) + '</div>';
              }
            })
            .catch(function(){
              if (msg) msg.innerHTML = '<div class=\"kbf-alert kbf-alert-error\">Submission failed. Please try again.</div>';
            })
            .finally(function(){
              if (btnNext) kbfSetBtnLoading(btnNext, false);
              if (typeof kbfSetLoadingPage === 'function') kbfSetLoadingPage(false);
            });
        }

        if (funderGrid) {
          funderGrid.querySelectorAll('.kbf-choice-card').forEach(function(card){
            card.addEventListener('click', function(){
              campaignData.funder_type = card.getAttribute('data-value') || '';
              if (funderInput) funderInput.value = campaignData.funder_type;
              funderGrid.querySelectorAll('.kbf-choice-card').forEach(function(btn){
                btn.classList.toggle('is-selected', btn === card);
              });
              clearError(funderGrid);
              updateSaveCloseState();
            });
          });
        }

        if (categoryGrid) {
          categoryGrid.querySelectorAll('.kbf-category-card').forEach(function(card){
            card.addEventListener('click', function(){
              campaignData.category = card.getAttribute('data-value') || '';
              if (categoryInput) categoryInput.value = campaignData.category;
              categoryGrid.querySelectorAll('.kbf-category-card').forEach(function(btn){
                btn.classList.toggle('is-selected', btn === card);
              });
              clearError(categoryGrid);
              updateSaveCloseState();
            });
          });
        }

        categoryMore = null;

        [titleInput, descInput].forEach(function(input){
          if (!input) return;
          bindCounter(input);
          input.addEventListener('input', function(){
            updateCampaignData();
            clearError(input);
          });
        });

        if (goalInput) {
          goalInput.addEventListener('input', function(){
            updateCampaignData();
            clearError(goalInput);
          });
        }

        if (deadlineInput) {
          deadlineInput.addEventListener('change', function(){
            updateCampaignData();
            clearError(deadlineInput);
            renderCreateDeadlineMeta();
          });
          deadlineInput.addEventListener('focus', renderCreateDeadlineMeta);
          deadlineInput.addEventListener('click', renderCreateDeadlineMeta);
        }

        if (photoInput) {
          photoInput.addEventListener('change', function(){
            handlePhotoFiles(photoInput.files);
          });
        }

        if (addTierBtn) addTierBtn.addEventListener('click', addTier);

        if (emailInput) emailInput.addEventListener('input', function(){
          updateCampaignData();
          clearError(emailInput);
        });
        if (phoneInput) {
          phoneInput.setAttribute('inputmode', 'numeric');
          phoneInput.setAttribute('maxlength', '13');
          phoneInput.placeholder = '09XX XXX XXXX';

          var onCreatePhoneChange = function(){
            phoneInput.value = formatCreatePhoneNumber(phoneInput.value);
            updateCampaignData();
            clearError(phoneInput);
          };
          phoneInput.addEventListener('input', onCreatePhoneChange);
          phoneInput.addEventListener('change', onCreatePhoneChange);
          phoneInput.addEventListener('blur', onCreatePhoneChange);
          phoneInput.addEventListener('paste', function(){
            setTimeout(onCreatePhoneChange, 0);
          });
        }
        if (provInput) provInput.addEventListener('change', function(){
          updateCampaignData();
          clearError(provInput);
        });
        if (muniInput) muniInput.addEventListener('change', function(){
          updateCampaignData();
          clearError(muniInput);
        });
        if (brgyInput) brgyInput.addEventListener('change', function(){
          updateCampaignData();
          clearError(brgyInput);
        });
        if (termsInput) termsInput.addEventListener('change', function(){
          updateCampaignData();
          clearError(termsInput);
        });

        if (stepButtons.length) {
          stepButtons.forEach(function(btn){
            btn.addEventListener('click', function(){
              var target = parseInt(btn.getAttribute('data-step') || '1', 10);
              if (target <= campaignData.maxStepReached) setStep(target);
            });
          });
        }

        if (btnPrev) {
          btnPrev.addEventListener('click', function(){
            setStep(Math.max(1, campaignData.step - 1));
          });
        }
        if (btnNext) {
          btnNext.addEventListener('click', function(){
            if (campaignData.step < 4) {
              if (!validateStep(campaignData.step)) return;
              setStep(campaignData.step + 1);
              updateCampaignData();
              return;
            }
            submitCreate();
          });
        }

        if (btnSave) {
          btnSave.addEventListener('click', function(){
            if (!hasValue()) return;
            ensurePhotoData().then(function(){
              setDraft(buildDraft());
              if (typeof kbfCloseModal === 'function') kbfCloseModal('kbf-modal-create');
            });
          });
        }

        if (typeof window.kbfInitLocationPicker === 'function' && provInput && muniInput && brgyInput) {
          window.kbfCreateLocPicker = window.kbfInitLocationPicker(provInput, muniInput, brgyInput);
        }

        /**
         * @function  openCreateModal
         * @purpose   Handles openCreateModal behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function openCreateModal(){
          modal.style.display = 'flex';
          requestAnimationFrame(function(){ modal.classList.add('is-open'); });
          modal.classList.remove('is-success');
          var draft = getDraft();
          if (draft) {
            applyDraft(draft);
          } else {
            campaignData.step = 1;
            campaignData.maxStepReached = 1;
            setStep(1);
          }
          updateCampaignData();
          applyProfilePhonePrefill();
          applyProfileAddressPrefill();
          lastSavedHash = draftComparable(draft || buildDraft());
          updateSaveCloseState();
        }

        window.kbfCreateOpen = openCreateModal;
        window.kbfCreateSetStep = setStep;
        window.kbfCreateValidateStep = validateStep;
        window.kbfCreateSubmit = submitCreate;
        window.kbfUpdateSaveCloseState = updateSaveCloseState;
        /**
         * @function  kbfCreateRedesignApplyPhoto
         * @purpose   Handles kbfCreateRedesignApplyPhoto behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    index: any - parameter; nextFile: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfCreateRedesignApplyPhoto = function(index, nextFile){
          if (!nextFile || typeof index !== 'number') return;
          campaignData.photos[index] = nextFile;
          var reader = new FileReader();
          reader.onload = function(e){
            nextFile._kbfDataUrl = e && e.target ? e.target.result : '';
            syncPhotoInput();
            renderPhotoGrid();
            updateSaveCloseState();
          };
          reader.onerror = function(){
            syncPhotoInput();
            renderPhotoGrid();
            updateSaveCloseState();
          };
          reader.readAsDataURL(nextFile);
        };
        /**
         * @function  kbfApplyCreateDraft
         * @purpose   Handles kbfApplyCreateDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    force: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfApplyCreateDraft = function(force){
          var draft = getDraft();
          if (!draft) return false;
          if (!force && hasValue()) return false;
          return applyDraft(draft);
        };
        /**
         * @function  kbfSaveAndCloseCreateDraft
         * @purpose   Handles kbfSaveAndCloseCreateDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfSaveAndCloseCreateDraft = function(){
          if (!hasValue()) return;
          ensurePhotoData().then(function(){
            setDraft(buildDraft());
            if (typeof kbfCloseModal === 'function') kbfCloseModal('kbf-modal-create');
          });
        };
        /**
         * @function  kbfClearCreateDraft
         * @purpose   Handles kbfClearCreateDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfClearCreateDraft = function(){ setDraft(null); };
        /**
         * @function  kbfRequestCloseCreate
         * @purpose   Handles kbfRequestCloseCreate behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfRequestCloseCreate = function(){
          if (typeof kbfCloseModal === 'function') kbfCloseModal('kbf-modal-create');
        };

        renderPhotoGrid();
        renderTiers();
        bindCounter(titleInput);
        bindCounter(descInput);
        renderCreateDeadlineMeta();
        setStep(1);
        updateCampaignData();
      });
    </script>
    <script>
      (function(){
        var btn = document.getElementById('kbf-user-menu-btn');
        var dd = document.getElementById('kbf-user-dropdown');
        if(!btn || !dd) return;
        /**
         * @function  closeMenu
         * @purpose   Handles closeMenu behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function closeMenu(){
          dd.classList.remove('kbf-open');
          btn.setAttribute('aria-expanded','false');
        }
        btn.addEventListener('click', function(e){
          e.stopPropagation();
          var isOpen = dd.classList.toggle('kbf-open');
          btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        document.querySelectorAll('.kbf-nav a, .kbf-mobile-menu a, .kbf-notif-btn, #kbf-user-dropdown a').forEach(function(el){
          el.addEventListener('click', function(){
            closeMenu();
          });
        });
        document.addEventListener('click', function(){ closeMenu(); });
        document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeMenu(); });
      })();
    </script>
    <script>
      (function(){
        var menus = Array.prototype.slice.call(document.querySelectorAll('.kbf-notif-menu'));
        if(!menus.length) return;

        /**
         * @function  menuParts
         * @purpose   Handles menuParts behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    wrap: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function menuParts(wrap){
          if(!wrap) return null;
          var btn = wrap.querySelector('.kbf-notif-btn');
          var dd = wrap.querySelector('.kbf-notif-dropdown');
          if(!btn || !dd) return null;
          return { wrap: wrap, btn: btn, dd: dd };
        }
        var partsList = menus.map(menuParts).filter(function(parts){ return !!parts; });
        if(!partsList.length) return;
        var nonceWrap = partsList[0].wrap;
        /**
         * @function  hydrateNotifTimes
         * @purpose   Handles hydrateNotifTimes behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function hydrateNotifTimes(){
          partsList.forEach(function(parts){
            parts.dd.querySelectorAll('.kbf-notif-item-time[data-notif-time-utc]').forEach(function(el){
              var raw = (el.getAttribute('data-notif-time-utc') || '').trim();
              if(!raw) return;
              // Stored format is "YYYY-MM-DD HH:mm:ss" from server; treat as UTC then render local.
              var iso = raw.replace(' ', 'T') + 'Z';
              var d = new Date(iso);
              if(isNaN(d.getTime())) return;
              el.textContent = d.toLocaleString(undefined, {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
              });
            });
          });
          document.querySelectorAll('.kbf-mobile-notif-item-time[data-notif-time-utc]').forEach(function(el){
            var raw = (el.getAttribute('data-notif-time-utc') || '').trim();
            if(!raw) return;
            var iso = raw.replace(' ', 'T') + 'Z';
            var d = new Date(iso);
            if(isNaN(d.getTime())) return;
            el.textContent = d.toLocaleString(undefined, {
              month: 'short',
              day: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
              hour12: true
            });
          });
        }
        hydrateNotifTimes();
        var marked = false;
        /**
         * @function  closeNotif
         * @purpose   Handles closeNotif behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function closeNotif(){
          partsList.forEach(function(parts){
            parts.dd.classList.remove('kbf-open');
            parts.btn.setAttribute('aria-expanded', 'false');
          });
        }
        /**
         * @function  clearUnreadUI
         * @purpose   Handles clearUnreadUI behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function clearUnreadUI(){
          partsList.forEach(function(parts){
            var badge = parts.wrap.querySelector('.kbf-notif-badge');
            if(badge && badge.parentNode) badge.parentNode.removeChild(badge);
            parts.btn.classList.remove('has-unread');
            parts.dd.querySelectorAll('.kbf-notif-item.is-unread').forEach(function(item){
              item.classList.remove('is-unread');
            });
            var headCount = parts.dd.querySelector('.kbf-notif-head-count');
            if(headCount && headCount.parentNode) headCount.parentNode.removeChild(headCount);
          });
        }
        /**
         * @function  clearAllUI
         * @purpose   Handles clearAllUI behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function clearAllUI(){
          clearUnreadUI();
          partsList.forEach(function(parts){
            parts.dd.querySelectorAll('.kbf-notif-item').forEach(function(item){
              if(item && item.parentNode) item.parentNode.removeChild(item);
            });
            var list = parts.dd.querySelector('.kbf-notif-list');
            if(list && !list.querySelector('.kbf-notif-empty')){
              var empty = document.createElement('div');
              empty.className = 'kbf-notif-empty';
              empty.textContent = 'No notifications yet.';
              list.appendChild(empty);
            }
          });
        }
        /**
         * @function  markReadOnce
         * @purpose   Handles markReadOnce behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function markReadOnce(){
          if(marked) return;
          marked = true;
          var nonce = nonceWrap.getAttribute('data-mark-nonce') || '';
          if(!nonce || typeof ajaxurl === 'undefined') return;
          var fd = new FormData();
          fd.append('action', 'kbf_mark_notifications_read');
          fd.append('nonce', nonce);
          fetch(ajaxurl, { method:'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(j){
              if(!j || !j.success) return;
              clearUnreadUI();
            })
            .catch(function(){});
        }
        /**
         * @function  markSingleRead
         * @purpose   Handles markSingleRead behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    notifId: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function markSingleRead(notifId){
          var nonce = nonceWrap.getAttribute('data-single-nonce') || '';
          if(!nonce || !notifId || typeof ajaxurl === 'undefined') return;
          var fd = new FormData();
          fd.append('action', 'kbf_mark_notification_read');
          fd.append('nonce', nonce);
          fd.append('notification_id', notifId);
          fetch(ajaxurl, { method:'POST', body: fd })
            .then(function(r){ return r.json(); })
            .catch(function(){});
        }
        partsList.forEach(function(parts){
          parts.btn.addEventListener('click', function(e){
            if (window.innerWidth <= 900) return;
            e.preventDefault();
            e.stopPropagation();
            var shouldOpen = !parts.dd.classList.contains('kbf-open');
            closeNotif();
            if(shouldOpen){
              parts.dd.classList.add('kbf-open');
              parts.btn.setAttribute('aria-expanded', 'true');
              markReadOnce();
            }
          });
        });
        document.querySelectorAll('.kbf-nav a, .kbf-mobile-menu a, #kbf-user-menu-btn, .kbf-dashboard-user').forEach(function(el){
          el.addEventListener('click', function(){
            closeNotif();
          });
        });
        partsList.forEach(function(parts){
          parts.dd.addEventListener('click', function(e){
            var clearAll = e.target.closest('.kbf-notif-view-all[data-notif-action="clear-all"]');
            if (clearAll) {
              e.preventDefault();
              clearAllUI();
              var nonce = nonceWrap.getAttribute('data-mark-nonce') || '';
              if(nonce && typeof ajaxurl !== 'undefined'){
                var fd = new FormData();
                fd.append('action', 'kbf_clear_notifications');
                fd.append('nonce', nonce);
                fetch(ajaxurl, { method:'POST', body: fd })
                  .then(function(r){ return r.json(); })
                  .catch(function(){});
              }
              return;
            }
            var item = e.target.closest('.kbf-notif-item[data-notification-id]');
            if(!item) return;
            var notifId = item.getAttribute('data-notification-id') || '';
            if(notifId) markSingleRead(notifId);
          });
        });
        var mobileNotifMenu = document.getElementById('kbf-mobile-notif-menu');
        if (mobileNotifMenu) {
          mobileNotifMenu.addEventListener('click', function(e){
            var clearAll = e.target.closest('.kbf-mobile-notif-clear[data-notif-action="clear-all"]');
            if (clearAll) {
              e.preventDefault();
              clearAllUI();
              mobileNotifMenu.querySelectorAll('.kbf-mobile-notif-item').forEach(function(item){
                if(item && item.parentNode) item.parentNode.removeChild(item);
              });
              var mobileList = mobileNotifMenu.querySelector('.kbf-mobile-notif-list');
              if (mobileList && !mobileList.querySelector('.kbf-mobile-notif-empty')) {
                var empty = document.createElement('div');
                empty.className = 'kbf-mobile-notif-empty';
                empty.textContent = 'No notifications yet.';
                mobileList.appendChild(empty);
              }
              var nonce = nonceWrap.getAttribute('data-mark-nonce') || '';
              if(nonce && typeof ajaxurl !== 'undefined'){
                var fd = new FormData();
                fd.append('action', 'kbf_clear_notifications');
                fd.append('nonce', nonce);
                fetch(ajaxurl, { method:'POST', body: fd })
                  .then(function(r){ return r.json(); })
                  .catch(function(){});
              }
              return;
            }
            var item = e.target.closest('.kbf-mobile-notif-item[data-notification-id]');
            if (!item) return;
            var notifId = item.getAttribute('data-notification-id') || '';
            if(notifId) markSingleRead(notifId);
          });
        }
        document.addEventListener('click', function(e){
          for(var i = 0; i < partsList.length; i++){
            if(partsList[i].wrap.contains(e.target)) return;
          }
          closeNotif();
        });
        document.addEventListener('keydown', function(e){
          if(e.key === 'Escape') closeNotif();
        });
      })();
    </script>
    <script>
      // preload removed
      (function(){
        var topbar = document.querySelector('.kbf-topbar');
        if (!topbar) return;
        /**
         * @function  onScroll
         * @purpose   Handles onScroll behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function onScroll(){
          if (window.scrollY > 10) topbar.classList.add('kbf-topbar-scrolled');
          else topbar.classList.remove('kbf-topbar-scrolled');
        }
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
      })();

      /**
       * @function  kbfToggleMobileMenu
       * @purpose   Handles kbfToggleMobileMenu behavior in the dashboard script flow
       * @used-by   [no confirmed caller in this file]
       * @calls     [none explicitly documented]
       * @params    none
       * @returns   void
       * @status    NEEDS REVIEW
       */
      function kbfToggleMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var notifMenu = document.getElementById('kbf-mobile-notif-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        var icon = document.getElementById('kbf-mobile-menu-icon');
        if (!menu || !overlay) return;
        if (notifMenu) notifMenu.classList.remove('kbf-menu-open');
        menu.classList.toggle('kbf-menu-open');
        overlay.classList.toggle('kbf-overlay-open');
        if (icon) {
          var isOpen = menu.classList.contains('kbf-menu-open');
          var openCls = (icon.getAttribute('data-open') || 'ph ph-x').split(' ');
          var closeCls = (icon.getAttribute('data-close') || 'ph ph-list').split(' ');
          icon.classList.remove.apply(icon.classList, openCls);
          icon.classList.remove.apply(icon.classList, closeCls);
          icon.classList.add.apply(icon.classList, isOpen ? openCls : closeCls);
        }
      }
      /**
       * @function  kbfCloseMobileMenu
       * @purpose   Handles kbfCloseMobileMenu behavior in the dashboard script flow
       * @used-by   [same file references detected]
       * @calls     [none explicitly documented]
       * @params    none
       * @returns   void
       * @status    ACTIVE
       */
      function kbfCloseMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        var notifMenu = document.getElementById('kbf-mobile-notif-menu');
        var icon = document.getElementById('kbf-mobile-menu-icon');
        if (menu) menu.classList.remove('kbf-menu-open');
        if (overlay && (!notifMenu || !notifMenu.classList.contains('kbf-menu-open'))) overlay.classList.remove('kbf-overlay-open');
        if (icon) {
          var closeCls = (icon.getAttribute('data-close') || 'ph ph-list').split(' ');
          var openCls = (icon.getAttribute('data-open') || 'ph ph-x').split(' ');
          icon.classList.remove.apply(icon.classList, openCls);
          icon.classList.remove.apply(icon.classList, closeCls);
          icon.classList.add.apply(icon.classList, closeCls);
        }
      }
      function kbfToggleMobileNotifMenu(e){
        if (window.innerWidth > 900) return;
        if (e) {
          e.preventDefault();
          e.stopPropagation();
        }
        var menu = document.getElementById('kbf-mobile-notif-menu');
        var mobileMenu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (!menu || !overlay) return;
        if (mobileMenu) mobileMenu.classList.remove('kbf-menu-open');
        var icon = document.getElementById('kbf-mobile-menu-icon');
        if (icon) {
          var closeCls = (icon.getAttribute('data-close') || 'ph ph-list').split(' ');
          var openCls = (icon.getAttribute('data-open') || 'ph ph-x').split(' ');
          icon.classList.remove.apply(icon.classList, openCls);
          icon.classList.remove.apply(icon.classList, closeCls);
          icon.classList.add.apply(icon.classList, closeCls);
        }
        menu.classList.toggle('kbf-menu-open');
        overlay.classList.toggle('kbf-overlay-open');
      }
      function kbfCloseMobileNotifMenu(){
        var menu = document.getElementById('kbf-mobile-notif-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        var mobileMenu = document.getElementById('kbf-mobile-menu');
        if (menu) menu.classList.remove('kbf-menu-open');
        if (overlay && (!mobileMenu || !mobileMenu.classList.contains('kbf-menu-open'))) overlay.classList.remove('kbf-overlay-open');
      }
      window.kbfToggleMobileNotifMenu = kbfToggleMobileNotifMenu;
      window.kbfCloseMobileNotifMenu = kbfCloseMobileNotifMenu;
      window.addEventListener('resize', function(){
        if (window.innerWidth > 900) {
          kbfCloseMobileMenu();
          kbfCloseMobileNotifMenu();
        }
      });
      document.addEventListener('click', function(e){
        var menu = document.getElementById('kbf-mobile-menu');
        var notifMenu = document.getElementById('kbf-mobile-notif-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (!menu || !overlay) return;
        if (overlay.classList.contains('kbf-overlay-open') && e.target === overlay) {
          kbfCloseMobileMenu();
          if (notifMenu) kbfCloseMobileNotifMenu();
        }
      });

      (function(){
        if (window.kbfIsLoggedIn) return;
        document.querySelectorAll('.kbf-auth-required').forEach(function(link){
          link.addEventListener('click', function(e){
            e.preventDefault();
            var label = link.getAttribute('data-auth-tab') || 'this section';
            if (window.kbfOpenAuthModal) window.kbfOpenAuthModal('Sign in to access ' + label + '.');
            if (typeof kbfCloseMobileMenu === 'function') kbfCloseMobileMenu();
          });
        });
      })();

    /**
     * @function  kbfCloseModal
     * @purpose   Handles kbfCloseModal behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    id: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfCloseModal(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('is-open');
        setTimeout(function(){
            el.style.display = 'none';
            var hasOpenModal = !!document.querySelector('.kbf-modal-overlay.is-open');
            if (!hasOpenModal) {
                document.documentElement.classList.remove('kbf-modal-lock');
                document.body.classList.remove('kbf-modal-lock');
            }
        }, 220);
    }
    /**
     * @function  kbfOpenModal
     * @purpose   Handles kbfOpenModal behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    id: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfOpenModal(id)  {
        
        var el = document.getElementById(id);
        if (!el) return;
        document.documentElement.classList.add('kbf-modal-lock');
        document.body.classList.add('kbf-modal-lock');
        el.style.display = 'flex';
        requestAnimationFrame(function(){ el.classList.add('is-open'); });
        if (id === 'kbf-modal-create') {
            if (typeof window.kbfCreateOpen === 'function') {
                window.kbfCreateOpen();
                return;
            }
            kbfSetCreateStep(1);
            if (window.kbfApplyCreateDraft) window.kbfApplyCreateDraft(true);
            if (typeof window.kbfUpdateSaveCloseState === 'function') window.kbfUpdateSaveCloseState();
        }
    }
    window.kbfOpenModal = kbfOpenModal;
    window.kbfCloseModal = kbfCloseModal;

    /**
     * @function  kbfSetCreateStep
     * @purpose   Handles kbfSetCreateStep behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    step: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSetCreateStep(step) {
        if (typeof window.kbfCreateSetStep === 'function') {
            window.kbfCreateSetStep(step);
            return;
        }
        var form = document.getElementById('kbf-create-fund-form');
        if (!form) return;
        var n = parseInt(step || '1', 10);
        if (isNaN(n)) n = 1;
        n = Math.min(3, Math.max(1, n));
        var steps = form.querySelectorAll('.kbf-step');
        for (var i=0;i<steps.length;i++) {
            var s = steps[i];
            var sStep = parseInt(s.getAttribute('data-step') || '0', 10);
            if (sStep === n) s.classList.add('is-active');
            else s.classList.remove('is-active');
        }
        var panels = form.querySelectorAll('.kbf-step-content');
        for (var j=0;j<panels.length;j++) {
            var p = panels[j];
            var pStep = parseInt(p.getAttribute('data-step') || '0', 10);
            if (pStep === n) p.classList.add('is-active');
            else p.classList.remove('is-active');
        }
        var modal = document.getElementById('kbf-modal-create');
        if (modal) {
            var body = modal.querySelector('.kbf-modal-body');
          if (body) {
            if (typeof body.scrollTo === 'function') body.scrollTo({ top: 0, behavior: 'smooth' });
            else body.scrollTop = 0;
          }
        }
        var activePanel = form.querySelector('.kbf-step-content.is-active');
        if (activePanel) {
          if (typeof activePanel.scrollTo === 'function') activePanel.scrollTo({ top: 0, behavior: 'smooth' });
          else activePanel.scrollTop = 0;
        }
        var prev = document.getElementById('kbf-create-prev');
        var next = document.getElementById('kbf-create-next');
        var submit = document.getElementById('kbf-create-submit');
        if (prev) {
            prev.dataset.step = String(n);
            prev.disabled = n === 1;
        }
        if (next) {
            next.dataset.step = String(n);
            next.style.display = n === 3 ? 'none' : '';
        }
        if (submit) {
            submit.style.display = n === 3 ? '' : 'none';
        }
    }
    window.kbfSetCreateStep = kbfSetCreateStep;

    /**
     * @function  kbfSetEditStep
     * @purpose   Handles kbfSetEditStep behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    step: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSetEditStep(step) {
        var form = document.getElementById('kbf-edit-fund-form');
        if (!form) return;
        var n = parseInt(step || '1', 10);
        if (isNaN(n)) n = 1;
        n = Math.min(3, Math.max(1, n));
        var steps = form.querySelectorAll('.kbf-step');
        for (var i=0;i<steps.length;i++) {
            var s = steps[i];
            var sStep = parseInt(s.getAttribute('data-step') || '0', 10);
            if (sStep === n) s.classList.add('is-active');
            else s.classList.remove('is-active');
        }
        var panels = form.querySelectorAll('.kbf-step-content');
        for (var j=0;j<panels.length;j++) {
            var p = panels[j];
            var pStep = parseInt(p.getAttribute('data-step') || '0', 10);
            if (pStep === n) p.classList.add('is-active');
            else p.classList.remove('is-active');
        }
        var modal = document.getElementById('kbf-modal-edit');
        if (modal) {
          var body = modal.querySelector('.kbf-modal-body');
          if (body) {
            if (typeof body.scrollTo === 'function') body.scrollTo({ top: 0, behavior: 'smooth' });
            else body.scrollTop = 0;
          }
        }
        var activePanel = form.querySelector('.kbf-step-content.is-active');
        if (activePanel) {
          if (typeof activePanel.scrollTo === 'function') activePanel.scrollTo({ top: 0, behavior: 'smooth' });
          else activePanel.scrollTop = 0;
        }
        var prev = document.getElementById('kbf-edit-prev');
        var next = document.getElementById('kbf-edit-next');
        var submit = document.getElementById('kbf-edit-submit');
        if (prev) {
            prev.dataset.step = String(n);
            prev.disabled = n === 1;
        }
        if (next) {
            next.dataset.step = String(n);
            next.style.display = n === 3 ? 'none' : '';
        }
        if (submit) {
            submit.style.display = n === 3 ? '' : 'none';
        }
    }
    window.kbfSetEditStep = kbfSetEditStep;

    var kbfPsgcData = null;
    var kbfPsgcLoading = false;
    var kbfPsgcQueue = [];
    /**
     * @function  kbfTitleCase
     * @purpose   Handles kbfTitleCase behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    str: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfTitleCase(str){
        return String(str).toLowerCase().replace(/\b\w/g,function(c){return c.toUpperCase();});
    }
    /**
     * @function  kbfSetMuniOptions
     * @purpose   Handles kbfSetMuniOptions behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    muniEl: any - parameter; list: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSetMuniOptions(muniEl, list){
        if (!muniEl) return;
        muniEl.innerHTML = '<option value="">Select Municipality</option>';
        for (var i=0;i<list.length;i++){
            var opt = document.createElement('option');
            opt.value = list[i].label;
            opt.textContent = list[i].label;
            muniEl.appendChild(opt);
        }
        if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(muniEl);
    }
    /**
     * @function  kbfSetBrgyOptions
     * @purpose   Handles kbfSetBrgyOptions behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    brgyEl: any - parameter; list: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSetBrgyOptions(brgyEl, list){
        if (!brgyEl) return;
        brgyEl.innerHTML = '<option value="">Select Barangay</option>';
        for (var i=0;i<list.length;i++){
            var opt = document.createElement('option');
            opt.value = list[i];
            opt.textContent = list[i];
            brgyEl.appendChild(opt);
        }
        if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(brgyEl);
    }
    /**
     * @function  kbfBuildMunicipalities
     * @purpose   Handles kbfBuildMunicipalities behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    provinceUpper: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfBuildMunicipalities(provinceUpper){
        var out = [];
        if (!kbfPsgcData) return out;
        for (var regionKey in kbfPsgcData){
            if (!kbfPsgcData.hasOwnProperty(regionKey)) continue;
            var provList = kbfPsgcData[regionKey].province_list || {};
            if (provList[provinceUpper]) {
                var munList = provList[provinceUpper].municipality_list || [];
                for (var i=0;i<munList.length;i++){
                    var obj = munList[i];
                    for (var muniName in obj){
                        if (obj.hasOwnProperty(muniName)){
                            var barangays = obj[muniName].barangay_list || [];
                            var brgyList = [];
                            for (var b=0;b<barangays.length;b++){
                                brgyList.push(kbfTitleCase(barangays[b]));
                            }
                            out.push({ key: muniName, label: kbfTitleCase(muniName), barangays: brgyList });
                        }
                    }
                }
                break;
            }
        }
        return out;
    }
    /**
     * @function  kbfEnsurePsgc
     * @purpose   Handles kbfEnsurePsgc behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    cb: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfEnsurePsgc(cb){
        if (typeof cb !== 'function') cb = function(){};
        if (kbfPsgcData){ cb(); return; }
        if (kbfPsgcLoading) { kbfPsgcQueue.push(cb); return; }
        kbfPsgcLoading = true;
        fetch('<?php echo esc_url(BNTM_KBF_URL . 'data/psgc_2016.json'); ?>')
          .then(function(r){ return r.json(); })
          .then(function(j){ kbfPsgcData = j; })
          .catch(function(){ kbfPsgcData = null; })
          .finally(function(){
              kbfPsgcLoading = false;
              var queue = kbfPsgcQueue.slice();
              kbfPsgcQueue = [];
              queue.push(cb);
              for (var i=0;i<queue.length;i++){
                  try { queue[i](); } catch(e){}
              }
          });
    }
    /**
     * @function  kbfInitLocationPicker
     * @purpose   Handles kbfInitLocationPicker behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    provinceEl: any - parameter; muniEl: any - parameter; brgyEl: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfInitLocationPicker(provinceEl, muniEl, brgyEl){
        if (!provinceEl || !muniEl || !brgyEl) return;
        var muniData = [];
        /**
         * @function  handleProvinceChange
         * @purpose   Handles handleProvinceChange behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function handleProvinceChange(){
            if (window._kbf_applying_location) return;
            var val = provinceEl.value || '';
            if (!val){
                muniEl.disabled = true;
                brgyEl.disabled = true;
                kbfSetMuniOptions(muniEl, []);
                kbfSetBrgyOptions(brgyEl, []);
                if (typeof window.kbfRefreshSelect === 'function') {
                    window.kbfRefreshSelect(muniEl);
                    window.kbfRefreshSelect(brgyEl);
                }
                return;
            }
            muniEl.disabled = true;
            brgyEl.disabled = true;
            kbfSetMuniOptions(muniEl, []);
            kbfSetBrgyOptions(brgyEl, []);
            if (typeof window.kbfRefreshSelect === 'function') {
                window.kbfRefreshSelect(muniEl);
                window.kbfRefreshSelect(brgyEl);
            }
            kbfEnsurePsgc(function(){
                if (window._kbf_applying_location) return;
                muniData = kbfBuildMunicipalities(String(val).toUpperCase());
                kbfSetMuniOptions(muniEl, muniData);
                muniEl.disabled = muniData.length === 0;
                if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(muniEl);
            });
        }
        /**
         * @function  handleMunicipalityChange
         * @purpose   Handles handleMunicipalityChange behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function handleMunicipalityChange(){
            if (window._kbf_applying_location) return;
            var val = muniEl.value || '';
            if (!val){
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
                if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(brgyEl);
                return;
            }
            var upperVal = String(val).toUpperCase();
            var found = null;
            for (var i=0;i<muniData.length;i++){
                if (muniData[i].key === upperVal){
                    found = muniData[i];
                    break;
                }
            }
            if (!found){
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
                if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(brgyEl);
                return;
            }
            kbfSetBrgyOptions(brgyEl, found.barangays);
            brgyEl.disabled = found.barangays.length === 0;
            if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(brgyEl);
        }
        provinceEl.addEventListener('change', handleProvinceChange);
        muniEl.addEventListener('change', handleMunicipalityChange);
        handleProvinceChange();
        return { handleProvinceChange: handleProvinceChange, handleMunicipalityChange: handleMunicipalityChange };
    }
    /**
     * @function  kbfApplyLocationSelection
     * @purpose   Handles kbfApplyLocationSelection behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    provinceEl: any - parameter; muniEl: any - parameter; brgyEl: any - parameter; loc: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfApplyLocationSelection(provinceEl, muniEl, brgyEl, loc){
        if (!provinceEl || !muniEl || !brgyEl) return;
        var parts = String(loc || '').split(',').map(function(p){ return p.trim(); }).filter(Boolean);
        var province = '';
        var municipality = '';
        var barangay = '';

        // Flag to prevent kbfInitLocationPicker change handlers from interfering.
        window._kbf_applying_location = true;

        /**
         * @function  findOptionMatch
         * @purpose   Handles findOptionMatch behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    el: any - parameter; value: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function findOptionMatch(el, value){
            if (!el || !value) return '';
            var target = String(value).toLowerCase();
            for (var i=0;i<el.options.length;i++){
                var optVal = String(el.options[i].value || '');
                if (!optVal) continue;
                if (optVal.toLowerCase() === target) return optVal;
            }
            return '';
        }

        // Detect province by matching option values first.
        var provinceIdx = -1;
        for (var p=0;p<parts.length;p++){
            if (findOptionMatch(provinceEl, parts[p])) {
                provinceIdx = p;
                break;
            }
        }
        if (provinceIdx >= 0) {
            province = findOptionMatch(provinceEl, parts[provinceIdx]);
            parts.splice(provinceIdx, 1);
        } else if (parts.length >= 3) {
            province = findOptionMatch(provinceEl, parts[parts.length - 1]) || parts[parts.length - 1];
            parts = parts.slice(0, -1);
        } else if (parts.length === 2) {
            var maybeProv = findOptionMatch(provinceEl, parts[1]);
            if (maybeProv) {
                province = maybeProv;
                parts = [parts[0]];
            }
        }

        provinceEl.value = province;
        if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(provinceEl);
        if (!province) {
            muniEl.disabled = true; brgyEl.disabled = true;
            kbfSetMuniOptions(muniEl, []); kbfSetBrgyOptions(brgyEl, []);
            if (typeof window.kbfRefreshSelect === 'function') {
                window.kbfRefreshSelect(muniEl);
                window.kbfRefreshSelect(brgyEl);
            }
            window._kbf_applying_location = false;
            return;
        }

        kbfEnsurePsgc(function(){
            var muniData = kbfBuildMunicipalities(String(province).toUpperCase());
            kbfSetMuniOptions(muniEl, muniData);
            muniEl.disabled = muniData.length === 0;
            if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(muniEl);

            if (parts.length) {
                // Try match municipality from remaining parts.
                var muniMatch = '';
                for (var i=0;i<parts.length;i++){
                    var candidate = String(parts[i] || '').trim();
                    if (!candidate) continue;
                    for (var m=0;m<muniData.length;m++){
                        if (String(muniData[m].label).toLowerCase() === candidate.toLowerCase()){
                            muniMatch = muniData[m].label;
                            parts.splice(i,1);
                            break;
                        }
                    }
                    if (muniMatch) break;
                }
                if (!muniMatch && parts.length >= 2) {
                    muniMatch = parts[parts.length - 1];
                    parts = parts.slice(0, -1);
                }
                if (muniMatch) municipality = muniMatch;
            }

            // If municipality is still missing but we have a barangay candidate,
            // derive municipality by searching PSGC barangay lists.
            if (!municipality && parts.length === 1) {
                var brgyCandidate = String(parts[0] || '').trim();
                if (brgyCandidate) {
                    var brgyLower = brgyCandidate.toLowerCase();
                    for (var mi=0; mi<muniData.length; mi++){
                        var bList = muniData[mi].barangays || [];
                        for (var bi=0; bi<bList.length; bi++){
                            if (String(bList[bi]).toLowerCase() === brgyLower){
                                municipality = muniData[mi].label;
                                break;
                            }
                        }
                        if (municipality) break;
                    }
                    if (municipality) {
                        barangay = brgyCandidate;
                        parts = [];
                    }
                }
            }

            if (municipality) {
                muniEl.value = municipality;
                if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(muniEl);
            }
            var upperVal = String(muniEl.value || '').toUpperCase();
            var found = null;
            for (var i=0;i<muniData.length;i++){
                if (muniData[i].key === upperVal){
                    found = muniData[i];
                    break;
                }
            }
            if (found){
                kbfSetBrgyOptions(brgyEl, found.barangays);
                brgyEl.disabled = found.barangays.length === 0;
                if (!barangay && parts.length) barangay = parts[0];
                if (barangay) {
                    brgyEl.value = barangay;
                    if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(brgyEl);
                }
            } else {
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
                if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(brgyEl);
            }
            window._kbf_applying_location = false;
        });
    }
        (function(){
        var prev = document.getElementById('kbf-create-prev');
        var next = document.getElementById('kbf-create-next');
        var submit = document.getElementById('kbf-create-submit');
        var createForm = document.getElementById('kbf-create-fund-form');
        var isCreateRedesign = createForm && createForm.querySelector('.kbf-create-panel');
        var editPrev = document.getElementById('kbf-edit-prev');
        var editNext = document.getElementById('kbf-edit-next');
        var editSubmit = document.getElementById('kbf-edit-submit');
        var photoInput = document.getElementById('kbf-create-photos');
        var photoWrap = document.getElementById('kbf-create-photo-previews');
        var kbfCreateFiles = [];
        if (!isCreateRedesign) {
            if (prev) prev.addEventListener('click', function(){
                var step = parseInt(prev.dataset.step || '1', 10);
                kbfSetCreateStep(Math.max(1, step - 1));
            });
            if (next) next.addEventListener('click', function(){
                var step = parseInt(next.dataset.step || '1', 10);
                if (!kbfValidateCreateStep(step)) return;
                kbfSetCreateStep(Math.min(3, step + 1));
            });
            if (submit) submit.addEventListener('click', function(){
                if (!kbfValidateCreateStep(3)) return;
                kbfSetLoadingPage(true);
                kbfSetBtnLoading(submit, true, 'Submitting...');
                kbfSubmitCreate();
            });
        }
        if (editPrev) editPrev.addEventListener('click', function(){
            var step = parseInt(editPrev.dataset.step || '1', 10);
            kbfSetEditStep(Math.max(1, step - 1));
        });
        if (editNext) editNext.addEventListener('click', function(){
            var step = parseInt(editNext.dataset.step || '1', 10);
            if (!kbfValidateEditStep(step)) return;
            kbfSetEditStep(Math.min(3, step + 1));
        });
        if (editSubmit) editSubmit.addEventListener('click', function(){
            if (!kbfValidateEditStep(3)) return;
            kbfSubmitEdit();
        });
        /**
         * @function  kbfSyncCreateFiles
         * @purpose   Handles kbfSyncCreateFiles behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfSyncCreateFiles(){
            if (!photoInput) return;
            var dt = new DataTransfer();
            kbfCreateFiles.forEach(function(f){ dt.items.add(f); });
            photoInput.files = dt.files;
        }
        /**
         * @function  kbfRenderCreateThumbs
         * @purpose   Handles kbfRenderCreateThumbs behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfRenderCreateThumbs(){
            if (!photoWrap) return;
            photoWrap.innerHTML = '';
            kbfCreateFiles.forEach(function(file, idx){
                if (!file.type || file.type.indexOf('image/') !== 0) return;
                /**
                 * @function  buildThumb
                 * @purpose   Handles buildThumb behavior in the dashboard script flow
                 * @used-by   [same file references detected]
                 * @calls     [none explicitly documented]
                 * @params    src: any - parameter
                 * @returns   void
                 * @status    ACTIVE
                 */
                function buildThumb(src){
                    if (!src) return;
                    var thumb = document.createElement('div');
                    thumb.className = 'kbf-photo-thumb';
                    thumb.setAttribute('data-index', String(idx));
                    var img = document.createElement('img');
                    img.alt = '';
                    img.src = src;
                    var editBtn = document.createElement('button');
                    editBtn.type = 'button';
                    editBtn.className = 'kbf-photo-edit';
                    editBtn.setAttribute('aria-label', 'Edit photo');
                    editBtn.innerHTML = '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/></svg>';
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'kbf-photo-remove';
                    btn.innerHTML = '&times;';
                    btn.addEventListener('click', function(){
                        kbfCreateFiles.splice(idx, 1);
                        kbfSyncCreateFiles();
                        kbfRenderCreateThumbs();
                    });
                    editBtn.addEventListener('click', function(e){
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof kbfOpenPhotoEditor === 'function') {
                            kbfOpenPhotoEditor(file, 'create', idx);
                        }
                    });
                    thumb.appendChild(img);
                    thumb.appendChild(editBtn);
                    thumb.appendChild(btn);
                    photoWrap.appendChild(thumb);
                }
                if (file._kbfDataUrl) {
                    buildThumb(file._kbfDataUrl);
                    return;
                }
                var reader = new FileReader();
                reader.onload = function(e){
                    file._kbfDataUrl = e && e.target ? e.target.result : '';
                    buildThumb(file._kbfDataUrl);
                };
                reader.readAsDataURL(file);
            });
            if (kbfCreateFiles.length < 5) {
                var addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'kbf-photo-add';
                addBtn.setAttribute('aria-label', 'Add photos');
                addBtn.innerHTML = '+';
                photoWrap.appendChild(addBtn);
            }
            if (photoInput) {
                photoInput.disabled = (kbfCreateFiles.length >= 5);
            }
        }
        if (photoWrap) {}

        var editPhotoInput = document.getElementById('kbf-edit-photos');
        var editPhotoWrap = document.getElementById('kbf-edit-photo-previews');
        var kbfEditFiles = [];
        var kbfEditExistingUrls = [];
        var kbfEditRemovedUrls = [];
        var EDIT_MAX_PHOTO_BYTES = 5 * 1024 * 1024; // < 5MB only
        function kbfSetEditPhotoError(message){
            if (!editPhotoInput) return;
            var group = editPhotoInput.closest('.kbf-form-group');
            if (!group) return;
            var err = group.querySelector('.kbf-field-error');
            if (!err) return;
            err.textContent = message || '';
            err.style.display = message ? 'block' : '';
        }
        /**
         * @function  kbfSyncEditFiles
         * @purpose   Handles kbfSyncEditFiles behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfSyncEditFiles(){
            if (!editPhotoInput) return;
            var dt = new DataTransfer();
            kbfEditFiles.forEach(function(f){ dt.items.add(f); });
            editPhotoInput.files = dt.files;
        }
        /**
         * @function  kbfRenderEditThumbs
         * @purpose   Handles kbfRenderEditThumbs behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfRenderEditThumbs(){
            if (!editPhotoWrap) return;
            editPhotoWrap.innerHTML = '';
            var existingCount = 0;
            kbfEditExistingUrls.forEach(function(src, idx){
                if (!src) return;
                var thumb = document.createElement('div');
                thumb.className = 'kbf-photo-thumb kbf-photo-thumb-existing kbf-photo-slot';
                var img = document.createElement('img');
                img.alt = '';
                img.src = src;
                thumb.appendChild(img);
                var editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'kbf-photo-edit';
                editBtn.setAttribute('aria-label', 'Edit photo');
                editBtn.innerHTML = '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/></svg>';
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'kbf-photo-remove';
                btn.innerHTML = '&times;';
                btn.addEventListener('click', function(){
                    kbfEditRemovedUrls.push(src);
                    kbfEditExistingUrls.splice(idx, 1);
                    var removedInput = document.getElementById('kbf-edit-removed-photos');
                    if (removedInput) removedInput.value = JSON.stringify(kbfEditRemovedUrls);
                    kbfRenderEditThumbs();
                });
                editBtn.addEventListener('click', function(e){
                    e.preventDefault();
                    if (!window.kbfOpenPhotoEditor) return;
                    fetch(src)
                    .then(function(r){ return r.blob(); })
                    .then(function(blob){
                        var ext = (blob.type || 'image/jpeg').split('/')[1] || 'jpg';
                        var file = new File([blob], 'edit-' + Date.now() + '.' + ext, { type: blob.type || 'image/jpeg' });
                        var maxNew = Math.max(0, 5 - kbfEditExistingUrls.length);
                        if (kbfEditFiles.length >= maxNew) return;
                        kbfEditRemovedUrls.push(src);
                        var removedInput = document.getElementById('kbf-edit-removed-photos');
                        if (removedInput) removedInput.value = JSON.stringify(kbfEditRemovedUrls);
                        kbfEditExistingUrls.splice(idx, 1);
                        kbfEditFiles.push(file);
                        kbfSyncEditFiles();
                        kbfRenderEditThumbs();
                        kbfOpenPhotoEditor(file, 'edit', kbfEditFiles.length - 1);
                    })
                    .catch(function(){});
                });
                thumb.appendChild(editBtn);
                thumb.appendChild(btn);
                editPhotoWrap.appendChild(thumb);
                existingCount++;
            });
            kbfEditFiles.forEach(function(file, idx){
                if (!file.type || file.type.indexOf('image/') !== 0) return;
                var reader = new FileReader();
                reader.onload = function(e){
                    var thumb = document.createElement('div');
                    thumb.className = 'kbf-photo-thumb kbf-photo-slot';
                    thumb.setAttribute('data-index', String(idx));
                    var img = document.createElement('img');
                    img.alt = '';
                    img.src = e.target.result;
                    var editBtn = document.createElement('button');
                    editBtn.type = 'button';
                    editBtn.className = 'kbf-photo-edit';
                    editBtn.setAttribute('aria-label', 'Edit photo');
                    editBtn.innerHTML = '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/></svg>';
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'kbf-photo-remove';
                    btn.innerHTML = '&times;';
                    btn.addEventListener('click', function(){
                        kbfEditFiles.splice(idx, 1);
                        kbfSyncEditFiles();
                        kbfRenderEditThumbs();
                    });
                    editBtn.addEventListener('click', function(e){
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof kbfOpenPhotoEditor === 'function') {
                            kbfOpenPhotoEditor(file, 'edit', idx);
                        }
                    });
                    thumb.appendChild(img);
                    thumb.appendChild(editBtn);
                    thumb.appendChild(btn);
                    editPhotoWrap.appendChild(thumb);
                };
                reader.readAsDataURL(file);
            });
            var totalCount = existingCount + kbfEditFiles.length;
            if (totalCount < 5) {
                for (var s = totalCount; s < 5; s++) {
                    var addBtn = document.createElement('button');
                    addBtn.type = 'button';
                    addBtn.className = 'kbf-photo-add kbf-photo-slot';
                    addBtn.setAttribute('aria-label', 'Add photos');
                    addBtn.textContent = 'Add photo';
                    editPhotoWrap.appendChild(addBtn);
                }
            }
            if (editPhotoInput) {
                editPhotoInput.disabled = (totalCount >= 5);
            }
        }
        if (editPhotoInput && editPhotoWrap) {
            editPhotoWrap.addEventListener('click', function(e){
                if (!e.target || !e.target.classList.contains('kbf-photo-add')) return;
                e.preventDefault();
                editPhotoInput.click();
            });
            editPhotoInput.addEventListener('change', function(){
                var incoming = Array.from(editPhotoInput.files || []);
                if (!incoming.length) return;
                var oversizedFound = false;
                incoming = incoming.filter(function(f){
                    if (!f || typeof f.size !== 'number') return false;
                    if (f.size >= EDIT_MAX_PHOTO_BYTES) {
                        oversizedFound = true;
                        return false;
                    }
                    return true;
                });
                if (oversizedFound) {
                    kbfSetEditPhotoError('Please upload photos below 5MB.');
                } else {
                    kbfSetEditPhotoError('');
                }
                if (!incoming.length) {
                    editPhotoInput.value = '';
                    return;
                }
                /**
                 * @function  kbfFileKey
                 * @purpose   Handles kbfFileKey behavior in the dashboard script flow
                 * @used-by   [same file references detected]
                 * @calls     [none explicitly documented]
                 * @params    f: any - parameter
                 * @returns   void
                 * @status    ACTIVE
                 */
                function kbfFileKey(f){
                    return [f.name, f.size, f.lastModified, f.type].join('|');
                }
                var byKey = {};
                var merged = [];
                kbfEditFiles.forEach(function(f){
                    var k = kbfFileKey(f);
                    if (!byKey[k]) { byKey[k] = 1; merged.push(f); }
                });
                incoming.forEach(function(f){
                    var k = kbfFileKey(f);
                    if (!byKey[k]) { byKey[k] = 1; merged.push(f); }
                });
                var maxNew = Math.max(0, 5 - kbfEditExistingUrls.length);
                kbfEditFiles = merged.slice(0, maxNew);
                kbfSyncEditFiles();
                kbfRenderEditThumbs();
            });
            kbfRenderEditThumbs();
        }
        var editorBackdrop = document.getElementById('kbf-photo-editor');
        var editorImg = document.getElementById('kbf-photo-editor-img');
        var editorClose = document.getElementById('kbf-photo-editor-close');
        var editorCancel = document.getElementById('kbf-photo-editor-cancel');
        var editorApply = document.getElementById('kbf-photo-editor-apply');
        var editorRotateLeft = document.getElementById('kbf-photo-rotate-left');
        var editorRotateRight = document.getElementById('kbf-photo-rotate-right');
        var editorFlipX = document.getElementById('kbf-photo-flip-x');
        var editorFlipY = document.getElementById('kbf-photo-flip-y');
        var editorReset = document.getElementById('kbf-photo-reset');
        var editorZoom = document.getElementById('kbf-photo-zoom');
        var editorZoomMin = 1;
        var editorCrop = document.getElementById('kbf-photo-editor-crop');
        var editorState = {
            mode: null,
            index: -1,
            file: null,
            rotation: 0,
            flipX: 1,
            flipY: 1,
            zoom: 1,
            baseScale: 1,
            offsetX: 0,
            offsetY: 0,
            stageW: 0,
            stageH: 0,
            crop: { x: 0, y: 0, w: 0, h: 0 }
        };

        /**
         * @function  kbfClosePhotoEditor
         * @purpose   Handles kbfClosePhotoEditor behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfClosePhotoEditor(){
            if (editorBackdrop) {
                editorBackdrop.classList.remove('is-open');
                setTimeout(function(){ editorBackdrop.style.display = 'none'; }, 220);
            }
            if (editorImg) editorImg.src = '';
            editorState.mode = null;
            editorState.index = -1;
            editorState.file = null;
            editorState.rotation = 0;
            editorState.flipX = 1;
            editorState.flipY = 1;
            editorState.zoom = 1;
            editorState.baseScale = 1;
            editorState.offsetX = 0;
            editorState.offsetY = 0;
        }

        /**
         * @function  kbfUpdatePhotoEditorPreview
         * @purpose   Handles kbfUpdatePhotoEditorPreview behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfUpdatePhotoEditorPreview(){
            if (!editorImg) return;
            var scale = editorState.baseScale * Math.max(editorZoomMin, editorState.zoom);
            editorImg.style.transform =
                'translate(-50%, -50%) translate(' + editorState.offsetX + 'px,' + editorState.offsetY + 'px) rotate(' +
                editorState.rotation + 'deg) scale(' + (editorState.flipX * scale) + ',' + (editorState.flipY * scale) + ')';
        }

        /**
         * @function  kbfClampPhotoEditorOffset
         * @purpose   Handles kbfClampPhotoEditorOffset behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfClampPhotoEditorOffset(){
            if (!editorBackdrop || !editorImg || !editorCrop) return;
            var stage = editorBackdrop.querySelector('.kbf-photo-editor-stage');
            if (!stage) return;
            var cropRect = editorCrop.getBoundingClientRect();
            var imgRect = editorImg.getBoundingClientRect();
            if (!cropRect.width || !cropRect.height || !imgRect.width || !imgRect.height) return;
            var dx = 0;
            var dy = 0;
            if (imgRect.left > cropRect.left) dx -= (imgRect.left - cropRect.left);
            if (imgRect.top > cropRect.top) dy -= (imgRect.top - cropRect.top);
            if (imgRect.right < cropRect.right) dx += (cropRect.right - imgRect.right);
            if (imgRect.bottom < cropRect.bottom) dy += (cropRect.bottom - imgRect.bottom);
            if (dx || dy) {
                editorState.offsetX += dx;
                editorState.offsetY += dy;
            }
        }

        /**
         * @function  kbfLayoutPhotoCropBox
         * @purpose   Handles kbfLayoutPhotoCropBox behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfLayoutPhotoCropBox(){
            if (!editorBackdrop || !editorCrop) return;
            var stage = editorBackdrop.querySelector('.kbf-photo-editor-stage');
            if (!stage) return;
            var pad = 22;
            var stageW = stage.clientWidth || 0;
            var stageH = stage.clientHeight || 0;
            if (!stageW || !stageH) return;
            var maxW = Math.max(40, stageW - pad * 2);
            var maxH = Math.max(40, stageH - pad * 2);
            var cropW = Math.min(maxW, maxH * 4 / 3);
            var cropH = cropW * 3 / 4;
            if (cropH > maxH) {
                cropH = maxH;
                cropW = cropH * 4 / 3;
            }
            var cropX = Math.max(0, (stageW - cropW) / 2);
            var cropY = Math.max(0, (stageH - cropH) / 2);
            editorState.crop = { x: cropX, y: cropY, w: cropW, h: cropH };
            editorState.stageW = stageW;
            editorState.stageH = stageH;
            editorCrop.style.left = cropX + 'px';
            editorCrop.style.top = cropY + 'px';
            editorCrop.style.width = cropW + 'px';
            editorCrop.style.height = cropH + 'px';
            return editorState.crop;
        }

        /**
         * @function  kbfOpenPhotoEditor
         * @purpose   Handles kbfOpenPhotoEditor behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    file: any - parameter; mode: any - parameter; index: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfOpenPhotoEditor = function(file, mode, index){
            if (!editorBackdrop || !editorImg || !file) return;
            editorState.mode = mode;
            editorState.index = index;
            editorState.file = file;
            editorState.rotation = 0;
            editorState.flipX = 1;
            editorState.flipY = 1;
            editorState.zoom = 1;
            editorState.offsetX = 0;
            editorState.offsetY = 0;
            if (editorZoom) editorZoom.value = '1';
            var reader = new FileReader();
            reader.onload = function(e){
                editorImg.src = e.target.result;
                editorImg.onload = function(){
                    editorBackdrop.style.display = 'flex';
                    requestAnimationFrame(function(){
                        editorBackdrop.classList.add('is-open');
                        var stage = editorBackdrop.querySelector('.kbf-photo-editor-stage');
                        var stageW = stage ? stage.clientWidth : 0;
                        var stageH = stage ? stage.clientHeight : 0;
                        if (!stageW || !stageH) {
                            stageW = 520;
                            stageH = 280;
                        }
                        var w = editorImg.naturalWidth || 1;
                        var h = editorImg.naturalHeight || 1;
                    var crop = kbfLayoutPhotoCropBox();
                    var targetW = (crop && crop.w) ? crop.w : stageW;
                    var targetH = (crop && crop.h) ? crop.h : stageH;
                    editorState.baseScale = Math.max(targetW / w, targetH / h) || 1;
                    editorZoomMin = 1;
                    if (editorZoom) {
                        editorZoom.min = '1';
                        editorZoom.value = '1';
                    }
                    kbfUpdatePhotoEditorPreview();
                    kbfClampPhotoEditorOffset();
                    kbfUpdatePhotoEditorPreview();
                });
                };
            };
            reader.readAsDataURL(file);
        };

        /**
         * @function  kbfApplyPhotoEditor
         * @purpose   Handles kbfApplyPhotoEditor behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfApplyPhotoEditor(){
            if (!editorState.file || !editorImg || !editorImg.src) return;
            var stage = editorBackdrop ? editorBackdrop.querySelector('.kbf-photo-editor-stage') : null;
            if (!stage) return;
            var stageW = stage.clientWidth || 1;
            var stageH = stage.clientHeight || 1;
            var crop = editorState.crop || { x: 0, y: 0, w: stageW, h: stageH };
            var img = new Image();
            img.onload = function(){
                var rotation = ((editorState.rotation % 360) + 360) % 360;
                var sx = editorState.flipX;
                var sy = editorState.flipY;
                var w = img.naturalWidth || 1;
                var h = img.naturalHeight || 1;
                var scale = editorState.baseScale * editorState.zoom;
                var render = document.createElement('canvas');
                render.width = stageW;
                render.height = stageH;
                var rctx = render.getContext('2d');
                rctx.translate(stageW / 2 + editorState.offsetX, stageH / 2 + editorState.offsetY);
                rctx.rotate(rotation * Math.PI / 180);
                rctx.scale(sx * scale, sy * scale);
                rctx.drawImage(img, -w / 2, -h / 2);
                var out = document.createElement('canvas');
                out.width = Math.max(1, Math.round(crop.w));
                out.height = Math.max(1, Math.round(crop.h));
                var octx = out.getContext('2d');
                octx.drawImage(render, crop.x, crop.y, crop.w, crop.h, 0, 0, out.width, out.height);
                /**
                 * @function  kbfNormalizeFileMeta
                 * @purpose   Handles kbfNormalizeFileMeta behavior in the dashboard script flow
                 * @used-by   [same file references detected]
                 * @calls     [none explicitly documented]
                 * @params    file: any - parameter
                 * @returns   void
                 * @status    ACTIVE
                 */
                function kbfNormalizeFileMeta(file){
                    var type = (file && file.type) ? file.type : 'image/jpeg';
                    if (type === 'image/jpg') type = 'image/jpeg';
                    var name = (file && file.name) ? file.name : 'photo.jpg';
                    var ext = name.indexOf('.') !== -1 ? name.split('.').pop().toLowerCase() : '';
                    var expectedExt = (type === 'image/png') ? 'png' : (type === 'image/webp' ? 'webp' : 'jpg');
                    if (!ext || ext === name.toLowerCase()) {
                        name = name.replace(/\.+$/, '') + '.' + expectedExt;
                    } else if (!['jpg','jpeg','png','webp'].includes(ext)) {
                        name = name + '.' + expectedExt;
                    }
                    return { name: name, type: type };
                }
                /**
                 * @function  kbfDataUrlToBlob
                 * @purpose   Handles kbfDataUrlToBlob behavior in the dashboard script flow
                 * @used-by   [same file references detected]
                 * @calls     [none explicitly documented]
                 * @params    dataUrl: any - parameter
                 * @returns   void
                 * @status    ACTIVE
                 */
                function kbfDataUrlToBlob(dataUrl){
                    var parts = dataUrl.split(',');
                    if (parts.length < 2) return null;
                    var mimeMatch = parts[0].match(/data:([^;]+);base64/);
                    var mime = mimeMatch ? mimeMatch[1] : 'image/jpeg';
                    var bin = atob(parts[1]);
                    var len = bin.length;
                    var bytes = new Uint8Array(len);
                    for (var i = 0; i < len; i++) bytes[i] = bin.charCodeAt(i);
                    return new Blob([bytes], { type: mime });
                }
                var meta = kbfNormalizeFileMeta(editorState.file);
                out.toBlob(function(blob){
                    if (!blob) {
                        var fallback = kbfDataUrlToBlob(out.toDataURL(meta.type, 0.95));
                        if (!fallback) return;
                        blob = fallback;
                    }
                    var nextFile = new File([blob], meta.name, { type: meta.type });
                    if (editorState.mode === 'create') {
                        kbfCreateFiles[editorState.index] = nextFile;
                        kbfSyncCreateFiles();
                        kbfRenderCreateThumbs();
                    } else if (editorState.mode === 'create-redesign') {
                        if (typeof window.kbfCreateRedesignApplyPhoto === 'function') {
                            window.kbfCreateRedesignApplyPhoto(editorState.index, nextFile);
                        }
                    } else if (editorState.mode === 'edit') {
                        kbfEditFiles[editorState.index] = nextFile;
                        kbfSyncEditFiles();
                        kbfRenderEditThumbs();
                    }
                    kbfClosePhotoEditor();
                }, editorState.file.type || 'image/jpeg', 0.95);
            };
            img.src = editorImg.src;
        }

        if (editorBackdrop) {
            editorBackdrop.addEventListener('click', function(e){
                if (e.target === editorBackdrop) kbfClosePhotoEditor();
            });
        }
        if (editorClose) editorClose.addEventListener('click', kbfClosePhotoEditor);
        if (editorCancel) editorCancel.addEventListener('click', kbfClosePhotoEditor);
        if (editorApply) editorApply.addEventListener('click', kbfApplyPhotoEditor);
        if (editorRotateLeft) editorRotateLeft.addEventListener('click', function(){
            editorState.rotation -= 90;
            kbfUpdatePhotoEditorPreview();
            kbfClampPhotoEditorOffset();
            kbfUpdatePhotoEditorPreview();
        });
        if (editorRotateRight) editorRotateRight.addEventListener('click', function(){
            editorState.rotation += 90;
            kbfUpdatePhotoEditorPreview();
            kbfClampPhotoEditorOffset();
            kbfUpdatePhotoEditorPreview();
        });
        if (editorFlipX) editorFlipX.addEventListener('click', function(){
            editorState.flipX = editorState.flipX * -1;
            kbfUpdatePhotoEditorPreview();
            kbfClampPhotoEditorOffset();
            kbfUpdatePhotoEditorPreview();
        });
        if (editorFlipY) editorFlipY.addEventListener('click', function(){
            editorState.flipY = editorState.flipY * -1;
            kbfUpdatePhotoEditorPreview();
            kbfClampPhotoEditorOffset();
            kbfUpdatePhotoEditorPreview();
        });
        if (editorReset) editorReset.addEventListener('click', function(){
            editorState.rotation = 0;
            editorState.flipX = 1;
            editorState.flipY = 1;
            editorState.zoom = 1;
            editorState.offsetX = 0;
            editorState.offsetY = 0;
            if (editorZoom) editorZoom.value = '1';
            kbfUpdatePhotoEditorPreview();
            kbfClampPhotoEditorOffset();
            kbfUpdatePhotoEditorPreview();
        });
        if (editorZoom) editorZoom.addEventListener('input', function(){
            editorState.zoom = parseFloat(editorZoom.value || '1') || 1;
            kbfUpdatePhotoEditorPreview();
            kbfClampPhotoEditorOffset();
            kbfUpdatePhotoEditorPreview();
        });
        if (editorBackdrop) {
            var stage = editorBackdrop.querySelector('.kbf-photo-editor-stage');
            if (stage) {
                var dragging = false;
                var startX = 0;
                var startY = 0;
                var startOffsetX = 0;
                var startOffsetY = 0;
                stage.addEventListener('pointerdown', function(e){
                    var rect = stage.getBoundingClientRect();
                    var x = e.clientX - rect.left;
                    var y = e.clientY - rect.top;
                    var crop = editorState.crop;
                    if (!crop || x < crop.x || x > (crop.x + crop.w) || y < crop.y || y > (crop.y + crop.h)) {
                        return;
                    }
                    dragging = true;
                    startX = e.clientX;
                    startY = e.clientY;
                    startOffsetX = editorState.offsetX;
                    startOffsetY = editorState.offsetY;
                    if (stage.setPointerCapture) stage.setPointerCapture(e.pointerId);
                    e.preventDefault();
                });
                stage.addEventListener('pointermove', function(e){
                    if (!dragging) return;
                    editorState.offsetX = startOffsetX + (e.clientX - startX);
                    editorState.offsetY = startOffsetY + (e.clientY - startY);
                    kbfUpdatePhotoEditorPreview();
                    kbfClampPhotoEditorOffset();
                    kbfUpdatePhotoEditorPreview();
                    e.preventDefault();
                });
                stage.addEventListener('pointerup', function(e){
                    dragging = false;
                    if (stage.releasePointerCapture) stage.releasePointerCapture(e.pointerId);
                });
                stage.addEventListener('pointercancel', function(e){
                    dragging = false;
                    if (stage.releasePointerCapture) stage.releasePointerCapture(e.pointerId);
                });
            }
            window.addEventListener('resize', function(){
                if (editorBackdrop.style.display !== 'flex') return;
                kbfLayoutPhotoCropBox();
                kbfClampPhotoEditorOffset();
                kbfUpdatePhotoEditorPreview();
            });
        }
        /**
         * @function  kbfResetEditPhotos
         * @purpose   Handles kbfResetEditPhotos behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfResetEditPhotos = function(){
            kbfEditFiles = [];
            kbfEditExistingUrls = [];
            if (editPhotoInput) {
                kbfSyncEditFiles();
            }
            if (editPhotoWrap) kbfRenderEditThumbs();
        };
    /**
     * @function  kbfSetEditExistingPhotos
     * @purpose   Handles kbfSetEditExistingPhotos behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    urls: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfSetEditExistingPhotos = function(urls){
        if (!Array.isArray(urls)) urls = [];
        var seen = {};
        kbfEditExistingUrls = urls
            .filter(function(u){ return !!u; })
            .filter(function(u){
                var key = String(u);
                if (seen[key]) return false;
                seen[key] = true;
                return true;
            });
        kbfEditRemovedUrls = [];
        var removedInput = document.getElementById('kbf-edit-removed-photos');
        if (removedInput) removedInput.value = '';
        if (editPhotoWrap) kbfRenderEditThumbs();
    };
        if (!isCreateRedesign && photoInput && photoWrap) {
            photoWrap.addEventListener('click', function(e){
                if (!e.target || !e.target.classList.contains('kbf-photo-add')) return;
                e.preventDefault();
                photoInput.click();
            });
            photoInput.addEventListener('change', function(){
                var incoming = Array.from(photoInput.files || []);
                if (!incoming.length) return;
                kbfCreateFiles = kbfCreateFiles.concat(incoming).slice(0, 5);
                kbfSyncCreateFiles();
                kbfRenderCreateThumbs();
            });
            kbfRenderCreateThumbs();
        }
        /**
         * @function  kbfInitBenefitsEditor
         * @purpose   Handles kbfInitBenefitsEditor behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    containerId: any - parameter; inputId: any - parameter; addBtnId: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function kbfInitBenefitsEditor(containerId, inputId, addBtnId) {
            var container = document.getElementById(containerId);
            var input = document.getElementById(inputId);
            var addBtn = document.getElementById(addBtnId);
            if (!container || !input) return null;
            /**
             * @function  buildCard
             * @purpose   Handles buildCard behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    data: any - parameter
             * @returns   void
             * @status    ACTIVE
             */
            function buildCard(data){
                var card = document.createElement('div');
                card.className = 'kbf-benefit-card';
                var row = document.createElement('div');
                row.className = 'kbf-benefit-row';
                var title = document.createElement('input');
                title.type = 'text';
                title.className = 'kbf-benefit-title';
                title.placeholder = 'Tier name';
                title.value = data && data.title ? data.title : '';
                title.maxLength = 80;
                var amount = document.createElement('input');
                amount.type = 'text';
                amount.inputMode = 'decimal';
                amount.className = 'kbf-benefit-amount';
                amount.placeholder = 'Amount (PHP)';
                amount.value = formatAmountInput(data && data.amount ? data.amount : '');
                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'kbf-benefit-remove';
                remove.innerHTML = '&times;';
                remove.addEventListener('click', function(){
                    card.remove();
                    sync();
                });
                row.appendChild(title);
                row.appendChild(amount);
                row.appendChild(remove);
                var titleMeta = document.createElement('div');
                titleMeta.className = 'kbf-benefit-meta kbf-benefit-meta-name';
                var titleErr = document.createElement('small');
                titleErr.className = 'kbf-benefit-field-error';
                var titleCounter = document.createElement('small');
                titleCounter.className = 'kbf-benefit-counter';
                titleCounter.textContent = (title.value || '').length + ' / 80';
                var desc = document.createElement('textarea');
                desc.className = 'kbf-benefit-desc';
                desc.rows = 2;
                desc.placeholder = 'Short description';
                desc.value = data && data.description ? data.description : '';
                desc.maxLength = 200;
                var descMeta = document.createElement('div');
                descMeta.className = 'kbf-benefit-meta kbf-benefit-meta-desc';
                var descErr = document.createElement('small');
                descErr.className = 'kbf-benefit-field-error';
                var descCounter = document.createElement('small');
                descCounter.className = 'kbf-benefit-counter';
                descCounter.textContent = (desc.value || '').length + ' / 200';
                var amountMeta = document.createElement('div');
                amountMeta.className = 'kbf-benefit-meta kbf-benefit-meta-amount';
                var amountErr = document.createElement('small');
                amountErr.className = 'kbf-benefit-field-error';
                amountMeta.appendChild(amountErr);
                card.appendChild(row);
                card.appendChild(titleMeta);
                card.appendChild(amountMeta);
                card.appendChild(desc);
                card.appendChild(descMeta);
                [title, amount, desc].forEach(function(el){
                    el.addEventListener('input', sync);
                });
                amount.addEventListener('input', function(){
                    amount.value = formatAmountInput(amount.value);
                });
                title.addEventListener('input', function(){
                    titleCounter.textContent = (title.value || '').length + ' / 80';
                });
                desc.addEventListener('input', function(){
                    descCounter.textContent = (desc.value || '').length + ' / 200';
                });
                titleMeta.appendChild(titleErr);
                titleMeta.appendChild(titleCounter);
                descMeta.appendChild(descErr);
                descMeta.appendChild(descCounter);
                return card;
            }
            /**
             * @function  normalizeAmount
             * @purpose   Handles normalizeAmount behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    val: any - parameter
             * @returns   void
             * @status    ACTIVE
             */
            function normalizeAmount(val){
                return String(val || '').replace(/[^0-9.]/g, '');
            }
            /**
             * @function  formatAmountInput
             * @purpose   Adds comma separators to benefit amount inputs while preserving raw numeric values.
             * @used-by   [same file references detected]
             * @calls     [normalizeAmount]
             * @params    val: any - parameter
             * @returns   string
             * @status    ACTIVE
             */
            function formatAmountInput(val){
                var clean = normalizeAmount(val);
                if (!clean) return '';
                var parts = clean.split('.');
                var whole = parts.shift() || '';
                var decimal = parts.length ? parts.join('').slice(0, 2) : '';
                var wholeFormatted = whole ? whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0';
                return decimal ? (wholeFormatted + '.' + decimal) : wholeFormatted;
            }
            function validateCard(card){
                if (!card) return true;
                var title = card.querySelector('.kbf-benefit-title');
                var amount = card.querySelector('.kbf-benefit-amount');
                var desc = card.querySelector('.kbf-benefit-desc');
                var titleErr = card.querySelectorAll('.kbf-benefit-field-error')[0];
                var amountErr = card.querySelectorAll('.kbf-benefit-field-error')[1];
                var descErr = card.querySelectorAll('.kbf-benefit-field-error')[2];
                var amountMeta = card.querySelector('.kbf-benefit-meta-amount');
                if (titleErr) titleErr.textContent = '';
                if (amountErr) amountErr.textContent = '';
                if (descErr) descErr.textContent = '';
                if (amountMeta) amountMeta.classList.remove('has-error');
                card.classList.remove('is-invalid');
                var t = title ? String(title.value || '').trim() : '';
                var a = amount ? normalizeAmount(amount.value) : '';
                var d = desc ? String(desc.value || '').trim() : '';
                var hasAny = !!(t || a || d);
                var valid = true;
                if (t.length > 80) { if (titleErr) titleErr.textContent = 'Tier name must be 80 characters or fewer.'; valid = false; }
                if (d.length > 200) { if (descErr) descErr.textContent = 'Description must be 200 characters or fewer.'; valid = false; }
                if (a) {
                    var amountNum = parseFloat(a);
                    if (isNaN(amountNum) || amountNum <= 0) {
                        if (amountErr) amountErr.textContent = 'Enter a valid amount greater than 0.';
                        if (amountMeta) amountMeta.classList.add('has-error');
                        valid = false;
                    }
                }
                if (hasAny) {
                    if (!t) { if (titleErr) titleErr.textContent = titleErr.textContent || 'Tier name is required.'; valid = false; }
                    if (!a) {
                        if (amountErr) amountErr.textContent = amountErr.textContent || 'Amount is required.';
                        if (amountMeta) amountMeta.classList.add('has-error');
                        valid = false;
                    }
                    if (!d) { if (descErr) descErr.textContent = descErr.textContent || 'Description is required.'; valid = false; }
                }
                if (!valid) card.classList.add('is-invalid');
                return valid;
            }
            /**
             * @function  sync
             * @purpose   Handles sync behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    none
             * @returns   void
             * @status    ACTIVE
             */
            function sync(){
                var items = container.querySelectorAll('.kbf-benefit-card');
                var list = [];
                items.forEach(function(card){
                    validateCard(card);
                    var title = card.querySelector('.kbf-benefit-title');
                    var amount = card.querySelector('.kbf-benefit-amount');
                    var desc = card.querySelector('.kbf-benefit-desc');
                    var t = title ? title.value.trim() : '';
                    var a = amount ? normalizeAmount(amount.value) : '';
                    var d = desc ? desc.value.trim() : '';
                    if (!t && !a && !d) return;
                    list.push({
                        title: t,
                        amount: a,
                        description: d
                    });
                });
                input.value = list.length ? JSON.stringify(list) : '';
            }
            function validate(){
                var firstInvalid = null;
                container.querySelectorAll('.kbf-benefit-card').forEach(function(card){
                    if (!validateCard(card) && !firstInvalid) {
                        firstInvalid = card.querySelector('input, textarea');
                    }
                });
                sync();
                return firstInvalid;
            }
            /**
             * @function  set
             * @purpose   Handles set behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    list: any - parameter
             * @returns   void
             * @status    ACTIVE
             */
            function set(list){
                container.innerHTML = '';
                if (Array.isArray(list)) {
                    list.forEach(function(item){
                        container.appendChild(buildCard(item || {}));
                    });
                }
                sync();
            }
            if (addBtn) {
                addBtn.addEventListener('click', function(){
                    container.appendChild(buildCard({}));
                    sync();
                });
            }
            set([]);
            return { set: set, sync: sync, validate: validate };
        }
        window.kbfBenefitsEditors = window.kbfBenefitsEditors || {};
        if (!isCreateRedesign) {
            window.kbfBenefitsEditors.create = kbfInitBenefitsEditor('kbf-create-benefits','kbf-create-benefits-input','kbf-create-benefit-add');
        }
        window.kbfBenefitsEditors.edit = kbfInitBenefitsEditor('kbf-edit-benefits','kbf-edit-benefits-input','kbf-edit-benefit-add');
        var createProv = document.getElementById('kbf-province');
        var createMuni = document.getElementById('kbf-municipality');
        var createBrgy = document.getElementById('kbf-barangay');
        if (!isCreateRedesign && createProv && createMuni && createBrgy) {
            window.kbfCreateLocPicker = kbfInitLocationPicker(createProv, createMuni, createBrgy);
        }
        var editProv = document.getElementById('kbf-edit-province');
        var editMuni = document.getElementById('kbf-edit-municipality');
        var editBrgy = document.getElementById('kbf-edit-barangay');
        if (editProv && editMuni && editBrgy) {
            window.kbfEditLocPicker = kbfInitLocationPicker(editProv, editMuni, editBrgy);
        }
        // Profile page address dropdowns
        var profileProv = document.getElementById('kbf-profile-province');
        var profileMuni = document.getElementById('kbf-profile-municipality');
        var profileBrgy = document.getElementById('kbf-profile-barangay');
        if (profileProv && profileMuni && profileBrgy) {
            window.kbfProfileLocPicker = kbfInitLocationPicker(profileProv, profileMuni, profileBrgy);
        }
        if (!isCreateRedesign) {
        var kbfDraftKey = 'kbf_create_draft_<?php echo (int)$business_id; ?>';
        /**
         * @function  kbfDraftStorageOk
         * @purpose   Handles kbfDraftStorageOk behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfDraftStorageOk(){
            try {
                var t = '__kbf__';
                localStorage.setItem(t, '1');
                localStorage.removeItem(t);
                return true;
            } catch(e) { return false; }
        }
        /**
         * @function  kbfGetCreateDraft
         * @purpose   Handles kbfGetCreateDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfGetCreateDraft(){
            if (!kbfDraftStorageOk()) return null;
            try {
                var raw = localStorage.getItem(kbfDraftKey);
                return raw ? JSON.parse(raw) : null;
            } catch(e) { return null; }
        }
        /**
         * @function  kbfSetCreateDraft
         * @purpose   Handles kbfSetCreateDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    data: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function kbfSetCreateDraft(data){
            if (!kbfDraftStorageOk()) return;
            if (!data) { localStorage.removeItem(kbfDraftKey); return; }
            try {
                localStorage.setItem(kbfDraftKey, JSON.stringify(data));
            } catch(e) {
                if (data.photos && data.photos.length) {
                    try {
                        var safe = Object.assign({}, data, { photos: [] });
                        localStorage.setItem(kbfDraftKey, JSON.stringify(safe));
                    } catch(e2) {}
                }
            }
        }
        /**
         * @function  kbfDraftComparable
         * @purpose   Handles kbfDraftComparable behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    draft: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function kbfDraftComparable(draft){
            if (!draft) return '';
            var photos = Array.isArray(draft.photos) ? draft.photos.map(function(p){
                return {
                    dataUrl: p && p.dataUrl ? p.dataUrl : '',
                    name: p && p.name ? p.name : '',
                    type: p && p.type ? p.type : ''
                };
            }) : [];
            return JSON.stringify({
                fields: draft.fields || {},
                step: draft.step || 1,
                photos: photos
            });
        }
        /**
         * @function  kbfCreateHasValue
         * @purpose   Handles kbfCreateHasValue behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfCreateHasValue(){
            if (!createForm) return false;
            var fields = createForm.querySelectorAll('input[name], select[name], textarea[name]');
            for (var i=0;i<fields.length;i++){
                var f = fields[i];
                if (f.type === 'file') {
                    if (f.files && f.files.length) return true;
                    continue;
                }
                if (f.type === 'checkbox') {
                    if (f.checked) return true;
                    continue;
                }
                if (String(f.value || '').trim() !== '') return true;
            }
            return false;
        }
        /**
         * @function  kbfGetCurrentCreateStep
         * @purpose   Handles kbfGetCurrentCreateStep behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfGetCurrentCreateStep(){
            if (!createForm) return 1;
            var active = createForm.querySelector('.kbf-step.is-active');
            var step = active && active.dataset ? parseInt(active.dataset.step || '1', 10) : 1;
            return isNaN(step) ? 1 : step;
        }
        /**
         * @function  kbfBuildCreateDraft
         * @purpose   Handles kbfBuildCreateDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfBuildCreateDraft(){
            if (!createForm) return null;
            var data = { fields: {}, step: kbfGetCurrentCreateStep(), saved_at: Date.now() };
            var fields = createForm.querySelectorAll('input[name], select[name], textarea[name]');
            for (var i=0;i<fields.length;i++){
                var f = fields[i];
                if (f.type === 'file') continue;
                if (f.type === 'checkbox') data.fields[f.name] = !!f.checked;
                else data.fields[f.name] = f.value;
            }
            if (Array.isArray(kbfCreateFiles) && kbfCreateFiles.length) {
                data.photos = kbfCreateFiles.map(function(file){
                    return {
                        dataUrl: file._kbfDataUrl || '',
                        name: file.name || 'photo.jpg',
                        type: file.type || 'image/jpeg'
                    };
                }).filter(function(p){ return !!p.dataUrl; });
            }
            return data;
        }
        /**
         * @function  kbfDataUrlToBlob
         * @purpose   Handles kbfDataUrlToBlob behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    dataUrl: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function kbfDataUrlToBlob(dataUrl){
            if (!dataUrl || dataUrl.indexOf('data:') !== 0) return null;
            var parts = dataUrl.split(',');
            if (parts.length < 2) return null;
            var meta = parts[0] || '';
            var base64 = parts[1] || '';
            var mime = (meta.match(/data:([^;]+)/) || [])[1] || 'image/jpeg';
            var binary = atob(base64);
            var len = binary.length;
            var bytes = new Uint8Array(len);
            for (var i=0;i<len;i++) bytes[i] = binary.charCodeAt(i);
            return new Blob([bytes], { type: mime });
        }
        /**
         * @function  kbfDataUrlToFile
         * @purpose   Handles kbfDataUrlToFile behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    dataUrl: any - parameter; name: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function kbfDataUrlToFile(dataUrl, name){
            var blob = kbfDataUrlToBlob(dataUrl);
            if (!blob) return null;
            var fname = name || ('photo-' + Date.now() + '.jpg');
            return new File([blob], fname, { type: blob.type || 'image/jpeg' });
        }
        /**
         * @function  kbfApplyDraftToForm
         * @purpose   Handles kbfApplyDraftToForm behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    draft: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function kbfApplyDraftToForm(draft){
            if (!draft || !draft.fields || !createForm) return false;
            var fields = createForm.querySelectorAll('input[name], select[name], textarea[name]');
            for (var i=0;i<fields.length;i++){
                var f = fields[i];
                if (!draft.fields.hasOwnProperty(f.name)) continue;
                if (f.type === 'file') continue;
                if (f.type === 'checkbox') f.checked = !!draft.fields[f.name];
                else f.value = draft.fields[f.name];
            }
            if (window.kbfBenefitsEditors && window.kbfBenefitsEditors.create) {
                var benefitsField = draft.fields.benefits || '';
                var benefitList = [];
                try {
                    benefitList = benefitsField ? JSON.parse(benefitsField) : [];
                } catch(e) { benefitList = []; }
                window.kbfBenefitsEditors.create.set(Array.isArray(benefitList) ? benefitList : []);
            }
            if (draft.photos && Array.isArray(draft.photos) && draft.photos.length) {
                kbfCreateFiles = [];
                draft.photos.slice(0,5).forEach(function(p){
                    var file = kbfDataUrlToFile(p.dataUrl, p.name);
                    if (file) {
                        file._kbfDataUrl = p.dataUrl;
                        kbfCreateFiles.push(file);
                    }
                });
                kbfSyncCreateFiles();
                kbfRenderCreateThumbs();
            }
            var provEl = document.getElementById('kbf-province');
            var muniEl = document.getElementById('kbf-municipality');
            var brgyEl = document.getElementById('kbf-barangay');
            if (provEl && muniEl && brgyEl) {
                var prov = draft.fields.location || provEl.value || '';
                var muni = draft.fields.municipality || muniEl.value || '';
                var brgy = draft.fields.barangay || brgyEl.value || '';
                var parts = [];
                if (brgy) parts.push(brgy);
                if (muni) parts.push(muni);
                if (prov) parts.push(prov);
                if (parts.length) kbfApplyLocationSelection(provEl, muniEl, brgyEl, parts.join(', '));
            }
            var titleInput = document.getElementById('kbf-create-title');
            if (titleInput) titleInput.dispatchEvent(new Event('input'));
            var descInput = createForm.querySelector('textarea[name="description"]');
            if (descInput) descInput.dispatchEvent(new Event('input'));
            var goalInput = document.getElementById('kbf-goal-amount');
            if (goalInput) goalInput.dispatchEvent(new Event('input'));
            var step = parseInt(draft.step || '1', 10);
            if (typeof kbfSetCreateStep === 'function') kbfSetCreateStep(Math.min(3, Math.max(1, isNaN(step) ? 1 : step)));
            return true;
        }
        /**
         * @function  kbfApplyCreateDraft
         * @purpose   Handles kbfApplyCreateDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    force: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfApplyCreateDraft = function(force){
            var draft = kbfGetCreateDraft();
            if (!draft) return false;
            if (!force && kbfCreateHasValue()) return false;
            return kbfApplyDraftToForm(draft);
        };
        /**
         * @function  kbfClearCreateDraft
         * @purpose   Handles kbfClearCreateDraft behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        window.kbfClearCreateDraft = function(){
            kbfSetCreateDraft(null);
        };
    /**
     * @function  kbfRequestCloseCreate
     * @purpose   Handles kbfRequestCloseCreate behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfRequestCloseCreate = function(){
      kbfCloseModal('kbf-modal-create');
    };
    /**
     * @function  kbfSaveAndCloseCreateDraft
     * @purpose   Handles kbfSaveAndCloseCreateDraft behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfSaveAndCloseCreateDraft = function(){
        var data = kbfBuildCreateDraft();
        if (data) kbfSetCreateDraft(data);
        kbfCloseModal('kbf-modal-create');
    };
        var saveCloseBtn = document.getElementById('kbf-create-save-close');
        var lastSavedDraftHash = kbfDraftComparable(kbfGetCreateDraft());
        /**
         * @function  kbfUpdateSaveCloseState
         * @purpose   Handles kbfUpdateSaveCloseState behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function kbfUpdateSaveCloseState(){
            if (!saveCloseBtn) return;
            if (!kbfCreateHasValue()) {
                saveCloseBtn.disabled = true;
                return;
            }
            var currentHash = kbfDraftComparable(kbfBuildCreateDraft());
            saveCloseBtn.disabled = currentHash === lastSavedDraftHash;
        }
        window.kbfUpdateSaveCloseState = kbfUpdateSaveCloseState;
        if (next) kbfSetCreateStep(1);
        if (saveCloseBtn) {
            saveCloseBtn.addEventListener('click', function(){
                if (window.kbfSaveAndCloseCreateDraft) window.kbfSaveAndCloseCreateDraft();
                lastSavedDraftHash = kbfDraftComparable(kbfGetCreateDraft());
                kbfUpdateSaveCloseState();
            });
        }
        if (createForm) {
            createForm.addEventListener('input', kbfUpdateSaveCloseState);
            createForm.addEventListener('change', kbfUpdateSaveCloseState);
        }
        kbfUpdateSaveCloseState();
        }
    })();;
    /**
     * @function  kbfSetBtnLoading
     * @purpose   Handles kbfSetBtnLoading behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    btn: any - parameter; on: any - parameter; label: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSetBtnLoading(btn, on, label) {
        if (!btn) return;
        if (on) {
            btn.dataset.kbfLabel = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = label || 'Loading...';
        } else {
            btn.disabled = false;
            if (btn.dataset.kbfLabel) btn.innerHTML = btn.dataset.kbfLabel;
        }
    }
    /**
     * @function  kbfSetSkeleton
     * @purpose   Handles kbfSetSkeleton behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    el: any - parameter; on: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSetSkeleton(el, on) {
        if (!el) return;
        if (on) {
            el.dataset.kbfPrev = el.innerHTML;
            el.innerHTML =
                '<div class="kbf-skeleton kbf-skel-line lg"></div>' +
                '<div class="kbf-skeleton kbf-skel-line md"></div>' +
                '<div class="kbf-skeleton kbf-skel-line sm"></div>' +
                '<div class="kbf-skeleton kbf-skel-box"></div>';
        } else if (el.dataset.kbfPrev !== undefined) {
            el.innerHTML = el.dataset.kbfPrev;
            delete el.dataset.kbfPrev;
        }
    }

    /**
     * @function  kbfSetFieldError
     * @purpose   Handles kbfSetFieldError behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    field: any - parameter; message: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSetFieldError(field, message) {
        var group = field.closest('.kbf-form-group');
        if (!group) return;
        var err = group.querySelector('.kbf-field-error');
        if (!err) {
            err = document.createElement('div');
            err.className = 'kbf-field-error';
            group.appendChild(err);
        }
        err.textContent = message;
        field.classList.add('kbf-input-error');
    }

    /**
     * @function  kbfClearFieldError
     * @purpose   Handles kbfClearFieldError behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    field: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfClearFieldError(field) {
        var group = field.closest('.kbf-form-group');
        if (!group) return;
        var err = group.querySelector('.kbf-field-error');
        if (err) err.textContent = '';
        field.classList.remove('kbf-input-error');
    }

    /**
     * @function  kbfValidateDateMinField
     * @purpose   Validates date input value against its min attribute.
     * @used-by   [edit modal validation]
     * @calls     [none explicitly documented]
     * @params    field: any - parameter; message: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfValidateDateMinField(field, message) {
      if (!field || field.type !== 'date') return true;
      var valueRaw = String(field.value || '').trim();
      if (!valueRaw) return true;
      var minRaw = String(field.getAttribute('min') || '').trim();
      if (!minRaw) return true;
      var valueDate = new Date(valueRaw + 'T00:00:00');
      var minDate = new Date(minRaw + 'T00:00:00');
      if (isNaN(valueDate.getTime()) || isNaN(minDate.getTime())) return true;
      if (valueDate < minDate) {
        kbfSetFieldError(field, message || 'Please select a date at least 7 days from today.');
        return false;
      }
      kbfClearFieldError(field);
      return true;
    }

    /**
     * @function  kbfRenderEditDeadlineMeta
     * @purpose   Renders visible today/earliest/selected labels for the edit deadline field.
     * @used-by   [edit modal open and deadline listeners]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function kbfRenderEditDeadlineMeta() {
      var deadlineInput = document.getElementById('edit-fund-deadline');
      var meta = document.getElementById('kbf-edit-deadline-today');
      if (!deadlineInput || !meta) return;

      var now = new Date();
      var todayLabel = now.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
      var minRaw = String(deadlineInput.getAttribute('min') || '').trim();
      var minLabel = '';
      if (minRaw) {
        var minDate = new Date(minRaw + 'T00:00:00');
        if (!isNaN(minDate.getTime())) {
          minLabel = minDate.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
        }
      }

      var selectedLabel = '';
      if (deadlineInput.value) {
        var selectedDate = new Date(deadlineInput.value + 'T00:00:00');
        if (!isNaN(selectedDate.getTime())) {
          selectedLabel = selectedDate.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
        }
      }

      var html = '<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-weight:700;">Today: ' + todayLabel + '</span>';
      if (minLabel) {
        html += '<span style="margin-left:8px;color:#64748b;">Earliest: ' + minLabel + '</span>';
      }
      if (selectedLabel) {
        html += '<span style="margin-left:8px;color:#0f172a;font-weight:600;">Selected: ' + selectedLabel + '</span>';
      }
      meta.innerHTML = html;
    }

    /**
     * @function  kbfValidateEditFund
     * @purpose   Handles kbfValidateEditFund behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    form: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfValidateEditFund(form) {
        var fields = [
            document.getElementById('edit-fund-title'),
            document.getElementById('edit-fund-desc')
        ];
        var firstInvalid = null;
        for (var i = 0; i < fields.length; i++) {
            var f = fields[i];
            if (!f) continue;
            var valid = String(f.value || '').trim() !== '';
            if (!valid) {
                if (!firstInvalid) firstInvalid = f;
                kbfSetFieldError(f, 'This field is required.');
            } else {
                kbfClearFieldError(f);
            }
        }
            var editDeadline = document.getElementById('edit-fund-deadline');
            if (editDeadline) {
              var dateOk = kbfValidateDateMinField(editDeadline, 'Please select a deadline at least 7 days from today.');
              if (!dateOk && !firstInvalid) firstInvalid = editDeadline;
            }
        return firstInvalid;
    }

    /**
     * @function  kbfValidateCreateForm
     * @purpose   Handles kbfValidateCreateForm behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    form: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfValidateCreateForm(form) {
        var fields = form.querySelectorAll('input, select, textarea');
        var firstInvalid = null;
        for (var i=0; i<fields.length; i++) {
            var f = fields[i];
            if (f.disabled) continue;
            if (f.hasAttribute('required')) {
                var valid = true;
                if (f.type === 'file') {
                    valid = f.files && f.files.length > 0;
                } else if (f.type === 'checkbox') {
                    valid = f.checked;
                } else {
                    valid = String(f.value || '').trim() !== '';
                }
                if (valid && f.id === 'kbf-goal-amount') {
                    var raw = (f.dataset.kbfRaw || f.value || '').replace(/,/g, '');
                    var num = parseFloat(raw);
                    valid = !isNaN(num) && num >= 100;
                    if (!valid) {
                        kbfSetFieldError(f, 'Minimum goal amount is 100.');
                        if (!firstInvalid) firstInvalid = f;
                        continue;
                    }
                }
                if (!valid) {
                    if (!firstInvalid) firstInvalid = f;
                    kbfSetFieldError(f, 'This field is required.');
                } else {
                    kbfClearFieldError(f);
                }
            } else {
                kbfClearFieldError(f);
            }
        }
        return firstInvalid;
    }

    /**
     * @function  kbfValidateCreateStep
     * @purpose   Handles kbfValidateCreateStep behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    step: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfValidateCreateStep(step) {
        if (typeof window.kbfCreateValidateStep === 'function') {
            return window.kbfCreateValidateStep(step);
        }
        var form = document.getElementById('kbf-create-fund-form');
        if (!form) return true;
        var n = parseInt(step || '1', 10);
        if (isNaN(n)) n = 1;
        var panel = form.querySelector('.kbf-step-content[data-step="'+n+'"]');
        if (!panel) return true;
        var fields = panel.querySelectorAll('input, select, textarea');
        var firstInvalid = null;
        for (var i=0; i<fields.length; i++) {
            var f = fields[i];
            if (f.disabled) continue;
            if (f.hasAttribute('required')) {
                var valid = true;
                if (f.type === 'file') {
                    valid = f.files && f.files.length > 0;
                } else if (f.type === 'checkbox') {
                    valid = f.checked;
                } else {
                    valid = String(f.value || '').trim() !== '';
                }
                if (!valid) {
                    if (!firstInvalid) firstInvalid = f;
                    kbfSetFieldError(f, 'This field is required.');
                } else {
                    kbfClearFieldError(f);
                }
            } else {
                kbfClearFieldError(f);
            }
        }
        if (firstInvalid) {
          if (typeof firstInvalid.scrollIntoView === 'function') {
            firstInvalid.scrollIntoView({ behavior:'smooth', block:'center' });
          }
          try { firstInvalid.focus({ preventScroll:true }); }
          catch(e) { firstInvalid.focus(); }
            return false;
        }
        return true;
    }
    window.kbfValidateCreateStep = kbfValidateCreateStep;

    /**
     * @function  kbfValidateEditStep
     * @purpose   Handles kbfValidateEditStep behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    step: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfValidateEditStep(step) {
        var form = document.getElementById('kbf-edit-fund-form');
        if (!form) return true;
        var n = parseInt(step || '1', 10);
        if (isNaN(n)) n = 1;
        var panel = form.querySelector('.kbf-step-content[data-step="'+n+'"]');
        if (!panel) return true;
        var fields = panel.querySelectorAll('input, select, textarea');
        var firstInvalid = null;
        for (var i=0; i<fields.length; i++) {
            var f = fields[i];
            if (f.disabled) continue;
            if (f.hasAttribute('required')) {
                var valid = true;
                if (f.type === 'file') {
                    valid = f.files && f.files.length > 0;
                } else if (f.type === 'checkbox') {
                    valid = f.checked;
                } else {
                    valid = String(f.value || '').trim() !== '';
                }
                if (!valid) {
                    if (!firstInvalid) firstInvalid = f;
                    kbfSetFieldError(f, 'This field is required.');
                } else {
                    kbfClearFieldError(f);
                }
            } else {
                kbfClearFieldError(f);
            }
            if (f.type === 'date') {
              var stepDateOk = kbfValidateDateMinField(f, 'Please select a deadline at least 7 days from today.');
              if (!stepDateOk && !firstInvalid) firstInvalid = f;
            }
        }
        if (firstInvalid) {
            firstInvalid.focus();
            return false;
        }
        return true;
    }
    window.kbfValidateEditStep = kbfValidateEditStep;

    /**
     * @function  kbfSetLoadingPage
     * @purpose   Handles kbfSetLoadingPage behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    on: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSetLoadingPage(on) {
        var el = document.getElementById('kbf-loading-overlay');
        if (!el) return;
        el.style.display = on ? 'flex' : 'none';
    }

    /**
     * @function  kbfInitDescCounter
     * @purpose   Handles kbfInitDescCounter behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    textarea: any - parameter
     * @returns   void
     * @status    ACTIVE
     */
    function kbfInitDescCounter(textarea){
        if (!textarea) return;
        var counter = textarea.parentNode.querySelector('.kbf-desc-counter');
        /**
         * @function  update
         * @purpose   Handles update behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    none
         * @returns   void
         * @status    ACTIVE
         */
        function update(){
            if (!counter) return;
            var len = (textarea.value || '').length;
            counter.textContent = len + ' / 800';
        }
        textarea.addEventListener('input', update);
        update();
    }
        /**
         * @function  kbfInitTitleCounter
         * @purpose   Handles kbfInitTitleCounter behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    input: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function kbfInitTitleCounter(input){
            if (!input) return;
            var counter = input.parentNode.querySelector('.kbf-title-counter');
            /**
             * @function  update
             * @purpose   Handles update behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    none
             * @returns   void
             * @status    ACTIVE
             */
            function update(){
                if (!counter) return;
                var len = (input.value || '').length;
                counter.textContent = len + ' / 150';
            }
            input.addEventListener('input', update);
            update();
        }
    (function(){
        var createForm = document.getElementById('kbf-create-fund-form');
        var isCreateRedesign = createForm && createForm.querySelector('.kbf-create-panel');
        if (!isCreateRedesign) {
            kbfInitDescCounter(document.querySelector('#kbf-create-fund-form textarea[name="description"]'));
            kbfInitTitleCounter(document.querySelector('#kbf-create-fund-form input[name="title"]'));
            var funderSelect = document.querySelector('#kbf-create-fund-form select[name="funder_type"]');
            var titleInput = document.getElementById('kbf-create-title');
            if (funderSelect && titleInput) {
                var placeholders = {
                    yourself: 'e.g., Help with my medical bills',
                    someone_else: "e.g., Support Maria's recovery",
                    charity_event: 'e.g., Barangay relief drive 2026'
                };
                var updatePlaceholder = function(){
                    var key = funderSelect.value || 'yourself';
                    titleInput.placeholder = placeholders[key] || 'Clear, compelling title';
                };
                funderSelect.addEventListener('change', updatePlaceholder);
                updatePlaceholder();
            }
        }
        kbfInitDescCounter(document.getElementById('edit-fund-desc'));
        kbfInitTitleCounter(document.getElementById('edit-fund-title'));
        (function(){
            var input = document.getElementById('kbf-goal-amount');
            var out = document.getElementById('kbf-fee-preview');
            if(!input || !out) return;
              var rate = <?php echo (bool)kbf_get_setting('kbf_disable_platform_fee', false) ? '0' : '0.03'; ?>;
            /**
             * @function  sanitizeMoney
             * @purpose   Handles sanitizeMoney behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    value: any - parameter
             * @returns   void
             * @status    ACTIVE
             */
            function sanitizeMoney(value){
                var v = String(value || '').replace(/[^\d.]/g, '');
                var parts = v.split('.');
                var intPart = parts[0] || '';
                var decPart = parts.slice(1).join('');
                if (decPart.length > 2) decPart = decPart.slice(0, 2);
                if (intPart === '' && decPart) intPart = '0';
                return { intPart: intPart, decPart: decPart };
            }
            /**
             * @function  formatWithCommas
             * @purpose   Handles formatWithCommas behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    intPart: any - parameter
             * @returns   void
             * @status    ACTIVE
             */
            function formatWithCommas(intPart){
                return intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
            /**
             * @function  formatMoneyInput
             * @purpose   Handles formatMoneyInput behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    value: any - parameter
             * @returns   void
             * @status    ACTIVE
             */
            function formatMoneyInput(value){
                var s = sanitizeMoney(value);
                var intPart = s.intPart.replace(/^0+(?=\d)/, '');
                if (intPart === '') intPart = '0';
                var formatted = formatWithCommas(intPart);
                if (s.decPart) formatted += '.' + s.decPart;
                var raw = intPart + (s.decPart ? '.' + s.decPart : '');
                return { formatted: formatted, raw: raw };
            }
            /**
             * @function  parseMoney
             * @purpose   Handles parseMoney behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    value: any - parameter
             * @returns   void
             * @status    ACTIVE
             */
            function parseMoney(value){
                var raw = String(value || '').replace(/,/g, '');
                var num = parseFloat(raw);
                return isNaN(num) ? 0 : num;
            }
            /**
             * @function  fmt
             * @purpose   Handles fmt behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    n: any - parameter
             * @returns   void
             * @status    ACTIVE
             */
            function fmt(n){
                return n.toLocaleString('en-US',{minimumFractionDigits:2, maximumFractionDigits:2});
            }
            /**
             * @function  render
             * @purpose   Handles render behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    none
             * @returns   void
             * @status    ACTIVE
             */
            function render(){
                var val = parseMoney(input.dataset.kbfRaw || input.value);
                var cut = val * rate;
                var net = Math.max(0, val - cut);
                var peso = String.fromCharCode(8369);
                out.innerHTML = 'Platform cut: ' + peso + fmt(cut) + ' &nbsp;&bull;&nbsp; Net goal: ' + peso + fmt(net);
            }
            input.addEventListener('input', function(){
                var before = input.value;
                var fm = formatMoneyInput(before);
                input.value = fm.formatted;
                input.dataset.kbfRaw = fm.raw;
                render();
            });
            render();
        })();
    })();

    /**
     * @function  kbfSubmitCreate
     * @purpose   Handles kbfSubmitCreate behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSubmitCreate() {
        if (typeof window.kbfCreateSubmit === 'function') {
            window.kbfCreateSubmit();
            return;
        }
        const form = document.getElementById('kbf-create-fund-form');
        const btn  = document.getElementById('kbf-create-submit');
        const msg  = document.getElementById('kbf-create-msg');
        const invalid = kbfValidateCreateForm(form);
        if (invalid) {
          if (typeof invalid.scrollIntoView === 'function') {
            invalid.scrollIntoView({ behavior:'smooth', block:'center' });
          }
          try { invalid.focus({ preventScroll:true }); }
          catch(e) { invalid.focus(); }
            kbfSetLoadingPage(false);
            if (btn) kbfSetBtnLoading(btn,false);
            return;
        }
        kbfSetLoadingPage(true);
        kbfSetBtnLoading(btn, true, 'Submitting...');
        kbfSetSkeleton(msg, true);
        if (typeof window.ajaxurl === 'undefined' || !window.ajaxurl) {
            kbfSetLoadingPage(false);
            kbfSetBtnLoading(btn,false);
            kbfSetSkeleton(msg,false);
            if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Submit failed: ajaxurl is not defined.</div>';
            return;
        }
        if (window.kbfBenefitsEditors && window.kbfBenefitsEditors.create) {
            window.kbfBenefitsEditors.create.sync();
        }
        const fd = new FormData(form);
        if (typeof getCreateBenefitsPayload === 'function') {
            fd.set('benefits', getCreateBenefitsPayload());
        }
        var goalInput = document.getElementById('kbf-goal-amount');
        if (goalInput) {
            var goalRaw = (goalInput.dataset.kbfRaw || goalInput.value || '').replace(/,/g, '');
            fd.set('goal_amount', goalRaw);
        }
        var provEl = document.getElementById('kbf-province');
        var muniEl = document.getElementById('kbf-municipality');
        var brgyEl = document.getElementById('kbf-barangay');
        var prov = provEl ? provEl.value : '';
        var muni = muniEl ? muniEl.value : '';
        var brgy = brgyEl ? brgyEl.value : '';
        var parts = [];
        if (brgy) parts.push(brgy);
        if (muni) parts.push(muni);
        if (prov) parts.push(prov);
        if (parts.length) fd.append('location_full', parts.join(', '));
        fd.append('action', 'kbf_create_fund');
        fd.append('nonce', '<?php echo $nonce_create; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(function(r){
            return r.text().then(function(t){ return { ok: r.ok, status: r.status, text: t }; });
        }).then(function(res){
            var json = null;
            try { json = JSON.parse(res.text); } catch(e) {}
            var ok = false;
            if (json) {
                ok = (json.success === true || json.success === 1 || json.success === '1' || json.success === 'true' || json.status === 'success');
                if (!ok && json.data && (json.data.success === true || json.data.success === 1 || json.data.success === '1')) ok = true;
                if (!ok && json.data && json.data.status === 'success') ok = true;
                if (!ok && json.data && json.data.message && !/error|fail|invalid/i.test(String(json.data.message))) ok = true;
            } else if (res.ok) {
                ok = true;
            }
            if (msg) {
                if (json && json.data && json.data.message) {
                    msg.innerHTML = '<div class="kbf-alert kbf-alert-'+(ok?'success':'error')+'">'+window.kbfEscapeHtml(json.data.message)+'</div>';
                } else if (!ok) {
                    msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Submission failed. Please try again.</div>';
                }
            }
        if(ok) {
            if (window.kbfClearCreateDraft) window.kbfClearCreateDraft();
            kbfCloseModal('kbf-modal-create');
            var modal = document.getElementById('kbf-modal-create');
            if (modal) {
                modal.classList.remove('is-open');
                setTimeout(function(){ modal.style.display = 'none'; }, 220);
            }
            // keep page scroll enabled
            var dashUrl = '<?php echo esc_url(add_query_arg('kbf_tab','overview', function_exists('kbf_get_page_url') ? kbf_get_page_url('dashboard') : home_url('/'))); ?>';
                setTimeout(function(){ window.location.href = dashUrl; }, 600);
            } else {
                kbfSetLoadingPage(false);
                kbfSetBtnLoading(btn,false);
                kbfSetSkeleton(msg,false);
            }
        }).catch(function(){
            kbfSetLoadingPage(false);
            kbfSetBtnLoading(btn,false);
            kbfSetSkeleton(msg,false);
        });
    }

    /**
     * @function  kbfSubmitEdit
     * @purpose   Handles kbfSubmitEdit behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSubmitEdit() {
        const form = document.getElementById('kbf-edit-fund-form');
        const btn  = document.querySelector('#kbf-modal-edit .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-edit-msg');
        var editInputEl = document.getElementById('kbf-edit-photos');
        var editMaxBytes = (typeof EDIT_MAX_PHOTO_BYTES !== 'undefined' && EDIT_MAX_PHOTO_BYTES) ? EDIT_MAX_PHOTO_BYTES : (5 * 1024 * 1024);
        var editFilesSafe = [];
        if (typeof kbfEditFiles !== 'undefined' && Array.isArray(kbfEditFiles)) {
            editFilesSafe = kbfEditFiles;
        } else if (editInputEl && editInputEl.files) {
            editFilesSafe = Array.from(editInputEl.files || []);
        }
        if (editFilesSafe.some(function(f){ return f && typeof f.size === 'number' && f.size >= editMaxBytes; })) {
            if (typeof kbfSetEditPhotoError === 'function') kbfSetEditPhotoError('Please upload photos below 5MB.');
            if (editInputEl) editInputEl.focus();
            return;
        } else if (typeof kbfSetEditPhotoError === 'function') {
            kbfSetEditPhotoError('');
        }
        if (window.kbfBenefitsEditors && window.kbfBenefitsEditors.edit) {
            var invalidBenefit = window.kbfBenefitsEditors.edit.validate();
            if (invalidBenefit) {
                invalidBenefit.focus();
                return;
            }
        }
        const invalid = kbfValidateEditFund(form);
        if (invalid) {
            invalid.focus();
            return;
        }
        var eProv = document.getElementById('kbf-edit-province');
        var eMuni = document.getElementById('kbf-edit-municipality');
        var eBrgy = document.getElementById('kbf-edit-barangay');
        /**
         * @function  kbfGetSelectValueOrLabel
         * @purpose   Handles kbfGetSelectValueOrLabel behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    sel: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function kbfGetSelectValueOrLabel(sel){
            if (!sel) return '';
            var val = String(sel.value || '').trim();
            if (val) return val;
            var labelEl = sel._kbfLabel || (sel._kbfDisplay ? sel._kbfDisplay.querySelector('span') : null);
            var label = labelEl ? String(labelEl.textContent || '').trim() : '';
            if (!label) return '';
            if (/^select\s+/i.test(label)) return '';
            return label;
        }
        var eProvVal = kbfGetSelectValueOrLabel(eProv);
        var eMuniVal = kbfGetSelectValueOrLabel(eMuni);
        var eBrgyVal = kbfGetSelectValueOrLabel(eBrgy);

        // If municipality is missing but we have province + barangay, derive it from PSGC before submit.
        if (!eMuniVal && eProvVal && eBrgyVal && typeof kbfEnsurePsgc === 'function' && !window.__kbfEditLocResolving) {
            window.__kbfEditLocResolving = true;
            kbfEnsurePsgc(function(){
                try {
                    var muniData = kbfBuildMunicipalities(String(eProvVal).toUpperCase());
                    var brgyLower = String(eBrgyVal).toLowerCase();
                    var muniFound = '';
                    for (var mi=0; mi<muniData.length; mi++){
                        var bList = muniData[mi].barangays || [];
                        for (var bi=0; bi<bList.length; bi++){
                            if (String(bList[bi]).toLowerCase() === brgyLower){
                                muniFound = muniData[mi].label;
                                break;
                            }
                        }
                        if (muniFound) break;
                    }
                    if (muniFound && eMuni) {
                        eMuni.value = muniFound;
                        if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(eMuni);
                    }
                } catch(e){}
                window.__kbfEditLocResolving = false;
                kbfSubmitEdit();
            });
            return;
        }

        kbfSetBtnLoading(btn, true, 'Saving...');
        kbfSetSkeleton(msg, true);
        if (window.kbfBenefitsEditors && window.kbfBenefitsEditors.edit) {
            window.kbfBenefitsEditors.edit.sync();
        }
        const fd = new FormData(form);
        var goalInput = document.getElementById('kbf-goal-amount');
        if (goalInput) {
            var goalRaw = (goalInput.dataset.kbfRaw || goalInput.value || '').replace(/,/g, '');
            fd.set('goal_amount', goalRaw);
        }
        var removedInput = document.getElementById('kbf-edit-removed-photos');
        if (removedInput && removedInput.value) {
            fd.set('remove_photos', removedInput.value);
        }
        if (eProv) fd.set('province', eProvVal || '');
        if (eMuni) fd.set('municipality', eMuniVal || '');
        if (eBrgy) fd.set('barangay', eBrgyVal || '');
        var editLocParts = [];
        if (eBrgyVal) editLocParts.push(eBrgyVal);
        if (eMuniVal) editLocParts.push(eMuniVal);
        if (eProvVal) editLocParts.push(eProvVal);
        var editLocFull = editLocParts.join(', ');
        var hiddenLoc = document.getElementById('edit-fund-location-hidden');
        if (hiddenLoc) hiddenLoc.value = editLocFull;
        fd.set('location', editLocFull);
        var eParts = [];
        if (eBrgyVal) eParts.push(eBrgyVal);
        if (eMuniVal) eParts.push(eMuniVal);
        if (eProvVal) eParts.push(eProvVal);
        if (eParts.length) fd.append('location_full', eParts.join(', '));
        fd.append('action', 'kbf_update_fund');
        fd.append('nonce', '<?php echo $nonce_edit; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.text()).then(t=>{
            var json = null;
            try {
                var cleaned = String(t || '').replace(/^\uFEFF/, '').trim();
                var start = cleaned.indexOf('{');
                var end = cleaned.lastIndexOf('}');
                var payload = (start !== -1 && end !== -1 && end > start) ? cleaned.slice(start, end + 1) : cleaned;
                json = JSON.parse(payload);
            } catch(e) {
                json = { success: false, data: { message: 'Invalid server response. Please try again.' } };
            }
            const m = document.getElementById('kbf-edit-msg');
            if (m) {
                if (json.success) {
                    m.innerHTML = '';
                } else {
                    m.innerHTML = '<div class="kbf-alert kbf-alert-error">'+window.kbfEscapeHtml(json.data && json.data.message ? json.data.message : 'Update failed.')+'</div>';
                }
            }
            if(json.success) {
                kbfSetBtnLoading(btn,false);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Save Changes';
                    if (btn.dataset) delete btn.dataset.kbfLabel;
                }
                kbfSetSkeleton(msg,false);
                var editModal = document.getElementById('kbf-modal-edit');
                if (editModal) editModal.classList.add('is-success');
                try { localStorage.setItem('kbf_fund_updated', String(Date.now())); } catch(e){}
                setTimeout(function(){
                    kbfCloseModal('kbf-modal-edit');
                    setTimeout(function(){ window.location.reload(); }, 220);
                }, 1200);
            } else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ 
            kbfSetBtnLoading(btn,false); 
            kbfSetSkeleton(msg,false); 
        });
    }

    /**
     * @function  kbfSubmitWd
     * @purpose   Handles kbfSubmitWd behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSubmitWd() {
        const form = document.getElementById('kbf-wd-form');
        const btn  = document.querySelector('#kbf-modal-wd .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-wd-msg');
        kbfSetBtnLoading(btn, true, 'Submitting...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        fd.append('action', 'kbf_request_withdrawal');
        fd.append('nonce', '<?php echo $nonce_wd; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-wd-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+window.kbfEscapeHtml(json.data.message)+'</div>';
            if(json.success) {
                kbfSetBtnLoading(btn,false);
                kbfSetSkeleton(msg,false);
                setTimeout(function(){ kbfCloseModal('kbf-modal-wd'); }, 1600);
            } else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }
    (function(){
        var form = document.getElementById('kbf-wd-form');
        if (!form || form.dataset.bound) return;
        form.dataset.bound = '1';
        form.addEventListener('submit', function(e){
            e.preventDefault();
            kbfSubmitWd();
        });
    })();

    /**
     * @function  kbfSubmitAppeal
     * @purpose   Handles kbfSubmitAppeal behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    nonce: any - parameter
     * @returns   void
     * @status    NEEDS REVIEW
     */
    function kbfSubmitAppeal(nonce) {
        const form = document.getElementById('kbf-appeal-form');
        const btn  = document.querySelector('#kbf-modal-appeal .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-appeal-msg');
        kbfSetBtnLoading(btn, true, 'Submitting...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        fd.append('action', 'kbf_submit_appeal');
        fd.append('nonce', nonce);
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            msg.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+window.kbfEscapeHtml(json.data.message)+'</div>';
            if (json.success) setTimeout(()=>{ kbfCloseModal('kbf-modal-appeal'); }, 1600);
            else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

    /**
     * @function  kbfOpenEdit
     * @purpose   Handles kbfOpenEdit behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    id: any - parameter; title: any - parameter; desc: any - parameter; loc: any - parameter; deadline: any - parameter; autoReturn: any - parameter; photosJson: any - parameter; benefitsJson: any - parameter
     * @returns   void
     * @status    NEEDS REVIEW
     */
    window.kbfOpenEdit = function(id, title, desc, loc, deadline, autoReturn, photosJson, benefitsJson) {
        var editModal = document.getElementById('kbf-modal-edit');
        if (editModal) editModal.classList.remove('is-success');
        document.getElementById('edit-fund-id').value = id;
        document.getElementById('edit-fund-title').value = title;
        document.getElementById('edit-fund-desc').value = desc;
        var editBtn = document.getElementById('kbf-edit-submit');
        if (editBtn) {
            editBtn.disabled = false;
            editBtn.innerHTML = 'Save Changes';
            editBtn.classList.remove('is-loading');
            if (editBtn.dataset) delete editBtn.dataset.kbfLabel;
        }
        var editMsg = document.getElementById('kbf-edit-msg');
        if (editMsg) editMsg.innerHTML = '';
        if (window.kbfResetEditPhotos) window.kbfResetEditPhotos();
        if (window.kbfSetEditExistingPhotos) {
            var existing = [];
            try {
                existing = photosJson ? JSON.parse(photosJson) : [];
            } catch(e) { existing = []; }
            window.kbfSetEditExistingPhotos(existing);
        }
        if (window.kbfBenefitsEditors && window.kbfBenefitsEditors.edit) {
            var benefitList = [];
            try {
                benefitList = benefitsJson ? JSON.parse(benefitsJson) : [];
            } catch(e) { benefitList = []; }
            window.kbfBenefitsEditors.edit.set(Array.isArray(benefitList) ? benefitList : []);
        }
        var hiddenLoc = document.getElementById('edit-fund-location-hidden');
        if (hiddenLoc) hiddenLoc.value = loc || '';
        var titleCounter = document.getElementById('edit-fund-title').parentNode.querySelector('.kbf-title-counter');
        if (titleCounter) titleCounter.textContent = (title || '').length + ' / 150';
        var deadlineEl = document.getElementById('edit-fund-deadline');
        if (deadlineEl) {
          deadlineEl.value = deadline || '';
          if (!deadlineEl.dataset.kbfDeadlineBound) {
            deadlineEl.addEventListener('change', function(){
              kbfValidateDateMinField(deadlineEl, 'Please select a deadline at least 7 days from today.');
              kbfRenderEditDeadlineMeta();
            });
            deadlineEl.addEventListener('focus', kbfRenderEditDeadlineMeta);
            deadlineEl.addEventListener('click', kbfRenderEditDeadlineMeta);
            deadlineEl.dataset.kbfDeadlineBound = '1';
          }
        }
        kbfRenderEditDeadlineMeta();
        var autoEl = document.getElementById('edit-fund-auto-return');
        if (autoEl) autoEl.checked = String(autoReturn) === '1';
        kbfApplyLocationSelection(
            document.getElementById('kbf-edit-province'),
            document.getElementById('kbf-edit-municipality'),
            document.getElementById('kbf-edit-barangay'),
            loc
        );
        var counter = document.getElementById('edit-fund-desc').parentNode.querySelector('.kbf-desc-counter');
        if (counter) counter.textContent = (desc || '').length + ' / 800';
        if (typeof kbfSetEditStep === 'function') kbfSetEditStep(1);
        // Hard reset footer buttons to step 1 state
        var editPrev = document.getElementById('kbf-edit-prev');
        var editNext = document.getElementById('kbf-edit-next');
        var editSubmit = document.getElementById('kbf-edit-submit');
        if (editPrev) { editPrev.disabled = true; editPrev.dataset.step = '1'; }
        if (editNext) { editNext.style.display = ''; editNext.dataset.step = '1'; }
        if (editSubmit) { editSubmit.style.display = 'none'; }
        kbfOpenModal('kbf-modal-edit');
    };

    /**
     * @function  kbfOpenWd
     * @purpose   Handles kbfOpenWd behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    fundId: any - parameter; available: any - parameter; title: any - parameter
     * @returns   void
     * @status    NEEDS REVIEW
     */
    window.kbfOpenWd = function(fundId, available, title) {
        document.getElementById('wd-fund-id').value = fundId;
        document.getElementById('wd-fund-title').textContent = title || 'Fund #'+fundId;
        var peso = String.fromCharCode(8369);
        document.getElementById('wd-available-label').textContent = peso + parseFloat(available).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        document.getElementById('wd-amount').max = available;
        document.getElementById('wd-amount').placeholder = 'Max ' + peso + parseFloat(available).toLocaleString('en-PH',{minimumFractionDigits:2});
        document.getElementById('kbf-wd-msg').innerHTML = '';
        document.getElementById('kbf-wd-form').reset();
        document.getElementById('wd-fund-id').value = fundId; // re-set after reset
        kbfOpenModal('kbf-modal-wd');
    };

    /**
     * @function  kbfOpenAppeal
     * @purpose   Handles kbfOpenAppeal behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    fundId: any - parameter; title: any - parameter
     * @returns   void
     * @status    NEEDS REVIEW
     */
    window.kbfOpenAppeal = function(fundId, title) {
        document.getElementById('kbf-appeal-fund-id').value = fundId;
        document.getElementById('kbf-appeal-msg').innerHTML = '';
        kbfOpenModal('kbf-modal-appeal');
    };

    var kbfMilestoneFiles = [];
    var milestoneInput = document.getElementById('kbf-milestone-photos');
    var milestoneWrap = document.getElementById('kbf-milestone-photo-previews');
    var MILESTONE_MAX_PHOTO_BYTES = 5 * 1024 * 1024; // < 5MB only
    function kbfSetMilestonePhotoError(message){
        if (!milestoneInput) return;
        var group = milestoneInput.closest('.kbf-form-group');
        if (!group) return;
        var err = group.querySelector('.kbf-field-error');
        if (!err) return;
        err.textContent = message || '';
        err.style.display = message ? 'block' : '';
    }
    /**
     * @function  kbfSyncMilestoneFiles
     * @purpose   Handles kbfSyncMilestoneFiles behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function kbfSyncMilestoneFiles(){
        if (!milestoneInput) return;
        var dt = new DataTransfer();
        kbfMilestoneFiles.forEach(function(f){ dt.items.add(f); });
        milestoneInput.files = dt.files;
    }
    /**
     * @function  kbfRenderMilestoneThumbs
     * @purpose   Handles kbfRenderMilestoneThumbs behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function kbfRenderMilestoneThumbs(){
        if (!milestoneWrap) return;
        milestoneWrap.innerHTML = '';
        kbfMilestoneFiles.forEach(function(file, idx){
            if (!file.type || file.type.indexOf('image/') !== 0) return;
            var reader = new FileReader();
            reader.onload = function(e){
                var thumb = document.createElement('div');
                thumb.className = 'kbf-photo-thumb kbf-photo-slot';
                thumb.setAttribute('data-index', String(idx));
                var img = document.createElement('img');
                img.alt = '';
                img.src = e.target.result;
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'kbf-photo-remove';
                btn.innerHTML = '&times;';
                btn.addEventListener('click', function(){
                    kbfMilestoneFiles.splice(idx, 1);
                    kbfSyncMilestoneFiles();
                    kbfRenderMilestoneThumbs();
                });
                thumb.appendChild(img);
                thumb.appendChild(btn);
                milestoneWrap.appendChild(thumb);
            };
            reader.readAsDataURL(file);
        });
        if (kbfMilestoneFiles.length < 5) {
            var addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'kbf-photo-add';
            addBtn.setAttribute('aria-label', 'Add photos');
            addBtn.innerHTML = '+';
            addBtn.addEventListener('click', function(){
                if (milestoneInput) milestoneInput.click();
            });
            milestoneWrap.appendChild(addBtn);
        }
        if (milestoneInput) {
            milestoneInput.disabled = (kbfMilestoneFiles.length >= 5);
        }
    }
    if (milestoneInput && !milestoneInput.dataset.bound) {
        milestoneInput.dataset.bound = '1';
        milestoneInput.addEventListener('change', function(){
            var files = Array.prototype.slice.call(milestoneInput.files || []);
            var oversizedFound = false;
            files.forEach(function(f){
                if (!f || typeof f.size !== 'number') return;
                if (f.size >= MILESTONE_MAX_PHOTO_BYTES) {
                    oversizedFound = true;
                    return;
                }
                if (kbfMilestoneFiles.length < 5) {
                    kbfMilestoneFiles.push(f);
                }
            });
            if (oversizedFound) {
                kbfSetMilestonePhotoError('Please upload photos below 5MB.');
            } else {
                kbfSetMilestonePhotoError('');
            }
            kbfSyncMilestoneFiles();
            kbfRenderMilestoneThumbs();
        });
    }

    /**
     * @function  kbfOpenMilestoneModal
     * @purpose   Handles kbfOpenMilestoneModal behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    fundId: any - parameter; title: any - parameter
     * @returns   void
     * @status    NEEDS REVIEW
     */
    window.kbfOpenMilestoneModal = function(fundId, title) {
        var milestoneModal = document.getElementById('kbf-modal-milestone');
        if (milestoneModal) milestoneModal.classList.remove('is-success');
        var titleEl = document.getElementById('kbf-milestone-fund-title');
        var idEl = document.getElementById('kbf-milestone-fund-id');
        if (titleEl) titleEl.textContent = title ? ('Fund: ' + title) : '';
        if (idEl) idEl.value = fundId || '';
        var form = document.getElementById('kbf-milestone-form');
        if (form) form.reset();
        var msg = document.getElementById('kbf-milestone-msg');
        if (msg) msg.innerHTML = '';
        kbfMilestoneFiles = [];
        kbfSyncMilestoneFiles();
        kbfRenderMilestoneThumbs();
        if (form) {
            var titleInput = form.querySelector('input[name="milestone_title"]');
            var bodyInput = form.querySelector('textarea[name="milestone_body"]');
            var titleCounter = form.querySelector('.kbf-title-counter');
            var descCounter = form.querySelector('.kbf-desc-counter');
            if (titleCounter) titleCounter.textContent = (titleInput && titleInput.value ? titleInput.value.length : 0) + ' / 150';
            if (descCounter) descCounter.textContent = (bodyInput && bodyInput.value ? bodyInput.value.length : 0) + ' / 300';
            form.querySelectorAll('.kbf-field-error').forEach(function(el){ el.textContent = ''; el.style.display = ''; });

            /**
             * @function  bindLiveCounter
             * @purpose   Handles bindLiveCounter behavior in the dashboard script flow
             * @used-by   [same file references detected]
             * @calls     [none explicitly documented]
             * @params    input: any - parameter; counterEl: any - parameter; max: any - parameter
             * @returns   void
             * @status    ACTIVE
             */
            function bindLiveCounter(input, counterEl, max){
                if (!input || !counterEl) return;
                if (input.dataset.counterBound) return;
                input.dataset.counterBound = '1';
                var update = function(){
                    var len = (input.value || '').length;
                    counterEl.textContent = len + ' / ' + max;
                };
                input.addEventListener('input', update);
                update();
            }
            bindLiveCounter(titleInput, titleCounter, 150);
            bindLiveCounter(bodyInput, descCounter, 300);
        }
        var saveBtn = document.getElementById('kbf-milestone-save');
        if (saveBtn && !saveBtn.dataset.bound) {
            saveBtn.dataset.bound = '1';
            saveBtn.addEventListener('click', kbfHandleMilestoneSave);
        }
        kbfOpenModal('kbf-modal-milestone');
    };
    /**
     * @function  kbfHandleMilestoneSave
     * @purpose   Handles kbfHandleMilestoneSave behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    function kbfHandleMilestoneSave(){
        var saveBtn = document.getElementById('kbf-milestone-save');
        var form = document.getElementById('kbf-milestone-form');
        var msg = document.getElementById('kbf-milestone-msg');
        if (!form) return;
        var title = form.querySelector('input[name="milestone_title"]');
        var body = form.querySelector('textarea[name="milestone_body"]');
        var photos = form.querySelector('#kbf-milestone-photos');
        var titleCounter = form.querySelector('.kbf-title-counter');
        var descCounter = form.querySelector('.kbf-desc-counter');
        var errors = form.querySelectorAll('.kbf-field-error');
        errors.forEach(function(el){ el.textContent=''; el.style.display=''; });
        if (titleCounter) titleCounter.textContent = (title && title.value ? title.value.length : 0) + ' / 150';
        if (descCounter) descCounter.textContent = (body && body.value ? body.value.length : 0) + ' / 300';

        /**
         * @function  setErr
         * @purpose   Handles setErr behavior in the dashboard script flow
         * @used-by   [same file references detected]
         * @calls     [none explicitly documented]
         * @params    input: any - parameter; message: any - parameter
         * @returns   void
         * @status    ACTIVE
         */
        function setErr(input, message){
            if (!input) return;
            var group = input.closest('.kbf-form-group');
            if (!group) return;
            var err = group.querySelector('.kbf-field-error');
            if (err) {
                err.textContent = message;
                err.style.display = 'block';
            }
        }

        var hasError = false;
        var titleVal = title ? title.value.trim() : '';
        var bodyVal = body ? body.value.trim() : '';
        if (!titleVal) { setErr(title, 'Story title is required.'); hasError = true; }
        if (!bodyVal) { setErr(body, 'Update details are required.'); hasError = true; }
        if (titleVal.length > 150) { setErr(title, 'Title must be 150 characters or fewer.'); hasError = true; }
        if (bodyVal.length > 300) { setErr(body, 'Description must be 300 characters or fewer.'); hasError = true; }

        if (photos && photos.files && photos.files.length > 5) {
            setErr(photos, 'Please upload up to 5 photos only.');
            hasError = true;
        }
        if (kbfMilestoneFiles.some(function(f){ return f && typeof f.size === 'number' && f.size >= MILESTONE_MAX_PHOTO_BYTES; })) {
            setErr(photos, 'Please upload photos below 5MB.');
            hasError = true;
        }

        if (hasError) {
            if (msg) msg.innerHTML = '';
            return;
        }
        if (typeof window.ajaxurl === 'undefined' || !window.ajaxurl) {
            if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Submit failed: ajaxurl is not defined.</div>';
            return;
        }
        if (milestoneInput) milestoneInput.disabled = false;
        var fd = new FormData(form);
        fd.append('action','kbf_add_milestone');
        fd.append('nonce','<?php echo wp_create_nonce('kbf_add_milestone'); ?>');
        if (saveBtn) kbfSetBtnLoading(saveBtn, true, 'Saving...');
        kbfSetSkeleton(msg, true);
        fetch(ajaxurl, { method:'POST', body:fd })
        .then(r=>r.text()).then(t=>{
            var json = null;
            try {
                var cleaned = String(t || '').replace(/^\uFEFF/, '').trim();
                var start = cleaned.indexOf('{');
                var end = cleaned.lastIndexOf('}');
                var payload = (start !== -1 && end !== -1 && end > start) ? cleaned.slice(start, end + 1) : cleaned;
                json = JSON.parse(payload);
            } catch(e) {
                json = { success:false, data:{ message:'Invalid server response. Please try again.', raw: t } };
            }
            if (msg) {
                var extra = (json.data && json.data.raw) ? ('<div style="margin-top:6px;font-size:11px;opacity:.7;word-break:break-word;">'+window.kbfEscapeHtml(String(json.data.raw).slice(0,280))+'</div>') : '';
                msg.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+window.kbfEscapeHtml(json.data && json.data.message ? json.data.message : 'Save failed.')+extra+'</div>';
            }
            if (saveBtn) kbfSetBtnLoading(saveBtn, false);
            kbfSetSkeleton(msg, false);
            if (json.success) {
                // Dynamic refresh: update milestones tab without full page reload
                if (json.data && json.data.milestone) {
                    kbfPrependMilestoneToUI(json.data.milestone);
                }
                if (typeof window.kbfShowTransientNotice === 'function') {
                    window.kbfShowTransientNotice('Story saved successfully.', 'success');
                }
                var milestoneModalSuccess = document.getElementById('kbf-modal-milestone');
                if (milestoneModalSuccess) milestoneModalSuccess.classList.add('is-success');
                setTimeout(function(){
                    kbfCloseModal('kbf-modal-milestone');
                }, 1100);
            }
        }).catch(()=>{
            if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Request failed.</div>';
            if (saveBtn) kbfSetBtnLoading(saveBtn, false);
            kbfSetSkeleton(msg, false);
        });
    }

    /**
     * @function  kbfCancelFund
     * @purpose   Handles kbfCancelFund behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    fundId: any - parameter
     * @returns   void
     * @status    NEEDS REVIEW
     */
    window.kbfCancelFund = function(fundId) {
        if (!confirm('Cancel this fund? This cannot be undone.')) return;
        const fd = new FormData();
        fd.append('action','kbf_cancel_fund'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo $nonce_cancel; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };

    /**
     * Dynamically prepends a new milestone to the Stories tab without page reload.
     * @param {Object} ms - Milestone object from server response
     */
    window.kbfPrependMilestoneToUI = function(ms){
        var container = document.querySelector('.kbf-section-milestones');
        if (!container) return;
        var dateStr = ms.created_at ? new Date(ms.created_at.replace(' ','T')).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '';
        var photosHtml = '';
        if (ms.photos && Array.isArray(ms.photos) && ms.photos.length) {
            photosHtml = '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">';
            ms.photos.forEach(function(p){
                var url = (typeof p === 'object' && p.url) ? p.url : (typeof p === 'string' ? p : '');
                url = window.kbfSafeUrl(url);
                if (url) photosHtml += '<img src="'+window.kbfEscapeHtml(url)+'" alt="Story photo" style="width:110px;height:82px;object-fit:cover;border-radius:8px;border:1px solid var(--kbf-border);">';
            });
            photosHtml += '</div>';
        }
        var titleHtml = ms.title ? '<div style="font-weight:600;color:var(--kbf-navy);margin-bottom:4px;">'+window.kbfEscapeHtml(ms.title)+'</div>' : '';
        var dateHtml = dateStr ? '<div style="font-size:11.5px;color:var(--kbf-slate);margin-bottom:6px;">'+dateStr+'</div>' : '';
        var bodyHtml = ms.body ? '<div style="font-size:13px;color:var(--kbf-text-sm);line-height:1.6;">'+window.kbfEscapeHtml(ms.body).replace(/\n/g,'<br>')+'</div>' : '';
        var cardHtml = '<div style="border:1px solid var(--kbf-border);border-radius:12px;padding:12px;background:#fff;animation:kbfFadeIn .3s ease;">'+titleHtml+dateHtml+bodyHtml+photosHtml+'</div>';
        var grid = container.querySelector('div[style*="display:grid"]');
        if (!grid) {
            var emptyMsg = container.querySelector('div[style*="text-align:center"]');
            if (emptyMsg) emptyMsg.remove();
            grid = document.createElement('div');
            grid.style.cssText = 'display:grid;gap:12px;';
            container.appendChild(grid);
        }
        grid.insertAdjacentHTML('afterbegin', cardHtml);
    };

    var kbfTrashFundId = null;
    var kbfTrashMode = 'cancel';
    var kbfTrashInFlight = false;
    /**
     * @function  kbfOpenTrashFund
     * @purpose   Handles kbfOpenTrashFund behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    fundId: any - parameter; title: any - parameter; mode: any - parameter
     * @returns   void
     * @status    NEEDS REVIEW
     */
    window.kbfOpenTrashFund = function(fundId, title, mode){
        kbfTrashFundId = fundId || null;
        kbfTrashMode = (mode === 'trash') ? 'trash' : 'cancel';
        var titleEl = document.getElementById('kbf-trash-title');
        var msgEl = document.getElementById('kbf-trash-message');
        if (kbfTrashMode === 'trash') {
            if (titleEl) titleEl.textContent = 'Cancel Campaign';
            if (msgEl) msgEl.textContent = 'This will permanently delete the fundraiser and its records. This cannot be undone. Are you sure you want to continue?';
        } else {
            if (titleEl) titleEl.textContent = 'Cancel Campaign';
            if (msgEl) msgEl.textContent = "This will move the fundraiser to cancelled status and it won't be visible to sponsors. Are you sure you want to continue?";
        }
        kbfOpenModal('kbf-modal-trash-fund');
    };
    /**
     * @function  kbfConfirmTrashFund
     * @purpose   Handles kbfConfirmTrashFund behavior in the dashboard script flow
     * @used-by   [same file references detected]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    ACTIVE
     */
    window.kbfConfirmTrashFund = function(){
        if (!kbfTrashFundId) return;
        if (kbfTrashInFlight) return;
        if (typeof window.ajaxurl === 'undefined' || !window.ajaxurl) {
            console.error('kbfConfirmTrashFund: ajaxurl is not defined');
            alert('Request failed: ajaxurl is not defined.');
            return;
        }
        kbfTrashInFlight = true;
        const fd = new FormData();
        fd.append('action', kbfTrashMode === 'trash' ? 'kbf_trash_fund' : 'kbf_cancel_fund');
        fd.append('fund_id', kbfTrashFundId);
        fd.append('nonce', kbfTrashMode === 'trash' ? '<?php echo $nonces['trash']; ?>' : '<?php echo $nonce_cancel; ?>');
        kbfCloseModal('kbf-modal-trash-fund');
        kbfSetLoadingPage(true);
        fetch(ajaxurl,{method:'POST',body:fd})
          .then(function(r){
              return r.text().then(function(t){ return { ok: r.ok, status: r.status, text: t }; });
          })
          .then(function(res){
              var j = null;
              var cleaned = String(res.text || '').replace(/^\uFEFF/, '').trim();
              try { j = JSON.parse(cleaned); } catch(e) {}
              if (j && j.success) {
                  location.reload();
                  return;
              }
              kbfSetLoadingPage(false);
              kbfTrashInFlight = false;
              if (!j) {
                  console.error('kbfConfirmTrashFund: non-JSON response', res.status, res.text);
                  alert('Request failed. Please try again.');
                  return;
              }
              console.error('kbfConfirmTrashFund: error payload', j);
              alert((j.data && j.data.message) ? j.data.message : 'Unable to trash fund.');
          })
          .catch(function(err){
              kbfSetLoadingPage(false);
              kbfTrashInFlight = false;
              console.error('kbfConfirmTrashFund: request failed', err);
              alert('Request failed. Please try again.');
          });
    };

    var kbfEscrowFundId = null;
    var kbfEscrowInFlight = false;
    /**
     * @function  kbfOpenEscrowRequest
     * @purpose   Handles kbfOpenEscrowRequest behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    fundId: any - parameter
     * @returns   void
     * @status    NEEDS REVIEW
     */
    window.kbfOpenEscrowRequest = function(fundId){
        kbfEscrowFundId = fundId || null;
        kbfOpenModal('kbf-modal-escrow-request');
    };
    /**
     * @function  kbfConfirmEscrowRequest
     * @purpose   Handles kbfConfirmEscrowRequest behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    none
     * @returns   void
     * @status    NEEDS REVIEW
     */
    window.kbfConfirmEscrowRequest = function(){
        if (!kbfEscrowFundId) return;
        if (kbfEscrowInFlight) return;
        if (typeof window.ajaxurl === 'undefined' || !window.ajaxurl) {
            alert('Request failed: ajaxurl is not defined.');
            return;
        }
        kbfEscrowInFlight = true;
        kbfCloseModal('kbf-modal-escrow-request');
        kbfSetLoadingPage(true);
        const fd = new FormData();
        fd.append('action','kbf_request_escrow');
        fd.append('fund_id', kbfEscrowFundId);
        fd.append('nonce','<?php echo $nonce_escrow; ?>');
        fetch(ajaxurl,{method:'POST',body:fd})
          .then(function(r){ return r.text().then(function(t){ return { ok:r.ok, status:r.status, text:t }; }); })
          .then(function(res){
              var j = null;
              var cleaned = String(res.text || '').replace(/^\uFEFF/, '').trim();
              try { j = JSON.parse(cleaned); } catch(e) {}
              if (j && j.success) {
                  location.reload();
                  return;
              }
              kbfSetLoadingPage(false);
              kbfEscrowInFlight = false;
              if (!j) { alert('Request failed. Please try again.'); return; }
              alert((j.data && j.data.message) ? j.data.message : 'Unable to submit request.');
          })
          .catch(function(){
              kbfSetLoadingPage(false);
              kbfEscrowInFlight = false;
              alert('Request failed. Please try again.');
          });
    };

    /**
     * @function  kbfExtendDeadline
     * @purpose   Handles kbfExtendDeadline behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    fundId: any - parameter
     * @returns   void
     * @status    NEEDS REVIEW
     */
    window.kbfExtendDeadline = function(fundId) {
        const d = prompt('New deadline (YYYY-MM-DD):'); if(!d) return;
        const fd = new FormData();
        fd.append('action','kbf_extend_deadline'); fd.append('fund_id',fundId);
        fd.append('deadline',d); fd.append('nonce','<?php echo $nonce_extend; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };


    /**
     * @function  kbfMarkComplete
     * @purpose   Handles kbfMarkComplete behavior in the dashboard script flow
     * @used-by   [no confirmed caller in this file]
     * @calls     [none explicitly documented]
     * @params    fundId: any - parameter
     * @returns   void
     * @status    NEEDS REVIEW
     */
    window.kbfMarkComplete = function(fundId) {
        if(!confirm('Mark this fund as complete?')) return;
        const fd = new FormData();
        fd.append('action','kbf_mark_fund_complete'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo $nonces['complete']; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };
    </script>

    <script>
      // Auto-refresh disabled per request.
    </script>
