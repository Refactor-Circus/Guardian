<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Support\Users;
use Workbench\App\Models\User;

// The SCIM case of Roster's tests/Feature/ProvisionedStatusTest.php at the
// pinned SHA (criterion 13): ScimUsers takes a new account's status from
// Roster::provisionedStatus().

it('provisions SCIM accounts through it, so a bad stored value never fails provisioning', function (): void {
    require_once __DIR__.'/helpers.php';
    Exceptions::fake();

    [$acme, $token] = scimOrg();
    $acme->update(['provisioned_status' => 'bogus']);
    test()->scimToken = $token;

    scim('POST', '/Users', oktaUser('one@acme.test', 'e1'))->assertCreated();

    expect(app(Users::class)->status(User::query()->where('email', 'one@acme.test')->sole()))->toBe(UserStatus::Active);
    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => str_contains($exception->getMessage(), '[bogus]'));
});
