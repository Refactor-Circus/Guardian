# Guardian v2: security platform — Shaping Notes

## Scope

Guardian becomes the suite's security package ("all things security") and releases as v2.0.0 with the rest of the suite. It changes Guardian only.

1. **v2 conversion.** JSON API and MCP only. Delete `src/Atrium`, the Blade views, the Atrium-only tests, browser suite and config. Drop every Atrium composer entry. Keystone and Roster move to `^2.0`, and `dev-main` is aliased to `2.0.x-dev`. Export `openapi.json` with dedoc/scramble from the workbench, and commit a generated TypeScript client (`@refactor-circus/guardian-client` 2.0.0, `createGuardianClient`) with a staleness test, following Keen's v2 pattern.
2. **Roles and permissions move in from Roster.** Actions, models, policies, API and MCP move over. Guardian implements Keystone's authorization contract (`Contracts\Permissions`, `Contracts\Roles`). Table names are configurable, and a migration adopts (copies) an install's existing `roster_*` role tables.
3. **Two-factor.** TOTP, one-time recovery codes, WebAuthn passkeys, and email/SMS one-time codes delivered through a host channel. It can be enforced globally, per scope (organization) or per role. Challenge and enrolment endpoints come with a middleware the host adds to its routes.
4. **Sessions and devices.** List and revoke sessions, sign out everywhere, and send a notice when a new device signs in.
5. **Password policy.** Length and complexity, a HIBP k-anonymity breach check, rotation and reuse prevention.
6. **API tokens.** Managed through a `TokenDriver` contract, with optional Sanctum and Passport adapters. No token package is forced.
7. **Login protection and security log.** Lockout and throttling, suspicious sign-in detection, IP allow/deny lists, and a security event log that Keen audits through Keystone's audit seams.

## Decisions

- **User decisions (2026-11-23):** all of the scope above. Roles and permissions move from Roster to Guardian behind a Keystone contract, with a Gate/policy fallback when Guardian is absent. The 2FA methods and enforcement are as listed. Token management is flexible (the user's main app uses Passport, others Sanctum or something else). Pace: build and gate, no review rounds.
- **Suite rule:** Guardian requires only Keystone and never names another package, except where SSO and SCIM already reach into Roster's users and organizations. That reference is kept as it is and flagged; it is not widened. Every new domain must work with no Roster installed.
- **Order:** Keystone's authorization addendum, then Roster v2 with its RBAC removal, then this spec. Guardian requires Roster `^2.0` in `require-dev` for the SSO/SCIM tests, and that resolves only once Roster's job merges.
- Defaults chosen without the user are recorded in `decisions.md`.

## Context

- **Existing Guardian (v0.1):** SSO (OIDC/SAML/Entra) and SCIM extracted from Roster, with an Atrium plugin. `Upgrade/RosterUpgrade` already renames Roster's SSO/SCIM tables and permissions into Guardian's (the precedent for the role adoption). `Support/Integration` turns every domain off without Roster.
- **Roster's RBAC** (`Roster--2026-11-23-0943-roster-v2-api-and-mcp-only/src/Domains/{Role,Permission}`): global, organization and team scopes; organization owners; super-admins (super role or `roster.super_admins`); a permission catalogue; built-in roles; escalation guard; a per-request memo plus an optional cache; `Gate::before` integration.
- **Keen v2** (`Keen--2026-11-23-0943-keen-v2-api-and-mcp-only`) is the finished example of the conversion (Scramble in the workbench, `client/`, `bin/client-stamp.php`, `OpenApiTest`).
- **Product docs:** `docs/agent/product/` (Guardian's mission); nothing conflicts.

## Standards

- actions/actions-events: every new action dispatches its event pair, and Keen audits the finished ones.
- actions/domain-guards: escalation guard, last-method guard, token lifetime limits.
- architecture/configuration: new config sections with banners; `ui`/`atrium` removed.
- architecture/container-bindings: Guardian overrides Keystone's bindings; contracts for token drivers, OTP channel and 2FA requirement.
- architecture/dependencies: Keystone is the only suite require; Sanctum, Passport and WebAuthn are optional; the Roster reference is flagged.
- architecture/domain-modules: new domains under `src/Domains`.
- architecture/naming: action, event, request, tool and resource names.
- architecture/public-api: v2.0.0 breaking changes.
- architecture/service-providers: provider changes, middleware aliases.
- data/data-objects, data/value-objects: action input; `TokenSummary`, `IssuedToken`, `TwoFactorRequirement`.
- errors/exceptions: `GuardianException` subclasses for driver and channel errors.
- http/authorization, http/request-persist, http/resources, http/routes: the new API.
- models/lifecycle-events, models/migrations, models/models, models/stored-identifiers: new tables, ULIDs, scope morphs and the adopting migration.
- testing/arch-tests, testing/test-layers: arch rules and the layering of tests.
