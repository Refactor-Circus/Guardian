<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\Session\Services\Sessions;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * One signed-in session, never with its key or the session id.
 *
 * @mixin SessionModel
 */
final class SessionResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'current' => $schema->boolean()->description('Whether this is the session the call was made from.')->required(),
            'ip' => $schema->string()->nullable()->required(),
            'user_agent' => $schema->string()->nullable()->required(),
            'device_id' => $schema->string()->nullable()->required(),
            'device_name' => $schema->string()->nullable()->required(),
            'last_active_at' => $schema->string()->required(),
            'created_at' => $schema->string()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $current = app(Sessions::class)->currentHash($request);

        return [
            'id' => $this->id,
            'current' => $current !== null && hash_equals($current, $this->key_hash),
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'device_id' => $this->device_id,
            'device_name' => $this->device?->name,
            'last_active_at' => $this->last_active_at->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
