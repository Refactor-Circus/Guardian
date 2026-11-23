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
 * Guardian's two-factor tables (criterion 26): the methods (TOTP, email and
 * SMS, their secret and destination encrypted by the model), the recovery
 * codes and one-time codes (HMACs only) and the passkeys. Needs nothing from
 * Roster. A table already in place is left alone; down() drops nothing while
 * any of them holds rows, unless `guardian.migrations.force_rollback` is on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $methods = Tables::name('two_factor_methods');
        $recoveryCodes = Tables::name('recovery_codes');
        $passkeys = Tables::name('passkeys');
        $oneTimeCodes = Tables::name('one_time_codes');

        if (! Schema::hasTable($methods)) {
            Schema::create($methods, function (Blueprint $table) use ($methods): void {
                $table->ulid('id')->primary();
                UserKey::column($table, 'user_id');
                $table->string('type', 16);
                // Encrypted by the model's casts.
                $table->text('secret')->nullable();
                $table->text('destination')->nullable();
                $table->string('label')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                // The last TOTP time step accepted, so a code is never accepted twice.
                $table->unsignedBigInteger('last_timestep')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'type'], $methods.'_user_index');
            });
        }

        if (! Schema::hasTable($recoveryCodes)) {
            Schema::create($recoveryCodes, function (Blueprint $table) use ($recoveryCodes): void {
                $table->ulid('id')->primary();
                UserKey::column($table, 'user_id');
                // HMAC-SHA256 of the code, keyed by the app key.
                $table->string('code_hash', 64);
                $table->timestamp('used_at')->nullable();

                $table->index('user_id', $recoveryCodes.'_user_index');
            });
        }

        if (! Schema::hasTable($passkeys)) {
            Schema::create($passkeys, function (Blueprint $table) use ($passkeys): void {
                $table->ulid('id')->primary();
                UserKey::column($table, 'user_id');
                // Base64url; at most 512 characters, so the unique index fits MySQL's limit.
                $table->string('credential_id', 512)->unique($passkeys.'_credential_unique');
                $table->text('public_key');
                $table->unsignedBigInteger('sign_count')->default(0);
                $table->json('transports')->nullable();
                $table->string('aaguid', 36)->nullable();
                $table->string('label')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index('user_id', $passkeys.'_user_index');
            });
        }

        if (! Schema::hasTable($oneTimeCodes)) {
            Schema::create($oneTimeCodes, function (Blueprint $table) use ($oneTimeCodes, $methods): void {
                $table->ulid('id')->primary();
                UserKey::column($table, 'user_id');
                $table->foreignUlid('method_id')->constrained($methods, 'id', $oneTimeCodes.'_method_foreign')->cascadeOnDelete();
                // HMAC-SHA256 of the code, keyed by the app key.
                $table->string('code_hash', 64);
                $table->timestamp('expires_at');
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->timestamp('consumed_at')->nullable();

                $table->index(['user_id', 'method_id'], $oneTimeCodes.'_user_index');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            Tables::name('one_time_codes'),
            Tables::name('passkeys'),
            Tables::name('recovery_codes'),
            Tables::name('two_factor_methods'),
        ];

        $holding = array_values(array_filter($tables, fn (string $table): bool => Schema::hasTable($table) && DB::table($table)->exists()));

        if (Config::get('guardian.migrations.force_rollback') !== true && $holding !== []) {
            throw RollbackRefusedException::forTables($holding);
        }

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
