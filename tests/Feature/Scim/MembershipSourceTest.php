<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Scim\ScimServiceProvider;
use RefactorCircus\Roster\Domains\Organization\Resources\MemberResource;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use Workbench\App\Models\User;

require_once __DIR__.'/helpers.php';

// Criterion 11: the SCIM domain registers the `scim` membership source
// ("SCIM") once every provider has booted, unless something already has, and
// the members SCIM provisions join with it.

/**
 * Boot the SCIM domain again on a fresh source registry. Roster's resource
 * and permission registries refuse a taken name, so they start fresh too.
 * Booted callbacks run at once on a booted application.
 */
function bootScimDomainWith(MembershipSources $sources): void
{
    app()->instance(MembershipSources::class, $sources);
    app()->forgetInstance(Resources::class);
    app()->forgetInstance(PermissionCatalogue::class);

    (new ScimServiceProvider(app()))->boot();
}

it('stores scim on a membership SCIM provisions and lists it as SCIM', function (): void {
    [$acme, $this->scimToken] = scimOrg();

    scim('POST', '/Users', oktaUser())->assertCreated();

    $membership = $acme->membershipFor(User::query()->where('email', 'ada@acme.test')->sole());

    expect($membership?->source)->toBe('scim')
        ->and(app(MembershipSources::class)->label('scim'))->toBe('SCIM')
        ->and((new MemberResource($membership))->resolve()['source'])->toBe('SCIM');
});

it('registers the scim source itself when nothing else has', function (): void {
    $sources = new MembershipSources;

    expect($sources->has('scim'))->toBeFalse();

    bootScimDomainWith($sources);

    expect($sources->has('scim'))->toBeTrue()
        ->and($sources->label('scim'))->toBe('SCIM');
});

it('boots without throwing when scim is registered before its booted callback', function (): void {
    $sources = new MembershipSources;
    $sources->register('scim', 'SCIM (Roster)');

    bootScimDomainWith($sources);

    expect($sources->label('scim'))->toBe('SCIM (Roster)');
});
