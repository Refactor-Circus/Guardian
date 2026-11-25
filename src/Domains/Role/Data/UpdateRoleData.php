<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for UpdateRoleAction: the fields to change. A given `permissions`
 * list replaces the role's; fields left out are kept.
 */
final readonly class UpdateRoleData extends Data
{
    /**
     * @param  array<int, string>|Missing  $permissions
     */
    public function __construct(
        public string|Missing $name = new Missing,
        public string|Missing|null $description = new Missing,
        public array|Missing $permissions = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists(PermissionModel::class, 'name')],
        ];
    }
}
