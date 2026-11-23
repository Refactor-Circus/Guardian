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

## D-032: Details of Guardian's role store
- Choice: (1) `allows()` checks, in order: guest (false), super-admin (true, for any ability), a `Scope` in the chain granting by itself (true), no permission row for the ability (false), then the grants. (2) `scopesWith()` returns the stored `scope_id` strings; a scope type given as a model class is turned into its morph class. (3) `abilities()` lists permission rows the user holds (what a scope grants by itself is not listed, as Keystone's fakes do). (4) `GuardianPermissions` and `GuardianRoles` are always bound `scoped()` under their own names, and `Permissions`/`Roles` resolve to those instances when `guardian.authorization.bind` is on, so Roster's `extend()` decorator wraps the same per-request instance. The switch is read in `register()`, as an app's config is; the test case sets it in `resolveApplicationConfiguration()` because Testbench registers providers before `defineEnvironment()`. (5) Model events forget: an assignment forgets its user (cache entry too), a role everyone's grants, a permission the names as well. `revoke()`/`revokeEverywhere()` delete through the models so these fire. (6) A scope's own role wins over a shared role with the same slug in `assign()`.
- Rationale: Criteria 10 to 14 leave these open. Each choice keeps answers consistent with Keystone's `FakePermissions` and Roster v0.1, so packages see no change when Guardian replaces them.
- Date: 2026-11-23
- Raised by: implement (group 4); conservative default, no user available

## D-033: How the API and MCP name scopes and roles
- Choice: Every role request names its scope as `scope_type` (a morph alias, or an Eloquent model class) plus `scope_id`, resolved by `Support\Scopes` (any Eloquent model; an unknown type or record is a 422 on `scope_type`, a type without an id a 422 on `scope_id`). Roles are named by slug (`roles/{role}`), seen from the named scope: that scope's own role first, then a shared one (`global` before the others). A role's own `scope` (`global`, `any` or a scope type) is a separate field. A scope's own role may be assignable `any` or with the owner's type, never `global`; a slug is unique per owner. Viewing (`index`, `show`) is checked in the named scope (`show` falls back to the role's owner); managing (`store`, `update`, `destroy`) in the role's owner, so a shared role is managed only globally. `Support\Authorization` routes `guardian.roles.*` to `RolePolicy`, which gains `assign()`. The escalation guard asks Keystone's `Permissions::allows()` per permission in the role's owner (so a scope that grants by itself counts) and `isSuperAdmin()` for super roles; it is skipped without an actor or with `guardian.authorization.enabled` off, as Roster's was.
- Rationale: Criteria 15 to 18 and task 5.3 (`slug`, not `id`) leave the addressing open. Morph types keep Guardian Roster-free (D-005); checking manage in the owner stops a scope admin from editing roles every scope shares.
- Date: 2026-11-23
- Raised by: implement (group 5); conservative default, no user available

## D-034: Assignments and user abilities over the API
- Choice: `POST role-assignments` takes `user` (the user's key), `role` (a slug) and the scope (`scope_type`/`scope_id`); the role is the one `Roles::assign()` would pick there (the scope's own first, then a shared one assignable there), and a role that cannot be held there, an unknown user or an assignment that already exists is a 422 on `role`/`user`. `DELETE role-assignments/{assignment}` takes the assignment's ULID (the resource's `id`, which assignments keep because they have no slug) and is checked, and escalation-guarded, in the assignment's scope (global when that scope record is gone). `GET role-assignments` is checked in the scope named by `scope_type` and `scope_id` together; `scope_type` alone filters every scope of that type and is checked globally. `GET users/{user}/abilities` answers the bound `Permissions::abilities()` (null when a store cannot list them) and `isSuperAdmin()`, and everyone may list their own (`self()`), as a front end needs to know what to show. `guardian:grant-super-admin` syncs the built-ins first and assigns `super-admin` through `GuardianRoles`.
- Rationale: Criteria 16, 17 and 19 leave the shapes open; these mirror Roster v0.1's rules on Guardian's scope morphs (D-033).
- Date: 2026-11-23
- Raised by: implement (group 6); conservative default, no user available

## D-035: Permissions over the API
- Choice: Permissions are global and named by their name in the route (`permissions/{permission}`); the resource has no `id`. Listing needs `guardian.roles.view`, creating, updating and deleting `guardian.roles.manage`, all checked globally through `RolePolicy` (the `guardian.roles` resource). An app permission may be renamed (unique, dotted lowercase) and roles keep granting it; a `system` permission keeps its name and cannot be deleted, but its description may change. Deleting detaches it from every role. The permission model's events forget every remembered answer, names included (D-032); the delete action forgets again after the pivot rows go. `search` matches a plain substring (LIKE wildcards in it are not escaped, as in Roster).
- Rationale: Criterion 16 ("a `system` permission cannot be deleted or renamed") implies app permissions can be renamed, which Roster v0.1 did not allow; the rest keeps Roster's behaviour.
- Date: 2026-11-23
- Raised by: implement (group 7); conservative default, no user available

## D-036: Details of the role adoption
- Choice: (1) "Already adopted" means a Guardian role keeps one of Roster's role ULIDs; it is checked before "Guardian holds roles", so a second run reports it. (2) "Only the seeded built-ins" means no assignment and every role a shared `system` role whose slug is in `guardian.roles.built_in`; extra permissions do not block adoption. (3) A Roster permission whose Guardian name already exists (the synced system rows, or `guardian.*` names `guardian:upgrade-from-roster` already wrote into Roster's tables) maps onto Guardian's row, which keeps its id; any other is copied with its ULID. Grants are de-duplicated. (4) Rows are written with the query builder in one transaction, then `BuiltInRoles::sync()` runs and `GuardianPermissions::forgetEveryone()` drops what was remembered. (5) The report is its own value object, `Upgrade\AdoptionReport` (outcome, counts, renames, the scope types used), not an extension of the SSO/SCIM `UpgradeReport`, whose "nothing to upgrade" line speaks of SSO and SCIM. (6) The command is registered whether or not Roster is installed: Roster v0.1's tables need no Roster classes. (7) A scope type in `guardian.adopt.scope_types` that names an Eloquent model is stored as its morph class; anything else is stored as given (a morph alias).
- Rationale: Criteria 20 and 21 leave these open; each keeps the adoption non-destructive and idempotent (D-007).
- Date: 2026-11-23
- Raised by: implement (group 8); conservative default, no user available

## D-037: Details of the security event log
- Choice: (1) `RecordSecurityEventAction` takes the raw identifier and hashes it itself (HMAC-SHA256 of it trimmed and lowercased, keyed by `app.key` as configured); its starting event carries only the type and the user key, and its finished event the stored row, so neither the identifier typed nor a pre-scrub context reaches an audit log. (2) `SecretlessContext` drops, at any depth, every context key containing `password`, `code`, `secret`, `token`, `recovery`, `otp`, `assertion`, `credential` or `remember`, and turns non-scalar values into their type name. (3) The action is listed in `PARITY_EXCEPTIONS` (HTTP and MCP): nobody may write the log through the API. (4) `guardian.security_log` has `enabled` (Laravel's auth events; Guardian's own are always recorded), `retention_days` (90; null keeps everything) and `identifier_keys` (`email`, `username`: where a failed sign-in's or lockout's identifier is read from). (5) Lists are cursor-paginated (default 50, max 200), newest first; `GET me/security-events` and the `list-my-security-events-tool` force the user filter to the caller and need only a signed-in user (`self()`); `show` lets a user read their own events. `guardian.security.*` checks go through `SecurityEventPolicy` (the `guardian.security` resource). (6) The authorization fixtures mark self-service routes and tools, which a signed-in user without permissions may use.
- Rationale: Criteria 22 to 25 and D-019 leave these open; each keeps credentials out of every store and payload.
- Date: 2026-11-23
- Raised by: implement (group 9); conservative default, no user available

## D-038: Details of TOTP, recovery codes and the two-factor status
- Choice: (1) A TOTP secret is 160 random bits (32 base32 characters). A user has at most one TOTP method waiting for confirmation (a new enrolment replaces it), so `POST two-factor/totp/confirm` takes only `code`; several confirmed authenticators may coexist. Replay protection moves `last_timestep` forward with a conditional update, so only one request can use a step. (2) Recovery codes use a 31-character alphabet without look-alikes (~49 bits), are stored as HMAC-SHA256 keyed by the app key (`Support\CodeHash`), and are checked against every unused code with `hash_equals`; a confirmed factor gets them only when the user held no other confirmed factor; they are deleted with the last factor. Regenerating needs two-factor on (422 `two_factor_not_enabled`) but no code, as criterion 28 says. (3) Removing a method that is still unconfirmed needs no code. For a confirmed one, the last-factor rule is checked first (422 `two_factor_required` before any recovery code is used up), then the code must prove another confirmed factor (never the one being removed) or be a recovery code, otherwise a 422 on `code`. (4) `GET two-factor` is the caller's own. The MCP `show-two-factor-tool` takes an optional `user`; another user's status needs `guardian.two-factor.manage`. `methods` lists unconfirmed methods too (`confirmed_at` null). Email destinations are masked to their first character and domain, phone numbers to their last two digits. `allowed_methods` is the requirement's methods that this install can offer. (5) `Contracts\TwoFactorRequirement`, `Data\Requirement` and `PolicyTwoFactorRequirement` are added now, with only `guardian.two_factor.required` until group 14, so the status and the last-factor rule can answer `required`. A requirement with no deadline is due at once. (6) Refusals with a machine-readable code are `TwoFactorException` (a `GuardianException`) and render `{message, code}`; a wrong code is a validation error on `code`. (7) The self-service routes (`two-factor.*` for the caller) need a signed-in user, even with `guardian.authorization.enabled` off. The authorization fixtures' self-service checks assert "not 401 or 403", because these routes answer 201, 404 or 422 to a bare call. (8) MCP gets the status, own-method removal and admin reset tools. Enrolment, confirmation and recovery-code regeneration are MCP-exempt (D-021). (9) `guardian_passkeys.credential_id` is a base64url `string(512)` with a unique index, so the index fits MySQL's key limit; longer credential ids are refused at registration. (10) Keystone's `AuditHooks` redacts `secret`, `destination` and `code_hash`, so an installed audit log never holds them.
- Rationale: Criteria 26 to 28 and 31, D-009 and D-023 leave these open; each keeps secrets out of storage, answers and logs, and keeps a stolen session from stripping two-factor.
- Date: 2026-11-23
- Raised by: implement (group 10); conservative default, no user available

## D-039: Details of email and SMS codes
- Choice: (1) `POST two-factor/one-time-codes` creates the unconfirmed method (replacing one of that type still waiting) and sends a code; calling it again is the resend. `.../confirm` takes `{type, code}`, since one method of each type waits at a time. Its 201 answer is the method with the destination masked. (2) Codes are six digits from `random_int`, stored as HMAC-SHA256 keyed by the app key and bound to their method, and compared with `hash_equals`. A new code deletes the method's unused ones. A wrong code uses one of the code's five attempts, and the attempt is written outside the confirming transaction so a refusal cannot roll it back. Consumption is a conditional update, so a code works once even under a race. (3) The one-per-minute limit is a `RateLimiter` key per user (`guardian-two-factor-send:{user}`) and covers every send, enrolment and (in group 13) challenge alike; a refusal is a 429 `too_many_attempts` with `retry_after` and `Retry-After`. (4) `MailOneTimeCodeChannel` sends an on-demand `OneTimeCodeNotification` to the enrolled address with `notifyNow()`, so the code never sits in a queue. (5) An email destination must be a valid address, and an SMS one an E.164 number (`+` and 7 to 15 digits). (6) A sent code also proves a factor when removing another one (D-023), and `available()` lists `email`/`sms` only when the bound channel supports them. (7) Enrolment and confirmation are MCP-exempt (D-021).
- Rationale: Criterion 29 and D-011 leave these open; each keeps codes single use, short-lived, rate-limited and out of storage and logs.
- Date: 2026-11-23
- Raised by: implement (group 11); conservative default, no user available

## D-040: Details of passkeys
- Choice: (1) `PasskeyCeremony` (the only class naming `Webauthn\*`) builds the options with the library and answers them as the JSON a browser's WebAuthn API takes, under `data`. It offers ES256 and RS256, prefers user verification and resident keys, uses `none` attestation, and excludes the user's existing passkeys. The serialized options are kept in the default cache store for 300 seconds under a key per ceremony and user (`PasskeyChallenges`), and pulled on use, so each challenge works once and only for the user it was issued to. (2) Responses are checked by the library's ceremony steps with the relying party id `guardian.two_factor.passkeys.rp_id` (default: the host of `app.url`) and an allowed-origins list, `guardian.two_factor.passkeys.origins`. That list is a config key beyond criterion 30; it defaults to the origin of `app.url`, so plain-HTTP local development works without the library's HTTPS rule. (3) The WebAuthn user handle is an HMAC-SHA256 of the user's key (keyed by the app key), so authenticators learn nothing about the account. It is checked on every assertion. (4) A passkey is a confirmed factor as soon as it is registered (recovery codes when it is the first factor, `two_factor.enabled` recorded). Its credential id (base64url, at most 512 characters) and COSE public key are stored but never answered by the API or written to the audit log. A credential id already registered is refused. (5) Sign counts follow the library: a counter that does not increase is refused, unless both the stored and the new counter are zero (authenticators that keep none). The refusal is recorded as `passkey.sign_count_mismatch`, and the stored counter moves forward only through a conditional update. (6) Removing a passkey follows D-023 and D-038. The proof may also be an `assertion` by another passkey, made against assertion options; the endpoint that issues them arrives with the challenge (group 13). (7) Without the library, `PasskeysAvailable` (a class check, which tests replace) makes every passkey endpoint, action and MCP tool answer 501 `passkeys_unavailable` with a message naming `web-auth/webauthn-lib`. The status then shows `passkeys_available: false`, and `passkey` is not offered. (8) Tests use a software authenticator (`tests/Fixtures/webauthn/SoftAuthenticator.php`, P-256 keys from OpenSSL and a minimal CBOR encoder) instead of recorded ceremonies: the server's challenge is random per run, and fresh ceremonies run every library check offline. (9) Registration and its options are MCP-exempt; listing and removal have tools.
- Rationale: Criterion 30 and D-010 leave these open; each keeps ceremonies single use, bound to the user and verified by the library, and keeps the package working without it.
- Date: 2026-11-23
- Raised by: implement (group 12); conservative default, no user available

## D-041: Details of the challenge and the two-factor middleware
- Choice: (1) `TwoFactorSession` keeps two marks in the session, `guardian.two_factor.pending` and `guardian.two_factor.confirmed`, each holding the user's key so neither carries over to another user. Every `Login` clears both, then marks the session pending when the user holds a confirmed factor. A session "owes a challenge" when the user holds a factor and it is not confirmed for them, so a session from before the user enrolled is challenged too. A request without a session never owes one (D-012). (2) While a session owes a challenge, Guardian's own routes that add, remove or replace a factor (TOTP and one-time-code enrolment and confirmation, passkey options and registration, recovery-code regeneration, method and passkey removal) answer 403 `two_factor_challenge_required`. Someone holding only the password can therefore not enrol their own factor and pass the challenge with it. Status, passkey listing and the challenge routes stay open, and the middleware never blocks any `guardian.two-factor.*` route. (3) Enrolling a first factor in a session that owed no challenge marks it confirmed: the user just proved the factor. (4) `POST two-factor/challenge` needs a session (409 `session_required` otherwise) and confirms it with `regenerate()`. A wrong answer is a 422 and counts towards 5 failures a minute per user (a `RateLimiter` key per user); the next attempt then answers 429 `too_many_attempts` with `Retry-After`. Every attempt is recorded with only the method kind; a recovery code also records `two_factor.recovery_code_used`. `challenge/send` uses the one-per-minute send limit (D-039) and the user's oldest confirmed method of that type. `challenge/passkey-options` issues assertion options for all of the user's passkeys, which also serve the passkey proof for removal (D-040). (5) `EnsureTwoFactor` checks the challenge first, then enrolment. When two-factor is required, only confirmed factors of the requirement's allowed methods count towards enrolment. A due requirement answers 403 `two_factor_enrollment_required` (with the methods that could be enrolled), and one not yet due passes with `Guardian-Two-Factor-Deadline`. The challenge answer lists the user's confirmed factor types plus `recovery` while codes remain. (6) Middleware aliases are registered in `GuardianServiceProvider::registerMiddleware()`, whether or not the API routes are on. (7) The challenge actions are MCP-exempt (D-021). Tests give Guardian's routes `StartSession` as a host's `web` group would, and sign in through a test login route.
- Rationale: Criteria 32 and 33 and D-012 leave these open; (2) closes the gap where a pending session could add the factor it is then challenged for.
- Date: 2026-11-23
- Raised by: implement (group 13); conservative default, no user available

## D-042: Details of two-factor enforcement policies
- Choice: (1) A policy's scope is given as `scope_type`/`scope_id` (resolved by `Support\Scopes`, so a morph alias or a model class, D-033) and stored as the model's morph class and key. `role` must be an existing role slug, `methods` a non-empty list of `totp`, `email`, `sms` and `passkey` (or null for any), `grace_days` 0 to 3650, and `required` defaults to true. Updating changes only `required`, `methods` and `grace_days`; whom a policy covers is fixed. (2) Matching: a policy with neither scope nor role covers everyone. One with a role covers holders of that role by slug, anywhere. One with a scope covers users with any assignment in that scope or in a scope whose `ScopeChain` includes it (global assignments do not count). One with both covers holders of the role whose assignment is in that scope or beneath it, the conservative reading of "in its scope", since it reaches more users. (3) `required` is the config switch or any matching policy with `required` true. A policy with `required` false still narrows the methods. The methods are the intersection over every matching policy with a non-null list. An empty intersection falls back to `totp` and `passkey` and records `two_factor.policy_conflict` (with the ids of the limiting policies) at most once per user per day, through a cache key. The deadline is the earliest `created_at` + `grace_days` among the requiring policies; under the config switch it is null, so the requirement is due at once. (4) The CRUD routes are `guardian.two-factor-policies.index|store|update|destroy` (`PATCH` for update). They and their four MCP tools need `guardian.two-factor.manage`, checked globally; `TwoFactorPolicyPolicy` is the model's policy in `guardian.policies`. (5) `PolicyTwoFactorRequirement` stays bound with `bindIf`, so a host's binding replaces it.
- Rationale: Criterion 34 and D-013 leave these open; each picks the stricter reading where the spec is ambiguous, without locking users out.
- Date: 2026-11-23
- Raised by: implement (group 14); conservative default, no user available

## D-043: Details of sessions and devices
- Choice: (1) The session key is 32 random bytes (hex) kept under `guardian.session_key`; the record stores an HMAC-SHA256 of it keyed by the app key. Every `Login` with a session issues a fresh key and record (a remember-me sign-in too); `guardian.session` starts a record for a signed-in session that has none (signed in before tracking began, or whose record belongs to another user), without a new-device notice. (2) A device is the user's user agent: `fingerprint_hash` is an HMAC-SHA256 of it keyed by the app key, `name` is the user agent cut to 255 characters. Devices are noted on every `Login` with a request, even without a session; `device.new` (context: the device id) and the notice need a user who already has another device, and the notice goes only to a user with `notify()`. (3) A `Logout` closes the session's record (`revoked_at`) without recording `session.revoked`, which is kept for revocations. (4) A revoked session is signed out of every `session`-driver guard, the session invalidated and its CSRF token regenerated, and the answer is 401 `{message, code: "session_revoked"}`. (5) Revocation is a conditional update per session, and each revoked one is recorded as `session.revoked` for its owner with `{session, by}` (and `device` when a device was forgotten). Lists show only active sessions (most recently active first) and devices (most recently seen first), page-numbered (default 50, max 200). The resource is flat (`device_id`, `device_name`) and `current` compares the request's key hash, so over MCP no session is current, and `revoke-other-sessions-tool` revokes every session of the caller. (6) `tokens: true` resolves the token driver before anything is revoked: with none, the call answers 501 `No API token driver is configured.` and revokes nothing (it never claims tokens were revoked). Until group 17 the driver is whatever is bound to `TokenDriver`; the contract, `TokenSummary`, `IssuedToken` and `TokenDriverException` arrive with this group for that reason. Sign-out-others answers 200 `{revoked, tokens_revoked}` (null without `tokens`), an admin's revoke-all 200 `{revoked}`. (7) Forgetting a device revokes its active sessions, detaches its sessions and deletes it, so the next sign-in from it is a new device. (8) The own-session routes and tools need only a signed-in user; `guardian.users.sessions.*` and their tools need `guardian.sessions.manage` (`SessionPolicy`, the `guardian.sessions` resource), checked globally.
- Rationale: Criteria 35 to 38 and D-018 leave these open; each keeps keys and fingerprints out of storage and answers, and never reports a revocation that did not happen.
- Date: 2026-11-23
- Raised by: implement (group 15); conservative default, no user available

## D-044: Details of the password policy
- Choice: (1) `PasswordPolicy::rules()` answers a list to spread into a host's rule array: the `Password` rule (min, max, the complexity rules switched on, `uncompromised($threshold)`) and `NotRecentlyUsed` for a user while `history` > 0. `NotRecentlyUsed` also refuses the user's current password. (2) The breach check stays Laravel's: Guardian extends the container's `UncompromisedVerifier` only when it is Laravel's own `NotPwnedVerifier`, replacing it with `BreachCheck`, the same HIBP range call (5-character prefix, `Add-Padding`) through Laravel's HTTP client, which passes when the call throws or answers unsuccessfully and records `password.breach_check_unavailable` with no password, prefix or context. A verifier a host or test binds is used untouched. A `StrayRequestException` is rethrown, so a test that forgets to fake HIBP fails; `tests/Pest.php` turns on `Http::preventStrayRequests()` for every feature test. (3) The observer watches `guardian.users.model` (else the auth provider's model): a created user's hash is the first history entry (no event), a change of `guardian.passwords.column` adds an entry and records `password.changed`. Entries are trimmed to `history`, but at least one is kept so rotation knows the last change. A password reset saves the user, so the observer covers it; the existing `PasswordReset` listener keeps recording `password.reset`. (4) `guardian.password-fresh` compares the latest entry's `created_at` with `max_age_days`; a user without one gets a baseline (their current hash, dated now). It answers 403 `{message, code: "password_expired"}`. (5) `GET password-policy` and `POST passwords/check` are public (a sign-up form has no user yet); the check uses the caller's history when signed in and is limited per user, or per address for guests, to `guardian.passwords.check_per_minute` (10, a config key beyond criterion 39), answering 429 `too_many_attempts` with `Retry-After`. `CheckPasswordAction`'s events carry only the user and the outcome; `CheckPasswordData` redacts itself in dumps. (6) The MCP `show-password-policy-tool` needs a signed-in caller like every tool; the check is MCP-exempt (D-021). `PasswordHistoryModel` gets `PasswordHistoryPolicy` (`guardian.security.manage`), never used by a route. (7) The suite's `TestCase` now sets `auth.providers.users.model` to the workbench `User`, as a host's config does, so the observer watches it.
- Rationale: Criteria 39 to 43 and D-014/D-015 leave these open; each keeps passwords out of every store, log and event, and fails open only where the spec says so.
- Date: 2026-11-23
- Raised by: implement (group 16); conservative default, no user available

## D-045: Details of API tokens and their drivers
- Choice: (1) `laravel/sanctum` 4.5.0 and `laravel/passport` 13.12.0 were already installed in `require-dev` and named in `suggest` (group 2): no composer change. (2) `TokenManager` (a singleton) reads `guardian.tokens.driver` on every call, so nothing about tokens is decided while the app boots; whether a driver's package is installed is asked of `Support\TokenPackages` (Composer's `InstalledVersions`, replaceable in tests). A value that is not `sanctum`, `passport` or an instantiable class implementing `TokenDriver` (an interface name included) throws `TokenDriverException::unknown()`; a missing package `missingPackage()`; a user model the adapter cannot serve `unsupportedUser()`; all three are host misconfigurations and answer 500. No driver answers 501 `{"message": "No API token driver is configured."}`, and over MCP that message is the error. (3) Sanctum: issued through `createToken($name, $abilities, $expiresAt)`; listed from Sanctum's configured token model while unexpired; revoking deletes. Passport: the user must be `OAuthenticatable`; abilities are Passport scopes, which the host defines with `Passport::tokensCan()`; an expiry given is applied by setting Passport's personal access token lifetime for that one `createToken()` and putting it back, so the JWT and the stored `expires_at` agree; listing skips revoked and expired tokens; revoking marks them revoked. Passport tokens have no last-used time (`last_used_at` is null). (4) `abilities` must each be in `guardian.tokens.abilities` (literally: with the default `['*']` only `*` may be asked for), and a token asked for without any gets the whole list. `expires_at` must be in the future and no later than `max_lifetime_days` from now; a token asked for without one expires at that limit (never, when the limit is null), so no token outlives the configured maximum. `IssueTokenAction` checks both again, whatever its caller. (5) `POST tokens` answers 201 `{data: token, plain_text_token}`; lists answer `{data: [...]}` unpaginated (drivers return whole lists). Revoking a token the caller does not hold is a 404 (`Not found.` over MCP); an admin's revoke-all answers 200 `{revoked}`. (6) The security log context holds the token's `id` and `name` (the `token` key would be stripped by `SecretlessContext`), `by` for the actor, and for a revoke-all one event with `{all, count}` when anything was revoked. The issuing events carry the Data (name, abilities, expiry) and the `TokenSummary`, never the value. (7) Own-token routes and tools need only a signed-in user; `guardian.users.tokens.*` need `guardian.tokens.manage`, checked through `Permissions` (Guardian stores no token model, so no policy is registered for it). Issuing is MCP-exempt. (8) `RevokeOtherSessionsAction` now asks `TokenManager` (D-043's temporary container lookup is gone). The tests use fixture users (`tests/Fixtures/Users/{SanctumUser,PassportUser}.php`) rather than adding either trait to the workbench user, since one model cannot use both packages' `HasApiTokens`.
- Rationale: Criteria 44 to 48 and D-017 leave these open; each keeps token values out of every store and event, keeps both packages optional, and never lets a token outlive the configured limit.
- Date: 2026-11-23
- Raised by: implement (group 17); conservative default, no user available

## D-046: Details of login protection and IP rules
- Choice: (1) Failures are counted in Laravel's `RateLimiter` (the default cache store) from every `Failed` event, under keys holding the identifier's HMAC (`IdentifierHash`, so `Ada@Example.com` and `ada@example.com` count together and the typed value is never stored) and the address. Three counters: the identifier's and the address's lockout windows (`decay_minutes`), and the identifier's failures in the past hour. A `Login` clears only the identifier's lockout counter; the one-hour counter runs out on its own, so the suspicious check never depends on listener order. (2) `guardian.login-throttle` refuses before the login runs: 429 `{message (Laravel's auth.throttle), retry_after}` with `Retry-After`, firing `Lockout` (recorded as `login.locked_out` by the security log's listener) on each refused attempt. (3) `DELETE lockouts` takes `{identifier}` and lifts only that identifier's lockout (not the address's, nor the one-hour count); `lockout.cleared` stores the identifier's HMAC and `by`, and the action's events carry only the HMAC; it answers 204. (4) A sign-in is checked for suspicion only when the device is new and the user already has another device, the same condition as the new-device notice it replaces, so a user's very first sign-in is never flagged. The network signal compares the address's /24 or /48 with every other session record and device address of the user. `login.suspicious` holds `{device, recent_failures, new_network}` (scalars, as `SecretlessContext` keeps them); `device.new` is still recorded. The suspicious notice goes through `guardian.devices.notify_via` even when `guardian.devices.notify` is off, as a security alert. (5) An IP rule's `cidr` is an IPv4 or IPv6 address or CIDR range (mask 0 to 32 or 128), `action` `allow|deny`, an optional description and future `expires_at`; `created_by` is the caller. Rules are cached forever in the default store under `guardian.ip_rules` with their expiry, filtered per request, and forgotten on every rule's `saved`/`deleted` lifecycle event. `guardian.ip` answers 403 `{message, code: "ip_denied"}` and records `ip.denied` (with the address) at most once a minute per address through `Cache::add`. The CRUD routes are `guardian.ip-rules.index|store|destroy` (page-numbered list); `IpRulePolicy` and the API and tools ask `guardian.security.manage`, globally. (6) `GuardianServer::TOOLS` passes 50 (51), beyond `search_tools`' 50-a-call limit with no offset; `toolCatalog()` in `tests/Pest.php` pages as a client would, by searching each word of the tool names seen until nothing new turns up, and the SCIM and Standalone catalog tests use it.
- Rationale: Criteria 49 to 52 and D-016/D-020 leave these open; each keeps identifiers out of storage and events, never blocks on a heuristic, and lets no rule change wait for a cache to expire.
- Date: 2026-11-23
- Raised by: implement (group 18); conservative default, no user available
