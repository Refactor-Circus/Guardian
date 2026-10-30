<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Services;

use RefactorCircus\Guardian\Domains\Scim\Exceptions\ScimException;

/**
 * Parses the subset of SCIM filters Roster supports (RFC 7644 §3.4.2.2):
 * `attribute op "value"` joined by `and`, with `eq`, `co` or `sw`.
 *
 * Returns plain conditions; callers map attributes to columns themselves,
 * so nothing from the filter ever reaches SQL except bound values.
 */
final class FilterParser
{
    private const string PATTERN = '/^\s*([A-Za-z][A-Za-z0-9.:\-]*)\s+(eq|co|sw)\s+("(?:[^"\\\\]|\\\\.)*"|true|false|null)\s*/i';

    /**
     * @param  array<int, string>  $attributes  the attributes allowed, lower-cased
     * @return array<int, array{attribute: string, operator: string, value: string|bool|null}>
     */
    public static function parse(string $filter, array $attributes): array
    {
        $conditions = [];
        $rest = $filter;

        while (true) {
            if (preg_match(self::PATTERN, $rest, $match) !== 1) {
                throw ScimException::invalidFilter("Unsupported filter [{$filter}]. Use attribute eq|co|sw \"value\", joined by and.");
            }

            $attribute = strtolower($match[1]);

            if (! in_array($attribute, $attributes, true)) {
                throw ScimException::invalidFilter("Filtering on [{$match[1]}] is not supported.");
            }

            $conditions[] = [
                'attribute' => $attribute,
                'operator' => strtolower($match[2]),
                'value' => self::value($match[3]),
            ];

            $rest = substr($rest, strlen($match[0]));

            if (trim($rest) === '') {
                return $conditions;
            }

            if (preg_match('/^and\s+/i', $rest, $and) !== 1) {
                throw ScimException::invalidFilter("Unsupported filter [{$filter}]. Only and is supported between conditions.");
            }

            $rest = substr($rest, strlen($and[0]));
        }
    }

    private static function value(string $raw): string|bool|null
    {
        return match (strtolower($raw)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => stripcslashes(substr($raw, 1, -1)),
        };
    }
}
