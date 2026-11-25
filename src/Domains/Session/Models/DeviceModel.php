<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * A browser a user signs in from (criterion 35), told apart by an HMAC of
 * its user agent, which is never answered.
 *
 * @property string $id
 * @property int|string $user_id
 * @property string $fingerprint_hash
 * @property string|null $name
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 * @property string|null $last_ip
 */
final class DeviceModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['user_id', 'fingerprint_hash', 'name', 'first_seen_at', 'last_seen_at', 'last_ip'];

    protected $hidden = ['fingerprint_hash'];

    public function getTable(): string
    {
        return Tables::name('devices');
    }

    /**
     * @return HasMany<SessionModel, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(SessionModel::class, 'device_id');
    }

    /**
     * A user's devices.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOwnedBy(Builder $query, int|string $user): void
    {
        $query->where('user_id', $user);
    }

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
