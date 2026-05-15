<section>
<<<<<<< HEAD
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
=======
    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <div class="form-group">
            <x-input-label for="update_password_current_password" value="Current password" />
            <x-text-input id="update_password_current_password" name="current_password"
                type="password" autocomplete="current-password"
                placeholder="Your current password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="form-group">
                <x-input-label for="update_password_password" value="New password" />
                <x-text-input id="update_password_password" name="password"
                    type="password" autocomplete="new-password"
                    placeholder="Min. 8 characters" />
                <x-input-error :messages="$errors->updatePassword->get('password')" />
            </div>

            <div class="form-group">
                <x-input-label for="update_password_password_confirmation" value="Confirm new password" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation"
                    type="password" autocomplete="new-password"
                    placeholder="Repeat new password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <x-primary-button>Update password</x-primary-button>

            @if (session('status') === 'password-updated')
                <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                      class="flex items-center gap-1.5 text-sm text-emerald-600 font-semibold">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Password updated
                </span>
>>>>>>> 9ad783d (Initial commit)
            @endif
        </div>
    </form>
</section>
