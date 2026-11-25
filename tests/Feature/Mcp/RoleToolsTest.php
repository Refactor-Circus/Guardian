<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Permission/helpers.php';

use Laravel\Mcp\Server\Tool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\CreateRoleTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\DeleteRoleTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\ListRolesTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\ShowRoleTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\UpdateRoleTool;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;

// Criterion 18: a tool for every role action, each with an output schema,
// answering as the HTTP API does.

const ROLE_TOOLS = [ListRolesTool::class, ShowRoleTool::class, CreateRoleTool::class, UpdateRoleTool::class, DeleteRoleTool::class];

it('offers every role tool', function (): void {
    expect(GuardianServer::TOOLS)->toContain(...ROLE_TOOLS);
});

it('declares an output schema on every role tool that answers with data', function (string $tool): void {
    /** @var Tool $instance */
    $instance = app($tool);

    expect($instance->toArray()['outputSchema'] ?? [])->not->toBeEmpty();
})->with(array_values(array_diff(ROLE_TOOLS, [DeleteRoleTool::class])));

it('manages roles over MCP with the HTTP API\'s payloads', function (): void {
    $folder = folder();
    $scope = ['scope_type' => FolderScope::class, 'scope_id' => $folder->id];

    mcpTool(CreateRoleTool::class, ['name' => 'Editors', 'permissions' => ['guardian.sso.view']] + $scope)
        ->assertOk()
        ->assertSee('editors');

    mcpTool(ShowRoleTool::class, ['role' => 'editors'] + $scope)
        ->assertOk()
        ->assertStructuredContent(test()->getJson(route('guardian.roles.show', ['editors'] + $scope))->json());

    mcpTool(UpdateRoleTool::class, ['role' => 'editors', 'name' => 'Writers'] + $scope)->assertOk()->assertSee('Writers');

    mcpTool(ListRolesTool::class, $scope)->assertOk()->assertSee(['Writers', 'super-admin']);
    mcpTool(ListRolesTool::class)->assertOk()->assertDontSee('Writers');

    mcpTool(DeleteRoleTool::class, ['role' => 'editors'] + $scope)->assertOk()->assertSee('Role deleted.');
    mcpTool(ShowRoleTool::class, ['role' => 'editors'] + $scope)->assertHasErrors(['Not found.']);

    expect(RoleModel::query()->where('slug', 'editors')->exists())->toBeFalse();
});

it('refuses what the action refuses', function (): void {
    mcpTool(DeleteRoleTool::class, ['role' => 'admin'])->assertHasErrors();

    expect(RoleModel::query()->where('slug', 'admin')->exists())->toBeTrue();
});
