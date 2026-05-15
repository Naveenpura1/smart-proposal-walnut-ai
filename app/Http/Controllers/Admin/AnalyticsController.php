<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\ProposalView;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * AnalyticsController — WB-029 / WB-030
 *
 * Platform-wide analytics for admin users:
 *   index()           – main analytics dashboard (AC-1 … AC-25)
 *   exportProposals() – CSV export of filtered proposal breakdown (AC-18)
 *   exportReps()      – CSV export of rep performance table (AC-19)
 */
class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    // ── Allowed sort columns for the proposal breakdown table (AC-9) ──────────

    private const PROPOSAL_SORTS = [
        'title'      => 'proposal_title',
        'rep'        => 'users.name',
        'client'     => 'client_name',
        'status'     => 'status',
        'deal_size'  => 'deal_size',
        'views'      => 'views_count',
        'created'    => 'proposals.created_at',
        'sent'       => 'proposals.sent_at',
        'updated_at' => 'proposals.updated_at',
    ];

    // ── Allowed sort columns for the rep performance table (AC-7) ─────────────

    private const REP_SORTS = [
        'name'        => 'name',
        'total'       => 'total_proposals',
        'sent'        => 'sent_proposals',
        'accepted'    => 'accepted_proposals',
        'open_rate'   => 'open_rate',
        'accept_rate' => 'accept_rate',
        'avg_days'    => 'avg_days_to_accept',
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        // ── AC-4/5: Date-range filter ─────────────────────────────────────────

        [$dateFrom, $dateTo, $datePreset] = $this->resolveDateRange($request);

        // ── AC-2: Platform-wide summary KPIs ─────────────────────────────────

        $baseQuery = fn () => Proposal::query()
            ->when($dateFrom, fn ($q) => $q->where('proposals.created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->where('proposals.created_at', '<=', $dateTo));

        $totalProposals = $baseQuery()->count();
        $totalDraft     = $baseQuery()->where('status', 'Draft')->count();
        $totalSent      = $baseQuery()->whereIn('status', ['Sent', 'Viewed', 'Accepted'])->count();
        $totalAccepted  = $baseQuery()->where('status', 'Accepted')->count();
        $totalViewed    = $baseQuery()->whereIn('status', ['Viewed', 'Accepted'])->count();

        // ── AC-3: Conversion rate ─────────────────────────────────────────────

        $conversionRate = $totalSent > 0
            ? round(($totalAccepted / $totalSent) * 100, 1)
            : 0;

        $openRate = $totalSent > 0
            ? round(($totalViewed / $totalSent) * 100, 1)
            : 0;

        // ── AC-17/22: Currency + avg time-to-acceptance (platform) ────────────

        $totalDealValue = $baseQuery()->sum('deal_size');

        $avgDaysToAccept = $baseQuery()
            ->where('status', 'Accepted')
            ->whereNotNull('sent_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, sent_at, updated_at) / 24.0) as avg_days')
            ->value('avg_days');

        $avgDaysToAccept = $avgDaysToAccept !== null ? round((float) $avgDaysToAccept, 1) : null;

        // Total human (non-bot) views within date range
        $totalViews = ProposalView::where('is_bot', false)
            ->when($dateFrom, fn ($q) => $q->where('viewed_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->where('viewed_at', '<=', $dateTo))
            ->count();

        // ── AC-6/7/8: Rep performance table ───────────────────────────────────

        $repSearch  = trim((string) $request->input('rep_search', ''));
        $repSortCol = $request->input('rep_sort', 'accepted');
        $repSortDir = $request->input('rep_dir', 'desc');

        if (! array_key_exists($repSortCol, self::REP_SORTS)) {
            $repSortCol = 'accepted';
        }
        if (! in_array($repSortDir, ['asc', 'desc'])) {
            $repSortDir = 'desc';
        }

        $repStats = $this->buildRepStats($dateFrom, $dateTo, $repSearch, $repSortCol, $repSortDir);

        // Top performer = rep with most accepted proposals
        $topRep = $repStats
            ->filter(fn ($r) => $r->accepted_proposals > 0)
            ->sortByDesc('accepted_proposals')
            ->first();

        // ── AC-9/10/11/12: Per-proposal breakdown table ───────────────────────

        $statusFilter = $request->input('filter_status', '');
        $repFilter    = $request->input('filter_rep', '');
        $sortCol      = $request->input('sort', 'updated_at');
        $sortDir      = $request->input('direction', 'desc');

        if (! array_key_exists($sortCol, self::PROPOSAL_SORTS)) {
            $sortCol = 'updated_at';
        }
        if (! in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'desc';
        }

        $proposals = $this->buildProposalQuery($dateFrom, $dateTo, $statusFilter, $repFilter)
            ->orderBy(self::PROPOSAL_SORTS[$sortCol], $sortDir)
            ->paginate(25)
            ->withQueryString();

        // List of reps for the "filter by rep" dropdown (AC-11)
        $repList = User::where('role', 'sales')
            ->orderBy('name')
            ->get(['id', 'name']);

        // ── AC-21: Last-updated timestamp ─────────────────────────────────────

        $lastUpdatedAt = now();

        return view('admin.analytics', compact(
            // Date filter
            'dateFrom', 'dateTo', 'datePreset',
            // KPIs
            'totalProposals', 'totalDraft', 'totalSent',
            'totalAccepted', 'totalViewed',
            'conversionRate', 'openRate',
            'totalDealValue', 'totalViews',
            'avgDaysToAccept',
            // Rep table
            'repStats', 'topRep', 'repSearch', 'repSortCol', 'repSortDir',
            // Proposal table
            'proposals', 'sortCol', 'sortDir',
            'statusFilter', 'repFilter', 'repList',
            // Meta
            'lastUpdatedAt',
        ));
    }

    // ── AC-18: CSV export — per-proposal breakdown ────────────────────────────

    public function exportProposals(Request $request): Response
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $statusFilter = $request->input('filter_status', '');
        $repFilter    = $request->input('filter_rep', '');

        $rows = $this->buildProposalQuery($dateFrom, $dateTo, $statusFilter, $repFilter)
            ->orderBy('proposals.created_at', 'desc')
            ->get();

        $csv  = "ID,Title,Rep,Client,Company,Status,Deal Size,Created,Sent,Views\n";
        foreach ($rows as $p) {
            $csv .= implode(',', [
                $p->id,
                $this->csvCell($p->proposal_title ?: $p->client_name),
                $this->csvCell($p->rep_name),
                $this->csvCell($p->client_name),
                $this->csvCell($p->client_company),
                $p->status,
                number_format($p->deal_size, 2, '.', ''),
                $p->created_at?->toDateString() ?? '',
                $p->sent_at?->toDateString() ?? '',
                $p->views_count ?? 0,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="proposals-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    // ── AC-19: CSV export — rep performance ───────────────────────────────────

    public function exportReps(Request $request): Response
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $rows = $this->buildRepStats($dateFrom, $dateTo, '', 'accepted', 'desc');

        $csv = "Name,Email,Total,Sent,Accepted,Open %,Accept %,Avg Days to Accept,Views\n";
        foreach ($rows as $rep) {
            $csv .= implode(',', [
                $this->csvCell($rep->name),
                $this->csvCell($rep->email),
                $rep->total_proposals,
                $rep->sent_proposals,
                $rep->accepted_proposals,
                $rep->open_rate,
                $rep->accept_rate,
                $rep->avg_days_to_accept ?? '',
                $rep->total_views,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="rep-performance-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Resolve the date range from the request.
     * Returns [$dateFrom, $dateTo, $preset].
     *
     * AC-4: preset options (7d, 30d, 90d, custom, all)
     * AC-5: custom range uses start/end date inputs
     */
    private function resolveDateRange(Request $request): array
    {
        $preset = $request->input('date_preset', 'all');

        $dateFrom = null;
        $dateTo   = null;

        switch ($preset) {
            case '7d':
                $dateFrom = now()->subDays(7)->startOfDay();
                $dateTo   = now()->endOfDay();
                break;
            case '30d':
                $dateFrom = now()->subDays(30)->startOfDay();
                $dateTo   = now()->endOfDay();
                break;
            case '90d':
                $dateFrom = now()->subDays(90)->startOfDay();
                $dateTo   = now()->endOfDay();
                break;
            case 'custom':
                $dateFrom = $request->input('date_from')
                    ? Carbon::parse($request->input('date_from'))->startOfDay()
                    : null;
                $dateTo = $request->input('date_to')
                    ? Carbon::parse($request->input('date_to'))->endOfDay()
                    : null;
                break;
            default:
                $preset = 'all';
        }

        return [$dateFrom, $dateTo, $preset];
    }

    /**
     * Build the per-rep statistics collection (used by index + CSV export).
     * AC-6/7/8/22
     */
    private function buildRepStats(
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        string  $search,
        string  $sortCol,
        string  $sortDir
    ) {
        $reps = User::where('role', 'sales')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount([
                'proposals as total_proposals' => fn ($q) => $this->dateScope($q, $dateFrom, $dateTo),
                'proposals as sent_proposals'  => fn ($q) => $this->dateScope($q, $dateFrom, $dateTo)
                    ->whereIn('status', ['Sent', 'Viewed', 'Accepted']),
                'proposals as accepted_proposals' => fn ($q) => $this->dateScope($q, $dateFrom, $dateTo)
                    ->where('status', 'Accepted'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (User $rep) use ($dateFrom, $dateTo) {
                $rep->open_rate = $rep->sent_proposals > 0
                    ? round(
                        ($rep->proposals()
                            ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', $dateFrom))
                            ->when($dateTo,   fn ($q) => $q->where('created_at', '<=', $dateTo))
                            ->whereIn('status', ['Viewed', 'Accepted'])->count()
                        / $rep->sent_proposals) * 100, 1)
                    : 0;

                $rep->accept_rate = $rep->sent_proposals > 0
                    ? round(($rep->accepted_proposals / $rep->sent_proposals) * 100, 1)
                    : 0;

                // AC-22: average days from sent_at → accepted (updated_at as proxy)
                $avgDays = $rep->proposals()
                    ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', $dateFrom))
                    ->when($dateTo,   fn ($q) => $q->where('created_at', '<=', $dateTo))
                    ->where('status', 'Accepted')
                    ->whereNotNull('sent_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, sent_at, updated_at) / 24.0) as avg_days')
                    ->value('avg_days');

                $rep->avg_days_to_accept = $avgDays !== null ? round((float) $avgDays, 1) : null;

                $rep->total_views = ProposalView::where('is_bot', false)
                    ->when($dateFrom, fn ($q) => $q->where('viewed_at', '>=', $dateFrom))
                    ->when($dateTo,   fn ($q) => $q->where('viewed_at', '<=', $dateTo))
                    ->whereIn('proposal_id', $rep->proposals()->pluck('id'))
                    ->count();

                return $rep;
            });

        // Sort by computed column (open_rate, accept_rate, avg_days are computed in PHP)
        $phpSortCols = ['open_rate', 'accept_rate', 'avg_days'];

        if (in_array($sortCol, $phpSortCols, true)) {
            $colMap = [
                'open_rate'   => 'open_rate',
                'accept_rate' => 'accept_rate',
                'avg_days'    => 'avg_days_to_accept',
            ];
            $attr = $colMap[$sortCol];
            $reps = $sortDir === 'asc'
                ? $reps->sortBy($attr, SORT_NATURAL, false)
                : $reps->sortByDesc($attr);
        } else {
            $dbColMap = [
                'name'     => 'name',
                'total'    => 'total_proposals',
                'sent'     => 'sent_proposals',
                'accepted' => 'accepted_proposals',
            ];
            $attr = $dbColMap[$sortCol] ?? 'accepted_proposals';
            $reps = $sortDir === 'asc'
                ? $reps->sortBy($attr, SORT_NATURAL, false)
                : $reps->sortByDesc($attr);
        }

        return $reps->values();
    }

    /**
     * Build the paginate-able proposal breakdown query.
     * AC-9/10/11/12
     */
    private function buildProposalQuery(
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        string  $statusFilter,
        string  $repFilter
    ) {
        $validStatuses = ['Draft', 'Sent', 'Viewed', 'Accepted'];

        return Proposal::select('proposals.*', 'users.name as rep_name')
            ->join('users', 'users.id', '=', 'proposals.user_id')
            ->withCount(['views as views_count' => fn ($q) => $q->where('is_bot', false)])
            ->when($dateFrom, fn ($q) => $q->where('proposals.created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->where('proposals.created_at', '<=', $dateTo))
            ->when(
                $statusFilter && in_array($statusFilter, $validStatuses, true),
                fn ($q) => $q->where('proposals.status', $statusFilter)
            )
            ->when(
                $repFilter && is_numeric($repFilter),
                fn ($q) => $q->where('proposals.user_id', (int) $repFilter)
            );
    }

    /**
     * Apply date scope to a proposal sub-query (used in withCount closures).
     */
    private function dateScope($query, ?Carbon $from, ?Carbon $to)
    {
        return $query
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('created_at', '<=', $to));
    }

    /**
     * Escape a value for CSV output.
     */
    private function csvCell(?string $value): string
    {
        $value = $value ?? '';
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}
