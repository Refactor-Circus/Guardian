<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Session\Store;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\ChallengeCodeSendingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\ChallengeCodeSentActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\ChallengePassedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\ChallengePassingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyChallengeStartedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyChallengeStartingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\PassChallengeRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeyCeremony;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\TwoFactorSession;
use RefactorCircus\Guardian\Tests\Fixtures\webauthn\SoftAuthenticator;

// Criterion 32: the challenge after signing in.

beforeEach(function (): void {
    config()->set('app.url', 'http://localhost');
    $this->travelTo(now()->startOfMinute()->addSeconds(10));
    withSessions();
});

function twoFactorSession(): Store
{
    return app('session.store');
}

it('marks the session pending when a user with a confirmed factor signs in', function (): void {
    $ada = user();
    enrolTotp($ada);

    signIn($ada);

    expect(twoFactorSession()->get(TwoFactorSession::PENDING))->toBe((string) $ada->getKey())
        ->and(twoFactorSession()->get(TwoFactorSession::CONFIRMED))->toBeNull();
});

it('leaves a user without a factor alone', function (): void {
    $ada = user();

    signIn($ada);

    expect(twoFactorSession()->get(TwoFactorSession::PENDING))->toBeNull();
    $this->get('test-protected')->assertOk();
});

it('passes with a TOTP code: 204, the session confirmed and recorded', function (): void {
    $ada = user();
    [, $secret] = enrolTotp($ada);
    signIn($ada);

    $this->get('test-protected')->assertForbidden();

    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'totp', 'code' => totpCode($secret, 1)])->assertNoContent();

    expect(twoFactorSession()->get(TwoFactorSession::CONFIRMED))->toBe((string) $ada->getKey())
        ->and(twoFactorSession()->get(TwoFactorSession::PENDING))->toBeNull()
        ->and(SecurityEventModel::query()->where('type', 'two_factor.challenge_passed')->sole()->context)->toBe(['method' => 'totp']);

    $this->get('test-protected')->assertOk()->assertSee('protected');
});

it('passes with a recovery code, used up and recorded', function (): void {
    $ada = user();
    [, , $codes] = enrolTotp($ada);
    signIn($ada);

    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'recovery', 'code' => (string) $codes[0]])->assertNoContent();

    expect(SecurityEventModel::query()->where('type', 'two_factor.recovery_code_used')->count())->toBe(1);

    signIn($ada);
    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'recovery', 'code' => (string) $codes[0]])->assertUnprocessable();
});

it('sends an email or SMS code and passes with it', function (string $type): void {
    $channel = fakeOneTimeCodeChannel();
    $ada = user();
    enrolOneTimeCode($ada, $channel, $type);
    signIn($ada);

    $this->postJson(route('guardian.two-factor.challenge.send'), ['method' => $type])->assertNoContent();
    $this->postJson(route('guardian.two-factor.challenge.send'), ['method' => $type])->assertTooManyRequests();

    $this->postJson(route('guardian.two-factor.challenge'), ['method' => $type, 'code' => $channel->lastCode()])->assertNoContent();

    $this->get('test-protected')->assertOk();
})->with(['email', 'sms']);

it('answers 422 method_unavailable for SMS with the default channel, and for a method the user lacks', function (): void {
    $ada = user();
    enrolTotp($ada);
    signIn($ada);

    $this->postJson(route('guardian.two-factor.challenge.send'), ['method' => 'sms'])->assertUnprocessable()->assertJsonPath('code', 'method_unavailable');
    $this->postJson(route('guardian.two-factor.challenge.send'), ['method' => 'email'])->assertUnprocessable()->assertJsonValidationErrors('method');
});

it('passes with a passkey assertion against the challenge\'s options', function (): void {
    $ada = user();
    $authenticator = registerPasskey($ada);
    signIn($ada);

    $options = $this->postJson(route('guardian.two-factor.challenge.passkey-options'))->assertOk()->json('data');

    expect($options['allowCredentials'][0]['id'])->toBe(SoftAuthenticator::base64Url($authenticator->credentialId));

    $assertion = $authenticator->assert($options, app(PasskeyCeremony::class)->userHandle($ada));

    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'passkey', 'assertion' => $assertion])->assertNoContent();
    $this->get('test-protected')->assertOk();
});

it('refuses a wrong answer, records it, and answers 429 after five failures a minute', function (): void {
    $ada = user();
    [, $secret] = enrolTotp($ada);
    signIn($ada);
    $wrong = totpCode($secret) === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'totp', 'code' => $wrong])->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'totp', 'code' => totpCode($secret)])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJsonPath('code', 'too_many_attempts');

    expect(SecurityEventModel::query()->where('type', 'two_factor.challenge_failed')->count())->toBe(5)
        ->and((string) json_encode(SecurityEventModel::query()->pluck('context')))->not->toContain($wrong);

    $this->travel(61)->seconds();
    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'totp', 'code' => totpCode($secret)])->assertNoContent();
});

it('validates the answer\'s shape', function (): void {
    $ada = user();
    enrolTotp($ada);
    signIn($ada);

    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'pigeon'])->assertUnprocessable()->assertJsonValidationErrors('method');
    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'totp'])->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'passkey'])->assertUnprocessable()->assertJsonValidationErrors('assertion');
});

it('regenerates the session id when the challenge passes', function (): void {
    $ada = user();
    $session = app(Store::class);
    $session->start();
    $before = $session->getId();

    app(TwoFactorSession::class)->confirm($session, $ada);

    expect($session->getId())->not->toBe($before)
        ->and(app(TwoFactorSession::class)->confirmed($session, $ada))->toBeTrue()
        ->and(app(TwoFactorSession::class)->confirmed($session, user()))->toBeFalse();
});

it('answers 409 session_required to a request without a session', function (): void {
    $ada = user();
    [, $secret] = enrolTotp($ada);

    $request = PassChallengeRequest::create('/', 'POST', ['method' => 'totp', 'code' => totpCode($secret)]);
    $request->setUserResolver(fn () => $ada);
    $request->setContainer(app())->validateResolved();

    expect(fn () => $request->persist())->toThrow(fn (TwoFactorException $e) => expect($e->errorCode)->toBe('session_required')->and($e->status())->toBe(409));
});

it('keeps a pending session from adding, removing or replacing factors', function (): void {
    fakeOneTimeCodeChannel();
    $ada = user();
    [$method, $secret] = enrolTotp($ada);
    signIn($ada);

    $this->postJson(route('guardian.two-factor.totp.store'))->assertForbidden()->assertJsonPath('code', 'two_factor_challenge_required');
    $this->postJson(route('guardian.two-factor.one-time-codes.store'), ['type' => 'email', 'destination' => 'evil@example.com'])->assertForbidden();
    $this->postJson(route('guardian.two-factor.passkeys.options'))->assertForbidden();
    $this->postJson(route('guardian.two-factor.recovery-codes.store'))->assertForbidden();
    $this->deleteJson(route('guardian.two-factor.methods.destroy', $method->id), ['code' => totpCode($secret)])->assertForbidden();

    // Status and the challenge stay open.
    $this->getJson(route('guardian.two-factor.show'))->assertOk();
    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'totp', 'code' => totpCode($secret)])->assertNoContent();
    $this->postJson(route('guardian.two-factor.recovery-codes.store'))->assertCreated();
});

it('confirms a session whose user enrols a first factor in it', function (): void {
    $ada = user();
    signIn($ada);

    $secret = $this->postJson(route('guardian.two-factor.totp.store'))->assertCreated()->json('data.secret');
    $this->postJson(route('guardian.two-factor.totp.confirm'), ['code' => totpCode($secret)])->assertOk();

    $this->get('test-protected')->assertOk();
});

it('dispatches the challenge actions\' events, without the answer', function (): void {
    Event::fake([ChallengePassingActionEvent::class, ChallengePassedActionEvent::class, ChallengeCodeSendingActionEvent::class, ChallengeCodeSentActionEvent::class, PasskeyChallengeStartingActionEvent::class, PasskeyChallengeStartedActionEvent::class]);
    $channel = fakeOneTimeCodeChannel();
    $ada = user();
    enrolOneTimeCode($ada, $channel);
    registerPasskey($ada);
    signIn($ada);

    $this->postJson(route('guardian.two-factor.challenge.send'), ['method' => 'email'])->assertNoContent();
    $this->postJson(route('guardian.two-factor.challenge.passkey-options'))->assertOk();
    $this->postJson(route('guardian.two-factor.challenge'), ['method' => 'email', 'code' => $channel->lastCode()])->assertNoContent();

    Event::assertDispatched(ChallengePassingActionEvent::class, fn (ChallengePassingActionEvent $event): bool => $event->method === 'email');
    Event::assertDispatched(ChallengePassedActionEvent::class);
    Event::assertDispatched(ChallengeCodeSendingActionEvent::class);
    Event::assertDispatched(ChallengeCodeSentActionEvent::class);
    Event::assertDispatched(PasskeyChallengeStartingActionEvent::class);
    Event::assertDispatched(PasskeyChallengeStartedActionEvent::class);
});
