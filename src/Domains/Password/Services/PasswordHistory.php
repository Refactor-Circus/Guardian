<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Guardian\Domains\Password\Data\PasswordPolicySettings;
use RefactorCircus\Guardian\Domains\Password\Models\PasswordHistoryModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use SensitiveParameter;

/**
 * The hashes of users' last passwords (criteria 41 and 42), kept to the
 * last `guardian.passwords.history` (at least one, so rotation knows when
 * the password last changed).
 */
final class PasswordHistory
{
    public function record(Authenticatable $user, #[SensitiveParameter] string $hash): PasswordHistoryModel
    {
        $key = UserKeys::of($user);

        $entry = PasswordHistoryModel::query()->create(['user_id' => $key, 'password_hash' => $hash]);

        $keep = PasswordHistoryModel::query()->newestFor($key)->limit(max(1, PasswordPolicySettings::fromConfig()->history))->pluck('id');

        PasswordHistoryModel::query()->where('user_id', $key)->whereNotIn('id', $keep)->delete();

        return $entry;
    }

    /**
     * The user's latest entry: when their password last changed.
     */
    public function latest(Authenticatable $user): ?PasswordHistoryModel
    {
        return PasswordHistoryModel::query()->newestFor(UserKeys::of($user))->first();
    }

    /**
     * A first entry for a user with none, dated now.
     */
    public function baseline(Authenticatable $user): PasswordHistoryModel
    {
        return $this->record($user, $user->getAuthPassword());
    }

    /**
     * The hashes of the user's last passwords.
     *
     * @return list<string>
     */
    public function hashes(Authenticatable $user, int $limit): array
    {
        /** @var list<string> */
        return PasswordHistoryModel::query()->newestFor(UserKeys::of($user))->limit($limit)->pluck('password_hash')->all();
    }
}
