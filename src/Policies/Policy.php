<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Policies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use RefactorCircus\Guardian\Support\Authorization;
use RefactorCircus\Keystone\Authorization\Contracts\Scope;
use RefactorCircus\Keystone\Policies\Policy as KeystonePolicy;

/**
 * Shared checks for Guardian's bundled policies.
 *
 * Policies are registered from `guardian.policies`, so an application swaps
 * one by pointing its model at another class there. The bundled policies
 * answer with Guardian's abilities (`guardian.sso.view`, `guardian.sso.manage`,
 * `guardian.scim.manage`, ...) through Keystone's bound `Permissions`: a
 * method asks whether the user may use the ability in the scope the subject
 * is, or belongs to (its team, else its organization). HTTP and MCP therefore
 * make the same check.
 *
 * Guardian's API and MCP tools reach a policy with the model's class, the
 * scope the call is about, and the user the call is about when everyone may
 * do it to themselves:
 *
 *     Gate::forUser($actor)->allows('manage', [SsoConnectionModel::class, $organization, null]);
 */
abstract class Policy extends KeystonePolicy
{
    /**
     * Whether the user may use an ability where the subject lives.
     */
    protected function permits(?Model $user, string $ability, ?Model $subject = null, ?Model $self = null): bool
    {
        return app(Authorization::class)->permits($user, $ability, $this->scope($subject), $self);
    }

    /**
     * The scope a subject is, or belongs to; null for global. A Keystone
     * `Scope` is its own scope; any other record is checked in its `team`,
     * else its `organization` (the narrowest first: a team check also counts
     * the team's organization, through the team's `parentScope()`).
     */
    protected function scope(?Model $subject): ?Model
    {
        if ($subject === null || $subject instanceof Scope) {
            return $subject;
        }

        foreach (['team', 'organization'] as $relation) {
            $parent = $this->parent($subject, $relation);

            if ($parent !== null) {
                return $parent;
            }
        }

        return null;
    }

    /**
     * A record's related scope: the loaded relation when there is one, else
     * looked up through the relation.
     */
    private function parent(Model $subject, string $relation): ?Model
    {
        if ($subject->relationLoaded($relation)) {
            $parent = $subject->getRelation($relation);

            return $parent instanceof Model ? $parent : null;
        }

        if (! method_exists($subject, $relation)) {
            return null;
        }

        $query = $subject->{$relation}();
        $parent = $query instanceof Relation ? $query->getResults() : null;

        return $parent instanceof Model ? $parent : null;
    }
}
