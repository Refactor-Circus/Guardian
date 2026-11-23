<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\LoginProtection\Actions\ListIpRulesAction;
use RefactorCircus\Guardian\Domains\LoginProtection\Concerns\NamesIpRules;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\ListIpRulesData;
use RefactorCircus\Guardian\Domains\LoginProtection\Resources\IpRuleResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ListIpRulesMcpRequest extends Request
{
    use NamesIpRules;

    protected function rules(): array
    {
        return ListIpRulesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return IpRuleResource::collection(app(ListIpRulesAction::class)->execute(ListIpRulesData::fromValidated($validated)))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
