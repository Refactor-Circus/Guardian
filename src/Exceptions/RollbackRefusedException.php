<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Exceptions;

/**
 * A rollback of Guardian's create migrations met tables that hold rows, and
 * dropped nothing (D-031). After an upgrade from Roster those rows are the
 * install's SSO connections, identities and SCIM tokens.
 */
final class RollbackRefusedException extends GuardianException
{
    /**
     * @param  list<string>  $tables
     */
    private function __construct(string $message, public readonly array $tables)
    {
        parent::__construct($message);
    }

    /**
     * @param  list<string>  $tables  the tables that hold rows
     */
    public static function forTables(array $tables): self
    {
        $named = implode(', ', array_map(fn (string $table): string => "[{$table}]", $tables));

        return new self(
            "Guardian will not drop tables that hold rows: {$named}. Set GUARDIAN_FORCE_ROLLBACK=true to drop them anyway.",
            $tables,
        );
    }
}
