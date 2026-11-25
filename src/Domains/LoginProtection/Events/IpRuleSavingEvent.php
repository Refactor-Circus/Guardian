<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The IpRuleModel `saving` Eloquent event.
 */
final class IpRuleSavingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public IpRuleModel $ipRule) {}

    public function model(): Model
    {
        return $this->ipRule;
    }

    public function hook(): string
    {
        return 'saving';
    }
}
