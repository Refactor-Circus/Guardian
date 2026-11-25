<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Channels;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Notification;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\OneTimeCodeChannel;
use RefactorCircus\Guardian\Domains\TwoFactor\Notifications\OneTimeCodeNotification;
use SensitiveParameter;

/**
 * Guardian's default channel: email codes as a mail notification to the
 * enrolled address; no SMS.
 */
final class MailOneTimeCodeChannel implements OneTimeCodeChannel
{
    public function __construct(private readonly Repository $config) {}

    public function supports(string $type): bool
    {
        return $type === 'email';
    }

    public function send(Authenticatable $user, string $type, string $destination, #[SensitiveParameter] string $code): void
    {
        $minutes = $this->config->get('guardian.two_factor.one_time_codes.ttl_minutes', 10);

        Notification::route('mail', $destination)->notifyNow(new OneTimeCodeNotification($code, is_numeric($minutes) ? (int) $minutes : 10));
    }
}
