// public/js/admin/pages/add_inmate.js
// ShowPage() and OpenAddInmateModal() live in dashboard.js — do NOT redefine them here.

function initAddInmatePage() {

    // ── STEP NAVIGATION ──────────────────────────────────────────────
    const steps      = document.querySelectorAll('.ai-step');
    const connectors = document.querySelectorAll('.ai-step-connector');
    const sections   = document.querySelectorAll('.ai-section');

    // Required field IDs per step.
    // NOTE: 'ai-cell' is a hidden input — it is validated separately in validateStep()
    // via a custom branch so the error appears on the visible search input instead.
    const STEP_REQUIRED = {
        1: ['ai-lastName', 'ai-firstName', 'ai-status', 'ai-detentionType', 'ai-admissionDate', 'ai-secLvl', 'ai-commitOrder'],
        2: ['ai-dob', 'ai-sex', 'ai-homeAddress'],
        // Step 3 has no classic required inputs — it requires at least 1 saved crime (checked separately)
    };

    const FIELD_LABELS = {
        'ai-lastName':      'Last Name',
        'ai-firstName':     'First Name',
        'ai-status':        'Status',
        'ai-detentionType': 'Detention Type',
        'ai-admissionDate': 'Admission Date',
        'ai-secLvl':        'Security Level',
        'ai-commitOrder':   'Commitment Order No.',
        'ai-dob':           'Date of Birth',
        'ai-sex':           'Sex',
        'ai-homeAddress':   'Home Address',
    };

    function clearErrors(stepNum) {
        const section = document.getElementById(`ai-section-${stepNum}`);
        section?.querySelectorAll('.ai-field-error').forEach(el => el.remove());
        section?.querySelectorAll('.ai-input-error').forEach(el => el.classList.remove('ai-input-error'));
    }

    // ── MUGSHOT PREVIEWER ────────────────────────────────────────────
    const mugshotInput       = document.getElementById('ai-mugshot');
    const mugshotImg         = document.getElementById('ai-mugshot-img');
    const mugshotPlaceholder = document.getElementById('ai-mugshot-placeholder');
    const mugshotFilename    = document.getElementById('ai-mugshot-filename');
    const mugshotClear       = document.getElementById('ai-mugshot-clear');

    document.querySelector('.ai-mugshot-label')?.addEventListener('click', () => {
        mugshotInput?.click();
    });

    mugshotInput?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('Mugshot file is too large. Please choose an image under 5 MB.');
            AiClearMugshot();
            return;
        }

        const preview = document.getElementById('ai-mugshot-preview');
        const reader  = new FileReader();
        reader.onload = (e) => {
            mugshotImg.src = e.target.result;
            mugshotImg.style.display = 'block';
            if (mugshotPlaceholder) mugshotPlaceholder.style.display = 'none';
            if (preview) preview.classList.add('has-photo');
        };
        reader.readAsDataURL(file);

        if (mugshotFilename) mugshotFilename.textContent = file.name;
        if (mugshotClear)    mugshotClear.style.display  = 'inline-flex';
    });

    window.AiClearMugshot = function () {
        const preview = document.getElementById('ai-mugshot-preview');
        if (mugshotInput)       mugshotInput.value         = '';
        if (mugshotImg)       { mugshotImg.src = ''; mugshotImg.style.display = 'none'; }
        if (mugshotPlaceholder) mugshotPlaceholder.style.display = '';
        if (mugshotFilename)    mugshotFilename.textContent = 'No file chosen';
        if (mugshotClear)       mugshotClear.style.display  = 'none';
        if (preview)            preview.classList.remove('has-photo');
    };

    // ── CELL PICKER MODAL ────────────────────────────────────────────
    // The trigger button (#ai-cell-trigger) opens #ai-cell-modal.
    // Inside the modal, a live search input filters the available-cells list.
    // Clicking a row commits the cell_id to the hidden #ai-cell input and
    // closes the modal. The confirmation badge is updated accordingly.

    let cellSearchData   = [];   // full list fetched once
    let cellSearchLoaded = false;

    // DOM refs used across modal functions
    const cellHiddenInput   = document.getElementById('ai-cell');
    const cellSelectedBadge = document.getElementById('ai-cell-selected-badge');
    const cellSelectedText  = document.getElementById('ai-cell-selected-text');
    const cellClearBtn      = document.getElementById('ai-cell-clear');
    const cellTrigger       = document.getElementById('ai-cell-trigger');
    const cellTriggerText   = document.getElementById('ai-cell-trigger-text');

    // ── Fetch (once) ─────────────────────────────────────────────────
    async function fetchCellData() {
        if (cellSearchLoaded) return;
        try {
            const res  = await fetch('/admin/cells/data', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();

            // Only cells that can still accept inmates
            cellSearchData   = (data.cells || []).filter(c => c.status === 'available');
            cellSearchLoaded = true;
        } catch (err) {
            console.warn('Could not load cell list:', err);
        }
    }

    // ── Render modal list ────────────────────────────────────────────
    function renderModalList(query) {
        const list = document.getElementById('ai-cell-modal-list');
        const countEl = document.getElementById('ai-cell-modal-count');
        if (!list) return;

        const q       = query.trim().toLowerCase();
        const matched = q
            ? cellSearchData.filter(c =>
                c.cell_id.toLowerCase().includes(q) ||
                (c.type || '').toLowerCase().includes(q)
              )
            : cellSearchData;

        list.innerHTML = '';

        if (matched.length === 0) {
            const li = document.createElement('li');
            li.className = 'ai-cell-modal-empty';
            li.textContent = q ? 'No cells match your search.' : 'No available cells.';
            list.appendChild(li);
            if (countEl) countEl.textContent = '';
            return;
        }

        if (countEl) countEl.textContent = `${matched.length} cell${matched.length !== 1 ? 's' : ''} available`;

        matched.forEach(c => {
            const li      = document.createElement('li');
            li.className  = 'ai-cell-modal-row';
            li.role       = 'option';
            li.dataset.id = c.id;   // numeric PK

            // Highlight currently selected cell (compare against stored numeric PK)
            if (cellHiddenInput?.value === String(c.id)) {
                li.classList.add('ai-cell-modal-row--selected');
            }

            const pct    = c.capacity > 0 ? Math.round((c.occupancy / c.capacity) * 100) : 0;
            const dotCls = pct >= 80 ? 'warn' : 'ok';

            li.innerHTML = `
                <span class="ai-cell-modal-col-id">
                    <span class="ai-cell-modal-dot ${dotCls}"></span>
                    ${escHtml(c.cell_id)}
                </span>
                <span class="ai-cell-modal-col-type">${escHtml(c.type || '—')}</span>
                <span class="ai-cell-modal-col-occ">${c.occupancy} / ${c.capacity}</span>
            `;

            li.addEventListener('click', () => commitCell(c));
            list.appendChild(li);
        });
    }

    // ── Commit selection ─────────────────────────────────────────────
    function commitCell(cell) {
        // Store the numeric primary key (id) as the FK value sent to the server.
        // The human-readable cell_id is used only for display purposes.
        if (cellHiddenInput) cellHiddenInput.value = cell.id;

        // Update trigger button label (display cell_id string, not the PK)
        if (cellTriggerText) cellTriggerText.textContent = cell.cell_id;
        if (cellTrigger)     cellTrigger.classList.add('ai-cell-trigger--selected');

        // Update confirmation badge
        if (cellSelectedText)  cellSelectedText.textContent = `${cell.cell_id} · ${cell.type || '—'} · ${cell.occupancy}/${cell.capacity}`;
        if (cellSelectedBadge) cellSelectedBadge.style.display = 'flex';

        // Clear any lingering validation error
        const wrap = document.getElementById('ai-cell-search-wrap');
        wrap?.querySelector('.ai-field-error')?.remove();
        wrap?.classList.remove('ai-input-error');

        AiCloseCellModal();
    }

    // ── Open / close modal ───────────────────────────────────────────
    window.AiOpenCellModal = async function () {
        const modal = document.getElementById('ai-cell-modal');
        if (!modal) return;

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Show spinner while data loads
        const list = document.getElementById('ai-cell-modal-list');
        if (list && !cellSearchLoaded) {
            list.innerHTML = `
                <li class="ai-cell-modal-loading">
                    <svg class="ai-cell-modal-spinner" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" width="18" height="18">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83
                                 M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                    Loading cells…
                </li>`;
        }

        await fetchCellData();

        // Clear and focus search
        const searchInput = document.getElementById('ai-cell-modal-search');
        if (searchInput) { searchInput.value = ''; searchInput.focus(); }

        const searchClear = document.getElementById('ai-cell-modal-search-clear');
        if (searchClear) searchClear.style.display = 'none';

        renderModalList('');
    };

    window.AiCloseCellModal = function () {
        const modal = document.getElementById('ai-cell-modal');
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
        cellTrigger?.focus();
    };

    // Close when clicking the dark overlay (not the modal card itself)
    window.AiCellModalOverlayClick = function (e) {
        if (e.target === document.getElementById('ai-cell-modal')) {
            AiCloseCellModal();
        }
    };

    // Live search inside the modal
    window.AiCellModalFilter = function (value) {
        renderModalList(value);
        const clearBtn = document.getElementById('ai-cell-modal-search-clear');
        if (clearBtn) clearBtn.style.display = value.trim() ? 'flex' : 'none';
    };

    // Clear the modal search input
    window.AiCellModalClearSearch = function () {
        const searchInput = document.getElementById('ai-cell-modal-search');
        if (searchInput) { searchInput.value = ''; searchInput.focus(); }
        const clearBtn = document.getElementById('ai-cell-modal-search-clear');
        if (clearBtn) clearBtn.style.display = 'none';
        renderModalList('');
    };

    // Keyboard: Escape closes the modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('ai-cell-modal');
            if (modal && modal.style.display !== 'none') AiCloseCellModal();
        }
    });

    // ── Clear selection ───────────────────────────────────────────────
    window.AiClearCellSelection = function () {
        if (cellHiddenInput)   cellHiddenInput.value = '';
        if (cellSelectedBadge) cellSelectedBadge.style.display = 'none';
        if (cellTriggerText)   cellTriggerText.textContent = 'Select a cell…';
        if (cellTrigger)       cellTrigger.classList.remove('ai-cell-trigger--selected');
    };

    // ── FORM SUBMIT ──────────────────────────────────────────────────
    document.getElementById('addInmateForm')?.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!validateStep(3)) return;

        const formData = new FormData(this);

        // ── Sanitize cell_id ─────────────────────────────────────────
        // The hidden #ai-cell input can contain the string "undefined" if
        // cell.id was undefined when commitCell() ran (API shape mismatch),
        // or an empty string when no cell was chosen. Either way, delete it
        // so the server falls back to the Holding Cell automatically.
        const rawCellId = formData.get('cell_id');
        if (!rawCellId || rawCellId === 'undefined' || rawCellId.trim() === '') {
            formData.delete('cell_id');
        }
        // ─────────────────────────────────────────────────────────────

        formData.delete('crimes');
        crimes.forEach((c, idx) => {
            const crimeObj = {
                crime_name:        c.name,
                crime_date:        c.date,
                crime_location:    c.location,
                law_offended:      c.law,
                crime_description: c.desc,
                sentence_years:    c.years,
                sentence_months:   c.months,
                verdict_date:      c.verdictDate,
                case_number:       c.caseNum,
                prosecutor:        c.prosecutor,
                judge:             c.judge,
            };
            Object.entries(crimeObj).forEach(([key, val]) => {
                formData.append(`crimes[${idx}][${key}]`, val ?? '');
            });
            c.victims.forEach((v, vi) => {
                formData.append(`crimes[${idx}][victims][${vi}][name]`,       v.name       ?? '');
                formData.append(`crimes[${idx}][victims][${vi}][age]`,        v.age        ?? '');
                formData.append(`crimes[${idx}][victims][${vi}][testifiers]`, v.testifiers ?? '');
                formData.append(`crimes[${idx}][victims][${vi}][relation]`,   v.relation   ?? '');
            });
        });

        const submitBtn = this.querySelector('.ai-btn-submit');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving...'; }

        fetch('/admin/inmates', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept':       'application/json',
            },
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('addInmateForm').reset();
                AiClearMugshot();
                AiClearCellSelection();
                crimes.length = 0;
                renderCrimes();
                AiNextStep(1);
                ShowPage('inmates');
            } else {
                console.error('Validation :', data.errors);
                alert('Please check the form errors:\n' + Object.values(data.errors || {}).flat().join('\n'));
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Submit Inmate Record'; }
            }
        })
        .catch(err => {
            console.error('Submit error:', err);
            alert('Something went wrong. Please try again.');
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Submit Inmate Record'; }
        });
    });

    function validateStep(stepNum) {
        clearErrors(stepNum);
        const required     = STEP_REQUIRED[stepNum] || [];
        const firstInvalid = [];

        required.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const empty = el.value.trim() === '' || (el.tagName === 'SELECT' && el.value === '');
            if (empty) {
                el.classList.add('ai-input-error');
                if (firstInvalid.length === 0) firstInvalid.push(el);

                const msg = document.createElement('span');
                msg.className   = 'ai-field-error';
                msg.textContent = 'This field is required.';
                const wrapper   = el.closest('.ai-select-wrapper') || el;
                wrapper.insertAdjacentElement('afterend', msg);

                const clear = () => { el.classList.remove('ai-input-error'); msg.remove(); };
                el.addEventListener('input',  clear, { once: true });
                el.addEventListener('change', clear, { once: true });
            }
        });

        // NOTE: Cell assignment is optional — no validation block needed here.
        // #ai-cell hidden input may be blank; the server accepts null for cell_id.

        if (stepNum === 3 && crimes.length === 0) {
            const noCrimes = document.getElementById('ai-no-crimes');
            if (noCrimes) {
                noCrimes.style.color   = 'var(--red)';
                noCrimes.textContent   = 'At least one offense must be added before submitting.';
            }
            return false;
        }

        if (firstInvalid.length > 0) {
            firstInvalid[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        return true;
    }

    window.AiNextStep = function (targetStep) {
        const activeSection = document.querySelector('.ai-section.active');
        const currentNum    = activeSection ? parseInt(activeSection.id.replace('ai-section-', '')) : 1;

        if (targetStep > currentNum) {
            if (!validateStep(currentNum)) return;
        } else {
            clearErrors(currentNum);
        }

        sections.forEach(s => s.classList.remove('active'));
        const targetSection = document.getElementById(`ai-section-${targetStep}`);
        if (targetSection) targetSection.classList.add('active');

        steps.forEach((step, i) => {
            const stepNum = i + 1;
            step.classList.remove('active', 'done');
            if (stepNum < targetStep)  step.classList.add('done');
            if (stepNum === targetStep) step.classList.add('active');
        });

        connectors.forEach((conn, i) => {
            conn.classList.toggle('done', i + 1 < targetStep);
        });

        document.getElementById('page-add-inmate')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    // ── AGE AUTO-CALCULATION ─────────────────────────────────────────
    const dobInput = document.getElementById('ai-dob');
    const ageInput = document.getElementById('ai-age');

    dobInput?.addEventListener('change', () => {
        if (!dobInput.value) { ageInput.value = ''; return; }
        const dob   = new Date(dobInput.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
        ageInput.value = age >= 0 ? age : '';
    });

    // ── VICTIM ROWS (inside crime form) ──────────────────────────────
    let victimCount = 0;

    window.AiAddVictimRow = function () {
        victimCount++;
        const list   = document.getElementById('ai-victims-list');
        const noNote = document.getElementById('ai-no-victims');
        if (noNote) noNote.style.display = 'none';

        const row = document.createElement('div');
        row.className = 'ai-victim-row';
        row.id = `victim-row-${victimCount}`;
        row.innerHTML = `
            <div class="vf-name">
                <label style="font-size:10.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:5px;">Full Name</label>
                <input type="text" id="vf-name-${victimCount}" placeholder="Victim's full name">
            </div>
            <div class="vf-age">
                <label style="font-size:10.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:5px;">Age</label>
                <input type="number" id="vf-age-${victimCount}" placeholder="Age" min="1">
            </div>
            <div class="vf-testifiers">
                <label style="font-size:10.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:5px;">Testifier Type</label>
                <select type="text" id="vf-testifiers-${victimCount}">
                    <option value="">Select Testifier Type</option>
                    <option value="Witness">Witness</option>
                    <option value="Victim">Victim</option>
                </select>
            </div>
            <div class="vf-relation">
                <label style="font-size:10.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:5px;">Relationship to Offender</label>
                <input type="text" id="vf-relation-${victimCount}" placeholder="e.g. Stranger, Neighbor">
            </div>
            <button type="button" class="ai-victim-remove" onclick="AiRemoveVictimRow(${victimCount})" title="Remove">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        `;
        list.appendChild(row);
    };

    window.AiRemoveVictimRow = function (id) {
        document.getElementById(`victim-row-${id}`)?.remove();
        const list = document.getElementById('ai-victims-list');
        if (list && list.children.length === 0) {
            const noNote = document.getElementById('ai-no-victims');
            if (noNote) noNote.style.display = '';
        }
    };

    // ── CRIME FORM STATE ─────────────────────────────────────────────
    const crimes = [];
    let crimeIdCounter = 0;
    let editingCrimeId = null;

    const CF_FIELDS = ['cf-crimeName', 'cf-crimeDate', 'cf-lawOffended', 'cf-sentenceYears'];

    function getCrimeFormValues() {
        const victimRows = document.querySelectorAll('#ai-victims-list .ai-victim-row');
        const victims = [];
        victimRows.forEach(row => {
            const id = row.id.replace('victim-row-', '');
            victims.push({
                name:       document.getElementById(`vf-name-${id}`)?.value.trim()       || '',
                age:        document.getElementById(`vf-age-${id}`)?.value.trim()         || '',
                relation:   document.getElementById(`vf-relation-${id}`)?.value.trim()   || '',
                testifiers: document.getElementById(`vf-testifiers-${id}`)?.value.trim() || '',
            });
        });

        return {
            name:        document.getElementById('cf-crimeName')?.value.trim()      || '',
            date:        document.getElementById('cf-crimeDate')?.value             || '',
            location:    document.getElementById('cf-crimeLocation')?.value.trim()  || '',
            law:         document.getElementById('cf-lawOffended')?.value.trim()    || '',
            desc:        document.getElementById('cf-crimeDesc')?.value.trim()      || '',
            years:       document.getElementById('cf-sentenceYears')?.value.trim()  || '',
            months:      document.getElementById('cf-sentenceMonths')?.value.trim() || '',
            verdictDate: document.getElementById('cf-verdictDate')?.value           || '',
            caseNum:     document.getElementById('cf-caseNumber')?.value.trim()     || '',
            prosecutor:  document.getElementById('cf-prosecutor')?.value.trim()     || '',
            judge:       document.getElementById('cf-judge')?.value.trim()          || '',
            victims,
        };
    }

    function resetCrimeForm() {
        ['cf-crimeName','cf-crimeDate','cf-crimeLocation','cf-lawOffended','cf-crimeDesc',
         'cf-sentenceYears','cf-sentenceMonths','cf-verdictDate','cf-caseNumber','cf-prosecutor','cf-judge']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

        const list = document.getElementById('ai-victims-list');
        if (list) list.innerHTML = '';
        const noNote = document.getElementById('ai-no-victims');
        if (noNote) { noNote.style.display = ''; noNote.textContent = 'No victims added. Click "Add Victim" if applicable.'; }
        victimCount = 0;

        document.querySelectorAll('#ai-crime-form-panel .ai-input-error').forEach(el => el.classList.remove('ai-input-error'));
        document.querySelectorAll('#ai-crime-form-panel .ai-field-error').forEach(el => el.remove());
    }

    function validateCrimeForm() {
        let valid = true;
        CF_FIELDS.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('ai-input-error');
            el.parentElement?.querySelector('.ai-field-error')?.remove();
            el.nextElementSibling?.classList.contains('ai-field-error') && el.nextElementSibling.remove();

            if (el.value.trim() === '') {
                valid = false;
                el.classList.add('ai-input-error');
                const msg = document.createElement('span');
                msg.className   = 'ai-field-error';
                msg.textContent = 'This field is required.';
                el.insertAdjacentElement('afterend', msg);
                const clear = () => { el.classList.remove('ai-input-error'); msg.remove(); };
                el.addEventListener('input',  clear, { once: true });
                el.addEventListener('change', clear, { once: true });
            }
        });
        if (!valid) {
            document.getElementById(CF_FIELDS[0])?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return valid;
    }

    function renderCrimes() {
        const list    = document.getElementById('ai-crimes-list');
        const noLabel = document.getElementById('ai-no-crimes');
        const hidden  = document.getElementById('ai-crimes-hidden');

        if (!list) return;
        list.innerHTML   = '';
        hidden.innerHTML = '';

        if (crimes.length === 0) {
            if (noLabel) {
                noLabel.style.display = '';
                noLabel.style.color   = '';
                noLabel.innerHTML = 'No offenses added yet. Click <strong>Add Crime</strong> to begin.';
            }
            return;
        }

        if (noLabel) noLabel.style.display = 'none';

        crimes.forEach((c, idx) => {
            const n = idx + 1;

            const card = document.createElement('div');
            card.className = 'ai-crime-card';
            card.id = `crime-card-${c.id}`;

            const sentenceStr = [c.years  ? `${c.years} yr${c.years != 1 ? 's' : ''}`  : '',
                                 c.months ? `${c.months} mo` : ''].filter(Boolean).join(', ') || '—';
            const victimList  = c.victims.filter(v => v.name);
            const victimStr   = victimList.map(v => v.name).join(', ') || 'None';

            const victimRowsHtml = victimList.length
                ? victimList.map(v => `
                    <div class="ai-crime-victim-row">
                        <span class="ai-crime-victim-name">${escHtml(v.name)}</span>
                        ${v.age        ? `<span class="ai-crime-victim-detail">Age ${escHtml(v.age)}</span>` : ''}
                        ${v.testifiers ? `<span class="ai-crime-victim-detail">${escHtml(v.testifiers)}</span>` : ''}
                        ${v.relation   ? `<span class="ai-crime-victim-detail">${escHtml(v.relation)}</span>` : ''}
                    </div>`).join('')
                : `<p class="ai-crime-victim-none">No victims / witnesses recorded.</p>`;

            card.innerHTML = `
                <div class="ai-crime-card-top">
                    <div class="ai-crime-card-badge">#${n}</div>
                    <div class="ai-crime-card-title">${escHtml(c.name)}</div>
                    <button type="button" class="ai-crime-edit" onclick="AiEditCrime(${c.id})" title="Edit offense">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                    </button>
                    <button type="button" class="ai-victim-remove" onclick="AiRemoveCrime(${c.id})" title="Remove offense">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="ai-crime-card-meta">
                    <span><strong>Law:</strong> ${escHtml(c.law)}</span>
                    <span><strong>Date:</strong> ${c.date || '—'}</span>
                    <span><strong>Sentence:</strong> ${sentenceStr}</span>
                    ${c.caseNum  ? `<span><strong>Case No.:</strong> ${escHtml(c.caseNum)}</span>` : ''}
                    ${c.location ? `<span><strong>Location:</strong> ${escHtml(c.location)}</span>` : ''}
                    <span><strong>Victim(s):</strong> ${escHtml(victimStr)}</span>
                </div>
                ${c.desc ? `<div class="ai-crime-card-desc">${escHtml(c.desc)}</div>` : ''}
                <div class="ai-crime-card-victims">
                    <div class="ai-crime-victims-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Victims / Witnesses
                        ${victimList.length ? `<span class="ai-crime-victim-count">${victimList.length}</span>` : ''}
                    </div>
                    <div class="ai-crime-victims-body">${victimRowsHtml}</div>
                </div>
            `;
            list.appendChild(card);
        });
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    window.AiOpenCrimeForm = function () {
        const panel = document.getElementById('ai-crime-form-panel');
        const btn   = document.getElementById('ai-open-crime-form-btn');
        if (!panel) return;
        panel.style.display = 'block';
        if (btn) btn.disabled = true;
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    window.AiCloseCrimeForm = function () {
        const panel = document.getElementById('ai-crime-form-panel');
        const btn   = document.getElementById('ai-open-crime-form-btn');
        if (!panel) return;
        panel.style.display = 'none';
        if (btn) btn.disabled = false;

        editingCrimeId = null;
        const panelTitle = document.getElementById('ai-crime-form-title');
        const saveBtn    = document.getElementById('ai-save-crime-btn');
        if (panelTitle) panelTitle.textContent = 'Add Offense';
        if (saveBtn)    saveBtn.textContent    = 'Save Crime';

        resetCrimeForm();
    };

    window.AiSaveCrime = function () {
        if (!validateCrimeForm()) return;

        const vals = getCrimeFormValues();

        if (editingCrimeId !== null) {
            const idx = crimes.findIndex(c => c.id === editingCrimeId);
            if (idx !== -1) crimes[idx] = { id: editingCrimeId, ...vals };
            editingCrimeId = null;

            const panelTitle = document.getElementById('ai-crime-form-title');
            const saveBtn    = document.getElementById('ai-save-crime-btn');
            if (panelTitle) panelTitle.textContent = 'Add Offense';
            if (saveBtn)    saveBtn.textContent    = 'Save Crime';
        } else {
            crimeIdCounter++;
            crimes.push({ id: crimeIdCounter, ...vals });
        }

        AiCloseCrimeForm();
        renderCrimes();

        const noLabel = document.getElementById('ai-no-crimes');
        if (noLabel) { noLabel.style.color = ''; }
    };

    window.AiEditCrime = function (id) {
        const crime = crimes.find(c => c.id === id);
        if (!crime) return;

        editingCrimeId = id;
        AiOpenCrimeForm();

        const panelTitle = document.getElementById('ai-crime-form-title');
        const saveBtn    = document.getElementById('ai-save-crime-btn');
        if (panelTitle) panelTitle.textContent = 'Edit Offense';
        if (saveBtn)    saveBtn.textContent    = 'Update Crime';

        const fieldMap = {
            'cf-crimeName':      crime.name,
            'cf-crimeDate':      crime.date,
            'cf-crimeLocation':  crime.location,
            'cf-lawOffended':    crime.law,
            'cf-crimeDesc':      crime.desc,
            'cf-sentenceYears':  crime.years,
            'cf-sentenceMonths': crime.months,
            'cf-verdictDate':    crime.verdictDate,
            'cf-testifiers':     crime.testifiers,
            'cf-caseNumber':     crime.caseNum,
            'cf-prosecutor':     crime.prosecutor,
            'cf-judge':          crime.judge,
        };
        Object.entries(fieldMap).forEach(([elId, val]) => {
            const el = document.getElementById(elId);
            if (el) el.value = val || '';
        });

        const list = document.getElementById('ai-victims-list');
        if (list) list.innerHTML = '';
        victimCount = 0;
        const noNote = document.getElementById('ai-no-victims');
        if (noNote) noNote.style.display = crime.victims.length ? 'none' : '';

        crime.victims.forEach(v => {
            victimCount++;
            const row = document.createElement('div');
            row.className = 'ai-victim-row';
            row.id = `victim-row-${victimCount}`;
            row.innerHTML = `
                <div>
                    <label style="font-size:10.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:5px;">Full Name</label>
                    <input type="text" id="vf-name-${victimCount}" placeholder="Victim's full name" value="${escHtml(v.name)}">
                </div>
                <div>
                    <label style="font-size:10.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:5px;">Age</label>
                    <input type="number" id="vf-age-${victimCount}" placeholder="Age" min="1" value="${escHtml(v.age)}">
                </div>
                <div>
                    <label style="font-size:10.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:5px;">Testifier Type</label>
                    <input type="text" id="vf-address-${victimCount}" placeholder="Victim's address" value="${escHtml(v.testifiers)}">
                </div>
                <div>
                    <label style="font-size:10.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:5px;">Relationship to Offender</label>
                    <input type="text" id="vf-relation-${victimCount}" placeholder="e.g. Stranger, Neighbor" value="${escHtml(v.relation)}">
                </div>
                <button type="button" class="ai-victim-remove" onclick="AiRemoveVictimRow(${victimCount})" title="Remove">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            `;
            list.appendChild(row);
        });
    };

    window.AiRemoveCrime = function (id) {
        const idx = crimes.findIndex(c => c.id === id);
        if (idx !== -1) crimes.splice(idx, 1);
        renderCrimes();
    };
}

// Safe init — works whether the script fires before or after DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAddInmatePage);
} else {
    initAddInmatePage();
}