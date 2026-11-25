<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/LoginProtection/helpers.php';
require_once dirname(__DIR__).'/Permission/helpers.php';

use Illuminate\Support\Facades\Event;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRuleCreatedActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRuleCreatingActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRuleDeletedActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRuleDeletingActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRulesListedActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRulesListingActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\LockoutClearedActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\LockoutClearingActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Tools\ClearLockoutTool;
use RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Tools\CreateIpRuleTool;
use RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Tools\DeleteIpRuleTool;
use RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Tools\ListIpRulesTool;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Mcp\GuardianServer;

// Criteria 50 and 52: clearing lockouts and managing IP rules over HTTP and MCP.

beforeEach(fn () => withLoginRoutes());

it('clears an identifier\'s lockout and records lockout.cleared', function (): void {
    foreach (range(1, 5) as $failure) {
        attempt('ada@example.com');
    }

    attempt('ada@example.com')->assertTooManyRequests();

    $admin = user();
    $this->actingAs($admin)->deleteJson(route('guardian.lockouts.destroy'), ['identifier' => 'ADA@example.com'])->assertNoContent();

    attempt('ada@example.com')->assertStatus(422);

    $event = SecurityEventModel::query()->where('type', 'lockout.cleared')->sole();
    expect($event->context)->toMatchArray(['by' => (string) $admin->getKey()])
        ->and($event->identifier_hash)->not->toBeNull()
        ->and((string) json_encode($event->toArray()))->not->toContain('example.com');

    $this->deleteJson(route('guardian.lockouts.destroy'))->assertUnprocessable()->assertJsonValidationErrors('identifier');
});

it('clears a lockout over MCP', function (): void {
    foreach (range(1, 5) as $failure) {
        attempt('ada@example.com');
    }

    $this->actingAs(user());
    mcpTool(ClearLockoutTool::class, ['identifier' => 'ada@example.com'])->assertOk()->assertSee('Lockout cleared.');

    attempt('ada@example.com')->assertStatus(422);
});

it('creates, lists and deletes IP rules over HTTP', function (): void {
    $admin = user();
    $this->actingAs($admin);

    $id = $this->postJson(route('guardian.ip-rules.store'), ['cidr' => '203.0.113.0/24', 'action' => 'deny', 'description' => 'Abuse', 'expires_at' => now()->addDay()->toIso8601String()])
        ->assertCreated()
        ->assertJsonPath('data.cidr', '203.0.113.0/24')
        ->assertJsonPath('data.action', 'deny')
        ->assertJsonPath('data.description', 'Abuse')
        ->assertJsonPath('data.created_by', (string) $admin->getKey())
        ->json('data.id');

    $this->getJson(route('guardian.ip-rules.index'))->assertOk()->assertJsonPath('data.0.id', $id)->assertJsonPath('meta.total', 1);
    fromIp('203.0.113.9')->assertForbidden();

    $this->deleteJson(route('guardian.ip-rules.destroy', $id))->assertNoContent();
    $this->deleteJson(route('guardian.ip-rules.destroy', $id))->assertNotFound();
    fromIp('203.0.113.9')->assertOk();
});

it('answers 422 for an invalid CIDR or action', function (string $cidr): void {
    $this->actingAs(user())->postJson(route('guardian.ip-rules.store'), ['cidr' => $cidr, 'action' => 'deny'])->assertUnprocessable()->assertJsonValidationErrors('cidr');
})->with(['nonsense', '203.0.113.0/33', '2001:db8::/129', '999.1.1.1/24', '10.0.0.0/', '/24']);

it('validates the action and expiry', function (): void {
    $this->actingAs(user());

    $this->postJson(route('guardian.ip-rules.store'), ['cidr' => '10.0.0.0/8', 'action' => 'block'])->assertUnprocessable()->assertJsonValidationErrors('action');
    $this->postJson(route('guardian.ip-rules.store'), ['cidr' => '10.0.0.0/8', 'action' => 'deny', 'expires_at' => now()->subDay()->toIso8601String()])->assertUnprocessable()->assertJsonValidationErrors('expires_at');
});

it('needs guardian.security.manage', function (): void {
    config()->set('guardian.authorization.enabled', true);
    $rule = IpRuleModel::query()->create(['cidr' => '10.0.0.0/8', 'action' => 'allow']);

    $this->getJson(route('guardian.ip-rules.index'))->assertUnauthorized();

    $this->actingAs(user());
    $this->getJson(route('guardian.ip-rules.index'))->assertForbidden();
    $this->postJson(route('guardian.ip-rules.store'), ['cidr' => '10.0.0.0/8', 'action' => 'deny'])->assertForbidden();
    $this->deleteJson(route('guardian.ip-rules.destroy', $rule->id))->assertForbidden();
    $this->deleteJson(route('guardian.lockouts.destroy'), ['identifier' => 'a@b.c'])->assertForbidden();

    $admin = user();
    assignDirectly($admin, roleGranting('security-admin', ['guardian.security.manage']));
    $this->actingAs($admin);

    $this->getJson(route('guardian.ip-rules.index'))->assertOk();
    $this->deleteJson(route('guardian.lockouts.destroy'), ['identifier' => 'a@b.c'])->assertNoContent();
});

it('offers the IP rule tools, answering like HTTP', function (): void {
    expect(GuardianServer::TOOLS)->toContain(ClearLockoutTool::class, ListIpRulesTool::class, CreateIpRuleTool::class, DeleteIpRuleTool::class);
    $this->actingAs(user());

    $created = mcpTool(CreateIpRuleTool::class, ['cidr' => '2001:db8::/32', 'action' => 'deny'])->assertOk();
    $rule = IpRuleModel::query()->sole();

    $created->assertStructuredContent(['data' => $this->getJson(route('guardian.ip-rules.index'))->json('data.0')]);
    mcpTool(ListIpRulesTool::class)->assertOk()->assertSee($rule->id);
    mcpTool(CreateIpRuleTool::class, ['cidr' => 'nonsense', 'action' => 'deny'])->assertHasErrors();
    mcpTool(DeleteIpRuleTool::class, ['rule' => $rule->id])->assertOk()->assertSee('IP rule deleted.');

    expect(IpRuleModel::query()->count())->toBe(0);
});

it('dispatches a starting and a finished event for every action', function (): void {
    Event::fake([
        LockoutClearingActionEvent::class, LockoutClearedActionEvent::class,
        IpRuleCreatingActionEvent::class, IpRuleCreatedActionEvent::class,
        IpRuleDeletingActionEvent::class, IpRuleDeletedActionEvent::class,
        IpRulesListingActionEvent::class, IpRulesListedActionEvent::class,
    ]);
    $this->actingAs(user());

    $this->deleteJson(route('guardian.lockouts.destroy'), ['identifier' => 'ada@example.com'])->assertNoContent();
    $id = $this->postJson(route('guardian.ip-rules.store'), ['cidr' => '10.0.0.0/8', 'action' => 'allow'])->assertCreated()->json('data.id');
    $this->getJson(route('guardian.ip-rules.index'))->assertOk();
    $this->deleteJson(route('guardian.ip-rules.destroy', $id))->assertNoContent();

    Event::assertDispatched(LockoutClearingActionEvent::class, fn (LockoutClearingActionEvent $event): bool => ! str_contains(serialize($event), 'ada@example.com'));
    Event::assertDispatched(LockoutClearedActionEvent::class, fn (LockoutClearedActionEvent $event): bool => ! str_contains(serialize($event), 'ada@example.com'));

    foreach ([IpRuleCreatingActionEvent::class, IpRuleCreatedActionEvent::class, IpRuleDeletingActionEvent::class, IpRuleDeletedActionEvent::class, IpRulesListingActionEvent::class, IpRulesListedActionEvent::class] as $event) {
        Event::assertDispatched($event);
    }
});

it('pages through the whole tool catalog, past search_tools\' 50 a call', function (): void {
    expect(count(GuardianServer::TOOLS))->toBeGreaterThan(50)
        ->and(toolCatalog())->toEqualCanonicalizing(array_map(fn (string $tool): string => app($tool)->name(), GuardianServer::TOOLS));
});
