<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\Session\Http\Middleware\TrackSession;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\Session\Notifications\NewDeviceSignInNotification;
use RefactorCircus\Guardian\Domains\Session\Services\SessionKeys;

// Criteria 36 and 37: the guardian.session middleware and the sign-in listener.

beforeEach(function (): void {
    $this->travelTo(now()->startOfMinute()->addSeconds(10));
    Notification::fake();
    withTrackedSessions();
});

it('registers the guardian.session alias', function (): void {
    expect(Route::getMiddleware()['guardian.session'] ?? null)->toBe(TrackSession::class);
});

it('records the device and the session on sign-in, keyed by a hash of a random key', function (): void {
    $ada = user();

    signInFrom($ada);

    $device = DeviceModel::query()->sole();
    $session = SessionModel::query()->sole();
    $key = session(SessionKeys::KEY);

    expect($device->user_id)->toBe($ada->getKey())
        ->and($device->fingerprint_hash)->toBe(hash_hmac('sha256', 'Mozilla/5.0 (Macintosh) Firefox/130.0', 'base64:'.base64_encode(str_repeat('a', 32))))
        ->and($device->last_ip)->toBe('203.0.113.7')
        ->and($session->user_id)->toBe($ada->getKey())
        ->and($session->device_id)->toBe($device->id)
        ->and($session->ip)->toBe('203.0.113.7')
        ->and($session->user_agent)->toBe('Mozilla/5.0 (Macintosh) Firefox/130.0')
        ->and($key)->toBeString()->toHaveLength(64)
        ->and($session->key_hash)->toBe(app(SessionKeys::class)->hash($key))
        ->and($session->key_hash)->not->toBe($key)
        ->and($session->key_hash)->not->toBe(session()->getId());
});

it('keeps the same record when the session id is regenerated', function (): void {
    $ada = user();
    signInFrom($ada);
    $record = SessionModel::query()->sole();
    $id = session()->getId();

    $this->post('test-regenerate')->assertOk();
    $this->get('test-tracked')->assertOk();

    expect(session()->getId())->not->toBe($id)
        ->and(SessionModel::query()->sole()->id)->toBe($record->id);
});

it('touches last_active_at at most once per touch_seconds', function (): void {
    $ada = user();
    signInFrom($ada);
    $started = SessionModel::query()->sole()->last_active_at;

    $this->travel(30)->seconds();
    $this->get('test-tracked')->assertOk();
    expect(SessionModel::query()->sole()->last_active_at->equalTo($started))->toBeTrue();

    $this->travel(31)->seconds();
    $this->get('test-tracked')->assertOk();
    expect(SessionModel::query()->sole()->last_active_at->equalTo(now()))->toBeTrue();

    config()->set('guardian.sessions.touch_seconds', 5);
    $this->travel(6)->seconds();
    $this->get('test-tracked')->assertOk();
    expect(SessionModel::query()->sole()->last_active_at->equalTo(now()))->toBeTrue();
});

it('signs a revoked session out with 401 session_revoked', function (): void {
    $ada = user();
    signInFrom($ada);
    SessionModel::query()->sole()->forceFill(['revoked_at' => now()])->save();

    $this->getJson('test-tracked')->assertUnauthorized()->assertJson(['code' => 'session_revoked']);

    expect(Auth::guard('web')->check())->toBeFalse()
        ->and(session(SessionKeys::KEY))->toBeNull();
});

it('starts a record for a session signed in before tracking began', function (): void {
    $ada = user();
    $this->actingAs($ada);

    $this->get('test-tracked')->assertOk();

    expect(SessionModel::query()->where('user_id', $ada->getKey())->count())->toBe(1);
});

it('passes guests and requests without a session', function (): void {
    Route::middleware('guardian.session')->get('test-open', fn (): string => 'open');

    $this->get('test-open')->assertOk();
    $this->actingAs(user())->get('test-open')->assertOk();

    expect(SessionModel::query()->count())->toBe(0);
});

it('ends the record on sign-out', function (): void {
    $ada = user();
    signInFrom($ada);

    Route::middleware('web')->post('test-logout', function (): string {
        Auth::guard('web')->logout();

        return 'out';
    });
    $this->post('test-logout')->assertOk();

    expect(SessionModel::query()->sole()->revoked_at)->not->toBeNull()
        ->and(SecurityEventModel::query()->where('type', 'session.revoked')->count())->toBe(0);
});

it('does not announce the first device, nor a known one', function (): void {
    $ada = user();

    signInFrom($ada);
    signInFrom($ada);

    Notification::assertNothingSent();
    expect(DeviceModel::query()->count())->toBe(1)
        ->and(SessionModel::query()->count())->toBe(2)
        ->and(SecurityEventModel::query()->where('type', 'device.new')->count())->toBe(0);
});

it('announces a new device and records device.new', function (): void {
    $ada = user();
    signInFrom($ada);

    // The same network, so the sign-in is not suspicious (criterion 51).
    signInFrom($ada, 'Mozilla/5.0 (iPhone) Safari/18.0', '203.0.113.20');

    Notification::assertSentTo($ada, NewDeviceSignInNotification::class, function (NewDeviceSignInNotification $notification) use ($ada): bool {
        $mail = $notification->toMail($ada);

        return $notification->via($ada) === ['mail']
            && str_contains(implode(' ', $mail->introLines), '203.0.113.20');
    });

    $event = SecurityEventModel::query()->where('type', 'device.new')->sole();
    expect((string) $event->user_id)->toBe((string) $ada->getKey())
        ->and($event->context)->toHaveKey('device')
        ->and(DeviceModel::query()->count())->toBe(2);
});

it('sends the notice through guardian.devices.notify_via, or not at all', function (): void {
    $ada = user();
    signInFrom($ada);

    config()->set('guardian.devices.notify_via', ['database']);
    signInFrom($ada, 'Second');
    Notification::assertSentTo($ada, NewDeviceSignInNotification::class, fn (NewDeviceSignInNotification $notification): bool => $notification->via($ada) === ['database']);

    config()->set('guardian.devices.notify', false);
    signInFrom($ada, 'Third');
    Notification::assertSentToTimes($ada, NewDeviceSignInNotification::class, 1);
    expect(SecurityEventModel::query()->where('type', 'device.new')->count())->toBe(2);
});
