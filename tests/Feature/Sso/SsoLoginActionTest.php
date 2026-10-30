<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Sso\Actions\SsoLoginAction;
use RefactorCircus\Guardian\Domains\Sso\Data\IdentityClaims;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoLoginFailedActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoLoginSucceededActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\User\Actions\ApproveUserAction;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use Workbench\App\Models\User;

function claims(string $email = 'ada@acme.test', string $subject = 'sub-1'): IdentityClaims
{
    return new IdentityClaims($subject, $email, 'Ada Lovelace');
}

it('creates an account just in time for the organization\'s domains', function (): void {
    Event::fake([SsoLoginSucceededActionEvent::class]);
    $connection = acmeWithSso();

    [$user, $method] = app(SsoLoginAction::class)->execute($connection, claims());

    expect($method)->toBe('jit')
        ->and($user->getAttribute('email'))->toBe('ada@acme.test')
        ->and($user->getAttribute('email_verified_at'))->not->toBeNull()
        ->and($connection->organization->membershipFor($user))->not->toBeNull()
        ->and(SsoIdentityModel::query()->sole()->subject)->toBe('sub-1');

    Event::assertDispatched(SsoLoginSucceededActionEvent::class, fn (SsoLoginSucceededActionEvent $event): bool => $event->method === 'jit');
});

it('matches returning users by subject, even after an email change', function (): void {
    $connection = acmeWithSso();
    [$first] = app(SsoLoginAction::class)->execute($connection, claims());

    [$again, $method] = app(SsoLoginAction::class)->execute($connection, claims('renamed@elsewhere.test'));

    expect($method)->toBe('identity')->and($again->is($first))->toBeTrue();
});

it('links an existing account on a trusted domain', function (): void {
    $connection = acmeWithSso();
    $ada = user(['email' => 'Ada@acme.test']);

    [$user, $method] = app(SsoLoginAction::class)->execute($connection, claims());

    expect($method)->toBe('linked')->and($user->is($ada))->toBeTrue();
});

it('refuses emails outside the organization\'s domains', function (): void {
    $connection = acmeWithSso();
    user(['email' => 'eve@gmail.test']);

    app(SsoLoginAction::class)->execute($connection, claims('eve@gmail.test'));
})->throws(ValidationException::class);

it('records refused sign-ins', function (): void {
    Event::fake([SsoLoginFailedActionEvent::class]);
    $connection = acmeWithSso();

    try {
        app(SsoLoginAction::class)->execute($connection, claims('eve@gmail.test'));
    } catch (ValidationException) {
    }

    Event::assertDispatched(SsoLoginFailedActionEvent::class, fn (SsoLoginFailedActionEvent $event): bool => $event->reason === 'untrusted_email' && $event->email === 'eve@gmail.test');
});

it('refuses new accounts when just-in-time is off', function (): void {
    $connection = acmeWithSso(['jit' => false]);

    app(SsoLoginAction::class)->execute($connection, claims());
})->throws(ValidationException::class);

it('refuses inactive users and disabled connections', function (string $case): void {
    $connection = acmeWithSso($case === 'disabled' ? ['enabled' => false] : []);

    if ($case === 'inactive') {
        app(SuspendUserAction::class)->execute(user(['email' => 'ada@acme.test']));
    }

    app(SsoLoginAction::class)->execute($connection, claims());
})->with(['inactive', 'disabled'])->throws(ValidationException::class);

it('creates no duplicate users across logins', function (): void {
    $connection = acmeWithSso();

    app(SsoLoginAction::class)->execute($connection, claims());
    app(SsoLoginAction::class)->execute($connection, claims());

    expect(User::query()->where('email', 'ada@acme.test')->count())->toBe(1);
});

it('keeps a just-in-time account the organization wants approved, and refuses its sign-in until then', function (): void {
    $connection = acmeWithSso();
    $connection->organization->update(['provisioned_status' => 'pending']);

    expect(fn () => app(SsoLoginAction::class)->execute($connection, claims()))
        ->toThrow(ValidationException::class, 'awaiting approval');

    $ada = User::query()->where('email', 'ada@acme.test')->sole();

    expect($ada->rosterStatus()->value)->toBe('pending')
        ->and($connection->organization->membershipFor($ada))->not->toBeNull()
        ->and(SsoIdentityModel::query()->sole()->user_id)->toBe($ada->id);

    app(ApproveUserAction::class)->execute($ada);

    [$user, $method] = app(SsoLoginAction::class)->execute($connection, claims());

    expect($method)->toBe('identity')->and($user->is($ada))->toBeTrue();
});
