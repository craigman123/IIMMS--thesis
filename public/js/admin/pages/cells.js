// public/js/admin/pages/cells.js

let allCells = [];
let _drawerCell = null;
let _blockEdit = null;
let _cellEdit = null;

function setEl(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function escHtml(str = '') {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function capitalize(str = '') {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function isHoldingCell(cell) {
    return (cell?.type || '').trim().toLowerCase() === 'holding cell';
}

function blockSortValue(block = '') {
    const raw = String(block || '').trim().toUpperCase();
    if (!raw) return Number.MAX_SAFE_INTEGER;

    let value = 0;
    for (const ch of raw) value = (value * 26) + (ch.charCodeAt(0) - 64);
    return value;
}

function compareCells(a, b) {
    if (isHoldingCell(a) && !isHoldingCell(b)) return -1;
    if (!isHoldingCell(a) && isHoldingCell(b)) return 1;

    const blockDiff = blockSortValue(a.block) - blockSortValue(b.block);
    if (blockDiff !== 0) return blockDiff;

    const numA = Number(a.block_number || 0);
    const numB = Number(b.block_number || 0);
    if (numA !== numB) return numA - numB;

    return String(a.cell_id || '').localeCompare(String(b.cell_id || ''));
}

function cellStatusClass(cell) {
    if (cell.status === 'maintenance') return 'maintenance';
    if (cell.status === 'condemned') return 'condemned';
    if (cell.status === 'full') return 'full';
    if (Number(cell.occupancy) === 0) return 'empty';
    return 'occupied';
}

function groupCells(cells) {
    const grouped = new Map();

    [...cells].sort(compareCells).forEach(cell => {
        const key = isHoldingCell(cell) ? '__holding__' : String(cell.block || '').toUpperCase();
        if (!grouped.has(key)) grouped.set(key, []);
        grouped.get(key).push(cell);
    });

    return [...grouped.entries()].sort(([a], [b]) => {
        if (a === '__holding__') return -1;
        if (b === '__holding__') return 1;
        return blockSortValue(a) - blockSortValue(b);
    });
}

function getBlockSummary(cells) {
    const occupancy = cells.reduce((sum, cell) => sum + Number(cell.occupancy || 0), 0);
    const capacity = cells.reduce((sum, cell) => sum + Number(cell.capacity || 0), 0);
    const statusCounts = cells.reduce((map, cell) => {
        map[cell.status] = (map[cell.status] || 0) + 1;
        return map;
    }, {});

    return { occupancy, capacity, statusCounts };
}

function getAvailabilityBadge(summary, totalCells) {
    const available = summary.statusCounts.available || 0;
    const full = summary.statusCounts.full || 0;
    const maintenance = summary.statusCounts.maintenance || 0;
    const condemned = summary.statusCounts.condemned || 0;

    if (available > 0) return { label: `${available} AVAILABLE`, className: 'available' };
    if (full === totalCells && totalCells > 0) return { label: `${full} FULL`, className: 'full' };
    if (maintenance > 0) return { label: `${maintenance} MAINTENANCE`, className: 'maintenance' };
    if (condemned > 0) return { label: `${condemned} CONDEMNED`, className: 'condemned' };

    return { label: `${totalCells} CELLS`, className: '' };
}

async function loadCellGrid() {
    const grid = document.getElementById('cellGrid');
    if (!grid) return;

    grid.innerHTML = Array(3).fill(`
        <div class="cell-board-skeleton">
            <div class="cell-board-skeleton-head"></div>
            <div class="cell-board-skeleton-card"></div>
            <div class="cell-board-skeleton-card"></div>
        </div>
    `).join('');

    try {
        const res = await fetch('/admin/cells/data', {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        allCells = [...(data.cells || [])].sort(compareCells);
        renderCellStats(data.stats || {});
        applyFilters();
    } catch (err) {
        console.warn('Cell data fetch failed:', err);
        grid.innerHTML = `
            <div style="text-align:center; padding:40px; color:var(--muted); font-size:13px;">
                Could not load cell data.
                <a href="#" onclick="loadCellGrid(); return false;" style="color:var(--gold); margin-left:6px; text-decoration:none; font-weight:600;">Retry</a>
            </div>
        `;
    }
}

function renderCellStats(stats) {
    setEl('cell-stat-total', stats.total ?? '—');
    setEl('cell-stat-available', stats.available ?? '—');
    setEl('cell-stat-full', stats.full ?? '—');
    setEl('cell-stat-maintenance', stats.maintenance ?? '—');
}

function applyFilters() {
    const grid = document.getElementById('cellGrid');
    const emptyNote = document.getElementById('cell-empty');
    if (!grid) return;

    const statusFilter = document.getElementById('cell-filter-status')?.value || '';
    const typeFilter = document.getElementById('cell-filter-type')?.value || '';

    const filtered = allCells.filter(cell => {
        const matchStatus = !statusFilter || cell.status === statusFilter;
        const matchType = !typeFilter || cell.type === typeFilter;
        return matchStatus && matchType;
    });

    grid.innerHTML = '';

    if (!filtered.length) {
        if (emptyNote) emptyNote.style.display = 'block';
        return;
    }

    if (emptyNote) emptyNote.style.display = 'none';

    groupCells(filtered).forEach(([blockKey, cells]) => {
        const first = cells[0];
        const summary = getBlockSummary(cells);
        const badge = getAvailabilityBadge(summary, cells.length);
        const blockLabel = blockKey === '__holding__' ? 'Holding Cell' : `Block ${blockKey}`;
        const canManage = !cells.some(isHoldingCell);

        const board = document.createElement('section');
        board.className = `cell-board${canManage ? '' : ' holding'}`;
        board.innerHTML = `
            <div class="cell-board-header">
                <div class="cell-board-head-main">
                    <div class="cell-board-title-row">
                        <span class="cell-board-title">${escHtml(blockLabel)}</span>
                        <span class="cell-board-chip">${cells.length} cell${cells.length !== 1 ? 's' : ''}</span>
                    </div>
                    <div class="cell-board-subtitle">${escHtml(first.type || '—')} · ${summary.occupancy}/${summary.capacity} occupied</div>
                    <div class="cell-board-stats">
                        <span class="cell-board-stat ${badge.className}">${escHtml(badge.label)}</span>
                    </div>
                </div>
                <button class="btn-outline-muted cell-board-manage-btn"${canManage ? '' : ' disabled'}>${canManage ? 'Edit Block' : 'Locked'}</button>
            </div>
            <div class="cell-board-grid"></div>
        `;

        const manageBtn = board.querySelector('.cell-board-manage-btn');
        if (canManage) manageBtn.addEventListener('click', () => openBlockEditModal(blockKey));

        const boardGrid = board.querySelector('.cell-board-grid');
        cells.forEach(cell => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = `cell-block ${cellStatusClass(cell)}${isHoldingCell(cell) ? ' holding' : ''}`;
            card.title = `Cell ${cell.cell_id} — ${cell.type} — ${cell.occupancy}/${cell.capacity}`;
            card.innerHTML = `
                <span class="cell-number">${escHtml(cell.cell_id)}</span>
                <span class="cell-type-tag">${escHtml(cell.type || '—')}</span>
                <span class="cell-occupancy">${cell.occupancy} / ${cell.capacity}</span>
                <span class="cell-status-dot ${cell.status}"></span>
            `;
            card.addEventListener('click', () => openCellDrawer(cell));
            boardGrid.appendChild(card);
        });

        grid.appendChild(board);
    });
}

document.getElementById('cell-filter-status')?.addEventListener('change', applyFilters);
document.getElementById('cell-filter-type')?.addEventListener('change', applyFilters);

function openCellDrawer(cell) {
    _drawerCell = cell;

    const drawer = document.getElementById('cellDrawer');
    const overlay = document.getElementById('cellDrawerOverlay');
    if (!drawer || !overlay) return;

    setEl('drawerCellId', cell.cell_id);
    setEl('drawerType', cell.type || '—');
    setEl('drawerBlock', isHoldingCell(cell) ? 'Holding Cell' : `Block ${cell.block || '—'}`);
    setEl('drawerCapacity', cell.capacity ?? '—');
    setEl('drawerOccupied', cell.occupancy ?? '—');

    const badge = document.getElementById('drawerStatusBadge');
    if (badge) {
        badge.textContent = cell.status ?? '—';
        badge.className = `cell-drawer-badge ${cell.status ?? ''}`;
    }

    const pct = cell.capacity > 0 ? Math.round((cell.occupancy / cell.capacity) * 100) : 0;
    const bar = document.getElementById('drawerOccBar');
    const pctEl = document.getElementById('drawerOccPct');
    const fracEl = document.getElementById('drawerOccFraction');

    if (bar) {
        bar.style.width = '0%';
        bar.className = `cell-occ-bar-fill${pct >= 100 ? ' full' : pct >= 75 ? ' warn' : ''}`;
        requestAnimationFrame(() => { bar.style.width = `${pct}%`; });
    }

    if (pctEl) pctEl.textContent = `${pct}%`;
    if (fracEl) fracEl.textContent = `${cell.occupancy} / ${cell.capacity}`;

    renderInmateSkeleton();
    fetchCellInmates(cell.id);

    const editBtn = document.getElementById('cellDrawerEditBtn');
    const editNote = document.getElementById('cellDrawerEditNote');
    const locked = isHoldingCell(cell);
    if (editBtn) {
        editBtn.disabled = locked;
        editBtn.classList.toggle('btn-disabled', locked);
    }
    if (editNote) editNote.style.display = locked ? 'block' : 'none';

    overlay.classList.add('open');
    drawer.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeCellDrawer() {
    document.getElementById('cellDrawer')?.classList.remove('open');
    document.getElementById('cellDrawerOverlay')?.classList.remove('open');

    if (!document.getElementById('blockEditOverlay')?.classList.contains('open') &&
        !document.getElementById('cellEditOverlay')?.classList.contains('open')) {
        document.body.style.overflow = '';
    }

    _drawerCell = null;
}

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.getElementById('blockDeleteOverlay')?.classList.contains('open')) {
        closeBlockDeleteModal();
        return;
    }
    if (event.key === 'Escape' && document.getElementById('cellEditOverlay')?.classList.contains('open')) {
        closeCellEditModal();
        return;
    }
    if (event.key === 'Escape' && document.getElementById('blockEditOverlay')?.classList.contains('open')) {
        closeBlockEditModal();
        return;
    }
    if (event.key === 'Escape' && document.getElementById('cellDrawer')?.classList.contains('open')) {
        closeCellDrawer();
    }
});

function renderInmateSkeleton() {
    const list = document.getElementById('drawerInmateList');
    if (!list) return;
    list.innerHTML = Array(3).fill('<div class="cell-inmate-skeleton"></div>').join('');
    setEl('drawerInmateCount', '…');
}

async function fetchCellInmates(cellId) {
    const list = document.getElementById('drawerInmateList');
    if (!list) return;

    try {
        const res = await fetch(`/admin/cells/${encodeURIComponent(cellId)}/inmates`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        renderInmateList(data.inmates || []);
    } catch (err) {
        console.warn('Could not fetch inmates for cell', cellId, err);
        renderInmateList([]);
    }
}

function renderInmateList(inmates) {
    const list = document.getElementById('drawerInmateList');
    if (!list) return;

    setEl('drawerInmateCount', inmates.length);

    if (!inmates.length) {
        list.innerHTML = `
            <div class="cell-inmate-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
                <span>No inmates assigned to this cell.</span>
            </div>
        `;
        return;
    }

    list.innerHTML = inmates.map(inmate => {
        const status = inmate.status ? capitalize(inmate.status) : '';
        return `
            <div class="cell-inmate-row">
                <div class="cell-inmate-avatar">${getInitials(inmate.name)}</div>
                <div class="cell-inmate-info">
                    <span class="cell-inmate-name">${escHtml(inmate.name)}</span>
                    <span class="cell-inmate-sub">${escHtml(inmate.inmate_id || '')}${inmate.crime ? ' · ' + escHtml(inmate.crime) : ''}</span>
                </div>
                ${status ? `<span class="status-badge ${inmateStatusClass(inmate.status)} cell-inmate-status">${escHtml(status)}</span>` : ''}
            </div>
        `;
    }).join('');
}

function getInitials(name = '') {
    return name.trim().split(/\s+/).slice(0, 2).map(word => word[0]?.toUpperCase() || '').join('');
}

function inmateStatusClass(status = '') {
    const map = {
        active: 'active',
        released: 'released',
        transferred: 'transferred',
        incident: 'incident',
        new: 'new',
        pending: 'pending',
    };
    return map[status.toLowerCase()] || '';
}

function getBlockCells(block) {
    return allCells.filter(cell => String(cell.block || '').toUpperCase() === String(block || '').toUpperCase());
}

function findCellById(id) {
    return allCells.find(cell => Number(cell.id) === Number(id)) || null;
}

function openCellEditModal(cell) {
    if (!cell) return;
    if (isHoldingCell(cell)) {
        _showToast('Holding Cell cannot be edited.', 'error');
        return;
    }

    _cellEdit = { ...cell };

    setEl('cellEditModalTitle', 'Edit Cell');
    setEl('cellEditModalSubtitle', `${cell.cell_id} · ${cell.type || '—'} · Block ${cell.block || '—'}`);

    const typeInput = document.getElementById('cellEditType');
    const capacityInput = document.getElementById('cellEditCapacity');
    const statusInput = document.getElementById('cellEditStatus');

    if (typeInput) typeInput.value = cell.type || 'Standard';
    if (capacityInput) {
        capacityInput.value = cell.capacity || '';
        capacityInput.min = Number(cell.occupancy || 0) || 1;
    }
    if (statusInput) statusInput.value = cell.status || 'available';

    clearCellErrors();
    setCellWarning(false);

    document.getElementById('cellEditOverlay')?.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeCellEditModal() {
    document.getElementById('cellEditOverlay')?.classList.remove('open');
    if (!document.getElementById('cellDrawer')?.classList.contains('open') &&
        !document.getElementById('blockEditOverlay')?.classList.contains('open')) {
        document.body.style.overflow = '';
    }
    _cellEdit = null;
}

function handleCellOverlayClick(event) {
    if (event.target === document.getElementById('cellEditOverlay')) closeCellEditModal();
}

document.getElementById('cellEditCapacity')?.addEventListener('input', function () {
    if (!_cellEdit) return;
    const value = parseInt(this.value, 10);
    setCellWarning(!Number.isNaN(value) && value < Number(_cellEdit.occupancy || 0));
});

function setCellWarning(show) {
    const warn = document.getElementById('cellEditWarning');
    const text = document.getElementById('cellEditWarningText');
    if (!warn) return;

    if (show && _cellEdit) {
        text.textContent = `Capacity cannot be lower than current occupancy (${_cellEdit.occupancy}).`;
        warn.classList.add('visible');
    } else {
        warn.classList.remove('visible');
    }
}

function clearCellErrors() {
    ['cellFieldType', 'cellFieldCapacity', 'cellFieldStatus'].forEach(id => {
        document.getElementById(id)?.classList.remove('has-error');
    });
}

function showCellError(fieldId, errorId, message) {
    document.getElementById(fieldId)?.classList.add('has-error');
    if (message) {
        const errorEl = document.getElementById(errorId);
        if (errorEl) errorEl.textContent = message;
    }
}

function openBlockEditModal(block) {
    const cells = getBlockCells(block);
    if (!cells.length) return;
    if (cells.some(isHoldingCell)) {
        _showToast('Holding Cell cannot be edited.', 'error');
        return;
    }

    const summary = getBlockSummary(cells);
    const maxOccupancy = Math.max(...cells.map(cell => Number(cell.occupancy || 0)), 0);

    _blockEdit = { block, cells, maxOccupancy };

    setEl('blockEditModalTitle', `Manage Block ${block}`);
    setEl('blockEditModalSubtitle', `${cells.length} cells · ${summary.occupancy}/${summary.capacity} occupied`);

    const typeInput = document.getElementById('blockEditType');
    const capInput = document.getElementById('blockEditCapacity');
    const addInput = document.getElementById('blockEditAddCount');
    const meta = document.getElementById('blockEditMeta');

    if (typeInput) typeInput.value = cells[0].type || 'Standard';
    if (capInput) {
        capInput.value = cells[0].capacity || '';
        capInput.min = maxOccupancy || 1;
    }
    if (addInput) addInput.value = 0;

    if (meta) {
        meta.innerHTML = `
            <div class="cell-block-modal-chip">Highest occupancy: ${maxOccupancy}</div>
            <div class="cell-block-modal-chip">Next cell: ${escHtml(block)}-${Math.max(...cells.map(cell => Number(cell.block_number || 0)), 0) + 1}</div>
        `;
    }

    clearBlockErrors();
    setBlockWarning(false);

    document.getElementById('blockEditOverlay')?.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeBlockEditModal() {
    document.getElementById('blockEditOverlay')?.classList.remove('open');
    if (!document.getElementById('cellDrawer')?.classList.contains('open') &&
        !document.getElementById('blockDeleteOverlay')?.classList.contains('open') &&
        !document.getElementById('cellEditOverlay')?.classList.contains('open')) {
        document.body.style.overflow = '';
    }
    _blockEdit = null;
}

function handleBlockOverlayClick(event) {
    if (event.target === document.getElementById('blockEditOverlay')) closeBlockEditModal();
}

function openBlockDeleteModal() {
    if (!_blockEdit) return;

    setEl('blockDeleteModalTitle', `Delete Block ${_blockEdit.block}`);
    setEl('blockDeleteModalSubtitle', `${_blockEdit.cells.length} cells will be removed if they are empty.`);
    setEl('blockDeleteMessage', `Are you sure you want to delete Block ${_blockEdit.block}? This cannot be undone.`);

    document.getElementById('blockDeleteOverlay')?.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeBlockDeleteModal() {
    document.getElementById('blockDeleteOverlay')?.classList.remove('open');
    if (!document.getElementById('cellDrawer')?.classList.contains('open') &&
        !document.getElementById('blockEditOverlay')?.classList.contains('open') &&
        !document.getElementById('cellEditOverlay')?.classList.contains('open')) {
        document.body.style.overflow = '';
    }
}

function handleBlockDeleteOverlayClick(event) {
    if (event.target === document.getElementById('blockDeleteOverlay')) closeBlockDeleteModal();
}

document.getElementById('blockEditCapacity')?.addEventListener('input', function () {
    if (!_blockEdit) return;
    const value = parseInt(this.value, 10);
    setBlockWarning(!Number.isNaN(value) && value < _blockEdit.maxOccupancy);
});

function setBlockWarning(show) {
    const warn = document.getElementById('blockEditWarning');
    const text = document.getElementById('blockEditWarningText');
    if (!warn) return;

    if (show && _blockEdit) {
        text.textContent = `Capacity cannot be lower than the most occupied cell in this block (${_blockEdit.maxOccupancy}).`;
        warn.classList.add('visible');
    } else {
        warn.classList.remove('visible');
    }
}

function clearBlockErrors() {
    ['blockFieldType', 'blockFieldCapacity', 'blockFieldAddCount'].forEach(id => {
        document.getElementById(id)?.classList.remove('has-error');
    });
}

function showBlockError(fieldId, errorId, message) {
    document.getElementById(fieldId)?.classList.add('has-error');
    if (message) {
        const errorEl = document.getElementById(errorId);
        if (errorEl) errorEl.textContent = message;
    }
}

async function submitCellEdit() {
    if (!_cellEdit) return;

    clearCellErrors();
    const editingCellId = _cellEdit.id;
    const editingCellCode = _cellEdit.cell_id;

    const type = document.getElementById('cellEditType')?.value || '';
    const capacity = parseInt(document.getElementById('cellEditCapacity')?.value, 10);
    const status = document.getElementById('cellEditStatus')?.value || '';
    const minCapacity = Number(_cellEdit.occupancy || 0);

    let valid = true;
    if (!['Luxury', 'Standard', 'Dormitory', 'Solitary'].includes(type)) {
        showCellError('cellFieldType', 'cellErrorType');
        valid = false;
    }
    if (Number.isNaN(capacity) || capacity < 1 || capacity > 50) {
        showCellError('cellFieldCapacity', 'cellErrorCapacity');
        valid = false;
    } else if (capacity < minCapacity) {
        setCellWarning(true);
        showCellError('cellFieldCapacity', 'cellErrorCapacity', `Must be at least ${minCapacity}.`);
        valid = false;
    }
    if (!['available', 'full', 'maintenance', 'condemned'].includes(status)) {
        showCellError('cellFieldStatus', 'cellErrorStatus');
        valid = false;
    }
    if (!valid) return;

    const saveBtn = document.getElementById('cellEditSaveBtn');
    saveBtn?.classList.add('loading');

    try {
        const res = await fetch(`/admin/cells/${encodeURIComponent(_cellEdit.id)}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ type, capacity, status }),
        });

        const body = await res.json().catch(() => ({}));

        if (res.status === 422) {
            const errors = body.errors || {};
            if (errors.type?.[0]) showCellError('cellFieldType', 'cellErrorType', errors.type[0]);
            if (errors.capacity?.[0]) showCellError('cellFieldCapacity', 'cellErrorCapacity', errors.capacity[0]);
            if (errors.status?.[0]) showCellError('cellFieldStatus', 'cellErrorStatus', errors.status[0]);
            return;
        }

        if (!res.ok) {
            _showToast(body.message || 'Could not save cell changes.', 'error');
            return;
        }

        closeCellEditModal();
        await loadCellGrid();

        const freshCell = findCellById(editingCellId);
        if (freshCell) openCellDrawer(freshCell);

        _showToast(body.message || `Cell ${editingCellCode} updated successfully.`, 'success');
    } catch (err) {
        console.error('Cell update failed:', err);
        _showToast('Could not save cell changes. Please try again.', 'error');
    } finally {
        saveBtn?.classList.remove('loading');
    }
}

async function submitBlockEdit() {
    if (!_blockEdit) return;

    clearBlockErrors();
    const blockName = _blockEdit.block;

    const type = document.getElementById('blockEditType')?.value || '';
    const capacity = parseInt(document.getElementById('blockEditCapacity')?.value, 10);
    const addCount = parseInt(document.getElementById('blockEditAddCount')?.value, 10) || 0;

    let valid = true;
    if (!['Luxury', 'Standard', 'Dormitory', 'Solitary'].includes(type)) {
        showBlockError('blockFieldType', 'blockErrorType');
        valid = false;
    }
    if (Number.isNaN(capacity) || capacity < 1 || capacity > 50) {
        showBlockError('blockFieldCapacity', 'blockErrorCapacity');
        valid = false;
    } else if (capacity < _blockEdit.maxOccupancy) {
        setBlockWarning(true);
        showBlockError('blockFieldCapacity', 'blockErrorCapacity', `Must be at least ${_blockEdit.maxOccupancy}.`);
        valid = false;
    }
    if (addCount < 0 || addCount > 50) {
        showBlockError('blockFieldAddCount', 'blockErrorCapacity');
        valid = false;
    }
    if (!valid) return;

    const saveBtn = document.getElementById('blockEditSaveBtn');
    saveBtn?.classList.add('loading');

    try {
        const res = await fetch(`/admin/blocks/${encodeURIComponent(_blockEdit.block)}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ type, capacity, add_count: addCount }),
        });

        const body = await res.json().catch(() => ({}));

        if (res.status === 422) {
            const errors = body.errors || {};
            if (errors.type?.[0]) showBlockError('blockFieldType', 'blockErrorType', errors.type[0]);
            if (errors.capacity?.[0]) showBlockError('blockFieldCapacity', 'blockErrorCapacity', errors.capacity[0]);
            return;
        }

        if (!res.ok) {
            _showToast(body.message || 'Could not save block changes.', 'error');
            return;
        }

        closeBlockEditModal();
        closeCellDrawer();
        await loadCellGrid();
        _showToast(body.message || `Block ${blockName} updated successfully.`, 'success');
    } catch (err) {
        console.error('Block update failed:', err);
        _showToast('Could not save block changes. Please try again.', 'error');
    } finally {
        saveBtn?.classList.remove('loading');
    }
}

async function deleteBlock() {
    if (!_blockEdit) return;
    openBlockDeleteModal();
}

async function confirmDeleteBlock() {
    if (!_blockEdit) return;
    const blockName = _blockEdit.block;
    const confirmBtn = document.getElementById('blockDeleteConfirmBtn');
    confirmBtn?.classList.add('loading');

    try {
        const res = await fetch(`/admin/blocks/${encodeURIComponent(_blockEdit.block)}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const body = await res.json().catch(() => ({}));
        if (!res.ok) {
            _showToast(body.message || 'Could not delete block.', 'error');
            return;
        }

        closeBlockDeleteModal();
        closeBlockEditModal();
        closeCellDrawer();
        await loadCellGrid();
        _showToast(body.message || `Block ${blockName} deleted successfully.`, 'success');
    } catch (err) {
        console.error('Block delete failed:', err);
        _showToast('Could not delete block. Please try again.', 'error');
    } finally {
        confirmBtn?.classList.remove('loading');
    }
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function _showToast(message, type = 'success') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
    }

    const existing = document.getElementById('_cellBlockToast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = '_cellBlockToast';
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
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(8px)';
        setTimeout(() => toast.remove(), 250);
    }, 3200);
}

function editCellFromDrawer() {
    if (!_drawerCell) return;
    if (isHoldingCell(_drawerCell)) {
        _showToast('Holding Cell cannot be edited.', 'error');
        return;
    }
    openCellEditModal(_drawerCell);
}

window.closeCellDrawer = closeCellDrawer;
window.loadCellGrid = loadCellGrid;
window.editCellFromDrawer = editCellFromDrawer;
window.closeCellEditModal = closeCellEditModal;
window.handleCellOverlayClick = handleCellOverlayClick;
window.submitCellEdit = submitCellEdit;
window.closeBlockEditModal = closeBlockEditModal;
window.handleBlockOverlayClick = handleBlockOverlayClick;
window.closeBlockDeleteModal = closeBlockDeleteModal;
window.handleBlockDeleteOverlayClick = handleBlockDeleteOverlayClick;
window.submitBlockEdit = submitBlockEdit;
window.deleteBlock = deleteBlock;
window.confirmDeleteBlock = confirmDeleteBlock;

document.addEventListener('pageChanged', event => {
    if (event.detail?.page === 'cells') loadCellGrid();
});

if (document.getElementById('page-cells')?.classList.contains('active')) {
    loadCellGrid();
}
