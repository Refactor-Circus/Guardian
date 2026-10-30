<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Two\User as SocialiteUser;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoLoginFailedActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Domains\Sso\Services\Sso;
use Workbench\App\Models\User;

/*
 * D-039: an OIDC sign-in links an existing account by email, or creates one
 * just in time, only when the id_token says `email_verified: true`. A change
 * from Roster, which trusted the email alone. SAML is unchanged.
 */

beforeEach(function (): void {
    $this->idp = new FakeIdp;
    $this->connection = acmeWithSso();
});

/**
 * Run an OIDC sign-in end to end with the given extra id_token claims.
 *
 * @param  array<string, mixed>  $claims
 */
function signInWithOidc(array $claims): TestResponse
{
    $location = (string) test()->get(route('guardian.sso.start', 'acme-sso'))->headers->get('Location');
    $query = queryOf($location);

    test()->idp->idToken = test()->idp->sign($claims + ['sub' => 'okta-1', 'email' => 'ada@acme.test', 'name' => 'Ada', 'nonce' => $query['nonce']]);

    return test()->get(route('guardian.sso.callback', ['acme-sso', 'code' => 'c', 'state' => $query['state']]));
}

it('refuses to create an account just in time for an unverified email', function (array $claims): void {
    Event::fake([SsoLoginFailedActionEvent::class]);

    signInWithOidc($claims)
        ->assertRedirect('/')
        ->assertSessionHasErrors(['sso' => __('guardian::guardian.sso_failed_unverified_email')]);

    expect(Auth::check())->toBeFalse()
        ->and(User::query()->where('email', 'ada@acme.test')->exists())->toBeFalse()
        ->and(SsoIdentityModel::query()->exists())->toBeFalse();

    Event::assertDispatched(SsoLoginFailedActionEvent::class, fn (SsoLoginFailedActionEvent $event): bool => $event->reason === 'unverified_email' && $event->email === 'ada@acme.test');
})->with([
    'false' => [['email_verified' => false]],
    'missing' => [[]],
    'the string "true"' => [['email_verified' => 'true']],
]);

it('refuses to link an existing account by an unverified email', function (array $claims): void {
    $ada = user(['email' => 'ada@acme.test']);

    signInWithOidc($claims)
        ->assertSessionHasErrors(['sso' => __('guardian::guardian.sso_failed_unverified_email')]);

    expect(Auth::check())->toBeFalse()
        ->and(SsoIdentityModel::query()->where('user_id', $ada->getKey())->exists())->toBeFalse();
})->with([
    'false' => [['email_verified' => false]],
    'missing' => [[]],
]);

it('links an existing account when the email is verified', function (): void {
    $ada = user(['email' => 'ada@acme.test']);

    signInWithOidc(['email_verified' => true])->assertRedirect('/')->assertSessionHasNoErrors();

    expect(Auth::user()?->is($ada))->toBeTrue()
        ->and(SsoIdentityModel::query()->sole()->user_id)->toBe($ada->getKey());
});

it('creates an account just in time when the email is verified', function (): void {
    signInWithOidc(['email_verified' => true])->assertRedirect('/')->assertSessionHasNoErrors();

    expect(Auth::user()?->getAttribute('email'))->toBe('ada@acme.test');
});

it('still signs in an identity already linked, whatever the claim says', function (): void {
    $ada = user(['email' => 'ada@acme.test']);
    SsoIdentityModel::query()->create(['connection_id' => $this->connection->getKey(), 'subject' => 'okta-1', 'user_id' => $ada->getKey(), 'email' => 'ada@acme.test']);

    signInWithOidc(['email_verified' => false])->assertSessionHasNoErrors();

    expect(Auth::user()?->is($ada))->toBeTrue();
});

it('reads email_verified only for OIDC connections', function (string $protocol, array $raw, bool $verified): void {
    $connection = $this->connection;
    $connection->protocol = $protocol;
    $user = (new SocialiteUser)->setRaw($raw)->map(['id' => 'sub-1', 'email' => 'ada@acme.test', 'name' => 'Ada']);

    expect(app(Sso::class)->claims($connection, $user)->emailVerified)->toBe($verified);
})->with([
    'oidc, true' => ['oidc', ['email_verified' => true], true],
    'oidc, false' => ['oidc', ['email_verified' => false], false],
    'oidc, missing' => ['oidc', [], false],
    'saml, missing' => ['saml', [], true],
    'azure, missing' => ['azure', ['userPrincipalName' => 'ada@acme.test'], true],
]);
