<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Session/helpers.php';
require_once dirname(__DIR__).'/Permission/helpers.php';
require_once dirname(__DIR__).'/Token/helpers.php';

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\Session\Events\DeviceForgettingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\DeviceForgottenActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\DevicesListedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\DevicesListingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\OtherSessionsRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\OtherSessionsRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\SessionRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\SessionRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\SessionsListedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\SessionsListingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\UserSessionsListedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\UserSessionsListingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\UserSessionsRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\UserSessionsRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;

// Criterion 38: sessions and devices over HTTP.

beforeEach(function (): void {
    $this->travelTo(now()->startOfMinute()->addSeconds(10));
    Notification::fake();
    withTrackedSessions();
});

it('lists your own sessions, newest activity first, with the current one flagged', function (): void {
    $ada = user();
    $other = sessionRecord($ada, ['last_active_at' => now()->subHour()]);
    sessionRecord(user());
    sessionRecord($ada, ['revoked_at' => now()]);
    signInFrom($ada);
    $current = SessionModel::query()->where('ip', '203.0.113.7')->sole();

    $this->getJson(route('guardian.sessions.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $current->id)
        ->assertJsonPath('data.0.current', true)
        ->assertJsonPath('data.0.ip', '203.0.113.7')
        ->assertJsonPath('data.0.user_agent', 'Mozilla/5.0 (Macintosh) Firefox/130.0')
        ->assertJsonPath('data.0.device_id', $current->device_id)->assertJsonPath('data.0.device_name', 'Mozilla/5.0 (Macintosh) Firefox/130.0')
        ->assertJsonPath('data.1.id', $other->id)
        ->assertJsonPath('data.1.current', false)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonMissingPath('data.0.key_hash');
});

it('revokes one of your sessions and records session.revoked', function (): void {
    $ada = user();
    $other = sessionRecord($ada);
    $theirs = sessionRecord(user());
    signInFrom($ada);

    $this->deleteJson(route('guardian.sessions.destroy', $theirs->id))->assertNotFound();
    $this->deleteJson(route('guardian.sessions.destroy', $other->id))->assertNoContent();

    expect($other->fresh()?->revoked_at)->not->toBeNull()
        ->and($theirs->fresh()?->revoked_at)->toBeNull();

    $event = SecurityEventModel::query()->where('type', 'session.revoked')->sole();
    expect((string) $event->user_id)->toBe((string) $ada->getKey())
        ->and($event->context)->toMatchArray(['session' => $other->id, 'by' => (string) $ada->getKey()]);

    // A revoked session is gone from the list and cannot be revoked again.
    $this->deleteJson(route('guardian.sessions.destroy', $other->id))->assertNotFound();
});

it('signs out everywhere else, keeping the current session', function (): void {
    $ada = user();
    $first = sessionRecord($ada);
    $second = sessionRecord($ada);
    signInFrom($ada);

    $this->deleteJson(route('guardian.sessions.others.destroy'))->assertOk()->assertJson(['revoked' => 2, 'tokens_revoked' => null]);

    expect($first->fresh()?->revoked_at)->not->toBeNull()
        ->and($second->fresh()?->revoked_at)->not->toBeNull()
        ->and(SessionModel::query()->whereNull('revoked_at')->count())->toBe(1)
        ->and(SecurityEventModel::query()->where('type', 'session.revoked')->count())->toBe(2);

    $this->get('test-tracked')->assertOk();
});

it('revokes your API tokens too with tokens: true, through the token driver', function (): void {
    $ada = user();
    $driver = fakeTokenDriver();
    $driver->issue($ada, 'CLI', ['*'], null);
    $driver->issue($ada, 'CI', ['*'], null);
    sessionRecord($ada);
    signInFrom($ada);

    $this->deleteJson(route('guardian.sessions.others.destroy'), ['tokens' => true])->assertOk()->assertJson(['revoked' => 1, 'tokens_revoked' => 2]);

    expect($driver->revokeAllCalls)->toBe(1)
        ->and($driver->tokens($ada))->toBe([]);
});

it('refuses tokens: true without a token driver, revoking nothing', function (): void {
    $ada = user();
    $other = sessionRecord($ada);
    signInFrom($ada);

    $this->deleteJson(route('guardian.sessions.others.destroy'), ['tokens' => true])->assertStatus(501)->assertJson(['message' => 'No API token driver is configured.']);

    expect($other->fresh()?->revoked_at)->toBeNull();
});

it('lists and revokes another user\'s sessions with guardian.sessions.manage', function (): void {
    config()->set('guardian.authorization.enabled', true);
    $ada = user();
    sessionRecord($ada);
    sessionRecord($ada);
    $admin = user();

    $this->actingAs(user())->getJson(route('guardian.users.sessions.index', $ada->getKey()))->assertForbidden();
    $this->deleteJson(route('guardian.users.sessions.destroy', $ada->getKey()))->assertForbidden();

    assignDirectly($admin, roleGranting('session-admin', ['guardian.sessions.manage']));
    $this->actingAs($admin);

    $this->getJson(route('guardian.users.sessions.index', $ada->getKey()))->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.current', false);
    $this->deleteJson(route('guardian.users.sessions.destroy', $ada->getKey()))->assertOk()->assertJson(['revoked' => 2]);
    $this->getJson(route('guardian.users.sessions.index', 'nobody'))->assertNotFound();

    expect(SessionModel::query()->whereNull('revoked_at')->count())->toBe(0)
        ->and(SecurityEventModel::query()->where('type', 'session.revoked')->pluck('context')->pluck('by')->unique()->all())->toBe([(string) $admin->getKey()]);
});

it('lists your devices and forgets one, revoking its sessions', function (): void {
    $ada = user();
    signInFrom($ada);
    signInFrom($ada, 'Phone');
    $phone = DeviceModel::query()->where('name', 'Phone')->sole();
    $theirs = DeviceModel::query()->create(['user_id' => user()->getKey(), 'fingerprint_hash' => 'x', 'first_seen_at' => now(), 'last_seen_at' => now()]);

    $this->getJson(route('guardian.devices.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.last_ip', '203.0.113.7')
        ->assertJsonMissingPath('data.0.fingerprint_hash');

    $this->deleteJson(route('guardian.devices.destroy', $theirs->id))->assertNotFound();
    $this->deleteJson(route('guardian.devices.destroy', $phone->id))->assertNoContent();

    expect($phone->fresh())->toBeNull()
        ->and(SessionModel::query()->where('device_id', $phone->id)->count())->toBe(0)
        ->and(SessionModel::query()->whereNotNull('revoked_at')->count())->toBe(1)
        ->and(SecurityEventModel::query()->where('type', 'session.revoked')->sole()->context)->toMatchArray(['device' => $phone->id]);
});

it('dispatches a starting and a finished event for every action', function (): void {
    $ada = user();
    signInFrom($ada);
    $session = sessionRecord($ada);
    $device = DeviceModel::query()->sole();
    Event::fake([
        SessionsListingActionEvent::class, SessionsListedActionEvent::class,
        SessionRevokingActionEvent::class, SessionRevokedActionEvent::class,
        OtherSessionsRevokingActionEvent::class, OtherSessionsRevokedActionEvent::class,
        UserSessionsListingActionEvent::class, UserSessionsListedActionEvent::class,
        UserSessionsRevokingActionEvent::class, UserSessionsRevokedActionEvent::class,
        DevicesListingActionEvent::class, DevicesListedActionEvent::class,
        DeviceForgettingActionEvent::class, DeviceForgottenActionEvent::class,
    ]);

    $this->getJson(route('guardian.sessions.index'))->assertOk();
    $this->deleteJson(route('guardian.sessions.destroy', $session->id))->assertNoContent();
    $this->deleteJson(route('guardian.sessions.others.destroy'))->assertOk();
    $this->getJson(route('guardian.users.sessions.index', $ada->getKey()))->assertOk();
    $this->getJson(route('guardian.devices.index'))->assertOk();
    $this->deleteJson(route('guardian.devices.destroy', $device->id))->assertNoContent();
    $this->deleteJson(route('guardian.users.sessions.destroy', $ada->getKey()))->assertOk();

    foreach ([
        SessionsListingActionEvent::class, SessionsListedActionEvent::class,
        SessionRevokingActionEvent::class, SessionRevokedActionEvent::class,
        OtherSessionsRevokingActionEvent::class, OtherSessionsRevokedActionEvent::class,
        UserSessionsListingActionEvent::class, UserSessionsListedActionEvent::class,
        UserSessionsRevokingActionEvent::class, UserSessionsRevokedActionEvent::class,
        DevicesListingActionEvent::class, DevicesListedActionEvent::class,
        DeviceForgettingActionEvent::class, DeviceForgottenActionEvent::class,
    ] as $event) {
        Event::assertDispatched($event);
    }
});
