<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Support\Authorization;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;

/**
 * Stops anyone handing out more than they hold (criterion 15, moved from
 * Roster): an actor may only grant permissions - by building a role or
 * assigning or revoking one - that they hold themselves in that scope, and
 * only super-admins touch a `super` role. Code calls without an actor are
 * trusted, as is everything while `guardian.authorization.enabled` is off.
 */
trait GuardsEscalation
{
    /**
     * @param  array<int, string>  $permissions
     *
     * @throws ValidationException naming the permissions the actor lacks
     */
    private function guardEscalation(?Model $actor, array $permissions, ?Model $scope, string $field): void
    {
        if (! $this->guarding($actor)) {
            return;
        }

        $checker = app(Permissions::class);
        $missing = $actor instanceof Authenticatable
            ? array_values(array_filter($permissions, fn (string $permission): bool => ! $checker->allows($actor, $permission, $scope)))
            : array_values($permissions);

        if ($missing !== []) {
            sort($missing);

            throw ValidationException::withMessages([
                $field => __('guardian::guardian.cannot_grant', ['permissions' => implode(', ', $missing)]),
            ]);
        }
    }

    /**
     * @throws ValidationException unless the actor is a super-admin
     */
    private function guardSuper(?Model $actor, bool $super, string $field): void
    {
        if (! $super || ! $this->guarding($actor)) {
            return;
        }

        if (! $actor instanceof Authenticatable || ! app(Permissions::class)->isSuperAdmin($actor)) {
            throw ValidationException::withMessages([$field => __('guardian::guardian.only_super_admins')]);
        }
    }

    private function guarding(?Model $actor): bool
    {
        return $actor !== null && app(Authorization::class)->enabled();
    }
}
