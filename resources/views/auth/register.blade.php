<x-guest-layout>
<<<<<<< HEAD
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

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
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
=======

    <div class="mb-7">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Create your account</h2>
        <p class="mt-1.5 text-sm text-slate-500">Get started — no credit card required</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        {{-- Name --}}
        <div class="form-group">
            <x-input-label for="name" value="Full name" />
            <x-text-input id="name" type="text" name="name" :value="old('name')"
                required autofocus autocomplete="name"
                placeholder="Jane Smith"
                class="{{ $errors->has('name') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        {{-- Email --}}
        <div class="form-group">
            <x-input-label for="email" value="Work email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                required autocomplete="username"
                placeholder="you@company.com"
                class="{{ $errors->has('email') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        {{-- Password --}}
        <div class="form-group">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" type="password" name="password"
                required autocomplete="new-password"
                placeholder="Min. 8 chars, uppercase, number, symbol"
                class="{{ $errors->has('password') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}" />
            {{-- AC-4: each unmet requirement is listed as a separate message --}}
            <x-input-error :messages="$errors->get('password')" />
            @if(!$errors->has('password'))
                <p class="form-hint">Must be 8+ characters with uppercase, number, and symbol.</p>
            @endif
        </div>

        {{-- Confirm Password --}}
        <div class="form-group">
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation"
                required autocomplete="new-password"
                placeholder="Repeat your password"
                class="{{ $errors->has('password_confirmation') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        {{-- Role — card-style radio buttons --}}
        <div class="form-group">
            <x-input-label value="I am joining as…" />

            <div class="grid grid-cols-2 gap-3 mt-1.5">

                {{-- Sales Rep --}}
                <label for="role_sales"
                       class="relative flex flex-col gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all duration-150
                              {{ old('role', 'sales') === 'sales'
                                  ? 'border-violet-500 bg-violet-50/60'
                                  : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50' }}"
                       x-data
                       x-on:click="
                           document.getElementById('role_sales').checked = true;
                           document.querySelectorAll('[data-role-card]').forEach(el => {
                               el.classList.remove('border-violet-500','bg-violet-50/60');
                               el.classList.add('border-slate-200','bg-white');
                           });
                           $el.classList.remove('border-slate-200','bg-white','hover:border-slate-300','hover:bg-slate-50');
                           $el.classList.add('border-violet-500','bg-violet-50/60');
                       "
                       data-role-card>
                    <input type="radio" id="role_sales" name="role" value="sales"
                           class="sr-only"
                           {{ old('role', 'sales') === 'sales' ? 'checked' : '' }}>
                    <div class="flex items-start justify-between">
                        <div class="w-9 h-9 rounded-xl bg-violet-100 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-violet-600" style="width:18px;height:18px;"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        {{-- Check indicator --}}
                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5
                                    {{ old('role', 'sales') === 'sales' ? 'border-violet-500 bg-violet-500' : 'border-slate-300' }}">
                            @if(old('role', 'sales') === 'sales')
                                <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Sales Rep</p>
                        <p class="text-xs text-slate-400 mt-0.5">Create & manage proposals</p>
                    </div>
                </label>

                {{-- Admin --}}
                <label for="role_admin"
                       class="relative flex flex-col gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all duration-150
                              {{ old('role') === 'admin'
                                  ? 'border-violet-500 bg-violet-50/60'
                                  : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50' }}"
                       x-data
                       x-on:click="
                           document.getElementById('role_admin').checked = true;
                           document.querySelectorAll('[data-role-card]').forEach(el => {
                               el.classList.remove('border-violet-500','bg-violet-50/60');
                               el.classList.add('border-slate-200','bg-white');
                           });
                           $el.classList.remove('border-slate-200','bg-white','hover:border-slate-300','hover:bg-slate-50');
                           $el.classList.add('border-violet-500','bg-violet-50/60');
                       "
                       data-role-card>
                    <input type="radio" id="role_admin" name="role" value="admin"
                           class="sr-only"
                           {{ old('role') === 'admin' ? 'checked' : '' }}>
                    <div class="flex items-start justify-between">
                        <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-indigo-600" style="width:18px;height:18px;"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5
                                    {{ old('role') === 'admin' ? 'border-violet-500 bg-violet-500' : 'border-slate-300' }}">
                            @if(old('role') === 'admin')
                                <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Admin</p>
                        <p class="text-xs text-slate-400 mt-0.5">Full platform access</p>
                    </div>
                </label>
            </div>

            <x-input-error :messages="$errors->get('role')" />
        </div>

        {{-- Submit --}}
        <div class="pt-1">
            <button type="submit"
                    class="w-full btn-primary py-3 text-[15px] font-bold rounded-xl shadow-lg hover:shadow-violet-200 transition-all duration-150">
                Create account
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </button>
        </div>
    </form>

    {{-- Login link --}}
    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-sm text-slate-500">
            Already have an account?
            <a href="{{ route('login') }}"
               class="font-semibold text-violet-600 hover:text-violet-800 ml-0.5 transition-colors">
                Sign in
            </a>
        </p>
    </div>

>>>>>>> 9ad783d (Initial commit)
</x-guest-layout>
