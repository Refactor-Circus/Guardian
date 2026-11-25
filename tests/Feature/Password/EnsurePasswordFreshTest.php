<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Password\Http\Middleware\EnsurePasswordFresh;
use RefactorCircus\Guardian\Domains\Password\Models\PasswordHistoryModel;

// Criterion 42: rotation.

beforeEach(function (): void {
    Route::middleware(['auth', 'guardian.password-fresh'])->get('test-fresh', fn (): string => 'fresh');
});

it('registers the guardian.password-fresh alias', function (): void {
    expect(Route::getMiddleware()['guardian.password-fresh'] ?? null)->toBe(EnsurePasswordFresh::class);
});

it('passes everyone while max_age_days is null', function (): void {
    $ada = user();
    $this->travel(5)->years();

    $this->actingAs($ada)->get('test-fresh')->assertOk();
});

it('refuses a password older than max_age_days with 403 password_expired', function (): void {
    config()->set('guardian.passwords.max_age_days', 90);
    $ada = user();

    $this->travel(89)->days();
    $this->actingAs($ada)->getJson('test-fresh')->assertOk();

    $this->travel(2)->days();
    $this->actingAs($ada)->getJson('test-fresh')->assertForbidden()->assertJson(['code' => 'password_expired']);

    $ada->update(['password' => 'a fresh passphrase']);
    $this->actingAs($ada)->getJson('test-fresh')->assertOk();
});

it('gives a user with no history a baseline and does not refuse them', function (): void {
    config()->set('guardian.passwords.max_age_days', 90);
    $ada = user();
    PasswordHistoryModel::query()->delete();
    $this->travel(1)->years();

    $this->actingAs($ada)->getJson('test-fresh')->assertOk();

    expect(PasswordHistoryModel::query()->where('user_id', $ada->getKey())->sole()->created_at->toDateTimeString())->toBe(now()->toDateTimeString());

    $this->travel(91)->days();
    $this->actingAs($ada)->getJson('test-fresh')->assertForbidden();
});

it('passes guests', function (): void {
    config()->set('guardian.passwords.max_age_days', 1);
    Route::middleware('guardian.password-fresh')->get('test-open', fn (): string => 'open');

    $this->get('test-open')->assertOk();
});
