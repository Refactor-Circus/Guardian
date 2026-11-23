<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\TwoFactor\Enums\TwoFactorMethodType;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\Mask;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * One two-factor method. Never its secret; its destination only masked.
 *
 * @mixin TwoFactorMethodModel
 */
final class TwoFactorMethodResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'type' => $schema->string()->enum(TwoFactorMethodType::values())->required(),
            'label' => $schema->string()->nullable()->required(),
            'destination' => $schema->string()->nullable()->description('The email address or phone number codes go to, masked.')->required(),
            'confirmed_at' => $schema->string()->nullable()->description('Null while the method waits for confirmation.')->required(),
            'last_used_at' => $schema->string()->nullable()->required(),
            'created_at' => $schema->string()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'label' => $this->label,
            'destination' => Mask::destination($this->destination),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
