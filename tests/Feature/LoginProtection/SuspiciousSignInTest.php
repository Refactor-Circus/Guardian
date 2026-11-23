<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Notification;
use RefactorCircus\Guardian\Domains\LoginProtection\Notifications\SuspiciousSignInNotification;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\Session\Notifications\NewDeviceSignInNotification;

// Criterion 51: suspicious sign-ins, never blocked (D-016).

beforeEach(function (): void {
    Notification::fake();
    withLoginRoutes();
});

it('flags a new device on a network never seen for the user', function (): void {
    $ada = user(['email' => 'ada@example.com']);
    signInFrom($ada, 'Laptop', '203.0.113.7');

    signInFrom($ada, 'Phone', '198.51.100.20');

    $event = SecurityEventModel::query()->where('type', 'login.suspicious')->sole();
    expect((string) $event->user_id)->toBe((string) $ada->getKey())
        ->and($event->context)->toMatchArray(['new_network' => true, 'recent_failures' => 0]);

    Notification::assertSentTo($ada, SuspiciousSignInNotification::class);
    Notification::assertNotSentTo($ada, NewDeviceSignInNotification::class);
    expect(SecurityEventModel::query()->where('type', 'device.new')->count())->toBe(1);
});

it('flags a new device after three recent failures, on a known network', function (): void {
    $ada = user(['email' => 'ada@example.com', 'password' => 'the right passphrase']);
    signInFrom($ada, 'Laptop', '203.0.113.7');

    foreach (range(1, 3) as $failure) {
        attempt('ada@example.com', ip: '203.0.113.50', userAgent: 'Phone');
    }

    attempt('ada@example.com', 'the right passphrase', '203.0.113.50', 'Phone')->assertOk();

    expect(SecurityEventModel::query()->where('type', 'login.suspicious')->sole()->context)->toMatchArray(['new_network' => false, 'recent_failures' => 3]);
    Notification::assertSentTo($ada, SuspiciousSignInNotification::class, function (SuspiciousSignInNotification $notification) use ($ada): bool {
        return $notification->via($ada) === ['mail'] && str_contains(implode(' ', $notification->toMail($ada)->introLines), '203.0.113.50');
    });
});

it('forgets failures older than an hour', function (): void {
    $ada = user(['email' => 'ada@example.com']);
    signInFrom($ada, 'Laptop', '203.0.113.7');

    foreach (range(1, 3) as $failure) {
        attempt('ada@example.com', ip: '203.0.113.50');
    }

    $this->travel(61)->minutes();
    signInFrom($ada, 'Phone', '203.0.113.50');

    expect(SecurityEventModel::query()->where('type', 'login.suspicious')->count())->toBe(0);
    Notification::assertSentTo($ada, NewDeviceSignInNotification::class);
});

it('sends the new-device notice when neither signal is there', function (): void {
    $ada = user(['email' => 'ada@example.com']);
    signInFrom($ada, 'Laptop', '203.0.113.7');

    signInFrom($ada, 'Phone', '203.0.113.99');

    expect(SecurityEventModel::query()->where('type', 'login.suspicious')->count())->toBe(0);
    Notification::assertSentTo($ada, NewDeviceSignInNotification::class);
    Notification::assertNotSentTo($ada, SuspiciousSignInNotification::class);
});

it('never flags a known device, nor the first one', function (): void {
    $ada = user(['email' => 'ada@example.com']);

    signInFrom($ada, 'Laptop', '203.0.113.7');
    signInFrom($ada, 'Laptop', '198.51.100.20');

    expect(SecurityEventModel::query()->where('type', 'login.suspicious')->count())->toBe(0);
    Notification::assertNothingSent();
});

it('compares IPv6 networks by their /48', function (): void {
    $ada = user(['email' => 'ada@example.com']);
    signInFrom($ada, 'Laptop', '2001:db8:aaaa:1::1');

    signInFrom($ada, 'Phone', '2001:db8:aaaa:ffff::2');
    expect(SecurityEventModel::query()->where('type', 'login.suspicious')->count())->toBe(0);

    signInFrom($ada, 'Tablet', '2001:db8:bbbb::3');
    expect(SecurityEventModel::query()->where('type', 'login.suspicious')->count())->toBe(1);
});

it('never blocks the sign-in', function (): void {
    $ada = user(['email' => 'ada@example.com', 'password' => 'the right passphrase']);
    signInFrom($ada, 'Laptop', '203.0.113.7');

    attempt('ada@example.com', 'the right passphrase', '198.51.100.20', 'Phone')->assertOk();
    $this->get('test-tracked')->assertOk();

    expect(SecurityEventModel::query()->where('type', 'login.suspicious')->count())->toBe(1);
});

it('sends the suspicious notice even with the new-device notice off', function (): void {
    config()->set('guardian.devices.notify', false);
    $ada = user(['email' => 'ada@example.com']);
    signInFrom($ada, 'Laptop', '203.0.113.7');

    signInFrom($ada, 'Phone', '198.51.100.20');

    Notification::assertSentTo($ada, SuspiciousSignInNotification::class);
});
