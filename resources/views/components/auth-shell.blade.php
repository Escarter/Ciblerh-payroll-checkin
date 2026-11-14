@props(['title', 'subtitle' => null, 'back' => false, 'wide' => false])

@php
    $authBg = \App\Support\Branding::get('auth_bg');
    $heroKicker = \App\Support\Branding::get('auth_kicker') ?: __('auth.hero_kicker');
    $heroTitle = \App\Support\Branding::get('auth_title') ?: __('auth.hero_title');
    $heroSubtitle = \App\Support\Branding::get('auth_subtitle') ?: __('auth.hero_text');
    $heroFeatures = [
        [
            'M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z',
            \App\Support\Branding::get('auth_feature_1') ?: __('auth.feature_1'),
        ],
        [
            'M8 7V3m8 4V3M5 11h14M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z',
            \App\Support\Branding::get('auth_feature_2') ?: __('auth.feature_2'),
        ],
        [
            'M5 13l4 4L19 7',
            \App\Support\Branding::get('auth_feature_3') ?: __('auth.feature_3'),
        ],
    ];
    $heroFooter = \App\Support\Branding::get('auth_footer')
        ?: ('© ' . date('Y') . ' ' . \App\Support\Branding::get('app_name') . '. ' . __('auth.rights'));
@endphp

<style>
    @media (min-width: 992px) {
        .rf-auth-split {
            grid-template-columns: minmax(340px, 38%) minmax(0, 1fr) !important;
        }
        .rf-auth-hero {
            padding: 3.5rem 3rem 2.25rem !important;
        }
    }
    .rf-auth-form.rf-auth-form-wide {
        max-width: 640px;
    }
</style>

<div class="rf-auth-split">
    <aside
        class="rf-auth-hero"
        @if ($authBg)
            style="background: linear-gradient(155deg, color-mix(in srgb, var(--rf-brand) 78%, rgba(11, 18, 32, 0.85)) 0%, rgba(14, 17, 22, 0.88) 100%), url('{{ $authBg }}') center / cover no-repeat !important;"
        @endif
    >
        @if (! $authBg)
            <span class="rf-auth-orb rf-auth-orb-a"></span>
            <span class="rf-auth-orb rf-auth-orb-b"></span>
        @endif

        <div class="rf-auth-hero-brand">
            <x-brand-logo class="rf-auth-hero-logo" />
        </div>

        <div class="rf-auth-hero-body">
            <p class="rf-auth-kicker">{{ $heroKicker }}</p>
            <h2>{{ $heroTitle }}</h2>
            <p class="rf-auth-hero-text">{{ $heroSubtitle }}</p>
            <ul class="rf-auth-features">
                @foreach ($heroFeatures as [$path, $featureLabel])
                    @if (filled($featureLabel))
                        <li>
                            <span class="rf-auth-feat-ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
                            </span>
                            {{ $featureLabel }}
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>

        <p class="rf-auth-hero-foot">{{ $heroFooter }}</p>
    </aside>

    <section class="rf-auth-panel">
        <div class="rf-auth-tools">
            <div class="rf-auth-lang">
                <a href="{{ route('language-switcher', ['locale' => 'en']) }}" class="{{ \App::isLocale('en') ? 'on' : '' }}">EN</a>
                <a href="{{ route('language-switcher', ['locale' => 'fr']) }}" class="{{ \App::isLocale('fr') ? 'on' : '' }}">FR</a>
            </div>
            <button type="button" class="rf-auth-theme rf-theme-toggle" onclick="rfToggleTheme()" aria-label="{{ __('common.toggle_dark_mode') }}" title="{{ __('common.toggle_dark_mode') }}">
                <svg class="rf-icon-moon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                <svg class="rf-icon-sun" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg>
            </button>
        </div>

        <div class="rf-auth-form {{ $wide ? 'rf-auth-form-wide' : '' }}">
            <x-brand-logo class="rf-auth-mobile-logo" text-class="rf-auth-mobile-logo" />
            @if ($back)
                <a href="{{ route('login') }}" class="rf-auth-back">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 12H5m6-6l-6 6 6 6"/></svg>
                    {{ __('auth.back_to_log_in') }}
                </a>
            @endif
            <h1>{{ $title }}</h1>
            @if ($subtitle)<p class="rf-auth-sub">{{ $subtitle }}</p>@endif

            {{ $slot }}
        </div>
    </section>
</div>
