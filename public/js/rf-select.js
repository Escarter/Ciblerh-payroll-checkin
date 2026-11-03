/* Upgrades every single-value select to a searchable Choices.js dropdown. Opt out with data-no-search. */
(function () {
    if (window.__rfSelect) return;
    window.__rfSelect = true;

    const SKIP = '[multiple], [data-no-search], [data-rf-select], .choices__input';

    function enhance(root) {
        if (!window.Choices) return;
        (root || document).querySelectorAll('select.form-select, select.form-control').forEach((el) => {
            if (el.matches(SKIP) || el.closest('.choices, .rf-search-select')) return;
            if (el.options.length < 2) return;
            try {
                const instance = new window.Choices(el, {
                    searchEnabled: true,
                    searchPlaceholderValue: 'Search…',
                    shouldSort: false,
                    itemSelectText: '',
                    allowHTML: false,
                    placeholder: false,
                    noResultsText: 'No results found',
                    noChoicesText: 'No options available',
                });
                el.setAttribute('data-rf-select', '1');
                if (el.disabled) instance.disable();
            } catch (e) { /* leave the native select in place */ }
        });
    }

    const run = () => enhance(document);

    document.addEventListener('DOMContentLoaded', run);
    document.addEventListener('livewire:navigated', run);
    document.addEventListener('livewire:init', () => {
        window.Livewire.hook('commit', ({ succeed }) => {
            succeed(() => setTimeout(run, 0));
        });
    });
    document.addEventListener('shown.bs.modal', run);
    if (document.readyState !== 'loading') run();
})();
