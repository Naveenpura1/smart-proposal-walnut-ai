


<section
    aria-labelledby="walnut-embed-heading"
    class="mt-6"
    data-testid="walnut-embed-section"
>

    
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
                <?php if($proposal->hasEmbed()): ?>
                    Powered by Walnut AI — <?php echo e($proposal->client_company ?: $proposal->client_name); ?>

                <?php else: ?>
                    Walnut AI demo attachment
                <?php endif; ?>
            </p>
        </div>
    </div>

    
    <?php if (! ($proposal->hasEmbed())): ?>
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
                    
                    <h3 class="text-sm font-semibold text-slate-800 mb-1">No demo attached</h3>
                    
                    <p class="text-sm text-slate-500 leading-relaxed">
                        No Walnut AI interactive demo is currently linked to this proposal.
                        To add one, edit the proposal and paste the Walnut embed URL in the
                        <strong class="font-medium text-slate-700">Interactive Demo</strong> field.
                    </p>
                    <a href="<?php echo e(route('proposals.edit', $proposal)); ?>"
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
    <?php else: ?>
        
        <div
            x-data="{
                /*
                 * AC-16: exactly one of {loading, loaded, error} is true at a time.
                 * AC-14: loading = true on mount (spinner visible immediately).
                 * AC-15: transitions to loaded or error when iframe resolves.
                 */
                loading: true,
                loaded:  false,
                error:   false,

                /*
                 * AC-19: iframe height starts at 600 px; adjusts via postMessage
                 * from the Walnut embed when it broadcasts a resize event.
                 */
                iframeHeight: 600,

                onLoad() {
                    // AC-15: hide spinner, show iframe
                    this.loading = false;
                    this.loaded  = true;
                    this.error   = false;
                },

                onError() {
                    /*
                     * AC-7:  iframe load failed — replace with formatted fallback.
                     * AC-22: error handled here; no unhandled rejection possible
                     *        because this is a synchronous DOM event handler.
                     */
                    this.loading = false;
                    this.loaded  = false;
                    this.error   = true;
                },

                init() {
                    /*
                     * AC-19: listen for postMessage height updates from the
                     * Walnut embed so we can prevent excessive white space / clipping.
                     * AC-22: wrapped in try/catch to silence any malformed messages.
                     */
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
                        } catch (_) {
                            /* AC-22: swallow malformed postMessage data silently */
                        }
                    }, { passive: true });
                },
            }"
            class="card overflow-hidden"
            data-testid="embed-container"
        >
            
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
                    
                    <span x-show="loading"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="badge badge-gray text-[10px]"
                          aria-live="polite">
                        Loading…
                    </span>
                    
                    <span x-show="loaded"
                          x-transition:enter="transition ease-out duration-200"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          class="badge badge-success text-[10px]"
                          style="display:none">
                        Live
                    </span>
                    
                    <span x-show="error"
                          class="badge badge-danger text-[10px]"
                          style="display:none">
                        Unavailable
                    </span>
                </div>

                
                <a href="<?php echo e($proposal->walnut_embed_url); ?>"
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

            
            <div class="relative bg-slate-50" style="min-height: 600px;">

                
                <div x-show="loading"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-slate-50 z-10"
                     aria-live="polite"
                     aria-label="Loading interactive demo">
                    
                    <div class="w-full max-w-md space-y-3 px-8">
                        <div class="h-3 bg-slate-200 rounded-full animate-pulse w-3/4 mx-auto"></div>
                        <div class="h-3 bg-slate-200 rounded-full animate-pulse w-1/2 mx-auto"></div>
                    </div>
                    
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

                
                <iframe
                    x-show="!error"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"

                    
                    src="<?php echo e($proposal->walnut_embed_url); ?>"

                    
                    title="Walnut AI Interactive Demo — <?php echo e($proposal->proposal_title ?: $proposal->client_name); ?>"

                    
                    width="100%"
                    :style="'height: ' + iframeHeight + 'px; min-height: 600px;'"
                    style="height: 600px; min-height: 600px;"

                    
                    sandbox="allow-scripts allow-same-origin allow-popups allow-forms allow-presentation"
                    allow="fullscreen; clipboard-write"

                    
                    scrolling="no"

                    
                    loading="lazy"

                    
                    @load="onLoad()"

                    
                    <?php $__errorArgs = [];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>="onError()"

                    class="block w-full border-0"
                    data-testid="walnut-embed-iframe"
                    aria-label="Walnut AI Interactive Demo"

                    style="display:none"
                ></iframe>

                
                <div x-show="error"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute inset-0 flex flex-col items-center justify-center gap-5 bg-slate-50 p-8"
                     style="display:none"
                     data-testid="embed-fallback-error"
                     role="alert"
                     aria-live="assertive">

                    
                    <div class="w-14 h-14 rounded-2xl bg-rose-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    
                    <div class="text-center max-w-sm">
                        <h3 class="text-base font-semibold text-slate-800 mb-2">
                            Demo could not be loaded
                        </h3>
                        
                        <p class="text-sm text-slate-500 leading-relaxed">
                            The interactive demo could not be loaded. This may be due to a
                            network issue or the Walnut service being temporarily unavailable.
                            Please try refreshing the page or contact your administrator.
                        </p>
                    </div>

                    
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <button
                            @click="
                                error = false;
                                loading = true;
                                loaded = false;
                                /* Force iframe reload by re-assigning src */
                                const f = $el.closest('[data-testid=embed-container]')
                                             .querySelector('[data-testid=walnut-embed-iframe]');
                                if (f) { const s = f.src; f.src = ''; f.src = s; }
                            "
                            class="btn-secondary btn-sm focus:outline-none
                                   focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Retry
                        </button>
                        <a href="<?php echo e($proposal->walnut_embed_url); ?>"
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

            </div>
        </div>
    <?php endif; ?>

</section>
