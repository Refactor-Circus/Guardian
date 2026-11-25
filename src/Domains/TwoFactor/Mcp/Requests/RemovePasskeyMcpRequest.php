<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RemovePasskeyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactor;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RemoveTwoFactorMethodData;
use RefactorCircus\Guardian\Mcp\Requests\Request;

/**
 * Remove one of your own passkeys, with a code from another factor.
 */
final class RemovePasskeyMcpRequest extends Request
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

    protected function rules(): array
    {
        return [
            'passkey' => ['required', 'string'],
            'code' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }

    protected function handle(array $validated): Response
    {
        app(RemovePasskeyAction::class)->execute($this->signedIn(), $this->ownPasskey(), RemoveTwoFactorMethodData::fromValidated($validated));

        return Response::text('Passkey removed.');
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
