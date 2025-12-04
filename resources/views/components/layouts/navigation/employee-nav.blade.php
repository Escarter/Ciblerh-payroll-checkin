 @auth
 <div class="d-flex align-items-center">
     {{-- Language Switcher --}}
     <div class="d-flex align-items-center text-gray-500 me-2 me-md-3">
         <a class="btn btn-sm {{ \App::isLocale('fr') ? 'btn-outline-secondary' : 'btn-outline-light' }} rounded-start border-0 px-1 px-md-2"
             href="{{route('language-switcher',['locale'=>'fr'])}}" wire:navigate
             title="Français">
             <span class="d-inline d-md-none">🇫🇷</span>
             <span class="d-none d-md-inline">FR</span>
         </a>
         <a class="btn btn-sm {{ \App::isLocale('en') ? 'btn-outline-secondary' : 'btn-outline-light' }} rounded-end border-0 px-1 px-md-2"
             href="{{route('language-switcher',['locale'=>'en'])}}" wire:navigate
             title="English">
             <span class="d-inline d-md-none">🇺🇸</span>
             <span class="d-none d-md-inline">EN</span>
         </a>
     </div>

     {{-- Portal Switcher for users with both admin/supervisor/manager AND employee roles --}}
     @if(auth()->user()->canSwitchPortals())
     <div class="me-2 me-md-3">
         <div class="btn-group btn-group-sm" role="group" aria-label="Portal Switcher">
             <a href="{{ route('portal.dashboard') }}"
                 class="btn btn-sm {{ request()->routeIs('portal.*') ? 'btn-secondary' : 'btn-outline-secondary' }} rounded-start px-1 px-md-2"
                 title="Admin Portal">
                 <svg class="icon icon-xs {{ request()->routeIs('portal.*') ? '' : 'me-md-1' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                 </svg>
                 <span class="d-none d-md-inline">Admin</span>
             </a>
             <a href="{{ route('employee.dashboard') }}"
                 class="btn btn-sm {{ request()->routeIs('employee.*') ? 'btn-secondary' : 'btn-outline-secondary' }} rounded-end px-1 px-md-2"
                 title="Employee Portal">
                 <svg class="icon icon-xs {{ request()->routeIs('employee.*') ? '' : 'me-md-1' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                 </svg>
                 <span class="d-none d-md-inline">Employee</span>
             </a>
         </div>
     </div>
     @endif



     {{-- User Actions --}}
     <div class="d-flex align-items-center">
         <a href='{{route("employee.profile")}}' wire:navigate
             class='btn btn-outline-primary btn-sm me-1 me-md-2 px-1 px-md-2'
             title="Profile">
             <svg class="icon icon-xs me-md-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
             </svg>
             <span class="d-none d-md-inline">Profile</span>
         </a>
         <a class="btn btn-outline-danger btn-sm px-1 px-md-2" href="{{route('logout')}}" wire:navigate
             title="Logout">
             <svg class="icon icon-xs me-md-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
             </svg>
             <span class="d-none d-md-inline">Logout</span>
         </a>
     </div>
 </div>
 @endauth