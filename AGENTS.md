# Guardian

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `refactor-circus/guardian`.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Role in the suite

- Guardian is the auth and security package of the Refactor Circus suite, starting with SSO and SCIM moved out of Roster with their behaviour unchanged (MFA, session policies and IP rules are planned: `docs/agent/product/roadmap.md`). It requires only refactor-circus/keystone; refactor-circus/roster and refactor-circus/atrium are suggested, and dev dependencies only (`tests/Feature/PackageWiringTest.php` holds `composer.json` to that).
- Without Roster, Guardian is inert: it boots, `php artisan about` says "Inactive: install refactor-circus/roster" (or "Inactive: needs a newer refactor-circus/roster" when Roster lacks the seams), and nothing else registers. `Support\Integration` (`rosterInstalled()`, `atriumInstalled()`) is the one gate; every class that names `RefactorCircus\Roster\*` lives under `src/Domains`, `src/Atrium`, `src/Mcp`, `src/Upgrade`, `src/Policies`, `src/Http`, `src/Console` or `database/`.
- Guardian reaches Roster only through its public seams (`Resources`, `PermissionCatalogue`, `MembershipSources`, `Contracts\SignInRequirement`, `Data\ExternalSignIn`, `Roster::requireSignIn()`, `Roster::provisionedStatus()`) and its public domain API (models, actions and their Data, `Roster`, `Authorizer`, `Support\Users`, `Support\Statuses`, `Support\UserKey`, `Support\Tables`). Never Roster's `@internal` members (`rosterResource()`, `rosterPermission()`) or anything under `Roster\Atrium`, `Roster\Http`, `Roster\Mcp`, `Roster\Support\Concerns`, `Roster\Support\Policies` or a Roster domain's `Http` / `Mcp`; Guardian has its own request, MCP request and policy bases on Keystone's. `tests/ArchTest.php` enforces it.
- Guardian reaches Atrium only through Atrium's screen extensions registry, from `src/Atrium` (`GuardianPlugin`, `Screens`), and only when both Roster and Atrium are installed. Where its tabs and sections go is config: `guardian.atrium.screens` places `guardian.sso` / `guardian.scim` on `roster.organization` and `guardian.sso-identities` on `roster.user`; no other code names a Roster screen. A malformed entry throws in `local` / `testing` and is reported once and skipped elsewhere.
- Code lives in `src/Domains/Sso` and `src/Domains/Scim`. Actions extend `RefactorCircus\Keystone\Actions\Action` with a starting and a finished event each and write inside `DB::transaction()`; input is a `final readonly` `{Action}Data` with its own `rules()`; requests extend Keystone's request bases, validate with the Data rules and build with `fromValidated()`. Models use Keystone's `DispatchesModelEvents`, name their tables through `Support\Tables` (`guardian.tables`, never a literal `guardian_*` name) and have a policy in `guardian.policies`. `tests/ArchTest.php` checks all of it with Keystone's `Testing\Arch` and `Testing\Parity`.
- Permissions are `guardian.sso.view`, `guardian.sso.manage` and `guardian.scim.manage`, registered on Roster's `PermissionCatalogue`; resources `guardian.sso` and `guardian.scim` on `Resources`. Policies answer through Roster's `Authorizer::permits()`, so HTTP, MCP and Atrium make the same check.
- Keen is a dev dependency only: Guardian's events are audited as package `guardian` through Keystone's `AuditTrail` / `AuditHooks` when Keen is installed and `guardian.audit.enabled` is on.
- Upgrading from Roster: `Upgrade\RosterUpgrade` (the `2026_11_01_000000` migration and `php artisan guardian:upgrade-from-roster`) renames Roster's `roster_*` SSO/SCIM tables and merges the permission grants, only once `Upgrade\Handover::complete()` (Roster no longer ships SSO). Until then Guardian loads no SSO web or SCIM server routes and no morph aliases, so it never shadows Roster's identical URIs. Guardian never reads `roster.sso.*` / `roster.scim.*` config; the upgrade reports leftovers.

## Quick Commands

- Full validation: `composer test`
- Formatting check: `composer lint:check`
- Static analysis: `composer analyse`
- Pest tests: `composer test:unit`
- Workbench build: `composer build`
- Workbench server: `composer serve`

## Local Skills

- `package-scaffold`: use when adding package capabilities or wiring them through the service provider, including commands, migrations, routes, config, views, translations, assets, middleware, publish tags, workbench files, and console-only behavior.
- `package-testing`: use when adding or changing package tests with Pest 4/5 and Orchestra Testbench.
- `package-release`: use when preparing changelog, release notes, tags, or GitHub release workflow changes.
- `package-compatibility`: use when reviewing code, dependencies, or CI against the PHP and Laravel support matrix.

<!-- ring-master:start -->
## Ring Master

- Specs, product docs and local standards live in `docs/agent/`. Shared standards come from `vendor/` through `docs/agent/standards.index.yml`; read the matching standards before changing code.
- Workflow: `/ring-master-shape-spec` → `/ring-master-write-spec` → `/ring-master-create-tasks` → `/ring-master-implement` → `/ring-master-verify-spec`.
- Done means `composer test` passes. When work must break a standard, add a row to `docs/agent/standards-feedback.md`.
- Keep this checkout on `main`: other packages' jobs build against it. Never switch branches, commit or stash here; do any change that is not a factory job (which makes its own) in a worktree beside the package: `git worktree add ../<Package>--<slug> -b <branch> origin/main`, removed once it is merged.
<!-- ring-master:end -->
