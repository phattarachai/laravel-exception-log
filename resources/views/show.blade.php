<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exception: {{ $log->shortClass() }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="{{ route('exception-logs.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to list</a>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $log->shortClass() }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <form action="{{ route('exception-logs.toggle-mute', $log) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        {{ $log->is_muted ? 'Unmute' : 'Mute' }}
                    </button>
                </form>
                <form action="{{ route('exception-logs.destroy', $log) }}" method="POST" onsubmit="return confirm('Delete this exception log?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                        Delete
                    </button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-700">{{ session('status') }}</p>
            </div>
        @endif

        <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
            <dl>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Class</dt>
                    <dd class="text-sm text-gray-900 col-span-2 font-mono">{{ $log->exception_class }}</dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4 bg-gray-50">
                    <dt class="text-sm font-medium text-gray-500">Message</dt>
                    <dd class="text-sm text-gray-900 col-span-2">{{ $log->message }}</dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Location</dt>
                    <dd class="text-sm text-gray-900 col-span-2 font-mono">{{ $log->file }}:{{ $log->line }}</dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4 bg-gray-50">
                    <dt class="text-sm font-medium text-gray-500">Fingerprint</dt>
                    <dd class="text-sm text-gray-500 col-span-2 font-mono">{{ $log->fingerprint }}</dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Occurrences</dt>
                    <dd class="text-sm text-gray-900 col-span-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $log->occurrence_count >= 100 ? 'bg-red-100 text-red-800' : ($log->occurrence_count >= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ number_format($log->occurrence_count) }}
                        </span>
                    </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4 bg-gray-50">
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="text-sm col-span-2">
                        @if ($log->is_muted)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Muted</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Active</span>
                        @endif
                    </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">First Seen</dt>
                    <dd class="text-sm text-gray-900 col-span-2">{{ $log->first_seen_at->format('Y-m-d H:i:s') }} ({{ $log->first_seen_at->diffForHumans() }})</dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4 bg-gray-50">
                    <dt class="text-sm font-medium text-gray-500">Last Seen</dt>
                    <dd class="text-sm text-gray-900 col-span-2">{{ $log->last_seen_at->format('Y-m-d H:i:s') }} ({{ $log->last_seen_at->diffForHumans() }})</dd>
                </div>
                @if ($log->last_notified_at)
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">Last Notified</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $log->last_notified_at->format('Y-m-d H:i:s') }} ({{ $log->last_notified_at->diffForHumans() }})</dd>
                    </div>
                @endif
            </dl>
        </div>

        @if ($log->trace)
            <div class="mt-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Stack Trace</h2>
                </div>
                <div class="p-6">
                    <pre class="text-xs text-gray-700 bg-gray-50 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap break-words">{{ $log->trace }}</pre>
                </div>
            </div>
        @endif
    </div>
</body>
</html>
