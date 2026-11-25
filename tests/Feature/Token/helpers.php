<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use RefactorCircus\Guardian\Tests\Fixtures\FakeTokenDriver;
use RefactorCircus\Guardian\Tests\Fixtures\Users\PassportUser;
use RefactorCircus\Guardian\Tests\Fixtures\Users\SanctumUser;

/**
 * Configure a fake token driver by class name, as a host binds its own.
 */
function fakeTokenDriver(): FakeTokenDriver
{
    $driver = new FakeTokenDriver;
    app()->instance(FakeTokenDriver::class, $driver);
    config()->set('guardian.tokens.driver', FakeTokenDriver::class);

    return $driver;
}

/**
 * Sanctum installed and selected, with its table; the user model has its
 * tokens.
 */
function useSanctum(): void
{
    app()->register(SanctumServiceProvider::class);
    Artisan::call('migrate', ['--path' => dirname(__DIR__, 3).'/vendor/laravel/sanctum/database/migrations', '--realpath' => true]);

    config()->set('guardian.tokens.driver', 'sanctum');
    config()->set('guardian.users.model', SanctumUser::class);
}

function sanctumUser(): SanctumUser
{
    return SanctumUser::query()->findOrFail(user()->getKey());
}

/**
 * Passport installed and selected: its tables, signing keys, the `api`
 * guard and a personal access client.
 */
function usePassport(): void
{
    static $keys = null;

    if ($keys === null) {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $private);
        $keys = ['private' => $private, 'public' => openssl_pkey_get_details($key)['key']];
    }

    config()->set('passport.private_key', $keys['private']);
    config()->set('passport.public_key', $keys['public']);
    config()->set('auth.guards.api', ['driver' => 'passport', 'provider' => 'users']);

    app()->register(PassportServiceProvider::class);
    // Abilities are Passport scopes, which the host defines.
    Passport::tokensCan(['read' => 'Read', 'write' => 'Write']);
    Artisan::call('migrate', ['--path' => dirname(__DIR__, 3).'/vendor/laravel/passport/database/migrations', '--realpath' => true]);
    app(ClientRepository::class)->createPersonalAccessGrantClient('Guardian tests', 'users');

    config()->set('guardian.tokens.driver', 'passport');
    config()->set('guardian.users.model', PassportUser::class);
}

function passportUser(): PassportUser
{
    return PassportUser::query()->findOrFail(user()->getKey());
}
