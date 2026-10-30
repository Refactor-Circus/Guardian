<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Scim\Events\ScimTokenRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Scim\Events\ScimTokenRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Keystone\Actions\Action;

final class RevokeScimTokenAction extends Action
{
    /**
     * Stop a token working. Revoking twice changes nothing.
     */
    protected function handle(ScimTokenModel $token): ScimTokenModel
    {
        if ($token->revoked_at !== null) {
            return $token;
        }

        ScimTokenRevokingActionEvent::dispatch($token);

        DB::transaction(fn () => $token->update(['revoked_at' => now()]));

        ScimTokenRevokedActionEvent::dispatch($token);

        return $token->load(['organization', 'ssoConnection']);
    }
}
