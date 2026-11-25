<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a user their account was signed in to from a new device
 * (criterion 37), through `guardian.devices.notify_via`.
 */
final class NewDeviceSignInNotification extends Notification
{
    public function __construct(
        public readonly ?string $device,
        public readonly ?string $ip,
        public readonly CarbonImmutable $at,
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
        return (new MailMessage)
            ->subject(__('guardian::guardian.new_device_subject'))
            ->line(__('guardian::guardian.new_device_line', ['device' => $this->device ?? __('guardian::guardian.unknown_device')]))
            ->line(__('guardian::guardian.new_device_where', ['ip' => $this->ip ?? '-', 'time' => $this->at->toDayDateTimeString()]))
            ->line(__('guardian::guardian.new_device_ignore'));
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'device' => $this->device,
            'ip' => $this->ip,
            'at' => $this->at->toIso8601String(),
        ];
    }
}
