<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Upgrade;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Date;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;

/**
 * Carries an install's SSO and SCIM data over from Roster (criterion 22).
 *
 * Runs from the `2026_11_01_000000` migration and from
 * `php artisan guardian:upgrade-from-roster`, and only once Roster has handed
 * SSO and SCIM over (`Handover::complete()`): before that it changes nothing.
 * It is idempotent, safe in a deploy script, and never deletes a row that
 * holds data.
 *
 * DDL is not transactional (MySQL commits implicitly), so every table is
 * planned before anything changes, then acted on in two passes: empty
 * Guardian tables in the way are dropped children first, then Roster's
 * tables are renamed parents first. A run that fails midway leaves a state
 * the next run plans from and completes. The permission renames are DML and
 * run in one transaction.
 *
 * Roster's table, permission and config names appear here as strings: the
 * upgrade is about Roster by definition (architecture/dependencies departure,
 * docs/agent/standards-feedback.md).
 */
final class RosterUpgrade
{
    /**
     * Guardian's table keys, parents before the tables that reference them.
     *
     * @var list<string>
     */
    public const array TABLES = ['sso_connections', 'sso_identities', 'scim_tokens', 'scim_users', 'scim_groups'];

    /**
     * Roster's permission names => Guardian's.
     *
     * @var array<string, string>
     */
    public const array PERMISSIONS = [
        'roster.sso.view' => 'guardian.sso.view',
        'roster.sso.manage' => 'guardian.sso.manage',
        'roster.scim.manage' => 'guardian.scim.manage',
    ];

    /**
     * The config sections Roster read and Guardian now reads under `guardian.`.
     *
     * @var list<string>
     */
    private const array CONFIG_SECTIONS = ['sso', 'scim'];

    private const string RENAME = 'rename';

    private const string REPLACE = 'replace';

    private const string CONFLICT = 'conflict';

    public function __construct(
        private readonly Handover $handover,
        private readonly Repository $config,
        private readonly DatabaseManager $db,
        private readonly Permissions $permissions,
    ) {}

    public function run(): UpgradeReport
    {
        $report = new UpgradeReport;

        if (! $this->handover->complete()) {
            return $report->pending();
        }

        $this->moveTables($report);
        $this->moveGrants($report);
        $this->findLeftoverConfig($report);

        return $report;
    }

    /**
     * Roster's tables that still hold rows while the Guardian table they
     * belong in is empty or missing: the upgrade was skipped before Roster
     * let go, so the create migrations made empty tables (D-029). Empty
     * until the handover is complete.
     *
     * @return list<string>
     */
    public function stranded(): array
    {
        if (! $this->handover->complete()) {
            return [];
        }

        $schema = $this->connection()->getSchemaBuilder();
        $stranded = [];

        foreach (self::TABLES as $key) {
            $source = $this->source($key);
            $target = Tables::name($key);

            if ($source !== $target && $schema->hasTable($source) && $this->hasRows($source) && ! ($schema->hasTable($target) && $this->hasRows($target))) {
                $stranded[] = $source;
            }
        }

        return $stranded;
    }

    /**
     * Plan every table, then drop the empty Guardian tables in the way
     * (children first) and rename Roster's into place (parents first).
     */
    private function moveTables(UpgradeReport $report): void
    {
        $schema = $this->connection()->getSchemaBuilder();
        $plan = $this->plan();

        foreach (array_reverse($plan, true) as $key => $action) {
            if ($action === self::REPLACE) {
                $schema->drop(Tables::name($key));
                $report->dropped(Tables::name($key));
            }
        }

        foreach ($plan as $key => $action) {
            if ($action === self::RENAME || $action === self::REPLACE) {
                $schema->rename($this->source($key), Tables::name($key));
                $report->renamed($this->source($key), Tables::name($key));
            }

            if ($action === self::CONFLICT) {
                $report->conflicts($this->source($key), Tables::name($key));
            }
        }
    }

    /**
     * What to do with each table, decided before anything changes. Tables
     * with nothing to do are left out.
     *
     * @return array<string, string> table key => action, parents first
     */
    private function plan(): array
    {
        $schema = $this->connection()->getSchemaBuilder();
        $plan = [];

        foreach (self::TABLES as $key) {
            $source = $this->source($key);
            $target = Tables::name($key);

            if ($source === $target || ! $schema->hasTable($source)) {
                continue;
            }

            $plan[$key] = match (true) {
                ! $schema->hasTable($target) => self::RENAME,
                $this->hasRows($target) => self::CONFLICT,
                default => self::REPLACE,
            };
        }

        return $plan;
    }

    /**
     * Rename Roster's SSO and SCIM permissions to Guardian's, keeping every
     * role's grant. Where both names exist, the old grants join the new row
     * and the old row goes. The only Roster tables that hold permission
     * names or grants are `permissions` and `permission_role`.
     */
    private function moveGrants(UpgradeReport $report): void
    {
        $connection = $this->connection();
        $permissions = $this->rosterTable('permissions');
        $pivot = $this->rosterTable('permission_role');

        if (! $connection->getSchemaBuilder()->hasTable($permissions) || ! $connection->getSchemaBuilder()->hasTable($pivot)) {
            return;
        }

        $moved = $connection->transaction(function () use ($connection, $permissions, $pivot): array {
            $moved = [];

            foreach (self::PERMISSIONS as $from => $to) {
                $old = $connection->table($permissions)->where('name', $from)->value('id');

                if ($old === null) {
                    continue;
                }

                $new = $connection->table($permissions)->where('name', $to)->value('id');

                if ($new === null) {
                    $connection->table($permissions)->where('id', $old)->update(['name' => $to, 'updated_at' => Date::now()]);
                } else {
                    $held = $connection->table($pivot)->where('permission_id', $new)->pluck('role_id')->all();

                    foreach ($connection->table($pivot)->where('permission_id', $old)->pluck('role_id') as $role) {
                        if (! in_array($role, $held, true)) {
                            $connection->table($pivot)->insert(['role_id' => $role, 'permission_id' => $new]);
                        }
                    }

                    $connection->table($pivot)->where('permission_id', $old)->delete();
                    $connection->table($permissions)->where('id', $old)->delete();
                }

                $moved[$from] = $to;
            }

            return $moved;
        });

        foreach ($moved as $from => $to) {
            $report->grants($from, $to);
        }

        if ($moved !== []) {
            // Written without Roster's models, so its remembered grants and
            // any cached copy go too.
            $this->permissions->forgetCache();
        }
    }

    /**
     * Guardian never reads `roster.sso.*` / `roster.scim.*` (D-030): list each
     * value still set that differs from Guardian's, with the key to move it
     * to. Lists are compared whole.
     */
    private function findLeftoverConfig(UpgradeReport $report): void
    {
        foreach (self::CONFIG_SECTIONS as $section) {
            $values = $this->config->get("roster.{$section}");

            if (! is_array($values)) {
                continue;
            }

            foreach ($this->leaves($values) as $path => $value) {
                $guardianKey = "guardian.{$section}.{$path}";

                if ($this->config->get($guardianKey) !== $value) {
                    $report->leftovers("roster.{$section}.{$path}", $guardianKey);
                }
            }
        }
    }

    /**
     * A config section's values by dotted path, with lists kept whole.
     *
     * @param  array<mixed>  $values
     * @return array<string, mixed>
     */
    private function leaves(array $values, string $prefix = ''): array
    {
        $leaves = [];

        foreach ($values as $key => $value) {
            if (is_array($value) && $value !== [] && ! array_is_list($value)) {
                $leaves += $this->leaves($value, $prefix.$key.'.');

                continue;
            }

            $leaves[$prefix.$key] = $value;
        }

        return $leaves;
    }

    /**
     * Where Roster kept a Guardian table's rows.
     */
    private function source(string $key): string
    {
        return $this->rosterTable($key);
    }

    /**
     * One of Roster's tables by key, as Roster's config names it.
     */
    private function rosterTable(string $key): string
    {
        $table = $this->config->get("roster.tables.{$key}");

        return is_string($table) && $table !== '' ? $table : 'roster_'.$key;
    }

    private function hasRows(string $table): bool
    {
        return $this->connection()->table($table)->exists();
    }

    private function connection(): Connection
    {
        return $this->db->connection();
    }
}
