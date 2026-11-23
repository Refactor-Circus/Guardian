<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Resources;

use Illuminate\Http\Request;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * New recovery codes, shown this once.
 *
 * @property list<string> $resource
 */
final class RecoveryCodesResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['recovery_codes' => $this->resource];
    }
}
