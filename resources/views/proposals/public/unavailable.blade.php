<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proposal Unavailable</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 min-h-screen flex flex-col items-center justify-center px-4">

    {{--
        AC-10: Expired / deleted → "no longer available"
        AC-11: Draft → "not yet shared"
        AC-32: Invalid token → 404-style friendly message
    --}}

    <div class="w-full max-w-md text-center">

        {{-- Logo --}}
        <div class="flex items-center justify-center gap-2 mb-10">
            <div class="w-9 h-9 bg-violet-600 rounded-xl flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <span class="font-bold text-slate-800 text-lg tracking-tight">{{ config('app.name') }}</span>
        </div>

        @php
            $reason = $reason ?? 'not_found';
        @endphp

        @if($reason === 'draft')
            {{-- AC-11: Draft — not yet shared --}}
            <div class="w-16 h-16 rounded-2xl bg-amber-100 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-3">Not yet shared</h1>
            <p class="text-slate-500 leading-relaxed">
                This proposal hasn't been shared with you yet.
                Please check back once the sender has finished preparing it.
            </p>

        @elseif($reason === 'already_resolved')
            {{-- Accepted / already resolved --}}
            <div class="w-16 h-16 rounded-2xl bg-emerald-100 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-3">Proposal already resolved</h1>
            <p class="text-slate-500 leading-relaxed">
                This proposal has already been accepted or declined and cannot be modified further.
            </p>

        @else
            {{-- AC-32: not_found / invalid token — friendly 404 --}}
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-3">This proposal is no longer available</h1>
            <p class="text-slate-500 leading-relaxed">
                The link you followed may have expired, been revoked, or never existed.
                Please contact the sender for an updated link.
            </p>
        @endif

        <p class="mt-10 text-xs text-slate-400">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
    </div>

</body>
</html>
