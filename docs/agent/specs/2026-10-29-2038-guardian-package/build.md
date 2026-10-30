# Build: Guardian package

## Group 1

Wire the package into the suite (criteria 1 to 4).

### Files

- `composer.json`: require php `^8.5`, Keen's `laravel/framework` (`^13.35`) and `refactor-circus/keystone: dev-main` only; suggest Roster, Atrium, `laravel/mcp` and the four SSO vendors (vendor wording copied from Roster at `6b1833dae7ed21ba352612a13693d662bc7354cc`, the `auth-extension-seams` head); require-dev Roster, Atrium, Keen, Ringside, Standards (`dev-main`), the four SSO vendors at Roster's require-dev constraints and Keen's dev tools (including `laravel/mcp`); VCS repositories for Keystone, Roster, Atrium, Keen, Ringside and Standards; `conflict` `refactor-circus/atrium: <0.1.0`; Keen's scripts with `local:install` / `local:update`.
- `bin/composer-local.php`, `.gitattributes`, `phpstan.windows.neon`: Keen's, byte for byte.
- `phpunit.xml.dist`: suites `Arch`, `Feature`, `Modes` (the scaffold's `Unit` suite dropped, so `tests/Unit/ExampleTest.php` was removed); `tests/Modes/.gitkeep` keeps the `Modes` directory PHPUnit requires until group 2 adds its modes.
- `.github/workflows/tests.yml`: Keen's, plus a "Ring Master generated files" step (`vendor/bin/ringside sync`, then `git diff --exit-code` on `AGENTS.md`, `.agents` and `docs/agent`) and a `vendor/bin/ringside check` step.
- `tests/Pest.php`: `uses(TestCase::class)->in('Feature')`, `ArchPreset::register()`, an empty `PARITY_EXCEPTIONS`, `phpFiles()` and `parityGaps()` (UI surface: every file under `src/Atrium` plus `Domains/*/Http/Controllers/*WebController.php`).
- `tests/TestCase.php`: Keen's shape (MCP, Atrium and Guardian providers, app key, `testing` database, Laravel, Guardian and Atrium migrations).
- `tests/ArchTest.php`: php, security and refactorCircus presets, the global bans, strict types, and Keen's rules over `src/Domains`: final actions, Data input, `dataWithoutRules`, parity, `actionEventGaps`, `writesOutsideTransactions`, `modelEventGaps`, request-persist, `modelsWithoutPolicies` against `guardian.policies` and `literalTableNames` against `Support\Tables::DEFAULTS`.
- `AGENTS.md` (`CLAUDE.md` links to it): Keen's shape with Guardian's "Role in the suite" section; the generated Ring Master block kept.
- `docs/agent/product/roadmap.md`: SSO and SCIM done; MFA, session policies and IP rules planned.
- `composer.local.json` (gitignored, not committed): path repositories `../Roster--2026-10-29-2038-auth-extension-seams` and `../Atrium--2026-10-29-2037-screen-extensions-registry` (symlink, aliased `dev-main`) ahead of the generated sibling entries; installed with `COMPOSER=composer.local.json composer update`. `composer local:install` was not run.
- `ring-master.yml` already had Keen's commands block; `vendor/bin/ringside sync` was re-run and `vendor/bin/ringside check` passes. `pint.json`, `.editorconfig`, `testbench.yaml`, `.gitignore`, `update-changelog.yml` and `workbench/` already matched Keen's and are unchanged.

### Tests

- `tests/Feature/PackageWiringTest.php` (criteria 1, 2, 4): composer `require` / `suggest` / `require-dev` / `repositories` / `conflict` / `extra.laravel`, scripts and `bin/composer-local.php` equal to Keen's installed copy, the phpunit suites, the CI `ringside check` step, the Ring Master files, `.gitignore` entries, `AGENTS.md` block, `CLAUDE.md` role section and the roadmap's done and planned items.
- `tests/ArchTest.php` (criterion 3): passes on the empty `src/Domains`.
- Ran `tests/Feature/PackageWiringTest.php` and `tests/ArchTest.php` only (42 passed); the full suite is left to the gate.

### Deviations and notes for later groups

- `phpstan.neon.dist` keeps the scaffold's paths (`src`, `config`, `database`, `routes`) instead of Keen's `tests/Fixtures`, which does not exist here yet; PHPStan fails on a missing path.
- The `literalTableNames` rule is skipped while `Support\Tables` does not exist (an empty table list would match every `''` literal). Group 2 adds `Support\Tables` and can drop the `class_exists()` guard.
- "Actions are final" is written as a reflection filter over `Arch::domainClasses()` instead of Keen's `->classes()->toBeFinal()` on `domainNamespaces()`, so it passes on an empty `src/Domains`.
- The Roster SSO vendor wording is pinned in the test as constants with the Roster SHA, not read from the installed Roster, because Roster `remove-sso-and-scim` drops those `suggest` entries.
- `tests/Feature/ExampleTest.php` and the scaffold placeholders (`config/guardian.php` `placeholder`, `routes/guardian.php`, `GuardianCommand`, the placeholder migration and view) are left for group 2, which owns those files.

### Retry

- Every group 1 task was already committed in `aba413c`; the retry changed no code.

## Group 2 (salvaged)

Package shape, config, integration, models and create migrations (criteria 5, 6, 7, 21).

### What I found

No index lock, merge or rebase. Nothing of group 2 was committed. The earlier agent had left uncommitted: the tests (`PackageShapeTest`, `ConfigTest`, `MigrationsTest`, `tests/Modes/Standalone`, `tests/Modes/RenamedTables`, `StandaloneTestCase`, `RenamedTablesTestCase`, the `Pest.php` and `TestCase.php` changes, the two new arch rules), `config/guardian.php`, `lang/en/guardian.php`, `Support\Tables`, `Support\Integration`, `GuardianException`, `RollbackRefusedException`, `GuardianServer`, the domain providers, the facade, staged deletions of the scaffold placeholders, and `database/factories/` in `composer.json` / `composer.local.json` autoload. Missing: the provider (still the scaffold's), the models, lifecycle events, policies, factory, migrations, the request, MCP request and policy bases, the CI job and the standards-feedback row.

### What I committed

- `GuardianServiceProvider` on Keystone's `PackageServiceProvider` (`guardian`, label `Guardian`, `GuardianServer`). `Integration` is a singleton; `DomainServiceProvider` is registered from a `booting()` callback, only when `rosterInstalled()`, so an `Integration` bound after providers register (Testbench's `defineEnvironment()`, an app) is the one asked. `boot()` always defines the `guardian` limiter (Roster's keying, null `per_minute` off), the `about` section (`Active`, `Inactive: needs a newer refactor-circus/roster`, `Inactive: install refactor-circus/roster`, resolved when the command runs), translations and the config/lang publishes; policies, not-found responses, MCP server, Cortex, migrations and `guardian-migrations` sit behind `rosterInstalled()` in `bootWithRoster()`.
- The five models in `Domains/Sso/Models` and `Domains/Scim/Models` (Roster's at `6b1833d`, tables from `Support\Tables`), their 50 lifecycle events, five policies on Guardian's `Policies\Policy` (Roster `Authorizer::permits()`, `guardian.*` permissions), and `database/factories/SsoConnectionFactory`.
- `Http\Requests\Request` and `Mcp\Requests\Request` on Keystone's bases, authorizing `ability()` / `scope()` / `self()` through Roster's `Authorizer::check()`, with the across-organizations list rule (Roster's `AuthorizesWithRoster`, which Guardian may not use, re-done in each base).
- Create migrations `2026_11_01_000001` / `000002`: Roster's columns, unique keys, the `_lookup_index` and `_organization_created_index` indexes, Roster `Tables` / `UserKey` for organizations, teams and user columns, a `hasTable()` guard per create. `down()` throws `RollbackRefusedException` (a `GuardianException`) naming every Guardian table that holds rows, and drops nothing, unless `guardian.migrations.force_rollback === true`. Both migrations check all five tables, so the scim rollback (which runs first) does not drop its tables when the sso one is going to refuse.
- `StandaloneTestCase` clears `ServiceProvider::$publishes` / `$publishGroups` before each boot (they are static, and the suite runs in random order beside Feature).
- The `standalone` job in `.github/workflows/tests.yml`; the models/migrations row in `docs/agent/standards-feedback.md`.
- `composer analyse`, `composer lint:check` and `composer test:types` pass. The Pest suite was not run (left to the gate).

### Outside the group's file list

- `src/Exceptions/RollbackRefusedException.php`: `GuardianException` is abstract (errors/exceptions), so the refusal is a final subclass with `forTables()`.
- `composer.json` (and the gitignored `composer.local.json`): `RefactorCircus\Guardian\Database\Factories\` → `database/factories/` autoload.
- `phpstan.neon.dist`: dropped the deleted `routes` path, and added `configDirectories` so Larastan accepts `env('GUARDIAN_FORCE_ROLLBACK')` in `config/guardian.php`.
- Deleted the scaffold placeholders: `routes/guardian.php`, `src/Console/Commands/GuardianCommand.php`, `resources/views/placeholder.blade.php`, `lang/en/messages.php`, the placeholder migration, `tests/Feature/ExampleTest.php` and `tests/Modes/.gitkeep`.

### Left

- 2.8: the full Pest run, by the gate.
- The Atrium plugin call: `GuardianPlugin` is group 5's. It goes in `bootWithRoster()` behind `Integration::atriumInstalled()`; note Keystone's `registerAtriumPlugin()` also requires `guardian.ui.enabled`, which `config/guardian.php` does not have (criterion 6's key list).
- No views are loaded yet (`resources/views` is empty until group 5). Checked again in the job worktree: `composer lint:check` and `composer analyse` pass, `ringside check` passes, and `tests/Feature/PackageWiringTest.php` with `tests/ArchTest.php` pass (42 tests). `ring-master.yml` already has Keen's `commands` block, and `CLAUDE.md` is a symlink to `AGENTS.md`, as in Keen. `composer.local.json` still points Roster and Atrium at the two PR worktrees, aliased `dev-main`, and `composer local:install` was not run.
