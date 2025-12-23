<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="theme-color" content="#1F2937">

    <link rel="icon" href="{{ asset('img/fav.jpg') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('img/fav.jpg') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('img/fav.jpg') }}">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap" rel="stylesheet">

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{env('APP_NAME') ?? __('app.ciblerh')}}</title>


    <meta name="msapplication-TileColor" content="#1F2937">

    <link type="text/css" href="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.css')}}" rel="stylesheet">
    <link type="text/css" href="{{ asset('vendor/notyf/notyf.min.css')}}" rel="stylesheet">
    <link type="text/css" href="{{ asset('vendor/fullcalendar/main.min.css')}}" rel="stylesheet">
    <link type="text/css" href="{{ asset('vendor/dropzone/dist/min/dropzone.min.css')}}" rel="stylesheet">
    <link type="text/css" href="{{ asset('vendor/choices.js/public/assets/styles/choices.min.css')}}" rel="stylesheet">
    <!-- Choices.js CDN CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
    <link type="text/css" href="{{ asset('vendor/leaflet/dist/leaflet.css')}}" rel="stylesheet">
    <link type="text/css" href="{{ asset('vendor/medium-editor/css/medium-editor.css')}}" rel="stylesheet">
    <link type="text/css" href="{{ asset('vendor/medium-editor/css/themes/default.css')}}" rel="stylesheet">
    <link type="text/css" href="{{ asset('css/theme.css')}}" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        /* Custom sidebar and content layout adjustments */
        @media (min-width: 768px) {
            .sidebar {
                width: 100%;
                max-width: 320px !important;
            }
        }

        @media (min-width: 992px) {
            .content {
                margin-left: 320px !important;
            }
        }

        /* Ensure sidebar stays within viewport on smaller screens */
        @media (max-width: 991.98px) {
            .sidebar {
                width: 100% !important;
            }

            main.content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
    @livewireStyles

    <!-- Alpine.js Choices Multi-Select Component -->
    <script>
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
                        if (initialSelected.length > 0) {
                            this.selectInstance.setChoiceByValue(initialSelected);
                        }

                        // On change, update Livewire property
                        this.selectInstance.passedElement.element.addEventListener('change', () => {
                            const rawValues = this.selectInstance.getValue(true);
                            let normalized = [];
                            if (Array.isArray(rawValues)) {
                                // Map and normalize values - handle both object format {value: "1"} and direct values
                                normalized = rawValues.map(item => {
                                    if (typeof item === 'object' && item !== null && item.value !== undefined) {
                                        return String(item.value);
                                    }
                                    return String(item);
                                }).filter(val => val !== '' && val !== 'undefined' && val !== 'null');
                            } else if (rawValues != null) {
                                normalized = [String(rawValues)];
                            }
                            
                            // Use the dedicated method for employee_id (more reliable for nested properties)
                            if (wireModel === 'newReport.filters.employee_id') {
                                // Call the dedicated method to update employee IDs
                                this.$wire.call('updateEmployeeIds', normalized).then(() => {
                                    // Verify the update worked (optional debugging)
                                    const currentValue = this.$wire.get('newReport.filters.employee_id');
                                    console.log('Employee IDs updated:', currentValue, 'Sent:', normalized);
                                });
                            } else if (wireModel.includes('.')) {
                                // For other nested properties, use dot notation (Livewire 3 supports this)
                                this.$wire.set(wireModel, normalized);
                            } else {
                                this.$wire.set(wireModel, normalized);
                            }
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
                                const uniqueChoices = Object.entries(uniqueOptionsMap).map(([value, label]) => ({
                                    value,
                                    label
                                }));

                                // Replace all choices with new unique choices
                                this.selectInstance.setChoices(uniqueChoices, 'value', 'label', true);

                                // Clear all selected items in UI and in Livewire model
                                this.selectInstance.removeActiveItems();
                                this.selectInstance.setValue([]);
                                
                                // Clear Livewire model using the appropriate method
                                if (wireModel === 'newReport.filters.employee_id') {
                                    this.$wire.call('updateEmployeeIds', []);
                                } else {
                                    this.$wire.set(wireModel, []);
                                }

                                // Set selected values if provided in the event
                                if (event.detail.selected && Array.isArray(event.detail.selected)) {
                                    this.selectInstance.setChoiceByValue(event.detail.selected);
                                    
                                    // Use the appropriate method to update Livewire model
                                    if (wireModel === 'newReport.filters.employee_id') {
                                        this.$wire.call('updateEmployeeIds', event.detail.selected);
                                    } else {
                                        this.$wire.set(wireModel, event.detail.selected);
                                    }
                                }
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

            // Custom searchable select with Choices.js styling
            Alpine.data('searchableSelect', (selectId, wireModel, placeholder) => {
                return {
                    open: false,
                    search: '',
                    selectedValue: '',
                    selectedLabel: '',
                    options: [],
                    filteredOptions: [],
                    init() {
                        this.loadOptions();

                        // Set initial value
                        const initialValue = this.$wire.get(wireModel);
                        if (initialValue) {
                            this.selectedValue = initialValue;
                            this.updateSelectedLabel();
                        }

                        // Watch for Livewire updates
                        this.$wire.$watch(wireModel, (value) => {
                            if (value !== this.selectedValue) {
                                this.selectedValue = value || '';
                                this.updateSelectedLabel();
                            }
                        });

                        // Listen for departments-updated if this is department field
                        if (selectId === 'departmentSelect') {
                            window.addEventListener('departments-updated', () => {
                                setTimeout(() => {
                                    this.loadOptions();
                                    this.selectedValue = '';
                                    this.selectedLabel = placeholder || '--Select--';
                                    this.$wire.set(wireModel, '');
                                }, 150);
                            });
                        }

                        // Watch for option changes in the select (for Livewire updates)
                        const observer = new MutationObserver(() => {
                            this.loadOptions();
                        });

                        const select = this.$refs[selectId];
                        if (select) {
                            observer.observe(select, {
                                childList: true,
                                subtree: true
                            });
                        }

                        // Close on outside click
                        this.$watch('open', (value) => {
                            if (value) {
                                this.$nextTick(() => {
                                    this.$refs.searchInput?.focus();
                                });
                            }
                        });
                    },
                    loadOptions() {
                        const select = this.$refs[selectId];
                        if (!select) return;

                        this.options = Array.from(select.options).map(opt => ({
                            value: opt.value,
                            label: opt.text,
                            searchText: opt.text.toLowerCase()
                        }));
                        this.filteredOptions = this.options;
                        this.updateSelectedLabel();
                    },
                    updateSelectedLabel() {
                        const option = this.options.find(opt => opt.value == this.selectedValue);
                        this.selectedLabel = option ? option.label : (placeholder || '--Select--');
                    },
                    filterOptions() {
                        if (!this.search.trim()) {
                            this.filteredOptions = this.options;
                            return;
                        }

                        const searchLower = this.search.toLowerCase();
                        this.filteredOptions = this.options.filter(opt =>
                            opt.searchText.includes(searchLower)
                        );
                    },
                    selectOption(option) {
                        this.selectedValue = option.value;
                        this.selectedLabel = option.label;
                        this.$wire.set(wireModel, option.value || '');
                        this.open = false;
                        this.search = '';
                        this.filterOptions();
                    },
                    toggle() {
                        this.open = !this.open;
                        if (!this.open) {
                            this.search = '';
                            this.filterOptions();
                        }
                    },
                    close() {
                        this.open = false;
                        this.search = '';
                        this.filterOptions();
                    }
                };
            });
        });
    </script>

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-pattern">

    {{$slot}}

    <script src=" {{ asset('vendor/@popperjs/core/dist/umd/popper.min.js')}}">
    </script>
    <script src="{{ asset('vendor/bootstrap/dist/js/bootstrap.min.js')}}"></script>
    <script src="{{ asset('vendor/onscreen/dist/on-screen.umd.min.js')}}"></script>
    <script src="{{ asset('vendor/nouislider/distribute/nouislider.min.js')}}"></script>
    <script src="{{ asset('vendor/smooth-scroll/dist/smooth-scroll.polyfills.min.js')}}"></script>
    <script src="{{ asset('vendor/chartist/dist/chartist.min.js')}}"></script>
    <script src="{{ asset('vendor/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js')}}"></script>
    <script src="{{ asset('vendor/vanillajs-datepicker/dist/js/datepicker.min.js')}}"></script>
    <script src="{{ asset('vendor/leaflet/dist/leaflet.js')}}"></script>
    <script src="{{ asset('vendor/simplebar/dist/simplebar.min.js')}}"></script>
    <script src="{{ asset('vendor/choices.js/public/assets/scripts/choices.min.js')}}"></script>
    <!-- Choices.js CDN JS -->
    <script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
    <script src="{{ asset('vendor/medium-editor/js/medium-editor.js')}}"></script>
    <script src="{{ asset('js/theme.js')}}"></script>



    @livewireScripts


    <script>
        document.addEventListener('livewire:init', () => {
            // Runs after Livewire is loaded but before it's initialized
            // on the page...
            var form = document.querySelector('.form-modal')
            if (form) {
                form.addEventListener('submit', function(e) {
                    var btn = document.querySelector('button[type="submit"].btn-loading')
                    btn.setAttribute('disabled', true)
                    btn.innerHtml =
                        `<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>{{ __('Loading') }}...`
                })
            }

            // closing any modal dynamically
            window.Livewire.on('cancel', ({
                modalId
            }) => {
                const modal = document.getElementById(modalId);

                modalEl = bootstrap.Modal.getInstance(modal)
                if (modalEl) {
                    modalEl.hide()
                }

            });

            // Handle toast notifications
            window.Livewire.on('showToast', (params) => {
                let toastContainer = document.getElementById('toast-container');
                if (!toastContainer) {
                    toastContainer = document.createElement('div');
                    toastContainer.id = 'toast-container';
                    toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                    toastContainer.style.zIndex = '1100';
                    document.body.appendChild(toastContainer);
                }

                const toastEl = document.createElement('div');
                toastEl.className = `toast align-items-center text-white bg-${params.type || 'success'} border-0`;
                toastEl.setAttribute('role', 'alert');
                toastEl.setAttribute('aria-live', 'assertive');
                toastEl.setAttribute('aria-atomic', 'true');

                toastEl.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            ${params.message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;

                toastContainer.appendChild(toastEl);

                const toast = new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 5000
                });
                toast.show();

                toastEl.addEventListener('hidden.bs.toast', () => {
                    toastEl.remove();
                });
            });

            // Handle modal opening
            window.Livewire.on('open-modal', (modalId) => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                }
            });

            // Handle modal closing
            window.Livewire.on('close-modal', (data) => {
                if (data.id) {
                    const modal = document.getElementById(data.id);
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) {
                            bsModal.hide();
                        }
                    }
                }
            });

        })
    </script>
    @stack('scripts')
</body>

</html>