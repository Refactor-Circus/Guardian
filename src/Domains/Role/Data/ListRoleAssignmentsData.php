<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListRoleAssignmentsAction: the filters (a user by key, a role by
 * slug, a scope type, a scope id) and the page to show.
 */
final readonly class ListRoleAssignmentsData extends Data
{
    public function __construct(
        public ?string $user = null,
        public ?string $role = null,
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
            'user' => ['sometimes', 'nullable', 'regex:/^[\w\-]{1,64}$/'],
            'role' => ['sometimes', 'nullable', 'string', 'max:255'],
            'scope_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'scope_id' => ['sometimes', 'nullable', 'regex:/^[\w\-]{1,64}$/'],
        ] + self::pagination()->rules();
    }
}
