<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/SecurityEvent/helpers.php';
require_once dirname(__DIR__).'/Permission/helpers.php';

use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;

// Criterion 25: the security log over HTTP, by route name.

it('lists and shows events over HTTP', function (): void {
    $ada = user();
    $event = recordSecurityEvent(SecurityEventType::LoginFailed, $ada, 'ada@example.com', ['guard' => 'web']);
    recordSecurityEvent(SecurityEventType::Logout);

    $this->getJson(route('guardian.security-events.index', ['type' => 'login.failed']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $event->id)
        ->assertJsonPath('data.0.user_id', (string) $ada->getKey())
        ->assertJsonPath('data.0.context.guard', 'web')
        ->assertJsonPath('meta.per_page', 50);

    $this->getJson(route('guardian.security-events.show', $event->id))->assertOk()->assertJsonPath('data.type', 'login.failed');
    $this->getJson(route('guardian.security-events.show', 'missing'))->assertNotFound();
    $this->getJson(route('guardian.security-events.index', ['type' => 'nope']))->assertUnprocessable()->assertJsonValidationErrors('type');
});

describe('authorization', function (): void {
    beforeEach(fn () => config(['guardian.authorization.enabled' => true]));

    it('needs guardian.security.view for the log, and lets everyone read their own', function (): void {
        $ada = user();
        $mine = recordSecurityEvent(SecurityEventType::LoginSucceeded, $ada);
        $theirs = recordSecurityEvent(SecurityEventType::LoginSucceeded, user());

        $this->getJson(route('guardian.security-events.index'))->assertUnauthorized();
        $this->getJson(route('guardian.me.security-events.index'))->assertUnauthorized();

        $this->actingAs($ada);

        $this->getJson(route('guardian.security-events.index'))->assertForbidden();
        $this->getJson(route('guardian.security-events.show', $theirs->id))->assertForbidden();
        $this->getJson(route('guardian.security-events.show', $mine->id))->assertOk();
        $this->getJson(route('guardian.me.security-events.index', ['user' => (string) $theirs->user_id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);

        $auditor = user();
        assignDirectly($auditor, roleGranting('auditor', ['guardian.security.view']));

        $this->actingAs($auditor)->getJson(route('guardian.security-events.index'))->assertOk()->assertJsonCount(2, 'data');
    });
});
