# Build: Guardian v2: security platform

## Group 1: Remove the Atrium UI

- Setup: `composer update` on the `^0.1` suite tags (no lock file in the worktree). Baseline 546 tests green.
- Tests first: `RemovedSurfacesTest` (no `atrium` in `src/`/`config/`, no `atrium.guardian.*` route, no `guardian.ui`/`guardian.atrium` config, no `guardian` view namespace, the Atrium-only files deleted), `ComposerTest` (no `refactor-circus/atrium` in require, require-dev, suggest, conflict or repositories; no `test:without-atrium`/`test:browser`; no browser plugin), ArchTest `nothing uses Atrium` with no `ignoring`.
- Deleted `src/Atrium`, `resources/views`, the Atrium/Ui/Browser/WithoutAtrium tests, `StylesTest`, `package.json`/`package-lock.json` (only Playwright used them), and the browser-tests workflow. The lang file keeps only the keys SSO/SCIM code uses (the `sso_failed_*` family is looked up dynamically).
- `Integration::atriumInstalled()` is gone (it named Atrium's class); `GuardianServiceProvider` no longer loads views or registers the plugin; `config/guardian.php` lost `ui` and `atrium`.
- `moved-tests.json` moves the four moved UI/browser files to a `removed` block with the reason; `MovedTestsManifestTest` checks they stay deleted.
- `PackageWiringTest`, `PackageShapeTest`, `ConfigTest`, `DocsTest`, the Standalone mode and CI (`without-atrium` job dropped, standalone no longer removes Atrium) were updated. README, SKILL, CLAUDE.md and AGENTS.md lost their Atrium placement sections (the full v2 docs come in the docs group).
- Gates: pest 477 passed; `composer analyse` 0 errors; `composer lint:check` passed.

## Group 2: v2 suite constraints, SSO/SCIM on Keystone's contract

- Vendor: Keystone v2 (`Keystone--2026-11-23-0943-drop-the-atrium-plugin-hook` @ efd6331), Keen v2 (`Keen--2026-11-23-0943-keen-v2-api-and-mcp-only` @ 3c34fee) and Roster v2 (`Roster--2026-11-23-0943-roster-v2-api-and-mcp-only` @ 0916ab1, latest commit while that job finishes) exported into `vendor/refactor-circus/*` with `git archive HEAD` (the scratchpad `vendorv2.sh`), then `composer dump-autoload`. Neither job is on `dev-main` yet (task 2.2's stop condition); the caller approved building against the job worktrees. No `composer update` after vendoring.
- `composer.json` edited by hand: Keystone `^2.0`, Roster/Keen `^2.0` (dev), `pragmarx/google2fa` (require) and Scramble, Sanctum, Passport, WebAuthn (dev) declared but not installed yet (D-029), the `2.0.x-dev` branch alias, the new description and suggestions.
- Tests first: `ComposerTest` (criterion 1), `RosterBoundaryTest` with the allow-list in `tests/Fixtures/roster-references.php` (no Role/Permission domains), Arch (new domains Roster-free; only Sso, Scim, Upgrade, Integration and the request bases name Roster; only SSO/SCIM wait for Roster), `StandaloneTest` (boots, migrates, only SSO/SCIM skipped, `about` lines), Authorization tests on `FakePermissions`/`FakeRoles`, `JoinsOrganizationsTest` through `Roles::of()`.
- `Support\Authorization` (D-026, D-028) replaces Roster's `Authorizer` in `Policies\Policy`, `Http\Requests\Request` and `Mcp\Requests\Request`; the scope is any model (a Keystone `Scope`, or a record's `team`/`organization`). SSO/SCIM abilities are defined in Keystone `Abilities` (`grantTo admin`), once per app. `JoinsOrganizations` assigns `roster.roles.default_member` through Keystone `Roles`; `RosterUpgrade` calls `Permissions::forget()`; `Integration` checks only `MembershipSources`.
- Roster-optional boot (D-027): `DomainServiceProvider` always registers and gates only SSO/SCIM; SSO/SCIM/upgrade migrations moved to `database/migrations/roster`; new `guardian.authorization.enabled` config (banner).
- Upgrade grant tests now run on Roster v0.1's role tables from `tests/Fixtures/roster-roles` (D-030).
- Gates: pest 486 passed; `composer analyse` 0 errors; `composer lint:check` passed; type coverage 100%.

## Group 3: RBAC tables, models and built-in roles

- Tests first: `tests/Feature/Role/RoleTablesTest.php` (columns, indexes, configured names, `user_id` by `guardian.users.key_type`, skip-if-exists, seeded built-ins, relations, owner morph, `Support\Users`), `SyncPermissionsTest.php` (Guardian's eleven abilities and criterion 8's admin grants, a system permission per Keystone ability, grants only on creation, edits kept, unknown slugs ignored, idempotent, `guardian.roles.built_in`, the command), Standalone migrate seeds the role tables without Roster.
- `database/migrations/2026_11_24_000001_create_guardian_role_tables.php` (Roster-free, so in `database/migrations`): four tables named through `Tables`, short explicit index names (MySQL's 64-character limit), sync at the end, guarded `down()` (D-031).
- `Support\UserKey`, `Support\Users` (model from `guardian.users.model` or the auth provider; `find`, `findByEmail` case-insensitive, `emailVerified`), the table keys.
- `Domains\Role` (`RoleModel` with `GLOBAL`/`ANY` and an owner morph, `RoleAssignmentModel` with a scope morph, `BuiltInRoles`, `GuardianAbilities`, `RoleServiceProvider`) and `Domains\Permission` (`PermissionModel`, `guardian:sync-permissions`, `PermissionServiceProvider`), each model with its ten lifecycle events and a minimal policy.
- Config: `users` and `roles` sections with banners, the four table keys, the three policies; README/SKILL now name `guardian:sync-permissions`.
- Gates: pest 514 passed; `composer analyse` 0 errors; `composer lint:check` passed; type coverage 100%.

## Group 4: Guardian as Keystone's role store

- Tests first: `tests/Feature/Permission/GuardianPermissionsTest.php` (guest, super role, configured super-admin verified/unverified, global/scope/parent-scope grants on a Roster-free fixture `Scope` (`tests/Fixtures/Scopes/FolderScope.php`), a non-`Scope` model, `grantsByItself`, unknown ability, `scopesWith`, `abilities`, `forget`, the per-request memo, the cache with a TTL and its forgetting on writes), `tests/Feature/Role/GuardianRolesTest.php` (criterion 12), `GateTest.php` (criterion 14), `BindingTest.php` (criterion 9, on and off), and Standalone binds Guardian without Roster.
- `Domains\Permission\Services\GuardianPermissions` (grants loaded once per request or from the cache, answered in memory over `ScopeChain`) and `PermissionCache` (ported from Roster v0.1, versioned keys under `guardian.permission_cache`); `Domains\Role\Services\GuardianRoles`.
- `GuardianServiceProvider` binds both `scoped()` behind `guardian.authorization.bind` and registers the `Gate::before`; `PermissionServiceProvider` forgets on role, assignment and permission model events (D-032).
- Config: `authorization.bind`, `super_admins`, `permission_cache` with banners.
- Tests now run on Guardian's own roles: `TestCase` no longer binds Keystone's fakes, `grant()` assigns through the bound `Roles`; one authorization test still binds `FakePermissions` to prove the contract seam.
- Gates: pest 545 passed (544 + the Standalone role-store check); `composer analyse` 0 errors; `composer lint:check` passed; type coverage 100%.
