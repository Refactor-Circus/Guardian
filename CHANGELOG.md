# Release Notes

## [Unreleased](https://github.com/refactor-circus/guardian/compare/v0.1.0...1.x)

### Added

- SSO (OIDC, SAML, Microsoft Entra ID) and SCIM 2.0 provisioning, moved from refactor-circus/roster with their behaviour unchanged: HTTP API, ten MCP tools, `guardian.*` permissions and Keen auditing as package `guardian`.
- `php artisan guardian:upgrade-from-roster` and the `2026_11_01_000000` upgrade migration: rename Roster's SSO and SCIM tables and permissions to Guardian's, keeping rows and grants, once Roster has handed SSO and SCIM over. Leftover `roster.sso.*` / `roster.scim.*` config is listed.
- Stranded-data warning on `php artisan about` and a reported exception per boot.
- Create-migration rollback refuses to drop tables with rows unless `GUARDIAN_FORCE_ROLLBACK=true`.


## [v0.1.0](https://github.com/refactor-circus/guardian/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
