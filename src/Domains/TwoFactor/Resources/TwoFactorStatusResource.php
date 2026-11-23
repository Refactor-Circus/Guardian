<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\Requirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\TwoFactorStatus;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * A user's two-factor status (criterion 31). Recovery codes appear only as
 * the number left.
 *
 * @property TwoFactorStatus $resource
 */
final class TwoFactorStatusResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'enabled' => $schema->boolean()->description('Whether the user holds a confirmed factor.')->required(),
            'methods' => $schema->array()->items($schema->object(TwoFactorMethodResource::schema($schema)))->required(),
            'passkeys' => $schema->array()->items($schema->object())->required(),
            'passkeys_available' => $schema->boolean()->description('Whether this install can register passkeys.')->required(),
            'recovery_codes_remaining' => $schema->integer()->required(),
            'required' => $schema->boolean()->required(),
            'allowed_methods' => $schema->array()->items($schema->string()->enum(Requirement::ALL_METHODS))->required(),
            'enrolment_deadline' => $schema->string()->nullable()->description('When a required user must have enrolled; null for at once.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->resource;

        return [
            'enabled' => $status->enabled,
            'methods' => TwoFactorMethodResource::collection($status->methods)->resolve($request),
            'passkeys' => [],
            'passkeys_available' => $status->passkeysAvailable,
            'recovery_codes_remaining' => $status->recoveryCodesRemaining,
            'required' => $status->requirement->required,
            'allowed_methods' => $status->allowedMethods,
            'enrolment_deadline' => $status->requirement->deadline?->toIso8601String(),
        ];
    }
}
