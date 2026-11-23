<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Testing\Arch;

const GUARDIAN_DOMAINS = __DIR__.'/../src/Domains';

arch()->preset()->php();

arch()->preset()->security();

arch()->preset()->refactorCircus();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('RefactorCircus\Guardian')
    ->toUseStrictTypes();

arch('actions are final classes')
    ->expect(fn (): array => array_values(array_filter(
        Arch::domainClasses('RefactorCircus\Guardian', GUARDIAN_DOMAINS, 'Actions'),
        fn (string $action): bool => ! (new ReflectionClass($action))->isFinal(),
    )))
    ->toBeEmpty();

arch('actions take their input as Data objects, not arrays')
    ->expect(fn (): array => array_values(array_filter(
        array_map(function (string $action): ?string {
            // Guardian's actions extend Keystone's Action, whose `execute()`
            // forwards to the action's own `handle()`.
            foreach ((new ReflectionMethod($action, 'handle'))->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && $type->getName() === 'array' && in_array($parameter->getName(), ['data', 'filters', 'input', 'attributes', 'values'], true)) {
                    return $action.'::handle($'.$parameter->getName().')';
                }
            }

            return null;
        }, Arch::domainClasses('RefactorCircus\Guardian', GUARDIAN_DOMAINS, 'Actions')),
    )))
    ->toBeEmpty();

arch('action input validates itself')
    ->expect(fn (): array => Arch::dataWithoutRules(Arch::domainClasses('RefactorCircus\Guardian', GUARDIAN_DOMAINS, 'Data')))
    ->toBeEmpty();

arch('every action is reachable from the HTTP API and MCP')
    ->expect(fn (): array => parityGaps())
    ->toBeEmpty();

arch('every action dispatches a starting and a finished event')
    ->expect(fn (): array => Arch::actionEventGaps(GUARDIAN_DOMAINS.'/*/Actions/*.php'))
    ->toBeEmpty();

arch('actions write inside a transaction')
    ->expect(fn (): array => Arch::writesOutsideTransactions(GUARDIAN_DOMAINS.'/*/Actions/*.php'))
    ->toBeEmpty();

arch('every model fires an event for every lifecycle hook')
    ->expect(fn (): array => Arch::modelEventGaps(Arch::domainClasses('RefactorCircus\\Guardian', GUARDIAN_DOMAINS, 'Models')))
    ->toBeEmpty();

arch('requests validate once, with the Data rules, and build from what they validated')
    ->expect(fn (): array => array_values(array_filter(
        [...(array) glob(GUARDIAN_DOMAINS.'/*/Http/Requests/*.php'), ...(array) glob(GUARDIAN_DOMAINS.'/*/Mcp/Requests/*.php')],
        fn (string $path): bool => str_contains((string) file_get_contents($path), 'Data::from('),
    )))
    ->toBeEmpty();

arch('every model has a policy in guardian.policies')
    ->expect(fn (): array => Arch::modelsWithoutPolicies(
        Arch::domainClasses('RefactorCircus\\Guardian', GUARDIAN_DOMAINS, 'Models'),
        (require __DIR__.'/../config/guardian.php')['policies'] ?? [],
    ))
    ->toBeEmpty();

arch('tables are named through Tables, never as literals')
    ->expect(fn (): array => Arch::literalTableNames(
        [...array_values(array_filter(
            phpFiles(__DIR__.'/../src'),
            fn (string $path): bool => ! str_ends_with($path, 'Support/Tables.php'),
        )), ...phpFiles(__DIR__.'/../database/migrations')],
        array_values(Tables::DEFAULTS),
    ))
    ->toBeEmpty();

// The existing Roster reference is kept, not widened (criterion 4, D-003):
// only SSO, SCIM, the upgrade and the shared request and policy bases (for
// the organization scope SSO and SCIM supply) name Roster's classes.
// Support\Integration names them as strings, for class_exists().
arch('only SSO, SCIM, the upgrade and the shared bases name Roster\'s classes')
    ->expect('RefactorCircus\Guardian')
    ->not->toUse('RefactorCircus\Roster')
    ->ignoring([
        'RefactorCircus\Guardian\Domains\Sso',
        'RefactorCircus\Guardian\Domains\Scim',
        'RefactorCircus\Guardian\Upgrade',
        'RefactorCircus\Guardian\Console\Commands\UpgradeFromRosterCommand',
        'RefactorCircus\Guardian\Policies\Policy',
        'RefactorCircus\Guardian\Http\Requests\Request',
        'RefactorCircus\Guardian\Mcp\Requests\Request',
        'RefactorCircus\Guardian\Tests',
    ]);

// Every new domain works with no Roster installed (criterion 4). The rule
// covers each domain as it appears.
arch('the new domains use nothing in Roster')
    ->expect([
        'RefactorCircus\Guardian\Domains\Role',
        'RefactorCircus\Guardian\Domains\Permission',
        'RefactorCircus\Guardian\Domains\TwoFactor',
        'RefactorCircus\Guardian\Domains\Session',
        'RefactorCircus\Guardian\Domains\Password',
        'RefactorCircus\Guardian\Domains\Token',
        'RefactorCircus\Guardian\Domains\LoginProtection',
        'RefactorCircus\Guardian\Domains\SecurityEvent',
    ])
    ->not->toUse('RefactorCircus\Roster');

// Passkeys are optional (criterion 30, D-010): only the passkey ceremony
// names web-auth/webauthn-lib, so nothing else breaks without it.
arch('only Domains\\TwoFactor\\Passkeys uses WebAuthn')
    ->expect('RefactorCircus\Guardian')
    ->not->toUse('Webauthn')
    ->ignoring('RefactorCircus\Guardian\Domains\TwoFactor\Passkeys');

// Token drivers are optional (criterion 45, D-017): only each adapter names
// its package, so Guardian boots without Sanctum or Passport.
arch('only the Sanctum adapter uses Laravel\\Sanctum')
    ->expect('RefactorCircus\Guardian')
    ->not->toUse('Laravel\Sanctum')
    ->ignoring(['RefactorCircus\Guardian\Domains\Token\Drivers\SanctumTokenDriver', 'RefactorCircus\Guardian\Tests']);

arch('only the Passport adapter uses Laravel\\Passport')
    ->expect('RefactorCircus\Guardian')
    ->not->toUse('Laravel\Passport')
    ->ignoring(['RefactorCircus\Guardian\Domains\Token\Drivers\PassportTokenDriver', 'RefactorCircus\Guardian\Tests']);

// v2 has no UI (criterion 2): nothing in Guardian names Atrium.
arch('nothing uses Atrium')
    ->expect('RefactorCircus\Guardian')
    ->not->toUse('RefactorCircus\Atrium');

// Without Roster, only SSO and SCIM are skipped (criterion 3): their
// providers, Roster-backed migrations and the upgrade command register only
// after the rosterInstalled() check in the same body.
arch('only the SSO and SCIM layers wait for Roster')
    ->expect(function (): array {
        $gated = [
            'src/Domains/DomainServiceProvider.php' => ['SsoServiceProvider::class', 'ScimServiceProvider::class'],
            'src/GuardianServiceProvider.php' => ['UpgradeFromRosterCommand::class', "migrations/roster'"],
        ];
        $early = [];

        foreach ($gated as $file => $calls) {
            $source = (string) file_get_contents(__DIR__.'/../'.$file);

            // Each method (or closure) on its own: a gated call must come
            // after the rosterInstalled() check in the same body.
            foreach (preg_split('/\bfunction\s+/', $source) ?: [] as $body) {
                $gate = strpos($body, 'rosterInstalled()');

                foreach ($calls as $call) {
                    $at = strpos($body, $call);

                    if ($at !== false && ($gate === false || $at < $gate)) {
                        $early[] = $file.' '.strtok($body, '(').': '.$call;
                    }
                }
            }
        }

        return $early;
    })
    ->toBeEmpty();
