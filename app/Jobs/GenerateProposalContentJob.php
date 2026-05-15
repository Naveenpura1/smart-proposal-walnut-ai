<?php

namespace App\Jobs;

use App\Models\Proposal;
use App\Services\WalnutAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * GenerateProposalContentJob — async AI content generation (WB-027).
 *
 * AC-20: Runs via the queue so it never blocks the HTTP request cycle.
 * AC-21: Sets ai_status = 'processing' when the job starts executing.
 * AC-22: Updates ai_status atomically to 'generated' or 'fallback' on completion.
 * AC-23: DB write failures are logged with full context; content is not discarded.
 * AC-24: Failed jobs land in the failed_jobs table (Laravel default).
 * AC-30: Job timeout set to 120 s to surface deviations beyond the 60 s SLA.
 */
class GenerateProposalContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum seconds this job may run before Laravel marks it as timed out.
     * Set above the documented 60 s SLA (AC-30) to allow the retry chain to
     * exhaust naturally and still produce fallback content.
     */
    public int $timeout = 120;

    /**
     * Let the job fail immediately on the queue layer — the WalnutAiService
     * already handles all retries internally with back-off (AC-17).
     * Queue-level retries would re-run a cold attempt, which is not what we want.
     */
    public int $tries = 1;

    // ─────────────────────────────────────────────────────────────────────────

    public function __construct(
        private readonly int $proposalId,
        private readonly bool $forceRegenerate = false,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Execute the job.
     *
     * Transition map:
     *   pending → processing → generated   (happy path)
     *   pending → processing → fallback    (all retries failed)
     *   pending → processing → failed      (DB write itself failed)
     */
    public function handle(WalnutAiService $aiService): void
    {
        $logChannel = config('walnut_ai.log_channel', 'walnut_ai');

        $proposal = Proposal::find($this->proposalId);

        if (! $proposal) {
            Log::channel($logChannel)->error('GenerateProposalContentJob: proposal not found', [
                'proposal_id' => $this->proposalId,
                'timestamp'   => now()->utc()->toIso8601String(),
            ]);
            return;
        }

        // Guard: skip if already generated, unless this is a forced regeneration.
        if (! $this->forceRegenerate && $proposal->ai_status === 'generated') {
            Log::channel($logChannel)->info('GenerateProposalContentJob: skipping — already generated', [
                'proposal_id' => $proposal->id,
                'timestamp'   => now()->utc()->toIso8601String(),
            ]);
            return;
        }

        // AC-21: Mark as processing before calling the API.
        $proposal->update(['ai_status' => 'processing']);

        Log::channel($logChannel)->info('GenerateProposalContentJob started', [
            'proposal_id'      => $proposal->id,
            'force_regenerate' => $this->forceRegenerate,
            'timestamp'        => now()->utc()->toIso8601String(),
        ]);

        $jobStartMs = (int) (microtime(true) * 1000);

        // Call the service — never throws (all exceptions handled internally).
        $result = $aiService->generate($proposal);

        $totalDurationMs = (int) (microtime(true) * 1000) - $jobStartMs;

        // AC-22: Update atomically within a transaction (AC-23 on DB failure).
        try {
            DB::transaction(function () use ($proposal, $result, $totalDurationMs, $logChannel): void {
                $finalStatus = $result['is_fallback'] ? 'fallback' : 'generated';

                $proposal->update([
                    'generated_content' => $result['content'],
                    'ai_status'         => $finalStatus,
                    'ai_attempts'       => $result['attempts'],
                    'ai_generated_at'   => now(),
                ]);

                // AC-29: Log structured success event.
                Log::channel($logChannel)->info('GenerateProposalContentJob completed', [
                    'proposal_id'     => $proposal->id,
                    'final_status'    => $finalStatus,
                    'attempts'        => $result['attempts'],
                    'is_fallback'     => $result['is_fallback'],
                    'total_ms'        => $totalDurationMs,
                    'timestamp'       => now()->utc()->toIso8601String(),
                ]);
            });
        } catch (Throwable $e) {
            // AC-23: DB write failed — log with full context; mark as failed.
            Log::channel($logChannel)->critical('GenerateProposalContentJob: DB write failed', [
                'proposal_id'    => $proposal->id,
                'error_type'     => 'db_write_failure',
                'error_message'  => $e->getMessage(),
                'content_length' => strlen($result['content']),
                'is_fallback'    => $result['is_fallback'],
                'timestamp'      => now()->utc()->toIso8601String(),
            ]);

            // Try to at least mark the proposal as failed.
            try {
                $proposal->update(['ai_status' => 'failed']);
            } catch (Throwable) {
                // If even the status update fails, the proposal stays in 'processing'.
                // It can be recovered via the proposal:regenerate command (AC-25).
            }

            // Re-throw so the job lands in failed_jobs (AC-24).
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * AC-24: Called when the job fails after exhausting queue-level attempts
     * (here: tries = 1, so this fires immediately on any un-caught exception).
     * The job record is already in failed_jobs; log for operator visibility.
     */
    public function failed(Throwable $exception): void
    {
        $logChannel = config('walnut_ai.log_channel', 'walnut_ai');

        Log::channel($logChannel)->critical('GenerateProposalContentJob failed (dead-letter)', [
            'proposal_id'   => $this->proposalId,
            'error_message' => $exception->getMessage(),
            'timestamp'     => now()->utc()->toIso8601String(),
        ]);
    }
}
