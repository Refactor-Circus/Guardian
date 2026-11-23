<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\Token\Actions\RevokeTokenAction;
use RefactorCircus\Guardian\Domains\Token\Concerns\NamesTokens;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class RevokeTokenMcpRequest extends Request
{
    use NamesTokens;

    protected function authorize(): bool
    {
        return $this->actor() !== null && parent::authorize();
    }

    protected function self(): ?Model
    {
        return $this->actor();
    }

    protected function rules(): array
    {
        return ['token' => ['required', 'string', 'max:255']];
    }

    protected function handle(array $validated): Response
    {
        app(RevokeTokenAction::class)->execute($this->signedIn(), $this->tokenId(), $this->signedIn());

        return Response::text('Token revoked.');
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
