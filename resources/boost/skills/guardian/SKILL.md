---
name: guardian
description: >
  The Refactor Circus suite's security package, refactor-circus/guardian: roles and permissions (Keystone's role
  store), two-factor, sessions and devices, passwords, API tokens, login protection and IP rules, the security log,
  SSO and SCIM; its middleware, contracts, config, JSON API, TypeScript client, MCP tools, and the upgrade from Roster.
license: MIT
metadata:
  author: Jay Fletcher
---

# Guardian

Use this skill when a Laravel application uses `refactor-circus/guardian` v2, the security package of the Refactor Circus suite. It serves a JSON API and MCP tools and has no PHP UI: Atrium v2, or any front end, builds on the API (`openapi.json`, `@refactor-circus/guardian-client`). Guardian ships its own Atrium module (JavaScript only, `dist/atrium/`, declared in composer.json's `extra.atrium`), which Atrium injects into its page.

## Install

- `composer require refactor-circus/guardian`, then `php artisan migrate` (creates the tables, the built-in roles `super-admin`, `admin` and `member`, and a permission per ability), then `php artisan guardian:grant-super-admin you@example.com`.
- Guardian requires only refactor-circus/keystone. Only SSO and SCIM need refactor-circus/roster: without it `php artisan about` shows them as "Inactive: install refactor-circus/roster" and they are skipped; everything else works.
- Optional: `web-auth/webauthn-lib` (passkeys; without it passkey endpoints answer 501), `laravel/sanctum` or `laravel/passport` (token drivers), `laravel/mcp`, and for SSO `laravel/socialite` plus `firebase/php-jwt` (OIDC), `socialiteproviders/saml2` (SAML) or `socialiteproviders/microsoft-azure` (Entra ID).
- Set `guardian.users.key_type` (`int`, `ulid`, `uuid`) before migrating when user keys are not integers.

## Host wiring

- Turn the API on with `guardian.routes.enabled` (off as shipped) behind authentication middleware; a cookie SPA needs sessions for the two-factor challenge, e.g. `'middleware' => ['web', 'auth', 'throttle:guardian']`.
- `guardian.login-throttle` on the login route (429 lockouts by identifier and address).
- `guardian.two-factor` on authenticated routes (403 `two_factor_enrollment_required` / `two_factor_challenge_required`).
- `guardian.session` on authenticated routes with a session (tracks sessions; 401 `session_revoked`).
- `guardian.password-fresh` on authenticated routes when `passwords.max_age_days` is set (403 `password_expired`).
- `guardian.ip` where IP rules should apply (403 `ip_denied`).
- Contracts a host may bind: `Domains\TwoFactor\Contracts\TwoFactorRequirement` (who must use two-factor), `Domains\TwoFactor\Contracts\OneTimeCodeChannel` (mail by default; bind one for SMS), `Domains\Token\Contracts\TokenDriver` (or set `guardian.tokens.driver` to `sanctum`, `passport` or a class).

## Permissions

- Guardian is Keystone's role store: with `guardian.authorization.bind` on (default) it answers `app(Permissions::class)->allows($user, $ability, $scope)` and `Roles` for every package, from its role tables, over scope chains, plus a `Gate::before` for super-admins. Set it false to keep Keystone's defaults.
- Abilities: `guardian.roles.view`, `guardian.roles.manage`, `guardian.roles.assign`, `guardian.sso.view`, `guardian.sso.manage`, `guardian.scim.manage`, `guardian.two-factor.manage`, `guardian.sessions.manage`, `guardian.tokens.manage`, `guardian.security.view`, `guardian.security.manage`. `admin` gets the roles view/assign, SSO and SCIM ones.
- Existing built-in roles do not gain new abilities automatically: run `php artisan guardian:sync-permissions`, then grant them by hand.
- `guardian.super_admins` lists verified emails that may do anything. Escalation is guarded: assigning a role needs all its permissions in that scope.

## Domains

- Roles and permissions: `guardian.roles.*`, `guardian.permissions.*`, `guardian.role-assignments.*`, `guardian.users.abilities.index`.
- Security log: `guardian.security-events.*` (`guardian.security.view`), `guardian.me.security-events.index`; identifiers stored only as HMACs; prune with `model:prune`.
- Two-factor: `guardian.two-factor.*` (TOTP, email/SMS codes, passkeys, recovery codes, the challenge), `guardian.two-factor-policies.*`, admin reset `DELETE users/{user}/two-factor`.
- Sessions and devices: `guardian.sessions.*`, `guardian.devices.*`, `guardian.users.sessions.*`.
- Passwords: `PasswordPolicy::rules($user)` in the host's own requests; `guardian.password-policy.show`, `guardian.passwords.check`.
- API tokens: `guardian.tokens.*`, `guardian.users.tokens.*`; the plain token is shown once.
- Login protection: `guardian.lockouts.destroy`, `guardian.ip-rules.*` (`guardian.security.manage`).
- SSO and SCIM: `guardian.organizations.sso-connections.*`, `guardian.sso-connections.*`, `guardian.users.sso-identities.index`, `guardian.sso-identities.destroy`, `guardian.organizations.scim-tokens.*`, `guardian.scim-tokens.destroy`; sign-in routes under `guardian.sso.prefix` (`roster/sso`, kept from Roster); SCIM 2.0 server at `/scim/v2/{organization}` (`guardian.scim.*`).

## OpenAPI and client

- `openapi.json` (OpenAPI 3.1, version 2.0.0, `operationId` = route name) documents every `guardian.*` API route.
- `@refactor-circus/guardian-client`: `createGuardianClient({ baseUrl: 'https://app.test/guardian' })`, a typed openapi-fetch client. Contributors regenerate both with `composer client:generate`.

### MCP

- `GuardianServer` has 51 tools (roles, permissions, security log, two-factor status and policies, sessions, devices, password policy, token list/revoke, lockouts, IP rules, SSO, SCIM), found through `search_tools`. Enrolment, challenges and token issuing are HTTP-only. Enable `mcp.web` or `mcp.local` in config.

## Upgrading from Roster

- Roles: install Guardian v2 with Roster v2, set `guardian.adopt.scope_types` if Roster's models use morph aliases, run `php artisan migrate`, check the adoption report (`php artisan guardian:adopt-roster-roles` prints it), then drop `roster_permissions`, `roster_roles`, `roster_permission_role` and `roster_role_assignments` by hand.
- Changed from Roster: an OIDC sign-in links an account by email or creates one just in time only when the id_token's `email_verified` claim is `true`; otherwise it is refused with reason `unverified_email`. Identities already linked, SAML and Entra ID are unchanged.
- SSO and SCIM: install Guardian, then upgrade Roster to the release without SSO and SCIM. Until Roster lets go, Guardian loads no sign-in or SCIM server routes and the upgrade changes nothing.
- SSO enforcement fails open until Guardian is installed after Roster's removal: a Roster without SSO enforces nothing, so enforced domains can sign in with a password until Guardian is installed and the upgrade has run.
- `php artisan guardian:upgrade-from-roster` renames the `roster_*` SSO and SCIM tables to `guardian_*` and the `roster.sso.*` / `roster.scim.*` permissions to `guardian.*`, keeping grants. It is idempotent and never deletes rows.
- Rename published `roster.sso.*` / `roster.scim.*` config and env keys to `guardian.*` by hand: Guardian never reads the old keys. The command lists leftovers.
- Audit entries move from package `roster` to `guardian`, with new event class names.
- If `php artisan about` reports stranded tables, run the command: SSO enforcement is off for those connections until then.
- Rolling back the create migrations over rows needs `GUARDIAN_FORCE_ROLLBACK=true`.
