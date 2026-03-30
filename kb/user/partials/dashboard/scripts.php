        <!-- ================== JS ================== -->
    <script>if(typeof ajaxurl==='undefined') var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';</script>
    <script>
      (function(){
        var btn = document.getElementById('kbf-user-menu-btn');
        var dd = document.getElementById('kbf-user-dropdown');
        if(!btn || !dd) return;
        function closeMenu(){
          dd.classList.remove('kbf-open');
          btn.setAttribute('aria-expanded','false');
        }
        btn.addEventListener('click', function(e){
          e.stopPropagation();
          var isOpen = dd.classList.toggle('kbf-open');
          btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        document.addEventListener('click', function(){ closeMenu(); });
        document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeMenu(); });
      })();
    </script>
    <script>
      // preload removed
      (function(){
        var topbar = document.querySelector('.kbf-topbar');
        if (!topbar) return;
        function onScroll(){
          if (window.scrollY > 10) topbar.classList.add('kbf-topbar-scrolled');
          else topbar.classList.remove('kbf-topbar-scrolled');
        }
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
      })();

      function kbfToggleMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (!menu || !overlay) return;
        menu.classList.toggle('kbf-menu-open');
        overlay.classList.toggle('kbf-overlay-open');
      }
      function kbfCloseMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (menu) menu.classList.remove('kbf-menu-open');
        if (overlay) overlay.classList.remove('kbf-overlay-open');
      }
      window.addEventListener('resize', function(){
        if (window.innerWidth > 900) kbfCloseMobileMenu();
      });
      document.addEventListener('click', function(e){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        if (!menu || !overlay) return;
        if (overlay.classList.contains('kbf-overlay-open') && e.target === overlay) {
          kbfCloseMobileMenu();
        }
      });

    function kbfCloseModal(id) {
        document.getElementById(id).style.display = 'none';
        if (id === 'kbf-modal-create') {
            document.documentElement.classList.remove('kbf-modal-lock');
            document.body.classList.remove('kbf-modal-lock');
        }
    }
    function kbfOpenModal(id)  {
        
        document.getElementById(id).style.display = 'flex';
        if (id === 'kbf-modal-create') {
            document.documentElement.classList.add('kbf-modal-lock');
            document.body.classList.add('kbf-modal-lock');
            kbfSetCreateStep(1);
        }
    }
    window.kbfOpenModal = kbfOpenModal;
    window.kbfCloseModal = kbfCloseModal;

    var kbfPsgcData = null;
    var kbfPsgcLoading = false;
    function kbfTitleCase(str){
        return String(str).toLowerCase().replace(/\b\w/g,function(c){return c.toUpperCase();});
    }
    function kbfSetMuniOptions(muniEl, list){
        if (!muniEl) return;
        muniEl.innerHTML = '<option value="">Select Municipality</option>';
        for (var i=0;i<list.length;i++){
            var opt = document.createElement('option');
            opt.value = list[i].label;
            opt.textContent = list[i].label;
            muniEl.appendChild(opt);
        }
    }
    function kbfSetBrgyOptions(brgyEl, list){
        if (!brgyEl) return;
        brgyEl.innerHTML = '<option value="">Select Barangay</option>';
        for (var i=0;i<list.length;i++){
            var opt = document.createElement('option');
            opt.value = list[i];
            opt.textContent = list[i];
            brgyEl.appendChild(opt);
        }
    }
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
    function kbfEnsurePsgc(cb){
        if (kbfPsgcData){ cb(); return; }
        if (kbfPsgcLoading) return;
        kbfPsgcLoading = true;
        fetch('<?php echo esc_url(BNTM_KBF_URL . 'data/psgc_2016.json'); ?>')
          .then(function(r){ return r.json(); })
          .then(function(j){ kbfPsgcData = j; cb(); })
          .catch(function(){ kbfPsgcData = null; })
          .finally(function(){ kbfPsgcLoading = false; });
    }
    function kbfInitLocationPicker(provinceEl, muniEl, brgyEl){
        if (!provinceEl || !muniEl || !brgyEl) return;
        var muniData = [];
        function handleProvinceChange(){
            var val = provinceEl.value || '';
            if (!val){
                muniEl.disabled = true;
                brgyEl.disabled = true;
                kbfSetMuniOptions(muniEl, []);
                kbfSetBrgyOptions(brgyEl, []);
                return;
            }
            muniEl.disabled = true;
            brgyEl.disabled = true;
            kbfSetMuniOptions(muniEl, []);
            kbfSetBrgyOptions(brgyEl, []);
            kbfEnsurePsgc(function(){
                muniData = kbfBuildMunicipalities(String(val).toUpperCase());
                kbfSetMuniOptions(muniEl, muniData);
                muniEl.disabled = muniData.length === 0;
            });
        }
        function handleMunicipalityChange(){
            var val = muniEl.value || '';
            if (!val){
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
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
                return;
            }
            kbfSetBrgyOptions(brgyEl, found.barangays);
            brgyEl.disabled = found.barangays.length === 0;
        }
        provinceEl.addEventListener('change', handleProvinceChange);
        muniEl.addEventListener('change', handleMunicipalityChange);
        handleProvinceChange();
        return { handleProvinceChange: handleProvinceChange, handleMunicipalityChange: handleMunicipalityChange };
    }
    function kbfApplyLocationSelection(provinceEl, muniEl, brgyEl, loc){
        if (!provinceEl || !muniEl || !brgyEl) return;
        var parts = String(loc || '').split(',').map(function(p){ return p.trim(); }).filter(Boolean);
        var barangay = parts.length > 0 ? parts[0] : '';
        var municipality = parts.length > 1 ? parts[1] : '';
        var province = parts.length > 2 ? parts[2] : (parts.length === 1 ? parts[0] : (parts.length === 2 ? parts[1] : ''));
        provinceEl.value = province;
        if (!province) {
            muniEl.disabled = true; brgyEl.disabled = true;
            kbfSetMuniOptions(muniEl, []); kbfSetBrgyOptions(brgyEl, []);
            return;
        }
        kbfEnsurePsgc(function(){
            var muniData = kbfBuildMunicipalities(String(province).toUpperCase());
            kbfSetMuniOptions(muniEl, muniData);
            muniEl.disabled = muniData.length === 0;
            if (municipality) muniEl.value = municipality;
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
                if (barangay) brgyEl.value = barangay;
            } else {
                brgyEl.disabled = true;
                kbfSetBrgyOptions(brgyEl, []);
            }
        });
    }
        (function(){
        var prev = document.getElementById('kbf-create-prev');
        var next = document.getElementById('kbf-create-next');
        var submit = document.getElementById('kbf-create-submit');
        var createForm = document.getElementById('kbf-create-fund-form');
        var photoInput = document.getElementById('kbf-create-photos');
        var photoWrap = document.getElementById('kbf-create-photo-previews');
        var kbfCreateFiles = [];
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
        function kbfSyncCreateFiles(){
            if (!photoInput) return;
            var dt = new DataTransfer();
            kbfCreateFiles.forEach(function(f){ dt.items.add(f); });
            photoInput.files = dt.files;
        }
        function kbfRenderCreateThumbs(){
            if (!photoWrap) return;
            photoWrap.innerHTML = '';
            kbfCreateFiles.forEach(function(file, idx){
                if (!file.type || file.type.indexOf('image/') !== 0) return;
                var reader = new FileReader();
                reader.onload = function(e){
                    var thumb = document.createElement('div');
                    thumb.className = 'kbf-photo-thumb';
                    var img = document.createElement('img');
                    img.alt = '';
                    img.src = e.target.result;
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'kbf-photo-remove';
                    btn.innerHTML = '&times;';
                    btn.addEventListener('click', function(){
                        kbfCreateFiles.splice(idx, 1);
                        kbfSyncCreateFiles();
                        kbfRenderCreateThumbs();
                    });
                    thumb.appendChild(img);
                    thumb.appendChild(btn);
                    photoWrap.appendChild(thumb);
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
        }
        if (photoInput && photoWrap) {
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
                window.kbfRequestCloseCreate = function(){
            var createModal = document.getElementById('kbf-modal-create');
            if (createModal) createModal.style.display = 'none';
            kbfOpenModal('kbf-modal-draft');
        };
        window.kbfCancelDraftPrompt = function(){
            kbfCloseModal('kbf-modal-draft');
            var createModal = document.getElementById('kbf-modal-create');
            if (createModal) {
                createModal.style.display = 'flex';
                document.documentElement.classList.add('kbf-modal-lock');
                document.body.classList.add('kbf-modal-lock');
            }
        };window.kbfSaveCreateDraft = function(){
            kbfCloseModal('kbf-modal-draft');
            kbfCloseModal('kbf-modal-create');
        };
                var draftDiscardBtn = document.getElementById('kbf-draft-discard');
        if (draftDiscardBtn) draftDiscardBtn.addEventListener('click', function(){
            window.kbfDiscardCreateDraft();
        });
        var draftSaveBtn = document.getElementById('kbf-draft-save');
        if (draftSaveBtn) draftSaveBtn.addEventListener('click', function(){
            window.kbfSaveCreateDraft();
        });window.kbfDiscardCreateDraft = function(){
            if (createForm) createForm.reset();
            kbfCreateFiles = [];
            if (photoInput) {
                kbfSyncCreateFiles();
            }
            if (photoWrap) kbfRenderCreateThumbs();
            if (createForm) {
                createForm.querySelectorAll('.kbf-field-error').forEach(function(el){ el.textContent = ''; });
                createForm.querySelectorAll('.kbf-input-error').forEach(function(el){ el.classList.remove('kbf-input-error'); });
                var t = createForm.querySelector('.kbf-title-counter');
                if (t) t.textContent = '0 / 150';
                var d = createForm.querySelector('.kbf-desc-counter');
                if (d) d.textContent = '0 / 800';
            }
            kbfSetCreateStep(1);
            kbfCloseModal('kbf-modal-draft');
            kbfCloseModal('kbf-modal-create');
        };
        if (next) kbfSetCreateStep(1);
    })();;
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

    function kbfClearFieldError(field) {
        var group = field.closest('.kbf-form-group');
        if (!group) return;
        var err = group.querySelector('.kbf-field-error');
        if (err) err.textContent = '';
        field.classList.remove('kbf-input-error');
    }

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
        return firstInvalid;
    }

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

    function kbfSetLoadingPage(on) {
        var el = document.getElementById('kbf-loading-overlay');
        if (!el) return;
        el.style.display = on ? 'flex' : 'none';
    }

    function kbfInitDescCounter(textarea){
        if (!textarea) return;
        var counter = textarea.parentNode.querySelector('.kbf-desc-counter');
        function update(){
            if (!counter) return;
            var len = (textarea.value || '').length;
            counter.textContent = len + ' / 800';
        }
        textarea.addEventListener('input', update);
        update();
    }
        function kbfInitTitleCounter(input){
            if (!input) return;
            var counter = input.parentNode.querySelector('.kbf-title-counter');
            function update(){
                if (!counter) return;
                var len = (input.value || '').length;
                counter.textContent = len + ' / 150';
            }
            input.addEventListener('input', update);
            update();
        }
    (function(){
        kbfInitDescCounter(document.querySelector('#kbf-create-fund-form textarea[name="description"]'));
        kbfInitDescCounter(document.getElementById('edit-fund-desc'));
        kbfInitTitleCounter(document.querySelector('#kbf-create-fund-form input[name="title"]'));
        kbfInitTitleCounter(document.getElementById('edit-fund-title'));
        var funderSelect = document.querySelector('#kbf-create-fund-form select[name="funder_type"]');
        var titleInput = document.getElementById('kbf-create-title');
        if (funderSelect && titleInput) {
            var placeholders = {
                yourself: 'e.g., Help with my medical bills',
                someone_else: 'e.g., Support Maria’s recovery',
                charity_event: 'e.g., Barangay relief drive 2026'
            };
            var updatePlaceholder = function(){
                var key = funderSelect.value || 'yourself';
                titleInput.placeholder = placeholders[key] || 'Clear, compelling title';
            };
            funderSelect.addEventListener('change', updatePlaceholder);
            updatePlaceholder();
        }
        (function(){
            var input = document.getElementById('kbf-goal-amount');
            var out = document.getElementById('kbf-fee-preview');
            if(!input || !out) return;
            var rate = 0.05;
            function fmt(n){
                return n.toLocaleString('en-US',{minimumFractionDigits:2, maximumFractionDigits:2});
            }
            function render(){
                var val = parseFloat(input.value || '0');
                if(isNaN(val)) val = 0;
                var cut = val * rate;
                var net = Math.max(0, val - cut);
                out.innerHTML = 'Platform cut: ₱' + fmt(cut) + ' &nbsp;•&nbsp; Net goal: ₱' + fmt(net);
            }
            input.addEventListener('input', render);
            render();
        })();
    })();

    function kbfSubmitCreate() {
        const form = document.getElementById('kbf-create-fund-form');
        const btn  = document.getElementById('kbf-create-submit');
        const msg  = document.getElementById('kbf-create-msg');
        const invalid = kbfValidateCreateForm(form);
        if (invalid) {
            invalid.focus();
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
        const fd = new FormData(form);
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
                    msg.innerHTML = '<div class="kbf-alert kbf-alert-'+(ok?'success':'error')+'">'+json.data.message+'</div>';
                } else if (!ok) {
                    msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Submission failed. Please try again.</div>';
                }
            }
            if(ok) {
                kbfCloseModal('kbf-modal-create');
                var modal = document.getElementById('kbf-modal-create');
                if (modal) modal.style.display = 'none';
                document.documentElement.classList.remove('kbf-modal-lock');
                document.body.classList.remove('kbf-modal-lock');
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

    function kbfSubmitEdit() {
        const form = document.getElementById('kbf-edit-fund-form');
        const btn  = document.querySelector('#kbf-modal-edit .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-edit-msg');
        const invalid = kbfValidateEditFund(form);
        if (invalid) {
            invalid.focus();
            return;
        }
        kbfSetBtnLoading(btn, true, 'Saving...');
        kbfSetSkeleton(msg, true);
        const fd = new FormData(form);
        var eProv = document.getElementById('kbf-edit-province');
        var eMuni = document.getElementById('kbf-edit-municipality');
        var eBrgy = document.getElementById('kbf-edit-barangay');
        var eProvVal = eProv ? eProv.value : '';
        var eMuniVal = eMuni ? eMuni.value : '';
        var eBrgyVal = eBrgy ? eBrgy.value : '';
        var eParts = [];
        if (eBrgyVal) eParts.push(eBrgyVal);
        if (eMuniVal) eParts.push(eMuniVal);
        if (eProvVal) eParts.push(eProvVal);
        if (eParts.length) fd.append('location_full', eParts.join(', '));
        fd.append('action', 'kbf_update_fund');
        fd.append('nonce', '<?php echo $nonce_edit; ?>');
        fetch(ajaxurl, {method:'POST', body:fd})
        .then(r=>r.json()).then(json=>{
            const m = document.getElementById('kbf-edit-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json.success) {
                kbfCloseModal('kbf-modal-edit');
            } else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

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
            console.log('kbfSubmitWd: response', json);
            const m = document.getElementById('kbf-wd-msg');
            m.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if(json.success) {
                kbfCloseModal('kbf-modal-wd');
            } else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

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
            msg.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+json.data.message+'</div>';
            if (json.success) setTimeout(()=>{ kbfCloseModal('kbf-modal-appeal'); }, 1600);
            else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); });
    }

    window.kbfOpenEdit = function(id, title, desc, loc, deadline, autoReturn) {
        document.getElementById('edit-fund-id').value = id;
        document.getElementById('edit-fund-title').value = title;
        document.getElementById('edit-fund-desc').value = desc;
        var hiddenLoc = document.getElementById('edit-fund-location-hidden');
        if (hiddenLoc) hiddenLoc.value = loc || '';
        var titleCounter = document.getElementById('edit-fund-title').parentNode.querySelector('.kbf-title-counter');
        if (titleCounter) titleCounter.textContent = (title || '').length + ' / 150';
        var deadlineEl = document.getElementById('edit-fund-deadline');
        if (deadlineEl) deadlineEl.value = deadline || '';
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
        kbfOpenModal('kbf-modal-edit');
    };

    window.kbfOpenWd = function(fundId, available, title) {
        document.getElementById('wd-fund-id').value = fundId;
        document.getElementById('wd-fund-title').textContent = title || 'Fund #'+fundId;
        document.getElementById('wd-available-label').textContent = '₱' + parseFloat(available).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        document.getElementById('wd-amount').max = available;
        document.getElementById('wd-amount').placeholder = 'Max ₱'+parseFloat(available).toLocaleString('en-PH',{minimumFractionDigits:2});
        document.getElementById('kbf-wd-msg').innerHTML = '';
        document.getElementById('kbf-wd-form').reset();
        document.getElementById('wd-fund-id').value = fundId; // re-set after reset
        kbfOpenModal('kbf-modal-wd');
    };

    window.kbfOpenAppeal = function(fundId, title) {
        document.getElementById('kbf-appeal-fund-id').value = fundId;
        document.getElementById('kbf-appeal-msg').innerHTML = '';
        kbfOpenModal('kbf-modal-appeal');
    };

    window.kbfCancelFund = function(fundId) {
        if (!confirm('Cancel this fund? This cannot be undone.')) return;
        const fd = new FormData();
        fd.append('action','kbf_cancel_fund'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo $nonce_cancel; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };

    window.kbfExtendDeadline = function(fundId) {
        const d = prompt('New deadline (YYYY-MM-DD):'); if(!d) return;
        const fd = new FormData();
        fd.append('action','kbf_extend_deadline'); fd.append('fund_id',fundId);
        fd.append('deadline',d); fd.append('nonce','<?php echo $nonce_extend; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };


    window.kbfMarkComplete = function(fundId) {
        if(!confirm('Mark this fund as complete?')) return;
        const fd = new FormData();
        fd.append('action','kbf_mark_fund_complete'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo wp_create_nonce('kbf_cancel_fund'); ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };
    </script>









