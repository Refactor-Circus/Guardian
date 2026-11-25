<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * One of a user's past passwords (criterion 41), as the hash the user
 * model stored, which is never answered.
 *
 * @property string $id
 * @property int|string $user_id
 * @property string $password_hash
 * @property Carbon $created_at
 */
final class PasswordHistoryModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    public const null UPDATED_AT = null;

    protected $fillable = ['user_id', 'password_hash'];

    protected $hidden = ['password_hash'];

    public function getTable(): string
    {
        return Tables::name('password_histories');
    }

    /**
     * A user's history, newest first.
     *
     * @param  Builder<self>  $query
     */
    public function scopeNewestFor(Builder $query, int|string $user): void
    {
        $query->where('user_id', $user)->latest('created_at')->latest('id');
    }
}
