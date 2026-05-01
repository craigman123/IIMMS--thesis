{{-- resources/views/admin/pages/overview.blade.php --}}

<div class="page active" id="page-overview">
    <div class="page-header">
        <h1>Good <span id="greeting-time">Morning</span>, <span class="gold">{{ explode(' ', Auth::user()->name)[0] }}</span></h1>
        <p>Here's what's happening at the facility today.</p>
    </div>

    {{-- Stat Cards --}}
    <div class="stats-grid">
        <div class="stat-card" data-color="gold">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="stat-total">—</span>
                <span class="stat-label">Total Inmates</span>
            </div>
            <div class="stat-trend up" id="trend-total">—</div>
        </div>

        <div class="stat-card" data-color="green">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 12l2 2 4-4"/>
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="stat-active">—</span>
                <span class="stat-label">Currently Housed</span>
            </div>
            <div class="stat-trend" id="trend-active">—</div>
        </div>

        <div class="stat-card" data-color="red">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="stat-incidents">—</span>
                <span class="stat-label">Active Incidents</span>
            </div>
            <div class="stat-trend" id="trend-incidents">—</div>
        </div>

        <div class="stat-card" data-color="blue">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M9 3v18M15 3v18M3 9h18M3 15h18"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="stat-cells">—</span>
                <span class="stat-label">Occupied Cells</span>
            </div>
            <div class="stat-trend" id="trend-cells">—</div>
        </div>
    </div>

    {{-- Two column layout --}}
    <div class="overview-grid">
        {{-- Recent Activity --}}
        <div class="panel-card">
            <div class="panel-card-header">
                <h3>Recent Activity</h3>
                <span class="panel-card-meta">Last 24 hours</span>
            </div>
            <div class="activity-list" id="activityList">
                <div class="activity-item skeleton"></div>
                <div class="activity-item skeleton"></div>
                <div class="activity-item skeleton"></div>
                <div class="activity-item skeleton"></div>
            </div>
        </div>

        {{-- Upcoming Releases --}}
        <div class="panel-card">
            <div class="panel-card-header">
                <h3>Upcoming Releases</h3>
                <span class="panel-card-meta">Next 7 days</span>
            </div>
            <div class="release-list" id="releaseList">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4l3 3"/>
                    </svg>
                    <p>No releases scheduled</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Facility Capacity Bar --}}
    <div class="panel-card capacity-card">
        <div class="panel-card-header">
            <h3>Facility Capacity</h3>
            <span class="capacity-pct" id="capacityPct">—%</span>
        </div>
        <div class="capacity-bar-track">
            <div class="capacity-bar-fill" id="capacityFill" style="width: 0%"></div>
        </div>
        <div class="capacity-labels">
            <span>0</span>
            <span id="capacityMax">Max capacity: —</span>
        </div>
    </div>
</div>

<script>
    const inmateCount   = {{ count($inmates_json) }};
    const active_inmates = {{ $active_inmates }};
</script>

