<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Guardian\Domains\TwoFactor\Enums\TwoFactorMethodType;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use SensitiveParameter;

/**
 * A user's two-factor factors: which they hold, which this install can
 * offer, and whether a code proves one of them.
 */
final class Factors
{
    public function __construct(
        private readonly Totp $totp,
        private readonly RecoveryCodes $recoveryCodes,
    ) {}

    /**
     * The method types this install can offer.
     *
     * @return list<string>
     */
    public function available(): array
    {
        return [TwoFactorMethodType::Totp->value];
    }

    /**
     * The types of the user's confirmed factors, each once.
     *
     * @return list<string>
     */
    public function confirmedTypes(Authenticatable $user): array
    {
        $types = TwoFactorMethodModel::query()->ownedBy(UserKeys::of($user))->confirmed()->get()
            ->map(fn (TwoFactorMethodModel $method): string => $method->type->value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return array_values($types);
    }

    /**
     * Whether the user holds a confirmed factor, other than the one named.
     */
    public function has(Authenticatable $user, ?string $exceptMethod = null): bool
    {
        return TwoFactorMethodModel::query()->ownedBy(UserKeys::of($user))->confirmed()
            ->when($exceptMethod !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptMethod))
            ->exists();
    }

    /**
     * Which factor a code proves, other than the method named: `totp`, or
     * `recovery` (the recovery code is then used up); null for none.
     */
    public function verify(Authenticatable $user, #[SensitiveParameter] ?string $code, ?string $exceptMethod = null): ?string
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $code = trim($code);

        if ($this->recoveryCodes->looksLikeOne($code)) {
            return $this->recoveryCodes->consume(UserKeys::of($user), $code) ? 'recovery' : null;
        }

        $methods = TwoFactorMethodModel::query()->ownedBy(UserKeys::of($user))->confirmed()
            ->where('type', TwoFactorMethodType::Totp->value)
            ->when($exceptMethod !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptMethod))
            ->get();

        foreach ($methods as $method) {
            if ($this->useTotp($method, $code)) {
                return 'totp';
            }
        }

        return null;
    }

    /**
     * Accept a TOTP code for a method: valid now, and for a later time step
     * than any accepted before (replay protection), recorded atomically.
     */
    public function useTotp(TwoFactorMethodModel $method, #[SensitiveParameter] string $code): bool
    {
        $secret = $method->secret;

        if ($secret === null) {
            return false;
        }

        $step = $this->totp->verify($secret, $code, $method->last_timestep);

        if ($step === null) {
            return false;
        }

        // Only one request may move the step forward: a replay racing this
        // one finds the step already taken.
        $updated = TwoFactorMethodModel::query()->whereKey($method->id)
            ->where(fn (Builder $query): Builder => $query->whereNull('last_timestep')->orWhere('last_timestep', '<', $step))
            ->update(['last_timestep' => $step, 'last_used_at' => now()]);

        if ($updated !== 1) {
            return false;
        }

        $method->forceFill(['last_timestep' => $step, 'last_used_at' => now()])->syncOriginal();

        return true;
    }
}
