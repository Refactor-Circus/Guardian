<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Support;

use RefactorCircus\Keystone\Authorization\Abilities;

/**
 * Guardian's own abilities, defined in Keystone's `Abilities` like every
 * package's (criterion 8). `admin` gets what it got in Roster: viewing and
 * assigning roles, and the SSO and SCIM abilities.
 */
final class GuardianAbilities
{
    /**
     * Ability => [description, built-in roles granted it on creation].
     *
     * @var array<string, array{0: string, 1: list<string>}>
     */
    public const array ABILITIES = [
        'guardian.roles.view' => ['View roles, permissions and who holds them.', ['admin']],
        'guardian.roles.manage' => ['Create, edit and delete roles and permissions.', []],
        'guardian.roles.assign' => ['Assign and revoke roles.', ['admin']],
        'guardian.sso.view' => ["View an organization's single sign-on connections.", ['admin']],
        'guardian.sso.manage' => ['Configure single sign-on connections.', ['admin']],
        'guardian.scim.manage' => ['Issue and revoke SCIM provisioning tokens.', ['admin']],
        'guardian.two-factor.manage' => ["Set two-factor policies and reset another user's two-factor.", []],
        'guardian.sessions.manage' => ["List and revoke other users' sessions.", []],
        'guardian.tokens.manage' => ["List and revoke other users' API tokens.", []],
        'guardian.security.view' => ['Read the security event log.', []],
        'guardian.security.manage' => ['Manage IP rules and clear lockouts.', []],
    ];

    /**
     * Define every ability not defined yet: a provider booted twice (tests,
     * a re-registered domain) defines nothing twice.
     */
    public static function define(Abilities $abilities): void
    {
        foreach (self::ABILITIES as $ability => [$description, $grantTo]) {
            if (! $abilities->has($ability)) {
                $abilities->define($ability, $description, $grantTo);
            }
        }
    }
}
