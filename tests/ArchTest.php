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

arch('every action is reachable from the HTTP API, MCP and a UI')
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
        )), ...(array) glob(__DIR__.'/../database/migrations/*.php')],
        array_values(Tables::DEFAULTS),
    ))
    ->toBeEmpty();

// Without Roster, Guardian must boot (criterion 7): only the layers that
// load behind Integration::rosterInstalled() may name Roster's classes.
// Support\Integration names them as strings, for class_exists().
arch('only the Roster-facing layers name Roster\'s classes')
    ->expect('RefactorCircus\Guardian')
    ->not->toUse('RefactorCircus\Roster')
    ->ignoring([
        'RefactorCircus\Guardian\Domains',
        'RefactorCircus\Guardian\Atrium',
        'RefactorCircus\Guardian\Mcp',
        'RefactorCircus\Guardian\Upgrade',
        'RefactorCircus\Guardian\Policies',
        'RefactorCircus\Guardian\Http',
        'RefactorCircus\Guardian\Console',
        'RefactorCircus\Guardian\Database',
        'RefactorCircus\Guardian\Tests',
    ]);

// Without Atrium, Guardian must boot and serve everything but its screens
// (criterion 30): only src/Atrium, loaded behind atriumInstalled(), may name
// Atrium's classes. Support\Integration names one as a string.
arch('only the Atrium layer names Atrium\'s classes')
    ->expect('RefactorCircus\Guardian')
    ->not->toUse('RefactorCircus\Atrium')
    ->ignoring([
        'RefactorCircus\Guardian\Atrium',
        'RefactorCircus\Guardian\Tests',
    ]);

arch('the package provider reaches the Atrium layer only once Atrium is installed')
    ->expect(function (): array {
        $source = (string) file_get_contents(__DIR__.'/../src/GuardianServiceProvider.php');
        $early = [];

        foreach (preg_split('/\bfunction\s+/', $source) ?: [] as $body) {
            $gate = strpos($body, 'atriumInstalled()');

            foreach (['registerAtriumPlugin(', 'GuardianPlugin::'] as $call) {
                $at = strpos($body, $call);

                if ($at !== false && ($gate === false || $at < $gate)) {
                    $early[] = strtok($body, '(').': '.$call;
                }
            }
        }

        return $early;
    })
    ->toBeEmpty();

arch('the package provider reaches the Roster-facing layers only once Roster is installed')
    ->expect(function (): array {
        $source = (string) file_get_contents(__DIR__.'/../src/GuardianServiceProvider.php');
        $gated = [
            'DomainServiceProvider::class',
            'registerPolicies(',
            'registerMcpServer(',
            'registerCortex(',
            'registerAtriumPlugin(',
            'loadMigrationsFrom(',
            'publishesMigrations(',
        ];
        $early = [];

        // Each method (or closure) on its own: a gated call must come after
        // the rosterInstalled() check in the same body.
        foreach (preg_split('/\bfunction\s+/', $source) ?: [] as $body) {
            $gate = strpos($body, 'rosterInstalled()');

            foreach ($gated as $call) {
                $at = strpos($body, $call);

                if ($at !== false && ($gate === false || $at < $gate)) {
                    $early[] = strtok($body, '(').': '.$call;
                }
            }
        }

        return $early;
    })
    ->toBeEmpty();
