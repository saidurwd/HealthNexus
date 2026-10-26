<?php

namespace App\Services\Demo;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Test users for a demo / training organization, by module, permission and feature (AI-RULES 16):
 *  - one super user (role super_admin: everything);
 *  - for every module in config/modules.php that owns permissions: a `<key>.viewer` (view / list / report-view permissions only) and a
 *    `<key>.manager` (every permission of the module) — each with a role of the same purpose (`module_<key>_viewer`, `module_<key>_manager`);
 *  - one user for every business role the PermissionSeeder defines (`role.<name>`), so each real job can be tried;
 *  - one signed-in member with no permissions at all (`noaccess`), to see what a refusal looks like.
 * Idempotent: running it again updates the same users and roles. The e-mail domain is fixed per organization, so logins are predictable.
 */
class DemoAccess
{
    /** @return list<array{email: string, name: string, kind: string, roles: string}> */
    public function build(Organization $organization, Branch $branch, string $password): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $domain = Str::slug($organization->code).'.example.invalid';
        $hash = Hash::make($password);
        $rows = [];

        $rows[] = $this->user($organization, $branch, "superuser@$domain", 'Super User', 'super user', ['super_admin'], $hash, 'admin', 'manager');

        foreach (config('modules') as $key => $module) {
            $permissions = $this->modulePermissions($module['permissions'] ?? []);
            if ($permissions === []) {
                continue;
            }
            $viewer = array_values(array_filter($permissions, fn ($p) => $this->isRead($p)));

            Role::firstOrCreate(['name' => "module_{$key}_viewer", 'guard_name' => 'web'])->syncPermissions($viewer);
            Role::firstOrCreate(['name' => "module_{$key}_manager", 'guard_name' => 'web'])->syncPermissions($permissions);

            $rows[] = $this->user($organization, $branch, "$key.viewer@$domain", $module['name'].' — viewer', 'module viewer', ["module_{$key}_viewer"], $hash, 'staff', 'staff');
            $rows[] = $this->user($organization, $branch, "$key.manager@$domain", $module['name'].' — manager', 'module manager', ["module_{$key}_manager"], $hash, 'staff', 'manager');
        }

        foreach ($this->businessRoles() as $role) {
            $rows[] = $this->user($organization, $branch, 'role.'.str_replace('_', '-', $role)."@$domain", Str::headline($role), 'job role', [$role], $hash, 'staff', 'staff');
        }

        $rows[] = $this->user($organization, $branch, "noaccess@$domain", 'No Access Member', 'no permissions', [], $hash, 'staff', 'staff');

        return $rows;
    }

    /** @param list<string> $prefixes @return list<string> */
    private function modulePermissions(array $prefixes): array
    {
        $names = [];
        foreach ($prefixes as $prefix) {
            $names = array_merge($names, Permission::query()->where('name', 'like', "$prefix.%")->orWhere('name', 'like', "$prefix %")->pluck('name')->all());
        }

        return array_values(array_unique($names));
    }

    /** View / list / read-only permissions: the ones that cannot change anything. */
    private function isRead(string $permission): bool
    {
        return (bool) preg_match('/(^|[.\s])(view|list|show|read)($|[.\s_])/', $permission) && ! preg_match('/\.(export|print|manage|create|update|delete)/', $permission);
    }

    /** @return list<string> roles that are real jobs (not the per-module roles made here) */
    private function businessRoles(): array
    {
        return Role::query()->where('name', 'not like', 'module\_%')->where('name', '!=', 'super_admin')->orderBy('name')->pluck('name')->all();
    }

    /** @param list<string> $roles */
    private function user(Organization $organization, Branch $branch, string $email, string $name, string $kind, array $roles, string $hash, string $orgAccess, string $branchAccess): array
    {
        $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $hash, 'is_active' => true]);
        $user->organizations()->syncWithoutDetaching([$organization->id => ['access_level' => $orgAccess, 'is_default' => true]]);
        $user->branches()->syncWithoutDetaching([$branch->id => ['access_level' => $branchAccess, 'organization_id' => $organization->id, 'is_default' => true]]);
        $user->syncRoles($roles);

        return ['email' => $email, 'name' => $name, 'kind' => $kind, 'roles' => implode(', ', $roles)];
    }
}
