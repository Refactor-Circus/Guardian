<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\RecordSecurityEventAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\RecordSecurityEventData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;

/**
 * Revokes session records (criterion 38): each one still active is marked
 * revoked with a conditional update, so two callers never both count it,
 * and recorded as `session.revoked` for its owner, naming who revoked it.
 */
final class Revocations
{
    public function __construct(private readonly RecordSecurityEventAction $record) {}

    /**
     * @param  iterable<SessionModel>  $sessions
     * @param  array<string, string>  $context  more context for each event
     * @return int the number revoked
     */
    public function revoke(iterable $sessions, ?Authenticatable $actor, array $context = []): int
    {
        $revoked = 0;
        $request = app()->bound('request') ? app('request') : null;

        foreach ($sessions as $session) {
            $now = now();
            $updated = SessionModel::query()->whereKey($session->id)->whereNull('revoked_at')->update(['revoked_at' => $now, 'updated_at' => $now]);

            if ($updated === 0) {
                continue;
            }

            $session->revoked_at = $now;
            $revoked++;

            $this->record->execute(new RecordSecurityEventData(
                type: SecurityEventType::SessionRevoked,
                user: (string) $session->user_id,
                ip: $request instanceof Request ? $request->ip() : null,
                userAgent: $request instanceof Request ? $request->userAgent() : null,
                context: ['session' => $session->id] + $context + ($actor === null ? [] : ['by' => (string) UserKeys::of($actor)]),
            ));
        }

        return $revoked;
    }
}
