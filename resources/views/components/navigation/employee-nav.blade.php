 @auth
 <div class=" d-flex align-items-center mb-4">
     <div class="d-flex align-items-end justify-content-end text-gray-500 mt-1 mx-1">
         <a class=" {{ \App::isLocale('fr') ? ' text-secondary' : ''}} px-1" href=" {{route('language-switcher',['locale'=>'fr'])}}">{{__('FR')}}</a> |
         <a class="{{ \App::isLocale('en') ? ' text-secondary' : ''}} px-1" href="{{route('language-switcher',['locale'=>'en'])}}">{{__('EN')}}</a>
     </div>
     <a href='{{route("employee.profile")}}' class='text-gray-500'>
         <svg class="icon icon-sm me-1 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
         </svg>
     </a>

     <!-- <a href='' class='text-gray-500'>
                            <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </a> -->
     <!-- <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded-circle bg-secondary text-gray-600"><span class=" text-gray-50 p-2">{{auth()->user()->initials}}</span></div> -->

     <a class="mx-2 text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout').submit();">
         <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
         </svg>
     </a>
     <form id="logout" action="{{ route('logout') }}" method="POST" class="d-none">
         @csrf
     </form>
 </div>
 @endauth