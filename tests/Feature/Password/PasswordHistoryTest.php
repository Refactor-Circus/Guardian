<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\Password\Models\PasswordHistoryModel;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Exceptions\RollbackRefusedException;

// Criterion 41: password history.

beforeEach(fn () => fakeBreaches());

it('creates the history table', function (): void {
    expect(Schema::getColumnListing('guardian_password_histories'))->toEqualCanonicalizing(['id', 'user_id', 'password_hash', 'created_at']);

    config()->set('guardian.tables.password_histories', 'acme_histories');
    (require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000008_create_guardian_password_tables.php')->up();

    expect(Schema::hasTable('acme_histories'))->toBeTrue()
        ->and((new PasswordHistoryModel)->getTable())->toBe('acme_histories');
});

it('refuses to roll back a table holding rows, unless forced', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000008_create_guardian_password_tables.php';
    user();

    expect(fn () => $migration->down())->toThrow(RollbackRefusedException::class);

    config()->set('guardian.migrations.force_rollback', true);
    $migration->down();

    expect(Schema::hasTable('guardian_password_histories'))->toBeFalse();
});

it('records the hash when a user is created and when the password changes, with password.changed', function (): void {
    $ada = user();

    expect(PasswordHistoryModel::query()->where('user_id', $ada->getKey())->count())->toBe(1)
        ->and(SecurityEventModel::query()->where('type', 'password.changed')->count())->toBe(0);

    $this->travel(1)->seconds();
    $ada->update(['password' => 'a brand new passphrase']);

    $latest = PasswordHistoryModel::query()->where('user_id', $ada->getKey())->latest('created_at')->latest('id')->first();
    expect(PasswordHistoryModel::query()->where('user_id', $ada->getKey())->count())->toBe(2)
        ->and(Hash::check('a brand new passphrase', (string) $latest?->password_hash))->toBeTrue()
        ->and($latest?->toArray())->not->toHaveKey('password_hash');

    $event = SecurityEventModel::query()->where('type', 'password.changed')->sole();
    expect((string) $event->user_id)->toBe((string) $ada->getKey())
        ->and((string) json_encode($event->toArray()))->not->toContain('passphrase')->not->toContain((string) $latest?->password_hash);

    $ada->update(['name' => 'Ada L']);
    expect(PasswordHistoryModel::query()->where('user_id', $ada->getKey())->count())->toBe(2);
});

it('keeps only the last `history` hashes', function (): void {
    config()->set('guardian.passwords.history', 3);
    $ada = user();

    foreach (range(1, 5) as $i) {
        $this->travel(1)->minutes();
        $ada->update(['password' => "passphrase number {$i}"]);
    }

    expect(PasswordHistoryModel::query()->where('user_id', $ada->getKey())->count())->toBe(3);
});

it('refuses a recently used password, and allows it once it falls out of the history', function (): void {
    config()->set('guardian.passwords.history', 2);
    $ada = user(['password' => 'the first passphrase']);

    expect(passwordErrors('the first passphrase', $ada))->toBe([__('guardian::guardian.password_recently_used')])
        ->and(passwordErrors('never used before ok', $ada))->toBe([]);

    $this->travel(1)->minutes();
    $ada->update(['password' => 'the second passphrase']);
    $this->travel(1)->minutes();
    $ada->update(['password' => 'the third passphrase']);

    expect(passwordErrors('the second passphrase', $ada->fresh()))->not->toBe([])
        ->and(passwordErrors('the first passphrase', $ada->fresh()))->toBe([]);
});

it('watches the configured column', function (): void {
    config()->set('guardian.passwords.column', 'name');
    $ada = user();
    $before = PasswordHistoryModel::query()->count();

    $ada->update(['password' => 'changed but not watched']);
    expect(PasswordHistoryModel::query()->count())->toBe($before);

    $ada->update(['name' => 'Watched']);
    expect(PasswordHistoryModel::query()->count())->toBe($before + 1);
});
