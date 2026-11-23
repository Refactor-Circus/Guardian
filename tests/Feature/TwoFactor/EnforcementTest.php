<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';
require_once dirname(__DIR__).'/Permission/helpers.php';

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\CreateTwoFactorPolicyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\DeleteTwoFactorPolicyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ListTwoFactorPoliciesAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\UpdateTwoFactorPolicyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\TwoFactorRequirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\CreateTwoFactorPolicyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\Requirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\UpdateTwoFactorPolicyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPoliciesListedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPoliciesListingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyCreatedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyCreatingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyDeletedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyDeletingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyUpdatedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyUpdatingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\PolicyTwoFactorRequirement;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;
use Workbench\App\Models\User;

// Criterion 34: who must use two-factor.

beforeEach(fn () => $this->travelTo(CarbonImmutable::parse('2026-11-23 12:00:00')));

/**
 * @param  array<string, mixed>  $attributes
 */
function twoFactorPolicy(array $attributes = []): TwoFactorPolicyModel
{
    return app(CreateTwoFactorPolicyAction::class)->execute(CreateTwoFactorPolicyData::from($attributes));
}

function requirementFor(User $user): Requirement
{
    return app(TwoFactorRequirement::class)->for($user);
}

it('binds the policy requirement, which requires nothing by default', function (): void {
    expect(app(TwoFactorRequirement::class))->toBeInstanceOf(PolicyTwoFactorRequirement::class)
        ->and(requirementFor(user()))->toEqual(new Requirement);
});

it('requires everyone, at once, with guardian.two_factor.required', function (): void {
    config()->set('guardian.two_factor.required', true);

    $requirement = requirementFor(user());

    expect($requirement->required)->toBeTrue()
        ->and($requirement->methods)->toBe(Requirement::ALL_METHODS)
        ->and($requirement->deadline)->toBeNull()
        ->and($requirement->due())->toBeTrue();
});

it('applies a policy without scope or role to everyone, with its grace', function (): void {
    twoFactorPolicy(['grace_days' => 7]);

    $requirement = requirementFor(user());

    expect($requirement->required)->toBeTrue()
        ->and($requirement->deadline?->toDateTimeString())->toBe('2026-11-30 12:00:00')
        ->and($requirement->due())->toBeFalse();
});

it('applies a role policy to the role\'s holders, anywhere', function (): void {
    $folder = folder();
    $editor = roleGranting('editor', []);
    $ada = user();
    $bob = user();
    assignDirectly($ada, $editor, $folder);

    twoFactorPolicy(['role' => 'editor']);

    expect(requirementFor($ada)->required)->toBeTrue()
        ->and(requirementFor($bob)->required)->toBeFalse();
});

it('applies a role policy with a scope only to holders within that scope', function (): void {
    $acme = folder(['name' => 'Acme']);
    $team = folder(['name' => 'Team', 'parent_id' => $acme->id]);
    $other = folder(['name' => 'Other']);
    $editor = roleGranting('editor', []);
    [$inside, $below, $outside] = [user(), user(), user()];
    assignDirectly($inside, $editor, $acme);
    assignDirectly($below, $editor, $team);
    assignDirectly($outside, $editor, $other);

    twoFactorPolicy(['role' => 'editor', 'scope_type' => FolderScope::class, 'scope_id' => $acme->id]);

    expect(requirementFor($inside)->required)->toBeTrue()
        ->and(requirementFor($below)->required)->toBeTrue()
        ->and(requirementFor($outside)->required)->toBeFalse();
});

it('applies a scope policy to members of the scope and the scopes beneath it, through ScopeChain', function (): void {
    $acme = folder(['name' => 'Acme']);
    $team = folder(['name' => 'Team', 'parent_id' => $acme->id]);
    $other = folder(['name' => 'Other']);
    $member = roleGranting('member-role', []);
    [$inAcme, $inTeam, $inOther, $global] = [user(), user(), user(), user()];
    assignDirectly($inAcme, $member, $acme);
    assignDirectly($inTeam, $member, $team);
    assignDirectly($inOther, $member, $other);
    assignDirectly($global, $member);

    twoFactorPolicy(['scope_type' => FolderScope::class, 'scope_id' => $team->id]);
    $policyOnAcme = twoFactorPolicy(['scope_type' => FolderScope::class, 'scope_id' => $acme->id, 'required' => false]);

    expect(requirementFor($inTeam)->required)->toBeTrue()
        ->and(requirementFor($inAcme)->required)->toBeFalse()
        ->and(requirementFor($inOther)->required)->toBeFalse()
        ->and(requirementFor($global)->required)->toBeFalse()
        ->and($policyOnAcme->scope_type)->toBe((new FolderScope)->getMorphClass());
});

it('intersects the matching policies\' methods', function (): void {
    twoFactorPolicy(['methods' => ['totp', 'passkey', 'email']]);
    twoFactorPolicy(['methods' => ['passkey', 'totp'], 'required' => false]);
    twoFactorPolicy();

    expect(requirementFor(user())->methods)->toBe(['totp', 'passkey']);
});

it('falls back to totp and passkey when the methods conflict, recording it once a day', function (): void {
    $ada = user();
    twoFactorPolicy(['methods' => ['email']]);
    twoFactorPolicy(['methods' => ['sms']]);

    expect(requirementFor($ada)->methods)->toBe(['totp', 'passkey'])
        ->and(requirementFor($ada)->methods)->toBe(['totp', 'passkey'])
        ->and(SecurityEventModel::query()->where('type', 'two_factor.policy_conflict')->count())->toBe(1);

    $this->travel(1)->day();
    requirementFor($ada);

    expect(SecurityEventModel::query()->where('type', 'two_factor.policy_conflict')->count())->toBe(2);
});

it('takes the earliest deadline of the requiring policies', function (): void {
    twoFactorPolicy(['grace_days' => 30]);
    $this->travel(2)->days();
    twoFactorPolicy(['grace_days' => 5]);
    twoFactorPolicy(['grace_days' => 1, 'required' => false]);

    expect(requirementFor(user())->deadline?->toDateTimeString())->toBe('2026-11-30 12:00:00');
});

it('lets a host bind its own requirement', function (): void {
    app()->instance(TwoFactorRequirement::class, new class implements TwoFactorRequirement
    {
        public function for(Authenticatable $user): Requirement
        {
            return new Requirement(true, ['passkey']);
        }
    });
    twoFactorPolicy(['methods' => ['totp']]);

    $this->actingAs(user())->getJson(route('guardian.two-factor.show'))
        ->assertOk()
        ->assertJsonPath('data.required', true)
        ->assertJsonPath('data.allowed_methods', ['passkey']);
});

it('shows the requirement in the status', function (): void {
    twoFactorPolicy(['grace_days' => 3, 'methods' => ['totp']]);

    $this->actingAs(user())->getJson(route('guardian.two-factor.show'))
        ->assertOk()
        ->assertJsonPath('data.required', true)
        ->assertJsonPath('data.allowed_methods', ['totp'])
        ->assertJsonPath('data.enrolment_deadline', '2026-11-26T12:00:00+00:00');
});

it('enforces a policy through the middleware: header before the deadline, 403 after it', function (): void {
    withSessions();
    twoFactorPolicy(['grace_days' => 2]);
    signIn(user());

    $this->get('test-protected')->assertOk()->assertHeader('Guardian-Two-Factor-Deadline', '2026-11-25T12:00:00+00:00');

    $this->travel(3)->days();
    $this->getJson('test-protected')->assertForbidden()->assertJsonPath('code', 'two_factor_enrollment_required');
});

it('creates, updates, lists and deletes policies, dispatching their events', function (): void {
    Event::fake([TwoFactorPolicyCreatingActionEvent::class, TwoFactorPolicyCreatedActionEvent::class, TwoFactorPolicyUpdatingActionEvent::class, TwoFactorPolicyUpdatedActionEvent::class, TwoFactorPoliciesListingActionEvent::class, TwoFactorPoliciesListedActionEvent::class, TwoFactorPolicyDeletingActionEvent::class, TwoFactorPolicyDeletedActionEvent::class]);

    $policy = twoFactorPolicy(['methods' => ['totp']]);
    app(UpdateTwoFactorPolicyAction::class)->execute($policy, UpdateTwoFactorPolicyData::from(['grace_days' => 4, 'methods' => null]));

    expect($policy->refresh()->grace_days)->toBe(4)
        ->and($policy->methods)->toBeNull()
        ->and($policy->required)->toBeTrue()
        ->and(app(ListTwoFactorPoliciesAction::class)->execute()->total())->toBe(1);

    app(DeleteTwoFactorPolicyAction::class)->execute($policy);

    expect(TwoFactorPolicyModel::query()->count())->toBe(0);

    foreach ([TwoFactorPolicyCreatingActionEvent::class, TwoFactorPolicyCreatedActionEvent::class, TwoFactorPolicyUpdatingActionEvent::class, TwoFactorPolicyUpdatedActionEvent::class, TwoFactorPoliciesListingActionEvent::class, TwoFactorPoliciesListedActionEvent::class, TwoFactorPolicyDeletingActionEvent::class, TwoFactorPolicyDeletedActionEvent::class] as $event) {
        Event::assertDispatched($event);
    }
});
