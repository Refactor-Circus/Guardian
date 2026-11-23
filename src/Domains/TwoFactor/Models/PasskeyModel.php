<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Guardian\Support\Users;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * One of a user's passkeys (criterion 30): a WebAuthn credential's id
 * (base64url) and public key (base64 COSE), with the authenticator's last
 * signature counter. A passkey counts as soon as it is registered.
 *
 * @property string $id
 * @property int|string $user_id
 * @property string $credential_id
 * @property string $public_key
 * @property int $sign_count
 * @property list<string>|null $transports
 * @property string|null $aaguid
 * @property string|null $label
 * @property Carbon|null $last_used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class PasskeyModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['user_id', 'credential_id', 'public_key', 'sign_count', 'transports', 'aaguid', 'label', 'last_used_at'];

    protected $hidden = ['public_key'];

    protected $attributes = [
        'sign_count' => 0,
    ];

    public function getTable(): string
    {
        return Tables::name('passkeys');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'user_id');
    }

    /**
     * A user's passkeys.
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
            'sign_count' => 'integer',
            'transports' => 'array',
            'last_used_at' => 'datetime',
        ];
    }
}
