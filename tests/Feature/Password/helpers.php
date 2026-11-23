<?php

declare(strict_types=1);

use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Validator;
use RefactorCircus\Guardian\Domains\Password\PasswordPolicy;
use RefactorCircus\Guardian\Tests\Fixtures\FakeUncompromisedVerifier;
use Workbench\App\Models\User;

/**
 * Bind a verifier that answers without the network.
 *
 * @param  list<string>  $breached
 */
function fakeBreaches(array $breached = []): FakeUncompromisedVerifier
{
    $verifier = new FakeUncompromisedVerifier($breached);
    app()->instance(UncompromisedVerifier::class, $verifier);

    return $verifier;
}

/**
 * The messages the policy gives a password (none when it passes).
 *
 * @return list<string>
 */
function passwordErrors(string $password, ?User $user = null): array
{
    return array_values(Validator::make(['password' => $password], ['password' => PasswordPolicy::rules($user)])->errors()->get('password'));
}
