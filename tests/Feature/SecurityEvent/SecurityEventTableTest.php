<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Support\Tables;

// Criterion 22: the security event table and its pruning.

it('creates the security events table under its configured name, with its indexes', function (): void {
    $table = Tables::name('security_events');

    expect($table)->toBe('guardian_security_events')
        ->and(Schema::getColumnListing($table))->toEqualCanonicalizing(['id', 'type', 'user_id', 'identifier_hash', 'ip', 'user_agent', 'context', 'occurred_at']);

    $indexes = collect(Schema::getIndexes($table))->map(fn (array $index): array => $index['columns'])->values()->all();

    expect($indexes)->toContain(['user_id', 'occurred_at'], ['type', 'occurred_at']);
});

it('stores the type as an enum, the context as an array and the time as a date', function (): void {
    $event = recordSecurityEvent(SecurityEventType::Logout, user(), context: ['guard' => 'web'])->refresh();

    expect($event->type)->toBe(SecurityEventType::Logout)
        ->and($event->context)->toBe(['guard' => 'web'])
        ->and($event->occurred_at->isToday())->toBeTrue()
        ->and(strlen($event->id))->toBe(26);
});

it('prunes events older than guardian.security_log.retention_days', function (): void {
    $old = recordSecurityEvent(SecurityEventType::Logout);
    $old->forceFill(['occurred_at' => now()->subDays(91)])->save();
    $recent = recordSecurityEvent(SecurityEventType::Logout);
    $recent->forceFill(['occurred_at' => now()->subDays(89)])->save();

    $this->artisan('model:prune', ['--model' => [SecurityEventModel::class]])->assertSuccessful();

    expect(SecurityEventModel::query()->pluck('id')->all())->toBe([$recent->id]);

    config(['guardian.security_log.retention_days' => null]);
    $recent->forceFill(['occurred_at' => now()->subYears(5)])->save();

    $this->artisan('model:prune', ['--model' => [SecurityEventModel::class]])->assertSuccessful();

    expect(SecurityEventModel::query()->count())->toBe(1);
});
