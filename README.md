<div align="center">
    <h1>Guardian</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/refactor-circus/guardian"><img src="https://img.shields.io/packagist/v/refactor-circus/guardian.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/refactor-circus/guardian"><img src="https://img.shields.io/packagist/php-v/refactor-circus/guardian.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/refactor-circus/guardian"><img src="https://badge.laravel.cloud/badge/refactor-circus/guardian?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/refactor-circus/guardian/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/refactor-circus/guardian/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/refactor-circus/guardian"><img src="https://img.shields.io/packagist/dt/refactor-circus/guardian.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Auth and security for the Refactor Circus suite: single sign-on (OIDC, SAML, Microsoft Entra ID) and SCIM 2.0 provisioning for [refactor-circus/roster](https://github.com/refactor-circus/roster)'s organizations, moved out of Roster with their behaviour unchanged but for one hardening: OIDC sign-in needs a verified email (below).

## Installation

Guardian requires only refactor-circus/keystone. It works on Roster's users and organizations, so install Roster too:

```bash
composer require refactor-circus/roster refactor-circus/guardian
php artisan migrate
```

Without Roster, Guardian is inactive: it boots, `php artisan about` shows "Inactive: install refactor-circus/roster" (or "Inactive: needs a newer refactor-circus/roster" for a Roster without the sign-in and provisioning seams), and it registers no routes, migrations, tools, commands or screens.

With [refactor-circus/atrium](https://github.com/refactor-circus/atrium) installed, Guardian adds its tabs and sections to Roster's Atrium screens. Without Atrium everything else works.

You may publish the config, migrations and translations:

```bash
php artisan vendor:publish --tag="guardian-config"
php artisan vendor:publish --tag="guardian-migrations"
php artisan vendor:publish --tag="guardian-lang"
```

### Optional SSO vendors

Single sign-on needs `laravel/socialite`, plus one package per protocol you use:

| Protocol | Package |
|---|---|
| OIDC | `firebase/php-jwt` |
| SAML | `socialiteproviders/saml2` |
| Microsoft Entra ID | `socialiteproviders/microsoft-azure` |

Without them the sign-in routes are not registered; connections can still be managed.

## Configuration

`config/guardian.php` has a section per feature:

- `sso`: `redirect` (where a sign-in lands), `discovery_cache_seconds`, and `prefix` / `middleware` for the sign-in routes. The prefix stays `roster/sso`, Roster's, so the callback URLs registered at your identity providers keep working. You may change it; then update the callback URL at each identity provider.
- `scim`: `enabled`, `prefix` (`scim/v2`), `middleware` (with `throttle:guardian`), page sizes, Bulk limits, and `audit_context_packages`, the packages whose audit entries record the SCIM token a change came through.
- `routes`: the JSON API, off by default (`enabled => false`), under `prefix` `guardian`.
- `rate_limit.per_minute`: the `guardian` limiter, 120 a minute per user (or IP for guests); null turns it off.
- `audit.enabled`: with refactor-circus/keen installed, Guardian's changes are recorded as package `guardian`.
- `policies`, `tables`, `mcp`, `cortex`: as in every suite package.
- `migrations.force_rollback`: see [Rolling back](#rolling-back).
- `atrium.screens` and `atrium.organization_route`: see [Atrium screens](#atrium-screens).

## Permissions

Guardian registers three permissions on Roster's catalogue, granted to the built-in `admin` role:

| Permission | Allows |
|---|---|
| `guardian.sso.view` | Viewing an organization's single sign-on connections |
| `guardian.sso.manage` | Configuring single sign-on connections |
| `guardian.scim.manage` | Issuing and revoking SCIM provisioning tokens |

New installs get them with the built-in roles. On an existing install, built-in roles do not gain them automatically: run `php artisan roster:sync-permissions` to create them, then grant them to the roles that need them by hand. The upgrade from Roster moves the grants of Roster's old `roster.sso.*` / `roster.scim.*` permissions for you.

## Atrium screens

Where Guardian's tabs and sections go is config, in `atrium.screens`:

```php
'screens' => [
    'roster.organization' => ['guardian.sso', 'guardian.scim'],
    'roster.user' => ['aside' => 'guardian.sso-identities'],
],
```

A plain value is a tab (`guardian.sso`, `guardian.scim`); a `slot => key` entry is a section in that slot (`guardian.sso-identities`). Move an entry under another screen key to place it there, or empty the list to add nothing. Each tab and section still checks its permission. The `guardian.sso` tab is where an organization's connections are managed; each connection's page links back to it through `atrium.organization_route`.

A bad entry (an unknown key, a target that is not a string, or an entry Atrium rejects) throws an `InvalidArgumentException` at boot in the `local` and `testing` environments. In any other environment it is reported once, skipped, and the rest registers.

## HTTP API

With `routes.enabled` on (add authentication middleware first), under `/guardian`:

| Method | URI | Name |
|---|---|---|
| GET, POST | `organizations/{organization}/sso-connections` | `guardian.organizations.sso-connections.index`, `.store` |
| GET, PATCH, DELETE | `sso-connections/{connection}` | `guardian.sso-connections.show`, `.update`, `.destroy` |
| GET | `users/{user}/sso-identities` | `guardian.users.sso-identities.index` |
| DELETE | `sso-identities/{identity}` | `guardian.sso-identities.destroy` |
| GET, POST | `organizations/{organization}/scim-tokens` | `guardian.organizations.scim-tokens.index`, `.store` |
| DELETE | `scim-tokens/{token}` | `guardian.scim-tokens.destroy` |

The sign-in routes (`guardian.sso.discover`, `start`, `callback`, `metadata`, `link`) live under `sso.prefix`.

**Changed from Roster: OIDC needs a verified email.** An OIDC sign-in links an existing account by email, or creates one just in time, only when the id_token's `email_verified` claim is `true`. A false, missing or non-boolean claim refuses the sign-in ("Your identity provider has not verified this email address."), and the failed sign-in is recorded with the reason `unverified_email`. Roster trusted the email alone. Identities already linked keep signing in, and SAML and Microsoft Entra ID are unchanged. If your provider omits the claim, turn it on there (most send it with the `email` scope), or have users link their identity from their account.

## SCIM

Each organization has a SCIM 2.0 server at `/scim/v2/{organization}` (`Users`, `Groups`, `Bulk`, `ServiceProviderConfig`, `ResourceTypes`, `Schemas`; routes `guardian.scim.*`). Requests need one of the organization's SCIM tokens, issued in Atrium, through the API or MCP, and shown once. Members SCIM provisions join with the membership source `scim`.

## MCP

`GuardianServer` offers ten tools: `list-sso-connections-tool`, `show-sso-connection-tool`, `create-sso-connection-tool`, `update-sso-connection-tool`, `delete-sso-connection-tool`, `list-sso-identities-tool`, `unlink-sso-identity-tool`, `list-scim-tokens-tool`, `create-scim-token-tool` and `revoke-scim-token-tool`. Turn on the web endpoint (`mcp.web`, at `mcp/guardian`) or the local server (`mcp.local`, `php artisan mcp:start guardian`) in config; both are off as shipped. Each tool checks the same permissions as the API. With refactor-circus/cortex installed, Cortex agents see them too.

## Upgrading from Roster

SSO and SCIM used to ship with Roster. Guardian takes them over; your connections, linked identities, tokens and role grants come with them.

1. **Install order.** Install Guardian, then upgrade Roster to the release that removes SSO and SCIM. Guardian can be installed first: while Roster still ships SSO and SCIM, Guardian serves neither the sign-in routes nor the SCIM server routes, so it never shadows Roster's identical URLs. Upgrading Roster first leaves nothing to enforce single sign-on: SSO enforcement fails open until Guardian is installed after Roster's removal and the upgrade has run: enforced domains can sign in with a password in between.
2. **The handover rule.** Guardian moves the data only once Roster no longer declares SSO and SCIM. Until then the upgrade changes nothing and says "Roster still owns SSO and SCIM; run `php artisan guardian:upgrade-from-roster` after upgrading Roster."
3. **Run the upgrade.** `php artisan migrate` runs Guardian's upgrade migration. If Roster was still on the old release then, the migration is recorded but skipped; run `php artisan guardian:upgrade-from-roster` once Roster is upgraded. The command is safe to run again and from a deploy script, never deletes a table that holds rows, and prints what it did:
   - renames `roster_sso_connections`, `roster_sso_identities`, `roster_scim_tokens`, `roster_scim_users` and `roster_scim_groups` to their `guardian_*` names (dropping an empty `guardian_*` table in the way first). Where a `guardian_*` table already holds rows, it changes neither table and tells you.
   - renames the permissions `roster.sso.view`, `roster.sso.manage` and `roster.scim.manage` to `guardian.*`, keeping every role's grant.
   - lists leftover config (below).
4. **Config and env keys.** Rename published `roster.sso.*` / `roster.scim.*` config keys and their env variables to `guardian.sso.*` / `guardian.scim.*` in `config/guardian.php`. There is no automated config or env key migration, and Guardian never reads the old keys as a fallback. The upgrade lists every `roster.sso.*` / `roster.scim.*` value still set that differs from Guardian's, with the `guardian.*` key to move it to.
5. **Other renames.** Route names (`roster.sso.*` to `guardian.sso.*`, `roster.scim.*` to `guardian.scim.*`, the API's `roster.*` to `guardian.*`), Atrium tab keys, table names and resource keys change. URLs, payloads and MCP tool names do not.
6. **Audit history.** With Keen, SSO and SCIM entries are recorded as package `guardian` instead of `roster`, under Guardian's event class names. History queries, filters and exports by package or event show a break at the handover. Entries stored with Roster's model class names still resolve to Guardian's models.
7. **Stranded data.** If Roster is upgraded before the upgrade runs, Guardian's migrations create empty tables while Roster's still hold the rows. Guardian then warns: `php artisan about` names the tables and the command, and each boot reports one exception. SSO enforcement is off for those connections until you run `php artisan guardian:upgrade-from-roster`.

### Rolling back

Rolling back Guardian's create migrations refuses to drop a table that holds rows, names the tables, and drops nothing: after the upgrade those tables hold your SSO and SCIM data. To drop them anyway, set `GUARDIAN_FORCE_ROLLBACK=true` (`migrations.force_rollback`) for that rollback only. Deploy scripts that roll back routinely must set it deliberately. Rolling back the upgrade migration does nothing.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Guardian! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Jay Fletcher](https://github.com/refactor-circus)
- [All Contributors](../../contributors)

## License

Guardian is open-sourced software licensed under the [MIT license](LICENSE.md).
