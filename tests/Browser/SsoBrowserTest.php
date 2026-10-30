<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

beforeEach(fn () => signInAsSuperAdmin());

it('creates an Entra ID connection and shows its callback URL', function (): void {
    organization(attributes: ['name' => 'Acme']);

    visit('/atrium/roster/organizations/acme?tab=guardian.sso')
        ->assertVisible('[data-sso-field="issuer"]')
        ->assertMissing('[data-sso-field="tenant"]')
        ->assertMissing('[data-sso-field="metadata_url"]')
        ->select('protocol', 'saml')
        ->assertVisible('[data-sso-field="metadata_url"]')
        ->assertMissing('[data-sso-field="client_id"]')
        ->type('name', 'Entra')
        ->select('protocol', 'azure')
        ->assertMissing('[data-sso-field="issuer"]')
        ->type('tenant', 'acme.onmicrosoft.com')
        ->type('client_id', 'app')
        ->type('client_secret', 'secret')
        ->click('@create-sso')
        ->assertSee('SSO connection created.')
        ->assertSee('/roster/sso/acme-entra/callback');
});
