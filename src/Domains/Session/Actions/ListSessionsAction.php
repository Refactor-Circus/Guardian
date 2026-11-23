<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Session\Data\ListSessionsData;
use RefactorCircus\Guardian\Domains\Session\Events\SessionsListedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\SessionsListingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class ListSessionsAction extends Action
{
    /**
     * The signed-in user's own sessions still active, most recently active
     * first (criterion 38).
     *
     * @return LengthAwarePaginator<int, SessionModel>
     */
    protected function handle(Authenticatable $user, ListSessionsData $filters = new ListSessionsData): LengthAwarePaginator
    {
        SessionsListingActionEvent::dispatch($user, $filters);

        $sessions = SessionModel::query()->activeFor(UserKeys::of($user))->with('device')
            ->latest('last_active_at')->latest('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);

        SessionsListedActionEvent::dispatch($user, $filters);

        return $sessions;
    }
}
