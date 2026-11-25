<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Data;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Guardian\Domains\LoginProtection\Rules\ValidCidr;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateIpRuleAction: an IPv4 or IPv6 address or CIDR range,
 * `allow` or `deny`, a description and an optional expiry.
 */
final readonly class CreateIpRuleData extends Data
{
    public function __construct(
        public string $cidr,
        public string $action,
        public ?string $description = null,
        public ?Carbon $expiresAt = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'cidr' => ['required', 'string', 'max:49', new ValidCidr],
            'action' => ['required', 'string', Rule::in([IpRuleModel::ALLOW, IpRuleModel::DENY])],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }
}
