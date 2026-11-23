<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ListPasskeysAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RegisterPasskeyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RemovePasskeyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\StartPasskeyRegistrationAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RegisterPasskeyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RemoveTwoFactorMethodData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRegisteredActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRegisteringActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRegistrationStartedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRegistrationStartingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRemovedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRemovingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeysListedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeysListingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\ListPasskeysTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\RemovePasskeyTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeyCeremony;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeyChallenges;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Factors;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Tests\Fixtures\webauthn\SoftAuthenticator;

// Criterion 30: passkeys, with a software authenticator answering through
// the real web-auth/webauthn-lib checks.

beforeEach(function (): void {
    config()->set('app.url', 'http://localhost');
    config()->set('app.name', 'Acme');
    $this->travelTo(now()->startOfMinute()->addSeconds(10));
});

it('issues registration options for the relying party, keeping the challenge for the user', function (): void {
    $ada = user(['email' => 'ada@example.com']);

    $options = app(StartPasskeyRegistrationAction::class)->execute($ada);

    expect($options['rp'])->toBe(['id' => 'localhost', 'name' => 'Acme'])
        ->and($options['user']['name'])->toBe('ada@example.com')
        ->and($options['user']['id'])->not->toBe(SoftAuthenticator::base64Url((string) $ada->getKey()))
        ->and(strlen((string) base64_decode(strtr($options['user']['id'], '-_', '+/'))))->toBe(32)
        ->and($options['attestation'])->toBe('none')
        ->and($options['timeout'])->toBe(300000)
        ->and(strlen((string) base64_decode(strtr($options['challenge'], '-_', '+/'))))->toBe(32)
        ->and(app(PasskeyChallenges::class)->pull(PasskeyCeremony::REGISTRATION, $ada->getKey()))->toContain($options['challenge']);
});

it('takes the relying party id and name from config', function (): void {
    config()->set('guardian.two_factor.passkeys.rp_id', 'example.com');
    config()->set('guardian.two_factor.passkeys.rp_name', 'Example');

    expect(app(StartPasskeyRegistrationAction::class)->execute(user())['rp'])->toBe(['id' => 'example.com', 'name' => 'Example']);
});

it('verifies the attestation and stores the credential, with recovery codes for a first factor', function (): void {
    $ada = user();
    $authenticator = new SoftAuthenticator;
    $options = app(StartPasskeyRegistrationAction::class)->execute($ada);

    $registered = app(RegisterPasskeyAction::class)->execute($ada, new RegisterPasskeyData(credential: $authenticator->register($options), label: 'Laptop'));

    $passkey = PasskeyModel::query()->sole();

    expect($registered->factor->is($passkey))->toBeTrue()
        ->and($registered->recoveryCodes)->toHaveCount(10)
        ->and($passkey->credential_id)->toBe(SoftAuthenticator::base64Url($authenticator->credentialId))
        ->and($passkey->label)->toBe('Laptop')
        ->and($passkey->transports)->toBe(['internal'])
        ->and($passkey->aaguid)->toBe('00000000-0000-0000-0000-000000000000')
        ->and(app(Factors::class)->has($ada))->toBeTrue()
        ->and(app(Factors::class)->confirmedTypes($ada))->toBe(['passkey'])
        ->and(SecurityEventModel::query()->where('type', 'two_factor.enabled')->sole()->context)->toBe(['method' => 'passkey', 'factor_id' => $passkey->id]);
});

it('refuses a response to another user\'s challenge, a reused one and an expired one', function (): void {
    $ada = user();
    $bob = user();
    $authenticator = new SoftAuthenticator;
    $register = fn ($user, array $credential) => app(RegisterPasskeyAction::class)->execute($user, new RegisterPasskeyData(credential: $credential));

    // Bound to the user who asked.
    $credential = $authenticator->register(app(StartPasskeyRegistrationAction::class)->execute($ada));
    expect(fn () => $register($bob, $credential))->toThrow(ValidationException::class);

    // Used once.
    $register($ada, $credential);
    expect(fn () => $register($ada, $credential))->toThrow(ValidationException::class);

    // Five minutes.
    $late = (new SoftAuthenticator)->register(app(StartPasskeyRegistrationAction::class)->execute($ada));
    $this->travel(301)->seconds();
    expect(fn () => $register($ada, $late))->toThrow(ValidationException::class)
        ->and(PasskeyModel::query()->count())->toBe(1);
});

it('refuses a response from another origin, for another challenge or tampered', function (string $case): void {
    $ada = user();
    $authenticator = new SoftAuthenticator(origin: $case === 'origin' ? 'https://evil.example' : 'http://localhost');
    $options = app(StartPasskeyRegistrationAction::class)->execute($ada);

    $credential = $authenticator->register($options, $case === 'challenge' ? ['challenge' => SoftAuthenticator::base64Url(random_bytes(32))] : ($case === 'type' ? ['type' => 'webauthn.get'] : []));

    expect(fn () => app(RegisterPasskeyAction::class)->execute($ada, new RegisterPasskeyData(credential: $credential)))->toThrow(ValidationException::class)
        ->and(PasskeyModel::query()->count())->toBe(0);
})->with(['origin', 'challenge', 'type']);

it('accepts an assertion whose counter moves forward, and refuses and records one that does not', function (): void {
    $ada = user();
    $authenticator = registerPasskey($ada);
    $factors = app(Factors::class);

    expect($factors->verifyAssertion($ada, passkeyAssertion($ada, $authenticator)))->toBe('passkey')
        ->and(PasskeyModel::query()->sole()->sign_count)->toBe(1)
        ->and($factors->verifyAssertion($ada, passkeyAssertion($ada, $authenticator, signCount: 1)))->toBeNull()
        ->and(SecurityEventModel::query()->where('type', 'passkey.sign_count_mismatch')->count())->toBe(1)
        ->and($factors->verifyAssertion($ada, passkeyAssertion($ada, $authenticator, signCount: 5)))->toBe('passkey');
});

it('accepts an authenticator that keeps no counter (always zero)', function (): void {
    $ada = user();
    $authenticator = registerPasskey($ada);

    expect(app(Factors::class)->verifyAssertion($ada, passkeyAssertion($ada, $authenticator, signCount: 0)))->toBe('passkey')
        ->and(app(Factors::class)->verifyAssertion($ada, passkeyAssertion($ada, $authenticator, signCount: 0)))->toBe('passkey');
});

it('refuses another user\'s passkey, and an assertion without its challenge', function (): void {
    $ada = user();
    $bob = user();
    $authenticator = registerPasskey($ada);
    $assertion = passkeyAssertion($ada, $authenticator);

    expect(app(Factors::class)->verifyAssertion($bob, $assertion))->toBeNull()
        ->and(app(Factors::class)->verifyAssertion($ada, $assertion))->toBe('passkey')
        // The challenge was used.
        ->and(app(Factors::class)->verifyAssertion($ada, $assertion))->toBeNull();
});

it('lists and removes passkeys, needing another factor', function (): void {
    $ada = user();
    $laptop = registerPasskey($ada, label: 'Laptop');
    registerPasskey($ada, label: 'Phone');
    [$laptopKey, $phoneKey] = PasskeyModel::query()->orderBy('label')->get()->all();
    $remove = app(RemovePasskeyAction::class);

    expect(app(ListPasskeysAction::class)->execute($ada)->pluck('label')->all())->toBe(['Laptop', 'Phone'])
        ->and(fn () => $remove->execute($ada, $phoneKey))->toThrow(ValidationException::class);

    // Its own assertion does not count; another passkey's does.
    expect(fn () => $remove->execute($ada, $laptopKey, new RemoveTwoFactorMethodData(assertion: passkeyAssertion($ada, $laptop))))->toThrow(ValidationException::class);

    $remove->execute($ada, $phoneKey, new RemoveTwoFactorMethodData(assertion: passkeyAssertion($ada, $laptop)));

    expect(PasskeyModel::query()->pluck('label')->all())->toBe(['Laptop'])
        ->and(SecurityEventModel::query()->where('type', 'two_factor.disabled')->sole()->context)->toBe(['method' => 'passkey', 'factor_id' => $phoneKey->id]);
});

it('refuses removing the last factor while two-factor is required', function (): void {
    config()->set('guardian.two_factor.required', true);
    $ada = user();
    registerPasskey($ada);

    expect(fn () => app(RemovePasskeyAction::class)->execute($ada, PasskeyModel::query()->sole()))->toThrow(TwoFactorException::class);
});

it('dispatches every action\'s events', function (): void {
    Event::fake([
        PasskeyRegistrationStartingActionEvent::class, PasskeyRegistrationStartedActionEvent::class,
        PasskeyRegisteringActionEvent::class, PasskeyRegisteredActionEvent::class,
        PasskeysListingActionEvent::class, PasskeysListedActionEvent::class,
        PasskeyRemovingActionEvent::class, PasskeyRemovedActionEvent::class,
    ]);
    $ada = user();
    [, , $codes] = enrolTotp($ada);
    registerPasskey($ada);
    app(ListPasskeysAction::class)->execute($ada);
    app(RemovePasskeyAction::class)->execute($ada, PasskeyModel::query()->sole(), new RemoveTwoFactorMethodData(code: (string) $codes[0]));

    foreach ([PasskeyRegistrationStartingActionEvent::class, PasskeyRegistrationStartedActionEvent::class, PasskeyRegisteringActionEvent::class, PasskeyRegisteredActionEvent::class, PasskeysListingActionEvent::class, PasskeysListedActionEvent::class, PasskeyRemovingActionEvent::class, PasskeyRemovedActionEvent::class] as $event) {
        Event::assertDispatched($event);
    }
});

describe('over HTTP', function (): void {
    it('registers, lists, shows in the status and removes', function (): void {
        $ada = user();
        [, $secret] = enrolTotp($ada);
        $this->actingAs($ada);
        $authenticator = new SoftAuthenticator;

        $options = $this->postJson(route('guardian.two-factor.passkeys.options'))->assertOk()->json('data');

        $id = $this->postJson(route('guardian.two-factor.passkeys.store'), ['credential' => $authenticator->register($options), 'label' => 'Laptop'])
            ->assertCreated()
            ->assertJsonPath('data.passkey.label', 'Laptop')
            ->assertJsonPath('data.recovery_codes', null)
            ->json('data.passkey.id');

        $this->getJson(route('guardian.two-factor.passkeys.index'))->assertOk()->assertJsonPath('data.0.id', $id)->assertJsonMissingPath('data.0.public_key')->assertJsonMissingPath('data.0.credential_id');
        $this->getJson(route('guardian.two-factor.show'))->assertOk()->assertJsonPath('data.passkeys.0.id', $id)->assertJsonPath('data.passkeys_available', true);

        $this->postJson(route('guardian.two-factor.passkeys.store'), ['credential' => ['id' => 'x', 'type' => 'public-key', 'response' => ['a' => 'b']]])->assertUnprocessable();

        $this->deleteJson(route('guardian.two-factor.passkeys.destroy', $id))->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->deleteJson(route('guardian.two-factor.passkeys.destroy', $id), ['code' => totpCode($secret, 1)])->assertNoContent();
        $this->deleteJson(route('guardian.two-factor.passkeys.destroy', $id), ['code' => totpCode($secret, 1)])->assertNotFound();
    });

    it('stores no credential detail in the security log', function (): void {
        $ada = user();
        $authenticator = registerPasskey($ada);

        expect((string) json_encode(DB::table('guardian_security_events')->get()))->not->toContain(SoftAuthenticator::base64Url($authenticator->credentialId));
    });
});

describe('over MCP', function (): void {
    it('offers list and remove tools', function (string $tool): void {
        expect(GuardianServer::TOOLS)->toContain($tool);
    })->with([ListPasskeysTool::class, RemovePasskeyTool::class]);

    it('lists your passkeys like the HTTP API, and removes one with another factor\'s code', function (): void {
        $ada = user();
        [, , $codes] = enrolTotp($ada);
        registerPasskey($ada);
        $this->actingAs($ada);

        mcpTool(ListPasskeysTool::class)->assertOk()->assertStructuredContent($this->getJson(route('guardian.two-factor.passkeys.index'))->json());
        mcpTool(RemovePasskeyTool::class, ['passkey' => PasskeyModel::query()->sole()->id, 'code' => (string) $codes[0]])->assertOk();

        expect(PasskeyModel::query()->count())->toBe(0);
    });
});
