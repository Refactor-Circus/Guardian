# Standards for Guardian v2: security platform

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| actions/actions-events | vendor/refactor-circus/standards/standards/actions/actions-events.md | refactor-circus/standards@dev-main | Every new action (roles, 2FA, sessions, tokens, security events) dispatches its starting/finished pair, which Keen audits |
| actions/domain-guards | vendor/refactor-circus/standards/standards/actions/domain-guards.md | refactor-circus/standards@dev-main | Escalation guard, last-factor guard, token ability and lifetime limits, system role and permission protection |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | New sections with banners (`authorization`, `two_factor`, `passwords`, `tokens`, ...); `ui` and `atrium` removed |
| architecture/container-bindings | vendor/refactor-circus/standards/standards/architecture/container-bindings.md | refactor-circus/standards@dev-main | Scoped override of Keystone's `Permissions`/`Roles`; `TokenDriver`, `OneTimeCodeChannel` and `TwoFactorRequirement` contracts with default bindings |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | Keystone is the only suite require; Sanctum, Passport and WebAuthn are optional; the Roster reference is kept and flagged, not widened |
| architecture/domain-modules | vendor/refactor-circus/standards/standards/architecture/domain-modules.md | refactor-circus/standards@dev-main | Eight new domains under `src/Domains`; `src/Atrium` deleted |
| architecture/naming | vendor/refactor-circus/standards/standards/architecture/naming.md | refactor-circus/standards@dev-main | Names of the new actions, events, requests, resources, tools and middleware |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | v2.0.0 removes the Atrium UI and adds the security API and contracts |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | Provider drops the Atrium plugin, binds the contracts and registers middleware aliases and listeners |
| data/data-objects | vendor/refactor-circus/standards/standards/data/data-objects.md | refactor-circus/standards@dev-main | Action input Data for every new action |
| data/value-objects | vendor/refactor-circus/standards/standards/data/value-objects.md | refactor-circus/standards@dev-main | `TokenSummary`, `IssuedToken` and `Requirement` as final read-only value objects |
| errors/exceptions | vendor/refactor-circus/standards/standards/errors/exceptions.md | refactor-circus/standards@dev-main | `TokenDriverException` and the other new exceptions extend `GuardianException` |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | Every new route is authorized through Keystone's `Authorizer`/contract with 401 and 403 cases |
| http/request-persist | vendor/refactor-circus/standards/standards/http/request-persist.md | refactor-circus/standards@dev-main | Requests own the work through `persist()` |
| http/resources | vendor/refactor-circus/standards/standards/http/resources.md | refactor-circus/standards@dev-main | Final resources shared by HTTP and MCP; secrets never serialized |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | New `guardian.*` routes inside the configured group, per domain `routes.php` |
| models/lifecycle-events | vendor/refactor-circus/standards/standards/models/lifecycle-events.md | refactor-circus/standards@dev-main | New models dispatch their lifecycle events; cache forgetting listens to them |
| models/migrations | vendor/refactor-circus/standards/standards/models/migrations.md | refactor-circus/standards@dev-main | New tables with configurable names, skip-if-exists, guarded `down()`; adoption migration |
| models/models | vendor/refactor-circus/standards/standards/models/models.md | refactor-circus/standards@dev-main | Final `*Model` classes with encrypted casts for secrets |
| models/stored-identifiers | vendor/refactor-circus/standards/standards/models/stored-identifiers.md | refactor-circus/standards@dev-main | ULID keys, scope morph columns, user key type |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | No Atrium, no Roster in new domains, no Scramble in src, Sanctum/Passport/WebAuthn isolated, parity |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Behaviour at the action layer; HTTP/MCP test wiring by route name; fakes for HIBP, channels and drivers |
