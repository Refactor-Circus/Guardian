<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Guardian\Domains\Role\Events\RoleAssignmentsListedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleAssignmentsListingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Actions\Action;

final class ListRoleAssignmentsAction extends Action
{
    public function __construct(private readonly Scopes $scopes) {}

    /**
     * Who holds which role where, oldest first, filtered by user, role
     * slug, scope type and scope id.
     *
     * @return LengthAwarePaginator<int, RoleAssignmentModel>
     */
    protected function handle(ListRoleAssignmentsData $filters = new ListRoleAssignmentsData): LengthAwarePaginator
    {
        RoleAssignmentsListingActionEvent::dispatch($filters);

        $query = RoleAssignmentModel::query()->with('role');

        if ($filters->user !== null) {
            $query->where('user_id', $filters->user);
        }

        if ($filters->role !== null) {
            $query->whereHas('role', fn (Builder $role): Builder => $role->where('slug', $filters->role));
        }

        if ($filters->scopeType !== null) {
            $query->where('scope_type', $this->scopes->morphType($filters->scopeType)
                ?? throw ValidationException::withMessages(['scope_type' => __('guardian::guardian.unknown_scope_type')]));
        }

        if ($filters->scopeId !== null) {
            $query->where('scope_id', $filters->scopeId);
        }

        $assignments = $query->oldest()->oldest('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        RoleAssignmentsListedActionEvent::dispatch($filters);

        return $assignments;
    }
}
