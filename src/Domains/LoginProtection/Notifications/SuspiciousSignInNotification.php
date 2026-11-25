<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a user about a suspicious sign-in to their account (criterion 51),
 * in place of the new-device notice, through `guardian.devices.notify_via`.
 * Sent even when the new-device notice is off.
 */
final class SuspiciousSignInNotification extends Notification
{
    public function __construct(
        public readonly ?string $device,
        public readonly ?string $ip,
        public readonly CarbonImmutable $at,
        public readonly int $recentFailures,
        public readonly bool $newNetwork,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = config('guardian.devices.notify_via', ['mail']);

        return array_values(array_filter(is_array($channels) ? $channels : ['mail'], is_string(...)));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('guardian::guardian.suspicious_subject'))
            ->line(__('guardian::guardian.suspicious_line', ['device' => $this->device ?? __('guardian::guardian.unknown_device')]))
            ->line(__('guardian::guardian.new_device_where', ['ip' => $this->ip ?? '-', 'time' => $this->at->toDayDateTimeString()]));

        if ($this->recentFailures >= 3) {
            $mail->line(__('guardian::guardian.suspicious_failures', ['count' => $this->recentFailures]));
        }

        if ($this->newNetwork) {
            $mail->line(__('guardian::guardian.suspicious_network'));
        }

        return $mail->line(__('guardian::guardian.suspicious_act'));
    }

    /**
     * @return array<string, string|int|bool|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'device' => $this->device,
            'ip' => $this->ip,
            'at' => $this->at->toIso8601String(),
            'recent_failures' => $this->recentFailures,
            'new_network' => $this->newNetwork,
        ];
    }
}
