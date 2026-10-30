# Guardian package — Shaping Notes

## Scope

Create `refactor-circus/guardian`, the suite's auth and security package. It starts with SSO and SCIM moved out of Roster, and grows into the home for further auth and security features.

In scope:

- **Scaffold.** New package `Guardian` (`RefactorCircus\Guardian`), scaffolded by `ringside new-package` per `new_packages`, with Keystone conventions:
  - registered as package `guardian`
  - domain modules
  - `config/guardian.php`
  - configurable tables `guardian.tables`
  - PHP `^8.5`
- **Domains.** Move Roster's `Domains/Sso` and `Domains/Scim` (actions, data, events, models, policies, resources, HTTP API, MCP tools, routes, SCIM 2.0 server, services) into Guardian's `Domains/Sso` and `Domains/Scim`. Behaviour is unchanged; namespaces and config keys change from `roster.sso.*` to `guardian.sso.*`. Morph aliases keep stored morph types readable.
- **Data.** Guardian's tables are named `guardian_sso_connections`, `guardian_sso_identities`, `guardian_scim_tokens`, `guardian_scim_users` and `guardian_scim_groups`. Guardian ships:
  - create migrations
  - a one-off **upgrade migration** that renames the existing `roster_sso_*` / `roster_scim_*` tables when they exist and Guardian's do not, so current installs keep their connections, identities and tokens (user decision, 2026-10-29). It must be idempotent and safe on fresh installs.
- **Roster integration.** Only through Roster's public seams (spec `Roster/2026-10-29-2038-auth-extension-seams`):
  - authorization scope types `sso` and `scim`
  - membership source `scim`
  - SCIM token audit context
  - an SSO sign-in policy that replaces `Roster::ssoRequiredFor()`
  - `provisioned_status`
  - Guardian's permissions in Roster's catalogue
- **UI.** Only through Atrium's screen extensions registry (spec `Atrium/2026-10-29-2037-screen-extensions-registry`). Guardian registers:
  - `sso` and `scim` tabs on `roster.organization`
  - an "SSO identities" section on the `roster.user` overview slot
  - its own SSO connection screen (`guardian.sso-connection`, moved from Roster's `ui/sso/show`) as a Guardian plugin page

  Guardian's views use `x-atrium::*` components only, with no stylesheet.
- **Surfaces.** Guardian has its own MCP server (`GuardianServer`), HTTP API, Cortex integration through Keystone, and a Boost skill and README. Surface parity follows the suite standard.
- **Suggestions.** Socialite, SAML2, `php-jwt` and Microsoft Azure move from Roster's `suggest` to Guardian's.

## Decisions

- D-001: the spec lives at the workspace root because Guardian doesn't exist yet. The factory scaffolds Guardian and builds there. (User, 2026-10-29.)
- D-002: tables are renamed to `guardian_*`, and an upgrade migration carries existing Roster data across. (User, 2026-10-29.)
- D-003: Guardian requires Roster, because SSO/SCIM provision Roster users and memberships. Roster must never require Guardian.
- D-004: Guardian's UI reaches Roster screens only through Atrium registry keys, so it uses no Roster view or controller internals.
- D-005: Guardian only **suggests** `refactor-circus/atrium`, and does not require it. Its UI registrations, plugin page and Atrium routes load only when Atrium is installed (the add-on checks for its host). Roster currently requires Atrium, so in practice it is present, but Guardian doesn't rely on that. (User, 2026-10-29.)
- D-006: permissions are renamed to `guardian.sso.*` / `guardian.scim.*` and registered in Roster's catalogue through the seam. The upgrade migration renames existing role grants of `roster.sso.*` / `roster.scim.*` to the new names. (User, 2026-10-29.)
- D-007: Guardian's tabs on `roster.organization` are `guardian.sso` and `guardian.scim` (vendor-prefixed, Atrium D-008). This removes the reserved-key clash with Roster's built-in `sso` / `scim` while both exist, so Guardian can merge before Roster's removal.
- D-008: open for spec review: whether the Guardian Atrium plugin page or the Roster organization tab is the main home for managing SSO connections.

## Context

- **Depends on (merged first):**
  - Atrium `screen-extensions-registry`
  - Roster `auth-extension-seams`
  - Roster `adopt-atrium-screen-registry`
- **Followed by:** Roster `remove-sso-and-scim`, which deletes the originals. Until then both copies exist, side by side, with distinct tab keys (D-007).
- **Product:** Roster's roadmap lists SSO/SCIM as done. Guardian takes over that roadmap item and becomes the place for future auth and security work (MFA, session policies, IP rules).
- **References:** see `references.md`.
- **Visuals:** none. Screens keep their current Roster look.

## Standards

- architecture/domain-modules: `Domains/Sso` and `Domains/Scim` layout.
- architecture/dependencies: Guardian requires Roster and Keystone and suggests Atrium, never the reverse. Optional vendors (Socialite, …) stay behind `class_exists()` in `Sso::available()`.
- architecture/configuration: `config/guardian.php` with banners, safe security defaults, and `guardian.tables`.
- architecture/service-providers: provider shape and Keystone `PackageServiceProvider`.
- architecture/naming: namespaces, models and events.
- architecture/public-api: Guardian's public surface, plus the upgrade path from Roster.
- models/models, models/migrations, models/stored-identifiers: moved models, the rename migration, morph aliases and stored `source` values.
- models/lifecycle-events: model event classes move with the models.
- actions/actions-events, actions/domain-guards: moved actions keep the event pairs and guards.
- data/data-objects: moved Data classes.
- http/routes, http/resources, http/authorization, http/request-persist: the SSO callback routes, SCIM server routes, API resources and policies.
- errors/exceptions: moved domain exceptions.
- testing/test-layers, testing/arch-tests: the moved test suites, plus the upgrade-migration test.

## Addendum (2026-10-29): every screen declares slots and tab bar

User decision (Atrium D-013): every Atrium screen in this package declares itself with `Screen::make("{package}.{screen}")`, renders the Atrium tab bar (only visible once at least 2 tabs are visible), and declares the standard slots: `header.actions`, `before` and `after` on all screens; `aside` and `history` (when audited) on detail screens; the `row.actions` row slot on list screens. Screens without built-in tabs get a default `overview` tab. With nothing registered, every existing feature and browser test passes unchanged. A feature test per screen registers a tab and a section in each standard slot and asserts that they render.
Guardian screens: the SSO connection screen (`guardian.sso-connection`) and any other Guardian Atrium page.

## Addendum (2026-10-29): no package knows another (Atrium D-015 to D-019)

User principle (Atrium D-017): no package has knowledge of another. Companion packages are suggested, never required. Integrations are done through simple config. Consequences for this spec:
- Guardian requires only Keystone (D-018), and suggests Roster, Atrium and the SSO vendor libraries. This supersedes D-003 ("Guardian requires Roster") and D-005 (Atrium already suggested).
- Without Roster, Guardian boots and its own SSO/SCIM data and API stay available, but provisioning has no user or membership target. Spec review decides the exact behaviour: inert with a clear message, or provisioning onto the app's user model through a Keystone-level contract.
- Guardian's UI on Roster screens is placed through config (D-019): `config/guardian.php` `atrium.screens` defaults to `['roster.organization' => ['guardian.sso', 'guardian.scim'], 'roster.user' => ['aside' => 'guardian.sso-identities']]`. Guardian's code uses only Roster's public seams, behind a check that Roster is installed.
