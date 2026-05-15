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
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
=======
        <title>{{ config('app.name', 'Smart Proposal') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900">

        {{-- ── AC-15/16/17: Idle session timeout ──────────────────────────────
             Idle threshold  : 30 minutes of inactivity
             Warning appears : 5 minutes before forced logout
             On extend       : heartbeat POST to /extend-session resets the timer
             On expiry       : auto-POST to /logout
        ─────────────────────────────────────────────────────────────────── --}}
        @auth
        <div
            x-data="{
                idleLimit:   30 * 60,
                warnBefore:   5 * 60,
                remaining:    30 * 60,
                showWarning:  false,
                timer:        null,
                countdown:    5 * 60,

                init() {
                    this.resetTimer();
                    ['mousemove','keydown','click','scroll','touchstart'].forEach(e => {
                        document.addEventListener(e, () => this.resetTimer(), { passive: true });
                    });
                },

                resetTimer() {
                    if (this.showWarning) return;   // don't reset while warning is up
                    this.remaining = this.idleLimit;
                    clearInterval(this.timer);
                    this.timer = setInterval(() => this.tick(), 1000);
                },

                tick() {
                    this.remaining--;
                    if (this.remaining <= this.warnBefore && !this.showWarning) {
                        this.showWarning = true;
                        this.countdown = this.remaining;
                    }
                    if (this.showWarning) {
                        this.countdown = this.remaining;
                    }
                    if (this.remaining <= 0) {
                        clearInterval(this.timer);
                        this.$refs.logoutForm.submit();
                    }
                },

                extend() {
                    this.showWarning = false;
                    // POST a keep-alive so the server session is also touched
                    fetch('{{ route('session.extend') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                    });
                    this.resetTimer();
                },

                formatTime(s) {
                    const m = Math.floor(s / 60);
                    const sec = s % 60;
                    return (m > 0 ? m + 'm ' : '') + sec + 's';
                },
            }"
            x-init="init()"
        >
            {{-- Hidden logout form used by the auto-logout path --}}
            <form x-ref="logoutForm" method="POST" action="{{ route('logout') }}" class="hidden">
                @csrf
            </form>

            {{-- Warning modal (AC-16: countdown prompt) --}}
            <div
                x-show="showWarning"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[9999] flex items-center justify-center px-4"
                style="display:none"
            >
                {{-- Backdrop --}}
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

                {{-- Modal card --}}
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                    {{-- Amber top strip --}}
                    <div class="h-1.5 bg-gradient-to-r from-amber-400 to-orange-400"></div>

                    <div class="p-6">
                        {{-- Icon --}}
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>

                        <h3 class="text-base font-bold text-slate-900 mb-1">Session expiring soon</h3>
                        <p class="text-sm text-slate-500 mb-4">
                            You've been inactive for a while. You will be automatically logged out in
                            <strong class="text-amber-600 font-bold tabular-nums" x-text="formatTime(countdown)"></strong>.
                        </p>

                        {{-- Actions (AC-17: extend session) --}}
                        <div class="flex gap-3">
                            <button
                                @click="extend()"
                                class="flex-1 btn-primary py-2.5 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Stay logged in
                            </button>
                            <button
                                @click="$refs.logoutForm.submit()"
                                class="flex-1 btn-secondary py-2.5 text-sm">
                                Sign out now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endauth

        <div class="min-h-screen flex flex-col">

            @include('layouts.navigation')

            {{-- AC-10: Email verification pending banner --}}
            @auth
                @if (! auth()->user()->hasVerifiedEmail())
                    <div class="bg-amber-50 border-b border-amber-200">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div class="flex items-center gap-2.5 text-sm text-amber-800">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                <span>
                                    <strong class="font-semibold">Verify your email address</strong>
                                    — some features are restricted until you confirm your email.
                                </span>
                            </div>
                            <form method="POST" action="{{ route('verification.send') }}" class="shrink-0">
                                @csrf
                                <button type="submit"
                                        class="text-xs font-semibold text-amber-700 underline underline-offset-2
                                               hover:text-amber-900 transition-colors whitespace-nowrap">
                                    Resend verification email
                                </button>
                            </form>
                        </div>
                        @if (session('status') === 'verification-link-sent')
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-2">
                                <p class="text-xs text-emerald-700 font-medium">
                                    A fresh verification link has been sent to {{ auth()->user()->email }}.
                                </p>
                            </div>
                        @endif
                    </div>
                @endif
            @endauth

            <!-- Page Heading -->
            @isset($header)
                <div class="bg-white border-b border-slate-200">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <!-- Main Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="border-t border-slate-200 bg-white mt-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                    <p class="text-xs text-slate-400">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
                    <p class="text-xs text-slate-400">Powered by Walnut AI</p>
                </div>
            </footer>
        </div>

>>>>>>> 9ad783d (Initial commit)
    </body>
</html>
