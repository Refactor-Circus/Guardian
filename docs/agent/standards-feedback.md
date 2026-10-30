# Standards Feedback

Every time work has to break a standard, add a row. `/ring-master-discover-standards` reads this log, so changes to the shared standards are based on evidence.

| Date | Spec | Standard | Deviation | Why |
| --- | --- | --- | --- | --- |
| 2026-10-30 | 2026-10-29-2038-guardian-package | models/migrations | The create migrations (`2026_11_01_000001` / `000002`) skip each `Schema::create()` whose table already exists, and their `down()` throws `RollbackRefusedException` (a `GuardianException`), dropping nothing, while any Guardian table holds rows, unless `guardian.migrations.force_rollback` is on. | The upgrade from Roster renames Roster's SSO/SCIM tables into place before these run, so a plain create would fail and a plain `down()` would delete an install's connections, identities and tokens (spec criterion 21, D-031). |
| 2026-10-30 | 2026-10-29-2038-guardian-package | architecture/dependencies | `src/Upgrade` (`Handover`, `RosterUpgrade`) names Roster's SSO connection class, its `roster_*` SSO/SCIM table names, its `roster.sso.*` / `roster.scim.*` permission names and config keys, and its `permissions` / `permission_role` tables as strings, and writes those tables with the query builder rather than through Roster's models or actions. | The upgrade path is about Roster by definition: it must detect whether Roster still ships SSO without loading the class, and move data Roster no longer has models or actions for (spec Design, criterion 22). |
