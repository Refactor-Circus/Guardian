<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Exceptions\RollbackRefusedException;
use RefactorCircus\Guardian\Support\Tables;

/**
 * Guardian's IP rules (criterion 52): addresses and ranges (IPv4 or IPv6
 * CIDR) allowed or denied, optionally until a time. Needs nothing from
 * Roster. A table already in place is left alone; down() drops nothing
 * while it holds rows, unless `guardian.migrations.force_rollback` is on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rules = Tables::name('ip_rules');

        if (Schema::hasTable($rules)) {
            return;
        }

        Schema::create($rules, function (Blueprint $table) use ($rules): void {
            $table->ulid('id')->primary();
            $table->string('cidr', 49);
            $table->string('action', 5);
            $table->string('description')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('created_by', 64)->nullable();
            $table->timestamps();

            $table->index('action', $rules.'_action_index');
        });
    }

    public function down(): void
    {
        $rules = Tables::name('ip_rules');

        if (Config::get('guardian.migrations.force_rollback') !== true && Schema::hasTable($rules) && DB::table($rules)->exists()) {
            throw RollbackRefusedException::forTables([$rules]);
        }

        Schema::dropIfExists($rules);
    }
};
