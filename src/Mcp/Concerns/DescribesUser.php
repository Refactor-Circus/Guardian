<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Mcp\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * The `user` argument of the tools about one user, described as Roster's own
 * tools describe it.
 */
trait DescribesUser
{
    /**
     * @return array<string, Type>
     */
    protected function userSchema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()->description('The user id, as returned in the `id` field of list-users-tool.')->required(),
        ];
    }
}
