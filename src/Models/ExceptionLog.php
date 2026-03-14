<?php

namespace Phattarachai\ExceptionLog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Phattarachai\ExceptionLog\Support\ContextCollector;
use Throwable;

class ExceptionLog extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $guarded = [];

    public bool $wasReopened = false;

    public ?Carbon $previousLastSeenAt = null;

    protected function casts(): array
    {
        return [
            'line' => 'integer',
            'occurrence_count' => 'integer',
            'is_muted' => 'boolean',
            'context' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public static function fingerprint(Throwable $e): string
    {
        return md5(get_class($e).'|'.$e->getFile().'|'.$e->getLine());
    }

    public static function capture(Throwable $e): self
    {
        $fingerprint = self::fingerprint($e);
        $now = now();
        $context = ContextCollector::collect();

        $existing = self::query()->where('fingerprint', $fingerprint)->first();

        if ($existing) {
            $existing->previousLastSeenAt = $existing->last_seen_at;

            $updates = [
                'message' => mb_substr($e->getMessage(), 0, 65535),
                'trace' => mb_substr($e->getTraceAsString(), 0, 5000),
                'context' => $context,
                'occurrence_count' => $existing->occurrence_count + 1,
                'last_seen_at' => $now,
            ];

            if ($existing->resolved_at !== null) {
                $updates['resolved_at'] = null;
                $existing->wasReopened = true;
            }

            $existing->update($updates);

            return $existing;
        }

        return self::query()->create([
            'fingerprint' => $fingerprint,
            'exception_class' => mb_substr(get_class($e), 0, 500),
            'message' => mb_substr($e->getMessage(), 0, 65535),
            'file' => mb_substr($e->getFile(), 0, 500),
            'line' => $e->getLine(),
            'trace' => mb_substr($e->getTraceAsString(), 0, 5000),
            'context' => $context,
            'occurrence_count' => 1,
            'is_muted' => false,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);
    }

    public function shouldNotify(): bool
    {
        if ($this->is_muted) {
            return false;
        }

        if (! config('exception-log.notify_email')) {
            return false;
        }

        if ($this->occurrence_count === 1) {
            return true;
        }

        if ($this->wasReopened) {
            return true;
        }

        if ($this->previousLastSeenAt !== null) {
            $hours = config('exception-log.re_alert_after_hours', 24);

            if ($this->previousLastSeenAt->diffInHours(now()) >= $hours) {
                return true;
            }
        }

        return $this->isNotificationMilestone($this->occurrence_count);
    }

    private function isNotificationMilestone(int $count): bool
    {
        if ($count < 10) {
            return false;
        }

        if ($count === 10) {
            return true;
        }

        if ($count < 100) {
            return false;
        }

        $magnitude = (int) pow(10, floor(log10($count)));

        return $count % $magnitude === 0;
    }

    public function shortClass(): string
    {
        return class_basename($this->exception_class);
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('resolved_at');
    }

    public function prunable(): Builder
    {
        $days = config('exception-log.retention_days', 90);

        return static::query()->where('last_seen_at', '<=', now()->subDays($days));
    }
}
