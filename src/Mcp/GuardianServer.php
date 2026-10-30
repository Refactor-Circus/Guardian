<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Mcp;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tool;
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
     * @var array<int, class-string<Tool>>
     */
    public const array TOOLS = [];

    /**
     * @var array<int, class-string<Tool>|Tool>
     */
    protected array $tools = self::TOOLS;

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
