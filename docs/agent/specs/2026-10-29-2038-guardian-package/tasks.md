# Tasks: Guardian package

## Group 1: Wire the package into the suite
package: Guardian
depends on: none
files: composer.json, bin/composer-local.php, testbench.yaml, phpunit.xml.dist, phpstan.neon.dist, phpstan.windows.neon, pint.json, .gitignore, .gitattributes, .editorconfig, .github/workflows/tests.yml, .github/workflows/update-changelog.yml, tests/Pest.php, tests/TestCase.php, tests/ArchTest.php, tests/Feature/PackageWiringTest.php, CLAUDE.md, AGENTS.md, ring-master.yml, docs/agent/product/roadmap.md, docs/agent/standards-feedback.md, workbench/**
criteria: 1, 2, 3, 4

- [x] 1.1 Write tests/Feature/PackageWiringTest.php asserting composer.json require/suggest (vendor wording equal to Roster's at the pinned SHA)/repositories/conflict on refactor-circus/atrium "<0.1.0", local:install and local:update equal to Keen's, bin/composer-local.php identical to Keen's, the Ring Master files and .gitignore entries, and roadmap.md done and planned items (criteria 1, 2, 4).
- [x] 1.2 Write composer.json per criterion 1 (require php ^8.5, Keen's laravel/framework constraint and keystone dev-main only; suggest Roster, Atrium, laravel/mcp and the four SSO vendors; require-dev Roster, Atrium, Keen, Ringside, Standards, the four vendors and Keen's dev tools; VCS repositories for Keystone, Roster, Atrium, Keen, Ringside and Standards; conflict refactor-circus/atrium "<0.1.0"; Keen's scripts, extra.laravel provider and Guardian alias, config).
- [x] 1.3 Copy Keen's bin/composer-local.php, testbench.yaml, phpunit.xml.dist (suites Arch, Feature, Modes), phpstan.neon.dist, phpstan.windows.neon, .gitattributes, .editorconfig, workbench and .github/workflows (tests.yml, update-changelog.yml) with names changed to Guardian, and add a `vendor/bin/ringside check` step to tests.yml.
- [x] 1.4 Write the job worktree's gitignored composer.local.json with path repositories ../Roster--2026-10-29-2038-auth-extension-seams and ../Atrium--2026-10-29-2037-screen-extensions-registry (symlink, versions aliased to dev-main) ahead of the sibling entries, and install with `COMPOSER=composer.local.json composer update`, never `composer local:install`, while those PRs are open.
- [x] 1.5 Write tests/Pest.php calling RefactorCircus\Standards\Pest\ArchPreset::register() and tests/TestCase.php on Keen's shape, and tests/ArchTest.php with the php, security and refactorCircus presets, the global bans and Keen's Keystone Arch finders and request-persist and parity rules (criterion 3).
- [x] 1.6 Run `vendor/bin/ringside install` then `vendor/bin/ringside sync` so ring-master.yml (Keen's commands block), pint.json, the .gitignore block, docs/agent/ and docs/agent/standards.index.yml exist, and write CLAUDE.md on Keen's shape with Guardian's "Role in the suite" section and docs/agent/product/roadmap.md (SSO and SCIM done; MFA, session policies and IP rules planned).
- [x] 1.7 Run vendor/bin/pest; all green.

## Group 2: Package shape, config, integration, models and create migrations
package: Guardian
depends on: 1
files: src/Guardian.php, src/GuardianServiceProvider.php, src/Facades/Guardian.php, src/Support/Tables.php, src/Support/Integration.php, src/Exceptions/GuardianException.php, src/Http/Requests/Request.php, src/Mcp/Requests/Request.php, src/Policies/Policy.php, src/Mcp/GuardianServer.php, src/Domains/DomainServiceProvider.php, src/Domains/Sso/SsoServiceProvider.php, src/Domains/Scim/ScimServiceProvider.php, src/Domains/Sso/Models/**, src/Domains/Sso/Events/**, src/Domains/Sso/Policies/**, src/Domains/Scim/Models/**, src/Domains/Scim/Events/**, src/Domains/Scim/Policies/**, database/factories/**, database/migrations/2026_11_01_000001_create_guardian_sso_tables.php, database/migrations/2026_11_01_000002_create_guardian_scim_tables.php, config/guardian.php, lang/en/guardian.php, tests/TestCase.php, tests/StandaloneTestCase.php, tests/RenamedTablesTestCase.php, tests/Pest.php, tests/ArchTest.php, tests/Feature/PackageShapeTest.php, tests/Feature/ConfigTest.php, tests/Feature/MigrationsTest.php, tests/Modes/Standalone/**, tests/Modes/RenamedTables/**, phpunit.xml.dist, .github/workflows/tests.yml, docs/agent/standards-feedback.md
criteria: 5, 6, 7, 21

- [ ] 2.1 Write tests for the package registration and each model's getTable(), the config keys and literal defaults (sso and scim equal to Roster's at the pinned SHA, no roster.* key, 429 on the 121st request), the Standalone and "needs a newer" cases, the RenamedTables mode, the create migrations' indexes and the down() refusal with and without guardian.migrations.force_rollback (criteria 5, 6, 7, 21).
- [ ] 2.2 Write config/guardian.php with a banner per section and every key and default of criterion 6, including migrations.force_rollback, atrium.screens and atrium.organization_route.
- [ ] 2.3 Implement GuardianServiceProvider, Guardian, the facade, Support\Tables, GuardianException, the guardian rate limiter and the about section, and the Guardian request, MCP request and policy bases.
- [ ] 2.4 Implement Support\Integration (class-exists check injected; rosterInstalled() with the seam method and class checks, atriumInstalled() on ScreenExtensions) and gate DomainServiceProvider, migrations, policies, MCP server, Cortex and the Atrium plugin hook behind it, with the inactive and "needs a newer" about lines.
- [ ] 2.5 Move the five models with their lifecycle events, policies and factories into Domains/Sso and Domains/Scim, with getTable() from Support\Tables and guardian.policies registered through registerPolicies().
- [ ] 2.6 Write the two create migrations with Roster's columns, unique keys and indexes, Roster Tables/UserKey references, a hasTable() guard per create, and a down() that throws GuardianException naming tables with rows unless guardian.migrations.force_rollback, and record the models/migrations departure in docs/agent/standards-feedback.md.
- [ ] 2.7 Add tests/Modes/Standalone with StandaloneTestCase, tests/Modes/RenamedTables with RenamedTablesTestCase, the Roster-reference arch rule, and the `standalone` CI job in .github/workflows/tests.yml that removes Roster, Atrium and Keen and runs tests/Modes/Standalone.
- [ ] 2.8 Run vendor/bin/pest; all green.

## Group 3: SSO domain with HTTP API and MCP
package: Guardian
depends on: 2
files: src/Domains/Sso/**, src/Domains/DomainServiceProvider.php, src/Mcp/GuardianServer.php, src/Mcp/Concerns/DescribesSsoConnection.php, src/GuardianServiceProvider.php, config/guardian.php, lang/en/guardian.php, tests/TestCase.php, tests/ArchTest.php, tests/moved-tests.json, tests/Feature/Sso/**, tests/Feature/Http/SsoHttpTest.php, tests/Feature/Authorization/**, tests/Feature/Mcp/**, tests/Feature/MovedTestsManifestTest.php, tests/Feature/RosterBoundaryTest.php, tests/Modes/SsoUnavailable/**
criteria: 8, 9, 10, 12, 13, 15, 24, 25, 26

- [ ] 3.1 Copy Roster's SSO tests (tests/Feature/Sso, Http/SsoHttpTest, Modes/SsoUnavailable, the SSO cases of Authorization/*, the SSO MCP tests) from a pinned Roster SHA with only the criterion 8 renames, record the SHA and the moved files and test names in tests/moved-tests.json, and write the manifest-count, Roster-boundary arch and @internal grep, permission-registration and SSO audit tests (criteria 8, 9, 10, 15).
- [ ] 3.2 Move Roster's Domains/Sso (actions, data, events, exceptions, HTTP controllers, requests, middleware, MCP requests and tools, resources, services, Concerns, Support\NotSsoEnforced, routes) and Mcp\Concerns\DescribesSsoConnection into Guardian with guardian namespaces, guardian.sso config reads and Guardian's request and policy bases.
- [ ] 3.3 Register the guardian.sso resource and the guardian.sso.view and guardian.sso.manage permissions from SsoServiceProvider, and rename every roster.sso permission string to guardian.sso.
- [ ] 3.4 Implement SsoSignInRequirement registered with Roster::requireSignIn(), switch NotSsoEnforced to Roster::externalSignInFor() and SsoLoginAction to Roster::provisionedStatus() (criteria 12, 13).
- [ ] 3.5 Load the SSO API routes through loadApiRoutesFrom() under guardian.routes with the guardian.* names of criterion 25, and the SSO web routes named guardian.sso.discover|start|callback|metadata|link under guardian.sso.prefix.
- [ ] 3.6 Register the seven SSO tools on GuardianServer with Roster's names, descriptions and output schemas, and wire guardian.mcp.* web and local servers and Cortex as in Keen.
- [ ] 3.7 Add the SSO parity exceptions (SsoLoginAction and LinkSsoIdentityAction for http and mcp with Roster's reasons) and temporary "Atrium UI group" UI exceptions for the SSO actions whose UI lands in group 5.
- [ ] 3.8 Run vendor/bin/pest; all green.

## Group 4: SCIM domain with HTTP API and MCP
package: Guardian
depends on: 3
files: src/Domains/Scim/**, src/Domains/DomainServiceProvider.php, src/Mcp/GuardianServer.php, src/GuardianServiceProvider.php, config/guardian.php, lang/en/guardian.php, tests/TestCase.php, tests/ArchTest.php, tests/moved-tests.json, tests/Feature/Scim/**, tests/Feature/Http/ScimTokensHttpTest.php, tests/Feature/Authorization/**, tests/Feature/Mcp/**
criteria: 8, 10, 11, 13, 14, 15, 24, 25, 26

- [ ] 4.1 Copy Roster's SCIM tests (tests/Feature/Scim, Http/ScimTokensHttpTest, the SCIM cases of Authorization/*, the SCIM MCP tests) from the pinned SHA with only the criterion 8 renames, add them to tests/moved-tests.json, and write the membership-source, SCIM audit-context, ten-tool listing and structured-content-equals-HTTP tests (criteria 8, 11, 14, 26).
- [ ] 4.2 Move Roster's Domains/Scim (actions, data, events, exceptions, HTTP, MCP, resources, services, ScimContext, SCIM 2.0 server, routes) into Guardian with guardian namespaces, guardian.scim config reads and ScimContext bound scoped.
- [ ] 4.3 Register the guardian.scim resource and the guardian.scim.manage permission, rename every roster.scim permission string, and make ScimUsers use Roster::provisionedStatus() and join() with 'scim'.
- [ ] 4.4 Register MembershipSources 'scim' ("SCIM") inside booted() only when MembershipSources::has('scim') is false, and the AuditHooks::context() closure inside booted() when AuditTrail::available(), with the package-key filter and try/catch report.
- [ ] 4.5 Load the SCIM token API routes under guardian.routes with the guardian.* names and the SCIM server routes named guardian.scim.* under guardian.scim.prefix with throttle:guardian, when guardian.scim.enabled.
- [ ] 4.6 Register the three SCIM tools on GuardianServer with Roster's names, descriptions and output schemas, and add temporary "Atrium UI group" UI parity exceptions for the SCIM actions whose UI lands in group 5.
- [ ] 4.7 Run vendor/bin/pest; all green.

## Group 5: Atrium UI and the without-Atrium proof
package: Guardian
depends on: 4
files: src/Atrium/**, src/GuardianServiceProvider.php, resources/views/atrium/**, lang/en/guardian.php, config/guardian.php, tests/TestCase.php, tests/WithoutAtriumTestCase.php, tests/Pest.php, tests/ArchTest.php, tests/moved-tests.json, tests/Feature/Ui/**, tests/Feature/Atrium/**, tests/Feature/StylesTest.php, tests/Modes/WithoutAtrium/**, tests/Browser/**, phpunit.xml.dist, composer.json, .github/workflows/tests.yml
criteria: 8, 16, 17, 18, 19, 20, 24, 30

> Starts only after Roster 2026-10-29-2038-adopt-atrium-screen-registry has merged (D-035); before starting, point the worktree's composer.local.json Roster path at a checkout holding both the seams and the adoption.

- [ ] 5.1 Copy Roster's Ui/SsoPagesTest, Ui/ScimPagesTest and the SSO/SCIM browser tests from the pinned SHA with only the criterion 8 renames, and write the placement-by-config (including throw in testing, report-and-skip in production), tab, section, own-screen slot, back-link, styles and WithoutAtrium tests (criteria 8, 16 to 20, 30).
- [ ] 5.2 Implement src/Atrium/Screens.php parsing atrium.screens into ScreenExtensions::tab() and section() calls, throwing InvalidArgumentException in local/testing and reporting once and skipping in other environments, and GuardianPlugin (key guardian, no nav items) registering them only when atriumInstalled() and rosterInstalled().
- [ ] 5.3 Define the guardian.sso and guardian.scim TabDefinitions and the guardian.sso-identities aside SectionDefinition with the labels, icons, views, permissions, one-exists authorize and resolvers of criteria 17 and 18.
- [ ] 5.4 Implement SsoUiController and ScimUiController with the seven atrium.guardian.* routes of criterion 19, and the guardian.sso-connection show page declaring Screen::standard('guardian.sso-connection', 'detail') with the Authorizer access closure, guardian.sso.view check before resolve() and the guardian.atrium.organization_route back link.
- [ ] 5.5 Write resources/views/atrium (organization-sso, organization-scim, user-sso-identities, sso-connection, partials/sso-fields) reproducing Roster's markup with x-atrium components only, and move the strings into lang/en/guardian.php.
- [ ] 5.6 Add tests/Modes/WithoutAtrium with WithoutAtriumTestCase, the src/Atrium-only Atrium-reference arch rule, the `without-atrium` CI job that removes Atrium and its dependents and runs tests/Modes/WithoutAtrium, and the test:browser script and browser workflow as in Keen.
- [ ] 5.7 Remove the temporary "Atrium UI group" parity exceptions so only SsoLoginAction and LinkSsoIdentityAction remain.
- [ ] 5.8 Run vendor/bin/pest; all green.

## Group 6: Transition machinery and docs
package: Guardian
depends on: 5
files: src/Upgrade/**, src/Console/Commands/UpgradeFromRosterCommand.php, src/GuardianServiceProvider.php, src/Domains/Sso/SsoServiceProvider.php, src/Domains/Scim/ScimServiceProvider.php, src/Domains/Sso/Models/**, src/Domains/Scim/Models/**, database/migrations/2026_11_01_000000_upgrade_guardian_from_roster.php, tests/TestCase.php, tests/HandoverPendingTestCase.php, tests/Pest.php, tests/Feature/Upgrade/**, tests/Feature/MorphAliasesTest.php, tests/Feature/HandoverRoutesTest.php, tests/Feature/DocsTest.php, tests/Modes/HandoverPending/**, phpunit.xml.dist, .github/workflows/tests.yml, README.md, resources/boost/skills/guardian/SKILL.md, CHANGELOG.md, docs/agent/standards-feedback.md
criteria: 22, 23, 27, 28, 29, 31

- [ ] 6.1 Write the upgrade tests (every case listed in criterion 22, the rename and FK-survival cases tagged ->group('upgrade-mysql')), the morph-alias, handover route-gate, HandoverPending and docs tests (criteria 22, 23, 27, 28, 31).
- [ ] 6.2 Implement Upgrade\Handover (injected class-exists check on the literal 'RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel') and bind it complete in the base TestCase's defineEnvironment() alongside roster.scim.enabled = false and a moved roster.sso.prefix.
- [ ] 6.3 Implement Upgrade\RosterUpgrade::run() and UpgradeReport with the plan-then-act table passes, the transactional grant merge with Permissions::forget(), the conflict report and the leftover roster.sso/roster.scim config listing, and record the architecture/dependencies departure in docs/agent/standards-feedback.md.
- [ ] 6.4 Add the 2026_11_01_000000 upgrade migration (no-op down()) and the guardian:upgrade-from-roster command printing the report, both gated on Handover::complete() with the pending message.
- [ ] 6.5 Implement the stranded-state check: the about line naming the tables and command, one reported GuardianException per boot, and no fail-closed enforcement.
- [ ] 6.6 Gate the SSO web and SCIM server routes and keepMorphAliases() for the five old Roster class names on Handover::complete() in the domain providers and models.
- [ ] 6.7 Add tests/Modes/HandoverPending with HandoverPendingTestCase (real Handover) and the `mysql` CI job running vendor/bin/pest --group=upgrade-mysql against a MySQL 8 service.
- [ ] 6.8 Write README.md and resources/boost/skills/guardian/SKILL.md covering every item of criterion 27, including "Upgrading from Roster", "Permissions" and "MCP" headings and GUARDIAN_FORCE_ROLLBACK, plus a CHANGELOG.md entry.
- [ ] 6.9 Run composer test and vendor/bin/pest; all green.
