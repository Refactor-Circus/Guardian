<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactor;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * A request about the signed-in user's own two-factor: any signed-in user
 * may make it (a guest gets a 401, whatever `guardian.authorization` says).
 */
abstract class OwnTwoFactorRequest extends Request
{
    use NamesTwoFactor;

    public function authorize(): bool
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

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
