<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Domains\TwoFactor\Enums\TwoFactorMethodType;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Guardian\Support\Users;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * One of a user's two-factor methods (criterion 26): a TOTP authenticator, or
 * an email address or phone number one-time codes are sent to. The TOTP
 * secret and the destination are encrypted at rest and never serialized; a
 * method counts once it is confirmed.
 *
 * @property string $id
 * @property int|string $user_id
 * @property TwoFactorMethodType $type
 * @property string|null $secret
 * @property string|null $destination
 * @property string|null $label
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $last_used_at
 * @property int|null $last_timestep
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class TwoFactorMethodModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['user_id', 'type', 'secret', 'destination', 'label', 'confirmed_at', 'last_used_at', 'last_timestep'];

    protected $hidden = ['secret', 'destination'];

    public function getTable(): string
    {
        return Tables::name('two_factor_methods');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'user_id');
    }

    /**
     * A user's methods.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOwnedBy(Builder $query, int|string $user): void
    {
        $query->where('user_id', $user);
    }

    /**
     * Confirmed methods only.
     *
     * @param  Builder<self>  $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->whereNotNull('confirmed_at');
    }

    public function confirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    protected function casts(): array
    {
        return [
            'type' => TwoFactorMethodType::class,
            'secret' => 'encrypted',
            'destination' => 'encrypted',
            'confirmed_at' => 'datetime',
            'last_used_at' => 'datetime',
            'last_timestep' => 'integer',
        ];
    }
}
