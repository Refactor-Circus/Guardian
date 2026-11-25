<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;

/**
 * Columns referencing the host's users, typed by `guardian.users.key_type`
 * (`int`, `ulid` or `uuid`).
 *
 * Used by Guardian's own migrations; set the key type before migrating. SSO
 * and SCIM's tables keep Roster's (`roster.users.key_type`), as they always
 * have.
 */
final class UserKey
{
    public static function column(Blueprint $table, string $name): ColumnDefinition
    {
        return match (config('guardian.users.key_type', 'int')) {
            'ulid' => $table->ulid($name),
            'uuid' => $table->uuid($name),
            default => $table->unsignedBigInteger($name),
        };
    }
}
