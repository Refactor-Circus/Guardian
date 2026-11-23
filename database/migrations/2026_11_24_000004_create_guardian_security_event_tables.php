<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Exceptions\RollbackRefusedException;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Guardian\Support\UserKey;

/**
 * Guardian's security event log (criterion 22). Needs nothing from Roster.
 * A table already in place is left alone; down() drops nothing while it
 * holds rows, unless `guardian.migrations.force_rollback` is on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $events = Tables::name('security_events');

        if (Schema::hasTable($events)) {
            return;
        }

        Schema::create($events, function (Blueprint $table) use ($events): void {
            $table->ulid('id')->primary();
            $table->string('type', 64);
            UserKey::column($table, 'user_id')->nullable();
            // HMAC-SHA256 of the lowercased sign-in identifier, keyed by the app key.
            $table->string('identifier_hash', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');

            $table->index(['user_id', 'occurred_at'], $events.'_user_index');
            $table->index(['type', 'occurred_at'], $events.'_type_index');
        });
    }

    public function down(): void
    {
        $events = Tables::name('security_events');

        if (Config::get('guardian.migrations.force_rollback') !== true && Schema::hasTable($events) && DB::table($events)->exists()) {
            throw RollbackRefusedException::forTables([$events]);
        }

        Schema::dropIfExists($events);
    }
};
