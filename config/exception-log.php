<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions
    |--------------------------------------------------------------------------
    |
    | Exception classes listed here will not be captured.
    | Uses instanceof, so subclasses are also ignored.
    |
    */
    'ignore' => [
        ValidationException::class,
        AuthenticationException::class,
        NotFoundHttpException::class,
        ModelNotFoundException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Re-alert After Quiet Period
    |--------------------------------------------------------------------------
    |
    | Hours of silence after which a recurring exception triggers
    | a new email notification, even if it's not at a milestone count.
    |
    */
    're_alert_after_hours' => env('EXCEPTION_LOG_RE_ALERT_AFTER_HOURS', 24),

];
