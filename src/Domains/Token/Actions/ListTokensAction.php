<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Guardian\Domains\Token\Data\TokenSummary;
use RefactorCircus\Guardian\Domains\Token\Events\TokensListedActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\TokensListingActionEvent;
use RefactorCircus\Guardian\Domains\Token\TokenManager;
use RefactorCircus\Keystone\Actions\Action;

final class ListTokensAction extends Action
{
    public function __construct(private readonly TokenManager $tokens) {}

    /**
     * The signed-in user's API tokens that still work (criterion 48),
     * through the token driver.
     *
     * @return list<TokenSummary>
     */
    protected function handle(Authenticatable $user): array
    {
        TokensListingActionEvent::dispatch($user);

        $tokens = $this->tokens->driver()->tokens($user);

        TokensListedActionEvent::dispatch($user);

        return $tokens;
    }
}
