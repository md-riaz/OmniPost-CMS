<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Define thresholds for triggering system alerts
    |
    */

    'alert_threshold_failed_jobs' => env('ALERT_THRESHOLD_FAILED_JOBS', 5),
    'alert_threshold_queue_depth' => env('ALERT_THRESHOLD_QUEUE_DEPTH', 100),
    
    /*
    |--------------------------------------------------------------------------
    | Alert Channels
    |--------------------------------------------------------------------------
    |
    | Configure where alerts should be sent
    |
    */

    'alert_email' => env('ALERT_EMAIL', 'admin@omnipost.local'),
    'alert_slack_webhook' => env('ALERT_SLACK_WEBHOOK'),
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure platform-specific rate limits
    |
    */

    'rate_limits' => [
        'facebook' => [
            'limit' => 200,
            'window' => 'hour',
        ],
        'linkedin' => [
            'limit' => 500,
            'window' => 'day',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Configure circuit breaker behavior
    |
    */

    'circuit_breaker' => [
        'failure_threshold' => 5,
        'timeout' => 300, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Validation Limits
    |--------------------------------------------------------------------------
    |
    | Configure media upload limits per platform
    |
    */

    'media_limits' => [
        'facebook' => [
            'image_max_size' => 4 * 1024 * 1024, // 4MB
            'video_max_size' => 1024 * 1024 * 1024, // 1GB
            'min_width' => 600,
            'min_height' => 315,
        ],
        'linkedin' => [
            'image_max_size' => 5 * 1024 * 1024, // 5MB
            'document_max_size' => 5 * 1024 * 1024, // 5MB
            'min_width' => 552,
            'min_height' => 368,
        ],
    ],
];
