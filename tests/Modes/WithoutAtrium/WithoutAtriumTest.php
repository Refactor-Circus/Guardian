<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\DomainServiceProvider;
use RefactorCircus\Guardian\Domains\Scim\Actions\CreateScimTokenAction;
use RefactorCircus\Guardian\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\ScimServiceProvider;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Domains\Sso\SsoServiceProvider;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Support\Integration;

/*
 * Guardian runs without Atrium (criterion 30, D-020): with Roster installed
 * and Atrium absent, the domains, the JSON API, the SSO and SCIM endpoints
 * and the MCP tools all work, and nothing reaches Atrium. These tests also
 * run in the `without-atrium` CI job, with Atrium uninstalled, so they name
 * no Atrium class.
 */

const ATRIUM_CLASSES = [
    'RefactorCircus\\Atrium\\Atrium',
    'RefactorCircus\\Atrium\\Domains\\Screens\\Services\\ScreenExtensions',
    'RefactorCircus\\Atrium\\Domains\\Plugins\\Services\\PluginRegistry',
];

/**
 * Acme on acme.test, with a connection, a member's identity on it and a
 * SCIM token whose plain value is returned.
 *
 * @return array<string, mixed>
 */
function withoutAtriumWorld(): array
{
    $acme = organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);
    $member = user(['email' => 'ada@acme.test']);
    $connection = SsoConnectionModel::factory()->create(['organization_id' => $acme->id, 'slug' => 'acme-okta', 'enabled' => true]);
    $identity = SsoIdentityModel::query()->create(['connection_id' => $connection->id, 'user_id' => $member->getKey(), 'subject' => 'sub-1']);
    $issued = app(CreateScimTokenAction::class)->execute($acme, CreateScimTokenData::from(['name' => 'Okta']));

    return compact('acme', 'member', 'connection', 'identity') + ['token' => $issued->plain, 'scimToken' => ScimTokenModel::query()->sole()];
}

it('sees Roster and no Atrium', function (): void {
    expect(app(Integration::class)->rosterInstalled())->toBeTrue()
        ->and(app(Integration::class)->atriumInstalled())->toBeFalse();
});

it('loads its domain providers', function (): void {
    expect(app()->getProviders(DomainServiceProvider::class))->not->toBe([])
        ->and(app()->getProviders(SsoServiceProvider::class))->not->toBe([])
        ->and(app()->getProviders(ScimServiceProvider::class))->not->toBe([]);
});

it('serves the JSON API', function (): void {
    withoutAtriumWorld();
    $this->actingAs(user());

    $this->getJson(route('guardian.organizations.sso-connections.index', 'acme'))
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'acme-okta');

    $this->getJson(route('guardian.organizations.scim-tokens.index', 'acme'))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Okta');
});

it('serves the SSO sign-in routes', function (): void {
    withoutAtriumWorld();

    foreach (['discover', 'start', 'callback', 'metadata', 'link'] as $name) {
        expect(Route::has('guardian.sso.'.$name))->toBeTrue($name);
    }

    $this->get(route('guardian.sso.discover', ['email' => 'grace@acme.test']))
        ->assertRedirect(route('guardian.sso.start', 'acme-okta'));
});

it('serves the SCIM endpoint', function (): void {
    $world = withoutAtriumWorld();

    $this->getJson('/scim/v2/acme/Users', ['Authorization' => 'Bearer '.$world['token']])
        ->assertOk()
        ->assertJsonPath('schemas.0', 'urn:ietf:params:scim:api:messages:2.0:ListResponse');
});

it('answers every MCP tool', function (string $tool, Closure $arguments): void {
    $world = withoutAtriumWorld();
    $this->actingAs(user());

    mcpTool($tool, $arguments($world))->assertHasNoErrors();
})->with(fn (): array => array_map(fn (string $tool): array => [$tool, match (class_basename($tool)) {
    'ListSsoConnectionsTool', 'ListScimTokensTool' => fn (): array => ['organization' => 'acme'],
    'CreateSsoConnectionTool' => fn (): array => ['organization' => 'acme', 'name' => 'Entra', 'protocol' => 'azure', 'tenant' => 'acme.onmicrosoft.com', 'client_id' => 'app', 'client_secret' => 'secret'],
    'ShowSsoConnectionTool', 'DeleteSsoConnectionTool' => fn (): array => ['connection' => 'acme-okta'],
    'UpdateSsoConnectionTool' => fn (): array => ['connection' => 'acme-okta', 'name' => 'Okta prod'],
    'ListSsoIdentitiesTool' => fn (array $w): array => ['user' => $w['member']->getRouteKey()],
    'UnlinkSsoIdentityTool' => fn (array $w): array => ['identity' => $w['identity']->id],
    'CreateScimTokenTool' => fn (): array => ['organization' => 'acme', 'name' => 'Entra'],
    'RevokeScimTokenTool' => fn (array $w): array => ['token' => $w['scimToken']->id],
}], GuardianServer::TOOLS));

it('registers no Atrium route', function (): void {
    $atrium = array_filter(
        array_keys(Route::getRoutes()->getRoutesByName()),
        fn (string $name): bool => str_starts_with($name, 'atrium.'),
    );

    expect($atrium)->toBe([]);
});

it('registers nothing with Atrium', function (): void {
    foreach (ATRIUM_CLASSES as $class) {
        expect(app()->bound($class))->toBeFalse($class)
            ->and(app()->resolved($class))->toBeFalse($class);
    }

    expect(app('view')->getFinder()->getHints())->not->toHaveKey('atrium');
});
