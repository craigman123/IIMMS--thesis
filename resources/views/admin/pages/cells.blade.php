{{-- resources/views/admin/pages/cells.blade.php --}}

<div class="page" id="page-cells">
    <div class="page-header">
        <h1>Cell <span class="gold">Assignment</span></h1>
        <p>Monitor occupancy, status, and block configuration across all detention cells.</p>
    </div>

    {{-- ── Stats ── --}}
    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card" data-color="gold">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h6M3 15h6"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="cell-stat-total">—</span>
                <span class="stat-label">Total Cells</span>
            </div>
        </div>
        <div class="stat-card" data-color="green">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="cell-stat-available">—</span>
                <span class="stat-label">Available</span>
            </div>
        </div>
        <div class="stat-card" data-color="red">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="cell-stat-full">—</span>
                <span class="stat-label">Full</span>
            </div>
        </div>
        <div class="stat-card" data-color="blue">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="cell-stat-maintenance">—</span>
                <span class="stat-label">Maintenance</span>
            </div>
        </div>
    </div>

    {{-- ── Controls ── --}}
    <div class="panel-card" style="margin-bottom: 20px;">
        <div class="panel-card-header">
            <h3>Cell Blocks</h3>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">

                {{-- Filter: Status --}}
                <select id="cell-filter-status" class="cell-select">
                    <option value="">All Statuses</option>
                    <option value="available">Available</option>
                    <option value="full">Full</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="condemned">Condemned</option>
                </select>

                {{-- Filter: Type --}}
                <select id="cell-filter-type" class="cell-select">
                    <option value="">All Types</option>
                    <option value="Luxury">Luxury</option>
                    <option value="Standard">Standard</option>
                    <option value="Dormitory">Dormitory</option>
                    <option value="Solitary">Solitary</option>
                </select>

                {{-- Add Cell --}}
                <button class="btn-gold" onclick="ShowPage('add-cell')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Cell Block
                </button>
            </div>
        </div>

        {{-- ── Cell Grid ── --}}
        <div style="padding: 20px;">
            <div class="cell-grid" id="cellGrid">
                {{-- Populated by JS --}}
            </div>
            <div id="cell-empty" style="display:none; text-align:center; padding:40px; color:var(--muted); font-size:13px;">
                No cells found. Try adjusting the filters or add a new block.
            </div>
        </div>
    </div>
</div>

{{-- ── Cell Detail Drawer Modal ── --}}
<div class="cell-drawer-overlay" id="cellDrawerOverlay" onclick="closeCellDrawer()"></div>

<div class="cell-drawer" id="cellDrawer">

    {{-- Drawer Header --}}
    <div class="cell-drawer-header">
        <div class="cell-drawer-title-group">
            <span class="cell-drawer-id" id="drawerCellId">—</span>
            <span class="cell-drawer-badge" id="drawerStatusBadge">—</span>
        </div>
        <button class="cell-drawer-close" onclick="closeCellDrawer()" title="Close">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    {{-- Meta Info Grid --}}
    <div class="cell-drawer-meta">
        <div class="cell-meta-item">
            <span class="cell-meta-label">Type</span>
            <span class="cell-meta-value" id="drawerType">—</span>
        </div>
        <div class="cell-meta-item">
            <span class="cell-meta-label">Block</span>
            <span class="cell-meta-value" id="drawerBlock">—</span>
        </div>
        <div class="cell-meta-item">
            <span class="cell-meta-label">Capacity</span>
            <span class="cell-meta-value" id="drawerCapacity">—</span>
        </div>
        <div class="cell-meta-item">
            <span class="cell-meta-label">Occupied</span>
            <span class="cell-meta-value" id="drawerOccupied">—</span>
        </div>
    </div>

    {{-- Occupancy Bar --}}
    <div class="cell-drawer-section">
        <div class="cell-drawer-section-label">Occupancy</div>
        <div class="cell-occ-bar-track">
            <div class="cell-occ-bar-fill" id="drawerOccBar" style="width:0%"></div>
        </div>
        <div class="cell-occ-bar-labels">
            <span id="drawerOccPct">0%</span>
            <span id="drawerOccFraction">0 / 0</span>
        </div>
    </div>

    {{-- Inmates List --}}
    <div class="cell-drawer-section" style="flex:1; display:flex; flex-direction:column; min-height:0;">
        <div class="cell-drawer-section-label" style="display:flex; align-items:center; justify-content:space-between;">
            <span>Assigned Inmates</span>
            <span class="cell-inmate-count" id="drawerInmateCount">0</span>
        </div>

        <div class="cell-inmate-list" id="drawerInmateList">
            {{-- Populated by JS --}}
        </div>
    </div>

    {{-- Drawer Actions --}}
    <div class="cell-drawer-actions">
        <button class="btn-gold" style="flex:1;" onclick="editCellFromDrawer()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit Cell
        </button>
        <button class="btn-outline-muted" onclick="closeCellDrawer()">
            Close
        </button>
    </div>
</div>

{{-- Overlay (clicking outside closes) --}}
<div class="cell-edit-overlay" id="cellEditOverlay" onclick="handleEditOverlayClick(event)">

    <div class="cell-edit-modal" id="cellEditModal" role="dialog" aria-modal="true" aria-labelledby="cellEditModalTitle">

        {{-- Header --}}
        <div class="cell-edit-header">
            <div class="cell-edit-title">
                <span class="cell-edit-title-main" id="cellEditModalTitle">Edit Cell</span>
                <span class="cell-edit-title-sub" id="cellEditModalSubtitle">Loading…</span>
            </div>
            <button class="cell-edit-close" onclick="closeCellEditModal()" title="Close" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="cell-edit-body">

            {{-- Capacity-below-occupancy warning --}}
            <div class="cell-edit-warning" id="cellEditCapacityWarn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span id="cellEditCapacityWarnText">Capacity cannot be lower than current occupancy.</span>
            </div>

            {{-- Type --}}
            <div class="cell-edit-field" id="fieldType">
                <label class="cell-edit-label" for="editCellType">Cell Type</label>
                <select class="cell-edit-select" id="editCellType" name="type">
                    <option value="Luxury">Luxury</option>
                    <option value="Standard">Standard</option>
                    <option value="Dormitory">Dormitory</option>
                    <option value="Solitary">Solitary</option>
                </select>
                <span class="cell-edit-error" id="errorType">Please select a valid cell type.</span>
            </div>

            {{-- Capacity --}}
            <div class="cell-edit-field" id="fieldCapacity">
                <label class="cell-edit-label" for="editCellCapacity">Capacity</label>
                <input class="cell-edit-input" type="number" id="editCellCapacity"
                       name="capacity" min="1" max="50" placeholder="e.g. 4">
                <span class="cell-edit-hint">Maximum 50 inmates per cell.</span>
                <span class="cell-edit-error" id="errorCapacity">Enter a number between 1 and 50.</span>
            </div>

            {{-- Status --}}
            <div class="cell-edit-field" id="fieldStatus">
                <label class="cell-edit-label" for="editCellStatus">Status</label>
                <select class="cell-edit-select" id="editCellStatus" name="status">
                    <option value="available">Available</option>
                    <option value="full">Full</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="condemned">Condemned</option>
                </select>
                <span class="cell-edit-error" id="errorStatus">Please select a valid status.</span>
            </div>

        </div>

        {{-- Footer --}}
        <div class="cell-edit-footer">
            <button class="btn-gold" style="flex:1;" id="cellEditSaveBtn" onclick="submitCellEdit()">
                <span class="btn-label" style="display:flex;align-items:center;gap:6px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Save Changes
                </span>
            </button>
            <button class="btn-outline-muted" onclick="closeCellEditModal()">Cancel</button>
        </div>

    </div>
</div>