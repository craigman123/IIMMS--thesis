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
                <rect class="single-cube" x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span class="nav-label">Overview</span>
        </a>

        <a href="#" class="nav-item" data-page="ai-assistant">
             <svg class="nav-svg" xmlns="http://www.w3.org/2000/svg" width="20" height="20"  
                fill="currentColor" viewBox="0 0 24 24" >
            <path d="M19.62 12s.08-.1.11-.14c1.34-1.84 1.67-3.57.93-4.86-.78-1.35-2.58-1.87-4.85-1.6C14.91 3.3 13.56 2 12 2S9.09 3.3 8.19 5.4c-2.27-.27-4.07.25-4.85 1.6-.74 1.29-.41 3.01.93 4.86.04.05.08.1.11.14-.04.05-.08.1-.11.14-1.34 1.84-1.67 3.57-.93 4.86.65 1.12 2 1.68 3.74 1.68.35 0 .73-.03 1.11-.08.9 2.1 2.25 3.4 3.81 3.4s2.91-1.3 3.81-3.4c.38.05.76.08 1.11.08 1.74 0 3.09-.55 3.74-1.68.74-1.29.41-3.01-.93-4.86-.04-.05-.08-.1-.11-.14m-2.66-4.67c1.02 0 1.73.25 1.97.67.29.5.03 1.44-.67 2.47-.44-.43-.92-.85-1.44-1.25-.09-.65-.21-1.28-.35-1.86.17-.01.34-.02.5-.02ZM13.5 14.6c-.51.3-1.01.55-1.5.78-.49-.23-.99-.48-1.5-.78-.5-.29-.97-.59-1.43-.91C9.02 13.15 9 12.59 9 12s.03-1.15.07-1.69c.45-.32.93-.62 1.43-.91.51-.3 1.01-.55 1.5-.77.49.23.99.48 1.5.77.5.29.97.59 1.43.91.05.54.07 1.1.07 1.69s-.03 1.15-.07 1.69c-.45.32-.93.62-1.43.91M12 4c.56 0 1.23.65 1.79 1.81-.58.17-1.18.38-1.79.63-.61-.25-1.21-.46-1.79-.63C10.78 4.65 11.45 4 12 4M5.07 8c.24-.42.94-.67 1.97-.67.16 0 .33.01.5.02-.15.59-.27 1.21-.35 1.86-.52.4-1 .82-1.44 1.25-.7-1.03-.96-1.97-.67-2.47Zm0 8c-.29-.5-.03-1.44.67-2.47.44.43.92.85 1.44 1.25.09.65.21 1.28.35 1.86-1.29.09-2.19-.16-2.47-.65ZM12 20c-.56 0-1.23-.65-1.79-1.82.58-.17 1.18-.38 1.79-.63.61.25 1.21.46 1.79.63C13.22 19.34 12.55 20 12 20m6.93-4c-.28.48-1.18.74-2.47.65.15-.59.27-1.21.35-1.86.52-.4 1-.82 1.44-1.25.7 1.03.96 1.97.67 2.47Z"></path><path d="M12 10.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 1 0 0-3"></path>
            </svg>
            <span class="nav-label">Atom AI Assistant</span>
        </a>

        <div class="nav-section-label">Inmate Management</div>

        <a href="#" class="nav-item" data-page="add-inmate">
            <svg class="nav-svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24" >
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
                <line class="stLine" x1="12" y1="9" x2="12" y2="13"/>
                <line class="ndLine" x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <span class="nav-label">Incidents</span>
            <span class="nav-badge alert" id="badge-incidents">—</span>
        </a>

        <a href="#" class="nav-item nav-item-3d" data-page="schedules">
            <svg  xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24" >
            <path d="M8 13h8v2H8z"></path><path d="M19 4h-2V2h-2v2H9V2H7v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2M5 20V8h14V6v14z"></path>
            </svg>
            <span class="nav-label">Schedules</span>
        </a>

        <div class="nav-section-label">Cell Management</div>
        
        <a href="#" class="nav-item" data-page="add-cell">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path d="M4.5 11h5c.83 0 1.5-.67 1.5-1.5v-5c0-.83-.67-1.5-1.5-1.5h-5C3.67 3 3 3.67 3 4.5v5c0 .83.67 1.5 1.5 1.5M5 5h4v4H5zm14.5-2h-5c-.83 0-1.5.67-1.5 1.5v5c0 .83.67 1.5 1.5 1.5h5c.83 0 1.5-.67 1.5-1.5v-5c0-.83-.67-1.5-1.5-1.5M19 9h-4V5h4zM4.5 21h5c.83 0 1.5-.67 1.5-1.5v-5c0-.83-.67-1.5-1.5-1.5h-5c-.83 0-1.5.67-1.5 1.5v5c0 .83.67 1.5 1.5 1.5m.5-6h4v4H5z"></path>
                <path class="plus-icon" d="M18 13h-2v3h-3v2h3v3h2v-3h3v-2h-3z" transform-origin="19px 17px"></path>
            </svg>
            <span class="nav-label">Add Cells</span>
        </a>

        <a href="#" class="nav-item" data-page="cells">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M9 3v18M15 3v18M3 9h18M3 15h18"/>
            </svg>
            <span class="nav-label">Cell Assignment</span>
        </a>

        <div class="nav-section-label">Staff Management</div>

            <a href="#" class="nav-item" data-page="staff-list">
                <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24"  
                fill="currentColor" viewBox="0 0 24 24" >
                <path d="M19 2H5c-.55 0-1 .45-1 1v4H2v2h2v2H2v2h2v2H2v2h2v4c0 .55.45 1 1 1h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2m0 18H6V4h13z"></path><path d="M12.5 7a2.5 2.5 0 1 0 0 5 2.5 2.5 0 1 0 0-5m4.5 9c0-1.66-1.34-3-3-3h-3c-1.66 0-3 1.34-3 3v1h9z"></path>
                </svg>
                <span class="nav-label">Staff List</span>
            </a>
            <a href="#" class="nav-item" data-page="staff-assignment">
                <svg  xmlns="http://www.w3.org/2000/svg" width="20" height="20"  
                fill="currentColor" viewBox="0 0 24 24" >
                <path d="M12 2a2 2 0 1 0 0 4 2 2 0 1 0 0-4m-2 16h4v-5h2V9c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v4h2z"></path><path d="M16 14.3v2.03c2.63.47 4 1.3 4 1.66 0 .51-2.75 2-8 2s-8-1.49-8-2c0-.36 1.37-1.2 4-1.66V14.3c-3.31.52-6 1.72-6 3.7 0 2.75 5.18 4 10 4s10-1.25 10-4c0-1.98-2.69-3.18-6-3.7"></path>
                </svg>
                <span class="nav-label">Staff Assignment</span>
            </a>

        <div class="nav-section-label">System</div>

        <a href="#" class="nav-item" data-page="users">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span class="nav-label">Users Accounts</span>
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
        <a href="#" class="nav-item" data-page="analytics">
            <svg  xmlns="http://www.w3.org/2000/svg" width="20" height="20"  
            fill="currentColor" viewBox="0 0 24 24" >
            <path d="M12 2C6.49 2 2 6.49 2 12s4.49 10 10 10 10-4.49 10-10S17.51 2 12 2m7.93 9H13V4.07c3.61.45 6.48 3.32 6.93 6.93M4 12c0-4.07 3.06-7.44 7-7.93V12c0 .21.06.41.19.58l4.61 6.46c-1.13.61-2.42.96-3.8.96-4.41 0-8-3.59-8-8m13.42 5.87L13.94 13h5.99a7.98 7.98 0 0 1-2.51 4.87"></path>
            </svg>
            <span class="nav-label">Analytics</span>
        </a>

        <div class="nav-section-label">Personal</div>

        <a href="#" class="nav-item" data-page="profile">
            <svg  xmlns="http://www.w3.org/2000/svg" width="20" height="20"  
            fill="currentColor" viewBox="0 0 24 24" >
            <path d="M13 9h5v2h-5zm1 4h4v2h-4z"></path><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2M4 18V6h16v12z"></path><path d="M9 8a2 2 0 1 0 0 4 2 2 0 1 0 0-4m0 5c-1.66 0-3 1.34-3 3h6c0-1.66-1.34-3-3-3"></path>
            </svg>
            <span class="nav-label">Profile Information</span>
        </a>

        <div class="nav-section-label">Contacts</div>

        <a href="#" class="nav-item" data-page="contacts">
            <svg  xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24" >
            <path d="M18.07 22h.35c.47-.02.9-.26 1.17-.64l2.14-3.09c.23-.33.32-.74.24-1.14s-.31-.74-.64-.97l-4.64-3.09a1.47 1.47 0 0 0-.83-.25c-.41 0-.81.16-1.1.48l-1.47 1.59c-.69-.43-1.61-1.07-2.36-1.82-.72-.72-1.37-1.64-1.82-2.36l1.59-1.47c.54-.5.64-1.32.23-1.93L7.84 2.67c-.22-.33-.57-.57-.97-.64a1.46 1.46 0 0 0-1.13.24L2.65 4.41c-.39.27-.62.7-.64 1.17-.03.69-.16 6.9 4.68 11.74 4.35 4.35 9.81 4.69 11.38 4.69ZM6.88 10.05c-.16.15-.21.39-.11.59.05.09 1.15 2.24 2.74 3.84 1.6 1.6 3.75 2.7 3.84 2.75.2.1.44.06.59-.11l1.99-2.15 3.86 2.57-1.7 2.46c-1.16 0-6.13-.24-9.99-4.1S4 7.06 4 5.91l2.46-1.7 2.57 3.86-2.15 1.99Z"></path>
            </svg>
            <span class="nav-label">Emergency Contacts</span>
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