<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Guardian\Upgrade\RosterUpgrade;

/**
 * Carries an existing install's SSO and SCIM tables and permission grants
 * over from Roster, before the create migrations run (criterion 22).
 *
 * It runs only once Roster has handed SSO and SCIM over. Until then it
 * changes nothing, but is still recorded as run, so
 * `php artisan guardian:upgrade-from-roster` finishes the job after Roster is
 * upgraded. The report goes to the log; the command prints it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $report = App::make(RosterUpgrade::class)->run();

        foreach ($report->lines() as $line) {
            $report->pending ? Log::warning('Guardian: '.$line) : Log::info('Guardian: '.$line);
        }
    }

    /**
     * Nothing to undo: the rows stay where the upgrade put them.
     */
    public function down(): void {}
};
