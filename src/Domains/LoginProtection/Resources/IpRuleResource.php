<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * One IP rule.
 *
 * @mixin IpRuleModel
 */
final class IpRuleResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'cidr' => $schema->string()->description('An IPv4 or IPv6 address or CIDR range.')->required(),
            'action' => $schema->string()->enum([IpRuleModel::ALLOW, IpRuleModel::DENY])->required(),
            'description' => $schema->string()->nullable()->required(),
            'expires_at' => $schema->string()->nullable()->description('When the rule stops applying; null for never.')->required(),
            'created_by' => $schema->string()->nullable()->description('The id of the user who made it.')->required(),
            'created_at' => $schema->string()->required(),
            'updated_at' => $schema->string()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cidr' => $this->cidr,
            'action' => $this->action,
            'description' => $this->description,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
