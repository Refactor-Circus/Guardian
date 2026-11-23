<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Guardian\Upgrade\RosterRoleAdoption;

/**
 * Copies Roster v0.1's roles, permissions and assignments into Guardian's
 * role tables (criterion 20), once they exist. With no Roster role tables,
 * or once adopted, it changes nothing; `php artisan guardian:adopt-roster-roles`
 * runs it again later. The report goes to the log; the command prints it.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (App::make(RosterRoleAdoption::class)->run()->lines() as $line) {
            Log::info('Guardian: '.$line);
        }
    }

    /**
     * Nothing to undo: the copied rows stay, and Roster's tables were never
     * touched.
     */
    public function down(): void {}
};
