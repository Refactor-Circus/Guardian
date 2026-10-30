<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Scim\Http\Middleware\AuthenticateScimToken;
use RefactorCircus\Guardian\Domains\Scim\ScimServiceProvider;
use RefactorCircus\Guardian\Tests\TestCase;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;

// Criterion 25: the SCIM token JSON API loads under `guardian.routes` with
// guardian.* names, and the SCIM 2.0 server under `guardian.scim.prefix`,
// named guardian.scim.*, behind `throttle:guardian`, while
// `guardian.scim.enabled`.

const SCIM_API_ROUTES = [
    'guardian.organizations.scim-tokens.index' => ['GET', 'guardian/organizations/{organization}/scim-tokens'],
    'guardian.organizations.scim-tokens.store' => ['POST', 'guardian/organizations/{organization}/scim-tokens'],
    'guardian.scim-tokens.destroy' => ['DELETE', 'guardian/scim-tokens/{token}'],
];

const SCIM_SERVER_ROUTES = [
    'guardian.scim.users.index' => ['GET', 'scim/v2/{organization}/Users'],
    'guardian.scim.users.store' => ['POST', 'scim/v2/{organization}/Users'],
    'guardian.scim.users.show' => ['GET', 'scim/v2/{organization}/Users/{id}'],
    'guardian.scim.users.replace' => ['PUT', 'scim/v2/{organization}/Users/{id}'],
    'guardian.scim.users.patch' => ['PATCH', 'scim/v2/{organization}/Users/{id}'],
    'guardian.scim.users.destroy' => ['DELETE', 'scim/v2/{organization}/Users/{id}'],
    'guardian.scim.groups.index' => ['GET', 'scim/v2/{organization}/Groups'],
    'guardian.scim.groups.store' => ['POST', 'scim/v2/{organization}/Groups'],
    'guardian.scim.groups.show' => ['GET', 'scim/v2/{organization}/Groups/{id}'],
    'guardian.scim.groups.replace' => ['PUT', 'scim/v2/{organization}/Groups/{id}'],
    'guardian.scim.groups.patch' => ['PATCH', 'scim/v2/{organization}/Groups/{id}'],
    'guardian.scim.groups.destroy' => ['DELETE', 'scim/v2/{organization}/Groups/{id}'],
    'guardian.scim.bulk' => ['POST', 'scim/v2/{organization}/Bulk'],
    'guardian.scim.config' => ['GET', 'scim/v2/{organization}/ServiceProviderConfig'],
    'guardian.scim.resource-types' => ['GET', 'scim/v2/{organization}/ResourceTypes'],
    'guardian.scim.schemas' => ['GET', 'scim/v2/{organization}/Schemas'],
];

it('names the SCIM token API routes guardian.* under the guardian prefix and its throttle', function (string $name, array $route): void {
    $registered = Route::getRoutes()->getByName($name);

    expect($registered)->not->toBeNull()
        ->and($registered?->methods())->toContain($route[0])
        ->and($registered?->uri())->toBe($route[1])
        ->and($registered?->gatherMiddleware())->toContain('api', 'throttle:guardian');
})->with(fn (): array => array_map(fn (string $name, array $route): array => [$name, $route], array_keys(SCIM_API_ROUTES), SCIM_API_ROUTES));

it('names the SCIM server routes guardian.scim.* under guardian.scim.prefix, behind throttle:guardian and the token', function (string $name, array $route): void {
    $registered = Route::getRoutes()->getByName($name);

    expect($registered)->not->toBeNull()
        ->and($registered?->methods())->toContain($route[0])
        ->and($registered?->uri())->toBe($route[1])
        ->and($registered?->gatherMiddleware())->toContain('api', 'throttle:guardian', AuthenticateScimToken::class);
})->with(fn (): array => array_map(fn (string $name, array $route): array => [$name, $route], array_keys(SCIM_SERVER_ROUTES), SCIM_SERVER_ROUTES));

it('registers none of the SCIM token API routes while guardian.routes.enabled is off, as shipped', function (): void {
    TestCase::$apiRoutes = false;

    try {
        $this->refreshApplication();
    } finally {
        TestCase::$apiRoutes = true;
    }

    foreach (array_keys(SCIM_API_ROUTES) as $name) {
        expect(Route::has($name))->toBeFalse();
    }

    // The SCIM server does not depend on the API switch.
    expect(Route::has('guardian.scim.users.index'))->toBeTrue();
});

it('serves no SCIM endpoint while guardian.scim.enabled is off', function (): void {
    config()->set('guardian.scim.enabled', false);
    $this->app['router']->setRoutes(new RouteCollection);

    // Boot the SCIM domain again; Roster's registries refuse a taken name.
    $this->app->forgetInstance(Resources::class);
    $this->app->forgetInstance(PermissionCatalogue::class);

    $this->app->register(ScimServiceProvider::class, true);

    expect(Route::has('guardian.scim.users.index'))->toBeFalse()
        ->and(Route::has('guardian.organizations.scim-tokens.index'))->toBeTrue();
});
