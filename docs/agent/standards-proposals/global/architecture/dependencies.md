---
key: architecture/dependencies
action: amend
kind: architectural
package: refactor-circus/standards
from_spec: 2026-10-29-2038-guardian-package
status: queued
evidence:
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/build.md Review round 1 fixes (architecture/dependencies major, criterion 9: ManagesMemberships, CaseInsensitive, UserSummaryResource; RosterBoundaryTest dataset)"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/standards-feedback.md rows architecture/dependencies (src/Upgrade names Roster tables as strings; JoinsOrganizations writes Roster models directly)"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/decisions.md D-024, D-036"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/round-1/review-standards.md ('the Roster-boundary classes were replaced or recorded')"
  - "post-mortem root cause spec_gap: 'Criterion 9 did not list all denied Roster namespaces (Concerns, Resources, Support)'"
---

## Rationale

The post-mortem's only major finding came from here. Criterion 9 named the denied Roster layers (Atrium/HTTP/MCP and internal bases) but not Concerns, Resources or non-public Support, and the standard only covers boundaries between domains, not packages. Guardian used ManagesMemberships, Support\CaseInsensitive and Roster's UserSummaryResource for six task groups, until standards review round 1 caught them, and RosterBoundaryTest had to be widened with a pinned dataset. The fix also left two architecture/dependencies feedback rows: a direct model write where Roster has no action that joins with a source (JoinsOrganizations), and Upgrade code naming Roster's tables and keys as strings. This amendment turns all of that into a rule specs can copy.

## Proposed text

# Dependencies

A package requires only what its core needs. Shared runtime comes from the base package, other domains and packages are reached only through their public seams and public domain API, and every optional integration is guarded so the package installs and runs without it.

## Between domains

- Change another domain's data only by calling its action (`app({Verb}{Entity}Action::class)->execute(...)`), never by writing its models directly. The action owns the transaction and the event pair; see [[actions-events]].
- React to another domain by listening to its action or lifecycle events, not by reaching into its services' internals.
- Depend on another domain's `Contracts/`, `Data/` and models (for reading). Keep its `Support/`, `Concerns/` and private helpers out of reach.
- Framework-free core domains never import an integration domain. Integrations depend on the core, not the reverse.
- Package-wide code at the top level ([[domain-modules]]) may be used by every domain.

## Between packages

When a package builds on another suite package, the same rules apply, with the boundary written down as an explicit allowlist:

- **Allowed:** the other package's documented seams (contracts, registries, `Data`), its models, its actions with their `Data`, its entry-point class, and the `Support` classes it names as public.
- **Denied:** members marked `@internal`; any domain's `Concerns/`, `Resources/`, `Http/` and `Mcp/`; the package's top-level `Http`, `Mcp` and UI (`Atrium`) layers; and every `Support` class not on the allowlist. A resource belongs to the HTTP layer even though it sits beside the model.
- The spec that sets the boundary lists both sides by namespace. An arch test enforces it with a dataset that pins each denied and allowed reference, so a missed namespace fails the test instead of review.
- If no public action does the write you need, ask the owning package for one. Until it lands, copy the rule into your own `Concerns/` and record the direct write in `standards-feedback.md`, with a test that pins the behaviour.
- An upgrade path that moves the other package's data (`src/Upgrade`) is the one place that may name its tables, config keys and classes as strings. Keep all of that in one namespace.

```php
dataset('denied', [
    'Roster\\Domains\\Organization\\Concerns\\ManagesMemberships',
    'Roster\\Domains\\User\\Resources\\UserSummaryResource',
    'Roster\\Support\\CaseInsensitive',
]);

dataset('allowed', [
    'Roster\\Support\\Users',
    'Roster\\Domains\\Organization\\Actions\\AddMemberAction',
]);
```

## Composer `require`

- Shared runtime (base actions, request bases, event contracts, provider bases) comes only from the base package, which every package in the suite requires. Don't copy it into a package.
- Add a dependency only when the package's core behavior needs it, and only with approval.
- An optional integration is never in `require`. Keep it in `require-dev`, or leave it unlisted when listing it would be circular (the integration itself requires this package).

## Optional integrations

- Reference an optional package's classes only behind a check that it is installed and enabled: `class_exists()` at the wiring point, or a single `active()` method that also checks its provider is loaded and that the package's config allows it.
- Keep all those references in one integration class or an `Integration` domain, so static analysis ignores exactly one place.

```php
public function active(): bool
{
    return class_exists(OtherServiceProvider::class)
        && $this->app->getProvider(OtherServiceProvider::class) !== null
        && $this->config->get('{package}.other.enabled', true) === true;
}
```

## Feature flags

- Never require consumers to install a feature-flag package (such as Pennant); gating is the host application's concern.
- A package may ship a feature class for hosts that use one, with the flag package as a dev dependency only.
- Surfaces the package itself can switch off are config flags, checked strictly; see [[service-providers]].

## Reference implementation (Refactor Circus)

The base package is `refactor-circus/keystone`: `Actions\Action`, `Data\Data`, the `Contracts\Action*Event` contracts, `Models\Concerns\DispatchesModelEvents` and `Support\{ServiceProvider,PackageServiceProvider}`. `Keystone\Cortex\CortexIntegration::active()` is the guard for Cortex, which cannot be a dev dependency because it requires Keystone; `phpstan.neon.dist` ignores Cortex classes in that one file. Feature-flag management lives in `refactor-circus/pennantplus`, which plugs in like any other package. Guardian's `tests/Feature/RosterBoundaryTest.php` is the reference boundary test.

