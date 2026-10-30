<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;

it('issues, shows once and revokes tokens from the organization page', function (): void {
    $this->actingAs(user());
    organization(attributes: ['name' => 'Acme']);

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.scim']))->assertOk()->assertSee(url('scim/v2/acme'));

    $this->post(route('atrium.guardian.scim-tokens.store', 'acme'), ['name' => 'Okta'])->assertRedirect();

    $page = $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.scim']))->assertOk()->assertSee('scim_');
    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.scim']))->assertOk()->assertDontSee('data-testid="scim-token"', false);

    $this->delete(route('atrium.guardian.scim-tokens.revoke', ScimTokenModel::query()->sole()->id))->assertRedirect();

    expect(ScimTokenModel::query()->sole()->revoked_at)->not->toBeNull();
});
