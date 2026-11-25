<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;
use RefactorCircus\Guardian\Domains\Password\Data\PasswordPolicySettings;
use RefactorCircus\Guardian\Domains\Password\Rules\NotRecentlyUsed;

/**
 * The password rules hosts put on their own registration, reset and change
 * requests (criterion 39), built from `guardian.passwords`:
 *
 *     'password' => ['required', 'confirmed', ...PasswordPolicy::rules($request->user())],
 *
 * `Password::min()->max()`, the complexity rules switched on, the breach
 * check (HIBP, k-anonymity) and, for a user while history is kept,
 * `NotRecentlyUsed`.
 */
final class PasswordPolicy
{
    /**
     * @return list<Password|ValidationRule>
     */
    public static function rules(?Authenticatable $user = null): array
    {
        $settings = PasswordPolicySettings::fromConfig();

        $password = Password::min($settings->minLength)->max($settings->maxLength);

        if ($settings->mixedCase) {
            $password->mixedCase();
        }

        if ($settings->letters) {
            $password->letters();
        }

        if ($settings->numbers) {
            $password->numbers();
        }

        if ($settings->symbols) {
            $password->symbols();
        }

        if ($settings->uncompromised) {
            $password->uncompromised($settings->uncompromisedThreshold);
        }

        $rules = [$password];

        if ($user !== null && $settings->history > 0) {
            $rules[] = new NotRecentlyUsed($user, $settings->history);
        }

        return $rules;
    }
}
