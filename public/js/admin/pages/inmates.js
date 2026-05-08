// public/js/admin/pages/inmates.js

document.addEventListener('DOMContentLoaded', () => {
    let currentInmate = null;
    let editState = null;
    let editMugshotFile = null;   // new File chosen by user, or null
    let editMugshotClear = false; // true = user explicitly removed the photo
    let availableCells = [];

    const STATUS_OPTIONS = ['new', 'active', 'transferred', 'hold', 'pending', 'released', 'deceased'];
    const SECURITY_OPTIONS = ['normal', 'medium', 'max', 'extreme', 'deathrow'];
    const DETENTION_OPTIONS = ['sentenced', 'detained', 'transferred'];

    function fallback(value = '--') {
        return value || '--';
    }

    function capitalize(str) {
        if (!str) return '--';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function setMugshotPreview(src) {
        const img         = document.getElementById('edit-mugshot-img');
        const placeholder = document.getElementById('edit-mugshot-placeholder');
        const clearBtn    = document.getElementById('edit-mugshot-clear');

        if (src) {
            img.src           = src;
            img.style.display = 'block';
            placeholder.style.display = 'none';
            if (clearBtn) clearBtn.style.display = '';
        } else {
            img.src           = '';
            img.style.display = 'none';
            placeholder.style.display = '';
            if (clearBtn) clearBtn.style.display = 'none';
        }
    }

    window.InmateClearMugshot = function () {
        editMugshotFile  = null;
        editMugshotClear = true;
        setMugshotPreview(null);
        const fileInput = document.getElementById('edit-mugshot-file');
        if (fileInput) fileInput.value = '';
    };

    function escapeHtml(str = '') {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildName(parts) {
        const lastName = parts.last_name || '';
        const firstMiddle = [parts.first_name, parts.middle_name].filter(Boolean).join(' ');

        if (lastName && firstMiddle) return `${lastName}, ${firstMiddle}`;
        return lastName || firstMiddle || 'Unnamed Inmate';
    }

    function parseCellDisplay(cellValue) {
        let cellDisplay = cellValue ?? '--';

        if (typeof cellDisplay === 'string' && cellDisplay.startsWith('{')) {
            try {
                const parsed = JSON.parse(cellDisplay);
                cellDisplay = parsed.CELL_ID ?? parsed.cell_id ?? '--';
            } catch {
                cellDisplay = '--';
            }
        } else if (typeof cellDisplay === 'object' && cellDisplay !== null) {
            cellDisplay = cellDisplay.cell_id ?? '--';
        }

        return cellDisplay;
    }

    function createSelectOptions(selectId, values, labelMap = null) {
        const select = document.getElementById(selectId);
        if (!select) return;

        select.innerHTML = values
            .map(value => `<option value="${value}">${labelMap?.[value] || capitalize(value)}</option>`)
            .join('');
    }

    function initStaticSelects() {
        createSelectOptions('edit-status', STATUS_OPTIONS, {
            new: 'New Inmate',
            active: 'Active',
            transferred: 'Transferred',
            hold: 'On Hold',
            pending: 'Pending',
            released: 'Released',
            deceased: 'Deceased',
        });

        createSelectOptions('edit-security', SECURITY_OPTIONS, {
            normal: 'Normal',
            medium: 'Medium',
            max: 'Maximum',
            extreme: 'Extreme',
            deathrow: 'Death Row',
        });

        createSelectOptions('edit-detention', DETENTION_OPTIONS, {
            sentenced: 'Sentenced',
            detained: 'Detained',
            transferred: 'Transferred',
        });
    }

    async function fetchCells() {
        const res = await fetch('/admin/cells/data', {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const data = await res.json();
        availableCells = data.cells || [];
    }

    async function fetchInmateDetails(id) {
        const res = await fetch(`/admin/inmates/${id}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const data = await res.json();
        return data.inmate;
    }

    function populateCellSelect(selectedId = null) {
        const select = document.getElementById('edit-cell');
        if (!select) return;

        const options = ['<option value="">Unassigned</option>'].concat(
            availableCells.map(cell => {
                return `<option value="${cell.id}">${escapeHtml(cell.cell_id)} - ${escapeHtml(cell.type || '--')}</option>`;
            })
        );

        select.innerHTML = options.join('');
        select.value = selectedId ? String(selectedId) : '';
    }

    function loadInmateTable() {
        const tbody = document.getElementById('inmateTableBody');
        if (!tbody) return;

        setTimeout(() => {
            tbody.innerHTML = '';

            inmates.forEach(inmate => {
                const row = document.createElement('tr');
                const cellDisplay = parseCellDisplay(inmate.cell);

                row.innerHTML = `
                    <td style="color:var(--muted);font-size:12px">${inmate.id}</td>
                    <td style="font-weight:500;color:var(--white)">${escapeHtml(inmate.name ?? '--')}</td>
                    <td>${escapeHtml(cellDisplay)}</td>
                    <td><span class="status-badge ${inmate.status ?? ''}">${capitalize(inmate.status)}</span></td>
                    <td><span class="security-badge ${inmate.security ?? ''}">${capitalize(inmate.security)}</span></td>
                    <td>${escapeHtml(inmate.admitted ?? '--')}</td>
                    <td>${escapeHtml(inmate.release ?? '--')}</td>
                    <td>
                        <button class="action-btn" onclick="InmateOpenDrawer(${inmate.id})">View</button>
                        <button class="action-btn" style="margin-left:4px" onclick="InmateOpenEditModal(${inmate.id})">Edit</button>
                    </td>
                `;

                tbody.appendChild(row);
            });
        }, 250);
    }

    function fillDrawerFromSummary(inmate) {
        document.getElementById('drawer-id-label').textContent = `#${inmate.id}`;
        document.getElementById('drawer-name-label').textContent = fallback(inmate.name);

        const statusBadge = document.getElementById('drawer-status-badge');
        statusBadge.textContent = capitalize(inmate.status);
        statusBadge.className = `status-badge ${inmate.status ?? ''}`;

        document.getElementById('dv-cell').textContent = parseCellDisplay(inmate.cell);
        document.getElementById('dv-security').textContent = capitalize(inmate.security);
        document.getElementById('dv-detention').textContent = capitalize(inmate.detention);
        document.getElementById('dv-admitted').textContent = fallback(inmate.admitted);
        document.getElementById('dv-release').textContent = fallback(inmate.release);
        document.getElementById('dv-commitment').textContent = fallback(inmate.commitment_order);
        document.getElementById('dv-court').textContent = fallback(inmate.court_branch);

        const img = document.getElementById('drawer-mugshot-img');
        const placeholder = document.getElementById('drawer-mugshot-placeholder');

        if (inmate.mugshot) {
            img.src = inmate.mugshot;
            img.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            img.style.display = 'none';
            placeholder.style.display = '';
        }
    }

    function fillDrawerFromDetails(inmate) {
        document.getElementById('drawer-id-label').textContent = `#${inmate.id}`;
        document.getElementById('drawer-name-label').textContent = buildName(inmate);

        const statusBadge = document.getElementById('drawer-status-badge');
        statusBadge.textContent = capitalize(inmate.status);
        statusBadge.className = `status-badge ${inmate.status ?? ''}`;

        document.getElementById('dv-cell').textContent = fallback(inmate.cell_label);
        document.getElementById('dv-security').textContent = capitalize(inmate.security_lvl);
        document.getElementById('dv-detention').textContent = capitalize(inmate.detention_type);
        document.getElementById('dv-admitted').textContent = fallback(inmate.admission_date);
        document.getElementById('dv-release').textContent = fallback(inmate.release_date);
        document.getElementById('dv-commitment').textContent = fallback(inmate.commitment_order);
        document.getElementById('dv-court').textContent = fallback(inmate.court_branch);

        const img = document.getElementById('drawer-mugshot-img');
        const placeholder = document.getElementById('drawer-mugshot-placeholder');

        if (inmate.mugshot_url) {
            img.src = inmate.mugshot_url;
            img.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            img.style.display = 'none';
            placeholder.style.display = '';
        }
    }

    function normalizeCrime(crime = null) {
        return crime || {
            crime_name: '',
            crime_date: '',
            crime_location: '',
            law_offended: '',
            crime_description: '',
            sentence_years: '',
            sentence_months: '',
            verdict_date: '',
            case_number: '',
            prosecutor: '',
            judge: '',
            victims: [],
        };
    }

    // ── Release date = admission date + sum of all crime sentences ──────────
    function calculateReleaseDate(admissionDate, crimes) {
        if (!admissionDate || !crimes || !crimes.length) return '';

        const base = new Date(admissionDate);
        if (Number.isNaN(base.getTime())) return '';

        let totalMonths = 0;
        crimes.forEach(crime => {
            const years  = parseInt(crime.sentence_years,  10) || 0;
            const months = parseInt(crime.sentence_months, 10) || 0;
            totalMonths += years * 12 + months;
        });

        if (totalMonths <= 0) return '';

        const release = new Date(base);
        release.setMonth(release.getMonth() + totalMonths);

        // Format as YYYY-MM-DD for the date input
        const yyyy = release.getFullYear();
        const mm   = String(release.getMonth() + 1).padStart(2, '0');
        const dd   = String(release.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    function syncReleaseDate() {
        const admissionDate = document.getElementById('edit-admission-date')?.value;
        const crimes = editState?.crimes || [];
        const computed = calculateReleaseDate(admissionDate, crimes);
        const releaseInput = document.getElementById('edit-release-date');
        if (releaseInput && computed) {
            releaseInput.value = computed;
        }
    }

    function calculateAgeFromDob(dob) {
        if (!dob) return '';

        const birthDate = new Date(dob);
        if (Number.isNaN(birthDate.getTime())) return '';

        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDelta = today.getMonth() - birthDate.getMonth();

        if (monthDelta < 0 || (monthDelta === 0 && today.getDate() < birthDate.getDate())) {
            age -= 1;
        }

        return age >= 0 ? age : '';
    }

    function renderVictims(crimeIndex) {
        const crime = editState.crimes[crimeIndex];

        return (crime.victims || []).map((victim, victimIndex) => `
            <div class="inmate-victim-card">
                <div class="inmate-victim-grid">
                    <div class="inmate-edit-group"><label>Name</label><input type="text" data-field="name" data-crime="${crimeIndex}" data-victim="${victimIndex}" class="victim-input" value="${escapeHtml(victim.name || '')}"></div>
                    <div class="inmate-edit-group"><label>Age</label><input type="number" data-field="age" data-crime="${crimeIndex}" data-victim="${victimIndex}" class="victim-input" value="${escapeHtml(victim.age || '')}"></div>
                    <div class="inmate-edit-group"><label>Type</label><input type="text" data-field="testifiers" data-crime="${crimeIndex}" data-victim="${victimIndex}" class="victim-input" value="${escapeHtml(victim.testifiers || '')}"></div>
                    <div class="inmate-edit-group"><label>Relation</label><input type="text" data-field="relation" data-crime="${crimeIndex}" data-victim="${victimIndex}" class="victim-input" value="${escapeHtml(victim.relation || '')}"></div>
                </div>
                <button type="button" class="btn-inline-danger" onclick="InmateRemoveVictim(${crimeIndex}, ${victimIndex})">Remove Victim</button>
            </div>
        `).join('');
    }

    function renderCrimes() {
        const container = document.getElementById('inmate-edit-crimes');
        const empty = document.getElementById('inmate-edit-no-crimes');
        if (!container || !empty) return;

        container.innerHTML = '';
        const crimes = editState?.crimes || [];
        empty.style.display = crimes.length ? 'none' : '';

        crimes.forEach((crime, crimeIndex) => {
            const card = document.createElement('div');
            card.className = 'inmate-crime-card';
            card.innerHTML = `
                <div class="inmate-edit-section-head">
                    <div class="inmate-crime-title">Crime #${crimeIndex + 1}</div>
                    <button type="button" class="btn-inline-danger" onclick="InmateRemoveCrime(${crimeIndex})">Remove Crime</button>
                </div>
                <div class="inmate-edit-grid three">
                    <div class="inmate-edit-group"><label>Crime Name</label><input type="text" class="crime-input" data-field="crime_name" data-crime="${crimeIndex}" value="${escapeHtml(crime.crime_name || '')}"></div>
                    <div class="inmate-edit-group"><label>Crime Date</label><input type="date" class="crime-input" data-field="crime_date" data-crime="${crimeIndex}" value="${escapeHtml(crime.crime_date || '')}"></div>
                    <div class="inmate-edit-group"><label>Crime Location</label><input type="text" class="crime-input" data-field="crime_location" data-crime="${crimeIndex}" value="${escapeHtml(crime.crime_location || '')}"></div>
                </div>
                <div class="inmate-edit-grid two">
                    <div class="inmate-edit-group"><label>Law Offended</label><input type="text" class="crime-input" data-field="law_offended" data-crime="${crimeIndex}" value="${escapeHtml(crime.law_offended || '')}"></div>
                    <div class="inmate-edit-group"><label>Case Number</label><input type="text" class="crime-input" data-field="case_number" data-crime="${crimeIndex}" value="${escapeHtml(crime.case_number || '')}"></div>
                </div>
                <div class="inmate-edit-grid three">
                    <div class="inmate-edit-group"><label>Sentence Years</label><input type="number" class="crime-input" data-field="sentence_years" data-crime="${crimeIndex}" value="${escapeHtml(crime.sentence_years || '')}"></div>
                    <div class="inmate-edit-group"><label>Sentence Months</label><input type="number" class="crime-input" data-field="sentence_months" data-crime="${crimeIndex}" value="${escapeHtml(crime.sentence_months || '')}"></div>
                    <div class="inmate-edit-group"><label>Verdict Date</label><input type="date" class="crime-input" data-field="verdict_date" data-crime="${crimeIndex}" value="${escapeHtml(crime.verdict_date || '')}"></div>
                </div>
                <div class="inmate-edit-grid two">
                    <div class="inmate-edit-group"><label>Prosecutor</label><input type="text" class="crime-input" data-field="prosecutor" data-crime="${crimeIndex}" value="${escapeHtml(crime.prosecutor || '')}"></div>
                    <div class="inmate-edit-group"><label>Judge</label><input type="text" class="crime-input" data-field="judge" data-crime="${crimeIndex}" value="${escapeHtml(crime.judge || '')}"></div>
                </div>
                <div class="inmate-edit-grid one">
                    <div class="inmate-edit-group"><label>Description</label><textarea class="crime-input inmate-edit-textarea" data-field="crime_description" data-crime="${crimeIndex}">${escapeHtml(crime.crime_description || '')}</textarea></div>
                </div>
                <div class="inmate-edit-section-head">
                    <div class="inmate-subsection-title">Victims / Witnesses</div>
                    <button type="button" class="btn-outline-muted" onclick="InmateAddVictim(${crimeIndex})">Add Victim</button>
                </div>
                <div class="inmate-victim-list">${renderVictims(crimeIndex)}</div>
            `;

            container.appendChild(card);
        });

        container.querySelectorAll('.crime-input').forEach(input => {
            input.addEventListener('input', event => {
                const crimeIndex = Number(event.target.dataset.crime);
                const field = event.target.dataset.field;
                editState.crimes[crimeIndex][field] = event.target.value;

                // Recalculate release date when sentence duration changes
                if (field === 'sentence_years' || field === 'sentence_months') {
                    syncReleaseDate();
                }
            });
        });

        container.querySelectorAll('.victim-input').forEach(input => {
            input.addEventListener('input', event => {
                const crimeIndex = Number(event.target.dataset.crime);
                const victimIndex = Number(event.target.dataset.victim);
                const field = event.target.dataset.field;
                editState.crimes[crimeIndex].victims[victimIndex][field] = event.target.value;
            });
        });
    }

    function fillEditForm(inmate) {
        // Reset mugshot state
        editMugshotFile  = null;
        editMugshotClear = false;
        setMugshotPreview(inmate.mugshot_url || null);

        document.getElementById('edit-inmate-id').value = inmate.id;
        document.getElementById('edit-last-name').value = inmate.last_name || '';
        document.getElementById('edit-first-name').value = inmate.first_name || '';
        document.getElementById('edit-middle-name').value = inmate.middle_name || '';
        document.getElementById('edit-status').value = inmate.status || 'new';
        document.getElementById('edit-security').value = inmate.security_lvl || 'normal';
        document.getElementById('edit-detention').value = inmate.detention_type || 'sentenced';
        document.getElementById('edit-admission-date').value = inmate.admission_date || '';
        document.getElementById('edit-release-date').value = inmate.release_date || '';
        document.getElementById('edit-commitment-order').value = inmate.commitment_order || '';
        document.getElementById('edit-court-branch').value = inmate.court_branch || '';
        populateCellSelect(inmate.cell_id || '');

        document.getElementById('edit-dob').value = inmate.personal?.dob || '';
        document.getElementById('edit-age').value = inmate.personal?.age || '';
        document.getElementById('edit-sex').value = inmate.personal?.sex || 'male';
        document.getElementById('edit-civil-status').value = inmate.personal?.civil_status || '';
        document.getElementById('edit-nationality').value = inmate.personal?.nationality || '';
        document.getElementById('edit-religion').value = inmate.personal?.religion || '';
        document.getElementById('edit-phone').value = inmate.personal?.phone || '';
        document.getElementById('edit-email').value = inmate.personal?.email || '';
        document.getElementById('edit-home-address').value = inmate.personal?.home_address || '';
        document.getElementById('edit-sss-number').value = inmate.personal?.sss_number || '';
        document.getElementById('edit-philhealth-number').value = inmate.personal?.philhealth_number || '';
        document.getElementById('edit-pagibig-number').value = inmate.personal?.pagibig_number || '';
        document.getElementById('edit-ec-name').value = inmate.personal?.ec_name || '';
        document.getElementById('edit-ec-relation').value = inmate.personal?.ec_relation || '';
        document.getElementById('edit-ec-phone').value = inmate.personal?.ec_phone || '';

        editState = {
            id: inmate.id,
            crimes: (inmate.crimes || []).map(crime => normalizeCrime({
                ...crime,
                victims: (crime.victims || []).map(victim => ({ ...victim })),
            })),
        };

        renderCrimes();
        // Recompute release date from crimes loaded from DB
        syncReleaseDate();
    }

    function buildEditPayload() {
        const crimes = (editState?.crimes || [])
            .filter(crime => {
                return [
                    crime.crime_name,
                    crime.crime_date,
                    crime.crime_location,
                    crime.law_offended,
                    crime.case_number,
                    crime.crime_description,
                ].some(value => String(value || '').trim() !== '');
            })
            .map(crime => ({
                crime_name: crime.crime_name || '',
                crime_date: crime.crime_date || '',
                crime_location: crime.crime_location || '',
                law_offended: crime.law_offended || '',
                crime_description: crime.crime_description || '',
                sentence_years: crime.sentence_years || 0,
                sentence_months: crime.sentence_months || '',
                verdict_date: crime.verdict_date || '',
                case_number: crime.case_number || '',
                prosecutor: crime.prosecutor || '',
                judge: crime.judge || '',
                victims: (crime.victims || [])
                    .filter(victim => String(victim.name || '').trim() !== '')
                    .map(victim => ({
                        name: victim.name || '',
                        age: victim.age || '',
                        testifiers: victim.testifiers || '',
                        relation: victim.relation || '',
                    })),
            }));

        return {
            last_name: document.getElementById('edit-last-name').value.trim(),
            first_name: document.getElementById('edit-first-name').value.trim(),
            middle_name: document.getElementById('edit-middle-name').value.trim(),
            status: document.getElementById('edit-status').value,
            security_lvl: document.getElementById('edit-security').value,
            detention_type: document.getElementById('edit-detention').value,
            cell_id: document.getElementById('edit-cell').value || '',
            admission_date: document.getElementById('edit-admission-date').value,
            release_date: document.getElementById('edit-release-date').value,
            commitment_order: document.getElementById('edit-commitment-order').value.trim(),
            court_branch: document.getElementById('edit-court-branch').value.trim(),
            dob: document.getElementById('edit-dob').value,
            age: document.getElementById('edit-age').value || '',
            sex: document.getElementById('edit-sex').value,
            civil_status: document.getElementById('edit-civil-status').value.trim(),
            nationality: document.getElementById('edit-nationality').value.trim(),
            religion: document.getElementById('edit-religion').value.trim(),
            phone: document.getElementById('edit-phone').value.trim(),
            email: document.getElementById('edit-email').value.trim(),
            home_address: document.getElementById('edit-home-address').value.trim(),
            sss_number: document.getElementById('edit-sss-number').value.trim(),
            philhealth_number: document.getElementById('edit-philhealth-number').value.trim(),
            pagibig_number: document.getElementById('edit-pagibig-number').value.trim(),
            ec_name: document.getElementById('edit-ec-name').value.trim(),
            ec_relation: document.getElementById('edit-ec-relation').value.trim(),
            ec_phone: document.getElementById('edit-ec-phone').value.trim(),
            crimes,
        };
    }

    function collectValidationMessage(payload) {
        if (payload?.errors) {
            return Object.values(payload.errors).flat().join('\n');
        }

        return payload?.message || 'Failed to save inmate details.';
    }

    document.getElementById('inmateSearch')?.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#inmateTableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    document.getElementById('edit-dob')?.addEventListener('change', event => {
        const ageInput = document.getElementById('edit-age');
        if (!ageInput) return;
        ageInput.value = calculateAgeFromDob(event.target.value);
    });

    document.getElementById('edit-admission-date')?.addEventListener('change', () => {
        syncReleaseDate();
    });

    window.InmateOpenDrawer = async function (id) {
        currentInmate = inmates.find(inmate => inmate.id === id);
        if (!currentInmate) return;

        fillDrawerFromSummary(currentInmate);
        document.getElementById('inmate-drawer').classList.add('open');
        document.getElementById('inmate-drawer-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';

        try {
            const inmate = await fetchInmateDetails(id);
            fillDrawerFromDetails(inmate);
        } catch (error) {
            console.error('Failed to load full inmate drawer details:', error);
        }
    };

    window.InmateCloseDrawer = function () {
        document.getElementById('inmate-drawer').classList.remove('open');
        document.getElementById('inmate-drawer-overlay').classList.remove('open');

        if (!document.getElementById('inmate-edit-overlay')?.classList.contains('open')) {
            document.body.style.overflow = '';
        }
    };

    window.InmateAddCrime = function () {
        editState.crimes.push(normalizeCrime());
        renderCrimes();
    };

    window.InmateRemoveCrime = function (crimeIndex) {
        editState.crimes.splice(crimeIndex, 1);
        renderCrimes();
    };

    window.InmateAddVictim = function (crimeIndex) {
        editState.crimes[crimeIndex].victims.push({ name: '', age: '', testifiers: '', relation: '' });
        renderCrimes();
    };

    window.InmateRemoveVictim = function (crimeIndex, victimIndex) {
        editState.crimes[crimeIndex].victims.splice(victimIndex, 1);
        renderCrimes();
    };

    window.InmateOpenEditModal = async function (id) {
        // ── 1. Open the modal immediately with skeleton ──────────────────
        document.getElementById('inmateEditTitle').textContent    = 'Edit Inmate';
        document.getElementById('inmateEditSubtitle').textContent = 'Loading inmate details…';
        document.getElementById('inmate-edit-overlay').classList.add('open');
        document.getElementById('inmate-edit-form').classList.add('edit-form-loading');
        document.body.style.overflow = 'hidden';

        try {
            if (!availableCells.length) {
                await fetchCells();
            }

            const inmate = await fetchInmateDetails(id);
            document.getElementById('inmate-edit-form').classList.remove('edit-form-loading');

            fillEditForm(inmate);
            document.getElementById('inmateEditSubtitle').textContent = `Editing #${inmate.id} - ${buildName(inmate)}`;
        } catch (error) {
            console.error('Failed to load inmate details:', error);
            document.getElementById('inmate-edit-overlay').classList.remove('open');
            document.body.style.overflow = '';
            alert('Could not load inmate details.');
        }
    };

    window.InmateCloseEditModal = function () {
        document.getElementById('inmate-edit-overlay').classList.remove('open');

        if (!document.getElementById('inmate-drawer')?.classList.contains('open')) {
            document.body.style.overflow = '';
        }

        editState        = null;
        editMugshotFile  = null;
        editMugshotClear = false;
    };

    window.InmateEditOverlayClick = function (event) {
        if (event.target === document.getElementById('inmate-edit-overlay')) {
            InmateCloseEditModal();
        }
    };

    window.InmateEditSubmit = async function (event) {
        event.preventDefault();

        const id  = document.getElementById('edit-inmate-id').value;
        const btn = document.getElementById('inmate-edit-save-btn');
        const payload = buildEditPayload();

        btn.disabled  = true;
        btn.innerHTML = 'Saving...';

        try {
            // Build FormData so we can attach the mugshot file
            const fd = new FormData();
            fd.append('_method', 'PUT'); // Laravel method spoofing

            // Flat fields
            const flatFields = [
                'last_name','first_name','middle_name','status','security_lvl',
                'detention_type','cell_id','admission_date','release_date',
                'commitment_order','court_branch','dob','age','sex','civil_status',
                'nationality','religion','phone','email','home_address',
                'sss_number','philhealth_number','pagibig_number',
                'ec_name','ec_relation','ec_phone',
            ];
            flatFields.forEach(key => {
                if (payload[key] !== undefined && payload[key] !== null) {
                    fd.append(key, payload[key]);
                }
            });

            // Crimes (nested array)
            (payload.crimes || []).forEach((crime, ci) => {
                Object.entries(crime).forEach(([key, val]) => {
                    if (key === 'victims') return;
                    fd.append(`crimes[${ci}][${key}]`, val ?? '');
                });
                (crime.victims || []).forEach((victim, vi) => {
                    Object.entries(victim).forEach(([key, val]) => {
                        fd.append(`crimes[${ci}][victims][${vi}][${key}]`, val ?? '');
                    });
                });
            });

            // Mugshot
            if (editMugshotFile) {
                fd.append('mugshot', editMugshotFile);
            } else if (editMugshotClear) {
                fd.append('remove_mugshot', '1');
            }

            const res = await fetch(`/admin/inmates/${id}`, {
                method: 'POST', // POST + _method=PUT for FormData
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    Accept: 'application/json',
                },
                body: fd,
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                alert(collectValidationMessage(data));
                return;
            }

            const idx = inmates.findIndex(inmate => inmate.id === Number(id));
            if (idx !== -1) {
                inmates[idx] = { ...inmates[idx], ...data.summary };
            }

            loadInmateTable();
            InmateCloseEditModal();
        } catch (error) {
            console.error('Failed to update inmate:', error);
            alert('Network error. Please try again.');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
                Save Changes
            `;
        }
    };

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.getElementById('inmate-edit-overlay')?.classList.contains('open')) {
            InmateCloseEditModal();
        } else if (event.key === 'Escape') {
            InmateCloseDrawer();
        }
    });

    document.getElementById('edit-mugshot-file')?.addEventListener('change', function () {
        const file = this.files?.[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('Photo must be under 5 MB.');
            this.value = '';
            return;
        }

        editMugshotFile  = file;
        editMugshotClear = false;

        const reader = new FileReader();
        reader.onload = e => setMugshotPreview(e.target.result);
        reader.readAsDataURL(file);
    });

    initStaticSelects();
    loadInmateTable();
});