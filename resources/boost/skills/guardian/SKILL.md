---
name: guardian
description: >
  Single sign-on (OIDC, SAML, Microsoft Entra ID) and SCIM 2.0 provisioning for Roster organizations with
  refactor-circus/guardian: config, permissions, Atrium screen placement, HTTP API, MCP tools, and the upgrade from Roster.
license: MIT
metadata:
  author: Jay Fletcher
---

# Guardian

Use this skill when a Laravel application uses `refactor-circus/guardian`, the auth and security package of the Refactor Circus suite. It took SSO and SCIM over from `refactor-circus/roster`.

## Install

- `composer require refactor-circus/roster refactor-circus/guardian`, then `php artisan migrate`.
- Guardian requires only refactor-circus/keystone. Without Roster it is inactive: `php artisan about` says "Inactive: install refactor-circus/roster" and nothing else registers.
- Optional SSO vendors: `laravel/socialite`, plus `firebase/php-jwt` (OIDC), `socialiteproviders/saml2` (SAML) or `socialiteproviders/microsoft-azure` (Entra ID). Without them the sign-in routes are not registered.

## Config (`config/guardian.php`)

- `sso.prefix` defaults to `roster/sso`, kept from Roster so identity providers' callback URLs keep working. Apps may change it, then update each identity provider.
- `scim.enabled`, `scim.prefix` (`scim/v2`), `scim.middleware` (`throttle:guardian`), page sizes and Bulk limits.
- `routes.enabled` (off by default) mounts the JSON API under `/guardian`.
- `migrations.force_rollback` (`GUARDIAN_FORCE_ROLLBACK`): rolling back the create migrations refuses to drop tables with rows unless it is true.

## Permissions

- `guardian.sso.view`, `guardian.sso.manage`, `guardian.scim.manage`, registered on Roster's catalogue and granted to the built-in `admin` role.
- Existing built-in roles do not gain them automatically: run `php artisan roster:sync-permissions`, then grant them by hand. The upgrade from Roster moves the old `roster.*` grants.
- Check them with Roster's `Authorizer::check($user, 'guardian.sso.manage', $organization)`.

## Atrium screens

`atrium.screens` places Guardian's tabs and sections on other packages' screens:

```php
'screens' => [
    'roster.organization' => ['guardian.sso', 'guardian.scim'],
    'roster.user' => ['aside' => 'guardian.sso-identities'],
],
```

- A plain value is a tab key; a `slot => key` entry is a section in that slot. Move an entry to another screen key, or empty the list to add nothing.
- A bad entry throws `InvalidArgumentException` at boot in `local` / `testing`; elsewhere it is reported once and skipped.

## HTTP API, SCIM endpoint and MCP

- API routes: `guardian.organizations.sso-connections.*`, `guardian.sso-connections.*`, `guardian.users.sso-identities.index`, `guardian.sso-identities.destroy`, `guardian.organizations.scim-tokens.*`, `guardian.scim-tokens.destroy`.
- SCIM 2.0 server per organization at `/scim/v2/{organization}` (`guardian.scim.*`), authenticated by the organization's SCIM tokens.

### MCP

- `GuardianServer` has ten tools: list, show, create, update and delete SSO connection; list and unlink SSO identity; list, create and revoke SCIM token. Enable `mcp.web` or `mcp.local` in config.

## Upgrading from Roster

- Install Guardian, then upgrade Roster to the release without SSO and SCIM. Until Roster lets go, Guardian loads no sign-in or SCIM server routes and the upgrade changes nothing.
- `php artisan guardian:upgrade-from-roster` renames the `roster_*` SSO and SCIM tables to `guardian_*` and the `roster.sso.*` / `roster.scim.*` permissions to `guardian.*`, keeping grants. It is idempotent and never deletes rows.
- Rename published `roster.sso.*` / `roster.scim.*` config and env keys to `guardian.*` by hand: Guardian never reads the old keys. The command lists leftovers.
- Audit entries move from package `roster` to `guardian`, with new event class names.
- If `php artisan about` reports stranded tables, run the command: SSO enforcement is off for those connections until then.
- Rolling back the create migrations over rows needs `GUARDIAN_FORCE_ROLLBACK=true`.
