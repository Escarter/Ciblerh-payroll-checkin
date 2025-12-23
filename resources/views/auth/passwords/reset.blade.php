<x-layouts.app>
    <section class="bg-soft d-flex align-items-center vh-100">
        <div class="container">
            <div class="row justify-content-center form-bg-image">
                <p class="text-center">
                    <a href="{{route('login')}}" class="d-flex align-items-center justify-content-center">
                        <svg class="icon icon-xs me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                        </svg> {{__('auth.back_to_log_in')}}
                    </a>
                </p>
                <div class="col-12 d-flex align-items-center justify-content-center">
                    <div class="bg-white shadow border-0 rounded p-4 p-lg-5 w-100 fmxw-500">
                        <div class="mb-1 mt-md-0">
                            <img src='/img/logo.jpg' class="w-50 h-auto" alt=''>

                            <h1 class="h4">{{__('auth.reset_password_title')}}?</h1>
                        </div>
                        <x-form-items.form method="POST" action="{{ route('password.update') }}">
                            <input type="hidden" name="token" value="{{ $token }}">
                            <div class="mb-4">
                                <label for="email">{{__('auth.your_email')}}</label>
                                <div class="input-group">
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" readonly autofocus>

                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="password">{{__('auth.your_new_password')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon2">
                                        <svg class="icon icon-xs text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </span>
                                    <input type="password" placeholder="{{__('auth.password')}}" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                    @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="confirm_password">{{__('auth.confirm_new_password')}}</label>
                                <div class="input-group"><span class="input-group-text" id="basic-addon2">
                                        <svg class="icon icon-xs text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </span>
                                    <input type="password" placeholder="{{__('auth.confirm_new_password')}}" class="form-control" name="password_confirmation" required autocomplete="new-password">
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-secondary btn-loading">{{__('auth.reset_password_title')}}</button>
                            </div>
                        </x-form-items.form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>