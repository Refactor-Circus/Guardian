<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\TwoFactor\Enums\TwoFactorMethodType;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Exceptions\RollbackRefusedException;

// Criterion 26: the two-factor tables.

it('creates the four two-factor tables with their columns', function (string $table, array $columns): void {
    expect(Schema::hasTable($table))->toBeTrue()
        ->and(Schema::getColumnListing($table))->toEqualCanonicalizing($columns);
})->with([
    'methods' => ['guardian_two_factor_methods', ['id', 'user_id', 'type', 'secret', 'destination', 'label', 'confirmed_at', 'last_used_at', 'last_timestep', 'created_at', 'updated_at']],
    'recovery codes' => ['guardian_recovery_codes', ['id', 'user_id', 'code_hash', 'used_at']],
    'passkeys' => ['guardian_passkeys', ['id', 'user_id', 'credential_id', 'public_key', 'sign_count', 'transports', 'aaguid', 'label', 'last_used_at', 'created_at', 'updated_at']],
    'one-time codes' => ['guardian_one_time_codes', ['id', 'user_id', 'method_id', 'code_hash', 'expires_at', 'attempts', 'consumed_at']],
]);

it('makes passkey credential ids unique', function (): void {
    $unique = collect(Schema::getIndexes('guardian_passkeys'))->first(fn (array $index): bool => $index['columns'] === ['credential_id']);

    expect($unique['unique'] ?? false)->toBeTrue();
});

it('names every table from guardian.tables', function (): void {
    config()->set('guardian.tables.two_factor_methods', 'acme_factors');
    config()->set('guardian.tables.recovery_codes', 'acme_recovery');
    config()->set('guardian.tables.passkeys', 'acme_passkeys');
    config()->set('guardian.tables.one_time_codes', 'acme_codes');

    $migration = require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000005_create_guardian_two_factor_tables.php';
    $migration->up();

    expect(Schema::hasTable('acme_factors'))->toBeTrue()
        ->and(Schema::hasTable('acme_recovery'))->toBeTrue()
        ->and(Schema::hasTable('acme_passkeys'))->toBeTrue()
        ->and(Schema::hasTable('acme_codes'))->toBeTrue()
        ->and((new TwoFactorMethodModel)->getTable())->toBe('acme_factors');
});

it('encrypts the secret and destination at rest and never serializes them', function (): void {
    $method = TwoFactorMethodModel::query()->create([
        'user_id' => user()->getKey(),
        'type' => TwoFactorMethodType::Email,
        'secret' => 'JBSWY3DPEHPK3PXP',
        'destination' => 'ada@example.com',
    ]);

    $row = (array) DB::table('guardian_two_factor_methods')->where('id', $method->id)->first();

    expect($row['secret'])->not->toContain('JBSWY3DPEHPK3PXP')
        ->and($row['destination'])->not->toContain('ada@example.com')
        ->and($method->fresh()?->secret)->toBe('JBSWY3DPEHPK3PXP')
        ->and($method->toArray())->not->toHaveKeys(['secret', 'destination'])
        ->and((string) json_encode($method))->not->toContain('ada@example.com');
});

it('refuses to roll back while a table holds rows, unless forced', function (): void {
    TwoFactorMethodModel::query()->create(['user_id' => user()->getKey(), 'type' => TwoFactorMethodType::Totp, 'secret' => 'JBSWY3DPEHPK3PXP']);

    $migration = require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000005_create_guardian_two_factor_tables.php';

    expect(fn () => $migration->down())->toThrow(RollbackRefusedException::class);

    config()->set('guardian.migrations.force_rollback', true);
    $migration->down();

    expect(Schema::hasTable('guardian_two_factor_methods'))->toBeFalse();
});
