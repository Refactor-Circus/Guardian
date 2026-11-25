<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\Token\Data\TokenSummary;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * One API token, never with its value.
 *
 * @mixin TokenSummary
 */
final class TokenResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'name' => $schema->string()->required(),
            'abilities' => $schema->array()->items($schema->string())->required(),
            'last_used_at' => $schema->string()->nullable()->required(),
            'expires_at' => $schema->string()->nullable()->required(),
            'created_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * The tools' output for a list of tokens.
     *
     * @return array<string, Type>
     */
    public static function listSchema(JsonSchema $schema): array
    {
        return ['data' => $schema->array()->items($schema->object(self::schema($schema)))->required()];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'abilities' => $this->abilities,
            'last_used_at' => $this->lastUsedAt?->toIso8601String(),
            'expires_at' => $this->expiresAt?->toIso8601String(),
            'created_at' => $this->createdAt?->toIso8601String(),
        ];
    }
}
