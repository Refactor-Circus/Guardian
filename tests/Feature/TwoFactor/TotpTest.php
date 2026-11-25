<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ConfirmTotpAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RemoveTwoFactorMethodAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\StartTotpEnrolmentAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmTotpData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RemoveTwoFactorMethodData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TotpConfirmedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TotpConfirmingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TotpEnrolmentStartedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TotpEnrolmentStartingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Factors;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Totp;
use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;
use RefactorCircus\Keen\KeenServiceProvider;

// Criterion 27: TOTP enrolment, confirmation and replay protection.

// Time stands still mid-step, so a code never crosses a step boundary.
beforeEach(fn () => $this->travelTo(now()->startOfMinute()->addSeconds(10)));

// RFC 6238 appendix B, SHA-1: the seed "12345678901234567890" (base32
// below); the RFC lists eight digits, of which a six-digit code is the last six.
it('matches the RFC 6238 SHA-1 test vectors', function (int $time, string $code): void {
    $totp = app(Totp::class);

    expect($totp->code('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', intdiv($time, 30)))->toBe($code)
        ->and($totp->verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $code, null, intdiv($time, 30)))->toBe(intdiv($time, 30));
})->with([
    [59, '287082'],
    [1111111109, '081804'],
    [1111111111, '050471'],
    [1234567890, '005924'],
    [2000000000, '279037'],
    [20000000000, '353130'],
]);

it('starts an enrolment with a secret and an otpauth URI, unconfirmed', function (): void {
    config()->set('app.name', 'Acme');
    $ada = user(['email' => 'ada@example.com']);

    $enrolment = app(StartTotpEnrolmentAction::class)->execute($ada);

    expect($enrolment->secret)->toMatch('/^[A-Z2-7]{32}$/')
        ->and($enrolment->otpauthUri)->toStartWith('otpauth://totp/Acme:ada%40example.com?secret='.$enrolment->secret)
        ->and($enrolment->otpauthUri)->toContain('issuer=Acme', 'algorithm=SHA1', 'digits=6', 'period=30')
        ->and($enrolment->method->confirmed_at)->toBeNull()
        ->and(app(Factors::class)->has($ada))->toBeFalse();
});

it('uses guardian.two_factor.issuer when set', function (): void {
    config()->set('guardian.two_factor.issuer', 'Acme Security');

    expect(app(StartTotpEnrolmentAction::class)->execute(user())->otpauthUri)->toContain('issuer=Acme%20Security');
});

it('keeps one enrolment waiting at a time', function (): void {
    $ada = user();

    app(StartTotpEnrolmentAction::class)->execute($ada);
    $second = app(StartTotpEnrolmentAction::class)->execute($ada);

    expect(TwoFactorMethodModel::query()->where('user_id', $ada->getKey())->pluck('id')->all())->toBe([$second->method->id]);
});

it('confirms with a code from the current step or one either side, and makes recovery codes the first time', function (int $offset): void {
    $ada = user();
    $enrolment = app(StartTotpEnrolmentAction::class)->execute($ada);

    $confirmed = app(ConfirmTotpAction::class)->execute($ada, new ConfirmTotpData(code: totpCode($enrolment->secret, $offset)));

    expect($enrolment->method->refresh()->confirmed_at)->not->toBeNull()
        ->and($confirmed->recoveryCodes)->toHaveCount(10)
        ->and(app(Factors::class)->has($ada))->toBeTrue()
        ->and(SecurityEventModel::query()->where('type', 'two_factor.enabled')->count())->toBe(1);
})->with(['previous step' => -1, 'current step' => 0, 'next step' => 1]);

it('refuses a code two steps away, or not a code at all', function (int|string $offset): void {
    $ada = user();
    $enrolment = app(StartTotpEnrolmentAction::class)->execute($ada);
    $code = is_int($offset) ? totpCode($enrolment->secret, $offset) : $offset;

    expect(fn () => app(ConfirmTotpAction::class)->execute($ada, new ConfirmTotpData(code: $code)))->toThrow(ValidationException::class);
    expect($enrolment->method->refresh()->confirmed_at)->toBeNull();
})->with(['two steps back' => -2, 'two steps on' => 2, 'letters' => 'abcdef', 'too short' => '123']);

it('refuses a code from a time step already used (replay)', function (): void {
    $ada = user();
    [$method, $secret] = enrolTotp($ada);
    $factors = app(Factors::class);

    $code = totpCode($secret);

    expect($factors->useTotp($method, $code))->toBeTrue()
        ->and($factors->useTotp($method->refresh(), $code))->toBeFalse()
        // An earlier step's code is refused once a later one was used.
        ->and($factors->useTotp($method->refresh(), totpCode($secret, -1)))->toBeFalse()
        ->and($factors->useTotp($method->refresh(), totpCode($secret, 1)))->toBeTrue();
});

it('makes recovery codes only with the first factor', function (): void {
    $ada = user();
    [, , $first] = enrolTotp($ada);
    [, , $second] = enrolTotp($ada, 'Tablet');

    expect($first)->toHaveCount(10)->and($second)->toBeNull();
});

it('says nothing waits when there is no enrolment', function (): void {
    expect(fn () => app(ConfirmTotpAction::class)->execute(user(), new ConfirmTotpData(code: '123456')))->toThrow(ValidationException::class);
});

it('dispatches the starting and finished events, without the secret', function (): void {
    Event::fake([TotpEnrolmentStartingActionEvent::class, TotpEnrolmentStartedActionEvent::class, TotpConfirmingActionEvent::class, TotpConfirmedActionEvent::class]);

    [, $secret] = enrolTotp(user());

    Event::assertDispatched(TotpEnrolmentStartingActionEvent::class);
    Event::assertDispatched(TotpEnrolmentStartedActionEvent::class, fn (TotpEnrolmentStartedActionEvent $event): bool => ! str_contains((string) json_encode($event->method), $secret));
    Event::assertDispatched(TotpConfirmingActionEvent::class);
    Event::assertDispatched(TotpConfirmedActionEvent::class);
});

it('stores the secret encrypted and records no secret in the log', function (): void {
    [$method, $secret] = enrolTotp(user());

    expect((string) DB::table('guardian_two_factor_methods')->where('id', $method->id)->value('secret'))->not->toContain($secret)
        ->and((string) json_encode(DB::table('guardian_security_events')->get()))->not->toContain($secret);
});

describe('over HTTP', function (): void {
    it('enrols and confirms, answering the secret and the recovery codes once', function (): void {
        $ada = user(['email' => 'ada@example.com']);
        $this->actingAs($ada);

        $started = $this->postJson(route('guardian.two-factor.totp.store'), ['label' => 'Phone'])
            ->assertCreated()
            ->assertJsonPath('data.method.type', 'totp')
            ->assertJsonPath('data.method.label', 'Phone')
            ->assertJsonPath('data.method.confirmed_at', null)
            ->json('data');

        expect($started['otpauth_uri'])->toContain('secret='.$started['secret']);

        $this->postJson(route('guardian.two-factor.totp.confirm'), ['code' => '000000'])->assertUnprocessable()->assertJsonValidationErrors('code');

        $confirmed = $this->postJson(route('guardian.two-factor.totp.confirm'), ['code' => totpCode($started['secret'])])
            ->assertOk()
            ->assertJsonPath('data.method.id', $started['method']['id'])
            ->assertJsonCount(10, 'data.recovery_codes')
            ->json('data');

        expect($confirmed['method']['confirmed_at'])->not->toBeNull();

        $this->getJson(route('guardian.two-factor.show'))->assertOk()->assertJsonMissing(['secret' => $started['secret']]);
    });

    it('needs a signed-in user', function (): void {
        $this->postJson(route('guardian.two-factor.totp.store'))->assertUnauthorized();
        $this->postJson(route('guardian.two-factor.totp.confirm'), ['code' => '123456'])->assertUnauthorized();
    });
});

describe('with Keen', function (): void {
    beforeEach(function (): void {
        $this->app->register(KeenServiceProvider::class);
        $this->loadMigrationsFrom(dirname(__DIR__, 3).'/vendor/refactor-circus/keen/database/migrations');
    });

    it('audits enrolment, confirmation and removal without a secret, code or destination', function (): void {
        $ada = user();
        [$method, $secret, $codes] = enrolTotp($ada);
        $email = TwoFactorMethodModel::query()->create(['user_id' => $ada->getKey(), 'type' => 'email', 'destination' => 'ada.private@example.com']);
        $email->delete();
        app(RemoveTwoFactorMethodAction::class)->execute($ada, $method, new RemoveTwoFactorMethodData(code: (string) $codes[0]));

        $log = (string) json_encode(AuditEntryModel::query()->get()->toArray());

        expect(AuditEntryModel::query()->where('source', 'guardian')->count())->toBeGreaterThan(0);

        foreach ([$secret, 'ada.private@example.com', ...(array) $codes] as $value) {
            expect($log)->not->toContain($value);
        }
    });
});
