<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\LoginProtection\Actions\ListIpRulesAction;
use RefactorCircus\Guardian\Domains\LoginProtection\Concerns\NamesIpRules;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\ListIpRulesData;
use RefactorCircus\Guardian\Domains\LoginProtection\Resources\IpRuleResource;
use RefactorCircus\Guardian\Http\Requests\Request;

final class IndexIpRulesRequest extends Request
{
    use NamesIpRules;

    public function rules(): array
    {
        return ListIpRulesData::rules();
    }

    public function persist(): JsonResponse
    {
        return IpRuleResource::collection(app(ListIpRulesAction::class)->execute(ListIpRulesData::fromValidated($this->validated())))->response();
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
