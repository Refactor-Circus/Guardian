<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Console\Commands;

use Illuminate\Console\Command;
use RefactorCircus\Guardian\Upgrade\RosterRoleAdoption;

/**
 * Copies Roster v0.1's role tables into Guardian's (criterion 20). Safe to
 * run again, and from a deploy script: it changes nothing once adopted, or
 * while Guardian holds roles of its own, and never touches Roster's tables.
 */
final class AdoptRosterRolesCommand extends Command
{
    protected $signature = 'guardian:adopt-roster-roles';

    protected $description = "Copy roles, permissions and assignments from Roster's old role tables into Guardian's";

    public function handle(RosterRoleAdoption $adoption): int
    {
        foreach ($adoption->run()->lines() as $line) {
            $this->components->info($line);
        }

        return self::SUCCESS;
    }
}
