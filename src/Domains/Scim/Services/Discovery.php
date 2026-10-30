<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Services;

/**
 * ServiceProviderConfig, ResourceTypes and Schemas (RFC 7643 §5–7).
 */
final class Discovery
{
    /**
     * @return array<string, mixed>
     */
    public static function serviceProviderConfig(string $base): array
    {
        return [
            'schemas' => [Scim::CONFIG],
            'documentationUri' => 'https://github.com/Refactor-Circus/roster#scim-provisioning',
            'patch' => ['supported' => true],
            'bulk' => [
                'supported' => true,
                'maxOperations' => (int) config('guardian.scim.bulk.max_operations', 100),
                'maxPayloadSize' => (int) config('guardian.scim.bulk.max_payload_bytes', 1048576),
            ],
            'filter' => ['supported' => true, 'maxResults' => (int) config('guardian.scim.max_count', 500)],
            'changePassword' => ['supported' => false],
            'sort' => ['supported' => false],
            'etag' => ['supported' => true],
            'authenticationSchemes' => [[
                'type' => 'oauthbearertoken',
                'name' => 'OAuth Bearer Token',
                'description' => "A SCIM token issued for this organization in Roster, sent as 'Authorization: Bearer <token>'.",
                'primary' => true,
            ]],
            'meta' => ['resourceType' => 'ServiceProviderConfig', 'location' => $base.'/ServiceProviderConfig'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function resourceTypes(string $base): array
    {
        return [
            ['schemas' => [Scim::RESOURCE_TYPE], 'id' => 'User', 'name' => 'User', 'endpoint' => '/Users', 'schema' => Scim::USER, 'meta' => ['resourceType' => 'ResourceType', 'location' => $base.'/ResourceTypes/User']],
            ['schemas' => [Scim::RESOURCE_TYPE], 'id' => 'Group', 'name' => 'Group', 'endpoint' => '/Groups', 'schema' => Scim::GROUP, 'meta' => ['resourceType' => 'ResourceType', 'location' => $base.'/ResourceTypes/Group']],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function schemas(): array
    {
        $attribute = fn (string $name, string $type = 'string', bool $required = false, string $uniqueness = 'none', array $sub = []): array => array_filter([
            'name' => $name,
            'type' => $type,
            'multiValued' => in_array($name, ['emails', 'groups', 'members'], true),
            'required' => $required,
            'mutability' => in_array($name, ['id', 'groups'], true) ? 'readOnly' : 'readWrite',
            'returned' => 'default',
            'uniqueness' => $uniqueness,
            'caseExact' => false,
            'subAttributes' => $sub === [] ? null : $sub,
        ], fn (mixed $value): bool => $value !== null);

        return [
            [
                'schemas' => [Scim::SCHEMA],
                'id' => Scim::USER,
                'name' => 'User',
                'attributes' => [
                    $attribute('userName', required: true, uniqueness: 'server'),
                    $attribute('name', 'complex', sub: [$attribute('formatted'), $attribute('givenName'), $attribute('familyName')]),
                    $attribute('displayName'),
                    $attribute('emails', 'complex', sub: [$attribute('value'), $attribute('type'), $attribute('primary', 'boolean')]),
                    $attribute('active', 'boolean'),
                    $attribute('groups', 'complex', sub: [$attribute('value'), $attribute('display')]),
                ],
                'meta' => ['resourceType' => 'Schema'],
            ],
            [
                'schemas' => [Scim::SCHEMA],
                'id' => Scim::GROUP,
                'name' => 'Group',
                'attributes' => [
                    $attribute('displayName', required: true),
                    $attribute('members', 'complex', sub: [$attribute('value'), $attribute('display')]),
                ],
                'meta' => ['resourceType' => 'Schema'],
            ],
        ];
    }
}
