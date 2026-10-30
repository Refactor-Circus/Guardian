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

## Group 2

Retry of group 2 (criteria 5, 6, 7, 21). The salvaged commit `08c7bac` already held every task's work; this retry reviewed it against the spec and Roster's sources and changed no code.

### Files

- No source, config, migration or test file changed. The files are the ones listed under "Group 2 (salvaged)" above.
- `tasks.md`: 2.8 ticked (the gate runs the full suite).

### Checks made

- Migrations against Roster's `2026_01_01_000006` / `000007` (columns, unique keys, FKs through Roster `Tables` / `UserKey`), `000010` (`_lookup_index` on `sso_connections.organization_id`, `scim_tokens.organization_id` / `sso_connection_id`, `scim_groups.organization_id`) and `2026_10_12_000001` (`_organization_created_index` on `scim_users` / `scim_groups`): equal.
- `guardian` limiter against Roster's `roster` limiter: same keying (`user:` / `ip:`) and null-`per_minute` rule.
- `config/guardian.php` `mcp` / `cortex` against Keen's `config/keen.php`: same literals with Guardian's names.

### Tests

Run file by file (not the whole suite, which the gate runs), all green:

- `tests/Feature/PackageShapeTest.php`: 20 passed.
- `tests/Feature/ConfigTest.php`: 14 passed.
- `tests/Feature/MigrationsTest.php`: 25 passed.
- `tests/Modes` (Standalone and RenamedTables): 19 passed.
- `tests/ArchTest.php`: 17 passed (1 incomplete from the shared preset).

### Deviations and open points

- `Support\Integration` has a third public method, `rosterPresent()`, and a second injected check (`method_exists`), beyond the spec's "two public methods" and "a class-exists check". The `about` line needs to tell "no Roster" from "a Roster without the seams", and a test can only stand in a Roster without `requireSignIn()` / `provisionedStatus()` through an injected method check, because the real Roster is installed. Returned as a decision.
- The Atrium plugin call is not in `bootWithRoster()` yet: `GuardianPlugin` is under `src/Atrium/**`, which group 5 owns. Keystone's `registerAtriumPlugin()` also requires `guardian.ui.enabled === true`, a key criterion 6 does not list. Returned as a decision for group 5.
- Outside the group's file list (carried from the salvaged commit): `src/Exceptions/RollbackRefusedException.php`, `composer.json` (factories autoload), `phpstan.neon.dist`, and the deleted scaffold placeholders.

## Group 3

SSO domain with HTTP API and MCP (criteria 8, 9, 10, 12, 13, 15, 24, 25, 26).

### What I found

Two salvaged commits (`ad29b12` with the moved source, `838f588` with the tests and wiring) held most of the group. I diffed both against Roster at `6b1833dae7ed21ba352612a13693d662bc7354cc` (the pinned SHA in `tests/moved-tests.json`), ran the group's tests, and fixed what failed: the created-connection audit entry had no organization scope, and Pint had not been run.

### Files

- `src/Domains/Sso/**`: Roster's `Domains/Sso` at the pinned SHA, moved with the renames only, except:
  - Actions extend Keystone's `Action` (`protected handle()`; callers still call `execute()`), as actions/actions-events asks. `CreateSsoConnectionAction` makes its own unique slug, because Roster's `Support\Slugs` is not part of the API Guardian may use (criterion 9).
  - `SsoLoginAction::fail()` takes only the reason (the connection and claims it ignored are gone), and finds users through Roster's `Support\Users`.
  - Requests and MCP requests extend Guardian's bases. `SsoOrganizationRequest`, `SsoUserRequest`, `SsoOrganizationMcpRequest` and `SsoUserMcpRequest` replace Roster's `Organization` / `User` request bases, which Guardian may not use. `Concerns\ResolvesOrganizations` replaces Roster's `Support\Concerns\ResolvesScopes`. Each is a copy of what Roster's had.
  - Exceptions extend `GuardianException`.
  - Container, session and cache keys `roster.sso.http` / `.intent` / `.nonce` / `.discovery.*` / `.jwks.*` become `guardian.sso.*`. The criterion 10 grep test allows no `roster.sso` string under `src/`.
  - `SsoServiceProvider` registers the `guardian.sso` resource and the `guardian.sso.view` / `guardian.sso.manage` permissions through `Resources::resource()` / `PermissionCatalogue::permission()`, not the `@internal` `roster*` methods. It registers `SsoSignInRequirement` with `Roster::requireSignIn()`, the API routes through `loadApiRoutesFrom()`, and the web routes `guardian.sso.*` under `guardian.sso.prefix` when `Sso::available()`. Policies come from `guardian.policies`. Roster's gate calls are gone.
  - New `Support\SsoAudit`: Guardian's version of what Roster's `Support\Audit` did for the SSO events. On Keystone's `AuditHooks`, only for `Domains\Sso\Events\*`, it makes the event's `user` the subject. For the scope it uses the event's organization, else the user's current organization (`Roster::organization()`), else the subject's `organization_id`.
- `src/Mcp/Concerns/{DescribesSsoConnection,DescribesOrganization,DescribesUser}.php`: Roster's, without the organization-settings and status schemas no SSO tool uses.
- `src/Mcp/GuardianServer.php`: the seven SSO tools behind `ToolSearch` in `TOOLS`; no tools without Roster. The `guardian.mcp.*` web and local servers and Cortex are wired by group 2's `bootWithRoster()` (Keystone's `registerMcpServer()` / `registerCortex()`).
- `src/Support/Integration.php`: `rosterPresent()` marked `@internal` (D-037). No test counts `Integration`'s public methods, so no test changed.
- `lang/en/guardian.php`: Roster's SSO messages and `unknown_organization`, word for word.
- `tests/Pest.php`: `PARITY_EXCEPTIONS` keeps Roster's `SsoLoginAction` / `LinkSsoIdentityAction` (`http`, `mcp`) with Roster's reason, plus temporary `ui` exceptions marked "Atrium UI group" for the seven other SSO actions. `SsoUnavailableTestCase` is used for `Modes/SsoUnavailable`.
- `tests/TestCase.php`: `$apiRoutes` (on by default for surface tests) and `$mcpServers` (off) switches, `roster.authorization = false` for surface tests, and Roster's own SSO and SCIM routes moved out of the way (`roster.sso.prefix`, `roster.scim.enabled`; criterion 28).
- `workbench/app/Models/User.php`: uses Roster's `HasRoster` trait, which the moved tests need on the user model.

### Tests

- Moved from Roster at the pinned SHA (`tests/moved-tests.json` lists every file and test name):
  - `tests/Feature/Sso/{AzureFlowTest,OidcFlowTest,SsoLoginActionTest,SsoWiringTest,helpers}.php`
  - `tests/Feature/Http/SsoHttpTest.php`
  - `tests/Modes/SsoUnavailable/SsoUnavailableTest.php`, with `SsoUnavailableTestCase` moved beside it
  - the SSO cases of `Authorization/HttpAuthorizationTest`, `McpAuthorizationTest` and `fixtures.php`, as `tests/Feature/Authorization/*`
  - the SSO half of `Modes/Keen/KeenAuditTest` "redacts passwords and connection secrets", as "redacts connection secrets" in `SsoAuditTest`

  Diffed against the originals, the only changes are namespaces, route names (`roster.sso.*` and `roster.*` API names to `guardian.*`), the `roster::roster.*` to `guardian::guardian.*` translation keys, the `roster_sso_connections` to `guardian_sso_connections` table name, the `roster.sso.http` container key, and import order (Pint, after the namespace rename). In `HttpAuthorizationTest`, "covers every roster API route" became "covers every guardian API route", filtering `guardian.*` names. Roster has no SSO-only MCP test file; its SSO MCP cases are in `SsoHttpTest` and `McpAuthorizationTest`.
- New:
  - `MovedTestsManifestTest`: the SHA is pinned, and each moved file keeps at least the recorded cases (criterion 8).
  - `RosterBoundaryTest`: the denied Roster namespaces, by arch rule and by grep, and no `rosterResource` / `rosterPermission` under `src/` (criterion 9).
  - `Authorization/PermissionsTest`: resource, permissions, system rows held by a new admin, `guardian.sso.manage` through the policy per organization, and no `roster.sso` / `roster.scim` string outside `src/Upgrade` (criterion 10).
  - `Sso/SsoAuditTest`: one entry, source `guardian`, with the connection as subject and its organization as scope; nothing with `guardian.audit.enabled = false`; secrets redacted (criterion 15).
  - `Sso/SsoRoutesTest`: API names, URIs and middleware, the web routes under `roster/sso`, and no API route with `routes.enabled = false` (criterion 25).
  - `Mcp/SsoToolsTest`: the seven tools, an output schema on every structured tool, name, description and schemas equal to Roster's, and MCP structured content equal to the HTTP payload (criterion 26).
  - `Mcp/GuardianServerTest`: no web or local server as shipped, both with `guardian.mcp.*` on.
- Run file by file, not the whole suite: group 3's files with `tests/Modes` and `tests/ArchTest.php` gave 162 passed. `composer lint:check`, `composer analyse` and `composer test:types` (100%) pass.

### Deviations and notes for later groups

- Pint reformatted the moved source (brace and import style) and two moved test files (import order only).
- `SsoAudit` covers only the SSO events. Group 4 needs the same subject and scope hooks for the SCIM events. Roster scoped them through its own `Support\Audit`, which Guardian cannot use.
- The ten-tool listing of criterion 26 is group 4's, once the SCIM tools exist. `SsoToolsTest` checks the seven SSO tools.
- Criterion 10's re-check: Roster's migrations at the pinned SHA store role scope only as the `scope` enum column. No column holds a resource key, so the upgrade needs no resource-key step.
- Cortex is not installed here, so `registerCortex()` runs as a no-op in tests. Keen tests it the same way.

### Retry

- Every group 3 task was already committed in `ad29b12`, `838f588` and `7283fc8`, and the working tree was clean. The retry changed no code.
- Re-checked: the group's tests with `tests/Modes`, `tests/ArchTest.php`, `PackageShapeTest` and `ConfigTest` (197 passed, run file by file, not the whole suite), `composer lint:check` and `composer analyse` all pass. The criterion 9 boundary and criterion 10 grep tests scan the paths the spec names, and `PARITY_EXCEPTIONS` holds Roster's two entries plus the temporary "Atrium UI group" ones.
