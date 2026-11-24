<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Mcp\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * The `scope_type` and `scope_id` arguments of the tools that act in a
 * scope (an organization, a team, or any other model a package scopes by).
 */
trait DescribesScope
{
    /**
     * @return array<string, Type>
     */
    protected function scopeSchema(JsonSchema $schema, string $purpose = 'The scope the call is about; omit both for global.'): array
    {
        return [
            'scope_type' => $schema->string()->description($purpose.' The scope type: a morph alias or model class.'),
            'scope_id' => $schema->string()->description('The scope\'s route key (such as an organization\'s slug) or its key, with scope_type.'),
        ];
    }
}
