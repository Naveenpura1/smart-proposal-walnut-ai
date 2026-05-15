<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.index') }}"
               class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Edit User</h2>
                <p class="mt-0.5 text-sm text-slate-500">Update name, email, or role for {{ $user->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section-sm">
        <div class="card animate-fade-up">
            <div class="card-header flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br
                            {{ $user->isAdmin() ? 'from-violet-500 to-indigo-600' : 'from-sky-400 to-cyan-500' }}
                            flex items-center justify-center text-white font-bold shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                    <p class="text-xs text-slate-400">{{ $user->email }}</p>
                </div>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.update', $user) }}"
                      class="space-y-5">
                    @csrf
                    @method('PATCH')

                    {{-- Name --}}
                    <div class="form-group">
                        <label for="name" class="form-label">Full name</label>
                        <input id="name" type="text" name="name"
                               value="{{ old('name', $user->name) }}"
                               required autocomplete="name"
                               placeholder="Jane Smith"
                               class="form-control @error('name') border-rose-400 focus:ring-rose-400 @enderror" />
                        @error('name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <label for="email" class="form-label">Email address</label>
                        <input id="email" type="email" name="email"
                               value="{{ old('email', $user->email) }}"
                               required autocomplete="email"
                               placeholder="jane@company.com"
                               class="form-control @error('email') border-rose-400 focus:ring-rose-400 @enderror" />
                        @error('email')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Role --}}
                    <div class="form-group">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role"
                                class="form-select @error('role') border-rose-400 focus:ring-rose-400 @enderror">
                            <option value="sales"  {{ old('role', $user->role) === 'sales'  ? 'selected' : '' }}>
                                Sales Rep
                            </option>
                            <option value="admin"  {{ old('role', $user->role) === 'admin'  ? 'selected' : '' }}>
                                Admin
                            </option>
                        </select>
                        <p class="form-hint">
                            <strong>Admin</strong> — full platform access, user management.<br>
                            <strong>Sales Rep</strong> — can create and manage their own proposals.
                        </p>
                        @error('role')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Self-edit warning --}}
                    @if ($user->id === auth()->id())
                        <div class="alert-warning text-xs">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            You are editing your own account. Changing your role will affect your access immediately.
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                            Save changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-app-layout>
