<?php

namespace Phattarachai\ExceptionLog\Tests;

use Exception;
use Phattarachai\ExceptionLog\Models\ExceptionLog;

class ExceptionLogTest extends TestCase
{
    public function test_it_captures_an_exception(): void
    {
        $exception = new Exception('Test error');

        $log = ExceptionLog::capture($exception);

        $this->assertDatabaseCount('exception_logs', 1);
        $this->assertEquals(Exception::class, $log->exception_class);
        $this->assertEquals('Test error', $log->message);
        $this->assertEquals(1, $log->occurrence_count);
        $this->assertFalse($log->is_muted);
    }

    public function test_it_increments_count_for_same_exception(): void
    {
        $exception = new Exception('Test error');

        ExceptionLog::capture($exception);
        $log = ExceptionLog::capture($exception);

        $this->assertDatabaseCount('exception_logs', 1);
        $this->assertEquals(2, $log->occurrence_count);
    }

    public function test_it_creates_separate_records_for_different_exceptions(): void
    {
        ExceptionLog::capture(new Exception('Error one'));
        ExceptionLog::capture(new \RuntimeException('Error two'));

        $this->assertDatabaseCount('exception_logs', 2);
    }

    public function test_fingerprint_is_deterministic(): void
    {
        $exception = new Exception('Test');

        $fp1 = ExceptionLog::fingerprint($exception);
        $fp2 = ExceptionLog::fingerprint($exception);

        $this->assertEquals($fp1, $fp2);
        $this->assertEquals(32, strlen($fp1));
    }

    public function test_should_notify_on_first_occurrence(): void
    {
        $log = ExceptionLog::capture(new Exception('Test'));

        config(['exception-log.notify_email' => 'test@example.com']);

        $this->assertTrue($log->shouldNotify());
    }

    public function test_should_not_notify_when_muted(): void
    {
        $log = ExceptionLog::capture(new Exception('Test'));
        $log->update(['is_muted' => true]);

        config(['exception-log.notify_email' => 'test@example.com']);

        $this->assertFalse($log->shouldNotify());
    }

    public function test_should_not_notify_without_email_configured(): void
    {
        $log = ExceptionLog::capture(new Exception('Test'));

        config(['exception-log.notify_email' => null]);

        $this->assertFalse($log->shouldNotify());
    }

    public function test_should_notify_at_powers_of_ten(): void
    {
        config(['exception-log.notify_email' => 'test@example.com']);

        $log = ExceptionLog::capture(new Exception('Test'));

        $log->update(['occurrence_count' => 10]);
        $log->refresh();
        $this->assertTrue($log->shouldNotify());

        $log->update(['occurrence_count' => 100]);
        $log->refresh();
        $this->assertTrue($log->shouldNotify());

        $log->update(['occurrence_count' => 1000]);
        $log->refresh();
        $this->assertTrue($log->shouldNotify());
    }

    public function test_should_not_notify_at_non_power_of_ten(): void
    {
        config(['exception-log.notify_email' => 'test@example.com']);

        $log = ExceptionLog::capture(new Exception('Test'));

        $log->update(['occurrence_count' => 5]);
        $log->refresh();
        $this->assertFalse($log->shouldNotify());

        $log->update(['occurrence_count' => 15]);
        $log->refresh();
        $this->assertFalse($log->shouldNotify());

        $log->update(['occurrence_count' => 50]);
        $log->refresh();
        $this->assertFalse($log->shouldNotify());
    }

    public function test_short_class_returns_basename(): void
    {
        $log = ExceptionLog::capture(new \RuntimeException('Test'));

        $this->assertEquals('RuntimeException', $log->shortClass());
    }

    public function test_message_is_updated_on_recapture(): void
    {
        $exception = new Exception('Original message');
        ExceptionLog::capture($exception);

        // Same fingerprint (same file+line), different message
        $log = ExceptionLog::capture(new Exception('Updated message'));

        $this->assertEquals('Updated message', $log->message);
    }

    public function test_prunable_query_targets_old_records(): void
    {
        config(['exception-log.retention_days' => 30]);

        $old = ExceptionLog::capture(new Exception('Old'));
        $old->update(['last_seen_at' => now()->subDays(31)]);

        $recent = ExceptionLog::capture(new \RuntimeException('Recent'));

        $prunableIds = ExceptionLog::query()
            ->where('last_seen_at', '<=', now()->subDays(30))
            ->pluck('id')
            ->toArray();

        $this->assertContains($old->id, $prunableIds);
        $this->assertNotContains($recent->id, $prunableIds);
    }
}
