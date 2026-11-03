@php
    $user = auth()->user();
    $ico = [
        'home' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10',
        'check' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'cash' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'doc' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'leave' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'dots' => 'M5 12h.01M12 12h.01M19 12h.01',
        'logout' => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
        'switch' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
    ];
    $links = [
        ['employee.dashboard', __('common.dashboard'), 'home'],
        ['employee.checklogs', __('employees.checkins'), 'check'],
        ['employee.payslips', __('common.payslips'), 'cash'],
        ['employee.leaves', __('employees.leave'), 'leave'],
        ['employee.overtimes', __('common.overtime'), 'clock'],
        ['employee.advance-salaries', __('employees.advance_salary_short'), 'cash'],
        ['employee.absences', __('employees.absence'), 'calendar'],
        ['employee.annual-declarations', __('annual_declarations.annual_declarations'), 'doc'],
    ];
    $by = collect($links)->keyBy(0);
    $groups = [
        ['route' => 'employee.dashboard', 'label' => __('common.dashboard')],
        ['route' => 'employee.checklogs', 'label' => __('employees.checkins')],
        ['label' => __('common.requests'), 'items' => [$by['employee.leaves'], $by['employee.overtimes'], $by['employee.advance-salaries'], $by['employee.absences']]],
        ['label' => __('common.documents'), 'items' => [$by['employee.payslips'], $by['employee.annual-declarations']]],
    ];
    $tabs = array_slice($links, 0, 4);
    $more = array_slice($links, 4);
    $moreActive = collect($more)->contains(fn ($l) => request()->routeIs($l[0] . '*')) || request()->routeIs('employee.profile');
    $svg = fn ($k, $cls = 'emp-ico') => '<svg class="' . $cls . '" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="' . $ico[$k] . '"/></svg>';
@endphp

<header class="emp-topbar emp-portal">
    <div class="emp-topbar-inner">
        <a href="{{ route('employee.dashboard') }}" wire:navigate class="emp-brand"><x-brand-logo /></a>

        <nav class="emp-links" aria-label="Employee navigation">
            @foreach($groups as $g)
            @if(isset($g['route']))
            <a href="{{ route($g['route']) }}" wire:navigate class="emp-link {{ request()->routeIs($g['route'] . '*') ? 'active' : '' }}">{{ $g['label'] }}</a>
            @else
            @php $gActive = collect($g['items'])->contains(fn ($l) => request()->routeIs($l[0] . '*')); @endphp
            <div class="emp-dd" x-data="{ o: false }" @click.outside="o = false" @keydown.escape="o = false" :class="{ 'open': o }">
                <button type="button" class="emp-link emp-dd-toggle {{ $gActive ? 'active' : '' }}" @click="o = !o" :aria-expanded="o">
                    {{ $g['label'] }}
                    <svg class="emp-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="emp-dd-menu">
                    @foreach($g['items'] as [$route, $label, $icon])
                    <a href="{{ route($route) }}" wire:navigate @click="o = false" class="emp-dd-item {{ request()->routeIs($route . '*') ? 'active' : '' }}">{!! $svg($icon, 'emp-ico emp-ico-sm') !!}<span>{{ $label }}</span></a>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach
        </nav>

        <div class="emp-actions">
            <button type="button" class="emp-pill rf-theme-toggle" onclick="rfToggleTheme()" title="{{ __('common.toggle_dark_mode') }}" aria-label="{{ __('common.toggle_dark_mode') }}"><svg class="emp-ico emp-ico-sm rf-icon-moon" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg><svg class="emp-ico emp-ico-sm rf-icon-sun" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg></button>
            <div class="emp-lang">
                <a href="{{ route('language-switcher', ['locale' => 'fr']) }}" wire:navigate class="{{ \App::isLocale('fr') ? 'on' : '' }}">FR</a>
                <a href="{{ route('language-switcher', ['locale' => 'en']) }}" wire:navigate class="{{ \App::isLocale('en') ? 'on' : '' }}">EN</a>
            </div>
            @if($user->canSwitchPortals())
            <a href="{{ route('portal.dashboard') }}" class="emp-pill d-none d-lg-inline-flex" title="Admin Portal">{!! $svg('switch', 'emp-ico emp-ico-sm') !!} Admin</a>
            @endif
            <a href="{{ route('employee.profile') }}" wire:navigate class="emp-avatar" title="{{ __('common.profile') }}">{{ $user->initials }}</a>
            <a href="{{ route('logout') }}" class="emp-pill emp-pill-danger d-none d-lg-inline-flex" title="Logout">{!! $svg('logout', 'emp-ico emp-ico-sm') !!} Logout</a>
        </div>
    </div>
</header>

<div x-data="{ open: false }" @keydown.escape.window="open = false" class="emp-tabbar-wrap">
    <div class="emp-sheet-backdrop" :class="{ 'open': open }" @click="open = false"></div>
    <div class="emp-sheet" :class="{ 'open': open }" role="dialog" aria-label="More">
        <div class="emp-sheet-handle"></div>
        <div class="emp-sheet-grid">
            @foreach($more as [$route, $label, $icon])
            <a href="{{ route($route) }}" wire:navigate @click="open = false" class="emp-sheet-item {{ request()->routeIs($route . '*') ? 'active' : '' }}">
                <span class="emp-sheet-ico">{!! $svg($icon) !!}</span><span>{{ $label }}</span>
            </a>
            @endforeach
            <a href="{{ route('employee.profile') }}" wire:navigate @click="open = false" class="emp-sheet-item {{ request()->routeIs('employee.profile') ? 'active' : '' }}">
                <span class="emp-sheet-ico">{!! $svg('user') !!}</span><span>{{ __('common.profile') }}</span>
            </a>
            @if($user->canSwitchPortals())
            <a href="{{ route('portal.dashboard') }}" class="emp-sheet-item"><span class="emp-sheet-ico">{!! $svg('switch') !!}</span><span>Admin</span></a>
            @endif
            <a href="{{ route('logout') }}" class="emp-sheet-item danger"><span class="emp-sheet-ico">{!! $svg('logout') !!}</span><span>Logout</span></a>
        </div>
    </div>

    <nav class="emp-tabbar" aria-label="Primary">
        @foreach($tabs as [$route, $label, $icon])
        <a href="{{ route($route) }}" wire:navigate class="emp-tab {{ request()->routeIs($route . '*') ? 'active' : '' }}">
            {!! $svg($icon) !!}<span>{{ $label }}</span>
        </a>
        @endforeach
        <button type="button" class="emp-tab {{ $moreActive ? 'active' : '' }}" @click="open = !open" :class="{ 'active': open }">
            {!! $svg('dots') !!}<span>{{ __('common.more') }}</span>
        </button>
    </nav>
</div>
