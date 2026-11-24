<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Support\AssignmentSubjects;
use RefactorCircus\Guardian\Support\Labels;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * Who holds which role where. `id` names the assignment to revoke;
 * `user_label` and `scope_label` name the user and the scope for people to
 * read (`Support\Labels`, D-053), null when nothing names them.
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
            'user_label' => $schema->string()->nullable()->description('What people call the user (its name or email); null when nothing names it or the user is gone.')->required(),
            'role' => $schema->object([
                'slug' => $schema->string()->required(),
                'name' => $schema->string()->required(),
                'scope' => $schema->string()->required(),
                'super' => $schema->boolean()->required(),
            ])->nullable()->required(),
            'scope_type' => $schema->string()->nullable()->description('Null for a global assignment.')->required(),
            'scope_id' => $schema->string()->nullable()->required(),
            'scope_label' => $schema->string()->nullable()->description('What people call the scope (its name); null for a global assignment, or when nothing names it or it is gone.')->required(),
            'assigned_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->role;

        if (! $this->resource->relationLoaded('user') || ! $this->resource->relationLoaded('scope')) {
            app(AssignmentSubjects::class)->load([$this->resource]);
        }

        return [
            'id' => $this->id,
            'user_id' => (string) $this->user_id,
            'user_label' => self::label($this->resource->getRelation('user')),
            'role' => $role === null ? null : [
                'slug' => $role->slug,
                'name' => $role->name,
                'scope' => $role->scope,
                'super' => $role->super,
            ],
            'scope_type' => $this->scope_type,
            'scope_id' => $this->scope_id,
            'scope_label' => $this->scope_type === null ? null : self::label($this->resource->getRelation('scope')),
            'assigned_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * What people call a user or scope; null when nothing names it.
     */
    private static function label(mixed $model): ?string
    {
        return app(Labels::class)->for($model instanceof Model ? $model : null);
    }
}
