<?php

declare(strict_types=1);

use Workbench\App\Models\User;

/**
 * Sign in as a fresh super-admin for a browser test.
 */
function signInAsSuperAdmin(): User
{
    $admin = user(['name' => 'Admin', 'email' => 'admin@example.com']);
    grant($admin, 'super-admin');
    test()->actingAs($admin);

    return $admin;
}
