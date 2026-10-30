<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Support\Users;
use Workbench\App\Models\User;

// The SCIM case of Roster's tests/Feature/UserApprovalTest.php at the pinned
// SHA (criterion 13): a SCIM user provisioned into an organization whose
// provisioned_status is pending waits for approval.

it('lets organizations choose whether their SCIM accounts need approval', function (): void {
    require_once __DIR__.'/helpers.php';
    [$acme, $token] = scimOrg();
    test()->scimToken = $token;

    scim('POST', '/Users', oktaUser('one@acme.test', 'e1'))->assertCreated();
    app(UpdateOrganizationAction::class)->execute($acme, new UpdateOrganizationData(provisionedStatus: UserStatus::Pending));
    scim('POST', '/Users', oktaUser('two@acme.test', 'e2'))->assertCreated();

    expect(app(Users::class)->status(User::query()->where('email', 'one@acme.test')->sole()))->toBe(UserStatus::Active)
        ->and(app(Users::class)->status(User::query()->where('email', 'two@acme.test')->sole()))->toBe(UserStatus::Pending);
});
