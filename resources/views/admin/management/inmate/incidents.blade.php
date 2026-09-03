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

{{-- ================= Report Incident: stacked modal flow ================= --}}
<div class="incident-flow" id="incidentFlow" aria-hidden="true">
    <div class="incident-flow-backdrop" id="incidentBackdrop"></div>

    {{-- Modal 1: pick an inmate --}}
    <div class="incident-modal incident-modal-picker" id="inmatePickerModal" role="dialog" aria-modal="true" aria-labelledby="inmatePickerTitle">
        <div class="incident-modal-header">
            <h3 id="inmatePickerTitle">Select inmate</h3>
            <button type="button" class="incident-modal-close" id="closeInmatePicker" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="5" y1="5" x2="19" y2="19"/>
                    <line x1="19" y1="5" x2="5" y2="19"/>
                </svg>
            </button>
        </div>

        <div class="incident-filters">
            <div class="incident-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                    <circle cx="11" cy="11" r="7"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="inmateSearch" placeholder="Search name or ID number">
            </div>
            <div class="incident-filter-row">
                <select id="filterBlock">
                    <option value="">All blocks</option>
                    <option value="A">Block A</option>
                    <option value="B">Block B</option>
                    <option value="C">Block C</option>
                    <option value="D">Block D</option>
                </select>
                <select id="filterStatus">
                    <option value="">All statuses</option>
                    <option value="Active">Active</option>
                    <option value="Isolation">Isolation</option>
                    <option value="Medical">Medical</option>
                    <option value="Release pending">Release pending</option>
                </select>
            </div>
        </div>

        <ul class="inmate-list" id="inmateList"></ul>
        <p class="inmate-list-empty" id="inmateListEmpty" hidden>No inmates match those filters.</p>
    </div>

    {{-- Modal 2: report form, tucked behind modal 1 until an inmate is chosen --}}
    <div class="incident-modal incident-modal-report" id="incidentReportModal" role="dialog" aria-modal="true" aria-labelledby="incidentReportTitle">
        <div class="incident-modal-header">
            <button type="button" class="incident-modal-back" id="backToPicker" aria-label="Back to inmate list">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
            </button>
            <h3 id="incidentReportTitle">Report incident</h3>
            <button type="button" class="incident-modal-close" id="closeIncidentReport" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="5" y1="5" x2="19" y2="19"/>
                    <line x1="19" y1="5" x2="5" y2="19"/>
                </svg>
            </button>
        </div>

        <div class="incident-target" id="incidentTarget">
            <div class="incident-target-avatar" id="incidentTargetAvatar">—</div>
            <div class="incident-target-info">
                <span class="incident-target-name" id="incidentTargetName">No inmate selected</span>
                <span class="incident-target-meta" id="incidentTargetMeta">&nbsp;</span>
            </div>
        </div>

        <form class="incident-form" id="incidentForm">
            <div class="incident-form-row">
                <label for="incidentType">Type</label>
                <select id="incidentType" required>
                    <option value="" disabled selected>Select a type</option>
                    <option>Fight / altercation</option>
                    <option>Contraband</option>
                    <option>Medical emergency</option>
                    <option>Escape attempt</option>
                    <option>Property damage</option>
                    <option>Verbal threat</option>
                    <option>Other</option>
                </select>
            </div>

            <div class="incident-form-row incident-form-split">
                <div>
                    <label for="incidentLocation">Location</label>
                    <input type="text" id="incidentLocation" placeholder="e.g. Cell Block C, Yard 2" required>
                </div>
                <div>
                    <label for="incidentSeverity">Severity</label>
                    <select id="incidentSeverity" required>
                        <option value="" disabled selected>Select</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
            </div>

            <div class="incident-form-row">
                <label for="incidentDateTime">Date &amp; time</label>
                <input type="datetime-local" id="incidentDateTime" required>
            </div>

            <div class="incident-form-row">
                <label for="incidentDescription">Description</label>
                <textarea id="incidentDescription" rows="4" placeholder="What happened, who was involved, and what action was taken" required></textarea>
            </div>

            <div class="incident-form-actions">
                <button type="button" class="btn-ghost" id="cancelIncidentForm">Cancel</button>
                <button type="submit" class="btn-gold">Submit report</button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/admin/incident/incident-modal.css') }}">
<script src="{{ asset('js/admin/incident/incident-modal.js') }}" defer></script>