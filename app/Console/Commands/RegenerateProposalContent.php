<?php

namespace App\Console\Commands;

use App\Jobs\GenerateProposalContentJob;
use App\Models\Proposal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * RegenerateProposalContent — AC-25 (WB-027).
 *
 * Allows an operator to re-queue one or more proposals for AI content
 * generation without duplicating existing successful records.
 *
 * Usage:
 *   # Regenerate a single proposal by ID
 *   php artisan proposals:regenerate --id=42
 *
 *   # Regenerate all proposals in 'failed' or 'pending' ai_status
 *   php artisan proposals:regenerate --failed
 *   php artisan proposals:regenerate --pending
 *
 *   # Force-regenerate ALL proposals (including already-generated ones)
 *   php artisan proposals:regenerate --all --force
 *
 * AC-25: Will not re-queue proposals that already have ai_status = 'generated'
 *        unless --force is explicitly supplied, preventing duplicates.
 */
class RegenerateProposalContent extends Command
{
    protected $signature = 'proposals:regenerate
                            {--id=     : Proposal ID to regenerate}
                            {--failed  : Re-queue all proposals with ai_status = failed}
                            {--pending : Re-queue all proposals with ai_status = pending or processing}
                            {--all     : Re-queue all proposals}
                            {--force   : Allow re-queuing of already-generated proposals}';

    protected $description = 'Re-queue proposals for AI content generation (WB-027 AC-25)';

    public function handle(): int
    {
        $forceRegenerate = (bool) $this->option('force');
        $queued          = 0;
        $skipped         = 0;

        // ── Single proposal by ID ──────────────────────────────────────────
        if ($id = $this->option('id')) {
            $proposal = Proposal::find((int) $id);

            if (! $proposal) {
                $this->error("Proposal #{$id} not found.");
                return self::FAILURE;
            }

            if (! $forceRegenerate && $proposal->ai_status === Proposal::AI_GENERATED) {
                $this->warn("Proposal #{$id} already has ai_status = 'generated'. Use --force to override.");
                return self::SUCCESS;
            }

            GenerateProposalContentJob::dispatch($proposal->id, forceRegenerate: true);
            $this->info("Proposal #{$proposal->id} queued for AI generation.");
            $this->logDispatch($proposal, $forceRegenerate);
            return self::SUCCESS;
        }

        // ── Bulk operations ────────────────────────────────────────────────
        $query = Proposal::query();

        if ($this->option('failed')) {
            $query->where('ai_status', Proposal::AI_FAILED);
            $this->info('Queuing all proposals with ai_status = failed…');
        } elseif ($this->option('pending')) {
            $query->whereIn('ai_status', [Proposal::AI_PENDING, Proposal::AI_PROCESSING]);
            $this->info('Queuing all proposals with ai_status = pending / processing…');
        } elseif ($this->option('all')) {
            if (! $forceRegenerate) {
                $query->where('ai_status', '!=', Proposal::AI_GENERATED);
            }
            $this->info('Queuing all proposals' . ($forceRegenerate ? ' (including already-generated)' : ' (skipping generated)') . '…');
        } else {
            $this->error('Specify --id=N, --failed, --pending, or --all.');
            return self::FAILURE;
        }

        $query->chunkById(100, function ($proposals) use ($forceRegenerate, &$queued, &$skipped): void {
            foreach ($proposals as $proposal) {
                if (! $forceRegenerate && $proposal->ai_status === Proposal::AI_GENERATED) {
                    $skipped++;
                    continue;
                }

                GenerateProposalContentJob::dispatch($proposal->id, forceRegenerate: true);
                $this->logDispatch($proposal, $forceRegenerate);
                $queued++;
            }
        });

        $this->info("Done — {$queued} proposal(s) queued, {$skipped} skipped (already generated).");

        return self::SUCCESS;
    }

    private function logDispatch(Proposal $proposal, bool $forceRegenerate): void
    {
        Log::channel(config('walnut_ai.log_channel', 'walnut_ai'))->info('proposals:regenerate dispatched job', [
            'proposal_id'      => $proposal->id,
            'force_regenerate' => $forceRegenerate,
            'triggered_by'     => 'artisan_command',
            'timestamp'        => now()->utc()->toIso8601String(),
        ]);
    }
}
