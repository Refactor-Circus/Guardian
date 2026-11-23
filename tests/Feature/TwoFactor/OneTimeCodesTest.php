<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ConfirmOneTimeCodeAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RemoveTwoFactorMethodAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\StartOneTimeCodeEnrolmentAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Channels\MailOneTimeCodeChannel;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\OneTimeCodeChannel;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmOneTimeCodeData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RemoveTwoFactorMethodData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\StartOneTimeCodeEnrolmentData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\OneTimeCodeConfirmedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\OneTimeCodeConfirmingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\OneTimeCodeEnrolmentStartedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\OneTimeCodeEnrolmentStartingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\OneTimeCodeModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Notifications\OneTimeCodeNotification;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Factors;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\OneTimeCodes;
use Workbench\App\Models\User;

// Criterion 29: email and SMS one-time codes.

function startOneTimeCode(User $user, string $type = 'email', string $destination = 'ada@example.com'): TwoFactorMethodModel
{
    return app(StartOneTimeCodeEnrolmentAction::class)->execute($user, new StartOneTimeCodeEnrolmentData(type: $type, destination: $destination));
}

it('binds the mail channel, which sends email and no SMS', function (): void {
    $channel = app(OneTimeCodeChannel::class);

    expect($channel)->toBeInstanceOf(MailOneTimeCodeChannel::class)
        ->and($channel->supports('email'))->toBeTrue()
        ->and($channel->supports('sms'))->toBeFalse()
        ->and(app(Factors::class)->available())->toBe(['totp', 'email']);
});

it('enrols an email address, sends a six-digit code and confirms with it', function (): void {
    $channel = fakeOneTimeCodeChannel();
    $ada = user();

    $method = startOneTimeCode($ada);

    expect($method->confirmed_at)->toBeNull()
        ->and($channel->sent)->toHaveCount(1)
        ->and($channel->sent[0]['destination'])->toBe('ada@example.com')
        ->and($channel->lastCode())->toMatch('/^\d{6}$/');

    $confirmed = app(ConfirmOneTimeCodeAction::class)->execute($ada, new ConfirmOneTimeCodeData(type: 'email', code: $channel->lastCode()));

    expect($method->refresh()->confirmed_at)->not->toBeNull()
        ->and($confirmed->recoveryCodes)->toHaveCount(10);
});

it('stores codes only as HMACs and the destination encrypted', function (): void {
    $channel = fakeOneTimeCodeChannel();
    $method = startOneTimeCode(user());

    $row = (array) DB::table('guardian_one_time_codes')->where('method_id', $method->id)->first();

    expect($row['code_hash'])->toMatch('/^[0-9a-f]{64}$/')
        ->and($row['code_hash'])->not->toContain($channel->lastCode())
        ->and((string) DB::table('guardian_two_factor_methods')->where('id', $method->id)->value('destination'))->not->toContain('ada@example.com');
});

it('refuses SMS with the default channel, and sends it through a channel that supports it', function (): void {
    expect(fn () => startOneTimeCode(user(), 'sms', '+447700900123'))->toThrow(TwoFactorException::class);

    $channel = fakeOneTimeCodeChannel();
    $method = startOneTimeCode(user(), 'sms', '+447700900123');

    expect($method->type->value)->toBe('sms')->and($channel->sent[0]['type'])->toBe('sms');
});

it('accepts each code once', function (): void {
    $channel = fakeOneTimeCodeChannel();
    $ada = user();
    $method = startOneTimeCode($ada);
    $code = $channel->lastCode();

    app(ConfirmOneTimeCodeAction::class)->execute($ada, new ConfirmOneTimeCodeData(type: 'email', code: $code));

    expect(app(Factors::class)->verify($ada, $code))->toBeNull()
        ->and(OneTimeCodeModel::query()->where('method_id', $method->id)->whereNotNull('consumed_at')->count())->toBe(1);
});

it('expires codes after ttl_minutes', function (): void {
    config()->set('guardian.two_factor.one_time_codes.ttl_minutes', 5);
    $channel = fakeOneTimeCodeChannel();
    $ada = user();
    startOneTimeCode($ada);

    $this->travel(6)->minutes();

    expect(fn () => app(ConfirmOneTimeCodeAction::class)->execute($ada, new ConfirmOneTimeCodeData(type: 'email', code: $channel->lastCode())))
        ->toThrow(ValidationException::class);
});

it('allows five attempts, then refuses even the right code', function (): void {
    $channel = fakeOneTimeCodeChannel();
    $ada = user();
    $method = startOneTimeCode($ada);
    $wrong = $channel->lastCode() === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $attempt) {
        expect(fn () => app(ConfirmOneTimeCodeAction::class)->execute($ada, new ConfirmOneTimeCodeData(type: 'email', code: $wrong)))->toThrow(ValidationException::class);
    }

    expect(OneTimeCodeModel::query()->where('method_id', $method->id)->value('attempts'))->toBe(5)
        ->and(fn () => app(ConfirmOneTimeCodeAction::class)->execute($ada, new ConfirmOneTimeCodeData(type: 'email', code: $channel->lastCode())))->toThrow(ValidationException::class);
});

it('sends at most one code a minute per user, and a resend replaces the code', function (): void {
    $channel = fakeOneTimeCodeChannel();
    $ada = user();
    startOneTimeCode($ada);
    $first = $channel->lastCode();

    expect(fn () => startOneTimeCode($ada))->toThrow(fn (TwoFactorException $e) => expect($e->status())->toBe(429)->and($e->errorCode)->toBe('too_many_attempts'));

    // Another user is not held back.
    startOneTimeCode(user());

    $this->travel(61)->seconds();
    $method = startOneTimeCode($ada);

    expect(OneTimeCodeModel::query()->where('method_id', $method->id)->count())->toBe(1)
        ->and(fn () => app(ConfirmOneTimeCodeAction::class)->execute($ada, new ConfirmOneTimeCodeData(type: 'email', code: $first === $channel->lastCode() ? 'x' : $first)))->toThrow(ValidationException::class);

    app(ConfirmOneTimeCodeAction::class)->execute($ada, new ConfirmOneTimeCodeData(type: 'email', code: $channel->lastCode()));

    expect($method->refresh()->confirmed_at)->not->toBeNull();
});

it('lets a sent code prove a factor when removing another', function (): void {
    $channel = fakeOneTimeCodeChannel();
    $ada = user();
    [$totp] = enrolTotp($ada);
    $email = enrolOneTimeCode($ada, $channel);

    app(OneTimeCodes::class)->send($ada, $email);

    app(RemoveTwoFactorMethodAction::class)->execute($ada, $totp, new RemoveTwoFactorMethodData(code: $channel->lastCode()));

    expect($totp->fresh())->toBeNull();
});

it('dispatches the starting and finished events, without the code or destination', function (): void {
    Event::fake([OneTimeCodeEnrolmentStartingActionEvent::class, OneTimeCodeEnrolmentStartedActionEvent::class, OneTimeCodeConfirmingActionEvent::class, OneTimeCodeConfirmedActionEvent::class]);
    $channel = fakeOneTimeCodeChannel();

    enrolOneTimeCode(user(), $channel);

    Event::assertDispatched(OneTimeCodeEnrolmentStartingActionEvent::class, fn (OneTimeCodeEnrolmentStartingActionEvent $event): bool => $event->type === 'email');
    Event::assertDispatched(OneTimeCodeEnrolmentStartedActionEvent::class, fn (OneTimeCodeEnrolmentStartedActionEvent $event): bool => ! str_contains((string) json_encode($event->method), 'ada@example.com'));
    Event::assertDispatched(OneTimeCodeConfirmingActionEvent::class);
    Event::assertDispatched(OneTimeCodeConfirmedActionEvent::class);
});

describe('the mail notification', function (): void {
    it('sends the code to the enrolled address, at once', function (): void {
        Notification::fake();
        $ada = user(['email' => 'ada@example.com']);

        startOneTimeCode($ada, 'email', 'ada.work@example.com');

        Notification::assertSentOnDemand(OneTimeCodeNotification::class, function (OneTimeCodeNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
            $mail = $notification->toMail($notifiable);

            return $notifiable->routes['mail'] === 'ada.work@example.com'
                && preg_match('/^\d{6}$/', $notification->code) === 1
                && str_contains(implode(' ', $mail->introLines), $notification->code)
                && str_contains(implode(' ', $mail->introLines), '10 minutes');
        });
    });

    it('keeps the code and the destination out of the security log', function (): void {
        $channel = fakeOneTimeCodeChannel();
        enrolOneTimeCode(user(), $channel, 'email', 'ada.private@example.com');

        $log = (string) json_encode(DB::table('guardian_security_events')->get());

        expect($log)->not->toContain('ada.private@example.com')
            ->and($log)->not->toContain($channel->lastCode())
            ->and($log)->toContain('two_factor.enabled');
    });
});

describe('over HTTP', function (): void {
    it('enrols with 201, then confirms', function (): void {
        $channel = fakeOneTimeCodeChannel();
        $this->actingAs($ada = user());

        $this->postJson(route('guardian.two-factor.one-time-codes.store'), ['type' => 'email', 'destination' => 'ada@example.com', 'label' => 'Work'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'email')
            ->assertJsonPath('data.destination', 'a***@example.com')
            ->assertJsonPath('data.confirmed_at', null);

        $this->postJson(route('guardian.two-factor.one-time-codes.confirm'), ['type' => 'email', 'code' => $channel->lastCode()])
            ->assertOk()
            ->assertJsonPath('data.method.type', 'email')
            ->assertJsonCount(10, 'data.recovery_codes');
    });

    it('validates the destination for its type', function (): void {
        fakeOneTimeCodeChannel();
        $this->actingAs(user());

        $this->postJson(route('guardian.two-factor.one-time-codes.store'), ['type' => 'email', 'destination' => 'not-an-email'])->assertUnprocessable()->assertJsonValidationErrors('destination');
        $this->postJson(route('guardian.two-factor.one-time-codes.store'), ['type' => 'sms', 'destination' => '07700 900123'])->assertUnprocessable()->assertJsonValidationErrors('destination');
        $this->postJson(route('guardian.two-factor.one-time-codes.store'), ['type' => 'pigeon', 'destination' => 'x'])->assertUnprocessable()->assertJsonValidationErrors('type');
    });

    it('answers 422 method_unavailable for SMS with the default channel, and 429 for a second send in a minute', function (): void {
        $this->actingAs(user());

        $this->postJson(route('guardian.two-factor.one-time-codes.store'), ['type' => 'sms', 'destination' => '+447700900123'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'method_unavailable');

        fakeOneTimeCodeChannel();
        $this->postJson(route('guardian.two-factor.one-time-codes.store'), ['type' => 'sms', 'destination' => '+447700900123'])->assertCreated();
        $this->postJson(route('guardian.two-factor.one-time-codes.store'), ['type' => 'sms', 'destination' => '+447700900123'])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('code', 'too_many_attempts');
    });
});
