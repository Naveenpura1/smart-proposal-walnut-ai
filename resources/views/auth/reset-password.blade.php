<x-guest-layout>
<<<<<<< HEAD
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
=======

    <!-- Icon -->
    <div class="w-12 h-12 bg-violet-100 rounded-2xl flex items-center justify-center mb-6">
        <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>

    {{-- AC-11: Expired / already-used token — show a clear error with new-link CTA --}}
    @if ($errors->has('email') && str_contains($errors->first('email'), 'token'))
        <div class="mb-6">
            <div class="flex items-start gap-3 px-4 py-4 bg-rose-50 border border-rose-200 rounded-xl text-sm text-rose-800">
                <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div>
                    <p class="font-semibold mb-1">This reset link has expired or has already been used.</p>
                    <p class="text-rose-600 text-xs">Password reset links are valid for 60 minutes and can only be used once.</p>
                </div>
            </div>
            <a href="{{ route('password.request') }}"
               class="mt-3 w-full btn-secondary py-2.5 text-sm rounded-xl flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Request a new reset link
            </a>
        </div>
    @else

        <div class="mb-7">
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Choose a new password</h2>
            <p class="mt-1.5 text-sm text-slate-500">
                Enter a strong new password for your account.
            </p>
        </div>

        {{-- Password complexity hint --}}
        <div class="flex items-start gap-2.5 px-3.5 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500 mb-5">
            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Must be at least 8 characters with uppercase, lowercase, a number, and a symbol.</span>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf

            <!-- Hidden token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email -->
            <div class="form-group">
                <x-input-label for="email" value="Email address" />
                <x-text-input id="email" type="email" name="email"
                    :value="old('email', $request->email)"
                    required autofocus autocomplete="username"
                    placeholder="you@company.com" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <!-- New Password -->
            <div class="form-group">
                <x-input-label for="password" value="New password" />
                <x-text-input id="password" type="password" name="password"
                    required autocomplete="new-password"
                    placeholder="Min. 8 characters" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <x-input-label for="password_confirmation" value="Confirm new password" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation"
                    required autocomplete="new-password"
                    placeholder="Repeat your new password" />
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <!-- Submit -->
            <div class="pt-1">
                <button type="submit"
                        class="w-full btn-primary py-3 text-[15px] font-bold rounded-xl tracking-wide
                               shadow-lg hover:shadow-violet-200 transition-all duration-150">
                    Reset password
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </button>
            </div>
        </form>

    @endif

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
