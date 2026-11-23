<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Data;

use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for ListUserAbilitiesAction: the scope to list the user's
 * abilities in (`scope_type` and `scope_id`; neither for global).
 */
final readonly class ListUserAbilitiesData extends Data
{
    public function __construct(
        public ?string $scopeType = null,
        public ?string $scopeId = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return Scopes::rules();
    }
}
