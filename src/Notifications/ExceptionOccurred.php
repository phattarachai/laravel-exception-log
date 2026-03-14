<?php

namespace Phattarachai\ExceptionLog\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Phattarachai\ExceptionLog\Models\ExceptionLog;

class ExceptionOccurred extends Notification
{
    public function __construct(
        public ExceptionLog $log,
        public bool $reopened = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $shortClass = $this->log->shortClass();
        $count = $this->log->occurrence_count;

        $prefix = $this->reopened ? 'REOPENED: ' : '';

        $subject = $count === 1
            ? "[{$appName}] {$prefix}New Exception: {$shortClass}"
            : "[{$appName}] {$prefix}Exception ({$count}x): {$shortClass}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->line("**Exception:** {$this->log->exception_class}")
            ->line("**Message:** {$this->log->message}")
            ->line("**Location:** {$this->log->file}:{$this->log->line}")
            ->line("**Count:** {$count}")
            ->line("**First seen:** {$this->log->first_seen_at}")
            ->line("**Last seen:** {$this->log->last_seen_at}");

        $context = $this->log->context;
        if ($context) {
            $type = $context['type'] ?? 'unknown';
            $mail->line('---');

            if ($type === 'http') {
                $mail->line("**Context:** {$context['method']} {$context['url']}");
            } elseif (in_array($type, ['console', 'queue'])) {
                $mail->line("**Context:** [{$type}] {$context['command']}");
            }
        }

        return $mail;
    }
}
