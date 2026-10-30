VERDICT: PASS

Gate results (round 1):
- composer test: passed (phpstan 0 errors, pint passed, pest 546 tests / 2043 assertions, 100% type coverage).
- composer lint:check: passed.
- composer exec ringside check: passed.

Criteria to proof (file-level mapping from test names in the diff; not every test body was re-read):
1. tests/Feature/PackageWiringTest.php
2. tests/Feature/PackageWiringTest.php (scripts and bin/composer-local.php equal Keen's)
3. tests/ArchTest.php
4. tests/Feature/PackageWiringTest.php
5. tests/Feature/PackageShapeTest.php, tests/Modes/RenamedTables/RenamedTablesTest.php
6. tests/Feature/ConfigTest.php
7. tests/Modes/Standalone/StandaloneTest.php, tests/ArchTest.php, tests/Feature/RosterBoundaryTest.php
8. tests/Feature/Sso/*, tests/Feature/Scim/*, tests/Feature/Http/*, tests/Feature/Ui/*, tests/Modes/SsoUnavailable, tests/Feature/MovedTestsManifestTest.php, tests/moved-tests.json
9. tests/ArchTest.php, tests/Feature/RosterBoundaryTest.php
10. tests/Feature/Authorization/PermissionsTest.php
11. tests/Feature/Scim/MembershipSourceTest.php
12. tests/Feature/Sso/SsoWiringTest.php
13. tests/Feature/Scim/ProvisionedStatusTest.php
14. tests/Feature/Scim/ScimAuditContextTest.php
15. tests/Feature/Sso/SsoAuditTest.php, tests/Feature/Scim/ScimAuditTest.php
16. tests/Feature/Atrium/PlacementTest.php
17. tests/Feature/Atrium/OrganizationTabsTest.php
18. tests/Feature/Atrium/UserSectionTest.php
19. tests/Feature/Atrium/SsoConnectionScreenTest.php
20. tests/Feature/StylesTest.php
21. tests/Feature/MigrationsTest.php
22. tests/Feature/Upgrade/* (TablesTest, GrantsTest, StrandedStateTest, LeftoverConfigTest, CommandTest)
23. tests/Feature/MorphAliasesTest.php
24. tests/ArchTest.php
25. tests/Feature/Http/SsoHttpTest.php, ScimTokensHttpTest.php
26. tests/Feature/Mcp/GuardianServerTest.php, SsoToolsTest.php, ScimToolsTest.php
27. tests/Feature/DocsTest.php
28. tests/Modes/HandoverPending/HandoverPendingTest.php, tests/TestCase.php
29. composer test green; browser tests in tests/Browser
30. tests/Modes/WithoutAtrium/WithoutAtriumTest.php
31. tests/Feature/HandoverRoutesTest.php

Observations (non-blocking):
- Scaffold leftovers remain in the diff: src/Console/Commands/GuardianCommand.php, database/migrations/2026_01_01_000000_create_guardian_placeholder_table.php, resources/views/placeholder.blade.php, tests/Feature/ExampleTest.php, tests/Unit/ExampleTest.php.
- CI jobs (standalone, without-atrium, mysql) were not run locally.
