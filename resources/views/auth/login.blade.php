<x-guest-layout>
<<<<<<< HEAD
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
=======

    <div class="mb-7">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Welcome back</h2>
        <p class="mt-1.5 text-sm text-slate-500">Sign in to your account to continue</p>
    </div>

    {{-- Session status (e.g. password reset success) --}}
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        {{-- Email --}}
        <div class="form-group">
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                required autofocus autocomplete="username"
                placeholder="you@company.com"
                class="{{ $errors->has('email') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        {{-- Password --}}
        <div class="form-group">
            <div class="flex items-center justify-between mb-1.5">
                <x-input-label for="password" value="Password" class="!mb-0" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs font-semibold text-violet-600 hover:text-violet-800 transition-colors">
                        Forgot password?
                    </a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password"
                required autocomplete="current-password"
                placeholder="••••••••••"
                class="{{ $errors->has('email') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        {{-- Remember me --}}
        <label class="flex items-center gap-2.5 cursor-pointer select-none group">
            <div class="relative">
                <input id="remember_me" type="checkbox" name="remember" class="sr-only peer">
                <div class="w-4 h-4 border-2 border-slate-300 rounded
                            peer-checked:bg-violet-600 peer-checked:border-violet-600
                            transition-all group-hover:border-violet-400"></div>
                <svg class="absolute inset-0 w-4 h-4 text-white hidden peer-checked:block pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <span class="text-sm text-slate-600">Remember me</span>
        </label>

        {{-- Submit --}}
        <div class="pt-1">
            <button type="submit"
                    class="w-full btn-primary py-3 text-[15px] font-bold rounded-xl shadow-lg hover:shadow-violet-200 transition-all duration-150">
                Sign in
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </button>
        </div>
    </form>

    {{-- Register link --}}
    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-sm text-slate-500">
            Don't have an account?
            <a href="{{ route('register') }}"
               class="font-semibold text-violet-600 hover:text-violet-800 ml-0.5 transition-colors">
                Create one free
            </a>
        </p>
    </div>

>>>>>>> 9ad783d (Initial commit)
</x-guest-layout>
