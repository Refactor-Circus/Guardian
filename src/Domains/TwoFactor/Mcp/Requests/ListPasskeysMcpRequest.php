<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ListPasskeysAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactor;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\PasskeyResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

/**
 * Your own passkeys.
 */
final class ListPasskeysMcpRequest extends Request
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

    protected function self(): ?Model
    {
        return $this->actor();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $passkeys = app(ListPasskeysAction::class)->execute($this->signedIn());

        return $this->structuredCollection(PasskeyResource::collection($passkeys)->resolve(request()));
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
