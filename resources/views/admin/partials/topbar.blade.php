{{-- resources/views/admin/partials/topbar.blade.php --}}

<header class="topbar">
    <div class="topbar-left">
        <button class="mobile-menu-btn" id="mobileMenu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <div class="breadcrumb">
            <span class="breadcrumb-root">Dashboard</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
            <span class="breadcrumb-current" id="breadcrumbCurrent">Overview</span>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-time" id="topbarTime"></div>
        <div class="topbar-date" id="topbarDate"></div>
        <div class="alert-bell" id="alertBell" title="Alerts">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="bell-dot"></span>
        </div>
    </div>
</header>
