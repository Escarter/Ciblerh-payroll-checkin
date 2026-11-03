/* Wraps the text of icon+text action buttons in a label span so CSS can hide it on mobile. */
(function () {
    if (window.__rfActionButtons) return;
    window.__rfActionButtons = true;

    const SKIP = '.modal, .btn-group, [role="tab"], .dropdown-menu, .pagination, [data-no-collapse], .emp-tabbar, .auth-card, form[action*="login"]';

    function enhance(root) {
        (root || document).querySelectorAll('a.btn, button.btn').forEach((btn) => {
            if (btn.classList.contains('rf-has-label') || btn.closest(SKIP)) return;
            if (btn.classList.contains('btn-close') || btn.type === 'submit' && btn.closest('form') && !btn.closest('.d-flex')) return;
            if (!btn.querySelector(':scope > svg, :scope > .icon')) return;
            const textNodes = Array.from(btn.childNodes).filter((n) => n.nodeType === 3 && n.textContent.trim());
            const textEl = Array.from(btn.children).find((c) => c.tagName === 'SPAN' && !c.querySelector('svg') && c.textContent.trim() && !c.classList.contains('spinner-border'));
            if (!textNodes.length && !textEl) return;

            const label = (textEl ? textEl.textContent : textNodes.map((n) => n.textContent).join(' ')).trim().replace(/\s+/g, ' ');
            if (!btn.getAttribute('title')) btn.setAttribute('title', label);
            if (!btn.getAttribute('aria-label')) btn.setAttribute('aria-label', label);
            textNodes.forEach((n) => {
                const span = document.createElement('span');
                span.className = 'rf-btn-label';
                span.textContent = n.textContent.trim();
                btn.replaceChild(span, n);
            });
            if (textEl) textEl.classList.add('rf-btn-label');
            btn.classList.add('rf-has-label');
        });
    }

    // Bare icon links in table rows and card footers become round icon buttons.
    function enhanceIconLinks(root) {
        (root || document).querySelectorAll('td a, td button, .item-card-footer a, .item-card-footer button').forEach((el) => {
            if (el.classList.contains('btn') || el.classList.contains('rf-icon-btn') || el.closest('.modal, .dropdown-menu, .pagination')) return;
            if (el.matches('[data-no-collapse], .btn-close') || el.querySelector('input, img, .badge')) return;
            const svg = el.querySelector(':scope > svg');
            if (!svg || el.children.length !== 1 || el.textContent.trim()) return;
            const m = (svg.getAttribute('class') || '').match(/text-(primary|success|warning|danger|info)/);
            el.classList.add('rf-icon-btn');
            if (m) el.classList.add('rf-icon-btn-' + (m[1] === 'info' ? 'primary' : m[1]));
            svg.classList.remove(...Array.from(svg.classList).filter((c) => /^text-/.test(c)));
        });
    }

    const RULES = [
        ['danger', /delet|trash|remov|supprim|forcedelete|destroy|cancel_?job|reject|rejet|corbeille/i],
        ['success', /restor|retry|enable|activ|approv|valid|creat|add|new|ajout|nouveau|save|enregistr|assign|confirm|import|upload/i],
        ['warning', /cancel|annul|pause|suspend|disable|d[ée]sactiv|refresh|actualis|reset|clear|r[ée]init/i],
        ['secondary', /\bview|detail|voir|consult|show|open|preview|aper[cç]u/i],
        ['primary', /edit|modif|download|export|t[ée]l[ée]charg|print|imprim|send|envoy/i],
    ];

    function classify(el) {
        const hay = [el.textContent, el.getAttribute('title'), el.getAttribute('aria-label'), el.getAttribute('wire:click'), el.getAttribute('data-bs-target'), el.id].join(' ');
        const order = ['danger', 'secondary', 'success', 'warning', 'primary'];
        for (const k of order) { const r = RULES.find((x) => x[0] === k); if (r[1].test(hay)) return k; }
        return 'neutral';
    }

    // Plain/neutral buttons get a colour matching their action; solid coloured ones are left alone.
    function colourise(root) {
        (root || document).querySelectorAll('.btn, .rf-icon-btn').forEach((el) => {
            if (el.dataset.rfAct || el.closest('.modal, .dropdown-menu, .pagination, .emp-tabbar, [role="tablist"], .nav-tabs')) return;
            if (el.matches('.btn-close, [data-no-collapse]')) return;
            const solid = /\bbtn-(primary|success|danger|warning|info|tertiary)\b/.test(el.className);
            if (el.classList.contains('rf-icon-btn') && /rf-icon-btn-(primary|success|warning|danger)/.test(el.className)) {
                el.dataset.rfAct = '1';
                if (classify(el) === 'secondary') el.className = el.className.replace(/rf-icon-btn-\w+/, 'rf-icon-btn-secondary');
                return;
            }
            const plain = /\bbtn-(outline-secondary|outline-gray|gray-\d+|light|secondary|white|link)\b/.test(el.className) || el.classList.contains('rf-icon-btn');
            if (solid && !plain) { el.dataset.rfAct = '1'; return; }
            if (!plain) return;
            const kind = classify(el);
            el.dataset.rfAct = '1';
            if (kind === 'neutral') return;
            el.classList.add('rf-act', 'rf-act-' + kind);
        });
    }

    const run = () => { enhance(document); enhanceIconLinks(document); colourise(document); };
    document.addEventListener('DOMContentLoaded', run);
    document.addEventListener('livewire:navigated', run);
    document.addEventListener('livewire:init', () => {
        window.Livewire.hook('commit', ({ succeed }) => succeed(() => setTimeout(run, 0)));
    });
    if (document.readyState !== 'loading') run();
})();
