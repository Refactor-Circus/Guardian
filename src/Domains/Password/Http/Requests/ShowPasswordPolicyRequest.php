<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Password\Actions\ShowPasswordPolicyAction;
use RefactorCircus\Guardian\Domains\Password\Resources\PasswordPolicyResource;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * The password rules, for anyone: a registration form needs them before
 * there is a user.
 */
final class ShowPasswordPolicyRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    protected function ability(): string
    {
        return 'guardian.security.view';
    }

    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        return (new PasswordPolicyResource(app(ShowPasswordPolicyAction::class)->execute()))->response();
    }
}
