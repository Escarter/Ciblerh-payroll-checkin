<div>
    <!-- Global Search Modal -->
    <div class="modal fade @if($isOpen) show @endif" 
         id="globalSearchModal" 
         tabindex="-1" 
         style="@if($isOpen) display: block; @endif"
         wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
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
                            placeholder="Search pages, features..." 
                            wire:model.live.debounce.300ms="search"
                            wire:keydown.arrow-down="selectNext"
                            wire:keydown.arrow-up="selectPrevious"
                            wire:keydown.enter="selectItem"
                            wire:keydown.escape="closeSearch"
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
                                    <p class="mb-0">Type to search for pages and features</p>
                                @else
                                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="mx-auto mb-3 text-muted">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="mb-0">No results found for "{{ $search }}"</p>
                                    <small class="text-muted">Try searching for something else</small>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="p-3 bg-light border-top">
                    <div class="d-flex justify-content-between align-items-center small text-muted">
                        <div>
                            <kbd class="badge bg-white border">↑↓</kbd> to navigate
                            <kbd class="badge bg-white border ms-1">↵</kbd> to select
                        </div>
                        <div>
                            <kbd class="badge bg-white border">⌘K</kbd> or 
                            <kbd class="badge bg-white border">Ctrl+K</kbd> to search
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backdrop -->
    @if($isOpen)
        <div class="modal-backdrop fade show" wire:click="closeSearch"></div>
    @endif

    <!-- JavaScript for keyboard shortcuts and focus -->
    <script>
        document.addEventListener('livewire:init', () => {
            // Keyboard shortcut listener
            document.addEventListener('keydown', function(e) {
                // Cmd+K (Mac) or Ctrl+K (Windows/Linux)
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    @this.call('openSearch');
                }
            });

            // Focus input when modal opens
            Livewire.on('openGlobalSearch', () => {
                setTimeout(() => {
                    const input = document.getElementById('globalSearchInput');
                    if (input) {
                        input.focus();
                    }
                }, 100);
            });
        });

        // Focus input when modal is opened
        document.addEventListener('livewire:update', () => {
            if (@js($isOpen)) {
                setTimeout(() => {
                    const input = document.getElementById('globalSearchInput');
                    if (input) {
                        input.focus();
                    }
                }, 100);
            }
        });
    </script>
</div>