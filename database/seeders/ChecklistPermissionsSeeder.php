<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ChecklistPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'checklists.view',
            'checklists.fill',
            'deficiencies.view',
            'deficiencies.create',
            'deficiencies.authorize',
            'deficiencies.resolve',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolesPermissions = [
            'Gerente' => ['checklists.view', 'deficiencies.view', 'deficiencies.authorize'],
            'Vendedor' => ['checklists.view', 'deficiencies.view', 'deficiencies.create', 'deficiencies.authorize'],
            'Técnico de Planta' => ['checklists.view', 'checklists.fill', 'deficiencies.view', 'deficiencies.create', 'deficiencies.resolve'],
            'Técnico de Campo' => ['checklists.view', 'checklists.fill', 'deficiencies.view', 'deficiencies.create', 'deficiencies.resolve'],
            'Administrador' => $permissions,
        ];

        foreach ($rolesPermissions as $roleName => $rolePerms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($rolePerms);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
