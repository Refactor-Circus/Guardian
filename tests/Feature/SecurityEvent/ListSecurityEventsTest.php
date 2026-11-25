<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Event;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\ListSecurityEventsAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\ShowSecurityEventAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\ListSecurityEventsData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventShowingActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventShownActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventsListedActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventsListingActionEvent;

// Criterion 25: the log filtered by type, user and time, newest first, a
// cursor at a time; and one event.

it('lists events newest first, filtered by type, user and time', function (): void {
    Event::fake([SecurityEventsListingActionEvent::class, SecurityEventsListedActionEvent::class]);
    $ada = user();
    $bob = user();
    $this->travelTo(now()->subDays(3));
    $old = recordSecurityEvent(SecurityEventType::LoginFailed, $ada);
    $this->travelBack();
    $failed = recordSecurityEvent(SecurityEventType::LoginFailed, $bob);
    $this->travel(1)->minutes();
    $login = recordSecurityEvent(SecurityEventType::LoginSucceeded, $ada);

    $ids = fn (array $filters): array => app(ListSecurityEventsAction::class)->execute(ListSecurityEventsData::from($filters))->getCollection()->pluck('id')->all();

    expect($ids([]))->toBe([$login->id, $failed->id, $old->id])
        ->and($ids(['type' => 'login.failed']))->toBe([$failed->id, $old->id])
        ->and($ids(['user' => (string) $ada->getKey()]))->toBe([$login->id, $old->id])
        ->and($ids(['from' => now()->subDay()->toIso8601String()]))->toBe([$login->id, $failed->id])
        ->and($ids(['to' => now()->subDay()->toIso8601String()]))->toBe([$old->id]);

    Event::assertDispatched(SecurityEventsListingActionEvent::class);
    Event::assertDispatched(SecurityEventsListedActionEvent::class);
});

it('pages with a cursor', function (): void {
    foreach (range(1, 5) as $minute) {
        $this->travel(1)->minutes();
        recordSecurityEvent(SecurityEventType::Logout);
    }

    $first = app(ListSecurityEventsAction::class)->execute(ListSecurityEventsData::from(['per_page' => 2]));
    $second = app(ListSecurityEventsAction::class)->execute(ListSecurityEventsData::from(['per_page' => 2, 'cursor' => $first->nextCursor()?->encode()]));

    expect($first->getCollection())->toHaveCount(2)
        ->and($second->getCollection())->toHaveCount(2)
        ->and(array_intersect($first->getCollection()->pluck('id')->all(), $second->getCollection()->pluck('id')->all()))->toBe([]);
});

it('shows one event', function (): void {
    Event::fake([SecurityEventShowingActionEvent::class, SecurityEventShownActionEvent::class]);
    $event = recordSecurityEvent(SecurityEventType::IpDenied);

    expect(app(ShowSecurityEventAction::class)->execute($event)->is($event))->toBeTrue();

    Event::assertDispatched(SecurityEventShowingActionEvent::class);
    Event::assertDispatched(SecurityEventShownActionEvent::class);
});
