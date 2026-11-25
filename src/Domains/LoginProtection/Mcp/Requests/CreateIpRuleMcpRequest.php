<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\LoginProtection\Actions\CreateIpRuleAction;
use RefactorCircus\Guardian\Domains\LoginProtection\Concerns\NamesIpRules;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\CreateIpRuleData;
use RefactorCircus\Guardian\Domains\LoginProtection\Resources\IpRuleResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class CreateIpRuleMcpRequest extends Request
{
    use NamesIpRules;

    protected function rules(): array
    {
        return CreateIpRuleData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new IpRuleResource(app(CreateIpRuleAction::class)->execute(CreateIpRuleData::fromValidated($validated), $this->actingUser())))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
