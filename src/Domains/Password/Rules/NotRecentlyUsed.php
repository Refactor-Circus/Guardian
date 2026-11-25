<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Rules;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;
use RefactorCircus\Guardian\Domains\Password\Services\PasswordHistory;

/**
 * Refuses a password the user had among their last `history` ones, or has
 * now (criterion 41), by `Hash::check()` against each stored hash.
 */
final class NotRecentlyUsed implements ValidationRule
{
    public function __construct(
        private readonly Authenticatable $user,
        private readonly int $history,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $hashes = app(PasswordHistory::class)->hashes($this->user, $this->history);
        $current = $this->user->getAuthPassword();

        if ($current !== '') {
            $hashes[] = $current;
        }

        foreach ($hashes as $hash) {
            if ($hash !== '' && Hash::isHashed($hash) && Hash::check($value, $hash)) {
                $fail(__('guardian::guardian.password_recently_used'));

                return;
            }
        }
    }
}
