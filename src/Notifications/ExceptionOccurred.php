<?php

namespace Phattarachai\ExceptionLog\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Phattarachai\ExceptionLog\Models\ExceptionLog;

class ExceptionOccurred extends Notification
{
    public function __construct(public ExceptionLog $log) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $shortClass = $this->log->shortClass();
        $count = $this->log->occurrence_count;

        $subject = $count === 1
            ? "[{$appName}] New Exception: {$shortClass}"
            : "[{$appName}] Exception ({$count}x): {$shortClass}";

        return (new MailMessage)
            ->subject($subject)
            ->line("**Exception:** {$this->log->exception_class}")
            ->line("**Message:** {$this->log->message}")
            ->line("**Location:** {$this->log->file}:{$this->log->line}")
            ->line("**Count:** {$count}")
            ->line("**First seen:** {$this->log->first_seen_at}")
            ->line("**Last seen:** {$this->log->last_seen_at}");
    }
}
