<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;

/**
 * The scopes Guardian's API and MCP tools name as `scope_type` (a morph
 * alias, or an Eloquent model class) and `scope_id` (its route key, such as
 * an organization's slug, or else its key), with no knowledge of what they
 * are: organization and team semantics come only from the scope model's
 * Keystone `Scope` (D-005, D-033). Assignments, roles and policies store the
 * scope's key (D-051).
 */
final class Scopes
{
    /**
     * Validation rules for a named scope: both or neither.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'scope_type' => ['sometimes', 'nullable', 'string', 'max:255', 'required_with:scope_id'],
            'scope_id' => ['sometimes', 'nullable', 'required_with:scope_type', 'regex:/^[\w\-]{1,64}$/'],
        ];
    }

    /**
     * The model a type names: a morph alias, or an Eloquent model class.
     *
     * @return class-string<Model>|null
     */
    public function modelClass(string $type): ?string
    {
        $class = Relation::getMorphedModel($type) ?? $type;

        return class_exists($class) && is_subclass_of($class, Model::class) ? $class : null;
    }

    /**
     * The type as assignments store it (the model's morph class); null for
     * an unknown type.
     */
    public function morphType(string $type): ?string
    {
        $class = $this->modelClass($type);

        return $class === null ? null : (new $class)->getMorphClass();
    }

    /**
     * The scope a type and key name; null when neither is given.
     *
     * @throws ValidationException for an unknown type or a missing record
     */
    public function resolve(mixed $type, mixed $id, string $field = 'scope_type'): ?Model
    {
        if (($type === null || $type === '') && ($id === null || $id === '')) {
            return null;
        }

        if (! is_scalar($id) || $id === '') {
            throw ValidationException::withMessages(['scope_id' => __('validation.required_with', ['attribute' => 'scope id', 'values' => 'scope type'])]);
        }

        $class = is_string($type) ? $this->modelClass($type) : null;

        if ($class === null) {
            throw ValidationException::withMessages([$field => __('guardian::guardian.unknown_scope_type')]);
        }

        $scope = self::named($class, (string) $id);

        if (! $scope instanceof Model) {
            throw ValidationException::withMessages([$field => __('guardian::guardian.unknown_scope')]);
        }

        return $scope;
    }

    /**
     * The key assignments store for the scope a type and identifier name (its
     * route key or key), quietly: the identifier as given when the type or
     * the record is unknown, so a filter on it matches what was stored.
     */
    public function storedKey(string $type, string $id): string
    {
        $class = $this->modelClass($type);

        return self::key($class === null ? null : self::named($class, $id)) ?? $id;
    }

    /**
     * The scope of a model class an identifier names: by its route key (what
     * a screen passes as the record, D-051), else by its key.
     *
     * @param  class-string<Model>  $class
     */
    private static function named(string $class, string $id): ?Model
    {
        $model = new $class;

        if ($model->getRouteKeyName() !== $model->getKeyName()) {
            $scope = $model->newQuery()->where($model->qualifyColumn($model->getRouteKeyName()), $id)->first();

            if ($scope instanceof Model) {
                return $scope;
            }
        }

        return $model->newQuery()->whereKey($id)->first();
    }

    /**
     * The stored scope a type and key name, quietly: null for none, an
     * unknown type or a record that is gone.
     */
    public function find(?string $type, ?string $id): ?Model
    {
        $class = $type === null || $id === null ? null : $this->modelClass($type);

        return $class === null ? null : (new $class)->newQuery()->whereKey($id)->first();
    }

    /**
     * A scope's key as assignments store it.
     */
    public static function key(?Model $scope): ?string
    {
        if ($scope === null) {
            return null;
        }

        $key = $scope->getKey();

        return is_scalar($key) ? (string) $key : null;
    }
}
