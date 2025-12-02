<x-layouts.app>
    <main class="pt-5" wire:ignore>
        <section class="d-flex align-items-center my-5 py-5 mt-lg-6 mb-lg-5">
            <div class="container">
                <div class="row justify-content-center form-bg-image" data-background-lg="{{asset('img/illustrations/signin.svg')}}">

                    <div class="col-12 d-flex align-items-center justify-content-center ">
                        <div class="bg-white shadow-soft border rounded border-light px-4 pt-3 pb-4 px-lg-5  pt-lg-3  pb-lg-5  w-100 fmxw-500">

                            <div class=" mb-2 mt-md-0 text-center">
                                <img src='/img/logo.jpg' class="w-75 h-auto" alt=''>
                                <!-- <div class="mb-0  py-3"> <span class="text-xl fs-3 fw-bold">{{ __('CibeRH')}}</span> <span class="text-primary h3 bg-secondary p-2 rounded">{{__('HR')}}</span></div> -->
                            </div>
                            <x-form-items.form method="POST" action="{{ route('login') }}" class="mt-1 form-modal needs-validation" id="">
                                <div class='text-center'>
                                    <x-alert />
                                </div>
                                <div class="form-group mb-4"><label for="email">{{ __('E-Mail Address') }}</label>
                                    <div class="input-group ">
                                        <span class="input-group-text" id="basic-addon1">
                                            <!-- <span class="fas fa-envelope"></span> -->
                                            <svg class="icon icon-xs text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                            </svg>
                                        </span>
                                        <input type="email" name="email" class="form-control  @error('email') is-invalid @enderror " value="{{ old('email') }}" placeholder="example@company.com" id="email" autofocus="" required="" style="background-image: url(&quot;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAASCAYAAABSO15qAAAAAXNSR0IArs4c6QAAAPhJREFUOBHlU70KgzAQPlMhEvoQTg6OPoOjT+JWOnRqkUKHgqWP4OQbOPokTk6OTkVULNSLVc62oJmbIdzd95NcuGjX2/3YVI/Ts+t0WLE2ut5xsQ0O+90F6UxFjAI8qNcEGONia08e6MNONYwCS7EQAizLmtGUDEzTBNd1fxsYhjEBnHPQNG3KKTYV34F8ec/zwHEciOMYyrIE3/ehKAqIoggo9inGXKmFXwbyBkmSQJqmUNe15IRhCG3byphitm1/eUzDM4qR0TTNjEixGdAnSi3keS5vSk2UDKqqgizLqB4YzvassiKhGtZ/jDMtLOnHz7TE+yf8BaDZXA509yeBAAAAAElFTkSuQmCC&quot;); background-repeat: no-repeat; background-attachment: scroll; background-size: 16px 18px; background-position: 98% 50%;">
                                    </div>
                                    @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <div class="form-group mb-4">
                                        <label for="password">{{ __('Password') }}</label>
                                        <div class="input-group">
                                            <span class="input-group-text" id="basic-addon2">
                                                <!-- <span class="fas fa-unlock-alt"></span> -->
                                                <svg class="icon icon-xs text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                                </svg>

                                            </span>
                                            <input type="password" name="password" placeholder="Password" class="form-control  @error('password') is-invalid @enderror " id="password" required="" style="background-image: url(&quot;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAASCAYAAABSO15qAAAAAXNSR0IArs4c6QAAAPhJREFUOBHlU70KgzAQPlMhEvoQTg6OPoOjT+JWOnRqkUKHgqWP4OQbOPokTk6OTkVULNSLVc62oJmbIdzd95NcuGjX2/3YVI/Ts+t0WLE2ut5xsQ0O+90F6UxFjAI8qNcEGONia08e6MNONYwCS7EQAizLmtGUDEzTBNd1fxsYhjEBnHPQNG3KKTYV34F8ec/zwHEciOMYyrIE3/ehKAqIoggo9inGXKmFXwbyBkmSQJqmUNe15IRhCG3byphitm1/eUzDM4qR0TTNjEixGdAnSi3keS5vSk2UDKqqgizLqB4YzvassiKhGtZ/jDMtLOnHz7TE+yf8BaDZXA509yeBAAAAAElFTkSuQmCC&quot;); background-repeat: no-repeat; background-attachment: scroll; background-size: 16px 18px; background-position: 98% 50%;">
                                        </div>
                                        @error('password')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="d-flex justify-content-between align-items-top mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <label class="form-check-label mb-0" for="remember">{{ __('Remember Me') }}</label>
                                        </div>
                                        <div>
                                            @if (Route::has('password.request'))
                                            <a href="{{ route('password.request') }}" class="small text-right">
                                                {{ __('Lost Password?') }}
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-loading px-6"> {{ __('Login') }}</button>
                                </div>
                            </x-form-items.form>
                            <div class='d-flex justify-content-center my-3'>
                                <!-- {{__('Language')}} <br> -->
                                <a class="{{ \App::isLocale('fr') ? ' text-secondary' : ''}} mx-2" href="{{route('language-switcher',['locale'=>'fr'])}}" >{{__('FR')}}</a> |
                                <a class="{{ \App::isLocale('en') ? ' text-secondary' : ''}} mx-2" href="{{route('language-switcher',['locale'=>'en'])}}" >{{__('EN')}}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

</x-layouts.app>