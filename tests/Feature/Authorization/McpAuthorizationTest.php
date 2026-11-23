<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Tools\ListMySecurityEventsTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\ForgetDeviceTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\ListDevicesTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\ListSessionsTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\RevokeOtherSessionsTool;
use RefactorCircus\Guardian\Domains\Session\Mcp\Tools\RevokeSessionTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\ListPasskeysTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\RemovePasskeyTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\RemoveTwoFactorMethodTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\ShowTwoFactorTool;
use RefactorCircus\Guardian\Mcp\GuardianServer;

/**
 * Tools every signed-in user may use on themselves.
 */
const SELF_SERVICE_TOOLS = [ListMySecurityEventsTool::class, ShowTwoFactorTool::class, RemoveTwoFactorMethodTool::class, ListPasskeysTool::class, RemovePasskeyTool::class, ListSessionsTool::class, RevokeSessionTool::class, RevokeOtherSessionsTool::class, ListDevicesTool::class, ForgetDeviceTool::class];

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
            'ShowRoleTool', 'UpdateRoleTool', 'DeleteRoleTool' => fn (): array => ['role' => 'editor'],
            'CreateRoleTool' => fn (): array => ['name' => 'X'],
            'AssignRoleTool' => fn (array $w): array => ['user' => (string) $w['target']->getKey(), 'role' => 'member'],
            'RevokeRoleTool' => fn (array $w): array => ['assignment' => $w['assignment']->id],
            'ListUserAbilitiesTool' => fn (array $w): array => ['user' => (string) $w['target']->getKey()],
            'CreatePermissionTool' => fn (): array => ['name' => 'a.b'],
            'UpdatePermissionTool', 'DeletePermissionTool' => fn (): array => ['permission' => 'guardian.sso.view'],
            'ShowSecurityEventTool' => fn (array $w): array => ['event' => $w['securityEvent']->id],
            'ResetTwoFactorTool' => fn (array $w): array => ['user' => (string) $w['target']->getKey()],
            'RemoveTwoFactorMethodTool' => fn (array $w): array => ['method' => $w['twoFactorMethod']->id, 'code' => '123456'],
            'RemovePasskeyTool' => fn (array $w): array => ['passkey' => $w['passkey']->id, 'code' => '123456'],
            'UpdateTwoFactorPolicyTool', 'DeleteTwoFactorPolicyTool' => fn (array $w): array => ['policy' => $w['twoFactorPolicy']->id],
            'RevokeSessionTool' => fn (array $w): array => ['session' => $w['session']->id],
            'ListUserSessionsTool', 'RevokeUserSessionsTool' => fn (array $w): array => ['user' => (string) $w['target']->getKey()],
            'ForgetDeviceTool' => fn (array $w): array => ['device' => $w['device']->id],
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

    // A self-service tool answers for the caller (another user's record is
    // not found), never Unauthorized.
    in_array($tool, SELF_SERVICE_TOOLS, true)
        ? mcpTool($tool, $arguments)->assertDontSee('Unauthorized.')
        : mcpTool($tool, $arguments)->assertHasErrors(['Unauthorized.']);
})->with(fn (): array => array_keys(toolArguments()));

it('lets a super-admin past authorization on every tool', function (string $tool): void {
    $world = authorizationWorld();
    $admin = user();
    grant($admin, 'super-admin');
    $this->actingAs($admin);

    mcpTool($tool, toolArguments()[$tool]($world))->assertDontSee('Unauthorized.');
})->with(fn (): array => array_keys(toolArguments()));
