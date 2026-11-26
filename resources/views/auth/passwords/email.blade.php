<x-layouts.app>
    <x-auth-shell :title="__('auth.forgot_password') . '?'" :subtitle="__('auth.forgot_password_description')" :back="true">
        <x-form-items.form method="POST" action="{{ route('password.email') }}" class="rf-auth-fields form-modal">
            @if (session('status'))
                <div class="alert alert-success" role="alert">{{ session('status') }}</div>
            @endif

            <div class="rf-field">
                <label for="email">{{ __('auth.your_email') }}</label>
                <div class="rf-input @error('email') is-invalid @enderror">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email" autofocus>
                </div>
                @error('email')<div class="rf-error">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="rf-auth-submit btn-loading">{{ __('auth.send_password_reset_link') }}</button>
        </x-form-items.form>
    </x-auth-shell>
</x-layouts.app>
