<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\RecordSecurityEventAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\RecordSecurityEventData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use Workbench\App\Models\User;

/**
 * Record one event through the action, as Guardian's domains do.
 *
 * @param  array<string, mixed>  $context
 */
function recordSecurityEvent(SecurityEventType $type, ?User $user = null, ?string $identifier = null, array $context = []): SecurityEventModel
{
    return app(RecordSecurityEventAction::class)->execute(new RecordSecurityEventData(
        type: $type,
        user: $user === null ? null : (string) $user->getKey(),
        identifier: $identifier,
        ip: '203.0.113.7',
        userAgent: 'Mozilla/5.0',
        context: $context,
    ));
}
