<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\RecordSecurityEventAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\RecordSecurityEventData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;

/**
 * Records the two-factor domain's security events through
 * `RecordSecurityEventAction`, with the current request's address. Callers
 * pass only what identifies the factor (its type and id), never a code,
 * secret or destination; the action strips any that slip through.
 */
final class SecurityLog
{
    public function __construct(private readonly RecordSecurityEventAction $record) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(SecurityEventType $type, ?Authenticatable $user, array $context = []): void
    {
        $request = app()->bound('request') ? app('request') : null;

        $this->record->execute(new RecordSecurityEventData(
            type: $type,
            user: $user === null ? null : (string) UserKeys::of($user),
            ip: $request instanceof Request ? $request->ip() : null,
            userAgent: $request instanceof Request ? $request->userAgent() : null,
            context: $context,
        ));
    }
}
