<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Guardian\Support\Users;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;

/**
 * One security event (criterion 22): what happened, to whom, from where.
 * Written only by `RecordSecurityEventAction`. A failed sign-in's identifier
 * is kept only as an HMAC; the context never holds a secret. Pruned after
 * `guardian.security_log.retention_days`.
 *
 * @property string $id
 * @property SecurityEventType $type
 * @property int|string|null $user_id
 * @property string|null $identifier_hash
 * @property string|null $ip
 * @property string|null $user_agent
 * @property array<string, mixed> $context
 * @property Carbon $occurred_at
 */
final class SecurityEventModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;
    use Prunable;

    public $timestamps = false;

    protected $fillable = ['type', 'user_id', 'identifier_hash', 'ip', 'user_agent', 'context', 'occurred_at'];

    protected $attributes = [
        'context' => '{}',
    ];

    public function getTable(): string
    {
        return Tables::name('security_events');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'user_id');
    }

    /**
     * Rows older than `guardian.security_log.retention_days`; none when it
     * is null.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $days = config('guardian.security_log.retention_days');

        return is_numeric($days)
            ? self::query()->where('occurred_at', '<', now()->subDays((int) $days))
            : self::query()->whereRaw('1 = 0');
    }

    protected function casts(): array
    {
        return [
            'type' => SecurityEventType::class,
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
