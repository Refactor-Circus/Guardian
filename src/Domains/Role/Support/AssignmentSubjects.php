<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Support;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Guardian\Support\Users;

/**
 * Loads the users and scopes a page of assignments names, for their labels
 * (D-053): one query for the users and one per scope type, whatever the
 * page's size. A scope type no model answers to (a class since removed) and
 * a record that is gone load as null instead of failing, as `morphTo` would.
 */
final readonly class AssignmentSubjects
{
    public function __construct(
        private Users $users,
        private Scopes $scopes,
    ) {}

    /**
     * @param  iterable<RoleAssignmentModel>  $assignments
     */
    public function load(iterable $assignments): void
    {
        $assignments = collect($assignments);

        $pending = $assignments->reject(fn (RoleAssignmentModel $assignment): bool => $assignment->relationLoaded('user'));
        $users = $pending->isEmpty() ? [] : $this->find($this->users->model(), $pending->map(fn (RoleAssignmentModel $assignment): string => (string) $assignment->user_id)->all());

        foreach ($pending as $assignment) {
            $assignment->setRelation('user', $users[(string) $assignment->user_id] ?? null);
        }

        $scoped = $assignments->reject(fn (RoleAssignmentModel $assignment): bool => $assignment->relationLoaded('scope'));

        foreach ($scoped->groupBy(fn (RoleAssignmentModel $assignment): string => (string) $assignment->scope_type) as $type => $group) {
            $class = $type === '' ? null : $this->scopes->modelClass((string) $type);
            $scopes = $class === null ? [] : $this->find($class, $group->map(fn (RoleAssignmentModel $assignment): string => (string) $assignment->scope_id)->all());

            foreach ($group as $assignment) {
                $assignment->setRelation('scope', $scopes[(string) $assignment->scope_id] ?? null);
            }
        }
    }

    /**
     * The records of a model class with these keys, by key.
     *
     * @param  class-string<Model>  $class
     * @param  list<string>|array<int, string>  $keys
     * @return array<string, Model>
     */
    private function find(string $class, array $keys): array
    {
        $keys = array_values(array_unique(array_filter($keys, fn (string $key): bool => $key !== '')));

        if ($keys === []) {
            return [];
        }

        $found = [];

        foreach ((new $class)->newQuery()->whereKey($keys)->get() as $model) {
            $key = $model->getKey();

            if (is_scalar($key)) {
                $found[(string) $key] = $model;
            }
        }

        return $found;
    }
}
