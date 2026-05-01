// public/js/admin/pages/add_inmate.js
// ShowPage() and OpenAddInmateModal() live in dashboard.js — do NOT redefine them here.

function initAddInmatePage() {

    // ── STEP NAVIGATION ──────────────────────────────────────────────
    const steps      = document.querySelectorAll('.ai-step');
    const connectors = document.querySelectorAll('.ai-step-connector');
    const sections   = document.querySelectorAll('.ai-section');

    // Required field IDs per step
    const STEP_REQUIRED = {
        1: ['ai-lastName', 'ai-firstName', 'ai-cell', 'ai-status', 'ai-detentionType', 'ai-admissionDate', 'ai-commitOrder'],
        2: ['ai-dob', 'ai-sex', 'ai-homeAddress'],
        // Step 3 has no classic required inputs — it requires at least 1 saved crime (checked separately)
    };

    const FIELD_LABELS = {
        'ai-lastName':      'Last Name',
        'ai-firstName':     'First Name',
        'ai-cell':          'Assigned Cell',
        'ai-status':        'Status',
        'ai-detentionType': 'Detention Type',
        'ai-admissionDate': 'Admission Date',
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

    document.getElementById('addInmateForm')?.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!validateStep(3)) return;

        // Build the payload from FormData + structured crimes array
        const formData = new FormData(this);
        const payload  = Object.fromEntries(formData.entries());

        // Attach crimes as a proper nested array (built by JS, not hidden inputs)
        payload.crimes = crimes.map(c => ({
            crime_name:         c.name,
            crime_date:         c.date,
            crime_location:     c.location,
            law_offended:       c.law,
            crime_description:  c.desc,
            sentence_years:     c.years,
            sentence_months:    c.months,
            verdict_date:       c.verdictDate,
            case_number:        c.caseNum,
            prosecutor:         c.prosecutor,
            judge:              c.judge,
            victims:            c.victims,
        }));

        // Disable submit button to prevent double submission
        const submitBtn = this.querySelector('.ai-btn-submit');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving...'; }

        fetch('/admin/inmates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept':       'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {

                //Reset Form and go back to steps 1 (in case admin wants to add another inmate right away)
                document.getElementById('addInmateForm').reset();
                crimes.length = 0;
                renderCrimes();
                AiNextStep(1);

                // Then go back to inmates list
                ShowPage('inmates');
            } else {
                // Show validation errors returned from Laravel
                console.error('Validation errors:', data.errors);
                alert('Please check the form for errors:\n' + Object.values(data.errors || {}).flat().join('\n'));
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
        const required = STEP_REQUIRED[stepNum] || [];
        const firstInvalid = [];

        required.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const empty = el.value.trim() === '' || (el.tagName === 'SELECT' && el.value === '');
            if (empty) {
                el.classList.add('ai-input-error');
                if (firstInvalid.length === 0) firstInvalid.push(el);

                const msg = document.createElement('span');
                msg.className = 'ai-field-error';
                msg.textContent = 'This field is required.';
                const wrapper = el.closest('.ai-select-wrapper') || el;
                wrapper.insertAdjacentElement('afterend', msg);

                const clear = () => { el.classList.remove('ai-input-error'); msg.remove(); };
                el.addEventListener('input',  clear, { once: true });
                el.addEventListener('change', clear, { once: true });
            }
        });

        if (stepNum === 3 && crimes.length === 0) {
            const noCrimes = document.getElementById('ai-no-crimes');
            if (noCrimes) {
                noCrimes.style.color = 'var(--red)';
                noCrimes.textContent = 'At least one offense must be added before submitting.';
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
        const activeSection  = document.querySelector('.ai-section.active');
        const currentNum     = activeSection ? parseInt(activeSection.id.replace('ai-section-', '')) : 1;

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
    const crimes = []; // { id, name, date, location, law, desc, years, months, verdictDate, caseNum, prosecutor, judge, victims[] }
    let crimeIdCounter = 0;
    let editingCrimeId = null; // null = adding new, number = editing existing

    const CF_FIELDS = ['cf-crimeName', 'cf-crimeDate', 'cf-lawOffended', 'cf-sentenceYears']; // required

    function getCrimeFormValues() {
        // Collect victim rows from the form panel
        const victimRows = document.querySelectorAll('#ai-victims-list .ai-victim-row');
        const victims = [];
        victimRows.forEach(row => {
            const id = row.id.replace('victim-row-', '');
            victims.push({
                name:     document.getElementById(`vf-name-${id}`)?.value.trim()     || '',
                age:      document.getElementById(`vf-age-${id}`)?.value.trim()      || '',
                relation: document.getElementById(`vf-relation-${id}`)?.value.trim() || '',
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

        // Clear victim rows
        const list = document.getElementById('ai-victims-list');
        if (list) list.innerHTML = '';
        const noNote = document.getElementById('ai-no-victims');
        if (noNote) { noNote.style.display = ''; noNote.textContent = 'No victims added. Click "Add Victim" if applicable.'; }
        victimCount = 0;

        // Clear inline errors inside crime form
        document.querySelectorAll('#ai-crime-form-panel .ai-input-error').forEach(el => el.classList.remove('ai-input-error'));
        document.querySelectorAll('#ai-crime-form-panel .ai-field-error').forEach(el => el.remove());
    }

    function validateCrimeForm() {
        let valid = true;
        CF_FIELDS.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            // Remove old error
            el.classList.remove('ai-input-error');
            el.parentElement?.querySelector('.ai-field-error')?.remove();
            el.nextElementSibling?.classList.contains('ai-field-error') && el.nextElementSibling.remove();

            if (el.value.trim() === '') {
                valid = false;
                el.classList.add('ai-input-error');
                const msg = document.createElement('span');
                msg.className = 'ai-field-error';
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
        const list     = document.getElementById('ai-crimes-list');
        const noLabel  = document.getElementById('ai-no-crimes');
        const hidden   = document.getElementById('ai-crimes-hidden');

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

            // ── Summary card ──
            const card = document.createElement('div');
            card.className = 'ai-crime-card';
            card.id = `crime-card-${c.id}`;

            const sentenceStr = [c.years ? `${c.years} yr${c.years != 1 ? 's' : ''}` : '',
                                 c.months ? `${c.months} mo` : ''].filter(Boolean).join(', ') || '—';
            const victimList  = c.victims.filter(v => v.name);
            const victimStr   = victimList.map(v => v.name).join(', ') || 'None';

            // Build victim rows HTML for the expanded list
            const victimRowsHtml = victimList.length
                ? victimList.map(v => `
                    <div class="ai-crime-victim-row">
                        <span class="ai-crime-victim-name">${escHtml(v.name)}</span>
                        ${v.age      ? `<span class="ai-crime-victim-detail">Age ${escHtml(v.age)}</span>` : ''}
                        ${v.testifiers ? `<span class="ai-crime-victim-detail">${escHtml(v.testifiers)}</span>` : ''}
                        ${v.relation ? `<span class="ai-crime-victim-detail">${escHtml(v.relation)}</span>` : ''}
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

            // ── Hidden inputs for form submission ──
            const fields = {
                crime_name: c.name, crime_date: c.date, crime_location: c.location,
                law_offended: c.law, crime_description: c.desc,
                sentence_years: c.years, sentence_months: c.months,
                verdict_date: c.verdictDate, case_number: c.caseNum,
                prosecutor: c.prosecutor, judge: c.judge,
            };
            Object.entries(fields).forEach(([key, val]) => {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = `crimes[${idx}][${key}]`;
                inp.value = val;
                hidden.appendChild(inp);
            });
            c.victims.forEach((v, vi) => {
                ['name','age','relation'].forEach(vk => {
                    const inp = document.createElement('input');
                    inp.type  = 'hidden';
                    inp.name  = `crimes[${idx}][victims][${vi}][${vk}]`;
                    inp.value = v[vk];
                    hidden.appendChild(inp);
                });
            });
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

        // Reset edit state
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
            // Update existing crime in-place
            const idx = crimes.findIndex(c => c.id === editingCrimeId);
            if (idx !== -1) crimes[idx] = { id: editingCrimeId, ...vals };
            editingCrimeId = null;

            // Restore panel title and button label
            const panelTitle = document.getElementById('ai-crime-form-title');
            const saveBtn    = document.getElementById('ai-save-crime-btn');
            if (panelTitle) panelTitle.textContent = 'Add Offense';
            if (saveBtn)    saveBtn.textContent    = 'Save Crime';
        } else {
            // Add new crime
            crimeIdCounter++;
            crimes.push({ id: crimeIdCounter, ...vals });
        }

        AiCloseCrimeForm();
        renderCrimes();

        // Clear "at least one crime" error if it was showing
        const noLabel = document.getElementById('ai-no-crimes');
        if (noLabel) { noLabel.style.color = ''; }
    };

    window.AiEditCrime = function (id) {
        const crime = crimes.find(c => c.id === id);
        if (!crime) return;

        editingCrimeId = id;

        // Open the form panel
        AiOpenCrimeForm();

        // Update panel title and button label to reflect edit mode
        const panelTitle = document.getElementById('ai-crime-form-title');
        const saveBtn    = document.getElementById('ai-save-crime-btn');
        if (panelTitle) panelTitle.textContent = 'Edit Offense';
        if (saveBtn)    saveBtn.textContent    = 'Update Crime';

        // Populate scalar fields
        const fieldMap = {
            'cf-crimeName':     crime.name,
            'cf-crimeDate':     crime.date,
            'cf-crimeLocation': crime.location,
            'cf-lawOffended':   crime.law,
            'cf-crimeDesc':     crime.desc,
            'cf-sentenceYears': crime.years,
            'cf-sentenceMonths':crime.months,
            'cf-verdictDate':   crime.verdictDate,
            'cf-testifiers':    crime.testifiers,
            'cf-caseNumber':    crime.caseNum,
            'cf-prosecutor':    crime.prosecutor,
            'cf-judge':         crime.judge,
        };
        Object.entries(fieldMap).forEach(([elId, val]) => {
            const el = document.getElementById(elId);
            if (el) el.value = val || '';
        });

        // Repopulate victim rows
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

    // ── FORM SUBMIT ──────────────────────────────────────────────────
    document.getElementById('addInmateForm')?.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!validateStep(3)) return;

        const data = Object.fromEntries(new FormData(this));
        data.crimes = crimes; // also available as structured array
        console.log('Inmate form payload:', data);

        // TODO: POST to /admin/inmates via fetch() and redirect on success
        // fetch('/admin/inmates', { method: 'POST', body: new FormData(this) })
        //     .then(res => res.json())
        //     .then(() => ShowPage('inmates'));
    });
}

// Safe init — works whether the script fires before or after DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAddInmatePage);
} else {
    initAddInmatePage();
}