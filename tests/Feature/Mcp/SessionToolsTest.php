<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Session/helpers.php';

use Illuminate\Support\Facades\Notification;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\ForgetDeviceTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\ListDevicesTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\ListSessionsTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\ListUserSessionsTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\RevokeOtherSessionsTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\RevokeSessionTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\RevokeUserSessionsTool;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\Token\Contracts\TokenDriver;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Tests\Fixtures\FakeTokenDriver;

// Criterion 38: sessions and devices over MCP.

beforeEach(fn () => Notification::fake());

/**
 * A single page of a list as a tool answers it.
 *
 * @param  list<array<string, mixed>>  $data
 * @return array<string, mixed>
 */
function onePage(array $data): array
{
    return ['data' => $data, 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => count($data)]];
}

it('offers the session and device tools', function (): void {
    expect(GuardianServer::TOOLS)->toContain(
        ListSessionsTool::class, RevokeSessionTool::class, RevokeOtherSessionsTool::class,
        ListUserSessionsTool::class, RevokeUserSessionsTool::class, ListDevicesTool::class, ForgetDeviceTool::class,
    );
});

it('lists your sessions like the HTTP API', function (): void {
    $ada = user();
    sessionRecord($ada);
    $this->actingAs($ada);

    mcpTool(ListSessionsTool::class)->assertOk()->assertStructuredContent(onePage($this->getJson(route('guardian.sessions.index'))->json('data')));
    mcpTool(ListUserSessionsTool::class, ['user' => (string) $ada->getKey()])->assertOk()->assertStructuredContent(onePage($this->getJson(route('guardian.users.sessions.index', $ada->getKey()))->json('data')));
});

it('revokes a session, the others and another user\'s', function (): void {
    $ada = user();
    $one = sessionRecord($ada);
    sessionRecord($ada);
    $bob = user();
    sessionRecord($bob);
    $this->actingAs($ada);

    mcpTool(RevokeSessionTool::class, ['session' => $one->id])->assertOk()->assertSee('Session revoked.');
    expect($one->fresh()?->revoked_at)->not->toBeNull();

    // Over MCP there is no current session: every other one goes.
    mcpTool(RevokeOtherSessionsTool::class)->assertOk()->assertStructuredContent(['revoked' => 1, 'tokens_revoked' => null]);
    mcpTool(RevokeUserSessionsTool::class, ['user' => (string) $bob->getKey()])->assertOk()->assertStructuredContent(['revoked' => 1]);

    expect(SessionModel::query()->whereNull('revoked_at')->count())->toBe(0);
});

it('revokes your tokens through the driver with tokens: true, and errors without one', function (): void {
    $ada = user();
    $this->actingAs($ada);

    mcpTool(RevokeOtherSessionsTool::class, ['tokens' => true])->assertHasErrors(['No API token driver is configured.']);

    $driver = new FakeTokenDriver;
    app()->instance(TokenDriver::class, $driver);
    $driver->issue($ada, 'CLI', ['*'], null);

    mcpTool(RevokeOtherSessionsTool::class, ['tokens' => true])->assertOk()->assertStructuredContent(['revoked' => 0, 'tokens_revoked' => 1]);
});

it('lists and forgets your devices like the HTTP API', function (): void {
    $ada = user();
    $device = DeviceModel::query()->create(['user_id' => $ada->getKey(), 'fingerprint_hash' => 'x', 'name' => 'Laptop', 'first_seen_at' => now(), 'last_seen_at' => now()]);
    sessionRecord($ada, ['device_id' => $device->id]);
    $this->actingAs($ada);

    mcpTool(ListDevicesTool::class)->assertOk()->assertStructuredContent(onePage($this->getJson(route('guardian.devices.index'))->json('data')));
    mcpTool(ForgetDeviceTool::class, ['device' => $device->id])->assertOk()->assertSee('Device forgotten.');

    expect($device->fresh())->toBeNull()
        ->and(SessionModel::query()->sole()->revoked_at)->not->toBeNull();
});
