<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Permission/helpers.php';

use Laravel\Mcp\Server\Tool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\AssignRoleTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\ListRoleAssignmentsTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\ListUserAbilitiesTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\RevokeRoleTool;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;

// Criterion 18: the assignment and ability tools.

it('offers every assignment tool, with output schemas where they answer with data', function (): void {
    expect(GuardianServer::TOOLS)->toContain(ListRoleAssignmentsTool::class, AssignRoleTool::class, RevokeRoleTool::class, ListUserAbilitiesTool::class);

    foreach ([ListRoleAssignmentsTool::class, AssignRoleTool::class, ListUserAbilitiesTool::class] as $tool) {
        /** @var Tool $instance */
        $instance = app($tool);

        expect($instance->toArray()['outputSchema'] ?? [])->not->toBeEmpty();
    }
});

it('assigns, lists and revokes roles over MCP with the HTTP API\'s payloads', function (): void {
    $ada = user();
    $folder = folder();
    $scope = ['scope_type' => FolderScope::class, 'scope_id' => $folder->id];

    mcpTool(AssignRoleTool::class, ['user' => (string) $ada->getKey(), 'role' => 'admin'] + $scope)->assertOk()->assertSee('admin');
    $assignment = RoleAssignmentModel::query()->sole();

    mcpTool(ListUserAbilitiesTool::class, ['user' => (string) $ada->getKey()] + $scope)
        ->assertOk()
        ->assertStructuredContent(test()->getJson(route('guardian.users.abilities.index', [$ada->getKey()] + $scope))->json());

    mcpTool(ListRoleAssignmentsTool::class, ['user' => (string) $ada->getKey()])->assertOk()->assertSee($assignment->id);

    mcpTool(RevokeRoleTool::class, ['assignment' => $assignment->id])->assertOk()->assertSee('Role revoked.');
    mcpTool(RevokeRoleTool::class, ['assignment' => $assignment->id])->assertHasErrors(['Not found.']);

    expect(RoleAssignmentModel::query()->count())->toBe(0);
});
