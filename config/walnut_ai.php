<?php

/**
 * Walnut AI API configuration (WB-027).
 *
 * All values are driven by environment variables so they are never hardcoded
 * in source code and can differ per environment (AC-3, AC-28).
 *
 * Required env variables:
 *   WALNUT_AI_API_KEY    — secret API key; never commit this value (AC-3)
 *   WALNUT_AI_BASE_URL   — API base URL (may differ between sandbox and production)
 *
 * Optional env variables (have safe defaults documented below):
 *   WALNUT_AI_TIMEOUT     — HTTP request timeout in seconds (default: 30 — AC-4)
 *   WALNUT_AI_MAX_RETRIES — total retry attempts before fallback (default: 3 — AC-17)
 *   WALNUT_AI_RETRY_DELAY — base delay between retries in milliseconds (default: 1000)
 *                           Each subsequent retry doubles the delay (exponential back-off).
 *   WALNUT_AI_LOG_CHANNEL — log channel for AI-specific events (default: walnut_ai — AC-16)
 */
return [

    /*
    |--------------------------------------------------------------------------
    | API Credentials (AC-3)
    |--------------------------------------------------------------------------
    | The API key is read exclusively from the environment and must never be
    | hardcoded in this file or committed to version control.
    */
    'api_key'  => env('WALNUT_AI_API_KEY', ''),
    'base_url' => env('WALNUT_AI_BASE_URL', 'https://api.walnut.ai/v1'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (AC-4)
    |--------------------------------------------------------------------------
    | Default: 30 seconds. Configurable so that staging environments can use
    | shorter timeouts and production can tolerate slower AI responses.
    |
    | SLA note: the full generation flow (enqueue → storage) is expected to
    | complete under 60 seconds in normal conditions (AC-30). This timeout
    | applies per individual HTTP request, not the entire retry chain.
    */
    'timeout' => (int) env('WALNUT_AI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Policy (AC-17)
    |--------------------------------------------------------------------------
    | max_retries : maximum number of attempts (first attempt + retries).
    |               E.g. 3 means 1 initial call + 2 retries.
    | retry_delay : base delay in milliseconds before the first retry.
    |               Doubles on each subsequent retry (exponential back-off).
    */
    'max_retries' => (int) env('WALNUT_AI_MAX_RETRIES', 3),
    'retry_delay' => (int) env('WALNUT_AI_RETRY_DELAY', 1000),

    /*
    |--------------------------------------------------------------------------
    | Dedicated Log Channel (AC-16)
    |--------------------------------------------------------------------------
    | All AI generation events (success, error, retry, fallback) are written
    | to this channel so they can be monitored separately from the main log.
    */
    'log_channel' => env('WALNUT_AI_LOG_CHANNEL', 'walnut_ai'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoint Paths
    |--------------------------------------------------------------------------
    | Paths relative to base_url for each operation.
    */
    'endpoints' => [
        'generate' => '/proposals/generate',
    ],

];
