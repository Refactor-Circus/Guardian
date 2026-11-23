<?php

declare(strict_types=1);

/*
 * Criterion 27: the README and the Boost skill cover install, the vendors,
 * config, permissions, the API, MCP and the upgrade from
 * Roster.
 */

it('covers what an installer needs', function (string $file): void {
    $contents = (string) file_get_contents(dirname(__DIR__, 2).'/'.$file);

    foreach (['guardian:upgrade-from-roster', 'guardian.sso.manage', 'guardian:sync-permissions', 'GUARDIAN_FORCE_ROLLBACK'] as $needle) {
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

/*
 * Criterion 57: v2's docs describe the security package, how a host wires
 * it, and the move of roles from Roster.
 */

function guardianDoc(string $file): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/'.$file);
}

/**
 * The `[Unreleased]` section of the changelog.
 */
function unreleasedChangelog(): string
{
    $changelog = guardianDoc('CHANGELOG.md');
    $start = (int) strpos($changelog, '## [Unreleased]');
    $end = strpos($changelog, "\n## [", $start + 1);

    return substr($changelog, $start, $end === false ? null : $end - $start);
}

it('describes Guardian as the suite\'s security package, with Atrium v2 as the optional UI', function (): void {
    expect(guardianDoc('README.md'))->toContain('security package', 'JSON API', 'MCP', 'Atrium v2');
});

it('names every middleware alias Guardian registers, in the README and the Boost skill', function (string $file): void {
    $aliases = array_keys(array_filter(app('router')->getMiddleware(), fn (string $alias): bool => str_starts_with($alias, 'guardian.'), ARRAY_FILTER_USE_KEY));

    expect($aliases)->toEqualCanonicalizing(['guardian.two-factor', 'guardian.session', 'guardian.password-fresh', 'guardian.login-throttle', 'guardian.ip']);

    foreach ($aliases as $alias) {
        expect(guardianDoc($file))->toContain('`'.$alias.'`');
    }
})->with(['README.md', 'resources/boost/skills/guardian/SKILL.md']);

it('covers the bindings, drivers, commands, the OpenAPI document and the client', function (): void {
    expect(guardianDoc('README.md'))->toContain(
        'guardian.authorization.bind',
        'TokenDriver',
        'OneTimeCodeChannel',
        'TwoFactorRequirement',
        'web-auth/webauthn-lib',
        'guardian:adopt-roster-roles',
        'guardian:grant-super-admin',
        'guardian:sync-permissions',
        'adopt.scope_types',
        'openapi.json',
        '@refactor-circus/guardian-client',
        'createGuardianClient',
        'composer client:generate',
    );
});

it('has a section for each domain and for host wiring', function (string $heading): void {
    expect(preg_match('/^#+ '.preg_quote($heading, '/').'$/m', guardianDoc('README.md')))->toBe(1, "README.md has no \"{$heading}\" heading");
})->with([
    'Host wiring',
    'Roles and permissions',
    'Security log',
    'Two-factor authentication',
    'Sessions and devices',
    'Passwords',
    'API tokens',
    'Login protection and IP rules',
    'Single sign-on',
    'SCIM',
    'OpenAPI and TypeScript client',
]);

it('names every ability Guardian defines', function (): void {
    foreach (['guardian.roles.view', 'guardian.roles.manage', 'guardian.roles.assign', 'guardian.sso.view', 'guardian.sso.manage', 'guardian.scim.manage', 'guardian.two-factor.manage', 'guardian.sessions.manage', 'guardian.tokens.manage', 'guardian.security.view', 'guardian.security.manage'] as $ability) {
        expect(guardianDoc('README.md'))->toContain('`'.$ability.'`');
    }
});

it('lists SSO and SCIM\'s use of Roster as an existing cross-package reference', function (): void {
    expect(guardianDoc('README.md'))->toContain('cross-package reference');
});

it('mentions neither the Atrium plugin hook nor the removed UI config', function (string $file): void {
    expect(guardianDoc($file))->not->toContain('registerAtriumPlugin')
        ->not->toContain('guardian.ui');
})->with(['README.md', 'CHANGELOG.md', 'resources/boost/skills/guardian/SKILL.md', 'CLAUDE.md', 'AGENTS.md']);

it('has the Breaking, Added and Upgrading to v2.0.0 sections under [Unreleased]', function (): void {
    $unreleased = unreleasedChangelog();

    foreach (['### Breaking', '### Added', '### Upgrading to v2.0.0'] as $heading) {
        expect(preg_match('/^'.preg_quote($heading, '/').'$/m', $unreleased))->toBe(1, "[Unreleased] has no \"{$heading}\"");
    }
});

it('gives the upgrade steps: Atrium, Keystone ^2.0 and adopting Roster\'s roles', function (): void {
    $upgrade = substr(unreleasedChangelog(), (int) strpos(unreleasedChangelog(), '### Upgrading to v2.0.0'));

    expect($upgrade)->toContain('Atrium', 'refactor-circus/keystone', '^2.0', 'Roster v2', 'php artisan migrate', 'guardian:adopt-roster-roles', 'adoption report', 'by hand')
        ->and($upgrade)->toContain('roster_permissions', 'roster_roles', 'roster_permission_role', 'roster_role_assignments')
        ->and(strpos($upgrade, 'php artisan migrate'))->toBeLessThan((int) strpos($upgrade, 'adoption report'))
        ->and(strpos($upgrade, 'adoption report'))->toBeLessThan((int) strpos($upgrade, 'by hand'));
});

it('describes v2 in the Boost skill, CLAUDE.md and AGENTS.md', function (string $file): void {
    expect(guardianDoc($file))->toContain('security package', 'two-factor', 'role store', 'openapi.json');
})->with(['resources/boost/skills/guardian/SKILL.md', 'CLAUDE.md', 'AGENTS.md']);

it('no longer calls two-factor, sessions or IP rules planned', function (string $file): void {
    expect(guardianDoc($file))->not->toContain('are planned');
})->with(['CLAUDE.md', 'AGENTS.md']);
