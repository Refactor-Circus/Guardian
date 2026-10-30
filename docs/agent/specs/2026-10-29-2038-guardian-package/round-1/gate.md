VERDICT: PASS

Gate results (run in the job worktree):
- `composer test`: passed (phpstan 0 errors, pint passed, type coverage 100%, pest 496 tests / 1931 assertions, all passed).
- `composer lint:check`: passed.
- `composer exec ringside check`: passed.

Criteria to proof (by test file; names are in the files):
1. Composer: tests/Feature/PackageWiringTest.php
2. Local install: tests/Feature/PackageWiringTest.php (scripts, bin/composer-local.php parity)
3. Arch preset: tests/ArchTest.php, tests/Pest.php
4. Ring Master files: tests/Feature/PackageWiringTest.php, `ringside check`
5. Package shape and tables: tests/Feature/PackageShapeTest.php, tests/Modes/RenamedTables/RenamedTablesTest.php
6. Config: tests/Feature/ConfigTest.php
7. Roster optional, inert: tests/Modes/Standalone/StandaloneTest.php, tests/ArchTest.php
8. Moved domains: tests/Feature/{Sso,Scim,Http,Ui}, tests/Modes/SsoUnavailable, tests/Feature/MovedTestsManifestTest.php, tests/moved-tests.json
9. Roster boundary: tests/Feature/RosterBoundaryTest.php, tests/ArchTest.php
10. Resources and permissions: tests/Feature/Authorization/PermissionsTest.php
11. Membership source: tests/Feature/Scim/MembershipSourceTest.php
12. Sign-in requirement: tests/Feature/Sso/SsoWiringTest.php
13. Provisioned status: tests/Feature/Scim/ProvisionedStatusTest.php
14. SCIM audit context: tests/Feature/Scim/ScimAuditContextTest.php
15. Guardian audit: tests/Feature/Sso/SsoAuditTest.php, tests/Feature/Scim/ScimAuditTest.php
16. Placement: tests/Feature/Atrium/PlacementTest.php
17. Org tabs: tests/Feature/Atrium/OrganizationTabsTest.php
18. User section: tests/Feature/Atrium/UserSectionTest.php
19. Own screen: tests/Feature/Atrium/SsoConnectionScreenTest.php
20. Views and styles: tests/Feature/StylesTest.php
21. Create migrations: tests/Feature/MigrationsTest.php
22. Upgrade: tests/Feature/Upgrade/{TablesTest,GrantsTest,StrandedStateTest,LeftoverConfigTest,CommandTest}.php
23. Morph aliases: tests/Feature/MorphAliasesTest.php
24. Parity: tests/ArchTest.php
25. HTTP API: tests/Feature/Http/SsoHttpTest.php, ScimTokensHttpTest.php
26. MCP: tests/Feature/Mcp/{GuardianServerTest,SsoToolsTest,ScimToolsTest}.php
27. Docs: tests/Feature/DocsTest.php
28. Coexistence: tests/TestCase.php, tests/Modes/HandoverPending/HandoverPendingTest.php
29. Gate: this run
30. Without Atrium: tests/Modes/WithoutAtrium/WithoutAtriumTest.php
31. Handover routes: tests/Feature/HandoverRoutesTest.php, HandoverPending mode

Not run here: the CI-only `standalone`, `without-atrium` and `mysql` lanes and `composer test:browser`.
