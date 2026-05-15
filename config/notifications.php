<?php

/**
 * Proposal notification settings — WB-035
 *
 * All values are driven by environment variables so they can be adjusted
 * without a code deployment (AC-23).
 *
 * Required .env variables: none (all have safe defaults)
 *
 * Optional .env variables:
 *   PROPOSAL_VIEW_NOTIFY_ENABLED     — master on/off switch (default: true)  (AC-24)
 *   PROPOSAL_VIEW_NOTIFY_THROTTLE    — minimum minutes between notifications
 *                                      per proposal per viewer IP (default: 60)  (WB-034)
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Proposal View Notification (AC-1/24)
    |--------------------------------------------------------------------------
    | Master toggle. Set to false to globally disable view notifications
    | without touching any other system functionality.
    */
    'proposal_view_notify_enabled' => (bool) env('PROPOSAL_VIEW_NOTIFY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Throttle Window — minutes (AC-3/4/23)
    |--------------------------------------------------------------------------
    | Minimum number of minutes that must elapse between two notifications
    | sent to the same Sales Rep for the same proposal from the same client IP.
    |
    | Setting this to 0 disables throttling (every view triggers a notification).
    | Configurable via PROPOSAL_VIEW_NOTIFY_THROTTLE env variable (AC-23).
    */
    'proposal_view_notify_throttle' => (int) env('PROPOSAL_VIEW_NOTIFY_THROTTLE', 60),

    /*
    |--------------------------------------------------------------------------
    | Queue name for notification jobs (AC-2/13)
    |--------------------------------------------------------------------------
    */
    'proposal_view_notify_queue' => env('PROPOSAL_VIEW_NOTIFY_QUEUE', 'default'),

];
