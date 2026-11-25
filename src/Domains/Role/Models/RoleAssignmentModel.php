<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Guardian\Support\Users;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * A role held by a user, globally (no scope) or in one scope: the scope
 * model's morph class and its key, as a string (D-005).
 *
 * @property string $id
 * @property string $role_id
 * @property int|string $user_id
 * @property string|null $scope_type
 * @property string|null $scope_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class RoleAssignmentModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['role_id', 'user_id', 'scope_type', 'scope_id'];

    public function getTable(): string
    {
        return Tables::name('role_assignments');
    }

    /**
     * @return BelongsTo<RoleModel, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RoleModel::class, 'role_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'user_id');
    }

    /**
     * The scope the role is held in; null for a global assignment.
     *
     * @return MorphTo<Model, $this>
     */
    public function scope(): MorphTo
    {
        return $this->morphTo('scope', 'scope_type', 'scope_id');
    }
}
