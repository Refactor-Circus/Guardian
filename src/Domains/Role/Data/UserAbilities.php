<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Data;

/**
 * What ListUserAbilitiesAction answers: the abilities a user holds in a
 * scope (every known one for a super-admin), sorted; null when the bound
 * `Permissions` cannot list them.
 */
final readonly class UserAbilities
{
    /**
     * @param  list<string>|null  $abilities
     */
    public function __construct(
        public string $user,
        public ?string $scopeType,
        public ?string $scopeId,
        public bool $superAdmin,
        public ?array $abilities,
    ) {}
}
