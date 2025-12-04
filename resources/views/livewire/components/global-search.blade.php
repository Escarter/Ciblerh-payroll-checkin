<div x-data="{ modalOpen: @entangle('isOpen').live }" 
     x-init="
        $watch('modalOpen', value => {
            if (value) {
                $nextTick(() => {
                    $refs.searchInput?.focus();
                });
            }
        })
     ">
    <!-- Global Search Modal -->
    <div class="modal fade"
         :class="{ 'show': modalOpen }"
         id="globalSearchModal"
         tabindex="-1"
         :style="modalOpen ? 'display: block;' : 'display: none;'"
         wire:ignore.self
         @keydown.arrow-up.prevent="$wire.selectPrevious()"
         @keydown.arrow-down.prevent="$wire.selectNext()"
         @keydown.page-up.prevent="$wire.selectPrevious(5)"
         @keydown.page-down.prevent="$wire.selectNext(5)"
         @keydown.home.prevent="$wire.selectFirst()"
         @keydown.end.prevent="$wire.selectLast()"
         @keydown.enter.prevent="$wire.selectItem()"
         @keydown.escape.prevent="$wire.closeSearch()">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <!-- Modal Header with Close Button -->
                <div class="modal-header border-bottom">
                    <button type="button"
                            class="btn-close ms-auto"
                            @click="$wire.closeSearch()"
                            aria-label="Close">
                    </button>
                </div>

                <!-- Search Input -->
                <div class="p-4 border-bottom">
                    <div class="position-relative">
                        <svg class="position-absolute top-50 translate-middle-y ms-3 text-muted" 
                             width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input
                            type="text"
                            class="form-control form-control-lg ps-5 border-0 bg-light"
                            placeholder="{{ __('common.search_pages_features') }}"
                            wire:model.live.debounce.300ms="search"
                            x-ref="searchInput"
                            id="globalSearchInput"
                            autocomplete="off">
                        
                        <div class="position-absolute top-50 translate-middle-y end-0 me-3">
                            <kbd class="badge bg-light text-muted">Esc</kbd>
                        </div>
                    </div>
                </div>

                <!-- Search Results -->
                <div class="modal-body p-0" style="max-height: 400px; overflow-y: auto;">
                    @if(count($results) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($results as $index => $item)
                                <button type="button" 
                                        class="list-group-item list-group-item-action border-0 px-4 py-3 d-flex align-items-center
                                               @if($selectedIndex === $index) active @endif"
                                        wire:click="selectItem({{ $index }})"
                                        wire:key="search-result-{{ $item['id'] }}">
                                    
                                    <!-- Icon -->
                                    <div class="me-3">
                                        @if(isset($item['icon']))
                                            <i class="{{ $item['icon'] }} text-muted fa-fw"></i>
                                        @else
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-muted">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="flex-grow-1 text-start">
                                        <div class="fw-semibold text-dark">{{ $item['label'] }}</div>
                                        @if($item['description'])
                                            <div class="small text-muted">{{ $item['description'] }}</div>
                                        @endif
                                    </div>
                                    
                                    <!-- Group Badge -->
                                    @if($item['group'])
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary small">
                                            {{ $item['group'] }}
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="text-muted">
                                @if(empty(trim($search)))
                                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="mx-auto mb-3 text-muted">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <p class="mb-0">{{ __('common.type_to_search') }}</p>
                                @else
                                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="mx-auto mb-3 text-muted">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="mb-0">{{ __('common.no_results_found') }} "{{ $search }}"</p>
                                    <small class="text-muted">{{ __('common.try_searching_else') }}</small>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Backdrop -->
    <div x-show="modalOpen"
         class="modal-backdrop fade show"
         @click="$wire.closeSearch()"></div>

    <!-- JavaScript for keyboard shortcuts and focus -->
    @script
    <script>
        // Keyboard shortcut listener
        document.addEventListener('keydown', function(e) {
            // Cmd+K (Mac) or Ctrl+K (Windows/Linux)
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                $wire.call('openSearch');
            }
        });

        // Focus input when modal opens
        $wire.on('openGlobalSearch', () => {
            setTimeout(() => {
                const input = document.getElementById('globalSearchInput');
                if (input) {
                    input.focus();
                }
            }, 100);
        });
    </script>
    @endscript
</div>