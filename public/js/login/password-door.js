(function () {
    const LEVELS = ['weak', 'fair', 'good', 'strong', 'unique'];
    const LABELS = { weak: 'Weak', fair: 'Fair', good: 'Good', strong: 'Strong', unique: 'Unique' };
    const COLORS = { weak: '#c0392b', fair: '#d97706', good: '#8a712f', strong: '#00AB13', unique: '#8C00AB' };

    function scoreStrength(pw) {
        let score = 0;
        if (pw.length >= 8) score++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
        if (/\d/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;

        if (score <= 1) return score;          
        if (score <= 3) return 2;             
        return pw.length >= 12 ? 4 : 3;      
    }

    function renderDoors(container) {
        container.innerHTML = `
            <div class="pw-door-scene is-active" data-level="empty">
                <div class="pw-frame"></div>
                <div class="pw-interior">
                    <div class="pw-spark s1"></div>
                    <div class="pw-spark s2"></div>
                    <div class="pw-spark s3"></div>
                    <div class="pw-spark s4"></div>
                    <div class="pw-gold-pyramid">
                        <div class="pw-gold-row">
                            <div class="pw-gold-brick"></div>
                            <div class="pw-gold-brick"></div>
                            <div class="pw-gold-brick"></div>
                            <div class="pw-gold-brick"></div>
                        </div>
                        <div class="pw-gold-row">
                            <div class="pw-gold-brick"></div>
                            <div class="pw-gold-brick"></div>
                            <div class="pw-gold-brick"></div>
                        </div>
                        <div class="pw-gold-row">
                            <div class="pw-gold-brick"></div>
                            <div class="pw-gold-brick"></div>
                        </div>
                        <div class="pw-gold-row">
                            <div class="pw-gold-brick"></div>
                        </div>
                    </div>
                </div>
                <div class="pw-door"></div>
            </div>
            <div class="pw-door-scene" data-level="weak">
                <div class="pw-frame"></div>
                <div class="pw-door"><div class="pw-crack"></div><div class="pw-handle"></div></div>
            </div>
            <div class="pw-door-scene" data-level="fair">
                <div class="pw-frame"></div>
                <div class="pw-door"><div class="pw-handle"></div><div class="pw-bolt-fair"></div></div>
            </div>
            <div class="pw-door-scene" data-level="good">
                <div class="pw-frame"></div>
                <div class="pw-door"><div class="pw-dial"></div></div>
            </div>
            <div class="pw-door-scene" data-level="strong">
                <div class="pw-frame"></div>
                <div class="pw-door"><div class="pw-vault-wheel"></div></div>
                <div class="pw-vault-bolt top"></div>
                <div class="pw-vault-bolt bottom"></div>
                <div class="pw-vault-bolt left"></div>
                <div class="pw-vault-bolt right"></div>
            </div>
            <div class="pw-door-scene" data-level="unique">
                <div class="pw-frame"></div>
                <div class="pw-door"><div class="pw-vault-wheel"></div></div>
                <div class="pw-vault-bolt top"></div>
                <div class="pw-vault-bolt bottom"></div>
                <div class="pw-vault-bolt left"></div>
                <div class="pw-vault-bolt right"></div>
                <div class="pw-laser l1"></div>
                <div class="pw-laser l2"></div>
                <div class="pw-laser l3"></div>
                <div class="pw-laser l4"></div>
                <div class="pw-laser l5"></div>
            </div>
        `;
    }

    function initPasswordDoor(inputId, visualId, captionId, segIds) {
        const input = document.getElementById(inputId);
        const visual = document.getElementById(visualId);
        const caption = document.getElementById(captionId);
        const segs = (segIds || []).map(id => document.getElementById(id)).filter(Boolean);
        if (!input || !visual) return;

        renderDoors(visual);
        const scenes = visual.querySelectorAll('.pw-door-scene');
        const emptyScene = visual.querySelector('[data-level="empty"]');

        function setBars(filledCount, color) {
            segs.forEach((s, i) => {
                s.style.background = i < filledCount ? color : '#e5e7eb';
            });
        }

        function reset() {
            scenes.forEach(s => s.classList.remove('is-active', 'locking'));
            emptyScene.classList.add('is-active');
            if (caption) { caption.textContent = ''; caption.removeAttribute('data-level'); }
            setBars(0, '#e5e7eb');
        }

        input.addEventListener('input', function () {
            const val = input.value;

            if (!val) { reset(); return; }

            const idx = scoreStrength(val);      // 0..3
            const level = LEVELS[idx];
            const color = COLORS[level];

            scenes.forEach(s => s.classList.remove('is-active', 'locking'));
            const scene = visual.querySelector(`.pw-door-scene[data-level="${level}"]`);
            scene.classList.add('is-active');
            requestAnimationFrame(() => scene.classList.add('locking'));

            if (caption) {
                caption.textContent = LABELS[level];
                caption.dataset.level = level;
            }

            setBars(idx + 1, color); // weak=1 bar, fair=2, good=3, strong=4
        });

        reset();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPasswordDoor(
            'reg-password',
            'pw-strength-visual',
            'pw-strength-caption',
            ['seg1', 'seg2', 'seg3', 'seg4', 'seg5']
        );
    });

    window.initPasswordDoor = initPasswordDoor;
})();