<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Data;

use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for AssignRoleAction: the user (by key), the role (by slug) and the
 * scope to hold it in (`scope_type` and `scope_id`; neither for global).
 */
final readonly class AssignRoleData extends Data
{
    public function __construct(
        public string $user,
        public string $role,
        public ?string $scopeType = null,
        public ?string $scopeId = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'user' => ['required', 'regex:/^[\w\-]{1,64}$/'],
            'role' => ['required', 'string', 'max:255'],
        ] + Scopes::rules();
    }
}
