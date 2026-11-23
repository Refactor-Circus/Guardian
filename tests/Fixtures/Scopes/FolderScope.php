<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Fixtures\Scopes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Keystone\Authorization\Contracts\Scope;

/**
 * A Roster-free Keystone `Scope` for Guardian's role store tests: a folder
 * inside an optional parent folder, whose owner may do anything in it but
 * `guardian.roles.manage`.
 *
 * @property string $id
 * @property string $name
 * @property string|null $parent_id
 * @property int|string|null $owner_id
 */
final class FolderScope extends Model implements Scope
{
    use HasUlids;

    protected $table = 'test_folders';

    protected $fillable = ['name', 'parent_id', 'owner_id'];

    public static function migrate(): void
    {
        if (! Schema::hasTable('test_folders')) {
            Schema::create('test_folders', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('name');
                $table->ulid('parent_id')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->timestamps();
            });
        }
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
