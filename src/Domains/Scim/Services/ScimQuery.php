<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Services;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Grammar;

/**
 * SCIM filter comparisons, case-insensitive as SCIM strings are by default.
 */
final class ScimQuery
{
    /**
     * `eq` is exact and index-friendly (see equals()), so `_` or `%` in a
     * value can never match a different account.
     * `co` and `sw` are searches; wildcards in them only widen the results.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public static function compare(Builder $builder, string $column, string $operator, string $value): Builder
    {
        if ($operator === 'co') {
            return $builder->whereLike($column, '%'.$value.'%');
        }

        if ($operator === 'sw') {
            return $builder->whereLike($column, $value.'%');
        }

        return self::equals($builder, $column, $value);
    }

    /**
     * An exact, case-insensitive comparison that stays index-friendly on each
     * database.
     *
     * PostgreSQL compares `lower(column) = ?`, which an expression index on
     * `lower(column)` serves (an ILIKE never uses a plain index). MySQL and
     * SQLite compare with a wildcard-free LIKE, which MySQL's case-insensitive
     * collations answer from the column's own index.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    private static function equals(Builder $builder, string $column, string $value): Builder
    {
        if ($builder->getModel()->getConnection()->getDriverName() === 'pgsql') {
            // `lower(column)`, wrapped by the query's own grammar.
            $lower = new readonly class($column) implements Expression
            {
                public function __construct(private string $column) {}

                public function getValue(Grammar $grammar): string
                {
                    return 'lower('.$grammar->wrap($this->column).')';
                }
            };

            return $builder->where($lower, mb_strtolower($value));
        }

        // Without LIKE wildcards in the value, a LIKE is an exact match.
        if (strpbrk($value, '%_') === false) {
            return $builder->whereLike($column, $value);
        }

        // `%` or `_` would widen the LIKE (`a_b@x` matching `axb@x`), so take
        // the LIKE's matches and keep only the exact ones.
        $model = $builder->getModel();

        $keys = $model->newQuery()
            ->whereLike($column, $value)
            ->get()
            ->filter(fn (Model $candidate): bool => strcasecmp((string) $candidate->getAttribute($column), $value) === 0)
            ->modelKeys();

        return $builder->whereIn($model->getQualifiedKeyName(), $keys);
    }
}
