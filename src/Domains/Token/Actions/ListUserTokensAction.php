<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Guardian\Domains\Token\Data\TokenSummary;
use RefactorCircus\Guardian\Domains\Token\Events\UserTokensListedActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\UserTokensListingActionEvent;
use RefactorCircus\Guardian\Domains\Token\TokenManager;
use RefactorCircus\Keystone\Actions\Action;

final class ListUserTokensAction extends Action
{
    public function __construct(private readonly TokenManager $tokens) {}

    /**
     * Another user's API tokens that still work, for an admin (criterion 48),
     * through the token driver.
     *
     * @return list<TokenSummary>
     */
    protected function handle(Authenticatable $user): array
    {
        UserTokensListingActionEvent::dispatch($user);

        $tokens = $this->tokens->driver()->tokens($user);

        UserTokensListedActionEvent::dispatch($user);

        return $tokens;
    }
}
