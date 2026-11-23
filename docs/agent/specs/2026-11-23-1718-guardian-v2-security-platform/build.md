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
