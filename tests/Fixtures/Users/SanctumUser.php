<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Fixtures\Users;

use Laravel\Sanctum\HasApiTokens;
use Workbench\App\Models\User;

/**
 * The workbench user with Sanctum's personal access tokens.
 */
class SanctumUser extends User
{
    use HasApiTokens;

    protected $table = 'users';
}
