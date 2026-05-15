<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Platform Settings</h2>
                <p class="text-sm text-slate-500 mt-0.5">Configure application-wide preferences</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section-sm space-y-5">

        {{-- Application Info --}}
        <div class="card overflow-hidden">
            <div class="card-header flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Application Information</h3>
                    <p class="text-xs text-slate-500">Read-only environment summary</p>
                </div>
            </div>
            <div class="card-body">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Application Name</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ config('app.name') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Environment</dt>
                        <dd>
                            <span class="badge {{ app()->isProduction() ? 'badge-danger' : 'badge-warning' }}">
                                {{ ucfirst(app()->environment()) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Laravel Version</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ app()->version() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">PHP Version</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ PHP_VERSION }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Debug Mode</dt>
                        <dd>
                            <span class="badge {{ config('app.debug') ? 'badge-warning' : 'badge-success' }}">
                                {{ config('app.debug') ? 'Enabled' : 'Disabled' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">App URL</dt>
                        <dd class="text-sm text-slate-600 truncate">{{ config('app.url') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Security Settings --}}
        <div class="card overflow-hidden">
            <div class="card-header flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-sky-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Security Configuration</h3>
                    <p class="text-xs text-slate-500">Active security policies</p>
                </div>
            </div>
            <div class="card-body space-y-3">
                @php
                    $policies = [
                        ['label' => 'Password minimum length',        'value' => '8 characters'],
                        ['label' => 'Password complexity',            'value' => 'Mixed case + numbers + symbols'],
                        ['label' => 'Email verification required',    'value' => 'Yes — for proposals & admin routes'],
                        ['label' => 'Role-based access control',      'value' => 'Enabled (sales / admin / super-admin)'],
                        ['label' => 'Security audit log',             'value' => 'storage/logs/security.log (90 days)'],
                        ['label' => 'Remember-me token rotation',     'value' => 'On logout & password change'],
                        ['label' => 'Proposal enumeration protection', 'value' => '404 (not 403) for foreign proposals'],
                    ];
                @endphp
                <ul class="divide-y divide-slate-100">
                    @foreach($policies as $policy)
                        <li class="flex items-start justify-between gap-4 py-3">
                            <span class="text-sm text-slate-600">{{ $policy['label'] }}</span>
                            <span class="text-sm font-semibold text-slate-800 text-right shrink-0">{{ $policy['value'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Roles Config --}}
        <div class="card overflow-hidden">
            <div class="card-header flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Role Hierarchy</h3>
                    <p class="text-xs text-slate-500">Configured in <code class="text-violet-600 font-mono">config/roles.php</code></p>
                </div>
            </div>
            <div class="card-body">
                <div class="space-y-3">
                    @foreach(config('roles.hierarchy', []) as $role => $satisfies)
                        <div class="flex items-center gap-3">
                            <span class="w-28 text-xs font-bold text-slate-700 uppercase tracking-wide shrink-0">
                                {{ $role }}
                            </span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($satisfies as $satisfied)
                                    <span class="badge badge-primary text-[11px]">{{ $satisfied }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Note --}}
        <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 flex items-start gap-3">
            <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-slate-500 leading-relaxed">
                These settings are managed via environment variables and configuration files.
                Changes to security policies, roles, or logging behaviour require a code change and deployment.
            </p>
        </div>

    </div>

</x-app-layout>
