<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * An IP rule (criterion 52): an IPv4 or IPv6 address or CIDR range,
 * allowed or denied, until `expires_at` when one is set.
 *
 * @property string $id
 * @property string $cidr
 * @property string $action
 * @property string|null $description
 * @property Carbon|null $expires_at
 * @property string|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class IpRuleModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    public const string ALLOW = 'allow';

    public const string DENY = 'deny';

    protected $fillable = ['cidr', 'action', 'description', 'expires_at', 'created_by'];

    public function getTable(): string
    {
        return Tables::name('ip_rules');
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
