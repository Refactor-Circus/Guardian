# Product Roadmap

Guardian is the auth and security package of the Refactor Circus suite. It starts with the SSO and SCIM features moved out of Roster, behaviour unchanged, and grows the rest of the suite's security controls on Roster's seams.

## Done

- **SSO**: SAML, OIDC and Microsoft Entra ID sign-in per organization, with domain enforcement through Roster's sign-in requirement, linked identities, an HTTP API, MCP tools and Atrium screens. Moved from Roster (spec `2026-10-29-2038-guardian-package`).
- **SCIM**: SCIM 2.0 user and group provisioning per organization, with provisioning tokens, an HTTP API, MCP tools and Atrium screens. Moved from Roster (spec `2026-10-29-2038-guardian-package`).

## Planned

- **MFA**: multi-factor authentication for Roster users, with per-organization enforcement.
- **Session policies**: idle and absolute session lifetimes, concurrent session limits and forced sign-out, per organization.
- **IP rules**: allow and deny lists of IP ranges for sign-in and API access, per organization.

Specs and decision logs: `docs/agent/specs/`.
