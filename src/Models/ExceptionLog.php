<?php

namespace Phattarachai\ExceptionLog\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class ExceptionLog extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'line' => 'integer',
            'occurrence_count' => 'integer',
            'is_muted' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_notified_at' => 'datetime',
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

        $existing = self::query()->where('fingerprint', $fingerprint)->first();

        if ($existing) {
            $existing->update([
                'message' => mb_substr($e->getMessage(), 0, 65535),
                'trace' => mb_substr($e->getTraceAsString(), 0, 5000),
                'occurrence_count' => $existing->occurrence_count + 1,
                'last_seen_at' => $now,
            ]);

            return $existing;
        }

        return self::query()->create([
            'fingerprint' => $fingerprint,
            'exception_class' => mb_substr(get_class($e), 0, 500),
            'message' => mb_substr($e->getMessage(), 0, 65535),
            'file' => mb_substr($e->getFile(), 0, 500),
            'line' => $e->getLine(),
            'trace' => mb_substr($e->getTraceAsString(), 0, 5000),
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

        // Notify on first occurrence
        if ($this->occurrence_count === 1) {
            return true;
        }

        // Notify at powers of 10 (10, 100, 1000, ...)
        return $this->occurrence_count >= 10
            && $this->isPowerOfTen($this->occurrence_count);
    }

    private function isPowerOfTen(int $n): bool
    {
        if ($n < 10) {
            return false;
        }

        while ($n >= 10) {
            if ($n % 10 !== 0) {
                return false;
            }
            $n /= 10;
        }

        return $n === 1;
    }

    public function shortClass(): string
    {
        return class_basename($this->exception_class);
    }

    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        $days = config('exception-log.retention_days', 90);

        return static::query()->where('last_seen_at', '<=', now()->subDays($days));
    }
}
