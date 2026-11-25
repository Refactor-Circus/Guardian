<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * A two-factor enforcement policy (criterion 34). With no scope and no role
 * it covers everyone; with a role, that role's holders (in its scope, when
 * it has one); with a scope, users assigned there or below. `methods` limits
 * the factors that count (null: any), and `grace_days` gives users time to
 * enrol after the policy is made.
 *
 * @property string $id
 * @property string|null $scope_type
 * @property string|null $scope_id
 * @property string|null $role
 * @property bool $required
 * @property list<string>|null $methods
 * @property int $grace_days
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class TwoFactorPolicyModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['scope_type', 'scope_id', 'role', 'required', 'methods', 'grace_days'];

    protected $attributes = [
        'required' => true,
        'grace_days' => 0,
    ];

    public function getTable(): string
    {
        return Tables::name('two_factor_policies');
    }

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'methods' => 'array',
            'grace_days' => 'integer',
        ];
    }
}
