<x-app-layout>
<<<<<<< HEAD
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create New Proposal') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 p-6 shadow sm:rounded-lg">
                <form action="{{ route('proposals.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="client_name" value="Client Name" />
                        <x-text-input id="client_name" name="client_name" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="industry" value="Industry" />
                        <x-text-input id="industry" name="industry" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="deal_size" value="Deal Size ($)" />
                        <x-text-input type="number" id="deal_size" name="deal_size" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="pain_points" value="Pain Points" />
                        <textarea id="pain_points" name="pain_points" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" rows="4" required></textarea>
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>
                            {{ __('Generate with Walnut AI') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
=======

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('proposals.index') }}"
               class="w-8 h-8 flex items-center justify-center rounded-lg
                      text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
               title="Back to proposals">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900">New Proposal</h2>
                <p class="text-sm text-slate-500 mt-0.5">Fill in the client details — Walnut AI will generate the content</p>
            </div>
        </div>
    </x-slot>

    {{-- ── Alpine component: form state management ─────────────────────── --}}
    <div
        x-data="{
            submitting: false,
            dirty: false,

            /* Field values mirrored for live validation gate (AC-7) */
            proposalTitle:  '{{ old('proposal_title') }}',
            clientName:     '{{ old('client_name') }}',
            clientCompany:  '{{ old('client_company') }}',
            clientEmail:    '{{ old('client_email') }}',
            industry:       '{{ old('industry') }}',
            painPoints:     '{{ old('pain_points') }}',
            dealSize:       '{{ old('deal_size') }}',

            get allMandatoryFilled() {
                return this.proposalTitle.trim()  !== '' &&
                       this.clientName.trim()     !== '' &&
                       this.clientCompany.trim()  !== '' &&
                       this.clientEmail.trim()    !== '' &&
                       this.industry.trim()       !== '' &&
                       this.painPoints.trim()     !== '' &&
                       this.dealSize              !== '';
            },

            submitForm() {
                if (!this.allMandatoryFilled) return;
                this.submitting = true;
                this.dirty = false;
            },

            confirmLeave(event) {
                if (this.dirty && !this.submitting) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            },
        }"
        @beforeunload.window="confirmLeave($event)"
        class="page-section-sm"
    >

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

            {{-- ═══════════════════════════════════════════════════
                 MAIN FORM  (3 / 5 cols)
            ═══════════════════════════════════════════════════ --}}
            <div class="lg:col-span-3">
                <div class="card overflow-hidden">

                    {{-- Gradient header --}}
                    <div class="px-6 py-4 bg-gradient-to-r from-violet-600 to-indigo-600 flex items-center gap-3">
                        <div class="w-9 h-9 bg-white/15 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Proposal Details</p>
                            <p class="text-xs text-white/65">Fields marked <span class="text-rose-300">*</span> are required</p>
                        </div>
                    </div>

                    <form action="{{ route('proposals.store') }}" method="POST"
                          class="p-6 space-y-5"
                          @submit.prevent="submitForm(); $el.submit()">
                        @csrf

                        {{-- ── SECTION 1: Proposal Identity ───────────────── --}}
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
                                    placeholder="e.g. Digital Transformation Proposal for Acme Corp"
                                    :value="old('proposal_title')"
                                    :class="$errors->has('proposal_title') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                    x-model="proposalTitle"
                                    @input="dirty = true"
                                    required
                                    autofocus
                                    autocomplete="off" />
                                <x-input-error :messages="$errors->get('proposal_title')" />
                            </div>
                        </div>

                        {{-- ── SECTION 2: Client Details (AC-2) ───────────── --}}
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
                                        :value="old('client_name')"
                                        :class="$errors->has('client_name') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                        x-model="clientName"
                                        @input="dirty = true"
                                        required
                                        autocomplete="off" />
                                    <x-input-error :messages="$errors->get('client_name')" />
                                </div>

                                <div class="form-group">
                                    <label for="client_company" class="form-label">
                                        Company / Organisation <span class="text-rose-500">*</span>
                                    </label>
                                    <x-text-input
                                        id="client_company"
                                        name="client_company"
                                        type="text"
                                        placeholder="e.g. Acme Corporation"
                                        :value="old('client_company')"
                                        :class="$errors->has('client_company') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                        x-model="clientCompany"
                                        @input="dirty = true"
                                        required
                                        autocomplete="off" />
                                    <x-input-error :messages="$errors->get('client_company')" />
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <label for="client_email" class="form-label">
                                    Client Email Address <span class="text-rose-500">*</span>
                                </label>
                                <x-text-input
                                    id="client_email"
                                    name="client_email"
                                    type="email"
                                    placeholder="jane@acmecorp.com"
                                    :value="old('client_email')"
                                    :class="$errors->has('client_email') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                    x-model="clientEmail"
                                    @input="dirty = true"
                                    required
                                    autocomplete="off" />
                                <p class="form-hint">We'll flag if another proposal already exists for this email address.</p>
                                <x-input-error :messages="$errors->get('client_email')" />
                            </div>
                        </div>

                        {{-- ── SECTION 3: Deal Context (AC-3) ─────────────── --}}
                        <div class="pt-4 border-t border-slate-100">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                                Deal Context <span class="text-slate-300 font-normal normal-case tracking-normal">(used by AI for personalisation)</span>
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
                                        placeholder="e.g. SaaS, Healthcare, Retail"
                                        :value="old('industry')"
                                        :class="$errors->has('industry') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : ''"
                                        x-model="industry"
                                        @input="dirty = true"
                                        required
                                        autocomplete="off" />
                                    <x-input-error :messages="$errors->get('industry')" />
                                </div>

                                <div class="form-group">
                                    <label for="deal_size" class="form-label">
                                        Estimated Budget / Deal Size (USD) <span class="text-rose-500">*</span>
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
                                            step="1"
                                            :value="old('deal_size')"
                                            x-model="dealSize"
                                            @input="dirty = true"
                                            required />
                                    </div>
                                    <x-input-error :messages="$errors->get('deal_size')" />
                                </div>
                            </div>

                            {{-- Pain Points (AC-3 — mandatory for AI) --}}
                            <div class="form-group mt-4">
                                <label for="pain_points" class="form-label">
                                    Client Pain Points &amp; Challenges <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    id="pain_points"
                                    name="pain_points"
                                    rows="5"
                                    placeholder="Describe the key challenges, frustrations, or problems the client faces. The more specific you are, the better the AI output will be."
                                    class="form-control form-textarea {{ $errors->has('pain_points') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}"
                                    x-model="painPoints"
                                    @input="dirty = true"
                                    required>{{ old('pain_points') }}</textarea>
                                <p class="form-hint">
                                    Primary AI generation input — be specific about timelines, scale, and impact.
                                </p>
                                <x-input-error :messages="$errors->get('pain_points')" />
                            </div>

                            {{-- Requirements (AC-3 — optional) --}}
                            <div class="form-group mt-4">
                                <label for="requirements" class="form-label">
                                    Specific Requirements or Notes
                                    <span class="text-slate-400 font-normal ml-1">(optional)</span>
                                </label>
                                <textarea
                                    id="requirements"
                                    name="requirements"
                                    rows="3"
                                    placeholder="Any specific requirements, constraints, preferences, or notes you want the AI to consider (e.g. 'must integrate with Salesforce', 'prefer phased delivery')."
                                    class="form-control form-textarea {{ $errors->has('requirements') ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' : '' }}"
                                    @input="dirty = true">{{ old('requirements') }}</textarea>
                                <x-input-error :messages="$errors->get('requirements')" />
                            </div>
                        </div>

                        {{-- ── Form Actions (AC-4/AC-7/AC-8/AC-17) ─────────── --}}
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100 gap-3 flex-wrap">

                            {{-- AC-17: Cancel with confirmation if dirty --}}
                            <button type="button"
                                    class="btn-ghost btn-sm text-slate-500"
                                    @click="
                                        if (dirty) {
                                            if (confirm('Discard this proposal? All entered data will be lost.')) {
                                                dirty = false;
                                                window.location.href = '{{ route('proposals.index') }}';
                                            }
                                        } else {
                                            window.location.href = '{{ route('proposals.index') }}';
                                        }
                                    ">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Discard
                            </button>

                            {{-- AC-7: Button disabled until all mandatory fields filled
                                 AC-8: Loading state on submit --}}
                            <button
                                type="submit"
                                class="btn-primary"
                                :disabled="!allMandatoryFilled || submitting"
                                :class="{
                                    'opacity-50 cursor-not-allowed': !allMandatoryFilled,
                                    'opacity-70 cursor-wait': submitting,
                                }">

                                {{-- Idle: not all filled --}}
                                <template x-if="!allMandatoryFilled && !submitting">
                                    <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </template>

                                {{-- Idle: all filled --}}
                                <template x-if="allMandatoryFilled && !submitting">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </template>

                                {{-- AC-8: Spinner while submitting --}}
                                <template x-if="submitting">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor"
                                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </template>

                                <span x-text="submitting
                                    ? 'Generating proposal…'
                                    : (allMandatoryFilled ? 'Generate with Walnut AI' : 'Fill required fields to continue')">
                                    Generate with Walnut AI
                                </span>
                            </button>
                        </div>

                        {{-- AC-11: Progress hint shown while submitting --}}
                        <div x-show="submitting"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="rounded-xl bg-violet-50 border border-violet-200 px-4 py-3 flex items-center gap-3"
                             style="display:none">
                            <svg class="w-4 h-4 text-violet-500 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-violet-900">Walnut AI is generating your proposal…</p>
                                <p class="text-xs text-violet-600 mt-0.5">This usually takes a few seconds. Please don't close or refresh the page.</p>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════
                 SIDEBAR  (2 / 5 cols)
            ═══════════════════════════════════════════════════ --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- How it works --}}
                <div class="rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 p-5 text-white relative overflow-hidden">
                    <div class="absolute -top-8 -right-8 w-36 h-36 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                    <div class="relative">
                        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-bold mb-3">How Walnut AI works</p>
                        <ol class="space-y-2.5">
                            @foreach([
                                'Fill in the client and deal details',
                                'Walnut AI analyses the pain points and context',
                                'A 4-section structured proposal is generated',
                                'Review, edit the content, and mark as Sent',
                            ] as $i => $step)
                                <li class="flex items-start gap-2.5">
                                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center
                                                 text-white text-[11px] font-bold shrink-0 mt-0.5">{{ $i + 1 }}</span>
                                    <span class="text-xs text-white/80 leading-relaxed">{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                {{-- What AI generates --}}
                <div class="card overflow-hidden">
                    <div class="card-header flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">AI generates 4 sections</h3>
                    </div>
                    <div class="card-body">
                        <ul class="space-y-2.5">
                            @foreach([
                                ['Executive Summary',             'High-level overview tailored to the client'],
                                ['Scope of Work',                 'Phased deliverables and engagement structure'],
                                ['Proposed Solution & Approach',  'Directly addresses the stated pain points'],
                                ['Investment & Pricing',          'Budget breakdown using the deal size provided'],
                            ] as [$section, $desc])
                                <li class="flex items-start gap-2.5">
                                    <div class="w-5 h-5 rounded bg-violet-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-violet-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">{{ $section }}</p>
                                        <p class="text-xs text-slate-400">{{ $desc }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="card p-5">
                    <p class="text-sm font-semibold text-slate-800 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                  d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                                  clip-rule="evenodd"/>
                        </svg>
                        Tips for better results
                    </p>
                    <ul class="space-y-2">
                        @foreach([
                            'Be specific about the client\'s main challenges and business impact',
                            'Include industry context — sector-specific language improves quality',
                            'Mention timeline, urgency, or scale if known',
                            'Use the Requirements field for integration needs or constraints',
                        ] as $tip)
                            <li class="flex items-start gap-2 text-xs text-slate-600">
                                <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                          clip-rule="evenodd"/>
                                </svg>
                                {{ $tip }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Required fields reminder --}}
                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 flex items-start gap-3">
                    <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Fields marked <span class="text-rose-500 font-bold">*</span> are required.
                        The Generate button activates once all mandatory fields are filled.
                        Your entered data is preserved if validation fails.
                    </p>
                </div>

            </div>
        </div>
    </div>

</x-app-layout>
>>>>>>> 9ad783d (Initial commit)
