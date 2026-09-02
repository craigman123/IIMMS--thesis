{{-- resources/views/admin/pages/incidents.blade.php --}}

<div class="page" id="page-incidents">
    <div class="page-header">
        <h1>Incident <span class="gold">Reports</span></h1>
        <p>Track and manage facility incidents.</p>
    </div>
    <div class="panel-card">
        <div class="panel-card-header">
            <h3>All Incidents</h3>
            <button class="btn-gold" id="addIncidentBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Report Incident
            </button>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ref #</th>
                        <th>Type</th>
                        <th>Inmate</th>
                        <th>Location</th>
                        <th>Severity</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="incidentTableBody">
                    <tr><td colspan="7" class="empty-cell">No incidents recorded.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>