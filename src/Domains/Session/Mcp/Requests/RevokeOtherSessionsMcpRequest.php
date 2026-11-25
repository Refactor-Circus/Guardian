<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Session\Actions\RevokeOtherSessionsAction;
use RefactorCircus\Guardian\Domains\Session\Data\RevokeOtherSessionsData;

/**
 * Over MCP there is no current session, so every session of the caller is
 * revoked.
 */
final class RevokeOtherSessionsMcpRequest extends OwnSessionMcpRequest
{
    protected function rules(): array
    {
        return RevokeOtherSessionsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $result = app(RevokeOtherSessionsAction::class)->execute($this->signedIn(), RevokeOtherSessionsData::fromValidated($validated));

        return Response::structured(['revoked' => $result->sessions, 'tokens_revoked' => $result->tokens]);
    }
}
