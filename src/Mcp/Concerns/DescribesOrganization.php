<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Mcp\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * The `organization` argument of the tools about one Roster organization,
 * described as Roster's own tools describe it.
 */
trait DescribesOrganization
{
    /**
     * @return array<string, Type>
     */
    protected function organizationSchema(JsonSchema $schema): array
    {
        return [
            'organization' => $schema->string()->description('The organization slug.')->required(),
        ];
    }
}
