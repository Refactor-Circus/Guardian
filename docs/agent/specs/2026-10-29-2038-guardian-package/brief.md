Risk: high

## What changed

- **New package `refactor-circus/guardian`**: auth and security for the suite. Its first features are SSO (OIDC, SAML, Entra ID) and SCIM 2.0 provisioning, moved out of Roster with their actions, data, events, models, policies, HTTP API, MCP tools (seven SSO, three SCIM) and routes.
- **Suite rules**: Keystone is the only required suite package. Roster, Atrium, Keen and laravel/mcp are suggested. `conflict` on `refactor-circus/atrium <0.1.0`. Without Roster, Guardian boots inert and its `about` line says "Inactive: install refactor-circus/roster" (D-023).
- **Roster integration**: Guardian talks to Roster through the auth-extension seams (`Roster::requireSignIn()`, `externalSignInFor()`, `provisionedStatus()`, `Resources`, `PermissionCatalogue`, `MembershipSources`) plus Roster's public domain API (D-024). `RosterBoundaryTest` denies Roster's internals.
- **Atrium UI**: `guardian.sso` and `guardian.scim` tabs and the `guardian.sso-identities` aside are placed on Roster's screens from `config/guardian.php` `atrium.screens`. There is also a `guardian.sso-connection` screen. A WithoutAtrium suite and CI lane prove Guardian runs without Atrium.
- **Transition from Roster**: a runtime `Handover` gate means no SSO/SCIM routes, morph aliases or table rename until Roster's SSO code is gone (D-022). `guardian:upgrade-from-roster` and the `2026_11_01_000000` migration rename the tables and merge the `roster.sso.*` / `roster.scim.*` grants. Stranded tables are reported, never enforced (D-029).
- **Changed from Roster (D-039)**: OIDC sign-in links an account by email, or creates one, only when the id_token's `email_verified` claim is `true`.

## Why

SSO and SCIM are security features with their own lifecycle. Moving them into Guardian keeps Roster focused on users and organizations, and gives MFA, session policies and IP rules a home.

## Look at

- `src/Upgrade/RosterUpgrade.php`: the table rename and grant merge, plan-then-act.
- `src/Domains/Sso/Actions/SsoLoginAction.php`: `linkOrCreate()` with the D-039 email check.
- `src/Domains/Sso/Concerns/JoinsOrganizations.php`: writes Roster's membership models directly, because Roster has no public action that takes a membership source (recorded in standards-feedback.md).
- `src/Support/Integration.php`: the Roster and Atrium detection that gates everything.
- `database/migrations/2026_11_01_000001_create_guardian_sso_tables.php`: `down()` refuses to drop tables with rows unless forced (D-031, D-040).

## Tested

- `composer test`: PHPStan, Pint, 100% type coverage and 546 Pest tests pass.
- Tested against the open PR branches of Roster (#13, which contains #11 and #12), Atrium #16 and Keen #8.
- Moved tests come from Roster at a pinned SHA (`tests/moved-tests.json`). Comparisons with Roster's config and MCP tools use fixtures captured at that SHA.
- Browser tests and the MySQL upgrade lane (`--group=upgrade-mysql`) run only in CI and have not run yet.

## Risks

- Guardian is new and depends on four open PRs. Merge Atrium #16, Keen #8 and Roster #11, #12 and #13 first, then tag Guardian.
- Until Guardian is installed after Roster's removal, SSO enforcement fails open (Roster D-017). README and CHANGELOG say so in bold.
- Recorded departures (build.md, standards-feedback.md):
  - the real-migration upgrade test inserts rows directly;
  - HandoverPending uses a stand-in check next to a Roster without SSO;
  - the upgrade calls `Permissions::forgetCache()`;
  - read actions keep their event pairs (D-041).
