<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Fixtures\Users;

use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Workbench\App\Models\User;

/**
 * The workbench user with Passport's personal access tokens.
 */
class PassportUser extends User implements OAuthenticatable
{
    use HasApiTokens;

    protected $table = 'users';
}
