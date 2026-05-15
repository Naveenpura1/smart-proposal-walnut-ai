<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied &mdash; {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-slate-50 min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-md">

        {{-- Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-card-md overflow-hidden">

            {{-- Red top bar --}}
            <div class="h-1.5 bg-gradient-to-r from-rose-500 to-pink-500"></div>

            <div class="p-8">

                {{-- Icon --}}
                <div class="w-14 h-14 rounded-2xl bg-rose-50 flex items-center justify-center mb-5">
                    <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>

                {{-- Heading --}}
                <h1 class="text-xl font-extrabold text-slate-900 tracking-tight mb-1">Access Denied</h1>
                <p class="text-sm text-slate-500 mb-6">
                    You don't have permission to view this page.
                </p>

                {{-- Details table (AC-13: current role + attempted action) --}}
                @auth
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 divide-y divide-slate-100 mb-6 text-sm">
                        <div class="flex items-center justify-between px-4 py-3">
                            <span class="text-slate-500 font-medium">Your role</span>
                            <span class="font-semibold text-slate-800 capitalize">
                                {{ auth()->user()->role ?? 'unknown' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between px-4 py-3">
                            <span class="text-slate-500 font-medium">Attempted URL</span>
                            <span class="font-mono text-xs text-slate-600 truncate max-w-[180px]">
                                /{{ request()->path() }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between px-4 py-3">
                            <span class="text-slate-500 font-medium">HTTP method</span>
                            <span class="font-semibold text-slate-800">{{ request()->method() }}</span>
                        </div>
                    </div>
                @endauth

                {{-- Actions --}}
                <div class="flex flex-col gap-3">
                    @auth
                        @php
                            $homeRoute = config(
                                'roles.home_routes.' . (auth()->user()->role ?? 'default'),
                                config('roles.home_routes.default', 'dashboard')
                            );
                        @endphp
                        <a href="{{ route($homeRoute) }}"
                           class="w-full btn-primary py-2.5 text-sm font-bold rounded-xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Go to my home page
                        </a>
                        <button onclick="history.back()"
                                class="w-full btn-secondary py-2.5 text-sm rounded-xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Go back
                        </button>
                    @else
                        <a href="{{ route('login') }}"
                           class="w-full btn-primary py-2.5 text-sm font-bold rounded-xl">
                            Sign in to continue
                        </a>
                    @endauth
                </div>

            </div>
        </div>

        {{-- Brand footer --}}
        <div class="mt-5 text-center">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-600 transition-colors">
                <div class="w-5 h-5 bg-gradient-to-br from-violet-600 to-indigo-600 rounded flex items-center justify-center">
                    <svg class="text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:11px;height:11px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                {{ config('app.name') }}
            </a>
        </div>

    </div>

</body>
</html>
