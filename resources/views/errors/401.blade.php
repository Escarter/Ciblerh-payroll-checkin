<x-errors.layout
    :code="401"
    :title="__('errors.401_title')"
    :subtitle="__('errors.401_message')"
    :back="false"
>
    <div class="rf-error-action-stack">
        <a href="{{ route('login') }}" class="btn rf-auth-submit text-decoration-none">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            <span>{{ __('auth.login') }}</span>
        </a>

        <a href="{{ url('/') }}" class="btn rf-auth-secondary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span>{{ __('errors.go_home') }}</span>
        </a>
    </div>
</x-errors.layout>
