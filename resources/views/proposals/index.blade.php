<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Proposals</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    @if(isset($loadError) && $loadError)
                        Could not load proposals
                    @elseif($search || $status)
                        {{ $proposals->total() }} result{{ $proposals->total() !== 1 ? 's' : '' }}
                        @if($search)
                            for "<span class="font-medium text-slate-700">{{ $search }}</span>"
                        @endif
                        @if($status)
                            &middot; <span class="font-medium text-slate-700">{{ $status }}</span>
                        @endif
                    @else
                        {{ $totalCount }} proposal{{ $totalCount !== 1 ? 's' : '' }} in your pipeline
                    @endif
                </p>
            </div>
            <a href="{{ route('proposals.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Proposal
            </a>
        </div>
    </x-slot>

    {{-- AC-24: page-level loading indicator via NProgress-style thin bar ─── --}}
    <style>
        #page-loader {
            position: fixed; top: 0; left: 0; right: 0;
            height: 3px; background: #7c3aed; z-index: 9999;
            transform: scaleX(0); transform-origin: left;
            transition: transform 0.3s ease;
        }
        .htmx-request #page-loader { transform: scaleX(0.7); }
    </style>
    <div id="page-loader"></div>

    <div class="page-section space-y-5"
         x-data="{ loading: false }"
         @submit.document="loading = true">

        {{-- ── Flash messages ────────────────────────────────────── --}}
        @if (session('success'))
            <div class="alert-success animate-fade-up">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert-error animate-fade-up">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- AC-25: DB / server error banner ─────────────────────── --}}
        @if(isset($loadError) && $loadError)
            <div class="alert-error">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    Unable to load proposals. Please try again.
                </div>
                <a href="{{ route('proposals.index') }}"
                   class="text-rose-700 underline underline-offset-2 text-xs font-semibold shrink-0">
                    Retry
                </a>
            </div>
        @endif

        {{-- ── Toolbar ────────────────────────────────────────────── --}}
        <form method="GET" action="{{ route('proposals.index') }}" id="filter-form">

            {{-- Carry sort state through all filter changes --}}
            @if($sortKey !== 'modified' || $sortDir !== 'desc')
                <input type="hidden" name="sort"      value="{{ $sortKey }}">
                <input type="hidden" name="direction" value="{{ $sortDir }}">
            @endif

            {{-- Status pill tabs + per-page selector ──────────────── --}}
            <div class="flex flex-wrap items-center gap-2 mb-4">

                @php
                    $tabs = [
                        ''         => ['label' => 'All',      'dot' => null,             'count' => $totalCount],
                        'Draft'    => ['label' => 'Draft',    'dot' => 'bg-slate-400',   'count' => $statusCounts['Draft']    ?? 0],
                        'Sent'     => ['label' => 'Sent',     'dot' => 'bg-sky-400',     'count' => $statusCounts['Sent']     ?? 0],
                        'Accepted' => ['label' => 'Accepted', 'dot' => 'bg-emerald-500', 'count' => $statusCounts['Accepted'] ?? 0],
                    ];
                @endphp

                @foreach($tabs as $val => $tab)
                    <button type="submit" name="status" value="{{ $val }}"
                            class="{{ ($status ?? '') === $val ? 'pill-tab-active' : 'pill-tab-inactive' }}"
                            @click="loading = true">
                        @if($tab['dot'])
                            <span class="w-1.5 h-1.5 rounded-full {{ $tab['dot'] }}"></span>
                        @endif
                        {{ $tab['label'] }}
                        <span class="ml-0.5 tabular-nums {{ ($status ?? '') === $val ? 'text-slate-600' : 'text-slate-400' }}">
                            {{ $tab['count'] }}
                        </span>
                    </button>
                @endforeach

                <span class="flex-1"></span>

                {{-- Keep search + status when per_page changes --}}
                @if($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif
                @if($status)
                    <input type="hidden" name="status_hidden" value="{{ $status }}">
                @endif

                <select name="per_page"
                        onchange="
                            const f = this.form;
                            const sh = f.querySelector('[name=status_hidden]');
                            if (sh) { sh.name = 'status'; }
                            f.submit();
                        "
                        class="form-select text-xs py-1.5 w-28">
                    @foreach([10, 25, 50] as $n)
                        <option value="{{ $n }}" {{ $perPage === $n ? 'selected' : '' }}>
                            {{ $n }} per page
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Search row ─────────────────────────────────────────── --}}
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <span class="input-prefix">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                        </svg>
                    </span>
                    {{-- AC-22: descriptive placeholder --}}
                    <input type="text"
                           name="search"
                           id="search-input"
                           value="{{ $search ?? '' }}"
                           placeholder="Search by proposal name, ID, or client…"
                           class="form-control input-has-prefix"
                           autocomplete="off" />
                </div>
                {{-- AC-7: explicit Search button --}}
                <button type="submit" class="btn-secondary shrink-0" @click="loading = true">
                    Search
                </button>
                @if($search || $status)
                    <a href="{{ route('proposals.index', array_filter(['per_page' => $perPage !== 10 ? $perPage : null])) }}"
                       class="btn-ghost shrink-0 text-slate-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Clear
                    </a>
                @endif
            </div>

            {{-- Active filter chips ─────────────────────────────────── --}}
            @if($search || $status)
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <span class="text-xs text-slate-400 font-medium">Filters:</span>
                    @if($search)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-violet-100 text-violet-700 text-xs font-medium">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                            </svg>
                            "{{ $search }}"
                        </span>
                    @endif
                    @if($status)
                        @php
                            $chipDot = match($status) { 'Sent' => 'bg-sky-400', 'Accepted' => 'bg-emerald-500', default => 'bg-slate-400' };
                        @endphp
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-slate-100 text-slate-700 text-xs font-medium">
                            <span class="w-1.5 h-1.5 rounded-full {{ $chipDot }}"></span>
                            {{ $status }}
                        </span>
                    @endif
                    @if($sortKey !== 'modified' || $sortDir !== 'desc')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                     bg-slate-100 text-slate-600 text-xs font-medium">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="{{ $sortDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                            </svg>
                            Sorted by {{ $sortKey }}
                        </span>
                    @endif
                </div>
            @endif

        </form>

        {{-- AC-24: Loading overlay (shown while form navigates) ─────── --}}
        <div x-show="loading"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 bg-white/60 backdrop-blur-sm z-50 flex items-center justify-center pointer-events-none"
             style="display:none">
            <div class="flex items-center gap-3 bg-white rounded-2xl shadow-lg border border-slate-200 px-5 py-3.5">
                <svg class="w-5 h-5 text-violet-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm font-medium text-slate-600">Loading proposals…</span>
            </div>
        </div>

        {{-- ── Table card ─────────────────────────────────────────── --}}
        <div class="card overflow-hidden">

            @if($proposals->isEmpty())

                {{-- ── Empty states (4 variants) ─────────────────── --}}
                <div class="empty-state">
                    <div class="empty-icon">
                        @if($search || $status)
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                            </svg>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        @endif
                    </div>

                    {{-- AC-17/18/19/23: Context-specific empty state copy --}}
                    @if($search && $status)
                        <p class="empty-title">No {{ strtolower($status) }} proposals match "{{ $search }}"</p>
                        <p class="empty-subtitle">
                            Try a different search term, remove the status filter, or
                            <a href="{{ route('proposals.index', array_filter(['search' => $search, 'per_page' => $perPage !== 10 ? $perPage : null])) }}"
                               class="text-violet-600 hover:underline">clear the status filter</a>.
                        </p>
                    @elseif($search)
                        {{-- AC-23: clear message for no-result search --}}
                        <p class="empty-title">No proposals match "{{ $search }}"</p>
                        <p class="empty-subtitle">
                            No results for that term. Try a different client name, company, email, or industry —
                            or <a href="{{ route('proposals.index', array_filter(['status' => $status, 'per_page' => $perPage !== 10 ? $perPage : null])) }}"
                                  class="text-violet-600 hover:underline">clear the search</a>.
                        </p>
                    @elseif($status)
                        <p class="empty-title">No {{ strtolower($status) }} proposals</p>
                        <p class="empty-subtitle">
                            You have no proposals with status "{{ $status }}" yet.
                        </p>
                    @else
                        {{-- AC-19: first-time empty state, distinct from no-results --}}
                        <p class="empty-title">No proposals yet</p>
                        <p class="empty-subtitle">
                            Create your first AI-powered proposal to get started.
                            Walnut AI will generate a structured draft from your client details.
                        </p>
                        <a href="{{ route('proposals.create') }}" class="btn-primary btn-sm mt-5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create your first proposal
                        </a>
                    @endif

                    {{-- AC-18: clear-filters CTA on filtered empty states --}}
                    @if($search || $status)
                        <a href="{{ route('proposals.index', array_filter(['per_page' => $perPage !== 10 ? $perPage : null])) }}"
                           class="btn-secondary btn-sm mt-5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Reset all filters
                        </a>
                    @endif
                </div>

            @else

                {{-- ── Proposal table ──────────────────────────────── --}}
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                {{-- AC-2: proposal ID shown as first column --}}
                                <th class="hidden lg:table-cell w-14">#</th>

                                {{-- AC-15: sortable column headers --}}
                                @php
                                    /**
                                     * Helper: returns the URL for sorting by a given key.
                                     * Toggles direction if already sorted by that key.
                                     */
                                    $sortUrl = function (string $key) use ($sortKey, $sortDir, $search, $status, $perPage): string {
                                        $newDir = ($sortKey === $key && $sortDir === 'asc') ? 'desc' : 'asc';
                                        return route('proposals.index', array_filter([
                                            'sort'      => $key,
                                            'direction' => $newDir,
                                            'search'    => $search,
                                            'status'    => $status,
                                            'per_page'  => $perPage !== 10 ? $perPage : null,
                                        ]));
                                    };

                                    $sortIcon = function (string $key) use ($sortKey, $sortDir): string {
                                        if ($sortKey !== $key) {
                                            return '<svg class="w-3 h-3 text-slate-300 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>';
                                        }
                                        return $sortDir === 'asc'
                                            ? '<svg class="w-3 h-3 text-violet-600 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
                                            : '<svg class="w-3 h-3 text-violet-600 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
                                    };
                                @endphp

                                <th>
                                    <a href="{{ $sortUrl('title') }}"
                                       class="inline-flex items-center hover:text-slate-700 transition-colors {{ $sortKey === 'title' ? 'text-violet-700' : '' }}">
                                        Proposal {!! $sortIcon('title') !!}
                                    </a>
                                </th>
                                <th class="hidden sm:table-cell">
                                    <a href="{{ $sortUrl('client') }}"
                                       class="inline-flex items-center hover:text-slate-700 transition-colors {{ $sortKey === 'client' ? 'text-violet-700' : '' }}">
                                        Industry {!! $sortIcon('client') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('status') }}"
                                       class="inline-flex items-center hover:text-slate-700 transition-colors {{ $sortKey === 'status' ? 'text-violet-700' : '' }}">
                                        Status {!! $sortIcon('status') !!}
                                    </a>
                                </th>
                                <th class="hidden md:table-cell">
                                    <a href="{{ $sortUrl('deal') }}"
                                       class="inline-flex items-center hover:text-slate-700 transition-colors {{ $sortKey === 'deal' ? 'text-violet-700' : '' }}">
                                        Deal Size {!! $sortIcon('deal') !!}
                                    </a>
                                </th>
                                <th class="hidden lg:table-cell">
                                    <a href="{{ $sortUrl('created') }}"
                                       class="inline-flex items-center hover:text-slate-700 transition-colors {{ $sortKey === 'created' ? 'text-violet-700' : '' }}">
                                        Created {!! $sortIcon('created') !!}
                                    </a>
                                </th>
                                <th class="hidden lg:table-cell">
                                    <a href="{{ $sortUrl('modified') }}"
                                       class="inline-flex items-center hover:text-slate-700 transition-colors {{ $sortKey === 'modified' ? 'text-violet-700' : '' }}">
                                        Modified {!! $sortIcon('modified') !!}
                                    </a>
                                </th>
                                <th class="hidden md:table-cell">Views</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($proposals as $proposal)
                                @php
                                    $badgeClass = match($proposal->status) {
                                        'Sent'     => 'badge-info',
                                        'Viewed'   => 'badge-info',
                                        'Accepted' => 'badge-success',
                                        default    => 'badge-gray',
                                    };
                                    $dotColor = match($proposal->status) {
                                        'Sent'     => 'bg-sky-400',
                                        'Viewed'   => 'bg-sky-400',
                                        'Accepted' => 'bg-emerald-500',
                                        default    => 'bg-slate-300',
                                    };
                                    $primaryLabel   = $proposal->proposal_title ?: $proposal->client_name;
                                    $secondaryLabel = $proposal->client_company ?: $proposal->client_name;
                                    $avatarLetters  = strtoupper(substr($secondaryLabel, 0, 2));
                                @endphp
                                {{-- AC-14: row click navigates to detail --}}
                                <tr class="cursor-pointer hover:bg-violet-50/30 transition-colors duration-100"
                                    onclick="window.location='{{ route('proposals.show', $proposal) }}'">

                                    {{-- AC-2: proposal ID column --}}
                                    <td class="hidden lg:table-cell">
                                        <span class="text-xs font-mono text-slate-400">#{{ $proposal->id }}</span>
                                    </td>

                                    {{-- Proposal title + client --}}
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-100 to-indigo-100
                                                        flex items-center justify-center text-violet-700 font-bold text-[11px] shrink-0">
                                                {{ $avatarLetters }}
                                            </div>
                                            <div class="min-w-0">
                                                {{-- Search term highlight in title (AC-6) --}}
                                                <p class="text-sm font-semibold text-slate-800 truncate max-w-[200px]">
                                                    @if($search)
                                                        {!! preg_replace(
                                                            '/(' . preg_quote(e($search), '/') . ')/iu',
                                                            '<mark class="bg-violet-100 text-violet-800 rounded-sm px-0.5 not-italic">$1</mark>',
                                                            e($primaryLabel)
                                                        ) !!}
                                                    @else
                                                        {{ $primaryLabel }}
                                                    @endif
                                                </p>
                                                <p class="text-xs text-slate-400 truncate max-w-[200px]">
                                                    {{ $secondaryLabel }}
                                                    @if($proposal->client_name && $proposal->client_company)
                                                        <span class="text-slate-300 mx-0.5">&middot;</span>{{ $proposal->client_name }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Industry --}}
                                    <td class="hidden sm:table-cell">
                                        <span class="text-sm text-slate-500">{{ $proposal->industry }}</span>
                                    </td>

                                    {{-- AC-13: colour-coded status badge --}}
                                    <td>
                                        <span class="{{ $badgeClass }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                            {{ $proposal->status }}
                                        </span>
                                    </td>

                                    {{-- Deal size --}}
                                    <td class="hidden md:table-cell">
                                        <span class="text-sm font-semibold text-slate-800 tabular-nums">
                                            ${{ number_format($proposal->deal_size, 0) }}
                                        </span>
                                    </td>

                                    {{-- AC-2: created date --}}
                                    <td class="hidden lg:table-cell">
                                        <span class="text-sm text-slate-400">
                                            {{ $proposal->created_at->format('M j, Y') }}
                                        </span>
                                    </td>

                                    {{-- AC-2: last modified --}}
                                    <td class="hidden lg:table-cell">
                                        <span class="text-sm text-slate-400">
                                            {{ $proposal->updated_at->diffForHumans(null, true) }} ago
                                        </span>
                                    </td>

                                    {{-- WB-032 AC-13: view count alongside status --}}
                                    <td class="hidden md:table-cell" data-testid="view-count-cell">
                                        @php $vc = $proposal->views_count ?? $proposal->humanViews()->count(); @endphp
                                        @if($vc > 0)
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold
                                                         text-sky-700 bg-sky-50 border border-sky-200 rounded-full px-2 py-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                {{ $vc }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-300">—</span>
                                        @endif
                                    </td>

                                    {{-- Row actions --}}
                                    <td class="text-right" onclick="event.stopPropagation()">
                                        <div class="flex items-center justify-end gap-0.5">

                                            {{-- View --}}
                                            <a href="{{ route('proposals.show', $proposal) }}"
                                               class="w-8 h-8 flex-center rounded-lg text-slate-400
                                                      hover:bg-violet-50 hover:text-violet-600 transition-colors"
                                               title="View">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>

                                            {{-- Edit --}}
                                            <a href="{{ route('proposals.edit', $proposal) }}"
                                               class="w-8 h-8 flex-center rounded-lg text-slate-400
                                                      hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                               title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>

                                            {{-- Clone --}}
                                            <form method="POST"
                                                  action="{{ route('proposals.clone', $proposal) }}"
                                                  onsubmit="return confirm('Clone \'{{ addslashes($primaryLabel) }}\'?')">
                                                @csrf
                                                <button type="submit"
                                                        class="w-8 h-8 flex-center rounded-lg text-slate-400
                                                               hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                                                        title="Clone">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                </button>
                                            </form>

                                            {{-- Delete --}}
                                            <form method="POST"
                                                  action="{{ route('proposals.destroy', $proposal) }}"
                                                  onsubmit="return confirm('Delete \'{{ addslashes($primaryLabel) }}\'? This cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-8 h-8 flex-center rounded-lg text-slate-400
                                                               hover:bg-rose-50 hover:text-rose-600 transition-colors"
                                                        title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ── Pagination footer (AC-3/4/5) ─────────────────── --}}
                <div class="card-footer">
                    <div class="flex items-center justify-between gap-4 flex-wrap">

                        {{-- AC-5: "Showing X–Y of Z" --}}
                        <p class="text-xs text-slate-400">
                            @if($proposals->hasPages())
                                Showing
                                <span class="font-semibold text-slate-600">{{ $proposals->firstItem() }}</span>
                                –
                                <span class="font-semibold text-slate-600">{{ $proposals->lastItem() }}</span>
                                of
                                <span class="font-semibold text-slate-600">{{ $proposals->total() }}</span>
                                proposal{{ $proposals->total() !== 1 ? 's' : '' }}
                                &nbsp;&middot;&nbsp; Page
                                <span class="font-semibold text-slate-600">{{ $proposals->currentPage() }}</span>
                                of
                                <span class="font-semibold text-slate-600">{{ $proposals->lastPage() }}</span>
                            @else
                                {{ $proposals->total() }} proposal{{ $proposals->total() !== 1 ? 's' : '' }}
                            @endif
                        </p>

                        {{-- AC-4: First / Prev / page numbers / Next / Last --}}
                        @if($proposals->hasPages())
                            <nav class="flex items-center gap-1" aria-label="Pagination">

                                {{-- First page --}}
                                @if($proposals->onFirstPage())
                                    <span class="w-8 h-8 flex-center rounded-lg text-slate-300 cursor-not-allowed" title="First page">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M18 19l-7-7 7-7"/>
                                        </svg>
                                    </span>
                                @else
                                    <a href="{{ $proposals->url(1) }}"
                                       class="w-8 h-8 flex-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                                       title="First page">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M18 19l-7-7 7-7"/>
                                        </svg>
                                    </a>
                                @endif

                                {{-- Previous page --}}
                                @if($proposals->onFirstPage())
                                    <span class="w-8 h-8 flex-center rounded-lg text-slate-300 cursor-not-allowed" title="Previous page">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </span>
                                @else
                                    <a href="{{ $proposals->previousPageUrl() }}"
                                       class="w-8 h-8 flex-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                                       title="Previous page">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </a>
                                @endif

                                {{-- Page number pills (show at most 5 pages centred on current) --}}
                                @php
                                    $window = 2;
                                    $start  = max(1, $proposals->currentPage() - $window);
                                    $end    = min($proposals->lastPage(), $proposals->currentPage() + $window);
                                @endphp
                                @if($start > 1)
                                    <span class="text-xs text-slate-300 px-0.5">…</span>
                                @endif
                                @for($page = $start; $page <= $end; $page++)
                                    @if($page === $proposals->currentPage())
                                        <span class="w-8 h-8 flex-center rounded-lg bg-violet-600 text-white text-xs font-bold">
                                            {{ $page }}
                                        </span>
                                    @else
                                        <a href="{{ $proposals->url($page) }}"
                                           class="w-8 h-8 flex-center rounded-lg text-slate-600 text-xs
                                                  hover:bg-slate-100 hover:text-slate-800 transition-colors">
                                            {{ $page }}
                                        </a>
                                    @endif
                                @endfor
                                @if($end < $proposals->lastPage())
                                    <span class="text-xs text-slate-300 px-0.5">…</span>
                                @endif

                                {{-- Next page --}}
                                @if($proposals->hasMorePages())
                                    <a href="{{ $proposals->nextPageUrl() }}"
                                       class="w-8 h-8 flex-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                                       title="Next page">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                @else
                                    <span class="w-8 h-8 flex-center rounded-lg text-slate-300 cursor-not-allowed" title="Next page">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </span>
                                @endif

                                {{-- Last page --}}
                                @if($proposals->hasMorePages())
                                    <a href="{{ $proposals->url($proposals->lastPage()) }}"
                                       class="w-8 h-8 flex-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                                       title="Last page">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M6 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                @else
                                    <span class="w-8 h-8 flex-center rounded-lg text-slate-300 cursor-not-allowed" title="Last page">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M6 5l7 7-7 7"/>
                                        </svg>
                                    </span>
                                @endif

                            </nav>
                        @endif
                    </div>
                </div>

            @endif
        </div>

    </div>

</x-app-layout>
