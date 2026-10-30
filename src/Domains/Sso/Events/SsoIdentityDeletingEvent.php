<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The SsoIdentityModel `deleting` Eloquent event.
 */
final class SsoIdentityDeletingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public SsoIdentityModel $ssoIdentity) {}

    public function model(): Model
    {
        return $this->ssoIdentity;
    }

    public function hook(): string
    {
        return 'deleting';
    }
}
