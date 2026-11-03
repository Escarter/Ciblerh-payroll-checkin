@php
    $u = auth()->user();
    $sv = fn ($d) => '<svg class="emp-ico emp-ico-sm" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="' . $d . '"/></svg>';
@endphp
<nav class="navbar navbar-top navbar-expand navbar-dashboard emp-topbar rf-admin-bar">
    <div class="emp-topbar-inner">
        <button id="sidebar-toggle" type="button" aria-label="Toggle sidebar" title="Toggle sidebar" class="sidebar-toggle btn btn-icon-only">
            <svg class="toggle-icon" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
            </svg>
        </button>
        <a href="{{ route('portal.dashboard') }}" wire:navigate class="emp-brand d-lg-none"><x-brand-logo /></a>

        @auth
        <button type="button" class="emp-pill d-none d-md-inline-flex" onclick="Livewire.dispatch('openGlobalSearch')" title="Search (Ctrl+K)">
            {!! $sv('M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z') !!} {{ __('common.search') }} <kbd class="d-none d-xl-inline">⌘K</kbd>
        </button>

        <div class="emp-actions">
            <button type="button" class="emp-pill rf-theme-toggle" onclick="rfToggleTheme()" title="{{ __('common.toggle_dark_mode') }}" aria-label="{{ __('common.toggle_dark_mode') }}"><svg class="emp-ico emp-ico-sm rf-icon-moon" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg><svg class="emp-ico emp-ico-sm rf-icon-sun" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg></button>
            <div class="emp-lang">
                <a href="{{ route('language-switcher', ['locale' => 'fr']) }}" wire:navigate class="{{ \App::isLocale('fr') ? 'on' : '' }}">FR</a>
                <a href="{{ route('language-switcher', ['locale' => 'en']) }}" wire:navigate class="{{ \App::isLocale('en') ? 'on' : '' }}">EN</a>
            </div>
            @if($u->canSwitchPortals())
            <a href="{{ route('employee.dashboard') }}" class="emp-pill" title="Employee Portal">{!! $sv('M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4') !!}<span class="d-none d-lg-inline">Employee</span></a>
            @endif
            <a href="{{ route('portal.profile-setting') }}" wire:navigate class="emp-avatar" title="{{ __('common.profile') }}">{{ $u->initials }}</a>
            <a href="{{ route('logout') }}" wire:navigate class="emp-pill emp-pill-danger" title="Logout">{!! $sv('M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1') !!}<span class="d-none d-lg-inline">Logout</span></a>
        </div>
        @endauth
    </div>
</nav>
