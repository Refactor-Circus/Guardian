<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Data;

use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListRolesAction: filters and the page to show. With
 * `scope_type` and `scope_id`, the roles usable there: the shared ones plus
 * that scope's own. `scope` keeps only roles assignable as it says
 * (`global`, `any` or a scope type).
 */
final readonly class ListRolesData extends Data
{
    public function __construct(
        public ?string $scope = null,
        public ?string $scopeType = null,
        public ?string $scopeId = null,
        public int $perPage = 50,
        public ?int $page = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::pages(default: 50);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'scope' => ['sometimes', 'nullable', 'string', 'max:255'],
        ] + Scopes::rules() + self::pagination()->rules();
    }
}
