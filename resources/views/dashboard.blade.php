<x-app-layout>
<<<<<<< HEAD
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
=======

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Good {{ now()->format('G') < 12 ? 'morning' : (now()->format('G') < 17 ? 'afternoon' : 'evening') }},
                    {{ auth()->user()->name }}
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    @if(auth()->user()->isAdmin())
                        Platform overview — manage users and monitor activity.
                    @else
                        Here's what's happening with your proposals today.
                    @endif
                </p>
            </div>
            @if(auth()->user()->isSales() && auth()->user()->hasVerifiedEmail())
                <a href="{{ route('proposals.create') }}" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Proposal
                </a>
            @endif
        </div>
    </x-slot>

    <div class="page-section space-y-6">

        {{-- ═══════════════════════════════════════════════════════
             ADMIN DASHBOARD
        ═══════════════════════════════════════════════════════ --}}
        @if(auth()->user()->isAdmin())

            {{-- WB-030: Admin analytics summary KPIs --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" data-testid="admin-kpi-cards">
                <div class="stat-card" data-testid="admin-kpi-total">
                    <div class="stat-icon bg-violet-100">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-value">{{ number_format($adminStats['total_proposals']) }}</p>
                        <p class="stat-label">Total Proposals</p>
                    </div>
                </div>

                <div class="stat-card" data-testid="admin-kpi-open-rate">
                    <div class="stat-icon bg-sky-100">
                        <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-value">{{ $adminStats['open_rate'] }}<span class="text-base font-semibold text-slate-400">%</span></p>
                        <p class="stat-label">Open Rate</p>
                    </div>
                </div>

                <div class="stat-card" data-testid="admin-kpi-accepted-rate">
                    <div class="stat-icon bg-emerald-100">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-value">{{ $adminStats['accepted_rate'] }}<span class="text-base font-semibold text-slate-400">%</span></p>
                        <p class="stat-label">Accepted Rate</p>
                    </div>
                </div>

                <div class="stat-card" data-testid="admin-kpi-views">
                    <div class="stat-icon bg-amber-100">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-value">{{ number_format($adminStats['total_views']) }}</p>
                        <p class="stat-label">Total Views</p>
                    </div>
                </div>
            </div>

            {{-- Quick-action cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                <a href="{{ route('admin.users.index') }}"
                   class="card-hover p-5 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-violet-100 flex items-center justify-center shrink-0
                                group-hover:bg-violet-200 transition-colors">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-violet-700 transition-colors">
                            Manage Users
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">View, edit roles, and remove accounts</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-violet-400 ml-auto transition-colors"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ route('admin.sessions.index') }}"
                   class="card-hover p-5 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-sky-100 flex items-center justify-center shrink-0
                                group-hover:bg-sky-200 transition-colors">
                        <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-sky-700 transition-colors">
                            Active Sessions
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">View and revoke user sessions</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-sky-400 ml-auto transition-colors"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ route('admin.settings') }}"
                   class="card-hover p-5 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center shrink-0
                                group-hover:bg-slate-200 transition-colors">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-slate-900 transition-colors">
                            Platform Settings
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">Configure application preferences</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-400 ml-auto transition-colors"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ route('profile.edit') }}"
                   class="card-hover p-5 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-sky-100 flex items-center justify-center shrink-0
                                group-hover:bg-sky-200 transition-colors">
                        <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-sky-700 transition-colors">
                            My Profile
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">Update name, email, and password</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-sky-400 ml-auto transition-colors"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- WB-030: Analytics quick-action --}}
                <a href="{{ route('admin.analytics') }}"
                   class="card-hover p-5 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center shrink-0
                                group-hover:bg-amber-200 transition-colors">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-amber-700 transition-colors">
                            Analytics
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">Open rates, rep performance, proposal breakdown</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-amber-400 ml-auto transition-colors"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

            </div>

            {{-- Admin info banner --}}
            <div class="rounded-2xl border border-violet-200 bg-violet-50/50 p-5 flex items-start gap-4">
                <div class="w-9 h-9 rounded-xl bg-violet-100 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-violet-900">You are signed in as Administrator</p>
                    <p class="text-xs text-violet-700 mt-1 leading-relaxed">
                        As an admin you have full access to user management and platform settings.
                        Proposal creation and management are features available to Sales Reps.
                    </p>
                </div>
            </div>

        {{-- ═══════════════════════════════════════════════════════
             SALES REP DASHBOARD
        ═══════════════════════════════════════════════════════ --}}
        @else

            {{-- Stat cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stat-card">
                    <div class="stat-icon bg-violet-100">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-value">{{ $stats['total'] }}</p>
                        <p class="stat-label">Total</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-slate-100">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-value">{{ $stats['draft'] }}</p>
                        <p class="stat-label">Drafts</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-sky-100">
                        <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-value">{{ $stats['sent'] }}</p>
                        <p class="stat-label">Sent</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-emerald-100">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-value">{{ $stats['accepted'] }}</p>
                        <p class="stat-label">Accepted</p>
                    </div>
                </div>
            </div>

            {{-- Main grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Recent proposals --}}
                <div class="lg:col-span-2 card overflow-hidden">
                    <div class="card-header flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-800">Recent Proposals</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Your most recently updated proposals</p>
                        </div>
                        @if($stats['total'] > 0 && auth()->user()->hasVerifiedEmail())
                            <a href="{{ route('proposals.index') }}"
                               class="text-xs font-semibold text-violet-600 hover:text-violet-800 transition-colors">
                                View all →
                            </a>
                        @endif
                    </div>

                    @if($recent->isEmpty())
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <p class="empty-title">No proposals yet</p>
                            <p class="empty-subtitle">Create your first AI-powered proposal to get started.</p>
                            @if(auth()->user()->hasVerifiedEmail())
                                <a href="{{ route('proposals.create') }}" class="btn-primary btn-sm mt-5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Create Proposal
                                </a>
                            @endif
                        </div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach($recent as $proposal)
                                @php
                                    $badgeClass = match($proposal->status) {
                                        'Sent'     => 'badge-info',
                                        'Accepted' => 'badge-success',
                                        default    => 'badge-gray',
                                    };
                                    $dotColor = match($proposal->status) {
                                        'Sent'     => 'bg-sky-400',
                                        'Accepted' => 'bg-emerald-500',
                                        default    => 'bg-slate-300',
                                    };
                                @endphp
                                <li>
                                    <a href="{{ route('proposals.show', $proposal) }}"
                                       class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50/60 group transition-colors">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-100 to-indigo-100
                                                    flex items-center justify-center text-violet-600 font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($proposal->client_name, 0, 2)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-slate-800 truncate group-hover:text-violet-700 transition-colors">
                                                {{ $proposal->client_name }}
                                            </p>
                                            <p class="text-xs text-slate-400 truncate">{{ $proposal->industry }}</p>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <span class="{{ $badgeClass }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                                {{ $proposal->status }}
                                            </span>
                                            <span class="text-xs text-slate-400 hidden sm:block w-20 text-right">
                                                {{ $proposal->updated_at->diffForHumans(null, true) }} ago
                                            </span>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-300 group-hover:text-violet-400 shrink-0 -mr-1 transition-colors"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        @if($stats['total'] > 5)
                            <div class="card-footer">
                                <a href="{{ route('proposals.index') }}"
                                   class="text-xs font-semibold text-violet-600 hover:text-violet-800 transition-colors">
                                    View all {{ $stats['total'] }} proposals →
                                </a>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="space-y-4">

                    {{-- Quick actions --}}
                    <div class="card overflow-hidden">
                        <div class="card-header">
                            <h3 class="text-sm font-semibold text-slate-800">Quick Actions</h3>
                        </div>
                        <div class="p-2">
                            @if(auth()->user()->hasVerifiedEmail())
                                <a href="{{ route('proposals.create') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-violet-50 group transition-colors">
                                    <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center
                                                group-hover:bg-violet-200 transition-colors shrink-0">
                                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-700 group-hover:text-violet-700 transition-colors">
                                            New Proposal
                                        </p>
                                        <p class="text-xs text-slate-400">Generate with Walnut AI</p>
                                    </div>
                                </a>
                                <a href="{{ route('proposals.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-50 group transition-colors">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center
                                                group-hover:bg-slate-200 transition-colors shrink-0">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-700">All Proposals</p>
                                        <p class="text-xs text-slate-400">Browse & filter</p>
                                    </div>
                                </a>
                            @endif
                            <a href="{{ route('profile.edit') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-50 group transition-colors">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center
                                            group-hover:bg-slate-200 transition-colors shrink-0">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Profile Settings</p>
                                    <p class="text-xs text-slate-400">Update your info</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    {{-- AI tip --}}
                    <div class="rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 p-5 text-white relative overflow-hidden">
                        <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 rounded-full bg-indigo-400/20 blur-2xl pointer-events-none"></div>
                        <div class="relative">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold mb-1">AI Tip</p>
                            <p class="text-xs text-white/75 leading-relaxed">
                                Add detailed pain points to your proposals for more personalised, compelling AI-generated content.
                            </p>
                        </div>
                    </div>

                </div>
            </div>

        @endif

    </div>

>>>>>>> 9ad783d (Initial commit)
</x-app-layout>
