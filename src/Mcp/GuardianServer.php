<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Mcp;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;
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
    'Manage the single sign-on connections, linked sign-in identities and SCIM provisioning tokens of Roster '.
    'organizations. Every call acts as the signed-in user and needs the guardian.sso.view, guardian.sso.manage '.
    'or guardian.scim.manage permission in the organization it is about.',
)]
final class GuardianServer extends Server
{
    /**
     * Every tool the server offers, with the names, descriptions and output
     * schemas they had in Roster.
     *
     * @var array<int, class-string<Tool>>
     */
    public const array TOOLS = [
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
     * Every tool works on Roster's organizations and users, so without a
     * Roster Guardian can work with the server offers none.
     */
    public function createContext(): ServerContext
    {
        if (! app(Integration::class)->rosterInstalled()) {
            $this->tools = [];
        }

        return parent::createContext();
    }
}
