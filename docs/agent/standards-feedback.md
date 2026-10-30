# Standards Feedback

Every time work has to break a standard, add a row. `/ring-master-discover-standards` reads this log, so changes to the shared standards are based on evidence.

| Date | Spec | Standard | Deviation | Why |
| --- | --- | --- | --- | --- |
| 2026-10-30 | 2026-10-29-2038-guardian-package | models/migrations | The create migrations (`2026_11_01_000001` / `000002`) skip each `Schema::create()` whose table already exists, and their `down()` throws `RollbackRefusedException` (a `GuardianException`), dropping nothing, while any Guardian table holds rows, unless `guardian.migrations.force_rollback` is on. | The upgrade from Roster renames Roster's SSO/SCIM tables into place before these run, so a plain create would fail and a plain `down()` would delete an install's connections, identities and tokens (spec criterion 21, D-031). |
