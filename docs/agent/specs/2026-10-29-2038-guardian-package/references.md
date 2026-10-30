# References

- **Roster SSO/SCIM source (to move):** `Roster/src/Domains/Sso/` and `Roster/src/Domains/Scim/`, with routes in `routes.php`, `web.php` and `scim.php`. Also:
  - UI: `Roster/src/Atrium/Http/Controllers/SsoUiController.php`, `ScimUiController.php` and `Roster/resources/views/ui/sso/`
  - MCP: `Roster/src/Mcp/RosterServer.php` (SSO/SCIM tools) and `Roster/src/Mcp/Concerns/DescribesSsoConnection.php`
  - config: `Roster/config/roster.php` (sso :244, scim :268, policies :538-542, tables :576-580)
  - migrations: `Roster/database/migrations/2026_01_01_00000{6,7}_*`
- **Original specs:** `Roster/docs/agent/specs/2026-10-01-1251-sso` and `2026-10-01-1308-scim`, for behaviour and decisions to keep.
- **Optional vendor gating to keep:** `Roster/src/Domains/Sso/Services/Sso.php:33-41`.
- **Package skeleton precedent:** any sibling package (Keen is the smallest) for the Keystone `PackageServiceProvider`, `Package::make()`, `Support/Tables`, `ArchTest`, Boost skill and CI layout.
- **Registering into another package's screens:** Atrium spec `screen-extensions-registry`.
