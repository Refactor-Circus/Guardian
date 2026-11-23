<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;

/**
 * Records `token.issued` and `token.revoked` for the token's owner
 * (criterion 48): the token's id and name and who acted, never its value.
 */
final class TokenLog
{
    public function __construct(private readonly SecurityLog $log) {}

    /**
     * @param  array<string, int|string|bool>  $context
     */
    public function record(SecurityEventType $type, Authenticatable $user, ?Authenticatable $actor, array $context): void
    {
        $this->log->record($type, $user, $context + ($actor === null ? [] : ['by' => (string) UserKeys::of($actor)]));
    }
}
