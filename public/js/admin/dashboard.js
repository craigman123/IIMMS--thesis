// public/js/admin/dashboard.js

document.addEventListener('DOMContentLoaded', () => {

    // ── CLOCK ────────────────────────────────────────────────────────
    const timeEl  = document.getElementById('topbarTime');
    const dateEl  = document.getElementById('topbarDate');
    const greetEl = document.getElementById('greeting-time');

    function updateClock() {
        const now  = new Date();
        const h    = now.getHours();
        const m    = String(now.getMinutes()).padStart(2, '0');
        const s    = String(now.getSeconds()).padStart(2, '0');
        const ampm = h >= 12 ? 'PM' : 'AM';
        const h12  = h % 12 || 12;

        if (timeEl) timeEl.textContent = `${h12}:${m}:${s} ${ampm}`;

        if (dateEl) {
            dateEl.textContent = now.toLocaleDateString('en-PH', {
                weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
            });
        }

        if (greetEl) {
            if      (h < 12) greetEl.textContent = 'Morning';
            else if (h < 17) greetEl.textContent = 'Afternoon';
            else             greetEl.textContent = 'Evening';
        }
    }

    updateClock();
    setInterval(updateClock, 1000);

    // ── SIDEBAR TOGGLE ───────────────────────────────────────────────
    const sidebar       = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileMenu    = document.getElementById('mobileMenu');

    sidebarToggle?.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
    });

    mobileMenu?.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
    });

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 &&
            !sidebar.contains(e.target) &&
            !mobileMenu.contains(e.target)) {
            sidebar.classList.remove('mobile-open');
        }
    });

    // ── PAGE NAVIGATION ──────────────────────────────────────────────
    const navItems   = document.querySelectorAll('.nav-item[data-page]');
    const pages      = document.querySelectorAll('.page');
    const breadcrumb = document.getElementById('breadcrumbCurrent');

    // ── SHOW PAGE (global helper — used by add-inmate back btn, submit, etc.) ──
    window.ShowPage = function (pageId) {
        pages.forEach(p => p.classList.remove('active'));
        navItems.forEach(n => n.classList.remove('active'));

        const targetPage = document.getElementById(`page-${pageId}`);
        if (targetPage) targetPage.classList.add('active');

        const navItem = document.querySelector(`.nav-item[data-page="${pageId}"]`);
        if (navItem) navItem.classList.add('active');

        if (breadcrumb) {
            const labels = {
                'overview':   'Overview',
                'inmates':    'All Inmates',
                'add-inmate': 'Add Inmate',
                'cells':      'Cell Management',
                'incidents':  'Incidents',
                'releases':   'Releases',
                'users':      'Users',
                'logs':       'Logs',
            };
            breadcrumb.textContent = labels[pageId] || pageId;
        }

        if (window.innerWidth <= 768) {
            sidebar.classList.remove('mobile-open');
        }

        targetPage?.scrollIntoView({ block: 'start' });
    };

    // ── "Add Inmate" btn on inmates page → navigate (no modal) ──────
    window.OpenAddInmateModal = function () {
        ShowPage('add-inmate');
    };

    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            ShowPage(item.dataset.page);
        });
    });

    // ── MOCK STATS ───────────────────────────────────────────────────
    function loadStats() {
        const stats = {
            total:     142,
            active:    138,
            incidents: 3,
            cells:     47,
            capacity:  200,
        };

        setTimeout(() => {
            animateCounter('stat-total',     stats.total);
            animateCounter('stat-active',    stats.active);
            animateCounter('stat-incidents', stats.incidents);
            animateCounter('stat-cells',     stats.cells);

            setEl('badge-inmates',   stats.total);
            setEl('badge-incidents', stats.incidents);

            const pct = Math.round((stats.active / stats.capacity) * 100);
            setEl('capacityPct', `${pct}%`);
            setEl('capacityMax', `Max capacity: ${stats.capacity}`);

            const fill = document.getElementById('capacityFill');
            if (fill) {
                fill.style.width = `${pct}%`;
                if      (pct >= 90) fill.classList.add('full');
                else if (pct >= 75) fill.classList.add('warn');
            }
        }, 600);
    }

    function animateCounter(id, target) {
        const el = document.getElementById(id);
        if (!el) return;
        const duration = 900;
        const start    = performance.now();
        function step(now) {
            const progress = Math.min((now - start) / duration, 1);
            const ease     = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(ease * target);
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    function setEl(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    // ── MOCK ACTIVITY FEED ───────────────────────────────────────────
    function loadActivity() {
        const activities = [
            { color: 'green', text: 'Inmate <strong>Juan Dela Cruz</strong> admitted to Cell B-12.',     time: '2m ago'  },
            { color: 'red',   text: 'Incident reported at <strong>Cell Block C</strong> — altercation.', time: '18m ago' },
            { color: 'blue',  text: 'Cell <strong>A-04</strong> assignment updated.',                    time: '45m ago' },
            { color: 'gold',  text: '<strong>Pedro Santos</strong> scheduled for release on May 5.',     time: '1h ago'  },
            { color: 'green', text: 'Staff account created for <strong>Officer Reyes</strong>.',         time: '3h ago'  },
        ];

        const list = document.getElementById('activityList');
        if (!list) return;

        setTimeout(() => {
            list.innerHTML = '';
            activities.forEach(a => {
                const item = document.createElement('div');
                item.className = 'activity-item';
                item.innerHTML = `
                    <div class="activity-dot ${a.color}"></div>
                    <div class="activity-text">${a.text}</div>
                    <div class="activity-time">${a.time}</div>
                `;
                list.appendChild(item);
            });
        }, 800);
    }

    // ── MOCK CELL GRID ───────────────────────────────────────────────
    function loadCellGrid() {
        const grid = document.getElementById('cellGrid');
        if (!grid) return;

        const blocks = ['A', 'B', 'C', 'D'];
        const cells  = [];

        blocks.forEach(block => {
            for (let i = 1; i <= 12; i++) {
                const num    = String(i).padStart(2, '0');
                const rand   = Math.random();
                const status = rand < 0.05 ? 'empty' : rand < 0.15 ? 'full' : 'occupied';
                const occ    = status === 'empty' ? 0 : status === 'full' ? 4 : Math.floor(Math.random() * 3) + 1;
                cells.push({ id: `${block}-${num}`, status, occ, max: 4 });
            }
        });

        grid.innerHTML = '';
        cells.forEach(c => {
            const el = document.createElement('div');
            el.className = `cell-block ${c.status}`;
            el.innerHTML = `
                <span class="cell-number">${c.id}</span>
                <span class="cell-occupancy">${c.occ} / ${c.max}</span>
            `;
            el.title = `Cell ${c.id} — ${c.occ}/${c.max} occupied`;
            grid.appendChild(el);
        });
    }

    // ── INIT ─────────────────────────────────────────────────────────
    loadStats();
    loadActivity();
    loadCellGrid();
    // Note: loadInmateTable() and inmateSearch are handled by inmates.js
});