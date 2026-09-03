{{--
    This partial is a fixed, always-in-DOM overlay drawer — NOT one of the
    swapped `.page` sections, and NOT a modal. It docks to the right of the
    sidebar and never blocks clicks on the sidebar or the page underneath,
    so the user can keep navigating while it stays open. Include it ONCE in
    the main admin layout (e.g. resources/views/layouts/admin.blade.php),
    right after the sidebar include and before </body>.
--}}
<div class="ai-drawer" id="aiDrawer" aria-hidden="true">
    <div class="ai-panel" id="aiPanel" role="complementary" aria-label="Atom AI Assistant">
        <header class="ai-panel__header">
            <div class="ai-panel__title">
                <span class="ai-panel__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">

                        <path d="M19.62 12s.08-.1.11-.14c1.34-1.84 1.67-3.57.93-4.86-.78-1.35-2.58-1.87-4.85-1.6C14.91 3.3 13.56 2 12 2S9.09 3.3 8.19 5.4c-2.27-.27-4.07.25-4.85 1.6-.74 1.29-.41 3.01.93 4.86.04.05.08.1.11.14-.04.05-.08.1-.11.14-1.34 1.84-1.67 3.57-.93 4.86.65 1.12 2 1.68 3.74 1.68.35 0 .73-.03 1.11-.08.9 2.1 2.25 3.4 3.81 3.4s2.91-1.3 3.81-3.4c.38.05.76.08 1.11.08 1.74 0 3.09-.55 3.74-1.68.74-1.29.41-3.01-.93-4.86-.04-.05-.08-.1-.11-.14m-2.66-4.67c1.02 0 1.73.25 1.97.67.29.5.03 1.44-.67 2.47-.44-.43-.92-.85-1.44-1.25-.09-.65-.21-1.28-.35-1.86.17-.01.34-.02.5-.02ZM13.5 14.6c-.51.3-1.01.55-1.5.78-.49-.23-.99-.48-1.5-.78-.5-.29-.97-.59-1.43-.91C9.02 13.15 9 12.59 9 12s.03-1.15.07-1.69c.45-.32.93-.62 1.43-.91.51-.3 1.01-.55 1.5-.77.49.23.99.48 1.5.77.5.29.97.59 1.43.91.05.54.07 1.1.07 1.69s-.03 1.15-.07 1.69c-.45.32-.93.62-1.43.91M12 4c.56 0 1.23.65 1.79 1.81-.58.17-1.18.38-1.79.63-.61-.25-1.21-.46-1.79-.63C10.78 4.65 11.45 4 12 4M5.07 8c.24-.42.94-.67 1.97-.67.16 0 .33.01.5.02-.15.59-.27 1.21-.35 1.86-.52.4-1 .82-1.44 1.25-.7-1.03-.96-1.97-.67-2.47Zm0 8c-.29-.5-.03-1.44.67-2.47.44.43.92.85 1.44 1.25.09.65.21 1.28.35 1.86-1.29.09-2.19-.16-2.47-.65ZM12 20c-.56 0-1.23-.65-1.79-1.82.58-.17 1.18-.38 1.79-.63.61.25 1.21.46 1.79.63C13.22 19.34 12.55 20 12 20m6.93-4c-.28.48-1.18.74-2.47.65.15-.59.27-1.21.35-1.86.52-.4 1-.82 1.44-1.25.7 1.03.96 1.97.67 2.47Z"></path>
                        <path d="M12 10.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 1 0 0-3"></path>
                    </svg>
                </span>
                <h1>Atom AI Assistant</h1>
            </div>

            <div class="ai-panel__controls">
                <div class="ai-model-dropdown" id="aiModelDropdown">
                    <button
                        type="button"
                        class="ai-model-trigger"
                        id="aiModelTrigger"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                    >
                        <span class="ai-model-trigger__label" id="aiModelTriggerLabel">Loading models…</span>
                        <svg class="ai-model-trigger__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <ul class="ai-model-menu" id="aiModelMenu" role="listbox" aria-label="Active model" hidden></ul>
                </div>

                <button type="button" class="ai-close-btn" id="aiCloseBtn" aria-label="Close AI Assistant">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </header>

        <div class="ai-messages" id="aiMessages" aria-live="polite">
            <div class="ai-msg ai-msg--assistant">
                <div class="ai-msg__bubble">
                    Hi! I'm Atom, your AI assistant. Ask me anything about this page or your data.
                </div>
            </div>
        </div>

        <div class="ai-panel__footer">
            <form id="aiForm" class="ai-input-row" autocomplete="off">
                <label for="aiInput" class="sr-only">Type your message</label>
                <input
                    type="text"
                    id="aiInput"
                    name="message"
                    class="ai-input"
                    placeholder="Type your message…"
                    maxlength="4000"
                    required
                >
                <button type="submit" class="ai-send-btn" id="aiSendBtn" aria-label="Send message">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>