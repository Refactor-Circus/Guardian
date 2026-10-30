<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Http\Requests\Request;
use RefactorCircus\Roster\Support\Users;

/**
 * A request about one user, named in the route by the user model's route key.
 */
abstract class SsoUserRequest extends Request
{
    private ?Model $target = null;

    /**
     * The user named in the route.
     */
    protected function targetUser(): Model
    {
        return $this->target ??= app(Users::class)->findOrFail($this->route('user'));
    }
}
