<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for UpdatePermissionAction: a new name (never for a built-in
 * permission) or description; fields left out are kept, a null description
 * clears it.
 */
final readonly class UpdatePermissionData extends Data
{
    /**
     * A dotted name: `invoices.edit`, `guardian.roles.view`.
     */
    public const string NAME = '/^[a-z0-9_-]+(\.[a-z0-9_-]+)*$/';

    public function __construct(
        public string|Missing $name = new Missing,
        public string|Missing|null $description = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', 'regex:'.self::NAME],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
