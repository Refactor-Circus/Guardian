<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Support;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Keystone\Authorization\Abilities;

/**
 * Keeps the permission rows and built-in roles in step with Keystone's
 * `Abilities` and `guardian.roles.built_in` (criterion 7, D-006).
 *
 * It only ever adds: a system permission for each ability missing one, and
 * each built-in role missing (a shared role with that slug), granted the
 * abilities whose `grantTo` names it, only as it is created. It never
 * removes a permission or rewrites a role, so an admin's edits survive every
 * run, and running it again changes nothing.
 */
final class BuiltInRoles
{
    /**
     * The built-in roles, by slug.
     *
     * @return array<string, array{name: string, scope: string, super: bool}>
     */
    public static function roles(): array
    {
        $roles = [];

        foreach ((array) config('guardian.roles.built_in', []) as $slug => $role) {
            if (! is_string($slug) || ! is_array($role)) {
                continue;
            }

            $roles[$slug] = [
                'name' => is_string($role['name'] ?? null) ? $role['name'] : $slug,
                'scope' => is_string($role['scope'] ?? null) ? $role['scope'] : RoleModel::ANY,
                'super' => ($role['super'] ?? false) === true,
            ];
        }

        return $roles;
    }

    /**
     * Add what is missing. Returns the system permissions no ability defines
     * any more, which it leaves in place.
     *
     * @return list<string>
     */
    public static function sync(): array
    {
        $abilities = app(Abilities::class)->all();

        return array_values(DB::transaction(function () use ($abilities): array {
            foreach ($abilities as $name => $ability) {
                PermissionModel::query()->firstOrCreate(['name' => $name], ['description' => $ability['description'], 'system' => true]);
            }

            foreach (self::roles() as $slug => $definition) {
                if (RoleModel::query()->whereNull('owner_type')->where('slug', $slug)->exists()) {
                    continue;
                }

                $role = RoleModel::query()->create([
                    'name' => $definition['name'],
                    'slug' => $slug,
                    'scope' => $definition['scope'],
                    'super' => $definition['super'],
                    'system' => true,
                ]);

                $granted = array_keys(array_filter($abilities, fn (array $ability): bool => in_array($slug, $ability['grantTo'], true)));

                $role->permissions()->sync(PermissionModel::query()->whereIn('name', $granted)->pluck('id')->all());
            }

            return PermissionModel::query()
                ->where('system', true)
                ->whereNotIn('name', array_keys($abilities))
                ->orderBy('name')
                ->pluck('name')
                ->map(strval(...))
                ->values()
                ->all();
        }));
    }
}
