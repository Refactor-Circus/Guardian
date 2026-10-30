<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Authorization/fixtures.php';

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Atrium\Atrium;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Guardian\Domains\Scim\Events\ScimTokensListingActionEvent;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionsListingActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use Workbench\App\Models\User;

// Criterion 17: the guardian.sso and guardian.scim tabs on Roster's
// organization screen, answered by Roster's own access closure.

/**
 * The tab Guardian registered on the organization screen.
 */
function organizationTab(string $key): TabDefinition
{
    foreach (app(Atrium::class)->screens()->tabs('roster.organization') as $tab) {
        if ($tab->key === $key) {
            return $tab;
        }
    }

    throw new RuntimeException("No tab [{$key}] on roster.organization.");
}

/**
 * A member of Acme who holds no Guardian permission.
 */
function plainMember(OrganizationModel $organization): User
{
    $member = user();
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $member->getRouteKey()));

    return $member;
}

it('defines the SSO tab with its label, icon, view and permission', function (): void {
    $tab = organizationTab('guardian.sso');

    expect($tab->label)->toBe(__('guardian::guardian.sso'))
        ->and($tab->icon)->toBe('arrow-right-end-on-rectangle')
        ->and($tab->view)->toBe('guardian::atrium.organization-sso')
        ->and($tab->permission)->toBe('guardian.sso.view');
});

it('defines the SCIM tab with its label, icon, view and permission', function (): void {
    $tab = organizationTab('guardian.scim');

    expect($tab->label)->toBe(__('guardian::guardian.scim'))
        ->and($tab->icon)->toBe('server-stack')
        ->and($tab->view)->toBe('guardian::atrium.organization-scim')
        ->and($tab->permission)->toBe('guardian.scim.manage');
});

it('hides a tab whose permission the host access closure denies', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $denying = Screen::standard('roster.organization', Screen::DETAIL)->record($acme)->access(fn (string $permission, Request $request, ?Model $record): bool => false);
    $granting = Screen::standard('roster.organization', Screen::DETAIL)->record($acme)->access(fn (string $permission, Request $request, ?Model $record): bool => true);

    expect(organizationTab('guardian.sso')->allows(request(), $acme, $denying, $denying->permits(...)))->toBeFalse()
        ->and(organizationTab('guardian.sso')->allows(request(), $acme, $granting, $granting->permits(...)))->toBeTrue();
});

it('resolves the organization\'s connections, 25 a page, and the organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    SsoConnectionModel::factory()->count(2)->create(['organization_id' => $acme->id]);
    SsoConnectionModel::factory()->create(['organization_id' => organization(attributes: ['name' => 'Globex'])->id]);

    $screen = Screen::standard('roster.organization', Screen::DETAIL)->record($acme);
    $data = organizationTab('guardian.sso')->resolveData($acme, $screen);

    expect(array_keys($data))->toBe(['connections', 'organization'])
        ->and($data['connections']->perPage())->toBe(25)
        ->and($data['connections']->total())->toBe(2)
        ->and($data['organization'])->toBe($acme);
});

it('resolves the organization\'s tokens, the SCIM base URL and the organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    ScimTokenModel::query()->create(['organization_id' => $acme->id, 'name' => 'Okta', 'token_hash' => hash('sha256', 'x')]);

    $screen = Screen::standard('roster.organization', Screen::DETAIL)->record($acme);
    $data = organizationTab('guardian.scim')->resolveData($acme, $screen);

    expect($data)->toHaveKeys(['tokens', 'baseUrl', 'organization'])
        ->and($data['tokens']->total())->toBe(1)
        ->and($data['baseUrl'])->toBe(url('scim/v2/acme'))
        ->and($data['organization'])->toBe($acme);
});

it('renders the connection list and the create form for an admin', function (): void {
    authorizationOn();
    $admin = user();
    $acme = organization(attributes: ['name' => 'Acme']);
    grant($admin, 'admin', $acme);
    SsoConnectionModel::factory()->create(['organization_id' => $acme->id, 'name' => 'Okta']);

    $this->actingAs($admin)
        ->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.sso']))
        ->assertOk()
        ->assertSee('data-testid="tab-guardian.sso"', false)
        ->assertSee('data-testid="sso-connection"', false)
        ->assertSee('Okta')
        ->assertSee('data-testid="create-sso"', false)
        ->assertSee(route('atrium.guardian.sso.store', 'acme'), false);
});

it('refuses a member without guardian.sso.view, and shows them no SSO tab', function (): void {
    authorizationOn();
    $acme = organization(attributes: ['name' => 'Acme']);
    $member = plainMember($acme);

    $this->actingAs($member)
        ->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.sso']))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('atrium.roster.organizations.show', 'acme'))
        ->assertOk()
        ->assertDontSee('data-testid="tab-guardian.sso"', false);
});

it('renders the token list, the base URL card and the create form for an admin', function (): void {
    authorizationOn();
    $admin = user();
    $acme = organization(attributes: ['name' => 'Acme']);
    grant($admin, 'admin', $acme);
    ScimTokenModel::query()->create(['organization_id' => $acme->id, 'name' => 'Okta', 'token_hash' => hash('sha256', 'x')]);

    $this->actingAs($admin)
        ->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.scim']))
        ->assertOk()
        ->assertSee('data-testid="tab-guardian.scim"', false)
        ->assertSee('data-testid="scim-base-url"', false)
        ->assertSee(url('scim/v2/acme'))
        ->assertSee('data-testid="scim-token-row"', false)
        ->assertSee('data-testid="create-scim-token"', false);
});

it('refuses a member without guardian.scim.manage, and shows them no SCIM tab', function (): void {
    authorizationOn();
    $acme = organization(attributes: ['name' => 'Acme']);
    $member = plainMember($acme);

    $this->actingAs($member)
        ->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.scim']))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('atrium.roster.organizations.show', 'acme'))
        ->assertOk()
        ->assertDontSee('data-testid="tab-guardian.scim"', false);
});

it('shows a new token once, in the alert', function (): void {
    $this->actingAs(user());
    organization(attributes: ['name' => 'Acme']);

    $this->post(route('atrium.guardian.scim-tokens.store', 'acme'), ['name' => 'Okta'])
        ->assertRedirect(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.scim']));

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.scim']))
        ->assertSee('data-testid="scim-token"', false)
        ->assertSee(__('guardian::guardian.scim_token_created'));

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.scim']))
        ->assertDontSee('data-testid="scim-token"', false);
});

it('runs neither resolver while another tab is open', function (): void {
    $this->actingAs(user());
    organization(attributes: ['name' => 'Acme']);
    $listed = 0;
    Event::listen(SsoConnectionsListingActionEvent::class, function () use (&$listed): void {
        $listed++;
    });
    Event::listen(ScimTokensListingActionEvent::class, function () use (&$listed): void {
        $listed++;
    });

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'members']))
        ->assertOk()
        ->assertSee('data-testid="tab-guardian.sso"', false)
        ->assertSee('data-testid="tab-guardian.scim"', false);

    expect($listed)->toBe(0);

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.scim']))->assertOk();

    expect($listed)->toBeGreaterThan(0);
});
