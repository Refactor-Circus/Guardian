<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRuleDeletedActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRuleDeletingActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Keystone\Actions\Action;

final class DeleteIpRuleAction extends Action
{
    /**
     * Delete an IP rule (criterion 52); the cached rules are forgotten.
     */
    protected function handle(IpRuleModel $rule): void
    {
        IpRuleDeletingActionEvent::dispatch($rule);

        DB::transaction(fn (): ?bool => $rule->delete());

        IpRuleDeletedActionEvent::dispatch($rule);
    }
}
