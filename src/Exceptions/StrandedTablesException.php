<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Exceptions;

/**
 * Roster has handed SSO and SCIM over, but its tables still hold the rows
 * while Guardian's are empty: the upgrade was skipped (D-029). Reported once
 * per boot, never thrown; SSO enforcement stays off for those connections
 * until the command has moved them.
 */
final class StrandedTablesException extends GuardianException
{
    /**
     * @param  list<string>  $tables
     */
    private function __construct(string $message, public readonly array $tables)
    {
        parent::__construct($message);
    }

    /**
     * @param  list<string>  $tables  Roster's tables that still hold the rows
     */
    public static function forTables(array $tables): self
    {
        return new self(
            'Guardian\'s SSO and SCIM data is still in '.self::named($tables).'. Run `php artisan guardian:upgrade-from-roster` to move it; until then SSO enforcement is off for those connections.',
            $tables,
        );
    }

    /**
     * @param  list<string>  $tables
     */
    public static function named(array $tables): string
    {
        return implode(', ', array_map(fn (string $table): string => "[{$table}]", $tables));
    }
}
