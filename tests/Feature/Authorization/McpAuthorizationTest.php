<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use RefactorCircus\Guardian\Mcp\GuardianServer;

// The SSO and SCIM cases of Roster's tests/Authorization/McpAuthorizationTest.php at
// the pinned SHA, against GuardianServer.

beforeEach(fn () => authorizationOn());

/**
 * Arguments that name existing records for every tool.
 *
 * @return array<class-string, Closure(array<string, mixed>): array<string, mixed>>
 */
function toolArguments(): array
{
    $user = fn (array $w): array => ['user' => $w['target']->getRouteKey()];
    $org = fn (): array => ['organization' => 'acme'];

    return collect(GuardianServer::TOOLS)
        ->mapWithKeys(fn (string $tool): array => [$tool => match (class_basename($tool)) {
            'ListSsoConnectionsTool', 'CreateSsoConnectionTool', 'ListScimTokensTool', 'CreateScimTokenTool' => $org,
            'ShowSsoConnectionTool', 'UpdateSsoConnectionTool', 'DeleteSsoConnectionTool' => fn (): array => ['connection' => 'acme-okta'],
            'ListSsoIdentitiesTool' => $user,
            'UnlinkSsoIdentityTool' => fn (array $w): array => ['identity' => $w['identity']->id],
            'RevokeScimTokenTool' => fn (array $w): array => ['token' => $w['scimToken']->id],
            default => fn (): array => [],
        }])
        ->all();
}

it('refuses every tool to guests and to users without the permission', function (string $tool): void {
    $world = authorizationWorld();
    $arguments = toolArguments()[$tool]($world);

    mcpTool($tool, $arguments)->assertHasErrors(['Unauthorized.']);

    $this->actingAs(user());

    mcpTool($tool, $arguments)->assertHasErrors(['Unauthorized.']);
})->with(fn (): array => array_keys(toolArguments()));

it('lets a super-admin past authorization on every tool', function (string $tool): void {
    $world = authorizationWorld();
    $admin = user();
    grant($admin, 'super-admin');
    $this->actingAs($admin);

    mcpTool($tool, toolArguments()[$tool]($world))->assertDontSee('Unauthorized.');
})->with(fn (): array => array_keys(toolArguments()));
