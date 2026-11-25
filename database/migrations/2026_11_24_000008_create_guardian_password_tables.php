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
 * Guardian's password history (criterion 41): the hashes of each user's
 * last passwords, to refuse reuse and to tell when a password was last
 * changed. Needs nothing from Roster. A table already in place is left
 * alone; down() drops nothing while it holds rows, unless
 * `guardian.migrations.force_rollback` is on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $histories = Tables::name('password_histories');

        if (Schema::hasTable($histories)) {
            return;
        }

        Schema::create($histories, function (Blueprint $table) use ($histories): void {
            $table->ulid('id')->primary();
            UserKey::column($table, 'user_id');
            $table->string('password_hash');
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at'], $histories.'_user_created_index');
        });
    }

    public function down(): void
    {
        $histories = Tables::name('password_histories');

        if (Config::get('guardian.migrations.force_rollback') !== true && Schema::hasTable($histories) && DB::table($histories)->exists()) {
            throw RollbackRefusedException::forTables([$histories]);
        }

        Schema::dropIfExists($histories);
    }
};
