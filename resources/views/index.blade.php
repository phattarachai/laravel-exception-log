<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exception Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Exception Logs</h1>

        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-700">{{ session('status') }}</p>
            </div>
        @endif

        {{-- Filter Form --}}
        <form action="{{ route('exception-logs.index') }}" method="GET" class="mb-6 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Search message</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..."
                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Exception class</label>
                    <input type="text" name="class" value="{{ request('class') }}" list="exception-classes" placeholder="Class..."
                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <datalist id="exception-classes">
                        @foreach ($exceptionClasses as $class)
                            <option value="{{ $class }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All</option>
                        <option value="unresolved" {{ request('status') === 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="muted" {{ request('status') === 'muted' ? 'selected' : '' }}>Muted</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                    <input type="date" name="from" value="{{ request('from') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                    <input type="date" name="to" value="{{ request('to') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            <div class="mt-3 flex gap-2">
                <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Filter
                </button>
                <a href="{{ route('exception-logs.index') }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Reset
                </a>
            </div>
        </form>

        <div class="bg-white shadow overflow-hidden rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exception</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Seen</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($logs as $log)
                        <tr class="{{ $log->is_muted ? 'opacity-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <a href="{{ route('exception-logs.show', $log) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $log->shortClass() }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-md truncate">
                                {{ Str::limit($log->message, 80) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $log->occurrence_count >= 100 ? 'bg-red-100 text-red-800' : ($log->occurrence_count >= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ number_format($log->occurrence_count) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->last_seen_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($log->resolved_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Resolved</span>
                                @elseif ($log->is_muted)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Muted</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Active</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                <div class="flex items-center justify-center gap-2">
                                    <form action="{{ route('exception-logs.resolve', $log) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:text-blue-900">
                                            {{ $log->resolved_at ? 'Reopen' : 'Resolve' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('exception-logs.toggle-mute', $log) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-gray-600 hover:text-gray-900">
                                            {{ $log->is_muted ? 'Unmute' : 'Mute' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                No exceptions logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</body>
</html>
