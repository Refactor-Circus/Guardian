<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\Role\Data\UserAbilities;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * The abilities a user holds in a scope.
 *
 * @property UserAbilities $resource
 */
final class UserAbilitiesResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->string()->required(),
            'scope_type' => $schema->string()->nullable()->required(),
            'scope_id' => $schema->string()->nullable()->required(),
            'super_admin' => $schema->boolean()->required(),
            'abilities' => $schema->array()->items($schema->string())->nullable()->description('Sorted; null when the role store cannot list them.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->resource->user,
            'scope_type' => $this->resource->scopeType,
            'scope_id' => $this->resource->scopeId,
            'super_admin' => $this->resource->superAdmin,
            'abilities' => $this->resource->abilities,
        ];
    }
}
