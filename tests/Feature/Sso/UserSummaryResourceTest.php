<?php

declare(strict_types=1);

// Criterion 9: Guardian keeps its own user summary rather than nesting
// Roster's HTTP resource, so it must stay identical to Roster's payload.

use Illuminate\Http\Request;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use RefactorCircus\Guardian\Domains\Sso\Resources\UserSummaryResource;
use RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource as RosterUserSummaryResource;

it('declares the same schema as Roster\'s user summary', function (): void {
    $schema = new JsonSchemaTypeFactory;

    expect($schema->object(UserSummaryResource::schema($schema))->toArray())
        ->toBe($schema->object(RosterUserSummaryResource::schema($schema))->toArray());
});

it('serializes a user as Roster\'s user summary does', function (): void {
    $user = user(['name' => 'Ada Lovelace', 'email' => 'ada@acme.test']);
    $request = Request::create('/');

    expect((new UserSummaryResource($user))->resolve($request))
        ->toBe((new RosterUserSummaryResource($user))->resolve($request))
        ->toBe(['id' => $user->getRouteKey(), 'name' => 'Ada Lovelace', 'email' => 'ada@acme.test']);
});
