<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\CreateSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ListSsoIdentitiesTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ShowSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;

it('manages connections over HTTP and MCP without exposing secrets', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);

    $this->postJson(route('guardian.organizations.sso-connections.store', 'acme'), [
        'name' => 'Entra', 'slug' => 'acme-entra', 'protocol' => 'azure',
        'tenant' => 'acme.onmicrosoft.com', 'client_id' => 'app', 'client_secret' => 'top-secret',
    ])
        ->assertCreated()
        ->assertJsonPath('data.settings.tenant', 'acme.onmicrosoft.com')
        ->assertJsonPath('data.client_secret', 'set')
        ->assertJsonPath('data.callback_url', route('guardian.sso.callback', 'acme-entra'));

    $this->patchJson(route('guardian.sso-connections.update', 'acme-entra'), ['enforced' => true])->assertOk()->assertJsonPath('data.enforced', true);

    mcpTool(ShowSsoConnectionTool::class, ['connection' => 'acme-entra'])
        ->assertOk()
        ->assertStructuredContent(test()->getJson(route('guardian.sso-connections.show', 'acme-entra'))->json())
        ->assertDontSee('top-secret');

    mcpTool(CreateSsoConnectionTool::class, ['organization' => 'acme', 'name' => 'Okta', 'protocol' => 'oidc', 'issuer' => 'https://idp.test', 'client_id' => 'a', 'client_secret' => 'b'])->assertOk();

    $this->getJson(route('guardian.organizations.sso-connections.index', 'acme'))->assertOk()->assertJsonPath('meta.total', 2);
    $this->deleteJson(route('guardian.sso-connections.destroy', 'acme-entra'))->assertNoContent();
});

it('lists and unlinks identities', function (): void {
    require_once dirname(__DIR__).'/Sso/helpers.php';
    $connection = acmeWithSso();
    $ada = user();
    $identity = SsoIdentityModel::query()->create(['connection_id' => $connection->id, 'user_id' => $ada->getKey(), 'subject' => 's1', 'email' => 'ada@acme.test']);

    $this->getJson(route('guardian.users.sso-identities.index', $ada->getRouteKey()))->assertOk()->assertJsonPath('data.0.subject', 's1');
    mcpTool(ListSsoIdentitiesTool::class, ['user' => $ada->getRouteKey()])->assertOk()->assertSee('s1');

    $this->deleteJson(route('guardian.sso-identities.destroy', $identity->id))->assertNoContent();
    expect(SsoIdentityModel::query()->count())->toBe(0);
});
