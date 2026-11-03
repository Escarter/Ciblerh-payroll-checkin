(function () {
    function enhance(root) {
        root.querySelectorAll('.modal .modal-content').forEach((content) => {
            if (content.querySelector('[data-rf-close], .btn-close[data-bs-dismiss="modal"], .close[data-bs-dismiss="modal"]')) return;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'rf-modal-close';
            btn.setAttribute('data-rf-close', '1');
            btn.setAttribute('data-bs-dismiss', 'modal');
            btn.setAttribute('aria-label', 'Close');
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>';
            content.prepend(btn);
        });
    }
    const run = () => enhance(document);
    document.addEventListener('DOMContentLoaded', run);
    document.addEventListener('livewire:navigated', run);
    document.addEventListener('livewire:init', () => {
        window.Livewire.hook('commit', ({ succeed }) => succeed(() => setTimeout(run, 0)));
    });
    document.addEventListener('show.bs.modal', run);
    if (document.readyState !== 'loading') run();
})();
