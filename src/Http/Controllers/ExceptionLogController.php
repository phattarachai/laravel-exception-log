<?php

namespace Phattarachai\ExceptionLog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Phattarachai\ExceptionLog\Models\ExceptionLog;

class ExceptionLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewExceptionLogs');

        $query = ExceptionLog::query();

        if ($request->filled('status')) {
            match ($request->input('status')) {
                'unresolved' => $query->unresolved()->where('is_muted', false),
                'resolved' => $query->resolved(),
                'muted' => $query->where('is_muted', true),
                default => null,
            };
        }

        if ($request->filled('search')) {
            $query->where('message', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('class')) {
            $query->where('exception_class', 'like', '%'.$request->input('class').'%');
        }

        if ($request->filled('from')) {
            $query->where('last_seen_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('last_seen_at', '<=', $request->input('to').' 23:59:59');
        }

        $logs = $query->orderByDesc('last_seen_at')->paginate(25)->withQueryString();

        $exceptionClasses = ExceptionLog::query()
            ->select('exception_class')
            ->distinct()
            ->orderBy('exception_class')
            ->pluck('exception_class');

        return view('exception-log::index', compact('logs', 'exceptionClasses'));
    }

    public function show(ExceptionLog $exceptionLog)
    {
        Gate::authorize('viewExceptionLogs');

        return view('exception-log::show', ['log' => $exceptionLog]);
    }

    public function toggleMute(ExceptionLog $exceptionLog)
    {
        Gate::authorize('viewExceptionLogs');

        $exceptionLog->update(['is_muted' => ! $exceptionLog->is_muted]);

        return back()->with('status', $exceptionLog->is_muted ? 'Exception muted.' : 'Exception unmuted.');
    }

    public function resolve(ExceptionLog $exceptionLog)
    {
        Gate::authorize('viewExceptionLogs');

        $exceptionLog->update([
            'resolved_at' => $exceptionLog->resolved_at ? null : now(),
        ]);

        $status = $exceptionLog->resolved_at ? 'Exception resolved.' : 'Exception reopened.';

        return back()->with('status', $status);
    }

    public function destroy(ExceptionLog $exceptionLog)
    {
        Gate::authorize('viewExceptionLogs');

        $exceptionLog->delete();

        return redirect()->route('exception-logs.index')
            ->with('status', 'Exception log deleted.');
    }
}
