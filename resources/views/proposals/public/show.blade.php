<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- AC-20: no internal controls / no indexing --}}
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $proposal->proposal_title ?: 'Proposal' }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50">

{{--
    AC-2/3:  No auth required; read-only view of proposal content.
    AC-20:   No editable fields, no internal management controls.
    AC-21:   Accept / Decline CTAs for the client.
    AC-29:   Minimal privacy notice in the footer.
--}}

{{-- ── Header ─────────────────────────────────────────────────────────────── --}}
<header class="bg-white border-b border-slate-200 sticky top-0 z-30">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 bg-violet-600 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <span class="font-bold text-slate-800 text-sm">{{ config('app.name') }}</span>
        </div>

        {{-- Status badge --}}
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full
            {{ $proposal->status === 'Accepted' ? 'bg-emerald-100 text-emerald-700' :
               ($proposal->status === 'Viewed'  ? 'bg-sky-100 text-sky-700' :
                                                  'bg-violet-100 text-violet-700') }}">
            <span class="w-1.5 h-1.5 rounded-full
                {{ $proposal->status === 'Accepted' ? 'bg-emerald-500' :
                   ($proposal->status === 'Viewed'  ? 'bg-sky-400' :
                                                      'bg-violet-500') }}"></span>
            {{ $proposal->status }}
        </span>
    </div>
</header>

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-10 space-y-8">

    {{-- ── Flash messages (AC-22) ────────────────────────────────────────── --}}
    @if(isset($flash) && $flash === 'accepted')
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5 flex items-start gap-3"
             role="alert">
            <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-emerald-800">Proposal accepted</p>
                <p class="text-xs text-emerald-700 mt-0.5">
                    Thank you for accepting this proposal. The sender has been notified.
                </p>
            </div>
        </div>
    @elseif(isset($flash) && $flash === 'declined')
        <div class="rounded-2xl bg-slate-50 border border-slate-200 p-5 flex items-start gap-3"
             role="alert">
            <svg class="w-5 h-5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-slate-700">Proposal declined</p>
                <p class="text-xs text-slate-500 mt-0.5">
                    You have declined this proposal. The sender has been notified.
                </p>
            </div>
        </div>
    @endif

    {{-- ── Proposal header ────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">

        {{-- Gradient bar --}}
        <div class="h-2 bg-gradient-to-r from-violet-600 to-indigo-500"></div>

        <div class="p-6 sm:p-8">
            <p class="text-xs font-semibold text-violet-600 uppercase tracking-widest mb-2">Proposal</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 leading-tight mb-4">
                {{ $proposal->proposal_title ?: 'Untitled Proposal' }}
            </h1>

            <div class="flex flex-wrap gap-x-8 gap-y-3 text-sm text-slate-600">
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Prepared for</p>
                    <p class="font-semibold text-slate-800">{{ $proposal->client_name }}</p>
                    @if($proposal->client_company)
                        <p class="text-slate-500">{{ $proposal->client_company }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Industry</p>
                    <p class="font-medium">{{ $proposal->industry }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Deal Value</p>
                    <p class="font-semibold text-slate-800">${{ number_format($proposal->deal_size, 0) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Date</p>
                    <p class="font-medium">{{ $proposal->created_at->format('F j, Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── AI-generated content (AC-3) ───────────────────────────────────── --}}
    @if($proposal->generated_content)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
             data-testid="public-content">
            <div class="px-6 sm:px-8 py-5 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Proposal Details</h2>
            </div>
            <div class="px-6 sm:px-8 py-6 prose prose-slate prose-sm max-w-none
                        prose-headings:font-bold prose-headings:text-slate-900
                        prose-p:text-slate-600 prose-p:leading-relaxed
                        prose-strong:text-slate-800 prose-li:text-slate-600">
                {!! nl2br(e($proposal->generated_content)) !!}
            </div>
        </div>
    @endif

    {{-- ── Requirements / scope (AC-3) ───────────────────────────────────── --}}
    @if($proposal->requirements)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 sm:px-8 py-5 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Scope & Requirements</h2>
            </div>
            <div class="px-6 sm:px-8 py-6 text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                {{ $proposal->requirements }}
            </div>
        </div>
    @endif

    {{-- ── Client CTAs (AC-21) ────────────────────────────────────────────── --}}
    @if(in_array($proposal->status, ['Sent', 'Viewed']))
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8"
             data-testid="client-cta-section">
            <h2 class="text-base font-bold text-slate-900 mb-2">Ready to proceed?</h2>
            <p class="text-sm text-slate-500 mb-6">
                Review the proposal above and let us know your decision. Your response
                will be recorded immediately.
            </p>

            <div class="flex flex-col sm:flex-row gap-3">
                {{-- Accept (AC-21/22) --}}
                <form method="POST"
                      action="{{ route('proposals.public.accept', $proposal->public_token) }}"
                      onsubmit="return confirm('Accept this proposal? This action cannot be undone.')"
                      class="flex-1">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2
                                   bg-emerald-600 hover:bg-emerald-700 text-white font-semibold
                                   text-sm px-6 py-3 rounded-xl transition-colors
                                   focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                            data-testid="accept-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Accept Proposal
                    </button>
                </form>

                {{-- Decline (AC-21/22) --}}
                <form method="POST"
                      action="{{ route('proposals.public.decline', $proposal->public_token) }}"
                      onsubmit="return confirm('Decline this proposal?')"
                      class="flex-1 sm:flex-none">
                    @csrf
                    <button type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2
                                   bg-white hover:bg-slate-50 text-slate-600 font-semibold
                                   text-sm px-6 py-3 rounded-xl border border-slate-200
                                   transition-colors focus:outline-none focus-visible:ring-2
                                   focus-visible:ring-slate-400 focus-visible:ring-offset-2"
                            data-testid="decline-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Decline
                    </button>
                </form>
            </div>
        </div>
    @elseif($proposal->status === 'Accepted')
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-6 flex items-center gap-4"
             data-testid="accepted-banner">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-emerald-800">Proposal accepted</p>
                <p class="text-xs text-emerald-700 mt-0.5">
                    This proposal has been accepted. Thank you for your business.
                </p>
            </div>
        </div>
    @endif

</main>

{{-- ── Footer with privacy notice (AC-29) ─────────────────────────────────── --}}
<footer class="mt-16 border-t border-slate-200 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center
                justify-between gap-3 text-xs text-slate-400">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        {{-- AC-29: minimal privacy notice --}}
        <p class="text-center sm:text-right leading-relaxed max-w-sm">
            When you view this page, basic access data (IP address, browser) may be
            recorded to help the sender understand engagement. No personal account is
            created. See our <a href="#" class="underline hover:text-slate-600">Privacy Policy</a>.
        </p>
    </div>
</footer>

</body>
</html>
