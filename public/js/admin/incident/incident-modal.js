(function () {
    'use strict';

    /* -----------------------------------------------------------
       Endpoints — adjust these if your route names/paths differ.
       ----------------------------------------------------------- */
    var ENDPOINTS = {
        inmates: '/admin/incidents/inmates',
        incidents: '/admin/incidents'
    };

    var flow = document.getElementById('incidentFlow');
    var backdrop = document.getElementById('incidentBackdrop');
    var addBtn = document.getElementById('addIncidentBtn');

    var pickerModal = document.getElementById('inmatePickerModal');
    var closePickerBtn = document.getElementById('closeInmatePicker');
    var searchInput = document.getElementById('inmateSearch');
    var blockFilter = document.getElementById('filterBlock');
    var statusFilter = document.getElementById('filterStatus');
    var listEl = document.getElementById('inmateList');
    var listEmpty = document.getElementById('inmateListEmpty');

    var reportModal = document.getElementById('incidentReportModal');
    var closeReportBtn = document.getElementById('closeIncidentReport');
    var backBtn = document.getElementById('backToPicker');
    var targetAvatar = document.getElementById('incidentTargetAvatar');
    var targetName = document.getElementById('incidentTargetName');
    var targetMeta = document.getElementById('incidentTargetMeta');
    var cancelFormBtn = document.getElementById('cancelIncidentForm');
    var form = document.getElementById('incidentForm');
    var submitBtn = form.querySelector('button[type="submit"]');

    var tableBody = document.getElementById('incidentTableBody');

    var selectedInmate = null;
    var selectedRowBtn = null;
    var searchDebounce = null;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function initials(name) {
        return name.split(' ').map(function (p) { return p[0]; }).join('').slice(0, 2).toUpperCase();
    }

    function severityClass(value) {
        return 'severity-badge severity-' + value.toLowerCase();
    }

    function statusClass(value) {
        return 'status-badge status-' + value.toLowerCase().replace(/\s+/g, '-');
    }

    /* -------------------- inmate list (from backend) -------------------- */

    function fetchInmates() {
        var params = new URLSearchParams();
        var query = (searchInput.value || '').trim();
        if (query) params.set('search', query);
        if (blockFilter.value) params.set('block', blockFilter.value);
        if (statusFilter.value) params.set('status', statusFilter.value);

        listEl.setAttribute('aria-busy', 'true');

        fetch(ENDPOINTS.inmates + '?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed to load inmates');
                return res.json();
            })
            .then(renderInmateList)
            .catch(function (err) {
                listEl.innerHTML = '';
                listEmpty.hidden = false;
                listEmpty.textContent = 'Could not load inmates. Try again.';
                console.error(err);
            })
            .finally(function () {
                listEl.removeAttribute('aria-busy');
            });
    }

    function renderInmateList(inmates) {
        listEl.innerHTML = '';
        listEmpty.hidden = inmates.length !== 0;
        listEmpty.textContent = 'No inmates match those filters.';

        inmates.forEach(function (inmate) {
            var li = document.createElement('li');

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'inmate-list-item pressing-glow';
            btn.setAttribute('data-id', inmate.id);
            btn.setAttribute('aria-pressed', 'false');

            btn.innerHTML =
                '<span class="inmate-avatar">' + initials(inmate.name) + '</span>' +
                '<span class="inmate-info">' +
                    '<span class="inmate-name">' + inmate.name + '</span>' +
                    '<span class="inmate-meta">' +
                        '<span class="inmate-status-dot" data-status="' + inmate.status + '"></span>' +
                        inmate.id + ' &middot; Block ' + inmate.block + ' &middot; ' + inmate.status +
                    '</span>' +
                '</span>';

            btn.addEventListener('click', function () {
                selectInmate(inmate, btn);
            });

            li.appendChild(btn);
            listEl.appendChild(li);
        });
    }

    function selectInmate(inmate, btn) {
        // clear glow off any previously selected row
        if (selectedRowBtn) {
            selectedRowBtn.classList.remove('is-selected');
            selectedRowBtn.setAttribute('aria-pressed', 'false');
        }

        selectedInmate = inmate;
        selectedRowBtn = btn || null;

        if (selectedRowBtn) {
            selectedRowBtn.classList.add('is-selected');
            selectedRowBtn.setAttribute('aria-pressed', 'true');
        }

        targetAvatar.textContent = initials(inmate.name);
        targetName.textContent = inmate.name;
        targetMeta.textContent = inmate.id + ' \u00B7 Block ' + inmate.block + ' \u00B7 ' + inmate.status;

        // small delay so the glow is visible before the panel slides over
        setTimeout(function () {
            flow.classList.add('inmate-selected');
        }, 200);
    }

    /* -------------------- open / close flow -------------------- */

    function openFlow() {
        flow.setAttribute('aria-hidden', 'false');
        flow.classList.add('is-open');
        fetchInmates();
        document.body.style.overflow = 'hidden';
        setTimeout(function () { searchInput.focus(); }, 350);
    }

    function closeFlow() {
        flow.classList.remove('is-open', 'inmate-selected');
        flow.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        selectedInmate = null;
        if (selectedRowBtn) {
            selectedRowBtn.classList.remove('is-selected');
            selectedRowBtn = null;
        }
        form.reset();
    }

    function backToPicker() {
        flow.classList.remove('inmate-selected');
    }

    /* -------------------- incidents table -------------------- */

    function loadIncidents() {
        fetch(ENDPOINTS.incidents, { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (incidents) {
                tableBody.innerHTML = '';
                if (!incidents.length) {
                    tableBody.innerHTML = '<tr><td colspan="7" class="empty-cell">No incidents recorded.</td></tr>';
                    return;
                }
                incidents.forEach(addIncidentRow);
            })
            .catch(function (err) { console.error('Failed to load incidents', err); });
    }

    function addIncidentRow(data) {
        var emptyRow = tableBody.querySelector('.empty-cell');
        if (emptyRow) {
            emptyRow.closest('tr').remove();
        }

        var tr = document.createElement('tr');
        tr.setAttribute('data-incident-row', '');
        tr.setAttribute('data-id', data.id);
        tr.innerHTML =
            '<td>' + data.ref + '</td>' +
            '<td>' + data.type + '</td>' +
            '<td>' + data.inmate + '</td>' +
            '<td>' + data.location + '</td>' +
            '<td><span class="' + severityClass(data.severity) + '">' + data.severity + '</span></td>' +
            '<td>' + data.date + '</td>' +
            '<td><span class="' + statusClass(data.status) + '">' + data.status + '</span></td>';

        tableBody.prepend(tr);
    }

    /* -------------------- events -------------------- */
    addBtn.addEventListener('click', openFlow);
    closePickerBtn.addEventListener('click', closeFlow);
    closeReportBtn.addEventListener('click', closeFlow);
    cancelFormBtn.addEventListener('click', closeFlow);
    backBtn.addEventListener('click', backToPicker);

    searchInput.addEventListener('input', function () {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(fetchInmates, 250);
    });
    blockFilter.addEventListener('change', fetchInmates);
    statusFilter.addEventListener('change', fetchInmates);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && flow.classList.contains('is-open')) {
            if (flow.classList.contains('inmate-selected')) {
                backToPicker();
            } else {
                closeFlow();
            }
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!selectedInmate) return;

        submitBtn.disabled = true;

        fetch(ENDPOINTS.incidents, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify({
                inmate_id: selectedInmate.db_id || selectedInmate.id,
                type: document.getElementById('incidentType').value,
                location: document.getElementById('incidentLocation').value,
                severity: document.getElementById('incidentSeverity').value,
                occurred_at: document.getElementById('incidentDateTime').value,
                description: document.getElementById('incidentDescription').value
            })
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed to submit incident');
                return res.json();
            })
            .then(function (incident) {
                addIncidentRow(incident);
                closeFlow();
            })
            .catch(function (err) {
                console.error(err);
                alert('Could not submit the incident report. Please try again.');
            })
            .finally(function () {
                submitBtn.disabled = false;
            });
    });

    // initial table load
    loadIncidents();
})();