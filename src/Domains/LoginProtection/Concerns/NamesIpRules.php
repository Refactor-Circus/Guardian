<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;

/**
 * What a request about login protection names: an IP rule by id. Every
 * such request needs `guardian.security.manage`.
 */
trait NamesIpRules
{
    /**
     * An argument the caller sent.
     */
    abstract protected function argument(string $key): mixed;

    abstract protected function actor(): ?Model;

    protected function ability(): string
    {
        return 'guardian.security.manage';
    }

    protected function rule(): IpRuleModel
    {
        $id = $this->argument('rule');

        return IpRuleModel::query()->whereKey(is_scalar($id) ? (string) $id : '')->firstOrFail();
    }

    protected function actingUser(): ?Authenticatable
    {
        $actor = $this->actor();

        return $actor instanceof Authenticatable ? $actor : null;
    }
}
