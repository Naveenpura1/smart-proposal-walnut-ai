<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">

            {{-- Back + title --}}
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('proposals.index') }}"
                   class="w-8 h-8 flex items-center justify-center rounded-lg shrink-0
                          text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <h2 class="text-xl font-bold text-slate-900 truncate max-w-[260px] sm:max-w-md lg:max-w-2xl">
                        {{ $proposal->proposal_title ?: $proposal->client_name }}
                    </h2>
                    <p class="text-sm text-slate-500 mt-0.5 truncate">
                        {{ $proposal->client_company ? $proposal->client_company . ' · ' : '' }}{{ $proposal->industry }}
                    </p>
                </div>
            </div>

            {{-- Action bar --}}
            <div class="flex items-center gap-2 flex-wrap shrink-0">
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
                @endphp

                <span class="{{ $badgeClass }} text-sm px-3 py-1">
                    <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                    {{ $proposal->status }}
                </span>

                {{-- Print --}}
                <button onclick="window.print()" class="btn-secondary btn-sm" title="Print proposal">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span class="hidden sm:inline">Print</span>
                </button>

                {{-- Edit --}}
                <a href="{{ route('proposals.edit', $proposal) }}" class="btn-secondary btn-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span class="hidden sm:inline">Edit</span>
                </a>

                {{-- Clone --}}
                <form method="POST" action="{{ route('proposals.clone', $proposal) }}"
                      onsubmit="return confirm('Clone this proposal? A new Draft will be created.')">
                    @csrf
                    <button type="submit" class="btn-secondary btn-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span class="hidden sm:inline">Clone</span>
                    </button>
                </form>

                {{-- Delete --}}
                <form method="POST" action="{{ route('proposals.destroy', $proposal) }}"
                      onsubmit="return confirm('Permanently delete this proposal? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger btn-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span class="hidden sm:inline">Delete</span>
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="page-section-sm space-y-5">

        {{-- Flash messages --}}
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

        {{-- ── AI generation status banners (WB-027) ─────────────────────── --}}
        @php $aiStatus = $proposal->ai_status ?? 'generated'; @endphp

        @if(in_array($aiStatus, ['pending', 'processing']))
            {{-- AC-21: Auto-poll every 8 s until content arrives --}}
            <div class="flex items-center gap-3 px-4 py-3.5 rounded-xl border border-violet-200 bg-violet-50"
                 x-data="{}" x-init="setTimeout(() => location.reload(), 8000)">
                <svg class="w-4 h-4 text-violet-500 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-violet-900">Walnut AI is generating your proposal content…</p>
                    <p class="text-xs text-violet-600 mt-0.5">This page refreshes automatically. Usually takes under 30 seconds.</p>
                </div>
                <span class="badge badge-primary text-[10px] py-0.5 shrink-0">{{ ucfirst($aiStatus) }}</span>
            </div>

        @elseif($aiStatus === 'fallback')
            {{-- AC-13: Fallback content clearly labelled --}}
            <div class="flex items-start gap-3 px-4 py-3.5 rounded-xl border border-amber-200 bg-amber-50">
                <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-amber-900">Offline template — AI generation unavailable</p>
                    <p class="text-xs text-amber-700 mt-0.5">
                        The Walnut AI API was unreachable after {{ $proposal->ai_attempts ?? 0 }} attempt(s).
                        This content was generated from a pre-built template.
                        <a href="{{ route('proposals.edit', $proposal) }}"
                           class="font-semibold underline underline-offset-2 hover:text-amber-900 transition-colors">
                            Regenerate when ready →
                        </a>
                    </p>
                </div>
            </div>

        @elseif($aiStatus === 'failed')
            {{-- AC-23/24: terminal failure --}}
            <div class="flex items-start gap-3 px-4 py-3.5 rounded-xl border border-rose-200 bg-rose-50">
                <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-rose-900">AI generation failed</p>
                    <p class="text-xs text-rose-700 mt-0.5">
                        An administrator can re-queue this proposal:<br>
                        <code class="font-mono bg-rose-100 px-1.5 py-0.5 rounded text-[10px]">php artisan proposals:regenerate --id={{ $proposal->id }}</code>
                    </p>
                </div>
            </div>

        @elseif($proposal->status === 'Draft')
            {{-- Standard Draft nudge --}}
            <div class="alert-warning">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span class="flex-1">
                    This proposal is a <strong>Draft</strong> — review the AI-generated content, make any edits, then mark it as Sent when ready.
                </span>
                <a href="{{ route('proposals.edit', $proposal) }}"
                   class="text-amber-700 font-semibold underline underline-offset-2 text-xs shrink-0 hover:text-amber-900 transition-colors">
                    Edit now →
                </a>
            </div>
        @endif

        {{-- ── Main content grid ───────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- LEFT COLUMN — proposal content (2/3 cols) --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- AI-generated content card --}}
                @if($proposal->generated_content)
                    <div class="card overflow-hidden" x-data="{ copied: false }">

                        <div class="px-6 py-4 bg-gradient-to-r from-violet-600 to-indigo-600
                                    flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-white/15 rounded-xl flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-white">
                                        @if($aiStatus === 'fallback')
                                            Offline Template
                                        @else
                                            AI-Generated Proposal
                                        @endif
                                    </p>
                                    <p class="text-xs text-white/65">
                                        @if($aiStatus === 'fallback')
                                            Pre-built template · regenerate when AI is available
                                        @else
                                            Generated by Walnut AI · review before sending
                                        @endif
                                    </p>
                                </div>
                            </div>
                            {{-- Copy to clipboard --}}
                            <button
                                @click="
                                    navigator.clipboard.writeText($refs.proposalContent.innerText);
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white/15
                                       text-white text-xs font-medium hover:bg-white/25 transition-colors shrink-0">
                                <template x-if="!copied">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </template>
                                <template x-if="copied">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
                            </button>
                        </div>

                        <div class="p-6 lg:p-8" x-ref="proposalContent">
                            @php
                                $raw  = $proposal->generated_content;
                                $html = e($raw);
                                $html = preg_replace('/^### (.+)$/m', '<h3 class="text-base font-bold text-slate-800 mt-6 mb-2">$1</h3>', $html);
                                $html = preg_replace('/^## (.+)$/m',  '<h2 class="text-lg font-bold text-slate-900 mt-8 mb-3 pb-2 border-b border-slate-100">$1</h2>', $html);
                                $html = preg_replace('/^# (.+)$/m',   '<h1 class="text-xl font-extrabold text-slate-900 mt-2 mb-1">$1</h1>', $html);
                                $html = preg_replace('/^&gt; (.+)$/m', '<blockquote class="border-l-4 border-violet-300 pl-4 my-3 text-slate-600 italic text-sm">$1</blockquote>', $html);
                                $html = preg_replace('/^---$/m', '<hr class="my-6 border-slate-100">', $html);
                                $html = preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-semibold text-slate-900">$1</strong>', $html);
                                $html = preg_replace('/\*(.+?)\*/',     '<em class="italic">$1</em>', $html);
                                $html = preg_replace('/^- (.+)$/m', '<li class="flex gap-2 text-sm text-slate-700"><span class="text-violet-400 shrink-0 mt-1">•</span><span>$1</span></li>', $html);
                                $html = preg_replace('/(<li[^>]*>.*<\/li>\n?)+/s', '<ul class="my-3 space-y-1.5">$0</ul>', $html);
                                $html = preg_replace_callback('/(\|.+\|\n?)+/s', function ($m) {
                                    $rows = array_filter(array_map('trim', explode("\n", trim($m[0]))));
                                    $table = '<div class="overflow-x-auto my-4"><table class="w-full text-sm border-collapse">';
                                    $first = true;
                                    foreach ($rows as $row) {
                                        if (preg_match('/^\|[-\s|]+\|$/', $row)) continue;
                                        $cells = array_map('trim', explode('|', trim($row, '|')));
                                        if ($first) {
                                            $table .= '<thead><tr>';
                                            foreach ($cells as $c) {
                                                $table .= '<th class="border border-slate-200 bg-slate-50 px-3 py-2 text-left font-semibold text-slate-700">' . $c . '</th>';
                                            }
                                            $table .= '</tr></thead><tbody>';
                                            $first = false;
                                        } else {
                                            $table .= '<tr>';
                                            foreach ($cells as $c) {
                                                $table .= '<td class="border border-slate-200 px-3 py-2 text-slate-600">' . $c . '</td>';
                                            }
                                            $table .= '</tr>';
                                        }
                                    }
                                    return $table . '</tbody></table></div>';
                                }, $html);
                                $paragraphs = preg_split('/\n{2,}/', $html);
                                $html = implode('', array_map(function ($p) {
                                    $p = trim($p);
                                    if (!$p) return '';
                                    if (preg_match('/^<(h[1-6]|ul|ol|li|blockquote|hr|div|table)/i', $p)) return $p;
                                    return '<p class="text-sm text-slate-700 leading-relaxed mb-3">' . $p . '</p>';
                                }, $paragraphs));
                            @endphp
                            <div class="proposal-content">{!! $html !!}</div>
                        </div>

                        <div class="card-footer flex items-center justify-between">
                            <p class="text-xs text-slate-400">
                                @if($aiStatus === 'fallback')
                                    Offline template · {{ $proposal->ai_attempts ?? 0 }} AI attempt(s) made
                                @elseif($proposal->ai_generated_at)
                                    Generated {{ $proposal->ai_generated_at->diffForHumans() }}
                                @else
                                    AI-generated · review before sending to a client
                                @endif
                            </p>
                            <a href="{{ route('proposals.edit', $proposal) }}"
                               class="text-xs font-semibold text-violet-600 hover:text-violet-800 transition-colors">
                                Edit content →
                            </a>
                        </div>
                    </div>
                @else
                    <div class="card overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-violet-600 to-indigo-600 flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/15 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-white">AI-Generated Proposal</p>
                        </div>
                        <div class="empty-state py-12">
                            <div class="empty-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <p class="empty-title">No content generated yet</p>
                            <p class="empty-subtitle">Edit this proposal and use "Save & Regenerate" to trigger AI generation.</p>
                            <a href="{{ route('proposals.edit', $proposal) }}" class="btn-primary btn-sm mt-5">
                                Edit & Generate
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Client context card --}}
                <div class="card overflow-hidden">
                    <div class="card-header flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">Client Context</h3>
                        <span class="text-xs text-slate-400 ml-auto">(AI generation inputs)</span>
                    </div>
                    <div class="card-body space-y-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Pain Points &amp; Challenges</p>
                            <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap">{{ $proposal->pain_points }}</p>
                        </div>
                        @if($proposal->requirements)
                            <div class="pt-3 border-t border-slate-100">
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Specific Requirements</p>
                                <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap">{{ $proposal->requirements }}</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- RIGHT COLUMN — metadata sidebar (1/3 cols) --}}
            <div class="space-y-4">

                {{-- Quick status change --}}
                <div class="card overflow-hidden">
                    <div class="card-header flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">Status</h3>
                    </div>
                    <div class="p-4 space-y-2">
                        @foreach(\App\Http\Controllers\ProposalController::STATUSES as $s)
                            @php
                                $isActive   = $proposal->status === $s;
                                $dotColors  = ['Draft' => 'bg-slate-400', 'Sent' => 'bg-sky-400', 'Viewed' => 'bg-sky-400', 'Accepted' => 'bg-emerald-500'];
                                $activeBgs  = ['Draft' => 'bg-slate-50 border-slate-300', 'Sent' => 'bg-sky-50 border-sky-300', 'Viewed' => 'bg-sky-50 border-sky-300', 'Accepted' => 'bg-emerald-50 border-emerald-300'];
                                $inactiveBg = 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50';
                            @endphp
                            @if($isActive)
                                <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border {{ $activeBgs[$s] }}">
                                    <span class="w-2 h-2 rounded-full {{ $dotColors[$s] }} shrink-0"></span>
                                    <span class="text-sm font-semibold text-slate-800">{{ $s }}</span>
                                    <svg class="w-3.5 h-3.5 text-emerald-500 ml-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @else
                                <form method="POST" action="{{ route('proposals.update', $proposal) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="proposal_title"    value="{{ $proposal->proposal_title }}">
                                    <input type="hidden" name="client_name"       value="{{ $proposal->client_name }}">
                                    <input type="hidden" name="client_company"    value="{{ $proposal->client_company }}">
                                    <input type="hidden" name="client_email"      value="{{ $proposal->client_email }}">
                                    <input type="hidden" name="industry"          value="{{ $proposal->industry }}">
                                    <input type="hidden" name="pain_points"       value="{{ $proposal->pain_points }}">
                                    <input type="hidden" name="deal_size"         value="{{ $proposal->deal_size }}">
                                    <input type="hidden" name="requirements"      value="{{ $proposal->requirements }}">
                                    <input type="hidden" name="generated_content" value="{{ $proposal->generated_content }}">
                                    <input type="hidden" name="status"            value="{{ $s }}">
                                    <button type="submit"
                                            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl border text-left
                                                   {{ $inactiveBg }} transition-colors">
                                        <span class="w-2 h-2 rounded-full {{ $dotColors[$s] }} shrink-0 opacity-50"></span>
                                        <span class="text-sm text-slate-500">Mark as {{ $s }}</span>
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Deal details --}}
                <div class="card overflow-hidden">
                    <div class="card-header">
                        <h3 class="text-sm font-bold text-slate-800">Deal Details</h3>
                    </div>
                    <div class="card-body space-y-3">
                        @if($proposal->proposal_title)
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Proposal Title</p>
                                <p class="text-sm font-semibold text-slate-800">{{ $proposal->proposal_title }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Contact</p>
                            <p class="text-sm font-semibold text-slate-800">{{ $proposal->client_name }}</p>
                        </div>
                        @if($proposal->client_company)
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Company</p>
                                <p class="text-sm text-slate-700">{{ $proposal->client_company }}</p>
                            </div>
                        @endif
                        @if($proposal->client_email)
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Email</p>
                                <a href="mailto:{{ $proposal->client_email }}"
                                   class="text-sm text-violet-600 hover:text-violet-800 transition-colors break-all">
                                    {{ $proposal->client_email }}
                                </a>
                            </div>
                        @endif
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Industry</p>
                            <p class="text-sm text-slate-700">{{ $proposal->industry }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Deal Size</p>
                            <p class="text-2xl font-extrabold text-slate-900 tabular-nums">
                                ${{ number_format($proposal->deal_size, 0) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Proposal ID</p>
                            <p class="font-mono text-xs text-slate-500">#{{ $proposal->id }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">AI Status</p>
                            @php
                                $aiStatusColors = [
                                    'generated'  => 'badge-success',
                                    'fallback'   => 'badge-warning',
                                    'failed'     => 'badge-danger',
                                    'processing' => 'badge-primary',
                                    'pending'    => 'badge-gray',
                                ];
                            @endphp
                            <span class="{{ $aiStatusColors[$aiStatus] ?? 'badge-gray' }}">
                                {{ ucfirst($aiStatus) }}
                                @if($proposal->ai_attempts > 0)
                                    · {{ $proposal->ai_attempts }} attempt{{ $proposal->ai_attempts !== 1 ? 's' : '' }}
                                @endif
                            </span>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Created by</p>
                            <p class="text-sm text-slate-600">{{ $proposal->user->name ?? 'Unknown' }}</p>
                        </div>
                    </div>
                </div>

                {{-- ── Share Link card (WB-032 AC-30) ──────────────── --}}
                <div class="card overflow-hidden" x-data="{ copied: false }">
                    <div class="card-header">
                        <h3 class="text-sm font-bold text-slate-800">Client Share Link</h3>
                    </div>
                    <div class="card-body space-y-3">
                        {{-- Copy link button (AC-30) --}}
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-1.5">
                                Public URL
                            </p>
                            <div class="flex items-center gap-2">
                                <input type="text" readonly
                                       id="share-url"
                                       value="{{ route('proposals.public.show', $proposal->public_token) }}"
                                       class="flex-1 text-[11px] font-mono text-slate-500 bg-slate-50
                                              border border-slate-200 rounded-lg px-2.5 py-1.5
                                              focus:outline-none focus:ring-2 focus:ring-violet-500/30
                                              truncate min-w-0"
                                       aria-label="Proposal share URL"
                                       data-testid="share-url-input">
                                <button
                                    @click="
                                        navigator.clipboard.writeText($el.previousElementSibling.value)
                                            .then(() => { copied = true; setTimeout(() => copied = false, 2000); });
                                    "
                                    class="shrink-0 inline-flex items-center gap-1 text-xs font-semibold
                                           px-2.5 py-1.5 rounded-lg border transition-colors focus:outline-none
                                           focus-visible:ring-2 focus-visible:ring-violet-500"
                                    :class="copied
                                        ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                                        : 'bg-white border-slate-200 text-slate-600 hover:border-violet-300 hover:text-violet-700'"
                                    :aria-label="copied ? 'Link copied' : 'Copy share link'"
                                    data-testid="copy-link-btn">
                                    <template x-if="!copied">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </template>
                                    <template x-if="copied">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </template>
                                    <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
                                </button>
                            </div>
                        </div>

                        {{-- View stats (AC-5/13/14) --}}
                        @php
                            $totalViews  = $proposal->totalViewCount();
                            $uniqueViews = $proposal->uniqueViewCount();
                        @endphp
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <div class="bg-slate-50 rounded-lg p-2.5 text-center"
                                 data-testid="total-views">
                                <p class="text-lg font-bold text-slate-800">{{ $totalViews }}</p>
                                <p class="text-[10px] text-slate-400 font-medium">Total Views</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-2.5 text-center"
                                 data-testid="unique-views">
                                <p class="text-lg font-bold text-slate-800">{{ $uniqueViews }}</p>
                                <p class="text-[10px] text-slate-400 font-medium">Unique</p>
                            </div>
                        </div>

                        {{-- Last viewed (AC-14) --}}
                        @if($proposal->first_viewed_at)
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">
                                    First Viewed
                                </p>
                                <p class="text-xs text-slate-700">
                                    {{ $proposal->first_viewed_at->format('M j, Y · g:i a') }}
                                </p>
                                <p class="text-[10px] text-slate-400">
                                    {{ $proposal->first_viewed_at->diffForHumans() }}
                                </p>
                            </div>
                            @php
                                $lastView = $proposal->humanViews()->latest('viewed_at')->first();
                            @endphp
                            @if($lastView)
                                <div data-testid="last-viewed">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">
                                        Last Viewed
                                    </p>
                                    <p class="text-xs text-slate-700">
                                        {{ $lastView->viewed_at->format('M j, Y · g:i a') }}
                                    </p>
                                    <p class="text-[10px] text-slate-400">
                                        {{ $lastView->viewed_at->diffForHumans() }}
                                    </p>
                                </div>
                            @endif
                        @endif

                        {{-- Regenerate token (AC-18) --}}
                        <form method="POST"
                              action="{{ route('proposals.regenerate-token', $proposal) }}"
                              onsubmit="return confirm('Regenerate share link?\n\nThe current link will immediately stop working and a new one will be generated. All previous view history will be preserved.')">
                            @csrf
                            <button type="submit"
                                    class="w-full btn-ghost btn-sm text-rose-600 hover:text-rose-700
                                           hover:bg-rose-50 border border-rose-200 hover:border-rose-300
                                           focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
                                    data-testid="regenerate-token-btn">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Regenerate link
                            </button>
                        </form>
                    </div>
                </div>

                {{-- ── View Event Log (WB-032 AC-6) ──────────────── --}}
                @php
                    $viewLog = $proposal->views()->orderByDesc('viewed_at')->limit(20)->get();
                @endphp
                @if($viewLog->isNotEmpty())
                    <div class="card overflow-hidden" data-testid="view-log">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-800">View Log</h3>
                            <span class="badge badge-gray text-[10px]">
                                {{ $proposal->views()->count() }} events
                            </span>
                        </div>
                        <ul class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
                            @foreach($viewLog as $view)
                                <li class="px-4 py-3 {{ $view->is_bot ? 'opacity-50' : '' }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-semibold text-slate-700 truncate">
                                                {{ $view->ip_address ?? 'Unknown IP' }}
                                                @if($view->is_bot)
                                                    <span class="ml-1 text-[9px] font-bold text-amber-600 bg-amber-50
                                                                 border border-amber-200 rounded px-1 py-0.5">BOT</span>
                                                @elseif($view->is_unique)
                                                    <span class="ml-1 text-[9px] font-bold text-violet-600 bg-violet-50
                                                                 border border-violet-200 rounded px-1 py-0.5">NEW</span>
                                                @endif
                                            </p>
                                            @if($view->user_agent)
                                                <p class="text-[10px] text-slate-400 truncate mt-0.5"
                                                   title="{{ $view->user_agent }}">
                                                    {{ Str::limit($view->user_agent, 60) }}
                                                </p>
                                            @endif
                                            {{-- AC-19: flag if this view used an old token --}}
                                            @if($view->token_used !== $proposal->public_token)
                                                <p class="text-[9px] text-rose-500 mt-0.5">via previous link</p>
                                            @endif
                                        </div>
                                        <p class="text-[10px] text-slate-400 shrink-0 whitespace-nowrap">
                                            {{ $view->viewed_at->diffForHumans(null, true) }} ago
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- ── Timeline ──────────────────────────────────── --}}
                <div class="card overflow-hidden">
                    <div class="card-header">
                        <h3 class="text-sm font-bold text-slate-800">Timeline</h3>
                    </div>
                    <div class="card-body space-y-3">
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Created</p>
                            <p class="text-sm text-slate-700">{{ $proposal->created_at->format('M j, Y · g:i a') }}</p>
                            <p class="text-xs text-slate-400">{{ $proposal->created_at->diffForHumans() }}</p>
                        </div>
                        @if($proposal->sent_at)
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Sent</p>
                                <p class="text-sm text-slate-700">{{ $proposal->sent_at->format('M j, Y · g:i a') }}</p>
                                <p class="text-xs text-slate-400">{{ $proposal->sent_at->diffForHumans() }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">Last Modified</p>
                            <p class="text-sm text-slate-700">{{ $proposal->updated_at->format('M j, Y · g:i a') }}</p>
                            <p class="text-xs text-slate-400">{{ $proposal->updated_at->diffForHumans() }}</p>
                        </div>
                        @if($proposal->ai_generated_at)
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-0.5">AI Generated</p>
                                <p class="text-sm text-slate-700">{{ $proposal->ai_generated_at->format('M j, Y · g:i a') }}</p>
                                <p class="text-xs text-slate-400">{{ $proposal->ai_generated_at->diffForHumans() }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Clone CTA --}}
                <div class="rounded-2xl border border-violet-200 bg-violet-50/50 p-4">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-violet-900">Reuse this proposal</p>
                            <p class="text-xs text-violet-700 mt-0.5 leading-relaxed">
                                Clone it as a new Draft — all content is copied, status resets to Draft.
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('proposals.clone', $proposal) }}"
                          onsubmit="return confirm('Clone this proposal?')">
                        @csrf
                        <button type="submit" class="w-full btn-primary btn-sm py-2 text-xs rounded-xl">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Clone as new Draft
                        </button>
                    </form>
                </div>

            </div>
        </div>

    </div>

    {{-- Walnut AI embed (WB-026 AC-13: after main grid) --}}
    @include('proposals.partials.walnut-embed', ['proposal' => $proposal])

    {{-- Print styles --}}
    <style>
        @media print {
            header, nav, aside, footer,
            .btn-primary, .btn-secondary, .btn-danger, .btn-ghost,
            form[action*="clone"], form[action*="destroy"], form[action*="update"],
            [class*="alert-"], .card-footer { display: none !important; }
            .page-section-sm { max-width: 100% !important; padding: 0 !important; }
            .lg\:grid-cols-3 { display: block !important; }
            .lg\:col-span-2  { width: 100% !important; }
            .space-y-4 > * + * { margin-top: 1rem; }
        }
    </style>

</x-app-layout>
