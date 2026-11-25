<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Validator;
use RefactorCircus\Guardian\Domains\Password\Data\CheckPasswordData;
use RefactorCircus\Guardian\Domains\Password\Data\PasswordCheck;
use RefactorCircus\Guardian\Domains\Password\Events\PasswordCheckedActionEvent;
use RefactorCircus\Guardian\Domains\Password\Events\PasswordCheckingActionEvent;
use RefactorCircus\Guardian\Domains\Password\PasswordPolicy;
use RefactorCircus\Keystone\Actions\Action;

final class CheckPasswordAction extends Action
{
    /**
     * Check a password against the policy (criterion 43), with the
     * history of the user given. The password is never stored, logged or
     * put in an event: both events carry only the user and the outcome.
     */
    protected function handle(CheckPasswordData $data, ?Authenticatable $user = null): PasswordCheck
    {
        PasswordCheckingActionEvent::dispatch($user);

        $errors = Validator::make(['password' => $data->password], ['password' => PasswordPolicy::rules($user)])->errors()->get('password');

        /** @var list<string> $messages */
        $messages = array_values(array_filter($errors, is_string(...)));
        $result = new PasswordCheck($messages === [], $messages);

        PasswordCheckedActionEvent::dispatch($user, $result);

        return $result;
    }
}
