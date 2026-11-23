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
 * Guardian's devices and sessions (criterion 35): the browsers a user signs
 * in from (by an HMAC of the user agent) and each signed-in session, found
 * by the hash of a random key kept in the session, never by the session id.
 * Needs nothing from Roster. A table already in place is left alone; down()
 * drops nothing while either holds rows, unless
 * `guardian.migrations.force_rollback` is on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $devices = Tables::name('devices');
        $sessions = Tables::name('sessions');

        if (! Schema::hasTable($devices)) {
            Schema::create($devices, function (Blueprint $table) use ($devices): void {
                $table->ulid('id')->primary();
                UserKey::column($table, 'user_id');
                $table->string('fingerprint_hash', 64);
                $table->string('name')->nullable();
                $table->timestamp('first_seen_at');
                $table->timestamp('last_seen_at');
                $table->string('last_ip', 45)->nullable();

                $table->unique(['user_id', 'fingerprint_hash'], $devices.'_user_fingerprint_unique');
            });
        }

        if (! Schema::hasTable($sessions)) {
            Schema::create($sessions, function (Blueprint $table) use ($sessions): void {
                $table->ulid('id')->primary();
                UserKey::column($table, 'user_id');
                $table->ulid('device_id')->nullable();
                $table->string('key_hash', 64);
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->timestamp('last_active_at');
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->unique('key_hash', $sessions.'_key_hash_unique');
                $table->index('user_id', $sessions.'_user_index');
                $table->index('device_id', $sessions.'_device_index');
            });
        }
    }

    public function down(): void
    {
        $tables = [Tables::name('sessions'), Tables::name('devices')];

        $holding = array_values(array_filter($tables, fn (string $table): bool => Schema::hasTable($table) && DB::table($table)->exists()));

        if (Config::get('guardian.migrations.force_rollback') !== true && $holding !== []) {
            throw RollbackRefusedException::forTables($holding);
        }

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
