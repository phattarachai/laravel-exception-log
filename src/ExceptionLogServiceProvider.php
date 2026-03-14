<?php

namespace Phattarachai\ExceptionLog;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Gate;
use Phattarachai\ExceptionLog\Commands\PruneExceptionLogsCommand;
use Phattarachai\ExceptionLog\Models\ExceptionLog;
use Phattarachai\ExceptionLog\Notifications\ExceptionOccurred;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Throwable;

class ExceptionLogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('exception-log')
            ->hasConfigFile()
            ->hasMigration('create_exception_logs_table')
            ->hasViews()
            ->hasRoute('web')
            ->hasCommand(PruneExceptionLogsCommand::class);
    }

    public function bootingPackage(): void
    {
        if (! config('exception-log.enabled', true)) {
            return;
        }

        $this->registerExceptionHandler();

        Gate::define('viewExceptionLogs', fn ($user) => true);
    }

    private function registerExceptionHandler(): void
    {
        $this->app->make(ExceptionHandler::class)
            ->reportable(function (Throwable $e) {
                try {
                    $log = ExceptionLog::capture($e);

                    if ($log->shouldNotify()) {
                        $log->update(['last_notified_at' => now()]);

                        (new AnonymousNotifiable)
                            ->route('mail', config('exception-log.notify_email'))
                            ->notify(new ExceptionOccurred($log));
                    }
                } catch (Throwable) {
                    // Prevent infinite loops — silently ignore logging failures
                }
            });
    }
}
