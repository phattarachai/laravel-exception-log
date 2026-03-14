# Laravel Exception Log

Log exceptions to database with fingerprinted deduplication, smart email alerts (first occurrence + powers of 10), and mute capability.

## Installation

```bash
composer require phattarachai/laravel-exception-log
```

Publish and run migrations:

```bash
php artisan vendor:publish --provider="Phattarachai\ExceptionLog\ExceptionLogServiceProvider" --tag="exception-log-migrations"
php artisan migrate
```

Optionally publish config:

```bash
php artisan vendor:publish --provider="Phattarachai\ExceptionLog\ExceptionLogServiceProvider" --tag="exception-log-config"
```

## Configuration

Add to your `.env`:

```env
EXCEPTION_LOG_ENABLED=true
EXCEPTION_LOG_NOTIFY_EMAIL=your@email.com
EXCEPTION_LOG_RETENTION_DAYS=90
```

## Usage

The package automatically captures all reported exceptions. No code changes needed.

### Email Notifications

Notifications are sent:
- On **first occurrence** of a new exception
- At **powers of 10** (10th, 100th, 1000th, ...)
- Only if `notify_email` is configured and the exception is not muted

### Admin UI

Visit `/exception-logs` to view all logged exceptions. Requires authentication by default.

Customize the gate in your `AuthServiceProvider`:

```php
Gate::define('viewExceptionLogs', fn ($user) => $user->isAdmin());
```

### Pruning

```bash
php artisan exception-log:prune
```

Or use Laravel's built-in model pruning:

```bash
php artisan model:prune --model="Phattarachai\ExceptionLog\Models\ExceptionLog"
```

### Publishing Views

```bash
php artisan vendor:publish --provider="Phattarachai\ExceptionLog\ExceptionLogServiceProvider" --tag="exception-log-views"
```

## License

MIT
