<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * Who holds which role where. `id` names the assignment to revoke.
 *
 * @mixin RoleAssignmentModel
 */
final class RoleAssignmentResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'user_id' => $schema->string()->required(),
            'role' => $schema->object([
                'slug' => $schema->string()->required(),
                'name' => $schema->string()->required(),
                'scope' => $schema->string()->required(),
                'super' => $schema->boolean()->required(),
            ])->nullable()->required(),
            'scope_type' => $schema->string()->nullable()->description('Null for a global assignment.')->required(),
            'scope_id' => $schema->string()->nullable()->required(),
            'assigned_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->role;

        return [
            'id' => $this->id,
            'user_id' => (string) $this->user_id,
            'role' => $role === null ? null : [
                'slug' => $role->slug,
                'name' => $role->name,
                'scope' => $role->scope,
                'super' => $role->super,
            ],
            'scope_type' => $this->scope_type,
            'scope_id' => $this->scope_id,
            'assigned_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
