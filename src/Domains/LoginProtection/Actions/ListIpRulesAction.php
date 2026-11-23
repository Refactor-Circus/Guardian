<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\ListIpRulesData;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRulesListedActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRulesListingActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Keystone\Actions\Action;

final class ListIpRulesAction extends Action
{
    /**
     * The IP rules, expired ones included, oldest first.
     *
     * @return LengthAwarePaginator<int, IpRuleModel>
     */
    protected function handle(ListIpRulesData $filters = new ListIpRulesData): LengthAwarePaginator
    {
        IpRulesListingActionEvent::dispatch($filters);

        $rules = IpRuleModel::query()->oldest()->oldest('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        IpRulesListedActionEvent::dispatch($filters);

        return $rules;
    }
}
