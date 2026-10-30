<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Services;

use RefactorCircus\Guardian\Domains\Scim\Exceptions\ScimException;

/**
 * Applies SCIM PATCH operations (RFC 7644 §3.5.2) to a resource's SCIM view.
 *
 * Supports add, replace and remove on simple and dotted paths, on
 * `emails[type eq "..."].value`, on `members` and `members[value eq "..."]`,
 * and path-less value objects (as Microsoft Entra ID sends them).
 */
final class PatchApplier
{
    /** @var array<int, string> */
    private const array SIMPLE = ['active', 'username', 'displayname', 'externalid', 'name.givenname', 'name.familyname', 'name.formatted', 'emails'];

    /**
     * @param  array<string, mixed>  $resource
     * @param  array<int, mixed>  $operations
     * @return array<string, mixed>
     */
    public static function apply(array $resource, array $operations): array
    {
        if ($operations === []) {
            throw ScimException::invalidValue('PATCH needs at least one operation.');
        }

        foreach ($operations as $operation) {
            if (! is_array($operation)) {
                throw ScimException::invalidValue('Each PATCH operation must be an object.');
            }

            $op = strtolower((string) ($operation['op'] ?? ''));
            $path = $operation['path'] ?? null;
            $value = $operation['value'] ?? null;

            if (! in_array($op, ['add', 'replace', 'remove'], true)) {
                throw ScimException::invalidValue("Unsupported operation [{$op}].");
            }

            if ($path === null || $path === '') {
                if (! is_array($value) || $op === 'remove') {
                    throw ScimException::invalidValue('An operation without a path needs an object value.');
                }

                foreach ($value as $key => $item) {
                    $resource = self::applyPath($resource, $op, (string) $key, $item);
                }

                continue;
            }

            $resource = self::applyPath($resource, $op, (string) $path, $value);
        }

        return $resource;
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>
     */
    private static function applyPath(array $resource, string $op, string $path, mixed $value): array
    {
        $normal = strtolower(preg_replace('/^urn:[^:]+(?::[^:]+)*:(User|Group):/i', '', $path) ?? $path);

        if (preg_match('/^members\[value eq "([^"]+)"\]$/i', $path, $match) === 1) {
            $resource['members'] = array_values(array_filter(
                (array) ($resource['members'] ?? []),
                fn (mixed $member): bool => ! (is_array($member) && ($member['value'] ?? null) === $match[1]),
            ));

            return $resource;
        }

        if (preg_match('/^emails\[type eq "([^"]+)"\]\.value$/i', $path, $match) === 1) {
            return self::setEmail($resource, $op === 'remove' ? null : (string) $value, $match[1]);
        }

        if ($normal === 'members') {
            return self::members($resource, $op, $value);
        }

        if (! in_array($normal, self::SIMPLE, true)) {
            throw ScimException::invalidPath($path);
        }

        if ($normal === 'emails') {
            $first = is_array($value) ? ($value[0] ?? null) : null;

            return self::setEmail($resource, $op === 'remove' ? null : (is_array($first) ? (string) ($first['value'] ?? '') : null), 'work');
        }

        if ($normal === 'active') {
            $value = self::boolean($value);
        }

        [$key, $sub] = array_pad(explode('.', $normal, 2), 2, null);
        $key = self::canonical($key);

        if ($sub !== null) {
            $sub = self::canonical($sub);
            $nested = (array) ($resource[$key] ?? []);

            if ($op === 'remove') {
                unset($nested[$sub]);
            } else {
                $nested[$sub] = $value;
            }

            // A changed name part makes the old formatted name stale.
            if ($key === 'name' && $sub !== 'formatted') {
                unset($nested['formatted']);
            }

            $resource[$key] = $nested;

            return $resource;
        }

        if ($op === 'remove') {
            unset($resource[$key]);
        } else {
            $resource[$key] = $key === 'name' && is_array($value) ? array_merge((array) ($resource['name'] ?? []), $value) : $value;
        }

        return $resource;
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>
     */
    private static function members(array $resource, string $op, mixed $value): array
    {
        $given = array_values(array_filter(array_map(
            fn (mixed $member): ?string => is_array($member) && is_scalar($member['value'] ?? null) ? (string) $member['value'] : null,
            is_array($value) ? $value : [],
        )));

        $current = array_map(fn (mixed $member): string => (string) (is_array($member) ? ($member['value'] ?? '') : ''), (array) ($resource['members'] ?? []));

        $members = match ($op) {
            'add' => array_values(array_unique([...$current, ...$given])),
            'replace' => $given,
            default => $given === [] ? [] : array_values(array_diff($current, $given)),
        };

        $resource['members'] = array_map(fn (string $id): array => ['value' => $id], $members);

        return $resource;
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>
     */
    private static function setEmail(array $resource, ?string $email, string $type): array
    {
        $resource['emails'] = $email === null || $email === '' ? [] : [['value' => $email, 'type' => $type, 'primary' => true]];

        return $resource;
    }

    private static function boolean(mixed $value): bool
    {
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1'], true);
        }

        return (bool) $value;
    }

    private static function canonical(string $key): string
    {
        return match ($key) {
            'username' => 'userName',
            'displayname' => 'displayName',
            'externalid' => 'externalId',
            'givenname' => 'givenName',
            'familyname' => 'familyName',
            default => $key,
        };
    }
}
