<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Password\Actions\ShowPasswordPolicyAction;
use RefactorCircus\Guardian\Domains\Password\Resources\PasswordPolicyResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

/**
 * The password rules, for any signed-in caller.
 */
final class ShowPasswordPolicyMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return $this->actor() !== null && parent::authorize();
    }

    protected function ability(): string
    {
        return 'guardian.security.view';
    }

    protected function self(): ?Model
    {
        return $this->actor();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new PasswordPolicyResource(app(ShowPasswordPolicyAction::class)->execute()))->toStructuredResponse();
    }
}
