<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Upgrade;

/**
 * What one run of the upgrade from Roster did, and what it left for a person
 * to do. A value object, not action input, so it lives beside the upgrade.
 */
final class UpgradeReport
{
    public const string PENDING = 'Roster still owns SSO and SCIM; run `php artisan guardian:upgrade-from-roster` after upgrading Roster.';

    /**
     * Whether the run stopped at the handover gate and changed nothing.
     */
    public private(set) bool $pending = false;

    /**
     * Empty Guardian tables dropped to make way for Roster's.
     *
     * @var list<string>
     */
    public private(set) array $dropped = [];

    /**
     * Roster table => the Guardian table it was renamed to.
     *
     * @var array<string, string>
     */
    public private(set) array $renamed = [];

    /**
     * Roster table => the Guardian table that already holds rows, so neither
     * was touched.
     *
     * @var array<string, string>
     */
    public private(set) array $conflicts = [];

    /**
     * Roster permission => the Guardian permission its grants moved to.
     *
     * @var array<string, string>
     */
    public private(set) array $grants = [];

    /**
     * Roster config key still set => the Guardian key to move its value to.
     *
     * @var array<string, string>
     */
    public private(set) array $leftovers = [];

    public function pending(bool $pending = true): static
    {
        $this->pending = $pending;

        return $this;
    }

    public function dropped(string $table): static
    {
        $this->dropped[] = $table;

        return $this;
    }

    public function renamed(string $from, string $to): static
    {
        $this->renamed[$from] = $to;

        return $this;
    }

    public function conflicts(string $roster, string $guardian): static
    {
        $this->conflicts[$roster] = $guardian;

        return $this;
    }

    public function grants(string $from, string $to): static
    {
        $this->grants[$from] = $to;

        return $this;
    }

    public function leftovers(string $rosterKey, string $guardianKey): static
    {
        $this->leftovers[$rosterKey] = $guardianKey;

        return $this;
    }

    /**
     * The report, a line per thing done or left to do.
     *
     * @return list<string>
     */
    public function lines(): array
    {
        if ($this->pending) {
            return [self::PENDING];
        }

        $lines = [];

        foreach ($this->dropped as $table) {
            $lines[] = "Dropped the empty table [{$table}] to make way for Roster's.";
        }

        foreach ($this->renamed as $from => $to) {
            $lines[] = "Renamed [{$from}] to [{$to}].";
        }

        foreach ($this->conflicts as $roster => $guardian) {
            $lines[] = "Left [{$roster}] alone: [{$guardian}] already holds rows. Move what you need by hand, then run `php artisan guardian:upgrade-from-roster` again.";
        }

        foreach ($this->grants as $from => $to) {
            $lines[] = "Moved the [{$from}] permission and its grants to [{$to}].";
        }

        foreach ($this->leftovers as $rosterKey => $guardianKey) {
            $lines[] = "Config [{$rosterKey}] is set, but Guardian never reads it: move the value to [{$guardianKey}].";
        }

        return $lines === [] ? ['Nothing to upgrade: Guardian already holds the SSO and SCIM tables and permissions.'] : $lines;
    }
}
