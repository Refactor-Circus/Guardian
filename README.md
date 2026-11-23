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

Guardian is the security package of the Refactor Circus suite: roles and permissions, two-factor authentication, sessions and devices, a password policy, API tokens, login protection and a security log, plus single sign-on (OIDC, SAML, Microsoft Entra ID) and SCIM 2.0 provisioning for [refactor-circus/roster](https://github.com/refactor-circus/roster)'s organizations. It is the role store behind Keystone's authorization contract, so every installed suite package checks its permissions through Guardian.

Guardian has no UI of its own. It serves a JSON API and MCP tools; [Atrium v2](https://github.com/refactor-circus/atrium), or any front end, is the optional UI and builds on the API through the committed `openapi.json` and the `@refactor-circus/guardian-client` TypeScript client.

## Installation

Guardian requires only refactor-circus/keystone:

```bash
composer require refactor-circus/guardian
php artisan migrate
```

The migrations create Guardian's tables and the built-in roles (`super-admin`, `admin`, `member`), with a permission for every ability the installed packages define. Grant yourself the super role:

```bash
php artisan guardian:grant-super-admin you@example.com
```

Only SSO and SCIM need Roster (`composer require refactor-circus/roster`). Without it they are skipped: `php artisan about` shows them as "Inactive: install refactor-circus/roster" (or "Inactive: needs a newer refactor-circus/roster" for a Roster without the sign-in and provisioning seams), and their routes, tools, migrations and the upgrade command are not registered. Everything else works.

You may publish the config, migrations and translations:

```bash
php artisan vendor:publish --tag="guardian-config"
php artisan vendor:publish --tag="guardian-migrations"
php artisan vendor:publish --tag="guardian-lang"
```

Optional packages, each needed only for its feature:

| Package | For |
|---|---|
| `refactor-circus/roster` | SSO and SCIM |
| `web-auth/webauthn-lib` | Passkeys as a second factor (without it every passkey endpoint answers 501) |
| `laravel/sanctum` or `laravel/passport` | The bundled API token drivers |
| `laravel/mcp` | The MCP server |
| `laravel/socialite`, plus `firebase/php-jwt` (OIDC), `socialiteproviders/saml2` (SAML) or `socialiteproviders/microsoft-azure` (Entra ID) | SSO sign-in |
| `refactor-circus/keen` | Auditing Guardian's changes as package `guardian` |

## Host wiring

Turn the API on (`routes.enabled`, off as shipped) behind your authentication middleware, then put Guardian's middleware on your own routes:

```php
// config/guardian.php
'routes' => [
    'enabled' => true,
    'prefix' => 'guardian',
    // A cookie-authenticated SPA needs sessions for the two-factor challenge:
    'middleware' => ['web', 'auth', 'throttle:guardian'],
],
```

| Alias | Class | Put it on |
|---|---|---|
| `guardian.login-throttle` | `ThrottleLogins` | Your login route: 429 with `Retry-After` once an identifier or address has failed too often |
| `guardian.two-factor` | `EnsureTwoFactor` | Authenticated routes: 403 `two_factor_enrollment_required` past a requirement's deadline, 403 `two_factor_challenge_required` while the session owes a challenge |
| `guardian.session` | `TrackSession` | Authenticated routes with a session: keeps the session record current, signs a revoked session out with 401 `session_revoked` |
| `guardian.password-fresh` | `EnsurePasswordFresh` | Authenticated routes, when `passwords.max_age_days` is set: 403 `password_expired` |
| `guardian.ip` | `EnforceIpRules` | Any routes you want behind the IP rules: 403 `ip_denied` |

```php
Route::post('login', LoginController::class)->middleware('guardian.login-throttle');

Route::middleware(['auth', 'guardian.session', 'guardian.two-factor', 'guardian.password-fresh'])->group(function () {
    // ...
});

Route::middleware('guardian.ip')->prefix('admin')->group(function () {
    // ...
});
```

Guardian's own two-factor enrolment, challenge and status routes are never blocked by `guardian.two-factor`.

The bindings a host may replace:

| Contract | Default | Replace it to |
|---|---|---|
| Keystone's `Permissions` and `Roles` | `GuardianPermissions`, `GuardianRoles` (with `guardian.authorization.bind` on) | Keep Keystone's defaults: set `guardian.authorization.bind` to false |
| `Domains\TwoFactor\Contracts\TwoFactorRequirement` | `PolicyTwoFactorRequirement` (config and two-factor policies) | Decide who must use two-factor, with which methods and by when |
| `Domains\TwoFactor\Contracts\OneTimeCodeChannel` | `MailOneTimeCodeChannel` (email codes as a mail notification, no SMS) | Send SMS codes, or email them another way |
| `Domains\Token\Contracts\TokenDriver` | none (`guardian.tokens.driver` null) | Issue tokens through Sanctum, Passport or your own store |

## Roles and permissions

Guardian is Keystone's role store. With `guardian.authorization.bind` on (the default) it binds Keystone's `Permissions` to `GuardianPermissions` and `Roles` to `GuardianRoles`, both scoped to the request, and registers a `Gate::before` that answers `true` for super-admins and for abilities it holds a permission for (never `false`). Every package that checks `app(Permissions::class)->allows($user, $ability, $scope)` is answered from Guardian's tables:

- A role has a `scope` where it may be assigned: `global`, `any`, or a scope model's morph type, and optionally an owner (a role of one organization's own). A `super` role may do anything.
- An assignment gives a user a role globally or in a scope. A grant in a scope counts in its child scopes too (Keystone's `ScopeChain`), so an organization admin is an admin of its teams.
- `super_admins` lists emails that may do anything once verified: a way back in when nobody holds a super role.
- Answers are remembered for the request; `permission_cache.ttl` keeps them between requests, forgotten whenever Guardian changes a role, assignment or permission.
- Escalation is guarded: assigning, revoking or editing a role needs every permission it grants, in that scope, and only super-admins touch `super` roles.

Guardian's abilities, defined in Keystone's `Abilities`:

| Ability | Allows | Granted to |
|---|---|---|
| `guardian.roles.view` | Viewing roles, permissions and who holds them | `admin` |
| `guardian.roles.manage` | Creating, editing and deleting roles and permissions | |
| `guardian.roles.assign` | Assigning and revoking roles | `admin` |
| `guardian.sso.view` | Viewing an organization's single sign-on connections | `admin` |
| `guardian.sso.manage` | Configuring single sign-on connections | `admin` |
| `guardian.scim.manage` | Issuing and revoking SCIM provisioning tokens | `admin` |
| `guardian.two-factor.manage` | Two-factor policies, and resetting another user's two-factor | |
| `guardian.sessions.manage` | Listing and revoking other users' sessions | |
| `guardian.tokens.manage` | Listing and revoking other users' API tokens | |
| `guardian.security.view` | Reading the security log | |
| `guardian.security.manage` | IP rules and clearing lockouts | |

`php artisan guardian:sync-permissions` (also run by the migration) creates a permission for every ability any package defines and any missing built-in role (`roles.built_in`). A role is granted its packages' `grantTo` abilities only when it is created, so your edits survive: grant new abilities to existing roles by hand. `php artisan guardian:grant-super-admin {email}` assigns `super-admin` to an existing user.

Routes: `guardian.roles.*`, `guardian.permissions.*`, `guardian.role-assignments.*` and `guardian.users.abilities.index` (`GET users/{user}/abilities?scope_type=&scope_id=`). A `system` permission or role cannot be deleted.

## Security log

Guardian records security events in its own table (`guardian_security_events`): sign-ins and failures, lockouts, suspicious sign-ins, sign-outs, password resets and changes, every two-factor, session, token and IP event. A failed sign-in's identifier is stored only as an HMAC; passwords, codes, secrets and tokens never are. Read it through `guardian.security-events.index` (filters `type`, `user`, `from`, `to`, by cursor) and `.show` with `guardian.security.view`; every signed-in user may read their own at `guardian.me.security-events.index`. `security_log.enabled` turns off recording Laravel's auth events; rows older than `security_log.retention_days` (90) are pruned by `php artisan model:prune`, which you should schedule.

## Two-factor authentication

Users enrol through the `guardian.two-factor.*` routes:

- **Authenticator apps (TOTP).** `POST two-factor/totp` answers the secret and an `otpauth://` URI (issuer `two_factor.issuer`, or the app name); `POST two-factor/totp/confirm` with a code confirms it. Codes are accepted one step either side and never twice.
- **Email and SMS codes.** `POST two-factor/one-time-codes` (`{type, destination}`) sends a six-digit code, `.../confirm` confirms it. Codes expire after `two_factor.one_time_codes.ttl_minutes`, allow five attempts, and are sent at most once a minute. The default `OneTimeCodeChannel` emails codes as a mail notification and sends no SMS; bind your own to add SMS:

  ```php
  use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\OneTimeCodeChannel;

  $this->app->bind(OneTimeCodeChannel::class, SmsAndMailChannel::class);
  ```

- **Passkeys.** Install `web-auth/webauthn-lib` (suggested, not required) to register passkeys (`two-factor/passkeys/options`, then `two-factor/passkeys`). The relying party comes from `two_factor.passkeys` (`rp_id`, `rp_name`, `origins`), defaulting to `app.url` and `app.name`.
- **Recovery codes.** Ten single-use codes come with the first factor, shown once; `POST two-factor/recovery-codes` replaces them.

`GET two-factor` answers the user's status (factors with masked destinations, passkeys, remaining recovery codes, the requirement and its deadline). Removing a factor needs a code from another factor or a recovery code, and the last factor cannot be removed while two-factor is required. An admin with `guardian.two-factor.manage` resets a user who lost theirs (`DELETE users/{user}/two-factor`).

**The challenge.** When a user with a factor signs in, the session owes a challenge: `POST two-factor/challenge` with `method` (`totp`, `recovery`, `email`, `sms` or `passkey`) and the `code` or `assertion` confirms it (codes are sent by `two-factor/challenge/send`, passkey options come from `two-factor/challenge/passkey-options`). Five failures a minute answer 429. `guardian.two-factor` refuses the session's other requests until then.

**Requiring it.** `two_factor.required` requires it of everyone. Two-factor policies (`guardian.two-factor-policies.*`, with `guardian.two-factor.manage`) require it of a role's holders, of a scope's members, or of everyone, with allowed methods and days of grace. Bind your own `TwoFactorRequirement` to decide another way:

```php
use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\TwoFactorRequirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\Requirement;

$this->app->bind(TwoFactorRequirement::class, fn () => new class implements TwoFactorRequirement {
    public function for(Authenticatable $user): Requirement
    {
        return new Requirement(required: $user->is_staff, methods: ['totp', 'passkey']);
    }
});
```

## Sessions and devices

With `guardian.session` on your authenticated routes, Guardian records every signed-in session and device (by an HMAC of the user agent; it never stores a session id). Users list theirs (`guardian.sessions.index`, the current one flagged), sign one out (`guardian.sessions.destroy`) or all the others (`guardian.sessions.others.destroy`, with `tokens: true` to revoke their API tokens too), and list or forget devices (`guardian.devices.*`). Admins with `guardian.sessions.manage` list and revoke another user's sessions (`guardian.users.sessions.*`). A first sign-in from a new device, for a user who has another, sends `NewDeviceSignInNotification` through `devices.notify_via` (turn it off with `devices.notify`).

## Passwords

`PasswordPolicy::rules($user)` gives your registration, reset and change requests the policy in `passwords`: length 12 to 128, a breach check (HIBP's range API: only five characters of the SHA-1 leave the app; when HIBP is unreachable the check passes and `password.breach_check_unavailable` is recorded), optional complexity rules, and the last `history` (5) passwords refused:

```php
use RefactorCircus\Guardian\Domains\Password\PasswordPolicy;

$request->validate(['password' => ['required', 'confirmed', ...PasswordPolicy::rules($user)]]);
```

Guardian records each password change from your user model. `GET password-policy` answers the rules for UIs and `POST passwords/check` checks one (`{valid, errors}`, rate limited); both serve guests. With `passwords.max_age_days` set, `guardian.password-fresh` refuses users whose password is older.

## API tokens

Guardian stores no tokens: a `TokenDriver` does. Set `tokens.driver`:

| Value | Driver |
|---|---|
| `null` (default) | None: the token routes answer 501 |
| `'sanctum'` | `SanctumTokenDriver`, for users with Sanctum's `HasApiTokens` |
| `'passport'` | `PassportTokenDriver`, for Passport users; abilities are scopes (define them with `Passport::tokensCan()`) |
| A class name | Your own implementation of `RefactorCircus\Guardian\Domains\Token\Contracts\TokenDriver` (`issue()`, `tokens()`, `revoke()`, `revokeAll()`) |

Users list, issue and revoke their tokens (`guardian.tokens.*`); the plain token is answered once, at issue. Abilities must come from `tokens.abilities` and no token outlives `tokens.max_lifetime_days`. Admins with `guardian.tokens.manage` list and revoke another user's tokens (`guardian.users.tokens.*`). MCP lists and revokes, never issues.

## Login protection and IP rules

`guardian.login-throttle` counts failed sign-ins (Laravel's `Failed` event) by the identifier (`login.identifier_field`, stored only as an HMAC) and by address: after `login.max_attempts` (5) for an identifier, or `login.ip_max_attempts` (50) for an address, within `login.decay_minutes` (15), the login route answers 429 and Laravel's `Lockout` fires. A sign-in clears the identifier; an admin with `guardian.security.manage` clears a lockout through `DELETE lockouts`. A sign-in from a new device after three recent failures, or from a network never seen for the user, is recorded as `login.suspicious` and the user is told; it is never blocked.

IP rules (`guardian.ip-rules.*`, with `guardian.security.manage`) allow or deny IPv4 and IPv6 addresses and CIDR ranges, optionally until a date. `guardian.ip` enforces them: in `ip_rules.mode` `denylist` (the default) only denied addresses are refused; in `allowlist` every address no allow rule matches is too. A deny always wins. Add an allow rule for your own network before switching to `allowlist`.

## Single sign-on

Organizations sign their people in through their own identity provider. Connections are managed through `guardian.organizations.sso-connections.*`, `guardian.sso-connections.*`, `guardian.users.sso-identities.index` and `guardian.sso-identities.destroy` (`guardian.sso.view`, `guardian.sso.manage`, in the organization). The sign-in routes (`guardian.sso.discover`, `start`, `callback`, `metadata`, `link`) live under `sso.prefix`, which stays `roster/sso`, Roster's, so the callback URLs registered at your identity providers keep working.

SSO and SCIM work on Roster's users and organizations: they are an existing cross-package reference, reached only through Roster's public seams, and the only part of Guardian that knows Roster. Every other domain is Roster-free.

**Changed from Roster: OIDC needs a verified email.** An OIDC sign-in links an existing account by email, or creates one just in time, only when the id_token's `email_verified` claim is `true`. A false, missing or non-boolean claim refuses the sign-in ("Your identity provider has not verified this email address."), and the failed sign-in is recorded with the reason `unverified_email`. Roster trusted the email alone. Identities already linked keep signing in, and SAML and Microsoft Entra ID are unchanged. If your provider omits the claim, turn it on there (most send it with the `email` scope), or have users link their identity from their account.

## SCIM

Each organization has a SCIM 2.0 server at `/scim/v2/{organization}` (`Users`, `Groups`, `Bulk`, `ServiceProviderConfig`, `ResourceTypes`, `Schemas`; routes `guardian.scim.*`). Requests need one of the organization's SCIM tokens, issued through `guardian.organizations.scim-tokens.store` or MCP with `guardian.scim.manage`, and shown once. Members SCIM provisions join with the membership source `scim`.

## Configuration

`config/guardian.php` has a section per feature, each explained in its banner: `sso`, `scim`, `routes` (the JSON API, off by default, under `guardian`), `authorization` (`enabled`, and `bind` for the role store), `super_admins`, `permission_cache`, `rate_limit` (the `guardian` limiter, 120 a minute), `audit`, `users` (`model`, and `key_type` for the user columns: `int`, `ulid` or `uuid`, set before migrating), `roles`, `adopt`, `security_log`, `two_factor`, `sessions`, `devices`, `passwords`, `tokens`, `login`, `ip_rules`, `policies`, `tables`, `mcp`, `cortex` and `migrations`.

## HTTP API

With `routes.enabled` on (add authentication middleware first), every route is named `guardian.*` and lives under `routes.prefix` (`/guardian`). Each checks the caller's ability in the scope it is about, through Keystone's contract, so the API and MCP make the same check; a guest gets 401 and a caller without the ability 403. Self-service routes (your own two-factor, sessions, devices, tokens and security events) need only a signed-in user. `openapi.json` lists every operation, under its route name.

## OpenAPI and TypeScript client

`openapi.json`, committed at the package root, is the OpenAPI 3.1 document of the JSON API (version 2.0.0, one operation per `guardian.*` API route, `operationId` = route name; the SCIM protocol server and SSO sign-in pages are not in it). Its paths are relative to your `routes.prefix`.

`client/` is `@refactor-circus/guardian-client`, a typed [openapi-fetch](https://openapi-ts.dev/openapi-fetch/) client generated from it:

```ts
import { createGuardianClient } from '@refactor-circus/guardian-client';

const guardian = createGuardianClient({ baseUrl: 'https://app.test/guardian', credentials: 'include' });

const { data } = await guardian.GET('/two-factor');
await guardian.POST('/two-factor/totp/confirm', { body: { code: '123456' } });
```

It exports `createGuardianClient`, `GuardianClient`, `GuardianClientOptions` and the generated `paths` and `components` types. Contributors regenerate the document and the client with `composer client:generate` (Node required); `composer test` fails, without Node, when either is stale.

## MCP

`GuardianServer` offers 51 tools, one per action that suits an agent: roles, assignments, user abilities and permissions; the security log; two-factor status, removal, reset and policies; sessions and devices; the password policy; listing and revoking API tokens; lockouts and IP rules; SSO connections and identities; SCIM tokens. Enrolment, challenges, token issuing and the password check stay HTTP-only. Clients find tools through `search_tools`. Turn on the web endpoint (`mcp.web`, at `mcp/guardian`) or the local server (`mcp.local`, `php artisan mcp:start guardian`) in config; both are off as shipped. Each tool checks the same permissions as the API. With refactor-circus/cortex installed, Cortex agents see them too.

## Permissions

Guardian's abilities are listed under [Roles and permissions](#roles-and-permissions). New installs get them with the built-in roles. On an existing install, built-in roles do not gain new ones automatically: run `php artisan guardian:sync-permissions` to create them, then grant them to the roles that need them by hand. The upgrade from Roster moves the grants of Roster's old `roster.sso.*` / `roster.scim.*` permissions for you, and the role adoption moves `roster.roles.*`.

## Upgrading from Roster

Two things moved from Roster to Guardian: SSO and SCIM (Guardian v0.1), and roles and permissions (v2.0.0).

### Roles and permissions

Roster v2 no longer stores roles. Guardian v2 adopts Roster v0.1's role tables once, on install:

1. Install Guardian v2 with Roster v2 (`composer require refactor-circus/roster:^2.0 refactor-circus/guardian:^2.0`).
2. If you map Roster's organization or team models to morph aliases, set `adopt.scope_types` to those aliases first.
3. Run `php artisan migrate`. The `adopt_roster_role_tables` migration copies `roster_permissions`, `roster_roles`, `roster_permission_role` and `roster_role_assignments` into Guardian's tables, keeping every ULID, while Guardian holds nothing but its seeded built-in roles. Organization and team scopes become those models' morph types, a custom role's organization becomes its owner, and `roster.roles.*`, `roster.sso.*` and `roster.scim.manage` become their `guardian.*` names. Every user keeps the answers Roster gave.
4. Check the adoption report in your log (or run `php artisan guardian:adopt-roster-roles`, which prints it; a second run says "already adopted").
5. Drop the four `roster_*` role tables by hand once you are satisfied: Guardian never alters or drops them.

### SSO and SCIM


SSO and SCIM used to ship with Roster. Guardian takes them over; your connections, linked identities, tokens and role grants come with them.

1. **Install order.** Install Guardian, then upgrade Roster to the release that removes SSO and SCIM. Guardian can be installed first: while Roster still ships SSO and SCIM, Guardian serves neither the sign-in routes nor the SCIM server routes, so it never shadows Roster's identical URLs. Upgrading Roster first leaves nothing to enforce single sign-on: SSO enforcement fails open until Guardian is installed after Roster's removal and the upgrade has run: enforced domains can sign in with a password in between.
2. **The handover rule.** Guardian moves the data only once Roster no longer declares SSO and SCIM. Until then the upgrade changes nothing and says "Roster still owns SSO and SCIM; run `php artisan guardian:upgrade-from-roster` after upgrading Roster."
3. **Run the upgrade.** `php artisan migrate` runs Guardian's upgrade migration. If Roster was still on the old release then, the migration is recorded but skipped; run `php artisan guardian:upgrade-from-roster` once Roster is upgraded. The command is safe to run again and from a deploy script, never deletes a table that holds rows, and prints what it did:
   - renames `roster_sso_connections`, `roster_sso_identities`, `roster_scim_tokens`, `roster_scim_users` and `roster_scim_groups` to their `guardian_*` names (dropping an empty `guardian_*` table in the way first). Where a `guardian_*` table already holds rows, it changes neither table and tells you.
   - renames the permissions `roster.sso.view`, `roster.sso.manage` and `roster.scim.manage` to `guardian.*`, keeping every role's grant.
   - lists leftover config (below).
4. **Config and env keys.** Rename published `roster.sso.*` / `roster.scim.*` config keys and their env variables to `guardian.sso.*` / `guardian.scim.*` in `config/guardian.php`. There is no automated config or env key migration, and Guardian never reads the old keys as a fallback. The upgrade lists every `roster.sso.*` / `roster.scim.*` value still set that differs from Guardian's, with the `guardian.*` key to move it to.
5. **Other renames.** Route names (`roster.sso.*` to `guardian.sso.*`, `roster.scim.*` to `guardian.scim.*`, the API's `roster.*` to `guardian.*`, table names and resource keys change. URLs, payloads and MCP tool names do not.
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
