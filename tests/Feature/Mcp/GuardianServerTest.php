<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Registrar;
use RefactorCircus\Guardian\Tests\TestCase;

// Criterion 26: the web and local MCP servers follow `guardian.mcp.*`, as in
// Keen: both off as shipped, served at the configured route and handle once
// switched on.

it('serves no MCP server while guardian.mcp is off, as shipped', function (): void {
    $mcp = app(Registrar::class);

    expect(config('guardian.mcp.web.enabled'))->toBeFalse()
        ->and(config('guardian.mcp.local.enabled'))->toBeFalse()
        ->and($mcp->getWebServer('mcp/guardian'))->toBeNull()
        ->and($mcp->getLocalServer('guardian'))->toBeNull();
});

it('serves GuardianServer over HTTP and locally once guardian.mcp is on', function (): void {
    TestCase::$mcpServers = true;

    try {
        $this->refreshApplication();
    } finally {
        TestCase::$mcpServers = false;
    }

    $mcp = app(Registrar::class);

    expect($mcp->getWebServer('mcp/guardian'))->not->toBeNull()
        ->and($mcp->getLocalServer('guardian'))->not->toBeNull();
});
