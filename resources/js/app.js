import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Choices.js is loaded via CDN, so it's available globally as window.Choices

document.addEventListener('alpine:init', () => {
    // Note: choicesMultiSelect is now defined in the app layout file
    // This file only contains other Alpine components if needed

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
                        allowHTML: true,
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