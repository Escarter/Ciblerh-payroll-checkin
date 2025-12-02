import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Choices.js is loaded via CDN, so it's available globally as window.Choices

document.addEventListener('alpine:init', () => {
    Alpine.data('choicesMultiSelect', (prettyname, wireModel, selected) => {
        return {
            selectInstance: null,
            eventHandler: null,
            init() {
                // Initialize Choices instance only once
                this.selectInstance = new window.Choices(this.$refs[prettyname], {
                    itemSelectText: '',
                    removeItems: true,
                    removeItemButton: true,
                    noResultsText: 'No results found',
                    noChoicesText: 'No options available',
                });

                // Set initial selected values if any
                const initialSelected = Array.isArray(selected) ? selected : (selected ? [selected] : []);
                this.selectInstance.setChoiceByValue(initialSelected);

                // On change, update Livewire property
                this.selectInstance.passedElement.element.addEventListener('change', () => {
                    const rawValues = this.selectInstance.getValue(true);
                    let normalized = [];
                    if (Array.isArray(rawValues)) {
                        normalized = rawValues.map(item => (typeof item === 'object' && item !== null && item.value) ? String(item.value) : String(item));
                    } else if (rawValues != null) {
                        normalized = [String(rawValues)];
                    }
                    this.$wire.set(wireModel, normalized);
                });

                // Remove any existing event listener to avoid duplicates
                if (this.eventHandler) {
                    window.removeEventListener('refreshChoices', this.eventHandler);
                }

                // Listen for refreshChoices event to update options
                this.eventHandler = (event) => {
                    if (event.detail.id === prettyname) {
                        // Clear existing choices and selected items completely
                        this.selectInstance.clearChoices(); // Clears choices but keeps selected items
                        this.selectInstance.clearStore(); // Clears everything (choices + items) — use carefully

                        // Prepare choices array without duplicates
                        const uniqueOptionsMap = {};
                        for (const [value, label] of Object.entries(event.detail.options)) {
                            uniqueOptionsMap[value] = label;
                        }
                        const uniqueChoices = Object.entries(uniqueOptionsMap).map(([value, label]) => ({ value, label }));

                        // Replace all choices with new unique choices
                        this.selectInstance.setChoices(uniqueChoices, 'value', 'label', true);

                        // Clear all selected items in UI and in Livewire model
                        this.selectInstance.removeActiveItems();
                        this.selectInstance.setValue([]);
                        this.$wire.set(wireModel, []);
                    }
                };

                window.addEventListener('refreshChoices', this.eventHandler);

                // Sync Livewire changes with Choices
                this.$wire.$watch(wireModel, (newValues) => {
                    const currentValues = this.selectInstance.getValue(true);
                    const normalizedNew = Array.isArray(newValues) ? newValues : (newValues ? [newValues] : []);

                    if (JSON.stringify(currentValues.sort()) !== JSON.stringify(normalizedNew.sort())) {
                        this.selectInstance.setChoiceByValue(normalizedNew);
                    }
                });
            },
            destroy() {
                // Clean up event listener when component destroyed
                if (this.eventHandler) {
                    window.removeEventListener('refreshChoices', this.eventHandler);
                }
                if (this.selectInstance) {
                    this.selectInstance.destroy();
                }
            }
        }
    });

    // Single select with search functionality
    Alpine.data('choicesSelect', (selectId, wireModel, placeholder, listenToDepartments = false) => {
        return {
            selectInstance: null,
            eventHandler: null,
            init() {
                this.$nextTick(() => {
                    this.selectInstance = new window.Choices(this.$refs[selectId], {
                        itemSelectText: '',
                        searchEnabled: true,
                        searchChoices: true,
                        shouldSort: true,
                        placeholder: true,
                        placeholderValue: placeholder || '--Select--',
                    });

                    // Set initial value if exists
                    const initialValue = this.$wire.get(wireModel);
                    if (initialValue) {
                        this.selectInstance.setChoiceByValue(initialValue);
                    }

                    // On change, update Livewire property
                    this.selectInstance.passedElement.element.addEventListener('change', (event) => {
                        this.$wire.set(wireModel, event.detail.value || '');
                    });

                    // Only listen for departments-updated event if this is the department field
                    if (listenToDepartments) {
                        this.eventHandler = () => {
                            this.updateOptions();
                        };
                        window.addEventListener('departments-updated', this.eventHandler);
                    }
                });
            },
            updateOptions() {
                if (!this.selectInstance) return;
                
                this.$nextTick(() => {
                    const select = this.$refs[selectId];
                    const departments = this.$wire.get('departments');
                    
                    // Clear and rebuild options
                    select.innerHTML = '<option value="">' + (placeholder || '--Select--') + '</option>';
                    
                    if (departments && departments.length > 0) {
                        departments.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.id;
                            const employeeCount = dept.employees ? dept.employees.length : (dept.employees_count || 0);
                            option.textContent = dept.name + ' - with ' + employeeCount + ' employees';
                            select.appendChild(option);
                        });
                    }
                    
                    // Update Choices instance
                    const options = Array.from(select.options).map(opt => ({
                        value: opt.value,
                        label: opt.text
                    }));
                    
                    this.selectInstance.clearChoices();
                    this.selectInstance.setChoices(options, 'value', 'label', true);
                    this.selectInstance.setChoiceByValue('');
                    this.$wire.set(wireModel, '');
                });
            },
            destroy() {
                if (this.eventHandler) {
                    window.removeEventListener('departments-updated', this.eventHandler);
                }
                if (this.selectInstance) {
                    this.selectInstance.destroy();
                }
            }
        };
    });
});

Livewire.start()