// public/js/admin/pages/cells.js

let allCells   = [];    // cache for client-side filtering
let _drawerCell = null; // currently open cell object

// ── Helpers ───────────────────────────────────────────────────────────────────

function setEl(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

// ── Load & render grid ────────────────────────────────────────────────────────

async function loadCellGrid() {
    const grid      = document.getElementById('cellGrid');
    const emptyNote = document.getElementById('cell-empty');
    if (!grid) return;

    // Skeleton shimmer while fetching
    grid.innerHTML = Array(8).fill(
        `<div class="cell-block" style="
            background: linear-gradient(90deg, var(--navy-border) 25%, var(--navy-light) 50%, var(--navy-border) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            min-height: 90px;
            border-color: transparent;
            pointer-events: none;
        "></div>`
    ).join('');

    try {
        const res = await fetch('/admin/cells/data', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        allCells = data.cells || [];
        renderCellStats(data.stats || {});
        applyFilters();

    } catch (err) {
        console.warn('Cell data fetch failed:', err);
        grid.innerHTML = `
            <div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--muted); font-size:13px;">
                Could not load cell data.
                <a href="#" onclick="loadCellGrid(); return false;"
                   style="color:var(--gold); margin-left:6px; text-decoration:none; font-weight:600;">
                   Retry
                </a>
            </div>`;
    }
}

function renderCellStats(stats) {
    setEl('cell-stat-total',       stats.total       ?? '—');
    setEl('cell-stat-available',   stats.available   ?? '—');
    setEl('cell-stat-full',        stats.full        ?? '—');
    setEl('cell-stat-maintenance', stats.maintenance ?? '—');
}

function applyFilters() {
    const grid         = document.getElementById('cellGrid');
    const emptyNote    = document.getElementById('cell-empty');
    const statusFilter = document.getElementById('cell-filter-status')?.value || '';
    const typeFilter   = document.getElementById('cell-filter-type')?.value   || '';

    const filtered = allCells.filter(c => {
        const matchStatus = !statusFilter || c.status === statusFilter;
        const matchType   = !typeFilter   || c.type   === typeFilter;
        return matchStatus && matchType;
    });

    grid.innerHTML = '';

    if (filtered.length === 0) {
        if (emptyNote) emptyNote.style.display = 'block';
        return;
    }

    if (emptyNote) emptyNote.style.display = 'none';

    filtered.forEach(c => {
        const el = document.createElement('div');
        el.className = `cell-block ${cellStatusClass(c)}`;
        el.title     = `Cell ${c.cell_id} — ${c.type} — ${c.occupancy}/${c.capacity}`;
        el.setAttribute('role', 'button');
        el.setAttribute('tabindex', '0');
        el.innerHTML = `
            <span class="cell-number">${c.cell_id}</span>
            <span class="cell-type-tag">${c.type}</span>
            <span class="cell-occupancy">${c.occupancy} / ${c.capacity}</span>
            <span class="cell-status-dot ${c.status}"></span>
        `;

        // Open drawer on click or Enter/Space
        el.addEventListener('click', () => openCellDrawer(c));
        el.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openCellDrawer(c); }
        });

        grid.appendChild(el);
    });
}

function cellStatusClass(cell) {
    if (cell.status === 'maintenance') return 'maintenance';
    if (cell.status === 'condemned')   return 'condemned';
    if (cell.status === 'full')        return 'full';
    if (cell.occupancy === 0)          return 'empty';
    return 'occupied';
}

// Wire filters
document.getElementById('cell-filter-status')?.addEventListener('change', applyFilters);
document.getElementById('cell-filter-type')?.addEventListener('change',   applyFilters);

// ── Cell Detail Drawer ────────────────────────────────────────────────────────

function openCellDrawer(cell) {
    _drawerCell = cell;

    const drawer  = document.getElementById('cellDrawer');
    const overlay = document.getElementById('cellDrawerOverlay');
    if (!drawer || !overlay) return;

    // ── Populate static fields ──
    setEl('drawerCellId',   cell.cell_id);
    setEl('drawerType',     cell.type     || '—');
    setEl('drawerBlock',    cell.block     || extractBlock(cell.cell_id));
    setEl('drawerCapacity', cell.capacity  ?? '—');
    setEl('drawerOccupied', cell.occupancy ?? '—');

    // Status badge
    const badge = document.getElementById('drawerStatusBadge');
    if (badge) {
        badge.textContent = cell.status ?? '—';
        badge.className   = `cell-drawer-badge ${cell.status ?? ''}`;
    }

    // Occupancy bar
    const pct    = cell.capacity > 0 ? Math.round((cell.occupancy / cell.capacity) * 100) : 0;
    const bar    = document.getElementById('drawerOccBar');
    const pctEl  = document.getElementById('drawerOccPct');
    const fracEl = document.getElementById('drawerOccFraction');

    if (bar) {
        bar.style.width = '0%'; // reset before animation
        bar.className   = 'cell-occ-bar-fill' + (pct >= 100 ? ' full' : pct >= 75 ? ' warn' : '');
        requestAnimationFrame(() => { bar.style.width = `${pct}%`; });
    }

    if (pctEl)  pctEl.textContent  = `${pct}%`;
    if (fracEl) fracEl.textContent = `${cell.occupancy} / ${cell.capacity}`;

    // ── Render inmates (skeleton → async fetch) ──
    renderInmateSkeleton();
    fetchCellInmates(cell.cell_id);

    // ── Open ──
    overlay.classList.add('open');
    drawer.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeCellDrawer() {
    const drawer  = document.getElementById('cellDrawer');
    const overlay = document.getElementById('cellDrawerOverlay');

    drawer?.classList.remove('open');
    overlay?.classList.remove('open');
    document.body.style.overflow = '';
    _drawerCell = null;
}

// Close on Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeCellDrawer();
});

// ── Inmate data ───────────────────────────────────────────────────────────────

function renderInmateSkeleton() {
    const list = document.getElementById('drawerInmateList');
    if (!list) return;
    list.innerHTML = Array(3).fill(
        '<div class="cell-inmate-skeleton"></div>'
    ).join('');
    setEl('drawerInmateCount', '…');
}

async function fetchCellInmates(cellId) {
    const list = document.getElementById('drawerInmateList');
    if (!list) return;

    try {
        const res = await fetch(`/admin/cells/${encodeURIComponent(cellId)}/inmates`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        renderInmateList(data.inmates || []);

    } catch (err) {
        console.warn('Could not fetch inmates for cell', cellId, err);
        renderInmateList(_drawerCell?.inmates || []);
    }
}

function renderInmateList(inmates) {
    const list = document.getElementById('drawerInmateList');
    if (!list) return;

    setEl('drawerInmateCount', inmates.length);

    if (inmates.length === 0) {
        list.innerHTML = `
            <div class="cell-inmate-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
                <span>No inmates assigned to this cell.</span>
            </div>`;
        return;
    }

    list.innerHTML = inmates.map(inmate => {
        const initials    = getInitials(inmate.name);
        const statusClass = inmateStatusClass(inmate.status);
        const statusLabel = inmate.status ? capitalize(inmate.status) : '';

        return `
            <div class="cell-inmate-row">
                <div class="cell-inmate-avatar">${initials}</div>
                <div class="cell-inmate-info">
                    <span class="cell-inmate-name">${escHtml(inmate.name)}</span>
                    <span class="cell-inmate-sub">${escHtml(inmate.inmate_id || '')}${inmate.inmate_id && inmate.crime ? ' · ' : ''}${escHtml(inmate.crime || '')}</span>
                </div>
                ${statusLabel
                    ? `<span class="status-badge ${statusClass} cell-inmate-status">${statusLabel}</span>`
                    : ''}
            </div>`;
    }).join('');
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function getInitials(name = '') {
    return name.trim().split(/\s+/)
        .slice(0, 2)
        .map(w => w[0]?.toUpperCase() || '')
        .join('');
}

function inmateStatusClass(status = '') {
    const map = {
        active:      'active',
        released:    'released',
        transferred: 'transferred',
        incident:    'incident',
        new:         'new',
        pending:     'pending',
    };
    return map[status?.toLowerCase()] || '';
}

function extractBlock(cellId = '') {
    const match = cellId.match(/^([A-Za-z]+)/);
    return match ? `Block ${match[1].toUpperCase()}` : '—';
}

function capitalize(str = '') {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function escHtml(str = '') {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ── Edit shortcut from drawer ─────────────────────────────────────────────────

window.editCellFromDrawer = function () {
    if (!_drawerCell) return;
    // Keep drawer open underneath — modal sits on top (z-index 500 > drawer 400)
    openCellEditModal(_drawerCell);
};

// ── Expose globals ────────────────────────────────────────────────────────────

window.openCellDrawer  = openCellDrawer;
window.closeCellDrawer = closeCellDrawer;
window.loadCellGrid    = loadCellGrid;

// ── Init ──────────────────────────────────────────────────────────────────────

// Re-load whenever the cells page becomes active
document.addEventListener('pageChanged', (e) => {
    if (e.detail?.page === 'cells') loadCellGrid();
});

// Also load immediately if already active on mount
if (document.getElementById('page-cells')?.classList.contains('active')) {
    loadCellGrid();
}

// public/js/admin/pages/cell-edit-modal.js
// Handles the centered Edit Cell modal launched from the detail drawer.

// ── State ─────────────────────────────────────────────────────────────────────

let _editCell = null; // cell object currently being edited

// ── Open / Close ──────────────────────────────────────────────────────────────

/**
 * Open the edit modal pre-populated with `cell` data.
 * Called by editCellFromDrawer() in cells.js.
 */
function openCellEditModal(cell) {
    _editCell = cell;

    // Populate header
    const titleEl    = document.getElementById('cellEditModalTitle');
    const subtitleEl = document.getElementById('cellEditModalSubtitle');
    if (titleEl)    titleEl.textContent    = `Edit Cell`;
    if (subtitleEl) subtitleEl.textContent = `${cell.cell_id}  ·  ${cell.type}  ·  Block ${cell.block || extractBlock(cell.cell_id)}`;

    // Populate fields
    _setSelectValue('editCellType',     cell.type);
    _setSelectValue('editCellStatus',   cell.status);

    const capInput = document.getElementById('editCellCapacity');
    if (capInput) {
        capInput.value = cell.capacity ?? '';
        // Enforce minimum = current occupancy so capacity can never drop below housed inmates
        capInput.min = cell.occupancy ?? 1;
    }

    // Clear previous errors / warnings
    _clearErrors();
    _setCapacityWarning(false);

    // Open overlay
    const overlay = document.getElementById('cellEditOverlay');
    overlay?.classList.add('open');
    document.body.style.overflow = 'hidden';

    // Focus first field for a11y
    setTimeout(() => document.getElementById('editCellType')?.focus(), 50);
}

function closeCellEditModal() {
    document.getElementById('cellEditOverlay')?.classList.remove('open');
    document.body.style.overflow = '';
    _editCell = null;
}

/** Close only when the dark backdrop itself is clicked (not the modal card) */
function handleEditOverlayClick(e) {
    if (e.target === document.getElementById('cellEditOverlay')) {
        closeCellEditModal();
    }
}

// Close on Escape (stacked on top of the drawer's own Escape listener)
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.getElementById('cellEditOverlay')?.classList.contains('open')) {
        e.stopImmediatePropagation(); // don't also close the drawer
        closeCellEditModal();
    }
});

// ── Capacity warning ──────────────────────────────────────────────────────────

document.getElementById('editCellCapacity')?.addEventListener('input', function () {
    if (!_editCell) return;
    const val = parseInt(this.value, 10);
    const minAllowed = _editCell.occupancy ?? 0;
    _setCapacityWarning(!isNaN(val) && val < minAllowed);
});

function _setCapacityWarning(show) {
    const warn = document.getElementById('cellEditCapacityWarn');
    const text = document.getElementById('cellEditCapacityWarnText');
    if (!warn) return;

    if (show && _editCell) {
        text.textContent = `Capacity cannot be lower than current occupancy (${_editCell.occupancy}).`;
        warn.classList.add('visible');
    } else {
        warn.classList.remove('visible');
    }
}

// ── Submit ────────────────────────────────────────────────────────────────────

async function submitCellEdit() {
    if (!_editCell) return;

    _clearErrors();

    const type     = document.getElementById('editCellType')?.value     || '';
    const capacity = parseInt(document.getElementById('editCellCapacity')?.value, 10);
    const status   = document.getElementById('editCellStatus')?.value   || '';

    // ── Client-side validation ──
    let valid = true;

    const validTypes    = ['Luxury', 'Standard', 'Dormitory', 'Solitary'];
    const validStatuses = ['available', 'full', 'maintenance', 'condemned'];

    if (!validTypes.includes(type)) {
        _showFieldError('fieldType', 'errorType'); valid = false;
    }
    if (isNaN(capacity) || capacity < 1 || capacity > 50) {
        _showFieldError('fieldCapacity', 'errorCapacity'); valid = false;
    } else if (capacity < (_editCell.occupancy ?? 0)) {
        // Redundant with the warning below, but keeps valid=false in one place
        _setCapacityWarning(true); valid = false;
    }
    if (!validStatuses.includes(status)) {
        _showFieldError('fieldStatus', 'errorStatus'); valid = false;
    }

    if (!valid) return;

    // ── Submit to server ──
    const btn = document.getElementById('cellEditSaveBtn');
    btn?.classList.add('loading');

    try {
        const res = await fetch(`/admin/cells/${encodeURIComponent(_editCell.cell_id)}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': _getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ type, capacity, status }),
        });

        if (res.status === 422) {
            const body = await res.json();
            _handleServerErrors(body.errors || {});
            return;
        }

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        // ── Success: update local cache & re-render ──
        const idx = allCells.findIndex(c => c.cell_id === _editCell.cell_id);
        if (idx !== -1) {
            allCells[idx] = { ...allCells[idx], type, capacity, status };
        }

        closeCellEditModal();
        closeCellDrawer();

        // Reload grid to reflect changes (also refreshes stats)
        await loadCellGrid();

        _showToast(`Cell ${_editCell.cell_id} updated successfully.`, 'success');

    } catch (err) {
        console.error('Cell update failed:', err);
        _showToast('Could not save changes. Please try again.', 'error');
    } finally {
        btn?.classList.remove('loading');
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function _setSelectValue(id, value) {
    const el = document.getElementById(id);
    if (!el || value == null) return;
    const opt = [...el.options].find(o => o.value === value);
    if (opt) el.value = value;
}

function _clearErrors() {
    ['fieldType', 'fieldCapacity', 'fieldStatus'].forEach(id => {
        document.getElementById(id)?.classList.remove('has-error');
    });
    _setCapacityWarning(false);
}

function _showFieldError(fieldId, errorId) {
    document.getElementById(fieldId)?.classList.add('has-error');
    // error text is already set in HTML; just make it visible via CSS
}

function _handleServerErrors(errors) {
    if (errors.type)     _showFieldError('fieldType',     'errorType');
    if (errors.capacity) {
        _showFieldError('fieldCapacity', 'errorCapacity');
        const errEl = document.getElementById('errorCapacity');
        if (errEl && errors.capacity[0]) errEl.textContent = errors.capacity[0];
    }
    if (errors.status)   _showFieldError('fieldStatus',   'errorStatus');
}

function _getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * Lightweight toast — falls back gracefully if your app has its own.
 * Replace with your existing toast/snackbar system as needed.
 */
function _showToast(message, type = 'success') {
    // If a global showToast / notify already exists, use it
    if (typeof window.showToast === 'function') {
        window.showToast(message, type); return;
    }

    const existing = document.getElementById('_cellEditToast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = '_cellEditToast';
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(8px);
        background: ${type === 'success' ? 'var(--navy-light)' : 'var(--red-dim)'};
        border: 1px solid ${type === 'success' ? 'var(--navy-border)' : 'rgba(219,68,68,.3)'};
        color: ${type === 'success' ? 'var(--white)' : 'var(--red)'};
        padding: 10px 20px;
        border-radius: 10px;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        z-index: 9999;
        opacity: 0;
        transition: opacity .2s, transform .2s;
        pointer-events: none;
        white-space: nowrap;
        box-shadow: 0 8px 24px rgba(0,0,0,.3);
    `;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.opacity   = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';
    });

    setTimeout(() => {
        toast.style.opacity   = '0';
        toast.style.transform = 'translateX(-50%) translateY(8px)';
        setTimeout(() => toast.remove(), 250);
    }, 3200);
}

// ── Expose globals ────────────────────────────────────────────────────────────

window.openCellEditModal      = openCellEditModal;
window.closeCellEditModal     = closeCellEditModal;
window.handleEditOverlayClick = handleEditOverlayClick;
window.submitCellEdit         = submitCellEdit;