<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * One recovery code (criterion 28), kept only as an HMAC. Each works once.
 *
 * @property string $id
 * @property int|string $user_id
 * @property string $code_hash
 * @property Carbon|null $used_at
 */
final class RecoveryCodeModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['user_id', 'code_hash', 'used_at'];

    protected $hidden = ['code_hash'];

    public function getTable(): string
    {
        return Tables::name('recovery_codes');
    }

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }
}
