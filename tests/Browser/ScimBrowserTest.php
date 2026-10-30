<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

beforeEach(fn () => signInAsSuperAdmin());

it('issues a SCIM token and shows it once', function (): void {
    organization(attributes: ['name' => 'Acme']);

    visit('/atrium/roster/organizations/acme?tab=guardian.scim')
        ->assertSee('/scim/v2/acme')
        ->type('#scim-token-name', 'Okta')
        ->click('@create-scim-token')
        ->assertSee('Copy this token now')
        ->assertPresent('@scim-token');
});
