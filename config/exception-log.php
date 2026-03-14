<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Exception Logging
    |--------------------------------------------------------------------------
    |
    | Set to false to disable exception logging entirely.
    |
    */
    'enabled' => env('EXCEPTION_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Notification Email
    |--------------------------------------------------------------------------
    |
    | Email address to receive exception notifications.
    | Leave null to disable email notifications.
    |
    */
    'notify_email' => env('EXCEPTION_LOG_NOTIFY_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Retention Days
    |--------------------------------------------------------------------------
    |
    | Number of days to keep exception logs before pruning.
    |
    */
    'retention_days' => env('EXCEPTION_LOG_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | URL prefix for the exception log viewer.
    |
    */
    'route_prefix' => 'exception-logs',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the exception log viewer routes.
    |
    */
    'route_middleware' => ['web', 'auth'],

];
