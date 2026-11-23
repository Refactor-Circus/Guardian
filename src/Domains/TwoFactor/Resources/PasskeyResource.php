<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * One passkey: never its credential id or public key.
 *
 * @mixin PasskeyModel
 */
final class PasskeyResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'label' => $schema->string()->nullable()->required(),
            'transports' => $schema->array()->items($schema->string())->required(),
            'aaguid' => $schema->string()->nullable()->description('The authenticator model\'s id, when it gave one.')->required(),
            'last_used_at' => $schema->string()->nullable()->required(),
            'created_at' => $schema->string()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'transports' => $this->transports ?? [],
            'aaguid' => $this->aaguid,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
