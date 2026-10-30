<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Scim\Mcp\Tools\CreateScimTokenTool;
use RefactorCircus\Guardian\Domains\Scim\Mcp\Tools\ListScimTokensTool;
use RefactorCircus\Guardian\Domains\Scim\Mcp\Tools\RevokeScimTokenTool;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;

it('issues a token once, lists without it, and revokes', function (): void {
    organization(attributes: ['name' => 'Acme']);

    $response = $this->postJson(route('guardian.organizations.scim-tokens.store', 'acme'), ['name' => 'Okta', 'expires_in_days' => 30])
        ->assertCreated()
        ->assertJsonPath('data.usable', true)
        ->assertJsonPath('data.base_url', url('scim/v2/acme'));

    $token = (string) $response->json('token');

    expect($token)->toStartWith('scim_')
        ->and(ScimTokenModel::query()->sole()->token_hash)->toBe(hash('sha256', $token));

    $this->getJson(route('guardian.organizations.scim-tokens.index', 'acme'))->assertOk()->assertJsonMissingPath('token')->assertDontSee($token);

    mcpTool(ListScimTokensTool::class, ['organization' => 'acme'])->assertOk()->assertDontSee($token);
    mcpTool(CreateScimTokenTool::class, ['organization' => 'acme', 'name' => 'Entra'])->assertOk()->assertSee('scim_');

    $this->deleteJson(route('guardian.scim-tokens.destroy', ScimTokenModel::query()->where('name', 'Okta')->sole()->id))->assertOk()->assertJsonPath('data.usable', false);
    mcpTool(RevokeScimTokenTool::class, ['token' => ScimTokenModel::query()->where('name', 'Entra')->sole()->id])->assertOk();
});
