<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ShowTwoFactorAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\ListPasskeysTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\RemovePasskeyTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeysAvailable;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Factors;

// Criterion 30: without web-auth/webauthn-lib (its check faked here, since
// the library is installed for the other tests), passkeys answer 501.

beforeEach(fn () => app()->instance(PasskeysAvailable::class, new PasskeysAvailable(fn (): bool => false)));

it('sees the library when it is installed', function (): void {
    expect((new PasskeysAvailable)->available())->toBeTrue();
});

it('answers 501 naming the library on every passkey endpoint', function (string $method, string $name, array $parameters): void {
    $ada = user();
    $passkey = PasskeyModel::query()->create(['user_id' => $ada->getKey(), 'credential_id' => 'abc', 'public_key' => 'x']);

    $this->actingAs($ada)->json($method, route($name, $parameters === [] ? [] : [$passkey->id]), ['credential' => ['id' => 'x']])
        ->assertStatus(501)
        ->assertJsonPath('code', 'passkeys_unavailable')
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'web-auth/webauthn-lib'));
})->with([
    'options' => ['POST', 'guardian.two-factor.passkeys.options', []],
    'register' => ['POST', 'guardian.two-factor.passkeys.store', []],
    'list' => ['GET', 'guardian.two-factor.passkeys.index', []],
    'remove' => ['DELETE', 'guardian.two-factor.passkeys.destroy', ['passkey']],
]);

it('answers an error from the MCP tools', function (): void {
    $ada = user();
    $this->actingAs($ada);

    mcpTool(ListPasskeysTool::class)->assertHasErrors()->assertSee('web-auth/webauthn-lib');
    mcpTool(RemovePasskeyTool::class, ['passkey' => 'missing', 'code' => '123456'])->assertHasErrors();
});

it('reports passkeys as unavailable in the status and offers no passkey method', function (): void {
    $ada = user();

    expect(app(ShowTwoFactorAction::class)->execute($ada)->passkeysAvailable)->toBeFalse()
        ->and(app(Factors::class)->available())->not->toContain('passkey');

    $this->actingAs($ada)->getJson(route('guardian.two-factor.show'))
        ->assertOk()
        ->assertJsonPath('data.passkeys_available', false)
        ->assertJsonPath('data.allowed_methods', fn (array $methods): bool => ! in_array('passkey', $methods, true));
});
