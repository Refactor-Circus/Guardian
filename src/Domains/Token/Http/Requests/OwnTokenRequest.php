<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Token\Concerns\NamesTokens;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * A request about the signed-in user's own API tokens: any signed-in user
 * may make it.
 */
abstract class OwnTokenRequest extends Request
{
    use NamesTokens;

    public function authorize(): bool
    {
        return $this->actor() !== null && parent::authorize();
    }

    protected function self(): ?Model
    {
        return $this->actor();
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
