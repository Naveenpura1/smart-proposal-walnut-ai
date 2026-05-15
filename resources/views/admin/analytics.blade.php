<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Platform Analytics</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Platform-wide proposal performance across all sales reps
                    {{-- AC-21: last-updated timestamp --}}
                    <span class="text-slate-400">· as of {{ $lastUpdatedAt->format('M j, Y g:i a') }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                {{-- AC-19: Export rep performance CSV --}}
                <a href="{{ route('admin.analytics.export.reps', request()->query()) }}"
                   class="btn-secondary btn-sm" data-testid="export-reps-btn">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Reps CSV
                </a>
                {{-- AC-18: Export proposal breakdown CSV --}}
                <a href="{{ route('admin.analytics.export.proposals', request()->query()) }}"
                   class="btn-secondary btn-sm" data-testid="export-proposals-btn">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Proposals CSV
                </a>
            </div>
        </div>
    </x-slot>

    <div class="page-section space-y-6">

        {{-- ═══════════════════════════════════════════════════════════════════
             AC-4/5: DATE-RANGE FILTER BAR
        ═══════════════════════════════════════════════════════════════════ --}}
        <form method="GET" action="{{ route('admin.analytics') }}"
              class="card p-4 flex flex-wrap items-end gap-3"
              data-testid="date-filter-form">

            {{-- Preserve existing proposal/rep filters while changing date --}}
            @foreach(['sort','direction','filter_status','filter_rep','rep_sort','rep_dir','rep_search'] as $carry)
                @if(request($carry))
                    <input type="hidden" name="{{ $carry }}" value="{{ request($carry) }}">
                @endif
            @endforeach

            <div>
                <label class="form-label text-xs" for="date_preset">Period</label>
                <select id="date_preset" name="date_preset"
                        class="form-control text-sm py-1.5"
                        onchange="this.form.submit()"
                        data-testid="date-preset-select">
                    <option value="all"    {{ $datePreset === 'all'    ? 'selected' : '' }}>All Time</option>
                    <option value="7d"     {{ $datePreset === '7d'     ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30d"    {{ $datePreset === '30d'    ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90d"    {{ $datePreset === '90d'    ? 'selected' : '' }}>Last 90 Days</option>
                    <option value="custom" {{ $datePreset === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>

            @if($datePreset === 'custom')
                <div>
                    <label class="form-label text-xs" for="date_from">From</label>
                    <input id="date_from" name="date_from" type="date"
                           value="{{ request('date_from') }}"
                           class="form-control text-sm py-1.5"
                           data-testid="date-from-input">
                </div>
                <div>
                    <label class="form-label text-xs" for="date_to">To</label>
                    <input id="date_to" name="date_to" type="date"
                           value="{{ request('date_to') }}"
                           class="form-control text-sm py-1.5"
                           data-testid="date-to-input">
                </div>
            @endif

            <button type="submit" class="btn-primary btn-sm py-1.5">Apply</button>

            @if($datePreset !== 'all')
                <a href="{{ route('admin.analytics') }}"
                   class="btn-ghost btn-sm text-slate-400 py-1.5">Clear</a>
            @endif
        </form>

        {{-- ═══════════════════════════════════════════════════════════════════
             AC-2/3: HEADLINE KPI CARDS
        ═══════════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" data-testid="kpi-cards">

            <div class="stat-card" data-testid="kpi-total">
                <div class="stat-icon bg-violet-100">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="stat-value">{{ number_format($totalProposals) }}</p>
                    <p class="stat-label">Total Proposals</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $totalDraft }} draft · {{ $totalSent }} sent</p>
                </div>
            </div>

            <div class="stat-card" data-testid="kpi-conversion">
                <div class="stat-icon bg-emerald-100">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="stat-value">{{ $conversionRate }}<span class="text-base font-semibold text-slate-400">%</span></p>
                    <p class="stat-label">Conversion Rate</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $totalAccepted }} of {{ $totalSent }} sent</p>
                </div>
            </div>

            <div class="stat-card" data-testid="kpi-open-rate">
                <div class="stat-icon bg-sky-100">
                    <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="stat-value">{{ $openRate }}<span class="text-base font-semibold text-slate-400">%</span></p>
                    <p class="stat-label">Open Rate</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $totalViews }} total views</p>
                </div>
            </div>

            <div class="stat-card" data-testid="kpi-deal-value">
                <div class="stat-icon bg-amber-100">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="stat-value text-lg">${{ number_format($totalDealValue, 0) }}</p>
                    <p class="stat-label">Total Deal Value</p>
                    @if($avgDaysToAccept !== null)
                        <p class="text-[10px] text-slate-400 mt-0.5">Avg {{ $avgDaysToAccept }}d to accept</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════
             AC-6/7/8: REP PERFORMANCE TABLE
        ═══════════════════════════════════════════════════════════════════ --}}
        <div class="card overflow-hidden" data-testid="rep-table">

            {{-- Table header + search (AC-8) --}}
            <div class="card-header flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Rep Performance</h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        All sales reps — click a column header to sort (AC-7)
                    </p>
                </div>

                {{-- AC-8: Rep name search --}}
                <form method="GET" action="{{ route('admin.analytics') }}"
                      class="flex items-center gap-2">
                    @foreach(['date_preset','date_from','date_to','sort','direction','filter_status','filter_rep','rep_sort','rep_dir'] as $carry)
                        @if(request($carry))
                            <input type="hidden" name="{{ $carry }}" value="{{ request($carry) }}">
                        @endif
                    @endforeach
                    <input type="text" name="rep_search"
                           value="{{ $repSearch }}"
                           placeholder="Search rep…"
                           class="form-control text-xs py-1.5 w-36"
                           data-testid="rep-search-input">
                    <button type="submit" class="btn-secondary btn-sm py-1.5 text-xs">Search</button>
                    @if($repSearch)
                        <a href="{{ route('admin.analytics', array_filter(array_merge(request()->query(), ['rep_search' => null]))) }}"
                           class="text-xs text-slate-400 hover:text-slate-600">Clear</a>
                    @endif
                </form>
            </div>

            @php
                // Rep table sort URL helper (AC-7)
                $repSortUrl = fn (string $col) => route('admin.analytics', array_merge(
                    request()->query(),
                    [
                        'rep_sort' => $col,
                        'rep_dir'  => ($repSortCol === $col && $repSortDir === 'asc') ? 'desc' : 'asc',
                    ]
                ));
                $repSortIcon = function (string $col) use ($repSortCol, $repSortDir): string {
                    if ($repSortCol !== $col) {
                        return '<svg class="w-3 h-3 text-slate-300 ml-0.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>';
                    }
                    return $repSortDir === 'asc'
                        ? '<svg class="w-3 h-3 text-violet-500 ml-0.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
                        : '<svg class="w-3 h-3 text-violet-500 ml-0.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
                };
            @endphp

            @if($repStats->isEmpty())
                <div class="px-6 py-10 text-center text-sm text-slate-400" data-testid="rep-empty">
                    @if($repSearch)
                        No reps match "{{ $repSearch }}".
                    @else
                        No sales reps found.
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[640px]">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/60">
                                <th class="px-4 py-3 text-left">
                                    <a href="{{ $repSortUrl('name') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                        Rep {!! $repSortIcon('name') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <a href="{{ $repSortUrl('total') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                        Total {!! $repSortIcon('total') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <a href="{{ $repSortUrl('sent') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                        Sent {!! $repSortIcon('sent') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <a href="{{ $repSortUrl('accepted') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                        Accepted {!! $repSortIcon('accepted') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <a href="{{ $repSortUrl('open_rate') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                        Open % {!! $repSortIcon('open_rate') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right">
                                    <a href="{{ $repSortUrl('accept_rate') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                        Accept % {!! $repSortIcon('accept_rate') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right hidden lg:table-cell">
                                    <a href="{{ $repSortUrl('avg_days') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                        Avg Days {!! $repSortIcon('avg_days') !!}
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($repStats as $rep)
                                <tr class="hover:bg-slate-50/50 transition-colors" data-testid="rep-row">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            @if($topRep && $rep->id === $topRep->id)
                                                <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-label="Top performer">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endif
                                            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-violet-100 to-indigo-100
                                                        flex items-center justify-center text-violet-700 font-bold text-[10px] shrink-0">
                                                {{ strtoupper(substr($rep->name, 0, 2)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-slate-800 truncate text-sm">{{ $rep->name }}</p>
                                                <p class="text-[10px] text-slate-400 truncate">{{ $rep->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-700 font-medium">{{ $rep->total_proposals }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ $rep->sent_proposals }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums font-semibold text-emerald-700">{{ $rep->accepted_proposals }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">
                                        <span class="{{ $rep->open_rate >= 60 ? 'text-emerald-600 font-semibold' : ($rep->open_rate >= 30 ? 'text-amber-600 font-semibold' : 'text-slate-400') }}">
                                            {{ $rep->open_rate }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums">
                                        <span class="{{ $rep->accept_rate >= 50 ? 'text-emerald-600 font-semibold' : ($rep->accept_rate >= 20 ? 'text-amber-600 font-semibold' : 'text-slate-400') }}">
                                            {{ $rep->accept_rate }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-500 hidden lg:table-cell">
                                        {{ $rep->avg_days_to_accept !== null ? $rep->avg_days_to_accept . 'd' : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════
             AC-9/10/11/12/13: PER-PROPOSAL BREAKDOWN TABLE
        ═══════════════════════════════════════════════════════════════════ --}}
        <div class="card overflow-hidden" data-testid="proposal-breakdown">

            {{-- Filter bar (AC-10/11) --}}
            <div class="card-header flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Proposal Breakdown</h3>
                    <p class="text-xs text-slate-400 mt-0.5">All proposals — filter by status or rep, click headers to sort</p>
                </div>
                <span class="badge badge-gray text-[10px]" data-testid="proposal-count-badge">
                    {{ $proposals->total() }} total
                </span>
            </div>

            <form method="GET" action="{{ route('admin.analytics') }}"
                  class="px-4 py-3 border-b border-slate-100 bg-slate-50/40 flex flex-wrap items-center gap-3"
                  data-testid="proposal-filter-form">

                @foreach(['date_preset','date_from','date_to','sort','direction','rep_sort','rep_dir','rep_search'] as $carry)
                    @if(request($carry))
                        <input type="hidden" name="{{ $carry }}" value="{{ request($carry) }}">
                    @endif
                @endforeach
                <input type="hidden" name="page" value="1">

                {{-- AC-10: Status filter --}}
                <div class="flex items-center gap-2">
                    <label class="text-xs text-slate-500 font-medium whitespace-nowrap">Status:</label>
                    <select name="filter_status"
                            class="form-control text-xs py-1.5"
                            onchange="this.form.submit()"
                            data-testid="status-filter-select">
                        <option value="">All statuses</option>
                        @foreach(['Draft','Sent','Viewed','Accepted'] as $s)
                            <option value="{{ $s }}" {{ $statusFilter === $s ? 'selected' : '' }}>
                                {{ $s }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- AC-11: Rep filter --}}
                <div class="flex items-center gap-2">
                    <label class="text-xs text-slate-500 font-medium whitespace-nowrap">Rep:</label>
                    <select name="filter_rep"
                            class="form-control text-xs py-1.5"
                            onchange="this.form.submit()"
                            data-testid="rep-filter-select">
                        <option value="">All reps</option>
                        @foreach($repList as $rep)
                            <option value="{{ $rep->id }}" {{ (string)$repFilter === (string)$rep->id ? 'selected' : '' }}>
                                {{ $rep->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($statusFilter || $repFilter)
                    <a href="{{ route('admin.analytics', array_merge(request()->except(['filter_status','filter_rep','page']))) }}"
                       class="text-xs text-slate-400 hover:text-slate-600 ml-1">
                        Clear filters
                    </a>
                @endif
            </form>

            @php
                $sortUrl = fn (string $col) => route('admin.analytics', array_merge(
                    request()->query(),
                    [
                        'sort'      => $col,
                        'direction' => ($sortCol === $col && $sortDir === 'asc') ? 'desc' : 'asc',
                        'page'      => 1,
                    ]
                ));
                $sortIcon = function (string $col) use ($sortCol, $sortDir): string {
                    if ($sortCol !== $col) {
                        return '<svg class="w-3 h-3 text-slate-300 ml-0.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>';
                    }
                    return $sortDir === 'asc'
                        ? '<svg class="w-3 h-3 text-violet-500 ml-0.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
                        : '<svg class="w-3 h-3 text-violet-500 ml-0.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
                };
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[700px]">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="px-4 py-3 text-left w-8 text-xs font-semibold text-slate-400 uppercase tracking-wider">#</th>
                            <th class="px-4 py-3 text-left">
                                <a href="{{ $sortUrl('title') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                    Proposal {!! $sortIcon('title') !!}
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left hidden sm:table-cell">
                                <a href="{{ $sortUrl('rep') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                    Rep {!! $sortIcon('rep') !!}
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left hidden md:table-cell">
                                <a href="{{ $sortUrl('client') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                    Client {!! $sortIcon('client') !!}
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <a href="{{ $sortUrl('status') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                    Status {!! $sortIcon('status') !!}
                                </a>
                            </th>
                            <th class="px-4 py-3 text-right hidden md:table-cell">
                                <a href="{{ $sortUrl('deal_size') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                    Deal {!! $sortIcon('deal_size') !!}
                                </a>
                            </th>
                            <th class="px-4 py-3 text-right hidden lg:table-cell">
                                <a href="{{ $sortUrl('created') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                    Created {!! $sortIcon('created') !!}
                                </a>
                            </th>
                            <th class="px-4 py-3 text-right hidden lg:table-cell">
                                <a href="{{ $sortUrl('views') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 uppercase tracking-wider hover:text-slate-700">
                                    Views {!! $sortIcon('views') !!}
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($proposals as $proposal)
                            @php
                                $badgeClass = match($proposal->status) {
                                    'Sent', 'Viewed' => 'badge-info',
                                    'Accepted'       => 'badge-success',
                                    default          => 'badge-gray',
                                };
                                $dotColor = match($proposal->status) {
                                    'Sent', 'Viewed' => 'bg-sky-400',
                                    'Accepted'       => 'bg-emerald-500',
                                    default          => 'bg-slate-300',
                                };
                            @endphp
                            {{-- AC-13: clickable row navigates to proposal detail --}}
                            <tr class="hover:bg-slate-50/60 cursor-pointer transition-colors"
                                onclick="window.location='{{ route('proposals.show', $proposal->id) }}'"
                                data-testid="proposal-row"
                                title="View proposal #{{ $proposal->id }}">
                                <td class="px-4 py-3 text-xs font-mono text-slate-400">#{{ $proposal->id }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-800 truncate max-w-[160px]">
                                        {{ $proposal->proposal_title ?: $proposal->client_name }}
                                    </p>
                                    <p class="text-xs text-slate-400 truncate max-w-[160px]">
                                        {{ $proposal->client_company ?: $proposal->client_email }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 hidden sm:table-cell">
                                    <p class="text-slate-600 truncate max-w-[120px] text-xs">{{ $proposal->rep_name }}</p>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell">
                                    <p class="text-slate-600 truncate max-w-[120px] text-xs">{{ $proposal->client_name }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="{{ $badgeClass }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                        {{ $proposal->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-700 hidden md:table-cell">
                                    ${{ number_format($proposal->deal_size, 0) }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-slate-400 hidden lg:table-cell">
                                    {{ $proposal->created_at->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 text-right hidden lg:table-cell">
                                    @if($proposal->views_count > 0)
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold
                                                     text-sky-700 bg-sky-50 border border-sky-200 rounded-full px-2 py-0.5">
                                            {{ $proposal->views_count }}
                                        </span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            {{-- AC-16: empty state --}}
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center" data-testid="proposal-empty-state">
                                    <div class="mx-auto w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mb-3">
                                        <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-500">No proposals found for the selected period</p>
                                    <p class="text-xs text-slate-400 mt-1">Try adjusting the date range or filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- AC-12: Pagination --}}
            @if($proposals->hasPages())
                <div class="px-4 py-4 border-t border-slate-100">
                    {{ $proposals->links() }}
                </div>
            @endif
        </div>

    </div>

</x-app-layout>
