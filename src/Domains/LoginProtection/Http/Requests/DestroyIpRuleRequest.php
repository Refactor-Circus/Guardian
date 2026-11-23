<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\LoginProtection\Actions\DeleteIpRuleAction;
use RefactorCircus\Guardian\Domains\LoginProtection\Concerns\NamesIpRules;
use RefactorCircus\Guardian\Http\Requests\Request;

final class DestroyIpRuleRequest extends Request
{
    use NamesIpRules;

    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        app(DeleteIpRuleAction::class)->execute($this->rule());

        return new JsonResponse(null, 204);
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
