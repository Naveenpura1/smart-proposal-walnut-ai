<?php

namespace App\Services;

use App\Models\Proposal;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WalnutAiService — AI content generation via the Walnut AI API (WB-027).
 *
 * Responsibilities:
 *   AC-1:  Dedicated service for AI content generation (single responsibility).
 *   AC-2:  Assembles a structured request payload from the Proposal model.
 *   AC-3:  API key read from config (never hardcoded); omitted from logs (AC-15).
 *   AC-4:  Configurable HTTP timeout (default 30 s) enforced per request.
 *   AC-5:  Parses JSON response and extracts content on HTTP 2xx.
 *   AC-8:  Validates required response fields; treats missing fields as failure.
 *   AC-9:  Non-2xx status codes caught and routed to error-handling flow.
 *   AC-10: Network/DNS exceptions caught and routed to error-handling flow.
 *   AC-11: Timeout exceptions caught; elapsed time logged.
 *   AC-12: Fallback content generated when all retries fail.
 *   AC-13: Fallback distinguished in return value via 'is_fallback' flag.
 *   AC-14: Structured error log on every failure type (via dedicated channel).
 *   AC-15: API key, full PII never written to logs.
 *   AC-16: Dedicated 'walnut_ai' log channel for all AI events.
 *   AC-17: Configurable retry count with exponential back-off.
 *   AC-18: Attempt count returned so caller can persist it.
 *   AC-19: HTTP 429 Retry-After header respected before next retry.
 *   AC-29: Success event logged with duration + usage metadata.
 */
class WalnutAiService
{
    private string $baseUrl;
    private string $apiKey;
    private int    $timeout;
    private int    $maxRetries;
    private int    $retryDelayMs;
    private string $logChannel;

    public function __construct()
    {
        $this->baseUrl      = rtrim((string) config('walnut_ai.base_url', ''), '/');
        $this->apiKey       = (string) config('walnut_ai.api_key', '');
        $this->timeout      = (int)    config('walnut_ai.timeout', 30);
        $this->maxRetries   = max(1,   (int) config('walnut_ai.max_retries', 3));
        $this->retryDelayMs = (int)    config('walnut_ai.retry_delay', 1000);
        $this->logChannel   = (string) config('walnut_ai.log_channel', 'walnut_ai');
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate proposal content for the given Proposal.
     *
     * Returns an array:
     * [
     *   'content'     => string,   // the generated (or fallback) content
     *   'is_fallback' => bool,     // true when fallback content is returned
     *   'attempts'    => int,      // total API call attempts made
     * ]
     *
     * Never throws. All exceptions are caught and handled internally.
     */
    public function generate(Proposal $proposal): array
    {
        $payload  = $this->buildPayload($proposal);
        $attempts = 0;
        $lastError = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            $attempts++;
            $startMs = (int) (microtime(true) * 1000);

            // AC-17: Log each individual attempt.
            Log::channel($this->logChannel)->info('AI generation attempt', [
                'proposal_id' => $proposal->id,
                'attempt'     => $attempt,
                'max_retries' => $this->maxRetries,
                'timestamp'   => now()->utc()->toIso8601String(),
            ]);

            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout($this->timeout)
                    ->acceptJson()
                    ->post($this->baseUrl . config('walnut_ai.endpoints.generate', '/proposals/generate'), $payload);

                $durationMs = (int) (microtime(true) * 1000) - $startMs;

                // AC-9: Handle non-2xx responses.
                if (! $response->successful()) {
                    $statusCode = $response->status();

                    // AC-19: Honour Retry-After on 429.
                    if ($statusCode === 429) {
                        $retryAfter = (int) ($response->header('Retry-After') ?? 0);
                        Log::channel($this->logChannel)->warning('AI API rate-limited (429)', [
                            'proposal_id' => $proposal->id,
                            'attempt'     => $attempt,
                            'retry_after' => $retryAfter,
                            'timestamp'   => now()->utc()->toIso8601String(),
                        ]);
                        if ($retryAfter > 0 && $attempt < $this->maxRetries) {
                            sleep($retryAfter);
                            continue;
                        }
                    }

                    // AC-14: Structured error log for non-2xx.
                    $lastError = "HTTP {$statusCode}";
                    Log::channel($this->logChannel)->error('AI API non-2xx response', [
                        'proposal_id'    => $proposal->id,
                        'attempt'        => $attempt,
                        'error_type'     => 'http_error',
                        'http_status'    => $statusCode,
                        'error_message'  => $lastError,
                        'payload_summary'=> $this->safePayloadSummary($payload),
                        'duration_ms'    => $durationMs,
                        'timestamp'      => now()->utc()->toIso8601String(),
                    ]);

                    $this->backOff($attempt);
                    continue;
                }

                // AC-5: Parse JSON on success.
                $data = $response->json();

                // AC-8: Validate required fields.
                if (! $this->responseIsValid($data)) {
                    $lastError = 'Missing required fields in response';
                    Log::channel($this->logChannel)->error('AI API malformed response', [
                        'proposal_id'    => $proposal->id,
                        'attempt'        => $attempt,
                        'error_type'     => 'malformed_response',
                        'http_status'    => $response->status(),
                        'error_message'  => $lastError,
                        'payload_summary'=> $this->safePayloadSummary($payload),
                        'duration_ms'    => $durationMs,
                        'timestamp'      => now()->utc()->toIso8601String(),
                    ]);

                    $this->backOff($attempt);
                    continue;
                }

                // AC-29: Log success with duration + usage metadata.
                Log::channel($this->logChannel)->info('AI generation succeeded', [
                    'proposal_id' => $proposal->id,
                    'attempt'     => $attempt,
                    'duration_ms' => $durationMs,
                    'usage'       => $data['usage'] ?? null,
                    'timestamp'   => now()->utc()->toIso8601String(),
                ]);

                return [
                    'content'     => $this->extractContent($data),
                    'is_fallback' => false,
                    'attempts'    => $attempts,
                ];

            } catch (ConnectionException $e) {
                // AC-10: Network / DNS / connection refused.
                $durationMs = (int) (microtime(true) * 1000) - $startMs;
                $lastError  = $e->getMessage();
                Log::channel($this->logChannel)->error('AI API network error', [
                    'proposal_id'    => $proposal->id,
                    'attempt'        => $attempt,
                    'error_type'     => 'network_error',
                    'http_status'    => null,
                    'error_message'  => $lastError,
                    'payload_summary'=> $this->safePayloadSummary($payload),
                    'duration_ms'    => $durationMs,
                    'timestamp'      => now()->utc()->toIso8601String(),
                ]);
                $this->backOff($attempt);

            } catch (\Illuminate\Http\Client\Request\ConnectException|\Exception $e) {
                // AC-11: Timeout or any other exception.
                $durationMs = (int) (microtime(true) * 1000) - $startMs;
                $lastError  = $e->getMessage();
                $isTimeout  = str_contains(strtolower($lastError), 'timeout')
                           || str_contains(strtolower($lastError), 'timed out');

                Log::channel($this->logChannel)->{$isTimeout ? 'error' : 'error'}('AI API exception', [
                    'proposal_id'    => $proposal->id,
                    'attempt'        => $attempt,
                    'error_type'     => $isTimeout ? 'timeout' : 'exception',
                    'http_status'    => null,
                    'error_message'  => $lastError,
                    'payload_summary'=> $this->safePayloadSummary($payload),
                    'duration_ms'    => $durationMs,
                    'timestamp'      => now()->utc()->toIso8601String(),
                ]);
                $this->backOff($attempt);
            }
        }

        // AC-12: All retries exhausted — generate fallback content.
        Log::channel($this->logChannel)->warning('AI generation falling back after all retries', [
            'proposal_id' => $proposal->id,
            'attempts'    => $attempts,
            'last_error'  => $lastError,
            'timestamp'   => now()->utc()->toIso8601String(),
        ]);

        return [
            'content'     => $this->buildFallbackContent($proposal),
            'is_fallback' => true,
            'attempts'    => $attempts,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * AC-2: Build the structured API request payload from the Proposal.
     *
     * AC-15: The API key is NOT included in the payload (sent via header).
     *        Client email is included only as much as needed for AI context.
     */
    private function buildPayload(Proposal $proposal): array
    {
        return [
            'proposal_id'     => $proposal->id,
            'proposal_title'  => $proposal->proposal_title,
            'client_name'     => $proposal->client_name,
            'client_company'  => $proposal->client_company,
            'industry'        => $proposal->industry,
            'pain_points'     => $proposal->pain_points,
            'requirements'    => $proposal->requirements,
            'deal_size'       => (float) $proposal->deal_size,
            'tone'            => 'professional',
            'sections'        => ['executive_summary', 'scope_of_work', 'proposed_solution', 'investment'],
        ];
    }

    /**
     * AC-8: Check that the API response contains the minimum required fields.
     */
    private function responseIsValid(mixed $data): bool
    {
        return is_array($data)
            && isset($data['content'])
            && is_string($data['content'])
            && strlen(trim($data['content'])) > 0;
    }

    /**
     * AC-5: Extract the generated content string from the API response.
     */
    private function extractContent(array $data): string
    {
        return trim((string) $data['content']);
    }

    /**
     * AC-12: Build deterministic fallback content so the proposal is never
     *        left empty. Mirrors the placeholder used before this service existed.
     *
     * AC-13: Caller sets ai_status = 'fallback' to distinguish from 'generated'.
     */
    private function buildFallbackContent(Proposal $proposal): string
    {
        $name     = $proposal->client_name;
        $company  = $proposal->client_company ?? $name;
        $industry = $proposal->industry;
        $title    = $proposal->proposal_title ?? "Proposal for {$company}";
        $budget   = '$' . number_format((float) $proposal->deal_size, 0);
        $pain     = $proposal->pain_points;
        $reqs     = $proposal->requirements ?? '';

        $lines = [
            "# {$title}",
            "**Prepared for:** {$name}, {$company}",
            '',
            '## Executive Summary',
            "This proposal has been prepared for {$name} at {$company} operating in the "
                . "{$industry} sector. Our solution is designed to address the challenges "
                . "outlined below and deliver measurable value.",
            '',
            '## Scope of Work',
            "Our engagement with {$company} will include:",
            '',
            "- **Discovery & Assessment** — Review of {$company}'s current state",
            '- **Solution Design** — Architecture addressing the identified challenges',
            '- **Implementation** — Phased delivery with clear milestones',
            "- **Training & Handover** — Knowledge transfer to the {$company} team",
            '- **Post-launch Support** — Dedicated support for a smooth transition',
            '',
            '## Proposed Solution & Approach',
            "Having reviewed the key challenges faced by {$company} in the {$industry} space:",
            '',
            "> {$pain}",
            '',
        ];

        if ($reqs) {
            $lines[] = '**Additional requirements:**';
            $lines[] = "> {$reqs}";
            $lines[] = '';
        }

        $lines = array_merge($lines, [
            '## Investment & Pricing',
            "Total estimated investment: **{$budget}**",
            '',
            '| Phase | Deliverable | Cost |',
            '|-------|-------------|------|',
            '| 1 | Discovery & Design | 20% |',
            '| 2 | Implementation | 60% |',
            '| 3 | Support & Handover | 20% |',
            '',
            '*Payment terms available upon request.*',
            '',
            '---',
            '*Note: This content was generated using the offline fallback template.*',
            '*Please regenerate when the AI service is available for a fully personalised proposal.*',
        ]);

        return implode("\n", $lines);
    }

    /**
     * AC-17: Exponential back-off between retry attempts.
     * Skips sleeping on the last attempt since there will be no next try.
     */
    private function backOff(int $attempt): void
    {
        if ($attempt < $this->maxRetries) {
            $sleepMs = $this->retryDelayMs * (2 ** ($attempt - 1));
            usleep($sleepMs * 1000);
        }
    }

    /**
     * AC-15: Return a safe summary of the payload for logging.
     * Email addresses are masked; deal_size and proposal_id are safe to log.
     */
    private function safePayloadSummary(array $payload): array
    {
        return [
            'proposal_id'    => $payload['proposal_id']    ?? null,
            'proposal_title' => $payload['proposal_title'] ?? null,
            'client_company' => $payload['client_company'] ?? null,
            'industry'       => $payload['industry']       ?? null,
            'deal_size'      => $payload['deal_size']      ?? null,
            // client_name, client_email, pain_points omitted — may contain PII
        ];
    }
}
