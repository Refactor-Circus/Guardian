<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Observers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Password\Services\PasswordHistory;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;

/**
 * Watches `guardian.users.model` (criterion 41): a new user's password is
 * the first history entry; a change to `guardian.passwords.column` is
 * recorded in the history and as `password.changed` (never with the hash).
 * A password reset saves the user, so it is recorded here too.
 */
final class RecordPasswordChange
{
    public function __construct(
        private readonly PasswordHistory $history,
        private readonly SecurityLog $log,
    ) {}

    public function created(Model $user): void
    {
        $hash = $user->getAttribute($this->column());

        if ($user instanceof Authenticatable && is_string($hash) && $hash !== '') {
            $this->history->record($user, $hash);
        }
    }

    public function updated(Model $user): void
    {
        $hash = $user->getAttribute($this->column());

        if (! $user instanceof Authenticatable || ! $user->wasChanged($this->column()) || ! is_string($hash) || $hash === '') {
            return;
        }

        $this->history->record($user, $hash);
        $this->log->record(SecurityEventType::PasswordChanged, $user);
    }

    private function column(): string
    {
        $column = config('guardian.passwords.column', 'password');

        return is_string($column) && $column !== '' ? $column : 'password';
    }
}
