<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Services;

use Illuminate\Http\JsonResponse;

/**
 * SCIM schema URNs and response helpers.
 */
final class Scim
{
    public const string USER = 'urn:ietf:params:scim:schemas:core:2.0:User';

    public const string GROUP = 'urn:ietf:params:scim:schemas:core:2.0:Group';

    public const string LIST = 'urn:ietf:params:scim:api:messages:2.0:ListResponse';

    public const string PATCH = 'urn:ietf:params:scim:api:messages:2.0:PatchOp';

    public const string ERROR = 'urn:ietf:params:scim:api:messages:2.0:Error';

    public const string BULK_REQUEST = 'urn:ietf:params:scim:api:messages:2.0:BulkRequest';

    public const string BULK_RESPONSE = 'urn:ietf:params:scim:api:messages:2.0:BulkResponse';

    public const string CONFIG = 'urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig';

    public const string RESOURCE_TYPE = 'urn:ietf:params:scim:schemas:core:2.0:ResourceType';

    public const string SCHEMA = 'urn:ietf:params:scim:schemas:core:2.0:Schema';

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    public static function response(array $body, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($body, $status, ['Content-Type' => 'application/scim+json'] + $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * A weak ETag over the resource's content (everything but `meta`), so any
     * change yields a new version.
     *
     * @param  array<string, mixed>  $resource
     */
    public static function version(array $resource): string
    {
        unset($resource['meta']);

        return 'W/"'.substr(hash('sha256', (string) json_encode($resource)), 0, 32).'"';
    }

    /**
     * Whether an If-Match / If-None-Match header names this version.
     */
    public static function matches(?string $header, string $version): bool
    {
        if ($header === null || trim($header) === '*') {
            return $header !== null;
        }

        $strip = fn (string $tag): string => trim(preg_replace('/^W\//', '', trim($tag)) ?? '', '"');

        foreach (explode(',', $header) as $tag) {
            if ($strip($tag) === $strip($version)) {
                return true;
            }
        }

        return false;
    }
}
