<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Token/helpers.php';
require_once dirname(__DIR__).'/Permission/helpers.php';

use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\Token\Events\TokenIssuedActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\TokenIssuingActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\TokenRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\TokenRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\TokensListedActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\TokensListingActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\UserTokensListedActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\UserTokensListingActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\UserTokensRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\UserTokensRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Token\Mcp\Tools\ListTokensTool;
use RefactorCircus\Guardian\Domains\Token\Mcp\Tools\ListUserTokensTool;
use RefactorCircus\Guardian\Domains\Token\Mcp\Tools\RevokeTokenTool;
use RefactorCircus\Guardian\Domains\Token\Mcp\Tools\RevokeUserTokensTool;
use RefactorCircus\Guardian\Mcp\GuardianServer;

// Criterion 48: the token API and tools, on a host driver; criterion 45's
// 501 without one.

it('answers 501 on every token route without a driver, and the tools an error', function (): void {
    $ada = user();
    $this->actingAs($ada);

    $this->getJson(route('guardian.tokens.index'))->assertStatus(501)->assertExactJson(['message' => 'No API token driver is configured.']);
    $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI'])->assertStatus(501);
    $this->deleteJson(route('guardian.tokens.destroy', 'x'))->assertStatus(501);
    $this->getJson(route('guardian.users.tokens.index', $ada->getKey()))->assertStatus(501);
    $this->deleteJson(route('guardian.users.tokens.destroy', $ada->getKey()))->assertStatus(501);

    mcpTool(ListTokensTool::class)->assertHasErrors(['No API token driver is configured.']);
    mcpTool(RevokeTokenTool::class, ['token' => 'x'])->assertHasErrors(['No API token driver is configured.']);
});

it('issues a token: 201 with the plain token once, then lists it without it', function (): void {
    $driver = fakeTokenDriver();
    $this->actingAs($ada = user());

    $response = $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI', 'abilities' => ['*'], 'expires_at' => now()->addDays(30)->toIso8601String()])
        ->assertCreated()
        ->assertJsonPath('data.name', 'CLI')
        ->assertJsonPath('data.abilities', ['*']);
    $plain = $response->json('plain_text_token');

    expect($plain)->toBeString()->not->toBe('')
        ->and($driver->tokens($ada))->toHaveCount(1);

    $index = $this->getJson(route('guardian.tokens.index'))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $response->json('data.id'));
    expect($index->getContent())->not->toContain($plain);
});

it('requires a name, keeps abilities within guardian.tokens.abilities and expiry within max_lifetime_days', function (): void {
    fakeTokenDriver();
    $this->actingAs(user());

    $this->postJson(route('guardian.tokens.store'))->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI', 'abilities' => ['admin']])->assertUnprocessable()->assertJsonValidationErrors('abilities.0');
    $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI', 'expires_at' => now()->addDays(366)->toIso8601String()])->assertUnprocessable()->assertJsonValidationErrors('expires_at');
    $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI', 'expires_at' => now()->subDay()->toIso8601String()])->assertUnprocessable()->assertJsonValidationErrors('expires_at');

    config()->set('guardian.tokens.abilities', ['read', 'write']);
    config()->set('guardian.tokens.max_lifetime_days', 30);

    $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI', 'abilities' => ['*']])->assertUnprocessable()->assertJsonValidationErrors('abilities.0');
    $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI', 'abilities' => ['read']])->assertCreated();
    $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI', 'expires_at' => now()->addDays(31)->toIso8601String()])->assertUnprocessable();
});

it('defaults the abilities to the allow-list and the expiry to the maximum lifetime', function (): void {
    fakeTokenDriver();
    $this->actingAs(user());
    $this->freezeSecond();

    $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI'])
        ->assertCreated()
        ->assertJsonPath('data.abilities', ['*'])
        ->assertJsonPath('data.expires_at', now()->addDays(365)->toIso8601String());

    config()->set('guardian.tokens.max_lifetime_days', null);

    $this->postJson(route('guardian.tokens.store'), ['name' => 'Forever'])->assertCreated()->assertJsonPath('data.expires_at', null);
});

it('revokes one of your tokens; another user\'s is not found', function (): void {
    $driver = fakeTokenDriver();
    $ada = user();
    $mine = $driver->issue($ada, 'Mine', ['*'], null);
    $theirs = $driver->issue(user(), 'Theirs', ['*'], null);
    $this->actingAs($ada);

    $this->deleteJson(route('guardian.tokens.destroy', $theirs->token->id))->assertNotFound();
    $this->deleteJson(route('guardian.tokens.destroy', $mine->token->id))->assertNoContent();
    $this->deleteJson(route('guardian.tokens.destroy', $mine->token->id))->assertNotFound();
});

it('lists and revokes another user\'s tokens with guardian.tokens.manage', function (): void {
    config()->set('guardian.authorization.enabled', true);
    $driver = fakeTokenDriver();
    $ada = user();
    $driver->issue($ada, 'One', ['*'], null);
    $driver->issue($ada, 'Two', ['*'], null);

    $this->actingAs(user())->getJson(route('guardian.users.tokens.index', $ada->getKey()))->assertForbidden();
    $this->deleteJson(route('guardian.users.tokens.destroy', $ada->getKey()))->assertForbidden();

    $admin = user();
    assignDirectly($admin, roleGranting('token-admin', ['guardian.tokens.manage']));
    $this->actingAs($admin);

    $this->getJson(route('guardian.users.tokens.index', $ada->getKey()))->assertOk()->assertJsonCount(2, 'data');
    $this->deleteJson(route('guardian.users.tokens.destroy', $ada->getKey()))->assertOk()->assertExactJson(['revoked' => 2]);
    $this->getJson(route('guardian.users.tokens.index', 'nobody'))->assertNotFound();

    expect($driver->tokens($ada))->toBe([]);
});

it('records token.issued and token.revoked without token values, and every action\'s events', function (): void {
    $driver = fakeTokenDriver();
    $this->actingAs($ada = user());
    Event::fake([
        TokensListingActionEvent::class, TokensListedActionEvent::class,
        TokenIssuingActionEvent::class, TokenIssuedActionEvent::class,
        TokenRevokingActionEvent::class, TokenRevokedActionEvent::class,
        UserTokensListingActionEvent::class, UserTokensListedActionEvent::class,
        UserTokensRevokingActionEvent::class, UserTokensRevokedActionEvent::class,
    ]);

    $plain = $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI'])->assertCreated()->json('plain_text_token');
    $id = $driver->tokens($ada)[0]->id;
    $this->getJson(route('guardian.tokens.index'))->assertOk();
    $this->deleteJson(route('guardian.tokens.destroy', $id))->assertNoContent();
    $driver->issue($ada, 'Second', ['*'], null);
    $this->getJson(route('guardian.users.tokens.index', $ada->getKey()))->assertOk();
    $this->deleteJson(route('guardian.users.tokens.destroy', $ada->getKey()))->assertOk();

    $issued = SecurityEventModel::query()->where('type', 'token.issued')->sole();
    expect($issued->context)->toMatchArray(['id' => $id, 'name' => 'CLI'])
        ->and(SecurityEventModel::query()->where('type', 'token.revoked')->count())->toBe(2)
        ->and((string) json_encode(SecurityEventModel::query()->get()->toArray()))->not->toContain($plain)->not->toContain(explode('|', (string) $plain)[1]);

    foreach ([TokensListingActionEvent::class, TokensListedActionEvent::class, TokenIssuingActionEvent::class, TokenIssuedActionEvent::class, TokenRevokingActionEvent::class, TokenRevokedActionEvent::class, UserTokensListingActionEvent::class, UserTokensListedActionEvent::class, UserTokensRevokingActionEvent::class, UserTokensRevokedActionEvent::class] as $event) {
        Event::assertDispatched($event, fn (object $dispatched): bool => ! str_contains(serialize($dispatched), (string) $plain));
    }
});

it('lists and revokes over MCP like HTTP, and cannot issue', function (): void {
    $driver = fakeTokenDriver();
    $ada = user();
    $token = $driver->issue($ada, 'CLI', ['*'], now()->addDay()->toImmutable());
    $this->actingAs($ada);

    expect(GuardianServer::TOOLS)->toContain(ListTokensTool::class, RevokeTokenTool::class, ListUserTokensTool::class, RevokeUserTokensTool::class)
        ->and(array_filter(GuardianServer::TOOLS, fn (string $tool): bool => str_contains($tool, 'Issue') || str_contains($tool, 'CreateToken')))->toBe([]);

    mcpTool(ListTokensTool::class)->assertOk()->assertStructuredContent($this->getJson(route('guardian.tokens.index'))->json());
    mcpTool(ListUserTokensTool::class, ['user' => (string) $ada->getKey()])->assertOk()->assertStructuredContent($this->getJson(route('guardian.users.tokens.index', $ada->getKey()))->json());
    mcpTool(RevokeTokenTool::class, ['token' => $token->token->id])->assertOk()->assertSee('Token revoked.');
    mcpTool(RevokeTokenTool::class, ['token' => $token->token->id])->assertHasErrors(['Not found.']);

    $driver->issue($ada, 'Again', ['*'], null);
    mcpTool(RevokeUserTokensTool::class, ['user' => (string) $ada->getKey()])->assertOk()->assertStructuredContent(['revoked' => 1]);
});

it('issues and lists through Sanctum end to end', function (): void {
    if (! class_exists(Sanctum::class)) {
        $this->markTestSkipped('laravel/sanctum is not installed.');
    }

    useSanctum();
    $ada = sanctumUser();
    $this->actingAs($ada);

    $plain = $this->postJson(route('guardian.tokens.store'), ['name' => 'CLI', 'abilities' => ['*']])->assertCreated()->json('plain_text_token');

    $this->getJson(route('guardian.tokens.index'))->assertOk()->assertJsonPath('data.0.name', 'CLI');
    expect($plain)->toContain('|');
});
