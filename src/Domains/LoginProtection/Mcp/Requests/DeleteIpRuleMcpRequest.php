<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\LoginProtection\Actions\DeleteIpRuleAction;
use RefactorCircus\Guardian\Domains\LoginProtection\Concerns\NamesIpRules;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class DeleteIpRuleMcpRequest extends Request
{
    use NamesIpRules;

    protected function rules(): array
    {
        return ['rule' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(DeleteIpRuleAction::class)->execute($this->rule());

        return Response::text('IP rule deleted.');
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
