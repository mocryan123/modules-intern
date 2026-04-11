        <!-- ================== JS ================== -->
    <script>if(typeof ajaxurl==='undefined') var ajaxurl='<?php echo admin_url("admin-ajax.php"); ?>';</script>
    <script>
      window.kbfIsLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
    window.kbfOpenAuthModal = function(reason){
        var modal = document.getElementById('kbf-auth-modal');
        if (!modal) return;
        var msg = document.getElementById('kbf-auth-reason');
        if (msg && reason) msg.textContent = reason;
        modal.style.display = 'flex';
        requestAnimationFrame(function(){ modal.classList.add('is-open'); });
        // keep page scroll enabled
    };
    window.kbfCloseAuthModal = function(){
        var modal = document.getElementById('kbf-auth-modal');
        if (!modal) return;
        modal.classList.remove('is-open');
        setTimeout(function(){ modal.style.display = 'none'; }, 220);
        // keep page scroll enabled
    };
      document.addEventListener('click', function(e){
        var modal = document.getElementById('kbf-auth-modal');
        if (modal && e.target === modal) window.kbfCloseAuthModal();
      });
    </script>
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
        var icon = document.getElementById('kbf-mobile-menu-icon');
        if (!menu || !overlay) return;
        menu.classList.toggle('kbf-menu-open');
        overlay.classList.toggle('kbf-overlay-open');
        if (icon) {
          var isOpen = menu.classList.contains('kbf-menu-open');
          var openCls = (icon.getAttribute('data-open') || 'ph-bold ph-x').split(' ');
          var closeCls = (icon.getAttribute('data-close') || 'ph ph-list').split(' ');
          icon.classList.remove.apply(icon.classList, openCls);
          icon.classList.remove.apply(icon.classList, closeCls);
          icon.classList.add.apply(icon.classList, isOpen ? openCls : closeCls);
        }
      }
      function kbfCloseMobileMenu(){
        var menu = document.getElementById('kbf-mobile-menu');
        var overlay = document.getElementById('kbf-mobile-overlay');
        var icon = document.getElementById('kbf-mobile-menu-icon');
        if (menu) menu.classList.remove('kbf-menu-open');
        if (overlay) overlay.classList.remove('kbf-overlay-open');
        if (icon) {
          var closeCls = (icon.getAttribute('data-close') || 'ph ph-list').split(' ');
          var openCls = (icon.getAttribute('data-open') || 'ph-bold ph-x').split(' ');
          icon.classList.remove.apply(icon.classList, openCls);
          icon.classList.remove.apply(icon.classList, closeCls);
          icon.classList.add.apply(icon.classList, closeCls);
        }
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

    function kbfCloseModal(id) {
        var el = document.getElementById(id);
        if (!el) return;
        if (typeof window.kbfCloseAllSelects === 'function') {
            window.kbfCloseAllSelects();
        }
        el.classList.remove('is-open');
        setTimeout(function(){ el.style.display = 'none'; }, 220);
        // keep page scroll enabled
    }
    function kbfOpenModal(id)  {
        
        var el = document.getElementById(id);
        if (!el) return;
        el.style.display = 'flex';
        requestAnimationFrame(function(){ el.classList.add('is-open'); });
        if (id === 'kbf-modal-create') {
            kbfSetCreateStep(1);
            if (window.kbfApplyCreateDraft) window.kbfApplyCreateDraft(true);
            if (typeof window.kbfUpdateSaveCloseState === 'function') window.kbfUpdateSaveCloseState();
        }
    }
    window.kbfOpenModal = kbfOpenModal;
    window.kbfCloseModal = kbfCloseModal;

    function kbfSetCreateStep(step) {
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
            if (body) body.scrollTop = 0;
        }
        var activePanel = form.querySelector('.kbf-step-content.is-active');
        if (activePanel) activePanel.scrollTop = 0;
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

    function kbfSetEditStep(step) {
        var form = document.getElementById('kbf-edit-fund-form');
        if (!form) return;
        var n = parseInt(step || '1', 10);
        if (isNaN(n)) n = 1;
        n = Math.min(2, Math.max(1, n));
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
        var prev = document.getElementById('kbf-edit-prev');
        var next = document.getElementById('kbf-edit-next');
        var submit = document.getElementById('kbf-edit-submit');
        if (prev) {
            prev.dataset.step = String(n);
            prev.disabled = n === 1;
        }
        if (next) {
            next.dataset.step = String(n);
            next.style.display = n === 2 ? 'none' : '';
        }
        if (submit) {
            submit.style.display = n === 2 ? '' : 'none';
        }
    }
    window.kbfSetEditStep = kbfSetEditStep;

    var kbfPsgcData = null;
    var kbfPsgcLoading = false;
    var kbfPsgcQueue = [];
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
        if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(muniEl);
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
        if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(brgyEl);
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
                muniData = kbfBuildMunicipalities(String(val).toUpperCase());
                kbfSetMuniOptions(muniEl, muniData);
                muniEl.disabled = muniData.length === 0;
                if (typeof window.kbfRefreshSelect === 'function') window.kbfRefreshSelect(muniEl);
            });
        }
        function handleMunicipalityChange(){
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
    function kbfApplyLocationSelection(provinceEl, muniEl, brgyEl, loc){
        if (!provinceEl || !muniEl || !brgyEl) return;
        var parts = String(loc || '').split(',').map(function(p){ return p.trim(); }).filter(Boolean);
        var province = '';
        var municipality = '';
        var barangay = '';

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
        });
    }
        (function(){
        var prev = document.getElementById('kbf-create-prev');
        var next = document.getElementById('kbf-create-next');
        var submit = document.getElementById('kbf-create-submit');
        var createForm = document.getElementById('kbf-create-fund-form');
        var editPrev = document.getElementById('kbf-edit-prev');
        var editNext = document.getElementById('kbf-edit-next');
        var editSubmit = document.getElementById('kbf-edit-submit');
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
        if (editPrev) editPrev.addEventListener('click', function(){
            var step = parseInt(editPrev.dataset.step || '1', 10);
            kbfSetEditStep(Math.max(1, step - 1));
        });
        if (editNext) editNext.addEventListener('click', function(){
            var step = parseInt(editNext.dataset.step || '1', 10);
            if (!kbfValidateEditStep(step)) return;
            kbfSetEditStep(Math.min(2, step + 1));
        });
        if (editSubmit) editSubmit.addEventListener('click', function(){
            if (!kbfValidateEditStep(2)) return;
            kbfSubmitEdit();
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
        function kbfSyncEditFiles(){
            if (!editPhotoInput) return;
            var dt = new DataTransfer();
            kbfEditFiles.forEach(function(f){ dt.items.add(f); });
            editPhotoInput.files = dt.files;
        }
        function kbfRenderEditThumbs(){
            if (!editPhotoWrap) return;
            editPhotoWrap.innerHTML = '';
            var existingCount = 0;
            kbfEditExistingUrls.forEach(function(src, idx){
                if (!src) return;
                var thumb = document.createElement('div');
                thumb.className = 'kbf-photo-thumb kbf-photo-thumb-existing';
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
                    thumb.className = 'kbf-photo-thumb';
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
                var addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'kbf-photo-add';
                addBtn.setAttribute('aria-label', 'Add photos');
                addBtn.innerHTML = '+';
                editPhotoWrap.appendChild(addBtn);
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
                var maxNew = Math.max(0, 5 - kbfEditExistingUrls.length);
                kbfEditFiles = kbfEditFiles.concat(incoming).slice(0, maxNew);
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

        function kbfUpdatePhotoEditorPreview(){
            if (!editorImg) return;
            var scale = editorState.baseScale * Math.max(editorZoomMin, editorState.zoom);
            editorImg.style.transform =
                'translate(-50%, -50%) translate(' + editorState.offsetX + 'px,' + editorState.offsetY + 'px) rotate(' +
                editorState.rotation + 'deg) scale(' + (editorState.flipX * scale) + ',' + (editorState.flipY * scale) + ')';
        }

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
        window.kbfResetEditPhotos = function(){
            kbfEditFiles = [];
            kbfEditExistingUrls = [];
            if (editPhotoInput) {
                kbfSyncEditFiles();
            }
            if (editPhotoWrap) kbfRenderEditThumbs();
        };
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
        function kbfInitBenefitsEditor(containerId, inputId, addBtnId) {
            var container = document.getElementById(containerId);
            var input = document.getElementById(inputId);
            var addBtn = document.getElementById(addBtnId);
            if (!container || !input) return null;
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
                var amount = document.createElement('input');
                amount.type = 'text';
                amount.inputMode = 'decimal';
                amount.className = 'kbf-benefit-amount';
                amount.placeholder = 'Amount (PHP)';
                amount.value = data && data.amount ? data.amount : '';
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
                var desc = document.createElement('textarea');
                desc.className = 'kbf-benefit-desc';
                desc.rows = 2;
                desc.placeholder = 'Short description';
                desc.value = data && data.description ? data.description : '';
                card.appendChild(row);
                card.appendChild(desc);
                [title, amount, desc].forEach(function(el){
                    el.addEventListener('input', sync);
                });
                return card;
            }
            function normalizeAmount(val){
                return String(val || '').replace(/[^0-9.]/g, '');
            }
            function sync(){
                var items = container.querySelectorAll('.kbf-benefit-card');
                var list = [];
                items.forEach(function(card){
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
            return { set: set, sync: sync };
        }
        window.kbfBenefitsEditors = window.kbfBenefitsEditors || {};
        window.kbfBenefitsEditors.create = kbfInitBenefitsEditor('kbf-create-benefits','kbf-create-benefits-input','kbf-create-benefit-add');
        window.kbfBenefitsEditors.edit = kbfInitBenefitsEditor('kbf-edit-benefits','kbf-edit-benefits-input','kbf-edit-benefit-add');
        var createProv = document.getElementById('kbf-province');
        var createMuni = document.getElementById('kbf-municipality');
        var createBrgy = document.getElementById('kbf-barangay');
        if (createProv && createMuni && createBrgy) {
            window.kbfCreateLocPicker = kbfInitLocationPicker(createProv, createMuni, createBrgy);
        }
        var editProv = document.getElementById('kbf-edit-province');
        var editMuni = document.getElementById('kbf-edit-municipality');
        var editBrgy = document.getElementById('kbf-edit-barangay');
        if (editProv && editMuni && editBrgy) {
            window.kbfEditLocPicker = kbfInitLocationPicker(editProv, editMuni, editBrgy);
        }
        var kbfDraftKey = 'kbf_create_draft_<?php echo (int)$business_id; ?>';
        function kbfDraftStorageOk(){
            try {
                var t = '__kbf__';
                localStorage.setItem(t, '1');
                localStorage.removeItem(t);
                return true;
            } catch(e) { return false; }
        }
        function kbfGetCreateDraft(){
            if (!kbfDraftStorageOk()) return null;
            try {
                var raw = localStorage.getItem(kbfDraftKey);
                return raw ? JSON.parse(raw) : null;
            } catch(e) { return null; }
        }
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
        function kbfGetCurrentCreateStep(){
            if (!createForm) return 1;
            var active = createForm.querySelector('.kbf-step.is-active');
            var step = active && active.dataset ? parseInt(active.dataset.step || '1', 10) : 1;
            return isNaN(step) ? 1 : step;
        }
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
        function kbfDataUrlToFile(dataUrl, name){
            var blob = kbfDataUrlToBlob(dataUrl);
            if (!blob) return null;
            var fname = name || ('photo-' + Date.now() + '.jpg');
            return new File([blob], fname, { type: blob.type || 'image/jpeg' });
        }
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
        window.kbfApplyCreateDraft = function(force){
            var draft = kbfGetCreateDraft();
            if (!draft) return false;
            if (!force && kbfCreateHasValue()) return false;
            return kbfApplyDraftToForm(draft);
        };
        window.kbfClearCreateDraft = function(){
            kbfSetCreateDraft(null);
        };
    window.kbfRequestCloseCreate = function(){
        kbfCloseModal('kbf-modal-create');
    };
    window.kbfSaveAndCloseCreateDraft = function(){
        var data = kbfBuildCreateDraft();
        if (data) kbfSetCreateDraft(data);
        kbfCloseModal('kbf-modal-create');
    };
        var saveCloseBtn = document.getElementById('kbf-create-save-close');
        var lastSavedDraftHash = kbfDraftComparable(kbfGetCreateDraft());
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

    function kbfValidateCreateStep(step) {
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
            firstInvalid.focus();
            return false;
        }
        return true;
    }
    window.kbfValidateCreateStep = kbfValidateCreateStep;

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
        }
        if (firstInvalid) {
            firstInvalid.focus();
            return false;
        }
        return true;
    }
    window.kbfValidateEditStep = kbfValidateEditStep;

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
              var rate = <?php echo (bool)kbf_get_setting('kbf_disable_platform_fee', false) ? '0' : '0.05'; ?>;
            function sanitizeMoney(value){
                var v = String(value || '').replace(/[^\d.]/g, '');
                var parts = v.split('.');
                var intPart = parts[0] || '';
                var decPart = parts.slice(1).join('');
                if (decPart.length > 2) decPart = decPart.slice(0, 2);
                if (intPart === '' && decPart) intPart = '0';
                return { intPart: intPart, decPart: decPart };
            }
            function formatWithCommas(intPart){
                return intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
            function formatMoneyInput(value){
                var s = sanitizeMoney(value);
                var intPart = s.intPart.replace(/^0+(?=\d)/, '');
                if (intPart === '') intPart = '0';
                var formatted = formatWithCommas(intPart);
                if (s.decPart) formatted += '.' + s.decPart;
                var raw = intPart + (s.decPart ? '.' + s.decPart : '');
                return { formatted: formatted, raw: raw };
            }
            function parseMoney(value){
                var raw = String(value || '').replace(/,/g, '');
                var num = parseFloat(raw);
                return isNaN(num) ? 0 : num;
            }
            function fmt(n){
                return n.toLocaleString('en-US',{minimumFractionDigits:2, maximumFractionDigits:2});
            }
            function render(){
                var val = parseMoney(input.dataset.kbfRaw || input.value);
                var cut = val * rate;
                var net = Math.max(0, val - cut);
                var peso = String.fromCharCode(8369);
                out.innerHTML = 'Platform cut: ' + peso + fmt(cut) + ' &nbsp;•&nbsp; Net goal: ' + peso + fmt(net);
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
        if (window.kbfBenefitsEditors && window.kbfBenefitsEditors.create) {
            window.kbfBenefitsEditors.create.sync();
        }
        const fd = new FormData(form);
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
                    msg.innerHTML = '<div class="kbf-alert kbf-alert-'+(ok?'success':'error')+'">'+json.data.message+'</div>';
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

    function kbfSubmitEdit() {
        const form = document.getElementById('kbf-edit-fund-form');
        const btn  = document.querySelector('#kbf-modal-edit .kbf-modal-footer .kbf-btn-primary');
        const msg  = document.getElementById('kbf-edit-msg');
        const invalid = kbfValidateEditFund(form);
        if (invalid) {
            invalid.focus();
            return;
        }
        var eProv = document.getElementById('kbf-edit-province');
        var eMuni = document.getElementById('kbf-edit-municipality');
        var eBrgy = document.getElementById('kbf-edit-barangay');
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
                    m.innerHTML = '<div class="kbf-alert kbf-alert-error">'+(json.data && json.data.message ? json.data.message : 'Update failed.')+'</div>';
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
                kbfCloseModal('kbf-modal-edit');
                try { localStorage.setItem('kbf_fund_updated', String(Date.now())); } catch(e){}
                setTimeout(function(){ window.location.reload(); }, 200);
            } else { kbfSetBtnLoading(btn,false); kbfSetSkeleton(msg,false); }
        }).catch(()=>{ 
            kbfSetBtnLoading(btn,false); 
            kbfSetSkeleton(msg,false); 
        });
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

    window.kbfOpenEdit = function(id, title, desc, loc, deadline, autoReturn, photosJson, benefitsJson) {
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

    window.kbfOpenAppeal = function(fundId, title) {
        document.getElementById('kbf-appeal-fund-id').value = fundId;
        document.getElementById('kbf-appeal-msg').innerHTML = '';
        kbfOpenModal('kbf-modal-appeal');
    };

    window.kbfOpenMilestoneModal = function(fundId, title) {
        var titleEl = document.getElementById('kbf-milestone-fund-title');
        var idEl = document.getElementById('kbf-milestone-fund-id');
        if (titleEl) titleEl.textContent = title ? ('Fund: ' + title) : '';
        if (idEl) idEl.value = fundId || '';
        var form = document.getElementById('kbf-milestone-form');
        if (form) form.reset();
        var msg = document.getElementById('kbf-milestone-msg');
        if (msg) msg.innerHTML = '';
        var saveBtn = document.getElementById('kbf-milestone-save');
        if (saveBtn && !saveBtn.dataset.bound) {
            saveBtn.dataset.bound = '1';
            saveBtn.addEventListener('click', kbfHandleMilestoneSave);
        }
        kbfOpenModal('kbf-modal-milestone');
    };
    function kbfHandleMilestoneSave(){
        var saveBtn = document.getElementById('kbf-milestone-save');
        var form = document.getElementById('kbf-milestone-form');
        var msg = document.getElementById('kbf-milestone-msg');
        if (!form) return;
        var title = form.querySelector('input[name="milestone_title"]');
        var body = form.querySelector('textarea[name="milestone_body"]');
        if (title && body && !title.value.trim() && !body.value.trim()) {
            if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Please add a title or update details.</div>';
            return;
        }
        if (typeof window.ajaxurl === 'undefined' || !window.ajaxurl) {
            if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Submit failed: ajaxurl is not defined.</div>';
            return;
        }
        var fd = new FormData(form);
        fd.append('action','kbf_add_milestone');
        fd.append('nonce','<?php echo wp_create_nonce('kbf_add_milestone'); ?>');
        if (saveBtn) kbfSetBtnLoading(saveBtn, true, 'Saving...');
        kbfSetSkeleton(msg, true);
        fetch(ajaxurl, { method:'POST', body:fd })
        .then(r=>r.text()).then(t=>{
            console.log('kbf_add_milestone raw response:', t);
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
            console.log('kbf_add_milestone parsed:', json);
            if (msg) {
                var extra = (json.data && json.data.raw) ? ('<div style="margin-top:6px;font-size:11px;opacity:.7;word-break:break-word;">'+String(json.data.raw).slice(0,280)+'</div>') : '';
                msg.innerHTML = '<div class="kbf-alert kbf-alert-'+(json.success?'success':'error')+'">'+(json.data && json.data.message ? json.data.message : 'Save failed.')+extra+'</div>';
            }
            if (saveBtn) kbfSetBtnLoading(saveBtn, false);
            kbfSetSkeleton(msg, false);
            if (json.success) {
                // Temporarily disable auto-refresh after save
            }
        }).catch(()=>{
            if (msg) msg.innerHTML = '<div class="kbf-alert kbf-alert-error">Request failed.</div>';
            if (saveBtn) kbfSetBtnLoading(saveBtn, false);
            kbfSetSkeleton(msg, false);
        });
    }

    window.kbfCancelFund = function(fundId) {
        if (!confirm('Cancel this fund? This cannot be undone.')) return;
        const fd = new FormData();
        fd.append('action','kbf_cancel_fund'); fd.append('fund_id',fundId);
        fd.append('nonce','<?php echo $nonce_cancel; ?>');
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{alert(j.data.message);if(j.success)location.reload();});
    };

    var kbfTrashFundId = null;
    var kbfTrashMode = 'cancel';
    var kbfTrashInFlight = false;
    window.kbfOpenTrashFund = function(fundId, title, mode){
        kbfTrashFundId = fundId || null;
        kbfTrashMode = (mode === 'trash') ? 'trash' : 'cancel';
        var titleEl = document.getElementById('kbf-trash-title');
        var msgEl = document.getElementById('kbf-trash-message');
        if (kbfTrashMode === 'trash') {
            if (titleEl) titleEl.textContent = 'Trash?';
            if (msgEl) msgEl.textContent = 'This will permanently delete the fundraiser and its records. This cannot be undone. Are you sure you want to continue?';
        } else {
            if (titleEl) titleEl.textContent = 'Trash?';
            if (msgEl) msgEl.textContent = 'This will move the fundraiser to cancelled status and it won’t be visible to sponsors. Are you sure you want to continue?';
        }
        kbfOpenModal('kbf-modal-trash-fund');
    };
    window.kbfConfirmTrashFund = function(){
        if (!kbfTrashFundId) return;
        if (kbfTrashInFlight) return;
        console.log('kbfConfirmTrashFund: confirmed', kbfTrashFundId);
        if (typeof window.ajaxurl === 'undefined' || !window.ajaxurl) {
            console.error('kbfConfirmTrashFund: ajaxurl is not defined');
            alert('Request failed: ajaxurl is not defined.');
            return;
        }
        kbfTrashInFlight = true;
        console.log('kbfConfirmTrashFund: ajaxurl', ajaxurl);
        const fd = new FormData();
        fd.append('action', kbfTrashMode === 'trash' ? 'kbf_trash_fund' : 'kbf_cancel_fund');
        fd.append('fund_id', kbfTrashFundId);
        fd.append('nonce','<?php echo $nonce_cancel; ?>');
        console.log('kbfConfirmTrashFund: payload', {
            action: kbfTrashMode === 'trash' ? 'kbf_trash_fund' : 'kbf_cancel_fund',
            fund_id: kbfTrashFundId,
            nonce: '<?php echo $nonce_cancel; ?>'
        });
        kbfCloseModal('kbf-modal-trash-fund');
        kbfSetLoadingPage(true);
        fetch(ajaxurl,{method:'POST',body:fd})
          .then(function(r){
              console.log('kbfConfirmTrashFund: response status', r.status, r.ok);
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
    window.kbfOpenEscrowRequest = function(fundId){
        kbfEscrowFundId = fundId || null;
        kbfOpenModal('kbf-modal-escrow-request');
    };
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

    <script>
      (function(){
        if (!window.kbfIsLoggedIn) return;
        var kbfUserTab = '<?php echo esc_js($tab); ?>';
        var kbfUserRefreshNonce = '<?php echo esc_js($nonce_refresh); ?>';
        var kbfUserRefreshTabs = ['overview','sponsorships','withdrawals','sponsor_history','my_funds'];
        if (kbfUserRefreshTabs.indexOf(kbfUserTab) === -1) return;

        var refreshInterval = 25000;
        var refreshing = false;
        var refreshTimer = null;

        function runInlineScripts(container){
          if (!container) return;
          var scripts = container.querySelectorAll('script');
          scripts.forEach(function(script){
            var code = script.textContent || '';
            if (!code.trim()) return;
            try { (new Function(code))(); } catch(e) { console.error('kbfUser inline script error:', e); }
          });
        }

        function kbfUserRefreshTab(){
          if (document.hidden) return;
          if (refreshing) return;
          if (document.documentElement.classList.contains('kbf-modal-lock') || document.body.classList.contains('kbf-modal-lock')) return;
          refreshing = true;
          var fd = new FormData();
          fd.append('action','kbf_user_refresh_tab');
          fd.append('_ajax_nonce', kbfUserRefreshNonce);
          fd.append('tab', kbfUserTab);
          fetch(ajaxurl,{method:'POST',body:fd})
            .then(function(r){ return r.text(); })
            .then(function(text){
              var cleaned = String(text || '').replace(/^\uFEFF+/, '').trim();
              if (!cleaned) return null;
              try { return JSON.parse(cleaned); }
              catch(e){ throw e; }
            })
            .then(function(j){
              if (!j || !j.success || !j.data || !j.data.html) return;
              var container = document.querySelector('.kbf-tab-content');
              if (!container) return;
              container.innerHTML = j.data.html;
              runInlineScripts(container);
              if (window.kbfInitTablePager) window.kbfInitTablePager();
              if (window.kbfInitTableDescriptions) window.kbfInitTableDescriptions();
            })
            .catch(function(err){
              console.error('kbfUserRefreshTab error:', err);
            })
            .finally(function(){ refreshing = false; });
        }

        function startRefresh(){
          if (refreshTimer) clearInterval(refreshTimer);
          refreshTimer = setInterval(kbfUserRefreshTab, refreshInterval);
        }
        function stopRefresh(){
          if (!refreshTimer) return;
          clearInterval(refreshTimer);
          refreshTimer = null;
        }
        startRefresh();
        document.addEventListener('visibilitychange', function(){
          if (document.hidden) {
            stopRefresh();
            return;
          }
          startRefresh();
          kbfUserRefreshTab();
        });
      })();
    </script>










