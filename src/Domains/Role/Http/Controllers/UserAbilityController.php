<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\IndexUserAbilitiesRequest;

final class UserAbilityController
{
    public function index(IndexUserAbilitiesRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
