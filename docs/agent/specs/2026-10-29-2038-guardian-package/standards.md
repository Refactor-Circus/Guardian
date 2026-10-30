# Standards for Guardian package

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| actions/actions-events | vendor/refactor-circus/standards/standards/actions/actions-events.md | refactor-circus/standards@dev-main | Moved SSO/SCIM actions keep their event pairs and transactions |
| actions/domain-guards | vendor/refactor-circus/standards/standards/actions/domain-guards.md | refactor-circus/standards@dev-main | Moved actions keep their ValidationException guards |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | New config/guardian.php: banners, safe defaults, guardian.tables, atrium.screens |
| architecture/container-bindings | vendor/refactor-circus/standards/standards/architecture/container-bindings.md | refactor-circus/standards@dev-main | Integration/Handover singletons, scoped ScimContext |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | Keystone-only require; Roster, Atrium and SSO vendors suggested and gated |
| architecture/domain-modules | vendor/refactor-circus/standards/standards/architecture/domain-modules.md | refactor-circus/standards@dev-main | Domains/Sso and Domains/Scim layout, DomainServiceProvider, morph aliases for moved models |
| architecture/naming | vendor/refactor-circus/standards/standards/architecture/naming.md | refactor-circus/standards@dev-main | RefactorCircus\Guardian namespaces, guardian.* keys and names |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | Guardian's public surface and the upgrade path from Roster |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | GuardianServiceProvider on Keystone PackageServiceProvider, boot gating |
| data/data-objects | vendor/refactor-circus/standards/standards/data/data-objects.md | refactor-circus/standards@dev-main | Moved Data classes as action input |
| errors/exceptions | vendor/refactor-circus/standards/standards/errors/exceptions.md | refactor-circus/standards@dev-main | GuardianException base and moved domain exceptions |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | guardian.policies, policies answering through Roster's Authorizer, same check on every surface |
| http/request-persist | vendor/refactor-circus/standards/standards/http/request-persist.md | refactor-circus/standards@dev-main | Moved requests on Guardian's request bases |
| http/resources | vendor/refactor-circus/standards/standards/http/resources.md | refactor-circus/standards@dev-main | Moved SSO/SCIM resources, unchanged payloads |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | API, SSO web and SCIM server routes under guardian.* names |
| models/lifecycle-events | vendor/refactor-circus/standards/standards/models/lifecycle-events.md | refactor-circus/standards@dev-main | Model event classes move with the five models |
| models/migrations | vendor/refactor-circus/standards/standards/models/migrations.md | refactor-circus/standards@dev-main | Create migrations and the upgrade migration (hasTable departure noted) |
| models/models | vendor/refactor-circus/standards/standards/models/models.md | refactor-circus/standards@dev-main | Moved models, Tables-driven getTable(), policies |
| models/stored-identifiers | vendor/refactor-circus/standards/standards/models/stored-identifiers.md | refactor-circus/standards@dev-main | Old Roster class names kept as morph aliases; stored scim membership source |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | ArchTest with php, security and the shared refactorCircus preset plus Keystone Arch finders |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Moved suites, upgrade tests, Standalone and HandoverPending modes |
