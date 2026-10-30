<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\UserKey;

/**
 * Roster's SSO and SCIM tables as Roster created them at the pinned SHA
 * (tests/moved-tests.json): its 000006 and 000007 create migrations, with the
 * lookup indexes of its 000010 and the performance indexes of its
 * 2026_10_12_000001 for these tables.
 *
 * A Roster that has handed SSO and SCIM over no longer ships those
 * migrations, so the upgrade tests bring them here. The only change is the
 * table names: Roster's Tables no longer knows these keys, so they are read
 * as the upgrade reads them (`roster.tables.{key}`, default `roster_{key}`).
 * A table that already exists (a Roster that still ships SSO and SCIM created
 * it) is left as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->create('sso_connections', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained(Tables::name('organizations'))->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('protocol', 8);
            $table->text('config');
            $table->boolean('jit')->default(true);
            $table->boolean('enforced')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->index('organization_id', $this->name('sso_connections').'_organization_id_lookup_index');
        });

        $this->create('sso_identities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('connection_id')->constrained($this->name('sso_connections'))->cascadeOnDelete();
            UserKey::column($table, 'user_id')->index();
            $table->string('subject');
            $table->string('email')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['connection_id', 'subject']);
        });

        $this->create('scim_tokens', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained(Tables::name('organizations'))->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->foreignUlid('sso_connection_id')->nullable()->constrained($this->name('sso_connections'))->nullOnDelete();
            UserKey::column($table, 'created_by')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index('organization_id', $this->name('scim_tokens').'_organization_id_lookup_index');
            $table->index('sso_connection_id', $this->name('scim_tokens').'_sso_connection_id_lookup_index');
        });

        $this->create('scim_users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained(Tables::name('organizations'))->cascadeOnDelete();
            UserKey::column($table, 'user_id');
            $table->string('external_id')->nullable();
            $table->boolean('created_by_scim')->default(false);
            $table->boolean('deactivated_by_scim')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index(['organization_id', 'external_id']);
            $table->index(['organization_id', 'created_at', 'id'], $this->name('scim_users').'_organization_created_index');
        });

        $this->create('scim_groups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained(Tables::name('organizations'))->cascadeOnDelete();
            $table->foreignUlid('team_id')->unique()->constrained(Tables::name('teams'))->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->timestamps();
            $table->index('organization_id', $this->name('scim_groups').'_organization_id_lookup_index');
            $table->index(['organization_id', 'created_at', 'id'], $this->name('scim_groups').'_organization_created_index');
        });
    }

    public function down(): void
    {
        foreach (['scim_groups', 'scim_users', 'scim_tokens', 'sso_identities', 'sso_connections'] as $key) {
            Schema::dropIfExists($this->name($key));
        }
    }

    private function create(string $key, Closure $columns): void
    {
        if (! Schema::hasTable($this->name($key))) {
            Schema::create($this->name($key), $columns);
        }
    }

    private function name(string $key): string
    {
        $table = config("roster.tables.{$key}");

        return is_string($table) && $table !== '' ? $table : 'roster_'.$key;
    }
};
