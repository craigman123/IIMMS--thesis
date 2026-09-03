(function () {
    'use strict';

    /* -----------------------------------------------------------
       Placeholder inmate data — replace with your real source
       (e.g. hydrate this array from a JSON blob rendered by Blade,
       or fetch it from an endpoint before opening the modal).
       ----------------------------------------------------------- */
    var INMATES = [
        { id: 'INM-1023', name: 'John Doe', block: 'C', status: 'Active' },
        { id: 'INM-1044', name: 'Mia Santos', block: 'A', status: 'Active' },
        { id: 'INM-1067', name: 'Ramon Cruz', block: 'B', status: 'Isolation' },
        { id: 'INM-1098', name: 'Ariel Bautista', block: 'C', status: 'Medical' },
        { id: 'INM-1112', name: 'Leo Fernandez', block: 'D', status: 'Active' },
        { id: 'INM-1130', name: 'Grace Villanueva', block: 'B', status: 'Release pending' },
        { id: 'INM-1155', name: 'Noel Reyes', block: 'A', status: 'Active' },
        { id: 'INM-1170', name: 'Diego Manalo', block: 'D', status: 'Isolation' }
    ];

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

    var tableBody = document.getElementById('incidentTableBody');

    var selectedInmate = null;

    function initials(name) {
        return name.split(' ').map(function (p) { return p[0]; }).join('').slice(0, 2).toUpperCase();
    }

    function renderInmateList() {
        var query = (searchInput.value || '').trim().toLowerCase();
        var block = blockFilter.value;
        var status = statusFilter.value;

        var filtered = INMATES.filter(function (inmate) {
            var matchesQuery = !query ||
                inmate.name.toLowerCase().indexOf(query) !== -1 ||
                inmate.id.toLowerCase().indexOf(query) !== -1;
            var matchesBlock = !block || inmate.block === block;
            var matchesStatus = !status || inmate.status === status;
            return matchesQuery && matchesBlock && matchesStatus;
        });

        listEl.innerHTML = '';
        listEmpty.hidden = filtered.length !== 0;

        filtered.forEach(function (inmate) {
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
                if (btn.classList.contains('is-selecting')) return;
                btn.classList.add('is-selecting');
                btn.setAttribute('aria-pressed', 'true');
                setTimeout(function () {
                    btn.classList.remove('is-selecting');
                    btn.removeAttribute('aria-pressed');
                    selectInmate(inmate);
                }, 450);
            });

            li.appendChild(btn);
            listEl.appendChild(li);
        });
    }

    function selectInmate(inmate) {
        selectedInmate = inmate;
        targetAvatar.textContent = initials(inmate.name);
        targetName.textContent = inmate.name;
        targetMeta.textContent = inmate.id + ' \u00B7 Block ' + inmate.block + ' \u00B7 ' + inmate.status;
        flow.classList.add('inmate-selected');
    }

    function openFlow() {
        flow.setAttribute('aria-hidden', 'false');
        flow.classList.add('is-open');
        renderInmateList();
        document.body.style.overflow = 'hidden';
        setTimeout(function () { searchInput.focus(); }, 350);
    }

    function closeFlow() {
        flow.classList.remove('is-open', 'inmate-selected');
        flow.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        selectedInmate = null;
        form.reset();
    }

    function backToPicker() {
        flow.classList.remove('inmate-selected');
    }

    function nextRefNumber() {
        var count = tableBody.querySelectorAll('tr[data-incident-row]').length + 1;
        var year = new Date().getFullYear();
        return 'INC-' + year + '-' + String(count).padStart(3, '0');
    }

    function severityClass(value) {
        return 'severity-badge severity-' + value.toLowerCase();
    }

    function addIncidentRow(data) {
        var emptyRow = tableBody.querySelector('.empty-cell');
        if (emptyRow) {
            emptyRow.closest('tr').remove();
        }

        var tr = document.createElement('tr');
        tr.setAttribute('data-incident-row', '');
        tr.innerHTML =
            '<td>' + data.ref + '</td>' +
            '<td>' + data.type + '</td>' +
            '<td>' + data.inmate + '</td>' +
            '<td>' + data.location + '</td>' +
            '<td><span class="' + severityClass(data.severity) + '">' + data.severity + '</span></td>' +
            '<td>' + data.date + '</td>' +
            '<td><span class="status-badge status-open">Open</span></td>';

        tableBody.prepend(tr);
    }

    function formatDate(value) {
        if (!value) return '';
        var d = new Date(value);
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) +
            ' ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
    }

    /* -------------------- events -------------------- */
    addBtn.addEventListener('click', openFlow);
    closePickerBtn.addEventListener('click', closeFlow);
    closeReportBtn.addEventListener('click', closeFlow);
    cancelFormBtn.addEventListener('click', closeFlow);
    backBtn.addEventListener('click', backToPicker);

    searchInput.addEventListener('input', renderInmateList);
    blockFilter.addEventListener('change', renderInmateList);
    statusFilter.addEventListener('change', renderInmateList);

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

        addIncidentRow({
            ref: nextRefNumber(),
            type: document.getElementById('incidentType').value,
            inmate: selectedInmate.name,
            location: document.getElementById('incidentLocation').value,
            severity: document.getElementById('incidentSeverity').value,
            date: formatDate(document.getElementById('incidentDateTime').value)
        });

        closeFlow();
    });
})();