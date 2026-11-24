# Guardian

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `refactor-circus/guardian`.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Role in the suite

- Guardian v2 is the security package of the Refactor Circus suite, a JSON API and MCP tools with no PHP UI (its only front end is its Atrium module, JavaScript only): roles and permissions (moved from Roster), the security log, two-factor (TOTP, email/SMS codes, passkeys, recovery codes, policies), sessions and devices, the password policy, driver-based API tokens, login protection and IP rules, plus SSO and SCIM (moved from Roster in v0.1). It requires only refactor-circus/keystone; Roster, Keen, Sanctum, Passport, WebAuthn and Scramble are dev dependencies and suggestions (`tests/Feature/ComposerTest.php`, `tests/Feature/PackageWiringTest.php`).
- Guardian is Keystone's role store: with `guardian.authorization.bind` on it binds Keystone's `Permissions`/`Roles` to `GuardianPermissions`/`GuardianRoles` (scoped) and registers a `Gate::before` that never answers false. Its eleven abilities live in `Domains\Role\Support\GuardianAbilities`. Requests and tools check through `Support\Authorization`, which asks the policy registered for the ability's resource, and the policies ask Keystone's bound `Permissions`, so HTTP and MCP make the same check. `guardian.authorization.enabled` off leaves the middleware as the only check.
- Only SSO and SCIM need Roster: without it Guardian boots and works, `php artisan about` shows SSO and SCIM as "Inactive: install refactor-circus/roster" (or "Inactive: needs a newer refactor-circus/roster"), and their providers, routes, tools, migrations (`database/migrations/roster`) and the upgrade command are skipped (`tests/Modes/Standalone`). `Support\Integration::rosterInstalled()` gates only those. Only `Domains\Sso`, `Domains\Scim`, `Upgrade`, `Support\Integration` and the base `Http`/`Mcp` requests may name `RefactorCircus\Roster\*`, from the allow-list in `tests/Fixtures/roster-references.php` (D-003); every other domain is Roster-free (`tests/ArchTest.php`).
- Guardian reaches Roster only through its public seams (`MembershipSources`, `Contracts\SignInRequirement`, `Data\ExternalSignIn`, `Roster::requireSignIn()`, `Roster::provisionedStatus()`) and its public domain API. Never Roster's `@internal` members or anything under `Roster\Http`, `Roster\Mcp`, `Roster\Support\Concerns`, `Roster\Support\Policies` or a Roster domain's `Http` / `Mcp`; Guardian has its own request, MCP request and policy bases on Keystone's. `tests/ArchTest.php` enforces it.
- No PHP UI: the Atrium plugin, views and the `ui` / `atrium` config sections are gone. Guardian ships its Atrium front-end module, JavaScript only: Vue sources in `atrium/` (export-ignored; built with `@refactor-circus/atrium/vite`, the SDK a dev dependency pinned by commit), built into the committed `dist/atrium/` (`atrium.js`, `chunks/`, `atrium.css`, `integration.json`, `build.json`) and declared in composer.json's `extra.atrium`, which Atrium reads to inject the script into its page. Guardian's integration (feature, nav, screens and their config-derived options, the Access/Security/SSO/SCIM tabs and the SSO identities section on Roster's screens) is declared in the static manifest `atrium/src/integration.json` (Atrium's `MODULES.md` §8; the build validates it and writes `dist/atrium/integration.json`): edit it there and rebuild; `atrium/test/integration.test.ts` checks it against the module's `screens` and `extensions`. Rebuild it after any change under `atrium/src` or `client/src`: `cd atrium && pnpm install && pnpm typecheck && pnpm test && pnpm build`, and commit `dist/atrium`; `tests/Feature/AtriumModuleTest.php` fails, without Node, on a stale build, and the CI `ui` job rebuilds and diffs. The module uses `client/`'s schema and `createGuardianClient`. Its components compose Nuxt UI and the SDK's `Atrium*` components, never raw interactive HTML (`atrium/test/nuxt-ui-guard.test.ts` enforces it; D-052). Atrium v2, or any front end, uses the JSON API through the committed `openapi.json` and `client/` (`@refactor-circus/guardian-client`, `createGuardianClient`). Regenerate both with `composer client:generate` whenever a route, request or resource changes; `tests/Feature/OpenApiTest.php` fails, without Node, when either is stale. Scramble is configured only in `workbench/app/Providers/WorkbenchServiceProvider.php`; controllers carry `@response` docblocks naming what each request's `persist()` answers. No PHP in `src/`, `config/`, `routes` or `workbench/` names Atrium (`tests/Feature/RemovedSurfacesTest.php`) or Scramble (arch).
- Host wiring is middleware aliases (`guardian.two-factor`, `guardian.session`, `guardian.password-fresh`, `guardian.login-throttle`, `guardian.ip`) and replaceable contracts: `Domains\TwoFactor\Contracts\TwoFactorRequirement`, `Domains\TwoFactor\Contracts\OneTimeCodeChannel`, `Domains\Token\Contracts\TokenDriver` (`guardian.tokens.driver`: null, `sanctum`, `passport` or a class). Only `Domains\TwoFactor\Passkeys` uses WebAuthn, and only each token driver uses its package (arch).
- Code lives in `src/Domains/{Role, Permission, SecurityEvent, TwoFactor, Session, Password, Token, LoginProtection, Sso, Scim}`. Actions extend `RefactorCircus\Keystone\Actions\Action` with a starting and a finished event each and write inside `DB::transaction()`; input is a `final readonly` `{Action}Data` with its own `rules()`; requests extend Keystone's request bases, validate with the Data rules and build with `fromValidated()`. Models use Keystone's `DispatchesModelEvents`, name their tables through `Support\Tables` (`guardian.tables`, never a literal `guardian_*` name) and have a policy in `guardian.policies`. Every action is reachable over HTTP and MCP but those in `PARITY_EXCEPTIONS` (`tests/Pest.php`). `tests/ArchTest.php` checks all of it with Keystone's `Testing\Arch` and `Testing\Parity`.
- Secrets never leave: passwords, codes, TOTP secrets, token values and raw identifiers are never stored in clear, logged, put in an event or audited; the security log (`Domains\SecurityEvent`, `RecordSecurityEventAction` its only writer) stores identifiers as HMACs.
- Keen is a dev dependency only: Guardian's events are audited as package `guardian` through Keystone's `AuditTrail` / `AuditHooks` when Keen is installed and `guardian.audit.enabled` is on.
- Upgrading from Roster: `Upgrade\RosterRoleAdoption` (the `2026_11_24_000003` migration and `php artisan guardian:adopt-roster-roles`) copies Roster v0.1's role tables once and never drops them. `Upgrade\RosterUpgrade` (the `2026_11_01_000000` migration and `php artisan guardian:upgrade-from-roster`) renames Roster's `roster_*` SSO/SCIM tables and merges the permission grants, only once `Upgrade\Handover::complete()` (Roster no longer ships SSO). Until then Guardian loads no SSO web or SCIM server routes and no morph aliases. Guardian never reads `roster.*` config; the upgrade reports leftovers.

## Quick Commands

- Full validation: `composer test`
- Formatting check: `composer lint:check`
- Static analysis: `composer analyse`
- Pest tests: `composer test:unit`
- Workbench build: `composer build`
- Workbench server: `composer serve`
- OpenAPI document and TypeScript client: `composer client:generate`

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
