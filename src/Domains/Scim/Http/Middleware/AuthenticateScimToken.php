<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\Scim\Exceptions\ScimException;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Services\ScimContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates SCIM requests with an organization's bearer token. A token
 * only ever reaches its own organization.
 */
final class AuthenticateScimToken
{
    public function __construct(private readonly ScimContext $context) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();
        $token = $bearer === null ? null : ScimTokenModel::query()->where('token_hash', hash('sha256', $bearer))->with('organization')->first();

        if ($token === null || ! $token->isUsable() || $token->organization?->slug !== $request->route('organization')) {
            return (new ScimException(401, 'A valid SCIM token for this organization is required.'))->render()
                ->header('WWW-Authenticate', 'Bearer');
        }

        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        $this->context->token = $token;

        try {
            return $next($request);
        } catch (ScimException $exception) {
            return $exception->render();
        }
    }
}
