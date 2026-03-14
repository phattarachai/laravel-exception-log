<?php

namespace Phattarachai\ExceptionLog\Support;

class ContextCollector
{
    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'secret',
        'api_key',
        'credit_card',
        'cvv',
    ];

    public static function collect(): array
    {
        if (! app()->runningInConsole()) {
            return self::collectHttp();
        }

        $argv = $_SERVER['argv'] ?? [];
        $command = implode(' ', $argv);

        if (isset($argv[1]) && in_array($argv[1], ['queue:work', 'queue:listen'])) {
            return [
                'type' => 'queue',
                'command' => $command,
            ];
        }

        return [
            'type' => 'console',
            'command' => $command,
        ];
    }

    private static function collectHttp(): array
    {
        if (! app()->has('request')) {
            return ['type' => 'unknown'];
        }

        $request = app('request');

        $context = [
            'type' => 'http',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if ($routeName = $request->route()?->getName()) {
            $context['route'] = $routeName;
        }

        if ($userId = $request->user()?->getKey()) {
            $context['user_id'] = $userId;
            $context['user_name'] = $request->user()->name ?? null;
        }

        $input = self::sanitizeInput($request->except(['_token', '_method']));
        if ($input) {
            $context['input'] = $input;
        }

        return $context;
    }

    public static function sanitizeInput(array $input): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            if (is_string($key) && self::isSensitiveKey($key)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $lower = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($lower, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
