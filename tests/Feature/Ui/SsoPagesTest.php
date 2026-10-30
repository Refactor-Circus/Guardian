<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;

it('creates, edits and deletes a connection from the organization page', function (): void {
    $this->actingAs(user());
    organization(attributes: ['name' => 'Acme']);

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.sso']))->assertOk()->assertSee('Microsoft Entra ID');

    $this->post(route('atrium.guardian.sso.store', 'acme'), [
        'name' => 'Okta', 'protocol' => 'oidc', 'issuer' => 'https://idp.test', 'client_id' => 'a', 'client_secret' => 'b', 'jit' => '1', 'enabled' => '1',
    ])->assertRedirect();

    $connection = SsoConnectionModel::query()->sole();

    $this->get(route('atrium.guardian.sso.show', $connection->slug))->assertOk()->assertSee(route('guardian.sso.callback', $connection->slug));

    $this->patch(route('atrium.guardian.sso.update', $connection->slug), ['name' => 'Okta prod', 'client_secret' => '', 'enabled' => '1'])->assertRedirect();

    expect($connection->refresh()->name)->toBe('Okta prod')
        ->and($connection->jit)->toBeFalse()
        ->and($connection->setting('client_secret'))->toBe('b');

    $this->delete(route('atrium.guardian.sso.destroy', $connection->slug))->assertRedirect();
    expect(SsoConnectionModel::query()->count())->toBe(0);
});

it('shows only the connection\'s own protocol settings when editing', function (string $protocol, array $shown, array $hidden): void {
    $this->actingAs(user());
    $connection = SsoConnectionModel::factory()->create([
        'organization_id' => organization(attributes: ['name' => 'Acme'])->id,
        'protocol' => $protocol,
    ]);

    $page = $this->get(route('atrium.guardian.sso.show', $connection->slug))->assertOk();

    foreach ($shown as $field) {
        $page->assertSee('data-sso-field="'.$field.'"', false);
    }

    foreach ($hidden as $field) {
        $page->assertDontSee('data-sso-field="'.$field.'"', false);
    }
})->with([
    'oidc' => ['oidc', ['issuer', 'client_id', 'client_secret'], ['tenant', 'metadata_url', 'certificate']],
    'azure' => ['azure', ['tenant', 'client_id', 'client_secret'], ['issuer', 'entity_id']],
    'saml' => ['saml', ['metadata_url', 'entity_id', 'sso_url', 'certificate'], ['issuer', 'tenant', 'client_id']],
]);
