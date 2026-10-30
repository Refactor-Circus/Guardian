<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Atrium\Atrium;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoIdentitiesListingActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use Workbench\App\Models\User;

// Criterion 18: the guardian.sso-identities section in the aside of Roster's
// user screen, shown only when the user has an SSO identity.

function identitiesSection(): SectionDefinition
{
    return app(Atrium::class)->screens()->sections('roster.user', 'aside')[0];
}

/**
 * Link a user to a new connection of a new organization.
 */
function linkIdentity(User $user, string $organization, string $subject): SsoIdentityModel
{
    $connection = SsoConnectionModel::factory()->create([
        'organization_id' => organization(attributes: ['name' => $organization])->id,
        'name' => $organization.' IdP',
    ]);

    return SsoIdentityModel::query()->create(['connection_id' => $connection->id, 'user_id' => $user->getKey(), 'subject' => $subject, 'email' => $user->email]);
}

it('defines the section with its label and view, in the aside', function (): void {
    $section = identitiesSection();

    expect($section->key)->toBe('guardian.sso-identities')
        ->and($section->label)->toBe(__('guardian::guardian.sso_identities'))
        ->and($section->view)->toBe('guardian::atrium.user-sso-identities');
});

it('authorizes with one exists query', function (bool $linked): void {
    $user = user();

    if ($linked) {
        linkIdentity($user, 'Acme', 'sub-1');
    }

    $screen = Screen::standard('roster.user', Screen::DETAIL)->record($user);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $allowed = identitiesSection()->allows(request(), $user, $screen, null);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($allowed)->toBe($linked)
        ->and($queries)->toHaveCount(1)
        ->and(strtolower($queries[0]['query']))->toContain('exists');
})->with(['linked' => true, 'not linked' => false]);

it('is hidden on a record that is not a user', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $screen = Screen::standard('roster.organization', Screen::DETAIL)->record($acme);

    expect(identitiesSection()->allows(request(), $acme, $screen, null))->toBeFalse()
        ->and(identitiesSection()->allows(request(), null, $screen, null))->toBeFalse();
});

it('resolves the user\'s identities, 50 a page', function (): void {
    $user = user();
    linkIdentity($user, 'Acme', 'sub-1');

    $data = identitiesSection()->resolveData($user, Screen::standard('roster.user', Screen::DETAIL)->record($user));

    expect(array_keys($data))->toBe(['identities'])
        ->and($data['identities']->perPage())->toBe(50)
        ->and($data['identities']->total())->toBe(1);
});

it('shows both of a user\'s identities, each with an unlink form', function (): void {
    $this->actingAs(user());
    $user = user(['email' => 'ada@example.com']);
    $first = linkIdentity($user, 'Acme', 'sub-1');
    $second = linkIdentity($user, 'Globex', 'sub-2');

    $this->get(route('atrium.roster.users.show', $user->getRouteKey()))
        ->assertOk()
        ->assertSee('data-testid="section-guardian.sso-identities"', false)
        ->assertSee('Acme IdP')
        ->assertSee('Globex IdP')
        ->assertSee(route('atrium.guardian.sso-identities.destroy', $first->id), false)
        ->assertSee(route('atrium.guardian.sso-identities.destroy', $second->id), false);
});

it('shows no section, and runs no resolver, for a user without identities', function (): void {
    $this->actingAs(user());
    $user = user();
    $listed = 0;
    Event::listen(SsoIdentitiesListingActionEvent::class, function () use (&$listed): void {
        $listed++;
    });

    $this->get(route('atrium.roster.users.show', $user->getRouteKey()))
        ->assertOk()
        ->assertDontSee('data-testid="section-guardian.sso-identities"', false);

    expect($listed)->toBe(0);
});

it('unlinks an identity and goes back to the page it came from', function (): void {
    $this->actingAs(user());
    $user = user();
    $identity = linkIdentity($user, 'Acme', 'sub-1');
    $page = route('atrium.roster.users.show', $user->getRouteKey());

    $this->from($page)
        ->delete(route('atrium.guardian.sso-identities.destroy', $identity->id))
        ->assertRedirect($page);

    expect(SsoIdentityModel::query()->count())->toBe(0);
});

it('refuses to unlink another user\'s identity without guardian.sso.manage', function (): void {
    config(['roster.authorization' => true]);
    $user = user();
    $identity = linkIdentity($user, 'Acme', 'sub-1');

    $this->actingAs(user())
        ->delete(route('atrium.guardian.sso-identities.destroy', $identity->id))
        ->assertForbidden();

    expect(SsoIdentityModel::query()->count())->toBe(1);
});
