<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * A role as the API and MCP tools show it, named by its slug.
 *
 * @mixin RoleModel
 */
final class RoleResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->required(),
            'name' => $schema->string()->required(),
            'scope' => $schema->string()->description('Where it may be assigned: global, any, or a scope type.')->required(),
            'owner_type' => $schema->string()->nullable()->description('The scope type a custom role belongs to.')->required(),
            'owner_id' => $schema->string()->nullable()->description('The scope a custom role belongs to.')->required(),
            'description' => $schema->string()->nullable()->required(),
            'super' => $schema->boolean()->required(),
            'system' => $schema->boolean()->required(),
            'permissions' => $schema->array()->items($schema->string())->description('Permission names, when loaded.'),
            'created_at' => $schema->string()->nullable()->required(),
            'updated_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'scope' => $this->scope,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'description' => $this->description,
            'super' => $this->super,
            'system' => $this->system,
            'permissions' => $this->whenLoaded('permissions', fn (): array => $this->permissions
                ->map(fn (PermissionModel $permission): string => $permission->name)
                ->sort()
                ->values()
                ->all()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
