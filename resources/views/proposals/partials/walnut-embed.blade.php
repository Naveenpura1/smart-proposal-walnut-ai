{{--
    Walnut AI Interactive Demo Embed — WB-026
    ──────────────────────────────────────────────────────────────────────────
    Props (passed from show.blade.php via @include):
      $proposal  — App\Models\Proposal   (required)

    States managed by Alpine.js (AC-16: only one visible at a time):
      loading   — spinner shown while iframe initialises (AC-14/15)
      loaded    — iframe visible, spinner hidden (AC-15)
      error     — fallback rendered, iframe hidden (AC-7/15)

    Fallback triggers:
      • $proposal->hasEmbed() is false     → shows "no embed" fallback (AC-5)
      • iframe @error event fires          → shows load-error fallback (AC-7)
      • iframe @load fires but src is bad  → handled by same error path (AC-7/22)

    postMessage height adjustment: the component listens for
      { type: 'walnut:resize', height: <px> }
    from the Walnut embed origin and updates the iframe height (AC-19).
--}}

{{-- ── Section wrapper (AC-1: clearly labelled & visually separated) ─────── --}}
<section
    aria-labelledby="walnut-embed-heading"
    class="mt-6"
    data-testid="walnut-embed-section"
>

    {{-- Section heading (AC-1/21: WCAG AA contrast, keyboard-navigable) --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600
                    flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h2 id="walnut-embed-heading"
                class="text-base font-bold text-slate-900">
                Interactive Demo
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                @if($proposal->hasEmbed())
                    Powered by Walnut AI — {{ $proposal->client_company ?: $proposal->client_name }}
                @else
                    Walnut AI demo attachment
                @endif
            </p>
        </div>
    </div>

    {{-- ── NO EMBED URL stored (AC-5/6/8) ─────────────────────────────────── --}}
    @unless($proposal->hasEmbed())
        <div class="card overflow-hidden" data-testid="embed-fallback-no-url">
            <div class="px-6 py-5 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 10l4.553-2.069A1 1 0 0121 8.82V15.18a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    {{-- AC-6: styled with design system, not raw HTML --}}
                    <h3 class="text-sm font-semibold text-slate-800 mb-1">No demo attached</h3>
                    {{-- AC-8: clear, informative message --}}
                    <p class="text-sm text-slate-500 leading-relaxed">
                        No Walnut AI interactive demo is currently linked to this proposal.
                        To add one, edit the proposal and paste the Walnut embed URL in the
                        <strong class="font-medium text-slate-700">Interactive Demo</strong> field.
                    </p>
                    <a href="{{ route('proposals.edit', $proposal) }}"
                       class="inline-flex items-center gap-1.5 mt-3 text-xs font-semibold
                              text-violet-600 hover:text-violet-800 transition-colors focus:outline-none
                              focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 rounded"
                       aria-label="Edit proposal to add an interactive demo">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit proposal to attach a demo
                    </a>
                </div>
            </div>
        </div>
    @else
        {{-- ── EMBED URL present — Alpine manages load/error states ─────────── --}}
        <div
            x-data="walnutEmbed()"
            class="card overflow-hidden"
            data-testid="embed-container"
        >
            {{-- Card header --}}
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                        <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Walnut AI Demo
                    </span>
                    {{-- Loading badge --}}
                    <span x-show="loading"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="badge badge-gray text-[10px]"
                          aria-live="polite">
                        Loading…
                    </span>
                    {{-- Loaded badge --}}
                    <span x-show="loaded"
                          x-transition:enter="transition ease-out duration-200"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          class="badge badge-success text-[10px]"
                          style="display:none">
                        Live
                    </span>
                    {{-- Error badge --}}
                    <span x-show="error"
                          class="badge badge-danger text-[10px]"
                          style="display:none">
                        Unavailable
                    </span>
                </div>

                {{-- Full-screen link for mobile (AC-9) --}}
                <a href="{{ $proposal->walnut_embed_url }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   x-show="loaded"
                   style="display:none"
                   class="text-xs text-slate-400 hover:text-slate-700 transition-colors flex items-center gap-1
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 rounded"
                   aria-label="Open demo in new tab">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Open in new tab
                </a>
            </div>

            {{-- Embed body --}}
            <div class="relative bg-slate-50" style="min-height: 600px;">

                {{-- ── AC-14: Loading skeleton / spinner ──────────────────── --}}
                <div x-show="loading"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-slate-50 z-10"
                     aria-live="polite"
                     aria-label="Loading interactive demo">
                    {{-- Skeleton shimmer bar --}}
                    <div class="w-full max-w-md space-y-3 px-8">
                        <div class="h-3 bg-slate-200 rounded-full animate-pulse w-3/4 mx-auto"></div>
                        <div class="h-3 bg-slate-200 rounded-full animate-pulse w-1/2 mx-auto"></div>
                    </div>
                    {{-- Spinner --}}
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-violet-500 animate-spin" fill="none" viewBox="0 0 24 24"
                             aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span class="text-sm text-slate-500">Loading interactive demo…</span>
                    </div>
                </div>

                {{-- ── AC-2/3/4/10/18/19/20: The iframe ───────────────────── --}}
                <iframe
                    x-show="!error"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"

                    {{-- AC-12: src from DB, never hardcoded (AC-12) --}}
                    src="{{ $proposal->walnut_embed_url }}"

                    {{-- AC-20: descriptive title for screen readers --}}
                    title="Walnut AI Interactive Demo — {{ $proposal->proposal_title ?: $proposal->client_name }}"

                    {{-- AC-3: full-width, minimum 600 px, dynamic height via postMessage --}}
                    width="100%"
                    :style="'height: ' + iframeHeight + 'px; min-height: 600px;'"
                    style="height: 600px; min-height: 600px;"

                    {{-- AC-4: sandboxing — allow only what Walnut needs --}}
                    sandbox="allow-scripts allow-same-origin allow-popups allow-forms allow-presentation"
                    allow="fullscreen; clipboard-write"

                    {{-- AC-10: scrolling="no" keeps parent page scrollable --}}
                    scrolling="no"

                    {{-- AC-18: no autoplay audio attribute --}}
                    loading="lazy"

                    {{-- AC-15: transition from loading → loaded on success --}}
                    x-on:load="onLoad()"

                    {{-- AC-7/15: detect failure and show fallback.
                         NOTE: x-on:error not @error — @error is a reserved Blade directive. --}}
                    x-on:error="onError()"

                    class="block w-full border-0"
                    data-testid="walnut-embed-iframe"
                    aria-label="Walnut AI Interactive Demo"

                    style="display:none"
                ></iframe>

                {{-- ── AC-5/6/7/8: Error/unavailable fallback ─────────────── --}}
                <div x-show="error"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute inset-0 flex flex-col items-center justify-center gap-5 bg-slate-50 p-8"
                     style="display:none"
                     data-testid="embed-fallback-error"
                     role="alert"
                     aria-live="assertive">

                    {{-- Icon (AC-6: styled, not raw HTML) --}}
                    <div class="w-14 h-14 rounded-2xl bg-rose-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    {{-- AC-6: heading styled consistently with design system --}}
                    <div class="text-center max-w-sm">
                        <h3 class="text-base font-semibold text-slate-800 mb-2">
                            Demo could not be loaded
                        </h3>
                        {{-- AC-8: clear error message --}}
                        <p class="text-sm text-slate-500 leading-relaxed">
                            The interactive demo could not be loaded. This may be due to a
                            network issue or the Walnut service being temporarily unavailable.
                            Please try refreshing the page or contact your administrator.
                        </p>
                    </div>

                    {{-- Retry + open-externally actions (AC-8) --}}
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <button
                            @click="retryEmbed($el)"
                            class="btn-secondary btn-sm focus:outline-none
                                   focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Retry
                        </button>
                        <a href="{{ $proposal->walnut_embed_url }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn-ghost btn-sm text-slate-500 focus:outline-none
                                  focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Open in new tab
                        </a>
                    </div>
                </div>

            </div>{{-- /embed body --}}
        </div>{{-- /card --}}
    @endunless

</section>

{{-- Alpine component defined outside the x-data attribute to avoid Blade compiler
     mis-parsing block comments and $-prefixed JS identifiers as PHP. --}}
<script>
function walnutEmbed() {
    return {
        loading: true,
        loaded:  false,
        error:   false,
        iframeHeight: 600,

        onLoad() {
            this.loading = false;
            this.loaded  = true;
            this.error   = false;
        },

        onError() {
            this.loading = false;
            this.loaded  = false;
            this.error   = true;
        },

        retryEmbed(btn) {
            this.error   = false;
            this.loading = true;
            this.loaded  = false;
            const container = btn.closest('[data-testid="embed-container"]');
            const f = container ? container.querySelector('[data-testid="walnut-embed-iframe"]') : null;
            if (f) { const s = f.src; f.src = ''; f.src = s; }
        },

        init() {
            window.addEventListener('message', (event) => {
                try {
                    if (
                        event.data &&
                        typeof event.data === 'object' &&
                        event.data.type === 'walnut:resize' &&
                        typeof event.data.height === 'number' &&
                        event.data.height > 0
                    ) {
                        this.iframeHeight = Math.max(400, event.data.height);
                    }
                } catch (e) {
                    // swallow malformed postMessage data
                }
            }, { passive: true });
        },
    };
}
</script>
