<x-guest-layout>
<<<<<<< HEAD
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
=======

    <!-- Icon -->
    <div class="w-12 h-12 bg-amber-100 rounded-2xl flex items-center justify-center mb-6">
        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>

    <div class="mb-7">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Confirm your identity</h2>
        <p class="mt-1.5 text-sm text-slate-500">
            This is a secure area. Please confirm your password to continue.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div class="form-group">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" type="password" name="password"
                required autocomplete="current-password"
                placeholder="••••••••••" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="pt-1">
            <button type="submit"
                    class="w-full btn-primary btn-full py-3 text-[15px] font-bold rounded-xl tracking-wide">
                Confirm & Continue
            </button>
        </div>
    </form>

>>>>>>> 9ad783d (Initial commit)
</x-guest-layout>
