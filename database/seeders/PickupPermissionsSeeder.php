<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PickupPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'pickups.view',
            'pickups.create',
            'pickups.custody',
            'actas.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolesPermissions = [
            'Gerente' => ['pickups.view', 'pickups.custody', 'actas.view'],
            'Vendedor' => ['pickups.view', 'pickups.create', 'actas.view'],
            'Técnico de Planta' => ['pickups.view', 'pickups.custody', 'actas.view'],
            'Técnico de Campo' => ['pickups.view', 'pickups.create', 'pickups.custody', 'actas.view'],
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
