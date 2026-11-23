<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateTwoFactorPolicyAction: whom it covers (a scope by
 * `scope_type`/`scope_id`, a role by slug, both, or neither for everyone),
 * whether it requires two-factor, the methods that count (null: any) and the
 * days of grace.
 */
final readonly class CreateTwoFactorPolicyData extends Data
{
    /**
     * @param  list<string>|null  $methods
     */
    public function __construct(
        public ?string $scopeType = null,
        public ?string $scopeId = null,
        public ?string $role = null,
        public bool $required = true,
        public ?array $methods = null,
        public int $graceDays = 0,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return Scopes::rules() + [
            'role' => ['sometimes', 'nullable', 'string', 'max:255', Rule::exists(RoleModel::class, 'slug')],
            'required' => ['sometimes', 'boolean'],
            'methods' => ['sometimes', 'nullable', 'array', 'min:1'],
            'methods.*' => ['string', 'distinct', Rule::in(Requirement::ALL_METHODS)],
            'grace_days' => ['sometimes', 'integer', 'min:0', 'max:3650'],
        ];
    }
}
