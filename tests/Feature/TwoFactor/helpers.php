<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ConfirmTotpAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\StartTotpEnrolmentAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmedFactor;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmTotpData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\StartTotpEnrolmentData;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Totp;
use Workbench\App\Models\User;

/**
 * The TOTP code for a secret at a time step (now by default).
 */
function totpCode(string $secret, int $offset = 0): string
{
    $totp = app(Totp::class);

    return $totp->code($secret, $totp->timestep() + $offset);
}

/**
 * Enrol and confirm an authenticator for a user, as the API does: the
 * confirmed method, its secret, and the recovery codes made with it.
 *
 * @return array{0: TwoFactorMethodModel, 1: string, 2: list<string>|null}
 */
function enrolTotp(User $user, string $label = 'Phone'): array
{
    $enrolment = app(StartTotpEnrolmentAction::class)->execute($user, new StartTotpEnrolmentData(label: $label));

    // The step before now, so the next code (now) is still accepted.
    /** @var ConfirmedFactor $confirmed */
    $confirmed = app(ConfirmTotpAction::class)->execute($user, new ConfirmTotpData(code: totpCode($enrolment->secret, -1)));

    /** @var TwoFactorMethodModel $method */
    $method = $confirmed->factor;

    return [$method->refresh(), $enrolment->secret, $confirmed->recoveryCodes];
}
