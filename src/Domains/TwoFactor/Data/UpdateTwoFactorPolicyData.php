<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for UpdateTwoFactorPolicyAction: the fields to change; fields left
 * out are kept. Whom a policy covers is fixed: make a new one instead.
 */
final readonly class UpdateTwoFactorPolicyData extends Data
{
    /**
     * @param  list<string>|Missing|null  $methods
     */
    public function __construct(
        public bool|Missing $required = new Missing,
        public array|Missing|null $methods = new Missing,
        public int|Missing $graceDays = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'required' => ['sometimes', 'boolean'],
            'methods' => ['sometimes', 'nullable', 'array', 'min:1'],
            'methods.*' => ['string', 'distinct', Rule::in(Requirement::ALL_METHODS)],
            'grace_days' => ['sometimes', 'integer', 'min:0', 'max:3650'],
        ];
    }
}
