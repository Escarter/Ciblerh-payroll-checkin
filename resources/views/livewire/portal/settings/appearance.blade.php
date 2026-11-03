<div>
    <x-alert />

    <div class="py-2 pb-2">
        <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                <li class="breadcrumb-item"><a href="{{ route('portal.dashboard') }}" wire:navigate>{{ __('dashboard.home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('portal.settings.index') }}" wire:navigate>{{ __('common.settings') }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('settings.appearance') }}</li>
            </ol>
        </nav>
        <h1 class="h4">{{ __('settings.appearance') }}</h1>
        <p class="mb-0 text-muted">{{ __('settings.appearance_subtitle') }}</p>
    </div>

    <div wire:ignore.self class="modal fade" id="ResetAppearanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="p-3 p-lg-4">
                        <div class="mb-4 mt-md-0 text-center">
                            <svg class="icon icon-xxl text-danger mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <h1 class="mb-0 h2 fw-bolder">{{ __('common.are_you_sure') }}</h1>
                            <p class="pt-2">{{ __('settings.reset_confirm') }}</p>
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="button" wire:click="resetDefaults" class="btn btn-danger mx-3" data-bs-dismiss="modal">{{ __('common.confirm') }}</button>
                            <button type="button" class="btn btn-gray-300 text-white" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="row g-4 mt-1" x-data="{ p: @entangle('primary_color'), s: @entangle('secondary_color'), sb: @entangle('sidebar_color'), en: @entangle('emp_nav_color'), eh: @entangle('emp_hero_color'), pb: @entangle('page_bg_color') }">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header"><h2 class="h6 mb-0">{{ __('settings.identity') }}</h2></div>
                <div class="card-body">
                    <div class="mb-4">
                        <label for="app_name">{{ __('settings.app_name') }}</label>
                        <input id="app_name" type="text" wire:model="app_name" class="form-control @error('app_name') is-invalid @enderror" placeholder="CibleRH">
                        @error('app_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="show_logo" wire:model.live="show_logo">
                        <label class="form-check-label" for="show_logo">{{ __('settings.show_logo') }}</label>
                        <div class="small text-muted">{{ __('settings.show_logo_hint') }}</div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label>{{ __('settings.logo') }}</label>
                            <div class="rf-brand-preview mb-2"><img src="{{ $logo ? $logo->temporaryUrl() : $current_logo }}" alt=""></div>
                            <input type="file" wire:model="logo" accept="image/*" class="form-control @error('logo') is-invalid @enderror">
                            <div class="small text-muted mt-1">{{ __('settings.logo_hint') }}</div>
                            @error('logo') <div class="text-danger small">{{ $message }}</div> @enderror
                            @if (\App\Models\Setting::first()?->brand_logo_path)
                                <button type="button" wire:click="removeAsset('logo')" class="btn btn-link btn-sm text-danger p-0 mt-1">{{ __('settings.use_default') }}</button>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('settings.favicon') }}</label>
                            <div class="rf-brand-preview rf-brand-preview-sm mb-2"><img src="{{ $favicon ? $favicon->temporaryUrl() : $current_favicon }}" alt=""></div>
                            <input type="file" wire:model="favicon" accept="image/*" class="form-control @error('favicon') is-invalid @enderror">
                            <div class="small text-muted mt-1">{{ __('settings.favicon_hint') }}</div>
                            @error('favicon') <div class="text-danger small">{{ $message }}</div> @enderror
                            @if (\App\Models\Setting::first()?->brand_favicon_path)
                                <button type="button" wire:click="removeAsset('favicon')" class="btn btn-link btn-sm text-danger p-0 mt-1">{{ __('settings.use_default') }}</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h2 class="h6 mb-0">{{ __('settings.colors_and_font') }}</h2></div>
                <div class="card-body">
                    <div class="mb-4">
                        <label>{{ __('settings.palettes') }}</label>
                        <div class="rf-palettes">
                            @foreach ([
                                ['Ocean', '#1d4ba0', '#38bdf8'],
                                ['Teal', '#0e7490', '#f59e0b'],
                                ['Forest', '#047857', '#a3e635'],
                                ['Violet', '#7c3aed', '#f472b6'],
                                ['Ruby', '#be123c', '#fb923c'],
                                ['Amber', '#b45309', '#0ea5e9'],
                                ['Midnight', '#0f172a', '#6366f1'],
                                ['Sunset', '#c2410c', '#8b5cf6'],
                ['Sky', '#0369a1', '#f97316'],
                ['Emerald', '#059669', '#6366f1'],
                ['Plum', '#86198f', '#facc15'],
                ['Rose', '#e11d48', '#14b8a6'],
                ['Slate', '#334155', '#22d3ee'],
                ['Indigo', '#4338ca', '#fbbf24'],
                ['Lime', '#4d7c0f', '#e879f9'],
                ['Coral', '#dc2626', '#0ea5e9'],
                ['Cocoa', '#7c2d12', '#d4a373'],
                ['Navy', '#1e3a8a', '#f43f5e'],
                ['Mint', '#0f766e', '#a3e635'],
                ['Graphite', '#18181b', '#a1a1aa'],
                            ] as [$name, $pp, $ss])
                                <button type="button" class="rf-palette" x-on:click="p='{{ $pp }}'; s='{{ $ss }}'; eh='{{ $pp }}'" x-bind:class="{ 'is-on': p === '{{ $pp }}' && s === '{{ $ss }}' }">
                                    <span class="rf-palette-chip"><i style="background:{{ $pp }}"></i><i style="background:{{ $ss }}"></i></span>
                                    <small>{{ $name }}</small>
                                </button>
                            @endforeach
                        </div>
                        <div class="small text-muted mt-1">{{ __('settings.palettes_hint') }}</div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label>{{ __('settings.primary_color') }}</label>
                            <div class="rf-color-field">
                                <input type="color" x-model="p" aria-label="{{ __('settings.primary_color') }}">
                                <input type="text" x-model="p" class="form-control @error('primary_color') is-invalid @enderror" maxlength="7">
                            </div>
                                                        <div class="rf-swatches" role="group">
                                @foreach (['#1d4ba0','#0e7490','#047857','#7c3aed','#be123c','#b45309','#0f172a','#c2410c','#0369a1'] as $c)
                                    <button type="button" class="rf-swatch" style="background:{{ $c }}" x-on:click="p='{{ $c }}'" x-bind:class="{ 'is-on': p === '{{ $c }}' }" aria-label="{{ $c }}"></button>
                                @endforeach
                            </div>
@error('primary_color') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('settings.secondary_color') }}</label>
                            <div class="rf-color-field">
                                <input type="color" x-model="s" aria-label="{{ __('settings.secondary_color') }}">
                                <input type="text" x-model="s" class="form-control @error('secondary_color') is-invalid @enderror" maxlength="7">
                            </div>
                                                        <div class="rf-swatches" role="group">
                                @foreach (['#3b6fd0','#06b6d4','#10b981','#a78bfa','#f43f5e','#f59e0b','#64748b','#fb923c','#38bdf8'] as $c)
                                    <button type="button" class="rf-swatch" style="background:{{ $c }}" x-on:click="s='{{ $c }}'" x-bind:class="{ 'is-on': s === '{{ $c }}' }" aria-label="{{ $c }}"></button>
                                @endforeach
                            </div>
@error('secondary_color') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('settings.sidebar_color') }}</label>
                            <div class="rf-color-field">
                                <input type="color" x-model="sb" aria-label="{{ __('settings.sidebar_color') }}">
                                <input type="text" x-model="sb" class="form-control @error('sidebar_color') is-invalid @enderror" maxlength="7">
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                @foreach (['#ffffff' => 'White', '#0f172a' => 'Slate', '#1d4ba0' => 'Blue', '#14532d' => 'Green', '#3f1d5c' => 'Purple'] as $hex => $name)
                                    <button type="button" class="rf-swatch" style="background:{{ $hex }}" title="{{ $name }}" x-on:click="sb='{{ $hex }}'"></button>
                                @endforeach
                            </div>
                            @error('sidebar_color') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="font_family">{{ __('settings.font') }}</label>
                            <select id="font_family" wire:model.live="font_family" class="form-select">
                                @foreach ($fonts as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="timezone">{{ __('settings.timezone') }}</label>
                            <select id="timezone" wire:model="timezone" class="form-select @error('timezone') is-invalid @enderror">
                                @foreach ($timezones as $tz)
                                    <option value="{{ $tz }}">{{ $tz }}</option>
                                @endforeach
                            </select>
                            @error('timezone') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h2 class="h6 mb-0">{{ __('settings.employee_portal') }}</h2></div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label>{{ __('settings.emp_nav_color') }}</label>
                            <div class="rf-color-field">
                                <input type="color" x-model="en" aria-label="{{ __('settings.emp_nav_color') }}">
                                <input type="text" x-model="en" class="form-control @error('emp_nav_color') is-invalid @enderror" maxlength="7">
                            </div>
                                                        <div class="rf-swatches" role="group">
                                @foreach (['#ffffff','#f1f5f9','#1d4ba0','#0f172a','#0e7490','#047857','#7c3aed','#be123c','#b45309'] as $c)
                                    <button type="button" class="rf-swatch" style="background:{{ $c }}" x-on:click="en='{{ $c }}'" x-bind:class="{ 'is-on': en === '{{ $c }}' }" aria-label="{{ $c }}"></button>
                                @endforeach
                            </div>
@error('emp_nav_color') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('settings.emp_hero_color') }}</label>
                            <div class="rf-color-field">
                                <input type="color" x-model="eh" aria-label="{{ __('settings.emp_hero_color') }}">
                                <input type="text" x-model="eh" class="form-control @error('emp_hero_color') is-invalid @enderror" maxlength="7">
                            </div>
                                                        <div class="rf-swatches" role="group">
                                @foreach (['#ffffff','#f1f5f9','#1d4ba0','#0f172a','#0e7490','#047857','#7c3aed','#be123c','#b45309'] as $c)
                                    <button type="button" class="rf-swatch" style="background:{{ $c }}" x-on:click="eh='{{ $c }}'" x-bind:class="{ 'is-on': eh === '{{ $c }}' }" aria-label="{{ $c }}"></button>
                                @endforeach
                            </div>
@error('emp_hero_color') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('settings.page_bg_color') }}</label>
                            <div class="rf-color-field">
                                <input type="color" x-model="pb" aria-label="{{ __('settings.page_bg_color') }}">
                                <input type="text" x-model="pb" class="form-control @error('page_bg_color') is-invalid @enderror" maxlength="7">
                            </div>
                                                        <div class="rf-swatches" role="group">
                                @foreach (['#f7f8fb','#ffffff','#f1f5f9','#fef9f3','#f0fdf4','#eff6ff','#faf5ff','#0f172a'] as $c)
                                    <button type="button" class="rf-swatch" style="background:{{ $c }}" x-on:click="pb='{{ $c }}'" x-bind:class="{ 'is-on': pb === '{{ $c }}' }" aria-label="{{ $c }}"></button>
                                @endforeach
                            </div>
@error('page_bg_color') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="radius">{{ __('settings.roundness') }}</label>
                            <select id="radius" wire:model="radius" class="form-select">
                                <option value="sharp">{{ __('settings.radius_sharp') }}</option>
                                <option value="soft">{{ __('settings.radius_soft') }}</option>
                                <option value="round">{{ __('settings.radius_round') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="small text-muted mt-3">{{ __('settings.employee_portal_hint') }}</div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-gray-600" data-bs-toggle="modal" data-bs-target="#ResetAppearanceModal">{{ __('settings.reset_defaults') }}</button>
                <button type="submit" class="btn btn-gray-800" wire:loading.attr="disabled" wire:target="save,logo,favicon">{{ __('common.save') }}</button>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="position-sticky" style="top:5rem;" x-bind:style="`--pv:${p};--pa:${s};--sb:${sb};--en:${en};--eh:${eh};--pb:${pb}`">
            <div class="card mb-3">
                <div class="card-header"><h2 class="h6 mb-0">{{ __('settings.preview') }}</h2></div>
                <div class="card-body rf-pv">
                    @if ($show_logo)
                        <img src="{{ $logo ? $logo->temporaryUrl() : $current_logo }}" alt="" class="rf-pv-logo mb-3">
                    @else
                        <div class="rf-brand-text mb-3">{{ $app_name ?: config('app.name') }}</div>
                    @endif
                    <div class="rf-pv-title">{{ $app_name ?: config('app.name') }}</div>
                    <div class="rf-pv-bar"></div>
                    <div class="d-flex gap-2 my-3">
                        <span class="rf-pv-btn" style="background:var(--pv)">{{ __('common.save') }}</span>
                        <span class="rf-pv-btn rf-pv-btn-soft" style="color:var(--pv)">{{ __('common.cancel') }}</span>
                    </div>
                    <span class="rf-pv-badge" style="background:var(--pv)">Active</span>
                    <span class="rf-pv-badge" style="background:var(--pa)">Accent</span>
                    <div class="rf-pv-sidebar mt-3" style="background:var(--sb)"><span></span><span></span><span></span></div>
                </div>
            </div>

            <div class="card" x-data="{ dark(c) { const n = parseInt((c || '#fff').slice(1), 16); return (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) < 150; } }">
                <div class="card-header"><h2 class="h6 mb-0">{{ __('settings.employee_portal_preview') }}</h2></div>
                <div class="card-body p-3">
                    <div class="rf-ep" x-bind:class="{ 'is-dark': dark(en) }" x-bind:style="`background:${pb}`">
                        <div class="rf-ep-nav" x-bind:style="`background:${en}`">
                            @if ($show_logo)
                                <img src="{{ $logo ? $logo->temporaryUrl() : $current_logo }}" alt="" class="rf-ep-logo">
                            @else
                                <span class="rf-ep-name">{{ $app_name ?: config('app.name') }}</span>
                            @endif
                            <span class="rf-ep-links"><i class="on"></i><i></i><i></i></span>
                            <span class="rf-ep-pill"></span>
                        </div>
                        <div class="rf-ep-body">
                            <div class="rf-ep-hero" x-bind:style="`background:linear-gradient(135deg, ${eh}, ${eh}cc)`">
                                <small>{{ __('settings.preview') }}</small>
                                <strong>{{ $app_name ?: config('app.name') }}</strong>
                            </div>
                            <div class="rf-ep-tiles">
                                <span><b style="background:var(--pv)"></b></span>
                                <span><b style="background:var(--pa)"></b></span>
                                <span><b style="background:var(--pv)"></b></span>
                            </div>
                        </div>
                        <div class="rf-ep-tabbar" x-bind:style="`background:${en}`"><i class="on"></i><i></i><i></i><i></i></div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </form>
</div>
