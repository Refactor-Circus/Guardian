<?php

declare(strict_types=1);

/*
 * Criterion 27: the README and the Boost skill cover install, the vendors,
 * config, permissions, Atrium placement, the API, MCP and the upgrade from
 * Roster.
 */

it('covers what an installer needs', function (string $file): void {
    $contents = (string) file_get_contents(dirname(__DIR__, 2).'/'.$file);

    foreach (['guardian:upgrade-from-roster', 'atrium.screens', 'guardian.sso.manage', 'roster:sync-permissions', 'GUARDIAN_FORCE_ROLLBACK'] as $needle) {
        expect($contents)->toContain($needle);
    }

    foreach (['Upgrading from Roster', 'Permissions', 'MCP'] as $heading) {
        expect(preg_match('/^#+ '.preg_quote($heading, '/').'$/m', $contents))->toBe(1, "{$file} has no \"{$heading}\" heading");
    }
})->with(['README.md', 'resources/boost/skills/guardian/SKILL.md']);

it('says Guardian is inactive without Roster, keeps the roster/sso prefix, and that enforcement fails open in between', function (string $file): void {
    $contents = (string) file_get_contents(dirname(__DIR__, 2).'/'.$file);

    expect($contents)->toContain('Inactive: install refactor-circus/roster')
        ->and($contents)->toContain('roster/sso')
        ->and($contents)->toContain('socialiteproviders/saml2')
        ->and($contents)->toContain('SSO enforcement fails open until Guardian is installed after Roster\'s removal');
})->with(['README.md', 'resources/boost/skills/guardian/SKILL.md']);

it('documents that OIDC needs a verified email, a change from Roster (D-039)', function (string $file): void {
    $contents = (string) file_get_contents(dirname(__DIR__, 2).'/'.$file);

    expect($contents)->toContain('email_verified')
        ->and($contents)->toContain('Changed from Roster');
})->with(['README.md', 'resources/boost/skills/guardian/SKILL.md', 'CHANGELOG.md']);
