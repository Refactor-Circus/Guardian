<?php

declare(strict_types=1);

use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ConfirmOneTimeCodeAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ConfirmTotpAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RegisterPasskeyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\StartOneTimeCodeEnrolmentAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\StartPasskeyRegistrationAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\StartTotpEnrolmentAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\OneTimeCodeChannel;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmedFactor;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmOneTimeCodeData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmTotpData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RegisterPasskeyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\StartOneTimeCodeEnrolmentData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\StartTotpEnrolmentData;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeyCeremony;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Totp;
use RefactorCircus\Guardian\Tests\Fixtures\FakeOneTimeCodeChannel;
use RefactorCircus\Guardian\Tests\Fixtures\webauthn\SoftAuthenticator;
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

/**
 * Bind a fake channel (email and SMS unless told otherwise) and answer it.
 *
 * @param  list<string>  $types
 */
function fakeOneTimeCodeChannel(array $types = ['email', 'sms']): FakeOneTimeCodeChannel
{
    $channel = new FakeOneTimeCodeChannel($types);
    app()->instance(OneTimeCodeChannel::class, $channel);

    return $channel;
}

/**
 * Enrol and confirm an email or SMS method through the fake channel.
 */
function enrolOneTimeCode(User $user, FakeOneTimeCodeChannel $channel, string $type = 'email', ?string $destination = null): TwoFactorMethodModel
{
    $method = app(StartOneTimeCodeEnrolmentAction::class)->execute($user, new StartOneTimeCodeEnrolmentData(type: $type, destination: $destination ?? ($type === 'email' ? 'ada@example.com' : '+447700900123')));
    app(ConfirmOneTimeCodeAction::class)->execute($user, new ConfirmOneTimeCodeData(type: $type, code: $channel->lastCode()));
    RateLimiter::clear('guardian-two-factor-send:'.$user->getKey());

    return $method->refresh();
}

/**
 * Register a passkey for a user through the API's actions, answered by a
 * software authenticator; the authenticator is returned to assert with.
 */
function registerPasskey(User $user, ?SoftAuthenticator $authenticator = null, string $label = 'Laptop'): SoftAuthenticator
{
    $authenticator ??= new SoftAuthenticator;
    $options = app(StartPasskeyRegistrationAction::class)->execute($user);

    app(RegisterPasskeyAction::class)->execute($user, new RegisterPasskeyData(credential: $authenticator->register($options), label: $label));

    return $authenticator;
}

/**
 * An assertion by the authenticator against fresh assertion options issued
 * to the user.
 *
 * @return array<string, mixed>
 */
function passkeyAssertion(User $user, SoftAuthenticator $authenticator, ?int $signCount = null): array
{
    $ceremony = app(PasskeyCeremony::class);
    $options = $ceremony->assertionOptions($user, PasskeyModel::query()->where('user_id', $user->getKey())->get());

    return $authenticator->assert($options, $ceremony->userHandle($user), $signCount);
}

/**
 * Give Guardian's API routes a session, as a host's `web` group does, and
 * add a sign-in route and a protected route behind `guardian.two-factor`.
 */
function withSessions(): void
{
    foreach (Route::getRoutes() as $route) {
        if (str_starts_with((string) $route->getName(), 'guardian.')) {
            $route->middleware(StartSession::class);
        }
    }

    Route::middleware('web')->post('test-login', function (): string {
        $user = User::query()->findOrFail(request()->integer('user'));
        Auth::guard('web')->login($user);

        return 'signed in';
    });

    Route::middleware(['web', 'auth', 'guardian.two-factor'])->get('test-protected', fn (): string => 'protected');
}

/**
 * Sign in through the host's (test) login route, as a browser does.
 */
function signIn(User $user): void
{
    test()->post('test-login', ['user' => $user->getKey()])->assertOk();
}
