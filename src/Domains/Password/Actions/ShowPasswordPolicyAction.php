<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Actions;

use RefactorCircus\Guardian\Domains\Password\Data\PasswordPolicySettings;
use RefactorCircus\Guardian\Domains\Password\Events\PasswordPolicyShowingActionEvent;
use RefactorCircus\Guardian\Domains\Password\Events\PasswordPolicyShownActionEvent;
use RefactorCircus\Keystone\Actions\Action;

final class ShowPasswordPolicyAction extends Action
{
    /**
     * The active password rules, for UIs (criterion 43).
     */
    protected function handle(): PasswordPolicySettings
    {
        PasswordPolicyShowingActionEvent::dispatch();

        $settings = PasswordPolicySettings::fromConfig();

        PasswordPolicyShownActionEvent::dispatch($settings);

        return $settings;
    }
}
