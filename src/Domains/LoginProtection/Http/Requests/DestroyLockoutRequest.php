<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\LoginProtection\Actions\ClearLockoutAction;
use RefactorCircus\Guardian\Domains\LoginProtection\Concerns\NamesIpRules;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\ClearLockoutData;
use RefactorCircus\Guardian\Http\Requests\Request;

final class DestroyLockoutRequest extends Request
{
    use NamesIpRules;

    public function rules(): array
    {
        return ClearLockoutData::rules();
    }

    public function persist(): JsonResponse
    {
        app(ClearLockoutAction::class)->execute(ClearLockoutData::fromValidated($this->validated()), $this->actingUser());

        return new JsonResponse(null, 204);
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
