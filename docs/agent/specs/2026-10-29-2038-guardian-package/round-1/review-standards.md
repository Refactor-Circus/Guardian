VERDICT: CHANGES

Standards review, round 1.

- major (architecture/dependencies, spec criterion 9): Guardian uses Roster classes outside the allowed list (seams plus models, actions and Data, Roster, Authorizer, Users, Statuses, UserKey, Tables). Roster Domains/Organization/Concerns/ManagesMemberships (SsoLoginAction, ScimUsers), Roster Support/CaseInsensitive (ScimQuery) and Roster Domains/User/Resources/UserSummaryResource (SsoIdentityResource) are Roster helpers or HTTP-layer classes. Fix: use Roster public actions/seams, or move the small helper logic into Guardian, and extend tests/Feature/RosterBoundaryTest.php to deny them.
- minor (architecture/service-providers): views are loaded but not publishable. Add a publishes() group tagged guardian and guardian-views behind runningInConsole() in GuardianServiceProvider.
- minor (architecture/service-providers, domain-modules): DomainServiceProvider is registered from a booting callback instead of register(). The spec needs the Integration check at boot, so keep it, but add a row to docs/agent/standards-feedback.md.
- nit: SsoLoginRefused uses a public constructor instead of a forX() named constructor (exceptions). Uncast config reads in scim.php/Bulk.php (configuration).
