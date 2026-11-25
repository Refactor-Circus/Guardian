<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\CreateIpRuleData;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRuleCreatedActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRuleCreatingActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Guardian\Domains\LoginProtection\Support\Cidr;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class CreateIpRuleAction extends Action
{
    /**
     * Allow or deny an address or range (criterion 52). The cached rules
     * are forgotten as the rule is saved.
     */
    protected function handle(CreateIpRuleData $data, ?Authenticatable $actor = null): IpRuleModel
    {
        IpRuleCreatingActionEvent::dispatch($data);

        if (! Cidr::valid($data->cidr) || ! in_array($data->action, [IpRuleModel::ALLOW, IpRuleModel::DENY], true)) {
            throw ValidationException::withMessages(['cidr' => __('guardian::guardian.ip_rule_invalid_cidr')]);
        }

        $rule = DB::transaction(fn (): IpRuleModel => IpRuleModel::query()->create([
            'cidr' => $data->cidr,
            'action' => $data->action,
            'description' => $data->description,
            'expires_at' => $data->expiresAt,
            'created_by' => $actor === null ? null : (string) UserKeys::of($actor),
        ]));

        IpRuleCreatedActionEvent::dispatch($rule);

        return $rule;
    }
}
