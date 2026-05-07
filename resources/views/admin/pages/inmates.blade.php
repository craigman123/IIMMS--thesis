{{-- resources/views/admin/pages/inmates.blade.php --}}

<div class="page" id="page-inmates">
    <div class="page-header">
        <h1>Inmate <span class="gold">Registry</span></h1>
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
                    <tr class="skeleton-row"><td colspan="7"><div class="skeleton"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="7"><div class="skeleton"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="7"><div class="skeleton"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="7"><div class="skeleton"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="7"><div class="skeleton"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>