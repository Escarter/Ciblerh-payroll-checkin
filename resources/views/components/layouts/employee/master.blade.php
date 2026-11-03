<x-layouts.app>
    <x-layouts.employee.topbar />
    <main class="emp-main">
        {{$slot}}
    </main>

    <!-- Global Search Component -->
    @livewire('components.global-search')

    <script>
        // Copies column headers onto cells so tables can render as cards on phones.
        (function labelCells() {
            const run = () => document.querySelectorAll('.emp-main table').forEach(t => {
                const heads = [...t.querySelectorAll('thead th')].map(h => h.textContent.trim());
                t.querySelectorAll('tbody tr').forEach(r => [...r.children].forEach((c, i) => {
                    if (heads[i] && !c.hasAttribute('colspan') && c.dataset.label !== heads[i]) c.dataset.label = heads[i];
                }));
            });
            let t;
            const schedule = () => { clearTimeout(t); t = setTimeout(run, 30); };
            new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true });
            document.addEventListener('DOMContentLoaded', run);
            document.addEventListener('livewire:navigated', run);
            run();
        })();
    </script>
</x-layouts.app>
