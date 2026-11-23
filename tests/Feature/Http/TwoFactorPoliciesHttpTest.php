<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Permission/helpers.php';

use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\CreateTwoFactorPolicyTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\DeleteTwoFactorPolicyTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\ListTwoFactorPoliciesTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\UpdateTwoFactorPolicyTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;

// Criterion 34: the policies over HTTP and MCP, with guardian.two-factor.manage.

it('creates, lists, updates and deletes policies over HTTP', function (): void {
    $folder = folder();
    roleGranting('editor', []);

    $id = $this->postJson(route('guardian.two-factor-policies.store'), [
        'scope_type' => FolderScope::class,
        'scope_id' => $folder->id,
        'role' => 'editor',
        'methods' => ['totp', 'passkey'],
        'grace_days' => 14,
    ])
        ->assertCreated()
        ->assertJsonPath('data.scope_type', (new FolderScope)->getMorphClass())
        ->assertJsonPath('data.scope_id', $folder->id)
        ->assertJsonPath('data.role', 'editor')
        ->assertJsonPath('data.required', true)
        ->assertJsonPath('data.methods', ['totp', 'passkey'])
        ->assertJsonPath('data.grace_days', 14)
        ->json('data.id');

    $this->getJson(route('guardian.two-factor-policies.index'))->assertOk()->assertJsonPath('data.0.id', $id)->assertJsonPath('meta.total', 1);
    $this->patchJson(route('guardian.two-factor-policies.update', $id), ['required' => false])->assertOk()->assertJsonPath('data.required', false)->assertJsonPath('data.grace_days', 14);
    $this->deleteJson(route('guardian.two-factor-policies.destroy', $id))->assertNoContent();
    $this->deleteJson(route('guardian.two-factor-policies.destroy', $id))->assertNotFound();
});

it('validates policies', function (): void {
    FolderScope::migrate();
    $this->postJson(route('guardian.two-factor-policies.store'), ['role' => 'nobody'])->assertUnprocessable()->assertJsonValidationErrors('role');
    $this->postJson(route('guardian.two-factor-policies.store'), ['methods' => ['pigeon']])->assertUnprocessable()->assertJsonValidationErrors('methods.0');
    $this->postJson(route('guardian.two-factor-policies.store'), ['methods' => []])->assertUnprocessable()->assertJsonValidationErrors('methods');
    $this->postJson(route('guardian.two-factor-policies.store'), ['grace_days' => -1])->assertUnprocessable()->assertJsonValidationErrors('grace_days');
    $this->postJson(route('guardian.two-factor-policies.store'), ['scope_type' => 'Nope', 'scope_id' => '1'])->assertUnprocessable()->assertJsonValidationErrors('scope_type');
    $this->postJson(route('guardian.two-factor-policies.store'), ['scope_type' => FolderScope::class, 'scope_id' => 'missing'])->assertUnprocessable();
});

it('needs guardian.two-factor.manage', function (): void {
    config()->set('guardian.authorization.enabled', true);
    $policy = TwoFactorPolicyModel::query()->create([]);

    $this->getJson(route('guardian.two-factor-policies.index'))->assertUnauthorized();

    $this->actingAs(user());
    $this->getJson(route('guardian.two-factor-policies.index'))->assertForbidden();
    $this->postJson(route('guardian.two-factor-policies.store'))->assertForbidden();
    $this->patchJson(route('guardian.two-factor-policies.update', $policy->id), ['grace_days' => 1])->assertForbidden();
    $this->deleteJson(route('guardian.two-factor-policies.destroy', $policy->id))->assertForbidden();

    $admin = user();
    assignDirectly($admin, roleGranting('security-admin', ['guardian.two-factor.manage']));
    $this->actingAs($admin);

    $this->getJson(route('guardian.two-factor-policies.index'))->assertOk();
    $this->postJson(route('guardian.two-factor-policies.store'))->assertCreated();
});

it('offers the policy tools with output schemas, answering like the HTTP API', function (): void {
    expect(GuardianServer::TOOLS)->toContain(ListTwoFactorPoliciesTool::class, CreateTwoFactorPolicyTool::class, UpdateTwoFactorPolicyTool::class, DeleteTwoFactorPolicyTool::class);

    $created = mcpTool(CreateTwoFactorPolicyTool::class, ['methods' => ['totp'], 'grace_days' => 2])->assertOk();
    $policy = TwoFactorPolicyModel::query()->sole();

    mcpTool(ListTwoFactorPoliciesTool::class)->assertOk()->assertSee($policy->id)->assertSee('"total":1');
    $created->assertStructuredContent(['data' => $this->getJson(route('guardian.two-factor-policies.index'))->json('data.0')]);
    mcpTool(UpdateTwoFactorPolicyTool::class, ['policy' => $policy->id, 'grace_days' => 9])->assertOk()->assertSee('"grace_days":9');
    mcpTool(DeleteTwoFactorPolicyTool::class, ['policy' => $policy->id])->assertOk();

    expect(TwoFactorPolicyModel::query()->count())->toBe(0);
});
