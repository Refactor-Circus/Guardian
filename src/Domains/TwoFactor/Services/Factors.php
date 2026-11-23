<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Enums\TwoFactorMethodType;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeyCeremony;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeyException;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeysAvailable;
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
        private readonly OneTimeCodes $oneTimeCodes,
        private readonly PasskeysAvailable $passkeys,
        private readonly PasskeyCeremony $ceremony,
        private readonly SecurityLog $log,
    ) {}

    /**
     * The method types this install can offer.
     *
     * @return list<string>
     */
    public function available(): array
    {
        $types = [TwoFactorMethodType::Totp->value];

        foreach ([TwoFactorMethodType::Email, TwoFactorMethodType::Sms] as $type) {
            if ($this->oneTimeCodes->supports($type->value)) {
                $types[] = $type->value;
            }
        }

        if ($this->passkeys->available()) {
            $types[] = 'passkey';
        }

        return $types;
    }

    /**
     * The types of the user's confirmed factors, each once.
     *
     * @return list<string>
     */
    public function confirmedTypes(Authenticatable $user): array
    {
        $types = TwoFactorMethodModel::query()->ownedBy(UserKeys::of($user))->confirmed()->get()->toBase()
            ->map(fn (TwoFactorMethodModel $method): string => $method->type->value)
            ->when(PasskeyModel::query()->ownedBy(UserKeys::of($user))->exists(), fn (Collection $types): Collection => $types->push('passkey'))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return array_values($types);
    }

    /**
     * Whether the user holds a confirmed factor (a method or a passkey),
     * other than the one named.
     */
    public function has(Authenticatable $user, ?string $except = null): bool
    {
        return TwoFactorMethodModel::query()->ownedBy(UserKeys::of($user))->confirmed()
            ->when($except !== null, fn (Builder $query): Builder => $query->whereKeyNot($except))
            ->exists()
            || PasskeyModel::query()->ownedBy(UserKeys::of($user))
                ->when($except !== null, fn (Builder $query): Builder => $query->whereKeyNot($except))
                ->exists();
    }

    /**
     * Whether an assertion proves one of the user's passkeys (other than the
     * one named) against the assertion options kept for them: `passkey`, or
     * null. The passkey's counter moves forward; one that did not is refused
     * and recorded as `passkey.sign_count_mismatch`.
     *
     * @param  array<string, mixed>|null  $assertion
     */
    public function verifyAssertion(Authenticatable $user, ?array $assertion, ?string $except = null): ?string
    {
        if ($assertion === null || $assertion === [] || ! $this->passkeys->available()) {
            return null;
        }

        $credentialId = $this->ceremony->credentialIdOf($assertion);
        $passkey = $credentialId === null ? null : PasskeyModel::query()->ownedBy(UserKeys::of($user))
            ->where('credential_id', $credentialId)
            ->when($except !== null, fn (Builder $query): Builder => $query->whereKeyNot($except))
            ->first();

        if ($passkey === null) {
            return null;
        }

        try {
            $count = $this->ceremony->verifyAssertion($user, $passkey, $assertion);
        } catch (PasskeyException $e) {
            if ($e->signCountMismatch) {
                $this->log->record(SecurityEventType::PasskeySignCountMismatch, $user, ['passkey_id' => $passkey->id]);
            }

            return null;
        }

        // Only a counter that moves forward (or a zero counter the
        // authenticator does not keep) is recorded.
        $updated = PasskeyModel::query()->whereKey($passkey->id)
            ->where(fn (Builder $query): Builder => $query->where('sign_count', '<', $count)->orWhere(fn (Builder $zero): Builder => $zero->where('sign_count', 0)->where('sign_count', $count)))
            ->update(['sign_count' => $count, 'last_used_at' => now()]);

        return $updated === 1 ? 'passkey' : null;
    }

    /**
     * Which factor a code proves, other than the method named: `totp`, a
     * sent code's `email` or `sms`, or `recovery` (a recovery code or sent
     * code is then used up); null for none.
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
            ->when($exceptMethod !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptMethod))
            ->get();

        foreach ($methods as $method) {
            if ($method->type === TwoFactorMethodType::Totp ? $this->useTotp($method, $code) : $this->oneTimeCodes->verify($method, $code)) {
                return $method->type->value;
            }
        }

        return null;
    }

    /**
     * Whether an answer proves the named kind of factor (a challenge):
     * `totp`, `email` or `sms` with a code, `recovery` with a recovery code
     * (used up), or `passkey` with an assertion.
     *
     * @param  array<string, mixed>|null  $assertion
     */
    public function verifyAs(Authenticatable $user, string $method, #[SensitiveParameter] ?string $code, ?array $assertion = null): bool
    {
        if ($method === 'passkey') {
            return $this->verifyAssertion($user, $assertion) !== null;
        }

        $code = trim((string) $code);

        if ($code === '') {
            return false;
        }

        if ($method === 'recovery') {
            return $this->recoveryCodes->consume(UserKeys::of($user), $code);
        }

        $type = TwoFactorMethodType::tryFrom($method);

        if ($type === null) {
            return false;
        }

        $methods = TwoFactorMethodModel::query()->ownedBy(UserKeys::of($user))->confirmed()->where('type', $type->value)->get();

        foreach ($methods as $candidate) {
            if ($type === TwoFactorMethodType::Totp ? $this->useTotp($candidate, $code) : $this->oneTimeCodes->verify($candidate, $code)) {
                return true;
            }
        }

        return false;
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
