# Guardian v2: security platform

## Goal

Guardian v2.0.0 is the suite's security package, a JSON API plus MCP tools with no UI of its own. It owns roles and permissions, moved in from Roster, and implements Keystone's authorization contract for every package. It adds two-factor authentication, session and device management, a password policy, driver-based API tokens, login protection and a security event log. SSO and SCIM keep working as they do today. Atrium v2, or any front end, reaches Guardian through the committed `openapi.json` and `@refactor-circus/guardian-client`.

## Acceptance criteria

### v2 conversion

1. `composer.json`: `require` holds `php` `^8.5`, `laravel/framework`, `refactor-circus/keystone` `^2.0` and `pragmarx/google2fa`, and no other `refactor-circus/*` package. `require-dev` adds `refactor-circus/roster` `^2.0` and `refactor-circus/keen` `^2.0` (tests only), `dedoc/scramble`, `laravel/sanctum`, `laravel/passport` and `web-auth/webauthn-lib`, and drops `pestphp/pest-plugin-browser`. `refactor-circus/atrium` is in none of `require`, `require-dev`, `suggest`, `conflict` or `repositories`. `suggest` names Roster (SSO/SCIM), Sanctum, Passport, `web-auth/webauthn-lib`, `laravel/mcp` and the Socialite packages. `extra.branch-alias.dev-main` is `2.0.x-dev`. There is no `test:without-atrium` or `test:browser` script. The description calls Guardian the suite's security package with a JSON API and MCP. `tests/Feature/ComposerTest.php` checks all of this.
2. The Atrium UI is gone. `src/Atrium/`, `resources/views/`, the Atrium-only `lang/` keys, `tests/Feature/Atrium/`, `tests/Feature/Ui/`, `tests/Browser/`, `tests/Modes/WithoutAtrium/`, `tests/WithoutAtriumTestCase.php` and `tests/Feature/StylesTest.php` are deleted. The root `package.json` and `package-lock.json` are deleted when only styles or browser tests used them. `config/guardian.php` has no `ui` or `atrium` key. No route named `atrium.guardian.*` exists. An arch rule says nothing uses `RefactorCircus\Atrium`, and a test says no file under `src/` or `config/` contains `atrium` (case-insensitive).
3. Guardian works without Roster. In the Standalone mode test case (Roster not loaded), Guardian boots, migrates and registers every route and MCP tool of the RBAC, two-factor, session, password, token, login-protection and security-event domains. Only the SSO and SCIM routes, tools and providers are absent. `Support\Integration::rosterInstalled()` gates only `Domains\Sso` and `Domains\Scim`.
4. The Roster reference is not widened. An arch test asserts that `RefactorCircus\Guardian\Domains\{Role, Permission, TwoFactor, Session, Password, Token, LoginProtection, SecurityEvent}` use nothing in `RefactorCircus\Roster`. Nothing in `src/` uses `RefactorCircus\Roster\Domains\Role` or `RefactorCircus\Roster\Domains\Permission`. Every other `RefactorCircus\Roster\*` class named in `src/` is on the allow-list in `tests/Fixtures/roster-references.php`, which is today's list minus those two domains, and `RosterBoundaryTest` checks it.
5. SSO and SCIM use Keystone's contract. `guardian.sso.view`, `guardian.sso.manage` and `guardian.scim.manage` are defined in Keystone's `Abilities` and checked with Keystone `Authorizer::allows()`, with the Roster organization as the scope. The organization owner rule still applies, through Roster's `Scope` implementation. When SSO or SCIM joins a user to an organization, it assigns the default member role through Keystone's `Roles` (the same `roster.roles.default_member` setting as today). The existing SSO and SCIM tests pass, with roles granted through Keystone's fakes until group 4 and through Guardian after it.

### Roles and permissions

6. Migration `create_guardian_role_tables` creates `permissions` (ULID, unique `name`, `description`, `system`, timestamps), `roles` (ULID, `name`, `slug`, `scope`, nullable `owner_type`/`owner_id`, `description`, `super`, `system`, timestamps; index `scope, owner_type, owner_id, slug`), `permission_role`, and `role_assignments` (ULID, `role_id`, `user_id`, nullable `scope_type`/`scope_id`, timestamps; unique `role_id, user_id, scope_type, scope_id`; index `user_id, scope_type, scope_id`). The names come from `guardian.tables.*` (defaults `guardian_permissions`, `guardian_roles`, `guardian_permission_role`, `guardian_role_assignments`). `user_id` is typed by `guardian.users.key_type` (`int` default, `ulid`, `uuid`) through `Support\UserKey`. `roles.scope` is `global`, `any`, or a scope model's morph type.
7. Built-in roles come from `guardian.roles.built_in`: `super-admin` (scope `global`, super), and `admin` and `member` (scope `any`). `guardian:sync-permissions` (also run by the migration) creates a `system` permission for every Keystone `Abilities` entry. It creates missing built-in roles, granting each the abilities whose `grantTo` names it, only when it creates that role. It never removes a permission or rewrites a role. It is idempotent and ignores `grantTo` slugs that are not built-in.
8. Guardian defines its own abilities in Keystone's `Abilities`: `guardian.roles.view`, `guardian.roles.manage`, `guardian.roles.assign`, `guardian.sso.view`, `guardian.sso.manage`, `guardian.scim.manage`, `guardian.two-factor.manage`, `guardian.sessions.manage`, `guardian.tokens.manage`, `guardian.security.view` and `guardian.security.manage`. `admin` gets `guardian.roles.view` and `guardian.roles.assign`, plus the SSO and SCIM abilities as today's role grants did.
9. With `guardian.authorization.bind` true (the default), Guardian binds Keystone's `Permissions` to `GuardianPermissions` and `Roles` to `GuardianRoles`, both `scoped()`. With it false, Keystone's defaults stay bound.
10. `GuardianPermissions::allows($user, $ability, $scope)` is false for a guest and true for a super-admin. It is true when any scope in `ScopeChain::of($scope)` answers `grantsByItself()`. Otherwise it is true only when the user holds an assignment whose role grants the ability globally, in `$scope`, or in one of its parent scopes. A scope that is an Eloquent model but not a `Scope` counts only itself. An ability Guardian has no permission row for is false.
11. `scopesWith()` is null when the user holds the ability globally (or is a super-admin). Otherwise it lists the distinct `scope_id`s of their assignments of that `scope_type` that grant it. `abilities()` is the sorted list held in a scope (every known permission for a super-admin). `isSuperAdmin()` is true for a global assignment of a `super` role, or for a user whose email is in `guardian.super_admins` and verified. `forget($user)` drops that user's remembered answers, and `forget()` drops them all.
12. `GuardianRoles`: `available()` is true. `exists($slug, $scope)` is true when a role with that slug may be assigned in that scope: scope `global` with a null scope, `any`, a matching morph type, and an `owner` that is null or the scope itself. `assign()` is idempotent and returns false for a role that cannot be assigned there. `revoke()` removes assignments in exactly that scope. `revokeEverywhere()` removes them all. `of()` returns sorted slugs. `holders($ability)` returns the keys of users with a global assignment of a super role or of a role that grants the ability, plus the verified configured super-admins.
13. Answers are memoized per request. `guardian.permission_cache.ttl` (default 0) and `.store` keep the permission names and each user's grants between requests. Guardian's actions and its role, assignment and permission model events forget what they change.
14. With binding on, Guardian registers a `Gate::before`. It answers `true` for a super-admin, and for an ability with a Guardian permission row it answers `true` when `allows($user, $ability, <first Model argument or null>)`, and `null` otherwise. It never answers `false`.
15. Escalation guard (moved from Roster's `GuardsEscalation`): assigning or revoking a role in a scope requires the actor to hold every permission of that role in that scope. Only super-admins assign, revoke, create or change `super` roles. A role's permissions can only be set to ones the actor holds. Each refusal is a validation error naming the missing permissions.
16. Role actions, moved from Roster and renamed into `RefactorCircus\Guardian\Domains\Role`: `CreateRoleAction`, `UpdateRoleAction`, `DeleteRoleAction` (system roles are refused), `ShowRoleAction`, `ListRolesAction`, `AssignRoleAction`, `RevokeRoleAction`, `ListRoleAssignmentsAction` (filters `user`, `role`, `scope_type`, `scope_id`) and `ListUserAbilitiesAction`. Permission actions in `Domains\Permission`: `CreatePermissionAction`, `UpdatePermissionAction`, `DeletePermissionAction` (a `system` permission cannot be deleted or renamed) and `ListPermissionsAction`. Each one dispatches its starting and finished events.
17. HTTP routes: `guardian.roles.index|store|show|update|destroy`, `guardian.permissions.index|store|update|destroy`, `guardian.role-assignments.index|store|destroy` and `guardian.users.abilities.index` (`GET users/{user}/abilities?scope_type=&scope_id=`). Viewing needs `guardian.roles.view`, managing roles and permissions needs `guardian.roles.manage`, and assignments need `guardian.roles.assign`, each checked in the scope the request names. A guest gets 401 and a user without the permission 403.
18. MCP: `GuardianServer::TOOLS` includes a tool for every action in criterion 16, each with an output schema from its resource, and `parityGaps()` reports none.
19. Commands: `guardian:sync-permissions` (criterion 7) and `guardian:grant-super-admin {email}` (moved from Roster). The latter assigns `super-admin` globally to an existing user and fails with a message for an unknown email.
20. Adopting Roster's tables. The migration `2026_11_24_000003_adopt_roster_role_tables.php` and the command `guardian:adopt-roster-roles` run `Upgrade\RosterRoleAdoption`. When all four source tables named in `guardian.adopt.roster_tables` exist (defaults `roster_permissions`, `roster_roles`, `roster_permission_role`, `roster_role_assignments`), and Guardian holds no assignment and no role other than the seeded built-ins, the adoption replaces the seeded rows with copies of Roster's, keeping their ULIDs:
    - Role scope `global` stays `global`. `organization` and `team` become the morph types in `guardian.adopt.scope_types`, whose defaults are Roster's organization and team model class names.
    - A custom role's `organization_id` becomes its owner.
    - An assignment's `team_id`, or else its `organization_id`, becomes its scope.
    - Permission names `roster.roles.{view,manage,assign}`, `roster.sso.*` and `roster.scim.manage` become their `guardian.*` names.
    - Then it runs the sync.
    It never alters or drops a source table. A second run changes nothing and reports "already adopted", and with no source tables it reports "nothing to adopt". The report goes to the log from the migration and to the console from the command.
21. After adopting a fixture dataset with global, organization, team, owner-custom and super roles, every user's `allows()` answers for every permission in the fixture's scopes equal what Roster's resolution gave for the same data. The expected answers are recorded in the fixture.

### Security event log

22. Table `guardian_security_events` (`guardian.tables.security_events`): ULID, `type`, nullable `user_id`, nullable `identifier_hash` (an HMAC-SHA256 of the lowercased login identifier, keyed by the app key), `ip`, `user_agent` (at most 512 characters), JSON `context` and `occurred_at`, indexed on `user_id, occurred_at` and `type, occurred_at`. The model is `Prunable` after `guardian.security_log.retention_days` (default 90).
23. `RecordSecurityEventAction` is the only writer. Types come from the `SecurityEventType` enum: `login.succeeded`, `login.failed`, `login.locked_out`, `login.suspicious`, `logout`, `password.reset`, `password.changed`, `password.breach_check_unavailable`, `two_factor.enabled`, `two_factor.disabled`, `two_factor.challenge_passed`, `two_factor.challenge_failed`, `two_factor.recovery_code_used`, `two_factor.recovery_codes_regenerated`, `two_factor.policy_conflict`, `passkey.sign_count_mismatch`, `session.revoked`, `device.new`, `token.issued`, `token.revoked`, `ip.denied` and `lockout.cleared`. The action dispatches its starting and finished events, so an installed audit log (Keen, through Keystone's audit seams) records each event as a Guardian action. A test runs every type and asserts that no stored context or audit payload contains a password, code, secret or token value.
24. Listeners record Laravel's `Login`, `Failed` (with the identifier hash and the user when found), `Lockout`, `Logout` and `PasswordReset` events.
25. HTTP and MCP: `guardian.security-events.index` (filters `type`, `user`, `from`, `to`, cursor-paginated, needs `guardian.security.view`), `guardian.security-events.show`, and `guardian.me.security-events.index` (the caller's own events, for any signed-in user).

### Two-factor

26. Tables: `guardian_two_factor_methods` (ULID, `user_id`, `type` `totp|email|sms`, encrypted `secret` and `destination`, `label`, `confirmed_at`, `last_used_at`, `last_timestep`, timestamps), `guardian_recovery_codes` (ULID, `user_id`, `code_hash`, `used_at`), `guardian_passkeys` (ULID, `user_id`, unique `credential_id`, `public_key`, `sign_count`, JSON `transports`, `aaguid`, `label`, `last_used_at`, timestamps) and `guardian_one_time_codes` (ULID, `user_id`, `method_id`, `code_hash`, `expires_at`, `attempts`, `consumed_at`). Every name is configurable under `guardian.tables.*`.
27. TOTP enrolment. `POST two-factor/totp` (`guardian.two-factor.totp.store`) answers 201 with `secret` and `otpauth_uri` (issuer `guardian.two_factor.issuer`, default `config('app.name')`; SHA-1, 6 digits, 30 seconds). The method stays unconfirmed. `POST two-factor/totp/confirm` with a valid code confirms it. On the first confirmed method it creates recovery codes and returns them once. Codes are accepted within ±1 time step, and a code from a time step already used is refused (replay).
28. Recovery codes: 10 codes shaped `xxxxx-xxxxx`, stored as HMAC hashes. `POST two-factor/recovery-codes` replaces them all and returns them once. Each code works once. No endpoint returns stored codes, only the remaining count.
29. Email and SMS codes. The `Domains\TwoFactor\Contracts\OneTimeCodeChannel` contract has `supports(string $type): bool` and `send(Authenticatable $user, string $type, string $destination, string $code): void`. The default binding sends `email` codes as a mail notification (`OneTimeCodeNotification`) and supports no `sms`. Enrolling or challenging with an unsupported type answers 422 `method_unavailable`. `POST two-factor/one-time-codes` (`{type, destination}`) enrols the method and sends a code, and `.../confirm` confirms it. Codes are 6 digits, hashed and single use. They expire after `guardian.two_factor.one_time_codes.ttl_minutes` (10) and allow at most 5 attempts. Resends are limited to one per 60 seconds per user. Tests use a fake channel.
30. Passkeys. With `web-auth/webauthn-lib` installed:
    - `POST two-factor/passkeys/options` returns registration options. The challenge is kept server side for 5 minutes and bound to the user.
    - `POST two-factor/passkeys` verifies the attestation and stores the credential.
    - `GET two-factor/passkeys` lists them and `DELETE two-factor/passkeys/{passkey}` removes one.
    - The relying party id and name come from `guardian.two_factor.passkeys.rp_id` (default: the host of `app.url`) and `.rp_name`.
    - An assertion whose sign count does not increase, when it is non-zero, is refused and recorded as `passkey.sign_count_mismatch`.
    Without the library, every passkey endpoint answers 501 with a message naming `web-auth/webauthn-lib`, and the status reports passkeys as unavailable. Nothing outside `Domains\TwoFactor\Passkeys\` uses `Webauthn\*` (arch).
31. Status and removal. `GET two-factor` (`guardian.two-factor.show`) answers `enabled`, `methods` (`id`, `type`, `label`, `confirmed_at`, `last_used_at`; never a secret or destination in full, and email or phone masked), `passkeys`, `passkeys_available`, `recovery_codes_remaining`, `required`, `allowed_methods` and `enrolment_deadline`. `DELETE two-factor/methods/{method}` and passkey removal need a current code from another confirmed factor, or a recovery code, in the body. Removing the last factor while 2FA is required answers 422 `two_factor_required`. An admin with `guardian.two-factor.manage` may reset another user's two-factor (`DELETE users/{user}/two-factor`), which is recorded.
32. Challenge. When a user with a confirmed factor signs in, `TwoFactorSession` marks the session pending. `POST two-factor/challenge` with `method` (`totp|recovery|email|sms|passkey`) and `code` or `assertion` answers 204, confirms the session and regenerates its id. `POST two-factor/challenge/send` sends an email or SMS code, and `POST two-factor/challenge/passkey-options` returns assertion options. Failures are limited to 5 per minute per user (429 after that), and every attempt is recorded (`two_factor.challenge_passed|failed`, `two_factor.recovery_code_used`).
33. Middleware alias `guardian.two-factor` (`EnsureTwoFactor`), for authenticated requests:
    - A user whose requirement (criterion 34) is past its deadline, with no confirmed factor, gets 403 `{"code": "two_factor_enrollment_required"}`. Before the deadline the request passes, with a `Guardian-Two-Factor-Deadline` header.
    - A user with a confirmed factor whose session is pending gets 403 `{"code": "two_factor_challenge_required", "methods": [...]}`.
    - A stateless request (no session) from a user with a confirmed factor passes (D-012).
    - Guardian's own enrolment, challenge and status routes are never blocked by it.
34. Enforcement. `guardian_two_factor_policies` holds ULID, nullable `scope_type`/`scope_id`, nullable `role` (slug), `required`, nullable JSON `methods` and `grace_days` (default 0). `two-factor-policies` index, store, update and destroy routes and MCP tools need `guardian.two-factor.manage`. `Contracts\TwoFactorRequirement::for(Authenticatable $user): Requirement` (`required`, `methods`, `deadline`) is bound by default to `PolicyTwoFactorRequirement`:
    - It is required when `guardian.two_factor.required` (default false) is on or any matching policy requires it.
    - A policy matches when it has no scope and no role; when it has a role the user holds (anywhere, or in its scope if it also has one); or when it has a scope and the user holds an assignment in that scope or a scope whose chain includes it.
    - The allowed methods are the intersection of the matching policies' non-null lists. An empty intersection falls back to `totp` and `passkey` and records `two_factor.policy_conflict` once a day.
    - The deadline is the earliest matching policy's `created_at` plus its `grace_days`.
    Hosts may bind their own `TwoFactorRequirement`.

### Sessions and devices

35. Tables: `guardian_devices` (ULID, `user_id`, `fingerprint_hash`, `name`, `first_seen_at`, `last_seen_at`, `last_ip`; unique `user_id, fingerprint_hash`) and `guardian_sessions` (ULID, `user_id`, `device_id`, unique `key_hash`, `ip`, `user_agent`, `last_active_at`, `revoked_at`, timestamps).
36. Middleware alias `guardian.session` (`TrackSession`), for authenticated requests with a session:
    - It keeps a random `guardian.session_key` in the session and a record found by its hash, so the record survives session id regeneration.
    - It touches `last_active_at` at most every `guardian.sessions.touch_seconds` (60).
    - On a revoked record it logs the guard out, invalidates the session and answers 401 `{"code": "session_revoked"}`.
37. On `Login`, Guardian upserts the device (fingerprint: HMAC of the user agent) and the session record. The first time a device is seen for a user who already has another device, Guardian records `device.new` and sends `NewDeviceSignInNotification` through `guardian.devices.notify_via` (default `['mail']`). `guardian.devices.notify` set to false turns the notice off.
38. HTTP and MCP:
    - `guardian.sessions.index` lists your own sessions, with the current one flagged.
    - `guardian.sessions.destroy` revokes one of yours.
    - `guardian.sessions.others.destroy` signs out everywhere else. With `tokens: true` it also revokes your API tokens through the token driver.
    - `guardian.users.sessions.index|destroy` list and revoke all of another user's sessions, with `guardian.sessions.manage`.
    - `guardian.devices.index|destroy` list your devices, or forget one and revoke its sessions.
    Every revocation is recorded as `session.revoked`.

### Password policy

39. `PasswordPolicy::rules(?Authenticatable $user = null): array` returns Laravel rules built from `guardian.passwords`: `min_length` 12, `max_length` 128, `mixed_case`, `numbers`, `symbols` and `letters` (all false), `uncompromised` true with `uncompromised_threshold` 0, `history` 5, and `max_age_days` null. They are `Password::min()->max()` plus the enabled complexity rules, `uncompromised()`, and `NotRecentlyUsed` when a user is given and `history` > 0. Hosts use it in their own registration, reset and change requests.
40. The breach check uses Laravel's `UncompromisedVerifier`, the HIBP range API with a 5-character SHA-1 prefix (k-anonymity) and padding. `tests/Pest.php` calls `Http::preventStrayRequests()`, and the tests bind a fake verifier. No test touches the network. When the HIBP call fails the check passes, which is Laravel's behaviour, and Guardian records `password.breach_check_unavailable`.
41. History. `guardian_password_histories` holds ULID, `user_id`, `password_hash` and `created_at`. An observer on `guardian.users.model` records the new hash when `guardian.passwords.column` (`password`) changes, and records `password.changed`. It keeps the last `history` rows. `NotRecentlyUsed` refuses a password that `Hash::check()` matches against any of them.
42. Rotation. Middleware alias `guardian.password-fresh` (`EnsurePasswordFresh`) answers 403 `{"code": "password_expired"}` when `max_age_days` is set and the latest change is older than that. A user with no history gets a baseline row the first time and is not refused.
43. HTTP: `guardian.password-policy.show` returns the active rules as JSON for UIs. `guardian.passwords.check` (`POST {password}`) answers 200 `{"valid": bool, "errors": [...]}`, includes history for a signed-in caller, is rate-limited, and never logs or records the password. MCP has a `show-password-policy-tool` only.

### API tokens

44. `Domains\Token\Contracts\TokenDriver` has these methods:
    - `issue(Authenticatable $user, string $name, array $abilities, ?CarbonImmutable $expiresAt): IssuedToken`
    - `tokens(Authenticatable $user): array` (a `list<TokenSummary>`)
    - `revoke(Authenticatable $user, string $id): bool`
    - `revokeAll(Authenticatable $user): int`
    `TokenSummary` (`id`, `name`, `abilities`, `lastUsedAt`, `expiresAt`, `createdAt`) and `IssuedToken` (`token`, `plainTextToken`) are final read-only value objects.
45. `TokenManager` picks the driver from `guardian.tokens.driver`:
    - `null` (the default) means no driver: token endpoints answer 501 `{"message": "No API token driver is configured."}` and the MCP tools answer an error.
    - `'sanctum'` selects `SanctumTokenDriver` and `'passport'` selects `PassportTokenDriver`.
    - A class name implementing `TokenDriver` is resolved from the container.
    - A missing package or unknown value throws `TokenDriverException` (a `GuardianException`) on first use.
    Only `Domains\Token\Drivers\SanctumTokenDriver` uses `Laravel\Sanctum`, and only `PassportTokenDriver` uses `Laravel\Passport` (arch). Neither package is required.
46. `SanctumTokenDriver` issues through `createToken()`, with expiry, on users using Sanctum's `HasApiTokens`. It lists the user's personal access tokens, and revoking deletes one. It is tested with `laravel/sanctum`.
47. `PassportTokenDriver` issues personal access tokens with `createToken($name, $abilities)`, where abilities are scopes, and sets expiry when given. It lists the user's tokens that are not revoked, and revoking marks one revoked. It is tested with `laravel/passport`.
48. HTTP:
    - `guardian.tokens.index` lists the caller's tokens.
    - `guardian.tokens.store` answers 201 and returns the plain token once. `name` is required. `abilities` must be within `guardian.tokens.abilities` (default `['*']`), and `expires_at` cannot be later than `guardian.tokens.max_lifetime_days` (default 365).
    - `guardian.tokens.destroy` revokes one of the caller's tokens.
    - `guardian.users.tokens.index|destroy` list and revoke all of another user's tokens, with `guardian.tokens.manage`.
    MCP lists and revokes but cannot issue. Issuing and revoking are recorded as `token.issued` and `token.revoked`, without token values.

### Login protection

49. Throttling and lockout. Middleware alias `guardian.login-throttle` (`ThrottleLogins`), for the host's login route, counts failures from `Failed` events. It keys them by the identifier HMAC (`guardian.login.identifier_field`, default `email`) and by IP. After `guardian.login.max_attempts` (5) failures within `decay_minutes` (15) for an identifier, or `ip_max_attempts` (50) for an IP, it answers 429 `{"message", "retry_after"}` with `Retry-After`, and fires Laravel's `Lockout`. A `Login` clears the identifier's counter.
50. `DELETE lockouts` (`guardian.lockouts.destroy`, `{identifier}`, needs `guardian.security.manage`) clears an identifier's lockout and records `lockout.cleared`. The MCP tool `clear-lockout-tool` does the same.
51. Suspicious sign-ins. A `Login` is suspicious when the device is new to the user and either the identifier had 3 or more failures in the past hour, or the IP's network (/24 IPv4, /48 IPv6) has never been seen for the user. A suspicious sign-in records `login.suspicious` and sends `SuspiciousSignInNotification` in place of the new-device notice. It does not block the sign-in (D-016).
52. IP rules. `guardian_ip_rules` holds ULID, `cidr`, `action` (`allow|deny`), `description`, `expires_at`, `created_by` and timestamps. Middleware alias `guardian.ip` (`EnforceIpRules`) answers 403 `{"code": "ip_denied"}` when a deny rule matches. With `guardian.ip_rules.mode` set to `allowlist` (the default is `denylist`), it also answers 403 when no allow rule matches, and deny wins. It handles IPv4 and IPv6 CIDRs through `IpUtils`, ignores expired rules, caches the rules and refreshes them on write, and records `ip.denied` at most once a minute per IP. `guardian.ip-rules.index|store|destroy` and the MCP tools need `guardian.security.manage`, and an invalid CIDR answers 422.

### Surfaces, OpenAPI, client and docs

53. Every action is reachable over HTTP and MCP except those in `tests/Pest.php`'s `PARITY_EXEMPT`, each with a reason. Exempt from MCP: TOTP enrolment and confirmation, recovery-code regeneration, one-time-code enrolment, sending and confirmation, passkey ceremonies, the challenge actions, token issuance and the password check (secrets, ceremonies or session state).
54. `dedoc/scramble` is in `require-dev` only and configured in `Workbench\App\Providers\WorkbenchServiceProvider`. `openapi.json` is committed: OpenAPI 3.1, `info.version` `2.0.0`, one operation per `guardian.*` API route (`operationId` = route name). It has no SCIM protocol route (`guardian.scim.v2.*`) and no SSO web route. A fresh export equals it as decoded JSON, and two exports are byte-identical. An arch rule says nothing in `RefactorCircus\Guardian` uses `Dedoc\Scramble`.
55. `client/` follows Keen v2:
    - `@refactor-circus/guardian-client` `2.0.0`, ESM, with a committed `dist/`.
    - `openapi-fetch` is the only dependency, and `openapi-typescript` and `typescript` are exact-pinned.
    - `createGuardianClient(options)` requires `baseUrl` and is exported with `GuardianClient`, `paths` and `components`.
    - `composer client:generate` and `bin/client-stamp.php` produce `client/openapi.sha256`.
    - `OpenApiTest` fails on a stale export or stamp, naming `composer client:generate`.
    - A CI `client` job runs the generator and `git diff --exit-code`.
56. `config/guardian.php` has a banner for each new section: `authorization`, `users`, `roles`, `super_admins`, `permission_cache`, `adopt`, `two_factor`, `sessions`, `devices`, `passwords`, `tokens`, `login`, `ip_rules` and `security_log`. `guardian.routes.enabled` stays false. `ConfigTest` asserts every documented default.
57. Docs:
    - The README describes Guardian as the suite's security package (API and MCP, with Atrium v2 as the optional UI). It covers each domain, host wiring (a table of the middleware aliases and which routes take them), token drivers, the passkey suggestion, the OTP channel and `TwoFactorRequirement` bindings, and Keystone's contract binding with `guardian.authorization.bind`. It lists SSO and SCIM's use of Roster as an existing cross-package reference, and has the OpenAPI/client section.
    - `CHANGELOG.md` `[Unreleased]` has `### Breaking`, `### Added` and `### Upgrading to v2.0.0`. The upgrade note covers the Atrium removal, Keystone `^2.0`, and roles moving from Roster: install with Roster v2, run migrate, check the adoption report, then drop the `roster_*` role tables by hand.
    - The Boost skill, `CLAUDE.md` and `AGENTS.md` are updated.
    - `DocsTest` asserts that the README names every middleware alias, `guardian.authorization.bind`, `TokenDriver`, `OneTimeCodeChannel`, `guardian:adopt-roster-roles`, `openapi.json` and `@refactor-circus/guardian-client`, and that none of these docs mention `registerAtriumPlugin` or `guardian.ui`.
58. `composer test` (analyse, lint check, 100% type coverage, unit) passes.

## Design

### Layout

New domains under `src/Domains/`, each with its own provider listed in `DomainServiceProvider`, `routes.php`, `Actions`, `Data`, `Events`, `Models`, `Policies`, `Resources`, `Http/{Requests,Controllers}` and `Mcp/{Requests,Tools}`, following the existing Sso/Scim layout:

- `Role` (roles, assignments, escalation guard, `GuardianRoles`, built-ins, commands).
- `Permission` (permissions, `GuardianPermissions`, cache, `Gate::before`, sync).
- `SecurityEvent` (model, enum, action, listeners, API).
- `TwoFactor` (methods, recovery codes, one-time codes, `Passkeys/`, `TwoFactorSession`, challenge, policies, `Contracts\{OneTimeCodeChannel, TwoFactorRequirement}`, `EnsureTwoFactor`).
- `Session` (devices, sessions, `TrackSession`, notifications).
- `Password` (`PasswordPolicy`, `NotRecentlyUsed`, history observer, `EnsurePasswordFresh`).
- `Token` (`Contracts\TokenDriver`, `TokenManager`, `Drivers\{SanctumTokenDriver, PassportTokenDriver}`, value objects).
- `LoginProtection` (`ThrottleLogins`, `LoginFailures`, `SuspiciousSignIns`, IP rules, `EnforceIpRules`).

`Support\UserKey` and `Support\Users` (`guardian.users.model`, defaulting to `auth.providers.users.model`; `find`, `findByEmail`, `emailVerified` through `MustVerifyEmail` or `email_verified_at`) serve every new domain without Roster. SSO/SCIM keep using Roster's `UserKey` and `Users` (existing reference).

### Scope and users

Scopes are stored as `scope_type` (the model's morph class) and `scope_id` (a string). `GuardianPermissions` loads a user's assignments once per request (`role_id`, scope, permission names, `super`) and answers in memory, walking `ScopeChain`. Organization and team semantics come only from the scope models' `Scope` implementation (Roster's, when installed).

### Bindings

`GuardianServiceProvider::register()`, when `guardian.authorization.bind` is true: `scoped(Permissions::class, GuardianPermissions::class)` and `scoped(Roles::class, GuardianRoles::class)`. Decorators such as Roster's impersonation wrapper still apply (Keystone D-009). It also binds `OneTimeCodeChannel` to `MailOneTimeCodeChannel`, `TwoFactorRequirement` to `PolicyTwoFactorRequirement`, and `TokenManager` as a singleton. Middleware aliases are registered on the router in `boot()`.

### Migrations

All new create migrations use the configured table names and skip a table that already exists. Their `down()` drops only empty tables unless `guardian.migrations.force_rollback` is on, the same rule as the existing ones (D-040 of the first spec). The adoption migration's `down()` does nothing.

### Roster boundary

`Support\Integration` keeps its Roster checks minus `Resources` and `PermissionCatalogue`, and gates only the Sso and Scim providers. `Policies\Policy`, `Http\Requests\Request` and `Mcp\Requests\Request` drop Roster's `Authorizer`. They call Keystone `Authorizer::for($package)->allows()` with the scope a subclass supplies. The Sso/Scim subclasses supply the Roster organization (existing reference), and the new domains supply none or their own. `Upgrade\RosterUpgrade` stops calling Roster's `Permissions::flush()` and calls `Permissions::forget()` on the Keystone contract.

### Public API change

v2.0.0 removes the Atrium plugin, screens, views, `guardian.ui.*` and `guardian.atrium.*`, the `atrium.guardian.*` routes and the without-Atrium/browser suites. It adds everything above. SSO and SCIM routes, tools and config are unchanged.

## Out of scope

- Passwordless sign-in with passkeys, a login endpoint, and any session or login flow of Guardian's own. Guardian protects the host's login; it does not replace it.
- GeoIP and impossible-travel detection (no GeoIP data source in the suite).
- Per-scope IP rules, and deleting rows of the `database` session driver on revocation (the `guardian.session` middleware enforces revocation).
- Dropping Roster's old role tables; changing SSO/SCIM behaviour; removing the existing Roster reference (needs Roster seams, its own spec).
- Publishing the client to npm, and tagging the release.

## Risks and open questions

- **Library support for PHP 8.5 / Laravel 13.** `pragmarx/google2fa`, `web-auth/webauthn-lib`, `laravel/passport` and `dedoc/scramble` must each have a release that installs. If one does not, the builder records a D-entry. A required one (google2fa) is replaced by an in-house RFC 6238 implementation with the RFC test vectors. An optional one stays suggested, and its tests skip while it cannot be installed.
- **Order.** This spec waits for Keystone's authorization addendum and Roster v2 (`Roster/2026-11-23-0943-roster-v2-api-and-mcp-only`, including its RBAC removal). Until Roster's job merges, `refactor-circus/roster` `^2.0` does not resolve.
- **Adoption mapping.** The default scope types are Roster's model class names (no morph map today). A host with a morph map for Roster's models must set `guardian.adopt.scope_types` before migrating. The README says so, and the adoption report names the types it used.
- **2FA membership.** Policies on a scope reach only users with an assignment there. An organization owner with no role there is not covered by that organization's policy. Hosts that need it bind their own `TwoFactorRequirement`.
- **Stateless requests** from enrolled users pass the two-factor middleware (D-012). A token-based client is trusted to have been issued after a full sign-in.
- **Fail-open breach check** during an HIBP outage (D-014), recorded as an event.
- **Large group count.** Twenty groups in one job. Each one ends green, so a stalled job can be resumed from any group.
