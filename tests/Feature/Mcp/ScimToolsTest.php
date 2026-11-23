<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Tool;
use RefactorCircus\Guardian\Domains\Scim\Mcp\Tools\CreateScimTokenTool;
use RefactorCircus\Guardian\Domains\Scim\Mcp\Tools\ListScimTokensTool;
use RefactorCircus\Guardian\Domains\Scim\Mcp\Tools\RevokeScimTokenTool;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\CreateSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\DeleteSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ListSsoConnectionsTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ListSsoIdentitiesTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ShowSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\UnlinkSsoIdentityTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\UpdateSsoConnectionTool;
use RefactorCircus\Guardian\Mcp\GuardianServer;

// Criterion 26: GuardianServer offers exactly the ten tools that moved from
// Roster, with the names, descriptions and schemas they had there; this file
// covers the listing and the three SCIM tools (SsoToolsTest the SSO ones).

const SCIM_TOOLS = [
    ListScimTokensTool::class,
    CreateScimTokenTool::class,
    RevokeScimTokenTool::class,
];

const GUARDIAN_TOOLS = [
    // Single sign-on
    ListSsoConnectionsTool::class,
    ShowSsoConnectionTool::class,
    CreateSsoConnectionTool::class,
    UpdateSsoConnectionTool::class,
    DeleteSsoConnectionTool::class,
    ListSsoIdentitiesTool::class,
    UnlinkSsoIdentityTool::class,
    // SCIM provisioning
    ListScimTokensTool::class,
    CreateScimTokenTool::class,
    RevokeScimTokenTool::class,
];

/**
 * A tool as a client sees it, with permission names written Guardian's way.
 *
 * @param  class-string<Tool>  $tool
 * @return array<string, mixed>
 */
function scimToolDefinition(string $tool): array
{
    /** @var Tool $instance */
    $instance = app($tool);

    /** @var array<string, mixed> */
    return json_decode(str_replace(['roster.sso.', 'roster.scim.'], ['guardian.sso.', 'guardian.scim.'], (string) json_encode($instance->toArray())), true);
}

/**
 * The Roster tool a Guardian tool moved from.
 */
function rosterToolFor(string $tool): string
{
    $domain = str_contains($tool, '\\Domains\\Scim\\') ? 'Scim' : 'Sso';

    return 'RefactorCircus\\Roster\\Domains\\'.$domain.'\\Mcp\\Tools\\'.class_basename($tool);
}

it('lists the ten tools that moved from Roster, in order, among the others', function (): void {
    // The tools sit behind tool search, so browse the catalog as a client
    // does, paging past search_tools' 50 a call.
    $catalog = toolCatalog();

    expect($catalog)->toHaveCount(count(GuardianServer::TOOLS))
        ->and(array_values(array_filter($catalog, fn (string $name): bool => str_contains($name, 'sso') || str_contains($name, 'scim'))))
        ->toBe(array_map(fn (string $tool): string => app($tool)->name(), GUARDIAN_TOOLS))
        ->toHaveCount(10);
});

it('keeps each of the ten tools\' name, description and schemas from Roster', function (string $tool): void {
    expect(scimToolDefinition($tool))->toBe(pinnedRosterTool(rosterToolFor($tool)));
})->with(GUARDIAN_TOOLS);

it('declares an output schema on every SCIM tool, as each answers with structured content', function (string $tool): void {
    /** @var Tool $instance */
    $instance = app($tool);

    expect($instance->toArray()['outputSchema'] ?? [])->not->toBeEmpty();
})->with(SCIM_TOOLS);

it('answers over MCP with the same payload as the HTTP API', function (): void {
    organization(attributes: ['name' => 'Acme']);

    $this->postJson(route('guardian.organizations.scim-tokens.store', 'acme'), ['name' => 'Okta'])->assertCreated();
    $token = ScimTokenModel::query()->where('name', 'Okta')->sole();

    $http = $this->deleteJson(route('guardian.scim-tokens.destroy', $token->id))->assertOk()->json();

    // Revoking twice changes nothing, so both answers describe the same token.
    mcpTool(RevokeScimTokenTool::class, ['token' => $token->id])
        ->assertOk()
        ->assertStructuredContent($http);
});
