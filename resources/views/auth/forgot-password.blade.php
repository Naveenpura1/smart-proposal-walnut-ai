<x-guest-layout>
<<<<<<< HEAD
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
=======

    <!-- Icon -->
    <div class="w-12 h-12 bg-violet-100 rounded-2xl flex items-center justify-center mb-6">
        <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
    </div>

    <div class="mb-7">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Reset your password</h2>
        <p class="mt-1.5 text-sm text-slate-500">
            Enter your email and we'll send you a reset link if an account exists.
        </p>
    </div>

    {{-- Generic confirmation (shown for both found & not-found emails — AC-5 anti-enumeration) --}}
    @if (session('status'))
        <div class="flex items-start gap-3 px-4 py-3.5 mb-6 bg-emerald-50 border border-emerald-200
                    rounded-xl text-sm text-emerald-800">
            <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Throttle error (only error we surface — AC-23) --}}
    @if ($errors->has('email'))
        <div class="flex items-start gap-3 px-4 py-3.5 mb-6 bg-amber-50 border border-amber-200
                    rounded-xl text-sm text-amber-800">
            <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span>{{ $errors->first('email') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div class="form-group">
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                required autofocus placeholder="you@company.com" />
        </div>

        <div class="pt-1">
            <button type="submit"
                    class="w-full btn-primary py-3 text-[15px] font-bold rounded-xl tracking-wide">
                Send reset link
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </button>
        </div>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <a href="{{ route('login') }}"
           class="inline-flex items-center gap-1.5 text-sm font-semibold text-violet-600 hover:text-violet-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to sign in
        </a>
    </div>

>>>>>>> 9ad783d (Initial commit)
</x-guest-layout>
