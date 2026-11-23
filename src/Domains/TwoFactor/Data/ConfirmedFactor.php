<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

/**
 * A factor just confirmed, with the recovery codes created alongside the
 * user's first one (null otherwise), answered this once.
 */
final readonly class ConfirmedFactor
{
    /**
     * @param  list<string>|null  $recoveryCodes
     */
    public function __construct(
        public Model $factor,
        #[SensitiveParameter] public ?array $recoveryCodes = null,
    ) {}
}
