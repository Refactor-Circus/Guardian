<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Role\Data\ListUserAbilitiesData;
use RefactorCircus\Guardian\Domains\Role\Data\UserAbilities;
use RefactorCircus\Guardian\Domains\Role\Events\UserAbilitiesListedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\UserAbilitiesListingActionEvent;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Actions\Action;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;

final class ListUserAbilitiesAction extends Action
{
    public function __construct(
        private readonly Scopes $scopes,
        private readonly Permissions $permissions,
    ) {}

    /**
     * The abilities a user holds in a scope (or globally), as Keystone's
     * bound `Permissions` answers: every known one for a super-admin.
     */
    protected function handle(Model $user, ListUserAbilitiesData $filters = new ListUserAbilitiesData): UserAbilities
    {
        UserAbilitiesListingActionEvent::dispatch($user, $filters);

        $scope = $this->scopes->resolve($filters->scopeType, $filters->scopeId);
        $authenticatable = $user instanceof Authenticatable ? $user : null;
        $key = $user->getKey();

        $abilities = new UserAbilities(
            user: is_scalar($key) ? (string) $key : '',
            scopeType: $scope?->getMorphClass(),
            scopeId: Scopes::key($scope),
            superAdmin: $this->permissions->isSuperAdmin($authenticatable),
            abilities: $this->permissions->abilities($authenticatable, $scope),
        );

        UserAbilitiesListedActionEvent::dispatch($user, $abilities);

        return $abilities;
    }
}
