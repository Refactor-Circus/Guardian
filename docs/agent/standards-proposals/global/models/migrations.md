---
key: models/migrations
action: amend
kind: architectural
package: refactor-circus/standards
from_spec: 2026-10-29-2038-guardian-package
status: queued
evidence:
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/standards-feedback.md row models/migrations"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/decisions.md D-031, D-034, D-040"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/build.md Group 7 (7.3, D-040: each down() checks only its own tables; MigrationsTest)"
  - "post-mortem root cause spec_gap: 'migration down() scope (D-040)' decided only after code was written"
---

## Rationale

Guardian's create migrations had to break the plain create/drop rule. The Roster upgrade renames existing tables into place, so a plain create would fail and a plain down() would delete a customer's SSO connections and SCIM tokens. The user settled the behaviour in two decisions: refuse to drop tables with rows unless forced (D-031) and check only the tables each migration drops (D-040). D-040 surfaced only in code review after the code was written, which the post-mortem lists as a spec gap. Writing the pattern down means the next package that takes over tables (the suite is splitting features out of Roster) gets it right from the spec.

## Proposed text

# Migrations

Package migrations use a fixed date plus a sequence number and one file per feature's tables. They take table names from the package's configurable tables map and are publishable. A migration whose tables may already hold another package's data never overwrites that data and never silently drops it.

## Naming and order

```
2026_01_01_000001_create_{package}_{feature}_tables.php
2026_01_01_000002_create_{package}_{other_feature}_tables.php
```

- Use a fixed date with an incrementing sequence, so package migrations run in a stable, reviewable order in any host app, whenever they were written. The next migration takes the next sequence number.
- One migration creates all of a feature's tables (parent, children, pivots). `down()` drops them in reverse dependency order.
- Write anonymous class migrations with `declare(strict_types=1)`.

## Table names

- `Schema::create()`, `constrained()` and `Schema::dropIfExists()` take the table name from the same tables class and keys the models use, never a literal `{package}_*` string. A host renames a table with one config change made before the migrations run. See [[models]] and [[configuration]].
- Foreign keys go through `constrained(<configured parent table>)` with an explicit cascade rule. Add the compound uniques and indexes the domain needs.

```php
Schema::create(Tables::name('{entity}_items'), function (Blueprint $table): void {
    $table->id();
    $table->foreignId('{entity}_id')
        ->constrained(Tables::name('{entities}'))
        ->cascadeOnDelete();
    $table->timestamps();
});
```

## Tables taken over from another package

Sometimes a package inherits tables that another package created, for example when a feature moves between packages and an upgrade step renames the old tables into place before the create migrations run.

- The upgrade step (a migration with an earlier date plus an artisan command) renames the old tables. It runs only once the old owner no longer uses them.
- Each create migration skips a `Schema::create()` whose table already exists, so it never fails on, or recreates, a table that was renamed into place.
- `down()` refuses to drop a table that holds rows. It throws a package exception and drops nothing, unless a `{package}.migrations.force_rollback` config flag is on.
- Each `down()` checks only the tables that migration drops. Rows in another feature's tables never block its rollback.
- Test both drivers the rename runs on (SQLite plus a MySQL CI lane), so foreign keys are proven to survive the rename.

```php
public function down(): void
{
    $tables = [Tables::name('{feature}_identities'), Tables::name('{feature}_connections')];

    if (config('{package}.migrations.force_rollback') !== true) {
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw RollbackRefusedException::forTable($table);
            }
        }
    }

    foreach ($tables as $table) {
        Schema::dropIfExists($table);
    }
}
```

## Loading and publishing

- The package service provider loads migrations from `database/migrations` and registers them with `publishesMigrations()` under the package tag and a `{package}-migrations` tag, so a host can publish and edit them. See [[service-providers]].

## Reference implementation (Refactor Circus)

- Migrations call the package's `Support\Tables::name($key)` (on `RefactorCircus\Keystone\Database\Tables`).
- `tests/ArchTest.php` runs `Arch::literalTableNames()` over the migrations as well as `src/`.
- Guardian's `2026_11_01_000001` / `000002` create migrations and its `Upgrade\RosterUpgrade` (`2026_11_01_000000`, `guardian:upgrade-from-roster`) follow the takeover rules.

