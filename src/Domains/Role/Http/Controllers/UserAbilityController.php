<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\IndexUserAbilitiesRequest;
use RefactorCircus\Guardian\Domains\Role\Resources\UserAbilitiesResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class UserAbilityController
{
    /**
     * List a user's abilities, optionally within a scope.
     *
     * @response UserAbilitiesResource
     */
    public function index(IndexUserAbilitiesRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
