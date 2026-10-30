<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Auth;

beforeEach(function (): void {
    $this->idp = new FakeIdp;
    $this->connection = acmeWithSso();
});

function startOidc(): array
{
    $location = (string) test()->get(route('guardian.sso.start', 'acme-sso'))->assertRedirect()->headers->get('Location');

    expect($location)->toStartWith('https://idp.test/authorize?');

    return queryOf($location);
}

it('signs a user in end to end', function (): void {
    $query = startOidc();

    expect($query['client_id'])->toBe('roster-app')
        ->and($query['scope'])->toBe('openid email profile')
        ->and($query['redirect_uri'])->toBe(route('guardian.sso.callback', 'acme-sso'));

    $this->idp->idToken = $this->idp->sign(['sub' => 'okta-1', 'email' => 'ada@acme.test', 'name' => 'Ada', 'nonce' => $query['nonce']]);

    $this->get(route('guardian.sso.callback', ['acme-sso', 'code' => 'c', 'state' => $query['state']]))->assertRedirect('/');

    expect(Auth::user()?->getAttribute('email'))->toBe('ada@acme.test');
});

it('discovers the connection from an email domain', function (): void {
    $this->get(route('guardian.sso.discover', ['email' => 'ada@acme.test']))->assertRedirect(route('guardian.sso.start', 'acme-sso'));
    $this->get(route('guardian.sso.discover', ['email' => 'ada@nowhere.test']))->assertSessionHasErrors('email');
});

it('refuses tokens that fail verification', function (string $case): void {
    $query = startOidc();
    $claims = ['sub' => 'okta-1', 'email' => 'ada@acme.test', 'nonce' => $query['nonce']];
    $state = $query['state'];

    $this->idp->idToken = match ($case) {
        'forged signature' => $this->idp->sign($claims, (new FakeIdp)->privateKey),
        'wrong audience' => $this->idp->sign(['aud' => 'someone-else'] + $claims),
        'wrong nonce' => $this->idp->sign(['nonce' => 'replayed'] + $claims),
        'expired' => $this->idp->sign(['exp' => time() - 3600, 'iat' => time() - 7200] + $claims),
        'wrong issuer' => $this->idp->sign(['iss' => 'https://evil.test'] + $claims),
        'bad state' => $this->idp->sign($claims),
    };

    if ($case === 'bad state') {
        $state = 'tampered';
    }

    $this->get(route('guardian.sso.callback', ['acme-sso', 'code' => 'c', 'state' => $state]))
        ->assertRedirect('/')
        ->assertSessionHasErrors('sso');

    expect(Auth::check())->toBeFalse();
})->with(['forged signature', 'wrong audience', 'wrong nonce', 'expired', 'wrong issuer', 'bad state']);
