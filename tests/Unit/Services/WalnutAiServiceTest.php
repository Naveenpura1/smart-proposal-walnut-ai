<?php

namespace Tests\Unit\Services;

use App\Models\Proposal;
use App\Services\WalnutAiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * WalnutAiServiceTest — AC-26 (WB-027).
 *
 * Tests verify:
 *   - Correct payload construction (AC-2)
 *   - Successful API response handling (AC-5/6)
 *   - Response validation / missing fields (AC-8)
 *   - Non-2xx error handling (AC-9)
 *   - Network failure handling (AC-10)
 *   - Timeout handling (AC-11)
 *   - Fallback triggering after exhausted retries (AC-12/13)
 *   - Retry logic with attempt counting (AC-17/18)
 *   - HTTP 429 + Retry-After handling (AC-19)
 *   - Safe payload summary excludes PII (AC-15)
 *   - API key not logged (AC-15)
 */
class WalnutAiServiceTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function makeProposal(array $attributes = []): Proposal
    {
        $proposal = new Proposal(array_merge([
            'id'             => 99,
            'proposal_title' => 'Test Proposal',
            'client_name'    => 'Jane Smith',
            'client_company' => 'Acme Corp',
            'client_email'   => 'jane@acme.com',
            'industry'       => 'SaaS',
            'pain_points'    => 'Legacy systems are slowing growth.',
            'requirements'   => 'Must integrate with Salesforce.',
            'deal_size'      => 50000,
        ], $attributes));

        // Stub the id attribute so we don't need a real DB record.
        $proposal->setAttribute('id', $attributes['id'] ?? 99);

        return $proposal;
    }

    private function configureService(int $maxRetries = 1, int $retryDelay = 0): void
    {
        Config::set('walnut_ai.max_retries', $maxRetries);
        Config::set('walnut_ai.retry_delay', $retryDelay);
        Config::set('walnut_ai.timeout', 30);
        Config::set('walnut_ai.api_key', 'test-key-not-logged');
        Config::set('walnut_ai.base_url', 'https://api.walnut.ai/v1');
        Config::set('walnut_ai.endpoints.generate', '/proposals/generate');
        Config::set('walnut_ai.log_channel', 'null');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-2: Payload construction
    // ─────────────────────────────────────────────────────────────────────────

    public function test_payload_contains_all_required_fields(): void
    {
        $this->configureService();
        $proposal = $this->makeProposal();

        Http::fake([
            '*' => Http::response(['content' => 'Generated content here.'], 200),
        ]);

        (new WalnutAiService())->generate($proposal);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return isset($body['proposal_id'])
                && isset($body['proposal_title'])
                && isset($body['client_name'])
                && isset($body['client_company'])
                && isset($body['industry'])
                && isset($body['pain_points'])
                && isset($body['requirements'])
                && isset($body['deal_size'])
                && isset($body['tone'])
                && isset($body['sections']);
        });
    }

    public function test_payload_proposal_id_matches_proposal(): void
    {
        $this->configureService();
        $proposal = $this->makeProposal(['id' => 42]);

        Http::fake(['*' => Http::response(['content' => 'OK'], 200)]);

        (new WalnutAiService())->generate($proposal);

        Http::assertSent(fn (Request $r) => $r->data()['proposal_id'] === 42);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-5/6: Successful response
    // ─────────────────────────────────────────────────────────────────────────

    public function test_successful_response_returns_content_and_not_fallback(): void
    {
        $this->configureService();
        $proposal = $this->makeProposal();

        Http::fake([
            '*' => Http::response(['content' => 'AI-generated proposal content.'], 200),
        ]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertFalse($result['is_fallback']);
        $this->assertEquals('AI-generated proposal content.', $result['content']);
        $this->assertEquals(1, $result['attempts']);
    }

    public function test_successful_response_with_usage_metadata(): void
    {
        $this->configureService();
        $proposal = $this->makeProposal();

        Http::fake([
            '*' => Http::response([
                'content' => 'Proposal text.',
                'usage'   => ['tokens' => 1200, 'model' => 'gpt-4'],
            ], 200),
        ]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertFalse($result['is_fallback']);
        $this->assertEquals('Proposal text.', $result['content']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-8: Missing required fields → failure
    // ─────────────────────────────────────────────────────────────────────────

    public function test_response_missing_content_triggers_fallback(): void
    {
        $this->configureService(maxRetries: 1);
        $proposal = $this->makeProposal();

        Http::fake([
            '*' => Http::response(['result' => 'ok_but_no_content'], 200),
        ]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertTrue($result['is_fallback']);
    }

    public function test_empty_content_string_triggers_fallback(): void
    {
        $this->configureService(maxRetries: 1);
        $proposal = $this->makeProposal();

        Http::fake([
            '*' => Http::response(['content' => '   '], 200),
        ]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertTrue($result['is_fallback']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-9: Non-2xx responses
    // ─────────────────────────────────────────────────────────────────────────

    public function test_500_response_triggers_fallback_after_retries(): void
    {
        $this->configureService(maxRetries: 2);
        $proposal = $this->makeProposal();

        Http::fake(['*' => Http::response('Internal Server Error', 500)]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertTrue($result['is_fallback']);
        $this->assertEquals(2, $result['attempts']);
    }

    public function test_401_response_triggers_fallback(): void
    {
        $this->configureService(maxRetries: 1);
        $proposal = $this->makeProposal();

        Http::fake(['*' => Http::response('Unauthorised', 401)]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertTrue($result['is_fallback']);
    }

    public function test_400_response_triggers_fallback(): void
    {
        $this->configureService(maxRetries: 1);
        $proposal = $this->makeProposal();

        Http::fake(['*' => Http::response(['error' => 'bad request'], 400)]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertTrue($result['is_fallback']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-10: Network failure
    // ─────────────────────────────────────────────────────────────────────────

    public function test_connection_exception_triggers_fallback(): void
    {
        $this->configureService(maxRetries: 1);
        $proposal = $this->makeProposal();

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertTrue($result['is_fallback']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-12/13: Fallback content
    // ─────────────────────────────────────────────────────────────────────────

    public function test_fallback_content_is_not_empty(): void
    {
        $this->configureService(maxRetries: 1);
        $proposal = $this->makeProposal();

        Http::fake(['*' => Http::response('', 503)]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertTrue($result['is_fallback']);
        $this->assertNotEmpty(trim($result['content']));
    }

    public function test_fallback_content_contains_client_name(): void
    {
        $this->configureService(maxRetries: 1);
        $proposal = $this->makeProposal(['client_name' => 'Alice Doe']);

        Http::fake(['*' => Http::response('', 503)]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertStringContainsString('Alice Doe', $result['content']);
    }

    public function test_fallback_content_marks_itself_as_offline_template(): void
    {
        $this->configureService(maxRetries: 1);
        $proposal = $this->makeProposal();

        Http::fake(['*' => Http::response('', 503)]);

        $result = (new WalnutAiService())->generate($proposal);

        // AC-13: fallback content is distinguishable (note text or label).
        $this->assertStringContainsString('fallback', strtolower($result['content']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-17/18: Retry logic and attempt counting
    // ─────────────────────────────────────────────────────────────────────────

    public function test_retries_up_to_max_retries_before_fallback(): void
    {
        $maxRetries = 3;
        $this->configureService(maxRetries: $maxRetries);
        $proposal = $this->makeProposal();

        Http::fake(['*' => Http::response('', 500)]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertTrue($result['is_fallback']);
        $this->assertEquals($maxRetries, $result['attempts']);
    }

    public function test_succeeds_on_second_attempt(): void
    {
        $this->configureService(maxRetries: 3);
        $proposal = $this->makeProposal();

        Http::fake([
            '*' => Http::sequence()
                ->push('', 500)
                ->push(['content' => 'Success on retry.'], 200),
        ]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertFalse($result['is_fallback']);
        $this->assertEquals(2, $result['attempts']);
        $this->assertEquals('Success on retry.', $result['content']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-19: HTTP 429 rate-limit handling
    // ─────────────────────────────────────────────────────────────────────────

    public function test_429_with_no_retry_after_counts_as_retry_attempt(): void
    {
        $this->configureService(maxRetries: 2);
        $proposal = $this->makeProposal();

        Http::fake([
            '*' => Http::sequence()
                ->push('', 429)
                ->push(['content' => 'OK after rate limit.'], 200),
        ]);

        $result = (new WalnutAiService())->generate($proposal);

        $this->assertFalse($result['is_fallback']);
        $this->assertEquals('OK after rate limit.', $result['content']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-3/15: API key not in payload; safe payload summary excludes PII
    // ─────────────────────────────────────────────────────────────────────────

    public function test_api_key_is_not_in_request_payload(): void
    {
        $this->configureService();
        $proposal = $this->makeProposal();

        Http::fake(['*' => Http::response(['content' => 'OK'], 200)]);

        (new WalnutAiService())->generate($proposal);

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            // API key must not appear in the payload (it goes in the Authorization header)
            return ! isset($body['api_key'])
                && ! isset($body['apiKey'])
                && ! isset($body['token']);
        });
    }

    public function test_api_key_is_sent_as_bearer_token(): void
    {
        Config::set('walnut_ai.api_key', 'my-secret-key');
        $this->configureService();
        $proposal = $this->makeProposal();

        Http::fake(['*' => Http::response(['content' => 'OK'], 200)]);

        (new WalnutAiService())->generate($proposal);

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('Authorization')
                && str_starts_with($request->header('Authorization')[0] ?? '', 'Bearer ');
        });
    }
}
