# Decisions: Guardian v2: security platform

## D-001: Release, constraints and spec review
- Choice: Release v2.0.0. Every `refactor-circus/*` constraint is `^2.0`, and `dev-main` is aliased to `2.0.x-dev`. `/ring-master-spec-review` is skipped on the user's instruction, so `meta.yml` is `approved`. The pace is build and gate, with no review rounds.
- Rationale: The user said Guardian converts to v2 with the suite and that review is skipped. This supersedes Keystone D-006 / Roster D-020's "Guardian stays on `^0.1`".
- Date: 2026-11-23
- Raised by: user

## D-002: Order with Keystone and Roster
- Choice: This spec waits for `Keystone/2026-11-23-0943-drop-the-atrium-plugin-hook` (authorization addendum) and `Roster/2026-11-23-0943-roster-v2-api-and-mcp-only` (v2 with RBAC removed). Group 1 (Atrium removal) runs on the current `^0.1` dependencies; group 2 moves to `^2.0`.
- Rationale: Roster `^2.0` and Keystone's contract only resolve once those jobs merge. Guardian's current main stays on `^0.1` tags, so it is not broken meanwhile.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-003: What happens to the existing Roster reference (SSO/SCIM)?
- Choice: Kept as it is and flagged. Only `Domains\Sso`, `Domains\Scim`, `Upgrade`, `Support\Integration` and the base Request/Policy classes, through the scope their Sso/Scim subclasses supply, may name Roster. They use the allow-list of today's Roster classes minus the Role/Permission domains. Every new domain is Roster-free (arch test), and `Integration` gates only Sso and Scim.
- Rationale: User instruction: keep the behaviour, flag it, do not widen it. Removing it needs Roster seams and is its own spec.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-004: Which suite packages stay in `require-dev`?
- Choice: `refactor-circus/roster` `^2.0` (SSO/SCIM tests) and `refactor-circus/keen` `^2.0` (audit tests) stay in `require-dev`. `refactor-circus/atrium` is removed everywhere.
- Rationale: Both are tests of existing behaviour. `require` still holds Keystone only.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-005: How are scopes stored for roles and assignments?
- Choice: `scope_type` (the morph class) plus `scope_id` (a string) on assignments. Roles have `scope` (`global`, `any`, or a morph type that limits where they may be assigned) and an optional owner morph (a custom role belonging to one scope). Hierarchy and owner rights come only from Keystone's `Scope` on the scope model.
- Rationale: Guardian must not know Roster's organizations and teams. A morph keeps roles generic, and the adoption maps Roster's columns onto it without losing anything.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-006: Built-in roles
- Choice: `super-admin` (global, super), `admin` (any) and `member` (any), configurable in `guardian.roles.built_in`. Abilities reach them through Keystone `Abilities`' `grantTo`, applied only when the sync creates the role, as Roster did. Roster's team `lead` role is not a built-in; adopted installs keep theirs.
- Rationale: A fresh install with no team scope type cannot create a team role. Keeping "grant on creation only" means an admin's edits survive every deploy.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-007: How are Roster's role tables adopted?
- Choice: Copy, not rename. The copy runs only when Guardian holds no assignments and no roles other than the seeded built-ins. It keeps the ULIDs, maps the scopes through `guardian.adopt.scope_types` (defaults: Roster's organization and team model class names), renames `roster.roles.*`, `roster.sso.*` and `roster.scim.manage` to `guardian.*`, then syncs. It never alters or drops the source tables. It runs as a migration and as `guardian:adopt-roster-roles`, and is idempotent with a report.
- Rationale: Non-destructive, and safe whatever the order of the two packages' migrations. Roster's addendum (D-024 there) leaves the tables for the host to drop. The table and class names appear as strings in `src/Upgrade`, the same departure already recorded for SSO/SCIM.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-008: How does Guardian take over Keystone's contract?
- Choice: `scoped()` bindings of `Permissions` and `Roles` in `register()`, switchable with `guardian.authorization.bind` (default true). With binding on, a `Gate::before` answers `true` or `null` for known permissions and super-admins, and never `false`.
- Rationale: `scoped` because answers are memoized per request (Octane-safe). Returning `null` keeps the app's own gates and policies in play, as Roster did. The switch lets a host keep the Gate-only fallback.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-009: Which TOTP implementation?
- Choice: `pragmarx/google2fa` in `require`, with SHA-1, 6 digits and 30 seconds, ±1 step, and replay refusal through `last_timestep`. Guardian returns an `otpauth://` URI and leaves QR rendering to the UI.
- Rationale: A small, long-maintained library (the one Laravel Fortify uses), which avoids hand-written crypto. Rendering QR codes server side would add a heavier dependency for a UI concern. Fallback if it cannot install: see Risks.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-010: Which WebAuthn library, and is it required?
- Choice: `web-auth/webauthn-lib`, in `suggest` and `require-dev` only. Without it, passkey endpoints answer 501 and the status says so. Only `Domains\TwoFactor\Passkeys\` uses it.
- Rationale: It is the most actively maintained PHP WebAuthn library, from Spomky-Labs, with FIDO2 conformance tests and full attestation and assertion support. It is also heavy, with Symfony components among its dependencies, and many hosts will use TOTP alone, so it stays optional, as Socialite and php-jwt already are for SSO.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-011: How are email and SMS codes delivered?
- Choice: A `OneTimeCodeChannel` contract (`supports()`, `send()`). The default sends email through a mail notification and supports no SMS. A host binds its own channel for SMS, or to replace email. Codes are 6 digits, hashed, valid for 10 minutes, with 5 attempts and one resend per 60 seconds.
- Rationale: The user asked for a host-provided channel. Shipping no SMS provider avoids a vendor choice. Email works out of the box because Laravel mail is always there.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-012: How do challenges and the middleware treat sessions and stateless requests?
- Choice: Challenge state lives in the session (`TwoFactorSession`), and confirming regenerates the session id. A stateless request (no session) from a user with a confirmed factor passes `guardian.two-factor`. An unenrolled, required user past the deadline is refused either way.
- Rationale: The host owns sign-in and token issuance, so Guardian cannot challenge a bearer token. Refusing every stateless request would break the token APIs that hosts use Sanctum or Passport for. This is listed under Risks.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-013: How are enforcement policies combined?
- Choice: Required if global config or any matching policy requires it. A policy matches globally, by role held, or by an assignment in the scope or below it. The allowed methods are the intersection of the matching non-null lists; an empty intersection falls back to `totp` and `passkey` and records `two_factor.policy_conflict`. The deadline is the earliest matching `created_at` plus `grace_days`. Hosts can replace `TwoFactorRequirement`.
- Rationale: The strictest combination, without locking users out when policies conflict. The fallback methods are the standard and the phishing-resistant ones.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-014: How does the breach check work, and what if HIBP is down?
- Choice: Reuse Laravel's `Password::uncompromised()` and `UncompromisedVerifier` (HIBP range API, k-anonymity, padding). Tests bind a fake and `Http::preventStrayRequests()`. An outage passes the check, as Laravel does, and records `password.breach_check_unavailable`.
- Rationale: No new HTTP code to maintain. Failing closed would stop every sign-up and password change while a third party is down, and the event keeps the gap visible.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-015: Password policy defaults
- Choice: Minimum 12 and maximum 128 characters, no composition rules, breach check on, history 5, no rotation (`max_age_days` null).
- Rationale: These follow NIST SP 800-63B: length and breach screening over composition and forced rotation. Rotation and complexity stay available as config.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-016: What counts as a suspicious sign-in, and does it block?
- Choice: A new device plus either 3 or more recent failures for the identifier or a network (/24, /48) never seen for the user. It records an event and notifies the user. It does not block. There is no GeoIP.
- Rationale: Blocking on heuristics would lock out legitimate users. Lockout (criterion 49) is the blocking control. The suite has no GeoIP source.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-017: How are API tokens driven?
- Choice: A `TokenDriver` contract. `guardian.tokens.driver` defaults to `null` (no driver, 501), or takes `sanctum`, `passport` or a class name. The Sanctum and Passport adapters are `require-dev` and `suggest` only, and each is isolated by an arch rule. Abilities are limited by `guardian.tokens.abilities` and lifetimes by `max_lifetime_days` (365). MCP may list and revoke tokens but not issue them.
- Rationale: The user's main app uses Passport and others use Sanctum or something else, so Guardian must force neither. Off by default means no surprise token surface. Agents should never receive a usable token.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-018: How are sessions tracked and revoked?
- Choice: Guardian keeps its own records, keyed by the hash of a random key stored in the session, which survives id regeneration. The `guardian.session` middleware enforces revocation and touches activity at most once a minute. Rows of the `database` session driver are not touched.
- Rationale: It works with every session driver. Storing session ids, which are bearer secrets, is avoided.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-019: What does the security log store, and how does Keen see it?
- Choice: The type, the user, an HMAC of the identifier (never the raw email of a failed attempt), the IP, a truncated user agent and a context free of secrets, pruned after 90 days. Every row is written by `RecordSecurityEventAction`, whose finished event Keen records through Keystone's audit seams.
- Rationale: It is useful for investigations without keeping credentials or unbounded personal data. Going through an action reuses the suite's audit mechanism, with no Keen dependency.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-020: IP rules scope and default mode
- Choice: Global rules only, default mode `denylist`, and deny always wins. Per-scope rules are out of scope.
- Rationale: A denylist by default cannot lock anyone out of a fresh install, and per-scope rules would need membership knowledge Guardian does not have.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-021: Which actions are exempt from MCP parity?
- Choice: TOTP enrolment and confirmation, recovery-code regeneration, one-time-code enrolment, sending and confirmation, passkey ceremonies, the challenge actions, token issuance and the password check. Each is listed in `PARITY_EXEMPT` with its reason.
- Rationale: These carry secrets, need a browser ceremony or session state, or would hand an agent a credential.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-022: Do Guardian's routes turn on by default?
- Choice: No. `guardian.routes.enabled` stays `false`, as in v0.1. The middleware aliases are always registered.
- Rationale: This keeps v0.1 behaviour, and a security API should be opted into. Hosts turn it on to use Atrium v2 or the client.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-023: What does removing a factor need?
- Choice: A current code from another confirmed factor, or a recovery code. Removing the last factor while 2FA is required is refused. Admins with `guardian.two-factor.manage` can reset another user's 2FA, which is recorded.
- Rationale: A stolen session alone must not be able to strip 2FA. The admin reset covers lost devices.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-024: Lockout thresholds
- Choice: 5 failures per identifier and 50 per IP within 15 minutes give a 429 with `Retry-After`. A successful sign-in clears the identifier's counter, and an admin can clear it too.
- Rationale: These match Laravel's usual login throttling while adding an IP ceiling against credential stuffing. Lockouts are temporary, so there is no permanent account lock to manage.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-025: Group order
- Choice: A linear chain (each group depends on the previous one). Atrium is removed first on the old dependencies, then the move to `^2.0` with SSO/SCIM on Keystone's contract, then RBAC, adoption, the security log (so later domains can record events), 2FA, sessions, passwords, tokens, login protection, OpenAPI/client and docs.
- Rationale: Many groups share `config/guardian.php`, `GuardianServer::TOOLS`, `DomainServiceProvider` and `tests/Pest.php`, so parallel groups would collide. Each group ends green.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because no user was available ("choose sensible conservative defaults")

## D-026: Guardian's authorization switch and Keystone's `Authorizer`
- Choice: Guardian's requests, MCP requests and policies check through `Support\Authorization`, which asks Keystone's bound `Permissions` (the same call `Keystone\Auth\Authorizer::allows()` makes when on), not through `Authorizer::for($package)`. The switch is `guardian.authorization.enabled` (default true; tests turn it off for surface tests, as they did `roster.authorization`). Guardian's Keystone `Package` keeps its default (no `authorization()`), as in v0.1.
- Rationale: Keystone's `Authorizer::enabled()` reads `{package}.authorization` and is on only when it is exactly `true`, while criteria 9 and 56 make `guardian.authorization` a config section (`bind`). Going through `Authorizer::for()` would silently turn every Guardian check off the moment the section exists. Asking `Permissions` directly keeps the check fail-closed and the contract the same.
- Date: 2026-11-23
- Raised by: implement (group 2); conservative default, no user available

## D-027: What runs without Roster, and what `about` says
- Choice: Guardian is always active. Without Roster only SSO and SCIM are skipped: `DomainServiceProvider` registers their providers after `rosterInstalled()`, the MCP server leaves out their tools, and their Roster-backed migrations (SSO, SCIM, the upgrade) move to `database/migrations/roster`, loaded and published only with Roster, as is `guardian:upgrade-from-roster`. Policies, the MCP server, Cortex, the not-found responses and `database/migrations` always register. `php artisan about` shows `Status: Active` plus `SSO and SCIM: Active | Inactive: install refactor-circus/roster | Inactive: needs a newer refactor-circus/roster`.
- Rationale: Criterion 3. A separate migration directory (rather than an early return inside the migrations) means an install that adds Roster later still gets the SSO and SCIM tables on its next `migrate`; the files keep their names, so installs that already ran them see no change.
- Date: 2026-11-23
- Raised by: implement (group 2); conservative default, no user available

## D-028: Requests still reach the swappable policies
- Choice: `Support\Authorization::check()` asks the Gate policy of the model registered for the ability's prefix (`guardian.sso` => `SsoConnectionModel`, `guardian.scim` => `ScimTokenModel`, registered by the SSO and SCIM providers) as the camel-cased verb, and the bundled policies answer with `permits()` (Keystone's `Permissions`). Abilities with no registered resource go straight to `permits()`. Lists across organizations use `Permissions::scopesWith()` plus the organizations whose own `Scope` rule (their owner) grants the ability.
- Rationale: The http/authorization standard and v0.1 let a host swap a policy in `guardian.policies` and have the API and MCP follow it (an existing test proves it). Roster's `Resources` registry did the routing before; a Guardian-local map keeps that behaviour without a Roster seam. The final answer is still Keystone's contract, as criterion 5 asks.
- Date: 2026-11-23
- Raised by: implement (group 2); conservative default, no user available

## D-029: New third-party packages are declared before they can be installed
- Choice: `composer.json` gets criterion 1's full shape in group 2 (`pragmarx/google2fa` in `require`; `dedoc/scramble`, `laravel/sanctum`, `laravel/passport`, `web-auth/webauthn-lib` in `require-dev`), but they are not installed in the job worktree yet: its `vendor/` holds the suite's v2 packages as git-archive exports, and `composer update` cannot resolve `refactor-circus/* ^2.0` until the v2 tags exist. The groups that use them must install them (or the run waits for the tags).
- Rationale: ComposerTest asserts criterion 1 now (task 2.1); no code uses those packages before groups 8 onward.
- Date: 2026-11-23
- Raised by: implement (group 2)

## D-030: Test doubles until Guardian's role store exists
- Choice: Until group 4, `TestCase` binds Keystone's `FakePermissions` and `FakeRoles` with `super-admin` (global), `admin` (global, and in Roster organizations and teams) and `member` (organizations and teams); `admin` grants what Keystone `Abilities` grant it. `grant()` in tests/Pest.php assigns through them. The upgrade's grant tests use Roster v0.1's role tables from `tests/Fixtures/roster-roles` (Roster v2 no longer creates them), run only by the tests that need them.
- Rationale: Task 2.1. Keeping the fixture out of the base migrations means the adoption (group 5) never sees source tables unless a test asks for them.
- Date: 2026-11-23
- Raised by: implement (group 2)

## D-031: Rolling back the role tables, and what group 3 adds early
- Choice: The role tables migration's `down()` refuses (with `RollbackRefusedException`) while anyone holds a role or any non-`system` role or permission exists, unless `guardian.migrations.force_rollback` is on; the seeded system rows alone do not hold it back. Group 3 also adds minimal `RolePolicy`, `RoleAssignmentPolicy` and `PermissionPolicy` (view/manage/assign through `permits()`) to `guardian.policies`, since the arch rule needs a policy for every model; groups 5 to 7 extend them. Guardian's eleven abilities are defined by `RoleServiceProvider` (always, with or without Roster), so the SSO and SCIM providers no longer define theirs.
- Rationale: "Drops only empty tables" (spec Design, D-040 of the first spec) would make every fresh install unable to roll back, because the migration seeds built-ins. Treating seeded rows as not-data keeps the protection for what an admin made.
- Date: 2026-11-23
- Raised by: implement (group 3); conservative default, no user available
