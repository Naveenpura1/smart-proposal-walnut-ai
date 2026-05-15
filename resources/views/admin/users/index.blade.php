<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">User Management</h2>
                <p class="mt-0.5 text-sm text-slate-500">Manage accounts and roles across the platform</p>
            </div>
            <span class="badge-primary text-xs px-3 py-1">Admin Only</span>
        </div>
    </x-slot>

    <div class="page-section space-y-6">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert-success animate-fade-up">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert-error animate-fade-up">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Search & filter bar --}}
        <form method="GET" action="{{ route('admin.users.index') }}"
              class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <span class="input-prefix">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                    </svg>
                </span>
                <input type="text" name="search" value="{{ $search ?? '' }}"
                       placeholder="Search by name or email…"
                       class="form-control input-has-prefix" />
            </div>

            <select name="role" class="form-select w-full sm:w-40">
                <option value="">All roles</option>
                <option value="admin"  {{ ($role ?? '') === 'admin'  ? 'selected' : '' }}>Admin</option>
                <option value="sales"  {{ ($role ?? '') === 'sales'  ? 'selected' : '' }}>Sales</option>
            </select>

            <button type="submit" class="btn-primary shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm3 6a1 1 0 011-1h10a1 1 0 010 2H7a1 1 0 01-1-1zm3 6a1 1 0 011-1h4a1 1 0 010 2h-4a1 1 0 01-1-1z"/>
                </svg>
                Filter
            </button>

            @if ($search || $role)
                <a href="{{ route('admin.users.index') }}" class="btn-secondary shrink-0">Clear</a>
            @endif
        </form>

        {{-- Users table --}}
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">All Users</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $users->total() }} user{{ $users->total() !== 1 ? 's' : '' }} found</p>
                </div>
            </div>

            @if ($users->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <p class="empty-title">No users found</p>
                    <p class="empty-subtitle">Try adjusting your search or filter criteria</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="{{ $user->id === auth()->id() ? 'bg-violet-50/40' : '' }}">
                                    {{-- Avatar + name/email --}}
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br
                                                        {{ $user->isAdmin() ? 'from-violet-500 to-indigo-600' : 'from-sky-400 to-cyan-500' }}
                                                        flex items-center justify-center text-white text-sm font-bold shadow-sm shrink-0">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-slate-800 truncate">
                                                    {{ $user->name }}
                                                    @if ($user->id === auth()->id())
                                                        <span class="ml-1 text-xs font-normal text-violet-500">(you)</span>
                                                    @endif
                                                </p>
                                                <p class="text-xs text-slate-400 truncate">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Role badge --}}
                                    <td>
                                        @if ($user->isAdmin())
                                            <span class="badge-primary">Admin</span>
                                        @else
                                            <span class="badge-info">Sales</span>
                                        @endif
                                    </td>

                                    {{-- Joined date --}}
                                    <td class="text-slate-500 text-sm">
                                        {{ $user->created_at->format('M j, Y') }}
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="btn-secondary btn-sm">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Edit
                                            </a>

                                            @if ($user->id !== auth()->id())
                                                <form method="POST"
                                                      action="{{ route('admin.users.destroy', $user) }}"
                                                      x-data
                                                      @submit.prevent="if(confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')) $el.submit()">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger btn-sm">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($users->hasPages())
                    <div class="card-footer">
                        {{ $users->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>

</x-app-layout>
