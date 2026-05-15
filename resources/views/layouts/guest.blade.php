<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
<<<<<<< HEAD

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
=======
        <title>{{ config('app.name', 'Smart Proposal') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">

        <div class="min-h-screen flex">

            <!-- ── Left panel (brand) ───────────────────────────── -->
            <div class="hidden lg:flex lg:w-[45%] xl:w-[42%] flex-col relative overflow-hidden
                        bg-gradient-to-br from-violet-700 via-violet-600 to-indigo-600">

                <!-- Pattern overlay -->
                <div class="absolute inset-0 opacity-10"
                     style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 32px 32px;"></div>

                <!-- Floating blobs -->
                <div class="absolute -top-20 -left-20 w-72 h-72 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-64 h-64 rounded-full bg-indigo-400/20 blur-2xl"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 rounded-full bg-violet-400/10 blur-3xl"></div>

                <!-- Content -->
                <div class="relative z-10 flex flex-col h-full px-10 py-10">

                    <!-- Logo -->
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm border border-white/20">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="text-white font-bold text-lg tracking-tight">{{ config('app.name') }}</span>
                    </div>

                    <!-- Hero text -->
                    <div class="flex-1 flex flex-col justify-center">
                        <div class="mb-6 inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm border border-white/20 rounded-full px-4 py-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="text-white/90 text-xs font-medium">AI-Powered Proposals</span>
                        </div>

                        <h1 class="text-4xl xl:text-5xl font-extrabold text-white leading-[1.15] tracking-tight mb-5">
                            Win more deals<br/>
                            <span class="text-violet-200">with smarter</span><br/>
                            proposals
                        </h1>

                        <p class="text-white/70 text-base leading-relaxed max-w-sm mb-8">
                            Generate compelling, personalized proposals in seconds using the power of AI. Save time. Close faster.
                        </p>

                        <!-- Feature pills -->
                        <div class="flex flex-wrap gap-2.5">
                            @foreach(['✦ Auto-generated content','✦ Client-tailored','✦ Instant delivery'] as $feat)
                                <span class="text-xs font-medium text-white/80 bg-white/10 backdrop-blur-sm border border-white/15 rounded-full px-3.5 py-1.5">
                                    {{ $feat }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Testimonial / quote -->
                    <div class="mt-auto bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl p-5">
                        <p class="text-white/85 text-sm leading-relaxed italic mb-3">
                            "Smart Proposal cut our proposal time from 3 hours to 10 minutes. Our close rate jumped 40%."
                        </p>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-violet-300/40 flex items-center justify-center text-white text-xs font-bold">S</div>
                            <div>
                                <div class="text-white text-xs font-semibold">Sarah Chen</div>
                                <div class="text-white/60 text-xs">VP Sales, TechFlow Inc.</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── Right panel (form) ───────────────────────────── -->
            <div class="flex-1 flex flex-col items-center justify-center px-6 py-10 bg-slate-50 min-h-screen">

                <!-- Mobile logo -->
                <div class="lg:hidden flex items-center gap-2 mb-8">
                    <div class="w-9 h-9 bg-violet-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <span class="font-bold text-slate-800 text-lg tracking-tight">{{ config('app.name') }}</span>
                </div>

                <!-- Form card -->
                <div class="w-full max-w-md">
                    <div class="bg-white rounded-2xl shadow-card-md border border-slate-200/80 p-8">
                        {{ $slot }}
                    </div>
                    <p class="mt-5 text-center text-xs text-slate-400">
                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </p>
                </div>

            </div>
        </div>

>>>>>>> 9ad783d (Initial commit)
    </body>
</html>
