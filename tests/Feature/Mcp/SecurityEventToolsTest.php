<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/SecurityEvent/helpers.php';

use Laravel\Mcp\Server\Tool;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Tools\ListMySecurityEventsTool;
use RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Tools\ListSecurityEventsTool;
use RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Tools\ShowSecurityEventTool;
use RefactorCircus\Guardian\Mcp\GuardianServer;

// Criterion 25: the security log over MCP.

it('offers the security log tools, each with an output schema', function (string $tool): void {
    /** @var Tool $instance */
    $instance = app($tool);

    expect(GuardianServer::TOOLS)->toContain($tool)
        ->and($instance->toArray()['outputSchema'] ?? [])->not->toBeEmpty();
})->with([ListSecurityEventsTool::class, ShowSecurityEventTool::class, ListMySecurityEventsTool::class]);

it('reads the log over MCP with the HTTP API\'s payloads', function (): void {
    $ada = user();
    $event = recordSecurityEvent(SecurityEventType::DeviceNew, $ada);
    recordSecurityEvent(SecurityEventType::Logout, user());

    mcpTool(ShowSecurityEventTool::class, ['event' => $event->id])
        ->assertOk()
        ->assertStructuredContent(test()->getJson(route('guardian.security-events.show', $event->id))->json());

    mcpTool(ListSecurityEventsTool::class, ['type' => 'device.new'])->assertOk()->assertSee($event->id);

    $this->actingAs($ada);

    mcpTool(ListMySecurityEventsTool::class)->assertOk()->assertSee($event->id)->assertSee('device.new')->assertDontSee('logout');
});
