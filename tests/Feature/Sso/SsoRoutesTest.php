<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Tests\TestCase;

// Criterion 25: the SSO JSON API loads under `guardian.routes` with guardian.*
// names, and the sign-in routes under `guardian.sso.prefix`.

const SSO_API_ROUTES = [
    'guardian.organizations.sso-connections.index' => ['GET', 'guardian/organizations/{organization}/sso-connections'],
    'guardian.organizations.sso-connections.store' => ['POST', 'guardian/organizations/{organization}/sso-connections'],
    'guardian.sso-connections.show' => ['GET', 'guardian/sso-connections/{connection}'],
    'guardian.sso-connections.update' => ['PATCH', 'guardian/sso-connections/{connection}'],
    'guardian.sso-connections.destroy' => ['DELETE', 'guardian/sso-connections/{connection}'],
    'guardian.users.sso-identities.index' => ['GET', 'guardian/users/{user}/sso-identities'],
    'guardian.sso-identities.destroy' => ['DELETE', 'guardian/sso-identities/{identity}'],
];

const SSO_WEB_ROUTES = [
    'guardian.sso.discover' => 'roster/sso',
    'guardian.sso.start' => 'roster/sso/{connection}',
    'guardian.sso.callback' => 'roster/sso/{connection}/callback',
    'guardian.sso.metadata' => 'roster/sso/{connection}/metadata',
    'guardian.sso.link' => 'roster/sso/{connection}/link',
];

it('names the SSO API routes guardian.* under the guardian prefix and its throttle', function (string $name, array $route): void {
    $registered = Route::getRoutes()->getByName($name);

    expect($registered)->not->toBeNull()
        ->and($registered?->methods())->toContain($route[0])
        ->and($registered?->uri())->toBe($route[1])
        ->and($registered?->gatherMiddleware())->toContain('api', 'throttle:guardian');
})->with(fn (): array => array_map(fn (string $name, array $route): array => [$name, $route], array_keys(SSO_API_ROUTES), SSO_API_ROUTES));

it('names the SSO sign-in routes guardian.sso.* under the kept roster/sso prefix', function (string $name, string $uri): void {
    expect(Route::getRoutes()->getByName($name)?->uri())->toBe($uri);
})->with(fn (): array => array_map(fn (string $name, string $uri): array => [$name, $uri], array_keys(SSO_WEB_ROUTES), SSO_WEB_ROUTES));

it('registers none of the SSO API routes while guardian.routes.enabled is off, as shipped', function (): void {
    TestCase::$apiRoutes = false;

    try {
        $this->refreshApplication();
    } finally {
        TestCase::$apiRoutes = true;
    }

    expect(config('guardian.routes.enabled'))->toBeFalse();

    foreach (array_keys(SSO_API_ROUTES) as $name) {
        expect(Route::has($name))->toBeFalse();
    }

    // The sign-in routes do not depend on the API switch.
    expect(Route::has('guardian.sso.start'))->toBeTrue();
});
