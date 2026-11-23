<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * One security event. Nothing secret was stored, so nothing is withheld.
 *
 * @mixin SecurityEventModel
 */
final class SecurityEventResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'type' => $schema->string()->enum(SecurityEventType::values())->required(),
            'user_id' => $schema->string()->nullable()->required(),
            'identifier_hash' => $schema->string()->nullable()->description('HMAC of the sign-in identifier typed, to match attempts at the same one.')->required(),
            'ip' => $schema->string()->nullable()->required(),
            'user_agent' => $schema->string()->nullable()->required(),
            'context' => $schema->object()->required(),
            'occurred_at' => $schema->string()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'user_id' => $this->user_id === null ? null : (string) $this->user_id,
            'identifier_hash' => $this->identifier_hash,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            /** @var array<string, mixed> */
            'context' => (object) $this->context,
            'occurred_at' => $this->occurred_at->toIso8601String(),
        ];
    }
}
