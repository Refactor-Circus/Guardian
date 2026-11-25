<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Exceptions\RollbackRefusedException;

// Criterion 35: the device and session tables.

it('creates the device and session tables with their columns', function (string $table, array $columns): void {
    expect(Schema::hasTable($table))->toBeTrue()
        ->and(Schema::getColumnListing($table))->toEqualCanonicalizing($columns);
})->with([
    'devices' => ['guardian_devices', ['id', 'user_id', 'fingerprint_hash', 'name', 'first_seen_at', 'last_seen_at', 'last_ip']],
    'sessions' => ['guardian_sessions', ['id', 'user_id', 'device_id', 'key_hash', 'ip', 'user_agent', 'last_active_at', 'revoked_at', 'created_at', 'updated_at']],
]);

it('makes a device unique per user and fingerprint, and a session per key hash', function (): void {
    $device = collect(Schema::getIndexes('guardian_devices'))->first(fn (array $index): bool => $index['columns'] === ['user_id', 'fingerprint_hash']);
    $session = collect(Schema::getIndexes('guardian_sessions'))->first(fn (array $index): bool => $index['columns'] === ['key_hash']);

    expect($device['unique'] ?? false)->toBeTrue()
        ->and($session['unique'] ?? false)->toBeTrue();
});

it('names both tables from guardian.tables', function (): void {
    config()->set('guardian.tables.devices', 'acme_devices');
    config()->set('guardian.tables.sessions', 'acme_sessions');

    $migration = require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000007_create_guardian_session_tables.php';
    $migration->up();

    expect(Schema::hasTable('acme_devices'))->toBeTrue()
        ->and(Schema::hasTable('acme_sessions'))->toBeTrue()
        ->and((new DeviceModel)->getTable())->toBe('acme_devices')
        ->and((new SessionModel)->getTable())->toBe('acme_sessions');
});

it('never serializes the key or fingerprint hashes', function (): void {
    $device = DeviceModel::query()->create(['user_id' => user()->getKey(), 'fingerprint_hash' => 'f', 'first_seen_at' => now(), 'last_seen_at' => now()]);
    $session = SessionModel::query()->create(['user_id' => $device->user_id, 'device_id' => $device->id, 'key_hash' => 'k', 'last_active_at' => now()]);

    expect($device->toArray())->not->toHaveKey('fingerprint_hash')
        ->and($session->toArray())->not->toHaveKey('key_hash');
});

it('refuses to roll back tables holding rows, unless forced', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000007_create_guardian_session_tables.php';
    DeviceModel::query()->create(['user_id' => user()->getKey(), 'fingerprint_hash' => 'f', 'first_seen_at' => now(), 'last_seen_at' => now()]);

    expect(fn () => $migration->down())->toThrow(RollbackRefusedException::class)
        ->and(Schema::hasTable('guardian_devices'))->toBeTrue();

    config()->set('guardian.migrations.force_rollback', true);
    $migration->down();

    expect(Schema::hasTable('guardian_devices'))->toBeFalse()
        ->and(Schema::hasTable('guardian_sessions'))->toBeFalse();
});
