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

    public function test_should_notify_at_milestone_counts(): void
    {
        config(['exception-log.notify_email' => 'test@example.com']);

        $log = ExceptionLog::capture(new Exception('Test'));

        // Power of 10
        $log->update(['occurrence_count' => 10]);
        $log->refresh();
        $this->assertTrue($log->shouldNotify());

        // Every 100 at the hundreds level
        $log->update(['occurrence_count' => 100]);
        $log->refresh();
        $this->assertTrue($log->shouldNotify());

        $log->update(['occurrence_count' => 200]);
        $log->refresh();
        $this->assertTrue($log->shouldNotify());

        // Every 1000 at the thousands level
        $log->update(['occurrence_count' => 1000]);
        $log->refresh();
        $this->assertTrue($log->shouldNotify());

        $log->update(['occurrence_count' => 2000]);
        $log->refresh();
        $this->assertTrue($log->shouldNotify());
    }

    public function test_should_not_notify_at_non_milestone_counts(): void
    {
        config(['exception-log.notify_email' => 'test@example.com']);

        $log = ExceptionLog::capture(new Exception('Test'));

        $nonMilestones = [5, 15, 50, 99, 150, 250, 1500, 2500];

        foreach ($nonMilestones as $count) {
            $log->update(['occurrence_count' => $count]);
            $log->refresh();
            $this->assertFalse($log->shouldNotify(), "Count {$count} should not be a milestone");
        }
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

    public function test_context_stored_on_capture(): void
    {
        $log = ExceptionLog::capture(new Exception('Test'));

        $this->assertNotNull($log->context);
        $this->assertIsArray($log->context);
        $this->assertArrayHasKey('type', $log->context);
    }

    public function test_context_updated_on_recapture(): void
    {
        $exception = new Exception('Test');

        $log1 = ExceptionLog::capture($exception);

        $log2 = ExceptionLog::capture($exception);

        $this->assertNotNull($log2->context);
        $this->assertArrayHasKey('type', $log2->context);
    }

    public function test_new_exception_is_unresolved(): void
    {
        $log = ExceptionLog::capture(new Exception('Test'));

        $this->assertNull($log->resolved_at);
    }

    public function test_resolved_exception_reopens_on_recapture(): void
    {
        $exception = new Exception('Test');

        $log = ExceptionLog::capture($exception);
        $log->update(['resolved_at' => now()]);

        $recaptured = ExceptionLog::capture($exception);

        $this->assertNull($recaptured->resolved_at);
        $this->assertTrue($recaptured->wasReopened);
    }

    public function test_reopened_exception_triggers_notification(): void
    {
        config(['exception-log.notify_email' => 'test@example.com']);

        $exception = new Exception('Test');

        $log = ExceptionLog::capture($exception);
        $log->update(['resolved_at' => now()]);

        $recaptured = ExceptionLog::capture($exception);

        $this->assertTrue($recaptured->shouldNotify());
    }

    public function test_notify_after_quiet_period(): void
    {
        config([
            'exception-log.notify_email' => 'test@example.com',
            'exception-log.re_alert_after_hours' => 24,
        ]);

        $exception = new Exception('Test');

        $log = ExceptionLog::capture($exception);
        $log->update(['last_seen_at' => now()->subHours(25)]);

        $recaptured = ExceptionLog::capture($exception);

        $this->assertTrue($recaptured->shouldNotify());
    }

    public function test_no_notify_within_quiet_period(): void
    {
        config([
            'exception-log.notify_email' => 'test@example.com',
            'exception-log.re_alert_after_hours' => 24,
        ]);

        $exception = new Exception('Test');

        $log = ExceptionLog::capture($exception);
        $log->update(['last_seen_at' => now()->subHours(2)]);

        $recaptured = ExceptionLog::capture($exception);

        // Count is 2 — not a milestone, and only 2h since last seen
        $this->assertFalse($recaptured->shouldNotify());
    }

    public function test_unresolved_scope(): void
    {
        $unresolved = ExceptionLog::capture(new Exception('Unresolved'));
        $resolved = ExceptionLog::capture(new \RuntimeException('Resolved'));
        $resolved->update(['resolved_at' => now()]);

        $results = ExceptionLog::query()->unresolved()->pluck('id')->toArray();

        $this->assertContains($unresolved->id, $results);
        $this->assertNotContains($resolved->id, $results);
    }

    public function test_resolved_scope(): void
    {
        $unresolved = ExceptionLog::capture(new Exception('Unresolved'));
        $resolved = ExceptionLog::capture(new \RuntimeException('Resolved'));
        $resolved->update(['resolved_at' => now()]);

        $results = ExceptionLog::query()->resolved()->pluck('id')->toArray();

        $this->assertContains($resolved->id, $results);
        $this->assertNotContains($unresolved->id, $results);
    }
}
