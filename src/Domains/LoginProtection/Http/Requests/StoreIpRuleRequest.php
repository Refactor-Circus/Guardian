<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\LoginProtection\Actions\CreateIpRuleAction;
use RefactorCircus\Guardian\Domains\LoginProtection\Concerns\NamesIpRules;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\CreateIpRuleData;
use RefactorCircus\Guardian\Domains\LoginProtection\Resources\IpRuleResource;
use RefactorCircus\Guardian\Http\Requests\Request;

final class StoreIpRuleRequest extends Request
{
    use NamesIpRules;

    public function rules(): array
    {
        return CreateIpRuleData::rules();
    }

    public function persist(): JsonResponse
    {
        $rule = app(CreateIpRuleAction::class)->execute(CreateIpRuleData::fromValidated($this->validated()), $this->actingUser());

        return (new IpRuleResource($rule))->response()->setStatusCode(201);
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
