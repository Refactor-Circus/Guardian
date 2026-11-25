<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\RecordSecurityEventAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\RecordSecurityEventData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;

/**
 * Records Laravel's sign-in events in the security log (criterion 24): a
 * sign-in, a failed one (with the identifier's HMAC, and the user when one
 * was found), a lockout, a sign-out and a password reset. Off with
 * `guardian.security_log.enabled`.
 */
final class RecordAuthEvents
{
    public function __construct(
        private readonly Repository $config,
        private readonly RecordSecurityEventAction $record,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, fn (Login $event) => $this->record(SecurityEventType::LoginSucceeded, $event->user, context: ['guard' => $event->guard]));
        $events->listen(Failed::class, fn (Failed $event) => $this->record(SecurityEventType::LoginFailed, $event->user, $this->identifier($event->credentials), ['guard' => $event->guard]));
        $events->listen(Lockout::class, fn (Lockout $event) => $this->record(SecurityEventType::LoginLockedOut, null, $this->identifier($event->request->all()), [], $event->request));
        $events->listen(Logout::class, fn (Logout $event) => $this->record(SecurityEventType::Logout, $event->user, context: ['guard' => $event->guard]));
        $events->listen(PasswordReset::class, fn (PasswordReset $event) => $this->record(SecurityEventType::PasswordReset, $event->user));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function record(SecurityEventType $type, ?Authenticatable $user, ?string $identifier = null, array $context = [], ?Request $request = null): void
    {
        if ($this->config->get('guardian.security_log.enabled', true) === false) {
            return;
        }

        $request ??= app()->bound('request') ? app('request') : null;
        $key = $user?->getAuthIdentifier();

        $this->record->execute(new RecordSecurityEventData(
            type: $type,
            user: is_int($key) || is_string($key) ? (string) $key : null,
            identifier: $identifier,
            ip: $request instanceof Request ? $request->ip() : null,
            userAgent: $request instanceof Request ? $request->userAgent() : null,
            context: $context,
        ));
    }

    /**
     * The identifier typed: the first of `guardian.security_log.identifier_keys`
     * given.
     *
     * @param  array<array-key, mixed>  $input
     */
    private function identifier(array $input): ?string
    {
        foreach ((array) $this->config->get('guardian.security_log.identifier_keys', ['email', 'username']) as $key) {
            $value = is_string($key) ? ($input[$key] ?? null) : null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
