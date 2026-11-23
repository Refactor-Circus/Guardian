# Build: Guardian v2: security platform

## Group 1: Remove the Atrium UI

- Setup: `composer update` on the `^0.1` suite tags (no lock file in the worktree). Baseline 546 tests green.
- Tests first: `RemovedSurfacesTest` (no `atrium` in `src/`/`config/`, no `atrium.guardian.*` route, no `guardian.ui`/`guardian.atrium` config, no `guardian` view namespace, the Atrium-only files deleted), `ComposerTest` (no `refactor-circus/atrium` in require, require-dev, suggest, conflict or repositories; no `test:without-atrium`/`test:browser`; no browser plugin), ArchTest `nothing uses Atrium` with no `ignoring`.
- Deleted `src/Atrium`, `resources/views`, the Atrium/Ui/Browser/WithoutAtrium tests, `StylesTest`, `package.json`/`package-lock.json` (only Playwright used them), and the browser-tests workflow. The lang file keeps only the keys SSO/SCIM code uses (the `sso_failed_*` family is looked up dynamically).
- `Integration::atriumInstalled()` is gone (it named Atrium's class); `GuardianServiceProvider` no longer loads views or registers the plugin; `config/guardian.php` lost `ui` and `atrium`.
- `moved-tests.json` moves the four moved UI/browser files to a `removed` block with the reason; `MovedTestsManifestTest` checks they stay deleted.
- `PackageWiringTest`, `PackageShapeTest`, `ConfigTest`, `DocsTest`, the Standalone mode and CI (`without-atrium` job dropped, standalone no longer removes Atrium) were updated. README, SKILL, CLAUDE.md and AGENTS.md lost their Atrium placement sections (the full v2 docs come in the docs group).
- Gates: pest 477 passed; `composer analyse` 0 errors; `composer lint:check` passed.
