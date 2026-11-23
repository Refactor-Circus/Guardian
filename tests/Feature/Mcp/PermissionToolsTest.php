<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Tool;
use RefactorCircus\Guardian\Domains\Permission\Mcp\Tools\CreatePermissionTool;
use RefactorCircus\Guardian\Domains\Permission\Mcp\Tools\DeletePermissionTool;
use RefactorCircus\Guardian\Domains\Permission\Mcp\Tools\ListPermissionsTool;
use RefactorCircus\Guardian\Domains\Permission\Mcp\Tools\UpdatePermissionTool;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Mcp\GuardianServer;

// Criterion 18: the permission tools.

it('offers every permission tool, with output schemas where they answer with data', function (): void {
    expect(GuardianServer::TOOLS)->toContain(ListPermissionsTool::class, CreatePermissionTool::class, UpdatePermissionTool::class, DeletePermissionTool::class);

    foreach ([ListPermissionsTool::class, CreatePermissionTool::class, UpdatePermissionTool::class] as $tool) {
        /** @var Tool $instance */
        $instance = app($tool);

        expect($instance->toArray()['outputSchema'] ?? [])->not->toBeEmpty();
    }
});

it('manages permissions over MCP with the HTTP API\'s payloads', function (): void {
    mcpTool(CreatePermissionTool::class, ['name' => 'invoices.edit'])->assertOk()->assertSee('invoices.edit');

    mcpTool(UpdatePermissionTool::class, ['permission' => 'invoices.edit', 'description' => 'Edit invoices'])
        ->assertOk()
        ->assertStructuredContent(['data' => test()->getJson(route('guardian.permissions.index', ['search' => 'invoices']))->json('data.0')]);

    mcpTool(ListPermissionsTool::class, ['search' => 'invoices'])->assertOk()->assertSee('Edit invoices');
    mcpTool(DeletePermissionTool::class, ['permission' => 'guardian.roles.view'])->assertHasErrors();
    mcpTool(DeletePermissionTool::class, ['permission' => 'invoices.edit'])->assertOk()->assertSee('Permission deleted.');

    expect(PermissionModel::query()->where('name', 'invoices.edit')->exists())->toBeFalse();
});
