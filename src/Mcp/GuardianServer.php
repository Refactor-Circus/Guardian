<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Mcp;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\AssignRoleTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\CreateRoleTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\DeleteRoleTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\ListRoleAssignmentsTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\ListRolesTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\ListUserAbilitiesTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\RevokeRoleTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\ShowRoleTool;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\UpdateRoleTool;
use RefactorCircus\Guardian\Domains\Scim\Mcp\Tools\CreateScimTokenTool;
use RefactorCircus\Guardian\Domains\Scim\Mcp\Tools\ListScimTokensTool;
use RefactorCircus\Guardian\Domains\Scim\Mcp\Tools\RevokeScimTokenTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\CreateSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\DeleteSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ListSsoConnectionsTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ListSsoIdentitiesTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\ShowSsoConnectionTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\UnlinkSsoIdentityTool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Tools\UpdateSsoConnectionTool;
use RefactorCircus\Guardian\Support\Integration;
use RefactorCircus\Keystone\Mcp\Server;

#[Name('Guardian')]
#[Version('1.0.0')]
#[Instructions(
    'Manage roles, permissions and who holds them, and the single sign-on connections, linked sign-in identities '.
    'and SCIM provisioning tokens of Roster organizations. Every call acts as the signed-in user and needs the '.
    'matching guardian.* permission (guardian.roles.view, guardian.roles.manage, guardian.roles.assign, '.
    'guardian.sso.view, guardian.sso.manage, guardian.scim.manage) in the scope it is about.',
)]
final class GuardianServer extends Server
{
    /**
     * Every tool the server offers. The SSO and SCIM tools keep the names,
     * descriptions and output schemas they had in Roster.
     *
     * @var array<int, class-string<Tool>>
     */
    public const array TOOLS = [
        // Roles
        ListRolesTool::class,
        ShowRoleTool::class,
        CreateRoleTool::class,
        UpdateRoleTool::class,
        DeleteRoleTool::class,
        ListRoleAssignmentsTool::class,
        AssignRoleTool::class,
        RevokeRoleTool::class,
        ListUserAbilitiesTool::class,
        // Single sign-on
        ListSsoConnectionsTool::class,
        ShowSsoConnectionTool::class,
        CreateSsoConnectionTool::class,
        UpdateSsoConnectionTool::class,
        DeleteSsoConnectionTool::class,
        ListSsoIdentitiesTool::class,
        UnlinkSsoIdentityTool::class,
        // SCIM provisioning
        ListScimTokensTool::class,
        CreateScimTokenTool::class,
        RevokeScimTokenTool::class,
    ];

    /**
     * Behind ToolSearch, as Keystone's servers and Roster's offered them.
     *
     * @var array<class-string<ToolSearch>, array<int, class-string<Tool>|Tool>>|array<int, class-string<Tool>|Tool>
     */
    protected array $tools = [
        ToolSearch::class => self::TOOLS,
    ];

    /**
     * The SSO and SCIM tools work on Roster's organizations and users, so
     * without a Roster they can work with the server leaves them out; every
     * other tool is offered (criterion 3).
     */
    public function createContext(): ServerContext
    {
        if (! app(Integration::class)->rosterInstalled()) {
            $this->tools = [ToolSearch::class => array_values(array_filter(
                self::TOOLS,
                fn (string $tool): bool => ! str_starts_with($tool, 'RefactorCircus\\Guardian\\Domains\\Sso\\')
                    && ! str_starts_with($tool, 'RefactorCircus\\Guardian\\Domains\\Scim\\'),
            ))];
        }

        return parent::createContext();
    }
}
