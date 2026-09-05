(function () {
    'use strict';

    const config = window.AI_ASSISTANT_CONFIG || {};
    const drawerEl = document.getElementById('aiDrawer');
    const closeBtn = document.getElementById('aiCloseBtn');
    const messagesEl = document.getElementById('aiMessages');
    const formEl = document.getElementById('aiForm');
    const inputEl = document.getElementById('aiInput');
    const sendBtn = document.getElementById('aiSendBtn');
    const modelDropdown = document.getElementById('aiModelDropdown');
    const modelTrigger = document.getElementById('aiModelTrigger');
    const modelTriggerLabel = document.getElementById('aiModelTriggerLabel');
    const modelMenu = document.getElementById('aiModelMenu');
    const sidebarEl = document.getElementById('sidebar');

    // The sidebar nav item for the assistant. We fully intercept its click
    // (see the capture-phase listener below) instead of letting it behave
    // like a normal page-nav item — it should only toggle this drawer,
    // never mark itself "active" or swap out the page underneath.
    const navTrigger = document.querySelector('.nav-item[data-page="ai-assistant"]');

    let modelsLoaded = false;

    // Keeps --ai-sidebar-width in sync with the sidebar's real rendered
    // width, so the drawer never overlaps it — including when the
    // sidebar is collapsed/expanded via its own toggle button.
    function syncSidebarWidth() {
        if (!sidebarEl) return;
        const width = sidebarEl.getBoundingClientRect().width;
        document.documentElement.style.setProperty('--ai-sidebar-width', width + 'px');
    }

    syncSidebarWidth();
    window.addEventListener('resize', syncSidebarWidth);
    if (sidebarEl && window.ResizeObserver) {
        new ResizeObserver(syncSidebarWidth).observe(sidebarEl);
    }

    function openDrawer() {
        if (!drawerEl) return;
        syncSidebarWidth();
        drawerEl.classList.add('is-open');
        drawerEl.setAttribute('aria-hidden', 'false');
        if (navTrigger) navTrigger.classList.add('ai-nav-open');
        if (!modelsLoaded) {
            loadModels();
            modelsLoaded = true;
        }
        // Let the slide-in transition start before focusing, avoids jank.
        window.setTimeout(() => inputEl && inputEl.focus(), 150);
    }

    function closeDrawer() {
        if (!drawerEl) return;
        drawerEl.classList.remove('is-open');
        drawerEl.setAttribute('aria-hidden', 'true');
        if (navTrigger) navTrigger.classList.remove('ai-nav-open');
    }

    function toggleDrawer() {
        if (!drawerEl) return;
        if (drawerEl.classList.contains('is-open')) {
            closeDrawer();
        } else {
            openDrawer();
        }
    }

    // Registered on `document` in the CAPTURE phase so it runs before the
    // event ever reaches the nav item itself — this is what lets us
    // reliably block dashboard.js's own click handler on that same
    // element regardless of which script tag loads first. Without this,
    // dashboard.js still marks the item "active", hides whatever page
    // you were on, and tries to show a #page-ai-assistant that no
    // longer exists (since this is a drawer now, not a swapped page),
    // leaving the content area blank.
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.nav-item[data-page="ai-assistant"]');
        if (!trigger) return;
        e.preventDefault();
        e.stopPropagation();
        toggleDrawer();
    }, true);

    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

    // Escape still closes it, but there's no backdrop to click through —
    // this stays open while the user clicks around and navigates pages.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawerEl && drawerEl.classList.contains('is-open')) {
            closeDrawer();
        }
    });

    // Keeps the last ~10 turns so the model has conversational context
    // without the request growing unbounded.
    const history = [];
    const MAX_HISTORY_MESSAGES = 20;

    function authHeaders() {
        // Session-cookie auth, same as the rest of the admin dashboard —
        // just needs the CSRF token, no separate API token.
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': config.csrfToken || '',
        };
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function appendMessage(role, text, fileInfo, table) {
        const wrapper = document.createElement('div');
        wrapper.className = `ai-msg ai-msg--${role}`;

        const bubble = document.createElement('div');
        bubble.className = 'ai-msg__bubble';
        bubble.textContent = text;

        if (fileInfo && fileInfo.url) {
            const link = document.createElement('a');
            link.href = fileInfo.url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.className = 'ai-msg__file';
            link.textContent = `Open ${fileInfo.filename || 'file'}`;
            bubble.appendChild(document.createElement('br'));
            bubble.appendChild(link);
        }

        if (table && Array.isArray(table.columns) && Array.isArray(table.rows)) {
            bubble.appendChild(buildTableElement(table));
        }

        wrapper.appendChild(bubble);

        messagesEl.appendChild(wrapper);
        scrollToBottom();
        return wrapper;
    }

    // Builds a scrollable table (sticky header, vertical scroll after a
    // handful of rows) for query_data responses that include row-level
    // data, e.g. the full inmate list behind a "how many inmates" answer.
    function buildTableElement(table) {
        const outer = document.createElement('div');
        outer.className = 'ai-table-wrapper';

        if (table.title) {
            const heading = document.createElement('div');
            heading.className = 'ai-table-title';
            heading.textContent = table.title;
            outer.appendChild(heading);
        }

        const scrollBox = document.createElement('div');
        scrollBox.className = 'ai-table-scroll';

        const el = document.createElement('table');
        el.className = 'ai-table';

        const thead = document.createElement('thead');
        const headRow = document.createElement('tr');
        table.columns.forEach((col) => {
            const th = document.createElement('th');
            th.textContent = col;
            headRow.appendChild(th);
        });
        thead.appendChild(headRow);
        el.appendChild(thead);

        const tbody = document.createElement('tbody');
        table.rows.forEach((row) => {
            const tr = document.createElement('tr');
            table.columns.forEach((col) => {
                const td = document.createElement('td');
                const value = row[col];
                td.textContent = (value === null || value === undefined || value === '') ? '—' : value;
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        el.appendChild(tbody);

        scrollBox.appendChild(el);
        outer.appendChild(scrollBox);
        return outer;
    }

    let typingTimerInterval = null;
    let typingStartTime = null;

    function showTypingIndicator() {
        const wrapper = document.createElement('div');
        wrapper.className = 'ai-msg ai-msg--assistant';
        wrapper.id = 'aiTypingIndicator';

        const bubble = document.createElement('div');
        bubble.className = 'ai-msg__bubble ai-typing';
        bubble.innerHTML = '<span></span><span></span><span></span>';

        const timer = document.createElement('span');
        timer.id = 'aiTypingTimer';
        timer.className = 'ai-typing-timer';
        timer.style.marginLeft = '8px';
        timer.style.fontSize = '0.75em';
        timer.style.opacity = '0.65';
        timer.textContent = '0s';
        bubble.appendChild(timer);

        wrapper.appendChild(bubble);
        messagesEl.appendChild(wrapper);
        scrollToBottom();

        typingStartTime = Date.now();
        updateTypingTimer();
        typingTimerInterval = window.setInterval(updateTypingTimer, 1000);
    }

    function updateTypingTimer() {
        const timer = document.getElementById('aiTypingTimer');
        if (!timer || !typingStartTime) return;
        const elapsedSeconds = Math.floor((Date.now() - typingStartTime) / 1000);
        timer.textContent = `${elapsedSeconds}s`;
    }

    function removeTypingIndicator() {
        if (typingTimerInterval) {
            window.clearInterval(typingTimerInterval);
            typingTimerInterval = null;
        }
        typingStartTime = null;

        const el = document.getElementById('aiTypingIndicator');
        if (el) el.remove();
    }

    function pushHistory(role, content) {
        history.push({ role, content });
        while (history.length > MAX_HISTORY_MESSAGES) {
            history.shift();
        }
    }

    async function sendMessage(message) {
        appendMessage('user', message);
        pushHistory('user', message);
        showTypingIndicator();
        sendBtn.disabled = true;

        try {
            const response = await fetch(config.chatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: authHeaders(),
                body: JSON.stringify({ message, history }),
            });

            const data = await response.json().catch(() => ({}));
            removeTypingIndicator();

            if (!response.ok) {
                appendMessage('error', data.reply || 'Something went wrong. Please try again.');
                return;
            }

            appendMessage('assistant', data.reply || "Sorry, I didn't get a valid response.", data.file, data.table);
            pushHistory('assistant', data.reply || '');
        } catch (err) {
            removeTypingIndicator();
            appendMessage('error', 'Could not reach the AI assistant. Check your connection and try again.');
        } finally {
            sendBtn.disabled = false;
        }
    }

    // Turns a raw Ollama model tag like "llama3.1:8b" or "gemma2:9b" into
    // a plain, user-facing label like "Llama 8B" / "Gemma 9B". Falls back
    // to a lightly-cleaned version of the raw name for anything it
    // doesn't recognize, so an unfamiliar model never shows as blank.
    const MODEL_FAMILY_LABELS = {
        llama: 'Llama',
        gemma: 'Gemma',
        mistral: 'Mistral',
        mixtral: 'Mixtral',
        phi: 'Phi',
        qwen: 'Qwen',
        deepseek: 'DeepSeek',
        codellama: 'CodeLlama',
        vicuna: 'Vicuna',
        orca: 'Orca',
        starcoder: 'StarCoder',
        'llava': 'LLaVA',
    };

    function titleCase(text) {
        return text
            .split(/[\s_-]+/)
            .filter(Boolean)
            .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }

    function friendlyModelName(raw) {
        if (!raw) return raw;

        const [namePart, tagPart] = raw.split(':');
        const familyMatch = namePart.match(/^[a-zA-Z]+/);
        const familyKey = familyMatch ? familyMatch[0].toLowerCase() : namePart.toLowerCase();
        const familyLabel = MODEL_FAMILY_LABELS[familyKey] || titleCase(namePart.replace(/[\d.]+$/, '') || namePart);

        if (!tagPart) return familyLabel;

        const sizeMatch = tagPart.match(/^(\d+(?:\.\d+)?)\s*b\b/i);
        const sizeLabel = sizeMatch ? `${sizeMatch[1]}B` : titleCase(tagPart);

        return `${familyLabel} ${sizeLabel}`.trim();
    }

    let currentModels = [];
    let activeModel = '';

    function closeModelMenu() {
        if (!modelMenu) return;
        modelMenu.hidden = true;
        if (modelTrigger) modelTrigger.setAttribute('aria-expanded', 'false');
    }

    function openModelMenu() {
        if (!modelMenu) return;
        modelMenu.hidden = false;
        if (modelTrigger) modelTrigger.setAttribute('aria-expanded', 'true');
    }

    function renderModelMenu() {
        if (!modelMenu) return;
        modelMenu.innerHTML = '';

        if (currentModels.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'ai-model-menu__empty';
            empty.textContent = 'No models found';
            modelMenu.appendChild(empty);
            return;
        }

        currentModels.forEach((name) => {
            const item = document.createElement('li');
            item.className = 'ai-model-option';
            item.setAttribute('role', 'option');
            item.setAttribute('tabindex', '0');
            item.dataset.value = name;
            item.setAttribute('aria-selected', name === activeModel ? 'true' : 'false');

            const label = document.createElement('span');
            label.textContent = friendlyModelName(name);
            label.title = name; // raw tag still available on hover for anyone who wants it

            const check = document.createElement('span');
            check.className = 'ai-model-option__check';
            check.setAttribute('aria-hidden', 'true');
            check.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>';

            item.appendChild(label);
            item.appendChild(check);

            item.addEventListener('click', function () {
                selectModel(name);
            });
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectModel(name);
                }
            });

            modelMenu.appendChild(item);
        });
    }

    function setActiveModelLabel() {
        if (!modelTriggerLabel) return;
        modelTriggerLabel.textContent = activeModel ? friendlyModelName(activeModel) : 'No models found';
        modelTriggerLabel.title = activeModel || '';
    }

    function selectModel(name) {
        if (name === activeModel) {
            closeModelMenu();
            return;
        }
        activeModel = name;
        setActiveModelLabel();
        renderModelMenu();
        closeModelMenu();
        switchModel(name);
    }

    if (modelTrigger) {
        modelTrigger.addEventListener('click', function () {
            if (modelMenu && modelMenu.hidden) {
                openModelMenu();
            } else {
                closeModelMenu();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (modelDropdown && !modelDropdown.contains(e.target)) {
            closeModelMenu();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modelMenu && !modelMenu.hidden) {
            closeModelMenu();
        }
    });

    async function loadModels() {
        if (modelTriggerLabel) modelTriggerLabel.textContent = 'Loading models…';

        try {
            const response = await fetch(config.modelsUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: authHeaders(),
            });

            const data = await response.json().catch(() => ({ models: [], active: '' }));

            currentModels = data.models || [];
            activeModel = data.active || (currentModels[0] || '');

            setActiveModelLabel();
            renderModelMenu();
        } catch (err) {
            currentModels = [];
            activeModel = '';
            if (modelTriggerLabel) modelTriggerLabel.textContent = 'Could not load models';
            renderModelMenu();
        }
    }

    async function switchModel(model) {
        if (!model) return;
        try {
            await fetch(config.setModelUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: authHeaders(),
                body: JSON.stringify({ model }),
            });
        } catch (err) {
            // Silently ignore — model list will just reflect the old
            // active model on next load, no need to interrupt the user.
        }
    }

    formEl.addEventListener('submit', function (e) {
        e.preventDefault();
        const message = inputEl.value.trim();
        if (!message) return;
        inputEl.value = '';
        sendMessage(message);
    });
})();