<x-layouts.app>
    <section class="vh-lg-100 mt-4 mt-lg-0 bg-soft d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center form-bg-image">
                <p class="text-center">
                    <a href="{{route('login')}}" class="d-flex align-items-center justify-content-center">
                        <svg class="icon icon-xs me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                        </svg> {{__('Back to log in')}}
                    </a>
                </p>
                <div class="col-12 d-flex align-items-center justify-content-center">
                    <div class="signin-inner my-1 my-lg-0 bg-white shadow border-0 rounded p-5 p-lg-5 w-100 fmxw-500">
                        <div class="mb-1 mt-md-0">
                            <img src='/img/logo.jpg' class="w-50 h-auto" alt=''>

                            <h1 class="h4">{{__('Forgot your password')}}?</h1>
                        </div>
                        <p class="mb-4">{{__('Don\'t fret! Just type in your email and we will send you a code to reset your password')}}!</p>
                        <x-form-items.form method="POST" action="{{ route('password.email') }}" class="form-modal">
                            @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                            @endif
                            <div class="mb-4"><label for="email">{{__('Your Email')}}</label>
                                <div class="form-group">
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" placeholder="example@domain.com" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="d-grid"><button type="submit" class="btn btn-primary btn-loading">{{ __('Send Password Reset Link') }}</button></div>
                        </x-form-items.form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>