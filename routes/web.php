<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Phattarachai\ExceptionLog\Models\ExceptionLog;

Route::middleware(config('exception-log.route_middleware', ['web', 'auth']))
    ->prefix(config('exception-log.route_prefix', 'exception-logs'))
    ->group(function () {

        Route::get('/', function () {
            Gate::authorize('viewExceptionLogs');

            $logs = ExceptionLog::query()
                ->orderByDesc('last_seen_at')
                ->paginate(25);

            return view('exception-log::index', compact('logs'));
        })->name('exception-logs.index');

        Route::get('/{exceptionLog}', function (ExceptionLog $exceptionLog) {
            Gate::authorize('viewExceptionLogs');

            return view('exception-log::show', ['log' => $exceptionLog]);
        })->name('exception-logs.show');

        Route::post('/{exceptionLog}/toggle-mute', function (ExceptionLog $exceptionLog) {
            Gate::authorize('viewExceptionLogs');

            $exceptionLog->update(['is_muted' => ! $exceptionLog->is_muted]);

            return back()->with('status', $exceptionLog->is_muted ? 'Exception muted.' : 'Exception unmuted.');
        })->name('exception-logs.toggle-mute');

        Route::delete('/{exceptionLog}', function (ExceptionLog $exceptionLog) {
            Gate::authorize('viewExceptionLogs');

            $exceptionLog->delete();

            return redirect()->route('exception-logs.index')
                ->with('status', 'Exception log deleted.');
        })->name('exception-logs.destroy');
    });
