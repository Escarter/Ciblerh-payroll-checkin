 @auth
 <div class=" d-flex align-items-center mb-4">
     <div class="d-flex align-items-end justify-content-end text-gray-500 mt-1 mx-1">
         <a class=" {{ \App::isLocale('fr') ? ' text-secondary' : ''}} px-1" href=" {{route('language-switcher',['locale'=>'fr'])}}" wire:nagivate>{{__('FR')}}</a> |
         <a class="{{ \App::isLocale('en') ? ' text-secondary' : ''}} px-1" href="{{route('language-switcher',['locale'=>'en'])}}" wire:nagivate>{{__('EN')}}</a>
     </div>
     <a href='{{route("employee.profile")}}' wire:nagivate class='text-gray-500'>
         <svg class="icon icon-sm me-1 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
         </svg>
     </a>
     <a class="mx-2" href="{{route('logout')}}" wire:nagivate>
         <svg class="icon icon-sm me-1 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
         </svg>
     </a>
 </div>
 @endauth