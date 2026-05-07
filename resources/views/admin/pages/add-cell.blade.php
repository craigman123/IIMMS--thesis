{{-- resources/views/admin/pages/add-cell.blade.php --}}

<div class="page" id="page-add-cell">
    <div class="page-header">
        <h1>Add <span class="gold">Cell Block</span></h1>
        <p>Configure and add a new block of cells to the facility.</p>
    </div>

    {{-- ── Toast ── --}}
    <div id="ac-toast" class="ac-toast" style="display:none;"></div>

    <div class="ac-grid">

        {{-- ── Step 1: Count ── --}}
        <div class="ac-card">
            <div class="ac-card-label">
                <span class="ac-step">01</span>
                How many cells in this block?
            </div>
            <div class="ac-count-row">
                <button type="button" class="ac-count-btn" id="dec-btn">−</button>
                <input type="number" id="count-input" value="1" min="1" max="50"
                       class="ac-count-input" readonly>
                <button type="button" class="ac-count-btn" id="inc-btn">+</button>
            </div>
            <div class="ac-count-preview" id="count-preview"></div>
        </div>

        {{-- ── Step 2: Type ── --}}
        <div class="ac-card">
            <div class="ac-card-label">
                <span class="ac-step">02</span>
                Cell Type
            </div>
            <div class="ac-type-grid">
                {{-- data-value is read directly by JS — no hidden radio needed --}}
                <div class="ac-type-card" data-value="Luxury">
                    <span class="ac-type-icon">✦</span>
                    <span class="ac-type-name">Luxury</span>
                    <span class="ac-type-desc">Premium accommodation</span>
                </div>
                <div class="ac-type-card" data-value="Standard">
                    <span class="ac-type-icon">▣</span>
                    <span class="ac-type-name">Standard</span>
                    <span class="ac-type-desc">Regular holding cell</span>
                </div>
                <div class="ac-type-card" data-value="Dormitory">
                    <span class="ac-type-icon">⊞</span>
                    <span class="ac-type-name">Dormitory</span>
                    <span class="ac-type-desc">Shared open dormitory</span>
                </div>
                <div class="ac-type-card" data-value="Solitary">
                    <span class="ac-type-icon">◈</span>
                    <span class="ac-type-name">Solitary</span>
                    <span class="ac-type-desc">Isolated confinement</span>
                </div>
            </div>
        </div>

        {{-- ── Step 3: Capacity ── --}}
        <div class="ac-card">
            <div class="ac-card-label">
                <span class="ac-step">03</span>
                Capacity per Cell
            </div>
            <div class="ac-cap-grid">
                {{-- data-value is read directly by JS --}}
                <div class="ac-cap-btn" data-value="1">
                    <span class="ac-cap-num">1</span>
                    <span class="ac-cap-label">person</span>
                </div>
                <div class="ac-cap-btn" data-value="2">
                    <span class="ac-cap-num">2</span>
                    <span class="ac-cap-label">persons</span>
                </div>
                <div class="ac-cap-btn" data-value="4">
                    <span class="ac-cap-num">4</span>
                    <span class="ac-cap-label">persons</span>
                </div>
                <div class="ac-cap-btn" data-value="8">
                    <span class="ac-cap-num">8</span>
                    <span class="ac-cap-label">persons</span>
                </div>
                <div class="ac-cap-btn" id="custom-cap-label" data-value="custom">
                    <span class="ac-cap-num">?</span>
                    <span class="ac-cap-label">custom</span>
                </div>
            </div>
            <div class="ac-custom-input-wrap" id="custom-input-wrap" style="display:none;">
                <input type="number" id="custom-cap-input"
                       placeholder="Enter number (max 50)" min="1" max="50"
                       class="ac-custom-input">
            </div>
        </div>

        {{-- ── Step 4: Preview ── --}}
        <div class="ac-card ac-preview-card">
            <div class="ac-card-label">
                <span class="ac-step">04</span>
                Preview
            </div>
            <div class="ac-preview" id="preview-box">
                <p class="ac-preview-empty">Configure the options above to see a preview.</p>
            </div>
        </div>

    </div>

    <div class="ac-actions">
        <button type="button" class="ac-btn-cancel" onclick="ShowPage('cells')">Cancel</button>
        <button type="button" class="ac-btn-submit" id="submit-btn" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                 stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Cells
        </button>
    </div>
</div>