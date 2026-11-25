<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ShowTwoFactorAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactor;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorStatusResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

/**
 * Your own two-factor status, or another user's with
 * `guardian.two-factor.manage`.
 */
final class ShowTwoFactorMcpRequest extends Request
{
    use NamesTwoFactor;

    protected function authorize(): bool
    {
        return $this->actor() !== null && parent::authorize();
    }

    protected function ability(): string
    {
        return 'guardian.two-factor.manage';
    }

    protected function self(): Model
    {
        return $this->subject();
    }

    protected function rules(): array
    {
        return [
            'user' => ['sometimes', 'nullable', 'regex:/^[\w\-]{1,64}$/'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new TwoFactorStatusResource(app(ShowTwoFactorAction::class)->execute($this->subject())))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }

    private function subject(): Model&Authenticatable
    {
        return $this->argument('user') === null ? $this->signedIn() : $this->namedUser();
    }
}
