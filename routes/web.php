<?php

use Illuminate\Support\Facades\Route;
use Phattarachai\ExceptionLog\Http\Controllers\ExceptionLogController;

Route::middleware(config('exception-log.route_middleware', ['web', 'auth']))
    ->prefix(config('exception-log.route_prefix', 'exception-logs'))
    ->group(function () {

        Route::get('/', [ExceptionLogController::class, 'index'])->name('exception-logs.index');
        Route::get('/{exceptionLog}', [ExceptionLogController::class, 'show'])->name('exception-logs.show');
        Route::post('/{exceptionLog}/toggle-mute', [ExceptionLogController::class, 'toggleMute'])->name('exception-logs.toggle-mute');
        Route::post('/{exceptionLog}/resolve', [ExceptionLogController::class, 'resolve'])->name('exception-logs.resolve');
        Route::delete('/{exceptionLog}', [ExceptionLogController::class, 'destroy'])->name('exception-logs.destroy');
    });
