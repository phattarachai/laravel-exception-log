<?php

namespace Phattarachai\ExceptionLog\Commands;

use Illuminate\Console\Command;
use Phattarachai\ExceptionLog\Models\ExceptionLog;

class PruneExceptionLogsCommand extends Command
{
    protected $signature = 'exception-log:prune';

    protected $description = 'Prune old exception logs';

    public function handle(): int
    {
        $days = config('exception-log.retention_days', 90);

        $count = ExceptionLog::query()
            ->where('last_seen_at', '<=', now()->subDays($days))
            ->delete();

        $this->info("Pruned {$count} exception log(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
