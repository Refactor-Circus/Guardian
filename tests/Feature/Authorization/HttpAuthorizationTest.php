<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use Illuminate\Support\Facades\Route;

// The SSO and SCIM cases of Roster's tests/Authorization/HttpAuthorizationTest.php at
// the pinned SHA, with Guardian's route names.

beforeEach(fn () => authorizationOn());

it('covers every guardian API route', function (): void {
    $names = collect(Route::getRoutes()->getRoutesByName())
        ->keys()
        ->filter(fn (string $name): bool => str_starts_with($name, 'guardian.') && ! str_starts_with($name, 'guardian.sso.') && ! str_starts_with($name, 'guardian.scim.'))
        ->map(fn (string $name): string => substr($name, 9))
        ->sort()
        ->values()
        ->all();

    $covered = collect(apiRoutes())->map(fn (array $route): string => substr($route[1], 9))->sort()->values()->all();

    expect($covered)->toBe($names);
});

it('rejects guests with 401', function (string $method, string $name, Closure $parameters, array $body): void {
    $world = authorizationWorld();

    $this->json($method, route($name, $parameters($world)), $body)->assertUnauthorized();
})->with(apiRoutes());

it('rejects users without the permission with 403', function (string $method, string $name, Closure $parameters, array $body): void {
    $world = authorizationWorld();

    $this->actingAs(user())->json($method, route($name, $parameters($world)), $body)->assertForbidden();
})->with(apiRoutes());

it('lets a super-admin through', function (string $method, string $name, Closure $parameters, array $body): void {
    $world = authorizationWorld();
    $admin = user();
    grant($admin, 'super-admin');

    $status = $this->actingAs($admin)->json($method, route($name, $parameters($world)), $body)->status();

    expect($status)->not->toBeIn([401, 403]);
})->with(apiRoutes());
