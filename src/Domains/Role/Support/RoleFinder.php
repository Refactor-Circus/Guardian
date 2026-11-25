<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Support\Scopes;

/**
 * Roles as the API and MCP tools name them: by slug, among the shared roles
 * and the named scope's own (D-033).
 */
final class RoleFinder
{
    public function __construct(private readonly Scopes $scopes) {}

    /**
     * The role with that slug seen from the scope: the scope's own first,
     * then a shared one (a `global` one before the others).
     *
     * @throws ModelNotFoundException<RoleModel>
     */
    public function find(string $slug, ?Model $scope = null): RoleModel
    {
        $type = $scope?->getMorphClass();
        $key = Scopes::key($scope);

        $role = RoleModel::query()
            ->where('slug', $slug)
            ->where(fn (Builder $owner): Builder => $scope === null
                ? $owner->whereNull('owner_type')
                : $owner->whereNull('owner_type')->orWhere(fn (Builder $own): Builder => $own->where('owner_type', $type)->where('owner_id', $key)))
            ->orderByRaw('case when owner_type is null then 1 else 0 end')
            ->orderByRaw('case when scope = ? then 0 else 1 end', [RoleModel::GLOBAL])
            ->orderBy('scope')
            ->first();

        if ($role === null) {
            throw (new ModelNotFoundException)->setModel(RoleModel::class, [$slug]);
        }

        return $role;
    }

    /**
     * The scope a custom role belongs to; null for a shared role.
     */
    public function owner(RoleModel $role): ?Model
    {
        return $this->scopes->find($role->owner_type, $role->owner_id);
    }

    /**
     * The role with that slug that may be assigned in the scope (the
     * scope's own first), as Keystone's `Roles::assign()` finds it; null
     * when there is none.
     */
    public function assignable(string $slug, ?Model $scope): ?RoleModel
    {
        $query = RoleModel::query()->with('permissions')->where('slug', $slug);

        if ($scope === null) {
            return $query->whereIn('scope', [RoleModel::GLOBAL, RoleModel::ANY])->whereNull('owner_type')->first();
        }

        return $query
            ->whereIn('scope', [RoleModel::ANY, $scope->getMorphClass()])
            ->where(fn (Builder $owner): Builder => $owner
                ->whereNull('owner_type')
                ->orWhere(fn (Builder $own): Builder => $own->where('owner_type', $scope->getMorphClass())->where('owner_id', Scopes::key($scope))))
            ->orderByRaw('case when owner_type is null then 1 else 0 end')
            ->first();
    }

    /**
     * Where a role may be assigned, as stored: `global`, `any` or a scope
     * model's morph type.
     *
     * @throws ValidationException for anything else
     */
    public function assignableIn(string $scope): string
    {
        if (in_array($scope, [RoleModel::GLOBAL, RoleModel::ANY], true)) {
            return $scope;
        }

        return $this->scopes->morphType($scope)
            ?? throw ValidationException::withMessages(['scope' => __('guardian::guardian.role_scope_unknown')]);
    }
}
