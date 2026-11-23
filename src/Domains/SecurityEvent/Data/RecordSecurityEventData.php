<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for RecordSecurityEventAction: what happened, the user it happened
 * to (by key), the sign-in identifier typed (stored only as an HMAC), where
 * from, and a context the action strips of secrets.
 */
final readonly class RecordSecurityEventData extends Data
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public SecurityEventType $type,
        public ?string $user = null,
        public ?string $identifier = null,
        public ?string $ip = null,
        public ?string $userAgent = null,
        public array $context = [],
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(SecurityEventType::class)],
            'user' => ['sometimes', 'nullable', 'regex:/^[\w\-]{1,64}$/'],
            'identifier' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ip' => ['sometimes', 'nullable', 'ip'],
            'user_agent' => ['sometimes', 'nullable', 'string'],
            'context' => ['sometimes', 'array'],
        ];
    }
}
