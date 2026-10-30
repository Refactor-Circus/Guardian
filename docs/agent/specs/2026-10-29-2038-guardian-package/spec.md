# Guardian package

## Goal

Create `refactor-circus/guardian` (`RefactorCircus\Guardian`), the suite's auth and security package, with SSO and SCIM moved out of Roster and their behaviour unchanged. Guardian requires only Keystone and suggests Roster and Atrium. Without Roster it is inert, with a clear `about` line (D-023, D-032). It reaches Roster only through Roster's public seams and public domain API (D-024, D-036), and reaches Roster's screens only through Atrium's screen extensions registry, placed by editable defaults in `config/guardian.php`. Existing installs keep their connections, identities, tokens and role grants through an idempotent upgrade that runs only once Roster has handed SSO and SCIM over (D-022, D-027).

"Behaviour unchanged" means what the SSO and SCIM features do, not what they are called. These renames are accepted breaks, all listed in the README "Upgrading from Roster" section: permission names (`roster.sso.*` / `roster.scim.*` to `guardian.*`), route names, Atrium tab keys, config and env keys (`roster.sso.*` / `roster.scim.*` to `guardian.sso.*` / `guardian.scim.*`), table names (`roster_*` to `guardian_*`), the Keen package key and event class names for their audit entries, and resource keys. Guardian never reads the old config keys as a fallback; the upgrade only detects and reports leftover values (D-030, criterion 22). `guardian.routes.enabled => false` is carried over from Roster's own default for its API routes, not a new choice. The default SSO web prefix stays `roster/sso`, so identity-provider callback URLs keep working (D-026).

Sources:
- Roster at the `auth-extension-seams` PR (SSO/SCIM already on the seams; worktree `Roster--2026-10-29-2038-auth-extension-seams`).
- Atrium at the `screen-extensions-registry` PR (#16; worktree `Atrium--2026-10-29-2037-screen-extensions-registry`).
- Roster `adopt-atrium-screen-registry`, which declares the host screens `roster.organization` and `roster.user`. The Atrium UI task group starts only after it merges (D-035).
- The original behaviour specs `Roster/docs/agent/specs/2026-10-01-1251-sso` and `2026-10-01-1308-scim`.

## Acceptance criteria

### 1. Package wiring (first task group)

1. **Composer.** `Guardian/composer.json` names `refactor-circus/guardian` and requires `php: ^8.5`, `laravel/framework` (the same constraint as Keen) and `refactor-circus/keystone: dev-main`, and no other `refactor-circus/*` package.
   - `suggest` lists `refactor-circus/roster`, `refactor-circus/atrium`, `laravel/mcp`, `laravel/socialite`, `socialiteproviders/saml2`, `firebase/php-jwt` and `socialiteproviders/microsoft-azure`. The four vendor entries use Roster's current wording and constraints, asserted equal to Roster's `composer.json` `suggest` values at the pinned Roster SHA (criterion 8).
   - `require-dev` adds Roster, Atrium, Keen (for audit tests), Ringside and Standards (all `dev-main`), plus the four SSO vendors and Keen's dev tools.
   - `repositories` has a VCS entry for every suite package named in `require` or `require-dev`: Keystone, Roster, Atrium, Keen, Ringside and Standards.
   - `conflict` has a `refactor-circus/atrium` entry that refuses Atrium versions older than the screen extensions registry (D-020). Atrium has no tags today, so the constraint is `<0.1.0`: v0.1.0 is Atrium's first tagged release and carries the registry, and `dev-main` is not refused. The runtime `ScreenExtensions` check of criterion 7 stays as the guard for untagged installs.
   - The `conflict` entry on Roster versions older than the `remove-sso-and-scim` release is not added in this spec. It is added once that release is tagged (D-022, D-027; see Out of scope).
   - Proof: a test reads `composer.json` and asserts the `require` keys, the `suggest` keys and the vendor wording, the `repositories` URLs and the `conflict` entry for `refactor-circus/atrium`.
2. **Local install.** `bin/composer-local.php` and the `local:install` / `local:update` scripts are Keen's. The scripts list, `extra.laravel.providers` (`GuardianServiceProvider`), the `Guardian` facade alias, `testbench.yaml`, `phpunit.xml.dist` (suites `Arch`, `Feature`, `Modes`), `phpstan.neon.dist`, `phpstan.windows.neon`, `pint.json`, `.gitignore`, `.gitattributes` and `.github/workflows/tests.yml` follow Keen's layout.
   - On a fresh clone in the workspace, `composer local:install` then `composer test` passes.
   - **Build-time dependencies.** During this build, Roster must resolve from the seams PR branch and Atrium from PR #16. The job worktree's `composer.local.json` (gitignored, as in Keen) therefore lists path repositories for `../Roster--2026-10-29-2038-auth-extension-seams` and `../Atrium--2026-10-29-2037-screen-extensions-registry` ahead of the generated sibling entries, each with `"options": {"symlink": true, "versions": {"refactor-circus/<name>": "dev-main"}}`, so they satisfy `dev-main`. The builder installs with `COMPOSER=composer.local.json composer update`, not `composer local:install`, because `bin/composer-local.php` regenerates the file and would drop the two entries. Once both PRs have merged, the entries are removed and `composer local:install` is used again.
   - Proof: a Pest test asserts the `local:install` and `local:update` scripts in `composer.json` equal Keen's, and that `bin/composer-local.php` is byte-identical to Keen's. CI installs from the committed `composer.json` and runs the gate.
3. **Shared arch preset.** `tests/Pest.php` calls `RefactorCircus\Standards\Pest\ArchPreset::register()`. `tests/ArchTest.php` runs:
   - `arch()->preset()->php()`, `->security()` and `->refactorCircus()`
   - the global bans (`dd`, `ddd`, `env`, `exit`)
   - every Keystone `Arch` finder Keen uses: `domainClasses`, `dataWithoutRules`, `actionEventGaps`, `writesOutsideTransactions`, `modelEventGaps`, `modelsWithoutPolicies` against `guardian.policies`, and `literalTableNames` against `Support\Tables::DEFAULTS`
   - Keen's request-persist rule and the parity rule (criterion 24)

   Finders that have nothing to check yet pass on an empty `src/Domains`. Proof: `vendor/bin/pest tests/ArchTest.php` passes.
4. **Ring Master files.** `vendor/bin/ringside install` has been run, so `ring-master.yml` (Keen's commands block), `docs/agent/` (with `product/`, `specs/`, `standards/` and `standards-feedback.md`), `pint.json` and the `.gitignore` block for generated files exist as in Keen. `vendor/bin/ringside sync` generates `docs/agent/standards.index.yml`, which is gitignored as in Keen. `CLAUDE.md` describes Guardian's role in the suite in the shape of Keen's "Role in the suite" section. `AGENTS.md` carries the generated Ring Master block. `docs/agent/product/roadmap.md` lists SSO and SCIM as done, and MFA, session policies and IP rules as planned.
   - Proof: a Pest test asserts the listed files exist, `.gitignore` lists the generated files, and `roadmap.md` lists the done and planned items. CI runs `vendor/bin/ringside check`, which fails if the generated files are stale.

### 2. Package shape

5. `GuardianServiceProvider` extends Keystone's `PackageServiceProvider` with `Package::make('guardian', __NAMESPACE__)->label('Guardian')->server(GuardianServer::class)`.
   - It merges `config/guardian.php` and, while Roster is installed (criterion 7), registers `Domains\DomainServiceProvider`, which registers `SsoServiceProvider` and `ScimServiceProvider` through its `$providers` loop.
   - `Support\Tables` extends Keystone's `Database\Tables`, with `CONFIG = 'guardian.tables'` and `DEFAULTS` of `sso_connections => guardian_sso_connections`, `sso_identities => guardian_sso_identities`, `scim_tokens => guardian_scim_tokens`, `scim_users => guardian_scim_users` and `scim_groups => guardian_scim_groups`.
   - Proof: a feature test resolves `PackageRegistry::get('guardian')` and asserts each model's `getTable()`. A renamed-tables mode (`tests/Modes/RenamedTables` with `tests/RenamedTablesTestCase.php`, as in Keen) asserts a configured name is used by both the models and the migrations.
6. **Config.** `config/guardian.php` has a banner per section, safe defaults and casts on read (architecture/configuration):
   - `sso`: `redirect => '/'`, `discovery_cache_seconds => 3600`, `prefix => 'roster/sso'` and `middleware => ['web']`, which are Roster's values at the pinned SHA. The `roster/sso` prefix is Guardian's default for this release (D-026); the README says apps may change it.
   - `scim`: `enabled => true`, `prefix => 'scim/v2'`, `middleware => ['api', 'throttle:guardian']`, `default_count => 100`, `max_count => 500`, `bulk.max_operations => 100`, `bulk.max_payload_bytes => 1048576` and `audit_context_packages => ['guardian', 'roster']`.
   - `routes`: `enabled => false`, `prefix => 'guardian'`, `middleware => ['api', 'throttle:guardian']`.
   - `rate_limit.per_minute => 120`, `audit.enabled => true`, `policies`, `tables`, `mcp.web` / `mcp.local` and `cortex`: the literal defaults of Keen's `config/keen.php` for the same keys, copied at Keen's pinned SHA.
   - `migrations.force_rollback => (bool) env('GUARDIAN_FORCE_ROLLBACK', false)` (criterion 21).
   - `atrium.screens` and `atrium.organization_route` (criteria 16 and 19).

   Guardian defines its own `guardian` rate limiter: per user, or per IP for guests, with a null `per_minute` turning it off, exactly as Roster's `roster` limiter does at 120 a minute. The SCIM throttle therefore keeps the same limit and keying; only the limiter name changes. Proof: a config test asserts every key and literal default above, that the `sso` and `scim` defaults equal Roster's `roster.sso` / `roster.scim` values at the pinned SHA, that the 121st request in a minute gets a 429, and that no value in the file names a `roster.*` config key.

### 3. Without Roster, without Atrium

7. **Roster is optional; without it Guardian is inert** (D-023, D-032).
   - `Support\Integration` is a final singleton constructed with a class-exists check (default `class_exists(...)`), so tests can stub it. It has two public methods:
     - `rosterInstalled()` is true only when `RefactorCircus\Roster\Roster` exists and has the capabilities Guardian uses: `method_exists(Roster::class, 'requireSignIn')` and `'provisionedStatus'`, and the classes `Resources`, `PermissionCatalogue` and `MembershipSources`.
     - `atriumInstalled()` is true only when `RefactorCircus\Atrium\ScreenExtensions` exists.
   - Without Roster, Guardian boots: config, translations and the package registration load, and `php artisan about` shows a Guardian section with the line "Inactive: install refactor-circus/roster". Nothing else is registered: no domain providers, migrations, API, SSO or SCIM routes, MCP tools, command, Atrium plugin or screen registrations.
   - A Roster without the seam methods or classes falls back to the same inactive path with the `about` line "Inactive: needs a newer refactor-circus/roster", never a missing-method fatal. An Atrium without `ScreenExtensions` registers no Guardian plugin and no screen extensions; the rest of Guardian works.
   - Proof:
     - `tests/Modes/Standalone` (with `tests/StandaloneTestCase.php`) binds an `Integration` whose check reports no Roster and no Atrium. It boots the app and asserts the `about` line, no `guardian.*` route, `GuardianServer` lists no tools, the domain providers are not loaded, `guardian-migrations` publishes nothing, and Atrium has no `guardian` plugin or screen registration. This suite runs inside `composer test`.
     - A second Standalone case stubs a Roster without the seam methods and asserts the "needs a newer" line and no registrations.
     - A `standalone` CI job in `.github/workflows/tests.yml` runs `composer remove --dev --no-update refactor-circus/roster refactor-circus/atrium refactor-circus/keen`, then `composer update`, asserts the classes are absent, and runs `vendor/bin/pest tests/Modes/Standalone`. It must pass before merge.
   - An arch test asserts that every class referencing `RefactorCircus\Roster\*` is under `src/Domains`, `src/Atrium`, `src/Mcp`, `src/Upgrade`, `src/Policies`, `src/Http`, `src/Console` or `database/`, and that `GuardianServiceProvider` reaches them only after `rosterInstalled()`.
30. **Without Atrium** (D-020). Guardian runs without Atrium, proven both ways:
    - `tests/Modes/WithoutAtrium` (with `tests/WithoutAtriumTestCase.php`) does not register Atrium's providers and binds an `Integration` whose check reports Roster present and Atrium absent. It runs inside `composer test` and asserts: Guardian's domain providers load, the HTTP API (with `routes.enabled = true`), SSO web and SCIM server routes and the ten MCP tools work, no `atrium.guardian.*` route exists, and nothing is registered with Atrium.
    - A `without-atrium` CI job runs `composer remove --dev --no-update refactor-circus/atrium` together with every dev dependency that requires Atrium, then `composer update`, fails unless `class_exists('RefactorCircus\Atrium\Atrium')` is false, and runs `vendor/bin/pest tests/Modes/WithoutAtrium`. This lane needs a Roster whose `require` no longer names Atrium, which Roster `adopt-atrium-screen-registry` delivers, so it is added in the Atrium UI task group (D-035).
    - An arch test asserts no class outside `src/Atrium` references `RefactorCircus\Atrium\*`.

### 4. Domains moved

8. **SSO and SCIM move.** Everything under Roster's `src/Domains/Sso` and `src/Domains/Scim` moves to `Guardian/src/Domains/Sso` and `Guardian/src/Domains/Scim`: actions, data, events, exceptions, HTTP controllers, requests, middleware, MCP requests and tools, models, policies, resources, services, `Concerns`, `Support\NotSsoEnforced` and the routes files. So do `Mcp\Concerns\DescribesSsoConnection` (as `Guardian\Mcp\Concerns\DescribesSsoConnection`) and the factories.
   - Namespaces change from `RefactorCircus\Roster\Domains\{Sso,Scim}` to `RefactorCircus\Guardian\Domains\{Sso,Scim}`, and config reads change from `roster.sso.*` / `roster.scim.*` to `guardian.sso.*` / `guardian.scim.*`.
   - Optional vendor gating stays in `Sso::available()` behind `class_exists()`.
   - Proof: Roster's SSO and SCIM test files are copied into Guardian and pass. These are `tests/Feature/Sso`, `tests/Feature/Scim`, `Http/SsoHttpTest`, `Http/ScimTokensHttpTest`, `Ui/SsoPagesTest`, `Ui/ScimPagesTest`, `Modes/SsoUnavailable` and the SSO/SCIM cases of the `Authorization/*` suites, as of the seams PR.
     - The source is pinned to a Roster commit SHA, recorded in the PR and in `tests/moved-tests.json`, not to a branch.
     - The only changes allowed are the mechanical ones forced by this spec: namespaces, config keys, table names, route names, permission names, tab keys, view and translation names, and the Atrium tab and section assertions of criteria 16 to 18. Each changed test is listed in the PR by file and test name.
     - The UI test files (`Ui/SsoPagesTest`, `Ui/ScimPagesTest`) are copied by the Atrium UI task group, the rest by the domain groups.
     - A manifest of the moved test files and test names (`tests/moved-tests.json`) is committed, and a Pest test asserts Guardian's suite has at least as many cases per moved file as the manifest records.
     - The PR includes a diff of the copied tests against the originals at the pinned SHA, which may show only renames from the list above.
9. **What Guardian may use from Roster** (D-024, D-036).
   - Allowed: the seams (`Resources`, `PermissionCatalogue`, `MembershipSources`, `Contracts\SignInRequirement`, `Data\ExternalSignIn`, `Roster::requireSignIn()`, `Roster::provisionedStatus()`), plus Roster's public domain API: models, actions and their Data, `Roster`, `Authorizer`, `Support\Users`, `Support\Statuses`, `Support\UserKey` and `Support\Tables` (the last only to name Roster's own tables in migrations).
   - Not allowed: any `@internal` member (`rosterResource()`, `rosterPermission()`), and any class under `RefactorCircus\Roster\Atrium`, `Roster\Http`, `Roster\Mcp`, `Roster\Support\Concerns`, `Roster\Support\Policies`, `Roster\Domains\*\Http` or `Roster\Domains\*\Mcp`.
   - Guardian has its own request bases extending Keystone's `Http\Requests\Request` and `Mcp\Requests` bases, its own policy base answering through `Authorizer::permits()`, and its own organization-binding helpers.
   - Proof: an arch test asserts the denied namespaces, and a grep test asserts the two `@internal` method names never appear under `src/`.

### 5. Roster seams

10. **Resources and permissions.** From their providers' `boot()`, the SSO domain calls `Resources::resource('guardian.sso', SsoConnectionModel::class)` and the SCIM domain calls `resource('guardian.scim', ScimTokenModel::class)`.
    - Through `PermissionCatalogue::permission()`, they register `guardian.sso.view` ("View an organization's single sign-on connections."), `guardian.sso.manage` ("Configure single sign-on connections.") and `guardian.scim.manage` ("Issue and revoke SCIM provisioning tokens."), each with `['admin']`.
    - Every `roster.sso.*` / `roster.scim.*` permission name in moved code, views, requests and policies becomes the `guardian.*` name.
    - Resource keys are not persisted: Roster stores role scope as the `RoleScope` enum values (`global`, `organization`, `team`), and the resource keys exist only in the in-memory `Resources` registry (checked at the pinned SHA). So the upgrade needs no resource-key rename step. The implementer re-checks that no Roster column or cache stores `roster.sso` / `roster.scim` before merging, and adds a rename step with a test if one does.
    - Policies are registered from `guardian.policies`, which maps the five models to their policies, through Keystone's `registerPolicies()`.
    - Proof:
      - The registry resolves `guardian.sso` and `guardian.scim`.
      - After `BuiltInRoles::sync()` the three permissions exist with `system = true`, and a newly created `admin` holds them.
      - `guardian.sso.manage` reaches `SsoConnectionPolicy` through `Authorizer::check()`: pass for an admin of the organization, fail for an admin of another.
      - A grep test finds no `roster.sso` or `roster.scim` string under `src/`, `resources/` or `config/`, except in `src/Upgrade`.
11. **Membership source.** The SCIM provider registers `MembershipSources::register('scim', 'SCIM')` inside `$this->app->booted()`, and only when `MembershipSources::has('scim')` is false, so a Roster that still registers it does not throw. `ScimUsers` passes `'scim'` to `join()`. Proof: a membership provisioned by SCIM stores `scim` and lists with the label "SCIM". A test registers `scim` before Guardian's booted callback and asserts that boot does not throw.
12. **Sign-in requirement.** `Domains\Sso\Services\SsoSignInRequirement` implements `SignInRequirement` and is registered with `Roster::requireSignIn(SsoSignInRequirement::class)` from the SSO provider. `NotSsoEnforced` uses `Roster::externalSignInFor()`. Proof: the moved `SsoWiringTest` enforcement cases pass: an enforced domain refused, a non-enforced domain allowed and a super-admin allowed.
13. **Provisioning status.** `SsoLoginAction` and `ScimUsers` take a new user's status from `Roster::provisionedStatus($organization)`. Proof: the moved tests that provision an SSO user and a SCIM user into an organization with `provisioned_status = pending` pass.
14. **SCIM audit context.** Inside `$this->app->booted()`, the SCIM provider registers one closure on Keystone's `AuditHooks::context()`, when Keystone's `AuditTrail::available()` is true.
    - The closure returns `['scim_token' => ['id' => …, 'name' => …]]` (shape unchanged) while `ScimContext` holds a token and the event's package key (`PackageRegistry::for($event)?->key`) is in `guardian.scim.audit_context_packages`. Otherwise it returns `[]`.
    - It wraps its body in try/catch, calls `report()` and returns `[]` on a throw (Roster D-009, D-012).
    - Proof (Keen installed): a member provisioned through SCIM yields a Roster entry carrying `scim_token`. An entry for a package not in the list carries none. A throwing `ScimContext` leaves the entry written without it, and the exception reported (`Exceptions::fake()`).
    - Audit continuity: entries for SSO and SCIM move from package key `roster` to `guardian`, and from Roster's event classes to Guardian's. History queries or exports that filter by package or event show a discontinuity at handover, noted in the README upgrade section.
15. **Audit of Guardian's own events** (behaviour parity: Roster audited these; Keen is a dev dependency only). With `guardian.audit.enabled` on and Keen installed, Guardian's model and action events are recorded as package `guardian`, with the moved model as subject and its organization as scope, as Roster's support did for them. Proof: creating a connection through the action writes one entry with the connection as subject and its organization as scope. With `guardian.audit.enabled = false` it writes none.

### 6. Atrium UI

The Atrium UI task group starts only after Roster `adopt-atrium-screen-registry` has merged (D-035), so criteria 16 to 20 run against Roster's real `roster.organization` and `roster.user` screens, not a stand-in.

16. **Placement by config (Atrium D-019).** `config/guardian.php` `atrium.screens` defaults to:
    ```php
    'screens' => [
        'roster.organization' => ['guardian.sso', 'guardian.scim'],
        'roster.user' => ['aside' => 'guardian.sso-identities'],
    ],
    ```
    In each target's list, an integer-keyed value is a Guardian tab key, registered with `ScreenExtensions::tab($target, …)`. A string key is a slot, and its value is a Guardian section key, registered with `section($target, $slot, …)`.
    - Registration happens in `GuardianPlugin` (under `src/Atrium`), only when `Integration::atriumInstalled()` and `rosterInstalled()` are true. Nowhere else in the code names a Roster screen key.
    - **Malformed entries** (D-021): an unknown tab or section key, a non-string target, or an entry Atrium rejects with `InvalidArgumentException`. In the `local` and `testing` environments, Guardian throws `InvalidArgumentException` naming the target and key at boot. In any other environment it calls `report()` once per bad entry, skips that entry, registers the rest and keeps booting.
    - Proof:
      - The defaults register as listed, asserted with `Atrium::screens()->tabs('roster.organization')` and `sections('roster.user', 'aside')`.
      - Emptying `atrium.screens` registers nothing.
      - Moving `guardian.sso` to another target registers it there instead.
      - An unknown key throws in `testing`. With the environment set to `production`, the same entry is reported once (`Exceptions::fake()`), skipped, the other entries register and boot completes.
      - A grep test finds `roster.organization` / `roster.user` only in `config/guardian.php`, the README, the Boost skill and tests.
17. **Tabs on `roster.organization`.**
    - `TabDefinition::make('guardian.sso')`: label `__('guardian::guardian.sso')`, icon `arrow-right-end-on-rectangle`, view `guardian::atrium.organization-sso`, `permission('guardian.sso.view')`. Its resolver returns `['connections' => ListSsoConnectionsAction` (25 per page, page from the request, query string kept), `'organization' => $record]`.
    - `guardian.scim`: label `__('guardian::guardian.scim')`, icon `server-stack`, view `guardian::atrium.organization-scim`, `permission('guardian.scim.manage')`. Its resolver returns `['tokens' => ListScimTokensAction, 'baseUrl' => …, 'organization' => $record]`.
    - The views reproduce Roster's `sso` / `scim` tab markup: the list, create form, one-time token alert and base URL card. They post to Guardian's Atrium routes (criterion 19), and gate controls with `guardian.sso.manage` / `guardian.scim.manage` through `Authorizer::check()`.
    - Permission checks go through the host screen's `access()` closure (Roster's), with the organization as record.
    - Asserted markup, by test id and element: `tab-guardian.sso`, `tab-guardian.scim`, the connection list rows, the create form, the one-time token alert and the base URL card, as the moved UI tests assert them.
    - This tab is the home for managing SSO connections; the connection screen is reached from it, as today (D-025).
    - Proof:
      - A tab with `permission('guardian.sso.view')` is hidden, and returns 403, when the host `access()` closure denies it.
      - `?tab=guardian.sso` renders the connection list and create form for an admin.
      - A member without `guardian.sso.view` gets 403 on `?tab=guardian.sso` and sees no `tab-guardian.sso` link.
      - `?tab=guardian.scim` behaves the same with `guardian.scim.manage`.
      - The token alert shows once after creating a token.
      - The SSO and SCIM resolvers are never called while another tab is open (counting spy).
18. **Section on `roster.user`.** `SectionDefinition::make('guardian.sso-identities')` in slot `aside`, view `guardian::atrium.user-sso-identities`, label `__('guardian::guardian.sso_identities')`.
    - Its `authorize(fn (Request $request, ?Model $record, Screen $screen): bool)` returns true only when `$record` is a user with at least one SSO identity (one `exists` query), keeping Roster's "shown only when not empty".
    - Its resolver returns `['identities' => ListSsoIdentitiesAction` (50 per page)`]`.
    - The unlink button shows under `Authorizer::check($viewer, 'guardian.sso.manage', $connection->organization, $self)`, as today.
    - Proof: `authorize` runs exactly one `exists` query (query-count assertion). A user with two identities shows `section-guardian.sso-identities` with both. A user with none shows no section, and the resolver is not called. The unlink form posts to `atrium.guardian.sso-identities.destroy`.
19. **Guardian's own screen.** `GuardianPlugin` (key `guardian`, no navigation items, D-025) registers these routes under Atrium's `atrium.` name prefix:

    | Method | URI | Name |
    |---|---|---|
    | POST | `guardian/organizations/{organization}/sso` | `guardian.sso.store` |
    | GET | `guardian/sso/{connection}` | `guardian.sso.show` |
    | PATCH | `guardian/sso/{connection}` | `guardian.sso.update` |
    | DELETE | `guardian/sso/{connection}` | `guardian.sso.destroy` |
    | DELETE | `guardian/sso-identities/{identity}` | `guardian.sso-identities.destroy` |
    | POST | `guardian/organizations/{organization}/scim-tokens` | `guardian.scim-tokens.store` |
    | DELETE | `guardian/scim-tokens/{token}` | `guardian.scim-tokens.revoke` |

    The show page (moved from `roster::ui.sso.show`) declares `Screen::standard('guardian.sso-connection', 'detail')`:
    - `->record($connection)`, with `access(fn (string $permission, Request $request, ?Model $record): bool => app(Authorizer::class)->check($request->user(), $permission, $connection->organization))`.
    - It has only the synthetic `overview` tab and the slots `header.actions`, `before`, `after` and `aside`.
    - It renders `x-atrium::screen-tabs`, `x-atrium::screen-tab-panel` and the sections of each slot, laid out as Atrium's screen-tabs and panels place them: the tab bar above `before`, `header.actions` in the page header, `aside` beside the main column, `after` below it (asserted by slot test ids).
    - The controller authorizes `guardian.sso.view` before calling `resolve()`.
    - The page header's back link goes to `route(config('guardian.atrium.organization_route'), [$organization, 'tab' => 'guardian.sso'])`, rendered only when `Route::has()` that name. The default is `atrium.roster.organizations.show`.
    - Proof:
      - The moved `SsoPagesTest` / `ScimPagesTest` pass (criterion 8).
      - A feature test registers a tab and a section in each of the four slots on `guardian.sso-connection`, and asserts each renders in its slot and the tab opens through `?tab=`.
      - With nothing registered, no tab bar renders.
      - A viewer without `guardian.sso.view` gets 403 and no resolver runs.
      - The back link is absent when the configured route does not exist.
20. **Views.** All Guardian views use `x-atrium::*` components and Atrium-safelisted utilities only, with no stylesheet. Strings are in `lang/en/guardian.php`, moved from Roster's SSO/SCIM strings. Proof: a styles test with Atrium's `Testing\AtriumStyles` asserts `missingClasses()` and `inlineStyles()` are empty for `resources/views`. The suite uses `Atrium\Testing\ResetsScreenExtensions`.

### 7. Data and upgrade

21. **Create migrations.**
    - `database/migrations/2026_11_01_000001_create_guardian_sso_tables.php` and `000002_create_guardian_scim_tables.php` create the five tables with Roster's columns, unique keys, the lookup indexes from Roster's `000010` (`sso_connections.organization_id`; `scim_tokens.organization_id`, `sso_connection_id`; `scim_groups.organization_id`) and the performance indexes from `2026_10_12_000001` for `scim_users` / `scim_groups`.
    - Foreign keys to organizations and teams use Roster's configured table names (`Roster\Support\Tables::name()`), and user columns use `Roster\Support\UserKey`.
    - Each `Schema::create` is skipped when the table already exists (needed after the upgrade renames; see Design).
    - Migrations load, and publish under the `guardian-migrations` tag, only while Roster is installed.
    - **`down()` refuses to drop data** (D-031). Before dropping, `down()` checks every table it would drop. If any has rows and `guardian.migrations.force_rollback` is false, it throws `GuardianException` naming the tables with rows and drops nothing. With the flag on, it drops them. Empty tables drop as usual. This guards the upgraded install, whose tables hold the data carried over from Roster, and any other install alike, because a migration cannot tell where its tables came from. The README "Upgrading from Roster" section documents the rule and the `GUARDIAN_FORCE_ROLLBACK` env variable.
    - Proof: a fresh migrate creates the five tables with the named indexes. `migrate:rollback` drops empty tables in reverse order. With a connection row present, rollback throws, names `guardian_sso_connections` and leaves all five tables. With `force_rollback = true`, rollback drops them.
22. **Upgrade from Roster.** The upgrade (`Upgrade\RosterUpgrade::run(): UpgradeReport`) runs from `database/migrations/2026_11_01_000000_upgrade_guardian_from_roster.php` and from `php artisan guardian:upgrade-from-roster`. It is idempotent, safe to run from a deploy script, and never deletes rows that hold data.
    - **Handover gate** (D-022, D-027). It runs only when `Upgrade\Handover::complete()` is true, meaning Roster no longer declares the tables: `! class_exists('RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel')`, written as a string literal. Otherwise it changes nothing and writes "Roster still owns SSO and SCIM; run `php artisan guardian:upgrade-from-roster` after upgrading Roster." Because the migration is recorded even when it skipped, the command finishes the job later.
    - DDL is not transactional (MySQL commits implicitly), so the run first plans every table, then acts, and each step can be re-run: a run that fails midway leaves a state the next run completes.
    - **Tables:** the source names are `config('roster.tables.{key}') ?? 'roster_{key}'`, and the targets come from Guardian's `Tables`. They are decided per table before anything changes, then acted on in two passes. Pass one drops the empty new tables that have an old counterpart, in reverse FK order (groups, users, tokens, identities, connections). Pass two renames old tables in forward FK order (connections, identities, tokens, users, groups).
      - Old table only: rename it.
      - Both exist and the new one is empty: drop the empty new table (pass one), then rename the old one (pass two).
      - Both exist and the new one has rows: change nothing, and report the conflict by table name.
      - Neither, or only the new one: do nothing.
    - **Grants:** in Roster's permissions table, for `roster.sso.view`, `roster.sso.manage` and `roster.scim.manage`:
      - Old name only: rename the row to the `guardian.*` name in place, keeping its `permission_role` grants.
      - Both names: copy each old grant to the new row unless it is already there, then delete the old row.
      - The grant step runs inside one DB transaction (DML is transactional), so a re-run after a failure finds either the old or the new state. The only Roster tables that hold permission names or grants are `permissions` and `permission_role` (role assignments reference role ids), so there are no direct or scoped grants to carry.
      - Afterwards: call `Permissions::forget()`.
    - **Stranded state** (D-029): handover is complete, a source `roster_*` table holds rows, and the matching `guardian_*` table is empty or missing. This happens when the upgrade was skipped before Roster dropped its classes, so the create migrations made empty tables. Guardian warns only:
      - a `php artisan about` line naming the stranded tables and the command to run
      - one reported `GuardianException` per boot naming them
      - SSO enforcement stays off for the stranded connections: `SsoSignInRequirement` reads Guardian's (empty) tables as usual and does not fail closed. Enforcement returns once the command has moved the rows.
    - **Leftover config** (D-030): Guardian never reads `roster.sso.*` / `roster.scim.*` values. The upgrade report and the command list every such value that is set and differs from Guardian's current `guardian.*` value, with the `guardian.*` key to move it to.
    - `down()` of the upgrade migration does nothing.
    - **Drivers** (D-034): every case below runs on SQLite inside `composer test` (foreign keys enforced). The rename and FK-survival cases, tagged `->group('upgrade-mysql')`, also run in a `mysql` CI job in `.github/workflows/tests.yml` against a MySQL 8 service (`DB_CONNECTION=mysql`), which runs `vendor/bin/pest --group=upgrade-mysql` and must pass before merge.
    - Proof, with a test per case:
      - fresh install (no-op)
      - old tables with rows: rows, ids and the `scim_tokens.sso_connection_id` FK survive (deleting a connection nulls the token's FK, with foreign keys enforced); row counts of the five tables and of `permission_role` are asserted before and after
      - both exist with the new tables empty, including all five new tables empty at once (the skip-then-create state), which must drop in reverse FK order without an FK error
      - a failure injected after the drop pass leaves a state the next run completes
      - a stranded state (handover complete, `roster_*` rows present, empty `guardian_*` tables): the `about` line and the reported exception appear, and an SSO sign-in for a stranded connection's domain is not refused
      - a leftover `roster.scim.enabled` / `roster.sso.prefix` value that differs is listed in the report, and Guardian's runtime value is unchanged
      - a custom role that held an old permission name keeps its grant, and a re-run after a partial grant failure completes
      - a real-migration test: Roster's own create migrations run, rows are seeded through Roster's models, then Guardian's migrations and the command run with `Handover` forced complete
      - `Handover` itself: the injected class-exists check returns true and false, and the real implementation's literal class string is asserted
      - both with rows (unchanged, reported)
      - a second run is a no-op
      - handover pending (nothing changes, message written)
      - grants for old only, both and neither, with an `admin` that held `roster.sso.manage` holding `guardian.sso.manage` afterwards and passing `Authorizer::check()`
      - the command prints the report
23. **Morph aliases.** While `Handover::complete()`, each moved model calls `keepMorphAliases()` mapping its old Roster class name (as a string) to itself, so Keen entries whose `subject_type` is the old class resolve to Guardian's model. While handover is pending none are registered, because Roster's classes still own those names. Proof (models/stored-identifiers): a Keen entry stored with the old `SsoConnectionModel` class name resolves to Guardian's model with handover complete, and an alias test asserts nothing is registered while pending.
31. **Handover gates the live routes** (D-022, D-027, D-028). The SSO domain loads its SSO web routes (when `Sso::available()`) and the SCIM domain loads its SCIM server routes (when `guardian.scim.enabled`) only while `Handover::complete()`, so Guardian's routes never shadow Roster's identical URIs. The API routes, MCP tools, permissions, resources and Atrium registrations do not depend on handover. Proof: covered by the `HandoverPending` suite of criterion 28, plus a test that with handover complete `Route::has('guardian.sso.start')` and `Route::has('guardian.scim.users.index')` are true.

### 8. Surfaces and docs

24. **Parity.** Every Guardian action is reachable from the HTTP API (`Domains/*/Http/Requests`), MCP (`Domains/*/Mcp/Requests`) and a UI (`src/Atrium/**` and `*WebController`). `PARITY_EXCEPTIONS` keeps Roster's entries for `SsoLoginAction` and `LinkSsoIdentityAction` (`http`, `mcp`), with their reasons. Until the Atrium UI group lands, the UI half of the rule lists the actions whose UI arrives there as temporary exceptions, each with the reason "Atrium UI group"; that group removes them. Proof: the arch parity rule passes, and after the Atrium UI group no temporary exception remains.
25. **HTTP API.** The moved `routes.php` files load through Keystone's `loadApiRoutesFrom()` under `guardian.routes`. Routes are named `guardian.organizations.sso-connections.index|store`, `guardian.sso-connections.show|update|destroy`, `guardian.users.sso-identities.index`, `guardian.sso-identities.destroy`, `guardian.organizations.scim-tokens.index|store` and `guardian.scim-tokens.destroy`. The SSO web routes are named `guardian.sso.discover|start|callback|metadata|link`, and the SCIM server routes `guardian.scim.*`. Payloads are unchanged. Proof: the moved HTTP tests pass by route name, and `routes.enabled = false` (the default) registers none of the API routes.
26. **MCP.** `Mcp\GuardianServer` (Keystone `Mcp\Server`) registers the 10 moved tools with their current names, descriptions and output schemas: list, show, create, update and delete SSO connection; list and unlink SSO identity; list, create and revoke SCIM token. Web and local servers follow `guardian.mcp.*`, as in Keen, and Cortex sees them through Keystone's `registerCortex()`. Proof: a test lists exactly ten tools and compares each name, description and schema with Roster's at the pinned SHA. The moved MCP tests pass through `McpTools::call(GuardianServer::class, …)` with output schemas checked, and a structured-content-equals-HTTP-payload test covers one tool per domain.
27. **README and Boost skill.** The README and `resources/boost/skills/guardian/SKILL.md` cover:
    - install, and that Guardian is inactive without Roster
    - optional SSO vendors
    - the config sections, including that the `roster/sso` web prefix is kept so IdP callback URLs keep working, and apps may change it
    - the permissions, and that existing built-in roles do not gain them automatically (`roster:sync-permissions`, grant by hand)
    - the `atrium.screens` defaults, how to move or empty them, and that a bad entry throws in local/testing and is reported and skipped in production
    - the HTTP API, MCP tools and SCIM endpoint
    - an "Upgrading from Roster" section: install order, the handover rule, `guardian:upgrade-from-roster`, renaming published `roster.sso.*` / `roster.scim.*` config and env keys to `guardian.*` (there is no automated config or env key migration, and no fallback read), the audit package-key and event-name change, the stranded-state warning, the leftover config check, and the create-migration rollback rule with `GUARDIAN_FORCE_ROLLBACK`

    Proof: a docs test asserts both files contain `guardian:upgrade-from-roster`, `atrium.screens`, `guardian.sso.manage`, `roster:sync-permissions` and `GUARDIAN_FORCE_ROLLBACK`, and the headings "Upgrading from Roster", "Permissions" and "MCP".
28. **Coexistence.** Guardian's whole suite passes with Roster at the seams branch, which still ships its own SSO and SCIM, installed as a dev dependency.
    - The base `TestCase` binds `Handover` as complete in `defineEnvironment()`, sets `roster.scim.enabled = false` and moves `roster.sso.prefix`, so Roster's live routes don't collide with Guardian's.
    - A separate `tests/Modes/HandoverPending` suite (real `Handover`) asserts that while Roster ships SSO/SCIM, Guardian loads neither its SSO web routes nor its SCIM server routes (`Route::has('guardian.sso.start')` and `Route::has('guardian.scim.users.index')` are false), registers no morph aliases, and does not run the upgrade.
    - The `Handover` and `HandoverPending` machinery and the coexistence tests are deleted in a follow-up once Roster `remove-sso-and-scim` is tagged (Out of scope).
29. **Gate.** `composer test` (analyse, lint, type coverage at 100, Pest) passes, including the `Standalone`, `WithoutAtrium`, `RenamedTables` and `HandoverPending` modes. The `standalone`, `without-atrium` and `mysql` CI jobs pass. Browser tests are not part of `composer test`: the moved `SsoBrowserTest` / `ScimBrowserTest` pass with `composer test:browser`.

## Design

**Build order for create-tasks.** Task group 1 is "wire the package into the suite" (criteria 1 to 4): the `ringside new-package` scaffold, `composer.json` with Keystone as the only suite requirement, VCS repositories, the Atrium `conflict`, `bin/composer-local.php` and the `local:*` scripts as Keen has them, the shared arch preset in `tests/Pest.php` and `tests/ArchTest.php`, CI, `CLAUDE.md` and the Ringside install. No feature group starts before it. Then, in order:

1. package shape, config, `Integration`, models, create migrations and the Standalone mode (5 to 7, 21)
2. the SSO domain with its HTTP API and MCP
3. the SCIM domain with its HTTP API and MCP (2 and 3 together cover 8 to 15 and 24 to 26)
4. Atrium UI and the without-Atrium proof (16 to 20, 30), after Roster `adopt-atrium-screen-registry` merges (D-035)
5. the transition machinery and docs, last (D-028): `Handover`, the upgrade, morph aliases, the route gate, `HandoverPending`, the MySQL lane, README and Boost skill (22, 23, 27 to 29, 31)

Until the transition group lands, the domain providers load the SSO web and SCIM server routes unconditionally, and the base `TestCase` keeps Roster's routes out of the way (criterion 28). The transition group adds the `Handover::complete()` gate.

**Layout.**

```
src/
  Guardian.php, GuardianServiceProvider.php, Facades/Guardian.php
  Support/Tables.php, Support/Integration.php
  Upgrade/RosterUpgrade.php, Upgrade/Handover.php, Upgrade/UpgradeReport.php
  Console/Commands/UpgradeFromRosterCommand.php
  Exceptions/GuardianException.php
  Http/Requests/Request.php, Mcp/Requests/Request.php, Policies/Policy.php   (Guardian bases)
  Domains/DomainServiceProvider.php, Domains/Sso/**, Domains/Scim/**
  Mcp/GuardianServer.php, Mcp/Concerns/DescribesSsoConnection.php
  Atrium/GuardianPlugin.php, Atrium/Screens.php (tab/section definitions, config parsing), Atrium/Http/Controllers/{Sso,Scim}UiController.php
resources/views/atrium/{organization-sso,organization-scim,user-sso-identities,sso-connection}.blade.php, resources/views/atrium/partials/sso-fields.blade.php
resources/boost/skills/guardian/SKILL.md
lang/en/guardian.php
database/migrations/2026_11_01_00000{0,1,2}_*.php
```

`Domains/*/Models` keep their lifecycle-event traits and get policies in `guardian.policies` (arch rules). `UpgradeReport` is a final value object in `Upgrade/`, not under `Data/`, because it is not action input. `Handover` and `Integration` are final singletons constructed with a class-exists check; tests swap them in `defineEnvironment()`.

**Boot sequence.**
- `register()`: merge config, register the package, bind `Integration`, `Handover` and `ScimContext` (scoped).
- Register `DomainServiceProvider` only when `Integration::rosterInstalled()`.
- `boot()`: always define the `guardian` rate limiter and the `about` section (inactive line, or the stranded-state line when it applies).
- When Roster is installed, also: load migrations, register policies, the MCP server, Cortex, the command, and, when `Integration::atriumInstalled()`, the Atrium plugin through Keystone's `registerAtriumPlugin()`.
- Domain providers: load API routes; register resources, permissions and the sign-in requirement.
- When `Handover::complete()`: the SSO domain loads SSO web routes (if `Sso::available()`) and the SCIM domain loads SCIM server routes (if `guardian.scim.enabled`); both register morph aliases.
- Register the membership source and audit context inside `booted()`.

**Malformed config at boot** (D-021). `Atrium\Screens` validates each `atrium.screens` entry and wraps each Atrium registration call. On a bad entry it throws when `app()->environment(['local', 'testing'])`, and otherwise calls `report()` and continues with the next entry.

**Authorization.**
- Requests and MCP requests authorize through the Guardian base's policy call. Policies answer with `Authorizer::permits()` on `guardian.*` permissions, so HTTP, MCP and Atrium make the same check (http/authorization).
- Tabs on Roster screens use `permission()`, answered by Roster's `access()` closure.
- Guardian's own screen uses `Authorizer::check()` in its `access()` closure.

**Departures from standards.**
- models/migrations: the create migrations guard `Schema::hasTable()`, because the upgrade may already have renamed Roster's tables into place; their `down()` refuses to drop tables with rows unless forced (D-031).
- architecture/dependencies: `Upgrade/` names Roster's table and class names as strings, because the upgrade path is by definition about Roster.

Both are recorded in `docs/agent/standards-feedback.md` (workspace root) by the group that introduces them.

## Out of scope

- Deleting SSO/SCIM from Roster, Roster's CHANGELOG/README upgrade notes and dropping the `roster.sso` / `roster.scim` registrations: Roster `remove-sso-and-scim`. That spec's upgrade note tells apps that installed Guardian early to run `guardian:upgrade-from-roster`.
- The `conflict` entry on Roster versions older than the `remove-sso-and-scim` release: added once that release is tagged (D-022, D-027).
- Removing `Handover`, the HandoverPending test mode and the coexistence tests: a follow-up once Roster `remove-sso-and-scim` is tagged.
- Roster's screen declarations and access closure (`roster.organization`, `roster.user`, the `aside` slot), and Roster's move of Atrium to `suggest`: Roster `adopt-atrium-screen-registry`.
- A Guardian navigation page listing connections across organizations (D-025).
- Provisioning onto a non-Roster user model (D-023), MFA, session policies and IP rules.
- Changing SSO/SCIM behaviour, payloads, MCP tool names or the default URLs (`roster/sso`, `scim/v2`).
- Creating the GitHub repository and the first tag (factory and release steps).
- Automated renaming of published `roster.sso.*` / `roster.scim.*` config and env keys, and any fallback read of them (D-030): documented in the README, with leftover values detected and reported.
- Granting the new permissions to existing built-in roles other than through the upgrade's grant merge: documented as a manual step (`roster:sync-permissions`, grant by hand).
- A reversible `down()` for the upgrade migration (criterion 22: it does nothing).
- The accepted renames listed in the Goal are not changes to preserve behaviour for; only URLs, payloads and MCP tool names are.

## Risks

- **External sequencing.** The Atrium UI group waits for Roster `adopt-atrium-screen-registry` to merge (D-035), and the `without-atrium` CI lane needs that Roster, whose `require` no longer names Atrium. Ringside's `depends on:` only names groups in this file, so the wait is a note on the group, not enforced by the factory. When that group starts, the worktree's `composer.local.json` must resolve Roster from a checkout that has both the seams and the adoption (Roster `main` once both have merged), not from the seams worktree alone.
- **Atrium `conflict` constraint.** Atrium has no tags, so `<0.1.0` is a forward guard only. The runtime `ScreenExtensions` check carries the protection until Atrium is tagged.
- **Mixed morph types.** During the coexistence window, Keen entries for Guardian's models are written under Guardian's class names, and after handover under the old Roster names (the `keepMorphAliases` write rule). Both resolve, but `subject_type` is not uniform. The audit package key (`roster` to `guardian`) and event class names also change, so history queries, filters and exports by package or event show a discontinuity.
- **Index names.** Renamed tables keep their `roster_*` index names. This is cosmetic, but a later Guardian migration that names an index must look it up rather than assume `guardian_*`.
- **Permission rows during coexistence.** `roster:sync-permissions` run during the window creates the `guardian.*` rows without granting them to existing admins. The upgrade's merge step fixes the grants at handover. Until then Guardian's screens are admin-invisible, which is acceptable because its tables are empty.
- **Stranded state.** If the app upgrades Roster before running Guardian's command, the upgrade migration is recorded as skipped and the create migrations make empty tables. SSO enforcement is then off for those organizations until the command runs (D-029). Guardian warns on `about` and through a reported exception on every boot; it does not fail closed.
- **Rollback refusal.** `migrate:rollback` across Guardian's create migrations now throws on tables with rows. Deploy scripts that roll back routinely must set `GUARDIAN_FORCE_ROLLBACK` deliberately.
- **Scope.** About 170 files move, plus a new package, an upgrade path and four test modes.

## Changelog

- D-020: criterion 1 adds the Atrium `conflict`; new criterion 30 adds the `WithoutAtrium` suite and the `without-atrium` CI lane; criterion 7 runs Standalone inside `composer test` as well as in its lane.
- D-021: criterion 16 and Design: malformed `atrium.screens` entries throw in local/testing, are reported once and skipped in production.
- D-022, D-027, D-033: runtime handover gate kept (criteria 22, 23, 28, new 31); Roster `conflict` deferred until the removal is tagged (Out of scope).
- D-023, D-032: criterion 7 makes Guardian inert without Roster; Q1 removed.
- D-024, D-036: criterion 9 allows the seams plus Roster's public domain API; Q2 removed.
- D-025: criteria 17 and 19: the `guardian.sso` tab is the home, no Guardian nav page; Q3 removed.
- D-026: criterion 6 keeps `roster/sso`, README note in 27; Q4 removed.
- D-028: transition machinery stays in this spec, as the last task group (Design build order).
- D-029: criterion 22: stranded state warns only; enforcement stays off until the command runs.
- D-030: criterion 22 and Goal: no fallback read of `roster.*` config; leftovers reported.
- D-031: criterion 21: create-migration `down()` refuses to drop tables with rows unless `guardian.migrations.force_rollback`; config key in criterion 6.
- D-034: criterion 22: SQLite in `composer test` plus a `mysql` CI lane for the rename and FK-survival cases.
- D-035: section 6 and Risks: the Atrium UI group runs after Roster `adopt-atrium-screen-registry` merges; the stand-in option is dropped.
- Review round 1 finding left open (CI resolution of Roster and Atrium branches): settled by D-035 and the build-time `composer.local.json` paths in criterion 2.
