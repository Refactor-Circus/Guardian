<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Data;

use SensitiveParameter;

/**
 * A token just issued: what it is, and its plain-text value, which is
 * answered once and never stored or logged by Guardian.
 */
final readonly class IssuedToken
{
    public function __construct(
        public TokenSummary $token,
        #[SensitiveParameter] public string $plainTextToken,
    ) {}
}
