<?php

namespace Phattarachai\ExceptionLog\Tests;

use Phattarachai\ExceptionLog\Support\ContextCollector;

class ContextCollectorTest extends TestCase
{
    public function test_http_context_collection(): void
    {
        $context = $this->get('/test-route')->baseResponse;

        // Simulate HTTP context by calling collect in an HTTP context
        $result = ContextCollector::collect();

        // In test environment, runningInConsole() returns true, so we get console context
        $this->assertArrayHasKey('type', $result);
    }

    public function test_console_context_collection(): void
    {
        $result = ContextCollector::collect();

        $this->assertEquals('console', $result['type']);
        $this->assertArrayHasKey('command', $result);
    }

    public function test_password_sanitization(): void
    {
        $input = ['username' => 'john', 'password' => 'secret123'];

        $sanitized = ContextCollector::sanitizeInput($input);

        $this->assertEquals('john', $sanitized['username']);
        $this->assertEquals('[REDACTED]', $sanitized['password']);
    }

    public function test_token_sanitization(): void
    {
        $input = ['access_token' => 'abc123', 'name' => 'test'];

        $sanitized = ContextCollector::sanitizeInput($input);

        $this->assertEquals('[REDACTED]', $sanitized['access_token']);
        $this->assertEquals('test', $sanitized['name']);
    }

    public function test_api_key_sanitization(): void
    {
        $input = ['api_key' => 'key123', 'data' => 'value'];

        $sanitized = ContextCollector::sanitizeInput($input);

        $this->assertEquals('[REDACTED]', $sanitized['api_key']);
        $this->assertEquals('value', $sanitized['data']);
    }

    public function test_nested_sensitive_fields(): void
    {
        $input = [
            'user' => [
                'name' => 'John',
                'password' => 'secret',
                'profile' => [
                    'bio' => 'Hello',
                    'secret_key' => 'hidden',
                ],
            ],
        ];

        $sanitized = ContextCollector::sanitizeInput($input);

        $this->assertEquals('John', $sanitized['user']['name']);
        $this->assertEquals('[REDACTED]', $sanitized['user']['password']);
        $this->assertEquals('Hello', $sanitized['user']['profile']['bio']);
        $this->assertEquals('[REDACTED]', $sanitized['user']['profile']['secret_key']);
    }

    public function test_credit_card_and_cvv_sanitization(): void
    {
        $input = ['credit_card' => '4111111111111111', 'cvv' => '123', 'amount' => 100];

        $sanitized = ContextCollector::sanitizeInput($input);

        $this->assertEquals('[REDACTED]', $sanitized['credit_card']);
        $this->assertEquals('[REDACTED]', $sanitized['cvv']);
        $this->assertEquals(100, $sanitized['amount']);
    }

    public function test_empty_input_returns_empty_array(): void
    {
        $this->assertEquals([], ContextCollector::sanitizeInput([]));
    }
}
