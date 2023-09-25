<nav class="navbar navbar-top navbar-expand navbar-dashboard navbar-primary text-white ps-0 pe-1 pb-0">
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between w-100" id="navbarSupportedContent">
            <div class="d-flex align-items-center">
                <button id="sidebar-toggle" class="sidebar-toggle me-3 btn btn-icon-only d-none d-lg-inline-block align-items-center justify-content-center"><svg class="toggle-icon" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>

            <ul class="navbar-nav align-items-center">
                <div class="d-flex align-items-end justify-content-end text-gray-500 mt-1 mx-3">
                    <a class=" {{ \App::isLocale('fr') ? ' text-secondary' : ''}} px-1" href=" {{route('language-switcher',['locale'=>'fr'])}}">{{__('FR')}}</a> |
                    <a class="{{ \App::isLocale('en') ? ' text-secondary' : ''}} px-1" href="{{route('language-switcher',['locale'=>'en'])}}">{{__('EN')}}</a>
                </div>
                <li class="nav-item dropdown ms-lg-3">
                    <a class="nav-link dropdown-toggle pt-1 px-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @auth
                        <div class="media d-flex align-items-center">
                            <div class="avatar-md d-flex align-items-center justify-content-center fw-bold rounded bg-primary text-gray-50"><span class=" p-2">{{auth()->user()->initials}}</span></div>

                        </div>
                        @endauth
                    </a>
                    <div class="dropdown-menu dashboard-dropdown dropdown-menu-end mt-2 py-1">
                        <a class="dropdown-item d-flex align-items-center text-gray-800" href="{{route('portal.profile-setting')}}">
                            <svg class="dropdown-icon text-gray-400 me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"></path>
                            </svg> {{__('Profile Settings')}}
                        </a>
                        <div role="separator" class="dropdown-divider my-1"></div>
                        <a class="dropdown-item d-flex align-items-center text-gray-800" href="{{ route('logout') }}" onclick="event.preventDefault();
                        document.getElementById('logout').submit();">
                            <svg class="dropdown-icon text-danger me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            {{__('Logout')}}
                        </a>
                        <form id="logout" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>