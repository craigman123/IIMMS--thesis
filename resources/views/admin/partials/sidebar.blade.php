{{-- resources/views/admin/partials/sidebar.blade.php --}}

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-seal">
            <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="28" fill="none" stroke="#c9a84c" stroke-width="1.5"/>
                <circle cx="30" cy="30" r="22" fill="#1a3557"/>
                <path d="M30 10 L40 16 L40 29 Q40 38 30 43 Q20 38 20 29 L20 16 Z" fill="#c9a84c"/>
                <path d="M30 13 L38 18 L38 29 Q38 36 30 40 Q22 36 22 29 L22 18 Z" fill="#1a3557"/>
                <rect x="26" y="27" width="8" height="6" rx="1" fill="#c9a84c"/>
                <path d="M27.5 27 L27.5 25 Q27.5 22 30 22 Q32.5 22 32.5 25 L32.5 27" fill="none" stroke="#c9a84c" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="sidebar-brand">
            <span class="brand-name">SIIMMS</span>
            <span class="brand-sub">Admin Panel</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle" title="Collapse">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>

        <a href="#" class="nav-item active" data-page="overview">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span class="nav-label">Overview</span>
        </a>

        <div class="nav-section-label">Inmate Management</div>

        <a href="#" class="nav-item" data-page="add-inmate">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" >
            <path d="M22 11h-3V8h-2v3h-3v2h3v3h2v-3h3zM4 8c0 2.28 1.72 4 4 4s4-1.72 4-4-1.72-4-4-4-4 1.72-4 4m6 0c0 1.18-.82 2-2 2s-2-.82-2-2 .82-2 2-2 2 .82 2 2M3 20h10c.55 0 1-.45 1-1v-1c0-2.76-2.24-5-5-5H7c-2.76 0-5 2.24-5 5v1c0 .55.45 1 1 1m4-5h2c1.65 0 3 1.35 3 3H4c0-1.65 1.35-3 3-3"></path>
            </svg>
            <span class="nav-label">Add Inmates</span>
        </a>

        <a href="#" class="nav-item" data-page="inmates">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span class="nav-label">All Inmates</span>
            <span class="nav-badge" id="badge-inmates">—</span>
        </a>

        <a href="#" class="nav-item" data-page="incidents">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <span class="nav-label">Incidents</span>
            <span class="nav-badge alert" id="badge-incidents">—</span>
        </a>

        <a href="#" class="nav-item" data-page="releases">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span class="nav-label">Releases</span>
        </a>

        <div class="nav-section-label">Cell Management</div>

        <a href="#" class="nav-item" data-page="cells">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M9 3v18M15 3v18M3 9h18M3 15h18"/>
            </svg>
            <span class="nav-label">Cell Assignment</span>
        </a>

        <div class="nav-section-label">System</div>

        <a href="#" class="nav-item" data-page="users">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span class="nav-label">Staff Accounts</span>
        </a>

        <a href="#" class="nav-item" data-page="logs">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            <span class="nav-label">Audit Logs</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
            <div class="user-info">
                <span class="user-name">{{ Auth::user()->name }}</span>
                <span class="user-role">{{ ucfirst(Auth::user()->role) }}</span>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn" title="Sign Out">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </button>
        </form>
    </div>
</aside>

<script>
    const inmateCount    = {{ count($inmates_json) }};
    const active_inmates = {{ $active_inmates }};
    const inmates        = @json($inmates_json);
</script>
