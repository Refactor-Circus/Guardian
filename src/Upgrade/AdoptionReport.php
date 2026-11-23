<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Upgrade;

/**
 * What one run of the role adoption from Roster did (criterion 20). A value
 * object, not action input, so it lives beside the adoption.
 */
final class AdoptionReport
{
    public const string NOTHING = 'nothing to adopt';

    public const string ALREADY = 'already adopted';

    public const string GUARDIAN_HOLDS_ROLES = 'guardian holds roles';

    public const string ADOPTED = 'adopted';

    /**
     * One of the constants above.
     */
    public private(set) string $outcome = self::NOTHING;

    /**
     * Roster table names that are missing, when there is nothing to adopt.
     *
     * @var list<string>
     */
    public private(set) array $missing = [];

    /**
     * Rows copied, by kind: permissions, roles, grants, assignments.
     *
     * @var array<string, int>
     */
    public private(set) array $copied = [];

    /**
     * Roster permission => the Guardian permission it became.
     *
     * @var array<string, string>
     */
    public private(set) array $renamed = [];

    /**
     * Roster's scope => the morph type it became.
     *
     * @var array<string, string>
     */
    public private(set) array $scopeTypes = [];

    /**
     * @param  list<string>  $missing
     */
    public function nothing(array $missing): static
    {
        $this->outcome = self::NOTHING;
        $this->missing = $missing;

        return $this;
    }

    public function already(): static
    {
        $this->outcome = self::ALREADY;

        return $this;
    }

    public function guardianHoldsRoles(): static
    {
        $this->outcome = self::GUARDIAN_HOLDS_ROLES;

        return $this;
    }

    /**
     * @param  array<string, int>  $copied
     * @param  array<string, string>  $renamed
     * @param  array<string, string>  $scopeTypes
     */
    public function adopted(array $copied, array $renamed, array $scopeTypes): static
    {
        $this->outcome = self::ADOPTED;
        $this->copied = $copied;
        $this->renamed = $renamed;
        $this->scopeTypes = $scopeTypes;

        return $this;
    }

    /**
     * The report, a line per thing done or left to do.
     *
     * @return list<string>
     */
    public function lines(): array
    {
        return match ($this->outcome) {
            self::NOTHING => ['Roles: nothing to adopt: Roster\'s role tables ['.implode(', ', $this->missing).'] are not there.'],
            self::ALREADY => ['Roles: already adopted from Roster\'s role tables; nothing changed.'],
            self::GUARDIAN_HOLDS_ROLES => ['Roles: nothing adopted: Guardian already holds role assignments or roles of its own. Move what you need from Roster\'s role tables by hand.'],
            default => $this->adoptedLines(),
        };
    }

    /**
     * @return list<string>
     */
    private function adoptedLines(): array
    {
        $copied = $this->copied + ['permissions' => 0, 'roles' => 0, 'grants' => 0, 'assignments' => 0];
        $lines = ["Roles: adopted {$copied['roles']} roles, {$copied['permissions']} permissions, {$copied['grants']} grants and {$copied['assignments']} assignments from Roster's role tables, which are left as they were: drop them by hand once you are done."];

        foreach ($this->scopeTypes as $roster => $type) {
            $lines[] = "Roles: Roster's {$roster} scope is now [{$type}] (guardian.adopt.scope_types).";
        }

        foreach ($this->renamed as $from => $to) {
            $lines[] = "Roles: the [{$from}] permission is now [{$to}].";
        }

        return $lines;
    }
}
