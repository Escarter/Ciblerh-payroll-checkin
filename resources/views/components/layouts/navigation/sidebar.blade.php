@inject('request', 'Illuminate\Http\Request')
<nav id="sidebarMenu" class="sidebar d-lg-block bg-primary text-white collapse" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" style="height: auto; overflow: auto;">
                    <div class="simplebar-content" style="padding: 0px;">
                        <div class="sidebar-inner px-4 pt-3">
                            <div class="user-card d-flex d-md-none justify-content-between justify-content-md-center pb-4">
                                @auth
                                <div class="d-flex align-items-center">
                                    <div class="avatar-md me-3 d-flex align-items-center justify-content-center fw-bold rounded bg-gray-50 shadow "><span class="p-2 text-secondary ">{{auth()->user()->initials}}</span></div>
                                    <div class="d-block ">
                                        <h2 class="h5 mb-0">{{auth()->user()->first_name}}</h2>
                                        <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            <svg class="icon icon-xs dropdown-icon text-danger me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            {{__('Logout')}}
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </div>
                                @endauth
                                <div class="collapse-close d-md-none"><a href="#sidebarMenu" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="true" aria-label="Toggle navigation"><svg class="icon icon-xs" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg></a></div>
                            </div>
                            <div class="d-flex-row justify-content-center align-items-center text-center">
                                <div class="mt-2 mb-1 text-start">
                                    <span class="ml-0 lead "><span class="bg-white px-1 border rounded text-secondary display-4">{{ __('Admin') }}</span>
                                        <span class="display-4">{{ __('Portal') }}</span></span>
                                    <!-- <img src="{{ asset('img/logo.png') }}" class="rounded" id="fullLogo" alt="SofiCam"> -->
                                    <!-- <img src="{{ asset('img/fav.jpeg') }}" class="rounded d-none" id="smallLogo" alt="SofiCam"> -->
                                </div>
                            </div>
                            
                            <ul class="nav flex-column pt-3 pt-md-0">
                                <li class="nav-item mt-3 {{ $request->routeIs('portal.dashboard.*') ? 'active' : '' }}">
                                    <a href="{{route('portal.dashboard')}}"  class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon  text-gary-50">
                                                <svg class="icon icon-sm me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                                                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Dashboard')}}</span>
                                        </span>
                                    </a>
                                </li>
                                <li role="separator" class="dropdown-divider mt-2 mb-2 border-gray-600"></li>

                                @can('company-read')
                                <li class="nav-item {{ $request->routeIs('portal.companies.*') || $request->routeIs('portal.departments.index') || $request->routeIs('portal.employees.index')  || $request->routeIs('portal.services.index')   ? 'active' : '' }}">
                                    <a href="{{route('portal.companies.index')}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Companies')}}</span>
                                        </span>
                                    </a>
                                </li>
                                @endcan

                                @hasrole('supervisor')
                                <li class="nav-item {{ $request->routeIs('portal.all-employees.*') ? 'active' : '' }}">
                                    <a href="{{route('portal.employees.index',['company_uuid'=>auth()->user()->company_id])}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Employees')}}</span>
                                        </span>
                                    </a>
                                </li>
                                @else
                                <li class="nav-item {{ $request->routeIs('portal.all-employees.*') ? 'active' : '' }}">
                                    <a href="{{route('portal.all-employees')}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Employees')}}</span>
                                        </span>
                                    </a>
                                </li>
                                @endhasrole
                                @canany('ticking-read','overtime-read')
                                <li role="separator" class="dropdown-divider mt-2 mb-2 border-gray-600"></li>
                                @can('ticking-read')
                                <li class="nav-item {{ $request->routeIs('portal.checklogs.*') ? 'active' : '' }}">
                                    <a href="{{route('portal.checklogs.index')}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Checkins')}}</span>
                                        </span>
                                    </a>
                                </li>
                                @endcan
                                @can('overtime-read')
                                <li class="nav-item {{ $request->routeIs('portal.overtimes.*') ? 'active' : '' }}">
                                    <a href="{{route('portal.overtimes.index')}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Overtimes')}}</span>
                                        </span>
                                    </a>
                                </li>
                                @endcan
                                @endcanany
                                @canany('advance_salary-read','absence-read')
                                <li role="separator" class="dropdown-divider mt-2 mb-2 border-gray-600"></li>
                                @can('advance_salary-read')
                                <li class="nav-item {{ $request->routeIs('portal.advance-salaries.*') ? 'active' : '' }}">
                                    <a href="{{route('portal.advance-salaries.index')}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Advance Salaries')}}</span>
                                        </span>
                                    </a>
                                </li>
                                @endcan
                                @can('absence-read')
                                <li class="nav-item {{ $request->routeIs('portal.absences.*') ? 'active' : '' }}">
                                    <a href="{{route('portal.absences.index')}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Absences')}}</span>
                                        </span>
                                    </a>
                                </li>
                                @endcan
                                @endcanany
                                @canany('payslip-read','payslip-sending')
                                <li role="separator" class="dropdown-divider mt-2 mb-2 border-gray-600"></li>
                                @can('payslip-sending')
                                <li class="nav-item {{ $request->routeIs('portal.payslips.index') ? 'active' : '' }}">
                                    <a href="{{route('portal.payslips.index')}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Send Payslips')}}</span>
                                        </span>
                                    </a>
                                </li>
                                @endcan
                                @can('payslip-read')
                                <li class="nav-item {{ $request->routeIs('portal.payslips.history') ? 'active' : '' }}">
                                    <a href="{{route('portal.payslips.history')}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                                </svg>

                                            </span>
                                            <span class="sidebar-text">{{__('Payslip History')}}</span>
                                        </span>
                                    </a>
                                </li>
                                @endcan

                                @endcanany
                                @canany('leave-read','leave-type-read')
                                <li role="separator" class="dropdown-divider mt-2 mb-2 border-gray-600"></li>
                                @can('leave-read')
                                <li class="nav-item {{ $request->routeIs('portal.leaves.index') ? 'active' : '' }}">
                                    <a href="{{route('portal.leaves.index')}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Requested Leaves')}}</span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ $request->routeIs('portal.leaves.types') ? 'active' : '' }}">
                                    <a href="{{route('portal.leaves.types')}}" wire:navigate class="nav-link d-flex align-items-center justify-content-between">
                                        <span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z" />
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Leave Types')}}</span>
                                        </span>
                                    </a>
                                </li>
                                @endcan
                                @endcanany

                                @canany('report-payslip-read','report-checkin-read')
                                <li role="separator" class="dropdown-divider mt-2 mb-2 border-gray-600"></li>
                                <li class="nav-item">
                                    <span class="nav-link d-flex justify-content-between align-items-center {{ $request->routeIs('portal.reports.*') ? '' : 'collapsed' }} " data-bs-toggle="collapse" data-bs-target="#submenu-user" aria-expanded="{{ $request->routeIs('portal.reports.*') ? 'true' : 'false' }}"><span>
                                            <span class="sidebar-icon">
                                                <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                            </span>
                                            <span class="sidebar-text">{{__('Reports')}}</span> </span>
                                        <span class="link-arrow">
                                            <svg class="icon icon-sm" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    </span>
                                    <div class="multi-level {{ $request->routeIs('portal.reports.*') || $request->routeIs('portal.download-jobs.*') ? '' : 'collapse' }}" role="list" id="submenu-user" aria-expanded="false">
                                        <ul class="flex-column nav gap-0">
                                            <li class="nav-item {{ $request->routeIs('portal.reports.checklogs') ? 'active' : '' }}">
                                                <a href="{{route('portal.reports.checklogs')}}" wire:navigate class="nav-link ">
                                                    <span class="sidebar-text-contracted">C</span>
                                                    <span class="sidebar-text">{{__('Checkins')}}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ $request->routeIs('portal.reports.overtime') ? 'active' : '' }}">
                                                <a href="{{route('portal.reports.overtime')}}" wire:navigate class="nav-link">
                                                    <span class="sidebar-text-contracted">O</span><span class="sidebar-text">{{__('Overtime')}}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ $request->routeIs('portal.reports.payslip') ? 'active' : '' }}">
                                                <a href="{{route('portal.reports.payslip')}}" wire:navigate class="nav-link">
                                                    <span class="sidebar-text-contracted">P</span><span class="sidebar-text">{{__('Payslips')}}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ $request->routeIs('portal.download-jobs.*') ? 'active' : '' }}">
                                                <a href="{{route('portal.download-jobs.index')}}" class="nav-link">
                                                    <span class="sidebar-text-contracted">G</span><span class="sidebar-text">{{__('Generate')}}</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                                @endcanany
                                <li role="separator" class="dropdown-divider mt-2 mb-2 border-gray-600"></li>
                                @can('role-read')
                                <li class="nav-item {{ $request->routeIs('portal.roles.*') ? 'active' : '' }}">
                                    <a href="{{ route('portal.roles.index') }}" wire:navigate class="nav-link">
                                        <span class="sidebar-icon">
                                            <svg class="icon icon-sm me-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z" />
                                            </svg>
                                        </span>
                                        <span class="sidebar-text">{{ __('Roles & Permissions') }}</span>
                                    </a>
                                </li>
                                @endcan
                                @can('setting-read')
                                <li class="nav-item {{ $request->routeIs('portal.settings.*') ? 'active' : '' }}">
                                    <a href="{{ route('portal.settings.index') }}" wire:navigate class="nav-link">
                                        <span class="sidebar-icon">
                                            <svg class="icon icon-sm me-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>
                                        <span class="sidebar-text">{{ __('Settings') }}</span>
                                    </a>
                                </li>
                                @endcan
                                <li class="nav-item {{ $request->routeIs('portal.auditlogs.*') ? 'active' : '' }}">
                                    <a href="{{route('portal.auditlogs.index')}}" wire:navigate class="nav-link">
                                        <span class="sidebar-icon">
                                            <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                            </svg>
                                        </span>
                                        <span class="sidebar-text">{{__('Audit Logs')}}</span>
                                    </a>
                                </li>
                                <!-- <li role="separator" class="dropdown-divider mt-2 mb-2 border-gray-600"></li>
                                <li class="nav-item">
                                    <a href="#" target="_blank" class="nav-link d-flex align-items-center">
                                        <span class="sidebar-icon">
                                            <svg class="icon icon-sm me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                        <span class="sidebar-text">{{__('Support')}}
                                            <span class="badge badge-md bg-secondary ms-1 text-gray-50">v0.1</span>
                                        </span>
                                    </a>
                                </li> -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 0px; height: 0px;"></div>
    </div>
    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
    </div>
    <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="height: 0px; transform: translate3d(0px, 0px, 0px); display: none;"></div>
    </div>
</nav>