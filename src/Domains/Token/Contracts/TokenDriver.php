<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Contracts;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Guardian\Domains\Token\Data\IssuedToken;
use RefactorCircus\Guardian\Domains\Token\Data\TokenSummary;

/**
 * Where a user's API tokens live (criterion 44): Sanctum's personal access
 * tokens, Passport's, or the host's own. Guardian never stores a token
 * itself; `guardian.tokens.driver` picks the driver.
 */
interface TokenDriver
{
    /**
     * Issue a token. The plain-text value is in the answer only.
     *
     * @param  list<string>  $abilities
     */
    public function issue(Authenticatable $user, string $name, array $abilities, ?CarbonImmutable $expiresAt): IssuedToken;

    /**
     * The user's tokens that still work.
     *
     * @return list<TokenSummary>
     */
    public function tokens(Authenticatable $user): array;

    /**
     * Revoke one of the user's tokens; false when they hold none with that id.
     */
    public function revoke(Authenticatable $user, string $id): bool;

    /**
     * Revoke every token the user holds; the number revoked.
     */
    public function revokeAll(Authenticatable $user): int;
}
