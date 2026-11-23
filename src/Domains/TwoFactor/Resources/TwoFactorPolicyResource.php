<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\Requirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * One two-factor enforcement policy.
 *
 * @mixin TwoFactorPolicyModel
 */
final class TwoFactorPolicyResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'scope_type' => $schema->string()->nullable()->description('The scope it covers (with scope_id); null for none.')->required(),
            'scope_id' => $schema->string()->nullable()->required(),
            'role' => $schema->string()->nullable()->description('The role slug whose holders it covers; null for none.')->required(),
            'required' => $schema->boolean()->required(),
            'methods' => $schema->array()->items($schema->string()->enum(Requirement::ALL_METHODS))->nullable()->description('The methods that count; null for any.')->required(),
            'grace_days' => $schema->integer()->required(),
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
            'scope_type' => $this->scope_type,
            'scope_id' => $this->scope_id,
            'role' => $this->role,
            'required' => $this->required,
            'methods' => $this->methods,
            'grace_days' => $this->grace_days,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
