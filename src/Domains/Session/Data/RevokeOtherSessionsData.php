<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for RevokeOtherSessionsAction: whether the user's API tokens go
 * too, through the token driver.
 */
final readonly class RevokeOtherSessionsData extends Data
{
    public function __construct(
        public bool $tokens = false,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'tokens' => ['sometimes', 'boolean'],
        ];
    }
}
