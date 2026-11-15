<x-errors.layout
    :code="429"
    :title="__('errors.429_title')"
    :subtitle="__('errors.429_message')"
    :back="false"
>
    <div class="rf-error-action-stack">
        <a href="javascript:window.location.reload()" class="btn rf-auth-submit text-decoration-none">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
            </svg>
            <span>{{ __('errors.refresh_page') }}</span>
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
