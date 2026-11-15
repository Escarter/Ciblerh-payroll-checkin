<x-errors.layout
    :code="404"
    :title="__('errors.404_title')"
    :subtitle="__('errors.404_message')"
    :back="false"
>
    <div class="rf-error-action-stack">
        <a href="{{ url('/') }}" class="btn rf-auth-submit text-decoration-none">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span>{{ __('errors.go_home') }}</span>
        </a>

        <a href="javascript:history.back()" class="btn rf-auth-secondary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>{{ __('errors.go_back') }}</span>
        </a>
    </div>
</x-errors.layout>
