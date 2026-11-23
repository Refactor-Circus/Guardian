<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Event;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\RecordSecurityEventAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\RecordSecurityEventData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventRecordedActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventRecordingActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\SecurityEvent\Support\IdentifierHash;
use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;
use RefactorCircus\Keen\KeenServiceProvider;

// Criterion 23: RecordSecurityEventAction is the only writer; every type is
// recorded, announced and audited, and no secret is ever kept.

/**
 * Secret values a careless caller might put in a context.
 */
const SECURITY_SECRETS = [
    'password' => 'hunter2-password',
    'code' => '918273',
    'recovery_code' => 'abcde-fghij',
    'secret' => 'JBSWY3DPEHPK3PXP',
    'token' => 'tok_live_secret',
    'nested' => ['api_token' => 'tok_nested_secret', 'safe' => 'kept'],
];

it('records every type, starting and finishing as an action', function (SecurityEventType $type): void {
    Event::fake([SecurityEventRecordingActionEvent::class, SecurityEventRecordedActionEvent::class]);
    $ada = user();

    $event = recordSecurityEvent($type, $ada, 'Ada@Example.com ', ['reason' => 'test']);

    expect($event->type)->toBe($type)
        ->and($event->user_id)->toBe((string) $ada->getKey())
        ->and($event->identifier_hash)->toBe(IdentifierHash::of('ada@example.com'))
        ->and($event->identifier_hash)->toBe(hash_hmac('sha256', 'ada@example.com', (string) config('app.key')))
        ->and($event->ip)->toBe('203.0.113.7')
        ->and($event->context)->toBe(['reason' => 'test']);

    Event::assertDispatched(SecurityEventRecordingActionEvent::class, fn (SecurityEventRecordingActionEvent $starting): bool => $starting->type === $type);
    Event::assertDispatched(SecurityEventRecordedActionEvent::class, fn (SecurityEventRecordedActionEvent $finished): bool => $finished->securityEvent->is($event));
})->with(SecurityEventType::cases());

it('keeps no secret, and no identifier as typed', function (): void {
    foreach (SecurityEventType::cases() as $type) {
        recordSecurityEvent($type, user(), 'victim@example.com', SECURITY_SECRETS);
    }

    $stored = (string) json_encode(SecurityEventModel::query()->get()->toArray());

    foreach (['hunter2', '918273', 'abcde-fghij', 'JBSWY3DPEHPK3PXP', 'tok_live_secret', 'tok_nested_secret', 'victim@example.com'] as $secret) {
        expect($stored)->not->toContain($secret);
    }

    expect(SecurityEventModel::query()->first()?->context)->toBe(['nested' => ['safe' => 'kept']]);
});

it('cuts the user agent to 512 characters', function (): void {
    $event = app(RecordSecurityEventAction::class)->execute(new RecordSecurityEventData(
        type: SecurityEventType::LoginSucceeded,
        userAgent: str_repeat('a', 2000),
    ));

    expect(strlen((string) $event->user_agent))->toBe(512);
});

describe('with Keen', function (): void {
    beforeEach(function (): void {
        $this->app->register(KeenServiceProvider::class);
        $this->loadMigrationsFrom(dirname(__DIR__, 3).'/vendor/refactor-circus/keen/database/migrations');
    });

    it('audits every recorded event as Guardian\'s, without a secret', function (): void {
        foreach (SecurityEventType::cases() as $type) {
            recordSecurityEvent($type, user(), 'victim@example.com', SECURITY_SECRETS);
        }

        $entries = AuditEntryModel::query()->where('source', 'guardian')->where('subject_type', (new SecurityEventModel)->getMorphClass())->get();

        expect($entries->pluck('subject_id')->unique()->count())->toBe(count(SecurityEventType::cases()));

        $log = (string) json_encode(AuditEntryModel::query()->get()->toArray());

        foreach (['hunter2', '918273', 'abcde-fghij', 'JBSWY3DPEHPK3PXP', 'tok_live_secret', 'tok_nested_secret', 'victim@example.com'] as $secret) {
            expect($log)->not->toContain($secret);
        }
    });
});
