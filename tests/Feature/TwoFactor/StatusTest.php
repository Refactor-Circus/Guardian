<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';
require_once dirname(__DIR__).'/Permission/helpers.php';

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RemoveTwoFactorMethodAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ResetTwoFactorAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ShowTwoFactorAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RemoveTwoFactorMethodData;
use RefactorCircus\Guardian\Domains\TwoFactor\Enums\TwoFactorMethodType;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorMethodRemovedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorMethodRemovingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorResetActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorResettingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorShowingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorShownActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\RemoveTwoFactorMethodTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\ResetTwoFactorTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools\ShowTwoFactorTool;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\RecoveryCodeModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\RecoveryCodes;
use RefactorCircus\Guardian\Mcp\GuardianServer;

// Criterion 31: status, removal and the admin reset.

beforeEach(fn () => $this->travelTo(now()->startOfMinute()->addSeconds(10)));

it('reports a user without two-factor', function (): void {
    $status = app(ShowTwoFactorAction::class)->execute(user());

    expect($status->enabled)->toBeFalse()
        ->and($status->methods)->toBeEmpty()
        ->and($status->recoveryCodesRemaining)->toBe(0)
        ->and($status->requirement->required)->toBeFalse();
});

it('answers the status payload with masked destinations and no secret', function (): void {
    $ada = user();
    [$method, $secret] = enrolTotp($ada);
    TwoFactorMethodModel::query()->create(['user_id' => $ada->getKey(), 'type' => TwoFactorMethodType::Email, 'destination' => 'ada@example.com', 'label' => 'Work', 'confirmed_at' => now()]);
    TwoFactorMethodModel::query()->create(['user_id' => $ada->getKey(), 'type' => TwoFactorMethodType::Sms, 'destination' => '+447700900123']);
    config()->set('guardian.two_factor.required', true);

    $response = $this->actingAs($ada)->getJson(route('guardian.two-factor.show'))
        ->assertOk()
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.methods.0.id', $method->id)
        ->assertJsonPath('data.methods.0.type', 'totp')
        ->assertJsonPath('data.methods.0.destination', null)
        ->assertJsonPath('data.methods.1.destination', 'a***@example.com')
        ->assertJsonPath('data.methods.2.destination', '***********23')
        ->assertJsonPath('data.methods.2.confirmed_at', null)
        ->assertJsonPath('data.passkeys', [])
        ->assertJsonPath('data.recovery_codes_remaining', 10)
        ->assertJsonPath('data.required', true)
        ->assertJsonPath('data.enrolment_deadline', null);

    expect(array_keys($response->json('data')))->toBe(['enabled', 'methods', 'passkeys', 'passkeys_available', 'recovery_codes_remaining', 'required', 'allowed_methods', 'enrolment_deadline'])
        ->and(array_keys($response->json('data.methods.0')))->toBe(['id', 'type', 'label', 'destination', 'confirmed_at', 'last_used_at', 'created_at'])
        ->and($response->getContent())->not->toContain($secret)
        ->and($response->getContent())->not->toContain('ada@example.com')
        ->and($response->getContent())->not->toContain('+447700900123')
        ->and($response->json('data.allowed_methods'))->toContain('totp');
});

it('dispatches the status events', function (): void {
    Event::fake([TwoFactorShowingActionEvent::class, TwoFactorShownActionEvent::class]);

    app(ShowTwoFactorAction::class)->execute(user());

    Event::assertDispatched(TwoFactorShowingActionEvent::class);
    Event::assertDispatched(TwoFactorShownActionEvent::class);
});

describe('removing a factor', function (): void {
    it('needs a code from another factor, never its own', function (): void {
        $ada = user();
        [$phone, $phoneSecret] = enrolTotp($ada);
        [$tablet, $tabletSecret] = enrolTotp($ada, 'Tablet');
        $remove = app(RemoveTwoFactorMethodAction::class);

        expect(fn () => $remove->execute($ada, $phone, new RemoveTwoFactorMethodData))->toThrow(ValidationException::class)
            ->and(fn () => $remove->execute($ada, $phone, new RemoveTwoFactorMethodData(code: totpCode($phoneSecret, 1))))->toThrow(ValidationException::class);

        $remove->execute($ada, $phone, new RemoveTwoFactorMethodData(code: totpCode($tabletSecret, 1)));

        expect(TwoFactorMethodModel::query()->whereKey($phone->id)->exists())->toBeFalse()
            ->and(TwoFactorMethodModel::query()->whereKey($tablet->id)->exists())->toBeTrue()
            ->and(SecurityEventModel::query()->where('type', 'two_factor.disabled')->count())->toBe(1);
    });

    it('takes a recovery code, used up and recorded, and drops the codes with the last factor', function (): void {
        $ada = user();
        [$method, , $codes] = enrolTotp($ada);

        app(RemoveTwoFactorMethodAction::class)->execute($ada, $method, new RemoveTwoFactorMethodData(code: (string) $codes[0]));

        expect(TwoFactorMethodModel::query()->count())->toBe(0)
            ->and(RecoveryCodeModel::query()->count())->toBe(0)
            ->and(SecurityEventModel::query()->where('type', 'two_factor.recovery_code_used')->count())->toBe(1)
            ->and(SecurityEventModel::query()->where('type', 'two_factor.disabled')->count())->toBe(1);
    });

    it('refuses the last factor while two-factor is required', function (): void {
        config()->set('guardian.two_factor.required', true);
        $ada = user();
        [$method, , $codes] = enrolTotp($ada);

        expect(fn () => app(RemoveTwoFactorMethodAction::class)->execute($ada, $method, new RemoveTwoFactorMethodData(code: (string) $codes[0])))
            ->toThrow(TwoFactorException::class);

        expect(TwoFactorMethodModel::query()->whereKey($method->id)->exists())->toBeTrue()
            ->and(app(RecoveryCodes::class)->remaining($ada->getKey()))->toBe(10);
    });

    it('removes an unconfirmed method without a code', function (): void {
        $ada = user();
        $pending = TwoFactorMethodModel::query()->create(['user_id' => $ada->getKey(), 'type' => TwoFactorMethodType::Totp, 'secret' => 'JBSWY3DPEHPK3PXP']);

        app(RemoveTwoFactorMethodAction::class)->execute($ada, $pending);

        expect(TwoFactorMethodModel::query()->count())->toBe(0)
            ->and(SecurityEventModel::query()->where('type', 'two_factor.disabled')->count())->toBe(0);
    });

    it('dispatches the removal events', function (): void {
        Event::fake([TwoFactorMethodRemovingActionEvent::class, TwoFactorMethodRemovedActionEvent::class]);
        $ada = user();
        $pending = TwoFactorMethodModel::query()->create(['user_id' => $ada->getKey(), 'type' => TwoFactorMethodType::Totp, 'secret' => 'JBSWY3DPEHPK3PXP']);

        app(RemoveTwoFactorMethodAction::class)->execute($ada, $pending);

        Event::assertDispatched(TwoFactorMethodRemovingActionEvent::class);
        Event::assertDispatched(TwoFactorMethodRemovedActionEvent::class);
    });

    it('works over HTTP, 204, with 422 two_factor_required for the last required factor and 404 for another user\'s', function (): void {
        $ada = user();
        [$phone, , $codes] = enrolTotp($ada);
        [$theirs] = enrolTotp(user());
        $this->actingAs($ada);

        $this->deleteJson(route('guardian.two-factor.methods.destroy', $theirs->id), ['code' => (string) $codes[0]])->assertNotFound();
        $this->deleteJson(route('guardian.two-factor.methods.destroy', $phone->id))->assertUnprocessable()->assertJsonValidationErrors('code');

        config()->set('guardian.two_factor.required', true);
        $this->deleteJson(route('guardian.two-factor.methods.destroy', $phone->id), ['code' => (string) $codes[0]])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'two_factor_required');

        config()->set('guardian.two_factor.required', false);
        $this->deleteJson(route('guardian.two-factor.methods.destroy', $phone->id), ['code' => (string) $codes[1]])->assertNoContent();
    });
});

describe('the admin reset', function (): void {
    it('removes every factor and code, recording who reset it', function (): void {
        $ada = user();
        enrolTotp($ada);
        $admin = user();
        Event::fake([TwoFactorResettingActionEvent::class, TwoFactorResetActionEvent::class]);

        app(ResetTwoFactorAction::class)->execute($ada, $admin);

        $event = SecurityEventModel::query()->where('type', 'two_factor.disabled')->sole();

        expect(TwoFactorMethodModel::query()->count())->toBe(0)
            ->and(RecoveryCodeModel::query()->count())->toBe(0)
            ->and($event->user_id)->toBe($ada->getKey())
            ->and($event->context)->toBe(['reset' => true, 'reset_by' => (string) $admin->getKey()]);

        Event::assertDispatched(TwoFactorResettingActionEvent::class);
        Event::assertDispatched(TwoFactorResetActionEvent::class);
    });

    it('needs guardian.two-factor.manage over HTTP', function (): void {
        config()->set('guardian.authorization.enabled', true);
        $ada = user();
        enrolTotp($ada);

        $this->deleteJson(route('guardian.users.two-factor.destroy', $ada->getKey()))->assertUnauthorized();
        $this->actingAs(user())->deleteJson(route('guardian.users.two-factor.destroy', $ada->getKey()))->assertForbidden();
        $this->actingAs($ada)->deleteJson(route('guardian.users.two-factor.destroy', $ada->getKey()))->assertForbidden();

        $admin = user();
        assignDirectly($admin, roleGranting('security-admin', ['guardian.two-factor.manage']));

        $this->actingAs($admin)->deleteJson(route('guardian.users.two-factor.destroy', $ada->getKey()))->assertNoContent();
        $this->actingAs($admin)->deleteJson(route('guardian.users.two-factor.destroy', 'missing'))->assertNotFound();

        expect(TwoFactorMethodModel::query()->count())->toBe(0);
    });
});

describe('over MCP', function (): void {
    it('offers the status, removal and reset tools', function (string $tool): void {
        expect(GuardianServer::TOOLS)->toContain($tool);
    })->with([ShowTwoFactorTool::class, RemoveTwoFactorMethodTool::class, ResetTwoFactorTool::class]);

    it('shows your own status with the HTTP API\'s payload, and another user\'s with guardian.two-factor.manage', function (): void {
        config()->set('guardian.authorization.enabled', true);
        $ada = user();
        enrolTotp($ada);
        $this->actingAs($ada);

        mcpTool(ShowTwoFactorTool::class)->assertOk()->assertStructuredContent($this->getJson(route('guardian.two-factor.show'))->json());

        $this->actingAs(user());
        mcpTool(ShowTwoFactorTool::class, ['user' => (string) $ada->getKey()])->assertHasErrors(['Unauthorized.']);

        $admin = user();
        assignDirectly($admin, roleGranting('security-admin', ['guardian.two-factor.manage']));
        $this->actingAs($admin);
        mcpTool(ShowTwoFactorTool::class, ['user' => (string) $ada->getKey()])->assertOk()->assertSee('"enabled":true');
        mcpTool(ResetTwoFactorTool::class, ['user' => (string) $ada->getKey()])->assertOk();

        expect(TwoFactorMethodModel::query()->count())->toBe(0);
    });

    it('removes your own method with another factor\'s code', function (): void {
        $ada = user();
        [$phone] = enrolTotp($ada);
        [, $tabletSecret] = enrolTotp($ada, 'Tablet');
        $this->actingAs($ada);

        mcpTool(RemoveTwoFactorMethodTool::class, ['method' => $phone->id])->assertHasErrors();
        mcpTool(RemoveTwoFactorMethodTool::class, ['method' => $phone->id, 'code' => totpCode($tabletSecret, 1)])->assertOk();

        expect(TwoFactorMethodModel::query()->whereKey($phone->id)->exists())->toBeFalse();
    });
});
