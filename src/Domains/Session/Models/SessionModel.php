<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * A signed-in session (criterion 35), found by the hash of a random key the
 * session holds (never by the session id), so it survives the id being
 * regenerated. A revoked session is signed out on its next request through
 * the `guardian.session` middleware.
 *
 * @property string $id
 * @property int|string $user_id
 * @property string|null $device_id
 * @property string $key_hash
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon $last_active_at
 * @property Carbon|null $revoked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read DeviceModel|null $device
 */
final class SessionModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['user_id', 'device_id', 'key_hash', 'ip', 'user_agent', 'last_active_at', 'revoked_at'];

    protected $hidden = ['key_hash'];

    public function getTable(): string
    {
        return Tables::name('sessions');
    }

    /**
     * @return BelongsTo<DeviceModel, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class, 'device_id');
    }

    /**
     * A user's sessions that have not been revoked.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActiveFor(Builder $query, int|string $user): void
    {
        $query->where('user_id', $user)->whereNull('revoked_at');
    }

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
