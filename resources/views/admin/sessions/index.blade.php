<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-sky-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Active Sessions</h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        {{ $totalActive }} active session{{ $totalActive !== 1 ? 's' : '' }} across all users
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary btn-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Users
            </a>
        </div>
    </x-slot>

    <div class="page-section space-y-5">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert-success">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert-error">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Notice --}}
        <div class="rounded-2xl border border-sky-200 bg-sky-50/50 p-4 flex items-start gap-3">
            <svg class="w-4 h-4 text-sky-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-sky-700 leading-relaxed">
                Sessions are considered active if they had activity within the last
                <strong>{{ config('session.lifetime', 120) }} minutes</strong>.
                Revoking a session forces that device to re-authenticate immediately.
            </p>
        </div>

        {{-- Sessions table --}}
        <div class="card overflow-hidden">

            @if ($sessions->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <p class="empty-title">No active sessions</p>
                    <p class="empty-subtitle">All sessions have expired or been revoked.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Device / Browser</th>
                                <th>Last Active</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $session)
                                @php
                                    $isCurrentSession = $session->session_id === session()->getId();
                                    $lastActivity     = \Carbon\Carbon::createFromTimestamp($session->last_activity);
                                    $minutesAgo       = $lastActivity->diffInMinutes(now());
                                    $isRecent         = $minutesAgo < 5;

                                    // Parse a human-readable browser/device hint from user-agent
                                    $ua = $session->user_agent ?? '';
                                    $browser = 'Unknown';
                                    if (str_contains($ua, 'Firefox'))       $browser = 'Firefox';
                                    elseif (str_contains($ua, 'Edg'))       $browser = 'Edge';
                                    elseif (str_contains($ua, 'Chrome'))    $browser = 'Chrome';
                                    elseif (str_contains($ua, 'Safari'))    $browser = 'Safari';
                                    elseif (str_contains($ua, 'curl'))      $browser = 'cURL';
                                    elseif (str_contains($ua, 'Postman'))   $browser = 'Postman';

                                    $device = 'Desktop';
                                    if (str_contains($ua, 'Mobile') || str_contains($ua, 'Android')) $device = 'Mobile';
                                    elseif (str_contains($ua, 'Tablet') || str_contains($ua, 'iPad')) $device = 'Tablet';
                                @endphp
                                <tr class="{{ $isCurrentSession ? 'bg-violet-50/40' : 'hover:bg-slate-50/60' }} transition-colors">
                                    <td>
                                        @if ($session->user_id)
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-violet-400 to-indigo-500
                                                            flex items-center justify-center text-white text-xs font-bold shrink-0">
                                                    {{ strtoupper(substr($session->user_name ?? '?', 0, 1)) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-slate-800 truncate">
                                                        {{ $session->user_name }}
                                                        @if ($isCurrentSession)
                                                            <span class="ml-1 badge badge-primary text-[10px] py-0.5">You</span>
                                                        @endif
                                                    </p>
                                                    <p class="text-xs text-slate-400 truncate">{{ $session->user_email }}</p>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Guest / Unauthenticated</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-mono text-xs text-slate-600">
                                            {{ $session->ip_address ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <p class="text-sm text-slate-700">{{ $browser }} &middot; {{ $device }}</p>
                                            <p class="text-xs text-slate-400 truncate max-w-[200px]" title="{{ $ua }}">
                                                {{ \Illuminate\Support\Str::limit($ua, 40) }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <p class="text-sm text-slate-700">{{ $lastActivity->diffForHumans() }}</p>
                                            <p class="text-xs text-slate-400">{{ $lastActivity->format('d M Y, H:i') }}</p>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($isCurrentSession)
                                            <span class="badge badge-success">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Current
                                            </span>
                                        @elseif ($isRecent)
                                            <span class="badge badge-info">
                                                <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                                                Online
                                            </span>
                                        @else
                                            <span class="badge badge-gray">Idle</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if (! $isCurrentSession)
                                            <form method="POST"
                                                  action="{{ route('admin.sessions.destroy', $session->session_id) }}"
                                                  onsubmit="return confirm('Revoke this session? That device will need to log in again.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn-danger btn-sm">
                                                    Revoke
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($sessions->hasPages())
                    <div class="card-footer">
                        {{ $sessions->links() }}
                    </div>
                @endif
            @endif
        </div>

        {{-- Revoke-all by user (quick panel) --}}
        @if ($sessions->isNotEmpty())
            @php
                $usersWithSessions = $sessions->getCollection()
                    ->filter(fn ($s) => $s->user_id && $s->session_id !== session()->getId())
                    ->groupBy('user_id')
                    ->map(fn ($group) => $group->first());
            @endphp

            @if ($usersWithSessions->isNotEmpty())
                <div class="card overflow-hidden">
                    <div class="card-header flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-rose-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Revoke All Sessions by User</h3>
                            <p class="text-xs text-slate-500">Signs a user out from every device at once</p>
                        </div>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($usersWithSessions as $userId => $sessionRow)
                            <li class="flex items-center justify-between px-6 py-3 gap-4">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-violet-400 to-indigo-500
                                                flex items-center justify-center text-white text-xs font-bold shrink-0">
                                        {{ strtoupper(substr($sessionRow->user_name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $sessionRow->user_name }}</p>
                                        <p class="text-xs text-slate-400 truncate">{{ $sessionRow->user_email }}</p>
                                    </div>
                                </div>
                                <form method="POST"
                                      action="{{ route('admin.sessions.destroyForUser', $userId) }}"
                                      onsubmit="return confirm('Sign {{ $sessionRow->user_name }} out from ALL devices?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm">
                                        Revoke all
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif

    </div>

</x-app-layout>
