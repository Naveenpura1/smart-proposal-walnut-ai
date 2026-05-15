<<<<<<< HEAD
<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <!-- Admin Links -->
                    @if(Auth::user()->role === 'admin')
                        <x-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')">
                            {{ __('System Settings') }}
                        </x-nav-link>
                        <!-- Example link for User Management -->
                        <x-nav-link href="#" :active="false">
                            {{ __('Manage Users') }}
                        </x-nav-link>
                    @endif

                    <!-- Sales Rep Links -->
                    @if(Auth::user()->role === 'sales')
                        <x-nav-link :href="route('proposals.create')" :active="request()->routeIs('proposals.create')">
                            {{ __('New Proposal') }}
                        </x-nav-link>
                        <x-nav-link href="#" :active="false">
                            {{ __('My Proposals') }}
=======
<nav x-data="{ open: false }" class="bg-white border-b border-slate-100 sticky top-0 z-40">

    @php $user = auth()->user(); @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-[60px]">

            {{-- Brand --}}
            <div class="flex items-center gap-7">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 shrink-0">
                    <div class="w-8 h-8 bg-gradient-to-br from-violet-600 to-indigo-600 rounded-lg
                                flex items-center justify-center shadow-sm">
                        <svg class="text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             style="width:17px;height:17px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                  d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <span class="font-bold text-slate-800 text-[15px] tracking-tight hidden sm:block">
                        {{ config('app.name') }}
                    </span>
                </a>

                {{-- Desktop nav --}}
                <div class="hidden sm:flex items-center gap-0.5">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Home
                    </x-nav-link>

                    @if($user?->isAdmin())
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Users
                        </x-nav-link>
                        <x-nav-link :href="route('admin.sessions.index')" :active="request()->routeIs('admin.sessions.*')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Sessions
                        </x-nav-link>
                        <x-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Settings
                        </x-nav-link>
                    @endif

                    @if($user?->isSales())
                        <x-nav-link :href="route('proposals.index')"
                                    :active="request()->routeIs('proposals.index') || request()->routeIs('proposals.show') || request()->routeIs('proposals.edit') || request()->routeIs('proposals.create')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Proposals
>>>>>>> 9ad783d (Initial commit)
                        </x-nav-link>
                    @endif
                </div>
            </div>

<<<<<<< HEAD
            <!-- Settings Dropdown (Remains the same) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }} <span class="text-xs text-gray-400">({{ ucfirst(Auth::user()->role) }})</span></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
=======
            {{-- Right side --}}
            <div class="hidden sm:flex items-center gap-2">

                {{-- New proposal CTA (sales only) --}}
                @if($user?->isSales())
                    <a href="{{ route('proposals.create') }}"
                       class="btn-primary btn-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New
                    </a>
                @endif

                {{-- User menu --}}
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 pl-1 pr-2.5 py-1.5 rounded-xl
                                       border border-slate-200 bg-white hover:bg-slate-50
                                       hover:border-slate-300 transition-all duration-150 group">
                            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600
                                        flex items-center justify-center text-white text-xs font-bold">
                                {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="text-left hidden md:block">
                                <div class="text-[13px] font-semibold text-slate-800 leading-tight">{{ $user?->name }}</div>
                                <div class="text-[11px] text-slate-400 leading-tight">{{ ucfirst($user?->role ?? '') }}</div>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
>>>>>>> 9ad783d (Initial commit)
                        </button>
                    </x-slot>

                    <x-slot name="content">
<<<<<<< HEAD
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
=======
                        <div class="px-4 py-3 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600
                                            flex items-center justify-center text-white text-sm font-bold shrink-0">
                                    {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-800 truncate">{{ $user?->name }}</div>
                                    <div class="text-xs text-slate-400 truncate">{{ $user?->email }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="py-1">
                            <x-dropdown-link :href="route('profile.edit')">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Profile Settings
                            </x-dropdown-link>
                        </div>
                        <div class="border-t border-slate-100 py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="!text-rose-600 hover:!bg-rose-50">
                                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Sign Out
                                </x-dropdown-link>
                            </form>
                        </div>
>>>>>>> 9ad783d (Initial commit)
                    </x-slot>
                </x-dropdown>
            </div>

<<<<<<< HEAD
            <!-- Hamburger (Mobile) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (Mobile) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if(Auth::user()->role === 'admin')
                <x-responsive-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')">
                    {{ __('System Settings') }}
                </x-responsive-nav-link>
            @endif

            @if(Auth::user()->role === 'sales')
                <x-responsive-nav-link :href="route('proposals.create')" :active="request()->routeIs('proposals.create')">
                    {{ __('New Proposal') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
=======
            {{-- Mobile hamburger --}}
            <button @click="open = !open"
                    class="sm:hidden w-9 h-9 flex items-center justify-center rounded-lg
                           text-slate-500 hover:bg-slate-100 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path :class="{'hidden': open}"  class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path :class="{'hidden': !open}" class="hidden"      stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden border-t border-slate-100 bg-white">
        <div class="px-3 py-2 space-y-0.5">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Home</x-responsive-nav-link>
            @if($user?->isAdmin())
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Users</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.sessions.index')" :active="request()->routeIs('admin.sessions.*')">Sessions</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')">Settings</x-responsive-nav-link>
            @endif
            @if($user?->isSales())
                <x-responsive-nav-link :href="route('proposals.index')" :active="request()->routeIs('proposals.*')">Proposals</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('proposals.create')" :active="request()->routeIs('proposals.create')">New Proposal</x-responsive-nav-link>
            @endif
        </div>
        <div class="border-t border-slate-100 px-3 py-2">
            <div class="flex items-center gap-3 px-3 py-2.5 mb-1">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600
                            flex items-center justify-center text-white font-bold text-sm shrink-0">
                    {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-800">{{ $user?->name }}</div>
                    <div class="text-xs text-slate-400">{{ $user?->email }}</div>
                </div>
            </div>
            <x-responsive-nav-link :href="route('profile.edit')">Profile Settings</x-responsive-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault(); this.closest('form').submit();">Sign Out</x-responsive-nav-link>
            </form>
        </div>
    </div>

</nav>
>>>>>>> 9ad783d (Initial commit)
