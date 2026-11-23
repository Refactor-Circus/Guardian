<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Resources;

use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\TotpEnrolment;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * A TOTP enrolment just started: the method, and the secret and
 * `otpauth://` URI the authenticator app needs, shown this once.
 *
 * @property TotpEnrolment $resource
 */
final class TotpEnrolmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'method' => (new TwoFactorMethodResource($this->resource->method))->resolve($request),
            'secret' => $this->resource->secret,
            'otpauth_uri' => $this->resource->otpauthUri,
        ];
    }
}
