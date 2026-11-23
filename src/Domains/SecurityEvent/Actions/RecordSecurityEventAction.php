<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\RecordSecurityEventData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventRecordedActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventRecordingActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\SecurityEvent\Support\IdentifierHash;
use RefactorCircus\Guardian\Domains\SecurityEvent\Support\SecretlessContext;
use RefactorCircus\Keystone\Actions\Action;

final class RecordSecurityEventAction extends Action
{
    /**
     * The only writer of the security log (criterion 23, D-019): the
     * identifier is stored as an HMAC, the user agent cut to 512
     * characters, and the context stripped of secrets. Its finished event
     * is what an installed audit log (Keen) records.
     */
    protected function handle(RecordSecurityEventData $data): SecurityEventModel
    {
        SecurityEventRecordingActionEvent::dispatch($data->type, $data->user);

        $event = DB::transaction(fn (): SecurityEventModel => SecurityEventModel::query()->create([
            'type' => $data->type,
            'user_id' => $data->user,
            'identifier_hash' => $data->identifier === null || $data->identifier === '' ? null : IdentifierHash::of($data->identifier),
            'ip' => $data->ip,
            'user_agent' => $data->userAgent === null ? null : Str::limit($data->userAgent, 512, ''),
            'context' => SecretlessContext::of($data->context),
            'occurred_at' => now(),
        ]));

        SecurityEventRecordedActionEvent::dispatch($event);

        return $event;
    }
}
