<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Fixtures\Scopes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RefactorCircus\Keystone\Authorization\Contracts\Scope;

/**
 * A Roster-free Keystone `Scope` for Guardian's role store tests: a folder
 * inside an optional parent folder, whose owner may do anything in it but
 * `guardian.roles.manage`. It stands in for a host's scope as strictly as
 * Roster's organization: a ULID key, routed by a slug (its route key, which
 * a screen passes as the record), and no morph alias (D-051).
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $parent_id
 * @property int|string|null $owner_id
 */
final class FolderScope extends Model implements Scope
{
    use HasUlids;

    protected $table = 'test_folders';

    protected $fillable = ['name', 'slug', 'parent_id', 'owner_id'];

    public static function migrate(): void
    {
        if (! Schema::hasTable('test_folders')) {
            Schema::create('test_folders', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->ulid('parent_id')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        self::creating(function (self $folder): void {
            $folder->slug ??= Str::slug($folder->name).'-'.Str::lower(Str::random(6));
        });
    }

    public function parentScope(): ?Scope
    {
        return $this->parent_id === null ? null : self::query()->find($this->parent_id);
    }

    public function grantsByItself(Authenticatable $user, string $ability): bool
    {
        return $this->owner_id !== null
            && (string) $this->owner_id === (string) $user->getAuthIdentifier()
            && $ability !== 'guardian.roles.manage';
    }
}
