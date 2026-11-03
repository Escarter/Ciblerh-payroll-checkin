<x-layouts.app>
    <main wire:ignore>
        <x-auth-shell :title="__('auth.welcome_back')" :subtitle="__('auth.welcome_sub')">
            <x-form-items.form method="POST" action="{{ route('login') }}" class="rf-auth-fields form-modal needs-validation" id="">
                <x-alert />

                <div class="rf-field">
                    <label for="email">{{ __('auth.e_mail_address') }}</label>
                    <div class="rf-input @error('email') is-invalid @enderror">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" autofocus required>
                    </div>
                    @error('email')<div class="rf-error">{{ $message }}</div>@enderror
                </div>

                <div class="rf-field">
                    <label for="password">{{ __('auth.password_label') }}</label>
                    <div class="rf-input @error('password') is-invalid @enderror">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                        <input type="password" name="password" id="password" placeholder="{{ __('auth.password_placeholder') }}" autocomplete="current-password" required>
                        <button type="button" class="rf-eye" onclick="var i=this.parentNode.querySelector('input');i.type=i.type==='password'?'text':'password'" aria-label="{{ __('auth.show_password') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password')<div class="rf-error">{{ $message }}</div>@enderror
                </div>

                <div class="rf-auth-row">
                    <label class="rf-check" for="remember">
                        <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <span>{{ __('auth.remember_me') }}</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="rf-auth-link">{{ __('auth.lost_password') }}</a>
                    @endif
                </div>

                <button type="submit" class="rf-auth-submit btn-loading">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                    {{ __('auth.login') }}
                </button>
            </x-form-items.form>
        </x-auth-shell>
    </main>
</x-layouts.app>
