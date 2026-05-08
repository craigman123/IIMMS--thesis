{{-- resources/views/admin/pages/inmates.blade.php --}}

<div id="inmate-drawer-overlay" class="inmate-drawer-overlay" onclick="InmateCloseDrawer()"></div>
<div id="inmate-drawer" class="inmate-drawer">
    <div class="inmate-drawer-header">
        <div class="inmate-drawer-title-row">
            <h3 id="inmate-drawer-title">Inmate Details</h3>
            <button type="button" class="inmate-drawer-close" onclick="InmateCloseDrawer()" title="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    <div id="drawer-view-panel" class="inmate-drawer-body">
        <div class="inmate-drawer-mugshot-row">
            <div class="inmate-drawer-mugshot">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"
                     width="48" height="48" style="opacity:.3" id="drawer-mugshot-placeholder">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <img id="drawer-mugshot-img" src="" alt="Mugshot" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:8px;">
            </div>
            <div class="inmate-drawer-id-block">
                <span class="inmate-drawer-id" id="drawer-id-label"></span>
                <span class="inmate-drawer-name" id="drawer-name-label"></span>
                <span id="drawer-status-badge" class="status-badge"></span>
            </div>
        </div>

        <div class="inmate-drawer-section-label">Facility Info</div>
        <div class="inmate-drawer-fields">
            <div class="inmate-drawer-field"><span class="idf-label">Cell</span><span class="idf-value" id="dv-cell"></span></div>
            <div class="inmate-drawer-field"><span class="idf-label">Security</span><span class="idf-value" id="dv-security"></span></div>
            <div class="inmate-drawer-field"><span class="idf-label">Detention Type</span><span class="idf-value" id="dv-detention"></span></div>
            <div class="inmate-drawer-field"><span class="idf-label">Admission Date</span><span class="idf-value" id="dv-admitted"></span></div>
            <div class="inmate-drawer-field"><span class="idf-label">Release Date</span><span class="idf-value" id="dv-release"></span></div>
            <div class="inmate-drawer-field"><span class="idf-label">Commitment Order</span><span class="idf-value" id="dv-commitment"></span></div>
            <div class="inmate-drawer-field"><span class="idf-label">Court / Branch</span><span class="idf-value" id="dv-court"></span></div>
        </div>
    </div>
</div>

<div id="inmate-edit-overlay" class="inmate-edit-overlay" onclick="InmateEditOverlayClick(event)">
    <div class="inmate-edit-modal" role="dialog" aria-modal="true" aria-labelledby="inmateEditTitle">
        <div class="inmate-edit-modal-header">
            <div>
                <h2 id="inmateEditTitle">Edit Inmate</h2>
                <p id="inmateEditSubtitle">Update inmate, personal, and case information.</p>
            </div>
            <button type="button" class="inmate-edit-close" onclick="InmateCloseEditModal()" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form id="inmate-edit-form" class="inmate-edit-modal-body" onsubmit="InmateEditSubmit(event)">
            <input type="hidden" id="edit-inmate-id">

            <section class="inmate-edit-section">
                <div class="inmate-edit-section-title">Inmate Info</div>

                {{-- Mugshot uploader --}}
                <div class="inmate-mugshot-upload-row">
                    <div class="inmate-mugshot-preview" id="edit-mugshot-preview">
                        <svg id="edit-mugshot-placeholder" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="36" height="36" style="opacity:.3">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <img id="edit-mugshot-img" src="" alt="Mugshot" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:10px;">
                    </div>
                    <div class="inmate-mugshot-upload-info">
                        <label class="inmate-mugshot-upload-btn" for="edit-mugshot-file">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            Upload Photo
                        </label>
                        <input type="file" id="edit-mugshot-file" accept="image/jpeg,image/png,image/webp" style="display:none">
                        <button type="button" id="edit-mugshot-clear" class="inmate-mugshot-clear-btn" style="display:none" onclick="InmateClearMugshot()">Remove</button>
                        <span class="inmate-mugshot-hint">JPG, PNG or WEBP · max 5 MB</span>
                    </div>
                </div>

                <div class="inmate-edit-grid three">
                    <div class="inmate-edit-group"><label>Last Name</label><input type="text" id="edit-last-name"></div>
                    <div class="inmate-edit-group"><label>First Name</label><input type="text" id="edit-first-name"></div>
                    <div class="inmate-edit-group"><label>Middle Name</label><input type="text" id="edit-middle-name"></div>
                </div>
                <div class="inmate-edit-grid four">
                    <div class="inmate-edit-group"><label>Status</label><select id="edit-status"></select></div>
                    <div class="inmate-edit-group"><label>Security Level</label><select id="edit-security"></select></div>
                    <div class="inmate-edit-group"><label>Detention Type</label><select id="edit-detention"></select></div>
                    <div class="inmate-edit-group"><label>Assigned Cell</label><select id="edit-cell"></select></div>
                </div>
                <div class="inmate-edit-grid three">
                    <div class="inmate-edit-group"><label>Admission Date</label><input type="date" id="edit-admission-date"></div>
                    <div class="inmate-edit-group"><label>Release Date <span style="font-weight:400;opacity:.6;font-size:10px;letter-spacing:0;text-transform:none">(auto-computed)</span></label><input type="date" id="edit-release-date" readonly title="Calculated from admission date + total sentence"></div>
                    <div class="inmate-edit-group"><label>Commitment Order</label><input type="text" id="edit-commitment-order"></div>
                </div>
                <div class="inmate-edit-grid one">
                    <div class="inmate-edit-group"><label>Court / Branch</label><input type="text" id="edit-court-branch"></div>
                </div>
            </section>

            <section class="inmate-edit-section">
                <div class="inmate-edit-section-title">Personal Info</div>
                <div class="inmate-edit-grid four">
                    <div class="inmate-edit-group"><label>Date of Birth</label><input type="date" id="edit-dob"></div>
                    <div class="inmate-edit-group"><label>Age</label><input type="number" id="edit-age" min="1" max="120"></div>
                    <div class="inmate-edit-group"><label>Sex</label><select id="edit-sex"><option value="male">Male</option><option value="female">Female</option></select></div>
                    <div class="inmate-edit-group"><label>Civil Status</label><input type="text" id="edit-civil-status"></div>
                </div>
                <div class="inmate-edit-grid three">
                    <div class="inmate-edit-group"><label>Nationality</label><input type="text" id="edit-nationality"></div>
                    <div class="inmate-edit-group"><label>Religion</label><input type="text" id="edit-religion"></div>
                    <div class="inmate-edit-group"><label>Phone</label><input type="text" id="edit-phone"></div>
                </div>
                <div class="inmate-edit-grid two">
                    <div class="inmate-edit-group"><label>Email</label><input type="email" id="edit-email"></div>
                    <div class="inmate-edit-group"><label>Home Address</label><input type="text" id="edit-home-address"></div>
                </div>
                <div class="inmate-edit-grid three">
                    <div class="inmate-edit-group"><label>SSS Number</label><input type="text" id="edit-sss-number"></div>
                    <div class="inmate-edit-group"><label>PhilHealth Number</label><input type="text" id="edit-philhealth-number"></div>
                    <div class="inmate-edit-group"><label>Pag-IBIG Number</label><input type="text" id="edit-pagibig-number"></div>
                </div>
                <div class="inmate-edit-grid three">
                    <div class="inmate-edit-group"><label>Emergency Contact</label><input type="text" id="edit-ec-name"></div>
                    <div class="inmate-edit-group"><label>Relationship</label><input type="text" id="edit-ec-relation"></div>
                    <div class="inmate-edit-group"><label>Emergency Phone</label><input type="text" id="edit-ec-phone"></div>
                </div>
            </section>

            <section class="inmate-edit-section">
                <div class="inmate-edit-section-head">
                    <div class="inmate-edit-section-title">Crimes</div>
                    <button type="button" class="btn-outline-muted" onclick="InmateAddCrime()">Add Crime</button>
                </div>
                <div id="inmate-edit-crimes"></div>
                <p id="inmate-edit-no-crimes" class="inmate-edit-empty">No crimes added yet.</p>
            </section>

            <section class="inmate-edit-section">
                <div class="inmate-edit-section-title">Incidents</div>
                <p class="inmate-edit-note">Incident assignment is not wired yet because this project currently has no incident relationship/model for inmates.</p>
            </section>

            <div class="inmate-edit-modal-footer">
                <button type="button" class="btn-outline-muted" onclick="InmateCloseEditModal()">Cancel</button>
                <button type="submit" class="btn-gold" id="inmate-edit-save-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<div class="page" id="page-inmates">
    <div class="page-header">
        <h1>Inmates <span class="gold">List</span></h1>
        <p>Manage and monitor all inmates in the facility.</p>
    </div>
    <div class="panel-card">
        <div class="panel-card-header">
            <div class="table-controls">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" placeholder="Search inmates..." id="inmateSearch">
                </div>
            </div>
        </div>
        <div class="table-wrapper">
            <table class="data-table" id="inmateTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Cell</th>
                        <th>Status</th>
                        <th>Security Level</th>
                        <th>Admission Date</th>
                        <th>Release Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inmateTableBody">
                    <tr class="skeleton-row"><td colspan="8"><div class="skeleton"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="8"><div class="skeleton"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="8"><div class="skeleton"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="8"><div class="skeleton"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="8"><div class="skeleton"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>