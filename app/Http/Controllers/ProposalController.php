<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateProposalContentJob;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * ProposalController
 *
 * Every action is gated by ProposalPolicy via $this->authorize().
 * Ownership enforcement is centralised in the policy.
 * The controller never trusts a user-supplied owner ID.
 */
class ProposalController extends Controller
{
    /** Valid business-lifecycle statuses (WB-032 adds 'Viewed'). */
    public const STATUSES = ['Draft', 'Sent', 'Viewed', 'Accepted'];

    /** Sortable columns: URL key → DB column (WB-018 AC-15). */
    private const SORTABLE = [
        'title'    => 'proposal_title',
        'client'   => 'client_name',
        'status'   => 'status',
        'deal'     => 'deal_size',
        'created'  => 'created_at',
        'modified' => 'updated_at',
    ];

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Paginated, searchable, filterable, sortable proposal list (WB-018/WB-019).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Proposal::class);

        $search = trim((string) $request->input('search', '')) ?: null;
        $status = $request->input('status');

        if ($request->has('per_page')) {
            $perPage = (int) $request->input('per_page');
            session(['proposals.per_page' => $perPage]);
        } else {
            $perPage = (int) session('proposals.per_page', 10);
        }
        if (! in_array($perPage, [10, 25, 50])) {
            $perPage = 10;
        }

        $sortKey = $request->input('sort', 'modified');
        $sortDir = $request->input('direction', 'desc');
        if (! array_key_exists($sortKey, self::SORTABLE)) { $sortKey = 'modified'; }
        if (! in_array($sortDir, ['asc', 'desc']))         { $sortDir = 'desc'; }
        $sortColumn = self::SORTABLE[$sortKey];

        $base = auth()->user()->proposals();

        $applySearch = fn ($q) => $q->where(function ($inner) use ($search) {
            $inner->where('proposal_title',  'like', "%{$search}%")
                  ->orWhere('client_name',   'like', "%{$search}%")
                  ->orWhere('client_company', 'like', "%{$search}%")
                  ->orWhere('client_email',  'like', "%{$search}%")
                  ->orWhere('industry',      'like', "%{$search}%");
        });

        try {
            $statusCounts = (clone $base)
                ->when($search, $applySearch)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $totalCount = array_sum($statusCounts);

            $proposals = (clone $base)
                ->when($search, $applySearch)
                ->when(
                    $status && in_array($status, self::STATUSES),
                    fn ($q) => $q->where('status', $status)
                )
                // WB-032 AC-13: eager-load human view counts to avoid N+1
                ->withCount(['views as views_count' => fn ($q) => $q->where('is_bot', false)])
                ->orderBy($sortColumn, $sortDir)
                ->paginate($perPage)
                ->withQueryString();

        } catch (\Throwable $e) {
            Log::error('Proposal list query failed', ['error' => $e->getMessage()]);

            return view('proposals.index', [
                'proposals'    => new LengthAwarePaginator([], 0, $perPage),
                'search'       => $search,
                'status'       => $status,
                'perPage'      => $perPage,
                'sortKey'      => $sortKey,
                'sortDir'      => $sortDir,
                'statusCounts' => [],
                'totalCount'   => 0,
                'loadError'    => true,
            ]);
        }

        return view('proposals.index', compact(
            'proposals', 'search', 'status', 'perPage',
            'sortKey', 'sortDir', 'statusCounts', 'totalCount'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** Show the create form. */
    public function create(): View
    {
        $this->authorize('create', Proposal::class);

        return view('proposals.create');
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Persist a new proposal and dispatch the async AI generation job (WB-027).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Proposal::class);

        $validated = $request->validate([
            'proposal_title' => ['required', 'string', 'max:255'],
            'client_name'    => ['required', 'string', 'max:255'],
            'client_company' => ['required', 'string', 'max:255'],
            'client_email'   => ['required', 'email', 'max:255'],
            'industry'       => ['required', 'string', 'max:255'],
            'pain_points'    => ['required', 'string'],
            'deal_size'      => ['required', 'numeric', 'min:0'],
            'requirements'   => ['nullable', 'string'],
        ]);

        $duplicateExists = auth()->user()
            ->proposals()
            ->where('client_email', $validated['client_email'])
            ->exists();

        $proposal = auth()->user()->proposals()->create(array_merge(
            $validated,
            [
                'status'    => 'Draft',
                'ai_status' => Proposal::AI_PENDING,
            ]
        ));

        Log::channel('security')->info('Proposal created', [
            'user_id'      => auth()->id(),
            'proposal_id'  => $proposal->id,
            'client_name'  => $proposal->client_name,
            'client_email' => $proposal->client_email,
            'ip'           => $request->ip(),
            'timestamp'    => now()->utc()->toIso8601String(),
        ]);

        GenerateProposalContentJob::dispatch($proposal->id);

        Log::channel(config('walnut_ai.log_channel', 'walnut_ai'))->info('GenerateProposalContentJob enqueued', [
            'proposal_id' => $proposal->id,
            'timestamp'   => now()->utc()->toIso8601String(),
        ]);

        $successMsg = $duplicateExists
            ? 'Proposal created. Note: another proposal exists for this client email. AI is generating content…'
            : 'Proposal created — Walnut AI is generating content, it will appear shortly.';

        return redirect()->route('proposals.show', $proposal)
            ->with('success', $successMsg);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** Display a single proposal. */
    public function show(Proposal $proposal): View
    {
        $this->authorize('view', $proposal);

        return view('proposals.show', compact('proposal'));
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** Show the edit form — owner only. */
    public function edit(Proposal $proposal): View
    {
        $this->authorize('update', $proposal);

        return view('proposals.edit', compact('proposal'));
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Persist changes — owner only.
     *
     * WB-026: walnut_embed_url accepted here so users can attach/detach a demo.
     */
    public function update(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);

        $validated = $request->validate([
            'proposal_title'    => ['required', 'string', 'max:255'],
            'client_name'       => ['required', 'string', 'max:255'],
            'client_company'    => ['required', 'string', 'max:255'],
            'client_email'      => ['required', 'email', 'max:255'],
            'industry'          => ['required', 'string', 'max:255'],
            'pain_points'       => ['required', 'string'],
            'deal_size'         => ['required', 'numeric', 'min:0'],
            'status'            => ['required', 'string', 'in:Draft,Sent,Viewed,Accepted'],
            'requirements'      => ['nullable', 'string'],
            'generated_content' => ['nullable', 'string'],
            // WB-026: Walnut AI interactive demo embed URL (AC-12)
            'walnut_embed_url'  => ['nullable', 'url', 'max:2048'],
            'regenerate'        => ['sometimes', 'boolean'],
        ]);

        $wantsRegenerate = $request->boolean('regenerate');
        unset($validated['regenerate']);

        // AC-17 (WB-024): AI regeneration is only permitted for proposals in
        // editable statuses.  Sent and Accepted are considered locked — the
        // edit form already hides / disables the checkbox, but we enforce this
        // server-side as well so it cannot be bypassed via a raw PATCH request.
        $lockedStatuses = ['Sent', 'Accepted'];
        if ($wantsRegenerate && in_array($proposal->status, $lockedStatuses, true)) {
            $wantsRegenerate = false;

            Log::channel(config('walnut_ai.log_channel', 'walnut_ai'))->warning(
                'Regenerate blocked — proposal status is locked',
                [
                    'proposal_id' => $proposal->id,
                    'status'      => $proposal->status,
                    'user_id'     => auth()->id(),
                    'timestamp'   => now()->utc()->toIso8601String(),
                ]
            );
        }

        if ($wantsRegenerate) {
            $validated['ai_status'] = Proposal::AI_PENDING;
        }

        // WB-032 AC-27: record when the proposal is first marked Sent
        if (
            isset($validated['status']) &&
            $validated['status'] === 'Sent' &&
            $proposal->status !== 'Sent' &&
            $proposal->sent_at === null
        ) {
            $validated['sent_at'] = now();
        }

        $proposal->update($validated);

        if ($wantsRegenerate) {
            GenerateProposalContentJob::dispatch($proposal->id, forceRegenerate: true);

            // AC-16 (WB-024): Log the regeneration event with user / proposal context.
            Log::channel(config('walnut_ai.log_channel', 'walnut_ai'))->info('GenerateProposalContentJob enqueued (regenerate)', [
                'proposal_id'  => $proposal->id,
                'triggered_by' => 'edit_form',
                'user_id'      => auth()->id(),
                'timestamp'    => now()->utc()->toIso8601String(),
            ]);

            return redirect()->route('proposals.show', $proposal)
                ->with('success', 'Proposal saved — Walnut AI is regenerating content, it will appear shortly.');
        }

        return redirect()->route('proposals.show', $proposal)
            ->with('success', 'Proposal updated successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** Permanently delete a proposal — owner only. */
    public function destroy(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('delete', $proposal);

        $proposalId    = $proposal->id;
        $proposalLabel = $proposal->proposal_title ?: $proposal->client_name;

        $proposal->delete();

        Log::channel('security')->info('Proposal deleted', [
            'user_id'        => auth()->id(),
            'proposal_id'    => $proposalId,
            'proposal_label' => $proposalLabel,
            'ip'             => $request->ip(),
            'timestamp'      => now()->utc()->toIso8601String(),
        ]);

        $referer = $request->headers->get('referer', '');
        $listUrl = route('proposals.index');

        if ($referer && str_starts_with($referer, $listUrl)) {
            return redirect($referer)->with('success', "\"$proposalLabel\" has been deleted.");
        }

        return redirect()->route('proposals.index')
            ->with('success', "\"$proposalLabel\" has been deleted.");
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** Clone an existing proposal as a new Draft — owner only. */
    public function clone(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('clone', $proposal);

        try {
            $clone = DB::transaction(function () use ($proposal): Proposal {
                return $proposal->cloneFor(auth()->user());
            });
        } catch (\Throwable $e) {
            Log::error('Proposal clone failed', ['source_id' => $proposal->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'The proposal could not be cloned. Please try again.');
        }

        Log::channel('security')->info('Proposal cloned', [
            'user_id'     => auth()->id(),
            'source_id'   => $proposal->id,
            'clone_id'    => $clone->id,
            'clone_token' => $clone->public_token,
            'ip'          => $request->ip(),
            'timestamp'   => now()->utc()->toIso8601String(),
        ]);

        $cloneLabel = $clone->proposal_title ?: $clone->client_name;

        return redirect()
            ->route('proposals.edit', $clone)
            ->with('success', "\"$cloneLabel\" created as a new Draft — you can now edit and customise it.");
    }
}
