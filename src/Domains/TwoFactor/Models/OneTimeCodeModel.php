<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * One email or SMS code (criterion 29), kept only as an HMAC: six digits,
 * single use, valid until `expires_at` for at most five attempts.
 *
 * @property string $id
 * @property int|string $user_id
 * @property string $method_id
 * @property string $code_hash
 * @property Carbon $expires_at
 * @property int $attempts
 * @property Carbon|null $consumed_at
 */
final class OneTimeCodeModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['user_id', 'method_id', 'code_hash', 'expires_at', 'attempts', 'consumed_at'];

    protected $hidden = ['code_hash'];

    protected $attributes = [
        'attempts' => 0,
    ];

    public function getTable(): string
    {
        return Tables::name('one_time_codes');
    }

    /**
     * @return BelongsTo<TwoFactorMethodModel, $this>
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(TwoFactorMethodModel::class, 'method_id');
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
            'consumed_at' => 'datetime',
        ];
    }
}
