---
key: testing/arch-tests
action: amend
kind: architectural
package: refactor-circus/standards
from_spec: 2026-10-29-2038-guardian-package
status: queued
evidence:
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/build.md Review round 1 fixes: 'RosterBoundaryTest.php now also denies a Roster domain's Concerns and Resources and every Roster\\Support class except ... A dataset pins which references are denied and which are allowed.'"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/round-1/gate.md criterion 9 proven by tests/ArchTest.php and tests/Feature/RosterBoundaryTest.php"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/decisions.md D-041"
  - "post-mortem root cause spec_gap (criterion 9 boundary test missed three classes)"
---

## Rationale

arch-tests says a new architectural standard is not done until it has a rule here, but the list had no rule for package boundaries. Guardian's first boundary test did not deny Concerns, Resources or Support, so three violations went unnoticed for six task groups. The fix was a dataset that pins every denied and allowed reference. This amendment adds that rule to the list, so the boundary in the dependencies proposal is enforced the same way in every package. It also says the action-event rule covers read actions, matching what Arch::actionEventGaps() actually checks (D-041).

## Proposed text

# Arch Tests

Architectural rules are executable: every package has a `tests/ArchTest.php` that enforces them, instead of leaving them to review.

## Baseline

Every package runs the PHP and security presets, the shared suite preset, and the global bans:

```php
arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->{suitePreset}();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('{Vendor}\{Package}')
    ->toUseStrictTypes();
```

- Every PHP file declares `strict_types=1`. `env()` is read only in config; code reads `config()` ([[configuration]]).
- An exception to a preset is scoped to the one class that needs it (`->ignoring(Foo::class)`), with a comment saying why. Never use a blanket opt-out.
- The shared preset is registered from `tests/Pest.php`, never from an autoloaded file, because Composer may load it before Pest's functions exist.

## Package rules

Each package adds the rules that enforce the standards it follows. Each rule collects violations and expects the list to be empty, so a failure names the offending class:

- Models extend Eloquent's `Model` and end in `Model` ([[models]], [[naming]]).
- Actions take Data objects, not arrays, and every Data input declares its rules ([[data-objects]]).
- Every action, read actions included, dispatches its starting and finished event pair and writes only inside a transaction ([[actions-events]]).
- Every model fires an event for every lifecycle hook ([[lifecycle-events]]).
- Tables are named through the package's table-name map, never as literals in `src` or migrations ([[migrations]]).
- Every model has a policy in the config ([[authorization]]).
- Requests validate with the Data rules and never call `Data::from()`. Every request declares its own `persist()`, and controllers make no database calls ([[request-persist]]).
- A package that builds on another suite package scans every reference to that package against the boundary's allow and deny lists. A dataset pins each denied namespace (`Concerns`, `Resources`, `Http`, `Mcp`, UI, non-public `Support`) and each allowed one ([[dependencies]]).

```php
arch('every action dispatches a starting and a finished event')
    ->expect(fn (): array => actionEventGaps($actionFiles))
    ->toBeEmpty();

it('names no denied {Peer} class', function (string $reference): void {
    expect(boundaryViolations('src', '{Vendor}\\{Peer}'))->not->toContain($reference);
})->with('denied');
```

A new architectural standard is not done until it has a rule here.

## Reference implementation (Refactor Circus)

`RefactorCircus\Standards\Pest\ArchPreset::register()` (in `tests/Pest.php`) adds `arch()->preset()->refactorCircus()`: strict types and no `env()` across the package's `RefactorCircus\` namespaces. `RefactorCircus\Keystone\Testing\Arch` supplies the violation finders: `domainClasses()`, `dataWithoutRules()`, `actionEventGaps()`, `writesOutsideTransactions()`, `modelEventGaps()`, `modelsWithoutPolicies()` and `literalTableNames()`. Guardian's `tests/Feature/RosterBoundaryTest.php` is the reference for a boundary rule.

