<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * A bundle of permissions users hold globally or in a scope (D-005).
 *
 * `scope` says where the role may be assigned: `global` (only with no
 * scope), `any` (anywhere), or a scope model's morph type (only in scopes of
 * that type). A role with an owner is that scope's own custom role, assigned
 * only in the owner itself. A `super` role allows everything.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $scope
 * @property string|null $owner_type
 * @property string|null $owner_id
 * @property string|null $description
 * @property bool $super
 * @property bool $system
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class RoleModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    /**
     * Assigned only with no scope.
     */
    public const string GLOBAL = 'global';

    /**
     * Assigned globally or in any scope.
     */
    public const string ANY = 'any';

    protected $fillable = ['name', 'slug', 'scope', 'owner_type', 'owner_id', 'description', 'super', 'system'];

    protected $attributes = [
        'super' => false,
        'system' => false,
    ];

    public function getTable(): string
    {
        return Tables::name('roles');
    }

    /**
     * @return BelongsToMany<PermissionModel, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionModel::class, Tables::name('permission_role'), 'role_id', 'permission_id');
    }

    /**
     * @return HasMany<RoleAssignmentModel, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(RoleAssignmentModel::class, 'role_id');
    }

    /**
     * The scope a custom role belongs to; null for a shared role.
     *
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('owner', 'owner_type', 'owner_id');
    }

    protected function casts(): array
    {
        return [
            'super' => 'boolean',
            'system' => 'boolean',
        ];
    }
}
