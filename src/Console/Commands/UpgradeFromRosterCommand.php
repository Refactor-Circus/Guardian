<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Console\Commands;

use Illuminate\Console\Command;
use RefactorCircus\Guardian\Upgrade\RosterUpgrade;

/**
 * Finishes the upgrade from Roster: renames Roster's SSO and SCIM tables into
 * Guardian's, moves the permission grants and lists leftover `roster.*`
 * config. Safe to run again, and from a deploy script; while Roster still
 * owns SSO and SCIM it changes nothing and says so.
 */
final class UpgradeFromRosterCommand extends Command
{
    protected $signature = 'guardian:upgrade-from-roster';

    protected $description = 'Move SSO and SCIM data, permissions and grants over from Roster';

    public function handle(RosterUpgrade $upgrade): int
    {
        $report = $upgrade->run();

        foreach ($report->lines() as $line) {
            $report->pending ? $this->components->warn($line) : $this->components->info($line);
        }

        return self::SUCCESS;
    }
}
