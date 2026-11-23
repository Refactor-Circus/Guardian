<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\TwoFactorSession;

/**
 * On sign-in, a user with a confirmed factor leaves the session pending
 * until they pass a challenge (criterion 32). A sign-in without a session
 * (a token guard) is left alone (D-012).
 */
final class MarkSessionPending
{
    public function __construct(private readonly TwoFactorSession $session) {}

    public function handle(Login $event): void
    {
        $request = app()->bound('request') ? app('request') : null;

        if ($request instanceof Request && $request->hasSession()) {
            $this->session->signedIn($request->session(), $event->user);
        }
    }
}
