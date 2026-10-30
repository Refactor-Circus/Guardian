<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Mcp\Requests\Request;
use RefactorCircus\Roster\Support\Users;

/**
 * A tool call about one user, named by the `user` argument.
 */
abstract class SsoUserMcpRequest extends Request
{
    private ?Model $target = null;

    /**
     * The user named by the `user` argument, found by the user model's route key.
     */
    protected function targetUser(): Model
    {
        return $this->target ??= app(Users::class)->findOrFail($this->get('user'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function userRules(): array
    {
        return ['user' => ['required']];
    }

    /**
     * Every user tool names its user.
     */
    protected function rules(): array
    {
        return $this->userRules();
    }
}
