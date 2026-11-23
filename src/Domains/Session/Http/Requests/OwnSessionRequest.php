<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Session\Concerns\NamesSessions;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * A request about the signed-in user's own sessions or devices: any
 * signed-in user may make it (a guest gets a 401, whatever
 * `guardian.authorization` says).
 */
abstract class OwnSessionRequest extends Request
{
    use NamesSessions;

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
