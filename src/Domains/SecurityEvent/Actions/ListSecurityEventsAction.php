<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\ListSecurityEventsData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventsListedActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventsListingActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Keystone\Actions\Action;

final class ListSecurityEventsAction extends Action
{
    /**
     * The security log, newest first, filtered by type, user and time.
     *
     * @return CursorPaginator<int, SecurityEventModel>
     */
    protected function handle(ListSecurityEventsData $filters = new ListSecurityEventsData): CursorPaginator
    {
        SecurityEventsListingActionEvent::dispatch($filters);

        $query = SecurityEventModel::query();

        if ($filters->type !== null) {
            $query->where('type', $filters->type->value);
        }

        if ($filters->user !== null) {
            $query->where('user_id', $filters->user);
        }

        if ($filters->from !== null) {
            $query->where('occurred_at', '>=', $filters->from);
        }

        if ($filters->to !== null) {
            $query->where('occurred_at', '<=', $filters->to);
        }

        $events = $query->orderByDesc('occurred_at')->orderByDesc('id')->cursorPaginate($filters->perPage, ['*'], 'cursor', $filters->cursor);

        SecurityEventsListedActionEvent::dispatch($filters);

        return $events;
    }
}
