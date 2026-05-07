<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create New Proposal') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 p-6 shadow sm:rounded-lg">
                <form action="{{ route('proposals.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="client_name" value="Client Name" />
                        <x-text-input id="client_name" name="client_name" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="industry" value="Industry" />
                        <x-text-input id="industry" name="industry" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="deal_size" value="Deal Size ($)" />
                        <x-text-input type="number" id="deal_size" name="deal_size" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="pain_points" value="Pain Points" />
                        <textarea id="pain_points" name="pain_points" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" rows="4" required></textarea>
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>
                            {{ __('Generate with Walnut AI') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>