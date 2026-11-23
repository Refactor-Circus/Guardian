# Tasks: Guardian v2: security platform

Builders: if a design question comes up, choose the most conservative option consistent with spec.md and decisions.md, record it as the next D-entry in decisions.md, and continue. You may change files outside a group's list when the group needs it; commit them with the group.

## Group 1: Remove the Atrium UI
depends on: none
files: src/Atrium/** (delete), resources/views/** (delete), lang/en/guardian.php, src/GuardianServiceProvider.php, config/guardian.php, composer.json, package.json (delete), package-lock.json (delete), phpunit.xml.dist, .github/workflows/tests.yml, .github/workflows/browser-tests.yml (delete), tests/Feature/Atrium/** (delete), tests/Feature/Ui/** (delete), tests/Browser/** (delete), tests/Modes/WithoutAtrium/** (delete), tests/WithoutAtriumTestCase.php (delete), tests/Feature/StylesTest.php (delete), tests/TestCase.php, tests/Pest.php, tests/moved-tests.json, tests/Feature/MovedTestsManifestTest.php, tests/ArchTest.php, tests/Feature/ComposerTest.php, tests/Feature/RemovedSurfacesTest.php
criteria: 2

- [x] 1.1 Write tests: `RemovedSurfacesTest` (no `atrium` in `src/` or `config/`, case-insensitive; no `atrium.guardian.*` route; `config('guardian.ui')` and `config('guardian.atrium')` null; no Guardian view namespace); `ArchTest` (nothing uses `RefactorCircus\Atrium`, no `ignoring`); `ComposerTest` (no `refactor-circus/atrium` in require, require-dev, suggest, conflict or repositories; no `test:without-atrium` or `test:browser` script; no `pestphp/pest-plugin-browser`)
- [x] 1.2 Delete `src/Atrium/`, `resources/views/` and the Atrium-only lang keys. Drop `registerAtriumPlugin()` and the view loading from `GuardianServiceProvider`, and the `ui`/`atrium` sections from `config/guardian.php`.
- [x] 1.3 Remove the Atrium entries, the browser plugin and the scripts from `composer.json`, delete the root `package.json`/`package-lock.json` if only styles or browser tests used them, and run `composer update`. Remove the browser and without-Atrium suites from `phpunit.xml.dist` and CI.
- [x] 1.4 Delete the Atrium-only tests and test cases, remove Atrium from `TestCase`, and update `moved-tests.json` and its manifest test for the deleted files
- [x] 1.5 Run vendor/bin/pest; all green

## Group 2: v2 suite constraints, SSO/SCIM on Keystone's contract
depends on: 1
files: composer.json, src/Support/Integration.php, src/Policies/Policy.php, src/Http/Requests/Request.php, src/Mcp/Requests/Request.php, src/Domains/Sso/SsoServiceProvider.php, src/Domains/Scim/ScimServiceProvider.php, src/Domains/Sso/Concerns/JoinsOrganizations.php, src/Domains/Sso/Concerns/ResolvesOrganizations.php, src/Upgrade/RosterUpgrade.php, src/GuardianServiceProvider.php, src/Domains/DomainServiceProvider.php, tests/Fixtures/roster-references.php, tests/Feature/RosterBoundaryTest.php, tests/Feature/ComposerTest.php, tests/ArchTest.php, tests/TestCase.php, tests/StandaloneTestCase.php, tests/Modes/Standalone/StandaloneTest.php, tests/Feature/Authorization/*, tests/Feature/Scim/*, tests/Feature/Sso/*
criteria: 1, 3, 4, 5

- [x] 2.1 Write tests: `ComposerTest`: criterion 1's constraints, the `2.0.x-dev` alias and the description; `RosterBoundaryTest`: the allow-list in `tests/Fixtures/roster-references.php` (today's Roster classes minus `Domains\Role` and `Domains\Permission`); nothing in `src/` uses those two domains; Arch: the new domain namespaces do not use `RefactorCircus\Roster` (the rule is written now and covers each domain as it appears); `StandaloneTest`: without Roster, Guardian boots, and only the SSO/SCIM providers are skipped; Authorization tests grant abilities with Keystone's `FakePermissions`, and the SSO/SCIM join tests assert the default member role through `FakeRoles`
- [x] 2.2 Require `refactor-circus/keystone` `^2.0` and `refactor-circus/roster`/`keen` `^2.0` (dev), set the branch alias, and run `composer update`. Stop and report if Keystone's authorization addendum or Roster v2 is not on `dev-main`.
- [x] 2.3 Rework `Policies\Policy`, `Http\Requests\Request` and `Mcp\Requests\Request` onto Keystone `Authorizer::allows()`, with the scope supplied by the subclass (Sso/Scim supply the Roster organization)
- [x] 2.4 Define `guardian.sso.*` and `guardian.scim.manage` in Keystone `Abilities` in place of Roster's `PermissionCatalogue`/`Resources`. `JoinsOrganizations` assigns the default member role through Keystone's `Roles`. `RosterUpgrade` uses `Permissions::forget()`. `Integration` drops `Resources` and `PermissionCatalogue` and gates only the Sso and Scim providers.
- [x] 2.5 Run vendor/bin/pest; all green

## Group 3: RBAC tables, models and built-in roles
depends on: 2
files: database/migrations/2026_11_24_000001_create_guardian_role_tables.php, src/Support/UserKey.php, src/Support/Users.php, src/Support/Tables.php, src/Domains/Role/Models/RoleModel.php, src/Domains/Role/Models/RoleAssignmentModel.php, src/Domains/Permission/Models/PermissionModel.php, src/Domains/Role/Support/BuiltInRoles.php, src/Domains/Role/Support/GuardianAbilities.php, src/Domains/Role/RoleServiceProvider.php, src/Domains/Permission/PermissionServiceProvider.php, src/Domains/Permission/Console/Commands/SyncPermissionsCommand.php, src/Domains/Role/Events/*, src/Domains/Permission/Events/*, src/Domains/DomainServiceProvider.php, config/guardian.php, tests/Feature/Role/RoleTablesTest.php, tests/Feature/Role/SyncPermissionsTest.php
criteria: 6, 7, 8

- [x] 3.1 Write tests: the four tables with their columns, indexes and configured names, and `user_id` typed by `guardian.users.key_type`; seeded built-ins after migrate; the sync creates a system permission per Keystone `Abilities` entry (including Guardian's own), grants `grantTo` only to roles it creates, keeps edits, ignores unknown slugs and is idempotent; Guardian's abilities are defined with criterion 8's grants; `Users` resolves the model from `guardian.users.model` or the auth provider
- [x] 3.2 Add `Support\UserKey`, `Support\Users` and the table keys, and the migration (skip-if-exists, guarded `down()`)
- [x] 3.3 Add the models (ULIDs, morph scope and owner relations, lifecycle events), `BuiltInRoles`, `GuardianAbilities` and `guardian:sync-permissions`, and register the two domain providers
- [x] 3.4 Add the `roles`, `users` and `tables` config entries with banners
- [x] 3.5 Run vendor/bin/pest; all green

## Group 4: Guardian as Keystone's role store
depends on: 3
files: src/Domains/Permission/Services/GuardianPermissions.php, src/Domains/Permission/Services/PermissionCache.php, src/Domains/Role/Services/GuardianRoles.php, src/Domains/Permission/PermissionServiceProvider.php, src/GuardianServiceProvider.php, config/guardian.php, tests/Feature/Permission/GuardianPermissionsTest.php, tests/Feature/Role/GuardianRolesTest.php, tests/Feature/Permission/GateTest.php, tests/Feature/Authorization/*
criteria: 9, 10, 11, 12, 13, 14

- [x] 4.1 Write tests: `GuardianPermissionsTest` (criteria 10, 11, 13): guest; super role; configured super-admin, verified and unverified; global, scope and parent-scope grants with fixture `Scope` models; a non-`Scope` model; `grantsByItself`; an unknown ability; `scopesWith` null and lists; `abilities`; `forget`; the memo; the cache with a TTL and forgetting on writes; `GuardianRolesTest` (criterion 12): `exists` by scope kind and owner; idempotent `assign` and false for a role not assignable there; `revoke` in an exact scope; `revokeEverywhere`; `of`; `holders`; `GateTest` (criterion 14); Binding on and off (criterion 9)
- [x] 4.2 Implement `GuardianPermissions` (per-request grants, `ScopeChain`) and `PermissionCache` (ported from Roster's)
- [x] 4.3 Implement `GuardianRoles`
- [x] 4.4 Bind both with `scoped()` behind `guardian.authorization.bind`, and register the `Gate::before` and the cache-forgetting listeners. Add the `authorization`, `super_admins` and `permission_cache` config. Switch the SSO/SCIM authorization tests from Keystone's fakes to real Guardian roles.
- [x] 4.5 Run vendor/bin/pest; all green

## Group 5: Roles API and MCP
depends on: 4
files: src/Domains/Role/Actions/{CreateRoleAction,UpdateRoleAction,DeleteRoleAction,ShowRoleAction,ListRolesAction}.php, src/Domains/Role/Data/{CreateRoleData,UpdateRoleData,ListRolesData}.php, src/Domains/Role/Concerns/GuardsEscalation.php, src/Domains/Role/Policies/RolePolicy.php, src/Domains/Role/Resources/RoleResource.php, src/Domains/Role/Http/Requests/*Role*Request.php, src/Domains/Role/Http/Controllers/RoleController.php, src/Domains/Role/routes.php, src/Domains/Role/Mcp/**, src/Mcp/GuardianServer.php, config/guardian.php (policies), tests/Feature/Role/RoleActionsTest.php, tests/Feature/Http/RolesHttpTest.php, tests/Feature/Mcp/RoleToolsTest.php, tests/Pest.php
criteria: 15, 16, 17, 18

- [x] 5.1 Write tests: `RoleActionsTest` (create, update, delete, show and list; events; system role refused; escalation on permissions and the super flag); HTTP by route name (200, 201 and 204, plus 401 and 403 with `guardian.roles.view` and `guardian.roles.manage` in a scope); MCP tools with output schemas; parity
- [x] 5.2 Port the role actions, Data and `GuardsEscalation` from Roster onto scope morphs
- [x] 5.3 Add `RolePolicy`, `RoleResource` (`slug`, not `id`, ISO timestamps), the requests, the controller and the routes
- [x] 5.4 Add the MCP requests and tools, and register them in `GuardianServer::TOOLS`
- [x] 5.5 Run vendor/bin/pest; all green

## Group 6: Role assignments and user abilities
depends on: 5
files: src/Domains/Role/Actions/{AssignRoleAction,RevokeRoleAction,ListRoleAssignmentsAction,ListUserAbilitiesAction}.php, src/Domains/Role/Data/{AssignRoleData,ListRoleAssignmentsData,ListUserAbilitiesData}.php, src/Domains/Role/Policies/RoleAssignmentPolicy.php, src/Domains/Role/Resources/RoleAssignmentResource.php, src/Domains/Role/Http/Requests/*Assignment*Request.php, src/Domains/Role/Http/Requests/IndexUserAbilitiesRequest.php, src/Domains/Role/Http/Controllers/{RoleAssignmentController,UserAbilityController}.php, src/Domains/Role/routes.php, src/Domains/Role/Mcp/**, src/Domains/Role/Console/Commands/GrantSuperAdminCommand.php, src/Mcp/GuardianServer.php, tests/Feature/Role/AssignmentActionsTest.php, tests/Feature/Http/RoleAssignmentsHttpTest.php, tests/Feature/Mcp/RoleAssignmentToolsTest.php, tests/Feature/Role/GrantSuperAdminCommandTest.php
criteria: 15, 16, 17, 18, 19

- [x] 6.1 Write tests: assign and revoke in global and scoped contexts, with escalation (missing permissions named, super roles for super-admins only); assignment listing with every filter; user abilities in a scope; HTTP and MCP wiring with `guardian.roles.assign`/`view`; `guardian:grant-super-admin` for a known and an unknown email
- [x] 6.2 Implement the four actions, their Data and events, the policy and the resource
- [x] 6.3 Add the requests, controllers, routes and MCP tools
- [x] 6.4 Add `GrantSuperAdminCommand`
- [x] 6.5 Run vendor/bin/pest; all green

## Group 7: Permissions API and MCP
depends on: 6
files: src/Domains/Permission/Actions/{CreatePermissionAction,UpdatePermissionAction,DeletePermissionAction,ListPermissionsAction}.php, src/Domains/Permission/Data/*.php, src/Domains/Permission/Policies/PermissionPolicy.php, src/Domains/Permission/Resources/PermissionResource.php, src/Domains/Permission/Http/**, src/Domains/Permission/routes.php, src/Domains/Permission/Mcp/**, src/Mcp/GuardianServer.php, tests/Feature/Permission/PermissionActionsTest.php, tests/Feature/Http/PermissionsHttpTest.php, tests/Feature/Mcp/PermissionToolsTest.php
criteria: 16, 17, 18

- [x] 7.1 Write tests: create, update, delete and list; a system permission cannot be deleted or renamed; names unique; cache forgotten; HTTP (`guardian.roles.manage`, 401 and 403) and MCP wiring; parity
- [x] 7.2 Port the permission actions, Data, events, policy and resource
- [x] 7.3 Add the requests, controller, routes and MCP tools
- [x] 7.4 Run vendor/bin/pest; all green

## Group 8: Adopt Roster's role tables
depends on: 7
files: src/Upgrade/RosterRoleAdoption.php, src/Upgrade/UpgradeReport.php, src/Console/Commands/AdoptRosterRolesCommand.php, database/migrations/2026_11_24_000003_adopt_roster_role_tables.php, src/GuardianServiceProvider.php, config/guardian.php, tests/Fixtures/roster-role-tables.php, tests/Feature/Upgrade/RosterRoleAdoptionTest.php
criteria: 20, 21

- [x] 8.1 Write tests: The fixture creates `roster_*` role tables with global, organization, team, owner-custom and super rows, plus expected `allows()` answers recorded from Roster's resolution rules; adoption copies them with ULIDs kept, maps scopes and owners, and renames permissions; the answers match; a second run reports "already adopted"; with no source tables it reports "nothing to adopt"; when Guardian already has assignments or custom roles nothing changes; the source tables and rows are untouched; custom `guardian.adopt.*` config is honoured, and the migration logs the report while the command prints it
- [x] 8.2 Implement `RosterRoleAdoption`: plan first, then copy in one transaction, then sync. Record the architecture/dependencies departure (Roster table and class names as strings) in `docs/agent/standards-feedback.md`.
- [x] 8.3 Add the migration, `guardian:adopt-roster-roles` and the `adopt` config section
- [x] 8.4 Run vendor/bin/pest; all green

## Group 9: Security event log
depends on: 8
files: database/migrations/2026_11_24_000004_create_guardian_security_event_tables.php, src/Domains/SecurityEvent/** (new: Models/SecurityEventModel.php, Enums/SecurityEventType.php, Actions/{RecordSecurityEventAction,ListSecurityEventsAction,ShowSecurityEventAction}.php, Data/*, Events/*, Listeners/RecordAuthEvents.php, Policies/SecurityEventPolicy.php, Resources/SecurityEventResource.php, Http/**, Mcp/**, routes.php, SecurityEventServiceProvider.php), src/Domains/DomainServiceProvider.php, src/Mcp/GuardianServer.php, config/guardian.php, tests/Feature/SecurityEvent/*, tests/Feature/Http/SecurityEventsHttpTest.php, tests/Feature/Mcp/SecurityEventToolsTest.php
criteria: 22, 23, 24, 25

- [ ] 9.1 Write tests: table and pruning; every enum type recorded through the action, with events dispatched and Keen (require-dev) recording them; no secret in context or audit payload; the five Laravel auth events recorded (identifier HMAC, never raw); list filters and cursor; `show`; own events for any user; `guardian.security.view` 401/403; MCP tools
- [ ] 9.2 Add the migration, model (`Prunable`), enum, action, events and listeners
- [ ] 9.3 Add the list and show actions, the policy, the resource, HTTP and MCP, and the `security_log` config
- [ ] 9.4 Run vendor/bin/pest; all green

## Group 10: TOTP, recovery codes and two-factor status
depends on: 9
files: composer.json (pragmarx/google2fa), database/migrations/2026_11_24_000005_create_guardian_two_factor_tables.php, src/Domains/TwoFactor/Models/{TwoFactorMethodModel,RecoveryCodeModel}.php, src/Domains/TwoFactor/Services/{Totp,RecoveryCodes}.php, src/Domains/TwoFactor/Actions/{StartTotpEnrolmentAction,ConfirmTotpAction,RegenerateRecoveryCodesAction,ShowTwoFactorAction,RemoveTwoFactorMethodAction,ResetTwoFactorAction}.php, src/Domains/TwoFactor/Data/*, src/Domains/TwoFactor/Events/*, src/Domains/TwoFactor/Policies/*, src/Domains/TwoFactor/Resources/*, src/Domains/TwoFactor/Http/**, src/Domains/TwoFactor/Mcp/**, src/Domains/TwoFactor/routes.php, src/Domains/TwoFactor/TwoFactorServiceProvider.php, src/Domains/DomainServiceProvider.php, config/guardian.php, tests/Feature/TwoFactor/TotpTest.php, tests/Feature/TwoFactor/RecoveryCodesTest.php, tests/Feature/TwoFactor/StatusTest.php, tests/Pest.php
criteria: 26, 27, 28, 31

- [ ] 10.1 Write tests: The tables, including passkey and one-time-code tables; TOTP against the RFC 6238 vectors through the library; enrolment returns the secret and URI; confirmation within ±1 step; replay refused; recovery codes: returned once, single use, hashed, regenerating replaces them; status payload masks secrets; removing a factor needs another factor's code; last factor while required refused; admin reset with `guardian.two-factor.manage`; events recorded; parity exemptions for the secret-returning actions
- [ ] 10.2 Require `pragmarx/google2fa` (if no release installs on PHP 8.5, implement `Services\Totp` per RFC 6238 and record a D-entry)
- [ ] 10.3 Add the migration for all four 2FA tables, the models (encrypted casts) and the services
- [ ] 10.4 Add the actions, requests, controllers, routes, MCP status and reset tools, and the `two_factor` config
- [ ] 10.5 Run vendor/bin/pest; all green

## Group 11: Email and SMS one-time codes
depends on: 10
files: src/Domains/TwoFactor/Contracts/OneTimeCodeChannel.php, src/Domains/TwoFactor/Channels/MailOneTimeCodeChannel.php, src/Domains/TwoFactor/Notifications/OneTimeCodeNotification.php, src/Domains/TwoFactor/Models/OneTimeCodeModel.php, src/Domains/TwoFactor/Services/OneTimeCodes.php, src/Domains/TwoFactor/Actions/{StartOneTimeCodeEnrolmentAction,ConfirmOneTimeCodeAction}.php, src/Domains/TwoFactor/Http/**, src/Domains/TwoFactor/routes.php, src/Domains/TwoFactor/TwoFactorServiceProvider.php, tests/Fixtures/FakeOneTimeCodeChannel.php, tests/Feature/TwoFactor/OneTimeCodesTest.php
criteria: 29

- [ ] 11.1 Write tests with the fake channel: email enrolment sends and confirms; SMS answers 422 `method_unavailable` with the default channel and works with a fake supporting it; codes hashed, expire, 5 attempts, single use; resend limited to one per 60s; mail notification content without the destination in logs
- [ ] 11.2 Add the contract, the mail channel and notification, the model and the service
- [ ] 11.3 Add the enrolment actions and routes, and bind the default channel
- [ ] 11.4 Run vendor/bin/pest; all green

## Group 12: Passkeys (WebAuthn)
depends on: 11
files: composer.json (web-auth/webauthn-lib dev + suggest), src/Domains/TwoFactor/Passkeys/{PasskeyCeremony,PasskeyChallenges,PasskeysAvailable}.php, src/Domains/TwoFactor/Models/PasskeyModel.php, src/Domains/TwoFactor/Actions/{StartPasskeyRegistrationAction,RegisterPasskeyAction,ListPasskeysAction,RemovePasskeyAction}.php, src/Domains/TwoFactor/Resources/PasskeyResource.php, src/Domains/TwoFactor/Http/**, src/Domains/TwoFactor/Mcp/**, src/Domains/TwoFactor/routes.php, tests/Fixtures/webauthn/*, tests/Feature/TwoFactor/PasskeysTest.php, tests/Feature/TwoFactor/PasskeysUnavailableTest.php, tests/ArchTest.php
criteria: 30

- [ ] 12.1 Write tests: Registration options and a stored challenge bound to the user that expires; attestation verified from recorded fixture ceremonies (`none` attestation) and the credential stored; list and remove (remove needs another factor); a sign count that does not increase is refused and recorded; with the library hidden, every endpoint answers 501 and the status shows passkeys as unavailable; Arch: only `Passkeys\` uses `Webauthn\*`
- [ ] 12.2 `composer require --dev web-auth/webauthn-lib` and add it to `suggest`. If no release installs, record a D-entry and keep the 501 path with the tests skipped.
- [ ] 12.3 Implement `PasskeyCeremony` (registration and assertion), `PasskeyChallenges` (cache, 5 minutes) and the model, actions, resource, routes and MCP list/remove tools
- [ ] 12.4 Run vendor/bin/pest; all green

## Group 13: Two-factor challenge and middleware
depends on: 12
files: src/Domains/TwoFactor/Services/TwoFactorSession.php, src/Domains/TwoFactor/Actions/{PassChallengeAction,SendChallengeCodeAction,StartPasskeyChallengeAction}.php, src/Domains/TwoFactor/Listeners/MarkSessionPending.php, src/Domains/TwoFactor/Http/Middleware/EnsureTwoFactor.php, src/Domains/TwoFactor/Http/**, src/Domains/TwoFactor/routes.php, src/Domains/TwoFactor/TwoFactorServiceProvider.php, src/GuardianServiceProvider.php, tests/Feature/TwoFactor/ChallengeTest.php, tests/Feature/TwoFactor/EnsureTwoFactorTest.php
criteria: 32, 33

- [ ] 13.1 Write tests: Sign-in with a confirmed factor marks the session pending; the challenge passes with TOTP, a recovery code, email, SMS (fake) and a passkey (fixture), and regenerates the session id; failures limited (429) and recorded; Middleware: unenrolled required user before the deadline (header) and after it (403 enrolment); pending session (403 challenge with methods); confirmed session passes; stateless enrolled request passes; Guardian's own 2FA routes never blocked; the alias is registered
- [ ] 13.2 Implement `TwoFactorSession` and the `Login` listener
- [ ] 13.3 Implement the challenge actions and routes (parity-exempt)
- [ ] 13.4 Implement `EnsureTwoFactor` against `TwoFactorRequirement` (global config only until group 14), and register the `guardian.two-factor` alias
- [ ] 13.5 Run vendor/bin/pest; all green

## Group 14: Two-factor enforcement policies
depends on: 13
files: database/migrations/2026_11_24_000006_create_guardian_two_factor_policy_tables.php, src/Domains/TwoFactor/Contracts/TwoFactorRequirement.php, src/Domains/TwoFactor/Data/Requirement.php, src/Domains/TwoFactor/Services/PolicyTwoFactorRequirement.php, src/Domains/TwoFactor/Models/TwoFactorPolicyModel.php, src/Domains/TwoFactor/Actions/{CreateTwoFactorPolicyAction,UpdateTwoFactorPolicyAction,DeleteTwoFactorPolicyAction,ListTwoFactorPoliciesAction}.php, src/Domains/TwoFactor/Policies/TwoFactorPolicyPolicy.php, src/Domains/TwoFactor/Resources/TwoFactorPolicyResource.php, src/Domains/TwoFactor/Http/**, src/Domains/TwoFactor/Mcp/**, src/Domains/TwoFactor/routes.php, tests/Feature/TwoFactor/EnforcementTest.php, tests/Feature/Http/TwoFactorPoliciesHttpTest.php
criteria: 34

- [ ] 14.1 Write tests: global config; a global policy; a role policy (anywhere and in a scope); a scope policy reaching child scopes through `ScopeChain`; method intersection and the conflict fallback with its event; earliest deadline; a host-bound `TwoFactorRequirement` replaces the default; CRUD over HTTP and MCP with `guardian.two-factor.manage`
- [ ] 14.2 Add the migration, model, contract, `Requirement` value object and `PolicyTwoFactorRequirement`, and bind it
- [ ] 14.3 Add the CRUD actions, policy, resource, routes and MCP tools
- [ ] 14.4 Run vendor/bin/pest; all green

## Group 15: Sessions and devices
depends on: 14
files: database/migrations/2026_11_24_000007_create_guardian_session_tables.php, src/Domains/Session/** (new: Models/{DeviceModel,SessionModel}.php, Services/{Devices,SessionKeys}.php, Listeners/TrackSignIn.php, Http/Middleware/TrackSession.php, Notifications/NewDeviceSignInNotification.php, Actions/{ListSessionsAction,RevokeSessionAction,RevokeOtherSessionsAction,ListUserSessionsAction,RevokeUserSessionsAction,ListDevicesAction,ForgetDeviceAction}.php, Data/*, Events/*, Policies/*, Resources/*, Http/**, Mcp/**, routes.php, SessionServiceProvider.php), src/Domains/DomainServiceProvider.php, src/GuardianServiceProvider.php, src/Mcp/GuardianServer.php, config/guardian.php, tests/Feature/Session/*, tests/Feature/Http/SessionsHttpTest.php, tests/Feature/Mcp/SessionToolsTest.php
criteria: 35, 36, 37, 38

- [ ] 15.1 Write tests: Tables; sign-in creates the device and session, and the record survives `session()->regenerate()`; touch throttling; a revoked session logs out with 401 `session_revoked`; the new-device notice: not for the first device, off by config, `device.new` recorded; own list with the current one flagged; revoke one; sign out others with and without `tokens` (a fake driver until group 17); the admin routes with `guardian.sessions.manage`; devices list and forget; MCP; events
- [ ] 15.2 Add the migration, models and services, the `Login` listener and `TrackSession` (alias `guardian.session`)
- [ ] 15.3 Add the actions, policies, resources, routes, MCP tools and notification, plus the `sessions` and `devices` config
- [ ] 15.4 Run vendor/bin/pest; all green

## Group 16: Password policy
depends on: 15
files: database/migrations/2026_11_24_000008_create_guardian_password_tables.php, src/Domains/Password/** (new: PasswordPolicy.php, Rules/NotRecentlyUsed.php, Models/PasswordHistoryModel.php, Observers/RecordPasswordChange.php, Http/Middleware/EnsurePasswordFresh.php, Actions/{ShowPasswordPolicyAction,CheckPasswordAction}.php, Resources/*, Http/**, Mcp/**, routes.php, PasswordServiceProvider.php), src/Domains/DomainServiceProvider.php, src/GuardianServiceProvider.php, src/Mcp/GuardianServer.php, config/guardian.php, tests/Pest.php, tests/Fixtures/FakeUncompromisedVerifier.php, tests/Feature/Password/*, tests/Feature/Http/PasswordHttpTest.php
criteria: 39, 40, 41, 42, 43

- [ ] 16.1 Write tests: Rules built from each config key; the breach check through the fake verifier: rejected, accepted, and an outage passes and records the event; `Http::preventStrayRequests()` is on, so a real HIBP call would fail the test; history recorded on a password change, trimmed to `history`; reuse refused; rotation middleware: expired gives 403 `password_expired`, a baseline is created for users with no history; the policy endpoint; the check endpoint (200 payload, throttled, the password never in logs or events); the MCP show tool; check is parity-exempt
- [ ] 16.2 Add `PasswordPolicy`, `NotRecentlyUsed`, the history migration, model and observer (on `guardian.users.model`), and the `PasswordReset` listener hook
- [ ] 16.3 Add `EnsurePasswordFresh` (alias `guardian.password-fresh`), the actions, routes, MCP tool and the `passwords` config
- [ ] 16.4 Run vendor/bin/pest; all green

## Group 17: API tokens and drivers
depends on: 16
files: composer.json (laravel/sanctum, laravel/passport dev + suggest), src/Domains/Token/** (new: Contracts/TokenDriver.php, Data/{TokenSummary,IssuedToken}.php, TokenManager.php, Drivers/{SanctumTokenDriver,PassportTokenDriver}.php, Exceptions/TokenDriverException.php, Actions/{ListTokensAction,IssueTokenAction,RevokeTokenAction,ListUserTokensAction,RevokeUserTokensAction}.php, Data/*, Events/*, Policies/*, Resources/TokenResource.php, Http/**, Mcp/**, routes.php, TokenServiceProvider.php), src/Domains/Session/Actions/RevokeOtherSessionsAction.php, src/Domains/DomainServiceProvider.php, src/Mcp/GuardianServer.php, config/guardian.php, workbench/app/Models/User.php, tests/Fixtures/FakeTokenDriver.php, tests/Feature/Token/*, tests/ArchTest.php
criteria: 44, 45, 46, 47, 48

- [ ] 17.1 Write tests: `TokenManager`: null gives 501 over HTTP and an MCP error; `sanctum`, `passport` and a custom class; an unknown value or missing package throws `TokenDriverException`; Sanctum and Passport adapters: issue with abilities and expiry, list, revoke and revoke all. Each adapter's tests skip with a reason when its package cannot be installed; HTTP: ability allow-list and maximum lifetime (422); plain token returned once; admin routes with `guardian.tokens.manage`; MCP lists and revokes, and issuing is exempt; events carry no token values; Arch: only the adapters use `Laravel\Sanctum` and `Laravel\Passport`; sign out others with `tokens: true` calls `revokeAll`
- [ ] 17.2 `composer require --dev laravel/sanctum laravel/passport` (newest releases supporting Laravel 13; record a D-entry for any that cannot install) and add both to `suggest`
- [ ] 17.3 Add the contract, value objects, `TokenManager`, exception and both adapters (class_exists guards)
- [ ] 17.4 Add the actions, policy, resource, routes and MCP tools, plus the `tokens` config; wire `RevokeOtherSessionsAction` to the manager
- [ ] 17.5 Run vendor/bin/pest; all green

## Group 18: Login protection and IP rules
depends on: 17
files: database/migrations/2026_11_24_000009_create_guardian_ip_rule_tables.php, src/Domains/LoginProtection/** (new: Services/{LoginFailures,SuspiciousSignIns,IpRules}.php, Listeners/{CountLoginFailures,DetectSuspiciousSignIn}.php, Http/Middleware/{ThrottleLogins,EnforceIpRules}.php, Notifications/SuspiciousSignInNotification.php, Models/IpRuleModel.php, Actions/{ClearLockoutAction,CreateIpRuleAction,DeleteIpRuleAction,ListIpRulesAction}.php, Data/*, Events/*, Policies/*, Resources/*, Http/**, Mcp/**, routes.php, LoginProtectionServiceProvider.php), src/Domains/Session/Listeners/TrackSignIn.php, src/Domains/DomainServiceProvider.php, src/GuardianServiceProvider.php, src/Mcp/GuardianServer.php, config/guardian.php, tests/Feature/LoginProtection/*, tests/Feature/Http/LoginProtectionHttpTest.php
criteria: 49, 50, 51, 52

- [ ] 18.1 Write tests: Lockout after 5 identifier failures and after 50 IP failures (429 with `Retry-After`, `Lockout` fired and recorded); a sign-in clears the identifier; the admin clears a lockout over HTTP and MCP; suspicious sign-in with each signal and without either: event, notification in place of the new-device notice, never blocked; IP rules: deny match; allowlist mode; deny wins; IPv6; expired ignored; cache refreshed on write; invalid CIDR 422; `ip.denied` throttled; CRUD with `guardian.security.manage`; middleware aliases registered
- [ ] 18.2 Add `LoginFailures`, the listeners, `ThrottleLogins` (alias `guardian.login-throttle`) and the clear-lockout action, route and tool
- [ ] 18.3 Add `SuspiciousSignIns` and the notification, and hand off from `TrackSignIn`
- [ ] 18.4 Add the IP rule migration, model, service, `EnforceIpRules` (alias `guardian.ip`), the CRUD and MCP, and the `login` and `ip_rules` config
- [ ] 18.5 Run vendor/bin/pest; all green

## Group 19: OpenAPI export and TypeScript client
depends on: 18
files: composer.json (dedoc/scramble, client:generate), workbench/app/Providers/WorkbenchServiceProvider.php, testbench.yaml, src/Domains/*/Http/Controllers/*.php (@response annotations), openapi.json, client/package.json, client/package-lock.json, client/tsconfig.json, client/src/index.ts, client/src/schema.d.ts, client/dist/index.js, client/dist/index.d.ts, client/openapi.sha256, bin/client-stamp.php, .gitignore, .gitattributes, .github/workflows/tests.yml, tests/Feature/OpenApiTest.php, tests/Feature/ComposerTest.php, tests/ArchTest.php, tests/Modes/Standalone/StandaloneTest.php, tests/Pest.php
criteria: 3, 53, 54, 55

- [ ] 19.1 Write tests: `OpenApiTest`: a fresh export equals the committed file; two exports are byte-identical; 3.1 and `2.0.0`; one operation per `guardian.*` API route (operationId = name); no SCIM protocol or SSO web routes; stamp check; client package fields and exports; `ComposerTest`: the `client:generate` script order; the CI `client` job; nothing export-ignored; Arch: no `Dedoc\Scramble` in src; `StandaloneTest`: every non-SSO/SCIM route and tool is registered without Roster; `parityGaps()` is empty with the D-021 exemptions
- [ ] 19.2 Require Scramble (dev) and configure it in the workbench (with the token driver and passkeys enabled, so their routes are documented). Add `@response` annotations where inference fails.
- [ ] 19.3 Create `client/` (`@refactor-circus/guardian-client` 2.0.0, `createGuardianClient`), `bin/client-stamp.php` and `composer client:generate`. Run it and commit the output.
- [ ] 19.4 Add the CI `client` job
- [ ] 19.5 Run vendor/bin/pest; all green

## Group 20: Docs, changelog and Boost skill
depends on: 19
files: README.md, CHANGELOG.md, config/guardian.php (banners), resources/boost/skills/guardian/SKILL.md, CLAUDE.md, AGENTS.md, docs/agent/standards-feedback.md, tests/Feature/DocsTest.php, tests/Feature/ConfigTest.php
criteria: 56, 57, 58

- [ ] 20.1 Write tests: `DocsTest` covers criterion 57's names (every middleware alias, `guardian.authorization.bind`, `TokenDriver`, `OneTimeCodeChannel`, `guardian:adopt-roster-roles`, `openapi.json`, `@refactor-circus/guardian-client`) and the absence of `registerAtriumPlugin` and `guardian.ui`. CHANGELOG has the three `[Unreleased]` subsections and the adoption steps. `ConfigTest` checks every new section's defaults and banner.
- [ ] 20.2 Rewrite the README: Security package overview and Keystone contract binding; One section per domain; a host wiring table: `guardian.two-factor`, `guardian.session` and `guardian.password-fresh` on authenticated routes; `guardian.login-throttle` on the login route; `guardian.ip` where wanted; Token drivers; passkeys suggestion; OTP channel; `TwoFactorRequirement`; Roster integration as an existing cross-package reference, with the `adopt.scope_types` note for morph maps; Atrium v2 as the optional UI; OpenAPI and client
- [ ] 20.3 Write the CHANGELOG `[Unreleased]` Breaking, Added and Upgrading to v2.0.0 entries, and finish the config banners
- [ ] 20.4 Update the Boost skill, `CLAUDE.md` and `AGENTS.md`. Add `standards-feedback.md` rows for any departure taken during the build.
- [ ] 20.5 Run composer test; all green
