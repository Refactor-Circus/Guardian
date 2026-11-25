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
 * Guardian's two-factor enforcement policies (criterion 34): who must use
 * two-factor (everyone, a role's holders, a scope's members), with which
 * methods and how many days of grace. Needs nothing from Roster. A table
 * already in place is left alone; down() drops nothing while it holds rows,
 * unless `guardian.migrations.force_rollback` is on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $policies = Tables::name('two_factor_policies');

        if (Schema::hasTable($policies)) {
            return;
        }

        Schema::create($policies, function (Blueprint $table) use ($policies): void {
            $table->ulid('id')->primary();
            $table->string('scope_type')->nullable();
            $table->string('scope_id', 64)->nullable();
            $table->string('role')->nullable();
            $table->boolean('required')->default(true);
            $table->json('methods')->nullable();
            $table->unsignedInteger('grace_days')->default(0);
            $table->timestamps();

            $table->index(['scope_type', 'scope_id'], $policies.'_scope_index');
        });
    }

    public function down(): void
    {
        $policies = Tables::name('two_factor_policies');

        if (Config::get('guardian.migrations.force_rollback') !== true && Schema::hasTable($policies) && DB::table($policies)->exists()) {
            throw RollbackRefusedException::forTables([$policies]);
        }

        Schema::dropIfExists($policies);
    }
};
