<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Session\Concerns\NamesSessions;
use RefactorCircus\Guardian\Mcp\Requests\Request;

/**
 * A call about the signed-in user's own sessions or devices: any
 * signed-in user may make it.
 */
abstract class OwnSessionMcpRequest extends Request
{
    use NamesSessions;

    protected function authorize(): bool
    {
        return $this->actor() !== null && parent::authorize();
    }

    protected function self(): ?Model
    {
        return $this->actor();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
