<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RefactorCircus\Guardian\Domains\LoginProtection\Services\IpRules;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;
use Symfony\Component\HttpFoundation\Response;

/**
 * The `guardian.ip` middleware (criterion 52): an address the IP rules
 * deny gets a 403 `{message, code: "ip_denied"}`, recorded as `ip.denied`
 * at most once a minute per address.
 */
final class EnforceIpRules
{
    public function __construct(
        private readonly IpRules $rules,
        private readonly SecurityLog $log,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if ($ip === null || ! $this->rules->denies($ip)) {
            return $next($request);
        }

        if (Cache::add('guardian.ip_denied:'.$ip, true, 60)) {
            $this->log->record(SecurityEventType::IpDenied, null);
        }

        return new JsonResponse(['message' => __('guardian::guardian.ip_denied'), 'code' => 'ip_denied'], 403);
    }
}
