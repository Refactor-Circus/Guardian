<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Concerns;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;

/**
 * What an HTTP request or MCP call about the security log names: an event
 * by id, and the signed-in user's key for their own events.
 */
trait NamesSecurityEvents
{
    private ?SecurityEventModel $namedEvent = null;

    /**
     * An argument the caller sent.
     */
    abstract protected function argument(string $key): mixed;

    abstract protected function actor(): ?Model;

    protected function securityEvent(): SecurityEventModel
    {
        $id = $this->argument('event');

        return $this->namedEvent ??= SecurityEventModel::query()->whereKey(is_scalar($id) ? (string) $id : '')->firstOrFail();
    }

    /**
     * The signed-in user's key, as the log stores it.
     */
    protected function actorKey(): string
    {
        $key = $this->actor()?->getKey();

        return is_scalar($key) ? (string) $key : '';
    }
}
