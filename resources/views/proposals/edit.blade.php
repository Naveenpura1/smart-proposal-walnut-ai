<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('proposals.show', $proposal) }}"
                   class="w-8 h-8 flex items-center justify-center rounded-lg
                          text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Edit Proposal</h2>
                    <p class="text-sm text-slate-500 mt-0.5 truncate max-w-[220px] sm:max-w-none">
                        {{ $proposal->proposal_title ?: $proposal->client_name }}
                    </p>
                </div>
            </div>

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
            <span class="{{ $badgeClass }} text-sm px-3 py-1">
                <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                {{ $proposal->status }}
            </span>
        </div>
    </x-slot>

    <div x-data="{
            dirty:       false,
            regenerate:  false,
            saving:      false,
            regenLocked: {{ in_array($proposal->status, ['Sent', 'Accepted']) ? 'true' : 'false' }},

            init() {
                this.$el.querySelectorAll('input, textarea, select').forEach(el => {
                    el.addEventListener('change', () => { this.dirty = true; });
                    el.addEventListener('input',  () => { this.dirty = true; });
                });
            },

            confirmLeave(event) {
                if (this.dirty && !this.saving) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            },

            /*
             * AC-5:  Validate required AI-generation fields before submitting.
             * AC-7:  Show a confirmation dialog when regenerate is checked.
             * AC-8:  Cancel the dialog → no request sent, proposal unchanged.
             * AC-18: Form is always saved first (the same PATCH request carries
             *        regenerate=1), so AI always receives the latest field values.
             * AC-24: `saving` flag prevents concurrent submissions.
             */
            submitForm() {
                if (this.saving) return;                     // AC-24: deduplicate

                // AC-5: front-end required-field guard for AI generation fields
                if (this.regenerate) {
                    const required = ['proposal_title', 'client_name', 'client_company',
                                      'client_email', 'industry', 'pain_points', 'deal_size'];
                    const missing = required.filter(name => {
                        const el = this.$el.querySelector('[name="' + name + '"]');
                        return !el || !el.value.trim();
                    });
                    if (missing.length) {
                        alert('Please fill in all required fields before regenerating:\n• ' +
                              missing.join('\n• '));
                        return;
                    }

                    // AC-7: Confirm before overwriting existing AI content
                    const confirmed = confirm(
                        'Regenerate AI content?\n\n' +
                        'This will permanently overwrite the existing AI-generated content ' +
                        'with a freshly generated version based on the current field values.\n\n' +
                        'This action cannot be automatically undone. Continue?'
                    );
                    // AC-8: User cancelled — do nothing
                    if (!confirmed) return;
                }

                this.saving = true;
                this.dirty  = false;
            },
        }"
         @beforeunload.window="confirmLeave($event)"
         class="page-section-sm space-y-5">

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

        {{-- Unsaved-changes banner --}}
        <div x-show="dirty && !saving"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="alert-warning"
             style="display:none">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span class="text-amber-800">You have unsaved changes — don't forget to save.</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

            {{-- ═══════════════════════════════════════════════════
                 MAIN FORM (3 / 5 cols)
            ═══════════════════════════════════════════════════ --}}
            <div class="lg:col-span-3">
                <div class="card overflow-hidden">

                    {{-- Gradient header --}}
                    <div class="px-6 py-4 bg-gradient-to-r from-violet-600 to-indigo-600 flex items-center gap-3">
                        <div class="w-9 h-9 bg-white/15 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Proposal Details</p>
                            <p class="text-xs text-white/65">All fields are pre-filled — fields marked <span class="text-rose-300">*</span> are required</p>
                        </div>
                    </div>

                    <form id="edit-form"
                          action="{{ route('proposals.update', $proposal) }}"
                          method="POST"
                          class="p-6 space-y-5"
                          @submit.prevent="submitForm(); if (saving) $el.submit()">
                        @csrf
                        @method('PATCH')

                        {{-- Regenerate flag --}}
                        <input type="hidden" name="regenerate" :value="regenerate ? '1' : '0'">

                        {{-- ── Section 1: Proposal Identity ─────────────────── --}}
                        <div>
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                                Proposal Identity
                            </h4>
                            <div class="form-group">
                                <label for="proposal_title" class="form-label">
                                    Proposal Title <span class="text-rose-500">*</span>
                                </label>
                                <x-text-input
                                    id="proposal_title"
                                    name="proposal_title"
                                    type="text"
                                    placeholder="e.g. Digital Transformation Proposal"
                                    :value="old('proposal_title', $proposal->proposal_title)"
                                    :class="$errors->has('proposal_title') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                    required
                                    autocomplete="off" />
                                <x-input-error :messages="$errors->get('proposal_title')" />
                            </div>
                        </div>

                        {{-- ── Section 2: Client Details ────────────────────── --}}
                        <div class="pt-4 border-t border-slate-100">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                                Client Details
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label for="client_name" class="form-label">
                                        Contact Name <span class="text-rose-500">*</span>
                                    </label>
                                    <x-text-input
                                        id="client_name"
                                        name="client_name"
                                        type="text"
                                        placeholder="e.g. Jane Smith"
                                        :value="old('client_name', $proposal->client_name)"
                                        :class="$errors->has('client_name') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                        required
                                        autocomplete="off" />
                                    <x-input-error :messages="$errors->get('client_name')" />
                                </div>

                                <div class="form-group">
                                    <label for="client_company" class="form-label">
                                        Company <span class="text-rose-500">*</span>
                                    </label>
                                    <x-text-input
                                        id="client_company"
                                        name="client_company"
                                        type="text"
                                        placeholder="e.g. Acme Corporation"
                                        :value="old('client_company', $proposal->client_company)"
                                        :class="$errors->has('client_company') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                        required
                                        autocomplete="off" />
                                    <x-input-error :messages="$errors->get('client_company')" />
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <label for="client_email" class="form-label">
                                    Client Email <span class="text-rose-500">*</span>
                                </label>
                                <x-text-input
                                    id="client_email"
                                    name="client_email"
                                    type="email"
                                    placeholder="jane@acmecorp.com"
                                    :value="old('client_email', $proposal->client_email)"
                                    :class="$errors->has('client_email') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                    required
                                    autocomplete="off" />
                                <x-input-error :messages="$errors->get('client_email')" />
                            </div>
                        </div>

                        {{-- ── Section 3: Deal Context ──────────────────────── --}}
                        <div class="pt-4 border-t border-slate-100">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                                Deal Context
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label for="industry" class="form-label">
                                        Industry / Sector <span class="text-rose-500">*</span>
                                    </label>
                                    <x-text-input
                                        id="industry"
                                        name="industry"
                                        type="text"
                                        placeholder="e.g. SaaS, Healthcare"
                                        :value="old('industry', $proposal->industry)"
                                        :class="$errors->has('industry') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                        required
                                        autocomplete="off" />
                                    <x-input-error :messages="$errors->get('industry')" />
                                </div>

                                <div class="form-group">
                                    <label for="deal_size" class="form-label">
                                        Deal Size (USD) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="input-prefix font-medium text-slate-500">$</span>
                                        <x-text-input
                                            type="number"
                                            id="deal_size"
                                            name="deal_size"
                                            class="input-has-prefix {{ $errors->has('deal_size') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}"
                                            placeholder="50000"
                                            min="0"
                                            step="0.01"
                                            :value="old('deal_size', $proposal->deal_size)"
                                            required />
                                    </div>
                                    <x-input-error :messages="$errors->get('deal_size')" />
                                </div>
                            </div>

                            {{-- Status --}}
                            <div class="form-group mt-4">
                                <label for="status" class="form-label">
                                    Status <span class="text-rose-500">*</span>
                                </label>
                                <select id="status" name="status"
                                        class="form-select {{ $errors->has('status') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}">
                                    @foreach(\App\Http\Controllers\ProposalController::STATUSES as $s)
                                        <option value="{{ $s }}"
                                            {{ old('status', $proposal->status) === $s ? 'selected' : '' }}>
                                            {{ $s }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('status')" />
                            </div>
                        </div>

                        {{-- ── Section 4: AI Context ────────────────────────── --}}
                        <div class="pt-4 border-t border-slate-100">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                                AI Generation Context
                            </h4>

                            {{-- Pain Points --}}
                            <div class="form-group">
                                <label for="pain_points" class="form-label">
                                    Client Pain Points <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    id="pain_points"
                                    name="pain_points"
                                    rows="5"
                                    placeholder="Describe the key challenges, frustrations, or problems the client faces."
                                    class="form-control form-textarea {{ $errors->has('pain_points') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}"
                                    required>{{ old('pain_points', $proposal->pain_points) }}</textarea>
                                <p class="form-hint">Editing pain points and regenerating will produce updated AI content.</p>
                                <x-input-error :messages="$errors->get('pain_points')" />
                            </div>

                            {{-- Requirements (optional) --}}
                            <div class="form-group mt-4">
                                <label for="requirements" class="form-label">
                                    Specific Requirements
                                    <span class="text-slate-400 font-normal ml-1">(optional)</span>
                                </label>
                                <textarea
                                    id="requirements"
                                    name="requirements"
                                    rows="3"
                                    placeholder="Integration needs, constraints, or notes for AI to consider."
                                    class="form-control form-textarea {{ $errors->has('requirements') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}">{{ old('requirements', $proposal->requirements) }}</textarea>
                                <x-input-error :messages="$errors->get('requirements')" />
                            </div>
                        </div>

                        {{-- ── Section 5: Walnut AI Demo (WB-026 AC-12) ───── --}}
                        <div class="pt-4 border-t border-slate-100">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                                Interactive Demo
                            </h4>
                            <div class="form-group">
                                <label for="walnut_embed_url" class="form-label">
                                    Walnut AI Embed URL
                                    <span class="text-slate-400 font-normal ml-1">(optional)</span>
                                </label>
                                <x-text-input
                                    id="walnut_embed_url"
                                    name="walnut_embed_url"
                                    type="url"
                                    placeholder="https://app.walnut.io/embed/…"
                                    :value="old('walnut_embed_url', $proposal->walnut_embed_url)"
                                    :class="$errors->has('walnut_embed_url') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                    autocomplete="off" />
                                <p class="form-hint">
                                    Paste the Walnut AI share / embed URL to display an interactive demo on the proposal detail page.
                                    Leave blank to hide the embed section.
                                </p>
                                <x-input-error :messages="$errors->get('walnut_embed_url')" />
                            </div>
                        </div>

                        {{-- ── AI Content (editable) ───────────────────────── --}}
                        <div class="form-group pt-4 border-t border-slate-100">
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="generated_content" class="form-label !mb-0">
                                    AI-Generated Content
                                </label>
                                <span class="badge badge-primary text-[10px] py-0.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Walnut AI
                                </span>
                            </div>
                            <textarea
                                id="generated_content"
                                name="generated_content"
                                rows="8"
                                placeholder="AI-generated content will appear here. You can edit it directly or regenerate."
                                class="form-control form-textarea font-mono text-xs leading-relaxed {{ $errors->has('generated_content') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}">{{ old('generated_content', $proposal->generated_content) }}</textarea>
                            <p class="form-hint">
                                You can edit the content directly, or check "Regenerate" below to replace it entirely.
                            </p>
                            <x-input-error :messages="$errors->get('generated_content')" />
                        </div>

                        {{-- ── AI Regenerate toggle (WB-024) ──────────────── --}}
                        {{--
                            AC-17: Disabled for Sent / Accepted (locked statuses).
                            AC-22: ARIA labels on the checkbox and locked state.
                            AC-24: `saving` prevents a second click while in-flight.
                        --}}
                        <div class="rounded-xl border p-4 transition-colors"
                             :class="{
                                 'border-violet-400 bg-violet-50':       regenerate && !regenLocked,
                                 'border-violet-200 bg-violet-50/40':    !regenerate && !regenLocked,
                                 'border-slate-200 bg-slate-50 opacity-60': regenLocked,
                             }"
                             role="group"
                             aria-labelledby="regen-label">

                            {{-- Locked notice (AC-17) --}}
                            <div x-show="regenLocked"
                                 class="flex items-start gap-2 mb-3"
                                 role="alert"
                                 aria-live="polite">
                                <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    AI regeneration is disabled for proposals with status
                                    <strong class="font-semibold text-slate-700">{{ $proposal->status }}</strong>.
                                    Change the status to <strong>Draft</strong> to enable regeneration.
                                </p>
                            </div>

                            <label class="flex items-start gap-3 select-none"
                                   :class="regenLocked ? 'cursor-not-allowed' : 'cursor-pointer'">
                                <div class="relative shrink-0 mt-0.5">
                                    <input type="checkbox"
                                           id="regenerate_cb"
                                           class="sr-only peer"
                                           x-model="regenerate"
                                           :disabled="regenLocked || saving"
                                           aria-describedby="regen-hint"
                                           aria-label="Regenerate AI content when saving">
                                    <div class="w-4 h-4 border-2 rounded transition-all"
                                         :class="regenLocked
                                             ? 'border-slate-200 bg-slate-100'
                                             : 'border-slate-300 peer-checked:bg-violet-600 peer-checked:border-violet-600'">
                                    </div>
                                    <svg class="absolute inset-0 w-4 h-4 text-white pointer-events-none"
                                         :class="regenerate && !regenLocked ? 'block' : 'hidden'"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                         aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                              d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <p id="regen-label"
                                       class="text-sm font-semibold"
                                       :class="regenerate && !regenLocked ? 'text-violet-900' : 'text-slate-800'">
                                        Regenerate AI content on save
                                    </p>
                                    <p id="regen-hint" class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                        When checked, the current AI content will be replaced with a freshly
                                        generated version based on the updated pain points and client details.
                                        {{-- AC-7 warning — visible when checkbox is ticked --}}
                                        <span class="font-semibold text-rose-600"
                                              x-show="regenerate && !regenLocked"
                                              aria-live="polite">
                                            Existing AI content will be overwritten — you will be asked to confirm before saving.
                                        </span>
                                    </p>
                                </div>
                            </label>
                        </div>

                        {{-- ── Form Actions ────────────────────────────────── --}}
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 gap-3 flex-wrap">
                            <a href="{{ route('proposals.show', $proposal) }}"
                               class="btn-ghost btn-sm text-slate-500"
                               @click.prevent="
                                   if (dirty && !saving) {
                                       if (confirm('You have unsaved changes. Leave anyway?')) {
                                           dirty = false;
                                           window.location.href = $el.href;
                                       }
                                   } else {
                                       window.location.href = $el.href;
                                   }">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Discard changes
                            </a>

                            {{-- AC-6/22/24: spinner, ARIA busy, disabled while in-flight --}}
                            <button type="submit"
                                    class="btn-primary"
                                    :disabled="saving"
                                    :class="saving ? 'opacity-70 cursor-not-allowed' : ''"
                                    :aria-label="saving
                                        ? 'Saving proposal, please wait…'
                                        : (regenerate ? 'Save proposal and regenerate AI content' : 'Save proposal changes')"
                                    :aria-busy="saving ? 'true' : 'false'"
                                    data-testid="save-btn">
                                <template x-if="!saving">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                         aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="saving">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"
                                         aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor"
                                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </template>
                                <span x-text="saving ? 'Saving…' : (regenerate ? 'Save & Regenerate' : 'Save Changes')">
                                    Save Changes
                                </span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════
                 SIDEBAR (2 / 5 cols)
            ═══════════════════════════════════════════════════ --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Live summary --}}
                <div class="card overflow-hidden">
                    <div class="card-header flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">Proposal Info</h3>
                    </div>
                    <div class="card-body space-y-3 text-sm">
                        <div class="flex justify-between items-center gap-2">
                            <span class="text-slate-400 shrink-0">Status</span>
                            <span class="{{ $badgeClass }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                {{ $proposal->status }}
                            </span>
                        </div>
                        @if($proposal->client_company)
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-slate-400 shrink-0">Company</span>
                                <span class="text-slate-700 text-right truncate">{{ $proposal->client_company }}</span>
                            </div>
                        @endif
                        @if($proposal->client_email)
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-slate-400 shrink-0">Email</span>
                                <a href="mailto:{{ $proposal->client_email }}"
                                   class="text-violet-600 text-xs hover:text-violet-800 transition-colors truncate">
                                    {{ $proposal->client_email }}
                                </a>
                            </div>
                        @endif
                        <div class="flex justify-between items-center gap-2">
                            <span class="text-slate-400 shrink-0">Deal Size</span>
                            <span class="font-semibold text-slate-800 tabular-nums">
                                ${{ number_format($proposal->deal_size, 0) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <span class="text-slate-400 shrink-0">Created</span>
                            <span class="text-slate-600">{{ $proposal->created_at->format('M j, Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <span class="text-slate-400 shrink-0">Last saved</span>
                            <span class="text-slate-600">{{ $proposal->updated_at->diffForHumans() }}</span>
                        </div>
                        @if($proposal->public_token)
                            <div>
                                <p class="text-slate-400 text-xs mb-0.5">Token</p>
                                <p class="font-mono text-[11px] text-slate-400 break-all">{{ $proposal->public_token }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- AI Regenerate explanation --}}
                <div class="rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 p-5 text-white relative overflow-hidden">
                    <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                    <div class="relative">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-bold mb-1">AI Regeneration</p>
                        <p class="text-xs text-white/75 leading-relaxed">
                            Update the pain points or client details, then check
                            <strong class="text-white">"Regenerate AI content on save"</strong>
                            to produce a fresh proposal tailored to the new inputs.
                        </p>
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
                            <p class="text-sm font-semibold text-violet-900">Need a similar proposal?</p>
                            <p class="text-xs text-violet-700 mt-0.5 leading-relaxed">
                                Clone this proposal as a new Draft to reuse these details.
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('proposals.clone', $proposal) }}"
                          onsubmit="return confirm('Clone this proposal?')">
                        @csrf
                        <button type="submit" class="w-full btn-secondary btn-sm py-2 text-xs rounded-xl">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Clone as new Draft
                        </button>
                    </form>
                </div>

                {{-- Danger zone --}}
                <div class="rounded-2xl border border-rose-200 bg-rose-50/30 p-4">
                    <p class="text-sm font-semibold text-rose-800 mb-1">Danger Zone</p>
                    <p class="text-xs text-rose-600 leading-relaxed mb-3">
                        Deleting this proposal is permanent and cannot be undone.
                    </p>
                    <form method="POST" action="{{ route('proposals.destroy', $proposal) }}"
                          onsubmit="return confirm('Delete this proposal permanently? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full btn-danger btn-sm py-2 text-xs rounded-xl">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete Proposal
                        </button>
                    </form>
                </div>

            </div>
        </div>

    </div>

</x-app-layout>
