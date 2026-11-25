<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * A named ability roles grant, such as `guardian.roles.view` or a host's
 * `invoices.edit`. A `system` permission mirrors a Keystone `Abilities`
 * entry and is created by `guardian:sync-permissions`.
 *
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property bool $system
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class PermissionModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['name', 'description', 'system'];

    protected $attributes = [
        'system' => false,
    ];

    public function getTable(): string
    {
        return Tables::name('permissions');
    }

    /**
     * @return BelongsToMany<RoleModel, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, Tables::name('permission_role'), 'permission_id', 'role_id');
    }

    protected function casts(): array
    {
        return ['system' => 'boolean'];
    }
}
