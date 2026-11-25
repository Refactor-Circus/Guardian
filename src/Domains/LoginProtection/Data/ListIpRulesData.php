<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListIpRulesAction: the page to show, oldest first.
 */
final readonly class ListIpRulesData extends Data
{
    public function __construct(
        public int $perPage = 50,
        public ?int $page = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::pages(default: 50, max: 200);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return self::pagination()->rules();
    }
}
