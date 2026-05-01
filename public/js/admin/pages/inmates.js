// public/js/admin/pages/inmates.js

document.addEventListener('DOMContentLoaded', () => {

    // ── HELPERS ──────────────────────────────────────────────────────
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // ── INMATE TABLE ─────────────────────────────────────────────────
    function loadInmateTable() {
        const tbody = document.getElementById('inmateTableBody');
        if (!tbody) return;

        setTimeout(() => {
            tbody.innerHTML = '';
            inmates.forEach(inmate => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td style="color:var(--muted);font-size:12px">${inmate.id}</td>
                    <td style="font-weight:500;color:var(--white)">${inmate.name}</td>
                    <td>${inmate.cell}</td>
                    <td><span class="status-badge ${inmate.status}">${capitalize(inmate.status)}</span></td>
                    <td>${inmate.admitted}</td>
                    <td>${inmate.release}</td>
                    <td>
                        <button class="action-btn">View</button>
                        <button class="action-btn" style="margin-left:4px">Edit</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }, 700);
    }

    // ── SEARCH FILTER ────────────────────────────────────────────────
    document.getElementById('inmateSearch')?.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#inmateTableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // ── MODAL ─────────────────────────────────────────────────────────
    const modal = document.getElementById('addInmateModal');
    const form  = document.getElementById('addInmateForm');

    window.OpenAddInmateModal = function () {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.CloseAddInmateModal = function () {
        modal.classList.remove('open');
        document.body.style.overflow = '';
        form?.reset();
    };

    // Close on backdrop click
    modal?.addEventListener('click', function (e) {
        if (e.target === modal) CloseAddInmateModal();
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) {
            CloseAddInmateModal();
        }
    });

    // Form submit (wire to your real API later)
    form?.addEventListener('submit', function (e) {
        e.preventDefault();
        // TODO: POST to /admin/inmates via fetch()
        console.log('Add inmate submitted:', Object.fromEntries(new FormData(form)));
        CloseAddInmateModal();
    });

    // ── INIT ─────────────────────────────────────────────────────────
    loadInmateTable();
});