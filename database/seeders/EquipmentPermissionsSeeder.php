<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EquipmentPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['equipment.view', 'equipment.create', 'equipment.update', 'equipment.transfer'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (['Gerente', 'Vendedor', 'Almacén', 'Técnico de Planta', 'Técnico de Campo'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            $role->givePermissionTo('equipment.view');

            if (in_array($roleName, ['Vendedor', 'Técnico de Planta'], true)) {
                $role->givePermissionTo(['equipment.create', 'equipment.update', 'equipment.transfer']);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
