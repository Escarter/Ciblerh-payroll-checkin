<x-layouts.app>
    <x-auth-shell :title="__('auth.reset_password_title')" :back="true">
        <x-form-items.form method="POST" action="{{ route('password.update') }}" class="rf-auth-fields">
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="rf-field">
                <label for="email">{{ __('auth.your_email') }}</label>
                <div class="rf-input @error('email') is-invalid @enderror">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                    <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" readonly>
                </div>
                @error('email')<div class="rf-error">{{ $message }}</div>@enderror
            </div>

            @foreach ([['password', 'auth.your_new_password', true], ['password_confirmation', 'auth.confirm_new_password', false]] as [$name, $label, $showErr])
                <div class="rf-field">
                    <label for="{{ $name }}">{{ __($label) }}</label>
                    <div class="rf-input @if($showErr) @error('password') is-invalid @enderror @endif">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                        <input id="{{ $name }}" type="password" name="{{ $name }}" placeholder="{{ __($label) }}" required autocomplete="new-password">
                    </div>
                    @if ($showErr)@error('password')<div class="rf-error">{{ $message }}</div>@enderror @endif
                </div>
            @endforeach

            <button type="submit" class="rf-auth-submit btn-loading">{{ __('auth.reset_password_title') }}</button>
        </x-form-items.form>
    </x-auth-shell>
</x-layouts.app>
