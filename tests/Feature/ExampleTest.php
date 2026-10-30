<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Guardian;

it('resolves the singleton', function () {
    expect(app(Guardian::class))->toBeInstanceOf(Guardian::class);
});

it('returns the same instance from the container', function () {
    expect(app(Guardian::class))->toBe(app(Guardian::class));
});

it('merges the package config', function () {
    expect(config('guardian.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('guardian::messages.placeholder'))->toBe('Guardian placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('guardian::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('guardian:placeholder')
        ->expectsOutputToContain('Guardian placeholder command executed.')
        ->assertSuccessful();
});
