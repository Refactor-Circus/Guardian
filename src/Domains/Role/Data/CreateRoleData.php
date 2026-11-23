<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateRoleAction: the role, where it may be assigned (`scope`:
 * `global`, `any` or a scope type) and the permission names it grants.
 *
 * Without `slug` one is made from the name. With `scope_type` and
 * `scope_id` the role is that scope's own, assigned only there.
 */
final readonly class CreateRoleData extends Data
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public string $name,
        public string $scope = 'any',
        public ?string $slug = null,
        public ?string $scopeType = null,
        public ?string $scopeId = null,
        public ?string $description = null,
        public bool $super = false,
        public array $permissions = [],
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'super' => ['sometimes', 'boolean'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists(PermissionModel::class, 'name')],
        ] + Scopes::rules();
    }
}
