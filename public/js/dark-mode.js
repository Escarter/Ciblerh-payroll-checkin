(function () {
    var KEY = 'rf-theme';
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function stored() {
        try { return localStorage.getItem(KEY); } catch (e) { return null; }
    }

    function current() {
        var s = stored();
        if (s === 'dark' || s === 'light') return s;
        return media && media.matches ? 'dark' : 'light';
    }

    function apply(theme) {
        root.setAttribute('data-theme', theme);
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute('content', theme === 'dark' ? '#0b1220' : '#1F2937');
        document.querySelectorAll('.rf-theme-toggle').forEach(function (b) {
            b.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        });
    }

    window.rfToggleTheme = function () {
        var next = current() === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem(KEY, next); } catch (e) {}
        // Suspend transitions so surfaces switch together instead of fading at different speeds
        var st = document.createElement('style');
        st.textContent = '*,*::before,*::after{transition:none!important}';
        document.head.appendChild(st);
        apply(next);
        void root.offsetHeight;
        setTimeout(function () { st.remove(); }, 50);
    };

    apply(current());

    if (media && media.addEventListener) {
        media.addEventListener('change', function () { if (!stored()) apply(current()); });
    }
    // Keep data-theme if anything (e.g. a navigation swap) strips it, avoiding a light flash
    new MutationObserver(function () {
        if (root.getAttribute('data-theme') !== current()) apply(current());
    }).observe(root, { attributes: true, attributeFilter: ['data-theme'] });
    // Livewire wire:navigate swaps <html> attributes; re-apply after navigation
    document.addEventListener('livewire:navigated', function () { apply(current()); });
})();
