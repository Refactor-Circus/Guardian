<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Services;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Users;

/**
 * Roster records as SCIM resources (RFC 7643 §4).
 */
final class ScimMapper
{
    public function __construct(
        private readonly Users $users,
        private readonly ScimContext $context,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function user(ScimUserModel $scimUser): array
    {
        $user = $scimUser->user;
        $email = $user instanceof Model ? $this->users->email($user) : null;
        $name = $user instanceof Model ? ($this->users->name($user) ?? $email) : null;
        [$given, $family] = array_pad(explode(' ', (string) $name, 2), 2, null);
        $display = $user instanceof Model ? ($this->users->profileIfExists($user)->display_name ?? $name) : null;

        $resource = [
            'schemas' => [Scim::USER],
            'id' => $scimUser->id,
            'externalId' => $scimUser->external_id,
            'userName' => $email,
            'name' => array_filter(['formatted' => $name, 'givenName' => $given, 'familyName' => $family], fn (mixed $value): bool => $value !== null && $value !== ''),
            'displayName' => $display,
            'emails' => $email === null ? [] : [['value' => $email, 'type' => 'work', 'primary' => true]],
            'active' => $scimUser->active,
            'groups' => array_map(fn (ScimGroupModel $group): array => [
                'value' => $group->id,
                'display' => $group->team?->name,
                '$ref' => $this->location('Groups', $group->id),
            ], app(ScimUsers::class)->groupsOf($scimUser)),
        ];

        $resource = array_filter($resource, fn (mixed $value): bool => $value !== null);

        return $resource + ['meta' => [
            'resourceType' => 'User',
            'created' => $scimUser->created_at?->toIso8601String(),
            'lastModified' => $scimUser->updated_at?->toIso8601String(),
            'location' => $this->location('Users', $scimUser->id),
            'version' => Scim::version($resource),
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    public function group(ScimGroupModel $group): array
    {
        $team = $group->team;
        $members = [];

        if ($team instanceof TeamModel) {
            $scimUsers = $this->context->members[$group->id]
                ?? ScimUserModel::query()->where('organization_id', $group->organization_id)->whereIn('user_id', $team->memberships()->pluck('user_id'))->with('user')->orderBy('id')->get()->all();

            foreach ($scimUsers as $scimUser) {
                $user = $scimUser->user;
                $members[] = [
                    'value' => $scimUser->id,
                    'display' => $user instanceof Model ? ($this->users->name($user) ?? $this->users->email($user)) : null,
                    '$ref' => $this->location('Users', $scimUser->id),
                ];
            }
        }

        $resource = array_filter([
            'schemas' => [Scim::GROUP],
            'id' => $group->id,
            'externalId' => $group->external_id,
            'displayName' => $team?->name,
            'members' => $members,
        ], fn (mixed $value): bool => $value !== null);

        return $resource + ['meta' => [
            'resourceType' => 'Group',
            'created' => $group->created_at?->toIso8601String(),
            'lastModified' => max($group->updated_at?->toIso8601String(), $team?->updated_at?->toIso8601String()),
            'location' => $this->location('Groups', $group->id),
            'version' => Scim::version($resource),
        ]];
    }

    /**
     * @param  array<int, array<string, mixed>>  $resources
     * @return array<string, mixed>
     */
    public function list(array $resources, int $total, int $startIndex): array
    {
        return [
            'schemas' => [Scim::LIST],
            'totalResults' => $total,
            'startIndex' => $startIndex,
            'itemsPerPage' => count($resources),
            'Resources' => $resources,
        ];
    }

    private function location(string $type, string $id): string
    {
        return route('guardian.scim.'.strtolower($type).'.show', [$this->context->organization()->slug, $id]);
    }
}
