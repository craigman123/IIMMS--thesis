// public/js/admin/pages/add-cell.js

(function () {
    'use strict';

    // ── State ────────────────────────────────────────────────────
    const state = {
        count:    1,
        type:     null,
        capacity: null,
        block:    null,   // fetched from server on init
    };

    // ── Elements ─────────────────────────────────────────────────
    const countInput      = document.getElementById('count-input');
    const decBtn          = document.getElementById('dec-btn');
    const incBtn          = document.getElementById('inc-btn');
    const countPreview    = document.getElementById('count-preview');
    const typeCards       = document.querySelectorAll('.ac-type-card');
    const capBtns         = document.querySelectorAll('.ac-cap-btn');
    const customCapLabel  = document.getElementById('custom-cap-label');
    const customInputWrap = document.getElementById('custom-input-wrap');
    const customCapInput  = document.getElementById('custom-cap-input');
    const previewBox      = document.getElementById('preview-box');
    const submitBtn       = document.getElementById('submit-btn');
    const toast           = document.getElementById('ac-toast');

    // ── Fetch next block letter from server ──────────────────────
    fetch('/admin/cells/next-block')
        .then(r => r.json())
        .then(data => {
            state.block = data.block;
            refresh();
        })
        .catch(() => {
            state.block = 'A';
            refresh();
        });

    // ── Count controls ───────────────────────────────────────────
    decBtn.addEventListener('click', () => {
        if (state.count > 1) {
            state.count--;
            countInput.value = state.count;
            refresh();
        }
    });

    incBtn.addEventListener('click', () => {
        if (state.count < 50) {
            state.count++;
            countInput.value = state.count;
            refresh();
        }
    });

    countInput.addEventListener('change', () => {
        let v = parseInt(countInput.value);
        if (isNaN(v) || v < 1) v = 1;
        if (v > 50) v = 50;
        state.count = v;
        countInput.value = v;
        refresh();
    });

    // ── Type selection ───────────────────────────────────────────
    typeCards.forEach(card => {
        card.addEventListener('click', () => {
            typeCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            state.type = card.dataset.value;   // read from data-value, no hidden radio needed
            refresh();
        });
    });

    // ── Capacity selection ───────────────────────────────────────
    capBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            capBtns.forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');

            if (btn.id === 'custom-cap-label') {
                customInputWrap.style.display = 'block';
                customCapInput.focus();
                state.capacity = parseInt(customCapInput.value) || null;
            } else {
                customInputWrap.style.display = 'none';
                state.capacity = parseInt(btn.dataset.value);   // read from data-value
            }
            refresh();
        });
    });

    customCapInput.addEventListener('input', () => {
        let v = parseInt(customCapInput.value);
        if (!isNaN(v) && v > 0) {
            state.capacity = Math.min(v, 50);
        } else {
            state.capacity = null;
        }
        refresh();
    });

    // ── Submit ───────────────────────────────────────────────────
    submitBtn.addEventListener('click', () => {
        if (!state.type || !state.capacity || !state.count) return;

        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                 stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                <circle cx="12" cy="12" r="9"/>
            </svg>
            Saving…`;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch('/admin/cells', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                count:    state.count,
                type:     state.type,
                capacity: state.capacity,
            }),
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (ok) {
                showToast(`✓ Block ${data.block} — ${state.count} cell${state.count > 1 ? 's' : ''} added.`, 'success');
                // Reset form
                setTimeout(() => {
                    state.count    = 1;
                    state.type     = null;
                    state.capacity = null;
                    countInput.value = 1;
                    typeCards.forEach(c => c.classList.remove('selected'));
                    capBtns.forEach(b => b.classList.remove('selected'));
                    customInputWrap.style.display = 'none';
                    customCapInput.value = '';
                    // Re-fetch next block letter
                    fetch('/admin/cells/next-block')
                        .then(r => r.json())
                        .then(d => { state.block = d.block; refresh(); });
                    refresh();
                }, 800);
            } else {
                const msg = data.message || Object.values(data.errors || {})[0]?.[0] || 'Something went wrong.';
                showToast('✗ ' + msg, 'error');
                refresh(); // re-enable button
            }
        })
        .catch(() => {
            showToast('✗ Network error. Please try again.', 'error');
            refresh();
        });
    });

    // ── Toast ────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
        toast.textContent = msg;
        toast.style.display = 'block';
        toast.style.borderLeftColor = type === 'success' ? 'var(--green)' : 'var(--red)';
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 3500);
    }

    // ── Refresh UI ───────────────────────────────────────────────
    function refresh() {
        renderChips();
        renderPreview();
        updateSubmit();
    }

    function renderChips() {
        countPreview.innerHTML = '';
        if (!state.block) return;
        const show = Math.min(state.count, 20);
        for (let i = 1; i <= show; i++) {
            const chip = document.createElement('span');
            chip.className = 'ac-chip';
            chip.textContent = `${state.block}-${i}`;
            countPreview.appendChild(chip);
        }
        if (state.count > 20) {
            const chip = document.createElement('span');
            chip.className = 'ac-chip';
            chip.textContent = `+${state.count - 20} more`;
            countPreview.appendChild(chip);
        }
    }

    function renderPreview() {
        if (!state.type || !state.capacity || !state.block) {
            previewBox.innerHTML = '<p class="ac-preview-empty">Configure the options above to see a preview.</p>';
            return;
        }

        const grid = document.createElement('div');
        grid.className = 'ac-preview-grid';

        const showCount = Math.min(state.count, 12);
        for (let i = 1; i <= showCount; i++) {
            const cell = document.createElement('div');
            cell.className = 'ac-preview-cell';
            cell.innerHTML = `
                <div class="ac-preview-cell-id">${state.block}-${i}</div>
                <div class="ac-preview-cell-meta">${state.type} · cap. ${state.capacity}</div>
                <div class="ac-preview-status">Available · 0/${state.capacity}</div>
            `;
            grid.appendChild(cell);
        }

        if (state.count > 12) {
            const more = document.createElement('div');
            more.className = 'ac-preview-cell';
            more.style.justifyContent = 'center';
            more.style.alignItems = 'center';
            more.innerHTML = `
                <div class="ac-preview-cell-id">+${state.count - 12}</div>
                <div class="ac-preview-cell-meta">more cells</div>`;
            grid.appendChild(more);
        }

        previewBox.innerHTML = '';
        previewBox.appendChild(grid);
    }

    function updateSubmit() {
        const ready = state.count >= 1 && state.type && state.capacity && state.block;
        submitBtn.disabled = !ready;
        submitBtn.innerHTML = ready
            ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                   <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
               </svg>
               Add ${state.count} Cell${state.count > 1 ? 's' : ''} (Block ${state.block})`
            : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                   <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
               </svg>
               Add Cells`;
    }

    // ── Init ─────────────────────────────────────────────────────
    refresh();

})();