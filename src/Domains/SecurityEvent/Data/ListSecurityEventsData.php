<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Data;

use Carbon\CarbonInterface;
use Illuminate\Validation\Rule;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListSecurityEventsAction: filters (a type, a user by key, a
 * time range) and the cursor to continue from. Newest first.
 */
final readonly class ListSecurityEventsData extends Data
{
    public function __construct(
        public ?SecurityEventType $type = null,
        public ?string $user = null,
        public ?CarbonInterface $from = null,
        public ?CarbonInterface $to = null,
        public int $perPage = 50,
        public ?string $cursor = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::cursor(default: 50, max: 200);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['sometimes', 'nullable', Rule::enum(SecurityEventType::class)],
            'user' => ['sometimes', 'nullable', 'regex:/^[\w\-]{1,64}$/'],
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date', 'after_or_equal:from'],
        ] + self::pagination()->rules();
    }
}
