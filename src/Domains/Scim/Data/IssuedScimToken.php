<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Data;

use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use SensitiveParameter;

/**
 * A freshly issued SCIM token. `$plain` is the only copy: show it once.
 */
final readonly class IssuedScimToken
{
    public function __construct(
        public ScimTokenModel $token,
        #[SensitiveParameter]
        public string $plain,
    ) {}
}
