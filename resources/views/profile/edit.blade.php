<x-app-layout>
<<<<<<< HEAD
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
=======

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ auth()->user()->name }}</h2>
                <p class="text-sm text-slate-500 mt-0.5">{{ auth()->user()->email }} &middot; <span class="capitalize font-medium text-violet-600">{{ auth()->user()->role }}</span></p>
            </div>
        </div>
    </x-slot>

    <div class="page-section-sm space-y-5">

        <!-- Profile Information -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Profile Information</h3>
                    <p class="text-xs text-slate-500">Update your name and email address</p>
                </div>
            </div>
            <div class="px-6 py-6">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <!-- Update Password -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-sky-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Update Password</h3>
                    <p class="text-xs text-slate-500">Use a strong, unique password</p>
                </div>
            </div>
            <div class="px-6 py-6">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="bg-white rounded-2xl border border-rose-200 shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b border-rose-100 bg-rose-50/60 flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-rose-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.07 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-rose-700">Danger Zone</h3>
                    <p class="text-xs text-rose-500">These actions are irreversible</p>
                </div>
            </div>
            <div class="px-6 py-6">
                @include('profile.partials.delete-user-form')
            </div>
        </div>

    </div>

>>>>>>> 9ad783d (Initial commit)
</x-app-layout>
