(function () {
    const UPLOAD = '<svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>';
    const DONE = '<svg class="icon icon-md text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';

    const esc = (s) => s.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    function hint(input) {
        const accept = (input.getAttribute('accept') || '').split(',')
            .map((t) => t.trim().replace(/^\./, '').replace(/^.*\//, '').toUpperCase()).filter(Boolean);
        return accept.length ? accept.join(', ') : '';
    }

    function render(zone, input) {
        const files = input.files;
        if (files && files.length) {
            const f = files[0];
            zone.innerHTML = DONE + '<div class="fw-bold text-break">' + esc(f.name) + '</div>'
                + '<div class="small text-muted">' + (f.size / 1048576).toFixed(2) + ' MB · Click to replace</div>';
        } else {
            const h = hint(input);
            zone.innerHTML = UPLOAD + '<div class="fw-bold">Click to upload or drag &amp; drop</div>'
                + (h ? '<div class="small text-muted">' + esc(h) + '</div>' : '');
        }
    }

    function enhance(root) {
        root.querySelectorAll('input[type="file"]:not([data-rf-dz]):not(.d-none):not([data-no-dropzone])').forEach((input) => {
            if (input.closest('label.rf-dropzone, .rf-dropzone')) return;
            input.setAttribute('data-rf-dz', '1');
            input.classList.add('rf-file-hidden');

            const zone = document.createElement('div');
            zone.className = 'rf-dropzone w-100';
            zone.setAttribute('role', 'button');
            zone.tabIndex = 0;
            zone.setAttribute('data-rf-dz-zone', '1');
            render(zone, input);
            input.insertAdjacentElement('afterend', zone);

            zone.addEventListener('click', () => input.click());
            zone.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
            });
            ['dragenter', 'dragover'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.add('is-dragging'); }));
            ['dragleave', 'drop'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.remove('is-dragging'); }));
            zone.addEventListener('drop', (e) => {
                if (!e.dataTransfer || !e.dataTransfer.files.length) return;
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
            input.addEventListener('change', () => render(zone, input));
            const form = input.closest('form');
            if (form) form.addEventListener('reset', () => setTimeout(() => render(zone, input), 0));
        });

        // Drop orphaned zones whose input was removed or re-rendered by Livewire.
        root.querySelectorAll('[data-rf-dz-zone]').forEach((zone) => {
            const prev = zone.previousElementSibling;
            if (!prev || prev.tagName !== 'INPUT') zone.remove();
            else render(zone, prev);
        });
    }

    const run = () => enhance(document);
    document.addEventListener('DOMContentLoaded', run);
    document.addEventListener('livewire:navigated', run);
    document.addEventListener('livewire:init', () => {
        window.Livewire.hook('commit', ({ succeed }) => succeed(() => setTimeout(run, 0)));
    });
    document.addEventListener('shown.bs.modal', run);
    if (document.readyState !== 'loading') run();
})();
