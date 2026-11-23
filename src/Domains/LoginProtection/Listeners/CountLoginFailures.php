<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\LoginProtection\Services\LoginFailures;
use RefactorCircus\Guardian\Domains\LoginProtection\Support\Identifier;

/**
 * Counts every failed sign-in by identifier and address, and clears the
 * identifier's lockout counter when its user signs in (criterion 49).
 */
final class CountLoginFailures
{
    public function __construct(private readonly LoginFailures $failures) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Failed::class, [self::class, 'failed']);
        $events->listen(Login::class, [self::class, 'login']);
    }

    public function failed(Failed $event): void
    {
        $request = app()->bound('request') ? app('request') : null;

        $this->failures->failed(Identifier::in($event->credentials), $request instanceof Request ? $request->ip() : null);
    }

    public function login(Login $event): void
    {
        $identifier = Identifier::of($event->user);

        if ($identifier !== null) {
            $this->failures->clear($identifier);
        }
    }
}
