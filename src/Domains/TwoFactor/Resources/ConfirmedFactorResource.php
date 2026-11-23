<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Resources;

use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmedFactor;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * A factor just confirmed, with the recovery codes made alongside the first
 * one: shown this once, and null otherwise.
 *
 * @property ConfirmedFactor $resource
 */
final class ConfirmedFactorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $factor = $this->resource->factor;

        return $factor instanceof TwoFactorMethodModel
            ? ['method' => (new TwoFactorMethodResource($factor))->resolve($request), 'recovery_codes' => $this->resource->recoveryCodes]
            : ['passkey' => (new PasskeyResource($factor))->resolve($request), 'recovery_codes' => $this->resource->recoveryCodes];
    }
}
