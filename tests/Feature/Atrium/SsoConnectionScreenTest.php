<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Authorization/fixtures.php';

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Atrium\Atrium;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionShowingActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;

// Criterion 19: Guardian's own SSO connection page, the screen
// `guardian.sso-connection`, with its routes, slots and back link.

const SSO_CONNECTION_SLOTS = ['header.actions', 'before', 'after', 'aside'];

function acmeConnection(): SsoConnectionModel
{
    return SsoConnectionModel::factory()->create([
        'organization_id' => organization(attributes: ['name' => 'Acme'])->id,
        'slug' => 'acme-okta',
        'name' => 'Okta',
    ]);
}

/**
 * Whether the page has an element with test id `$inner` inside one with
 * test id `$outer`.
 */
function nestedTestId(TestResponse $response, string $outer, string $inner): bool
{
    $document = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML((string) $response->getContent());
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $nodes = (new DOMXPath($document))->query(sprintf('//*[@data-testid="%s"]//*[@data-testid="%s"]', $outer, $inner));

    return $nodes !== false && $nodes->length > 0;
}

/**
 * A public probe tab and a section in every slot of the connection screen.
 */
function registerProbes(): void
{
    View::addNamespace('guardian-probe', __DIR__.'/views');
    $screens = app(Atrium::class)->screens();

    $screens->tab('guardian.sso-connection', TabDefinition::make('probe.tab')->label('Probe')->view('guardian-probe::probe')->public()->resolve(fn (): array => ['probe' => 'tab']));

    foreach (SSO_CONNECTION_SLOTS as $slot) {
        $screens->section('guardian.sso-connection', $slot, SectionDefinition::make('probe.'.$slot)->view('guardian-probe::probe')->public()->resolve(fn (): array => ['probe' => $slot]));
    }
}

it('registers the seven routes under Atrium\'s name prefix', function (string $method, string $name, string $uri): void {
    $route = Route::getRoutes()->getByName($name);

    expect($route)->not->toBeNull()
        ->and($route?->methods())->toContain($method)
        ->and($route?->uri())->toBe('atrium/'.$uri);
})->with([
    ['POST', 'atrium.guardian.sso.store', 'guardian/organizations/{organization}/sso'],
    ['GET', 'atrium.guardian.sso.show', 'guardian/sso/{connection}'],
    ['PATCH', 'atrium.guardian.sso.update', 'guardian/sso/{connection}'],
    ['DELETE', 'atrium.guardian.sso.destroy', 'guardian/sso/{connection}'],
    ['DELETE', 'atrium.guardian.sso-identities.destroy', 'guardian/sso-identities/{identity}'],
    ['POST', 'atrium.guardian.scim-tokens.store', 'guardian/organizations/{organization}/scim-tokens'],
    ['DELETE', 'atrium.guardian.scim-tokens.revoke', 'guardian/scim-tokens/{token}'],
]);

it('renders each slot\'s sections in their place, and opens a registered tab through ?tab=', function (): void {
    $this->actingAs(user());
    $connection = acmeConnection();
    registerProbes();

    $page = $this->get(route('atrium.guardian.sso.show', $connection->slug))->assertOk()
        ->assertSee('data-testid="screen-tabs"', false)
        ->assertSee('data-testid="tab-overview"', false)
        ->assertSee('data-testid="tab-probe.tab"', false);

    foreach (SSO_CONNECTION_SLOTS as $slot) {
        expect(nestedTestId($page, 'slot-'.$slot, 'section-probe.'.$slot))->toBeTrue($slot)
            ->and(nestedTestId($page, 'slot-'.$slot, 'probe-'.$slot))->toBeTrue($slot);
    }

    expect(nestedTestId($page, 'slot-header.actions', 'back-to-organization'))->toBeTrue();

    $tab = $this->get(route('atrium.guardian.sso.show', [$connection->slug, 'tab' => 'probe.tab']))->assertOk()
        ->assertSee('data-testid="tab-panel-probe.tab"', false)
        ->assertSee('tab for '.$connection->getKey())
        ->assertDontSee('data-testid="sso-settings"', false);

    expect(nestedTestId($tab, 'tab-panel-probe.tab', 'probe-tab'))->toBeTrue();
});

it('declares the connection as the screen\'s record, with only the overview tab and the standard detail slots', function (): void {
    $this->actingAs(user());
    $connection = acmeConnection();

    $page = $this->get(route('atrium.guardian.sso.show', $connection->slug))->assertOk();
    $resolved = $page->viewData('resolved');

    expect($resolved->screen->key)->toBe('guardian.sso-connection')
        ->and($resolved->screen->kind)->toBe('detail')
        ->and($resolved->screen->record?->is($connection))->toBeTrue()
        ->and(array_map(fn (TabDefinition $tab): string => $tab->key, $resolved->screen->tabs))->toBe(['overview'])
        ->and($resolved->screen->slots)->toBe(['header.actions', 'before', 'after', 'aside']);
});

it('renders no tab bar when nothing is registered', function (): void {
    $this->actingAs(user());

    $this->get(route('atrium.guardian.sso.show', acmeConnection()->slug))->assertOk()
        ->assertDontSee('data-testid="screen-tabs"', false)
        ->assertSee('data-testid="callback-url"', false)
        ->assertSee('data-testid="sso-settings"', false);
});

it('answers the screen\'s permissions through Roster\'s Authorizer, in the connection\'s organization', function (): void {
    authorizationOn();
    $connection = acmeConnection();
    $admin = user();
    grant($admin, 'admin', $connection->organization);
    $outsider = user();
    grant($outsider, 'admin', organization(attributes: ['name' => 'Globex']));

    View::addNamespace('guardian-probe', __DIR__.'/views');
    app(Atrium::class)->screens()->section('guardian.sso-connection', 'before', SectionDefinition::make('probe.manage')->view('guardian-probe::probe')->permission('guardian.sso.manage')->resolve(fn (): array => ['probe' => 'manage']));

    $this->actingAs($admin)->get(route('atrium.guardian.sso.show', 'acme-okta'))->assertOk()
        ->assertSee('data-testid="section-probe.manage"', false);

    $this->actingAs($outsider)->get(route('atrium.guardian.sso.show', 'acme-okta'))->assertForbidden();
});

it('refuses a viewer without guardian.sso.view before anything resolves', function (): void {
    authorizationOn();
    acmeConnection();
    $resolved = 0;
    Event::listen(SsoConnectionShowingActionEvent::class, function () use (&$resolved): void {
        $resolved++;
    });
    View::addNamespace('guardian-probe', __DIR__.'/views');
    app(Atrium::class)->screens()->section('guardian.sso-connection', 'before', SectionDefinition::make('probe.before')->view('guardian-probe::probe')->public()->resolve(function () use (&$resolved): array {
        $resolved++;

        return ['probe' => 'before'];
    }));

    $this->actingAs(user())->get(route('atrium.guardian.sso.show', 'acme-okta'))->assertForbidden();

    expect($resolved)->toBe(0);
});

it('links back to the organization\'s SSO tab', function (): void {
    $this->actingAs(user());
    $connection = acmeConnection();

    $this->get(route('atrium.guardian.sso.show', $connection->slug))->assertOk()
        ->assertSee('data-testid="back-to-organization"', false)
        ->assertSee(e(route('atrium.roster.organizations.show', ['acme', 'tab' => 'guardian.sso'])), false);
});

it('shows no back link when the configured route does not exist', function (): void {
    config(['guardian.atrium.organization_route' => 'atrium.nowhere.show']);
    $this->actingAs(user());

    $this->get(route('atrium.guardian.sso.show', acmeConnection()->slug))->assertOk()
        ->assertDontSee('data-testid="back-to-organization"', false);
});
