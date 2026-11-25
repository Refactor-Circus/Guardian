<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

/**
 * The email carrying a sign-in code. Sent at once, never queued, so the code
 * is not written to a queue.
 */
final class OneTimeCodeNotification extends Notification
{
    public function __construct(
        #[SensitiveParameter] public readonly string $code,
        public readonly int $minutes,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('guardian::guardian.one_time_code_subject'))
            ->line(__('guardian::guardian.one_time_code_line', ['code' => $this->code]))
            ->line(__('guardian::guardian.one_time_code_expiry', ['minutes' => $this->minutes]))
            ->line(__('guardian::guardian.one_time_code_ignore'));
    }
}
