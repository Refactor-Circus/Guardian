<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\CreateSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\DeleteSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ListSsoConnectionsTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ListSsoIdentitiesTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ShowSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\UnlinkSsoIdentityTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\UpdateSsoConnectionTool;
use RefactorCircus\Guardian\Mcp\GuardianServer;

// Criterion 26, SSO half: GuardianServer offers Roster's seven SSO tools with
// the names, descriptions and schemas they had in Roster.

const SSO_TOOLS = [
    ListSsoConnectionsTool::class,
    ShowSsoConnectionTool::class,
    CreateSsoConnectionTool::class,
    UpdateSsoConnectionTool::class,
    DeleteSsoConnectionTool::class,
    ListSsoIdentitiesTool::class,
    UnlinkSsoIdentityTool::class,
];

/**
 * A tool as a client sees it, with permission names written Guardian's way.
 *
 * @param  class-string<Tool>  $tool
 * @return array<string, mixed>
 */
function toolDefinition(string $tool): array
{
    /** @var Tool $instance */
    $instance = app($tool);

    /** @var array<string, mixed> */
    return json_decode(str_replace('roster.sso.', 'guardian.sso.', (string) json_encode($instance->toArray())), true);
}

it('offers the seven SSO tools behind tool search', function (): void {
    expect(array_intersect(GuardianServer::TOOLS, SSO_TOOLS))->toHaveCount(7)
        ->and((new GuardianServer(new FakeTransporter))->createContext()->tools())->not->toBeEmpty();
});

it('declares an output schema on every tool that answers with structured content', function (string $tool): void {
    /** @var Tool $instance */
    $instance = app($tool);
    $request = 'RefactorCircus\\Guardian\\Domains\\Sso\\Mcp\\Requests\\'.str_replace('Tool', 'McpRequest', class_basename($tool));
    $source = (string) file_get_contents((string) (new ReflectionClass($tool))->getFileName())
        .(class_exists($request) ? (string) file_get_contents((string) (new ReflectionClass($request))->getFileName()) : '');

    // Delete and unlink answer with a plain message, as they did in Roster.
    if (preg_match('/toStructuredResponse\(|Response::structured\(|structuredCollection\(/', $source) !== 1) {
        expect($instance->toArray()['outputSchema'] ?? [])->toBeEmpty();

        return;
    }

    expect($instance->toArray()['outputSchema'] ?? [])->not->toBeEmpty();
})->with(SSO_TOOLS);

it('keeps each tool\'s name, description and schemas from Roster', function (string $tool): void {
    $roster = 'RefactorCircus\\Roster\\Domains\\Sso\\Mcp\\Tools\\'.class_basename($tool);

    expect(toolDefinition($tool))->toBe(toolDefinition($roster));
})->with(SSO_TOOLS)
    ->skip(fn (): bool => ! class_exists('RefactorCircus\\Roster\\Domains\\Sso\\Mcp\\Tools\\ListSsoConnectionsTool'), 'The installed Roster no longer ships SSO to compare with.');

it('answers over MCP with the same payload as the HTTP API', function (): void {
    organization(attributes: ['name' => 'Acme']);

    $this->postJson(route('guardian.organizations.sso-connections.store', 'acme'), [
        'name' => 'Okta', 'slug' => 'acme-okta', 'protocol' => 'oidc',
        'issuer' => 'https://idp.test', 'client_id' => 'app', 'client_secret' => 'top-secret',
    ])->assertCreated();

    mcpTool(ShowSsoConnectionTool::class, ['connection' => 'acme-okta'])
        ->assertOk()
        ->assertStructuredContent($this->getJson(route('guardian.sso-connections.show', 'acme-okta'))->json());
});
